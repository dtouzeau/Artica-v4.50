<?php
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}




function pdns_increment_func($array){
	if(!isset($GLOBALS["CLASS_SOCKETS"])){ if(function_exists("LoadIncludes")){LoadIncludes();}else{return $array;} }
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnablePDNS")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePDNS", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/PowerDNSEnableRecursor")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSEnableRecursor", 1);}
	$EnablePDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
	
	$array[]="pdns_stats";
	if($EnablePDNS==0){return $array;}
	
	

	$array[]="pdns_instance";
	$array[]="pdns_recursor";
	return $array;
}



function pdns_stats_pid(){
    $Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("dsc");
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/dsc.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);
}


function pdns_stats():string{
	$PDNSStatsInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSStatsInstalled"));
	if($PDNSStatsInstalled==0){if($GLOBALS["VERBOSE"]){echo "PDNSStatsInstalled [FALSE]\n";}return "";}
	
	$PDNSStatsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSStatsEnabled"));
	$master_pid=pdns_stats_pid();
	$pdns_stats_version=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DSCVersion"));
	if(!is_file("/etc/init.d/dsc")){return "";}
	
	$l[]="[APP_DSC]";
	$l[]="service_name=APP_DSC";
	$l[]="master_version=$pdns_stats_version";
	$l[]="service_cmd=/etc/init.d/dsc";
	$l[]="service_disabled=$PDNSStatsEnabled";
	$l[]="pid_path=/var/run/dsc.pid";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	
	if($PDNSStatsEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			if(is_file("/etc/init.d/dsc")){shell_exec("/etc/init.d/dsc stop >/dev/null 2>&1 &");}
			return implode("\n",$l);
		}
	}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(1, "DNS Stat Collector is down [{action}={start}]", null,__FILE__,__LINE__);
			shell_exec("{$GLOBALS["nohup"]} /etc/init.d/dsc start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";
		$l[]="";
		return implode("\n",$l);
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"APP_DSC");
	$l[]="";
    return @implode("\n", $l);
	
	
}

function pdns_instance(){
	$verbose=$GLOBALS["VERBOSE"];
	$master_pid=null;
	if(!is_file("/etc/artica-postfix/settings/Daemons/PDNSRestartIfUpToMB")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PDNSRestartIfUpToMB", 0);}
	
	$PDNSRestartIfUpToMB=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSRestartIfUpToMB");
	$pdns_server=$GLOBALS["CLASS_UNIX"]->find_program("pdns_server");
	if($pdns_server==null){if($verbose){echo "pdns_server no such binary\n";}return null;}

	$pidof=$GLOBALS["CLASS_UNIX"]->find_program("pidof");
	$cmd="$pidof $pdns_server-instance 2>&1";
	exec($cmd,$results);
	if($verbose){echo "$cmd return ". count($results)." rows\n";}
	foreach ($results as $num=>$ligne){
		if(trim($ligne)==null){continue;}
		if(preg_match("#^([0-9]+)#",$ligne,$re)){
			if($GLOBALS["CLASS_UNIX"]->process_exists($re[1])){$master_pid=$re[1];break;}
		}
	}


	if(!is_numeric($master_pid)){
		$results=array();
		$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
		$cmd="$pgrep -l -f $pdns_server-instance 2>&1";
		exec($cmd,$results);
		if($verbose){echo "$cmd return ". count($results)." rows\n";}
		foreach ($results as $num=>$ligne){
			if(trim($ligne)==null){continue;}
			if(preg_match("#^([0-9]+)\s+.+?pdns#",$ligne,$re)){
				if($GLOBALS["CLASS_UNIX"]->process_exists($re[1])){$master_pid=$re[1];break;}
			}
		}
	}



	if($GLOBALS["VERBOSE"]){echo "$pdns_server-instance -> $master_pid\n";}
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){return;}

	$PROCESS_MEMORY=$GLOBALS["CLASS_UNIX"]->PROCESS_MEMORY($master_pid,true);
	$PROCESS_CACHE_MEMORY=$GLOBALS["CLASS_UNIX"]->PROCESS_CACHE_MEMORY($master_pid,true);
	$PDNSRestartIfUpToMBOrg=$PDNSRestartIfUpToMB;
	if($PDNSRestartIfUpToMB>0){
		$PDNSRestartIfUpToMB=$PDNSRestartIfUpToMB*1024;

		if($verbose){echo "PROCESS_MEMORY:{$PROCESS_MEMORY}KB against {$PDNSRestartIfUpToMB}KB\n";}

		if($PROCESS_MEMORY>$PDNSRestartIfUpToMB){
			$PROCESS_MEMORY_EX=round($PROCESS_MEMORY/1024,2);
			$GLOBALS["CLASS_UNIX"]->send_email_events("Watchdog: PowerDNS reach Max memory !!! ({$PROCESS_MEMORY_EX}M/{$PDNSRestartIfUpToMBOrg}M)","PowerDNS service was restarted","system");
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			shell_exec2(trim("$nohup /etc/init.d/pdns restart >/dev/null 2>&1 &"));
		}}
		$l[]="";
		$l[]="";
		$l[]="[APP_PDNS_INSTANCE]";
		$l[]="service_name=APP_PDNS_INSTANCE";
		$l[]="master_version={$GLOBALS["PDNS_VERSION"]}";
		$l[]="service_cmd=pdns";
		$l[]="service_disabled=1";
		$l[]="watchdog_features=1";
		$l[]="family=network";
		$l[]="running=1";
		$l[]="master_memory=$PROCESS_MEMORY";
		$l[]="master_cached_memory=$PROCESS_CACHE_MEMORY";
		$l[]="processes_number=1";
		$l[]="master_pid=$master_pid";
		$l[]="running=1\ninstalled=1";
		$l[]="";
		return implode("\n",$l);
}

//========================================================================================================================================================
function pdns_recursor(){
	$enabled=1;
    if($GLOBALS["VERBOSE"]){echo "PDNS_RECURSOR: ".__LINE__."\n";}
	$PowerDNSEnableRecursor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableRecursor"));
	$EnablePDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
	
	if(!is_file("/etc/init.d/pdns-recursor")){
		if($PowerDNSEnableRecursor==1){
			if($EnablePDNS==0){
                if($GLOBALS["VERBOSE"]){echo "PDNS_RECURSOR: ".__LINE__."\n";}
				$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSEnableRecursor",0);
				return;
			}
			
			squid_admin_mysql(1, "PowerDNS Recursor is not installed, install the service",null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.pdns_server.install.php --install-recursor >/dev/null 2>&1"); 
		}
        if($GLOBALS["VERBOSE"]){echo "PDNS_RECURSOR: ".__LINE__."\n";}
		return;
	}
	
	if(is_file("/etc/init.d/pdns-recursor")){
		if(($PowerDNSEnableRecursor==0) OR ($EnablePDNS==0)){
			squid_admin_mysql(1, "PowerDNS Recursor is installed but disabled, uninstall the service",null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.pdns_server.install.php --uninstall-recursor >/dev/null 2>&1");
            return;
		}

	}
	
	
	$DisablePowerDnsManagement=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePowerDnsManagement"));
	if($GLOBALS["VERBOSE"]){echo "PDNS_RECURSOR: ".__LINE__."\n";}
	if($DisablePowerDnsManagement==1){return;}
	$pdns_server=$GLOBALS["CLASS_UNIX"]->find_program("pdns_recursor");
	
	if($pdns_server==null){if($GLOBALS["VERBOSE"]){echo "PDNS_RECURSOR: pdns_recursor no such binary\n";}return null;}
	if(!is_file($pdns_server)){if($GLOBALS["VERBOSE"]){echo "PDNS_RECURSOR: pdns_recursor no such binary\n";}return null;}
	
	$pid_path="/var/run/pdns/pdns_recursor.pid";
	$master_pid=trim(@file_get_contents($pid_path));
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($pdns_server);
		if($master_pid<>null){@file_put_contents($pid_path,$master_pid);}
	}


	

	$pdns_recursor_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSRecursorVersion");
    if($GLOBALS["VERBOSE"]){echo "PDNS_RECURSOR: ".__LINE__." Version: $pdns_recursor_version\n";}

	$l[]="[PDNS_RECURSOR]";
	$l[]="service_name=APP_PDNS_RECURSOR";
	$l[]="master_version=$pdns_recursor_version";
	$l[]="service_cmd=/etc/init.d/pdns-recursor";
	$l[]="service_disabled=$EnablePDNS";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";

	if($EnablePDNS==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			squid_admin_mysql(1, "PowerDNS Recursor is UP but PowerDNS is disabled [action=stop]", null,__FILE__,__LINE__);
			shell_exec2("/etc/init.d/pdns-recursor stop >/dev/null 2>&1 &");
			return implode("\n",$l);
		}
	}
	
	if($PowerDNSEnableRecursor==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			squid_admin_mysql(1, "PowerDNS Recursor is UP but Recursor is disabled [action=stop]", null,__FILE__,__LINE__);
			shell_exec2("/etc/init.d/pdns-recursor stop >/dev/null 2>&1 &");
			return implode("\n",$l);
		}		
	}
	

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(1, "PowerDNS Recursor is DOWN [{action}={start}]", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/pdns-recursor start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";
		$l[]="";
		return implode("\n",$l);
	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"APP_PDNS_RECURSOR");
	$l[]="";
	return implode("\n",$l);return;

}








