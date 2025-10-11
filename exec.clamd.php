<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Clam AntiVirus userspace daemon";
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




// Usage: /etc/init.d/clamav-daemon {start|stop|restart|force-reload|reload-log|reload-database|status}
// exec.clamd.php --monit
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(isset($argv[1])){
    if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
    if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
    if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
    if($argv[1]=="--reload-database"){$GLOBALS["OUTPUT"]=true;reload_database();exit();}
    if($argv[1]=="--reload-log"){$GLOBALS["OUTPUT"]=true;reload();exit();}
    if($argv[1]=="--force-reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
    if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
    if($argv[1]=="--monit"){$GLOBALS["OUTPUT"]=true;install_monit();exit();}
    if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
    if($argv[1]=="--remove"){$GLOBALS["OUTPUT"]=true;remove();exit();}
    if($argv[1]=="--install-fresh"){$GLOBALS["OUTPUT"]=true;install_clamav_freshclam();exit();}
    if($argv[1]=="--reconfigure"){$GLOBALS["OUTPUT"]=true;reconfigure();exit();}
    if($argv[1]=="--permissions"){$GLOBALS["OUTPUT"]=true;permissions();exit();}
    if($argv[1]=="--sockets"){CheckClamavSocket();exit;}
    if($argv[1]=="--socket-watch"){CheckClamavSocket(true);exit;}
    if($argv[1]=="--apparmor"){apparmor();exit;}
    if($argv[1]=="--memory-exceed"){memory_exceed();exit;}
    if($argv[1]=="--restart-schedule"){restart_schedule();exit;}
}

function memory_exceed():bool{
    $unix = new unix();
    $pid = PID_NUM();
    $KB = $unix->MEMORY_OF($pid);
    $Text = $unix->FormatBytes($KB);
    squid_admin_mysql("{watchdog} {APP_CLAMAV} {memory} $Text {action}={restart}", null, __FILE__, __LINE__);
    restart();
    return true;
}

function remove(){
    $prc=10;
    $unix=new unix();
    $aptget=$unix->find_program("apt-get");

    $pid=$unix->PIDOF("/usr/bin/dpkg");
    if($unix->process_exists($pid)){
        build_progress(110,"dpkg {already_running} pid $pid");
        return false;
    }
    $pid=$unix->PIDOF_PATTERN("/var/lib/dpkg/");
    if($unix->process_exists($pid)){
        $process=@file_get_contents("/proc/$pid/cmdline");
        echo "Existing process: $process\n";
        build_progress(110,"dpkg {already_running} pid $pid");
        return false;
    }

    $opts="-o Dpkg::Options::=\"--force-confold\"  -o Dpkg::Options::=\"--force-remove-reinstreq\"";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableClamavDaemon", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFreshClam", 0);

    $packages=explode(",","clamav,clamav-base,clamav-daemon,clamav-freshclam,clamdscan,libclamav-dev,libclamav9,libclamav*,clamav*");
    foreach ($packages as $pkg){
        $prc++;
        build_progress($prc,"{remove} $pkg");
        $cmdline="DEBIAN_FRONTEND=noninteractive $aptget purge -y -q $opts $pkg";
        echo $cmdline."\n";
        system($cmdline);
    }
    $tdirs[]="/lib";
    $tdirs[]="/usr/lib";
    $tdirs[]="/usr/local/lib";
    $tdirs[]="/usr/lib/x86_64-linux-gnu";
    foreach ($tdirs as $basedir){
        $prc++;
        build_progress($prc,"{remove} libclam* [$basedir]");
        if(!is_dir($basedir)){continue;}
        $handle = opendir($basedir);
        while (false !== ($filename = readdir($handle))) {
            if ($filename == ".") {continue;}
            if ($filename == "..") {continue;}
            $tpath="$basedir/$filename";
            if(is_dir($tpath)){continue;}
            if(preg_match("#^libclam#",$filename)){
                echo "Removing $tpath\n";
            }
        }
    }

    $artfiles[]="/etc/cron.d/clamd-cron";
    $artfiles[]="/etc/monit/conf.d/APP_CLAMAV.monitrc";
    foreach ($artfiles as $tfile){
        if(is_file($tfile)){@unlink($tfile);}
    }



    build_progress(40,"{uninstalling}");
    if(is_file("/etc/monit/conf.d/APP_CLAMAV.monitrc")) {
        @unlink("/etc/monit/conf.d/APP_CLAMAV.monitrc");
    }
    shell_exec("/etc/init.d/cron reload");
    build_progress(70,"{uninstalling}");
    $unix->reload_monit();
    build_progress(80,"{uninstalling}");
    $dirs[]="/etc/clamav";
    $dirs[]="/var/run/clamav";
    $dirs[]="/var/lib/clamav*";
    $dirs[]="/var/log/clamav";
    $unix=new  unix();
    $rm=$unix->find_program("rm");
    build_progress(90,"{uninstalling}");
    foreach ($dirs as $directory){if(is_dir($directory)){shell_exec("$rm -rf $directory/*");}}

    $servs[]="/etc/init.d/clamav-daemon";
    $servs[]="/etc/init.d/clamav-freshclam";
    foreach ($servs as $tfile){
        if(is_file($tfile)){
            remove_service($tfile);
            build_progress(91,"{uninstalling} $tfile");
        }
    }


    build_progress(100,"{uninstalling} {done}");
    return true;
}



function restart_schedule():bool{
    squid_admin_mysql(2,"{schedule} {restarting} {APP_CLAMAV} {action}={restart}",null,__FILE__,__LINE__);
    restart();
    return true;
}

function restart():bool{
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return false;
	}
	@file_put_contents($pidfile, getmypid());
	build_progress_restart(30,"{stopping_service}");
	stop(true);
	build_progress_restart(50,"{reconfiguring}");
	build();
	build_progress_restart(70,"{starting_service}");
	sleep(1);
	if(!start(true)){
		build_progress_restart(110,"{starting_service} {failed}");
		return false;
	}
	build_progress_restart(90,"{starting_service} {success}");
	$cicap=$unix->find_program("c-icap");
	if(is_file($cicap)){
		$CicapEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"));
		if($CicapEnabled==1){
			build_progress_restart(95,"{restarting_service} {APP_C_ICAP}");
            $unix->CICAP_SERVICE_EVENTS("Reloading ICAP service",__FILE__,__LINE__);
			system("/etc/init.d/c-icap reload");}
	}
	build_progress_restart(100,"{restarting_service} {done}");
    return true;
}
function build_progress_restart($pourc,$text):bool{
    $unix=new unix();
    _out($text);
   return $unix->framework_progress($pourc,$text,"clamd.restart");
}

function build_progress($pourc,$text):bool{
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." $pourc% $echotext\n";
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"clamd.progress");
    $unix->framework_progress(10,"$pourc% $echotext","cicap.install.progress");
    return true;
}

function uninstall():bool{
	$sock=new sockets();
	$unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
	build_progress(10,"{uninstalling}");
	$sock->SET_INFO("EnableClamavDaemon", 0);
	$sock->SET_INFO("EnableFreshClam", 0);
    $sock->SET_INFO("EnableeCapClamav", 0);
	build_progress(20,"{uninstalling}");
	remove_service("/etc/init.d/clamav-daemon");
	build_progress(30,"{uninstalling}");
	remove_service("/etc/init.d/clamav-freshclam");
	build_progress(40,"{uninstalling}");
	@unlink("/etc/monit/conf.d/APP_CLAMAV.monitrc");
	build_progress(50,"{uninstalling}");
	@unlink("/etc/cron.d/clamd-cron");
	shell_exec("/etc/init.d/cron reload");
	build_progress(70,"{uninstalling}");
    $unix->reload_monit();
	build_progress(80,"{uninstalling}");
	$dirs[]="/var/run/clamav";
	$dirs[]="/var/lib/clamav";
	$dirs[]="/var/log/clamav";
	$unix=new  unix();
	$rm=$unix->find_program("rm");
	build_progress(90,"{uninstalling}");
	foreach ($dirs as $directory){if(is_dir($directory)){shell_exec("$rm -rf $directory");}}
	build_progress(100,"{uninstalling} {done}");
    return true;
}

function install(){
	$unix=new unix();

	$unix->Popuplate_cron_make("clamd-cron","0 */2 * * *","exec.freshclam.php --sigtool");

	$sock=new sockets();
	$sock->SET_INFO("EnableClamavDaemon", 1);
	$sock->SET_INFO("EnableFreshClam", 1);
	build_progress(10,"{installing}");
	install_clamav_daemon();
	install_clamav_freshclam();
	install_monit();
	reconfigure();
	build_progress(95,"{starting_service}...");
	start(true);
	system("/etc/init.d/clamav-freshclam start");
	build_progress(100,"{installing} {success}");
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}
function install_monit():bool{
	$f=array();
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $monitfile="/etc/monit/conf.d/APP_CLAMAV.monitrc";
    $md51=null;
    if(is_file($monitfile)){
        $md51=md5_file($monitfile);
    }

    $ClamavRefreshDaemonTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavRefreshDaemonTime"));
    if($ClamavRefreshDaemonTime==0){
        $unix->Popuplate_cron_delete("clamd-restart");
    }else{
        $hoursEX[15]="*/15 * * * *";
        $hoursEX[30]="*/30 * * * *";
        $hoursEX[60]="0 * * * *";
        $hoursEX[120]="0 */2 * * *";
        $hoursEX[180]="0 */3 * * *";
        $hoursEX[420]="0 */4 * * *";
        $hoursEX[480]="0 */8 * * *";
        $unix->Popuplate_cron_make("clamd-restart",$hoursEX[$ClamavRefreshDaemonTime],basename(__FILE__)." --restart-schedule");
    }






    $f[]="#!/bin/sh";
    $f[]="$php ".__FILE__." --memory-exceed";
    $f[]="exit 0";
    @file_put_contents("/usr/sbin/clamd-memory-exceed.sh",@implode("\n",$f));
    @chmod("/usr/sbin/clamd-memory-exceed.sh",0755);

    $f=array();
	$f[]="check process APP_CLAMAV with pidfile /var/run/clamav/clamd.pid";
	$f[]="\tstart program = \"/etc/init.d/clamav-daemon start --monit\"";
	$f[]="\tstop program =  \"/etc/init.d/clamav-daemon stop --monit\"";
    $f[]="\tif failed unixsocket /var/run/clamav/clamav.sock protocol clamav then exec \"/etc/init.d/clamav-daemon socket\"";

    $ClamavRefreshDaemonMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavRefreshDaemonMemory"));
    if($ClamavRefreshDaemonMemory>0){
        if($ClamavRefreshDaemonMemory<1500){
            $ClamavRefreshDaemonMemory=1500;
        }
    }
    if($ClamavRefreshDaemonMemory>0){
        $f[]="\tif totalmem > $ClamavRefreshDaemonMemory MB for 2 cycles then exec \"/usr/sbin/clamd-memory-exceed.sh\"";
    }


	$f[]="if 5 restarts within 5 cycles then timeout";
	$f[]="";
	$f[]="check process APP_FRESHCLAM";
	$f[]="with pidfile /var/run/clamav/freshclam.pid";
	$f[]="start program = \"/etc/init.d/clamav-freshclam start --monit\"";
	$f[]="stop program =  \"/etc/init.d/clamav-freshclam stop --monit\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	$f[]="";
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Clamd service...\n";}
	@file_put_contents("/etc/monit/conf.d/APP_CLAMAV.monitrc", @implode("\n", $f));

    $md52=md5_file($monitfile);
    if($md51==$md52){return true;}
    $unix->MONIT_RELOAD();
    return true;
}
function _out($text):bool{
    $unix=new unix();
    $unix->ToSyslog("$text",false,"clamav-daemon");
    $date=date("H:i:s");
    echo "Starting......: $date [INIT]: clamav-daemon Service: $text\n";
    return true;
}

function CheckClamavSocket($watchdog=true):bool{
    $socket_path    = "/var/run/clamav/clamav.sock";
    $pidfile        = "/etc/artica-postfix/pids/exec.clamd.php.start.pid";
    $unix=new unix();
    if(!is_file("/etc/init.d/clamav-daemon")){
        echo "No clamav-daemon init found\n";
        return false;
    }
    if($watchdog){
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid)){
            _out("Already task pid $pid is running");
            return false;
        }

    }

    if($GLOBALS["VERBOSE"]){echo "stream_socket_client()\n";}
    $socket = stream_socket_client("unix://$socket_path", $errorno, $errorstr, 600);

    if(!$socket){
        if($watchdog){
            squid_admin_mysql(2,"Error $errorno on clamav.sock [ {action} = {restart} ]",$errorstr,__FILE__,__LINE__);
            shell_exec("/etc/init.d/clamav-daemon restart");
        }
        return false;
    }

    fwrite($socket, "PING", 4);
    $pingResponse = fread($socket,4);
    if($GLOBALS["VERBOSE"]){echo "Server response = $pingResponse\n";}

    if ($pingResponse == "PONG") {
        if($GLOBALS["VERBOSE"]){echo "ClamAV is Alive!\n";}
        $ClamUser=$unix->ClamUser();
        @chown($socket_path,$ClamUser);
        @chmod($socket_path, 0777);
        return true;
    }

    if($watchdog) {
        _out("Error ping PING/$pingResponse on clamav.sock");
        squid_admin_mysql(2, "Error PING/$pingResponse on clamav.sock [action=reload]",
            $pingResponse, __FILE__, __LINE__);
        shell_exec("/etc/init.d/clamav-daemon reload");
    }
   return false;
}


function reconfigure():bool{
	$md5=md5_file("/etc/clamav/clamd.conf");
	build_progress(20, "{reconfiguring}");
	build();
    $md52=md5_file("/etc/clamav/clamd.conf");
    if($md5==$md52){
        install_monit();
        build_progress(100, "{success}");
        return true;
    }
	build_progress(95, "{reloading}");
    install_monit();
	reload_database();
	build_progress(100, "{success}");
    return true;
}


function install_clamav_daemon(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/clamav-daemon";
	$php5script="exec.clamd.php";
	$daemonbinLog="Clam AntiVirus userspace daemon";
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:        clamav-daemon";
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
	$f[]=" build)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]=" force-reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --force-reload \$2 \$3";
	$f[]="    ;;";
	$f[]=" reload-database)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload-database \$2 \$3";
	$f[]="    ;;";
	$f[]=" reload-log)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload-log \$2 \$3";
	$f[]="    ;;";
    $f[]=" socket)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --socket-watch \$2 \$3";
    $f[]="    ;;";

	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: $INITD_PATH {start|stop|restart|force-reload|reload-log|reload-database|socket|status} (+ '--verbose' for more infos)\"";
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

function apparmor(){
    $socket_path    = "run/clamav/clamav.sock";
    $fname="/etc/apparmor.d/usr.sbin.clamd";

    $f[]="#include <tunables/global>";
    $f[]="";
    $f[]="/usr/sbin/clamd  flags=(complain){";
    $f[]="  #include <abstractions/base>";
    $f[]="  #include <abstractions/nameservice>";
    $f[]="  #include <abstractions/openssl>";
    $f[]="";
    $f[]="  capability dac_override,";
    $f[]="  capability setgid,";
    $f[]="  capability setuid,";
    $f[]="  capability chown,";
    $f[]="  @{PROC}/filesystems r,";
    $f[]="  @{PROC}/[0-9]*/status r,";
    $f[]="  /etc/clamav/clamd.conf r,";
    $f[]="  /usr/sbin/clamd mr,";
    $f[]="  /tmp/ rw,";
    $f[]="  /tmp/** krw,";
    $f[]="";
    $f[]="  /var/lib/clamav/ r,";
    $f[]="  /var/lib/clamav/** krw,";
    $f[]="  /var/log/clamav/* krw,";
    $f[]="  /{,var/}run/clamav/clamd.ctl w,";
    $f[]="  /{,var/}run/clamav/clamd.pid w,";
    $f[]="  /var/run/clamav/clamd.pid w,";
    $f[]="  /var/run/clamav/clamav.sock w,";
    $f[]="  /{,var/}$socket_path w,";
    $f[]="  /{,var/}run/clamav/clamd.pid w,";
    $f[]="  /var/spool/clamsmtp/* r,";
    $f[]="  /var/spool/qpsmtpd/* r,";
    $f[]="  /var/spool/p3scan/children/** r,";
    $f[]="  /var/spool/havp/** r,";
    $f[]="  /var/lib/amavis/tmp/** r,";
    $f[]="  /var/spool/MIMEDefang/mdefang-*/Work/ r,";
    $f[]="  /var/spool/MIMEDefang/mdefang-*/Work/** r,";
    $f[]="  # Allow home dir to be scanned,";
    $f[]="  @{HOME}/ r,";
    $f[]="  @{HOME}/** r,";
    $f[]="  # Site-specific additions and overrides. See local/README for details.";
    $f[]="  #include <local/usr.sbin.clamd>";
    $f[]="}";
    @file_put_contents($fname,@implode("\n",$f));
    $unix=new unix();
    $aa_complain=$unix->find_program("aa-complain");
    shell_exec("$aa_complain /usr/sbin/clamd");
}



function reload($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("clamd");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, clamd not installed\n";}
		return;
	}

	$EnableClamavDaemon=$sock->EnableClamavDaemon();
	if($EnableClamavDaemon==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see sock->EnableClamavDaemon)\n";}
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
	$pid=PID_NUM();
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service reloading PID $pid running since {$timepid}Mn...\n";}
		unix_system_HUP($pid);
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not running\n";}

}



function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("clamd");
    $pidfile="/etc/artica-postfix/pids/exec.clamd.php.start.pid";

	if(!is_file($Masterbin)){
        _out("Clamd not installed");
		return false;
	}

	if(!$aspid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
            _out("Already Artica task running PID $pid since {$time}mn");
			return false;
		}

	}
    @file_put_contents($pidfile, getmypid());
	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		_out("Service already started $pid since {$timepid}Mn...");
		@file_put_contents("/var/run/clamav/clamd.pid", $pid);
		return true;
	}

	$EnableClamavDaemon=$sock->EnableClamavDaemon();
    apparmor();



	if($EnableClamavDaemon==0){
        _out("service disabled (see sock->EnableClamavDaemon)");
		return false;
	}


	$nohup=$unix->find_program("nohup");
	$aa_complain=$unix->find_program('aa-complain');
	if(is_file($aa_complain)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} add clamd Profile to AppArmor..\n";}
		shell_exec("$aa_complain $Masterbin >/dev/null 2>&1");
	}




	$YaraIncompatible=unserialize(@file_get_contents("/etc/artica-postfix/YaraIncompatible.db"));
    if(is_array($YaraIncompatible)) {
        foreach ($YaraIncompatible as $path => $line) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }



	$clamd_version=clamd_version();
	$file_temp=$unix->FILE_TEMP();
	build();
	$cmd="$Masterbin --config-file=/etc/clamav/clamd.conf >$file_temp 2>&1";
    _out("Starting clamav-daemon version $clamd_version [$cmd]");
    $cmdsh=$unix->sh_command($cmd);
    $unix->go_exec($cmdsh);


    $restart_prc=70;
	for($i=1;$i<21;$i++){
        $restart_prc++;
        build_progress_restart($restart_prc,"Waiting $i/20");
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
        if($i==20){break;}
	}

	$RESTART=false;
    $file_data=@file_get_contents($file_temp);
	$f=explode("\n",$file_data);
	foreach ($f as $line){

        if(preg_match("#LibClamAV Error: mpool_malloc.*?allocate memory#i",$line,$re)){
            echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Memory issue, disable service\n";
            squid_admin_mysql(0,"ClamAV No enough memory ( need at least 1GB free memory ) (see content), remove feature",$file_data,__FILE__,__LINE__);
            uninstall();
            return false;
        }


        if(preg_match("#clamd:\s+(.+?): no version information available#i",$line,$re)){
            $libpath=trim($re[1]);
            echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $libpath library issue\n";
            @unlink($libpath);
            fixClamLibs();
            squid_admin_mysql(0,"ClamAV $libpath library issue (see content), try to fix it",$file_data,__FILE__,__LINE__);
            return false;
        }

        if(preg_match("#ERROR: This tool requires libclamav with functionality level#i",$line,$re)){
            _out("Functionality level issue");
            fixClamLibs();
            squid_admin_mysql(0,"ClamAV compatibility issue (see content), try to fix it",$file_data,__FILE__,__LINE__);
            return false;
        }

		if(preg_match("#Error: cli_loadyara: failed to parse rules file (.*?),#i", $line,$re)){
            _out("Removing incompatible Yara rule $re[1]");
			$YaraIncompatible[$re[1]]=true;
			$RESTART=true;
			continue;
		}

       _out("Starting: $line");
	}
	@unlink($file_temp);
	@file_put_contents("/etc/artica-postfix/YaraIncompatible.db", serialize($YaraIncompatible));
	if($RESTART){return start(true);}


	$pid=PID_NUM();
	if($unix->process_exists($pid)){
        _out("Starting: Success PID $pid");
		sleep(1);
		for($i=1;$i<31;$i++){

			if(CheckClamavSocket()){
				_out("Starting: Apply permissions on clamav.sock");
				@chmod("/var/run/clamav/clamav.sock", 0777);
                if(is_file("/etc/init.d/c-icap")){
                    $unix->CICAP_SERVICE_EVENTS("Reloading ICAP service",__FILE__,__LINE__);
                    shell_exec("$nohup /etc/init.d/c-icap reload >/dev/null 2>&1 &");
                }

				break;
			}else{
                $restart_prc++;
                if($restart_prc>99){$restart_prc=99;}
                build_progress_restart($restart_prc," Waiting for socket... $i/30 /var/run/clamav/clamav.sock");
				sleep(1);
			}
		}

		if($unix->is_socket("/var/run/clamav/clamav.sock")){
			_out("Starting: Apply permissions on clamav.sock");
			@chmod("/var/run/clamav/clamav.sock", 0777);

		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} socket failed\n";}
		}

	}else{
        _out("Starting: Failed");
        _out("$cmd");
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){return false;}

	if(!$unix->is_socket("/var/run/clamav/clamav.sock")){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} socket Failed..\n";}return false;}
	return true;

}

function clamd_version(){
	if(isset( $GLOBALS["clamd_version"])){return  $GLOBALS["clamd_version"];}
	$unix=new unix();
	$Masterbin=$unix->find_program("clamd");
	exec("$Masterbin -V 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#ClamAV\s+([0-9\.]+)\/#i", $line,$re)){
			$GLOBALS["clamd_version"]=$re[1];
			return $GLOBALS["clamd_version"];
		}
	}
}

function PID_NUM():int{
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/clamav/clamd.pid");
	if($unix->process_exists($pid)){return intval($pid);}
	$Masterbin=$unix->find_program("clamd");
	return $unix->PIDOF($Masterbin);

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
	$chmod=$unix->find_program("chmod");



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



