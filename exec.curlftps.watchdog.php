<?php

include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.postfix.builder.inc');

start();
function start(){

	$unix=new unix();
	$pid=getmypid();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($GLOBALS["VERBOSE"]){echo "pidTime: $pidTime\n";}
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		if($pid<>$pid){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			
			if($time>120){
				ToSyslog("killing $pid  (line:  Line: ".__LINE__.")");
				unix_system_kill_force($pid);
			}else{
				exit();
			}
		}
	}
	
	@file_put_contents($pidfile, getmypid());
	
	
	$pidTimeEx=$unix->file_time_min($pidTime);
	if(!$GLOBALS["FORCE"]){
		if($pidTimeEx<240){
			ToSyslog("Waiting 240mn minimal - current ({$pidTimeEx}Mn)");
			return;
		}
	}
	
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	$ARRAY=array();
	$curlftps=$unix->find_program("curlftpfs");
	$pgrep=$unix->find_program("pgrep");
	
	exec("$pgrep -l -f \"$curlftps\" 2>&1",$results);

	foreach ($results as $index=>$line){
		$line=trim($line);
		if($line==null){continue;}
		$MOUNTED=null;
		$pidtime=0;
		$pid=0;
		if(preg_match("#pgrep#", $line)){continue;}
		if(preg_match("#^([0-9]+)\s+(.+)#",$line,$re) ){
			$pid=$re[1];
			$pidtime=$unix->PROCESS_TTL($pid);
			$cmdline=trim($re[2]);
			
			$cmdline=str_replace($curlftps, "", $cmdline);
			$cmdline=trim($re[2]);
			if($GLOBALS["VERBOSE"]){echo "Found $pid {$pidtime}Mn [$cmdline]\n";}
			$ARRAY[$pid]["TIME"]=$pidtime;
			if($GLOBALS["VERBOSE"]){echo "Explode $cmdline\n";}
			$TR=explode(" ",$cmdline);
			foreach ($TR as $bg){
				if($GLOBALS["VERBOSE"]){echo "Checks $bg\n";}
				if(substr($bg, 0,1)=="/"){
					$MOUNTED=$bg;
					if($GLOBALS["VERBOSE"]){echo "Found $pid {$pidtime}Mn mounted on $bg\n";}
					$ARRAY[$pid]["MOUNTED"]=$MOUNTED;
					break;}
			}
		}
		
	}
	
	if(count($ARRAY)==0){return;}
	$umount=$unix->find_program("umount");
	foreach ($ARRAY as $pid=>$ar){
	    if(!isset($ar["MOUNTED"])){continue;}
		$TIME=$ar["TIME"];
		$MOUNTED=$ar["MOUNTED"];
		if($TIME<960){continue;}
		ToSyslog("Umounting curlftps process id $pid mounted on $MOUNTED and running since {$TIME}mn, and exceed 960mn");
		shell_exec("$umount -l $MOUNTED");
		if($unix->process_exists($pid)){
			ToSyslog("Killing curlftps process id $pid");
			$unix->KILL_PROCESS($pid,9);
		}
	}
	
	
	


}


function ToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}



