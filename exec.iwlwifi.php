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
include_once(dirname(__FILE__).'/framework/class.status.hardware.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}


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
	sleep(1);
	start(true);
	
}

function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/iwlwifi.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function install(){
	$unix=new unix();

    $KERNEL_VERSION=$unix->KERNEL_VERSION();
    if(!is_file("/lib/modules/$KERNEL_VERSION/kernel/drivers/net/wireless/intel/iwlwifi/iwlwifi.ko")){
        echo "No Supported Kernel ($KERNEL_VERSION)\n";
        build_progress(110, "{installing} $KERNEL_VERSION {failed}");
        return;

    }
    echo "Checking firmware-iwlwifi....\n";
	$hardware=new status_hardware();

	if(!$hardware->IntelWirelessCards()){
        build_progress(15, "{installing} firmware-iwlwifi");
        $unix->DEBIAN_INSTALL_PACKAGE("firmware-iwlwifi");
        if(!$hardware->IntelWirelessCards()){
            echo $hardware->mysql_error."\n";
            build_progress(110, "{installing} firmware-iwlwifi {failed}");
            return;
        }

    }else{
	    echo "firmware-iwlwifi OK\n";
    }



	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableIwlwifi",1);
	build_progress(50, "{installing} mac80211");
	install_module("mac80211");
	build_progress(55, "{installing} cfg80211");
	install_module("cfg80211");
	build_progress(60, "{installing} iwlwifi");
	install_module("iwlwifi");
	build_progress(65, "{installing} mac80211");
	install_module("mac80211");
	build_progress(70, "{installing} iwldvm");
	install_module("iwldvm");
	if(!start()){
		build_progress(110, "{installing} {failed}");
		return;
	}
	build_progress(90, "{installing} update initramfs...");
	$update_initramfs=$unix->find_program("update-initramfs");
	shell_exec("$update_initramfs -u -k all");
	
	build_progress(100, "{installing} {success}");
	
}

function uninstall(){
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableIwlwifi",0);
	build_progress(15, "{uninstalling}");
	remove_module("mac80211");
	remove_module("cfg80211");
	remove_module("iwlwifi");
	remove_module("iwldvm");
	stop();
	
	$f[]="blacklist iwldvm";
	$f[]="blacklist mac80211";
	$f[]="blacklist iwlwifi";
	$f[]="blacklist cfg80211";
	$f[]="blacklist mac80211";
	
	@file_put_contents("/etc/modprobe.d/iwlwifi.conf", @implode("\n", $f)."\n");
	$unix=new unix();
	$update_initramfs=$unix->find_program("update-initramfs");
	build_progress(90, "{installing} update initramfs...");
	shell_exec("$update_initramfs -u -k all");
	build_progress(100, "{uninstalling} {success}");
}

function remove_module($modulename){
	$f=explode("\n",@file_get_contents("/etc/modules"));
	$ADDED=false;
	$n=array();
	foreach ($f as $line){
		if(preg_match("#^$modulename#", $line)){continue;}
		$n[]=$line;
	}
	@file_put_contents("/etc/modules", @implode("\n", $n));
}


function install_module($modulename){
	$f=explode("\n",@file_get_contents("/etc/modules"));
	foreach ($f as $line){
		if(preg_match("#^$modulename#", $line)){return true;}
	}
	
	$f[]="$modulename";
	@file_put_contents("/etc/modules", @implode("\n", $f));
}


function is_module_loaded($modulename){
	$unix=new unix();
	$lsmod=$unix->find_program("lsmod");
	exec("$lsmod 2>&1",$results);
	foreach ($results as $line){ if(preg_match("#^$modulename#", $line)){return true;} }
	return false;
}

function stop_module($modulename){
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} stopping module '$modulename'\n";}
	
	$unix=new unix();
	if(!is_module_loaded($modulename)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} module $modulename success to stop\n";}
		return true;}
		
	$rmmod=$unix->find_program("rmmod");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $rmmod $modulename\n";}	
	$results=array();exec("$rmmod $modulename 2>&1",$results);	
	foreach ($results as $line){if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $modulename: $line\n";} }	
	sleep(1);
	for($i=0;$i<5;$i++){
		if(!is_module_loaded($modulename)){if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} module $modulename success to stop\n";}return true;}
		$results=array();exec("$rmmod $modulename 2>&1",$results);
		foreach ($results as $line){if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $modulename: $line\n";} }
		sleep(1);
	}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} module $modulename failed to stop\n";}
	return false;
	
}

function start_module($modulename,$options=null){
    if($options<>null){$options=" $options";}
	if(is_module_loaded($modulename)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} module $modulename success to start\n";}
		return true;}
	$unix=new unix();
	$modprobe=$unix->find_program("modprobe");
	exec("$modprobe $modulename{$options} 2>&1",$results);
	foreach ($results as $line){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $modulename: $line\n";}
	}
	
	
	sleep(1);
	for($i=0;$i<5;$i++){
		if(is_module_loaded($modulename)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} module $modulename success to start\n";}
			return true;}
		sleep(1);
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} module $modulename failed to start\n";}
	return false;
}
function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	

	
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
	$EnableIwlwifi=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIwlwifi"));
	
	if($EnableIwlwifi==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableIwlwifi)\n";}
		return;
	}

    @file_put_contents("options iwlwifi 11n_disable=8 bt_coex_active=0 power_save=0 auto_agg=0 swcrypto=1\n","/etc/modprobe.d/iwlwifi.conf");
	
	
	build_progress(20, "{loading} mac80211 Kernel module");
	if(!start_module("mac80211")){return false;}

	build_progress(25, "{loading} cfg80211 Kernel module");
	if(!start_module("cfg80211")){return false;}

	build_progress(35, "{loading} iwlwifi Kernel module");
	if(!start_module("iwlwifi","11n_disable=8 bt_coex_active=0 power_save=0 auto_agg=0 swcrypto=1")){return false;}
	
	build_progress(40, "{loading} iwldvm Kernel module");
	if(!start_module("iwldvm")){return false;}
	
	build_progress(50, "{loading} Kernel modules success");

	return true;
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
	build_progress(25, "{removing} iwldvm Kernel module");
	if(!stop_module("iwldvm")){return false;}
	
	build_progress(30, "{removing} mac80211 Kernel module");
	if(!stop_module("mac80211")){return false;}
	
	build_progress(35, "{removing} iwlwifi Kernel module");
	if(!stop_module("iwlwifi")){return false;}
	
	build_progress(40, "{removing} cfg80211 Kernel module");
	if(!stop_module("cfg80211")){return false;}
	
	build_progress(50, "{removing} Kernel modules success");

}

function PID_NUM(){
	
	$unix=new unix();
	$Masterbin=$unix->find_program("arpd");
	return $unix->PIDOF($Masterbin);
	
}
?>