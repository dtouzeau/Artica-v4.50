<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if($GLOBALS["VERBOSE"]){echo "DEBUG::: ".@implode(" ", $argv)."\n";}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["NOLOCK"]=true;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.squid.tail.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");



function ParseLogsDir(){
	$d=0;$h=0;
	$sock=new sockets();
	$workingDir=$sock->GET_INFO("SquidOldLogsDefaultDir");
	

if (!$handle = opendir($workingDir)) {@mkdir($workingDir,0755,true);return;}
$squidtail=new squid_tail();



while (false !== ($filename = readdir($handle))) {
	if($filename=="."){continue;}if($filename==".."){continue;}
	$targetFile="$workingDir/$filename";
	if(!is_file($targetFile)){continue;}
	$d++;$h++;$c=0;

	if($d>300){
		if(systemMaxOverloaded()){$array_load=sys_getloadavg();$internal_load=$array_load[0];events("ParseSquidLogBrutProcess()::$workingDir:: Overloaded: $internal_load system, break loop...",__LINE__);break;}
		$d=0;
	}
	
	
	$handle = @fopen($targetFile, "r");
	if (!$handle) {events("Failed to open file",__LINE__);continue;}
	while (!feof($handle)){
		$c++;
		$buffer =trim(fgets($handle, 4096));
		if(!$squidtail->parse_tail($buffer,null)){continue;}
		
		
	}
		
	
	
	
}


	
	
}
function events($text,$line=0){
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}if($line>0){$sourceline=$line;}$text="$text ($sourcefunction::$sourceline)";}
	events_tail($text);
}


function events_tail($text){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	//if($GLOBALS["VERBOSE"]){echo "$text\n";}
	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile="/var/log/artica-postfix/auth-tail.debug";
	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)." $date $text");
	@fwrite($f, "$pid ".basename(__FILE__)." $date $text\n");
	@fclose($f);
}