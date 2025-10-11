<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$classunix=dirname(__FILE__)."/class.unix.inc";
if(!is_file($classunix)){$classunix="/opt/artica-agent/usr/share/artica-agent/ressources/class.unix.inc";}
include_once($classunix);
include_once(dirname(__FILE__)."/class.ini-frame.inc");

function sys_events($text){
	if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
		$pid=getmypid();
		$date=date("H:i:s");
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/framework.debug";
		$size=filesize($logFile);
		if($size>100000){unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$line="[$pid] $date $text\n";
		@fwrite($f,$line);
		@fclose($f);
		}
		
		
function file_time_min($path){
	if(!is_file($path)){
		if($GLOBALS["VERBOSE"]){echo "file_time_min() -> unable to stat $path\n";}
		return 100000;
		}
	 $last_modified = filemtime($path);
	 $data1 = $last_modified;
	 $data2 = time();
	$difference = ($data2 - $data1); 	 
	return round($difference/60);	 
}

function calc_time_min($time_source){
	$last_modified = $time_source;
	 $data1 = $last_modified;
	 $data2 = time();
	$difference = ($data2 - $data1); 	 
	return round($difference/60);	 	
	
}

function file_time_sec($path){
	if(!is_file($path)){return 100000;}
	$last_modified = filemtime($path);
	$data1 = $last_modified;
	$data2 = time();
	$difference = ($data2 - $data1); 	 
	return round($difference);	 
}


function Build_pid_func($file,$function){
	if(!isset($GLOBALS["CLASS_UNIX"])){ include_once(dirname(__FILE__)."/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
   	if(!is_object($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
	$mypid=getmypid();
	$base=basename($file);
	if(!is_dir("/etc/artica-postfix/pids.3")){@mkdir("/etc/artica-postfix/pids.3",null,true);}
	$pid_file="/etc/artica-postfix/pids.3/$base.$function.pid";
	if(!is_file($pid_file)){@file_put_contents($pid_file,$mypid);return true;}
	$pid=trim(@file_get_contents($pid_file));
	if($pid==null){@file_put_contents($pid_file,$mypid);return true;}
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){writelogs_framework("[$base:$function] . PID $pid already executed",__FUNCTION__,__FILE__,__LINE__);return false;}
	@file_put_contents($pid_file,$mypid);
	return true;
	
}

function CheckPID($pid,$base,$function,$file){
	if(!isset($GLOBALS["CLASS_UNIX"])){ include_once(dirname(__FILE__)."/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
   	if(!is_object($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
   	return $GLOBALS["CLASS_UNIX"]->process_exists($pid);
	}


function sys_THREAD_COMMAND_SET($zcommands){
	 if(!isset($GLOBALS["CLASS_UNIX"])){ include_once(dirname(__FILE__)."/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
	 if(!is_object($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
	 $GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($zcommands);
 	}

function sys_exec($cmd){
	sys_events("Execute:: $cmd");
	exec($cmd);
}

function find_program($strProgram) {
   if(!isset($GLOBALS["CLASS_UNIX"])){ include_once(dirname(__FILE__)."/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
   if(!is_object($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
   return $GLOBALS["CLASS_UNIX"]->find_program($strProgram);
   }

function sys_CRON_CREATE_SCHEDULE($ProgrammedTime,$Croncommand,$filename){
  $mailadmin=info_ADMIN_MAIL();
  $conf="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin\n";
  $conf=$conf."MAILTO=\"$mailadmin\"\n";
  $conf=$conf."$ProgrammedTime\troot\t$Croncommand >/dev/null 2>&1\n";
  sys_events("sys_CRON_CREATE_SCHEDULE:: create new entry $filename");
  file_put_contents("/etc/cron.d/$filename",$conf);
  chmod("/etc/cron.d/$filename",0640);
  chown("/etc/cron.d/$filename","root");
}

function info_ADMIN_MAIL(){
	if(!is_file('/etc/artica-postfix/smtpnotif.conf')){return null;}
	$ini=new iniFrameWork("/etc/artica-postfix/smtpnotif.conf");
	return $ini->get("SMTP","smtp_dest");
}
function LOCATE_PHP5_BIN2(){
  if(!isset($GLOBALS["CLASS_UNIX"])){ include_once(dirname(__FILE__)."/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
  if(!is_object($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__)."/class.unix.inc");$GLOBALS["CLASS_UNIX"]=new unix();}
  return $GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
	}
//############################################################################## 
//############################################################################# 

?>