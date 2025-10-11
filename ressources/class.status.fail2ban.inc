<?php

function fail2ban_pid(){
	$pidfile="/var/run/fail2ban/fail2ban.pid";
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("fail2ban-server");



}


//=============================================================================================================================
function fail2ban():string{
    $EnableFail2Ban =intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFail2Ban"));
    $execp          = "{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix";
    $ServerRunSince = $GLOBALS["CLASS_UNIX"]->ServerRunSince();
    $version        = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FAIL2BAN_VERSION"));
    $Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("fail2ban-server");
    if(!is_file($Masterbin)){ $EnableFail2Ban=0;}

	if(!is_file("/etc/init.d/fail2ban")) {
        if ($EnableFail2Ban == 1) {
            if ($ServerRunSince > 3) {
                squid_admin_mysql(0, "Fail2ban is enabled but not installed [action=reinstall]",
                    null, __FILE__, __LINE__);
                shell_exec("$execp/exec.fail2ban.php --install 2>&1 &");
                return "";
            }

        }
        return "";
    }
    if ($EnableFail2Ban == 0) {
        if ($ServerRunSince > 3) {
            squid_admin_mysql(0, "Fail2ban is not enabled but installed [action=uninstall]",
                null, __FILE__, __LINE__);
            shell_exec("$execp/exec.fail2ban.php --uninstall 2>&1 &");
            return "";
        }
        return "";
    }


	$l[]="[APP_FAIL2BAN]";
	$l[]="service_name=APP_FAIL2BAN";
	$l[]="master_version=$version";
	$l[]="service_cmd=/etc/init.d/fail2ban";
	$l[]="service_disabled=$EnableFail2Ban";
	$l[]="watchdog_features=1";
	$l[]="family=mailbox";

	fail2ban_bug_reload();
	
	$master_pid=fail2ban_pid();
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$ServerRunSince=$GLOBALS["CLASS_UNIX"]->ServerRunSince();
		if($ServerRunSince>3){squid_admin_mysql(0, "Fatal Fail2ban not running, start it", null,__FILE__,__LINE__);}
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/fail2ban start >/dev/null 2>&1 &");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);

	}
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.fail2ban.dashboard.time");
	if($time_file>30){
		$cmd=trim("$execp/exec.fail2ban.dashboard.php >/dev/null 2>&1 &");
		shell_exec2($cmd);
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"APP_FAIL2BAN");
	$l[]="";
	return implode("\n",$l);
}

function fail2ban_bug_reload(){
	$pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("/usr/bin/fail2ban-client reload");
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($pid)){return;}
	$minutes=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
	if($minutes>1){
		squid_admin_mysql(1, "Fatal fail2ban-client reload executed more than 1mn ({$minutes}mn) KILL IT", null,__FILE__,__LINE__);
		$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid,9);
	}
}

//========================================================================================================================================================
