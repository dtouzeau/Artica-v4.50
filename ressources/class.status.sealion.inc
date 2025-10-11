<?php


function sealion_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/usr/local/sealion-agent/var/run/sealion.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return 0;

}


function sealion_agent(){
	$l[]="[APP_SEALION_AGENT]";
	$l[]="service_name=APP_SEALION_AGENT";
	$enabled=1;
	$l[]="service_cmd=/etc/init.d/sealion";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";

	if(!is_file("/usr/local/sealion-agent/etc/init.d/sealion")){
		$l[]="running=0\ninstalled=0";
		$l[]="";
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SealionAgentInstalled", 0);
		return implode("\n",$l);
	}
	
	$data=@file_get_contents("/usr/local/sealion-agent/etc/agent.json");
	$array=json_decode($data);
	
	
	$l[]="master_version=".$array->agentVersion;
	$l[]="service_disabled=1";
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SealionAgentInstalled", 1);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SealionAgentVersion", $array->agentVersion);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SealLionMD5", $array->orgToken);
	
	
	
	$master_pid=sealion_pid();
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "{warning} Sealion Agent service is {stopped} [{action}={start}]", null,__FILE__,__LINE__);
			$cmd="/etc/init.d/sealion start";
			shell_exec2($cmd);
	
		}
	
	
		
		$l[]="running=0";
		$l[]="installed=1";
		$l[]="";
		return implode("\n",$l);
	
	}
	$l[]="master_pid=$master_pid";
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
	
}
