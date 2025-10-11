<?php
include_once(dirname(__FILE__)."/class.ccurl.inc");
include_once(dirname(__FILE__)."/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/class.stats-appliance.inc");
include_once(dirname(__FILE__)."/class.artica-meta.inc");
include_once(dirname(__FILE__)."/class.realtime-buildsql.inc");
class squid_logs_parser{
	
	private $EnableSquidRemoteMySQL=0;
	private $uuid;
	private $hostname=null;
	private $EnableArticaMetaClient=0;
	
	function __construct($stop=false){
		$GLOBALS["DEBUG_CACHES"]=false;
		$GLOBALS["LOG_HOSTNAME"]=false;
		$GLOBALS["LogFileDeamonLogDir"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogFileDeamonLogDir");
		if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid/realtime-events";}	
		$EnableProxyLogHostnames=intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyLogHostnames")));
		
		$this->EnableArticaMetaClient=intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMetaClient")));
		
		if($EnableProxyLogHostnames==1){$GLOBALS["LOG_HOSTNAME"]=true;}
		
		$stats_appliance=new stats_appliance();
		
		$unix=new unix();
		$this->hostname=$unix->hostname_g();
		$this->uuid=$unix->GetUniqueID();
		$this->EnableSquidRemoteMySQL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidRemoteMySQL"));
		if($stop){return;}
		$this->berekley_memory_dump();

		
		
		$this->ScanCachedStats();
		$this->ScanStatsApplianceStatistics();
		$this->ScanStatsApplianceLogs();
		$this->ScanRealtimeFiles();
		$this->ScanHyperCacheFiles();
		
		
	}
	
	private function berekley_memory_dump(){
		$unix=new unix();
		$files=$unix->DirFiles($GLOBALS["LogFileDeamonLogDir"],"_realARRAY\.array$");
		while (list ($filename, $ligne) = each ($files) ){
			preg_match("#^([0-9]+)\.#", $filename,$re);
			$TimeFile=$re[1];
			$path="{$GLOBALS["LogFileDeamonLogDir"]}/$filename";
			$LOCK_FILE="{$GLOBALS["LogFileDeamonLogDir"]}/$filename.LCK";
			if($this->isLocked($path)){$this->events("LOCKED $path SKIP!");continue;}
			@file_put_contents($LOCK_FILE, getmypid());
			$array=unserialize(@file_get_contents($path));
			if(!is_array($array)){@unlink($path);@unlink($LOCK_FILE);continue;}
			$this->events("berekley_memory_dump: check $path");
			if(!$this->berekley_memory_dump_tofile2($TimeFile,$array)){@unlink($LOCK_FILE);continue;}
			@unlink($path);@unlink($LOCK_FILE);
			
		}
		
		
	}
	
	private function rtt_microtime_ms($start){
		return  round($this->rtt_microtime_float() - $start,3);
	}	
	private function rtt_microtime_float(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	
	private function berekley_memory_dump_tofile2($TimeFile,$array){
		$t1=$this->rtt_microtime_float();
		$db_path="{$GLOBALS["LogFileDeamonLogDir"]}/$TimeFile.$t1.".getmypid()."_realtime.db";
		if(!$this->berekley_db_create($db_path)){return;}
	
		$CountOfRows= count($array);
		$db_con = @dba_open($db_path, "w","db4");
		if(!$db_con){
			$this->events("berekley_memory_dump_tofile:: Dump $CountOfRows rows to $db_path failed connect");
			@dba_close($db_con);
			return;
		}
	
		foreach ($array as $key=>$value){
			@dba_replace($key,$value,$db_con);
		}
		$texec=$this->rtt_microtime_ms($t1);
		@dba_close($db_con);
	
		$this->events("berekley_memory_dump_tofile:: Dump $CountOfRows rows to $db_path success EnableArticaMetaClient:{$GLOBALS["EnableArticaMetaClient"]}");
	
	
		return true;
	
	}	
	
	
	public function ScanCachedStats(){
		$unix=new unix();
		$files=$unix->DirFiles("/var/log/squid/cached-stats","[0-9]+\.[0-9]+\.array");
		foreach ($files as $num=>$ligne){
			$full_path="/var/log/squid/cached-stats/$num";
			preg_match("#[0-9]+\.([0-9]+)\.array#", $num,$re);
			$time=$re[1];
			$array=unserialize(@file_get_contents($full_path));
			if(!is_array($array)){@unlink($full_path);continue;}
			if(!isset($array["HITS"]["SIZE"])){@unlink($full_path);continue;}
			$hit_size=$array["HITS"]["SIZE"];
			$miss_size=$array["MISS"]["SIZE"];
			$total=$hit_size+$miss_size;
			$prc=$hit_size/$total;
			$prc=$prc*100;
			$tablename=date("Ymd",$time)."_cacherated";
			cache_rate_day_FULL($tablename);
			
			$zDate=date("Y-m-d H:i:s",$time);
			$q=new mysql_squid_builder();
			$q->QUERY_SQL("INSERT IGNORE INTO `$tablename` (zDate,zrate) VALUES (NOW(),'$prc')");
			if(!$q->ok){continue;}
			@unlink($full_path);
		}
		
	}
	
	public function ScanStatsApplianceStatistics(){}
	private function ScanStatsApplianceLogs(){}

	public function SendFile($file_path,$uri){
		
		$size=@filesize($file_path);
		$filename=basename($file_path);
		$array=array(
				"UUID"=>$this->uuid,
				"HOSTNAME"=>$this->hostname,"SIZE"=>$size,"FILENAME"=>$filename,"MD5FILE"=>md5_file($file_path));
		
		events("SendFile: PUSH $file_path");
		
		
		
		
		$curl=new ccurl($uri,false,null,$this->nochecksquid);
		$curl->x_www_form_urlencoded=false;
		
		if(!$curl->postFile(basename($file_path),$file_path,$array )){
			$this->events("$filename: Failed CURL_ERROR: $curl->error $curl->data");
			return false;
		}
		
		if($GLOBALS["VERBOSE"]){echo "\n ******************* \n  $curl->data \n ************* \n";}
		
		if(!preg_match("#<RESULTS>(.*?)</RESULTS>#is", $curl->data,$re)){
			$this->events("$uri did not report something !!!");
			return;
		}
		
		$RESULT=$re[1];
		$this->events("$filename RESULT = $RESULT");
		
		if($RESULT<>"SUCCESS"){
			$this->events("Failed, $uri report $RESULT ");
			return;
		}
		
		$this->events("Success, upload $filename");
		return true;
	}
	
	
	private function isLocked($path){
		$LOCK_FILE="$path.LCK";
		if(!is_file($LOCK_FILE)){return false;}
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		$timeexec=$GLOBALS["CLASS_UNIX"]->file_time_min($LOCK_FILE);
		if($timeexec<5){return true;}
		
		
		$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($LOCK_FILE);
		if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
			$this->events("$LOCK_FILE still locked by pid $pid since {$timeexec}min");
			return true;
		}
		@unlink($LOCK_FILE);
		return false;
			
	
		
	}
	
	private function CleanHyperCacheLCK(){
		$unix=new unix();
		$Files=$unix->DirFiles($GLOBALS["LogFileDeamonLogDir"],"hypercachertt_.*?\.LCK$");
		foreach ($Files as $filename=>$none){
			$path="{$GLOBALS["LogFileDeamonLogDir"]}/$filename";
			$source_filename=str_replace(".LCK","",$filename);
			if(!is_file("{$GLOBALS["LogFileDeamonLogDir"]}/$source_filename")){
				@unlink($path);
			}
		}
		
	}
	
	private function ScanHyperCacheFiles(){
		$this->CleanHyperCacheLCK();
		$unix=new unix();
		$MyPID=getmypid();
		$ARRAY=array();
		$Files=$unix->DirFiles($GLOBALS["LogFileDeamonLogDir"],"hypercachertt_[0-9]+\.db$");
		$CurrentMin=date("YmdHi");
		foreach ($Files as $filename=>$none){
			$path="{$GLOBALS["LogFileDeamonLogDir"]}/$filename";
			preg_match("#hypercachertt_([0-9\.]+)\.db$#", $filename,$re);
			$curtime=$re[1];
			if($curtime==$CurrentMin){continue;}
			
			$LOCK_FILE="{$GLOBALS["LogFileDeamonLogDir"]}/$filename.LCK";
			if($this->isLocked($path)){
				$this->events("$path LOCKED - SKIP");
				continue;
			}
			@file_put_contents($LOCK_FILE, $MyPID);
			if(!is_file($path)){if(is_file($LOCK_FILE)){$this->events("$path doesn't exists remove $LOCK_FILE");}continue;}
			$t1=$this->microtime_float();
			
			if(!$this->ParseHyperCacheFile($path)){
				$this->events("ScanHyperCacheFiles:: Scanning $filename failed Took:".$this->microtime_ms($t1));
				@unlink($LOCK_FILE);
				continue;
			}
			
			$ARRAY[$path]=$LOCK_FILE;
			
		}
		
		if(!$this->ScanHyperCacheFilesClean()){
			
			while (list ($path, $LOCK_FILE) = each ($ARRAY) ){@unlink($LOCK_FILE);}
			return;
		}
		if(count($ARRAY)==0){return;}
		
		while (list ($path, $LOCK_FILE) = each ($ARRAY) ){
			
			@unlink($path);
			@unlink($LOCK_FILE);
		}
		
		
		
	}
	
	private function ScanHyperCacheFilesClean(){
		if(!isset($GLOBALS["ParseHyperCacheFile"])){return;}
		if(count($GLOBALS["ParseHyperCacheFile"])==0){return;}
		
		while (list ($key, $Array) = each ($GLOBALS["ParseHyperCacheFile"]) ){
			
			$time=strtotime($Array["date"]);
			$Category=$Array["category"];
			$ipaddr=$Array["ipaddr"];
			$count=$Array["count"];
			$zDate=$Array["date"];
			
			$table_hour=date("YmdH",$time)."_hypercache";
			$TABLES[$table_hour][]="('$key','$Category','$zDate','$ipaddr','$count')";
			
		}
		
		
		$q=new mysql_squid_builder();
		while (list ($tablename, $Array) = each ($TABLES) ){
			if(!$q->check_hypercacheHour($tablename)){return false;}
			$q->QUERY_SQL("INSERT IGNORE INTO `$tablename` (zmd5,`category`,`zDate`,`ipaddr`,`hits`) VALUES ".@implode(",", $Array));
			if(!$q->ok){
				$this->events("$tablename: $q->mysql_error");
				return false;
			}
			
			
		}
		
		return true;
		
		
	}
	
	
	function ParseHyperCacheFile($path){
		if($GLOBALS["VERBOSE"]){echo "Parsing $path\n";}
		if(!is_file($path)){
			$this->events("$path no such file");
			if(is_file("$path.LCK")){@unlink("$path.LCK");}
			return false;
		}
		$db_con = @dba_open($path, "r","db4");
		if(!$db_con){$this->events("Warning! DB open $path failed...");return false;}
		
		$mainkey=dba_firstkey($db_con);
		$c=0;
		$n=0;
		while($mainkey !=false){
			$data=dba_fetch($mainkey,$db_con);
			$Array=unserialize(base64_decode($data));
			if(!is_array($Array)){
				$this->events("Warning! $mainkey -> $data not an array...");
				$mainkey=dba_nextkey($db_con);
				continue;
			}
			$c++;
			$n++;
			$xtime=$Array["date"];
			$Category=$Array["category"];
			$ipaddr=$Array["ipaddr"];
			$count=$Array["count"];
			$key=md5("$xtime$Category$ipaddr");
			if(isset($GLOBALS["ParseHyperCacheFile"][$key])){
				$GLOBALS["ParseHyperCacheFile"][$key]["count"]=$GLOBALS["ParseHyperCacheFile"][$key]["count"]+1;
				$mainkey=dba_nextkey($db_con);
				continue;
			}
			
	
			$GLOBALS["ParseHyperCacheFile"][$key]["date"]=$xtime;
			$GLOBALS["ParseHyperCacheFile"][$key]["category"]=$Category;
			$GLOBALS["ParseHyperCacheFile"][$key]["ipaddr"]=$ipaddr;
			$GLOBALS["ParseHyperCacheFile"][$key]["count"]=1;
			$mainkey=dba_nextkey($db_con);
	
		}
		
		return true;
	}
	
	
	private function ScanRealtimeFiles(){
		$unix=new unix();
		$this->CleanMemory();
		$COUNTFILES=0;
		$Files=$unix->DirFiles($GLOBALS["LogFileDeamonLogDir"],"[0-9\.]+_realtime\.db$");
		$GLOBALS["GetLineFromArray"]=0;
		$MaxFiles=count($Files);
		if($MaxFiles==0){return;}
		$CounterMAX=$MaxFiles;
		if($CounterMAX>100){$CounterMAX=100;}
		$MyPID=getmypid();
		$c=0;
		$CounterFiles=0;
		foreach ($Files as $filename=>$none){
			$c++;
			$path="{$GLOBALS["LogFileDeamonLogDir"]}/$filename";
			$LOCK_FILE="{$GLOBALS["LogFileDeamonLogDir"]}/$filename.LCK";
			
			if($this->isLocked($path)){
				$this->events("$path LOCKED - SKIP");
				continue;
			}
			@file_put_contents($LOCK_FILE, $MyPID);
			
			$Time=$unix->file_time_sec($path);
			$size=@filesize($path)/1024;
			$size=$size/1024;
			$size=round($size,2);
			$t1=$this->microtime_float();
			
			if(!is_file($path)){
				if(is_file($LOCK_FILE)){
					$this->events("$path doesn't exists remove $LOCK_FILE");
				}
				
				continue;
			}
			
			events("$c/$MaxFiles - $CounterFiles/$CounterMAX ScanRealtimeFiles:: Scanning $filename {$Time}s {$size}MB");
			if(!$this->ParseRealeTimeFile($path)){
				$this->events("$c/$MaxFiles ScanRealtimeFiles:: Scanning $filename failed Took:".$this->microtime_ms($t1));
				@unlink($LOCK_FILE);
				continue;
			}
			
			$this->events("$c/$MaxFiles - $CounterFiles/$CounterMAX ScanRealtimeFiles:: Scanning $filename success {$GLOBALS["GetLineFromArray"]} rows, Took:".$this->microtime_ms($t1) ." {$size}MB");
			
			
			@unlink($path);
			@unlink($LOCK_FILE);
			$COUNTFILES++;
			$CounterFiles++;
			
			if($CounterFiles>$CounterMAX){break;}
			
			if($COUNTFILES>3){
				$this->ComputersClean();
				$this->FileSystemUserAgent_empty();
				$this->CleanTables();
				$this->CleanMemory();
				$COUNTFILES=0;
				$this->events("$c/$MaxFiles - $CounterFiles/$CounterMAX  ScanRealtimeFiles:: {$GLOBALS["GetLineFromArray"]} lines; Clean memory done ".$this->microtime_ms($t1));
			}
			
			
		}
		
		$t1=$this->microtime_float();
		$this->ComputersClean();
		$this->FileSystemUserAgent_empty();
		$this->CleanTables();
		
		
		$Files=$unix->DirFiles($GLOBALS["LogFileDeamonLogDir"],"[0-9\.]+_realtime\.db\.LCK$");
		foreach ($Files as $filename=>$none){
			$LCK_PATH="{$GLOBALS["LogFileDeamonLogDir"]}/$filename";
			$dbname=str_replace(".LCK", "", $filename);
			$DB_PATH="{$GLOBALS["LogFileDeamonLogDir"]}/$dbname";
			if(!is_file($DB_PATH)){
				$this->events("Removing BAD LCK $LCK_PATH, $DB_PATH no such file");
				@unlink($LCK_PATH);
			}
			
		}
		
		
		$this->events("$c/$MaxFiles - $CounterFiles/$CounterMAX ScanRealtimeFiles:: {$GLOBALS["GetLineFromArray"]} lines; Clean memory done ".$this->microtime_ms($t1));

		
		
		
		
	}
	
	private function events($text){
		if(trim($text)==null){return;}
	
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/squid/logfile_daemon.debug";
		if(!isset($GLOBALS["PREFIX_LOGSTATS"])){$GLOBALS["PREFIX_LOGSTATS"]=null;}
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		if($GLOBALS["VERBOSE"]){echo "$date:[".basename(__FILE__)."] $pid {$GLOBALS["PREFIX_LOGSTATS"]} $text\n";}
		@fwrite($f, "$date:[".basename(__FILE__)."] $pid `$text`\n");
		@fclose($f);
	
	}
	
	private function CleanMemory(){
		$GLOBALS["TABLES_PRIMAIRES_CACHEHOUR"]=array();
		$GLOBALS["TABLES_PRIMAIRES_SEARCHWORDS"]=array();
		$GLOBALS["TABLES_PRIMAIRES_SIZEHOUR"]=array();
		$GLOBALS["TABLES_PRIMAIRES_QUOTATEMP"]=array();
		$GLOBALS["TablePrimaireHour"]=array();
		$GLOBALS["TABLES_PRIMAIRES_CACHEHOUR"]=array();
		$GLOBALS["TABLES_PRIMAIRES_SEARCHWORDS"]=array();
		$GLOBALS["TABLES_PRIMAIRES_SIZEHOUR"]=array();
		$GLOBALS["TABLES_PRIMAIRES_QUOTATEMP"]=array();
		$GLOBALS["TablePrimaireHour"]=array();
		$GLOBALS["UserAgents"]=array();
		$GLOBALS["RTTH_TABLES"]=array();
		$GLOBALS["AUTH_USERDB"]=array();
	}
	
	private function ParseRealeTimeFile($path){
		if($GLOBALS["VERBOSE"]){echo "Parsing $path\n";}
		if(!is_file($path)){
			$this->events("$path no such file");
			if(is_file("$path.LCK")){@unlink("$path.LCK");}
			return false;
		}
		$db_con = @dba_open($path, "r","db4");
		if(!$db_con){$this->events("Warning! DB open $path failed...");return false;}
		
		$mainkey=dba_firstkey($db_con);
		$c=0;
		$n=0;
		while($mainkey !=false){
			$data=dba_fetch($mainkey,$db_con);
			$Array=unserialize(base64_decode($data));
			if(!is_array($Array)){
				$this->events("Warning! $mainkey -> $data not an array...");
				$mainkey=dba_nextkey($db_con);
				continue;
			}
			$c++;
			$n++;
			$this->GetLineFromArray($mainkey,$Array);
			if($n>500){
				$this->events("$path $c items");
				$n=0;
			}
			$mainkey=dba_nextkey($db_con);
		}
		
		dba_close($db_con);
		return true;
		
	}
	
	private function GetFamilySites($sitename){
		if(isset($GLOBALS["GetFamilySites"][$sitename])){return $GLOBALS["GetFamilySites"][$sitename];}
		$fam=new squid_familysite();
		$GLOBALS["GetFamilySites"][$sitename]=$fam->GetFamilySites($sitename);
		return $GLOBALS["GetFamilySites"][$sitename];
	}
	
	private function MacToUid($mac=null){
		if($mac==null){return;}
		if(!isset($GLOBALS["USERSDB"])){$GLOBALS["USERSDB"]=unserialize(@file_get_contents("/etc/squid3/usersMacs.db"));}
		if(!isset($GLOBALS["USERSDB"]["MACS"][$mac]["UID"])){return;}
		if($GLOBALS["USERSDB"]["MACS"][$mac]["UID"]==null){return;}
		return trim($GLOBALS["USERSDB"]["MACS"][$mac]["UID"]);
	
	}
	private function IpToUid($ipaddr=null){
		if($ipaddr==null){return;}
		if(!isset($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"])){return;}
		if($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"]==null){return;}
		$uid=trim($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"]);
	
	}
	
	private function MacToHost($mac=null){
		if($mac==null){return;}
		if(!isset($GLOBALS["USERSDB"]["MACS"][$mac]["HOST"])){return;}
		if($GLOBALS["USERSDB"]["MACS"][$mac]["HOST"]==null){return;}
		$uid=trim($GLOBALS["USERSDB"]["MACS"][$mac]["HOST"]);
	}
	private function IpToHost($ipaddr=null){
		if($ipaddr==null){return;}
		if(!isset($GLOBALS["USERSDB"]["MACS"][$ipaddr]["HOST"])){return;}
		if($GLOBALS["USERSDB"]["MACS"][$ipaddr]["HOST"]==null){return;}
		$uid=trim($GLOBALS["USERSDB"]["MACS"][$ipaddr]["HOST"]);
	}
	
	private function GetUid($uid,$mac,$ipaddr){
		if($uid<>null){return $uid;}
		$uid=$this->MacToUid($mac);
		if($uid<>null){return $uid;}
		$uid=$this->IpToUid($ipaddr);
		if($uid<>null){return $uid;}
	}
	private function mysql_escape_string2($line){
		$search=array("\\","\0","\n","\r","\x1a","'",'"');
		$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
		return str_replace($search,$replace,$line);
	}
	
	private function create_tables($TimeCache){
		if(isset($GLOBALS["create_tables"][$TimeCache])){return;}
		REALTIME_RTTH($TimeCache);
		
		REALTIME_squidhour($TimeCache);
		REALTIME_cachehour($TimeCache);
		REALTIME_sizehour($TimeCache);
		REALTIME_youtubehours($TimeCache);
		REALTIME_quotatemp($TimeCache);
		REALTIME_searchwords($TimeCache);
		$GLOBALS["create_tables"][$TimeCache]=true;
		return true;
	
	}
	
	private function CleanTables(){
		$q=new mysql_squid_builder();
		
		
		$this->RTTH_Clean();
		$this->USERAUTHDB_Clean();
		$this->berekley_db_access_clean();
		$this->berekly_db_mime_clean();
		
		$this->berekley_db_cached_www_clean();
		$this->berekley_db_notcached_www_clean();
		$this->berekley_db_size_clean();
		$this->berekley_proto_clean();
		
		if(count($GLOBALS["TABLES_PRIMAIRES_CACHEHOUR"])>0){
			while (list ($tablename, $content) = each ($GLOBALS["TABLES_PRIMAIRES_CACHEHOUR"]) ){
				
				$q->TablePrimaireCacheHour(null,false,$tablename);
				
				$sql="INSERT IGNORE INTO `$tablename` (`zDate`,`size`,`cached`,`familysite`) VALUES ".@implode(",", $content);
				$q->QUERY_SQL($sql);
				if(!$q->ok){$this->db_error($tablename,$sql,$q->mysql_error);}
			}
		}
		
		if(count($GLOBALS["TABLES_PRIMAIRES_SEARCHWORDS"]>0)){
			while (list ($TableSource, $content) = each ($GLOBALS["TABLES_PRIMAIRES_SEARCHWORDS"]) ){
			$q->check_SearchWords_hour(null,$TableSource);
				$sql="INSERT IGNORE INTO `$TableSource`
				(`zmd5`,`sitename`,`zDate`,`ipaddr`,`hostname`,`uid`,`MAC`,`account`,`familysite`,`words`)
				VALUES ".@implode(",", $content);
				$q->QUERY_SQL($sql);
				if(!$q->ok){$this->db_error($tablename,$sql,$q->mysql_error);}
			}
		
		}
		
		if(count($GLOBALS["TABLES_PRIMAIRES_SIZEHOUR"])>0){
			while (list ($TableSizeHours, $content) = each ($GLOBALS["TABLES_PRIMAIRES_SIZEHOUR"]) ){
				$q->check_sizehour($TableSizeHours);
				$sql="INSERT IGNORE INTO `$TableSizeHours` (`zDate`,`size`,`cached`) VALUES ".@implode(",", $content);
				$q->QUERY_SQL($sql);
				if(!$q->ok){$this->db_error($tablename,$sql,$q->mysql_error);}
			}
		}
		
		
		if(count($GLOBALS["TABLES_PRIMAIRES_QUOTATEMP"])>0){
			while (list ($TableSource, $content) = each ($GLOBALS["TABLES_PRIMAIRES_QUOTATEMP"]) ){
				preg_match("#quotatemp_([0-9]+)#", $TableSource,$re);
				$q->check_quota_hour_tmp($re[1]);
				$q->check_quota_hour($re[1]);
				$sql="INSERT IGNORE INTO `$TableSource` (`xtime`,`keyr`,`ipaddr`,`familysite`,`servername`,`uid`,`MAC`,`size`) VALUES ".@implode(",", $content);
				$q->QUERY_SQL($sql);
				if(!$q->ok){$this->db_error($tablename,$sql,$q->mysql_error);}
			}
		}
		
		
		
		if(count($GLOBALS["TablePrimaireHour"])>0){
			while (list ($TableSource, $content) = each ($GLOBALS["TablePrimaireHour"]) ){
				preg_match("#squidhour_([0-9]+)#", $TableSource,$re);
				$xtime=$re[1];
				$q->TablePrimaireHour($xtime);
				$sql="INSERT IGNORE INTO `squidhour_{$xtime}`  (`sitename`,`uri`,`TYPE`,`REASON`,`CLIENT`,`hostname`,`zDate`,`zMD5`,`uid`,`QuerySize`,`cached`,`MAC`,`category`) VALUES ".@implode(",", $content);
				$q->QUERY_SQL($sql);
				if(!$q->ok){
					if($GLOBALS["VERBOSE"]){echo "\n\n ********************************************************************* \n\n$q->mysql_error\n*********************************************************************\n\n";}
					events("parse_tables_primaires(): Fatal: MySQL error:");
					if(preg_match("#Table 'squidlogs\.(.+?)' doesn't exist#",$q->mysql_error,$re)){
						if($GLOBALS["VERBOSE"]){echo "Creating table: {$re[1]}\n";}
						$q->TablePrimaireHour(null,false,$re[1]);
						$q->QUERY_SQL($sql);
					}
				}
				if(!$q->ok){$this->db_error($tablename,$sql,$q->mysql_error);}
			}
				
		}
		
		
		
	}
	
	
	private function microtime_ms($start){
		return  round($this->microtime_float() - $start,3);
	}
	
	
	private function microtime_float(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}
	
	private function RTTH_Clean(){
		if(count($GLOBALS["RTTH_TABLES"])==0){
			events("RTTH_Clean:: RTTH_TABLES = NOTHING");
			return;
		}
		$q=new mysql_squid_builder();
		
		$c=0;
		
		while (list ($TableSource, $content) = each ($GLOBALS["RTTH_TABLES"]) ){
			REALTIME_RTTH_TABLE($TableSource);
			$c++;
			events("RTTH_Clean:: $TableSource = ".count($content));
			$sql="INSERT IGNORE INTO `squidlogs`.`$TableSource` 
			(`xtime`,`sitename`,`ipaddr`,`uid`,`MAC`,`size`) 
			 VALUES ".@implode(",", $content);
			$q->QUERY_SQL($sql);
			if(!$q->ok){$this->db_error($TableSource,$sql);}
		
		}
		
		$GLOBALS["RTTH_TABLES"]=array();
		if($c>0){
			$unix=new unix();
			$php=$unix->LOCATE_PHP5_BIN();
			$nohup=$unix->find_program("nohup");
			
			
		}
		
	}
	
	private function USERAUTHDB_Clean(){
		if(count($GLOBALS["AUTH_USERDB"])==0){return;}
		$q=new mysql_squid_builder();
		$sql="INSERT IGNORE INTO UserAutDB (zmd5,MAC,ipaddr,uid,hostname,UserAgent) VALUES ".@implode(",", $GLOBALS["AUTH_USERDB"]);
		$q->QUERY_SQL($sql);
		$GLOBALS["AUTH_USERDB"]=array();
		
		
	}
	
	private function gethostbyaddr2($sitename){
		if(isset($GLOBALS["gethostbyaddr2"][$sitename])){return $GLOBALS["gethostbyaddr2"][$sitename];}
		$GLOBALS["gethostbyaddr2"][$sitename]=gethostbyaddr($sitename);
	}
	
	private function GetRealUID($uid=null,$mac=null,$ipaddr=null){
		if(trim(strtolower($uid))<>null){return trim(strtolower($uid));}
		return trim(strtolower($this->GetUid($uid,$mac,$ipaddr)));
	}
	
	
	private function GetLineFromArray($zMD5,$MAIN){
		if(!isset($MAIN["FAMILYSITE"])){$MAIN["FAMILYSITE"]=null;}
		$WORDS=null;
		$IpClass=new IP();
		$logfileD=new logfile_daemon();
		$catz=new mysql_catz();
		$q=new mysql_squid_builder();
		$xtime=$MAIN["TIMESTAMP"];
		$uri=$MAIN["URI"];
		$sitename=$MAIN["sitename"];
		$SIZE=$MAIN["SIZE"];
		$cached=$MAIN["CACHED"];
		$ipaddr=$MAIN["IPADDR"];
		
		
		$MimeType=$MAIN["MIMETYPE"];
		if($MAIN["FAMILYSITE"]<>null){$GLOBALS["GetFamilySites"][$sitename]=$MAIN["FAMILYSITE"]; }
		$mac=$MAIN["MAC"];
		$uid=$MAIN["UID"];
		$UserAgent=$MAIN["USERAGENT"];
		$SquidCode=$MAIN["SQUID_CODE"];
		$code_error=$MAIN["HTTP_CODE"];
		$RESPONSE_TIME=$MAIN["RESPONSE_TIME"];
		$proto=$MAIN["PROTO"];
		if(isset($MAIN["WORDS"])){$WORDS=$MAIN["WORDS"];}
		$SUFFIX_DATE=date("YmdH",$xtime);
		if(preg_match("#(.+?):(.+)#", $SquidCode,$re)){ $SquidCode=$re[1]; }
		$uri=trim($uri);
		if($uri==null){return;}
		$urisrc=$uri;
		$TimeCache=date("YmdH",$xtime);
		$this->create_tables($TimeCache);
		$TablePrimaireHour="squidhour_".$SUFFIX_DATE;
		$TableSizeHours="sizehour_".$SUFFIX_DATE;
		$TableCacheHours="cachehour_".$SUFFIX_DATE;
		$tableYoutube="youtubehours_".$SUFFIX_DATE;
		$tableSearchWords="searchwords_".$SUFFIX_DATE;
		$GLOBALS["GetLineFromArray"]++;
		
		
		if(isset($MAIN["HOSTNAME"])){$hostname=$MAIN["HOSTNAME"]; }
		if($IpClass->isValid($uid)){ $uid=null; }
		$category=$catz->GetMemoryCache($sitename,true);
		
	
		if($GLOBALS["VERBOSE"]){echo "Scanning $SUFFIX_DATE $xtime $ipaddr $sitename\n";}
		if(isset($GLOBALS["ZMD5"][$zMD5])){events("$uri - md5 = $zMD5 is the same !!!"); }
		if(strpos("   $sitename", "www.")>0){ if(preg_match("#^www\.(.+)#", $sitename,$re)){$sitename=$re[1];} }
			
		if($IpClass->isValid($sitename)){
			if(!isset($GLOBALS["SitenameResolved"][$sitename])){$GLOBALS["SitenameResolved"][$sitename]=$this->gethostbyaddr2($sitename); }
			if($GLOBALS["SitenameResolved"][$sitename]<>null){$sitename=$GLOBALS["SitenameResolved"][$sitename];}
		}
			
		if(!isset($GLOBALS["GetFamilySites"][$sitename])){
			$GLOBALS["GetFamilySites"][$sitename]=$this->GetFamilySites($sitename);
			if($GLOBALS["GetFamilySites"][$sitename]==null){$GLOBALS["GetFamilySites"][$sitename]=$sitename;}
		}
		
			
		$familysite=$GLOBALS["GetFamilySites"][$sitename];
		if($familysite=="localhost"){return false;}
		$key=null;
		$uid=$this->GetRealUID($uid,$mac,$ipaddr);
		if($uid<>null){$key="uid";}
		if($key==null){if($mac<>null){$key="MAC";}}
		if($key==null){if($ipaddr<>null){$key="ipaddr";}}
		if($key==null){return false;}
		
		
		if($hostname==null){$hostname=$this->MacToHost($mac);}
		if($hostname==null){$hostname=$this->IpToHost($ipaddr);}
		if(trim($hostname)==null){if($GLOBALS["LOG_HOSTNAME"]){ $hostname=$this->gethostbyaddr2($ipaddr); } }
		
		// TOUT EN MINISCULE
		$uid=trim(strtolower($uid));
		$hostname=trim(strtolower($hostname));
		$mac=trim(strtolower($mac));
		$familysite=trim(strtolower($familysite));
		
		$this->berekley_proto($proto,$familysite,$SIZE,$cached,$xtime);
		$this->berekley_db_size($familysite,$mac,$ipaddr,$uid,$SIZE,$category,$cached,$MimeType,$xtime);
		if($UserAgent<>null){$this->FileSystemUserAgent($UserAgent);}
		$this->ComputersAdd($ipaddr, $mac);
			
		$hour=date("H",$xtime);
		$date=date("Y-m-d H:i:s",$xtime);
		
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: Date: $date: $familysite $uid/$ipaddr\n";}
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: Uri <$uri> Squid code=$SquidCode cached=$cached  Client = $uid/$mac/$hostname [$ipaddr] , Size=$SIZE bytes\n";}
		
		
		$KeyUser=md5($uid.$hostname.$ipaddr.$mac.$UserAgent);
		$UserAgent=$this->mysql_escape_string2($UserAgent);
		$sitename=$this->mysql_escape_string2($sitename);
		$uri=substr($uri, 0,254);
		$uri=$this->mysql_escape_string2($uri);
		$uriT=$this->mysql_escape_string2($uri);
		$hostname=$this->mysql_escape_string2($hostname);
		$category=$this->mysql_escape_string2($category);
		$REASON=$logfileD->codeToString($code_error);
		
		$GLOBALS["TABLES_PRIMAIRES_SIZEHOUR"][$TableSizeHours][]="('$date','$SIZE','$cached')";
		$GLOBALS["TablePrimaireHour"][$TablePrimaireHour][]="('$sitename','$uri','$REASON','$REASON','$ipaddr','$hostname','$date','$zMD5','$uid','$SIZE','$cached','$mac','$category')";
		$GLOBALS["RTTH_TABLES"]["RTTH_$TimeCache"][]="('$xtime','$familysite','$ipaddr','$uid','$mac','$SIZE')";
		
		if(count($GLOBALS["RTTH_TABLES"]["RTTH_$TimeCache"])>800){$this->RTTH_Clean();}
		if(count($GLOBALS["RTTH_TABLES"])>2){$this->RTTH_Clean();}
		
		
		
		if(!isset($GLOBALS["KEYUSERS"][$KeyUser])){
			$GLOBALS["AUTH_USERDB"][]="('$KeyUser','$mac','$ipaddr','$uid','$hostname','$UserAgent')";
			$GLOBALS["KEYUSERS"][$KeyUser]=true;
			if(count($GLOBALS["AUTH_USERDB"])>800){$this->RTTH_Clean();}
		}
		if($SIZE>0){
			$GLOBALS["TABLES_PRIMAIRES_CACHEHOUR"][$TableCacheHours][]="('$date','$SIZE','$cached','$familysite')";
		}
		if($WORDS<>null){
			$GLOBALS["TABLES_PRIMAIRES_SEARCHWORDS"][$tableSearchWords][]="('$zMD5','$sitename','$date','$ipaddr','$hostname','$uid','$mac','0','$familysite','$WORDS')";
		}
	
		if(strpos(" $uri", "youtube")>0){
			$VIDEOID=$logfileD->GetYoutubeID($urisrc);
			if($VIDEOID<>null){
				events("YOUTUBE:: $date: $ipaddr $uid $mac [$VIDEOID]");
				$sql="INSERT IGNORE INTO `$tableYoutube` (`zDate`,`ipaddr`,`hostname`,`uid`,`MAC` ,`account`,`youtubeid`) VALUES ('$date','$ipaddr','$hostname','$uid','$mac','0','$VIDEOID')";
				$q->QUERY_SQL($sql);
				if(!$q->ok){$this->db_error($tableYoutube,$sql);}
			}
		}			
		
		
		$timekey=date('YmdH',$xtime);
		$stime=date("Y-m-d H:i:s",$xtime);
		$table="quotatemp_$timekey";
		$keyr2=md5("$stime$date$uid$ipaddr$mac$sitename");
		$GLOBALS["TABLES_PRIMAIRES_QUOTATEMP"][$table][]="('$stime','$keyr2','$ipaddr','$familysite','$familysite','$uid','$mac','$SIZE')";
		
	}
	private function FileSystemUserAgent($UserAgent){
		if(trim($UserAgent)==null){return;}
		$UserAgent_md=md5($UserAgent);
		if(isset($GLOBALS[$UserAgent])){return;}
		$GLOBALS[$UserAgent]=true;
		
	
	}
	
	
	private function ComputersAdd($ipaddr,$mac){
		if($mac==null){return;}
		$GLOBALS["COMPS"][$mac]=$ipaddr;
		
	}
	
	
	private function ComputersClean(){
		if(!isset($GLOBALS["COMPS"])){return;}
		if(count($GLOBALS["COMPS"])==0){return;}
		$ocs=new ocs();
		while (list ($mac, $ipaddr) = each ($GLOBALS["COMPS"]) ){
			$ocs->ADD_HARDWARE($ipaddr, $mac);
		}
		
	}
	
	
	
	private function FileSystemUserAgent_empty(){
		if(!isset($GLOBALS["UserAgents"])){return;}
		if(count($GLOBALS["UserAgents"])==0){return;}
		while (list ($UserAgent, $sql) = each ($GLOBALS["UserAgents"]) ){
			$UserAgent=$this->mysql_escape_string2($UserAgent);
			$f[]="('$UserAgent')";
		}
	
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("INSERT IGNORE INTO `UserAgents` (`pattern`) VALUES ".@implode(",", $f));
		$GLOBALS["UserAgents"]=array();
	
	}
	
	private function berekley_db_create($db_path){
		if(is_file($db_path)){return true;}
		$db_desttmp = @dba_open($db_path, "c","db4");
		$this->events("berekley_db_create:: Creating $db_path database Close");
		@dba_close($db_desttmp);
		$this->events("berekley_db_create:: Creating $db_path database done");
		if(!is_file($db_path)){return false;}	
		return true;
	
	}
	
	
	private function berekley_proto_get($xtime,$keymd5){
		$date=date("Ymd",$xtime);
		$db_path="{$GLOBALS["LogFileDeamonLogDir"]}/".date("Ymd",$xtime)."_proto.db";
		
		if(isset($GLOBALS["berekley_proto"][$date][$keymd5])){
			return unserialize($GLOBALS["berekley_proto"][$date][$keymd5]);
		}
		
		return $this->berekley_array_get($keymd5, $db_path);
		
		
	}
	
	
	private function berekley_proto_clean(){
		
		if(!isset($GLOBALS["berekley_proto"])){return;}
		if(count($GLOBALS["berekley_proto"])==0){return;}
		while (list ($datesuffix, $array) = each ($GLOBALS["berekley_proto"]) ){
			$DatabasePath="{$GLOBALS["LogFileDeamonLogDir"]}/{$datesuffix}_proto.db";
			$this->berekley_db_create($DatabasePath);
			if(!is_file($DatabasePath)){continue;}
			$this->berekley_array_set($DatabasePath,$array);
		}
	
	
	}
	
	
	private function berekley_proto($proto,$familysite,$SIZE,$cached,$xtime){
		$date=date("Ymd",$xtime);
		$db_path="{$GLOBALS["LogFileDeamonLogDir"]}/".date("Ymd",$xtime)."_proto.db";
		

		$keymd5=$familysite;
		
		$array=$this->berekley_proto_get($xtime,$keymd5);
	
		if(count($array)==0){
			$array[$proto]["SIZE"]=$SIZE;
			$array[$proto]["HIT"]=1;
			$GLOBALS["berekley_proto"][$date][$keymd5]=serialize($array);
			return;
		}
	
	
		
		$array[$proto]["SIZE"]=intval($array[$proto]["SIZE"])+$SIZE;
		$array[$proto]["HIT"]=intval($array[$proto]["HIT"])+1;
		$GLOBALS["berekley_proto"][$date][$keymd5]=serialize($array);
		
	}	
	
	
	private function berekley_array_get($keymd5,$DatabasePath){
		if(!is_file($DatabasePath)){return array();}
		
		$db_con = @dba_open($DatabasePath, "r","db4");
		if(!@dba_exists($keymd5,$db_con)){
			@dba_close($db_con);
			return array();
		}
		
		
		$data=dba_fetch($keymd5,$db_con);
		@dba_close($db_con);
		return unserialize($data);
	}
	
	private function berekley_array_set($DatabasePath,$array){
		if(count($array)==0){
			events(__FUNCTION__."::".__LINE__." $DatabasePath 0 event");
			return;
		}
		$this->berekley_db_create($DatabasePath);
		if(!is_file($DatabasePath)){return;}
		$db_con = @dba_open($DatabasePath, "w","db4");
		
			
		$c=0;
		foreach ($array as $key=>$value){
			$c++;
			dba_replace($key,$value,$db_con);
			
		}
		events(__FUNCTION__."::".__LINE__." $DatabasePath $c events");
		@dba_close($db_con);
		
	}
		
	
	private function berekley_db_size_clean(){
		if(!isset($GLOBALS["berekley_db_size"])){return;}
		$dateW=date("YW");
		$DatabasePath="/var/log/squid/{$dateW}_QUOTASIZE.db";
		$this->berekley_db_create($DatabasePath);
		if(!is_file($DatabasePath)){return;}
		$this->berekley_array_set($DatabasePath,$GLOBALS["berekley_db_size"]);
		
	}
	
	private function berekley_db_size_get($keymd5,$xtime){
		$dateW=date("YW",$xtime);
		$DatabasePath="/var/log/squid/{$dateW}_QUOTASIZE.db";
		if(isset($GLOBALS["berekley_db_size"][$keymd5])){return unserialize($GLOBALS["berekley_db_size"][$keymd5]);}
		$GLOBALS["berekley_db_size"][$keymd5]=$this->berekley_array_get($keymd5,$DatabasePath);
		
		
	}
	
	
	private function berekley_db_size($familysite,$mac,$ipaddr,$uid,$SIZE,$category=null,$cached,$MimeType,$xtime){
		if($SIZE==0){return;}
		if(trim($familysite)==null){return;}
		if($GLOBALS["DEBUG_CACHES"]){
			events("$familysite: Mime: $MimeType cached=$cached");
		}
	
		
		$this->berekley_db_access($familysite,$mac,$ipaddr,$uid,$SIZE,$category,$cached,$MimeType,$xtime);
		$this->berekly_db_mime($mac,$ipaddr,$uid,$SIZE,$MimeType,$xtime);
		// Cached or not.
		if($cached==1){$this->berekley_db_cached_www($familysite,$SIZE,$xtime);}
	
		if($cached==0){$this->berekley_db_notcached_www($familysite,$SIZE,$xtime);}
		
		$dateW=date("YW",$xtime);
		$DatabasePath="/var/log/squid/{$dateW}_QUOTASIZE.db";
		
		$Fetched=true;
		if($ipaddr<>null){$keymd5=md5("$familysite$ipaddr");}
		if($mac<>null){$keymd5=md5("$familysite$mac"); }
		if($uid<>null){$keymd5=md5("$familysite$uid"); }
	
		$array=$this->berekley_db_size_get($keymd5,$xtime);
	
		if(count($array)==0){
			
			$array["DAILY"][date("d")]=$SIZE;
			$array["HOURLY"][date("d")][date("H")]=$SIZE;
			$array["WEEKLY"]=$SIZE;
			$array["IPADDR"]=$ipaddr;
			$array["MAC"]=$mac;
			$array["UID"]=$uid;
			$array["WWW"]=$familysite;
			$array["category"]=$category;
			$GLOBALS["berekley_db_size"][$keymd5]=serialize($array);
			return;
		}
	
	
		
	
		if(!isset($array["HOURLY"][date("d")][date("H")])){
			$array["HOURLY"][date("d")][date("H")]=$SIZE;
		}else{
			$array["HOURLY"][date("d")][date("H")]=intval($array["HOURLY"][date("d")][date("H")])+$SIZE;
		}
	
	
		if(!isset($array["WEEKLY"])){
			$array["WEEKLY"]=$array["DAILY"][date("d")];
		}else{
			$array["WEEKLY"]=intval($array["WEEKLY"])+$SIZE;
		}
	
	
		if(!isset($array["DAILY"][date("d")])){
			$array["DAILY"][date("d")]=$SIZE;
		}else{
			$array["DAILY"][date("d")]=intval($array["DAILY"][date("d")])+$SIZE;
		}
	
		$array["category"]=$category;
		$GLOBALS["berekley_db_size"][$keymd5]=serialize($array);
	}
	
	private function berekley_db_access_clean(){
		if(!isset($GLOBALS["berekley_db_access"])){return;}
		events("berekley_db_access_clean added ".count($GLOBALS["berekley_db_access"])." rows");
		if(count($GLOBALS["berekley_db_access"])==0){return;}
		
		$c=0;
		while (list ($xdate, $array) = each ($GLOBALS["berekley_db_access"]) ){
			$db_path="{$GLOBALS["LogFileDeamonLogDir"]}/{$xdate}_dbaccess.db";
			$this->berekley_array_set($db_path, $array);
			$c++;
		}
		events("berekley_db_access_clean added $c events...");
		$GLOBALS["berekley_db_access"]=array();
		
	}
	
	
	private function berekley_db_access_exists($keymd5,$xtime){
		$TimeKey=date("YmdH",$xtime);
		
		
		if(isset($GLOBALS["berekley_db_access"][$TimeKey][$keymd5])){
			return unserialize($GLOBALS["berekley_db_access"][$TimeKey][$keymd5]);
		}
		
		
		
		$db_path="{$GLOBALS["LogFileDeamonLogDir"]}/{$TimeKey}_dbaccess.db";
		if(!is_file($db_path)){return array();}
		
		
		$db_con = @dba_open($db_path, "r","db4");
		if(!$db_con){events(__FUNCTION__."::".__LINE__." FATAL!!!::$db_path, unable to open"); return array(); }
		
		if(!@dba_exists($keymd5,$db_con)){
			@dba_close($db_con);
			if($GLOBALS["VERBOSE"]){echo "berekley_db_access_exists:: $keymd5 NONE\n";}
			return array();
		}
		
		
		if($GLOBALS["VERBOSE"]){echo "berekley_db_access_exists:: $keymd5 OK\n";}
		$dba_fetch=dba_fetch($keymd5,$db_con);
		@dba_close($db_con);
		
		$GLOBALS["berekley_db_access"][$TimeKey][$keymd5]=$dba_fetch;
		$array=unserialize($dba_fetch);
		return $array;
		
	}
	
	
	
	
	

	private function berekley_db_access($familysite,$mac,$ipaddr,$uid,$SIZE,$category=null,$cached,$MimeType,$xtime){
	
		if($ipaddr<>null){$keymd5=md5("$familysite$ipaddr");}
		if($mac<>null){$keymd5=md5("$familysite$mac"); }
		if($uid<>null){$keymd5=md5("$familysite$uid"); }
		$TimeKey=date("YmdH",$xtime);
		
		$array=$this->berekley_db_access_exists($keymd5,$xtime);
		if(count($array)==0){
			$array["IPADDR"]=$ipaddr;
			$array["MAC"]=$mac;
			$array["UID"]=$uid;
			$array["WWW"]=$familysite;
			$array["category"]=$category;
			$array["HITS"]=1;
			$array["SIZE"]=$SIZE;
			$GLOBALS["berekley_db_access"][$TimeKey][$keymd5]=serialize($array);
			return;
		}
		
		
		
		$SIZEA=intval($array["SIZE"]);
		$HITSA=intval($array["HITS"])+1;
		$array["SIZE"]=$SIZEA+$SIZE;
		
		
		$sizemb=$array["SIZE"];
		$sizemb=$sizemb/1024;
		$sizemb=round($sizemb/1024,2);
		
		
		$array["HITS"]=$HITSA;
		$array["IPADDR"]=$ipaddr;
		$array["MAC"]=$mac;
		$array["UID"]=$uid;
		$array["WWW"]=$familysite;
		$array["category"]=$category;
		$GLOBALS["berekley_db_access"][$TimeKey][$keymd5]=serialize($array);
		
	}
	

	

	
	


private function berekley_db_cached_www_clean(){
	if(!isset($GLOBALS["berekley_db_cached_www"])){return;}
	$dateW=date("YW");
	$DatabasePath="/var/log/squid/".date("YW")."_wwwcached.db";
	$this->berekley_array_set($DatabasePath, $GLOBALS["berekley_db_cached_www"]);
}


private function berekley_db_cached_www_exists($familysite,$xtime){
	
	$DatabasePath="/var/log/squid/".date("YW",$xtime)."_wwwcached.db";
	
	if(isset($GLOBALS["berekley_db_cached_www"][$familysite])){
		return $GLOBALS["berekley_db_cached_www"][$familysite];
	}
	$GLOBALS["berekley_db_cached_www"][$familysite]=0;
	if(!is_file($DatabasePath)){return;}
	$db_con = @dba_open($DatabasePath, "r","db4");
	
	
	$GLOBALS["berekley_db_cached_www"][$familysite]=intval(@dba_fetch($familysite,$db_con));
	@dba_close($db_con);
	
}

private function berekley_db_cached_www($familysite,$SIZE,$xtime){
		$DatabasePath="/var/log/squid/".date("YW",$xtime)."_wwwcached.db";
		$this->berekley_db_cached_www_exists($familysite,$xtime);
		$GLOBALS["berekley_db_cached_www"][$familysite]=$GLOBALS["berekley_db_cached_www"][$familysite]+$SIZE;
		
	
	}


	

	


private function db_error($tablename,$sql,$error){
	$DatabasePath="{$GLOBALS["LogFileDeamonLogDir"]}/mysql_errors.db";
	
	events("MySQL error $error on $tablename");
	
	$this->berekley_db_create($DatabasePath);
	$db_con = @dba_open($DatabasePath, "c","db4");
	if(!@dba_exists($tablename,$db_con)){
		$array[]=$sql;
		dba_replace($tablename,serialize($array),$db_con);
		@dba_close($db_con);
		return;
	}
	$dba_fetch=dba_fetch($tablename,$db_con);
	$array=unserialize($dba_fetch);
	$array[]=$sql;
	dba_replace($tablename,serialize($array),$db_con);
	@dba_close($db_con);
}

private function berekley_db_notcached_www_query($familysite,$xtime){
	$DatabasePath="/var/log/squid/".date("YW",$xtime)."_NOTCACHED_WEEK.db";
	
	if(isset($GLOBALS["berekley_db_notcached_www"][$familysite])){
		return unserialize($GLOBALS["berekley_db_notcached_www"][$familysite]);
	}
	
	return $this->berekley_array_get($familysite, $DatabasePath);
	
}

private function berekley_db_notcached_www_clean(){
	if(!isset($GLOBALS["berekley_db_notcached_www"])){return;}
	$DatabasePath="/var/log/squid/".date("YW")."_NOTCACHED_WEEK.db";
	$this->berekley_array_set($DatabasePath, $GLOBALS["berekley_db_notcached_www"]);
}


private function berekley_db_notcached_www($familysite,$SIZE,$xtime){
	$familysite=trim($familysite);
	if($familysite==null){return;}
	
	
	$array=$this->berekley_db_notcached_www_query($familysite,$xtime);
	
	if(count($array)==0){
		$array["SIZE"]=$SIZE;
		$array["HIT"]=1;
		$GLOBALS["berekley_db_notcached_www"][$familysite]=serialize($array);
		return;
	}
	
	$array["SIZE"]=intval($array["SIZE"])+intval($SIZE);
	$array["HIT"]=intval($array["HIT"])+1;
	$GLOBALS["berekley_db_notcached_www"][$familysite]=serialize($array);
}

private function berekly_db_mime_query($keymd5,$xtime){
	$date=date("Ymd",$xtime);
	
	if(isset($GLOBALS["berekly_db_mime_query"][$date][$keymd5])){
			return unserialize($GLOBALS["berekly_db_mime_query"][$date][$keymd5]);
	}
	
	$database="/var/log/squid/{$date}_mime.db";
	if(!is_file($database)){return array();}
	
	$db_con = @dba_open($database, "r","db4");
	if(!$db_con){
		events(__FUNCTION__."::".__LINE__." FATAL!!!::$database, unable to open");
		return array();
	}
	
	if(!@dba_exists($keymd5,$db_con)){return array();}
	$data=dba_fetch($keymd5,$db_con);
	return unserialize($data);
	
	
}

private function berekly_db_mime_clean(){

	if(!isset($GLOBALS["berekly_db_mime_query"])){return;}
	if(count($GLOBALS["berekly_db_mime_query"])==0){return;}
	
	while (list ($xdate, $array) = each ($GLOBALS["berekly_db_mime_query"]) ){
		$db_path="{$GLOBALS["LogFileDeamonLogDir"]}/{$xdate}_mime.db";
		if(!$this->berekley_db_create($db_path)){continue;}
		$db_con = @dba_open($db_path, "c","db4");
		if(!$db_con){
			events(__FUNCTION__."::".__LINE__." FATAL!!!::$db_path, unable to open");
			@dba_close($db_con);
			continue;
		}
			
		
		while (list ($keymd5, $content) = each ($array) ){
			dba_replace($keymd5,$content,$db_con);
	
		}
			
		@dba_close($db_con);
			
	}
	
	$GLOBALS["berekly_db_mime_query"]=array();
	
	
}

private function berekly_db_mime($mac,$ipaddr,$uid,$SIZE,$mime,$xtime){

	$date=date("Ymd",$xtime);
	
	if($ipaddr<>null){$keymd5=md5("$ipaddr");}
	if($mac<>null){$keymd5=md5("$mac"); }
	if($uid<>null){$keymd5=md5("$uid"); }
	

	
	$array=$this->berekly_db_mime_query($keymd5,$xtime);

	if(count($array)==0){
		$array[$mime]["SIZE"]=$SIZE;
		$array[$mime]["MAC"]=$mac;
		$array[$mime]["UID"]=$uid;
		$array[$mime]["IPADDR"]=$ipaddr;
		$GLOBALS["berekly_db_mime_query"][$date][$keymd5]=serialize($array);
		return;
	}


	
	if(!isset($array[$mime]["SIZE"])){$array[$mime]["SIZE"]=0;}
	$array[$mime]["SIZE"]=$array[$mime]["SIZE"]+$SIZE;
	$array[$mime]["MAC"]=$mac;
	$array[$mime]["UID"]=$uid;
	$array[$mime]["IPADDR"]=$ipaddr;
	$GLOBALS["berekly_db_mime_query"][$date][$keymd5]=serialize($array);

}

	
}