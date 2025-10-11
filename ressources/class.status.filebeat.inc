<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

function _filebeat_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/filebeat.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    $binpath="/usr/share/filebeat/bin/filebeat";
	return $GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
}


function _filebeat(){

	if(!is_file("/etc/init.d/filebeat")){return;}

	$l[]="[APP_FILEBEAT]";
	$l[]="service_name=APP_FILEBEAT";
	
	$Version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FILEBEAT_VERSION");
	$enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFileBeat"));
	$pid_path="/var/run/filebeat.pid";
	
	$l[]="master_version=$Version";
	$l[]="service_cmd=/etc/init.d/filebeat";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	$PPID=_filebeat_pid();

	if($enabled==0){
        squid_admin_mysql(0, "{APP_FILEBEAT} init is installed but not enabled [action=uninstall]", null,__FILE__,__LINE__);
        shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.filebeat.php --uninstall >/dev/null 2>&1 &");
        return;
    }

	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($PPID)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "{APP_FILEBEAT} service {stopped} [{action}={start}]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]} /etc/init.d/filebeat start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);

	}
	
	
	$l[]=GetMemoriesOf($PPID,"APP_FILEBEAT");
	$l[]="";
	

	return implode("\n",$l);return;
}

