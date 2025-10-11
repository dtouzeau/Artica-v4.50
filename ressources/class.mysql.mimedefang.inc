<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
include_once(dirname(__FILE__).'/class.users.menus.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
class mysql_mimedefang_builder{
	var $ClassSQL;
	var $ok=false;
	var $mysql_error;
	var $UseMysql=true;
	var $database="mimedefang";
	var $mysql_server;
	var $mysql_admin;
	var $mysql_password;
	var $mysql_port;
	var $MysqlFailed=false;
	var $EnableRemoteStatisticsAppliance=0;
	
	
	function __construct(){
		$sock=new sockets();
		$this->ClassSQL=new mysql();
		$this->UseMysql=$this->ClassSQL->UseMysql;
		$this->mysql_admin=$this->ClassSQL->mysql_admin;
		$this->mysql_password=$this->ClassSQL->mysql_password;
		$this->mysql_port=$this->ClassSQL->mysql_port;
		$this->mysql_server=$this->ClassSQL->mysql_server;	

		$MimeDefangFileHostingExternMySQL=$sock->GET_INFO("MimeDefangFileHostingExternMySQL");
		if(!is_numeric($MimeDefangFileHostingExternMySQL)){$MimeDefangFileHostingExternMySQL=0;}
		if($MimeDefangFileHostingExternMySQL==1){
			$this->mysql_server=$sock->GET_INFO("MimeDefangFileHostingMySQLsrv");
			if(preg_match("#(.*?):([0-9]+)#",$this->mysql_server,$re)){$this->mysql_server=$re[1];$this->mysql_port=$re[2];}
			$this->mysql_admin=$sock->GET_INFO("MimeDefangFileHostingMySQLusr");
			$this->mysql_password=$sock->GET_INFO("MimeDefangFileHostingMySQLPass");
			$this->ClassSQL->mysql_password=$this->mysql_password;
			$this->ClassSQL->mysql_admin=$this->mysql_admin;
			$this->ClassSQL->mysql_server=$this->mysql_server;
			$this->ClassSQL->mysql_port=$this->mysql_port;
		}		
		
		if(!$this->TestingConnection()){$this->MysqlFailed=true;}
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
	
	public function CheckTables(){
		if(!$this->DATABASE_EXISTS($this->database)){$this->CREATE_DATABASE($this->database);}
		$this->storage_tables();
		
	}
	
	public function storage_tables(){
		if(!$this->TABLE_EXISTS('storage',$this->database)){	
			$sql="CREATE TABLE `$this->database`.`storage` (
			`filename` VARCHAR(255) NOT NULL PRIMARY KEY,
			`filetime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
			`filedata` longblob NOT NULL,
			`filesize` BIGINT UNSIGNED NOT NULL,
			 INDEX ( `filetime`,`filesize`)
			 )";	

			$this->QUERY_SQL($sql,$this->database);
		}		
		
		
	}
	
	
	
}
