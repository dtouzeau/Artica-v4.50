<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');




	$GLOBALS["ARGVS"]=implode(" ",$argv);
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
	if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
	



function PID_NUM(){
	$filename=PID_PATH();
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	
}
//##############################################################################
function PID_PATH(){
	if(isset($GLOBALS["PID_PATH"])){return $GLOBALS["PID_PATH"];}
	$unix=new unix();

	if(!isset($GLOBALS["QUEUE_DIRECTORY"])){
		$postconf=$unix->find_program("postconf");
		exec("$postconf queue_directory 2>&1",$results);
		foreach ($results as $num=>$line){
			$line=trim($line);
			if($line==null){continue;}
			if(preg_match("#^queue_directory.*?=(.+)#", $line,$re)){
				$GLOBALS["QUEUE_DIRECTORY"]=trim($re[1]);
				break;
			}
			
		}
	}
	
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."/line: "."{$GLOBALS["QUEUE_DIRECTORY"]}/pid/master.pid\n";}
	$GLOBALS["PID_PATH"]="{$GLOBALS["QUEUE_DIRECTORY"]}/pid/master.pid";
	return $GLOBALS["PID_PATH"];
}
//##############################################################################
function restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		build_progress_restart(110, "{failed}");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());	
	
	build_progress_restart(50, "{stopping_service}");
	stop(true);
	build_progress_restart(90, "{starting_service}");
	start(true);
	
	$pid=PID_NUM();
	
	if($unix->process_exists($pid)){
		build_progress_restart(100, "{starting_service} {success}");
	}else{
		build_progress_restart(110, "{starting_service} {failed}");
	}
	
}
//##############################################################################
function build_progress_restart($pourc,$text){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents(PROGRESS_DIR."/postfix.progress", serialize($array));

	if($GLOBALS["WIZARD"]){
		$array["POURC"]=25;
		$array["TEXT"]=$text;
		@file_put_contents(PROGRESS_DIR."/postfix.progress", serialize($array));
	}

	@chmod(PROGRESS_DIR."/postfix.progress",0755);


}
function reload(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	

	$postfix=$unix->find_program("postfix");
	$unixsocket="/var/spool/postfix/var/run/cyrus/socket/lmtp";
	@chown($unixsocket, "postfix");
	@chgrp($unixsocket, "postfix");
	@chmod($unixsocket,0777);
	
	if(is_file("/etc/sasldb2")){
		@chown("/etc/sasldb2", "postfix");
		@chgrp("/etc/sasldb2", "postfix");
	}
	
	shell_exec("$postfix reload");

}
//##############################################################################
function start(){
    system("/usr/sbin/artica-phpfpm-service -start-postfix");
}
function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Postfix Pid file: ". PID_PATH()."\n";}
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Postfix service already stopped...\n";}
		return;
	}
	
	$postconf=$unix->find_program("postconf");
	$postfix=$unix->find_program("postfix");
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Postfix service Shutdown pid $pid...\n";}
	
	
	
	shell_exec("$postfix stop >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Postfix service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Postfix service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Postfix service success...\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Postfix service failed...\n";}
	
}



?>