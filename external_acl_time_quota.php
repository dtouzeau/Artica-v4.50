#!/usr/bin/php -q
<?php
$GLOBALS["DEBUG"]=false;
$GLOBALS["DBPATH"]="/var/log/squid/QUOTADB.db";
//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");

if(preg_match("#--gpid\s+([0-9]+)#", @implode(" ", $argv),$re)){
	$GLOBALS["GPID"]=$re[1];
}

$GLOBALS["MYPID"]=getmypid();
WLOG("Starting PID:{$GLOBALS["MYPID"]}");

if(is_file($GLOBALS["DBPATH"])){
	$filesize=@filesize($GLOBALS["DBPATH"]);
	$filesize=$filesize/1024;
	$filesize=$filesize/1024;
	if($filesize>100){@unlink($GLOBALS["DBPATH"]);}
}

if(!is_file($GLOBALS["DBPATH"])){
	
	try {
		WLOG("Creating {$GLOBALS["DBPATH"]} database");
		$db_desttmp = dba_open($GLOBALS["DBPATH"], "c","db4");
	}
	catch (Exception $e) {
		$error=$e->getMessage();
		WLOG("TIME_QUOTA::FATAL ERROR $error");
		
	}
	
	
	
	if(!$db_desttmp){WLOG("TIME_QUOTA::FATAL ERROR, unable to create database {$GLOBALS["DBPATH"]}");}
	dba_close($db_desttmp);
}
@chmod($GLOBALS["DBPATH"],0777);

LOADING_RULES();
WLOG("Quota Database : Starting Group id:{$GLOBALS["MYPID"]}");


$DCOUNT=0;
while (!feof(STDIN)) {
	$url = trim(fgets(STDIN));
	if($url==null){
		if($GLOBALS["DEBUG"]){WLOG("$DCOUNT] TIME_QUOTA::URL is null [".__LINE__."]");}
		continue;
	}
	$DCOUNT++;
	
	
	
	try {
		$result = TIME_QUOTA($url);
	}
	catch (Exception $e) {
		$error=$e->getMessage();
		WLOG("$DCOUNT] TIME_QUOTA::FATAL ERROR $error");
		$result=false;
	}
	
	if(!$result){
		if($GLOBALS["DEBUG"]){WLOG("$DCOUNT] TIME_QUOTA::ERR");}
		fwrite(STDOUT, "ERR\n");
		continue;
	}

	if($GLOBALS["DEBUG"]){WLOG("$DCOUNT] TIME_QUOTA::OK");}
	fwrite(STDOUT, "OK\n");
	
	
}



WLOG("Stopping PID:{$GLOBALS["MYPID"]} After $DCOUNT event(s) SAVED {$GLOBALS["DATABASE_ITEMS"]} items in database");
	
	
function WLOG($text=null){
	$trace=@debug_backtrace();
	$filename="/var/log/squid/acl_timequota.log";
	if(isset($trace[0])){$called=" called by ". basename($trace[0]["file"])." {$trace[0]["function"]}() line {$trace[0]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	$mem=_get_memory_usage_158();
	
	
	if (is_file($filename)) {
		$size=@filesize($filename);
		if($size>1000000){ unlink($filename); }
	}
	
	
	$f = @fopen($filename, 'a');
	
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}]: $text $called - process Memory:$mem\n");
	@fclose($f);
}

function _get_memory_usage_158() {
	$mem_usage = memory_get_usage(true);
	if ($mem_usage < 1024){return $mem_usage." bytes";}
	if ($mem_usage < 1048576){return round($mem_usage/1024,2)." kilobytes";}
	return round($mem_usage/1048576,2)." megabytes";
}

function LOADING_RULES(){
	$file="/etc/squid3/acls/time_gpid{$GLOBALS["GPID"]}.acl";
	if(!is_file($file)){
		WLOG("LOADING_RULES::$file no such file! [".__LINE__."]");
		$GLOBALS["ACL_RULES"]=array();return;
	}
	$array=unserialize(@file_get_contents($file));
	$c=0;
	foreach($array as $line){
		
		if(preg_match("#max:.*?([0-9]+)#i", $line,$re)){
			if($GLOBALS["DEBUG"]){WLOG("LOADING_RULES::$c Max time = {$re[1]} minutes [".__LINE__."]");}
			$GLOBALS["ACL_RULES"][$c]["MAX"]=$re[1];
		}
		if(preg_match("#wait:.*?([0-9]+)#i", $line,$re)){
			if($GLOBALS["DEBUG"]){WLOG("LOADING_RULES::$c WAIT time = {$re[1]} minutes [".__LINE__."]");}
			$GLOBALS["ACL_RULES"][$c]["WAIT"]=$re[1];
		}	

		if(preg_match("#interval:.*?([0-9]+)#i", $line,$re)){
			if($GLOBALS["DEBUG"]){WLOG("LOADING_RULES::$c interval time = {$re[1]} minutes [".__LINE__."]");}
			$GLOBALS["ACL_RULES"][$c]["INTERVAL"]=$re[1];
		}
		$c++;
	}
	
	
	
	
	
}
	
	
function TIME_QUOTA($url){
	
	if(trim($url)==null){if($GLOBALS["DEBUG"]){WLOG("TIME_QUOTA::URL is null [".__LINE__."]"); return false; }}
	if(strpos(" $url", "127.0.0.1 00:00:00:00:00:00")>0){return false;}
	
	if($GLOBALS["DEBUG"]){WLOG("TIME_QUOTA::$url [".__LINE__."]");}
	$values=explode(" ",$url);
	$USERNAME=$values[0];
	
	
	if(strpos($USERNAME, '$')>0){
		if(substr($USERNAME, strlen($USERNAME)-1,1)=="$"){
			$USERNAME=null;
		}
	}
	$IPADDR=$values[1];
	$MAC=$values[2];
	$XFORWARD=$values[3];
	$WWW=$values[4];
	
	if($GLOBALS["DEBUG"]){WLOG("TIME_QUOTA::USERNAME:$USERNAME [".__LINE__."]");}
	if($GLOBALS["DEBUG"]){WLOG("TIME_QUOTA::IPADDR..:$IPADDR [".__LINE__."]");}
	if($GLOBALS["DEBUG"]){WLOG("TIME_QUOTA::MAC.....:$MAC [".__LINE__."]");}
	if($GLOBALS["DEBUG"]){WLOG("TIME_QUOTA::XFORWARD:$XFORWARD [".__LINE__."]");}
	if($GLOBALS["DEBUG"]){WLOG("TIME_QUOTA::WWW.....:$WWW [".__LINE__."]");}
	
	$USERNAME=str_replace("%20", " ", $USERNAME);
	$USERNAME=str_replace("%25", "-", $USERNAME);
	
	$IPADDR=str_replace("%25", "-", $IPADDR);
	$MAC=str_replace("%25", "-", $MAC);
	$XFORWARD=str_replace("%25", "-", $XFORWARD);
	if($XFORWARD=="-"){$XFORWARD=null;}
	if($MAC=="00:00:00:00:00:00"){$MAC=null;}
	if($MAC=="-"){$MAC=null;}
	if($USERNAME=="-"){$USERNAME=null;}
	
	$IPCalls=new IP();
	
	if($IPCalls->isIPAddress($XFORWARD)){$IPADDR=$XFORWARD;}
	
	if(preg_match("#(.+?):[0-9]+#", $WWW,$re)){$WWW=$re[1];}
	if(preg_match("#^www\.(.+)#", $WWW,$re)){$WWW=$re[1];}
	if(!class_exists("squid_familysite")){include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");}
	$fam=new squid_familysite();
	$WWW=$fam->GetFamilySites($WWW);
	
	
	
	$db_con = dba_open($GLOBALS["DBPATH"], "c","db4");
	if(!$db_con){
		WLOG("FATAL!!! TIME_QUOTA::{$GLOBALS["DBPATH"]}, unable to open");
		return false;
	}
	
	
	$mainkey=md5(trim("$USERNAME$IPADDR$MAC$WWW"));
	if($USERNAME<>null){$mainkey=md5("$USERNAME$WWW");}
	if($USERNAME==null){if($MAC<>null){$mainkey=md5("$MAC$WWW");}}
	$Fetched=true;
	if(!dba_exists($mainkey,$db_con)){
		$Fetched=false;
		if($GLOBALS["DEBUG"]){WLOG("FATAL!!! TIME_QUOTA::$mainkey doesn't exists");}
		
	}else{
		if($GLOBALS["DEBUG"]){WLOG("TIME_QUOTA::$mainkey Exists OK");}
	}
	
	if(!$Fetched){
		$array["START"]=time();
		$array["website"]=$WWW;
		$array["username"]=$USERNAME;
		$array["ipaddr"]=$IPADDR;
		$array["MAC"]=$MAC;
		$array["END"]=time();
		$array["ORG_START"]=time();
		if($GLOBALS["DEBUG"]){WLOG("TIME_QUOTA::[$WWW]: new item for UID:$USERNAME; IPADDR=$IPADDR;MAC=$MAC,sitename=$WWW");}
		dba_replace($mainkey,serialize($array),$db_con);
		@dba_close($db_con);
		return false;
	}
	
	$array=unserialize(dba_fetch($mainkey,$db_con));
	if(!isset($array["END"])){$array["END"]=time();}
	if(!isset($array["ORG_START"])){$array["ORG_START"]=time();}
	
	
	if(!is_array($array)){
		if($GLOBALS["DEBUG"]){WLOG("[$WWW]: FATAL!!! Array is not an array...");}
	}else{
		if($GLOBALS["DEBUG"]){WLOG("[$WWW]: In DB www:{$array["website"]} Last scan {$array["END"]}");}
		
		
	}
	if(!is_numeric($array["START"])){$array["START"]=time();}
	if(!is_numeric($array["ORG_START"])){$array["ORG_START"]=time();}
	
	if($array["START"]==0){$array["START"]=time();}
	
	if(!isset($array["website"])){$array["website"]=$WWW;}
	if(!isset($array["username"])){$array["username"]=$USERNAME;}
	if(!isset($array["ipaddr"])){$array["ipaddr"]=$IPADDR;}
	if(!isset($array["MAC"])){$array["MAC"]=$MAC;}
	
	$array["SEC"]=time()-$array["START"];
	$array["TIME"]=time_passed_min($array["START"],time());
	
	if($GLOBALS["ACL_RULES"]>0){$array=TIMED_OUT($array);}
	
	if(!isset($array["LOCK"])){$array["LOCK"]=false;}
	if($array["LOCK"]){
		if($GLOBALS["DEBUG"]){WLOG("[$WWW]: ** LOCKED **");}
	}
	$array["END"]=time();
	if($GLOBALS["DEBUG"]){WLOG("[$WWW]: TIME_QUOTA::Start: {$array["START"]} ({$array["SEC"]} seconds) for UID:$USERNAME; IPADDR=$IPADDR;MAC=$MAC,sitename=$WWW");}
	if(!dba_replace($mainkey,serialize($array),$db_con)){WLOG("[$WWW]: TIME_QUOTA::FATAL ERROR, dba_replace $mainkey"); }
	@dba_close($db_con);
	
	
	return $array["LOCK"];
		
	
	
	
	
	
}

function TIMED_OUT($array){
	$WWW=$array["website"];
	if(count($GLOBALS["ACL_RULES"])==0){
		if($GLOBALS["DEBUG"]){WLOG("[$WWW]:TIMED_OUT: skipped no rule defined");}
		$array["LOCK"]=false;
		return $array;
	}
	
	
	if($GLOBALS["DEBUG"]){WLOG("[$WWW]: ---------------- ||$WWW|| ANALYZE ----------------");}
	
	$passed_minutes=$array["TIME"];
	
	if(!is_numeric($passed_minutes)){$passed_minutes=0;}
	foreach($GLOBALS["ACL_RULES"] as $array_rules){
		
		
		if(!isset($array_rules["INTERVAL"])){$array_rules["INTERVAL"]=5;}
		$MAX=intval($array_rules["MAX"]);
		$WAIT=intval($array_rules["WAIT"]);
		$INTERVAL=intval($array_rules["INTERVAL"]);
		if($MAX==0){$MAX=5;}
		if($INTERVAL==0){$INTERVAL=5;}
		
		// On calcul l'intervalle de la dernière heure de scan si ce n'est pas locké.
		$interval_passed=time_passed_min($array["END"],time());
		if(!$array["LOCK"]){
			if($interval_passed>$INTERVAL){
				// L'interval est trop important, on débloque et on réitère.
				if($GLOBALS["DEBUG"]){WLOG("[$WWW]:TIMED_OUT:: UNLOCK : Interval {$interval_passed}Mn > $INTERVAL [".__LINE__."]");}
				$array["START"]=time();
				$array["LOCK"]=false;
				return $array;
			}
		}
		
		if($GLOBALS["DEBUG"]){WLOG("[$WWW]:TIMED_OUT:: passed minutes: {$passed_minutes}mn rule: {$MAX}mn [".__LINE__."]");}
		
		
		if($passed_minutes<$MAX){continue;}
		
		// Délai imparti
		if($GLOBALS["DEBUG"]){WLOG("[$WWW]:TIMED_OUT:: LOCKED [".__LINE__."]");}
		
		$array["LOCK"]=true;
		if($WAIT==0){
			// On block tout le temps
			if($GLOBALS["DEBUG"]){WLOG("[$WWW]:TIMED_OUT:: Locked every time [".__LINE__."]");}
			return $array;
		}
		
		if(!isset($array["WAIT_START_TIME"])){
			//premier pointeur
			if($GLOBALS["DEBUG"]){WLOG("[$WWW]:TIMED_OUT:: %%% First pointer %%% [".__LINE__."]");}
			$array["WAIT_START_TIME"]=time();
			return $array;
		}
			
		// nombre de minutes d'attente
		$MINUTES_TO_WAIT=time_passed_min($array["WAIT_START_TIME"],time());
		
		if($GLOBALS["DEBUG"]){
			$started=date("m d H:i:s",$array["WAIT_START_TIME"]);
			WLOG("[$WWW]:TIMED_OUT:: Current {$MINUTES_TO_WAIT}mn from $started need to wait: {$WAIT}mn [".__LINE__."]");
		}
		
		
		if($MINUTES_TO_WAIT>$WAIT){
			//A attendu suffisament de temps, flush...
			if($GLOBALS["DEBUG"]){WLOG("[$WWW]:TIMED_OUT:: Unlock!, flush [".__LINE__."]");}
			$array["START"]=time();
			$array["LOCK"]=false;
			return $array;
		}
		
		
		
			
		}
		
		
	// rien découvert, on retourne le tableau;
	if($GLOBALS["DEBUG"]){WLOG("[$WWW]:Nothing to do [".__LINE__."]");}
	return $array;
	
}


function time_passed_min($StartTime=0,$EndTime=0){
	$difference = ($EndTime - $StartTime);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}