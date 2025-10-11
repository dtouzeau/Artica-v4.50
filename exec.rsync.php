<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NORELOAD"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["TITLENAME"]="Rsync Daemon";
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");


if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--no-reload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}

if($argv[1]=="--reload"){reload_progress();exit();}
if($argv[1]=="--uninstall"){uninstall_service();exit();}
if($argv[1]=="--install"){install_service();exit();}
if($argv[1]=="--start"){start();exit();}
if($argv[1]=="--monit"){build_monit();exit();}
if($argv[1]=="--default"){autofs_default();exit();}
if($argv[1]=="--checks"){Checks();exit();}
if($argv[1]=="--restart"){$GLOBALS["PROGRESS"]=true;$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--build"){build();exit();}


function build_progress_rs($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$GLOBALS["TITLENAME"]} {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/autofs.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function build_progress_install($text,$pourc){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$GLOBALS["TITLENAME"]} {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/rsync.install.prg";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}


function install_service(){
	build_progress_install("{enable_feature}",20);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableRsyncDaemon", 1);
	build_progress_install("{install_service}",30);
	create_service();
	build_monit();
	build_progress_install("{success}",100);
}

function build_monit(){
	$f[]="check process APP_RSYNC_SERVER with pidfile /var/run/rsyncd.pid";
	$f[]="\tstart program = \"/etc/init.d/rsync start --monit\"";
	$f[]="\tstop program = \"/etc/init.d/rsync stop --monit\"";

	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_RSYNC_SERVER.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_RSYNC_SERVER.monitrc")){
		echo "/etc/monit/conf.d/APP_RSYNC_SERVER.monitrc failed !!!\n";
	}
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
	
}

function  uninstall_service(){
	$unix=new unix();
	$rm=$unix->find_program("rm");
	build_progress_install("{disable_feature}",20);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableRsyncDaemon", 0);
	build_progress_install("{uninstall_service}",30);
	shell_exec("$rm -f /etc/rsyncd.*.secrets");
	remove_service("/etc/init.d/rsync");
	@unlink("/var/log/rsyncd.log");
	if(is_file("/etc/monit/conf.d/APP_RSYNC_SERVER.monitrc")){
		@unlink("/etc/monit/conf.d/APP_RSYNC_SERVER.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
	}
	
	build_progress_install("{success}",100);
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
		build_progress_install("{failed}",110);
		
		return;
	}
	@file_put_contents($pidfile, getmypid());
	@chdir("/root");
	build_progress_install("{stopping_service}",10);
	
	stop(true);
	build_progress_install("{reconfiguring}",50);
	build();
	sleep(1);
	build_progress_install("{starting_service}",60);
	if(!start(true,50)){
		build_progress_install("{starting_service} {failed}",110);
		return;
	}
	build_progress_install("{starting_service} {success}",100);
}


function start($aspid=false,$progress=0){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("rsync");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, rsync not installed\n";}
		build_progress_install("{starting_service}",$progress++);
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			build_progress_install("{starting_service}",$progress++);
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}
	build_progress_install("{starting_service}",$progress++);
	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		build_progress_install("{starting_service}",100);
		@file_put_contents("/var/run/rsyncd.pid",$pid);
		return true;
	}


	if(!is_file("/etc/rsyncd.conf")){build();}

	if(is_file("/var/run/rsyncd.pid")){@unlink("/var/run/rsyncd.pid");}
	shell_exec("$Masterbin --daemon --config=/etc/rsyncd.conf --log-file=/var/log/rsyncd.log");

	for($i=1;$i<5;$i++){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";
		sleep(1);
		$pid=PID_NUM();
		build_progress_install("{starting_service}",$progress++);
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	build_progress_install("{starting_service}",$progress++);
	if($unix->process_exists($pid)){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";
		return true;
	}else{
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $Masterbin --daemon --config=/etc/rsyncd.conf\n";
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
		build_progress_install("{stopping_service}",50);
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");




	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		build_progress_install("{stopping_service}",15);
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		build_progress_install("{stopping_service}",50);
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
		build_progress_install("{stopping_service}",20);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		build_progress_install("{stopping_service}",50);
		return;
	}
	
	build_progress_install("{stopping_service}",50);

}

function PID_NUM(){

	$unix=new unix();
        $pid=$unix->get_pid_from_file("/var/run/rsyncd.pid");
        if(!$unix->process_exists($pid)){
            $pid=$unix->PIDOF_PATTERN("rsync\s+--daemon");
        }
        return $pid;

}



function create_service(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$dirname=dirname(__FILE__);
	$filename=basename(__FILE__);
	$chmod=$unix->find_program("chmod");
	$INIT_FILE="/etc/init.d/rsync";
	$conf[]="#! /bin/sh";
	$conf[]="# /etc/init.d/rsync";
	$conf[]="#";
	$conf[]="# rsync Debian init script";
	$conf[]="#";
	$conf[]="### BEGIN INIT INFO";
	$conf[]="# Provides:          rsync-daemon";
	$conf[]="# Required-Start:    \$syslog";
	$conf[]="# Required-Stop:     \$syslog";
	$conf[]="# Should-Start:      \$local_fs";
	$conf[]="# Should-Stop:       \$local_fs";
	$conf[]="# Default-Start:     3 4 5";
	$conf[]="# Default-Stop:      1";
	$conf[]="# Short-Description: Launch rsync server";
	$conf[]="# Description:       Launch rsync server";
	$conf[]="### END INIT INFO";
	$conf[]="";
	$conf[]="case \"\$1\" in";
	$conf[]=" start)";
	$conf[]="    $php5 $dirname/$filename --start \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]="  stop)";
	$conf[]="    $php5 $dirname/$filename --stop \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]=" restart)";
	$conf[]="	  $php5 $dirname/$filename --restart \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]=" reload)";
	$conf[]="     $php5 $dirname/$filename --reload \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]="";
	$conf[]="  *)";
	$conf[]="    echo \"Usage: \$0 {start|stop|restart|reload}\"";
	$conf[]="    exit 1";
	$conf[]="    ;;";
	$conf[]="esac";
	$conf[]="exit 0\n";
	@file_put_contents($INIT_FILE,@implode("\n",$conf));
	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");
	
	shell_exec("$chmod +x $INIT_FILE >/dev/null 2>&1");
	if(is_file($debianbin)){
		shell_exec("$debianbin -f ".basename($INIT_FILE)." defaults >/dev/null 2>&1");
		return;
	}
	if(is_file($redhatbin)){
		shell_exec("$redhatbin --add ".basename($INIT_FILE)." >/dev/null 2>&1");
		shell_exec("$redhatbin --level 2345 ".basename($INIT_FILE)." on >/dev/null 2>&1");
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: ".basename($INIT_FILE)." success...\n";}
}

function build():bool{
	$unix=new unix();
	$RsyncPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyncPort"));
	$RsyncMaxcnx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyncMaxcnx"));
	$RsyncInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyncInterface"));
	$RsyncReverseLookup=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyncReverseLookup"));
    $EnableRsyncDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRsyncDaemon"));
	if($EnableRsyncDaemon==0){return false;}
	
	$address=null;
	if($RsyncInterface<>null){
		$address=$unix->NETWORK_IFNAME_TO_IP($RsyncInterface);
		
	}
	
	
	
	if($RsyncPort==0){$RsyncPort=873;}
	if($RsyncMaxcnx==0){$RsyncMaxcnx=20;}
	
	$f[]="lock file 		= /var/run/rsync.lock";
	$f[]="log file 			= /var/log/rsyncd.log";
	$f[]="pid file 			= /var/run/rsyncd.pid";
	$f[]="port 				= $RsyncPort";
	$f[]="max connections 	= $RsyncMaxcnx";
	if($address<>null){
		$f[]="address 			= $address";
	}
	
	if($RsyncReverseLookup==1){
		$f[]="reverse lookup 	= yes";
	}else{
		$f[]="reverse lookup 	= no";
	}

	$f[]="";
	$f[]="[artica-postfix]";
	$f[]="path = /usr/share/artica-postfix";
	$f[]="comment = For Artica update";
	$f[]="exclude = bin/install/rrd/*** bin/EasyRSA-3.0.0/*** ressources/usb.scan.inc ressources/usb.scan.serialize ressources/interface-cache/*** framework/*** roundcube-plugin/*** ressources/local_ldap.php ressources/settings.inc ressources/settings.bak ressources/databases/ALL_SQUID_STATUS ressources/databases/SQUID.version ressources/logs/*** ressources/conf/*** ressources/mysqltuner/*** ressources/sessions/*** ressources/web/***";
	$f[]="read only = true";
	$f[]="timeout = 300";
	
	$EnableWsusOffline=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWsusOffline"));
	
	if($EnableWsusOffline==1){
		$wsusofflineStorageDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("wsusofflineStorageDir"));
		if($wsusofflineStorageDir==null){$wsusofflineStorageDir="/usr/share/wsusoffline";}
		$f[]="";
		$f[]="[wsusoffline]";
		$f[]="path = $wsusofflineStorageDir/client";
		$f[]="comment = RSYNC WSUS";
		$f[]="read only = true";
		$f[]="timeout = 300";
		
	}
	$f[]="";
	$q=new mysql();
	$sql="SELECT *  FROM rsyncd_folders WHERE enabled=1 ORDER BY directory";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$ALREADY=array();
	while ($ligne = mysqli_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$USERS=array();
		$USERS_FILE=array();
		$directory=$ligne["directory"];
		$dirname=basename($directory);
		if(isset($ALREADY[$dirname])){
			$dirname=$dirname.$ligne["ID"];
			
		}
		$ALREADY[$dirname]=true;
		$config=unserialize(base64_decode($ligne["config"]));
		$comment=$ligne["comment"];
		$comment=str_replace("\n", " ", $comment);
		$comment=str_replace("\r", " ", $comment);
		
		$readonly=$ligne["readonly"];
		$writeonly=$ligne["writeonly"];
		$listable=$ligne["listable"];
		
		if($readonly==1){$readonly="yes";}else{$readonly="no";}
		if($writeonly==1){$writeonly="yes";}else{$writeonly="no";}
		if($listable==1){$listable="yes";}else{$listable="no";}
		
			
		$f[]="";
		$f[]="[$dirname]";
		$f[]="path 			= $directory";
		$f[]="comment 		= $comment";
		$f[]="read only 	= $readonly";
		$f[]="write only 	= $writeonly";
		$f[]="list			= $listable";
		
		if(count($config["AUTH"])>0){
			while (list ($username, $password) = each ($config["AUTH"])){
				if(trim($username)==null){continue;}
				$USERS[]=$username;
				$USERS_FILE[]="$username:$password";
			}
			
		}
		
		if(count($config["RESTRICTIONS"])>0){$f[]="hosts allow	= ".@implode(" ", $config["RESTRICTIONS"]);}
		
		
		if(count($USERS)>0){
			$f[]="auth users	= ".@implode(" ", $USERS);
			$f[]="secrets file 	= /etc/rsyncd.{$ID}.secrets";
			@file_put_contents("/etc/rsyncd.{$ID}.secrets", @implode("\n", $USERS_FILE));
			@chmod("/etc/rsyncd.{$ID}.secrets",0600);
		}
		
		
		$f[]="";
		
	}
    @file_put_contents("/etc/rsyncd.conf", @implode("\n", $f));
    return true;
	
}

?>