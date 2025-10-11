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


include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
$unix=new unix();

	$GLOBALS["ARGVS"]=implode(" ",$argv);
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}

	
	
function GETPID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/hypercache-tail.pid");
	if($unix->process_exists($pid)){return $pid;}
	$pid=$unix->PIDOF_PATTERN("hypercache-tail.py");
	if($unix->process_exists($pid)){return $pid;}
	$pid=$unix->PIDOF("/usr/share/artica-postfix/hypercache-tail.py");
	if($unix->process_exists($pid)){return $pid;}
	
}

function restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: hypercache-tail, Already task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	start(true);
}

function start($aspid=false){
	$GLOBALS["LOGS"]=array();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$sock=new sockets();
	$reconfigure=false;
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	$NtpdateAD=$sock->GET_INFO("NtpdateAD");
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	$InfluxUseRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemote"));
	$InfluxSyslogRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxSyslogRemote"));
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	if($InfluxUseRemote==0){$InfluxSyslogRemote=0;}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableInfluxDB")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableInfluxDB", 1);}
	$EnableInfluxDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableInfluxDB"));

		
	$kill=$unix->find_program("kill");	
	$enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHyperCacheProxy"));
	
	
	if($enabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: service ( EnableHyperCacheProxy ) disabled...\n";}
		$pid=GETPID();
		if($unix->process_exists($pid)){stop();}
		return;
	}
	
	
	

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			@file_put_contents("/var/run/hypercache-tail.pid", $pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: hypercache-tail, Already task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	

	$pid=GETPID();

	
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: hypercache-tail, already running since {$time}Mn\n";}
		return;
	}
	@chmod("/usr/share/artica-postfix/hypercache-tail.py",0755);
	$php=$unix->LOCATE_PHP5_BIN();
	
	$python=$unix->find_program("python");
	
	
	if(is_file("/var/run/hypercache-tail.pid")){@unlink("/var/run/hypercache-tail.pid");}
	
	$cmd="$python /usr/share/artica-postfix/hypercache-tail.py start &";
	
	shell_exec($cmd);
	for($i=0;$i<6;$i++){
		$pid=GETPID();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: hypercache-tail, waiting $i/5\n";}
		sleep(1);
	}
	$pid=GETPID();
	if($unix->process_exists($pid)){
		events("hypercache-tail success to start daemon PID:$pid...");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: hypercache-tail, success PID:$pid\n";}
	}else{
		events("hypercache-tail failed to start daemon...");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: hypercache-tail, failed\n";}
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
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: hypercache-tail, Already task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	

	$pid=GETPID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: hypercache-tail, already stopped\n";}
		return;
	}
	
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: hypercache-tail, stopping pid: $pid\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<10;$i++){
		$pid=GETPID();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: hypercache-tail, waiting pid: $pid $i/10\n";}
		unix_system_kill_force($pid);
		sleep(1);
	}	
	
	$pid=GETPID();
	if(!$unix->process_exists($pid)){
		if(is_file("/var/run/hypercache-tail.pid")){@unlink("/var/run/hypercache-tail.pid");}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: hypercache-tail, success\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: hypercache-tail, failed\n";}
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