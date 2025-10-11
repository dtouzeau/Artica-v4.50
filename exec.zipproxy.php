<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){
		if(is_file("/etc/init.d/artica-cd")){
				print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)
				." Waiting Artica-CD to finish\n";exit();
			}
}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;
	$GLOBALS["OUTPUT"]=true;
	$GLOBALS["debug"]=true;
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}

$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["SERVICE_NAME"]="Proxy compressor";
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
$GLOBALS["BY_SCHEDULE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--byschedule#",implode(" ",$argv))){$GLOBALS["BY_SCHEDULE"]=true;}

if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.groups.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.parents.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();exit();}
if($argv[1]=="--rotate"){$GLOBALS["OUTPUT"]=true;zipproxy_rotate();exit();}
if($argv[1]=="--global"){$GLOBALS["OUTPUT"]=true;zipproxy_global();exit();}
if($argv[1]=="--access"){$GLOBALS["OUTPUT"]=true;zipproxy_access();exit();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}



function restart($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			build_progress(110,"{restarting} {failed}");
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Stopping service\n";}
	
	build_progress(50,"{stopping_service}");
	stop(true);
	
	if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Building configuration\n";}
	build_progress(60,"{reconfiguring}");
	build();
	if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service\n";}
	if(!start(true)){
		build_progress(110,"{failed}");
	}
	build_progress(100,"{success}");
}

function reload($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());

	$sock=new sockets();
	$EnableProxyCompressor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyCompressor"));
	if($EnableProxyCompressor==0){
		if($GLOBALS["OUTPUT"]){echo "Reload........: [INIT]: {$GLOBALS["SERVICE_NAME"]} Disabled ( see EnableProxyCompressor )...\n";}
		return;
	}


	build();
	$masterbin=$unix->find_program("ziproxy");
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Reload........: [INIT]: {$GLOBALS["SERVICE_NAME"]} not installed\n";}
		return;
	}
	$pid=zipproxy_pid();
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Reload........: [INIT]: {$GLOBALS["SERVICE_NAME"]} Service PID $pid running since {$time}Mn...\n";}
		unix_system_HUP($pid);
		return;
	}
	start(true);
}
function NETWORK_ALL_INTERFACES(){
	if(isset($GLOBALS["NETWORK_ALL_INTERFACES"])){return $GLOBALS["NETWORK_ALL_INTERFACES"];}
	$unix=new unix();
	$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES(true);
	unset($GLOBALS["NETWORK_ALL_INTERFACES"]["127.0.0.1"]);
}
function start($nopid=false){
	$unix=new unix();

	$sock=new sockets();

	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return true;
		}
	}

	if(is_file("/etc/init.d/ziproxy")){remove_service("/etc/init.d/ziproxy");}

	$pid=zipproxy_pid();
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already running since {$time}Mn...\n";}
		return true;
	}

	$EnableProxyCompressor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyCompressor"));
	if($EnableProxyCompressor==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Disabled ( see EnableProxyCompressor )...\n";}
		return false;
	}

	$masterbin=$unix->find_program("ziproxy");
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Not installed...\n";}
		return false;
	}

	CheckFilesAndSecurity();
	if(!is_file("/etc/squid3/ziproxy.conf")){build();}
	$zipproxy_version=zipproxy_version();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service v$zipproxy_version\n";}
	$cmd="$masterbin -d -c /etc/squid3/ziproxy.conf -p /var/run/squid/ziproxy.pid";
	@unlink("/var/run/squid/ziproxy.pid");
	shell_exec($cmd);

	$c=1;
	for($i=0;$i<10;$i++){
		sleep(1);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service waiting $c/10\n";}
		$pid=zipproxy_pid();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Success PID $pid\n";}
			break;
		}
		$c++;
	}

	$pid=zipproxy_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $cmd\n";}
		return false;
	}
	
	return true;

}

function install(){
	echo "Installing Ziproxy...\n";
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableProxyCompressor", 1);
	build_progress(20,"{installing}");
	zipproxy_service();
	build_progress(30,"{installing}");
	build();
	build_progress(40,"{installing}");
	build_monit();
	build_progress(50,"{installing}");
	shell_exec("/etc/init.d/zipproxy restart");
	build_progress(60,"{installing}");
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	build_progress(100,"{installing} {success}");
	cluster_mode();
}

function uninstall(){
	$unix=new unix();
	build_progress(50, "{uninstalling}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableProxyCompressor",0);
	remove_service("/etc/init.d/zipproxy");
    remove_service("/etc/init.d/ziproxy");

	if(is_file("/etc/monit/conf.d/APP_ZIPPROXY.monitrc")){
		@unlink("/etc/monit/conf.d/APP_ZIPPROXY.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
	}
	build_progress(90, "{uninstalling}");
	system("/usr/sbin/artica-phpfpm-service -proxy-parents");
	
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	build_progress(100, "{uninstalling} {done}");
	cluster_mode();
	
}

function build_monit(){

	$f[]="check process APP_ZIPPROXY with pidfile /var/run/squid/ziproxy.pid";
	$f[]="\tstart program = \"/etc/init.d/zipproxy start --monit\"";
	$f[]="\tstop program = \"/etc/init.d/zipproxy stop --monit\"";

	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_ZIPPROXY.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_ZIPPROXY.monitrc")){
		echo "/etc/monit/conf.d/APP_ZIPPROXY.monitrc failed !!!\n";
	}
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");

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

function zipproxy_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/zipproxy";
	$php5script="exec.zipproxy.php";
	$daemonbinLog="Proxy compressor";
	if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}


	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         zipproxy";
	$f[]="# Required-Start:    \$local_fs \$syslog \$squid";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$squid";
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
function CheckFilesAndSecurity(){
	$unix=new unix();
	$f[]="/etc/ziproxy";
    foreach ($f as $val){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} checking \"$val\"\n";}
		if(!is_dir($val)){@mkdir($val,0755,true);}
		$unix->chown_func("squid","squid","$val/*");
	}

}
function stop(){

	$unix=new unix();

	$sock=new sockets();
	$masterbin=$unix->find_program("ziproxy");

	$pid=zipproxy_pid();
	if(!is_file($masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Not installed\n";}
		return;

	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already stopped...\n";}
		return;
	}

	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");





	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
	shell_exec("$masterbin -c /etc/squid3/ziproxy.conf -k");
	for($i=0;$i<5;$i++){
		$pid=zipproxy_pid();
		if(!$unix->process_exists($pid)){break;}
		shell_exec("$masterbin -c /etc/squid3/ziproxy.conf -k");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=zipproxy_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}

	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=zipproxy_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		unix_system_kill_force($pid);
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success stopped...\n";}
		@unlink("/var/run/squid/ziproxy.pid");
		return;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
	}
}
function zipproxy_version(){
	$unix=new unix();
	if(isset($GLOBALS["zipproxy_version"])){return $GLOBALS["zipproxy_version"];}
	$squidbin=$unix->find_program("ziproxy");
	if(!is_file($squidbin)){return "0.0.0";}
	exec("$squidbin -h 2>&1",$results);
	foreach ($results as $num=>$val){
		if(preg_match("#Ziproxy\s+([0-9\.]+)#", $val,$re)){
			$GLOBALS["zipproxy_version"]=trim($re[1]);
			return $GLOBALS["zipproxy_version"];
		}
	}
}

function zipproxy_pid(){
	$unix=new unix();
	$masterbin=$unix->find_program("ziproxy");
	$pid=$unix->get_pid_from_file('/var/run/squid/ziproxy.pid');
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($masterbin);
}

function build_progress($pourc,$text){
	$cachefile=PROGRESS_DIR."/zipproxy.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "{$pourc}% $text\n";
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}


function build(){
	$sock=new sockets();
	$unix=new unix();
	$ini=new Bs_IniHandler();
	$squid=new squidbee();
	$IPADDRSSL=array();
	$IPADDRSSL2=array();
	$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
	$SquidAsMasterPeer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAsMasterPeer"));
	$SquidAsMasterPeerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAsMasterPeerPort"));
	$SquidAsMasterPeerPortSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAsMasterPeerPortSSL"));
	$SquidAsMasterPeerIPAddr=$sock->GET_INFO("SquidAsMasterPeerIPAddr");
	$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
	$ini->loadString($ArticaSquidParameters);
	$ZipProxyListenIpAdress=$sock->GET_INFO("ZipProxyListenIpAdress");
	$ZipProxyListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZipProxyListenInterface"));
	if($ZipProxyListenInterface==null){$ZipProxyListenIpAdress="127.0.0.1";}
	
	
	$zipproxy_port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("zipproxy_port"));
	if($zipproxy_port==0){$zipproxy_port=5561;}
	$zipproxy_MaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("zipproxy_MaxSize"));
	if($zipproxy_MaxSize==0){$zipproxy_MaxSize=1048576;}
	$ZipProxyUnrestricted=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZipProxyUnrestricted"));
	$ConvertToGrayscale=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ConvertToGrayscale"));

	$zipproxy_ProcessHTML=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("zipproxy_ProcessHTML"));
	$zipproxy_ProcessCSS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("zipproxy_ProcessCSS"));
	$zipproxy_ProcessJS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("zipproxy_ProcessJS"));

	
	if($ZipProxyListenIpAdress==null){
		$ZipProxyListenIpAdress=$unix->InterfaceToIPv4($ZipProxyListenInterface);
	}


	
	$hostname=$unix->hostname_g();


	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: Listen......: $ZipProxyListenIpAdress:$zipproxy_port\n";}
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: Unrestricted: $ZipProxyUnrestricted\n";}
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: Master......: $SquidAsMasterPeerIPAddr:$SquidAsMasterPeerPort\n";}
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: Convert to g: $ConvertToGrayscale\n";}
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: Process JS..: $zipproxy_ProcessJS\n";}
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: Process CSS.: $zipproxy_ProcessCSS\n";}
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: Process HTML: $zipproxy_ProcessHTML\n";}

	if($ZipProxyListenIpAdress==null){$ZipProxyListenIpAdress="127.0.0.1";}


	$f[]="############################";
	$f[]="# daemon mode-only options #";
	$f[]="############################";
	$f[]="";
	$f[]="## Port to listen for proxy connections";
	$f[]="Port = $zipproxy_port";
	$f[]="";
	$f[]="## Local address to listen for proxy connections";
	$f[]="## If you have more than one network interface,";
	$f[]="## it's useful for restricting to which interface you want to bind to.";
	$f[]="## By default Ziproxy binds to all interfaces.";
	if($ZipProxyListenIpAdress<>null){
		$f[]="Address = \"$ZipProxyListenIpAdress\"";
	}
	$f[]="";
	$f[]="## Accepts conections only from that address.";
	$f[]="## WARNING: Remember to restrict the access to Ziproxy";
	$f[]="## if your machine is directly connected to the Internet.";
	$f[]="OnlyFrom = \"127.0.0.1\"";
	$f[]="# MaxActiveUserConnections = 20";
	$f[]="PIDFile = \"/var/run/squid/ziproxy.pid\"";
	$f[]="RunAsUser = \"squid\"";
	$f[]="RunAsGroup = \"squid\"";
	$f[]="";
	$f[]="";
	$f[]="";
	$f[]="##################################";
	$f[]="# TOS marking (daemon mode-only) #";
	$f[]="##################################";
	$f[]="";
	$f[]="## TOS marking";
	$f[]="## Enable this if you want to specify the (IP-level) TOS certain types";
	$f[]="## of traffic from ziproxy -> user.";
	$f[]="##";
	$f[]="## This feature is useful if one wants to do application-level QoS.";
	$f[]="## Setting TOS does not provide QoS alone. You must be either using";
	$f[]="## a network with routers priorizing traffic according to their TOS,";
	$f[]="## or set your own QoS/traffic-shaper system  and treat the packets";
	$f[]="## with certain TOS accordingly.";
	$f[]="##";
	$f[]="## Ziproxy is RFC-agnostic regarding TOS bit meanings,";
	$f[]="## though there may be limitations imposed by the host OS.";
	$f[]="## See: RFC 791, RFC 1122, RFC 1349, RFC 2474 and RFC 3168.";
	$f[]="##";
	$f[]="## If disabled, all other TOS options won't have effect.";
	$f[]="## Disabled by default.";
	$f[]="# TOSMarking = false";
	$f[]="";
	$f[]="## TOS to set by default";
	$f[]="## This is a decimal value between 0-255.";
	$f[]="##";
	$f[]="## If unset, will use the OS default (which usually is 0).";
	$f[]="## If you want to make sure it is set to 0, then set";
	$f[]="## this option accordingly.";
	$f[]="##";
	$f[]="## Your OS may put restrictions on which bits you may set";
	$f[]="## (so certain bits will remain unchanged regardless).";
	$f[]="## Your OS may also restrict which bits and/or value ranges";
	$f[]="## you may set if you're not running as root.";
	$f[]="## Other (non-unixish) OSes may be unable to set TOS at all.";
	$f[]="##";
	$f[]="## Default: unset.";
	$f[]="# TOSFlagsDefault = 0";
	$f[]="";
	$f[]="## TOS to set when the traffic is considered \"differentiated\",";
	$f[]="## according to TOSMarkAsDiffURL, TOSMarkAsDiffCT or TOSMarkAsDiffSizeBT.";
	$f[]="## This is a decimal value between 0-255.";
	$f[]="##";
	$f[]="## If unset, there will be no differentiated traffic at all.";
	$f[]="##";
	$f[]="## Your OS may put restrictions on which bits you may set";
	$f[]="## (so certain bits will remain unchanged regardless).";
	$f[]="## Your OS may also restrict which bits and/or value ranges";
	$f[]="## you may set if you're not running as root.";
	$f[]="## Other (non-unixish) OSes may be unable to set TOS at all.";
	$f[]="##";
	$f[]="## Default: unset.";
	$f[]="# TOSFlagsDiff = 16";
	$f[]="";
	$f[]="## This is the file containing a list of URLs which should";
	$f[]="## have their traffic \"differentiated\"";
	$f[]="## (that is, to have their TOS changed to TOSFlagsDiff).";
	$f[]="##";
	$f[]="## Inside the file, the URLs may also contain pattern-matching asterisks.";
	$f[]="## Comments may be present if prefixed by '#' (shell-alike).";
	$f[]="## In order to match a whole site: \"http://www.examplehost.xyz/*\"";
	$f[]="##";
	$f[]="## Default: none";
	$f[]="# TOSMarkAsDiffURL = \"/etc/ziproxy/change_tos.list\"";
	$f[]="";
	$f[]="## This is the content-type list of data that should";
	$f[]="## have their traffic \"differentiated\"";
	$f[]="## (that is, to have their TOS changed to TOSFlagsDiff).";
	$f[]="## This is the content-type as received by the remote HTTP server,";
	$f[]="## if it is changed by Ziproxy later, it will not be taken into account.";
	$f[]="##";
	$f[]="## \"\" (empty string) will match empty content-types AND data which have";
	$f[]="## no content-type specified.";
	$f[]="##";
	$f[]="## If no subtype is specified, all subtypes will match:";
	$f[]="## \"aaaa\" will match \"aaaa\", \"aaaa/bbbb\", \"aaaa/cccc\" etc";
	$f[]="##";
	$f[]="## See also: TOSMarkAsDiffCTAlsoXST";
	$f[]="## Default: none";
	$f[]="# TOSMarkAsDiffCT = {\"video/flv\", \"video/x-msvideo\", \"audio/*\",";
	$f[]="#                    \"application/x-shockwave-flash\", \"application/x-rpm\",";
	$f[]="#                    \"application/x-msi\", \"application/x-tar\"}";
	$f[]="";
	$f[]="## When using TOSMarkAsDiffCT, this defines whether to also automatically add";
	$f[]="## content-type entries with 'x-' prefix appended to subtypes";
	$f[]="## (aaaa/bbbb also adding aaaa/x-bbbb).";
	$f[]="## Usually it's convenient to do this way, that avoids worrying about";
	$f[]="## having to create duplicated entries, or whether which variant is valid.";
	$f[]="##";
	$f[]="## You may want to disable this is you wish to have a precise control";
	$f[]="## of what types of content-type you wish to include.";
	$f[]="##";
	$f[]="## See also: TOSMarkAsDiffCT";
	$f[]="## Default: true";
	$f[]="# TOSMarkAsDiffCTAlsoXST = true";
	$f[]="";
	$f[]="## This is the stream size threshold (in bytes) which, if reached,";
	$f[]="## will make such traffic \"differentiated\"";
	$f[]="## (that is, to have their TOS changed to TOSFlagsDiff).";
	$f[]="## The stream size is the ziproxy -> user one (which may be";
	$f[]="## bigger or smaller than the original one, sent by the HTTP server).";
	$f[]="##";
	$f[]="## There are two possible behaviors with this parameter:";
	$f[]="## - The total stream size is known beforehand, so the data";
	$f[]="##   will be marked as differentiated from the beginning.";
	$f[]="## - The total stream size is unknown, so the data will";
	$f[]="##   be marked as differentiated once it reaches that";
	$f[]="##   size.";
	$f[]="##";
	$f[]="## Current limitations (this may change in the future):";
	$f[]="## - The maximum value to be specified here is signed int";
	$f[]="##   usually 32bit -> (2^31 - 1).";
	$f[]="## - HTTP range requests are not taken into account so, if their effective";
	$f[]="##   streams do not reach this threshold, such data will not be";
	$f[]="##   marked as \"differentiated\", even if the HTTP range goes beyond that.";
	$f[]="## - Usually the HTTP headers will not be taken into account (only the body";
	$f[]="##   size itself), except in cases such as CONNECT method";
	$f[]="##   and URLNoProcessing (cases when the data from server is treated like";
	$f[]="##   a \"black box\").";
	$f[]="##";
	$f[]="## Default: none";
	$f[]="# TOSMarkAsDiffSizeBT = 4000000";
	$f[]="";
	$f[]="";
	$f[]="";
	$f[]="###################";
	$f[]="# general options #";
	$f[]="###################";
	$f[]="";
	$f[]="#DebugLog = \"/var/log/squid/zipproxy-debug.log\"";
	$f[]="ErrorLog = \"/var/log/squid/zipproxy-error.log\"";
	$f[]="AccessLog = \"/var/log/squid/access-ziproxy.log\"";
	$f[]="# InterceptCrashes = false";
	$f[]="AuthMode = 0";
	$f[]="# AuthPasswdFile = \"/etc/ziproxy/http.passwd\"";
	$f[]="# AuthSASLConfPath = \"/etc/ziproxy/sasl/\"";

	if($SquidAsMasterPeerIPAddr<>null){
		$f[]="#NextProxy=\"$SquidAsMasterPeerIPAddr\"";
		$f[]="#NextPort=$SquidAsMasterPeerPort";
	}

	$ZiproxyOutgoingIPAddr=null;
	$ZiproxyOutgoingInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZiproxyOutgoingInterface"));
	if($ZiproxyOutgoingInterface<>null){
		$ZiproxyOutgoingIPAddr=$unix->InterfaceToIPv4($ZiproxyOutgoingInterface);
	}

	$f[]="";
	$f[]="## Bind outgoing connections (to remote HTTP server) to the following (local) IPs";
	$f[]="## It applies to the _outgoing_ connections, it has _no_ relation to the listener socket.";
	$f[]="## When 2 or more IPs are specified, Ziproxy will rotate to each of those at each";
	$f[]="## outgoing connection. All IPs have the same priority.";
	$f[]="## You may use this option for either of the following reasons:";
	$f[]="## 1. - To use only a specific IP when connecting to remote HTTP servers.";
	$f[]="## 2. - Use 2 or more IPs for load balancing (a rather primitive one, since it's";
	$f[]="##      connection-based and does not take into account the bytes transferred).";
	$f[]="## 3. - You have a huge intranet and certain sites (google.com, for example)";
	$f[]="##      are blocking your requests because there are so many coming from the same IP.";
	$f[]="##      So you may use 2 or more IPs here and make it appear that your requests";
	$f[]="##      come from several different machines.";
	$f[]="## This option does _not_ spoof packets, it merely uses the host's local IPs.";
	$f[]="## Note: While in (x)inetd mode, output may be bind-ed only to one IP.";
	$f[]="## Disabled by default (binds to the default IP, the OS decides which one).";
	$f[]="## See also: BindOutgoingExList";
	$f[]="# BindOutgoing = { \"234.22.33.44\", \"4.3.2.1\", \"44.200.34.11\" }";
	if($ZiproxyOutgoingIPAddr<>null){
		$f[]="BindOutgoing = { \"$ZiproxyOutgoingIPAddr\"}";
	}
	$f[]="";
	$f[]="## Specifies a file containing a list of hosts which should not suffer";
	$f[]="## IP rotation as specified by the option \"BindOutgoing\".";
	$f[]="## The reason for this option is that certain services do not like";
	$f[]="## the client IP changing in the same session.";
	$f[]="## Certain webmail services fail or return authentication failure in this case.";
	$f[]="## Example: www.bol.com.br";
	$f[]="## This option has no effect if BindOutgoing is not used.";
	$f[]="## Default: empty, no hosts are exempted.";
	$f[]="## See also: BindOutgoingExAddr";
	$f[]="# BindOutgoingExList=\"/etc/ziproxy/bo_exception.list\"";
	$f[]="";
	$f[]="## Defines a specific IP to be bound to for hosts specified in BindOutgoingExList.";
	$f[]="## As with BindOutgoing, this IP must be a local IP from the server running Ziproxy.";
	$f[]="## This IP may be one of those specified in BindOutgoing, but that's _not_";
	$f[]="## a requirement and may be a different IP.";
	$f[]="## This option has no effect if BindOutgoingExList is not being used.";
	$f[]="## Default: empty, uses the first IP specified in BindOutgoing.";
	$f[]="# BindOutgoingExAddr=\"98.7.65.43\"";
	$f[]="";
	$f[]="## Allow processing of requests as transparent proxy";
	$f[]="## (will still accept normal proxy requests)";
	$f[]="## In order to use Ziproxy as transparent proxy it's also needed";
	$f[]="## to reroute the connections from x.x.x.x:80 to ziproxy.host:PROXY_PORT";
	$f[]="## Disabled by default.";
	$f[]="## See also: RestrictOutPortHTTP";
	$f[]="# TransparentProxy = false";
	$f[]="";
	$f[]="## Whether to process normal proxy requests or not";
	$f[]="## Only makes sense when TransparentProxy is enabled.";
	$f[]="## If transparent proxy is enabled, it's usually a good idea to disable";
	$f[]="## conventional proxying since, depending on the layout of your network,";
	$f[]="## it can be abused by ill-meant users to circumvent restrictions";
	$f[]="## presented by another proxy placed between Ziproxy and the users.";
	$f[]="## Enabled by default.";
	$f[]="ConventionalProxy = true";
	$f[]="";
	$f[]="## Whether to allow the CONNECT method.";
	$f[]="## This method is used by HTTPS, but may be used for other";
	$f[]="## types of service (like instant messenging) which allow tunneling through http proxy.";
	$f[]="## If you plan on serving only HTTP requests (no HTTPS nor anything else)";
	$f[]="## you may want to disable this, in order to prevent potential";
	$f[]="## abuse of the service.";
	$f[]="## Enabled by default.";
	$f[]="## See also: RestrictOutPortCONNECT";
	$f[]="AllowMethodCONNECT = true";
	$f[]="";
	$f[]="## If defined, restricts the outgoing connections (except CONNECT methods - used by HTTPS)";
	$f[]="## to the listed destination ports.";
	$f[]="## If TransparentProxy is used, for security reasons it's recommended to restrict";
	$f[]="## to the ports (typically port 80) which are being intercepted.";
	$f[]="## Default: all ports are allowed.";
	$f[]="## See also: RestrictOutPortCONNECT";
	$f[]="# RestrictOutPortHTTP = {80, 8080}";
	$f[]="";
	$f[]="## If defined, restricts the outgoing connections using the CONNECT method (used by HTTPS)";
	$f[]="## to the listed destination ports.";
	$f[]="## If AllowMethodCONNECT=false, then no ports are allowed at all regardless this list.";
	$f[]="## Default: all ports are allowed.";
	$f[]="## See also: AllowMethodCONNECT, RestrictOutPortHTTP";
	$f[]="# RestrictOutPortCONNECT = {443}";
	$f[]="";
	$f[]="## Whether to override the Accept-Encoding more to Ziproxy's liking.";
	$f[]="## If disabled, Ziproxy will just forward Accept-Encoding received from the client";
	$f[]="## (thus the data may or not come gzipped, depending on what the HTTP client says).";
	$f[]="##";
	$f[]="## Currently, this option is used to always advertise Gzip capability to";
	$f[]="## the remote HTTP server.";
	$f[]="## Enabling this does not neccessarily mean that the data will come compressed";
	$f[]="## from the server. This option just advertises the capability at Ziproxy's side,";
	$f[]="## the remote server must support that capability aswell.";
	$f[]="##";
	$f[]="## This has _no_ relation to the Gzip support between Ziproxy and the client, thus";
	$f[]="## you may leave this enabled even if you have clients that do not support Gzip.";
	$f[]="## Ziproxy will compress/decompress the data according to the client.";
	$f[]="##";
	$f[]="## Enabled by default.";
	$f[]="OverrideAcceptEncoding = true";
	$f[]="DecompressIncomingGzipData = true";
	$f[]="";
	$f[]="## Replaces the User-Agent data sent by the client with a custom string,";
	$f[]="## OR defines User-Agent with that string if that entry was not defined.";
	$f[]="## If disabled, Ziproxy will just forward the User-Agent sent by the client.";
	$f[]="## Normally you will want to leave this option DISABLED (commented).";
	$f[]="##";
	$f[]="## It's useful if you, for some reason, want to identify all the clients as";
	$f[]="## some specific browser/version/OS.";
	$f[]="## Certain websites may appear broken if the client uses a different browser than";
	$f[]="## the one specified here.";
	$f[]="## Certain webservers may break completely when an unrecognized User-Agent is provided";
	$f[]="## (for example: www.rzeczpospolita.pl).";
	$f[]="##";
	$f[]="## Undefined by default (leave User-Agent as defined by the client).";
	$f[]="# RedefineUserAgent = \"Mozilla/5.0 (compatible; UltraBrowser/8.1; CP/M; console40x24; z80)\"";
	$f[]="";
	$f[]="## When Ziproxy receives Gzip data it will try to decompress in order to do";
	$f[]="## further processing (HTMLopt, PreemptDNS etc).";
	$f[]="## This makes Ziproxy vulnerable to 'gzip-bombs' (eg. like 10 GB of zeroes, compressed)";
	$f[]="## which could be used to slow down or even crash the server.";
	$f[]="## In order to avoid/minimise such problems, you can limit the max";
	$f[]="## decompression proportion, related to the original file.";
	$f[]="## If a Gzipped file exceedes that proportion while decompressing, its";
	$f[]="## decompression is aborted.";
	$f[]="## The user will receive an error page instead or (if already transferring)";
	$f[]="## transfer will simply be aborted.";
	$f[]="##";
	$f[]="## You may disable this feature defining its value to '0'.";
	$f[]="## default: 2000 (that's 2000% == 20 times the compressed size)";
	$f[]="MaxUncompressedGzipRatio = 2000";
	$f[]="";
	$f[]="## When limiting decompression rate with MaxUncompressedGzipRatio";
	$f[]="## _and_ gunzipping while streaming it's not possible to know the";
	$f[]="## file size until the transfer is finished. So Ziproxy verifies this while";
	$f[]="## decompressing.";
	$f[]="## The problem by doing this is the possible false positives:";
	$f[]="## certain files compress a lot at their beginning, but then not-so";
	$f[]="## shortly after.";
	$f[]="## In order to prevent/minimize such problems, we define the minimum";
	$f[]="## output (the decompressed data) generated before starting to";
	$f[]="## check the decompression rate.";
	$f[]="## If defined as '0', it will check the rate immediately.";
	$f[]="## A too large value will increase the rate-limit precision, at the cost of less";
	$f[]="## protection.";
	$f[]="## Streams with output less that this value won't have decompression";
	$f[]="## rate checking at all.";
	$f[]="## This feature is only active if MaxUncompressedGzipRatio is defined.";
	$f[]="## This does not affect data wholly loaded to memory (for further processing).";
	$f[]="## default: 10000000 (bytes)";
	$f[]="## Note: The previous default (until version 2.7.9_BETA) was 250000";
	$f[]="## See also: MaxUncompressedGzipRatio";
	$f[]="MinUncompressedGzipStreamEval = 10000000";
	$f[]="";
	$f[]="## This is the maximum compression rate allowable for an incoming";
	$f[]="## (before recompression) image file.";
	$f[]="## If an image has a higher compression rate than this, it will not";
	$f[]="## be unpacked and it will be forwarded to the client as is.";
	$f[]="## This feature protects against (or mitigates) the problem with";
	$f[]="## \"image bombs\" (gif bombs, etc) done with huge bitmaps with the same";
	$f[]="## pixel color (thus very small once compressed).";
	$f[]="## Since Ziproxy may try to recompress the image, if several of this";
	$f[]="## kind are requested, the server may run out of memory, so this";
	$f[]="## may be used as a DoS attack against Ziproxy.";
	$f[]="## This feature will not protect the client, since it will receive";
	$f[]="## the unmodified picture.";
	$f[]="## There are rare legitimate cases matching such high compression rate,";
	$f[]="## including poor website design. But in such cases is not really worth";
	$f[]="## recompressing anyway (the processing costs are not worth the savings).";
	$f[]="## Usually \"image bomb\" pictures have a >1000:1 compression ratio.";
	$f[]="## Setting this to less than 100 risks not processing legitimate pictures.";
	$f[]="## Setting 0 disables this feature.";
	$f[]="## Default: 500 (500:1 ratio)";
	$f[]="MaxUncompressedImageRatio = 0";
	$f[]="";
	$f[]="## If specified, ziproxy will send and check Via: header";
	$f[]="## with given string as host identification.";
	$f[]="## It is sometimes useful to avoid request loops. Default: not specified";
	$f[]="ViaServer = \"zipproxy-$hostname\"";
	$f[]="";
	$f[]="## If processing of request exceeds specified time in seconds,";
	$f[]="## or connection is idle beyond that time (stalled) it will abort.";
	$f[]="## This avoids processes staying forever (or for a very long time)";
	$f[]="## in case of a stalled connection or software bug.";
	$f[]="## This will NOT necessarily abort the streaming of very big files,";
	$f[]="## it will ONLY if the connection stalls or there's a software bug.";
	$f[]="## If \"0\", no timeout.";
	$f[]="## Default: 90 (seconds)";
	$f[]="ConnTimeout = 90";
	$f[]="";
	$f[]="## Max file size to try to (re)compress, in bytes;";
	$f[]="## If \"0\", means that this limitation won't apply.";
	$f[]="## This regards to the file size as received from the remote HTTP server";
	$f[]="## (which may arrive gzipped or not -- it doesn't matter).";
	$f[]="## If a file is bigger than this limit, Ziproxy will simply stream it unmodified,";
	$f[]="## unless the user also requested gzip compression (see below).";
	$f[]="## Attention: If setting a very big size, the request answer latency will";
	$f[]="##   increase since Ziproxy needs to fetch the whole file before";
	$f[]="##   attempting to (re)compress it.";
	$f[]="##   A too low value will prevent data bigger that that to de processed";
	$f[]="##   (jpg/png/gif recompression, htmlopt, preemptdns..).";
	$f[]="## Note that if:";
	$f[]="##   - Only gzipping is to be applied *OR*";
	$f[]="##   - Gzipping and other is to be applied, but data is > MaxSize";
	$f[]="##   Gzip compression (and only that) will be applied while streaming.";
	$f[]="## Default: 1048576 (bytes)";
	$f[]="##   (default used to be \"0\" in ziproxy 2.3.0 and earlier)";
	$f[]="MaxSize = $zipproxy_MaxSize";
	$f[]="UseContentLength = false";
	$f[]="";
	$f[]="## Whether to try to apply lossless compression with gzip.";
	$f[]="## This option concerns traffic between Ziproxy and the client only.";
	$f[]="## This optimization is not limited by MaxSize.";
	$f[]="##";
	$f[]="## Gzip compression applies only to content-types specified with";
	$f[]="## the parameter LosslessCompressCT.";
	$f[]="##";
	$f[]="## See also: LosslessCompressCT";
	$f[]="## Default: true";
	$f[]="Gzip = true";
	$f[]="";
	$f[]="## This parameter specifies what kind of content-type is to be";
	$f[]="## considered lossless compressible (that is, data worth applying gzip).";
	$f[]="##";
	$f[]="## Images, movies etc, normally are NOT compressible such way and those";
	$f[]="## content-types should not be added (such data would turn slightly bigger";
	$f[]="## and CPU would be wasted).";
	$f[]="##";
	$f[]="## See also: LosslessCompressCTAlsoXST, Gzip";
	$f[]="## Default: an internal list of the most common compressible content-types.";
	$f[]="LosslessCompressCT = {";
	$f[]="	\"text/*\", ";
	$f[]="	\"application/asp\", ";
	$f[]="	\"application/awk\", ";
	$f[]="	\"application/cgi\", ";
	$f[]="	\"application/class\", ";
	$f[]="	\"application/css\", ";
	$f[]="	\"application/dvi\", ";
	$f[]="	\"application/executable\", ";
	$f[]="	\"application/font\", ";
	$f[]="	\"application/futuresplash\", ";
	$f[]="	\"application/iso9660-image\", ";
	$f[]="	\"application/java\", ";
	$f[]="	\"application/javascript\", ";
	$f[]="	\"application/json\", ";
	$f[]="	\"application/msexcel\", ";
	$f[]="	\"application/mspowerpoint\", ";
	$f[]="	\"application/msword\", ";
	$f[]="	\"application/pdf\", ";
	$f[]="	\"application/perl\", ";
	$f[]="	\"application/php\", ";
	$f[]="	\"application/postscript\", ";
	$f[]="	\"application/python\", ";
	$f[]="	\"application/rtf\", ";
	$f[]="	\"application/shellscript\", ";
	$f[]="	\"application/shockwave\", ";
	$f[]="	\"application/staroffice\", ";
	$f[]="	\"application/tar\", ";
	$f[]="	\"application/truetype-font\", ";
	$f[]="	\"application/vnd.*\", ";
	$f[]="	\"application/*+xml\", ";
	$f[]="	\"application/xml\", ";
	$f[]="	\"application/xml-dtd\", ";
	$f[]="	\"image/svg+xml\"";
	$f[]=" }";
	$f[]="";
	$f[]="## When using LosslessCompressCT, this defines whether to also automatically add";
	$f[]="## content-type entries with 'x-' prefix appended to subtypes";
	$f[]="## (aaaa/bbbb also adding aaaa/x-bbbb).";
	$f[]="## Usually it's convenient to do this way, that avoids worrying about";
	$f[]="## having to create duplicated entries, or whether which variant is valid.";
	$f[]="##";
	$f[]="## Note: If LosslessCompressCT is undefined (thus the internal defaults";
	$f[]="## are being used) this option has no effect.";
	$f[]="##";
	$f[]="## You may want to disable this is you wish to have a precise control";
	$f[]="## of what types of content-type you wish to include.";
	$f[]="##";
	$f[]="## See also: LosslessCompressCT";
	$f[]="## Default: true";
	$f[]="LosslessCompressCTAlsoXST = true";
	$f[]="";
	$f[]="## Whether to try to (re)compress incoming data originally in";
	$f[]="## the following formats (true) or not (false)";
	$f[]="## default: true";
	$f[]="ProcessJPG = true";
	$f[]="ProcessPNG = true";
	$f[]="ProcessGIF = true";
	$f[]="";
	$f[]="## Whether to try to optimize HTML, CSS and Javascript, thus reducing their size";
	$f[]="## ProcessHTML: text/html";
	$f[]="## ProcessCSS:  text/css";
	$f[]="## ProcessJS:   application/[x-]javascript)";
	$f[]="## Although such data may be Gzipped too, optimizing prior to Gzipping normally";
	$f[]="## reduces the data size even further.";
	$f[]="## The final size depends much on how unoptimal is the coding of such data;";
	$f[]="## some sites already present HTML pre-optimized so, in such cases, there won't";
	$f[]="## be much gain.";
	$f[]="## Note: Due to the higher complexity of such optimization, there's some risk of a page";
	$f[]="## being corrupted.";
	$f[]="## ****** THESE OPTIONS ARE EXPERIMENTAL ******";
	$f[]="##";

	if($zipproxy_ProcessHTML==1){$zipproxy_ProcessHTML="true";}else{$zipproxy_ProcessHTML="false";}
	if($zipproxy_ProcessCSS==1){$zipproxy_ProcessCSS="true";}else{$zipproxy_ProcessCSS="false";}
	if($zipproxy_ProcessJS==1){$zipproxy_ProcessJS="true";}else{$zipproxy_ProcessJS="false";}


	$f[]="ProcessHTML = $zipproxy_ProcessHTML";
	$f[]="ProcessCSS = $zipproxy_ProcessCSS";
	$f[]="ProcessJS = $zipproxy_ProcessJS";
	$f[]="";
	$f[]="## Options for fine-tuning text/html optimization.";
	$f[]="## Only used when ProcessHTML=true";
	$f[]="## Certain optimizations may be disabled as quick 'fix' when a text data";
	$f[]="## gets currupted after being optimized.";
	$f[]="## Note: CSS and JS switches apply _only_ to such data when embedded into HTML data,";
	$f[]="##       for JS, CSS-only data, see ProcessJS and ProcessCSS options.";
	$f[]="##";
	if($zipproxy_ProcessHTML==1){
		$f[]="ProcessHTML_CSS = true";
		$f[]="ProcessHTML_JS = true";
		$f[]="ProcessHTML_tags = true";
		$f[]="ProcessHTML_text = true";
		$f[]="ProcessHTML_PRE = true";
		$f[]="ProcessHTML_NoComments = true";
		$f[]="ProcessHTML_TEXTAREA = true";
	}
	$f[]="";
	$f[]="## If enabled, will discard PNG/GIF/JP2K transparency and de-animate";
	$f[]="## GIF images if necessary for recompression, at the cost of some image";
	$f[]="## distortion.";
	$f[]="## Note: Images with useless transparency/alpha data (all pixels";
	$f[]="##       being opaque) do not require this option. In such cases Ziproxy";
	$f[]="##       will detect that and remove the useless data automatically.";
	$f[]="## Disabled by default.";
	$f[]="AllowLookChange = true";
	$f[]="";
	$f[]="## If enabled, convert images to grayscale before recompressing.";
	$f[]="## This provides extra compression, at the cost of losing color data.";
	$f[]="## Note: Not all images sent will be in grayscale, only the ones";
	$f[]="##       considered worth recompression that way.";
	$f[]="## Disabled by default.";
	if($ConvertToGrayscale==1){
		$f[]="ConvertToGrayscale = true";
	}
	$f[]="## Preemptive Name Resolution";
	$f[]="## If enabled, tries to resolve hostnames present in the processed HTML files";
	$f[]="## for speeding up things (no delay for name resolution).";
	$f[]="## One extra process + (max)PreemptNameResMax threads will run for each HTML request.";
	$f[]="## PreemptNameResMax is the max hostnames it will try to resolve per HTML file.";
	$f[]="## PreemptNameResBC \"bogus check\", ignore names whose domains are not .nnnn, .nnn or .nn";
	$f[]="##";
	$f[]="## WARNING: This option makes sense _only_ if you have a caching DNS or";
	$f[]="## a name cache of some sort (like: PDNSD).";
	$f[]="## == THIS OPTION WILL INCREASE BY MANY TIMES THE REQUESTS TO THE DNS ==";
	$f[]="##";
	$f[]="# PreemptNameRes = false";
	$f[]="# PreemptNameResMax = 50";
	$f[]="# PreemptNameResBC = true";
	$f[]="";
	$f[]="## Image quality for JPG (JPEG) compression.";
	$f[]="## Image quality is specified in integers between 100 (best) and 0 (worst).";
	$f[]="ImageQuality = {30,25,25,20}";
	$f[]="";
	$f[]="## Alpha channel (image transparency data) removal threshold";
	$f[]="## Removes alpha channel from images with a minimum opacity";
	$f[]="## of AlphaRemovalMinAvgOpacity";
	$f[]="## (1000000: completely opaque, 0: completely transparent).";
	$f[]="##";
	$f[]="## This reduces data by removing unnecessary alpha channel from";
	$f[]="## fully-opaque images; and from (subjectively) not-so-relevant transparency";
	$f[]="## information.";
	$f[]="## This also allows recompression to JPEG for PNG/JP2k images originally";
	$f[]="## with alpha channel (which is not supported by JPEG image format).";
	$f[]="## Note: Debug log reports the average alpha opacity for each";
	$f[]="##       image with alpha channel.";
	$f[]="## Default: 1000000 (remove alpha only from fully-opaque images)";
	$f[]="##";
	$f[]="# AlphaRemovalMinAvgOpacity = 1000000";
	$f[]="";
	$f[]="## Workaround for MSIE's pseudo-feature \"Show friendly HTTP error messages.\"";
	$f[]="## If User-Agent=MSIE, don't change/compress the body of error messages in any way.";
	$f[]="## If compressed it could go down below to 256 or 512 bytes and be replaced with";
	$f[]="## a local error message instead.";
	$f[]="## In certain cases the body has crucial data, like HTML redirection or so, and";
	$f[]="## that would be broken if a \"friendly error\" replaces it.";
	$f[]="##";
	$f[]="## If you are sure there are no users using MSIE's with \"friendly error messages\"";
	$f[]="## enabled, or you don't support/have users with such configuration, you may";
	$f[]="## disable this and have error data compressed for MSIE users.";
	$f[]="## This workaround does not affect other clients at all, and error messages";
	$f[]="## will be sent compressed if the client supports it.";
	$f[]="##";
	$f[]="## Enabled by default.";
	$f[]="# WA_MSIE_FriendlyErrMsgs = true";
	$f[]="";
	$f[]="## This option specifies a file containing a list of URLs that should be tunneled";
	$f[]="## by Ziproxy with no kind of processing whatsoever.";
	$f[]="## The list contain fully-formatted URLS (http://xxx/xxx), one URL per line.";
	$f[]="## The URLs may also contain pattern-matching asterisks.";
	$f[]="## Comments may be present if prefixed by '#' (shell-alike).";
	$f[]="## In order to exempt a whole site from processing: \"http://www.exemptedhost.xyz/*\"";
	$f[]="##";
	$f[]="## This option exists when a page is known to stop working under Ziproxy processing";
	$f[]="## and there's no specific workaround/bugfix still available.";
	$f[]="## Thus, this is a temporary solution when you depend on the page to work in a";
	$f[]="## production environment.";
	$f[]="##";
	$f[]="## ****** REMEMBER TO REPORT BUGS/INCOMPATIBILITIES SO THEY MAY BE FIXED *******";
	$f[]="## *** THIS IS NOT SUPPOSED TO BE A DEFINITIVE SOLUTION TO INCOMPATIBILITIES ***";
	$f[]="##";
	
	$acl=array();

	if(count($acl)>0){
		@file_put_contents("/etc/ziproxy/noprocess.list", @implode("\n", $acl));
		$f[]="URLNoProcessing = \"/etc/ziproxy/noprocess.list\"";
	}
	
	
	$f[]="";
	$f[]="## This option specifies a file containing a list of URLs which its";
	$f[]="## data should be intercepted and replaced by another.";
	$f[]="## Header data such as cookies is maintained.";
	$f[]="## Currently the only replacing data available is an empty image";
	$f[]="## (1x1 transparent pixel GIF).";
	$f[]="##";
	$f[]="## The list contain fully-formatted URLS (http://xxx/xxx), one URL per line.";
	$f[]="## The URLs may also contain pattern-matching asterisks.";
	$f[]="## Comments may be present if prefixed by '#' (shell-alike).";
	$f[]="## In order to exempt a whole site from processing: \"http://ad.somehost.xyz/*\"";
	$f[]="##";
	$f[]="## The way it is, this option may be used as an AD-BLOCKER which is";
	$f[]="## transparent to the remote host (data is downloaded from the remove server";
	$f[]="## and cookies are transported) -- a stealthy ad-blocker, if you like.";
	$f[]="##";
	$f[]="## Default: empty (no file specified, inactive)";
	$f[]="## See also: URLReplaceDataCT";
	$f[]="# URLReplaceData = \"/etc/ziproxy/replace.list\"";
	$f[]="";
	$f[]="## Same as URLReplaceData, except it will only replace the data";
	$f[]="## from matching URLs if the content-type matches";
	$f[]="## the list in URLReplaceDataCTList (mandatory parameter) aswell.";
	$f[]="##";
	$f[]="## URLReplaceDataCT may be useful as a more compatible AD-BLOCKER";
	$f[]="## if only visual files are replaced. Certain websites rely on";
	$f[]="## external javascript from advertisement hosts and break when";
	$f[]="## that data is missing, this is a way to block advertisements";
	$f[]="## in such cases.";
	$f[]="##";
	$f[]="## Default: empty (no file specified, inactive)";
	$f[]="## See also: URLReplaceDataCTList, URLReplaceData";
	$f[]="# URLReplaceDataCT = \"/etc/ziproxy/replace_ct.list\"";
	$f[]="";
	$f[]="## List of content-types to use with the URLReplaceDataCT option.";
	$f[]="## This option is required by URLReplaceDataCT.";
	$f[]="## Default: empty (no content-type specified, inactive)";
	$f[]="## See also: URLReplaceDataCTListAlsoXST, URLReplaceDataCT";
	$f[]="# URLReplaceDataCTList = {\"image/jpeg\", \"image/gif\", \"image/png\", \"application/x-shockwave-flash\"}";
	$f[]="";
	$f[]="## When using URLReplaceDataCTList, this defines whether to also automatically add";
	$f[]="## content-type entries with 'x-' prefix appended to subtypes";
	$f[]="## (aaaa/bbbb also adding aaaa/x-bbbb).";
	$f[]="## Usually it's convenient to do this way, that avoids worrying about";
	$f[]="## having to create duplicated entries, or whether which variant is valid.";
	$f[]="##";
	$f[]="## You may want to disable this is you wish to have a precise control";
	$f[]="## of what types of content-type you wish to include.";
	$f[]="##";
	$f[]="## See also: URLReplaceDataCTList";
	$f[]="## Default: true";
	$f[]="# URLReplaceDataCTListAlsoXST = true";
	$f[]="";
	$f[]="## This option specifies a file containing a list of URLs which";
	$f[]="## should be blocked.";
	$f[]="## A \"access denied\" 403 error will be returned when trying to access";
	$f[]="## one of those URLs.";
	$f[]="## Default: empty (no file specified, inactive)";
	$f[]="# URLDeny = \"/etc/ziproxy/deny.list\"";
	$f[]="";
	$f[]="## Custom HTTP Error Messages";
	$f[]="## Define here the full path to the HTML file which should be";
	$f[]="## sent, instead of the internal default page.";
	$f[]="## Note: The internal defaults give more precise error messages.";
	$f[]="##";
	
	$tpls=unserialize(base64_decode(zipproxy_templates()));
	if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: ". count($tpls)." Template(s)\n";}
	while (list ($code, $template_data) = each ($tpls) ){
		if($GLOBALS["OUTPUT"]){echo "Configuring...: ".date("H:i:s")." [INIT]: Template $code\n";}
		$template_data=str_replace("%SERV%", $unix->hostname_g(), $template_data);
		@file_put_contents("/usr/share/squid-langpack/ZIPROXY_$code.html", $template_data);
		$f[]="CustomError{$code}=\"/usr/share/squid-langpack/ZIPROXY_$code.html\"";

	}
	$f[]="";
	$f[]="";
	$f[]="";
	$f[]="##############################################################################";
	$f[]="# JPEG 2000-specific options (require Ziproxy to be compiled with libjasper) #";
	$f[]="##############################################################################";
	$f[]="";
	$f[]="## Whether to try to (re)compress incoming data originally in";
	$f[]="## the JP2 format (true) or not (false)";
	$f[]="## Note: This option is not required to be enabled in order to convert";
	$f[]="## _to_ JP2 format.";
	$f[]="## default: false";
	$f[]="# ProcessJP2 = false";
	$f[]="";
	$f[]="## Whether to try to compress a image to JP2K (JPEG 2000)";
	$f[]="## Even when enabled, other formats may sill be tried.";
	$f[]="## Web browsers' support vary and an external plugin may be required";
	$f[]="## in order to display JP2K pictures.";
	$f[]="## If \"ForceOutputNoJP2 = true\", this option will be overrided";
	$f[]="## and stay disabled.";
	$f[]="## default: false";
	$f[]="# ProcessToJP2 = false";
	$f[]="";
	$f[]="## When enabled, this option forces the conversion of all incoming";
	$f[]="## JP2K images to another format (usually JPEG).";
	$f[]="## JP2K images with unsupported internal data will be forwarded unmodified.";
	$f[]="## One may use this option to create \"JP2K-compressed tunnels\" between";
	$f[]="## two Ziproxies with narrow bandwidth in between and serve clients";
	$f[]="## which otherwise do not support JP2K while still taking advantage of that";
	$f[]="## format. In such scenario, if the clients and their Ziproxy share a LAN,";
	$f[]="## for best image quality it is recommended to set a very low (highest quality)";
	$f[]="## _local_ output compression.";
	$f[]="## This option requires \"ProcessJP2 = true\" in order to work.";
	$f[]="## default: false";
	$f[]="# ForceOutputNoJP2 = false";
	$f[]="";
	$f[]="## When enabled, every request as a client will include an extra header \"X-Ziproxy-Flags\"";
	$f[]="## announcing it as a Ziproxy with JP2 support enabled.";
	$f[]="## This option makes sense when chaining to another Ziproxy.";
	$f[]="## Note: when the request is intercepted by another Ziproxy,";
	$f[]="##       the extra header won't be sent further.";
	$f[]="## See also: JP2OutRequiresExpCap";
	$f[]="## default: false";
	$f[]="# AnnounceJP2Capability = false";
	$f[]="";
	$f[]="## \"JP2 Output Requires Explicit Capability\"";
	$f[]="## When enabled (and when JP2 output is enabled) will only compress to JP2 to";
	$f[]="## clients which explicity support for that -- that means Ziproxy with";
	$f[]="## AnnounceJP2Capability = true.";
	$f[]="## This option is useful when you want to compress to JP2 only for clients";
	$f[]="## behind a local Ziproxy with ForceOutputNoJP2 = true, but at the same time";
	$f[]="## you have clients connecting directly and those do not support JP2.";
	$f[]="## default: false (does not make such discrimination for JP2 output)";
	$f[]="# JP2OutRequiresExpCap = false";
	$f[]="";
	$f[]="## Image quality for JP2 (JPEG 2000) compression.";
	$f[]="## Image quality is specified in integers between 100 (best) and 0 (worst).";
	$f[]="## This option is similar to \"ImageQuality\" except it applies to JP2K files, instead.";
	$f[]="## JP2K, internally, works differently and has a \"rate\" setting instead of \"quality\".";
	$f[]="## Within Ziproxy's context we want to use a fixed quality, not a fixed bitrate.";
	$f[]="## Thus, prior to compression, the image is analysed in order to know which rate";
	$f[]="## (loosely) reflects the quality had this picture be compressed using jpeg.";
	$f[]="## This option obsoletes \"JP2Rate\".";
	$f[]="# JP2ImageQuality = {20,15,15,15}";
	$f[]="";
	$f[]="## Color model to be used while compressing images to JP2K.";
	$f[]="## Accepted values:";
	$f[]="##   0 - RGB";
	$f[]="##   1 - YUV";
	$f[]="## If different than RGB, it adds extra processing due to conversion.";
	$f[]="## By itself doesn't change much the output data size, and the";
	$f[]="## conversion is not 100.0% lossless.";
	$f[]="## If you plan using JP2CSampling* or JP2BitRes* options, a non-RGB";
	$f[]="## color model is highly prefereable.";
	$f[]="## Default: 0 (YUV)";
	$f[]="## Note: certain jp2-aware software do NOT support a color model";
	$f[]="##       other than RGB and will either fail or display a distorted image.";
	$f[]="# JP2Colorspace = 1";
	$f[]="";
	$f[]="## Upsampler to be used while resampling each component of a JP2K picture.";
	$f[]="## This is used ONLY when decompressing JP2K pictures, it does not affect";
	$f[]="## JP2K compression at all (that uses a downsampler, which is linear-only).";
	$f[]="## Accepted values:";
	$f[]="##   0 - Linear";
	$f[]="##   1 - Lanczos (Lanczos3)";
	$f[]="## For modest scaling such as 2:1, linear is usually better,";
	$f[]="## resulting in a overall clear component.";
	$f[]="## Lanczos may be interesting when scaling 4:1 or more, though";
	$f[]="## it tends to sharpen the JP2K artifacts and add harmonic";
	$f[]="## interference to the component.";
	$f[]="## Default: 0 (Linear)";
	$f[]="# JP2Upsampler = 0";
	$f[]="";
	$f[]="## This applies to B&W pictures compressed to JP2K.";
	$f[]="## Defines the channel resolution for each component:";
	$f[]="## Y (luma) and A (alpha, if present)";
	$f[]="## in number of bit (min: 1, max: 8)";
	$f[]="## Defines for each file size (see JP2ImageQuality).";
	$f[]="## Smallest image is the first components in array.";
	$f[]="## Sequence is YAYAYAYA.";
	$f[]="##";
	$f[]="## Default: all to eight bits";
	$f[]="#JP2BitResYA = {6,4,";
	$f[]="#               7,5,";
	$f[]="#               8,6,";
	$f[]="#               8,6}";
	$f[]="";
	$f[]="## This applies to color pictures compressed to JP2K";
	$f[]="## using the RGB model (see JP2Colorspace).";
	$f[]="## Defines the channel resolution for each component:";
	$f[]="## R (red), G (green), B (blue) and A (alpha, if present)";
	$f[]="## in number of bit (min: 1, max: 8)";
	$f[]="## Defines for each file size (see JP2ImageQuality).";
	$f[]="## Smallest image is the first components in array.";
	$f[]="## Sequence is RGBARGBARGBARGBA.";
	$f[]="##";
	$f[]="## Default: all to eight bits";
	$f[]="# JP2BitResRGBA = {6,5,5,4,";
	$f[]="#                  7,6,6,5,";
	$f[]="#                  6,7,7,6,";
	$f[]="#                  8,8,8,6}";
	$f[]="";
	$f[]="## This applies to color pictures compressed to JP2K";
	$f[]="## using the YUV color model (see JP2Colorspace).";
	$f[]="## Defines the channel resolution for each component:";
	$f[]="## Y (luma), U (chroma, Cb), V (chroma, Cr), and A (alpha, if present)";
	$f[]="## in number of bit (min: 1, max: 8)";
	$f[]="## Defines for each file size (see JP2ImageQuality).";
	$f[]="## Smallest image is the first components in array.";
	$f[]="## Sequence is YUVAYUVAYUVAYUVA.";
	$f[]="##";
	$f[]="## Default: sensible values for best quality/compression";
	$f[]="#JP2BitResYUVA = {6,5,5,4,";
	$f[]="#                 7,6,6,5,";
	$f[]="#                 8,7,7,6,";
	$f[]="#                 8,8,8,6}";
	$f[]="";
	$f[]="## This applies to B&W pictures compressed to JP2K.";
	$f[]="## Here you may define the sampling rate for each component,";
	$f[]="## for each picture size.";
	$f[]="## The sequence is:";
	$f[]="## Y_xpos, Y_ypos, Y_xstep, Y_ystep,  A_xpos, A_ypos, A_xstep, A_ystep, (smallest picture)";
	$f[]="## ... ... ... (medium-sized picture)";
	$f[]="## etc.";
	$f[]="## Default: all x/ypos=0 x/ystep=1 (no components suffer subsampling)";
	$f[]="## Note: certain jp2-aware software do NOT support component subsampling and will fail.";
	$f[]="#JP2CSamplingYA = {0,0,1,1, 0,0,1,1,";
	$f[]="#                  0,0,1,1, 0,0,1,1,";
	$f[]="#                  0,0,1,1, 0,0,2,2,";
	$f[]="#                  0,0,1,1, 0,0,2,2}";
	$f[]="";
	$f[]="## This applies to color pictures compressed to JP2K";
	$f[]="## using the RGB model (see JP2Colorspace).";
	$f[]="## Here you may define the sampling rate for each component,";
	$f[]="## for each picture size.";
	$f[]="## The sequence is:";
	$f[]="## R_xpos, R_ypos, R_xstep, R_ystep,  G_xpos, G_ypos, G_xstep, G_ystep,  B...  A... (smallest picture)";
	$f[]="## ... ... ... (medium-sized picture)";
	$f[]="## etc.";
	$f[]="## Default: all x/ypos=0 x/ystep=1 (no components suffer subsampling)";
	$f[]="## Note: certain jp2-aware software do NOT support component subsampling and will fail.";
	$f[]="#JP2CSamplingRGBA = {0,0,1,1, 0,0,1,1, 0,0,1,1, 0,0,1,1,";
	$f[]="#                    0,0,1,1, 0,0,1,1, 0,0,1,1, 0,0,1,1,";
	$f[]="#                    0,0,1,1, 0,0,1,1, 0,0,1,1, 0,0,1,1,";
	$f[]="#                    0,0,1,1, 0,0,1,1, 0,0,1,1, 0,0,1,1}";
	$f[]="";
	$f[]="## This applies to color pictures compressed to JP2K";
	$f[]="## using the YUV color model (see JP2Colorspace).";
	$f[]="## Here you may define the sampling rate for each component,";
	$f[]="## for each picture size.";
	$f[]="## The sequence is:";
	$f[]="## Y_xpos, Y_ypos, Y_xstep, Y_ystep,  U_xpos, U_ypos, U_xstep, U_ystep,  V...  A... (smallest picture)";
	$f[]="## ... ... ... (medium-sized picture)";
	$f[]="## etc.";
	$f[]="## Default: sensible values for a good image quality.";
	$f[]="## Note: certain jp2-aware software do NOT support component subsampling and will fail.";
	$f[]="#JP2CSamplingYUVA = {0,0,1,1, 0,0,1,1, 0,0,1,1, 0,0,1,1,";
	$f[]="#                    0,0,1,1, 0,0,1,2, 0,0,2,1, 0,0,1,1,";
	$f[]="#                    0,0,1,1, 0,0,2,2, 0,0,2,1, 0,0,2,2,";
	$f[]="#                    0,0,1,1, 0,0,2,2, 0,0,2,2, 0,0,2,2}";
	$f[]="";

	CheckFilesAndSecurity();

	@file_put_contents("/etc/squid3/ziproxy.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} /etc/squid3/ziproxy.conf done\n";}
	system("/usr/sbin/artica-phpfpm-service -proxy-parents");
	cluster_mode();
}


function zipproxy_global($nopid=false){

	$unix=new unix();
	$ziproxylogtool=$unix->find_program("ziproxylogtool");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$logfile="/var/log/squid/access-ziproxy.log";

	if($GLOBALS["VERBOSE"]){echo "$pidTime\n";}

	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());

	$AccessTime=$unix->file_time_min($pidTime);



	$cmd="$ziproxylogtool -m g -i /var/log/squid/access-ziproxy.log  -o $pidTime";
	shell_exec($cmd);
	$f=explode("\n",@file_get_contents($pidTime));
    foreach ($f as $val){
		if(preg_match("#Total accesses.*?:\s+([0-9]+)#", $val,$re)){
			$total_access=$re[1];
			continue;
		}

		if(preg_match("#Total incoming.*?:\s+([0-9]+)#", $val,$re)){
			$total_incoming=$re[1];
			continue;
		}
		if(preg_match("#Total outgoing.*?:\s+([0-9]+)#", $val,$re)){
			$total_outgoing=$re[1];
			continue;
		}
	}

	if(!$GLOBALS["VERBOSE"]){ if($AccessTime<10){return;} }

	$array["incoming"]=$total_incoming;
	$array["outgoing"]=$total_outgoing;
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/zipproxy_stats.db", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/zipproxy_stats.db",0755);
}
function zipproxy_access($nopid=false){

	$unix=new unix();
	$ziproxylogtool=$unix->find_program("ziproxylogtool");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$logfile="/var/log/squid/access-ziproxy.log";

	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());

	$AccessTime=$unix->file_time_min($pidTime);

	if(!$GLOBALS["VERBOSE"]){ if($AccessTime<60){return;} }

	@unlink($pidTime);
	@file_put_contents($pidTime, time());

	if($GLOBALS["VERBOSE"]){ echo "PID TIME FILE: $pidTime\n"; }

	$lasthour = date("Y-m-d H:00:00", time() - 3600);
	$ThisHour  = date("Y-m-d H:00:00", time());
	$xtime=strtotime($lasthour);
	$xtime2=strtotime($ThisHour);
	echo "$lasthour $xtime -> $ThisHour $xtime2\n";
	$cmd="$ziproxylogtool -m g -i /var/log/squid/access-ziproxy.log  -1 $xtime -2 $xtime2 -o $pidTime";
	shell_exec($cmd);
	$f=explode("\n",@file_get_contents($pidTime));
    foreach ($f as $val){
		if(preg_match("#Total accesses.*?:\s+([0-9]+)#", $val,$re)){
			$total_access=$re[1];
			continue;
		}

		if(preg_match("#Total incoming.*?:\s+([0-9]+)#", $val,$re)){
			$total_incoming=$re[1];
			continue;
		}
		if(preg_match("#Total outgoing.*?:\s+([0-9]+)#", $val,$re)){
			$total_outgoing=$re[1];
			continue;
		}
	}


	$percent=round(($total_outgoing/$total_incoming)*100);

	$md5=md5("$xtime$xtime2");
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `squidlogs`.`ziproxy_stats` (
				`zMd5` VARCHAR( 255 ) NOT NULL,
				`zDate` DATETIME NOT NULL,
				`access` INT UNSIGNED ,
				`incoming` BIGINT UNSIGNED,
				`outgoing` BIGINT UNSIGNED,
				`percent` smallint not null,
				 PRIMARY KEY ( `zMd5` ),
				 KEY `zDate`(`zDate`),
				 KEY `incoming`(`incoming`),
				 KEY `percent`(`percent`)
				) ENGINE=MYISAM;");

	if(!$q->ok){echo $q->mysql_error;}

	$sql="INSERT IGNORE INTO `ziproxy_stats` (zDate,access,incoming,outgoing,percent )
	VALUES ('$lasthour','$total_access','$total_incoming','$total_outgoing','$percent')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}


}
function zipproxy_rotate(){
	$filename="/var/log/squid/access-ziproxy.log";
	$sock=new sockets();
	$unix=new unix();
	$getmypid=getmypid();
	$LastRotate=$unix->file_time_min("/etc/artica-postfix/pids/ziproxy-rotate-cache.time");
	@unlink("/etc/artica-postfix/pids/ziproxy-rotate-cache.time");
	@file_put_contents("/etc/artica-postfix/pids/ziproxy-rotate-cache.time", time());
	zipproxy_access();

	$size=@filesize($filename);
	$size=$size/1024;
	$size=round($size/1024);
	if(!@copy($filename, "/home/squid/zipproxy_logs/".basename($filename).".".time())){
		squid_admin_mysql(1, "Rotate HTTP Compressor logs failed", "",__FILE__,__LINE__);
		return;
	}
	@unlink($filename);
	reload(true);

	if($GLOBALS["VERBOSE"]){echo date("H:i:s")."[$getmypid] Ask proxy to rotate logs....\n";}
	squid_admin_mysql(2, "Rotate HTTP Compressor logs ({$size}MB) - last task was executed since {$LastRotate}Mn", "",__FILE__,__LINE__);

}


function zipproxy_templates(){
	return @file_get_contents("/usr/share/artica-postfix/ressources/databases/zipproxy_tpls.db");
	
	
}


?>