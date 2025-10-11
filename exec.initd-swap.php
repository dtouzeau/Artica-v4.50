<?php

$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(isset($argv[1])) {
    if ($argv[1] == "--start") {
        start();
        exit;
    }
    if ($argv[1] == "--stop") {
        stop();
        exit;
    }
}

buildscript();

function buildscript(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          artica-swap";
	$f[]="# Required-Start:    \$local_fs";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Start SWAP config server";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: SWap config Daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php ". __FILE__." --start --byinitd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php ". __FILE__." --stop --byinitd --force \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php ". __FILE__." --stop --byinitd --force \$2 \$3";
	$f[]="    $php ". __FILE__." --start --byinitd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} {ldap|} (+ 'debug' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/artica-swap";
	echo "SWAP: [INFO] Writing $INITD_PATH with new config\n";
	@file_put_contents($INITD_PATH, @implode("\n", $f));

	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
}

if(is_file('/sbin/chkconfig')){
	shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
	shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
}

}


function start(){
	$sock=new sockets();
	$unix=new unix();
	@mkdir("/etc/artica-postfix/pids",0755,true);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		echo "SWAP: [INFO] Already running...\n";
		exit();
	}
	
	@file_put_contents($pidfile, getmypid());
	
	
	$sysctl=$unix->find_program("sysctl");
	$swapoff=$unix->find_program("swapoff");
	$swapon=$unix->find_program("swapon");
	$nohup=$unix->find_program("nohup");
			
	$DisableSWAPP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSWAPP"));
	$ARRAY=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kernel_values")));
	$swapiness=intval($ARRAY["swappiness"]);
	if($swapiness==0){$swapiness=60;}
	if(!is_numeric($DisableSWAPP)){$DisableSWAPP=0;}
	if($DisableSWAPP==0){
		shell_exec("$sysctl -w vm.swappiness=$swapiness >/dev/null 2>&1");
		echo "SWAP: [INFO] swap is enabled to {$swapiness}%, aborting\n";
		shell_exec("$nohup $swapon -a >/dev/null 2>&1 &");
		return;
	}

	echo "SWAP: [INFO] swap is disabled, hide swap usage...\n";
	shell_exec("$sysctl -w vm.swappiness=0 >/dev/null 2>&1");
	shell_exec("$nohup $swapoff -a >/dev/null 2>&1 &");
}

function stop(){
	echo "SWAP: [INFO] cleaning caches...\n";
	$unix=new unix();
	$rm=$unix->find_program("rm");
	shell_exec("$rm -rf /usr/share/artica-postfix/ressources/logs/* >/dev/null 2>&1");
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",0777,true);
}

