<?php

class lib_sqlite{
    public $db_path="";
    public $last_id=0;
    var $ok=true;
    var $mysql_error;
    private $sql;
    private $ReadOnly=false;
    public 	$array_field_relay_tls=array(
        "none"=>"{none}",
        "may"=>"{Opportunistic_TLS}",
        "dane"=>"{DANE_SMTP}",
        "dane-only"=>"{DANE_ONLY}",
        "encrypt"=>"{Mandatory_TLS_encryption}",
        "verify"=>"{Mandatory_TLS_verification}",
        "secure"=>"{Secure_channel_TLS}");

    function __construct($database_path=null,$ro=false){
        $this->db_path=$database_path;
        $this->ReadOnly=$ro;

    }
    public function PatternToSearch($search):string{
        if($search==null){return "%";}
        $search=$this->SearchAntiXSS($search);
        $search="*$search*";
        $search=str_replace("**","*",$search);
        return str_replace("*","%",$search);

    }

    public function SearchAntiXSS($search_pattern):string{
        $search_pattern=trim($search_pattern);
        $search_pattern_src=$search_pattern;
        $search_pattern=str_replace("'",'',$search_pattern);
        $search_pattern=str_replace("(",'',$search_pattern);
        $search_pattern=str_replace(")",'',$search_pattern);
        $search_pattern=str_replace(";",'',$search_pattern);
        $search_pattern=str_replace("#",'',$search_pattern);
        $search_pattern=str_replace("/**",' ',$search_pattern);
        $search_pattern=str_replace("%00",' ',$search_pattern);
        $search_pattern=str_replace("%53",' ',$search_pattern);
        $search_pattern=str_replace("%45",' ',$search_pattern);
        $search_pattern=str_replace("%4c",' ',$search_pattern);
        $search_pattern=str_replace("%43",' ',$search_pattern);
        $search_pattern=str_replace("%54",' ',$search_pattern);
        $search_pattern=str_replace("%2553",' ',$search_pattern);
        $search_pattern=str_replace("%2545",' ',$search_pattern);
        $search_pattern=str_replace("%254c",' ',$search_pattern);
        $search_pattern=str_replace("%2545",' ',$search_pattern);
        $search_pattern=str_replace("%2543",' ',$search_pattern);
        $search_pattern=str_replace("%2554",' ',$search_pattern);
        $search_pattern=trim($search_pattern);
        if($search_pattern_src<>$search_pattern){
            admin_tracks("Suspicious SQL Injection attack discover [$search_pattern_src]");
        }
        if(preg_match("#(select|SELSELECTECT|delete)\s+from\s+#",$search_pattern)){
            admin_tracks("SQL Injection attack discover [$search_pattern]");
            return "";
        }


        if(preg_match('/(\s+|^)(and|or|union|where|limit|group by|select|information_schema|gRoUp_cOncaT|load_file|xmltype|convert_from|query_to_xml|database_to_xml|\'|hex|substr)\s+/i', $search_pattern)){
            admin_tracks("SQL Injection attack discover [$search_pattern]");
            return "";

        }
        return $search_pattern;
    }

    function FIELDS_TYPES($table){
        $ARRAY=array();
        $results=$this->QUERY_SQL("pragma table_info($table)");
        foreach ($results as $index=>$ligne){
            $ARRAY[$ligne["name"]]=strtoupper($ligne["type"]);

        }
        return $ARRAY;
    }

    public function FIELDS_LIST($table,$array=array()){
        $sql="SELECT m.name as tableName, p.name as columnName FROM sqlite_master m 
		left outer join pragma_table_info((m.name)) p on m.name <> p.name WHERE m.name='$table' order by tableName, columnName";
        $results=$this->QUERY_SQL($sql);
        if(!$this->ok){return false;}
        if(count($results)==0){return false;}
        foreach ($results as $index=>$ligne){
            if($GLOBALS["VERBOSE"]){echo "$table == {$ligne["columnName"]}<br>\n";}
            $array[]="FIELD:{$ligne["columnName"]}";
        }

        return $array;

    }

    public function FIELDS_LIST_FOR_QUERY($table){
        $array=array();
        $sql="SELECT m.name as tableName, p.name as columnName FROM sqlite_master m
		left outer join pragma_table_info((m.name)) p on m.name <> p.name WHERE m.name='$table' order by tableName, columnName";
        $results=$this->QUERY_SQL($sql);
        if(!$this->ok){return false;}
        if(count($results)==0){return false;}
        foreach ($results as $index=>$ligne){
            $array[]="{$ligne["columnName"]}";
        }

        return $array;

    }

    public function INDEX_EXISTS($stable,$sindexname){


        $md5=md5($this->db_path);
        if(!isset($GLOBALS["INDEX_EXISTS"][$md5])) {

            $sql = "SELECT DISTINCT m.name || '.' || ii.name AS 'indexed-columns'
              FROM sqlite_master AS m,
                   pragma_index_list(m.name) AS il,
                   pragma_index_info(il.name) AS ii
             WHERE m.type = 'table'
             ORDER BY 1;";

            $results = $this->QUERY_SQL($sql);

            foreach ($results as $index=>$ligne){

                $info=$ligne["indexed-columns"];
                if(preg_match("#(.+?)\.(.+)#",$info,$re)){
                    $table=strtolower($re[1]);
                    $indexz=strtolower($re[2]);
                    $GLOBALS["INDEX_EXISTS"][$md5][$table][$indexz]=true;
                }

            }


        }

        $stable=strtolower($stable);
        $sindexname=strtolower($sindexname);
        if(!isset($GLOBALS["INDEX_EXISTS"][$md5][$stable])){return false;}
        if(!isset($GLOBALS["INDEX_EXISTS"][$md5][$stable][$sindexname])){return false;}
        if($GLOBALS["INDEX_EXISTS"][$md5][$stable][$sindexname]){return true;}


        return false;

    }


    public function FIELD_EXISTS($table,$field){
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

    public function LIST_TABLES(){
        $MAIN=array();
        $sql="SELECT name FROM sqlite_master WHERE type ='table' AND name NOT LIKE 'sqlite_%';";
        $results=$this->QUERY_SQL($sql);
        foreach ($results as $index=>$ligne){
            $tablename=$ligne["name"];
            $MAIN[]=$tablename;
        }
        return $MAIN;
    }

    public function IF_TABLE_EXISTS($tablename){
        if(!extension_loaded("pdo_sqlite")){return false;}
        $database=basename($this->db_path);
        if(isset($GLOBALS["TABLES_$database"][$tablename])){return true;}

        $sql="Select * FROM $tablename LIMIT 1";
        try {
            $db = new PDO("sqlite:$this->db_path");
            //$db->setAttribute(PDO::ATTR_TIMEOUT, 5000);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e){
            $error=$e->getMessage();
            writelogs("[DB:$database]: $error",__FUNCTION__,__FILE__,__LINE__);
            return false;
        }

        try {
            $db->exec("PRAGMA busy_timeout=5000");
            $db->query($sql);
        } catch (PDOException $e){
            $error=$e->getMessage();
            writelogs("[DB:$database]: $error",__FUNCTION__,__FILE__,__LINE__);
            return false;
        }
        $GLOBALS["TABLES_$database"][$tablename]=true;
        return $GLOBALS["TABLES_$database"][$tablename];

    }



    public function COUNT_ROWS($table){
        $sql="SELECT count(*) as tcount from `$table`";
        $results=$this->QUERY_SQL($sql);
        if(!is_array($results)){return 0;}
        if(count($results)==0){return 0;}
        return $results[0]["tcount"];

    }

    public function BuildTables(){
        //for backward compatibilities.
    }


    public	function QUERY_SQL($sql){
        $this->ok=true;
        $chmod=false;
        $LASTID=false;

        if(strlen($sql)<6){
            return array();
        }

        $sql_log=$sql;
        if(strlen($sql_log)>512){
            $sql_log=substr($sql_log,0,512)."...";
        }
        if($this->db_path==null){$this->mysql_error="Line:". __LINE__." DB Path is null";return false;}
        if(!extension_loaded("pdo_sqlite")){
            $this->ok=false;
            $this->mysql_error="pdo_sqlite module no loaded";
            return array();
        }

        if(preg_match("#insert\s+into\s+(.+?)\s+#i", $sql,$re)){
            $LASTID=true;
            $LASTID_table=$re[1];
            $LASTID_table=str_replace("`", "", $LASTID_table);
            $LASTID_table=str_replace("'", "", $LASTID_table);
            $LASTID_table=str_replace('"', "", $LASTID_table);
        }

        $this->sql=$sql;
        if(!is_file($this->db_path)){$chmod=true;}
        $this->ok=true;
        $dsn="sqlite:$this->db_path";


        try {
            $db = new PDO($dsn);
            //$db->setAttribute(PDO::ATTR_TIMEOUT, 5000);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

            if($chmod){@chmod($this->db_path, 0777);}
        } catch (PDOException $e){
            $strace=@debug_backtrace();
            foreach ($strace as $index=>$trace){$zlines[]="File ".basename($trace["file"])." Function {$trace["function"]} Line:{$trace["line"]}";}
            $error=$e->getMessage();
            $this->ok=false;
            $this->mysql_error="Line:". __LINE__." ".$error ." in $dsn\n#<br>".@implode("\n#<br>", $zlines);
            $this->SQLITE_ERROR($e->getMessage(),debug_backtrace(),$sql_log);
            return array();
        }

        try {
            $db->exec("PRAGMA busy_timeout=5000");
            $results = $db->query($sql);
            if($this->ReadOnly){
                $db->exec("PRAGMA query_only = 1;");
            }
        } catch (PDOException $e){
            $strace=@debug_backtrace();
            foreach ($strace as $index=>$trace){$zlines[]="File ".basename($trace["file"])." Function {$trace["function"]} Line:{$trace["line"]}";}
            $error=$e->getMessage();
            $this->ok=false;
            $this->mysql_error="Line:". __LINE__." ".$error ." in $this->db_path\n#<br>".@implode("\n#<br>", $zlines);
            $db=null;
            $this->SQLITE_ERROR($e->getMessage(),debug_backtrace(),$sql_log);
            return array();
        }


        if ($results === false) {
            $strace=@debug_backtrace();
            if($GLOBALS["VERBOSE"]){echo "SQLITE ERROR[".__LINE__."] $this->db_path\n";}
            if($GLOBALS["VERBOSE"]){echo "SQLITE ERROR[".__LINE__."] $sql\n";}
            foreach ($strace as $index=>$trace){$zlines[]="File ".basename($trace["file"])." Function {$trace["function"]} Line:{$trace["line"]}";}
            $error = @implode("\n",$db->errorInfo());
            $serror[]=$error;
            $this->ok = false;
            $this->mysql_error="Line:". __LINE__." ".$error ." in $this->db_path\n#<br>".@implode("\n#<br>", $zlines);
            if(preg_match("#(duplicate column name|file is not a database|UNIQUE constraint|already exists|database schema has changed|database is locked)#is",@implode(" ", $serror))){
                return array();
            }

            writelogs("SQLITE Error: [0] $sql",__FUNCTION__,__FILE__,__LINE__);
            foreach ($db->errorInfo() as $index=>$text){
                writelogs("SQLITE Error: $index] $text $this->db_path",
                    __FUNCTION__,__FILE__,__LINE__);
                if(is_numeric($text)){continue;}
                if($GLOBALS["VERBOSE"]){echo "SQLITE ERROR[".__LINE__."] $text\n";}
            }

            if(preg_match("#readonly database#",@implode(" ", $serror))){
                $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/permissions");
            }


            $this->mysql_error = "<br>\n#SQL.".__LINE__." ".
                @implode("\n#<br>", $serror)."\n#In in $this->db_path\n#<br>".
                @implode("\n#<br>", $zlines);




            $this->SQLITE_ERROR($db->errorInfo(),debug_backtrace(),$sql_log);
            return array();
        }

        if(preg_match("#^DELETE\s+#i", $sql)){return true;}

        $FINAL=$results->fetchAll(PDO::FETCH_ASSOC);
        $db=null;
        if($LASTID){$this->get_last_id($LASTID_table);}
        return $FINAL;



    }



    private function SQLITE_ERROR($error,$debug,$sql){
        writelogs("SQLITE_ERROR $sql",__FUNCTION__,__FILE__,__LINE__);
        if(isset( $GLOBALS["SQLITE_NO_SAVE_ERRORS"])){return true;}
        if(preg_match("#(INSERT|CREATE).*?sql_errors#i",$sql)){return false;}
        if(strpos($sql,"adminprivs")>0){return true;}

        $subject="SQLite Error";
        $database_path=$this->db_path;
        if(!is_array($error)){
            $subject=str_replace("'","`",$error);
            $error=base64_encode($error);
        }else {
            $error = base64_encode(serialize($error));
        }
        $debug=base64_encode(serialize($debug));
        $sqlsrc=base64_encode($sql);

        $zdate=time();
        $sql="INSERT INTO sql_errors (zdate,subject,error,sql,database,debug) VALUES('$zdate','$subject','$error','$sqlsrc','$database_path','$debug')";
        $md=md5($sql);
        @file_put_contents("/usr/share/artica-postfix/ressources/logs/sql_errors/$md",$sql);
        return true;
    }

    private function get_last_id($table_name):bool{
        $this->last_id =0;
        $ligne=$this->mysqli_fetch_array("select seq from sqlite_sequence where name=\"$table_name\"");
        if(isset($ligne["seq"])) {
            $this->last_id = $ligne["seq"];
            return true;
        }
        return false;
    }


    public function mysqli_fetch_array($sql){
        $results=$this->QUERY_SQL($sql);
        if(!isset($results[0])){
            if(!$this->ok){VERBOSE($sql." Return none", __LINE__);VERBOSE($this->mysql_error, __LINE__);}
            return array();
        }
        return $results[0];

    }
    public function sqlite_escape_string2($line){
        $line=str_replace("'", "''", $line);
        return $line;
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