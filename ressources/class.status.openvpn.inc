<?php



function openvpn_server_pid($Masterbin){

	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/openvpn/openvpn-server.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$Masterbin --port.+?--dev");
}

function openvpn(){

	$l[]="[OPENVPN_SERVER]";
	$l[]="service_name=APP_OPENVPN";
    $l[]="service_cmd=/etc/init.d/openvpn-server";

	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("openvpn");

	if(!is_file((string) $bin_path)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenVPNInstalled", 0);
		$l[]="running=0\ninstalled=0";$l[]="";
		return @implode("\n", $l);
	}

	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenVPNInstalled", 1);
	$EnableOpenVPNServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenVPNServer"));
	if($EnableOpenVPNServer==1){
        $clientsDir=$GLOBALS["CLASS_UNIX"]->dirdir("/etc/artica-postfix/openvpn/clients");
		writelogs(count($clientsDir)." openvpn client session(s)",__FUNCTION__,__FILE__,__LINE__);
        if(count($clientsDir)>0){
            $nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
            $cmd="$nohup {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.openvpn.php --wakeup-clients >/dev/null 2>&1 &";
            shell_exec2(trim($cmd));
        }
    }
	
	$ini=new iniFrameWork();
    $openvpn_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_OPENVPN_VERSION");
	$EnableOPenVPNServerMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOPenVPNServerMode"));
    $l[]="master_version=$openvpn_version";
    $l[]="service_disabled=$EnableOPenVPNServerMode";

	if(!is_file("/etc/artica-postfix/settings/Daemons/ArticaOpenVPNSettings")){
        return @implode("\n",$l);
    }

	$ini->loadString(@file_get_contents('/etc/artica-postfix/settings/Daemons/ArticaOpenVPNSettings'));
	if(!isset($ini->_params["GLOBAL"]["ENABLE_SERVER"])){$ini->_params["GLOBAL"]["ENABLE_SERVER"]=0;}
	$EnableOPenVPNServerMode=intval($ini->_params["GLOBAL"]["ENABLE_SERVER"]);

	$l[]="family=vpn";
	$l[]="watchdog_features=1";
	if($EnableOPenVPNServerMode==0){return implode("\n",$l);return;}


	$master_pid=openvpn_server_pid($bin_path);

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "{warning} OpenVPN server {stopped} [{action}={start}]", null,__FILE__,__LINE__);
			$cmd=trim("{$GLOBALS["NICE"]} /etc/init.d/openvpn-server start");
			shell_exec2($cmd);
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		
		return implode("\n",$l);
		
	}
	


	
	
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		$pidtime="/etc/artica-postfix/pids/exec.openvpn.php.stats.time";
		if($GLOBALS["CLASS_UNIX"]->file_time_min($pidtime)>4){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			$cmd="$nohup {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.openvpn.php --stats >/dev/null 2>&1 &";
			shell_exec2(trim($cmd));
		}
		
		
		$pidtime="/etc/artica-postfix/pids/exec.openvpn.php.GenStats.time";
		if($GLOBALS["CLASS_UNIX"]->file_time_min($pidtime)>9){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			$cmd="$nohup {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.openvpn.php --genstats >/dev/null 2>&1 &";
			shell_exec2(trim($cmd));
		}
		
		$pidTime="/etc/artica-postfix/pids/exec.openvpn.client-connect.php.xrun.time";
		if($GLOBALS["CLASS_UNIX"]->file_time_min($pidtime)>4){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			$cmd="$nohup {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.openvpn.client-connect.php >/dev/null 2>&1 &";
			shell_exec2(trim($cmd));
		}
		

		
	}
	
	

	$pidtime="/etc/artica-postfix/pids/exec.openvpn.php.wakeup_server_mode.time";
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		if($GLOBALS["CLASS_UNIX"]->file_time_min($pidtime)>2){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			$cmd="$nohup {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.openvpn.php --wakeup-server >/dev/null 2>&1 &";
			shell_exec2(trim($cmd));
		}
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"APP_OPENVPN");
	$l[]="";
	return implode("\n",$l);return;


}




