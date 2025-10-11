<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$GLOBALS["YESCGROUP"]=true;
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.process.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.automatic-tasks.inc");

if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){
	ini_set('display_errors', 1);
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
}


$unix=new unix();
if(!is_file($unix->LOCATE_SQUID_BIN())){exit();}
$sock=new sockets();
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_UNIX"]=new unix();
$GLOBALS["PHP5"]=$unix->LOCATE_PHP5_BIN();
$GLOBALS["NICE"]=$unix->EXEC_NICE();
$GLOBALS["nohup"]=$unix->find_program("nohup");
$GLOBALS["CHMOD"]=$unix->find_program("chmod");
$GLOBALS["CHOWN"]=$unix->find_program("chown");
$GLOBALS["KILLBIN"]=$unix->find_program("kill");
$GLOBALS["RMBIN"]=$unix->find_program("rm");
$GLOBALS["SYNCBIN"]=$unix->find_program("sync");
$GLOBALS["ECHOBIN"]=$unix->find_program("echo");
$GLOBALS["NMAPBIN"]=$unix->find_program("nmap");
$GLOBALS["SquidRotateOnlySchedule"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateOnlySchedule"));
$GLOBALS["SQUID_BIN"]=$unix->LOCATE_SQUID_BIN();



squid_running_schedules();

function squid_running_schedules(){
	
	$TimeFile="/etc/artica-postfix/pids/exec.squid.run.schedules.php.time";
	$pidfile="/etc/artica-postfix/pids/exec.squid.run.schedules.php.pid";
	$unix=new unix();
	
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		_statussquid("$pid already executed since {$timepid}Mn");
		if($timepid<5){return false;}
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}
	
	@file_put_contents($pidfile, getmypid());
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($TimeFile);
		if($time<4){
			_statussquid("Current {$time}Mn need 5Mn");
			return false;
		}
	}
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	
	
	$BASEDIR=ARTICA_ROOT;
	$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));

	if(function_exists("systemMaxOverloaded")){
		if(systemMaxOverloaded()){
            squid_admin_mysql(1,"{OVERLOADED_SYSTEM}",
                $GLOBALS["CLASS_UNIX"]->ps_mem_report(),
                __FILE__,__LINE__);
			return false;}
	}
	
	if($SQUIDEnable==0){return false;}
	
	
	
	$filetimeF="/etc/artica-postfix/pids/exec.squid.watchdog.php.start_watchdog.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>5){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.watchdog.php >/dev/null 2>&1 &");
        RestartTime($filetimeF);
	}

	
	$filetimeF='/etc/artica-postfix/pids/EnableKerbAuth.time';
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	if($EnableKerbAuth==1){
		$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
		_statussquid(basename($filetimeF).": {$filetime}Mn");
		if($filetime>5){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.kerbauth.php --pinglic >/dev/null 2>&1 &");
            RestartTime($filetimeF);
		}
	}



	$EnableGoogleSafeSearch=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleSafeSearch");


		
	$filetimeF="/etc/artica-postfix/pids/exec.squid.php.Defaultschedules.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>120){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.php --defaults-schedules");
        RestartTime($filetimeF);
	}
	
	
	if($SquidPerformance<2){
		$filetimeF="/etc/artica-postfix/pids/exec.squid.stats.not-categorized.php.not_categorized_scan.time";
		$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
		_statussquid(basename($filetimeF).": {$filetime}Mn");
		if($filetime>120){
			//squid_admin_mysql(2, "----- Executing exec.squid.stats.not-categorized.php --recategorize", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.stats.not-categorized.php --recategorize >/dev/null 2>&1 &");
            RestartTime($filetimeF);
		}
	}
	

	
	

	
	if($SquidPerformance<2){
		$filetimeF="/etc/artica-postfix/pids/exec.squid.stats.quota-week.parser.php.time";
		$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
		_statussquid(basename($filetimeF).": {$filetime}Mn");
		if($filetime>1880){
			//squid_admin_mysql(2, "----- Executing exec.squid.stats.quota-week.parser.php", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.stats.quota-week.parser.php >/dev/null 2>&1 &");
            RestartTime($filetimeF);
		}
	}
	


	if($SquidPerformance<2){
		$filetimeF="/etc/artica-postfix/pids/exec.squid.stats.mime.proto.php.time";
		$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
		_statussquid(basename($filetimeF).": {$filetime}Mn");
		if($filetime>19){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.stats.mime.proto.php >/dev/null 2>&1 &");
            RestartTime($filetimeF);
		}
	}
	




	
	$filetimeF="/etc/artica-postfix/pids/exec.squid.rotate.php.build.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>120){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.rotate.php >/dev/null 2>&1 &");
        RestartTime($filetimeF);
	}

	if($SquidPerformance<2){
		$filetimeF="/etc/artica-postfix/pids/exec.squid.interface-size.php.CachedOrNot.time";
		$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
		_statussquid(basename($filetimeF).": {$filetime}Mn");
		if($filetime>9){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.interface-size.php --cache-or-not >/dev/null 2>&1 &");
            RestartTime($filetimeF);
		}
	}
	

	
	$filetimeF="/etc/artica-postfix/settings/Daemons/StatsApplianceReceivers";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>4){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.squid.interface-size.php --stats-apps-clients >/dev/null 2>&1 &");
        RestartTime($filetimeF);
	}
	

	

	
	$filetimeF="/etc/artica-postfix/pids/exec.mysqld.crash.php.check_crashed_squid.time";
	$filetime=$GLOBALS["CLASS_UNIX"]->file_time_min($filetimeF);
	_statussquid(basename($filetimeF).": {$filetime}Mn");
	if($filetime>120){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.mysqld.crash.php --crashed-squid >/dev/null 2>&1 &");
        RestartTime($filetimeF);
	}
	

	squid_tasks();
	
}
function RestartTime($ftime){
    $dir=dirname($ftime);
    if(!is_dir($dir)){@mkdir($dir,0755,true);}
    if(is_file($ftime)){@unlink($ftime);}
    @file_put_contents($ftime,time());
}

function squid_tasks(){
	if(system_is_overloaded()){squid_tasks_events("{OVERLOADED_SYSTEM}, aborting",__FUNCTION__,__FILE__,__LINE__);return;}
	
		$time_start = microtime(true);
		squid_tasks_events("Invoke squid_auto_tasks()",__FUNCTION__,__FILE__,__LINE__);
		$t=new squid_auto_tasks();
		$time_end = microtime(true);
		$time_calc = $time_end - $time_start;
		_statussquid("Running squid_tasks {$time_calc}ms");
}


function squid_tasks_events($text,$function=null,$line=0){
	$filename=basename(__FILE__);
	$function=__CLASS__."/".$function;
	$GLOBALS["CLASS_UNIX"]->events("$text","/var/log/artica-scheduler-squid.log",false,$function,$line,$filename);
}





function shell_exec2($cmdline){
	$cmdline=str_replace("/usr/share/artica-postfix/ressources/exec.","/usr/share/artica-postfix/exec.",$cmdline);

	if(function_exists("debug_backtrace")){


		$trace=debug_backtrace();
		if(isset($trace[0])){
			$T_FUNCTION=$trace[0]["function"];
			$T_LINE=$trace[0]["line"];
			$T_FILE=basename($trace[0]["file"]);
		}


		if(isset($trace[1])){
			$T_FUNCTION=$trace[1]["function"];
			$T_LINE=$trace[1]["line"];
			$T_FILE=basename($trace[1]["file"]);
		}


	}


	if(!isset($GLOBALS["shell_exec2"])){$GLOBALS["shell_exec2"]=array();}
	if(!is_array($GLOBALS["shell_exec2"])){$GLOBALS["shell_exec2"]=array();}
	$md5=md5($cmdline);
	$time=date("YmdHi");
	if(isset($GLOBALS["shell_exec2"][$time][$md5])){
		if($GLOBALS["VERBOSE"]){echo "ERROR ALREADY EXECUTED $cmdline\n";}
		return;
	}
	if(count($GLOBALS["shell_exec2"])>5){$GLOBALS["shell_exec2"]=array();}
	$GLOBALS["shell_exec2"][$time][$md5]=true;


	if(!preg_match("#\/nohup\s+#",$cmdline)){
		$cmdline="{$GLOBALS["nohup"]} $cmdline";
	}
	if(!preg_match("#\s+>\/.*?2>\&1#",$cmdline)){
		if(!preg_match("#\&$#",$cmdline)){
			$cmdline="$cmdline >/dev/null 2>&1 &";
		}
	}

	if($GLOBALS["VERBOSE"]){echo "******************* EXEC ********************************\n$cmdline\n********************************\n";}
	if(!$GLOBALS["VERBOSE"]){_statussquid("$T_FILE:$T_FUNCTION:$T_LINE:Execute: $cmdline",__FUNCTION__,__LINE__);}
	shell_exec($cmdline);

}
function _statussquid($text=null){
    if ($GLOBALS["VERBOSE"]) {echo $text . "\n";}
    if (!function_exists("syslog")) {return;}
    $LOG_SEV = LOG_INFO;
    openlog("artica-status", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
}

