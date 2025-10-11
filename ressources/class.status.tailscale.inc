<?php

function TAILSCALE_PID():int{
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/tailscale/tailscale.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    $Masterbin="/usr/sbin/tailscaled";
    return intval($GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin));
}
function TAILSCALE_WWW_PID(){
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/tailscale/tailscalwww.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    return intval($GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("tailscale web --listen"));
}

function TAILSCALE_STATUS():string{
    $Enable         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTailScaleService"));
    $APP_TAILSCALE_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_TAILSCALE_INSTALLED"));
    if($APP_TAILSCALE_INSTALLED==0){$Enable=0;}
    $rphp           = "{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]}";
    $root           =  $GLOBALS["BASE_ROOT"];
    $initd          = "/etc/init.d/tailscale";
    $srclist        ="/etc/apt/sources.list.d/tailscale.list";
    $statusfile     = PROGRESS_DIR."/tailscale.status";
    $CODE_NAME      = "APP_TAILSCALE";
    $CODE_NAME_TEXT = "{".$CODE_NAME."}";
    $scrfile        = "exec.tailscale.php";

    $log[]="Application Installed: $APP_TAILSCALE_INSTALLED";
    $log[]="Application Enabled..: $Enable";
    $log[]="init.d...............: $initd";

    if(!is_file($initd)){
        if($Enable==1){
            squid_admin_mysql(0, "$CODE_NAME_TEXT seems enabled but install corrupted [action=install]","$initd missing",__FILE__,__LINE__);
            shell_exec("$rphp $root/$scrfile --install 2>&1 &");
            @file_put_contents($statusfile,"");
            return "";
        }
        @file_put_contents($statusfile,"");
        return "";
    }


    $l[]="[$CODE_NAME]";
    $l[]="service_name=$CODE_NAME";
    $l[]="service_cmd=$initd";
    $l[]="family=proxy";
    $l[]="watchdog_features=1";
    $l[]="service_disabled=$Enable";

    if($Enable==0){
        $l[]="running=0\ninstalled=0";
        $l[]="";
        squid_admin_mysql(0, "$CODE_NAME_TEXT seems not enabled but install exists [action=uninstall]",
            @implode("\n",$log),__FILE__,__LINE__);
        shell_exec("$rphp $root/$scrfile --uninstall 2>&1 &");
        @file_put_contents($statusfile,@implode("\n",$l));
        return implode("\n",$l);
    }
    $master_pid=TAILSCALE_PID();
    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){squid_admin_mysql(0, "{warning} $CODE_NAME_TEXT {service_stopped} [{action}={start}]", null,__FILE__,__LINE__);}
            $cmd=trim("$rphp $root/$scrfile --start");
            shell_exec2($cmd);
        }

        $l[]="master_pid=$master_pid";
        $l[]="running=0";
        $l[]="installed=1";
        $l[]="";
        @file_put_contents($statusfile,@implode("\n",$l));
        return implode("\n",$l);

    }

    if(!is_file($srclist)){
        $cmd=trim("$rphp $root/$scrfile --repository >/dev/null 2>&1 &");
        shell_exec2($cmd);

    }

    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid);
    $l[]="";
    $l[]=TAILSCALE_WEB();
    @file_put_contents($statusfile,@implode("\n",$l));
    return implode("\n",$l);
}

function TAILSCALE_WEB():string{
    $CODE_NAME="APP_TAILSCALE_WEB";
    $CODE_NAME_TEXT = "{".$CODE_NAME."}";
    $rphp           = "{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]}";
    $root           =  $GLOBALS["BASE_ROOT"];
    $scrfile        = "exec.tailscale.php";

	$l[]="[$CODE_NAME]";
	$l[]="service_name=$CODE_NAME";
	$l[]="service_cmd=/ect/init.d/tailscale";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";
    $l[]="service_disabled=1";


	$master_pid=TAILSCALE_WWW_PID();
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){squid_admin_mysql(0, "{warning} $CODE_NAME_TEXT {service_stopped} [{action}={start}]", null,__FILE__,__LINE__);}
			$cmd=trim("$rphp $root/$scrfile --start-www");
			shell_exec2($cmd);
		}

		$l[]="master_pid=$master_pid";
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

