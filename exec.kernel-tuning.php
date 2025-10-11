<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.templates.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.ini.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::framework/class.unix.inc\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::frame.class.inc\n";}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
$GLOBALS["RELOAD"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NO_USE_BIN"]=false;
$GLOBALS["REBUILD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["REBOOT"]=false;
$GLOBALS["PREFIX_OUPUT"]="{$GLOBALS["PREFIX_OUPUT"]} ";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}


if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--withoutloading#",implode(" ",$argv))){$GLOBALS["NO_USE_BIN"]=true;}
if($argv[1]=="--squid"){$GLOBALS["PREFIX_OUPUT"]="Starting......: ".date("H:i:s")." Squid, kernel:";}
if($argv[1]=="--reboot"){$GLOBALS["REBOOT"]=true;}
if($argv[1]=="--sysctl"){sysctl_start();exit();}
if($argv[1]=="--shmall"){shared_memory();exit();}
$unix=new unix();
$sock=new sockets();
$sysctl=$unix->find_program("sysctl");

$DEFAULTS=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KernelTuning")));

$f=file("/etc/sysctl.conf");
foreach ( $f as $index=>$line ){
	if(preg_match("#^\##", $line)){continue;}
	if(preg_match("#(.*?)=(.*)#" , $line,$re)){
		$SYSCTLCONF[trim($re[1])]=trim($re[2]);
	}
	
}


$DEFAULTS["vm.overcommit_memory"]=1;
$DEFAULTS["kernel.panic"]=10;

foreach ($DEFAULTS as $key=>$value){
		echo "{$GLOBALS["PREFIX_OUPUT"]} tuning $key = `$value`\n";
		if($value==null){continue;}
		$SYSCTLCONF[$key]=$value;
		$cmd[]="$sysctl -w $key=$value";
		
	}



	$ARRAY=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kernel_values")));
	if(!is_array($ARRAY)){echo "{$GLOBALS["PREFIX_OUPUT"]} tuning (squid) not set..\n";}
	if(count($ARRAY)<2){echo "{$GLOBALS["PREFIX_OUPUT"]} tuning (squid) not set..\n";}
	if(count($ARRAY)>2){
		$ARRAY["overcommit_memory"]=1;
		$SYSCTLCONF["vm.swappiness"]=$ARRAY["swappiness"];
		//$SYSCTLCONF["vm.vfs_cache_pressure"]=$ARRAY["vfs_cache_pressure"];
		$SYSCTLCONF["vm.overcommit_memory"]=$ARRAY["overcommit_memory"];
		$SYSCTLCONF["net.ipv4.tcp_max_syn_backlog"]=$ARRAY["tcp_max_syn_backlog"];
		echo "{$GLOBALS["PREFIX_OUPUT"]} tuning vm.swappiness={$ARRAY["swappiness"]}\n";
		if(isset($ARRAY["vfs_cache_pressure"])){echo "{$GLOBALS["PREFIX_OUPUT"]} tuning vm.vfs_cache_pressure={$ARRAY["vfs_cache_pressure"]}\n";}
		echo "{$GLOBALS["PREFIX_OUPUT"]} tuning vm.overcommit_memory={$ARRAY["overcommit_memory"]}\n";
		echo "{$GLOBALS["PREFIX_OUPUT"]} tuning net.ipv4.tcp_max_syn_backlog={$ARRAY["tcp_max_syn_backlog"]}\n";
		
		$cmd[]="$sysctl -w vm.swappiness={$ARRAY["swappiness"]}";
		if(isset($ARRAY["vfs_cache_pressure"])){$cmd[]="$sysctl -w vm.vfs_cache_pressure={$ARRAY["vfs_cache_pressure"]}";}
		$cmd[]="$sysctl -w vm.overcommit_memory=1";
		$cmd[]="$sysctl -w net.ipv4.tcp_max_syn_backlog={$ARRAY["tcp_max_syn_backlog"]}";
	}
	
	if(count($cmd)==0){exit();}

	foreach ($SYSCTLCONF as $num=>$ligne){
		if($ligne==null){continue;}
		$tt[]="$num=$ligne";
		
	}
	@file_put_contents("/etc/sysctl.conf", @implode("\n", $tt));
	echo "{$GLOBALS["PREFIX_OUPUT"]} tuning saving sysctl.conf with ". count($tt)." values\n";	
	if($GLOBALS["REBOOT"]){
		shell_exec("/usr/sbin/artica-phpfpm-service -reboot");
		exit();
	}
	shell_exec("$sysctl -p");
	echo "{$GLOBALS["PREFIX_OUPUT"]} please wait while executing ". count($cmd)." commands\n";

	foreach ($cmd as $num=>$ligne){
		if($GLOBALS["VERBOSE"]){echo "$ligne\n";}
		shell_exec($ligne." >/dev/null 2>&1");
	}	
	
	
function sysctl_start(){
$unix=new unix();

$sock=new sockets();
$sysctl=$unix->find_program("sysctl");
$nohup=$unix->find_program("nohup");
$DEFAULTS=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KernelTuning")));	
$ARRAY=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kernel_values")));
$run=false;
if(is_array($DEFAULTS)){	$run=true;}else{echo "{$GLOBALS["PREFIX_OUPUT"]} `KernelTuning` no config\n";}
if(is_array($ARRAY)){$run=true;}else{echo "{$GLOBALS["PREFIX_OUPUT"]} `kernel_values` no config\n";}


	if($run){
		echo "{$GLOBALS["PREFIX_OUPUT"]} running kernel setup. (sysctl.conf)\n";
		shared_memory();
		shell_exec("$sysctl -w vm.overcommit_memory=1 >/dev/null 2>&1 &");
		shell_exec("$nohup $sysctl -p >/dev/null 2>&1 &");
	}else{
		echo "{$GLOBALS["PREFIX_OUPUT"]} running kernel no setup..\n";
	}
}

function shared_memory():bool{
return true;}

?>	
	
	