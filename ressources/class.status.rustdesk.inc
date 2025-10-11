<?php

function RUSTDESK_BBS_PID():int{
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/rustdesk-bbs.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    return $GLOBALS["CLASS_UNIX"]->PIDOF("/usr/local/bin/hbbs");
}
function RUSTDESK_BBR_PID():int{
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/rustdesk-bbr.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    return $GLOBALS["CLASS_UNIX"]->PIDOF("/usr/local/bin/hbbr");
}
function rustdesk():string{

    $status_file=PROGRESS_DIR."/rustdesk.status";
	if($GLOBALS["VERBOSE"]){echo __LINE__."]********* rustdesk() *********\n";}
	$binpath="/usr/local/bin/hbbs";
	
	$l[]="[APP_RUSTDESKBBS]";
	$l[]="service_name=APP_RUSTDESKBBS";
	
	if(!is_file($binpath)){
		if($GLOBALS["VERBOSE"]){echo  __LINE__."] ********* rustdesk no such file...*********\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_RUSTDESK_INSTALLED", 0);
		$l[]="running=0\ninstalled=0";
        @file_put_contents($status_file,@implode("\n",$l));
        @chmod($status_file,0755);
        if(is_file("/etc/init.d/rustdesk")){
            squid_admin_mysql(0, "RustDesk Dameon service {installed} [{action}={uninstall}]",
                "EnableRustDeskServer si set to 0 has the init.d exists",__FILE__,__LINE__);
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.rustdesk.php --uninstall");
        }
		return @implode("\n", $l);
	}

    $EnableService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRustDeskServer"));

    if($EnableService==0){
        if(is_file("/etc/init.d/rustdesk")){
            squid_admin_mysql(0, "RustDesk Dameon service {installed} [{action}={uninstall}]",
                "EnableRustDeskServer si set to 0 has the init.d exists",__FILE__,__LINE__);
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.rustdesk.php --uninstall");
        }
        $l[]="running=0\ninstalled=0";
        @file_put_contents($status_file,@implode("\n",$l));
        @chmod($status_file,0755);
        return @implode("\n", $l);
    }

    if(!is_file("/etc/init.d/rustdesk")){
        squid_admin_mysql(0, "RustDesk Dameon service {not_installed} [{action}={install}]",
            "EnableRustDeskServer si set to 1 has the init.d doesn`t exists",__FILE__,__LINE__);
        $GLOBALS["CLASS_UNIX"]->framework_exec("exec.rustdesk.php --install");
        $l[]="running=0\ninstalled=0";
        @file_put_contents($status_file,@implode("\n",$l));
        @chmod($status_file,0755);
        return @implode("\n", $l);
    }


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_RUSTDESK_INSTALLED", 1);
	$Version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RUSTDESK_VERSION");

	$l[]="master_version=$Version";
	$l[]="service_cmd=/etc/init.d/docker";
	$l[]="service_disabled=1";
	$l[]="pid_path=/var/run/docker.pid";
	$l[]="watchdog_features=1";
	$l[]="family=network";

	$PPID=RUSTDESK_BBS_PID();
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($PPID)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
                squid_admin_mysql(0, "RustDesk Dameon service {stopped} [{action}={start}]", null,__FILE__,__LINE__);
            }
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.rustdesk.php --start");
            $GLOBALS["CLASS_UNIX"]->RESTART_SYSLOG(true);
		}
		$l[]="";
        $l[]=rustdesk_bbr();
        @file_put_contents($status_file,@implode("\n",$l));
        @chmod($status_file,0755);
		return implode("\n",$l);
	}

	
	$l[]=GetMemoriesOf($PPID,"APP_RUSTDESKBBS");
	$l[]="";
    $l[]=rustdesk_bbr();
    @file_put_contents($status_file,@implode("\n",$l));
    @chmod($status_file,0755);
	return implode("\n",$l);

}

function rustdesk_bbr():string{

    $l[]="[APP_RUSTDESKBBR]";
    $l[]="service_name=APP_RUSTDESKBBR";

    $EnableService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRustDeskServer"));
    $Version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RUSTDESK_VERSION");

    $l[]="master_version=$Version";
    $l[]="service_cmd=/etc/init.d/docker";
    $l[]="service_disabled=1";
    $l[]="pid_path=/var/run/docker.pid";
    $l[]="watchdog_features=$EnableService";
    $l[]="family=network";

    $PPID=RUSTDESK_BBR_PID();

    if(!$GLOBALS["CLASS_UNIX"]->process_exists($PPID)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
                squid_admin_mysql(0, "RustDesk Relay Dameon service {stopped} [{action}={start}]", null,__FILE__,__LINE__);
            }
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.rustdesk.php --start");
        }
        $l[]="";
        return implode("\n",$l);
    }


    $l[]=GetMemoriesOf($PPID,"APP_RUSTDESKBBR");
    $l[]="";
    return implode("\n",$l);

}

//==================================================================================================================