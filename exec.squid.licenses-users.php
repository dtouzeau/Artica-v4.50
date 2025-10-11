<?php
$GLOBALS["YESCGROUP"]=true;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["SCHEDULE_ID"]=0;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}



xstart();


function xstart(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($GLOBALS["VERBOSE"]){echo "cachetime:$cachetime\n";}
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){exit();}
	$TimeEx=$unix->file_time_min($cachetime);
	if(!$GLOBALS["FORCE"]){if($TimeEx<5){return;}}
	if($unix->ServerRunSince()<3){return;}
	
	if(system_is_overloaded()){exit();}
	@file_put_contents($pidfile, getmypid());
	@unlink($cachetime);@file_put_contents($cachetime, time());
	
	
	$OUTPUTDIR="/home/squid/licenses";
	@mkdir("$OUTPUTDIR",0755);
	@chown($OUTPUTDIR, "squid");
	@chgrp($OUTPUTDIR, "squid");
	
	$FILES=$unix->DirFiles("/home/squid/licenses");
	$q=new mysql_squid_builder();
	$sql="CREATE TABLE IF NOT EXISTS `lic_proxy_day` (`zdate` timestamp NOT NULL PRIMARY KEY,`users` BIGINT UNSIGNED,KEY `users`(`users`) ) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";CleanTable();return;}
	
	$f=array();
	foreach ($Files as $filename=>$none){
		$srcfile="$OUTPUTDIR/$filename";
		
		$time=$unix->file_time_min($srcfile);
		if($time>2880){@unlink($srcfile);continue;}
		
		$data=unserialize(@file_get_contents($srcfile));
		$TIME=$data["TIME"];
		$date=date("YmdH",$TIME);
		$min=date("i",$TIME);
		$minute=$min[0];
		$key=$date.$minute;
		if(strlen($minute)==1){$minute_sql=$minute."0:00";}else{$minute_sql=$minute.":00";}
		$sql_time=date("Y-m-d H",$TIME).":$minute_sql";
		
		$USERS=$data["USERS"];
		foreach ($USERS as $uid=>$count){
			if($uid=="127.0.0.1"){continue;}
			$MAIN_ARRAY[$key]["USERS"][$uid]=true;
			$MAIN_ARRAY[$key]["SQLTIME"]=$sql_time;
		}
		
		$files[]=$srcfile;
		
	
	}
	

	
	
	foreach ($MAIN_ARRAY as $key=>$array){
		$CountofUsers=count($array["USERS"]);
		$sql_time=$array["SQLTIME"];
		$n[]="('$sql_time','$CountofUsers')";
	}
	
	if(count($n)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO lic_proxy_day (zdate,users) VALUES ".@implode(",", $n));
		if(!$q->ok){echo $q->mysql_error."\n";CleanTable();return;}
	}
	
	if(count($files)>0){
		foreach ($files as $path){@unlink($path);}
	}
	
	
	$now=date("Y-m-d H:00:00",strtotime("-25 hour"));
	
	$results=$q->QUERY_SQL("SELECT zdate,users from lic_proxy_day WHERE zdate>'$now' ORDER BY zDate");
	if(!$q->ok){echo $q->mysql_error."\n";}
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$s_array[0][]=$ligne["zdate"];
		$s_array[1][]=$ligne["users"];
	}
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LicensesUsersRows", serialize($s_array));
	$now=date("Y-m-d H:00:00",strtotime("-1 week"));
	
	$sql="SELECT AVG(users) as avg FROM (SELECT zdate,users from lic_proxy_day WHERE zdate>'$now') as t";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error."\n";}
	if($GLOBALS["VERBOSE"]){echo "$sql\nAVG: {$ligne["avg"]} --> ".intval($ligne["avg"])."\n";}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LicensesUsersCount", intval($ligne["avg"]));
	CleanTable();
}

function CleanTable(){
	$q=new mysql_squid_builder();
	$sql="DELETE FROM lic_proxy_day WHERE zDate<DATE_SUB(NOW(), INTERVAL 30 DAY)";
	$q->QUERY_SQL($sql);
}



