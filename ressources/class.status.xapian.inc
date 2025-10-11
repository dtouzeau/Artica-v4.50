<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

function xapian_web_pid(){
	$unix=new unix();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/xapian-webconsole.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("/usr/local/ArticaWebConsole/sbin/artica-webconsole -c /etc/artica-postfix/xapian-web.conf");
}

function xapian_web(){
	$l[]="[APP_XAPIAN_WEB]";
	$l[]="service_name=APP_XAPIAN_WEB";
	
	if(!is_file("/etc/init.d/xapian-web")){
		$l[]="service_disabled=0\nrunning=0\ninstalled=0";
		return @implode("\n", $l);
	}
	
	
	$l[]="installed=1";

	
	
	$l[]="master_version=".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("XAPIAN_OMINDEX_VERSION");
	$l[]="service_disabled=1";
	
	$master_pid=xapian_web_pid();
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$l[]="running=0";
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "InstantSearch Web service {stopped} [{action}={start}]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.xapian.php --start");
			shell_exec2($cmd);
		}
		return @implode("\n", $l);
	}
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);
	
}


