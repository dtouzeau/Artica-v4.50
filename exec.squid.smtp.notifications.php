<?php
$GLOBALS["YESCGROUP"]=true;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
$BASEDIR="/usr/share/artica-postfix";
$GLOBALS["PROGRESS"]=false;
include_once($BASEDIR. '/ressources/class.users.menus.inc');
include_once($BASEDIR. '/ressources/class.sockets.inc');
include_once($BASEDIR. '/framework/class.unix.inc');
include_once($BASEDIR. '/framework/frame.class.inc');
include_once($BASEDIR. '/ressources/class.iptables-chains.inc');
include_once($BASEDIR. '/ressources/class.mysql.haproxy.builder.php');
include_once($BASEDIR. "/ressources/class.mysql.squid.builder.php");
include_once($BASEDIR. "/ressources/class.mysql.builder.inc");
include_once($BASEDIR. "/ressources/smtp/class.smtp.loader.inc");
include_once($BASEDIR. "/ressources/class.mysql.catz.inc");
include_once($BASEDIR. '/ressources/class.mail.inc');


exec("/usr/bin/pgrep -f /usr/sbin/cron 2>&1",$results);
if(count($results)>15){
    $unix=new unix();
    $unix->ToSyslog("Too much cron processes (". count($results).") aborting","CRON");
    die();
}

if(isset($argv[1])){
    if($argv[1]=="--ufdb"){ufdbguard_notifs_check();exit;}
}

if(!is_dir("/home/artica/ufdb/catnotifs")){die();}
ufdbguard_notifs_check();


function ufdbguard_notifs_check(){

    $BaseWorkDir="/home/artica/ufdb/catnotifs";
    if(!is_dir($BaseWorkDir)){return false;}

    $UfdbguardSMTPNotifs = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbguardSMTPNotifs"));
    if(!isset($UfdbguardSMTPNotifs["ENABLED"])){$UfdbguardSMTPNotifs["ENABLED"]=0;}
    $text=array();
    $MAIN["NOTIFY_CATEGORY"]=array();

    $q = new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $results=$q->QUERY_SQL("SELECT * FROM webfilter_notifications");

    foreach ($results as $ligne){
        if(intval($ligne["category"])==0){continue;}
        $MAIN["NOTIFY_CATEGORY"][$ligne["category"]]=True;
    }


    $catz=new mysql_catz();

    events_smtp("Scanning $BaseWorkDir",__FUNCTION__,__LINE__);
    if (!$handle = opendir($BaseWorkDir)) {events_smtp("$BaseWorkDir handle failed",__FUNCTION__,__LINE__);return;}

    while (false !== ($filename = readdir($handle))) {
        if ($filename == ".") { continue; }
        if ($filename == "..") { continue; }
        $targetFile = "$BaseWorkDir/$filename";
        $SUPER_ARRAY = unserialize(@file_get_contents($targetFile));
        @unlink($targetFile);
        if($UfdbguardSMTPNotifs["ENABLED"]==0){continue;}
        if(count($MAIN["NOTIFY_CATEGORY"])==0){continue;}


        $DATE = $SUPER_ARRAY["DATE"];
        $USER = $SUPER_ARRAY["USER"];
        $IPADDR = $SUPER_ARRAY["LOCAL_IP"];
        $rulename = $SUPER_ARRAY["RULENAME"];
        $category = $SUPER_ARRAY["CATEGORY"];
        $SITENAME = $SUPER_ARRAY["SITENAME"];
        if (preg_match("#P([0-9]+)#", $category, $re)) {
            $category = $re[1];
        }

         if(!isset($MAIN["NOTIFY_CATEGORY"][$category])){continue;}

        $categoryname=$catz->CategoryIntToStr($category);
        $text[] = date("Y-m-d H:i:s",$DATE) . " BLOCK $SITENAME FROM $USER/$IPADDR Category:$categoryname Rule: $rulename";

    }

    $Count=count($text);
    if($Count>0) {
        $subject = "Webfiltering: $Count Blocked Requests";
        squid_admin_mysql(0,$subject, @implode("\n", $text),"webfilter-notifications",__LINE__);
    }
}






function removeall(){
	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/squid_admin_notifs";
    if(is_dir($BaseWorkDir)) {
        if (!$handle = opendir($BaseWorkDir)) {
            return;
        }
        while (false !== ($filename = readdir($handle))) {
            if ($filename == ".") {
                continue;
            }
            if ($filename == "..") {
                continue;
            }
            $targetFile = "$BaseWorkDir/$filename";
            @unlink($targetFile);
        }
    }
    $BaseWorkDir="/home/artica/ufdb/catnotifs";
	if(is_dir($BaseWorkDir)) {
        if (!$handle = opendir($BaseWorkDir)) {
            return;
        }
        while (false !== ($filename = readdir($handle))) {
            if ($filename == ".") {
                continue;
            }
            if ($filename == "..") {
                continue;
            }
            $targetFile = "$BaseWorkDir/$filename";
            @unlink($targetFile);
        }
    }

	$BaseWorkDir="/home/artica/ufdb/catnotifs-failed";
    if(is_dir($BaseWorkDir)) {
        if (!$handle = opendir($BaseWorkDir)) {
            return;
        }
        while (false !== ($filename = readdir($handle))) {
            if ($filename == ".") {
                continue;
            }
            if ($filename == "..") {
                continue;
            }
            $targetFile = "$BaseWorkDir/$filename";
            @unlink($targetFile);
        }
    }

}

function events_smtp($text,$function,$line){
	$logFile="/var/log/artica.squid.smtp.log";
	$maxSize=900000;
	if(!is_file($logFile)){@touch($logFile);}
	
	$size=@filesize($logFile);
	if($size>$maxSize){
		@unlink($logFile);
		@touch($logFile);
		@chmod($logFile, 0777);
	}
	
	$h = @fopen($logFile, 'a');
	$line=date("Y-m-d H:i:s")." ".getmypid()."] $text ($function/$line)\n";
	@fwrite($h,$line);
	@fclose($h);
	
}




function build_progress($pourc,$text){
	if(!$GLOBALS["PROGRESS"]){return;}
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/squid.watchdpg.smtp.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);

}





function GetRemoteIP(){
	if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
	if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
	if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
	return $IPADDR;
}

