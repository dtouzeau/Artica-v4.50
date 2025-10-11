<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["FORCE_MIME"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.access.manager.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}


$unix=new unix();




if(is_file("/etc/artica-postfix/FROM_ISO")){
	if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){return false;}
}



$sock=new sockets();
$GLOBALS["RELOAD"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NO_USE_BIN"]=false;
$GLOBALS["REBUILD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["NOCACHES"]=false;
$GLOBALS["NOAPPLY"]=false;
$GLOBALS["NORELOAD"]=false;
$GLOBALS["SMOOTH"]=false;
$GLOBALS["RESTART"]=false;
$GLOBALS["BY_SCHEDULE"]=false;
$GLOBALS["NO_VERIF_CACHES"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["EMERGENCY"]=false;
$GLOBALS["NOUFDBG"]=false;
$GLOBALS["FORMETA"]=false;
$GLOBALS["NO_VERIF_ACLS"]=false;

CheckWatdogCron();

$GLOBALS["MYCOMMANDS"]=implode(" ",$argv);
WriteMyLogs("commands= {$GLOBALS["MYCOMMANDS"]}","MAIN",__FILE__,__LINE__);

if(preg_match("#--force-mime#",implode(" ",$argv))){$GLOBALS["FORCE_MIME"]=true;}
if(preg_match("#--smooth#",implode(" ",$argv))){$GLOBALS["SMOOTH"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--withoutloading#",implode(" ",$argv))){$GLOBALS["NO_USE_BIN"]=true;$GLOBALS["NORELOAD"]=true;}
if(preg_match("#--nocaches#",implode(" ",$argv))){$GLOBALS["NOCACHES"]=true;}
if(preg_match("#--noapply#",implode(" ",$argv))){$GLOBALS["NOCACHES"]=true;$GLOBALS["NOAPPLY"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--restart#",implode(" ",$argv))){$GLOBALS["RESTART"]=true;}
if(preg_match("#--byschedule#",implode(" ",$argv))){$GLOBALS["BY_SCHEDULE"]=true;}
if(preg_match("#--noverifcaches#",implode(" ",$argv))){$GLOBALS["NO_VERIF_CACHES"]=true;}
if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
if(preg_match("#--emergency#",implode(" ",$argv))){$GLOBALS["EMERGENCY"]=true;}
if(preg_match("#--noufdbg#",implode(" ",$argv))){$GLOBALS["NOUFDBG"]=true;}
if(preg_match("#--for-meta#",implode(" ",$argv))){$GLOBALS["FORMETA"]=true;}
if(preg_match("#--noverifacls#",implode(" ",$argv))){$GLOBALS["NO_VERIF_ACLS"]=true;}



if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

$squidbin=$unix->find_program("squid3");
$php5=$unix->LOCATE_PHP5_BIN();
if(!is_file($squidbin)){$squidbin=$unix->find_program("squid");}
$GLOBALS["SQUIDBIN"]=$squidbin;
$GLOBALS["CLASS_USERS"]=new usersMenus();
if($GLOBALS["VERBOSE"]){echo "squid binary=$squidbin\n";}
if(isset($argv[1])){
    if($argv[1]=="--active-requests"){die();}
    if($argv[1]=="--no-access-logs"){no_access_logs();exit;}
    if($argv[1]=="--service-pack"){exit();}
    if($argv[1]=="--security-limit"){security_limit();exit();}
    if($argv[1]=="--tests-caches"){test_caches();return false;}
    if($argv[1]=="--purge-dns"){purge_dns();return false;}
    if($argv[1]=="--import-acls"){import_acls($argv[2]);return false;}
    if($argv[1]=="--import-webfilter"){import_webfilter($argv[2]);return false;}
    if($argv[1]=="--quick-ban"){quick_bann();exit();}
    if($argv[1]=="--kreconfigure"){Reload_only_squid();exit();}
    if($argv[1]=="--artica-templates"){DefaultTemplatesInArtica();exit();}
    if($argv[1]=="--dump-tpl"){dump_templates();exit();}
    if($argv[1]=="--shm"){$GLOBALS["VERBOSE"]=true;echo $unix->TMPFS_CURRENTSIZE("/run/shm")."\n";}
    if($argv[1]=="--SquidReloadInpublicAlias"){SquidReloadInpublicAlias();exit;}
    
    
    if($argv[1]=="--disableUFDB"){disableUFDB();return false;}
    if($argv[1]=="--checks"){CheckConfig();return false;}
    if($argv[1]=="--notify-clients-proxy"){notify_remote_proxys();return false;}
    if($argv[1]=="--ping-clients-proxy"){notify_remote_proxys();return false;}
    if($argv[1]=="--reload-squid"){if($GLOBALS["VERBOSE"]){echo "reload in debug mode\n";} Reload_Squid();exit();}
    if($argv[1]=="--retrans"){retrans();exit();}
    if($argv[1]=="--certificate"){certificate_generate();exit();}
    if($argv[1]=="--caches"){BuildCaches();exit();}
    if($argv[1]=="--caches-reconstruct"){ReconstructCaches();exit();}
    if($argv[1]=="--compilation-params"){compilation_params();exit();}
    if($argv[1]=="--mysql-tpl"){DefaultTemplatesInArtica();exit();}
    if($argv[1]=="--tpl-save"){TemplatesInMysql();exit();}
    if($argv[1]=="--templates"){TemplatesInMysql();exit();}
    if($argv[1]=="--tpl-unique"){TemplatesUniqueInMysql($argv[2]);exit();}
    if($argv[1]=="--watchdog-config"){watchdog_config();exit();}
    if($argv[1]=="--build-schedules"){build_schedules();exit();}
    if($argv[1]=="--build-schedules-test"){build_schedules_tests();exit();}
    if($argv[1]=="--run-schedules"){run_schedules($argv[2]);exit();}
    if($argv[1]=="--schedules-extract"){extract_schedules();exit();}
    if($argv[1]=="--restart-squid"){restart_squid();exit();}
    
    if($argv[1]=="--change-value"){change_value($argv[2],$argv[3]);exit();}
    if($argv[1]=="--smooth-build"){$GLOBALS["FORCE"]=true;build_smoothly();exit();}
    if($argv[1]=="--reconfigure-squid"){Reload_Squid();exit();}
    if($argv[1]=="--remove-cache"){remove_cache($argv[2]);exit();}
    if($argv[1]=="--banddebug"){bandwithdebug();exit();}
    if($argv[1]=="--global-conf"){output_global_conf();exit();}
    if($argv[1]=="--ntlm"){dump_ntlm(true);exit();}
    if($argv[1]=="--test-notif"){echo test_notifs();exit();}
    if($argv[1]=="--ports-conversion"){$GLOBALS["REBUILD"]=true; PortsConversion();exit();}
    if($argv[1]=="--disable-cache"){disable_cache();exit();}
    if($argv[1]=="--check-temp"){CheckTempConfig();exit();}
    if($argv[1]=="--test-sarg"){test_sarg();exit();}
    if($argv[1]=="--pactester"){squid_pactester();exit();}
    if($argv[1]=="--cache-rules"){cache_rules();exit();}
    if($argv[1]=="--band"){bandwith_rules();exit();}
    if($argv[1]=="--cert"){BuildSquidCertificate();exit();}
    if($argv[1]=="--defaults-schedules"){Defaultschedules();exit();}
    if($argv[1]=="--reconfigure"){$argv[1]="--build";}
    if($argv[1]=="--build"){build();exit;}
    if($argv[1]=="--purge"){purge();exit;}
    if($argv[1]=="--compress-access-log"){compress_access_log($argv[2]);exit;}
    if($argv[1]=="--export-blacklists"){export_blacklists();exit;}
 }
writelogs("Unable to understand:`".@implode(" ", $argv)."`","MAIN",__FILE__,__LINE__);



function export_blacklists_progress($prc,$text):bool{
    $unix=new unix();
    $unix->framework_progress($prc,$text,"squid.export.progress");
    return true;
}
function export_blacklists():bool{
    export_blacklists_progress(30,"{exporting}...{please_wait}");
    $binary="/usr/share/artica-postfix/bin/sqlite-to-csv";
    @chmod($binary,0755);
    $database="/home/artica/SQLITE/proxy.db";
    $table="deny_websites";
    $target=PROGRESS_DIR."/$table.csv";
    if(is_file($target)){@unlink($target);}
    $cmd="$binary -database \"$database\" -table \"$table\" -csv \"$target\"";
    echo $cmd."\n";
    system("$cmd");
    if(!is_file($target)){
        return export_blacklists_progress(110,"{exporting}...{failed}");
    }
    return export_blacklists_progress(100,"{exporting}...{success}");
}

function compress_access_log_progress($prc,$text):bool{
    $unix=new unix();
    $unix->framework_progress($prc,$text,"squid.access.compress.progress");
    return true;
}
function compress_access_log($encoded_file):bool{

    if(!function_exists("base64_decode")){
        compress_access_log_progress(110,"base64_decode no such function");
        return false;
    }
    
    $fname=base64_decode($encoded_file);
    if(!is_file("/var/log/squid/$fname")){
        compress_access_log_progress(110,"[/var/log/squid/$fname]: No such file");
        return false;
    }
    compress_access_log_progress(50,"Compressing /var/log/squid/$fname");
    $unix=new unix();
    if(!$unix->compress("/var/log/squid/$fname",PROGRESS_DIR."/$fname.gz")){
        compress_access_log_progress(110,"/var/log/squid/$fname {faield}");
        return false;
    }
    compress_access_log_progress(100,"/var/log/squid/$fname {success}");
    return true;
}

function purge():bool    {
    $f=explode("\n",@file_get_contents("/etc/squid3/listen_ports.conf"));
    foreach ($f as $line){
        if(!preg_match("#^http_port.*?:([0-9]+).*?name=MyManagerPort#",$line,$re)){continue;}
        $port=$re[1];
        break;
    }
    $unix=new unix();
    $squidclient=$unix->find_program("squidclient");

    $cmd="$squidclient -p $port -h 127.0.0.1 mgr:ipcache 2>&1";
    exec($cmd,$results);
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^(.+?)\s+#",$line,$re)){continue;}
        echo "Purge http://{$re[1]}\n";
        $cmd="$squidclient -p $port -h 127.0.0.1 -m PURGE http://{$re[1]}";
        $unix->go_exec($cmd);
    }
    return true;
}


function CheckWatdogCron(){
	$nice=EXEC_NICE();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();

	if(!is_file("/etc/cron.d/artica-squid-watchdog")){
		$f=array();
		$f[]="MAILTO=\"\"";
		$f[]="3,6,9,11,13,15,17,19,21,23,25,27,29,31,33,35,37,39,41,43,45,47,49,51,55,57,59 * * * *  root $nice $php5 /usr/share/artica-postfix/exec.squid.watchdog.php >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/artica-squid-watchdog", @implode("\n", $f));
		WriteMyLogs("Creating Cron task cron.d/artica-squid-watchdog done",__FUNCTION__,__FILE__,__LINE__);
	}

	if(!is_file("/etc/cron.d/artica-ping-cloud")){
		$f=array();
		$f[]="MAILTO=\"\"";
		$f[]="15 0,2,4,6,8,10,12,14,16,18,20,22 * * * root $nice $php5 /usr/share/artica-postfix/exec.web-community-filter.php --bycron >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/artica-ping-cloud", @implode("\n", $f));
		WriteMyLogs("Creating Cron task cron.d/artica-ping-cloud done",__FUNCTION__,__FILE__,__LINE__);
	}
}
function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR ."/squid.access.center.progress";

	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

	if($GLOBALS["AD_PROGRESS"]>0){
		@file_put_contents(PROGRESS_DIR."/squid.ad.progress", serialize($array));
		@chmod(PROGRESS_DIR."/squid.ad.progress",0755);
	}

    $cachefile=PROGRESS_DIR ."/squid.access.center.progress";
    $array["POURC"]="30";
    $array["TEXT"]="({$pourc}%) - $text";
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);


}
function no_access_logs(){
    $SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
    $LogsWarninStop=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsWarninStop"));
    $SquidNoAccessLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNoAccessLogs"));
    if($SquidUrgency==1){return true;}
    if($LogsWarninStop==1){return true;}
    if($SquidNoAccessLogs==1){return true;}

    $unix=new unix();
    $PidFile="/etc/artica-postfix/pids/".__FUNCTION__.".pid";
    $PidFileTime="/etc/artica-postfix/pids/".__FUNCTION__.".time";
    $pid=$unix->get_pid_from_file($PidFile);
    if($unix->process_exists($pid,basename(__FILE__))){
        syslogsquid("no_access_logs():: Another artica script running pid $pid, aborting ...");
        return false;
    }

    @file_put_contents($PidFile,getmypid());

    if(is_file($PidFileTime)){
        $time=$unix->file_time_min($PidFileTime);
        if($time<10){return false;}
    }
    @unlink($PidFileTime);
    @file_put_contents($PidFileTime,time());

    $filesize=@filesize("/var/log/squid/access.log");
    if($filesize>10){return true;}

    $f[]="It seems the log file generated by the proxy is stopped.";
    $f[]="It should be locked (this problem can be seen on log rotation)";
    $f[]="Artica will restart the proxy service in order to fix this issue.";
    $f[]="This log file will be checked each 10 minutes.";
    $f[]="So the next alert will be in 10 minutes.";
    syslogsquid("{restarting_proxy_service} for empty realtime events ({$filesize}Bytes)");
    squid_admin_mysql(0,"Locked Proxy realtime events ({$filesize}Bytes) [{action}={reload}]",
    @implode("\n",$f),__FILE__,__LINE__);
    $unix->go_exec("/usr/sbin/artica-phpfpm-service -reload-proxy");
    return true;

}
function syslogsquid($text){
    if(!function_exists("syslog")){return false;}
    openlog("squid", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}


function SquidReloadInpublicAlias_progress($text,$pourc){
	$echotext=$text;

	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/SquidReloadInpublicAlias.progress";
	@mkdir(dirname($cachefile),0755,true);
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	if(!is_file($cachefile)){echo "!!! $cachefile No such file\n";}
	@chmod($cachefile,0755);
	sleep(1);
}
function SquidReloadInpublicAlias(){


	SquidReloadInpublicAlias_progress("{reloading_proxy_service}",15);
	squid_admin_mysql(1, "Proxy was reloaded by the Quick Alias reload", null,__FILE__,__LINE__);
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if( is_file($squidbin)){
		squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
		system("/usr/sbin/artica-phpfpm-service -reload-proxy");}

	$sock=new sockets();
	if($sock->EnableUfdbGuard()==1){
		SquidReloadInpublicAlias_progress("{reloading_webfilter_service}",50);
		squid_admin_mysql(1, "Reloading Web-Filtering service", null,__FILE__,__LINE__);
		system("/etc/init.d/ufdb reload --force");
	}
	SquidReloadInpublicAlias_progress("{done}",100);

}

function disable_cache(){


	$SquidDisableCaching=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableCaching"));
	if($SquidDisableCaching==1){
		squid_admin_mysql(0, "Temporary disable the cache system (by Web Interface)", null,__FILE__,__LINE__);

	}else{
		squid_admin_mysql(0, "Enable the cache system (by Web Interface)", null,__FILE__,__LINE__);
	}
    system("/usr/sbin/artica-phpfpm-service -proxy-build-caches");
	$squid_refresh_pattern=new squid_refresh_pattern();
	$squid_refresh_pattern->build();
    system("/usr/sbin/artica-phpfpm-service -proxy-parents");
	$HyperCacheSquid=new HyperCacheSquid();
	$HyperCacheSquid->build();
	$unix=new unix();
	system("/usr/sbin/artica-phpfpm-service -reload-proxy");
}



function build(){
    $unix=new unix();
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0){
        $unix->ToSyslog("Aborting script, Proxy is disabled",false,"squid");
        build_progress("{starting} {reconfigure} {failed}",110);
        return false;
    }

		build_progress("{reconfigure} (1)",2);
		if($GLOBALS["VERBOSE"]){echo "Running build...\n";}

		$forceCMD=null;
		$argv=null;

		if($unix->ServerRunSince()<3){
			squid_admin_mysql(1, "Aborting configuration, server just booting", implode(" ",$argv),__FILE__,__LINE__);
			echo "Server running less than 3mn, please try later\n";
			build_progress("{starting} {reconfigure} {failed}",110);
			return false;
		}
    $PHP=$unix->LOCATE_PHP5_BIN();
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        if(!$q->TABLE_EXISTS("proxy_ports")){
            shell_exec("$PHP /usr/share/artica-postfix/exec.convert-to-sqlite.php --proxy");
        }


		squid_admin_mysql(1, "{building_configuration} {APP_PROXY}", null,__FILE__,__LINE__);


		$mypid=getmypid();
		if(isset($argv[1])){$argv=$argv[1];}
		$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__).".*?$argv");
		if(count($pids)>2){
			build_progress("{already_process_exists_try_later}",110);
			foreach ($pids as $num=>$ligne){
				$cmdline=@file_get_contents("/proc/$num/cmdline");
				echo "Starting......: ".date("H:i:s")." [SERV]: [$mypid] Already process PID $num $cmdline exists..\n";
				echo "Starting......: ".date("H:i:s")." [SERV]: [$mypid] Running ".@file_get_contents("/proc/$num/cmdline")."\n";
			}
			exit();
		}

		$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
		if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
		if($EnableWebProxyStatsAppliance==1){notify_remote_proxys();}
		//Vérifie le compte utilisateur.
		//-----------------------------------------------------------------------------------------------
		$unix->CreateUnixUser("squid","squid","Squid Cache Service");
		$WindowsUpdateCachingDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsUpdateCachingDir");
		if($WindowsUpdateCachingDir==null){$WindowsUpdateCachingDir="/home/squid/WindowsUpdate";}
		@mkdir($WindowsUpdateCachingDir,0755,true);
		@chmod($WindowsUpdateCachingDir,0755);
		@chown($WindowsUpdateCachingDir,"squid");
		@chgrp($WindowsUpdateCachingDir,"squid");

		$MustHave[]="/etc/squid3/artica-meta/whitelist-nets.db";
		$MustHave[]="/var/logs/cache.log";
		$MustHave[]="/etc/squid3/squid-block.acl";
		$MustHave[]="/etc/squid3/allowed-user-agents.acl";
		$MustHave[]="/etc/squid3/GlobalAccessManager_auth.conf";
		$MustHave[]="/etc/squid3/icap.conf";
		$MustHave[]="/etc/squid3/GlobalAccessManager_url_rewrite.conf";
		$MustHave[]="/etc/squid3/GlobalAccessManager_deny_cache.conf";
		$MustHave[]="/etc/squid3/GlobalAccessManager_deny.conf";
		$MustHave[]="/etc/squid3/squid-block.acl";
		$MustHave[]="/etc/squid3/clients_ftp.acl";
		$MustHave[]="/etc/squid3/allowed-user-agents.acl";
		$MustHave[]="/etc/squid3/whitelisted-computers-by-mac.acl";



        foreach ($MustHave as $path){
			echo "Starting......: ".date("H:i:s")." [SYS]: checking $path\n";
			if(!is_file($path)){@touch($path);}
			@chown($path,"squid");
			@chgrp($path, "squid");
		}

		if($GLOBALS["FORCE"]){$forceCMD=" --force";}
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if(!is_file($squidbin)){
			build_progress("{squid_binary_not_found}",110);
			echo "Starting......: ".date("H:i:s")." [SERV]: Unable to stat squid binary, aborting..\n";
			exit();
		}


		$EXEC_TIME_FILE="/etc/artica-postfix/".basename(__FILE__).".build.time";
		if(!$GLOBALS["FORCE"]){
			$time=$unix->file_time_min($EXEC_TIME_FILE);
			if($time==0){
				build_progress("Failed! Only one config per minute !!!",110);
				echo "Starting......: ".date("H:i:s")." [SERV]: Only one config per minute...\n";
				exit();
			}

		}


		@unlink($EXEC_TIME_FILE);
		@file_put_contents($EXEC_TIME_FILE, time());
		if($GLOBALS["EMERGENCY"]){squid_admin_mysql(0, "Reconfiguring Proxy service after Emergency enabled", null,__FILE__,__LINE__);}

		$TimeStart=time();
		$EXEC_PID_FILE="/etc/artica-postfix/".basename(__FILE__).".build.pid";
		$pid=@file_get_contents($EXEC_PID_FILE);
		if($unix->process_exists($pid,basename(__FILE__))){
			$TimePid=$unix->PROCCESS_TIME_MIN($pid);
			if($TimePid>30){
				posix_kill(intval($pid),9);
			}else{
				if(!$GLOBALS["FORCE"]){
					print "Starting......: ".date("H:i:s")." Checking (L.".__LINE__.") Squid Already executed pid $pid since {$TimePid}mn ...\n";
					exit();
				}
			}
		}



		build_progress("{reconfigure} (1)",5);

		PortsConversion();
		squid_reconfigure_build_tool();
		build_progress("{reconfigure}",10);
		squid_pactester();
		build_progress("{reconfigure}",15);


		$childpid=posix_getpid();
		$sock=new sockets();
		$squid_user=SquidUser();
		$SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();
		$PHP=LOCATE_PHP5_BIN2();
		$NOHUP=$unix->find_program("nohup");
		build_progress("{reconfigure}",20);

		@file_put_contents($EXEC_PID_FILE,$childpid);
		if(is_file("/etc/squid3/mime.conf")){shell_exec("/bin/chown squid:squid /etc/squid3/mime.conf");}
		$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
		if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}

		if(!is_dir("/usr/share/squid-langpack")){TemplatesInMysql(true);exit;}
		echo "Starting......: ".date("H:i:s")." Checking squid kerberos authentification is set to $EnableKerbAuth\n";
		echo "Starting......: ".date("H:i:s")." Checking squid certificate\n";
		build_progress("{reconfigure} Check database",25);
		checkdatabase();
		build_progress("{reconfigure} {certificates}",30);
		certificate_generate();
		build_progress("{reconfigure}",40);
		echo "Starting......: ".date("H:i:s")." Instanciate squid library..\n";
		$squid=new squidbee();
		$squidbin=$unix->find_program("squid3");
		echo "Starting......: ".date("H:i:s")." checking squid binaries..\n";
		if(!is_file($squidbin)){$squidbin=$unix->find_program("squid");}
		echo "Starting......: ".date("H:i:s")." Binary: $squidbin\n";
		echo "Starting......: ".date("H:i:s")." Config: $SQUID_CONFIG_PATH\n";
		echo "Starting......: ".date("H:i:s")." User..: $squid_user\n";
		echo "Starting......: ".date("H:i:s")." Checking blocked sites\n";
		build_progress("{reconfigure} {building} NET ADS",45);
		shell_exec("$NOHUP $PHP ".basename(__FILE__)."/exec.squid.netads.php >/dev/null 2>&1 &");
		echo "Starting......: ".date("H:i:s")." Building master configuration\n";

		$squid->ASROOT=true;


		echo "Starting......: ".date("H:i:s")." Checking Watchdog\n";
		build_progress("{reconfigure} checking Watchdog settings",46);
		watchdog_config();
		build_progress("{reconfigure} build errors",47);

		build_progress("{reconfigure} Checking caches",48);
		BuildCaches(true);
		build_progress("{reconfigure} Check files and security",49);

		build_progress("{reconfigure} Building schedules",50);
		build_schedules(true);
		build_progress("{reconfigure} Building SSL passwords",89);
		build_sslpasswords();
		build_progress("{reconfigure} Building {GLOBAL_ACCESS_CENTER}",93);
		echo "Starting......: ".date("H:i:s")." Executing exec.squid.global.access.php --nochek\n";
		system("$PHP /usr/share/artica-postfix/exec.squid.global.access.php --nochek");
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ReconfigureProxy306",1);



		build_progress("{reconfigure} Building main configuration",94);
		if(!ApplyConfig()){
			build_progress("Apply configuration failed",110);
			echo "Starting......: ".date("H:i:s")." Apply configuration failed....\n";
			return false;
		}



		build_progress("{reconfigure} Wan Compressor Proxy service",95);
		system("$NOHUP $PHP /usr/share/artica-postfix/exec.wanproxy.php --build-squid >/dev/null 2>&1 &");




		build_progress("{checking_transparent_mode}",95);
		if($unix->IS_FIREHOLE_ACTIVE()){
			build_progress("{restarting_firewall}",95);
			system("$PHP /usr/share/artica-postfix/exec.firehol.php");
			system("/etc/init.d/firehol restart");
		}else{

			system("$PHP /usr/share/artica-postfix/exec.secure.gateway.php");

			if(is_file("/bin/artica-secure-gateway.sh")){
				build_progress("{restarting_firewall} (Secure gateway)",95);
				shell_exec("/bin/artica-secure-gateway.sh");
			}

		}

		build_progress("{checking_wccp_mode}",95);
		system("/usr/sbin/artica-phpfpm-service -wccp");


		build_progress("{reconfigure}",96);
		$GLOBALS["OUTPUT"]=true;
		if($GLOBALS["NOAPPLY"]){
			build_progress("{reconfiguring_proxy_service} {success}",100);
			return false;
		}


		if(!$GLOBALS["RESTART"]){
			build_progress("{reloading_service}",91);
			if(!$GLOBALS["NORELOAD"]){
				Reload_Squid();
			}
		}

		if($GLOBALS["RESTART"]){
			if(!$GLOBALS["NORELOAD"]){
				build_progress("{stopping_service}",91);
				system("$PHP /usr/share/artica-postfix/exec.squid.watchdog.php --stop $forceCMD --byForceReconfigure");
				build_progress("{starting_service}",93);
				system("$PHP /usr/share/artica-postfix/exec.squid.watchdog.php --start $forceCMD --byForceReconfigure");
				build_progress("{starting_service}",95);
			}
		}



		build_progress("{building} Cached Web frontend pages",97);
		shell_exec("$NOHUP $PHP ".basename(__FILE__)."/exec.cache.pages.php --force >/dev/null 2>&1 &");

		$BuildAllTemplatesDone=$sock->GET_INFO("BuildAllTemplatesDone");
		if(!is_numeric($BuildAllTemplatesDone)){$BuildAllTemplatesDone=0;}
		if($BuildAllTemplatesDone==0){
			build_progress("{building} Templates schedules",97);
			echo "Starting......: ".date("H:i:s")." scheduling Building templates\n";
			sys_THREAD_COMMAND_SET("$PHP ". __FILE__." --tpl-save");
			$sock->SET_INFO("BuildAllTemplatesDone", 1);
		}

		build_progress("{building} Templates",98);
		sys_THREAD_COMMAND_SET("$PHP ". __FILE__." --mysql-tpl");


		build_progress("{reconfiguring_proxy_service} {success}",100);

		echo "Starting......: ".date("H:i:s")." Done (Took: ".$unix->distanceOfTimeInWords($TimeStart,time()).")\n";
		exit();
}






function change_value($key,$val){
	$squid=new squidbee();
	$squid->global_conf_array[$key]=$val;
	$squid->SaveToLdap();
	echo "Starting......: ".date("H:i:s")." Squid change $key to $val (squid will be restarted)\n";

}





function build_sslpasswords(){

	$q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
	$sql="SELECT `keyPassword`,`CommonName` FROM sslcertificates WHERE LENGTH(keyPassword)>0";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "$q->mysql_error\n";
	}else{
		foreach ($results as $index=>$ligne){
			$array["/etc/squid3/{$ligne["CommonName"]}.key"]=$ligne["keyPassword"];
		}
	}
	@file_put_contents("/etc/squid3/sslpass", serialize($array));

}
function squid_pactester(){
	if(is_file("/usr/bin/pactester")){return false;}
	if(!is_file("/usr/share/artica-postfix/bin/install/squid/pactester.tar.gz")){return false;}
	$unix=new unix();
	$ldconfig=$unix->find_program("ldconfig");
	$tar=$unix->find_program("tar");
	shell_exec("$tar -xhf /usr/share/artica-postfix/bin/install/squid/pactester.tar.gz -C /");
	if(!is_file("/usr/bin/pactester")){return false;}
	@chmod("/usr/bin/pactester", 0755);
	shell_exec("$ldconfig >/dev/null 2>&1");
}

function output_global_conf(){
	$sock=new sockets();
	echo $sock->GET_INFO("ArticaSquidParameters");

}

function build_smoothly():bool{
		ApplyConfig(true);
		Reload_Squid();
        return true;
}






function watchdog($direction)
{
}


function locate_ssl_crtd(){
	return locate_generic_bin("ssl_crtd");


}

function locate_generic_bin($program){
	$unix=new unix();
	return $unix->squid_locate_generic_bin($program);

}



function remove_cache($cacheenc){
	$unix=new unix();
	$PidFile="/etc/artica-postfix/pids/".md5("remove-$cacheenc").".pid";

	$pid=$unix->get_pid_from_file($PidFile);
	if($unix->process_exists($pid,basename(__FILE__))){
		WriteToSyslogMail("remove_cache():: Another artica script running pid $pid, aborting ...", basename(__FILE__));
		return false;
	}

	$directory=base64_decode($cacheenc);
	if(!is_dir($directory)){WriteToSyslogMail("remove_cache():: $directory no such directory", basename(__FILE__));return false;}
	$rm=$unix->find_program("rm");
	shell_exec("$rm -rf $directory");
	ApplyConfig();
	shell_exec('/etc/init.d/artica-postfix restart squid-cache');


}
function SQUID_PID(){
	$unix=new unix();
	$squidbin=$unix->find_program("squid");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	$pid=$unix->get_pid_from_file($unix->LOCATE_SQUID_PID());
	if(!$unix->process_exists($pid)){
		$pid=$unix->PIDOF($squidbin);
	}

	return $pid;

}

function Start_squid(){
	system("/usr/sbin/artica-phpfpm-service -start-proxy");


}



function Defaultschedules($aspid=false){
	$PidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$PidTime="/etc/artica-postfix/pids/exec.squid.php.Defaultschedules.time";
	if($GLOBALS["VERBOSE"]){echo "$PidTime\n";}
	$unix=new unix();
	if(!$aspid){
		$pid=$unix->get_pid_from_file($PidFile);
		if($pid<>getmypid()){
			if($unix->process_exists($pid,basename(__FILE__))){
				echo "Starting......: ".date("H:i:s")." Blacklists: Another artica script running pid $pid, aborting ...\n";
				WriteToSyslogMail("build_blacklists():: Another artica script running pid $pid, aborting ...", basename(__FILE__));
				return false;
			}
		}
	}

	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($PidTime);
		if($time<120){return false;}
	}
	@unlink($PidTime);
	@file_put_contents($PidTime, time());

	$q=new mysql_squid_builder();
	$q->CheckDefaultSchedules();
}










function build_progress_wb($text,$pourc){
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"squid.wb.progress");
}
function build_progress_reload($text,$pourc){
	if($GLOBALS["VERBOSE"]){echo "{$pourc}% $text\n";}

	echo "{$pourc}% $text\n";
	$cachefile=PROGRESS_DIR."/squid.reload.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function build_progress_rotation($text,$pourc){
	if($GLOBALS["VERBOSE"]){echo "{$pourc}% $text\n";}
	if(!$GLOBALS["PROGRESS"]){return false;}
	echo "{$pourc}% $text\n";
	$cachefile=PROGRESS_DIR."/squid.rotate.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);
}
function build_progress_schedules($text,$pourc){
	if($GLOBALS["VERBOSE"]){echo "{$pourc}% $text\n";}
	if(!$GLOBALS["PROGRESS"]){return false;}
	echo "{$pourc}% $text\n";
	$cachefile=PROGRESS_DIR."/squid.databases.schedules.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}





function Reload_only_squid(){
	$unix=new unix();
	$results=array();
	$force=null;
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if($SQUIDEnable==0){echo "Proxy is disabled\n";exit();}
	if(!is_file($GLOBALS["SQUIDBIN"])){
		$GLOBALS["SQUIDBIN"]=$unix->find_program("squid");
		if(!is_file($GLOBALS["SQUIDBIN"])){$GLOBALS["SQUIDBIN"]=$unix->find_program("squid3");}
	}

	if($GLOBALS["FORCE"]){$force=" --force";}
	build_progress_reload("{reloading}",50);
	squid_watchdog_events("Reconfiguring Proxy parameters...");
	squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
	$cmd="/etc/init.d/squid reload$force --script=".basename(__FILE__);
    $results=$unix->go_exec_out($cmd);


	foreach ($results as $num=>$val){
		echo "Starting......: ".date("H:i:s")." [RELOAD]: $val\n";

	}
	build_progress_reload("{reloading}",70);
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"exec.logfile_daemon.php\" 2>&1",$results2);
	foreach ($results2 as $val){
		if(preg_match("#pgrep#", $val)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $val,$re)){continue;}
		$pid=$re[1];
		$processtime=$unix->PROCCESS_TIME_MIN($pid);

		if($processtime<1){
			echo "Starting......: ".date("H:i:s")." [RELOAD]: exec.logfile_daemon.php $pid running since {$processtime}Mn\n";
			continue;}
		echo "Starting......: ".date("H:i:s")." [RELOAD]: Kill exec.logfile_daemon.php $pid running since {$processtime}Mn\n";
		unix_system_kill_force($pid);

	}

	$EnableTransparent27=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransparent27"));
	if($EnableTransparent27==1){
		if(is_file("/etc/init.d/squid-nat")){
		system("/etc/init.d/squid-nat reload --script=".basename(__FILE__));}
	}
	build_progress_reload("{done}",100);

}
function squid_watchdog_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}


function Reload_Squid_Reconfigure(){
    $unix=new unix();
    $squidbin=$unix->LOCATE_SQUID_BIN();
    squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
    $cmd="/usr/sbin/artica-phpfpm-service -reload-proxy";
    $unix->framework_exec($cmd);
}

function Reload_Squid(){
	if($GLOBALS["NORELOAD"]){return false;}
	$force=null;
	if($GLOBALS["FORCE"]){$force=" --force";}
	$unix=new unix();


	if($unix->ServerRunSince()>3){
        Reload_Squid_Reconfigure();
    }
	$EnableTransparent27=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransparent27"));
	if($EnableTransparent27==1){
		if(is_file("/etc/init.d/squid-nat")){
            $unix->go_exec("/etc/init.d/squid-nat reload$force --script=".basename(__FILE__));
		}
	}
    return true;
}


function CICAP_PID_PATH(){
	return '/var/run/c-icap/c-icap.pid';
}

function squidclamav(){
	$squid=new squidbee();
	$sock=new sockets();
	$unix=new unix();
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
	$users=$GLOBALS["CLASS_USERS"];
	$SquidGuardIPWeb=$sock->GET_INFO("SquidGuardIPWeb");
	if($SquidGuardIPWeb==null){$SquidGuardIPWeb="http://$users->hostname:9020/exec.squidguard.php";}


	$conf[]="squid_ip 127.0.0.1";
	$conf[]="squid_port $squid->listen_port";
	$conf[]="logfile /var/log/squid/squidclamav.log";
	$conf[]="debug 0";
	$conf[]="stat 0";
	$conf[]="clamd_local ".$unix->LOCATE_CLAMDSOCKET();
	$conf[]="#clamd_ip 192.168.1.5";
	$conf[]="#clamd_port 3310";
	$conf[]="maxsize 5000000";
	$conf[]="redirect $SquidGuardIPWeb";
	if($squid->enable_squidguard==1){
		$conf[]="squidguard $users->SQUIDGUARD_BIN_PATH";
	}else{
		if($squid->enable_UfdbGuard==1){
			$conf[]="squidguard $users->ufdbgclient_path";
		}
	}
	$conf[]="maxredir 30";
	$conf[]="timeout 60";
	$conf[]="useragent Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)";
	$conf[]="trust_cache 1";
	$conf[]="";
	$conf[]="# Do not scan standard HTTP images";
	$conf[]="abort ^.*\.(ico|gif|png|jpg)$";
	$conf[]="abortcontent ^image\/.*$";
	$conf[]="# Do not scan text and javascript files";
	$conf[]="abort ^.*\.(css|xml|xsl|js|html|jsp)$";
	$conf[]="abortcontent ^text\/.*$";
	$conf[]="abortcontent ^application\/x-javascript$";
	$conf[]="# Do not scan streaming videos";
	$conf[]="abortcontent ^video\/mp4";
	$conf[]="abortcontent ^video\/x-flv$";
	$conf[]="# Do not scan pdf and flash";
	$conf[]="#abort ^.*\.(pdf|swf)$";
	$conf[]="";
	$conf[]="# Do not scan sequence of framed Microsoft Media Server (MMS)";
	$conf[]="abortcontent ^.*application\/x-mms-framed.*$";
	$conf[]="";
	$conf[]="# White list some sites";
	$conf[]="whitelist .*\.clamav.net";
	@file_put_contents("/etc/squidclamav.conf",@implode("\n",$conf));
	echo "Starting......: ".date("H:i:s")." Squid building squidclamav.conf configuration done\n";
}

function GetLocalCaches(){
	$unix=new unix();
	return $unix->SQUID_CACHE_FROM_SQUIDCONF();
}



function ReconstructCaches(){
	$unix=new unix();
	echo "Starting......: ".date("H:i:s")."  reconstruct caches\n";
	$GetCachesInsquidConf=$unix->SQUID_CACHE_FROM_SQUIDCONF();
	if(count($GetCachesInsquidConf)==0){return false;}
	foreach ($GetCachesInsquidConf as $dir=>$type){
		if(is_dir($dir)){
			echo "Starting......: ".date("H:i:s")." Squid removing directory $dir\n";
			shell_exec("/bin/rm -rf $dir");
		}
	}
	echo "Starting......: ".date("H:i:s")."  Building caches\n";
	BuildCaches();

}


function BuildCaches($NOTSTART=false){
	echo "Starting......: ".date("H:i:s")." Squid Check *** caches ***\n";
	$unix=new unix();
	$sock=new sockets();
	$su_bin=$unix->find_program("su");
	$chown=$unix->find_program("chown");
	$nohup=$unix->find_program("nohup");
	$TimeFileChown="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
	if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}
	$squid_user=SquidUser();
	writelogs("Using squid user: \"$squid_user\"",__FUNCTION__,__FILE__,__LINE__);
	writelogs("$chown cache directories...",__FUNCTION__,__FILE__,__LINE__);
	$unix->chown_func($squid_user,null, "/etc/squid3/*");
	if(is_dir("/usr/share/squid-langpack")){$unix->chown_func($squid_user,null, "/usr/share/squid-langpack");}

	$GetCachesInsquidConf=$unix->SQUID_CACHE_FROM_SQUIDCONF();

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Squid ".count($GetCachesInsquidConf)." caches to check\n";}
	writelogs(count($GetCachesInsquidConf)." caches to check",__FUNCTION__,__FILE__,__LINE__);

	$MustBuild=false;
	if($SquidBoosterMem>0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Squid Cache booster set to {$SquidBoosterMem}Mb\n";}
		@mkdir("/var/squid/cache_booster",0755,true);
		@chown("/var/squid/cache_booster", "squid");
		@chgrp("/var/squid/cache_booster", "squid");
		if(!is_dir("/var/squid/cache_booster/00")){
			echo "Starting......: ".date("H:i:s")." Squid *** /var/squid/cache_booster/00 *** No such directory ask to rebuild caches\n";
			$MustBuild=true;
		}

	}

	$nice=$unix->EXEC_NICE();
	$rm=$unix->find_program("rm");
	if(!$GLOBALS["NOCACHES"]){
		$TimeFileChownTime=$unix->file_time_min($TimeFileChown);
		$SH[]="#!/bin/sh";
		foreach ($GetCachesInsquidConf as $CacheDirectory=>$type){
			if(trim($CacheDirectory)==null){continue;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Squid Check *** $CacheDirectory ***\n";}
			$subdir=basename($CacheDirectory);
			$MainDir=dirname($CacheDirectory);
			if(isDirInFsTab($MainDir)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Squid Check *** $MainDir -> Mounted ? ***\n";}
			}

			if(!is_dir($CacheDirectory)){
				@mkdir($CacheDirectory,0755,true);
				$MustBuild=true;
			}

			build_progress("{reconfigure} Checking $CacheDirectory",86);
			$SH[]="$nice $chown -R $squid_user:$squid_user $CacheDirectory";
			@chmod($CacheDirectory, 0755);



		}

		$TMPFILE=$unix->FILE_TEMP();
		$SH[]="$rm -f $TMPFILE.sh";
		@file_put_contents("$TMPFILE.sh", @implode("\n", $SH));
		@chmod("$TMPFILE.sh",0755);
		build_progress("{reconfigure} Checking $TMPFILE.sh ok",86);
		shell_exec("$nohup $TMPFILE.sh >/dev/null 2>&1 &");
		$SH=array();

	}
	if($unix->file_time_min($TimeFileChown)>120){
		@unlink($TimeFileChown);
		@file_put_contents($TimeFileChown, time());
	}


	if(!$GLOBALS["NOCACHES"]){$MustBuild=false;return false;}


	if(!$MustBuild){

		echo "Starting......: ".date("H:i:s")." Squid all caches are OK\n";
		return false;
	}


	if(preg_match("#(.+?):#",$squid_user,$re)){$squid_uid=$re[1];}else{$squid_uid="squid";}
	writelogs("Stopping squid...",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("/etc/init.d/artica-postfix stop squid-cache");
	writelogs("Building caches with user: \"$squid_uid\"",__FUNCTION__,__FILE__,__LINE__);
	writelogs("$su_bin $squid_uid -c \"{$GLOBALS["SQUIDBIN"]} -z\" 2>&1",__FUNCTION__,__FILE__,__LINE__);
	exec("$su_bin $squid_uid -c \"{$GLOBALS["SQUIDBIN"]} -z\" 2>&1",$results);

    foreach ($results as $agent=>$val){
			writelogs("$val",__FUNCTION__,__FILE__,__LINE__);
	}


	writelogs("Send Notifications",__FUNCTION__,__FILE__,__LINE__);
	send_email_events("Squid Cache: reconfigure caches","Here it is the results\n",@implode("\n",$results),"proxy");
	writelogs("Starting squid",__FUNCTION__,__FILE__,__LINE__);

	unset($results);
	if(!$NOTSTART){
		reconfigure_squid();
	}

    return true;

}



function isDirInFsTab($directory){
	$directoryRegex=$directory;
	$directoryRegex=str_replace("/", "\/", $directoryRegex);
	$directoryRegex=str_replace(".", "\.", $directoryRegex);
	$f=explode("\n", @file_get_contents("/etc/fstab"));
	foreach ($f as $index=>$val){
		if(preg_match("#^(.+)\s+$directoryRegex#", $val,$re)){
			echo "Starting......: ".date("H:i:s")." Squid Check $directory must be mounted on {$re[1]}\n";
			return true;
		}

	}
	return false;
}



function security_limit():bool{
    no_suid_monitor();
    $unix=new unix();
    return $unix->SystemSecurityLimitsConf();
}

function no_suid_monitor():bool{
    $Monitfile      = "/etc/monit/conf.d/APP_SQUID_4755.monitrc";
    if($Monitfile){@unlink($Monitfile);}
    return true;
}

function CheckTempConfig(){
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$TEMPDIR=$unix->TEMP_DIR();

	$tempsquid="$TEMPDIR/squid.conf";
	@mkdir($TEMPDIR,0770,true);
	@chmod($TEMPDIR, 0770);

	if(!is_dir("/tmp")){@mkdir("/tmp",0755,true);}

	if(!is_file($tempsquid)){

		$squid=new squidbee();
		$conf=$squid->BuildSquidConf();
		$conf=str_replace("\n\n", "\n", $conf);
		@file_put_contents("$tempsquid", $conf);
	}
	@chown($tempsquid, 'squid');
	@chgrp($tempsquid, 'squid');
	$cmd="$squidbin -f $tempsquid -k parse 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);

	foreach ($results as $index=>$ligne){
		if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
			echo "DETECTD: FAILED\n";
			echo "LINE \"$ligne\"\n";
			if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
				$Buggedline=intval($ri[1]);
				$tt=explode("\n",@file_get_contents("$tempsquid"));
				for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
					$lineNumber=$i+1;
					if(trim($tt[$i])==null){continue;}
					echo "[line:$lineNumber]: $tt[$i]\n";}
				}
			return false;
		}

		if(preg_match("#ERROR: Failed#", $ligne)){
			echo "FAILED\n";
			echo "$ligne\n";
			return false;
		}

	}

	echo "SUCCESS\n";

}


function ApplyConfig($smooth=false){
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Invoke ApplyConfig function", basename(__FILE__));}
	$unix=new unix();
	$ulimit=$unix->find_program("ulimit");
	if(is_file($ulimit)){
		shell_exec("$ulimit -HSd unlimited");
	}else{
		echo "Starting......: ".date("H:i:s")." [SYS]: Squid ulimit no such binary...\n";
	}

	echo "Starting......: ".date("H:i:s")." [SYS]: Squid apply Checks security limits\n";
	build_progress("{reconfigure} Security limits",47);
	security_limit();
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid Checking Remote appliances...\n";
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$squidbin=$unix->find_program("squid");
	$SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();

	echo "Starting......: ".date("H:i:s")." [SYS]: Squid loading libraires...\n";
	$sock=new sockets();
	$squid=new squidbee();


	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid binary: `$squidbin`\n";
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid Conf..: `$SQUID_CONFIG_PATH`\n";
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid php...: `$php5`\n";
	echo "Starting......: ".date("H:i:s")." [SYS]: Squid nohup.: `$nohup`\n";


	$DenySquidWriteConf=$sock->GET_INFO("DenySquidWriteConf");
	if(!is_numeric($DenySquidWriteConf)){$DenySquidWriteConf=0;}

	echo "Starting......: ".date("H:i:s")." [SYS]: Squid Checking `DenySquidWriteConf` = $DenySquidWriteConf\n";

	if(!is_dir("/usr/share/squid-langpack")){
		echo "Starting......: ".date("H:i:s")." [SYS]: Squid Checking Templates from MySQL\n";
		$unix->THREAD_COMMAND_SET("$php5 ".__FILE__." --tpl-save");
	}


	echo "Starting......: ".date("H:i:s")." [SYS]: Squid Build blocked Websites list...\n";


	if(!is_dir("/etc/squid3/artica-meta")){@mkdir("/etc/squid3/artica-meta",0755,true);}
	if(!is_file("/etc/squid3/artica-meta/whitelist-net.db")){@touch("/etc/squid3/artica-meta/whitelist-net.db");}
	if(!is_file("/etc/squid3/artica-meta/whitelist-domains.db")){@touch("/etc/squid3/artica-meta/whitelist-domains.db");}


	build_progress("{reconfigure} Whitelisted browsers",50);
	acl_whitelisted_browsers();
	build_progress("{reconfigure} allowed browsers",51);
	acl_allowed_browsers();
	build_progress("{reconfigure} FTP clients ACLs",55);
	acl_clients_ftp();
	echo "Starting......: ".date("H:i:s")." [SYS]:Squid building main configuration done\n";
	build_progress("{reconfigure} Check files and security",58);

	$tar=$unix->find_program("tar");
	if($GLOBALS["NOAPPLY"]){$DenySquidWriteConf=0;}

	$tempsquid=$unix->TEMP_DIR()."/squid.conf";
	if(is_file($tempsquid)){@unlink($tempsquid);}

	if($DenySquidWriteConf==0){
			$squid->CURRENT_PROGRESS=79;
			$squid->MAX_PROGRESS=79;
			$squid->BuildSquidConf();
			$sock->TOP_NOTIFY("{squid_parameters_was_saved}","info");
			build_progress("{writing_templates}",79);
			$cmd=$unix->LOCATE_PHP5_BIN()." ".__FILE__." --templates --noreload";
			$unix->THREAD_COMMAND_SET($cmd);
	}

	build_progress("{checking}: squidclamav",79);
	if(!$smooth){squidclamav();}

	build_progress("{checking}: {certificates}",79);
	if(!$smooth){certificate_generate();}
	shell_exec("$nohup /usr/sbin/artica-phpfpm-service -reconfigure-syslog >/dev/null 2>&1 &");


	if(is_file("/root/squid-good.tgz")){@unlink("/root/squid-good.tgz");}
	chdir("/etc/squid3");
	shell_exec("cd /etc/squid3");
	shell_exec("tar -czf /root/squid-good.tgz *");
	chdir("/root");
	shell_exec("cd /root");

	return true;

}



function acl_clients_ftp(){
	$q=new mysql();
	$sql="SELECT * FROM squid_white WHERE task_type='FTP_RESTR' ORDER BY ID DESC";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){return false;}

	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if(!preg_match("#FTP_RESTR:(.+)#",$ligne["uri"],$re)){continue;}
		$f[]=$re[1];
	}
	@file_put_contents("/etc/squid3/clients_ftp.acl",@implode("\n",$f));

}

function acl_allowed_browsers(){
	$sql="SELECT uri FROM squid_white WHERE task_type='USER_AGENT_BAN_WHITE' ORDER BY ID DESC";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}

	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$string=trim($ligne["uri"]);
		if($string==null){continue;}
		$string=str_replace(".","\.",$string);
		$string=str_replace("(","\(",$string);
		$string=str_replace(")","\)",$string);
		$string=str_replace("/","\/",$string);
		$f[]=$string;
	}
	@file_put_contents("/etc/squid3/allowed-user-agents.acl",@implode("\n",$f));
}

function acl_whitelisted_browsers(){
	$sql="SELECT uri FROM squid_white WHERE task_type='AUTH_WL_USERAGENTS'";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}

	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$arrayUserAgents[$ligne["uri"]]=1;
	}

	if(!isset($arrayUserAgents)){
		echo "Starting......: ".date("H:i:s")." Whitelisted User-Agents: 0\n";
		@file_put_contents("/etc/squid3/white-listed-user-agents.acl","");
		return false;
	}

	if(!is_array($arrayUserAgents)){
		echo "Starting......: ".date("H:i:s")." Whitelisted User-Agents: 0\n";
		@file_put_contents("/etc/squid3/white-listed-user-agents.acl","");
		return false;
	}

 foreach ($arrayUserAgents as $agent=>$val){
		$sql="SELECT unique_key,`string` FROM `UserAgents` WHERE browser='$agent' ORDER BY string";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$string=trim($ligne["string"]);
			if($string==null){continue;}
			$string=str_replace(".","\.",$string);
			$string=str_replace("(","\(",$string);
			$string=str_replace(")","\)",$string);
			$string=str_replace("/","\/",$string);
			$f[]=$string;
		}
	}
	echo "Starting......: ".date("H:i:s")." Whitelisted User-Agents: ". count($arrayUserAgents)." (". count($f)." patterns)\n";
	@file_put_contents("/etc/squid3/white-listed-user-agents.acl",@implode("\n",$f));


}


function retrans(){
	$unix=new unix();
	$array=$unix->getDirectories("/tmp");
	foreach ($array as $num=>$ligne){
		if(preg_match("#(.+?)\/temporaryFolder\/bases\/av#",$ligne,$re)){
			$folder=$re[1];
		}
	}
	if(is_dir($folder)){
		$cmd=$unix->find_program("du")." -h -s $folder 2>&1";
		exec($cmd,$results);
		$text=trim(implode(" ",$results));
		if(preg_match("#^([0-9\.\,A-Z]+)#",$text,$re)){
			$dbsize=$re[1];
		}
	}else{
		$dbsize="0M";
	}

	echo $dbsize;
}


function certificate_conf(){
	include_once('ressources/class.ssl.certificate.inc');
	$ssl=new ssl_certificate();
	$array=$ssl->array_ssl;
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
	$users=$GLOBALS["CLASS_USERS"];
	$cc=$array["artica"]["country"]."_".$array["default_ca"]["countryName_value"];




		$country_code="US";
		$contryname="Delaware";
		$locality="Wilmington";
		$organizationalUnitName="Artica Web Proxy Unit";
		$organizationName="Artica";
		$emailAddress="root@$users->hostname";
		$commonName=$users->hostname;



		if(preg_match("#(.+?)_(.+?)$#",$cc,$re)){
			$contryname=$re[1];
			$country_code=$re[2];
		}
		if($array["server_policy"]["localityName"]<>null){$locality=$array["server_policy"]["localityName"];}
		if($array["server_policy"]["organizationalUnitName"]<>null){$organizationalUnitName=$array["server_policy"]["organizationalUnitName"];}
		if($array["server_policy"]["emailAddress"]<>null){$emailAddress=$array["server_policy"]["emailAddress"];}
		if($array["server_policy"]["organizationName"]<>null){$organizationName=$array["server_policy"]["organizationName"];}
		if($array["server_policy"]["commonName"]<>null){$commonName=$array["server_policy"]["commonName"];}

		@mkdir("/etc/squid3/ssl/new",0666,true);

		$conf[]="[ca]";
		$conf[]="default_ca=default_db";
		$conf[]="unique_subject=no";
		$conf[]="";
		$conf[]="[default_db]";
		$conf[]="dir=.";
		$conf[]="certs=.";
		$conf[]="new_certs_dir=/etc/squid3/ssl/new";
		$conf[]="database= /etc/squid3/ssl/ca.index";
		$conf[]="serial = /etc/squid3/ssl/ca.serial";
		$conf[]="RANDFILE=.rnd";
		$conf[]="certificate=/etc/squid3/ssl/key.pem";
		$conf[]="private_key=/etc/squid3/ssl/ca.key";
		$conf[]="default_days= 730";
		$conf[]="default_crl_days=30";
		$conf[]="default_md=md5";
		$conf[]="preserve=no";
		$conf[]="name_opt=ca_default";
		$conf[]="cert_opt=ca_default";
		$conf[]="unique_subject=no";
		$conf[]="policy=policy_match";
		$conf[]="";
		$conf[]="[server_policy]";
		$conf[]="countryName=supplied";
		$conf[]="stateOrProvinceName=supplied";
		$conf[]="localityName=supplied";
		$conf[]="organizationName=supplied";
		$conf[]="organizationalUnitName=supplied";
		$conf[]="commonName=supplied";
		$conf[]="emailAddress=supplied";
		$conf[]="";
		$conf[]="[server_cert]";
		$conf[]="subjectKeyIdentifier=hash";
		$conf[]="authorityKeyIdentifier=keyid:always";
		$conf[]="extendedKeyUsage=serverAuth,clientAuth,msSGC,nsSGC";
		$conf[]="basicConstraints= critical,CA:false";
		$conf[]="";
		$conf[]="[user_policy]";
		$conf[]="commonName=supplied";
		$conf[]="emailAddress=supplied";
		$conf[]="";
		$conf[]="[user_cert]";
		$conf[]="subjectAltName=email:copy";
		$conf[]="basicConstraints= critical,CA:false";
		$conf[]="authorityKeyIdentifier=keyid:always";
		$conf[]="extendedKeyUsage=clientAuth,emailProtection";
		$conf[]="";
		$conf[]="[req]";
		$conf[]="default_bits=1024";
		$conf[]="default_keyfile=ca.key";
		$conf[]="distinguished_name=default_ca";
		$conf[]="x509_extensions=extensions";
		$conf[]="string_mask=nombstr";
		$conf[]="req_extensions=req_extensions";
		$conf[]="input_password=secret";
		$conf[]="output_password=secret";
		$conf[]="";
		$conf[]="[default_ca]";
		$conf[]="countryName=Country Code";
		$conf[]="countryName_value=$country_code";
		$conf[]="countryName_min=2";
		$conf[]="countryName_max=2";
		$conf[]="stateOrProvinceName=State Name";
		$conf[]="stateOrProvinceName_value=$contryname";
		$conf[]="localityName=Locality Name";
		$conf[]="localityName_value=$locality";
		$conf[]="organizationName=Organization Name";
		$conf[]="organizationName_value=$organizationName";
		$conf[]="organizationalUnitName=Organizational Unit Name";
		$conf[]="organizationalUnitName_value=$organizationalUnitName";
		$conf[]="commonName=Common Name";
		$conf[]="commonName_value=$commonName";
		$conf[]="commonName_max=64";
		$conf[]="emailAddress=Email Address";
		$conf[]="emailAddress_value=$emailAddress";
		$conf[]="emailAddress_max=".strlen($emailAddress);
		$conf[]="unique_subject=no";
		$conf[]="";
		$conf[]="[extensions]";
		$conf[]="subjectKeyIdentifier=hash";
		$conf[]="authorityKeyIdentifier=keyid:always";
		$conf[]="basicConstraints=critical,CA:false";
		$conf[]="";
		$conf[]="[req_extensions]";
		$conf[]="nsCertType=objsign,email,server";
		$conf[]="";
		$conf[]="[CA_default]";
		$conf[]="policy=policy_match";
		$conf[]="";
		$conf[]="[policy_match]";
		$conf[]="countryName=match";
		$conf[]="stateOrProvinceName=match";
		$conf[]="organizationName=match";
		$conf[]="organizationalUnitName=optional";
		$conf[]="commonName=match";
		$conf[]="emailAddress=optional";
		$conf[]="";
		$conf[]="[policy_anything]";
		$conf[]="countryName=optional";
		$conf[]="stateOrProvinceName=optional";
		$conf[]="localityName=optional";
		$conf[]="organizationName=optional";
		$conf[]="organizationalUnitName=optional";
		$conf[]="commonName=optional";
		$conf[]="emailAddress=optional";
		$conf[]="";
		$conf[]="[v3_ca]";
		$conf[]="subjectKeyIdentifier=hash";
		$conf[]="authorityKeyIdentifier=keyid:always,issuer:always";
		$conf[]="basicConstraints=critical,CA:false";
		@mkdir("/etc/squid3/ssl",0666,true);
		file_put_contents("/etc/squid3/ssl/openssl.conf",@implode("\n",$conf));
	}

function certificate_generate(){
		$ssl_path="/etc/squid3/ssl";

		if(is_certificate()){
			echo "Starting......: ".date("H:i:s")." Squid SSL certificate OK\n";
			return false;
		}


		@unlink("$ssl_path/privkey.cp.pem");
		@unlink("$ssl_path/cacert.pem");
		@unlink("$ssl_path/privkey.pem");


		 echo "Starting......: ".date("H:i:s")." Squid building SSL certificate\n";
		 certificate_conf();
		 $ldap=new clladp();
		 $sock=new sockets();
		 $unix=new unix();
		$CertificateMaxDays=$sock->GET_INFO('CertificateMaxDays');
		if($CertificateMaxDays==null){$CertificateMaxDays='730';}
		 echo "Starting......: ".date("H:i:s")." Squid Max Days are $CertificateMaxDays\n";
		 $password=$unix->shellEscapeChars($ldap->ldap_password);

		 $openssl=$unix->find_program("openssl");
		 $config="/etc/squid3/ssl/openssl.conf";

		 $cmd="$openssl genrsa -des3 -passout pass:$password -out $ssl_path/privkey.pem 2048";
         echo "Starting......: ".date("H:i:s")." $cmd\n";
		 system($cmd);
		 system("$openssl req -new -x509 -nodes -passin pass:$password -key $ssl_path/privkey.pem -batch -config $config -out $ssl_path/cacert.pem -days $CertificateMaxDays");
		 system("/bin/cp $ssl_path/privkey.pem $ssl_path/privkey.cp.pem");
		 system("$openssl rsa -passin pass:$password -in $ssl_path/privkey.cp.pem -out $ssl_path/privkey.pem");


	}

function is_certificate(){
	$ssl_path="/etc/squid3/ssl";;
	if(!is_file("$ssl_path/cacert.pem")){return false;}
	if(!is_file("$ssl_path/privkey.pem")){return false;}
	if(!is_file("$ssl_path/privkey.cp.pem")){return false;}
	return true;

}







function SquidUser(){
	$unix=new unix();
	$squidconf=$unix->SQUID_CONFIG_PATH();
	$group=null;
	if(!is_file($squidconf)){
		echo "Starting......: ".date("H:i:s")." squidGuard unable to get squid configuration file\n";
		return "squid:squid";
	}

	writelogs("Open $squidconf");
	$array=explode("\n",@file_get_contents($squidconf));
	foreach ($array as $index=>$line){
		if(preg_match("#cache_effective_user\s+(.+)#",$line,$re)){
			$user=trim($re[1]);
		}
		if(preg_match("#cache_effective_group\s+(.+)#",$line,$re)){
			$group=trim($re[1]);
		}
	}
	if($group==null){$group="squid";}
	return "$user:$group";
}






function compilation_params(){



	@mkdir("/etc/artica-postfix/pids",0755,true);
	if(!is_file($GLOBALS["SQUIDBIN"])){return false;}
	$EXEC_PID_FILE="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".build.pid";
	$EXEC_PID_TIME="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".build.time";

	$unix=new unix();
	$pid=@file_get_contents($EXEC_PID_FILE);
	if($unix->process_exists($pid,basename(__FILE__))){exit();}
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.compilation.params";

	$timefile=$unix->file_time_min($EXEC_PID_TIME);
	if($timefile<5){return false;}
	@unlink($EXEC_PID_TIME);
	@file_put_contents($EXEC_PID_TIME, time());

	if(is_file($cachefile)){
		$timefile=$unix->file_time_min($cachefile);
		if($timefile<30){return false;}
	}


	exec($GLOBALS["SQUIDBIN"]." -v",$results);
	$text=@implode("\n", $results);
	if(preg_match("#configure options:\s+(.+)#is", $text,$re)){$text=$re[1];}
	if(preg_match_all("#'(.+?)'#is", $text, $re)){
		foreach ($re[1] as $index=>$line){
			if(preg_match("#(.+?)=(.+)#", $line,$ri)){
				$key=$ri[1];
				$value=$ri[2];
				$key=str_replace("--", "", $key);
				$array[$key]=$value;
				continue;
			}
			$key=$line;
			$value=1;
			$key=str_replace("--", "", $key);
			$array[$key]=$value;


		}
		@unlink("/usr/share/artica-postfix/ressources/logs/squid.compilation.params");
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/squid.compilation.params", base64_encode(serialize($array)));
		shell_exec("/bin/chmod 755 /usr/share/artica-postfix/ressources/logs/squid.compilation.params");
	}
}



function TemplatesUniqueInMysql($zmd5){
	$unix=new unix();
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$base="/usr/share/squid-langpack/templates";
	@mkdir("/usr/share/squid-langpack/templates",0755,true);
	@mkdir("/usr/share/squid3/icons",0755,true);

	@mkdir($base,0755,true);
	if(!is_dir("$base/templates")){@mkdir("$base/templates",0755,true);}
	$sql="SELECT * FROM squidtpls WHERE `zmd5`='{$zmd5}'";
	$ligne=$q->mysqli_fetch_array($sql);
	if(!$q->ok){echo $q->mysql_error."\n";return false;}

	if($ligne["template_link"]==1){return false;}
	$ligne["template_header"]=stripslashes($ligne["template_header"]);
	$ligne["template_title"]=stripslashes($ligne["template_title"]);
	$ligne["template_body"]=stripslashes($ligne["template_body"]);


	$header=trim($ligne["template_header"]);
	if($ligne["template_name"]==null){return false;}

	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		$header=null;
		$ligne["template_header"]=null;
		$ligne["template_body"]=null;
	}


	if(is_numeric($ligne["lang"])){$ligne["lang"]="en";}

	if($header==null){$header=@file_get_contents(dirname(__FILE__)."/ressources/databases/squid.default.header.db");}
	if(!preg_match("#ERR_.+#", $ligne["template_name"])){$ligne["template_name"]="ERR_".$ligne["template_name"];}

	$filename="$base/{$ligne["lang"]}/{$ligne["template_name"]}";
	$newheader=str_replace("{TITLE}", $ligne["template_title"], $header);
	$templateDatas="$newheader{$ligne["template_body"]}</body></html>";

	if($ligne["emptytpl"]==1){
		$templateDatas="<html><head></head><body></body></html>";
	}

	@mkdir(dirname($filename),0755,true);
	@file_put_contents($filename, $templateDatas);



	if($GLOBALS["VERBOSE"]){echo "Writing $base/{$ligne["lang"]}/{$ligne["template_name"]}\n";}
	@file_put_contents("$base/{$ligne["lang"]}/{$ligne["template_name"]}", $templateDatas);
	$unix->chown_func("squid","squid","$base/{$ligne["lang"]}/{$ligne["template_name"]}");
	$unix->chown_func("squid:squid",null, "$base/{$ligne["lang"]}/{$ligne["template_name"]}");
	$unix->chown_func("squid:squid",null, dirname($filename)."/*");
	if($ligne["lang"]=="en"){
		if($GLOBALS["VERBOSE"]){echo "Writing $base/{$ligne["template_name"]}\n";}
		@file_put_contents("$base/{$ligne["template_name"]}", $templateDatas);
		$unix->chown_func("squid:squid", null,"$base/{$ligne["template_name"]}");
	}

}

function dump_templates(){
	$defaultdb=dirname(__FILE__)."/ressources/databases/squid.default.templates.db";
	$array=unserialize(@file_get_contents($defaultdb));
	print_r($array);
}


function DefaultTemplatesInArtica(){
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if($SQUIDEnable==0){return false;}

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
        if(isset($trace[1])) {
            $called = " called by " . basename($trace[1]["file"]) . " {$trace[1]["function"]}() line {$trace[1]["line"]}";
        }
	}
	$sock=new sockets();
	$SquidTemplateSimple=$sock->GET_INFO("SquidTemplateSimple");
	if(!is_numeric($SquidTemplateSimple)){$SquidTemplateSimple=1;}
	if($SquidTemplateSimple==1){
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		$by="--FUNC-".__FUNCTION__."-L-".__LINE__;
		squid_admin_mysql(2, "Ask to build simple templates [$called]", $GLOBALS["ARGVS"],__FILE__,__LINE__);
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.templates.php $by >/dev/null 2>&1 &");
		return false;
	}

	$defaultdb=dirname(__FILE__)."/ressources/databases/squid.default.templates.db";
	$array=unserialize(@file_get_contents($defaultdb));

	$basename="/usr/share/squid-langpack/templates";
	@mkdir("/usr/share/squid3/icons/silk",0755);
	@chown("/usr/share/squid3/icons/silk", "squid");
	@chgrp("/usr/share/squid3/icons/silk", "squid");
	@unlink("/usr/share/squid3/icons/silk/bigshield-256.png");
	@unlink("/usr/share/squid3/icons/silk/logo-artica-64.png");
	@mkdir("/usr/share/squid-langpack/templates",0755,true);

	@copy("/usr/share/artica-postfix/img/bigshield-256.png","/usr/share/squid3/icons/silk/bigshield-256.png");
	@copy("/usr/share/artica-postfix/img/logo-artica-64.png","/usr/share/squid3/icons/silk/logo-artica-64.png");
	@chown("/usr/share/squid3/icons/silk/bigshield-256.png", "squid");
	@chgrp("/usr/share/squid3/icons/silk/bigshield-256.png", "squid");
	@chown("/usr/share/squid3/icons/silk/logo-artica-64.png", "squid");
	@chgrp("/usr/share/squid3/icons/silk/logo-artica-64.png", "squid");


	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");


	$prefix="INSERT INTO squidtpls (`zmd5`,`lang`,`template_name`,`template_body`,`template_title`,`emptytpl`) VALUES ";

    foreach ($array as $language=>$arrayTPL){
		$directory="$basename/$language";
		@mkdir($directory,0755,true);
		@chown($directory, "squid");
		@chgrp($directory, "squid");
        foreach ($arrayTPL as $templateName=>$templateData){
			$title=$templateData["TITLE"];
			$md5=md5($language.$templateName);
			if($title==null){echo "$templateName -> null title\n";}
			$body=$templateData["BODY"];
			$filepath="$directory/$templateName";
			$content=TemplatesDesign($title,$body);
			if($templateName=="ERR_DIR_LISTING"){
				$content=TemplatesFTP($title,$body);
			}

			$body=sqlite_escape_string2($content);
			$title=sqlite_escape_string2($title);


			$ss="('$md5','$language','$templateName','$body','$title',0)";
			$q->QUERY_SQL("DELETE FROM squidtpls WHERE `zmd5`='$md5'");
			$q->QUERY_SQL($prefix.$ss);
			@file_put_contents($filepath, $content);
			@chown($filepath, "squid");
			@chgrp($filepath, "squid");


		}
	}
    system("/usr/sbin/artica-phpfpm-service -proxy-mimeconf");
}


function TemplatesFTP($title,$content){
	$title=utf8_decode($title);
	$content=utf8_decode($content);
	if(!isset($GLOBALS["CORP_LICENSE"])){
		$users=new usersMenus();
		$GLOBALS["CORP_LICENSE"]=$users->CORP_LICENSE;
	}


	$sock=new sockets();
	$sock->BuildTemplatesConfig();

	if(!$GLOBALS["CORP_LICENSE"]){
		$FOOTER="
		<table style='width:75%;border-top:1px solid {$GLOBALS["UfdbGuardHTTP"]["FontColor"]};margin-top:15px'>
		<tr><td colspan=2>&nbsp;</td></tr>
		<tr>
		<td width=64px><img src='/squid-internal-static/icons/silk/logo-artica-64.png'></td>
		<td style='font-size:14px;padding-left:10px' width=99%>
		You using Artica Proxy Appliance v{$GLOBALS["ARTICA_VERSION"]} in Community mode.<br>
		<i>Visit our  <a href=\"http://artica-proxy.com\">website</a> for technical informations or to purchase an Enterprise Edition License</i>
		</td>
		</tr>
		</table>
		</div>";
	}
	$f[]="<!DOCTYPE HTML>";
	$f[]="<html>";
		$f[]="<head>";
		$f[]="<title>$title</title>";
		$f[]="<script type=\"text/javascript\">";
	$f[]="    function checkIfTopMostWindow()";
	$f[]="    {";
		$f[]="        if (window.top != window.self) ";
	$f[]="        {  ";
	$f[]="            document.body.style.opacity    = \"0.0\";";
	$f[]="            document.body.style.background = \"#FFFFFF\";";
	$f[]="        }";
		$f[]="        else";
	$f[]="        {";
		$f[]="            document.body.style.opacity    = \"1.0\";";
	$f[]="            document.body.style.background = \"{$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]}\";";
		$f[]="        } ";
	$f[]="    }";
		$f[]="</script>";
		$f[]="<style type=\"text/css\">";
	$f[]="    body {";
	$f[]="        color:            {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="        background-color: #FFFFFF; ";
	$f[]="        font-family:      {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight:      lighter;";
	$f[]="        font-size:        14pt; ";
	$f[]="        ";
		$f[]="        opacity:            0.0;";
	$f[]="        transition:         opacity 2s;";
	$f[]="        -webkit-transition: opacity 2s;";
	$f[]="        -moz-transition:    opacity 2s;";
	$f[]="        -o-transition:      opacity 2s;";
	$f[]="        -ms-transition:     opacity 2s;    ";
	$f[]="    }";


	$f[]="    center {";
	$f[]="        color:            {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="        font-family:      {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight:      lighter;";
	$f[]="        font-size:        12pt; ";
	$f[]="}";

	$f[]="    h1 {";
	$f[]="        font-size: 72pt; ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]="    h2 {";
	$f[]="        font-size: 22pt; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight: lighter;";
	$f[]="    }   ";
	$f[]="    h3 {";
	$f[]="        font-size: 18pt; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight: lighter;";
	$f[]="        margin-bottom: 0 ;";
	$f[]="    }   ";
	$f[]="    #wrapper {";
	$f[]="        width: 700px ;";
	$f[]="        margin-left: auto ;";
	$f[]="        margin-right: auto ;";
	$f[]="    }    ";
	$f[]="    #info {";
	$f[]="        width: 600px ;";
	$f[]="        margin-left: auto ;";
	$f[]="        margin-right: auto ;";
	$f[]="    }    ";

	$f[]="    #titles h1 {";
	$f[]="        font-size: 72pt; ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";

	$f[]="hr {
				border-top: 1px dotted #f00;
  color: #fff;
  background-color: #fff;
  height: 1px;
  width:50%;
}
";

	$f[]="    #content p {";
	$f[]="       font-size:  11pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]="    #footer p {";
	$f[]="       font-size:  12pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";

	$f[]="    #data pre{";
	$f[]="       font-size:  12pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        font-weight: bold;";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]="    #data pre:before{content: \"\\275D\";margin-right:5px;font-size:22pt}";
	$f[]="    #data pre:after{content: \"\\275E\";margin-left:5px;font-size:22pt}";
	$f[]=".bad{ font-size: 110px; float:left; margin-right:30px; }";
	$f[]=".bad:before{ content: \"\\260C\";}";

	$f[]="#dirlisting{";
	$f[]="       font-size:  12pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        font-weight: lighter;";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]="#dirlisting th{";
	$f[]="       font-size:  16pt;  ";
	$f[]="    }    ";
	$f[]="    td.info_title {    ";
	$f[]="        text-align: right;";
	$f[]="        font-size:  12pt;  ";
	$f[]="        min-width: 100px;";
	$f[]="    }";
	$f[]="    td.info_content {";
	$f[]="        text-align: left;";
	$f[]="        padding-left: 10pt ;";
	$f[]="        font-size:  12pt;  ";
	$f[]="    }";
	$f[]="    .break-word {";
	$f[]="        width: 500px;";
	$f[]="        word-wrap: break-word;";
	$f[]="    }    ";
	$f[]="    a {";
	$f[]="        text-decoration: underline;";
	$f[]="        color: {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="    }";
	$f[]="    a:visited{";
	$f[]="        text-decoration: underline;";
	$f[]="        color: {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="    }";
	$f[]="</style>";
	$f[]="</head>";
	$f[]="<body onLoad='checkIfTopMostWindow()'>";
	$f[]="<div id=\"wrapper\">";
	$f[]="    <h1 class=bad></h1>";
	$f[]="    <div id=\"info\">";
	$f[]="$content";


		if($GLOBALS["UfdbGuardHTTP"]["NoVersion"]==0){
		$f[]="<center>Artica Proxy, version {$GLOBALS["ARTICA_VERSION"]}</center>";
				}
				$f[]="    </div>    $FOOTER";
				$f[]="</div>";
	$f[]="</body>";
	$f[]="<!-- ";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="-->";
	$f[]="</html>";
	return @implode("\n", $f);

}


function TemplatesDesign($title,$content){
	$title=utf8_decode($title);
	$content=utf8_decode($content);
	if(!isset($GLOBALS["CORP_LICENSE"])){
		$users=new usersMenus();
		$GLOBALS["CORP_LICENSE"]=$users->CORP_LICENSE;
	}

	$sock=new sockets();
	$sock->BuildTemplatesConfig();
	$SquidHTTPTemplateSmiley=$sock->GET_INFO("SquidHTTPTemplateSmiley");
	if($SquidHTTPTemplateSmiley==null){$SquidHTTPTemplateSmiley=2639;}


	if(!$GLOBALS["CORP_LICENSE"]){
		$FOOTER="
		<table style='width:75%;border-top:1px solid {$GLOBALS["UfdbGuardHTTP"]["FontColor"]};margin-top:15px'>
		<tr><td colspan=2>&nbsp;</td></tr>
		<tr>
		<td width=64px><img src='/squid-internal-static/icons/silk/logo-artica-64.png'></td>
		<td style='font-size:14px;padding-left:10px' width=99%>
		You using Artica Proxy Appliance v{$GLOBALS["ARTICA_VERSION"]} in Community mode.<br>
		<i>Visit our  <a href=\"http://artica-proxy.com\">website</a> for technical informations or to purchase an Enterprise Edition License</i>
		</td>
		</tr>
		</table>
		</div>";
	}
	$f[]="<!DOCTYPE HTML>";
	$f[]="<html>";
	$f[]="<head>";
	$f[]="<title>$title</title>";
	$f[]="<script type=\"text/javascript\">";
	$f[]="    function checkIfTopMostWindow()";
	$f[]="    {";
	$f[]="        if (window.top != window.self) ";
	$f[]="        {  ";
	$f[]="            document.body.style.opacity    = \"0.0\";";
	$f[]="            document.body.style.background = \"#FFFFFF\";";
	$f[]="        }";
	$f[]="        else";
	$f[]="        {";
	$f[]="            document.body.style.opacity    = \"1.0\";";
	$f[]="            document.body.style.background = \"{$GLOBALS["UfdbGuardHTTP"]["BackgroundColor"]}\";";
		$f[]="        } ";
	$f[]="    }";
	$f[]="</script>";
	$f[]="<style type=\"text/css\">";
	$f[]="    body {";
	$f[]="        color:            {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="        background-color: #FFFFFF; ";
	$f[]="        font-family:      {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight:      lighter;";
	$f[]="        font-size:        14pt; ";
	$f[]="        ";
	$f[]="        opacity:            0.0;";
	$f[]="        transition:         opacity 2s;";
	$f[]="        -webkit-transition: opacity 2s;";
	$f[]="        -moz-transition:    opacity 2s;";
	$f[]="        -o-transition:      opacity 2s;";
	$f[]="        -ms-transition:     opacity 2s;    ";
	$f[]="    }";


	$f[]="    center {";
	$f[]="        color:            {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
	$f[]="        font-family:      {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight:      lighter;";
	$f[]="        font-size:        12pt; ";
	$f[]="}";

	$f[]="    h1 {";
	$f[]="        font-size: 72pt; ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]="    h2 {";
	$f[]="        font-size: 22pt; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight: lighter;";
	$f[]="    }   ";
	$f[]="    h3 {";
	$f[]="        font-size: 18pt; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]}; ";
	$f[]="        font-weight: lighter;";
	$f[]="        margin-bottom: 0 ;";
	$f[]="    }   ";
	$f[]="    #wrapper {";
	$f[]="        width: 700px ;";
	$f[]="        margin-left: auto ;";
	$f[]="        margin-right: auto ;";
	$f[]="    }    ";
	$f[]="    #info {";
	$f[]="        width: 600px ;";
	$f[]="        margin-left: auto ;";
	$f[]="        margin-right: auto ;";
	$f[]="    }    ";

	$f[]="    #titles h1 {";
	$f[]="        font-size: 72pt; ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";

	$f[]="hr {
   border-top: 1px dotted #f00;
  color: #fff;
  background-color: #fff;
  height: 1px;
  width:50%;
}
";

	$f[]="    #content p {";
	$f[]="       font-size:  11pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";

	$f[]="    #footer p {";
	$f[]="       font-size:  12pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";


	$f[]="    #data pre{";
	$f[]="       font-size:  12pt;  ";
	$f[]="        margin-bottom: 0; ";
	$f[]="        font-family: {$GLOBALS["UfdbGuardHTTP"]["Family"]};";
	$f[]="        font-weight: bold;";
	$f[]="        margin-top: 0 ;";
	$f[]="    }    ";
	$f[]="    #data pre:before{content: \"\\275D\";margin-right:5px;font-size:22pt}";
	$f[]="    #data pre:after{content: \"\\275E\";margin-left:5px;font-size:22pt}";
	$f[]=".bad{ font-size: 110px; float:left; margin-right:30px; }";
	$f[]=".bad:before{ content: \"\\$SquidHTTPTemplateSmiley\";}";

	$f[]="    td.info_title {    ";
	$f[]="        text-align: right;";
	$f[]="        font-size:  12pt;  ";
	$f[]="        min-width: 100px;";
	$f[]="    }";
	$f[]="    td.info_content {";
	$f[]="        text-align: left;";
	$f[]="        padding-left: 10pt ;";
	$f[]="        font-size:  12pt;  ";
	$f[]="    }";
	$f[]="    .break-word {";
	$f[]="        width: 500px;";
	$f[]="        word-wrap: break-word;";
	$f[]="    }    ";
	$f[]="    a {";
	$f[]="        text-decoration: underline;";
	$f[]="        color: {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
		$f[]="    }";
	$f[]="    a:visited{";
	$f[]="        text-decoration: underline;";
	$f[]="        color: {$GLOBALS["UfdbGuardHTTP"]["FontColor"]}; ";
		$f[]="    }";
	$f[]="</style>";
	$f[]="</head>";
	$f[]="<body onLoad='checkIfTopMostWindow()'>";
	$f[]="<div id=\"wrapper\">";
	$f[]="    <h1 class=bad></h1>";
	$f[]="    <div id=\"info\">";
	$f[]="$content";


	if($GLOBALS["UfdbGuardHTTP"]["NoVersion"]==0){
		$f[]="<center>Artica Proxy, version {$GLOBALS["ARTICA_VERSION"]}</center>";
	}
	$f[]="    </div>    $FOOTER";
	$f[]="</div>";
	$f[]="</body>";
	$f[]="<!-- ";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="    xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
	$f[]="-->";
	$f[]="</html>";
	return @implode("\n", $f);

}


function TemplatesInMysql($aspid=false){

	$unix=new unix();
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if(!$aspid){
		$pid=$unix->get_pid_from_file($pidpath);
		if($unix->process_exists($pid)){return false;}

	}

	@file_put_contents($pidpath, getmypid());
	@file_put_contents("/etc/artica-postfix/SQUID_TEMPLATE_DONE", time());
	@file_put_contents("/etc/artica-postfix/SQUID_TEMPLATE_DONEv2", time());


	$sock=new sockets();
	$SquidTemplateSimple=$sock->GET_INFO("SquidTemplateSimple");
	if(!is_numeric($SquidTemplateSimple)){$SquidTemplateSimple=1;}
	if($SquidTemplateSimple==1){
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		$by="--FUNC-".__FUNCTION__."-L-".__LINE__;
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.templates.php $by >/dev/null 2>&1 &");
		return false;
	}



	$TimeExec=$unix->file_time_min($pidtime);
	if(!$GLOBALS["FORCE"]){
		if($TimeExec<240){return false;}
	}




	$sock=new sockets();

	$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}

	$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
	if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}


	@mkdir("/etc/artica-postfix",0755,true);
	$base="/usr/share/squid-langpack";
	@mkdir($base,0755,true);
	if(!is_dir("$base/templates")){@mkdir("$base/templates",0755,true);}
	$headerTemp=@file_get_contents(dirname(__FILE__)."/ressources/databases/squid.default.header.db");




	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");

	if($q->COUNT_ROWS("squidtpls")==0){
		if(!is_file("/etc/artica-postfix/SQUID_TEMPLATE_DONE")){
			squid_admin_mysql(2,"Ask to build default templates squidtpls=0", null,__FILE__,__LINE__);
			DefaultTemplatesInArtica();
		}
	}

	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		if(!is_file("/etc/artica-postfix/SQUID_TEMPLATE_DONE")){
			squid_admin_mysql(2,"Ask to build default templates - no license -", null,__FILE__,__LINE__);
			DefaultTemplatesInArtica();
			return false;
		}
	}

	$sql="SELECT * FROM squidtpls";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		squid_admin_mysql(1, "MySQL Error on templates", $q->mysql_error,__FILE__,__LINE__);
		squid_admin_mysql(1, "Fatal,$q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "proxy");
		return false;
	}
	$c=0;
	foreach ($results as $index=>$ligne){
		$ligne["template_header"]=stripslashes($ligne["template_header"]);
		$ligne["template_title"]=stripslashes($ligne["template_title"]);
		$ligne["template_body"]=stripslashes($ligne["template_body"]);
		$template_name=$ligne["template_name"];
		if($ligne["template_link"]==1){continue;}
		$header=trim($ligne["template_header"]);
		if($header==null){$header=$headerTemp;}
		if($GLOBALS["VERBOSE"]){
			echo "Template: `$template_name`: {$ligne["template_title"]}\n";
		}

		if(!preg_match("#^ERR_.+#", $ligne["template_name"])){
				$ligne["template_name"]="ERR_".$ligne["template_name"];
		}

		$filename2=null;
		$ligne["template_body"]=utf8_encode($ligne["template_body"]);
		$ligne["template_title"]=utf8_encode($ligne["template_title"]);


		$filename="$base/{$ligne["lang"]}/{$ligne["template_name"]}";
		if($ligne["lang"]=="en"){
			$filename2="/usr/share/squid-langpack/templates/{$ligne["template_name"]}";
		}
		$newheader=str_replace("{TITLE}", $ligne["template_title"], $header);
		$templateDatas="$newheader{$ligne["template_body"]}</body></html>";

		if($GLOBALS["VERBOSE"]){
			echo "Template: `$template_name`: Path `$filename`\n";
		}

		if($ligne["emptytpl"]==1){
			$templateDatas="<html><head></head><body></body></html>";
		}

		if($GLOBALS["VERBOSE"]){
			echo "Template: `$template_name`: {$ligne["lang"]}\n";
		}

		if(is_numeric($ligne["lang"])){$ligne["lang"]="en";}

		@mkdir(dirname($filename),0755,true);
		@file_put_contents($filename, $templateDatas);
		if($filename2<>null){
			@file_put_contents($filename2, $templateDatas);
			$unix->chown_func("squid","squid","$filename2");
		}
		@file_put_contents("$base/{$ligne["lang"]}/{$ligne["template_name"]}", $templateDatas);
		$unix->chown_func("squid","squid","$base/{$ligne["lang"]}/{$ligne["template_name"]}");
		$unix->chown_func("squid","squid","$filename");

		$c++;


		if($ligne["lang"]=="en"){
			if($GLOBALS["VERBOSE"]){echo "Writing $base/{$ligne["template_name"]}\n";}
			@file_put_contents("$base/{$ligne["template_name"]}", $templateDatas);
			$unix->chown_func("squid:squid", null,"$base/templates/{$ligne["template_name"]}");
		}else{
			if(!IfTemplateExistsinEn($template_name)){
				@mkdir("$base/en",0755,true);
				@file_put_contents("$base/en/{$ligne["template_name"]}", $templateDatas);
				$unix->chown_func("squid:squid", null,"$base/en/{$ligne["template_name"]}");
				}
		}
	}



	$sql="SELECT * FROM squidtpls WHERE emptytpl=1";
	$results = $q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		if(is_numeric($ligne["lang"])){$ligne["lang"]="en";}
		if(!preg_match("#^ERR_.+#", $ligne["template_name"])){
			$ligne["template_name"]="ERR_".$ligne["template_name"];
		}

		$filename="$base/{$ligne["lang"]}/{$ligne["template_name"]}";
		$templateDatas="<html><head></head><body></body></html>";
		@mkdir(dirname($filename),0755,true);
		@file_put_contents($filename, $templateDatas);
		@file_put_contents("$base/{$ligne["lang"]}/{$ligne["template_name"]}", $templateDatas);
		$unix->chown_func("squid","squid","$base/{$ligne["lang"]}/{$ligne["template_name"]}");
		$unix->chown_func("squid","squid","$filename");
	}




	$unix=new unix();
	$tar=$unix->find_program("tar");
	$unix->chown_func("squid","squid", "$base/*");
	chdir($base);
	shell_exec("$tar -czf ".dirname(__FILE__)."/ressources/databases/squid-lang-pack.tgz *");

	if($EnableWebProxyStatsAppliance==1){
		if($GLOBALS["VERBOSE"]){echo "-> notify_remote_proxys()\n";}
		notify_remote_proxys();
		if($GLOBALS["VERBOSE"]){echo "This is a statistics appliance, aborting next step\n";}
		return false;
	}

	squid_admin_mysql(2, "$c web pages templates saved", "no information",__FILE__,__LINE__);
	Reload_Squid();

}

function EventsWatchdog($text){

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}

	}

	$unix=new unix();
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}


function IfTemplateExistsinEn($template_name){
	if(isset($GLOBALS["IfTemplateExistsinEn$template_name"])){return $GLOBALS["IfTemplateExistsinEn$template_name"];}
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$ligne=$q->mysqli_fetch_array("SELECT zmd5 FROM squidtpls WHERE template_name='$template_name' AND lang='en'");
	if($ligne["zmd5"]==null){$GLOBALS["IfTemplateExistsinEn$template_name"]=false;return false;}
	$GLOBALS["IfTemplateExistsinEn$template_name"]=true;
	return true;
}






function watchdog_config(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$unix->THREAD_COMMAND_SET("$php /usr/share/artica-postfix/exec.monit.php --build");
}

function checkdatabase(){}




function ToSyslog($text){

if(!function_exists("syslog")){return false;}
$file=basename(__FILE__);
$LOG_SEV=LOG_INFO;
openlog($file, LOG_PID , LOG_SYSLOG);
syslog($LOG_SEV, $text);
closelog();
}





function restart_squid(){
	$unix=new unix();
	$byschedule=null;
	$taskid=null;
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$TimeMin=$unix->file_time_min($timeFile);
	if($TimeMin<60){
		squid_admin_mysql(1, "Ask to restart proxy service aborted {$TimeMin}Mn need at least 60mn", null,__FILE__,__LINE__);
		return false;
	}

	@unlink($timeFile);
	@file_put_contents($timeFile, time());

	if($GLOBALS["BY_SCHEDULE"]){
		$byschedule="Scheduled task";
		if($GLOBALS["SCHEDULE_ID"]>0){
			$taskid=" - Task ID {$GLOBALS["SCHEDULE_ID"]}";
		}
	}

	squid_admin_mysql(1, "Ask to restart proxy service ($byschedule$taskid)", null,__FILE__,__LINE__);
	$unix->go_exec("/etc/init.d/squid restart --force --script=".basename(__FILE__));


}



function extract_schedules(){
    $lines=array();
	$sql="SELECT *  FROM webfilters_schedules WHERE enabled=1";
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$results = $q->QUERY_SQL($sql);
	foreach ($results as $inex=>$ligne){
		$TaskType=$ligne["TaskType"];
		$TimeText=$ligne["TimeText"];
		$TimeDescription=mysql_escape_string2($ligne["TimeDescription"]);
		$lines[]="\$array[$TaskType]=array(\"TimeText\"=>\"$TimeText\",\"TimeDescription\"=>\"$TimeDescription\");";

	}
	echo implode("\n", $lines);

}

function run_schedules($ID){
	$GLOBALS["SCHEDULE_ID"]=$ID;
	writelogs("Task $ID",__FUNCTION__,__FILE__,__LINE__);
	$qProxy=new mysql_squid_builder(true);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$ligne=$q->mysqli_fetch_array("SELECT TaskType FROM webfilters_schedules WHERE ID=$ID");

	$TaskType=$ligne["TaskType"];
	if($TaskType==0){return false;}
	if(!isset($qProxy->tasks_processes[$TaskType])){squid_admin_mysql(1, "Unable to understand task type `$TaskType` For this task" , __FUNCTION__, __FILE__, __LINE__, "tasks");return false;}
	$script=$qProxy->tasks_processes[$TaskType];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$WorkingDirectory=dirname(__FILE__);
	$cmd="$nohup $php5 $WorkingDirectory/$script --schedule-id=$ID >/dev/null 2>&1 &";
	writelogs("Task {$GLOBALS["SCHEDULE_ID"]} is executed with `$cmd` ",__FUNCTION__,__FILE__,__LINE__);
	squid_admin_mysql(1, "Task is executed with `$cmd`" , __FUNCTION__, __FILE__, __LINE__, "tasks");
	shell_exec($cmd);

}

function build_schedules_tests(){
	$unix=new unix();
	if(!$unix->IsSquidTaskCanBeExecuted()){
		EventsWatchdog("IsSquidTaskCanBeExecuted() return false");

		return false;}
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";

	$pidTimeINT=$unix->file_time_min($pidTime);
	if(!$GLOBALS["VERBOSE"]){
		if($pidTimeINT<5){
			EventsWatchdog("Too short time to execute the process ($pidTime)");
			writelogs("To short time to execute the process",__FILE__,__FUNCTION__,__LINE__);
			return false;
		}
	}

	@file_put_contents($pidTime, time());

	if(!is_file("/etc/artica-postfix/squid.schedules")){
		echo "No schedule yet....\n";
		shell_exec("/etc/init.d/artica-postfix restart watchdog");
	}
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$qProxy=new mysql_squid_builder(true);
	$ligne=$q->mysqli_fetch_array("SELECT TimeText FROM webfilters_schedules WHERE TaskType=14");
	if($ligne["TimeText"]==null){
		$sql="INSERT OR IGNORE INTO `webfilters_schedules` (`TimeText`, `TimeDescription`, `TaskType`, `enabled`,`params`) VALUES ('30 6 * * *', 'Optimize all tables  each day at 06h30', 14, 1,' ');";
		$q->QUERY_SQL($sql);
		if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
		shell_exec("/etc/init.d/artica-postfix restart watchdog");
	}
}


function build_schedules($notfcron=false):bool{
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    if($Enablehacluster==1){return true;}
	$unix=new unix();
	@mkdir("/var/log/artica-postfix/youtube",0755,true);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=$unix->get_pid_from_file($pidfile);


	if($unix->process_exists($pid,basename(__FILE__))){
		writelogs("Already executed pid $pid",__FILE__,__FUNCTION__,__LINE__);
		return false;
	}


	@file_put_contents($pidfile, getmypid());

	$pidTimeINT=$unix->file_time_min($pidTime);
	if(!$GLOBALS["VERBOSE"]){
		if($pidTimeINT<2){
			build_progress_schedules("{failed}", 110);
			writelogs("To short time to execute the process",__FILE__,__FUNCTION__,__LINE__);
			return false;
		}
	}


	$RemoveProxyTasks=false;
	@file_put_contents($pidTime, time());
	if(!$unix->IsSquidTaskCanBeExecuted()){
		$RemoveProxyTasks=true;
	}

	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$qProxy=new mysql_squid_builder(true);
	$qProxy->CheckDefaultSchedules(true);

	if($q->COUNT_ROWS("webfilters_schedules")==0){
		build_progress_schedules("{failed} no schedules set", 110);
		return false;
	}
	$AuthTaskType=array();

	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		echo "Community Edition\n";
		$AuthTaskType[6]=true;

	}else{
		$AuthTaskType[3]=true;
		echo "Entreprise Edition\n";
	}
    $sql="SELECT *  FROM webfilters_schedules WHERE enabled=1";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		build_progress_schedules("{failed} MySQL error", 110);
		return false;}

	@unlink("/etc/cron.d/SquidTailInjector");
	$php5=$unix->LOCATE_PHP5_BIN();
	$WorkingDirectory=dirname(__FILE__);

	foreach (glob("/etc/cron.d/*") as $filename) {
		$file=basename($filename);

		if(preg_match("#squidsch-[0-9]+#", $filename)){if($GLOBALS["VERBOSE"]){echo "Removing old task $file\n";}@unlink($filename);}
	}
	@unlink("/etc/artica-postfix/TASKS_SQUID_CACHE.DB");

	$settings=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FcronSchedulesParams"));
	if(!isset($settings["max_nice"])){$settings["max_nice"]=19;}
	if(!isset($settings["max_load_avg5"])){$settings["max_load_avg5"]=3;}
	if(!isset($settings["max_load_wait"])){$settings["max_load_wait"]=10;}
	if(!is_numeric($settings["max_load_avg5"])){$settings["max_load_avg5"]="3";}
	if(!is_numeric($settings["max_load_wait"])){$settings["max_load_wait"]="10";}
	if(!is_numeric($settings["max_nice"])){$settings["max_nice"]="19";}

	@unlink("/etc/artica-postfix/squid.schedules");
	$nice=EXEC_NICE();

	build_progress_schedules("{building}", 50);
	$c=0;$d=0;
	foreach ($results as $index=>$ligne){
		$allminutes="1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59";
		$TaskType=$ligne["TaskType"];
		$CronFile="/etc/cron.d/squidsch-{$ligne["ID"]}";

		echo "*****************************************************************************************\n";
		echo "* * Task {$ligne["ID"]} Type $TaskType: $CronFile {$ligne["TimeDescription"]} * * \n";
		echo "*****************************************************************************************\n";

		if($RemoveProxyTasks){
			if(!isset($AuthTaskType[$TaskType])){
				echo "Task {$ligne["ID"]} type '$TaskType' {$ligne["TimeDescription"]} aborted...\n";
				if(is_file($CronFile)){@unlink($CronFile);}continue;}
		}

		$TimeText=$ligne["TimeText"];
		if($TaskType==0){continue;}
		if($ligne["TimeText"]==null){continue;}

		$md5=md5("$TimeText$TaskType");
		if(isset($alreadydone[$md5])){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." artica-postfix watchdog task {$ligne["ID"]} already set\n";}continue;}
		$alreadydone[$md5]=true;

		if(!isset($qProxy->tasks_processes[$TaskType])){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." artica-postfix task {$ligne["ID"]} no such task...\n";}
			$d++;continue;
		}
		if(isset($qProxy->tasks_disabled[$TaskType])){
			if(!isset($AuthTaskType[$TaskType])){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." artica-postfix task {$ligne["ID"]} is disabled or did not make sense...\n";}
				$d++;
				continue;
			}
		}



		$script=$qProxy->tasks_processes[$TaskType];
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." artica-postfix create task {$ligne["ID"]} type $TaskType..\n";}
		if(trim($ligne["TimeText"]=="$allminutes * * * *")){$ligne["TimeText"]="* * * * *";}

		$f=array();
		$f[]="MAILTO=\"\"";
		$f[]="# $script Type: $TaskType";
		$f[]="{$ligne["TimeText"]}  root $nice $php5 $WorkingDirectory/exec.schedules.php --run-squid {$ligne["ID"]} >/dev/null 2>&1";
		$f[]="";
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." creating /etc/cron.d/squidsch-{$ligne["ID"]}\n";}
		@file_put_contents("/etc/cron.d/squidsch-{$ligne["ID"]}", @implode("\n", $f));
		$c++;

	}


	build_progress_schedules("{building}", 80);
	@file_put_contents("/etc/artica-postfix/squid.schedules",implode("\n",$f));
	if($notfcron){
		echo "Starting......: ".date("H:i:s")." Squid $c scheduled tasks ($d disabled)\n";
		return false;
	}
	$cron_path=$unix->find_program("cron");
	$cron_pid=null;
	if(is_file("/var/run/cron.pid")){$cron_pid=$unix->get_pid_from_file("/var/run/cron.pid");}
	if(!$unix->process_exists($cron_pid)){$cron_pid=0;}
	if(!is_numeric($cron_pid) OR $cron_pid<5){$cron_pid=$unix->PIDOF("$cron_path");}
	if($cron_pid>5){
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." artica-postfix reloading $cron_path [$cron_pid]...\n";}
		unix_system_HUP("$cron_pid");
	}
	build_progress_schedules("{building}", 90);
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." artica-postfix reloading fcron...\n";}
    $unix->go_exec("/etc/init.d/cron reload");
	build_progress_schedules("{done}", 100);
    return true;
}
function WriteMyLogs($text){
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	$sourcefunction=null;
	$sourceline=0;

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}

	}
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$GLOBALS["CLASS_UNIX"]->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}
function squid_reconfigure_build_tool(){
	$unix=new unix();
	$squidbin=$unix->find_program("squid3");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid");}
	$php5=$unix->find_program("php5");
	$f[]="#! /bin/sh";
	$f[]="echo \"Reconfiguring proxy, please wait\"";
	$f[]="$php5 ".__FILE__." --build \$1";
	$f[]="exit 0";
	@file_put_contents("/bin/squidreconf", @implode("\n", $f));
	@chmod("/bin/squidreconf",0755);
}
function bandwithdebug(){
	$GLOBALS["VERBOSE"]=true;
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$ban=new squid_bandwith_builder();
	echo $ban->compile();
}
function import_webfilter($filename){
	if(!is_file($filename)){echo "$filename no such file\n";return false;}
	$unix=new unix();
	$ext=Get_extension($filename);
	if($ext<>"gz"){
		echo "$filename not a compressed file\n";
		return false;
	}

	$destinationfile=$unix->FILE_TEMP();
	$sqlsourcefile=$unix->FILE_TEMP().".sql";
	if(!$unix->uncompress($filename, $destinationfile)){
		echo "$filename corrupted GZ file...\n";
		return false;
	}

	$contentArray=$GLOBALS["CLASS_SOCKETS"]->unserializeb64(@file_get_contents($destinationfile));
	if(!is_array($contentArray)){
		echo "$filename corrupted file not an array...\n";
		return false;
	}
	print_r($contentArray);
	@file_put_contents($sqlsourcefile, $contentArray["SQL"]);
	$sock=new sockets();
	echo "Saving default rule...\n";
	$sock->SaveConfigFile($contentArray["DansGuardianDefaultMainRule"], "DansGuardianDefaultMainRule");
	$mysqlbin=$unix->find_program("mysql");
	$password=null;
	$localdatabase="squidlogs";
	$q=new mysql_squid_builder();
	$cmdline="$mysqlbin --batch --force $q->MYSQL_CMDLINES";
	$cmd="$cmdline --database=$localdatabase <$sqlsourcefile 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	exec($cmd,$results);
    foreach ($results as $key=>$value){
		echo "$value\n";

	}

}
function import_acls($filename){
	if(!is_file($filename)){echo "$filename no such file\n";return false;}
	$unix=new unix();

	$ext=Get_extension($filename);
	if($ext=="acl"){
		import_acls_extacl($filename,null,0);
		return false;
	}

	$destinationfile=$unix->FILE_TEMP();
	if(!$unix->uncompress($filename, $destinationfile)){
		echo "$filename corrupted GZ file...\n";
		;return false;
	}

	$mysqlbin=$unix->find_program("mysql");
	$password=null;
	$localdatabase="squidlogs";



	$q=new mysql_squid_builder();

	$cmdline="$mysqlbin --batch --force $q->MYSQL_CMDLINES";
	$cmd="$cmdline --database=$localdatabase <$destinationfile 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
    $unix->go_exec($cmd);

}
function import_acls_extacl($filename=null,$ARRAY=array(),$aclgpid=0){
	$q=new mysql_squid_builder();
	$acl=new squid_acls_groups();
	if($filename<>null){
		if(is_file($filename)){
			$ARRAY=$GLOBALS["CLASS_SOCKETS"]->unserializeb64(@file_get_contents($filename));
		}
	}



	if(!is_array($ARRAY)){
		echo "$filename, unable to decode Array()\n";return false;
	}

	if(!isset($ARRAY["webfilters_sqacls"])){
		echo "$filename, unable to decode webfilters_sqacls (".__LINE__.")\n";
		return false;
	}


	if(!is_array($ARRAY["webfilters_sqacls"])){
		echo "$filename, unable to decode webfilters_sqacls\n";return false;
	}

	if(isset($ARRAY["webfilters_sqaclaccess"])){
		if(!is_array($ARRAY["webfilters_sqaclaccess"])){
			if(!isset($ARRAY["SUBRULES"])){
				echo "$filename, unable to decode webfilters_sqaclaccess\n";return false;
			}
		}
	}

	if(!isset($ARRAY["SUBRULES"])){
		if(!is_array($ARRAY["webfilters_sqgroups"])){
			echo "$filename, unable to decode webfilters_sqgroups\n";return false;
		}
	}

	$keys=array();$values=array();
    foreach ($ARRAY["webfilters_sqacls"] as $key=>$value){
		$keys[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";

	}
	if($aclgpid>0){
		echo "Prepare SUB-ACL Master ACL:$aclgpid\n";
		$keys[]="`aclgpid`";
		$values[]="'$aclgpid'";
	}

	$sql="INSERT OR IGNORE INTO webfilters_sqacls (".@implode(",", $keys).") VALUES (".@implode(",", $values).")";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return false;}
	$ACLID=$q->last_id;


	echo "*** New ACL $ACLID ***\n";


	if(isset($ARRAY["SUBRULES"])){
		if(is_array($ARRAY["SUBRULES"])){
		    foreach ($ARRAY["SUBRULES"] as $arrayrule){
				if($GLOBALS["VERBOSE"]){echo "import_acls_extacl(null,$arrayrule,$ACLID)\n";}
				import_acls_extacl(null,$arrayrule,$ACLID);
			}
		}
	}




	if(isset($ARRAY["webfilters_sqaclaccess"])){
		$acl->aclrule_edittype($ACLID, $ARRAY["webfilters_sqaclaccess"]["httpaccess"], $ARRAY["webfilters_sqaclaccess"]["httpaccess_value"]);
		echo "New sqaclaccess for $ACLID {$ARRAY["webfilters_sqaclaccess"]["httpaccess"]}\n";
	}


	if(isset($ARRAY["webfilters_sqgroups"])){
            foreach ($ARRAY["webfilters_sqgroups"] as $index=>$grouparray){
				$GROUP_ARRAY=$grouparray["GROUP"];
				$GROUP_ITEMS=$grouparray["ITEMS"];
				$GROUP_DYN=$grouparray["DYN"];
				$keys=array();$values=array();
                foreach ($GROUP_ARRAY as $key=>$value){
					$keys[]="`$key`";
					$values[]="'".mysql_escape_string2($value)."'";
				}




				$sql="INSERT OR IGNORE INTO webfilters_sqgroups (".@implode(",", $keys).") VALUES (".@implode(",", $values).")";
				$q->QUERY_SQL($sql);
				if(!$q->ok){echo $q->mysql_error."\n$sql\n";return false;}
				$GPID=$q->last_id;
				$GROUPSACLS[$GPID]=true;

                foreach ($GROUP_ITEMS as $index=>$itemsArray){
					$keys=array();$values=array();

                    foreach ($itemsArray as $key=>$value){
						$keys[]="`$key`";
						$values[]="'".mysql_escape_string2($value)."'";
					}
					$keys[]="`gpid`";
					$values[]="$GPID";
					$sql="INSERT OR IGNORE INTO webfilters_sqitems (".@implode(",", $keys).") VALUES (".@implode(",", $values).")";
					$q->QUERY_SQL($sql);
					if(!$q->ok){echo $q->mysql_error."\n$sql\n[$index]\n";return false;}

				}

				if(count($GROUP_DYN)>0){
					$keys=array();$values=array();
                    foreach ($GROUP_DYN as $key=>$value){
						$keys[]="`$key`";
						$values[]="'".mysql_escape_string2($value)."'";
					}

					$keys[]="`gpid`";
					$values[]="$GPID";
					$sql="INSERT IGNORE INTO webfilter_aclsdynamic (".@implode(",", $keys).") VALUES (".@implode(",", $values).")";
					$q->QUERY_SQL($sql);
					if(!$q->ok){echo $q->mysql_error."\n$sql\n";return false;}
				}
			}

            foreach ($GROUPSACLS as $gpid=>$value){
				echo "Linking ACL $ACLID with group $gpid\n";
				$md5=md5($ACLID.$gpid);
				$sql="INSERT OR IGNORE INTO webfilters_sqacllinks (zmd5,aclid,gpid) VALUES('$md5','$ACLID','$gpid')";
				$q->QUERY_SQL($sql);
			}

	}



}
function test_sarg(){
	$sock=new sockets();
	$EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
	if(!is_numeric($EnableSargGenerator)){$EnableSargGenerator=0;}
	$unix=new unix();


	$SARGOK=false;
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	foreach ($f as $line){
		if(preg_match("#\/sarg\.log#", $line)){$SARGOK=true;break;}

	}

	$php=$unix->LOCATE_PHP5_BIN();

	if(!$SARGOK){
		if($EnableSargGenerator==0){return false;}
		shell_exec("$php ".__FILE__." --build --force >/dev/null 2>&1 &");
		return false;
	}else{
		if($EnableSargGenerator==1){return false;}
		shell_exec("$php ".__FILE__." --build --force >/dev/null 2>&1 &");
		return false;
	}
}
function disableUFDB(){
	shell_exec("/etc/init.d/ufdb-client stop");
}
function cache_rules(){
    $q=new squid_caches_rules();
	$q->build();
	echo @implode("\n", $q->final_array);

}
function bandwith_rules():bool{
	$bandwith=new squid_bandwith_builder();
	echo "\n-----------------\n\n";
	echo $bandwith->compile()."\n";
	return true;
}
function purge_dns():bool{
	squid_admin_mysql(1, "Reload proxy service for purging DNS Cache", null,__FILE__,__LINE__);
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	system("/usr/sbin/artica-phpfpm-service -reload-proxy");
    return true;
}
function test_caches(){
	$GLOBALS["VERBOSE"]=true;
	$squid=new squidbee();
	echo $squid->cache_dir_method_0();
	return true;
}
function quick_bann(){
	$EXEC_PID_FILE="/etc/artica-postfix/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();

	$pid=$unix->get_pid_from_file($EXEC_PID_FILE);
	if(!$GLOBALS["VERBOSE"]){
		if($unix->process_exists($pid,basename(__FILE__))){ return false; }
	}
	@file_put_contents($EXEC_PID_FILE, getmypid());

	$squid=new squidbee();
	$squid->ACL_BANNED_COMPUTERS_IP();

	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	$compile=false;

	if(isset($GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS_MAC"])){
		if($GLOBALS["VERBOSE"]){echo "MAC Blacklisted detected\n";}
		$MAC=false;
		foreach ($f as $index=>$line){
			if(preg_match("#deny.*?banned_mac_computers#", $line)){ if($GLOBALS["VERBOSE"]){echo "$line FOUND\n";} $MAC=true; break;}
		}

		if(!$MAC){$compile=true;}

	}
	if(isset($GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS"])){
		if($GLOBALS["VERBOSE"]){echo "IP Blacklisted detected\n";}
		$MAC=false;
		reset($f);
		foreach ($f as $index=>$line){
			if(preg_match("#deny.*?banned_computers#", $line)){ if($GLOBALS["VERBOSE"]){echo "$line FOUND\n";} $MAC=true; break;}
		}

		if(!$MAC){$compile=true;}

	}
	if(!$compile){
		if($GLOBALS["VERBOSE"]){echo "Just reload ok\n";}
		Reload_Squid();return false;}

		if($GLOBALS["VERBOSE"]){echo "Just reconfigure ok\n";}
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php ".__FILE__." --build >/dev/null 2>&1 &");
    return true;

}
function BuildSquidCertificate():bool{
	$squid=new squidbee();
	$squid->BuildSquidCertificate();
    return true;
}
function PortsConversion(){


	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	if($q->COUNT_ROWS("proxy_ports")==0){
		if($GLOBALS["VERBOSE"]){echo "proxy_ports == 0, Enforce\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IsPortsConverted", 0);
	}
	$sock=new sockets();
	$IsPortsConverted=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IsPortsConverted"));
	if($IsPortsConverted==1){
		if($GLOBALS["VERBOSE"]){echo "IsPortsConverted == True, Aborting\n";}
		return true;
	}

	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	$squid=new squidbee();
	if(!is_numeric($squid->second_listen_port)){$squid->second_listen_port=0;}
	if(!is_numeric($squid->ssl_port)){$squid->ssl_port=0;}
	$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
	if($SquidBinIpaddr==null){$SquidBinIpaddr="0.0.0.0";}


	$HTTP_PORT=$squid->listen_port;




	if($GLOBALS["VERBOSE"]){echo "hasProxyTransparent === $squid->hasProxyTransparent\n";}
	if($squid->hasProxyTransparent==1){
		$ligne=$q->mysqli_fetch_array("SELECT ID FROM proxy_ports WHERE ipaddr='$SquidBinIpaddr' AND port='$squid->listen_port'");
		if($ligne["ID"]==0){
			if(intval($squid->listen_port)>80){
				$PortName="{main_port}: Main transparent port $SquidBinIpaddr:$squid->listen_port";
				$sql="INSERT INTO proxy_ports (PortName,ipaddr,port,enabled,transparent) VALUES ('$PortName','$SquidBinIpaddr','$squid->listen_port',1,1)";
				$q->QUERY_SQL($sql);
				if(!$q->ok){echo $q->mysql_error."\n";return false;}
			}

			if(intval($squid->second_listen_port)>80){
				$PortName="Main connected port $SquidBinIpaddr:$squid->second_listen_port";
				$sql="INSERT INTO proxy_ports (PortName,ipaddr,port,enabled,transparent,AuthPort) VALUES ('$PortName','$SquidBinIpaddr','$squid->second_listen_port',1,0,$EnableKerbAuth)";
				$q->QUERY_SQL($sql);
				if(!$q->ok){echo $q->mysql_error."\n";return false;}
			}


			if($q->COUNT_ROWS("proxy_ports")>0){
				if($GLOBALS["VERBOSE"]){echo "SUCCESS\n";}
				$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IsPortsConverted", 1);
				return true;
			}

		}

		if($q->COUNT_ROWS("proxy_ports")>0){
			if($GLOBALS["VERBOSE"]){echo "SUCCESS\n";}
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IsPortsConverted", 1);
			return true;
		}

	}


	if($GLOBALS["VERBOSE"]){echo "Main connected port $SquidBinIpaddr:$squid->listen_port\n";}


	$PortName="{main_port}: Main connected port $SquidBinIpaddr:$squid->listen_port";


	$ligne=$q->mysqli_fetch_array("SELECT ID FROM proxy_ports WHERE port=".intval($squid->listen_port));
	if(intval($ligne["ID"])>0){
		if($GLOBALS["VERBOSE"]){echo "ID:{$ligne["ID"]} For $SquidBinIpaddr:$squid->listen_port\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IsPortsConverted", 1);
		return false;
	}



	if(intval($squid->listen_port)>80){
		$sql="INSERT INTO proxy_ports (PortName,ipaddr,port,enabled,AuthPort) VALUES ('$PortName','$SquidBinIpaddr','$squid->listen_port',1,$EnableKerbAuth)";
		$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
		if(!$q->ok){echo $q->mysql_error."\n";return false;}
	}


	if($q->COUNT_ROWS("proxy_ports")>0){
		if($GLOBALS["VERBOSE"]){echo "SUCCESS\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IsPortsConverted", 1);
		return true;
	}

	if($GLOBALS["REBUILD"]){return true;}

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.squid.global.access.php --ports --noverifacls");


	return true;
}
function dump_ntlm():bool{
	$squid=new squidbee();
	echo $squid->ntlm_auth_conf();
	return true;
}
function test_notifs():bool{
	$GLOBALS["DEBUG_NOTIFS"]=true;
	squid_admin_mysql(0, "Test notifications", "This is a notification test",__FILE__,__LINE__);
    return true;
}

?>