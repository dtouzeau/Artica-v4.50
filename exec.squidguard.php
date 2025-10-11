<?php
if(isset($_GET["verbose"])){ini_set_verbosedx();}else{	ini_set('display_errors', 0);ini_set('error_reporting', 0);}
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["RESTART"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["WRITELOGS"]=false;
$GLOBALS["TITLENAME"]="URLfilterDB daemon";
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
$_GET["LOGFILE"]="/var/log/artica-postfix/dansguardian.compile.log";
if(posix_getuid()<>0){
	$ID=0;
	$TARGET_GROUP_SOURCE=null;
	$CATEGORY_SOURCE=null;
	$fatalerror=null;
	$HTTP_HOST=null;
	header("Pragma: no-cache");
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	$proto="http";
	$HTTP_HOST=$_SERVER["HTTP_HOST"];
	$REQUEST_URI=$_SERVER["REQUEST_URI"];
	$SERVER_NAME=$_SERVER["SERVER_NAME"];
	if(isset($_GET["fatalerror"])){$ID=0;$fatalerror="&fatalerror=yes";}
	if(isset($_GET["loading-database"])){$ID=0;$fatalerror="&loading-database=yes";}
	if($HTTP_HOST==null){$HTTP_HOST=$SERVER_NAME;}
	$SquidGuardServerName=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardServerName");
	$SquidGuardApachePort=intval(@file_get_contents("/etc/artica-postfix/settings/SquidGuardApachePort"));
	$SquidGuardApacheSSLPort=intval(@file_get_contents("/etc/artica-postfix/settings/SquidGuardApacheSSLPort"));
	if($SquidGuardApacheSSLPort==0){$SquidGuardApacheSSLPort=9025;}
	if($SquidGuardApachePort==0){$SquidGuardApachePort=9020;}
	$localport=$SquidGuardApachePort;
	
	if(isset($_SERVER["HTTPS"])){$proto="https";$localport=$SquidGuardApacheSSLPort;}
	if(isset($_GET["rule-id"])){$ID=$_GET["rule-id"];}
	if(isset($_GET["category"])){$CATEGORY_SOURCE=$_GET["category"];}
	if(isset($_GET["targetgroup"])){
		$TARGET_GROUP_SOURCE=$_GET["targetgroup"];
		if($CATEGORY_SOURCE==null){$CATEGORY_SOURCE=$TARGET_GROUP_SOURCE;}
	}
	$uri="$proto://$HTTP_HOST/$REQUEST_URI";
	if(isset($_GET["url"])){$uri=$_GET["url"];}
	$uri=urlencode($uri);
	$link="$proto://$SquidGuardServerName:$localport/ufdbguardd.php?rule-id=$ID&category=$CATEGORY_SOURCE&targetgroup=$TARGET_GROUP_SOURCE{$fatalerror}&url=$uri\n";
	
	$data="
	<html>
	<head>
		<meta http-equiv=\"refresh\" content=\"0; url=$link/\" />
	</head>
			<body>
				<center><H1>Please redirecting...</H1></center>
			</body>
	</html>";
	echo $data;
	
	exit();
}

if(preg_match("#--ouput#",implode(" ",$argv),$re)){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["GETPARAMS"]=@implode(" Params:",$argv);
$GLOBALS["CMDLINEXEC"]=@implode("\nParams:",$argv);

include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squidguard.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.compile.ufdbguard.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.ufdbguard-tools.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");



if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(count($argv)>0){
	$imploded=implode(" ",$argv);
	
	if(preg_match("#--(output|ouptut)#",$imploded)){
		$GLOBALS["OUTPUT"]=true;
	}
	
	if(preg_match("#--verbose#",$imploded)){
			$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;
			$GLOBALS["OUTPUT"]=true;ini_set_verbosed(); 
	}
	
	
	
	if(preg_match("#--reload#",$imploded)){$GLOBALS["RELOAD"]=true;}
	if(preg_match("#--force#",$imploded)){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--shalla#",$imploded)){$GLOBALS["SHALLA"]=true;}
	if(preg_match("#--restart#",$imploded)){$GLOBALS["RESTART"]=true;}
	if(preg_match("#--catto=(.+?)\s+#",$imploded,$re)){$GLOBALS["CATTO"]=$re[1];}
	
	if($argv[1]=="--disks"){DisksStatus();exit;}
	if($argv[1]=="--version"){exit;}
	if($argv[1]=="--dump-adrules"){dump_adrules($argv[2]);exit;}
	if($argv[1]=="--dbmem"){ufdbdatabases_in_mem();exit;}
	if($argv[1]=="--artica-db-status"){ufdguard_artica_db_status();exit;}
    if($argv[1]=="--json-flat"){json_to_flat($argv[2]);exit;}
	
	
	
	
	$argvs=$argv;
	unset($argvs[0]);
	
	if($argv[1]=="--stop"){stop_ufdbguard();exit;}
	if($argv[1]=="--reload"){build_ufdbguard_HUP();exit;}
	if($argv[1]=="--reload-ufdb"){build_ufdbguard_HUP();exit;}
	if($argv[1]=="--databases-status"){databases_status();exit;}
	if($argv[1]=="--ufdbguard-status"){print_r(UFDBGUARD_STATUS());exit;}
	if($argv[1]=="--cron-compile"){cron_compile();exit;}
	if($argv[1]=="--compile-category"){UFDBGUARD_COMPILE_CATEGORY($argv[2]);exit;}
	if($argv[1]=="--compile-all-categories"){UFDBGUARD_COMPILE_ALL_CATEGORIES();exit;}
	if($argv[1]=="--ufdbguard-recompile-dbs"){echo UFDBGUARD_COMPILE_ALL_CATEGORIES();exit;}
	if($argv[1]=="--phraselists"){echo CompileCategoryWords();exit;}
	if($argv[1]=="--fix1"){exit;}
	if($argv[1]=="--bads"){echo remove_bad_files();exit;}
	if($argv[1]=="--reload131"){exit;}
	
	$GLOBALS["EXECUTEDCMDLINE"]=@implode(" ", $argvs);
	if($GLOBALS["VERBOSE"]){echo "Execute ".@implode(" ", $argv)."\n";}
	

	if($argv[1]=="--parse"){echo inject($argv[2],$argv[3],$argv[4]);exit;}
	if($argv[1]=="--conf"){build_ufdbguard_config();exit;}


	

	if($argv[1]=="--ufdbguard-dbs"){echo UFDBGUARD_COMPILE_DB();exit;}
	if($argv[1]=="--ufdbguard-miss-dbs"){echo ufdbguard_recompile_missing_dbs();exit;}
	
	if($argv[1]=="--ufdbguard-schedule"){ufdbguard_schedule();exit;}
	if($argv[1]=="--ufdbguard-start"){ufdbguard_start();exit;}
	if($argv[1]=="--list-missdbs"){BuildMissingUfdBguardDBS(false,true);exit;}				
	if($argv[1]=="--parsedir"){ParseDirectory($argv[2]);exit;}
	if($argv[1]=="--notify-dnsmasq"){notify_remote_proxys_dnsmasq();exit;}
	if($argv[1]=='--build-ufdb-smoothly'){$GLOBALS["FORCE"]=true;echo build_ufdbguard_smooth();echo "Starting......: ".date("H:i:s")." Starting UfdGuard FINISH DONE\n";exit;}

	
	
}
	


$unix=new unix();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".MAIN.pid";
$pid=@file_get_contents($pidfile);
if($unix->process_exists($pid,basename(__FILE__))){
	$timefile=$unix->PROCCESS_TIME_MIN($pid);
	if($timefile<6){
		writelogs(basename(__FILE__).": Already running PID $pid since {$timefile}mn.. aborting the process",
		basename(__FILE__),__FILE__,__LINE__);
		exit();
	}else{
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}
}
@file_put_contents($pidfile, getmypid());
if($GLOBALS["VERBOSE"]){echo "New PID ".getmypid()." [1]={$argv[1]}\n";}

if($argv[1]=="--categories"){build_categories();exit;}
if(isset($argv[2])){if($argv[2]=="--reload"){$GLOBALS["RELOAD"]=true;}}
if($argv[1]=="--build"){build_ufdbguard_config();exit();}
if($argv[1]=="--status"){echo status();exit;}
if($argv[1]=="--compile"){echo compile_databases();exit;}
if($argv[1]=="--db-status"){print_r(databasesStatus());exit;}
if($argv[1]=="--db-status-www"){echo serialize(databasesStatus());exit;}

if($argv[1]=="--compile-single"){echo CompileSingleDB($argv[2]);exit;}
if($argv[1]=="--conf"){echo conf();exit;}




//http://cri.univ-tlse1.fr/documentations/cache/squidguard.html


function build_categories(){
	$q=new mysql_squid_builder();
	
	$sql="SELECT LOWER(pattern) FROM category_porn WHERE enabled=1 AND pattern REGEXP '[a-zA-Z0-9\_\-]+\.[a-zA-Z0-9\_\-]+' ORDER BY pattern INTO OUTFILE '/tmp/porn.txt' FIELDS OPTIONALLY ENCLOSED BY 'n'";
	$q->QUERY_SQL($sql);	
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	
}

function build_progress($text,$pourc){
	echo "[{$pourc}%]: $text\n";
	$cachefile=PROGRESS_DIR."/ufdbguard.compile.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function json_to_flat($path){
    if(!is_file($path)){
        echo "$path no such file\n";
        exit;
    }
    $tfile=$path.".flat.txt";
    $jsondata=@file_get_contents($path);
    $json=json_decode($jsondata);
    foreach ($json as $num=>$domain){
        $f[]=$domain;
    }
    @file_put_contents($tfile,@implode("\n",$f));
}




function build_progress_install($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/ufdb.enable.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	sleep(1);

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



function build_ufdbguard_smooth(){
	$users=new usersMenus();
	$unix=new unix();
	$ufdbguardd_path=$unix->find_program("ufdbguardd");
	$php=$unix->LOCATE_PHP5_BIN();

	
	
	if(!is_file($ufdbguardd_path)){
		$TMPDIR=$unix->TEMP_DIR();
		$Tfile="ufdbGuard-64-1.31.tar.gz";
		include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
		build_progress("{downloading} $Tfile",5);
		$curl=new ccurl("http://articatech.net/download/$Tfile");
		if(!$curl->GetFile("$TMPDIR/$Tfile")){
			build_progress("{downloading} {failed2}",110);
			return;
		}
		
		$tar=$unix->find_program("tar");
		build_progress("{extracting} $Tfile",5);
		system("$tar -xvf $TMPDIR/$Tfile -C /");
		$ufdbguardd_path="/usr/bin/ufdbguardd";
		
		@unlink("$TMPDIR/$Tfile");
	}
	
	
	
	
	if(!is_file($ufdbguardd_path)){
		echo "Starting......: ".date("H:i:s")." ufdbguardd, no such binary...\n";
		build_progress("{not_installed}, aborting",110);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_UFDBGUARD_INSTALLED", 0);
		return;
		
	}

	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_UFDBGUARD_INSTALLED", 1);
	@chmod($ufdbguardd_path,0755);
	system("$php /usr/share/artica-postfix/exec.ufdbconfig.php");

	if(function_exists('WriteToSyslogMail')){WriteToSyslogMail("build_ufdbguard_smooth() -> reconfigure UfdbGuardd", basename(__FILE__));}
	

	

	system("$php /usr/share/artica-postfix/exec.ufdb.enable.php --ufdb");
	$squidbin=$unix->LOCATE_SQUID_BIN();
	
	echo "Starting......: ".date("H:i:s")." Webfiltering service ". date("Y-m-d H:i:s")."\n";
	build_ufdbguard_config();
	build_progress("{reloading_service}",70);
	if(!build_ufdbguard_HUP()){
		build_progress("{reloading_service} {failed}",75);
		ufdbguard_start();
	}
	
	
	if($GLOBALS["RELOAD"]){
		if(is_file("/etc/init.d/ufdb-http")){
			build_progress("{reloading_service} HTTP",80);
			system("/etc/init.d/ufdb-http restart");
		}
	}
	
	
	if(is_file($squidbin)){
		if(!build_ufdbguard_isinconf()){
			build_progress("{reconfiguring_proxy_service}",95);
			squid_admin_mysql(2, "{reloading_proxy_service} (By Web-Filtering)", null,__FILE__,__LINE__);
			system("/usr/sbin/artica-phpfpm-service -proxy-plugins");
			
		}
	}
	
	$PDSNInUfdb=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDSNInUfdb"));
	if($PDSNInUfdb==1){
		build_progress("{reconfiguring_dns_service}",95);
		$pdns_control=$unix->find_program("pdns_control");
		squid_admin_mysql(1, "Reloading DNS service (By Web-Filtering)", null,__FILE__,__LINE__);
		if(is_file($pdns_control)){system("$pdns_control cycle >/dev/null");}
	}
	
	build_progress("{done}",100);
	cluster_mode();
}




function build_ufdbguard_isinconf(){return true;}


function build_ufdbguard_HUP(){
	if(isset($GLOBALS["build_ufdbguard_HUP_EXECUTED"])){return;}
	$GLOBALS["build_ufdbguard_HUP_EXECUTED"]=true;
	$unix=new unix();
	$sock=new sockets();$forceTXT=null;
	$ufdbguardReloadTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardReloadTTL"));
	if($ufdbguardReloadTTL<1){$ufdbguardReloadTTL=10;}
	$php5=$unix->LOCATE_PHP5_BIN();
	$rm=$unix->find_program("rm");
	
	shell_exec("$php5 /usr/share/artica-postfix/exec.ufdbclient.reload.php");
	shell_exec("$rm /home/squid/error_page_cache/*");
	
	if(function_exists("debug_backtrace")){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
			$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
		}
	}
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	
	$timeFile="/etc/artica-postfix/pids/UfdbGuardReload.time";
	$TimeReload=$unix->file_time_min($timeFile);
	if(!$GLOBALS["FORCE"]){
		if($TimeReload<$ufdbguardReloadTTL){
			build_progress("{reloading_service} {failed}",110);
			
			$unix->_syslog("Webfiltering service Aborting reload, last reload since {$TimeReload}Mn, need at least {$ufdbguardReloadTTL}Mn", basename(__FILE__));
			echo "Starting......: ".date("H:i:s")." Webfiltering service Aborting reload, last reload since {$TimeReload}Mn, need at least {$ufdbguardReloadTTL}Mn\n";
			return;
		}
	}else{
		echo "Starting......: ".date("H:i:s")." --- FORCED --- ufdbGuard last reload was {$TimeReload}mn\n";
	}
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	
	$pid=ufdbguard_pid();
	build_progress("{reloading_service} $pid",71);
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$ufdbguardd=$unix->find_program("ufdbguardd");
	if(strlen($ufdbguardd)<5){WriteToSyslogMail("ufdbguardd no such binary", basename(__FILE__));return;}
	$kill=$unix->find_program("kill");

	
	
	
if($unix->process_exists($pid)){
		$processTTL=intval($unix->PROCCESS_TIME_MIN($pid));
		
		$LastTime=intval($unix->file_time_min($timeFile));
		build_progress("{reloading_service} $pid {$processTTL}Mn",72);
		
		echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading service TTL {$processTTL}Mn\n";
		echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading service Last config since {$LastTime}Mn\n";
		echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading Max reload {$ufdbguardReloadTTL}Mn\n";
		
		if(!$GLOBALS["FORCE"]){
			echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading force is disabled\n";
			if($LastTime<$ufdbguardReloadTTL){
				squid_admin_mysql(2, "Reloading Web Filtering PID: $pid [Aborted] last reload {$LastTime}Mn, need {$ufdbguardReloadTTL}mn",null,__FILE__,__LINE__);
				echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading service Aborting... minimal time was {$ufdbguardReloadTTL}mn - Current {$LastTime}mn\n$called\n";
				return;
			}			
			
			
			if($processTTL<$ufdbguardReloadTTL){
				squid_admin_mysql(2, "Reloading Web Filtering PID: $pid [Aborted] {$processTTL}Mn, need {$ufdbguardReloadTTL}mn",null,__FILE__,__LINE__);
				echo "Starting......: ".date("H:i:s")." Webfiltering service PID: $pid  Reloading service Aborting... minimal time was {$ufdbguardReloadTTL}mn\n$called\n";
				return;
			}
		}
		
		
		if($GLOBALS["FORCE"]){ $forceTXT=" with option FORCE enabled";$prefix="[FORCED]:";}
		@unlink($timeFile);
		@file_put_contents($timeFile, time());
		
		echo "Starting......: ".date("H:i:s")." Webfiltering service Reloading service PID:$pid {$processTTL}mn\n";
		squid_admin_mysql(1, "{$prefix}Reloading Web Filtering service PID: $pid TTL {$processTTL}Mn","$forceTXT\n$called\n{$GLOBALS["CMDLINEXEC"]}");
		
		build_progress("{reloading_service} (Web-filtering) HUP $pid",75);
		unix_system_HUP($pid);

		return true;
}
	
	squid_admin_mysql(1, "Warning, Reloading Web Filtering but not running [{action}={start}]","$forceTXT\n$called\n{$GLOBALS["CMDLINEXEC"]}");
	echo "Starting......: ".date("H:i:s")." Webfiltering service reloading service no pid is found, Starting service...\n";
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	build_progress("{starting_service}",76);
	if(!ufdbguard_start()){return;}
	
	echo "Starting......: ".date("H:i:s")." Webfiltering Service restarting ufdb-tail process\n";
	shell_exec("/etc/init.d/ufdb-tail restart");
	build_progress("{starting_service} {done}",77);
	return true;
}

function ufdbguard_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/tmp/ufdbguardd.pid");
	if($unix->process_exists($pid)){
	    return $pid;
	}
	$ufdbguardd=$unix->find_program("ufdbguardd");
	return $unix->PIDOF($ufdbguardd);
}




function ufdguard_get_listen_port(){
	$f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
	foreach ($f as $index=>$ligne){
		if(preg_match("#^port\s+([0-9]+)#", $ligne,$re)){return $re[1];}
		
	}
	return 3977;
}




function ufdbguard_start(){
	$unix=new unix();
	$sock=new sockets();
	$nohup=$unix->find_program("nohup");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		build_progress("Already task executed", 110);
		echo "Starting......: ".date("H:i:s")." Webfiltering service Starting service aborted, task pid already running $pid\n";
		writelogs(basename(__FILE__).":Already executed.. aborting the process",basename(__FILE__),__FILE__,__LINE__);
		return;
	}
	@file_put_contents($pidfile, getmypid());	
	
	
	$pid_path="/var/tmp/ufdbguardd.pid";
	if(!is_dir("/var/tmp")){@mkdir("/var/tmp",0775,true);}
	$ufdbguardd_path=$unix->find_program("ufdbguardd");
	$master_pid=ufdbguard_pid();

	

	if(!$unix->process_exists($master_pid)){
		if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("UfdGuard master Daemon seems to not running, trying with pidof", basename(__FILE__));}
		$master_pid=$unix->PIDOF($ufdbguardd_path);
		if($unix->process_exists($master_pid)){
			echo "Starting......: ".date("H:i:s")." UfdGuard master is running, updating PID file with $master_pid\n";
			if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("UfdGuard master is running, updating PID file with $master_pid", basename(__FILE__));}
			@file_put_contents($pid_path,$master_pid);	
			build_progress("Already running...",76);
			return true;
		}
	}
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	$UseRemoteUfdbguardService=$sock->GET_INFO('UseRemoteUfdbguardService');
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if($UseRemoteUfdbguardService==1){$EnableUfdbGuard=0;}
	if($SQUIDEnable==0){$EnableUfdbGuard=0;}
	if($EnableUfdbGuard==0){echo "Starting......: ".date("H:i:s")." Starting UfdGuard master service Aborting, service is disabled\n";return;}
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	squid_admin_mysql(2, "{starting_web_filtering} engine service","$trace\n{$GLOBALS["CMDLINEXEC"]}");
		
	echo "Starting......: ".date("H:i:s")." Starting UfdGuard master service...\n";
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Starting UfdGuard master service...", basename(__FILE__));}
	@mkdir("/var/log/ufdbguard",0755,true);
	@file_put_contents("/var/log/ufdbguard/ufdbguardd.log", "#");
	@chown("/var/log/ufdbguard/ufdbguardd.log", "squid");
	@chgrp("/var/log/ufdbguard/ufdbguardd.log", "squid");	
	
	
	shell_exec("$nohup /usr/sbin/artica-phpfpm-service -start-ufdb >/dev/null 2>&1 &");
	
	
	for($i=1;$i<5;$i++){
		build_progress("Starting {webfiltering} waiting $i/5",76);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Starting UfdGuard  waiting $i/5\n";}
		sleep(1);
		$pid=ufdbguard_pid();
		if($unix->process_exists($pid)){break;}
	}
	
	echo "Starting......: ".date("H:i:s")." Starting UfdGuard master init.d ufdb done...\n";
	$master_pid=ufdbguard_pid();
	if(!$unix->process_exists($master_pid)){
		echo "Starting......: ".date("H:i:s")." Starting UfdGuard master service failed...\n";
		squid_admin_mysql(0, "{starting_web_filtering} engine service failed","$trace\n{$GLOBALS["CMDLINEXEC"]}\n");
		return false;
	}
	echo "Starting......: ".date("H:i:s")." Starting UfdGuard master success pid $master_pid...\n";
	squid_admin_mysql(2, "{starting_web_filtering} engine service success","$trace\n{$GLOBALS["CMDLINEXEC"]}\n");
	echo "Starting......: ".date("H:i:s")." Starting UfdGuard master ufdbguard_start() function done\n";
	return true;
	
}

function build_ufdbguard_config():bool{
	build_progress("Building parameters",10);
    $ufdb=new compile_ufdbguard();
	$datas=$ufdb->buildConfig();
    file_put_contents("/etc/ufdbguard/ufdbGuard.conf",$datas);
    file_put_contents("/etc/squid3/ufdbGuard.conf",$datas);
    build_progress("Saving configuration {done}",65);
    return true;
}


function conf(){
	$users=new usersMenus();
	$unix=new unix();
	
	@mkdir("/var/tmp",0775,true);
	
	
	if(!is_file("/var/log/ufdbguard/ufdbguardd.log")){
		@mkdir("/var/log/ufdbguard",0755,true);
		@file_put_contents("/var/log/ufdbguard/ufdbguardd.log", "see /var/log/squid/ufdbguardd.log\n");
		shell_exec("chmod 777 /var/log/ufdbguard/ufdbguardd.log");
	}
	
	
	if(is_file("/usr/sbin/ufdbguardd")){
		if(!is_file("/usr/bin/ufdbguardd")){
			$unix=new unix();
			$ln=$unix->find_program("ln");
			shell_exec("$ln -s /usr/sbin/ufdbguardd /usr/bin/ufdbguardd");
		}
	}
	@mkdir("/etc/ufdbguard",0755,true);
	
	build_ufdbguard_config();

	if($users->APP_UFDBGUARD_INSTALLED){
		$chmod=$unix->find_program("chmod");
		shell_exec("$chmod 755 /etc >/dev/null 2>&1");
		shell_exec("$chmod 755 /etc/ufdbguard >/dev/null 2>&1");
		shell_exec("$chmod 755 /var/log/ufdbguard >/dev/null 2>&1");
		shell_exec("$chmod 755 /var/log/squid >/dev/null 2>&1");
		shell_exec("$chmod -R 755 /var/lib/squidguard >/dev/null 2>&1 &");	
		squid_admin_mysql(1,"Asking to reload ufdbguard",null,__FILE__,__LINE__);	
		build_ufdbguard_HUP();
		
	}
	
	
}









	

	
function FileMD5($path){
if(strlen(trim($GLOBALS["md5sum"]))==0){
		$unix=new unix();
		$md5sum=$unix->find_program("md5sum");
		$GLOBALS["md5sum"]=$md5sum;
}

if(strlen(trim($GLOBALS["md5sum"]))==0){return md5(@file_get_contents($path));}


exec("{$GLOBALS["md5sum"]} $path 2>&1",$res);
$data=trim(@implode(" ",$res));
if(preg_match("#^(.+?)\s+.+?#",$data,$re)){return trim($re[1]);}
	
}

function ufdbguard_watchdog_remove(){
}


function dump_adrules($ruleid){
	
	$ufbd=new compile_ufdbguard();
	$ufbd->build_membersrule($ruleid);
	@file_put_contents(PROGRESS_DIR."/ufdb-dump-$ruleid.wt",0);
	@file_put_contents(PROGRESS_DIR."/ufdb-dump-$ruleid.txt","\n");
	@chmod(PROGRESS_DIR."/ufdb-dump-$ruleid.wt",0777);
	@chmod(PROGRESS_DIR."/ufdb-dump-$ruleid.txt",0777);
	if($GLOBALS["VERBOSE"]){echo "/usr/share/artica-postfix/external_acl_squid_ldap.php --db $ruleid\n";}
	exec("/usr/share/artica-postfix/external_acl_squid_ldap.php --db $ruleid --output 2>&1", $results);
	@file_put_contents(PROGRESS_DIR."/ufdb-dump-$ruleid.wt",1);
	@file_put_contents(PROGRESS_DIR."/ufdb-dump-$ruleid.txt",@implode("\n", $results));
	
}





	

function databasesStatus(){
	$datas=explode("\n",@file_get_contents("/etc/squid/squidGuard.conf"));
	$count=0;
	$f=array();
	foreach ($datas as $a=>$b){
		
		if(preg_match("#domainlist.+?(.+)#",$b,$re)){
			$f[]["domainlist"]["path"]="/var/lib/squidguard/{$re[1]}";
			
			continue;
			
		}
		
		if(preg_match("#expressionlist.+?(.+)#",$b,$re)){
			$f[]["expressionlist"]["path"]="/var/lib/squidguard/{$re[1]}";
			
			continue;
		}
		
		if(preg_match("#urllist.+?(.+)#",$b,$re)){
			$f[]["urllist"]["path"]="/var/lib/squidguard/{$re[1]}";
			
			continue;
		}
		
		
	}



    foreach ($f as $a=>$b){

		$domainlist=$b["domainlist"]["path"];
		$expressionlist=$b["expressionlist"]["path"];
		$urllist=$b["urllist"]["path"];
		
		if(is_file($domainlist)){
			$key="domainlist";
			$path=$domainlist;
		}
		
		if(is_file($expressionlist)){
			$key="expressionlist";
			$path=$expressionlist;
		}

		if(is_file($urllist)){
			$key="urllist";
			$path=$urllist;
		}			
		
		$d=explode("\n",@file_get_contents($path));
		$i[$path]["type"]=$key;
		$i[$path]["size"]=@filesize("$domainlist.db");
		$i[$path]["linesn"]=count($d);
		$i[$path]["date"]=filemtime($path);
		
		
		
		
	}
	
	return $i;
	
}

function status(){
	
	
	$squid=new squidbee();
	$array=$squid->SquidGuardDatabasesStatus();
	$conf[]="[APP_SQUIDGUARD]";
	$conf[]="service_name=APP_SQUIDGUARD";
	
	
	if(is_array($array)){
		$conf[]="running=0";
		$conf[]="why={waiting_database_compilation}<br>{databases}:&nbsp;".count($array);
		return implode("\n",$conf);
		
	}
	
	
	$unix=new unix();
	$users=new usersMenus();
	$pidof=$unix->find_program("pidof");
	exec("$pidof $users->SQUIDGUARD_BIN_PATH",$res);
	$array=explode(" ",implode(" ",$res));
	foreach ($array as $index=>$line){
		if(preg_match("#([0-9]+)#",$line,$ri)){
			$pid=$ri[1];
			$inistance=$inistance+1;
			$mem=$mem+$unix->MEMORY_OF($pid);
			$ppid=$unix->PPID_OF($pid);
		}
	}
	$conf[]="running=1";
	$conf[]="master_memory=$mem";
	$conf[]="master_pid=$ppid";
	$conf[]="other={processes}:$inistance"; 
	return implode("\n",$conf);
	
}

function CompileSingleDB($db_path){
	$user=GetSquidUser();
	$users=new usersMenus();
	$unix=new unix();
	if(strpos($db_path,".db")>0){$db_path=str_replace(".db","",$db_path);}
	$verb=" -d";
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	exec($users->SQUIDGUARD_BIN_PATH." $verb -C $db_path",$repair);	
	shell_exec("$chown -R $user /var/lib/squidguard/*");
	shell_exec("$chmod -R 755 /var/lib/squidguard/*");	
	shell_exec("$chmod -R ug+x /var/lib/squidguard/*");	
	
	$db_recover=$unix->LOCATE_DB_RECOVER();
	shell_exec("$db_recover -h ".dirname($db_path));
    build_ufdbguard_config();
	KillSquidGuardInstances();	

	
}

function KillSquidGuardInstances(){
	$unix=new unix();
	$users=new usersMenus();
	$pidof=$unix->find_program("pidof");
	if(strlen($pidof)>3){
		exec("$pidof $users->SQUIDGUARD_BIN_PATH 2>&1",$results);
		$pids=trim(@implode(" ",$results));
		if(strlen($pids)>3){
			echo "Starting......: ".date("H:i:s")." squidGuard kill $pids PIDs\n";
			shell_exec("/bin/kill $pids");
		}
		
	}	
	
}


function compile_databases(){
	$users=new usersMenus();
	$squid=new squidbee();
	$array=$squid->SquidGuardDatabasesStatus();
	$verb=" -d";
	
	
		$array=$squid->SquidGuardDatabasesStatus(0);

	
	if( count($array)>0){
		while (list ($index, $file) = each ($array)){
			echo "Starting......: ".date("H:i:s")." squidGuard compiling ". count($array)." databases\n";
			$file=str_replace(".db",'',$file);
			$textfile=str_replace("/var/lib/squidguard/","",$file);
			echo "Starting......: ".date("H:i:s")." squidGuard compiling $textfile database ".($index+1) ."/". count($array)."\n";
			if($GLOBALS["VERBOSE"]){$verb=" -d";echo $users->SQUIDGUARD_BIN_PATH." $verb -C $file\n";}
			system($users->SQUIDGUARD_BIN_PATH." -P$verb -C $file");
		}
	}else{
		echo "Starting......: ".date("H:i:s")." squidGuard compiling all databases\n";
		if($GLOBALS["VERBOSE"]){$verb=" -d";echo $users->SQUIDGUARD_BIN_PATH." $verb -C all\n";}
		system($users->SQUIDGUARD_BIN_PATH." -P$verb -C all");
	}

	
		
	$user=GetSquidUser();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	shell_exec("$chown -R $user /var/lib/squidguard/*");
	shell_exec("$chmod -R 755 /var/lib/squidguard/*");		
 	system("/usr/sbin/artica-phpfpm-service -proxy-plugins");
    build_ufdbguard_config();
	KillSquidGuardInstances();
	cluster_mode();
	
	
 
 
}

function CacheManager_default(){
	$sock=new sockets();
	$LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
	$WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
		
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]="contact@articatech.com";}
	$LicenseInfos["EMAIL"]=str_replace("'", "", $LicenseInfos["EMAIL"]);
	$LicenseInfos["EMAIL"]=str_replace('"', "", $LicenseInfos["EMAIL"]);
	$LicenseInfos["EMAIL"]=str_replace(' ', "", $LicenseInfos["EMAIL"]);
	return $LicenseInfos["EMAIL"];
}

function CacheManager(){
	$sock=new sockets();
	$cache_mgr_user=$sock->GET_INFO("cache_mgr_user");
	if($cache_mgr_user<>null){return $cache_mgr_user;}
	return CacheManager_default();
}









function GetSquidUser(){
	$unix=new unix();
	$squidconf=$unix->SQUID_CONFIG_PATH();
	$group=null;
	if(!is_file($squidconf)){
		echo "Starting......: ".date("H:i:s")." squidGuard unable to get squid configuration file\n";
		return "squid:squid";
	}
	
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

function ParseDirectory($path){}

function sourceCategoryToArticaCategory($category){
	$array["gambling"]="gamble";
	$array["gamble"]="gamble";
	$array["hacking"]="hacking";
	$array["malware"]="malware";
	$array["phishing"]="phishing";
	$array["porn"]="porn";
	$array["sect"]="sect";
	$array["socialnetwork"]="socialnet";
	$array["violence"]="violence";
	$array["adult"]="porn";
	$array["ads"]="publicite";
	$array["warez"]="warez";
	$array["drugs"]="drogue";
	$array["forums"]="forums";
	$array["filehosting"]="filehosting";
	$array["games"]="games";
	$array["astrology"]="astrology";
	$array["publicite"]="publicite";
	$array["radio"]="webradio";
	$array["sports"]="recreation/sports";
	$array["getmarried"]="getmarried";
	$array["police"]="police";
	$array["press"]="news";
	$array["youtube"]="youtube";
	$array["google"]="google";
	$array["apple"]="apple";
	$array["amazonaws"]="amazonaws";
	$array["citrix"]="citrix";
	$array["akamai"]="akamai";
	$array["yahoo"]="yahoo";
	$array["skype"]="skype";
	$array["facebook"]="facebook";
	$array["audio-video"]="audio-video";
	$array["webmail"]="webmail";
	$array["chat"]="chat";
	$array["social_networks"]="socialnet";
	$array["ads"]="publicite";
	$array["adult"]="porn";
	$array["aggressive"]="aggressive";
	$array["astrology"]="astrology";
	
	$array["bank"]="finance/banking";
	$array["blog"]="blog";
	$array["celebrity"]="celebrity";
	$array["chat"]="chat";
	$array["cleaning"]="cleaning";
	$array["dangerous_material"]="dangerous_material";
	$array["dating"]="dating";
	$array["drugs"]="porn";
	$array["filehosting"]="filehosting";
	$array["financial"]="financial";
	$array["forums"]="forums";
	$array["gambling"]="gamble";
	$array["games"]="games";
	$array["hacking"]="hacking";
	$array["jobsearch"]="jobsearch";
	$array["liste_bu"]="liste_bu";
	$array["malware"]="malware";
	$array["marketingware"]="marketingware";
	$array["mixed_adult"]="mixed_adult";
	$array["mobile-phone"]="mobile-phone";
	$array["phishing"]="phishing";
	
	$array["radio"]="webradio";
	$array["reaffected"]="reaffected";
	$array["redirector"]="redirector";
	$array["remote-control"]="remote-control";
	$array["sect"]="sect";
	$array["sexual_education"]="sexual_education";
	$array["shopping"]="shopping";
	$array["social_networks"]="socialnet";
	$array["sports"]="recreation/sports";
	$array["getmarried"]="getmarried";
	$array["police"]="police";	

	$array["tricheur"]="tricheur";
	$array["violence"]="violence";
	$array["warez"]="warez";
	$array["webmail"]="webmail";
	$array["ads"]="publicite";
	$array["adult"]="porn";
	$array["aggressive"]="aggressive";
	$array["astrology"]="astrology";
	
	$array["bank"]="finance/banking";
	$array["blog"]="blog";
	$array["celebrity"]="celebrity";
	$array["chat"]="chat";
	$array["cleaning"]="cleaning";
	$array["dangerous_material"]="dangerous_material";
	$array["dating"]="dating";
	$array["drugs"]="porn";
	$array["filehosting"]="filehosting";
	$array["financial"]="financial";
	$array["forums"]="forums";
	$array["gambling"]="gamble";
	$array["games"]="games";
	$array["hacking"]="hacking";
	$array["jobsearch"]="jobsearch";
	$array["liste_bu"]="liste_bu";
	$array["malware"]="malware";
	$array["marketingware"]="marketingware";
	$array["mixed_adult"]="mixed_adult";
	$array["mobile-phone"]="mobile-phone";
	$array["phishing"]="phishing";
	
	$array["radio"]="webradio";
	$array["reaffected"]="reaffected";
	$array["redirector"]="redirector";
	$array["remote-control"]="remote-control";
	$array["sect"]="sect";
	$array["sexual_education"]="sexual_education";
	$array["shopping"]="shopping";
	$array["social_networks"]="socialnet";
	$array["sports"]="recreation/sports";
	$array["getmarried"]="getmarried";
	$array["police"]="police";	

	$array["tricheur"]="tricheur";
	$array["violence"]="violence";
	$array["warez"]="warez";
	$array["webmail"]="webmail";	
	$array["adv"]="publicite";
	$array["aggressive"]="aggressive";
	$array["automobile"]="automobile/cars";
	$array["chat"]="chat";
	$array["dating"]="dating";
	$array["downloads"]="downloads";
	$array["drugs"]="drugs";
	$array["education"]="recreation/schools";
	$array["finance"]="financial";
	$array["forum"]="forums";
	$array["gamble"]="gamble";
	$array["government"]="governments";
	$array["hacking"]="hacking";
	$array["hospitals"]="hospitals";
	$array["imagehosting"]="imagehosting";
	$array["isp"]="isp";
	$array["jobsearch"]="jobsearch";
	$array["library"]="books";
	$array["models"]="models";
	$array["movies"]="movies";
	$array["music"]="music";
	$array["news"]="news";
	$array["porn"]="porn";
	$array["redirector"]="redirector";
	$array["religion"]="religion";
	$array["remotecontrol"]="remote-control";
	
	$array["searchengines"]="searchengines";
	$array["shopping"]="shopping";
	$array["socialnet"]="socialnet";
	$array["spyware"]="spyware";
	$array["tracker"]="tracker";
	$array["updatesites"]="updatesites";
	$array["violence"]="violence";
	$array["warez"]="warez";
	$array["weapons"]="weapons";
	$array["webmail"]="webmail";
	$array["webphone"]="webphone";
	$array["webradio"]="webradio";
	$array["webtv"]="webtv";		
	if(!isset($array[$category])){return null;}
	return $array[$category];
	
	
}



function UFDBGUARD_COMPILE_DB(){
	$tstart=time();
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/UFDBGUARD_COMPILE_DB.pid";
	if($unix->process_exists(@file_get_contents($pidfile))){
		echo "Process already exists PID: ".@file_get_contents($pidfile)."\n";
		return;
	}
	
	
	@file_put_contents($pidfile,getmypid());
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	$datas=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
	if(strlen($ufdbGenTable)<5){echo "ufdbGenTable no such file\n";return ;}
	
	$md5db=unserialize(@file_get_contents("/etc/artica-postfix/ufdbGenTableMD5"));
	
	
	$count=0;
	foreach ($datas as $a=>$b){
		if(preg_match('#domainlist\s+"(.+)\/domains#',$b,$re)){
			$f["/var/lib/squidguard/{$re[1]}"]="/var/lib/squidguard/{$re[1]}";
		}
	}
	
	
	
	if(!is_array($datas)){echo "No databases set\n";return ;}
	foreach ($f as $directory=>$b){
		$mustrun=false;
		if(preg_match("#.+?\/([a-zA-Z0-9\-\_]+)$#",$directory,$re)){
			$category=$re[1];
			$category=substr($category,0,15);
			if($GLOBALS["VERBOSE"]){echo "Checking $category\n";}
		}
		
		// ufdbGenTable -n -D -W -t adult -d /var/lib/squidguard/adult/domains -u /var/lib/squidguard/adult/urls     
		if(is_file("$directory/domains")){
			$md5=FileMD5("$directory/domains");
			if($md5<>$md5db["$directory/domains"]){
				$mustrun=true;
				$md5db["$directory/domains"]=$md5;
				$dbb[]="$directory/domains";
			}else{
				if($GLOBALS["VERBOSE"]){echo "$md5 is the same, skip $directory/domains\n";}
			}
			
			
			$d=" -d $directory/domains";
		}else{
			if($GLOBALS["VERBOSE"]){echo "$directory/domains no such file\n";}
		}
		if(is_file("$directory/urls")){
			$md5=FileMD5("$directory/urls");
			if($md5<>$md5db["$directory/urls"]){$mustrun=true;$md5db["$directory/urls"]=$md5;$dbb[]="$directory/urls";}
			$u=" -u $directory/urls";
		}
		
		if(!is_file("$directory/domains.ufdb")){$mustrun=true;$dbb[]="$directory/*";}
		
		if($mustrun){
				$dbcount=$dbcount+1;
				$category_compile=$category;
				if(strlen($category_compile)>15){
				$category_compile=str_replace("recreation_","recre_",$category_compile);
				$category_compile=str_replace("automobile_","auto_",$category_compile);
				$category_compile=str_replace("finance_","fin_",$category_compile);
				if(strlen($category_compile)>15){
					$category_compile=str_replace("_", "", $category_compile);
					if(strlen($category_compile)>15){
						$category_compile=substr($category_compile, strlen($category_compile)-15,15);
					}
				}
			}			
				
				
			$cmd="$ufdbGenTable -n -D -W -t $category_compile$d$u";
			echo $cmd."\n";
			$t=time();
			shell_exec($cmd);
			$took=$unix->distanceOfTimeInWords($t,time(),true);
			squid_admin_mysql(1, "Compiled $category_compile in $directory {took} $took",@implode("\n",$dbb)."\n",__FILE__,__LINE__, "ufdb-compile");
			if(function_exists("system_is_overloaded")){
				if(system_is_overloaded(__FILE__)){
					squid_admin_mysql(1, "{OVERLOADED_SYSTEM} after $dbcount compilations, oberting task...",@implode("\n",$dbb)."\n",__FILE__,__LINE__, "ufdb-compile");
					return;
				}
			}
		}
		$u=null;$d=null;$md5=null;
	}
	
	@file_put_contents("/etc/artica-postfix/ufdbGenTableMD5",serialize($md5db));
	$user=GetSquidUser();
	$chown=$unix->find_program($chown);
	if(is_file($chown)){
		shell_exec("$chown -R $user /var/lib/squidguard/*");
		shell_exec("$chown -R $user /var/log/squid/*");
	}	
	if($dbcount>0){
		$took=$unix->distanceOfTimeInWords($tstart,time(),true);
		squid_admin_mysql(1, "Maintenance on Web Proxy urls Databases: $dbcount database(s) {took} $took",@implode("\n",$dbb)."\n",__FILE__,__LINE__, "ufdb-compile");
	}
	
	
	
}

function BuildMissingUfdBguardDBS($all=false,$output=false){
	$sock=new sockets();
	$Narray=array();
	$array=explode("\n",@file_get_contents("/etc/ufdbguard/ufdbGuard.conf"));
	foreach ($array as $index=>$line){
		if(preg_match("#domainlist.+?(.+)\/domains#",$line,$re)){
			$datas_path="/var/lib/squidguard/{$re[1]}/domains";
			$path="/var/lib/squidguard/{$re[1]}/domains.ufdb";
			
			if(!$all){
				if(!is_file($path)){
					if($output){echo "Missing $path\n";} 
					$Narray[$path]=@filesize($datas_path);
				}
			}
			if($all){$Narray[$path]=@filesize($datas_path);}
			
		}
		
	}
	
	echo "Starting......: ".date("H:i:s")." Webfiltering service ". count($Narray)." database(s) must be compiled\n";
	if(!$all){
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/ufdbguard.db.status.txt",serialize($Narray));
		chmod("/usr/share/artica-postfix/ressources/logs/ufdbguard.db.status.txt",777);
	}
	return $Narray;
}

function UFDBGUARD_STATUS(){
	$Narray=array();
	$unix=new unix();
	$array=explode("\n",@file_get_contents("/etc/ufdbguard/ufdbGuard.conf"));
	foreach ($array as $index=>$line){
		if(preg_match("#domainlist.+?(.+)\/domains#",$line,$re)){
			$datas_path="/var/lib/squidguard/{$re[1]}/domains";
			$path="/var/lib/squidguard/{$re[1]}/domains.ufdb";
			$size=$unix->file_size($path);
			$Narray[$path]=$size;
			
		}
		
	}
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/ufdbguard.db.size.txt",serialize($Narray));
	chmod("/usr/share/artica-postfix/ressources/logs/ufdbguard.db.size.txt",777);
	
	return $Narray;
}


function DisksStatus($aspid=false){}


function databases_status(){
	if($GLOBALS["VERBOSE"]){echo "databases_status() line:".__LINE__."\n";}
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	@mkdir("/var/lib/squidguard",0755,true);
	$q=new mysql_squid_builder();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'category_%'";
	$results=$q->QUERY_SQL($sql);
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$table=$ligne["c"];
		if(!preg_match("#^category_(.+)#", $table,$re)){continue;}
		$categoryname=$re[1];
		if($GLOBALS["VERBOSE"]){echo "Checks $categoryname\n";}
		if(is_file("/var/lib/squidguard/$categoryname/domains.ufdb")){
			if($GLOBALS["VERBOSE"]){echo "Checks $categoryname/domains.ufdb\n";}
			$size=@filesize("/var/lib/squidguard/$categoryname/domains.ufdb");
			if($GLOBALS["VERBOSE"]){echo "Checks $categoryname/domains\n";}
			$textsize=@filesize("/var/lib/squidguard/$categoryname/domains");
			
		}
		if(!is_numeric($textsize)){$textsize=0;}
		if(!is_numeric($size)){$size=0;}
		$array[$table]=array("DBSIZE"=>$size,"TXTSIZE"=>$textsize);
	}

	if($GLOBALS["VERBOSE"]){print_r($array);}
	@file_put_contents(PROGRESS_DIR."/ufdbguard_db_status", serialize($array));
	shell_exec("$chmod 777 /usr/share/artica-postfix/ressources/logs/web/ufdbguard_db_status");
	
}

function ufdbguard_recompile_missing_dbs(){
	$unix=new unix();
	$MYSQL_DATA_DIR=$unix->MYSQL_DATA_DIR();
	$touch=$unix->find_program("touch");
	@mkdir("/var/lib/squidguard",0755,true);
	$q=new mysql_squid_builder();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'category_%'";
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$table=$ligne["c"];
		if(!preg_match("#^category_(.+)#", $table,$re)){continue;}
		$categoryname=$re[1];
		echo "Starting......: ".date("H:i:s")." Webfiltering service $table -> $categoryname\n";
		if(!is_file("/var/lib/squidguard/$categoryname/domains")){
			@mkdir("/var/lib/squidguard/$categoryname",0755,true);
			$sql="SELECT LOWER(pattern) FROM {$ligne["c"]} WHERE enabled=1 AND pattern REGEXP '[a-zA-Z0-9\_\-]+\.[a-zA-Z0-9\_\-]+' ORDER BY pattern INTO OUTFILE '$table.temp' FIELDS OPTIONALLY ENCLOSED BY 'n'";
			$q->QUERY_SQL($sql);
			if(!is_file("$MYSQL_DATA_DIR/squidlogs/$table.temp")){
				echo "Starting......: ".date("H:i:s")." Webfiltering service $MYSQL_DATA_DIR/squidlogs/$table.temp no such file\n";
				continue;
			}
			echo "Starting......: ".date("H:i:s")." Webfiltering service $MYSQL_DATA_DIR/squidlogs/$table.temp done...\n";
			@copy("$MYSQL_DATA_DIR/squidlogs/$table.temp", "/var/lib/squidguard/$categoryname/domains");	
			@unlink("$MYSQL_DATA_DIR/squidlogs/$table.temp");
			echo "Starting......: ".date("H:i:s")." Webfiltering service UFDBGUARD_COMPILE_SINGLE_DB(/var/lib/squidguard/$categoryname/domains)\n";
			UFDBGUARD_COMPILE_SINGLE_DB("/var/lib/squidguard/$categoryname/domains");					
		}else{
			echo "Starting......: ".date("H:i:s")." Webfiltering service /var/lib/squidguard/$categoryname/domains OK\n";
			
		}
		
		if(!is_file("/var/lib/squidguard/$categoryname/expressions")){shell_exec("$touch /var/lib/squidguard/$categoryname/expressions");}
		
	}
    build_ufdbguard_config();
	if(is_file("/etc/init.d/ufdb")){
		echo "Starting......: ".date("H:i:s")." Webfiltering service reloading service\n";
		squid_admin_mysql(1,"Web-Filtering Service will be reloaded",null,__FILE__,__LINE__);
		build_ufdbguard_HUP();
	}
	
}



function UFDBGUARD_COMPILE_CATEGORY_PROGRESS($text,$pourc){
	
	$cachefile=PROGRESS_DIR."/ufdbguard.compile.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	if($GLOBALS["OUTPUT"]){echo "{$pourc}% $text\n";sleep(2);}
}

function UFDBGUARD_COMPILE_CATEGORY($category){
	$sock=new sockets();
	$UseRemoteUfdbguardService=$sock->GET_INFO("UseRemoteUfdbguardService");
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}	

	if($UseRemoteUfdbguardService==1){
		UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{failed} Use remote service",110);
		return;
	}	
	$unix=new unix();
	if($GLOBALS["VERBOSE"]){
		$ufdbguardd=$unix->find_program("ufdbguardd");
		system("$ufdbguardd -v");
	}
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{failed} $category category aborting,task pid $pid running since {$time}Mn",110);
		squid_admin_mysql(1, "Compile $category category aborting,task pid $pid {running} {since} {$time}Mn",__FILE__,__LINE__);
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$t=time();
	
	echo "Starting......: ".date("H:i:s")." Compiling category $category\n";
	UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{compiling} Compiling category $category",2);
	$ufdb=new compile_ufdbguard();
	$ufdb->compile_category($category);
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	
	if($EnableWebProxyStatsAppliance==1){
		echo "Starting......: ".date("H:i:s")." This server is a Squid Appliance, compress databases and notify proxies\n";
		CompressCategories();	
		notify_remote_proxys();
	}	
}

function UFDBGUARD_COMPILE_ALL_CATEGORIES(){}
function CompressCategories(){
	$sock=new sockets();
	$unix=new unix();
	$tar=$unix->find_program("tar");
	$chmod=$unix->find_program("chmod");
	$chown=$unix->find_program("chown");
	$lighttpdUser=$unix->LIGHTTPD_USER();
	$StorageDir="/usr/share/artica-postfix/ressources/databases";
	
	if(!is_dir("/var/lib/squidguard")){squid_admin_mysql(1, "/var/lib/squidguard no such directory",__FILE__,__LINE__,"global-compile");return;}
	$t=time();
	if(is_dir("/var/lib/squidguard")){
		chdir("/var/lib/squidguard");
		if(is_file("$StorageDir/blacklist.tar.gz")){@unlink("$StorageDir/blacklist.tar.gz");}
		writelogs("Compressing /var/lib/squidguard",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$tar -czf $StorageDir/blacklist.tar.gz *");
		shell_exec("$chmod 770 $StorageDir/blacklist.tar.gz");
	}
	
	if(is_dir("/var/lib/ftpunivtlse1fr")){
		chdir("/var/lib/ftpunivtlse1fr");
		writelogs("Compressing /var/lib/ftpunivtlse1fr",__FUNCTION__,__FILE__,__LINE__);
		if(is_file("$StorageDir/ftpunivtlse1fr.tar.gz")){@unlink("$StorageDir/ftpunivtlse1fr.tar.gz");}
		shell_exec("$tar -czf $StorageDir/ftpunivtlse1fr.tar.gz *");
		shell_exec("$chmod 770 $StorageDir/ftpunivtlse1fr.tar.gz");
	}
	
	if(is_dir("/etc/dansguardian")){
		chdir("/etc/dansguardian");
		writelogs("Compressing /etc/dansguardian",__FUNCTION__,__FILE__,__LINE__);
		if(is_file("$StorageDir/dansguardian.tar.gz")){@unlink("$StorageDir/dansguardian.tar.gz");}
		exec("$tar -czf $StorageDir/dansguardian.tar.gz * 2>&1",$lines);
		foreach ($lines as $line){writelogs($line,__FUNCTION__,__FILE__,__LINE__);}
		if(!is_file("$StorageDir/dansguardian.tar.gz")){writelogs(".$StorageDir/dansguardian.tar.gz no such file",__FUNCTION__,__FILE__,__LINE__);}
		shell_exec("$chmod 770 /usr/share/artica-postfix/ressources/databases/dansguardian.tar.gz");
	}
	
	writelogs("Compressing done, apply permissions for `$lighttpdUser` user",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$chown $lighttpdUser:$lighttpdUser $StorageDir");
	shell_exec("$chown $lighttpdUser:$lighttpdUser $StorageDir/*");
	
	$ttook=$unix->distanceOfTimeInWords($t,time(),true);
	squid_admin_mysql(1, "compress all categories done ($ttook)",__FILE__,__LINE__);
	
	
	
}

function cron_compile(){
	$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
	$isFiltersInstalled=false;
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$users=new usersMenus();
	if($users->APP_UFDBGUARD_INSTALLED){$isFiltersInstalled=true;}
	if($users->DANSGUARDIAN_INSTALLED){$isFiltersInstalled=true;}
	if($EnableWebProxyStatsAppliance==0){if(!$isFiltersInstalled){return;}}

	
			
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$restart=false;
	if($unix->process_exists(@file_get_contents($pidfile))){return;}
	@file_put_contents($pidfile, getmypid());
	
	
	if(is_file("/etc/artica-postfix/ufdbguard.compile.alldbs")){
		$WHY="ufdbguard.compile.alldbs exists";
		@unlink("/etc/artica-postfix/ufdbguard.compile.alldbs");
		UFDBGUARD_COMPILE_ALL_CATEGORIES();
		return;
	}
	
	if(is_file("/etc/artica-postfix/ufdbguard.compile.missing.alldbs")){
		$WHY="ufdbguard.compile.missing.alldbs exists";
		events_ufdb_exec("CRON:: -> ufdbguard_recompile_missing_dbs()");
		@unlink("/etc/artica-postfix/ufdbguard.compile.missing.alldbs");
		squid_admin_mysql(1, "-> ufdbguard_recompile_missing_dbs()",__FILE__,__LINE__,"config");
		ufdbguard_recompile_missing_dbs();
		return;
	}
	
	if(is_file("/etc/artica-postfix/ufdbguard.reconfigure.task")){
		$WHY="ufdbguard.reconfigure.task exists";
		events_ufdb_exec("CRON:: -> build()");
		@unlink("/etc/artica-postfix/ufdbguard.reconfigure.task");
		squid_admin_mysql(1, "-> build()",__FILE__,__LINE__,"config");
		build();
		return;
	}
	

	foreach (glob("/etc/artica-postfix/ufdbguard.recompile-queue/*") as $filename) {
		$restart=true;
		$db=@file_get_contents($filename);
		@unlink($filename);
		squid_admin_mysql(1, "-> UFDBGUARD_COMPILE_SINGLE_DB(/var/lib/squidguard/$db/domains)",__FILE__,__LINE__,"config");
		UFDBGUARD_COMPILE_SINGLE_DB("/var/lib/squidguard/$db/domains");
		
		
	}
	
	if($restart){
		squid_admin_mysql(1,"Web-Filtering Service will be reloaded",null,__FILE__,__LINE__);
		build_ufdbguard_HUP();
	}
	
	
}

function UFDBGUARD_DOWNLOAD_ALL_CATEGORIES(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$unix=new unix();
	$sock=new sockets();
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$uri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/ressources/databases/blacklist.tar.gz";
	$curl=new ccurl($uri,true);
	if(!$curl->GetFile("/tmp/blacklist.tar.gz")){squid_admin_mysql(1, "Failed to download blacklist.tar.gz aborting `$curl->error`",__FILE__,__LINE__,"global-compile");return;}
	$t=time();
	shell_exec("$rm -rf /var/lib/squidguard/*");
	exec("$tar -xhf /tmp/blacklist.tar.gz -C /var/lib/squidguard/ 2>&1",$results);
	$ttook=$unix->distanceOfTimeInWords($t,time(),true);
	squid_admin_mysql(1, "Extracting blacklist.tar.gz {took} $ttook `".@implode("\n",$results),__FUNCTION__,__FILE__,__LINE__,"global-compile");
	
	$array=$unix->dirdir("/var/lib/squidguard");
	$GLOBALS["NORESTART"]=true;
	foreach ($array as $directoryPath){
		if(!is_file("$directoryPath/domains.ufdb")){UFDBGUARD_COMPILE_SINGLE_DB("$directoryPath/domains");}
	}
	squid_admin_mysql(1,"Web-Filtering Service will be reloaded",null,__FILE__,__LINE__);
	build_ufdbguard_HUP();
	

}

function Dansguardian_remote(){
	$users=new usersMenus();
	$sock=new sockets();
	$unix=new unix();	
	$tar=$unix->find_program("tar");
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$baseUri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/ressources/databases";	
	$uri="$baseUri/dansguardian.tar.gz";
	$curl=new ccurl($uri,true);
	if($curl->GetFile("/tmp/dansguardian.tar.gz")){
		$cmd="$tar -xhf /tmp/dansguardian.tar.gz -C /etc/dansguardian/";
		writelogs($cmd,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
		
		if($users->DANSGUARDIAN_INSTALLED){
			echo "Starting......: ".date("H:i:s")." Dansguardian reloading service\n";
			shell_exec("/usr/share/artica-postfix/bin/artica-install --reload-dansguardian --withoutconfig");
		}		
		
	}else{
		squid_admin_mysql(1, "Failed to download dansguardian.tar.gz aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");			
	}		
}


function ufdbguard_remote(){
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$users=new usersMenus();
	$sock=new sockets();
	$unix=new unix();
	$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}	
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($unix->file_time_min($timeFile)<5){
		writelogs("too short time to change settings, aborting $called...",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	@mkdir("/etc/ufdbguard",0755,true);
	$tar=$unix->find_program("tar");
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$DenyUfdbWriteConf=$sock->GET_INFO("DenyUfdbWriteConf");
	if(!is_numeric($DenyUfdbWriteConf)){$DenyUfdbWriteConf=0;}
	$baseUri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/ressources/databases";
	
	if($DenyUfdbWriteConf==0){
		$uri="$baseUri/ufdbGuard.conf";
		$curl=new ccurl($uri,true);
		if($curl->GetFile("/tmp/ufdbGuard.conf")){
			@file_put_contents("/etc/ufdbguard/ufdbGuard.conf", @file_get_contents("/tmp/ufdbGuard.conf"));
			@file_put_contents("/etc/squid3/ufdbGuard.conf", @file_get_contents("/tmp/ufdbGuard.conf"));
		}else{
			squid_admin_mysql(1, "Failed to download ufdbGuard.conf aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");			
		}
	}

	$uri="$baseUri/blacklist.tar.gz";
	$curl=new ccurl($uri,true);
	if($curl->GetFile("/tmp/blacklist.tar.gz")){
		$cmd="$tar -xhf /tmp/blacklist.tar.gz -C /var/lib/squidguard/";
		writelogs($cmd,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
	}else{
		squid_admin_mysql(1, "Failed to download blacklist.tar.gz aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");			
	}	
	
	$uri="$baseUri/ftpunivtlse1fr.tar.gz";
	$curl=new ccurl($uri,true);
	if($curl->GetFile("/tmp/ftpunivtlse1fr.tar.gz")){
		$cmd="$tar -xhf /tmp/ftpunivtlse1fr.tar.gz -C /var/lib/ftpunivtlse1fr/";
		writelogs($cmd,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
	}else{
		squid_admin_mysql(1, "Failed to download ftpunivtlse1fr.tar.gz aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");			
	}

	Dansguardian_remote();	
	
	CheckPermissions();	
	ufdbguard_schedule();
	
	if($unix->Ufdbguard_remote_srvc_bool()){squid_admin_mysql(1, "Using a remote UfdbGuard service, aborting",__FUNCTION__,__FILE__,__LINE__,"config");return;}
	
	
	squid_admin_mysql(1,"Web-Filtering Service will be reloaded",null,__FILE__,__LINE__);
	build_ufdbguard_HUP();
	

	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	if(is_file($GLOBALS["SQUIDBIN"])){
		echo "Starting......: ".date("H:i:s")." Squid reloading service\n";
		system("/usr/sbin/artica-phpfpm-service -proxy-plugins");
	}	
	

	shell_exec(LOCATE_PHP5_BIN2()."/usr/share/artica-postfix/exec.c-icap.php --maint-schedule");
	
	
}





function events_ufdb_exec($text){
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-postfix/ufdbguard-compilator.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$textnew="$date [$pid]:: ".basename(__FILE__)." $text\n";
		
		@fwrite($f,$text );
		@fclose($f);	
		}


function events_ufdb_tail($text,$line=0){
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-postfix/ufdbguard-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		if($line>0){$line=" line:$line";}else{$line=null;}
		$textnew="$date [$pid]:: ".basename(__FILE__)." $text$line\n";
		if($GLOBALS["VERBOSE"]){echo $textnew;}
		@fwrite($f,$textnew );
		@fclose($f);	
		events_ufdb_exec($textnew);
		}

function CompileCategoryWords(){
	$unix=new unix();
	$uuid="8cdd119c-2dc1-452d-b9d0-451c6046464f";
	$f=$unix->DirRecursiveFiles("/etc/dansguardian/lists/phraselists");
	$q=new mysql_squid_builder();
	foreach ($f as $index=>$filename){
		$basename=basename($filename);
		
		
		if(!preg_match("#weighted#",$basename)){continue;}
		$categoryname=basename(dirname($filename));
		$language="english";
		if($categoryname=="pornography"){$categoryname="porn";}
		if($categoryname=="gambling"){$categoryname="gamble";}
		if($categoryname=="nudism"){$categoryname="mixed_adult";}
		if($categoryname=="illegaldrugs"){$categoryname="drugs";}
		if($categoryname=="translation"){$categoryname="translators";}
		if($categoryname=="warezhacking"){$categoryname="warez";}
		
		
		if(preg_match("#weighted_(.+)#", $basename,$re)){$language=$re[1];}
		$language=str_replace("general_", "",$language);
		echo "$basename -> $categoryname ($language)\n";
		
		$q->CreateCategoryWeightedTable();
		
		$lines=explode("\n",@file_get_contents($filename));
		
		
		$prefix="INSERT IGNORE INTO phraselists_weigthed (zmd5,zDate,category,pattern,score,uuid,language) VALUES ";
		
		foreach ($lines as $linum=>$line){
			if(substr($line,0,1)=="#"){continue;}
			if(preg_match("#.+?<([0-9]+)>$#",$line,$re)){
				$line=str_replace("<{$re[1]}>","",$line);
				echo "$categoryname: $line -> score:{$re[1]}\n";
				$score=$re[1];
				$zmd5=md5($line.$score);
				$zDate=date('Y-m-d H:i:s');
				$line=addslashes($line);
				$sqls[]="('$zmd5','$zDate','$categoryname','$line','$score','$uuid','$language')";
				$sqlb[]="('$zmd5','$zDate','$categoryname','$line','$score','$uuid','$language')";
			}
		}
		
		$q->QUERY_SQL($prefix.@implode(",",$sqls));
		if(!$q->ok){echo $q->mysql_error."\n";}
		$sqls=array();
		
	}
	
	@file_put_contents("/root/weightedPhrases.db", serialize($sqlb));

	
}	

function notify_remote_proxys(){

}



function ufdbdatabases_in_mem(){
	$sock=new sockets();
	$unix=new unix();
	$UfdbDatabasesInMemory=$sock->GET_INFO("UfdbDatabasesInMemory");
	if(!is_numeric($UfdbDatabasesInMemory)){$UfdbDatabasesInMemory=0;}
	if($UfdbDatabasesInMemory==0){
		echo "Starting URLfilterDB Database in memory feature is disabled\n";
		$MOUNTED_DIR_MEM=$unix->MOUNTED_TMPFS_MEM("/var/lib/ufdbguard-memory");
		if($MOUNTED_DIR_MEM>0){
			echo "Starting URLfilterDB Database unmounting...\n";
			$umount=$unix->find_program("umount");
			shell_exec("$umount -l /var/lib/ufdbguard-memory");
		}
		return;
	}
	
	
	$POSSIBLEDIRS[]="/var/lib/ufdbartica";
	$POSSIBLEDIRS[]="/var/lib/squidguard";
	$POSSIBLEDIRS[]="/var/lib/ftpunivtlse1fr";
	
	$ufdbartica_size=$unix->DIRSIZE_BYTES("/var/lib/ufdbartica");
	$ufdbartica_size=round(($ufdbartica_size/1024)/1000)+5;
	
	$squidguard_size=$unix->DIRSIZE_BYTES("/var/lib/squidguard");
	$squidguard_size=round(($squidguard_size/1024)/1000)+5;
	$ftpunivtlse1fr_size=$unix->DIRSIZE_BYTES("/var/lib/ftpunivtlse1fr");
	$ftpunivtlse1fr_size=round(($ftpunivtlse1fr_size/1024)/1000)+5;
	echo "Starting URLfilterDB ufdbartica DB....: about {$ufdbartica_size}MB\n";
	echo "Starting URLfilterDB squidguard DB....: about {$squidguard_size}MB\n";
	echo "Starting URLfilterDB ftpunivtlse1fr DB: about {$ftpunivtlse1fr_size}MB\n";
	$total=$ufdbartica_size+$squidguard_size+$ftpunivtlse1fr_size+10;
	echo "Starting URLfilterDB require {$total}MB\n";
	$mount=$unix->find_program("mount");
	
	$MOUNTED_DIR_MEM=$unix->MOUNTED_TMPFS_MEM("/var/lib/ufdbguard-memory");
	if($MOUNTED_DIR_MEM==0){
		$system_mem=$unix->TOTAL_MEMORY_MB();
		echo "Starting URLfilterDB system memory {$system_mem}MB\n";
		if($system_mem<$total){
			$require=$total-$system_mem;
			echo "Starting URLfilterDB not engough memory require at least {$require}MB\n";
			return;
		}
		$system_free=$unix->TOTAL_MEMORY_MB_FREE();
		echo "Starting URLfilterDB system memory available {$system_free}MB\n";
		if($system_free<$total){
			$require=$total-$system_free;
			echo "Starting URLfilterDB not engough memory require at least {$require}MB\n";
			return;
		}
	}
	
	$idbin=$unix->find_program("id");
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	$chown=$unix->find_program("chown");
	if($MOUNTED_DIR_MEM>0){
		if($MOUNTED_DIR_MEM<$total){
			echo "Starting URLfilterDB: umounting from memory\n";
			shell_exec("$umount -l /var/lib/ufdbguard-memory");
			$MOUNTED_DIR_MEM=$unix->MOUNTED_TMPFS_MEM("/var/lib/ufdbguard-memory");
		}
	}

	if($MOUNTED_DIR_MEM==0){
		if(strlen($idbin)<3){echo "Starting URLfilterDB: tmpfs `id` no such binary\n";return;}
		if(strlen($mount)<3){echo "Starting URLfilterDB: tmpfs `mount` no such binary\n";return;}
		exec("$idbin squid 2>&1",$results);
		if(!preg_match("#uid=([0-9]+).*?gid=([0-9]+)#", @implode("", $results),$re)){echo "Starting......: ".date("H:i:s")."MySQL mysql no such user...\n";return;}
		$uid=$re[1];
		$gid=$re[2];
		echo "Starting URLfilterDB: tmpfs uid/gid =$uid:$gid for {$total}M\n";
		@mkdir("/var/lib/ufdbguard-memory");
		$cmd="$mount -t tmpfs -o rw,uid=$uid,gid=$gid,size={$total}M,nr_inodes=10k,mode=0700 tmpfs \"/var/lib/ufdbguard-memory\"";
		shell_exec($cmd);	
		$MOUNTED_DIR_MEM=$unix->MOUNTED_TMPFS_MEM("/var/lib/ufdbguard-memory");
		if($MOUNTED_DIR_MEM==0){
			echo "Starting URLfilterDB: tmpfs failed...\n";
			return;
		}
	}
	
	echo "Starting URLfilterDB: mounted as {$MOUNTED_DIR_MEM}MB\n";
	reset($POSSIBLEDIRS);
    foreach ($POSSIBLEDIRS as $index=>$directory){
		$directoryname=basename($directory);
		@mkdir("/var/lib/ufdbguard-memory/$directoryname",0755,true);
		if(!is_dir("/var/lib/ufdbguard-memory/$directoryname")){
			echo "Starting URLfilterDB: $directoryname permission denied\n";
			return;
		}
		@chown("/var/lib/ufdbguard-memory/$directoryname","squid");
		echo "Starting URLfilterDB: replicating $directoryname\n";
		shell_exec("$cp -rfu $directory/* /var/lib/ufdbguard-memory/$directoryname/");
	}
	
	$ufdbguardConfs[]="/etc/ufdbguard/ufdbGuard.conf";
	$ufdbguardConfs[]="/etc/squid3/ufdbGuard.conf";
	
	echo "Starting URLfilterDB: setup privileges\n";
	shell_exec("$chown -R squid:squid /var/lib/ufdbguard-memory >/dev/null 2>&1");
	
	echo "Starting URLfilterDB: modify configuration files\n";
    foreach ($ufdbguardConfs as $configfile){
		$f=explode("\n",@file_get_contents($configfile));
		foreach ($f as $indexLine=>$line){
			reset($POSSIBLEDIRS);
            foreach ($POSSIBLEDIRS as $index=>$directory){
				$directoryname=basename($directory);
				$line=str_replace($directory, "/var/lib/ufdbguard-memory/$directoryname", $line);
				$f[$indexLine]=$line;
			}
		}
	
		@file_put_contents($configfile, @implode("\n", $f));
		echo "Starting URLfilterDB: $configfile success...\n";
	}
	
}



function stop_ufdbguard($aspid=false){
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
	
	$pid=ufdbguard_pid();
	
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=ufdbguard_pid();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	squid_admin_mysql(0, "Stopping Web Filtering engine service","",__FILE__,__LINE__);
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=ufdbguard_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	
	$pid=ufdbguard_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=ufdbguard_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function ufdguard_artica_db_status(){
	$unix=new unix();
	$mainpath="/var/lib/ufdbartica";
	
	
	$mainpath_size=$unix->DIRSIZE_BYTES($mainpath);
	
	$array["SIZE"]=$mainpath_size;
	if(is_file("$mainpath/category_porn/domains.ufdb")){
		$date=filemtime("$mainpath/category_porn/domains.ufdb");
		$array["DATE"]=$date;
	}else{
		$array["DATE"]=0;
	}
	@file_put_contents("/etc/artica-postfix/ARTICA_WEBFILTER_DB_STATUS", serialize($array));
	
}

























function ini_set_verbosedx(){
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string','');
	ini_set('error_append_string','');
	$GLOBALS["VERBOSE"]=true;
}
?>