<?php
include_once(dirname(__FILE__). '/class.ini.inc');
include_once(dirname(__FILE__). '/class.sockets.inc');
include_once(dirname(__FILE__). '/class.users.menus.inc');
include_once(dirname(__FILE__). '/class.mysql.inc');

class mysqlserver_multi{
	
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
	var $instance_id=0;
	var $PersoConfText=null;
	var $TABLE_ARRAY=array();
	var $mysqlSSL=0;
	var $usesocket=0;
	var $WorkingDirectory=null;
	var $mysql_admin=null;
	var $mysql_password=null;	
	var $PidPath=null;
	
	function __construct($instance_id=0){
		
		if($instance_id>0){
			$this->instance_id=$instance_id;
			$this->load_params();
		}
		
		
	}
	
	public function setssl(){
		if($this->mysqlSSL==0){return;}
		$q=new mysql_multi($this->instance_id);
		$q->SET_VARIABLES("have_openssl","YES");
		$q->SET_VARIABLES("have_ssl", "YES");
	}	
	
	private function load_params(){
		$users=new usersMenus();
		$this->mysql_version_string=$users->mysqld_version;
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM mysqlmulti WHERE ID='$this->instance_id'","artica_backup"));
		$this->usesocket=$ligne["usesocket"];
		$this->TABLE_ARRAY=unserialize(base64_decode($ligne["params"]));
		$this->main_array=$this->TABLE_ARRAY["SERVER_SETTINGS"];
		$this->PersoConfText=$this->TABLE_ARRAY["PERSOTEXT"];
		$this->mysqlSSL=$this->TABLE_ARRAY["mysqlSSL"];
		$this->mysql_admin=$this->TABLE_ARRAY["mysql_admin"];
		$this->mysql_password=$this->TABLE_ARRAY["mysql_password"];		
		if(!is_numeric($this->mysqlSSL)){$this->mysqlSSL=0;}
		if(!is_numeric($this->usesocket)){$this->usesocket=0;}
		$this->PidPath="/var/run/mysqld/mysqld$this->instance_id.pid";
		$this->WorkingDirectory=$ligne["Dir"];
		if($GLOBALS["VERBOSE"]){
			echo "Loading settings from $this->instance_id success\n";
			echo "usesocket...............: $this->usesocket\n";
			echo "mysql_admin.............: $this->mysql_admin\n";
			echo "mysql_password..........: $this->mysql_password\n";
			echo "mysqlSSL................: $this->mysqlSSL\n";
			echo "WorkingDirectory........: $this->WorkingDirectory\n";
			
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

		$this->BuildDefault();
		
	}
	
	public function SHOW_VARIABLES(){
		$q=new mysql_multi($this->instance_id);
		return $q->SHOW_VARIABLES();
		}	
	
	function save(){
		$this->TABLE_ARRAY["SERVER_SETTINGS"]=$this->main_array;
		$this->TABLE_ARRAY["PERSOTEXT"]=$this->PersoConfText;
		$this->TABLE_ARRAY["mysqlSSL"]=$this->mysqlSSL;
		$this->TABLE_ARRAY["mysql_admin"]=$this->mysql_admin;
		$this->TABLE_ARRAY["mysql_password"]=$this->mysql_password;
	
		$NEW_ARRAY=base64_encode(serialize($this->TABLE_ARRAY));
		$q=new mysql();
		$q->QUERY_SQL("UPDATE mysqlmulti SET params='$NEW_ARRAY' WHERE ID='$this->instance_id'","artica_backup");
	}
	
	private function BuildDefault(){
		if($this->main_array["skip_external_locking"]==null){$this->main_array["skip_external_locking"]='yes';}
		if($this->main_array["skip_name_resolve"]==null){$this->main_array["skip_name_resolve"]='no';}
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
	}	
	
	function mysqld_variables_cmdline(){
		if(isset($GLOBALS["MYSQL_VARIABLES_ROOT"])){return $GLOBALS["MYSQL_VARIABLES_ROOT"];}
		$unix=new unix();
		$mysqld=$unix->find_program("mysqld");
		exec("$mysqld --help --verbose 2>&1",$results);
		$datas=@implode("\n", $results);
		if(preg_match("#Variables \(--(.+)#is", $datas,$re)){$datas=$re[1];}
		$tb=explode("\n",$datas);
        foreach ($tb as  $key=>$value) {
			if(preg_match("#^([a-z\-\_]+)\s+(.+)#", $value,$re)){
				$key=trim($re[1]);
				$value=trim($re[2]);
				if($value=="(No default value)"){$value=null;}
				$GLOBALS["MYSQL_VARIABLES_ROOT"][$key]=$value;
			}
			
		}
		if($GLOBALS["VERBOSE"]){echo "mysqld: DEBUG: mysqld_variables_cmdline() return ". count($GLOBALS["MYSQL_VARIABLES_ROOT"])." tokens\n";}
		return $GLOBALS["MYSQL_VARIABLES_ROOT"];
		
	}	
	

	private function ssl_server_mode(){
		if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
		if(!$GLOBALS["AS_ROOT"]){return;}
		$mysqlSSL=$this->mysqlSSL;
		if(!is_numeric($mysqlSSL)){$mysqlSSL=0;}
		if($mysqlSSL==0){return null;}
		$files[]="$this->WorkingDirectory/mysql-ca-cert.pem";
		$files[]="$this->WorkingDirectory/mysql-server-cert.pem";
		$files[]="$this->WorkingDirectory/mysql-server-key.pem";
		$continue=true;
        foreach ($files as $filename) {if(!is_file($filename)){echo "$filename no such file\n";$continue=false;}}
		if(!$continue){
			shell_exec("/usr/share/artica-postfix/bin/artica-install --mysql-certificate $this->instance_id");
			reset($files);
			$continue=true;
            foreach ($files as $filename) {if(!is_file($filename)){echo "$filename no such file\n";$continue=false;}}
		}
		if(!$continue){return null;}
		$f[]="ssl";
		$f[]="ssl-ca=$this->WorkingDirectory/mysql-ca-cert.pem";
		$f[]="ssl-cert=$this->WorkingDirectory/mysql-server-cert.pem";
		$f[]="ssl-key=$this->WorkingDirectory/mysql-server-key.pem";
		$f[]=""; 	
		return @implode("\n", $f);
		
	}	
	
	public function BuildConfig(){
		$mysqld_variables_cmdline=$this->mysqld_variables_cmdline();
		if($this->main_array["skip_external_locking"]=="yes"){$mysqld["skip-external-locking"]="";}
		if($this->main_array["skip_name_resolve"]=="yes"){$mysqld["skip-name-resolve"]=null;}		
		if($this->main_array["key_buffer"]<>null){$mysqld["key_buffer"]="{$this->main_array["key_buffer"]}M";}
		
		$mysqld["expire_logs_days"]="10";
		$mysqld["max_binlog_size"]="100M";
		$mysqld["back_log"]="75";
		if($this->main_array["read_rnd_buffer_size"]<>null){$mysqld["read_rnd_buffer_size"]="{$this->main_array["read_rnd_buffer_size"]}M";}
		if($this->main_array["sort_buffer"]<>null){$mysqld["sort_buffer"]="{$this->main_array["sort_buffer"]}M";}	
		if($this->main_array["table_cache"]<>null){$mysqld["table_cache"]="{$this->main_array["table_cache"]}";}
		if($this->main_array["tmp_table_size"]<>null){$mysqld["tmp_table_size"]="{$this->main_array["tmp_table_size"]}M";}
		if($this->main_array["innodb_additional_mem_pool_size"]<>null){$mysqld["innodb_additional_mem_pool_size"]="{$this->main_array["innodb_additional_mem_pool_size"]}M";}	
		if($this->main_array["max_allowed_packet"]<>null){$mysqld["max_allowed_packet"]="{$this->main_array["max_allowed_packet"]}M";}	
		if($this->main_array["max_connections"]<>null){$mysqld["max_connections"]="{$this->main_array["max_connections"]}";}
		if($this->main_array["myisam_sort_buffer_size"]<>null){$mysqld["myisam_sort_buffer_size"]="{$this->main_array["myisam_sort_buffer_size"]}M";}		
		if($this->main_array["sort_buffer_size"]<>null){$mysqld["sort_buffer_size"]="{$this->main_array["sort_buffer_size"]}M";}				
		if($this->main_array["net_buffer_length"]<>null){$mysqld["net_buffer_length"]="{$this->main_array["net_buffer_length"]}M";}	
		if($this->main_array["join_buffer_size"]<>null){$mysqld["join_buffer_size"]="{$this->main_array["join_buffer_size"]}M";}			
		if($this->main_array["read_buffer_size"]<>null){$mysqld["read_buffer_size"]="{$this->main_array["read_buffer_size"]}M";}	
		if($this->main_array["thread_cache_size"]<>null){$mysqld["thread_cache_size"]="{$this->main_array["thread_cache_size"]}";}	
		if($this->main_array["query_cache_limit"]<>null){$mysqld["query_cache_limit"]="{$this->main_array["query_cache_limit"]}M";}		
		if($this->main_array["key_buffer_size"]<>null){$mysqld["key_buffer_size"]="{$this->main_array["key_buffer_size"]}M";}		
		if($this->main_array["query_cache_size"]<>null){$mysqld["query_cache_size"]="{$this->main_array["query_cache_size"]}M";}
		if($this->main_array["thread_stack"]<>null){$mysqld["thread_stack"]="{$this->main_array["query_cache_size"]}M";}
		if(is_numeric($this->main_array["open_files_limit"])){$mysqld["open_files_limit"]="{$this->main_array["open_files_limit"]}";}

		
		
		$mysqld["log_error"]=$this->WorkingDirectory."/error.log";
		
		$this->PersoConfText=$this->ssl_server_mode()."\n".$this->PersoConfText."\n";
		$tb=explode("\n", $this->PersoConfText);
        foreach ($tb as $value) {
			$value=trim($value);
			if($value==null){continue;}
			if(substr($value, 0,1)=="#"){continue;}
			if(preg_match("#^([a-z\-\_]+).*?=(.*)#", strtolower($value),$re)){$mysqld[trim($re[1])]=trim($re[2]);continue;}
			if(preg_match("#^([a-z\-\_]+)#", $value,$re)){$mysqld[trim($re[1])]=null;}
			
		}
		

		if(count($mysqld)==0){
			echo "Starting......: ".date("H:i:s")." [Note] Mysql no additional setting\n";
			return null;}
        foreach ($mysqld as $key=>$value) {
			if($key=="ssl"){$f[]="$key";continue;}
			if($key=="ssl-ca"){$f[]="$key = $value";continue;}
			if($key=="ssl-cert"){$f[]="$key = $value";continue;}
			if($key=="ssl-key"){$f[]="$key = $value";continue;}
			if($key=="log_error"){$f[]="$key = $value";continue;}
			
			if(!isset($mysqld_variables_cmdline[$key])){
				if($this->instance_id==$GLOBALS["SHOWLOGONLYFOR"]){echo "Starting......: ".date("H:i:s")." [Note] Mysql skipping token `$key`\n";}
				continue;
			}
			if($value==null){$f[]="$key";continue;}
			if(preg_match("#(.*?)M#", $value,$ri)){
				$value=$this->SetUnits($value);
			}
			$f[]="$key = $value";
		}
		
		if($this->instance_id==$GLOBALS["SHOWLOGONLYFOR"]){echo "Starting......: ".date("H:i:s")." [Note] Mysql building ". count($f)." additional settings\n";}
		return @implode("\n", $f)."\n";
		
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
	
	
}
?>