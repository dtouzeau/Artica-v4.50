<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');

if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}



function install(){
    $unix=new unix();
    build_progress_install("{installing}",10);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDKFilter",1);
    opendkim_service();
    build_progress_install("{installing}",20);
    build_monit();
    $php=$unix->LOCATE_PHP5_BIN();
    build_progress_install("{installing}",50);



    shell_exec("$php /usr/share/artica-postfix/exec.opendkim.php --build");
    build_progress_install("{installing}",55);
    shell_exec("$php /usr/share/artica-postfix/exec.opendkim.php --build-domains");
    build_progress_install("{installing}",60);
    shell_exec("$php /usr/share/artica-postfix/exec.opendkim.php --build");
    build_progress_install("{installing}",65);
    shell_exec("/etc/init.d/opendkim start");
    build_progress_install("{installing}",90);
    shell_exec("$php /usr/share/artica-postfix/exec.postfix.maincf.php --milters");
    build_progress_install("{installing} {success}",100);
}
function uninstall(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    build_progress_install("{unistalling}",20);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDKFilter",0);
    shell_exec("$php /usr/share/artica-postfix/exec.postfix.maincf.php --milters");
    build_progress_install("{uninstalling}",50);
    remove_service("/etc/init.d/opendkim");
    @unlink("/etc/monit/conf.d/APP_OPENDKIM.monitrc");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
    build_progress_install("{uninstalling} {done}",100);
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
function build_monit(){

    $f[]="check process APP_OPENDKIM with pidfile /var/run/opendkim/opendkim.pid";
    $f[]="\tstart program = \"/etc/init.d/opendkim start --monit\"";
    $f[]="\tstop program = \"/etc/init.d/opendkim stop --monit\"";

    $f[]="";
    @file_put_contents("/etc/monit/conf.d/APP_OPENDKIM.monitrc", @implode("\n", $f));
    if(!is_file("/etc/monit/conf.d/APP_OPENDKIM.monitrc")){
        echo "/etc/monit/conf.d/APP_OPENDKIM.monitrc failed !!!\n";
    }
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");

}


function build_progress_install($text,$pourc){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/opendkim.install.progress";
    echo "{$pourc}% $text\n";
    $cachefile=$GLOBALS["CACHEFILE"];
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}

function opendkim_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();

	$opendkim=$unix->find_program("opendkim");

	if(!is_file("$opendkim")){return;}
	$f[]="#! /bin/sh";
	$f[]="#";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:		opendkim";
	$f[]="# Required-Start:	\$syslog \$time \$local_fs \$remote_fs \$network";
	$f[]="# Required-Stop:	\$syslog \$time \$local_fs \$remote_fs";
	$f[]="# Default-Start:	2 3 4 5";
	$f[]="# Default-Stop:		0 1 6";
	$f[]="# Short-Description:	Start the OpenDKIM service";
	$f[]="# Description:		Enable DKIM signing and verification provided by OpenDKIM";
	$f[]="### END INIT INFO";

	$f[]="PATH=/sbin:/bin:/usr/sbin:/usr/bin";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/exec.opendkim.php --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/exec.opendkim.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/exec.opendkim.php --restart \$2 \$3";

	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/opendkim";
	echo "OpenDKIM: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);@file_put_contents($INITD_PATH, @implode("\n", $f));

	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

}