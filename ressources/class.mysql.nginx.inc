<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.users.menus.inc');
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__).'/class.mysql.inc');

class nginx_stats{
	private $ClassSQL;
	private $MySQLSyslogType=1;
	public $mysql_admin;
	public $mysql_password;
	public $mysql_server;
	public $MYSQL_CMDLINES;
	public $database=null;
	private $mysqli_connection;
	public $SocketName="";
	public $ok=false;
	public $mysql_error;
	public $last_id=0;
	public $dbidsn="";
	public $mysql_port=3306;
	private $NOCHDB=array("mysql"=>true);
	public $start_error=null;
	private $MySQLLogsTypeTextAR=array("{not_set}","{server}","{client}");
	public $MYSQLTypeText;
	public $EnableSyslogDB=0;
	
	function __construct($servername){
		$this->PrepareMySQLClass();
		$this->database=$servername;
		if(!$this->DATABASE_EXISTS($this->database)){
			$this->CREATE_DATABASE($this->database);
			$this->CheckTables();
		}		
		
	}
	
	private function PrepareMySQLClass(){
		$sock=new sockets();
		$this->MySQLSyslogType=$sock->GET_INFO("MySQLNgnixType");
		$this->EnableSyslogDB=$sock->GET_INFO("EnableNginxStats");
		if(!is_numeric($this->EnableSyslogDB)){$this->EnableSyslogDB=0;}
		if(!is_numeric($this->MySQLSyslogType)){$this->MySQLSyslogType=1;}
		if($this->MySQLSyslogType==0){$this->MySQLSyslogType=4;}
		
		unset($GLOBALS["MYSQL_PARAMETERS"]);
		unset($_SESSION["MYSQL_PARAMETERS"]);
		
		$TuningParameters=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLNginxParams")));
		$this->MYSQLTypeText=$this->MySQLLogsTypeTextAR[$this->MySQLSyslogType];
		if($this->MySQLSyslogType==1){
			$ListenPort=$TuningParameters["ListenPort"];
			$def["SocketPath"]="/var/run/nginxdb.sock";
			$this->SocketName=$def["SocketPath"];
			$def["mysql_admin"]="root";
			$def["mysql_password"]=null;
			$def["TryTCP"]=false;
			$def["mysql_port"]=$ListenPort;
			$this->SocketName=$def["SocketPath"];
			$this->mysql_admin=$def["mysql_admin"];
			$this->mysql_password=$def["mysql_password"];
			$this->mysql_port=$def["mysql_port"];
			$this->mysql_server="127.0.0.1";
			$this->ClassSQL=new mysql($def);
			$this->MYSQL_CMDLINES="--protocol=socket --socket={$def["SocketPath"]} -u root";
			$this->dbidsn="['DBI:mysql:database=$this->database;mysql_socket=$this->SocketName', '$this->mysql_admin', '$this->mysql_password']";
		}
		
		if($this->MySQLSyslogType==2){
			$TuningParameters=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSyslogParams")));
			$username=$TuningParameters["username"];
			$password=$TuningParameters["password"];
			$mysqlserver=$TuningParameters["mysqlserver"];
			$RemotePort=$TuningParameters["RemotePort"];			
			$def["SocketPath"]=null;
			$def["mysql_admin"]=$username;
			$def["mysql_password"]=$password;
			$def["TryTCP"]=true;
			$def["mysql_port"]=$RemotePort;
			$def["mysql_server"]=$mysqlserver;
			$this->SocketName=$def["SocketPath"];
			$this->mysql_admin=$def["mysql_admin"];
			$this->mysql_password=$def["mysql_password"];
			$this->mysql_port=$def["mysql_port"];
			$this->mysql_server=$mysqlserver;
			$this->ClassSQL=new mysql($def);
			$pwd=null;
			if($password<>null){ $pwd=" -p$password";}
			$this->MYSQL_CMDLINES="--protocol=tcp --user=$username$pwd --host=$mysqlserver --port=$RemotePort";
			$this->dbidsn="['DBI:mysql:database=$this->database;host=$mysqlserver;port=$RemotePort', '$username', '$password']";
		}
		return;
	}
	
	private function DATABASE_EXISTS($database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->DATABASE_EXISTS($database);
	}
	private FUNCTION CREATE_DATABASE($database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->CREATE_DATABASE($database);
	}
	
	public function BD_CONNECT(){
		$this->PrepareMySQLClass();
		$this->ok=true;
		$results=$this->ClassSQL->BD_CONNECT();
		if($results){
			$this->mysqli_connection=$this->ClassSQL->mysqli_connection;
			return true;
		}
		$this->ok=false;
		$this->mysql_error=$this->ClassSQL->mysql_error;
		writelogs("Fatal connection failed [".$this->ClassSQL->mysql_error."]",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
	
	}
	
	public function DATABASE_LIST_SIMPLE(){
		$sql="SHOW DATABASES";
		$this->BD_CONNECT();
		$results=@mysqli_query($this->mysqli_connection,$sql);
		$errnum=@mysqli_error($this->mysqli_connection);
		$des=@mysqli_error($this->mysqli_connection);
		$this->mysql_error=$des;
	
	
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$Database=$ligne["Database"];
			$array[$Database]=true;
		}
		return $array;
	}

	public function COUNT_ROWS($table,$database=null){
		$this->ok=true;
		if(!isset($this->NOCHDB[$database])){$this->NOCHDB[$database]=false;}
	
		if(!$this->NOCHDB[$database]){if($database<>$this->database){$database=$this->database;}}
		$count=$this->ClassSQL->COUNT_ROWS($table,$database);
		if(!$this->ClassSQL->ok){
			$this->ok=false;
			$this->mysql_error=$this->ClassSQL->mysql_error;
			if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
				
			writelogs($called,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		return $count;
	}
		
	
	public function QUERY_SQL($sql,$database=null){
		if(!$this->BD_CONNECT()){return false;}
		$this->ok=true;
		if(!isset($this->NOCHDB[$database])){$this->NOCHDB[$database]=false;}
		if(!$this->NOCHDB[$database]){if($database<>$this->database){$database=$this->database;}}
		if($database==null){$database=$this->database;}
		$results=$this->ClassSQL->QUERY_SQL($sql,$database);
		$this->ok=$this->ClassSQL->ok;
		$this->mysql_error=$this->ClassSQL->mysql_error;
		$this->last_id=$this->ClassSQL->last_id;
		return $results;
	}
	
	public function EXECUTE_SQL($sql){
		if(!$this->BD_CONNECT()){return false;}
		if(isset($GLOBALS[__CLASS__]["FAILED"])){return;}
		@mysqli_query($this->mysqli_connection,$sql);
		if(mysqli_error($this->mysqli_connection)){
			$time=date('h:i:s');
			$errnum=mysqli_errno($this->mysqli_connection);
			$des=mysqli_error($this->mysqli_connection);
			$this->mysql_error="Error Number ($errnum) ($des)";
			writelogs("$this->SocketPath:$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
	
	
		return true;
	}	
	
	public function FIELD_EXISTS($table,$field,$database=null){
		$database=$this->database;
		return $this->ClassSQL->FIELD_EXISTS($table,$field,$database);
	}	
	
	public function SHOW_STATUS(){
		if(!$this->BD_CONNECT()){return false;}
		return $this->ClassSQL->SHOW_STATUS();
	}	
	
	public function COUNT_ALL_TABLES(){
		if(!$this->BD_CONNECT()){return false;}
		$sql="SELECT COUNT(*) as tcount, (SUM(`INDEX_LENGTH`)+ SUM(`DATA_LENGTH`)) as x FROM information_schema.tables WHERE table_schema = '$this->database'";
		$ligne=@mysqli_fetch_array($this->QUERY_SQL($sql));
		return array($ligne["tcount"],$ligne["x"]);
	}	

	public function checkTables(){
		if(!$this->BD_CONNECT()){return false;}
		
		}		
	
	public function TABLE_EXISTS($table,$database=null){
		$this->BD_CONNECT();
		if($database==null){$database=$this->database;}
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->TABLE_EXISTS($table,$database);
	}


	public function hourtable($tablename){
		
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
		
		`zmd5` varchar(90) NOT NULL ,
		`zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`ipaddr` varchar(90) NOT NULL,
		`hostname` varchar(128) NOT NULL,
		`useragent` varchar(128) NOT NULL,
		`country` varchar(90) NOT NULL,
		`uri` varchar(255) NOT NULL,
		`size` BIGINT UNSIGNED NOT NULL,
		`proto` varchar(50) NOT NULL,
	    `httpcode` smallint(3) NOT NULL,
			PRIMARY KEY (`zmd5`),
			KEY `zDate` (`zDate`),
			KEY `ipaddr` (`ipaddr`),
			KEY `hostname` (`hostname`),
			KEY `useragent` (`useragent`),
			KEY `country` (`country`),
			KEY `uri` (`uri`),
			KEY `size` (`size`),
			KEY `httpcode` (`httpcode`)
		)";	

		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){return false;}
		return true;
		
		
	}
	
	public function DayTable($tablename){
	
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
	
		`zmd5` varchar(90) NOT NULL ,
		`zDate` date,
		`zhour` smallint(2) NOT NULL,
		`ipaddr` varchar(90) NOT NULL,
		`hostname` varchar(128) NOT NULL,
		`useragent` varchar(128) NOT NULL,
		`country` varchar(90) NOT NULL,
		`uri` varchar(255) NOT NULL,
		`size` BIGINT UNSIGNED NOT NULL,
		`hits` BIGINT UNSIGNED NOT NULL,
		`proto` varchar(50) NOT NULL,
		`httpcode` smallint(3) NOT NULL,
		PRIMARY KEY (`zmd5`),
		KEY `zDate` (`zDate`),
		KEY `zhour` (`zhour`),
		KEY `ipaddr` (`ipaddr`),
		KEY `hostname` (`hostname`),
		KEY `useragent` (`useragent`),
		KEY `country` (`country`),
		KEY `uri` (`uri`),
		KEY `size` (`size`),
		KEY `hits` (`hits`),
		KEY `httpcode` (`httpcode`)
		)";
	
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){return false;}
		return true;
	
	
	}	
	
	public function MonthTable($tablename){
	
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
		`zmd5` varchar(90) NOT NULL ,
		`zDay` smallint(2) NOT NULL,
		`ipaddr` varchar(90) NOT NULL,
		`hostname` varchar(128) NOT NULL,
		`country` varchar(90) NOT NULL,
		`size` BIGINT UNSIGNED NOT NULL,
		`hits` BIGINT UNSIGNED NOT NULL,
		PRIMARY KEY (`zmd5`),
		KEY `zDay` (`zDay`),
		KEY `ipaddr` (`ipaddr`),
		KEY `hostname` (`hostname`),
		KEY `country` (`country`),
		KEY `size` (`size`),
		KEY `hits` (`hits`)
		)";
	
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){return false;}
		return true;
	
	
		}	
	
	
	public function LIST_TABLES_DAY(){
		if(isset($GLOBALS["{$this->database}LIST_TABLES_DAY"])){return $GLOBALS["{$this->database}LIST_TABLES_DAY"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = '$this->database'
		AND table_name LIKE 'day_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysqli_num_rows($results)."\n";}
	
				while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
				if(preg_match("#day_[0-9]+$#", $ligne["c"])){
				$GLOBALS["{$this->database}LIST_TABLES_DAY"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
				}
				}
				return $array;
	}	
	
	public function events($text){
		$pid=@getmypid();
		
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				$file=basename($trace[1]["file"]);
				$function=$trace[1]["function"];
				$line=$trace[1]["line"];
			}
		}		
		
		$date=@date("H:i:s");
		$logFile="/var/log/artica-postfix/nginxdb.debug";
		$line="$date [$pid] $file $text <div><i>function:$function, line:$line</i></div>\n";
		if($GLOBALS["VERBOSE"]){echo $line;}
		$size=@filesize($logFile);
		if($size>8000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, $line);
		@fclose($f);	
		}
	
}
