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

$GLOBALS["Q"]=new mysql_squid_builder();
if($argv[1]=="--restart"){restart();exit;}


start();
function start(){
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
		if($timeexec<1440){
			if($GLOBALS["VERBOSE"]){echo "{$timeexec} <>1440...\n";}
			return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	@file_put_contents($timefile, time());
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("tables_day", "DangerousCatz")){
		$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `DangerousCatz` smallint( 1 ) NOT NULL NOT NULL,ADD INDEX ( `DangerousCatz`)");
	}
	
	$sql="SELECT tablename,DATE_FORMAT(zDate,'%Y%m%d') AS suffix
	FROM tables_day WHERE DangerousCatz=0 AND zDate<DATE_SUB(NOW(),INTERVAL 1 DAY) ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){writelogs_squid("Fatal: $q->mysql_error on `tables_day`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	
	$DANGS_CATZ["hacking"]=true;
	$DANGS_CATZ["malware"]=true;
	$DANGS_CATZ["phishing"]=true;
	$DANGS_CATZ["suspicious"]=true;
	$DANGS_CATZ["warez"]=true;
	$DANGS_CATZ["proxy"]=true;
	$DANGS_CATZ["dynamic"]=true;
	
	$POLLUATE_CATZ["publicite"]=true;
	$POLLUATE_CATZ["tracker"]=true;
	$POLLUATE_CATZ["marketingware"]=true;
	$POLLUATE_CATZ["mailing"]=true;
	$POLLUATE_CATZ["spyware"]=true;
	
	$DEVIANT_CATZ["porn"]=true;
	$DEVIANT_CATZ["sect"]=true;
	$DEVIANT_CATZ["religion"]=true;
	$DEVIANT_CATZ["violence"]=true;
	$DEVIANT_CATZ["gamble"]=true;
	$DEVIANT_CATZ["dangerous_material"]=true;
	$DEVIANT_CATZ["weapons"]=true;
	$DEVIANT_CATZ["paytosurf"]=true;
	$DEVIANT_CATZ["terrorism"]=true;
	
	$HEAVY_CATZ["audio-video"]=true;
	$HEAVY_CATZ["movies"]=true;
	$HEAVY_CATZ["webtv"]=true;
	$HEAVY_CATZ["webradio"]=true;
	$HEAVY_CATZ["downloads"]=true;
	$HEAVY_CATZ["music"]=true;
	$HEAVY_CATZ["filehosting"]=true;
	
	
	$NONPROD_CATZ["womanbrand"]=true;
	$NONPROD_CATZ["celebrities"]=true;
	$NONPROD_CATZ["models"]=true;
	$NONPROD_CATZ["hobby/other"]=true;
	$NONPROD_CATZ["society"]=true;
	$NONPROD_CATZ["tobacco"]=true;
	$NONPROD_CATZ["socialnet"]=true;
	$NONPROD_CATZ["sex/lingerie"]=true;
	$NONPROD_CATZ["sexual_education"]=true;
	$NONPROD_CATZ["ringtones"]=true;
	$NONPROD_CATZ["recreation/sports"]=true;
	$NONPROD_CATZ["recreation/nightout"]=true;
	$NONPROD_CATZ["recreation/humor"]=true;
	$NONPROD_CATZ["paytosurf"]=true;
	$NONPROD_CATZ["mixed_adult"]=true;
	$NONPROD_CATZ["hobby/cooking"]=true;
	$NONPROD_CATZ["genealogy"]=true;
	$NONPROD_CATZ["games"]=true;
	$NONPROD_CATZ["dating"]=true;
	$NONPROD_CATZ["chat"]=true;
	$NONPROD_CATZ["astrology"]=true;
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$tablename=$ligne["tablename"];
		$suffix=$ligne["suffix"];
		$sourcetable="{$suffix}_hour";
		if($GLOBALS["VERBOSE"]){echo "Checking $sourcetable\n";}
		if(!$q->TABLE_EXISTS($sourcetable)){
			if($GLOBALS["VERBOSE"]){echo "$sourcetable no such table\n";}
		}
		if(!$q->FIELD_EXISTS($sourcetable, "catfam")){$q->QUERY_SQL("ALTER TABLE $sourcetable ADD `catfam` smallint( 1 ) NOT NULL NOT NULL,ADD INDEX ( `catfam`)");}		
		reset($DANGS_CATZ);
		
		while (list ($cat,$none ) = each ($DANGS_CATZ) ){
			$q->QUERY_SQL("UPDATE $sourcetable SET catfam=1 WHERE category LIKE '%$cat%'");
			
		}
		
		reset($POLLUATE_CATZ);
		while (list ($cat,$none ) = each ($POLLUATE_CATZ) ){
			$q->QUERY_SQL("UPDATE $sourcetable SET catfam=2 WHERE category LIKE '%$cat%'");
				
		}		
		reset($DEVIANT_CATZ);
		while (list ($cat,$none ) = each ($DEVIANT_CATZ) ){
			$q->QUERY_SQL("UPDATE $sourcetable SET catfam=3 WHERE category LIKE '%$cat%'");
		
		}
		reset($HEAVY_CATZ);
		while (list ($cat,$none ) = each ($HEAVY_CATZ) ){
			$q->QUERY_SQL("UPDATE $sourcetable SET catfam=4 WHERE category LIKE '%$cat%'");
		
		}
		reset($NONPROD_CATZ);
		while (list ($cat,$none ) = each ($NONPROD_CATZ) ){
			$q->QUERY_SQL("UPDATE $sourcetable SET catfam=5 WHERE category LIKE '%$cat%'");
		
		}		
		
		$q->QUERY_SQL("UPDATE tables_day SET DangerousCatz=1 WHERE tablename='$tablename'");
		
	}
	MonthCatzFamilies();
	
}

function restart(){
	$q=new mysql_squid_builder();
	$sql="SELECT tablename,DATE_FORMAT(zDate,'%Y%m%d') AS suffix,DATE_FORMAT(zDate,'%Y%m') AS suffixmonth
	FROM tables_day WHERE zDate<DATE_SUB(NOW(),INTERVAL 1 DAY) ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){writelogs_squid("Fatal: $q->mysql_error on `tables_day`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$tablename=$ligne["tablename"];
		$suffix=$ligne["suffix"];
		$sourcetable="{$suffix}_hour";
		$suffixmonth=$ligne["suffixmonth"];
		echo "Patching table $sourcetable\n";
		$q->QUERY_SQL("UPDATE $sourcetable SET catfam=0");
		$q->QUERY_SQL("UPDATE tables_day SET DangerousCatz=0 WHERE tablename='$tablename'");
		$q->QUERY_SQL("UPDATE tables_day SET monthcatfam=0 WHERE tablename='$tablename'");
		$tableblockMonth="{$suffixmonth}_catfam";
		$q->QUERY_SQL("TRUNCATE TABLE `$tableblockMonth`");
	}
	
}

function MonthCatzFamilies(){
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("tables_day", "monthcatfam")){
		$q->QUERY_SQL("ALTER TABLE `tables_day` ADD `monthcatfam` smallint( 1 ) NOT NULL NOT NULL,ADD INDEX ( `monthcatfam`)");
	}
		
	
	$sql="SELECT tablename,DATE_FORMAT(zDate,'%Y%m%d') AS suffix,DATE_FORMAT(zDate,'%Y-%m-%d') AS tday
	FROM tables_day WHERE DangerousCatz=1 AND monthcatfam=0 AND zDate<DATE_SUB(NOW(),INTERVAL 1 DAY) ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){writelogs_squid("Fatal: $q->mysql_error on `tables_day`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$tablename=$ligne["tablename"];
		$suffix=$ligne["suffix"];
		$sourcetable="{$suffix}_hour";
		$xtime=strtotime($ligne["tday"]." 00:00:00");
		if(MonthCatzFamilies_perform($sourcetable,$xtime)){
			$q->QUERY_SQL("UPDATE tables_day SET monthcatfam=1 WHERE tablename='$tablename'");
		}
	}	
	
	
	
}

//sitename     | familysite   | client        | hostname      | account | hour | remote_ip     | MAC               | country | size | hits | uid | category                      | cached | catfam
function MonthCatzFamilies_perform($sourcetable,$xtime){
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($sourcetable)){return true;}
	
	$sql="SELECT SUM(size) as size,SUM(hits) as hits,familysite,client,hostname,MAC,size,hits,uid,catfam FROM `$sourcetable`
		GROUP BY familysite,client,hostname,MAC,size,hits,uid,catfam HAVING catfam>0";
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){writelogs_squid("Fatal: $q->mysql_error on `tables_day`",__FUNCTION__,__FILE__,__LINE__,"stats");return;}
	
	if(!MonthCatzFamiliesBuildTables($xtime)){return;}
	$tableblockMonth=date('Ym',$xtime)."_catfam";
	
	$prefix="INSERT IGNORE INTO `$tableblockMonth` (`zmd5`,`zDate`,`hits`,`size`,`client`,`uid`,`hostname`,`MAC`,`familysite`,`catfam`) VALUES ";
	
	$day=date("Y-m-d",$xtime);
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$zmd5=md5($day.serialize($ligne));
		$f[]="('$zmd5','$day','{$ligne["hits"]}','{$ligne["size"]}','{$ligne["client"]}','{$ligne["uid"]}','{$ligne["hostname"]}','{$ligne["MAC"]}','{$ligne["familysite"]}','{$ligne["catfam"]}')";
		if(count($f)>500){
			if($GLOBALS["VERBOSE"]){echo "$tableblockMonth -> 500\n";}
			$q->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
			if(!$q->ok){return false;}
		}
	}	
	
	if(count($f)>0){
		if($GLOBALS["VERBOSE"]){echo "$tableblockMonth -> ".count($f)."\n";}
		$q->QUERY_SQL($prefix.@implode(",", $f));
		$f=array();
		if(!$q->ok){return false;}
	}

	return true;
}


function MonthCatzFamiliesBuildTables($xtime){
	$q=new mysql_squid_builder();
	$tableblockMonth=date('Ym',$xtime)."_catfam";

$sql="CREATE TABLE IF NOT EXISTS `$tableblockMonth` (
	`zmd5` VARCHAR( 100 ) NOT NULL PRIMARY KEY ,
	
	`zDate` date ,
	`client` VARCHAR( 90 ) NOT NULL ,
	`uid` VARCHAR( 90 ) NOT NULL ,
	`hostname` VARCHAR( 120 ) NOT NULL ,
	`MAC` VARCHAR( 20 ) NOT NULL ,
	`familysite` VARCHAR( 125 ) NOT NULL ,
	`catfam` smallint(1) NOT NULL ,
	`hits` BIGINT( 100 ),
	`size` BIGINT( 100 ),
	KEY `zDate` (`zDate`),
	KEY `hits` (`hits`),
	KEY `size` (`size`),
	KEY `uid` (`uid`),
	KEY `client` (`client`),
	KEY `hostname` (`hostname`),
	KEY `familysite` (`familysite`),
	KEY `catfam` (`catfam`),
	KEY `MAC` (`MAC`)
	)  ENGINE = MYISAM;";
	$q->QUERY_SQL($sql);
	if(!$q->ok){return false;}
	return true;
}

