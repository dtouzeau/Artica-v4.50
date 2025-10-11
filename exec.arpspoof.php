<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FLUSH"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--flush#",implode(" ",$argv))){$GLOBALS["FLUSH"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.dhcpd.inc');
include_once(dirname(__FILE__) . '/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--status"){status($argv[2]);exit;}

start();



function restart(){
	$users=new usersMenus();
	$unix=new unix();
	if(!$users->ETTERCAP_INSTALLED){echo "ArpSpoofing.........: [STOP]: Ettercap, not installed...\n";return;}

	$me=basename(__FILE__);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,$me)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		echo "ArpSpoofing.........: [RESTART]: Ettercap, Already start instance executed PID $pid since {$time}Mn...\n";
		return;
	}
	@file_put_contents($pidfile, getmypid());

	
	stop(true);
	start(true);
	
}

function ettercap_version(){
	$unix=new unix();
	$ettercap_bin=$unix->find_program("ettercap");
	if(!is_file($ettercap_bin)){return;}
	if(is_file("/etc/artica-postfix/ETTERCAP_VERSION")){
		if($unix->file_time_min("/etc/artica-postfix/ETTERCAP_VERSION")<360){return @file_get_contents("/etc/artica-postfix/ETTERCAP_VERSION");}
	}
	@unlink("/etc/artica-postfix/ETTERCAP_VERSION");
	exec("$ettercap_bin -v 2>&1",$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#ettercap\s+([A-Z0-9\.\-]+)#", $ligne,$re)){
			@file_put_contents("/etc/artica-postfix/ETTERCAP_VERSION", $re[1]);
			return $re[1];}
	}
	
}

function status($pid){
	$unix=new unix();
	$sock=new sockets();
	$ArpSpoofEnabled=$sock->GET_INFO("ArpSpoofEnabled");
	if(!is_numeric($ArpSpoofEnabled)){$ArpSpoofEnabled=0;}
	$l[]="[APP_ARPSOOF]";
	$l[]="service_name=APP_ARPSOOF";
	$l[]="master_version=".ettercap_version("dansguardian");
	$l[]="service_cmd=dansguardian";
	$l[]="service_disabled=$ArpSpoofEnabled";
	$l[]="family=network";
	
	
	if($ArpSpoofEnabled==0){return implode("\n",$l);return;}
	
	$master_pid=get_rule_pid($pid);
	
	if(!$unix->process_exists($master_pid)){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
	
	}
	
	
	
	$l[]="running=1";
	$l[]=$unix->GetMemoriesOf($master_pid);
	$l[]="";
	echo implode("\n",$l);return;	
}

function stop($nopid=false){
	$users=new usersMenus();
	$unix=new unix();
	if(!$users->ETTERCAP_INSTALLED){echo "ArpSpoofing.........: [STOP]: Ettercap, not installed...\n";return;}
	$kill=$unix->find_program("kill");
	
	if(!$nopid){
		$me=basename(__FILE__);
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,$me)){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			echo "ArpSpoofing.........: [START]: Ettercap, Already start instance executed PID $pid since {$time}Mn...\n";
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	
  $pids=GetAllPids();

	
	if(count($pids)==0){
		echo "ArpSpoofing.........: [STOP]: Ettercap, no instance in memory\n";
		return;
	}
	
	while (list ($pid, $ruleid) = each ($pids) ){
		echo "ArpSpoofing.........: [STOP]: stopping smoothly pid $pid for rule $ruleid\n";
		unix_system_HUP($pid);
		unix_system_kill($pid);
		
	}
	
	for($i=0;$i<10;$i++){
		$pids=GetAllPids();
		while (list ($pid, $ruleid) = each ($pids) ){
			if(!$unix->process_exists($pid)){
				echo "ArpSpoofing.........: [STOP]: pid $pid for rule $ruleid stopped...\n";
				unset($pids[$pid]);
				continue;
			}
			
			echo "ArpSpoofing.........: [STOP]: pid $pid for rule $ruleid still alive...\n";
		}
		if(count($pids)==0){break;}
		sleep(1);
	}
	
	$pids=GetAllPids();
	if(count($pids)>0){
		reset($pids);
		while (list ($pid, $ruleid) = each ($pids) ){
			echo "ArpSpoofing.........: [STOP]: pid $pid for rule $ruleid force stopping...\n";
			unix_system_kill_force($pid);
		}
		
	}
	
	$pids=GetAllPids();
	if(count($pids)>0){
		reset($pids);
		while (list ($pid, $ruleid) = each ($pids) ){
			echo "ArpSpoofing.........: [STOP]: pid $pid for rule $ruleid failed stopping...\n";
		}
	
	}	
	
	
	echo "ArpSpoofing.........: [STOP]: DONE...\n";
}

function GetAllPids(){
	$unix=new unix();
	$pids=array();
	$pgrep=$unix->find_program("pgrep");
	
	$cmdline="$pgrep -l -f \"ettercap.*?\/etc\/ettercap\/[0-9]+\.log\" 2>&1";
	
	exec($cmdline,$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(preg_match("#^([0-9]+)\s+.*?etc\/ettercap\/([0-9]+)\.log#", $ligne,$re)){
			$pids[$re[1]]=$re[2];
		}
	}

	return $pids;
}

function start($nopid=false){
	$users=new usersMenus();
	$unix=new unix();
	$ettercap_bin=$unix->find_program("ettercap");
	if(!$users->ETTERCAP_INSTALLED){
		echo "ArpSpoofing.........: [START]: Ettercap, not installed...\n";
		return;
	}
	
	if(!is_file($ettercap_bin)){echo "ArpSpoofing.........: [START]: Ettercap, not such binary...\n";return;}
	
	$sock=new sockets();
	$ArpSpoofEnabled=$sock->GET_INFO("ArpSpoofEnabled");
	if(!is_numeric($ArpSpoofEnabled)){$ArpSpoofEnabled=0;}	

	
	if(!$nopid){
		$me=basename(__FILE__);
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,$me)){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			echo "ArpSpoofing.........: [START]: Ettercap, Already start instance executed PID $pid since {$time}Mn...\n";
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	if($ArpSpoofEnabled==0){
		echo "ArpSpoofing.........: [START]: Ettercap, not enabled\n";
		stop(true);
	}


	
	$nohup=$unix->find_program("nohup");
	$q=new mysql();
	if(!$q->BD_CONNECT()){
		echo "ArpSpoofing.........: [START]: unable to connect to MySQL database...\n";
		return;
	}
	
	if(!$unix->SystemUserExists("nobody")){
		echo "ArpSpoofing.........: [START]: Creating nobody user...\n";
		$unix->CreateUnixUser("nobody","nogroup");
	}
	
	if(!$unix->SystemGroupExists("nogroup")){
		$unix->SystemCreateGroup("nogroup");
		$unix->CreateUnixUser("nobody","nogroup");
	}
	
	$uid=$unix->SystemUserGetuid("nobody");
	$guid=$unix->SystemGroupUid("nogroup");
	
	init_debian();
	$sql="SELECT * FROM arpspoof_rules WHERE enabled=1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$rulename=$ligne["rulename"];
		$pid=get_rule_pid($ligne["ID"]);
		if($unix->process_exists($pid)){echo "ArpSpoofing.........: [START]: `$rulename` already running pid $pid\n";continue;}
		$sources=getobjects($ligne["ID"]);
		$gateway=$ligne["gateway"];
		if($sources==null){echo "ArpSpoofing.........: [START]: `$rulename` no item set...\n";continue;}
		if($sources=="all"){$sources=null;}
		$f=array();
		$f[]="[privs]";
		$f[]="ec_uid = $uid # nobody is the default";
		$f[]="ec_gid = $guid # nobody is the default";
		$f[]="[mitm]";
		$f[]="arp_storm_delay = 10";
		$f[]="arp_poison_warm_up = 1";
		$f[]="arp_poison_delay = 10";
		$f[]="arp_poison_equal_mac= 1";
		$f[]="arp_poison_reply=1";
		$f[]="arp_poison_icmp = 1";
		$f[]="dhcp_lease_time = 600";
		$f[]="port_steal_delay = 10         # milliseconds";
		$f[]="port_steal_send_delay = 2000  # microseconds";
		$f[]="[connections]";
		$f[]="connection_timeout = 300 # seconds";
		$f[]="connection_idle = 5 # seconds";
		$f[]="connection_buffer = 10000 # bytes";
		$f[]="connect_timeout = 5 # seconds";
		$f[]="";
		$f[]="[stats]";
		$f[]="sampling_rate = 50 # number of packets";
		$f[]="";
		$f[]="[misc]";
		$f[]="close_on_eof = 1 # boolean value";
		$f[]="store_profiles = 1 # 0 = disabled; 1 = all; 2 = local; 3 = remote";
		$f[]="aggressive_dissectors = 1 # boolean value";
		$f[]="skip_forwarded_pcks = 1 # boolean value";
		$f[]="checksum_check = 0 # boolean value";
		$f[]="checksum_warning = 0 # boolean value (valid only if checksum_check is 1)";
		$f[]="";	
		@mkdir("/etc/ettercap",0755,true);
		@file_put_contents("/etc/ettercap/{$ligne["ID"]}.conf", @implode("\n", $f));
		$unix->chown_func("nobody", "nogroup",'/etc/ettercap/*');
		
	
		echo "ArpSpoofing.........: [START]: `$rulename`:uid:$uid...\n";
		$cmdline="$nohup $ettercap_bin --daemon --superquiet --config /etc/ettercap/{$ligne["ID"]}.conf --log-msg /etc/ettercap/{$ligne["ID"]}.log --iface {$ligne["iface"]} --only-mitm --mitm arp:remote /$sources/ /$gateway/ >/dev/null 2>&1 &";
		shell_exec($cmdline);
		for($i=0;$i<6;$i++){
			$pid=get_rule_pid($ligne["ID"]);
			if($unix->process_exists($pid)){echo "ArpSpoofing.........: [START]: `$rulename` success running pid $pid\n";break;}
			echo "ArpSpoofing.........: [START]: `$rulename` waiting to start... $i/5\n";
			sleep(1);
		
		}
		
		$pid=get_rule_pid($ligne["ID"]);
		if(!$unix->process_exists($pid)){echo "ArpSpoofing.........: [START]: `$rulename` failed with commandline:`$cmdline`\n";}
		
	
	}
	echo "ArpSpoofing.........: [START]: done...\n";
	
}


function get_rule_pid($ID){
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	$cmdline="$pgrep -l -f \"ettercap.*?\/etc\/ettercap\/$ID\.log\" 2>&1";
	if($GLOBALS["VERBOSE"]){echo "ArpSpoofing.........: [START]: $cmdline\n";}
	exec("$pgrep -l -f \"ettercap.*?\/etc\/ettercap\/$ID\.log\" 2>&1",$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#pgrep#", $ligne)){
			if($GLOBALS["VERBOSE"]){echo "ArpSpoofing.........: [START]: pgrep -> `$ligne` skipped\n";}
			continue;
		}
		if(preg_match("#^([0-9]+)\s+#", $ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo "ArpSpoofing.........: [START]: pgrep -> `{$re[1]}` ...\n";}
			return $re[1];
		}
		if($GLOBALS["VERBOSE"]){echo "ArpSpoofing.........: [START]: pgrep -> `$ligne` skipped\n";}
		
	}
	
}

function getobjects($ID){
	$q=new mysql();
	$ipaddr=array();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT ipaddr FROM arpspoof_objects WHERE ID=$ID AND ipaddr='*'","artica_backup"));
	if($ligne["ipaddr"]=="*"){return "all";}
	
	$results2 = $q->QUERY_SQL("SELECT ipaddr FROM arpspoof_objects WHERE ruleid=$ID","artica_backup");
	if(!$q->ok){return null;}
	while ($ligne2 = mysqli_fetch_assoc($results2)) {$ipaddr[]=$ligne2["ipaddr"];}
	return @implode(";", $ipaddr);
}

function init_debian(){
	$unix=new unix();
	$users=new usersMenus();
	$sock=new sockets();
	$php5=$unix->LOCATE_PHP5_BIN();
	$ettercap_bin=$unix->find_program("ettercap");
	$servicebin=$unix->find_program("update-rc.d");
	if(!$users->ETTERCAP_INSTALLED){
		echo "ArpSpoofing.........: [INIT]: Ettercap, not installed...\n";
		return;
	}
	
	if(!is_file($ettercap_bin)){echo "ArpSpoofing.........: [INIT]: Ettercap, not such binary...\n";return;}
	if(!is_file($servicebin)){echo "ArpSpoofing.........: [INIT]: only debian style supported\n";return;}
	
	$ArpSpoofEnabled=$sock->GET_INFO("ArpSpoofEnabled");
	if(!is_numeric($ArpSpoofEnabled)){$ArpSpoofEnabled=0;}
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          arpspoof";
	$f[]="# Required-Start:    \$network \$local_fs";
	$f[]="# Required-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: arpspoof allows to spoof MAC addresses";
	$f[]="# Description:       arpspoof allows to spoof MAC addresses";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="";
	$f[]="# PATH should only include /usr/ if it runs after the mountnfs.sh script";
	$f[]="PATH=/sbin:/usr/sbin:/bin:/usr/bin";
	$f[]="DESC=arpspoof             # Introduce a short description here";
	$f[]="NAME=arpspoof             # Introduce the short server's name here";
	$f[]="DAEMON=\"$ettercap_bin\" # Introduce the server's location here";
	$f[]="DAEMON_ARGS=\"\"             # Arguments to run the daemon with";
	$f[]="ENABLED=\"$ArpSpoofEnabled\"";
	$f[]="";
	$f[]="# Exit if the package is not installed";
	$f[]="[ -x \$DAEMON ] || exit 0";
	$f[]="";
	$f[]="#";
	$f[]="# Function that starts the daemon/service";
	$f[]="#";
	$f[]="do_start(){";
	$f[]="	if [ \$ENABLED -eq 0 ]";
	$f[]="  	then";
	$f[]="   		$php5 /usr/share/artica-postfix/exec.arpspoof.php --stop";
	$f[]="  		return 0";
	$f[]="  	fi";	
	
	$f[]="	if [ \$ENABLED -eq 1 ]";
	$f[]="  	then";
	$f[]="   		$php5 /usr/share/artica-postfix/exec.arpspoof.php --start";
	$f[]="  	fi";
	$f[]="  return 0";
	$f[]="}";
	$f[]="";
	$f[]="#";
	$f[]="# Function that stops the daemon/service";
	$f[]="#";
	$f[]="do_stop(){";
	$f[]="   $php5 /usr/share/artica-postfix/exec.arpspoof.php --stop";
	$f[]="   return 0";
	$f[]="}";
	$f[]="";
	$f[]="#";
	$f[]="# Function that sends a SIGHUP to the daemon/service";
	$f[]="#";
	$f[]="do_reload() {";
	$f[]="   $php5 /usr/share/artica-postfix/exec.arpspoof.php --restart";
	$f[]="    return 0";
	$f[]="}";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]="start)";
	$f[]="        do_start";
	$f[]="    ;;";
	$f[]="stop)";
	$f[]="    do_stop";
	$f[]=";;";
	$f[]="reload|force-reload)";
	$f[]="      do_reload";
	$f[]="      ;;";
	$f[]="restart|force-reload)";
	$f[]="      do_stop";
	$f[]="      do_start";
	$f[]="      ;;";
	$f[]="*)";
	$f[]="    echo \"Usage: \$SCRIPTNAME {start|stop|status|restart|force-reload}\" >&2";
	$f[]="    exit 3";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="";
	$f[]=":";
	$f[]="";

	@file_put_contents("/etc/init.d/arpspoof", @implode("\n", $f));
	@chmod("/etc/init.d/arpspoof", 0755);
	shell_exec("$servicebin -f arpspoof defaults >/dev/null 2>&1");
	

}

