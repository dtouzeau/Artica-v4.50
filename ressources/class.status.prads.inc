<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

function _prads_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/prads/prads.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    $binpath="/usr/bin/prads";
	return $GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
}


function prads(){
    $BASE_PATH="/usr/share/artica-postfix";
    $EnablePrads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePrads"));
	if(!is_file("/etc/init.d/prads")){
        if($EnablePrads==0){return;}
        squid_admin_mysql(0, "{APP_PRADS} service is enabled [action=install]", null,__FILE__,__LINE__);
        shell_exec("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASE_PATH/exec.prads.php --install >/dev/null 2>&1 &");
        return;
	}

	$l[]="[APP_PRADS]";
	$l[]="service_name=APP_PRADS";
	



	if($EnablePrads==0){
        squid_admin_mysql(0, "{APP_PRADS} service is disabled [action=uninstall]", null,__FILE__,__LINE__);
        shell_exec("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASE_PATH/exec.prads.php --uninstall >/dev/null 2>&1 &");
        return;
    }

	$pid_path="/var/run/prads/prads.pid";
	
	$l[]="master_version=0.33";
	$l[]="service_cmd=/etc/init.d/prads";
	$l[]="service_disabled=$EnablePrads";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	$PPID=_prads_pid();
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($PPID)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "{APP_PRADS} service {stopped} [{action}={start}]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]} /etc/init.d/prads start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);

	}
	
	
	$l[]=GetMemoriesOf($PPID);
	$l[]="";
	

	return implode("\n",$l);return;
}

