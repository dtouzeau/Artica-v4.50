<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
include_once(dirname(__FILE__).'/class.users.menus.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__)."/class.categorize.externals.inc");
include_once(dirname(__FILE__)."/class.mysql.blackboxes.inc");
include_once(dirname(__FILE__)."/class.mysql.catz.inc");
include_once(dirname(__FILE__).'/class.simple.image.inc');


class mysql_haproxy_builder{
	var $ClassSQL;
	var $ok=false;
	var $mysql_error;
	var $UseMysql=true;
	var $database="haproxylogs";
	var $mysql_server;
	var $mysql_admin;
	var $mysql_password;
	var $mysql_port;
	var $MysqlFailed=false;
	var $EnableRemoteStatisticsAppliance=0;
	var $DisableArticaProxyStatistics=0;
	var $EnableSargGenerator=0;
	var $tasks_array=array();
	var $tasks_explain_array=array();
	var $tasks_processes=array();
	var $tasks_disabled=array();
	var $last_id;
	
	function __construct(){
		$sock=new sockets();
		
		
		$this->ClassSQL=new mysql();
		$this->UseMysql=$this->ClassSQL->UseMysql;
		$this->mysql_admin=$this->ClassSQL->mysql_admin;
		$this->mysql_password=$this->ClassSQL->mysql_password;
		$this->mysql_port=$this->ClassSQL->mysql_port;
		$this->mysql_server=$this->ClassSQL->mysql_server;
		if($this->TestingConnection()){}else{$this->MysqlFailed=true;}
		
	}
	
	
	
	
	
	
	
	FUNCTION DELETE_TABLE($table){
		if(!function_exists("mysqli_connect")){return 0;}
		if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}squid_admin_mysql(2, "MySQL table $this->database/$table was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");}
		$this->QUERY_SQL("DROP TABLE `$table`",$this->database);
	}		
	
	
	public function TestingConnection(){
		$this->ok=true;
		$this->ClassSQL->ok=true;
		$a=$this->ClassSQL->TestingConnection();
		$this->mysql_error=$this->ClassSQL->mysql_error;
		return $a;
	}
	
	public function COUNT_ROWS($table,$database=null){
		if($database<>$this->database){$database=$this->database;}
		$count=$this->ClassSQL->COUNT_ROWS($table,$database);
		if(!$this->ClassSQL->ok){
			if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
			writelogs($called,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		return $count;
	}
	
	
	public function TABLE_SIZE($table,$database=null){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->TABLE_SIZE($table,$database);		
	}
	
	public function TABLE_EXISTS($table,$database=null){
		if($table=="category_teans"){$table="category_teens";}
		if($database==null){$database=$this->database;}
		if($database<>$this->database){$database=$this->database;}
		$a=$this->ClassSQL->TABLE_EXISTS($table,$database);
		if(!$a){
				if(function_exists("debug_backtrace")){
				try {$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}} catch (Exception $e) {writelogs("TABLE_EXISTS:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}
				writelogs($called,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);	
		}
		return $a;
		
	}
	private function DATABASE_EXISTS($database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->DATABASE_EXISTS($database);
	}
	
	private function FIELD_EXISTS($table,$field,$database=null){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->FIELD_EXISTS($table,$field,$database);
	}
	
	public function QUERY_SQL($sql,$database=null){
		if($database<>$this->database){$database=$this->database;}
		$results=$this->ClassSQL->QUERY_SQL($sql,$database);
		$this->ok=$this->ClassSQL->ok;
		$this->mysql_error=$this->ClassSQL->mysql_error;
		$this->last_id=$this->ClassSQL->last_id;
		return $results;
	}
	
	private function FIELD_TYPE($table,$field,$database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->FIELD_TYPE($table,$field,$database);
	}
	
	private FUNCTION INDEX_EXISTS($table,$index,$database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->INDEX_EXISTS($table,$index,$database);
	}
	
	private FUNCTION CREATE_DATABASE($database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->CREATE_DATABASE($database);
	}
	
	
	public function LIST_TABLES_QUERIES(){
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE 'dansguardian_events_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysqli_num_rows($results)."\n";}
		
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			if(preg_match("#dansguardian_events_([0-9]{1,4})([0-9]{1,2})([0-9]{1,2})#", $ligne["c"],$re))
			$array[$ligne["c"]]=$re[1]."-".$re[2]."-".$re[3];
		}
		return $array;
		
	}

	

	
	public function LIST_TABLES_HOURS(){
		if(isset($GLOBALS["SQUID_LIST_TABLES_HOURS"])){return $GLOBALS["SQUID_LIST_TABLES_HOURS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%_hour'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysqli_num_rows($results)."\n";}
		
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			if(preg_match("#[0-9]+_hour#", $ligne["c"])){
				$GLOBALS["SQUID_LIST_TABLES_HOURS"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;		
	}
	
	
	public function HIER(){
		$sql="SELECT DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 DAY),'%Y-%m-%d') as tdate";
		$ligne=mysqli_fetch_array($this->QUERY_SQL($sql));
		return $ligne["tdate"];
	}
	
	

	
	
	
	
	public function create_TableHour($fullname=null){
		
		if($fullname==null){$fullname="hour_".date("YmdH");}
		$this->CREATE_DATABASE($this->database);
		
		
		if(!$this->TABLE_EXISTS($fullname,$this->database)){
		writelogs("Checking $fullname in $this->database NOT EXISTS...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$sql="CREATE TABLE IF NOT EXISTS `$fullname` (
		  `sitename` varchar(255) NOT NULL,
		  `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		  `uri` varchar(255) NOT NULL,
		  `td` varchar(50) NOT NULL,
		  `http_code` int(10) NOT NULL,
		  `client` varchar(50) NOT NULL DEFAULT '',
		  `hostname` varchar(120) NOT NULL DEFAULT '',
		  `familysite` varchar(120) NOT NULL DEFAULT '',
		  `service` varchar(120) NOT NULL DEFAULT '',
		  `backend` varchar(120) NOT NULL DEFAULT '',
		  `zDate` datetime NOT NULL,
		  `size` BIGINT UNSIGNED NOT NULL,
		  `MAC` varchar(20) NOT NULL,
		  `zMD5` varchar(90) NOT NULL,
		  `statuslb` varchar(40) NOT NULL,
		  PRIMARY KEY (`ID`),
		  UNIQUE KEY `zMD5` (`zMD5`),
		  KEY `sitename` (`sitename`,`td`,`client`,`http_code`),
		  KEY `uri` (`uri`),
		  KEY `service` (`service`),
		  KEY `backend` (`backend`),
		  KEY `hostname` (`hostname`),
		  KEY `familysite` (`familysite`),
		  KEY `statuslb` (`statuslb`),
		  KEY `zDate` (`zDate`),
		  KEY `MAC` (`MAC`)
		) ";
		$this->QUERY_SQL($sql,$this->database); 
		if(!$this->ok){
			writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$this->mysql_error=$this->mysql_error."\n$sql";
			return false;
		}else{
			writelogs("Checking $fullname SUCCESS",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);	
			}
		}
		
		
		
	}
	
	
	public function CheckTables($table=null){
		if(!$this->DATABASE_EXISTS($this->database)){$this->CREATE_DATABASE($this->database);}
		
		
	
	}
	
}
