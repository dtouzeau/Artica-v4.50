<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Network Monitor Daemon";
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
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}


function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	sleep(1);
	@file_put_contents("/etc/artica.darkstats", build());
	start(true);
	
}

function build(){
	if(isset($GLOBALS["CMDLINE_BUILD"])){return $GLOBALS["CMDLINE_BUILD"];}
	$unix=new unix();
	$DarkStatHome=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DarkStatHome");
	$DarkStatInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DarkStatInterface");
	if($DarkStatHome==null){$DarkStatHome="/home/artica/darkstat";}
	
	
	$DarkStatWebInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DarkStatWebInterface");
	$DarkStatWebPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DarkStatWebPort"));
	
	@mkdir($DarkStatHome,0755,true);
	if($DarkStatWebInterface==null){$DarkStatWebInterface="eth0";}
	if($DarkStatWebPort==0){$DarkStatWebPort=663;}
	if($DarkStatInterface==null){$DarkStatInterface="eth0";}
	if($DarkStatHome==null){$DarkStatHome="/home/artica/darkstat";}
	
	$DarkStatNetwork=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DarkStatNetwork"));
	if($DarkStatNetwork==null){$DarkStatNetwork="192.168.0.0/255.255.0.0";}
	if(!preg_match("#([0-9\.]+)\/([0-9\.]+)#", $DarkStatNetwork)){$DarkStatNetwork="192.168.0.0/255.255.0.0";}
	
	
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$ipaddr=$NETWORK_ALL_INTERFACES[$DarkStatInterface]["IPADDR"];
	
	$cmd[]="/usr/share/artica-postfix/bin/darkstat";
	$cmd[]="-i $DarkStatInterface";
	if($DarkStatNetwork<>"0.0.0.0/0.0.0.0"){
		$cmd[]="-l $DarkStatNetwork";	
	}
	
	$cmd[]="--user root";
	$cmd[]="--chroot $DarkStatHome";
	if($ipaddr<>null){
		$cmd[]="-b $ipaddr";
	}
	$cmd[]="-p $DarkStatWebPort";
	$cmd[]="--syslog";
	$cmd[]="--export darkstat.db";
	$cmd[]="--import darkstat.db";
	$cmd[]="--pidfile /var/run/darkstat.pid";
	$cmd[]=">/dev/null 2>&1 &";
	$GLOBALS["CMDLINE_BUILD"]=@implode(" ", $cmd);
	return $GLOBALS["CMDLINE_BUILD"];
}


function start($aspid=false){
	$unix=new unix();
	$Masterbin=$unix->find_program("arpd");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, arpd not installed\n";}
		return;
	}

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
		@file_put_contents("/var/run/darkstat.pid", $pid);
		return;
	}
	$EnableDarkStat=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDarkStat"));

	@chmod("/usr/share/artica-postfix/bin/darkstat",0755);
	if(is_file("/var/run/darkstat.pid")){@unlink("/var/run/darkstat.pid");}
	


	if($EnableDarkStat==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableDarkStat)\n";}
		return;
	}
	
	$DarkStatWebPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DarkStatWebPort"));
	$unix->KILL_PROCESSES_BY_PORT($DarkStatWebPort);
	
	if(!is_file("/etc/artica.darkstats")){
		$cmdline=build();
		@file_put_contents("/etc/artica.darkstats", $cmdline);
	}else{
		$cmdline=trim(@file_get_contents("/etc/artica.darkstats"));
	}
	

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	
	shell_exec($cmdline);
	
	
	

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmdline\n";}
	}


}

function uninstall(){
	build_progress(40, "{uninstalling}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDarkStat", 0);
	remove_service("/etc/init.d/darkstat");
	build_progress(50, "{uninstalling}");
	@unlink("/etc/monit/conf.d/APP_DARKSTAT.monitrc");
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	$DarkStatHome=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DarkStatHome");
	if($DarkStatHome==null){$DarkStatHome="/home/artica/darkstat";}	
	$unix=new unix();
	$rm=$unix->find_program("rm");
	shell_exec("$rm -rf $DarkStatHome");
	build_progress(100, "{uninstalling} {done}");
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/darkstat.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}


function install(){

	build_progress(20, "{installing}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDarkStat", 1);
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress(25, "{installing}");
	system("/usr/sbin/artica-phpfpm-service -uninstall-ntopng");
	
	build_progress(30, "{installing}");
	$filename=__FILE__;
	$cmdline=build();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          darkstat";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network \$time";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: darkstat Daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable darkstat daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php $filename --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php $filename --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php $filename --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php $filename --reload \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/darkstat";
	echo "DARKSTAT: [INFO] Writing $INITD_PATH with new config\n";
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
	
	build_progress(40, "{installing}");
	monit_install();
	build_progress(50, "{restarting}");
	system("/etc/init.d/darkstat restart");
	build_progress(100, "{done}");
	
	return true;

}
function monit_install(){
	$f=array();
	$f[]="check process APP_DARKSTAT";
	$f[]="with pidfile /var/run/darkstat.pid";
	$f[]="start program = \"/etc/init.d/darkstat start --monit\"";
	$f[]="stop program =  \"/etc/init.d/darkstat stop --monit\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_DARKSTAT.monitrc", @implode("\n", $f));
	$f=array();
	//********************************************************************************************************************
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");

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
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}
	if(is_file("/var/run/darkstat.pid")){@unlink("/var/run/darkstat.pid");}
}

function PID_NUM(){
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/darkstat.pid");
	if($unix->process_exists($pid)){return $pid;}
	
	$Masterbin="/usr/share/artica-postfix/bin/darkstat";
	return $unix->PIDOF($Masterbin);
	
}
?>