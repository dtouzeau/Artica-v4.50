<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
include_once(dirname(__FILE__).'/class.users.menus.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
class mysql_mailarchive_builder{
	private $ClassSQL;
	public  $ok=false;
	public  $mysql_error;
	private $UseMysql=true;
	public  $database="mailarchive";
	private $mysql_server;
	private $mysql_admin;
	private $mysql_password;
	private $mysql_port;
	public  $MysqlFailed=false;

	private $SocketName;
	public  $MYSQL_CMDLINES;
	
	
	
	function __construct(){
		$this->ClassSQL=new mysql();
		$this->UseMysql=$this->ClassSQL->UseMysql;
		$this->mysql_admin=$this->ClassSQL->mysql_admin;
		$this->mysql_password=$this->ClassSQL->mysql_password;
		$this->mysql_port=$this->ClassSQL->mysql_port;
		$this->mysql_server=$this->ClassSQL->mysql_server;		
		$this->SocketName=$this->ClassSQL->SocketName;	
		$pass=null;
		if(!$this->TestingConnection()){$this->MysqlFailed=true;}
		
		if($this->mysql_server=="localhost"){$this->mysql_server="127.0.0.1";}
		
		if(strlen($this->mysql_password)>1){$pass=$this->shellEscapeChars($this->mysql_password);$pass=" -p$pass";}
		if($this->mysql_server=="127.0.0.1"){$serv="--protocol=socket --socket=$this->SocketName";}
		if($this->mysql_server<>"127.0.0.1"){$serv="--protocol=tcp --host=$this->mysql_server --port=$this->mysql_port";}		
		$this->MYSQL_CMDLINES="$serv -u $this->mysql_admin{$pass}";
	}
	
	private function shellEscapeChars($path){
		$path=str_replace(" ","\ ",$path);
		$path=str_replace('$','\$',$path);
		$path=str_replace("&","\&",$path);
		$path=str_replace("?","\?",$path);
		$path=str_replace("#","\#",$path);
		$path=str_replace("[","\[",$path);
		$path=str_replace("]","\]",$path);
		$path=str_replace("{","\{",$path);
		$path=str_replace("}","\}",$path);
		$path=str_replace("*","\*",$path);
		$path=str_replace('"','\\"',$path);
		$path=str_replace("'","\\'",$path);
		$path=str_replace("(","\(",$path);
		$path=str_replace(")","\)",$path);
		$path=str_replace("<","\<",$path);				
		$path=str_replace(">","\>",$path);
		$path=str_replace("!","\!",$path);
		$path=str_replace("+","\+",$path);
		$path=str_replace(";","\;",$path);
		return $path;	
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
		
		if($database==null){$database=$this->database;}
		if($database<>$this->database){$database=$this->database;}
		$a=$this->ClassSQL->TABLE_EXISTS($table,$database);
		if(!$a){if(function_exists("debug_backtrace")){try {$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}} catch (Exception $e) {writelogs("TABLE_EXISTS:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}writelogs($called,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
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

	public function EVENTS_SUM(){
		$sql="SELECT SUM(TABLE_ROWS) as tsum FROM information_schema.tables WHERE table_schema = '$this->database' AND table_name LIKE 'dansguardian_events_%'";
		$ligne=mysqli_fetch_array($this->QUERY_SQL($sql));
		if(!$this->ok){writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		writelogs("{$ligne["tsum"]} : $sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		return $ligne["tsum"];
		
	}	
	
	public function COUNT_TABLES($database){
		if(isset($GLOBALS["COUNT_TABLES$database"])){return count($GLOBALS["COUNT_TABLES$database"]);}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = '$database'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysqli_num_rows($results)."\n";}
	
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$array[$ligne["c"]]=$ligne["c"];
			$GLOBALS["COUNT_TABLES$database"][$ligne["c"]]=$ligne["c"];
				
		}
	
		return count($array);
	}

	FUNCTION DATABASE_SIZE_BYTES($database){
		if(!function_exists("mysqli_connect")){return 0;}
		$sql="SHOW table STATUS";
		$dbsize=0;
		$results=$this->QUERY_SQL($sql,$database);
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$dbsize += $ligne['Data_length'] + $ligne['Index_length'];
		}
	
		return $dbsize;
	
	}
	
	public function CheckTables(){
		if(!$this->DATABASE_EXISTS($this->database)){$this->CREATE_DATABASE($this->database);}
		
	}
	
	public function BuildDayTable($tablename){
		if($this->TABLE_EXISTS($tablename)){return true;}
		
			if(!$this->TABLE_EXISTS($tablename)){		
			$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
				  `MessageID` varchar(80) NOT NULL,
				  `zDate` datetime NOT NULL,
				  `mailfrom` varchar(255) NOT NULL,
				  `subject` varchar(255) NOT NULL,
				  `MessageBody` text NOT NULL,
				  `organization` varchar(80) NOT NULL,
				  `uid` varchar(50) NOT NULL,
				  `file_path` varchar(255) DEFAULT NULL,
				  `original_messageid` varchar(80) NOT NULL,
				  `message_size` int(10) NOT NULL,
				  `mailfrom_domain` varchar(128) NOT NULL,
				  `mailto` varchar(255) NOT NULL,
				  `BinMessg` longblob NOT NULL,
				  `filename` varchar(128) NOT NULL,
				  `filesize` int(10) NOT NULL,
				  PRIMARY KEY (`MessageID`),
				  KEY `mailfrom` (`mailfrom`),
				  KEY `organization` (`organization`),
				  KEY `uid` (`uid`),
				  KEY `original_messageid` (`original_messageid`),
				  KEY `mailfrom_domain` (`mailfrom_domain`),
				  KEY `mailto` (`mailto`),
				  KEY `filename` (`filename`),
				  FULLTEXT KEY `MessageBody` (`MessageBody`)
				) ;";
				
				$this->QUERY_SQL($sql);
				if(!$this->ok){return false;}
				}

		
		return true;
		
	}
	
	
	public function BuildSummaryTable(){
		if(!$this->TABLE_EXISTS("indextables")){		
			$sql="CREATE TABLE IF NOT EXISTS `indextables` (
			 `tablename` varchar(80) NOT NULL,
			 `rowsnum` BIGINT UNSIGNED NOT NULL,
			 `xday`DATE NOT NULL,
			 `size` BIGINT UNSIGNED NOT NULL,
			 PRIMARY KEY (`tablename`),
			 KEY `rowsnum` (`rowsnum`),
			 KEY `size` (`size`)
			);";
		$this->QUERY_SQL($sql);if(!$this->ok){return false;}}
		return true;
	}
	
	
		
		
	public function LIST_BACKUP_TABLES(){
		if(isset($GLOBALS["LIST_BACKUP_TABLES"])){return $GLOBALS["LIST_BACKUP_TABLES"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = '$this->database'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysqli_num_rows($results)."\n";}
		$thishour=date("YmdH");
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			if(!is_numeric($ligne["c"])){continue;}
			$number=$ligne["c"];
			$year=substr($number, 0,4);
			$month=substr($number, 4,2);
			$day=substr($number, 6,2);
			$GLOBALS["LIST_BACKUP_TABLES"][$ligne["c"]]="$year-$month-$day";
			$array[$ligne["c"]]=$ligne["c"];
		}
		if(isset($GLOBALS["LIST_BACKUP_TABLES"])){
			return $GLOBALS["LIST_BACKUP_TABLES"];	
		}
		
		
	}
	
	
}