<?php
include_once(dirname(__FILE__)."/class.mysql.inc");;
include_once(dirname(__FILE__)."/class.groups.inc");
include_once(dirname(__FILE__)."/class.compile.ufdbguard.expressions.inc");
include_once(dirname(__FILE__)."/class.ActiveDirectory.inc");
include_once(dirname(__FILE__)."/class.squid.inc");;
include_once(dirname(__FILE__)."/class.tcpip.inc");
class compile_ufdbguard{
	var $SquidGuardIPWebEnc="";
	var $SquidGuardIPWeb;
	var $SquidGuardIPWebSSL;
	var $EnableRemoteStatisticsAppliance=0;
	var $EnableWebProxyStatsAppliance=0;
	var $SquidGuardWebFollowExtensions=1;
	var $UfdbGuardRedirectCategories=array();
	var $DansGuardianDefaultMainRule=array();
	var $AVAILABLE_REWRITE_RULES=array();
	var $BANNED_FILES_RULES=array();
	var $BANNED_EXTDOMS_RULES=array();
	var $AVAILABLE_REGEX_RULES=array();
	var $UpdateUtilityRedirectEnable=0;
	var $SquidDatabasesArticaEnable=0;
	var $SquidGuardWebExternalUri=null;
	var $SquidGuardWebExternalUriSSL=null;
	var $UseDynamicGroupsAcls=1;
	var $KerbAuthDisableNsswitch=0;
	var $licensed=0;
	var $Ufdbguard_Version=null;
	var $Notrefreshdomainlist=0;
	var $DisableExpressionList=1;
	var $NoMalwareUris=1;
	var $EnableSquidPhishTank=0;
	var $EnableRansomwares=0;
	private $UFDBURILOG=array();
	var $WEBSECURITY_APPLIANCE=false;
	var $REDIRECT_PAGE_LOGS=array();
	var $STATS_APPLIANCE=true;
	var $CORP_LICENSE=false;
	private $FATAL_ERROR=false;
	private $CATEGORY_EVENTS=array();
    private $SquidGuardWebUseExternalUri="";

	var $Is31=false;
	
	
	function __construct(){
		if(!isset($GLOBALS["OUTPUT"])){$GLOBALS["OUTPUT"]=false;}




		
		$GLOBALS["SRC"]=array();
		$sock=new sockets();
		$usrs=new usersMenus();
		$this->CORP_LICENSE=$usrs->CORP_LICENSE;
		$MAIN_INTERFACE=null;
		if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}
		if($GLOBALS["AS_ROOT"]){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){$this->WEBSECURITY_APPLIANCE=true;}
		if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$this->STATS_APPLIANCE=true;}
		
		if(!is_file("/etc/artica-postfix/settings/Daemons/SquidDatabasesArticaEnable")){
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidDatabasesArticaEnable", 1);
			$this->SquidDatabasesArticaEnable=1;
		}else{
			$this->SquidDatabasesArticaEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDatabasesArticaEnable"));
		
		}
		
		if(!is_file("/etc/artica-postfix/settings/Daemons/SquidDatabasesUtlseEnable")){
		    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidDatabasesUtlseEnable", 1);

		}
		
		
		
		$this->SquidGuardIPWeb=$this->ExternalUriHTTP();
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		$this->EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
		
		
		$this->SquidGuardWebUseExternalUri=$sock->GET_INFO("SquidGuardWebUseExternalUri");
		$this->SquidGuardWebExternalUri=$sock->GET_INFO("SquidGuardWebExternalUri");
		$this->SquidGuardWebExternalUriSSL=$sock->GET_INFO("SquidGuardWebExternalUriSSL");
		$this->KerbAuthDisableNsswitch=$sock->GET_INFO("KerbAuthDisableNsswitch");
		$SquidGuardUseRefreshDomainList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardUseRefreshDomainList"));
		
		

		$this->SquidGuardWebFollowExtensions=$sock->GET_INFO("SquidGuardWebFollowExtensions");
		$this->UpdateUtilityRedirectEnable=$sock->GET_INFO("UpdateUtilityRedirectEnable");
		$this->UfdbGuardRedirectCategories=$this->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardRedirectCategories"));
		$this->DansGuardianDefaultMainRule=$this->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DansGuardianDefaultMainRule"));
		$this->EnableSquidPhishTank=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidPhishTank"));
		$this->EnableRansomwares=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebFilteringRansomware"));
		if(!$usrs->CORP_LICENSE){
			$this->EnableSquidPhishTank=0;
			$this->EnableRansomwares=0;
		}
		
		$ufdbguardConfig=$this->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardConfig"));
		$ufdbguardConfig=$this->SetDefaultsConfig($ufdbguardConfig);
		
		
		
		$this->Notrefreshdomainlist=$ufdbguardConfig["Notrefreshdomainlist"];
		$this->DisableExpressionList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableExpressionList"));
		
	
		

		if(!is_numeric($this->UpdateUtilityRedirectEnable)){$this->UpdateUtilityRedirectEnable=0;}
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		if(!is_numeric($this->EnableWebProxyStatsAppliance)){$this->EnableWebProxyStatsAppliance=0;}
		if(!is_numeric($this->SquidGuardWebFollowExtensions)){$this->SquidGuardWebFollowExtensions=1;}
		if(!is_numeric($this->SquidDatabasesArticaEnable)){$this->SquidDatabasesArticaEnable=1;}
		if(!is_numeric($this->KerbAuthDisableNsswitch)){$this->KerbAuthDisableNsswitch=1;}
		if($SquidGuardUseRefreshDomainList==0){$this->Notrefreshdomainlist=1;}

		


		$array["SquidGuardIPWeb"]=$this->SquidGuardIPWeb;
		$array["SquidGuardIPWebSSL"]=$this->SquidGuardIPWebSSL;
		@file_put_contents("/var/log/squid/SquidGuardIPWeb", serialize($array));
		
		
		if(is_dir("/var/lib/ftpunivtlse1fr/porn")){
			if(!is_link("/var/lib/ftpunivtlse1fr/adult")){
				system("/bin/ln -sf /var/lib/ftpunivtlse1fr/porn /var/lib/ftpunivtlse1fr/adult");
			}
			
		}
        if(trim(@file_get_contents(base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2EvLmxpYw=="))=="TRUE")){$this->licensed=1;}else{$this->licensed=0;$this->SquidDatabasesArticaEnable=0;}
        $this->Is31=true;

	}
    private function unserializeb64($data):array{
        if(is_null($data)){
            return array();
        }
        if(strlen($data)<3){
            return array();
        }
        $Decoded=base64_decode($data);
        if(!$Decoded){
            return array();
        }
        $Unser=unserialize($Decoded);
        if(!$Unser){
            return array();
        }
        return $Unser;
    }


	
	

	
	public function SetDefaultsConfig($ufdbguardConfig){
        if(!is_array($ufdbguardConfig)){
            $ufdbguardConfig=array();
        }
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
        if(!isset($ufdbguardConfig["Notrefreshdomainlist"])){$ufdbguardConfig["Notrefreshdomainlist"]=0;}
		if(!is_numeric($ufdbguardConfig["Notrefreshdomainlist"])){$ufdbguardConfig["Notrefreshdomainlist"]=0;}
		return $ufdbguardConfig;
		
	}
	
	private function build_progress($text,$prc){
		if(!function_exists("build_progress")){return;}
		build_progress($text,$prc);
	}
	
	
	public function buildConfig(){
        $UfdbGuardListen        = null;
		if($this->FATAL_ERROR==true){
			$this->build_progress("{building_rules} (failed)",110);
			return false;
		}

		$this->build_progress("{building_rules} (sources)",15);
		$sources=$this->build_sources();
		
		$this->build_progress("{building_rules} (rewrites)",20);
		$rewrite=$this->build_rewrites();
		
		$this->build_progress("{building_rules} (times)",25);
		$times=$this->build_times();
		
		$this->build_progress("{building_rules} ({categories})",30);
		$categories=$this->build_categories();
		$this->build_progress("{building_rules} (banned files)",35);
		$bannedFiles=$this->BuildBannedFiles();
		$this->build_progress("{building_rules} (banned domains)",40);
		$BannexExtDomains=$this->BuildBannedDomains();
		$this->build_progress("{building_rules} (expressions)",45);
		$build_expressions_list=$this->build_expressions_list();
		
		$this->build_progress("{building_rules} ({ACLS})",50);
		$acls=$this->build_acls();
		$SquidGuardDenySSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardDenySSL"));
		$unix=new unix();
		$major=$unix->UFDBGUARDD_MAJOR();
		$minor=$unix->UFDBGUARDD_MINOR();
        $RedirectorsArray=array();
        $OutArray=$this->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));
        if(is_array($OutArray)){
            $RedirectorsArray=$OutArray;
        }
        if(!isset($RedirectorsArray["url_rewrite_children"])){$RedirectorsArray["url_rewrite_children"]=10;}
        if(!isset($RedirectorsArray["url_rewrite_startup"])){$RedirectorsArray["url_rewrite_startup"]=5;}
        if(!isset($RedirectorsArray["url_rewrite_idle"])){$RedirectorsArray["url_rewrite_idle"]=1;}
        if(!isset($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=4;}
        $url_rewrite_concurrency=$RedirectorsArray["url_rewrite_concurrency"];
        if($url_rewrite_concurrency>32){$url_rewrite_concurrency=32;}
        $childrens=$RedirectorsArray["url_rewrite_children"];
		$threads=$url_rewrite_concurrency*$childrens;
        if($threads>1280){$threads=1280;}

		$this->build_progress("$this->SquidGuardIPWeb",51);
		$conf[]="# Engine builder on " .date('Y-m-d H:i:s');
		$conf[]="# Engine version 2.1";
		$conf[]="# SquidGuardDenySSL = $SquidGuardDenySSL";
		$conf[]=@implode("\n", $this->REDIRECT_PAGE_LOGS);
		$conf[]="dbhome /var/lib/squidguard";
		$conf[]="logdir /var/log/squid";
		$conf[]="pidfile /var/run/ufdbguard/ufdbguardd.pid";
		$conf[]="upload-crash-reports off";
		$conf[]="use-ipv6-on-wan  off";
        $conf[]="num-worker-threads $threads";
		
		$ufdbguardConfig=$this->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardConfig"));
        
        
		$ufdbguardConfig=$this->SetDefaultsConfig($ufdbguardConfig);
		$datas=$ufdbguardConfig;


		$UfdbGuardInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardInterface"));
		$ufdbguardConfig["DebugAll"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbDebugAll"));
		$RefreshUserList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RefreshUserList"));
		$RefreshDomainList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RefreshDomainList"));
		if($RefreshUserList==0){$RefreshUserList=15;}
		if($RefreshUserList<5){$RefreshUserList=5;}
		if($RefreshUserList>1440){$RefreshUserList=1440;}
		if($RefreshDomainList==0){$RefreshDomainList=15;}
		
		$this->DisableExpressionList==intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableExpressionList"));
		$this->NoMalwareUris=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoMalwareUris"));
		
		if(!is_file("/var/lib/ufdbartica/category_socialnet/domains.ufdb")){
			$nohup=$unix->find_program("nohup");
            shell_exec("$nohup /usr/sbin/artica-phpfpm-service -categories-update >/dev/null 2>&1 &");
		}
		
		
		if(!$this->Is31){$ufdbguardConfig["Notrefreshdomainlist"]=1;}
		$this->Notrefreshdomainlist=$ufdbguardConfig["Notrefreshdomainlist"];
		echo "Starting......: ".date("H:i:s")." ufdbguardd: Major:$major, Minor:$minor\n";
		
		
		$redirect_loading_database="$this->SquidGuardIPWeb?loading-database=yes";
		$redirect_fatal_error="$this->SquidGuardIPWeb?fatalerror=yes";
		if(preg_match("#(.+?)\.html(.+)#", $redirect_loading_database,$re)){$redirect_loading_database=$re[1].".html";}
		if(preg_match("#(.+?)\.html(.+)#", $redirect_fatal_error,$re)){$redirect_fatal_error=$re[1].".html";}
        $redirect_fatal_error=str_replace("??","?",$redirect_fatal_error);
        $redirect_loading_database=str_replace("??","?",$redirect_loading_database);
        $SquidGuardWebExternalUriDecrypted=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebExternalUriDecrypted"));
			

			$conf[]="url-lookup-result-during-database-reload allow";
			$conf[]="redirect-loading-database \"$redirect_loading_database\"";
            $conf[]="url-lookup-result-when-fatal-error allow";
			$conf[]="redirect-fatal-error \"$redirect_fatal_error\"";
            $conf[]="check-proxy-tunnels off";
		    $conf[]="lookup-reverse-ip off";
		
	
		
		
		$redirect_tokens="?SquidGuardIPWeb=$this->SquidGuardIPWebEnc&clientaddr=%a&clientname=%n&clientuser=%i&clientgroup=%s&targetgroup=%t&url=%u";

        $redirect_https=$this->build_redirect_page_https($redirect_tokens);
        $redirect_https=str_replace("??","?",$redirect_https);
        $conf[]=@implode("\n",$this->UFDBURILOG);
		$this->SquidGuardIPWebSSL=$redirect_https;
        if($SquidGuardWebExternalUriDecrypted==0) {
            $conf[] = "redirect-https \"$redirect_https\"";
            $conf[]="squid-uses-active-bumping off";
        }else{
            $conf[]="redirect-bumped-https \"$redirect_https\"";
            $conf[]="squid-uses-active-bumping on";
        }
		$conf[]="squid-version \"4.1\"";
		

		
		foreach ($ufdbguardConfig as $key=>$line){
				if($key=="refreshdomainlist"){continue;}
				if($line==0){$ufdbguardConfig[$key]="off";}
				if($line==1){$ufdbguardConfig[$key]="on";}
				if($line==null){$ufdbguardConfig[$key]="off";}
			}
			
			
		$conf[]="upload-stats off";
		$conf[]="logblock on";
		$conf[]="logall {$ufdbguardConfig["DebugAll"]}";
		$conf[]="ufdb-debug-filter {$ufdbguardConfig["DebugAll"]}";
		$conf[]="ufdb-debug-external-scripts {$ufdbguardConfig["DebugAll"]}";

		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StripDomainFromUsername"))==1){$conf[]="strip-domain-from-username on";}
		$conf[]="max-logfile-size  20000000";
		$conf[]="analyse-uncategorised-urls off";
		$conf[]="ufdb-show-url-details on";
		$conf[]="ufdb-expression-optimisation off";
		$conf[]="strip-domain-from-username on";
		$conf[]="refreshuserlist $RefreshUserList";
		$conf[]="refreshdomainlist $RefreshDomainList";
		$conf[]="safe-search off";
		
		
		$ufdbguardConfig=$this->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardConfig"));
		
		if($UfdbGuardInterface<>null){
			$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
			$UfdbGuardListen=$NETWORK_ALL_INTERFACES[$UfdbGuardInterface]["IPADDR"];
		}
		if($UfdbGuardListen==null){$UfdbGuardListen="all";}
		$UfdbGuardPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardPort"));if($UfdbGuardPort==0){$UfdbGuardPort=3977;}
		

		
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PythonUfdbPort", $UfdbGuardPort);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PythonUfdbServer", $UfdbGuardListen);
		
		$conf[]="port $UfdbGuardPort";
		$conf[]="interface $UfdbGuardListen";
		
		

		$conf[]="category safesearch {";
		$conf[]="\toption safe-search off";
		$conf[]="}";

		
		$conf[]="";
		if($rewrite<>null){$conf[]=$rewrite;}
		foreach ($this->CATEGORY_EVENTS as $line){
		    $conf[]="# $line";
        }
 		$conf[]=$sources;
 		$conf[]=$times;
 		$conf[]=$bannedFiles;
 		$conf[]=$BannexExtDomains;
 		$conf[]=$build_expressions_list;
 		$conf[]=$categories;
 		$conf[]=$acls;	
 		$this->build_progress("{building_rules} {done}",55);
 		return @implode("\n", $conf);
 		
		
	}
	
	
	private function build_expressions_list(){
		$main_path="/var/lib/squidguard";
		
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$sql="SELECT * FROM webfilter_ufdbexpr WHERE enabled=1";
		$results=$q->QUERY_SQL($sql);
		$mainArray=array();
		$f=array();
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard fatal error $q->mysql_error\n";return null;}
		echo "Starting......: ".date("H:i:s")." ufdbGuard ". count($results)." expressions rules\n";
		foreach($results as $index=>$ligne){
			$t=new compile_ufdbguard_expression($ligne["ID"]);
			if(count($t->REGEX_TABLE)==0){continue;}
			$this->AVAILABLE_REGEX_RULES[$ligne["ruleid"]]="RegexRule{$ligne["ruleid"]}";
			$finalMainArray=@implode(".*", $t->REGEX_TABLE);
			$mainArray[$ligne["ruleid"]][]=$finalMainArray;
		}
		foreach ($mainArray as $ruleid=>$patternsArray){
			@mkdir("$main_path/RegexRule$ruleid",0755,true);

			@file_put_contents("$main_path/RegexRule$ruleid/expressions", @implode("\n", $patternsArray));
			$f[]="category \"RegexRule$ruleid\"{";
			$f[]="expressionlist	\"$main_path/RegexRule$ruleid/expressions\"";
			$f[]="}";
			$f[]="";			
			
		}
		
		return @implode("\n", $f);
		
	}
	
	
	
	private function build_rewrites(){
		$f=array();
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");

		
		$sql="SELECT * FROM webfilters_rewriterules WHERE enabled=1 AND ItemsCount>0";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard fatal error $q->mysql_error\n";return null;}
		foreach($results as $index=>$ligne){
			$rules=$this->build_rewrites_items($ligne["ID"]);
			if(count($rules)==0){continue;}
			$this->AVAILABLE_REWRITE_RULES[$ligne["ID"]]=true;
			$f[]="rewrite ReWriteRule{$ligne["ID"]}{";
			$f[]=@implode("\n", $rules);
			$f[]="}";
			
			
		}
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUG] build_rewrites() -> ".count($f)." lines\n";}
		if(count($f)>0){return @implode("\n",$f)."\n";}
        return "";
	}
	
	private function build_rewrites_items($ruleid){
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$items=array();
		$sql="SELECT * FROM webfilters_rewriteitems WHERE enabled=1 AND ruleid=$ruleid";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard fatal error $q->mysql_error\n";return $items;}
		foreach($results as $index=>$ligne){
			$t[]="\ts@{$ligne["frompattern"]}@{$ligne["topattern"]}@i";
		}
		return $t;
	}
	
	private function BuildBannedFiles(){
		
		echo "Starting......: ".date("H:i:s")." * * * Building BuildBannedFiles (L.".__LINE__.") * * *\n";
		$main_path="/var/lib/squidguard";
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$sql="SELECT ruleid,ext FROM webfilter_bannedexts WHERE enabled=1";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." $q->mysql_error\n";}
		$banned=array();
		echo "Starting......: ".date("H:i:s")." BuildBannedFiles ". count($results)." rules...\n";
		foreach($results as $index=>$ligne){
			$ligne["ext"]=trim($ligne["ext"]);
			if(trim($ligne["ext"])==null){continue;}
			$ligne["ext"]=str_replace(".", "", $ligne["ext"]);
			$ligne["ext"]=str_replace("*", "", $ligne["ext"]);
			$banned[$ligne["ruleid"]][]=$ligne["ext"];
			$this->BANNED_FILES_RULES[$ligne["ruleid"]]="FileBlock{$ligne["ruleid"]}";
		}
		$f=array();
		foreach ($banned as $ruleid=>$exts){
			@mkdir("$main_path/FileBlock$ruleid",0755,true);
			$FINAL=array();

			foreach ($exts as $b){
				if(trim($b)==null){continue;}
				$FINAL[]=".*?\/.*\.{$b}$";
			}
			
			echo "Starting......: ".date("H:i:s")." ufdbGuard FileBlock{$ruleid} ".count($FINAL)." Files\n";
			@file_put_contents("$main_path/FileBlock{$ruleid}/expressions",@implode("\n", $FINAL));
			$f[]="category \"FileBlock{$ruleid}\"{";
			$f[]="expressionlist	\"$main_path/FileBlock{$ruleid}/expressions\"";
			$f[]="}";
			$f[]="";
			
		}
		
		return @implode("\n", $f);
		
	}
	
	private function BuildBannedDomains(){
		$main_path="/var/lib/squidguard";
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$sql="SELECT ruleid,ext FROM webfilter_bannedextsdoms WHERE enabled=1";
		$results=$q->QUERY_SQL($sql);
		$banned=array();
		foreach($results as $index=>$ligne){
			if(trim($ligne["ext"])==null){continue;}
			$ligne["ext"]=str_replace(".", "", $ligne["ext"]);
			$ligne["ext"]=str_replace("*", "", $ligne["ext"]);
			$banned[$ligne["ruleid"]][]="([0-9a-z\-\_]+\.{$ligne["ext"]})$";
			$this->BANNED_EXTDOMS_RULES[$ligne["ruleid"]]="DomExtBlock{$ligne["ruleid"]}";
		}
		$f=array();
        foreach ($banned as $ruleid=>$exts){
			@mkdir("$main_path/DomExtBlock{$ruleid}",0755,true);
			$pattern=@implode("\n", $exts);
			@file_put_contents("$main_path/DomExtBlock{$ruleid}/expressions", $pattern);
			$f[]="category \"DomExtBlock{$ruleid}\"{";
			$f[]="expressionlist	\"$main_path/DomExtBlock{$ruleid}/expressions\"";
			$f[]="}";
			$f[]="";
		}
		
		return @implode("\n", $f);
		
	}	
	
	private function build_times(){
		$GLOBALS["TIMESRULES"]=array();
		if($GLOBALS["VERBOSE"]){echo " ***** ***** TIME : build TIMES ....\n";}
        $TimeSpace=array();
        $f=array();
        if(!isset($TimeSpace["TIMES"])){$TimeSpace["TIMES"]=array();}

		$ligne=$this->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DansGuardianDefaultMainRule"));
        if(isset($ligne["TimeSpace"])) {
            $TimeSpace = $this->unserializeb64($ligne["TimeSpace"]);
        }
		if(is_array($TimeSpace)){
			if(is_array($TimeSpace["TIMES"])){
				if(count($TimeSpace["TIMES"])>0){
					if($TimeSpace["RuleMatchTime"]==null){$TimeSpace["RuleMatchTime"]="none";}
					if($TimeSpace["RuleAlternate"]==null){$TimeSpace["RuleAlternate"]="none";}
					if($TimeSpace["RuleMatchTime"]<>null){
						
						$R=array();
                        foreach ($TimeSpace["TIMES"] as $TIMEID=>$array){
							if(count($array["DAYS"])==0){continue;}
							$dd=array();
                            foreach ($array["DAYS"] as $day=>$val){if($val==1){$dd[]=$day;}}
							if(count($dd)==0){continue;}
							if(strlen($array["BEGINH"])==1){$array["BEGINH"]="0{$array["BEGINH"]}";}
							if(strlen($array["BEGINM"])==1){$array["BEGINM"]="0{$array["BEGINM"]}";}
							if(strlen($array["ENDH"])==1){$array["ENDH"]="0{$array["ENDH"]}";}
							if(strlen($array["ENDM"])==1){$array["ENDM"]="0{$array["ENDM"]}";}
							$R[]="\tweekly ".@implode("", $dd)." {$array["BEGINH"]}:{$array["BEGINM"]} - {$array["ENDH"]}:{$array["ENDM"]}";
						}
						if(count($R)>0){
							$f[]="time timeR0 {";
							$f[]=@implode("\n", $R);
							$f[]="}";
							if($TimeSpace["RuleMatchTime"]=="inside"){$TimeSpace["RuleMatchTime"]="within";}
							$GLOBALS["TIMESRULES"][0]["MATCH"]=$TimeSpace["RuleMatchTime"];
							$GLOBALS["TIMESRULES"][0]["NEXT"]=$TimeSpace["RuleAlternate"];
						}
						
						
					}
				
				}
				
			}
			
			
		}
		
		
		
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$sql="SELECT ID,TimeSpace FROM webfilter_rules WHERE enabled=1 ORDER BY zOrder";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard fatal error $q->mysql_error\n";}
		
		if(count($results)==0){
			if($GLOBALS["VERBOSE"]){echo " ***** ***** TIME : SELECT ID,TimeSpace FROM webfilter_rules WHERE enabled=1 ORDER BY zOrder = 0 rules\n";}
		}else{
			if($GLOBALS["VERBOSE"]){echo " ***** ***** TIME : SELECT ID,TimeSpace FROM webfilter_rules WHERE enabled=1 ORDER BY zOrder = ".count($results)." rules\n";}
		}
		
		foreach($results as $index=>$ligne){
			$TimeSpace=$this->unserializeb64($ligne["TimeSpace"]);
			if(!is_array($TimeSpace)){continue;}
            if(!isset($TimeSpace["TIMES"])){continue;}
			if(!is_array($TimeSpace["TIMES"])){continue;}

			if($TimeSpace["RuleMatchTime"]==null){$TimeSpace["RuleMatchTime"]="none";}
			if($TimeSpace["RuleAlternate"]==null){$TimeSpace["RuleAlternate"]="none";}	
			if($TimeSpace["RuleMatchTime"]=="none"){
				if($GLOBALS["VERBOSE"]){echo " ***** ***** TIME : RuleMatchTime:{$TimeSpace["RuleMatchTime"]} -> none\n";}
				continue;}
			
			$CountOfTimeSpace=count($TimeSpace["TIMES"]);
			if($GLOBALS["VERBOSE"]){echo " ***** ***** TIME : TimeSpaces: $CountOfTimeSpace\n";}
			
			if(count($TimeSpace["TIMES"])==0){continue;}
			
			$R=array();
			foreach ($TimeSpace["TIMES"] as $TIMEID=>$array){
				if(count($array["DAYS"])==0){continue;}
				$dd=array();
				foreach ($array["DAYS"] as $day=>$val){if($val==1){$dd[]=$day;}}
				if(count($dd)==0){continue;}
				if(strlen($array["BEGINH"])==1){$array["BEGINH"]="0{$array["BEGINH"]}";}
				if(strlen($array["BEGINM"])==1){$array["BEGINM"]="0{$array["BEGINM"]}";}
				if(strlen($array["ENDH"])==1){$array["ENDH"]="0{$array["ENDH"]}";}
				if(strlen($array["ENDM"])==1){$array["ENDM"]="0{$array["ENDM"]}";}
				$R[]="\tweekly ".@implode("", $dd)." {$array["BEGINH"]}:{$array["BEGINM"]} - {$array["ENDH"]}:{$array["ENDM"]}";
			}
			if(count($R)==0){continue;}
			$f[]="time timeR{$ligne["ID"]} {";
			$f[]=@implode("\n", $R);
			$f[]="}";
			if($TimeSpace["RuleMatchTime"]=="inside"){$TimeSpace["RuleMatchTime"]="within";}
			$GLOBALS["TIMESRULES"][$ligne["ID"]]["MATCH"]=$TimeSpace["RuleMatchTime"];
			$GLOBALS["TIMESRULES"][$ligne["ID"]]["NEXT"]=$TimeSpace["RuleAlternate"];
			
		}
		
		return @implode("\n", $f);
		
		
	}
	
	
function ParseLDAPSubUsersGroups($LDAPID,$dn){
	if($GLOBALS["VERBOSE"]){writelogs("LDAPID: $LDAPID, Parsing DN=$dn ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	$ad=new ActiveDirectory($LDAPID);
	$Array=$ad->search_users_from_group($dn);
	if($GLOBALS["VERBOSE"]){writelogs("DN=$dn items=`". count($Array)."`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	if($ad->ldap_last_error<>null){writelogs("FAILED to lookup dn $dn",__FUNCTION__,__FILE__,__LINE__);}
	$f=array();
	if(count($Array)==0){
		writelogs("Lookup dn $dn return no user !!!",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		return array();
	}
	
	
	if($GLOBALS["VERBOSE"]){writelogs("DN=$dn START LOOP",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	
	foreach ($Array as $dn=>$GPARR){
		$dnEnc=base64_encode($dn);
		$type=$GPARR["TYPE"];
		$f=array();
		if($GLOBALS["VERBOSE"]){writelogs("DN=$dn type=`$type`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		
		if($type=="group"){
			if($ad->LDAP_RECURSIVE==1){
				writelogs("Group -> ParseUsersGroups($LDAPID,$dn,)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$newrow=$this->ParseLDAPSubUsersGroups($LDAPID,$dn);
				if(count($newrow)>0){
				    foreach ($newrow as $a=>$b){$f[]=$b;}
				}
			}
			continue;
		}
			if($GLOBALS["VERBOSE"]){writelogs("DN=$dn ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$cn=trim($GPARR["uid"]);
			if(trim($cn)==null){continue;}
			$cn=str_replace(" ", "%20", $cn);
			$f[]="\tuser\t\"$cn\"";
		}
	
	
	return $f;
}	
	
	
	private function members_from_activedirectory($dn){
		if(trim($dn)==null){return "";}
		if(preg_match("#AD:(.*?):(.+)#", $dn,$re)){
				$dnEnc=$re[2];
				$LDAPID=$re[1];
				$dn=base64_decode($dnEnc);
				if($GLOBALS["VERBOSE"]){writelogs("LDAPID:$LDAPID DN=`$dn`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				$ad=new ActiveDirectory($LDAPID);
				$USERS=$ad->search_users_from_group($dn,0);
		}else{
			squid_admin_mysql(1, "Failed to lookup $dn", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
			return "";
		}
		
		if($GLOBALS["VERBOSE"]){writelogs("Array(USERS) = ".count($USERS)." items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		
		if(count($USERS)==0){
			squid_admin_mysql(1, "Failed to lookup $dn no such user", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
			return "";
		}
		foreach ($USERS as $dn=>$Props){
			$type=$Props["TYPE"];
			if($GLOBALS["VERBOSE"]){writelogs("DN=$dn TYPE=`$type`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			
			
			
			if($type=="group"){
				if($ad->LDAP_RECURSIVE==1){
					writelogs("Group -> ParseLDAPSubUsersGroups($LDAPID,$dn)",__FUNCTION__,__FILE__,__LINE__);
					$newrow=$this->ParseLDAPSubUsersGroups($LDAPID,$dn);
                    if(!is_array($newrow)){
                        $newrow=array();
                    }
					if(count($newrow)>0){
                        foreach ($newrow as $a=>$b){$f[]=$b;}
					}
				}
				continue;
			}			
			
			if(trim($Props["uid"]==null)){continue;}
			$Props["uid"]=trim(strtolower($Props["uid"]));
			$Props["uid"]=str_replace(" ", "%20", $Props["uid"]);
			$f[]="\tuser\t\"{$Props["uid"]}\"";
			
		}
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard Active Directory group $dn return ". count($f) ." items\n";}
		return @implode("\n", $f)."\n";
	}	
	
	
	private function ActiveDirectoryToName($groupname){
		$groupname=trim($groupname);
		$groupname=strtolower($groupname);
		$groupname=str_replace(" ", "_", $groupname);
		$userinfo = @posix_getgrnam($groupname);
		if(!isset($userinfo["gid"])){return null;}
		if(!is_numeric($userinfo["gid"])){return null;}
		if($userinfo["gid"]<1){return null;}
		return $groupname;
	}
	
	
	public function isDynamic($ruleid):bool{
		$sql="SELECT webfilter_group.* FROM webfilter_group,webfilter_assoc_groups
		WHERE webfilter_assoc_groups.group_id=webfilter_group.ID
		AND webfilter_assoc_groups.webfilter_id=$ruleid
		AND webfilter_group.enabled=1";		
		$c=0;
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$results=$q->QUERY_SQL($sql);
		foreach($results as $index=>$ligne){
			if($ligne["localldap"]==0){
				$c++;
			}
			
			if($ligne["localldap"]==2){
				$c++;
			}

			if($ligne["localldap"]==3){
				$c++;
			}
			if($ligne["localldap"]==4){
				$c++;
			}			
		}
		
		if($c>0){return true;}

        return false;
	}
	
	
	public function build_membersrule($ruleid):string{
        $array=array();
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		if($ruleid>0){
			$sql="SELECT AllSystems FROM webfilter_rules WHERE ID=$ruleid";
			$ligne=$q->mysqli_fetch_array($sql);
			if($ligne["AllSystems"]==1){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." $ruleid] is an all systems rule\n";}
				return "\tip\t0.0.0.0 - 255.255.255.255";
			}
		}
		
		
		
		$sql="SELECT webfilter_group.* FROM webfilter_group,webfilter_assoc_groups
		 WHERE webfilter_assoc_groups.group_id=webfilter_group.ID
		 AND webfilter_assoc_groups.webfilter_id=$ruleid
		 AND webfilter_group.enabled=1";
		
		
		$results=$q->QUERY_SQL($sql);
		$arrayGROUPS=array();
        echo "Starting......: ".date("H:i:s")." [SOURCE]: ufdbGuard rule $ruleid as ".count($results)." groups\n";
		if(!isset($GLOBALS["PHP5_BIN"])){$unix=new unix();$GLOBALS["PHP5_BIN"]=$unix->LOCATE_PHP5_BIN();}
		
		$isDynamic=$this->isDynamic($ruleid);
		if($isDynamic){
            echo "Starting......: ".date("H:i:s")." [SOURCE]: ufdbGuard rule $ruleid stores dynamic groups\n";
		}
		
		foreach($results as $index=>$ligne){
				$groupname=$ligne["groupname"];
                echo "Starting......: ".date("H:i:s")." [SOURCE]: ufdbGuard Type:{$ligne["localldap"]}: $groupname \n";

				if($ligne["localldap"]==0){
					if($ligne["dn"]<>null){
						$squid=new squidbee();
						$arrayGROUPS["EXTLDAP"][]=array("DN"=>$ligne["dn"],"CONF"=>$squid->EXTERNAL_LDAP_AUTH_PARAMS);
						continue;
					}
					
					$arrayGROUPS["LDAP"][]=$ligne["gpid"];
					continue;
				}
			
			if($ligne["localldap"]==2){
				if(preg_match("#AD:[0-9]+:(.+)#", $ligne["dn"],$re)){
					if($GLOBALS["VERBOSE"]){writelogs("$ruleid] Type:{$ligne["localldap"]}: $groupname ACTIVE DIRECTORY={$ligne["localldap"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
					$arrayGROUPS["AD"][]=$re[1];
					continue;
				}
			

				if($GLOBALS["VERBOSE"]){writelogs("$ruleid] Type:{$ligne["localldap"]}:$groupname Is an ACTIVE DIRECTORY group -> `{$ligne["dn"]}` ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				$arrayGROUPS["AD"][]=$ligne["settings"];
				continue;
			}
			
			if($ligne["localldap"]==4){
				$arrayGROUPS["AD"][]="ADDYN:$groupname:{$ligne["settings"]}";
				continue;
			}

            if($ligne["localldap"]==5){
                $arrayGROUPS["AD"][]="OU:$groupname";
                continue;
            }

            if($ligne["localldap"]==3){
				if(preg_match("#ExtLDAP:(.+?):(.+)#", $groupname,$re)){
					if($GLOBALS["VERBOSE"]){writelogs("$ruleid] Type:{$ligne["localldap"]}: $groupname REMOTE LDAP SERVER={$ligne["localldap"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
					$arrayGROUPS["EXT-LDAP"][]=base64_decode($re[2]);
					continue;
				}
				continue;
			}			
		
			if($GLOBALS["VERBOSE"]){writelogs("$ruleid] Type:{$ligne["localldap"]}: $groupname Is an ACTIVE DIRECTORY group -> `{$ligne["dn"]}` ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			
			$TCPClass=new IP();
		
			if(!$isDynamic){
				$sql="SELECT * FROM webfilter_members WHERE enabled=1 AND groupid={$ligne["ID"]}";
				$results2=$q->QUERY_SQL($sql);
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard $groupname({$ligne["ID"]}) webfilter_members = ". count($results2)." items\n";}
				foreach ($results2 as $ligne2){
					$ligne2["pattern"]=trim($ligne2["pattern"]);
					if(preg_match("#^(.+?)\##",$ligne2["pattern"],$re)){$ligne2["pattern"]=trim($re[1]);}
					if($ligne2["pattern"]==null){continue;}
					if($ligne2["pattern"]=="0.0.0.0"){continue;}
					if($TCPClass->isIPAddressOrRange($ligne2["pattern"])){$array[]="\tip\t{$ligne2["pattern"]}";continue;}
					$array[]="\tuser\t\"{$ligne2["pattern"]}\"";
								
				}
			}
		
		
		}
		
		if($GLOBALS["VERBOSE"]){echo "******************************************************\n";print_r($arrayGROUPS);}
		
		
		if($isDynamic){
			if(count($arrayGROUPS)>0){
			@file_put_contents("/etc/squid3/ufdb.groups.$ruleid.db", serialize($arrayGROUPS));
			}
			$array[]="\texecuserlist\t\"/usr/share/artica-postfix/external_acl_squid_ldap.php --db $ruleid\"";
		}
		if(count($array)>0){return @implode("\n", $array);}
		return "";
	}
	
	
	private function build_sources(){
		echo "Starting......: ".date("H:i:s")." ufdbGuard build SOURCES ....\n";
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $array=array();
		$sql="SELECT ID,groupname FROM webfilter_rules WHERE enabled=1 ORDER BY zOrder";
		$results=$q->QUERY_SQL($sql);
		foreach($results as $index=>$ligne){
			$RULE_ID=$ligne["ID"];
			$rulename=$ligne["groupname"];

            echo "Starting......: ".date("H:i:s")." ufdbGuard Rule $rulename ($RULE_ID)\n";

			$members=$this->build_membersrule($RULE_ID);
			if($members==null){
				$array[]="# Group $rulename id: $RULE_ID $members == null";
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard skip `$rulename` (no members)\n";}
				continue;
			}
			
			
			$rulename=str_replace(" ", "-", $rulename);
			$q10=new mysql_squid_builder(true);
			$rulename=$q10->StripBadChars_hostname($rulename);
			$array[]="# {$ligne["groupname"]} ID:$RULE_ID";
			$array[]="source \"$rulename\" {";
			$array[]=$members;
			$array[]="}";
			$array[]="";
			$GLOBALS["SRC"][$ligne["ID"]]=$rulename;
			
		}
	    return @implode("\n", $array);
		
    }
		
	public function build_categories_uris($category,$destinationfile){
		$unix=new unix();
		
		if($category=="malware"){
			if($this->NoMalwareUris==1){
				echo "Starting......: ".date("H:i:s")." ".__LINE__."[URI] $category: is denied\n";
				$GLOBALS["build_categories_uris"][$category]=null;
				return $GLOBALS["build_categories_uris"][$category];
			}
		}
		
		if(isset($GLOBALS["build_categories_uris"][$category])){return $GLOBALS["build_categories_uris"][$category];}
		$urls_table="categoryuris_$category";
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		if(!$q->TABLE_EXISTS($urls_table)){
			echo "Starting......: ".date("H:i:s")." ".__LINE__."[URI] $category: `$urls_table` no such table...\n";
			$GLOBALS["build_categories_uris"][$category]=null;
			return $GLOBALS["build_categories_uris"][$category];
		}
		
		if($q->COUNT_ROWS($urls_table)==0){
			echo "Starting......: ".date("H:i:s")." ".__LINE__."[URI] $category: `$urls_table` now row...\n";
			$GLOBALS["build_categories_uris"][$category]=null;
			return $GLOBALS["build_categories_uris"][$category];
		}
		
		$tmpfile=$unix->FILE_TEMP();
		$chmod=$unix->find_program("chmod");
		shell_exec("$chmod 0777 ".dirname($tmpfile));
		@unlink($tmpfile);
		$sql="SELECT pattern FROM $urls_table WHERE enabled=1 INTO OUTFILE '$tmpfile' LINES TERMINATED BY '\n';";
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$q->QUERY_SQL($sql);
		
		if(!$q->ok){
			@unlink("$tmpfile");
			echo "Starting......: ".date("H:i:s")." ".__LINE__."[URI] $category: `$urls_table` $q->mysql_error\n";
			$GLOBALS["build_categories_uris"][$category]=null;
			return null;
		}
		
		if(!is_dir(dirname($destinationfile))){@mkdir(dirname($destinationfile),0755,true);}
		@copy("$tmpfile", $destinationfile);
		@unlink("$tmpfile");
		echo "Starting......: ".date("H:i:s")." ".__LINE__."[URI] $category: `$destinationfile` OK...\n";
		$GLOBALS["build_categories_uris"][$category]=$destinationfile;
		return $destinationfile;
	}

	
	private function parse_redirect_uri($url,$pattern=null):string{
		$query=null;
		$array=parse_url($url);
		$port=null;
        if(!isset($array["host"])){
            $array["host"]=php_uname('n');
        }
		if(!isset($array["scheme"])){$array["scheme"]="http";}
		if(!isset($array["path"])){$array["path"]="/";}
		if(isset($array["port"])){$port=":{$array["port"]}";}
		
		if(isset($array["query"])){
			if($array["query"]==$pattern){$pattern=null;}
		}
		
		if($pattern<>null){
			if(isset($array["query"])){$query="?{$array["query"]}&$pattern";}
			if(!isset($array["query"])){$query="?$pattern";}
		}else{
			if(isset($array["query"])){$query="?{$array["query"]}";}
		}
		
		return "{$array["scheme"]}://{$array["host"]}{$port}{$array["path"]}$query";
	}

    private function BlacklistedCategories():array{
        $BlacklistedCatz[229] = true;
        $BlacklistedCatz[230] = true;
        $BlacklistedCatz[231] = true;
        $BlacklistedCatz[232] = true;
        $BlacklistedCatz[233] = true;
        $BlacklistedCatz[234] = true;
        $BlacklistedCatz[236] = true;
        $BlacklistedCatz[237] = true;
        $BlacklistedCatz[238] = true;
        return $BlacklistedCatz;
    }
		
	private function build_categories_paragraph($category):string{
		$array=array();
		if(!$GLOBALS["AS_ROOT"]){
		    $this->CATEGORY_EVENTS[]="Not root, aborting!";
		    return "";}
        if(!isset($GLOBALS["CATEGORIES_BUILDED"])){$GLOBALS["CATEGORIES_BUILDED"]=array();}
		echo "Starting......: ".date("H:i:s")." Building `$category` (L.".__LINE__.")\n";
		$category_id=intval($category);
		$postgress=new postgres_sql();
		$ligne=$postgress->mysqli_fetch_array("SELECT categoryname,official_category FROM personal_categories WHERE category_id='$category_id'");

		if(!$postgress->ok){
		    $this->CATEGORY_EVENTS[]="Category id $category_id (PostGresSQL error)";
        }
        if(!isset($ligne["official_category"])){
            $ligne["official_category"]=0;
        }
        if(!isset($ligne["categoryname"])){
            $ligne["categoryname"]="Unkown_$category";
        }

		$official_category=$ligne["official_category"];
		$categoryname2=trim($ligne["categoryname"]);
		$categoryname2=str_replace("{free_edition}","Free Edition",$categoryname2);
		$OrigninalCategoryName=$category;

		if(isset($GLOBALS["CATEGORIES_BUILDED"][$OrigninalCategoryName])){return "";}
		echo "Starting......: ".date("H:i:s")." ufdbGuard Checking category `$categoryname2($category_id)`\n";
		
		if($this->SquidGuardWebFollowExtensions==1){$follow_ext="&followext=yes";}
		
		echo "Starting......: ".date("H:i:s")." ufdbGuard Enabling $categoryname2($category_id)\n";
		
		if($official_category==1){
			if(!$this->CORP_LICENSE){
				return "# $categoryname2($category_id) no license...\n";
			}
		}
		
		
		
		if($this->SquidGuardWebUseExternalUri==0){
			if(!preg_match("#^http.*?\/\/#",$this->SquidGuardIPWeb)){
				$this->SquidGuardIPWeb="http://$this->SquidGuardIPWeb";
				if(!preg_match("#ufdbguard\.php#",$this->SquidGuardIPWeb)){$this->SquidGuardIPWeb=$this->SquidGuardIPWeb."/ufdbguardd.php";}
			}

			if(!preg_match("#^http.*?\/\/#",$this->SquidGuardIPWebSSL)){
				$this->SquidGuardIPWebSSL="https://$this->SquidGuardIPWebSSL";

			}			
			
		}
        if(!is_null($this->SquidGuardIPWebSSL)) {
            if (!preg_match("#\/ufdbguard\.php#", $this->SquidGuardIPWebSSL)) {
                $this->SquidGuardIPWebSSL = $this->SquidGuardIPWebSSL . "/ufdbguard.php";
            }
        }

        $RedirectUri="\tredirect $this->SquidGuardIPWeb?category=". urlencode($category)."&SquidGuardIPWeb=$this->SquidGuardIPWebEnc&clientaddr=%a&clientname=%n&clientuser=%i&clientgroup=%s&targetgroup=%t&url=%u";
		
		if(!isset($this->UfdbGuardRedirectCategories[$category])){
            $this->UfdbGuardRedirectCategories[$category]=array();
        }
		
		$RedirectArray=$this->UfdbGuardRedirectCategories[$category];
		if(!isset($RedirectArray["external_uri"])){$RedirectArray["external_uri"]=0;}
		if(!isset($RedirectArray["redirect_url"])){$RedirectArray["redirect_url"]=null;}
		if(!isset($RedirectArray["enable"])){$RedirectArray["enable"]=0;}
		if(!is_numeric($RedirectArray["external_uri"])){$RedirectArray["external_uri"]=0;}		
		
        $main_artica_path="/var/lib/ufdbartica";
		$BASEArt="$main_artica_path/$category_id";
		$categorypathArtica="$BASEArt/domains.ufdb";
			
		if($RedirectArray["external_uri"]==1){$RedirectUri="\tredirect {$RedirectArray["external_uri"]}";}
		if($RedirectArray["enable"]==0){$RedirectUri=null;}
			
		$categoryname=$category_id;

		
		$BANNARTICA=array();

		if(!is_file($categorypathArtica)){
			echo "Starting......: ".date("H:i:s")." ".__LINE__."[ART] INFO `$categorypathArtica` $categoryname($category_id) doesn't exists\n# $categoryname2($category_id) $categorypathArtica no such file...\n";
		}
		
		
// ***********************************************************************************************************************************************************		
		
		if(isset($GLOBALS["ADDED_CATEGORY_TOFILE"]["P$category_id"])){return "";}
		if(isset($BANNARTICA[$OrigninalCategoryName])){return "";}
		if(isset($GLOBALS["CATEGORIES_BUILDED"]["P$category_id"])){return "";}
		
		if(!is_file($categorypathArtica)){
			$array[]="# $categorypathArtica no such file";
			return @implode("\n", $array);
		}
			
		echo "Starting......: ".date("H:i:s")." ".__LINE__."[ART] `$categoryname2(\"P$category_id\")` exists\n";
		if(!is_file("$BASEArt/expressions")){@file_put_contents("$BASEArt/expressions", "\n");}
		
		$GLOBALS["CATEGORIES_BUILDED"]["P$category_id"]="P$category_id";
		$GLOBALS["ADDED_CATEGORY_TOFILE"]["P$category_id"]="P$category_id";
		$array[]="# $categoryname2 ID: \"P$category_id\", official:$official_category\n";
		$array[]="category \"P$category_id\" {";
		if($RedirectUri<>null){$array[]=$RedirectUri;}
		$categorypathArtica=str_replace(".ufdb", "", $categorypathArtica);
		$array[]="\tdomainlist\t\"$categorypathArtica\"";
		echo "Starting......: ".date("H:i:s")." ".__LINE__."[ART] DisableExpressionList:`$this->DisableExpressionList`\n";
			if($this->DisableExpressionList==0){
				if(is_file("$BASEArt/expressions")){
					$isize=@filesize("$BASEArt/expressions");
					echo "Starting......: ".date("H:i:s")." ".__LINE__."[ART] `$categoryname2` $BASEArt/expressions ( $isize bytes) \n";
					if($isize>10){
						$array[]="\texpressionlist \"$BASEArt/expressions\"";
					}
				}
			}
		$array[]="}\n";	
		
		echo "Starting......: ".date("H:i:s")." ufdbGuard $categoryname2 \"P$category_id\" BUILDED in list\n";
		$GLOBALS["CATEGORIES_BUILDED"]["P$category_id"]="P$category_id";
		$GLOBALS["CATEGORIES_ADDED"]["P$category_id"]="P$category_id";

		return @implode("\n", $array);
		
	}
	

	
	
		
	function build_categories():string{
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$main_path="/var/lib/squidguard";
		$main_artica_path="/var/lib/ufdbartica";
		if($GLOBALS["VERBOSE"]){echo "DEBUG:: main_path=$main_path main_artica_path=$main_artica_path\n";}
		echo "Starting......: ".date("H:i:s")." * * * Building Categories (L.".__LINE__.") * * * \n";

		$add_ufdbgard_secu=false;
		$sql="SELECT category FROM webfilter_blks GROUP BY category";
		if($GLOBALS["VERBOSE"]){echo "DEBUG:: $sql\n";}
		$results=$q->QUERY_SQL($sql);

		$this->CATEGORY_EVENTS[]="Used categories: ".count($results);
        if(!$q->ok){echo "!!!!!!!!!!!!!!! FATAL !!!! $q->mysql_error\n";$results=array();}
        $BlacklistedCatz=$this->BlacklistedCategories();
		if($GLOBALS["VERBOSE"]){echo "DEBUG::".__FUNCTION__."::webfilter_blks: `".count($results)."` items\n";}
		foreach($results as $index=>$ligne){
			$category=intval($ligne["category"]);
            if(isset($BlacklistedCatz[$category])){
                continue;
            }
			if($GLOBALS["VERBOSE"]){echo "DEBUG:: $index:$category -> build_categories_paragraph($category)\n";}
			$Builded=$this->build_categories_paragraph($category);
			if($Builded<>null){$array[]=$Builded;}
		}
		
		$sql="SELECT webfilter_blkcnt.category,webfilter_blkcnt.webfilter_blkid,webfilter_blkgp.enabled,webfilter_blkgp.ID FROM webfilter_blkcnt,webfilter_blkgp WHERE webfilter_blkgp.ID=webfilter_blkcnt.webfilter_blkid AND webfilter_blkgp.enabled=1";
		$results=$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "DEBUG::".__FUNCTION__."::webfilter_blks: `".count($results)."` items\n";}
		$ARRAY=array();
        $BlacklistedCatz=$this->BlacklistedCategories();

		foreach($results as $index=>$ligne){
            if(isset($BlacklistedCatz[$ligne["category"]])){
                continue;
            }
			$ARRAY[$ligne["category"]]=$ligne["category"];
		}
        foreach ($ARRAY as $category){
            if(isset($BlacklistedCatz[$category])){
                continue;
            }
			$Builded=$this->build_categories_paragraph($category);
			if($Builded<>null){$array[]=$Builded;}
		}		
	    $EnforceHttpsWithHostname=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnforceHttpsWithHostname"));
		$EnforceHttpsOfficialCertificate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnforceHttpsOfficialCertificate"));
		$HttpsProhibitInsecureSslv2=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HttpsProhibitInsecureSslv2"));
		$AllowUnknownProtocolOverHttps=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowUnknownProtocolOverHttps"));
		$ufdbguardConfig["enforce-https-with-hostname"]=$EnforceHttpsWithHostname;
		$ufdbguardConfig["enforce-https-official-certificate"]=$EnforceHttpsOfficialCertificate;
		$ufdbguardConfig["https-prohibit-insecure-sslv2"]=$HttpsProhibitInsecureSslv2;
		$ufdbguardConfig["allow-unknown-protocol-over-https"]=$AllowUnknownProtocolOverHttps;

        foreach ($ufdbguardConfig as $key=>$line){
			if($line==0){$ufdbguardConfig[$key]="off";}
			if($line==1){$ufdbguardConfig[$key]="on";}
			if($line==null){$ufdbguardConfig[$key]="off";}
		}
			
		if($ufdbguardConfig["enforce-https-with-hostname"]=="on"){$add_ufdbgard_secu=true;}
		if($ufdbguardConfig["enforce-https-official-certificate"]=="on"){$add_ufdbgard_secu=true;}
		if($ufdbguardConfig["https-prohibit-insecure-sslv2"]=="on"){$add_ufdbgard_secu=true;}
		if($ufdbguardConfig["allow-unknown-protocol-over-https"]=="off"){$add_ufdbgard_secu=true;}

		if($add_ufdbgard_secu){$GLOBALS["ADD_UFDBGUARD_SSL"]=true;}
			
		$array[]="\ncategory security {";
		$array[]="\tcacerts \"/var/lib/squidguard/security/cacerts\"";
		$array[]="\toption enforce-https-with-hostname {$ufdbguardConfig["enforce-https-with-hostname"]}";
		$array[]="\toption enforce-https-official-certificate {$ufdbguardConfig["enforce-https-official-certificate"]}";
		$array[]="\toption https-prohibit-insecure-sslv2 {$ufdbguardConfig["https-prohibit-insecure-sslv2"]}";
		$array[]="\toption allow-unknown-protocol-over-https {$ufdbguardConfig["allow-unknown-protocol-over-https"]}";
		$array[]="}\n";
		return @implode("\n", $array);
	
	

	}
	
	private function build_acls_uriFromRuleID($ID){
		$RedirectUriDEF="$this->SquidGuardIPWeb";
		$RedirectUriDEFSSL=$this->SquidGuardIPWebSSL;
		$redirect_pattern="?rule-id=$ID&SquidGuardIPWeb=$this->SquidGuardIPWebEnc&clientaddr=%a&clientname=%n&clientuser=%i&clientgroup=%s&targetgroup=%t&url=%u";
		
		if($ID==0){
			if($this->DansGuardianDefaultMainRule["UseExternalWebPage"]==1){
				if(preg_match("#^http.*?:#", $this->DansGuardianDefaultMainRule["ExternalWebPage"])){
					$RedirectUriDEF=$this->DansGuardianDefaultMainRule["ExternalWebPage"];
				}
				if(preg_match("#^https.*?:#", $this->DansGuardianDefaultMainRule["ExternalWebPage"])){
					$RedirectUriDEFSSL=$this->DansGuardianDefaultMainRule["ExternalWebPage"];
				}

				return array("$RedirectUriDEF","$RedirectUriDEFSSL");
					
			}
			
			
			if($this->DansGuardianDefaultMainRule["freeweb"]<>null){
				$RedirectUriDEF="http://{$this->DansGuardianDefaultMainRule["freeweb"]}$redirect_pattern";
				$RedirectUriDEFSSL="https://{$this->DansGuardianDefaultMainRule["freeweb"]}$redirect_pattern";
				return array("$RedirectUriDEF","$RedirectUriDEFSSL");
			}	

		
		}
		
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		
		
		
		$sql="SELECT freeweb,ExternalWebPage,UseExternalWebPage FROM webfilter_rules WHERE ID=$ID";
		$ligne=$q->mysqli_fetch_array($sql);
		
		
		
		echo "Starting......: ".date("H:i:s")." [ACL]: [$ID] UseExternalWebPage: {$ligne["UseExternalWebPage"]}\n";
		echo "Starting......: ".date("H:i:s")." [ACL]: [$ID] freeweb: {$ligne["freeweb"]}\n";
		
		if($ligne["UseExternalWebPage"]==0){
				echo "Starting......: ".date("H:i:s")." [ACL]: [$ID]:SquidGuardIPWeb: $this->SquidGuardIPWeb\n";
				echo "Starting......: ".date("H:i:s")." [ACL]: [$ID] $RedirectUriDEF$redirect_pattern\n";
				return array("$RedirectUriDEF$redirect_pattern","$RedirectUriDEFSSL$redirect_pattern");
		}
		
		
		if($ligne["UseExternalWebPage"]==0){
			return array($RedirectUriDEF.$redirect_pattern,$RedirectUriDEFSSL.$redirect_pattern);
		}
		if(trim($ligne["ExternalWebPage"])==null){
			return array("$RedirectUriDEF$redirect_pattern","$RedirectUriDEFSSL$redirect_pattern");
		}
		
		
		if(!preg_match("#^http.*?:#",$ligne["ExternalWebPage"])){return array($RedirectUriDEF,$RedirectUriDEFSSL);}
		return array($ligne["ExternalWebPage"],$RedirectUriDEFSSL);
	}
	
	
	function http_code_fromruleid($ruleid){
		
		if($ruleid==0){
		    if(!isset($this->DansGuardianDefaultMainRule["http_code"])){$this->DansGuardianDefaultMainRule["http_code"]=302;}
			$http_code=intval($this->DansGuardianDefaultMainRule["http_code"]);
		}else{
			$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
			$ligne=$q->mysqli_fetch_array("SELECT `http_code` FROM webfilter_rules WHERE ID=$ruleid");
			$http_code=intval($ligne["http_code"]);
		}
		
		
		
		if($http_code>0){
			$http_code="$http_code:";
		}else{
			$http_code=null;
		}
		return $http_code;
	}

    function build_redirect_page_http($redirect_pattern):string{

        $URIS=$this->broken_uri($this->ExternalUriHTTP());
        $hostname="";

        if(isset($URIS["host"])) {
            $hostname = $URIS["host"];
        }
        $query=null;
        $URIS["scheme"]="http";


        if(isset($URIS["path"])){
            if($URIS["path"]=="/ufdbguardd.php"){
                $URIS["path"]="/";
            }

            $this->UFDBURILOG[]="# HTTP: Path: ".$URIS["path"];
            if($URIS["path"]<>"/") {
                if(isset($URIS["query"])){$query="?{$URIS["query"]}";}
                $final="{$URIS["scheme"]}://$hostname/{$URIS["path"]}$query";
                $final=str_replace("//","/",$final);
                $final=str_replace("??","?",$final);
                return $final;
            }
        }

        $final="{$URIS["scheme"]}://$hostname/ufdbguardd.php?$redirect_pattern";
        return str_replace("//","/",$final);
    }
    private function broken_uri($URL):array{

        if(!preg_match("#^(http|https):\/\/#",$URL)){
            $URL="http://$URL";
        }
        if(!preg_match("#^(http|https):#",$URL)){
            $URL="http:$URL";
        }

        $URIS=parse_url($URL);
        if(!$URIS){
            return array();
        }

        if(!isset($URIS["host"])){
            if(isset($URIS["path"])){
                if(isset($URIS["query"])) {
                    $URIS = parse_url("https://" . $URIS["path"]);
                }else{
                    $URIS = parse_url("https://" . $URIS["path"].$URIS["query"]);
                }
            }
        }

        $h[]=$URIS["host"];
        if(isset($URIS["port"])){
            $h[]=":".$URIS["port"];
        }
        $hostname=@implode("",$h);
        unset($URIS["port"]);

        $URIS["host"]=$hostname;
        return $URIS;

    }

    private function ExternalUriHTTP():string{
        $MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
        $HaClusterClient=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient");
        $SquidGuardWebExternalUri=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebExternalUri"));
        $SquidGuardIPWeb=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardIPWeb"));
        $EnableHaClusterForceURI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaClusterForceURI"));
        if($HaClusterClient==0){
            $EnableHaClusterForceURI=0;
        }

        if($EnableHaClusterForceURI==0){
            if(strlen($SquidGuardIPWeb)>3){
                return str_replace("articatech.net",$MyHostname.":9025",$SquidGuardIPWeb);
            }

            if(strlen($SquidGuardWebExternalUri)>3){
                return str_replace("articatech.net",$MyHostname.":9025",$SquidGuardWebExternalUri);
            }
            return "http://$MyHostname:9025";
        }
        $HaClusterForceURI=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterForceURI");
        return $HaClusterForceURI;
    }

    private function ExternalUri():string{
        $MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
        $HaClusterClient=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient");
        $SquidGuardWebExternalUriSSL=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebExternalUriSSL"));
        $EnableHaClusterForceURI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaClusterForceURI"));
        if($HaClusterClient==0){
            $EnableHaClusterForceURI=0;
        }

        if($EnableHaClusterForceURI==0){
            if(strlen($SquidGuardWebExternalUriSSL)>3){
                return str_replace("articatech.net",$MyHostname.":9026",$SquidGuardWebExternalUriSSL);
            }
            return "https://$MyHostname:9026";
        }

        $HaClusterForceURI=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterForceURI");
        return $HaClusterForceURI;
    }

    private function ExternalUriSSLDefault():string{
        $Default_url=$this->ExternalUri();
        $this->UFDBURILOG[]="# SSL: Default_url: ".$Default_url;
        $URIS=parse_url($Default_url);
        $h[]=$URIS["host"];
        if(isset($URIS["port"])){
            $h[]=":".$URIS["port"];
        }
        return @implode("", $h);


    }

    function build_redirect_page_https($redirect_pattern){
        $this->UFDBURILOG=array();
        $SquidGuardWebExternalUriDecrypted=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebExternalUriDecrypted"));

        $SquidGuardWebExternalUriSSL=$this->ExternalUriSSLDefault();
        $this->UFDBURILOG[]="# SSL: SquidGuardWebExternalUriSSL: ".$SquidGuardWebExternalUriSSL;
        $URIS=$this->broken_uri($SquidGuardWebExternalUriSSL);

        $hostname=$URIS["host"];
        $this->UFDBURILOG[]="# SSL: Host: ".$hostname;
        $this->UFDBURILOG[]="# SSL: Decrypt: ".$SquidGuardWebExternalUriDecrypted;

        if($SquidGuardWebExternalUriDecrypted==0){
            return $hostname;
        }

        foreach ($URIS as $key=>$line){
            $this->UFDBURILOG[]="# SSL $key: $line";
        }

        $URIS["scheme"]="https";


        if(isset($URIS["path"])){
            if($URIS["path"]=="/ufdbguardd.php"){
                $URIS["path"]="/";
            }

            $this->UFDBURILOG[]="# SSL: Path: ".$URIS["path"];
            if($URIS["path"]<>"/") {
                if(isset($URIS["query"])){$query="?{$URIS["query"]}";}
                $final="{$URIS["scheme"]}://$hostname/{$URIS["path"]}$query";
                $final=str_replace("//","/",$final);
                $final=str_replace("??","?",$final);
                return $final;
            }
        }
        $final="{$URIS["scheme"]}://$hostname/ufdbguardd.php?$redirect_pattern";
        $final=str_replace("//","/",$final);
        return $final;
    }
	
	function build_acls(){
		$allows=array();
		$banns=array();
        if(!is_array($this->DansGuardianDefaultMainRule)){
            $this->DansGuardianDefaultMainRule=array();
        }
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$time=null;

		if($this->SquidGuardWebFollowExtensions==1){$follow_ext="&followext=yes";}
		$redirect_pattern="rule-id=0&SquidGuardIPWeb=$this->SquidGuardIPWebEnc&clientaddr=%a&clientname=%n&clientuser=%i&clientgroup=%s&targetgroup=%t&url=%u";
		$rewrite_rules=null;
		$bannedExtDomains=null;
		$bannedfiles=null;
		$bannedRegexP=null;	

		if(!isset($this->DansGuardianDefaultMainRule["endofrule"])){$this->DansGuardianDefaultMainRule["endofrule"]="any";}
		if(!isset($this->DansGuardianDefaultMainRule["UseExternalWebPage"])){$this->DansGuardianDefaultMainRule["UseExternalWebPage"]=0;}
		if(!isset($this->DansGuardianDefaultMainRule["ExternalWebPage"])){$this->DansGuardianDefaultMainRule["ExternalWebPage"]=null;$this->DansGuardianDefaultMainRule["UseExternalWebPage"]=0;}
		if($this->DansGuardianDefaultMainRule["ExternalWebPage"]==null){$this->DansGuardianDefaultMainRule["UseExternalWebPage"]=0;}
		if(!isset($this->DansGuardianDefaultMainRule["UseSecurity"])){$this->DansGuardianDefaultMainRule["UseSecurity"]=0;}
		if(!isset($this->DansGuardianDefaultMainRule["freeweb"])){$this->DansGuardianDefaultMainRule["freeweb"]=null;}

		
		
		$http_code=$this->http_code_fromruleid(0);
		
		
		$endofrule=$this->DansGuardianDefaultMainRule["endofrule"];
		if($endofrule<>null){$endofrule=" $endofrule";}

        $this->SquidGuardIPWeb=$this->build_redirect_page_http($redirect_pattern);
		$RedirectUriDEF=$this->build_redirect_page_http($redirect_pattern);
		$RedirectUriDEFSSL=$this->build_redirect_page_https($redirect_pattern);

		
		$this->SquidGuardIPWebEnc=base64_encode($this->SquidGuardIPWeb);
		echo "Starting......: ".date("H:i:s")." ufdbGuard Redirect to encoded `$this->SquidGuardIPWebEnc`\n";
		
		echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard redirect banned to \"$RedirectUriDEF\"\n";
		echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard redirect banned to SSL \"$RedirectUriDEFSSL\"\n";
		echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard redirect code: $http_code\n";
		
		$array[]="acl{";
		$sql="SELECT category,modeblk FROM webfilter_blks WHERE webfilter_id=0";
		$results=$q->QUERY_SQL($sql);
		echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard build ACLS for default rule ". count($results)." items...\n";
		
		foreach($results as $index=>$ligne){
			$bannedRegexP=null;
			$category=intval($ligne["category"]);
			if($category==0){continue;}
			if(isset($GLOBALS["CATEGORIES_SKIPPED"]["P$category"])){echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS:$index:$category skipped\n";continue;}
			if(!isset($GLOBALS["CATEGORIES_ADDED"]["P$category"])){echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS:$category not added\n";continue;}
			
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard build DEFAULT: ACLS:$category banned={$ligne["modeblk"]}\n";}
			
			if($ligne["modeblk"]==0){
				if(isset($GLOBALS["CATEGORIES_BUILDED"]["P$category"])){$banns["P$category"]="!{$GLOBALS["CATEGORIES_BUILDED"]["P$category"]}";}
			}
			
			if($ligne["modeblk"]==1){
				if(isset($GLOBALS["CATEGORIES_BUILDED"]["P$category"])){$allows["P$category"]="{$GLOBALS["CATEGORIES_BUILDED"]["P$category"]}";}
			}			
		}
		
		
		if($GLOBALS["VERBOSE"]){echo "\n\n ****************** \n\n Checks Groups for 0 \n\n ****************** \n\n";}
			
		$sql="SELECT webfilter_blkid,blacklist FROM webfilter_blklnk WHERE webfilter_ruleid=0";
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:0 FATAL !!! $q->mysql_error\n";}
		foreach ($results as $index=>$blks){
			$groupid=$blks["webfilter_blkid"];
			$blacklist=$blks["blacklist"];
			$ligne2=$q->mysqli_fetch_array("SELECT enabled FROM webfilter_blkgp WHERE ID=$groupid");
			if($ligne2["enabled"]==0){continue;}
		
			$sql="SELECT `category` FROM webfilter_blkcnt WHERE `webfilter_blkid`='$groupid'";
			$resultsCatz=$q->QUERY_SQL($sql);
			if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:0 webfilter_blkid=$groupid FATAL !!! $q->mysql_error\n";continue;}
			foreach ($resultsCatz as $categoryLine){
				$category=$categoryLine["category"];
				
				if($category=="phishtank"){
					echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard build ACLS:$category Enabled:$this->EnableSquidPhishTank (L.".__LINE__.")\n";
					if($this->EnableSquidPhishTank==0){
						echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard build ACLS:$category (Skipped) (L.".__LINE__.")\n";
						$GLOBALS["CATEGORIES_SKIPPED"]["P$category"]=true;
						continue;
					}
				}
				
				if($category=="ransomwares"){
					echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard build ACLS:$category Enabled:$this->EnableRansomwares (L.".__LINE__.")\n";
					if($this->EnableRansomwares==0){
						echo "Starting......: ".date("H:i:s")." Building ransomwares paragraph - SKIPED -...\n";
						echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard build ACLS:$category (Skipped)\n";
						$GLOBALS["CATEGORIES_SKIPPED"]["P$category"]=true;
						continue;
					}
				}
				
				
				
				if($GLOBALS["VERBOSE"]){echo "DEBUG: Default rule: Category: {$categoryLine["category"]}=$category blacklist=$blacklist\n";}

				if(preg_match("#^P([0-9]+)#",$GLOBALS["CATEGORIES_BUILDED"]["P$category"],$re)){
                    $GLOBALS["CATEGORIES_BUILDED"]["P$category"]="{$re[1]}";
                }
				
				if($blacklist==0){
					if(isset($GLOBALS["CATEGORIES_BUILDED"]["P$category"])){$banns[$category]="!P{$GLOBALS["CATEGORIES_BUILDED"]["P$category"]}";}
				}
		
				if($blacklist==1){
					if(isset($GLOBALS["CATEGORIES_BUILDED"]["P$category"])){$allows[$category]="P{$GLOBALS["CATEGORIES_BUILDED"]["P$category"]}";}
				}
		
			}
				
		}
		
		if(isset($GLOBALS["TIMESRULES"][0])){
			if($GLOBALS["TIMESRULES"][0]["MATCH"]<>null){
				if($GLOBALS["TIMESRULES"][0]["MATCH"]=="none"){$GLOBALS["TIMESRULES"][0]["MATCH"]="within";}
					$time=" {$GLOBALS["TIMESRULES"][0]["MATCH"]} timeR0 ";
					echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS default rule to time timeR0...\n";
				}else{
					echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS default rule has no time...\n";
				}
			}
		
		if(!isset($this->DansGuardianDefaultMainRule["RewriteRules"])){$this->DansGuardianDefaultMainRule["RewriteRules"]=null;}
		if($this->DansGuardianDefaultMainRule["RewriteRules"]<>null) {
            $RewriteRules = $this->unserializeb64($this->DansGuardianDefaultMainRule["RewriteRules"]);
        }
		$RR=array();
		
		if($this->UpdateUtilityRedirectEnable==1){$RR[]="ReWriteKasper";}
		
		if( (isset($RewriteRules)) && (is_array($RewriteRules)))   {
		    foreach ($RewriteRules as $rewrite_rule_id=>$nomatch){
				if(!is_numeric($rewrite_rule_id)){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUG] \$rewrite_rule_id=$rewrite_rule_id is not a numeric\n";}continue;}
				if(!isset($this->AVAILABLE_REWRITE_RULES[$rewrite_rule_id])){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUG] \$rewrite_rule_id=$rewrite_rule_id is not set\n";}continue;}
				$RR[]="ReWriteRule{$rewrite_rule_id}";
			}
			
			if(count($RR)>0){$rewrite_rules="\t\trewrite ".@implode(" ", $RR);}
			
		}
		
		if(isset($this->BANNED_FILES_RULES[0])){
			$bannedfiles=" !".$this->BANNED_FILES_RULES[0]." ";
		}
		
		if(isset($this->BANNED_EXTDOMS_RULES[0])){
			$bannedExtDomains=" !".$this->BANNED_EXTDOMS_RULES[0]." ";
		}

		if(isset($this->AVAILABLE_REGEX_RULES[0])){
			$bannedRegexP=" !".$this->AVAILABLE_REGEX_RULES[0]." ";
		}

		if($this->DansGuardianDefaultMainRule["UseSecurity"]==1){
			echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS default rule Check SSL is enabled...\n";
			$banns["security"]="!security";			
		}

		
		$bannsFINAL=array();
		$allowsFINAL=array();
		if($GLOBALS["VERBOSE"]){echo "DEBUG: Default rule: BANN: ". count($banns)." items\n";}
		
		$redirect_pattern="rule-id=0&SquidGuardIPWeb=$this->SquidGuardIPWebEnc&clientaddr=%a&clientname=%n&clientuser=%i&clientgroup=%s&targetgroup=%t&url=%u";


		foreach ($banns as $category=>$line){if(preg_match("#^PP([0-9]+)#",trim($line),$re)){$line="P{$re[1]}";}$bannsFINAL[]=$line;}
        foreach ($allows as $category=>$line){if(preg_match("#^PP([0-9]+)#",trim($line),$re)){$line="P{$re[1]}";};$allowsFINAL[]=$line;}
				
		if($GLOBALS["VERBOSE"]){echo "DEBUG: Default rule: BANN: ". count($bannsFINAL)." items\n";}
		$arrayDEF[]="\t\"default\"$time{";
		if($rewrite_rules<>null){$arrayDEF[]=$rewrite_rules;}
		$arrayDEF[]="\t\tpass ".trim(@implode(" ", $allowsFINAL)." $bannedExtDomains$bannedfiles$bannedRegexP".@implode(" ", $bannsFINAL)).$endofrule;
		$arrayDEF[]="\t\tredirect $http_code".$this->parse_redirect_uri($RedirectUriDEF,$redirect_pattern);
		$arrayDEF[]="\t}";	
		
		$acls_default=@implode("\n", $arrayDEF);
		if(!isset($this->DansGuardianDefaultMainRule["defaultPosition"])){
            $this->DansGuardianDefaultMainRule["defaultPosition"]=0;
        }


		$DefaultPosition=$this->DansGuardianDefaultMainRule["defaultPosition"];
		if(!is_numeric($DefaultPosition)){$DefaultPosition=0;}
		
		if($DefaultPosition==0){
			$array[]=$acls_default;
		}
		
		echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS DefaultPosition = $DefaultPosition\n";

		if(is_array($GLOBALS["SRC"])){

            foreach ($GLOBALS["SRC"] as $RULE_ID=>$groupname){
				echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS `$groupname` ID:$RULE_ID\n";
				$time=null;
				$else=null;
				echo "Starting......: ".date("H:i:s")." ufdbGuard rule `$groupname`\n";
				
				$redirect_pattern="rule-id=$RULE_ID&SquidGuardIPWeb=$this->SquidGuardIPWebEnc&clientaddr=%a&clientname=%n&clientuser=%i&clientgroup=%s&targetgroup=%t&url=%u";
				$RedirectUriArray=$this->build_acls_uriFromRuleID($RULE_ID);
				$RedirectUri=$RedirectUriArray[0];
				$RedirectUriSSL=$RedirectUriArray[1];
				
				if(!isset($GLOBALS["TIMESRULES"][$RULE_ID]["MATCH"])){$GLOBALS["TIMESRULES"][$RULE_ID]["MATCH"]=null;}
				
				if($RedirectUri<>$this->SquidGuardIPWeb){$redirect_pattern=null;}
				if($RedirectUriSSL<>$this->SquidGuardIPWebSSL){$redirect_pattern=null;}
				
				if($GLOBALS["TIMESRULES"][$RULE_ID]["MATCH"]<>null){
						$time=" {$GLOBALS["TIMESRULES"][$RULE_ID]["MATCH"]} timeR{$RULE_ID} ";
					}else{
					if($GLOBALS["VERBOSE"]){echo " ***** ***** TIME : $groupname: $RULE_ID rule has no time\n";}
					echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS rule:`$groupname` $RULE_ID rule has no time...\n";
				}			
				
				if(isset($GLOBALS["TIMESRULES"][$RULE_ID]["NEXT"])){
					if(is_numeric($GLOBALS["TIMESRULES"][$RULE_ID]["NEXT"])){
						$compile_rewriterules_fromruleid=$this->compile_rewriterules_fromruleid($GLOBALS["TIMESRULES"][$RULE_ID]["NEXT"]);
						if($compile_rewriterules_fromruleid<>null){$compile_rewriterules_fromruleid="$compile_rewriterules_fromruleid\n";}
						$http_code=$this->http_code_fromruleid($GLOBALS["TIMESRULES"][$RULE_ID]["NEXT"]);
						$redirect_pattern_else="rule-id={$GLOBALS["TIMESRULES"][$RULE_ID]["NEXT"]}&SquidGuardIPWeb=$this->SquidGuardIPWebEnc&clientaddr=%a&clientname=%n&clientuser=%i&clientgroup=%s&targetgroup=%t&url=%u";
						$else=" else {\n$compile_rewriterules_fromruleid".
						$this->compile_categories_fromruleid($GLOBALS["TIMESRULES"][$RULE_ID]["NEXT"]).
						"\n\t\tredirect {$http_code}".
						$this->parse_redirect_uri($RedirectUri,$redirect_pattern_else)."\n\t}";
					}
				}
				
				
				$compile_categories_fromruleid=$this->compile_categories_fromruleid($RULE_ID);
				$compile_rewriterules_fromruleid=$this->compile_rewriterules_fromruleid($RULE_ID);
				$http_code=$this->http_code_fromruleid($RULE_ID);
				echo "Starting......: ".date("H:i:s")." ufdbGuard rule `$groupname` redirect code:$http_code\n";
				
				$q10=new mysql_squid_builder(true);
				$groupname=$q10->StripBadChars_hostname($groupname);
                $array[]="#\tRULEIDINLINE:$RULE_ID";
				$array[]="\t\"$groupname\"$time{";
				if($compile_rewriterules_fromruleid<>null){$array[]=$compile_rewriterules_fromruleid;}
				if($compile_categories_fromruleid<>null){$array[]=$compile_categories_fromruleid;}
				$array[]="\t\tredirect $http_code".$this->parse_redirect_uri($RedirectUri,$redirect_pattern);
				$array[]="\t}$else\n";	
			}
			
		}
		
		
		
		if($DefaultPosition==1){
			$array[]=$acls_default;
		}		
		
		$array[]="}\n\n";
		
		
		return @implode("\n", $array);
		
		
	}

	private function compile_rewriterules_fromruleid($RULE_ID){
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $RR=array();
		if($RULE_ID>0){
			$sql="SELECT RewriteRules FROM webfilter_rules WHERE ID=$RULE_ID";
			$ligne=$q->mysqli_fetch_array($sql);
			$RewriteRules=$this->unserializeb64($ligne["RewriteRules"]);
		}else{
			$RewriteRules=$this->unserializeb64($this->DansGuardianDefaultMainRule["RewriteRules"]);
		}
		$rewrite_rules=null;
		
		if($this->UpdateUtilityRedirectEnable==1){
			$rewrite_rules="\t\trewrite ReWriteKasper";
		}
		
		if(  (isset($RewriteRules)) && is_array($RewriteRules) ){
			
			if($this->UpdateUtilityRedirectEnable==1){
				$RR[]="ReWriteKasper";
			}
			foreach ($RewriteRules as $rewrite_rule_id=>$nomatch){
				if(!is_numeric($rewrite_rule_id)){continue;}
				if(isset($this->AVAILABLE_REWRITE_RULES[$rewrite_rule_id])){$RR[]="ReWriteRule{$rewrite_rule_id}";}
			}
			
			
			
			if(count($RR)>0){$rewrite_rules="\t\trewrite ".@implode(" ", $RR);}
			return $rewrite_rules;
		}
		
		
				
	}
	

	
	
	private function compile_categories_fromruleid($RULE_ID){
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$allows=array();
		$banns=array();
		if(!isset($GLOBALS["EnableGoogleSafeSearch"])){$sock=new sockets();$GLOBALS["EnableGoogleSafeSearch"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleSafeSearch"));if(!is_numeric($GLOBALS["EnableGoogleSafeSearch"])){$GLOBALS["EnableGoogleSafeSearch"]=1;}}
		
		if($RULE_ID>0){
			$sql="SELECT GoogleSafeSearch,groupmode,endofrule,UseSecurity FROM webfilter_rules WHERE ID='$RULE_ID'";
			$ligne=$q->mysqli_fetch_array($sql);
			if($ligne["groupmode"]==0){
				echo "Starting......: ".date("H:i:s")." ufdbGuard rule:$RULE_ID deny all users...\n";
				return "\t\tpass none";
			}
		}else{
			$ligne=$this->unserializeb64($this->DansGuardianDefaultMainRule);
			
		}
		$endofrule=$ligne["endofrule"];
		if($endofrule<>null){$endofrule=" $endofrule";}
		if($GLOBALS["EnableGoogleSafeSearch"]==1){$ligne["GoogleSafeSearch"]=0;}
		$MyRuleGoogleSafeSearch=$ligne["GoogleSafeSearch"];
		$UseSecurity=$ligne["UseSecurity"];
		if(!is_numeric($UseSecurity)){$UseSecurity=0;}
			
		if($GLOBALS["VERBOSE"]){echo "\n\nStarting......: ".date("H:i:s")."  ***** CHECK RULE $RULE_ID *****\n";}
		
		$sql="SELECT * FROM webfilter_blks WHERE webfilter_id=$RULE_ID";
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$results2=$q->QUERY_SQL($sql);
		
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID FATAL !!! $q->mysql_error\n";}
		foreach ($results2 as $index=>$blks ){
				$bannedExtDomains=null;
				$bannedfiles=null;
				$bannedRegexP=null;
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID category `{$blks["category"]}` (L.".__LINE__.")\n";}
				if(isset($ARRAY["{$blks["modeblk"]}$RULE_ID{$blks["category"]}"])){
					if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." {$blks["category"]} Alreay set, SKIP\n";}
					continue;}
				
				$category=intval($blks["category"]);
				
				
				
				
				if(!isset($GLOBALS["CATEGORIES_ADDED"]["P$category"])){
					if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID category `$category` ({$blks["category"]}) is skipped Not added in GLOBALS[\"CATEGORIES_ADDED\"][$category]\n";}
					continue;
				}
				
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard build RULE:$RULE_ID : ACLS:$category banned={$blks["modeblk"]}\n";}
				
				
				
				
				if($blks["modeblk"]==0){
					
					
					if(isset($GLOBALS["CATEGORIES_BUILDED"]["P$category"])){
						echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID Artica $category added for that rule\n";
						$banns[$category]="!{$GLOBALS["CATEGORIES_BUILDED"]["P$category"]}";}
					else{
						echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID Artica $category not set, skipped\n";
					}
				}
				if($blks["modeblk"]==1){
					
					
					if(isset($GLOBALS["CATEGORIES_BUILDED"]["P$category"])){$allows["P$category"]="{$GLOBALS["CATEGORIES_BUILDED"]["P$category"]}";}
				}				
				
				
				$ARRAY["{$blks["modeblk"]}$RULE_ID{$blks["category"]}"]=true;
			}
			
		if($GLOBALS["VERBOSE"]){echo "\n\n ****************** \n\n Checks Groups for $RULE_ID \n\n ****************** \n\n";}
			
		$sql="SELECT webfilter_blkid,blacklist FROM webfilter_blklnk WHERE webfilter_ruleid=$RULE_ID";
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID FATAL !!! $q->mysql_error\n";}
		
		foreach ($results as $index=>$blks){
			$groupid=$blks["webfilter_blkid"];
			$blacklist=$blks["blacklist"];
			$ligne2=$q->mysqli_fetch_array("SELECT groupname,enabled FROM webfilter_blkgp WHERE ID=$groupid AND `enabled`=1");
			if($GLOBALS["VERBOSE"]){echo "DEBUG: {$ligne2["groupname"]}: Group id:$groupid: blacklist=$blacklist\n";}
			
			
				
			$sql="SELECT `category` FROM webfilter_blkcnt WHERE `webfilter_blkid`='$groupid'";
			$resultsCatz=$q->QUERY_SQL($sql);
			if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID webfilter_blkid=$groupid FATAL !!! $q->mysql_error\n";continue;}
			foreach ($resultsCatz as $categoryLine){
				$category=$categoryLine["category"];
				
				if($category=="phishtank"){
					echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID,webfilter_blkid=$groupid special category phishtank, Enabled=$this->EnableSquidPhishTank\n";
					if($this->EnableSquidPhishTank==0){
						echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID,webfilter_blkid=$groupid special category phishtank SKIP\n";
						continue;
					}
						
				}
				
				if($category=="ransomwares"){
					echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID special category $category Enabled:$this->EnableRansomwares (L.".__LINE__.")\n";
					if($this->EnableRansomwares==0){
						echo "Starting......: ".date("H:i:s")." Building ransomwares paragraph - SKIPPED -...\n";
						echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID special category ransomwares (Skipped)\n";
						continue;
					}
				}
				
				
				
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID {$categoryLine["category"]} $category\n";}
				if($blacklist==0){
					if(isset($GLOBALS["CATEGORIES_BUILDED"]["P$category"])){$banns["P$category"]="!{$GLOBALS["CATEGORIES_BUILDED"]["P$category"]}";}
				}
				
				if($blacklist==1){
					if(isset($GLOBALS["CATEGORIES_BUILDED"]["P$category"])){$allows["P$category"]="{$GLOBALS["CATEGORIES_BUILDED"]["P$category"]}";}
				}				
				
			}
			
		}
		
		//compileFinal
		
		$bannsFINAL=array();
		$allowsFINAL=array();
		foreach ($banns as $category=>$line){
		    if(!preg_match("#([0-9]+)#",$line,$re)){continue;}
            $line="!P{$re[1]}";
			$bannsFINAL[]=$line;
		}
        foreach ($allows as $category=>$line){
            if(!preg_match("#([0-9]+)#",$line,$re)){continue;}
            $line="P{$re[1]}";
			$allowsFINAL[]=$line;
		}

        $bannedExtDomains="";
        $bannedfiles="";
        $bannedRegexP="";
		
		if(isset($this->BANNED_FILES_RULES[$RULE_ID])){
			$bannedfiles=" !".$this->BANNED_FILES_RULES[$RULE_ID]." ";
		}		

		if(isset($this->BANNED_EXTDOMS_RULES[$RULE_ID])){
			$bannedExtDomains=" !".$this->BANNED_EXTDOMS_RULES[$RULE_ID]." ";
		}	

			if(isset($this->AVAILABLE_REGEX_RULES[$RULE_ID])){
			$bannedRegexP=" !".$this->AVAILABLE_REGEX_RULES[$RULE_ID]." ";
		}		

		
		return "\t\tpass ".trim(@implode(" ", $allowsFINAL)."$bannedExtDomains$bannedfiles$bannedRegexP ".@implode(" ", $bannsFINAL)).$endofrule;
		
	}
	
	
	private function UFDBGUARD_COMPILE_CATEGORY_PROGRESS($text,$pourc){
		if(!function_exists("UFDBGUARD_COMPILE_CATEGORY_PROGRESS")){return;}
		UFDBGUARD_COMPILE_CATEGORY_PROGRESS($text,$pourc);
	}
	
	function compile_category($category){
		$unix=new unix();
		squid_admin_mysql(2, "Compiling category $category",__FUNCTION__,__FILE__,__LINE__,"$category");
		$ufdbGenTable=$unix->find_program("ufdbGenTable");
		if(strlen($ufdbGenTable)<strlen("ufdbGenTable")){return;}
		$chmod=$unix->find_program("chmod");
		$rm=$unix->find_program("rm");
		$t1=time();
        $qProxy=new mysql_squid_builder();
		$ufdbguardReloadTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardReloadTTL"));
		if($ufdbguardReloadTTL<1){$ufdbguardReloadTTL=10;}
		
		if(preg_match("#category_.+?#", $category)){$category=$qProxy->tablename_tocat($category);}

		echo "Starting......: ".date("H:i:s")." ufdbGuard table  = $category\n";
		
		if($category=="phishtank"){return;}
		if($category=="ransomwares"){return;}

		
		
		if($category=="checked"){
			$main_path="/var/lib/squidguard/checked";
			$this->UfdbGenTable($main_path,$category);
			$compiledbs_took=$unix->distanceOfTimeInWords($t1,time(),true);
			squid_admin_mysql(1, "$category compilation done\nUFDB Compilation took:$compiledbs_took",__FUNCTION__,__FILE__,__LINE__,"compile");
			return;
		}

		$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{compile2} $category...",10);
		
		if($this->EnableRemoteStatisticsAppliance==1){return;}
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$categorySource=$category;
		$category=str_replace("/", "_", $category);
		$main_path="/var/lib/squidguard/$category";
		
		echo "Starting......: ".date("H:i:s")." ufdbGuard Path  = $main_path\n";
		
		$tablename="category_".$qProxy->category_transform_name($category);
		$tablename=str_replace("category_category", "category", $tablename);
		$tablename_uris="categoryuris_".$qProxy->category_transform_name($categorySource);
		$tablename_uris=str_replace("categoryuris_categoryuris", "categoryuris_", $tablename_uris);
		
		echo "Starting......: ".date("H:i:s")." ufdbGuard Table Domains = $tablename\n";
		echo "Starting......: ".date("H:i:s")." ufdbGuard Table URLs  = $tablename_uris\n";
		
		
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard check Domains table `$tablename`\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard check Uris table `$tablename_uris`\n";}
		squid_admin_mysql(2, "Compiling $categorySource table `$tablename`",__FUNCTION__,__FILE__,__LINE__,"$categorySource");
        $META_URIS=0;
		
		
		if($q->TABLE_EXISTS($tablename_uris)){
			$sql="SELECT pattern FROM $tablename_uris WHERE enabled=1 ORDER BY pattern";
			$results = $q->QUERY_SQL($sql);
			UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{extracting} $tablename_uris...",10);
			while ($ligne = mysqli_fetch_assoc($results)) {$URIS[]=$ligne["pattern"];}
			
			if(count($URIS)>0){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard $categorySource ". count($URIS)." URL(s) Items\n";}
				@file_put_contents("$main_path/urls", @implode("\n", $URIS));
				unset($URIS);
			}
			
		}


		
		
		
		$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{extracting} $tablename...",20);
		if(!$q->TABLE_EXISTS($tablename)){

					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard $categorySource no such table \"$tablename\"\n";}
					$fake[]=md5(time()+1).".biz";
					$fake[]=md5(time()+2).".biz";
					$fake[]=md5(time()+3).".biz";
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard create directory $main_path\n";}
					@mkdir($main_path,0755,true);
					@file_put_contents("$main_path/domains", @implode("\n", $fake));
					if(!is_file("$main_path/urls")){shell_exec("/bin/touch $main_path/urls");}
					$this->UfdbGenTable($main_path,$category);
					return;

		}
		
		$tmpdir=$unix->TEMP_DIR();
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$badDomains[""]=true;
		$badDomains["com"]=true;
		$badDomains["fr"]=true;
		$badDomains["nl"]=true;
		$badDomains["org"]=true;
		$badDomains["co"]=true;
		$badDomains["cz"]=true;
		$badDomains["de"]=true;
		$badDomains["net"]=true;
		$badDomains["us"]=true;
		$badDomains["biz"]=true;
		$badDomains["info"]=true;
		$badDomains["ee"]=true;
		
		if($q->TABLE_EXISTS($tablename)){
		$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{cleaning} $tablename...",30);
		    foreach ($badDomains as $extensions=>$none){
				$q->QUERY_SQL("DELETE FROM $tablename WHERE pattern='$extensions'");
				if(!$q->ok){echo "$q->mysql_error !!! \n";}
			}
		}
		
		
		
		@mkdir("$tmpdir/$tablename",0777,true);
		
		if(!is_dir("$tmpdir/$tablename")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard $tmpdir/$tablename no such directory\n";}
			$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{extracting} {failed} $tmpdir/$tablename no such directory...",110);
			
		}
		
		$chmod=$unix->find_program("chmod");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard apply permissions on $tmpdir/$tablename\n";}
		shell_exec("$chmod 0777 $tmpdir");
		shell_exec("$chmod 0777 $tmpdir/$tablename");		
		
		if($q->TABLE_EXISTS($tablename)){
			$OUTFILE=array();
			$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{extracting} $tablename...",30);
			$sql="SELECT pattern FROM $tablename WHERE enabled=1 ORDER BY pattern";
			$results = $q->QUERY_SQL($sql);
			$mysqlquerry_took=$unix->distanceOfTimeInWords($t1,time(),true);
			if(!$q->ok){
				if(preg_match("#writing file '(.*?)'#",$q->mysql_error,$re)){$df=$unix->find_program("df");exec("$df -h ".dirname($re[1]),$dfh);}
				squid_admin_mysql(1,"Compilation category, MySQL error",
				"`$q->mysql_error` aborting\n".@implode("\n",$dfh),__FILE__,__LINE__,"$categorySource");
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard Failed to compile $categorySource $q->mysql_error\n";}
				echo $q->mysql_error."\n";
				@shell_exec("$rm -rf $tmpdir/$tablename");
				$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{extracting} $tablename {failed}...",110);
				return ;
			}
			if(is_file("$tmpdir/$tablename/domains")){@unlink("$tmpdir/$tablename/domains");}
			$f = @fopen("$tmpdir/$tablename/domains", 'a');
			while ($ligne = mysqli_fetch_assoc($results)) {@fwrite($f, $ligne["pattern"]."\n");}
			
			@fclose($f);
			$writetodisk_took=$unix->distanceOfTimeInWords($t1,time(),true);
			
		}

		if(!is_file("$tmpdir/$tablename/domains")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard $tmpdir/$tablename/domains no such file\n";}
			$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{extracting} $tablename {failed}...",110);
		}
		
		$size=@filesize("$tmpdir/$tablename/domains");
		$items=$unix->COUNT_LINES_OF_FILE("$tmpdir/$tablename/domains");
		if($items<10){
			$f = @fopen("$tmpdir/$tablename/domains", 'a');
			for($i=0;$i<10;$i++){
				@fwrite($f, md5(microtime()).".com\n");
			}
			@fclose($f);
		}
		
		
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard Creating $main_path\n";}
		@mkdir($main_path,0755,true);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard installing $tmpdir/$tablename/domains ". ($size/1024)."KB\n";}
		copy("$tmpdir/$tablename/domains", "$main_path/domains");
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard remove $tmpdir/$tablename\n";}
		@shell_exec("$rm -rf $tmpdir/$tablename");
		
		if(!is_file("$main_path/urls")){shell_exec("/bin/touch $main_path/urls");}
		$t1=time();
		if(preg_match("#category_#", $category)){$category=$q->tablename_tocat($category);}
		
		$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{installing} $category...",50);
		$this->UfdbGenTable($main_path,$category);
		$compiledbs_took=$unix->distanceOfTimeInWords($t1,time(),true);
		
		$t=time();
		$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{installing} $category took:$mysqlquerry_took...",80);
		$t2=time();
		$tt=$t2-$t1;
		if($tt>2){squid_admin_mysql(2,"Category: $category (Compilation done SQL took:$mysqlquerry_took)","Write to disk took:$writetodisk_took\nUFDB Compilation took:$compiledbs_took",__FILE__,__LINE__,"global-compile");}
		$php5=$unix->LOCATE_PHP5_BIN();
		$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("scanning disks..",90);
		$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.squidguard.php --disks");
		
		$TimeReload=$unix->file_time_min("/etc/artica-postfix/pids/UfdbGuardReload.time");
		$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("Last reload since {$TimeReload}Mn",92);
		
		if($TimeReload>$ufdbguardReloadTTL){
			if($GLOBALS["OUTPUT"]){
				if(!isset($GLOBALS["UFDB_RELOADED_TASK"])){
					$pid=$this->ufdbguard_pid();
					if($unix->process_exists($pid)){
						$kill=$unix->find_program("kill");
						$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("Reloading service..",93);
						squid_admin_mysql(2,"Reloading Web filtering service for category $category",__FILE__,__LINE__,"global-compile");
						system("$kill -HUP $pid");
						$GLOBALS["UFDB_RELOADED_TASK"]=true;
					}
					
					$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("Reloading service..",95);
					squid_admin_mysql(2,"Reloading Categories service for category $category",__FILE__,__LINE__,"global-compile");
					system("$php5 /usr/share/artica-postfix/exec.ufdbcat.php --reload");
				}
			}
		}
		
		$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{success}",100);
	}
	
	private function ufdbguard_pid(){
		$unix=new unix();
		$pid=$unix->get_pid_from_file("/var/tmp/ufdbguardd.pid");
		if($unix->process_exists($pid)){
			$cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));
			if(!preg_match("#ufdbcatdd#", $cmdline)){return $pid;}
		}
		$ufdbguardd=$unix->find_program("ufdbguardd");
		return $unix->PIDOF($ufdbguardd);
	}
	
	public function phishing_uris(){
		if(isset($GLOBALS["PHISHING_COMPILED"])){return;}
		$GLOBALS["PHISHING_COMPILED"]=true;
	}
	
	public function malwares_uris(){
		if(isset($GLOBALS["MALWARE_COMPILED"])){return;}
		$GLOBALS["MALWARE_COMPILED"]=true;
	}	
	
	
	public function UfdbGenTableCategoryName($category){
		$category_compile=$category;
		if(strpos($category, "other")>0){$category_compile=str_replace("/", "", $category);}
		if($category=="hobby/other"){$category_compile="hobbyother";}
		if(preg_match("#.+?\/(.+)#", $category_compile,$re)){$category_compile=$re[1];}	
		return $category_compile;
	}
	
	
	public function UfdbGenTable($directory,$category){
		$categoryLog=$category;
		squid_admin_mysql(2, "Order to compile `$category`",__FUNCTION__,__FILE__,__LINE__,"$categoryLog");
		$unix=new unix();
        $ufdbGenTable=$unix->find_program("ufdbGenTable");
		if(strlen($ufdbGenTable)<strlen("ufdbGenTable")){return;}
		$category_compile=$this->UfdbGenTableCategoryName($category);
		
		
		if(strlen($category_compile)>15){
			$category_compile=str_replace("recreation_","recre_",$category_compile);
			$category_compile=str_replace("automobile_","auto_",$category_compile);
			$category_compile=str_replace("finance_","fin_",$category_compile);
			if(strlen($category_compile)>15){
				$category_compile=str_replace("_", "", $category_compile);
				if(strlen($category_compile)>15){$category_compile=substr($category_compile, strlen($category_compile)-15,15);}
			}
		}	
        if(!is_file("$directory/urls")){@file_put_contents("$directory/urls", "\n");}
		
		$u=" -u $directory/urls";
		$d=" -d $directory/domains";

		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." $ufdbGenTable $directory/domains\n";}
		$cmd="$ufdbGenTable -n -q -W -t $category_compile$d$u";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." `$cmd`\n";}
        $results=shell_exec($cmd);
        return $results;
    }
	

	private function VerifyDomainCompiledPattern($pattern){
		if(!preg_match("#^(.+?)\.(.+)#",$pattern)){return false;}
		return true;
	}	
	
	
}