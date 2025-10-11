<?php
$GLOBALS["YESCGROUP"]=true;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Web console monitor";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}


function restart():bool {
    return uninstall();
}

function build_progress($text,$pourc){
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"webconsole-monitor.progress");
}

function install(){
   return uninstall();
}



function uninstall():bool{
    $INITD_PATH=INITD_PATH();
    $unix=new unix();
    $unix->remove_service($INITD_PATH);
    $tpath="/etc/monit/conf.d/APP_WEBCONSOLE_CPU.monitrc";
    if(is_file($tpath)){
        @unlink($tpath);
        $unix->MONIT_RELOAD();
    }
    $files[]=__FILE__;
    $files[]="/usr/share/artica-postfix/bin/percpu";
    $files[]="/usr/share/artica-postfix/bin/percpu.py";
    $files[]="/usr/sbin/percpu";
    $files[]="/var/run/percpu.pid";
    $files[]="/etc/monit/conf.d/APP_WEBCONSOLE_CPU.monitrc";
    $files[]="/usr/share/artica-postfix/ressources/class.status.webconsole-cpu.inc";
    foreach ($files as $path){
        if(is_file($path)){
            @unlink($path);
        }
    }
    shell_exec("/etc/init.d/artica-status restart --force");

    return true;
}
function INITD_PATH(){
    return "/etc/init.d/webconsole-monitor";
}



function _out($text):bool{
    echo "Service.......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("percpu-service", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}

function update_current_binary():bool{
    return uninstall();

}

function start($aspid=false):bool{
	return uninstall();

}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
            _out("[STOP]: Already Artica task running PID $pid since {$time}mn");
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
        _out("[STOP]: Service already stopped...");
		return;
	}
	$pid=PID_NUM();
    _out("[STOP]: Shutdown pid $pid...");

	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
        _out("[STOP]: Service waiting pid:$pid $i/5...");
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
        _out("[STOP]: Service success...");
		return true;
	}

    _out("[STOP]: Service shutdown - force - pid $pid...");
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
        _out("[STOP]: Waiting pid:$pid $i/5...");
		sleep(1);
	}

	if($unix->process_exists($pid)){
        _out("[STOP]: Service failed...");
		return false;
	}

    return true;
}

function PID_PATH():string{
    return "/var/run/percpu.pid";
}

function PID_NUM(){
	
	$unix=new unix();
    $pid=$unix->get_pid_from_file(PID_PATH());
    if($unix->process_exists($pid)){return $pid;}
	$pid=$unix->PIDOF_PATTERN("percpu.py start");
    if($unix->process_exists($pid)){return $pid;}
    return $unix->PIDOF("/usr/sbin/percpu");
	
}
?>