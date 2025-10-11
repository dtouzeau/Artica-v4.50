<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.familysites.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}

xrun();


function kill_purge(){
	
	$unix=new unix();
	$purge=$unix->find_program("purge");
	$running=1;
	while ($running==1) {
		$running=0;
		$pid=$unix->PIDOF($purge);
		if($unix->process_exists($pid)){
			$running=1;
			echo "Killing $purge process pid $pid\n";
			$unix->KILL_PROCESS($pid,9);
			sleep(1);
		}
		
	}
	
}

function xrun(){
	$unix=new unix();
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	$SquidCachesProxyEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled");
	$DisableAnyCache=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableAnyCache");
	$EnableSquidPurgeStoredObjects=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidPurgeStoredObjects"));
	
	$TimeFile="/etc/artica-postfix/pids/exec.purge.php.time";
	$pidfile="/etc/artica-postfix/pids/exec.purge.php.pid";
	$EnableSquidPurgeStoredObjectsTime=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidPurgeStoredObjectsTime"));
	$EnableSquidPurgeStoredObjectsMaxTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidPurgeStoredObjectsMaxTime"));
	if($EnableSquidPurgeStoredObjectsMaxTime==0){$EnableSquidPurgeStoredObjectsMaxTime=120;}

	if($SQUIDEnable==0){if($GLOBALS["VERBOSE"]){echo "SQUIDEnable -> 0 DIE.....\n";}return;}
	if($SquidCachesProxyEnabled==0){
	        if($GLOBALS["VERBOSE"]){echo "SquidCachesProxyEnabled -> 0 DIE.....\n";}
        build_progress("{failed} {not_installed}",110);
	        return;
	}
	if($DisableAnyCache==1){
	    if($GLOBALS["VERBOSE"]){echo "DisableAnyCache -> 1 DIE.....\n";}
        build_progress("{failed} {not_enabled}",110);
	    return;}

	
	$pid=$unix->get_pid_from_file($pidfile);
		
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		build_progress("Already: {running} $pid {since} {$timepid}mn, aborting",110);
		
		if(!$GLOBALS["FORCE"]){
			if($timepid>30){
				$kill=$unix->find_program("kill");
				unix_system_kill_force($pid);
			}
		}
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	if(!$GLOBALS["FORCE"]){
		$time=$unix->file_time_min($TimeFile);
		if($time<$EnableSquidPurgeStoredObjectsMaxTime){
			build_progress("Current {$time}Mn, require at least 14mn, aborting",110);
			echo "Current {$time}Mn, require at least 14mn\n";
			return;
		}
	}
		
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	
	$myhostname=$unix->hostname_g();
	$q=new postgres_sql();
	$q->CREATE_PURGE_TABLE();
	if(!$q->ok){
		echo "$q->mysql_error\n";
		build_progress("{mysql_error} on CREATE_PURGE_TABLE()...",110);
		return;
	}
	

	
	$proxyIP="127.0.0.1";
	$proxyport=$unix->squid_internal_port();
	$proxyaddr="$proxyIP:$proxyport";
	echo "Using $proxyaddr\n";
	
	$purge=$unix->find_program("purge");
	if(!is_file($purge)){
		echo "purge no such binary...\n";
		build_progress("{failed}...",110);
		return;
	}
	
	$pid=$unix->PIDOF($purge);
	if($unix->process_exists($pid)){
		echo "Already running PID since ".$unix->PROCCESS_TIME_MIN($pid)." minutes...\n";
		build_progress("{failed}...",110);
		return;
	}
	
	build_progress("{query_the_proxy_service}...",15);
	$tempfile=$unix->TEMP_DIR()."/SquidPurge.txt";
	$t=time();
	squid_admin_mysql(1, "Launching purge process - proxy service should be decreased during this time", null,__FILE__,__LINE__);
	
	$cmd="$purge -n -p $proxyaddr -c /etc/squid3/caches.conf -e . >$tempfile 2>&1";
	echo $cmd."\n";
	shell_exec($cmd);


	build_progress("{cleaning}...",20);
	$sql="TRUNCATE TABLE squidpurge";
	echo $sql."\n";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "$q->mysql_error\n";
		build_progress("{mysql_error}...",110);
		return;
	}
	
	squid_admin_mysql(1, "executed purge process duration:".$unix->distanceOfTimeInWords($t,time()), null,__FILE__,__LINE__);
	$q2=new lib_sqlite("/home/artica/SQLITE/caches.db");
	$sql="SELECT cache_dir,ID FROM squid_caches_center";
	$results=$q2->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		$cache_dir=trim($ligne["cache_dir"]);
		$cache_dir=str_replace("/", "\/", $cache_dir);
		$cache_dir=str_replace(".", "\.", $cache_dir);
		$MAIN_CACHES["$cache_dir"]=$ligne["ID"];
	}
	
	
	$fam=new squid_familysite();
	$size=@filesize($tempfile);
	echo "$tempfile = $size Bytes...\n";
	$max=$unix->COUNT_LINES_OF_FILE($tempfile);

	$suffix="INSERT INTO \"squidpurge\" (cachedir,proxyname,familysite,sitename,path,size) VALUES";
	$c=0;
	$l=0;
	$sw=0;
	if ($fd = fopen($tempfile, 'r')) {
		while (!feof($fd)) {
			$strline= trim(fgets($fd));
			$l++;
            $sw++;
			$TT = preg_split('/\s+/', $strline);
			reset($MAIN_CACHES);
			$cacheid=0;
			$cachedir=$TT[0];
			$size=$TT[2];
			$URI=$TT[3];


			if($sw>10) {
                $sw = 0;
                $prc = $l / $max;
                $prc1 = round($prc * 100, 1);
                $prcZ = round($prc * 100);

                if ($prcZ < 20) {
                    build_progress("{injecting} {$prc1}%", 20);
                }
                if ($prcZ > 90) {
                    $prcZ = 90;
                }
                if ($prcZ > 20) {
                    build_progress("{injecting} {$prc1}%", $prcZ);
                }
            }

			if(!is_numeric($size)){continue;}
			
			while (list ($reg, $ID) = each ($MAIN_CACHES) ){
				if(preg_match("#$reg#", $cachedir)){
					$cacheid=$ID;
					break;
				}
				
			}
			
			
			$c++;
			$uris=parse_url($URI);
			$host=$uris["host"];
			$path=$uris["path"];
			if(preg_match("#^(.+?):([0-9]+)#", $host,$re)){$host=$re[1];}
			$familysite=$fam->GetFamilySites($host);
			
			if(strlen($familysite)>64){$familysite=substr($familysite, 0,64);}
			if(strlen($host)>128){$host=substr($host, 0,128);}
			if(strlen($path)>128){$path=substr($path, 0,128);}
			
			$suffix_sql[]="('$cacheid','$myhostname','$familysite','$host','$path','$size')";
			
			if(count($suffix_sql)>500){
				build_progress("{injecting} $c {items}...",$prcZ);
				$q->QUERY_SQL($suffix." ".@implode(",", $suffix_sql));
				$suffix_sql=array();
				if(!$q->ok){
					echo $q->mysql_error."\n";
					build_progress("{injecting}  {failed}...",110);
					@fclose($fd);
					@unlink($tempfile);
					return;
					
				}
			}
		}
	}
	
	if(count($suffix_sql)>0){
		build_progress("{injecting} $c {items}...",90);
		$q->QUERY_SQL($suffix." ".@implode(",", $suffix_sql));
		$suffix_sql=array();
		if(!$q->ok){
			echo $q->mysql_error."\n";
			build_progress("{injecting}  {failed}...",110);
			@fclose($fd);
			@unlink($tempfile);
			return;
				
		}
	}
	build_progress("{injecting} $c {items} {success}...",100);
	@fclose($fd);
	@unlink($tempfile);

}


function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.purge.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
