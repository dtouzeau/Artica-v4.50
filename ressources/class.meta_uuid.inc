<?php
if(!isset($GLOBALS["DEBUG_SQL"])){$GLOBALS["DEBUG_SQL"]=false;}


class mysql_uuid_meta{
	private $mysql_admin="root";
	private $SocketName="/var/run/mysqld/mysqld.sock";
	public $last_id=0;
	public $ok=false;
	public $policy_type=array();
	public $mysql_error=null;
	private $mysqli_connection;
	private $database=null;
	private $mysql_server=":/var/run/mysqld/mysqld.sock";
	private $mysql_port=3306;
	private $mysql_password=null;
	
	public function mysql_uuid_meta($uuid=null){
		if($uuid<>null){
			$this->database="squid_{$uuid}";
			if(!$this->DATABASE_EXISTS($this->database)){$this->CREATE_DATABASE($this->database);}
			$this->systems_tables();
		}
	}
	
	
	public FUNCTION CREATE_DATABASE($database){
	
		if($GLOBALS["VERBOSE"]){echo " -> ->CREATE_DATABASE($database)<br>\n";}
		$this->mysql_password=trim($this->mysql_password);
	
		if(!$this->BD_CONNECT()){
			writelogs("CREATE_DATABASE Connection failed",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
			return false;
		}
		$results=@mysqli_query($this->mysqli_connection,"CREATE DATABASE `$database`");
		if(@mysqli_error($this->mysqli_connection)){
			$time=date('h:i:s');
			$errnum=@mysqli_errno($this->mysqli_connection);
			$des=@mysqli_error($this->mysqli_connectiond);
			if(preg_match("#database exists#", $des)){$this->ok=true;return true;}
			$this->mysql_error="CREATE DATABASE $database -> Error Number ($errnum) ($des)";
			writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
	
		$this->ok=true;
		return true;
	}
	
	private FUNCTION DELETE_DATABASE($database){
	
		if($GLOBALS["VERBOSE"]){echo " -> ->DELETE_DATABASE($database)<br>\n";}
		$this->mysql_password=trim($this->mysql_password);
	
		if(!$this->BD_CONNECT()){
			writelogs("DELETE_DATABASE Connection failed",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
			return false;
		}
		$results=@mysqli_query($this->mysqli_connection,"DROP DATABASE `$database`");
		if(@mysqli_error($this->mysqli_connection)){
			$time=date('h:i:s');
			$errnum=@mysqli_errno($this->mysqli_connection);
			$des=@mysqli_error($this->mysqli_connectiond);
			if(preg_match("#database exists#", $des)){$this->ok=true;return true;}
			$this->mysql_error="DELETE DATABASE $database -> Error Number ($errnum) ($des)";
			writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
	
		$this->ok=true;
		return true;
	}	
	
	private function DATABASE_EXISTS($database){
		$database=trim($this->database);
		
			
		$sql="SHOW DATABASES";
		$this->BD_CONNECT();
		$results=@mysqli_query($this->mysqli_connection,$sql);
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			if(strtolower($database)==strtolower($ligne["Database"])){
				$_SESSION["MYSQL_DATABASE_EXISTS"][$database]=true;
				return true;
			}
		}
			
		return false;
	}

	private function FillSettings(){
		if(!isset($GLOBALS["posix_getuid"])){
			$GLOBALS["posix_getuid"]=1000;
			if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}
		}
	
			
		if(!isset($GLOBALS["AS_ROOT"])){if($GLOBALS["posix_getuid"]==0){$GLOBALS["AS_ROOT"]=true;}}
			
		$this->mysql_password=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password"));
		if($this->mysql_password=="!nil"){$this->mysql_password=null;}
		$this->mysql_password=stripslashes($this->mysql_password);
		$this->mysql_admin=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin"));
		$this->mysql_server=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server"));
		$this->mysql_port=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/port"));
		if($this->mysql_port==null){$this->mysql_port=3306;}
		if($this->mysql_server==null){$this->mysql_server="localhost";}
		$this->mysql_admin=str_replace("\r", "", $this->mysql_admin);
		$this->mysql_admin=trim($this->mysql_admin);
		$this->mysql_password=str_replace("\r", "", $this->mysql_password);
		$this->mysql_password=trim($this->mysql_password);
		if(!isset($GLOBALS["TIMEZONES"])){$GLOBALS["TIMEZONES"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("timezones"));}
	
		if(function_exists("date_default_timezone_set")){
			if($GLOBALS["TIMEZONES"]<>null){@date_default_timezone_set($GLOBALS["TIMEZONES"]);}
		}
	
	
	
		if(($this->mysql_server=="!nil") OR ($this->mysql_admin=='!nil') OR ($this->mysql_password=='!nil')){
			$this->mysql_server="127.0.0.1";
			$this->mysql_password=null;
			$this->mysql_admin="root";
	
		}
	
		if($this->mysql_admin==null){$this->mysql_admin="root";}
		if($this->mysql_server==null){$this->mysql_server="127.0.0.1";}
		if($this->mysql_server=="localhost"){$this->mysql_server="127.0.0.1";}
		if($this->mysql_server=="locahost"){$this->mysql_server="127.0.0.1";}
		if($this->mysql_admin==null){$this->mysql_admin="root";}
		if(!is_numeric($this->mysql_port)){$this->mysql_port=3306;}
		if($this->mysql_server=="127.0.0.1"){
			$this->mysql_server=":/var/run/mysqld/mysqld.sock";
			$this->SocketName="/var/run/mysqld/mysqld.sock";
		}
	
	}

	public function BD_CONNECT($noretry=false,$called=null){
	
		if(trim($this->mysql_admin)==null){$this->mysql_admin="root";}
		if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}
	
		if($this->SocketName<>null){
			$bd=@mysqli_connect("localhost",$this->mysql_admin,$this->mysql_password,null,0,$this->SocketName);
		}else{
			$bd=@mysqli_connect("$this->mysql_server",$this->mysql_admin,$this->mysql_password,null,$this->mysql_port);
		}
	
		if($bd){
			$this->mysqli_connection=$bd;
			return true;
		}
	
	
		if($GLOBALS["VERBOSE"]){echo "mysqli_connect $this->SocketName -> error<br>\n";}
		$des=@mysqli_error(); $errnum=@mysqli_errno();
		$this->ToSyslog($this->BD_CONNECT_ERROR);
		return false;
	}

	private function ToSyslog($text,$error=false){
		$text=str_replace("\n", " ", $text);
		$text=str_replace("\r", " ", $text);
			
			
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				$function="{$trace[1]["function"]}()";
				$line="{$trace[1]["line"]}";
			}
		}
			
		$text="{$function}[$line]:$text";
		if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
		if(function_exists("openlog")){openlog("mysql-meta", LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}

	public function TABLE_EXISTS($table){
		$keyCache=__FUNCTION__;
		$database=trim($this->database);
		
		if(function_exists("debug_backtrace")){
			try {
				$trace=@debug_backtrace();
				if(isset($trace[1])){$called="\ncalled by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
			} catch (Exception $e) {$this->writeLogs("TABLE_EXISTS:".__LINE__.": Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
			
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
			
			
		if(!$this->DATABASE_EXISTS($database)){
			$this->writeLogs("Database $database does not exists...create it",__CLASS__.'/'.__FUNCTION__,__FILE__);
			if(!$this->CREATE_DATABASE($database)){
				$this->writeLogs("Unable to create $database database",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
		}
			
		$sql="SHOW TABLES";
		$results=$this->QUERY_SQL($sql,$database,$called);
		$result=false;
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$GLOBALS[$keyCache][$database][$ligne["Tables_in_$database"]]=true;
			if(!$GLOBALS["AS_ROOT"]){$_SESSION[$keyCache][$database][$ligne["Tables_in_$database"]]=true;}
			if(strtolower($table)==strtolower($ligne["Tables_in_$database"])){$result=true;}
		}
			
		return $result;
			
	}	
	
	private function writelogs($text=null,$function=null,$line=0){
		$file_source=PROGRESS_DIR."/mysql.meta.debug";
		@mkdir(dirname($file_source));
		if(!is_numeric($line)){$line=0;}
		if(function_exists("writelogs")){
			writelogs("$text (L.$line)",__CLASS__."/$function",__FILE__,$line);
		}
		if(!$GLOBALS["VERBOSE"]){return;}
		$logFile=$file_source;
		if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
		if (is_file($logFile)) {$size=filesize($logFile);if($size>1000000){unlink($logFile);}}
		$f = @fopen($logFile, 'a');
		$date=date("Y-m-d H:i:s");
		@fwrite($f, "$date:[".__CLASS__."/$function()][{$_SERVER['REMOTE_ADDR']}]:: $text (L.$line)\n");
		@fclose($f);
	}	
	
	function QUERY_SQL($sql,$database=null,$called=null){
		$showMysqlQuery=null;
		$database=trim($this->database);
			
		if($called==null){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
				}
			}
		}
	
		if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}

	
		if(!$this->BD_CONNECT(false,$called)){
			if($GLOBALS["VERBOSE"]){echo "Unable to BD_CONNECT class mysql/QUERY_SQL $this->mysql_admin@$this->mysql_server:$this->mysql_port \n";}
			if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}squid_admin_mysql(2, "MySQL error DB:\"$database\" Error, unable to connect to MySQL server, request failed\n$called" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
			$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error, unable to connect to MySQL server, request failed called:$called",__CLASS__.'/'.__FUNCTION__,__LINE__);
			$this->ok=false;
			$this->mysql_error=$this->mysql_error ." Error, unable to connect to MySQL server";
			return false;
		}
		if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("BD_CONNECT OK<br>",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	
		if(preg_match("#DROP TABLE\s+(.+)$#i", $sql,$re)){
			$TableDropped=$re[1];
			if(function_exists("system_admin_events")){
				$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
				squid_admin_mysql(2, "MySQL table $database/$TableDropped was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");
			}
		}
	
	
		if($database<>null){
			if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("mysql_select_db  > $database<br>",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$ok=@mysqli_select_db($this->mysqli_connection,$database);
				
			if (!$ok){
					$errnum=@mysqli_errno($this->mysqli_connection);
					$des=@mysqli_error($this->mysqli_connection);
					if($GLOBALS["VERBOSE"]){
						echo "mysql_select_db [FAILED] \"$database\"  N.$errnum DESC:$des mysql/QUERY_SQL<br>\n";
						echo "mysql_select_db [FAILED] \"$database\"  config:$this->mysql_server:$this->mysql_port<br>\n";
						echo "mysql_select_db [FAILED] \"$database\"  $called<br>\n";
					}
					$this->mysql_errornum=$errnum;
					$this->mysql_error=$des;
					$time=date('h:i:s');
				   
					$page=$this->CurrentPageName;
				
					if(preg_match("#Access denied for user#", $des)){
						unset($GLOBALS["ERROR_MYSQL_NOPASSWD"]);
						unset($GLOBALS["MYSQL_PARAMETERS"]);
						unset($_SESSION["MYSQL_PARAMETERS"]);
						$des=" please retry again...\n".$des;
					}
				   
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin ($called)",__CLASS__.'/'.__FUNCTION__,__LINE__);
					$this->mysql_error="QUERY_SQL:".__LINE__.": mysqli_select_db:: Error $errnum ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin line:".__LINE__;
				   
					$this->ok=false;
					if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
					return null;
				}
	
				if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("mysql_select_db \"$database\" OK<br>",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			}
	
	
			if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("mysqli_query($sql)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$results=mysqli_query($this->mysqli_connection,$sql);
	
			if(isset($GLOBALS["DEBUG_PROCESS"])){writelogs("mysqli_query() DONE...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			//if(preg_match("#Got error 134 from storage engine#i", $q->mysql_error)){
			if(!$results){
				$errnum=@mysqli_errno($this->mysqli_connection);
				$des=@mysqli_error($this->mysqli_connection);
				if($GLOBALS["VERBOSE"]){echo "mysql_query [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
					
					
					
					
				if(preg_match("#Got error 134 from storage engine#i", $des)){
					if(preg_match("\s+from\s+(.*?)\s+#i", $sql,$re)){
						$table=$re[1];
						if($this->TABLE_EXISTS($table, $database)){
							$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" try to repair table \"$table\"",__CLASS__.'/'.__FUNCTION__,__LINE__);
							mysqli_query($this->mysqli_connection,"REPAIR TABLE $table QUICK");
							$results=mysqli_query($this->mysqli_connection,$sql);
							}
					}
				}
				if($errnum==1548){
					$sock=new sockets();
					$sock->getFrameWork("mysql.php?mysql-upgrade=yes&instance-id=$this->instance_id");
					$this->mysql_error=$this->mysql_error."\nArtica as performed a mysql_upgrade, please retry later\n";
				}
			}
	
	
	
			if(!$results){
				$errnum=@mysqli_errno($this->mysqli_connection);
				$des=@mysqli_error($this->mysqli_connection);
				@mysqli_free_result($this->mysqli_connection);
				if($GLOBALS["VERBOSE"]){echo "mysql_query [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
				if($des<>null){if(!$GLOBALS["AS_ROOT"]){if($GLOBALS["VERBOSE"]){echo "\n<br><strong>Error $errnum $des ".__CLASS__.'/'.__FUNCTION__." in line ". __LINE__."</strong><br><i>$called</i><br><code>$sql</code>";}}}
			}
	
			if(@mysqli_error($this->mysqli_connection)){
				$time=date('h:i:s');
				$errnum=@mysqli_errno($this->mysqli_connection);
				@mysqli_free_result($this->mysqli_connection);
				$des=@mysqli_error($this->mysqli_connection);
				if($GLOBALS["VERBOSE"]){echo "mysql_query [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
				if(!$GLOBALS["AS_ROOT"]){if($GLOBALS["VERBOSE"]){echo "\n<br><strong>Error $errnum $des ".__CLASS__.'/'.__FUNCTION__." in line ". __LINE__."</strong>";}}
				if($GLOBALS["AS_ROOT"]){if($GLOBALS["VERBOSE"]){echo "Error $errnum $des ".__CLASS__.'/'.__FUNCTION__." in line ". __LINE__."\n";}}
					
				if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called="\ncalled by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
						} catch (Exception $e) {$this->writeLogs("QUERY_SQL:".__LINE__.": Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}
		
				$this->mysql_error="Error $this->mysql_server:$this->mysql_port ($this->SocketName) $des $called ". basename(__FILE__)." on line ".__LINE__;
				if($database=="catz"){$this->ok=false;return false;}
		
				if(preg_match("#Incorrect key file for table '.\/(.+?)/(.+?)\.MYI'#",$des,$re)){
					$this->mysql_error="Error $errnum $des\n$sql";
					if($database=="artica_events"){
						if(!isset($GLOBALS["DROPPED_TABLE"])){
							$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[1]} is crashed $errnum -> remove it",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
							$this->QUERY_SQL("DROP TABLE `$re[1]`",$database);
							$GLOBALS["DROPPED_TABLE"]=true;
							return false;
						}
					}
					
					
					$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[1]} is crashed $errnum -> i will try to repair it ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					
					}
	
			if(preg_match("#Incorrect key file for table '(.+)'#",$des,$re)){
				$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[1]} is crashed $errnum -> i will try to repair it ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->ok=false;
				$this->mysql_error="Error $errnum $des\n$sql";
				if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
				return false;
			}
	
			if(preg_match("#Can't find file: '(.+?)'#",$des,$re)){
			if(!$GLOBALS["AS_ROOT"]){
	
			if(isset($databasesKnown[$database])){
			$js="Loadjs('system.mysql.table.crashed.php?database=$database&table={$re[1]}')";
			$href="<a href=\"javascript:blur();\"
							OnClick=\"javascript:$js\" style='text-decoration:underline'>";
			}
	
			}
					
				if($GLOBALS["AS_ROOT"]){if($GLOBALS["VERBOSE"]){
	
				echo "Line:".__LINE__."::DB:$database Can't find file \"{$re[1]}\" is crashed REPAIR_TABLE_NOFILE_FOUND($database,$re[1]) -> Err.$errnum\n";
	
				}}
					
				$this->ok=false;
				$this->mysql_error="Error $errnum $des\n$sql";
				$this->ToSyslog("Error $errnum $des $sql");
					if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
					return false;
				}
	
				if(preg_match("#File '\./(.+?)\/(.+?)\.MYD' not found.*?Errcode: 13#i",$des,$re)){
					
				$database=$re[1];
				$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[2]} file not found  $errnum -> i will try to repair it ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->REPAIR_TABLE_FILE_NOT_FOUND($database,$re[2]);
					$this->ok=false;
						$this->mysql_error="Error $errnum $des\n$sql";
						$this->ToSyslog("Error $errnum $des $sql");
						if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
						return false;
				}
	
				if(preg_match("#File '\.\/(.+?)\/(.+?)\.MYD' not found#",$des,$re)){
				$database=$re[1];
				$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[2]} file not found  $errnum -> i will try to repair it ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->REPAIR_TABLE_FILE_NOT_FOUND($database,$re[2]);
				$this->ok=false;
					$this->mysql_error="Error $errnum $des\n$sql";
						if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
								return false;
				}
	
								if(preg_match("#Error writing file '\.\/(.+?)/(.+?)'#",$des,$re)){
								$database=$re[1];
								if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
						$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[2]} Error writing file  $errnum -> i will try to repair it ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
						$this->REPAIR_TABLE_FILE_NOT_FOUND($database,$re[2]);
						$this->ok=false;
						if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
						return false;
						}
	
	
							
						if(preg_match("#Table '(.+?)' is marked as crashed and should be repaired#",$des,$re)){
						$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[1]} is crashed $errnum -> i will try to repair it ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
						$this->ok=false;
						$this->mysql_error="Error $errnum $des $sql Line:".__LINE__;
					if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
					return false;
			}
	
			if(preg_match("#Table '\.\/(.+?)\/(.+?)'\s+is marked as crashed and last#",$des,$re)){
			if(!$GLOBALS["AS_ROOT"]){
			$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[2]} is crashed $errnum -> i will try to repair it",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
						}else{
						if($this->myisamchk($database,$re[2])){
						$this->ok=true;$this->mysql_error=null;return $this->QUERY_SQL($sql,$database);
			}
			}
			}
	
			if(preg_match("#Access denied for user#", $des)){
			unset($GLOBALS["ERROR_MYSQL_NOPASSWD"]);
			unset($GLOBALS["MYSQL_PARAMETERS"]);
			unset($_SESSION["MYSQL_PARAMETERS"]);
	
			$this->mysql_error=$this->mysql_error." please retry...";
			$this->ok=false;
				if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
				return;
			}
	
			if(preg_match("#Table\s+'\.\/(.+?)\/(.+?)'\s+is marked as crashed#",$des,$re)){
					echo "<p class=text-error>Line:".__LINE__."::". __CLASS__."::DB:$database Error table {$re[2]} is crashed $errnum : i will try to repair it (as root={$GLOBALS["AS_ROOT"]})\n<br>$called </p>\n";
						if(!$GLOBALS["AS_ROOT"]){
						$this->REPAIR_TABLE($re[1],$re[2]);
						$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error table {$re[1]} is crashed $errnum -> i will try to repair it, $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				}else{
				$this->myisamchk($database,$re[2]);
				die("DIE " .__FILE__." Line: ".__LINE__);
				}
				}
	
				if(preg_match('#Duplicate entry#',$des)){
				if($GLOBALS["VERBOSE++"]){
				$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error $errnum $des line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" ". substr($sql,0,255)."...line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->writelogs($sql,__CLASS__.'/'.__FUNCTION__,__FILE__);
					}
					$this->ok=true;
						@mysqli_free_result($this->mysqli_connection);
						if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
								return true;
				}
	
								if(preg_match("#Incorrect file format '(.+?)'#",$des,$re)){
								$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error $errnum $des line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
						$table=trim($re[1]);
						if($table<>null){
						$this->ok=false;
						$this->mysql_error="Incorrect file format for table $table";
						$this->REPAIR_TABLE($database,$table);
						$this->mysql_error="Error $errnum $des\n$sql";
						return false;
								if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
					}
						if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
						return null;
						}
	
	
						if(preg_match('#/(.+?)/(.+?)\'\s+.+?crashed.+?(repaired|repair)#',$des,$re)){
						if(!preg_match('#REPAIR TABLE#',$sql)){
						$this->writeLogs("Try to repair {$re[2]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
						$this->mysql_error=$des." Artica answer Try to repair {$re[2]}";
						$this->REPAIR_TABLE($re[1],$re[2]);
						$this->ok=false;
						$this->mysql_error="Error $errnum $des\n$sql";
						if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
						return null;
					}
				}
	
				
				if(preg_match("#Table.+?quarantine.+?doesn't exist#",$des)){
					if(!$GLOBALS["CHECKMYSQLTABLE"]["quarantine"]){
					$this->writeLogs("Try to build quarantine table",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$GLOBALS["CHECKMYSQLTABLE"]["quarantine"]=true;
					$this->Check_quarantine_table();
					$this->ok=true;
					if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
					return null;
						}
							
						}
	
						if(preg_match("#Table.+?storage.+?doesn't exist#",$des)){
							if(!$GLOBALS["CHECKMYSQLTABLE"]["storage"]){
								$GLOBALS["CHECKMYSQLTABLE"]["storage"]=true;
								$this->writeLogs("Try to build storage table",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
								$this->check_storage_table();
						$this->ok=true;
						$this->mysql_error="Error $errnum $des\n$sql";
						if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
						return null;
						}
							
	}
	
	if(preg_match("#Column count doesn.*?match value count#i", $des)){$showMysqlQuery="\n$sql";}
			if($called==null){
			if(function_exists("debug_backtrace")){
	$trace=@debug_backtrace();
						if(isset($trace[1])){
							$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
	}
	}
	}
	
	$this->ToSyslog("Error $des call:$called \"$sql\"");
	$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error $des call:$called \"$sql\" line:".__LINE__." of class.mysql.inc",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	
	
	$this->affected_rows=@mysqli_affected_rows($this->mysqli_connection);
	
		if($errnum==1062){
		$this->ok=true;
		@mysqli_free_result($this->mysqli_connection);
				if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
				return true;
		}
	
		$this->ok=false;
				@mysqli_free_result($this->mysqli_connection);
				if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
				return null;
	
	
	
	}
	
	$this->last_id=@mysqli_insert_id($this->mysqli_connection);
	$this->mysql_affected_rows=@mysqli_affected_rows($this->mysqli_connection);
	$this->ok=true;
	$result_return=$results;
	@mysqli_free_result($this->mysqli_connection);
	if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
	return $result_return;
	}
	
	public function LIST_TABLES_SQUID_HOURLY(){
		if(isset($GLOBALS["LIST_TABLES_SQUID_HOURLY"])){return $GLOBALS["LIST_TABLES_SQUID_HOURLY"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = '$this->database' AND table_name LIKE 'squid_hourly_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysqli_num_rows($results)."\n";}
	
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			if(preg_match("#squid_hourly_[0-9]+#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_SQUID_HOURLY"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;
	
	}
	
	public function LIST_TABLES_SQUID_DAILY(){
		if(isset($GLOBALS["LIST_TABLES_SQUID_DAILY"])){return $GLOBALS["LIST_TABLES_SQUID_DAILY"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = '$this->database' AND table_name LIKE 'squid_daily_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysqli_num_rows($results)."\n";}
		
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			if(preg_match("#squid_daily_[0-9]+#", $ligne["c"])){
				$GLOBALS["LIST_TABLES_SQUID_DAILY"][$ligne["c"]]=$ligne["c"];
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $array;
				
		
	}
	
	
	public function systems_tables(){
			if(isset($GLOBALS["meta_systems_tables"])){return;}
		

		
			
			$GLOBALS["meta_systems_tables"]=true;
		
	}

	
	public function create_squid_hourly($tablename){
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
				`zmd5` varchar(90) NOT NULL,
				`ipaddr` varchar(90) NOT NULL,
				`zDate` DATETIME NOT NULL,
				`uid` varchar(90),
				`mac` varchar(90),
				`sitename` varchar(128),
				`hits` INT UNSIGNED,
				`size` INT UNSIGNED,
				PRIMARY KEY (`zmd5`),
				KEY `mac` (`mac`),
				KEY `sitename` (`sitename`),
				KEY `uid` (`uid`),
				KEY `ipaddr` (`ipaddr`),
				KEY `zDate` (`zDate`)
				) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "$this->database -> CREATE $tablename\n";}
		if(!$this->ok){return false;}
		return true;
		
	}
	
}
