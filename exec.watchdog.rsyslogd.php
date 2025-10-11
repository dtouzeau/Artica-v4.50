#!/usr/bin/php -q
<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="RSyslog Daemon";
$GLOBALS["PROGRESS"]=false;
$GLOBALS["MIGRATION"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;
$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--migration#",implode(" ",$argv),$re)){$GLOBALS["MIGRATION"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.influx.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');


if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--restart"){restart();exit;}



function GET_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/rsyslogd.pid");
	if($unix->process_exists($pid)){return $pid;}
	$rsyslogd=$unix->find_program("rsyslogd");
	return $unix->PIDOF($rsyslogd);
	
}
function _out($text):bool{
    $date=date("H:i:s");
    echo "Starting......: $date [INIT]: {$GLOBALS["TITLENAME"]}, $text\n";
    return true;
}
function _xout($text):bool{
    $date=date("H:i:s");
    echo "Stopping......: $date [INIT]: {$GLOBALS["TITLENAME"]}, $text\n";
    return true;
}
function stop($aspid=true){
    $unix=new unix();
    if($aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            _xout("Already Artica task running PID $pid since {$time}mn");
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=GET_PID();


    if(!$unix->process_exists($pid)){
        _xout("service already stopped...");
        return true;
    }
    $pid=GET_PID();


    _xout("service Shutdown pid $pid");
    $unix->KILL_PROCESS($pid,15);

    for($i=0;$i<5;$i++){
        $pid=GET_PID();
        if(!$unix->process_exists($pid)){break;}
        _xout("Service waiting pid:$pid $i/5...");
        $unix->KILL_PROCESS($pid);
        sleep(1);
    }

    $pid=GET_PID();
    if(!$unix->process_exists($pid)){
        _xout("service success...");
        return true;
    }

    _xout("Service shutdown - force - pid $pid...");
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=GET_PID();
        if(!$unix->process_exists($pid)){break;}
        unix_system_kill_force($pid);
        _xout("service waiting pid:$pid $i/5...");
        sleep(1);
    }

    if($unix->process_exists($pid)){
        _xout("Service failed...");
        return false;
    }
    return true;

}
function restart(){
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";
        return;
    }

    stop(false);
    start(false);
}

function start($aspid=true){
	$unix=new unix();
    if($aspid) {
        $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
        $pid = $unix->get_pid_from_file($pidfile);
        if ($unix->process_exists($pid, basename(__FILE__))) {
            $time = $unix->PROCCESS_TIME_MIN($pid);
            _out("Already Artica task running PID $pid since {$time}mn");
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

	$PID=GET_PID();
	if($unix->process_exists($PID)){
		$SrcPid=intval(@file_get_contents("/var/run/rsyslogd.pid"));
		if($SrcPid<>$PID){
			@file_put_contents("/var/run/rsyslogd.pid", $PID);
		}
		@unlink($pidfile);
		return true;
	}




    if($aspid) {
        squid_admin_mysql(2, "Syslog daemon is down [{action}={start}]", null, __FILE__, __LINE__);
    }
    $nohup=$unix->find_program("nohup");
    $sh=$unix->sh_command("$nohup /usr/sbin/rsyslogd -n -i /var/run/rsyslogd.pid >/dev/null 2>&1 &");
    $unix->go_exec($sh);
    $PID=GET_PID();
    for($i=0;$i<5;$i++){

        if($unix->process_exists($PID)){
            _out("Success PID:$PID");
            return true;
        }
        sleep(1);
        $PID=GET_PID();
    }

    $PID=GET_PID();
    if(!$unix->process_exists($PID)){
        _out("{failed}");
    }
    return false;
}


