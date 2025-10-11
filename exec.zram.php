<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');

if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--connect"){connect();exit;}
if($argv[1]=="--disconnect"){disconnect();exit;}
function install(){
	
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CicapEnabled", 1);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableeCapClamav", 0);
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_install("{install_service} 1/5",10);
	install_cicap();
	
	build_progress_install("{install_service} 2/5",15);
	cicap_daemon_tail();
	build_progress_install("{install_service} 3/5",20);
	cicap_access_tail();
	build_progress_install("{install_service} 4/5",25);
	clamav_daemon();
	build_progress_install("{install_service} 5/5",30);
	clamav_freshclam();
	install_monit();
	
	build_progress_install("{configuring} 1/2",40);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=1,zOrder=1 WHERE ID=1");
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=1,zOrder=2 WHERE ID=2");
	
	build_progress_install("{restart_service} 1/6",45);
	system("/etc/init.d/c-icap restart");
	build_progress_install("{restart_service} 2/6",50);
	build_progress_install("{restart_service} 3/6",55);
	system("/etc/init.d/c-icap-access restart");
	build_progress_install("{restart_service} 4/6",60);
	system("/etc/init.d/clamav-daemon restart");
	build_progress_install("{restart_service} 5/6",65);
	system("/etc/init.d/clamav-freshclam restart");
	build_progress_install("{restart_service} 6/6",65);
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	build_progress_install("{configuring} 2/2",70);
	system("$php /usr/share/artica-postfix/exec.squid.global.access.php");
	build_progress_install("{done}",100);
	cluster_mode();
}


function install_monit(){
	
	
	@unlink("/etc/monit/conf.d/APP_C_ICAP.monitrc");
	$f[]="check process APP_C_ICAP with pidfile /var/run/c-icap/c-icap.pid";
	$f[]="\tstart program = \"/etc/init.d/c-icap start\"";
	$f[]="\tstop program = \"/etc/init.d/c-icap stop\"";
    $f[]="\trestart program = \"/etc/init.d/c-icap restart\"";

	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_CICAP.monitrc", @implode("\n", $f));
	
	$f=array();
	$f[]="check process APP_CLAMAV";
	$f[]="with pidfile /var/run/clamav/clamd.pid";
	$f[]="start program = \"/etc/init.d/clamav-daemon start --monit\"";
	$f[]="stop program =  \"/etc/init.d/clamav-daemon stop --monit\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Clamd service...\n";}
	@file_put_contents("/etc/monit/conf.d/APP_CLAMAV.monitrc", @implode("\n", $f));
	
	
	
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}


function disconnect(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_install("{configuring} 1/2",40);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CicapClusterConnect", 0);
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0 WHERE ID=1");
	if(!$q->ok){
		echo $q->mysql_error;
		build_progress_install("{failed2}",110);
		return;
	}
	
	$ligneSQL=mysqli_fetch_array($q->QUERY_SQL("SELECT `enabled` FROM c_icap_services WHERE ID=1"));
	if($ligneSQL["enabled"]==1){
		echo "Enabled == 0 for ID = 1 ???\n";
		build_progress_install("{failed2}",110);
		return;
	}
	echo "Enabled == 0 for ID = 1 [OK]\n";
	
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0 WHERE ID=2");
	build_progress_install("{configuring} 2/2",80);
	system("$php /usr/share/artica-postfix/exec.squid.global.access.php");
	build_progress_install("{done}",100);	
	cluster_mode();
	
}





function uninstall(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_install("{configuring} 1/2",5);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0,zOrder=1 WHERE ID=1");
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0,zOrder=2 WHERE ID=2");
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CicapEnabled", 0);
	
	build_progress_install("{remove_service} 1/5",10);
	remove_service("/etc/init.d/c-icap");
	uninstall_monit();
	build_progress_install("{remove_service} 2/5",20);
	remove_service("/etc/init.d/c-icap-watchdog");
	build_progress_install("{remove_service} 3/5",30);
	remove_service("/etc/init.d/c-icap-access");
	build_progress_install("{remove_service} 4/5",40);
	remove_service("/etc/init.d/clamav-daemon");
	build_progress_install("{remove_service} 5/5",50);
	remove_service("/etc/init.d/clamav-freshclam");
	
	build_progress_install("{configuring} 2/2",70);
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	build_progress_install("{done}",100);
	cluster_mode();
}

function build_progress_install($text,$pourc){
	$filename=PROGRESS_DIR."/cicap.install.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($filename, serialize($array));
	@chmod($filename,0777);

}


function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function install_zram(){

	$INITD_PATH="/etc/init.d/zram";
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          zram";
    $f[]="# Required-Start:    \$local_fs";
    $f[]="# Required-Stop:     \$local_fs";
    $f[]="# Default-Start:     S";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: Use compressed RAM as in-memory swap";
    $f[]="# Description:       Use compressed RAM as in-memory swap";
    $f[]="### END INIT INFO";
    $f[]="";
    $f[]="# Author: Antonio Galea <antonio.galea@gmail.com>";
    $f[]="# Thanks to Przemysław Tomczyk for suggesting swapoff parallelization";
    $f[]="# Distributed under the GPL version 3 or above, see terms at";
    $f[]="#      https://gnu.org/licenses/gpl-3.0.txt";
    $f[]="";
    $f[]="FRACTION=75";
    $f[]="";
    $f[]="MEMORY=`perl -ne'/^MemTotal:\s+(\d+)/ && print \$1*1024;' < /proc/meminfo`";
    $f[]="CPUS=`grep -c processor /proc/cpuinfo`";
    $f[]="SIZE=\$(( MEMORY * FRACTION / 100 / CPUS ))";
    $f[]="";
    $f[]="case \"\$1\" in";
    $f[]="  \"start\")";
    $f[]="    param=`modinfo zram|grep num_devices|cut -f2 -d:|tr -d ' '`";
    $f[]="    modprobe zram \$param=\$CPUS";
    $f[]="    for n in `seq \$CPUS`; do";
    $f[]="      i=\$((n - 1))";
    $f[]="      echo \$SIZE > /sys/block/zram\$i/disksize";
    $f[]="      mkswap /dev/zram\$i";
    $f[]="      swapon /dev/zram\$i -p 10";
    $f[]="    done";
    $f[]="    ;;";
    $f[]="  \"stop\")";
    $f[]="    for n in `seq \$CPUS`; do";
    $f[]="      i=\$((n - 1))";
    $f[]="      swapoff /dev/zram\$i && echo \"disabled disk \$n of \$CPUS\" &";
    $f[]="    done";
    $f[]="    wait";
    $f[]="    sleep .5";
    $f[]="    modprobe -r zram";
    $f[]="    ;;";
    $f[]="  *)";
    $f[]="    echo \"Usage: `basename \$0` (start | stop)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac\n";


	echo "zram: [INFO] Writing $INITD_PATH with new config\n";
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
