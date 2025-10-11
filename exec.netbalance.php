<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Net Load-Balance Daemon";
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
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--package"){$GLOBALS["OUTPUT"]=true;package();exit();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}


function install(){
	
	build_progress_idb("{creating_service}",25);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NetIPBalanceEnable", 1);
	netbalance_initd();
	build_progress_idb("{restarting_service}",90);
	system("/etc/init.d/netbalance restart");
	build_progress_idb("{restarting_service}",95);
	system("/etc/init.d/artica-status restart");
	build_progress_idb("{done}",100);
	
}
function uninstall(){
	build_progress_idb("{disable_feature}",25);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NetIPBalanceEnable", 0);
	build_progress_idb("{remove_service}",90);
	remove_service("/etc/init.d/netbalance");
	build_progress_idb("{restarting_service}",95);
	system("/etc/init.d/artica-status restart");
	build_progress_idb("{done}",100);
}
function build_progress_idb($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/netbalance.install.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}
function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		build_progress_reload("{failed}",110);
		
		return;
	}
	@file_put_contents($pidfile, getmypid());
	build_progress_restart("{stopping_service}",20);
	stop(true);
	sleep(1);
	build_progress_restart("{reconfiguring}",25);
	build();
	
	build_progress_restart("{starting_service}",55);
	if(!start(true)){
		build_progress_restart("{starting_service} {failed}",110);
		return;
	}
	build_progress_restart("{starting_service} {success}",100);

}

function build_progress_reload($text,$pourc){
	if($GLOBALS["VERBOSE"]){echo "{$pourc}% $text\n";}
	if($GLOBALS["OUTPUT"]){echo "Progress......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, {$pourc}% $text\n";}
	$cachefile="/usr/share/artica-postfix/ressources/logs/netdata.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function build_progress_restart($text,$pourc){
	if($GLOBALS["VERBOSE"]){echo "{$pourc}% $text\n";}
	if($GLOBALS["OUTPUT"]){echo "Progress......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, {$pourc}% $text\n";}
	$cachefile="/usr/share/artica-postfix/ressources/logs/netbalance.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function netbalance_initd(){
	echo "netbalance: [INFO] Start\n";
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/netbalance";


		$f[]="#!/bin/sh";
		$f[]="### BEGIN INIT INFO";
		$f[]="# Provides:          netbalance";
		$f[]="# Required-Start:    \$local_fs \$syslog";
		$f[]="# Required-Stop:     \$local_fs";
		$f[]="# Should-Start:";
		$f[]="# Should-Stop:";
		$f[]="# Default-Start:     3 4 5";
		$f[]="# Default-Stop:      0 1 6";
		$f[]="# Short-Description: netbalance daemon";
		$f[]="# chkconfig: 2345 11 89";
		$f[]="# description: netbalance interface";
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
		$f[]="   $php ".__FILE__." --restart \$2 \$3";
		$f[]="	 exit 0";
		$f[]="    ;;";
		$f[]="";
		$f[]="  *)";
		$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
		$f[]="    exit 1";
		$f[]="    ;;";
		$f[]="esac";
		$f[]="exit 0\n";


		echo "NETDATA: [INFO] Writing $INITD_PATH with new config\n";
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

function build(){
	$f[]="## Net::ISP::Balance configuration file";
	$f[]="## edit it as needed to describe your router setup";
	$f[]="";
	$f[]="## This table defines the LAN and IP services.";
	$f[]="## Uncomment by removing hash symbols (#) and then edit as needed";
	$f[]="";
	$f[]="## service    device   role     ping-ip            weight";
	$f[]="#CABLE	      eth0     isp      173.194.43.95      1";
	$f[]="#DSL	      ppp0     isp      173.194.43.95      1";
	$f[]="#LAN1	      eth1     lan      ";
	$f[]="#LAN2	      eth2     lan      ";
	
	$MAIN=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIPBalanceInterfaces"));
	
	$ROLES[0]="isp";
	$ROLES[1]="lan";
	
	while (list ($interface, $subarray) = each ($MAIN) ){
		$tr[]="$interface - skipped";
		if(trim($interface)==null){continue;}
		$weight=intval($subarray["WEIGHT"]);
		if($weight==0){$weight=1;}
		$role=$subarray["ROLE"];
		$ping=$subarray["PING"];
		$NAME=strtoupper($ROLES[$role]."".$interface);
		$f[]="$NAME	      $interface     {$ROLES[$role]}      {$ping}      $weight";
	}
	
	$f[]="";
	$f[]="## The \"forwarding_group\" option gives you fine control over how";
	$f[]="## packets are forwarded.  See the online docs for details.  :lan";
	$f[]="## means all interfaces marked as \"lan\" :isp means all interfaces";
	$f[]="## marked as \"isp\" the default (shown below) allows forwarding among";
	$f[]="## all lan and isp interfaces";
	$f[]="";
	$f[]="#forwarding_group=:lan :isp";
	$f[]="";
	$f[]="## The \"mode\" option, if present, selects which mode Net-ISP-Balance runs";
	$f[]="## in. The choices are \"balanced\" and \"failover\". ";
	$f[]="##";
	$f[]="## In \"balanced\" mode (the default) each interface marked as an ISP";
	$f[]="## will be used to balance outgoing and incoming packets. If one goes";
	$f[]="## down, the other(s) will be used as failover services.  The \"weight\"";
	$f[]="## column in the table above is used to prioritize how packets are";
	$f[]="## balanced across the (running) interfaces.";
	$f[]="## ";
	$f[]="## In \"failover\" mode, only one ISP will be used at a time. The others";
	$f[]="## will be used as backups if the primary interface fails. In this case";
	$f[]="## the weight is used to select which interface is currently active, with";
	$f[]="## the currently running interface with the highest weight being selected.";
	$f[]="";
	$f[]="#mode=balanced";
	$f[]="";
	$f[]="## These options are passed to lsm, among others.";
	$f[]="## the defaults are shown. To change them, uncomment";
	$f[]="## and edit.";
	$f[]="";
	$f[]="#warn_email=root@localhost";
	$f[]="#interval_ms=1000";
	$f[]="#max_packet_loss=15";
	$f[]="#max_successive_pkts_lost=7";
	$f[]="#min_packet_loss=5";
	$f[]="#min_successive_pkts_rcvd=10";
	$f[]="#long_down_time=120";
	$f[]="";
	
	@mkdir("/etc/network",0755,true);
	@file_put_contents("/etc/network/balance.conf", @implode("\n", $f));
	
}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin="/usr/local/bin/load_balance.pl";

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, netdata not installed\n";}
		build_progress_reload("{starting_service} {failed}",110);
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			build_progress_reload("{starting_service} {failed}",110);
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return true;
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/NetIPBalanceInterfaces")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not configured.\n";}
		build_progress_reload("{starting_service} {failed}",90);
		return false;
	}
	if(!is_file("/etc/artica-postfix/settings/Daemons/NetDataListenPort")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NetDataListenPort", 19999);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/NetDataHistory")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NetDataHistory", 3600);}
	
	
	$EnableDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetIPBalanceEnable"));


	if($EnableDaemon==0){
		build_progress_reload("{starting_service} {disabled}",90);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see NetIPBalanceEnable)\n";}
		return true;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	@mkdir("/var/lib/lsm",0755,true);
	if(is_file("/etc/network/load_balance.pl")){@unlink("/etc/network/load_balance.pl");}
	@copy($Masterbin, "/etc/network/load_balance.pl");
	@chmod("/etc/network/load_balance.pl", 0755);
	@chmod($Masterbin, 0755);
	
	$cmd="$nohup $Masterbin >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	build_progress_reload("{starting_service} {run}",90);
	shell_exec($cmd);




	for($i=1;$i<5;$i++){
		build_progress_reload("{starting_service} {waiting} $i/5",95);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		build_progress_reload("{starting_service} {sucess}",99);
		return true;

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		build_progress_reload("{starting_service} {failed}",110);
		return false;
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
		build_progress_reload("{stopping_service}",50);
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
		build_progress_reload("{stopping_service} $i/5",25);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		build_progress_reload("{stopping_service}",50);
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		build_progress_reload("{stopping_service} (2) $i/5",30);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		build_progress_reload("{stopping_service} (3) {failed}",50);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}
function PID_NUM(){

	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/lsm.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin="/usr/local/bin/lsm";
	return $unix->PIDOF($Masterbin);

}

function buildconf(){
	
	
	
}

