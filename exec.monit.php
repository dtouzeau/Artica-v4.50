<?php
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Daemon Monitor";
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
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.monit.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');


if(system_is_overloaded(basename(__FILE__))){echo "{OVERLOADED_SYSTEM}, EXIT";exit();}

$GLOBALS["MONIT_CLASS"]=new monit_unix();

$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();exit();}
if($argv[1]=="--status"){exit();}
if($argv[1]=="--monitor-wait"){monitor_wait();exit();}
if($argv[1]=="--install"){install_service(true);exit();}
if($argv[1]=="--syslog"){exit();}






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
	build_progress_restart("{stopping_service}",15);
	if(!stop()){return;}
	build_progress_restart("{reconfiguring}",21);
	build();
	sleep(1);
	build_progress_restart("{starting_service}",46);
	start(true);
	
}

function monitor_wait(){
	sleep(5);
	shell_exec("{$GLOBALS["MONIT_CLASS"]->monitor_all_cmdline} >/dev/null 2>&1");
	
}

function reload(){
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Reloading...\n";
    $unix=new unix();
    $monitbin=$unix->find_program("monit");
    $pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();
    if($unix->process_exists($pid)) {
        $time=$unix->PROCCESS_TIME_MIN($pid);
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Reloading PID $pid running since {$time}mn\n";
        shell_exec("$monitbin -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
        return;
    }

    start();

}


function start($aspid=false){
	if(is_file("/etc/artica-postfix/FROM_ISO")){if(!is_file("/etc/artica-postfix/artica-iso-setup-launched")){return;}}
	$unix=new unix();
	$Masterbin=$unix->find_program("monit");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, arpd not installed\n";}
		build_progress_restart("{starting_service} {failed}",110);
		return false;
	}

	if(!is_dir("/var/run/monit")){ @mkdir("/var/run/monit",0755,true);}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";
			build_progress_restart("{starting_service} {failed}",110);
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=$GLOBALS["MONIT_CLASS"]->PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		build_progress_restart("{starting_service} {success}",100);
		return true;
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableMonit")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableMonit", 1);}
	
	$EnableMonit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMonit"));



	if($EnableMonit==0){
	    $unix->ToSyslog("Starting Monit failed due to EnableMonit = 0", true,"monit");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableMonit)\n";}
		build_progress_restart("{starting_service} {failed}",110);
		return false;
	}
    build();
    system("/usr/sbin/artica-phpfpm-service -start-monit");
    build_progress_restart("{starting_service} {success}",100);
    return true;

}

function stop(){
    system("/usr/sbin/artica-phpfpm-service -stop-monit");
	build_progress_restart("{stopping_service} {success}",20);
	return true;

}
function build_progress_restart($text,$pourc):bool{
	$unix=new unix();
    return $unix->framework_progress($pourc,$text,"exec.monit.progress");
}





function build(){
	$sock=new sockets();
	$unix=new unix();

	build_progress_restart("{reconfiguring}",27);
	$f=array();
    build_progress_restart("{reconfiguring}",29);
// ********************************************************************************************************************
	$f=array();
	build_progress_restart("{reconfiguring} Proftpd",31);
	@unlink("/etc/monit/conf.d/APP_PROFTPD.monitrc");
	$proftpd=$unix->find_program("proftpd");
	
	if(is_file($proftpd)){
		$enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProFTPD"));
		if($enabled==1){
			$f[]="check process APP_PROFTPD with pidfile /var/run/proftpd.pid";
			$f[]="\tstart program = \"/etc/init.d/proftpd start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/proftpd stop --monit\"";

			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring PROFTPD...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_PROFTPD.monitrc", @implode("\n", $f));
		}
	}
	// *****************************************************************************************************



	$f=array();
	build_progress_restart("{reconfiguring} Bandwidthd",31);
	@unlink("/etc/monit/conf.d/APP_BANDWIDTHD.monitrc");
	
		if(is_file("/usr/bandwidthd/bandwidthd")){
		$enabled=$sock->Bandwidthd_enabled();
		if($enabled==1){
			$f[]="check process APP_BANDWIDTHD with pidfile /var/run/bandwidthd.pid";
			$f[]="\tstart program = \"/etc/init.d/bandwidthd start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/bandwidthd stop --monit\"";

			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring DnsMASQ...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_BANDWIDTHD.monitrc", @implode("\n", $f));
		}
		
	}
	//
	

	$f=array();
	build_progress_restart("{reconfiguring} Suricata",32);
	$EnableSuricata=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSuricata"));
	@unlink("/etc/monit/conf.d/APP_SURICATA.monitrc");
	@unlink("/etc/monit/conf.d/APP_SURICATA_TAIL.monitrc");
	$suricata=$unix->find_program("suricata");
	if(is_file($suricata)){
		if($EnableSuricata==1){
			$f[]="check process APP_SURICATA with pidfile /var/run/suricata/suricata.pid";
			$f[]="\tstart program = \"/etc/init.d/suricata start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/suricata stop --monit\"";
			$f[]="\tif cpu usage > 95% for 5 cycles then restart";

			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Suricata...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_SURICATA.monitrc", @implode("\n", $f));
			


			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Suricata tail...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_SURICATA_TAIL.monitrc", @implode("\n", $f));
		}
	}
// ********************************************************************************************************************	
	$f=array();
	build_progress_restart("{reconfiguring}",32);

// ********************************************************************************************************************
	$f=array();

// ********************************************************************************************************************	
	build_progress_restart("{reconfiguring}",34);
	@unlink("/etc/monit/conf.d/APP_SYSLOGDB.monitrc");
//********************************************************************************************************************
	$f=array();
	@unlink("/etc/monit/conf.d/cron.monitrc");
	if(is_file("/etc/monit/templates/rootbin")){
		$f[]="check process crond with pidfile /var/run/crond.pid";
		$f[]="   group system";
		$f[]="   group crond";
		$f[]="   start program = \"/etc/init.d/cron start\"";
		$f[]="   stop  program = \"/etc/init.d/cron stop\"";
		$f[]="   if 5 restarts with 5 cycles then timeout";
		$f[]="   depend cron_bin";
		$f[]="   depend cron_rc";
		$f[]="   depend cron_spool";
		$f[]="";
		$f[]=" check file cron_bin with path /usr/sbin/cron";
		$f[]="   group crond";
		$f[]="   include /etc/monit/templates/rootbin";
		$f[]="";
		$f[]=" check file cron_rc with path \"/etc/init.d/cron\"";
		$f[]="   group crond";
		$f[]="   include /etc/monit/templates/rootbin";
		$f[]="";
		$f[]=" check directory cron_spool with path /var/spool/cron/crontabs";
		$f[]="   group crond";
		$f[]="   if failed permission 1730 then unmonitor";
		$f[]="   if failed uid root        then unmonitor";
		$f[]="   if failed gid crontab     then unmonitor";	
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring cron...\n";}
		@file_put_contents("/etc/monit/conf.d/cron.monitrc", @implode("\n", $f));
		$f=array();
	}
	
	@unlink("/etc/monit/conf.d/APP_ZARAFASERVER.monitrc");
	@unlink("/etc/monit/conf.d/APP_ZARAFAGATEWAY.monitrc");
	@unlink("/etc/monit/conf.d/APP_ZARAFAAPACHE.monitrc");
	@unlink("/etc/monit/conf.d/APP_ZARAFAWEB.monitrc");
	@unlink("/etc/monit/conf.d/APP_ZARAFASPOOLER.monitrc");	
	@unlink("/etc/monit/conf.d/APP_ZARAFADB.monitrc");
	build_progress_restart("{reconfiguring}",35);
	



//********************************************************************************************************************
	build_progress_restart("{reconfiguring}",36);
	$EnableClamavDaemon=$sock->GET_INFO("EnableClamavDaemon");
	$EnableClamavDaemonForced=$sock->GET_INFO("EnableClamavDaemonForced");
	$CicapEnabled=$sock->GET_INFO("CicapEnabled");
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	
	if(!is_numeric($EnableClamavDaemon)){$EnableClamavDaemon=0;}
	if(!is_numeric($EnableClamavDaemonForced)){$EnableClamavDaemonForced=0;}
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
	if($SQUIDEnable==1){if($CicapEnabled==1){$EnableClamavDaemon=1;}}
	if($EnableClamavDaemonForced==1){$EnableClamavDaemon=1;}
//********************************************************************************************************************

	
	
	
	@unlink("/etc/monit/conf.d/ufdb.monitrc");
	@unlink("/etc/monit/conf.d/ufdbweb.monitrc");
	$ufdbbin=$unix->find_program("ufdbguardd");
	build_progress_restart("{reconfiguring}",38);
	if(is_file($ufdbbin)){
		$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
		
		$UseRemoteUfdbguardService=$sock->GET_INFO('UseRemoteUfdbguardService');
		$EnableSquidGuardHTTPService=$sock->GET_INFO("EnableSquidGuardHTTPService");
		$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
		$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
		$SquidGuardApachePort=$sock->GET_INFO("SquidGuardApachePort");
		$SquidGuardApacheSSLPort=$sock->GET_INFO("SquidGuardApacheSSLPort");
		if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
		if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
		if(!is_numeric($EnableSquidGuardHTTPService)){$EnableSquidGuardHTTPService=1;}
		if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
		if($EnableUfdbGuard==0){$EnableSquidGuardHTTPService=0;}
		if($EnableWebProxyStatsAppliance==1){$EnableSquidGuardHTTPService=1;}
		if(!is_numeric($SquidGuardApachePort)){$SquidGuardApachePort="9020";}
		if(!is_numeric($SquidGuardApacheSSLPort)){$SquidGuardApacheSSLPort=9025;}
		if($SquidPerformance>2){$EnableSquidGuardHTTPService=0;}
		
		if($SQUIDEnable==1){	
			
			if($EnableSquidGuardHTTPService==1){
				$f=array();
				if(is_file("/etc/init.d/ufdb-http")){
					$SquidGuardApachePort=intval($GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidGuardApachePort"));
					if($SquidGuardApachePort == 0){ $SquidGuardApachePort=9020;}
					
					$f[]="check process APP_UFDB_HTTP";
					$f[]="with pidfile /var/run/webfilter-http.pid";
					$f[]="start program = \"/etc/init.d/ufdb-http start --monit\"";
					$f[]="stop program =  \"/etc/init.d/ufdb-http stop --monit\"";
					$f[]="if failed host 127.0.0.1 port $SquidGuardApachePort with timeout 15 seconds then restart";
					$f[]="if 5 restarts within 5 cycles then timeout";
					$f[]="";
					if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Web filtering HTTP service...\n";}
					@file_put_contents("/etc/monit/conf.d/ufdbweb.monitrc", @implode("\n", $f));
				}
				
			}
			
			
		}
	}
	
//********************************************************************************************************************	

	@unlink("/etc/monit/conf.d/APP_LIGHTTPD.monitrc");
	if(!is_file("/usr/local/ArticaWebConsole/sbin/artica-webconsole")){
		$EnableArticaFrontEndToNGninx=$sock->GET_INFO("EnableArticaFrontEndToNGninx");
		$EnableArticaFrontEndToApache=$sock->GET_INFO("EnableArticaFrontEndToApache");
		if(!is_numeric($EnableArticaFrontEndToNGninx)){$EnableArticaFrontEndToNGninx=0;}
		if(!is_numeric($EnableArticaFrontEndToApache)){$EnableArticaFrontEndToApache=0;}
		$EnableNginx=$sock->GET_INFO("EnableNginx");
		$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
		if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
		if(!is_numeric($EnableNginx)){$EnableNginx=1;}
		if($EnableNginx==0){$EnableArticaFrontEndToNGninx=0;}
		$pid=null;
		build_progress_restart("{reconfiguring}",39);
		
		if($EnableArticaFrontEndToNGninx==0){			
			$pid="/var/run/lighttpd/lighttpd.pid";
			if($EnableArticaFrontEndToApache==1){$pid="/var/run/artica-apache/apache.pid";}
			$f=array();
			$f[]="check process APP_ARTICAWEBCONSOLE with pidfile $pid";
			$f[]="\tstart program = \"/etc/init.d/artica-webconsole start --monit\"";
			$f[]="\tstop program = \"/etc/init.d/artica-webconsole stop --monit\"";

			$f[]="";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Artica Web Console...\n";}
			@file_put_contents("/etc/monit/conf.d/APP_LIGHTTPD.monitrc", @implode("\n", $f));
		}
		//********************************************************************************************************************	
	}	

		
	
	build_progress_restart("{reconfiguring}",40);
	$f=array();
	if(is_file("/etc/init.d/sysklogd")){
		$f[]="check process APP_SYSLOGD with pidfile /var/run/syslogd.pid";
		$f[]="\tstart program = \"/etc/init.d/sysklogd start --monit\"";
		$f[]="\tstop program = \"/etc/init.d/sysklogd stop --monit\"";

		$f[]="\tcheck file syslogd_file with path /var/log/syslog";
        $f[]="\tif not exist then alert";
		$f[]="\tif timestamp > 10 minutes then restart";
		$f[]="";
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring sysklogd...\n";}
		@file_put_contents("/etc/monit/conf.d/APP_SYSKLOGD.monitrc", @implode("\n", $f));
	}
	
//********************************************************************************************************
build_progress_restart("{reconfiguring}",43);
    if(is_file("/etc/monit/conf.d/APP_DNSMASQ.monitrc")) {
        @unlink("/etc/monit/conf.d/APP_DNSMASQ.monitrc");
    }

    if(is_file("/etc/monit/conf.d/APP_WIFIDOG.monitrc")) {
        @unlink("/etc/monit/conf.d/APP_WIFIDOG.monitrc");
    }
$f=array();
//********************************************************************************************************************
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} checking syslog\n";}
	if(is_file("/etc/init.d/syslog")){checkDebSyslog();}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} configuration done\n";}
	shell_exec($GLOBALS["MONIT_CLASS"]->monitor_all_cmdline." 2>&1");
	build_progress_restart("{reconfiguring}",45);
}
function checkDebSyslog(){
	if(!is_file("/etc/rsyslog.conf")){return;}
	$f=file("/etc/init.d/syslog");
	$RSYSLOGD_PIDFILE=null;
	foreach ($f as $index=>$line){
		if(preg_match("#RSYSLOGD_PIDFILE=(.+)#", $line,$re)){
			$RSYSLOGD_PIDFILE=$re[1];
			break;
		}
	}

	$filesize=filesize("/etc/init.d/syslog");
	if($filesize<50){$RSYSLOGD_PIDFILE="/var/run/rsyslogd.pid";}
	if($RSYSLOGD_PIDFILE==null){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} pidfile `cannot check pid...`\n";return;}}

if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} rsyslog pidfile `$RSYSLOGD_PIDFILE`\n";}

	$f=file("/etc/rsyslog.conf");
	foreach ($f as $index=>$line){
		if(preg_match("#\*\.\*.*?\s+(.+)#", $line,$re)){
			$syslogpath=$re[1];
			if(substr($syslogpath, 0,1)=='-'){$syslogpath=substr($syslogpath, 1,strlen($syslogpath));}
			break;
		}
		 
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} syslog path `$syslogpath`\n";}
	if(!is_file($syslogpath)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} syslog path `$syslogpath` no such file!\n";return;}}

	$f=array();
	$f[]="check process APP_SYSLOGD with pidfile $RSYSLOGD_PIDFILE";
	$f[]="start program = \"/etc/init.d/syslog start --monit\"";
	$f[]="stop program = \"/etc/init.d/syslog stop --monit\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	@chmod("/etc/init.d/syslog",0755);
	@file_put_contents("/etc/monit/conf.d/APP_RSYSLOGD.monitrc", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/monit/conf.d/APP_RSYSLOGD.monitrc done\n";}
}



function install_service(){
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		$monit=$unix->find_program("monit");
	
		if(!is_file($monit)){
            echo "Unable to find monit binary\n";
            return false;}
	
		$f[]="#!/bin/sh";
		$f[]="### BEGIN INIT INFO";
		$f[]="# Provides:          artica-monit";
		$f[]="# Required-Start:    \$local_fs \$syslog";
		$f[]="# Required-Stop:     \$local_fs \$syslog";
		$f[]="# Should-Start:";
		$f[]="# Should-Stop:";
		$f[]="# Default-Start:     3 4 5";
		$f[]="# Default-Stop:      0 1 6";
		$f[]="# Short-Description: Monitor daemon";
		$f[]="# chkconfig: 2345 11 89";
		$f[]="# description: Extensible, configurable Monitor daemon";
		$f[]="### END INIT INFO";
		$f[]="case \"\$1\" in";
		$f[]=" start)";
		$f[]="   $php ".__FILE__." --start \$2 \$3";
	
		$f[]="	 exit 0";
		$f[]="    ;;";
		$f[]="";
		$f[]="  stop)";
		$f[]="   $php ".__FILE__." --stop \$2 \$3";
		$f[]="    ;;";
		$f[]="";
		$f[]=" restart)";
	
		$f[]="   $php ".__FILE__." --restart \$2 \$3";
		$f[]="	 exit 0";
		$f[]="    ;;";
		$f[]="";
		$f[]=" reload)";
		$f[]="   $php ".__FILE__." --reload \$2 \$3";
		$f[]="	 exit 0";
		$f[]="    ;;";
		$f[]="";
		$f[]="  *)";
		$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
		$f[]="    exit 1";
		$f[]="    ;;";
		$f[]="esac";
		$f[]="exit 0\n";
	
		$INITD_PATH="/etc/init.d/monit";
		echo "artica-monit: [INFO] Writing $INITD_PATH with new config\n";
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
?>