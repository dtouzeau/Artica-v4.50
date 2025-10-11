<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(!isset($GLOBALS["HOTSPOT_DEBUG"])){$GLOBALS["HOTSPOT_DEBUG"]=false;}
class mysql_hotspot{
	public $ok=false;
	public $mysql_error;
	
	public function __construct(){
			if(isset($GLOBALS["HOTSPOT_MYSQL_SERVER_CONFIGURED"])){return;}
			$GLOBALS["MYSQL_SOCKET"]=null;
			$GLOBALS["MYSQL_PASSWORD"]=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password"));
			if($GLOBALS["MYSQL_PASSWORD"]=="!nil"){$GLOBALS["MYSQL_PASSWORD"]=null;}
			$GLOBALS["MYSQL_PASSWORD"]=stripslashes($GLOBALS["MYSQL_PASSWORD"]);
			$GLOBALS["MYSQL_USERNAME"]=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin"));
			$GLOBALS["MYSQL_SERVER"]=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server"));
			$GLOBALS["MYSQL_PORT"]=intval(@file_get_contents("/etc/artica-postfix/settings/Mysql/port"));
			$GLOBALS["ProxyUseArticaDB"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyUseArticaDB"));
			if($GLOBALS["MYSQL_PORT"]==0){$GLOBALS["MYSQL_PORT"]=3306;}
			if($GLOBALS["MYSQL_SERVER"]==null){$GLOBALS["MYSQL_SERVER"]="127.0.0.1";}
			$GLOBALS["MYSQL_USERNAME"]=str_replace("\r", "", $GLOBALS["MYSQL_USERNAME"]);
			$GLOBALS["MYSQL_USERNAME"]=trim($GLOBALS["MYSQL_USERNAME"]);
			$GLOBALS["MYSQL_PASSWORD"]=str_replace("\r", "", $GLOBALS["MYSQL_PASSWORD"]);
			$GLOBALS["MYSQL_PASSWORD"]=trim($GLOBALS["MYSQL_PASSWORD"]);
		
			if($GLOBALS["MYSQL_USERNAME"]==null){$GLOBALS["MYSQL_USERNAME"]="root";}
			if($GLOBALS["MYSQL_SERVER"]=="localhost"){$GLOBALS["MYSQL_SERVER"]="127.0.0.1";}
						
			if($GLOBALS["MYSQL_SERVER"]=="127.0.0.1"){
				$GLOBALS["MYSQL_SOCKET"]="/var/run/mysqld/squid-db.sock";
				if($GLOBALS["ProxyUseArticaDB"]==0){
					$GLOBALS["MYSQL_SOCKET"]="/var/run/mysqld/mysqld.sock";
				}
			
			}
			
			if($GLOBALS["ProxyUseArticaDB"]==1){
				$GLOBALS["MYSQL_USERNAME"]="root";
				$GLOBALS["MYSQL_PASSWORD"]=null;
				$GLOBALS["MYSQL_SOCKET"]="/var/run/mysqld/squid-db.sock";
			}
			
			$GLOBALS["HOTSPOT_MYSQL_SERVER_CONFIGURED"]=true;
			
	}
	
	private function mysqlz_connect(){
	
		if($GLOBALS["MYSQL_SOCKET"]<>null){
			$bd=@mysqli_connect("localhost",$GLOBALS["MYSQL_USERNAME"],$GLOBALS["MYSQL_PASSWORD"],null,0,"{$GLOBALS["MYSQL_SOCKET"]}");
		}else{
			$bd=@mysqli_connect("{$GLOBALS["MYSQL_SERVER"]}","{$GLOBALS["MYSQL_USERNAME"]}","{$GLOBALS["MYSQL_PASSWORD"]}",null,$GLOBALS["MYSQL_PORT"]);
		}
	
		if($bd){
			$this->ok=true;
			return $bd;
		}
		$des=@mysqli_error();
		$errnum=@mysqli_errno();
		$this->ok=false;
		$this->mysql_error="failed (N:$errnum) \"$des\"";
		if(function_exists("wifidog_logs")){wifidog_logs($this->mysql_error,__FUNCTION__,__LINE__);}
		if(function_exists("events")){events(0,"Fatal, MySQL error $des",$this->mysql_error);}
		if(function_exists("writelogs")){writelogs("Fatal, MySQL error $des $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		if($GLOBALS["MYSQL_SOCKET"]=="/var/run/mysqld/squid-db.sock"){
			$this->ToSyslog("- ACTION REQUIRED RESTART MYSQL-SQUID - $des");
		}
		return false;
		
	}
	
	private function ToSyslog($text,$error=false){
		$text=str_replace("\n", " ", $text);
		$text=str_replace("\r", " ", $text);
		if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
		if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}
	
	public function QUERY_SQL($sql){
		$mysqli_connection=$this->mysqlz_connect();
		if(!$mysqli_connection){$this->ok=false;return false;}
	
		if(function_exists("wifidog_logs")){if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("$sql",__FUNCTION__,__LINE__);}}
		
		$ok=@mysqli_select_db($mysqli_connection,"squidlogs");
		if(!$ok){
			$errnum=@mysqli_errno($mysqli_connection);
			$des=@mysqli_error($mysqli_connection);
			@mysqli_close($mysqli_connection);
			
			$this->mysql_error="mysqli_select_db() failed (N:$errnum) \"$des\"";
			$this->ToSyslog($this->mysql_error);
			if(function_exists("wifidog_logs")){wifidog_logs($this->mysql_error,__FUNCTION__,__LINE__);}
			$this->ok=false;
			return false;
		}
	
			$mysql_unbuffered_query_log="mysql_query";
			$results=@mysqli_query($mysqli_connection,$sql);

		if(!$results){
			$errnum=@mysqli_errno($mysqli_connection);
			$des=@mysqli_error($mysqli_connection);
			@mysqli_close($mysqli_connection);
			$this->ok=false;
			$this->mysql_error="$mysql_unbuffered_query_log() failed (N:$errnum) \"$des\"";
			if(function_exists("wifidog_logs")){wifidog_logs($this->mysql_error,__FUNCTION__,__LINE__);}
			return false;
		}
		@mysqli_close($mysqli_connection);
		$this->ok=true;
		return $results;
	
	
	}
	
	public function COUNT_ROWS($table,$database=null){
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		if(!function_exists("mysqli_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysqli_fetch_array($this->QUERY_SQL($sql));
		if($ligne["Rows"]==null){$ligne["Rows"]=0;}
		return $ligne["Rows"];
	}	
	
	public function FIELD_EXISTS($table,$field){
		$field=trim($field);
		$sql="SHOW FULL FIELDS FROM `$table` WHERE Field='$field';";
		$ligne=@mysqli_fetch_array($this->QUERY_SQL($sql));
	
		if(trim($ligne["Field"])<>null){
			
			return true;
		}
		
		return false;
	
	}
	
	public function TABLE_EXISTS($table){
		$keyCache=md5(__CLASS__.__FUNCTION__.$table.__LINE__);
		if(isset($GLOBALS[$keyCache])){return $GLOBALS[$keyCache];}
	
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
	
	
		$sql="SHOW TABLES";
		$results=$this->QUERY_SQL($sql);
		$result=false;
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$GLOBALS[$keyCache][$ligne["Tables_in_squidlogs"]]=true;
			if(strtolower($table)==strtolower($ligne["Tables_in_squidlogs"])){
				$GLOBALS[$keyCache]=true;
				return true;
			}
		}
		$GLOBALS[$keyCache]=false;
		return $result;
	}

	public	function check_hotspot_tables(){
		
	}
	
}