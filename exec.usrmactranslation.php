<?php
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.postgres.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
$GLOBALS["UPDATE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["HOTSPOT"]=false;
$GLOBALS["NORELOAD"]=false;
if(is_array($argv)){if(preg_match("#--no-reload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}}
if(is_array($argv)){if(preg_match("#--hotspot#",implode(" ",$argv))){$GLOBALS["HOTSPOT"]=true;}}
if(is_array($argv)){if(preg_match("#--update#",implode(" ",$argv))){$GLOBALS["UPDATE"]=true;}}
if(is_array($argv)){if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}}
if(is_array($argv)){if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["UPDATE"]=true;$GLOBALS["PROGRESS"]=true;}}



if($argv[1]=='--build'){build();exit;}
if($argv[1]=='--hotspot'){build_hotspot();exit;}
if($argv[1]=='--reload'){ReloadMacHelpers(true);exit;}



build();

function build_progress($text,$pourc){
	if(!$GLOBALS["PROGRESS"]){return;}
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/squid.macToUid.progress", serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);
	usleep(500);
}

function build(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,__FILE__)){echo "Already PID running $pid (".basename(__FILE__).")\n";exit();}
	
	$time=$unix->file_time_min($timefile);
	
	if(!$GLOBALS["FORCE"]){if($time<5){
		if($GLOBALS["VERBOSE"]){echo "{$time}mn < 5mn\n";}
		exit();}}
	
	@mkdir(dirname($pidfile),0755,true);
	@file_put_contents($pidfile, getmypid());
	@unlink($timefile);
	@file_put_contents($timefile, time());
	$php=$unix->LOCATE_PHP5_BIN();	
	
	$EnableSquidMicroHotSpot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
	@unlink("/etc/squid3/usersMacs.db");
	@unlink("/usr/share/artica-postfix/ressources/databases/usersMacs.db");
	if(!function_exists("IsPhysicalAddress")){include_once(dirname(__FILE__)."/ressources/class.templates.inc");}
	if(!class_exists("mysql_squid_builder")){include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");}
	
	build_progress("{starting}",10);
	
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	$rm=$unix->find_program("rm");
	
	$HotSpotDirectory="/home/artica/UsersMac/Hotspots";
	$Directory="/home/artica/UsersMac/Caches";
	if(is_dir($Directory)){
		shell_exec("$rm -rf $Directory");
	}
	if(is_dir($HotSpotDirectory)){
		shell_exec("$rm -rf $HotSpotDirectory");
	}
	@mkdir($Directory,0755,true);
	@chmod($Directory, 0755);
	@chown($Directory, "squid");
	@chgrp($Directory, "squid");
	
	@mkdir($HotSpotDirectory,0755,true);
	@chmod($HotSpotDirectory, 0755);
	@chown($HotSpotDirectory, "squid");
	@chgrp($HotSpotDirectory, "squid");
    $RedisServer=false;
	$Count=0;
    $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisServer"));

    if($EnableRedisServer==1) {
        $RedisServer=true;
        $redis = new Redis();
        try {
            $redis->connect('/var/run/redis/redis.sock');
        } catch (Exception $e) {
            $RedisServer=false;
            squid_admin_mysql(0,"Fatal connection to Redis server ".$e->getMessage(),null,__FILE__,__LINE__);

        }
    }

	$q=new postgres_sql();
	$sql="SELECT mac,ipaddr,proxyalias,hostname FROM hostsnet WHERE length(proxyalias) >0;";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = pg_fetch_assoc($results)) {
		if($ligne["mac"]=="00:00:00:00:00:00"){continue;}
		if(!IsPhysicalAddress($ligne["mac"])){continue;}
		
		echo "{$ligne["mac"]} = {$ligne["proxyalias"]}\n";
		$f=array();
		$f[]=$ligne["proxyalias"];
		$f[]=null;
		$f[]=$ligne["hostname"];
		$Count++;
		@file_put_contents("$Directory/{$ligne["mac"]}", @implode("|", $f));
		@chown("$Directory/{$ligne["mac"]}", "squid");
		@chgrp("$Directory/{$ligne["mac"]}", "squid");
		
		@file_put_contents("$Directory/{$ligne["ipaddr"]}", @implode("|", $f));
		@chown("$Directory/{$ligne["ipaddr"]}", "squid");
		@chgrp("$Directory/{$ligne["ipaddr"]}", "squid");

		if($RedisServer){
            $redis->set("usrmac:{$ligne["mac"]}",$ligne["proxyalias"]);
            $redis->set("usrmac:{$ligne["ipaddr"]}",$ligne["proxyalias"]);

        }

		
		
		build_progress($ligne["mac"],20);
		
	}


    build_hotspot_white();
	build_hotspot();

	
	if($Count==0){
		if($EnableSquidMicroHotSpot==0){
			if(IfInSquidConf()){
				build_progress("{reconfigure_proxy_service}...",80);
				shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");
			}
			build_progress("{done} no item...",100);
			return;
		}
	}

	
	build_progress("$Count items",70);
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidAliasesCount",$Count);
	
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(is_file($squidbin)){
		squid_admin_mysql(2, "Translation members database updated $Count items", null,__FILE__,__LINE__);
		if(!$GLOBALS["NORELOAD"]){
			if(!IfInSquidConf()){
				build_progress("{reconfigure_proxy_service}...",80);
				shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");
				build_progress("{done}...",100);
				return;
			}else{
				build_progress("{reloading}...",80);
				
				if( is_file($squidbin)){
					squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
					shell_exec("$nohup /usr/sbin/artica-phpfpm-service -reload-proxy >/dev/null 2>&1 &");}
			}
		}
	}
	
	

	build_progress("{done}...",100);
	
}

function build_hotspot_white(){
    $unix=new unix();
    $squidbin=$unix->LOCATE_SQUID_BIN();
    if(!is_file($squidbin)){return;}
    $rm=$unix->find_program("rm");
    $HotSpotDirectory="/home/artica/UsersMac/HotspotWhite";
    if(is_dir($HotSpotDirectory)){
        shell_exec("$rm -rf $HotSpotDirectory");
    }

    @mkdir($HotSpotDirectory,0755,true);
    @chmod($HotSpotDirectory, 0755);
    @chown($HotSpotDirectory, "squid");
    @chgrp($HotSpotDirectory, "squid");

    $q=new postgres_sql();
    $sql="SELECT mac,ipaddr,proxyalias,hostname FROM hostsnet WHERE hotspotwhite=1;";
    $results = $q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        echo "{$ligne["mac"]} = {$ligne["proxyalias"]}\n";
        @file_put_contents("$HotSpotDirectory/{$ligne["mac"]}", $ligne["proxyalias"]);
        @chown("$HotSpotDirectory/{$ligne["mac"]}", "squid");
        @chgrp("$HotSpotDirectory/{$ligne["mac"]}", "squid");

        @file_put_contents("$HotSpotDirectory/{$ligne["ipaddr"]}", $ligne["proxyalias"]);
        @chown("$HotSpotDirectory/{$ligne["ipaddr"]}", "squid");
        @chgrp("$HotSpotDirectory/{$ligne["ipaddr"]}", "squid");

    }


}

function build_hotspot(){
	
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squidbin)){return;}
	$rm=$unix->find_program("rm");
	$HotSpotDirectory="/home/artica/UsersMac/Hotspots";
	if(is_dir($HotSpotDirectory)){
		shell_exec("$rm -rf $HotSpotDirectory");
	}
	
	
	@mkdir($HotSpotDirectory,0755,true);
	@chmod($HotSpotDirectory, 0755);
	@chown($HotSpotDirectory, "squid");
	@chgrp($HotSpotDirectory, "squid");

	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("hotspot_sessions")){return;}
	
	$sql="SELECT uid,MAC,ipaddr FROM hotspot_sessions WHERE LENGTH(uid)>1";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysqli_fetch_assoc($results)) {
		
		$f=array();
		$f[]=$ligne["uid"];
		$f[]="hotspot";
		$f[]=null;
		@file_put_contents("$HotSpotDirectory/{$ligne["MAC"]}", @implode("|", $f));
		@chown("$HotSpotDirectory/{$ligne["MAC"]}", "squid");
		@chgrp("$HotSpotDirectory/{$ligne["MAC"]}", "squid");
		
		$f=array();
		$f[]=$ligne["uid"];
		$f[]="hotspot";
		$f[]=null;
		@file_put_contents("$HotSpotDirectory/{$ligne["ipaddr"]}", @implode("|", $f));
		@chown("$HotSpotDirectory/{$ligne["ipaddr"]}", "squid");
		@chgrp("$HotSpotDirectory/{$ligne["ipaddr"]}", "squid");
		
	}
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if( is_file($squidbin)){system("/usr/sbin/artica-phpfpm-service -reload-proxy");}
	
}

function ReloadMacHelpers($output=false){}


function IfInSquidConf(){
	
	$f=explode("\n",@file_get_contents("/etc/squid3/external_acls.conf"));
	
	foreach ($f as $index=>$line){
		if(preg_match("#external_acl_usersMacs\.#", $line)){
			return true;
		}
		
	}
	
	return false;
	
}



function download_mydb(){
	$sock=new sockets();
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	$squidbin=$unix->find_program("squid3");
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid");}
	if(!is_file($squidbin)){return;}		
	$RemoteStatisticsApplianceSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteStatisticsApplianceSettings")));
	if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
	if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
	$GLOBALS["REMOTE_SSERVER"]=$RemoteStatisticsApplianceSettings["SERVER"];
	$GLOBALS["REMOTE_SPORT"]=$RemoteStatisticsApplianceSettings["PORT"];
	$GLOBALS["REMOTE_SSL"]=$RemoteStatisticsApplianceSettings["SSL"];
	if($GLOBALS["REMOTE_SSL"]==1){$refix="https";}else{$refix="http";}
	$baseUri="$refix://{$GLOBALS["REMOTE_SSERVER"]}:{$GLOBALS["REMOTE_SPORT"]}/ressources/databases";	
	$uri="$baseUri/usersMacs.db";
	$curl=new ccurl($uri,true);
	if($curl->GetFile("/etc/squid3/usersMacs.db")){
		shell_exec("$chmod 755 /etc/squid3/usersMacs.db");
		squid_admin_mysql(1, "download usersMacs.db success",__FUNCTION__,__FILE__,__LINE__,"global-compile");
	}else{
		squid_admin_mysql(1, "Failed to download ufdbGuard.conf aborting `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"global-compile");
		return;			
	}
	squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
	$cmd="/etc/init.d/squid reload --script=".basename(__FILE__);
	shell_exec("$cmd >/dev/null 2>&1");

		
}

function notify_remote_proxys_usersMacs(){
}