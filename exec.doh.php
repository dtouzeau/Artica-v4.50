<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="DNS Over HTTPS Daemon";
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
if($argv[1]=="--monit"){$GLOBALS["OUTPUT"]=true;monit_install();exit();}






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
	build_progress(20, "{stopping_service}");
	stop(true);
	sleep(1);
	build_progress(50, "{reconfiguring}");
	build();
	build_progress(90, "{starting_service}");
	start(true);
	
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		build_progress(110, "{starting_service} {failed}");
		return;
	}
	build_progress(100, "{starting_service} {success}");
	
}

function build_progress($pourc,$text){
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/doh.install";
	echo "$date: [{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
}

function install(){
	build_progress(20, "{creating_service}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DOHServerEnabled", 1);
	install_service();
	monit_install();
	build_progress(60, "{stopping_service}");
	stop(true);
	sleep(1);
	build();
	$unix=new unix();
	//$unix->Popuplate_cron_make("DSC-schedule","*/10 * * * *",basename(__FILE__)." --parse");
	//UNIX_RESTART_CRON();
	
	build_progress(90, "{starting_service}");
	start(true);
    $unix->framework_exec("/usr/sbin/artica-phpfpm-service -restart-artica-status");

    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $q->QUERY_SQL("UPDATE nginx_services SET enabled=1 WHERE `type`=7");

	build_progress(100, "{done}");
}
function uninstall(){
	build_progress(20, "{remove_service}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DOHServerEnabled", 0);
	remove_service("/etc/init.d/doh");
	if(is_file("/etc/monit/conf.d/APP_DOH_SERVER.monitrc")){
		@unlink("/etc/monit/conf.d/APP_DOH_SERVER.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	}
	system("/etc/init.d/artica-status restart --force");

    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $q->QUERY_SQL("UPDATE nginx_services SET enabled=0 WHERE `type`=7");
	build_progress(100, "{done}");
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
function install_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          doh";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network \$time \$pdns";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: DNS Over HTTPS framework";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, DNS Over HTTPS framework";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";

	$f[]="   $php /usr/share/artica-postfix/exec.doh.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php /usr/share/artica-postfix/exec.doh.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php /usr/share/artica-postfix/exec.doh.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php /usr/share/artica-postfix/exec.doh.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/doh";
	echo "PDNS: [INFO] Writing $INITD_PATH with new config\n";
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

function start($aspid=false){
	$unix=new unix();

	$Masterbin=$unix->find_program("doh-server");
    $nohup=$unix->find_program("nohup");
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, doh-server not installed\n";}
		return;
	}

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
		@file_put_contents("/var/run/doh-server.pid", $pid);
		return;
	}

	$cmd="$nohup $Masterbin -conf /etc/doh-server/doh-server.conf >/var/log/doh.log 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	system($cmd);

	
	

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)) {
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";
        }
        @file_put_contents("/var/run/doh-server.pid", $pid);
        return true;
    }

    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	@unlink("/var/run/doh-server.pid");
	return false;


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
        @unlink("/var/run/doh-server.pid");
		return true;
	}
	$pid=PID_NUM();


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
        @unlink("/var/run/doh-server.pid");
		return true;
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
		return false;
	}
    @unlink("/var/run/doh-server.pid");
	return true;

}

function build(){

$f[]="listen = [";
$f[]="    \"127.0.0.1:9053\",";
$f[]="";
$f[]="]";
$f[]="# TLS certification file";
$f[]="# If left empty, plain-text HTTP will be used.";
$f[]="# You are recommended to leave empty and to use a server load balancer (e.g.";
$f[]="# Caddy, Nginx) and set up TLS there, because this program does not do OCSP";
$f[]="# Stapling, which is necessary for client bootstrapping in a network";
$f[]="# environment with completely no traditional DNS service.";
$f[]="cert = \"\"";
$f[]="# TLS private key file";
$f[]="key = \"\"";
$f[]="# HTTP path for resolve application";
$f[]="path = \"/dns-query\"";
$f[]="# Upstream DNS resolver";
$f[]="# If multiple servers are specified, a random one will be chosen each time.";
$f[]="upstream = [";
$f[]="    \"127.0.0.1:53\",";
$f[]="]";
$f[]="# Upstream timeout";
$f[]="timeout = 60";
$f[]="# Number of tries if upstream DNS fails";
$f[]="tries = 10";
$f[]="# Only use TCP for DNS query";
$f[]="tcp_only = false";
$f[]="# Enable logging";
$f[]="verbose = true";
$f[]="";
	
	@mkdir("/etc/doh-server",0755,true);
	@file_put_contents("/etc/doh-server/doh-server.conf", @implode("\n", $f));

}

function monit_install(){
	
	$f=array();
	$f[]="check process APP_DOH_SERVER";
	$f[]="with pidfile /var/run/doh-server.pid";
	$f[]="start program = \"/etc/init.d/doh start --monit\"";
	$f[]="stop program =  \"/etc/init.d/doh stop --monit\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	@file_put_contents("/etc/monit/conf.d/APP_DOH_SERVER.monitrc", @implode("\n", $f));
	$f=array();
	//********************************************************************************************************************
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}


function PID_NUM(){
	
	$unix=new unix();
	$MasterBin=$unix->find_program("doh-server");
	return $unix->PIDOF($MasterBin);
	
}
?>