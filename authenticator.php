<?php
session_start();
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
include_once(dirname(__FILE__)."/ressources/class.iptables-chains.inc");
$GLOBALS["DEBUG"]=false;
$GLOBALS["OUTOUT"]=false;
$GLOBALS["ruleid"]=$_GET["ruleid"];
$SERVER_NAME=$_SERVER["SERVER_NAME"];
$HTTP_HOST=$_SERVER["HTTP_HOST"];
$GLOBALS["SSL"]=false;
if($_SERVER["HTTPS"]<>"off"){$GLOBALS["SSL"]=true;}
$HTTP_X_FORWARDED_FOR=$_SERVER["HTTP_X_FORWARDED_FOR"];
$HTTP_X_REAL_IP=$_SERVER["HTTP_X_REAL_IP"];
$ipaddr=$_GET["ipaddr"];
if(!isset($_GET["cachetime"])){$_GET["cachetime"]=15;}
if(!is_numeric($_GET["cachetime"])){$_GET["cachetime"]=15;}
Debuglogs("Receive connection from $HTTP_X_REAL_IP for HTTP_HOST = $HTTP_HOST ".count($_GET)." GET parameters",__FUNCTION__,__LINE__);


if($argv[1]=="--cas"){
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
	$GLOBALS["DEBUG"]=true;
	$GLOBALS["OUTOUT"]=true;
	cas_auth($argv[2],$argv[3]);
	die("DIE " .__FILE__." Line: ".__LINE__);
}

if($GLOBALS["DEBUG"]){
	while (list ($index, $alias) = each ($_GET) ){
		Debuglogs("HTTP_GET: $index: -> $alias",__FUNCTION__,__LINE__);
	
	}	
}

if(isset($_SESSION["AUTHENTICATOR_REDIRECT"])){
	Debuglogs("REDIRECT: {$_SESSION["AUTHENTICATOR_REDIRECT"]}",__FUNCTION__,__LINE__);
	echo "<html><head><meta http-equiv=\"refresh\" content=\"0; url={$_SESSION["AUTHENTICATOR_REDIRECT"]}\" />
	</head><body></body></html>";
	unset($_SESSION["AUTHENTICATOR_REDIRECT"]);
	return;
}



if(isset($_GET["results-page"])){
	if($GLOBALS["DEBUG"]){Debuglogs("-> results-page",__FUNCTION__,__LINE__);}
	send_result_page();
	exit;
}
if(isset($_GET["error-page"])){
	if($GLOBALS["DEBUG"]){Debuglogs("-> error-page",__FUNCTION__,__LINE__);}
	send_error_page();
	exit;
}
if(isset($_GET["pageid"])){
	if($GLOBALS["DEBUG"]){Debuglogs("-> send_pageid",__FUNCTION__,__LINE__);}
	send_pageid();
	exit;
}


$sesskey=$_GET["sesskey"];
$time=sessiontime();
if($time<$_GET["cachetime"]+1){
	Debuglogs("{$_SESSION[$sesskey]["AUTHENTICATOR_UID"]} Cached {$time}mn < {$_GET["cachetime"]}Mn",__FUNCTION__,__LINE__);
	header("HTTP/1.0 200 OK");
	die("DIE " .__FILE__." Line: ".__LINE__);
}

while (list ($index, $alias) = each ($_SERVER) ){
	Debuglogs("SERVER: $index: -> $alias",__FUNCTION__,__LINE__);

}


while (list ($index, $alias) = each ($_GET) ){
	Debuglogs("GET: $index: -> $alias",__FUNCTION__,__LINE__);
	
}

while (list ($index, $alias) = each ($_COOKIE) ){
	Debuglogs("COOKIE: $index: -> $alias",__FUNCTION__,__LINE__);

}

$gps=unserialize(base64_decode($_GET["gps"]));
while (list ($index, $type) = each ($gps) ){
	Debuglogs("GROUP: GroupID: $index, type:$type",__FUNCTION__,__LINE__);
}
$des=unserialize(base64_decode($_GET["des"]));

$MUST_AUTH=false;

while (list ($index, $type) = each ($des) ){
	Debuglogs("NEXT: GroupID: $index, type:$type",__FUNCTION__,__LINE__);
	if($type==0){$MUST_AUTH=true;}
	if($type==1){$MUST_AUTH=true;}
	if($type==2){$MUST_AUTH=true;}
	if($type==3){
		TraceZ("$HTTP_X_REAL_IP  uri:{$_GET['uri']}",$_GET["servername"]);
		while (list ($index, $key) = each ($_COOKIE) ){TraceZ("$HTTP_X_REAL_IP  Cookie $index \"$key\"",$_GET["servername"]);}
		while (list ($index, $key) = each ($_SERVER) ){TraceZ("$HTTP_X_REAL_IP  _SERVER $index \"$key\"",$_GET["servername"]);}
	}
	if($type==4){
		Debuglogs("NEXT: GroupID: $index, type:$type -> redirect_rule($index)",__FUNCTION__,__LINE__);
		if(redirect_rule($gps,$index)){die("DIE " .__FILE__." Line: ".__LINE__);}
	}
	
	if($type==5){
		if(cas_auth($index,$_GET["ruleid"])){
			header("HTTP/1.0 200 OK");
			Debuglogs("$HTTP_X_REAL_IP: Auth: Authenticated trough CAS rule {$_GET["ruleid"]}",__FUNCTION__,__LINE__);
			die("DIE " .__FILE__." Line: ".__LINE__);
		}else{
			header('HTTP/1.0 403 Unauthorized');
			die("DIE " .__FILE__." Line: ".__LINE__);
		}
	}
	
}

Debuglogs("$HTTP_X_REAL_IP: Auth: \"{$_SERVER['PHP_AUTH_USER']}\", uri:{$_GET['uri']}, rule:{$_GET["ruleid"]}",__FUNCTION__,__LINE__);
$banner=base64_decode($_GET["banner"]);
Debuglogs("$HTTP_X_REAL_IP: -> INIT",__FUNCTION__,__LINE__);
$GLOBALS["Q"]=new mysql_squid_builder();



if($MUST_AUTH){
	if(!isset($_SERVER['PHP_AUTH_USER']) OR ($_SERVER['PHP_AUTH_USER']==null)){
		header('WWW-Authenticate: Basic realm="'.$banner.'"');
		header('HTTP/1.0 401 Unauthorized');
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
}else{
	Debuglogs("HTTP/1.0 200 OK -> END",__FUNCTION__,__LINE__);
	header("HTTP/1.0 200 OK");
	die("DIE " .__FILE__." Line: ".__LINE__);
}





$auth=false;



if(CheckPassword_rule($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'],$_GET["ruleid"])){$auth=true;}

if(!$auth){
	header('WWW-Authenticate: Basic realm="'.$banner.'"');
	header('HTTP/1.0 401 Unauthorized');
	die("DIE " .__FILE__." Line: ".__LINE__);
}else{
	
	$_SESSION[$sesskey]["AUTHENTICATOR_TIME"]=time();
	$_SESSION[$sesskey]["AUTHENTICATOR_UID"]=$_SERVER['PHP_AUTH_USER'];
	Debuglogs("$HTTP_X_REAL_IP: -> OK Authenticated..",__FUNCTION__,__LINE__);
}

function sessiontime(){
	$sesskey=$_GET["sesskey"];
	if(!isset($_SESSION[$sesskey]["AUTHENTICATOR_TIME"])){return 9000000;}
	if(!isset($_SESSION[$sesskey]["AUTHENTICATOR_UID"])){return 9000000;}
	$last_modified = $_SESSION[$sesskey]["AUTHENTICATOR_TIME"];
	$data1 = $last_modified;
	$data2 = time();
	$difference = ($data2 - $data1);
	return round($difference/60);	
	
}


function CheckPassword_rule($username,$password,$ruleid){
	if(!isMustAuth($ruleid)){return true;}
	$sql="	SELECT
	authenticator_authlnk.ID,
	authenticator_authlnk.zorder,
	authenticator_auth.groupname,
	authenticator_auth.enabled,
	authenticator_auth.params,
	authenticator_auth.group_type,
	authenticator_authlnk.groupid
	FROM authenticator_authlnk,authenticator_auth
	WHERE authenticator_authlnk.ruleid='$ruleid'
	AND authenticator_authlnk.groupid=authenticator_auth.ID";
	$results = $GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){ErrorLogs($GLOBALS["Q"]->mysql_error,__FUNCTION__,__LINE__);return false;}
	
	$t=time();
	
	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$groupname=$ligne["groupname"];
		$group_type=$ligne["group_type"];
		$enabled=$ligne["enabled"];
		$params=unserialize(base64_decode($ligne["params"]));
		if($enabled==0){
			Debuglogs("Rule: $ruleid: $username : $groupname type( $group_type ) not enabled, SKIP",__FUNCTION__,__LINE__);
			continue;
		}
		
		Debuglogs("Rule: $ruleid: $username : $groupname type( $group_type ) Check ".count($params)." parameters",__FUNCTION__,__LINE__);
		if($group_type==0){
			if(local_ldap($username,$password)){
				return true;
			}
		}
		
		if($group_type==2){
			if(local_ad($username,$password,$params)){
				return true;
			}
		}		
	}	
	
	return false;
}

function local_ldap($username,$password){
	include_once(dirname(__FILE__)."/ressources/class.user.inc");
	$users=new user($username);
	if(!$users->UserExists){
		Debuglogs("$username : UserExists->False",__FUNCTION__,__LINE__);		
		return false;
	}
	if($users->password==$password){
		Debuglogs("$username : Authenticated...",__FUNCTION__,__LINE__);
		return true;
	}
	Debuglogs("$username : FAILED...",__FUNCTION__,__LINE__);
}

function local_ad($username,$password,$params){
	include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
	$array["LDAP_SERVER"]=$params["LDAP_SERVER"];
	$array["LDAP_PORT"]=$params["LDAP_PORT"];
	$array["WINDOWS_DNS_SUFFIX"]=$params["WINDOWS_DNS_SUFFIX"];
	$array["DEBUG"]=$GLOBALS["DEBUG"];
	
	Debuglogs("Active Directory : {$params["LDAP_SERVER"]}:{$params["LDAP_PORT"]} Check",__FUNCTION__,__LINE__);
	
	$external_ad_search=new external_ad_search(base64_encode(serialize($array)));
	if($external_ad_search->CheckUserAuth($username,$password)){
		Debuglogs("$username : Authenticated...",__FUNCTION__,__LINE__);
		return true;
	}
	Debuglogs("$username : FAILED...",__FUNCTION__,__LINE__);
	
}


function isMustAuth($ruleid){
	
	
	if(isset($_SESSION["isMustAuth-$ruleid"])){
		Debuglogs("rule:$ruleid -> _SESSION return {$_SESSION["isMustAuth-$ruleid"]}",__FUNCTION__,__LINE__);
		return $_SESSION["isMustAuth-$ruleid"];
	}
	
	$sql="
	SELECT
	authenticator_sourceslnk.ID,
	authenticator_sourceslnk.zorder,
	authenticator_sourceslnk.groupid,
	authenticator_groups.groupname,
	authenticator_groups.group_type,
	authenticator_groups.enabled
	FROM authenticator_sourceslnk,authenticator_groups
	WHERE authenticator_sourceslnk.ruleid='$ruleid'
	AND authenticator_sourceslnk.groupid=authenticator_groups.ID
	ORDER BY zorder
	";
	
	if(!isset($GLOBALS["Q"])){$GLOBALS["Q"]=new mysql_squid_builder();}
	Debuglogs("rule:$ruleid -> Running query on MySQL",__FUNCTION__,__LINE__);
	$results = $GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){ErrorLogs($GLOBALS["Q"]->mysql_error,__FUNCTION__,__LINE__);return true;}
	Debuglogs("rule:{$_GET["ruleid"]} -> ". mysqli_num_rows($results)." sources groups",__FUNCTION__,__LINE__);
	
	$t=time();
	
	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$enabled=$ligne["enabled"];
		if($enabled==0){continue;}
		Debuglogs("rule:{$_GET["ruleid"]} Group:{$ligne["groupname"]} (enabled=$enabled): type:{$ligne["group_type"]}",__FUNCTION__,__LINE__);
		if($ligne["group_type"]==0){
			Debuglogs("rule:{$_GET["ruleid"]} Group:{$ligne["groupname"]}: -> in All cases...",__FUNCTION__,__LINE__);
			$_SESSION["isMustAuth-$ruleid"]=true;
			return true;
		}
	}
	
	$_SESSION["isMustAuth-$ruleid"]=false;
	return false;
	
	
}

function redirect_rule($gps,$destinationid){
	
	Debuglogs("Destination: $destinationid",__FUNCTION__,__LINE__);
	if(!isset($_SESSION["AUTHENTICATOR_AUTH"]["$destinationid"])){
		$q=new mysql_squid_builder();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT params FROM authenticator_auth WHERE ID=$destinationid"));
		$_SESSION["AUTHENTICATOR_AUTH"]["$destinationid"]=$ligne["params"];
	}
	
	$params=unserialize(base64_decode($_SESSION["AUTHENTICATOR_AUTH"]["$destinationid"]));
	$url=$params["URI"];
	if($url==null){return false;}
	
	
	
	reset($gps);
	$DETECTED=false;
	while (list ($gpid, $type) = each ($gps) ){
		
		Debuglogs("Check Group ID $gpid for type=$type (".$GLOBALS["SOURCE_TYPE"][$type].")",__FUNCTION__,__LINE__);
		if(!isset($_SESSION["AUTHENTICATOR_ITEMS"][$gpid])){
			$q=new mysql_squid_builder();
			$sql="SELECT pattern FROM authenticator_items WHERE groupid=$gpid";
			$results = $q->QUERY_SQL($sql);
			while ($ligne = mysqli_fetch_assoc($results)) {$_SESSION["AUTHENTICATOR_ITEMS"][$gpid][]=$ligne["pattern"];}
			
		}
		
		reset($_SESSION["AUTHENTICATOR_ITEMS"][$gpid]);
		while (list ($none, $KEY_COOKIE) = each ($_SESSION["AUTHENTICATOR_ITEMS"][$gpid]) ){
			Debuglogs("If there a cookie `$KEY_COOKIE` ?",__FUNCTION__,__LINE__);
			if(!isset($_COOKIE[$KEY_COOKIE])){
				Debuglogs("`$KEY_COOKIE` is not present, break and perform action..",__FUNCTION__,__LINE__);
				$DETECTED=true;break;
			}
		}
		
		if($DETECTED){break;}
		
	}
	
	if($DETECTED){
		$url=format_uri($url);
		$_SESSION["AUTHENTICATOR_RESULTS"][$GLOBALS["ruleid"]]="redirect:$url";
		Debuglogs("Destination: $url",__FUNCTION__,__LINE__);
		header('HTTP/1.0 403 Forbidden');
		
		return true;
	}
	
	
}



function ErrorLogs($text=null,$function=null,$line=null){
	if($text==null){return;}
	$linetext=null;
	
	
	if(function_exists("debug_backtrace")){$trace=@debug_backtrace();}
	
	if(is_array($trace)){
		$filename=basename($trace[1]["file"]);
		$function=$trace[1]["function"];
		$line=$trace[1]["line"];
		$linetext="$function/$line $text";
	}else{
		$linetext=$text;
		if($function<>null){$linetext="$function/$line $linetext";}
	}
	
	if(function_exists("syslog")){
		$LOG_SEV=LOG_WARNING;
		openlog("authenticator", LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $text);
		closelog();
	
	}	
}


function Debuglogs($text=null,$function=null,$line=null){
	if($text==null){return;}
	$linetext=null;

	if($function==null){
		if(function_exists("debug_backtrace")){$trace=@debug_backtrace();}
		if(is_array($trace)){
			$filename=basename($trace[1]["file"]);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];	
		}
	}
	
	$linetext=$text;
	if($function<>null){$linetext="$function/$line $linetext";}else{
		if($line<>null){
			$linetext="$line $linetext";
		}
	}
	
	if($GLOBALS["OUTOUT"]){echo "$linetext\n";return;}
	
	if (is_file("/var/log/apache2/authenticator.log")) {
		$size=filesize("/var/log/apache2/authenticator.log");
		if($size>1000000){@unlink("/var/log/apache2/authenticator.log");}
	}
	
	$linetext=date("H:i:s")." ". $linetext;
	$f = @fopen("/var/log/apache2/authenticator.log", 'a');
	@fwrite($f, "$linetext\n");
	@fclose($f);
	

}

function send_result_page(){
	
	Debuglogs("_SESSION = \"{$_SESSION["AUTHENTICATOR_RESULTS"][$GLOBALS["ruleid"]]}\"",__FUNCTION__,__LINE__);
	
	if(!isset($_SESSION["AUTHENTICATOR_RESULTS"][$GLOBALS["ruleid"]])){
		echo "<H1>No results from last action</H1>";
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	
	if(preg_match("#^redirect:(.+)#", $_SESSION["AUTHENTICATOR_RESULTS"][$GLOBALS["ruleid"]],$re)){
		Debuglogs("Must a redirec to {$re[1]}",__FUNCTION__,__LINE__);
		echo "<html><head><meta http-equiv='refresh' content='0;url={$re[1]}'></head><body></body>";
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	
}

function nginx_attack(){
	
	$zDate=date('Y-m-d H:i:s');
	$HTTP_HOST=$_SERVER["HTTP_HOST"];
	$servername=$HTTP_HOST;
	$HTTP_X_REAL_IP=$_SERVER["HTTP_X_REAL_IP"];
	if($HTTP_X_REAL_IP=="127.0.0.1"){return;}
	$q=new mysql_squid_builder();
	$timekey=date('YmdH');
	$table="ngixattck_$timekey";
	$url=base64_decode($_GET["uencode"]);
	$localport=$_GET["localport"];
	
	if($GLOBALS["VERBOSE"]){
		Debuglogs("$HTTP_HOST $HTTP_X_REAL_IP $table",__FUNCTION__,__LINE__);
	}
	
	
	if(!is_numeric($localport)){$localport=80;}
	$ports[]=80;
	$ports[]=443;
	if($localport<>80){if($localport<>443){$ports[]=$localport;}}
	$hostname=null;
	$country=null;
	
	if(!isset($_SESSION["nginx_exploits_fw"][$servername])){
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT maxaccess,sendlogs FROM nginx_exploits_fw WHERE servername='$servername'"));
		$md5=md5("$zDate$servername$HTTP_X_REAL_IP");
		$md5L=md5("$servername$HTTP_X_REAL_IP");
		$maxaccess=$ligne["maxaccess"];
		$sendlogs=$ligne["sendlogs"];
		if(!is_numeric($maxaccess)){$maxaccess=0;}
		$_SESSION["nginx_exploits_fw"][$servername]["maxaccess"]=$maxaccess;
		$_SESSION["nginx_exploits_fw"][$servername]["sendlogs"]=$sendlogs;
		Debuglogs("$servername, maxaccess=$maxaccess, sendlogs={$ligne["sendlogs"]} table=$table",__FUNCTION__,__LINE__);
	}else{
		$maxaccess=$_SESSION["nginx_exploits_fw"][$servername]["maxaccess"];
		$sendlogs=$_SESSION["nginx_exploits_fw"][$servername]["sendlogs"];
	}

if(!isset($_SESSION["nginx_exploits_fw"]["BLOCKED"])){	
	if($maxaccess>0){
		$sendlogs=1;
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT COUNT(keyr) as tcount FROM `$table` WHERE ipaddr='$HTTP_X_REAL_IP' and `servername`='$servername'"));
		if(!$q->ok){Debuglogs("$q->mysql_error");}
		$Count=$ligne["tcount"];
		Debuglogs("Current $Count time(s)/$maxaccess",__FUNCTION__,__LINE__);
		$Count++;
		
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT `ipaddr` FROM `nginx_exploits_fwev` WHERE zmd5='$md5L'"));
		Debuglogs("$md5L = `{$ligne["ipaddr"]}",__FUNCTION__,__LINE__);
		

	}
}	

	
	if($sendlogs==1){
		if($country==null){$country=mysql_escape_string2(GeoLoc($HTTP_X_REAL_IP));}
		if($hostname==null){$hostname=gethostbyaddr($HTTP_X_REAL_IP);}
		$family=$q->GetFamilySites($hostname);
		$q->check_nginx_attacks_RT($timekey);
		$sql="INSERT IGNORE INTO $table (`keyr`,`servername`,`zDate`,`ipaddr`,`familysite`,`hostname`,`country`)
		VALUES('$md5','$servername','$zDate','$HTTP_X_REAL_IP','$family','$hostname','$country');";
		Debuglogs("$servername: Attack from $hostname [$HTTP_X_REAL_IP] - $country ");
		$q->QUERY_SQL($sql);
		if(!$q->ok){Debuglogs($q->mysql_error);}
	}
	
	
	
	
	
}
function GeoLoc($ipaddr){

	if(!function_exists("geoip_record_by_name")){return;}
	$record = @geoip_record_by_name($ipaddr);
	if ($record) {
		return $record["country_name"];
	}
	

}




function send_error_page(){
	$SERVER_NAME=$_SERVER["SERVER_NAME"];
	$HTTP_HOST=$_SERVER["HTTP_HOST"];
	$HTTP_X_FORWARDED_FOR=$_SERVER["HTTP_X_FORWARDED_FOR"];
	$HTTP_X_REAL_IP=$_SERVER["HTTP_X_REAL_IP"];	
	$REQUESTED_URI=$_GET["uri"];
	$uid=$_SERVER['PHP_AUTH_USER'];
	$error_id=$_GET["error-ID"];
	$MyError=intval($_GET["error-page"]);
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	
	if($GLOBALS["DEBUG"]){
		Debuglogs("$SERVER_NAME $REQUESTED_URI - $uid Error ID:$error_id",__FUNCTION__,__LINE__);
	}
	
	
	
	$GLOBALS["Q"]=new mysql_squid_builder();
	
	
	$error["400"]["TITLE"]="Bad Request";
	$error["400"]["EXPLAIN"]="The user's request contains incorrect syntax";

	$error["401"]["TITLE"]="Unauthorized";
	$error["401"]["EXPLAIN"]="The requested file requires authentication (a username and password).";
	
	$error["403"]["TITLE"]="Forbidden";
	$error["403"]["EXPLAIN"]="The server will not allow you to access the requested file.";
	
	
	$error["404"]["TITLE"]="Not Found";
	$error["404"]["EXPLAIN"]="The server could not find the file that you requested.<br>This error commonly occurs when a URL is mistyped.";
	
	
	$error["500"]["TITLE"]="Internal Server Error";
	$error["500"]["EXPLAIN"]="This error signifies that the server has encountered 
	an unexpected condition.<br>It is a &laquo;catch-all&raquo; error that will be displayed when 
	no specific information can be gathered by the server regarding the condition.<br>
	This error often occurs when an application request cannot be fulfilled due 
	to the application being misconfigured.";
	
	
	 
	$error["501"]["TITLE"]="Not Implemented";
	$error["501"]["EXPLAIN"]="This signifies that the HTTP method sent by the client is not supported 
	by the server.<br>
	It is most often caused by the server being out of date.<br>
	This error is very rare and generally requires that the web server be updated.";
	
	
	
	$error["502"]["TITLE"]="Bad Gateway";
	$error["502"]["EXPLAIN"]="This error is usually due to improperly configured proxy servers.<br>
	However, the problem may also arise when there is poor IP communication amongst back-end computers, 
	when the client’s ISP is overloaded, or when a firewall is functioning improperly.<br>
	The first step in resolving the issue is to clear the client’s cache.<br>
	This action should result in the a different proxy being used to resolve the web server’s content.";	
	
	$error["503"]["TITLE"]="Service Unavailable";
	$error["503"]["EXPLAIN"]="This error occurs when the server is unable to handle requests due to a 
	temporary overload or due to the server being temporarily closed for maintenance.<br>
	The error signifies that the server will only temporarily be down.<br>
	It is possible to receive other errors in place of 503.<br>	
	Contact the server administrator if this problem persists.";
	
	$error["504"]["TITLE"]="Gateway Timeout";
	$error["504"]["EXPLAIN"]="This occurs when this server somewhere along the chain does not receive 
	a timely response from a server further up the chain.<br>
	The problem is caused entirely by slow communication between upstream computers.<br>	
	To resolve this issue, contact the system administrator.";
	
	$error["505"]["TITLE"]="HTTP Version Not Supported";
	$error["505"]["EXPLAIN"]="This error occurs when the server refuses to support the HTTP protocol 
	that has been specified by the client computer.<br>
	It can be caused by the protocol not being specified properly by the client computer;
	for example, if an invalid version number has been specified.";
	
	
	$error["507"]["TITLE"]="Insufficient Storage";
	$error["507"]["EXPLAIN"]="This error indicates that the server is out of free memory.<br>
	It is most likely to occur when an application being requested cannot allocate the necessary 
	system resources for it to run.<br>
	To resolve the issue, the server’s hard disk may need to be cleaned of any unnecessary documents 
	to free up more hard disk space, its memory may need to be expanded, 
	or it may simply need to be restarted.<br>
	Please contact the system administrator for more information regarding this error message.";
	
	$error["509"]["TITLE"]="Bandwidth Limit Exceeded";
	$error["509"]["EXPLAIN"]="This error occurs when the bandwidth limit imposed by 
	the system administrator has been reached.<br>
	The only fix for this issue is to wait until the limit is reset in the following cycle.<br>	
	Consult the system administrator for information about acquiring more bandwidth.";
	
	
	$error["510"]["TITLE"]="Not Extended";
	$error["510"]["EXPLAIN"]="This error occurs when an extension attached to the HTTP request 
	is not supported by the web server.<br>	
	To resolve the issue, you may need to update the server.<br>
	Please consult the system administrator for more information.";
		
	$sock=new sockets();
	$ARTICAV=@file_get_contents("/usr/share/artica-postfix/VERSION");
	$title="Error: {$_GET["error-page"]}: ".$error[$_GET["error-page"]]["TITLE"];
	
	$content="<table class=\"w100 h100\">
	<tr>
	<td class=\"c m\">
	<table style=\"margin:0 auto;border:solid 1px #560000\">
	<tr>
	<td class=\"l\" style=\"padding:1px\">
	<div style=\"width:346px;background:#E33630\">
	<div style=\"padding:3px\">
	<div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\">
	<div style=\"background:#BF0A0A;padding:8px;border:solid 1px #FFF;color:#FFF\">
	<h1>$title</h1>
	</div>
	<div class=\"c\" style=\"font:bold 13px arial;text-transform:uppercase;color:#FFF;padding:8px 0\">
		{$error[$_GET["error-page"]]["TITLE"]}
	</div>
	<div style=\"background:#F7F7F7;padding:20px 28px 36px\">
	<div id=\"titles\">
	<h1>Request not allowed</h1> <h2>$uid</h2>
	</div> <hr>
	<div id=\"content\">
	<blockquote id=\"error\"> <p><b>{$error[$_GET["error-page"]]["EXPLAIN"]}</b></p> </blockquote>
	<p>The request:<a href=\"$REQUESTED_URI\">$REQUESTED_URI</a> cannot be displayed<br> 
	Please contact your service provider if you feel this is incorrect.
	</p>  <p>Generated by Artica Reverse Proxy <a href=\"http://www.articatech.net\">artica.fr</a></p>
	 <br> </div>  <hr> <div id=\"footer\"> <p>Artica version: $ARTICAV</p> <!-- %c --> </div> </div></div>
	</div>
	</td>
	</tr>
	</table>
	</td>
	</tr>
	</table>";
	$header=@file_get_contents(dirname(__FILE__)."/ressources/databases/squid.default.header.db");
	
	if($error_id>0){
		$users=new usersMenus();
		if($users->CORP_LICENSE){
			$sql="SELECT `title`,`headers`,`body` FROM nginx_error_pages WHERE ID='$error_id'";
			$q=new mysql_squid_builder();
			$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql));
			if(strlen($ligne["headers"])>10){$header=$ligne["headers"];}
			if(strlen($ligne["body"])>10){$content=$ligne["body"];}
			$title=$ligne["title"];
		}
	}
	
	
	if($MyError==403){
		if(!isset($_SESSION["ARTICA_RP_403_REFRESHED"])){
			$header=str_replace("<head>", "<head><META HTTP-EQUIV=\"Refresh\" CONTENT=\"1\">", $header);
			$_SESSION["ARTICA_RP_403_REFRESHED"]=true;
		}else{
			nginx_attack();
		}
		
	}
	
	
	
	
	$newheader=str_replace("{TITLE}", $title, $header);
	$newheader=str_replace("{ARTICA_VERSION}", $ARTICAV, $newheader);
	$newheader=str_replace("{uid}", $uid, $newheader);
	$newheader=str_replace("{error_code}", $_GET["error-page"], $newheader);
	$newheader=str_replace("{error_desc}", $error[$_GET["error-page"]]["EXPLAIN"], $newheader);
	$newheader=str_replace("{uri}", $REQUESTED_URI, $newheader);
	
	$content=str_replace("{ARTICA_VERSION}", $ARTICAV, $content);
	$content=str_replace("{uid}", $uid, $content);
	$content=str_replace("{TITLE}", $title, $content);
	$content=str_replace("{error_code}", $_GET["error-page"], $content);
	$content=str_replace("{error_desc}", $error[$_GET["error-page"]]["EXPLAIN"], $content);
	$content=str_replace("{uri}", $REQUESTED_URI, $content);
	
	
	
	$templateDatas="$newheader$content</body></html>";
	
	
	
	
	echo $templateDatas;
	
	
}

function format_uri($url){
	$keys["LANG"]=true;
	$keys["SHLVL"]=true;
	$keys["LANGUAGE"]=true;
	$keys["QUERY_STRING"]=true;
	$keys["REQUEST_METHOD"]=true;
	$keys["CONTENT_TYPE"]=true;
	$keys["CONTENT_LENGTH"]=true;
	$keys["SCRIPT_FILENAME"]=true;
	$keys["SCRIPT_NAME"]=true;
	$keys["PATH_INFO"]=true;
	$keys["REQUEST_URI"]=true;
	$keys["DOCUMENT_URI"]=true;
	$keys["SERVER_PROTOCOL"]=true;
	$keys["GATEWAY_INTERFACE"]=true;
	$keys["HTTP_HOST"]=true;
	$keys["HTTP_X_FORWARDED_FOR"]=true;
	$keys["HTTP_X_REAL_IP"]=true;
	$keys["HTTP_USER_AGENT"]=true;
	$keys["HTTP_ACCEPT"]=true;
	$keys["HTTP_ACCEPT_LANGUAGE"]=true;
	$keys["HTTP_ACCEPT_ENCODING"]=true;
	
	while (list ($index, $alias) = each ($keys) ){
		$url=str_replace("%$index%", $_SERVER[$index], $url);
		
	}
	
	if(preg_match_all("#%_GET_(.+?)%#", $url, $re)){
		while (list ($index, $geykey) = each ($re[1]) ){
			$url=str_replace("%_GET_$geykey%", $_GET[$geykey], $url);
		}
	}
	if(preg_match_all("#%_COOKIE_(.+?)%#", $url, $re)){
		while (list ($index, $geykey) = each ($re[1]) ){
			$url=str_replace("%_COOKIE_$geykey%", $_COOKIE[$geykey], $url);
		}
	}	
	
	return $url;
}


function TraceZ($text,$servername){
    $logFile="/var/log/apache2/$servername/authenticator.access.log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   			$size=filesize($logFile);
		   	if($size>1000000){unlink($logFile);}
   		}
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	$text=date("Y-m-d H:i:s")." $text";
	@fwrite($f, "$text\n");
	@fclose($f);
}

function if_groupes_matches($gps){
	
	
	while (list ($gpid, $type) = each ($gps) ){
	
		Debuglogs("Check Group ID $gpid for type=$type (".$GLOBALS["SOURCE_TYPE"][$type].")",__FUNCTION__,__LINE__);
		if($type==0){
			$MATCHES=true;
			Debuglogs("Group ID $gpid for type=$type MATCHES IN ALL CASES",__FUNCTION__,__LINE__);
			return true;
		}
	
		if(!isset($_SESSION["AUTHENTICATOR_ITEMS"][$gpid])){
			$q=new mysql_squid_builder();
			$sql="SELECT pattern FROM authenticator_items WHERE groupid=$gpid";
			$results = $q->QUERY_SQL($sql);
				
			while ($ligne = mysqli_fetch_assoc($results)) {
				Debuglogs("$gpid {$ligne["pattern"]}",__FUNCTION__,__LINE__);
				$_SESSION["AUTHENTICATOR_ITEMS"][$gpid][]=$ligne["pattern"];
					
			}
				
		}
	
		reset($_SESSION["AUTHENTICATOR_ITEMS"][$gpid]);
	}
	
	return $MATCHES;
	
}

// doit renvoyer http://auth.u-cergy.fr/login?service=http://biblioweb.u-cergy.org


function cas_auth($groupid,$ruleid){
	
	if(isset($_SESSION["CASUSER"][$groupid][$ruleid]["USER"])){
		Debuglogs("Rule:$ruleid Groupid:$groupid ->{$_SESSION["CASUSER"][$groupid][$ruleid]["USER"]}/{$_SESSION["CASUSER"][$groupid][$ruleid]["GPNAME"]} OK",__FUNCTION__,__LINE__);
		return true;
	}
	
	Debuglogs("Rule:$ruleid Groupid:$groupid Testing source groups...",__FUNCTION__,__LINE__);
	if(!isMustAuth($ruleid)){
		Debuglogs("Rule:$ruleid Groupid:$groupid From groups not match rule.",__FUNCTION__,__LINE__);
		return;
	}
	
	Debuglogs("Rule:$ruleid Groupid:$groupid From groups match rule.",__FUNCTION__,__LINE__);
	
	if(isset($_SESSION["AUTH_GROUP_DATA"][$groupid])){
		if($_SESSION["AUTH_GROUP_DATA"][$groupid]["params"]==null){
			unset($_SESSION["AUTH_GROUP_DATA"][$groupid]);
		}
	}
	
	if(!isset($_SESSION["AUTH_GROUP_DATA"][$groupid])){
		if(!isset($GLOBALS["Q"])){$GLOBALS["Q"]=new mysql_squid_builder();}
		Debuglogs("Rule:$ruleid Groupid:$groupid Run MySQL query",__FUNCTION__,__LINE__);
		$ligne=mysqli_fetch_array($GLOBALS["Q"]->QUERY_SQL("SELECT groupname,group_type,params FROM authenticator_auth WHERE ID='$groupid'"));
		if(!$GLOBALS["Q"]->ok){Debuglogs("Rule:{$_GET["ruleid"]} Groupid:$groupid {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__LINE__);}
		$_SESSION["AUTH_GROUP_DATA"][$groupid]["groupname"]=$ligne["groupname"];
		$_SESSION["AUTH_GROUP_DATA"][$groupid]["group_type"]=$ligne["group_type"];
		$_SESSION["AUTH_GROUP_DATA"][$groupid]["params"]=unserialize(base64_decode($ligne["params"]));
	}
	
	
	$groupname=$_SESSION["AUTH_GROUP_DATA"][$groupid]["groupname"];
	$group_type=$_SESSION["AUTH_GROUP_DATA"][$groupid]["group_type"];
	$params=$_SESSION["AUTH_GROUP_DATA"][$groupid]["params"];
		
	$cas_host=$params["CAS_HOST"];
	$cas_port=intval($params["CAS_PORT"]);
	$cas_context=$params["CAS_CONTEXT"];
	$certificate=$params["CAS_CERT"];
	$http_context="http";
	if($cas_port==443){	$http_context="https";}
	$mycontext="http";
	if($GLOBALS["SSL"]){$mycontext="https";}
	if($cas_context<>null){$cas_context="/$cas_context";}
	$uri="$http_context://$cas_host";
	
	Debuglogs("CAS URI: $uri",__FUNCTION__,__LINE__);
	Debuglogs("Request: {$_GET["uri"]}",__FUNCTION__,__LINE__);
	
	$MyServer=$_SERVER["HTTP_HOST"];
	if($_GET["servername"]<>null){$MyServer=$_GET["servername"];}
	
	
	$uri_renvoi="$uri$cas_context/login?service=$mycontext://$MyServer";
	
	Debuglogs("uri_renvoi = $uri_renvoi",__FUNCTION__,__LINE__);
	
	
	if(!preg_match("#\?ticket=(.+)#",$_GET["uri"],$re)){
		Debuglogs("No ticket found -> Redirect to \"$uri_renvoi\"",__FUNCTION__,__LINE__);
		$_SESSION["AUTHENTICATOR_REDIRECT"]=$uri_renvoi;
		return false;
	}
	

	$TGT=$re[1];
	$uri_check="$uri$cas_context/serviceValidate?ticket=$TGT&service=$mycontext://$MyServer";
	Debuglogs("Ticket found -> \"{$TGT}\"",__FUNCTION__,__LINE__);
	Debuglogs("Validate $uri_check",__FUNCTION__,__LINE__);
	$curl = curl_init("$uri_check");
	
	$t=time();
	curl_setopt($curl,  CURLOPT_HEADER, true);
	curl_setopt($curl,  CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl,  CURLOPT_FAILONERROR, true);
	curl_setopt($curl,  CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSLVERSION,'all');
	curl_setopt($curl,  CURLOPT_HTTPHEADER, array('Expect:'));
	curl_setopt($curl,  CURLOPT_POST, 0);
	curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
	
	$data = curl_exec($curl);
	$length=strlen($data);
	$CURLINFO_HTTP_CODE=curl_getinfo($curl,CURLINFO_HTTP_CODE);
	curl_close($curl);
	$distanceInSeconds = round(abs(time() - $t));
	Debuglogs("CAS response CODE:$CURLINFO_HTTP_CODE {$distanceInSeconds}s \"$data\"");
	if($CURLINFO_HTTP_CODE<>200){return false;}
	
	if(preg_match("#<cas:user>(.+?)</cas:user>#is", $data,$re)){
		Debuglogs("CAS USER \"{$re[1]}\" Stamp in memory");
		$_SESSION["CASUSER"][$groupid][$ruleid]["USER"]=$re[1];
		$_SESSION["CASUSER"][$groupid][$ruleid]["GPNAME"]=$groupname;
		return true;
	}
	
}


function xcas_auth($groupid,$ruleid){
	
	Debuglogs("Rule:$ruleid Groupid:$groupid Testing source groups...",__FUNCTION__,__LINE__);
	if(!isMustAuth($ruleid)){
		Debuglogs("Rule:$ruleid Groupid:$groupid From groups not match rule.",__FUNCTION__,__LINE__);
		return;
	}
	
	Debuglogs("Rule:$ruleid Groupid:$groupid From groups match rule.",__FUNCTION__,__LINE__);
	
	if(isset($_SESSION["AUTH_GROUP_DATA"][$groupid])){
		if($_SESSION["AUTH_GROUP_DATA"][$groupid]["params"]==null){
			unset($_SESSION["AUTH_GROUP_DATA"][$groupid]);
		}
	}
	
	if(!isset($_SESSION["AUTH_GROUP_DATA"][$groupid])){
		if(!isset($GLOBALS["Q"])){$GLOBALS["Q"]=new mysql_squid_builder();}
		Debuglogs("Rule:$ruleid Groupid:$groupid Run MySQL query",__FUNCTION__,__LINE__);
		$ligne=mysqli_fetch_array($GLOBALS["Q"]->QUERY_SQL("SELECT groupname,group_type,params FROM authenticator_auth WHERE ID='$groupid'"));
		if(!$GLOBALS["Q"]->ok){Debuglogs("Rule:{$_GET["ruleid"]} Groupid:$groupid {$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__LINE__);}
		$_SESSION["AUTH_GROUP_DATA"][$groupid]["groupname"]=$ligne["groupname"];
		$_SESSION["AUTH_GROUP_DATA"][$groupid]["group_type"]=$ligne["group_type"];
		$_SESSION["AUTH_GROUP_DATA"][$groupid]["params"]=unserialize(base64_decode($ligne["params"]));
	}
	
	
	$groupname=$_SESSION["AUTH_GROUP_DATA"][$groupid]["groupname"];
	$group_type=$_SESSION["AUTH_GROUP_DATA"][$groupid]["group_type"];
	$params=$_SESSION["AUTH_GROUP_DATA"][$groupid]["params"];
	
	
	include_once(dirname(__FILE__)."/ressources/externals/jasigcas/CAS.php");
	Debuglogs("Rule:$ruleid Groupid:$groupid checking group:$groupname type:$group_type",__FUNCTION__,__LINE__);	
	
	if(!preg_match("#\?ticket=(.+)#",$_GET["uri"],$re)){
		Debuglogs("Not ticket found in `{$_GET["uri"]}`",__FUNCTION__,__LINE__);
		return false;
		
	}
	
	
	
	//$_SESSION["USER"]=$user;
	//$_SESSION["CASTIME"]=time();
	
	
	if(preg_match("#\?ticket=(.+)#",$_GET["uri"],$re)){
		$ticket=$re[1];
		Debuglogs("{$_GET["uri"]} -> $ticket",__FUNCTION__,__LINE__);
		
		$uriToSend="https://auth.u-cergy.fr/serviceValidate?ticket=$ticket&service=http://{$_GET["servername"]}";
		Debuglogs("$uriToSend",__FUNCTION__,__LINE__);
		
		@unlink("/tmp/toto.txt");
		exec("wget \"$uriToSend\" -O /tmp/toto.txt");
		$tr=explode("\n",@file_get_contents("/tmp/toto.txt"));
		
		while (list ($index, $alias) = each ($tr) ){
			Debuglogs("$alias",__FUNCTION__,__LINE__);
			
		}
	}else{
		Debuglogs("{$_GET["uri"]} no pregmatch",__FUNCTION__,__LINE__);
	}
	
	
	

	
	
	if($GLOBALS["DEBUG"]){
		Debuglogs("Rule:$ruleid Groupid:$groupid checking group:$groupname set to debug",__FUNCTION__,__LINE__);
		phpCAS::setDebug("/var/log/apache2/cas.debug.log");
	
	}
	phpCAS::setDebug("/var/log/apache2/cas.debug.log");
	Debuglogs("for debug purpose cmdline should be \"". __FILE__." --cas $groupid $ruleid\"",__FUNCTION__,__LINE__);
	$cas_host=$params["CAS_HOST"];
	$cas_port=intval($params["CAS_PORT"]);
	$cas_context=$params["CAS_CONTEXT"];
	$certificate=$params["CAS_CERT"];
	Debuglogs("Using certificate: $certificate ",__FUNCTION__,__LINE__);
	Debuglogs("Rule:$ruleid Groupid:$groupid checking group:$groupname Initialize phpCAS host:$cas_host Port:\"$cas_port\" context=$cas_context",__FUNCTION__,__LINE__);
	phpCAS::client(CAS_VERSION_2_0, $cas_host, intval($cas_port), $cas_context);
	
	//phpCAS::proxy(CAS_VERSION_2_0, $cas_host, intval($cas_port), $cas_context);
	// For quick testing you can disable SSL validation of the CAS server.
// THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
// VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!

	if(is_file($certificate)){
		//Debuglogs("Using certificate: $certificate ",__FUNCTION__,__LINE__);
		//phpCAS::setCasServerCACert($certificate);
	}else{
		Debuglogs(" $certificate no such file",__FUNCTION__,__LINE__);
	}
	unset($_SESSION["AUTH_GROUP_DATA"]);
	
	Debuglogs("Rule:$ruleid Groupid:$groupid checking group:$groupname Initialize phpCAS setNoCasServerValidation()",__FUNCTION__,__LINE__);
	phpCAS::setNoCasServerValidation();
	phpCAS::setFixedServiceURL("http://biblioweb.u-cergy.org");
	
	//https://auth.u-cergy.fr/is/cas/serviceValidate?ticket=ST-956-Lyg0BdLkgdrBO9W17bXS&service=http://localhost/bling
	
	//phpCAS::forceAuthentication();
	if(!phpCAS::checkAuthentication()){
		Debuglogs("Rule:$ruleid Groupid:$groupid checking group:$groupname Initialize phpCAS, not authenticated",__FUNCTION__,__LINE__);
		return false;
	}
	// force CAS authentication
	//phpCAS::forceAuthentication();
	return true;


}
function _file_time_min($path){
	$last_modified=0;

	if(is_dir($path)){return 10000;}
	if(!is_file($path)){return 100000;}
	if($last_modified==0){$last_modified = filemtime($path);}
	$data1 = $last_modified;
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}

function send_pageid(){
	$ID=$_GET["pageid"];
	$cachepage="/home/reverse_pages_content/$ID";
	if(is_file($cachepage)){
		$ligne=unserialize(@file_get_contents($cachepage));
		$time=_file_time_min();
		if($time<$ligne["cachemin"]){
			$date=strtotime($ligne["zDate"]);
			header("Cache-Control: private, max-age=0");
			header("content-type: text/html; charset=ISO-8859-1");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s",$date) . " GMT");
			echo stripslashes($ligne["content"]);
			return;
		}
	}
	
	
	
	$q=new mysql_squid_builder();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_pages_content WHERE ID='$ID'"));
	
	$date=strtotime($ligne["zDate"]);
	header("Cache-Control: private, max-age=0");
	header("content-type: text/html; charset=ISO-8859-1");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s",$date) . " GMT");
	@unlink($cachepage);
	@file_put_contents($cachepage, serialize($ligne));
	echo stripslashes($ligne["content"]);
	
}

