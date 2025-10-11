<?php
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.mysql.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=@getmypid();}
	
	if (!defined("STDIN")) {define("STDIN", @fopen("php://stdin", "r"));}	
	
	LoadConfig();
	
	while (!feof(STDIN)) {
		$input = trim(fgets(STDIN));
		if(trim($input)==null){continue;}
		$array=@explode(" ", trim($input));
		
		$requested_server=$array[3];
		$requested_uri=$array[2];
		$host=uri_to_host($requested_uri);
		
		foreach ($array as $num=>$ligne){
			WriteMyLogs("$num = \"$ligne\"",__LINE__);
		}		
		
		if($host==$GLOBALS["EXTERNAL_HOST"]){@fwrite(STDOUT, "OK user=toto\n");continue;}
		
		
		

	
		@fwrite(STDOUT, "ERR\n");
		
	}

WriteMyLogs("Closing PID {$GLOBALS["MYPID"]}");

function uri_to_host($uri){
	$urlArray=parse_url($uri);
	$host=$urlArray["host"];
	if(preg_match("#^www\.(.+)#", $host,$re)){$host=$re[1];}
	return $host;
}
	
function LoadConfig(){
WriteMyLogs("Loading PID {$GLOBALS["MYPID"]}",__LINE__);	
$BaseDir="/etc/artica-postfix/settings/Daemons";
$GLOBALS["SquidSessionEngineExternalUrl"]=@file_get_contents("$BaseDir/SquidSessionEngineExternalUrl");
if($GLOBALS["SquidSessionEngineExternalUrl"]==null){
	WriteMyLogs("SquidSessionEngineExternalUrl is null assume http://www.articatech.net",__LINE__);
	$GLOBALS["SquidSessionEngineExternalUrl"]="http://www.articatech.net";
	
}

$GLOBALS["EXTERNAL_HOST"]=uri_to_host($GLOBALS["SquidSessionEngineExternalUrl"]);
WriteMyLogs("Config: Whitelisted host:{$GLOBALS["EXTERNAL_HOST"]}",__LINE__);	

}

	
function WriteMyLogs($text,$line=0){
	$mem=round(((memory_get_usage()/1024)/1000),2);
	$logFile="/var/log/squid/".basename(__FILE__).".log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>9000000){unlink($logFile);}
   	}
   	$date=date('m-d H:i:s');
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}][{$mem}MB][$line] $text\n");
	@fclose($f);
}	