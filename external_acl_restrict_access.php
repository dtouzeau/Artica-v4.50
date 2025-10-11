#!/usr/bin/php
<?php
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
//error_reporting(0);
if(preg_match("#--verbose#", @implode(" ", $argv))){
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);error_reporting(1);
	
	$GLOBALS["VERBOSE"]=true;
	echo "VERBOSED MODE\n";
}
  
  $GLOBALS["PID"]=getmypid();
  $GLOBALS["STARTIME"]=time();
  $GLOBALS["SESSION_TIME"]=array();
  $GLOBALS["TIMELOG"]=0;
  $GLOBALS["QUERIES_NUMBER"]=0;
  $GLOBALS["TIMELOG_TIME"]=time();

  if($argv[1]=="--tests"){Checktime($argv[2],$argv[3]);	die(0);}
  $max_execution_time=ini_get('max_execution_time'); 
  WLOG("[START]: Starting New process");
  $GLOBALS["MAIN"]=unserialize(@file_get_contents("/etc/squid3/MacRestrictAccess.db"));
  error_reporting(0);
  
  
while (!feof(STDIN)) {
 $content = trim(fgets(STDIN));
 
	 if($content<>null){
	 	
	 	$array=explode(" ",$content);
	 	$ip = $array[0];
	 	$mac=$array[1];
	 	$ip2 = $array[2];
	 	if($ip2=="-"){$ip2=null;}
	 	if($ip2<>null){$ip=$ip2;}
	 	if($GLOBALS["VERBOSE"]){WLOG("[LOOP]: IP:$ip MAC:$mac x-forwaded-for:$ip2");}
	 	$GLOBALS["QUERIES_NUMBER"]++;
	 	if (!Checktime($mac,$ip)){
	 		if($GLOBALS["VERBOSE"]){WLOG("[NOT_RESTRICTED]: $mac");}
	 		fwrite(STDOUT, "ERR\n");
	 		continue;
	 	}
	 	
	
	 	WLOG("[RESTRICTED]: $mac");
	 	fwrite(STDOUT, "OK\n");
	 	
		}
}

$distanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
$distanceInMinutes = round($distanceInSeconds / 60);
WLOG("[STOP]: Stopping process v1.0: After ({$distanceInSeconds}s - about {$distanceInMinutes}mn)");
WLOG("[STOP]: This process was replied {$GLOBALS["QUERIES_NUMBER"]} times...</span>");
die("DIE " .__FILE__." Line: ".__LINE__);




function Checktime($MAC,$ipaddr){
	$MAC=trim(strtolower($MAC));
	if($MAC=="00:00:00:00:00:00"){return false;}
	NewComputer($MAC,$ipaddr);
	
	$MAIN=$GLOBALS["MAIN"];
	
	if(count($MAIN)==0){
		if($GLOBALS["VERBOSE"]){WLOG("[ERR]: MAIN NO ARRAY...");}
		return false;
		
	}
	
	if(!isset($MAIN[$MAC])){
		if($GLOBALS["VERBOSE"]){WLOG("[INFO]: [$MAC] Not found");}
		return false;
	}
	
	$time=time();
	$currentStartDay=strtotime(date("Y-m-d 00:00:01"));
	$ThisInt=$time-$currentStartDay;
	
	
	$array["0"]="00:00";
	$array["3600"]="01:00";
	$array["7200"]="02:00";
	$array["10800"]="03:00";
	$array["14400"]="04:00";
	$array["18000"]="05:00";
	$array["21600"]="06:00";
	$array["25200"]="07:00";
	$array["28800"]="08:00";
	$array["32400"]="09:00";
	$array["36000"]="10:00";
	$array["39600"]="11:00";
	$array["43200"]="12:00";
	$array["46800"]="13:00";
	$array["50400"]="14:00";
	$array["54000"]="15:00";
	$array["57600"]="16:00";
	$array["61200"]="17:00";
	$array["64800"]="18:00";
	$array["68400"]="19:00";
	$array["72000"]="20:00";
	$array["75600"]="21:00";
	$array["79200"]="22:00";
	$array["82800"]="23:00";
	
	$DayIndex=date("w");
	if($GLOBALS["VERBOSE"]){WLOG("[INFO]: [$MAC] Day Index: $DayIndex Start: $currentStartDay - Time:$time = $ThisInt");}
	

	
	if($ThisInt<43200){
		
		$allow_time_from=$MAIN[$MAC][$DayIndex]["AM"][0];
		$allow_time_to=$MAIN[$MAC][$DayIndex]["AM"][1];
		if($GLOBALS["VERBOSE"]){WLOG("[INFO]: [$MAC] We are in morning...");}
	}else{
		
		$allow_time_from=$MAIN[$MAC][$DayIndex]["PM"][0];
		$allow_time_to=$MAIN[$MAC][$DayIndex]["PM"][1];
		if($GLOBALS["VERBOSE"]){WLOG("[INFO]: [$MAC] We are in afternoon...");}
		
	}
	
	if($GLOBALS["VERBOSE"]){WLOG("[INFO]: [$MAC] FROM: $allow_time_from ({$array[$allow_time_from]}) TO $allow_time_to ({$array[$allow_time_to]})");}
	
	
	if($ThisInt>$allow_time_from){
		if($ThisInt<$allow_time_to){
			if($GLOBALS["VERBOSE"]){WLOG("[INFO]: [$MAC] ALLOWED");}
			return false;
		}
	}
	if($GLOBALS["VERBOSE"]){WLOG("[INFO]: [$MAC] BANNED");}
	ufdbgevents($ipaddr);
	return true;
	
	
}

function internal_find_program($strProgram){
	global $addpaths;
	$arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin','/usr/local/sbin','/usr/kerberos/bin');
	if (function_exists("is_executable")) {foreach($arrPath as $strPath) {$strProgrammpath = $strPath . "/" . $strProgram;if (is_executable($strProgrammpath)) {return $strProgrammpath;}}} else {return strpos($strProgram, '.exe');}
}

function NewComputer($mac,$ipaddr){
	if($mac==null){return;}
	if(isset($GLOBALS["COMPUTERS_MEM"][$mac])){return;}
	$Dir="/var/log/squid/mysql-computers";
	$rand=rand(5, 90000);
	$filetemp="$Dir/Computers.$mac.sql";
	if(is_file($filetemp)){$GLOBALS["COMPUTERS_MEM"][$mac]=true;return;}
	$array["IP"]=$ipaddr;
	$array["MAC"]=$mac;
	@file_put_contents($filetemp,serialize($array));
	$GLOBALS["COMPUTERS_MEM"][$mac]=true;
}




function WLOG($text=null){

	
	$text=chop($text);
	$filename="/var/log/squid/external-acl-ldap.log";
	$trace=@debug_backtrace();
	if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	if(!isset($GLOBALS["PID"])){$GLOBALS["PID"]=getmypid();}
	
	if (is_file($filename)) { 
   		$size=@filesize($filename);
   		if($size>1000000){unlink($filename);}
   	}
   	$F = @fopen($filename, 'a');
	@fwrite($F, "$date [{$GLOBALS["PID"]}]: $text $called\n");
	@fclose($F);
}

function ufdbgevents($ipaddr){

	$time=time();
	$q=new influx();
	$Clienthostname=gethostbyaddr($ipaddr);
	$line="$time:::$ipaddr:::restricted_time:::default:::0.0.0.0:::blocked access::blocked access:::$Clienthostname:::ALL:::$ipaddr";
	$public_ip="0.0.0.0";
	$prefix="INSERT INTO webfilter (zDate,website,category,rulename,public_ip,blocktype,why,hostname,client,proxyname,rqs)";
	$line="('".date("Y-m-d H:i:s")."','restricted_time','restricted_time','default','$public_ip','blocked domain','blocked domain','$Clienthostname','$ipaddr','{$GLOBALS["myhostname"]}',1)";
	$md5=md5($line);
	@mkdir("/home/artica/squid/webfilter/events",0755,true);
	@file_put_contents("/home/artica/squid/webfilter/events/$md5.sql", $line);
}



?>
