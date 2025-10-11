<?php
ini_set('error_reporting', E_ALL);
	
	/*$GLOBALS["VERBOSE"]=true;
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',$_SERVER["SERVER_ADDR"].":");
	ini_set('error_append_string',"");	
    */

	if(  isset($_REQUEST["VERBOSE"])  OR (isset($_REQUEST["verbose"]))   ){
		echo "STATISTICS APPLIANCE -> VERBOSE MODE\n";
		$GLOBALS["VERBOSE"]=true;
		SetDebug();
	}
	
	if(isset($_GET["test-connection"])){echo "\n\nCONNECTIONOK\n\n";die("DIE " .__FILE__." Line: ".__LINE__);}
	
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.blackboxes.inc');
	include_once('ressources/class.mysql.squid.builder.php');		
	include_once('ressources/class.mysql.dump.inc');
	include_once('ressources/class.mysql.syslogs.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
    include_once('ressources/class.artica-logon.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

    foreach ($_REQUEST as $num=>$val){
		if(strlen($val)>500){$val=strlen($val)." bytes";}
		writelogs("From: {$_SERVER["REMOTE_ADDR"]} $num = $val",__FUNCTION__,__FILE__,__LINE__);
	}
    if(isset($_POST["HaClusterRegister"])){hacluster_receive_register();exit;}
	if( isset($_POST["SQUID_BEREKLEY"])){stats_berekley_upload();exit;}
    if( isset($_POST['legalslogs-upload']) ){LEGALSLOGS_UPLOADED();exit();}

	// ---------------------------------------------------------------------

    if(isset($_POST["hacluster-connect"])){
        $ConnectedIP=$_SERVER["REMOTE_ADDR"];
        hacluster_syslog("[ERROR]: $ConnectedIP Depreciated protocol, please Upgrade to latest version..");
        exit;
    }


if (isset($_GET["ldapcluster"])){
    LDAP_CLUSTER_INFOS();exit;
}
if (isset($_GET["legallogs-register"])) {
    LEGAL_LOGS_REGISTER();
    exit;
}
if (isset($_GET["legallogs-unregister"])) {
    LEGAL_LOGS_UNREGISTER();
    exit;
}
if (isset($_GET["elkMaster"])) {
    SetDebug();
    elkMaster();
    exit;
}
if (isset($_GET["ucarp"])) {
    SetDebug();
    ucarp_step1();
    exit;
}
if (isset($_GET["ucarp2"])) {
    SetDebug();
    ucarp_step2();
    exit;
}
if (isset($_GET["ucarp3"])) {
    SetDebug();
    ucarp_step3();
    exit;
}
if (isset($_GET["ucarp2-remove"])) {
    SetDebug();
    UCARP_REMOVE();
    exit;
}
if (isset($_POST["UCARP_DOWN"])) {
    UCARP_DOWN();
    exit;
}
if (isset($_GET["stats-appliance-compatibility"])) {
    stats_appliance_comptability();
    exit;
}
if (isset($_GET["stats-appliance-ports"])) {
    stats_appliance_ports();
    exit;
}
if (isset($_GET["stats-perform-connection"])) {
    stats_appliance_privs();
    exit;
}
if (isset($_GET["ufdbguardport"])) {
    stats_appliance_remote_port();
    exit;
}
if (isset($_REQUEST["SQUID_STATS_CONTAINER"])) {
    stats_appliance_upload();
    exit;
}
if (isset($_POST["OPENSYSLOG"])) {
    OPENSYSLOG();
    exit;
}
if (isset($_GET["squid-table"])) {
    export_squid_table();
    exit;
}
if (isset($_FILES["SETTINGS_INC"])) {
    SETTINGS_INC();
    exit;
}
if (isset($_POST["DNS_LINKER"])) {
    DNS_LINKER();
    exit;
}
if (isset($_POST["SQUIDCONF"])) {
    SQUIDCONF();
    exit;
}
if (isset($_POST["REGISTER"])) {
    REGISTER();
    exit;
}
if (isset($_POST["LATEST_ARTICA_VERSION"])) {
    LATEST_ARTICA_VERSION();
    exit;
}
if (isset($_POST["LATEST_SQUID_VERSION"])) {
    LATEST_SQUID_VERSION();
    exit;
}
if (isset($_POST["orderid"])) {
    ORDER_DELETE();
    exit;
}
if (isset($_POST["PING-ORDERS"])) {
    PARSE_ORDERS();
    exit;
}
if (isset($_REQUEST["SETTINGS_INC"])) {
    SETTINGS_INC_2();
    exit;
}
//keepalived
if (isset($_POST["sync-keepalived-nodes"])) {
    keepalived_syslog_secondary_node("Request from " . $_SERVER["REMOTE_ADDR"]);
    sync_keepalived_nodes();
    exit;
}
if (isset($_POST["delete-keepalived-vips"])) {
    keepalived_syslog_secondary_node("Request from " . $_SERVER["REMOTE_ADDR"]);
    delete_keepalived_vips();
    exit;
}
if (isset($_POST["delete-keepalived-services"])) {
    keepalived_syslog_secondary_node("Request from " . $_SERVER["REMOTE_ADDR"]);
    delete_keepalived_services();
    exit;
}
if (isset($_POST["delete-keepalived-tracks"])) {
    keepalived_syslog_secondary_node("Request from " . $_SERVER["REMOTE_ADDR"]);
    delete_keepalived_tracks();
    exit;
}
if (isset($_POST["delete-keepalived-nodes"])) {
    keepalived_syslog_secondary_node("Request from " . $_SERVER["REMOTE_ADDR"]);
    delete_keepalived_nodes();
    exit;
}
if (isset($_POST["ping-keepalived-nodes"])) {
    keepalived_syslog_secondary_node("Request from " . $_SERVER["REMOTE_ADDR"]);
    ping_keepalived_nodes();
    exit;
}
if (isset($_POST["keepalivedStatus"])) {
    keepalived_syslog_primary_node("Request status from " . $_SERVER["REMOTE_ADDR"]);
    keepalived_node_status();
    exit;
}
if (isset($_POST["keepalivedServiceStatus"])) {
    keepalived_syslog_primary_node("Request status from " . $_SERVER["REMOTE_ADDR"]);
    keepalived_node_service_status();
    exit;
}
if (isset($_POST["sync-debug-nodes"])) {
    keepalived_syslog_primary_node("Request status from " . $_SERVER["REMOTE_ADDR"]);
    keepalived_node_service_debug();
    exit;
}

$error=array();
if(isset($_FILES['DNS_LINKER'])) {
    foreach ($_FILES['DNS_LINKER'] as $num => $val) {
        $error[] = "\$_FILES['DNS_LINKER'][$num]:$val";
    }
}
foreach ($_REQUEST as $num=>$val){
    hacluster_syslog("ERROR Unexpected query $num FROM" .$_SERVER["REMOTE_ADDR"]);
    $error[] = "\$_REQUEST[$num]:$val";
}


writelogs("Unable to understand " . @implode(",", $error), __FILE__, __FUNCTION__, __LINE__);
http_response_code(404);
//KEEPALIVED
function keepalived_syslog_primary_node($text){
    echo $text . "\n";
    if (!function_exists("syslog")) {
        return;
    }
    $LOG_SEV = LOG_INFO;
    openlog("keepalived-master", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
}
function keepalived_syslog_secondary_node($text){
    echo $text . "\n";
    if (!function_exists("syslog")) {
        return;
    }
    $LOG_SEV = LOG_INFO;
    openlog("keepalived-slave", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
}
function sync_keepalived_nodes(){
    $postdata = $_POST["post_data"];
    keepalived_syslog_secondary_node("Accepting connection from {$_SERVER["REMOTE_ADDR"]}");

    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        echo "<ERROR>{license_error}</ERROR>";
        die();
    }
    if (!is_file("/usr/sbin/keepalived")) {
        echo "<ERROR>{keepalived_not_installed} {$_POST["secondary_node_ip"]}</ERROR>";
        die();
    }

    $APP_KEEPALIVED_ENABLE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE"));
    if (intval($APP_KEEPALIVED_ENABLE) == 1) {
        echo "<ERROR>{keepalived_primary_node_installed} {$_POST["secondary_node_ip"]}</ERROR>";
        die();
    }

    $APP_KEEPALIVED_ENABLE_SLAVE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));
    if ($APP_KEEPALIVED_ENABLE_SLAVE == 0) {
        keepalived_syslog_secondary_node("Installation failover secondary_node.");
        keepalived_syslog_secondary_node("ORDER: install Failover feature");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("keepalived.php?enable-secondary_node=yes");
    }
    sleep(3);
    echo "<STATUS>2</STATUS>";
    keepalived_syslog_secondary_node("Starting installation procedure.");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("keepalived.php?setup-nodes=yes&data=$postdata");

}
function keepalived_node_service_debug()
{

    keepalived_syslog_secondary_node("Accepting connection from {$_SERVER["REMOTE_ADDR"]}");

    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        echo "<ERROR>{license_error}</ERROR>";
        die();
    }
    if (!is_file("/usr/sbin/keepalived")) {
        echo "<ERROR>{keepalived_not_installed} {$_POST["secondary_node_ip"]}</ERROR>";
        die();
    }

    $APP_KEEPALIVED_ENABLE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE"));
    if (intval($APP_KEEPALIVED_ENABLE) == 1) {
        echo "<ERROR>{keepalived_primary_node_installed} {$_POST["secondary_node_ip"]}</ERROR>";
        die();
    }

    $APP_KEEPALIVED_ENABLE_SLAVE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));
    if ($APP_KEEPALIVED_ENABLE_SLAVE == 0) {
        keepalived_syslog_secondary_node("ORDER: install Failover feature");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("keepalived.php?enable-secondary_node=yes");
    }

    $debug = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("keepalived_log_detail"));
    if ($debug == intval($_POST['debug'])) {
        echo "<STATUS>2</STATUS>";
    } else {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("keepalived_log_detail", intval($_POST['debug']));
        echo "<STATUS>2</STATUS>";
        keepalived_syslog_secondary_node("Starting installation procedure.");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("keepalived.php?restart=yes");
    }


}
function delete_keepalived_vips(){
    $postdata = $_POST["post_data"];
    keepalived_syslog_secondary_node("Accepting connection from {$_SERVER["REMOTE_ADDR"]}");

    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        echo "<ERROR>{license_error}</ERROR>";
        die();
    }
    if (!is_file("/usr/sbin/keepalived")) {
        echo "<ERROR>{keepalived_not_installed} {$_POST["secondary_node_ip"]}</ERROR>";
        die();
    }

    $APP_KEEPALIVED_ENABLE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE"));
    if (intval($APP_KEEPALIVED_ENABLE) == 1) {
        echo "<ERROR>{keepalived_primary_node_installed} {$_POST["secondary_node_ip"]}</ERROR>";
        die();
    }

    $APP_KEEPALIVED_ENABLE_SLAVE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));
    if ($APP_KEEPALIVED_ENABLE_SLAVE == 0) {
        keepalived_syslog_secondary_node("ORDER: install Failover feature");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("keepalived.php?enable-secondary_node=yes");
    }

    echo "<STATUS>2</STATUS>";
    keepalived_syslog_secondary_node("Starting installation procedure.");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("keepalived.php?action-delete-nodes-vips=yes&data=$postdata");
}
function delete_keepalived_services()
{
    $postdata = $_POST["post_data"];
    keepalived_syslog_secondary_node("Accepting connection from {$_SERVER["REMOTE_ADDR"]}");

    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        echo "<ERROR>{license_error}</ERROR>";
        die();
    }
    if (!is_file("/usr/sbin/keepalived")) {
        echo "<ERROR>{keepalived_not_installed} {$_POST["secondary_node_ip"]}</ERROR>";
        die();
    }

    $APP_KEEPALIVED_ENABLE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE"));
    if (intval($APP_KEEPALIVED_ENABLE) == 1) {
        echo "<ERROR>{keepalived_primary_node_installed} {$_POST["secondary_node_ip"]}</ERROR>";
        die();
    }

    $APP_KEEPALIVED_ENABLE_SLAVE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));
    if ($APP_KEEPALIVED_ENABLE_SLAVE == 0) {
        keepalived_syslog_secondary_node("ORDER: install Failover feature");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("keepalived.php?enable-secondary_node=yes");
    }

    echo "<STATUS>2</STATUS>";
    keepalived_syslog_secondary_node("Starting installation procedure.");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("keepalived.php?action-delete-nodes-services=yes&data=$postdata");
}
function delete_keepalived_tracks()
{
    $postdata = $_POST["post_data"];
    keepalived_syslog_secondary_node("Accepting connection from {$_SERVER["REMOTE_ADDR"]}");

    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        echo "<ERROR>{license_error}</ERROR>";
        die();
    }
    if (!is_file("/usr/sbin/keepalived")) {
        echo "<ERROR>{keepalived_not_installed} {$_POST["secondary_node_ip"]}</ERROR>";
        die();
    }

    $APP_KEEPALIVED_ENABLE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE"));
    if (intval($APP_KEEPALIVED_ENABLE) == 1) {
        echo "<ERROR>{keepalived_primary_node_installed} {$_POST["secondary_node_ip"]}</ERROR>";
        die();
    }

    $APP_KEEPALIVED_ENABLE_SLAVE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));
    if ($APP_KEEPALIVED_ENABLE_SLAVE == 0) {
        keepalived_syslog_secondary_node("ORDER: install Failover feature");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("keepalived.php?enable-secondary_node=yes");
    }

    echo "<STATUS>2</STATUS>";
    keepalived_syslog_secondary_node("Starting installation procedure.");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("keepalived.php?action-delete-nodes-tracks=yes&data=$postdata");
}
function delete_keepalived_nodes()
{
    $postdata = $_POST["post_data"];
    keepalived_syslog_secondary_node("Accepting connection from {$_SERVER["REMOTE_ADDR"]}");

    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        echo "<ERROR>{license_error}</ERROR>";
        die();
    }
    if (!is_file("/usr/sbin/keepalived")) {
        echo "<ERROR>{keepalived_not_installed} {$_POST["secondary_node_ip"]}</ERROR>";
        die();
    }

    $APP_KEEPALIVED_ENABLE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE"));
    if (intval($APP_KEEPALIVED_ENABLE) == 1) {
        echo "<ERROR>{keepalived_primary_node_installed} {$_POST["secondary_node_ip"]}</ERROR>";
        die();
    }

    $APP_KEEPALIVED_ENABLE_SLAVE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));
    if ($APP_KEEPALIVED_ENABLE_SLAVE == 0) {
        keepalived_syslog_secondary_node("ORDER: install Failover feature");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("keepalived.php?enable-secondary_node=yes");
    }

    echo "<STATUS>2</STATUS>";
    keepalived_syslog_secondary_node("Starting installation procedure.");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("keepalived.php?action-delete-nodes=yes&data=$postdata");
}
function ping_keepalived_nodes()
{
    $postdata = $_POST["post_data"];
    keepalived_syslog_secondary_node("Accepting connection from {$_SERVER["REMOTE_ADDR"]}");

    echo "<STATUS>100</STATUS>";
    keepalived_syslog_secondary_node("Starting installation procedure.");
}
function keepalived_node_service_status()
{
    $primaryNodeID = $_POST["primaryNodeID"];
    $synckey = $_POST["synckey"];
    $state = $_POST["keepalivedServiceStatus"];
    $hostname = $_POST["hostname"];
    keepalived_syslog_primary_node("{$_SERVER["REMOTE_ADDR"]} ($hostname) ident:$primaryNodeID report service status $state");
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");


    $q->QUERY_SQL("UPDATE keepalived_secondary_nodes SET service_state='$state', hostname='$hostname' WHERE synckey=$synckey AND primary_node_id=$primaryNodeID");
}
function keepalived_node_status()
{
    $primaryNodeID = $_POST["primaryNodeID"];
    $secondary_nodeID = $_POST["secondary_nodeID"];
    $status = $_POST["keepalivedStatus"];
    $error = $_POST["keepalivedError"];
    $hostname = $_POST["hostname"];
    keepalived_syslog_primary_node("{$_SERVER["REMOTE_ADDR"]} ($hostname) ident:$primaryNodeID report status $status");
    if (!empty($secondary_nodeID)) {
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $q->QUERY_SQL("UPDATE keepalived_secondary_nodes SET status=$status,errortext='$error', hostname='$hostname' WHERE synckey=$secondary_nodeID AND primary_node_id=$primaryNodeID");
    }


}

//END KEEPALIVES

function SetDebug()
{
    ini_set('html_errors', 0);
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', $_SERVER["SERVER_ADDR"] . ":");
    ini_set('error_append_string', "");
}

function LEGAL_LOGS_UNREGISTER(){
    $ipaddr=$_SERVER["REMOTE_ADDR"];
    $LegalLogsProxyServers=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogsProxyServers"));
    unset($LegalLogsProxyServers[$ipaddr]);
    $Final=base64_encode(serialize($LegalLogsProxyServers));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegalLogsProxyServers",$Final);
    echo "<SUCCESS>OK</SUCCESS>";
}
function LDAP_CLUSTER_INFOS(){

    $MAIN=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["ldapcluster"]);
    if(!isset($MAIN["username"])){
        $array["ERROR"]=true;
        $array["ERROR_SHOW"]="{wrong_privileges} ERR.554";
        $arrstr=base64_encode(serialize($array));
        echo "<RESULTS>$arrstr</RESULTS>\n";
        die();
    }
    if(!isset($MAIN["password"])){
        $array["ERROR"]=true;
        $array["ERROR_SHOW"]="{wrong_privileges} ERR.560";
        $arrstr=base64_encode(serialize($array));
        echo "<RESULTS>$arrstr</RESULTS>\n";
        die();
    }

    if(!isAuthenticated($MAIN["username"],$MAIN["password"])){
        $array["ERROR"]=true;
        $array["ERROR_SHOW"]="{wrong_privileges} ERR.569";
        $arrstr=base64_encode(serialize($array));
        echo "<RESULTS>$arrstr</RESULTS>\n";
        die();
    }

    $EnableLDAPSyncProv=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProv"));
    if($EnableLDAPSyncProv==0){
        $array["ERROR"]=true;
        $array["ERROR_SHOW"]="NOT A MASTER";
        $arrstr=base64_encode(serialize($array));
        echo "<RESULTS>$arrstr</RESULTS>\n";
        die();
    }

    $SyncProvUser=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyncProvUserDN");
    if($SyncProvUser==null){
        $array["ERROR"]=true;
        $array["ERROR_SHOW"]="NO SYNC USER DEFINED!";
        $arrstr=base64_encode(serialize($array));
        echo "<RESULTS>$arrstr</RESULTS>\n";
        die();
    }
    $LdapListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapListenInterface"));
    if($LdapListenInterface==null){$LdapListenInterface="lo";}
    if($LdapListenInterface=="lo"){
        $array["ERROR"]=true;
        $array["ERROR_SHOW"]="NO LISTEN INTERFACE DEFINED";
        $arrstr=base64_encode(serialize($array));
        echo "<RESULTS>$arrstr</RESULTS>\n";
        die();

    }


    $ldap=new clladp();
    $HASH=$ldap->GetDNValues($SyncProvUser);
    $RESULTS["ERROR"]=false;
    $RESULTS["LDAPSyncProvClientBindPassword"]=$HASH[0]["userpassword"][0];
    $RESULTS["LDAPSyncProvClientBindDN"]=$SyncProvUser;
    $RESULTS["LDAPSyncProvClientSearchBase"]=$ldap->suffix;
    $arrstr=base64_encode(serialize($RESULTS));
    echo "<RESULTS>$arrstr</RESULTS>\n";
    die();
}
function LEGAL_LOGS_REGISTER(){

    $MAIN=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["legallogs-register"]);

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        echo "<error>{license_error}</error>";
        die();
    }

    $user=trim(strtolower($MAIN["CREDS"]["USER"]));
    $password=$MAIN["CREDS"]["PASS"];
    $HOST=$MAIN["CREDS"]["HOST"];

    if(!isAuthenticated($user,$password)){"<error>$user {wrong_privileges}</error>";die();}

    $ipaddr=$_SERVER["REMOTE_ADDR"];
    $LegalLogsProxyServers=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogsProxyServers"));
    $LegallogServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegallogServerPort"));
    if($LegallogServerPort==0){$LegallogServerPort=10514;}

    zWriteToSyslog("Request open Proxy Logs by $ipaddr ($HOST) local server port: $LegallogServerPort","LEGAL_LOGS");
    $LegalLogsProxyServers[$ipaddr]=$HOST;
    $Final=base64_encode(serialize($LegalLogsProxyServers));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegalLogsProxyServers",$Final);
    echo "<SUCCESS>$LegallogServerPort</SUCCESS>";

}
function LEGALSLOGS_UPLOADED(){
    $sf=" from {$_SERVER["REMOTE_ADDR"]}";
    $phpFileUploadErrors = array(
        0 => 'There is no error, the file uploaded with success',
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
    );

    $uploaded_filename=$_FILES["file"]["name"];
    $uploaded_tmpname=$_FILES["file"]["tmp_name"];
    $uploaded_error=intval($_FILES["file"]["error"]);
    $uploaded_size=intval($_FILES["file"]["size"]);
    $target=$_POST["target"];
    $src_md5=$_POST["md5"];

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        zWriteToSyslog("[Legal Logs]: Unable to receive $uploaded_filename with error License Error $sf");
        if(is_file($uploaded_tmpname)){@unlink($uploaded_tmpname);}
        die();
    }

    if($uploaded_error>0){
        $uploaded_error_text=$phpFileUploadErrors[$uploaded_error];
        zWriteToSyslog("[Legal Logs]: Unable to receive $uploaded_filename with error $uploaded_error ($uploaded_error_text) $sf");
        http_response_code(500);
        if(is_file($uploaded_tmpname)){@unlink($uploaded_tmpname);}
        exit;
    }
    $tarballs_dir="/usr/share/artica-postfix/ressources/conf/upload/LEGALS";
    if(!is_dir($tarballs_dir)){@mkdir($tarballs_dir,0755,true);}
    $tarballs_file="$tarballs_dir/$uploaded_filename";
    $tarballs_explain="$tarballs_dir/$uploaded_filename.txt";

    if (!move_uploaded_file($uploaded_tmpname, $tarballs_file)){
        if(is_file($uploaded_tmpname)){@unlink($uploaded_tmpname);}
        zWriteToSyslog("[Legal Logs]: Unable to move $uploaded_tmpname to $tarballs_file $sf");
        http_response_code(500);
        exit;
    }


    $md5=md5_file($tarballs_file);
    if($md5<>$src_md5){
        zWriteToSyslog("[Legal Logs]: Corrupted upload $uploaded_filename, aborting ($md5<>$src_md5) $sf");
        http_response_code(500);
        exit;
    }

    @file_put_contents($tarballs_explain,$target);
    $uploaded_size_kb=round($uploaded_size/1024,2);
    zWriteToSyslog("[Legal Logs]: Success received $uploaded_filename ({$uploaded_size_kb}Kb $sf");
    http_response_code(200);


}
function zWriteToSyslog($text,$bin="stats-appliance"){
		if(!function_exists("syslog")){return;}
		$LOG_SEV=LOG_INFO;
		openlog($bin, LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $text);
		closelog();
	
	}
function hacluster_syslog($text){
    if(!function_exists("syslog")){return;}
    $LOG_SEV=LOG_INFO;
    openlog("hacluster-client", LOG_PID , LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();

}
function hacluster_syslog_master($text){
    echo $text."\n";
    if(!function_exists("syslog")){return;}
    $LOG_SEV=LOG_INFO;
    openlog("hacluster", LOG_PID , LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
}


function hacluster_receive_register(){
    include_once(dirname(__FILE__)."/ressources/class.manager.inc");
    $UserName=$_POST["HaClusterRegister"];
    $Password=$_POST["HaClusterRegisterPassword"];
    $ComPort=$_POST["ComPort"];
    $ID=intval($_POST["ID"]);
    if($ID==0){
        echo "<ERROR>No such ID</ERROR>";
        die();
    }
    $manager=new class_manager();
    $Admin=md5(strtolower($manager->admin));
    $PasswordSrc=md5($manager->password);
    if( $UserName<>$Admin){
        echo "<ERROR>Credentials failed</ERROR>";
        die();
    }
    if($Password<>$PasswordSrc){
        echo "<ERROR>Credentials failed</ERROR>";
        die();
    }


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterID",$ID);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterClientListenPort",$ComPort);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/install");
    echo "\nRETURNED_TRUE\n";
    die();
}






function DNS_LINKER(){
	include_once("ressources/class.pdns.inc");
	$ME=$_SERVER["SERVER_ADDR"];
	
	$content_dir=dirname(__FILE__)."/ressources/conf/upload";
	writelogs("DNS_LINKER:: Request from " .$_SERVER["REMOTE_ADDR"]."",__FUNCTION__,__FILE__,__LINE__);
	
	
	
	writelogs("DNS_LINKER:: ->LDAP()",__FUNCTION__,__FILE__,__LINE__);
	
	$ldap=new clladp();
	if(preg_match("#^(.+?):(.+)#", $_POST["CREDS"],$re)){
		$SuperAdmin=$re[1];
		$SuperAdminPass=$re[2];
	}
	
	if($SuperAdmin<>$ldap->ldap_admin){
		writelogs("DNS_LINKER:: Invalid credential...",__FUNCTION__,__FILE__,__LINE__);
		header_status(500);
		echo "Invalid credential...\n";die("Invalid credential...");
	}
	if(md5($ldap->ldap_password)<>$SuperAdminPass){
		writelogs("DNS_LINKER:: Invalid credential...",__FUNCTION__,__FILE__,__LINE__);
		header_status(500);
		echo "Invalid credential...\n";die("Invalid credential...");
	}
	
	$TFILE=tempnam($content_dir,"dns-linker-");
	
	@file_put_contents($TFILE, base64_decode($_POST["DNS_LINKER"]));
	
	writelogs("DNS_LINKER:: zuncompress() $TFILE",__FUNCTION__,__FILE__,__LINE__);
	
	zuncompress($TFILE,"$TFILE.txt");
	@unlink($TFILE);
	$filesize=@filesize("$TFILE.txt");
	echo "$TFILE.txt -> $filesize bytes\n";
	
	$curlparms=unserializeb64(@file_get_contents("$TFILE.txt"));
	writelogs("DNS_LINKER:: Loading() $TFILE.txt -> ( ".count($curlparms)." items )",__FUNCTION__,__FILE__,__LINE__);
	
	@unlink("$TFILE.txt");
	
	
	if(!is_array($curlparms)){
		writelogs("DNS_LINKER:: Loading() curlparms no such array",__FUNCTION__,__FILE__,__LINE__);
		header_status(500);
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	
	$zdate=time();
	$sql="SELECT name,domain_id FROM records WHERE `content`='{$curlparms["listen_addr"]}'";
	$hostname=$curlparms["hostname"];
	$q=new mysql();
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"powerdns"));
	if($ligne["name"]==null){
		$tr=explode(".",$hostname);
		$netbiosname=$tr[0];
		$dnsname=str_replace("$netbiosname.", "", $hostname);
		$dns=new pdns($dnsname);
		$dns->EditIPName($netbiosname, $curlparms["listen_addr"], "A");
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"powerdns"));
	}
	if($ligne["name"]==null){
		writelogs("DNS_LINKER:: Error, unable to get name",__FUNCTION__,__FILE__,__LINE__);
		header_status(500);
		die("DIE " .__FILE__." Line: ".__LINE__);		
	}
	
	$domain_id=$ligne["domain_id"];
	$hostname_sql=$ligne["name"];

    foreach ($curlparms["FREEWEBS_SRV"] as $name=>$val){
		if($name==$hostname_sql){continue;}
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT name FROM records WHERE `name`='$name' AND `type`='CNAME'","powerdns"));
		writelogs("DNS_LINKER::$hostname_sql:: $name QUERY = `{$ligne["name"]}`",__FUNCTION__,__FILE__,__LINE__);
		if($ligne["name"]<>null){continue;}
		writelogs("DNS_LINKER:: $name ADD {$curlparms["listen_addr"]}",__FUNCTION__,__FILE__,__LINE__);
		$q->QUERY_SQL("INSERT INTO records (`domain_id`,`name`,`type`,`content`,`ttl`,`prio`,`change_date`)
			VALUES($domain_id,'$name','CNAME','$hostname_sql','86400','0','$zdate')","powerdns");
			header_status(500);
			if(!$q->ok){echo $q->mysql_error."\n";}
			
	}
	header_status(200);
	die("DIE " .__FILE__." Line: ".__LINE__);
	
	
}

function header_status($statusCode) {
	static $status_codes = null;

	if ($status_codes === null) {
		$status_codes = array (
				100 => 'Continue',
				101 => 'Switching Protocols',
				102 => 'Processing',
				200 => 'OK',
				201 => 'Created',
				202 => 'Accepted',
				203 => 'Non-Authoritative Information',
				204 => 'No Content',
				205 => 'Reset Content',
				206 => 'Partial Content',
				207 => 'Multi-Status',
				300 => 'Multiple Choices',
				301 => 'Moved Permanently',
				302 => 'Found',
				303 => 'See Other',
				304 => 'Not Modified',
				305 => 'Use Proxy',
				307 => 'Temporary Redirect',
				400 => 'Bad Request',
				401 => 'Unauthorized',
				402 => 'Payment Required',
				403 => 'Forbidden',
				404 => 'Not Found',
				405 => 'Method Not Allowed',
				406 => 'Not Acceptable',
				407 => 'Proxy Authentication Required',
				408 => 'Request Timeout',
				409 => 'Conflict',
				410 => 'Gone',
				411 => 'Length Required',
				412 => 'Precondition Failed',
				413 => 'Request Entity Too Large',
				414 => 'Request-URI Too Long',
				415 => 'Unsupported Media Type',
				416 => 'Requested Range Not Satisfiable',
				417 => 'Expectation Failed',
				422 => 'Unprocessable Entity',
				423 => 'Locked',
				424 => 'Failed Dependency',
				426 => 'Upgrade Required',
				500 => 'Internal Server Error',
				501 => 'Not Implemented',
				502 => 'Bad Gateway',
				503 => 'Service Unavailable',
				504 => 'Gateway Timeout',
				505 => 'HTTP Version Not Supported',
				506 => 'Variant Also Negotiates',
				507 => 'Insufficient Storage',
				509 => 'Bandwidth Limit Exceeded',
				510 => 'Not Extended'
		);
	}

	if ($status_codes[$statusCode] !== null) {
		$status_string = $statusCode . ' ' . $status_codes[$statusCode];
		header($_SERVER['SERVER_PROTOCOL'] . ' ' . $status_string, true, $statusCode);
	}
}

function export_squid_table(){
	$workdir=dirname(__FILE__)."/ressources/squid-export";
	$table=$_GET["squid-table"];
	$q=new mysql_squid_builder();
	$q->BD_CONNECT();
	if(is_file("$workdir/$table.gz")){@unlink("$workdir/$table.gz");}
	$dump=new phpMyDumper("squidlogs",$q->mysqli_connection,"$workdir/$table.gz",true,$table);
	$dump->doDump();
	$sock=new sockets();
	$content_type=base64_decode($sock->getFrameWork("cmd.php?mime-type=".base64_encode("$workdir/$table.gz")));
	$fsize = filesize("$workdir/$table.gz");
	
	
	
	if($GLOBALS["VERBOSE"]){
		echo "Content-type: $content_type<br>\nfilesize:$fsize<br>\n";
		
		return;}
	
	header('Content-type: '.$content_type);
	
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$table.gz\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé

	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	readfile("$workdir/$table.gz");	
	
	
	
}
	
function SETTINGS_INC(){
	$ME=$_SERVER["SERVER_ADDR"];
	$q=new mysql_blackbox();
	$q->CheckTables();
	$sock=new sockets();
	reset($_FILES['SETTINGS_INC']);
	$error=$_FILES['SETTINGS_INC']['error'];
	$tmp_file = $_FILES['SETTINGS_INC']['tmp_name'];
	$hostname=$_POST["HOSTNAME"];
	$nodeid=$_POST["nodeid"];
	$hostid=$_POST["hostid"];
	
	zWriteToSyslog("($hostname): Receive $nodeid/$hostid");
	
	$content_dir=dirname(__FILE__)."/ressources/conf/upload/$hostname-$nodeid";
	
	if(!is_dir($content_dir)){mkdir($content_dir,0755,true);}
	if( !is_uploaded_file($tmp_file) ){
        foreach ($_FILES['DNS_LINKER'] as $num=>$val){
            $error[]="$num:$val";
        }
        writelogs("ERROR:: ".@implode("\n", $error),__FUNCTION__,__FILE__,__LINE__);exit();}
	
	
	
	$sql="SELECT hostid,nodeid FROM nodes WHERE `hostid`='$hostid'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	if($GLOBALS["VERBOSE"]){
		echo "SELECT hostid,nodeid FROM nodes WHERE `hostid`='$hostid' -> {$ligne["hostid"]}\n";
	}
		
	
	
	 
	$type_file = $_FILES['SETTINGS_INC']['type'];
	$name_file = $_FILES['SETTINGS_INC']['name'];
	writelogs("$hostname ($nodeid):: receive name_file=$name_file; type_file=$type_file",__FUNCTION__,__FILE__,__LINE__);
	if(file_exists( $content_dir . "/" .$name_file)){@unlink( $content_dir . "/" .$name_file);}
	

	
	
 	if( !move_uploaded_file($tmp_file, $content_dir . "/" .$name_file) ){
		writelogs("$hostname ($nodeid) Error Unable to Move File : ". $content_dir . "/" .$name_file,__FUNCTION__,__FILE__,__LINE__);
		return;
 	}
    $moved_file=$content_dir . "/" .$name_file;	
    if(!is_file($moved_file)){
    	writelogs("$hostname ($nodeid) $moved_file no such file",__FUNCTION__,__FILE__,__LINE__);
    	return;
    }
    $filesize=@filesize($moved_file);
    zWriteToSyslog("($hostname): Uncompress $moved_file (".round($filesize/1024)." Kb)");
    zuncompress($moved_file,"$moved_file.txt");
    
    $curlparms=unserializeb64(@file_get_contents("$moved_file.txt"));
    @unlink("$moved_file.txt");
	if(!is_array($curlparms)){
		writelogs("blackboxes::$hostname ($nodeid) Error $moved_file.txt : Not an array...",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	if(isset($curlparms["VERBOSE"])){
		echo "STATISTICS APPLIANCE -> VERBOSE MODE\n";
		$GLOBALS["VERBOSE"]=true;
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
		ini_set('error_prepend_string','');
		ini_set('error_append_string','');
	}
	
	$MYSSLPORT=$curlparms["ArticaHttpsPort"];
	$ISARTICA=$curlparms["ISARTICA"];
	$ssl=$curlparms["usessl"];
	$sql="SELECT hostid,nodeid FROM nodes WHERE `hostid`='$hostid'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	if($GLOBALS["VERBOSE"]){
		echo "SELECT hostid,nodeid FROM nodes WHERE `hostid`='$hostid' -> {$ligne["hostid"]}\n";
	}
	
	if(!$q->TABLE_EXISTS("nodes")){$q->CheckTables();}
	
	if($ligne["hostid"]==null){
		$sql="INSERT INTO nodes (`hostname`,`ipaddress`,`port`,`hostid`,`BigArtica`,`ssl`) 
		VALUES ('$hostname','{$_SERVER["REMOTE_ADDR"]}','$MYSSLPORT','$hostid','$ISARTICA','$ssl')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "<ERROR>$ME: Statistics appliance: $q->mysql_error:\n$sql\n line:".__LINE__."</ERROR>\n";return;}	
		$sql="SELECT hostid,nodeid FROM nodes WHERE `hostid`='$hostid'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));	
			
	}
	$nodeid=$ligne["nodeid"];
	if($GLOBALS["VERBOSE"]){echo "Output nodeid:$nodeid\n";}
	echo "\n<NODEID>$nodeid</NODEID>\n";
	
	
	$settings=$curlparms["SETTINGS_PARAMS"];
	$softs=$curlparms["softwares"];
	$perfs=$curlparms["perfs"];
	$prodstatus=$curlparms["prodstatus"];
	$back=new blackboxes($nodeid);
	zWriteToSyslog("($hostname): Artica version v.{$curlparms["VERSION"]}");
	$back->VERSION=$curlparms["VERSION"];
	$back->hostname=$hostname;
	if($GLOBALS["VERBOSE"]){echo "Statistics Appliance:: $hostname ($nodeid) v.{$curlparms["VERSION"]}\n";}
	writelogs("$hostname ($nodeid) v.{$curlparms["VERSION"]}",__FUNCTION__,__FILE__,__LINE__);
	$back->SaveSettingsInc($settings,$perfs,$softs,$prodstatus,$curlparms["ISARTICA"]);
	$back->SaveDisks($curlparms["disks_list"]);
	
	if(isset($curlparms["YOREL"])){
		$mepath=dirname(__FILE__);
		$srcYourelPAth="$mepath/logs/web/$hostid/yorel.tar.gz";
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
		ini_set('error_prepend_string',$_SERVER["SERVER_ADDR"].":");
		ini_set('error_append_string',"");
		if(is_dir($srcYourelPAth)){
			if($GLOBALS["VERBOSE"]){echo "{$_SERVER["SERVER_ADDR"]}: $srcYourelPAth is a directory ??\n";}
			$sock->getFrameWork("services.php?chown-medir=".base64_encode($srcYourelPAth));
			rmdir($srcYourelPAth);
		}
		if(!is_dir(dirname($srcYourelPAth))){mkdir(dirname($srcYourelPAth),0755,true);}
		$sock->getFrameWork("services.php?chown-medir=".base64_encode(dirname($srcYourelPAth)));
		file_put_contents($srcYourelPAth, base64_decode($curlparms["YOREL"]));
		if(is_file($srcYourelPAth)){
			unset($curlparms["YOREL"]);
			if($GLOBALS["VERBOSE"]){echo "{$_SERVER["SERVER_ADDR"]}: $srcYourelPAth ". filesize($srcYourelPAth)." bytes\n";}
			exec("/bin/tar -xvf $srcYourelPAth -C ".dirname($srcYourelPAth)."/ 2>&1",$out);
			unlink($srcYourelPAth);
			$sock->getFrameWork("services.php?chowndir=".base64_encode(dirname($srcYourelPAth)));
		}else{
			if($GLOBALS["VERBOSE"]){echo "{$_SERVER["SERVER_ADDR"]}: $srcYourelPAth no such file\n";}
		}
	}
	
	zWriteToSyslog("($hostname): Squid-Cache version {$curlparms["SQUIDVER"]}");
	writelogs("blackboxes::$hostname squid version {$curlparms["SQUIDVER"]}",__FUNCTION__,__FILE__,__LINE__);
	
	if(strlen(trim($curlparms["SQUIDVER"]))>1){
		$qSQ=new mysql_squid_builder();
		
		writelogs($_SERVER["REMOTE_ADDR"] .":port:: `$MYSSLPORT` production server....",__FUNCTION__,__FILE__,__LINE__);
		$hostname=gethostbyaddr($_SERVER["REMOTE_ADDR"]);
		$time=date('Y-m-d H:i:s');
			
	}
	
	if(isset($curlparms["nets"])){
		writelogs("blackboxes::$hostname ($nodeid):: -> CARDS",__FUNCTION__,__FILE__,__LINE__);
		$back->SaveNets($curlparms["nets"]);
	}else{
		writelogs("blackboxes::$hostname ($nodeid):: No network cards info sended",__FUNCTION__,__FILE__,__LINE__);
	}
	if(isset($curlparms["squid_caches_info"])){$back->squid_save_cache_infos($curlparms["squid_caches_info"]);}
	if(isset($curlparms["squid_system_info"])){$back->squid_save_system_infos($curlparms["squid_system_info"]);}
	
	if(isset($curlparms["CACHE_LOGS"])){$back->squid_save_cachelogs($curlparms["CACHE_LOGS"]);}	
	if(isset($curlparms["ETC_SQUID_CONF"])){$back->squid_save_etcconf($curlparms["ETC_SQUID_CONF"]);}
	if(isset($curlparms["UFDBCLIENT_LOGS"])){$back->squid_ufdbclientlog($curlparms["UFDBCLIENT_LOGS"]);}
	if(isset($curlparms["TOTAL_MEMORY_MB"])){$back->system_update_memory($curlparms["TOTAL_MEMORY_MB"]);}
	if(isset($curlparms["SQUID_SMP_STATUS"])){$back->system_update_smtpstatus($curlparms["SQUID_SMP_STATUS"]);}
	if(isset($curlparms["BOOSTER_SMP_STATUS"])){$back->system_update_boostersmp($curlparms["BOOSTER_SMP_STATUS"]);}
	
	
	
	
	writelogs("blackboxes::$hostname ($nodeid):: Full squid version {$curlparms["SQUIDVER"]}",__FUNCTION__,__FILE__,__LINE__);
	
	if(isset($curlparms["SQUIDVER"])){$back->squid_save_squidver($curlparms["SQUIDVER"]);}
	if(isset($curlparms["ARCH"])){$back->SetArch($curlparms["ARCH"]);}
	if(isset($curlparms["PARMS"])){$back->DaemonsSettings($curlparms["PARMS"]);}		
	
	
	writelogs("blackboxes::$hostname ($nodeid): check orders...",__FUNCTION__,__FILE__,__LINE__);
	zWriteToSyslog("($hostname): Checks Orders....");
	$back->EchoOrders();
		
	
	
}


function stats_berekley_upload(){
	$hostname=$_POST["HOSTNAME"];
	$FILENAME=$_POST["FILENAME"];
	$SIZE=$_POST["SIZE"];
	$UUID=trim($_POST["UUID"]);
	$content_dir=dirname(__FILE__)."/ressources/conf/upload/BEREKLEY";
	$moved_file=$content_dir . "/$UUID-$FILENAME";
	@mkdir($content_dir,0755,true);
	if($UUID==null){$UUID=time();}
	$UploadedDir=$content_dir;
	@mkdir($UploadedDir,0755,true);
	if(!is_dir($UploadedDir)){return false;}
	
	foreach ($_FILES as $key=>$arrayF){
		$type_file = $arrayF['type'];
		$name_file = $arrayF['name'];
		$tmp_file  = $arrayF['tmp_name'];
				
		$target_file="$UploadedDir/$UUID-$name_file";
		if(file_exists( $target_file)){@unlink( $target_file);}
		if( !move_uploaded_file($tmp_file, $target_file) ){
			echo "$UUID: Error Unable to Move File : $target_file\n";
			writelogs("$UUID: Error Unable to Move File : $target_file",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
				
		}
		$sock=new sockets();
		$sock->getFrameWork("artica.php?stats-appliance-berekley=yes");
	
	
}

function isAuthenticated($username,$password){
    $username=strtolower(trim($username));

    $logon=new artica_logon($username,$password);
    $logon->Simulate=true;
    if($logon->isManager()){
        $logon->webconsole_syslog("Success connected as Manager using internal API");
        return true;
    }
    if(!$logon->CheckCreds()){
        $logon->webconsole_syslog("Failed connected as $username using internal API");
        $logon->logon_events("FAILED");
        return false;
    }
    if(!$logon->IsAdminPrivileges()){
        $logon->webconsole_syslog("Failed connected as $username (wrong privileges) using internal API");
        $logon->logon_events("FAILED");
        return false;
    }
    $logon->webconsole_syslog("Success connected as $username using internal API");
    return true;
}

function stats_appliance_upload(){
	$hostname=$_POST["HOSTNAME"];
	$sock=new sockets();	
	$credentials=unserializeb64($_POST["creds"]);
	$content_dir=dirname(__FILE__)."/ressources/conf/upload";
	$FILENAME=$_POST["FILENAME"];
	$SIZE=$_POST["SIZE"];
	$UUID=null;
	if(isset($_POST["UUID"])){
		$UUID=trim($_POST["UUID"]);
	}
	foreach ($_REQUEST as $num=>$array){
		if(is_array($array)){
			foreach ($array as $a=>$b){
				if(strlen($b)<150){
					writelogs("stats_appliance_upload:: RECEIVE `$num` = $b",__FUNCTION__,__FILE__,__LINE__);
				}
			}
			
			continue;
		}
		
		writelogs("stats_appliance_upload:: RECEIVE `$num` = $array",__FUNCTION__,__FILE__,__LINE__);
		
	
	}
	
	$ldap=new clladp();
	$array["DETAILS"][]="Manager: {$credentials["MANAGER"]}";
	if($ldap->ldap_admin<>$credentials["MANAGER"]){
		$array["APP_CREDS"]=false;
		squid_admin_mysql_mysql(0,"$hostname: Account mismatch..[$UUID]",null,__FUNCTION__,__FILE__,__LINE__);
		$array["DETAILS"][]="Account mismatch..";
		
		echo "\n\n<RESULTS>FAILED</RESULTS>\n\n";
		return;
	}
	if($ldap->ldap_password<>$credentials["PASSWORD"]){
		$array["APP_CREDS"]=false;
		$array["DETAILS"][]="Password mismatch..";
		squid_admin_mysql_mysql(0,"$hostname: Password mismatch..[$UUID]",null,__FUNCTION__,__FILE__,__LINE__);
		echo "\n\n<RESULTS>FAILED</RESULTS>\n\n";
		return;
	}
	

	
	@mkdir($content_dir,0755,true);
	
	writelogs("SQUID_STATS_CONTAINER = ".strlen($_REQUEST["SQUID_STATS_CONTAINER"])." bytes ",__FUNCTION__,__FILE__,__LINE__);
	
	
	
	$time=time();
	$moved_file=$content_dir . "/$hostname-$time-$FILENAME";
		if($UUID<>null){$moved_file=$content_dir . "/$hostname-$UUID-$time-$FILENAME";
	}
	@file_put_contents($moved_file, base64_decode($_REQUEST["SQUID_STATS_CONTAINER"]));
	
	if(!is_file($moved_file)){
		squid_admin_mysql_mysql(0,"$hostname $moved_file no such file [$UUID]",null,__FUNCTION__,__FILE__,__LINE__);
		writelogs("$hostname $moved_file no such file",__FUNCTION__,__FILE__,__LINE__);
		echo "\n\n<RESULTS>FAILED</RESULTS>\n\n";
		return;
	}
	$filesize=@filesize($moved_file);
	$filesizeKB=$filesize/1024;
	$filesizeMB=$filesizeKB/1024;
	writelogs("$hostname $moved_file {$filesize} bytes - $filesizeMB MB - ",__FUNCTION__,__FILE__,__LINE__);
	
	if($filesize<>$SIZE){
		$diff=intval($filesize-$SIZE);
		squid_admin_mysql_mysql(0,"$hostname $moved_file size differ {$diff}Bytes!!! [$UUID]",null,__FUNCTION__,__FILE__,__LINE__);
		writelogs("$hostname $moved_file size differ {$diff}Bytes!!!",__FUNCTION__,__FILE__,__LINE__);
		echo "\n\n<RESULTS>FAILED</RESULTS>\n\n";
		return;
	}
	
	$moved_filebas=basename($moved_file);
	$filesize=FormatBytes($filesize/1024);
	squid_admin_mysql_mysql(2,"$hostname: Success uploaded $moved_filebas ( $filesize ) [$UUID]",null,__FUNCTION__,__FILE__,__LINE__);
	writelogs("$hostname $moved_file OK!!!",__FUNCTION__,__FILE__,__LINE__);
	
	$data=trim($sock->getFrameWork("squidstats.php?move-stats-file=".urlencode($moved_file)));
	if($data<>"SUCCESS"){
		squid_admin_mysql_mysql(0,"$hostname: failed to move uploaded - $data -$moved_filebas ( $filesize ) [$UUID]",null,__FUNCTION__,__FILE__,__LINE__);
		echo "\n\n<RESULTS>FAILED</RESULTS>\n\n";
		return;
	}
	
	echo "\n\n<RESULTS>SUCCESS</RESULTS>\n\n";
	
}


function SETTINGS_INC_2(){
	$ME=$_SERVER["SERVER_ADDR"];
	$q=new mysql_blackbox();
	$q->CheckTables();
	$sock=new sockets();
	$hostname=$_POST["HOSTNAME"];
	$nodeid=$_POST["nodeid"];
	$hostid=$_POST["hostid"];

	zWriteToSyslog("($hostname): Receive $nodeid/$hostid");
	
	$content_dir=dirname(__FILE__)."/ressources/conf/upload/$hostname-$nodeid";
	$curlparms=$_REQUEST;

	if(isset($_FILES)){
		writelogs("blackboxes:: _FILES -> ".count($_FILES),__FUNCTION__,__FILE__,__LINE__);
	}else{
		writelogs("blackboxes:: _FILES -> NONE",__FUNCTION__,__FILE__,__LINE__);
	}	
	
	
	
	
	
	@mkdir($content_dir,0755,true);
	$moved_file=$content_dir . "/settings.gz";
	@file_put_contents($moved_file, base64_decode($_REQUEST["SETTINGS_INC"]));
	if(!is_file($moved_file)){
		writelogs("$hostname ($nodeid) $moved_file no such file",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$filesize=@filesize($moved_file);
	zWriteToSyslog("($hostname): Uncompress $moved_file (".round($filesize/1024)." Kb)");
	zuncompress($moved_file,"$moved_file.txt");	
	$curlparms=$GLOBALS["CLASS_SOCKETS"]->unserializeb64(@file_get_contents("$moved_file.txt"));
	@unlink($curlparms);
	

	if(isset($curlparms["VERBOSE"])){
		echo "STATISTICS APPLIANCE -> VERBOSE MODE\n";
		$GLOBALS["VERBOSE"]=true;
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
		ini_set('error_prepend_string','');
		ini_set('error_append_string','');
	}

	$MYSSLPORT=$curlparms["ArticaHttpsPort"];
	$ISARTICA=$curlparms["ISARTICA"];
	$ssl=$curlparms["usessl"];
	$sql="SELECT hostid,nodeid FROM nodes WHERE `hostid`='$hostid'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	if($GLOBALS["VERBOSE"]){
		echo "SELECT hostid,nodeid FROM nodes WHERE `hostid`='$hostid' -> {$ligne["hostid"]}\n";
	}

	if(!$q->TABLE_EXISTS("nodes")){$q->CheckTables();}

	if($ligne["hostid"]==null){
		$sql="INSERT INTO nodes (`hostname`,`ipaddress`,`port`,`hostid`,`BigArtica`,`ssl`)
		VALUES ('$hostname','{$_SERVER["REMOTE_ADDR"]}','$MYSSLPORT','$hostid','$ISARTICA','$ssl')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "<ERROR>$ME: Statistics appliance: $q->mysql_error:\n$sql\n line:".__LINE__."</ERROR>\n";return;}
		$sql="SELECT hostid,nodeid FROM nodes WHERE `hostid`='$hostid'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
			
	}
	$nodeid=$ligne["nodeid"];
	if($GLOBALS["VERBOSE"]){echo "Output nodeid:$nodeid\n";}
	echo "\n<NODEID>$nodeid</NODEID>\n";


	$settings=$curlparms["SETTINGS_INC"];
	$softs=$curlparms["softwares"];
	$perfs=$curlparms["perfs"];
	$prodstatus=$curlparms["prodstatus"];
	$back=new blackboxes($nodeid);
	zWriteToSyslog("($hostname): Artica version v.{$curlparms["VERSION"]}");
	$back->VERSION=$curlparms["VERSION"];
	$back->hostname=$hostname;
	if($GLOBALS["VERBOSE"]){echo "Statistics Appliance:: $hostname ($nodeid) v.{$curlparms["VERSION"]}\n";}
	writelogs("$hostname ($nodeid) v.{$curlparms["VERSION"]}",__FUNCTION__,__FILE__,__LINE__);
	$back->SaveSettingsInc($settings,$perfs,$softs,$prodstatus,$curlparms["ISARTICA"]);
	$back->SaveDisks($curlparms["disks_list"]);

	if(isset($curlparms["YOREL"])){
		$mepath=dirname(__FILE__);
		$srcYourelPAth="$mepath/logs/web/$hostid/yorel.tar.gz";
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
		ini_set('error_prepend_string',$_SERVER["SERVER_ADDR"].":");
		ini_set('error_append_string',"");
		if(is_dir($srcYourelPAth)){
			if($GLOBALS["VERBOSE"]){echo "{$_SERVER["SERVER_ADDR"]}: $srcYourelPAth is a directory ??\n";}
			$sock->getFrameWork("services.php?chown-medir=".base64_encode($srcYourelPAth));
			rmdir($srcYourelPAth);
		}
		if(!is_dir(dirname($srcYourelPAth))){mkdir(dirname($srcYourelPAth),0755,true);}
		$sock->getFrameWork("services.php?chown-medir=".base64_encode(dirname($srcYourelPAth)));
		file_put_contents($srcYourelPAth, base64_decode($curlparms["YOREL"]));
		if(is_file($srcYourelPAth)){
			unset($curlparms["YOREL"]);
			if($GLOBALS["VERBOSE"]){echo "{$_SERVER["SERVER_ADDR"]}: $srcYourelPAth ". filesize($srcYourelPAth)." bytes\n";}
			exec("/bin/tar -xvf $srcYourelPAth -C ".dirname($srcYourelPAth)."/ 2>&1",$out);

			unlink($srcYourelPAth);
			$sock->getFrameWork("services.php?chowndir=".base64_encode(dirname($srcYourelPAth)));
		}else{
			if($GLOBALS["VERBOSE"]){echo "{$_SERVER["SERVER_ADDR"]}: $srcYourelPAth no such file\n";}
		}
	}

	zWriteToSyslog("($hostname): Squid-Cache version {$curlparms["SQUIDVER"]}");
	writelogs("blackboxes::$hostname squid version {$curlparms["SQUIDVER"]}",__FUNCTION__,__FILE__,__LINE__);

	if(strlen(trim($curlparms["SQUIDVER"]))>1){

	}

	if(isset($curlparms["nets"])){
		writelogs("blackboxes::$hostname ($nodeid):: -> CARDS",__FUNCTION__,__FILE__,__LINE__);
		$back->SaveNets($curlparms["nets"]);
	}else{
		writelogs("blackboxes::$hostname ($nodeid):: No network cards info sended",__FUNCTION__,__FILE__,__LINE__);
	}
	if(isset($curlparms["squid_caches_info"])){$back->squid_save_cache_infos($curlparms["squid_caches_info"]);}
	if(isset($curlparms["squid_system_info"])){$back->squid_save_system_infos($curlparms["squid_system_info"]);}

	if(isset($curlparms["CACHE_LOGS"])){$back->squid_save_cachelogs($curlparms["CACHE_LOGS"]);}
	if(isset($curlparms["ETC_SQUID_CONF"])){$back->squid_save_etcconf($curlparms["ETC_SQUID_CONF"]);}
	if(isset($curlparms["UFDBCLIENT_LOGS"])){$back->squid_ufdbclientlog($curlparms["UFDBCLIENT_LOGS"]);}
	if(isset($curlparms["TOTAL_MEMORY_MB"])){$back->system_update_memory($curlparms["TOTAL_MEMORY_MB"]);}
	if(isset($curlparms["SQUID_SMP_STATUS"])){$back->system_update_smtpstatus($curlparms["SQUID_SMP_STATUS"]);}
	if(isset($curlparms["BOOSTER_SMP_STATUS"])){$back->system_update_boostersmp($curlparms["BOOSTER_SMP_STATUS"]);}




	writelogs("blackboxes::$hostname ($nodeid):: Full squid version {$curlparms["SQUIDVER"]}",__FUNCTION__,__FILE__,__LINE__);

	if(isset($curlparms["SQUIDVER"])){$back->squid_save_squidver($curlparms["SQUIDVER"]);}
	if(isset($curlparms["ARCH"])){$back->SetArch($curlparms["ARCH"]);}
	if(isset($curlparms["PARMS"])){$back->DaemonsSettings($curlparms["PARMS"]);}


	writelogs("blackboxes::$hostname ($nodeid): check orders...",__FUNCTION__,__FILE__,__LINE__);
	zWriteToSyslog("($hostname): Checks Orders....");
	$back->EchoOrders();



}
function PARSE_ORDERS(){
	$sock=new sockets();
	writelogs("Request PING-ORDER FROM " .$_SERVER["REMOTE_ADDR"],__FUNCTION__,__FILE__,__LINE__);
	writelogs("-> services.php?netagent-ping=yes",__FUNCTION__,__FILE__,__LINE__);
	$sock->getFrameWork("services.php?netagent-ping=yes");
	echo "<SUCCESS>SUCCESS</SUCCESS>";
	
}

function ORDER_DELETE(){
	$hostid=$_POST["hostid"];
	$blk=new blackboxes($hostid);
	echo "DELETING ORDER {$_POST["orderid"]}\n";
	
	writelogs("DEL ORDER \"{$_POST["orderid"]}\"",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql_blackbox();
	if(!$q->TABLE_EXISTS("poolorders")){$q->CheckTables();}
	$sql="DELETE FROM poolorders WHERE orderid='{$_POST["orderid"]}'";
	echo "$sql\n";
	$q->QUERY_SQL($sql);
	_udfbguard_admin_events("orderid {$_POST["roder_text"]} ({$_POST["orderid"]}) as been executed by remote host $blk->hostname", __FUNCTION__, __FILE__, __LINE__, "communicate");	
	if(!$q->ok){
		echo $q->mysql_error."\n";
		writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
	}	
}

function zuncompress($srcName, $dstName) {
	$sfp = gzopen($srcName, "rb");
	$fp = fopen($dstName, "w");
	while ($string = gzread($sfp, 4096)) {fwrite($fp, $string, strlen($string));}
	gzclose($sfp);
	fclose($fp);
} 	

function REGISTER(){
	$q=new mysql_blackbox();
	if(!isset($_POST["nets"])){die("No network sended");}
	$EncodedDatas=$_POST["nets"];
	$array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($EncodedDatas);
	$nodeid=$_POST["nodeid"];
	$hostid=$_POST["hostid"];
	$ISARTICA=$_POST["ISARTICA"];
	$usessl=$_POST["usessl"];
	
	if(!is_numeric($ISARTICA)){$ISARTICA=0;}
	if(!is_numeric($nodeid)){$nodeid=0;}
	if(!is_array($array)){
		echo "<ERROR>No an Array</ERROR>\n";
		writelogs("Not an array... ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	if(count($array)==0){
		echo "<ERROR>No item sended</ERROR>\n";
		writelogs("No item... ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	$sql="SELECT nodeid FROM nodes WHERE `hostid`='$hostid'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	$nodeid=$ligne["nodeid"];
	if(!is_numeric($nodeid)){$nodeid=0;}
	$ME=$_SERVER["SERVER_NAME"];
	
	
	$q=new mysql_blackbox();
	$q->CheckTables();
	
	if($nodeid>0){
		writelogs("item already exists",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		$sql="UPDATE nodes SET hostname='{$_POST["hostname"]}',
		ipaddress='{$_SERVER["REMOTE_ADDR"]}',
		port='{$_POST["port"]}',
		hostid='$hostid' WHERE nodeid='$nodeid'";
		if($GLOBALS["VERBOSE"]){echo "$ME:$sql\n";}
		$q->QUERY_SQL($sql);
		
		
		if(preg_match("#Unknown column 'hostid'#",$q->mysql_error)){
			$q->QUERY_SQL("DROP TABLE nodes");
			$q->CheckTables();
			$sql="INSERT INTO nodes (`hostname`,`ipaddress`,`port`,`hostid`,`BigArtica`,`ssl`) 
			VALUES ('{$_POST["hostname"]}','{$_SERVER["REMOTE_ADDR"]}','{$_POST["port"]}','$hostid','$ISARTICA','$usessl')";
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo "<ERROR>$ME: Statisics appliance: $q->mysql_error:\n$sql\n line:".__LINE__."</ERROR>\n";return;}
			$sql="SELECT nodeid FROM nodes WHERE `hostid`='$hostid'";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
			if(!$q->ok){echo "<ERROR>$ME: Statisics appliance: $q->mysql_error:\n$sql\n line:".__LINE__."</ERROR>\n";return;}
			$nodeid=$ligne["nodeid"];			
		}		
		
		if(!$q->ok){echo "<ERROR>$ME: Statisics appliance: $q->mysql_error:\n$sql\n line:".__LINE__."</ERROR>\n";return;}	
		echo "<SUCCESS>$nodeid</SUCCESS>";
		
	}else{
		echo "Adding new item\n...";
		
		writelogs("Adding new item",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$sql="INSERT INTO nodes (`hostname`,`ipaddress`,`port`,`hostid`,`BigArtica`,`ssl`) 
		VALUES ('{$_POST["hostname"]}','{$_SERVER["REMOTE_ADDR"]}','{$_POST["port"]}','$hostid','$ISARTICA','$usessl')";
		$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){if(!$q->ok){echo "<ERROR>$ME: Statisics appliance: $q->mysql_error: line:".__LINE__."</ERROR>\n";}}	
		if(preg_match("#Unknown column 'hostid'#",$q->mysql_error)){
			$q->QUERY_SQL("DROP TABLE nodes");
			$q->CheckTables();
			$q->QUERY_SQL($sql);
		}
		
		
		if(!$q->ok){echo "<ERROR>$ME:Statisics appliance: $q->mysql_error: line:".__LINE__."</ERROR>\n";return;}
		$sql="SELECT nodeid FROM nodes WHERE `hostid`='$hostid'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		echo "$ME:Success adding new item in the central server\n";
		echo "<SUCCESS>{$ligne["nodeid"]}</SUCCESS>";
	}

}

function SQUIDCONF(){
	$nodeid=$_POST["nodeid"];
	$workingdir=dirname(__FILE__)."/ressources/logs/web/squid/$nodeid";
	@mkdir($workingdir,0777,true);
	@mkdir($workingdir,0777,true);
	$squid=new squidnodes($nodeid);
	$blk=new blackboxes($nodeid);
	$data=$squid->build();
	@file_put_contents("$workingdir/squid-block.acl","");
	

	$DamonsSettings=base64_encode(serialize($blk->DumpSettings()));
	
	writelogs("Writing $workingdir/squid.conf",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("$workingdir/squid.conf", $data);
	writelogs("saving $workingdir/DaemonSettings.conf",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("$workingdir/DaemonSettings.conf", $DamonsSettings);
	if(!is_file("$workingdir/squid.conf")){writelogs("$workingdir/squid.conf no such file",__FUNCTION__,__FILE__,__LINE__);return;}
	@file_put_contents("$workingdir/squid.db", $globalConfig);
	compress("$workingdir/squid.conf","$workingdir/squid.conf.gz");
	compress("$workingdir/squid.db","$workingdir/squid.db.gz");
	compress("$workingdir/squid-block.acl","$workingdir/squid-block.acl.gz");
	compress("$workingdir/squid-block.acl","$workingdir/squid-block.acl.gz");
	compress("$workingdir/DaemonSettings.conf","$workingdir/DaemonSettings.conf.gz");
	
}

function LATEST_ARTICA_VERSION(){
	$f=new blackboxes();
	echo "<SUCCESS>".$f->last_available_version()."</SUCCESS>";
	
}

function LATEST_SQUID_VERSION(){
	$ARCH=$_POST["ARCH"];
	$f=new blackboxes();
	if($ARCH==32){
		$ver=$f->last_available_squidx32_version();
	}
	
	if($ARCH==64){
		$ver=$f->last_available_squidx64_version();
	}
	
	if($ARCH=="i386"){
		$ver=$f->last_available_squidx32_version();
	}
	
	if($ARCH=="x64"){
		$ver=$f->last_available_squidx64_version();
	}	
	writelogs("Arch:$ARCH; Version: $ver",__FUNCTION__,__FILE__,__LINE__);
	echo "<SUCCESS>".$f->last_available_squidx64_version()."</SUCCESS>";
		return;	
	
}


function compress($source,$dest){
    writelogs("Compress $source -> $dest ",__FUNCTION__,__FILE__,__LINE__);
    $mode='wb9';
    $error=false;
    $fp_out=gzopen($dest,$mode);
    if(!$fp_out){
    	writelogs("Failed to open $dest",__FUNCTION__,__FILE__,__LINE__);
    	return;
    }
    $fp_in=fopen($source,'rb');
    if(!$fp_in){
    	writelogs("Failed to open $source",__FUNCTION__,__FILE__,__LINE__);
    	return;
    }
    
    while(!feof($fp_in)){
    	gzwrite($fp_out,fread($fp_in,1024*512));
    }
    fclose($fp_in);
    gzclose($fp_out);
	return true;
}

function OPENSYSLOG(){
	$sock=new sockets();
	$sock->SET_INFO("ActAsASyslogServer", "1");
	$sock->SET_INFO("DisableArticaProxyStatistics", "0");
	$sock->getFrameWork("cmd.php?syslog-master-mode=yes");
	$sock->getFrameWork("squid.php?squid-reconfigure=yes");
	$sock->REST_API("/proxy/schedule/apply");
	echo "\n<RESULTS>OK</RESULTS>\n";
}

function stats_appliance_comptability(){
	$f=array();
	$users=new usersMenus();
	$sock=new sockets();
	$APP_SQUID_DB=true;
	$APP_SYSLOG_DB=true;
	$sock=new sockets();
	
	$APP_SQUIDDB_INSTALLED=trim($sock->getFrameWork("squid.php?IS_APP_SQUIDDB_INSTALLED=yes"));
	if($GLOBALS["VERBOSE"]){echo "<H1>APP_SQUIDDB_INSTALLED = `$APP_SQUIDDB_INSTALLED`</H1>\n";}
	
	if($APP_SQUIDDB_INSTALLED<>"TRUE"){
		$f[]="Token APP_SQUIDDB_INSTALLED return false";
		$APP_SQUID_DB=false;
	}
	
	
	$ProxyUseArticaDB=$sock->GET_INFO("ProxyUseArticaDB");
	if(!is_numeric($ProxyUseArticaDB)){$ProxyUseArticaDB=0;}
	if($ProxyUseArticaDB==0){
		$sock->SET_INFO("ProxyUseArticaDB",1);
		$APP_SQUID_DB=true;
	
	}
	$sock->SET_INFO("DisableArticaProxyStatistics", 0);
	$array["APP_SQUID_DB"]=$APP_SQUID_DB;
	
	
	if($_GET["AS_DISCONNECTED"]==1){
		$credentials=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["creds"]);
		$ldap=new clladp();
		$array["DETAILS"][]="Manager: {$credentials["MANAGER"]}";
		if($ldap->ldap_admin<>$credentials["MANAGER"]){
			$array["APP_CREDS"]=false;
			$array["DETAILS"][]="Account mismatch..";
			echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
			return;
		}
		if($ldap->ldap_password<>$credentials["PASSWORD"]){
			$array["APP_CREDS"]=false;
			$array["DETAILS"][]="Password mismatch..";
			echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
			return;
		}	

		$array["APP_CREDS"]=true;
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
		
	}
	
	
	
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	$EnableMySQLSyslogWizard=$sock->GET_INFO("EnableMySQLSyslogWizard");
	if(!is_numeric($EnableMySQLSyslogWizard)){$EnableMySQLSyslogWizard=0;}
	$EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}
	if($EnableSyslogDB==0){
		$MySQLSyslogWorkDir=$sock->GET_INFO("MySQLSyslogWorkDir");
		if($MySQLSyslogWorkDir==null){$MySQLSyslogWorkDir="/home/syslogsdb";}
		$TuningParameters=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSyslogParams"));
		
		if(!is_numeric($TuningParameters["ListenPort"])){$TuningParameters["ListenPort"]=0;}
		if($TuningParameters["ListenPort"]==0){$TuningParameters["ListenPort"]=rand(12500, 36500);}
		$sock->SET_INFO("EnableMySQLSyslogWizard", 1);
		$sock->SET_INFO("EnableSyslogDB", 1);
		$sock->SET_INFO("MySQLSyslogType",1);
		$sock->SaveConfigFile(base64_encode(serialize($TuningParameters)), "MySQLSyslogParams");
		$sock->getFrameWork("system.php?syslogdb-restart=yes");
		$sock->getFrameWork("cmd.php?restart-artica-status=yes");
		$APP_SYSLOG_DB=true;
	}
	
	
	$datas=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardConfig"));
	$datas["tcpsockets"]=1;
	$datas["listen_port"]=3977;
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ufdbguardConfig");

	
	
	$array["APP_SYSLOG_DB"]=$APP_SYSLOG_DB;
	$array["DETAILS"]=$f;
	echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
	
}

function stats_appliance_ports(){
	$sock=new sockets();
	$TuningParameters=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSyslogParams"));
	$ListenPort=$TuningParameters["ListenPort"];
	if(!is_numeric($ListenPort)){$ListenPort=0;}
	if($ListenPort==0){
		$ListenPort=rand(21500, 63000);
		$TuningParameters["ListenPort"]=$ListenPort;
		$sock->SaveConfigFile(base64_encode(serialize($TuningParameters)), "MySQLSyslogParams");
		$sock->getFrameWork("system.php?syslogdb-restart=yes");
	}
	$f["SyslogListenPort"]=$ListenPort;
	
	
	$SquidDBTuningParameters=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDBTuningParameters"));
	$ListenPort=$SquidDBTuningParameters["ListenPort"];
	if(!is_numeric($ListenPort)){$ListenPort=0;}
	if($ListenPort==0){
		$ListenPort=rand(21500, 63000);
		$SquidDBTuningParameters["ListenPort"]=$ListenPort;
		$sock->SaveConfigFile(base64_encode(serialize($SquidDBTuningParameters)), "SquidDBTuningParameters");
		$sock->getFrameWork("squid.php?artica-db-restart=yes");
	}
	
	$f["SquidDBListenPort"]=$ListenPort;
	echo "\n\n<RESULTS>".base64_encode(serialize($f))."</RESULTS>\n\n";
}

function stats_appliance_remote_port(){
	$sock=new sockets();
	$UFDB=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardConfig"));
	$UseRemoteUfdbguardService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteUfdbguardService"));
	$UFDB["tcpsockets"]=1;
	$UFDB["listen_port"]=$_GET["ufdbguardport"];
	$UFDB["listen_addr"]="all";
	$UFDB["UseRemoteUfdbguardService"]="0";
	$sock->SET_INFO("EnableUfdbGuard",1);
	$sock->SET_INFO("EnableUfdbGuard2",1);
	$sock->SET_INFO("UseRemoteUfdbguardService",0);
	$sock->SaveConfigFile(base64_encode(serialize($UFDB)),"ufdbguardConfig");	
	$sock->getFrameWork("cmd.php?restart-ufdb=yes");
	
}

function stats_appliance_privs(){
	if($GLOBALS["VERBOSE"]){echo "stats_appliance_privs():: {$_SERVER["REMOTE_ADDR"]}<br> \n";}
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$OrginalPassword=$q->mysql_password;
	$server=$_SERVER["REMOTE_ADDR"];
	$username=str_replace(".", "", $server);
	$password=md5($server);
	if($GLOBALS["VERBOSE"]){echo "USER:$username@$server and password: $password Line:".__LINE__."<br> \n";}
	writelogs("USER:$username@$server and password: $password",__FUNCTION__,__FILE__,__LINE__);
	
	
	// Enable Ufdbguard...
	$UFDB=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardConfig"));
	$UFDB["tcpsockets"]=1;
	$UFDB["listen_port"]=3977;
	$UFDB["listen_addr"]="all";
	$UFDB["UseRemoteUfdbguardService"]="0";
	$sock->SET_INFO("EnableUfdbGuard",1);
	$sock->SET_INFO("EnableUfdbGuard2",1);
	$sock->SET_INFO("UseRemoteUfdbguardService",0);
	
	
	
	$sock->SaveConfigFile(base64_encode(serialize($UFDB)),"ufdbguardConfig");
	//
	
	
	if(!$q->GRANT_PRIVS($server,$username,$password)){
		$array["ERROR"]=$q->mysql_error;
		if($GLOBALS["VERBOSE"]){echo "stats_appliance_privs():: MySQL Error line: ".__LINE__." $q->mysql_error<br> \n";}
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
	}

	
		
$q=new mysql_storelogs();
	if(!$q->GRANT_PRIVS($server,$username,$password)){
			$array["ERROR"]=$q->mysql_error;
			echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
			return;
		}

$array["mysql"]["username"]=$username;
$array["mysql"]["password"]=$password;
if($GLOBALS["VERBOSE"]){print_r($array);}
$sock->getFrameWork("cmd.php?restart-ufdb=yes");
$sock->getFrameWork("cmd.php?squidnewbee=yes");
echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";

}

function ucarp_step1(){
	$MyEth=null;
    $SEND_SETTING=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["ucarp"]);
	if(!is_array($SEND_SETTING)){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{corrupted_parameters}";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."<br>Not an array()</RESULTS>\n\n";
		return;
	}
	
	$second_ipaddr=$SEND_SETTING["SLAVE"];
	$ip=new IP();
	if(!$ip->isValid($second_ipaddr)){
		$array["ERROR"]=true;
        foreach ($SEND_SETTING as $a=>$b){
			$f[]="<strong>$a = $b</strong><br>";
		}

		$array["ERROR_SHOW"]="{corrupted_parameters}: {ipaddr}: $second_ipaddr<br>".@implode("\n", $f);
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
	}
	
	$ip=new networking();

    foreach ($ip->array_TCP as $eth=>$cip){
		if($cip==null){continue;}
		if($cip==$second_ipaddr){
			$MyEth=$eth;
		}
	}
	if($MyEth==null){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{corrupted_parameters}: {ipaddr}: $second_ipaddr {cannot_found_interface}";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
		
	}
	$array["ERROR"]=false;
	$array["ERROR_SHOW"]="{interface}:$MyEth";
	echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
}
function ucarp_step2(){
	$MyEth=null;
	$SEND_SETTING=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["ucarp2"]);
	if(!is_array($SEND_SETTING)){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{corrupted_parameters}";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."<br>Not an array()</RESULTS>\n\n";
		return;
	}

	$second_ipaddr=$SEND_SETTING["SLAVE"];
	$ip=new IP();
	if(!$ip->isValid($second_ipaddr)){
		$array["ERROR"]=true;
        foreach ($SEND_SETTING  as $a=>$b){
			$f[]="<strong>$a = $b</strong><br>";
		}

		$array["ERROR_SHOW"]="{corrupted_parameters}: {ipaddr}: $second_ipaddr<br>".@implode("\n", $f);
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
	}

	$ip=new networking();
    foreach ($ip->array_TCP as $eth=>$cip){
		if($cip==null){continue;}
		if($cip==$second_ipaddr){
			$MyEth=$eth;
		}
	}
	if($MyEth==null){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{corrupted_parameters}: {ipaddr}: $second_ipaddr {cannot_found_interface}";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;

	}

	$users=new usersMenus();
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{license_error}: {this_feature_is_disabled_corp_license}";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;		
	}
	
	$nic=new system_nic($MyEth);
	if($nic->IPADDR==null){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{unconfigured_network}";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;		
	}
	
	if(isset($SEND_SETTING["first_ipaddr"])){
		$SEND_SETTING["BALANCE_IP"]=$SEND_SETTING["first_ipaddr"];
	}
	
	$nic->ucarp_enabled=1;
	$nic->ucarp_vip=$SEND_SETTING["BALANCE_IP"];
	$nic->ucarp_vid=$SEND_SETTING["ucarp_vid"];
	$nic->ucarp_master=0;
	$nic->NoReboot=true;
	if(!$nic->SaveNic()){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="Save Network in Local Database failed [".__LINE__."]<br>$nic->mysql_error<br>MySQL server will be restarted, please try again";
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?restart-mysql=yes");
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;		
	}
	$array["ERROR"]=false;
	$array["ERROR_SHOW"]="{success}";
	echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";	
}
function ucarp_step3(){
	$sock=new sockets();
	$sock->getFrameWork("network.php?reconfigure-restart=yes");
	$array["ERROR"]=false;
	$array["ERROR_SHOW"]="DONE";
	echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
	
}


function UCARP_REMOVE(){
	$sock=new sockets();
	$SEND_SETTING=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($_GET["ucarp2-remove"]);
	
	if(!is_array($SEND_SETTING)){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{corrupted_parameters}";
		echo "\n\n<RESULTS>".base64_decode(serialize($array))."<br>Not an array()</RESULTS>\n\n";
		return;
	}
	
	$second_ipaddr=$SEND_SETTING["SLAVE"];
	$ip=new IP();
	if(!$ip->isValid($second_ipaddr)){
		$array["ERROR"]=true;
        foreach ($SEND_SETTING as $a=>$b){
			$f[]="<strong>$a = $b</strong><br>";
		}
	
		$array["ERROR_SHOW"]="{corrupted_parameters}: {ipaddr}: $second_ipaddr<br>".@implode("\n", $f);
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
	}
	
	$ip=new networking();
    foreach ($ip->array_TCP as $eth=>$cip){
		if($cip==null){continue;}
		if($cip==$second_ipaddr){
			$MyEth=$eth;
		}
	}
	if($MyEth==null){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{corrupted_parameters}: {ipaddr}: $second_ipaddr {cannot_found_interface}";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
	
	}
	$nic=new system_nic($MyEth);
	if($nic->IPADDR==null){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="{unconfigured_network}";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
	}
	
	$nic->ucarp_enabled=0;
	$nic->ucarp_vip=null;
	$nic->ucarp_vid=0;
	$nic->ucarp_master=0;
	$nic->NoReboot=true;
	if(!$nic->SaveNic()){
		$array["ERROR"]=true;
		$array["ERROR_SHOW"]="Save in Database failed";
		echo "\n\n<RESULTS>".base64_encode(serialize($array))."</RESULTS>\n\n";
		return;
	}
	
	echo "\n<RESULTS>SUCCESS</RESULTS>";
	$data=base64_decode($sock->getFrameWork("network.php?ucarp-down=$MyEth&master={$_SERVER["REMOTE_ADDR"]}"));
	$sock->getFrameWork("network.php?reconfigure-restart=yes");
	
	
}

function UCARP_DOWN(){
	$sock=new sockets();
	$data=base64_decode($sock->getFrameWork("network.php?ucarp-down={$_POST["UCARP_DOWN"]}&master={$_SERVER["REMOTE_ADDR"]}"));
	echo "\n<RESULTS>$data</RESULTS>";
	
}

function squid_admin_mysql_mysql($severity, $subject, $text,$function,$file,$line){
	$zdate=time();
	$q2=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $text=str_replace("'","`",$text);
    $subject=$q2->sqlite_escape_string2($subject);
    $text=$q2->sqlite_escape_string2($text);


	$file=basename($file);
    $q2->QUERY_SQL("INSERT OR IGNORE INTO `squid_admin_mysql`
			(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES
			('$zdate','$text','$subject','$function','$file','$line','$severity')","artica_events");
    if(!$q2->ok){writelogs("SQL ERROR $q2->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
	
}


function elkMaster(){

    $hostname=$GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?hostname-g=yes");
    $node=$_GET["node"];

    if($node==null){
        echo "<ERROR>Node is null</ERROR>";return;
    }

    $ElasticsearchTransportPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticsearchTransportPort"));
    $ElasticSearchClusterLists=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ElasticSearchClusterLists");
    $ClusterGroupName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterGroupName"));
    $zElasticSearchClusterLists=explode(",",$ElasticSearchClusterLists);
    foreach ($zElasticSearchClusterLists as $cluster){
        if(trim($cluster)==null){continue;}
        $ARRAY[$cluster]=$cluster;
    }

    echo "
    <transportport>$ElasticsearchTransportPort</transportport>\n
    <hostname>$hostname</hostname>\n
    <OKNODES>".serialize($ARRAY)."</OKNODES>\n<NODESNUM>".count($ARRAY)."</NODESNUM>\n<GROUP>$ClusterGroupName</GROUP>";

    $ARRAY[$node]=$node;

    foreach ($ARRAY as $comp=>$none){
        $znew[]=$comp;

    }
    $sock=new sockets();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ElasticSearchClusterLists",@implode(",",$znew));
    $sock->getFrameWork("elasticsearch.php?reload=yes");

}

