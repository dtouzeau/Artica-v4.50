<?php

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Artica Syslog daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}





function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Restarting....: [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	
	
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Restarting....: [INIT]: {$GLOBALS["TITLENAME"]} PID $pid running since {$time}mn\n";}
	}
	
	stop(true);
	sleep(1);
	start(true);
	
}

function build_monit():bool{
        $RESTART        = false;
        if(!is_file("/etc/init.d/artica-syslog")){
            return false;
        }
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/monit/conf.d/APP_SYSLOGER.monitrc\n";
        if(!is_file("/etc/monit/conf.d/APP_SYSLOGER.monitrc")){$RESTART=true;}
        $f[]="check process APP_SYSLOGER with pidfile /etc/artica-postfix/exec.syslog.php.pid";
        $f[]="\tstart program = \"/etc/init.d/artica-syslog start\"";
        $f[]="\tstop program = \"/etc/init.d/artica-syslog stop\"";
        $f[]="if cpu > 99% for 15 cycles then restart";
        $f[]="";

        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/monit/conf.d/APP_SYSLOGER.monitrc Done\n";
        @file_put_contents("/etc/monit/conf.d/APP_SYSLOGER.monitrc", @implode("\n", $f));
        if(!is_file("/etc/monit/conf.d/APP_SYSLOGER.monitrc")){
            echo "/etc/monit/conf.d/APP_SYSLOGER.monitrc failed !!!\n";
        }
        if($RESTART){
            shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
        }

        return true;

    }





function start($aspid=false){
	if(is_file("/etc/artica-postfix/FROM_ISO")){if(!is_file("/etc/artica-postfix/artica-iso-setup-launched")){return;}}
	$unix=new unix();

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		@file_put_contents("/etc/artica-postfix/exec.syslog.php.pid",$pid);
		return;
	}
	
	
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Performance Level:$SquidPerformance\n";}
	

	$php5=$unix->LOCATE_PHP5_BIN();
	$tail=$unix->find_program("tail");
	$ulimit=$unix->find_program("ulimit");
	shell_exec("$ulimit -u unlimited >/dev/null 2>&1");
	$syslog=$unix->LOCATE_SYSLOG_PATH();
	if($syslog==null){$syslog="/var/log/syslog";}
    $md52=null;
    $tailmd=md5_file($tail);

	if(is_file("/usr/sbin/syslog-tail")){
        $md52=md5_file("/usr/sbin/syslog-tail");
    }
    if($md52<>$tailmd){
        @unlink("/usr/sbin/syslog-tail");
        @copy($tail,"/usr/sbin/syslog-tail");
        @chmod("/usr/sbin/syslog-tail",0755);
    }
    build_monit();

	$prefix="/usr/sbin/syslog-tail -f -n 0 $syslog";
	$TAIL_STARTUP="$php5 /usr/share/artica-postfix/exec.syslog.php";
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} tail `$tail`\n";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} syslog `$syslog`\n";}
	
	if(!is_file($syslog)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} syslog no such file\n";}
		return;
	}
	
	$cmd="$prefix | $TAIL_STARTUP >/dev/null 2>&1 &";
	shell_exec($cmd);
	
	for($i=1;$i<3;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/3\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		stop_tail_instances();
		return;
	}
	$pid=PID_NUM();

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
    unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
        unix_system_kill_force($pid);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		stop_tail_instances();
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
    shell_exec("/usr/bin/kill -9 `/usr/bin/pidof /usr/sbin/syslog-tail`");
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		stop_tail_instances();
		return;
	}
    stop_tail_instances();

}

function stop_tail_instances(){
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$syslog=$unix->LOCATE_SYSLOG_PATH();
	$prefix="-f -n 0 $syslog";
	$pid=$unix->PIDOF_PATTERN($prefix);
	if($unix->process_exists($pid)) {
        for ($i = 0; $i < 15; $i++) {
            $pid = $unix->PIDOF_PATTERN($prefix);
            if (!$unix->process_exists($pid)) {
                return;
            }
            echo "Stopping......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} killing $pid tail instance\n";
            shell_exec("$kill -9 $pid >/dev/null 2>&1");
        }
    }
    $pgrep=$unix->find_program("pgrep");
    exec("$pgrep -l -f \"/bin/tail -f -n 0 /var/log/syslog\" 2>&1",$results);

    foreach ($results as $line){
        if(!preg_match("#^([0-9]+)\s+(.*)#",$line,$re)){continue;}
        if(preg_match("#pgrep#",$line)){continue;}
        $pid=$re[1];
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} killing $pid tail instance\n";
        shell_exec("$kill -9 $pid >/dev/null 2>&1");
    }

	
}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/etc/artica-postfix/exec.syslog.php.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF("/usr/sbin/syslog-tail");
	
}
?>