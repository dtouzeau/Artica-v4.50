<?php
$GLOBALS["YESCGROUP"]=true;
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="{$GLOBALS["ARTICALOGDIR"]}"; } }


if($GLOBALS["VERBOSE"]){echo "TimeFile: $pidTime\n";}
if(isset($argv[1])) {
    if ($argv[1] == "--admin-events") {
        clean_admin_events();
        exit;
    }

    if ($argv[1] == "--mysql") {
        CleanMySQL();
        exit;
    }
}



$unix=new unix();
$pid=$unix->get_pid_from_file($pidpath);
if( $unix->process_exists($pid,basename(__FILE__) ) ){
    $mypid=getmypid();
	echo "Already executed.. PID: ". @file_get_contents($pidpath). " Me=$mypid, aborting the process\n";
	system("ps -aux|grep ". @file_get_contents($pidpath));
	exit();
}
@file_put_contents($pidpath,getmypid());
$time=$unix->file_time_min($pidTime);
if($time<120){exit();}
@unlink($pidTime);
@file_put_contents($pidTime, time());

    cleanCronD();
	CleanPostGreSQLFailed();
	CleanTinyProxy();
	CleanTempDirs();
	CleanArticaUpdateLogs();
	ParseMysqlEventsQueue();

	CleanMySQL();
	
	
	$rm=$unix->find_program("rm");
	if(is_dir(PROGRESS_DIR."/ViewTemplates")){shell_exec("$rm -f ". PROGRESS_DIR."/ViewTemplates/*");}
	
	
	exit();


function cleanCronD(){

    if (!$handle = opendir("/etc/cron.d")) {return;}
    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        $targetFile="/etc/cron.d/$filename";
        if(!is_file($targetFile)){continue;}
        @chown($targetFile,"root");
        @chmod($targetFile,0600);
    }

}
	
function CleanMySQL(){
	$sock=new sockets();
	$unix=new unix();
	$dirs=$unix->dirdir("/var/lib/mysql");
    foreach ($dirs as $directory=>$none){
		CleanMySQLBAK($directory);
		
	}
	
	
	$WORKDIR=$sock->GET_INFO("SquidStatsDatabasePath");
	if($WORKDIR==null){$WORKDIR="/opt/squidsql";}	
	if(is_dir($WORKDIR)){
		$dirs=$unix->dirdir("$WORKDIR/data");
		foreach ($dirs as $directory=>$none){
			CleanMySQLBAK($directory);
	
		}
	}
	
	
}

function CleanMySQLBAK($directory){

	$unix=new unix();
	foreach (glob("$directory/*.BAK") as $filename) {
		$time=$unix->file_time_min($filename);
		if($time<380){continue;}
		@unlink($filename);
	}


}
	
	
function CleanTempDirs(){
	$unix=new unix();
	$dirs=$unix->dirdir("/tmp");
	if(!is_array($dirs)){return null;}
	foreach ($dirs as $num=>$ligne){
		if(trim($num)==null){continue;}
		if(preg_match("#\/krb5#", $num)){continue;}
		$time=$unix->file_time_min($num);
		if($time<380){continue;}
		if(is_dir($num)){
			shell_exec("/bin/rm -rf \"$num\"");
		}
		
	}
	if (!$handle = opendir("/")) {return;}
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="/$filename";
		if(is_numeric($filename)){@unlink($targetFile);}
	}
	
	if(is_file("/usr/share/artica-postfix/ressources/exec.syslog-engine.php")){ @unlink("/usr/share/artica-postfix/ressources/exec.syslog-engine.php"); }
	
	CleanTimedFiles($unix->TEMP_DIR(),380);
	CleanTimedFiles("/tmp",680);
	CleanTimedFiles("/usr/share/artica-postfix/ressources/logs/jGrowl",240);
	
	
}

function CleanTimedFiles($directory,$maxtime){
	if(!is_dir($directory)){return;}
	if (!$handle = opendir($directory)) {return;}
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$directory/$filename";
		if(preg_match("#^krb5#", $filename)){continue;}
		if(preg_match("#\.winbindd#", $filename)){continue;}
		if(!is_file($targetFile)){continue;}
		$file_time_min=file_time_min($filename);
		if($file_time_min<$maxtime){continue;}
		@unlink($filename);
	}
	
}
function CleanPostGreSQLFailed(){
		if(!is_dir("/home/squid/PostgreSQL-failed")){return;}
		$unix=new unix();
		$c=0;
		$TimeFile="/etc/artica-postfix/pids/RemovePostgreSQLFailed.time";
		$timeINT=$unix->file_time_min($TimeFile);
		if($timeINT<60){return;}
		@unlink($TimeFile);
		@file_put_contents($TimeFile, time());
		if (!$handle = opendir("/home/squid/PostgreSQL-failed")) {return;}
		while (false !== ($filename = readdir($handle))) {
			if($filename=="."){continue;}
			if($filename==".."){continue;}
			$targetFile="/home/squid/PostgreSQL-failed/$filename";
			$xtime=$unix->file_time_min($targetFile);
			if($xtime<1440){continue;}
			$c++;
			@unlink($targetFile);
		}	
			
	if($c>0){
		squid_admin_mysql(0, "$c files as been removed from PostgreSQL-failed directory",null,__FILE__,__LINE__);
	}
}


function CleanTinyProxy(){
	if(!is_file("/etc/artica-postfix/PROXYTINY_APPLIANCE")){return;}
	$BaseWorkDirs[]="{$GLOBALS["ARTICALOGDIR"]}/squid-usersize";
	$BaseWorkDirs[]="{$GLOBALS["ARTICALOGDIR"]}/ufdbguard-queue";

    foreach ($BaseWorkDirs as $workdir){
		if(!is_dir($workdir)){return;}
		if (!$handle = opendir($workdir)) {continue;}
		$c=0;
		while (false !== ($filename = readdir($handle))) {
				if($filename=="."){continue;}
				if($filename==".."){continue;}
				$targetFile="$workdir/$filename";
				@unlink($targetFile);
				$c++;
		}		
	}
	
}

function CleanArticaUpdateLogs(){
	foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/artica-update-*.debug") as $filename) {
		$file_time_min=file_time_min($filename);
		if(file_time_min($filename)>5752){@unlink($filename);}
		}

}


function ParseMysqlEventsQueue(){
	$q=new mysql();
	foreach (glob("{$GLOBALS["ARTICALOGDIR"]}/sql-events-queue/*.sql") as $filename) {
			$sql=@file_get_contents($filename);
			$q->QUERY_SQL($sql,"artica_events");
			if($q->ok){
				@unlink($filename);
			}
		}	
	}
	
function clean_admin_events(){
	$BaseWorkDir="{$GLOBALS["ARTICALOGDIR"]}/system_admin_events";
	if (!$handle = opendir($BaseWorkDir)) {
		echo "Failed open $BaseWorkDir\n";
		return;
	}
	$c=0;
	while (false !== ($filename = readdir($handle))) {
			if($filename=="."){continue;}
			if($filename==".."){continue;}
			$targetFile="$BaseWorkDir/$filename";
			@unlink($targetFile);
			$c++;
	}
	echo "$c cleaned files\n";
}

?>