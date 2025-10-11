#!/usr/bin/php -q
<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="UniFi controller";
$GLOBALS["PROGRESS"]=false;
$GLOBALS["MIGRATION"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;
$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--migration#",implode(" ",$argv),$re)){$GLOBALS["MIGRATION"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.influx.inc');

$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--build"){build();exit();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart_progress();exit();}

if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
if($argv[1]=="--install-progress"){$GLOBALS["OUTPUT"]=true;$GLOBALS["PROGRESS"]=true;install();exit();}
if($argv[1]=="--restart-progress"){$GLOBALS["OUTPUT"]=true;$GLOBALS["PROGRESS"]=true;restart_progress();exit();}




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
	build();
	sleep(1);
	start(true);
	
}


function DebianVersion(){
	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];

}


function install(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$unix=new unix();
	$sock=new sockets();
	$DebianVersion=DebianVersion();
	$aptget=$unix->find_program("apt-get");
	$aptkey=$unix->find_program("apt-key");
	
	if($DebianVersion<7){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, influxdb Debian version incompatible!\n";}
		build_progress_idb("Incompatible system!",110);
		exit();
	}
	
	if(!is_dir("/etc/apt/sources.list.d")){@mkdir("/etc/apt/sources.list.d");}
	
	@file_put_contents("/etc/apt/sources.list.d/ubiquiti.list", "\ndeb http://www.ubnt.com/downloads/unifi/debian stable ubiquiti\n");
	
	system("$aptkey adv --keyserver keyserver.ubuntu.com --recv C0A52C50");
	
	build_progress_idb("{update_debian_repository}",20);
	$cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\"   -y update 2>&1";
	system($cmd);
	
	build_progress_idb("{installing_package}",50);
	$cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\"   -y install unifi 2>&1";
	system($cmd);
	build_progress_idb("{installing_package} {done}",55);
	if(is_file("/usr/lib/unifi/lib/ace.jar")){
		$sock->SET_INFO("EnableUnifiController",1);
		build_progress_idb("{restarting_services}",60);
		system("/etc/init.d/artica-status restart --force");
		build_progress_idb("{restarting_services}",70);
		restart_progress(true);
		build_progress_idb("{done}",100);
		return;
	}
	build_progress_idb("{failed_to_install}",110);
	
	
}
function download_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

	if ( $download_size == 0 ){
		$progress = 0;
	}else{
		$progress = round( $downloaded_size * 100 / $download_size );
	}

	if ( $progress > $GLOBALS["previousProgress"]){
			if($progress<95){
				build_progress_idb("{downloading}",$progress);
			}
			$GLOBALS["previousProgress"]=$progress;
			
	}
}

function restart_progress($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	build_progress_rs("{stopping_service}",10);
	if(!stop(true)){return;}
	sleep(1);
	build();
	build_progress_rs("{starting_service}",30);
	start(true);
	
}
function GetUUID(){

	$f=explode("\n",@file_get_contents("/usr/lib/unifi/data/system.properties"));
	while (list ($key, $line) = each ($f) ){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^uuid=(.+)#", $line,$re)){continue;}
		return trim($re[1]);
	}


}



function build(){
	$unix=new unix();
	$sock=new sockets();
	$UnifiListenIP=null;
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$UnifiListenInterface=$sock->GET_INFO("UnifiListenInterface");
	if($UnifiListenInterface<>null){
		$UnifiListenIP=$NETWORK_ALL_INTERFACES[$UnifiListenIP]["IPADDR"];
		if($UnifiListenIP=="0.0.0.0"){$UnifiListenIP=null;}
	}
	
	$UnifiHTTPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnifiHTTPPort"));
	$UnifiHTTPSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnifiHTTPSPort"));
	$UnifiPortalPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnifiPortalPort"));
	$UnifiPortalSSLPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnifiPortalSSLPort"));
	$UnifiUDPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnifiUDPPort"));
	$UnifiUUID=$sock->GET_INFO("UnifiUUID");
	if($UnifiUUID==null){$UnifiUUID=GetUUID();}
	if($UnifiHTTPPort==0){$UnifiHTTPPort=8088;}
	if($UnifiHTTPSPort==0){$UnifiHTTPSPort=8443;}
	if($UnifiPortalPort==0){$UnifiPortalPort=8880;}
	if($UnifiPortalSSLPort==0){$UnifiPortalSSLPort=8943;}
	if($UnifiUDPPort==0){$UnifiUDPPort=3478;}
	
	
	$f[]="## system.properties";
	$f[]="#";
	$f[]="# each unifi instance requires a set of ports:";
	$f[]="#";
	$f[]="unifi.http.port=$UnifiHTTPPort";
	$f[]="unifi.https.port=$UnifiHTTPSPort";
	$f[]="portal.http.port=$UnifiPortalPort";
	$f[]="portal.https.port=$UnifiPortalSSLPort";
	$f[]="# unifi.db.port=27117";
	$f[]="# unifi.stun.port=$UnifiUDPPort";
	$f[]="#";
	if($UnifiListenIP<>null){
		$f[]="system_ip=$UnifiListenIP        # the IP devices should be talking to for inform";
	}
	$f[]="# unifi.db.nojournal=false # disable mongodb journaling";
	$f[]="# unifi.db.extraargs       # extra mongod args";
	$f[]="#";
	$f[]="## HTTPS options";
	$f[]="# unifi.https.ciphers=TLS_RSA_WITH_AES_256_CBC_SHA,TLS_RSA_WITH_AES_128_CBC_SHA";
	$f[]="# unifi.https.sslEnabledProtocols=TLSv1";
	$f[]="#";
	$f[]="#";
	$f[]="#". date("Y-m-d H:i:s");
	$f[]="uuid=$UnifiUUID\n";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, save settings done\n";}
	
	
	@file_put_contents("/usr/lib/unifi/data/system.properties", @implode("\n", $f));
}







function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin="/opt/influxdb/influxd";
	
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

	build_progress_rs("{starting_service}",30);
	if(!is_file($Masterbin)){
		Install();
		if(!is_file($Masterbin)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, influxdb not installed\n";}
			build_progress_rs("{failed_to_start_service} ({not_installed})",110);
			return;
		}
		
	}

	
	
	$pid=PID_NUM();
	
	$EnableUnifiController=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnifiController"));
	$UnifiHTTPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnifiHTTPPort"));
	if($UnifiHTTPPort==0){$UnifiHTTPPort=8088;}
	

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($EnableUnifiController==0){stop(true);}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		build_progress_rs("{already_running}",100);
		return true;
	}

	if($EnableUnifiController==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Disabled\n";}
		build_progress_rs("{starting_service} {failed} ({disabled})",110);
		return false;
	}
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$date=$unix->find_program("date");
	
	build_progress_rs("{starting_service}!",35);
	build_progress_rs("{starting_service}",45);
	$cmd="/etc/init.d/unifi start >/dev/null 2>&1 &";
	
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);
	
	for($i=1;$i<5;$i++){
		build_progress_rs("{starting_service}",45+$i);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	
	
	if($unix->process_exists($pid)){
		build_progress_rs("{starting_service}",50);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		build_progress_rs("{starting_service} {success}",100);
		return true;
		
	}else{
		build_progress_rs("{failed_to_start_service}",110);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		return false;
	}


}

function test_listen_port(){
	$sock=new sockets();
	$UnifiHTTPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnifiHTTPPort"));
	if($UnifiHTTPPort==0){$UnifiHTTPPort=8088;}
	
	$unix=new unix();
	$sock=new sockets();
	$UnifiListenIP=null;
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$UnifiListenInterface=$sock->GET_INFO("UnifiListenInterface");
	if($UnifiListenInterface<>null){
		$UnifiListenIP=trim($NETWORK_ALL_INTERFACES[$UnifiListenIP]["IPADDR"]);
		if($UnifiListenIP=="0.0.0.0"){$UnifiListenIP=null;}
	}
	if(trim($UnifiListenIP)==null){$UnifiListenIP="127.0.0.1";}
	
	$fp=@fsockopen($UnifiListenIP, $UnifiHTTPPort, $errno, $errstr, 2);

	if(!$fp){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $UnifiListenIP:$UnifiHTTPPort ($errstr)\n";}
		@socket_close($fp);
		return false;
	}
	@socket_close($fp);
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

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		build_progress_rs("{stopping_service}",30);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return true;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	


	build_progress_rs("{stopping_service}",15);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	system("/etc/init.d/unifi stop >/dev/null 2>&1 &");
	for($i=0;$i<5;$i++){
		build_progress_rs("{stopping_service}",15+$i);
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		build_progress_rs("{stopping_service}",30);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return true;
	}

	build_progress_rs("{stopping_service}",30);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		build_progress_rs("{stopping_service}",30+$i);
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		build_progress_rs("{stopping_service} {failed}",110);
		squid_admin_mysql(0, "Failed to stop Unifi Controller Engine",__FILE__,__LINE__);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}
	
	return true;

}


function PID_NUM(){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/unifi/unifi.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("/usr/lib/unifi/lib/ace.jar");
}



function build_progress_rdb($pourc,$text){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.remove.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function build_progress_idb($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/unifi.install.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function build_progress_rs($text,$pourc){
	if(!isset($GLOBALS["PROGRESS"])){return;}
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/unifi.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
?>