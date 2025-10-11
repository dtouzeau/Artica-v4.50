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

if($argv[1]=="--tests"){tests();exit;}

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

function tests(){
	$influx=new influx();
	$sql="SELECT SUM(SIZE) as size FROM MAIN_SIZE WHERE time > 1434913322s GROUP BY time(10m) ORDER BY ASC";
	$influx->debug=true;
	$main=$influx->QUERY_SQL($sql);
	

	
	
	
}



function GRAB_DATAS($ligne,$md5){
	$GLOBALS["zMD5"]=$md5;
	$params=unserialize($ligne["params"]);
	print_r($params);
	$influx=new influx();
	$mintime=strtotime("2008-01-01 00:00:00");$params["TO"]=intval($params["TO"]);$params["FROM"]=abs(intval($params["FROM"]));
	if($params["FROM"]<$mintime){$params["FROM"]=strtotime(date("Y-m-d 00:00:00"));}
	$params["TO"]=intval($params["TO"]);if($params["TO"]<$mintime){$params["TO"]=time();}
	$influx=new influx();
	$from=$params["FROM"];
	$to=$params["TO"];
	$interval=$params["INTERVAL"];
	$IP1=$params["IP1"];
	$IP2=$params["IP2"];
	$PORT2=$params["PORT2"];
	$md5_table=md5(__FUNCTION__."."."$from$to$IP1$IP2$PORT2");

	if($IP1=="*"){$IP1=null;}
	if($IP2=="*"){$IP2=null;}
	if($PORT2=="*"){$PORT2=null;}
	
	$distance=$influx->DistanceHour($from,$to);
	echo "Distance...: {$distance} hours\n";
	echo "ipaddr src.: {$IP1}\n";
	echo "ipaddr dst.: {$IP2}\n";
	echo "Listen port: {$PORT2}\n";
	$TimeGroup="date_trunc('hour', zdate) as zdate";
	


	if($IP1<>null){
		$ip=new IP();
		$operator="=";
		if(substr($IP1, 0,1)==">"){$operator="<=";$IP1=substr($IP1, 1,strlen($IP1));}
		if(substr($IP1, 0,1)=="<"){$operator=">=";$IP1=substr($IP1, 1,strlen($IP1));}
		
		if(preg_match("#[0-9\.]+\/[0-9]+#", $IP1)){
			$SSEARCH[]=" ( inet '$IP1' >> ip1 ) ";
		}
		if(preg_match("#^[0-9\.]+$#", $IP1)){
			$SSEARCH[]=" ( inet '$IP1' {$operator} ip1 ) ";
		}
	}	
	if($IP2<>null){
		$ip=new IP();
		$operator="=";
		if(substr($IP2, 0,1)==">"){$operator="<=";$IP2=substr($IP2, 1,strlen($IP2));}
		if(substr($IP2, 0,1)=="<"){$operator=">=";$IP2=substr($IP2, 1,strlen($IP2));}
	
		if(preg_match("#[0-9\.]+\/[0-9]+#", $IP2)){
			$SSEARCH[]=" ( inet '$IP2' >> ip2 ) ";
		}
		if(preg_match("#^[0-9\.]+$#", $IP2)){
			$SSEARCH[]=" ( inet '$IP2' {$operator} ip2 ) ";
		}
	}

	if($PORT2<>null){
		$operator="=";
		if(substr($PORT2, 0,1)==">"){$operator="<=";$PORT2=substr($PORT2, 1,strlen($PORT2));}
		if(substr($PORT2, 0,1)=="<"){$operator=">=";$PORT2=substr($PORT2, 1,strlen($PORT2));}
		
		if(strpos($PORT2, ",")>0){
			$tt=explode(",",$PORT2);
			foreach ($tt as $port){
				if(substr($port, 0,1)==">"){$operator="<=";$port=substr($port, 1,strlen($port));}
				if(substr($port, 0,1)=="<"){$operator=">=";$port=substr($port, 1,strlen($port));}
				$sz[]="ip2port$operator$port";
			
			}
			$SSEARCH[]=" ( ".@implode(" OR ", $sz)." ) ";
		}else{
			$SSEARCH[]=" ip2port$operator$port ";
		}
		
	}
	
	if(count($SSEARCH)>0){
		$SEARCHTEXT=@implode(" AND ", $SSEARCH)." AND";
		
	}

	
	
	//to_timestamp(floor((extract('epoch' from zdate) / 600 )) * 600)  AT TIME ZONE 'UTC' as interval_alias

	
	
	$SQLA[]="SELECT SUM(ip1bytes) as ip1bytes, SUM(ip2bytes) as ip2bytes,ip1,ip2,ip2port,protocol,";
	
	if($interval=="10m"){
		$SQLA[]="to_timestamp(floor((extract('epoch' from constartdate) / 600 )) * 600)  AT TIME ZONE 'UTC' as constartdate";
	}
	
	if($interval=="1h"){
		$SQLA[]="date_trunc('hour', constartdate) as constartdate";
	}
	
	$table_source="ipaudit";
	$FromTime=date("Y-m-d H:i:s",$from);
	$ToTime=date("Y-m-d H:i:s",$to);
	
	if($interval=="1d"){
		$table_source="ipaudit_days";
		$FromTime=date("Y-m-d 00:00:00",$from);
		$ToTime=date("Y-m-d 00:00:00",$to);
	}
	$SQLA[]="FROM $table_source";
	$SQLA[]="WHERE";
	$SQLA[]="$SEARCHTEXT (constartdate >='$FromTime' and constartdate <= '$ToTime')";

	$SQLA[]="GROUP BY ip1,ip2,ip2port,protocol,";
	
	if($interval=="10m"){
		$SQLA[]="to_timestamp(floor((extract('epoch' from constartdate) / 600 )) * 600)  AT TIME ZONE 'UTC'";
	}
	
	if($interval=="1h"){
		$SQLA[]="date_trunc('hour', constartdate)";
	}
	
	if($interval=="1d"){
		$SQLA[]="constartdate";
	}
	
	build_progress("{step} {waiting_data}: BigData engine, (Ip-Audit) {please_wait}",6);
	$unix=new unix();
	$hostname=$unix->hostname_g();
	
	
		
	$sql="CREATE TABLE IF NOT EXISTS \"{$md5}report\" (
	ip1 inet,
	ip2 inet,
	protocol BIGINT DEFAULT '0' NOT NULL,
	ip2port INT DEFAULT '0' NOT NULL,
	ip1bytes BIGINT DEFAULT '0' NOT NULL,
	ip2bytes BIGINT DEFAULT '0' NOT NULL,
	constartdate timestamp NOT NULL)";
	
	$q=new postgres_sql();
	$q->QUERY_SQL("DROP TABLE \"{$md5}report\" ");
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "***************\n$q->mysql_error\n***************\n";
		return false;
	}
	
	
	$q->QUERY_SQL("create index constartdate{$md5}report on \"{$md5}report\"(constartdate);");
	$q->QUERY_SQL("create index ip1{$md5}report on \"{$md5}report\"(ip1);");
	$q->QUERY_SQL("create index ip2{$md5}report on \"{$md5}report\"(ip2);");
	$q->QUERY_SQL("create index protocol{$md5}report on \"{$md5}report\"(protocol);");
	$q->QUERY_SQL("TRUNCATE TABLE \"{$md5}report\"");
	
	$sql=@implode(" ", $SQLA);
	$sql="INSERT INTO \"{$md5}report\" (ip1bytes,ip2bytes,ip1,ip2,ip2port,protocol,constartdate) $sql";
	
	echo "***************\n$sql\n*****************\n";
	$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		echo "***************\nERROR $q->mysql_error\n***************\n";
		$q->QUERY_SQL("DROP TABLE \"{$md5}report\"");
		return false;
	}
	
	$ligne=pg_fetch_assoc($q->QUERY_SQL("SELECT COUNT(*) as tcount FROM \"{$md5}report\""));
	
	if(!$q->ok){
		echo "***************\nERROR $q->mysql_error\n***************\n";
		$q->QUERY_SQL("DROP TABLE \"{$md5}report\"");
		return false;
	}
	
	
	$c=$ligne["tcount"];
	if($c==0){
		echo "No data....\n";
		$q->QUERY_SQL("DROP TABLE \"{$md5}report\"");
		return false;
	}
	
	
	

	echo "$c items inserted to PostgreSQL\n";
	
	$MAIN_ARRAY=array();
	return true;
}



function BUILD_REPORT($md5){
	build_progress("{building_query}",5);
	$unix=new unix();
	
	$q=new mysql_squid_builder();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM reports_cache WHERE `zmd5`='$md5'"));
	$influx=new influx();
	$md5_table=$md5;
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
		build_progress("PostGreSQL {failed} while injecting report",110);
		return;
	}
	
	build_progress("{success}",100);

}
