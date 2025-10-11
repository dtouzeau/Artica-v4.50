<?php
if(!defined("LkdPTEQ")) {
	define("LkdPTEQ", base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2EvLkdPTEQ="));
}
if(function_exists("date_default_timezone_set") and function_exists("date_default_timezone_get")){@date_default_timezone_set(@date_default_timezone_get());}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){
		$GLOBALS["ARTICALOGDIR"]=null;
		if(is_file("/etc/artica-postfix/settings/Daemons/ArticaLogDir")){
			$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir");
		}
		if($GLOBALS["ARTICALOGDIR"]==null){ 
			$GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; 
		} 
}
ini_set("log_errors", 1);
ini_set("error_log", "/var/log/php.log");


function WriteToSyslogMail($text,$file=null,$error=false){
	if(!function_exists("syslog")){error_log($text);return;}
	if($file==null){
		if(function_exists("debug_backtrace")){
			$trace=debug_backtrace();
			$file=basename($trace[1]["file"]);
		}
	}
	$file=basename($file);
	if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
	openlog($file, LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
	}


function writeToFile($text,$filepath):bool{
	if(!is_writable($filepath)){return false;}
	$f = @fopen($filepath, 'a');
	@fwrite($f,$text);
	@fclose($f);
	return true;
}

function writelogs_meta($text,$function=null,$file=null,$line=0){
	$pid=getmypid();
	$file=basename($file);
	$date=date("Y-m-d H:i:s");
	if(!isset($GLOBALS["CLIENT_META_IP"])){$GLOBALS["CLIENT_META_IP"]="127.0.0.1";}
	$ip=$GLOBALS["CLIENT_META_IP"];
	$filelogs="/var/log/artica-meta.log";
	
	$line="$date [$pid] $file: $text  - function:$function in line:$line";
	writeToFile("$line\n",$filelogs);
	
}
function writelogs_stats($text,$function=null,$file=null,$line=0):bool{
	$pid=getmypid();
	$file=basename($file);
	$date=date("Y-m-d H:i:s");
	if(!isset($GLOBALS["CLIENT_META_IP"])){$GLOBALS["CLIENT_META_IP"]="127.0.0.1";}
	$filelogs="/var/log/stats-appliance.log";
	$line="$date [$pid] $file: $text  - function:$function in line:$line";
	return writeToFile("$line\n",$filelogs);

}

function VERBOSE($text,$line=0,$forceFunc=""):bool{
	$function=null;$file=null;
	if(!$GLOBALS["VERBOSE"]){return false;}
	if(!isset($GLOBALS["AS_ROOT"])){$GLOBALS["AS_ROOT"]=false;}
	if(!isset($GLOBALS["posix_getuid"])){
			$GLOBALS["posix_getuid"]=1000;
			if(function_exists("posix_getuid")){
				$GLOBALS["posix_getuid"]=posix_getuid();
			}
	}

	if($GLOBALS["posix_getuid"]==0){$GLOBALS["AS_ROOT"]=true;}

	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$file=basename($trace[1]["file"]);
			if(isset($trace[1]["function"])){$function=$trace[1]["function"];}
			if(isset($trace[1]["class"])){$function="{$trace[1]["class"]}/$function";}
		}
	}
	
	if($GLOBALS["AS_ROOT"]){echo "[L.$line]: $text\n";return true;}
	$date=date("I:s:ss");
	
	
	$func="{$function}[$line]";
	if(strlen($forceFunc)>2){
		$func=$forceFunc;
	}
	echo "<div style='font-size:14px;font-weight:bold;background-color:white;color:black'><span style=color:red>$date&nbsp;$func:</span>&nbsp;$text&nbsp;<small>($file)</small></div>\n";
	return true;
}

function writelogs($text="",$function="",$file_source="",$line=""):bool{
	$arr=array();
	if(strlen($text)>2000){$text=substr($text, 0,2000);}
	if(!isset($GLOBALS["MEMORY_LOGS"])){$GLOBALS["MEMORY_LOGS"]=array();}
	if(!isset($GLOBALS["ARTICA"]["EVENTS"])){$GLOBALS["ARTICA"]["EVENTS"]=array();}
	if(count($GLOBALS["ARTICA"]["EVENTS"])>100){$GLOBALS["ARTICA"]["EVENTS"]=array();}
	if(count($GLOBALS["MEMORY_LOGS"])>100){$GLOBALS["MEMORY_LOGS"]=array();}
	$GLOBALS["ARTICA"]["EVENTS"][]=date('H:i:s')." $text $function ". basename((string) $file_source)." in line $line\n";



	$name=null;
	if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
	if(isset($_SESSION["uid"])){$name="[{$_SESSION["uid"]}]";}
	if($file_source==null){$file_source= __FILE__ ;}
	$pid=0;
	$timezone=null;$formattedDate=null;
	$prefix="[CONSOLE]::";
	if(function_exists("posix_getuid")){if(posix_getuid()==0){$prefix="[ROOT]::";}}
	if(function_exists("getmypid")){$pid=getmypid();}

	if($function<>null) {
		if (preg_match("#(.+?)=>(.+)#", $function, $re)) {
			$function = "$re[1]/$re[2]";
		}
	}
	
	$p=dirname(__FILE__);
	$p=str_replace("/ressources","",$p);
	$file_source=basename($file_source);
	$file_source=str_replace("$p/","",$file_source);
	
	if($line<>null){$line=" line $line";}
	$file_source=str_replace("/usr/share/artica-postfix/","",$file_source);
	$function=str_replace("/usr/share/artica-postfix/","",$function);
	$file_source=str_replace("/home/dtouzeau/developpement/artica-postfix/","",$file_source);
	if($GLOBALS["VERBOSE"]){if(function_exists("VERBOSE")){VERBOSE("$prefix::$function:: $text",$line);}else{echo "[$pid] $prefix::$function:: $text in $file_source$line\n";}}
	$GLOBALS["MEMORY_LOGS"][]="[$pid] $prefix::$function:: $text in $file_source$line";
	if(!isset($_SERVER["REMOTE_ADDR"])){$_SERVER["REMOTE_ADDR"]="LOCAL";}
	if(function_exists("posix_getuid")){
		if(posix_getuid()>0){
			if(isset($_SESSION["uid"])){
				if($_SESSION["uid"]==-100){$name="[SuperAdmin]";}
			}
		}
	}
	try {
		$timezone = new DateTimeZone(date_default_timezone_get());
	} catch (DateInvalidTimeZoneException $e) {
		$text=$e->getMessage()." $text";
	}
	try {
		$date = new DateTime("now", $timezone);
		$formattedDate = $date->format("d-M-Y H:i:s") . " " . $timezone->getName();
	} catch (DateMalformedStringException $e) {
		$text=$e->getMessage()." $text";
	}

	$text="[$formattedDate] [$pid] $prefix::$name:$function:: $text in $file_source$line\n";

	if(is_writable("/usr/share/artica-postfix/ressources/logs/php.log")) {
		return error_log($text, 3, "/usr/share/artica-postfix/ressources/logs/php.log");
	}
	return false;

}
function phpxlog($text):bool{
	return error_log($text);
	
}

function writeOtherlogs($file,$text=null):bool{
	$directory=dirname($file);
	if(!is_dir($directory)) {
		@mkdir($directory, 0755, true);
	}
	$logFile=$file;
	if(!is_writable($logFile)){return false;}
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>1000000){@unlink($logFile);}
   	}
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$text\n");
	@fclose($f);
	return true;
}

function writepostfixlogs($text=null,$function=null,$file_source=null){
	
if($file_source==null){$file_source= __FILE__ ;}
		$file_source=basename($file_source);
if(!is_dir('/usr/share/artica-postfix/ressources/logs/web')){@mkdir('/usr/share/artica-postfix/ressources/logs/web',0755,true);}
		    $logFile="/usr/share/artica-postfix/ressources/logs/web/interface-postfix.log";
		    if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   		if (is_file($logFile)) { 
   			$size=filesize($logFile);
		    	if($size>1000000){@unlink($logFile);}
   		}
		$logFile=str_replace("//","/",$logFile);
		$f = @fopen($logFile, 'a');
		$date=date("Y-m-d H:i:s");
		if(!isset($_SERVER['REMOTE_ADDR'])){$_SERVER['REMOTE_ADDR']="127.0.0.1";}
		$text="$date:($file_source) [$function()][{$_SERVER['REMOTE_ADDR']}]:: $text\n";
		if($GLOBALS["DEBUG"]){echo $text;}
		@fwrite($f,$text );
		@fclose($f);}function writesquidlogs($text=null,$function=null,$file_source=null){
	
if($file_source==null){$file_source= __FILE__ ;}
		$file_source=basename($file_source);
		if(!is_dir('/usr/share/artica-postfix/ressources/logs/web')){@mkdir('/usr/share/artica-postfix/ressources/logs/web',0755,true);}
		    $logFile="/usr/share/artica-postfix/ressources/logs/web/interface-squid.log";
		    if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   		if (is_file($logFile)) { 
   			$size=filesize($logFile);
		    	if($size>1000000){@unlink($logFile);}
   		}
		 $logFile=str_replace("//","/",$logFile);
		$f = @fopen($logFile, 'a');
		$date=date("Y-m-d H:i:s");
		@fwrite($f, "$date:($file_source) [$function()][{$_SERVER['REMOTE_ADDR']}]:: $text\n");
		@fclose($f);}function write_syslog($text,$file){$file=basename($file);if(!function_exists('syslog')){return null;}openlog($file, LOG_PID | LOG_PERROR, LOG_LOCAL0);syslog(LOG_INFO, $text);closelog();}
		
?>
