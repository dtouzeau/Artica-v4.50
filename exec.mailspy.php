<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["MONIT"]=false;
$GLOBALS["TITLENAME"]="Milter MailSpy";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--monit#",implode(" ",$argv),$re)){$GLOBALS["MONIT"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
if($argv[1]=="--scan"){scan();exit();}
if($argv[1]=="--parse"){parse();exit();}

function build_progress_restart($pourc,$text){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/mailspy.install", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/mailspy.install",0755);


}
function build_progress_install($pourc,$text){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/mailspy.install", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/mailspy.install",0755);


}
function install(){
	$unix=new unix();
	build_progress_install(10,"{installing}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableMilterSpyDaemon",1);
	mailspy_service();
	
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_install(20,"{configuring}");
	shell_exec("$php /usr/share/artica-postifx/exec.postfix.maincf.php --milters");
	build_progress_install(50,"{starting_service}");
	if(!start(true)){
		uninstall();
		build_progress_install(110,"{failed}");
		return;
	}
	build_progress_install(80,"{APP_MONIT}");
	build_monit();
	build_progress_install(100,"{done}");
	
}
function uninstall(){
	$unix=new unix();
	build_progress_install(10,"{uninstalling}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableMilterSpyDaemon",0);
	
	build_progress_install(50,"{uninstalling}");
	remove_service("/etc/init.d/mailspy");
	if(is_file("/etc/monit/conf.d/APP_MAILSPY.monitrc")){
		@unlink("/etc/monit/conf.d/APP_MAILSPY.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
		
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_install(20,"{configuring}");
	shell_exec("$php /usr/share/artica-postifx/exec.postfix.maincf.php --milters");
	
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function build_monit(){

	$f[]="check process APP_MAILSPY with pidfile /var/run/mailspy.pid";
	$f[]="\tstart program = \"/etc/init.d/mailspy start\"";
	$f[]="\tstop program = \"/etc/init.d/mailspy stop --monit\"";

	$f[]="\tif failed unixsocket /var/spool/postfix/var/run/mailspy/mailspy.sock then restart";
	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_MAILSPY.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_MAILSPY.monitrc")){
		echo "/etc/monit/conf.d/APP_MAILSPY.monitrc failed !!!\n";
	}
	echo "{$GLOBALS["TITLENAME"]}: [INFO] Writing /etc/monit/conf.d/APP_MAILSPY.monitrc\n";
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");

}

function restart($aspid=false) {
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		 	return build_progress_restart(110,"{restart}:{failed}");
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	build_progress_restart(50,"{stopping_service}");
	if(!stop(true)){
		build_progress_restart(110,"{stopping_service} {failed}");
		return;
	}
	build_progress_restart(70,"{starting_service}...");
	sleep(1);
	if(!start(true)){
		build_progress_restart(110,"{starting_service} {failed}");
		return;
	}
	build_progress_restart(100,"{success}");
	
}
function fuser_port(){
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$PIDS=$unix->PIDOF_BY_PORT(80);
	if(count($PIDS)==0){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} 0 PID listens 80...\n";}

		return;}
		foreach ($PIDS as $pid=>$b){
			if($unix->process_exists($pid)){
				$cmdline=@file_get_contents("/proc/$pid/cmdline");
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} killing PID $pid that listens 80 TCP port\n";}
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmdline\n";}
				unix_system_kill_force($pid);
			}
		}
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();


	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return true;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$pid=PID_NUM();
	
	
	

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		@file_put_contents("/var/run/mailspy.pid", $pid);
		return true;
	}
	$nohup=$unix->find_program("nohup");
	@mkdir("/var/spool/postfix/var/run/mailspy",0755,true);
	$cmd="$nohup /usr/libexec/postfix/mailspy -p unix:/var/spool/postfix/var/run/mailspy/mailspy.sock -f /var/log/mailspy.log >/dev/null 2>&1 &";
	
	
	@unlink("/var/spool/postfix/var/run/mailspy/mailspy.sock");
	shell_exec($cmd);
	
	
	

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		@file_put_contents("/var/run/mailspy.pid", $pid);
		return true;
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		return false;
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
		return true;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<10;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		usleep(1000);
		unix_system_kill($pid);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return true;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<10;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		usleep(900);
		unix_system_kill_force($pid);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return false;
	}
	@unlink("/var/spool/postfix/var/run/mailspy/mailspy.sock");
	return true;
}

function PID_NUM(){
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/mailspy.pid");
	if($unix->process_exists($pid)){
		return $pid;
	}
	
	return $unix->PIDOF("/usr/libexec/postfix/mailspy");
	
}


function mailspy_service(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	$INITD_PATH="/etc/init.d/mailspy";

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          mailspy";
		$f[]="# Required-Start:    \$local_fs \$syslog";
		$f[]="# Required-Stop:     \$local_fs";
		$f[]="# Should-Start:";
		$f[]="# Should-Stop:";
		$f[]="# Default-Start:     3 4 5";
		$f[]="# Default-Stop:      0 1 6";
		$f[]="# Short-Description: Milter Spy daemon";
		$f[]="# chkconfig: 2345 11 89";
		$f[]="# description: Milter Spy daemon";
		$f[]="### END INIT INFO";
		$f[]="case \"\$1\" in";
		$f[]=" start)";
		$f[]="   $php ".__FILE__." --start \$2 \$3";
		$f[]="	 exit 0";
		$f[]="    ;;";
		$f[]="";
		$f[]="  stop)";
		$f[]="   $php ".__FILE__." --stop \$2 \$3";
		$f[]="    ;;";
		$f[]="";
		$f[]=" restart)";
		$f[]="   $php ".__FILE__." --restart \$2 \$3";
		$f[]="	 exit 0";
		$f[]="    ;;";
		$f[]="";
		$f[]=" reload)";
		$f[]="   $php ".__FILE__." --reload \$2 \$3";
		$f[]="	 exit 0";
		$f[]="    ;;";
		$f[]="";
		$f[]="  *)";
		$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
		$f[]="    exit 1";
		$f[]="    ;;";
		$f[]="esac";
		$f[]="exit 0\n";


	
		@unlink($INITD_PATH);
		@file_put_contents($INITD_PATH, @implode("\n", $f));
		@chmod($INITD_PATH,0755);

		if(is_file('/usr/sbin/update-rc.d')){
			shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
		}

		if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
			shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
		}
}

function Scan(){
	$unix=new unix();
	$time=time();
	$mv=$unix->find_program("mv");
	mkdir("/home/artica/postfix/mailspy");
	if(!@copy("/var/log/mailspy.log", "/home/artica/postfix/mailspy/$time.log")){
		if($GLOBALS["VERBOSE"]){echo "/var/log/mailspy.log -> /home/artica/postfix/mailspy/$time.log FAILED\n";}
		return;}
	@unlink("/var/log/mailspy.log");
	restart(true);
}

function parse(){
	$BaseWorkDir="/home/artica/postfix/mailspy";
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {
		if($GLOBALS["VERBOSE"]){echo "$BaseWorkDir FATAL\n";}
		return;}
	
	if($GLOBALS["VERBOSE"]){echo "$BaseWorkDir OPEN\n";}
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if($GLOBALS["VERBOSE"]){echo "$targetFile\n";}
		if(is_dir($targetFile)){continue;}
		if(!parse_file($targetFile)){continue;}
		@unlink($targetFile);
	}
	
	closedir($handle);
}

function parse_file($targetFile){
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."] $targetFile OPEN\n";}
	$handle=fopen($targetFile,'r');
	if(!$handle){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__."] $targetFile unable to open\n";}
		return false;}
	
	$c=0;
	while (!feof($handle)) {
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__."] LINE $c\n";}
		$value=trim(fgets($handle));
		if($value==null){continue;}
		$f=explode("\t",$value);
		$zdate=0;
		$from=null;
		$to=null;
		$subject=null;
		$size=0;
		$attach=array();
		foreach ($f as $field){
			if(preg_match("#time=([0-9]+)#", $field,$re)){$zdate=$re[1];}
			if(preg_match("#from=<(.+?)>#", $field,$re)){$from=$re[1];}
			if(preg_match("#to=<(.+?)>#", $field,$re)){$to=$re[1];}
			if(preg_match("#subject=(.*)#",$field,$re)){$subject=$re[1];}
			if(preg_match("#size=([0-9]+)#",$field,$re)){$size=$re[1];}
			if(preg_match("#file=(.*)#", $field,$re)){$attach[]=$re[1];}
		}
		echo "From $from to $to ".date("Y-m-d H:i:s",$zdate)."\n";
		
		
		$c++;
	}
	fclose($handle);
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."] $c lines processed\n";}
	if($c==0){return true;}
	
}




?>