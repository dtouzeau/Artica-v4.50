<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/mysql.status.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');


$GLOBALS["SINGLE_DEBUG"]=false;
$GLOBALS["BY_SOCKET_FAILED"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["BY_FRAMEWORK"]=null;
$GLOBALS["BY_WIZARD"]=false;
$GLOBALS["CMDLINE"]=implode(" ",$argv);
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--socketfailed#",implode(" ",$argv))){$GLOBALS["BY_SOCKET_FAILED"]=true;}
if(preg_match("#--framework=(.+?)$#",implode(" ",$argv),$re)){$GLOBALS["BY_FRAMEWORK"]=$re[1];}
if(preg_match("#--bywizard#",implode(" ",$argv),$re)){$GLOBALS["BY_WIZARD"]=true;}
if(preg_match("#--progress#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;$GLOBALS["BY_FRAMEWORK"]="Manual";$GLOBALS["PROGRESS"]=true;$GLOBALS["BY_WIZARD"]=true;}

if($argv[1]=="--start"){SERVICE_START();die(0);}
if($argv[1]=="--stop"){SERVICE_STOP();die(0);}
if($argv[1]=="--restart"){SERVICE_RESTART();die(0);}
if($argv[1]=="--monit"){install_monit();exit();}
if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--uninstall"){uninstall();exit();}



function PID_NUM():int{
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/manticore/searchd.pid");
	if($unix->process_exists($pid)){return $pid;}
    $searchd=$unix->find_program("searchd");
    return $unix->PIDOF($searchd);
}
function build_progress($pourc,$text):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"manticore.progress");
}
function SERVICE_RESTART():bool{
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	$pid=@file_get_contents($pidfile);

	
	$LastExec=$unix->file_time_min($pidTime);
	if($LastExec<1){
			_out("Restarting MantiCore service Aborted Need at least 1mn");
			return false;
	}

	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($time<5){
            _out("Restarting MantiCore service Aborted an artica task $pid is running");
			return false;
		}
        _out("Killing `Restart task` Running too long {$time}Mn");
		unix_system_kill_force($pid);
	}


    _out("Restarting MantiCore service");
	
	if($GLOBALS["FORCE"]){
        _out("Restarting MantiCore using Force mode !");
	}
	

		
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		squid_admin_mysql(0, "{restarting} {APP_MANTICORE} by=[{$GLOBALS["BY_FRAMEWORK"]}] Service is not running,  start it",__FILE__,__LINE__);
		build_progress(50,"{starting_service}");
		if(!SERVICE_START(false,true)){
			return build_progress(110,"{starting_service} {failed}");
		}
		return true;
	}
	
	$time=$unix->PROCESS_TTL($pid);
	squid_admin_mysql(0, "{restarting} {APP_MANTICORE} {running} {since} {$time}Mn by=[{$GLOBALS["BY_FRAMEWORK"]}]...",__FILE__,__LINE__);
	build_progress(30, "{stopping_service}");
	SERVICE_STOP(true);
	build_progress(50, "{starting_service}");
	if(!SERVICE_START(true)){return build_progress(110, "{failed}");}
	return build_progress(100, "{starting_service} {success}");
}




function SERVICE_STOP($aspid=false):bool{
	$unix=new unix();

	$prc=30;
	build_progress($prc++, "{stopping_service}");
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);

	if(!$aspid){
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			_out("Stopping This script is already executed PID: $pid since {$time}Mn");
			if($time<5){if(!$GLOBALS["FORCE"]){return false;}}
			unix_system_kill_force($pid);
		}
		
		
		@file_put_contents($pidfile, getmypid());		
	}
	
	$pid=PID_NUM();  
	if($GLOBALS["VERBOSE"]){echo "DEBUG:: PID RETURNED $pid\n";}


    _out("Stopping MantiCore server");
	if(!$unix->process_exists($pid)){
		build_progress($prc++, "{stopping_service}");
        _out("Stopping MantiCore server Already stopped");
        return true;
    }
	
	

	build_progress($prc++, "{stopping_service}");
	squid_admin_mysql(0,"Stopping MantiCore service PID $pid", null,__FILE__,__LINE__);
    _out("Stopping MantiCore killing smoothly PID $pid");
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		build_progress($prc++, "{stopping_service}");
		sleep(1);
		$pid=PID_NUM();  
		if(!$unix->process_exists($pid)){break;}
	}	
	
	build_progress($prc++, "{stopping_service}");
	if(!$unix->process_exists($pid)){
        _out("Stopping MantiCore Stopped success");
		squid_admin_mysql(2, "Success to STOP MantiCore server", __FUNCTION__, __FILE__, __LINE__);
		return true;
	}
	
	build_progress($prc++, "{stopping_service}");
    _out("Stopping MantiCore Force killing PID $pid");
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		sleep(1);
		build_progress($prc++, "{stopping_service}");
		$pid=PID_NUM();  
		if(!$unix->process_exists($pid)){break;}
        unix_system_kill_force($pid);
	}	

	build_progress($prc++, "{stopping_service}");
	if(!$unix->process_exists($pid)){
        _out("Stopping MantiCore Stopped success");
		squid_admin_mysql(2, "Success to STOP MantiCore server", __FUNCTION__, __FILE__, __LINE__);
		return true;
	}


    _out("Stopping MantiCore failed");
	return false;
}
function _out($text):bool{
    echo "MantiCore.....: ".date("H:i:s")." [INIT]: $text\n";
    if(!function_exists("openlog")){return false;}
    openlog("manticore", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}

function build_conf():bool{
    $unix=new unix();
    $DIRS[]="/home/manticore/database";
    $DIRS[]="/home/manticore/databases/powerdns";
    $DIRS[]="/var/log/manticore";
    $DIRS[]="/var/run/manticore";
    $DIRS[]="/var/run/mysqld";
    $DIRS[]="/etc/manticoresearch";


    if(!$unix->CreateUnixUser("manticore","manticore","Manticore Search database")){
        _out("Failed to create manticore user");
        return false;

    }

    foreach ($DIRS as $directory){
        if(!is_dir($directory)){
            @mkdir($directory,0755,true);
        }
        @chown($directory,"manticore");
        @chmod($directory,0755);
        @chgrp($directory,"manticore");
    }

    $MantiCoreMySQLVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MantiCoreMySQLVersion");
    if($MantiCoreMySQLVersion==null){
        $MantiCoreMySQLVersion="5.0.37";
    }
    $MantiCoreNetWorkers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MantiCoreNetWorkers"));
    if($MantiCoreNetWorkers==0){$MantiCoreNetWorkers=1;}

    $f[]="searchd {";
    //$f[]="    listen = 127.0.0.1:9312";
    //$f[]="    listen = 127.0.0.1:9306:mysql";

    $MantiCoreListenPorts=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MantiCoreListenPorts"));
    if(is_array($MantiCoreListenPorts)){
        foreach ($MantiCoreListenPorts as $index=>$ligne){
            $interface=$ligne["INTERFACE"];
            if($interface==null){continue;}
            $Ipaddr=$unix->InterfaceToIPv4($interface);
            if($Ipaddr==null){continue;}
            $prefix=$ligne["MODE"];
            $port=$ligne["PORT"];
            $f[]="    listen = $Ipaddr:$port:$prefix";
        }
    }


    $f[]="\tmysql_version_string = $MantiCoreMySQLVersion";
    $f[]="\tlisten = /var/run/mysqld/mysqld.sock:mysql";
    $f[]="\tlog = /var/log/manticore/searchd.log";
    $f[]="\tquery_log = /var/log/manticore/query.log";
    $f[]="\tpid_file = /var/run/manticore/searchd.pid";
    $f[]="\tdata_dir = /home/manticore/database";
    $f[]="\tnet_workers = $MantiCoreNetWorkers";
    $f[]="\trt_flush_period = 30";
    $f[]="\tmax_packet_size = 128M";
    $f[]="\tsubtree_docs_cache = 512M";
    $f[]="\tsubtree_hits_cache = 512M";
    $f[]="\tqcache_max_bytes = 0";
    $f[]="}";



    $f[]="";
    @file_put_contents("/etc/manticoresearch/manticore.conf",@implode("\n",$f));
    _out("manticore.conf done..");
    return true;



}


function SERVICE_START($nopid=false):bool{
	$unix=new unix();
	$prc=50;
	
	if(!$nopid){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			_out("Already executed PID: $pid since {$time}Mn");
			if($time<5){if(!$GLOBALS["FORCE"]){return true;}}
			unix_system_kill_force($pid);
		}
		@file_put_contents($pidfile, getmypid());
	}


    build_progress($prc++, "{starting_service}");

	$PID_NUM=PID_NUM();
	if($unix->process_exists($PID_NUM)){
		$timemin=$unix->PROCCESS_TIME_MIN($PID_NUM);
		_out("MantiCore service already running PID \"$PID_NUM\" since {$timemin}Mn");
		return true;
	}

    build_progress($prc++, "{starting_service}");
    build_conf();
    build_progress($prc++, "{starting_service}");
    $cmd="searchd --config /etc/manticoresearch/manticore.conf";
    $unix->go_exec($cmd);

   for($i=0;$i<6;$i++){
   		$pid=PID_NUM();
   		if($unix->process_exists($pid)){
               break;
   		}
   		build_progress($prc++, "{starting_service}....($i/6)");
   		_out("Starting please wait ($i/6)");
   		sleep(1);
   }

   $pid=PID_NUM();
   if(!$unix->process_exists($pid)){
       _out("Starting failed [$cmd]");
       	return false;
   }

   	_out("Starting success PID $pid");
    build_progress(90, "{starting_service}....{success}}");
    return true;

}




function install_monit():bool{
    $tfile="/etc/monit/conf.d/APP_MANTICORE.monitrc";
	$unix=new unix();
    $me=$unix->LOCATE_PHP5_BIN()." ".__FILE__;
    $md5=null;
    if(is_file($tfile)){
        $md5=md5_file($tfile);
    }

	$f[]="check process APP_MANTICORE with pidfile /var/run/manticore/searchd.pid";
	$f[]="\tstart program = \"$me --start --monit\"";
	$f[]="\tstop program = \"$me --stop --monit\"";
    $f[]="\trestart program = \"$me --restart --monit\"";
    $f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_MANTICORE.monitrc", @implode("\n", $f));

    $md2=md5_file($tfile);
    if($md5==$md2){
        return true;
    }

	$unix->MONIT_RELOAD();
	_out("$tfile updated success");
    return true;
}

function build_progress_install($text,$pourc):bool{
	$unix=new unix();
    return $unix->framework_progress($pourc,$text,"manticore.install.progress");

}

function uninstall():bool{
	$unix=new unix();
	build_progress_install(10, "{APP_MANTICORE} {uninstalling}");

	$php=$unix->LOCATE_PHP5_BIN();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("MantiCoreSearchEnabled", 0);

	build_progress_install(20, "{APP_MANTICORE} {uninstalling}");
	$unix->remove_service("/etc/init.d/manticore-search");
	if(is_file("/etc/monit/conf.d/APP_MANTICORE.monitrc")){
		@unlink("/etc/monit/conf.d/APP_MANTICORE.monitrc");
		$unix->MONIT_RELOAD();
	}
	
	build_progress_install(30, "{APP_MANTICORE} {uninstalling}");
	system("/etc/init.d/artica-status restart --force");

	build_progress_install(100, "{APP_MANTICORE} {uninstalling} {done}");
    return true;
	
}

function install():bool{
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_install(10, "{APP_MANTICORE} {installing}");


	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MantiCoreSearchEnabled", 1);
	build_progress_install(20, "{APP_MANTICORE} {installing}");

	create_service();
	build_progress_install(30, "{APP_MANTICORE} {installing}");
	install_monit();
	build_progress_install(80, "{APP_MANTICORE} {installing}");
	system("/etc/init.d/artica-status restart --force");
    build_progress_install(100, "{APP_MANTICORE} {installing} {done}");
	return true;
}

function create_service():bool{
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/manticore-search";
	$php5script=basename(__FILE__);
	$daemonbinLog="ManticoreSearch";


	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         manticore-search";
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

    return true;
}


?>