<?php

function ipsec_init(){
	if(is_file("/etc/init.d/ipsec")){return "/etc/init.d/ipsec";}
}

function ipsec_pid_path(){
	if(is_file("/var/run/charon.pid")){return "/var/run/charon.pid";}
}

function ipsec_binpath(){
	if(is_file("/usr/lib/ipsec/charon")){return "/usr/lib/ipsec/charon";}
}
function ipsec(){
	//if(!$GLOBALS["CLASS_USERS"]->IPSEC_INSTALLED){return;}
	$bin_path=ipsec_binpath();
	if(!is_file((string) $bin_path)){return;}
	$EnableIPSEC=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStrongswanServer");
	if(!is_numeric($EnableIPSEC)){$EnableIPSEC=0;}
	$pid_path=ipsec_pid_path();
	$master_pid=trim(@file_get_contents($pid_path));

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);}
;
	$l[]="[APP_STRONGSWAN]";
	$l[]="service_name=APP_STRONGSWAN";
	$l[]="master_version=0.00";
	$l[]="service_cmd=";
	$l[]="service_disabled=$EnableIPSEC";
	$l[]="pid_path=$pid_path";
	$l[]="family=system";
	$l[]="watchdog_features=1";

	$l[]="";

	if($EnableIPSEC==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$init=ipsec_init();
			if(is_file($init))
			$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("$init stop");
		}
	}

	if($EnableIPSEC==0){$l[]="";return implode("\n",$l);}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		//WATCHDOG("APP_FRESHCLAM","freshclam");
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid,"APP_STRONGSWAN");
	$l[]="";
	return implode("\n",$l);

}