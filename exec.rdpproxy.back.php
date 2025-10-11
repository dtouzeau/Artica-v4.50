<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Remote Desktop Proxy";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--restart-progress"){$GLOBALS["OUTPUT"]=true;restart_progress();exit();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();exit();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--syslog"){install_syslog();exit;}
if($argv[1]=="--authhook-restart"){$GLOBALS["OUTPUT"]=true;AUTHHOOK_RESTART();exit();}
if($argv[1]=="--authhook-start"){$GLOBALS["OUTPUT"]=true;AUTHHOOK_START();exit();}
if($argv[1]=="--authhook-stop"){$GLOBALS["OUTPUT"]=true;AUTHHOOK_STOP();exit();}
if($argv[1]=="--clean-sessions"){$GLOBALS["OUTPUT"]=true;CLEAN_SESSIONS();exit();}
if($argv[1]=="--rotate"){$GLOBALS["OUTPUT"]=true;ROTATE_LOGS();exit();}
if($argv[1]=="--checks"){$GLOBALS["OUTPUT"]=true;CHECK_RDPPROXY_NEWVER();exit();}
if($argv[1]=="--upgrade"){upgrade_forced();exit;}

function patch_table(){
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    if(!$q->FIELD_EXISTS("targets","enabled")){
        $q->QUERY_SQL("ALTER TABLE targets ADD enabled NOT NULL DEFAULT 1");
    }
    if(!$q->FIELD_EXISTS("targets","DontResolve")){
        $q->QUERY_SQL("ALTER TABLE targets ADD DontResolve NOT NULL DEFAULT 0");
    }

    if(!$q->FIELD_EXISTS("members","ADGROUP")){
        $q->QUERY_SQL("ALTER TABLE members ADD ADGROUP INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("groups","session_time")){
        $q->QUERY_SQL("ALTER TABLE groups ADD session_time INTEGER");
    }
    if(!$q->FIELD_EXISTS("groups","user_rec")){
        $q->QUERY_SQL("ALTER TABLE groups ADD user_rec INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("groups","networks")){
        $q->QUERY_SQL("ALTER TABLE groups ADD networks TEXT");
        $q->QUERY_SQL("ALTER TABLE groups ADD enabled INTEGER NOT NULL DEFAULT 1");

    }


}

function upgrade_forced_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"squid.rdpproxy.upgrade");
}

function upgrade_forced(){
    include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
    $pp=10;
    $unix=new unix();
    $libs=CHECK_REQUIRED_LIBS();
    $debver=$unix->DEBIAN_VERSION();
    if($debver<10){
        upgrade_forced_progress($pp,"$debver v$debver not supported",110);
        return false;
    }

    foreach ($libs as $fname=>$package){
        $pp=$pp+5;
        upgrade_forced_progress($pp,"Checking $package");
        if(is_file($fname)){continue;}
        $unix->DEBIAN_INSTALL_PACKAGE($package);
        if(!is_file($fname)){
            upgrade_forced_progress(110,"{installing} $package {failed}");
            return false;
        }
    }

    upgrade_forced_progress(50,"{downloading} v9.1.5");
    $uri="http://mirror.articatech.com/download/Debian10-rdpproxy/9.1.5.tar.gz";
    $TMP_DIR=$unix->TEMP_DIR();
    $tfile="$TMP_DIR/9.1.03.tar.gz";
    $tmpdir="$TMP_DIR/UPKGRDP";
    $curl=new ccurl($uri);
    if(!$curl->GetFile($tfile)){
        upgrade_forced_progress(110,"{downloading} v9.1.03 {failed}");
        return false;
    }
    if(!is_dir($tmpdir)){@mkdir($tmpdir,0755,true);}
    $tar=$unix->find_program("tar");
    $cp=$unix->find_program("cp");
    $rm=$unix->find_program("rm");
    upgrade_forced_progress(60,"{uncompressing}...");
    system("$tar xf $tfile -C $tmpdir/");

    upgrade_forced_progress(65,"{stopping}...");
    stop(true);
    AUTHHOOK_STOP(true);
    upgrade_forced_progress(70,"{installing}...");

    $binaries[]="usr/local/bin";
    $binaries[]="usr/local/sbin";
    $binaries[]="usr/local/lib";
    $binaries[]="usr/local/man";
    $binaries[]="usr/local/share/ntopng";
    $binaries[]="usr/local/share";
    $binaries[]="usr/lib/x86_64-linux-gnu/xtables";
    $binaries[]="usr/lib/x86_64-linux-gnu";
    $binaries[]="usr/lib";
    $binaries[]="usr/sbin";
    $binaries[]="usr/bin";
    $binaries[]="sbin";
    $binaries[]="bin";
    $binaries[]="lib/modules/4.19.0-6-amd64/extra";
    $binaries[]="lib/modules/4.19.0-6-amd64";
    $binaries[]="lib/modules";
    $binaries[]="lib/squid3";
    $binaries[]="lib";
    $binaries[]="usr/share/squid3";
    $binaries[]="etc/squid3";

    foreach ($binaries as $directory){
        $srcdir="$tmpdir/$directory";
        $destdir="/$directory";

        if(!is_dir($srcdir)){continue;}
        upgrade_forced_progress(75,"{installing} {$destdir}...");
        if(!is_dir($destdir)){@mkdir($destdir,0755,true);}
        echo "Copy files $srcdir to $destdir/\n";
        shell_exec("$cp -rfd $srcdir/* $destdir/");
        shell_exec("$rm -rf $srcdir");
    }

    shell_exec("$cp -rfvd $tmpdir/* /");
    upgrade_forced_progress(80,"{removing} {$tmpdir}...");
    echo "Removing $tmpdir\n";
    shell_exec("$rm -rf $tmpdir");
    upgrade_forced_progress(85,"{installing}");
    install();
    upgrade_forced_progress(86,"{installing}");
    shell_exec("/etc/init.d/artica-status restart --force");
    upgrade_forced_progress(87,"{installing}");
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
    upgrade_forced_progress(88,"{installing}");
    CHECK_RDPPROXY_NEWVER();
    upgrade_forced_progress(100,"{success}");
    return true;

}

function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	build();
    CHECK_RDPPROXY_NEWVER();
	sleep(1);
	start(true);
	
}

function build_progress($pourc,$text){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents(PROGRESS_DIR."/squid.rdpproxy.progress", serialize($array));
	@chmod(PROGRESS_DIR."/squid.rdpproxy.progress",0755);
}

function monit(){

            $f[]="check process APP_RDPPROXY with pidfile /var/run/redemption/rdpproxy.pid";
            $f[]="\tstart program = \"/etc/init.d/rdpproxy start --monit\"";
            $f[]="\tstop program = \"/etc/init.d/rdpproxy stop --monit\"";

            $f[]="";
            if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring RDP Proxy...\n";}
            @file_put_contents("/etc/monit/conf.d/APP_RDPPROXY.monitrc", @implode("\n", $f));



        $f=array();
    $f[]="check process APP_RDPPROXYHOOK with pidfile /var/run/rdpproxy/auth.pid";
    $f[]="\tstart program = \"/etc/init.d/rdpproxy-authhook start --monit\"";
    $f[]="\tstop program = \"/etc/init.d/rdpproxy-authhook stop --monit\"";

    $f[]="if failed unixsocket /var/run/rdpproxy/auth.sock then restart";
    $f[]="";
    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring RDP Proxy...\n";}
    @file_put_contents("/etc/monit/conf.d/APP_RDPPROXYAUTH.monitrc", @implode("\n", $f));

}

function uninstall(){
	build_progress(20,"{disable_feature}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableRDPProxy", 0);
	remove_service("/etc/init.d/rdpproxy");
	build_progress(80,"{disable_feature}");
	remove_service("/etc/init.d/rdpproxy-authhook");
    @unlink("/etc/monit/conf.d/APP_RDPPROXY.monitrc");
    @unlink("/etc/monit/conf.d/APP_RDPPROXYAUTH.monitrc");

    @unlink("/etc/cron.d/rdpproxy-sessions");
    @unlink("/etc/cron.d/rdpproxy-rotate");
    @unlink("/etc/cron.d/rdpproxy-videos");
    build_progress(90,"{disable_feature}");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    system("/etc/init.d/cron reload");

    if(is_file("/etc/init.d/fail2ban")){system("/etc/init.d/fail2ban restart");}
    build_progress(100,"{disable_feature} {done}");
}

function install_syslog(){
    $md51=null;
    $target_file="/etc/rsyslog.d/rdpproxy.conf";
    if(is_file($target_file)){
        $md51=md5_file($target_file);
    }

    $f[]="if  (\$programname =='rdpproxy') then {";
    $f[] = BuildRemoteSyslogs("rdpprody");
    $f[]=buildlocalsyslogfile("/var/log/rdpproxy/daemon.log");
    $f[]="\t& stop";
    $f[]="}";
    $f[]="if  (\$programname =='rdpproxy-auth') then {";
    $f[] = BuildRemoteSyslogs("rdpprody");
    $f[]=buildlocalsyslogfile("/var/log/rdpproxy/auth.log");
    $f[]="\t& stop";
    $f[]="}";

    @file_put_contents($target_file,@implode("\n",$f));
    if($md51==$md51){return true;}
    $unix=new unix();$unix->RESTART_SYSLOG(true);
    return true;
}

function install(){

    $unix=new unix();
	build_progress(20,"{install_feature}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableRDPProxy", 1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RDPMinTLS",2);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RDPTlsSupport",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RDPSslCipher",2);
	install_rdpproxy();
	build_progress(50,"{install_feature}");
	build();



	build_progress(60,"{install_feature}");
	start(true);
	build_progress(70,"{install_feature}");
	AUTHHOOK_START();
    build_progress(80,"{install_feature}");
    monit();
    build_progress(90,"{install_feature}");
    $unix->Popuplate_cron_make("rdpproxy-videos","*/5 * * * *","exec.rdpproxy.videos.php");
    UNIX_RESTART_CRON();
    $unix=new unix();$unix->RESTART_SYSLOG(true);
    build_progress(100,"{done}");
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

function install_rdpproxy(){
	if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/rdpproxy";
	$php5script="exec.rdpproxy.php";
	$daemonbinLog="RDP Proxy Daemon";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:        rdpproxy";
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
	install_rdpproxy_authhook();

}
function install_rdpproxy_authhook(){

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/rdpproxy-authhook";
	$php5script="exec.rdpproxy.php";
	$daemonbinLog="authhook RDP Proxy Daemon";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:        authhook";
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
	$f[]="    $php /usr/share/artica-postfix/$php5script --authhook-start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --authhook-stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --authhook-restart \$2 \$3";
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

function restart_progress(){
	
	build_progress(15, "{stopping_service}");
	AUTHHOOK_STOP(true);
	build_progress(20, "{stopping_service}");
	stop(true);
	build_progress(30, "{building_configuration}");
	build();
	build_progress(50, "{starting_service}");
	AUTHHOOK_START(true);
	build_progress(80, "{starting_service}");
	if(!start(true)){
		build_progress(110, "{starting_service} {failed}");
		return;
	}
	build_progress(100, "{starting_service} {success}");
}

function AUTHHOOK_RESTART_PROGRESS($pourc,$text){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";
    @file_put_contents(PROGRESS_DIR."/squid.rdpproxy.auth.progress", serialize($array));
    @chmod(PROGRESS_DIR."/squid.rdpproxy.auth.progress",0755);


}

function AUTHHOOK_RESTART(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
        AUTHHOOK_RESTART_PROGRESS(110,"{failed} Already Artica task running PID $pid since {$time}mn");
		return;
	}
	@file_put_contents($pidfile, getmypid());
    AUTHHOOK_RESTART_PROGRESS(10,"{stopping}...");
	AUTHHOOK_STOP(true);
    AUTHHOOK_RESTART_PROGRESS(50,"{reconfiguring}...");
	build();
	sleep(1);
    AUTHHOOK_RESTART_PROGRESS(80,"{starting}...");
	AUTHHOOK_START(true);

    $pid=AUTHHOOK_PID_NUM();

    if($unix->process_exists($pid)){
        AUTHHOOK_RESTART_PROGRESS(100,"{starting} {success}...");
        return;
    }
    AUTHHOOK_RESTART_PROGRESS(110,"{starting} {failed}...");
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("rdpproxy");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, rdpproxy not installed\n";}
		return false;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return true;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return true;
	}
	$EnableRDPProxy=$sock->GET_INFO("EnableRDPProxy");
	if(!is_numeric($EnableRDPProxy)){$EnableRDPProxy=0;}
	

	if($EnableRDPProxy==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableRDPProxy)\n";}
		return false;
	}

	$nohup=$unix->find_program("nohup");
	$kill=$unix->find_program("kill");
	$RDPProxyPort=$sock->GET_INFO("RDPProxyPort");
	if(!is_numeric($RDPProxyPort)){$RDPProxyPort=3389;}
	
	$PIDS=$unix->PIDOF_BY_PORT($RDPProxyPort);
	if(count($PIDS)==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} 0 PID listens $RDPProxyPort...\n";}
	}
	if(count($PIDS)>0){
		foreach ($PIDS as $pid=>$b){
			if($unix->process_exists($pid)){
				$cmdline=@file_get_contents("/proc/$pid/cmdline");
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} killing PID $pid that listens $RDPProxyPort TCP port\n";}
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Process: `$cmdline`\n";}
				unix_system_kill_force($pid);
			}
		}
		
	}
	

	
	
	@mkdir('/etc/rdpproxy/cert/rdp',0755,true);
	@mkdir("/var/rdpproxy/recorded",0755,true);
	@mkdir("/var/run/redemption",0755,true);
	@mkdir("/tmp/rdpproxy",0755,true);
	@mkdir("/home/rdpproxy/recorded",0755,true);
	
	foreach (glob("/usr/share/artica-postfix/img/rdpproxy/*") as $filename) {
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} \"".basename($filename)."\"\n";}
		@copy($filename, "/usr/local/share/rdpproxy/".basename($filename));
	}

    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} binary $Masterbin\n";

	if(is_file("/var/run/redemption/rdpproxy.pid")){@unlink("/var/run/redemption/rdpproxy.pid");}
	$VERSION=VERSION();
	$cmd="$nohup $Masterbin >/var/log/rdpproxy.log 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service v.$VERSION\n";}
	shell_exec($cmd);
	
	
	

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		if(!is_file("/var/run/redemption/rdpproxy.pid")){@file_put_contents("/var/run/redemption/rdpproxy.pid", $pid);}
		AUTHHOOK_START(true);
		return true;
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


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
	$pid=$unix->get_pid_from_file("/var/run/redemption/rdpproxy.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("rdpproxy");
	return $unix->PIDOF($Masterbin);
	
}
function VERSION(){

    exec("/usr/local/bin/rdpproxy --version 2>&1",$array);
    foreach ($array as $pid=>$line){
        if(preg_match("#ReDemPtion\s+([0-9\.\-]+)#i", $line,$re)){
            $APP_RDPPROXY_VERSION=$re[1];
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_RDPPROXY_VERSION",$APP_RDPPROXY_VERSION);
            return $APP_RDPPROXY_VERSION;
        }

        if($GLOBALS['VERBOSE']){echo "APP_RDPPROXY_VERSION(), $line, not found \n";}
    }

	return "0.0.0";
}
function AUTHHOOK_PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/rdpproxy/auth.pid");
	if($unix->process_exists($pid)){return $pid;}
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"passtrough\" 2>&1",$results);
	foreach ($results as $ligne){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $ligne,$re)){continue;}
		return $re[1];	
	}
	
}

function AUTHOOK_LOGS($text,$type=1){
    $stype[1]="INFO";
    $stype[0]="ERROR";
    $stype[2]="DEBUG";
    $unix=new unix();
    $unix->ToSyslog("[{$stype[$type]}]: $text",false,"rdpproxy-auth");
}

function AUTHHOOK_START($aspid=false){
	$unix=new unix();
	$Masterbin=$unix->find_program("rdpproxy");

	if(!is_file($Masterbin)){
        AUTHOOK_LOGS("authhook rdpproxy not installed",0);
		echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]}, rdpproxy not installed\n";
		return false;
	}

    AUTHOOK_LOGS("Trying to start authhook service",1);
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
            AUTHOOK_LOGS("Already Artica task running PID $pid since {$time}mn",1);
            AUTHOOK_LOGS("Running task was ".trim(@file_get_contents("/proc/$pid/cmdline")),1);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=AUTHHOOK_PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
        AUTHOOK_LOGS("Service already started $pid since {$timepid}Mn",1);
		echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";
		return true;
	}
	$EnableRDPProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRDPProxy"));

    if($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("isArticaLicense",1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("isArticaLicense",0);
    }


	if($EnableRDPProxy==0){
        AUTHOOK_LOGS("service disabled (see EnableRDPProxy)",1);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service disabled (see EnableRDPProxy)\n";}
		return false;
	}

	$nohup=$unix->find_program("nohup");


    patch_table();


    $DEBIAN_VERSION=$unix->DEBIAN_VERSION();
    if($DEBIAN_VERSION==10) {
        if(!is_file("/usr/lib/x86_64-linux-gnu/libpython3.7m.so.1.0")){
            $unix->DEBIAN_INSTALL_PACKAGE("libpython3.7");
        }
        if(!is_file("/usr/lib/x86_64-linux-gnu/libpython3.7m.so.1.0")){
            squid_admin_mysql(0,"Unable to start RDP Proxy Authook libpython3.7m.so.1.0 missing","",__FUNCTION__,__LINE__);
            return false;
        }
        if(!is_file("/usr/lib/python3/dist-packages/memcache.py")){
            $unix->DEBIAN_INSTALL_PACKAGE("python3-memcache");
        }
        if(!is_file("/usr/lib/python3/dist-packages/memcache.py")){
            squid_admin_mysql(0,"Unable to start RDP Proxy Authook Python3 Memcache missing","",__FUNCTION__,__LINE__);
            return false;
        }
    }


	
    if(is_file("/usr/share/artica-postfix/bin/install/passtrough")){
        @unlink("/usr/sbin/passtrough");
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Replicate /usr/sbin/passtrough.py\n";}
        AUTHOOK_LOGS("Duplicate passtrough..",1);
        @copy("/usr/share/artica-postfix/bin/install/passtrough","/usr/sbin/passtrough");
    }else{
        AUTHOOK_LOGS("Duplicate passtrough.py Failed..",0);
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: /usr/share/artica-postfix/bin/install/passtrough, no such file!\n";}
    }

    if(!is_dir("/var/run/rdpproxy")){
        AUTHOOK_LOGS("Creating directory /var/run/rdpproxy",2);
        @mkdir("/var/run/rdpproxy",0755,true);
    }else{
        AUTHOOK_LOGS("directory /var/run/rdpproxy [OK]",2);
    }
    @chmod("/usr/sbin/passtrough",0755);
    @chmod("/var/run/rdpproxy",0755);
    install_syslog();
	$cmd="$nohup /usr/sbin/passtrough >/tmp/authhook.start 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service\n";}
	shell_exec($cmd);




	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=AUTHHOOK_PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=AUTHHOOK_PID_NUM();
	if($unix->process_exists($pid)){
        AUTHOOK_LOGS("Starting service Success...",1);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} Success PID $pid\n";}


	}else{
        AUTHOOK_LOGS("Starting service Failed...",0);
        $f=explode("\n",@file_get_contents("/tmp/authhook.start"));
        foreach ($f as $line){
            $line=trim($line);
            if($line==null){continue;}
            AUTHOOK_LOGS("$line",2);
        }
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}
function AUTHHOOK_STOP($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=AUTHHOOK_PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return true;
	}
	$pid=AUTHHOOK_PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
    AUTHOOK_LOGS("Shutdown PID $pid",1);
	$unix->KILL_PROCESS($pid,9);
	for($i=0;$i<3;$i++){
		$pid=AUTHHOOK_PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/2...\n";}
		sleep(1);
	}

	$pid=AUTHHOOK_PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service success...\n";}
        $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
        AUTHOOK_LOGS("Service stopped, Remove session database",1);
        $q->QUERY_SQL("DELETE FROM rdpproxy_sessions");
		return true;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=AUTHHOOK_PID_NUM();
		if(!$unix->process_exists($pid)){break;}
        unix_system_kill_force($pid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
        AUTHOOK_LOGS("Service failed to stop...",0);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: authhook {$GLOBALS["TITLENAME"]} service failed...\n";}
		return false;
	}

	return true;
}

function ApplyCertificate($CertificateName){

    @unlink("/etc/rdpproxy/rdpproxy.crt");
    @unlink("/etc/rdpproxy/rdpproxy.key");
    $q = new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $sql = "SELECT `srca`,`SquidCert`,`privkey`,`crt`,`Squidkey`,`UsePrivKeyCrt`  FROM sslcertificates WHERE CommonName='$CertificateName'";
    $ligne = $q->mysqli_fetch_array($sql);

    $Expose_content = $ligne["srca"];
    $Expose_content = str_replace("\\n", "\n", $Expose_content);
    @file_put_contents("/etc/rdpproxy/rdpproxy.key", $Expose_content);

    $field = "privkey";
    if ($ligne["UsePrivKeyCrt"] == 0) {
        $field = "Squidkey";
        if (strlen($ligne[$field]) < 10) {
            if (strlen($ligne["privkey"]) > 10) {
                $field = "privkey";
            }
        }

    }
    $Expose_content = $ligne[$field];
    $Expose_content = str_replace("\\n", "\n", $Expose_content);
    @file_put_contents("/etc/rdpproxy/rdpproxy.key", $Expose_content);


    $field="crt";
    if($ligne["UsePrivKeyCrt"]==0){$field="SquidCert";}
    $Expose_content = $ligne[$field];
    $Expose_content = str_replace("\\n", "\n", $Expose_content);
    @file_put_contents("/etc/rdpproxy/rdpproxy.crt", $Expose_content);

}

function build()
{
    $sock = new sockets();
    $unix = new unix();
    $RDPProxyListen = $sock->GET_INFO("RDPProxyListen");
    $RDPProxyPort = intval($sock->GET_INFO("RDPProxyPort"));

    $directories[] = "/var/run/rdpproxy";

    $directories[] = "/var/log/rdpproxy";
    $directories[] = "/var/rdpproxy/recorded/rdp";
    $directories[] = "/var/rdpproxy/recorded/metrics";
    $directories[] = "/var/rdpproxy/tmp";
    $directories[] = "/var/rdpproxy/hash";
    $directories[] = "/etc/rdpproxy/cert/rdplicense";
    $directories[] = "/var/lib/redemption/cache";
    $directories[] = "/var/run/redemption";
    $directories[] = "/var/lib/redemption/cache/mod_rdp";
    $directories[] = "/var/rdpproxy/drive_redirection";


    if(is_file("/etc/rdpproxy/rdpproxy.crt")){
        if(!is_file("/etc/rdpproxy/rdpproxy.crt.org")){
            @copy("/etc/rdpproxy/rdpproxy.crt","/etc/rdpproxy/rdpproxy.crt.org");
        }
    }
    if(is_file("/etc/rdpproxy/rdpproxy.key")){
        if(!is_file("/etc/rdpproxy/rdpproxy.key.org")){
            @copy("/etc/rdpproxy/rdpproxy.key","/etc/rdpproxy/rdpproxy.key.org");
        }
    }





    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    $RDPDisableGroups = $sock->GET_INFO("RDPDisableGroups");
    if (!is_numeric($RDPDisableGroups)) {
        $RDPDisableGroups = 1;
    }

    $RDPProxyListenIP = $unix->InterfaceToIPv4($RDPProxyListen);
    if ($RDPProxyListen == null) {
        $RDPProxyListenIP = "0.0.0.0";
    }
    if ($RDPProxyPort < 5) {
        $RDPProxyPort = 3389;
    }

    $RDPEncryptionLevel=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyListen"));
    $RDPSessionTimeout  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPSessionTimeout"));
    $RDPSessionInacTimeout = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPSessionInacTimeout"));
    if($RDPEncryptionLevel==null){$RDPEncryptionLevel="low";}


    $f[] = "[globals]";
    $f[] = "# Disables (default) or enables support of Glyph Cache.";
    $f[] = "#glyph_cache=no";
    $f[] = "port=$RDPProxyPort";
    $f[] = "#nomouse=no";
    $f[] = "#notimestamp=no";
    $f[] = "encryptionLevel=$RDPEncryptionLevel";
    $f[] = "authfile=/var/run/rdpproxy/auth.sock";
    $f[] = "session_timeout=$RDPSessionTimeout";
    $f[] = "inactivity_timeout=$RDPSessionInacTimeout";

    $f[] = "";
    $f[] = "# Specifies the time to spend on the close box of proxy RDP before closing client window (0 to desactivate)";
    $f[] = "close_timeout=5";
    $f[] = "#auth_channel=";
    $f[] = "";
    $f[] = "# Session record options.";
    $f[] = "# +------+-----------------------------------------+";
    $f[] = "# | Flag | Meaning                                 |";
    $f[] = "# +------+-----------------------------------------+";
    $f[] = "# | 0    | No encryption (faster).                 |";
    $f[] = "# +------+-----------------------------------------+";
    $f[] = "# | 1    | No encryption, with checksum (default). |";
    $f[] = "# +------+-----------------------------------------+";
    $f[] = "# | 2    | Encryption enabled.                     |";
    $f[] = "# +------+-----------------------------------------+";
    $f[] = "#trace_type=0";
    $f[] = "";
    $f[] = "listen_address=$RDPProxyListenIP";
    $f[] = "#enable_transparent_mode=no";
    $f[] = "#certificate_password=";
    $f[] = "#png_path=";
    $f[] = "#wrm_path=";
    $f[] = "# Disables (default) or enables Bitmap Update.";
    $f[] = "enable_bitmap_update=yes";
    $f[] = "enable_close_box=yes";
    $f[] = "#enable_osd=yes";
    $f[] = "#persistent_path=";
    $f[] = "";
    $f[] = "";

    $RDPIgnoreLogonPassword=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPIgnoreLogonPassword"));
    $RDPTlsFallbackLegacy= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPTlsFallbackLegacy"));
    $RDPTlsSupport=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPTlsSupport"));
    $RDPMinTLS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPMinTLS"));
    $RDPSslCipher=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPSslCipher"));
    $RDPProxySSLCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySSLCertificate"));
    $RDPDisconnectOnLogonUserChange=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPDisconnectOnLogonUserChange"));

    if($RDPProxySSLCertificate<>null){
        echo "Configure.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} use the \"$RDPProxySSLCertificate\" certificate\n";
        ApplyCertificate($RDPProxySSLCertificate);

    }else{
          if(is_file("/etc/rdpproxy/rdpproxy.certs.tar.gz")){

              echo "Configure.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} use the default certificate\n";
              shell_exec("tar -xf /etc/rdpproxy/rdpproxy.certs.tar.gz -C /etc/rdpproxy/");
          }
    }

    $RDPSslCipherz[0]="";
    $RDPSslCipherz[1]="HIGH:!ADH:!3DES";
    $RDPSslCipherz[2]="HIGH:!ADH:!3DES:!SHA";

    $f[] = "[client]";
    $f[] = "ignore_logon_password=$RDPIgnoreLogonPassword";
    $f[] = "# Disables or enables (default) support of Bitmap Compression.";
    $f[] = "#bitmap_compression=yes";
    $f[] = "";
    $f[] = "performance_flags_default=0x7";
    $f[] = "#performance_flags_force_present=0";
    $f[] = "#performance_flags_force_not_present=0";
    $f[] = "";
    $f[] = "tls_support=$RDPTlsSupport";
    $f[] = "tls_fallback_legacy=$RDPTlsFallbackLegacy";
    $f[] = "tls_min_level=$RDPMinTLS";
    $f[] = "ssl_cipher_list={$RDPSslCipherz[$RDPSslCipher]}";
    $f[] = "#bogus_neg_request=no";
    $f[] = "rdp_compression=4";
    $f[] = "#disable_tsk_switch_shortcuts=no";
    $f[] = "#max_color_depth=24";
    $f[] = "persistent_disk_bitmap_cache=yes";
    $f[] = "cache_waiting_list=no";
    $f[] = "persist_bitmap_cache_on_disk=yes";
    $f[] = "#fast_path=yes";

    $f[] = "[session_log]";
    $f[] = "enable_session_log=1";
    $f[] = "";
    $f[] = "[mod_rdp]";
    $f[] = "disconnect_on_logon_user_change=$RDPDisconnectOnLogonUserChange";
    $f[] = "rdp_compression=4";
    $f[] = "#open_session_timeout=0";
    $f[] = "enable_nla=yes";
    $f[] = "#enable_kerberos=no";
    $f[] = "persistent_disk_bitmap_cache=yes";
    $f[] = "#cache_waiting_list=yes";
    $f[] = "persist_bitmap_cache_on_disk=yes";
    $f[] = "allow_channels=*";
    $f[] = "#deny_channels=";
    $f[] = "#fast_path=yes";
    $f[] = "#bogus_sc_net_size=yes";
    $f[] = "enable_session_probe=no";
    $f[] = "session_probe_launch_timeout=20000";
    $f[] = "session_probe_keepalive_timeout=2000";
    $f[] = "[mod_vnc]";
    $f[] = "#encodings=2,0,1,-239";
    $f[] = "clipboard_up=no";
    $f[] = "clipboard_down=no";
    $f[] = "#allow_authentification_retries=0";
    $f[] = "";
    $f[] = "";
    $f[] = "[video]";
    $f[] = "#capture_groupid=";
    $f[] = "replay_path=/tmp/";
    $f[] = "# Every 2 seconds.";
    $f[] = "png_interval=20";
    $f[] = "break_interval=60";
    $f[] = "wrm_color_depth_selection_strategy=1";
    $f[] = "wrm_compression_algorithm=1";
    $f[] = "capture_flags=15";
    $RDPProxyVideoPreset=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoPreset"));
    $IsRDPProxyAuthDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IsRDPProxyAuthDebug"));
    if($RDPProxyVideoPreset==null){$RDPProxyVideoPreset="ultrafast";}
    $RDPProxyVideoTun=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoTun"));
    $RDPProxyVideoBitRate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoBitRate"));
    $RDPProxyVideoFrameRate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoFrameRate"));
    if($RDPProxyVideoBitRate==0){$RDPProxyVideoBitRate=30000;}
    if($RDPProxyVideoFrameRate==0){$RDPProxyVideoFrameRate=5;}
    $tune=null;
    if($RDPProxyVideoTun<>null){$tune=" tune=$RDPProxyVideoTun";}
    $f[] = "framerate = $RDPProxyVideoFrameRate";
    $f[] = "ffmpeg_options = profile=baseline preset=$RDPProxyVideoPreset{$tune} flags=+qscale b=$RDPProxyVideoBitRate";
    $f[] = "#disable_keyboard_log=1";
    $f[] = "";
    $f[] = "[debug]";
    if($IsRDPProxyAuthDebug==1) {
        $f[] = "front=1";
        $f[] = "mod_rdp=1";
        $f[] = "session=1";
    }else{
        $f[] = "front=0";
        $f[] = "mod_rdp=0";
        $f[] = "session=0";
    }
    $f[] = "";
    $f[] = "[internal_mod]";
    $f[] = "enable_target_field = false";
    $f[] = "";

    $RDPProxyTheme=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyTheme"));
    $RDPProxyBackGroundColor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyBackGroundColor");
    $RDPProxyFgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyFgcolor");
    $RDPProxySeparatorColor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySeparatorColor");
    $RDPProxyFocusColor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyFocusColor");
    $RDPProxyErrorColor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyErrorColor");
    $RDPProxySelectorLine1Bgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorLine1Bgcolor");
    $RDPProxySelectorLine1Fgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorLine1Fgcolor");

    $RDPProxySelectorLine2Bgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorLine2Bgcolor");
    $RDPProxySelectorLine2Fgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorLine2Fgcolor");

    $RDPProxySelectorSelectedBgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorSelectedBgcolor");
    $RDPProxySelectorSelectedFgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorSelectedFgcolor");
    $RDPProxySelectorFocusBgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorFocusBgcolor");
    $RDPProxySelectorFocusFgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorFocusFgcolor");
    $RDPProxySelectorLabelBgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorLabelBgcolor");
    $RDPProxySelectorLabelFgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorLabelFgcolor");

    $RDPProxyEditBgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyEditBgcolor");
    $RDPProxyEditFgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyEditFgcolor");
    $RDPProxyEditFocusColor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyEditFocusColor");

    if($RDPProxyBackGroundColor==null){$RDPProxyBackGroundColor="#081f60";}
    if($RDPProxyFgcolor==null){$RDPProxyFgcolor="#FFFFFF";}
    if($RDPProxySeparatorColor==null){$RDPProxySeparatorColor="#cfd5eb";}
    if($RDPProxyFocusColor==null){$RDPProxyFocusColor="#004D9C";}
    if($RDPProxyErrorColor==null){$RDPProxyErrorColor="#ffff00";}
    if($RDPProxySelectorLine1Bgcolor==null){$RDPProxySelectorLine1Bgcolor="#000000";}
    if($RDPProxySelectorLine1Fgcolor==null){$RDPProxySelectorLine1Fgcolor="#cfd5eb";}
    if($RDPProxySelectorLine2Bgcolor==null){$RDPProxySelectorLine2Bgcolor="#cfd5eb";}
    if($RDPProxySelectorLine2Fgcolor==null){$RDPProxySelectorLine2Fgcolor="#000000";}
    if($RDPProxySelectorSelectedBgcolor==null){$RDPProxySelectorSelectedBgcolor="#4472C4";}
    if($RDPProxySelectorSelectedFgcolor==null){$RDPProxySelectorSelectedFgcolor="#FFFFFF";}
    if($RDPProxySelectorFocusBgcolor==null){$RDPProxySelectorFocusBgcolor="#004D9C";}
    if($RDPProxySelectorFocusFgcolor==null){$RDPProxySelectorFocusFgcolor="#FFFFFF";}
    if($RDPProxySelectorLabelBgcolor==null){$RDPProxySelectorLabelBgcolor="#4472C4";}
    if($RDPProxySelectorLabelFgcolor==null){$RDPProxySelectorLabelFgcolor="#FFFFFF";}
    if($RDPProxyEditBgcolor==null){$RDPProxyEditBgcolor="#FFFFFF";}
    if($RDPProxyEditFgcolor==null){$RDPProxyEditFgcolor="#000000";}
    if($RDPProxyEditFocusColor==null){$RDPProxyEditFocusColor="#004D9C";}
    $RDPProxyLogo=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyLogo");
    if($RDPProxyLogo==null){$RDPProxyLogo="img/rdpproxy/wablogoblue.png";}
    if($RDPProxyTheme==1){
        $f[]="[theme]";
        $f[]="enable_theme = true";
        $f[]="bgcolor = $RDPProxyBackGroundColor";
        $f[]="fgcolor = $RDPProxyFgcolor";
        $f[]="separator_color = $RDPProxySeparatorColor";
        $f[]="focus_color = $RDPProxyFocusColor";
        $f[]="error_color = $RDPProxyErrorColor";
        $f[]="selector_line1_bgcolor  = $RDPProxySelectorLine1Bgcolor";
        $f[]="selector_line1_fgcolor  = $RDPProxySelectorLine1Fgcolor";
        $f[]="selector_line2_bgcolor  = $RDPProxySelectorLine2Bgcolor";
        $f[]="selector_line2_fgcolor  = $RDPProxySelectorLine2Fgcolor";
        $f[]="selector_selected_bgcolor = $RDPProxySelectorSelectedBgcolor";
        $f[]="selector_selected_fgcolor = $RDPProxySelectorSelectedFgcolor";
        $f[]="selector_focus_bgcolor = $RDPProxySelectorFocusBgcolor";
        $f[]="selector_focus_fgcolor = $RDPProxySelectorFocusFgcolor";
        $f[]="selector_label_bgcolor = $RDPProxySelectorLabelBgcolor";
        $f[]="selector_label_fgcolor = $RDPProxySelectorLabelFgcolor";
        $f[]="edit_bgcolor = $RDPProxyEditBgcolor";
        $f[]="edit_fgcolor = $RDPProxyEditFgcolor";
        $f[]="edit_focus_color=$RDPProxyEditFocusColor";
        $f[]="logo = true";
        $f[]="logo_path = /usr/share/artica-postfix/$RDPProxyLogo";
        $f[]="";
    }

    $f[] = "[metrics]";
    $f[] = "enable_rdp_metrics = 0";
    $f[] =  "#enable_vnc_metrics = 0";
    $f[] =  "log_dir_path = /var/rdpproxy/recorded/metrics";
    $f[] =  "log_interval = 5";
    $f[] =  "log_file_turnover_interval = 24";
    $f[] =  "#sign_key =";

    @file_put_contents("/etc/rdpproxy/rdpproxy.ini", @implode("\n", $f));
    if ($GLOBALS["OUTPUT"]) {
        echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} Success rdpproxy.ini\n";
    }
    if(is_file("/etc/init.d/fail2ban")){
        system("/etc/init.d/fail2ban restart");
    }

}

function CHECK_REQUIRED_LIBS():array{
    $MINZ["/usr/lib/python3/dist-packages/phpserialize.py"]="python3-phpserialize";
    $MINZ["/usr/lib/python3/dist-packages/netaddr/__init__.py"]="python3-netaddr";
    $MINZ["/usr/lib/python3/dist-packages/ldap/__init__.py"]="python3-ldap";
    $MINZ["/usr/lib/python3/dist-packages/DNS/__init__.py"]="python3-dns";
    $MINZ["/usr/lib/python3/dist-packages/geoip2/database.py"]="python3-geoip2";
    return $MINZ;
}

function CHECK_RDPPROXY_NEWVER(){
    $EnableRDPProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRDPProxy"));
    if($EnableRDPProxy==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_RDPPROXY_UPGRADE",0);
        return false;
    }
    $APP_RDPPROXY_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");
    if(!preg_match("#^(9|10|11|12)\.#",$APP_RDPPROXY_VERSION)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_RDPPROXY_UPGRADE",1);
        return true;
    }
    $MINZ=CHECK_REQUIRED_LIBS();
    foreach ($MINZ as $filepath=>$deb){
        if(!is_file($filepath)){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_RDPPROXY_UPGRADE",1);
            return true;
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_RDPPROXY_UPGRADE",0);
    return false;
}

function CLEAN_SESSIONS(){
    CHECK_RDPPROXY_NEWVER();
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM rdpproxy_sessions ORDER BY xtime DESC");

    foreach ($results as $index=>$ligne) {
        $xtime = $ligne["xtime"];
        $ID = $ligne["ID"];
        $diff = (time() - $xtime) / 60;

        if ($diff > 3) {
            $q->QUERY_SQL("DELETE FROM rdpproxy_sessions WHERE ID=$ID");
        }
    }
}

function ROTATE_LOGS(){

    $unix=new unix();
    $LogRotatePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath");
    if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
    CHECK_RDPPROXY_NEWVER();

    $BackupMaxDaysDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));
    if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
    if(!is_dir("$LogRotatePath/rdpproxy")){@mkdir($LogRotatePath."/rdpproxy",0755,true);}



    $SourceFilePath="$LogRotatePath/rdpproxy/service.".time().".log";
    if(!@copy("/var/log/rdpproxy/daemon.log", $SourceFilePath)){
        squid_admin_mysql(0, "[RDPPROXY]: Fatal, unable to copy /var/log/rdpproxy/daemon.log to $SourceFilePath", null,__FILE__,__LINE__);
        return;
    }
    $SourceFilePath2="$LogRotatePath/rdpproxy/auth.".time().".log";
    if(!@copy("/var/log/rdpproxy/auth.log", $SourceFilePath)){
        squid_admin_mysql(0, "[RDPPROXY]: Fatal, unable to copy /var/log/rdpproxy/auth.log to $SourceFilePath2", null,__FILE__,__LINE__);
        return;
    }



    $echo=$unix->find_program("echo");
    shell_exec("$echo \"\" >/var/log/rdpproxy/daemon.log");
    shell_exec("$echo \"\" >/var/log/rdpproxy/auth.log");
    $unix=new unix();$unix->RESTART_SYSLOG(true);



    $hier=strtotime( '-1 days' );
    $hiertime=date("Y-m-d");
    $FinalDirectory="$BackupMaxDaysDir/rdpproxy/".date("Y",$hier)."/".date("m",$hier)."/".date("d",$hier);
    if(!is_dir($FinalDirectory)){@mkdir($FinalDirectory,0755,true);}


    $targetcompressed="$FinalDirectory/rdpproxy-$hiertime.gz";
    $targetcompressed2="$FinalDirectory/rdpproxy-auth-$hiertime.gz";

    if(!$unix->compress($SourceFilePath, $targetcompressed)){
        squid_admin_mysql(0, "[RDPPROXY]: Fatal, unable to compress $SourceFilePath to $targetcompressed", null,__FILE__,__LINE__);
        return;
    }
    if(!$unix->compress($SourceFilePath2, $targetcompressed2)){
        squid_admin_mysql(0, "[RDPPROXY]: Fatal, unable to compress $SourceFilePath to $targetcompressed", null,__FILE__,__LINE__);
        return;
    }


    @unlink($SourceFilePath);
    @unlink($SourceFilePath2);

    $BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
    if($BackupSquidLogsUseNas==1){
        $php=$unix->LOCATE_PHP5_BIN();
        shell_exec("$php /usr/share/artica-postfix/exec.squid.rotate.php --backup-to-nas-rdpprody");
    }
}






?>