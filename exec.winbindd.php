#!/usr/bin/php -q
<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["WITHOUT-RELOAD"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.privileges.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
$GLOBALS["CLASS_UNIX"]=new unix();

if($argv[1]=="--setfacl-squid"){setfacl_squid();exit;}
if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--privs"){setfacl_squid();exit;}
if($argv[1]=="--privs-squid"){setfacl_squid(true);exit;}
if($argv[1]=="--patch8"){installDebian8x(true);exit;}




function setfacl_squid($without_reload=false){
    winbindd_privileges();
}
function squid_watchdog_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}


function is_run(){
	$unix=new unix();
	$pid=WINBIND_PID();
	if(!$unix->process_exists($pid)){return false;}
	return true;
	
}

function WINBIND_PID(){
	$pidfile="/var/run/samba/winbindd.pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	if(!$unix->process_exists($pid)){
		$winbindbin=$unix->find_program("winbindd");
		$pid=$unix->PIDOF($winbindbin);
	}
	return $pid;
}

function Winbindd_events($text,$sourcefunction=null,$sourceline=null){
	$GLOBALS["CLASS_UNIX"]->events("exec.winbindd.php::$text","/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);

}

function DebianVersion(){

	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];

}

function installDebian8x(){
	$unix=new unix();

	$ln=$unix->find_program("ln");
	
	
	
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libkrb5-samba4.so.26";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libkrb5-samba4.so.26.0.0";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libcom_err-samba4.so.0";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libcom_err-samba4.so.0.25";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libgssapi-samba4.so.2";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libgssapi-samba4.so.2.0.0";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libasn1-samba4.so.8";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libasn1-samba4.so.8.0.0";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libheimbase-samba4.so.1";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libheimbase-samba4.so.1.0.0";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libhx509-samba4.so.5";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libhx509-samba4.so.5.0.0";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libhcrypto-samba4.so.5";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libhcrypto-samba4.so.5.0.1";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libroken-samba4.so.19";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libroken-samba4.so.19.0.1";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libwind-samba4.so.0";
	$f[]="/usr/lib/x86_64-linux-gnu/samba/libwind-samba4.so.0.0.0";
	$f[]="/usr/lib/x86_64-linux-gnu/libndr.so.0";
	$f[]="/usr/lib/x86_64-linux-gnu/libndr.so.0.0.3";
	
	foreach ($f as $filename){
			
		if(is_file($filename)){
			echo "Starting......: ".date("H:i:s")." WINBIND remove $filename\n";
			@unlink($filename);
		}else{
			echo "Starting......: ".date("H:i:s")." WINBIND ".basename($filename)." [OK]\n";
		}
			
			
	}
	

	
	$LINKS["libkrb5-samba4.so.26"]="libkrb5-samba4.so.26.0.0";
	$LINKS["libcom_err-samba4.so.0"]="libcom_err-samba4.so.0.25";
	$LINKS["libgssapi-samba4.so.2"]="libgssapi-samba4.so.2.0.0";
	$LINKS["libasn1-samba4.so.8"]="libasn1-samba4.so.8.0.0";
	$LINKS["libheimbase-samba4.so.1"]="libheimbase-samba4.so.1.0.0";
	$LINKS["libhcrypto-samba4.so.5"]="libhcrypto-samba4.so.5.0.1";
	$LINKS["libroken-samba4.so.19"]="libroken-samba4.so.19.0.1";
	$LINKS["libwind-samba4.so.0"]="libwind-samba4.so.0.0.0";
	
	
	while (list ($check, $source) = each ($LINKS) ){
		if(!is_file("/usr/lib/samba/$check")){
			echo "Starting......: ".date("H:i:s")." WINBIND Installing $check -> $source\n";
			system("$ln -s /usr/lib/samba/$source /usr/lib/samba/$check");
		}else{
			echo "Starting......: ".date("H:i:s")." WINBIND $check [OK]\n";
		}
			
	}
	
	
}

function start($nopid=false){
	$unix=new unix();
	$sock=new sockets();
	
	$debianVersion=DebianVersion();

	
	
	if(!$nopid){
		$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidpath);
		if($unix->process_exists($pid,basename(__FILE__))){
			echo "Starting......: ".date("H:i:s")." WINBIND Already running start process exists\n";
			Winbindd_events("Already running start process exists",__FUNCTION__,__LINE__);
			
			return;
		}
	}
	
	if(is_run()){
		echo "Starting......: ".date("H:i:s")." WINBIND already running....\n";
		Winbindd_events("Winbindd ask to start But already running",__FUNCTION__,__LINE__);
		@file_put_contents("/var/run/samba/winbindd.pid", WINBIND_PID());
		echo "Starting......: ".date("H:i:s")." WINBIND check privileges...\n";
		winbindd_privileges();
		return true;
	}
	


	
	
	$winbindd=$unix->find_program("winbindd");
	echo "Starting......: ".date("H:i:s")." WINBIND $winbindd....\n";
	$DisableWinbindd=$sock->GET_INFO("DisableWinbindd");
	if(!is_numeric($DisableWinbindd)){$DisableWinbindd=0;}
	$squid=$unix->LOCATE_SQUID_BIN();
	if(is_file($squid)){
		$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
		if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
		if($EnableKerbAuth==1){$DisableWinbindd=0;}
	}
	
	
	
	if($DisableWinbindd==1){
		
		echo "Starting......: ".date("H:i:s")." WINBIND $winbindd is disabled ( see DisableWinbindd )....\n";
		stop();
		return false;
	}
	
	build_progress_restart(20, "{starting} {SAMBA_WINBIND} Clean old libraries");
	$unix->CleanOldLibs();
	

	
	$pidof=$unix->find_program("pidof");
	exec("$pidof $winbindd 2>&1",$pidofr);
	$lines=trim(@implode("", $pidofr));
	Winbindd_events("Winbindd PIDOF report:" .$lines,__FUNCTION__,__LINE__);
	$tr=explode(" ",$lines);
	
	while (list ($index, $pid) = each ($tr) ){
		if(!is_numeric($pid)){continue;}
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		$cmdline=@file_get_contents("/proc/$pid/cmdline");
		Winbindd_events("Winbindd PIDOF report: $pid ({$timepid}Mn) \"$cmdline\"",__FUNCTION__,__LINE__);
		$rr[$pid]=true;
	}
	if(count($rr)>0){
		Winbindd_events("Winbindd ask to start but already running ".count($rr)." instance(s)",__FUNCTION__,__LINE__);
		winbindd_privileges();
		return true;
	}
	

	build_progress_restart(30, "{starting} {SAMBA_WINBIND}");
	winbindd_privileges();
	shell_exec($winbindd." -D");
	for($i=0;$i<10;$i++){
		if(is_run()){break;}
		build_progress_restart(30+$i, "{starting} {SAMBA_WINBIND}");
		echo "Starting......: ".date("H:i:s")." WINBIND (start) waiting to run\n";
		sleep(1);
	}
	if(is_run()){
		$pid=WINBIND_PID();
		Winbindd_events("Winbindd start success PID $pid",__FUNCTION__,__LINE__);
		echo "Starting......: ".date("H:i:s")." WINBIND (start) success PID $pid\n";
		return true;
	}else{
		echo "Starting......: ".date("H:i:s")." WINBIND (start) failed\n";
		squid_admin_mysql(0,"Failed to Start Winbind (see logs)",
            @file_get_contents("/var/log/samba/log.winbindd"),__FILE__,__LINE__);
		return false;
	}
}

function build_progress_restart($pourc,$text){
	$filename=PROGRESS_DIR."/winbindd.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($filename, serialize($array));
	@chmod($filename,0755);
}

function restart(){
	
	build_progress_restart(10, "{restarting} {SAMBA_WINBIND}");
	
	
	if(!is_run()){
		echo "Starting......: ".date("H:i:s")." WINBIND (restart) not running, start it...\n";
		Winbindd_events("Winbindd (restart) not running, start it",__FUNCTION__,__LINE__);
		if(!start(true)){
			build_progress_restart(110, "{starting} {SAMBA_WINBIND} {failed}");
			return;
		}
		
		build_progress_restart(100, "{starting} {SAMBA_WINBIND} {success}");
		return;
	}
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$filetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$time=$unix->file_time_min($filetime);
	
	Winbindd_events("Winbindd ask to restart since {$time}Mn",__FUNCTION__,__LINE__);
	if(!$GLOBALS["FORCE"]){
		if($time<59){
			
			$pid=WINBIND_PID();
			if($unix->process_exists($pid)){
				$timepid=$unix->PROCESS_TTL($pid);
				echo "Starting......: ".date("H:i:s")." WINBIND ask to restart need to wait 60Mn pid:$pid $timepid\n";
				Winbindd_events("Winbindd ask to restart need to wait 60Mn pid:$pid $timepid",__FUNCTION__,__LINE__);
				build_progress_restart(110, "{restarting} need to wait 60Mn pid:$pid $timepid");
				return;
			}else{
				echo "Starting......: ".date("H:i:s")." WINBIND (restart) not running, start it...\n";
				if(!start(true)){
					build_progress_restart(110, "{starting} {SAMBA_WINBIND} {failed}");
				return;
				}
		
				build_progress_restart(100, "{starting} {SAMBA_WINBIND} {success}");
				return;
			}
		}
	}
	@unlink($filetime);
	@file_put_contents($filetime, time());
	
	
	$smbcontrol=$unix->find_program("smbcontrol");
	$chmod=$unix->find_program("chmod");
	$settings=new settings_inc();
	winbindd_privileges();	

	if(!$GLOBALS["FORCE"]){
		if(is_file($smbcontrol)){
			build_progress_restart(80, "{reloading} {SAMBA_WINBIND}");
			Winbindd_events("Winbindd reloading",__FUNCTION__,__LINE__);
			echo "Starting......: ".date("H:i:s")." WINBIND reloading...\n";
			shell_exec("$smbcontrol winbindd reload-config");
			shell_exec("$smbcontrol winbindd offline");
			shell_exec("$smbcontrol winbindd online");
			setfacl_squid();
			build_progress_restart(100, "{reloading} {SAMBA_WINBIND} {success}");
			return;
		}
	}
	Winbindd_events("Winbindd stop",__FUNCTION__,__LINE__);
	build_progress_restart(80, "{stopping} {SAMBA_WINBIND}");
	stop();
	Winbindd_events("Winbindd ask to start",__FUNCTION__,__LINE__);
	build_progress_restart(90, "{starting} {SAMBA_WINBIND}");
	if(!start(true)){
		build_progress_restart(110, "{starting} {SAMBA_WINBIND} {failed}");
		return;
	}
	build_progress_restart(100, "{starting} {SAMBA_WINBIND} {success}");
	return;
	
}

function stop(){
	$unix=new unix();
	echo "Stopping WINBIND.............: find binaries daemons\n";
	$pidof=$unix->find_program("pidof");
	$winbindd=$unix->find_program("winbindd");
	$kill=$unix->find_program("kill");
	if(!is_file($winbindd)){return;}
	exec("$pidof $winbindd 2>&1",$results);
	while (list ($key, $val) = each ($results) ){
		if(preg_match("#([0-9\s]+)#", $val,$re)){
			
			$tb=explode(" ",$re[1]);
			while (list ($a, $b) = each ($tb) ){
				if(!is_numeric($b)){continue;}
				echo "Stopping WINBIND.............: killing $b pid\n";
				unix_system_kill_force($b);
				
			}
		}
	}
	
}


