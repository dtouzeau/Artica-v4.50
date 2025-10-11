<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="AdsBlocker daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');

if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}





function install(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$daemonbin=$unix->find_program("postconf");
	if(!is_file($daemonbin)){
		build_progress_restart("{installing}... {failed} {not_installed}",110);
		return;
	}

	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePostfix", 1);
	build_progress_restart("{installing}...",20);
    squid_admin_mysql(0,"Removing proxy service and all associated service!",null,__FILE__,__LINE__);
	system("/usr/sbin/artica-phpfpm-service -uninstall-proxy");
    system("/usr/sbin/artica-phpfpm-service -stop-proxy");
	create_postfix_service();
	build_progress_restart("{installing}...",30);
	create_postfix_logger_service();
	build_progress_restart("{installing}...",31);
	create_postfix_monit();
	build_progress_restart("{installing}...",32);
	create_maillog_monit();
	
	$EnableMunin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
	if($EnableMunin==1){
		build_progress_restart("{reconfiguring} {APP_MUNIN}...",34);
		if(is_file("/etc/init.d/munin-node")){system("$php /usr/share/artica-postfix/exec.munin.php --reconfigure");}
	}
	build_progress_restart("{reconfiguring} {APP_FRONTAIL_MAILLOG}...",35);
	system("$php /usr/share/artica-postfix/exec.frontail.php --install >/dev/null 2>&1");



	$unix->Popuplate_cron_make("postfix-dashboard",
        "*/5 * * * *","exec.postfix.dashboard.php");



      $unix->Popuplate_cron_make("postfix-dashboard",
          "*/5 * * * *","exec.postfix.dashboard.php");




      $unix->Popuplate_cron_make("postfix-ipsets",
          "*/15 * * * *","exec.postfix.ipsets.php");


    shell_exec("/etc/init.d/cron reload");
    system("/usr/sbin/artica-phpfpm-service -install-postfix");

	build_progress_restart("{configuring} {please_wait}...",50);
    system("$php /usr/share/artica-postfix/exec.postfix.vacuum.php");
	system("$php /usr/share/artica-postfix/exec.postfix.maincf.php --others-values");
	build_progress_restart("{configuring} {please_wait}...",51);
	system("$php /usr/share/artica-postfix/exec.postfix.maincf.php --smtp-sender-restrictions");
	build_progress_restart("{starting_service}...",50);
	system("/etc/init.d/postfix restart");
	sleep(2);

	if(is_file("/etc/init.d/filebeat")){
        build_progress_restart("{starting_service}...",55);
	    system("/etc/init.d/filebeat restart");
    }

	$pid=PID_NUM();
	
	if(!$unix->process_exists($pid)){
		uninstall(true);
		build_progress_restart("{starting_service} {failed}...",110);
		return;
	}
	
	build_progress_restart("{success}...",100);
	
}

function queue_directory(){
	if(!is_file("/etc/artica-postfix/settings/Daemons/postfix_queue_directory")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("postfix_queue_directory", get_queue_directory());
	}
	return trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("postfix_queue_directory"));
}

function get_queue_directory(){
	$unix=new unix();
	$postconf=$unix->find_program('postconf');
	if($postconf==null){return null;}
	exec("$postconf -h queue_directory 2>&1",$results);
	return trim($results[0]);
}

function PID_NUM(){
	$unix=new unix();
	$queue_directory=queue_directory();
	$pidfile="$queue_directory/pid/master.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){return $pid;}
	$master=$unix->LOCATE_POSTFIX_DAEMON_DIRECTORY()."/master";
	return $unix->PIDOF($master);
	
}

function create_postfix_monit(){
	$f[]="check process APP_POSTFIX with pidfile /var/spool/postfix/pid/master.pid";
	$f[]="start program = \"/etc/init.d/postfix start --monit\"";
	$f[]="stop  program = \"/etc/init.d/postfix stop --monit\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	@file_put_contents("/etc/monit/conf.d/APP_POSTFIX.monitrc", @implode("\n", $f));
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}

function create_maillog_monit(){
	$f=array();
	$f[]="check process ARTICA_MYSQMAIL with pidfile /etc/artica-postfix/exec.maillog.php.pid";
	$f[]="\tstart program = \"/etc/init.d/postfix-logger start\"";
	$f[]="\tstop program = \"/etc/init.d/postfix-logger stop\"";

	$f[]="";
	@file_put_contents("/etc/monit/conf.d/ARTICA_MYSQMAIL.monitrc", @implode("\n", $f));
	build_progress_restart("{restarting} {APP_MONIT}...",33);
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}


function uninstall($noprogress=false){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePostfix", 0);
	if(!$noprogress){build_progress_restart("{uninstalling}...",20);}
	remove_service("/etc/init.d/postfix");
	if(!$noprogress){build_progress_restart("{uninstalling}...",25);}
	remove_service("/etc/init.d/postfix-logger");
	if(!$noprogress){build_progress_restart("{uninstalling}...",30);}
	if(is_file("/etc/monit/conf.d/APP_POSTFIX.monitrc")){
		@unlink("/etc/monit/conf.d/APP_POSTFIX.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	}
	
    $scron[]="pflogsumm-stats";
    $scron[]="postfix-dashboard";
    $scron[]="postfix-postqueue";
    $scron[]="postfix-ipsets";
    $scron[]="vacuumdb-postfix";
    $scron[]="artica-rotate-postfix";
    $scron[]="artica-pflogsumm-hourly";
    $scron[]="postfix-malware-patrol";

    foreach ($scron as $sfile){
        if(is_file("/etc/cron.d/$sfile")){@unlink("/etc/cron.d/$sfile");}
    }

    system("/etc/init.d/cron reload");

	
	if(!$noprogress){build_progress_restart("{uninstalling}...",31);}
	system("$php /usr/share/artica-postfix/exec.frontail.php --uninstall");
	if(!$noprogress){build_progress_restart("{uninstalling}...",32);}
	system("$php /usr/share/artica-postfix/exec.milter-regex.php --uninstall");
	if(!$noprogress){build_progress_restart("{uninstalling}...",33);}
	system("$php /usr/share/artica-postfix/exec.milter-greylist.install.php --uninstall");
    if(!$noprogress){build_progress_restart("{uninstalling}...",34);}
    if(!$noprogress){build_progress_restart("{uninstalling}...",35);}
    system("$php /usr/share/artica-postfix/exec.mimedefang.php --uninstall");
    if(!$noprogress){build_progress_restart("{uninstalling}...",36);}

    if(is_file("/etc/init.d/opendkim")) {
        if (!$noprogress) {build_progress_restart("{uninstalling}...", 37);}
        system("$php /usr/share/artica-postfix/exec.opendkim.install.php --uninstall");
    }

	
	
	if(!$noprogress){build_progress_restart("{uninstalling}...",59);}
	if(is_file("/etc/monit/conf.d/ARTICA_MYSQMAIL.monitrc")){
		@unlink("/etc/monit/conf.d/ARTICA_MYSQMAIL.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	}
	
	if(!$noprogress){build_progress_restart("{uninstalling}...",60);}
	
	if(is_file("/etc/init.d/munin-node")){system("$php /usr/share/artica-postfix/exec.munin.php --reconfigure");}
	shell_exec("/etc/init.d/cron reload");
	if(!$noprogress){build_progress_restart("{uninstalling}...{done}",100);}
	
}

function build_progress_restart($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/postfix.install";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}




function create_postfix_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("postconf");
	if(!is_file($daemonbin)){return;}
	
	@mkdir("/var/spool/postfix",0755,true);
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          postfix";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Postfix daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable Postfix MTA";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   mkdir -p /var/spool/postfix || true";
	$f[]="   chown root:root /var/spool/postfix || true";
	$f[]="   chmod 0755 /var/spool/postfix || true";
	$f[]="   chmod -R 0755 /usr/lib/postfix || true";
	$f[]="   chmod -R 0755 /usr/libexec/postfix || true";
	$f[]="   $php /usr/share/artica-postfix/exec.status.php --xmail";
	$f[]="   $php /usr/share/artica-postfix/exec.postfix.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php /usr/share/artica-postfix/exec.postfix.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   mkdir -p /var/spool/postfix || true";
	$f[]="   chown root:root /var/spool/postfix || true";
	$f[]="   chmod 0755 /var/spool/postfix || true";
	$f[]="   chmod -R 0755 /usr/lib/postfix || true";
	$f[]="   chmod -R 0755 /usr/libexec/postfix || true";	
	$f[]="   $php /usr/share/artica-postfix/exec.status.php --xmail";
	$f[]="   $php /usr/share/artica-postfix/exec.postfix.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php /usr/share/artica-postfix/exec.postfix.php --reload \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/postfix";
	echo "freeradius: [INFO] Writing $INITD_PATH with new config\n";
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

function create_postfix_logger_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/postfix-logger";
	$php5script="exec.service.postfix-logger.php";
	$daemonbinLog="Artica-postfix Realtime Logs";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         ".basename($INITD_PATH);
	$f[]="# Required-Start:    \$local_fs \$syslog \$postfix";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$postfix";
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