<?php
function load_stats(){
	events("************************ SCHEDULE ****************************",__FUNCTION__,__LINE__);
	
	if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	if(!isset($GLOBALS["CLASS_UNIX"])){$unix=new unix();}else{$unix=$GLOBALS["CLASS_UNIX"];}
	
	$time=time();
	$BASEDIR="/usr/share/artica-postfix";
	$hash_mem=array();
	
	
	
	$NtpdateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtpdateAD"));
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($NtpdateAD==1){$NTPDEnabled=1;}
	



	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.cleanfiles.php.time");
	if($time_file>120){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.cleanfiles.php >/dev/null 2>&1 &");
	}
	
	$timefile=$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.clean.logs.php.CleanLogs.time");
	if($time_file>240){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.clean.logs.php --clean-tmp >/dev/null 2>&1 &");
	}	



	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.watchdog.php.CHECK_DNS_SYSTEMS.time");
	events("CHECK_DNS_SYSTEMS: {$time_file}mn",__FUNCTION__,__LINE__);

	
	
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.clean.logs.php.clean_space.time");
	events("clean_space: {$time_file}mn",__FUNCTION__,__LINE__);
	if($time_file>240){
		$cmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.clean.logs.php --clean-space >/dev/null 2>&1 &";
		events($cmd,__FUNCTION__,__LINE__);
		shell_exec2("$cmd");
	}


	$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	exec("pgrep -l -f \"exec.schedules.php --run\" 2>&1",$results);

	foreach ($results as $line){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		$pid=$re[1];
		$TTL=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
		events("$line -> {$TTL}Mn");
		if($TTL<420){continue;}
		ToSyslog("Killing exec.schedules.php PID $pid");
		unix_system_kill_force($pid);
	}



	events("************************ SCHEDULE ****************************",__FUNCTION__,__LINE__);
}