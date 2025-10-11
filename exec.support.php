<?php
include_once(dirname(__FILE__).'/ressources/class.support-tracker.inc');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"] = new sockets();
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"] = false;
$GLOBALS["SCHEDULE"] = false;
if (is_array($argv)) {
    if (preg_match("#--verbose#", implode(" ", $argv))) {
        $GLOBALS["VERBOSE"] = true;
        echo "VERBOSE!\n";
    }
    if (preg_match("#--schedule#", implode(" ", $argv))) {
        $GLOBALS["SCHEDULE"] = true;
    }
    if (preg_match("#--force#", implode(" ", $argv))) {
        $GLOBALS["FORCE"] = true;
    }
    if ($GLOBALS["VERBOSE"]) {
        ini_set('html_errors', 0);
        ini_set('display_errors', 1);
        ini_set('error_reporting', E_ALL);
    }
}
if (function_exists("posix_getuid")) {
    if (posix_getuid() <> 0) {
        die("Cannot be used in web server mode\n\n");
    }
}
$GLOBALS["posix_getuid"] = 0;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.sqlite.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');


if ($argv[1] == "--get-mailbox") {
    get_mailbox();
    exit;
}
if ($argv[1] == "--create-bug") {
    create_bug();
    exit;
}
if ($argv[1] == "--get-tickets") {
    get_tickets();
    exit;
}
if ($argv[1] == "--reply") {
    reply();
    exit;
}
if ($argv[1] == "--delete-bug") {
    delete_bug($argv[2]);
    exit;
}
if ($argv[1] == "--support-tool") {
    support_tool($argv[2]);
    exit;
}

if ($argv[1] == "--refresh") {
    refresh_ticket($argv[2]);
    exit;
}

if ($argv[1] == "--recover") {
    recoverToken($argv[2]);
    exit;
}
if ($argv[1] == "--delete-account") {
    deleteAccount($argv[2]);
    exit;
}

function build_progress($pourc, $text):bool{
    $echotext = $text;
    echo "Starting......: " . date("H:i:s") . " $pourc% $echotext\n";
    $cachefile = PROGRESS_DIR . "/support.progress";
    $array["POURC"] = $pourc;
    $array["TEXT"] = $text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile, 0755);
    return true;
}

function deleteAccount($force=false)
{
    build_progress(10, "{check_account}");
    $q = new lib_sqlite("/home/artica/SQLITE/support.db");
    $q->QUERY_SQL("DELETE FROM tickets");
    $q->QUERY_SQL("DELETE FROM threads");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportName", "");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportName", "");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportEmail", "");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportSecCode", "");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportCustomerID", "");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportMID", "");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportReply", "");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportCreateBug", "");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportProxyName", "");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportProxyPort", "");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportUseProxy", "");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportChecked", "");
    build_progress(100, "{done}");


}
function recoverToken($force=false){
    build_progress(10, "{check_account}");
    $SupportTracker=new SupportTracker();
    if ($SupportTracker->supportEmail == null) {
        build_progress(110, "{check_account} {failed} Unable to get account information");
        if ($GLOBALS["VERBOSE"]) {
            echo "Failed supportEmail is Null\n";
        }
        return false;
    }

    build_progress(20, "{check_account} $SupportTracker->supportEmail");

    $MAIN = GetMailboxInfos( $SupportTracker->supportEmail,true);

}
function get_mailbox()
{
    $unix = new unix();
    $pidTime = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".time";
    if (!$GLOBALS["FORCE"]) {
        $time = $unix->file_time_min($pidTime);
        if ($time < 30) {
            build_progress(10, "{check_account} failed $time<30");
            exit();
        }
    }
    @unlink($pidTime);
    @file_put_contents($pidTime, time());

    build_progress(10, "{check_account}");
    $SupportTracker=new SupportTracker();

    //$url="https://bugs.articatech.com/rest.cgi/user?names={$LicenseInfos["EMAIL"]}";


    if ($SupportTracker->supportEmail == null) {
        build_progress(110, "{check_account} {failed} Unable to get account information");
        if ($GLOBALS["VERBOSE"]) {
            echo "Failed supportEmail is Null\n";
        }
        return false;
    }

    build_progress(20, "{check_account} $SupportTracker->supportEmail");

    $MAIN = GetMailboxInfos( $SupportTracker->supportEmail);

    if (!$MAIN) {
        if ($GLOBALS["ERROR_API"] > 0) {
            build_progress(110, "{check_account} {failed} {error} {$GLOBALS["ERROR_API"]}");
            if ($GLOBALS["VERBOSE"]) {
                echo "Failed\n";
            }
            return false;
        }
    }

    if (!isset($MAIN["MID"])) {
        build_progress(110, "{check_account} {failed} {error} line " . __LINE__);
        return false;
    }

    $MID = intval($MAIN["MID"]);

    if ($MID == 0) {
        build_progress(110, "{check_account} {failed} {error} line " . __LINE__);
        return false;
    }
get_tickets();
    build_progress(90, "{check_account}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportMID", base64_encode($MID));
    build_progress(100, "{check_account} {success}");
    return true;
}

function ExpectedStruct($json):bool{

    if (
        isset($json['_embedded']) &&
        is_array($json['_embedded']) &&
        isset($json['_embedded']['mailboxes']) &&
        is_array($json['_embedded']['mailboxes']) &&
        isset($json['_embedded']['mailboxes'][0]) &&
        is_array($json['_embedded']['mailboxes'][0]) &&
        array_key_exists('id', $json['_embedded']['mailboxes'][0])
    ) {
        return true;
    }
    return false;
}


function GetMailboxInfos($email,$recover=false){

    $url = "mailboxes?";
    if($recover){
        $url = "mailboxes?action=RecoverSecCode&";
    }
    $data = build_curl($url, null, "GET");
    if (!$data) {
        return false;
    }
    $json = json_decode($data, true);

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportChecked", 1);

    if(!ExpectedStruct($json)){
        return false;
    }

    if (!array_key_exists('id', $json['_embedded']['mailboxes'][0])) {
        if (!array_key_exists('"message"', $json)) {
            echo $json['message'];
        }
        return false;
    }
    if ($GLOBALS["VERBOSE"]) {
        var_dump($json);
    }
    $MID = $json['_embedded']['mailboxes'][0]['id'];
    if ($MID == 0) {
        return false;
    }
    $MAIN["MID"] = $MID;
    echo "MY ID IS =>" . $MAIN["MID"];
    return $MAIN;


}

function reply()
{
    if (!is_file("/etc/artica-postfix/settings/Daemons/supportReply")) {
        return;
    }
    $MAIN = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportReply"));
    //@unlink("/etc/artica-postfix/settings/Daemons/supportReply");
    $bugid = $MAIN["reply"];
    $content = $MAIN["replycontent"];
    $SupportTracker=new SupportTracker();
    $supportEmail = $SupportTracker->supportEmail;
    $supportMID = $SupportTracker->supportMID;
    $supportCustomerID=$SupportTracker->supportCustomerID;

//    $supportEmail = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportEmail");
//    $supportCustomerID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportCustomerID"));
//    $supportMID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportMID"));
    $LicenseInfos= $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));

    if ($supportEmail == null) {
        echo "Not Account defined, exit program.\n";
        die();
    }
    if($supportCustomerID==0){
        echo "Not Account defined, exit program.\n";
        die();
    }

    if($supportMID==0){
        echo "Not Account defined, exit program.\n";
        die();
    }

    if (!isset($bugid)) {
        build_progress(110, "Issue while posting... bugID missing");
        exit;
    }

    if (!isset($content)) {
        build_progress(110, "Issue while posting... body missing");
        exit;
    }

    if (empty($content)) {
        build_progress(110, "Issue while posting... body is empty");
        exit;
    }

    if (empty($bugid)) {
        build_progress(110, "Issue while posting... bugID is empty");
        exit;
    }

    build_progress(10, "Initialize reply");
    $SupportTool = $MAIN["SupportTool"];
    $ArticaVer = @file_get_contents("/usr/share/artica-postfix/VERSION");
    $SPVer = @file_get_contents("/usr/share/artica-postfix/SP/$ArticaVer");
    $OSVer= $GLOBALS['CLASS_SOCKETS']->GET_INFO("LinuxDistributionFullName");
    $KernelVer= $GLOBALS['CLASS_SOCKETS']->GET_INFO("LinuxKernelVersion");
    $SquidEnable=intval($GLOBALS['CLASS_SOCKETS']->GET_INFO("SQUIDEnable"));
    $SquidVer=$GLOBALS['CLASS_SOCKETS']->GET_INFO("SquidVersion");
    $PostfixEnable=intval($GLOBALS['CLASS_SOCKETS']->GET_INFO("EnablePostfix"));
    $PosfixVer=$GLOBALS['CLASS_SOCKETS']->GET_INFO("POSTFIX_VERSION");
    $SquidText="";
    $PostfixText="";
    if($SquidEnable==1){
        $SquidText="<br>- <b>Proxy Version: </b> $SquidVer";
    }
    if($PostfixEnable===1){
        $SquidText="<br>- <b>Postfix Version: </b> $PosfixVer";
    }

    $FINAL_TIME             = intval($LicenseInfos["FINAL_TIME"]);
    $GOLDKEY                = $GLOBALS["CLASS_SOCKETS"]->CORP_GOLD();
    $uuid=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID");
    $license="<br><br><b>- UUID: </b>$uuid<br><b>- License: </b>Community";
    if($GOLDKEY){
        $license="<br><br><b>- UUID: </b>$uuid<br><b>- License: </b>Golde Key";
    }
    else{
        if ($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
            if ($FINAL_TIME>0) {
                $ExpiresSoon=intval(time_between_day_Web($FINAL_TIME));
                if ($ExpiresSoon<7) {
                    $date= date('d/m/Y', $FINAL_TIME);
                    $license="<br><br><b>- UUID: </b>$uuid<br><b>- License: </b>Enterprise<br><b>- Expire Date: </b>$date ($ExpiresSoon days)";
                }

                if ($ExpiresSoon<31) {
                    $date= date('d/m/Y', $FINAL_TIME);
                    $license="<br><br><b>- UUID: </b>$uuid<br><b>- License: </b>Enterprise<br><b>- Expire Date: </b>$date ($ExpiresSoon days)";
                }

                if ($FINAL_TIME<time()) {
                    $date= date('d/m/Y', $FINAL_TIME);
                    $license="<br><br><b>- UUID: </b>$uuid<br><b>- License: </b>Enterprise<br><b>- Expire Date: </b>$date (Expired)";
                }
                else{
                    $date= date('d/m/Y', $FINAL_TIME);
                    $license="<br><br><b>- UUID: </b>$uuid<br><b>- License: </b>Enterprise<br><b>- Expire Date: </b>$date";
                }


            }
        }
    }

    $ArticaHotFixVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTFIX");
    $content .="<p><div></div></div><div  style=\"font-size: 11px;color: gray;\" data-columns='2'><b>############ SYSTEM INFO ############</b><br>- <b>Artica Version: </b>$ArticaVer SP$SPVer Hotfix:$ArticaHotFixVersion<br>- <b>OS Version: </b>$OSVer Kernel $KernelVer$SquidText$PostfixText$license</div></p>";

    if ($SupportTool == 1) {
        $unix = new unix();
        $rm = $unix->find_program("rm");
        build_progress(50, "Creating Support Package, please wait");
        system("/usr/sbin/artica-phpfpm-service -support-tool");

        if (!is_file("/usr/share/artica-postfix/ressources/support/support.tar.gz")) {
            build_progress(110, "Error while creating the Support Tool");
            shell_exec("$rm -rf /usr/share/artica-postfix/ressources/support");
            return;
        }
        $supportTool=base64_encode(@file_get_contents("/usr/share/artica-postfix/ressources/support/support.tar.gz"));
    }

    $array["type"] = "customer";
    $array["text"] = $content;
    $array["customer"]["email"] = $supportEmail;
    $array["imported"] = true;
    if ($SupportTool == 1) {
        $array["attachments"][0]["fileName"]= 'support.tar.gz';
        $array["attachments"][0]["mimeType"]= 'application/x-tar';
        $array["attachments"][0]["data"]= "$supportTool";
    }


    build_progress(70, "Posting reply");
    $response = build_curl("conversations/$bugid/threads?", $array, "POST");
    $json = json_decode($response,true);

    if (isset($json['id'])) {
        @unlink("/etc/artica-postfix/settings/Daemons/supportReply");
        if ($SupportTool == 1) {
            shell_exec("$rm -rf /usr/share/artica-postfix/ressources/support");
        }
        refresh_ticket($bugid);
        build_progress(100, "{success}");
    }
    else {
        build_progress(110, "{failed} to post the ticket");

    }

}


function build_curl($url, $json = null, $PROTO=null):string{

    if(is_null($PROTO)){
        $PROTO="GET";
    }

    $ch = curl_init();

    $SupportTracker=new SupportTracker();

    $supportEmail = $SupportTracker->supportEmail;
    $supportSecCode =$SupportTracker->supportSecCode;

    $GLOBALS["ERROR_API"] = 0;
    $CURLOPT_HTTPHEADER[] = "Accept: application/hal+json";
    $CURLOPT_HTTPHEADER[] = "Content-Type: application/json; charset=UTF-8";
    $CURLOPT_HTTPHEADER[] = "Pragma: no-cache,must-revalidate";
    $CURLOPT_HTTPHEADER[] = "Cache-Control: no-cache,must revalidate";
    $CURLOPT_HTTPHEADER[] = "Expect:";
    $CURLOPT_HTTPHEADER[] = "X-FreeScout-API-Key: " . hex2bin(SUPPORT_API);


    if ($supportEmail == null) {
        echo "Not Account defined, exit program.\n";
        die();
    }

    $MAIN_URI = "https://support.articatech.com/api/{$url}customerEmail=$supportEmail&code=$supportSecCode";
    if ($GLOBALS["VERBOSE"]) {
        echo __LINE__."]: MAIN_URI=$MAIN_URI\n";
    }

    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_URL, "$MAIN_URI");
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSLVERSION, 'all');

    if ($PROTO == "GET") {
        curl_setopt($ch, CURLOPT_POST, 0);
    }
    if ($PROTO == "POST") {
        curl_setopt($ch, CURLOPT_POST, 1);
    }
    if ($PROTO == "PUT") {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    }
    if ($PROTO == "DELETE") {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);

    if (is_array($json)) {
        $payload = json_encode($json);
        if ($GLOBALS["VERBOSE"]) {
            echo $payload . "\n";
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, FALSE);


    $supportProxyName = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportProxyName"));
    $supportProxyPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportProxyPort"));
    $supportUseProxy = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportUseProxy"));

    if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED")) == 1) {
        if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable")) == 1) {
            if ($supportProxyName == null) {
                $supportProxyName = "127.0.0.1";
            }
            if ($supportProxyName == "127.0.0.1") {
                $supportProxyPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
            }
        }
    }

    if ($supportUseProxy == 1) {
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($ch, CURLOPT_PROXY, "$supportProxyName:$supportProxyPort");
    }

    if ($GLOBALS["VERBOSE"]) {
        echo "$PROTO -> $url\n";
    }
    $data = curl_exec($ch);
    if ($GLOBALS["VERBOSE"]) {
        echo "$data\n";
    }
    $errno = curl_errno($ch);
    $error_text = curl_error($ch);
    $CURLINFO_HTTP_CODE = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));

    if ($ch) {
        @curl_close($ch);
    }
    $GLOBALS["CURLINFO_HTTP_CODE"] = $CURLINFO_HTTP_CODE;

    if ($errno > 0) {
        echo "Error Number $errno ( $CURLINFO_HTTP_CODE ) - $error_text\n";
        squid_admin_mysql(1, "[TRACKER]: Error Number $errno ( $CURLINFO_HTTP_CODE ) - $error_text", "$errno ( $CURLINFO_HTTP_CODE ) - $error_text", __FILE__, __LINE__);
        return false;
    }


    if ($GLOBALS["VERBOSE"]) {
        echo "CURLINFO_HTTP_CODE=$CURLINFO_HTTP_CODE\n";
    }
    if ($CURLINFO_HTTP_CODE == 200) {
        return $data;
    }
    if ($CURLINFO_HTTP_CODE == 201) {
        return $data;
    }
    if ($CURLINFO_HTTP_CODE == 204) {
        return 'updated';
    }
    if ($CURLINFO_HTTP_CODE == 404) {

        return $data;
    }
    if ($CURLINFO_HTTP_CODE == 400) {
        return $data;
    }

    if ($CURLINFO_HTTP_CODE == 401) {
        $json = json_decode($data, true);

        if($json['message']=="CreateCustomer"){
            build_progress(70, "{creating_account} {wait}");
            createCustomerAccount();
        }
        if($json['message']=="CreateSecCode"){
            build_progress(70, "{sending_email} {wait}");
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportCustomerID",base64_encode(serialize($json['cid'])));
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportName",base64_encode(serialize($json['name'])));
            if(isset($json['secCode'])){
                resendSecurityCode(true,$json['secCode']);
            }
            else{
                resendSecurityCode();
            }

        }
        return $data;
    }
    return "";
}

function build_curl_licensig($url, $json = null, $PROTO="GET"){
    $ch = curl_init();
    $supportEmail =base64_decode(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportEmail")));
    $GLOBALS["ERROR_API"] = 0;
    $CURLOPT_HTTPHEADER[] = "Accept: application/hal+json";
    $CURLOPT_HTTPHEADER[] = "Content-Type: application/json; charset=UTF-8";
    $CURLOPT_HTTPHEADER[] = "Pragma: no-cache,must-revalidate";
    $CURLOPT_HTTPHEADER[] = "Cache-Control: no-cache,must revalidate";
    $CURLOPT_HTTPHEADER[] = "Expect:";
    $CURLOPT_HTTPHEADER[] = "X-FreeScout-API-Key: " . hex2bin(SUPPORT_API);


    if ($supportEmail == null) {
        echo "Not Account defined, exit program.\n";
        die();
    }

    $MAIN_URI = "https://licensing.artica.center/$url";
    if ($GLOBALS["VERBOSE"]) {
        echo "MAIN_URI=$MAIN_URI\n";
    }

    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_URL, "$MAIN_URI");
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSLVERSION, 'all');

    if ($PROTO == "GET") {
        curl_setopt($ch, CURLOPT_POST, 0);
    }
    if ($PROTO == "POST") {
        curl_setopt($ch, CURLOPT_POST, 1);
    }
    if ($PROTO == "PUT") {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    }
    if ($PROTO == "DELETE") {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);

    if (is_array($json)) {
        $payload = json_encode($json);
        if ($GLOBALS["VERBOSE"]) {
            echo $payload . "\n";
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, FALSE);


    $supportProxyName = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportProxyName"));
    $supportProxyPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportProxyPort"));
    $supportUseProxy = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportUseProxy"));

    if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED")) == 1) {
        if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable")) == 1) {
            if ($supportProxyName == null) {
                $supportProxyName = "127.0.0.1";
            }
            if ($supportProxyName == "127.0.0.1") {
                $supportProxyPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
            }
        }
    }

    if ($supportUseProxy == 1) {
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($ch, CURLOPT_PROXY, "$supportProxyName:$supportProxyPort");
    }

    if ($GLOBALS["VERBOSE"]) {
        echo "$PROTO -> $url\n";
    }
    $data = curl_exec($ch);
    if ($GLOBALS["VERBOSE"]) {
        echo "$data\n";
    }
    $errno = curl_errno($ch);
    $error_text = curl_error($ch);
    $CURLINFO_HTTP_CODE = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));

    if ($ch) {
        @curl_close($ch);
    }
    $GLOBALS["CURLINFO_HTTP_CODE"] = $CURLINFO_HTTP_CODE;

    if ($errno > 0) {
        echo "Error Number $errno ( $CURLINFO_HTTP_CODE ) - $error_text\n";
        squid_admin_mysql(1, "[TRACKER]: Error Number $errno ( $CURLINFO_HTTP_CODE ) - $error_text", "$errno ( $CURLINFO_HTTP_CODE ) - $error_text", __FILE__, __LINE__);
        return false;
    }


    if ($GLOBALS["VERBOSE"]) {
        echo "CURLINFO_HTTP_CODE=$CURLINFO_HTTP_CODE\n";
    }
    if ($CURLINFO_HTTP_CODE == 200) {
        return $data;
    }
    if ($CURLINFO_HTTP_CODE == 201) {
        return $data;
    }
    if ($CURLINFO_HTTP_CODE == 404) {
        return $data;
    }
    if ($CURLINFO_HTTP_CODE == 400) {
        return $data;
    }

    if ($CURLINFO_HTTP_CODE == 401) {
        return $data;
    }
}

function createCustomerAccount(){

    $SupportTracker=new SupportTracker();

    $supportEmail =$SupportTracker->supportEmail;
    $supportName=$SupportTracker->supportName;

    if ($supportEmail == null) {
        echo "Not Account defined, exit program.\n";
        die();
    }

    $name=explode(" ",$supportName);
    $fname=$name[0];
    $lname=isset($name[1])?$name[1]:"";
    $secCode=bin2hex(random_bytes(25));
    $array["firstName"] = $fname;
    $array["lastName"] = $lname;
    $array["emails"][0]['value'] = $supportEmail;
    $array["emails"][0]['type'] = "1";
    $array["notes"] =$secCode;

    $url = "customers?action=createaccount&";
    $data = build_curl($url, $array, "POST");
    if (!$data) {
        return false;
    }
    $json = json_decode($data, true);
    if (isset($json['id'])) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportCustomerID",base64_encode(serialize($json['id'])));
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportName",base64_encode($json['firstName'].' '.$json['lastName']));
        $array1['name']=$json['firstName'].' '.$json['lastName'];
        $array1["email"] = $supportEmail;
        $array1["code"] =$secCode;
        $url = "api/send/email";
        $response = build_curl_licensig($url, $array1, "POST");
        $json = json_decode($response, true);
        if( $json['message']=="OK"){
            build_progress(100, "{account_created_success}");
            die();
        }
        else {
            build_progress(110, "{creating_account} {failed}");
            die();
        }
    }
    else{
        build_progress(110, "{creating_account} {failed}");
        die();
    }



}
function resendSecurityCode($forceSec=false,$Code=null){

    $SupportTracker=new SupportTracker();
    $supportEmail = $SupportTracker->supportEmail;
    $supportName=$SupportTracker->supportName;
    $supportCustomerID=$SupportTracker->supportCustomerID;

    if ($supportEmail == null) {
        echo "Not Account defined, exit program.\n";
        die();
    }
    if($supportCustomerID==0){
        echo "Not Account defined ($supportCustomerID), exit program.\n";
        die();
    }

    $secCode=bin2hex(random_bytes(25));
    if($forceSec){
        $secCode=$Code;
    }
    $array['customerFields'][0]["notes"] =$secCode;

    $url = "customers/$supportCustomerID/customer_fields?action=getseccode&";
    $data = build_curl($url, $array, "PUT");
    if (!$data) {
        return false;
    }
    if($data=="updated"){
        $array1['name']=$supportName;
        $array1["email"] = $supportEmail;
        $array1["code"] =$secCode;
        $url = "api/send/email";
        $response = build_curl_licensig($url, $array1, "POST");
        $json = json_decode($response, true);
        if( $json['message']=="OK"){
            build_progress(100, "{account_created_success}");
            die();
        }
        else {
            build_progress(110, "{creating_account} {failed}");
            die();
        }
    }

    else{
        build_progress(110, "{creating_account} {failed}");
        die();
    }

}

function create_bug()
{

    $DEF = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportCreateBug"));

    $SupportTracker=new SupportTracker();
    $supportEmail = $SupportTracker->supportEmail;
    $supportName=$SupportTracker->supportName;
    $supportCustomerID=$SupportTracker->supportCustomerID;
    $supportMID=$SupportTracker->supportMID;




    $LicenseInfos           = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));

    if ($supportEmail == null) {
        echo "Not Account defined, exit program.\n";
        die();
    }
    if($supportCustomerID==0){
        echo "Not Account defined, exit program.\n";
        die();
    }

    if($supportMID==0){
        echo "Not Account defined, exit program.\n";
        die();
    }

    if (!isset($DEF["subject"])) {
        build_progress(110, "Issue while posting... subject missing");
        exit;
    }

    if (!isset($DEF["comment"])) {
        build_progress(110, "Issue while posting... body missing");
        exit;
    }

    if (empty($DEF["comment"])) {
        build_progress(110, "Issue while posting... body is empty");
        exit;
    }

    if (empty($DEF["subject"])) {
        build_progress(110, "Issue while posting... subject is empty");
        exit;
    }

    build_progress(10, "{creating_ticket} {wait}");
    $SupportTool = $DEF["SupportTool"];
    $ArticaVer = @file_get_contents("/usr/share/artica-postfix/VERSION");
    $SPVer = @file_get_contents("/usr/share/artica-postfix/SP/$ArticaVer");
    $OSVer= $GLOBALS['CLASS_SOCKETS']->GET_INFO("LinuxDistributionFullName");
    $KernelVer= $GLOBALS['CLASS_SOCKETS']->GET_INFO("LinuxKernelVersion");
    $SquidEnable=intval($GLOBALS['CLASS_SOCKETS']->GET_INFO("SQUIDEnable"));
    $SquidVer=$GLOBALS['CLASS_SOCKETS']->GET_INFO("SquidVersion");
    $PostfixEnable=intval($GLOBALS['CLASS_SOCKETS']->GET_INFO("EnablePostfix"));
    $PosfixVer=$GLOBALS['CLASS_SOCKETS']->GET_INFO("POSTFIX_VERSION");
    $SquidText="";
    $PostfixText="";
    if($SquidEnable==1){
        $SquidText="<br>- <b>Proxy Version: </b> $SquidVer";
    }
    if($PostfixEnable===1){
        $SquidText="<br>- <b>Postfix Version: </b> $PosfixVer";
    }
    $FINAL_TIME             = intval($LicenseInfos["FINAL_TIME"]);
    $TIME                   = intval($LicenseInfos["TIME"]);
    $GOLDKEY                = $GLOBALS["CLASS_SOCKETS"]->CORP_GOLD();
    $uuid=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID");
    $license="<br><br><b>- UUID: </b>$uuid<br><b>- License: </b>Community";
    if($GOLDKEY){
        $license="<br><br><b>- UUID: </b>$uuid<br><b>- License: </b>Golde Key";
    }
    else{
        if ($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
            if ($FINAL_TIME>0) {
                $ExpiresSoon=intval(time_between_day_Web($FINAL_TIME));
                $distanceOfTimeInWords="(".distanceOfTimeInWords(time(), $FINAL_TIME).")";
                if ($ExpiresSoon<7) {
                    $date= date('d/m/Y', $FINAL_TIME);
                    $license="<br><br><b>- UUID: </b>$uuid<br><b>- License: </b>Enterprise<br><b>- Expire Date: </b>$date ($ExpiresSoon days)";
                }

                if ($ExpiresSoon<31) {
                    $date= date('d/m/Y', $FINAL_TIME);
                    $license="<br><br><b>- UUID: </b>$uuid<br><b>- License: </b>Enterprise<br><b>- Expire Date: </b>$date ($ExpiresSoon days)";
                }

                if ($FINAL_TIME<time()) {
                    $date= date('d/m/Y', $FINAL_TIME);
                    $license="<br><br><b>- UUID: </b>$uuid<br><b>- License: </b>Enterprise<br><b>- Expire Date: </b>$date (Expired)";
                }
                else{
                    $date= date('d/m/Y', $FINAL_TIME);
                    $license="<br><br><b>- UUID: </b>$uuid<br><b>- License: </b>Enterprise<br><b>- Expire Date: </b>$date";
                }


            }
        }
    }
    $BODY = $DEF["comment"];
    $BODY .="<p><div></div></div><div style='font-size: 11px;color: gray;' data-columns='2'><b>############ SYSTEM INFO ############</b><br>- <b>Artica Version: </b>$ArticaVer SP$SPVer<br>- <b>OS Version: </b>$OSVer Kernel $KernelVer$SquidText$PostfixText$license</div></p>";

    if ($SupportTool == 1) {
        $i = intval(@file_get_contents("/etc/artica-postfix/support-tool-prc"));
        $unix = new unix();
        $php = $unix->LOCATE_PHP5_BIN();
        $rm = $unix->find_program("rm");
        build_progress(50, "Creating Support Package, please wait");
        system("/usr/sbin/artica-phpfpm-service -support-tool");

        if (!is_file("/usr/share/artica-postfix/ressources/support/support.tar.gz")) {
            build_progress(110, "Error while creating the Support Tool");
            shell_exec("$rm -rf /usr/share/artica-postfix/ressources/support");
            return;
        }
        $supportTool=base64_encode(@file_get_contents("/usr/share/artica-postfix/ressources/support/support.tar.gz"));
    }
    $name=explode(" ",$supportName);
    $array["type"] = "email";
    $array["mailboxId"] = $supportMID;
    $array["subject"] = $DEF["subject"];
    $array["customer"]["email"] = $supportEmail;
    $array["threads"][0]["text"] = $BODY;
    $array["threads"][0]["type"]  = "customer";
    $array["threads"][0]["customer"]["email"]  = $supportEmail;
    $array["threads"][0]["customer"]["first_name"]  = $name[0];
    $array["threads"][0]["customer"]["last_name"]  = $name[1];
    if ($SupportTool == 1) {
        $array["threads"][0]["attachments"][0]["fileName"]= 'support.tar.gz';
        $array["threads"][0]["attachments"][0]["mimeType"]= 'application/x-tar';
        $array["threads"][0]["attachments"][0]["data"]= "$supportTool";
    }
    $array["status"] = "active";
    $array["customFields"][0]["id"]=$supportMID;
    $array["customFields"][0]["value"]=$DEF["priority"];

    echo "Initialize {$DEF["component"]} {$DEF["subject"]}\n";

    build_progress(70, "Posting ticket");
    $response = build_curl("conversations?", $array, "POST");
    $json = json_decode($response,true);

    if (isset($json['id'])) {
        @unlink("/etc/artica-postfix/settings/Daemons/supportCreateBug");
        if ($SupportTool == 1) {
            shell_exec("$rm -rf /usr/share/artica-postfix/ressources/support");
        }
        refresh_ticket($json['id']);
        build_progress(100, "{success}");
    }
    else {
        build_progress(110, "{failed} to post the ticket");
        return;
    }


}

function get_tickets()
{

    if ($GLOBALS["SCHEDULE"]) {
        $unix = new unix();
        $pidfile = "/etc/artica-postfix/pids/exec.support.php.get_tickets.pid";
        $pidTime = "/etc/artica-postfix/pids/exec.support.php.get_tickets.time";
        $pid = @file_get_contents($pidfile);
        if ($unix->process_exists($pid)) {
            exit();
        }
        $pidTimeEx = $unix->file_time_min($pidTime);
        if ($pidTimeEx < 60) {
            exit();
        }
    }

    $SupportTracker=new SupportTracker();
    $supportEmail = $SupportTracker->supportEmail;
    $supportID = $SupportTracker->supportMID;
    build_progress(60, "Fill ticket list for $supportEmail..");
    $response = build_curl("conversations?embed=threads,tags&mailboxId=$supportID&", null, "GET");
    $json = json_decode($response, true);

    @mkdir("/home/artica/SQLITE", 0755, true);
    if (is_file("/home/artica/SQLITE/support.db")) {
        @unlink("/home/artica/SQLITE/support.db");
    }
    $q = new lib_sqlite("/home/artica/SQLITE/support.db");
    @chmod("/home/artica/SQLITE/support.db", 0644);
    @chown("/home/artica/SQLITE/support.db", "www-data");
    @chown("/home/artica/SQLITE", "www-data");
    @chmod("/home/artica/SQLITE", 0755);
    $sql = "CREATE TABLE `tickets` (
				`id` INTEGER PRIMARY KEY,
				`number` int,
				`threads` int,
				`folderId` text,
				`status` text,
				`state` text,
				`subject` text,
				`customFields` text,
				`tags` text,
				`createdAt` text,
				`updatedAt` text,
				`mailboxId` int,
				`createdBy` text,
				`assignee` text
				
				)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo $q->mysql_error . "\n";
        return;
    }

    $sql = "CREATE TABLE `threads` (
				`id` INTEGER PRIMARY KEY,
				`number` int,
				`convertation_id` INTEGER,
				`body` text,
				`createdAt` text,
				`attachments` text,
				`createdBy` text,
				`type` text
				)";
    $q->QUERY_SQL($sql);

    foreach ($json['_embedded']['conversations'] as $indexTicket => $ticket) {

        if($supportEmail != $ticket['customer']['email']){
            build_progress(110, "Invalid identification");
            return false;
        }
        $id = intval($ticket['id']);
        $tnumber = intval($ticket['number']);
        $threads = intval($ticket['threadsCount']);
        $folderId = intval($ticket['folderId']);
        $status = $ticket['status'];
        $state = $ticket['state'];
        $subject = htmlspecialchars($ticket['subject'], ENT_QUOTES);
        echo $subject."\n";
        $customFields = "";
        if ($ticket['customFields'][0]['name'] == 'Priority') {
            $customFields = $ticket['customFields'][0]['text'];
        }
        $tags = array();
        foreach ($ticket['_embedded']['tags'] as $indexTag => $tag) {
            //$tags .= $tag['name'];
            array_push($tags, $tag['name']);

        }
        $tags = json_encode($tags);
        $createdAt = strtotime($ticket['createdAt']);
        $updatedAt = strtotime($ticket['updatedAt']);
        $mailboxId = intval($ticket['mailboxId']);
        $createdBy = $ticket['createdBy']['firstName'] . ' ' . $ticket['createdBy']['lastName'];
        $assignee = $ticket['assignee']['firstName'] . ' ' . $ticket['assignee']['lastName'];
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportCustomerID",base64_encode(serialize($ticket['customer']['id'])));
        $f[] = "('$id','$tnumber','$threads','$folderId','$status','$state','$subject','$customFields','$tags','$createdAt','$updatedAt','$mailboxId','$createdBy','$assignee')";
        build_progress(65, "{getting_ticket} #$tnumber");
        //Get Threads
        foreach ($ticket['_embedded']['threads'] as $indexTag => $threads) {
            if ($threads['type'] == 'message' || $threads['type'] == 'customer') {
                $thread_id = intval($threads['id']);
                $convertation_id = intval($ticket['id']);
                $body = str_replace("'", "''", $threads['body']);
                $body = str_replace('</div>', '<br/>', $body);
                $body = strip_tags($body, '<br>');

                $createdAt = strtotime($threads['createdAt']);
                $attachments = array();
                foreach ($threads['_embedded']['attachments'] as $indexAtt => $attachment) {
                    array_push($attachments, $attachment['fileUrl']);
                    //$attachments .= $attachment['fileUrl'] . '' . $attachment['fileName'];
                }
                $attachments = json_encode($attachments);
                $createdBy = $threads['createdBy']['firstName'] . ' ' . $threads['createdBy']['lastName'];
                $type = $threads['type'];
                $g[] = "('$thread_id','$tnumber','$convertation_id','$body','$createdAt','$attachments','$createdBy','$type')";
                build_progress(75, "{getting_threads_of} #$tnumber");
            }

        }

    }

    build_progress(70, count($f) . " {tickets}");

    if (count($f) == 0) {
        if (is_file("/home/artica/SQLITE/support.db")) {
            $sql = "DELETE FROM tickets";
            $q->QUERY_SQL($sql);
            $sql = "DELETE FROM threads";
            $q->QUERY_SQL($sql);


        }
    }

    if (count($f) > 0) {
        $sql = "INSERT OR IGNORE INTO tickets (`id`,`number`,`threads`,`folderId`,`status`,`state`,`subject`,`customFields`,`tags`,`createdAt`,`updatedAt`,`mailboxId`,`createdBy`,`assignee`) VALUES " . @implode(",", $f);
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            build_progress(110, "MySQL Error");
            echo $q->mysql_error . "\n";
            exit();
        }
        $sql = "INSERT OR IGNORE INTO threads (`id`,`number`,`convertation_id`,`body`,`createdAt`,`attachments`,`createdBy`,`type`) VALUES " . @implode(",", $g);
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            build_progress(110, "MySQL Error");
            echo $q->mysql_error . "\n";
            exit();
        }
    }
    build_progress(100, "{success}");

}

function sqlite_myescape($data) {
    if(is_array($data))
        return array_map("sqlite_escape_string", $data);

    return sqlite_escape_string($data);
}

function delete_bug($id):bool{

    $SupportTracker=new SupportTracker();

    $supportEmail =$SupportTracker->supportEmail;
    $supportCustomerID=$SupportTracker->supportCustomerID;
    $q=new lib_sqlite("/home/artica/SQLITE/support.db");
    $ligne=$q->mysqli_fetch_array("SELECT id,number,subject FROM `tickets` WHERE id='$id'");
    $number=$ligne["number"];
    $mysqid=$ligne['id'];
    build_progress(40, "{deleting} ticket #$number");
    $response = build_curl("conversations/$mysqid?embed=threads&", null, "GET");
    $ticket = json_decode($response,true);

    build_progress(65, "{deleting} ticket #$number");

    if ($GLOBALS["CURLINFO_HTTP_CODE"] !== 200) {
        return false;
    }

    if(isset($ticket['id'])){


        if($supportEmail !== $ticket['customer']['email']){
            build_progress(110, "Invalid identification");
            return false;
        }
        if($supportCustomerID !== $ticket['customer']['id']){
            build_progress(110, "Invalid identification1");
            return false;
        }

        $response = build_curl("conversations/$id?", null, "DELETE");

        if ($GLOBALS["CURLINFO_HTTP_CODE"] <> 204) {
            build_progress(40, "{failed} to delete the ticket");
            return false;
        }

        $q = new lib_sqlite("/home/artica/SQLITE/support.db");
        $q->QUERY_SQL("DELETE FROM tickets WHERE id=$mysqid");
        $q->QUERY_SQL("DELETE FROM threads WHERE convertation_id=$mysqid");

    }

    build_progress(100, "{success}");
    return true;

}

function refresh_ticket($bugid){
    $SupportTracker=new SupportTracker();
    $supportEmail = $SupportTracker->supportEmail;
    $supportMID = $SupportTracker->supportMID;
    $supportCustomerID=$SupportTracker->supportCustomerID;

    $q = new lib_sqlite("/home/artica/SQLITE/support.db");
    $q->QUERY_SQL("DELETE FROM tickets WHERE id='$bugid'");
    $q->QUERY_SQL("DELETE FROM threads WHERE convertation_id='$bugid'");

    $response = build_curl("conversations/$bugid?embed=threads,tags&", null, "GET");
    $ticket = json_decode($response,true);

    build_progress(65, "{getting_ticket_list}");

    if ($GLOBALS["CURLINFO_HTTP_CODE"] !== 200) {
        return;
    }

    if(isset($ticket['id'])){

        if($supportEmail !== $ticket['customer']['email']){
            build_progress(110, "Invalid identification");
            return false;
        }
        $id = intval($ticket['id']);
        $tnumber = intval($ticket['number']);
        $threads = intval($ticket['threadsCount']);
        $folderId = intval($ticket['folderId']);
        $status = $ticket['status'];
        $state = $ticket['state'];
        $subject = $ticket['subject'];
        $customFields = "";
        if ($ticket['customFields'][0]['name'] == 'Priority') {
            $customFields = $ticket['customFields'][0]['text'];
        }
        $tags = array();
        foreach ($ticket['_embedded']['tags'] as $indexTag => $tag) {
            //$tags .= $tag['name'];
            array_push($tags, $tag['name']);

        }
        $tags = json_encode($tags);
        $createdAt = strtotime($ticket['createdAt']);
        $updatedAt = strtotime($ticket['updatedAt']);
        $mailboxId = intval($ticket['mailboxId']);
        $createdBy = $ticket['createdBy']['firstName'] . ' ' . $ticket['createdBy']['lastName'];
        $assignee = $ticket['assignee']['firstName'] . ' ' . $ticket['assignee']['lastName'];

        $f[] = "('$id','$tnumber','$threads','$folderId','$status','$state','$subject','$customFields','$tags','$createdAt','$updatedAt','$mailboxId','$createdBy','$assignee')";
        build_progress(65, "{getting_ticket} #$tnumber");
        foreach ($ticket['_embedded']['threads'] as $indexTag => $threads) {
            if ($threads['type'] == 'message' || $threads['type'] == 'customer') {

                $thread_id = intval($threads['id']);
                $convertation_id = intval($ticket['id']);
                $body = str_replace("'", "''", $threads['body']);
                $body = str_replace('</div>', '<br/>', $body);
                $body = strip_tags($body, '<br>');

                $createdAt = strtotime($threads['createdAt']);
                $attachments = array();
                foreach ($threads['_embedded']['attachments'] as $indexAtt => $attachment) {
                    array_push($attachments, $attachment['fileUrl']);
                    //$attachments .= $attachment['fileUrl'] . '' . $attachment['fileName'];
                }
                $attachments = json_encode($attachments);
                $createdBy = $threads['createdBy']['firstName'] . ' ' . $threads['createdBy']['lastName'];
                $type = $threads['type'];
                $g[] = "('$thread_id','$tnumber','$convertation_id','$body','$createdAt','$attachments','$createdBy','$type')";
                build_progress(75, "{getting_threads_of} #$tnumber");
            }

        }

    }
    if (count($f) > 0) {
        $sql = "INSERT OR IGNORE INTO tickets (`id`,`number`,`threads`,`folderId`,`status`,`state`,`subject`,`customFields`,`tags`,`createdAt`,`updatedAt`,`mailboxId`,`createdBy`,`assignee`) VALUES " . @implode(",", $f);
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            build_progress(110, "MySQL Error");
            echo $q->mysql_error . "\n";
            exit();
        }
        $sql = "INSERT OR IGNORE INTO threads (`id`,`number`,`convertation_id`,`body`,`createdAt`,`attachments`,`createdBy`,`type`) VALUES " . @implode(",", $g);
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            build_progress(110, "MySQL Error");
            echo $q->mysql_error . "\n";
            exit();
        }
    }
    build_progress(100, "{success}");
}

function get_comment($bugid)
{

    $q = new lib_sqlite("/home/artica/SQLITE/support.db");
    $q->QUERY_SQL("DELETE FROM threads WHERE convertation_id='$bugid'");
    $response = build_curl("conversations/$bugid?", null, "GET");
    $json = json_decode($response,true);

    build_progress(65, "{getting_info}");

    if ($GLOBALS["CURLINFO_HTTP_CODE"] <> 200) {
        return;
    }
    foreach ($json['_embedded']['conversations'] as $indexTicket => $ticket) {
        foreach ($ticket['_embedded']['threads'] as $indexTag => $threads) {
            if ($threads['type'] == 'message' || $threads['type'] == 'customer') {

                $thread_id = intval($threads['id']);
                $tnumber = intval($ticket['number']);
                $convertation_id = intval($ticket['id']);
                $body = str_replace("'", "''", $threads['body']);
                $body = str_replace('</div>', '<br/>', $body);
                $body = strip_tags($body, '<br>');

                $createdAt = strtotime($threads['createdAt']);
                $attachments = array();
                foreach ($threads['_embedded']['attachments'] as $indexAtt => $attachment) {
                    array_push($attachments, $attachment['fileUrl']);
                    //$attachments .= $attachment['fileUrl'] . '' . $attachment['fileName'];
                }
                $attachments = json_encode($attachments);
                $createdBy = $threads['createdBy']['firstName'] . ' ' . $threads['createdBy']['lastName'];
                $type = $threads['type'];
                $g[] = "('$thread_id','$tnumber','$convertation_id','$body','$createdAt','$attachments','$createdBy','$type')";
                build_progress(75, "{getting_threads_of} #$tnumber");
                $sql = "INSERT OR IGNORE INTO threads (`id`,`number`,`convertation_id`,`body`,`createdAt`,`attachments`,`createdBy`,`type`) VALUES " . @implode(",", $g);
                $q->QUERY_SQL($sql);
                if (!$q->ok) {
                    build_progress(110, "MySQL Error");
                    echo $q->mysql_error . "\n";
                    exit();
                }
            }
        }
    }


}


function support_tool($bugid)
{


    $unix = new unix();
    $rm = $unix->find_program("rm");
    progress_support_tool("Ticket ID $bugid", 5);
    $supportPath="/usr/share/artica-postfix/ressources/support/support.tar.gz";
    system("/usr/sbin/artica-phpfpm-service -support-tool");

    if (!is_file($supportPath)) {
        progress("{failed}", 110);
        shell_exec("$rm -rf /usr/share/artica-postfix/ressources/support");
        return;
    }

    $array["data"] = base64_encode(@file_get_contents($supportPath));
    $array["file_name"] = $unix->hostname_g() . "." . time() . ".tar.gz";
    $array["subject"] = "Generated support Tool";
    $array["comment"] = "auto-generated support package for {$unix->hostname_g()}";
    $array["content_type"] = "application/x-tar";
    $array["is_patch"] = False;

    $size = FormatBytes(strlen($array["data"]) / 1024);
    progress_support_tool("{uploading} $size...", 90);
    $response = build_curl("bug/$bugid/attachment", $array, "POST");
    $json = json_decode($response);
    progress_support_tool("{uploading} {done}...", 91);

    if ($GLOBALS["CURLINFO_HTTP_CODE"] <> 200) {
        if (property_exists($json, 'code')) {
            $GLOBALS["ERROR_API"] = $json->code;
        }
        if (property_exists($json, 'message')) {
            echo $json->message . "\n";
        }
        progress_support_tool(110, "{failed} Error {$GLOBALS["ERROR_API"]}");
        shell_exec("$rm -rf /usr/share/artica-postfix/ressources/support");
        return;
    }
    progress_support_tool("{synchronize}...", 92);
    get_comment($bugid);
    progress_support_tool("{success}...", 100);

}

function progress_support_tool($title, $perc)
{
    echo "$title,$perc\n";
    echo "Starting......: " . date("H:i:s") . " {$perc}% $title\n";
    $cachefile = PROGRESS_DIR . "/squid.debug.support-tool.progress";
    $array["POURC"] = $perc;
    $array["TEXT"] = $title;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile, 0755);
}
function time_between_day_Web($xtime){
    $now = time(); // or your date as well
    $your_date = $xtime;
    $datediff = $your_date - $now ;
    return floor($datediff/(60*60*24));
}
function distanceOfTimeInWords($fromTime, $toTime = 0, $showLessThanAMinute = true) {
    if(!is_numeric($fromTime)){return "Unknown";}
    if(!is_numeric($toTime)){return "Unknown";}

    $distanceInSeconds = round(abs($toTime - $fromTime));
    $distanceInMinutes = round($distanceInSeconds / 60);

    if ( $distanceInMinutes <= 1 ) {
        if ( !$showLessThanAMinute ) {
            return ($distanceInMinutes == 0) ? 'less than a minute' : '1 {minute}';
        } else {
            if ( $distanceInSeconds < 5 ) {
                return '{lessthan} 5 {seconds} ('.$distanceInSeconds.'s)';
            }
            if ( $distanceInSeconds < 10 ) {
                return '{lessthan} 10 {seconds} ('.$distanceInSeconds.'s)';
            }
            if ( $distanceInSeconds < 20 ) {
                return '{lessthan} 20 {seconds} ('.$distanceInSeconds.'s) ';
            }
            if ( $distanceInSeconds < 40 ) {
                return '{abouttime} {halfaminute} ('.$distanceInSeconds.'s)';
            }
            if ( $distanceInSeconds < 60 ) {
                return '{lessthanaminute}';
            }

            return '1 minute';
        }
    }
    if ( $distanceInMinutes < 45 ) {
        return $distanceInMinutes . ' {minutes}';
    }
    if ( $distanceInMinutes < 90 ) {
        return '{abouttime} 1 {hour}';
    }
    if ( $distanceInMinutes < 1440 ) {
        return '{abouttime} ' . round(floatval($distanceInMinutes) / 60.0) . ' {hours}';
    }
    if ( $distanceInMinutes < 2880 ) {
        return '1 {day}';
    }
    if ( $distanceInMinutes < 43200 ) {
        return '{abouttime} ' . round(floatval($distanceInMinutes) / 1440) . ' {days}';
    }
    if ( $distanceInMinutes < 86400 ) {
        return '{abouttime} 1 {month}';
    }
    if ( $distanceInMinutes < 525600 ) {
        return round(floatval($distanceInMinutes) / 43200) . ' {months}';
    }
    if ( $distanceInMinutes < 1051199 ) {
        return '{abouttime} 1 {year}';
    }

    return 'over ' . round(floatval($distanceInMinutes) / 525600) . ' {years}';

}
?>