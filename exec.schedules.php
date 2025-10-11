<?php
$GLOBALS["YESCGROUP"]=true;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["WITHOUT_RESTART"]=false;
$GLOBALS["CMDLINES"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--no-restart#",implode(" ",$argv))){$GLOBALS["WITHOUT_RESTART"]=true;}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.tasks.inc');
include_once(dirname(__FILE__).'/ressources/class.process.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");


exec("/usr/bin/pgrep -f /usr/sbin/cron 2>&1",$results);
if(count($results)>15){
    $unix=new unix();
    $unix->ToSyslog("Too much cron processes (". count($results).") aborting","CRON");
    die();
}

    if($GLOBALS["VERBOSE"]){
            $GLOBALS["OUTPUT"]=true;
            $GLOBALS["WITHOUT_RESTART"]=true;
            ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
    }
    if(isset($argv[1])){
        if($argv[1]=="--run-schedules"){run_schedules($argv[2]);exit();}
        if($argv[1]=="--defaults"){Defaults();exit();}
        if($argv[1]=="--run"){execute_task($argv[2]);exit();}
        if($argv[1]=="--run-squid"){execute_task_squid($argv[2]);exit();}
        if($argv[1]=="--run-meta"){run_meta($argv[2]);exit();}
    }


build_schedules();

function Defaults(){
	$task=new system_tasks();
	if($GLOBALS["VERBOSE"]){echo "CheckDefaultSchedules()\n";}
	$task->CheckDefaultSchedules();
	build_schedules();
	
}
function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." $pourc% $echotext\n";
	$cachefile=PROGRESS_DIR."/tasks.compile.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function build_schedules(){
	$unix=new unix();
	if($unix->ServerRunSince()<3){
		build_progress(110, "Too short time to execute the process - ServerRunSince");
		return;}
	
	build_progress(15, "{starting}");
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		writelogs("Already executed pid $pid",__FILE__,__FUNCTION__,__LINE__);
		build_progress(110, "Already executed pid $pid");
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	
	$pidTimeINT=$unix->file_time_min($pidTime);
	if(!$GLOBALS["VERBOSE"]){
		if($pidTimeINT<1){
			writelogs("Too short time to execute the process $pidTime = {$pidTimeINT}Mn < 1",__FILE__,__FUNCTION__,__LINE__);
			build_progress(110, "Too short time to execute the process");
			return;
		}
	}
	
	@file_put_contents($pidTime, time());
	
	build_progress(30, "{configuring}");
	$task=new system_tasks();
	$task->CheckDefaultSchedules();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$php=$unix->LOCATE_PHP5_BIN();
	if(file_exists($squidbin)){
		$q=new mysql_squid_builder();
		$q->CheckDefaultSchedules();
	}
	$q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");

	if(!$q->TABLE_EXISTS("system_schedules")){
        shell_exec("$php /usr/share/artica-postfix/exec.convert-to-sqlite.php");
    }

	
	if($q->COUNT_ROWS("system_schedules")==0){
		echo "Starting......: ".date("H:i:s")." artica-postfix watchdog (fcron) system_schedules table is empty (1)!!\n";
		$task->CheckDefaultSchedules();
		if($q->COUNT_ROWS("system_schedules")==0){
			echo "Starting......: ".date("H:i:s")." artica-postfix watchdog (fcron) system_schedules table is empty (2)!!\n";
			exit();
		}
	}
	
	
	$sql="SELECT * FROM system_schedules WHERE enabled=1";
	$results = $q->QUERY_SQL($sql);	
	if(!$q->ok){
		echo "Starting......: ".date("H:i:s")." artica-postfix watchdog (fcron) $q->mysql_error on line ". __LINE__."\n";
		build_progress(110, "{failed}");
		return;
	}	
	
	
	$php5=$unix->LOCATE_PHP5_BIN();
    $settings=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FcronSchedulesParams"));
	if(!isset($settings["max_nice"])){$settings["max_nice"]=null;}
	if(!isset($settings["max_load_wait"])){$settings["max_load_wait"]=null;}
	if(!isset($settings["max_load_avg5"])){$settings["max_load_avg5"]=null;}
	
	
	
	if(!is_numeric($settings["max_load_avg5"])){$settings["max_load_avg5"]="2.5";}
	if(!is_numeric($settings["max_load_wait"])){$settings["max_load_wait"]="10";}
	if(!is_numeric($settings["max_nice"])){$settings["max_nice"]="19";}	

	@unlink("/etc/cron.d/artica-cron");
	foreach (glob("/etc/cron.d/*") as $filename) {
		if(preg_match("#syssch-[0-9]+#", $filename)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." artica-postfix watchdog (fcron) remove $filename\n";}
			@unlink($filename);}
	}
	@unlink("/etc/artica-postfix/TASKS_CACHE.DB");
	@unlink("/etc/artica-postfix/system.schedules");
	$nice=$unix->EXEC_NICE();
	build_system_defaults();
	$me=__FILE__;
	
	foreach ($results as $index=>$ligne){
		$TaskType=$ligne["TaskType"];
		$TimeText=$ligne["TimeText"];
		if($TaskType==0){continue;}
		if($ligne["TimeText"]==null){continue;}
		$md5=md5("$TimeText$TaskType");
		build_progress(50, "$TaskType $TimeText");
		if(isset($alreadydone[$md5])){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [$index]: artica-postfix watchdog task {$ligne["ID"]} already set\n";}continue;}
		$alreadydone[$md5]=true;
		
		if(!isset($task->tasks_processes[$TaskType])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." artica-postfix watchdog (fcron) Unable to stat task process of `$TaskType`\n";}
			continue;
		}
		
		if(isset($task->task_disabled[$TaskType])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." artica-postfix`$TaskType` disabled\n";}
			continue;
		}
		
		$script=$task->tasks_processes[$TaskType];
		
		
		
		$f=array();
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." scheduling $script /etc/cron.d/syssch-{$ligne["ID"]}\n";} 
		$cmdline=trim("$nice $php5 $me --run {$ligne["ID"]}");
		$f[]="MAILTO=\"\"";
		$f[]="{$ligne["TimeText"]}  root $cmdline >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/syssch-{$ligne["ID"]}", @implode("\n", $f));
	
	}
	build_progress(99, "{done}");
	shell_exec("/etc/init.d/cron reload");
	build_progress(100, "{success}");
	
}




function build_system_defaults(){
	
	$unix=new unix();
	$sock=new sockets();
	$nice=$unix->EXEC_NICE();
	$php=$unix->LOCATE_PHP5_BIN();
	$ArticaBackupEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaBackupEnabled"));
	$users=new usersMenus();
	@unlink("/etc/cron.d/artica-cron-backup");
	@unlink("/etc/cron.d/artica-cron-pflogsumm");
	
	if(is_file('/etc/artica-postfix/artica-backup.conf')){
		if($ArticaBackupEnabled==1){
			$ini=new Bs_IniHandler();
			$ini->loadFile('/etc/artica-postfix/artica-backup.conf');
			if(!isset($ini->_params["backup"]["backup_time"])){$ini->_params["backup"]["backup_time"]="03:00";}
			if(preg_match("#([0-9]+):([0-9]+)#", $ini->_params["backup"]["backup_time"],$re)){
				$backup_hour=intval($re[1]);
				$backup_min=intval($re[2]);
				$f[]="MAILTO=\"\"";
				$f[]="$backup_min $backup_hour * * * root $nice /usr/share/artica-postfix/bin/artica-backup --backup >/dev/null 2>&1";
				$f[]="";
				@file_put_contents("/etc/cron.d/artica-cron-backup", @implode("\n", $f));
				$f=array();
			}
		}
	}
	
	if(is_file('/etc/artica-postfix/settings/Daemons/pflogsumm')){
		$ini=new Bs_IniHandler();
		$ini->loadFile('/etc/artica-postfix/settings/Daemons/pflogsumm');
		$schedule_time=trim($ini->_params['SETTINGS']['schedule']);
		if ($schedule_time<>null){
			$f[]="MAILTO=\"\"";
			$f[]="$schedule_time root $nice $php /usr/share/artica-postfix/exec.postfix.reports.php >/dev/null 2>&1";
			$f[]="";
			@file_put_contents("/etc/cron.d/artica-cron-pflogsumm", @implode("\n", $f));
			$f=array();
		}	
	}
	
	$prefix="/usr/share/artica-postfix";
	$f=array();
	$f[]="MAILTO=\"\"";
	$f[]="@reboot root $nice /sbin/modprobe cifs && echo 0 > /proc/fs/cifs/OplockEnabled >/dev/null 2>&1";
	$f[]="";
	@file_put_contents("/etc/cron.d/cifs-fix", @implode("\n", $f));
	$f=array();

	$f[]="MAILTO=\"\"";
	$f[]="@reboot root $nice $php $prefix/exec.schedules.php >/dev/null 2>&1";
	$f[]="";
	@file_put_contents("/etc/cron.d/schedules", @implode("\n", $f));
	$f=array();	
	
	

	

	
	$f[]="MAILTO=\"\"";
	$f[]="10,34,51 0 * * * root $nice $php $prefix/exec.watchdog.php --monit >/dev/null 2>&1";
	$f[]="";
	@file_put_contents("/etc/cron.d/artica-watchdogmonit", @implode("\n", $f));
	$f=array();	
	
	$f[]="MAILTO=\"\"";
	$f[]="0,2,4,6,8,10,12,14,16,18,22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54,58 * * * * root $nice $php $prefix/exec.parse-orders.php >/dev/null 2>&1";
	$f[]="";
	@file_put_contents("/etc/cron.d/artica-parseorders", @implode("\n", $f));
	$f=array();
	
	if($users->spamassassin_installed){
		$f[]="MAILTO=\"\"";
		$f[]="10 3,6,9,12,15,18,21,23 * * * root $nice $php $prefix/exec.sa-learn-cyrus.php --execute >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/artica-salearn-cyrus", @implode("\n", $f));
		$f=array();
	}
	
	
	
	

	if($users->fetchmail_installed){
		$f[]="MAILTO=\"\"";
		$f[]="0,2,4,6,8,10,12,14,16,18,22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54,58 * * * * root $nice $php $prefix/exec.fetchmail.sql.php >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/artica-ftechmailsql", @implode("\n", $f));
		$f=array();
	}
}



function execute_task($ID){
	$unix=new unix();
	$tasks=new system_tasks();
	$php5=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["SCHEDULE_ID"]=$ID;
    $TaskType=0;
	$TASKS_CACHE=unserialize(@file_get_contents("/etc/artica-postfix/TASKS_CACHE.DB"));
    if(!is_array($TASKS_CACHE)){
        $TASKS_CACHE=array();
    }

	if(isset($TASKS_CACHE[$ID])){
		$TaskType=$TASKS_CACHE[$ID]["TaskType"];
		if(isset($task->task_disabled[$TaskType])){
			writelogs("Task $ID is disabled",__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
	}
	
	if(!isset($TASKS_CACHE[$ID])){
		$q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
		$ligne=$q->mysqli_fetch_array("SELECT TaskType FROM system_schedules WHERE ID=$ID");
		$TaskType=$ligne["TaskType"];
		$TASKS_CACHE[$ID]["TaskType"]=$ligne["TaskType"];
		@file_put_contents("/etc/artica-postfix/TASKS_CACHE.DB", serialize($TASKS_CACHE));
	}	
	if($TaskType==0){return false;}
	if(!isset($tasks->tasks_processes[$TaskType])){return false;}
	if(isset($task->task_disabled[$TaskType])){return false;}
	$script=$tasks->tasks_processes[$TaskType];

    $WorkingDirectory=dirname(__FILE__);
	if($GLOBALS["VERBOSE"]){
        echo "Running $php5 $WorkingDirectory/$script --schedule-id=$ID\n";
    }

	$cmd="$script --schedule-id=$ID";
	if(preg_match("#^bin:(.+)#",$script, $re)){
        $cmd="$WorkingDirectory/bin/$re[1]";
        $unix->go_exec($cmd);
        return true;
    }
    $unix->framework_exec($cmd);
    return true;
}

function events($text,$function,$line):bool{
	return system_admin_events($text , $function, __FILE__, $line, "tasks");
	
}

function run_meta($ID){
}
function snapshot_syslog($text){
    if(!function_exists("syslog")){return false;}
    echo "syslog:$text\n";
    openlog("snapshots", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}

function run_schedules($ID){
	$GLOBALS["SCHEDULE_ID"]=$ID;
	writelogs("Task $ID",__FUNCTION__,__FILE__,__LINE__);
	$q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
	$ligne=$q->mysqli_fetch_array("SELECT TaskType FROM system_schedules WHERE ID=$ID");
    if(!isset($ligne["TaskType"])){
        return false;
    }
	$tasks=new system_tasks();
	$TaskType=intval($ligne["TaskType"]);
	if($TaskType==0){return false;}
	if(!isset($tasks->tasks_processes[$TaskType])){squid_admin_mysql(2, "Unable to understand task type `$TaskType` For this task" , __FUNCTION__, __FILE__, __LINE__, "tasks");return false;}
	$script=$tasks->tasks_processes[$TaskType];
	if(isset($task->task_disabled[$TaskType])){return false;}

    $TASKS_DIRECT[1]=true;
	$TASKS_DIRECT[75]=true;
    $TASKS_DIRECT[78]=true;
    $TASKS_DIRECT[80]=true;


    $unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$WorkingDirectory=dirname(__FILE__);
	$cmd="$php5 $WorkingDirectory/$script --schedule-id=$ID";
	if(preg_match("#^bin:(.+)#",$script, $re)){$cmd="$WorkingDirectory/bin/$re[1]";}

	if(isset($TASKS_DIRECT[$TaskType])){
	    shell_exec("$nohup $cmd >/dev/null 2>&1 &");
	    return true;
    }
	
	writelogs("Task {$GLOBALS["SCHEDULE_ID"]} is scheduled with `$cmd` ",__FUNCTION__,__FILE__,__LINE__);
	$unix->THREAD_COMMAND_SET($cmd);
	return true;
	
}

function execute_task_squid($ID){
	
	$unix=new unix();
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$qProxy=new mysql_squid_builder(true);
	$php5=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["SCHEDULE_ID"]=$ID;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".$ID.squid.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$timeProcess=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["VERBOSE"]){echo "$pid, task is already executed (since {$timeProcess}Mn}), aborting\n";}
		squid_admin_mysql(2, "$pid, task is already executed (since {$timeProcess}Mn}), aborting" , __FUNCTION__, __FILE__, __LINE__);
		return;
	}
	
	$pidtime=$unix->file_time_min($pidfile);
	if($pidtime<1){
		if($GLOBALS["VERBOSE"]){echo "Last execution was done since {$pidtime}mn\n";}
		squid_admin_mysql(2, "last execution was done since {$pidtime}mn" , __FUNCTION__, __FILE__, __LINE__);
		return;
	}
	
	
	$TaskTypeForced[3]=true;
	$TASKS_CACHE=unserialize(@file_get_contents("/etc/artica-postfix/TASKS_SQUID_CACHE.DB"));

	if(isset($TASKS_CACHE[$ID])){
		$TaskType=$TASKS_CACHE[$ID]["TaskType"];
		if(!isset($TaskTypeForced[$TaskType])){
			if(isset($qProxy->tasks_disabled[$TaskType])){
				if($GLOBALS["VERBOSE"]){echo "Task $ID is disabled\n";}
				writelogs("Task $ID is disabled",__FUNCTION__,__FILE__,__LINE__);
				return;
			}
		}
	}	
	
	
	usleep(rand(900, 3000));
	@unlink($pidfile);
	@file_put_contents($pidfile, getmypid());


	
	if(!isset($TASKS_CACHE[$ID])){
        if($GLOBALS["VERBOSE"]){echo "SELECT TaskType FROM webfilters_schedules WHERE ID=$ID\n";}
		$ligne=$q->mysqli_fetch_array("SELECT TaskType FROM webfilters_schedules WHERE ID=$ID");
		$TaskType=$ligne["TaskType"];
        if($GLOBALS["VERBOSE"]){echo "SELECT TaskType == [$TaskType]\n";}
		$TASKS_CACHE[$ID]["TaskType"]=$ligne["TaskType"];
		@file_put_contents("/etc/artica-postfix/TASKS_SQUID_CACHE.DB", serialize($TASKS_CACHE));		
	}else{
        $TaskType=$TASKS_CACHE[$ID]["TaskType"];
    }
	
	if($GLOBALS["VERBOSE"]){echo "Task [$TaskType]\n";}
	
	if($TaskType==0){return;}
	if(!isset($qProxy->tasks_processes[$TaskType])){
        if($GLOBALS["VERBOSE"]){echo "Unable to understand task type `$TaskType` For this task\n";}
        squid_admin_mysql(1, "Unable to understand task type `$TaskType` For this task" , __FUNCTION__, __FILE__, __LINE__);return;
    }
	
	
	
	if(!isset($TaskTypeForced[$TaskType])){
		if(isset($qProxy->tasks_disabled[$TaskType])){
			if($GLOBALS["VERBOSE"]){echo "Task type `$TaskType` is disabled\n";}
			squid_admin_mysql(2, "Task type `$TaskType` is disabled" , __FUNCTION__, __FILE__, __LINE__);
			return;
		}
	}
	
	
	$script=trim($qProxy->tasks_processes[$TaskType]);
	if($script==null){
		if($GLOBALS["VERBOSE"]){echo "Task type `$TaskType` script is null\n";}
		squid_admin_mysql(2, "Task type `$TaskType` script is null" , __FUNCTION__, __FILE__, 
		__LINE__);
		return;
	}
	
	$WorkingDirectory=dirname(__FILE__);
	$cmd="$php5 $WorkingDirectory/$script --schedule-id=$ID";
	if(preg_match("#^bin:(.+)#",$script, $re)){$cmd="$WorkingDirectory/bin/$re[1]";}
    if(preg_match("#artica-phpfpm-service#",$script)){
        $cmd="$script";
    }


	$nohup=$unix->find_program("nohup");
	echo "$nohup $cmd >/dev/null 2>&1 &\n";
	shell_exec("$nohup $cmd >/dev/null 2>&1 &");
	
}



