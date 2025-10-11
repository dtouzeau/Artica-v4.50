<?php


function greensql_pid(){

	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/greensql-fw.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF("/usr/sbin/greensql-fw");

}

function greensql_status(){

	
	if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_GREENSQL_INSTALLED"))==0){
		$l[]="[APP_GREENSQL]";
		$l[]="service_name=APP_GREENSQL";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return implode("\n",$l);
	}
	
	$APP_GREENSQL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_GREENSQL_VERSION");
	$EnableGreenSQL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGreenSQL"));
	
	if($EnableGreenSQL==0){
		if(is_file("/etc/init.d/greensql-fw")){
			squid_admin_mysql(0, "Removing GreenSQL (not enabled)", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.greensql.php --uninstall");
			$l[]="[APP_GREENSQL]";
			$l[]="service_name=APP_GREENSQL";
			$l[]="installed=0";
			$l[]="service_disabled=0";
			return implode("\n",$l);
		}
	}

	$l[]="[APP_GREENSQL]";
	$l[]="service_name=APP_GREENSQL";
	$l[]="master_version=$APP_GREENSQL_VERSION";
	$l[]="service_cmd=greensql";
	$l[]="service_disabled=$EnableGreenSQL";
	$l[]="watchdog_features=1";
	$l[]="family=samba";
	$l[]="installed=1";
	

	$master_pid=greensql_pid();

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){squid_admin_mysql(0, "GreenSQL Firewall {stopped} [{action}={start}]", null,__FILE__,__LINE__);}
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.greensql.php --start");
		}
			
		$l[]="running=0";
		return implode("\n",$l);
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="running=1";
	return implode("\n",$l);
}
//========================================================================================================================================================
