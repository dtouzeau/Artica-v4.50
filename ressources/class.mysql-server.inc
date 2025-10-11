<?php
include_once(dirname(__FILE__). '/class.ini.inc');
include_once(dirname(__FILE__). '/class.sockets.inc');
include_once(dirname(__FILE__). '/class.users.menus.inc');
include_once(dirname(__FILE__). '/class.mysql.inc');
include_once(dirname(__FILE__). '/class.mysql-server-multi.inc');
class mysqlserver{
	
	var $main_array=array();
	var $mysql_version_string=0;
	var $mysqlvbin=0;
	var $innodb_file_per_table=0;
	var $VERSION_MAJOR;
	var $VERSION_MINOR;
	var $VERSION_REV;
	var $EnableZarafaTuning=0;
	var $ZARAFA_INSTALLED=false;
	var $MYSQL_BIN_PARAMS=array();
	var $INNODB_FILE_PER_TABLE_INSTALL=false;
	var $MYSQL_DATA_DIR="";
	
	function __construct(){
		$this->load();
		$users=new usersMenus();
		$this->mysql_version_string=$users->mysqld_version;
		$sock=new sockets();
		$EnableZarafaTuning=$sock->GET_INFO("EnableZarafaTuning");
		if(!is_numeric($EnableZarafaTuning)){$EnableZarafaTuning=0;}
		$users=new usersMenus();
		$this->EnableZarafaTuning=$EnableZarafaTuning;
		$this->ZARAFA_INSTALLED=$users->ZARAFA_INSTALLED;
		$this->INNODB_FILE_PER_TABLE_INSTALL=$users->INNODB_FILE_PER_TABLE_INSTALL;
   		$this->MYSQL_DATA_DIR=$sock->GET_INFO("ChangeMysqlDir");
		if($this->MYSQL_DATA_DIR==null){$this->MYSQL_DATA_DIR="/var/lib/mysql";}		
		
		if(posix_getuid()==0){
			$GLOBALS["AS_ROOT"]=true;
			echo "Starting......: ".date("H:i:s")." MySQL version.......:$this->mysql_version_string\n";
			
			
		}
		
		if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)#",$this->mysql_version_string,$re)){
			if(strlen($re[1])==1){$re[1]=$re[1].'0';}
			if(strlen($re[2])==1){$re[2]=$re[2].'0';}
			if(strlen($re[3])==1){$re[3]=$re[3].'0';}
			$this->VERSION_MAJOR=$re[1];
			$this->VERSION_MINOR=$re[2];
			$this->VERSION_REV=$re[3];
			$this->mysqlvbin=$re[1].$re[2].$re[3];
		}
		
	}
	
	
	private function load(){
		$ini=new Bs_IniHandler();
		$sock=new sockets();
		$datas=$sock->GET_INFO("MysqlParameters");
		$this->innodb_file_per_table=$sock->GET_INFO("InnodbFilePerTable");
		if(!is_numeric($this->innodb_file_per_table)){$this->innodb_file_per_table=0;}
		if($this->INNODB_FILE_PER_TABLE_INSTALL){$this->innodb_file_per_table=1;}
		if($GLOBALS["AS_ROOT"]){
			echo "Starting......: ".date("H:i:s")." MySQL version.......:$this->mysql_version_string\n";
			echo "Starting......: ".date("H:i:s")." MySQL ..............:innodb_file_per_table = $this->innodb_file_per_table\n";
		}
		$ini->loadString($datas);
		$this->main_array=$ini->_params["MYSQL"];
		$this->BuildDefault();
		$this->LOAD_VARIABLES();
		$this->LoadMysqlParameters();
	}
	
	private function LoadMysqlParameters(){
		if(!$GLOBALS["AS_ROOT"]){return array();}
		$unix=new unix();
		$mysqld=$unix->find_program("mysqld");
		if($GLOBALS["VERBOSE"]){echo "$mysqld --verbose --help ->\n";}
		exec("$mysqld --verbose --help 2>&1",$results);
        $this->MYSQL_BIN_PARAMS=array();

		foreach ($results as $index=>$ligne){
			if(preg_match("#^(.+?)\s+#", $ligne,$re)){
				if(trim($re[1])==null){continue;}
				$re[1]=str_replace("--", "", $re[1]);
				$re[1]=str_replace("=#", "", $re[1]);
				$this->MYSQL_BIN_PARAMS[$re[1]]=true;
				
			}
			
		}

		
		
	}
	
	public function SHOW_VARIABLES(){
		return $this->LOAD_VARIABLES();
	}
	
	
	private function LOAD_VARIABLES(){
		$q=new mysql();
		return $q->SHOW_VARIABLES();
	}
	
	private function BuildDefault(){
		if(!isset($this->main_array["default-character-set"])){$this->main_array["default-character-set"]=null;}
		if(!isset($this->main_array["bind-address"])){$this->main_array["bind-address"]=null;}
		if(!isset($this->main_array["key_buffer"])){$this->main_array["key_buffer"]=null;}
		if(!isset($this->main_array["tmp_table_size"])){$this->main_array["tmp_table_size"]=null;}
		if(!isset($this->main_array["max_allowed_packet"])){$this->main_array["max_allowed_packet"]=null;}
		if(!isset($this->main_array["sort_buffer_size"])){$this->main_array["sort_buffer_size"]=null;}
		if(!isset($this->main_array["key_buffer_size"])){$this->main_array["key_buffer_size"]=null;}
		if(!isset($this->main_array["innodb_log_file_size"])){$this->main_array["innodb_log_file_size"]=null;}
		if(!isset($this->main_array["net_buffer_length"])){$this->main_array["net_buffer_length"]=null;}
		if(!isset($this->main_array["join_buffer_size"])){$this->main_array["join_buffer_size"]=null;}
		if(!isset($this->main_array["thread_cache_size"])){$this->main_array["thread_cache_size"]=null;}
		if(!isset($this->main_array["query_cache_limit"])){$this->main_array["query_cache_limit"]=null;}
		if(!isset($this->main_array["max_heap_table_size"])){$this->main_array["max_heap_table_size"]=null;}
		if(!isset($this->main_array["sort_buffer"])){$this->main_array["sort_buffer"]=null;}
		if(!isset($this->main_array["innodb_lock_wait_timeout"])){$this->main_array["innodb_lock_wait_timeout"]=null;}
		if(!isset($this->main_array["open_files_limit"])){$this->main_array["open_files_limit"]=null;}
		
		
		

		
		if($this->main_array["skip_external_locking"]==null){$this->main_array["skip_external_locking"]='yes';}
		if($this->main_array["skip_name_resolve"]==null){$this->main_array["skip_name_resolve"]='no';}
		
		
		if($this->main_array["bind-address"]=="all"){$this->main_array["bind-address"]='0.0.0.0';}
		if($this->main_array["tmp_table_size"]==null){$this->main_array["tmp_table_size"]='64';}
		if($this->main_array["table_cache"]==null){$this->main_array["table_cache"]='512';}		
		if($this->main_array["max_allowed_packet"]==null){$this->main_array["max_allowed_packet"]='100';}
		if($this->main_array["sort_buffer_size"]==null){$this->main_array["sort_buffer_size"]='8';}
		if($this->main_array["key_buffer_size"]==null){$this->main_array["key_buffer_size"]='256';}
		if($this->main_array["table_open_cache"]==null){$this->main_array["table_open_cache"]='256';}
		if($this->main_array["read_buffer_size"]==null){$this->main_array["read_buffer_size"]='1';}
		if($this->main_array["read_rnd_buffer_size"]==null){$this->main_array["read_rnd_buffer_size"]='4';}
		if($this->main_array["myisam_sort_buffer_size"]==null){$this->main_array["myisam_sort_buffer_size"]='64';}
		if($this->main_array["query_cache_size"]==null){$this->main_array["query_cache_size"]='16';}
		if(count($this->MYSQL_BIN_PARAMS)<3){$this->LoadMysqlParameters();}
		$this->FixNumericValues();	
	}
	
	private function FixNumericValues(){
		$numeric_fields["join_buffer_size"]=true;
		$numeric_fields["thread_cache_size"]=true;
		$numeric_fields["query_cache_limit"]=true;
		$numeric_fields["query_cache_size"]=true;
		$numeric_fields["key_buffer"]=true;
		$numeric_fields["innodb_log_file_size"]=true;
		$numeric_fields["max_allowed_packet"]=true;
		$numeric_fields["max_heap_table_size"]=true;
		$numeric_fields["myisam_sort_buffer_size"]=true;
		$numeric_fields["read_buffer_size"]=true;
		$numeric_fields["table_open_cache"]=true;
		$numeric_fields["key_buffer_size"]=true;
		$numeric_fields["table_cache"]=true;
		$numeric_fields["tmp_table_size"]=true;
		$numeric_fields["innodb_buffer_pool_size"]=true;
		$numeric_fields["innodb_additional_mem_pool_size"]=true;
		$numeric_fields["innodb_log_buffer_size"]=true;
		$numeric_fields["thread_stack"]=true;
		$numeric_fields["sort_buffer_size"]=true;
		$numeric_fields["read_rnd_buffer_size"]=true;
		
		foreach ($numeric_fields as $field=>$DGRULE) {
            if(!isset($this->main_array[$field])){
                $this->main_array[$field]="";
            }
            if(is_null($this->main_array[$field])){continue;}
            $value=trim($this->main_array[$field]);
			if(preg_match("#.*?(,|;)#", $value)){
					$value=str_replace(",", ".", $value);
					$value=str_replace(";", ".", $value);
			}
			if(preg_match("#^\..*?#", $value)){$value="0".$value;}
			$this->main_array[$field]=$value;
		}
		

        foreach ($numeric_fields as $field=>$DGRULE) {
            if(is_null($this->main_array[$field])){continue;}
			$value=trim($this->main_array[$field]);
			if(preg_match("#.*?(,|\.|;)#", $value)){$value=null;}
			if(!is_numeric($value)){$this->main_array[$field]=10;}
		}
	}
	
	
	public function save(){
		$ini=new Bs_IniHandler();
		$ini->_params["MYSQL"]=$this->main_array;
		$sock=new sockets();
		$datas=$ini->toString();
		$sock->SaveConfigFile($datas, "MysqlParameters");
		$sock->getFrameWork("cmd.php?restart-mysql=yes");
		
	}
	
	private function default_character_set(){
		
		if($this->VERSION_MAJOR>4){
				if($this->VERSION_MAJOR<6){
					if($this->VERSION_REV<3){
						return "default-character-set";
					}
				}
			}
			
			if($this->VERSION_MAJOR>4){
				if($this->VERSION_MAJOR>4){
					if($this->VERSION_REV>=3){
						return "character-set-server";
					}
				}
			}
		
	}
	
	private function setssl(){
		$q=new mysql();
		$q->SET_VARIABLES("have_openssl","YES");
		$q->SET_VARIABLES("have_ssl", "YES");
	}
	
	function Mysql_verlow_config(){
		if(count($this->MYSQL_BIN_PARAMS)<3){$this->LoadMysqlParameters();}
		$this->main_array["max_allowed_packet"]=1;
		$this->main_array["key_buffer"]=16;
		$this->main_array["table_cache"]=1;
		if(posix_getuid()==0){
			$GLOBALS["AS_ROOT"]=true;
			echo "Starting......: ".date("H:i:s")." Mysql binary version:$this->mysqlvbin\n";
			if(!is_file("/etc/mysql/debian-start")){@file_put_contents("/etc/mysql/debian-start", "");}
			shell_exec("/bin/chmod 755 /etc/mysql/debian-start");
		}
		
		$conf="[client]\n";
		$conf=$conf ."port=3306\n";
		$conf=$conf ."socket=/var/run/mysqld/mysqld.sock\n";
		$conf=$conf . $this->ssl_client_mode();
		$conf=$conf ."\n";
		$conf=$conf ."[mysqld_safe]\n";
		$conf=$conf ."socket=/var/run/mysqld/mysqld.sock\n";
		$conf=$conf ."nice=0\n";
		$conf=$conf ."\n";
		$conf=$conf ."[mysqld]\n";
		$SpecificConf=$this->GetUsersSpecificConf();
		if($SpecificConf<>null){$conf=$conf.$SpecificConf."\n";}
		$conf=$conf ."user=mysql\n";
		$conf=$conf ."log-error\n";
		$conf=$conf ."pid-file=/var/run/mysqld/mysqld.pid\n";
		$conf=$conf ."socket=/var/run/mysqld/mysqld.sock\n";
		$conf=$conf ."port=3306\n";	
		$conf=$conf .$this->ssl_server_mode();
		if($this->main_array["bind-address"]<>null){
			if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." MySQL Bind Address {$this->main_array["bind-address"]} [".__LINE__."]\n";}
			if($this->main_array["bind-address"]=="all"){$this->main_array["bind-address"]="0.0.0.0";}
			$conf=$conf ."bind-address={$this->main_array["bind-address"]}\n";
		}	

		if($this->main_array["skip_name_resolve"]=="yes"){
			$conf=$conf ."skip-name-resolve\n";
		}				
		
		if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." MySQL Major:$this->VERSION_MAJOR; Minor:$this->VERSION_MAJOR; rev: $this->VERSION_REV\n";}
		
		if($this->main_array["default-character-set"]<>null){$conf=$conf.$this->default_character_set()."=".$this->main_array["default-character-set"];}
		
		if($this->main_array["skip-character-set-client-handshake"]=="yes"){
			$conf=$conf ."skip-character-set-client-handshake\n";
		}
		
		if($this->innodb_file_per_table==1){if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." Mysql innodb_file_per_table added...\n";}$conf=$conf ."innodb_file_per_table\n";}		
		
		if(is_numeric($this->main_array["open_files_limit"])){
			$conf=$conf ."open_files_limit={$this->main_array["open_files_limit"]}\n";
		}
		
		$conf=$conf ."basedir=/usr\n";
		$conf=$conf ."datadir=$this->MYSQL_DATA_DIR\n";
		$conf=$conf ."tmpdir=/tmp\n";
		if(isset($this->MYSQL_BIN_PARAMS["skip-locking"])){
			$conf=$conf. "skip-locking\n";
		}
		if(isset($this->MYSQL_BIN_PARAMS["skip-bdb"])){
			$conf=$conf. "skip-bdb\n";
		}
		if(isset($this->MYSQL_BIN_PARAMS["skip-innodb"])){
			$conf=$conf. "skip-innodb\n";		
		};		
		$conf=$conf. "key_buffer = 12K\n";
		$conf=$conf. "max_allowed_packet = {$this->main_array["max_allowed_packet"]}M\n";
		$conf=$conf. "table_cache = {$this->main_array["table_cache"]}\n";
		$conf=$conf. "sort_buffer_size = 16K\n";
		$conf=$conf. "read_buffer_size = 16K\n";
		$conf=$conf. "read_rnd_buffer_size = 1K\n";
		$conf=$conf. "net_buffer_length = 2K\n";
		$conf=$conf. "thread_stack =16K\n";
		$conf=$conf ."connect_timeout=10\n";
		$conf=$conf ."thread_concurrency=2\n";
		$conf=$conf ."thread_cache_size = 3\n";
		$conf=$conf ."max_connections = 10\n";
		$conf=$conf ."max_connect_errors=1000\n";
		$conf=$conf ."query_cache_limit = 1048576\n";
		$conf=$conf ."query_cache_size = 1048576\n";
		$conf=$conf ."query_cache_type = 0\n";
		$conf=$conf ."default-storage-engine=MyISAM\n";	
		$conf=$conf ."\n";
		$conf=$conf ."[mysqldump]\n";	
		$conf=$conf ."quick\n";	
		$conf=$conf ."max_allowed_packet = 14K\n";	
		$conf=$conf ."[isamchk]\n";
		$conf=$conf ."key_buffer = 14K\n";
		$conf=$conf ."sort_buffer_size = 14K\n";
		$conf=$conf ."\n";
		$conf=$conf ."[myisamchk]\n";
		$conf=$conf ."key_buffer = 14K\n";
		$conf=$conf ."sort_buffer_size = 14K\n";
		$conf=$conf ."\n";
		$conf=$conf ."[mysqlhotcopy]\n";
		$conf=$conf ."interactive-timeout\n";	
		return $conf. "\n".$this->mysql_multi();		
		
	}
	
	
	function Mysql_low_config(){
		if(count($this->MYSQL_BIN_PARAMS)<3){$this->LoadMysqlParameters();}
		$this->main_array["max_allowed_packet"]=1;
		$this->main_array["key_buffer"]=16;
		$this->main_array["table_cache"]=1;
		if(posix_getuid()==0){
			echo "Starting......: ".date("H:i:s")." Mysql binary version:$this->mysqlvbin\n";
		}
		
		
		$conf="[client]\n";
		$conf=$conf ."port=3306\n";
		$conf=$conf ."socket=/var/run/mysqld/mysqld.sock\n";
		$conf=$conf . $this->ssl_client_mode();
		$conf=$conf ."\n";
		$conf=$conf ."[mysqld_safe]\n";
		$conf=$conf ."socket=/var/run/mysqld/mysqld.sock\n";
		$conf=$conf ."nice=0\n";
		$conf=$conf ."\n";
		$conf=$conf ."[mysqld]\n";
		$SpecificConf=$this->GetUsersSpecificConf();
		if($SpecificConf<>null){$conf=$conf.$SpecificConf."\n";}
		$conf=$conf ."user=mysql\n";
		$conf=$conf ."pid-file=/var/run/mysqld/mysqld.pid\n";
		$conf=$conf ."socket=/var/run/mysqld/mysqld.sock\n";
		$conf=$conf ."port=3306\n";
		$conf=$conf .$this->ssl_server_mode();
		if($this->main_array["bind-address"]<>null){
			if($this->main_array["bind-address"]=="all"){$this->main_array["bind-address"]="0.0.0.0";}
			if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." MySQL Bind Address {$this->main_array["bind-address"]} [".__LINE__."]\n";}
			$conf=$conf ."bind-address={$this->main_array["bind-address"]}\n";
		}	
		if($this->main_array["default-character-set"]<>null){$conf=$conf.$this->default_character_set()."=".$this->main_array["default-character-set"];}
		
		
		if($this->main_array["skip-character-set-client-handshake"]=="yes"){
			$conf=$conf ."skip-character-set-client-handshake\n";
		}
		
		if(is_numeric($this->main_array["open_files_limit"])){
			$conf=$conf ."open_files_limit={$this->main_array["open_files_limit"]}\n";
		}		
		
		if($this->main_array["skip_name_resolve"]=="yes"){
			$conf=$conf ."skip-name-resolve\n";
		}				
		
		$conf=$conf ."basedir=/usr\n";
		$conf=$conf ."datadir=$this->MYSQL_DATA_DIR\n";
		$conf=$conf ."log-error\n";
		$conf=$conf ."tmpdir=/tmp\n";
		if(isset($this->MYSQL_BIN_PARAMS["skip-locking"])){
			$conf=$conf. "skip-locking\n";
		}
		if(isset($this->MYSQL_BIN_PARAMS["skip-bdb"])){
			$conf=$conf. "skip-bdb\n";
		}
		
		if($this->main_array["max_allowed_packet"]<2){$this->main_array["max_allowed_packet"]=5;}
		if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." MySQL max_allowed_packet = {$this->main_array["max_allowed_packet"]}M\n";}
		
		if(isset($this->MYSQL_BIN_PARAMS["skip-innodb"])){
			$conf=$conf. "skip-innodb\n";		
		}
		$conf=$conf. "key_buffer = 16K\n";
		$conf=$conf. "max_allowed_packet = {$this->main_array["max_allowed_packet"]}M\n";
		$conf=$conf. "table_cache = {$this->main_array["table_cache"]}\n";
		$conf=$conf. "sort_buffer_size = 16K\n";
		$conf=$conf. "read_buffer_size = 16K\n";
		$conf=$conf. "read_rnd_buffer_size = 1K\n";
		$conf=$conf. "net_buffer_length = 1K\n";
		$conf=$conf. "thread_stack =16K\n";
		$conf=$conf ."wait_timeout=7200\n";
		$conf=$conf ."connect_timeout=10\n";
		$conf=$conf ."max_connect_errors=1000\n";
		$conf=$conf ."query_cache_type=1\n";
		$conf=$conf ."query_cache_limit = 1048576\n";
		$conf=$conf ."query_cache_size = 1048576\n";
		$conf=$conf ."query_cache_type = 1\n";	
		$conf=$conf ."default-storage-engine=MyISAM\n";	
		if($this->innodb_file_per_table==1){if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." MySQL innodb_file_per_table added...\n";}$conf=$conf ."innodb_file_per_table\n";}	
		$conf=$conf ."\n";
		$conf=$conf ."[mysqldump]\n";	
		$conf=$conf ."quick\n";	
		$conf=$conf ."max_allowed_packet = 16M\n";	
		$conf=$conf ."[isamchk]\n";
		$conf=$conf ."key_buffer = 16K\n";
		$conf=$conf ."sort_buffer_size = 16K\n";
		$conf=$conf ."\n";
		$conf=$conf ."[myisamchk]\n";
		$conf=$conf ."key_buffer = 16K\n";
		$conf=$conf ."sort_buffer_size = 16K\n";
		$conf=$conf ."\n";
		$conf=$conf ."[mysqlhotcopy]\n";
		$conf=$conf ."interactive-timeout\n";	
		return $conf. "\n".$this->mysql_multi();
		
	}
	
	private function GetUsersSpecificConf(){
		$save=false;
			
		
		
		if(!$GLOBALS["AS_ROOT"]){return;}
		if(!is_file("/etc/artica-postfix/my.cnf.mysqld")){
			if($GLOBALS["VERBOSE"]){"mysqld: /etc/artica-postfix/my.cnf.mysqld no such file\n";}
		}
		$datas=@file_get_contents("/etc/artica-postfix/my.cnf.mysqld");
		$tbl=explode("\n",$datas);


        foreach ($tbl as $index=>$value) {
			if(substr($value, 0,1)=="#"){continue;}
			if(preg_match("#(.+?)=(.+)#", $value,$re)){
				$key=trim(strtolower($re[1]));
				if($this->TestsParametersToDelete($key)){
					echo "Starting......: ".date("H:i:s")." Mysql \"$key\" is depreciated\n";
					$tbl[$index]="#$key={$re[2]} - depreciated";
					$save=true;
					continue;
				}
				
				echo "Starting......: ".date("H:i:s")." Mysql checking $key done\n";
			}
			
		}
		
		if($save){@file_put_contents("/etc/artica-postfix/my.cnf.mysqld", @implode("\n", $tbl));}
		
		return @implode("\n", $tbl);
		
	}
	
	private function TestsParametersToDelete($param){
		if($this->EnableZarafaTuning==1){
			$array["innodb_buffer_pool_size"]=true;
			$array["query_cache_size"]=true;
			$array["innodb_log_file_size"]=true;
			$array["innodb_log_buffer_size"]=true;
			$array["max_allowed_packet"]=true;
			$array["max_connections"]=true;
		}
		
		
		
		if($this->VERSION_MAJOR>4){
			if($this->VERSION_MAJOR>4){
				if($this->VERSION_REV>=3){
						$array["master-connect-retry"]=true;
					}
				}
			}

		if(!is_array($array)){return false;}
		if(isset($array[$param])){return true;}
		
	}
	
	
	private function ssl_client_mode(){
		if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
		if(!$GLOBALS["AS_ROOT"]){return;}
		$sock=new sockets();
		$mysqlSSL=$sock->GET_INFO("mysqlSSL");
		if(!is_numeric($mysqlSSL)){$mysqlSSL=0;}
		if($mysqlSSL==0){return null;}		
		$files[]="/etc/ssl/certs/mysql/cacert.pem";
		$files[]="/etc/ssl/certs/mysql-client/client-cert.pem";
		$files[]="/etc/ssl/certs/mysql-client/client-key.pem";		
		$continue=true;
		foreach ($files as $filename){
            if(!is_file($filename)){echo "$filename no such file\n";$continue=false;}}
		if(!$continue){return null;}
		if(is_file("/etc/ssl/certs/mysql-client/cacert.pem")){$cacert="/etc/ssl/certs/mysql-client/cacert.pem";}else{$cacert="/etc/ssl/certs/mysql/cacert.pem";}
		$f[]="ssl-ca=$cacert";
		$f[]="ssl-cert=/etc/ssl/certs/mysql-client/client-cert.pem";
		$f[]="ssl-key=/etc/ssl/certs/mysql-client/client-key.pem";
		$f[]="";
		return @implode("\n", $f);		
	}
	

	
	private function ssl_server_mode(){
		if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
		if(!$GLOBALS["AS_ROOT"]){return;}
		$sock=new sockets();
		$mysqlSSL=$sock->GET_INFO("mysqlSSL");
		if(!is_numeric($mysqlSSL)){$mysqlSSL=0;}
		if($mysqlSSL==0){return null;}
		$files[]="$this->MYSQL_DATA_DIR/mysql-ca-cert.pem";
		$files[]="$this->MYSQL_DATA_DIR/mysql-server-cert.pem";
		$files[]="$this->MYSQL_DATA_DIR/mysql-server-key.pem";
		$continue=true;
		foreach ($files as $filename){
            if(!is_file($filename)){echo "$filename no such file\n";$continue=false;}}
		if(!$continue){
			shell_exec("/usr/share/artica-postfix/bin/artica-install --mysql-certificate");
			reset($files);
			$continue=true;
            foreach ($files as $filename){if(!is_file($filename)){echo "$filename no such file\n";$continue=false;}}
		}
		if(!$continue){return null;}
		$f[]="ssl";
		$f[]="ssl-ca=$this->MYSQL_DATA_DIR/mysql-ca-cert.pem";
		$f[]="ssl-cert=$this->MYSQL_DATA_DIR/mysql-server-cert.pem";
		$f[]="ssl-key=$this->MYSQL_DATA_DIR/mysql-server-key.pem";
		$f[]=""; 	
		$this->setssl();	
		return @implode("\n", $f);
		
	}
	
	private function SetUnits($value){
		$unit="M";
		if(strpos($value, ".")>0){
			$value=$value*1000;
			$unit="K";
			return "{$value}{$unit}";
		}
		return "{$value}{$unit}";
	}
	
	function BuildConf(){
		if($GLOBALS["VERBOSE"]){echo "mysqld: DEBUG: BuildConf()\n";}
		if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
		$SpecificConf=$this->GetUsersSpecificConf();
		$mysqld_variables_cmdline=$this->mysqld_variables_cmdline();
		if(count($this->MYSQL_BIN_PARAMS)<3){$this->LoadMysqlParameters();}

		$conf="[client]\n";
		$conf=$conf ."port=3306\n";
		$conf=$conf ."socket=/var/run/mysqld/mysqld.sock\n";
		$conf=$conf . $this->ssl_client_mode();
		$conf=$conf ."\n";
		$conf=$conf ."[mysqld_safe]\n";
		$conf=$conf ."socket=/var/run/mysqld/mysqld.sock\n";
		if($this->main_array["default-character-set"]<>null){
			$conf=$conf ."default-character-set={$this->main_array["default-character-set"]}\n";
		}		
		$conf=$conf ."nice=0\n";
		$conf=$conf ."\n";

		$conf=$conf ."[mysqld]\n";
		if($SpecificConf<>null){
			$conf=$conf.$SpecificConf."\n";
		}
		
		if(!isset($this->main_array["default-character-set"])){$this->main_array["default-character-set"]=null;}
		if(!isset($this->main_array["skip-character-set-client-handshake"])){$this->main_array["skip-character-set-client-handshake"]=null;}
		
		if($this->main_array["default-character-set"]<>null){$mysqld[$this->default_character_set()]=$this->main_array["default-character-set"];}	

		if($this->main_array["skip-character-set-client-handshake"]=="yes"){
			$mysqld["skip-character-set-client-handshake"]="";
		}
		
		if(isset($mysqld_variables_cmdline["general_log"])){
				$mysqld["general_log"]="Yes";
				$mysqld["general_log_file"]="$this->MYSQL_DATA_DIR/general_log.log";
		}else{
			if($GLOBALS["VERBOSE"]){echo "mysqld: Building: general_log token does not exists in ". count($mysqld_variables_cmdline)." items in array\n";}
		}		
		
		$ssl=$this->ssl_server_mode();
		if($ssl<>null){
			$mysqld["ssl"]=null;
			$mysqld["ssl-ca"]="$this->MYSQL_DATA_DIR/mysql-ca-cert.pem";
			$mysqld["ssl-cert"]="$this->MYSQL_DATA_DIR/mysql-server-cert.pem";
			$mysqld["ssl-key"]="$this->MYSQL_DATA_DIR/mysql-server-key.pem";	
		}
		
		$mysqld["user"]="mysql";
		$mysqld["pid-file"]="/var/run/mysqld/mysqld.pid";
		$mysqld["socket"]="/var/run/mysqld/mysqld.sock";
		$mysqld["port"]="3306";
		$mysqld["basedir"]="/usr";
		$mysqld["datadir"]="$this->MYSQL_DATA_DIR";
		$mysqld["tmpdir"]="/tmp";
		
		if($this->main_array["skip_external_locking"]=="yes"){
			$mysqld["skip-external-locking"]="";
		}
		
		if($this->main_array["skip_name_resolve"]=="yes"){
			$mysqld["skip-name-resolve"]="";
		}		
		
		
		
		if($this->main_array["bind-address"]<>null){
			if($this->main_array["bind-address"]=="all"){$this->main_array["bind-address"]="0.0.0.0";}
			if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." MySQL Bind Address {$this->main_array["bind-address"]} [".__LINE__."]\n";}
			$mysqld["bind-address"]=$this->main_array["bind-address"];
		}
		
		if($this->main_array["key_buffer"]<>null){
			$mysqld["key_buffer"]="{$this->main_array["key_buffer"]}M";
		}

		$mysqld["expire_logs_days"]="10";
		$mysqld["max_binlog_size"]="100M";
		$mysqld["back_log"]="75";
		if($this->main_array["read_rnd_buffer_size"]<>null){
			$mysqld["read_rnd_buffer_size"]=$this->SetUnits("{$this->main_array["read_rnd_buffer_size"]}");
		}

		if($this->main_array["sort_buffer"]<>null){
			$mysqld["sort_buffer"]=$this->SetUnits("{$this->main_array["sort_buffer"]}");
		}	
		if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." Mysql binary version :$this->mysqlvbin\n";}
		if($this->mysqlvbin>0){
		if($this->mysqlvbin<501030){
			if($this->main_array["table_cache"]<>null){
				$mysqld["table_cache"]="{$this->main_array["table_cache"]}";
		}}}	
		
		if($this->main_array["tmp_table_size"]<>null){
			$mysqld["tmp_table_size"]=$this->SetUnits($this->main_array["tmp_table_size"]);
		}
		
		if(is_numeric($this->main_array["open_files_limit"])){
			$mysqld["open_files_limit"]="{$this->main_array["open_files_limit"]}";
		}

		
		if($this->main_array["max_allowed_packet"]<>null){
			$mysqld["max_allowed_packet"]=$this->SetUnits($this->main_array["max_allowed_packet"]);
		}	
		
		if($this->main_array["max_connections"]<>null){
			$mysqld["max_connections"]="{$this->main_array["max_connections"]}";
		}

		if($this->main_array["myisam_sort_buffer_size"]<>null){
			$mysqld["myisam_sort_buffer_size"]=$this->SetUnits($this->main_array["myisam_sort_buffer_size"]);
		}		
		
		if($this->main_array["sort_buffer_size"]<>null){
			$mysqld["sort_buffer_size"]=$this->SetUnits($this->main_array["sort_buffer_size"]);
		}	

		
		if($this->main_array["innodb_additional_mem_pool_size"]<>null){
			$mysqld["innodb_additional_mem_pool_size"]=$this->SetUnits($this->main_array["innodb_additional_mem_pool_size"]);
		}			
		
		
		if(is_numeric($this->main_array["innodb_log_file_size"])){
			$mysqld["innodb_log_file_size"]=$this->SetUnits($this->main_array["innodb_log_file_size"]);
		}
		if(is_numeric($this->main_array["innodb_log_buffer_size"])){
			$mysqld["innodb_log_buffer_size"]=$this->SetUnits($this->main_array["innodb_log_buffer_size"]);
		}		
		if(is_numeric($this->main_array["innodb_lock_wait_timeout"])){
			$mysqld["innodb_lock_wait_timeout"]="{$this->main_array["innodb_lock_wait_timeout"]}";
		}			
		if(is_numeric($this->main_array["innodb_buffer_pool_size"])){
			
			$mysqld["innodb_buffer_pool_size"]=$this->SetUnits("{$this->main_array["innodb_buffer_pool_size"]}");
		}				
				
		if($this->main_array["net_buffer_length"]<>null){
			$mysqld["net_buffer_length"]=$this->SetUnits($this->main_array["net_buffer_length"]);
		}	

		if($this->main_array["join_buffer_size"]<>null){
			$mysqld["join_buffer_size"]=$this->SetUnits($this->main_array["join_buffer_size"]);
		}			
		
		if($this->main_array["read_buffer_size"]<>null){
			$mysqld["read_buffer_size"]=$this->SetUnits($this->main_array["read_buffer_size"]);
		}	
		if($this->main_array["thread_cache_size"]<>null){
			$mysqld["thread_cache_size"]=$this->SetUnits($this->main_array["thread_cache_size"]);
		}	
		if($this->main_array["query_cache_limit"]<>null){
			$mysqld["query_cache_limit"]=$this->SetUnits($this->main_array["query_cache_limit"]);
		}
		if(is_numeric($this->main_array["max_heap_table_size"])){
			$mysqld["max_heap_table_size"]=$this->SetUnits($this->main_array["max_heap_table_size"]);
		}

		if(is_numeric($this->main_array["thread_stack"])){
			$mysqld["thread_stack"]=$this->SetUnits($this->main_array["thread_stack"]);
		}			

		
		
		if($this->main_array["key_buffer_size"]<>null){
			$mysqld["key_buffer_size"]=$this->SetUnits($this->main_array["key_buffer_size"]);
		}		
		if($this->main_array["query_cache_size"]<>null){
			$mysqld["query_cache_size"]=$this->SetUnits($this->main_array["query_cache_size"]);
		}	
		if($this->mysqlvbin>0){
			if($this->mysqlvbin>501030){
				if($this->main_array["table_cache"]<>null){
				if($this->main_array["table_cache"]==0){$this->main_array["table_cache"]=1;}
				$mysqld["table_open_cache"]="{$this->main_array["table_cache"]}";
			}}
		}			

		$mysqld["wait_timeout"]="7200";
		$mysqld["connect_timeout"]="10";
		$mysqld["max_connect_errors"]="1000";
		$mysqld["query_cache_type"]="1";
		$mysqld["default-storage-engine"]="MyISAM";
		$mysqld["log-error"]="";
		if(is_array($SpecificConf)){
		if(count($SpecificConf)>0){
            foreach ($SpecificConf as $key=>$value){
				if($GLOBALS["VERBOSE"]){echo "mysqld:Personal settings $key = $value\n";}
				$mysqld[$key]=$value;
				}
			}
		}
		if($this->EnableZarafaTuning==1){
			$ZarafTuningParameters=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZarafaTuningParameters")));
			$mysqld["innodb_buffer_pool_size"]=$this->SetUnits($ZarafTuningParameters["zarafa_innodb_buffer_pool_size"]);
			$mysqld["query_cache_size"]=$this->SetUnits($ZarafTuningParameters["zarafa_query_cache_size"]);
			$mysqld["innodb_log_file_size"]=$this->SetUnits($ZarafTuningParameters["zarafa_innodb_log_file_size"]);
			$mysqld["innodb_log_buffer_size"]=$this->SetUnits($ZarafTuningParameters["zarafa_innodb_log_buffer_size"]);
			$mysqld["max_allowed_packet"]=$this->SetUnits($ZarafTuningParameters["zarafa_max_allowed_packet"]);
			$mysqld["max_connections"]=$ZarafTuningParameters["zarafa_max_connections"];
			
		}
		
		if($this->innodb_file_per_table==1){if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." Mysql innodb_file_per_table added...\n";}$conf=$conf ."innodb_file_per_table\n";}		

		if($this->ZARAFA_INSTALLED){
			///$mysqld["innodb_force_recovery"]= 4;
			$mysqld["innodb_data_home_dir"]="./";
			
		}
		
		
		
		unset($mysqld["version-string"]);

        foreach ($mysqld as $key=>$value){
			if($GLOBALS["VERBOSE"]){echo "mysqld: Building: $key = $value\n";}
			if(strlen(trim($value))>0){
				$conf=$conf ."$key=$value\n";
			}else{
				$conf=$conf ."$key\n";
			}
		}
		
		
		$conf=$conf ."[mysqldump]\n";
		$conf=$conf ."quick\n";
		$conf=$conf ."quote-names\n";
		$conf=$conf ."max_allowed_packet=256M\n";		

		$conf=$conf ."[mysql]\n";
		if($this->main_array["default-character-set"]<>null){
			$conf=$conf ."default-character-set={$this->main_array["default-character-set"]}\n";
		}		
		$conf=$conf ."\n" . $this->mysql_multi();
		
		return $conf;
	}
	
	function mysqld_variables_cmdline(){
		$q=new mysqlserver_multi(0);
		return $q->mysqld_variables_cmdline();
	}
	
	
	
	
	function mysql_multi(){
		if(!$GLOBALS["AS_ROOT"]){return;}
		$lastconf="/etc/artica-postfix/MysqlMulti.conf";
		if(!$GLOBALS["MULTI"]){if(is_file($lastconf)){echo "Starting......: ".date("H:i:s")." [Note] Mysql return cached content\n";return @file_get_contents($lastconf);}}
		$unix=new unix();
		
		$chown=$unix->find_program("chown");
		$mysqld_safe=$unix->find_program("mysqld_safe");
		$mysqladmin=$unix->find_program("mysqladmin");
		$mysql_install_db=$unix->find_program("mysql_install_db");
		$mysqld=$unix->find_program("mysqld");
		$mysqlbin=$unix->find_program("mysql");
		$mysqld_multi=$unix->find_program("mysqld_multi");
		if(!is_file($mysql_install_db)){echo "Starting......: ".date("H:i:s")." Mysql Warning mysql_install_db no such binary !!!\n";return;}
		$mysqld_variables_cmdline=$this->mysqld_variables_cmdline();
		$lc_messages_dir=true;
		if(!isset($mysqld_variables_cmdline["lc-messages-dir"])){$lc_messages_dir=false;}

		
		$q=new mysql();
		$sql="SELECT * FROM mysqlmulti WHERE enabled=1 ORDER BY ID DESC";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." Mysql checking fatal error $q->mysql_error\n";return;}
		$f[]="[mysqld_multi]";
		$f[]="mysqld = $mysqld";
		$f[]="mysqladmin = $mysqladmin";
		$f[]="user = mysqld_multi";
		$f[]="password = mysqld_multi";
		$f[]="";
		
		
		while ($ligne = mysqli_fetch_assoc($results)) {
			$ID=$ligne["ID"];
			if($ligne["Dir"]==null){$ligne["Dir"]="$this->MYSQL_DATA_DIR-$ID";}
			if($lc_messages_dir){$lc_messages_dir_cmd=" --lc-messages-dir=";}
			$mysql_install_db_cmds="$mysql_install_db --user=mysql --datadir=\"{$ligne["Dir"]}\"$lc_messages_dir_cmd";
			if($ID==$GLOBALS["SHOWLOGONLYFOR"]){echo "Starting......: ".date("H:i:s")." [Note] Mysql building configuration for instance Number $ID..\n";}
			if(!is_numeric($ligne["usesocket"])){$ligne["usesocket"]=0;}
			
			
			
			$f[]="[mysqld$ID]";
			//$f[]="mysqld =".basename($mysqld);
			//$f[]="ledir=".dirname($mysqld)."";
			//$f[]="basedir=/usr";
			if(isset($mysqld_variables_cmdline["general_log"])){
				$f[]="general_log = Yes";
				$f[]="general_log_file = {$ligne["Dir"]}/general_log.log";
			}
			$f[]="socket=/var/run/mysqld/mysqld$ID.sock";
			if($lc_messages_dir){$f[]="lc-messages-dir = /usr/share/mysql/";}
			if($ligne["usesocket"]==1){
				if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." MySQL Bind Address {$ligne["listen_addr"]} [".__LINE__."]\n";}
				$f[]="bind-address={$ligne["listen_addr"]}";
			
			}
			if($ligne["usesocket"]==1){$f[]="port={$ligne["listen_port"]}";}
			$f[]="pid-file=/var/run/mysqld/mysqld$ID.pid";
			$f[]="tmpdir= /tmp";
			$f[]="datadir={$ligne["Dir"]}";
			if($ligne["usesocket"]==0){$f[]="skip-networking";}
			$mysqlClass=new mysqlserver_multi($ID);
			$options=$mysqlClass->BuildConfig();
			if($options<>null){$f[]=$options;}
			$f[]="";
			
			
			
			if(!is_dir($ligne["Dir"])){
				@mkdir($ligne["Dir"],0755,true);
				shell_exec("$chown mysql:mysql {$ligne["Dir"]} >/dev/null 2>&1");
				echo "Starting......: ".date("H:i:s")." [Note] Mysql Installing databases in {$ligne["Dir"]}\n";
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Mysql $mysql_install_db_cmds\n";}
				shell_exec($mysql_install_db_cmds);	
				shell_exec("$mysqld_multi start $ID");	
				sleep(2);
				shell_exec("$mysqlbin -S /var/run/mysqld/mysqld$ID.sock -u root \"create user 'mysqld_multi'@'127.0.0.1' identified by 'mysqld_multi'\" >/dev/null 2>&1");
				shell_exec("$mysqlbin -S /var/run/mysqld/mysqld$ID.sock -u root \"create user 'mysqld_multi'@'localhost' identified by 'mysqld_multi'\" >/dev/null 2>&1");
				shell_exec("$mysqlbin -S /var/run/mysqld/mysqld$ID.sock -u root \"grant shutdown on *.* to mysqld_multi\" >/dev/null 2>&1");
											
			}
			
			if(!is_file("{$ligne["Dir"]}/mysql/user.MYI")){
				echo "Starting......: ".date("H:i:s")." [Note] Mysql Installing databases in {$ligne["Dir"]}\n";
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Mysql $mysql_install_db_cmds\n";}
				shell_exec($mysql_install_db_cmds);
				shell_exec("$mysqld_multi start $ID");
				sleep(2);
				shell_exec("$mysqlbin -S /var/run/mysqld/mysqld$ID.sock -u root \"create user 'mysqld_multi'@'127.0.0.1' identified by 'mysqld_multi'\" >/dev/null 2>&1");
				shell_exec("$mysqlbin -S /var/run/mysqld/mysqld$ID.sock -u root \"create user 'mysqld_multi'@'localhost' identified by 'mysqld_multi'\" >/dev/null 2>&1");
				shell_exec("$mysqlbin -S /var/run/mysqld/mysqld$ID.sock -u root \"grant shutdown on *.* to mysqld_multi\" >/dev/null 2>&1");
			}					
			
		}	

		  			
		echo "Starting......: ".date("H:i:s")." [Note] Mysql building configuration done..\n";	
		@file_put_contents("/etc/mysql-multi.cnf", @implode("\n", $f));
		@file_put_contents($lastconf, @implode("\n", $f));
		return @implode("\n", $f);
		
	}
	
	
}
?>