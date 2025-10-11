<?php

function smokeping_pid():int{
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/smokeping/smokeping.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("/smokeping [");
}

function smokeping():string{

    $EnableSmokePing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSmokePing"));
    $SMOKEPING_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SMOKEPING_VERSION");
    $service_cmd="/etc/init.d/smokeping";
    $status_file=PROGRESS_DIR."/smokeping.status";
	$l[]="[APP_SMOKEPING]";
	$l[]="service_name=APP_SMOKEPING";
	$l[]="service_cmd=$service_cmd";
	$l[]="master_version=$SMOKEPING_VERSION";
	$l[]="service_disabled=$EnableSmokePing";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	
	if($EnableSmokePing==0){
        if(is_file($service_cmd)){
            squid_admin_mysql(0, "{APP_SMOKEPING} {installed} [action={uninstall}]", null,__FILE__,__LINE__);
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.smokeping.php --uninstall");
            @file_put_contents($status_file,@implode("\n",$l));
            return @implode("\n",$l);
        }
        if(is_file("/etc/monit/conf.d/APP_SMOKEPING.monitrc")){
            squid_admin_mysql(0, "{APP_SMOKEPING} (Monit) {installed} [action={uninstall}]", null,__FILE__,__LINE__);
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.smokeping.php --uninstall");
            @file_put_contents($status_file,@implode("\n",$l));
            return @implode("\n",$l);
        }

        return @implode("\n",$l);
    }

    if(!is_file($service_cmd)){
        squid_admin_mysql(0, "{APP_SMOKEPING} {not_installed} [action={install}]", null,__FILE__,__LINE__);
        $GLOBALS["CLASS_UNIX"]->framework_exec("exec.smokeping.php --install");
        @file_put_contents($status_file,@implode("\n",$l));
        return @implode("\n",$l);
    }

	$master_pid=smokeping_pid();

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$ServerRunSince=$GLOBALS["CLASS_UNIX"]->ServerRunSince();
			if($ServerRunSince>3){squid_admin_mysql(0, "{APP_SMOKEPING} {stopped} [action={start}]", null,__FILE__,__LINE__);}
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			shell_exec2("$nohup $service_cmd start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";
		$l[]="";
        @file_put_contents($status_file,@implode("\n",$l));
		return implode("\n",$l);
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"APP_SMOKEPING");
	$l[]="";
    @file_put_contents($status_file,@implode("\n",$l));
    return @implode("\n",$l);

}
