<?php
$GLOBALS["BYPASS"]=true;$GLOBALS["REBUILD"]=false;$GLOBALS["OLD"]=false;$GLOBALS["FORCE"]=false;
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.mysql.postfix.builder.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');
include_once(dirname(__FILE__).'/ressources/class.mysql.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.familysites.inc');
events("commands= ".implode(" ",$argv));
$POSTFIX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
if($POSTFIX_INSTALLED==0){exit();}

$GLOBALS["CLASS_UNIX"]=new unix();
events("Executed " .@implode(" ",$argv));

if($argv[1]=="--reject"){STATS_CNX_REJECT();return;}
if($argv[1]=="--cnx"){STATS_CNX_ACCEPT();return;}
if($argv[1]=="--week"){STATS_BuildCurrentWeek();return;}
if($argv[1]=="--mins"){STATS_BuildCurrentTable();return;}
if($argv[1]=="--days"){STATS_BuildDayTables();return;}
if($argv[1]=="--month"){STATS_BuildMonthTables();return;}
if($argv[1]=="--hourly-cnx"){STATS_hourly_cnx_to_daily_cnx();return;}



function STATS_BuildDayTables(){
	$unix=new unix();
	$GLOBALS["DAYSTATS"]=0;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		squid_admin_mysql(2, "Already PID $pid {running} {since} {$timepid}mn" , __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return;
	}
	$GLOBALS["Q"]=new mysql_postfix_builder();
	$GLOBALS["Q"]->CheckTables();
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);		
	$t=time();
	TableDays_add_days();
	day_tables();
	
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	squid_admin_mysql(2, "{$GLOBALS["DAYSTATS"]}: day tables generated from hour tables took: $took" , __FUNCTION__, __FILE__, __LINE__, "postfix-stats");

}

function STATS_BuildMonthTables(){
	$unix=new unix();
	$GLOBALS["DAYSTATS"]=0;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		squid_admin_mysql(2, "Already PID $pid {running} {since} {$timepid}mn" , __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return;
	}
	$GLOBALS["Q"]=new mysql_postfix_builder();
	$GLOBALS["Q"]->CheckTables();
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);		
	$t=time();
	month_tables();

	$took=$unix->distanceOfTimeInWords($t,time(),true);
	squid_admin_mysql(2, "Task Month tables from {$GLOBALS["DAYSTATS"]} day tables took: $took" , __FUNCTION__, __FILE__, __LINE__, "postfix-stats");	
	
}

function month_tables(){
	
	$sql="SELECT zDays FROM TableDays WHERE MonthBuilded=0";
	$today=date("Y-m-d");
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	if(mysqli_num_rows($results)==0){return;}
	$c=0;
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($ligne["zDays"]==$today){continue;}
		$TableDay=str_replace("-", "", $ligne["zDays"])."_day";
		$TableMonth=date("Ymd",strtotime($ligne["zDays"]))."_month";
		if(!_month_table($ligne["zDays"])){continue;}
		$GLOBALS["Q"]->QUERY_SQL("UPDATE TableDays SET MonthBuilded=1 WHERE `zDays`='{$ligne["zDays"]}'");
		if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
		$c++;
		if(system_is_overloaded(__FILE__)){squid_admin_mysql(2, "Fatal: {OVERLOADED_SYSTEM} after $c calculated tables, try in next cycle..", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	}
		
	return true;	
	
}
function _month_table($day){
	$TableDay=str_replace("-", "", $day)."_day";
	$TableMonth=date("Ym",strtotime($day))."_month";
	if($GLOBALS["Q"]->TABLE_EXISTS(date("Ymd",strtotime($day))."_month")){$GLOBALS["Q"]->QUERY_SQL("DROP TABLE ".date("Ymd",strtotime($day))."_month");}
	
	$DayNum=date("d",strtotime($day));
	if(!$GLOBALS["Q"]->TABLE_EXISTS($TableDay)){return false;}
	if(!$GLOBALS["Q"]->BuildMonthTable($TableMonth)){
		system_admin_events($GLOBALS["Q"]->mysql_error ." table:$TableMonth", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return false;}
	
	$sql="SELECT SUM(hits) as hits, SUM(size) as mailsize,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,recipienthost,smtpcode
	FROM  $TableDay GROUP BY mailfrom,instancename,mailto,domainfrom,domainto,senderhost,recipienthost,smtpcode";
	
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	
	
	if(mysqli_num_rows($results)==0){
		if($GLOBALS["VERBOSE"]){echo "[$day]: No results...($TableMonth)\n";}return true;}
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$md5=md5(@serialize($ligne));
		$f[]="('$md5','$DayNum','{$ligne["hits"]}','{$ligne["mailsize"]}','{$ligne["mailfrom"]}',
		'{$ligne["instancename"]}','{$ligne["mailto"]}','{$ligne["domainfrom"]}','{$ligne["domainto"]}',
		'{$ligne["senderhost"]}','{$ligne["recipienthost"]}','{$ligne["smtpcode"]}')";
		
		if(count($f)>1500){
			if($GLOBALS["VERBOSE"]){echo "[$day]: Insert...". count($f). " items\n";}	
			$sql="INSERT IGNORE INTO `$TableMonth` (`zmd5`,`zday`,`hits`, `size` ,`mailfrom`,
			  `instancename`, `mailto`, `domainfrom`, `domainto`,`senderhost`,`recipienthost`,
			  `smtpcode`) VALUES ".@implode(",", $f);
			$GLOBALS["Q"]->QUERY_SQL($sql);
			if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
			$f=array();
		}		
		
	}
	
	if(count($f)>0){
		if($GLOBALS["VERBOSE"]){echo "[$day]: Insert...". count($f). " items\n";}
		$sql="INSERT IGNORE INTO `$TableMonth` (`zmd5`,`zday`,`hits`, `size` ,`mailfrom`,
			  `instancename`, `mailto`, `domainfrom`, `domainto`,`senderhost`,`recipienthost`,
			  `smtpcode`) VALUES ".@implode(",", $f);
		$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	}
	$GLOBALS["DAYSTATS"]=$GLOBALS["DAYSTATS"]+1;
	return true;	
	
}



function TableDays_add_days(){
	$GLOBALS["Q"]=new mysql_postfix_builder();
	$tables=$GLOBALS["Q"]->LIST_HOUR_TABLES();
	if(count($tables)==0){return;}
	$GLOBALS["Q"]->CheckTables();
	while (list ($tablename, $date) = each ($tables) ){
		$time=strtotime($date);
		$day=date("Y-m-d",$time);
		$rounded[$day]=true;
	}
	if(count($rounded)==0){return;}
	while (list ($sday, $none) = each ($rounded) ){
		$f[]="('$sday')";
	}
	
	if(count($f)>0){
		$sql="INSERT IGNORE INTO TableDays (zDays) VALUES ".@implode(",", $f);
		$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");}
	}
	
}

function day_tables(){
	
	$sql="SELECT zDays FROM TableDays WHERE DayBuilded=0";
	$today=date("Y-m-d");
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	if(mysqli_num_rows($results)==0){return;}
	$c=0;
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($ligne["zDays"]==$today){continue;}
		if(_day_tables($ligne["zDays"])){
			$tableDest=str_replace("-", "", $ligne["zDays"])."_day";
			$ligne2=mysqli_fetch_array($GLOBALS["Q"]->QUERY_SQL(
				"SELECT SUM( hits ) AS hits, SUM( size ) AS size FROM `$tableDest`")
			);
			$GLOBALS["Q"]->QUERY_SQL("UPDATE TableDays SET DayBuilded=1, 
			`size`='{$ligne2["size"]}',
			`events`='{$ligne2["hits"]}' WHERE `zDays`='{$ligne["zDays"]}'");
			if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
			 
		}
		$c++;
		if(system_is_overloaded(__FILE__)){squid_admin_mysql(2, "Fatal: {OVERLOADED_SYSTEM} after $c calculated tables, try in next cycle..", __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	}
}

function _day_tables($day){
	$tables=$GLOBALS["Q"]->LIST_HOUR_TABLES();
	if(count($tables)==0){return;}
	while (list ($tablename, $date) = each ($tables) ){
		$time=strtotime($date);
		$Dday=date("Y-m-d",$time);
		if($Dday==$day){$nexttables[]=$tablename;}
	}	
	if(count($nexttables)==0){return;}
	
	while (list ($index, $tablename) = each ($nexttables) ){
		if(!_day_tables_inject($tablename,$day)){return false;}

		
	}
	$GLOBALS["DAYSTATS"]=$GLOBALS["DAYSTATS"]+1;
	return true;
}

function _day_tables_inject($sourcetable,$day){
	$tableDest=str_replace("-", "", $day)."_day";
	if(!$GLOBALS["Q"]->BuildDayTable($tableDest)){return false;}
	
	
	$sql="SELECT COUNT(zhour) as hits,zhour,mailfrom,instancename,
	mailto,domainfrom,domainto,senderhost,recipienthost,SUM(mailsize) as mailsize,
	smtpcode FROM $sourcetable 
	GROUP BY zhour,mailfrom,instancename,mailto,domainfrom,domainto,senderhost,recipienthost,smtpcode";	
	
	
	$results=$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	
	
	if(mysqli_num_rows($results)==0){return true;}
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$md5=md5(@serialize($ligne));
		$f[]="('$md5','{$ligne["zhour"]}','{$ligne["hits"]}','{$ligne["mailsize"]}','{$ligne["mailfrom"]}',
		'{$ligne["instancename"]}','{$ligne["mailto"]}','{$ligne["domainfrom"]}','{$ligne["domainto"]}',
		'{$ligne["senderhost"]}','{$ligne["recipienthost"]}','{$ligne["smtpcode"]}')";
		
		if(count($f)>1500){
		$sql="INSERT IGNORE INTO `$tableDest` (`zmd5`,`zhour`,`hits`, `size` ,`mailfrom`,
			  `instancename`, `mailto`, `domainfrom`, `domainto`,`senderhost`,`recipienthost`,
			  `smtpcode`) VALUES ".@implode(",", $f);
			$GLOBALS["Q"]->QUERY_SQL($sql);
			if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
			$f=array();
		}		
		
	}
	
	if(count($f)>0){
		$sql="INSERT IGNORE INTO `$tableDest` (`zmd5`,`zhour`,`hits`, `size` ,`mailfrom`,
			  `instancename`, `mailto`, `domainfrom`, `domainto`,`senderhost`,`recipienthost`,
			  `smtpcode`) VALUES ".@implode(",", $f);
		$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){system_admin_events($GLOBALS["Q"]->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	}
	
	return true;
}


function STATS_hourly_cnx_to_daily_cnx($nopid=false){
	
	$unix=new unix();
	$GLOBALS["DAYSTATS"]=0;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){echo "pidTime: $pidTime\n";}
	if(!$nopid){
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){
			$timepid=$unix->PROCCESS_TIME_MIN($pid);
			squid_admin_mysql(2, "Already PID $pid {running} {since} {$timepid}mn" , __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
			return;
		}
	}
	$TimeF=$unix->file_time_min($pidTime);
	if(!$GLOBALS["FORCE"]){
		if($TimeF<60){return;}
	}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	$q=new mysql_postfix_builder();
	$LIST_POSTFIX_CNX_HOUR_TABLES=$q->LIST_POSTFIX_CNX_HOUR_TABLES();
	if($GLOBALS["VERBOSE"]){echo "LIST_POSTFIX_CNX_HOUR_TABLES = ".count($LIST_POSTFIX_CNX_HOUR_TABLES)."\n";}
	if(count($LIST_POSTFIX_CNX_HOUR_TABLES)>0){
		$currentHour=date("YmdH")."_hcnx";
		while (list ($tablename, $timeEx) = each ($LIST_POSTFIX_CNX_HOUR_TABLES) ){
			if($tablename==$currentHour){continue;}
			$suffix=date("Ymd",strtotime($timeEx));
			$HOUR_FIELD=date("H",strtotime($timeEx));
			if(!$q->postfix_buildday_connections($suffix)){continue;}
			$desttable="{$suffix}_dcnx";
			if($GLOBALS["VERBOSE"]){echo "$tablename -> $desttable\n";}
			if(!_STATS_hourly_cnx_to_daily_cnx($tablename,$desttable,$HOUR_FIELD)){continue;}
			$q->QUERY_SQL("DROP TABLE `$tablename`");
			
		}
	}
	
	$LIST_POSTFIX_CNX_FAILED_HOUR_TABLES=$q->LIST_POSTFIX_CNX_FAILED_HOUR_TABLES();
	if($GLOBALS["VERBOSE"]){echo "LIST_POSTFIX_CNX_FAILED_HOUR_TABLES = ".count($LIST_POSTFIX_CNX_FAILED_HOUR_TABLES)."\n";}
	if(count($LIST_POSTFIX_CNX_FAILED_HOUR_TABLES)>0){
		$currentHour=date("YmdH")."_hfcnx";
		while (list ($tablename, $timeEx) = each ($LIST_POSTFIX_CNX_FAILED_HOUR_TABLES) ){
			if($tablename==$currentHour){continue;}
			$suffix=date("Ymd",strtotime($timeEx));
			$HOUR_FIELD=date("H",strtotime($timeEx));
			if(!$q->postfix_buildday_failed_connections($suffix)){continue;}
			$desttable="{$suffix}_dfcnx";
			if($GLOBALS["VERBOSE"]){echo "$tablename -> $desttable\n";}
			if(!_STATS_hourly_cnx_failed_to_daily_cnx($tablename,$desttable,$HOUR_FIELD)){continue;}
			$q->QUERY_SQL("DROP TABLE `$tablename`");
				
		}
	}	
	
}

function _STATS_hourly_cnx_failed_to_daily_cnx($sourcetable,$desttable,$HOUR_FIELD){
	
	$q=new mysql_postfix_builder();
	$sql="SELECT COUNT(zmd5) as tcount,HOUR(zDate) as thour,hostname,domain,ipaddr,`WHY` 
	FROM $sourcetable GROUP BY thour,hostname,domain,ipaddr,`WHY`";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){system_admin_events($q->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	$prefix="INSERT IGNORE INTO `$desttable` (`zmd5`,`Hour`,`cnx`,`hostname`,`domain`,`ipaddr`,`WHY`) VALUES ";
	if(mysqli_num_rows($results)==0){return true;}
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$md5=md5(serialize($ligne));
		$why=mysql_escape_string2($ligne["WHY"]);
		$f[]="('$md5','{$ligne["thour"]}','{$ligne["tcount"]}','{$ligne["hostname"]}','{$ligne["domain"]}','{$ligne["ipaddr"]}','$why')";
	
		if(count($f)>500){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$q->ok){system_admin_events($q->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return false;}
			$f=array();
		}
	
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){system_admin_events($q->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return false;}
		$f=array();
	}
	return true;
}

function _STATS_hourly_cnx_to_daily_cnx($sourcetable,$desttable,$HOUR_FIELD){
	
	$q=new mysql_postfix_builder();
	$sql="SELECT COUNT(zmd5) as tcount,HOUR(zDate) as thour,hostname,domain,ipaddr FROM $sourcetable GROUP BY thour,hostname,domain,ipaddr ";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){system_admin_events($q->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return;}
	
	$prefix="INSERT IGNORE INTO `$desttable` (`zmd5`,`Hour`,`cnx`,`hostname`,`domain`,`ipaddr`) VALUES ";

	if(mysqli_num_rows($results)==0){return true;}
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$md5=md5(serialize($ligne));
		$f[]="('$md5','{$ligne["thour"]}','{$ligne["tcount"]}','{$ligne["hostname"]}','{$ligne["domain"]}','{$ligne["ipaddr"]}')";
		
		if(count($f)>500){
			$q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$q->ok){system_admin_events($q->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return false;}
			$f=array();
		}
		
	}
	
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){system_admin_events($q->mysql_error, __FUNCTION__, __FILE__, __LINE__, "postfix-stats");return false;}
		$f=array();
	}
	return true;
}

function STATS_CNX_REJECT(){

	echo __FUNCTION__."\n";
	$unix=new unix();
	$Files=$unix->DirFiles("/home/artica/postfix/postfix/logger","^cnx-reject.[0-9]+\.db");

	$current="cnx-reject.".date("YmdHi").".db";

	foreach ($Files as $filename=>$none){
		$c++;
		if($filename==$current){continue;}
		$path="/home/artica/postfix/postfix/logger/$filename";
		if(!preg_match("#^cnx-reject\.[0-9]+\.db$#", $filename,$re)){continue;}
		
		$LOCKFILE="$path.LCK";
		if(isLocked($path)){continue;}
		@unlink($LOCKFILE);
		@file_put_contents($LOCKFILE, getmypid());
		
		if(!STATS_CNX_REJECT_parse($path)){@unlink($LOCKFILE);continue;}
		echo "Remove $path\n";
		@unlink($path);
		@unlink($LOCKFILE);

	}

}


function STATS_CNX_ACCEPT(){
	
	$unix=new unix();
	$Files=$unix->DirFiles("/home/artica/postfix/postfix/logger","^cnx-accept.[0-9]+\.db");
	
	$current="cnx-accept.".date("YmdHi").".db";
	
	foreach ($Files as $filename=>$none){
		$c++;
		if($filename==$current){continue;}
		$path="/home/artica/postfix/postfix/logger/$filename";
		if(!preg_match("#^cnx-accept\.[0-9]+\.db$#", $filename,$re)){continue;}
		
		$LOCKFILE="$path.LCK";
		if(isLocked($path)){continue;}
		@unlink($LOCKFILE);
		@file_put_contents($LOCKFILE, getmypid());
		
		if(!STATS_CNX_ACCEPT_parse($path)){@unlink($LOCKFILE);continue;}
		echo "Remove $path\n";
		@unlink($path);
		@unlink($LOCKFILE);
	
	}
	events("STATS_CNX_REJECT()");
	STATS_CNX_REJECT();
	
}

function STATS_CNX_REJECT_parse($path){
	events("STATS_CNX_REJECT_parse(): Scanning $path");

	if($GLOBALS["VERBOSE"]){echo "Parsing $path\n";}
	$db_con = dba_open($path, "r","db4");
	if(!$db_con){echo "DB open $path failed\n";return false;}
	
	
	
	$fam=new familysite();
	$SQL1=array();
	
	$mainkey=dba_firstkey($db_con);
	
	while($mainkey !=false){
		if($GLOBALS["VERBOSE"]){echo "STATS_CNX_REJECT_parse: FECTH\n";}
		$data=dba_fetch($mainkey,$db_con);
		$ARRAY=unserialize($data);
		
		if(!isset($ARRAY["IPADDR"])){
			if($ARRAY["HOSTNAME"]==null){
				$mainkey=dba_nextkey($db_con);
				continue;}
			
		}
		if($ARRAY["IPADDR"]=="127.0.0.1"){
			$mainkey=dba_nextkey($db_con);
			continue;
		}
		$zmd5=md5($data);
		$error=mysql_escape_string2($ARRAY["error"]);
		if(!isset($ARRAY["DATE"])){$ARRAY["DATE"]=date("Y-m-d H:i:s",$ARRAY["TIME"]);}
		if($ARRAY["DATE"]==null){$ARRAY["DATE"]=date("Y-m-d H:i:s",$ARRAY["TIME"]);}
		
		if($ARRAY["HOSTNAME"]==null){if($ARRAY["IPADDR"]==null){
			$mainkey=dba_nextkey($db_con);
			continue;}
		}
	
		if($ARRAY["HOSTNAME"]==null){$ARRAY["HOSTNAME"]=$fam->GetComputerName($ARRAY["IPADDR"]);}
		$familysite=$fam->GetFamilySites($ARRAY["HOSTNAME"]);
		$SQL1[date("YmdH",$ARRAY["TIME"])][]="('$zmd5','{$ARRAY["DATE"]}','{$ARRAY["HOSTNAME"]}','$familysite','{$ARRAY["IPADDR"]}','$error')";
		if($GLOBALS["VERBOSE"]){echo "('$zmd5','{$ARRAY["DATE"]}','{$ARRAY["HOSTNAME"]}','$familysite','{$ARRAY["IPADDR"]}','$error')\n";}
	
		if($GLOBALS["VERBOSE"]){echo "STATS_CNX_REJECT_parse: NEXT KEY\n";}
		$mainkey=dba_nextkey($db_con);
	}
	if($GLOBALS["VERBOSE"]){echo "Closing $path\n";}
	dba_close($db_con);
	
	if(count($SQL1)>0){
		$q=new mysql_postfix_builder();
		while (list ($TIMESTAMP, $rows) = each ($SQL1) ){
			if(!$q->postfix_buildhour_failed_connections($TIMESTAMP)){
				events("STATS_CNX_REJECT_parse(): postfix_buildhour_failed_connections($TIMESTAMP) return failed");
				return false;
			}
			$sql="INSERT IGNORE INTO {$TIMESTAMP}_hfcnx (`zmd5`,`zDate`,`hostname`,`domain`,`ipaddr`,`WHY`) VALUES ". @implode(",", $rows);
			if($GLOBALS["VERBOSE"]){echo "QUERY_SQL\n";}
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				events("STATS_CNX_REJECT_parse(): $q->mysql_error");
				echo $q->mysql_error;return false;}
		}
	
	
	
	}
	
	events("STATS_CNX_REJECT_parse(): $path Success");
	if($GLOBALS["VERBOSE"]){echo "Parsing $path END\n";}
	return true;	
	
}

function STATS_CNX_ACCEPT_parse($path){
	if($GLOBALS["VERBOSE"]){echo "Parsing $path\n";}
	$db_con = dba_open($path, "r","db4");
	if(!$db_con){echo "DB open $path failed\n";return false;}
	

	
	$fam=new familysite();
	$SQL1=array();

	$mainkey=dba_firstkey($db_con);

	while($mainkey !=false){
		$data=dba_fetch($mainkey,$db_con);
		$ARRAY=unserialize($data);
		if($ARRAY["IPADDR"]=="127.0.0.1"){
			$mainkey=dba_nextkey($db_con);
			continue;
		}
		$zmd5=md5($data);
		
		if($ARRAY["HOSTNAME"]==null){$ARRAY["HOSTNAME"]=$fam->GetComputerName($ARRAY["IPADDR"]);}
		$familysite=$fam->GetFamilySites($ARRAY["HOSTNAME"]);
		$SQL1[date("YmdH",$ARRAY["TIME"])][]="('$zmd5','{$ARRAY["DATE"]}','{$ARRAY["HOSTNAME"]}','$familysite','{$ARRAY["IPADDR"]}')";
		if($GLOBALS["VERBOSE"]){echo "('$zmd5','{$ARRAY["DATE"]}','{$ARRAY["HOSTNAME"]}','$familysite','{$ARRAY["IPADDR"]}')\n";}
		
		$mainkey=dba_nextkey($db_con);
	}
	
	dba_close($db_con);
	
	if(count($SQL1)>0){
		$q=new mysql_postfix_builder();
		while (list ($TIMESTAMP, $rows) = each ($SQL1) ){
			$q->postfix_buildhour_connections($TIMESTAMP);
			$sql="INSERT IGNORE INTO {$TIMESTAMP}_hcnx (`zmd5`,`zDate`,`hostname`,`domain`,`ipaddr`) VALUES ". @implode(",", $rows);
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error;return false;}
		}
		
		
		
	}
	
	
	if($GLOBALS["VERBOSE"]){echo "Parsing $path END\n";}
	return true;

}


function isLocked($path){
	$LOCK_FILE="$path.LCK";
	if(!is_file($LOCK_FILE)){return false;}
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$timeexec=$GLOBALS["CLASS_UNIX"]->file_time_min($LOCK_FILE);
	if($timeexec<5){return true;}


	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($LOCK_FILE);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		return true;
	}
	@unlink($LOCK_FILE);
	return false;
		


}


function STATS_BuildCurrentTable(){
	
	$unix=new unix();
	$maxInstances=3;
	
	$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
		
	
	if(count($pids)>$maxInstances){
		if($GLOBALS["VERBOSE"]){echo count($pids) ." > $maxInstances\n";}
		events(count($pids) ." > $maxInstances, aborting");
		exit();}
	
	
	$pidefile="/etc/artica-postfix/pids/exec.postfix-stats.php.STATS_BuildCurrentTable.pid";
	$pid=$unix->get_pid_from_file($pidefile);
	if($unix->process_exists($pid)){exit();}
	
	file_put_contents($pidefile,$pid);
	$unix=new unix();
	$timefile="/etc/artica-postfix/pids/exec.postfix-stats.php.STATS_BuildCurrentTable.time";
	$timesched=$unix->file_time_min($timefile);
	if($timesched<1){
		events("{$timesched}Mn need at least 1mn");
		return;
	}
		
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$Files=$unix->DirFiles("/home/artica/postfix/postfix/logger","^realtime\.[0-9]+\.db");
	events(count($Files)." realtime files to parse...");
	$current="realtime.".date("YmdHi").".db";
	$c=0;
	foreach ($Files as $filename=>$none){
		$c++;
		if($filename==$current){continue;}
		if(!preg_match("#^realtime\.[0-9]+\.db$#", $filename,$re)){continue;}
		
		$path="/home/artica/postfix/postfix/logger/$filename";
		$LOCKFILE="$path.LCK";
		if(isLocked($path)){continue;}
		@unlink($LOCKFILE);
		@file_put_contents($LOCKFILE, getmypid());
		
		
		if(!STATS_BuildCurrentTable_parse($path)){
			@unlink($LOCKFILE);
			continue;
		}
		echo "Remove $path\n";
		@unlink($path);
		@unlink($LOCKFILE);
		
	}
	
	events("STATS_CNX_ACCEPT()");
	STATS_CNX_ACCEPT();
	events("STATS_BuildCurrentWeek()");
	STATS_BuildCurrentWeek();
	events("STATS_hourly_cnx_to_daily_cnx()");
	STATS_hourly_cnx_to_daily_cnx();
		
}

function STATS_BuildCurrentWeek($nopid=false){
	
	$unix=new unix();
	$timefile="/etc/artica-postfix/pids/exec.postfix-stats.php.STATS_BuildCurrentWeek.time";
	$timesched=$unix->file_time_min($timefile);
	if(!$GLOBALS["FORCE"]){
		if($timesched<15){
			events("STATS_BuildCurrentWeek: {$timesched}Mn, require 15mn");
			return;
		}
	}
	@unlink($timefile);
	@file_put_contents($timefile, time());
	$q=new mysql();
	$path="/home/artica/postfix/WEEK";
	$dbpath="$path/".date("YW").".db";
	$db_con = dba_open($dbpath, "r","db4");
	
	events("STATS_BuildCurrentWeek: Open $dbpath");
	
	if(!$db_con){echo "DB open $path failed\n";return false;}
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE `smtp_logs`","artica_events");
	$mainkey=dba_firstkey($db_con);
	while($mainkey !=false){
		$ARRAY=array();
		$data=dba_fetch($mainkey,$db_con);
		$delivery_success="no";
		$time_connect=null;
		$time_end=null;
		$domain_from=null;
		$domain_to=null;
		$bytes=0;
		$smtp_sender=null;
		$message_id=null;
		$ARRAY=unserialize($data);
		$Country=null;
		
		
		$MESSAGE_ID=$ARRAY["MESSAGE_ID"];
		if($MESSAGE_ID=="NOQUEUE"){$mainkey=dba_nextkey($db_con);continue;}
		
		if(isset($ALREADY[$MESSAGE_ID])){
			$mainkey=dba_nextkey($db_con);continue;
		}
		$ALREADY[$MESSAGE_ID]=true;
		if(isset($ARRAY["RECIPIENT"])){
			if(preg_match("#^(.*?)>#", $ARRAY["RECIPIENT"],$re)){
				$ARRAY["RECIPIENT"]=$re[1];
			}
		}
		
		if(isset($ARRAY["HOSTNAME"])){
			if($ARRAY["HOSTNAME"]=="127.0.0.1"){unset($ARRAY["HOSTNAME"]);}
		}
		if(isset($ARRAY["IPADDR"])){
			if($ARRAY["IPADDR"]=="127.0.0.1"){unset($ARRAY["IPADDR"]);}
		}
		
		
		$postfix_id=$ARRAY["MESSAGE_ID"];
		if(isset($ARRAY["SENT"])){
			if($ARRAY["SENT"]==1){
				if($mainkey==$postfix_id){
				$ARRAY_REMOVE_SENTS[$mainkey]=md5(serialize($ARRAY));
				}
				$delivery_success="yes";
			}
		}
		if(isset($ARRAY["RECIPIENT"])){
			$mailto=$ARRAY["RECIPIENT"];
			if(strpos($mailto, "@")>0){
				$f=explode("@",$mailto);
				$domain_to=$f[1];
			}
		}
		if(isset($ARRAY["SENDER"])){
			$mailfrom=$ARRAY["SENDER"];
			if(strpos($mailfrom, "@")>0){
				$f=explode("@",$mailfrom);
				$domain_from=$f[1];
			}
		
		}
		if(isset($ARRAY["TIME"])){
				$time_start=date("Y-m-d H:i:s",$ARRAY["TIME"]);
				$time_end=$time_start;
				$time_connect=$time_start;
		}
		if(isset($ARRAY["TIME_END"])){
			$time_end=date("Y-m-d H:i:s",$ARRAY["TIME_END"]);
		}
		if(isset($ARRAY["REJECTED"])){
			$ARRAY["REJECTED"]=str_replace('\\', '', $ARRAY["REJECTED"]);
			if(strlen($ARRAY["REJECTED"])>2000){$mainkey=dba_nextkey($db_con);continue;}
			$bounce_error=mysql_escape_string2($ARRAY["REJECTED"]);
		}
		
		if(isset($ARRAY["HOSTNAME"])){
			if(strlen($ARRAY["HOSTNAME"])>2000){$mainkey=dba_nextkey($db_con);continue;}
			$smtp_sender=mysql_escape_string2($ARRAY["HOSTNAME"]);}
		
		if(isset($ARRAY["STATUS"])){
			if(strlen($ARRAY["HOSTNAME"])>2000){$mainkey=dba_nextkey($db_con);continue;}
			$bounce_error=mysql_escape_string2($ARRAY["STATUS"]);
		}
		
		if(preg_match("#lost connection with.+?\[(.+?)\]\s+#",$bounce_error,$re)){
			$bounce_error="lost connection";$delivery_success="no";
			$smtp_sender=$re[1];
		}
		
		if(isset($ARRAY["SIZE"])){
			$bytes=$ARRAY["SIZE"];
		}
		
		if(isset($ARRAY["EMAIL_ID"])){
			$message_id=$ARRAY["EMAIL_ID"];
		}
		
		if($time_connect==null){if($time_start<>null){$time_connect=$time_start;}}
		$mailfrom=mysql_escape_string2($mailfrom);
		$mailto=mysql_escape_string2($mailto);
			
		$lines[]="('$postfix_id','$message_id','$time_connect','$time_end','$delivery_success','$mailfrom','$domain_from','$mailto','$domain_to','$bounce_error','$smtp_sender','$Country','$bytes')";
		if(count($lines)>500){
			
			$mem=round(((memory_get_usage()/1024)/1000),2);
			events("STATS_BuildCurrentWeek: Adding ".count($lines)." lines Memory:$mem MB");
			
			$sql="INSERT IGNORE INTO smtp_logs
			(delivery_id_text,msg_id_text,time_connect,time_sended,
			delivery_success,sender_user,sender_domain,
			delivery_user,delivery_domain,bounce_error,smtp_sender,Country,`bytes`  )
			VALUES ".@implode(",", $lines);
			if($GLOBALS["VERBOSE"]){echo "QUERY -> ".strlen($sql)." bytes\n";}
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){return;}
			if($GLOBALS["VERBOSE"]){
				$mem=round(((memory_get_usage()/1024)/1000),2);
				echo "QUERY -> OK $mem MB\n";}
			$lines=array();
			
			
			
		}
	
		$mainkey=dba_nextkey($db_con);
		}	
		
		events("STATS_BuildCurrentWeek: Close $dbpath");
		@dba_close($db_con);
		
		if(count($lines)==0){return;}
		events("STATS_BuildCurrentWeek: FINALLY Adding ".count($lines)." lines");
		
		$sql="INSERT IGNORE INTO smtp_logs
		(delivery_id_text,msg_id_text,time_connect,time_sended,
		delivery_success,sender_user,sender_domain,
		delivery_user,delivery_domain,bounce_error,smtp_sender,Country,`bytes`  )
		VALUES ".@implode(",", $lines);
		$q->QUERY_SQL($sql,"artica_events");
		
		
if(count($ARRAY_REMOVE_SENTS)>0){
	
	while (list ($key, $newkey) = each ($ARRAY_REMOVE_SENTS) ){
		if(!STATS_BuildCurrentWeek_move($key,$newkey)){continue;}
	}
	
}

}

function STATS_BuildCurrentWeek_move($key,$newkey){
	
	
	$path="/home/artica/postfix/WEEK";
	$dbpath="$path/".date("YW").".db";
	$db_con = dba_open($dbpath, "c","db4");
	if(!$db_con){echo "DB open $path failed\n";return false;}
	
	echo "$key -> $newkey\n";
	$data=dba_fetch($key,$db_con);
	dba_delete($key, $db_con);
	dba_replace($newkey,$data,$db_con);
	@dba_close($db_con);
	
	
}


function STATS_BuildCurrentTable_parse($path){
	if($GLOBALS["VERBOSE"]){echo "Parsing $path\n";}
	$db_con = dba_open($path, "r","db4");
	if(!$db_con){echo "DB open $path failed\n";return false;}

	$mainkey=dba_firstkey($db_con);

	while($mainkey !=false){
		$data=dba_fetch($mainkey,$db_con);
		
		echo $mainkey."\n";
		
		$array=unserialize($data);
		if(is_numeric($array["MESSAGE_ID"])){$mainkey=dba_nextkey($db_con);continue;}
		
		if(!STATS_BuildCurrentTable_parse_WEEK($array)){
			echo "$mainkey failed\n";
			dba_close($db_con);
			return;
		}
		$mainkey=dba_nextkey($db_con);
	}

	dba_close($db_con);
	if($GLOBALS["VERBOSE"]){echo "Parsing $path END\n";}
	return true;

}

function berekley_db_create($db_path){
	if(is_file($db_path)){return true;}
	$db_desttmp = @dba_open($db_path, "c","db4");
	@dba_close($db_desttmp);
	if(is_file($db_path)){return true;}

}

function STATS_BuildCurrentTable_parse_WEEK($ARRAY){
	
	
	$MESSAGE_ID=$ARRAY["MESSAGE_ID"];
	if($MESSAGE_ID=="NOQUEUE"){return true;}
	
	if(isset($array["RECIPIENT"])){
		if(preg_match("#^(.*?)>#", $ARRAY["RECIPIENT"],$re)){
			$ARRAY=$re[1];
		}
	}
	
	if(isset($ARRAY["HOSTNAME"])){
		if($ARRAY["HOSTNAME"]=="127.0.0.1"){unset($ARRAY["HOSTNAME"]);}
	}
	if(isset($ARRAY["IPADDR"])){
		if($ARRAY["IPADDR"]=="127.0.0.1"){unset($ARRAY["IPADDR"]);}
	}	
	
	if(preg_match("#:\s+([A-Z0-9]+)$#", $MESSAGE_ID,$re)){$MESSAGE_ID=$re[1]; }
	if(preg_match("#postfix\/smtp.*?:\s+([0-9]+)#", $MESSAGE_ID,$re)){$MESSAGE_ID=$re[1]; }
	
	
	echo "-- > $MESSAGE_ID\n";
	$path="/home/artica/postfix/WEEK";
	@mkdir($path);
	$dbpath="$path/".date("YW").".db";
	if(!berekley_db_create($dbpath)){return false;}
	
	$db_con = dba_open($dbpath, "w","db4");
	if(!$db_con){echo "DB open $path failed\n";return false;}
	
	if(!dba_exists($MESSAGE_ID,$db_con)){
		dba_replace($MESSAGE_ID,serialize($ARRAY),$db_con);
		@dba_close($db_con);
		return true;
	}
	
	$FETCH=dba_fetch($MESSAGE_ID,$db_con);
	$array_src=unserialize($FETCH);
	
	foreach ($array as $key=>$value){
		if(isset($array_src[$key])){continue;}
		$array_src[$key]=$value;
	}
	dba_replace($MESSAGE_ID,serialize($array_src),$db_con);
	@dba_close($db_con);
	return true;
}



function events($text){
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		if($GLOBALS["VERBOSE"]){echo $text."\n";}
		$common="/var/log/postfix.stats.log";
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