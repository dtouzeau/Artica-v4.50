<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');


if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
}

if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--start-syslog"){start_syslog();exit;}
if($argv[1]=="--stop-syslog"){stop_syslog();exit;}





function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/frontail.install.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function PID_SYSLOG(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/frontail-syslog.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN("frontail-linux.*?syslog");
}

function start_syslog($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("frontail-linux");
	$GLOBALS["TITLENAME"]="frontail-linux (syslog)";
	$GLOBALS["OUTPUT"]=true;

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, frontail-linux not installed\n";}
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

	$pid=PID_SYSLOG();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		@file_put_contents("/var/run/frontail-syslog.pid", $pid);
		return;
	}


	$php5=$unix->LOCATE_PHP5_BIN();
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");

	
	$cmd="$nohup $Masterbin --daemonize --theme default --url-path /syslog --ui-highlight-preset /etc/frontail.json --log-path /var/log/fontail.log --host 127.0.0.1 --port 2889 --pid-path /var/run/frontail-syslog.pid /var/log/syslog >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	presets();
	shell_exec($cmd);




	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_SYSLOG();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_SYSLOG();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}




function stop_syslog($aspid=false){
	$GLOBALS["TITLENAME"]="frontail-linux (syslog)";
	$GLOBALS["OUTPUT"]=true;
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

	$pid=PID_SYSLOG();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_SYSLOG();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");




	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_SYSLOG();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_SYSLOG();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_SYSLOG();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}


function presets(){

    $MAIN["words"]=array(
        "err"=>"color:red;",
        "failed"=>"color:red;",
        "fatal"=>"color:red;",
        "emerg"=>"color:red;",


    );
    $MAIN["lines"]=array( "err"=>"font-weight: bold;","failed"=>"font-weight: bold;");



    if(function_exists("json_encode")) {
        @file_put_contents("/etc/frontail.json", @json_encode($MAIN));
    }
	
}


function install(){
	$unix=new unix();
	$binary=$unix->find_program("frontail-linux");
	if(!is_file($binary)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFrontail", 0);
		build_progress(110, "{failed} frontail-linux no such file");
		return;
	}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFrontail", 1);
	if(!is_file("/etc/init.d/frontail-syslog")){
	build_progress(15, "{installing} {service}");
		frontail_syslog_service();
		build_progress(25, "{installing} {APP_MONIT}");
		monit_syslog();
	}
	build_progress(50, "{reconfiguring}");
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	build_progress(100, "{success} {APP_FRONTAIL_LINUX}");
	

}

function uninstall(){
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFrontail", 0);
	build_progress(15, "{uninstalling} {APP_FRONTAIL_LINUX}");

	build_progress(20, "{uninstalling} {APP_FRONTAIL_SYSLOG}");
	remove_service("/etc/init.d/frontail-syslog");
	build_progress(20, "{uninstalling} {APP_FRONTAIL_MAILLOG}");
	remove_service("/etc/init.d/frontail-maillog");
	
	
	if(is_file("/etc/monit/conf.d/APP_FRONTAIL_SYSLOG.monitrc")){@unlink("/etc/monit/conf.d/APP_FRONTAIL_SYSLOG.monitrc");}
	if(is_file("/etc/monit/conf.d/APP_FRONTAIL_MAILLOG.monitrc")){@unlink("/etc/monit/conf.d/APP_FRONTAIL_MAILLOG.monitrc");}

	build_progress(100, "{success} {uninstalling} {APP_FRONTAIL_LINUX}");
	
}

function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function monit_syslog(){
	$f[]="check process APP_FRONTAIL_SYSLOG with pidfile /var/run/frontail-syslog.pid";
	$f[]="\tstart program = \"/etc/init.d/frontail-syslog start\"";
	$f[]="\tstop program = \"/etc/init.d/frontail-syslog stop\"";
    $f[]="\tif failed host 127.0.0.1 port 2889 then restart";
	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_FRONTAIL_SYSLOG.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_FRONTAIL_SYSLOG.monitrc")){
		echo "/etc/monit/conf.d/APP_FRONTAIL_SYSLOG.monitrc failed !!!\n";
	}
	echo "Munin-node: [INFO] Writing /etc/monit/conf.d/APP_FRONTAIL_SYSLOGs.monitrc\n";
	
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}



function frontail_syslog_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/frontail-syslog";
	$php5script=basename(__FILE__);
	$daemonbinLog="Frontail for Syslog Web console";
	$chmod=$unix->find_program("chmod");
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          ".basename($INITD_PATH);
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
	$f[]="    $php /usr/share/artica-postfix/$php5script --start-syslog \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop-syslog \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop-syslog \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start-syslog \$2 \$3";
	
	$f[]="    ;;";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop-syslog \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start-syslog \$2 \$3";
	$f[]="    ;;";	
	$f[]=" restart-paused)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop-syslog \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start-syslog \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
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
		shell_exec("/usr/sbin/update-rc.d -f ".basename($INITD_PATH)." remove");
	
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}




