<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;



$GLOBALS["SETTINGS"][]="EnableMilterRegex";
$GLOBALS["SETTINGS"][]="MilterGreyListEnabled";
$GLOBALS["SETTINGS"][]="MimeDefangEnabled";
$GLOBALS["SETTINGS"][]="NTPDEnabled";
$GLOBALS["SETTINGS"][]="NTPDUseSpecifiedServers";
$GLOBALS["SETTINGS"][]="NTPClientDefaultServerList";
$GLOBALS["SETTINGS"][]="CurlUserAgent";
$GLOBALS["SETTINGS"][]="ArticaProxySettings";

if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
ini_set('memory_limit','1000M');
$GLOBALS["PROGRESS"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.backup.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');

include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
include_once(dirname(__FILE__)."/ressources/class.snapshots.blacklists.inc");
include_once(dirname(__FILE__)."/ressources/externals/class.aesCrypt.inc");

$GLOBALS["NOSTATUS"]=false;

$GLOBALS["COMMANDLINE"]=@implode(" ", $argv);
$GLOBALS["NOT_RESTORE_NETWORK"]=false;
$GLOBALS["SEND_META"]=false;
$GLOBALS["SNAPSHOT_NO_DELETE"]=false;
$GLOBALS["SNAPSHOT_NO_MYSQL"]=false;
if(preg_match("#--command=(.+?);#",$GLOBALS["COMMANDLINE"],$re)){$GLOBALS["COMMAND"]=$re[1];}
if(preg_match("#--verbose#",$GLOBALS["COMMANDLINE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--progress#",$GLOBALS["COMMANDLINE"])){$GLOBALS["PROGRESS"]=true;$GLOBALS["COMMAND"]="cluster-snapshot";}
if(isset($argv[1])) {
    if ($argv[1] == "--xnotify") {
        export_cluster();
        exit;
    }
    if ($argv[1] == "--install") {
        cluster_install();
        exit;
    }
    if ($argv[1] == "--uninstall") {
        cluster_uninstall();
        exit;
    }
}

export_cluster();

function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/artica.cluster.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function progress($pourc,$text){
	build_progress($pourc,$text);
}


function cluster_install(){

    build_progress(50,"{installing}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSEnableClusterMaster",1);
    build_progress(100,"{installing} {done}");

}
function cluster_uninstall(){
    build_progress(50,"{uninstalling}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSEnableClusterMaster",0);
    export_cluster_remove_files();
    build_progress(100,"{installing} {done}");

}


function export_cluster(){


}

function events($subject,$text=null,$line=0){
    $crit=2;
    if(preg_match("#(fatal|error|skip)#",$subject)){$crit=0;}
    cluster_events($crit,"Cluster: $subject",$text,__LINE__);

}



function cluster_syslog($subject,$content=null){
    $file="cluster-master";
    if(function_exists('syslog')){
        openlog($file, LOG_PID | LOG_PERROR, LOG_LOCAL0);
        syslog(LOG_INFO, "$subject $content");
        closelog();
    }
}

function Cluster_clean(){

    $date = strtotime("-7 day");
    $q=new lib_sqlite("/home/artica/SQLITE/clusters_events.db");
    $q->QUERY_SQL("DELETE FROM events WHERE zdate < '$date'");
    if(!$q->ok){
        cluster_events(0,"MySQL error",$q->mysql_error,__LINE__);
    }
}

function cluster_events($prio,$subject,$content,$line=0){


    cluster_syslog($subject,$content);

    $q=new lib_sqlite("/home/artica/SQLITE/clusters_events.db");
    $sql="CREATE TABLE IF NOT EXISTS `events` (
    `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
    `prio` INTEGER NOT NULL DEFAULT 2,
	`zdate` INTEGER,
	`sent` INTEGER NOT NULL DEFAULT 0,
	`subject` TEXT,
	`content` TEXT,
	`info` TEXT ) ";

    $q->QUERY_SQL($sql);
    $time=time();
    $info="Line ".$line ." file:".basename(__FILE__);
    $sql="INSERT INTO events (zdate,prio,sent,subject,content,info) VALUES('$time',$prio,0,'$subject','$content','$info');";
    $q->QUERY_SQL($sql);

}


