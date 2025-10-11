<?php
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
$GLOBALS["UPDATE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["HOTSPOT"]=false;
$GLOBALS["NORELOAD"]=false;
if(is_array($argv)){if(preg_match("#--no-reload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}}
if(is_array($argv)){if(preg_match("#--hotspot#",implode(" ",$argv))){$GLOBALS["HOTSPOT"]=true;}}
if(is_array($argv)){if(preg_match("#--update#",implode(" ",$argv))){$GLOBALS["UPDATE"]=true;}}
if(is_array($argv)){if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}}
if(is_array($argv)){if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["UPDATE"]=true;$GLOBALS["PROGRESS"]=true;}}




build();

function build_progress($text,$pourc){
	if(!$GLOBALS["PROGRESS"]){return;}
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/squid.macToUid.progress", serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);
	usleep(500);
}

function build(){
	$AS_STATS=false;
	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$AS_STATS=true;}
	$InfluxUseRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemote"));
	$InfluxSyslogRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxSyslogRemote"));
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($InfluxUseRemote==1){die("Use a statistics appliance\n");}
	if($InfluxSyslogRemote==1){die("Use a statistics appliance\n");}
	if(!$AS_STATS){if($SquidPerformance>1){die("Statistics Disabled\n");}}
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,__FILE__)){echo "Already PID running $pid (".basename(__FILE__).")\n";exit();}
	
	$time=$unix->file_time_min($timefile);
	
	if(!$GLOBALS["FORCE"]){if($time<5){
		if($GLOBALS["VERBOSE"]){echo "{$time}mn < 5mn\n";}
		exit();}}
	
	@mkdir(dirname($pidfile),0755,true);
	@file_put_contents($pidfile, getmypid());
	@unlink($timefile);
	@file_put_contents($timefile, time());
	$php=$unix->LOCATE_PHP5_BIN();	
	


	$q=new mysql_squid_builder();
	$postgres=new postgres_sql();
	$sql="SELECT mac,ipaddr,proxyalias,hostname FROM hostsnet WHERE length(proxyalias) >0;";
	$results = $postgres->QUERY_SQL($sql);
	while ($ligne = pg_fetch_assoc($results)) {
		if($ligne["mac"]=="00:00:00:00:00:00"){continue;}
		if(!IsPhysicalAddress($ligne["mac"])){continue;}
		
		echo "{$ligne["mac"]} = {$ligne["proxyalias"]}\n";
		$postgres->QUERY_SQL("UPDATE access_log SET userid='{$ligne["proxyalias"]}' WHERE mac='{$ligne["mac"]}'");
		if(!$postgres->ok){echo $postgres->mysql_error."\n";}
		$postgres->QUERY_SQL("UPDATE rttable_users SET userid='{$ligne["proxyalias"]}' WHERE mac='{$ligne["mac"]}'");
		if(!$postgres->ok){echo $postgres->mysql_error."\n";}
		$postgres->QUERY_SQL("UPDATE access_year SET userid='{$ligne["proxyalias"]}' WHERE mac='{$ligne["mac"]}'");
		if(!$postgres->ok){echo $postgres->mysql_error."\n";}
		$postgres->QUERY_SQL("UPDATE access_month SET userid='{$ligne["proxyalias"]}' WHERE mac='{$ligne["mac"]}'");
		if(!$postgres->ok){echo $postgres->mysql_error."\n";}
        $postgres->QUERY_SQL("UPDATE access_users SET userid='{$ligne["proxyalias"]}' WHERE userid='{$ligne["mac"]}'");


	}

	build_progress("{done}...",100);
	
}

