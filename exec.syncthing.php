<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["WATCHDOG"]=false;
$GLOBALS["MONIT"]=false;
$GLOBALS["UFDBTAIL"]=false;
$GLOBALS["TITLENAME"]="Cloud Sync Daemon";
$GLOBALS["BYSCHEDULE"]=false;
$GLOBALS["PID_PATH"]="/var/run/ufdbguard/ufdbguardd.pid";
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--from-schedule#",implode(" ",$argv),$re)){$GLOBALS["BYSCHEDULE"]=true;}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--monit#",implode(" ",$argv),$re)){$GLOBALS["MONIT"]=true;}
if(preg_match("#--watchdog#",implode(" ",$argv),$re)){$GLOBALS["WATCHDOG"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--ufdbtail#",implode(" ",$argv),$re)){$GLOBALS["UFDBTAIL"]=true;$GLOBALS["FORCE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.compile.ufdbguard.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--rotatelog"){$GLOBALS["OUTPUT"]=true;rotate();exit();}




function restart() {
	$unix=new unix();
	$FORCED_TEXT=null;
	$NOTIFY=false;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	if($GLOBALS["FORCE"]){
		$FORCED_TEXT=" (forced)";
	
	}
	stop(true);
	sleep(1);
	start(true);
	
}
function build_progress($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/syncthing.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}



function syncthing_version(){
	if(isset($GLOBALS["syncthing_version"])){return $GLOBALS["syncthing_version"];}
	$unix=new unix();
	$Masterbin=$unix->find_program("syncthing");
	exec("$Masterbin -version 2>&1",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#syncthing v([0-9\.]+)\s+#" ,$line,$re)){
			$GLOBALS["syncthing_version"]=$re[1];
			return $GLOBALS["syncthing_version"];
		}
	}
	
}




function reload(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$sock=new sockets();
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		$unix->_syslog("{$GLOBALS["TITLENAME"]} Reloading PID $pid\n",basename(__FILE__));
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Reloading PID $pid\n";}
		unix_system_HUP($pid);
		
	}else{
		start(true);
	}
	
}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("syncthing");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, syncthing not installed\n";}
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

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		if($GLOBALS["MONIT"]){@file_put_contents($GLOBALS["PID_PATH"],$pid);}
		return;
	}
	
	
	$EnableSyncThing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSyncThing"));
	$SyncThingPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyncThingPort"));
	if($SyncThingPort==0){$SyncThingPort=8000;}
	

	if($EnableSyncThing==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableSyncThing)\n";}
		stop();
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$kill=$unix->find_program("kill");
	
	
	@mkdir("/etc/syncthing",0755,true);
		
	$RESTART=false;
	if(!is_file("/etc/syncthing/config.xml")){$RESTART=true;}
	
	
	if(is_file("/etc/syncthing/config.xml")){
		change_port();
	}
	$syncthing_version=syncthing_version();
	$tmpf="/var/log/syncthing.log";
	$cmd="$nohup $Masterbin -home /etc/syncthing -no-browser >$tmpf 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service v$syncthing_version\n";}
	shell_exec($cmd);
	
	
	

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}
	

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		
		if($RESTART){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Must restart\n";}
			$unix->THREAD_COMMAND_SET("/etc/init.d/syncthing restart");
		}

		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		@unlink($tmpf);
		return true;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	$f=explode("\n",@file_get_contents($tmpf));
	
	foreach ( $f as $index=>$line ){
		if($GLOBALS["OUTPUT"]){echo "Starting......: $line\n";}
	}
	system("printenv");
	@unlink($tmpf);

}

function stop($aspid=false){
	if($GLOBALS["MONIT"]){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} runned by Monit, abort\n";}
		return;}
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Artica script already running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		killbyports();
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
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		killbyports();
		return;
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
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
	killbyports();
}

function IsPortListen(){
	$sock=new sockets();
	$unix=new unix();
	$SyncThingPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyncThingPort"));
	if($SyncThingPort==0){$SyncThingPort=8000;}
	count($unix->PIDOF_BY_PORT($SyncThingPort));
}

function change_port(){
	$sock=new sockets();
	$unix=new unix();

	$SyncThingPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyncThingPort"));
	if($SyncThingPort==0){$SyncThingPort=8000;}

	$f=explode("\n",@file_get_contents("/etc/syncthing/config.xml"));
	$START=FALSE;
	foreach ( $f as $index=>$line ){
		
		if(preg_match("#gui enabled#", $line)){
			$START=true;
		}
		if(!$START){continue;}
		if(preg_match("#<address>#", $line)){
			$f[$index]="\t<address>0.0.0.0:$SyncThingPort</address>";
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}  *** PORT CHANGE TO $SyncThingPort ***\n";}
			@file_put_contents("/etc/syncthing/config.xml", @implode("\n", $f));
			return;
		}
		
	}
	
	
}

function killbyports(){
	$sock=new sockets();
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$SyncThingPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyncThingPort"));
	if($SyncThingPort==0){$SyncThingPort=8000;}
	$PIDS=$unix->PIDOF_BY_PORT($SyncThingPort);
	if(count($PIDS)==0){if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} 0 PID listens {$SyncThingPort}...\n";}return;}
	foreach ($PIDS as $pid=>$b){
		if($unix->process_exists($pid)){
			$cmdline=@file_get_contents("/proc/$pid/cmdline");
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} killing PID $pid that listens $SyncThingPort} TCP port\n";}
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmdline\n";}
			unix_system_kill_force($pid);
		}
	}
	
	
}
function PID_NUM(){
	$unix=new unix();
	$Masterbin=$unix->find_program("syncthing");
	$pid=$unix->PIDOF_PATTERN("syncthing.*?no-browser");
	if($unix->process_exists($pid)){return $pid;}
}
?>