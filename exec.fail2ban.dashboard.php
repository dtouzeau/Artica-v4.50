<?php

$GLOBALS["BYPASS"]=true;
$GLOBALS["DEBUG_INFLUX_VERBOSE"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["DEBUG_MEM"]=false;
$GLOBALS["NODHCP"]=true;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
}

	ini_set('display_errors', 1);
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);


if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");

if(systemMaxOverloaded()){
	echo "OVERLOADED !!! \n";
	$TimeFile="/etc/artica-postfix/pids/exec.fail2ban.dashboard.time";
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	exit();
}


STARTX();

function STARTX(){
	$TimeFile="/etc/artica-postfix/pids/exec.suricata.dashboard.time";
	$pidfile="/etc/artica-postfix/pids/exec.suricata.dashboard.pid";
	$unix=new unix();
	if($unix->ServerRunSince()<3){return;}
	$InfluxUseRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemote"));
	
	if($InfluxUseRemote==1){
		echo "InfluxUseRemote == $InfluxUseRemote !!! \n";
		@unlink($TimeFile);
		@file_put_contents($TimeFile, time());
		exit();
	}
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid already executed since {$timepid}Mn\n";}
		if(!$GLOBALS["FORCE"]){
			if($timepid<14){return;}
			$kill=$unix->find_program("kill");
			unix_system_kill_force($pid);
		}
	}
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());	
	echo "==> COUNT_OF_FAIL2BAN !!! \n";
	COUNT_OF_FAIL2BAN();
	purge();
}

function COUNT_OF_FAIL2BAN(){

	$EnableFail2Ban=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFail2Ban"));
	if($EnableFail2Ban==0){
		echo "EnableFail2Ban --> $EnableFail2Ban !!! \n";
		return;
	}
	if(!function_exists("pg_fetch_assoc")){
		echo "pg_fetch_assoc() --> NONE !!! \n";
		return;}
	$q=new postgres_sql();
	$sql="SELECT SUM(xcount) as xcount FROM fail2ban_events";
	$ligne=pg_fetch_assoc($q->QUERY_SQL($sql));
	@mkdir($GLOBALS["BASEDIR"],0755,true);
	@file_put_contents("{$GLOBALS["BASEDIR"]}/COUNT_OF_FAIL2BAN", intval($ligne["xcount"]));
	//usr/local/ArticaStats/bin/psql -h /var/run/ArticaStats -U ArticaStats proxydb
	
	$sql="SELECT COUNT(*) as xcount FROM ( SELECT src_ip FROM fail2ban_events GROUP BY src_ip ) as t";
	$ligne=pg_fetch_assoc($q->QUERY_SQL($sql));
	@file_put_contents("{$GLOBALS["BASEDIR"]}/COUNT_OF_FAIL2BAN_SRC", intval($ligne["xcount"]));
}

function purge(){
	$unix=new unix();
	$q=new postgres_sql();
	$sock=new sockets();
	$users=new usersMenus();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/exec.fail2ban.hourly.purge.time";
	$users=new usersMenus();

	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";
		return;
	}

	@file_put_contents($pidfile, getmypid());
	if(system_is_overloaded()){return;}

	$timeExec=$unix->file_time_min($pidtime);
	if($timeExec<1440){return;}

	@unlink($pidtime);
	@file_put_contents($pidtime, time());


	$Fail2Purge=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Fail2Purge"));
	if($Fail2Purge==0){$Fail2Purge=7;}
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$Fail2Purge=7;}

	$q->QUERY_SQL("DELETE FROM fail2ban_events WHERE zdate < NOW() - INTERVAL '$Fail2Purge days'");
}