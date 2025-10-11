<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Kaspersky Network Agent Daemon";
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
if($argv[1]=="--reconfigure"){$GLOBALS["OUTPUT"]=true;reconfigure();exit();}
if($argv[1]=="--disable"){$GLOBALS["OUTPUT"]=true;disable();exit();}
if($argv[1]=="--klnagchk"){$GLOBALS["OUTPUT"]=true;klnagchk();exit();}
if($argv[1]=="--monit"){$GLOBALS["OUTPUT"]=true;monit_install();exit();}
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
	

	
}
function build_progressR($pourc,$text){
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/klnagent.reconfigure.progress";
	echo "$date: [{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
}
function build_progress($pourc,$text){
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/klnagent.progress";
	echo "$date: [{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
}

function reconfigure(){
	
	build_progressR(20,"{reconfiguring}");
	
	build_progressR(30,"{stopping_service}");
	system("/etc/init.d/klnagent64 stop");
	stop();
	build_progressR(40,"{reconfiguring}");
	build();
	if(start(true)){
		build_progressR(100,"{reconfiguring} {done}");
		klnagchk();
		return;
	}
	build_progressR(110,"{reconfiguring} {failed}");
	
}

function install(){
	build_progress(20, "{creating_service}");
	$unix=new unix();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKlnagent", 1);
	install_service();

	build_progress(60, "{stopping_service}");
	stop(true);
	build();
	if(!is_file("/etc/cron.d/klnagent")){
		$unix->Popuplate_cron_make("klnagent","0,3,6,9,12,15,18,21,24,27,30,33,36,39,42,45,48,51,54,57 * * * *","exec.klnagent.php --klnagchk");
		system("/etc/init.d/cron reload");
	}
	
	stop(true);
	sleep(1);
	start(true);
	
	build_progress(90, "{starting_service}");
	if(!start(true)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKlnagent", 0);
		remove_service("/etc/init.d/klnagent64");
		build_progress(110, "{starting_service} {failed}");
		return;
	}
	monit_install();
	build_progress(95, "{starting_service}");
	system("/etc/init.d/artica-status restart --force");
	klnagchk();
	build_progress(100, "{done}");
}
function uninstall(){
	build_progress(20, "{remove_service}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKlnagent", 0);
	
	if(is_file("/bin/klnagent-start.sh")){@unlink("/bin/klnagent-start.sh");}
	if(is_file("/bin/klnagent-stop.sh")){@unlink("/bin/klnagent-stop.sh");}
	
	build_progress(25, "{remove_service}");
	if(is_file("/etc/cron.d/klnagent")){
		@unlink("/etc/cron.d/klnagent");
		system("/etc/init.d/cron reload");
	}
	
	$unix=new unix();
	$rm=$unix->find_program("rm");
	shell_exec("$rm -rf /var/opt/kaspersky/klnagent/1103/\\\$FTClTmp");

	remove_service("/etc/init.d/klnagent64");
	if(is_file("/etc/monit/conf.d/APP_KLNAGENT.monitrc")){
		@unlink("/etc/monit/conf.d/APP_KLNAGENT.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	}
	system("/etc/init.d/artica-status restart --force");
	build_progress(100, "{done}");
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
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cp=$unix->find_program("cp");
	$f[]="#!/bin/sh";
	$f[]="$php ".__FILE__." --start";
	$f[]="exit 0\n";
	@file_put_contents("/bin/klnagent-start.sh", @implode("\n", $f));
	$f=array();
	$f[]="#!/bin/sh";
	$f[]="$php ".__FILE__." --stop";
	$f[]="exit 0\n";
	@file_put_contents("/bin/klnagent-stop.sh", @implode("\n", $f));
	$f=array();	
	@chmod("/bin/klnagent-stop.sh",0755);
	@chmod("/bin/klnagent-start.sh",0755);
	

	
	$INITD_PATH="/etc/init.d/klnagent64";
	
	system("$cp -p --remove-destination \"/opt/kaspersky/klnagent64/lib/bin/klnagent64\" \"/etc/init.d/klnagent64\"");
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}



}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin="/opt/kaspersky/klnagent64/sbin/klnagent";

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, /opt/kaspersky/klnagent64/sbin/klnagent not installed\n";}
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
		@file_put_contents("/var/run/klnagent.pid", $pid);
		return true;
	}
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");

	
	$cmd=$Masterbin;
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	
	system($cmd);
	
	
	

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		return true;
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
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

}

function build(){
	$unix=new unix();
	$array=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KLNAGENT"));
	$KLNAGENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KLNAGENT_VERSION");
	
	if(!isset($array["KLNAGENT_USESSL"])){$array["KLNAGENT_USESSL"]=1;}
	if(!isset($array["KLNAGENT_GW_MODE"])){$array["KLNAGENT_GW_MODE"]=1;}
	$KLNAGENT_SERVER=trim($array["KLNAGENT_SERVER"]);
	$KLNAGENT_PORT=intval($array["KLNAGENT_PORT"]);
	$KLNAGENT_SSLPORT=intval($array["KLNAGENT_SSLPORT"]);
	$KLNAGENT_USESSL=intval($array["KLNAGENT_USESSL"]);
	$KLNAGENT_GW_MODE=intval($array["KLNAGENT_GW_MODE"]);
	$KLNAGENT_GW_ADDRESS=trim($array["KLNAGENT_GW_ADDRESS"]);
	
	if($KLNAGENT_SSLPORT==0){$KLNAGENT_SSLPORT=13000;}
	if($KLNAGENT_PORT==0){$KLNAGENT_PORT=14000;}
	if($KLNAGENT_GW_MODE==0){$KLNAGENT_GW_MODE=1;}
	
	$f[]="KLNAGENT_AUTOINSTALL=1";
	$f[]="KLNAGENT_SERVER=$KLNAGENT_SERVER";
	$f[]="KLNAGENT_SSLPORT=$KLNAGENT_SSLPORT";
	$f[]="KLNAGENT_USESSL=$KLNAGENT_USESSL";
	$f[]="KLNAGENT_GW_MODE=$KLNAGENT_GW_MODE";
	$f[]="KLNAGENT_PORT=$KLNAGENT_PORT";
	$f[]="KLNAGENT_SSLPORT=$KLNAGENT_SSLPORT";
	$f[]="KLNAGENT_GW_ADDRESS=$KLNAGENT_GW_ADDRESS";
	
	if($KLNAGENT_SERVER==null){$KLNAGENT_SERVER="localhost";}
	@file_put_contents("/opt/kaspersky/klnagent64/lib/bin/setup/autoanswers.conf", @implode("\n", $f)."\n");
	
	$f[]="/opt/kaspersky/klnagent64/sbin/klnagent";
	$f[]="-regserver";
	$f[]="-pkgver $KLNAGENT_VERSION";
	$f[]="-server $KLNAGENT_SERVER";
	$f[]="-port $KLNAGENT_PORT";
	$f[]="-sslport $KLNAGENT_SSLPORT";
	$f[]="-usessl $KLNAGENT_USESSL";
	$f[]="-groupname Artica";
	$f[]="-gwmode $KLNAGENT_GW_MODE";
	$f[]="-gwaddress $KLNAGENT_GW_ADDRESS";
	
	build_progressR(50,"{reconfiguring}");
	$cmd=@implode(" ", $f);
	echo $cmd."\n";
	system($cmd);
	
	
	
	//chdir("/opt/kaspersky/klnagent64/lib/bin/setup");
	//system("cd /opt/kaspersky/klnagent64/lib/bin/setup");
	

	build_progressR(70,"{reconfiguring} {done}");
	

}

function monit_install(){
	
	if(is_file("/etc/monit/conf.d/APP_KLNAGENT.monitrc")){return;}
	$f=array();
	$f[]="check process APP_KLNAGENT";
	$f[]="with pidfile /var/run/klnagent.pid";
	$f[]="start program = \"/bin/klnagent-start.sh\"";
	$f[]="stop program =  \"/bin/klnagent-stop.sh\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	@file_put_contents("/etc/monit/conf.d/APP_KLNAGENT.monitrc", @implode("\n", $f));
	$f=array();
	//********************************************************************************************************************
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}


function PID_NUM(){
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/klnagent.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF("/opt/kaspersky/klnagent64/sbin/klnagent");
	
}


function klnagchk(){
	$unix=new unix();
	exec("/opt/kaspersky/klnagent64/bin/klnagchk 2>&1",$results);
	$MAIN["UPDATE_AGENT"]=0;
	$MAIN["CNX_GW"]=0;
	
	
	
	foreach ($results as $line){
		if(preg_match("#Host is a reserved update agent#i", $line)){$MAIN["UPDATE_AGENT"]=1;continue;}
		if(preg_match("#Host is an active update agent#i", $line)){$MAIN["UPDATE_AGENT"]=1;continue;}
		if(preg_match("#Host is a connection gateway#i", $line)){$MAIN["CNX_GW"]=1;continue;}
		
		if(preg_match("#(.+?):(.+)#", $line,$re)){
			$key=trim($re[1]);
			$key=strtoupper($key);
			$key=str_replace(" ", "_", $key);
			$key=str_replace(",", "_", $key);
			$key=str_replace(".", "", $key);
			$key=str_replace("__", "_", $key);
			$val=trim($re[2]);
			$val=str_replace("'", "", $val);
			$MAIN[$key]=$val;
			
		}
	}
	$MAIN["DIRSIZE_BYTES"]=$unix->DIRSIZE_BYTES("/var/opt/kaspersky/klnagent/1103/\\\$FTClTmp");
	
	if($GLOBALS["VERBOSE"]){print_r($MAIN);}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KLNAGCHK", serialize($MAIN));
	
	
	
}



?>