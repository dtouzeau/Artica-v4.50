<?php
ini_set('memory_limit','1000M');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="vsFTPD Daemon";
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

$GLOBALS["zMD5"]=$argv[1];
BUILD_REPORT($argv[1]);


function build_progress($text,$pourc){
	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/squid.statistics-{$GLOBALS["zMD5"]}.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}





function GRAB_DATAS($ligne,$md5){
	
	
	
	$GLOBALS["zMD5"]=$md5;
	$params=unserialize($ligne["params"]);
	
	while (list($num,$val)=each($params)){
		echo "$num........: $val\n";
	
	}
	
	$influx=new influx();
	$mintime=strtotime("2008-01-01 00:00:00");
	$params["TO"]=intval($params["TO"]);
	$params["FROM"]=abs(intval($params["FROM"]));
	if($params["FROM"]<$mintime){$params["FROM"]=strtotime(date("Y-m-d 00:00:00"));}
	$params["TO"]=intval($params["TO"]);
	if($params["TO"]<$mintime){$params["TO"]=time();}
	
	$influx=new influx();
	$from=$params["FROM"];
	$to=$params["TO"];
	$interval=$params["INTERVAL"];
	$USER_FIELD=strtolower($params["USER"]);
	$SEARCH=$params["SEARCH"];
	
	
	$q=new postgres_sql();
	$q->QUERY_SQL("DROP TABLE \"{$md5}report\"");
	
	
	$sql="CREATE TABLE IF NOT EXISTS \"{$md5}report\"
	(zdate timestamp,
	mac macaddr,
	ipaddr INET,
	userid VARCHAR(64) NULL,
	category VARCHAR(64) NULL,
	familysite VARCHAR(128) NULL,
	size BIGINT,
	rqs BIGINT)";
	
	
	
	$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		echo "********** FAILED **********\n";
		echo $q->mysql_error."\n";
		build_progress("{step} {insert_data}: PostreSQL engine, {failed}",110);
		return false;
	}
	
	$q->QUERY_SQL("create index zdate{$md5}report on \"{$md5}report\"(zdate);");
	$q->QUERY_SQL("create index familysite{$md5}report on \"{$md5}report\"(familysite);");
	$q->QUERY_SQL("create index user{$md5}report on \"{$md5}report\"(ipaddr,userid,familysite);");
	
	$FIELDS["MAC"]="mac";
	$FIELDS["IPADDR"]="ipaddr";
	$FIELDS["USERID"]="userid";
	
	$TimeGroup="zdate";
	$distance=$influx->DistanceHour($from,$to);
	echo "Distance: {$distance} hours\n";
	if($distance>4){
		$TimeGroup="date_trunc('hour', zdate) as zdate";
	}
	
	$NOFILL="userid !='none' AND ";
	
	if($USER_FIELD<>"userid"){$NOFILL=null;}
	if($SEARCH=="none"){$NOFILL=null;}
	
	
	$sqlA[]="SELECT SUM(size) as size, SUM(rqs) as RQS,$TimeGroup,FAMILYSITE, CATEGORY, MAC, IPADDR, USERID FROM access_log";
	$sqlA[]="WHERE $NOFILL $USER_FIELD='$SEARCH' and (zDate >'".date("Y-m-d H:i:s",$from)."'";
	$sqlA[]="and zDate < '".date("Y-m-d H:i:s",$to)."')";
	$sqlA[]="GROUP BY date_trunc('hour', zdate), FAMILYSITE, CATEGORY, MAC, IPADDR, USERID";
	
	
	if($distance>24*5){
		$sqlA=array();
		echo "Distance: {$distance} hours: Use the Month table\n";
		$sqlA[]="SELECT SUM(SIZE) as size, SUM(RQS) as rqs,zdate,familysite, category, mac, ipaddr, userid FROM access_month";
		$sqlA[]="WHERE $NOFILL $USER_FIELD='$SEARCH' and (zDate >='".date("Y-m-d H:i:s",$from)."'";
		$sqlA[]="and zDate <= '".date("Y-m-d H:i:s",$to)."')";
		$sqlA[]="GROUP BY zdate, FAMILYSITE, CATEGORY, MAC, IPADDR, USERID";
		
	}
	
	if($distance>744){
		$sqlA=array();
		echo "Distance: {$distance} hours: Use the Year table\n";
		$sqlA[]="SELECT SUM(SIZE) as size, SUM(RQS) as rqs,zdate,familysite, category, mac, ipaddr, userid FROM access_year";
		$sqlA[]="WHERE $NOFILL $USER_FIELD='$SEARCH' and (zDate >='".date("Y-m-d H:i:s",$from)."'";
		$sqlA[]="and zDate <= '".date("Y-m-d H:i:s",$to)."')";
		$sqlA[]="GROUP BY zdate, FAMILYSITE, CATEGORY, MAC, IPADDR, USERID";
	
	}	
	

	$sql=@implode(" ", $sqlA);
	
	$sql="INSERT INTO \"{$md5}report\" (size,rqs,zdate,familysite, category, mac, ipaddr, userid) $sql";
	
	echo "$sql\n";
	build_progress("{step} {waiting_data}: BigData engine, (websites) {please_wait}",6);
	
	$q->QUERY_SQL($sql);

	

	if(!$q->ok){
		echo "***************\n$postgres->mysql_error\n***************\n";
		$q->QUERY_SQL("DROP TABLE \"{$md5}report\"");
		return false;
	}
	
	$sql="SELECT COUNT(*) AS tcount FROM \"{$md5}report\"";
	$ligne=pg_fetch_assoc($q->QUERY_SQL($sql));
	$total = intval($ligne["tcount"]);
	
	echo "Member $total items inserted to PostGreSQL\n";
	
	if($total==0){
		$q->QUERY_SQL("DROP TABLE \"{$md5}report\"");
		return false;
	}

	return true;
}

function REMOVE_TABLES($md5){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DROP TABLE `tmp_{$md5}user`");
	
}


function BUILD_REPORT($md5){
	
	echo basename(__FILE__)." $md5\n";
	
	build_progress("{building_query}",5);
	$unix=new unix();
	
	$q=new mysql_squid_builder();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM reports_cache WHERE `zmd5`='$md5'"));
	
	$params=unserialize($ligne["params"]);
	$influx=new influx();
	$mintime=strtotime("2008-01-01 00:00:00");$params["TO"]=intval($params["TO"]);$params["FROM"]=abs(intval($params["FROM"]));
	if($params["FROM"]<$mintime){$params["FROM"]=strtotime(date("Y-m-d 00:00:00"));}
	$params["TO"]=intval($params["TO"]);if($params["TO"]<$mintime){$params["TO"]=time();}
	$influx=new influx();
	$from=InfluxQueryFromUTC($params["FROM"]);
	$to=InfluxQueryFromUTC($params["TO"]);
	$interval=$params["INTERVAL"];
	$md5_table=$md5;
	$searchsites_sql=null;
	
	$USER_FIELD=$params["USER"];
	$SEARCH=$params["SEARCH"];
	

	
	
	if(!GRAB_DATAS($ligne,$md5)){
		build_progress("{unable_to_query_to_bigdata}",110);
		return;
	}
	
	$q=new postgres_sql();
	$q->QUERY_SQL("COPY (SELECT * from \"{$md5}report\") To '/tmp/{$md5}report.csv' with CSV HEADER;");
	$values_size=@filesize("/tmp/{$md5}report.csv");
	$values=mysql_escape_string2(@file_get_contents("/tmp/{$md5}report.csv"));
	echo "MD5:{$GLOBALS["zMD5"]} {$values_size}Bytes ". FormatBytes($values_size/1024)."\n";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE reports_cache SET `builded`=1,`values`='$values',`values_size`='$values_size' WHERE `zmd5`='{$GLOBALS["zMD5"]}'");
	
	if(!$q->ok){
	echo $q->mysql_error."\n";
		build_progress("PostGreSQL {failed}",110);
		return;
	}
	
	build_progress("{success}",100);
	


}