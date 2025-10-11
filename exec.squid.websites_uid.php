<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');
include_once(dirname(__FILE__).'/ressources/class.squid.youtube.inc');

$sock=new sockets();
$sock->SQUID_DISABLE_STATS_exit();
$GLOBALS["CLASS_UNIX"]=new unix();

if($argv[1]=="--reset"){websites_uid_reset();exit();}
if($argv[1]=='--websites-uid'){websites_uid();exit;}
if($argv[1]=='--websites-uid-reset'){websites_uid_reset();exit;}
if($argv[1]=='--websites-uid-categories'){websites_uid_to_categories();exit;}
if($argv[1]=='--websites-uid-categorize'){websites_uid_not_categorised($argv[2],null,true);exit;}
websites_uid();


function websites_uid_reset(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE tables_day SET websites_uid=0");
	websites_uid();
}
function percentage($text,$purc){


	$array["TITLE"]=$text.": ".date("H:i:s");
	$array["POURC"]=$purc;
	@file_put_contents("/usr/share/artica-postfix/ressources/squid.stats.progress.inc", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/squid.stats.progress.inc",0755);
	$pid=getmypid();
	$lineToSave=date('H:i:s')." [$pid] [$purc] $text";
	if($GLOBALS["VERBOSE"]){echo "$lineToSave\n";}
	$f = @fopen("/var/log/artica-squid-statistics.log", 'a');
	@fwrite($f, "$lineToSave\n");
	@fclose($f);

}


function websites_uid(){
	
	$GLOBALS["Q"]=new mysql_squid_builder();
	if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
	$unix=new unix();
	if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
			return;
		}
	
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<540){return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}	
	
	
	
	if(isset($GLOBALS["websites_uid_executed"])){return;}
	$GLOBALS["websites_uid_executed"]=true;
	$q=new mysql_squid_builder();
	$sql="SELECT tablename,zDate FROM `tables_day` WHERE websites_uid=0 AND zDate<DATE_SUB(NOW(),INTERVAL 1 DAY)";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		if(preg_match("#Unknown column#",$q->mysql_error)){
			$q->CheckTables();
			$results=$q->QUERY_SQL($sql);
		}
	}

	if(!$q->ok){if($GLOBALS["VERBOSE"]){echo "############# ERROR #########\n$q->mysql_error\Line:".__LINE__."\n#############\n";}return;}
	$c=0;
	if(mysqli_num_rows($results)==0){return;}
	$TOT=mysqli_num_rows($results);
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$date=$ligne["zDate"];
		$c++;
		$time=strtotime($date." 00:00:00");
		$tablename=$ligne["tablename"];
		$but=null;
		$hourtable=date("Ymd",$time)."_hour";
		if(!$q->TABLE_EXISTS($hourtable)){
			if($q->TABLE_EXISTS($tablename)){$but=" but $tablename exists..";}
			if($GLOBALS["VERBOSE"]){echo "############# ERROR #########\n$hourtable no such table ($date) $but\n#############\n";}
			continue;
		}
		
		events("websites_uid_from_hourtable($hourtable,$time)");
		percentage("Statistics by Users/Websites: $date $c/$TOT",71);
		
		
		if(websites_uid_from_hourtable($hourtable,$time)){
			$q->QUERY_SQL("UPDATE tables_day SET websites_uid=1 WHERE tablename='$tablename'");
			if(SquidStatisticsTasksOverTime()){ squid_admin_mysql(1,"Statistics overtime... aborting",ps_report(),__FILE__,__LINE__); return; }
			continue;
		}else{
			if($GLOBALS["VERBOSE"]){echo "Return false for $hourtable injection\n";}
		}
	}
	if(SquidStatisticsTasksOverTime()){ squid_admin_mysql(1,"Statistics overtime... aborting",ps_report(),__FILE__,__LINE__); return; }
	


}

function websites_uid_to_categories(){
	$q=new mysql_squid_builder();
	$tables=$q->LIST_TABLES_WWWUID();
	if(count($tables)>0){
		while (list ($tablename, $rows) = each ($tables) ){
			if($GLOBALS["VERBOSE"]){echo "\n\n***** TESTING TABLE `$tablename`\n******\n\n";}
			websites_uid_not_categorised(null,$tablename);

		}

	}
}

function websites_uid_from_hourtable($tablename,$time){
	$zdate=date("Y-m-d",$time);
	$q=new mysql_squid_builder();
	$sql="SELECT uid, SUM(size) as size,SUM(hits) as hits,
	familysite,category FROM $tablename GROUP BY uid,familysite
	HAVING LENGTH(uid)>0";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){if($GLOBALS["VERBOSE"]){echo "############# ERROR #########\n$q->mysql_error\Line:".__LINE__."\n#############\n";}return false;}

	$a=0;
	$c=0;
	if(mysqli_num_rows($results)==0){return true;}
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$c++;$a++;
		$uid=$ligne["uid"];
		$size=$ligne["size"];
		$hits=$ligne["hits"];
		$familysite=trim($ligne["familysite"]);
		$category=trim($ligne["category"]);

		if($familysite==null){continue;}
		$md5=md5("$uid$zdate$familysite");
		$UIDS[$uid]=true;
		$f[$uid][]="('$md5','$zdate','$familysite','$size','$hits')";
		if($c>1000){
			events("websites_uid_from_hourtable($tablename,$time):: $c events - $a");
			if(!websites_uid_parse_array($f)){
				if($GLOBALS["VERBOSE"]){echo "websites_uid_parse_array return false in line ".__LINE__."\n";}
				return false;}
				$c=0;
				$f=array();
				continue;
		}

		if(count($f)>500){
			events("websites_uid_from_hourtable($tablename,$time):: $c events - $a");
			if(!websites_uid_parse_array($f)){
				if($GLOBALS["VERBOSE"]){echo "websites_uid_parse_array return false in line ".__LINE__."\n";}
				return false;}
				$f=array();

		}

	}

	if(count($f)>0){
		events("websites_uid_from_hourtable($tablename,$time):: $c events - $a");
		if(!websites_uid_parse_array($f)){
			if($GLOBALS["VERBOSE"]){echo "websites_uid_parse_array return false in line ".__LINE__."\n";}
			return false;}

	}

	if(count($UIDS)>0){
		while (list ($uid, $rows) = each ($UIDS) ){
			websites_uid_not_categorised($uid);
				
		}

	}


	return true;
	if($GLOBALS["VERBOSE"]){echo "return true ".__LINE__."\n";}


}

function websites_uid_parse_array($array){
	$q=new mysql_squid_builder();
	while (list ($uid, $rows) = each ($array) ){
		$uidtable=$q->uid_to_tablename($uid);

		$sql="CREATE TABLE IF NOT EXISTS `www_$uidtable` ( `zmd5` varchar(90)  NOT NULL, `zDate` date  NOT NULL, `size` BIGINT UNSIGNED  NOT NULL, `hits`  BIGINT UNSIGNED  NOT NULL,
		`familysite` varchar(255)  NOT NULL,`category` varchar(255), PRIMARY KEY (`zmd5`),
		KEY `zDate` (`zDate`), KEY `size` (`size`), KEY `hits` (`hits`),
		KEY `familysite` (`familysite`) ,KEY `category` (`category`) )  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql);

		if(!$q->FIELD_EXISTS("www_$uidtable", "category")){
				$q->QUERY_SQL("ALTER TABLE `www_$uidtable` ADD `category` varchar(255), ADD INDEX (`category`)");
				}


				if(!$q->ok){
				if($GLOBALS["VERBOSE"]){echo "$q->mysql_error in line: ".__LINE__."\n";}
						return false;
				}
				$sql="INSERT IGNORE INTO `www_$uidtable` (zmd5,zDate,familysite,size,hits) VALUES ".@implode(',', $rows);
				$q->QUERY_SQL($sql);
				if(!$q->ok){
				if($GLOBALS["VERBOSE"]){echo "$q->mysql_error in line: ".__LINE__."\n";}
				return false;}

}

	return true;

}

function websites_uid_not_categorised($uid=null,$tablename=null,$aspid=false){
	if(isset($GLOBALS["websites_uid_not_categorised_$uid"])){return;}
	$unix=new unix();
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$uid.pid";
	if($aspid){
		$pid=@file_get_contents($pidfile);
		$myfile=basename(__FILE__);
		if($unix->process_exists($pid,$myfile)){
			squid_admin_mysql(1, "Task already running PID: $pid, aborting current task",__FUNCTION__,__FILE__,__LINE__,"stats");
			return;
		}
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);

	$q=new mysql_squid_builder();
	if($uid<>null){
		$uidtable=$q->uid_to_tablename($uid);
		$tablename="www_$uidtable";
	}


	if(!$q->FIELD_EXISTS($tablename, "category")){
		$q->QUERY_SQL("ALTER TABLE `$tablename` ADD `category` varchar(255), ADD INDEX (`category`)");
	}

	$sql="SELECT familysite,`category` FROM `$tablename` GROUP BY familysite,`category` HAVING `category` IS NULL ";

	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){if($GLOBALS["VERBOSE"]){
	echo "############# ERROR #########\n$q->mysql_error\Line:".__LINE__."\n#############\n";}
	return false;
	}


	$c=0;
	$mysql_num_rows=mysqli_num_rows($results);
	if($mysql_num_rows==0){
	if($GLOBALS["VERBOSE"]){ echo "$sql (No rows)\n";}return true;}
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$sitename=$ligne["familysite"];
		$IpClass=new IP();
		if($IpClass->isValid($sitename)){
			if(isset($GLOBALS["IPCACHE"][$sitename])){
				$t=time();
				$sitename=gethostbyaddr($sitename);
				events("$tablename: {$ligne["familysite"]} -> $sitename ". $unix->distanceOfTimeInWords($t,time())." gethostbyaddr() LINE:".__LINE__);
				$GLOBALS["IPCACHE"][$sitename]=$sitename;

			}
		}
		
		
		$category=$q->GET_CATEGORIES($sitename);
		
		
		if($IpClass->isValid($sitename)){
			if($category==null){$category="ipaddr";}
			$q->categorize($sitename, $category);
		}
		events("$tablename: {$ligne["familysite"]} -> $sitename [$category] LINE:".__LINE__);
		
		if(strlen($category)>0){
			$category=mysql_escape_string2($category);
			$ligne["familysite"]=mysql_escape_string2($ligne["familysite"]);
			$sql="UPDATE `$tablename` SET `category`='$category' WHERE familysite='{$ligne["familysite"]}'";
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				squid_admin_mysql(1, "$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"stats");
				return;
			}
		}

	}
}

function events($text){
	if($GLOBALS["VERBOSE"]){echo $text."\n";}
	$common="/var/log/artica-postfix/squid.stats.log";
	$size=@filesize($common);
	if($size>100000){@unlink($common);}
	$pid=getmypid();
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)."$date $text");
	$h = @fopen($common, 'a');
	$sline="[$pid] $text";
	$line="$date [$pid] $text\n";
	@fwrite($h,$line);
	@fclose($h);
}