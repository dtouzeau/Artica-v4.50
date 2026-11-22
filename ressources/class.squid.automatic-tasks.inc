<?php
include_once(dirname(__FILE__)."/settings.exists.inc"); // artica_setting_exists(): settings are root-only, see the file
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
		if(!artica_setting_exists("EnableProxyCompressor")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableProxyCompressor", 0);}
		if(!artica_setting_exists("EnableKerbAuth")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKerbAuth", 0);}
		if(!artica_setting_exists("SquidLogRotateFreq")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidLogRotateFreq", 1440);}
		if(!artica_setting_exists("SquidCacheLevel")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidCacheLevel", 3);}
		if(!artica_setting_exists("EnableSS5")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSS5", 0);}
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
		// Supprime : exec.squid.watchdog.php --all-status, porte dans articarest
		// (squid.LegacyWatchdog et squid.CIcapClients, toutes les 5 minutes).

//************************************************************************************************************

        // squid-storedir-5mn: exec.squid.php.storedir.php is ported to articarest
        // (squid.RefreshCacheCounters, every 5 minutes); the daemon retires the cron.

		$system_is_overloaded=system_is_overloaded();
		if($system_is_overloaded){
            squid_admin_mysql(1,"{OVERLOADED_SYSTEM}",
            $GLOBALS["CLASS_UNIX"]->ps_mem_report(),
            __FILE__,__LINE__);
			_statussquid("{OVERLOADED_SYSTEM}");
            return false;
		}
		

			
		    // Purge des dossiers temporaires de c-icap : planifiee par articarest
		    // (Squidcicap.ScheduledPurge, toutes les 20 min), exec.c-icap.php supprime.
		
		
		    if(is_file("/etc/init.d/ufdb")) {
                $CacheSchedules = $GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/ARTICA_WEBFILTER_DB_STATUS");
                if ($CacheSchedules > 30) {
                    @unlink("/etc/artica-postfix/ARTICA_WEBFILTER_DB_STATUS");

                    $this->shell_exec2("/usr/sbin/artica-phpfpm-service -rest-api /ufdb/databases/status");
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
		
		
		
		
		
			
		

			// Moteur porte dans le daemon Go (module SystemTasks):
			// `exec.squid.php --build-schedules-test` n'existe plus. Un drapeau
			// inconnu passe a un exec.*.php ne leve aucune erreur -- PHP tombe
			// dans la voie par defaut du script -- d'ou l'appel REST.
			//
			// Le fichier d'horodatage etait ecrit par build_schedules_tests()
			// elle-meme; personne ne l'ecrit plus, et file_time_min() rend
			// 10000 sur un fichier absent (framework/class.unix.inc:6018 --
			// c'est file_time_SEC qui rend 100000, l. 6031). Sans l'ecriture
			// ci-dessous le garde-fou des 10 minutes serait toujours vrai
			// (10000 > 10) et la route serait appelee a chaque passage.
			$StampSchedules="/etc/artica-postfix/pids/exec.squid.php.build_schedules_tests.time";
			$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min($StampSchedules);
            $this->AutomaticToSyslog("exec.squid.php.build_schedules_tests.time = {$CacheSchedules}Mn");

			if($CacheSchedules>10){
				@file_put_contents($StampSchedules, time());
				$this->events("REST_API /proxy/schedule/apply",__CLASS__."/".__FUNCTION__,__LINE__);
				$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/schedule/apply");
			}

		

		
			// Un second garde-fou, journalier celui-la, appelait ICI la MEME
			// route /proxy/schedule/apply sur l'horodatage
			// /etc/artica-postfix/CACHES_SQUID_SCHEDULE (>1440 mn). Retire:
			// les deux blocs sont sequentiels dans cette methode et le
			// premier a le seuil le PLUS BAS, donc il a deja appele la route
			// dans les dix minutes precedentes chaque fois que le second
			// aurait pu le faire -- il ne pouvait plus rien garder. Il ne
			// portait pas non plus d'appel different depuis que
			// `exec.squid.php --build-schedules` a disparu. Le fichier
			// CACHES_SQUID_SCHEDULE n'avait aucun autre lecteur dans les deux
			// depots (verifie); il reste sur les appliances deja deployees,
			// simplement plus rafraichi.



			// exec.kerbauth.php a ete supprime : --klist (kinit avec le compte
			// d'administration AD) et --winbinddpriv (droits sur /var/lib/samba)
			// sont desormais servis par le daemon. Le garde-fou de 30 minutes est
			// conserve, il n'appartient pas au daemon.
			if($EnableKerbAuth==1){
				$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/kerberos.hourly.time");
				if($time>30){
					$GLOBALS["CLASS_SOCKETS"]->REST_API("/mktutils/kinit");
					$GLOBALS["CLASS_SOCKETS"]->REST_API("/winbindd/privileges");
					@unlink("/etc/artica-postfix/pids/kerberos.hourly.time");
					@touch("/etc/artica-postfix/pids/kerberos.hourly.time");
				}
			}




		// ex `exec.clean.logs.php --squid` : articarest logrotate.SquidCleanTask,
		// anti-rejeu de 15 min cote daemon.
		$GLOBALS["CLASS_SOCKETS"]->REST_API("/logrotate/squid-clean");
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