#!/usr/bin/php
<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$GLOBALS["DEBUG_GROUPS"]=0;
include_once("/usr/share/artica-postfix/ressources/class.ldap-extern.inc");
$GLOBALS["F"] = @fopen("/var/log/squid/external-acl.log", 'a');
error_reporting(0);


if(preg_match("#--verbose#", @implode(" ", $argv))){
	ini_set('display_errors', 1);	
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	error_reporting(1);
	$GLOBALS["VERBOSE"]=true;
	echo "VERBOSED MODE\n";
}
  define(LDAP_OPT_DIAGNOSTIC_MESSAGE, 0x0032);
  $GLOBALS["SplashScreenURI"]=null;
  $GLOBALS["PID"]=getmypid();
  $GLOBALS["STARTIME"]=time();
  $GLOBALS["MACTUIDONLY"]=false;
  $GLOBALS["uriToHost"]=array();
  $GLOBALS["SESSION_TIME"]=array();
  $GLOBALS["LDAP_TIME_LIMIT"]=10;
  $GLOBALS["BASENAME"]=basename(__FILE__);
  
  if(!isset($GLOBALS["DEBUG_GROUPS"])){
	  $GLOBALS["DEBUG_GROUPS"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternalLDAPDebug"));
	  if(!is_numeric($GLOBALS["DEBUG_GROUPS"])){
	  	WLOG("[START]: DEBUG_GROUP not a numeric, define it to 0");
	  	$GLOBALS["DEBUG_GROUPS"]=0;
	  }
  }
 
 
  $GLOBALS["TIMELOG"]=0;
  $GLOBALS["QUERIES_NUMBER"]=0;
  $GLOBALS["TIMELOG_TIME"]=time();
  $ldapext=new ldap_extern();
while (!feof(STDIN)) {
 $content = trim(fgets(STDIN));
 
 if($content<>null){
 	
 	if($GLOBALS["DEBUG_GROUPS"]>0){ WLOG("receive content...\"$content\""); }
 	$array=explode(" ",$content);
 	$member=trim($array[0]);
 	$member=str_replace("%20", " ", $member);
 	$groupDN=$array[1];
 	if(!preg_match("#ExtLDAP:(.+?):(.+)#", $groupDN,$re)){
 		WLOG("Wrong ACL pattern $groupDN"); 
 		fwrite(STDOUT, "OK\n");
 		continue;
 	}
 	$LOGNAME=$re[1];
 	$DN=base64_decode($re[2]);
 	$member=trim(strtolower($member));
	if($GLOBALS["DEBUG_GROUPS"] >0){ WLOG("Checking $member in $LOGNAME ($DN)"); }
	$MEMBERS=$ldapext->HashUsersFromGroupDN($DN);
	if(!isset($MEMBERS[$member])){
		fwrite(STDOUT, "ERR\n");
		continue;
	}
	
	fwrite(STDOUT, "OK\n");
	

	}
	
}

CleanSessions();
$distanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
$distanceInMinutes = round($distanceInSeconds / 60);
WLOG("[STOP]: Stopping process After ({$distanceInSeconds}s - about {$distanceInMinutes}mn)");




if(isset($GLOBALS["F"])){@fclose($GLOBALS["F"]);}


function CleanSessions(){
	if(!isset($GLOBALS["SESSIONS"])){return;}
	if(!is_array($GLOBALS["SESSIONS"])){return;}

}

function internal_find_program($strProgram){
	global $addpaths;
	$arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin','/usr/local/sbin','/usr/kerberos/bin');
	if (function_exists("is_executable")) {foreach($arrPath as $strPath) {$strProgrammpath = $strPath . "/" . $strProgram;if (is_executable($strProgrammpath)) {return $strProgrammpath;}}} else {return strpos($strProgram, '.exe');}
}



function WLOG($text=null){
	$filename="/var/log/squid/external-acl-ldap.log";
	$trace=@debug_backtrace();
	if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	if(!isset($GLOBALS["PID"])){$GLOBALS["PID"]=getmypid();}
   	if (is_file($filename)) { 
   		$size=@filesize($filename);
   		if($size>1000000){
   			@fclose($GLOBALS["F"]);
   			unlink($filename);
   			$GLOBALS["F"] = @fopen($filename, 'a');
   		}
   	}
	if($GLOBALS["VERBOSE"]){echo "$date ".basename(__FILE__)." [{$GLOBALS["PID"]}]: $text $called\n";}
	@fwrite($GLOBALS["F"], "$date [{$GLOBALS["BASENAME"]}/{$GLOBALS["PID"]}]: $text $called\n");
}
?>
