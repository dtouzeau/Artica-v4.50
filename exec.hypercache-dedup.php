<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["NOPROGRESS"]=false;
$GLOBALS["PROGRESS"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){
		$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.hypercache.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.checks.inc");
include_once(dirname(__FILE__)."/ressources/class.storeid.defaults.inc");

if(isset($argv[1])){
    if($argv[1]=="--wizard"){$GLOBALS["NOPROGRESS"]=true;}
    if($argv[1]=="--urgency"){disable_urgency();exit;}
    if($argv[1]=="--free"){echo ifHyperCacheFreeInsquid()."\n";exit();}
    if($argv[1]=="--rules"){echo rules();exit();}
    if($argv[1]=="--remove"){remove();exit();}
    if($argv[1]=="--install"){install();exit();}
    if($argv[1]=="--start"){start();exit();}
    if($argv[1]=="--restart"){rules();exit();}
    if($argv[1]=="--stop"){stop();exit();}
    if($argv[1]=="--update"){update();exit();}
    if($argv[1]=="--switch"){xswitch();exit();}
}

build_sequence();

function build_progress_urgency($pourc,$text):bool{
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"squid.urgency.hypercache.progress");
	return true;
}

function xswitch(){
	$SquidDisableHyperCacheDedup=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableHyperCacheDedup"));
	
	if($SquidDisableHyperCacheDedup==1){
		stop();
		return;
	}else{
		start(false);
	}
}

function disable_urgency(){
	build_progress_urgency(10,"{disable_emergency}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("StoreIDUrgency", 0);
	@file_put_contents("/etc/artica-postfix/settings/Daemons/StoreIDUrgency", 0);
	@chmod("/etc/artica-postfix/settings/Daemons/StoreIDUrgency", 0777);
	build_progress_urgency(50,"{reconfigure_proxy_service}");
	if(!build_sequence_plugin()){
		build_progress_urgency(110,"{failed}");
		return;
	}
	build_progress_urgency(100,"{success}");
	
}


function ifHyperCacheInsquid(){
	
	$f=explode("/n",@file_get_contents("/etc/squid3/squid.conf"));
	
	foreach ($f as $line){
		$line=trim($line);
		if(preg_match("#include.*?StoreID\.conf#", $line)){
			return true;
		}
		
	}
	echo "* * * * Include StoreID.conf not in squid * * * *\n";
	return false;
	
}
function ifHyperCacheFreeInsquid(){
	$f=explode("/n",@file_get_contents("/etc/squid3/StoreID.conf"));

	foreach ($f as $index=>$line){
		$line=trim($line);
		if(preg_match("#store_id_program.*?storeid_file_rewrite#", $line)){
			return true;
		}
		echo "$line\n";
	}
	echo "/etc/squid3/StoreID.conf nothing added...\n";
	return false;

}

function build_sequence_plugin():bool{
    $md51=null;
	$fname="/etc/squid3/StoreID.conf";
    if(is_file($fname)) {
        $md51 = md5_file($fname);
    }

	$StoreIDUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StoreIDUrgency"));
	$SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));

	if($SquidUrgency==1){$StoreIDUrgency=1;}
	if($StoreIDUrgency==1){
		build_progress(110,"{emergency}");
		@file_put_contents("/etc/squid3/StoreID.conf","# Emergency StoreIDUrgency = $StoreIDUrgency , SquidUrgency=$SquidUrgency\n");
		@chown("/etc/squid3/StoreID.conf","squid");
        $md52= md5_file($fname);
        if($md52 == $md51){ return false;}
        system("/usr/sbin/artica-phpfpm-service -reload-proxy");
        return false;
	}

	build_progress(60,"{reconfigure_proxy_service}");
    $HyperCacheSquid=new HyperCacheSquid();
	$HyperCacheSquid->build();
	
	build_progress(70,"{testing_configuration}");
	$squid_checks=new squid_checks();
	if(!$squid_checks->squid_parse()){
		build_progress("{testing_configuration} {failed}",110);
		return false;
	}
    _out("$fname(1) $md51");

    $md52= md5_file($fname);
    _out("$fname(2) $md52");
    if($md52 == $md51){
        build_progress(100,"{reconfigure_proxy_service} no change");
        return true;
    }

    _out("Reloading proxy service..");
	squid_admin_mysql(2, "{reloading_proxy_service} - StoreID -(".__FUNCTION__.")", null,__FILE__,__LINE__);
    system("/usr/sbin/artica-phpfpm-service -reload-proxy");
    build_progress(100,"{reconfigure_proxy_service} {success}");
	return true;
}
function _out($text){

    $LOG_SEV = LOG_INFO;
    echo "HyperCache: $text\n";
    if (!function_exists("openlog")) {return false;}
    openlog("squid", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, "HyperCache module: $text");
    closelog();
    return true;
}

function stop():bool{
	$unix=new unix();
	@file_put_contents("/etc/squid3/StoreID.conf","# Stopped\n");
	@chown("/etc/squid3/StoreID.conf","squid");
	squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
    system("/usr/sbin/artica-phpfpm-service -reload-proxy");
    return true;
}

function update(){
	echo "Update....\n";
	$HyperCacheStoreID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoreID"));
	if($HyperCacheStoreID==0){echo "HyperCacheStoreID==0\n";return;}
	$q=new mysql_squid_builder();
	$Count=$q->COUNT_ROWS("StoreID");
	if(function_exists("FillStoreIDUpdates")){
		$sql=FillStoreIDUpdates();
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
	}
	$Count2=$q->COUNT_ROWS("StoreID");
	if($Count2<>$Count){
		rules();
	}
}


function rules(){
	
	$StoreIDUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StoreIDUrgency"));
	$SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
	if($SquidUrgency==1){$StoreIDUrgency=1;}
	if($StoreIDUrgency==1){
		build_progress(110,"{emergency}");
		@file_put_contents("/etc/squid3/StoreID.conf","# Emergency StoreIDUrgency = $StoreIDUrgency , SquidUrgency=$SquidUrgency\n");
		@chown("/etc/squid3/StoreID.conf","squid");
		return;
	}
	
	
	
	$unix=new unix();
	include_once(dirname(__FILE__)."/ressources/class.squid.hypercache.inc");
	
	build_progress(20,"{reconfigure_proxy_service}");
	$hyper=new HyperCacheSquid();
	$hyper->build();
	
	$squid_checks=new squid_checks();
	if(!$squid_checks->squid_parse()){
		build_progress("{reconfigure_proxy_service} {failed}",110);
		return;
	}
	
	build_progress(80,"{reconfigure_proxy_service} {success}");
	$squidbin=$unix->LOCATE_SQUID_BIN();
	build_progress(90,"{reloading}");
	if( is_file($squidbin)){ 
		squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
        system("/usr/sbin/artica-phpfpm-service -reload-proxy");}
	build_progress(100,"{reconfigure_proxy_service} {success}");
	
}

function build_sequence():bool{
	$HyperCacheStoreID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoreID"));
	if($HyperCacheStoreID==0){return true;}
    build_progress(10,"{checking_plugin}");
	build_sequence_plugin();
	return true;
}

function install(){
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("HyperCacheStoreID", 1);
	build_progress(10,"{installing}");
	hypercache_init_debian();
	build_progress(50,"{reconfiguring}");
	start();
}


function start(){
	$HyperCacheStoreID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoreID"));
	$SquidDisableHyperCacheDedup=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableHyperCacheDedup"));
	if($HyperCacheStoreID==0){
		@file_put_contents("/etc/squid3/StoreID.conf", "# Disabled\n");
		echo "Starting Hypercache Success (disabled)";
		return;
	}
	
	if($SquidDisableHyperCacheDedup==1){
		@file_put_contents("/etc/squid3/StoreID.conf", "# Temporary disabled\n");
		echo "Starting Hypercache Success (disabled)";
		return;
		
	}
	
	build_sequence();
	
	
}

function remove(){
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("HyperCacheStoreID", 0);
	build_progress(10,"{removing}");
	remove_service("/etc/init.d/hypercache");
	build_progress(100,"{removing} {done}");
	
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

function build_progress($pourc,$text){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid.hypercache.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid.hypercache.progress",0755);
	sleep(1);

}


function HyperCache(){
	$sock=new sockets();
	$HyperCacheStoreID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoreID"));
	if($HyperCacheStoreID==0){return;}
	
}



function verify_proxy_configuration(){
	$sock=new sockets();
	$f=explode("\n",@file_get_contents("/etc/squid3/StoreID.conf"));
	$HyperCacheStoreID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoreID"));
	
	build_progress(35,"{checking_configuration}");
	if($HyperCacheStoreID==0){
		if(ifHyperCacheInsquid()){
			build_progress(36,"{disabled_feature}");
			return false;
		}
	}

}
function hypercache_init_debian(){
	if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}
	$unix=new unix();
	$sock=new sockets();
	$servicebin=$unix->find_program("update-rc.d");
	$users=new usersMenus();

	if(!is_file($servicebin)){return;}
	$php=$unix->LOCATE_PHP5_BIN();
	if(!is_file($servicebin)){return;}
	$EnableDNSMASQ=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSMASQ"));




	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/hypercache";
	$php5script=basename(__FILE__);
	$daemonbinLog="HyperCache";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         dnsmasq";
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
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";


	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
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


}




