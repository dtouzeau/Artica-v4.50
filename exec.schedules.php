<?php
// ETAT TRANSITOIRE (tache 13, migration systemtasks phase 1):
// Ce fichier ne genere plus les cron systeme ni n'execute les taches du
// catalogue general - ce moteur vit desormais dans le daemon Go
// (module SystemTasks, API /system/tasks/{build,defaults,run/{id}}).
// Seule execute_task_squid() (branche --run-squid) est conservee: les
// fichiers /etc/cron.d/squidsch-* que exec.squid.php genere encore
// l'appellent. Ce n'est pas un fichier a moitie casse: la voie squid
// relève d'une phase ulterieure de la migration et disparaitra alors.
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
        if($argv[1]=="--run-squid"){execute_task_squid($argv[2]);exit();}
        if($argv[1]=="--run-meta"){run_meta($argv[2]);exit();}
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



