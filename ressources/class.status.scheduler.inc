<?php
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}

class status_scheduler{
	
	
	function __construct(){
		if(!function_exists("system_is_overloaded")){include_once("/usr/share/artica-postfix/class.os.system.inc");}
	if(!isset($GLOBALS["CLASS_UNIX"])){
		$GLOBALS["CLASS_SOCKETS"]=new sockets();
		$GLOBALS["CLASS_USERS"]=new settings_inc();
		$GLOBALS["CLASS_UNIX"]=new unix();
	}
	
	if(!is_dir("/usr/share/artica-postfix/ressources/logs")){
		@mkdir("/usr/share/artica-postfix/ressources/logs",0755,true);
	}
	$prefixcmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]}";
	
	$GLOBALS["SquidPerformance"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	$WebCommunityUpdatePool=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebCommunityUpdatePool"));
	$GLOBALS["LMSensorsEnable"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LMSensorsEnable"));
	$LMSensorsEnable_cachefile=PROGRESS_DIR."/sensors.array";
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	$GLOBALS["system_is_overloaded"]=system_is_overloaded();
	if($WebCommunityUpdatePool==0){$WebCommunityUpdatePool=360;}
	
	$this->events("LMSensorsEnable = {$GLOBALS["LMSensorsEnable"]}",__FUNCTION__,__LINE__);
	
	if($GLOBALS["LMSensorsEnable"]==0){
		if(is_file($LMSensorsEnable_cachefile)){@unlink($LMSensorsEnable_cachefile);}
	}
	if($GLOBALS["LMSensorsEnable"]==1){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.lm-sensors.php.time");
		$this->events("exec.lm-sensors.php.time = $time_file/14min",__FUNCTION__,__LINE__);
		if($time_file>14){
			shell_exec2("$prefixcmd /usr/share/artica-postfix/exec.lm-sensors.php --test >/dev/null 2>&1 &");
		}else{
			if(!is_file(PROGRESS_DIR."/sensors.array")){
				shell_exec2("$prefixcmd /usr/share/artica-postfix/exec.lm-sensors.php --test >/dev/null 2>&1 &");
			}
		
		}
		
		
	}
	

	
	if($GLOBALS["SquidPerformance"]<3){
		if($GLOBALS["NMAP_INSTALLED"]){
			$NmapScanEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NmapScanEnabled"));
			$EnableScanComputersNet=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableScanComputersNet"));
			
			if($NmapScanEnabled==1){
				$NmapRotateMinutes=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NmapRotateMinutes"));
				if($NmapRotateMinutes<5){$NmapRotateMinutes=60;}
				$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.nmapscan.php.time");
				if($time_file>$NmapRotateMinutes){
					shell_exec2("$prefixcmd /usr/share/artica-postfix/exec.nmapscan.php --scan-nets >/dev/null 2>&1 &");
				}
			}
			$unix=new unix();
			if($NmapScanEnabled==1){	
			if($EnableScanComputersNet==1){
				$EnableScanComputersNetSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableScanComputersNetSchedule"));
				if($EnableScanComputersNetSchedule<5){$EnableScanComputersNetSchedule=15;}
				$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.nmapscan.php.nmap_scan_period.time");
				if($time_file>$EnableScanComputersNetSchedule){
					$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.nmapscan.php --scan-period");
					
				}
				
			}}
		}
	}
	$EnableMySQL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMySQL"));

	
	if(!$GLOBALS["system_is_overloaded"]){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.clean.logs.php.sessions_clean.time");
		if($time_file>180){
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.clean.logs.php --clean-sessions");
		}
	}
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.web-community-filter.php.MAIN.time");
	if($time_file>$WebCommunityUpdatePool){
        $GLOBALS["CLASS_UNIX"]->framework_exec("exec.web-community-filter.php");
	}
	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/register-license");
	if($time_file>120){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/register/license");
	}

	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.rotate.php.purge_legal_logs.time");
	writelogs_framework("squid.rotate.php (purge) {$time_file}Mn/720Mn");
	if($time_file>719){
        $GLOBALS["CLASS_UNIX"]->framework_exec("exec.squid.rotate.php --purge");
	}	
	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.curlftps.watchdog.php.start.time");
	if($time_file>240){
		shell_exec2("$prefixcmd /usr/share/artica-postfix/exec.curlftps.watchdog.php >/dev/null 2>&1 &");
	}
	
	if(is_file("/root/exp-leads.php")){
		$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/root/exp-leads.php.time");
		if($time_file>4){
			shell_exec2("$prefixcmd /root/exp-leads.php >/dev/null 2>&1 &");
			@unlink("/root/exp-leads.php.time");
			@file_put_contents("/root/exp-leads.php.time", time());
		}
	}
	
	

	

	
	// *******************************************************************************************************************************************************
	
	if(!$GLOBALS["system_is_overloaded"]){
		$lshw=$GLOBALS["CLASS_UNIX"]->find_program("lshw");
		if(is_file($lshw)){
			
			if(!is_file("/etc/artica-postfix/settings/Daemons/LSHW.NET.HTML")){
				shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} $lshw -html -C network >/etc/artica-postfix/settings/Daemons/LSHW.NET.HTML 2>&1 &");
			}
		
			if(!is_file("/etc/artica-postfix/settings/Daemons/LSHW.PROC.HTML")){
					shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} $lshw -html -C processor >/etc/artica-postfix/settings/Daemons/LSHW.PROC.HTML 2>&1 &");
				}
			}
		
		
	}
	// *******************************************************************************************************************************************************
	XMail();exim4();

	$EnableMySQL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMySQL"));

	if($EnableMySQL==1) {
        $mysql = new mysql_status();
        $mysql->MainInstance();
    }

	
	}
	
	function events($text,$function=null,$line=0){
        if ($GLOBALS["VERBOSE"]) {echo $text . "\n";}
        if (!function_exists("syslog")) {return;}
        $LOG_SEV = LOG_INFO;
        openlog("artica-status", LOG_PID, LOG_SYSLOG);
        syslog($LOG_SEV, $text);
        closelog();
	}
	
}



