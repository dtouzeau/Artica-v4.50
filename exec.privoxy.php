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
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.groups.inc');

// Usage: /etc/init.d/clamav-daemon {start|stop|restart|force-reload|reload-log|reload-database|status}

$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--reload-log"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--force-reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--reconfigure-squid"){$GLOBALS["OUTPUT"]=true;InSquid(true);exit();}
if($argv[1]=="--template"){$GLOBALS["OUTPUT"]=true;template(true);exit();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install(true);exit();}
if($argv[1]=="--remove"){$GLOBALS["OUTPUT"]=true;remove(true);exit();}
if($argv[1]=="--update"){$GLOBALS["OUTPUT"]=true;easyListDownloads(true);exit();}




function install(){
	build_progress_restart("{install_service}",10);
	install_service();
	install_monit();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PrivoxyEnabled", 1);
	restart();
}
function remove(){
	$unix=new unix();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PrivoxyEnabled", 0);
	build_progress_restart("{remove_service}",10);
	@unlink("/etc/squid3/privoxy.conf");
	@touch("/etc/squid3/privoxy.conf");
	$squidbin=$unix->LOCATE_SQUID_BIN();
	build_progress_restart("{remove_service}",50);
	squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
    system("/usr/sbin/artica-phpfpm-service -reload-proxy");
	remove_service("/etc/init.d/privoxy");
	build_progress_restart("{remove_service}",60);
	@unlink("/etc/monit/conf.d/APP_PRIVOXY.monitrc");
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	@unlink("/etc/cron.d/privoxy");
	build_progress_restart("{remove_service}",70);
	system("/etc/init.d/cron reload");
	build_progress_restart("{remove_service} {done}",100);
}

function install_monit(){
    $unix=new unix();
	@unlink("/etc/monit/conf.d/APP_PRIVOXY.monitrc");
	$f[]="check process APP_PRIVOXY with pidfile /var/run/privoxy.pid";
	$f[]="\tstart program = \"/etc/init.d/privoxy start\"";
	$f[]="\tstop program = \"/etc/init.d/privoxy stop\"";

	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_PRIVOXY.monitrc", @implode("\n", $f));
	$unix->MONIT_RELOAD();

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
function install_service(){
	if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/privoxy";
	$php5script="exec.privoxy.php";
	$daemonbinLog="AdsBlocker daemon";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:        privoxy";
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

	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: $INITD_PATH {start|stop|restart|force-reload|reload-log|reload-database|status} (+ '--verbose' for more infos)\"";
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

function restart() {
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		build_progress_restart("{failed}",110);
		return;
	}
	
	
	
	@file_put_contents($pidfile, getmypid());
	$PrivoxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyEnabled"));
	
	build_progress_restart("{stopping_service}",15);
	if(!stop(true)){
		build_progress_restart("{failed}",110);
		return;
	}
	
	if($PrivoxyEnabled==0){
		$size=@filesize("/etc/squid3/privoxy.conf");
		if($size>1){
			echo "Remove link with main proxy...\n";
			build_progress_restart("{reconfiguring}",20);
			@unlink("/etc/squid3/privoxy.conf");
			@touch("/etc/squid3/privoxy.conf");
			$squidbin=$unix->LOCATE_SQUID_BIN();
			squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
            system("/usr/sbin/artica-phpfpm-service -reload-proxy");
		}
		
		build_progress_restart("{disabled}",90);
		sleep(2);
		build_progress_restart("{success}",100);
		return;
	}
	
	
	build_progress_restart("{reconfiguring}",30);
	if(!build()){
		return;
	}
	sleep(1);
	build_progress_restart("{starting_service}",80);
	if(!start(true)){
		build_progress_restart("{failed}",110);
		return;
	}
	
	build_progress_restart("{success}",100);
	

}
function build_progress_restart($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_squidr($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.squid.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_template($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.template.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}


function reload($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("privoxy");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, clamd not installed\n";}
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
	
	
	$PrivoxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyEnabled"));
	if($PrivoxyEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see ClamavMilterEnabled)\n";}
		return false;
	}
	
	
	$pid=PID_NUM();
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service reloading PID $pid running since {$timepid}Mn...\n";}
		unix_system_HUP($pid);
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not running\n";}

}



function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("privoxy");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, service not installed\n";}
		return;
	}

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
		return true;
	}
	
	$PrivoxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyEnabled"));
	
	
	

	if($PrivoxyEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see PrivoxyEnabled)\n";}
		return false;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	build_progress_restart("{starting_service}",31);
	$aa_complain=$unix->find_program('aa-complain');
	if(is_file($aa_complain)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} add clamd Profile to AppArmor..\n";}
		shell_exec("$aa_complain $Masterbin >/dev/null 2>&1");
	}
	
	@unlink("/var/log/privoxy/start.log");
	$privoxy_version=privoxy_version();
	$cmd="$nohup $Masterbin --pidfile /var/run/privoxy.pid --user squid /etc/privoxy/privoxy.conf >/var/log/privoxy/start.log 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service version $privoxy_version\n";}
	shell_exec($cmd);
	
	for($i=1;$i<5;$i++){
		build_progress_restart("{starting_service}",35);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

		
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed..\n";}
		build_progress_restart("{starting_service} {failed}",40);
		echo " ******\n$cmd\n ******\n";
		return;
	}
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
    system("/usr/sbin/artica-phpfpm-service -reload-proxy");
	return true;


}

function privoxy_version($bin){
	$unix=new unix();
	if(isset($GLOBALS["privoxy_version"])){return $GLOBALS["privoxy_version"];}
	$bin=$unix->find_program("privoxy");
	exec("$bin --version 2>&1",$results);
	foreach ($results as $num=>$line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^Privoxy version\s+([0-9a-z\.]+)#",$line,$re)){continue;}
		$GLOBALS["privoxy_version"]=$re[1];
	}

	return $GLOBALS["privoxy_version"];

}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/privoxy.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("privoxy");
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
		return true;
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

function build(){
	$sock=new sockets();
	$unix=new unix();
	$ini=new Bs_IniHandler();
	$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
	$ini->loadString($ArticaSquidParameters);
	$PrivoxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyPort"));
	if($PrivoxyPort==0){
		$PrivoxyPort=rand(15000,5000);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PrivoxyPort", $PrivoxyPort);
	}
	$visible_hostname=$ini->_params["NETWORK"]["visible_hostname"];
	$visible_hostname=str_replace("..", ".", $visible_hostname);
	if($visible_hostname==null){$visible_hostname=$unix->hostname_g();}
	$php=$unix->LOCATE_PHP5_BIN();
	
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} listen 127.0.0.1:$PrivoxyPort\n";}
	
	@mkdir("/etc/privoxy",0755,true);
	@mkdir("/var/log/privoxy",0755,true);
	@mkdir("/home/privoxy",0755,true);
	
	@chown("/var/log/privoxy", "squid");
	@chgrp("/var/log/privoxy", "squid");
	@chgrp("/etc/privoxy", "squid");
	
	@chown("/home/privoxy", "squid");
	@chgrp("/home/privoxy", "squid");
	@chgrp("/etc/privoxy", "squid");
	
	$f[]="user-manual /usr/local/share/doc/privoxy/user-manual/";
	$f[]="#trust-info-url  http://www.example.com/why_we_block.html";
	$f[]="#trust-info-url  http://www.example.com/what_we_allow.html";
	$f[]="#admin-address privoxy-admin@example.com";
	$f[]="#proxy-info-url http://www.example.com/proxy-service.html";
	$f[]="confdir /etc/privoxy";
	$f[]="templdir /home/privoxy";
	$f[]="#temporary-directory .";
	$f[]="logdir /var/log/privoxy";
	$f[]="actionsfile match-all.action";
	$f[]="actionsfile default.action";
	
	Artica_pattern();
	
	$actionsfile[]="malwaredomains_full.script.action";
	$actionsfile[]="fanboy-social.script.action";
	$actionsfile[]="easyprivacy.script.action";
	$actionsfile[]="easylist.script.action";
	$actionsfile[]="easylistdutch.script.action";
	$actionsfile[]="easylistdutch+easylist.script.action";
	$actionsfile[]="liste_fr.script.action";
	$actionsfile[]="easylistchina.script.action";
	$actionsfile[]="easylistitaly.script.action";
	$actionsfile[]="artica.action";
	$actionsfile[]="ab2p.action";
	$actionsfile[]="ab2p.system.action";
	
	
	$PrivoxyUpdates=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyUpdates"));
	if($PrivoxyUpdates==0){$PrivoxyUpdates=3;}
	
	$unix->Popuplate_cron_make("privoxy", "45 0 */{$PrivoxyUpdates} * *",basename(__FILE__)." --update");
	system("/etc/init.d/cron reload");
	
	
	      
	
	$filterfile[]="malwaredomains_full.script.filter";
	$filterfile[]="fanboy-social.script.filter";
	$filterfile[]="easyprivacy.script.filter";
	$filterfile[]="easylist.script.filter";
	$filterfile[]="easylistdutch.script.filter";
	$filterfile[]="easylistdutch+easylist.script.filter";
	$filterfile[]="liste_fr.script.filter";
	$filterfile[]="easylistchina.script.filter";
	$filterfile[]="easylistitaly.script.filter";
	
	$filterfile[]="ab2p.filter";
	$filterfile[]="ab2p.system.filter";
	
	
	if(!is_file("/usr/share/x86_64-linux-ghc-7.10.3/adblock2privoxy-1.4.2/templates/ab2p.system.action")){
		@mkdir("/usr/share/x86_64-linux-ghc-7.10.3/adblock2privoxy-1.4.2/templates",0755,true);
		@copy("/usr/share/artica-postfix/bin/install/ab2p.system.action", "/usr/share/x86_64-linux-ghc-7.10.3/adblock2privoxy-1.4.2/templates/ab2p.system.action");
		@copy("/usr/share/artica-postfix/bin/install/ab2p.system.filter", "/usr/share/x86_64-linux-ghc-7.10.3/adblock2privoxy-1.4.2/templates/ab2p.system.filter");
	}
	
	
	
	while (list ($num, $filename) = each ($actionsfile)){
		if(!is_file("/etc/privoxy/$filename")){continue;}
		
		@chown("/etc/privoxy/$filename", "squid");
		@chgrp("/etc/privoxy/$filename", "squid");
		
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} add $filename\n";}
		$f[]="actionsfile $filename";
	}
	
	
	$f[]="actionsfile user.action";
	$f[]="filterfile default.filter";
	
	while (list ($num, $filename) = each ($filterfile)){
		
		@chown("/etc/privoxy/$filename", "squid");
		@chgrp("/etc/privoxy/$filename", "squid");
		
		if(!is_file("/etc/privoxy/$filename")){continue;}
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} add $filename\n";}
		$f[]="filterfile $filename";
	}
	
	
	$f[]="filterfile user.filter";
	
	
	
	$f[]="logfile privoxy.log";
	$f[]="#trustfile trust";
	$f[]="#debug     1 # Log the destination for each request Privoxy let through. See also debug 1024.";
	$f[]="#debug  1024 # Actions that are applied to all sites and maybe overruled later on.";
	$f[]="#debug  4096 # Startup banner and warnings";
	$f[]="#debug  8192 # Non-fatal errors";
	$f[]="debug 1024";
	$f[]="single-threaded 0";
	$f[]="hostname $visible_hostname";
	$f[]="listen-address  127.0.0.1:$PrivoxyPort";
	$f[]="toggle  1";
	$f[]="enable-remote-toggle  1";
	$f[]="enable-remote-http-toggle  1";
	$f[]="enable-edit-actions 1";
	$f[]="enforce-blocks 1";
	$f[]="buffer-limit 4096";
	$f[]="enable-proxy-authentication-forwarding 1";
	$f[]="forwarded-connect-retries  0";
	$f[]="accept-intercepted-requests 1";
	$f[]="allow-cgi-request-crunching 0";
	$f[]="split-large-forms 0";
	$f[]="keep-alive-timeout 300";
	$f[]="tolerate-pipelining 1";
	$f[]="#default-server-timeout 60";
	$f[]="#connection-sharing 1";
	$f[]="socket-timeout 600";
	$f[]="max-client-connections 512";
	$f[]="#handle-as-empty-doc-returns-ok 1";
	$f[]="#enable-compression 1";
	$f[]="#compression-level 9";
	$f[]="#activity-animation   1";
	$f[]="#log-messages   1";
	$f[]="#log-buffer-size 1";
	$f[]="#log-max-lines 200";
	$f[]="#log-highlight-messages 1";
	$f[]="#log-font-name Comic Sans MS";
	$f[]="#log-font-size 8";
	$f[]="#show-on-task-bar 0";
	$f[]="#close-button-minimizes 1";
	$f[]="#hide-console";
	$f[]="";
		
	
	
	if(!is_file("/usr/share/artica-postfix/bin/install/squid/privoxy.default.filter")){
		echo "Missing default.filter file ( source )\n";
		build_progress_restart("{reconfiguring} {failed}",110);
		return false;
	}
	if(!is_file("/usr/share/artica-postfix/bin/install/squid/privoxy.default.action")){
		echo "Missing default.action file ( source )\n";
		build_progress_restart("{reconfiguring} {failed}",110);
		return false;
	}
	if(!is_file("/usr/share/artica-postfix/bin/install/squid/privoxy.user.action")){
		echo "Missing user.action file ( source )\n";
		build_progress_restart("{reconfiguring} {failed}",110);
		return false;
	}	
	
	if(!is_file("/etc/privoxy/default.filter")){
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} installing /etc/privoxy/default.filter\n";}
		@copy("/usr/share/artica-postfix/bin/install/squid/privoxy.default.filter","/etc/privoxy/default.filter");
	}
	if(!is_file("/etc/privoxy/default.action")){
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} installing /etc/privoxy/default.action\n";}
		@copy("/usr/share/artica-postfix/bin/install/squid/privoxy.default.action","/etc/privoxy/default.action");
	}
	
	if(!is_file("/etc/privoxy/user.action")){
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} installing /etc/privoxy/user.action\n";}
		
		if(!is_file("/usr/share/artica-postfix/bin/install/squid/privoxy.user.action")){
			if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} fatal privoxy.user.action no such file!!!!\n";}
		}
		@copy("/usr/share/artica-postfix/bin/install/squid/privoxy.user.action","/etc/privoxy/user.action");
	}
	
	if(!is_file("/etc/privoxy/default.filter")){
		echo "Missing /etc/privoxy/default.filter file\n";
		echo "Please Restart....\n";
		build_progress_restart("{reconfiguring} {failed}",110);
		return false;
	}
	
	
	@chmod("/usr/share/artica-postfix/bin/privoxy-blocklist.sh", 0755);
	@chown("/etc/privoxy/default.filter", "squid");
	@chgrp("/etc/privoxy/default.filter", "squid");
	
	@chown("/etc/privoxy/default.action", "squid");
	@chgrp("/etc/privoxy/default.action", "squid");
	
	@chown("/etc/privoxy/user.action", "squid");
	@chgrp("/etc/privoxy/user.action", "squid");
	
	
	if(!is_file("/etc/privoxy/user.filter")){
		@touch("/etc/privoxy/user.filter");
		@chown("/etc/privoxy/user.filter", "squid");
		@chgrp("/etc/privoxy/user.filter", "squid");
	}
	
	

	
$actions="\n{ \
+change-x-forwarded-for{block} \
+client-header-tagger{css-requests} \
+client-header-tagger{image-requests} \
+hide-from-header{block} \
+set-image-blocker{pattern} \
}
/ # Match all URLs\n
";
	@file_put_contents("/etc/privoxy/match-all.action", $actions);
	@chown("/etc/privoxy/match-all.action", "squid");
	@chgrp("/etc/privoxy/match-all.action", "squid");
	
	@mkdir("/etc/privoxy",0755,true);
	@file_put_contents("/etc/privoxy/privoxy.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/privoxy/privoxy.conf done\n";}
	
	InSquid();
	return true;
}

function template(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	build_progress_template("{building} {error_page}",15);
	
	shell_exec("$php /usr/share/artica-postfix/exec.squid.templates.php --single ERR_ADS_BLOCK");
	$templateDestination="/usr/share/squid-langpack/templates/ERR_ADS_BLOCK";
	@unlink("/home/privoxy/blocked");
	@mkdir("/home/privoxy",0755,true);
	build_progress_template("{copy} {error_page}",50);
	@copy($templateDestination, "/home/privoxy/blocked");
	
	$content=@file_get_contents($templateDestination);
	$content_no_such_domain=str_replace("@block-reason@","The domain name <b>@host@</b> could not be resolved",$content);
	$content_connect_failed=str_replace("@block-reason@","Connection to <b>@host@</b> (@host-ip@) could not be established",$content);
	
	
	
	@file_put_contents("/home/privoxy/no-such-domain",$content_no_such_domain);
	@file_put_contents("/home/privoxy/connect-failed",$content_connect_failed);
	@copy($templateDestination, "/home/privoxy/no-server-data");
	build_progress_template("{error_page} {done}",100);
}


function InSquid($reconfigure_squid=false){
	$unix=new unix();
	$PrivoxyAllowPOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyAllowPOST"));
	$SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
	if($SquidUrgency==1){
		$InSquid="# SquidUrgency == 1";
		@file_put_contents("/etc/squid3/privoxy.conf", @implode("\n", $InSquid));
		build_progress_squidr("{reloading}",90);
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if( is_file($squidbin)){ 
			squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
            system("/usr/sbin/artica-phpfpm-service -reload-proxy");}
		build_progress_squidr("{done}",100);
		return;
	}
	
	
	$sock=new sockets();
	$ipClass=new IP();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	
	$acls=new squid_acls();
	$acls->clean_dstdomains();
	
	build_progress_squidr("{checking} {whitelist}",30);
	
	$sql="CREATE TABLE IF NOT EXISTS `privoxy_whitelist` (
				`items` VARCHAR(256) NOT NULL PRIMARY KEY
				) ENGINE=MYISAM;";
	
	
	$q->QUERY_SQL($sql);
	
	$results=$q->QUERY_SQL("SELECT * FROM privoxy_whitelist");
	
	$ACLS=array();
	$ACLS["IPS"]=array();
	$ACLS["DOMS"]=array();
	foreach($results as $index=>$ligne) {
		$items=trim(strtolower($ligne["items"]));
		if($ipClass->isIPAddressOrRange($items)){
			$ACLS["IPS"][$items]=$items;
			
		}
		$ACLS["DOMS"][$items]=$items;
		
		
	}
	
	$ipacls=array();
	$ACLS["DOMS"]["apple.com"]="apple.com";
	$ACLS["DOMS"]["windowsupdate.com"]="windowsupdate.com";
	$ACLS["DOMS"]["googleapis.com"]="googleapis.com";
	$ACLS["DOMS"]["mozilla.net"]="mozilla.net";
	$ACLS["DOMS"]["teamviewer.com"]="teamviewer.com";
	$ACLS["DOMS"]["microsoft.com"]="microsoft.com";
	$ACLS["DOMS"]["artica.fr"]="artica.fr";
	$ACLS["DOMS"]["cloudfront.net"]="cloudfront.net";
	$ACLS["DOMS"]["letsencrypt.org"]="letsencrypt.org";
	
	if(count($ACLS["IPS"])>0){
		while (list ($num, $line) = each ($ACLS["IPS"])){$ipacls[]=$line;}
	}
	
	if(count($ACLS["DOMS"])>0){
		while (list ($num, $line) = each ($ACLS["DOMS"])){$domacls[]=$line;}
	}
	
	if(count($domacls)>0){
		$domacls=$acls->clean_dstdomains($domacls);
	}
	
	
	$PrivoxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyPort"));
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	$privoxyInSquid=false;
	foreach ($f as $index=>$line){
		if(preg_match("#include.*?privoxy\.conf#", $line)){
			$privoxyInSquid=true;
			break;
		}
	}
	
	
	$InSquid[]="acl PrivoxyBrowsers browser -i Mozilla\/.*?(Firefox|Chrome|MSIE|iPhone)\/";
	$PrivoxyAllowGWL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyAllowGWL"));
	$PrivoxyAllowSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyAllowSSL"));
	
	
	if($PrivoxyAllowPOST==1){
		$InSquid[]="acl AntiAdsPost method POST";
		$nverdirect[]="never_direct allow POST";
	}
	if(count($domacls)>0){
		@file_put_contents("/etc/squid3/AntiAdsDenyWeb.acl", @implode("\n", $domacls));
		$InSquid[]="acl AntiAdsDenyWeb dstdomain \"/etc/squid3/AntiAdsDenyWeb.acl\"";
		$nverdirect[]="never_direct deny AntiAdsDenyWeb";
	}
	if(count($ipacls)>0){
		@file_put_contents("/etc/squid3/AntiAdsDenyIP.acl", @implode("\n", $ipacls));
		$InSquid[]="acl AntiAdsDenyIP dst \"/etc/squid3/AntiAdsDenyIP.acl\"";
	}
	$InSquid[]="cache_peer 127.0.0.1 parent $PrivoxyPort 0 no-query no-digest no-netdb-exchange name=PeerPrivoxy";
	$nverdirect[]="always_direct allow FTP";
	$InSquid[]="cache_peer_access PeerPrivoxy deny MgRPort";

	
	if($PrivoxyAllowSSL==1){
		$nverdirect[]="never_direct allow CONNECT";
	}else{
		$InSquid[]="cache_peer_access PeerPrivoxy deny CONNECT";
		$nverdirect[]="never_direct deny CONNECT";
	}
	
	
	if($PrivoxyAllowGWL==0){
		$InSquid[]="cache_peer_access PeerPrivoxy deny GlobalWhitelistDSTNet";
		$InSquid[]="cache_peer_access PeerPrivoxy deny GlobalWhitelistBrowsers";
		

		$nverdirect[]="never_direct deny GlobalWhitelistDSTNet";
		$nverdirect[]="never_direct deny GlobalWhitelistBrowsers";
		
	}
	if(count($ipacls)>0){$InSquid[]="cache_peer_access PeerPrivoxy deny AntiAdsDenyIP";}
	if(count($domacls)>0){$InSquid[]="cache_peer_access PeerPrivoxy deny AntiAdsDenyWeb";}
	if($PrivoxyAllowPOST==1){$InSquid[]="cache_peer_access PeerPrivoxy deny AntiAdsPost";}
	
	build_progress_squidr("{starting} {building_acls_proxy_objects}",50);
	$aclGen=new squid_acls();
	$aclGen->Build_Acls(true);
	@file_put_contents("/etc/squid3/acls_center.conf", @implode("\n",$aclGen->acls_array));
	
	
	$sql="SELECT * FROM squid_privoxy_acls WHERE enabled=1 order by zorder";
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$results=$q->QUERY_SQL($sql);
	$squid_acls_groups=new squid_acls_groups();
	$Tdeny[1]="deny";
	$Tdeny[0]="allow";
	
	foreach($results as $index=>$ligne) {
		$rulename=$ligne["rulename"];
		$aclid=$ligne["aclid"];
		$deny=$ligne["deny"];
		$InSquid[]="#------------------- $aclid] $rulename -------------------------------";
		$acls_array=$squid_acls_groups->buildacls_bytype_items($aclid,false,"privoxy_sqacllinks");
		if(count($acls_array)==0){
			$InSquid[]="#------------------- $aclid] $rulename no group...";
			$InSquid[]=@implode("\n", $squid_acls_groups->DEBUG);
			continue;
		}
		$InSquid[]="cache_peer_access PeerPrivoxy {$Tdeny[$deny]} ".@implode(" ", $acls_array);
		if($deny==0){$nverdirect[]="never_direct allow ".@implode(" ", $acls_array);}
		if($deny==1){$nverdirect[]="never_direct deny ".@implode(" ", $acls_array);}
	}
	
	
	$nverdirect[]="never_direct allow PrivoxyBrowsers";
	$InSquid[]="cache_peer_access PeerPrivoxy allow all";
	
	@file_put_contents("/etc/squid3/privoxy.conf", @implode("\n", $InSquid)."\n".@implode("\n", $nverdirect));
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/squid3/privoxy.conf done\n";}
	
	build_progress_squidr("{reconfiguring}",50);

	
	if($reconfigure_squid){
		build_progress_squidr("{reloading}",90);
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if( is_file($squidbin)){ 
			squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
            system("/usr/sbin/artica-phpfpm-service -reload-proxy");
        }
	}
	
	build_progress_squidr("{done}",100);
	
}


function Artica_pattern(){
	$EnableArticaMetaClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMetaClient"));
	
	$q=new mysql();
	$f[]="{ -block{Artica} -filter{Artica} }";
	$f[]=".articatech.net";
	$f[]=".artica.fr";
	$f[]=".privoxy.org";
	$f[]=".wdrmaus.de";
	$f[]=".die-maus.de";
	$f[]=".hirnwindungen.de";
	$f[]=".mathe-spass.de";
	$f[]=".learnetix.de";
	$f[]=".lerntux.de";
	$f[]=".wikipedia.org";
	$f[]=".wikimedia.org";
	$f[]=".fragfinn.de";
	$f[]=".geolino.de";
	$f[]=".geo.de";
	$f[]=".blinde-kuh.de";
	$f[]=".br-online.de";
	$f[]=".derkleinekoenig.de";
	$f[]=".kika.de";
	$f[]=".kindersache.de";
	$f[]=".kindernetz.de";
	$f[]=".seitenstark.de";
	$f[]=".rbb-online.de";
	$f[]=".kidsweb.de";
	$f[]=".bmu-kids.de";
	$f[]=".br-online.de";
	$f[]=".helles-koepfchen.de";
	$f[]=".kidsville.de";
	$f[]=".legakids.net";
	$f[]=".lilipuz.de";
	$f[]=".milkmoon.de";
	$f[]=".pixelkids.de";
	$f[]=".pomki.de";
	$f[]=".labbe.de";
	$f[]=".hamsterkiste.de";
	$f[]=".physikfuerkids.de";
	$f[]=".sowieso.de";
	$f[]=".hanisauland.de";
	$f[]=".rossipotti.de";
	$f[]=".wasistwas.de";
	$f[]=".wolf-kinderclub.de";
	$f[]=".kidnetting.de";
	$f[]=".radio108komma8.de";
	$f[]=".klasse-wasser.de";
	$f[]=".oekolandbau.de";
	$f[]=".news4kids.de";
	$f[]=".primolo.de";
	$f[]=".starke-pfoten.de";
	$f[]=".internet-abc.de";
	$f[]=".notenmax.de";
	$f[]=".lucylehmann.de";
	$f[]=".kidkit.de";
	$f[]=".junge-klassik.de";
	$f[]=".medizin-fuer-kids.de";
	$f[]=".global-gang.de";
	$f[]=".klickerkids.de";
	$f[]=".kinderrathaus.de";
	$f[]=".bayerische.staatsoper.de";
	$f[]=".zum.de";
	$f[]=".mechant-loup.schule.de";
	$f[]=".prinzessin-knoepfchen.de";
	$f[]=".1000-maerchen.de";
	$f[]=".creativecommons.org";
	$f[]=".toggo.de";
	$f[]=".toggolino.de";
	
	$results=$q->QUERY_SQL("SELECT * FROM acls_whitelist WHERE enabled=1 AND ztype='dstdomain'","artica_backup");
	
	foreach($results as $index=>$ligne) {
		$items=$ligne["pattern"];
		if(substr($items, 0,1)=="^"){$items=substr($items,1,strlen($items));}
		if(substr($items, 0,1)<>"."){$items=".$items";}
		$f[]=$items;
	}
	
	
	

	
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} ". count($f)." Whitelisted item(s)\n";}
	
	
	$f[]="{ +block{Artica} }";
	$f[]="/piwik/piwik\.php\?action_name=";
	$f[]=".f1g\.fr/media/ext";
	@file_put_contents("/etc/privoxy/artica.action", @implode("\n", $f));
}


function easyListDownloads(){
	$FanboyAnnoyanceList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FanboyAnnoyanceList"));
	$FanboySocialBlockingList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FanboySocialBlockingList"));
	$EasyPrivacy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EasyPrivacy"));
	$EasyListGermany=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EasyListGermany"));
	$EasyListItaly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EasyListItaly"));
	$EasyListDutch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EasyListDutch"));
	$EasyListFrench=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EasyListFrench"));
	$EasyListChina=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EasyListChina"));
	$EasyListBulgarian=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EasyListBulgarian"));
	$EasyListIndonesian=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EasyListIndonesian"));
	
	$uri[]="https://easylist-downloads.adblockplus.org/advblock.txt";
	$uri[]="https://easylist.to/easylist/easylist.txt";
	$uri[]="https://easylist-downloads.adblockplus.org/malwaredomains_full.txt";
	$uri[]="https://easylist-downloads.adblockplus.org/antiadblockfilters.txt";
	if($EasyPrivacy==1){
		$uri[]="https://easylist.to/easylist/easyprivacy.txt";
		$uri[]="https://easylist-downloads.adblockplus.org/easyprivacy.tpl";
	}
	if($FanboyAnnoyanceList==1){$uri[]="https://easylist.to/easylist/fanboy-annoyance.txt";}
	if($FanboySocialBlockingList==1){$uri[]="https://easylist.to/easylist/fanboy-social.txt";}
	if($EasyListGermany==1){$uri[]="https://easylist.to/easylistgermany/easylistgermany.txt";}
	if($EasyListItaly==1){$uri[]="https://easylist-downloads.adblockplus.org/easylistitaly.txt";}
	if($EasyListDutch==1){$uri[]="https://easylist-downloads.adblockplus.org/easylistdutch.txt";}
	if($EasyListFrench==1){$uri[]="https://easylist-downloads.adblockplus.org/liste_fr.txt";}
	if($EasyListChina==1){$uri[]="https://easylist-downloads.adblockplus.org/easylistchina.txt";}
	if($EasyListBulgarian ==1){$uri[]="http://stanev.org/abp/adblock_bg.txt";}
	if($EasyListIndonesian ==1){$uri[]="https://raw.githubusercontent.com/heradhis/indonesianadblockrules/master/subscriptions/abpindo.txt";}
	
	
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$TEMP_DIR=$unix->TEMP_DIR()."/easylist";
	if(is_dir($TEMP_DIR)){shell_exec("$rm -rf $TEMP_DIR");}
	@mkdir($TEMP_DIR,0755,true);
	if(is_file("/etc/privoxy/adblock2privoxy.task")){@unlink("/etc/privoxy/adblock2privoxy.task");}
	$cmd[]="/usr/share/artica-postfix/bin/adblock2privoxy";
	$cmd[]="-p /etc/privoxy";
	$cmd[]="-w /var/www/privoxy";
	$cmd[]="-d 127.0.0.1";
	$cmd[]="-t /etc/privoxy/adblock2privoxy.task";
	$c=0;$ok=0;
	foreach ($uri as $url){
		build_progress_restart("{downloading} ".basename($url),20);
		$curl=new ccurl($url);
		$c++;
		if(!$curl->GetFile("$TEMP_DIR/".basename($url))){
			@unlink("$TEMP_DIR/".basename($url));
			build_progress_restart("{downloading} ".basename($url)." {failed}",20);
			squid_admin_mysql(0, "Unable to download ".basename($url)." Error $curl->error", $url,__FILE__,__LINE__);
			continue;
		}
		$ok++;
		$cmd[]="$TEMP_DIR/".basename($url);
	}
	
	@chmod("/usr/share/artica-postfix/bin/adblock2privoxy", 0755);
	if($ok==0){
		build_progress_restart("{update} {failed}",110);
		return;
	}
	$t=time();
	$cmdline=@implode(" ", $cmd);
	build_progress_restart("{converting}",50);
	$nohup=$unix->find_program("nohup");
	echo "$nohup $cmdline >>/usr/share/artica-postfix/ressources/logs/privoxy.progress.log 2>&1 &\n";
	shell_exec("$nohup $cmdline >>/usr/share/artica-postfix/ressources/logs/privoxy.progress.log 2>&1 &");
	$prc=50;
	$o=0;
	for($i=0;$i<700;$i++){
		$pid=$unix->PIDOF("/usr/share/artica-postfix/bin/adblock2privoxy");
		if(!$unix->process_exists($pid)){break;}
		$distance=$unix->distanceOfTimeInWords($t,time());
		$o++;
		if($o>5){$prc++;$o=0;}
		if($prc>90){$prc=90;}
		build_progress_restart("{converting} $distance",$prc);
		sleep(1);
	}
	
	
	$f=explode("\n",@file_get_contents("/etc/privoxy/adblock2privoxy.task"));
	$ARRAY["TIME"]=time();
	
	foreach ($f as $line){
		$line=trim($line);
		if(preg_match("#Elements hiding rules:\s+([0-9]+)#i", $line,$re)){
			$ARRAY["EHR"]=$re[1];
			continue;
		}
		if(preg_match("#Request block rules for exception:\s+([0-9]+)#i", $line,$re)){
			$ARRAY["RBRFE"]=$re[1];
			continue;
		}		
		if(preg_match("#Request block rules total:\s+([0-9]+)#i", $line,$re)){
			$ARRAY["RBRT"]=$re[1];
			continue;
		}	
		if(preg_match("#Request block rules with domain option:\s+([0-9]+)#i", $line,$re)){
			$ARRAY["RBRDO"]=$re[1];
			continue;
		}
		if(preg_match("#Request block rules with request type options:\s+([0-9]+)#i", $line,$re)){
			$ARRAY["RBRRTO"]=$re[1];
			continue;
		}
		if(preg_match("#Rules with third party option:\s+([0-9]+)#i", $line,$re)){
			$ARRAY["RTPO"]=$re[1];
			continue;
		}							
	}

	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PrivoxyPatternStatus", serialize($ARRAY));
	build_progress_restart("{reconfiguring}",90);
	build();
	build_progress_restart("{reloading}",95);
	reload(true);
	build_progress_restart("{converting} {done}",100);
	
}
