<?php

function DWAGENT_PID():int{
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/usr/share/dwagent/dwagent.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    return intval($GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("native/dwagsvc"));
}
function DWAGENT_STATUS():string{
    $DWAgentEnable  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DWAgentEnable"));
    $rphp           = "{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]}";
    $root           =  $GLOBALS["BASE_ROOT"];
    if(!is_file("/etc/init.d/dwagent")){
        if($DWAgentEnable==1){
            squid_admin_mysql(0, "{APP_DWAGENT} seems enabled but install corrupted [action=install]","/etc/init.d/dwagent missing",__FILE__,__LINE__);
            shell_exec("$rphp $root/exec.dwagent.php --install 2>&1 &");
            @file_put_contents(PROGRESS_DIR."/dwagent.status","");
            return "";
        }
        @file_put_contents(PROGRESS_DIR."/dwagent.status","");
        return "";
    }


	$l[]="[APP_DWAGENT]";
	$l[]="service_name=APP_DWAGENT";
	$l[]="service_cmd=/etc/init.d/dwagent";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";
    $l[]="service_disabled=$DWAgentEnable";

	if($DWAgentEnable==0){
		$l[]="running=0\ninstalled=0";
		$l[]="";
        squid_admin_mysql(0, "{APP_DWAGENT} seems not enabled but install exists [action=uninstall]",null,__FILE__,__LINE__);
        shell_exec("$rphp $root/exec.dwagent.php --uninstall 2>&1 &");
        @file_put_contents(PROGRESS_DIR."/dwagent.status",@implode("\n",$l));
		return implode("\n",$l);
	}




	$master_pid=DWAGENT_PID();
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){squid_admin_mysql(0, "{warning} dwservice {service_stopped} [{action}={start}]", null,__FILE__,__LINE__);}
			$cmd=trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.dwagent.php --start");
			shell_exec2($cmd);
		}

		$l[]="master_pid=$master_pid";
		$l[]="running=0";
		$l[]="installed=1";
		$l[]="";
        @file_put_contents(PROGRESS_DIR."/dwagent.status",@implode("\n",$l));
		return implode("\n",$l);

	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"APP_DWAGENT");
	$l[]="";
    @file_put_contents(PROGRESS_DIR."/dwagent.status",@implode("\n",$l));
	return implode("\n",$l);


}

