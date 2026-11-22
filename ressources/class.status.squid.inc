<?php
//SP139
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}
include_once(dirname(__FILE__)."/class.squid.watchdog.inc");
function squid_increment_func($array){
        $array[] = "squid_nat";
        $array[] = "squid_db";
        $array[] = "squidguardweb";
        $array[] = "hypercache_logger";
        $array[]="adker_watchdog";
	return $array;
}



function _squid_status_out($text):bool{
    if(!function_exists("openlog")){return true;}
    openlog("monit", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, "artica-watchdog: $text");
    closelog();
    return true;
}

// storeid_file_rewrite_checkupdates() moved to articarest: squid.HyperCacheWatchdog (every 5 minutes).





function LockActiveDirectoryToKerberos():bool{
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){return false;}
    if(!is_file("/etc/squid3/krb5.keytab")){
        squid_admin_mysql(0,"Active Directory: Fatal missing /etc/squid3/krb5.keytab [action=reconnect]",null,__FILE__,__LINE__);
        shell_exec2("/usr/sbin/artica-phpfpm-service -msktutils >/dev/null 2>&1");

    }

    if(!is_file("/etc/cron.d/squid-negotiateauthenticator-5mn")) {
        $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("squid-negotiateauthenticator-5mn", "*/5 * * * *", "exec.negotiateauthenticator.php");
        system("/etc/init.d/cron reload");
    }
    return true;
}

function adker_watchdog():bool{
	if(is_file("/etc/cron.d/artica-ads-watchdog")){
		@unlink("/etc/cron.d/artica-ads-watchdog");
		shell_exec("/etc/init.d/cron reload");
	}

    $LockActiveDirectoryToKerberos= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
	if($LockActiveDirectoryToKerberos==1){
        LockActiveDirectoryToKerberos();
    }
    return true;
}



function HypercacheStatus():bool{
    $HyperCacheStoreID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoreID"));
    $tfile=PROGRESS_DIR."/HyperCacheStoreID.status";
    $l[]="[HYPERCACHE_STOREID]";
    $l[]="service_name=HYPERCACHE_STOREID";
    $l[]="service_cmd=/etc/init.d";
    $l[]="master_version=1.0";
    $l[]="service_disabled=$HyperCacheStoreID";
    $l[]="family=network";
    $l[]="watchdog_features=1";
    $l[]="installed=1";

    $pids=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN_ALL("storeid_file_rewrite");
    if(count($pids)==0){
        $l[]="running=0";
        @file_put_contents($tfile,@implode("\n",$l));
        return true;
    }
    $master_pid=0;
    $rss=0;$vm=0;$count=0;$uptime=null;
    foreach ($pids as $num=>$pid){
        if(!is_numeric($pid)){continue;}
        $newrss=$GLOBALS["CLASS_UNIX"]->PROCESS_MEMORY($pid,true);
        $rss=$rss+$newrss;
        $vm=$vm+$GLOBALS["CLASS_UNIX"]->PROCESS_CACHE_MEMORY($pid,true);
        $uptime=$GLOBALS["CLASS_UNIX"]->PROCESS_UPTIME($pid);
        $master_pid=$pid;
        $count++;
    }
    $l[]="master_pid=$master_pid";
    $l[]="running=1";
    $l[]="master_memory=$rss";
    $l[]="master_cached_memory=$vm";
    $l[]="processes_number=$count";
    $l[]=$uptime;
    $l[]="";
    if($GLOBALS["VERBOSE"]){echo __FUNCTION__."() Return ".count($l)." Lines\n";}
    @file_put_contents($tfile,@implode("\n",$l));
    return true;
}





function squid_compilation_params(){
    $squidbin=$GLOBALS["CLASS_UNIX"]->LOCATE_SQUID_BIN();
    $COMPILE_SQUID_TOKENS=array();
    exec("$squidbin -v 2>&1",$results);
    $text=@implode("\n", $results);
    if(preg_match("#configure options:\s+(.+)#is", $text,$re)){$text=$re[1];}
    if(preg_match_all("#'(.+?)'#is", $text, $re)){
        foreach ($re[1] as $index=>$line){
            if(preg_match("#(.+?)=(.*)#", $line,$ri)){
                $key=$ri[1];
                $value=$ri[2];
                $key=str_replace("--", "", $key);
                if(trim($value)==null){$value=true;}
                $COMPILE_SQUID_TOKENS[$key]=$value;
                continue;
            }
            $key=$line;
            $value=1;
            $key=str_replace("--", "", $key);
            if($GLOBALS["VERBOSE"]){echo "squid -v [$key] = `$value`\n";}
            $COMPILE_SQUID_TOKENS[$key]=$value;
        }

    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("COMPILE_SQUID_TOKENS",serialize($COMPILE_SQUID_TOKENS));
    return true;
}






function hypercache_logger(){
	if(is_file("/usr/share/artica-postfix/exec.init-tail-hypercache.php")){@unlink("/usr/share/artica-postfix/exec.init-tail-hypercache.php"); }
	if(is_file("/usr/share/artica-postfix/exec.hypercache-tail.php")){ @unlink("/usr/share/artica-postfix/exec.hypercache-tail.php"); }
}











function Windows_update_events($text,$line=0){
	$date=@date("H:i:s");
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0){return false;}
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	$logFile="/var/log/squid/windowsupdate.debug";
	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);@touch($logFile);@chown($logFile,"squid");@chgrp($logFile, "squid"); }
	$line="$date:[Scheduler/$line]:[{$GLOBALS["MYPID"]}]: $text";
	if($GLOBALS["VERBOSE"]){echo "$line\n";}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$line\n");
	@fclose($f);


}







//========================================================================================================================================================






function _statussquid($text=null){
	if(!isset($GLOBALS["MIPIDSQUID"])){$GLOBALS["MIPIDSQUID"]=getmypid();}
	
	$TIME=date("M d H:i:s");
	if($GLOBALS["VERBOSE"]){echo "$text\n";}
	$logFile="/var/log/artica-status-squid.log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>1000000){@unlink($logFile);}
	}
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$TIME [{$GLOBALS["MIPIDSQUID"]}] $text\n");
	@fclose($f);
}





function squid_nat(){
	if(!is_file("/etc/init.d/squid-nat")){return;}
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("squid27");
	if(!is_file($masterbin)){return;}
	$enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransparent27"));
	$l[]="[APP_SQUID_NAT]";
	$l[]="service_name=APP_SQUID_NAT";
	$l[]="master_version=".squid_27_version();
	$l[]="service_cmd=/etc/init.d/squid-nat";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);}
	$master_pid=squid_nat_pid();

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squid-nat reload >/dev/null 2>&1 &");
				squid_admin_mysql(0,"Squid-Cache NAT front-end not running, start it...",null,__FILE__,__LINE__);
			}
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"APP_SQUID_NAT");
	$l[]="";

	return implode("\n",$l);
}








//#############################################################################

// ======================================================================================


//====================================================================================
function squid_tasks():bool{
    $php=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
    $ionice=$GLOBALS["CLASS_UNIX"]->EXEC_NICE();
    $PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
    $RESTART_CRON=false;
    $ArticaP="/usr/share/artica-postfix";

	if(!is_file("/etc/cron.d/squid-client-list")){
		$f[]="MAILTO=\"\"";
		$f[]="10,20,30,40,50,0 * * * * root\t$ionice $php $ArticaP/exec.squidclient.mgr.clientlist.php >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/squid-client-list", @implode("\n", $f));
		@chmod("/etc/cron.d/squid-client-list",0644);
        $RESTART_CRON=true;
	}
	
	
	if(!is_file("/etc/cron.d/squid-run-c")){
        $f=array();
        $f[]="PATH=$PATH";
        $f[]="MAILTO=\"\"";
        $f[]="*/5 * * * * root\t$ionice $php $ArticaP/exec.squid.run.schedules.php >/dev/null 2>&1";
        $f[]="";
        @file_put_contents("/etc/cron.d/squid-run-c", @implode("\n", $f));
        @chmod("/etc/cron.d/squid-run-c",0644);
        $RESTART_CRON=true;
        unset($f);
	}





    if($RESTART_CRON) {
        UNIX_RESTART_CRON();
    }
    return true;

}
function squid_tasks_events($text,$function=null,$line=0){
	$filename=basename(__FILE__);
	$function=__CLASS__."/".$function;
	$GLOBALS["CLASS_UNIX"]->events("$text","/var/log/artica-stats-executor.log",false,$function,$line,$filename);
}

// ======================================================================================
function squid_nat_pid(){
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("squid27");
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/squid/squid-nat.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($masterbin." -f /etc/squid27/squid.conf");
}
function squid_27_version():bool{
	if(isset($GLOBALS["squid_27_version"])){return $GLOBALS["squid_27_version"];}
	$squidbin=$GLOBALS["CLASS_UNIX"]->find_program("squid27");
	if(!is_file($squidbin)){return "0.0.0";}
	exec("$squidbin -v 2>&1",$results);
	foreach ($results as $val){
		if(preg_match("#Squid Cache: Version\s+(.+)#", $val,$re)){
			$GLOBALS["squid_27_version"]=trim($re[1]);
			return $GLOBALS["squid_27_version"];
		}
	}
    return "";
}



function ss5_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/ss5/ss5.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("ss5");
	return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);
	
}
function ss5_version(){

	if(isset($GLOBALS["ss5_version"])){return $GLOBALS["ss5_version"];}
	exec("/usr/sbin/ss5 -v 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#SS5 Version\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS["ss5_version"]= $re[1];
			break;
		}
		
	}
	return $GLOBALS["ss5_version"];
}
function ss5(){
	$l[]="[APP_SS5]";
	$l[]="service_name=APP_SS5";

	if(!is_file("/usr/sbin/ss5")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SS5Installed", 0);
		$l[]="installed=0";
		$l[]="";
		return @implode("\n", $l);
		
	}
	if(!is_file("/var/lib/ss5/ss5/mod_socks4.so")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SS5Installed", 0);
		$l[]="installed=0";
		$l[]="";
		return @implode("\n", $l);
	}
	
	if(!is_file("/etc/init.d/ss5")){return "";}
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SS5Installed", 1);
	$enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSS5"));
	
	$l[]="master_version=".ss5_version();
	$l[]="service_cmd=/etc/init.d/ss5";
	$l[]="service_disabled=$enabled";
	$l[]="family=squid";
	if($enabled==0){
		if(is_file("/etc/init.d/ss5")){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ss5.php --uninstall");
		}
	}
	
	$master_pid=ss5_pid();
	
	if($GLOBALS["VERBOSE"]){echo "Enabled = $enabled PID: $master_pid\n";}
	if($enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			squid_admin_mysql(1, "Socks Proxy server MUST not running [action=stop]", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ss5 stop >/dev/null 2>&1 &");
			$l[]="running=1";
			$l[]=GetMemoriesOf($master_pid,"APP_SS5");
			$l[]="";
			return implode("\n",$l);
		}
	
		return implode("\n",$l)."\n";
	}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){squid_admin_mysql(0, "Socks Proxy server is {stopped} [{action}={start}]", null,__FILE__,__LINE__);}
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ss5 start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
	
		return implode("\n",$l);
	}
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"APP_SS5");
	$l[]="";
	return implode("\n",$l);
}





// ====================================================================================
