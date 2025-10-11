<?php


function unifi_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/unifi/unifi.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("/usr/lib/unifi/lib/ace.jar");

}
function unifi_mongodb_version(){
	if(isset($GLOBALS["unifi_mongodb_version"])){return $GLOBALS["unifi_mongodb_version"];}
	exec("/usr/lib/unifi/bin/mongod --version 2>&1",$results);
	foreach ($results as $key=>$value){
		if(preg_match("#version v([0-9\-\.a-z]+)#", $value,$re)){
			$GLOBALS["unifi_mongodb_version"]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "VERSION: $value...\n";}
			return $GLOBALS["unifi_mongodb_version"];
		}
	}
}
function unifi_mongodb_pid(){
	return $GLOBALS["CLASS_UNIX"]->PIDOF("/usr/lib/unifi/bin/mongod");

}


function unifi_mongodb(){
	$l[]="[UNIFI_MONGODB]";
	$l[]="service_name=UNIFI_MONGODB";
	$enabled=1;
	$l[]="service_cmd=/etc/init.d/unifi";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";

	if(!is_file("/usr/lib/unifi/bin/mongod")){
		$l[]="running=0\ninstalled=0";
		$l[]="";
		return implode("\n",$l);
	}

	$enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnifiController"));

	$l[]="master_version=".unifi_mongodb_version();
	$l[]="service_disabled=$enabled";



	$master_pid=unifi_mongodb_pid();
	events("Enabled:$enabled, pid:$master_pid",__FUNCTION__,__LINE__);

	if($enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd=trim("/etc/init.d/unifi stop");
			shell_exec2($cmd);
			$l[]="running=1";

		}
		$l[]="installed=1";
		$l[]="";
		return implode("\n",$l);
	}



	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("/etc/init.d/unifi start");
			shell_exec2($cmd);

		}
		$l[]="running=0";
		$l[]="installed=1";
		$l[]="";
		return implode("\n",$l);

	}else{
		if($enabled==0){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/unifi stop >/dev/null 2>&1 &");
			$l[]="running=0";
			$l[]="installed=1";
			$l[]="";
			return implode("\n",$l);
		}
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);


}

function unifi(){
	$l[]="[UNIFI_CONTROLLER]";
	$l[]="service_name=UNIFI_CONTROLLER";
	$enabled=1;
	$l[]="service_cmd=/etc/init.d/unifi";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";

	if(!is_file("/usr/lib/unifi/bin/mongod")){
		$l[]="running=0\ninstalled=0";
		$l[]="";
		return implode("\n",$l);
	}
	
	$enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnifiController"));

	$l[]="master_version=".unifi_version();
	$l[]="service_disabled=$enabled";



	$master_pid=unifi_pid();
	events("Enabled:$enabled, pid:$master_pid",__FUNCTION__,__LINE__);

	if($enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd=trim("/etc/init.d/unifi stop");
			shell_exec2($cmd);
			$l[]="running=0";
				
		}
		$l[]="installed=1";
		$l[]="";
		return implode("\n",$l);
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("/etc/init.d/unifi start");
			shell_exec2($cmd);

		}
		$l[]="running=0";
		$l[]="installed=1";
		$l[]="";
		return implode("\n",$l);

	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);


}

function unifi_version(){
	return trim(@file_get_contents("/usr/lib/unifi/data/db/version"));

}