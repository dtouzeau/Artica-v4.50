<?php
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){
    ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
    $GLOBALS["OUTPUT"]=true;}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.virustotal.inc");
include_once(dirname(__FILE__).  "/ressources/smtp/class.smtp.loader.inc");
include_once(dirname(__FILE__).'/ressources/class.mime.parser.inc');
include_once(dirname(__FILE__).'/ressources/class.rfc822.addresses.inc');
include_once(dirname(__FILE__).'/ressources/class.maillog.tools.inc');
$EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
if($EnablePostfix==0){die();}

//$GLOBALS["VERBOSE"]=true;

if($argv[1]=="--parse"){print_r(parse($argv[2]));exit;}
if($argv[1]=="--phishtank"){phishing_initiative($argv[2]);exit;}
if($argv[1]=="--virtotla"){articatech($argv[2]);exit;}
if($argv[1]=="--resend"){resend_urls_message($argv[2]);exit;}
if($argv[1]=="--back"){xbackups($argv[2]);exit;}
if($argv[1]=="--cron"){xCron();exit;}
if($argv[1]=="--xstart"){xstart();exit;}



xbackups();
xCron();
xstart();


function xbackups(){

    $postgres=new postgres_sql();
    $postgres->SMTP_TABLES();
    $storage_path="/var/spool/MIMEDefang/BACKUP";
    $postgres=new postgres_sql();
    $postgres->SMTP_TABLES();


    if ($handle = opendir($storage_path)) {
        while (false !== ($file = readdir($handle))) {
            if ($file == "." OR $file == "..") {continue;}
            if(substr($file, 0,1)=='.'){if ($GLOBALS["OUTPUT"]){echo "skipped: `$file`\n";}continue;}
            if(!preg_match("#.BAK$#", $file)){continue;}
            $path="$storage_path/$file";
            xbackup_file($path);
        }
    }

}

function xbackup_file($filepath){
    $unix=new unix();
    if ($GLOBALS["OUTPUT"]) {echo "Import $filepath\n";}
    $sock=new sockets();
    $dirname = dirname($filepath);
    $filename = basename($filepath);
    $msgid = str_replace(".BAK", "", $filename);
    $fileheader=$filepath;
    $filecontent = $dirname . "/" . str_replace(".BAK", ".backup", $filename);
    $filecontenGz = $dirname . "/" . str_replace(".BAK", ".gz", $filename);

    echo "Message Path..................: $filecontent\n";
    echo "Compressed Path...............: $filecontenGz\n";
    echo "Headers Path..................: $fileheader\n";

    if (!is_file($filecontent)) {
        echo "$filecontent no such file\n";
        @unlink($filepath);
        return true;
    }
    if (!is_file($fileheader)) {
        echo "$filecontent no such file\n";
        @unlink($filepath);
        return true;
    }


    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT id FROM mimedefang_urls WHERE msgid='$msgid'");
    if(intval($ligne["id"])==0){
        @unlink($filecontent);
        @unlink($fileheader);
        return false;
    }

    $last_modified = filemtime($filepath);
    $MimedefangUrlsCheckerMaxTTL=intval($sock->GET_INFO("MimedefangUrlsCheckerMaxTTL"));
    if($MimedefangUrlsCheckerMaxTTL==0){$MimedefangUrlsCheckerMaxTTL=604800;}
    $ttl_max=strtotime("+$MimedefangUrlsCheckerMaxTTL minutes", $last_modified);
    $filecontent_size=filesize($filecontent);
    echo "Compress $filecontent --> $filecontenGz\n";
    if($unix->compress($filecontent,$filecontenGz)){
        @unlink($filecontent);
    }else{
        echo "Compress failed\n";
        return;
    }
    echo "$filecontenGz ".filesize($filecontenGz)."bytes\n";

    $message=base64_encode(@file_get_contents($filecontenGz));
    $infos=@file_get_contents($fileheader);

    echo "Message ".strlen($message)."bytes\n";
    echo "INfos ".strlen($fileheader)."bytes\n";

    $sql="INSERT INTO mimedefang_msgurls (zdate,ttlmax,msgid,message,infos,size)
    VALUES (NOW(),'$ttl_max','$msgid','$message','$infos','$filecontent_size')";

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";return false;}

    @unlink($fileheader);
    @unlink($filecontenGz);

    return true;


}

function xCron(){

    $q=new postgres_sql();
    if(!$q->FIELD_EXISTS("mimedefang_urls","affected")){
        $q->QUERY_SQL("ALTER TABLE mimedefang_urls ADD affected smallint DEFAULT 0");
    }
    $sock=new sockets();
    $sql="SELECT id,zdate FROM mimedefang_urls WHERE scanned=0 AND affected=0";
    $results=$q->QUERY_SQL($sql);
    $MimedefangUrlsCheckerMinTTL=intval($sock->GET_INFO("MimedefangUrlsCheckerMinTTL"));
    $MimedefangUrlsCheckerMaxTTL=intval($sock->GET_INFO("MimedefangUrlsCheckerMaxTTL"));
    if($MimedefangUrlsCheckerMinTTL==0){$MimedefangUrlsCheckerMinTTL=30;}
    if($MimedefangUrlsCheckerMaxTTL==0){$MimedefangUrlsCheckerMaxTTL=604800;}
    echo "Parsing results....\n";
    while ($ligne = pg_fetch_assoc($results)) {
        $id=$ligne["id"];
        $ttl_min=strtotime("+$MimedefangUrlsCheckerMinTTL minutes");
        $ttl_max=strtotime("+$MimedefangUrlsCheckerMaxTTL minutes");

        if ($GLOBALS["OUTPUT"]){
            echo "$id]: ADDING $MimedefangUrlsCheckerMinTTL minutes ($ttl_min) for id: $id\n";
            echo "$id]: Next scan in ". distanceOfTimeInWords(time(),$ttl_min)." --> ".date("Y-m-d H:i:s",$ttl_min)." Current ".date("Y-m-d H:i:s")."\n";

        }
        $q->QUERY_SQL("UPDATE mimedefang_urls SET affected=1,ttlmin=$ttl_min,ttlmax=$ttl_max WHERE id=$id");
    }


}

function xstart(){

    $q=new postgres_sql();

    $time=time();
    $sql="SELECT * FROM mimedefang_urls WHERE scanned=0 AND affected=1 AND $time > ttlmin ";
    $results=$q->QUERY_SQL($sql);
    echo "Scanned = 0 and $time > ttlmin = ".pg_num_rows($results)."\n";

    while ($ligne = pg_fetch_assoc($results)) {
        $id = $ligne["id"];
        $GLOBALS["LOGS"]=array();
        $msgid=$ligne["msgid"];
        $ttlmin=intval($ligne["ttlmin"]);
        if ($GLOBALS["OUTPUT"]) { echo "URL ID $id message ID: $msgid ttlmin=$ttlmin --> ".date("Y-m-d H:i:s")."\n";}
        if($ttlmin>time()){
            echo "URL ID $id message ID: $msgid need to wait....\n";
            continue;
        }
        echo "URL ID $id message ID: $msgid\n";

        $urldest=$ligne["urldest"];
        if(trim($urldest)==null) {
            if ($GLOBALS["OUTPUT"]) {echo "ORDER TO PARSE {$ligne["urlsource"]}\n";}
            $ARRAY = parse($ligne["urlsource"]);
            $content_type = $ARRAY["CONTENT_TYPE"];
            $familysite = $ARRAY["FAMILYSITE"];
            $MAIN_URI=$ARRAY["MAIN_URI"];
            $q->QUERY_SQL("UPDATE mimedefang_urls SET urldest='$MAIN_URI',content_type='$content_type',familysite='$familysite' WHERE id=$id");
        }else{
            $MAIN_URI=$urldest;
            $content_type=$ligne["content_type"];
            $familysite=$ligne["familysite"];
        }



        echo "URL $MAIN_URI ID $id message ID: $msgid Content-Type: $content_type\n";


        if($content_type=="HTML" OR $content_type=="TIMEOUT") {

            echo "--> Phishing Initiative\n";

            if(phishing_initiative($MAIN_URI)){
                update_phishing($id,$familysite,$msgid);
                $GLOBALS["MESSAGE_ID_SCANNED"][$msgid]=true;
                continue;
            }

            echo "--> Virus Total\n";
            if(virustotal($MAIN_URI)){
                update_phishing($id,$familysite,$msgid);
                $GLOBALS["MESSAGE_ID_SCANNED"][$msgid]=true;
                continue;

            }

            if(articatech($MAIN_URI)){
                update_phishing($id,$familysite,$msgid);
                $GLOBALS["MESSAGE_ID_SCANNED"][$msgid]=true;
                continue;

            }

            update_scanned($id);
            continue;
        }

        if($content_type=="application/xml"){
            update_scanned($id);
            continue;
        }


    }


    $q->QUERY_SQL("DELETE FROM mimedefang_urls WHERE ttlmax <".time());
    $q->QUERY_SQL("DELETE FROM mimedefang_msgurls WHERE ttlmax <".time());

}

function virustotal($url){
    $positives=0;
    $sock=new sockets();
    $MimedefangVirusTotalAPIKey=trim($sock->GET_INFO("MimedefangVirusTotalAPIKey"));
    if($MimedefangVirusTotalAPIKey==null){return false;}

    $MimedefangUrlsCheckerTimeOut=intval($sock->GET_INFO("MimedefangUrlsCheckerTimeOut"));
    if($MimedefangUrlsCheckerTimeOut==0){$MimedefangUrlsCheckerTimeOut=5;}

    $virustotal=new VirusTotalAPIV2($MimedefangVirusTotalAPIKey);
    $result = $virustotal->getURLReport($url);

    try{
        $positives=intval($result->positives);
    } catch(Exception $e){
        echo $e->getMessage();
    }

    if($positives>0){
        $GLOBALS["LOGS"][]="$url: Detected by Virus Total $positives positive(s)!";
        return true;
    }

    $GLOBALS["LOGS"][]="Virus Total $positives positive";

    return false;


}

function update_scanned($id){
    $q=new postgres_sql();
    $logs=base64_encode(@implode("\n", $GLOBALS["LOGS"]));
    $q->QUERY_SQL("UPDATE mimedefang_urls 
      SET scanned=1,log='$logs' WHERE id='$id'");



}
function update_phishing($id,$familysite,$msgid){
    $sock=new sockets();
    $q=new postgres_sql();


    $ligne=$q->mysqli_fetch_array("SELECT * FROM mimedefang_urls WHERE id=$id");
    $logs=base64_encode(serialize($GLOBALS["LOGS"]));
    $q->QUERY_SQL("UPDATE mimedefang_urls SET log='$logs', phishing=1, scanned=1 WHERE id='$id'");

    if(!$q->ok){echo $q->mysql_error."\n";}

    if(strlen($msgid)<3){return;}

    $sql="SELECT * FROM smtplog WHERE msgid='$msgid' ORDER BY zdate";
    $results=$q->QUERY_SQL($sql);
    $fromdomain=null;
    $tomail=null;
    $ipaddr=null;
    $subject=null;
    $frommail=null;
    while ($ligne = pg_fetch_assoc($results)) {
        if($fromdomain==null) {
            if (trim($ligne["fromdomain"]) <> null) { $fromdomain = $ligne["fromdomain"]; $frommail = $ligne["frommail"];}
        }

        if($tomail==null) {
            if (trim($ligne["tomail"]) <> null) {$tomail = $ligne["tomail"];$todomain = $ligne["todomain"];}
        }

        if($ipaddr==null){
            if (trim($ligne["ipaddr"]) <> null) {$ipaddr=$ligne["ipaddr"];}
        }

        if($subject==null){
            if (trim($ligne["subject"]) <> null) {$subject=$ligne["subject"];}
        }


    }

    $subject=pg_escape_string2($subject);

    $sql="INSERT INTO smtplog (zdate,msgid,frommail,fromdomain,tomail,todomain,ipaddr,infected,reason, subject,sent ) VALUES (NOW(),'$msgid','$frommail','$fromdomain','$tomail','$todomain','$ipaddr',1,'Phishing','$subject',1)";

    $q->QUERY_SQL($sql);
    


}

function parse($MAIN_URI){

    $fam=new familysite();
    if ($GLOBALS["OUTPUT"]){echo "parse($MAIN_URI)...\n";}

    $ARRAY["MAIN_URI"]=getRealUrl($MAIN_URI);
    $ARRAY["CONTENT_TYPE"]=GetContentType($ARRAY["MAIN_URI"]);
    $ARRAY["FAMILYSITE"]=$fam->GetFamilySites($ARRAY["MAIN_URI"]);
    return $ARRAY;

}

function GetContentType($MAIN_URI){
    $curl=new ccurl($MAIN_URI);
    $sock=new sockets();
    $MimedefangUrlsCheckerTimeOut=intval($sock->GET_INFO("MimedefangUrlsCheckerTimeOut"));
    if($MimedefangUrlsCheckerTimeOut==0){$MimedefangUrlsCheckerTimeOut=5;}
    $Infos=$curl->getHeaders($MimedefangUrlsCheckerTimeOut);

    if ($GLOBALS["OUTPUT"]){echo "GetContentType($MAIN_URI) Content-Type:{$Infos["content_type"]}\n";}


    if(intval(($Infos["http_code"]))==0){
        $GLOBALS["LOGS"][]="$MAIN_URI: TimedOut! ( defined as {$MimedefangUrlsCheckerTimeOut} seconds)";
        return "TIMEOUT";
    }
    $GLOBALS["LOGS"][]="Content-Type: {$Infos["content_type"]}";
    if(preg_match("#text.*?html#i",$Infos["content_type"])){return "HTML";}
    echo "[{$Infos["content_type"]}] No match #text.*?html#i\n";
    return $Infos["content_type"];


}

function getRealUrl($MAIN_URI){
    $curl=new ccurl($MAIN_URI);
    $sock=new sockets();
    $MimedefangUrlsCheckerTimeOut=intval($sock->GET_INFO("MimedefangUrlsCheckerTimeOut"));
    if($MimedefangUrlsCheckerTimeOut==0){$MimedefangUrlsCheckerTimeOut=5;}
    $curl->Timeout=$MimedefangUrlsCheckerTimeOut;
    $Infos=$curl->getHeaders($MimedefangUrlsCheckerTimeOut);

    if ($GLOBALS["OUTPUT"]){echo "getRealUrl: $MAIN_URI code: {$Infos["http_code"]}\n";}

    if(intval(($Infos["http_code"]))==0){
        return $MAIN_URI;
    }

    if(isset($Infos["redirect_url"])){
        $redirect=trim($Infos["redirect_url"]);
        if($redirect==null){
            $GLOBALS["LOGS"][]="$MAIN_URI no redirect...";
            return $MAIN_URI;}
        $GLOBALS["LOGS"][]="$MAIN_URI Redirected to {$Infos["redirect_url"]}";
        return getRealUrl($redirect);
    }
}

function articatech($MAIN_URI){
    $sock=new sockets();
    $MAIN_URI=urlencode($MAIN_URI);
    $url="https://categories.articatech.net/rest.mailsecurity.php?url=$MAIN_URI";
    echo $url."\n";
    $curl=new ccurl($url);
    $MimedefangUrlsCheckerTimeOut=intval($sock->GET_INFO("MimedefangUrlsCheckerTimeOut"));
    if($MimedefangUrlsCheckerTimeOut==0){$MimedefangUrlsCheckerTimeOut=5;}
    $curl->Timeout=$MimedefangUrlsCheckerTimeOut;
    if(!$curl->get()){
        return false;

    }

    $json=json_decode($curl->data);

    try{
        $category=$json->category;
        $WRONGCATZ[90]=true;
        $WRONGCATZ[109]=true;
        $WRONGCATZ[105]=true;
        $WRONGCATZ[135]=true;
        $WRONGCATZ[167]=true;
        $WRONGCATZ[195]=true;
        $WRONGCATZ[181]=true;
        $WRONGCATZ[49]=true;
        $WRONGCATZ[140]=true;
        if(isset($WRONGCATZ[$category])){
            $GLOBALS["LOGS"][]="$MAIN_URI: Detected by Artica Cloud category:$category";
            return true;}
    } catch(Exception $e){
        $GLOBALS["LOGS"][]=$e->getMessage();
    }

    return false;

}

function phishing_initiative($MAIN_URI){
    $sock=new sockets();


    $MimedefangUrlsCheckerTimeOut=intval($sock->GET_INFO("MimedefangUrlsCheckerTimeOut"));
    if($MimedefangUrlsCheckerTimeOut==0){$MimedefangUrlsCheckerTimeOut=5;}

    $MimedefangPhishingInitiativeAPIKey=trim($sock->GET_INFO("MimedefangPhishingInitiativeAPIKey"));
    if($MimedefangPhishingInitiativeAPIKey==null){
        $GLOBALS["LOGS"][]="phishing-initiative: No API Key";
        return false;}



    $ch = curl_init();
    $URL="https://phishing-initiative.fr/api/v1/urls/lookup/?url=".urlencode($MAIN_URI);
    if ($GLOBALS["OUTPUT"]){echo "$URL\n";}
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_URL, "$URL");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST,0);
    curl_setopt($ch, CURLOPT_TIMEOUT, $MimedefangUrlsCheckerTimeOut);

    echo "Use:Token $MimedefangPhishingInitiativeAPIKey\n";
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json","Authorization: Token $MimedefangPhishingInitiativeAPIKey"));

    $reply=curl_exec($ch);
    $Infos= curl_getinfo($ch);

    $header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
    $headers=substr($reply, 0, $header_size);
    $content=substr( $reply, $header_size );
    curl_close($ch);
    $headerZ=explode("\n",$headers);

    foreach ($headerZ as $line){
        if(!preg_match("#(.+?):(.+)#",$line,$re)){continue;}
        $HEADZ[trim($re[1])]=trim($re[2]);

    }


    if(intval($Infos["http_code"])<>200){
        echo "!! {$Infos["http_code"]}\n";
        $GLOBALS["LOGS"][]="phishing-initiative HTTP Error: {$Infos["http_code"]}";
        return false;
    }


    $main=json_decode($content);

    if(!$main){
        $GLOBALS["LOGS"][]="phishing-initiative In database: No";
        return false;
    }

    if (!property_exists($main, "tag")) {$main->tag=0;}
    echo "Tag: {$main->tag}\n";

    if(intval($main->tag) > 0){
        $GLOBALS["LOGS"][]="phishing-initiative In database: Yes";
        return true;
    }

    echo "phishing-initiative In database: No\n";
    $GLOBALS["LOGS"][]="phishing-initiative In database: No";
    return false;
}
function resend_urls_message($id){
        $id=intval($id);
        $unix=new unix();


        if($id==0){
            echo "ID: $id not supported\n";
            exit();
        }


        $tempfile=$unix->FILE_TEMP();
        $q=new postgres_sql();

        if ($GLOBALS["OUTPUT"]){echo "mimedefang_msgurls --> $id\n";}
        $ligne=$q->mysqli_fetch_array("SELECT * FROM mimedefang_msgurls WHERE id='$id'");
        if(!$q->ok){echo $q->mysql_error."\n";}
        $msgid=$ligne["msgid"];
        $infos=$ligne["infos"];
        $size=$ligne["size"];
        @file_put_contents("$tempfile.gz",base64_decode($ligne["message"]));
        echo "Extracting....$tempfile.gz ({$size}Bytes)\n";
        $unix->uncompress("$tempfile.gz",$tempfile);
        @unlink("$tempfile.gz");

        echo "$msgid ($infos) $tempfile\n";

        $TRB=explode("|||",$infos);
        $mailfrom=$TRB[0];
        $mailfrom=str_replace("<","",$mailfrom);
        $mailfrom=str_replace(">","",$mailfrom);

        $instance=$unix->hostname_g();
        if($mailfrom==null){$mailfrom="root@$instance";}
        echo "From: $mailfrom $msgid ($infos) $tempfile\n";



        $TargetHostname=inet_interfaces();
        if(preg_match("#all#is", $TargetHostname)){$TargetHostname="127.0.0.1";}

        $msmtp=$unix->find_program("msmtp");
        $cmd="$msmtp --host=127.0.0.1 --port=25 --domain=$instance --from=$mailfrom --read-recipients < $tempfile";

        shell_exec($cmd);
        @unlink($tempfile);

        $maillog=new maillog_tools();

        $ARRAY["MESSAGE_ID"]=$msgid;
        $ARRAY["HOSTNAME"]="localhost";
        $ARRAY["IPADDR"]="0.0.0.0";
        $ARRAY["SENDER"]=$mailfrom;
        $ARRAY["REJECTED"]="Released";
        $ARRAY["SEQUENCE"]=55;
        $maillog->berkleydb_relatime_write($msgid,$ARRAY);
}
function inet_interfaces(){
    $f=file("/etc/postfix/main.cf");
    while (list ($key, $line) = each ($f) ){
        $line=str_replace("\r\n", "", $line);
        $line=str_replace("\r", "", $line);
        $line=str_replace("\n", "", $line);
        if(preg_match("#^inet_interfaces.*?=(.*)#", $line,$re)){
            $re[1]=trim($re[1]);
            if($GLOBALS["VERBOSE"]){echo "F:$line -> `{$re[1]}`\n";}
            $inet_interfaces=trim($re[1]);
            $inet_interfaces=str_replace("\r\n", "", $inet_interfaces);
            $inet_interfaces=str_replace("\r", "", $inet_interfaces);
            $inet_interfaces=str_replace("\n", "", $inet_interfaces);


            if(strpos($inet_interfaces, ",")>0){
                $tr=explode(",",$inet_interfaces);
                if(trim($tr[0])=="all"){$tr[0]="127.0.0.1";}
                if($GLOBALS["VERBOSE"]){echo "F:$line -> `{$tr[0]}`\n";}
                return $tr[0];
            }

            if(strpos($inet_interfaces, " ")>0){
                $tr=explode(" ",$inet_interfaces);
                if(trim($tr[0])=="all"){$tr[0]="127.0.0.1";}

                if($GLOBALS["VERBOSE"]){echo "F:$line -> `{$tr[0]}`\n";}
                return $tr[0];
            }
            if($GLOBALS["VERBOSE"]){echo "F:$line -> `{$re[1]}`\n";}
            return $re[1];

        }
    }

}