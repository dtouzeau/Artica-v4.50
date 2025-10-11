<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["ULIMITED"]=false;
$GLOBALS["VERBOSE2"]=false;
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.postfix.builder.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.ini-frame.inc");
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--unlimit#",implode(" ",$argv))){$GLOBALS["ULIMITED"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--verb2#",implode(" ",$argv))){$GLOBALS["VERBOSE2"]=true;}

$sock=new sockets();
$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
if($DisableMessaging==1){exit();}

if($argv[1]=="--count"){count_tables_hours();exit();}
if($argv[1]=="--hours"){MilterGreyList_hours();exit();}




MiltergreyList_days();


function MilterGreyList_hours(){
	$unix=new unix();
	$timeFile="/etc/artica-postfix/pids/exec.postfix.miltergrey.stats.php.MilterGreyList_hours.time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$pidTime=$unix->PROCCESS_TIME_MIN($pid);
		squid_admin_mysql(2, "Already process PID: $pid {running} {since} $pidTime minutes", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return;
	}
	
	
	if(!$GLOBALS["VERBOSE"]){
		if(!$GLOBALS["FORCE"]){
			if($unix->file_time_min($timeFile)<15){exit();}
		}
	}
	
	
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	@file_put_contents($pidfile, getmypid());
	
	
	if(!$GLOBALS["FORCE"]){
		if(system_is_overloaded(basename(__FILE__))){
			squid_admin_mysql(2, "{OVERLOADED_SYSTEM}, aborting", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
			return;
		}
	}
	
	
	@mkdir("/home/artica/postfix/milter-greylist/logger",0755,true);
	@chmod("/home/artica/postfix/milter-greylist",0755,true);
	@chown("/home/artica/postfix/milter-greylist", "postfix");
	@chgrp("/home/artica/postfix/milter-greylist", "postfix");
	
	@chmod("/home/artica/postfix/milter-greylist/logger",0755,true);
	@chown("/home/artica/postfix/milter-greylist/logger", "postfix");
	@chgrp("/home/artica/postfix/milter-greylist/logger", "postfix");
	
	
	
	$q=new mysql_postfix_builder();
	$CurrentHour=date("YmdH").".miltergreylist.db";
	$unix=new unix();
	$c=0;
	$Files=$unix->DirFiles("/home/artica/postfix/milter-greylist/logger","^([0-9]+)\.miltergreylist\.db");
	foreach ($Files as $filename=>$none){
		$c++;
		$path="/home/artica/postfix/milter-greylist/logger/$filename";
		if(!preg_match("#^([0-9]+)\.miltergreylist\.db#", $filename,$re)){continue;}
		$array=MilterGreyList_hours_parse($path);
		if(!is_array($array)){continue;}
		if(count($array)==0){continue;}
		$tablename="mgreyh_".date("YmdH");
		if($filename==$CurrentHour){
			$q->QUERY_SQL("DROP TABLE MGREY_RTT");
			$prefix="INSERT IGNORE INTO MGREY_RTT (`zmd5`,`ztime`,`zhour`,`mailfrom`,`instancename`,`mailto`,`domainfrom`,`domainto`,`senderhost`,`failed`) VALUES ";
			if(!MilterGreyList_hours_create_table("MGREY_RTT",true)){continue;}
			$q->QUERY_SQL($prefix.@implode(",", $array));
			continue;
		}
		if(!MilterGreyList_hours_create_table($tablename)){continue;}
		$prefix="INSERT IGNORE INTO $tablename (`zmd5`,`ztime`,`zhour`,`mailfrom`,`instancename`,`mailto`,`domainfrom`,`domainto`,`senderhost`,`failed`) VALUES ";
		$q->QUERY_SQL($prefix.@implode(",", $array));
		if(!$q->ok){continue;}
		@unlink($path);
	}
		
		
	
}


function MilterGreyList_hours_create_table($tablename,$memory=false){
	
	$ENGINE="MYISAM";
	if($memory){$ENGINE="MEMORY";}
	
	$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
	`zmd5` varchar(90) NOT NULL,
	`ztime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`zhour` tinyint(2) NOT NULL,
	`mailfrom` varchar(255) NOT NULL,
	`instancename` varchar(255) NOT NULL,
	`mailto` varchar(255) NOT NULL,
	`domainfrom` varchar(255) NOT NULL,
	`domainto` varchar(255) NOT NULL,
	`senderhost` varchar(128) NOT NULL,
	`failed` varchar(15) NOT NULL,
	PRIMARY KEY (`zmd5`),
	KEY `ztime` (`ztime`,`zhour`),
	KEY `mailfrom` (`mailfrom`),
	KEY `mailto` (`mailto`),
	KEY `domainfrom` (`domainfrom`),
	KEY `domainto` (`domainto`),
	KEY `senderhost` (`senderhost`),
	KEY `instancename` (`instancename`),
	KEY `failed` (`failed`)
	) ENGINE=$ENGINE";	
	
	$q=new mysql_postfix_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){return false;}
	return true;
	
}

function MilterGreyList_hours_parse($path){
		if($GLOBALS["VERBOSE"]){echo "Parsing $path\n";}
		$db_con = dba_open($path, "r","db4");
		if(!$db_con){echo "DB open $path failed\n";return false;}
		
		$mainkey=dba_firstkey($db_con);
		
		while($mainkey !=false){
			$data=dba_fetch($mainkey,$db_con);
			$f[]=$data;
			$mainkey=dba_nextkey($db_con);
		}
		
		dba_close($db_con);
		if($GLOBALS["VERBOSE"]){echo "Parsing $path END\n";}
		return $f;
		
	}


function MiltergreyList_days(){
	
	
	
	$unix=new unix();
	///etc/artica-postfix/pids/exec.postfix.miltergrey.stats.php.MiltergreyList_days.time
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	
	
	@mkdir("/home/artica/postfix/milter-greylist/logger",0755,true);
	@chmod("/home/artica/postfix/milter-greylist",0755,true);
	@chown("/home/artica/postfix/milter-greylist", "postfix");
	@chgrp("/home/artica/postfix/milter-greylist", "postfix");
	
	@chmod("/home/artica/postfix/milter-greylist/logger",0755,true);
	@chown("/home/artica/postfix/milter-greylist/logger", "postfix");
	@chgrp("/home/artica/postfix/milter-greylist/logger", "postfix");
	
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$pidTime=$unix->PROCCESS_TIME_MIN($pid);
		squid_admin_mysql(2, "Already process PID: $pid {running} {since} $pidTime minutes", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return;
	}
	
	
	if(!$GLOBALS["VERBOSE"]){
		if(!$GLOBALS["FORCE"]){
			if($unix->file_time_min($timeFile)<60){exit();}
		}
	}
	
	
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	@file_put_contents($pidfile, getmypid());
	
	
	if(!$GLOBALS["FORCE"]){
		if(system_is_overloaded(basename(__FILE__))){
			squid_admin_mysql(2, "{OVERLOADED_SYSTEM}, aborting", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
			return;
		}	
	}
	
	
	$q=new mysql_postfix_builder();
	if($GLOBALS["VERBOSE"]){echo "Scanning tables...\n"; }
	$tables=$q->LIST_MILTERGREYLIST_HOUR_TABLES();
	$currentHour=date("Y-m-d H");
	$tt=0;
	if(is_array($tables)){
		while (list ($tablesource, $time) = each ($tables) ){
			$tt++;
			if(date("Y",$time)=="1970"){
				$q->QUERY_SQL("DROP TABLE $tablesource");
				continue;
			}
			
			if( date("Y-m-d H",$time)== $currentHour ){if($GLOBALS["VERBOSE"]){echo "Skipping $currentHour\n";}continue;}
			if($GLOBALS["VERBOSE"]){echo "Processing $tablesource: ".date("Y-m-d H",$time)."[".__LINE__."]\n";}
			
			
			if(MiltergreyList_scan($tablesource,$time)){
				if($GLOBALS["VERBOSE"]){echo "DUMP_TABLE $tablesource: ".date("Y-m-d H",$time)."\n";}
				if($q->DUMP_TABLE($tablesource)){
					$q->QUERY_SQL("DROP TABLE $tablesource");
				}
			}else{
				if($GLOBALS["VERBOSE"]){echo "$tablesource failed...\n";}
			}
			
			if(system_is_overloaded(basename(__FILE__))){
				squid_admin_mysql(2, "{OVERLOADED_SYSTEM}, aborting task after $tt processed tables ", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
				return;
			}	
			
		}
	
	}
	
	
	if(!$GLOBALS["FORCE"]){
		if(system_is_overloaded(basename(__FILE__))){
			squid_admin_mysql(2, "{OVERLOADED_SYSTEM}, aborting", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
			return;
		}
	}	

	$yesterday=$q->HIER();
	$tables=$q->LIST_MILTERGREYLIST_DAY_TABLES();
	if(is_array($tables)){
		while (list ($tablesource, $time) = each ($tables) ){
			if( date("Y-m-d",$time)== date("Y-m-d") ){
				if($GLOBALS["VERBOSE"]){echo "Skipping $currentHour\n";}
				continue;
			}
			
			if(date("Y-m-d",$time)== $yesterday ){
				if($GLOBALS["VERBOSE"]){echo "Skipping $currentHour\n";}
				continue;
			}
			
			if($GLOBALS["VERBOSE"]){echo "Processing $tablesource: ".date("Y-m-d",$time)."[".__LINE__."]\n";}
			if(MiltergreyList_month($tablesource,$time)){
				if($GLOBALS["VERBOSE"]){echo "DUMP_TABLE $tablesource: ".date("Y-m-d H",$time)."\n";}
				if($q->DUMP_TABLE($tablesource)){
					$q->QUERY_SQL("DROP TABLE $tablesource");
				}
			}else{
				if($GLOBALS["VERBOSE"]){echo "$tablesource: {failed}\n";}
			}
		
		}
	}	
	
	

}

function MiltergreyList_scan($tablesource,$time){
	$q=new mysql_postfix_builder();
	if(date("Y-m-d h")==date("Y-m-d h",$time)){return false;}
	
	
	$NextTable="mgreyd_".date("Ymd",$time);
	if($GLOBALS["VERBOSE"]){echo "Processing $tablesource -> $NextTable\n";}
	if(!$q->milter_BuildDayTable($NextTable)){return false;}
	$database=$q->database;

	
	$prefix="INSERT IGNORE INTO $NextTable 
	(zmd5,hits,zhour,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,`failed`) VALUES ";
	
	$sql="SELECT COUNT(zmd5) as hits,zhour,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,failed FROM $tablesource 
	GROUP BY zhour,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,failed";
	$results = $q->QUERY_SQL($sql,$database);
	$f=array();
	while ($ligne = mysqli_fetch_assoc($results)) {
		$zmd5=md5(serialize($ligne));
		$zhour=$ligne["zhour"];
		$hits=$ligne["hits"];
		$mailfrom=mysql_escape_string2($ligne["mailfrom"]);
		$instancename=mysql_escape_string2($ligne["instancename"]);
		$mailfrom=mysql_escape_string2($ligne["mailfrom"]);
		$mailto=mysql_escape_string2($ligne["mailto"]);
		
		$domainfrom=mysql_escape_string2($ligne["domainfrom"]);
		$domainto=mysql_escape_string2($ligne["domainto"]);
		$mailto=mysql_escape_string2($ligne["mailto"]);
		$senderhost=mysql_escape_string2($ligne["senderhost"]);
		$failed=$ligne["failed"];
		$f[]="('$zmd5','$hits','$zhour','$mailfrom','$instancename','$mailto','$domainfrom','$domainto','$senderhost','$failed')";
		
		if(count($f)>500){
			if($GLOBALS["VERBOSE"]){echo $NextTable." "."500\n";}
			$q->QUERY_SQL($prefix.@implode(",", $f),$database);
			if(!$q->ok){return false;}
			$f=array();
		}
		
	}
	
	if(count($f)>0){
		if($GLOBALS["VERBOSE"]){echo $NextTable." ".count($f)."\n";}
		$q->QUERY_SQL($prefix.@implode(",", $f),$database);
		if(!$q->ok){return false;}
		$f=array();		
		
	}
	return true;
}

function count_tables_hours(){
	$dir="/var/lib/mysql/postfixlog";
	$unix=new unix();
	return $unix->COUNT_FILES($dir);
	
	
}

function MiltergreyList_month($tablesource,$time){
	$q=new mysql_postfix_builder();
	if(date("Y-m-d")==date("Y-m-d",$time)){return false;}


	$NextTable="mgreym_".date("Ym",$time);
	if($GLOBALS["VERBOSE"]){echo "Processing $tablesource -> $NextTable\n";}
	if(!$q->milter_BuildMonthTable($NextTable)){return false;}
	$database=$q->database;


	$prefix="INSERT IGNORE INTO $NextTable
	(zmd5,hits,zday,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,`failed`) VALUES ";

	$sql="SELECT SUM(hits) as hits,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,failed FROM $tablesource
	GROUP BY mailfrom,instancename,mailto,domainfrom,domainto,senderhost,failed";
	$results = $q->QUERY_SQL($sql,$database);
	$f=array();
	while ($ligne = mysqli_fetch_assoc($results)) {
		$zday=date("Y-m-d",$time);
		$zmd5=md5(serialize($ligne).$zday);
		$hits=$ligne["hits"];
		$mailfrom=mysql_escape_string2($ligne["mailfrom"]);
		$instancename=mysql_escape_string2($ligne["instancename"]);
		$mailfrom=mysql_escape_string2($ligne["mailfrom"]);
		$mailto=mysql_escape_string2($ligne["mailto"]);
		$domainfrom=mysql_escape_string2($ligne["domainfrom"]);
		$domainto=mysql_escape_string2($ligne["domainto"]);
		$mailto=mysql_escape_string2($ligne["mailto"]);
		$senderhost=mysql_escape_string2($ligne["senderhost"]);
		$failed=$ligne["failed"];
		$f[]="('$zmd5','$hits','$zday','$mailfrom','$instancename','$mailto','$domainfrom','$domainto','$senderhost','$failed')";

		if(count($f)>500){
			$q->QUERY_SQL($prefix.@implode(",", $f),$database);
			if(!$q->ok){return false;}
			$f=array();
		}

	}

	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f),$database);
		if(!$q->ok){return false;}
		$f=array();
	
	}
	return true;
}