<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(isset($_SESSION["TIMEZONES"])){if(function_exists("getLocalTimezone")){@date_default_timezone_set($_SESSION["TIMEZONES"]);}}
if(isset($GLOBALS["TIMEZONES"])){if(function_exists("getLocalTimezone")){@date_default_timezone_set($GLOBALS["TIMEZONES"]);}}
include_once(dirname(__FILE__).'/class.users.menus.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/class.familysites.inc');
include_once(dirname(__FILE__).'/class.tcpip.inc');
include_once(dirname(__FILE__).'/class.sockets.inc');
include_once(dirname(__FILE__).'/class.system.nics.inc');
class mysql_pdns{
	var $ok=false;
	var $mysql_error;
	var $UseMysql=true;
	var $database="powerdns";
	var $mysql_server;
	var $username;
	var $password;
	var $mysql_port;
	var $MysqlFailed=false;
	var $last_id=0;
	var $SocketName=null;
	var $MYSQL_CMDLINES=null;
	private $mysqli_connection;
	private $PowerDNSEnableClusterMaster=0;
	
	
	function __construct(){
		$this->LoadSettings();	
		if(!$this->TestingConnection()){$this->MysqlFailed=true;}
	}
	
	public function DATABASE_EXISTS($database){
		
		$sql="SHOW DATABASES";
		$this->TestingConnection();
		$results=@mysqli_query($this->mysqli_connection,$sql);
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
            if(isset($ligne["Databases"])) {
                if (strtolower($database) == strtolower($ligne["Databases"])) {
                    return true;
                }
            }

            if(isset($ligne["Database"])) {
                if (strtolower($database) == strtolower($ligne["Database"])) {
                    return true;
                }
            }
		}
		return false;
	
	}
	
	private function LoadSettings(){
		
		$this->username="powerdns";
		$this->password=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSQLPassword");
		if($this->password==null){$this->password="powerdns";}
		$passwordcmdline=$this->password;
		$passwordcmdline=$this->shellEscapeChars($passwordcmdline);
		$this->MYSQL_CMDLINES="-S /var/run/mysqld/mysqld.sock -u $this->username -p$passwordcmdline";
			
		
		
	}
	private function shellEscapeChars($path){
		$path=str_replace(" ","\ ",$path);
		$path=str_replace('$','\$',$path);
		$path=str_replace("&","\&",$path);
		$path=str_replace("?","\?",$path);
		$path=str_replace("#","\#",$path);
		$path=str_replace("[","\[",$path);
		$path=str_replace("]","\]",$path);
		$path=str_replace("{","\{",$path);
		$path=str_replace("}","\}",$path);
		$path=str_replace("*","\*",$path);
		$path=str_replace('"','\\"',$path);
		$path=str_replace("'","\\'",$path);
		$path=str_replace("(","\(",$path);
		$path=str_replace(")","\)",$path);
		$path=str_replace("<","\<",$path);
		$path=str_replace(">","\>",$path);
		$path=str_replace("!","\!",$path);
		$path=str_replace("+","\+",$path);
		$path=str_replace(";","\;",$path);
		return $path;
	}
	public function HIER(){
		$sql="SELECT DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 DAY),'%Y-%m-%d') as tdate";
		$ligne=mysqli_fetch_array($this->QUERY_SQL($sql));
		return $ligne["tdate"];
	}
	
	function cluster_table($tablename,$filename){
		$database="powerdns";
		$unix=new unix();
		$mysqldump=$unix->find_program("mysqldump");
		$gzip=$unix->find_program("gzip");
		$MYSQL_CMDLINES=$this->MYSQL_CMDLINES;
		$Rows=$this->COUNT_ROWS($tablename, $database);
		@mkdir("/usr/share/artica-postfix/ressources/logs/clusters",0755,true);
		$targetgz="/usr/share/artica-postfix/ressources/logs/clusters/$database.$tablename.gz";
		$cmdline="$mysqldump --skip-comments $MYSQL_CMDLINES $database $tablename | $gzip >$targetgz";
		@chmod(0755,$targetgz);
		echo "$cmdline\n";
		shell_exec("$cmdline");
		$MAIN["MD5"]=md5_file($targetgz);
		$MAIN["ROWS"]=$Rows;
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/clusters/$database.$tablename.array", serialize($MAIN));
	}

	
	public function LIST_TABLES_POWERDNS(){
		if(isset($GLOBALS["LIST_TABLES_POWERDNS"])){return $GLOBALS["LIST_TABLES_POWERDNS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = '$this->database'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	
	
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$array[$ligne["c"]]=$ligne["c"];
		}
		$GLOBALS["LIST_TABLES_POWERDNS"]=$array;
		return $array;
	
	}
	
	

	
	public function TestingConnection(){
		$funcclass=__CLASS__."/".__FUNCTION__;

		if(!function_exists("mysqli_connect")){
		    return false;
        }

		if(function_exists("mysqli_ping")) {
            if (@mysqli_ping($this->mysqli_connection)) {
                return true;
            }
        }
		
		if($GLOBALS["VERBOSE"]){$bd=mysqli_connect("localhost",$this->username,$this->password,null,0,"/var/run/mysqld/mysqld.sock");}
		if(!$GLOBALS["VERBOSE"]){$bd=@mysqli_connect("localhost",$this->username,$this->password,null,0,"/var/run/mysqld/mysqld.sock");}
			
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

	public function COUNT_ROWS($table,$database=null){
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		if(!function_exists("mysqli_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysqli_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Rows"]==null){$ligne["Rows"]=0;}
		return $ligne["Rows"];
		
	}

	public function TABLE_SIZE($table){
		if(!function_exists("mysqli_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysqli_fetch_array($this->QUERY_SQL($sql));
		if($ligne["Data_length"]==null){$ligne["Data_length"]=0;}
		if($ligne["Index_length"]==null){$ligne["Index_length"]=0;}
		return $ligne["Index_length"]+$ligne["Data_length"];	
		}
	
	public function TABLE_EXISTS($table){
		$keyCache=__FUNCTION__;

		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		
		if(!$this->DATABASE_EXISTS("powerdns")){ if(!$this->CREATE_DATABASE("powerdns")){return false;} }
		
		$sql="SHOW TABLES";
		$results=$this->QUERY_SQL($sql);
		$result=false;
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
            if(isset($ligne["Tables_in_powerdns"])) {
                if (strtolower($table) == strtolower($ligne["Tables_in_powerdns"])) {
                    return true;
                }
            }
            if(isset($ligne["Index"])){
                if (strtolower($table) == strtolower($ligne["Index"])) {
                    return true;
                }
            }
		}
		
		return $result;
	}

	public function FIELD_EXISTS($table,$field,$database=null){
		$field=trim($field);
		if(isset($GLOBALS["__FIELD_EXISTS"])){
				if(isset($GLOBALS["__FIELD_EXISTS"][$database][$table])){
					if(isset($GLOBALS["__FIELD_EXISTS"][$database][$table][$field])){
						if($GLOBALS["__FIELD_EXISTS"][$database][$table][$field]==true){return true;}
					}
				}
		}
		$sql="SHOW FULL FIELDS FROM `$table` WHERE Field='$field';";
		$ligne=@mysqli_fetch_array($this->QUERY_SQL($sql,$database));
		
		if(trim($ligne["Field"])<>null){
			$GLOBALS["__FIELD_EXISTS"][$database][$table][trim($field)]=true;
			return true;
		}
	}

	public function search_sql($search=null,$domain_id=0){
        $table="( SELECT records.*,domains.name as domain FROM records,domains WHERE records.domain_id=domains.id ) as t";
        $sql="SELECT * FROM $table LIMIT 0,100";
        $name_search2=null;
        $search_type=null;
        $domain_query=null;
        $NF=false;

        if($domain_id>0){
            $domain_query=" AND domain_id=$domain_id";
            $sql="SELECT * FROM $table WHERE domain_id=$domain_id LIMIT 0,100";
        }

        if($search<>null){

            if(preg_match("#VERBOSE#",$search,$re)){
                $GLOBALS["VERBOSE"]=true;
                $search=trim(str_replace("VERBOSE","",$search));
                ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
            }

            if(preg_match("#^(.*?)(\s|where|and)+type[\s|=]+(.+?)($|\s+)#i",$search,$re)) {
                $search = trim($re[1]);
                $search_type = strtoupper($re[3]);
                $NF = true;
            }

            if(!$NF){
                VERBOSE("preg_match1 [$search] not found",__LINE__);
                if(preg_match("#^type[\s|=]+(.+?)($|\s+)#i",trim($search),$re)){
                    $search="%";
                    $search_type=strtoupper($re[1]);
                    $NF = true;
                }else{
                    VERBOSE("preg_match2 [$search] not found",__LINE__);
                }
            }




            if(preg_match("#([0-9\.]+)$#",$search,$re)){
                VERBOSE("FOUND IPADDR IN $search",__LINE__);
                $ptre=explode(".",$re[1]);
                foreach ($ptre as $num){
                    if(!is_numeric($num)){
                        VERBOSE("EXPLODED '$num' SKIP",__LINE__);
                        continue;}
                    VERBOSE("EXPLODED '$num' ADD",__LINE__);
                    $FormatedIP[]=$num;
                }
                krsort($FormatedIP);
                $reversed=@implode(".",$FormatedIP);
                $name_search2="name LIKE '%$reversed%'";
            }else{
                VERBOSE("preg_match3 [$search] not found #([0-9\.]+)#");
            }

            if(strpos($search," ")>0){
                $exploded=explode(" ",$search);
                $search=$exploded[0];
            }

            $search="%".str_replace("*", "%", $search)."%";
            $search=str_replace("%%", "%", $search);
            $name_search="name LIKE '$search'";
            if($name_search2<>null){$name_search="($name_search) OR ($name_search2)";}


            $sql="SELECT * FROM $table  WHERE ( (`type` LIKE '$search') OR (domain LIKE '$search') OR ($name_search) OR (content LIKE '$search') ){$domain_query} LIMIT 0,100";




            if($search_type<>null){
                $sql="SELECT * FROM $table  WHERE ( (domain LIKE '$search') OR ($name_search) OR (content LIKE '$search') ) AND (`type`='$search_type'{$domain_query}) LIMIT 0,100";
            }



        }

        return $sql;

    }



	public function QUERY_SQL($sql,$database=null){
		$funcclass=__CLASS__."/".__FUNCTION__;
		$this->ok=true;
		$this->last_id=0;
		if(!$this->TestingConnection()){return false;}
		$ok=@mysqli_select_db($this->mysqli_connection,"powerdns");
		
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
            if($errnum==1064){
                if(preg_match("#already exists#",$des)){
                    $this->ok=true;
                    @mysqli_free_result($this->mysqli_connection);
                    if ($this->mysqli_connection instanceof mysqli) { @mysqli_close($this->mysqli_connection); }
                    $this->mysqli_connection=false;
                    return "";
                }
            }

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

    public function GET_CHARACTER_SET($tablename){
        $sql="SHOW TABLE STATUS WHERE NAME LIKE '$tablename'";
        $ligne=@mysqli_fetch_array($this->QUERY_SQL($sql));
        return strtolower($ligne["Collation"]);
    }


    private function FIELD_TYPE($table,$field,$database){
		$sql="SHOW FULL FIELDS FROM $table WHERE Field='$field';";
		$ligne=@mysqli_fetch_array($this->QUERY_SQL($sql));
		$GLOBALS["__FIELD_TYPE"][$database][$table][$field]=strtolower($ligne["Type"]);
		return strtolower($ligne["Type"]);
	}

	public function mysqli_fetch_array($sql){
        $ligne=@mysqli_fetch_array($this->QUERY_SQL($sql));
        return $ligne;
    }
	
	private FUNCTION INDEX_EXISTS($table,$index,$database){
	$sql="SHOW INDEX FROM $table WHERE Key_name='$index'";
		$ligne=@mysqli_fetch_array($this->QUERY_SQL($sql));
		if($ligne["Key_name"]<>null){
			$_SESSION["MYSQL_INDEX_EXISTS"][$database][$table][$index]=true;
			return true;
		}else{return true;}
	}
	
	public FUNCTION CREATE_DATABASE(){
		if($this->DATABASE_EXISTS("powerdns")){
			$this->ok=true;
			return true;
		}
  		$this->TestingConnection();
		$results=@mysqli_query($this->mysqli_connection,"CREATE DATABASE `powerdns`");
  		
  		if(@mysqli_error($this->mysqli_connection)){
			$time=date('h:i:s');
			$errnum=@mysqli_errno($this->mysqli_connection);
			$des=@mysqli_error($this->mysqli_connectiond);
			if(preg_match("#database exists#", $des)){$this->ok=true;return true;}
			$this->mysql_error="CREATE DATABASE powerdns -> Error Number ($errnum) ($des)";
			return false;
		}

		$this->ok=true;
		return true;
	}

	public function EVENTS_SUM(){
		$sql="SELECT SUM(TABLE_ROWS) as tsum FROM information_schema.tables WHERE table_schema = '$this->database' AND table_name LIKE 'dansguardian_events_%'";
		$ligne=mysqli_fetch_array($this->QUERY_SQL($sql));
		if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		writelogs("{$ligne["tsum"]} : $sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		return $ligne["tsum"];
		
	}	
	
	public function GetDomainID($domainname){
		$sql="select id from domains where name='$domainname';";
		$ligne=mysqli_fetch_array($this->QUERY_SQL($sql));
		if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		return intval($ligne["id"]);
	}
	
	public function GetDomainName($domain_id){
		if($domain_id==0){return "{all_domains}";}
		$sql="select name from domains where id='$domain_id';";
		$ligne=mysqli_fetch_array($this->QUERY_SQL($sql));
		if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		return trim($ligne["name"]);		
	}
	
	public function AllowDNSUpdates($domainname,$ipaddr){
		if($GLOBALS["VERBOSE"]){echo "\n";}
		$domain_id=$this->GetDomainID($domainname);
		if($domain_id==0){$this->AddDomain($domainname);$domain_id=$this->GetDomainID($domainname);}
		if($domain_id==0){
			if($GLOBALS["VERBOSE"]){echo "AddDomain:: $domainname return id 0 ".__FUNCTION__." in line ". __LINE__."\n";}
			return false;}
		
		$sql="select id from domainmetadata where domain_id='$domain_id' AND kind='ALLOW-DNSUPDATE-FROM' and content='$ipaddr';";
		$ligne=mysqli_fetch_array($this->QUERY_SQL($sql));
		$ID=intval($ligne["id"]);
		
		if($GLOBALS["VERBOSE"]){echo "AllowDNSUpdates($domainname,$ipaddr) domain_id=$domain_id ===> '{$ID}'\n";}
		if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		
	
		if($ID==0){
			$this->QUERY_SQL("insert into domainmetadata(domain_id, kind, content) values($domain_id, 'ALLOW-DNSUPDATE-FROM','$ipaddr');");
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		}
		return true;
	}
	
	public function sigkeys($keyname,$secret){
		$sql="select secret from tsigkeys where `name`='$keyname';";
		$ligne=mysqli_fetch_array($this->QUERY_SQL($sql));
		if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		if(trim($ligne["secret"])==trim($secret)){return true;}
		if(trim($ligne["secret"])<>null){
			$this->QUERY_SQL("UPDATE tsigkeys set secret='$secret' WHERE `name`='$keyname';");
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
			return true;
		}
		
		$this->QUERY_SQL("insert into tsigkeys (name, algorithm, secret) values ('$keyname', 'hmac-md5', '$secret');");
		if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		return true;
		
	}
	
	public function AllowTSIGUpdates($domainname,$keyname){
		if($GLOBALS["VERBOSE"]){echo "\n";}
		$domain_id=$this->GetDomainID($domainname);
		if($domain_id==0){$this->AddDomain($domainname);}
		if($domain_id==0){return false;}
	
		$sql="SELECT id from domainmetadata where domain_id='$domain_id' AND kind='TSIG-ALLOW-DNSUPDATE' AND content='$keyname';";
		$ligne=mysqli_fetch_array($this->QUERY_SQL($sql));
		$ID=intval($ligne["id"]);
		
		
		if($GLOBALS["VERBOSE"]){echo "AllowTSIGUpdates($domainname,$keyname) domain_id=$domain_id ===> '$ID'\n";}
		if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		if($ID==0){
			$this->QUERY_SQL("insert into domainmetadata(domain_id, kind, content) values($domain_id, 'TSIG-ALLOW-DNSUPDATE','$keyname');");
			if(!$this->ok){writelogs("$this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		}
		return true;
	}	
	
	
	
	
	
	public function CheckTables(){
		if(!$this->DATABASE_EXISTS($this->database)){$this->CREATE_DATABASE($this->database);}
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`pdns_wpad` (
			`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`network` varchar( 60 ) NOT NULL ,
			`ipaddr` varchar(40) NOT NULL,
			KEY `network` (`network`),
			KEY `ipaddr` (`ipaddr`)
		    ) ENGINE=MYISAM";
		$this->QUERY_SQL($sql);
		
	}
	
    private function AddDomainInPDNS($domain):int{
       $EnablePDNS      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
       if($EnablePDNS==0){return 0;}

       $RecursorKey     = "PowerDNSEnableRecursor";
       $Recursor        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($RecursorKey));
       $MyHostname      = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));

        $q              = new mysql_pdns();

        if(!$q->FIELD_EXISTS("domains","options","powerdns")) {
            writelogs("---- pdnsutil Patching domains table with options field",__FUNCTION__,__FILE__,__LINE__);
            $q->QUERY_SQL("ALTER TABLE domains ADD options VARCHAR(64000) DEFAULT NULL;");
            if(!$q->ok){
                writelogs("pdnsutil Patching domains table with options field (1) failed $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
                if(preg_match("#olumn length too big for column#",$q->mysql_error)){
                    writelogs("pdnsutil Patching domains table with options 16383",__FUNCTION__,__FILE__,__LINE__);
                    $q->QUERY_SQL("ALTER TABLE domains ADD options VARCHAR(16383) DEFAULT NULL;");
                    if(!$q->ok){
                        writelogs("ADD options VARCHAR(16383 -> $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
                    }
                }

            }
        }



        if(!$q->FIELD_EXISTS("domains","catalog","powerdns")) {
            $q->QUERY_SQL("ALTER TABLE domains ADD catalog VARCHAR(255) DEFAULT NULL;");
            $q->QUERY_SQL("CREATE INDEX catalog_idx ON domains(catalog);");
        }



        $domainenc      = base64_encode($domain);
        $MyHostnameenc  = base64_encode($MyHostname);
        $MyIP           = base64_encode($_SERVER["SERVER_ADDR"]);
        $cmd            = "pdns.php?create-zone=$domainenc&ns=$MyHostnameenc&ip=$MyIP";
        $sql            = "SELECT id FROM domains WHERE name='$domain'";
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork($cmd);
        $ligne          = mysqli_fetch_array($q->QUERY_SQL($sql));
        $domain_id      = intval($ligne["id"]);
        if($domain_id==0){
            $logfile=PROGRESS_DIR."/pdns.add.domain.txt";
            echo "FAILED (AddDomainInPDNS($domain)!!!!\n";
            $ff=explode("\n",@file_get_contents($logfile));
            foreach ($ff as $line){
                echo "$line\n";
            }

            return 0;
        }




        if(function_exists("admin_tracks")){
            admin_tracks("Creating new DNS domain $domain For PowerDNS");
        }
        // SP973 remove suspicious garbages


        if($Recursor){
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("pdns.php?reload-recusor=yes");
        }
        $this->RemoveGarbageDomain($domain_id,$domain);
        return $domain_id;

    }

    public function RemoveGarbageDomain($domain_id,$domainname):bool{
        $EnablePDNS      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
        if($EnablePDNS==0){return true;}
        // SP973 remove suspicious garbages
        $q              = new mysql_pdns();
        $q->QUERY_SQL("DELETE FROM records WHERE type is null AND domain_id=$domain_id");
        if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
        $q->QUERY_SQL("DELETE FROM records WHERE content='127.0.0.1' AND domain_id=$domain_id");
        if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
        $q->QUERY_SQL("DELETE FROM records WHERE name NOT LIKE '%$domainname' AND domain_id=$domain_id");
        if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
        return true;
    }

	
	public function AddDomain($domain){
		$q=new mysql_pdns();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT id FROM domains WHERE name='$domain'"));
		$domain_id=intval($ligne["id"]);
        $MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));

        if($domain_id==0){ $domain_id=$this->AddDomainInPDNS($domain); }

		if($domain_id==0){
			$sql="INSERT INTO domains (name, master, last_check, type, notified_serial, account) VALUES ('$domain', NULL, NULL, 'NATIVE', NULL, NULL);";
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error."<br>$sql";return false;}
			$domain_id=$q->last_id;
		}
	
		if($domain_id==0){return 0;}
		$time=time();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT id FROM records WHERE name='$domain' AND type='SOA'"));
		$id=intval($ligne["id"]);
		if($id==0){
            if(!$q->FIELD_EXISTS("records","change_date")){
                $q->QUERY_SQL("ALTER TABLE records ADD change_date INT DEFAULT NULL");
            }


			$sql="INSERT INTO `records` (domain_id,name,type,content,ttl,prio,change_date) VALUES ('$domain_id', '$domain', 'SOA', 'ns.$domain hostmaster.$domain 0 10800 1800 604800 86400', '3600', '0', '$time')";
			$q->QUERY_SQL($sql);
			if(!$q->ok){echo $q->mysql_error."<br>$sql";return 0;}
            if(function_exists("admin_tracks")){admin_tracks("Creating new DNS domain $domain");}
		}
		if(!preg_match("#in-addr\.arpa#i",$domain)) {

            $ligne = mysqli_fetch_array($q->QUERY_SQL("SELECT id FROM records WHERE name='$domain' AND type='A'"));
            $id = intval($ligne["id"]);

            if ($id == 0) {
                $sql = "INSERT INTO `records` (domain_id,name,type,content,ttl,prio,change_date) VALUES ('$domain_id', '$domain', 'A', '127.0.0.1', '3600', '0', '$time')";
                $q->QUERY_SQL($sql);
                if (!$q->ok) {
                    echo $q->mysql_error . "<br>$sql";
                    return 0;
                }

            }
            $ligne = mysqli_fetch_array($q->QUERY_SQL("SELECT id FROM records WHERE name='$domain' AND type='NS'"));
            $id = intval($ligne["id"]);


            if ($id == 0) {
                $sql = "INSERT INTO `records` (domain_id,name,type,content,ttl,prio,change_date) VALUES ('$domain_id', '$domain', 'NS', '$MyHostname', '3600', '0', '$time')";
                $q->QUERY_SQL($sql);
                if (!$q->ok) {
                    echo $q->mysql_error . "<br>$sql";
                    return 0;
                }

            }

            $ligne = mysqli_fetch_array($q->QUERY_SQL("SELECT id FROM records WHERE name='$MyHostname' AND type='A' and domain_id=$domain_id"));
            $id = intval($ligne["id"]);

            if ($id == 0) {
                $nicz = new system_nic("eth0");
                $sql = "INSERT INTO records (domain_id, name, content, type, ttl, prio) VALUES ($domain_id, '$MyHostname', '{$nicz->IPADDR}', 'A', 86400, NULL);";
                $q->QUERY_SQL($sql);
                if (!$q->ok) {
                    echo $q->mysql_error . "<br>$sql";
                    return 0;
                }

            }

        }
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("pdns.php?dnssec=yes");
        $this->RemoveGarbageDomain($domain_id,$domain);
		return $domain_id;
		
	
	}
	




private function events($text){
	
	
	$logFile="/var/log/mysql.postfix.log";
	if(!isset($GLOBALS["CLASS_UNIX"])){
		include_once("/usr/share/artica-postfix/framework/class.unix.inc");
		$GLOBALS["CLASS_UNIX"]=new unix();
	}
	
	if(function_exists("debug_backtrace")){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
				$filename=basename($trace[1]["file"]);
				$function=$trace[1]["function"];
				$line=$trace[1]["line"];
		}
	}
	
	$GLOBALS["CLASS_UNIX"]->events("$filename/$function $text [$line]",$logFile);
}

	
	
}