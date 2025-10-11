<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);ini_set('error_append_string',null);

include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');

if($argv[1]=="--install"){xinstall();exit();}
if($argv[1]=="--uninstall"){xuninstall();exit();}

function xinstall(){
	$unix=new unix();
	reconfigure_progress("{enable_feature}",10);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MilterGreyListEnabled", 1);
	reconfigure_progress("{install_service}",25);
	install_milter_greylist();
	reconfigure_progress("{install_service}",30);
	reconfigure_progress("{reconfiguring} {APP_MONIT}",35);
	reconfigure_progress("{restart_service}",40);
	system("/etc/init.d/milter-greylist restart");
	reconfigure_progress("{restart_service}",60);
	
	reconfigure_progress("{reconfigure} {APP_POSTFIX}",80);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.postfix.maincf.php --milters");
	reconfigure_progress("{done}",100);
	
}


function monit_install(){
	$f=array();
	$f[]="check process APP_MILTERGREYLIST with pidfile /var/run/milter-greylist/milter-greylist.pid";
	$f[]="\tstart program = \"/etc/init.d/milter-greylist start\"";
	$f[]="\tstop program = \"/etc/init.d/milter-greylist stop\"";
	$f[]="\tif failed unixsocket /var/run/milter-greylist/milter-greylist.sock then restart";

	$f[]="";

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring milter-greylist...\n";}
	@file_put_contents("/etc/monit/conf.d/APP_MILTERGREYLIST.monitrc", @implode("\n", $f));


    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");

}






function xuninstall(){
	$unix=new unix();
	reconfigure_progress("{remove_service}",20);
	remove_service("/etc/init.d/milter-greylist");
	reconfigure_progress("{remove_service}",30);
	remove_service("/etc/init.d/milter-greyweb");
	reconfigure_progress("{disable_feature}",40);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MilterGreyListEnabled", 0);
	reconfigure_progress("{restart_service} {APP_MONIT}",70);
	@unlink("/etc/monit/conf.d/MILTER_GREYLIST_WEB.monitrc");
	@unlink("/etc/monit/conf.d/APP_MILTERGREYLIST.monitrc");
	
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	reconfigure_progress("{reconfigure}",80);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.postfix.maincf.php --milters");
	reconfigure_progress("{done}",100);

}

function reconfigure_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/milter-greylist.install.progress", serialize($array));
	@chmod(PROGRESS_DIR."/milter-greylist.install.progress",0755);
	

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

function install_milter_greylist(){
	$daemonbinLog="Milter Greylist Daemon";
	if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}
	$unix=new unix();
	$milter_greylist=$unix->find_program("milter-greylist");
	if(!is_file($milter_greylist)){return;}
	$php=$unix->LOCATE_PHP5_BIN();
	$sock=new sockets();
	$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
	if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}

	$INITD_PATH="/etc/init.d/milter-greylist";

	$cmdline_start="$php /usr/share/artica-postfix/exec.milter-greylist.php --start-single";
	$cmdline_stop="$php /usr/share/artica-postfix/exec.milter-greylist.php --stop-single";
	$cmdline_restart="$php /usr/share/artica-postfix/exec.milter-greylist.php --restart-single";
	$cmdline_reload="$php /usr/share/artica-postfix/exec.milter-greylist.php --reload-single";
	if($EnablePostfixMultiInstance==1){
		$cmdline_start="$php /usr/share/artica-postfix/exec.milter-greylist.php --start";
		$cmdline_stop="$php /usr/share/artica-postfix/exec.milter-greylist.php --stop";
	}

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          milter-greylist";
	$f[]="# Required-Start:    \$local_fs";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $cmdline_start \$2";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $cmdline_stop \$2";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	if($EnablePostfixMultiInstance==1){
		$f[]="     $cmdline_stop \$2";
		$f[]="     sleep 3";
		$f[]="     $cmdline_start \$2";
	}else{
		$f[]="    $cmdline_restart \$2";
	}
	$f[]="    ;;";
	$f[]="  reload)";
	if($EnablePostfixMultiInstance==0){
		$f[]="    $cmdline_reload \$2";
	}
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reload}\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0";
	$f[]="";

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