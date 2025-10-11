<?php
include_once(dirname(__FILE__)."/class.mysql.inc");;
include_once(dirname(__FILE__)."/class.groups.inc");
include_once(dirname(__FILE__)."/class.compile.ufdbguard.expressions.inc");
include_once(dirname(__FILE__)."/class.ActiveDirectory.inc");
include_once(dirname(__FILE__)."/class.dnsfilter.inc");;
include_once(dirname(__FILE__)."/class.tcpip.inc");

class compile_dnsfilterd{
	var $EnableRemoteStatisticsAppliance=0;
	var $EnableWebProxyStatsAppliance=0;
	var $SquidGuardWebFollowExtensions=1;
	var $DansGuardianDefaultMainRule=array();
	var $AVAILABLE_REWRITE_RULES=array();
	var $SquidDatabasesArticaEnable=0;
	var $licensed=0;
	var $Ufdbguard_Version=null;
	var $NoMalwareUris=1;
	var $EnableSquidPhishTank=0;
	var $EnableRansomwares=0;
	private $DefaultIpRedirection;
	private $dns_neg_ttl;
	private $TMPLOG=array();
	
	var $CORP_LICENSE=false;
	private $FATAL_ERROR=false;
	var $Is31=false;
	
	
	function __construct(){
		if(!isset($GLOBALS["OUTPUT"])){$GLOBALS["OUTPUT"]=false;}
		$this->UnDuplicateRules();
		
		$GLOBALS["SRC"]=array();
		$sock=new sockets();
		$usrs=new usersMenus();
		$this->CORP_LICENSE=$sock->CORP_LICENSE();

		$MAIN_INTERFACE=null;
		if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
		if($GLOBALS["AS_ROOT"]){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		$this->SquidDatabasesUtlseEnable=1;

		if(!$usrs->CORP_LICENSE){
			$this->SquidDatabasesArticaEnable=0;
			$this->EnableSquidPhishTank=0;
			$this->EnableRansomwares=0;
		}
		
	
		
		if(!is_numeric($this->SquidDatabasesUtlseEnable)){$this->SquidDatabasesUtlseEnable=1;}
		if(!is_numeric($this->SquidDatabasesArticaEnable)){$this->SquidDatabasesArticaEnable=1;}

		
		if(is_dir("/var/lib/ftpunivtlse1fr/porn")){if(!is_link("/var/lib/ftpunivtlse1fr/adult")){system("/bin/ln -sf /var/lib/ftpunivtlse1fr/porn /var/lib/ftpunivtlse1fr/adult");} }
	
	}
	
	private function UnDuplicateRules(){
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$sql="SELECT groupname,COUNT(groupname) as tcount FROM webfilter_rules GROUP BY `groupname` HAVING tcount>1";
		$results=$q->QUERY_SQL($sql);
		
		if(!$q->ok){
			if(function_exists("debug_backtrace")){
				$trace=debug_backtrace();
				foreach ($trace as $line){$file=basename($line["file"]);$function=$line["function"];$line=$line["line"];$EXPLAINZ[]="From $file $function() Line $line";}
			}
			squid_admin_mysql(1, "Configuration failed on Web-Filtering [action=abort]", $q->mysql_error."\n",@implode("\n", $EXPLAINZ));
			$this->FATAL_ERROR=true;
			return;
		}
		
		
		if(count($results)==0){return;}
		foreach($results as $index=>$ligne){
			$groupnamesrc=$ligne["groupname"];
			$groupname=mysql_escape_string2($groupnamesrc);
			$sql="SELECT groupname,ID FROM webfilter_rules WHERE `groupname`='$groupname'";
			$results2=$q->QUERY_SQL($sql);
			
			foreach ($results2 as $index=>$ligne2){
				$NewGroupname="$groupnamesrc - {$ligne2["ID"]}";
				echo "Starting......: ".date("H:i:s")." ufdbGuard, unduplicate rule $groupnamesrc -> $NewGroupname\n";
				$q->QUERY_SQL("UPDATE `webfilter_rules` SET `groupname`='$NewGroupname' WHERE ID={$ligne2["ID"]}");
			}
			
		}
		
	}
	
	

	



	
	
	public function BuildCertificates(){
		if(isset($GLOBALS["BuildCertificatesF"])){return;}
		$GLOBALS["BuildCertificatesF"]=true;
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		echo "Starting......: ".date("H:i:s")." ufdbGuard building certificates\n";
		
		
		$sql="SELECT * FROM webfilter_certs";
		$results=$q->QUERY_SQL($sql);
		$mainArray=array();
		$f=array();
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard fatal error $q->mysql_error\n";return null;}
		echo "Starting......: ".date("H:i:s")." ufdbGuard ". count($results)." SSL certificates\n";
		foreach($results as $index=>$ligne){
			if($ligne["certname"]=="Default"){
				$f[]=base64_decode($ligne["certdata"]);
				continue;
			}
			
			$f[]="-----BEGIN CERTIFICATE-----".base64_decode($ligne["certdata"])."-----END CERTIFICATE-----";
			
		}		
		$f[]="";
		if(is_dir("/var/lib/squidguard/security/cacerts")){@rmdir("/var/lib/squidguard/security/cacerts");}
		@mkdir("/var/lib/squidguard/security",0755,true);
		@mkdir("/var/lib/squidguard/security/none",0755,true);
		@chmod("/var/lib/squidguard/security",0755);
		@chown("/var/lib/squidguard/security","squid");
		@chown("/var/lib/squidguard/security/none","squid");
		
		@mkdir("/home/ufdbcat/security",0755,true);
		@mkdir("/home/ufdbcat/security/none",0755,true);
		@chmod("/home/ufdbcat/security",0755);
		
		
		
		@file_put_contents("/var/lib/squidguard/security/cacerts", @implode("\n", $f));
		@file_put_contents("/home/ufdbcat/security/cacerts", @implode("\n", $f));
		
	}
	

	
	private function build_progress($text,$prc){
		if(function_exists("build_progress_r")){
			build_progress_r("$text {$prc}%",30);
		}
		
		if(!function_exists("build_progress")){return;}
		build_progress($text,$prc);
		
		
	}
	
	
	public function buildConfig(){
		$sock=new sockets();
		$IpClass=new IP();
		$unix=new unix();
		if($this->FATAL_ERROR==true){
			$this->build_progress("{building_rules} (failed)",110);
			return;
		}
		
		$this->build_progress("{building_rules} (sources)",15);
		$sources=$this->build_sources();
		
	
		
		$this->build_progress("{building_rules} (times)",25);
		$times=$this->build_times();
		
		$this->build_progress("{building_rules} ({categories})",30);
		$categories=$this->build_categories();
		
		
		$this->build_progress("{building_rules} ({ACLS})",50);
		$acls=$this->build_acls();
		
		$unix=new unix();
		

		$conf[]="safe-search off";
		
		$dnsfiltersocks=new dnsfiltersocks();
		$DebugFilter=intval($dnsfiltersocks->GET_INFO("DebugFilter"));
		$UfdbListenPort=intval($dnsfiltersocks->GET_INFO("UfdbListenPort"));
		$UfdbListenInterface=trim($dnsfiltersocks->GET_INFO("UfdbListenInterface"));
		$this->DefaultIpRedirection=$dnsfiltersocks->GET_INFO("DefaultIpRedirection");
		if($this->DefaultIpRedirection==null){$this->DefaultIpRedirection="127.0.0.1";}
		$this->dns_neg_ttl=intval($dnsfiltersocks->GET_INFO("dns_neg_ttl"));
		if($this->dns_neg_ttl==0){$this->dns_neg_ttl=3600;}

		
		
		$conf[]="# Engine builder on " .date('Y-m-d H:i:s');
		$conf[]="# Engine version 2.2";

		
		$conf[]="dbhome /var/lib/squidguard";
		$conf[]="logdir /var/log/dnsfilterd";
		$conf[]="pidfile /var/run/dnsfilterd/dnsfilterd.pid";
		$conf[]="upload-crash-reports off";
		$conf[]="use-ipv6-on-wan  off";
		$conf[]="url-lookup-result-during-database-reload allow";
		$conf[]="url-lookup-result-when-fatal-error allow";
		$conf[]="check-proxy-tunnels off";	
		$conf[]="upload-stats off";
		$conf[]="logblock on";
		if($DebugFilter==1){
		
		$conf[]="logall on";
		$conf[]="ufdb-debug-filter on";
		}else{
			$conf[]="logall off";
			$conf[]="ufdb-debug-filter off";			
		}
		$conf[]="ufdb-debug-external-scripts off";
		$conf[]="strip-domain-from-username off";
		$conf[]="max-logfile-size  20000000";
		$conf[]="analyse-uncategorised-urls off";
		$conf[]="ufdb-show-url-details on";
		$conf[]="ufdb-expression-optimisation off";
		$conf[]="strip-domain-from-username on";

		
		if(!is_file("/var/lib/ufdbartica/category_socialnet/domains.ufdb")){
			$php5=$unix->LOCATE_PHP5_BIN();
			$nohup=$unix->find_program("nohup");
			shell_exec("$nohup /usr/sbin/artica-phpfpm-service -categories-update >/dev/null 2>&1 &");
		}

	
		
		$conf[]="redirect-https \"http://$this->DefaultIpRedirection/$this->dns_neg_ttl\"";
		$conf[]="redirect-bumped-https \"http://$this->DefaultIpRedirection/$this->dns_neg_ttl\"";
		$conf[]="squid-version \"3.4\"";
		


		
		if($UfdbListenPort==0){$UfdbListenPort=3979;}
		if($UfdbListenInterface==null){$UfdbListenInterface="lo";}
		
		if($UfdbListenInterface<>null){
			if($UfdbListenInterface<>"lo"){
				$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
				$UfdbGuardListen=$NETWORK_ALL_INTERFACES[$UfdbListenInterface]["IPADDR"];
			}else{
				$UfdbGuardListen="127.0.0.1";
			}
		}
		
		
		$conf[]="port $UfdbListenPort";
		$conf[]="interface $UfdbGuardListen";
		
		$conf[]="";
        $conf[]="# Events....";
        $conf[]=@implode("\n",$this->TMPLOG);
 		$conf[]=$sources;
 		$conf[]=$times;
 		$conf[]=$categories;
 		$conf[]=$acls;	
 		$this->build_progress("{building_certificates}",52);
 		$this->BuildCertificates();
 		$this->build_progress("{building_rules} {done}",55);
 		return @implode("\n", $conf);
 		
		
	}
	
	
	private function build_times(){
		$GLOBALS["TIMESRULES"]=array();
		if($GLOBALS["VERBOSE"]){echo " ***** ***** TIME : build TIMES ....\n";}
		
		
		$dnsfiltersocks=new dnsfiltersocks();
		$ligne=unserialize($dnsfiltersocks->GET_INFO("DansGuardianDefaultMainRule"));
		$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
		if(is_array($TimeSpace)){
			if(is_array($TimeSpace["TIMES"])){
				if(count($TimeSpace["TIMES"])>0){
					if($TimeSpace["RuleMatchTime"]==null){$TimeSpace["RuleMatchTime"]="none";}
					if($TimeSpace["RuleAlternate"]==null){$TimeSpace["RuleAlternate"]="none";}
					if($TimeSpace["RuleMatchTime"]<>null){
						
						$R=array();
						while (list ($TIMEID, $array) = each ($TimeSpace["TIMES"]) ){
							if(count($array["DAYS"])==0){continue;}
							$dd=array();
							while (list ($day, $val) = each ($array["DAYS"])){if($val==1){$dd[]=$day;}}
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
		
		
		
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$sql="SELECT ID,TimeSpace FROM webfilter_rules WHERE enabled=1 ORDER BY zOrder";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard fatal error $q->mysql_error\n";}
		
		if(count($results)==0){
			if($GLOBALS["VERBOSE"]){echo " ***** ***** TIME : SELECT ID,TimeSpace FROM webfilter_rules WHERE enabled=1 ORDER BY zOrder = 0 rules\n";}
		}else{
			if($GLOBALS["VERBOSE"]){echo " ***** ***** TIME : SELECT ID,TimeSpace FROM webfilter_rules WHERE enabled=1 ORDER BY zOrder = ".count($results)." rules\n";}
		}
		
		foreach($results as $index=>$ligne){
			$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
			if(!is_array($TimeSpace)){continue;}
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
			while (list ($TIMEID, $array) = each ($TimeSpace["TIMES"]) ){
				if(count($array["DAYS"])==0){continue;}
				$dd=array();
				while (list ($day, $val) = each ($array["DAYS"])){if($val==1){$dd[]=$day;}}
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
	
	
	
	
	
	
	

	
	

	
	
	public function build_membersrule($ruleid){
		
		
		$array=array();
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		if($ruleid>0){
			$sql="SELECT AllSystems FROM webfilter_rules WHERE ID=$ruleid";
			$ligne=$q->mysqli_fetch_array($sql);
			if($ligne["AllSystems"]==1){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." $ruleid] is an all systems rule\n";}
				return "\tip\t0.0.0.0 - 255.255.255.255";
			}
		}
		
		$results=$q->QUERY_SQL("SELECT * FROM webfilter_ipsources WHERE ruleid='$ruleid' ORDER BY ipaddr");
		foreach ($results as $index=>$ligne){
			$array[]="\tip\t{$ligne["ipaddr"]}";
		
		
		}
		if(count($array)>0){return @implode("\n", $array);}
		
	}
	
	
	private function build_sources(){
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard build SOURCES ....\n";}
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		
		$sql="SELECT ID,groupname FROM webfilter_rules WHERE enabled=1 ORDER BY zOrder";
		$results=$q->QUERY_SQL($sql);
		foreach($results as $index=>$ligne){
			$RULE_ID=$ligne["ID"];
			$rulename=$ligne["groupname"];
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
		
		
		if(isset($GLOBALS["build_categories_uris"][$category])){return $GLOBALS["build_categories_uris"][$category];}
		$urls_table="categoryuris_$category";
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
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
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
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

	
	private function parse_redirect_uri($url,$pattern=null){ return $url; }
	
		
	private function build_categories_paragraph($category){
		$array=array();
		echo "Starting......: ".date("H:i:s")." Building `$category` (L.".__LINE__.")\n";

		$category_id=intval($category);
		$postgress=new postgres_sql();
		
		$ligne=pg_fetch_array($postgress->QUERY_SQL("SELECT categoryname,official_category FROM personal_categories WHERE category_id='$category_id'"));
		$official_category=$ligne["official_category"];
		$categoryname2=$ligne["categoryname"];
		

		
		$OrigninalCategoryName=$category;
		if(isset($GLOBALS["CATEGORIES_BUILDED"][$OrigninalCategoryName])){return;}
		echo "Starting......: ".date("H:i:s")." ufdbGuard Checking category `$categoryname2($category_id)`\n";
		echo "Starting......: ".date("H:i:s")." ufdbGuard Enabling $categoryname2($category_id)\n";
		
		if($official_category==1){
			if(!$this->CORP_LICENSE){
			    $this->TMPLOG[]="# $categoryname2($category_id) no license [".__LINE__."]";
				return "# $categoryname2($category_id) no license...\n";
			}
		}
		

		$main_artica_path="/var/lib/ufdbartica";
		$BASEArt="$main_artica_path/$category_id";
		$categorypathArtica="$BASEArt/domains.ufdb";
		$categoryname=$category_id;
		$BANNARTICA=array();
		
		if(!is_file($categorypathArtica)){
            $this->TMPLOG[]="# $categoryname2($category_id) $categorypathArtica no such file [".__LINE__."]";
			return "# $categorypathArtica $categoryname($category_id) doesn't exists";
			echo "Starting......: ".date("H:i:s")." ".__LINE__."[ART] INFO `$categorypathArtica` $categoryname($category_id) doesn't exists\n";
			return null;
		}
		
		
// ***********************************************************************************************************************************************************		

        $this->TMPLOG[]="# $categoryname2($category_id) ADDED [".__LINE__."]";
		if(isset($GLOBALS["ADDED_CATEGORY_TOFILE"][$category_id])){
            $this->TMPLOG[]="# [NONE]: ADDED_CATEGORY_TOFILE [".__LINE__."]";
		    return;}
		if(isset($BANNARTICA[$OrigninalCategoryName])){
            $this->TMPLOG[]="# [NONE]: BANNARTICA[$OrigninalCategoryName] [".__LINE__."]";
		    return;}
		if(isset($GLOBALS["UFDBART"][$category_id])){
            $this->TMPLOG[]="# [NONE]: UFDBART[$category_id] [".__LINE__."]";
		    return;
		}

			
		echo "Starting......: ".date("H:i:s")." ".__LINE__."[ART] `$categoryname2($category_id)` exists\n";
		if(!is_file("$BASEArt/expressions")){@file_put_contents("$BASEArt/expressions", "\n");}
		
		$GLOBALS["UFDBART"]["P$category_id"]="P$category_id";
		$GLOBALS["ADDED_CATEGORY_TOFILE"]["P$category_id"]=true;
		$array[]="# $categoryname2 ID: $category_id, official:$official_category\n";
		$array[]="category \"P$category_id\" {";
		$categorypathArtica=str_replace(".ufdb", "", $categorypathArtica);
		$array[]="\tdomainlist\t\"$categorypathArtica\"";
		$array[]="}\n";	
		
		echo "Starting......: ".date("H:i:s")." ufdbGuard $categoryname2($category_id) BUILDED in list\n";
		$GLOBALS["CATEGORIES_BUILDED"]["P$category_id"]=true;
		$GLOBALS["CATEGORIES_ADDED"]["P$category_id"]=true;
        $this->TMPLOG[]="# [OK]: $category_id [".__LINE__."]";
		return @implode("\n", $array);
		
	}
	

	
	
		
	function build_categories(){
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$sock=new sockets();
		$main_path="/var/lib/squidguard";
		$main_artica_path="/var/lib/ufdbartica";
		if($GLOBALS["VERBOSE"]){echo "DEBUG:: main_path=$main_path main_artica_path=$main_artica_path\n";}
		echo "Starting......: ".date("H:i:s")." * * * Building Categories (L.".__LINE__.") * * * \n";
		
		
		
		
		$add_ufdbgard_secu=false;
		$sql="SELECT category FROM webfilter_blks GROUP BY category";
		if($GLOBALS["VERBOSE"]){echo "DEBUG:: $sql\n";}
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){
            $this->TMPLOG[]="# MySQL Error [".__LINE__."]";
		    echo "!!!!!!!!!!!!!!! FATAL !!!! $q->mysql_error\n";}
		if($GLOBALS["VERBOSE"]){echo "DEBUG::".__FUNCTION__."::webfilter_blks: `".count($results)."` items\n";}


		foreach($results as $index=>$ligne){
			$category=intval($ligne["category"]);
			$this->TMPLOG[]="# Building category [$category] [".__LINE__."]";
			if($GLOBALS["VERBOSE"]){echo "DEBUG:: $category -> build_categories_paragraph($category)\n";}
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


		while (list ($key, $line) = each ($ufdbguardConfig) ){
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
		//$array[]="\tdomainlist	security/domains";
		$array[]="\tcacerts \"/var/lib/squidguard/security/cacerts\"";
		$array[]="\toption enforce-https-with-hostname {$ufdbguardConfig["enforce-https-with-hostname"]}";
		$array[]="\toption enforce-https-official-certificate {$ufdbguardConfig["enforce-https-official-certificate"]}";
		$array[]="\toption https-prohibit-insecure-sslv2 {$ufdbguardConfig["https-prohibit-insecure-sslv2"]}";
		$array[]="\toption allow-unknown-protocol-over-https {$ufdbguardConfig["allow-unknown-protocol-over-https"]}";
		//$array[]="\tallow-citrixonline-over-https on";
		$array[]="}\n";	
			
		//$array[]="\ncategory checked{";
		//$array[]="domainlist	checked/domains";
		//@mkdir("/var/lib/squidguard/checked",0755,true);
		//$array[]="}\n";
	
		return @implode("\n", $array);
	
	

	}
	
	private function build_acls_uriFromRuleID($ID){
		
		$dnsfiltersocks=new dnsfiltersocks();
		$UfdbListenPort=intval($dnsfiltersocks->GET_INFO("UfdbListenPort"));
		$UfdbListenInterface=trim($dnsfiltersocks->GET_INFO("UfdbListenInterface"));
		$this->DefaultIpRedirection=$dnsfiltersocks->GET_INFO("DefaultIpRedirection");
		if($this->DefaultIpRedirection==null){$this->DefaultIpRedirection="127.0.0.1";}
		$this->dns_neg_ttl=intval($dnsfiltersocks->GET_INFO("dns_neg_ttl"));
		if($this->dns_neg_ttl==0){$this->dns_neg_ttl=3600;}
		
		$RedirectUriDEF="http://$this->DefaultIpRedirection/$this->dns_neg_ttl/%s/%t";
		
		
		
		if($ID==0){
			$DNS_PARAMS=unserialize(base64_decode($this->DansGuardianDefaultMainRule["ExternalWebPage"]));
			if(!isset($DNS_PARAMS["dns_use_default"])){$DNS_PARAMS["dns_use_default"]=1;}
			if(!isset($DNS_PARAMS["dns_ipaddr"])){$DNS_PARAMS["dns_ipaddr"]="127.0.0.1";}
			if(!isset($DNS_PARAMS["dns_neg_ttl"])){$DNS_PARAMS["dns_neg_ttl"]=3600;}
			if($DNS_PARAMS["dns_use_default"]==1){return array("$RedirectUriDEF","$RedirectUriDEF");}
		}
		
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$sql="SELECT ExternalWebPage FROM webfilter_rules WHERE ID=$ID";
		$ligne=$q->mysqli_fetch_array($sql);
		$DNS_PARAMS=unserialize(base64_decode($ligne["ExternalWebPage"]));
		if(!isset($DNS_PARAMS["dns_use_default"])){$DNS_PARAMS["dns_use_default"]=1;}
		if(!isset($DNS_PARAMS["dns_ipaddr"])){$DNS_PARAMS["dns_ipaddr"]="127.0.0.1";}
		if(!isset($DNS_PARAMS["dns_neg_ttl"])){$DNS_PARAMS["dns_neg_ttl"]=3600;}
		if($DNS_PARAMS["dns_use_default"]==1){
			return array($RedirectUriDEF,$RedirectUriDEF);
		}
		if(intval($DNS_PARAMS["dns_neg_ttl"])==0){$DNS_PARAMS["dns_neg_ttl"]=3600;}
		if($DNS_PARAMS["dns_ipaddr"]==null){$DNS_PARAMS["dns_ipaddr"]="127.0.0.1";}
		
		
		
		$RedirectUriDEF="http://{$DNS_PARAMS["dns_ipaddr"]}/{$DNS_PARAMS["dns_neg_ttl"]}/%s/%t";
		return array("$RedirectUriDEF",$RedirectUriDEF);
	}
	
	

	
	
	function build_acls(){
		$allows=array();
		$banns=array();		
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$time=null;
		$rewrite_rules=null;
		$bannedExtDomains=null;
		$bannedfiles=null;
		$bannedRegexP=null;	
		$DNS_PARAMS=array();
		if($this->DefaultIpRedirection==null){
			$dnsfiltersocks=new dnsfiltersocks();
			$this->DefaultIpRedirection=$dnsfiltersocks->GET_INFO("DefaultIpRedirection");
			if($this->DefaultIpRedirection==null){$this->DefaultIpRedirection="127.0.0.1";}
			$this->dns_neg_ttl=intval($dnsfiltersocks->GET_INFO("dns_neg_ttl"));
			if($this->dns_neg_ttl==0){$this->dns_neg_ttl=3600;}
		}
		
		
		if(!isset($this->DansGuardianDefaultMainRule["endofrule"])){$this->DansGuardianDefaultMainRule["endofrule"]="any";}
		$endofrule=$this->DansGuardianDefaultMainRule["endofrule"];
		
		$http_code=302;
		
		if(isset($this->DansGuardianDefaultMainRule["ExternalWebPage"])){
			$DNS_PARAMS=unserialize(base64_decode($this->DansGuardianDefaultMainRule["ExternalWebPage"]));
		}
		
		if(!isset($DNS_PARAMS["dns_use_default"])){$DNS_PARAMS["dns_use_default"]=1;}
		if(!isset($DNS_PARAMS["dns_ipaddr"])){$DNS_PARAMS["dns_ipaddr"]="127.0.0.1";}
		if(!isset($DNS_PARAMS["dns_neg_ttl"])){$DNS_PARAMS["dns_neg_ttl"]=3600;}
		
		
		if($endofrule<>null){$endofrule=" $endofrule";}
		$RedirectUriDEF="http://{$DNS_PARAMS["dns_ipaddr"]}/{$DNS_PARAMS["dns_neg_ttl"]}";
		$array[]="# dns_use_default= ".intval($DNS_PARAMS["dns_use_default"]);
	
		if(intval($DNS_PARAMS["dns_use_default"])==1){
			$array[]="# DefaultIpRedirection='$this->DefaultIpRedirection'";
			$RedirectUriDEF="http://$this->DefaultIpRedirection/$this->dns_neg_ttl/%s/%t";
		}
		echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard redirect banned to \"$RedirectUriDEF\"\n";

		
		
		$array[]="acl{";
		$sql="SELECT category,modeblk FROM webfilter_blks WHERE webfilter_id=0";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";}
		
		
		echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard build ACLS for default rule ". count($results)." items...\n";
		
		foreach($results as $index=>$ligne){
			$bannedRegexP=null;
			$category=intval($ligne["category"]);
			echo "\n********************* $category *********************\n\n";
			if($category==0){continue;}
			if(isset($GLOBALS["CATEGORIES_SKIPPED"]["P$category"])){echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS:$category skipped\n";continue;}
			if(!isset($GLOBALS["CATEGORIES_ADDED"]["P$category"])){echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS:$category not added\n";continue;}
			
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard build DEFAULT: ACLS:$category banned={$ligne["modeblk"]}\n";}
			
			if($ligne["modeblk"]==0){
				if(isset($GLOBALS["UFDBART"]["P$category"])){$banns[$category]="!{$GLOBALS["UFDBART"]["P$category"]}";}
			}
			
			if($ligne["modeblk"]==1){
				if(isset($GLOBALS["UFDBART"]["P$category"])){$allows[$category]="{$GLOBALS["UFDBART"]["P$category"]}";}
			}			
		}
		
		
		if($GLOBALS["VERBOSE"]){echo "\n\n ****************** \n\n Checks Groups for 0 \n\n ****************** \n\n";}
			
		$sql="SELECT webfilter_blkid,blacklist FROM webfilter_blklnk WHERE webfilter_ruleid=0";
		
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:0 FATAL !!! $q->mysql_error\n";}
		foreach ($results as $index=>$blks){
			$groupid=$blks["webfilter_blkid"];
			$blacklist=$blks["blacklist"];
			$ligne2=$q->mysqli_fetch_array("SELECT enabled FROM webfilter_blkgp WHERE ID=$groupid");
			if($ligne2["enabled"]==0){continue;}
		
	
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
		
		
	
		$RR=array();
		


		
		$bannsFINAL=array();
		$allowsFINAL=array();
		if($GLOBALS["VERBOSE"]){echo "DEBUG: Default rule: BANN: ". count($banns)." items\n";}
		
		

		while (list ($category, $line) = each ($banns) ){$bannsFINAL[]=$line;}
		while (list ($category, $line) = each ($allows) ){$allowsFINAL[]=$line;}
				
		if($GLOBALS["VERBOSE"]){echo "DEBUG: Default rule: BANN: ". count($bannsFINAL)." items\n";}
		$arrayDEF[]="\t\"default\"$time{";
		$arrayDEF[]="\t\tpass ".trim(@implode(" ", $allowsFINAL)." $bannedExtDomains$bannedfiles$bannedRegexP".@implode(" ", $bannsFINAL)).$endofrule;
		$arrayDEF[]="\t\tredirect $RedirectUriDEF";
		$arrayDEF[]="\t}";	
		
		$acls_default=@implode("\n", $arrayDEF);
		$DefaultPosition=$this->DansGuardianDefaultMainRule["defaultPosition"];
		if(!is_numeric($DefaultPosition)){$DefaultPosition=0;}
		
		if($DefaultPosition==0){
			$array[]=$acls_default;
		}
		
		echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS DefaultPosition = $DefaultPosition\n";
		$bannedfiles=null;
		if(is_array($GLOBALS["SRC"])){
		    foreach ($GLOBALS["SRC"] as $RULE_ID=>$groupname){
				echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS `$groupname` ID:$RULE_ID\n";
				$allows=array();
				$banns=array();
				$time=null;
				$else=null;
				echo "Starting......: ".date("H:i:s")." ufdbGuard rule `$groupname`\n";
				
				
				$RedirectUriArray=$this->build_acls_uriFromRuleID($RULE_ID);
				$RedirectUri=$RedirectUriArray[0];
				if(!isset($GLOBALS["TIMESRULES"][$RULE_ID]["MATCH"])){$GLOBALS["TIMESRULES"][$RULE_ID]["MATCH"]=null;}
				
	
				
				if($GLOBALS["TIMESRULES"][$RULE_ID]["MATCH"]<>null){
						$time=" {$GLOBALS["TIMESRULES"][$RULE_ID]["MATCH"]} timeR{$RULE_ID} ";
					}else{
					if($GLOBALS["VERBOSE"]){echo " ***** ***** TIME : $groupname: $RULE_ID rule has no time\n";}
					echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS rule:`$groupname` $RULE_ID rule has no time...\n";
				}			
				
				if(isset($GLOBALS["TIMESRULES"][$RULE_ID]["NEXT"])){
					if(is_numeric($GLOBALS["TIMESRULES"][$RULE_ID]["NEXT"])){
						$else=" else {\n".
						$this->compile_categories_fromruleid($GLOBALS["TIMESRULES"][$RULE_ID]["NEXT"]).
						"\n\t\tredirect".$this->parse_redirect_uri($RedirectUri,$redirect_pattern_else)."\n\t}";
					}
				}
				
				
				$compile_categories_fromruleid=$this->compile_categories_fromruleid($RULE_ID);
				
				
				echo "Starting......: ".date("H:i:s")." ufdbGuard rule `$groupname` redirect code:$http_code\n";
				
				$q10=new mysql_squid_builder(true);
				$groupname=$q10->StripBadChars_hostname($groupname);
				$array[]="\t\"$groupname\" $time{";
				
				if($compile_categories_fromruleid<>null){$array[]=$compile_categories_fromruleid;}
				$array[]="\t\tredirect ".$this->parse_redirect_uri($RedirectUri,$RedirectUri);
				$array[]="\t}$else\n";	
			}
			
		}
		
		
		
		if($DefaultPosition==1){
			$array[]=$acls_default;
		}		
		
		$array[]="}\n\n";
		
		
		return @implode("\n", $array);
		
		
	}
	


	
			
	
	

	

	
	
	private function compile_categories_fromruleid($RULE_ID){
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$allows=array();
		$banns=array();
		
		
		if($RULE_ID>0){
			$sql="SELECT GoogleSafeSearch,groupmode,endofrule,UseSecurity FROM webfilter_rules WHERE ID='$RULE_ID'";
			$ligne=$q->mysqli_fetch_array($sql);
			if($ligne["groupmode"]==0){
				echo "Starting......: ".date("H:i:s")." ufdbGuard rule:$RULE_ID deny all users...\n";
				return "\t\tpass none";
			}
		}else{
			$dnsfiltersocks=new dnsfiltersocks();
			$ligne=unserialize($dnsfiltersocks->GET_INFO(DansGuardianDefaultMainRule));
			
		}
		$endofrule=$ligne["endofrule"];
		if($endofrule<>null){$endofrule=" $endofrule";}
		

		$UseSecurity=$ligne["UseSecurity"];
		if(!is_numeric($UseSecurity)){$UseSecurity=0;}
			
		if($GLOBALS["VERBOSE"]){echo "\n\nStarting......: ".date("H:i:s")."  ***** CHECK RULE $RULE_ID *****\n";}
		
		$sql="SELECT * FROM webfilter_blks WHERE webfilter_id=$RULE_ID";
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$results2=$q->QUERY_SQL($sql);
		
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID FATAL !!! $q->mysql_error\n";}
		foreach ($results2 as $index=>$blks ){
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
				
				if(!isset($GLOBALS["PERSONALCATZ"]["P$category"])){echo "Starting......: ".date("H:i:s")." ufdbGuard build $RULE_ID (PERSO) no such category \"$category\"\n";}else{
					echo "Starting......: ".date("H:i:s")." ufdbGuard build  RULE:$RULE_ID (PERSO) category \"$category\" = {$GLOBALS["PERSONALCATZ"]["P$category"]}\n";
				}
				
				
				if($blks["modeblk"]==0){
					if(isset($GLOBALS["PERSONALCATZ"]["P$category"])){$banns[$category]="!{$GLOBALS["PERSONALCATZ"]["P$category"]}";}
					if(isset($GLOBALS["UNIVTOULOUSE"]["P$category"])){$banns[$category]="!{$GLOBALS["UNIVTOULOUSE"]["P$category"]}";}
					if(isset($GLOBALS["UFDBART"]["P$category"])){$banns[$category]="!{$GLOBALS["UFDBART"]["P$category"]}";}
				}
				if($blks["modeblk"]==1){
					if(isset($GLOBALS["PERSONALCATZ"]["P$category"])){$allows[$category]="{$GLOBALS["PERSONALCATZ"]["P$category"]}";}
					if(isset($GLOBALS["UNIVTOULOUSE"]["P$category"])){$allows[$category]="{$GLOBALS["UNIVTOULOUSE"]["P$category"]}";}
					if(isset($GLOBALS["UFDBART"]["P$category"])){$allows["P$category"]="{$GLOBALS["UFDBART"]["P$category"]}";}
				}				
				
				
				$ARRAY["{$blks["modeblk"]}$RULE_ID{$blks["category"]}"]=true;
			}
			
		if($GLOBALS["VERBOSE"]){echo "\n\n ****************** \n\n Checks Groups for $RULE_ID \n\n ****************** \n\n";}
			
		$sql="SELECT webfilter_blkid,blacklist FROM webfilter_blklnk WHERE webfilter_ruleid=$RULE_ID";
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID FATAL !!! $q->mysql_error\n";}
		
		foreach ($results as $index=>$blks){
			$groupid=$blks["webfilter_blkid"];
			$blacklist=$blks["blacklist"];
			$ligne2=$q->mysqli_fetch_array("SELECT groupname,enabled FROM webfilter_blkgp WHERE ID=$groupid AND `enabled`=1");
			if($GLOBALS["VERBOSE"]){echo "DEBUG: {$ligne2["groupname"]}: Group id:$groupid: blacklist=$blacklist\n";}
		}
		
		//compileFinal
		
		$bannsFINAL=array();
		$allowsFINAL=array();
		while (list ($category, $line) = each ($banns) ){
			$bannsFINAL[]=$line;
		}
		while (list ($category, $line) = each ($allows) ){
			$allowsFINAL[]=$line;
		}		
		
			
		if($UseSecurity==1){
			echo "Starting......: ".date("H:i:s")." ufdbGuard build RULE:$RULE_ID SSL check enabled...\n";
			$banns[]="!security";			
		}
			
		
		return "\t\tpass ".trim(@implode(" ", $allowsFINAL)."$bannedfiles ".@implode(" ", $bannsFINAL)).$endofrule;
		
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
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$sock=new sockets();
		$ufdbguardReloadTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardReloadTTL"));
		if($ufdbguardReloadTTL<1){$ufdbguardReloadTTL=10;}
		
		if(preg_match("#category_.+?#", $category)){$category=$q->tablename_tocat($category);}	

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
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$categorySource=$category;
		$category=str_replace("/", "_", $category);
		$main_path="/var/lib/squidguard/$category";
		
		echo "Starting......: ".date("H:i:s")." ufdbGuard Path  = $main_path\n";
		
		$tablename="category_".$q->category_transform_name($category);
		$tablename=str_replace("category_category", "category", $tablename);
		$tablename_uris="categoryuris_".$q->category_transform_name($categorySource);
		$tablename_uris=str_replace("categoryuris_categoryuris", "categoryuris_", $tablename_uris);
		
		echo "Starting......: ".date("H:i:s")." ufdbGuard Table Domains = $tablename\n";
		echo "Starting......: ".date("H:i:s")." ufdbGuard Table URLs  = $tablename_uris\n";
		
		
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard check Domains table `$tablename`\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard check Uris table `$tablename_uris`\n";}
		squid_admin_mysql(2, "Compiling $categorySource table `$tablename`",__FUNCTION__,__FILE__,__LINE__,"$categorySource");

		$sql="SELECT COUNT( * ) AS tcount FROM webfiltering_meta_urls WHERE category='$categorySource'";
		$ligne2=$q->mysqli_fetch_array($sql);
		$META_URIS=intval($ligne2["tcount"]);
		
		
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
		
		if($META_URIS>0){
			$f = @fopen("$main_path/urls", 'a');
			UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{extracting} webfiltering_meta_urls...",10);
			$results=$q->QUERY_SQL("SELECT pattern FROM webfiltering_meta_urls WHERE category='$categorySource'");
			while ($ligne = mysqli_fetch_assoc($results)) {
				@fwrite($f, $ligne["pattern"]."\n");
			}
			@fclose($f);
		}
		
		
		
		$sql="SELECT COUNT( * ) AS tcount FROM webfiltering_meta_items WHERE category='$categorySource'";
		$ligne2=$q->mysqli_fetch_array($sql);
		$META_ITEMS=intval($ligne2["tcount"]);
		
		
		
		$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{extracting} $tablename...",20);
		if(!$q->TABLE_EXISTS($tablename)){
				if($META_ITEMS==0){
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
		}
		
		$tmpdir=$unix->TEMP_DIR();
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$badDomains[""]=true;
		$badDomains["com"]=true;
		$badDomains["fr"]=true;
		$badDomains["de"]=true;
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
			while (list ($extensions,$none) = each ($badDomains) ){
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
		
		if($META_ITEMS>0){
			$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{extracting} webfiltering_meta_items...",35);
			$f = @fopen("$tmpdir/$tablename/domains", 'a');
			$results=$q->QUERY_SQL("SELECT pattern FROM webfiltering_meta_items WHERE category='$categorySource'");
			while ($ligne = mysqli_fetch_assoc($results)) {
				@fwrite($f, $ligne["pattern"]."\n");
			}
			@fclose($f);
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
		$sock=new sockets();
		$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
		$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
		if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}				
		if($EnableRemoteStatisticsAppliance==1){return;}		
		$t=time();
		
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
		
		squid_admin_mysql(2, "`$category` transformed to $category_compile in $directory directory",__FUNCTION__,__FILE__,__LINE__,"$categoryLog");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard Compiling $category in $directory\n";}
		
		if(!is_file("$directory/urls")){@file_put_contents("$directory/urls", "\n");}
		
		$u=" -u $directory/urls";
		$d=" -d $directory/domains";
		$EXEC_NICE=$unix->EXEC_NICE();
		$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{installing} $category...",52);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." $ufdbGenTable $directory/domains\n";}
		$cmd="$EXEC_NICE$ufdbGenTable -n -q -W -t $category_compile$d$u";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." `$cmd`\n";}
		
		
		
		$results=shell_exec($cmd);
		$this->UFDBGUARD_COMPILE_CATEGORY_PROGRESS("{installing} $category...",55);
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		
		
	}
	

	private function VerifyDomainCompiledPattern($pattern){
		if(!preg_match("#^(.+?)\.(.+)#",$pattern)){return false;}
		return true;
	}	
	
	
}