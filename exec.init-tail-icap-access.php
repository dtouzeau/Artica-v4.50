<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["STAMP_MAX_RESTART"]="/etc/artica-postfix/SQUID_STAMP_RESTART";
$GLOBALS["PHP_ENGINE"]="exec.c-icap-access.php";
$GLOBALS["log_path"]="/var/log/c-icap/access.log";
$GLOBALS["DAEMON_NAME"]="ICAP Access";
$GLOBALS["TAILPATH"]="/bin/c-icap-access-tail";

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
$unix=new unix();

$GLOBALS["TAIL_STARTUP"]=$unix->LOCATE_PHP5_BIN().' /usr/share/artica-postfix/exec.c-icap-access.php';



	$GLOBALS["ARGVS"]=implode(" ",$argv);
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}

	
	
function GETPID(){
	$unix=new unix();
	if(is_file("/etc/artica-postfix/{$GLOBALS["PHP_ENGINE"]}.pid")){
		$pid=@file_get_contents("'/etc/artica-postfix/{$GLOBALS["PHP_ENGINE"]}.pid'");
	}
	if($unix->process_exists($pid)){return $pid;}
	$pid=$unix->PIDOF($GLOBALS["TAILPATH"]);
	if($unix->process_exists($pid)){return $pid;}
	$pid=$unix->PIDOF_PATTERN($GLOBALS["PHP_ENGINE"]);
	
}

function restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["DAEMON_NAME"]} Already task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	start(true);
}

function is_enabled(){
	$sock=new sockets();
	$CicapEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"));
	$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
	if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){ return 1;}
	if($SQUIDEnable==0){return 0;}
	return $CicapEnabled;
}

function start($aspid=false){
	$GLOBALS["LOGS"]=array();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$sock=new sockets();
	$reconfigure=false;
	
	
	$Enabled=is_enabled();
	
	
	$kill=$unix->find_program("kill");	
	if($Enabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Disabled...\n";}
		return;
	}
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["DAEMON_NAME"]} Already task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	

	$pid=GETPID();
	if(!is_file($GLOBALS["log_path"])){@file_put_contents($GLOBALS["log_path"], "");}
	
	@chmod($GLOBALS["log_path"],0755);
	@chown($GLOBALS["log_path"],"squid");
	
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["DAEMON_NAME"]} already running since {$time}Mn\n";}
		return;
	}
	
	if($GLOBALS["TAILPATH"]==null){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: TAILNAME NOT SET\n";} 
		exit();
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Checking {$GLOBALS["TAILPATH"]}\n";}
	if(!is_file($GLOBALS["TAILPATH"])){
		$tail=$unix->find_program("tail");
		@copy($tail, $GLOBALS["TAILPATH"]);
	}
	@chmod($GLOBALS["TAILPATH"],0755);
	
	$pid=GETPID();
	if($unix->process_exists($pid)){
		for($i=0;$i<20;$i++){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: killing old process pid $pid\n";}
			unix_system_kill_force($pid);
			usleep(800);
			$pid=GETPID();
			if(!$unix->process_exists($pid)){break;}
			unix_system_kill_force($pid);
		}
		
	}
	
	$cmd="{$GLOBALS["TAILPATH"]} --follow=name --retry --max-unchanged-stats=50 -n 0 {$GLOBALS["log_path"]}|{$GLOBALS["TAIL_STARTUP"]} >>/var/log/artica-postfix/squid-logger-start.log 2>&1 &";
	shell_exec($cmd);
	for($i=0;$i<6;$i++){
		$pid=GETPID();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["DAEMON_NAME"]} {$GLOBALS["TAILPATH"]} waiting $i/5\n";}
		sleep(1);
	}
	$pid=GETPID();
	if($unix->process_exists($pid)){
		events("success to start daemon PID:$pid...");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["DAEMON_NAME"]} success PID:$pid\n";}
	}else{
		events("failed to start daemon...");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["DAEMON_NAME"]} failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmd\n";}
	}
}

function stop($aspid=false){
	
	$unix=new unix();
	$kill=$unix->find_program("kill");
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["DAEMON_NAME"]} Already task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	
	$pid=GETPID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["DAEMON_NAME"]} already stopped\n";}
		return;
	}
	
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["DAEMON_NAME"]} stopping pid: $pid\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<20;$i++){
		$pid=GETPID();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["DAEMON_NAME"]} waiting pid: $pid $i/20\n";}
		unix_system_kill_force($pid);
		sleep(1);
	}	

	
	$pid=GETPID();
	if(!$unix->process_exists($pid)){
		events("success to stop daemon...");
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["DAEMON_NAME"]} success\n";}
	}else{
		events("failed to stop daemon...");
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["DAEMON_NAME"]} failed\n";}
	}	
	
}
function events($text){
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}

	}

	$unix=new unix();
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}