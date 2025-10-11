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
	$influx=new influx();
	if(!is_numeric($params["TO"])){$params["TO"]=strtotime($params["TO"]);}
	if(!is_numeric($params["FROM"])){$params["FROM"]=strtotime($params["FROM"]);}
	
	$mintime=strtotime("2008-01-01 00:00:00");$params["TO"]=intval($params["TO"]);$params["FROM"]=abs(intval($params["FROM"]));
	if($params["FROM"]<$mintime){$params["FROM"]=strtotime(date("Y-m-d 00:00:00"));}
	$params["TO"]=intval($params["TO"]);if($params["TO"]<$mintime){$params["TO"]=time();}
	$influx=new influx();
	
	
	$to=$params["TO"];
	$from=$params["FROM"];
	$interval=$params["INTERVAL"];
	$user=$params["USER"];
	$md5_table="{$md5}report";
	$search=$params["SEARCH"];
	$USER_FIELD=trim(strtolower($params["USER"]));
	if($USER_FIELD==null){$USER_FIELD="all";}
	$CHRONOLOGY=intval($params["CRONOLOGY"]);
	echo "Extract: FROM $from to $to $interval user:$USER_FIELD (".strlen($USER_FIELD).") $search\n";
	
	if($search=="*"){$search=null;}
	if($search<>null){
		$search=str_replace("*", ".*", $search);
		$search=str_replace("|","','",$search);
		$SSEARCH=" $USER_FIELD IN('$search') AND ";
	}
	
	if($USER_FIELD=="userid" and $search==null){$SSEARCH="$USER_FIELD <>'' AND ";}
	
	if($USER_FIELD=="ipaddr"){
		$ip=new IP();
		
		$operator=null;
		if(substr($search, 0,1)==">"){$operator="<";$search=substr($search, 1,strlen($search));}
		if(substr($search, 0,1)=="<"){$operator=">";$search=substr($search, 1,strlen($search));}
		
		if(preg_match("#[0-9\.]+\/[0-9]+#", $search)){
			$SSEARCH=" ( inet '$search' >> $USER_FIELD) AND ";
		}
		if(preg_match("#^[0-9\.]+$#", $search)){
			$SSEARCH=" ( inet '$search' {$operator}= $USER_FIELD) AND ";
		}
	}

	$FilterDate="(zdate >='".date("Y-m-d H:i:s",$from)."' and zdate <= '".date("Y-m-d H:i:s",$to)."')";
	
	if($USER_FIELD<>"all"){
		if($USER_FIELD<>null){$USER_FIELD=", $USER_FIELD";}
	}
	

	
	
	$sql="SELECT SUM(size) as size,sum(rqs) as rqs,zdate,sitename,category,familysite$USER_FIELD FROM access_log WHERE $SSEARCH $FilterDate GROUP BY zdate,sitename,category,familysite$USER_FIELD";
	
	if($CHRONOLOGY==1){
		$FIELDS[]="zdate";
	}
	
	$FIELDS[]="sitename";
	$FIELDS[]="familysite";
	$FIELDS[]="category";
	
	
	if($USER_FIELD=="all"){
		$FIELDS[]="userid";
		$FIELDS[]="ipaddr";
		$FIELDS[]="mac";
		
	}
	
	$fields_line=@implode(",", $FIELDS);
	$sql="SELECT SUM(size) as size,sum(rqs) as rqs,$fields_line FROM access_log WHERE $SSEARCH $FilterDate GROUP BY $fields_line";
	

	@mkdir("/home/squid/statistics-extractor",0777,true);
	@chmod("/home/squid/statistics-extractor", 0777);
	$sql="COPY ($sql) TO '/home/squid/statistics-extractor/{$GLOBALS["zMD5"]}.csv'  WITH (DELIMITER ';',FORMAT CSV, HEADER TRUE, FORCE_QUOTE *);";
	echo "$sql\n";
	build_progress("{step} {waiting_data}: BigData engine, (extract) {please_wait}",6);
	
	
	$q=new mysql_squid_builder();
	
	
	if(is_file("/home/squid/statistics-extractor/{$GLOBALS["zMD5"]}.csv")){
		@unlink("/home/squid/statistics-extractor/{$GLOBALS["zMD5"]}.csv");
		$q->QUERY_SQL("UPDATE reports_cache SET `builded`=1,`values`='',`values_size`='0' WHERE `zmd5`='{$GLOBALS["zMD5"]}'");
	}
	
	$postgres=new postgres_sql();
	
	$results=$postgres->QUERY_SQL($sql);
	if(!$postgres->ok){
		echo "ERROR.....\n";
		echo "***************\n$postgres->mysql_error\n***************\n";
		return false;
	}
	
	$size=@filesize("/home/squid/statistics-extractor/{$GLOBALS["zMD5"]}.csv");
	echo "$size bytes\n";
	
	
	$q->QUERY_SQL("UPDATE reports_cache SET `builded`=1,`values`='',`values_size`='$size' WHERE `zmd5`='{$GLOBALS["zMD5"]}'");
	if(!$q->ok){
		@unlink("/home/squid/statistics-extractor/{$GLOBALS["zMD5"]}.csv");
		echo $q->mysql_error."\n";
		return false;
	}
	
	build_progress("{success}",100);
	return true;
}

function BUILD_REPORT($md5){
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
	$to=InfluxQueryFromUTC($params["TO"]);
	$from=InfluxQueryFromUTC($params["FROM"]);
	$interval=$params["INTERVAL"];
	$user=$params["USER"];
	$md5_table=$md5;
	if(!GRAB_DATAS($ligne,$md5)){
		build_progress("{unable_to_query_to_bigdata}",110);
		return false;
	}
	



	
	
	

	
}




