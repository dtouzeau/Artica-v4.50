#!/usr/bin/php
<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');
include_once(dirname(__FILE__) . '/ressources/class.openssh.inc');
$GLOBALS["TITLENAME"]="Shell In a Box daemon";
$GLOBALS["MONIT"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#--monit#", @implode(" ", $argv))){$GLOBALS["MONIT"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit;}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit;}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit;}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit;}
if($argv[1]=="--progress"){$GLOBALS["OUTPUT"]=true;progress();exit;}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install_service();exit;}
if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstal();exit;}


function build_progress($text,$pourc):bool{
	$unix=new unix();
    $unix->framework_progress($pourc,$text,"bandwhich.progress");
    return true;
}
function build_progress_restart($text,$pourc):bool{
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"bandwhich.restart");
    return true;
}



function install_service():bool{
	build_progress("{stopping_service} Watchdog",15);
	system("/etc/init.d/artica-status stop");
	
	build_progress("{install_service} {APP_SHELLINABOX}",30);
	create_bandwhich_service();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableWebBandwhich", 1);

	build_progress("{starting_service} {APP_SHELLINABOX}",40);
	if(!start(true)){
		build_progress("{starting_service} {APP_SHELLINABOX} {failed}",110);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableWebBandwhich", 0);
		remove_service("/etc/init.d/bandwhich");
		return false;
	}
	build_progress("{starting_service} {APP_SHELLINABOX}",50);
	install_monit();
	
	
	build_progress("{restarting_service} {artica_status}",90);
	system("/etc/init.d/artica-status restart --force");
	build_progress("{install_service} {success}",100);
    return true;
	
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}
function install_monit():bool{
    $unix=new unix();
	@unlink("/etc/monit/conf.d/APP_BANDWIHCH.monitrc");
	$f[]="check process APP_BANDWIHCH with pidfile /var/run/bandwhich.pid";
	$f[]="\tstart program = \"/etc/init.d/bandwhich start\"";
	$f[]="\tstop program = \"/etc/init.d/bandwhich stop\"";
	$f[]="\tif failed unixsocket /var/run/bandwhich.sock then restart";

	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_BANDWIHCH.monitrc", @implode("\n", $f));
    $unix->reload_monit();
    return true;
}

function uninstal():bool{
    $unix=new unix();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableWebBandwhich", 0);
	build_progress("{uninstall_service} {APP_SHELLINABOX}",15);
	@unlink("/etc/monit/conf.d/APP_BANDWIHCH.monitrc");
	build_progress("{uninstall_service} {APP_SHELLINABOX}",30);
    $unix->reload_monit();
	$GLOBALS["FORCE"]=true;
	remove_service("/etc/init.d/bandwhich");
	build_progress("{uninstall_service} {APP_SHELLINABOX} {success}",100);
    return true;
}


function create_bandwhich_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$php5script=__FILE__;
	$f[]="#! /bin/sh";
	$f[]="";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:		bandwhich";
	$f[]="# Required-Start:	\$remote_fs \$syslog";
	$f[]="# Required-Stop:	\$remote_fs \$syslog";
	$f[]="# Default-Start:	2 3 4 5";
	$f[]="# Default-Stop:		";
	$f[]="# Short-Description:	OpenBSD Secure Shell server";
	$f[]="### END INIT INFO";
	$f[]="";
    $f[]="LC_ALL=C";
    $f[]="PATH=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin\"";
	$f[]="set -e";

	
	$f[]="case \"\$1\" in";
	$f[]=" start)";

	$f[]="    $php $php5script --start \$2 \$3";
	$f[]="	  exit 1";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php $php5script --stop \$2 \$3";
	$f[]="	  exit 1";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php $php5script --restart \$2 \$3";
	$f[]="	  exit 1";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php $php5script --reload \$2 \$3";
	$f[]="	  exit 1";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php $php5script --reload \$2 \$3";
	$f[]="	  exit 1";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	$f[]="";
	

	echo "SHELL IN A BOX: [INFO] Writing /etc/init.d/bandwhich with new config\n";
	@unlink("/etc/init.d/shellinabox");
	@file_put_contents("/etc/init.d/bandwhich", @implode("\n", $f));
	
	
	@chmod("/etc/init.d/bandwhich",0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename("/etc/init.d/shellinabox")." defaults >/dev/null 2>&1");
	
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename("/etc/init.d/shellinabox")." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename("/etc/init.d/shellinabox")." on >/dev/null 2>&1");
	}
}




function reload(){
	$unix=new unix();
    $pid=PID_NUM();
	if($unix->process_exists($pid)){
		unix_system_HUP($pid);
	}
	
	
	
}
function restart():bool{
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
        build_progress_restart("Already Artica task running PID $pid since {$time}mn",110);
		return false;
	}

	$pid=PID_NUM();
	if(!$GLOBALS["FORCE"]){
		if($unix->process_exists($pid)){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($time<10){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} only restart each 10mn is allowed\n";}
                build_progress_restart("Only restart each 10mn is allowed",110);
				return false;
			}
		}
	}


	@file_put_contents($pidfile, getmypid());
    build_progress_restart("{stopping}",50);
	stop(true);
	sleep(1);
    build_progress_restart("{starting}",70);
	if(!start(true)){
        build_progress_restart("{starting} {failed}",110);
        return false;
    }
    build_progress_restart("{starting} {success}",100);
    return true;
}

function CheckWebConsole():bool{
    $unix=new unix();
    $f=explode("\n",@file_get_contents("/etc/artica-postfix/webconsole.conf"));
    foreach ($f as $line){
        if(preg_match("#APP_BANDWHICH#",$line)){
            return true;
        }
    }

    $unix->go_exec("/etc/init.d/artica-webconsole restart");
    return true;

}


function start($aspid=false):bool{
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		@file_put_contents("/var/run/bandwhich.pid", $pid);
		return true;
	}
	$shellinaboxd=$unix->find_program("shellinaboxd");
	if(!is_file($shellinaboxd)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} shellinaboxd no such binary\n";}
        uninstal();
		return false;
	}
	
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		$time=$unix->PROCESS_TTL($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} shellinaboxd already running $pid since $time\n";}
		return true;
	}



    $bandwhich="/usr/sbin/bandwhich";
    if(!is_file($bandwhich)){
        @copy("/usr/share/artica-postfix/bin/bandwhich",$bandwhich);
        @chmod($bandwhich,0755);
    }
    CheckWebConsole();
	$t[]="$shellinaboxd";
	$t[]="--background=/var/run/bandwhich.pid";
	$t[]="--disable-ssl";
	$t[]="--numeric";
	$t[]="--localhost-only";
	$t[]="--user=0";
	$t[]="--group=0";
	$t[]="--no-beep --service='/:root:root:/:$bandwhich -t'";
	$t[]="--unixdomain-only=/var/run/bandwhich.sock:www-data:www-data:0755";
	$cmd=@implode(" ", $t);
    $sh=$unix->sh_command($cmd);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}....\n";}
	$unix->go_exec($sh);

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		return true;

	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	return false;

}
function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return true;
	}
	$pid=PID_NUM();

	if($GLOBALS["MONIT"]){
		$pid=PID_NUM();
		if($unix->process_exists($pid)){
			@file_put_contents("/var/run/bandwhich.pid", $pid);
			return true;
		}
	}


	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	if($GLOBALS["FORCE"]){unix_system_kill_force($pid);}
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return true;
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
		return false;
	}
    return true;

}
function PID_NUM():int{
	$unix=new unix();
	$pidfile="/var/run/bandwhich.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN("shellinaboxd.*?bandwhich.sock");

}