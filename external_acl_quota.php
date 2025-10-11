#!/usr/bin/php -q
<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
//ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
error_reporting(0);
$GLOBALS["SplashScreenURI"]=null;
$GLOBALS["PID"]=getmypid();
$GLOBALS["STARTIME"]=time();
$GLOBALS["MACTUIDONLY"]=false;
$GLOBALS["uriToHost"]=array();
$GLOBALS["SESSION_TIME"]=array();
$GLOBALS["SQLCACHE"]["TIME"]=time();
$GLOBALS["DEBUG_LEVEL"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidQuotaDebug");
$GLOBALS["PARAMS"]=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidQuotasParams")));
if(!isset($GLOBALS["PARAMS"]["CACHE_TIME"])){$GLOBALS["PARAMS"]["CACHE_TIME"]=360;}
if(!is_numeric($GLOBALS["PARAMS"]["CACHE_TIME"])){$GLOBALS["PARAMS"]["CACHE_TIME"]=360;}
if(!is_numeric( $GLOBALS["DEBUG_LEVEL"])){ $GLOBALS["DEBUG_LEVEL"]=0;}
//$GLOBALS["DEBUG_LEVEL"]=2;
if($GLOBALS["DEBUG_LEVEL"]>0){WLOG("Initialize library");}
$GLOBALS["Q"]=new mysql_squid_builder();
$GLOBALS["F"] = @fopen("/var/log/squid/external-acl-quota.log", 'a');
$max_execution_time=ini_get('max_execution_time'); 
  
if($GLOBALS["DEBUG_LEVEL"]>0){WLOG("Loading database...");}
if(!is_file("/etc/squid3/quotas_artica.db")){WLOG("/etc/squid3/quotas_artica.db no such file");}
$GLOBALS["QUOTAS_DB"]=unserialize(@file_get_contents("/etc/squid3/quotas_artica.db"));


WLOG("Starting... v1.1 Log level:{$GLOBALS["DEBUG_LEVEL"]}; max_execution_time:$max_execution_time argv[1]={$argv[1]} session-time={$GLOBALS["SESSION_TIME"]}");
WLOG(count($GLOBALS["QUOTAS_DB"])." rules...");
if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Starting loop...");}
  
  
while (!feof(STDIN)) {
	 $url = trim(fgets(STDIN));
	 
	 if($url<>null){
	 	
	 	if(count( $GLOBALS["QUOTAS_DB"])==0){
	 		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("QUOTAS_DB -> No rule, aborting...");}
	 		fwrite(STDOUT, "OK\n");
	 		continue;
	 	}
	 	
	 	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG($url);}
	 	$array=parseURL($url);
	 	if($GLOBALS["DEBUG_LEVEL"]>1){
	 		WLOG($url." str:".strlen($url)." bytes LOGIN:{$array["LOGIN"]},IPADDR:{$array["IPADDR"]} MAC:{$array["MAC"]} HOST:{$array["HOST"]} RHOST:{$array["RHOST"]}");
	 	}
	 	
		if($array["IPADDR"]=="127.0.0.1"){
			if(trim($array["LOGIN"])==null){
		 		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("127.0.0.1 return always true...");}
		 		fwrite(STDOUT, "OK\n");
		 		continue;
			}
		 }
	 	
	 	if(!CheckQuota($array)){
	 		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Out Of Quota");}
			fwrite(STDOUT, "ERR message=\"Out Of Quota\"\n");
	 		continue;
	 	}
	 	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("OK");}
	 	fwrite(STDOUT, "OK\n");
	 	continue;
	}
	
	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("...");}
	
}


CleanSessions();
$distanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
$distanceInMinutes = round($distanceInSeconds / 60);
WLOG("v2013-28-09:". basename(__FILE__)." die after ({$distanceInSeconds}s/about {$distanceInMinutes}mn)");
if(isset($GLOBALS["F"])){@fclose($GLOBALS["F"]);}


function CleanSessions(){
	if(!isset($GLOBALS["SESSIONS"])){return;}
	if(!is_array($GLOBALS["SESSIONS"])){return;}
	$cachesSessions=unserialize(@file_get_contents("/etc/squid3/session.cache"));
	if(isset($cachesSessions)){
		if(is_array($cachesSessions)){
			while (list ($md5, $array) = each ($cachesSessions)){$GLOBALS["SESSIONS"][$md5]=$array;}
		}
	}
	@file_put_contents("/etc/squid3/session.cache", serialize($GLOBALS["SESSIONS"]));
}

function parseURL($url){
	$uri=null;
	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("parseURL():: Analyze [$url]");}
	$md5=md5($url);
	
	$t=explode(" ",$url);
	while (list ($index, $value) = each ($t)){
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("parseURL():: $md5: t[$index]=$value");}
			$value=trim($value);
			if(preg_match("#^\%[0-9]+#", trim($value))){$t[$index]=null;}
			if($value=="-"){$t[$index]=null;}
			
	}
	$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=$t[0];
	$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$t[1];
	$GLOBALS["CACHE_URI"][$md5]["MAC"]=$t[2];
	$GLOBALS["CACHE_URI"][$md5]["FORWARDED"]=$t[3];
	$GLOBALS["CACHE_URI"][$md5]["RHOST"]=$t[4];
	
	
	
	
	if($GLOBALS["CACHE_URI"][$md5]["MAC"]=="00:00:00:00:00:00"){
		$GLOBALS["CACHE_URI"][$md5]["MAC"]=null;
	}
	
	
	if(preg_match("#^www\.(.+)$#", $GLOBALS["CACHE_URI"][$md5]["RHOST"],$re)){
		$GLOBALS["CACHE_URI"][$md5]["RHOST"]=$re[1];
	}
	if(preg_match("#(.+?):[0-9]+#", $GLOBALS["CACHE_URI"][$md5]["RHOST"],$re)){
		$GLOBALS["CACHE_URI"][$md5]["RHOST"]=$re[1];
	}
	
	if(trim($GLOBALS["CACHE_URI"][$md5]["FORWARDED"])<>null){
		$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$GLOBALS["CACHE_URI"][$md5]["FORWARDED"];
	}
	
	if($GLOBALS["CACHE_URI"][$md5]["LOGIN"]==null){
		if($GLOBALS["CACHE_URI"][$md5]["MAC"]<>null){
			$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=GetMacToUid($GLOBALS["CACHE_URI"][$md5]["MAC"]);
		}
	}
	if($GLOBALS["CACHE_URI"][$md5]["LOGIN"]==null){
		if($GLOBALS["CACHE_URI"][$md5]["IPADDR"]<>null){
			$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=GetMacToUid($GLOBALS["CACHE_URI"][$md5]["IPADDR"]);
		}
	}		
	
	// %25 192.168.1.209 %25 - www.google-analytics.com
	// 10.0.0.32 00:1e:8c:a5:39:19 - crash-
	// 10.0.0.76 00:25:22:73:31:d5 -
	// 10.0.0.60 00:1d:92:70:96:70 - fbexternal-a.akamaihd.net:443
	
	
	return $GLOBALS["CACHE_URI"][$md5];
	
	
}

function GetMacToUid($mac){
	if($mac==null){return;}
	
	$uid=$GLOBALS["Q"]->MacToUid($mac);
	if($uid<>null){return $uid;}
	
	$uid=$GLOBALS["Q"]->IpToUid($mac);
	if($uid<>null){return $uid;}
	
	
	if(isset($GLOBALS["GetMacToUidMD5"])){
			$md5file=md5_file("/etc/squid3/MacToUid.ini");
			if($md5file<>$GLOBALS["GetMacToUidMD5"]){
				unset($GLOBALS["GetMacToUid"]);
			}
	}
	if(isset($GLOBALS["GetMacToUid"])){
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("MEM: $mac =`{$GLOBALS["GetMacToUid"][$mac]}`");}
		if(isset($GLOBALS["GetMacToUid"][$mac])){
				return $GLOBALS["GetMacToUid"][$mac];
			}
		return;
	}
	
	$GLOBALS["GetMacToUid"]=unserialize(@file_get_contents("/etc/squid3/MacToUid.ini"));
	$GLOBALS["GetMacToUidMD5"]=md5_file("/etc/squid3/MacToUid.ini");
	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("DISK: $mac =`{$GLOBALS["GetMacToUid"][$mac]}`");}
	if(isset($GLOBALS["GetMacToUid"][$mac])){return $GLOBALS["GetMacToUid"][$mac];}
}




function CheckQuota($CPINFOS){
	$RULES=$GLOBALS["QUOTAS_DB"];
	$RHOST=$CPINFOS["RHOST"];
	if(!is_array($RULES)){
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("CheckQuota() no  rule, assume true");}
		return true;}
	if(count($RULES)==0){
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("CheckQuota() no  rule, assume true");}
		return true;}
	
	
	
	while (list ($xtype, $array) = each ($RULES)){
		$PATTERN=$array["PATTERN"];
		$DESTINATIONS=$array["DESTINATIONS"];
		$DUR=$array["DUR"];
		$MAX=$array["MAX"];
		
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("CheckQuota() $RHOST -> $xtype $PATTERN [duration:$DUR | max:{$MAX}MB]");}
		
		if(!sources_matches($xtype,$PATTERN,$CPINFOS)){continue;}
		if(!destination_matches($DESTINATIONS,$CPINFOS,$DUR,$MAX,$xtype)){continue;}
		WLOG("CheckQuota() *** Out of Quota ***  $xtype $PATTERN [duration:$DUR | max:{$MAX}MB]");
		return false;
		
	}

return true;
	
	
}

function destination_matches($DESTINATIONS,$CPINFOS,$DUR,$MAX,$SourceType){
	
	$RHOST=$CPINFOS["RHOST"];
	
	
	if(count($DESTINATIONS)==0){
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("destination_matches() no destinations rules assume * ALL *");}
		$CheckQuotaSQL=CheckQuotaSQL($SourceType,$DUR,$MAX,null,$CPINFOS);
		return $CheckQuotaSQL;
	}

	if(!is_array($DESTINATIONS)){
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("destination_matches() Not an array rules assume * ALL *");}
		$CheckQuotaSQL=CheckQuotaSQL($SourceType,$DUR,$MAX,null,$CPINFOS);
		return $CheckQuotaSQL;
	}
	
	while (list ($destination_type, $array) = each ($DESTINATIONS)){
		if(count($array)==0){
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("destination_matches() verify type: $destination_type No items -> FALSE");}
			return false;
		}
		
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("destination_matches() verify type: $destination_type ".count($array)." items");}
		
		if($destination_type=="dstdomain"){
			while (list ($hostw, $nothing) = each ($array)){
				if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("destination_matches() verify $hostw against $RHOST");}
				$hostwEX=str_replace(".", "\.", $hostw);
				$hostwEX=str_replace("*", ".*?", $hostwEX);
				if(preg_match("#$hostwEX#", $RHOST)){
					if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("$hostwEX matches $RHOST ->Type=$SourceType MySQL type = $DUR {$MAX}MB");}
					$CheckQuotaSQL=CheckQuotaSQL($SourceType,$DUR,$MAX,$hostw,$CPINFOS);
					if($CheckQuotaSQL){return true;}
				}else{
					if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("#$hostwEX# -> $RHOST in line ".__LINE__);}
				}
			}
		}
		
		
	}
	
	return false;
		
}

function CheckQuotaSQL($SourceType,$DUR,$MAX,$hostw=null,$CPINFOS){
	
	
	$table="quotaday_".date("Ymd");
	$tableHour="quotahours_".date("YmdH");
	
	
	if($SourceType=="ipaddr"){$FROM=" WHERE ipaddr='{$CPINFOS["IPADDR"]}'";}
	if($SourceType=="uid"){$FROM=" WHERE uid='{$CPINFOS["LOGIN"]}'";}
	if($SourceType=="MAC"){$FROM=" WHERE MAC='{$CPINFOS["MAC"]}'";}
	if($SourceType=="uidAD"){$FROM=" WHERE uid='{$CPINFOS["LOGIN"]}'";}
	
	$TO=null;
	
	if($hostw<>null){
		$family=$GLOBALS["Q"]->GetFamilySitestt($hostw);
		$TO=" AND servername='$hostw'";
		if($hostw==$family){$TO=" AND familysite='$hostw'";}
	}
	
	if($DUR==2){
		$sql="SELECT SUM(size) as size FROM $tableHour $FROM$TO";
		$MDKEY=md5($sql);
		$Cache=CheckQuotaSQL_cache($MDKEY);
		if($Cache==0){
			$ligne=mysqli_fetch_array($GLOBALS["Q"]->QUERY_SQL($sql));
			if(!$GLOBALS["Q"]->ok){WLOG("$sql = {$GLOBALS["Q"]->mysql_error}");}
			$size=$ligne["size"];
			$size=$size/1024;
			$size=$size/1024;
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("$sql = {$size}/MB {$MAX}MB");}
			$GLOBALS["SQLCACHE"][$MDKEY]["TIME"]=time();
			$GLOBALS["SQLCACHE"][$MDKEY]["VALUE"]=$size;
			$Cache=$GLOBALS["SQLCACHE"][$MDKEY]["VALUE"];
		}
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Hourly: {$Cache}MB <> {$MAX}MB");}
		if($Cache>$MAX){return true;}
		
		return false;		
	}
	

	if($DUR==1){
		$sql="SELECT SUM(size) as size FROM $table $FROM$TO";
		$MDKEY=md5($sql);
		$Cache=CheckQuotaSQL_cache($MDKEY);
		if($Cache==0){
			$ligne=mysqli_fetch_array($GLOBALS["Q"]->QUERY_SQL($sql));
			if(!$GLOBALS["Q"]->ok){WLOG("$sql = {$GLOBALS["Q"]->mysql_error}");}
			$size=$ligne["size"];
			$size=$size/1024;
			$size=$size/1024;
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("$sql = {$size}/MB {$MAX}MB");}
			$GLOBALS["SQLCACHE"][$MDKEY]["TIME"]=time();
			$GLOBALS["SQLCACHE"][$MDKEY]["VALUE"]=$size;
			$Cache=$GLOBALS["SQLCACHE"][$MDKEY]["VALUE"];
		}
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Daily: {$Cache}MB <> {$MAX}MB");}
		if($Cache>$MAX){return true;}
		
		$sql="SELECT SUM(size) as size FROM $tableHour $FROM$TO";
		$MDKEY=md5($sql);
		$Cache=CheckQuotaSQL_cache($MDKEY);
		if($Cache==0){
			$ligne=mysqli_fetch_array($GLOBALS["Q"]->QUERY_SQL($sql));
			if(!$GLOBALS["Q"]->ok){WLOG("$sql = {$GLOBALS["Q"]->mysql_error}");}
			$size=$ligne["size"];
			$size=$size/1024;
			$size=$size/1024;
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("$sql = {$size}/MB {$MAX}MB");}
			$GLOBALS["SQLCACHE"][$MDKEY]["TIME"]=time();
			$GLOBALS["SQLCACHE"][$MDKEY]["VALUE"]=$size;
			$Cache=$GLOBALS["SQLCACHE"][$MDKEY]["VALUE"];
		}
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Hourly: {$Cache}MB <> {$MAX}MB");}
		if($Cache>$MAX){return true;}
		
		return false;	
		
	}
}

function CheckQuotaSQL_cache($MDKEY){
	
	clean_cache();
	
	$CacheTime=round($GLOBALS["PARAMS"]["CACHE_TIME"]/60);
	if($CacheTime==0){$CacheTime=6;$GLOBALS["PARAMS"]["CACHE_TIME"]=$CacheTime*60;}
	if(!isset($GLOBALS["SQLCACHE"][$MDKEY])){return 0;}
	$data1 = $GLOBALS["SQLCACHE"][$MDKEY]["TIME"];
	$data2 = time();
	$difference = ($data2 - $data1); 	 
	$mins=round($difference/60);
	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("CheckQuotaSQL_cache() -> {$mins}Mn/{$CacheTime}Mn return {$GLOBALS["SQLCACHE"][$MDKEY]["VALUE"]}MB");}
	if($mins<$CacheTime){return $GLOBALS["SQLCACHE"][$MDKEY]["VALUE"];}
}
function clean_cache(){
	$CacheTime=round($GLOBALS["PARAMS"]["CACHE_TIME"]/60);
	if($CacheTime==0){$CacheTime=6;$GLOBALS["PARAMS"]["CACHE_TIME"]=$CacheTime*60;}
	$data1 = $GLOBALS["SQLCACHE"]["TIME"];
	if(!is_numeric($data1)){$GLOBALS["SQLCACHE"]["TIME"]=time();return;}
	if($data1<5){$GLOBALS["SQLCACHE"]["TIME"]=time();return;}
	if(count($GLOBALS["SQLCACHE"])>10000){unset($GLOBALS["SQLCACHE"]);$GLOBALS["SQLCACHE"]=array();$GLOBALS["SQLCACHE"]["TIME"]=time();return;}
	$data2 = time();
	$difference = ($data2 - $data1);
	$mins=round($difference/60);
	if($mins<$CacheTime){return;}
	unset($GLOBALS["SQLCACHE"]);
	$GLOBALS["SQLCACHE"]=array();
	$GLOBALS["SQLCACHE"]["TIME"]=time();
}


function sources_matches($xtype,$PATTERN,$CPINFOS){
	$IPADDR=$CPINFOS["IPADDR"];
	$MAC=$CPINFOS["MAC"];
	$HOST=$CPINFOS["HOST"];
	$LOGIN=$CPINFOS["LOGIN"];
	
	
	$identifications["ipaddr"]="{ipaddr}";
	$identifications["uid"]="{member}";
	$identifications["uidAD"]="{active_directory_member}";
	$identifications["MAC"]="{MAC}";
	$identifications["hostname"]="{hostname}";	
	$ad=new external_ad_search();

	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("sources_matches() \"$HOST\" Checking Source type =$xtype");}
	
	if($xtype=="ipaddr"){
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Checking $PATTERN against = $IPADDR");}
		if($IPADDR==null){return false;}
		$PATTERN=str_replace(".", "\.", $PATTERN);
		$PATTERN=str_replace("*", ".*?", $PATTERN);
		if(preg_match("#$PATTERN#i", $IPADDR)){
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Checking $PATTERN against = $IPADDR -> TRUE");}	
			return true;
		}
		
		return false;
		
	}
	if($xtype=="MAC"){
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Checking $PATTERN against = \"$MAC\"");}
		if($MAC==null){return false;}
		$PATTERN=str_replace(".", "\.", $PATTERN);
		$PATTERN=str_replace("*", ".*?", $PATTERN);
		if(preg_match("#$PATTERN#i", $IPADDR)){
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Checking $PATTERN against = $MAC -> TRUE");}
			return true;
		}
	
		return false;
	
	}	
	if($xtype=="uid"){
		
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Checking $PATTERN against = \"$LOGIN\"");}
		if($LOGIN==null){return false;}
		$PATTERN=str_replace(".", "\.", $PATTERN);
		$PATTERN=str_replace("*", ".*?", $PATTERN);
		if(preg_match("#$PATTERN#i", $IPADDR)){
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Checking $PATTERN against = $LOGIN -> TRUE");}
			return true;
		}
	
		return false;
	
	}

	
	if($xtype=="uidAD"){
		$LOGIN_A=trim(strtolower($LOGIN));
		$LOGIN_A=str_replace("%20", " ", $LOGIN_A);
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Checking $PATTERN against = \"$LOGIN_A\"");}
		if($LOGIN==null){return false;}
		$members=ActiveDirectoryCache($PATTERN);
		if(isset($members[$LOGIN_A])){
			if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Checking $PATTERN; $LOGIN is a memberOf = TRUE");}
			return true;
		}
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Checking $PATTERN; $LOGIN is not a memberOf = FALSE");}
		return false;
	
	}	
	
}

function ActiveDirectoryCache($PATTERN){
	$MASTERKEY=md5($PATTERN);
	$CacheTime=round($GLOBALS["PARAMS"]["CACHE_TIME"]/60);
	if($CacheTime==0){$CacheTime=6;$GLOBALS["PARAMS"]["CACHE_TIME"]=$CacheTime*60;}

	if(isset($GLOBALS["AD"][$MASTERKEY])){
		if(isset($GLOBALS["AD"][$MASTERKEY]["TIME"])){
			$data1 = $GLOBALS["AD"][$MASTERKEY]["TIME"];
			if(!is_numeric($data1)){$data1=0;}
			$data2 = time();
			$difference = ($data2 - $data1);
			$mins=round($difference/60);
			if($mins<$CacheTime){ return $GLOBALS["AD"][$MASTERKEY]["DB"];}
		}
	}
	
	$ad=new external_ad_search();
	$members=$ad->HashUsersFromGroupDN($PATTERN);
	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("ActiveDirectoryCache(): $PATTERN = ".count($members)." members");}
	if(count($members)==0){
		$GLOBALS["AD"][$MASTERKEY]["TIME"]=time();
		$GLOBALS["AD"][$MASTERKEY]["DB"]=array();
		return array();
	}
	while (list ($index, $uid) = each ($members)){
		$uid=trim(strtolower($uid));
		if($uid==null){continue;}
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("ActiveDirectoryCache(): $uid member");}
		$GLOBALS["AD"][$MASTERKEY]["TIME"]=time();
		$GLOBALS["AD"][$MASTERKEY]["DB"][$uid]=true;
	}
	return $GLOBALS["AD"][$MASTERKEY]["DB"];
	
}


function WLOG($text=null){
	if(!isset($GLOBALS["F"])){$GLOBALS["F"] = @fopen("/var/log/squid/external-acl.log", 'a');}
	$trace=@debug_backtrace();
	if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$date=@date("Y-m-d H:i:s");
   	if (is_file("/var/log/squid/external-acl.log")) { 
   		$size=@filesize("/var/log/squid/external-acl.log");
   		if($size>1000000){
   			@fclose($GLOBALS["F"]);
   			unlink("/var/log/squid/external-acl.log");
   			$GLOBALS["F"] = @fopen("/var/log/squid/external-acl.log", 'a');
   		}
   		
   		
   	}
	
	
	@fwrite($GLOBALS["F"], "$date ".basename(__FILE__)."[{$GLOBALS["PID"]}]: $text $called\n");
}

function uriToHost($uri){
	if(count($GLOBALS["uriToHost"])>20000){$GLOBALS["uriToHost"]=array();}
	if(isset($GLOBALS["uriToHost"][$uri])){return $GLOBALS["uriToHost"][$uri];}
	$URLAR=parse_url($uri);
	if(isset($URLAR["host"])){$sitename=$URLAR["host"];}
	if(preg_match("#^www\.(.*?)#", $sitename,$re)){$sitename=$re[1];}
	if(preg_match("#(.*?):[0-9]+#", $sitename,$re)){$sitename=$re[1];}
	$GLOBALS["uriToHost"][$uri]=$sitename;
	return $sitename;
	
}
function GetComputerName($ip){
		
		return $ip;
		}
function GetMacFromIP($ipaddr){
		$ipaddr=trim($ipaddr);
		$ttl=date('YmdH');
		if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
		if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}
		if(!isset($GLOBALS["SBIN_ARP"])){$GLOBALS["SBIN_ARP"]=find_program("arp");}
		if(!isset($GLOBALS["SBIN_ARPING"])){$GLOBALS["SBIN_ARPING"]=find_program("arping");}
		
		if(strlen($GLOBALS["SBIN_ARPING"])>3){
			$cmd="{$GLOBALS["SBIN_ARPING"]} $ipaddr -c 1 -r 2>&1";
			exec($cmd,$results);
			foreach ($results as $num=>$line){
				if(preg_match("#^([0-9a-zA-Z\:]+)#", $line,$re)){
					$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
					return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
				}
			}
		}
		
		
		$results=array();
			
		if(strlen($GLOBALS["SBIN_ARP"])<4){return;}
		if(!isset($GLOBALS["SBIN_PING"])){$GLOBALS["SBIN_PING"]=find_program("ping");}
		if(!isset($GLOBALS["SBIN_NOHUP"])){$GLOBALS["SBIN_NOHUP"]=find_program("nohup");}
		
		$cmd="{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1";
		WLOG($cmd);
		exec($cmd,$results);
		foreach ($results as $num=>$line){
			if(preg_match("#^[0-9\.]+\s+.+?\s+([0-9a-z\:]+)#", $line,$re)){
				if($re[1]=="no"){continue;}
				$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
				return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
			}
			
		}
		
		if(!isset($GLOBALS["PINGEDHOSTS"][$ipaddr])){
			shell_exec("{$GLOBALS["SBIN_NOHUP"]} {$GLOBALS["SBIN_PING"]} $ipaddr -c 3 >/dev/null 2>&1 &");
			$GLOBALS["PINGEDHOSTS"][$ipaddr]=true;
		}
			
		
	}
function find_program($strProgram) {
	  $key=md5($strProgram);
	  if(isset($GLOBALS["find_program"][$key])){return $GLOBALS["find_program"][$key];}
	  $value=trim(internal_find_program($strProgram));
	  $GLOBALS["find_program"][$key]=$value;
      return $value;
}
function internal_find_program($strProgram){
	  global $addpaths;	
	  $arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin', 
	  '/usr/local/sbin',
	  '/usr/kerberos/bin',
	  
	  );
	  
	  if (function_exists("is_executable")) {
	    foreach($arrPath as $strPath) {
	      $strProgrammpath = $strPath . "/" . $strProgram;
	      if (is_executable($strProgrammpath)) {
	      	  return $strProgrammpath;
	      }
	    }
	  } else {
	   	return strpos($strProgram, '.exe');
	  }
	}	
?>
