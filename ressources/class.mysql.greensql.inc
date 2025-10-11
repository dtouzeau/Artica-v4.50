<?php
include_once(dirname(__FILE__)."/class.sqlite.inc");

class green_sql{
	public $mysql_error;
	public $ok=true;
	public $last_id=0;
	private $SockPath;
	private $mysqli_connection;
	private $username="greensql";
	private $password="greensql";
	
	public function __construct(){
		$this->SockPath="/var/run/mysqld/mysqld.sock";
		
	}
	
	
	function TestingConnection(){
		$funcclass=__CLASS__."/".__FUNCTION__;
		
		if (@mysqli_ping($this->mysqli_connection)) {
			return true;
		}
		
		if($GLOBALS["VERBOSE"]){$bd=mysqli_connect("localhost",$this->username,$this->password,null,0,$this->SockPath);}
		if(!$GLOBALS["VERBOSE"]){$bd=@mysqli_connect("localhost",$this->username,$this->password,null,0,$this->SockPath);}
			
		if (mysqli_connect_errno()){
			$mysqli_connect_errno=mysqli_connect_errno();
			$mysqli_connect_error=mysqli_connect_error();
			VERBOSE("$funcclass:: Error.{$mysqli_connect_errno} Failed to connect to MySQL \"$mysqli_connect_error\"",__LINE__);
			$this->mysql_error="$funcclass:: $this->SockPath failed N.$mysqli_connect_errno \"$mysqli_connect_error\"";
			$this->ok=false;
			return false;
		}
		$this->mysqli_connection=$bd;
		return true;
		
	}
	
	FUNCTION FIELDS_LIST($table,$tags){
		
		$sql="SHOW FULL FIELDS FROM `$table`";
		VERBOSE($sql,__LINE__);
		$results=$this->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){if(!$this->ok){VERBOSE($this->mysql_error,__LINE__);}}
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$tags[]="FIELD:{$ligne["Field"]}";
				
		}
		
		return $tags;
	}
	
	public function GET_INFO($key){
		$q=new lib_sqlite("/home/artica/SQLITE/greensql.db");
		$ligne=$q->QUERY_SQL("SELECT zvalue FROM zDaemon where `zKey`=$key");
		return trim($ligne);
		if(!$q->ok){
			$sql="CREATE TABLE IF NOT EXISTS `settings` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`zKey` text UNIQUE, `zvalue` text )";
			$q->QUERY_SQL($sql);
		}
	}
	
	public function SET_INFO($key,$value){
		$q=new lib_sqlite("/home/artica/SQLITE/greensql.db");
		$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `settings` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`zKey` text UNIQUE, `zvalue` text )");
		$q->QUERY_SQL("DELETE FROM `settings` WHERE `zKey`='$key'");
		$q->QUERY_SQL("INSERT INTO `settings` (zKey,zvalue) VALUES ('$key','$value')");
	}
	
	
	
	public function GET_PROXY_NAME($proxyid){
		$ligne=mysqli_fetch_array($this->QUERY_SQL("SELECT proxyname FROM proxy WHERE proxyid='$proxyid'"));
		return $ligne["proxyname"];
	}
	
	public function QUERY_SQL($sql){
		$funcclass=__CLASS__."/".__FUNCTION__;
		$this->ok=true;
		$this->last_id=0;
		if(!$this->TestingConnection()){return false;}
		
		$ok=@mysqli_select_db($this->mysqli_connection,"greensql");
		
		if (!$ok){
			$errnum=@mysqli_errno($this->mysqli_connection);
			$des=@mysqli_error($this->mysqli_connection);
			if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
			$this->mysql_error="$funcclass failed N.$errnum $des";
			$this->ok=false;
		}
		
		$results=@mysqli_query($this->mysqli_connection,$sql);
		
		
		if(!$results){
			$errnum=@mysqli_errno($this->mysqli_connection);
			$des=@mysqli_error($this->mysqli_connection);
			if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
			$this->mysql_error="$funcclass failed N.$errnum $des";
			$this->ok=false;
		}
		
		if(intval($this->last_id)==0){
			$this->last_id=@mysqli_insert_id($this->mysqli_connection);
		}
		$result_return=$results;
		@mysqli_free_result($this->mysqli_connection);
		if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
		$this->mysqli_connection=false;
		return $result_return;
		
		
	}
	
}