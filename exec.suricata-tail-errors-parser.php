#!/usr/bin/php -q
<?php
if(isset($argv[1])){if($argv[1]=="--bycron"){exit();}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
$GLOBALS["COUNT"]=0;
$GLOBALS["VERBOSE"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

xstart();
function xstart(){
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/exec.suricata-tail.ERROR.php.pid";
	$pidTime="/etc/artica-postfix/pids/exec.suricata-tail.ERROR.php.time";
	@mkdir("/etc/artica-postfix/pids",0755,true);
	$pid=@file_get_contents($pidfile);
	
	
	if($unix->process_exists($pid)){exit();}
	
	$pid=getmypid();
	@file_put_contents($pidfile, getmypid());
	
	$pidExec=$unix->file_time_min($pidTime);
	if($pidExec<30){exit();}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	
	$BaseWorkDir="/home/artica/suricata-tail/errors";
	
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {return;}
	
	$q=new postgres_sql();
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if(is_dir($targetFile)){continue;}
		$time=$unix->file_time_min($targetFile);
		if($time>960){
			if($GLOBALS["VERBOSE"]){echo "$targetFile -> {$time}Mn DELETE\n";}
			@unlink($targetFile);continue;}
		$sql=@file_get_contents($targetFile);
		$q->QUERY_SQL($sql);
		
		if(!$q->ok){
			if(preg_match("#is out of range for type#",$postgres->mysql_error)){
				if($GLOBALS["VERBOSE"]){echo "$targetFile -> is out of range for type DELETE\n";}
				@unlink($targetFile);}
			if(preg_match("#invalid input syntax for integer#",$postgres->mysql_error)){
				if($GLOBALS["VERBOSE"]){echo "$targetFile -> invalid input syntax for integer DELETE\n";}
				@unlink($targetFile);}
				
				
				if($GLOBALS["VERBOSE"]){echo "$targetFile -> $postgres->mysql_error\n";}
			continue;
		}
		if($GLOBALS["VERBOSE"]){echo "$targetFile -> OK DELETE\n";}
		@unlink($targetFile);
		
		
	}
	
	
}	
?>