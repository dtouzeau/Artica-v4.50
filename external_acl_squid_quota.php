#!/usr/bin/php -q
<?php
  include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
  if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
  
 
  ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
  error_reporting(0);
  $GLOBALS["LOGFILE"]="/var/log/squid/external_acl_quota.log";
  $GLOBALS["CACHEFILE"]="/etc/squid3/external_acl_quotas.db";
  $GLOBALS["PID"]=getmypid();
  $GLOBALS["STARTIME"]=time();
  $GLOBALS["MACTUIDONLY"]=false;
  $GLOBALS["uriToHost"]=array();
  $GLOBALS["SESSION_TIME"]=array();
  $GLOBALS["DEBUG_LEVEL"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidQuotaDebug");
  
  if(!is_numeric( $GLOBALS["DEBUG_LEVEL"])){ $GLOBALS["DEBUG_LEVEL"]=0;}
  $GLOBALS["F"] = @fopen($GLOBALS["LOGFILE"], 'a');
  $max_execution_time=ini_get('max_execution_time'); 
 
  $GLOBALS["Q"]=new mysql_squid_builder();
  
  $GLOBALS["TABLE_CHECKED"][date("YmdH")]=true;
  WLOG("Starting... Log level:{$GLOBALS["DEBUG_LEVEL"]}; max_execution_time:$max_execution_time argv[1]={$argv[1]} session-time={$GLOBALS["SESSION_TIME"]}");
 
  
while (!feof(STDIN)) {
 $url = trim(fgets(STDIN));
 if($GLOBALS["DEBUG_LEVEL"]>2){WLOG("<$url>");}
 if($GLOBALS["DEBUG_LEVEL"]>2){WLOG("EOF -");}
 if($url<>null){
 	if($GLOBALS["DEBUG_LEVEL"]>2){WLOG($url);}
  	$array=parse_url_squid($url);
 	if($GLOBALS["DEBUG_LEVEL"]>2){WLOG($url." str:".strlen($url)." uid:{$array["uid"]},IP:{$array["ipaddr"]}, MAC:{$array["mac"]} HOST:{$array["servername"]}");}
 	memory_trace($array);
 	fwrite(STDOUT, "OK\n");
 	
	 //	if(CheckQuota($array)){fwrite(STDOUT, "OK\n");}else{WLOG("ERR \"Out of quota\"");fwrite(STDOUT, "ERR message=\"Out Of Quota\"\n");}
 	}

}


$distanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
$distanceInMinutes = round($distanceInSeconds / 60);
WLOG("v1.0:". basename(__FILE__)." die after ({$distanceInSeconds}s/about {$distanceInMinutes}mn)");
if(isset($GLOBALS["F"])){@fclose($GLOBALS["F"]);}


function parse_url_squid($url){
	
	$f=explode(" ",$url);
	if($GLOBALS["DEBUG_LEVEL"]>2){
		while (list ($index, $content) = each ($f)){
			WLOG("t[$index]=$content");
		}
		
	}

	
	//dtouzeau 192.168.1.210 00:00:00:00:00:00 - wiki.squid-cache.org 59393 text/html;%20charset=utf-8
	$UID=$f[0];
	$IPADDR=$f[1];
	$MAC=$f[2];
	$FORWARDED=$f[3];
	$domain=$f[4];
	$size=$f[5];
	$type=urldecode($f[6]);
	if(!is_numeric($size)){$size=0;}
	if($MAC=="00:00:00:00:00:00"){$MAC=null;}
	if($MAC=="-"){$MAC=null;}
	if($FORWARDED=="-"){$FORWARDED=null;}
	if(preg_match("#(.+?);#", $type,$re)){$type=$re[1];}
	if($UID=="-"){$UID=null;}
	if($FORWARDED<>null){$IPADDR=$FORWARDED;}
	if($IPADDR=="127.0.0.1"){$IPADDR=null;}
	if($IPADDR=="0.0.0.0"){$IPADDR=null;}
	return array("uid"=>$UID,"ipaddr"=>$IPADDR,"mac"=>$MAC,"size"=>$size,"type"=>$type,"servername"=>$domain);
	
}

function memory_trace($array){
	
	$key=null;

	$uid=$array["uid"];
	$ipaddr=$array["ipaddr"];
	$mac=$array["mac"];
	$size=$array["size"];
	$type=$array["type"];
	$servername=$array["servername"];
	
	if($array["size"]==0){
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("O size ($uid,$mac,$ipaddr) -> $servername");}
		return;}
	
	if(preg_match("#^www\.(.+)#", $servername,$re)){$servername=$re[1];}
	if($uid<>null){$key="uid";}
	if($key==null){if($mac<>null){$key="MAC";}}
	if($key==null){if($ipaddr<>null){$key="ipaddr";}}
	if($key==null){
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("Unable to check key ($uid,$mac,$ipaddr) -> $servername");}
		return;
	}
	
	
	if(!isset($GLOBALS["TABLE_CHECKED"][date("YmdH")])){
		
		$GLOBALS["TABLE_CHECKED"][date("YmdH")]=true;
	}
	
	
	$table="quotahours_".date('YmdH');
  
	$hour=date("H");
	
	$keyr=md5("$hour$uid$ipaddr$mac$type$servername");
	
	$sql="SELECT `size` FROM `$table` WHERE `keyr`='$keyr'";
	$ligne=mysqli_fetch_array($GLOBALS["Q"]->QUERY_SQL($sql));
	$ligne["size"]=intval($ligne["size"]);
	if(!is_numeric($ligne["size"])){$ligne["size"]=0;}
	if($ligne["size"]>0){
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG("$uid/$ipaddr $servername ={$ligne["size"]} + $size");}
		$newsize=$ligne["size"]+$size;
		$sql="UPDATE LOW_PRIORITY `$table` SET `size`='$newsize' WHERE `keyr`='$keyr'";
		if($GLOBALS["DEBUG_LEVEL"]>1){WLOG($sql);}
		$GLOBALS["Q"]->QUERY_SQL($sql);
		if(!$GLOBALS["Q"]->ok){WLOG($GLOBALS["Q"]->mysql_error);}
		return;
	}
	
	
	
	$familysite=$GLOBALS["Q"]->GetFamilySites($servername);
	$uid=mysql_escape_string2($uid);
	$sql="INSERT IGNORE INTO `$table` (`hour`,`keyr`,`ipaddr`,`familysite`,`servername`,`filetype`,`uid`,`MAC`,`size`) VALUES
	('$hour','$keyr','$ipaddr','$familysite','$servername','$type','$uid','$mac','$size')";
	if($GLOBALS["DEBUG_LEVEL"]>1){WLOG($sql);}
	$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){WLOG($GLOBALS["Q"]->mysql_error);}
	
}



function CheckQuota($CPINFOS){
return true;
	
	
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
