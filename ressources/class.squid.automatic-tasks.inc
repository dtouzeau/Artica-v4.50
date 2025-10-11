<?php
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}
include_once(dirname(__FILE__)."/class.os.system.inc");

class squid_auto_tasks{
	
	
	function __construct(){
		$this->LoadClasses();
		$this->execute();
	}

    private function AutomaticToSyslog($text){
        $LOG_SEV=LOG_INFO;
        if(function_exists("openlog")){openlog("artica-status", LOG_PID , LOG_SYSLOG);}
        if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
        if(function_exists("closelog")){closelog();}
    }
	
	
	private function ifStatisticsMustBeExecuted():bool{
		$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
		if($SquidPerformance>2){return false;}
		return true;
	}
	private function squid_rtt_events($text){
		if(trim($text)==null){return;}
	
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/squid/logfile_daemon.debug";
	
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		if($GLOBALS["VERBOSE"]){echo "$date:[".basename(__FILE__)."] $pid `$text`\n";}
		@fwrite($f, "$date:[".basename(__FILE__)."] $pid `$text`\n");
		@fclose($f);
		@chmod($logFile,0755);
		@chown($logFile, "squid");

	}	
	
	
	private function execute(){
		$DisableStats=0;
		if(!is_file("/etc/artica-postfix/settings/Daemons/EnableProxyCompressor")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableProxyCompressor", 0);}
		if(!is_file("/etc/artica-postfix/settings/Daemons/EnableKerbAuth")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKerbAuth", 0);}
		if(!is_file("/etc/artica-postfix/settings/Daemons/SquidLogRotateFreq")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidLogRotateFreq", 1440);}
		if(!is_file("/etc/artica-postfix/settings/Daemons/SquidCacheLevel")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidCacheLevel", 3);}
		if(!is_file("/etc/artica-postfix/settings/Daemons/EnableSS5")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSS5", 0);}
        $prefixcmd=$GLOBALS["nohup"]." {$GLOBALS["NICE"]}".$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN()." ";
		$DisableArticaProxyStatistics=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableArticaProxyStatistics");
		if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
		$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
		$ArticaDBPath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaDBPath");
		$CACHE_DYN_PARAMS=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDynamicCaches")));
        if(!$CACHE_DYN_PARAMS){
            $CACHE_DYN_PARAMS=array();
        }

        if(!isset($CACHE_DYN_PARAMS["INTERVAL"])){$CACHE_DYN_PARAMS["INTERVAL"]=420;}
		if(!is_numeric($CACHE_DYN_PARAMS["INTERVAL"])){$CACHE_DYN_PARAMS["INTERVAL"]=420;}
		if($CACHE_DYN_PARAMS["INTERVAL"]<60){$CACHE_DYN_PARAMS["INTERVAL"]=60;}
		$EnableProxyCompressor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyCompressor"));
		$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
		$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
		$SquidCacheLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel"));

		if(!isset($CACHE_DYN_PARAMS["MAX_WWW"])){$CACHE_DYN_PARAMS["MAX_WWW"]=100;}
        if(!isset($CACHE_DYN_PARAMS["MAX_TTL"])){$CACHE_DYN_PARAMS["MAX_TTL"]=15;}
        if(!isset($CACHE_DYN_PARAMS["LEVEL"])){$CACHE_DYN_PARAMS["LEVEL"]=5;}
        if(!isset($CACHE_DYN_PARAMS["INTERVAL"])){$CACHE_DYN_PARAMS["INTERVAL"]=420;}
        if(!isset($CACHE_DYN_PARAMS["ENABLED"])){$CACHE_DYN_PARAMS["ENABLED"]=0;}
		
		if(!is_numeric($CACHE_DYN_PARAMS["ENABLED"])){if($SquidCacheLevel>2){$CACHE_DYN_PARAMS["ENABLED"]=1; }}
		if(!is_numeric($CACHE_DYN_PARAMS["MAX_WWW"])){$CACHE_DYN_PARAMS["MAX_WWW"]=100;}
		if(!is_numeric($CACHE_DYN_PARAMS["LEVEL"])){$CACHE_DYN_PARAMS["LEVEL"]=5;}
		if(!is_numeric($CACHE_DYN_PARAMS["INTERVAL"])){$CACHE_DYN_PARAMS["INTERVAL"]=420;}
		if(!is_numeric($CACHE_DYN_PARAMS["MAX_TTL"])){$CACHE_DYN_PARAMS["MAX_TTL"]=15;}
		if(!is_numeric($CACHE_DYN_PARAMS["ENABLED"])){$CACHE_DYN_PARAMS["ENABLED"]=0;}
		if($SquidCacheLevel<3){$CACHE_DYN_PARAMS["ENABLED"]=0;}
		if($SQUIDEnable==0){return;}

		if($DisableArticaProxyStatistics==1){$DisableStats=1;}
		if($SquidPerformance>2){$DisableStats=1;}
		
		if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}

		
		
	if($EnableProxyCompressor==1){		
	//**********************************************************************************************************************
	// ZipProxy statistiques globales
			$TimeFile="/etc/artica-postfix/pids/exec.zipproxy.php.zipproxy_global.time";
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_sec($TimeFile);
			if($CacheSchedules>10){
				$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.zipproxy.php --global >/dev/null 2>&1 &");
				$this->squid_rtt_events("Running $cmd");
				shell_exec($cmd);
			}	
	//**********************************************************************************************************************
	// ZipProxy injection des access.log
			$TimeFile="/etc/artica-postfix/pids/exec.zipproxy.php.zipproxy_access.time";
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_sec($TimeFile);
			if($CacheSchedules>60){
				$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.zipproxy.php --access >/dev/null 2>&1 &");
				$this->squid_rtt_events("Running $cmd");
				shell_exec($cmd);
			}
			
	}
		
//**********************************************************************************************************************		
		//Regénères les états de l'interface ( toutes les 10mn)	
		$TimeFile="/etc/artica-postfix/pids/SQUID_ALL_STATUS.time";
		$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min($TimeFile);
		_statussquid("exec.squid.watchdog.php --all-status {$CacheSchedules}Mn/10");
		if($CacheSchedules>20){
			@unlink($TimeFile);
			@file_put_contents($TimeFile, time());
			$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.squid.watchdog.php --all-status >/dev/null 2>&1 &");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$this->shell_exec2($cmd);
		}

//************************************************************************************************************

        if(!is_file("/etc/cron.d/squid-storedir-5mn")){
            $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("squid-storedir-5mn","*/5 * * * *","exec.squid.php.storedir.php");
            shell_exec("/etc/init.d/cron reload");

        }

		$system_is_overloaded=system_is_overloaded();
		if($system_is_overloaded){
            squid_admin_mysql(1,"{OVERLOADED_SYSTEM}",
            $GLOBALS["CLASS_UNIX"]->ps_mem_report(),
            __FILE__,__LINE__);
			_statussquid("{OVERLOADED_SYSTEM}");
            return false;
		}
		

			
		    if(is_file("/etc/init.d/c-icap")) {
                $CacheSchedules = $GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.c-icap.php.purge.time");
                if ($CacheSchedules > 20) {
                    $cmd = trim($prefixcmd . "/usr/share/artica-postfix/exec.c-icap.php --purge --schedule >/dev/null 2>&1 &");
                    if ($GLOBALS["VERBOSE"]) {
                        echo "$cmd\n";
                    }
                    $this->shell_exec2($cmd);
                }
            }
		
		
		    if(is_file("/etc/init.d/ufdb")) {
                $CacheSchedules = $GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/ARTICA_WEBFILTER_DB_STATUS");
                if ($CacheSchedules > 30) {
                    @unlink("/etc/artica-postfix/ARTICA_WEBFILTER_DB_STATUS");
                    $cmd = trim($prefixcmd . "/usr/share/artica-postfix/exec.squidguard.php --artica-db-status >/dev/null 2>&1 &");
                    if ($GLOBALS["VERBOSE"]) {echo "$cmd\n";}
                    $this->shell_exec2($cmd);
                }

                if(is_file("$ArticaDBPath/VERSION")){
                    $CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/logs/web/categories-db.size.db");
                    if($CacheSchedules>20){
                        $cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.catz-db.php --databasesize >/dev/null 2>&1 &");
                        if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
                        $this->shell_exec2($cmd);
                    }
                }
            }
		
		
		
		
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/logs/squid.compilation.params");
			if($CacheSchedules>1280){
				$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.squid.php --compilation-params >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}
		
			
		

			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squid.php.build_schedules_tests.time");
            $this->AutomaticToSyslog("exec.squid.php.build_schedules_tests.time = {$CacheSchedules}Mn");

			if($CacheSchedules>10){
				$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.squid.php --build-schedules-test >/dev/null 2>&1 &");
				if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
				$this->shell_exec2($cmd);
			}

		

		
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/CACHES_SQUID_SCHEDULE");
            $this->AutomaticToSyslog("CACHES_SQUID_SCHEDULE = {$CacheSchedules}Mn");
			if($CacheSchedules>1440){
				$cmd=trim($prefixcmd."/usr/share/artica-postfix/exec.squid.php --build-schedules >/dev/null 2>&1 &");
				@unlink("/etc/artica-postfix/CACHES_SQUID_SCHEDULE");
				@file_put_contents("/etc/artica-postfix/CACHES_SQUID_SCHEDULE", time());
				$this->shell_exec2($cmd);
			}
				


			if(is_file("/usr/share/artica-postfix/exec.kerbauth.php")){
				if($EnableKerbAuth==1){
					$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.kerbauth.php.1");
					if($time>30){
						$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix/exec.kerbauth.php --klist >/dev/null 2>&1 &"));
						$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix/exec.kerbauth.php --winbinddpriv >/dev/null 2>&1 &"));
						@unlink("/etc/artica-postfix/pids/exec.kerbauth.php.1");
						@touch("/etc/artica-postfix/pids/exec.kerbauth.php.1");
					}
				}
			}




		$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.clean.logs.php.squidClean.time");
        $this->AutomaticToSyslog("exec.clean.logs.php.squidClean.time = {$CacheSchedules}Mn");
		if($time>15){
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix/exec.clean.logs.php --squid >/dev/null 2>&1 &"));
		}
		
		
		
		
		if(!is_file("/etc/artica-postfix/SQUID_TEMPLATE_DONE")){
			squid_admin_mysql(1, "SQUID_TEMPLATE_DONE: No such file, launch build template action...", null,__FILE__,__LINE__);
			$this->shell_exec2(trim($prefixcmd."/usr/share/artica-postfix/exec.squid.php --tpl-save >/dev/null 2>&1 &"));
		}
	}
	
	

	
	private function shell_exec2($cmdline){
		if(function_exists("shell_exec2")){shell_exec2($cmdline);return;}
		_statussquid($cmdline);
		$text_cmdline=$cmdline;
		if(preg_match("#\/artica-postfix\/(.+?)$#", $cmdline,$re)){
			$text_cmdline=$re[1];
		}
		
		$this->events("Execute: $text_cmdline",__CLASS__."/".__FUNCTION__,__LINE__);
		shell_exec($cmdline);
	
	}
	
	private function LoadClasses(){
		if(!isset($GLOBALS["CLASS_UNIX"])){
			include_once("/usr/share/artica-postfix/framework/class.unix.inc");
			$GLOBALS["CLASS_UNIX"]=new unix();
		}
		
		if(!isset($GLOBALS["SQUIDBIN"])){$GLOBALS["SQUIDBIN"]=$GLOBALS["CLASS_UNIX"]->LOCATE_SQUID_BIN();}
		
		if(!isset($GLOBALS["CLASS_SOCKETS"])){
			include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
			$GLOBALS["CLASS_SOCKETS"]=new sockets();
		}
		
		_statussquid("LoadClasses() done");
		
	}

	private function events($text,$function=null,$line=0){
		$filename=basename(__FILE__);
		$function=__CLASS__."/".$function;
		$GLOBALS["CLASS_UNIX"]->events("$text","/var/log/artica-stats-executor.log",false,$function,$line,$filename);
	}	
	
}