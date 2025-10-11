<?php
include_once(dirname(__FILE__)."/class.mysql.blackboxes.inc");
include_once(dirname(__FILE__)."/class.ccurl.inc");
include_once(dirname(__FILE__)."/class.nodes.squid.inc");

class blackboxes{
	
	public $nodeid=0;
	public $hostname=null;
	public $ipaddress;
	public $port=9001;
	public $last_error;
	public $settings_inc=array();
	public $laststatus=null;
	public $VERSION=null;
	public $etcsquidconf=null;
	public $squid_version;
	public $Architecture=0;
	public $IsArtica=0;
	public $hostid=null;
	public $ssl=1;
	public $ssluri=null;
	public $sslnouri=null;
	public $TotalMemoryMB=0;
	public $SquidSMPStatus=null;
	public $BoosterSMPStatus=null;
	public $SquidSystemInfo=null;
	
	function __construct($nodeid=0){
		if(!is_numeric($nodeid)){
			if(strlen($nodeid)>0){
				$this->hostid=$nodeid;
				$this->load();return;
			}
		}
		$this->nodeid=$nodeid;
		if(!is_numeric($this->nodeid)){$this->nodeid=0;return;}
		if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
		if($this->nodeid>0){$this->load();}
	}
	
	
	private function load(){
		$q=new mysql_blackbox();
		$sql="SELECT * FROM nodes WHERE nodeid=$this->nodeid";
		if(strlen($this->hostid)>5){$sql="SELECT * FROM nodes WHERE hostid='$this->hostid'";}
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){
			if(preg_match("#doesn't exist#",$q->mysql_error)){
				$q->CheckTables();
				$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
			}
			if(!$q->ok){echo "** FATAL *** $q->mysql_error\n";}
		}
		$this->hostname=$ligne["hostname"];
		$this->ipaddress=$ligne["ipaddress"];
		$this->port=$ligne["port"];
		$this->settings_inc=unserialize(base64_decode($ligne["settingsinc"]));
		
		if($ligne["squidver"]<>null){
			$this->settings_inc["SQUID_INSTALLED"]=true;
		}
		
		
		$this->laststatus=distanceOfTimeInWords(time(),strtotime($ligne["laststatus"]));
		$this->VERSION=$ligne["articaver"];
		$this->etcsquidconf=base64_decode($ligne["squidconf"]);
		$this->squid_version=$ligne["squidver"];
		$this->Architecture=$ligne["arch"];
		$this->SquidSMPStatus=@implode("\n",unserialize(base64_decode($ligne["SquidSMPStatus"])));
		$this->BoosterSMPStatus=$ligne["BoosterSMPStatus"];
		$this->SquidSystemInfo=$ligne["SquidSystemInfo"];
		$this->IsArtica=$ligne["BigArtica"];
		if($ligne["hostid"]<>null){
			$this->hostid=$ligne["hostid"];
		}
		$this->nodeid=$ligne["nodeid"];
		$this->TotalMemoryMB=$ligne["TotalMemoryMB"];
		if(!isset($ligne["ssl"])){$ligne["ssl"]=1;}
		$this->ssl=$ligne["ssl"];
		if(!$q->TABLE_EXISTS("pms$this->nodeid")){$q->build_cached_items_table($this->nodeid);}
		if(!is_numeric($this->port)){$this->port=9000;}
		if(!is_numeric($this->TotalMemoryMB)){$this->TotalMemoryMB=0;}
		if($this->port==0){$this->port=9000;}
		$this->ssluri="https://$this->ipaddress:$this->port";
		$this->sslnouri="http://$this->ipaddress:$this->port";
	}
	
	public function NotifyAll($order){
	
		$q=new mysql_blackbox();
		$sql="SELECT nodeid FROM nodes";
		$results = $q->QUERY_SQL($sql);
		if($GLOBALS["AS_ROOT"]){$GLOBALS["CLASS_UNIX"]=new unix();}
		if(isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]->events("$sql ->".mysqli_num_rows($results)." items","/var/log/stats-appliance.log");}
		while ($ligne = mysqli_fetch_assoc($results)) {
			$bb=new blackboxes($ligne["nodeid"]);
			if(isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]->events("id:{$ligne["nodeid"]} ->GenericOrder($order)","/var/log/stats-appliance.log");}
			$bb->GenericOrder($order);
		}
	}
	
	public function chock(){
		$parms["REGISTER"]="yes";
		return $this->SendOrder($parms);
	}
	
	public function GenericOrder($order){
		$parms[$order]="yes";
		return $this->SendOrder($parms);		
		
	}	
	
	public function reboot(){
		$parms["REBOOT"]="yes";
		return $this->SendOrder($parms);		
		
	}
	
	public function updateorder(){
		$parms["UPDATE_AGENT"]="yes";
		return $this->SendOrder($parms);		
		
	}	
	
	public function updatesquid(){
		$parms["UPDATE_SQUID"]="yes";
		return $this->SendOrder($parms);			
	}
	
	public function restart_squid(){
		$parms["RESTART_SQUID"]="yes";
		_udfbguard_admin_events("Ask to create new order `RESTART_SQUID` for $this->hostid ($this->hostname)", __FUNCTION__, __FILE__, __LINE__, "communicate");
		return $this->SendOrder($parms);			
	}

	public function reconfigure_squid(){
		$sock=new sockets();
		$sock->getFrameWork("services.php?nodes-export-tables=yes");
		$parms["RECONFIGURE_SQUID"]="yes";
		return $this->SendOrder($parms);			
	}	
	
	public function REBUILD_CACHE(){
		$parms["REBUILD_CACHE"]="yes";
		return $this->SendOrder($parms);			
	}	

	public function RECONSTRUCT_CACHE(){
		$parms["RECONSTRUCT_CACHE"]="yes";
		return $this->SendOrder($parms);			
	}	
	

	
	public function GET_SQUID_INFO($key){
		if(isset($GLOBALS["GET_SQUID_INFO_ARRAY"])){
			$array=$GLOBALS["GET_SQUID_INFO_ARRAY"];
			if(!isset($array[$key])){return $this->GET_SQUID_INFO_DISK($key);}
			if(trim($array[$key])==null){return $this->GET_SQUID_INFO_DISK($key);}
			return $array[$key];			
		}
		$q=new mysql();
		if(!$q->TABLE_EXISTS("squid_nodes_settings", "artica_backup")){$q->CheckTablesSquid();}
		$sql="SELECT `content` FROM squid_nodes_settings WHERE uuid='$this->hostid'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [SMP] content=". strlen($ligne["content"])." bytes for $this->hostid\n";}
		$array=unserialize(base64_decode($ligne["content"]));
        if(!is_array($array)){$array=array();}
		if(!is_array($array)){return $this->GET_SQUID_INFO_DISK($key);}
		$GLOBALS["GET_SQUID_INFO_ARRAY"]=$array;
		if(!isset($array[$key])){return $this->GET_SQUID_INFO_DISK($key);}
		if(trim($array[$key])==null){return $this->GET_SQUID_INFO_DISK($key);}
		return $array[$key];		
	}
	
	private function GET_SQUID_INFO_DISK($key){
		$sock=new sockets();
		return $sock->GET_INFO($key);
		
	}
	
	public function SET_SQUID_POST_INFO($arrayToSave){
		$q=new mysql();
		$sql="SELECT `content` FROM squid_nodes_settings WHERE uuid='$this->hostid'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$array=unserialize(base64_decode($ligne["content"]));
		while (list ($key, $val) = each ($arrayToSave) ){
			$array[$key]=$val;
		}
		
		
		$sql="DELETE FROM squid_nodes_settings WHERE uuid='$this->hostid'";
		$q->QUERY_SQL($sql,"artica_backup");
		$newdata=base64_encode(serialize($array));
		$sql="INSERT INTO squid_nodes_settings (`uuid`,`content`) VALUES ('$this->hostid','$newdata')";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
	}	
	
	public function SET_SQUID_INFO($key,$value){
		$q=new mysql();
		$sql="SELECT `content` FROM squid_nodes_settings WHERE uuid='$this->hostid'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$array=unserialize(base64_decode($ligne["content"]));
		$array[$key]=$value;
		$sql="DELETE FROM squid_nodes_settings WHERE uuid='$this->hostid'";
		$q->QUERY_SQL($sql,"artica_backup");
		$newdata=base64_encode(serialize($array));	
		$sql="INSERT INTO squid_nodes_settings (uuid,`content`) VALUES ('$this->hostid','$newdata')";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	
	private function SendOrder($parms){
		
		if($GLOBALS["AS_ROOT"]){$unix=new unix();}
		
		if(isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]->events("SendOrder($parms) Port:$this->port, Artica=$this->IsArtica","/var/log/stats-appliance.log");}
		
		if(($this->port<5) OR ($this->IsArtica==1)){
			if(isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]->events("this->OrderQueue()","/var/log/stats-appliance.log");}
			return $this->OrderQueue($parms);
			
		}		
		$sock=new sockets();
		$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
		$ArticaHttpUseSSL=$sock->GET_INFO("ArticaHttpUseSSL");
		if(!is_numeric($ArticaHttpUseSSL)){$ArticaHttpUseSSL=1;}
		if(!is_numeric($ArticaHttpsPort)){$ArticaHttpsPort="9000";}
			
		$curl=new ccurl("http://$this->ipaddress:$this->port/listener.php");		
		$curl->parms["SSL"]=$ArticaHttpUseSSL;
		$curl->parms["PORT"]=$ArticaHttpsPort;
		$curl->parms["NODE_ID"]=$this->nodeid;	
		while (list ($key, $arr) = each ($parms) ){
			writelogs("Asking $key  node:http://$this->ipaddress:$this->port",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$curl->parms[$key]=$arr;
		}
		if($GLOBALS["AS_ROOT"]){
			$unix->events("Sends to http://$this->ipaddress:$this->port","/var/log/stats-appliance.log");
		}
		
		if(!$curl->get()){
			if($GLOBALS["AS_ROOT"]){$unix->events("{$curl->error} http://$this->ipaddress:$this->port","/var/log/stats-appliance.log");}			
			$this->last_error="{$curl->error} http://$this->ipaddress:$this->port";return;}
		if(!preg_match("#<SUCCESS>#is", $curl->data)){
			if($GLOBALS["AS_ROOT"]){$unix->events("{failed} `http://$this->ipaddress:$this->port`","/var/log/stats-appliance.log");}
			$this->last_error="{failed} `http://$this->ipaddress:$this->port`";
			return;
		}	
		return true;			
		
	}
	
	private function WriteToSyslog($text){
		if(!function_exists("syslog")){return;}
		$LOG_SEV=LOG_INFO;
		openlog("stats-appliance", LOG_PID , LOG_SYSLOG);
		syslog($LOG_SEV, $text);
		closelog();		
		
	}
	
	private function OrderQueue($parms){
			$order=base64_encode(serialize($parms));
			$orderid=md5($order.time().$this->hostid.$this->nodeid);
			while (list ($a, $b) = each ($parms) ){$orr[]="$a=$b";}
			reset($parms);
			$this->WriteToSyslog("Create new order for $this->hostid[$this->nodeid] " .@implode(" ", $orr));
			
			$sql="INSERT IGNORE INTO `poolorders` (`hostid`,`nodeid`,`orderid`,`orderdata`) VALUES ('$this->hostid','$this->nodeid','$orderid','$order')";		
			$q=new mysql_blackbox();
			$this->WriteToSyslog($sql);
			$q->QUERY_SQL($sql);
			
			if(!$q->ok){
				echo $q->mysql_error;
				$this->WriteToSyslog($sql);
			_udfbguard_admin_events("Creating new order for $this->hostid ($this->hostname) failed\n$q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "communicate");
			return false;}
			
			if(isset($GLOBALS["CLASS_UNIX"])){
				$php=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();	
				$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
				$cmd="$php /usr/share/artica-postfix/exec.blackbox.php --ping $this->hostid >/dev/null 2>&1";
				$GLOBALS["CLASS_UNIX"]->events("$cmd","/var/log/stats-appliance.log");
				return;
			}
			
			$sock=new sockets();
			$sock->getFrameWork("services.php?blackbox-notify=$this->hostid");
			_udfbguard_admin_events("Creating new order for $this->hostid ($this->hostname)", __FUNCTION__, __FILE__, __LINE__, "communicate");
			return true;
	}
	
	
	public function SaveSettingsInc($EncodedDatas,$PerfsEncoded,$softsEncoded,$prodstatusEncoded,$IsArtica){
		if($this->nodeid<1){return;}
		if(!is_numeric($IsArtica)){$IsArtica=0;}
		$q=new mysql_blackbox();
		
		$sql="SELECT nodeid FROM nodes WHERE nodeid=$this->nodeid";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		if(!is_numeric($ligne["nodeid"])){$ligne["nodeid"]=0;}
		if($ligne["nodeid"]==0){
			$q->QUERY_SQL("INSERT INTO nodes (nodeid,hostname,ipaddress,port,BigArtica) 
			VALUES ($this->nodeid,'$this->hostname','{$_SERVER["REMOTE_ADDR"]}','9000',$IsArtica)");
		}
		
		
		$sql="UPDATE nodes SET settingsinc='$EncodedDatas',
		`perfs`='$PerfsEncoded',
		`prodstatus`='$prodstatusEncoded',
		`articaver`='$this->VERSION',
		`BigArtica`=$IsArtica,
		laststatus=NOW() WHERE nodeid=$this->nodeid";
		
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			if(preg_match("#Error Unknown column#", $q->mysql_error)){$q->CheckTables();$q->QUERY_SQL($sql);}
			if(!$q->ok){
				writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
		}
		
		$this->ParseSofts($softsEncoded);
		$this->ParseStatus($prodstatusEncoded);
		
	}
	
	public function NotifySquid(){
		return $this->NotifyNode("SQUID");
	}
	
	public function NotifyNode($ORDER){
		$parms[$ORDER]="yes";
		return $this->SendOrder($parms);	
			
	}	
	

	private function ParseStatus($prodstatusEncoded){
		$inidata=base64_decode($prodstatusEncoded);
		$q=new mysql_blackbox();
		if(!$q->TABLE_EXISTS("nodesstatus")){$q->CheckTables();}
		$ini=new Bs_IniHandler();
		$ini->loadString($inidata);

		$prefix="INSERT IGNORE INTO nodesstatus (
		`nodeid`,
		`service_name`,`master_version`,`master_pid`,`master_memory`,`master_cached_memory`,
		`processes_number`,`running`,`installed`) VALUES ";
		
		while (list ($key, $arr) = each ($ini->_params) ){
			$ttlogs[]=$key;
			if(!isset($arr["master_version"])){$arr["master_version"]=null;}
			if(!isset($arr["master_pid"])){$arr["master_pid"]=0;}
			if(!isset($arr["master_memory"])){$arr["master_memory"]=0;}
			if(!isset($arr["master_cached_memory"])){$arr["master_cached_memory"]=0;}
			if(!isset($arr["processes_number"])){$arr["processes_number"]=0;}
			if(!isset($arr["running"])){$arr["running"]=0;}
			if(!isset($arr["installed"])){$arr["installed"]=0;}
			
			$f[]="('$this->nodeid',
			'{$arr["service_name"]}',
			'{$arr["master_version"]}','{$arr["master_pid"]}','{$arr["master_memory"]}','{$arr["master_cached_memory"]}',
			'{$arr["processes_number"]}','{$arr["running"]}','{$arr["installed"]}')";
			
		}
		writelogs(@implode(",", $ttlogs),__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		if(count($f)==0){return;}
		$q->QUERY_SQL("DELETE FROM nodesstatus WHERE nodeid='$this->nodeid'");	
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		
	}
	
	
	public function SaveNets($EncodedDatas){
		$array=unserialize(base64_decode($EncodedDatas));
		if(!is_array($array)){writelogs("blackboxes::Not an array... ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;}
		if(count($array)==0){writelogs("blackboxes::No item... ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;}
		$f=array();
		$q=new mysql_blackbox();
		while (list ($eth, $arr) = each ($array) ){
			$arr["MACADDR"]=strtolower($arr["MACADDR"]);
			$arr["MACADDR"]=str_replace("-",":",$arr["MACADDR"]);
			if($arr["MACADDR"]=="00:00:00:00:00:00"){$arr["MACADDR"]=$arr["MACADDR"]."-$this->nodeid";}
			writelogs("blackboxes:: {$arr["MACADDR"]}",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL("DELETE FROM nics WHERE `mac`='{$arr["MACADDR"]}'");
			$f[]="('$this->nodeid','$eth','{$arr["IPADDR"]}','{$arr["MACADDR"]}','{$arr["NETMASK"]}','{$arr["GATEWAY"]}','{$arr["BROADCAST"]}')";	
			
		}
		
		writelogs("blackboxes::".count($f)." items ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		if(count($f)>0){
			
			$q->QUERY_SQL("DELETE FROM nics WHERE nodeid='$this->nodeid'");	
			if(!$q->ok){writelogs("blackboxes::$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			$prefix="INSERT IGNORE INTO nics (nodeid,nic,ipaddr,mac,netmask,gateway,broadcast) VALUES ".@implode(",", $f);
			$q->QUERY_SQL($prefix);
			if(!$q->ok){writelogs("blackboxes::$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			
		}
	}
	
	public function SaveDisks($EncodedDatas){
		$size=0;
		$array=unserialize(base64_decode($EncodedDatas));
		if(!is_array($array["DISKS"])){writelogs("Not an array... ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;}
		if(count($array["DISKS"])==0){writelogs("No item... ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;}		
		$q=new mysql_blackbox();
		if(!$q->TABLE_EXISTS("harddrives")){$q->CheckTables();}
		if(!$q->TABLE_EXISTS("harddrives")){writelogs("No harddrives table... ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;}
		$DISK=$array["DISKS"];
		$prefix="INSERT IGNORE INTO harddrives (`nodeid` ,`path`,`size`,`ID_FS_LABEL`,`ID_MODEL_1`,`ID_MODEL_2`,`ID_VENDOR`,`PARTITIONS`) VALUES ";
		$f=array();
		while (list ($path, $infos) = each ($DISK)){
			$PARTITIONS=base64_encode(serialize($infos["PARTITIONS"]));
			if(preg_match("#([0-9]+)\s+MB#", $infos["SIZE"],$re)){$size=$re[1];}
			writelogs("$this->nodeid:$path:($size -> `{$infos["SIZE"]}`)",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$f[]="($this->nodeid,'$path','$size','{$infos["ID_FS_LABEL"]}','{$infos["ID_MODEL_1"]}','{$infos["ID_MODEL_2"]}','{$infos["ID_VENDOR"]}','$PARTITIONS')";
		}
		
		if(count($f)==0){return;}
		$q->QUERY_SQL("DELETE FROM harddrives WHERE `nodeid`=$this->nodeid");
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		
		
	}
	
	public function squid_save_system_infos($EncodedDatas){
		$q=new mysql_blackbox();
		if(!$q->FIELD_EXISTS("nodes","SquidSystemInfo")){$sql="ALTER TABLE `nodes` ADD `SquidSystemInfo` TEXT NOT NULL";$q->QUERY_SQL($sql);}
		$sql="UPDATE nodes SET SquidSystemInfo='$EncodedDatas' WHERE hostid='$this->hostid'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}		
		
	}
	
	
	public function squid_save_cache_infos($EncodedDatas){
		
	}
	
	public function squid_ufdbclientlog($EncodedDatas){
		$sql="UPDATE `nodes` SET UfdbClientLogs='$EncodedDatas' WHERE hostid='$this->hostid'";
		$q=new mysql_blackbox();
		$q->QUERY_SQL($sql);
		if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		if($GLOBALS["VERBOSE"]){echo "squid_ufdbclientlog::$this->hostid:: ".strlen($EncodedDatas)." bytes done..\n$q->mysql_error\n";}
		
	}
	
	public function system_update_memory($TotalMemoryMB=0){
		$q=new mysql_blackbox();
		if(!$q->FIELD_EXISTS("nodes","TotalMemoryMB")){$sql="ALTER TABLE `nodes` ADD `TotalMemoryMB` INT UNSIGNED NOT NULL,ADD INDEX ( `TotalMemoryMB` )";$q->QUERY_SQL($sql);}
		$sql="UPDATE nodes SET TotalMemoryMB='$TotalMemoryMB' WHERE hostid='$this->hostid'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}		
		
	}
	
	public function system_update_smtpstatus($EncodedDatas){
		$q=new mysql_blackbox();
		if(!$q->FIELD_EXISTS("nodes","SquidSMPStatus")){$sql="ALTER TABLE `nodes` ADD `SquidSMPStatus` TEXT";$q->QUERY_SQL($sql);}
		$sql="UPDATE nodes SET SquidSMPStatus='$EncodedDatas' WHERE hostid='$this->hostid'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}		
	}
	
	public function system_update_boostersmp($EncodedDatas){
		$q=new mysql_blackbox();
		if(!$q->FIELD_EXISTS("nodes","BoosterSMPStatus")){$sql="ALTER TABLE `nodes` ADD `BoosterSMPStatus` TEXT";$q->QUERY_SQL($sql);}
		$sql="UPDATE nodes SET BoosterSMPStatus='$EncodedDatas' WHERE hostid='$this->hostid'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}		
	}
	
	public function squid_save_cachelogs($EncodedDatas){
		$array=unserialize(base64_decode($EncodedDatas));
		if(!is_array($array)){
			writelogs("Not an array",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return;}
			
		if(count($array)==0){
			writelogs("No rows",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return;				
		}	
		$q=new mysql_blackbox();
		$tt=array();
		$prefix="INSERT IGNORE INTO `cachelogs$this->nodeid`(`zDate`,`line`) VALUES ";
		while (list ($directory, $line) = each ($array)){
			$line=trim($line);
			$zDate=null;
			if($line==null){continue;}
			if(preg_match("#^(.+)\s+([0-9:]+)\s+(.+)#", $line,$re)){
				$time=strtotime("{$re[1]} {$re[2]}");
				$zDate=date("Y-m-d H:i:s",$time);
				$line=$re[3];
			}
			$line=$this->mysql_escape_string_art($line);
			$tt[]="('$zDate','$line')";
		}
		writelogs("Injecting ".count($tt)." lines",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		if(count($tt)>0){
			writelogs("removing `cachelogs$this->nodeid`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL("DROP TABLE `cachelogs$this->nodeid`");
			$q->BuildCachelogs($this->nodeid);
			$q->QUERY_SQL($prefix.@implode(",", $tt));
			if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		
	}
	
	private function mysql_escape_string_art($line){
		if(function_exists("mysql_real_escape_string")){
			return mysql_real_escape_string($line);
		}
		return mysql_escape_string2($line);
		
	}
	
	public function squid_save_etcconf($EncodedDatas){
		$q=new mysql_blackbox();	
		$sql="UPDATE nodes SET squidconf='$EncodedDatas' WHERE  nodeid=$this->nodeid";
		writelogs("/etc/squid3/squid.conf (virtual) ".strlen($EncodedDatas)." bytes",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			if(strpos($q->mysql_error, "column 'squidconf'")){$q->CheckTables();$q->QUERY_SQL($sql);}
		}
		if(!$q->ok){
			writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		}
	}
	
	function squid_save_squidver($ver){
		$q=new mysql_blackbox();	
		$sql="UPDATE nodes SET squidver='$ver' WHERE  nodeid=$this->nodeid";
		
		$q->QUERY_SQL($sql);
		if(!$q->ok){if(strpos($q->mysql_error, "column 'squidver'")){$q->CheckTables();$q->QUERY_SQL($sql);}}
		if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}		
		
	}
	
	function SetArch($Arch){
		$q=new mysql_blackbox();	
		$sql="UPDATE nodes SET arch='$Arch' WHERE  nodeid=$this->nodeid";
		$q->QUERY_SQL($sql);
		if(!$q->ok){if(strpos($q->mysql_error, "column 'arch'")){$q->CheckTables();$q->QUERY_SQL($sql);}}
		if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}			
		
	}
	
	
	function DaemonsSettings($EncodedDatas){
		$array=unserialize(base64_decode($EncodedDatas));
		if(!is_array($array)){return;}
		if(count($array)<3){return;}
		$q=new mysql_blackbox();
		$q->QUERY_SQL("TRUNCATE TABLE `pms$this->nodeid`");
		while (list ($key, $datas) = each ($array)){$f[]="('$key','$datas')";}
		$q->QUERY_SQL("INSERT IGNORE INTO pms$this->nodeid (zkey,`value`) VALUES ". @implode(",", $f));
		if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		
	}
	
	public function DumpSettings(){
		if(!isset($GLOBALS["CLASS_MYSQLBLK"])){$GLOBALS["CLASS_MYSQLBLK"]=new mysql_blackbox();$q=$GLOBALS["CLASS_MYSQLBLK"];}else{$q=$GLOBALS["CLASS_MYSQLBLK"];}
		$sql="SELECT `zkey`,`value` FROM `pms$this->nodeid`";
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysqli_fetch_assoc($results)) {
			$array[$ligne["zkey"]]=$ligne["value"];
		}
		return $array;		
		
		
	}	
	
	function SET_INFO($key,$value){
		$q=new mysql_blackbox();
		$value=base64_encode($value);
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT zkey FROM pms$this->nodeid WHERE zkey='$key'"));	
		if(trim($ligne["zkey"])==null){
			$q->QUERY_SQL("INSERT IGNORE INTO pms$this->nodeid (zkey,`value`) VALUES ('$key','$value')"); 
		}else{
			$q->QUERY_SQL("UPDATE pms$this->nodeid SET `value`='$value' WHERE zkey='$key'");
		}
		if(!$q->ok){writelogs($q->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		echo $q->mysql_error;
	}
	
	function EchoOrders(){
		$q=new mysql_blackbox();
		if(!$q->TABLE_EXISTS("poolorders")){$q->CheckTables();return;}
		
		$sql="SELECT * FROM poolorders WHERE hostid='$this->hostid'";
		$results=$q->QUERY_SQL($sql);
		if(mysqli_num_rows($results)==0){
			zWriteToSyslog("($this->hostname): Checks Orders -> nothing..");
			return;}
		
		$t=array();
		$this->WriteToSyslog("($this->hostname): ".mysqli_num_rows($results)." order(s)");
		$ALREADY=array();
		while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
			
			
			$md5=md5("$this->hostid{$ligne["orderdata"]}");
			if(isset($ALREADY[$md5])){
				$del[]="DELETE FROM poolorders WHERE orderid='{$ligne["orderid"]}'";
				continue;
			}
			$this->WriteToSyslog("($this->hostname): {$ligne["orderid"]} = {$ligne["orderdata"]}");
			$t[]=array("orderid"=>$ligne["orderid"],"orderdata"=>$ligne["orderdata"]);
			$ALREADY[$md5]=true;
		}
		
		echo "\n<ORDERS>".base64_encode(serialize($t))."</ORDERS>\n";
		
		if(count($del)>0){
			$this->WriteToSyslog("($this->hostname): remove ".count($del)." duplicated order(s)");
			while (list ($num, $sql) = each ($del) ){
				$q->QUERY_SQL($sql);
				if(!$q->ok){
					$this->WriteToSyslog("($this->hostname): $q->mysql_error");
				}
			}
		}
		
	}
	
	
	
	public function delete_node(){
	
		
	}
	
	function last_available_version(){
		$reposdir=dirname(__FILE__)."/artica-agent";
		
		foreach (glob("$reposdir/*.tar.gz") as $filename) {
			$val=basename($filename);
			if (preg_match('#artica-agent-([0-9\.\-\_]+)\.[a\.-z]+$#i',$val,$r)){
	          			if(strpos($r[1],'.')>0){
	          				$key=$r[1];
	          				$key=str_replace('.','',$key);
	          				$key=str_replace('-','',$key);
	          				$arr[$key]=$r[1];
	          			}
	          }
         }
		 if(is_array($arr)){
          	ksort($arr);
          	while (list ($num, $val) = each ($arr) ){$v[]=$val;}
          }

          	if($returnarray==1){return $v;}
          	return $v[count($v)-1];
  	}
  	
	function last_available_squidx64_version(){
		$reposdir=dirname(__FILE__)."/artica-agent";
		
		foreach (glob("$reposdir/*.tar.gz") as $filename) {
			$val=basename($filename);
			if (preg_match('#squid32-x64-([0-9\.\-\_a-z]+)\.tar\.gz$#i',$val,$r)){
	          			if(strpos($r[1],'.')>0){
	          				$key=$r[1];
	          				$key=str_replace('.','',$key);
	          				$key=str_replace('-','',$key);
	          				$arr[$key]=$r[1];
	          			}
	          }
         }
		 if(is_array($arr)){
          	ksort($arr);
          	while (list ($num, $val) = each ($arr) ){$v[]=$val;}
          }

          	if($returnarray==1){return $v;}
          	return $v[count($v)-1];
  	} 

	function last_available_squidx32_version(){
		$reposdir=dirname(__FILE__)."/artica-agent";
		
		foreach (glob("$reposdir/*.tar.gz") as $filename) {
			$val=basename($filename);
			if (preg_match('#^squid32-i386-([0-9\.\-\_a-z]+)\.tar\.gz$#i',$val,$r)){
	          			if(strpos($r[1],'.')>0){
	          				$key=$r[1];
	          				$key=str_replace('.','',$key);
	          				$key=str_replace('-','',$key);
	          				$arr[$key]=$r[1];
	          			}
	          }
         }
		 if(is_array($arr)){
          	ksort($arr);
          	while (list ($num, $val) = each ($arr) ){$v[]=$val;}
          }

          	if($returnarray==1){return $v;}
          	return $v[count($v)-1];
  	}   	
	
	
	private function ParseSofts($softsEncoded){
		$softs=explode("\n",base64_decode($softsEncoded));
			while (list ($num, $val) = each ($softs) ){
				if(preg_match('#\[(.+?)\]\s+"(.+?)"#', $val,$re)){
					if($re[2]=="0"){continue;}
					if($re[2]=="."){continue;}
					if($re[2]=="0.0"){continue;}
					$re[1]=addslashes($re[1]);
					$re[2]=addslashes($re[2]);
					$f[]="('$this->nodeid','{$re[1]}','{$re[2]}')";	
				}
			}

		$q=new mysql_blackbox();
		$q->CheckTables();	
		if($GLOBALS["VERBOSE"]){echo "DELETE FROM softwares WHERE nodeid='$this->nodeid'\n";}
		$q->QUERY_SQL("DELETE FROM softwares WHERE nodeid='$this->nodeid'");
		
		$prefix="INSERT IGNORE INTO softwares (nodeid,software,version) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($prefix);
	}
	
	
}