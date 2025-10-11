<?php


function haexchange_pid(){
    $pid_path="/var/run/ha-exchange.pid";
    if(!is_file($pid_path)){
        $binpath="/usr/sbin/ha-exchange";
        return $GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
    }
    $pids=explode("\n",@file_get_contents($pid_path));
    foreach ($pids as $line){
        $line=str_replace("\r", "", $line);
        $line=str_replace("\n", "", $line);
        if(!is_numeric(trim($line))){continue;}
        if($GLOBALS["VERBOSE"]){echo "$pid_path = $line\n";}
        if($GLOBALS["CLASS_UNIX"]->process_exists($line)){
            $PPID=$GLOBALS["CLASS_UNIX"]->PPID_OF($line);
            if($GLOBALS["VERBOSE"]){echo "$line ->running PPID:$PPID\n";}
            return $PPID;
        }
    }

}


function haexchange(){
    $prodname="Reverse-proxy For MS Exchange";
	$prcmd="{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix";
    $binpath="/usr/sbin/ha-exchange";
    $init="/etc/init.d/ha-exchange";
    $socket="/var/run/ha-exchange.stat";
	
	$l[]="[APP_HAPROXY_EXCHANGE]";
	$l[]="service_name=APP_HAPROXY_EXCHANGE";
	if(!is_file($binpath)){
		$l[]="running=0\ninstalled=0";
		return @implode("\n", $l);
		
	}

	$EnableHaExchange=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaExchange"));

	
	if($EnableHaExchange==0){
		if(is_file($init)){
			squid_admin_mysql(1,
                    "Removing $prodname startup script (not enabled)",
                     null,__FILE__,__LINE__);
			$cmd=trim("$prcmd/exec.haexchnage.php --uninstall");
			shell_exec2($cmd);
		}
		return;
	}

    $HaproxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaproxyVersion", $HaproxyVersion);
	
	if(!is_file($init)){
		squid_admin_mysql(0, "Install $prodname (enabled)", null,__FILE__,__LINE__);
		$cmd=trim("$prcmd/exec.haexchnage.php --install");
		shell_exec2($cmd);
	}
	$pid_path="/var/run/ha-exchange.pid";

	$l[]="master_version=$HaproxyVersion";
	$l[]="service_cmd=$init";
	$l[]="service_disabled=$EnableHaExchange";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";

	$PPID=haexchange_pid();
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($PPID)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "$prodname {stopped} [{action}={start}]", null,__FILE__,__LINE__);
			$cmd=trim("$prcmd/exec.haexchnage.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);

	}
	
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		if(!$GLOBALS["CLASS_UNIX"]->is_socket($socket)){
			squid_admin_mysql(0, "$prodname socket $socket not running [action=restart]", null,__FILE__,__LINE__);
			$cmd=trim("$prcmd/exec.haexchnage.php --restart >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
	}

	$l[]=GetMemoriesOf($PPID);
	$l[]="";
	

	return implode("\n",$l);
}

