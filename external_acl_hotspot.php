#!/usr/bin/php -q
<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){
		$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); 
		if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } 
}
  ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);
  ini_set('error_reporting', E_ALL);
  ini_set("error_log", "/var/log/php.log");
  error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR);
  
  $GLOBALS["PID"]=getmypid();
  $GLOBALS["SPLASH_DEBUG"]=false;
  $GLOBALS["SPLASH"]=false;
  $GLOBALS["STARTIME"]=time();
  $GLOBALS["MACTUIDONLY"]=false;
  $GLOBALS["uriToHost"]=array();
  $GLOBALS["DEBUG_LEVEL"]=0;
  $GLOBALS["HotSpotHardwareIdent"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotHardwareIdent"));

  $max_execution_time=ini_get('max_execution_time'); 
  WLOG("Starting... Log level:{$GLOBALS["DEBUG_LEVEL"]};");

  
  
while (!feof(STDIN)) {
 	$url = trim(fgets(STDIN));
 	if($url==null){continue;}
 	$clt_conn_tag=null;
    $IPADDR2=null;
	if($GLOBALS["DEBUG_LEVEL"]>0){WLOG($url);}


    $TR=preg_split("/[\s]+/", $url);
    //5 - 192.168.1.114 d4:3b:04:b6:87:35
    $ConnexionID=$TR[0];
    $Username=$TR[1];
    $IPADDR=$TR[2];
    $MAC=$TR[3];
    if(isset($TR[4])) {
        $IPADDR2 = $TR[4];
    }



	
	if(preg_match("#([0-9a-z:]+)#", $url,$re)){$MAC=trim(strtolower($re[1]));}
	if($GLOBALS["DEBUG_LEVEL"]>0){WLOG("ASK: $MAC = ?");}
	if($MAC=="00:00:00:00:00:00"){
		fwrite(STDOUT, "OK\n");
		continue;
	}
	
	$uidArray=GetMacToUid($MAC);
	$uid=$uidArray["UID"];
	$ruleid=$uidArray["RULE"];
	if($GLOBALS["DEBUG_LEVEL"]>0){WLOG("ASK: $MAC = $uid");}
	if($uid==null){
		fwrite(STDOUT, "OK\n");
		continue;
	}
	
	if($uid<>null){
		$clt_conn_tag=" tag=HotspotRule$ruleid log=HotSpot,none";
		fwrite(STDOUT, "OK user=$uid{$clt_conn_tag}\n");
		continue;
	}
	
	fwrite(STDOUT, "OK\n");
	
	
}


$distanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
$distanceInMinutes = round($distanceInSeconds / 60);
WLOG("v1.0:". basename(__FILE__)." die after ({$distanceInSeconds}s/about {$distanceInMinutes}mn)");




function GetMacToUid($mac){
	if(isset($GLOBALS["GetMacToUid"][$mac])){return $GLOBALS["GetMacToUid"][$mac];}
	$sql="SELECT ruleid,uid FROM hotspot_sessions WHERE MAC='$mac'";
	if($GLOBALS["DEBUG_LEVEL"]>0){WLOG("GetMacToUid() $sql");}
	$ligne=@mysqli_fetch_array(api_QUERY_SQL($sql));
	if($ligne["uid"]<>null){
		$ruleid=$ligne["ruleid"];
		$GLOBALS["GetMacToUid"][$mac]=array("UID"=>$ligne["uid"],"RULE"=>$ruleid);
	}
	if(!isset($GLOBALS["GetMacToUid"][$mac])){return array();}
	return $GLOBALS["GetMacToUid"][$mac];
}

function api_QUERY_SQL($sql){
	if($GLOBALS["DEBUG_LEVEL"]){WLOG("api_QUERY_SQL::Call api_mysqli_connect");}
	$mysqli_connection=api_mysqli_connect();
	if(!$mysqli_connection){return false;}

	if($GLOBALS["DEBUG_LEVEL"]>0){WLOG("api_QUERY_SQL::Call mysql_select_db");}
	$ok=@mysqli_select_db($mysqli_connection,"squidlogs");
	if(!$ok){
		$errnum=@mysqli_errno($mysqli_connection);
		$des=@mysqli_error($mysqli_connection);
		@mysqli_close($mysqli_connection);
		WLOG("mysqli_select_db() failed (N:$errnum) \"$des\"");
		return false;
	}

	$mysql_unbuffered_query_log=null;
	$results=@mysqli_query($mysqli_connection,$sql);

	if(!$results){
		$errnum=@mysqli_errno($mysqli_connection);
		$des=@mysqli_error($mysqli_connection);
		@mysqli_close($mysqli_connection);
		WLOG("$mysql_unbuffered_query_log() failed (N:$errnum) \"$des\"");
		return false;
	}
	@mysqli_close($mysqli_connection);
	return $results;


}





function api_mysqli_connect(){


	
	if($GLOBALS["MYSQL_SOCKET"]<>null){
		$bd=@mysqli_connect("localhost",$GLOBALS["MYSQL_USERNAME"],$GLOBALS["MYSQL_PASSWORD"],null,0,$GLOBALS["MYSQL_SOCKET"]);
	}else{
		$bd=@mysqli_connect($GLOBALS["MYSQL_SERVER"],"{$GLOBALS["MYSQL_USERNAME"]}","{$GLOBALS["MYSQL_PASSWORD"]}",null,$GLOBALS["MYSQL_PORT"]);
	}

	if($bd){return $bd;}
	$des=@mysqli_error();
	$errnum=@mysqli_errno();
	WLOG("api_mysqli_connect() failed (N:$errnum) \"$des\"");
	return false;
}


function tool_time_min($timeFrom){
	$data1 = $timeFrom;
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}



function WLOG($text=null){
	
	$trace=@debug_backtrace();
	if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	$handle = @fopen("/var/log/squid/HotSpotToUid.log", 'a');
	
   	if (is_file("/var/log/squid/HotSpotToUid.log")) { 
   		$size=@filesize("/var/log/squid/HotSpotToUid.log");
   		if($size>1000000){
   			@fclose($handle);
   			unlink("/var/log/squid/HotSpotToUid.log");
   			$handle = @fopen("/var/log/squid/HotSpotToUid.log", 'a');
   		}
   		
   		
   	}
	
	
	@fwrite($handle, "$date ".basename(__FILE__)."[{$GLOBALS["PID"]}]: $text $called\n");
	@fclose($handle);
}


function GetComputerName($ip){return $ip;}

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
