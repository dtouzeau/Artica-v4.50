<?php
$GLOBALS["YESCGROUP"]=true;
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
$GLOBALS["makeQueryForce"]=false;


include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");

if(isset($argv[1])){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;$GLOBALS["makeQueryForce"]=true;}
    if($argv[1]=="--counters"){exit;}
}

if($GLOBALS["VERBOSE"]){echo "START.....\n";}
xstart();


function xstart(){
        $ARTBASE="/usr/share/artica-postfix";
		$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
		if($SQUIDEnable==0){
            if($GLOBALS["VERBOSE"]){
                echo "SQUIDEnable -> 0 DIE.....\n";
            }
            return false;
        }
        $SquidCachesProxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled"));
        $DisableAnyCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableAnyCache"));
		if($DisableAnyCache==1){$SquidCachesProxyEnabled=0;}


		$unix=new unix();
        $nohup = $unix->find_program("nohup");
        $php   = $unix->LOCATE_PHP5_BIN();
		$TimeFile="/etc/artica-postfix/pids/exec.squid.php.caches_infos.time";
		$TimePID="/etc/artica-postfix/pids/exec.squid.php.caches_infos.pid";
		
		
		if(!$GLOBALS["FORCE"]){
			$CacheTime=$unix->file_time_min($TimeFile);
			if($CacheTime<4){
				if($GLOBALS["VERBOSE"]){echo "Max 4Mn, current=$CacheTime\n";}
				return false;
			}
			
			$pid=$unix->get_pid_from_file($TimePID);
			if($unix->process_exists($pid)){
                $unix->ToSyslog("caches_infos(): Already Artica process running pid $pid","storedir");
                return false;
			}
			
		}


		$squid_pid=$unix->SQUID_PID();
		if(!$unix->process_exists($squid_pid)){
            if(!is_file("/etc/squid3/squid.conf")){
                squid_admin_mysql(0, "Proxy service: missing main configuration [action=reconfigure]",
                    null, __FILE__, __LINE__);
                shell_exec("$nohup $php $ARTBASE/exec.squid.php --build >/dev/null 2>&1 &");
                return false;

            }


		    if(is_file("/etc/init.d/squid")) {
                squid_admin_mysql(0, "Proxy service is down [{action}={start}]", null, __FILE__, __LINE__);
                start_squid();

            }
			return false;
		}
		
		if(!$GLOBALS["FORCE"]){
			$ttl=$unix->PROCCESS_TIME_MIN($squid_pid);
			if($unix->PROCCESS_TIME_MIN($squid_pid)<5){
				echo "caches_infos(): squid-cache running only since {$ttl}mn, aborting\n";
				return false;
			}
		}
		
		
		@unlink($TimeFile);
		@file_put_contents($TimeFile, time());
		if($SquidCachesProxyEnabled==1){caches_infos();}



}

function start_squid(){
    $unix=new unix();
    $squidbin=$unix->LOCATE_SQUID_BIN();
    exec("$squidbin -f /etc/squid3/squid.conf",$results);
    $CONF_ERROR=false;
    foreach ($results as $line){
        $line=trim($line);
        if(preg_match("#Warning:\s+#i",$line)){continue;}
        $conf[]=$line;
        if(preg_match("#ACL not found:\s+(.+)#i",$line,$re)){$CONF_ERROR=true;}
        if(preg_match("#FATAL: Bungled:\s+(.+)#i",$line,$re)){$CONF_ERROR=true;}
        if(preg_match("#FATAL: Bungled.*?\/ssl.conf#",$line)){
            squid_admin_mysql(0,"Missing SSL parameters in proxy configuration turn into emergency",@implode("\n",$conf),
                __FILE__,__LINE__);
            shell_exec("/usr/sbin/artica-phpfpm-service -proxy-ssl-emergency-remove");
            return true;
        }

    }
    if($CONF_ERROR) {
        squid_admin_mysql(0, "Missing parameters in proxy configuration ( see report )", @implode("\n", $conf),
            __FILE__, __LINE__);
    }
    return true;
}



function caches_infos(){
	$unix=new unix();
	$array=$unix->squid_get_cache_infos();

	for($i=0;$i<10;$i++){
		$check=true;
			
		if(!is_array($array)){
			if($GLOBALS["VERBOSE"]){echo "unix->squid_get_cache_infos() Not an array...\n";}
			sleep(1);
			$array=$unix->squid_get_cache_infos();
			continue;

		}
			
		if(count($array)==0){
			if($GLOBALS["VERBOSE"]){echo "unix->squid_get_cache_infos() O items !!\n";}
			sleep(1);
			$array=$unix->squid_get_cache_infos();
			continue;
		}
		if($check){
			break;
		}

	}

	if($GLOBALS["VERBOSE"]){
	    print_r($array);
    }

	if(!is_array($array)){if($GLOBALS["VERBOSE"]){echo "unix->squid_get_cache_infos() Not an array...\n";}return;}
	if(count($array)==0){
		if($GLOBALS["VERBOSE"]){echo basename(__FILE__)."[".__LINE__."] unix->squid_get_cache_infos() O items !!...\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("squid_get_cache_infos.db", serialize($array));
		@chmod("/etc/artica-postfix/settings/Daemons/squid_get_cache_infos.db",0755);
		return;
	}

		@unlink("/etc/artica-postfix/settings/Daemons/squid_get_cache_infos.db");
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("squid_get_cache_infos.db", serialize($array));
		@chmod("/etc/artica-postfix/settings/Daemons/squid_get_cache_infos.db",0755);


	$uuid=$unix->GetUniqueID();
	$q2=new lib_sqlite("/home/artica/SQLITE/caches.db");
	

	foreach ($array as $directory=>$arrayDir){
		$directory=trim($directory);
		if($directory==null){continue;}
		if(!isset($arrayDir["POURC"])){$arrayDir["POURC"]=0;}
        if(!isset($arrayDir["CURRENT"])){$arrayDir["CURRENT"]=0;}

		$arrayDir["CURRENT"]=intval($arrayDir["CURRENT"]);
		$arrayDir["POURC"]=intval($arrayDir["POURC"]);
		
		if($GLOBALS["VERBOSE"]){echo "****************************\n$directory Current = {$arrayDir["CURRENT"]} {$arrayDir["POURC"]}%\n****************************\n";}
		
		
		if($directory=="MEM"){continue;}
		
		if($GLOBALS["VERBOSE"]){echo "('$uuid','$directory','{$arrayDir["MAX"]}','{$arrayDir["CURRENT"]}','{$arrayDir["POURC"]}')\n";}
		$f[]="('$uuid','$directory','{$arrayDir["MAX"]}','{$arrayDir["CURRENT"]}','{$arrayDir["POURC"]}')";
		
		if($arrayDir["CURRENT"]==0){continue;}
		
		$PERC=$arrayDir["POURC"];
		$USED=$arrayDir["CURRENT"];
		

		
		
		if(preg_match("#\/home\/squid\/cache\/MemBooster([0-9]+)#", $directory,$re)){
			$sql="UPDATE squid_caches_center SET percentcache='$PERC',percenttext='$PERC', `usedcache`='$USED' WHERE ID={$re[1]}";
			echo $sql."\n";
			$q2->QUERY_SQL($sql);
			if(!$q2->ok){squid_admin_mysql(0, "MySQL Error on {APP_PROXY} Cache Center", $q2->mysql_error,__FILE__,__LINE__);}
			continue;
		}
		
		if($GLOBALS["VERBOSE"]){echo "$directory -> $USED / {$PERC}%\n";}
		$sql="UPDATE squid_caches_center SET percentcache='$PERC',percenttext='$PERC', `usedcache`='$USED' WHERE `cache_dir`='$directory'";
		echo $sql."\n";
		$q2->QUERY_SQL($sql);
		if(!$q2->ok){squid_admin_mysql(0, "MySQL Error on {APP_PROXY} Cache Center", $q2->mysql_error,__FILE__,__LINE__);}
		
		
	
	}

	
	
	
	
}