<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOBUILD"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Webfilter HTTP engine";
$GLOBALS["PIDFILE"]="/var/run/webfilter-http.pid";
$GLOBALS["PYTHONPGR"]="/usr/share/artica-postfix/webfilter-http.py";

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--nobuild#",implode(" ",$argv),$re)){$GLOBALS["NOBUILD"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.apache.certificate.php');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--install-web"){$GLOBALS["OUTPUT"]=true;INSTALL_WEB_PROGRESS();exit();}
if($argv[1]=="--uninstall-web"){$GLOBALS["OUTPUT"]=true;UNINSTALL_WEB_PROGRESS();exit();}

function build_progress_restart($text,$pourc){
	$filename=PROGRESS_DIR."/microhotspot.web.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($filename, serialize($array));
	@chmod($filename,0777);

}

function restart() {
	UNINSTALL_WEB_PROGRESS();
	
}

function build(){
	UNINSTALL_WEB_PROGRESS();
}


function start($aspid=false){
	UNINSTALL_WEB_PROGRESS();
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
	$pid=$unix->get_pid_from_file($GLOBALS["PIDFILE"]);
	if($unix->process_exists($pid)){
		return $pid;
	}
	
	return $unix->PIDOF_PATTERN($GLOBALS["PYTHONPGR"]);
	
}
function INSTALL_WEB_PROGRESS(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableUfdbErrorPage", 1);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSquidGuardHTTPService", 1);
	
	
	
	build_progress_install("{creating_service}",54);
	remove_service("/etc/init.d/squidguard-http");
	squidguard_http_service();
	build_progress_install("{creating_service}",55);
	system("$php /usr/share/artica-postfix/exec.ufdb-lighthttp.php --uninstall-web");
	build_progress_install("{restarting_service}",56);
	system("/etc/init.d/ufdb-http restart");
	monit();
	build_progress_install("{reconfiguring}",57);
	build();
	if(!$GLOBALS["NOBUILD"]){ 
		build_progress_install("{reconfiguring}",80);
		system("$php /usr/share/artica-postfix/exec.squidguard.php --build --force"); 
	}
	build_progress_install("{done}",100);
}
function squidguard_http_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/ufdb-http";
	$php5script="exec.ufdb-http.php";
	$daemonbinLog="Ufdbguard Web page error";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         ufdb-http";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";

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
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";


	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
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

function monit(){
	
	$SquidGuardApachePort=intval($GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidGuardApachePort"));
	if($SquidGuardApachePort == 0){ $SquidGuardApachePort=9020;}
		
	$f[]="check process APP_UFDB_HTTP";
	$f[]="with pidfile /var/run/webfilter-http.pid";
	$f[]="start program = \"/etc/init.d/ufdb-http start --monit\"";
	$f[]="stop program =  \"/etc/init.d/ufdb-http stop --monit\"";
	$f[]="if failed host 127.0.0.1 port $SquidGuardApachePort then restart";
	$f[]="if 5 restarts within 5 cycles then timeout";
	$f[]="";
	@file_put_contents("/etc/monit/conf.d/ufdbweb.monitrc", @implode("\n", $f));
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	
}

function UNINSTALL_WEB_PROGRESS(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableUfdbErrorPage", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSquidGuardHTTPService", 0);
	build_progress_install("{remove_service}",55);
	remove_service("/etc/init.d/ufdb-http");
	build_progress_install("{restarting_service}",56);
	@unlink("/etc/monit/conf.d/ufdbweb.monitrc");
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	build_progress_install("{reconfiguring}",56);
	build();
	system("$php /usr/share/artica-postfix/exec.squidguard.php --build --force");
	build_progress_install("{done}",100);
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

function build_progress_install($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/ufdb.enable.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	sleep(1);

}


?>