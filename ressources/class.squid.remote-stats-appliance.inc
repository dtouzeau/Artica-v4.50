<?php
include_once(dirname(__FILE__).'/class.ccurl.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__)."/class.mysql.dump.inc");


class squid_stats_appliance{
	public $URI=null;
	public $workdir=null;
	private $ASROOT=false;
	private $uuid=null;
	
	function __construct(){
		if(posix_getuid()==0){$this->ASROOT=true;}
		$this->workdir=dirname(__FILE__)."/squid-export";
		$this->URI=$this->SquidStatsApplianceUri();
		$sock=new sockets();
		$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
		if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
		if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
		$this->uuid=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID");
	}
	
	
	private function SquidStatsApplianceUri(){
		
		if($GLOBALS["AS_ROOT"]){
			$RemoteStatisticsApplianceSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteStatisticsApplianceSettings")));
		}else{
			$sock=new sockets();
			$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO('RemoteStatisticsApplianceSettings')));
		}
		if(!isset($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
		if(!isset($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
		if(!isset($RemoteStatisticsApplianceSettings["SERVER"])){$RemoteStatisticsApplianceSettings["SERVER"]=null;}
		if(!is_numeric($RemoteStatisticsApplianceSettings["SSL"])){$RemoteStatisticsApplianceSettings["SSL"]=1;}
		if(!is_numeric($RemoteStatisticsApplianceSettings["PORT"])){$RemoteStatisticsApplianceSettings["PORT"]=9000;}
		$REMOTE_SSERVER=$RemoteStatisticsApplianceSettings["SERVER"];
		$REMOTE_SPORT=$RemoteStatisticsApplianceSettings["PORT"];
		$REMOTE_SSL=$RemoteStatisticsApplianceSettings["SSL"];
		if($REMOTE_SSL==1){$refix="https";}else{$refix="http";}
		return "$refix://$REMOTE_SSERVER:$REMOTE_SPORT";		
		
	}
	private function file_size_human($path){
		if(!class_exists("unix")){return;}
		$unix=new unix();
		exec($unix->find_program("du")." -h $path ",$results);
		foreach ($results as $num=>$line){
			if(preg_match("#^([0-9A-Z,]+)\s+#",$line,$re)){
				return $re[1];break;
			}
		}
		return 0;
	}	

	
	public function Replicate(){
		$unix=new unix();
		$curl=new ccurl("$this->URI/squid.stats.listener.php");
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Get tables indexes...\n";}
		$curl->parms["SQUID_TABLES_INDEX"]="yes";
		if(!$curl->get()){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Get tables indexes failed with error: $curl->error...\n";}
			squid_admin_mysql(1, "Check $this->URI failed with error: $curl->error", __FUNCTION__, __FILE__, __LINE__, "squidstats");
			return;
		}
		
		if(preg_match("#<ERROR>(.*?)</ERROR>#is", $curl->data,$re)){
				squid_admin_mysql(1, "Error from central server: {$re[1]}", __FUNCTION__, __FILE__, __LINE__, "squidstats");
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Error from central server: Protocol error\n";}
				return;
		}
		if(!preg_match("#<SUCCESS>(.*?)</SUCCESS>#is", $curl->data,$re)){
			if($GLOBALS["VERBOSE"]){echo $curl->data."\n";}
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Error from central server: Protocol error\n";}
			squid_admin_mysql(1, "Error from central server: Protocol error", 
			__FUNCTION__, __FILE__, __LINE__, "squidstats");return;
		}
		$array=unserialize(base64_decode($re[1]));
		if(!is_array($array)){
			squid_admin_mysql(1, "Error from central server: Protocol error (not an array)",__FUNCTION__, __FILE__, __LINE__, "squidstats");
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Error from central server: Protocol error (not an array)\n";}
			return;
		}
			
		if(count($array)==0){
			squid_admin_mysql(1, "Error from central server: Protocol error no item",__FUNCTION__, __FILE__, __LINE__, "squidstats");
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Error from central server: Protocol error no item\n";}
			return;
		}
		
		
		
		if(!is_dir($this->workdir)){@mkdir($this->workdir,0775,true);}
		
		
		while (list ($filename, $none) = each ($array) ){
			$curl=new ccurl("$this->URI/$filename");
			$target="$this->workdir/".basename($filename);
			if($GLOBALS["VERBOSE"]){echo "Replicate():: TARGET::: `$target`\n";}
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: downloading `$filename`\n";}
			if(!is_file($target)){if($GLOBALS["VERBOSE"]){echo "Replicate():: TARGET::: `$target` NO SUCH FILE....\n";}}
			$TARGETS[]="$this->workdir/".basename($filename);
			if($GLOBALS["VERBOSE"]){echo "Replicate():: TARGET::: DOWNLOADING `$target`....\n";}
			if(!$curl->GetFile($target)){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: downloading `$filename` failed\n";}
				if($GLOBALS["VERBOSE"]){echo "Replicate():: TARGET::: DOWNLOADING $this->URI/$filename failed....\n";}
				squid_admin_mysql(1, "Check $this->URI/$filename failed with error: $curl->error", __FUNCTION__, __FILE__, __LINE__, "squidstats");
				return;
			}
		}
		
		$mysqlbin=$unix->find_program("mysql");
		$q=new mysql();
		$password=null;
		if($q->mysql_server=="127.0.0.1"){$servcmd=" --socket=/var/run/mysqld/mysqld.sock ";}else{$servcmd=" --host=$q->mysql_server --port=$q->mysql_port ";}
		if($q->mysql_password<>null){$password=" --password=$q->mysql_password ";}
		$cmdline="$mysqlbin --batch --force --user=$q->mysql_admin$password $servcmd";
		$c=0;
		while (list ($index, $sourcefile) = each ($TARGETS) ){
			
			$destinationfile=$sourcefile.".sql";
			$localdatabase=basename($sourcefile);
			
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: extracting `$sourcefile`\n";}
			if(!$unix->uncompress($sourcefile, "$destinationfile")){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Replicate $localdatabase MySQL failed (uncompress $sourcefile) failed..\n";}
				squid_admin_mysql(1, "replication failed uncompress $sourcefile", 
				__FUNCTION__, __FILE__, __LINE__, "squidstats");
				continue;
			}
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: importing `$localdatabase` (". $this->file_size_human($destinationfile).")\n";}
			$localdatabase=str_replace(".gz", "", $localdatabase);
			$cmd="$cmdline --database=$localdatabase <$destinationfile 2>&1";
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			
			exec($cmd,$results);
			@unlink($destinationfile);
			if($q->IS_ERROR_DUMP_CMDLINE($results)){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Replicate $localdatabase MySQL tables failed..\n";}
				squid_admin_mysql(1, "replication failed restoring $localdatabase with $destinationfile with error $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "squidstats");
				continue;
			}
			$c++;
			squid_admin_mysql(1, "replication success restoring database $localdatabase", __FUNCTION__, __FILE__, __LINE__, "squidstats");
		} 
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Replicate $c MySQL tables done..\n";}
		
		
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Replicate specifics tokens for this node...\n";}
		$array=$this->GetSquidDefinedSettings();
		
		if(!is_array($array)){if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Replicate `squid_nodes_settings` is empty for me `$this->uuid` ask to main datas\n";}}
			
			
		if(is_array($array)){	
			$sock=new sockets();
			foreach ($array as $key=>$val){
				if($key=="uuid"){continue;}
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Replicate $key = `$val`\n";}
				$sock->SET_INFO($key, $val);
				
			}
		}
		
	}
	
	public function GetSquidDefinedSettings(){
		$curl=new ccurl("$this->URI/squid.stats.listener.php");
		$curl->parms["squid_nodes_settings"]=$this->uuid;
		
		if(!$curl->get()){
			squid_admin_mysql(1, "Check $this->URI failed for squid_nodes_settings table with error: $curl->error", __FUNCTION__, __FILE__, __LINE__, "squidstats");
			return;
		}

		if(preg_match("#<ERROR>(.*?)</ERROR>#is", $curl->data,$re)){
			squid_admin_mysql(1, "Error from central server: {$re[1]}", __FUNCTION__, __FILE__, __LINE__, "squidstats");
			return;
		}
		
		if(!preg_match("#<CONTENT>(.*?)</CONTENT>#is", $curl->data,$re)){
			
			squid_admin_mysql(1, "Failed to parse returned value for table content squid_nodes_settings", __FUNCTION__, __FILE__, __LINE__, "squidstats");
		}
		$content=$re[1];
		$q=new mysql();
		$q->QUERY_SQL("TRUNCATE TABLE squid_nodes_settings","artica_backup");
		$q->QUERY_SQL("INSERT INTO squid_nodes_settings (uuid,content) VALUES ('$this->uuid','$content')","artica_backup");
		$array=unserialize(base64_decode($content));
		return $array;
		
	}
	
	
	public function GET_INDEX(){
		if(!is_file("$this->workdir/artica_backup.gz")){$this->export_tables();}
		if(!is_file("$this->workdir/squidlogs.gz")){$this->export_tables();}
		if(!is_file("$this->workdir/artica_backup.gz")){echo "<ERROR>artica_backup.gz no such file</ERROR>";die("DIE " .__FILE__." Line: ".__LINE__);}
		if(!is_file("$this->workdir/squidlogs.gz")){echo "<ERROR>artica_backup.gz no such file</ERROR>";die("DIE " .__FILE__." Line: ".__LINE__);}	
		if(!is_file("$this->workdir/settingsHD.gz")){$this->export_artica_conf();}	
		if(!is_file("$this->workdir/settingsHD.gz")){echo "<ERROR>settingsHD.gz no such file</ERROR>";die("DIE " .__FILE__." Line: ".__LINE__);}	
		$base="ressources/".basename($this->workdir);
		$array["$base/artica_backup.gz"]=true;
		$array["$base/squidlogs.gz"]=true;
		
		echo "<SUCCESS>".base64_encode(serialize($array))."</SUCCESS>";
		
	}
	
	private function GET_REPLICATED_TOKENS(){
		$array["ArticaSquidParameters"]=true;
		$array["hasProxyTransparent"]=true;
		$array["UseTProxyMode"]=true;
		$array["KernelSendRedirects"]=true;
		$array["SquidTransparentMixed"]=true;

		$array["ufdbguardConfig"]=true;
		$array["EnableMalwarePatrol"]=true;
		$array["DDClientConfig"]=true;
		$array["AllowAllNetworksInSquid"]=true;
		$array["EnableDDClient"]=true;
		$array["SquidAutoblock"]=true;	
		$array["SquidDisableAllFilters"]=true;	
		$array["UseDynamicGroupsAcls"]=true;
		$array["SquidNTLMKeepAlive"]=true;
		$array["DynamicGroupsAclsTTL"]=true;
		$array["KerbAuthInfos"]=true;
		$array["EnableKerbAuth"]=true;
		$array["SquidQuotasParams"]=true;
		$array["SquidBoosterMem"]=true;
		$array["SquidBoosterMemK"]=true;
		$array["SquidBoosterOnly"]=true;
		$array["EnableSplashScreen"]=true;
		$array["EnableSplashScreenAsObject"]=true;
		$array["SquidSafePortsList"]=true;
		$array["SplashScreenURI"]=true;	
		$array["HotSpotConfig"]=true;
		$array["CicapEnabled"]=true;
		$array["EnableKerbAuth"]=true; 
		$array["SquidBubbleMode"]=true;
		$array["SquidClientParams"]=true;
		$array["SambaAdInfos"]=true; 
		$array["LockKerberosAuthentication"]=true; 
		$array["KerbAuthDisableGroupListing"]=true;
		$array["KerbAuthDisableNormalizeName"]=true;
		$array["DisableAnyCache"]=true;
		$array["DisableGoogleSSL"]=true;
		$array["AclsOptions"]=true;
		$array["UfdbguardSMTPNotifs"]=true;
		$array["SquidDebugAcls"]=true;
		$array["CacheReplacementPolicy"]=true;
		$array["SquidDebugCacheProc"]=true;
		$array["SquidSafePortsList"]=true;
		$array["SquidSafePortsSSLList"]=true;
		$array["EnableSquidPortsRestrictions"]=true;
		$array["SquidStandardLDAPAuth"]=true;
		$array["SquidLdapAuthEnableGroups"]=true;
		$array["SquidLdapAuthBanner"]=true;
		$array["NtpdateAD"]=true;
		
		
		$array["ForceWindowsUpdateCaching"]=true;
		$array["KerbAuthMethod"]=true;
		$array["UpdateUtilityRedirectEnable"]=true;
		$array["UpdateUtilityHTTPSRV"]=true;
		$array["UpdateUtilityExternWbsrvAddr"]=true;
		$array["UpdateUtilityExternWbsrv"]=true;	

		
		$array["BackupMaxDays"]=true;
		$array["BackupMaxDaysDir"]=true;
		$array["BackupSquidLogsNASIpaddr"]=true;
		$array["BackupSquidLogsNASFolder"]=true;
		$array["BackupSquidLogsNASUser"]=true;
		$array["BackupSquidLogsNASPassword"]=true;
		
		$array["EnableITChart"]=true;
		$array["ItChartFreeWeb"]=true;
		
		return $array;
	}
	
	
	public function REPLICATE_ETC_ARTICA_CONFS(){
		$unix=new unix();
		$sock=new sockets();
		$filetemp=$unix->FILE_TEMP().".gz";
		$desttemp=$unix->FILE_TEMP().".art";
		$curl=new ccurl("$this->URI/ressources/squid-export/settingsHD.gz");
		
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Replicate $this->URI [settingsHD.gz]...\n";}
		if(!$curl->GetFile($filetemp)){
			squid_admin_mysql(1, "Check $this->URI/ressources/squid-export/settingsHD.gz failed with error: $curl->error", 
			__FUNCTION__, __FILE__, __LINE__, "squidstats");
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Replicate FAILED with error: $curl->error...\n";}
			return;
		}
		if(!is_file($filetemp)){squid_admin_mysql(1, "Check $filetemp no such file...", __FUNCTION__, __FILE__, __LINE__, "squidstats");}
		if(!$unix->uncompress($filetemp, $desttemp)){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Replicate FAILED with error: Uncompress $filetemp error\n";}
			squid_admin_mysql(1, "Uncompress $filetemp error", __FUNCTION__, __FILE__, __LINE__, "squidstats");return;}
			@unlink($filetemp);
			$array=unserialize(base64_decode(@file_get_contents($desttemp)));
			$c=0;
			
		if(!is_array($array)){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Replicate FAILED with error: $desttemp not an array\n";}
			squid_admin_mysql(1, "$desttemp not an array....", __FUNCTION__, __FILE__, __LINE__, "squidstats");
			return;
		}
		
		$TOKENS=$this->GET_REPLICATED_TOKENS();
		
		foreach ($array as $key=>$value){
			if(!isset($TOKENS[$key])){continue;}
			if($GLOBALS["VERBOSE"]){echo "Saving $key ". strlen( (string) $value)." bytes...\n";}
			$sock->SET_INFO($key, $value);
			$c++;
		}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SERV]: Replicate $c parameters done..\n";}
		squid_admin_mysql(1, "Success applied  $c parameters", __FUNCTION__, __FILE__, __LINE__, "squidstats");
		
	}
	
	private function export_artica_conf(){
		
		if($GLOBALS["AS_ROOT"]){
			$unix=new unix();
			$php5=$unix->LOCATE_PHP5_BIN();
			shell_exec("$php5 /usr/share/artica-postfix/exec.export-artica-settings.php");
			return;
		}
		
		$sock=new sockets();
		$sock->getFrameWork("services.php?export-etc-artica=yes&MyCURLTIMEOUT=120");
	}
	
	
	public function export_tables(){
		$this->export_artica_conf();
		$q=new mysql();
		$q->BD_CONNECT();
		$artica_backup["squid_pools"]=true;
		$artica_backup["squid_balancers"]=true;
		$artica_backup["squid_header_access"]=true;
		$artica_backup["squid_parents_acls"]=true;
		$artica_backup["usersisp"]=true;
		$artica_backup["squid_ssl"]=true;
		$artica_backup["squid_accel"]=true;
		$artica_backup["websites_caches_params"]=true;
		$artica_backup["squid_speed"]=true;
		$artica_backup["squid_block"]=true;
		$artica_backup["dansguardian_files"]=true;
		$artica_backup["squid_white"]=true;
		
		$artica_backup["sslcertificates"]=true;
		$artica_backup["squid_nodes_settings"]=true;
		

		$artica_backup["ext_time_quota_acl"]=true;
		$artica_backup["ext_time_quota_acl_rules"]=true;
		$artica_backup["ext_time_quota_acl_link"]=true;
		
		  
		
		$databases["artica_backup"]=$artica_backup;
		if(!is_dir($this->workdir)){@mkdir($this->workdir,0775,true);}
		$dump=new phpMyDumper("artica_backup",$q->mysqli_connection,"$this->workdir/artica_backup.gz",true,$artica_backup);
		$dump->doDump();
		
		$q=new mysql_squid_builder();
		$q->BD_CONNECT();
		$squidlogs["cacheconfig"]=true;
		$squidlogs["webfilters_sqacls"]=true;
		$squidlogs["webfilters_sqaclaccess"]=true;
		$squidlogs["webfilters_sqgroups"]=true;
		$squidlogs["webfilters_sqacllinks"]=true;
		$squidlogs["webfilters_sqitems"]=true;
		$squidlogs["webfilters_sqtimes_rules"]=true;
		$squidlogs["webfilters_blkwhlts"]=true;
		$squidlogs["webfilters_usersasks"]=true;
		$squidlogs["webfilters_quotas"]=true;
		$squidlogs["webfilter_avwhitedoms"]=true;
		$squidlogs["webfilter_aclsdynamic"]=true;
		
		$squidlogs["squidtpls"]=true;
		$squidlogs["itcharters"]=true;
		$squidlogs["itchartlog"]=true;
		
		
		
		$databases["squidlogs"]=$squidlogs;
		$dump=new phpMyDumper("squidlogs",$q->mysqli_connection,"$this->workdir/squidlogs.gz",true,$squidlogs);
		$dump->doDump();
		
	}
	
	
}
