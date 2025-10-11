<?php
/*
* phpMyDumper
* -------------
* Version: 1.10
* Copyright (c) 2009 by Micky Holdorf
* Holdorf.dk/Software - micky.holdorf@gmail.com
* GNU Public License http://opensource.org/licenses/gpl-license.php
*
*/


class phpMyDumper {
	/**
	* @access private
	*/
	var $database = null;
	var $connection = null;
	var $compress = null;
	var $hexValue = null;
	var $dropTable = null;
	var $createTable = null;
	var $tableData = null;
	var $expInsert = null;
	var $phpMyAdmin = null;
	var $utf8 = null;
	var $autoincrement = null;

	var $filename = null;
	var $file = null;
	var $filestream = null;
	var $outputTofile = false;
	var $isWritten = false;
	var $tablename=null;

	/**
	* Class constructor
	* @param string $db The database name
	* @param string $connection The database connection handler
	* @param boolean $compress It defines if the output/import file is compressed (gzip) or not
	* @param string $filepath The file where the dump will be written
	*/
	function __construct($db=null, $connection=null, $filepath='dump.sql', $compress=false,$tablename=null) {
		$this->connection = $connection;
		$this->compress = $compress;
		$this->database=$db;
		$this->hexValue = false;
		$this->dropTable = true;
		$this->createTable = true;
		$this->tableData = true;
		$this->expInsert = false;
		$this->phpMyAdmin = true;
		$this->utf8 = true;
		$this->autoincrement = false;
		$this->tablename=$tablename;
		$this->outputTofile = ( $filepath!='' ) ? true : false;
		if ( $this->outputTofile && !$this->setOutputFile($filepath) ) {
			//if filepath is null, then we want stream
			return false;
		}
		if($db<>null){
			return $this->setDatabase($db);
		}
	}

	/**
	* Sets the database to work on
	* @param string $db The database name
	*/
	function setDatabase($db){
		if(trim($db)==null){
			if($this->database==null){
				return true;}else{$db=$this->database;}
		}
		$this->database = $db;
		if ( !@mysqli_select_db($this->connection,$this->database) ){
			if($GLOBALS["VERBOSE"]){echo "$db::mysqli_select_db($db) -> False...\n";}
			return false;}
		return true;
  	}

	/**
	* Sets the output file
	* @param string $filepath The file where the dump will be written
	*/
	function setOutputFile($filepath) {
		if ( $this->isWritten ){
			return false;
		}
		if($GLOBALS["VERBOSE"]){echo "Creating file '".$filepath."': ";}
		@mkdir(dirname($filepath),0777);
		if(!is_dir(dirname($filepath))){
			echo "Creating file '".dirname($filepath)." no such directory': ";
			return false;
		}
		
		$this->filename = $filepath;
		$this->file = $this->openFile($this->filename);
		if($GLOBALS["VERBOSE"]){echo " DONE!\n";}
		return $this->file;
  	}
	
	/**
 	* Writes to file all the selected database tables structure with SHOW CREATE TABLE
	* @param string $table The table name
	*/
	function getTableStructure($table) {
		// Header
		$structure = "\n-- --------------------------------------------------------\n";
		$structure .= "-- \n";
		$structure .= "-- Table structure for table `$table`\n";
		$structure .= "-- \n\n";

		// Dump Structure
		if ( $this->dropTable )
			$structure .= "DROP TABLE IF EXISTS `$table`;\n";
		$records = @mysqli_query($this->connection,"SHOW CREATE TABLE `$table`");
		if ( @mysqli_num_rows($records) == 0 )
			return false;
		while ( $record = @mysqli_fetch_assoc($records) ) {
			$structure .= $record['Create Table'];
		}
		$records = @mysqli_query($this->connection,"SHOW TABLE STATUS LIKE '$table'");
		while ( $row = @mysqli_fetch_assoc($records) ) {
			if ($this->autoincrement AND $row['Name']==$table AND $row['Auto_increment']!='') {
				$structure .= " AUTO_INCREMENT=".$row['Auto_increment'];
			}
		}
		$structure .= ";\n";
		$this->saveToFile($this->file,$structure);
  	}

	/**
	* Writes to file the $table's data
	* @param string $table The table name
	* @param boolean $hexValue It defines if the output is base 16 or not
	*/
	function getTableData($table,$hexValue = true) {
		if($GLOBALS["AS_ROOT"]){echo "Exporting MySQL table `$table`\n";}
		// Header
		$data = "\n-- --------------------------------------------------------\n";
		$data .= "-- \n";
		$data .= "-- Dumping data for table `$table`\n";
		$data .= "-- \n\n";
			
		// Field names
		if ($this->expInsert || $this->hexValue) {
			$records = @mysqli_query($this->connection,"SHOW FIELDS FROM `$table`");
			$num_fields = @mysqli_num_rows($records);
			if ( $num_fields == 0 )
				return false;
			$hexField = array();

			$insertStatement = "INSERT INTO `$table` (";
			$selectStatement = "SELECT ";
			for ($x = 0; $x < $num_fields; $x++) {
				$record = @mysqli_fetch_assoc($records);
				if ( ($hexValue) && ($this->isTextValue($record['Type'])) ) {
					$selectStatement .= 'HEX(`'.$record['Field'].'`)';
					$hexField [$x] = true;
				}
				else
					$selectStatement .= '`'.$record['Field'].'`';
				
				$insertStatement .= '`'.$record['Field'].'`';
				$insertStatement .= ", ";
				$selectStatement .= ", ";
			}
			$insertStatement = @substr($insertStatement,0,-2).') VALUES';
			$selectStatement = @substr($selectStatement,0,-2).' FROM `'.$table.'`';
		}
		if (!$this->expInsert)
			$insertStatement = "INSERT INTO `$table` VALUES";
		if (!$this->hexValue)
			$selectStatement = "SELECT * FROM $table";
		
		// Dump data
		$records = @mysqli_query($this->connection,$selectStatement);
		$num_rows = @mysqli_num_rows($records);
		$num_fields = @mysql_num_fields($records);

		$procent = 0;
		for ($i = 1; $i <= $num_rows; $i++) {
			$data .= $insertStatement;
			$record = @mysqli_fetch_assoc($records);
			$data .= ' (';
			for ($j = 0; $j < $num_fields; $j++) {
				$field_name = @mysql_field_name($records, $j);
				if (is_null($record[$field_name])) {
				 	$data .= "NULL";
				} 
				else {
					if ( isset($hexField[$j]) && (@strlen($record[$field_name]) > 0) ) {
						$data .= "0x".$record[$field_name];
					}
					else {
						$data .= '\''.@str_replace('\"','"',@phpMyDumper::mysql_escape_string2($record[$field_name])).'\'';
					}
				}
				$data .= ',';
			}
			$data = @substr($data,0,-1).");\n";

			$this->saveToFile($this->file,$data);
			$data = '';
		}
	}
	
	public function mysql_escape_string2($line){
		$search=array("\\","\0","\n","\r","\x1a","'",'"');
		$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
		return str_replace($search,$replace,$line);
		if(function_exists("mysql_real_escape_string")){
			return mysql_real_escape_string($line);
		}
		return mysql_escape_string2($line);
	
	}

 	/**
	* Writes to file all the selected database tables structure
	* @return boolean
	*/
	function getDatabaseStructure() {
		if($GLOBALS["VERBOSE"]){echo "getDatabaseStructure($this->database) check tables\n";}
		$records = @mysqli_query($this->connection,'SHOW TABLES');
		if ( @mysqli_num_rows($records) == 0 ){
			if($GLOBALS["VERBOSE"]){echo "mysql_num_rows = 0 for `SHOW TABLES` ??\n";}
			return false;
		}
		while ( $record = @mysqli_fetch_row($records) ) {
			if($GLOBALS["VERBOSE"]){echo "Scanning = `{$record[0]}`\n";}
			
			if(!$this->TablesSelector($record[0])){
				if($GLOBALS["VERBOSE"]){echo "Skip table structure for '".$record[0]."': ";}
				continue;
			}
			if($GLOBALS["VERBOSE"]){echo "Exporting table structure for '".$record[0]."': ";}
			$this->getTableStructure($record[0]);
			if($GLOBALS["VERBOSE"]){echo " DONE!\n";}
		}
		return true;
 	}
 	
 	private function TablesSelector($tablename){
 		if(is_array($this->tablename)){
 			if(count($this->tablename)==0){return true;}
 			if(isset($this->tablename[$tablename])){return true;}
 			return false;
 		}
 		
 		if($this->tablename==null){return true;}
 		if($this->tablename<>$tablename){return false;}
 		return true;
 		
 	}

	/**
	* Writes to file all the selected database tables data
	* @param boolean $hexValue It defines if the output is base-16 or not
	*/
	function getDatabaseData($hexValue = true) {
		$records = @mysqli_query($this->connection,'SHOW TABLES');
		if ( @mysqli_num_rows($records) == 0 )
			return false;
		while ( $record = @mysqli_fetch_row($records) ) {
			if($this->tablename<>null){
				if($this->tablename<>$record[0]){
					echo "skip table data for '".$record[0]."': ";
					continue;
				}
			}
			
			if ($this->filename) echo "Exporting table data for '".$record[0]."': ";
			$this->getTableData($record[0],$hexValue);
			if ($this->filename) echo " DONE!\n";
		}
  	}

	/**
	* Writes to file all the selected database tables data
	* @param boolean $hexValue It defines if the output is base-16 or not
	*/
	function getDatabaseStructureData($hexValue = true){
		if($GLOBALS["VERBOSE"]){echo "getDatabaseStructureData($hexValue)\n";}
		$records = @mysqli_query($this->connection,'SHOW TABLES');
		if ( @mysqli_num_rows($records) == 0 ){
			if($GLOBALS["VERBOSE"]){echo "getDatabaseStructureData($hexValue) return 0 records!<br>\n";}
			return false;}
		while ( $record = @mysqli_fetch_row($records) ) {
			
			if(!$this->TablesSelector($record[0])){continue;}
			
			
			if ( $this->createTable) {
				$this->getTableStructure($record[0]);
			}
			if ( $this->tableData) {
				$this->getTableData($record[0],$hexValue);	
			}
		}
  	}

	/**
	* Writes the selected database to file 
	*/
	function doDump() {		
		
		if($GLOBALS["VERBOSE"]){echo "Database:'$this->database'<br>\n";}
		
		if ( !$this->setDatabase($this->database) ){
			if($GLOBALS["VERBOSE"]){echo "setDatabase($this->database) return false...\n";}
			return false;
		}

		if ( $this->utf8 ) {
			$encoding = @mysqli_query( $this->connection,"SET NAMES 'utf8'");
		}

		$cur_time=date("Y-m-d H:i");
		$server_info=mysql_get_server_info();
		$this->saveToFile($this->file,"-- Generation Time: $cur_time\n");
		$this->saveToFile($this->file,"-- MySQL Server Version: $server_info\n");
		$this->saveToFile($this->file,"-- Database: `$this->database`\n");

		if ($this->phpMyAdmin) {
			$this->getDatabaseStructureData($this->hexValue);
		}
		else {	
			if ( $this->createTable )
				$this->getDatabaseStructure();
			if ( $this->tableData )
				$this->getDatabaseData($this->hexValue);
		}
		
		if ($this->outputTofile){
			$this->closeFile($this->file);
			return true;
		}
		else {
			return $this->filestream;
		}
	}

 	/**
	* @access private
	*/
	function isTextValue($field_type) {
		switch ($field_type) {
			case "tinytext":
			case "text":
			case "mediumtext":
			case "longtext":
			case "binary":
			case "varbinary":
			case "tinyblob":
			case "blob":
			case "mediumblob":
			case "longblob":
				return True;
				break;
			default:
				return False;
		}
	}
	
	/**
	* @access private
	*/
	function openFile($filename) {
		$file = false;
		if ( $this->compress )
			$file = @gzopen($filename, "w9");
		else
			$file = @fopen($filename, "w");
		return $file;
	}

	/**
	* @access private
	*/
	function saveToFile($file, $data) {
		if ($this->outputTofile){
			if ( $this->compress )
				@gzwrite($file, $data);
			else
				@fwrite($file, $data);
			$this->isWritten = true;
		}
		else {
			$this->saveToStream($data);
		}
	}

	/**
	* @access private
	*/
	function saveToStream($data) {
		$this->filestream .= $data;
	}
	
	/**
	* @access private
	*/
	function closeFile($file) {
		if ( $this->compress )
			@gzclose($file);
		else
			@fclose($file);
	}
}
?>