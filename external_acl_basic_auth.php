#!/usr/bin/php
<?php
include_once("/usr/share/artica-postfix/ressources/class.radius.auth.inc");
//include_once("/usr/share/artica-postfix/ressources/external_acl_squid_ldap.php");

error_reporting(0);
if(preg_match("#--verbose#", @implode(" ", $argv))){
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);error_reporting(1);
	error_reporting(1);
	$GLOBALS["VERBOSE"]=true;
	echo "VERBOSED MODE\n";
}
  define(LDAP_OPT_DIAGNOSTIC_MESSAGE, 0x0032);
  $GLOBALS["SplashScreenURI"]=null;
  $GLOBALS["PID"]=getmypid();
  $GLOBALS["STARTIME"]=time();
  $GLOBALS["MACTUIDONLY"]=false;
  $GLOBALS["SESSION_TIME"]=array();
  $GLOBALS["F"] = @fopen("/var/log/squid/basic.auth.log", 'a');
  $GLOBALS["TIMELOG"]=0;
  $GLOBALS["QUERIES_NUMBER"]=0;
  $GLOBALS["TIMELOG_TIME"]=time();

  if($argv[1]=="--db"){ufdbguard_checks($argv[2]);	die(0);}
  $max_execution_time=ini_get('max_execution_time'); 
  LoadSessions();
  WLOG("[START]: Starting New process");
  
  
  
while (!feof(STDIN)) {
 $content = trim(fgets(STDIN));
 
 if($content<>null){
 	
 	$array=explode(" ",$content);
 	$ip = $array[0];
 	$user=$array[1];
 	$auth = urldecode($array[2]);
 	$ID=$array[3];
 	$authR=explode(" ",$auth);
 	$TypeAuth=$authR[0];
 	$Ident=base64_decode($authR[1]);
 	if(preg_match("#ID([0-9]+)#", $ID,$re)){$ID=$re[1];}
 	$GLOBALS["OBJECTID"]=$ID;
 
 	$identR=explode(":", $Ident);
 	$username=$identR[0];
 	unset($identR[0]);
 	$password=@implode(":", $identR);
 	
 	if (!CheckAuth($ID,$username,$password,$ip)){
 		WLOG("[FAILED]: ID = $ID; IP = $ip; ident:$username");
 		fwrite(STDOUT, "ERR\n");
 		continue;
 	}
 	

 	WLOG("[SUCCESS]: ID = $ID; IP = $ip; ident:$username");
 	fwrite(STDOUT, "OK\n");
 	
	}
}

SaveSessions();
$distanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
$distanceInMinutes = round($distanceInSeconds / 60);
WLOG("[STOP]: <span style='color:#002FB2'>Stopping process v1.0: After ({$distanceInSeconds}s - about {$distanceInMinutes}mn)</span>");
WLOG("[STOP]: <span style='color:#002FB2'>This process was query the LDAP server <strong>{$GLOBALS["QUERIES_NUMBER"]}</strong> times...</span>");
if(isset($GLOBALS["F"])){@fclose($GLOBALS["F"]);}


function CheckAuth($ID,$username,$password,$ipaddr){
	
	$GLOBALS["QUERIES_NUMBER"]++;
	
	
	$md5=md5("$ID,$username,$password,$ipaddr");
	if(isset($GLOBALS["SESSIONS"][$md5])){
		$time=$GLOBALS["SESSIONS"][$md5]["TIME"];
		$distanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
		$distanceInMinutes = round($distanceInSeconds / 60);
		if($distanceInMinutes<10){return $GLOBALS["SESSIONS"][$md5]["RESULTS"];}
	}
	$time=time();
	
	if(PerformAuth($ID,$username,$password)){
		$GLOBALS["SESSIONS"][$md5]["TIME"]=$time;
		$GLOBALS["SESSIONS"][$md5]["RESULTS"]=PerformAuth($ID,$username,$password);
		SaveSessions();
		return true;
	}
}

function PerformAuth($ID,$username,$password){
	$params=unserialize(base64_decode(@file_get_contents("/etc/squid3/AuthParams{$ID}.conf")));
	if(!is_array($params)){WLOG("[AUTH]: Failed, /etc/squid3/AuthParams{$ID}.conf cannot be decoded..\n");return false;}
	if(isset($params["RAD_SERVER"])){if(check_radius($params,$username,$password)){return true;}}
	if(isset($params["AD_LDAP_PORT"])){if(check_activedirectory($params,$username,$password)){return true;}}
	if(isset($params["OPENLDAP_SERVER"])){if(check_openldap($params,$username,$password)){return true;}}
}

function check_radius($params,$username,$password){
	$RAD_SERVER=$params["RAD_SERVER"];
	$RAD_PORT=$params["RAD_PORT"];
	$RAD_PASSWORD=$params["RAD_PASSWORD"];
	$retval=RADIUS_AUTHENTICATION($username,$password,$RAD_SERVER,$RAD_PORT,$RAD_PASSWORD);
	WLOG("[AUTH]: $RAD_SERVER:$RAD_PORT return $retval\n");
	if($retval==2){return true;}
	return false;
}

function check_activedirectory($params,$username,$password){

	$AD_SERVER=$params["AD_SERVER"];
	$AD_PORT=$params["AD_LDAP_PORT"];
	
	$ldap_connection=@ldap_connect($AD_SERVER,$AD_PORT);
	if(!$ldap_connection){
		WLOG("[AUTH]: Failed to connect to DC {$AD_SERVER}:$AD_PORT\n");
		@ldap_close();
		return false;
	}

	ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
	$bind=ldap_bind($ldap_connection, $username, $password);
	if(!$bind){
		@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
		$error=ldap_err2str(ldap_errno($ldap_connection));
		WLOG("Failed to login to DC $AD_SERVER `$error ($extended_error)` width $username");
		return false;
	}
	return true;	
	
}

function check_openldap($params,$username,$password){
	
	$OPENLDAP_SERVER=$params["OPENLDAP_SERVER"];
	$OPENLDAP_PORT=$params["OPENLDAP_PORT"];
	$OPENLDAP_SUFFIX=$params["OPENLDAP_SUFFIX"];
	$OPENLDAP_DN=$params["OPENLDAP_DN"];
	$OPENLDAP_PASSWORD=$params["OPENLDAP_PASSWORD"];
	$OPENLDAP_PASSWORD_ATTRIBUTE=$params["OPENLDAP_PASSWORD_ATTRIBUTE"];
	$OPENLDAP_FILTER=$params["OPENLDAP_FILTER"];
	
	$ldap_connection=@ldap_connect($OPENLDAP_SERVER,$OPENLDAP_PORT);
	if(!$ldap_connection){
		WLOG("[AUTH]: Failed to connect to OpenLDAP server {$OPENLDAP_SERVER}:$OPENLDAP_PORT\n");
		@ldap_close();
		return false;
	}
	
	ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
	$bind=ldap_bind($ldap_connection, $OPENLDAP_DN, $OPENLDAP_PASSWORD);
	if(!$bind){
		@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
		$error=ldap_err2str(ldap_errno($ldap_connection));
		WLOG("Failed to login to DC $OPENLDAP_SERVER `$error` width $OPENLDAP_DN $error ($extended_error)");
		return false;
	}	
	
	$OPENLDAP_FILTER=str_replace("%uid", $username, $OPENLDAP_FILTER);
	$filter=array($OPENLDAP_PASSWORD_ATTRIBUTE);
	$OPENLDAP_PASSWORD_ATTRIBUTED=strtolower($OPENLDAP_PASSWORD_ATTRIBUTE);
	$sr =@ldap_search($ldap_connection,$OPENLDAP_SUFFIX,$OPENLDAP_FILTER,$filter);
	if(!$sr){
		$error=ldap_err2str(ldap_errno($ldap_connection));
		@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
		WLOG("Unable to find $OPENLDAP_FILTER in $OPENLDAP_SUFFIX $error ($extended_error)");
		return false;
	}	
	
	$hash=@ldap_get_entries($ldap_connection,$sr);
	if($hash["count"]==0){WLOG("Unable to find $OPENLDAP_PASSWORD_ATTRIBUTE in $OPENLDAP_SUFFIX");return false;}
	
	for($i=0;$i<$hash[0][$OPENLDAP_PASSWORD_ATTRIBUTED]["count"];$i++){
		$Password=$hash[0][$OPENLDAP_PASSWORD_ATTRIBUTED][0];
		if($Password==$password){return true;}
		
	}
	
	return false;
	
}

function LoadSessions(){
	$GLOBALS["SESSIONS"]=unserialize(@file_get_contents("/etc/squid3/".basename(__FILE__).".cache"));
	WLOG("[START]:Loading ". count($GLOBALS["SESSIONS"]). "sessions in cache...\n");
	
}

function SaveSessions(){
	@file_put_contents("/etc/squid3/".basename(__FILE__).".cache", serialize($GLOBALS["SESSIONS"]));
}


function CleanSessions(){
	if(!isset($GLOBALS["SESSIONS"])){return;}
	if(!is_array($GLOBALS["SESSIONS"])){return;}
	$cachesSessions=unserialize(@file_get_contents("/etc/squid3/".basename(__FILE__).".cache"));
	if(isset($cachesSessions)){
		if(is_array($cachesSessions)){
			while (list ($md5, $array) = each ($cachesSessions)){$GLOBALS["SESSIONS"][$md5]=$array;}
		}
	}
	@file_put_contents("/etc/squid3/".basename(__FILE__).".cache", serialize($GLOBALS["SESSIONS"]));
}





function internal_find_program($strProgram){
	global $addpaths;
	$arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin','/usr/local/sbin','/usr/kerberos/bin');
	if (function_exists("is_executable")) {foreach($arrPath as $strPath) {$strProgrammpath = $strPath . "/" . $strProgram;if (is_executable($strProgrammpath)) {return $strProgrammpath;}}} else {return strpos($strProgram, '.exe');}
}



function WLOG($text=null){

	
	$text=chop($text);
	$filename="/var/log/squid/external-acl-ldap.log";
	$trace=@debug_backtrace();
	if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	if(!isset($GLOBALS["PID"])){$GLOBALS["PID"]=getmypid();}
	
	if($GLOBALS["OBJECTID"]>0){
		$filename="/var/log/squid/externalAcl{$GLOBALS["OBJECTID"]}Auth.log";
		if (is_file($filename)) {$size=@filesize($filename);if($size>1000000){unlink($filename);}}		
		$f= @fopen($filename, 'a');
		if($GLOBALS["VERBOSE"]){echo "$date [{$GLOBALS["PID"]}]: $text $called\n";}
		@fwrite($f, "$date [{$GLOBALS["PID"]}]: $text $called\n");
		@fclose($f);
		return;
	}
	
	
	
   	if (is_file($filename)) { 
   		$size=@filesize($filename);
   		if($size>1000000){
   			@fclose($GLOBALS["F"]);
   			unlink($filename);
   			$GLOBALS["F"] = @fopen($filename, 'a');
   		}
   	}
	if($GLOBALS["VERBOSE"]){echo "$date [{$GLOBALS["PID"]}]: $text $called\n";}
	@fwrite($GLOBALS["F"], "$date [{$GLOBALS["PID"]}]: $text $called\n");
}




?>
