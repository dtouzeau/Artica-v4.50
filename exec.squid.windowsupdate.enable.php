<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.openvpn.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . '/ressources/class.postgres.inc');

if($argv[1]=="--disable"){disable();exit;}
if($argv[1]=="--reload"){reload();exit;}

xrun();


function disable(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$rm=$unix->find_program("rm");	
	$squid=$unix->LOCATE_SQUID_BIN();
	
	
	build_progress("{removing_old_caches}...",17);
	shell_exec("$rm -rf /home/squid/WindowsUpdate/*");
	build_progress("{uninstall_service} {WSUS_HTTP}",20);
	remove_service("/etc/init.d/cache-httpd");
	build_progress("{uninstall_service} {WSUS_SCHEDULER}",30);
	remove_service("/etc/init.d/cache-scheduler");
	build_progress("{disable_service}",40);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WindowsUpdateCaching",0);

	build_progress("{reloading_proxy_service}",50);
	squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
	system("/usr/sbin/artica-phpfpm-service -reload-proxy");
	
	build_progress("{disable_service}: {calculating_disk_space}",90);
	@unlink(PROGRESS_DIR."/WindowsUpdate.state");
	$WindowsUpdateCachingDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsUpdateCachingDir");
	if($WindowsUpdateCachingDir==null){$WindowsUpdateCachingDir="/home/squid/WindowsUpdate";}
	if(is_dir($WindowsUpdateCachingDir)){shell_exec("$rm -rf $WindowsUpdateCachingDir");}
	build_progress("{disable_feature} {done}",100);
	
}


function reload(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$rm=$unix->find_program("rm");
	$squid=$unix->LOCATE_SQUID_BIN();
	build_progress("{reloading_service}",35);
	system("/etc/init.d/cache-httpd restart");
	
	build_progress("{reloading_service}",40);
	system("/etc/init.d/cache-scheduler restart");
	

	
	build_progress("{reloading_proxy_service}",50);
	squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
	system("/usr/sbin/artica-phpfpm-service -reload-proxy");
	
	build_progress("{enable_service}: {calculating_disk_space}",90);
	@unlink(PROGRESS_DIR."/WindowsUpdate.state");
	system("$php /usr/share/artica-postfix/exec.windowsupdate-partials.php --partition");
	
	build_progress("{reloading} {done}",100);	
	
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
function xrun(){

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$rm=$unix->find_program("rm");
	$squid=$unix->LOCATE_SQUID_BIN();
	
	build_progress("{enable_service}: PostGreSQL",10);
	
	$q=new postgres_sql();
	$q->QUERY_SQL("DROP table wsus");
	if(!$q->WSUS_TABLES()){
		echo $q->mysql_error."\n";
		build_progress("PostGreSQL Error",110);
		return;
	}
	build_progress("{verify_python_package}...",15);
	
	if(is_dir("/home/squid/WindowsUpdate/Cache")){
		build_progress("{removing_old_caches}...",17);
		shell_exec("$rm -rf /home/squid/WindowsUpdate/*");}
	
	build_progress("{create_service} {WSUS_HTTP}",20);
	httpd_initd_create();
	
	
	build_progress("{create_service} {WSUS_SCHEDULER}",25);
	scheduler_initd_create();
	
	
	build_progress("{enable_service}",30);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WindowsUpdateCaching",1);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableUfdbGuard",1);

	build_progress("{starting_service}",35);
	system("/etc/init.d/cache-httpd restart");
	
	build_progress("{starting_service}",40);
	system("/etc/init.d/cache-scheduler restart");
	
	
	build_progress("{reconfigure_proxy_service}",45);
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	
	build_progress("{enable_service}: {reloading_proxy_service}",50);
	squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
	system("/usr/sbin/artica-phpfpm-service -reload-proxy");
	
	build_progress("{enable_service}: {calculating_disk_space}",90);
	@unlink(PROGRESS_DIR."/WindowsUpdate.state");
	system("$php /usr/share/artica-postfix/exec.windowsupdate-partials.php --partition");
	
	build_progress("{enable_feature} {done}",100);

}
function scheduler_initd_create(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/cache-scheduler";
	$php5script="exec.cache-scheduler.php";
	$daemonbinLog="Enforce cache Scheduler engine";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         cache-scheduler";
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

function httpd_initd_create(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/cache-httpd";
	$php5script="exec.cache-httpd.php";
	$daemonbinLog="Enforce cache Web engine";
	
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         cache-httpd";
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



function PID_NUM(){

	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/openvpn/openvpn-server.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("openvpn");
	return $unix->PIDOF_PATTERN("$Masterbin --port.+?--dev");

}




function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/squid.windowsupdate.enable.progress";
	echo "[{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){sleep(1);}
}


?>