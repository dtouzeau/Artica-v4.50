<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');



xrun();


function xrun(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/HyperCacheStatsHourlyQueue.pid";
	$pidTime="/etc/artica-postfix/pids/HyperCacheStatsHourlyQueue.time";
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){die("Already executed");}
	
	@file_put_contents($pidfile, getmypid());
	$timeExec=$unix->file_time_min($pidTime);
	
	if($timeExec<50){die("Only Each 50mn");}
	@unlink($pidfile);
	@file_put_contents($pidfile, time());
	
	
	$q=new postgres_sql();
	$q->CREATE_TABLES();
	if($q->isRemote){return;}
	
	$sql = "select tablename from pg_tables where tablename ~* 'temphypercache_[0-9]+' order by tablename";
	
	$curtime=date("YmdH");
	$cur_tablename="temphypercache_$curtime";
	

	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	
	while ($ligne = pg_fetch_array($results, null, PGSQL_ASSOC)) {
		$tablename=$ligne["tablename"];
		if($tablename==$cur_tablename){continue;}
		echo $tablename."\n";
		$TimeGroup="date_trunc('hour', zdate) as zdate";
		
			
		$sql="SELECT SUM(size) as size,SUM(rqs) as rqs,$TimeGroup,cached,category,familysite FROM \"$tablename\" GROUP 
		BY date_trunc('hour', zdate),category,familysite,cached";
		$sql="INSERT INTO \"hypercache_access\" (size,rqs,zdate,cached,category,familysite) $sql";
		echo $sql."\n";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";continue;}
		$q->QUERY_SQL("DROP TABLE \"$tablename\"");
		if(!$q->ok){echo $q->mysql_error."\n";continue;}
	}

}