<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="InfluxDB Daemon";
$GLOBALS["PROGRESS"]=false;
$GLOBALS["MIGRATION"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;
$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--migration#",implode(" ",$argv),$re)){$GLOBALS["MIGRATION"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.mysql.powerdns.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');


if($argv[1]=="--syslog"){exit();}
if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--install-services"){install_services();exit();}
if($argv[1]=="--remove"){remove();exit();}
if($argv[1]=="--enable"){install_services();exit();}
if($argv[1]=="--install-ufdb"){install_ufdb();exit();}
if($argv[1]=="--uninstall-ufdb"){uninstall_ufdb();exit();}
if($argv[1]=="--install-poweradmin"){install_poweradmin();exit();}
if($argv[1]=="--disable-poweradmin"){disable_poweradmin();exit();}
if($argv[1]=="--enable-poweradmin"){enable_poweradmin();exit();}
if($argv[1]=="--monit"){$GLOBALS["OUTPUT"]=true;exit();}
if($argv[1]=="--install-recursor"){$GLOBALS["OUTPUT"]=true;install_recursor();exit();}
if($argv[1]=="--uninstall-recursor"){$GLOBALS["OUTPUT"]=true;uninstall_recursor();exit();}
if($argv[1]=="--rest-install"){$GLOBALS["OUTPUT"]=true;install_rest();exit();}
if($argv[1]=="--rest-uninstall"){$GLOBALS["OUTPUT"]=true;uninstall_rest();exit();}

//exec.pdns_server.install.php --syslog


function build_progress($pourc,$text){
	$cachefile=PROGRESS_DIR."/pdns.first.install";
	
	if(is_numeric($text)){
		$array["POURC"]=$text;
		$array["TEXT"]=$pourc;
		echo "{$pourc}% $text\n";
		@file_put_contents($cachefile, serialize($array));
		@chmod($cachefile,0755);
		return;
		
	}
	
	
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "{$pourc}% $text\n";
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_recursor($pourc,$text){
	$cachefile=PROGRESS_DIR."/pdns.recursor.install";
	
	if(is_numeric($text)){
		$array["POURC"]=$text;
		$array["TEXT"]=$pourc;
		echo "{$pourc}% $text\n";
		@file_put_contents($cachefile, serialize($array));
		@chmod($cachefile,0755);
		return;
	
	}
	
	
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "{$pourc}% $text\n";
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);	
	
}

function install_rest(){

    build_progress(50, "{installing} {APP_POWERDNS_RESTFUL}");
    $sock=new sockets();
    $sock->SET_INFO("EnablePDNSRESTFul",1);
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    system("$php /usr/share/artica-postfix/exec.lighttpd.php --nginx-reload");
    build_progress(100, "{installing} {APP_POWERDNS_RESTFUL} {done}");
}

function uninstall_rest(){
    build_progress(50, "{uninstalling} {APP_POWERDNS_RESTFUL}");
    $sock=new sockets();
    $sock->SET_INFO("EnablePDNSRESTFul",0);
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    system("$php /usr/share/artica-postfix/exec.lighttpd.php --nginx-reload");
    build_progress(100, "{uninstalling} {APP_POWERDNS_RESTFUL} {done}");
}

function install(){
    system("/usr/sbin/artica-phpfpm-service -install-pdns");
	
}



function install_poweradmin(){
	$unix=new unix();
	build_progress(15, "{downloading}");
	$curl=new ccurl("http://articatech.net/download/poweradmin-2.1.7.tgz");
	$curl->Timeout=2400;
	$curl->WriteProgress=true;
	$curl->ProgressFunction="download_pdns_progress";
	$filetemp=$unix->FILE_TEMP().".tar.gz";
	if(!$curl->GetFile($filetemp)){
		build_progress(110, "{downloading} {failed2}");
		@unlink($filetemp);
		return;
	}
	
	$md5New=md5_file($filetemp);
	if($md5New<>"37d482228cf21088ea1fb1a444531b6a"){
		build_progress(110, "{downloading} {failed2} {corrupted}");
		@unlink($filetemp);
		return;
	}
	
	build_progress(50, "{extracting}");
	
	$tar=$unix->find_program("tar");
	system("$tar -xhf $filetemp -C /");
	
	
	if(!is_file("/usr/share/poweradmin/index.php")){
		build_progress(110, "{installing} {failed}");
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerAdminInstalled", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePowerAdmin", 0);
		return;
	}
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerAdminInstalled", 1);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePowerAdmin", 1);
	$php=$unix->LOCATE_PHP5_BIN();
	
	build_progress(90, "{reconfiguring}");

	install_poweradmin_service();
	build_progress(95, "{restarting_service}");
	system("/etc/init.d/poweradmin restart");
	build_progress(100, "{restarting_service} {done}");
	
	
}

function enable_poweradmin(){
	build_progress(50, "{reconfiguring}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePowerAdmin", 1);
	install_poweradmin_service();
	build_progress(95, "{restarting_service}");
	system("/etc/init.d/poweradmin restart");
	build_progress(100, "{restarting_service} {done}");
}

function disable_poweradmin(){
	build_progress(50, "{reconfiguring}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePowerAdmin", 0);
	remove_service("/etc/init.d/poweradmin");
	build_progress(100, "{disable_feature} {done}");
}

function uninstall_ufdb(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PDSNInUfdb", 0);
	build_progress(20,"{uninstall} {webfiltering_service}");
	system("/usr/sbin/artica-phpfpm-service -uninstall-ufdb");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	build_progress(73, "{restarting_service}");
	system("/etc/init.d/pdns restart");
	build_progress(100, "{uninstall} {webfiltering_service} {success}");

}

function install_ufdb(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PDSNInUfdb", 1);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableUfdbGuard", 1);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableUfdbGuard2", 1);
	build_progress(20,"{reconfiguring} {webfiltering_service}");
	system("$php /usr/share/artica-postfix/exec.ufdb.enable.php");
	build_progress("{reconfiguring} {webfiltering_service}",30);
	system("$php /usr/share/artica-postfix/exec.squidguard.php --build --force --output");
	build_progress("{restarting} {webfiltering_service}",50);
	system("/etc/init.d/ufdb restart --force");
	build_progress("{restarting} {webfiltering_service}",60);
	system("/etc/init.d/monit restart --force");
	build_progress(73, "{restarting_service}");
	system("/etc/init.d/pdns restart");
	build_progress(100, "{webfiltering_service} {success}");
	
}

function install_recursor(){
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSEnableRecursor",1);
	build_progress_recursor(60, "{installing_service} {APP_PDNS_RECURSOR}");
	if(!install_pdns_recursor()){return false;}
	build_progress_recursor(70, "{installing_service} {APP_PDNS_RECURSOR}");
	syslog_install();
	build_progress_recursor(80, "{installing_service} {APP_PDNS_RECURSOR}");
	install_monit_recursor();
	build_progress_recursor(90, "{installing_service} {APP_PDNS_RECURSOR}");

	if(is_file("/etc/init.d/pdns")){
        build_progress_recursor(90, "{installing_service} {restarting}");
        shell_exec("/etc/init.d/pdns restart");
    }

	system("/etc/init.d/pdns-recursor restart");
	build_progress_recursor(100, "{installing_service} {APP_PDNS_RECURSOR} {done}");
}

function install_monit_recursor(){
		$f[]="check process PDNS_RECURSOR";
		$f[]="with pidfile /var/run/pdns/pdns_recursor.pid";
		$f[]="start program = \"/etc/init.d/pdns-recursor start --monit\"";
		$f[]="stop program =  \"/etc/init.d/pdns-recursor stop --monit\"";
		$f[]="if 5 restarts within 5 cycles then timeout";
		$f[]="";
		@file_put_contents("/etc/monit/conf.d/APP_PDNS_RECURSOR.monitrc", @implode("\n", $f));
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}

function uninstall_recursor(){
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSEnableRecursor",0);
	build_progress_recursor(60, "{uninstalling_service} {APP_PDNS_RECURSOR}");
	remove_service("/etc/init.d/pdns-recursor");
	build_progress_recursor(70, "{uninstalling_service} {APP_PDNS_RECURSOR}");
	@unlink("/etc/monit/conf.d/APP_PDNS_RECURSOR.monitrc");
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	build_progress_recursor(80, "{uninstalling_service} {APP_PDNS_RECURSOR}");
	shell_exec("/etc/init.d/pdns restart");
	build_progress_recursor(100, "{uninstalling_service} {APP_PDNS_RECURSOR} {done}");
    if(is_file("/etc/cron.d/pdns-rpz")){
        @unlink("/etc/cron.d/pdns-rpz");
    }
}

function mysql_pid():int{
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/mysqld/mysqld.pid");
    if($GLOBALS["VERBOSE"]){echo "[VERBOSE]: /var/run/mysqld/mysqld.pid -> \"$pid\"\n";}
    if($unix->process_exists($pid)){return $pid;}
    return 0;
}

function install_services():bool{
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	if(is_file("/etc/init.d/unbound")){
		build_progress(55, "{uninstalling} {APP_UNBOUND}");
        squid_admin_mysql(1,"Removing DNS Cache service",null,__FILE__,__LINE__);
        system("/usr/sbin/artica-phpfpm-service -uninstall-unbound");
	}

    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    $MantiCoreSearchEnabled     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MantiCoreSearchEnabled"));
    if($HaClusterClient==1){
        build_progress(110, "{installing} {failed} Cluster environment...");
        return false;

    }
    if($MantiCoreSearchEnabled==0) {
        if (!is_file("/etc/init.d/mysql")) {
            build_progress(110, "{installing} {failed} Missing MySQL service..");
            return false;
        }
        $pid=mysql_pid();
        if(!$unix->process_exists($pid)) {
            build_progress(56, "{restarting_service} {APP_MYSQL}");
            system("/etc/init.d/mysql restart");
            $z = 56;
            for ($i = 1; $i < 6; $i++) {
                $z++;
                if ($unix->is_socket("/var/run/mysqld/mysqld.sock")) {
                    break;
                }
                build_progress($z, "{waiting} /var/run/mysqld/mysqld.sock $i/5 ");
                sleep(1);
            }
        }
    }
	
	
	build_progress(65, "{installing_service} {APP_PDNS}");
	
	
	system("$php /usr/share/artica-postfix/exec.pdns.php --mysql --force");
	$q=new mysql_pdns();
	$f["cryptokeys"]=true;
	$f["domainmetadata"]=true;
	$f["domains"]=true;
	$f["comments"]=true;
	$f["perm_items"]=true;
	$f["perm_templ"]=true;
	$f["perm_templ_items"]=true;
	$f["records"]=true;
	$f["supermasters"]=true;
	$f["tsigkeys"]=true;
	$f["users"]=true;
	$f["zones"]=true;
	$f["zone_templ"]=true;
	$f["zone_templ_records"]=true;
	$f["migrations"]=true;

	foreach ($f as $tablename=>$none){
	    if(!$q->TABLE_EXISTS($tablename, "powerdns")){
	        echo "Missing table $tablename\n";
	        build_progress(110, "{installing_service} {failed}");
	        return false;
	    }


	}
	if(!install_pdns_server()){return false;}
	
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePDNS", 1);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PDNSUseHostsTable", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSMASQ", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableLocalDNSMASQ", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnboundEnabled",0);
	if(is_file("/etc/init.d/dnsmasq")) {
        remove_service("/etc/init.d/dnsmasq");
    }

	if(is_file("/etc/init.d/unbound")) {
        squid_admin_mysql(1,"Removing DNS Cache service for {APP_PDNS}", null,
            __FILE__, __LINE__);
        remove_service("/etc/init.d/unbound");
    }
	
	build_progress(70, "{restarting_service}");
	system("/etc/init.d/pdns restart");
	build_progress(71, "{restarting_service}");
	if(is_file("/etc/init.d/pdns_recursor")){
		system("/etc/init.d/pdns_recursor restart");
	}
	
	build_progress(72, "{restarting_service}");
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	build_progress(73, "{restarting_service}");
	system("/etc/init.d/artica-status restart");
	
	build_progress(90, "{restarting_service}");
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.status.php --pdns");
	build_progress(100, "{installing_service} {success}");
	return true;
}


function remove(){
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePDNS", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePowerAdmin", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DHCPDInPowerDNS", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSEnableClusterSlave", 0);
	
	if(is_file("/etc/init.d/pdns-recursor")){
		build_progress(50, "{disable_feature}");
		remove_service("/etc/init.d/pdns-recursor");
	}

    if(is_file("/etc/cron.d/pdns-clean")){
        @unlink("/etc/cron.d/pdns-clean");
        UNIX_RESTART_CRON();
    }
	
	build_progress(60, "{disable_feature}");
	
	if(is_file("/etc/monit/conf.d/APP_PDNS.monitrc")){
		@unlink("/etc/monit/conf.d/APP_PDNS.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	}
	
	build_progress(70, "{disable_feature}");
	if(!is_file("/etc/init.d/isc-dhcp-server")){shell_exec("/etc/init.d/isc-dhcp-server restart");}
	
	remove_service("/etc/init.d/pdns");
	remove_service("/etc/init.d/poweradmin");
	build_progress(100, "{disable_feature} {done}");
}


function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}
function install_poweradmin_service(){
	if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          poweradmin";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network \$time \$pdns";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: PowerDNS Webinterface";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable DNS PROXY daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	

	$f[]="   $php /usr/share/artica-postfix/exec.poweradmin.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php /usr/share/artica-postfix/exec.poweradmin.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	

	$f[]="   $php /usr/share/artica-postfix/exec.poweradmin.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php /usr/share/artica-postfix/exec.poweradmin.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/poweradmin";
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
function install_pdns_server(){	

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("pdns_server");
	if(!is_file($daemonbin)){return;}
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          pdns";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network \$time";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: PowerDNS daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable DNS PROXY daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php /usr/share/artica-postfix/exec.pdns_server.php --start \$2 \$3";

	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php /usr/share/artica-postfix/exec.pdns_server.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php /usr/share/artica-postfix/exec.pdns_server.php --restart \$2 \$3";

	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php /usr/share/artica-postfix/exec.pdns_server.php --reload \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	$INITD_PATH="/etc/init.d/pdns";
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
	
	return true;
	
}
function install_pdns_recursor(){
		
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		$daemonbin=$unix->find_program("pdns_recursor");
		$daemonbinLog=basename($daemonbin);
	
		if(!is_file($daemonbin)){return;}
		$INITD_PATH="/etc/init.d/pdns-recursor";
		
		$f[]="#!/bin/sh";
		$f[]="### BEGIN INIT INFO";
		$f[]="# Provides:          pdns_recursor";
		$f[]="# Required-Start:    \$local_fs \$syslog \$network";
		$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
		$f[]="# Should-Start:";
		$f[]="# Should-Stop:";
		$f[]="# Default-Start:     3 4 5";
		$f[]="# Default-Stop:      0 1 6";
		$f[]="# Short-Description: pdns_recursor";
		$f[]="# chkconfig: - 80 75";
		$f[]="# description: pdns_recursor is a versatile high performance recursing nameserver";
		$f[]="### END INIT INFO";
		$f[]="case \"\$1\" in";
		$f[]=" start)";
		$f[]="    $php /usr/share/artica-postfix/exec.pdns_recursor.php --start \$2 \$3";
		$f[]="    ;;";
		$f[]="";
		$f[]="  stop)";
		$f[]="    $php /usr/share/artica-postfix/exec.pdns_recursor.php --stop \$2 \$3";
		$f[]="    ;;";
		$f[]="";
		$f[]=" restart)";
		$f[]="    $php /usr/share/artica-postfix/exec.pdns_recursor.php --restart \$2 \$3";
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
		}
	
		if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
			shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
		}
		
		return true;
	
	}

function download_pdns_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}
	

	if ( $download_size == 0 ){
		$progress = 0;
	}else{
		$progress = round( $downloaded_size * 100 / $download_size );
	}

	if ( $progress > $GLOBALS["previousProgress"]){
		if($progress==20){build_progress(20, "{downloading}");}
		if($progress==50){build_progress(25, "{downloading}");}
		if($progress==70){build_progress(30, "{downloading}");}
		if($progress==80){build_progress(35, "{downloading}");}
		if($progress==90){build_progress(40, "{downloading}");}
		if($progress==99){build_progress(45, "{downloading}");}
		echo "Downloading: ". $progress."%, please wait...\n";
		$GLOBALS["previousProgress"]=$progress;
	}
}