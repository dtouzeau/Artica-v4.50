<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="ARP Daemon";
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
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install_initd();exit();}



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
	start(true);
	
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
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
	
	if($unix->MEM_TOTAL_INSTALLEE()<624288){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not enough memory\n";}
		if($unix->process_exists($pid)){stop();}
		return;
	}

	

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	$EnableArpDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArpDaemon"));
	$ArpdKernelLevel=$sock->GET_INFO("ArpdKernelLevel");
	
	if(!is_numeric($ArpdKernelLevel)){$ArpdKernelLevel=0;}
	

	if($EnableArpDaemon==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableArpDaemon)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	
	if (intval($ArpdKernelLevel)>0){$ArpdKernelLevel_string=" -a $ArpdKernelLevel";}
	$Interfaces=$unix->NETWORK_ALL_INTERFACES();
	$nic=new system_nic();
    foreach ($Interfaces as $Interface=>$ligne){
		if($Interface=="lo"){continue;}
		if($Interface=="tun0"){continue;}
		if($ligne["IPADDR"]=="0.0.0.0"){continue;}
		$Interface=$nic->NicToOther($Interface);
		$TRA[$Interface]=$Interface;
	}
    foreach ($TRA as $Interface=>$ligne){
	$TR[]=$Interface; }
	@mkdir('/var/lib/arpd',0755,true);
	
	$f[]="$Masterbin -b /var/lib/arpd/arpd.db";
	$f[]=$ArpdKernelLevel_string;
	
	if(count($TR)>0){
		$f[]="-k ".@implode($TR," ");
	}
	
	
	$cmd=@implode(" ", $f) ." >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	
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

}

function PID_NUM(){
	
	$unix=new unix();
	$Masterbin=$unix->find_program("arpd");
	return $unix->PIDOF($Masterbin);
	
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");

	}

	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function uninstall(){
	
	if(is_file("/etc/init.d/webdav-service")){
		remove_service("/etc/init.d/webdav-service");
	}
	if(is_file("/etc/monit/conf.d/APP_DAVSERVICE.monitrc")){
		@unlink("/etc/monit/conf.d/APP_DAVSERVICE.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
	}

	
}

function install_initd(){

	$unix=new unix();
	
	$WebDavSquidLogsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsEnabled"));
	
	if($WebDavSquidLogsEnabled==0){uninstall();return;}
	
	
	$WebDavSquidLogsPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsPort"));
	$WebDavSquidLogsInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsInterface");
	$WebDavSquidLogsUsername=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsUsername");
	$WebDavSquidLogsPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsPassword");
	$WebDavSquidLogsAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsInterface"));
	$BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	if($WebDavSquidLogsPort==0){$WebDavSquidLogsPort=8008;}
	if($WebDavSquidLogsInterface==null){$WebDavSquidLogsInterface="eth0";}
	$host="0.0.0.0";
	if(!is_file("/usr/local/bin/davserver")){return;}
	
	$NET_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$host=$NET_INTERFACES[$WebDavSquidLogsInterface]["IPADDR"];
	$php=$unix->LOCATE_PHP5_BIN();
	
	
	$t[]="--directory \"$BackupMaxDaysDir\"";
	$t[]="--host $host";
	$t[]="--port $WebDavSquidLogsPort";
	if($WebDavSquidLogsAuth==0){
		$t[]="--noauth";
	}else{
		$t[]="--user \"$WebDavSquidLogsUsername\"";
		$t[]="--password \"$WebDavSquidLogsPassword\"";
	}
	//$t[]="--loglevel ERROR";
	$t[]="--daemon";
$cmdline="/usr/local/bin/davserver ".@implode(" ", $t);

	$f[]="#!/bin/sh";
	$f[]="";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:        webdav-service";
	$f[]="# Required-Start:  \$network \$remote_fs \$syslog";
	$f[]="# Required-Stop:   \$network \$remote_fs \$syslog";
	$f[]="# Default-Start:   2 3 4 5";
	$f[]="# Default-Stop: ";
	$f[]="# Short-Description: Start Dav server Daemon";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="PATH=/sbin:/bin:/usr/sbin:/usr/bin";
	$f[]="";
	$f[]=". /lib/lsb/init-functions";
	$f[]="";
	$f[]="DAEMON=/usr/local/bin/davserver";
	$f[]="PIDFILE=/var/run/davserver.pid";
	$f[]="WEBDAV_OPTS=".@implode(" ", $t);
	$f[]="";
	$f[]="test -x \$DAEMON || exit 5";
	$f[]="";
	$f[]="";
	$f[]="case \$1 in";
	$f[]="	start)";
	$f[]="		log_daemon_msg \"Starting WebDav server\" \"davserver\"";
	$f[]="  	$cmdline start";
	$f[]="		status=\$?";
	$f[]="		log_end_msg \$status";
	$f[]="  		;;";
	$f[]="	stop)";
	$f[]="		log_daemon_msg \"Stopping WebDav server\" \"davserver\"";
	$f[]="		$cmdline stop";
	$f[]="		log_end_msg \$?";
	$f[]="  		;;";
	$f[]="	restart|force-reload)";
	$f[]="		log_daemon_msg \"Stopping WebDav server\" \"davserver\"";
	$f[]="		$cmdline stop";
	$f[]="		log_daemon_msg \"Starting WebDav server\" \"davserver\"";
	$f[]="		$php ".__FILE__." --install >/dev/null";
	$f[]="		log_end_msg \$?";
	$f[]="  		;;";
	$f[]="	try-restart)";
	$f[]="		log_daemon_msg \"Stopping WebDav server\" \"davserver\"";
	$f[]="		$cmdline stop";
	$f[]="		log_daemon_msg \"Starting WebDav server\" \"davserver\"";
	$f[]="		$php ".__FILE__." --install >/dev/null";
	$f[]="		log_end_msg \$?";
	$f[]="		;;";
	$f[]="	reload)";
	$f[]="		log_daemon_msg \"Stopping WebDav server\" \"davserver\"";
	$f[]="		$cmdline stop";
	$f[]="		log_daemon_msg \"Starting WebDav server\" \"davserver\"";
	$f[]="		$cmdline start";
	$f[]="		log_end_msg \$?";
	$f[]="		;;";
	$f[]="	status)";
	$f[]="		$cmdline status";
	$f[]="		;;";
	$f[]="	*)";
	$f[]="		echo \"Usage: \$0 {start|stop|restart|try-restart|force-reload|status}\"";
	$f[]="		exit 2";
	$f[]="		;;";
	$f[]="esac";
	$f[]="";
	$INITD_PATH="/etc/init.d/webdav-service";
	echo "DAV: [INFO] Writing $INITD_PATH with new config\n";
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
	
	
	$f=array();
	$f[]="check process APP_DAVSERVICE with pidfile /tmp/pydav0.pid";
	$f[]="\tstart program = \"$INITD_PATH start --monit\"";
	$f[]="\tstop program = \"$INITD_PATH stop --monit\"";

	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_DAVSERVICE.monitrc", @implode("\n", $f));
    $unix->reload_monit();
	echo "DAV: [INFO] Writing /etc/monit/conf.d/APP_DAVSERVICE.monitrc with new config\n";
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $INITD_PATH start >/dev/null 2>&1");
	
}
?>