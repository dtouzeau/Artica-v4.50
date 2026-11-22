<?php
// Versionnée pour le SP9 2026-10-10
include_once(dirname(__FILE__)."/settings.exists.inc"); // artica_setting_exists(): settings are root-only, see the file


function haproxy_pid(){
	$pid_path="/var/run/haproxy.pid";
	if(!is_file($pid_path)){
		$binpath=$GLOBALS["CLASS_UNIX"]->find_program("haproxy");
		return $GLOBALS["CLASS_UNIX"]->PIDOF($binpath); 
	}
	$pids=explode("\n",@file_get_contents($pid_path));
    foreach ($pids as $index=>$line){
		$line=str_replace("\r", "", $line);
		$line=str_replace("\n", "", $line);
		if(!is_numeric(trim($line))){continue;}
		if($GLOBALS["VERBOSE"]){echo "$pid_path = $line\n";}
		if($GLOBALS["CLASS_UNIX"]->process_exists($line)){
			$PPID=$GLOBALS["CLASS_UNIX"]->PPID_OF($line);
			if($GLOBALS["VERBOSE"]){echo "$line ->running PPID:$PPID\n";}
			return $PPID;
		}
	}
	
}




//========================================================================================================================================================
function HaproxyCronStatistics(){
	$ENABLED=true;
	$SquidPerformance=0;
	$SquidStatsMonthQueue=0;

	// if > 1 then no stats $SquidPerformance
	$InfluxSyslogRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxSyslogRemote"));
	$InfluxUseRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemote"));
	$EnableHaProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaProxy"));
	if($InfluxUseRemote==0){$InfluxSyslogRemote=0;}

	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){
		$InfluxUseRemote=0;
		$SquidPerformance=0;

	}

	if($InfluxUseRemote==1){$ENABLED=false;}
	if($EnableHaProxy==0){$ENABLED=false;}

	if($ENABLED){
		if(!is_file("/etc/cron.d/haproxy-monthly")){
			$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("haproxy-monthly","30 1 * * *","exec.haproxy.stats.compress.php");
			shell_exec2("/etc/init.d/cron reload");
		}
		if(!is_file("/etc/cron.d/haproxy-yearly")){
			$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("haproxy-yearly","15 3 1 * *","exec.haproxy.stats.compress.php --compress-year");
			shell_exec2("/etc/init.d/cron reload");
		}


		HaproxyCronStatistics_run();
		HaproxyCronStatistics_year_run();
	}

	if(!$ENABLED){
		if(is_file("/etc/cron.d/haproxy-monthly")){
			@unlink("/etc/cron.d/haproxy-monthly");
			shell_exec2("/etc/init.d/cron reload");
		}
		if(is_file("/etc/cron.d/haproxy-yearly")){
			@unlink("/etc/cron.d/haproxy-yearly");
			shell_exec2("/etc/init.d/cron reload");
		}

	}

}
function HaproxyCronStatistics_run(){
	if(!artica_setting_exists("HaproxyStatsMonthQueue")){return;}
	$SquidStatsMonthQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaproxyStatsMonthQueue"));
	if($SquidStatsMonthQueue==0){return;}
	if(system_is_overloaded()){return;}
	$pidfile="/etc/artica-postfix/pids/HaproxyStatsMonthQueue.pid";
	$pidTime="/etc/artica-postfix/pids/HaproxyStatsMonthQueue.time";
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return;}
	$timeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($pidTime);
	if($timeExec<15){return;}
	$cmd="{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.haproxy.stats.compress.php >/dev/null 2>&1 &";
	shell_exec2(trim($cmd));
}
function HaproxyCronStatistics_year_run(){

	$cmd="{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.haproxy.stats.compress.php --compress-year >/dev/null 2>&1 &";
	if(!artica_setting_exists("HaproxyStatsYearQueue")){return;}
	$HaproxyStatsYearQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaproxyStatsYearQueue"));
	if($HaproxyStatsYearQueue==0){return;}
	if(system_is_overloaded()){return;}
	$pidfile="/etc/artica-postfix/pids/HaproxyStatsYearQueue.pid";
	$pidTime="/etc/artica-postfix/pids/HaproxyStatsYearQueue.time";
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return;}
	$timeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($pidTime);
	if($timeExec<15){return;}
	shell_exec2(trim($cmd));
}