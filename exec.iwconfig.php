<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="WIFI Daemon";
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
if(isset($argv[1])) {
    if ($argv[1] == "--stop") {
        $GLOBALS["OUTPUT"] = true;
        stop($argv[2]);
        exit();
    }
    if ($argv[1] == "--start") {
        $GLOBALS["OUTPUT"] = true;
        start($argv[2]);
        exit();
    }
    if ($argv[1] == "--connect") {
        $GLOBALS["OUTPUT"] = true;
        connect($argv[2], $argv[3]);
        exit();
    }
    if ($argv[1] == "--install") {
        $GLOBALS["OUTPUT"] = true;
        install();
        exit();
    }
    if ($argv[1] == "--uninstall") {
        $GLOBALS["OUTPUT"] = true;
        uninstall();
        exit();
    }
    if ($argv[1] == "--uninstall-services") {
        $GLOBALS["OUTPUT"] = true;
        uninstall_services();
        exit();
    }
    if ($argv[1] == "--restart") {
        $GLOBALS["OUTPUT"] = true;
        restart($argv[2]);
        exit();
    }

    if(isset($argv[2])){

        if($argv[2]=="DISCONNECTED"){
            notify_interface($argv[1],$argv[2]);
            exit;
        }
        if($argv[2]=="CONNECTED"){
            notify_interface($argv[1],$argv[2]);
            exit;
        }
    }

    $unix = new unix();
    $unix->ToSyslog("Unable to determine '{$GLOBALS["ARGVS"]}' command", false, basename(__FILE__));
}

function notify_interface($interface,$status){
    $unix=new unix();
    $unix->ToSyslog("WLAN $interface entered in mode <$status>");
    squid_admin_mysql(0,"Interface $interface entered in mode $status",null,__FILE__,__LINE__);
}

function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/iwlwifi.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_connect($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/iwconf-ap.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function install(){
	$unix=new unix();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableIwConfig",1);
	build_progress(50, "{installing}");
	run_wireless();
	
	build_progress(60, "{installing}");
	$unix->Popuplate_cron_make("artica-iwlist", "0,5,10,15,20,25,30,35,40,45,50,55 * * * *","exec.wifi.detect.cards.php --iwlist");
	UNIX_RESTART_CRON();
	build_progress(100, "{installing} {success}");
	
}

function restart($nic){
	
	if(stop($nic)){start($nic);}
	
}

function uninstall(){
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableIwConfig",0);
	build_progress(50, "{uninstalling}");
	uninstall_services();
	build_progress(70, "{uninstalling}");
	stop_wireless();
	build_progress(80, "{uninstalling}");
	if(is_file("/etc/cron.d/artica-iwlist")){@unlink("/etc/cron.d/artica-iwlist");UNIX_RESTART_CRON();}
	
	build_progress(80, "{reconfiguring}");
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.virtuals-ip.php --build");
	build_progress(100, "{uninstalling} {success}");
}

function uninstall_services(){
	$unix=new unix();
	$files=$unix->DirFiles("/etc/init.d","wifi-wlan");
	while (list ($filename, $val) = each ($files) ){
		$initpath="/etc/init.d/$filename";
		remove_service($initpath);
	}
	
	$files=$unix->DirFiles("/etc/monit/conf.d","APP_WPA_SUPPLIANT_");
	while (list ($filename, $val) = each ($files) ){
		$initpath="/etc/monit/conf.d/$filename";
		@unlink($initpath);
	}
	
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function stop_wireless(){
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	$iwconfig=$unix->find_program("iwconfig");

	exec("$iwconfig --version 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#^(wlan|wlp)([0-9]+)#", $line,$re)){
			$interface="$re[1]$re[2]";
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $interface\n";}
			shell_exec("$ifconfig $interface down");
		}
	}
}

function run_wireless(){
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	$iwconfig=$unix->find_program("iwconfig");
	$php=$unix->LOCATE_PHP5_BIN();
	$FOUND=false;
	
	exec("$iwconfig --version 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#^(wlan|wlp)([0-9]+)#", $line,$re)){
			$interface="$re[1]$re[2]";
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $interface\n";}
			shell_exec("$ifconfig $interface up");
			$FOUND=true;
		}
	}
	if($FOUND){
		system("$php /usr/share/artica-postfix/exec.wifi.detect.cards.php --iwlist");
	}
}

function connect($MACADDR,$nic){
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	$iwconfig=$unix->find_program("iwconfig");
	$wpa_cli=$unix->find_program("wpa_cli");
	$php=$unix->LOCATE_PHP5_BIN();
	$WifiAccessPoint=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WifiAccessPoint")));
	$CONFIG=$WifiAccessPoint[$MACADDR];
	$ESSID=$CONFIG["ESSID"];
	$WPA2=intval($CONFIG["WPA2"]);
	echo "exec.iwconfig.php --connect $MACADDR $nic\n";
	build_progress_connect(15,"{connecting} $ESSID");

	if(!isset($CONFIG["COUNTRY"])){$CONFIG["COUNTRY"]="US";}

	$PASSWORD=$CONFIG["ESSID_PASSWORD"];
	$COUNTRY=$CONFIG["COUNTRY"];

    $f[]="country=$COUNTRY";
    $f[]="update_config=1";
	$f[]="";
	$f[]="network={";
	$f[]="\tbssid=$MACADDR";
	$f[]="\tssid=\"$ESSID\"";
	$f[]="\tscan_ssid=1";
	if($WPA2==1){
        $f[]="\tproto=RSN";
        $f[]="\tauth_alg=OPEN";
    }
	$f[]="\tkey_mgmt=WPA-PSK";

	if($PASSWORD<>null){
	$f[]="\tpsk=\"$PASSWORD\"";
	}
    $f[]="\tgroup=CCMP TKIP";
    $f[]="\tpairwise=CCMP TKIP";
	$f[]="}";
	
	@mkdir("/etc/wpa_supplicant",0755,true);
	@mkdir("/var/run/wpa_supplicant",0755,true);
	@file_put_contents("/etc/wpa_supplicant/$nic.conf", @implode("\n", $f));
	
	$t[]="#!/bin/sh";
	$t[]="$php ".__FILE__. " $nic \$2 \$3 >/dev/null";
	$t[]="exit 0\n";
	@file_put_contents("/etc/wpa_supplicant/$nic.sh", @implode("\n", $t));
	@chmod("/etc/wpa_supplicant/$nic.sh",0755);
	
	build_progress_connect(15,"{creating_service} $nic $MACADDR $ESSID");
	build_service($nic);
	build_progress_connect(20,"{stopping_service} $nic $MACADDR $ESSID");
	stop($nic);
	build_progress_connect(50,"{starting_service} $nic $MACADDR $ESSID");
	if(!start($nic)){
		build_progress_connect(110,"{starting_service} {failed} $nic $MACADDR $ESSID");
		return;
	}
	build_progress_connect(100,"{starting_service} $nic $MACADDR $ESSID {success}");
}

function build_service($eth){
	$unix=new unix();
	$f[]="check process APP_WPA_SUPPLIANT_$eth with pidfile /var/run/wpa_supplicant/$eth.pid";
	$f[]="\tstart program = \"/etc/init.d/wifi-$eth start --monit\"";
	$f[]="\tstop program = \"/etc/init.d/wifi-$eth stop --monit\"";
	$f[]="\tif 5 restarts within 10 cycles then timeout";
	$f[]="";
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Artica Web Console...\n";}
	@file_put_contents("/etc/monit/conf.d/APP_WPA_SUPPLIANT_$eth.monitrc", @implode("\n", $f));
	$f=array();
	
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/wifi-$eth";
	$php5script=basename(__FILE__);
	$daemonbinLog="$eth WI-FI Daemon";
	
	
	
		$f[]="#!/bin/sh";
		$f[]="### BEGIN INIT INFO";
		$f[]="# Provides:         wifi-$eth";
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
		$f[]="    $php /usr/share/artica-postfix/$php5script --start $eth \$2 \$3";
		$f[]="    ;;";
		$f[]="";
		$f[]="  stop)";
		$f[]="    $php /usr/share/artica-postfix/$php5script --stop $eth \$2 \$3";
		$f[]="    ;;";
		$f[]="";
		$f[]=" restart)";
		$f[]="    $php /usr/share/artica-postfix/$php5script --restart $eth \$2 \$3";
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
	
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
	
}

function GET_PID($nic){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/wpa_supplicant/$nic.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN("wpa_supplicant -i $nic");
}
function CLI_PID($nic){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/wpa_supplicant/client_$nic.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN("wpa_cli -i{$nic}");
}

function stop($nic){
	$unix=new unix();
	$GLOBALS["TITLENAME"]="$nic WI-FI Daemon";
	$pid=GET_PID($nic);
	$wpa_cli=$unix->find_program("wpa_cli");
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic Already stopped\n";}
		return stop_cli($nic);
	}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic $pid\n";}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic disconnecting...\n";}
	shell_exec("$wpa_cli -p/var/run/wpa_supplicant/$nic -i $nic disconnect");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic terminating...\n";}
	shell_exec("$wpa_cli -p/var/run/wpa_supplicant/$nic -i $nic terminate");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic unlinking...\n";}
	$pid=GET_PID($nic);
	
	
	$unix->KILL_PROCESS($pid,15);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic stopped\n";}
		return stop_cli($nic);
	}

	for($i=0;$i<5;$i++){
		$pid=GET_PID($nic);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic $pid waiting...\n";}
		$unix->KILL_PROCESS($pid,15);
		sleep(1);
	}
	
	$pid=GET_PID($nic);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic $pid success...\n";}
		return stop_cli($nic);
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic $pid failed...\n";}
	
}

function stop_cli($nic){
	
	$unix=new unix();
	$GLOBALS["TITLENAME"]="$nic WI-FI Client Daemon";
	
	$pid=CLI_PID($nic);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic Already stopped\n";}
		return true;
	}
	
	$wpa_cli=$unix->find_program("wpa_cli");
	$ip=$unix->find_program("ip");
	if(!is_file($wpa_cli)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic wpa_cli no such binary\n";}
		return;
	}
	
	$pid=CLI_PID($nic);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic wpa_cli stopped\n";}
		return true;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic $pid\n";}
	$unix->KILL_PROCESS($pid,15);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic stopped\n";}
		return true;
	}
	
	for($i=0;$i<5;$i++){
		$pid=CLI_PID($nic);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic $pid waiting...\n";}
		$unix->KILL_PROCESS($pid,15);
		sleep(1);
	}
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic stopped\n";}
		return true;
	}
	
	return false;
	
}

function start($nic){
	$unix=new unix();
	$GLOBALS["TITLENAME"]="$nic WI-FI Daemon";
	$pid=GET_PID($nic);
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already running pid $pid\n";}
		@file_put_contents("/var/run/wpa_supplicant/$nic.pid", $pid);
		return start_cli($nic);
	}
	
	$wpa_supplicant=$unix->find_program("wpa_supplicant");
	if(!is_file($wpa_supplicant)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} wpa_supplicant no such binary\n";}
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} wpa_supplicant $nic\n";}
	
	@mkdir("/var/run/wpa_supplicant",0755,true);
	$ip     = $unix->find_program("ip");
	$iw     = $unix->find_program("iw");

	shell_exec("$ip link set dev $nic up");
    shell_exec("$iw $nic set power_save off >/dev/null 2>&1");
    shell_exec("$ip link set ip link set $nic mode default>/dev/null 2>&1");
    shell_exec("$ip link set dev $nic mtu 576");


	shell_exec("$wpa_supplicant -i $nic -C/var/run/wpa_supplicant/$nic -c/etc/wpa_supplicant/$nic.conf -d -s -P/var/run/wpa_supplicant/$nic.pid -B");
	sleep(1);
	
	for($i=0;$i<5;$i++){
		$pid=GET_PID($nic);
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic waiting...\n";}
		sleep(1);
	}
	
	$pid=GET_PID($nic);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic failed\n";}
		return false;
	}
	$wpa_cli=$unix->find_program("wpa_cli");
	
	exec("$wpa_cli -p/var/run/wpa_supplicant/$nic ping -i$nic 2>&1",$results);
	$echo=trim(@implode("", $results));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} PING -> $echo\n";}
	
	for($i=0;$i<5;$i++){
		if($echo=="PONG"){break;}
		sleep(1);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic waiting ping...\n";}
		exec("$wpa_cli -p/var/run/wpa_supplicant/$nic ping -i$nic 2>&1",$results);
		$echo=trim(@implode("", $results));
	}
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $nic sucesss...\n";}
	return start_cli($nic);
	
	
}

function start_cli($nic){
	$unix=new unix();
	$GLOBALS["TITLENAME"]="$nic WI-FI Client Daemon";
	
	$pid=CLI_PID($nic);
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already running pid $pid\n";}
		@file_put_contents("/var/run/wpa_supplicant/client_$nic.pid", $pid);
		return true;
	}
	
	$wpa_cli=$unix->find_program("wpa_cli");
	if(!is_file($wpa_cli)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} wpa_cli no such binary\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} wpa_cli $nic\n";}
	shell_exec("$wpa_cli -i{$nic} -P/var/run/wpa_supplicant/client_$nic.pid -p/var/run/wpa_supplicant/$nic -B -a/etc/wpa_supplicant/$nic.sh");
	
	for($i=0;$i<5;$i++){
		$pid=GET_PID($nic);
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} wpa_cli $nic waiting...\n";}
		sleep(1);
	}
	
	$pid=GET_PID($nic);
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} wpa_cli failed\n";}
		return false;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} wpa_cli success\n";}
	return true;
	
}



function isconnected($nic){
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	$iwconfig=$unix->find_program("iwconfig");
	
	exec("$iwconfig $nic 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#Access Point:\s+(.+?)\s+#", $line,$re)){
			$result=strtolower(trim($re[1]));
			echo "$nic Access Point = $result\n";
			if($result=="not-associated"){return false;}
		}
		
		
	}
	return true;
}

function String2Hex($string){
	$hex='';
	for ($i=0; $i < strlen($string); $i++){
		$hex .= dechex(ord($string[$i]));
	}
	return $hex;
}

?>