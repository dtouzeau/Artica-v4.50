<?php
class lib_sqlite{
    public $db_path="";
    public $last_id=0;
    var $ok=true;
    var $mysql_error;
    private $sql;
    private $ReadOnly=false;
    // When true, QUERY_SQL / IF_TABLE_EXISTS refuse to open a missing
    // file instead of letting PDO silently create a blank DB (and chmod
    // it 0777). Auto-enabled in the constructor for the articarest-
    // replicated databases so a PHP query arriving mid cluster-restore
    // cannot obliterate the daemon's in-flight WAL state. Callers can
    // still override either way: e.g. a first-run installer can set
    // $q->StrictFileMustExist=false to force auto-create.
    public $StrictFileMustExist = false;
    // Per-request memoization for notifyDaemonRefresh — every
    // lib_sqlite instance within one page load shares the same set,
    // so 50 inserts to acls.db produce 1 daemon notify, not 50.
    private static $daemonNotified = [];
    // Binds deposes par QUERY_SQL_PARAMS et consommes par QUERY_SQL. null =
    // requete non parametree (le comportement historique).
    private $bindParams = null;
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
        // Allowlist of databases whose lifecycle is owned by articarest's
        // dbpool and the cluster-restore safe-replicate path. Mirrors
        // backup.SqlitedbList() + the allowlist around
        // acluster/ClusterServer.go:579. Any DB whose basename is in
        // this map gets strict-file-must-exist semantics by default so
        // that an arriving PHP query during a restore cannot create a
        // blank file under the daemon and demote it out of WAL mode.
        static $StrictDBs = [
            "acls.db" => true,
            "antivirus.db" => true,
            "certificates.db" => true,
            "dns.db" => true,
            "etc_hosts.db" => true,
            "firewall.db" => true,
            "proxy.db" => true,
            "webfilter.db" => true,
            "nginx.db" => true,
        ];
        if($database_path !== null && isset($StrictDBs[basename($database_path)])){
            $this->StrictFileMustExist = true;
        }
    }

    // Databases owned EXCLUSIVELY by the articarest daemon (dbpool). PHP must
    // reach these only through the REST API. Opening them from a short-lived
    // PHP process breaks that ownership and — in WAL journal mode — can strand
    // the daemon's -shm mmap on an unlinked inode (the acls.db "disk I/O error"
    // wedge). This is the SINGLE source of truth for that set: both QUERY_SQL
    // and IF_TABLE_EXISTS (the only two methods that open a PDO handle) call it
    // before opening, so the guard can never again exist in one path and be
    // missing in the other. Keep in sync with the daemon: dbpool `aclsDBPath`
    // and the per-DB DSN switch in dbpool/dbpool.go.
    private function isDaemonOwnedDb(){
        static $daemonOwned = ["acls.db"=>true, "nginx.db"=>true];
        return isset($daemonOwned[basename((string)$this->db_path)]);
    }
    // Bases que le daemon tient ouvertes en permanence et qui doivent rester en
    // mode journal DELETE. L'acces PHP reste AUTORISE (contrairement a
    // isDaemonOwnedDb) : seul le pin WAL est supprime.
    //
    // sys_schedules.db porte system_schedules (servie par l'API SystemTasks)
    // mais aussi pdf_reports et imapsync, ouvertes directement par huit
    // scripts (exec.convert-to-sqlite.php, exec.mailsync.php, les trois
    // exec.pdf.proxy.*, exec.statscom-stats.php, fw.statscom.reports.php,
    // ressources/class.snapshots.blacklists.inc). Repousser cette base en
    // WAL a chaque ouverture ferait reapparaitre le wedge SHM-mmap
    // d'acls.db : le daemon lit alors un inode supprime et repond
    // "disk I/O error" sur toute lecture.
    //
    // haproxy.db est dans le meme cas et doit rester synchronisee avec la
    // liste Go dbpool.crossProcessDBs, qui contient acls.db, haproxy.db ET
    // sys_schedules.db : seize fichiers PHP l'ouvrent (fw.haproxy.*,
    // fw.haexchange.*, fw.hacluster.*, exec.haproxy.php, class.haproxy*.inc,
    // ...). Epinglee en DELETE d'un seul cote, elle repassait en WAL a chaque
    // ouverture PHP -- precisement le conflit de mode journal que ce garde
    // existe pour empecher. acls.db n'a pas besoin d'y figurer : elle est
    // deja refusee en amont par isDaemonOwnedDb().
    private function isNoWalPinDb(){
        static $noWalPin = ["sys_schedules.db"=>true, "haproxy.db"=>true];
        return isset($noWalPin[basename((string)$this->db_path)]);
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
        // Daemon-owned DBs (acls.db / nginx.db) are off-limits to PHP — same
        // guard as QUERY_SQL. Without this, the PDO open + PRAGMA
        // journal_mode=WAL below would touch the inode and fight the daemon's
        // DELETE pin. Checked before the GLOBALS table-cache so a stale cache
        // entry can't imply the table exists either.
        if($this->isDaemonOwnedDb()){
            $this->ok=false;
            $this->mysql_error="$database is not allowed here";
            writelogs(strtoupper($database)." (IF_TABLE_EXISTS): $tablename",__FUNCTION__,__FILE__,__LINE__);
            return false;
        }
        if(isset($GLOBALS["TABLES_$database"][$tablename])){return true;}

        // Same strict-mode guard as QUERY_SQL — never let an absent file
        // become a blank auto-created PDO DB on the pooled DB paths.
        if($this->StrictFileMustExist && !is_file($this->db_path)){
            return false;
        }

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
            $db->exec("PRAGMA busy_timeout=30000");
            // Pin WAL — see QUERY_SQL comment for rationale. NEVER on a
            // daemon-owned DB (it pins DELETE); the guard above already
            // returns before here, this is belt-and-suspenders.
            if(!$this->ReadOnly && !$this->isDaemonOwnedDb() && !$this->isNoWalPinDb()){
                $db->exec("PRAGMA journal_mode=WAL");
            }
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


    // QUERY_SQL_PARAMS($sql, array $params) PREPARES $sql and binds $params.
    // Use it whenever a value comes from $_POST/$_GET or from anything a user
    // can influence. Placeholders are PDO's: positional "?" with a plain list,
    // or named ":name" with an associative array.
    //
    //   $q->QUERY_SQL_PARAMS("UPDATE nics SET enabled=? WHERE Interface=?",
    //                        array($enabled, $eth));
    //
    // Why a separate METHOD rather than an optional 2nd argument on QUERY_SQL:
    // ~2100 call sites across the console already pass a second argument — the
    // legacy MySQL database name ($q->QUERY_SQL($sql,"artica_backup")). An
    // optional $params would silently swallow that string and hand it to
    // PDOStatement::execute(), breaking those callers. The name is the guard.
    //
    // Both forms share one execution path (this method only stashes the binds
    // and delegates), so the error handling, the /dbpool/refresh notification
    // and the last-insert-id bookkeeping cannot drift between them.
    //
    // Note SQL identifiers — table and column names — can NEVER be bound; those
    // still have to be validated against a whitelist by the caller.
    public	function QUERY_SQL_PARAMS($sql,$params){
        $this->bindParams = is_array($params) ? $params : array();
        return $this->QUERY_SQL($sql);
    }

    public	function QUERY_SQL($sql){
        // Recupere et REARME immediatement: toute sortie anticipee ci-dessous
        // laisserait sinon les binds en place pour la requete suivante.
        $params = $this->bindParams;
        $this->bindParams = null;
        $this->ok=true;
        $chmod=false;
        $LASTID=false;
        $file="";$function="";$line="";

        if(function_exists("debug_backtrace")){
            $trace=debug_backtrace();

            if(isset($trace[0])){
                $file=basename($trace[0]["file"]);
                $function=$trace[0]["function"];
                $line=$trace[0]["line"];
            }

            if(isset($trace[1])){
                $file=basename($trace[1]["file"]);
                $function=$trace[1]["function"];
                $line=$trace[1]["line"];
            }



        }



        if($this->isDaemonOwnedDb()){
            $base=basename($this->db_path);
            $this->ok=false;
            writelogs(strtoupper($base).": $sql $file/$function/$line",__FUNCTION__,__FILE__,__LINE__);
            $this->mysql_error="$base is not allowed here";
            return array();
        }

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
        // Strict mode (default-on for the articarest-replicated DBs):
        // refuse to open a missing file. Without this, PDO would create
        // a blank SQLite DB at $this->db_path in DEFAULT rollback-journal
        // mode and the @chmod 0777 below would obliterate the daemon's
        // in-flight WAL state during a cluster restore — the failure
        // already documented in class.squid.sqlite.inc QUERY_SQL.
        if($this->StrictFileMustExist && !is_file($this->db_path)){
            $this->ok=false;
            $this->mysql_error="Line:". __LINE__." database file does not exist: $this->db_path";
            return array();
        }
        if(!is_file($this->db_path)){$chmod=true;}
        $this->ok=true;
        $dsn="sqlite:$this->db_path";


        try {
            $db = new PDO($dsn);
            //$db->setAttribute(PDO::ATTR_TIMEOUT, 5000);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

            // 0660, pas 0777 : une base creee ici porte des donnees de la console
            // (comptes, certificats, historique). Un FICHIER world-writable n'a pas
            // d'equivalent du sticky bit — n'importe quel utilisateur local pouvait
            // le reecrire (CIS 6.1.10). php-fpm tourne en www-data, proprietaire du
            // fichier ; root ignore les modes. aperms.hardenSQLiteDatabases()
            // applique le meme 0660 cote demon, les deux valeurs doivent rester
            // identiques sinon le mode oscille a chaque passage.
            if($chmod){@chmod($this->db_path, 0660);}
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
            $db->exec("PRAGMA busy_timeout=30000");
            // Pin WAL journal mode. WAL persistence only holds while the
            // -wal sibling exists; if it was deleted mid-cluster-restore
            // (or by a checkpoint truncate, or never set on a freshly
            // auto-created file) the DB silently demotes to rollback-
            // journal. Setting WAL here is idempotent in WAL mode and
            // converts back from rollback-journal automatically. Skipped
            // for read-only callers — query_only=1 is set below — and for
            // daemon-owned DBs (the guard above already returns first; this
            // is belt-and-suspenders so the WAL-pin can never fight DELETE).
            if(!$this->ReadOnly && !$this->isDaemonOwnedDb() && !$this->isNoWalPinDb()){
                $db->exec("PRAGMA journal_mode=WAL");
            }
            if($params===null){
                $results = $db->query($sql);
            }else{
                // Chemin prepare: la valeur n'est jamais concatenee au SQL,
                // donc rien de ce qu'elle contient ne peut etre interprete.
                // L'erreur vit sur le STATEMENT, pas sur la connexion, d'ou le
                // message construit ici plutot que dans la branche partagee
                // plus bas (qui lit $db->errorInfo() et rendrait "00000").
                $results = false;
                $stmt = $db->prepare($sql);
                if($stmt===false){
                    $this->ok=false;
                    $this->mysql_error="Line:". __LINE__." prepare failed: ".@implode(" ", $db->errorInfo())." in $this->db_path";
                    $this->SQLITE_ERROR($db->errorInfo(),debug_backtrace(),$sql_log);
                    return array();
                }
                if(!$stmt->execute($params)){
                    $this->ok=false;
                    $this->mysql_error="Line:". __LINE__." execute failed: ".@implode(" ", $stmt->errorInfo())." in $this->db_path";
                    $this->SQLITE_ERROR($stmt->errorInfo(),debug_backtrace(),$sql_log);
                    return array();
                }
                $results = $stmt;
            }
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

        // Hint the daemon that this DB just took a write. Cheap when
        // not applicable (StrictFileMustExist=false short-circuits);
        // placed BEFORE both success returns so DELETE and the normal
        // fetchAll path both benefit, and BEFORE $db is released so
        // PHP still holds the WAL reader while the daemon resets its
        // pool — see notifyDaemonRefresh() for the ordering rationale.
        $this->notifyDaemonRefresh();
        if(preg_match("#^DELETE\s+#i", $sql)){return true;}

        $FINAL=$results->fetchAll(PDO::FETCH_ASSOC);
        $db=null;
        if($LASTID){$this->get_last_id($LASTID_table);}
        return $FINAL;



    }



    // notifyDaemonRefresh sends a fire-and-forget POST /dbpool/refresh
    // to articarest so the daemon can preemptively HardReset its
    // dbpool entry for this DB. Necessary because closing the last
    // WAL reader of a pooled SQLite DB can recreate the SHM inode,
    // leaving the daemon's mmap pointing at the unlinked old inode —
    // every subsequent daemon read then returns "disk I/O error" or
    // "database disk image is malformed" until restart (see the
    // dbpool gotcha in articarest CLAUDE.md).
    //
    // Coalesced in two layers:
    //   1. PHP per-request: self::$daemonNotified skips repeats for
    //      the same path within one page load.
    //   2. Daemon-side: rest_dbpool.go throttles HardResets to one
    //      per path per 30s window.
    //
    // Only fires when $this->StrictFileMustExist is true (i.e. the
    // articarest-replicated pooled DBs) AND the SQL is a mutation;
    // reads don't grow the WAL or change the schema cookie.
    //
    // Called BEFORE $db is released so the daemon's HardReset
    // happens while PHP still holds the WAL reader; when PHP later
    // closes and SQLite potentially recreates the SHM, the daemon's
    // pool is already empty and the next Get mmaps the fresh SHM.
    private function notifyDaemonRefresh(){
        if(!$this->StrictFileMustExist){return;}
        // Match the SQL families that mutate the file. Aligned with
        // the daemon's notion of "a write": INSERT/UPDATE/DELETE/
        // REPLACE plus DDL (CREATE/ALTER/DROP). Reads short-circuit.
        if(!preg_match(
            "#^\s*(INSERT\s+INTO|UPDATE|DELETE\s+FROM|REPLACE\s+INTO|ALTER\s+TABLE|DROP\s+(TABLE|INDEX|VIEW|TRIGGER)|CREATE\s+(TABLE|INDEX|VIEW|TRIGGER))\b#i",
            $this->sql)){
            return;
        }
        if(isset(self::$daemonNotified[$this->db_path])){return;}
        self::$daemonNotified[$this->db_path] = true;

        $socketPath = "/usr/share/artica-postfix/bin/run/articarest.sock";
        if(!file_exists($socketPath)){return;}

        $ch = @curl_init("http://localhost/dbpool/refresh");
        if($ch === false){return;}
        @curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $socketPath);
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["path" => $this->db_path]));
        @curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 100ms hard ceiling — a daemon hiccup must never block the
        // PHP page render. If the daemon doesn't answer in time the
        // worst case is one missed proactive refresh; the daemon's
        // own dbpool.HardReset path is still available reactively
        // when a query fails with disk-I/O error.
        @curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100);
        @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 100);
        @curl_exec($ch);
        @curl_close($ch);
    }

    private function SQLITE_ERROR($error,$debug,$sql){
        writelogs("SQLITE_ERROR $sql",__FUNCTION__,__FILE__,__LINE__);
        if(isset( $GLOBALS["SQLITE_NO_SAVE_ERRORS"])){return true;}
        if(preg_match("#(INSERT|CREATE).*?sql_errors#i",$sql)){return false;}
        if(strpos($sql,"adminprivs")>0){return true;}

        // Dropped as DATA (JSON), imported by articarest notifs.checkSQLErrors() with bound
        // parameters. It used to be a ready-made "INSERT ..." the daemon executed as is, so any
        // www-data process could run SQL on system_events.db through this directory.
        $subject="SQLite Error";
        if(!is_array($error)){
            $subject=(string)$error;
            $error=base64_encode((string)$error);
        }else {
            $error = base64_encode(serialize($error));
        }
        $doc=json_encode(array(
            "zdate"=>time(),
            "subject"=>substr($subject,0,2048),
            "error"=>$error,
            "sql"=>base64_encode((string)$sql),
            "database"=>(string)$this->db_path,
            "debug"=>base64_encode(serialize($debug)),
        ),JSON_PARTIAL_OUTPUT_ON_ERROR|JSON_INVALID_UTF8_SUBSTITUTE);
        if(!is_string($doc)){return true;}
        $dir="/usr/share/artica-postfix/ressources/logs/sql_errors";
        $tmp="$dir/.".md5($doc.microtime()).".tmp";
        // Written then renamed: the daemon never reads a half-written file.
        if(@file_put_contents($tmp,$doc)!==false){@rename($tmp,"$dir/".md5($doc).".json");}
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