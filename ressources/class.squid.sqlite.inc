<?php

class sqlite_squid{
	public $db_path="";
	public $last_id=0;
	var $ok=true;
	var $mysql_error;
	private $sql;
	
	function __construct($database_path){
		$this->db_path=$database_path;
		
	}
	
	public function FIELD_EXISTS($table,$field,$none=null){
		$field=trim(strtolower($field));
		
		if(!isset($GLOBALS["FIELD_EXISTS"][$table])){
			$sql="SELECT m.name as tableName, p.name as columnName FROM sqlite_master m 
			left outer join pragma_table_info((m.name)) p on m.name <> p.name WHERE m.name='$table' order by tableName, columnName";
			$results=$this->QUERY_SQL($sql);
			if(!$this->ok){return false;}
			if(count($results)==0){return false;}
			foreach ($results as $index=>$ligne){$GLOBALS["FIELD_EXISTS"][$table][strtolower($ligne["columnName"])]=true; }
		}
		if(!isset($GLOBALS["FIELD_EXISTS"][$table][$field])){return false;}
		return true;
	}
	
	public function TABLE_EXISTS($tablename){return $this->IF_TABLE_EXISTS($tablename);}
	
	public function IF_TABLE_EXISTS($tablename){
		$database=basename($this->db_path);
		if(!isset($GLOBALS["TABLES_$database"])){
			$sql="Select name FROM sqlite_master WHERE type='table'";
			$results=$this->QUERY_SQL($sql);
			if(!$this->ok){return false;}
			if(count($results)==0){return false;}
			foreach ($results as $num=>$ligne){$GLOBALS["TABLES_$database"][$ligne["name"]]=true;}
		}
		if(!isset($GLOBALS["TABLES_$database"][$tablename])){return false;}
		return $GLOBALS["TABLES_$database"][$tablename];
		
	}
	
	
	public function COUNT_ROWS($table):int{
        if(!$this->TABLE_EXISTS($table)){
            return 0;
        }
		$sql="SELECT count(*) as tcount from `$table`";
		$results=$this->QUERY_SQL($sql);
		return intval($results[0]["tcount"]);
		
	}

	
	
	public	function QUERY_SQL($sql){
		$LASTID=false;
		if($this->db_path==null){$this->mysql_error="Line:". __LINE__." DB Path is null";return;}
		if(!extension_loaded("pdo_sqlite")){
			$this->ok=false;
			$this->mysql_error="pdo_sqlite module no loaded";
			return false;
		}
		
		if(preg_match("#insert\s+into\s+(.+?)\s+#i", $sql,$re)){
			$LASTID=true;
			$LASTID_table=$re[1];
			$LASTID_table=str_replace("`", "", $LASTID_table);
			$LASTID_table=str_replace("'", "", $LASTID_table);
			$LASTID_table=str_replace('"', "", $LASTID_table);
		}
		
		$this->sql=$sql;
		// Refuse to silently create a fresh empty DB if the live file is
		// briefly absent (during a daemon-side hot-swap, an admin script
		// moving files, a failed restore, etc.). The previous behaviour let
		// PDO create an empty SQLite file in DEFAULT rollback-journal mode
		// and chmod it 0777, which would obliterate the Go daemon's WAL
		// state AND commit the pending write into a brand-new blank DB.
		// Loud failure here is strictly better than silent data divergence.
		if(!is_file($this->db_path)){
			$this->ok=false;
			$this->mysql_error="Line:". __LINE__." database file does not exist: $this->db_path";
			return false;
		}
		$this->ok=true;

		try {
			$db = new PDO("sqlite:$this->db_path");
			$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT); //  | ERRMODE_EXCEPTION | ERRMODE_SILEN
			// Pin WAL journal mode. SQLite's WAL persistence only holds while
			// the -wal file exists on disk; if it's ever missing when we open
			// (concurrent checkpoint truncate, daemon hot-swap deleting the
			// WAL/SHM siblings, etc.) the DB silently demotes to rollback-
			// journal mode. Setting WAL here is idempotent when already WAL
			// and converts back from rollback-mode automatically.
			$db->exec("PRAGMA journal_mode=WAL");
			// Match the Go daemon's 30s busy_timeout. Default is 0ms which
			// turns every contention with the Go side's longer operations
			// (VACUUM INTO, CleanBadAcls, replication ImportFromJson) into
			// an immediate SQLITE_BUSY failure.
			$db->exec("PRAGMA busy_timeout=30000");
		} catch (PDOException $e){
			
			$trace=@debug_backtrace();
			foreach ($trace as $index=>$trace){$zlines[]="File ".basename($trace["file"])." Function {$trace["function"]} Line:{$trace["line"]}";}
			$error=$e->getMessage();
			$this->ok=false;
			$this->mysql_error="Line:". __LINE__." ".$error ."\n#<br>".@implode("\n#<br>", $zlines);
			return false;
		}
		
		try {
			$results = $db->query($sql);
		} catch (PDOException $e){
			$trace=@debug_backtrace();
			foreach ($trace as $index=>$trace){$zlines[]="File ".basename($trace["file"])." Function {$trace["function"]} Line:{$trace["line"]}";}
			$error=$e->getMessage();
			$this->ok=false;
			$this->mysql_error="Line:". __LINE__." ".$error ."\n#<br>".@implode("\n#<br>", $zlines);
			$db=null;
			return false;
		}
		
		
		if ($results === false) {
			$trace=@debug_backtrace();
			foreach ($trace as $index=>$trace){$zlines[]="File ".basename($trace["file"])." Function {$trace["function"]} Line:{$trace["line"]}";}
			$error = $db->errorInfo();
			$this->ok = false;
			foreach ($error as $index=>$text){
				if(is_numeric($text)){continue;}
				$serror[]=$text;
			}
			$this->mysql_error = "<br>\n#SQL.".__LINE__." ".@implode("\n#<br>", $serror)."\n#<br>".@implode("\n#<br>", $zlines);
			return false;
		}
		
		$FINAL=$results->fetchAll(PDO::FETCH_ASSOC);
		$db=null;
		if($LASTID){$this->get_last_id($LASTID_table);}
		return $FINAL;
		
		
		
}

	private function get_last_id($table_name){
		$ligne=$this->mysqli_fetch_array("select seq from sqlite_sequence where name=\"$table_name\"");
		$this->last_id=$ligne["seq"];
	}


	public function mysqli_fetch_array($sql){
		$results=$this->QUERY_SQL($sql);
		return $results[0];
		
	}

public function mysql_error_html($asnew=false,$sql=null){
	$asnew=true;
	if($sql<>null){$sql="<br><strong>$sql</strong>";}
	if($this->sql<>null){$this->sql="<br>$this->sql";$this->sql=str_replace("\n", "<br>", $this->sql);}
	$trace=@debug_backtrace();
	if(isset($trace[1])){
		$called="in ". basename($trace[1]["file"])." function {$trace[1]["function"]}() line {$trace[1]["line"]}";
	}

	if($asnew){
		return "<div class='alert alert-danger'>$this->mysql_error$this->sql$sql<br><i>$called</i></div>";
			
	}

	return "<p class=text-error>$this->mysql_error$this->sql$sql<br><i>$called</i></p>";

}
	
	
	
}