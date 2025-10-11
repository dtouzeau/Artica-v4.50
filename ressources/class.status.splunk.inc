<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
function splunk_forwarder_pid(){
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/opt/splunkforwarder/var/run/splunk/splunkd.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin="/opt/splunkforwarder/bin/splunkd";
	return $unix->PIDOF($Masterbin);
}


//========================================================================================================================================================
function splunk_forwarder(){
	$unix=new unix();
	$bin="/opt/splunkforwarder/bin/splunkd";
	if(!is_file($bin)){return;}
	
	$APP_SPLUNK_FORWARDER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SPLUNK_FORWARDER_VERSION");

	$l[]="[APP_SPLUNK_FORWARDER]";
	$l[]="service_name=APP_SPLUNK_FORWARDER";
	$l[]="service_cmd=/etc/init.d/splunk";
	$l[]="master_version=$APP_SPLUNK_FORWARDER_VERSION";
	$l[]="service_disabled=1";
	$l[]="pid_path=/opt/splunkforwarder/var/run/splunk/splunkd.pid";
	$l[]="watchdog_features=1";
	$l[]="family=network";

	$master_pid=splunk_forwarder_pid();
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "Splunk Universal Forwarder {stopped} [{action}={start}]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/splunk.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);
		return;
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);return;
}

//========================================================================================================================================================
