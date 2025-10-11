<?php
$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
ini_set('memory_limit','1000M');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Webfiltering clients";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__)."/ressources/class.influx.inc");


BUILD_REPORT();


function build_progress($text,$pourc){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.statistics.webfiltering.members.refresh.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}




function BUILD_REPORT(){
	build_progress("{building_query}",50);
	$unix=new unix();
	
	$q=new postgres_sql();
	$q->QUERY_SQL("DROP TABLE webfiltering_clients");
	if(!$q->ok){
		echo $q->mysql_error."\n";
		build_progress("{failed}",110);return;
	}
	$q->QUERY_SQL("CREATE TABLE webfiltering_clients (client varchar(128) primary key,zperiod varchar(128),rqs BIGINT)");
	$q->create_index("webfiltering_clients","iclient",array("client"));
	$q->create_index("webfiltering_clients","irqs",array("rqs"));
	$q->create_index("webfiltering_clients","idate",array("period"));
	$sql="INSERT INTO \"webfiltering_clients\" (rqs,client) SELECT SUM(RQS) AS rqs,client FROM webfilter GROUP BY client";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error;
		build_progress("{failed}",110);
		return;
	}
	
	
	$results=$q->QUERY_SQL("SELECT client FROM webfiltering_clients");
	while ($ligne = pg_fetch_assoc($results)) {
		
		$ligne2=pg_fetch_assoc($q->QUERY_SQL("SELECT MAX(zDate) as max, MIN(zDate) as min from webfilter WHERE client='{$ligne["client"]}'"));
		
		if(!$q->ok){echo $q->mysql_error."\n";build_progress("{failed}",110);return;}
		$zperiod=$ligne2["min"]." - ".$ligne2["max"];
		build_progress("{updating} {$ligne["client"]} - $zperiod",60);
		$q->QUERY_SQL("UPDATE webfiltering_clients SET zperiod='$zperiod' WHERE client='{$ligne["client"]}'");
		if(!$q->ok){echo $q->mysql_error."\n";build_progress("{failed}",110);return;}
	}
	
	
	build_progress("{success}",100);
	
	
	

}
