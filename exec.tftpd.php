<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="TFTP Daemon";
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
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install_service();exit();}
if($argv[1]=="--check"){$GLOBALS["OUTPUT"]=true;check();exit();}


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

function check(){
	$EnableTFTPDServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTFTPDServer"));
	if($EnableTFTPDServer==1){
		if(!is_file("/etc/init.d/tftpd")){install_service();}
		start();
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
		return;
	}else{
		if(is_file("/etc/init.d/tftpd")){remove_service("/etc/init.d/tftpd");}
		@unlink("/etc/monit/conf.d/APP_TFTPD.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
		return;
	}
	
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("in.tftpd");
	$EnableTFTPDServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTFTPDServer"));

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, in.tftpd not installed\n";}
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
		@file_put_contents("/var/run/tftpd.pid", $pid);
		return;
	}
	

	if($EnableTFTPDServer==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableTFTPDServer)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$TFTPDListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TFTPDListenInterface"));
	$TFTPDDirectory=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TFTPDDirectory"));
	$TFTPDListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TFTPDListenPort"));
	if($TFTPDListenPort==0){$TFTPDListenPort=69;}
	if($TFTPDDirectory==null){$TFTPDDirectory="/home/kiosk/tftp";}
	
	
	$Interfaces=$unix->NETWORK_ALL_INTERFACES();
	if($Interfaces[$TFTPDListenInterface]["IPADDR"]==null){$Interfaces[$TFTPDListenInterface]="0.0.0.0";}
	@mkdir($TFTPDDirectory,0755,true);
	$unix->CreateUnixUser("kiosk","kiosk","kiosk user");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $TFTPDListenInterface......: {$Interfaces[$TFTPDListenInterface]["IPADDR"]}\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Directory......: $TFTPDDirectory\n";}
	
	$cmd="$Masterbin -l -v -a {$Interfaces[$TFTPDListenInterface]["IPADDR"]}:{$TFTPDListenPort} -p -u kiosk -s $TFTPDDirectory >/dev/null 2>&1 &";
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
		@file_put_contents("/var/run/tftpd.pid", $pid);
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		@unlink("/var/run/tftpd.pid");
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
		@unlink("/var/run/tftpd.pid");
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

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}
	@unlink("/var/run/tftpd.pid");
}

function PID_NUM(){
	
	$unix=new unix();
	$Masterbin=$unix->find_program("in.tftpd");
	return $unix->PIDOF($Masterbin);
	
}
function install_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$php5script=basename(__FILE__);
	
	$f=array();
	$f[]="check process APP_TFTPD";
	$f[]="with pidfile /var/run/tftpd.pid";
	$f[]="start program = \"/etc/init.d/tftp start --monit\"";
	$f[]="stop program =  \"/etc/init.d/tftp stop --monit\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	@file_put_contents("/etc/monit/conf.d/APP_TFTPD.monitrc", @implode("\n", $f));
	$f=array();
	//********************************************************************************************************************
	
	
	$f[]="#! /bin/sh";
	$f[]="";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:		tftp";
	$f[]="# Required-Start:	\$remote_fs \$syslog";
	$f[]="# Required-Stop:	\$remote_fs \$syslog";
	$f[]="# Default-Start:	2 3 4 5";
	$f[]="# Default-Stop:		";
	$f[]="# Short-Description: IPv4 Trivial File Transfer Protocol server";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="set -e";
	$f[]="";

	$f[]="case \"\$1\" in";
	$f[]=" start)";

	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	$f[]="";


	echo "tftp: [INFO] Writing /etc/init.d/tftp with new config\n";
	@unlink("/etc/init.d/tftp");
	@file_put_contents("/etc/init.d/tftp", @implode("\n", $f));


	@chmod("/etc/init.d/tftp",0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename("/etc/init.d/tftp")." defaults >/dev/null 2>&1");

	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename("/etc/init.d/tftp")." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename("/etc/init.d/tftp")." on >/dev/null 2>&1");
	}
}
?>