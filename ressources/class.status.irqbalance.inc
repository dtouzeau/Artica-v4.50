<?php

function irqbalance_pid(){
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("irqbalance");
	return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);
}

function irqbalance(){
	if(!is_file("/etc/init.d/irqbalance")){return;}
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("irqbalance");
	
	
	$l[]="[IRQBALANCE]";
	$l[]="service_name=IRQBALANCE";
	$l[]="service_cmd=/etc/init.d/irqbalance";
	$l[]="family=system";
	$l[]="watchdog_features=1";

	if(!is_file($Masterbin)){
		$l[]="running=0\ninstalled=0\nservice_disabled=0";
		$l[]="";
		return implode("\n",$l);
	}

	$enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIRQBalance"));

	$l[]="master_version=1.0.3-3";
	$l[]="service_disabled=$enabled";
	$master_pid=irqbalance_pid();
	events("Enabled:$enabled, pid:$master_pid",__FUNCTION__,__LINE__);

	if($enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd=trim("/etc/init.d/irqbalance stop");
			shell_exec2($cmd);
			$l[]="running=1";

		}
		$l[]="installed=1";
		$l[]="";
		return implode("\n",$l);
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){$cmd=trim("/etc/init.d/irqbalance start");shell_exec2($cmd);}
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