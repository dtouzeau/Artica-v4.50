<?php
ini_set("pgsql.auto_reset_persistent",1);
if(!isset($GLOBALS["OUTPUT"])){$GLOBALS["OUTPUT"]=false;}
if(!isset($GLOBALS["TITLENAME"])){$GLOBALS["TITLENAME"]="PostgresSQL";}

class postgres_sql{
    public $ok=false;
    private $connection;
    private $connection_string;
    private $database="proxydb";
    private $host="'/var/run/ArticaStats'";
    public $hostname="/var/run/ArticaStats";
    public $portname=null;
    public $isRemote=false;
    private $DisablePostGres=0;
    private $PgBouncerEnabled=0;

    public $mysql_error;
    public function __construct($check=false){
        $this->DisablePostGres=intval($this->GET_INFO("DisablePostGres"));
        $this->PgBouncerEnabled=intval($this->GET_INFO("PgBouncerEnabled"));
        if($this->DisablePostGres==1){return true;}
        if($check){
            if(!$this->DATABASE_EXISTS($this->database)){$this->CREATE_DATABASE($this->database);}
        }
        $this->LoadConfig();
        return true;
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

    private function GET_INFO($key):string{
        if(!isset($GLOBALS["CLASS_SOCKETS"])){
            if(!class_exists("sockets")){return "";}
            $GLOBALS["CLASS_SOCKETS"]=new sockets();
        }
        return $GLOBALS["CLASS_SOCKETS"]->GET_INFO($key);

    }

    private function LoadConfig(){

        $keys[]="InfluxUseRemote";
        $keys[]="InfluxApiIP";
        $keys[]="InfluxUseRemoteIpaddr";
        $keys[]="InfluxUseRemotePort";
        $keys[]="EnableInfluxDB";

        foreach ($keys as $num=>$token){
            if(is_file("/etc/artica-postfix/settings/Daemons/$token")){continue;}
            @touch("/etc/artica-postfix/settings/Daemons/$token");

        }


        $this->host="'/var/run/ArticaStats'";
        if($this->PgBouncerEnabled==1){
           // $this->host="'/var/run/ArticaStats' port=6432";
        }

        if(!isset($GLOBALS["InfluxApiIP"])) {
            $GLOBALS["InfluxApiIP"] = $this->GET_INFO("InfluxApiIP");
        }
        if($GLOBALS["InfluxApiIP"]==null){$GLOBALS["InfluxApiIP"]="127.0.0.1";}

        if(!isset($GLOBALS["InfluxSyslogRemote"])){
            $GLOBALS["InfluxSyslogRemote"]=intval($this->GET_INFO("InfluxSyslogRemote"));
        }

        if($GLOBALS["InfluxSyslogRemote"]==1){
            $GLOBALS["InfluxUseRemote"]=0;
            $this->isRemote=false;
        }

        if(!isset($GLOBALS["InfluxUseRemote"])){
            $GLOBALS["InfluxUseRemote"]=intval($this->GET_INFO("InfluxUseRemote"));
            if($GLOBALS["InfluxUseRemote"]==1){$this->isRemote=true;}

        }

        if(!isset($GLOBALS["InfluxUseRemoteIpaddr"])){
            $GLOBALS["InfluxUseRemoteIpaddr"]=trim($this->GET_INFO("InfluxUseRemoteIpaddr"));
        }

        if(!isset($GLOBALS["InfluxUseRemotePort"])){
            $GLOBALS["InfluxUseRemotePort"]=intval($this->GET_INFO("InfluxUseRemotePort"));
            if($GLOBALS["InfluxUseRemotePort"]==0){$GLOBALS["InfluxUseRemotePort"]=5432;}
        }
        if(!isset($GLOBALS["EnableInfluxDB"])){ $GLOBALS["EnableInfluxDB"]=1; }
        if($GLOBALS["InfluxUseRemote"]==1){
            $GLOBALS["InfluxApiIP"]=$GLOBALS["InfluxUseRemoteIpaddr"];
            $port=intval($GLOBALS["InfluxUseRemotePort"]);
            if($port==8086){$port=5432;}
            if($port==0){$port=5432;}
            $this->host="'{$GLOBALS["InfluxApiIP"]}' port='{$port}'";
            $this->hostname="{$GLOBALS["InfluxApiIP"]}";
            $this->portname=$port;
            $this->connection_string="$this->host $this->hostname:$this->portname";

        }



    }

    public function mysqli_fetch_array($sql=null){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }
        if($sql==null){return null;}
        if(!$this->connect()){return null;}
        $results = @pg_query($this->connection, $sql);
        if (!$results){
            $this->ok=false;
            $this->mysql_error=pg_last_error($this->connection);
            return false;
        }
        return @pg_fetch_assoc($results);
    }


    public function COUNT_ROWS_LOW($tablename){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return 0;
        }
        if(!$this->TABLE_EXISTS($tablename)){return 0;}
        $sql="SELECT count(*) as approximate_row_count from \"$tablename\"";
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}
        $ligne=$this->mysqli_fetch_array($sql);
        if(!$this->ok){
            if($GLOBALS["VERBOSE"]){
                echo $this->mysql_error."\n";
            }
        }

        return intval($ligne["approximate_row_count"]);
    }

    public function COUNT_ROWS($tablename){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return 0;
        }
        if(!$this->TABLE_EXISTS($tablename)){return 0;}
        $sql="SELECT reltuples AS approximate_row_count FROM pg_class WHERE relname = '$tablename'";
        if($GLOBALS["VERBOSE"]){echo "$sql\n";}
        if(!$this->connect()){return false;}
        $results = @pg_query ($this->connection, $sql);
        $ligne=$ligne=@pg_fetch_assoc($results);
        return intval($this->exp_to_dec($ligne["approximate_row_count"]));
    }

    function exp_to_dec($float_str){
        $float_str = (string)((float)($float_str));


        if(($pos = strpos(strtolower($float_str), 'e')) !== false){
            // get either side of the E, e.g. 1.6E+6 => exp E+6, num 1.6
            $exp = substr($float_str, $pos+1);
            $num = substr($float_str, 0, $pos);

            // strip off num sign, if there is one, and leave it off if its + (not required)
            if((($num_sign = $num[0]) === '+') || ($num_sign === '-')) $num = substr($num, 1);
            else $num_sign = '';
            if($num_sign === '+') $num_sign = '';

            // strip off exponential sign ('+' or '-' as in 'E+6') if there is one, otherwise throw error, e.g. E+6 => '+'
            if((($exp_sign = $exp[0]) === '+') || ($exp_sign === '-')) $exp = substr($exp, 1);
            else return $float_str;

            // get the number of decimal places to the right of the decimal point (or 0 if there is no dec point), e.g., 1.6 => 1
            $right_dec_places = (($dec_pos = strpos($num, '.')) === false) ? 0 : strlen(substr($num, $dec_pos+1));
            // get the number of decimal places to the left of the decimal point (or the length of the entire num if there is no dec point), e.g. 1.6 => 1
            $left_dec_places = ($dec_pos === false) ? strlen($num) : strlen(substr($num, 0, $dec_pos));

            // work out number of zeros from exp, exp sign and dec places, e.g. exp 6, exp sign +, dec places 1 => num zeros 5
            if($exp_sign === '+') $num_zeros = $exp - $right_dec_places;
            else $num_zeros = $exp - $left_dec_places;

            // build a string with $num_zeros zeros, e.g. '0' 5 times => '00000'
            $zeros = str_pad('', $num_zeros, '0');

            // strip decimal from num, e.g. 1.6 => 16
            if($dec_pos !== false) $num = str_replace('.', '', $num);

            // if positive exponent, return like 1600000
            if($exp_sign === '+') return $num_sign.$num.$zeros;
            // if negative exponent, return like 0.0000016
            else return $num_sign.'0.'.$zeros.$num;
        }
        // otherwise, assume already in decimal notation and return
        else return $float_str;
    }


    private function DATABASE_EXISTS($database){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }
        $database=trim($database);
        if(!$this->connect()){return false;}
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Check if $database exists\n";}
        $sql="SELECT datname, datdba FROM pg_database ORDER BY datname";
        $results = @pg_query ($this->connection, $sql);



        while($ligne=@pg_fetch_assoc($results)){
            if(strtolower($database)==strtolower($ligne["datname"])){
                $_SESSION["POSTGRES_DATABASE_EXISTS"][$database]=true;
                if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $database exists\n";}
                @pg_close($this->connection);
                return true;
            }
        }
        @pg_close($this->connection);

        return false;
    }

    public function fieldSelectore(){
        $BASEDIR="/usr/share/artica-postfix/ressources/interface-cache";
        $date1=intval(@file_get_contents("$BASEDIR/DATE_START"));
        $date2=intval(@file_get_contents("$BASEDIR/DATE_END"));
        $mintime=strtotime("2008-01-01 00:00:00");

        if($date1<$mintime){
            $date1=filemtime("/etc/artica-postfix/artica-iso-first-reboot");
        }

        if($date2<strtotime(date("Y-m-d H:i:s")." -5 hours")){
            $date2=time();

        }


        $date_start=date("Y-m-d",$date1);
        $date_end=date("Y-m-d",$date2);
        return "mindate:$date_start;maxdate:$date_end";
    }


    public function TABLE_EXISTS($table,$UseCache=false){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }
        $tableQuery=strtolower($table);
        if($UseCache){
            if(isset($GLOBALS["PG_TABLE_EXISTS"][$tableQuery])){return true;}
        }


        if(!function_exists("pg_connect")){
            $this->ok=false;$this->mysql_error="pg_connect(), no such function...";
            return false;
        }
        if(!$this->connect()){return false;}

        $sql="SELECT table_name FROM information_schema.tables WHERE table_schema='public'";
        $results = @pg_query ($this->connection, $sql);

        if($UseCache){
            if(!isset($GLOBALS["PG_TABLE_EXISTS"])){
                while($ligne=@pg_fetch_assoc($results)){
                    $GLOBALS["PG_TABLE_EXISTS"][strtolower($ligne["table_name"])]=true;
                }
                if(isset($GLOBALS["PG_TABLE_EXISTS"][$tableQuery])){return true;}
                return false;
            }
        }
        if(!$results){
            writelogs(@pg_last_error($this->connection),__FUNCTION__,__FILE__);
            return false;}
        while($ligne=@pg_fetch_assoc($results)){
            if($UseCache){$GLOBALS["PG_TABLE_EXISTS"][strtolower($ligne["table_name"])]=true;}
            if(strtolower($table)==strtolower($ligne["table_name"])){
                @pg_close($this->connection);
                return true;
            }
        }
        @pg_close($this->connection);

        return false;
    }
    public function LIST_TABLES($database){
        $array=array();
        if(!$this->connect()){return false;}
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return array();}
        $sql="SELECT table_name FROM information_schema.tables WHERE table_catalog='$database'";
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            writelogs(@pg_last_error($this->connection),__FUNCTION__,__FILE__);
            return array();}
        while($ligne=@pg_fetch_assoc($results)){
            $array[strtolower($ligne["table_name"])]=true;

        }
        @pg_close($this->connection);

        return $array;
    }



    function TABLE_SIZE($table){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return 0;
        }
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return 0;}
        if(!$this->connect()){return false;}
        $sql="SELECT
		relname AS objectname,
		relkind AS objecttype,
		reltuples AS \"#entries\", relpages::bigint AS size
		FROM pg_class
		WHERE relpages >= 8 AND relname LIKE '{$table}%'
				ORDER BY relpages DESC;";

        $results = @pg_query ($this->connection, $sql);
        $size=0;
        while($ligne=@pg_fetch_assoc($results)){
            $sizeB=$ligne["size"]*8;
            $sizeB=$sizeB*1024;
            $size=$size+$sizeB;
        }
        @pg_close($this->connection);
        return $size;
    }


    private function connect($database=null){
        if(!function_exists("pg_connect")){
            $this->ok=false;
            $this->mysql_error="pg_connect(), no such function...";
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} pg_connect no such function\n";}
            return false;
        }
        $user="ArticaStats";
        if(isset($GLOBALS["PG_USER"])){$user=$GLOBALS["PG_USER"];}
        $this->LoadConfig();
        if($database==null){$database=$this->database;}
        if(trim($this->host)==null){$this->host="127.0.0.1";}


        try {
            $this->connection = @pg_connect("host=$this->host user='$user' dbname='$database' connect_timeout='5'");
        }Catch (Exception $e) {
            $this->VERBOSE($e->getMessage(),__LINE__);
        }


        if(!$this->connection) {
            $this->VERBOSE("connection = FALSE host=$this->host",__LINE__);
            if (strpos($this->host, "ArticaStats") > 0) {
                $this->connection_string="127.0.0.1 x2";
                if($GLOBALS["VERBOSE"]){echo "pg_connect(\"host\t127.0.0.1' user='$user' dbname='$database' connect_timeout='5')\n";}
                $this->connection = @pg_connect("host='127.0.0.1' user='$user' dbname='$database' connect_timeout='5'");
            }
        }
        if(!$this->connection) {
            $this->mysql_error="Could not Connect to database service: $this->connection_string";
            $this->events_failed(__FUNCTION__,__LINE__);
            $this->ok=false;
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            return false;
        }
        $this->ok=true;
        return true;
    }

    public function CREATE_DATABASE($database){


        if($GLOBALS["AS_ROOT"]){
            $unix=new unix();
            $su=$unix->find_program("su");
            system("$su -c \"/usr/local/ArticaStats/bin/createdb -O ArticaStats -h /var/run/ArticaStats $database\" ArticaStats");
            if($this->DATABASE_EXISTS($database)){return true;}
        }


        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}
        if(!$this->connect()){return false;}
        $results = @pg_query ($this->connection, "CREATE DATABASE $database OWNER \"ArticaStats\"");
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            $this->events_failed(__FUNCTION__,__LINE__);
            return false;
        }
        $this->ok=true;
        @pg_close($this->connection);
        return true;
    }

    public function IMPORT_FILEDATA($path){
        pg_exec( $this->connection, "BEGIN" );
        $oid = intval(pg_lo_import( $this->connection, $path));
        pg_exec( $this->connection, "COMMIT" );
        return $oid;
    }


    public function QUERY_SQL($sql){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }

        if(!function_exists("pg_connect")){
            $this->ok=false;
            $this->mysql_error="pg_connect(), no such function...";
            return false;
        }

        if(!$this->connect($this->database)){return false;}
        $results = @pg_query($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            $this->events_failed(__FUNCTION__,__LINE__);
            $this->events("QUERY_SQL: Error: $this->mysql_error",__FILE__,__LINE__);
            $this->events("QUERY_SQL: [".substr($sql, 0,255)."]",__FILE__,__LINE__);
            return false;

        }

        $this->ok=true;
        @pg_close($this->connection);
        return $results;

    }

    public function smtprefused($hostname=null,$mailfrom=null,$mailto=null,$ipaddr=null,$reason=null){
        $date=date("Y-m-d H:i:s");
        $fromdomain=null;

        $this->ok=true;
        if($mailfrom<>null){
            $Zmailfrom=explode("@",$mailfrom);
            $fromdomain=$Zmailfrom[1];
        }
        if($mailto<>null){
            $zmailto=explode("@",$mailto);
            $todomain=$zmailto[1];
        }

        $smtp_code=0;
        $infected=0;
        $reason=str_replace("'", "", $reason);

        $trace=@debug_backtrace();
        foreach ($trace as $traceback){
            $filename=basename($traceback["file"]);
            $function=$traceback["function"];
            $line=$traceback["line"];
            events("$filename $function L.$line");

        }

        if(preg_match("#Message infected virus found\s+(.+?);#", $reason,$re)){return;}

        if(preg_match("#Infected:(.+)#i",$reason,$re)){
            $reason=$re[1];
            $smtp_code=0;
            $infected=1;
        }



        $reason=str_replace("'", "", $reason);
        $F[]="zdate";
        $F[]="fromdomain";
        $F[]="todomain";
        $F[]="frommail";
        $F[]="tomail";
        $F[]="ipaddr";
        $F[]="relay_s";
        $F[]="smtp_code";
        $F[]="refused";
        $F[]="infected";
        $F[]="reason";

        $D[]="'$date'";
        $D[]="'$fromdomain'";
        $D[]="'$todomain'";
        $D[]="'$mailfrom'";
        $D[]="'$mailto'";
        $D[]="'$ipaddr'";
        $D[]="'$hostname'";
        $D[]="$smtp_code";
        $D[]="1";
        $D[]="$infected";
        $D[]="'$reason'";

        $this->QUERY_SQL("INSERT INTO smtplog (".@implode(",", $F).") VALUES (".@implode(",", $D).")");

        if(!$this->ok){

            if(function_exists("events")){events(__CLASS__." Line.".__LINE__.": PostgreSQL Error");}
            $HASH["fromdomain"]=$fromdomain;
            $HASH["todomain"]=$todomain;
            $HASH["zdate"]=$date;
            $HASH["hostname"]=$hostname;
            $HASH["mailfrom"]=$mailfrom;
            $HASH["mailto"]=$mailto;
            $HASH["ipaddr"]=$ipaddr;
            $HASH["reason"]=$reason;
            $Serialized=serialize($HASH);
            $md5=md5(serialize($Serialized));
            if(!is_dir("/home/artica/postfix/smtprefused_failed")){@mkdir("/home/artica/postfix/smtprefused_failed",0755,true);}
            @file_put_contents("/home/artica/postfix/smtprefused_failed/$md5.log", $Serialized);
        }

    }

    public function mysql_error_html($asnew=false,$sql=null):string{
        $called=null;
        $sql="<br><strong>$sql</strong>";
        $trace=@debug_backtrace();
        if(isset($trace[1])){
            $called="in $asnew ". basename($trace[1]["file"])." function {$trace[1]["function"]}() line {$trace[1]["line"]}";
        }

        return "<div class='alert alert-danger'>$this->mysql_error$sql<br><i>$called</i></div>";
    }



    private function is_index_exists($tablename,$key):bool{
        if(!$this->ConnectionStatus()) {
            if (!$this->connect($this->database)) {
                return false;
            }
        }

        $sql="SELECT relname, indkey FROM pg_class, pg_index WHERE pg_class.oid = pg_index.indexrelid AND pg_class.oid IN ( SELECT indexrelid FROM pg_index, pg_class WHERE pg_class.relname='$tablename' AND pg_class.oid=pg_index.indrelid AND indisunique != 't' AND indisprimary != 't');";

        $results = @pg_query ($this->connection, $sql);

        while($ligne=@pg_fetch_assoc($results)){
            if(strtolower($ligne["relname"])==strtolower($key)){return true;}
        }

        return false;
    }

    public function create_index($table,$indexname,$fields=array()){
        if(!function_exists("pg_connect")){$this->ok=false;
            $this->mysql_error="pg_connect(), no such function...";return false;}
        $keyname="{$table}_$indexname";
        if($this->is_index_exists($table,$keyname)){return true;}

        if(!$this->ConnectionStatus()) {
            if (!$this->connect($this->database)) {
                return false;
            }
        }
        $sql="CREATE INDEX $keyname ON $table (".@implode(",", $fields).");";
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if(preg_match("#already exists#", $this->mysql_error)){ @pg_close($this->connection); return true; }
            $this->events_failed(__FUNCTION__,__LINE__);
            $this->events("create_index: $this->mysql_error ON $table,$indexname",__FILE__,__LINE__);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error ON $table,$indexname\n";}
            return false;

        }
        return true;

    }

    private function events($text=null,$file=null,$line=null){
        $date=date("Y-m-d H:i:s");
        $pid=getmypid();
        $file=basename($file);
        $maxSize=900000;
        $line="$date [$pid] $file: $text  - in line:$line\n";
        $logFile="/var/log/artica.postgres.log";


        $size=@filesize($logFile);
        if($size>$maxSize){
            @unlink($logFile);
            @touch($logFile);
            @chmod($logFile, 0777);
        }

        $h = @fopen($logFile, 'a');
        if(!$h){
            return;
        }
        @fwrite($h,$line);
        @fclose($h);


    }

    public function FIELDS_LIST($table,$array=array()){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return array();
        }
        if(!$this->connect($this->database)){$array[]=$this->mysql_error;return $array;}
        $sql="select column_name from information_schema.columns where table_name = '$table'";
        if($GLOBALS["VERBOSE"]){echo "$table == $sql<br>\n";}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $array[]=$this->mysql_error;
            return $array;
        }

        while($ligne=@pg_fetch_assoc($results)){
            if($GLOBALS["VERBOSE"]){echo "$table == {$ligne["column_name"]}<br>\n";}
            $array[]="FIELD:{$ligne["column_name"]}";
        }
        @pg_close($this->connection);
        return $array;

    }

    public function FIELD_EXISTS($table,$fieldname){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }
        $fieldname=strtolower($fieldname);
        if(!$this->connect($this->database)){return false;}
        $sql="select column_name from information_schema.columns where table_name = '$table'";
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            $this->events_failed(__FUNCTION__, __LINE__);
            @pg_close($this->connection);
            return false;

        }

        while($ligne=@pg_fetch_assoc($results)){
            $array[strtolower($ligne["column_name"])]=true;

        }
        @pg_close($this->connection);
        if(!isset($array[$fieldname])){
            $this->mysql_error="in $table,$fieldname does not exists";
            $this->events_failed(__FUNCTION__, __LINE__);
            return false;}
        return true;


    }

    public function ADD_FIELD($tablename,$COLUMN,$type){
        $default=null;
        if($type=="smallint"){$default=" NOT NULL DEFAULT 0";}
        $sql="ALTER TABLE $tablename ADD COLUMN $COLUMN $type$default";
        $this->QUERY_SQL($sql);
    }

    public function CREATE_CATEGORY_TABLE($tablename){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }
        $sql="CREATE TABLE IF NOT EXISTS \"$tablename\" ( sitename varchar(512) primary key )";
        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("$tablename","isitename",array("sitename"));
        $this->QUERY_SQL("DROP TABLE webfilters_categories_caches");
        $this->create_webfilters_categories_caches();
        return true;
    }

    private function create_webfilters_categories_caches(){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }

        $sql="CREATE TABLE IF NOT EXISTS webfilters_categories_caches (
			  categorykey varchar(50) primary key,
			  description varchar(255) NOT NULL,
			  picture varchar(50) NOT NULL,
			  master_category varchar(50) NOT NULL,
			  categoryname varchar(128) NOT NULL)";

        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("webfilters_categories_caches","icategoryname",array("categoryname","master_category"));
        return true;
    }

    public function ConnectionStatus():bool{

        if(is_null($this->connection)){
            return false;
        }

        if(is_bool( $this->connection)){
            if(!$this->connection){
                return false;
            }
        }

        if(!is_resource($this->connection)){
            return false;
        }

        try{
        if( pg_connection_status($this->connection) == PGSQL_CONNECTION_OK ) {
            return true;
        }
        }catch (Exception $e) {
            $this->mysql_error=$e->getMessage();
        }

        return false;
    }

    public function CREATE_NGINX_TABLES():bool{
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }
        if(!$this->connect($this->database)){
            if($this->mysql_error==null){$this->mysql_error="Unable to connect to database";}
            return false;
        }

        $sql="CREATE TABLE IF NOT EXISTS public_ips (
            id SERIAL PRIMARY KEY,
            ztime bigint,
            ipadress inet UNIQUE,
            countryname VARCHAR(40),
            city VARCHAR(40)
        )";

        $this->QUERY_SQL($sql);
        if(!$this->ok){return false;}

        $this->create_index("public_ips","idx_public_ips_addr",array("ipadress"));
        $this->create_index("public_ips","idx_data",array("countryname","city"));
        $this->create_index("public_ips","idx_time",array("ztime"));

        $sql="CREATE TABLE IF NOT EXISTS nginx_domains (
            id SERIAL PRIMARY KEY,
            domain VARCHAR(128) UNIQUE,
            serviceid bigint
           )";
        $this->QUERY_SQL($sql);
        if(!$this->ok){return false;}

        $this->create_index("nginx_domains","idx_nginx_domain",array("domain","serviceid"));


        $sql="CREATE TABLE IF NOT EXISTS nginx_paths (
            id SERIAL PRIMARY KEY,
            domainid bigint,
            paths VARCHAR(512)
           )";
        $this->QUERY_SQL($sql);
        if(!$this->ok){return false;}
        $this->create_index("nginx_paths","idx_nginx_paths",array("paths","domainid"));

        $sql="CREATE TABLE IF NOT EXISTS nginx_access (
            zdate       TIMESTAMP,
            domainid    bigint,
            serviceid   bigint,
            pathid      bigint,
            requests    bigint,
            size        bigint
           )";

        $this->QUERY_SQL($sql);
        if(!$this->ok){return false;}
        $this->create_index("nginx_access","idx_nginx_zdate",array("zdate"));
        $this->create_index("nginx_access","idx_nginx_ids",array("domainid","serviceid","pathid"));


        $sql="CREATE TABLE IF NOT EXISTS nginx_ipaccess (
            zdate       TIMESTAMP,
            domainid    bigint,
            serviceid   bigint,
            ipid        bigint,
            pathid      bigint,
            requests    bigint
           )";

        $this->QUERY_SQL($sql);
        if(!$this->ok){return false;}
        $this->create_index("nginx_access","idx_nginx_zdate",array("zdate"));
        $this->create_index("nginx_access","idx_nginx_ids",array("domainid","serviceid","ipid","pathid"));

        $sql="CREATE TABLE IF NOT EXISTS nginx_http_code_access (
            zdate       TIMESTAMP,
            domainid    bigint,
            serviceid   bigint,
            http_code   smallint ,
            pathid      bigint,
            requests    bigint
           )";

        $this->QUERY_SQL($sql);
        if(!$this->ok){return false;}
        $this->create_index("nginx_http_code_access","idx_nginx_zdate",array("zdate"));
        $this->create_index("nginx_http_code_access","idx_nginx_ids",array("domainid","serviceid","http_code","pathid"));



        return true;

    }

    public function CREATE_FILDESCRIPTORS():bool{
        $sql="CREATE TABLE IF NOT EXISTS sfiledescriptors (
            zdate TIMESTAMP,
            filedesc bigint,
          
        )";

        $this->QUERY_SQL($sql);
        if(!$this->ok) {return false;}
        $this->create_index("sfiledescriptors","idx_sfiledescriptors",array("zdate","filedesc"));

        $sql="CREATE TABLE IF NOT EXISTS sfiledescriptors_day (
            zdate TIMESTAMP,
            filedesc bigint,
          
        )";
        $this->create_index("sfiledescriptors","idx_sfiledescriptors",array("zdate","filedesc"));

        $this->QUERY_SQL($sql);
        if(!$this->ok) {return false;}

        return true;

    }

    public function CREATE_KSRN(){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }
        if(!$this->connect($this->database)){
            if($this->mysql_error==null){$this->mysql_error="Unable to connect to database";}
            return false;
        }

        $this->CREATE_STATSCOM();

        $maxChar=strlen("malwareurl");
        $sql="CREATE TABLE IF NOT EXISTS ksrn (
            zdate TIMESTAMP,
            username VARCHAR(90),
            ipaddr inet,
            mac macaddr,
            siteid bigint,
            provider VARCHAR($maxChar),
            duration int NOT NULL DEFAULT 0,
            category int
        )";


        $this->QUERY_SQL($sql);
        if(!$this->ok) {return false;}

        if(!$this->FIELD_EXISTS("ksrn","provider")){
            $this->ADD_FIELD("ksrn","provider","VARCHAR($maxChar)");
        }
        if(!$this->FIELD_EXISTS("ksrn","duration")){
            $this->ADD_FIELD("ksrn","duration","int");
        }

        $this->create_index("ksrn","idx_siteid",array("siteid"));
        $this->create_index("ksrn","idx_category",array("category"));
        $this->create_index("ksrn","idx_time",array("zdate","duration"));
        $this->create_index("ksrn","idx_provider",array("provider"));

        $sql="CREATE TABLE IF NOT EXISTS ksrn_white ( zdate bigint, sitename VARCHAR(128) PRIMARY KEY )";
        $this->QUERY_SQL($sql);
        if(! $this->ok) {return false;}
        $this->create_index("ksrn_white","idx_time",array("zdate"));

        $sql="CREATE TABLE IF NOT EXISTS ksrn_lines (zdate TIMESTAMP,requests bigint)";
        $this->QUERY_SQL($sql);
        if(!$this->ok) {return false;}

        $this->create_index("ksrn_lines","idx_dates",array("zdate"));
        $this->create_index("ksrn_lines","idx_count",array("requests"));




        return true;

    }

    public function create_sandbox_table():bool{
        $sql="CREATE TABLE IF NOT EXISTS cicap_sandbox(
            id SERIAL PRIMARY KEY,
            md5file VARCHAR(90) UNIQUE,
            workname VARCHAR(128) UNIQUE,
            filetime bigint,
            filesize bigint,
            filename VARCHAR(255),
            uri  VARCHAR(255),
            content_type VARCHAR(90),
            ipaddr inet,
            username VARCHAR(128),
            sandboxsrv  VARCHAR(20),
            posttime    bigint,
            restime     bigint,
            resultfile  int,    
            sbxcode      VARCHAR(90)
            )";

        $this->QUERY_SQL($sql);
        if(!$this->ok){
            if($GLOBALS["VERBOSE"]){echo "create_sandbox_table: $this->mysql_error\n";}
            return false;
        }

        $this->create_index("cicap_sandbox","idx_usr",array("ipaddr","username"));
        $this->create_index("cicap_sandbox","idx_obj",array("filename","uri","contentype"));
        return true;

    }

    public function CREATE_STATSCOM():bool{
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }
        if(!$this->connect($this->database)){
            if($this->mysql_error==null){$this->mysql_error="Unable to connect to database";}
            return false;
        }

        $MCONTINUE=false;
        $tables[]="statscom_proxies";
        $tables[]="statscom_hsites";
        $tables[]="statscom_husers";
        $tables[]="statscom_users";
        $tables[]="statscom_websites";
        $tables[]="statscom_entity";
        $tables[]="statscom";
        $tables[]="statsblocks";
        $tables[]="statscom_days";
        $tables[]="statscom_dns";
        $tables[]="statscom_dnsfwrules";

        foreach ($tables as $tbl){
            if($this->TABLE_EXISTS($tbl,true)){continue;}
            $MCONTINUE=true;
        }

        if(!$MCONTINUE){return true;}


// ---------------------- DNS Firewall -----------------------------------------------------------
        $sql="CREATE TABLE IF NOT EXISTS statscom_dnsfwrules ( id bigint default 0 PRIMARY KEY, rulename VARCHAR(128) )";

        $this->QUERY_SQL($sql);
        if(!$this->ok){
            $this->mysql_error=$this->mysql_error." on table statscom_dnsfwrules";
            if($GLOBALS["VERBOSE"]){echo "statscom: $this->mysql_error\n";}
            return false;
        }
        $this->create_index("statscom_dnsfwrules","idx_rulename",array("rulename"));



        if(!$this->ConnectionStatus()){$this->connect($this->database);}
        $sql="CREATE TABLE IF NOT EXISTS statscom_entity (
            entityid SERIAL PRIMARY KEY,
            entityname VARCHAR(255) UNIQUE,
            entitylabel VARCHAR(255),
            hits  bigint default 0,
            size bigint default 0)";

        $this->QUERY_SQL($sql);
        if(!$this->ok){
            if($GLOBALS["VERBOSE"]){echo "statscom: $this->mysql_error\n";}
            return false;
        }

        $this->create_index("statscom_entity","idx_entityname",array("entityname","entitylabel"));


        $sql="CREATE TABLE IF NOT EXISTS statsblocks (
            zdate TIMESTAMP,
            username VARCHAR(90),
            ipaddr inet,
            mac macaddr,
            siteid bigint,
            hits  bigint default 0,
            block smallint default 0)";

        $this->QUERY_SQL($sql);
        if(!$this->ok){
            if($GLOBALS["VERBOSE"]){echo "statscom: $this->mysql_error\n";}
            return false;
        }


        $this->create_index("statsblocks","idx_siteid",array("siteid"));
        $this->create_index("statsblocks","idx_hits",array("hits"));
        $this->create_index("statsblocks","idx_type",array("block"));
        $this->create_index("statsblocks","idx_user",array("username","ipaddr","mac"));
        return true;
    }
    public function create_personal_categories():bool{
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }
        $this->QUERY_SQL("ALTER TABLE personal_categories ALTER COLUMN categorykey TYPE varchar(50);");
        return true;
    }

    public function create_greylist_table(){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }

        $sql="CREATE TABLE IF NOT EXISTS greylist_turples (
				  zmd5 varchar(90) primary key,
				  mailfrom varchar(255),
				  mailto varchar(255),
				  stime TIMESTAMP,
				  hostname varchar(255),
				  ip_addr inet,
				  whitelisted int
				)";


        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            echo"SQL ERROR: $this->mysql_error\n";
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("greylist_turples","istime",array("stime"));
        $this->create_index("greylist_turples","imail",array("mailfrom","mailto"));
        $this->create_index("greylist_turples","iwhitelisted",array("whitelisted"));
        return true;

    }

    private function VERBOSE($text,$line):bool{
        if(!isset($GLOBALS["VERBOSE"])){return false;}
        if(!$GLOBALS["VERBOSE"]){return false;}
        if(function_exists("debug_backtrace")){
            $trace=debug_backtrace();
            if(isset($trace[1])){
                $file=basename($trace[1]["file"]);
                if(isset($trace[1]["function"])){$function=$trace[1]["function"];}
                if(isset($trace[1]["class"])){$function="{$trace[1]["class"]}/$function";}
            }
        }
        $date=date("I:s:ss");
        echo "<div style='font-size:14px;font-weight:bold;background-color:white;color:black'><span style=color:red>$date&nbsp;{$function}[$line]:</span>&nbsp;$text&nbsp;<small>($file)</small></div>\n";
        return true;
    }


    public function DNS_FILTER_TABLES(){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }
        $sql="CREATE TABLE IF NOT EXISTS dnsfilter(
		zdate timestamp,
		hits BIGINT,
		sitename VARCHAR(128),
		familysite VARCHAR(128),
		rulename VARCHAR(128),
		category BIGINT,
		ipaddr inet)";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing dnsfilter table\n";}
        if(!$this->connect($this->database)){return false;}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }


        $this->create_index("dnsfilter","idate",array("zdate"));
        $this->create_index("dnsfilter","isitename",array("sitename","familysite"));
        $this->create_index("dnsfilter","irulename",array("rulename","category"));
        $this->create_index("dnsfilter","iclient",array("ipaddr"));

        return true;
    }


    public function WSUS_TABLES(){

        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }

        $sql="CREATE TABLE IF NOT EXISTS wsus
		(zdate timestamp,
		zmd5 varchar(90) primary key,
		path varchar(256),
		contentType varchar(50),
		downloaded smallint,
		size BIGINT,
		header_size BIGINT,
		header_type varchar(90),
		domain varchar(256),
		sourceurl varchar(256),
		download_progress int,
		error_count int,
		finaltime BIGINT
		)";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing wsus table\n";}
        if(!$this->connect($this->database)){return false;}



        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("wsus","idate",array("zdate"));
        $this->create_index("wsus","ikeyf",array("domain","path"));
        $this->create_index("wsus","ikeyfprogress",array("download_progress","error_count","header_size"));
        $this->create_index("wsus","ikeyr",array("finaltime","size","downloaded"));


        return true;

    }
    public function VPN_TABLES(){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} VPN_TABLES...\n";}
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}
        $sql="CREATE TABLE IF NOT EXISTS openvpn_stats (zdate timestamp,nclients bigint,bytesin bigint,bytesout bigint)";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing openvpn_stats\n";}
        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("openvpn_stats","idate",array("zdate"));
        $this->create_index("openvpn_stats","isize",array("nclients","bytesin","bytesout"));


        $sql="CREATE TABLE IF NOT EXISTS openvpn_cnx (zdate timestamp,action varchar(20),ipaddr inet,uid varchar(128),ztime bigint)";
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing openvpn_stats\n";}
        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("openvpn_cnx","idate",array("zdate","ztime"));
        $this->create_index("openvpn_cnx","iaction",array("action"));
        $this->create_index("openvpn_cnx","iuser",array("uid",'ipaddr'));

        return true;


    }


    public function DNS_TABLES(){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DSN_TABLES...\n";}
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}


        $sql="CREATE TABLE IF NOT EXISTS dns_access
		(zdate timestamp,
		ipaddr inet,
		sitename varchar(256),
		familysite varchar(128),
		category varchar(60),
		rqs bigint
		)";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing dns_access\n";}
        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("dns_access","idate",array("zdate","rqs"));
        $this->create_index("dns_access","iipaddr",array("ipaddr"));
        $this->create_index("dns_access","isite",array("sitename","familysite"));
        $this->create_index("dns_access","icat",array("category"));


        $sql="CREATE TABLE IF NOT EXISTS dns_rqcounts(zdate timestamp,rqs bigint,clients bigint)";


        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing dns_rqcounts\n";}
        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        if(!$this->FIELD_EXISTS("dns_rqcounts", "clients")){$this->ADD_FIELD("dns_rqcounts", "clients", "bigint");}

        $this->create_index("dns_rqcounts","idate",array("zdate","rqs","clients"));


        $sql="CREATE TABLE IF NOT EXISTS dns_access_days
		(zdate timestamp,
		ipaddr inet,
		sitename varchar(256),
		familysite varchar(128),
		category varchar(60),
		rqs bigint
		)";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing dns_access\n";}
        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("dns_access_days","idate",array("zdate","rqs"));
        $this->create_index("dns_access_days","iipaddr",array("ipaddr"));
        $this->create_index("dns_access_days","isite",array("sitename","familysite"));
        $this->create_index("dns_access_days","icat",array("category"));


        return true;


    }

    public function AnSmtplogtable($tablename){
        $EnablePostfix=intval($this->GET_INFO("EnablePostfix"));
        if($EnablePostfix==0){return false;}
        if(!$this->connect($this->database)){
            if(function_exists("events")){events("PostGreSQL Error connection to database failed");}
            return false;
        }



        $sql="CREATE TABLE IF NOT EXISTS $tablename
		(id BIGSERIAL PRIMARY KEY,
		zdate timestamp,
		fromdomain varchar(128),
		relay_s varchar(128),
		relay_r varchar(128),
		todomain varchar(128),
		frommail varchar(256),
		tomail varchar(256),
		size bigint,
		aclid bigint,
		smtp_code int,
		ipaddr inet,
		refused int,
		infected int,
		rbl int,
		filtered int,
		spamscore decimal,
		sent int,
		subject varchar(512),
		msgid varchar(60),
		spamreport text,
		rblart text,
		whitelisted int,
		disclaimer int,
		maintenance smallint NOT NULL default 0,
		reason varchar(128)
		)";

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if(function_exists("events")){events("PostGreSQL Error $this->mysql_error");}
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQL ERROR: $this->mysql_error\n";}
        }

        if(!$this->FIELD_EXISTS($tablename, "aclid")){$this->QUERY_SQL("ALTER TABLE $tablename ADD aclid bigint");}
        if(!$this->FIELD_EXISTS($tablename, "msgid")){$this->QUERY_SQL("ALTER TABLE $tablename ADD msgid varchar(60)");}
        if(!$this->FIELD_EXISTS($tablename, "spamscore")){$this->QUERY_SQL("ALTER TABLE $tablename ADD spamscore int");}
        if(!$this->FIELD_EXISTS($tablename, "spamreport")){$this->QUERY_SQL("ALTER TABLE $tablename ADD spamreport text");}
        if(!$this->FIELD_EXISTS($tablename, "maintenance")){$this->QUERY_SQL("ALTER TABLE $tablename ADD maintenance smallint NOT NULL default 0");}
        if(!$this->FIELD_EXISTS($tablename, "id")){$this->QUERY_SQL("ALTER TABLE $tablename ADD id BIGSERIAL PRIMARY KEY");}
        if(!$this->FIELD_EXISTS($tablename, "disclaimer")){$this->QUERY_SQL("ALTER TABLE $tablename ADD disclaimer int");}
        if(!$this->FIELD_EXISTS($tablename, "backuped")){$this->QUERY_SQL("ALTER TABLE $tablename ADD backuped int");}
        if(!$this->FIELD_EXISTS($tablename, "infected")){$this->QUERY_SQL("ALTER TABLE $tablename ADD infected int");}
        if(!$this->FIELD_EXISTS($tablename, "filtered")){$this->QUERY_SQL("ALTER TABLE $tablename ADD filtered int");}
        if(!$this->FIELD_EXISTS($tablename, "rbl")){$this->QUERY_SQL("ALTER TABLE $tablename ADD rbl int DEFAULT 0");}
        if(!$this->FIELD_EXISTS($tablename, "whitelisted")){$this->QUERY_SQL("ALTER TABLE $tablename ADD whitelisted int");}
        if(!$this->FIELD_EXISTS($tablename, "compressed")){$this->QUERY_SQL("ALTER TABLE $tablename ADD compressed int");}
        if(!$this->FIELD_EXISTS($tablename, "stripped")){$this->QUERY_SQL("ALTER TABLE $tablename ADD stripped int");}
        if(!$this->FIELD_EXISTS($tablename, "rblart")){$this->QUERY_SQL("ALTER TABLE $tablename ADD rblart text");}

        $this->QUERY_SQL("alter table $tablename alter column spamscore type decimal(10,5);");

        $this->create_index($tablename,"imaint",array("msgid","maintenance"));
        $this->create_index($tablename,"idate",array("zdate"));
        $this->create_index($tablename,"acl",array("aclid"));
        $this->create_index($tablename,"ikeyf",array("frommail","fromdomain"));
        $this->create_index($tablename,"ikeyr",array("todomain","tomail"));
        $this->create_index($tablename,"icode",array("smtp_code","refused","sent"));
        $this->create_index($tablename,"itext",array("reason","subject"));
        $this->create_index($tablename,"imsg",array("msgid"));
        $this->create_index($tablename,"refused",array("refused","infected","rbl"));

        return true;


    }


    public function SMTP_TABLES(){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SMTP_TABLES...\n";}
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}


        $this->AnSmtplogtable("smtplog");
        $this->AnSmtplogtable("smtplog_query");




        $sql="CREATE TABLE IF NOT EXISTS mimedefang_urls
		(id BIGSERIAL PRIMARY KEY,
		urlmd5 varchar(90) UNIQUE,
		zdate timestamp,
		ttlmin bigint DEFAULT 0,
		ttlmax bigint,
		msgid varchar(60),
		sender varchar(256),
		urlsource varchar(512),
		urldest varchar(512),
		familysite varchar(256),
		scanned smallint DEFAULT 0,
		affected smallint DEFAULT 0,
		content_type varchar(40),
		nextscan smallint,
		phishing smallint,
		log text,
		malware smallint )";

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if(function_exists("events")){events("PostGreSQL Error $this->mysql_error");}
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQL ERROR: $this->mysql_error\n";}
        }

        $this->create_index("mimedefang_urls","imaint",array("msgid","sender"));
        $this->create_index("mimedefang_urls","iurls",array("urlsource","urldest","content_type"));
        $this->create_index("mimedefang_urls","imd5",array("urlmd5"));
        $this->create_index("mimedefang_urls","itime",array("ttlmin","ttlmax","scanned","nextscan"));
        $this->create_index("mimedefang_urls","iresults",array("phishing","malware"));


        $sql="CREATE TABLE IF NOT EXISTS mimedefang_msgurls (
	    ID SERIAL NOT NULL PRIMARY KEY,
	    zdate timestamp,
	    ttlmax BIGINT,
	    msgid varchar(60),
	    message TEXT,
	    infos TEXT,
	    size BIGINT
	 )";

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if(function_exists("events")){events("PostGreSQL Error $this->mysql_error");}
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQL ERROR: $this->mysql_error\n";}
        }
        $this->create_index("mimedefang_msgurls","imaint",array("msgid","ttlmax"));
        $this->create_index("mimedefang_msgurls","idate",array("zdate"));



        $sql="CREATE TABLE IF NOT EXISTS mimedefang_parts
		(zdate timestamp,
		mailfrom varchar(256),
		domainfrom varchar(128),
		mailto varchar(256),
		domainto varchar(128),
		fname varchar(128),
		ext varchar(30),
		contenttype varchar(80),
		size bigint)";

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if(function_exists("events")){events("PostGreSQL Error $this->mysql_error");}
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQL ERROR: $this->mysql_error\n";}
        }


        $sql="CREATE TABLE IF NOT EXISTS autowhite 
				(zmd5 varchar(90) primary key,
				zDate timestamp DEFAULT current_timestamp,
				mailfrom varchar(255),
				mailto varchar(255)
			)";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing autowhite\n";}
        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("autowhite","idate",array("zdate"));
        $this->create_index("autowhite","ikeyf",array("mailfrom"));
        $this->create_index("autowhite","ikeyr",array("mailto"));


#------------------------------------------------------------------------------------------------------------------------------------------------


        $sql="CREATE TABLE IF NOT EXISTS miltergreylist_acls (
			  id BIGSERIAL PRIMARY KEY,
			  instance varchar(128),
			  method varchar(50),
			  type varchar(50)  NOT NULL,
			  pattern varchar(128)  NOT NULL,
			  description varchar(255)  NOT NULL,
			  zDate timestamp DEFAULT current_timestamp)";



        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing miltergreylist_acls\n";}
        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("miltergreylist_acls","idate",array("zdate"));
        $this->create_index("miltergreylist_acls","imethod",array("method"));
        $this->create_index("miltergreylist_acls","itype",array("type"));
        $this->create_index("miltergreylist_acls","ipattern",array("pattern"));
        $this->create_index("miltergreylist_acls","idesc",array("description"));






        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing rbl_blacklists\n";}
        if(!$this->connect($this->database)){
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Connect failed\n";}
            return false;
        }



        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing smtpsum_day DONE\n";}

        $sql="CREATE TABLE IF NOT EXISTS smtpgraph_day(
		zmd5 VARCHAR(90) primary key,
		zdate timestamp,
		range VARCHAR(40),
		received BIGINT,
		delivered BIGINT,
		deferred BIGINT,
		bounced BIGINT,
		rejected  BIGINT)";

        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsum_day: SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("smtpgraph_day","zdate",array("zdate"));
        $this->create_index("smtpgraph_day","irange",array("range"));
        $this->create_index("smtpgraph_day","ikey",array("received","delivered","deferred","bounced","rejected"));

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing smtpgraph_day DONE\n";}


        $sql="CREATE TABLE IF NOT EXISTS smtpdeliver_day(
		zmd5 VARCHAR(90) primary key,
		zdate timestamp,
		domain VARCHAR(128),
		size BIGINT,
		rqs BIGINT )";

        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpdeliver_day: SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("smtpdeliver_day","zdate",array("zdate"));
        $this->create_index("smtpdeliver_day","idomain",array("domain"));
        $this->create_index("smtpdeliver_day","isize",array("size","rqs"));


        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing smtpdeliver_day DONE\n";}


        $sql="CREATE TABLE IF NOT EXISTS smtpsenders_day (
	zmd5 varchar(90) primary key,
	zdate timestamp, email varchar(128), rqs bigint)";

        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsenders_day: SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("smtpsenders_day","zdate",array("zdate"));
        $this->create_index("smtpsenders_day","iemail",array("email"));
        $this->create_index("smtpsenders_day","isize",array("rqs"));


//-----------------------------------------------------------------------------------------------------------------------------------
        $sql="CREATE TABLE IF NOT EXISTS smtprecipients_day (
	zmd5 varchar(90) primary key,
	zdate timestamp, email VARCHAR(128), rqs bigint)";

        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsenders_day: SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("smtprecipients_day","zdate",array("zdate"));
        $this->create_index("smtprecipients_day","iemail",array("email"));
        $this->create_index("smtprecipients_day","isize",array("rqs"));

//-----------------------------------------------------------------------------------------------------------------------------------
        $sql="CREATE TABLE IF NOT EXISTS smtprefused (
	zdate timestamp,
	hostname VARCHAR(128), 
	mailfrom VARCHAR(128),
	mailto VARCHAR(128),
	ipaddr inet,
	reason VARCHAR(90)		
	)";

        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsenders_day: SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("smtprefused","zdate",array("zdate"));
        $this->create_index("smtprefused","iemail",array("mailfrom","mailto"));
        $this->create_index("smtprefused","ipaddr",array("ipaddr"));
        $this->create_index("smtprefused","reason",array("reason"));

//-----------------------------------------------------------------------------------------------------------------------------------
        $sql="CREATE TABLE IF NOT EXISTS backupmsg (
	 ID SERIAL NOT NULL PRIMARY KEY,
	 zdate timestamp,
	 final BIGINT,
	 msgmd5 varchar(90),
	 msgid varchar(60),
	 htmlmess text,
	 size BIGINT,
	 subject varchar(255),
	 mailfrom varchar(128),
	 mailto varchar(128),
	 domainfrom varchar(128),
	 domainto varchar(128)
	 )";

        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsenders_day: SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        if(!$this->FIELD_EXISTS("backupmsg", "htmlmess")){
            $this->ADD_FIELD("backupmsg", "htmlmess", "TEXT");
        }
        if(!$this->FIELD_EXISTS("backupmsg", "msgid")){
            $this->ADD_FIELD("backupmsg", "msgid", "varchar(60)");
        }


        $this->create_index("backupmsg","zdate",array("zdate"));
        $this->create_index("backupmsg","ifinal",array("final"));
        $this->create_index("backupmsg","ifrom",array("mailfrom","domainfrom"));
        $this->create_index("backupmsg","ito",array("mailto","domainto"));
        $this->create_index("backupmsg","isubject",array("subject"));

        $sql="CREATE TABLE IF NOT EXISTS backupdata (msgmd5 varchar(90) primary key,zdate timestamp, final bigint,contentid BIGINT)";

        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsenders_day: SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("backupdata","msgid",array("msgid","msgmd5"));
        $this->create_index("backupdata","zdate",array("zdate"));
        $this->create_index("backupdata","ifinal",array("final"));

//--------------------------------------------------------------------------------------------------------
        $sql="CREATE TABLE IF NOT EXISTS quarmsg (
	 ID SERIAL NOT NULL PRIMARY KEY,
	 zdate timestamp,
	 final BIGINT,
	 msgmd5 varchar(90),
	 msgid varchar(60),
	 htmlmess TEXT,
	 htmlsize BIGINT,
	 size BIGINT,
	 instanceid BIGINT NOT NULL DEFAULT 0,
	 subject varchar(255),
	 mailfrom varchar(128),
	 mailto varchar(128),
	 domainfrom varchar(128),
	 domainto varchar(128)
	 )";

        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsenders_day: SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("quarmsg","zdate",array("zdate"));
        $this->create_index("quarmsg","ifinal",array("final"));
        $this->create_index("quarmsg","ifrom",array("mailfrom","domainfrom"));
        $this->create_index("quarmsg","ito",array("mailto","domainto"));
        $this->create_index("quarmsg","isubject",array("subject"));

        if(!$this->FIELD_EXISTS("quarmsg","htmlmess")){
            $this->ADD_FIELD("quarmsg", "htmlmess", "TEXT");
        }
        if(!$this->FIELD_EXISTS("quarmsg","htmlsize")){
            $this->ADD_FIELD("quarmsg", "htmlsize", "BIGINT");
        }
        if(!$this->FIELD_EXISTS("quarmsg","msgid")){
            $this->ADD_FIELD("quarmsg", "msgid", "varchar(60)");
            $this->create_index("quarmsg","msgid",array("msgid"));

        }

        $sql="CREATE TABLE IF NOT EXISTS quardata (
	msgmd5 varchar(90) primary key,
	zdate timestamp, final bigint,contentid BIGINT)";

        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpsenders_day: SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("quardata","zdate",array("zdate"));
        $this->create_index("quardata","ifinal",array("final"));

//--------------------------------------------------------------------------------------------------------

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtp_ipset: Checking...\n";}
        $sql="CREATE TABLE IF NOT EXISTS smtp_ipset (
		pattern inet primary key,
		zdate TIMESTAMP NOT NULL,
		automatic smallint NOT NULL DEFAULT 0,
		patype smallint NOT NULL DEFAULT 0,
		enabled smallint NOT NULL DEFAULT 1)";

        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} attachstats: SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        if(!$this->connect($this->database)){return false;}

        $this->create_index("smtp_ipset","ztokens",array("automatic","enabled","patype"));
        $this->create_index("smtp_ipset","idate",array("zdate"));

        //--------------------------------------------------------------------------------------------------------

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} attachstats: Checking...\n";}
        $sql="CREATE TABLE IF NOT EXISTS attachstats (
		zmd5 varchar(90) primary key,	
		zdate TIMESTAMP NOT NULL,
		mailfrom VARCHAR(255) NOT NULL,
		domainfrom VARCHAR(255) NOT NULL,
		mailto VARCHAR(255) NOT NULL,
		domainto VARCHAR(255) NOT NULL,
		fname VARCHAR(128) NOT NULL,
		ext VARCHAR(10) NOT NULL,
		contenttype VARCHAR(60) NOT NULL,
		size BIGINT )";

        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} attachstats: SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        if(!$this->connect($this->database)){return false;}

        $this->create_index("attachstats","izdate",array("zdate"));
        $this->create_index("attachstats","ifrom",array("mailfrom","domainfrom"));
        $this->create_index("attachstats","ito",array("mailto","domainto"));
        $this->create_index("attachstats","isize",array("size"));
        $this->create_index("attachstats","ifile",array("contenttype","ext","fname"));
//--------------------------------------------------------------------------------------------------------

        $sql="CREATE TABLE IF NOT EXISTS smtpstats ( 
	zmd5 varchar(90) primary key,	
	zdate timestamp,mailfrom varchar(255),
	domainfrom varchar(255),mailto varchar(255),domainto varchar(255),
	subject varchar(255),
	size BIGINT,
	spamscore int,
	spamreport text,
	disclaimer int,
	backuped int,
	infected int,
	filtered int,
	whitelisted int,
	compressed int,
	stripped int )";

        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} smtpstats: SQL ERROR: $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        if(!$this->connect($this->database)){return false;}
        @pg_query ($this->connection, "alter table smtpstats add column if not exists spamreport text;");

        $this->create_index("smtpstats","izdate",array("zdate"));
        $this->create_index("smtpstats","ifrom",array("mailfrom","domainfrom"));
        $this->create_index("smtpstats","ito",array("mailto","domainto"));
        $this->create_index("smtpstats","iscore",array("spamscore","disclaimer","backuped","infected","filtered","whitelisted","compressed","stripped"));
//--------------------------------------------------------------------------------------------------------
        return true;
    }





    public function CREATE_USERAGENT():bool{
        $this->ok=true;
        $sql="CREATE TABLE IF NOT EXISTS useragents ( useragent VARCHAR(255) NOT NULL PRIMARY KEY)";
        $this->QUERY_SQL($sql);
        if(!$this->ok){return false;}
        return true;
    }

    public function CREATE_ANTIVIRUS(){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }

        $sql="CREATE TABLE IF NOT EXISTS antivirus_events (
			   id SERIAL NOT NULL PRIMARY KEY,		
			   zdate timestamp,
			  taskname varchar(90) NOT NULL,
			  virusname varchar(128) NOT NULL,
			  infectedpath varchar(512) NOT NULL DEFAULT '',
			  computername varchar(256) NOT NULL
			)";
        if(!$this->connect($this->database)){return false;}
        if(!function_exists("pg_connect")){$this->ok=false;
            $this->mysql_error="pg_connect(), no such function...";return false;}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            @pg_close($this->connection);
            return false;
        }

        $this->create_index("antivirus_events","zdate",array("zdate"));
        $this->create_index("antivirus_events","taskname",array("taskname"));
        $this->create_index("antivirus_events","computername",array("computername"));
        $this->create_index("antivirus_events","virusname",array("virusname"));
        return true;
    }

    public function CREATE_SMOKEPING():bool{
        $sql="CREATE TABLE IF NOT EXISTS \"smokeping\" 
                (zdate timestamp,ruleid BIGINT,category VARCHAR(32))";
        $this->QUERY_SQL($sql);
        if(!$this->ok){return false;}
        $this->create_index("smokeping","xsmokeping",array("zdate","ruleid"));
        $this->create_index("smokeping","icategory",array("category"));
        return true;
    }




    public function CREATE_ACCESS_LOG_TEMP(){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return 0;
        }
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} this->CREATE_TABLES\n";}
        if(!$this->connect($this->database)){return false;}
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}

        $curtime=date("YmdH");
        $tablename="access_$curtime";


        $sql="CREATE TABLE IF NOT EXISTS \"$tablename\"
		(zDate timestamp,
		MAC macaddr,
		IPADDR INET,
		PROXYNAME VARCHAR(128) NOT NULL,
		CATEGORY VARCHAR(64) NULL,
		SITENAME VARCHAR(128) NULL,
		FAMILYSITE VARCHAR(128) NULL,
		USERID VARCHAR(64) NULL,
		SIZE BIGINT,
		RQS BIGINT)";


        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing access_log\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("$tablename","categoryi",array("familysite","category"));
        $this->create_index("$tablename","useridi",array("userid","mac","ipaddr"));
        $this->create_index("$tablename","sizei",array("SIZE","RQS"));
        $this->create_index("$tablename","proxynamei",array("PROXYNAME"));
        $this->create_index("$tablename","zdate",array("zdate"));

    }

    public function CREATE_PURGE_TABLE(){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }
        $this->ok=true;
        if(!$this->connect($this->database)){return false;}
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}
        $sql="CREATE TABLE IF NOT EXISTS \"squidpurge\"
			(cachedir INT,
			proxyname VARCHAR(128) NOT NULL,
			familysite VARCHAR(64) NULL,
			sitename VARCHAR(128) NULL,
			path VARCHAR(128) NULL,
			size BIGINT
			)";

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("squidpurge","cachedir",array("cachedir"));
        $this->create_index("squidpurge","sitename",array("familysite","path"));
        $this->create_index("squidpurge","sizei",array("size"));
        $this->create_index("squidpurge","proxyname",array("proxyname"));



    }

    public function create_prads_table(){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }
        $this->ok=true;
        if(!$this->connect($this->database)){return false;}
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}

        $sql="CREATE TABLE IF NOT EXISTS prads_time
		(discovered timestamp,
		mac macaddr,
		ipaddr inet,
		vlan int,
		protos bigint,
		ports bigint,
		portstext text,
		syn BIGINT,ack BIGINT,client BIGINT,server bigint,
		udp BIGINT,fin BIGINT,rst BIGINT,arp BIGINT,icmp BIGINT)";

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("prads_time","ident",array("mac","ipaddr"));
        $this->create_index("prads_time","vlan",array("vlan"));
        $this->create_index("prads_time","discovered",array("discovered"));
        $this->create_index("prads_time","sum",array("syn","ack","client",
            "udp","fin","rst","arp","icmp"));


        $sql="CREATE TABLE IF NOT EXISTS prads_tot (
		mac macaddr,
		ipaddr inet,
		protos bigint,
		ports bigint,
		syn BIGINT,ack BIGINT,client BIGINT,server bigint,
		udp BIGINT,fin BIGINT,rst BIGINT,arp BIGINT,icmp BIGINT)";

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            @pg_close($this->connection);
            return false;

        }

        return true;

    }

    public function create_ntlm_table(){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} this->CREATE_TABLES\n";}
        if(!$this->connect($this->database)){return false;}
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}


        $sql="CREATE TABLE IF NOT EXISTS ntlmauthenticator
		(zDate timestamp,
		uuid VARCHAR(128) NOT NULL,
		cpu BIGINT,
		requests BIGINT,
		processes BIGINT )";



        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("ntlmauthenticator","cpu",array("cpu"));
        $this->create_index("ntlmauthenticator","uuid",array("uuid"));
        $this->create_index("ntlmauthenticator","requests",array("requests"));
        $this->create_index("ntlmauthenticator","processes",array("processes"));
        $this->create_index("ntlmauthenticator","zdate",array("zdate"));

    }


    public function create_v4_proxy_tables(){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }
        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS proxy_traffic
		(zdate timestamp,
		member VARCHAR(128),
		category int,
		sitename varchar(128) NULL,
		familysite varchar(128) NULL,
		proxyname varchar(128) NULL,
		size BIGINT,
		rqs BIGINT)";

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("proxy_traffic","isitename",array("sitename"));
        $this->create_index("proxy_traffic","familysitei",array("familysite"));
        $this->create_index("proxy_traffic","memberi",array("member"));
        $this->create_index("proxy_traffic","sizei",array("size","rqs","category"));
        $this->create_index("proxy_traffic","proxynamei",array("proxyname"));
        $this->create_index("proxy_traffic","zdatei",array("zdate"));


        // MEMORY_WEBF[md5]=sitename+"|"+KeyUser+"|"+str(rule)+"|"+str(rqs)+"|"+str(category)+"|"+proxyname

        $sql="CREATE TABLE IF NOT EXISTS proxy_blocked
		(zdate timestamp,
		member VARCHAR(128),
		category int,
		sitename varchar(128) NULL,
		familysite varchar(128) NULL,
		rule int,
		proxyname varchar(128) NULL,
		rqs BIGINT)";

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("proxy_blocked","isitename",array("sitename"));
        $this->create_index("proxy_blocked","familysitei",array("familysite"));
        $this->create_index("proxy_blocked","irule",array("rule"));
        $this->create_index("proxy_blocked","memberi",array("member"));
        $this->create_index("proxy_blocked","irqs",array("rqs","category"));
        $this->create_index("proxy_blocked","proxynamei",array("proxyname"));
        $this->create_index("proxy_blocked","zdatei",array("zdate"));


        $sql="CREATE TABLE IF NOT EXISTS safebrowsing 
          (zdate timestamp,
          zmd5 varchar(90) PRIMARY KEY NOT NULL,
          category int);";


        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            @pg_close($this->connection);
            return false;

        }


        $sql="CREATE TABLE IF NOT EXISTS malwares_domains (zdate timestamp, zmd5 varchar(90) PRIMARY KEY NOT NULL);";


        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            @pg_close($this->connection);
            return false;

        }



        $sql="CREATE TABLE IF NOT EXISTS proxy_ua
		(zdate timestamp,
		ua VARCHAR(128),
		size bigint,
		proxyname varchar(128) NULL,
		rqs BIGINT)";

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("proxy_ua","iua",array("ua"));
        $this->create_index("proxy_ua","isze",array("size"));
        $this->create_index("proxy_ua","irqs",array("rqs"));
        $this->create_index("proxy_ua","proxynamei",array("proxyname"));
        $this->create_index("proxy_ua","zdatei",array("zdate"));


        //zdate,member,category,sitename,rule,proxyname,rqs



        return true;
    }
    public function CREATE_TABLES(){
        if($this->DisablePostGres==1){
            $this->ok=false;
            $this->mysql_error="PostgreSQL is disabled";
            return false;
        }


        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} this->CREATE_TABLES\n";}
        if(!$this->connect($this->database)){return false;}
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}


        $sql="CREATE TABLE IF NOT EXISTS access_log
		(zDate timestamp,
		MAC macaddr,
		IPADDR INET,
		PROXYNAME VARCHAR(128) NOT NULL,
		CATEGORY VARCHAR(64) NULL,
		SITENAME VARCHAR(255) NULL,
		FAMILYSITE VARCHAR(128) NULL,				
		USERID VARCHAR(64) NULL,
		SIZE BIGINT,
		RQS BIGINT)";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing access_log\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("access_log","categoryi",array("familysite","category"));
        $this->create_index("access_log","useridi",array("userid","mac","ipaddr"));
        $this->create_index("access_log","sizei",array("SIZE","RQS"));
        $this->create_index("access_log","proxynamei",array("PROXYNAME"));
        $this->create_index("access_log","zdate",array("zdate"));


        if(!$this->connect($this->database)){return false;}
        $sql='CREATE TABLE IF NOT EXISTS "access_users" (zdate timestamp,userid VARCHAR(64) NULL,size BIGINT,rqs BIGINT)';
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing access_users\n";}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("access_users","userid",array("userid"));
        $this->create_index("access_users","sizei",array("size","rqs"));
        $this->create_index("access_users","zdate",array("zdate"));
//-----------------------------------------------------------------------------------------------------------------------------------

        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS access_big
		(zDate timestamp,zmd5 VARCHAR(32),
		mac macaddr,
		ipaddr INET,
		proxyname VARCHAR(128) NOT NULL,
		category VARCHAR(64) NULL,
		url VARCHAR(512) NULL,
		familysite VARCHAR(128) NULL,				
		userid VARCHAR(64) NULL, size BIGINT )";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing access_big\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("access_big","categoryi",array("familysite","category"));
        $this->create_index("access_big","useridi",array("userid","mac","ipaddr"));
        $this->create_index("access_big","sizei",array("SIZE"));
        $this->create_index("access_big","proxynamei",array("PROXYNAME"));
        $this->create_index("access_big","zdate",array("zdate"));
        $this->create_index("access_big","zmd5",array("zmd5"));

//-----------------------------------------------------------------------------------------------------------------------------------
        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS domains_table( id SERIAL NOT NULL PRIMARY KEY,sitename VARCHAR(512) UNIQUE)";
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("domains_table","idomain",array("sitename"));
//-----------------------------------------------------------------------------------------------------------------------------------
        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS bandwidth_table( zdate timestamp NOT NULL PRIMARY KEY,sites BIGINT,hits BIGINT,size BIGINT)";
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("bandwidth_table","isize",array("sites","size","hits"));
//-----------------------------------------------------------------------------------------------------------------------------------


        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS domains_access( 
          zDate timestamp,
          domainid BIGINT,
          hits BIGINT,
          size BIGINT,
          users BIGINT

      )";
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("domains_table","idomain",array("sitename"));
//-----------------------------------------------------------------------------------------------------------------------------------

        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS categories_requests(
		id SERIAL NOT NULL PRIMARY KEY,		
		zDate timestamp,
		uuid VARCHAR(90),
		detectedas INT,
		sitename VARCHAR(255),
		category VARCHAR(128),
		company VARCHAR(128),
		email VARCHAR(128) )";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing access_big\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("categories_requests","categoryi",array("sitename","category"));


        //-----------------------------------------------------------------------------------------------------------------------------------


        if(!$this->connect($this->database)){return false;}
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}


        $sql="CREATE TABLE IF NOT EXISTS icap_access
		(zdate timestamp,
		proxyip INET,
		ipaddr INET,
		proxyname VARCHAR(128) NOT NULL,
	    service VARCHAR(64) NULL,				
		category VARCHAR(64) NULL,
		sitename VARCHAR(128) NULL,
		familysite VARCHAR(128) NULL,
		userid VARCHAR(64) NULL,
		size BIGINT,
		rqs BIGINT)";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing icap_access\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("icap_access","categoryi",array("familysite","category","service"));
        $this->create_index("icap_access","useridi",array("userid","proxyip","ipaddr"));
        $this->create_index("icap_access","sizei",array("SIZE","rqs"));
        $this->create_index("icap_access","proxynamei",array("proxyname"));
        $this->create_index("icap_access","zdate",array("zdate"));
//-----------------------------------------------------------------------------------------------------------------------------------
        if(!$this->connect($this->database)){return false;}
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}


        $sql="CREATE TABLE IF NOT EXISTS agents_access (zdate timestamp,mac macaddr,proxyname VARCHAR(128) NOT NULL,familysite VARCHAR(128) NULL,userid VARCHAR(64) NULL,useragent VARCHAR(128) NULL,size BIGINT,rqs BIGINT)";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing agents_access\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} agents_access -> $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }


        $this->create_index("agents_access","useridi",array("mac","userid"));
        $this->create_index("agents_access","sizei",array("size","rqs"));
        $this->create_index("agents_access","proxynamei",array("proxyname"));
        $this->create_index("agents_access","useragenti",array("useragent"));
        $this->create_index("agents_access","zdate",array("zdate"));
//-----------------------------------------------------------------------------------------------------------------------------------


        if(!$this->connect($this->database)){return false;}
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}


        $sql="CREATE TABLE IF NOT EXISTS hypercache_access (zdate timestamp,cached INT,category VARCHAR(64) NULL,familysite VARCHAR(128) NULL,size BIGINT,RQS BIGINT)";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing hypercache_access\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("hypercache_access","categoryi",array("familysite","category"));
        $this->create_index("hypercache_access","sizei",array("SIZE","rqs"));
        $this->create_index("hypercache_access","zdate",array("zdate"));
        //-----------------------------------------------------------------------------------------------------------------------------------



        if(!$this->connect($this->database)){return false;}
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}


        $sql="CREATE TABLE IF NOT EXISTS proxypac (zdate timestamp, ipaddr INET, useragent VARCHAR(256) NOT NULL,rule BIGINT NOT NULL)";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing proxypac\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("proxypac","rulei",array("rule"));
        $this->create_index("proxypac","useragent",array("useragent"));
        $this->create_index("proxypac","ipaddri",array("ipaddr"));
        $this->create_index("proxypac","zdate",array("zdate"));
        //-----------------------------------------------------------------------------------------------------------------------------------




        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} this->CREATE_TABLES\n";}
        if(!$this->connect($this->database)){return false;}
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}




        $sql="CREATE TABLE IF NOT EXISTS haproxy_log
		(zDate timestamp,
		ipsrc INET,
		servicename varchar(128),
		backend INET,
		http_code INT NOT NULL,
		rqs BIGINT,
		size BIGINT,
		proxyname varchar(128),
		ts varchar(10))";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing haproxy_log\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("haproxy_log","servicenamei",array("servicename","backend"));
        $this->create_index("haproxy_log","tsi",array("ts"));
        $this->create_index("haproxy_log","sizei",array("size","rqs"));
        $this->create_index("haproxy_log","proxynamei",array("proxyname"));
        $this->create_index("haproxy_log","zdate",array("zdate"));


//-----------------------------------------------------------------------------------------------------------------------------------
        $sql="CREATE TABLE IF NOT EXISTS haproxy_month
		(zDate timestamp,
		ipsrc INET,
		servicename varchar(128),
		backend INET,
		http_code INT NOT NULL,
		rqs BIGINT,
		size BIGINT,
		proxyname varchar(128),
		ts varchar(10))";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing haproxy_month\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("haproxy_month","servicenamei",array("servicename","backend"));
        $this->create_index("haproxy_month","tsi",array("ts"));
        $this->create_index("haproxy_month","sizei",array("size","rqs"));
        $this->create_index("haproxy_month","proxynamei",array("proxyname"));
        $this->create_index("haproxy_month","zdate",array("zdate"));
//-----------------------------------------------------------------------------------------------------------------------------------
        $sql="CREATE TABLE IF NOT EXISTS haproxy_year
		(zDate timestamp,
		ipsrc INET,
		servicename varchar(128),
		backend INET,
		http_code INT NOT NULL,
		rqs BIGINT,
		size BIGINT,
		proxyname varchar(128),
		ts varchar(10))";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing haproxy_year\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }


        $this->create_index("haproxy_year","servicenamei",array("servicename","backend"));
        $this->create_index("haproxy_year","tsi",array("ts"));
        $this->create_index("haproxy_year","sizei",array("size","rqs"));
        $this->create_index("haproxy_year","proxynamei",array("proxyname"));
        $this->create_index("haproxy_year","zdate",array("zdate"));

        //-----------------------------------------------------------------------------------------------------------------------------------




        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} this->CREATE_TABLES\n";}
        if(!$this->connect($this->database)){return false;}
        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}




        $sql="CREATE TABLE IF NOT EXISTS adagent_log
		(zDate timestamp,
		ipsrc INET,
		servicename varchar(128),
		backend INET,
		http_code INT NOT NULL,
		rqs BIGINT,
		size BIGINT,
		proxyname varchar(128),
		ts varchar(10))";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing adagent_log\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("adagent_log","servicenamei",array("servicename","backend"));
        $this->create_index("adagent_log","tsi",array("ts"));
        $this->create_index("adagent_log","sizei",array("size","rqs"));
        $this->create_index("adagent_log","proxynamei",array("proxyname"));
        $this->create_index("adagent_log","zdate",array("zdate"));

        //-----------------------------------------------------------------------------------------------------------------------------------
        $sql="CREATE TABLE IF NOT EXISTS adagent_month
		(zDate timestamp,
		ipsrc INET,
		servicename varchar(128),
		backend INET,
		http_code INT NOT NULL,
		rqs BIGINT,
		size BIGINT,
		proxyname varchar(128),
		ts varchar(10))";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing adagent_month\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("adagent_month","servicenamei",array("servicename","backend"));
        $this->create_index("adagent_month","tsi",array("ts"));
        $this->create_index("adagent_month","sizei",array("size","rqs"));
        $this->create_index("adagent_month","proxynamei",array("proxyname"));
        $this->create_index("adagent_month","zdate",array("zdate"));
//-----------------------------------------------------------------------------------------------------------------------------------
        $sql="CREATE TABLE IF NOT EXISTS adagent_year
		(zDate timestamp,
		ipsrc INET,
		servicename varchar(128),
		backend INET,
		http_code INT NOT NULL,
		rqs BIGINT,
		size BIGINT,
		proxyname varchar(128),
		ts varchar(10))";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing adagent_year\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }


        $this->create_index("adagent_year","servicenamei",array("servicename","backend"));
        $this->create_index("adagent_year","tsi",array("ts"));
        $this->create_index("adagent_year","sizei",array("size","rqs"));
        $this->create_index("adagent_year","proxynamei",array("proxyname"));
        $this->create_index("adagent_year","zdate",array("zdate"));
//-----------------------------------------------------------------------------------------------------------------------------------
        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS not_categorized
		(zdate timestamp,
		familysite varchar(128) primary key,
		rqs BIGINT )";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing not_categorized\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("not_categorized","sizei",array("rqs"));
        $this->create_index("not_categorized","idate",array("zdate"));

        $sql="CREATE TABLE IF NOT EXISTS visits (domain varchar(128) primary key)";
        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $sql="CREATE TABLE IF NOT EXISTS categories_caches (domain varchar(128) primary key,category_id BIGINT )";

        if(!$this->connect($this->database)){return false;}
        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("categories_caches","category_id",array("category_id"));




//-----------------------------------------------------------------------------------------------------------------------------------
        $sql="CREATE TABLE IF NOT EXISTS access_month
		(zDate timestamp,
		MAC macaddr,
		IPADDR INET,
		PROXYNAME VARCHAR(128) NOT NULL,
		CATEGORY VARCHAR(64) NULL,
		FAMILYSITE VARCHAR(128) NULL,
		USERID VARCHAR(64) NULL,
		SIZE BIGINT,
		RQS BIGINT)";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing access_month\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }


        $this->create_index("access_month","categoryi",array("familysite","category"));
        $this->create_index("access_month","useridi",array("userid","mac","ipaddr"));
        $this->create_index("access_month","sizei",array("SIZE","RQS"));
        $this->create_index("access_month","proxynamei",array("PROXYNAME"));
        $this->create_index("access_month","zdate",array("zdate"));

        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS access_year
		(zDate timestamp,
		MAC macaddr,
		IPADDR INET,
		PROXYNAME VARCHAR(128) NOT NULL,
		CATEGORY VARCHAR(64) NULL,
		FAMILYSITE VARCHAR(128) NULL,
		USERID VARCHAR(64) NULL,
		SIZE BIGINT,
		RQS BIGINT)";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing access_year\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }


        $this->create_index("access_year","categoryi",array("familysite","category"));
        $this->create_index("access_year","useridi",array("userid","mac","ipaddr"));
        $this->create_index("access_year","sizei",array("SIZE","RQS"));
        $this->create_index("access_year","proxynamei",array("PROXYNAME"));
        $this->create_index("access_year","zdate",array("zdate"));


        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS main_size (zDate timestamp, PROXYNAME VARCHAR(128) NOT NULL, SIZE BIGINT)";
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing main_size\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("main_size","PROXYNAMEi",array("PROXYNAME"));
        $this->create_index("main_size","keyi",array("zDate","SIZE"));


//*****************************************************************************************************************************
        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS squidtail (zdate timestamp, proxyname varchar(128) NOT NULL, size bigint,rqs bigint,users bigint)";
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing CACHED table\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("squidtail","proxyname",array("proxyname"));
        $this->create_index("squidtail","keyi",array("zdate","size","rqs","users"));

//*****************************************************************************************************************************



        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS CACHED (zDate timestamp, PROXYNAME VARCHAR(128) NOT NULL, SIZE BIGINT)";
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing CACHED table\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("CACHED","PROXYNAMEi",array("PROXYNAME"));
        $this->create_index("CACHED","keyi",array("zDate","SIZE"));


        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS NO_CACHED (zDate timestamp, PROXYNAME VARCHAR(128) NOT NULL, SIZE BIGINT)";
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing NO_CACHED table\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("NO_CACHED","PROXYNAMEi",array("PROXYNAME"));
        $this->create_index("NO_CACHED","keyi",array("zDate","SIZE"));


        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS proxy_requests (zDate timestamp, PROXYNAME VARCHAR(128) NOT NULL, RQS BIGINT)";
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing proxy_requests table\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("proxy_requests","PROXYNAMEi",array("PROXYNAME"));
        $this->create_index("proxy_requests","keyi",array("zDate","RQS"));


        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS websites (zDate timestamp, PROXYNAME VARCHAR(128) NOT NULL, FAMILYSITE VARCHAR(128), SIZE BIGINT,RQS BIGINT)";
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing websites table\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("websites","PROXYNAMEi",array("PROXYNAME"));
        $this->create_index("websites","keyi",array("zDate","FAMILYSITE","RQS","SIZE"));



        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS webfilter (zDate timestamp, 
				PROXYNAME VARCHAR(128) NOT NULL, 
				website VARCHAR(128), 
				category VARCHAR(64),
				rulename VARCHAR(128),
				public_ip inet,
				blocktype VARCHAR(128),
				why VARCHAR(128),
				hostname VARCHAR(128),
				client VARCHAR(128),
				rqs BIGINT)";
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing webfilter table\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("webfilter","PROXYNAMEi",array("PROXYNAME"));
        $this->create_index("webfilter","keyi",array("zDate","website","RQS"));
        $this->create_index("webfilter","zuid",array("hostname","client"));
        $this->create_index("webfilter","rule",array("category","rulename","blocktype"));

        $prefix="(zDate,IPADDR,SITENAME,HTTP_CODE,RQS,SIZE,PROXYNAME)";

        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS apache_size (zDate timestamp,
				PROXYNAME VARCHAR(128) NOT NULL,
				IPADDR inet,
				SITENAME VARCHAR(128),
				HTTP_CODE INT,
				RQS BIGINT,
				SIZE BIGINT
				)";
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing apache_size table\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }
        $this->create_index("apache_size","PROXYNAMEi",array("PROXYNAME"));
        $this->create_index("apache_size","keyi",array("zDate","IPADDR","RQS","SIZE"));
        $this->create_index("apache_size","zuid",array("IPADDR"));



        if(!$this->connect($this->database)){return false;}
        $sql="CREATE TABLE IF NOT EXISTS system (
				zdate timestamp,
				proxyname VARCHAR(128) NOT NULL,
				load_avg float NOT NULL,
				mem_stats BIGINT NOT NULL,
				cpu_stats float NOT null
				)";
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Testing system table\n";}

        $results = @pg_query ($this->connection, $sql);
        if(!$results){
            $this->ok=false;
            $this->mysql_error=@pg_last_error($this->connection);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $this->mysql_error\n";}
            @pg_close($this->connection);
            return false;

        }

        $this->create_index("system","PROXYNAMEi",array("PROXYNAME"));
        $this->create_index("system","keyi",array("zdate","load_avg","mem_stats","cpu_stats"));


    }

    public function suricata_tables(){

        if(!function_exists("pg_connect")){$this->ok=false;$this->mysql_error="pg_connect(), no such function...";return false;}




        $sql="CREATE TABLE IF NOT EXISTS fail2ban_events (
		zdate timestamp,
		country varchar(90),
		city varchar(90),
		src_ip inet,
		service varchar(40),
		hostname varchar(128),		
		xcount BIGINT)";

        $this->QUERY_SQL($sql);
        if(!$this->ok){return false;}

        if(!$this->FIELD_EXISTS("fail2ban_events","service")){
            $this->ADD_FIELD("fail2ban_events","service","varchar(40)");
        }
        if(!$this->FIELD_EXISTS("fail2ban_events","hostname")){
            $this->ADD_FIELD("fail2ban_events","hostname","varchar(128)");
        }

        $this->create_index("fail2ban_events","keyi",array("zdate","service","country","city","src_ip","xcount","hostname"));

        return true;
    }

    private function events_failed($function=null,$line=null){
        if(!function_exists("openlog")){return false;}
        $filename=basename(__FILE__);
        openlog("postgres", LOG_PID , LOG_SYSLOG);
        syslog(LOG_INFO, "ERROR: $filename: [$function/$line] $this->mysql_error");
        closelog();
    }

}
function pg_escape_string2($line):string{
    $search=array("\\","\0","\n","\r","\x1a","'",'"');
    $replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
    return str_replace($search,$replace,$line);
}
?>