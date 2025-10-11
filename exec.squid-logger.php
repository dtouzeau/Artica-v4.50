<?php

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
$GLOBALS["OUTPUT"]=true;

if($argv[1]=="--restart"){squid_logger_restart();exit;}
if($argv[1]=="--restart-logger"){squid_logger_restart();exit;}
if($argv[1]=="--create-logger"){squid_logger_create_service();build_monit();exit;}
if($argv[1]=="--install"){install();build_monit();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--stop-logger"){squid_logger_stop();exit;}
if($argv[1]=="--start-logger"){squid_logger_start();exit(1);}


function install(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress(10, "{installing}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSquidLogger", 1);

	if(!CheckDepencies()){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSquidLogger", 0);
        build_progress(110, "{failed}");
    }

	build_progress(20, "{creating_service}");
	squid_logger_create_service();
	build_monit();
	
	if(!is_file("/etc/cron.d/squid-logger")){
		$unix->Popuplate_cron_make("squid-logger", "0,5,10,15,20,25,30,35,40,45,55 * * * *","exec.squid-logger-queue.php");
		UNIX_RESTART_CRON();
	}
	
	build_progress(50, "{restarting_service}");
	if(!squid_logger_restart()){
		build_progress(110, "{restarting_service} {failed}");
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSquidLogger", 0);
		return;
	}
	build_progress(80, "{reconfigure}");
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if($SQUIDEnable==1) {
        shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --logging");
    }
	build_progress(100, "{done}");
	
	
}

function CheckDepencies(){
    $unix=new unix();
    $pip=$unix->find_program("pip");
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));

    if($SQUIDEnable==1){
        $MGR_LISTEN_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
        if($MGR_LISTEN_PORT>0){
            $proxy=" --proxy 127.0.0.1:$MGR_LISTEN_PORT ";
            echo "Starting......: ".date("H:i:s")." using proxy $proxy\n";
        }
    }

    if(!is_file($pip)){
        echo "Starting......: ".date("H:i:s")." pip not found!!!\n";
        return false;
    }
    $MAIN=GetPips();
    if($MAIN["urllib3"]<>"1.23"){
        echo "Starting......: ".date("H:i:s")." Fixing urllib3 to 1.23...\n";
        system("$pip{$proxy} install urllib3==1.23");
        $MAIN=GetPips();
        if($MAIN["urllib3"]<>"1.23"){
            echo "Starting......: ".date("H:i:s")." Fixing urllib3 to 1.23 FAILED!...\n";
            return false;
        }
    }

    if(!isset($MAIN["elasticsearch"])){
        echo "Starting......: ".date("H:i:s")." Fixing ElasticsSearch module...\n";
        system("$pip{$proxy} install elasticsearch");
        $MAIN=GetPips();
        if(!isset($MAIN["elasticsearch"])){
            echo "Starting......: ".date("H:i:s")." Fixing elasticsearch FAILED!...\n";
            return false;
        }
    }


    return true;


}

function GetPips(){
    $unix=new unix();
    $pip=$unix->find_program("pip");
    $MAIN=array();
    exec("$pip list --format=columns 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^(.+?)\s+([0-9\.]+)#",$line,$re)){continue;}
        $MAIN[$re[1]]=$re[2];
    }
    return $MAIN;
}


function uninstall(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress(10, "{uninstalling}");
	if(is_file("/etc/monit/conf.d/APP_ARTICALOGGER.monitrc")){@unlink("/etc/monit/conf.d/APP_ARTICALOGGER.monitrc");}
	if(is_file("/etc/cron.d/squid-logger")){@unlink("/etc/cron.d/squid-logger");}
	build_progress(20, "{uninstalling}");
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	UNIX_RESTART_CRON();
	build_progress(30, "{uninstalling}");
	remove_service("/etc/init.d/squid-logger");
	build_progress(50, "{uninstalling}");
	
	build_progress(80, "{reconfigure}");
    $SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
    if($SQUIDEnable==1) {
        shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --logging");
    }

	build_progress(100, "{done}");
	
}

function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/SquidLogger.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_restart($text,$pourc){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/SquidLogger.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function squid_logger_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/squid-tail.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN("squid-logger.py");

}
function squid_logger_stop(){
	$unix=new unix();
	$pid=squid_logger_pid();
	$GLOBALS["TITLENAME"]="Artica Proxy logger";

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return true;
	}
	$pid=squid_logger_pid();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	$python=$unix->find_program("python");

	system("$python /usr/share/artica-postfix/squid-logger.py stop >/dev/null 2>&1");

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}

	for($i=0;$i<5;$i++){
		$pid=squid_logger_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
		unix_system_kill($pid);
	}

	$pid=squid_logger_pid();
	if(!$unix->process_exists($pid)){squid_logger_stop_port();return true;}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=squid_logger_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
		unix_system_kill($pid);
	}

	$pid=squid_logger_pid();
	if(!$unix->process_exists($pid)){squid_logger_stop_port();return true;}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} failed...\n";}
}




function squid_logger_start(){
	$unix=new unix();
	$pid=squid_logger_pid();
	$GLOBALS["TITLENAME"]="Artica Proxy logger";

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already running pid $pid...\n";}
		@file_put_contents("/var/run/squid-tail.pid", $pid);
		return true;
	}


	if(!is_file("/etc/init.d/squid-tail")){
		remove_service("/etc/init.d/squid-tail");
	}

	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	$python=$unix->find_program("python");
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LOCATE_PHP5_BIN", $php5);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LOCATE_NOHUP", $nohup);
	
	$TEMPFILE=$unix->FILE_TEMP();
	squid_logger_stop_port();
	@unlink("/var/log/squid/squidtail.debug");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	system("$nohup $python /usr/share/artica-postfix/squid-logger.py start >$TEMPFILE 2>&1 &");
	sleep(1);
	for($i=0;$i<5;$i++){
		$pid=squid_logger_pid();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting $i/5...\n";}
		sleep(1);

	}
	$pid=squid_logger_pid();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} (squid-logger.py) service success\n";}
        @unlink($TEMPFILE);
		return true;
	
	}
	$f=explode("\n",@file_get_contents($TEMPFILE));
	@unlink($TEMPFILE);
	foreach ($f as $line){
	    $line=trim($line);
	    if($line==null){continue;}
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $line\n";}
    }
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} failed...\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $python /usr/share/artica-postfix/squid-logger.py\n";}

	return false;


}


function squid_logger_stop_port(){
	$SquidLoggerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLoggerPort"));
	if($SquidLoggerPort==0){$SquidLoggerPort=1444;}
	$unix=new unix();
	$pid=$unix->PIDOF_BY_PORT($SquidLoggerPort);
	if(!$unix->process_exists($pid)){return;}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=$unix->PIDOF_BY_PORT($SquidLoggerPort);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting stopped port:$SquidLoggerPort :$pid $i/5...\n";}
		sleep(1);
		unix_system_kill($pid);
	}

}


function squid_logger_restart(){
	$GLOBALS["TITLENAME"]="Artica Proxy logger";
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_restart("{stopping_service}",10);

	if(!squid_logger_stop()){
		build_progress_restart("{stopping_service} {failed}",110);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed..\n";}
		return;
	}
	build_progress_restart("{starting_service}...",50);

	if(!squid_logger_start()){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed..\n";}
		build_progress_restart("{starting_service} {failed}",110);
		return;
	}
	
	if(!is_file("/etc/cron.d/squid-logger")){
		$unix->Popuplate_cron_make("squid-logger", "0,5,10,15,20,25,30,35,40,45,55 * * * *","exec.squid-logger-queue.php");
		UNIX_RESTART_CRON();
	}
	
	if($GLOBALS["OUTPUT"]){echo "Restarting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} success..\n";}
	
	system("$php /usr/share/artica-postfix/exec.squid.global.access.php --logging");
	system("$php /usr/share/artica-postfix/exec.categories.flatfiles.php");
	build_progress_restart("{starting_service} {success}",100);
	return true;
}

function build_monit(){
	
	$SquidLoggerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLoggerPort"));
	if($SquidLoggerPort==0){$SquidLoggerPort=1444;}
	
	$f=array();
	$f[]="check process APP_ARTICALOGGER with pidfile /var/run/squid-tail.pid";
	$f[]="\tstart program = \"/etc/init.d/squid-logger start\"";
	$f[]="\tstop program = \"/etc/init.d/squid-logger stop\"";

	$f[]="\tif failed host 127.0.0.1 port $SquidLoggerPort then restart";
	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_ARTICALOGGER.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_ARTICALOGGER.monitrc")){
		echo "/etc/monit/conf.d/APP_ARTICALOGGER.monitrc failed !!!\n";
	}
	echo "SquidLogger: [INFO] Writing /etc/monit/conf.d/APP_ARTICALOGGER.monitrc\n";
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}



function squid_logger_create_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/squid-logger";
	$php5script=__FILE__;
	$daemonbinLog="Proxy Logger Daemon";

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         squid-logger";
	$f[]="# Required-Start:    \$local_fs \$syslog \$squid";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$squid";
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
	$f[]=" mkdir -p /var/run/squid || true";
	$f[]=" chmod 0755 /var/run/squid || true";
	$f[]=" chown squid /var/run/squid || true";
	$f[]=" chgrp squid /var/run/squid || true";
	$f[]="    $php $php5script --start-logger \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php $php5script --stop-logger \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php $php5script --restart-logger \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php $php5script --restart-logger \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php $php5script --restart-logger \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
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
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}