#!/usr/bin/php -q
<?php
$GLOBALS["HERLPER_LOADED_BY_SQUID"]=true;
$GLOBALS["MYPID"]=getmypid();
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
WLOG("Starting PID:{$GLOBALS["MYPID"]}");
$GLOBALS["DEBUG"]=false;
$DCOUNT=0;

while (!feof(STDIN)) {
	$Buffer = trim(fgets(STDIN));
	if($Buffer==null){
		if($GLOBALS["DEBUG"]){WLOG("$DCOUNT] DEBUG LOOP::URL `$Buffer` is null [".__LINE__."]");}
		continue;
	}
	
	if($GLOBALS["DEBUG"]){WLOG("$DCOUNT] LOOP::BUFFER `$Buffer` [".__LINE__."]");}
	$MAIN=explode(" ",$Buffer);
	$prefix_channel=null;
	// administrateur 192.168.1.177 3c:a9:f4:13:9b:90 - www.google.fr 57
	
	if(is_numeric($MAIN[0])){
		$GLOBALS["CHANNEL"]=$MAIN[0];
		$EXT_LOG=trim($MAIN[1]);
		$TAG=trim($MAIN[2]);
		$userid=trim($MAIN[3]);
		$ipaddr=trim($MAIN[4]);
		$MAC=trim($MAIN[5]);
		$ipaddr2=trim($MAIN[6]);
		$url=trim($MAIN[7]);
		$groupid=intval(trim($MAIN[8]));
		$MD5KEY=md5($Buffer);
		$GLOBALS["TAG"]=$TAG;
		$GLOBALS["USERID"]=$userid;
		$GLOBALS["IPADDR"]=$ipaddr;
		$GLOBALS["MAC"]=$MAC;
		$GLOBALS["DOMAIN"]=$url;
	}else{
		$EXT_LOG=trim($MAIN[0]);
		$TAG=trim($MAIN[1]);
		$userid=trim($MAIN[2]);
		$ipaddr=trim($MAIN[3]);
		$MAC=trim($MAIN[4]);
		$ipaddr2=trim($MAIN[5]);
		$url=trim($MAIN[6]);
		$groupid=intval(trim($MAIN[7]));
		$MD5KEY=md5($Buffer);
		$GLOBALS["EXT_LOG"]=$TAG;
		$GLOBALS["TAG"]=$TAG;
		$GLOBALS["USERID"]=$userid;
		$GLOBALS["IPADDR"]=$ipaddr;
		$GLOBALS["MAC"]=$MAC;
		$GLOBALS["DOMAIN"]=$url;
	}
	

	if(isset($GLOBALS["CHANNEL"])){
		if(is_numeric($GLOBALS["CHANNEL"])){
			if($GLOBALS["CHANNEL"]>0){
				$prefix_channel="{$GLOBALS["CHANNEL"]} ";
			}
		}
	}
	
	if($GLOBALS["DEBUG"]){WLOG("{$GLOBALS["DOMAIN"]}: LOOP TAG=$TAG USERID=$userid IPADDR=$ipaddr/$MAC");}
	
	if($GLOBALS["DEBUG"]){WLOG("{$GLOBALS["DOMAIN"]}: LOOP channel=$prefix_channel [$Buffer] ->`$url` -> $groupid");}
	
	$DCOUNT++;
	

	
	try {
		if($GLOBALS["DEBUG"]){WLOG("{$GLOBALS["DOMAIN"]}:LOOP Send ->`$url`");}
		$RESULT=categories_match($groupid,$url,$MD5KEY);
	}
	catch (Exception $e) {
		$error=$e->getMessage();
		WLOG("{$GLOBALS["DOMAIN"]}: $DCOUNT] LOOP::FATAL ERROR $error");
		$result=false;
	}
	
	if($RESULT){
		fwrite(STDOUT, "{$prefix_channel}OK\n");
		continue;
	}
	fwrite(STDOUT, "{$prefix_channel}ERR\n");
	continue;

	
	
}



WLOG("Stopping PID:{$GLOBALS["MYPID"]} After $DCOUNT event(s) SAVED {$GLOBALS["DATABASE_ITEMS"]} items in database");
	
	
function WLOG($text=null){
	$trace=@debug_backtrace();
	$filename="/var/log/squid/acl_categories.log";
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



function categories_match($gpid,$sitname,$MD5KEY){
	$sitname=trim($sitname);
	if(preg_match("#^www\.(.+)#", $sitname,$re)){$sitname=$re[1];}
	if(preg_match("#^(.+):[0-9]+]#", $sitname,$re)){$sitname=$re[1];}
	if($GLOBALS["DEBUG"]){WLOG("Analyze: Group: $gpid `$sitname`");}
	
	$categories_get_memory=categories_get_memory($gpid,$sitname,$MD5KEY);
	
	if($categories_get_memory==0){
		if($GLOBALS["DEBUG"]){WLOG("Group: $gpid `$sitname` -> MEMORY: `$categories_get_memory` ");}
		categories_logs("$gpid;MEMORY;UNKNOWN/NONE");
		if($GLOBALS["DEBUG"]){WLOG("Analyze: Group: FROM MEMORY `$sitname` -> UNKNOWN");}
		return false;
	}
		
	if($categories_get_memory==1){
		categories_logs("$gpid;MEMORY;TRUE/-");
		if($GLOBALS["DEBUG"]){WLOG("Analyze: Group: FROM MEMORY `$sitname` -> TRUE");}
		return true;
	}
	if($categories_get_memory==2){
		categories_logs("$gpid;MEMORY;FALSE/-");
		if($GLOBALS["DEBUG"]){WLOG("Analyze: Group: FROM MEMORY `$sitname` -> FALSE");}
		return false;
	}	
	
	

	$q=new mysql_catz();
	$categoriF=$q->GET_CATEGORIES($sitname);

	$trans=$q->TransArray();
	if($GLOBALS["DEBUG"]){WLOG("Group: $gpid `$sitname` -> RESULTS: `$categoriF` ");}

	if($categoriF==null){
		if($GLOBALS["DEBUG"]){WLOG("Group: $gpid `$sitname` -> SET TO  `UNKNOWN` ");}
		categories_logs("$gpid;QUERY;UNKNOWN/NONE");
		categories_set_memory($gpid,$sitname,0,$MD5KEY);
		return false;
	}

	if(strpos($categoriF, ",")>0){
		$categoriT=explode(",",$categoriF);
	}else{
		$categoriT[]=$categoriF;
	}

	while (list ($a, $b) = each ($categoriT)){
		if($GLOBALS["DEBUG"]){WLOG("Group: $gpid `$sitname` -> category IS: [$b] [".__LINE__."]");}
		$MAIN[$b]=true;
	}

	if(!isset($GLOBALS["CONFIG"][$gpid])){
		$filename="/etc/squid3/acls/catz_gpid{$gpid}.acl";
		$GLOBALS["CONFIG"][$gpid]=unserialize(@file_get_contents($filename));
	}
	
	$categories=$GLOBALS["CONFIG"][$gpid];

	while (list ($category_table, $category_rule) = each ($categories)){
		$category_rule=urlencode($category_rule);
		$categoryname=$trans[$category_table];
		if($categoryname==null){$categoryname=$category_rule;}
		
		if(isset($MAIN[$categoryname])){
			if($GLOBALS["DEBUG"]){WLOG("FOUND `$categoryname` -> `$category_rule` ");}
			categories_logs("$gpid;QUERY;TRUE/$categoryname");
			categories_set_memory($gpid,$sitname,1,$MD5KEY);
			return true;
		}

	}
	
	categories_logs("$gpid;QUERY;FALSE/".@implode(",", $categoriT));
	categories_set_memory($gpid,$sitname,2,$MD5KEY);
	return false;
}
	
function categories_get_memory($gpid,$sitname,$MD5KEY){
	$KEYRULE=md5("$MD5KEY$gpid");
    $memcached=new lib_memcached();
    $value=intval($memcached->getKey("ACL_CATEGORY_$KEYRULE"));
    if($GLOBALS["DEBUG"]){WLOG("$sitname: MEMORY: $KEYRULE return $value from memory");}
    if(!$memcached->MemCachedFound){return 3;}
    if($value==0){return 3;}
    return $value;
}



function categories_set_memory($gpid,$sitname,$result,$MD5KEY){
	$KEYRULE=md5("$MD5KEY$gpid");
    $memcached=new lib_memcached();
    if($GLOBALS["DEBUG"]){WLOG("MEMORY: Group: $gpid `$sitname` -> MEMCACHED AS $KEYRULE");}
	$memcached->saveKey("ACL_CATEGORY_$KEYRULE",$result,360);

}

function categories_logs($text=null){
	if($text==null){return;}
	$maxsize=10485760;
	$filename="/var/log/squid/acl.categories.log";
	$size=@filesize("/var/log/squid/acl.categories.log");
	
	if (is_file($filename)) {
		$size=@filesize($filename);
		if($size>$maxsize){ unlink($filename); }
	}
	$EXT_LOG=$GLOBALS["EXT_LOG"];
	$userid=$GLOBALS["USERID"];
	$ipaddr=$GLOBALS["IPADDR"];
	$MAC=$GLOBALS["MAC"];
	$url=$GLOBALS["DOMAIN"];
	$TAG=$GLOBALS["TAG"];
	if($EXT_LOG<>null){
		$pp=explode(",",$EXT_LOG);
		$GROUP=$pp[0];
		$OU=$pp[1];
	}
	if($GROUP==null){$GROUP="-";}
	if($OU==null){$OU="-";}
	$f = @fopen($filename, 'a');
	$date=time();
	@fwrite($f, "$date;$TAG;$userid | $GROUP | $OU;$ipaddr;$MAC;$url;$text\n");
	@fclose($f);
	
}
?>