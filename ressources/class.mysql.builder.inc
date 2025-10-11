<?php
include_once(dirname(__FILE__).'/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/class.mysql.xapian.builder.inc');
include_once(dirname(__FILE__).'/class.sqlite.inc');
include_once(dirname(__FILE__).'/class.sockets.inc');
if(is_file(dirname(__FILE__).'/class.tasks.inc')){include_once(dirname(__FILE__).'/class.tasks.inc');}


class mysql_builder{
	var $ClassSQL;
	var $ok=false;
	var $mysql_error;
	var $UseMysql=true;
	
	function __construct(){
		$this->ClassSQL=new mysql();
		$this->UseMysql=$this->ClassSQL->UseMysql;
		$sock=new sockets();
		$DisableMySqlTemp=$sock->GET_INFO("DisableMySqlTemp");
		if(!is_numeric($DisableMySqlTemp)){$DisableMySqlTemp=0;}
		if($DisableMySqlTemp==1){
			$this->mysql_error="Mysql temporary in maintenance";
			writelogs("Mysql temporary in maintenance",__CLASS__,__FUNCTION__,__FILE__,__LINE__);exit();
		}
	}
	
	private function TABLE_EXISTS($table,$database){
		return $this->ClassSQL->TABLE_EXISTS($table,$database);
	}
	private function DATABASE_EXISTS($database){
		return $this->ClassSQL->DATABASE_EXISTS($database);
	}
	
	private function FIELD_EXISTS($table,$field,$database){
		return $this->ClassSQL->FIELD_EXISTS($table,$field,$database);
	}
	
	private function QUERY_SQL($sql,$database=null){
		$this->ClassSQL->QUERY_SQL($sql,$database);
		$this->ok=$this->ClassSQL->ok;
		$this->mysql_error=$this->ClassSQL->mysql_error;
	}
	
	private function FIELD_TYPE($table,$field,$database){
		return $this->ClassSQL->FIELD_TYPE($table,$field,$database);
	}
	private function COUNT_ROWS($table,$database){
		return $this->ClassSQL->COUNT_ROWS($table,$database);
	}	
	private FUNCTION INDEX_EXISTS($table,$index,$database){
		return $this->ClassSQL->INDEX_EXISTS($table,$index,$database);
	}
	
	private FUNCTION CREATE_DATABASE($database){
		
		return $this->ClassSQL->CREATE_DATABASE($database);
	}
	
	
	public function Check_reports_table(){}

	
	private function FIELD_CHECKS($table,$field,$database,$type,$size,$key=false,$unique=false){
		$size_text=null;
		if(!$this->ClassSQL->FIELD_EXISTS($table,$field,$database)){
			if($key){$key_text=",ADD INDEX ( `$field` )";}
			if($unique){$key_text=",ADD UNIQUE KEY `$field` (`$field`)";}
			if($size>0){$size_text="( $size )";}
			$sql="ALTER TABLE `$table` ADD `$field` $type$size_text NOT NULL$key_text";
			$this->QUERY_SQL($sql,$database);
			if(!$this->ok){
				writelogs("Fatal: $this->mysql_error Adding field $field in $table/$database $type($size) $sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}	
			
		}else{
			$Filedtype=$this->FIELD_TYPE($table,$field,$database);
			if($size>0){$size_text="($size)";}
			$MyType=strtolower("$type$size_text");
			if($MyType<>$Filedtype){
				$sql="ALTER TABLE `$table` CHANGE `$field` `$field` $type$size_text";
				$this->QUERY_SQL($sql,$database);
			}
			
		}
	}
	
	public function check_mysql_dbtables(){}

private function ToSyslog($text,$error=false){
	$text=str_replace("\n", " ", $text);


	if(function_exists("debug_backtrace")){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
			$function="{$trace[1]["function"]}()";
			$line="{$trace[1]["line"]}";
		}
	}

	$text="{$function}[$line]:$text";
	if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
	if(function_exists("openlog")){openlog("mysql-global", LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}

private  function file_time_min($path){
	if(!is_dir($path)){
		if(!is_file($path)){return 100000;}
	}
	$last_modified = filemtime($path);
	$data1 = $last_modified;
	$data2 = time();
	$difference = ($data2 - $data1);
	return round($difference/60);
}
	
public function check_storage_table($force=false){
	
	if(!$force){
		if($GLOBALS["AS_ROOT"]){
			if(!$GLOBALS["VERBOSE"]){
				if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
				$unix=new unix();
				$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
				$XT_TIME=$this->file_time_min($timefile);
				if($XT_TIME<30){return true;}
				@unlink($timefile);
				@file_put_contents($timefile,time());
				$this->ToSyslog("check_storage_table():: Verify MySQL Tables ( as root) for database artica_backup last check since {$XT_TIME}Mn");
			}
		}
	
	
	
		if(!$GLOBALS["AS_ROOT"]){
			if(!$GLOBALS["VERBOSE"]){
				$timefile="/usr/share/artica-postfix/ressources/logs/web/".basename(__FILE__).".time";
				$XT_TIME=$this->file_time_min($timefile);
				if($XT_TIME<40){return true;}
				$this->ToSyslog("check_storage_table():: Verify MySQL Tables ( as {$_SESSION["uid"]} ) for database $this->database last check since {$XT_TIME}Mn");
				@unlink($timefile);
				@file_put_contents($timefile,time());
			}
		}
	}
	
	
	
	
		if(!$this->DATABASE_EXISTS("vmail")){$this->CREATE_DATABASE("vmail");}
		if(!$this->DATABASE_EXISTS("artica_backup")){$this->CREATE_DATABASE("artica_backup");}
		if(!$this->DATABASE_EXISTS("eyeOS")){$this->CREATE_DATABASE("eyeOS");}	
		$this->check_mysql_dbtables();

		
		if(!$this->TABLE_EXISTS('amanda_dumptype','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`amanda_dumptype` (
				`dumpname` VARCHAR( 90 ) NOT NULL ,
				`auth` VARCHAR( 20 ) NOT NULL ,
				`comment` VARCHAR( 128 ) NOT NULL ,
				`comprate` VARCHAR( 10 ) NOT NULL ,
				`compress` VARCHAR( 10 ) NOT NULL ,
				`dumpcycle` VARCHAR(20 ) NOT NULL ,
				`estimate` VARCHAR( 10 ) NOT NULL ,
				`holdingdisk` INT( 1 ) NOT NULL DEFAULT '1',
				`index` INT( 1 ) NOT NULL DEFAULT '1',
				`maxdumps` INT( 1 ) NOT NULL DEFAULT '1',
				`maxpromoteday` INT( 5 ) NOT NULL DEFAULT '1000',
				`priority` VARCHAR( 10 ) NOT NULL DEFAULT 'medium',
				`program` VARCHAR( 10 ) NOT NULL DEFAULT 'DUMP',
				`skip-full` INT( 1 ) NOT NULL DEFAULT '1',
				`skip-incr` INT( 1 ) NOT NULL DEFAULT '0',
				`strategy` VARCHAR( 10 ) NOT NULL ,
				PRIMARY KEY ( `dumpname` )
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		
		if(!$this->TABLE_EXISTS('mysqldb_backup','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`mysqldb_backup` (
				`md5` VARCHAR( 90 ) NOT NULL,
				`database` VARCHAR( 90 ) NOT NULL,
				`compress` SMALLINT( 1 ) NOT NULL ,
				`InstanceID` SMALLINT( 3 ) NOT NULL,
				`targetDir` VARCHAR( 255 ) NOT NULL,
				`MaxDay` SMALLINT( 2 ) NOT NULL ,
				`enabled` SMALLINT( 1 ) NOT NULL ,
				 PRIMARY KEY ( `md5` ),
				 KEY `MaxDay`(`MaxDay`),
				 KEY `enabled`(`enabled`),
				 KEY `database`(`database`),
				 KEY `InstanceID`(`InstanceID`)
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		

		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`wsusoffline_dirs` (
				`path` VARCHAR( 128 ) NOT NULL PRIMARY KEY,
				`filename` VARCHAR( 128 ) NOT NULL  DEFAULT 'filename',
				`sizebytes` INT(10) UNSIGNED NOT NULL DEFAULT 0,
				`filemtime`  INT(10) UNSIGNED NOT NULL DEFAULT 0,
				 KEY `path`(`path`),
				 KEY `filename`(`filename`)
				) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_backup');
		
		
		
		if(!$this->TABLE_EXISTS('storage_containers','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`storage_containers` (
				`groupid` VARCHAR( 255 ) NOT NULL,
				`enabled` smallint( 1 ) NOT NULL,
				`maxsize` INT UNSIGNED ,
				`directory` VARCHAR( 255 ) NOT NULL,
				 PRIMARY KEY ( `groupid` ),
				 KEY `enabled`(`enabled`)
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`autofs` (
				`mountpoint` VARCHAR( 128 ) NOT NULL,
				`enabled` smallint( 1 ) NOT NULL,
				`automountInformation` VARCHAR( 255 ) NOT NULL,
				 PRIMARY KEY ( `mountpoint` ),
				 KEY `automountInformation`(`automountInformation`)
				) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`lsm_rules` (
			`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			`interface` VARCHAR( 20 ) NOT NULL,
			`enabled` smallint( 1 ) NOT NULL DEFAULT 1,
			`check_arp` smallint( 1 ) NOT NULL DEFAULT 0,
			`checkip` varchar(40) ,
			`max_packet_loss` smallint( 2 ) NOT NULL DEFAULT '15',
			`max_successive_pkts_lost` smallint( 2 ) NOT NULL DEFAULT '7',
			`min_packet_loss` smallint( 2 ) NOT NULL DEFAULT '5',
			`min_successive_pkts_rcvd` smallint( 2 ) NOT NULL DEFAULT '10',
			`interval_ms` INT( 10 ) NOT NULL DEFAULT '1000',
			`timeout_ms` INT( 10 ) NOT NULL DEFAULT '1000',
			`ttl`  smallint( 2 ) NOT NULL DEFAULT '64',
			`action`  smallint( 2 ) NOT NULL DEFAULT '1',
			`actionconfig` TEXT,
			 PRIMARY KEY ( `id` ),
			 KEY `enabled`(`enabled`),
			 KEY `action`(`action`),
			 KEY `checkip`(`checkip`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_backup');
		
		
		
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			
		
		#   checkip=127.108.68.69
		#   eventscript=/usr/libexec/foolsm/conn1
		#   max_packet_loss=15
		#   max_successive_pkts_lost=7
		#   min_packet_loss=5
		#   min_successive_pkts_rcvd=10
		#   interval_ms=1000
		#   timeout_ms=1000
		#   warn_email=root1@some.tld
		#   check_arp=0
		#   sourceip=
		#   device=
		#   ttl=64
		

		

		
		
		$sql="CREATE TABLE IF NOT EXISTS `userpref` (
		username varchar(100) NOT NULL default '',
		preference varchar(30) NOT NULL default '',
		value varchar(100) NOT NULL default '',
		prefid int(11) NOT NULL auto_increment,
		PRIMARY KEY  (prefid),
		KEY username (username)
		) Engine=MyISAM COMMENT='Table SpamAsssin multi-utilisateurs';";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){echo $this->mysql_error;}
		
		
		$sql="CREATE TABLE IF NOT EXISTS `smptd_client_access` (
		ou varchar(100) NOT NULL default '',
		configuration TEXT NULL,
		PRIMARY KEY  (ou),
		KEY ou (ou)
		) Engine=MyISAM COMMENT='Table Postfix check_recipient_access';";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){echo $this->mysql_error;}

		$sql="CREATE TABLE IF NOT EXISTS `smptd_acls` (
		ID int(11) NOT NULL auto_increment,
		ou varchar(100) NOT NULL default '',
		type varchar(50),
		PRIMARY KEY  (ID),
		KEY ou (ou),
		KEY type (type)
		) Engine=MyISAM COMMENT='Table Postfix ACLS by OU';";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){echo $this->mysql_error;}		
		
		
		

		
		$sql="CREATE TABLE IF NOT EXISTS `philesight` (
			`directory` VARCHAR( 255 ) NOT NULL,
			`partition` VARCHAR( 128 ) NOT NULL,
			`hd` VARCHAR( 60 ) NOT NULL,
			`enabled` smallint( 1 ) NOT NULL,
			`maxtime` INT(10) NOT NULL DEFAULT 0,
			`lastscan` INT(10) NOT NULL DEFAULT 0,
			`USED` FLOAT NOT NULL DEFAULT 0,
			`FREEMB` INT UNSIGNED NOT NULL DEFAULT 0,
			 PRIMARY KEY ( `directory` ),
			 KEY `lastscan` ( `lastscan` ),
			 KEY `partition` ( `partition` ),
			 KEY `hd` ( `hd` ),
			 KEY `enabled`(`enabled`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			
		if($this->COUNT_ROWS("philesight", "artica_backup")==0){
			$f[]="('/var/log','1','360')";
			$f[]="('/home','1','360')";
			$f[]="('/opt','1','360')";
			$f[]="('/var','1','360')";
			$f[]="('/etc','1','420')";
			$this->QUERY_SQL("INSERT IGNORE INTO philesight (`directory`,`enabled`,`maxtime`) 
					VALUES ".@implode(",", $f),"artica_backup");
			unset($f);
			
		}


		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`postfix_dnsbl` (
		`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		`dnsbl` VARCHAR( 128 ) NOT NULL,
		`hostname` VARCHAR( 128 ) NOT NULL,
		 KEY `hostname`(`hostname`),
		 KEY `dnsbl`(`dnsbl`)) ENGINE=MYISAM;";
		
		$this->QUERY_SQL($sql,'artica_backup');
		

		
		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`iptables_webint` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				`pattern` VARCHAR(128) NOT NULL,
				UNIQUE KEY `pattern` (`pattern`)
				 
				) ENGINE=MYISAM;";
		
		$this->QUERY_SQL($sql,'artica_backup');	
		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `fw_time` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				`pattern` VARCHAR(128) NOT NULL,
				`pattern_type` VARCHAR(40) NOT NULL,
				`pattern_service` VARCHAR(40) NOT NULL,
				`pattern_time` TEXT,
				`enabled` smallint( 1 ) NOT NULL DEFAULT 1,
				`deny` smallint( 1 ) NOT NULL DEFAULT 1,
				 KEY `pattern`(`pattern`),
				 KEY `pattern_type`(`pattern_type`),
				 KEY `enabled`(`enabled`)
				) ENGINE=MYISAM;";
		
		$this->QUERY_SQL($sql,'artica_backup');
		
		
		

		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`artica_webdav` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				`directory` VARCHAR(128) NOT NULL,
				`write` smallint( 1 ) NOT NULL DEFAULT 0,
				UNIQUE KEY `directory` (`directory`)
			
				) ENGINE=MYISAM;";
		
		$this->QUERY_SQL($sql,'artica_backup');		
		
		if(!$this->FIELD_EXISTS("artica_webdav","write","artica_backup")){
			$sql="ALTER TABLE `artica_webdav` ADD `write` smallint( 1 ) NOT NULL DEFAULT 0";
			$this->QUERY_SQL($sql,"artica_backup");
		}

		
		

		
		
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}

		if(!$this->TABLE_EXISTS('users_containers','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`users_containers` (
				`container_id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`container_time` INT( 10 ) NOT NULL,
				`container_name` VARCHAR( 255 ) NOT NULL,
				`groupid` VARCHAR( 255 ) NOT NULL,
				`container_size` INT UNSIGNED,
				`uid` VARCHAR( 128 ) NOT NULL,
				`directory` VARCHAR( 255 ) NOT NULL,
				`loop_dev` VARCHAR( 255 ) NOT NULL,
				`uuid` VARCHAR( 255 ) NOT NULL,
				`webdav` smallint( 1 ) NOT NULL,
				`iscsid` smallint( 1 ) NOT NULL DEFAULT '0',
				`created` smallint( 1 ) NOT NULL DEFAULT '0',
				`onerror` smallint( 1 ) NOT NULL DEFAULT '0',
				`onerrortext` VARCHAR( 255 ),
				`webdav_creds` TEXT,
				`status` TEXT,
				 KEY `container_name`(`container_name`),
				 KEY `groupid`(`groupid`),
				 KEY `iscsid`(`iscsid`),
				 KEY `webdav`(`webdav`),
				 KEY `created`(`created`),
				 KEY `onerror`(`onerror`),
				 KEY `uid`(`uid`),
				 KEY `directory`(`directory`)
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}	

		if(!$this->FIELD_EXISTS("users_containers","created","artica_backup")){
			$sql="ALTER TABLE `users_containers` ADD `created` smallint( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( created ) ";
			$this->QUERY_SQL($sql,"artica_backup");
		}
		if(!$this->FIELD_EXISTS("users_containers","onerror","artica_backup")){
			$sql="ALTER TABLE `users_containers` ADD `onerror` smallint( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( onerror ) ";
			$this->QUERY_SQL($sql,"artica_backup");
		}
		if(!$this->FIELD_EXISTS("users_containers","onerrortext","artica_backup")){
			$sql="ALTER TABLE `users_containers` ADD `onerrortext` VARCHAR( 255 )";
			$this->QUERY_SQL($sql,"artica_backup");
		}
		if(!$this->FIELD_EXISTS("users_containers","loop_dev","artica_backup")){
			$sql="ALTER TABLE `users_containers` ADD `loop_dev` VARCHAR( 255 )";
			$this->QUERY_SQL($sql,"artica_backup");
		}						
		if(!$this->FIELD_EXISTS("users_containers","uuid","artica_backup")){
			$sql="ALTER TABLE `users_containers` ADD `uuid` VARCHAR( 255 )";
			$this->QUERY_SQL($sql,"artica_backup");
		}
		if(!$this->FIELD_EXISTS("users_containers","status","artica_backup")){
			$sql="ALTER TABLE `users_containers` ADD `status` TEXT";
			$this->QUERY_SQL($sql,"artica_backup");
		}		
		if(!$this->TABLE_EXISTS('mysqldb_backup_containers','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`mysqldb_backup_containers` (
				`md5` VARCHAR( 90 ) NOT NULL,
				`fullpath` VARCHAR( 255 ) NOT NULL,
				`duration` VARCHAR( 128 ) NOT NULL,
				`zDate` date NOT NULL ,
				`size` BIGINT UNSIGNED,
				 KEY `md5`(`md5`),
				 KEY `fullpath`(`fullpath`),
				 KEY `zDate`(`zDate`),
				 KEY `size`(`size`)
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}	

		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`texttoldap` (
		`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`connection` VARCHAR( 128 ) NOT NULL ,
		`hostname` VARCHAR( 128 ) NOT NULL ,
		`username` VARCHAR( 128 ) NOT NULL ,
		`password` VARCHAR( 128 ) NOT NULL ,
		`folder` VARCHAR( 128 ) NOT NULL ,
		`filename` VARCHAR( 128 ) NOT NULL ,
		`lastmd5` VARCHAR( 128 ) NOT NULL ,
		`ldapgroup` BIGINT( 128 ) NOT NULL ,		
		`lastscan` DATETIME NOT NULL,			
			INDEX ( `username` ) ,
		 	KEY `lastmd5`(`lastmd5`),
			KEY `ldapgroup`(`ldapgroup`),
			KEY `connection`(`connection`),
			KEY `hostname`(`hostname`),
			KEY `folder`(`folder`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,"artica_backup");
		
			

		

	

		if(!$this->TABLE_EXISTS('phpmyadminsecu','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`phpmyadminsecu` (
			`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`pattern` VARCHAR( 128 ) NOT NULL ,
			`type` smallint( 1 ) NOT NULL ,
			`enabled` smallint( 1 ) NOT NULL ,
			INDEX (`type` ,`enabled`) ,
			UNIQUE (`pattern`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		} 		

		
		if(!$this->TABLE_EXISTS('amanda_hosts','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`amanda_hosts` (
			`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`hostname` VARCHAR( 128 ) NOT NULL ,
			`dumptype` VARCHAR( 50 ) NOT NULL ,
			`directory` VARCHAR( 255 ) NOT NULL ,
			`resolved` VARCHAR( 90 ) NOT NULL ,
			INDEX ( `hostname` )
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		} 	
		
		if(!$this->TABLE_EXISTS('arpspoof_rules','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `arpspoof_rules` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`rulename` VARCHAR( 128 ) NOT NULL ,
			`gateway` VARCHAR( 80 ) NOT NULL ,
			`iface` VARCHAR( 40 ) NOT NULL ,
			`enabled` smallint( 1 ) NOT NULL DEFAULT 0 ,
			INDEX ( `rulename` ),
			KEY `gateway`(`gateway`),
			KEY `iface`(`iface`),
			KEY `enabled`(`enabled`)		
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}

		if(!$this->TABLE_EXISTS('arpspoof_objects','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `arpspoof_objects` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`ipaddr` VARCHAR( 90 ) NOT NULL ,
			`ruleid` INT UNSIGNED ,
			INDEX ( `ipaddr` ),
			KEY `ruleid`(`ruleid`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}		
		
		if(!$this->TABLE_EXISTS('clusters','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`clusters` (
			`ipaddr` VARCHAR( 60 ) NOT NULL PRIMARY KEY,
			`hostname` VARCHAR( 128 ) NOT NULL ,
			`disk` VARCHAR( 128 ) NOT NULL ,
			`zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`sslport` INT( 5 ) NOT NULL ,
			`username` VARCHAR( 128 ) NOT NULL ,
			`password` VARCHAR( 128 ) NOT NULL ,
			`IsMe` TINYINT( 1 ) NOT NULL ,
			INDEX ( `zDate`,`hostname`,`disk`,`IsMe` )
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		} 		

		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`mailarchives` (
			`zmd5` VARCHAR( 90 ) NOT NULL PRIMARY KEY,
			`ou` VARCHAR( 90 ) NOT NULL,
			`email` VARCHAR( 250 ) NOT NULL ,
			`direction` VARCHAR( 40 ) NOT NULL ,
			`next` VARCHAR( 250 ) NOT NULL ,
			`params` TEXT,
			`enable` TINYINT( 1 ) NOT NULL ,
			KEY  `enable` ( `enable`),
			KEY `email` (`email`),
			KEY `next` (`next`),
			KEY `direction` (`direction`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		if(!$this->FIELD_EXISTS("mailarchives","params","artica_backup")){
			$sql="ALTER TABLE `mailarchives` ADD `params` TEXT";
			$this->QUERY_SQL($sql,"artica_backup");
		}
		
		
		if(!$this->TABLE_EXISTS('syspackages_updt','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`syspackages_updt` (
			`package` VARCHAR( 90 ) NOT NULL ,
			`description` TEXT NOT NULL ,
			`upgrade` TINYINT( 1 ) NOT NULL DEFAULT '0',
			`progress` TINYINT( 2 ) NOT NULL ,
			INDEX ( `upgrade` , `progress` ) ,
			UNIQUE (`package`)) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		} 
		
		

		
				
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`mysqldbsmulti` (
			`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`databasename` VARCHAR( 90 ) NOT NULL ,
			`instance_id` BIGINT( 90 ) NOT NULL ,
			`TableCount` INT(5) NOT NULL ,
			`dbsize` BIGINT UNSIGNED DEFAULT '0',
			 INDEX ( `TableCount` , `dbsize`,`instance_id` ) ,
			 KEY `databasename` (`databasename`)) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		

		if(!$this->TABLE_EXISTS('mysqldbtablesmulti','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`mysqldbtablesmulti` (
			`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`instance_id` BIGINT( 90 ) NOT NULL ,
			`tablename` VARCHAR( 90 ) NOT NULL ,
			`databasename` VARCHAR( 90 ) NOT NULL ,
			`tablesize` BIGINT UNSIGNED DEFAULT '0',
			`tableRows` BIGINT UNSIGNED DEFAULT '0',
			 INDEX ( `tablesize` , `tableRows`,`instance_id` ) ,
			 KEY `databasename`(`databasename`),
			 KEY `tablename`(`tablename`)
			 ) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}		
		
		
		
		if(!$this->TABLE_EXISTS('exports','artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS `exports` (
			  `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			  `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  `function` varchar(60),
			  `line` int(3) NOT NULL,
			  `filename` varchar(60),
			  `subject` varchar(255),
			  `description` text,
			  PRIMARY KEY (`ID`),
			  KEY `zDate` (`zDate`,`function`,`filename`),
			  KEY `subject` (`subject`),
			  FULLTEXT KEY `description` (`description`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_events');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}  		
		
		if(!$this->TABLE_EXISTS('adgroups','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`adgroups` (
			`gpid` BIGINT UNSIGNED NOT NULL, 
			`groupname` VARCHAR( 128 ) NOT NULL ,
			 PRIMARY KEY (`gpid`),
			 KEY `groupname` (`groupname`)
			 ) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		if(!$this->TABLE_EXISTS('adusers','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`adusers` (
			`gpid` BIGINT UNSIGNED NOT NULL, 
			`uid` VARCHAR( 128 ) NOT NULL ,
			 KEY `gpid` (`gpid`),
			 KEY `uid` (`uid`)
			 ) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		if(!$this->TABLE_EXISTS('freeradius_db','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`freeradius_db` (
			`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`connectionname` VARCHAR( 128 ) NOT NULL ,
			`connectiontype` VARCHAR( 40 ) NOT NULL ,
			`params` LONGTEXT NOT NULL ,
			`enabled` smallint(1) NOT NULL,
			 PRIMARY KEY (`ID`),
			 KEY `enabled` (`enabled`),
			 KEY `connectiontype` (`connectiontype`),
			 KEY `connectionname` (`connectionname`)
			 ) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}		

		
		if(!$this->TABLE_EXISTS('setup_center','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`setup_center` (
			`CODE_NAME` VARCHAR( 50 ) NOT NULL ,
			`CODE_NAME_STRING` VARCHAR( 128 ) NOT NULL ,
			`CODE_NAME_ABOUT` VARCHAR( 50 ) NOT NULL ,
			`curversionstring` VARCHAR( 50 ) NOT NULL ,
			`curversionbin` INT UNSIGNED ,
			`nextversionstring` VARCHAR( 50 ) NOT NULL ,
			`REPO_CODE` VARCHAR( 20 ) NOT NULL ,
			`FAMILY` VARCHAR( 20 ) NOT NULL ,
			`SUBFAMILY` VARCHAR( 20 ) NOT NULL ,
			`upgrade` TINYINT( 1 ) NOT NULL ,
			`progress` TINYINT( 2 ) NOT NULL ,
			`shouldpgd` TINYINT( 1 ) NOT NULL ,
			`events` LONGTEXT NOT NULL ,
			`progress_text` VARCHAR( 128 ) NOT NULL ,
			PRIMARY KEY ( `CODE_NAME` ) ,
			KEY `CODE_NAME_STRING` (`CODE_NAME_STRING`),
			KEY `CODE_NAME_ABOUT` (`CODE_NAME_ABOUT`),
			INDEX ( `curversionbin` , `FAMILY` , `upgrade` , `progress`,`shouldpgd`,`REPO_CODE`,`SUBFAMILY` )
			) ENGINE=MYISAM;"; 		
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		
		
		if(!$this->TABLE_EXISTS('arpcache','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `arpcache` (
			  `mac` varchar(90)  NOT NULL,
			  `ipaddr` varchar(60)  NOT NULL,
			  `hostname` varchar(128)  NOT NULL,
			  `HWtype` varchar(40)  NOT NULL,
			  `flag` varchar(5)  NOT NULL,
			  `iface` varchar(20)  NOT NULL,
			  `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			  PRIMARY KEY (`ID`),
			  KEY `ipaddr` (`ipaddr`,`HWtype`,`flag`,`iface`),
			  KEY `hostname` (`hostname`),
			  KEY `mac` (`mac`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		
		if(!$this->TABLE_EXISTS('clamavsig','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `clamavsig` (
			  `patternfile` varchar(90)  NOT NULL,
			  `zDate` timestamp NOT NULL,
			  `version` INT(10)  NOT NULL,
			  `signatures` BIGINT UNSIGNED  NOT NULL,
			  PRIMARY KEY (`patternfile`),
			  KEY `version` (`version`),
			  KEY `signatures` (`signatures`),
			  KEY `zDate` (`zDate`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}

		if(!$this->TABLE_EXISTS('ejabberd','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `ejabberd` (
			  `hostname` varchar(255)  NOT NULL,
			  `Config` LONGTEXT NOT NULL,
			  `enabled` smallint(1)  NOT NULL,
			  `uid` varchar(90)  NOT NULL,
			  PRIMARY KEY (`hostname`),
			  KEY `enabled` (`enabled`),
			  KEY `uid` (`uid`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}		
		
		if(!$this->FIELD_EXISTS("ejabberd","uid","artica_backup")){
			$sql="ALTER TABLE `ejabberd` ADD `uid` VARCHAR( 90 ) NOT NULL ,ADD INDEX ( `uid` )";
			$this->QUERY_SQL($sql,"artica_backup");
		}
		
		if(!$this->FIELD_EXISTS("setup_center","CODE_NAME_ABOUT","artica_backup")){
			$sql="ALTER TABLE `setup_center` ADD `CODE_NAME_ABOUT` VARCHAR( 50 ) NOT NULL ,ADD INDEX ( CODE_NAME_ABOUT )";
			$this->QUERY_SQL($sql,"artica_backup");
		}		
		
		
		if(!$this->TABLE_EXISTS('update_events','artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS `update_events` (
				  `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				  `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				  `description` text NOT NULL,
				  `function` varchar(50) NOT NULL,
				  `category` varchar(50) NOT NULL,
				  `process` varchar(50) NOT NULL,
				  `line` INT UNSIGNED NOT NULL,
				  `servername` varchar(128) NOT NULL,
				  PRIMARY KEY (`ID`),
				  KEY `function` (`function`,`process`,`line`),
				  KEY `servername` (`servername`),
				  KEY `category` (`category`),
				  KEY `zDate` (`zDate`)
				) ENGINE=MYISAM;";
			
			$this->QUERY_SQL($sql,'artica_events');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		} 


		if(!$this->TABLE_EXISTS('psalerts','artica_events')){
			$sql="CREATE TABLE IF NOT EXISTS `psalerts` (
			 	`zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				content LONGTEXT,
				PRIMARY KEY (`zDate`)
			) ENGINE=MYISAM;
			";
			$this->QUERY_SQL($sql,'artica_events');
		}		
		
		
			$sql="CREATE TABLE IF NOT EXISTS `ldap_ou_import` (
			  `ID` int(11) NOT NULL AUTO_INCREMENT,
			  `ou` varchar(250) NOT NULL,
			  `ScheduleMin` varchar(250) NOT NULL,
			  `ldapdatas` blob NOT NULL,
			  `enabled` smallint(1) NOT NULL DEFAULT '0',
			  `connexion_name` VARCHAR( 255 ) NOT NULL ,
			  PRIMARY KEY (`ID`),
			  KEY `ScheduleMin` (`ScheduleMin`),
			  KEY `ou` (`ou`),
			  KEY `enabled` (`enabled`),
			  KEY `connexion_name` (`connexion_name`)
			) ENGINE=MyISAM";
			$this->QUERY_SQL($sql,'artica_backup');
			 		
	
	
			
				
			
			
	
		if(!$this->TABLE_EXISTS('storage','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `storage` (
				  `MessageID` varchar(80) NOT NULL,
				  `zDate` datetime NOT NULL,
				  `mailfrom` varchar(255) NOT NULL,
				  `subject` varchar(255) NOT NULL,
				  `MessageBody` text NOT NULL,
				  `organization` varchar(80) NOT NULL,
				  `uid` varchar(50) NOT NULL,
				  `file_path` varchar(255) DEFAULT NULL,
				  `original_messageid` varchar(80) NOT NULL,
				  `message_size` int(10) NOT NULL,
				  `mailfrom_domain` varchar(128) NOT NULL,
				  `mailto` varchar(255) NOT NULL,
				  `BinMessg` longblob NOT NULL,
				  `filename` varchar(128) NOT NULL,
				  `filesize` int(10) NOT NULL,
				  PRIMARY KEY (`MessageID`),
				  KEY `mailfrom` (`mailfrom`),
				  KEY `organization` (`organization`),
				  KEY `uid` (`uid`),
				  KEY `original_messageid` (`original_messageid`),
				  KEY `mailfrom_domain` (`mailfrom_domain`),
				  KEY `mailto` (`mailto`),
				  KEY `filename` (`filename`),
				  FULLTEXT KEY `MessageBody` (`MessageBody`)
				) ENGINE=MyISAM;";
				
				$this->QUERY_SQL($sql,'artica_backup');
				}
				
		if(!$this->FIELD_EXISTS("storage","BinMessg","artica_backup")){
			$sql="ALTER TABLE `storage` ADD `BinMessg` LONGBLOB NOT NULL ";
			$this->QUERY_SQL($sql,"artica_backup");
		}

		if(!$this->FIELD_EXISTS("storage","filename","artica_backup")){
			$sql="ALTER TABLE `storage` ADD `filename` VARCHAR( 128 ) NOT NULL ,ADD INDEX ( filename )";
			$this->QUERY_SQL($sql,"artica_backup");
		}	
		
		if(!$this->FIELD_EXISTS("storage","filesize","artica_backup")){
			$sql="ALTER TABLE `storage` ADD `filesize` INT( 10 ) NOT NULL";
			$this->QUERY_SQL($sql,"artica_backup");
		}			

		if(!$this->TABLE_EXISTS('backup_schedules','artica_backup')){				 
				$sql="CREATE TABLE IF NOT EXISTS `backup_schedules` (
				  `ID` int(5) NOT NULL AUTO_INCREMENT,
				  `zMD5` varchar(90) NOT NULL,
				  `resource_type` varchar(10) NOT NULL,
				  `pattern` varchar(255) NOT NULL,
				  `schedule` varchar(90) NOT NULL,
				  `datasbackup` text NOT NULL,
				  `container` varchar(20) NOT NULL,
				  `pid` INT( 5 ) NOT NULL,
				  PRIMARY KEY (`ID`),
				  UNIQUE KEY `zMD5` (`zMD5`),
				  KEY `resource_type` (`resource_type`,`pattern`),
				  KEY `container` (`container`)) ";
				$this->QUERY_SQL($sql,'artica_backup');
		}
		
		if(!$this->FIELD_EXISTS("backup_schedules","pid","artica_backup")){
			$sql="ALTER TABLE `backup_schedules` ADD `pid` INT( 5 ) NOT NULL";
			$this->QUERY_SQL($sql,"artica_backup");
		}	

		if(!$this->TABLE_EXISTS('backup_storages','artica_backup')){				 
				$sql="CREATE TABLE IF NOT EXISTS `backup_storages` (
			  `taskid` int(10) NOT NULL,
			  `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  `cnx_params` text NOT NULL,
			  `size` BIGINT UNSIGNED NOT NULL,
			  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			  `zmd5` varchar(90) ,
			  PRIMARY KEY (`ID`),
			  UNIQUE KEY `zmd5` (`zmd5`),
			  KEY `taskid` (`taskid`,`zDate`,`size`)
			) ";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}	
		}

		
		if(!$this->TABLE_EXISTS('ipban','artica_backup')){				 
				$sql="CREATE TABLE IF NOT EXISTS `ipban` (
			  	`network` varchar(90) ,
			  	PRIMARY KEY (`network`)
			  	) ";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}	
		}
		
		if(!$this->TABLE_EXISTS('ipband_rules','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `ipband_rules` (
				zmd5 varchar(128) NOT NULL PRIMARY KEY,
				network varchar(128) NOT NULL,
				topnumber smallint(2) NOT NULL,
				averageperiod INT(10) NOT NULL,
				reportperiod INT(10) NOT NULL,
				 KEY `network` (`network`),
			  	 KEY `topnumber` (`topnumber`),
			   	 KEY `averageperiod` (`averageperiod`),
			     KEY `reportperiod` (`reportperiod`)
			) ENGINE=MYISAM; ";
			
			$this->QUERY_SQL($sql,'artica_events');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			
		}
		
		

		if(!$this->TABLE_EXISTS('ipband','artica_events')){
			$sql="CREATE TABLE IF NOT EXISTS `ipband` (
			  `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  `IP_FROM` varchar(90) NOT NULL,
			  `IP_FROM_HOST` varchar(128) NULL,
			  `PORT_FROM` int(50) NOT NULL,
			  `IP_TO` varchar(90) NOT NULL,
			  `IP_TO_HOST` varchar(128) NULL,
			  `PORT_TO` int(50) NOT NULL,
			  `size` BIGINT UNSIGNED NOT NULL,
			  `proto` varchar(10) NOT NULL,
			   KEY `zDate` (`zDate`),
			   KEY `IP_FROM` (`IP_FROM`),
			   KEY `proto` (`proto`),
			   KEY `PORT_FROM` (`PORT_FROM`),
			   KEY `PORT_TO` (`PORT_TO`),
			   KEY `IP_FROM_HOST` (`IP_FROM_HOST`),
			   KEY `IP_TO_HOST` (`IP_TO_HOST`),
			   index `size` (`size`)
			) ENGINE=MYISAM; ";
			$this->QUERY_SQL($sql,'artica_events');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}	
		}
		
		if(!$this->FIELD_EXISTS("ipband","IP_FROM_HOST","artica_events")){
			$sql="ALTER TABLE `ipband` ADD `IP_FROM_HOST` varchar(128) NULL,ADD INDEX ( `IP_FROM_HOST` ) ";
			$this->QUERY_SQL($sql,"artica_events");
		}

		if(!$this->FIELD_EXISTS("ipband","IP_TO_HOST","artica_events")){
			$sql="ALTER TABLE `ipband` ADD `IP_TO_HOST` varchar(128) NULL,ADD INDEX ( `IP_TO_HOST` ) ";
			$this->QUERY_SQL($sql,"artica_events");
		}			
		
		


		if(!$this->TABLE_EXISTS('files_storage','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `files_storage` (
				  `id_files` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
				  `bin_data` longblob NOT NULL,
				  `description` tinytext NOT NULL,
				  `filename` varchar(50) NOT NULL,
				  `filesize` varchar(50) NOT NULL,
				  `filetype` varchar(50) NOT NULL,
				  PRIMARY KEY (`id_files`)
					) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
		}
	
		if(!$this->FIELD_EXISTS("files_storage","commandline","artica_backup")){
			$sql="ALTER TABLE `files_storage` ADD `commandline` VARCHAR( 200 ) NOT NULL";
			$this->QUERY_SQL($sql,"artica_backup");
		}

		if(!$this->FIELD_EXISTS("files_storage","ExecuteAfter","artica_backup")){
			$sql="ALTER TABLE `files_storage` ADD `ExecuteAfter` VARCHAR( 200 ) NOT NULL";
			$this->QUERY_SQL($sql,"artica_backup");
		}

		if(!$this->FIELD_EXISTS("files_storage","OCS_PACKAGE","artica_backup")){
			$sql="ALTER TABLE `files_storage` ADD `OCS_PACKAGE` INT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( OCS_PACKAGE ) ";
			$this->QUERY_SQL($sql,"artica_backup");
		}	

	if(!$this->TABLE_EXISTS('deploy_tasks','artica_backup')){	
		$sql="CREATE TABLE IF NOT EXISTS `deploy_tasks` (
			  `ID` int(5) NOT NULL AUTO_INCREMENT,
			  `files_id` int(5) NOT NULL,
			  `computer_id` varchar(90) NOT NULL,
			  `results` text NOT NULL,
			  `executed` smallint(1) NOT NULL DEFAULT '0',
			  `commandline` varchar(255) NOT NULL,
			  `username` varchar(128) NOT NULL,
			  `password` varchar(128) NOT NULL,
			  `PROGRESS` varchar(128) NOT NULL,
			  `pourcent` INT( 2 ) NOT NULL,
			  `TASK_TYPE` VARCHAR( 60 ) NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `files_id` (`files_id`,`computer_id`),
			  KEY `computer_id` (`computer_id`),
			  KEY `executed` (`executed`),
			  KEY `TASK_TYPE` (`TASK_TYPE`),
			  KEY `PROGRESS` (`PROGRESS`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_backup');	
	
		}
		
	if(!$this->TABLE_EXISTS('mysqlmulti','artica_backup')){	
		$sql="CREATE TABLE IF NOT EXISTS `mysqlmulti` (
			  `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			  `servername` varchar(128) NOT NULL,
			  `listen_port` INT(10) NOT NULL,
			  `listen_addr` varchar(60) NOT NULL,
			  `usesocket` smallint(1) NOT NULL,
			  `Dir` varchar(255) NOT NULL,
			  `params` TEXT NOT NULL,
			  `enabled` smallint(1) NOT NULL,
			  `uid` varchar(128) NOT NULL,
			  `watchdog` smallint(1) NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `servername` (`servername`),
			  KEY `listen_port` (`listen_port`),
			  KEY `listen_addr` (`listen_addr`),
			  KEY `Dir` (`Dir`),
			  KEY `enabled` (`enabled`),
			  KEY `watchdog` (`watchdog`),
			  KEY `uid` (`uid`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_backup');	
	
		}
		
		if(!$this->FIELD_EXISTS("mysqlmulti","watchdog","artica_backup")){
			$sql="ALTER TABLE `mysqlmulti` ADD `watchdog` TINYINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( watchdog ) ";
			writelogs("CREATE:: mysqlmulti watchdog in artica_backup",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$this->QUERY_SQL($sql,"artica_backup");
		}		
		

	if(!$this->FIELD_EXISTS("mysqlmulti","usesocket","artica_backup")){
		$sql="ALTER TABLE `mysqlmulti` ADD `usesocket` smallint(1) NOT NULL";
		$this->QUERY_SQL($sql,"artica_backup");
	}	
		
	if(!$this->FIELD_EXISTS("deploy_tasks","TASK_TYPE","artica_backup")){
		$sql="ALTER TABLE `deploy_tasks` ADD `TASK_TYPE` VARCHAR( 60 ) NOT NULL,ADD INDEX ( `TASK_TYPE` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}

	if(!$this->FIELD_EXISTS("deploy_tasks","PROGRESS","artica_backup")){
		$sql="ALTER TABLE `deploy_tasks` ADD `PROGRESS` VARCHAR( 60 ) NOT NULL,ADD INDEX ( `PROGRESS` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}	

	if(!$this->FIELD_EXISTS("deploy_tasks","debug_mode","artica_backup")){
		$sql="ALTER TABLE `deploy_tasks` ADD `debug_mode` INT( 1 ) NOT NULL DEFAULT '0'";
		$this->QUERY_SQL($sql,"artica_backup");
	}		
		
	if(!$this->FIELD_EXISTS("deploy_tasks","pourcent","artica_backup")){
		$sql="ALTER TABLE `deploy_tasks` ADD `pourcent` INT(2) NOT NULL DEFAULT '0'";
		$this->QUERY_SQL($sql,"artica_backup");
	}	

	if(!$this->FIELD_EXISTS("storage","file_path","artica_backup")){
		$sql="ALTER TABLE `storage` ADD `file_path` varchar(255) NOT NULL ,ADD INDEX ( `file_path` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}
	
	if(!$this->FIELD_EXISTS("storage","original_messageid","artica_backup")){
		$sql="ALTER TABLE `storage` ADD `original_messageid` varchar(80) NOT NULL ,ADD INDEX ( `original_messageid` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}	
	
	if(!$this->FIELD_EXISTS("storage","message_size","artica_backup")){
		$sql="ALTER TABLE `storage` ADD `message_size` INT(10) NOT NULL ";
		$this->QUERY_SQL($sql,"artica_backup");
	}		

	if(!$this->FIELD_EXISTS("storage","mailfrom_domain","artica_backup")){
		$sql="ALTER TABLE `storage` ADD `mailfrom_domain` varchar(255) NOT NULL, ADD INDEX ( `mailfrom_domain` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}	

	
	if(!$this->FIELD_EXISTS("storage","mailto","artica_backup")){
		$sql="ALTER TABLE `storage` ADD `mailto` varchar(255) NOT NULL ,ADD INDEX ( `mailto` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}
	
	
	if(!$this->TABLE_EXISTS('officials_domains','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`officials_domains` (`domain` VARCHAR( 255 ) NOT NULL ,UNIQUE (`domain`))";
			$this->QUERY_SQL($sql,"artica_backup");
	}
	
	
	if(!$this->TABLE_EXISTS('register_orgs','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `register_orgs` (
					  `email` varchar(255)  NOT NULL,
					  `ou` varchar(128)  NOT NULL,
					  `company` varchar(128)  NOT NULL,
					  `domain` varchar(256)  NOT NULL,
					  `username` varchar(128)  NOT NULL,
					  `password` varchar(256)  NOT NULL,
					  `zmd5` varchar(90)  NOT NULL,
					  `sended` smallint(1) NOT NULL DEFAULT '0',
					  `register_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					  UNIQUE KEY `email` (`email`),
					  UNIQUE KEY `ou` (`ou`),
					  UNIQUE KEY `zmd5` (`zmd5`),
					  KEY `username` (`username`),
					  KEY `sended` (`sended`),
					  KEY `time` (`register_date`)
					) ENGINE=MYISAM;";
	$this->QUERY_SQL($sql,"artica_backup");
	}			
				
	
	if(!$this->TABLE_EXISTS('computers_storage','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`computers_storage` (
			`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`PUBLISHER` VARCHAR( 255 ) NOT NULL ,
			`NAME` VARCHAR( 255 ) NOT NULL ,
			`local_path` VARCHAR( 255 ) NOT NULL ,
			`command_line` VARCHAR( 255 ) NOT NULL ,
			`version` VARCHAR(45) NOT NULL ,
			KEY `PUBLISHER` (`PUBLISHER`),
			KEY `NAME` (`NAME`)
			
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,"artica_backup");
	}
	
	
	if(!$this->TABLE_EXISTS('netatalk','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`netatalk` (
			`directory` VARCHAR( 255 ) NOT NULL ,
			`sharedname` VARCHAR( 50 ) NOT NULL ,
			`allow` TEXT NOT NULL ,
			`allowed_hosts` TEXT NOT NULL ,
			`readonly` TINYINT( 1 ) NOT NULL ,
			PRIMARY KEY ( `directory` ) ,
			UNIQUE (`sharedname`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,"artica_backup");
		}
		
	
	if(!$this->TABLE_EXISTS('smbstatus_users','artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS `smbstatus_users` (
				  `ID` int(10) NOT NULL AUTO_INCREMENT,
				  `pid` int(5) NOT NULL,
				  `username` varchar(128) NOT NULL,
				  `usersgroup` varchar(128) NOT NULL,
				  `computer` varchar(128) NOT NULL,
				  `ip_addr` varchar(50) NOT NULL,
				  `sharename` varchar(128) NOT NULL,
				  `zDate` datetime NOT NULL,
				  PRIMARY KEY (`ID`),
				  KEY `username` (`username`,`usersgroup`),
				  KEY `zDate` (`zDate`),
				  KEY `pid` (`pid`)
				) ENGINE=MYISAM;";	
	
			$this->QUERY_SQL($sql,"artica_events");
	}


	if(!$this->TABLE_EXISTS('smbstatus_users_dirs','artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS `smbstatus_users_dirs` (
				  `zmd5` varchar(90) NOT NULL,
				  `directory` varchar(128) NOT NULL,
				  `filepath` varchar(255) NOT NULL,
				  `zDate` datetime NOT NULL,
				  `pid` int(5) NOT NULL,
				  PRIMARY KEY (`zmd5`),
				  KEY `zDate` (`zDate`),
				  KEY `directory` (`directory`),
				  KEY `filepath` (`filepath`),
				  KEY `pid` (`pid`)
				) ENGINE=MYISAM;";	
	
			$this->QUERY_SQL($sql,"artica_events");
	}	

	
	
	if(!$this->TABLE_EXISTS('DirectorySizes','artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS `DirectorySizes` (
			  `zmd5` varchar(90)  NOT NULL,
			  `path` varchar(255) NOT NULL,
			  `size` BIGINT UNSIGNED NOT NULL,
			  UNIQUE KEY `zmd5` (`zmd5`),
			  KEY `size` (`size`),
			  KEY `path` (`path`)
			) ENGINE=MYISAM;";	
	
			$this->QUERY_SQL($sql,"artica_events");
	}		
	
	$sql="CREATE TABLE IF NOT EXISTS radacct (
  radacctid bigint(21) NOT NULL auto_increment,
  acctsessionid varchar(64) NOT NULL default '',
  acctuniqueid varchar(32) NOT NULL default '',
  username varchar(64) NOT NULL default '',
  groupname varchar(64) NOT NULL default '',
  realm varchar(64) default '',
  nasipaddress varchar(15) NOT NULL default '',
  nasportid varchar(15) default NULL,
  nasporttype varchar(32) default NULL,
  acctstarttime datetime NULL default NULL,
  acctstoptime datetime NULL default NULL,
  acctsessiontime int(12) default NULL,
  acctauthentic varchar(32) default NULL,
  connectinfo_start varchar(50) default NULL,
  connectinfo_stop varchar(50) default NULL,
  acctinputoctets bigint(20) default NULL,
  acctoutputoctets bigint(20) default NULL,
  calledstationid varchar(50) NOT NULL default '',
  callingstationid varchar(50) NOT NULL default '',
  acctterminatecause varchar(32) NOT NULL default '',
  servicetype varchar(32) default NULL,
  framedprotocol varchar(32) default NULL,
  framedipaddress varchar(15) NOT NULL default '',
  acctstartdelay int(12) default NULL,
  acctstopdelay int(12) default NULL,
  xascendsessionsvrkey varchar(10) default NULL,
  PRIMARY KEY  (radacctid),
  KEY username (username),
  KEY framedipaddress (framedipaddress),
  KEY acctsessionid (acctsessionid),
  KEY acctsessiontime (acctsessiontime),
  KEY acctuniqueid (acctuniqueid),
  KEY acctstarttime (acctstarttime),
  KEY acctstoptime (acctstoptime),
  KEY nasipaddress (nasipaddress)
 )ENGINE=MYISAM; ;";
	$this->QUERY_SQL($sql,"artica_backup");
	
	if(!$this->FIELD_EXISTS("radacct","username","artica_backup")){
		$sql="ALTER TABLE `radacct` ADD `username` varchar(64) NOT NULL default '' ,ADD INDEX ( `username` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}	
	
 $sql="CREATE TABLE IF NOT EXISTS radcheck (
  id int(11) unsigned NOT NULL auto_increment,
  username varchar(64) NOT NULL default '',
  attribute varchar(64)  NOT NULL default '',
  op char(2) NOT NULL DEFAULT '==',
  value varchar(253) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY username (username(32))
) ENGINE=MYISAM;";$this->QUERY_SQL($sql,"artica_backup");
 
 if(!$this->FIELD_EXISTS("radcheck","username","artica_backup")){
 	$sql="ALTER TABLE `radcheck` ADD `username` varchar(64) NOT NULL default '' ,ADD INDEX ( `username` )";
 	$this->QUERY_SQL($sql,"artica_backup");
 } 
 
 

 $sql="CREATE TABLE IF NOT EXISTS radgroupcheck (
   id int(11) unsigned NOT NULL auto_increment,
  gpid int(11) NOT NULL, 		
  groupname varchar(64) NOT NULL default '',
  attribute varchar(64)  NOT NULL default '',
  op char(2) NOT NULL DEFAULT '==',
  value varchar(253)  NOT NULL default '',
  PRIMARY KEY  (id),
  KEY groupname (groupname(32)),KEY gpid (gpid)
) ENGINE=MYISAM;";$this->QUERY_SQL($sql,"artica_backup");
 
 
 $sql="CREATE TABLE IF NOT EXISTS radgroupreply (
    id int(11) unsigned NOT NULL auto_increment,
 	gpid int(11) NOT NULL,
  	groupname varchar(64) NOT NULL default '',
  	attribute varchar(64)  NOT NULL default '',
 	op char(2) NOT NULL DEFAULT '=',
  	value varchar(253)  NOT NULL default '',
  	PRIMARY KEY  (id),
 	KEY gpid (gpid),
  	KEY groupname (groupname(32))
 ) ENGINE=MYISAM;";$this->QUERY_SQL($sql,"artica_backup");

 $sql="CREATE TABLE IF NOT EXISTS radreply (
  id int(11) unsigned NOT NULL auto_increment,
  groupname varchar(64) NOT NULL default '',
  attribute varchar(64)  NOT NULL default '',
  op char(2) NOT NULL DEFAULT '=',
  value varchar(253)  NOT NULL default '',
  PRIMARY KEY  (id),
  KEY groupname (groupname(32))
 ) ENGINE=MYISAM;";$this->QUERY_SQL($sql,"artica_backup");
 
 $sql="CREATE TABLE IF NOT EXISTS radusergroup (
  username varchar(64) NOT NULL default '',
  groupname varchar(64) NOT NULL default '',
  priority int(11) NOT NULL default '1',
  gpid int(11) NOT NULL,
  KEY username (username(32)),
  KEY gpid (gpid)
 ) ENGINE=MYISAM;";$this->QUERY_SQL($sql,"artica_backup");
 

 if(!$this->FIELD_EXISTS("radusergroup","gpid","artica_backup")){
 	$sql="ALTER TABLE `radusergroup` ADD `gpid` INT(11) NOT NULL ,ADD INDEX ( `gpid` )";
 	$this->QUERY_SQL($sql,"artica_backup");
 }
 
 
 $sql="CREATE TABLE IF NOT EXISTS radpostauth (
     id int(11) NOT NULL auto_increment,
  username varchar(64) NOT NULL default '',
  pass varchar(64) NOT NULL default '',
  reply varchar(32) NOT NULL default '',
  authdate timestamp NOT NULL,
  PRIMARY KEY  (id)
 		
 ) ENGINE=MYISAM;";$this->QUERY_SQL($sql,"artica_backup");
 
 $sql="CREATE TABLE IF NOT EXISTS nas (
   id int(10) NOT NULL auto_increment,
   nasname varchar(128) NOT NULL,
   shortname varchar(32),
   type varchar(30) DEFAULT 'other',
   ports int(5),
   secret varchar(60) DEFAULT 'secret' NOT NULL,
   community varchar(50),
   description varchar(200) DEFAULT 'RADIUS Client',
   PRIMARY KEY (id),
   KEY nasname (nasname)
) ENGINE=MYISAM;";$this->QUERY_SQL($sql,"artica_backup");
	



	$sql="CREATE TABLE IF NOT EXISTS `radattribute` (
	`att_status` int(2) NOT NULL default '0',
	`attribute` varchar(200) NOT NULL,
	`default_value` varchar(200) default NULL,
	`unit` varchar(20) default NULL,
	`gp_database` varchar(40) default NULL,
	`enum_value` varchar(255) default NULL,
	`description` varchar(255) default NULL,
	`category` varchar(200) default NULL,
	PRIMARY KEY  (`attribute`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
	;$this->QUERY_SQL($sql,"artica_backup");
	
	
	
	if($this->COUNT_ROWS("radattribute","artica_backup")==0){
		$f=array();
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (1, 'Auth-Type', 'Local', 'enum', 'radgroupcheck;', 'Local;Pam;Accept;Reject;System;EAP;', 'Authorization different value', 'Divers');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Callback-ID', NULL, NULL, 'radgroupreply;', NULL, NULL, 'Callback');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Callback-Number', NULL, NULL, 'radgroupreply;', NULL, NULL, 'Callback');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Callback-Station-ID', NULL, NULL, 'radgroupcheck;', NULL, NULL, 'Callback');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Calling-Station-ID', NULL, NULL, 'radgroupcheck;', NULL, 'MAC address of client', 'Divers');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Filter-ID', NULL, NULL, 'radgroupreply;', NULL, NULL, 'Divers');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Framed-Compression', 'Van-Jacobsen-Header-Compression', NULL, 'radgroupreply;', 'None;Van-Jacobson-TCP-IP;Van-Jacobsen-Header-Compression;IPX-Header-Compression;Stac-LZS-Compression;', NULL, 'Framed');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Framed-IP-Address', NULL, NULL, 'radgroupreply;', NULL, NULL, 'Framed');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Framed-IP-Netmask', NULL, NULL, 'radgroupreply;', NULL, NULL, 'Framed');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Framed-IPX-Network', NULL, NULL, 'radgroupreply;', NULL, NULL, 'Framed');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Framed-MTU', NULL, NULL, 'radgroupreply;', NULL, NULL, 'Framed');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Framed-Route', NULL, NULL, 'radgroupreply;', NULL, NULL, 'Framed');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Framed-Routing', NULL, NULL, 'radgroupreply;', 'Broadcast routing tables and notifications;Listen for routing notification broadcasts;Broadcast and listen for notifications;', NULL, 'Framed');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Framed-Protocol', 'PPP', NULL, 'radgroupreply;', 'PPP;SLIP;ARAP;Gandalf SLP/MLP;Xylogics IPX/SIP;X.75 Synchronous;', NULL, 'Framed');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Login-LAT-Group', NULL, NULL, 'radgroupreply;', NULL, NULL, 'Login');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Login-LAT-Node', NULL, NULL, 'radgroupreply;', NULL, NULL, 'Login');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Login-LAT-Port', NULL, NULL, 'radgroupreply;', NULL, NULL, 'Login');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Login-IP-Host', NULL, NULL, 'radgroupreply;', NULL, NULL, 'Login');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Login-Service', NULL, NULL, 'radgroupreply;', 'Telnet;Rlogin;Tcp Clear;PortMaster;LAT;X25-PAD;X25-T3POS;Tcp Clear Quiet;', NULL, 'Login');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Login-TCP-Port', NULL, NULL, 'radgroupreply;', NULL, NULL, 'Login');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Port-Limit', NULL, NULL, 'radgroupreply;', NULL, NULL, 'Divers');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (1, 'Service-Type', 'Login', 'enum', 'radgroupreply;', 'Login-User;Framed;Callback Login;Callback Framed;Outbound;Administrative;NAS Prompt;Authenticate Only;Callback NAS Prompt;Call Check;Callback Administrative;', 'Network service', 'Divers');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Fall-Through', '1', NULL, 'radgroupreply;', NULL, NULL, 'Divers');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Tunnel-Type', NULL, NULL, 'radgroupreply;', 'Point-to-Point Tunneling Protocol;Layer Two Forwarding;Layer Two Tunneling Protocol;Ascend Tunnel Manaement Protocol;Virtual Tunneling Protocol;IP Authentification Header in the Tunnel-mode;IP-in-IP Encapsulation;Minimal IP-in-IP Encapsulation;IP Encapsul', NULL, 'Tunnel');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Tunnel-Medium-Type', 'IPv4', NULL, 'radgroupreply;', 'IPv4;IPv6;NSAP;HDLC;BBN 1822;802;E.163;E.164;F.69;X.121;IPX;Appletalk;Decnet IV;Banyan Vines; E164 with NSAP format subaddress;', NULL, 'Tunnel');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Tunnel-Password', NULL, NULL, 'radgroupreply;', NULL, NULL, 'Tunnel');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'EAP-Message', NULL, NULL, 'radgroupreply;', NULL, NULL, 'EAP');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Reply-Message', NULL, NULL, 'radgroupreply;', NULL, 'String returned to the user', 'Divers');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'ChilliSpot-MAC-Allowed', NULL, NULL, 'radgroupcheck;radgroupreply;', NULL, 'For allow mac adress auth', 'Chillispot');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'ChilliSpot-UAM-Allowed', NULL, NULL, 'radgroupreply;', NULL, 'When received from the radius server in an RFC 2882 style configuration management message this attribute will override the uamallowed command line option.', 'Chillispot');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'ChilliSpot-Max-Total-Octets', NULL, NULL, 'radgroupreply;radgroupcheck;', NULL, 'Maximum number of octets the user is allowed to transfer (sum of octets transmitted and received). After this limit has been reached the user will be disconnected.', 'Chillispot');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'ChilliSpot-Max-Output-Octets', NULL, NULL, 'radgroupreply;radgroupcheck;', NULL, 'Maximum number of octets the user is allowed to receive. After this limit has been reached the user will be disconnected.', 'Chillispot');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'ChilliSpot-Max-Input-Octets', NULL, NULL, 'radgroupcheck;radgroupreply;', NULL, 'Maximum number of octets the user is allowed to transmit. After this limit has been reached the user will be disconnected.', 'Chillispot');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'WISPr-Session-Terminate-Time', NULL, NULL, 'radgroupreply;', NULL, 'The time when the user should be disconnected in ISO 8601 format (YYYY-MM-DDThh:mm:ssTZD). If TZD is not specified local time is assumed. For example a disconnect on 18 December 2001 at 7:00 PM UTC would be specified as 2001-12-18T19:00:00+00:00.', 'Wispr');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'WISPr-Bandwidth-Max-Down', '4194304', 'bits', 'radgroupreply;', NULL, 'Maximum receive rate (b/s). Limits the bandwidth of the connection. Note that this attribute is specified in bits per second.', 'Wispr');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'WISPr-Bandwidth-Max-Up', '2097152', 'bits', 'radgroupreply;', NULL, 'Maximum transmit rate (b/s). Limits the bandwidth of the connection. Note that this attribute is specified in bits per second.', 'Wispr');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (1, 'WISPr-Redirection-URL', 'http://www.pixpros.net', 'text', 'radgroupreply;', NULL, 'If present the client will be redirected to this URL once authenticated. This URL should include a link to WISPr-Logoff-URL in order to enable the client to log off.', 'Wispr');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'WISPr-Logoff-URL', NULL, NULL, 'radgroupreply;', NULL, 'Chilli includes this attribute in Access-Request messages in order to notify the operator of the log off URL to use for logging off clients. Defaults to \"http://192.168.182.1:3990/logoff\"', 'Wispr');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'WISPr-Location-Name', NULL, NULL, 'radgroupcheck;', NULL, 'Location Name is set to the radiuslocationname option if present. Should be in the format: <HOTSPOT_OPERATOR_NAME>,<LOCATION>', 'Wispr');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'WISPr-Location-ID', NULL, NULL, 'radgroupcheck;', NULL, 'Location ID is set to the radiuslocationid option if present. Should be in the format: isocc=<ISO_Country_Code>, cc=<E.164_Country_Code>,ac=<E.164_Area_Code>,network=<ssid/ZONE>', 'Wispr');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (1, 'Acct-Interim-Interval', '60', 'minutes', 'radgroupreply;', NULL, 'If present in Access-Accept chilli will generate interim accounting records with the specified interval (seconds).', 'Accounting');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'NAS-Port-Type', NULL, NULL, 'radgroupcheck;', 'Wireless-IEEE-802.11;', NULL, 'Nas');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'NAS-ID', NULL, NULL, 'radgroupcheck;', NULL, 'Set to radiusnasid option if present.', 'Nas');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'MS-MPPE-Send-Key', NULL, NULL, 'radgroupreply;', NULL, 'User for wpa', 'Mppe');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'MS-MPPE-Recv-Key', NULL, NULL, 'radgroupreply;', NULL, NULL, 'Mppe');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'Called-Station-ID', NULL, NULL, 'radgroupcheck;', NULL, '	Set to the radiuscalled command line option or the MAC address of ChilliSpot if not present.', 'Divers');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (1, 'Session-Timeout', '7200', 'hours', 'radgroupreply;radgroupcheck;', NULL, 'Logout once session timeout is reached (seconds)', 'Divers');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (1, 'Idle-Timeout', '600', 'minutes', 'radgroupreply;', NULL, 'Logout once idle timeout is reached (seconds)', 'Divers');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'CHAP-Password', NULL, NULL, 'radgroupcheck;', NULL, 'Used for UAM', 'Divers');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'CHAP-Challenge', NULL, NULL, 'radgroupcheck;', NULL, 'Used for UAM', 'Divers');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (0, 'NAS-IP-Address', NULL, NULL, 'radgroupcheck;', NULL, 'IP address of Chilli (set by the radiusnasip or radiuslisten option).IP address of Chilli (set by the radiusnasip or radiuslisten option). If neither radiuslisten nor nasipaddress are set NAS-IP-Address is set to \"0.0.0.0\". ', 'Nas');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (1, 'Max-Daily-Session', '14400', 'hours', 'radgroupreply;radgroupcheck;', NULL, NULL, 'Divers');";
		$f[]="INSERT IGNORE INTO `radattribute` VALUES (1, 'Simultaneous-Use', '1', 'enum', 'radgroupreply;radgroupcheck;', '0;1;', NULL, 'Divers');";		
		while (list ($key, $sql) = each ($f) ){
			$this->QUERY_SQL($sql,"artica_backup");
		}
	}

	

	
	
	if(!$this->TABLE_EXISTS('samba_recycle_bin','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`samba_recycle_bin` (
				`sharename` VARCHAR( 90 ) NOT NULL ,
				`path` VARCHAR( 255 ) NOT NULL ,
				`MaxFilesDay` INT( 2 ) NOT NULL ,
				PRIMARY KEY ( `sharename` ) ,
				INDEX ( `MaxFilesDay` )
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,"artica_backup");
	}

	if(!$this->TABLE_EXISTS('samba_veto_files','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`samba_veto_files` (
				 `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`md5path` VARCHAR( 90 ) NOT NULL ,
				`files` VARCHAR(60) NOT NULL ,
				PRIMARY KEY ( `ID` ) ,
				KEY `md5path` ( `md5path` ) ,
				KEY `files` ( `files` )
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,"artica_backup");
	}		
	
	
	if(!$this->TABLE_EXISTS('samba_recycle_bin_list','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `samba_recycle_bin_list` (
			  `path` varchar(255) NOT NULL,
			  `uid` varchar(128) NOT NULL,
			  `sharename` varchar(50) NOT NULL,
			  `filesize` int(50) NOT NULL,
			  `restore` smallint(1) DEFAULT NULL,
				  `delete` smallint(1) DEFAULT NULL,
				  `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				  PRIMARY KEY (`path`),
				  KEY `uid` (`uid`),
				  KEY `sharename` (`sharename`),
				  KEY `filesize` (`filesize`),
				  KEY `zDate` (`zDate`),
				  KEY `restore` (`restore`),
				  KEY `delete` (`delete`)
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,"artica_backup");
	}

	if(!$this->TABLE_EXISTS('gluster_dirs','artica_backup')){
		$sql="CREATE TABLE IF NOT EXISTS `gluster_dirs` (
			  `directory` varchar(255) NOT NULL,
			  `enabled` smallint(1) NOT NULL,
			  `config` TEXT NOT NULL,
			   PRIMARY KEY (`directory`),
			   KEY `enabled` (`enabled`)
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,"artica_backup");		
		}		
	
	
		
	if(!$this->TABLE_EXISTS('icons_db','artica_backup')){		
				$sql="CREATE TABLE IF NOT EXISTS `icons_db` (
				  `zmd5` varchar(90) NOT NULL,
				  `title` varchar(255) NOT NULL,
				  `text` text NOT NULL,
				  `icon` varchar(40) NOT NULL,
				  `js` varchar(255) NOT NULL,
				  `lang` varchar(10) NOT NULL,
				  `family` varchar(40) NOT NULL,
				  `uid` VARCHAR( 90 ) NOT NULL ,
				  PRIMARY KEY (`zmd5`),
				  KEY `lang` (`lang`,`family`),
				  KEY `title` (`title`),
				  KEY `uid` (`uid`),
				  KEY `family` (`family`),
				  FULLTEXT KEY `title_2` (`title`),
				  FULLTEXT KEY `text` (`text`)
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,"artica_backup");
	}	
	 	
	
	if(!$this->FIELD_EXISTS("samba_recycle_bin_list","restore","artica_backup")){
		$sql="ALTER TABLE `samba_recycle_bin_list` ADD `restore` smallint(1) NOT NULL ,ADD INDEX ( `restore` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}	
	
	if(!$this->FIELD_EXISTS("samba_recycle_bin_list","delete","artica_backup")){
		$sql="ALTER TABLE `samba_recycle_bin_list` ADD `delete` smallint(1) NOT NULL,ADD INDEX ( `delete` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}		
	
	
	if(!$this->TABLE_EXISTS('getent_users','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`getent_users` (
		`uid` VARCHAR( 128 ) NOT NULL ,
		PRIMARY KEY ( `uid` )
		) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,"artica_backup");
	}

	if(!$this->TABLE_EXISTS('getent_groups','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`getent_groups` (
			`group` VARCHAR( 128 ) NOT NULL ,
			`gpid` BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY ( `group` ),
			KEY `gpid` (`gpid`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,"artica_backup");
	}
	
	if(!$this->TABLE_EXISTS('activedirectorygroups','artica_backup')){	
		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`activedirectorygroups` (
			`group` VARCHAR( 128 ) NOT NULL ,
			`gpid` BIGINT UNSIGNED NOT NULL,
			`UsersNum` INT UNSIGNED NOT NULL,
			PRIMARY KEY ( `group` ),
			KEY `gpid` (`gpid`),
			KEY `UsersNum` (`UsersNum`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,"artica_backup");
	}
	
	

	
	if(!$this->TABLE_EXISTS('activedirectoryusers','artica_backup')){	
		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`activedirectoryusers` (
			`uid` VARCHAR( 128 ) NOT NULL ,
			`gpid` BIGINT UNSIGNED NOT NULL,
			KEY `gpid` (`gpid`),
			KEY `uid` (`uid`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,"artica_backup");
	}	
	
	
	if(!$this->FIELD_EXISTS("getent_groups","gpid","artica_backup")){
		$sql="ALTER TABLE `getent_groups` ADD `gpid` BIGINT UNSIGNED NOT NULL ,ADD INDEX ( `gpid` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}	
	
	if(!$this->TABLE_EXISTS('samba_domains_info','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`samba_domains_info` (
				`domain` VARCHAR( 80 ) NOT NULL ,
				`Alt_Name` VARCHAR( 128 ) NOT NULL ,
				`SID` VARCHAR( 90 ) NOT NULL ,
				`AD` INT( 1 ) NOT NULL ,
				`Native` INT( 1 ) NOT NULL ,
				`Primary` INT( 1 ) NOT NULL ,
				INDEX ( `Primary` ) ,
				UNIQUE (`domain`)
				) ENGINE=MYISAM";
		$this->QUERY_SQL($sql,"artica_backup");
	}	
	
	
	if(!$this->TABLE_EXISTS('cgroups_groups','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `cgroups_groups` (
		  `ID` int(5) NOT NULL AUTO_INCREMENT,
		  `groupname` varchar(50) NOT NULL,
		  `group_description` varchar(255) NOT NULL,
		  `memory_limit_in_bytes` int(10) NOT NULL,
		  `memory_force_empty` smallint(1) NOT NULL DEFAULT '0',
		  `memory_memsw_limit_in_bytes` int(10) NOT NULL,
		  `memory_soft_limit_in_bytes` int(10) NOT NULL,
		  `memory_swappiness` smallint(1) NOT NULL DEFAULT '0',
		  `cpu_shares` int(10) NOT NULL,
		  `cpu_rt_runtime_us` int(10) NOT NULL,
		  `cpu_rt_period_us` int(10) NOT NULL,
		  `cpuset_cpus` varchar(60) NOT NULL DEFAULT '0,1,2,3,4,5,6,7,8',
		  PRIMARY KEY (`ID`),
		  UNIQUE KEY `groupname` (`groupname`),
		  KEY `cpu_shares` (`cpu_shares`),
		  KEY `memory_limit_in_bytes` (`memory_limit_in_bytes`)
		) ENGINE=MYISAM";
		$this->QUERY_SQL($sql,"artica_backup");
	} 

	if(!$this->TABLE_EXISTS('nmap_events','artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS `nmap_events` (
			  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			  `subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			  `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `text` text NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `zDate` (`zDate`),
			  KEY `uid` (`uid`)
			) ENGINE=MYISAM";	
			$this->QUERY_SQL($sql,"artica_events");
	}
	
	if(!$this->TABLE_EXISTS('artica_patchs','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`artica_patchs` (
			`patch_number` INT UNSIGNED ,
			`path_explain` TEXT NOT NULL ,
			`filename` varchar(50) NOT NULL,
			`size` BIGINT UNSIGNED,
			`updated` INT( 1 ) NOT NULL DEFAULT '0',
			PRIMARY KEY ( `patch_number` ) ,
			INDEX ( `updated` ,`filename`)
			) ENGINE=MYISAM";	
			$this->QUERY_SQL($sql,"artica_backup");
	}	
	
	
	if(!$this->TABLE_EXISTS('cgroups_processes','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `cgroups_processes` (
				  `process_name` varchar(255) NOT NULL,
				  `groupid` int(10) NOT NULL,
				  `user` varchar(60) NOT NULL,
				  PRIMARY KEY (`process_name`),
				  KEY `groupid` (`groupid`),
				  KEY `user` (`user`)
				) ENGINE=MYISAM;"; 

		$this->QUERY_SQL($sql,"artica_backup");
	}
	
	if(!$this->FIELD_EXISTS("cgroups_processes","user","artica_backup")){
		$sql="ALTER TABLE `cgroups_processes` ADD `user` varchar(60) NOT NULL,ADD INDEX ( `user` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}


	


	


	
	if(!$this->TABLE_EXISTS('postfix_smtp_advrt','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `postfix_smtp_advrt` (
		  `domainname` varchar(255) NOT NULL,
		  `enabled` smallint(1) NOT NULL DEFAULT '1',
		  `params` longtext NOT NULL,
		  `hostname` varchar(255) NOT NULL,
		  `zmd5` varchar(90) NOT NULL,
		  PRIMARY KEY (`zmd5`),
		  KEY `enabled` (`enabled`),
		  KEY `hostname` (`hostname`),
		  KEY `domainname` (`domainname`)
		) ENGINE=MYISAM";
		$this->QUERY_SQL($sql,"artica_backup");
	}	

	
	
	
	$sql="CREATE TABLE IF NOT EXISTS `relay_host` (
		`hostname` varchar(255) NOT NULL,
		`enabled` smallint(1) NOT NULL,
		`relay` varchar(255) NOT NULL,
	    `relay_port` smallint(2) NOT NULL,
		`enabledauth` smallint(1) NOT NULL,
		`lookups` smallint(1) NOT NULL,
		`username` varchar(128) NOT NULL,
		`password` varchar(128) NOT NULL,
		 PRIMARY KEY (`hostname`)
		) ENGINE=MYISAM
	";
	$this->QUERY_SQL($sql,"artica_backup");
	
	
	
	
	$sql="CREATE TABLE IF NOT EXISTS `sender_dependent_relay_host` (
		  `hostname` varchar(255) NOT NULL,
		  `domain` varchar(255) NOT NULL,
		  `enabled` smallint(1) NOT NULL DEFAULT '1',
		  `relay` varchar(255) NOT NULL,
		  `relay_port` smallint(2) NOT NULL,
		  `lookups` smallint(1) NOT NULL,
		  `zOrders` smallint(2) NOT NULL,
		  `directmode` smallint(1) NOT NULL,
		  `override_transport` smallint(1) NOT NULL DEFAULT 0,
		  `override_relay` smallint(1) NOT NULL DEFAULT 0,
		  `enabledauth` smallint(1) NOT NULL,
		  `smtp_bind_address` varchar(90) NULL,
		  `smtp_helo_name` varchar(128) NULL,
		  `syslog_name` varchar(128) NULL,
		  `username` varchar(128) NOT NULL,
		  `password` varchar(128) NOT NULL,			
		  `zmd5` varchar(90) NOT NULL,
		  PRIMARY KEY (`zmd5`),
		  KEY `hostname` (`hostname`),
		  KEY `enabled` (`enabled`),
		  KEY `zOrders` (`zOrders`),
		  KEY `override_transport` (`override_transport`),
		  KEY `override_relay` (`override_relay`),
		  KEY `enabledauth` (`enabledauth`),
		  KEY `domain` (`domain`),
		  KEY `relay` (`relay`),
		  KEY `lookups` (`lookups`),
		  KEY `directmode` (`directmode`)
	) ENGINE=MYISAM";
	$this->QUERY_SQL($sql,"artica_backup");
	
	if(!$this->FIELD_EXISTS("sender_dependent_relay_host","override_transport","artica_backup")){
		$sql="ALTER TABLE `sender_dependent_relay_host` ADD `override_transport` smallint(1) NOT NULL DEFAULT '0',
		ADD INDEX ( `override_transport` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}
	
	if(!$this->FIELD_EXISTS("sender_dependent_relay_host","syslog_name","artica_backup")){
		$sql="ALTER TABLE `sender_dependent_relay_host` ADD `syslog_name` varchar(128)";
		$this->QUERY_SQL($sql,"artica_backup");
	}	
	
	if(!$this->FIELD_EXISTS("sender_dependent_relay_host","smtp_bind_address","artica_backup")){
		$sql="ALTER TABLE `sender_dependent_relay_host` ADD `smtp_bind_address` varchar(90) NULL,
		ADD INDEX ( `smtp_bind_address` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}	
	if(!$this->FIELD_EXISTS("sender_dependent_relay_host","smtp_helo_name","artica_backup")){
		$sql="ALTER TABLE `sender_dependent_relay_host` ADD `smtp_helo_name` varchar(128) NULL,
		ADD INDEX ( `smtp_helo_name` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}	
	if(!$this->FIELD_EXISTS("sender_dependent_relay_host","override_relay","artica_backup")){
		$sql="ALTER TABLE `sender_dependent_relay_host` ADD `override_relay` smallint(1) NOT NULL DEFAULT 0,
		ADD INDEX ( `override_relay` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}	
	
	if(!$this->TABLE_EXISTS('mbxs_backup','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `mbxs_backup` (
		  `uid` varchar(128) NOT NULL,
		  `enabled` smallint(1) NOT NULL DEFAULT '1',
		  `imapserv` varchar(255) NOT NULL,
		  `account` varchar(255) NOT NULL,
		  `config` longtext NOT NULL,
		  `zmd5` varchar(90) NOT NULL,
		  PRIMARY KEY (`zmd5`),
		  KEY `enabled` (`enabled`),
		  KEY `imapserv` (`imapserv`),
		  KEY `account` (`account`)
		) ENGINE=MYISAM";
		$this->QUERY_SQL($sql,"artica_backup");
	}	
	
	if(!$this->TABLE_EXISTS('mbxs_backup','artica_events')){		
		$sql="CREATE TABLE IF NOT EXISTS `mbxs_backup` (
		  `ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
		  `zDate` datetime NOT NULL,
		  `content` longtext NOT NULL,
		  `zmd5` varchar(90) NOT NULL,
		  KEY `zmd5` (`zmd5`),
		  KEY `zDate` (`zDate`)
		) ENGINE=MYISAM";
		$this->QUERY_SQL($sql,"artica_events");
	}	
	
	if(!$this->TABLE_EXISTS('mbxs_backup_storage','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `mbxs_backup_storage` (
		  `filepath` VARCHAR( 255 ) NOT NULL PRIMARY KEY ,
		  `zDate` datetime NOT NULL,
		  `filesize` INT UNSIGNED,
		  `imapserv` varchar(255) NOT NULL,
		  `account` varchar(255) NOT NULL,
		  `zmd5` varchar(90) NOT NULL,
		  KEY `imapserv` (`imapserv`),
		  KEY `zmd5` (`zmd5`),
		  KEY `filesize` (`filesize`),
		  KEY `zDate` (`zDate`)
		) ENGINE=MYISAM";
		$this->QUERY_SQL($sql,"artica_events");
	}	
	
	
	if(!$this->TABLE_EXISTS('postfix_transport_recipients','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `postfix_transport_recipients` (
		  `recipient` varchar(255) NOT NULL PRIMARY KEY,
		  `enabled` smallint(1) NOT NULL DEFAULT '1',
		  `transport` varchar(255) NOT NULL,
		  `hostname` varchar(255) NOT NULL,
		  KEY `enabled` (`enabled`),
		  KEY `hostname` (`hostname`),
		  KEY `transport` (`transport`)
		) ENGINE=MYISAM";
		$this->QUERY_SQL($sql,"artica_backup");
	}
	


	if(!$this->TABLE_EXISTS('postfix_externaldbs','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `postfix_externaldbs` (
		  `ID` int(5) NOT NULL AUTO_INCREMENT,
		  `dbtype` smallint(1) NOT NULL DEFAULT '1',
		   `postfixdb` smallint(1) NOT NULL DEFAULT '0',
		  `enabled` smallint(1) NOT NULL DEFAULT '1',
		  `content` longtext NOT NULL,
		  `hostname` varchar(255) NOT NULL,
		  `dbname` varchar(255) NOT NULL,
		  PRIMARY KEY (`ID`),
		  KEY `enabled` (`enabled`),
		  KEY `hostname` (`hostname`),
		  KEY `dbname` (`dbname`),
		  KEY `dbtype` (`dbtype`)
		) ENGINE=MYISAM";
		$this->QUERY_SQL($sql,"artica_backup");
	}

	

	
	

	

	if(!$this->TABLE_EXISTS('activedirectory_groups','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `activedirectory_groups` (
			  `groupdn` varchar(255) NOT NULL,
			  `userdn` varchar(255) NOT NULL,
			  `mdkey` varchar(90) NOT NULL,
			  `description` varchar(255) NOT NULL,
			  `CountDeUsers` INT UNSIGNED NOT NULL,
			  PRIMARY KEY (`mdkey`),
			  KEY `userdn` (`userdn`),
			  KEY `CountDeUsers` (`CountDeUsers`),
			  KEY `groupdn` (`groupdn`)
			) ENGINE=MYISAM";
			$this->QUERY_SQL($sql,"artica_backup");
	}

	if(!$this->FIELD_EXISTS("activedirectory_groups","description","artica_backup")){
		$sql="ALTER TABLE `activedirectory_groups` ADD `description` VARCHAR( 255 ) NOT NULL ";
		$this->QUERY_SQL($sql,"artica_backup");
	}
	if(!$this->FIELD_EXISTS("activedirectory_groups","CountDeUsers","artica_backup")){
		$sql="ALTER TABLE `activedirectory_groups` ADD `CountDeUsers` INT UNSIGNED ";
		$this->QUERY_SQL($sql,"artica_backup");
	}	
	
		$sql="CREATE TABLE IF NOT EXISTS `activedirectory_groupsNames` (
				  `dn` varchar(255) NOT NULL,
				  `groupname` varchar(128) NOT NULL,
				  `oudn` varchar(255) NOT NULL,
				  `UsersCount` int(5) NOT NULL,
				  `description` VARCHAR( 255 ) NOT NULL,
				  KEY `dn` (`dn`),
				  KEY `oudn` (`oudn`),
				  KEY `groupname` (`groupname`,`UsersCount`)
				) ENGINE=MYISAM";
			$this->QUERY_SQL($sql,"artica_backup");
	
	
	if(!$this->FIELD_EXISTS("activedirectory_groupsNames","description","artica_backup")){
		$sql="ALTER TABLE `activedirectory_groupsNames` ADD `description` VARCHAR( 255 ) NOT NULL ";
		$this->QUERY_SQL($sql,"artica_backup");
	}
	
	
	
	if(!$this->FIELD_EXISTS("activedirectory_groupsNames","oudn","artica_backup")){
		$sql="ALTER TABLE `activedirectory_groupsNames` ADD `oudn` VARCHAR( 255 ) NOT NULL ";
		$this->QUERY_SQL($sql,"artica_backup");
	}	

	if(!$this->FIELD_EXISTS("postfix_externaldbs","dbname","artica_backup")){
		$sql="ALTER TABLE `postfix_externaldbs` ADD `dbname` varchar(255) NOT NULL,ADD INDEX ( `dbname` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}
	if(!$this->FIELD_EXISTS("postfix_externaldbs","postfixdb","artica_backup")){
		$sql="ALTER TABLE `postfix_externaldbs` ADD `postfixdb` smallint(1) NOT NULL,ADD INDEX ( `postfixdb` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}		

	
	if(!$this->TABLE_EXISTS('activedirectory_orgs','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `activedirectory_orgs` (
			  `ou` varchar(80) NOT NULL,
			  `dn` varchar(255) NOT NULL,
			  UNIQUE KEY `dn` (`dn`),
			  KEY `ou` (`ou`)
			) ENGINE=MYISAM";
			$this->QUERY_SQL($sql,"artica_backup");
	} 	
	
	if(!$this->FIELD_EXISTS("activedirectory_orgs","enabled","artica_backup")){
		$sql="ALTER TABLE `activedirectory_orgs` ADD `enabled` smallint(1) NOT NULL DEFAULT '1',ADD INDEX ( `enabled` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}	

	if(!$this->FIELD_EXISTS("activedirectory_orgs","OnlyBranch","artica_backup")){
		$sql="ALTER TABLE `activedirectory_orgs` ADD `OnlyBranch` smallint(1) NOT NULL DEFAULT '0',ADD INDEX ( `OnlyBranch` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}		
	
	
	
	if(!$this->TABLE_EXISTS('fetchmail_debug_execute','artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS `fetchmail_debug_execute` (
			  `ID` int(5) NOT NULL AUTO_INCREMENT,
			  `account_id` int(5) NOT NULL,
			  `zDate` datetime NOT NULL,
			  `subject` VARCHAR(255) NOT NULL,
			  `events` longtext NOT NULL,
			  `PID` int(5) NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `account_id` (`account_id`,`zDate`),
			  KEY `PID` (`PID`),
			  KEY `subject` (`subject`)
			) ENGINE=MYISAM";
			$this->QUERY_SQL($sql,"artica_events");
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	} 
	
	if(!$this->TABLE_EXISTS('speedtests','artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS `speedtests` (
			  `zDate` datetime NOT NULL,
			  `ISP` VARCHAR(255) NOT NULL,
			  `download` FLOAT(10,2),
			  `upload` FLOAT(10,2),
			  KEY `zDate` (`zDate`),
			  KEY `download` (`download`),
			  KEY `upload` (`upload`)
			) ENGINE=MYISAM";
			$this->QUERY_SQL($sql,"artica_events");
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	} 	
	
	
	if(!$this->FIELD_EXISTS("fetchmail_debug_execute","subject","artica_events")){
		$sql="ALTER TABLE `fetchmail_debug_execute` ADD `subject` VARCHAR(255) NOT NULL,ADD INDEX ( `subject` )";
		$this->QUERY_SQL($sql,"artica_events");
	}	
	
	
	if(!$this->TABLE_EXISTS('computers_deploy_tasks','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`computers_deploy_tasks` (
			`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`storage_id` INT( 10 ) NOT NULL ,
			`task_type` INT( 1 ) NOT NULL DEFAULT '1',
			`group_id` INT( 5 ) NOT NULL ,
			`progress` INT( 2 ) NOT NULL ,
			`status` INT( 1 ) NOT NULL DEFAULT '0',
			`events` LONGTEXT NOT NULL ,
			`events_computers` LONGTEXT NOT NULL,
			INDEX ( `storage_id` , `task_type` , `group_id` )) ENGINE=MYISAM";
			$this->QUERY_SQL($sql,"artica_backup");
	} 
	
	$mysql_xapian_builder=new mysql_xapian_builder();
	$mysql_xapian_builder->build();			

	if(class_exists("usersMenus")){
		$users=new usersMenus();
		if($users->POWER_DNS_INSTALLED){
			if($this->TABLE_EXISTS("records","powerdns")){
				$this->FIELD_CHECKS("records", "articasrv", "powerdns",
					"varchar",128,true);
				$this->FIELD_CHECKS("records", "explainthis",
					"powerdns", "varchar",255,true);
				$MyHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
				$sql="ALTER TABLE `records` ALTER `articasrv` SET DEFAULT '$MyHostname'";
				$this->QUERY_SQL($sql);
				$sql="ALTER TABLE `records` ALTER `explainthis` SET DEFAULT ''";
				$this->QUERY_SQL($sql);
				     
			}
		}
	}

	if(!$this->FIELD_EXISTS("computers_deploy_tasks","events_computers","artica_backup")){
		$sql="ALTER TABLE `computers_deploy_tasks` ADD `events_computers` LONGTEXT NOT NULL";
		$this->QUERY_SQL($sql,"artica_backup");
	}
	


	
	if(!$this->TABLE_EXISTS('computers_deploy_tasks_sub','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`computers_deploy_tasks_sub` (
				`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`taskid` INT( 10 ) NOT NULL ,
				`package_id` INT( 10 ) NOT NULL ,
				`status` INT( 1 ) NOT NULL ,
				`progress` INT( 2 ) NOT NULL ,
				`events` LONGTEXT NOT NULL ,
				`uid` VARCHAR( 90 ) NOT NULL ,
				INDEX ( `taskid` , `status` , `progress` , `uid`,`package_id` )) ENGINE=MYISAM";
			$this->QUERY_SQL($sql,"artica_backup");
	}
	
	if(!$this->TABLE_EXISTS('cgroups_stats','artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS `cgroups_stats` (
			  `zmd5` varchar(90) NOT NULL,
			  `zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `structure` varchar(50) NOT NULL,
			  `key` varchar(50) NOT NULL,
			  `value` INT UNSIGNED NOT NULL,
			  `groupname` varchar(80) NOT NULL,
			  UNIQUE KEY `zmd5` (`zmd5`),
			  KEY `zDate` (`zDate`,`value`),
			  KEY `structure` (`structure`),
			  KEY `key` (`key`),
			  KEY `groupname` (`groupname`)
			) ENGINE=MYISAM";
		$this->QUERY_SQL($sql,"artica_events");
	}
	
	
	if(!$this->TABLE_EXISTS('computers_available','artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`computers_available` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
				`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`ipaddr` VARCHAR( 50 ) NOT NULL ,
				`MAC` VARCHAR( 90 ) NOT NULL ,
				`live` SMALLINT( 1 ) NOT NULL ,
				INDEX ( `ipaddr` , `MAC` , `live` )
				) ENGINE=MYISAM";
		$this->QUERY_SQL($sql,"artica_events");
	}
	
	
	if(!$this->TABLE_EXISTS('squidlogs_restores','artica_events')){
		$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`squidlogs_restores` (
				`fullpath` VARCHAR( 255 ) NOT NULL PRIMARY KEY ,
				`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`results` LONGTEXT,
 				INDEX ( `zDate`)
				) ENGINE=MYISAM";
		$this->QUERY_SQL($sql,"artica_events");
	}	
	
	
	if(!$this->TABLE_EXISTS('computers_lastscan','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`computers_lastscan` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
				`MAC` VARCHAR( 90 ) NOT NULL,
				`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`ipaddr` VARCHAR( 50 ) NOT NULL ,
				`hostname` VARCHAR( 128 ) NOT NULL ,
				`Info` TEXT NOT NULL ,
				INDEX ( `ipaddr` , `MAC`,`zDate`),
				KEY `hostname` (`hostname`)
				) ENGINE=MYISAM";
		$this->QUERY_SQL($sql,"artica_backup");
	}		
			
			
	
	if(!$this->TABLE_EXISTS('roundcube_globaladdressbook','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`roundcube_globaladdressbook` (
				`hostname` VARCHAR( 255 ) NOT NULL ,
				`groups` TEXT NOT NULL ,
				`admins` TEXT NOT NULL ,
				`readonly` INT( 1 ) NOT NULL DEFAULT '0',
				`enabled` INT( 1 ) NOT NULL DEFAULT '1',
				UNIQUE (`hostname`)) ENGINE=MYISAM";
			$this->QUERY_SQL($sql,"artica_backup");
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}

	if(!$this->TABLE_EXISTS('samba_usb_share','artica_backup')){	
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`samba_usb_share` (`uuid` VARCHAR( 255 ) NOT NULL ,`share_name` VARCHAR( 255 ) NOT NULL ,`settings`
		 TEXT NOT NULL ,
		 UNIQUE (`uuid` ),
		 UNIQUE(`share_name`)
		 ) ENGINE=MYISAM";
		$this->QUERY_SQL($sql,"artica_backup");
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}	
	}
	
	
	if(!$this->TABLE_EXISTS('samba_remote_announce','artica_backup')){	
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`samba_remote_announce`(  
			 `ID` int(11) NOT NULL AUTO_INCREMENT,
			  `ipaddr` varchar(90) NOT NULL,
			  `domain` varchar(90) NOT NULL,
			  `hostname` varchar(50) NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `hostname` (`hostname`)
			) ENGINE=MYISAM";	
		$this->QUERY_SQL($sql,"artica_backup");
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}	
	}		
	
	
	
		$this->CheckSnort();
	}
	function Check_quarantine_table(){
		if(!$this->TABLE_EXISTS('quarantine','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `quarantine` (
					  `MessageID` varchar(80) NOT NULL,
					  `zDate` datetime NOT NULL,
					  `mailfrom` varchar(255) NOT NULL,
					  `mailfrom_domain` varchar(255) NOT NULL,
					  `mailto` varchar(255) NOT NULL,
					  `subject` varchar(255) NOT NULL,
					  `MessageBody` text NOT NULL,
					  `fullmesg` text NOT NULL,
					  `header` text NOT NULL,
					  `organization` varchar(80) NOT NULL,
					  PRIMARY KEY  (`MessageID`),
					  KEY `mailfrom` (`mailfrom`),
					  KEY `mailfrom_domain` (`mailfrom_domain`),
					  KEY `mailto` (`mailto`),
					  FULLTEXT KEY `MessageBody` (`MessageBody`),  FULLTEXT KEY `header` (`header`)
					  ) ENGINE=MYISAM;";
				
				$this->QUERY_SQL($sql,'artica_backup');
				}
	
		if(!$this->TABLE_EXISTS('smtp_attachments_blocking','artica_backup')){			
			$sql="CREATE TABLE IF NOT EXISTS `smtp_attachments_blocking` (
			  `ID` int(11) NOT NULL AUTO_INCREMENT,
			  `ou` varchar(255) NOT NULL,
			  `IncludeByMime` varchar(90) NOT NULL,
			  `ExcludeByMime` varchar(90) NOT NULL,
			  `IncludeByName` varchar(90) NOT NULL,
			  `ExcludeByName` varchar(90) NOT NULL,
			  `hostname` varchar(255) NOT NULL,
			  PRIMARY KEY (`ID`),
			  	KEY `ou` (`ou`),
			  	KEY `IncludeByMime` (`IncludeByMime`),
			  	KEY `ExcludeByMime` (`ExcludeByMime`),
			  	KEY `IncludeByName` (`IncludeByName`),
			 	KEY `hostname` (`hostname`)
			 	) ENGINE=MYISAM";
			$this->QUERY_SQL($sql,'artica_backup');
			}
			
			if(!$this->FIELD_EXISTS("smtp_attachments_blocking","hostname","artica_backup")){
				$sql="ALTER TABLE `smtp_attachments_blocking` ADD `hostname` VARCHAR( 255 ) NOT NULL ,ADD INDEX ( `hostname` )";
				$this->QUERY_SQL($sql,"artica_backup");
			}			
	

		if(!$this->TABLE_EXISTS('smtpd_sasl_exceptions_networks','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`smtpd_sasl_exceptions_networks` (
			`ID` bigint(100) NOT NULL AUTO_INCREMENT,
			`pattern` VARCHAR( 128 ),
			`allow` smallint( 1 ) NOT NULL DEFAULT 1,
			`enabled` smallint( 1 ) NOT NULL DEFAULT 1,
			 PRIMARY KEY (`ID`),
			 KEY `pattern` (`pattern`),
			 KEY `allow` (`allow`),
			 KEY `enabled` (`enabled`)
			)";
			$this->QUERY_SQL($sql,"artica_backup");
		}		
			
	
	

		
		if(!$this->TABLE_EXISTS('spamassassin_spf_wl','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`spamassassin_spf_wl` (
				`ID` INT( 20 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`domain` VARCHAR( 255 ) NOT NULL ,
				UNIQUE (`domain`))";
			$this->QUERY_SQL($sql,"artica_backup");
		}

		
		
		
		
		
		if(!$this->TABLE_EXISTS('spamassassin_dkim_wl','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`spamassassin_dkim_wl` (
				`ID` INT( 20 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`domain` VARCHAR( 255 ) NOT NULL ,
				UNIQUE (`domain`))";
			$this->QUERY_SQL($sql,"artica_backup");
		}	
		
		if(!$this->TABLE_EXISTS('spamassassin_table','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`spamassassin_table` (
				`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`value` VARCHAR( 255 ) NOT NULL ,
				`spam_type` VARCHAR( 20 ) NOT NULL ,
				`enabled` INT( 1 ) NOT NULL DEFAULT '1',
					KEY  `value` (`value`),
					KEY `spam_type` (`spam_type`), 
					KEY `enabled` (`enabled`)
				)";
				$this->QUERY_SQL($sql,"artica_backup");
				if(!$this->ok){writelogs($this->mysql_error,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);	}
		}

		
	if(!$this->FIELD_EXISTS("quarantine","file_path","artica_backup")){
		$sql="ALTER TABLE `quarantine` ADD `file_path` varchar(255) NOT NULL ,ADD INDEX ( `file_path` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}
	
	if(!$this->FIELD_EXISTS("quarantine","original_messageid","artica_backup")){
		$sql="ALTER TABLE `quarantine` ADD `original_messageid` varchar(200) NOT NULL ,ADD INDEX ( `original_messageid` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}	
	
	if(!$this->FIELD_EXISTS("quarantine","message_size","artica_backup")){
		$sql="ALTER TABLE `quarantine` ADD `message_size` INT(10) NOT NULL";
		$this->QUERY_SQL($sql,"artica_backup");
	}

	if(!$this->FIELD_EXISTS("quarantine","mailto","artica_backup")){
		$sql="ALTER TABLE `quarantine` ADD `mailto` varchar(255) NOT NULL ,ADD INDEX ( `mailto` )";
		$this->QUERY_SQL($sql,"artica_backup");
	}		
	
	if(!$this->FIELD_EXISTS("quarantine","BinMessg","artica_backup")){
		$sql="ALTER TABLE `quarantine` ADD `BinMessg` LONGBLOB NOT NULL";
		$this->QUERY_SQL($sql,"artica_backup");
	}

	if(!$this->FIELD_EXISTS("quarantine","filesize","artica_backup")){
		$sql="ALTER TABLE `quarantine` ADD `filesize` INT( 10 ) NOT NULL 	";
		$this->QUERY_SQL($sql,"artica_backup");
	}	

	if(!$this->FIELD_EXISTS("quarantine","filename","artica_backup")){
		$sql="ALTER TABLE `quarantine` ADD `filename` VARCHAR( 128 ) NOT NULL ,ADD INDEX ( filename )";
		$this->QUERY_SQL($sql,"artica_backup");
	}	

	if(!$this->TABLE_EXISTS('postfix_sender_quotas','artica_backup')){	
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`postfix_sender_quotas` (
		`uid` VARCHAR( 128 ) NOT NULL ,
		`MaxMailsDay` INT( 5 ) NOT NULL ,
		`MaxMailDaySize` INT( 5 ) NOT NULL ,
		`OnlyNotify` INT( 1 ) NOT NULL DEFAULT '0',
		PRIMARY KEY ( `uid` ) ,
		KEY `MaxMailsDay` ( `MaxMailsDay`),
		KEY `MaxMailDaySize` (`MaxMailDaySize`) ,
		KEY `OnlyNotify` (`OnlyNotify`))";
		$this->QUERY_SQL($sql,"artica_backup");
		if(!$this->ok){writelogs($this->mysql_error,__FUNCTION__,__FILE__,__LINE__);}
	}
	
	
	if(!$this->TABLE_EXISTS('postfix_regex_checks','artica_backup')){	
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`postfix_regex_checks` (
		`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
		`pcre` TINYINT( 1 ) NOT NULL DEFAULT '1',
		`pattern` TEXT NOT NULL ,
		`flags` VARCHAR( 50 ) NOT NULL ,
		`action` VARCHAR( 128 ) NOT NULL ,
		`hostname` VARCHAR( 128 ) NOT NULL ,
		`enabled` TINYINT( 1 ) NOT NULL DEFAULT '1',
		`headers` TINYINT( 1 ) NOT NULL DEFAULT '1',
		`xNOTIFY` TINYINT( 1 ) NOT NULL DEFAULT '0',
		INDEX ( `pcre` , `flags` , `action` , `hostname` , `enabled` ,`headers`,`xNOTIFY`)
		)";
		$this->QUERY_SQL($sql,"artica_backup");
		if(!$this->ok){writelogs($this->mysql_error,__FUNCTION__,__FILE__,__LINE__);}
	}

	if(!$this->FIELD_EXISTS("postfix_regex_checks","xNOTIFY","artica_backup")){
		$sql="ALTER TABLE `postfix_regex_checks` ADD `xNOTIFY` TINYINT( 1 ) NOT NULL DEFAULT '0'";
		$this->QUERY_SQL($sql,"artica_backup");
	}		
	
	
	if(!$this->TABLE_EXISTS('smtpd_data_restrictions','artica_backup')){	
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`smtpd_data_restrictions` (
			`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`restriction` VARCHAR( 50 ) NOT NULL ,
			`hostname` VARCHAR( 128 ) NOT NULL ,
			`restrictions_datas` LONGTEXT NOT NULL ,
			`enabled` INT( 1 ) NOT NULL DEFAULT '1',
			`zorder` INT( 2 ) NOT NULL ,
			KEY `hostname`(`hostname` ),
			INDEX ( `restriction` , `enabled` , `zorder` )
			) ";
		$this->QUERY_SQL($sql,"artica_backup");
		if(!$this->ok){writelogs($this->mysql_error,__FUNCTION__,__FILE__,__LINE__);}
	}	

	
	if(!$this->TABLE_EXISTS('postfix_perso_settings','artica_backup')){	
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`postfix_perso_settings` (
			`postfix_key` VARCHAR( 90 ) NOT NULL ,
			`postfix_value` VARCHAR( 255 ) NOT NULL ,
			`postfix_type` VARCHAR( 10 ) NOT NULL ,
			PRIMARY KEY ( `postfix_key` )
			)";
		$this->QUERY_SQL($sql,"artica_backup");
		if(!$this->ok){writelogs($this->mysql_error,__FUNCTION__,__FILE__,__LINE__);}
	} 	

	
		
	}

function Check_iptables_table(){
	
				
			$sql="CREATE TABLE IF NOT EXISTS `unbound_blacklists` ( 
					`hostname` varchar(255) NOT NULL, 
					enabled smallint(1) NOT NULL DEFAULT 1,
					PRIMARY KEY (`hostname`),
					KEY `enabled` (`enabled`)
					) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');			
		
		

		
		
		

		


	

		
		
		if(!$this->TABLE_EXISTS('iscsi_client','artica_backup')){	
		
			  $sql="CREATE TABLE IF NOT EXISTS `iscsi_client` (
			  `ID` int(2) NOT NULL AUTO_INCREMENT,
			  `username` varchar(255) NOT NULL,
			  `password` varchar(255) NOT NULL,
			  `Params` blob NOT NULL,
			  `hostname` varchar(255) NOT NULL,
			  `directory` varchar(255) NOT NULL,
			  `Persistante` INT( 1 ) NOT NULL DEFAULT '0',
			  PRIMARY KEY (`ID`),
			  KEY `directory` (`directory`),
			  KEY `Persistante` (`Persistante`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}	

		
		if(!$this->TABLE_EXISTS('iscsi_params','artica_backup')){	
		
			  $sql="CREATE TABLE IF NOT EXISTS `iscsi_params` (
			  `ID` int(11) NOT NULL AUTO_INCREMENT,
			  `shared_folder` varchar(90) NOT NULL,
			  `hostname` varchar(255) NOT NULL,
			  `dev` varchar(255) NOT NULL,
			  `type` varchar(10) NOT NULL,
			  `file_size` int(2) NOT NULL,
			  `uid` varchar(90) NOT NULL,
			  `EnableAuth` smallint(1) NOT NULL,
			  `Params` blob NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `type` (`type`),
			  KEY `shared_folder` (`shared_folder`),
			  KEY `dev` (`dev`),
			  KEY `uid` (`uid`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}

		if(!$this->TABLE_EXISTS('acl_directories','artica_backup')){	
			  $sql="CREATE TABLE IF NOT EXISTS `acl_directories` (
				  `md5` varchar(90) NOT NULL,
				  `directory` varchar(255) NOT NULL,
				  `chmod` LONGTEXT NOT NULL,
				  `enabled` smallint(1) NOT NULL,
				  `acls` blob NOT NULL,
				  `events` blob NOT NULL,
				  `update` smallint(1) NOT NULL DEFAULT '1',
				  PRIMARY KEY (`md5`),
				  KEY `enabled` (`enabled`),
				  KEY `update` (`update`)
				) ENGINE=MYISAM;";	
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}			  	
	
				

		
		
		$sql="CREATE TABLE IF NOT EXISTS `artica_clusters` (
				  `ID` INT(11) NOT NULL AUTO_INCREMENT,
				  `HOSTNAME` varchar(128) NOT NULL,
				  `LOCALNIC` varchar(40) NOT NULL,
				  `LISTEN_PORT` smallint(3) NOT NULL DEFAULT '9000',
				  `enabled` smallint(1) NOT NULL DEFAULT '1',
				  `USERNAME` varchar(128) NOT NULL,
				  `PASSWORD` TEXT NOT NULL,
				  PRIMARY KEY (`ID`),
				  UNIQUE KEY (`HOSTNAME`),
				  KEY `USERNAME` (`USERNAME`)
				) ENGINE=MYISAM AUTO_INCREMENT=10;";
		$this->QUERY_SQL($sql,'artica_backup');
		
		
		

		

		
		
		

		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `nic_routes` (
				  `zmd5` varchar(90) NOT NULL,
				  `type` smallint(1) NOT NULL,
				  `gateway` varchar(90) NOT NULL,
				  `pattern` varchar(255) NOT NULL,
				  `status` smallint(1) NOT NULL,
				  `nic` varchar(20) NOT NULL,
				 `zOrder` INT(10) NOT NULL,
				  PRIMARY KEY (`zmd5`),
				  KEY `type` (`type`,`status`),
				  KEY `zOrder` (`zOrder`),
				  KEY `nic` (`nic`)
				) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_backup');
		
			
		if(!$this->FIELD_EXISTS("nic_routes", "zOrder", "artica_backup")){
			$this->QUERY_SQL("ALTER TABLE `nic_routes` ADD `zOrder` INT(10) NOT NULL, ADD INDEX (`zOrder`)","artica_backup");
		}
		
		if(!$this->FIELD_EXISTS("nic_routes", "metric", "artica_backup")){
			$this->QUERY_SQL("ALTER TABLE `nic_routes` ADD `metric` INT(10) NOT NULL, ADD INDEX (`metric`)","artica_backup");
		}		
			
		
		if(!$this->TABLE_EXISTS('infected_count','artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS
				`artica_events`.`infected_count` (`zMD5` VARCHAR( 80 ) NOT NULL ,
				`zDate` DATETIME NOT NULL ,
				`daemon` VARCHAR( 20 ) NOT NULL ,
				`event_text` VARCHAR( 255 ) NOT NULL ,
				`virusname` VARCHAR( 80 ) NOT NULL ,
				PRIMARY KEY ( `zMD5` ) , INDEX ( `zDate` , `daemon` , `virusname` )) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,"artica_events");	
		}

		
		if(!$this->TABLE_EXISTS('loadavg','artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS `loadavg` (`stime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,`load` double(100,2) NOT NULL,PRIMARY KEY (`stime`)) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,"artica_events");	
			if(!$q->ok){writelogs("{warning} !!!! $q->mysql_error : $sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			include_once(dirname(__FILE__)."/class.sockets.inc");
			$sock=new sockets();
			if(!is_numeric($sock->GET_INFO("MysqlChangeloadavgDOUBLE"))){
				$sql="ALTER TABLE `loadavg` CHANGE `load` `load` DOUBLE( 100, 2 ) NOT NULL";
				$this->QUERY_SQL($sql,"artica_events");	
				$sock->SET_INFO("MysqlChangeloadavgDOUBLE", 1);
			}
		}
		
		
		if(!$this->TABLE_EXISTS('dhcpd_sharednets','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `dhcpd_sharednets` (
				  `ID` int(11) NOT NULL AUTO_INCREMENT,
				  `sharednet_name` varchar(50) NOT NULL,
				  `scopename` varchar(128) NOT NULL,
				  `subnet` varchar(50) NOT NULL,
				  `netmask` varchar(50) NOT NULL,
				  `range1` varchar(50) NOT NULL,
				  `range2` varchar(50) NOT NULL,
				  `domain-name-servers1` varchar(50) NOT NULL,
				  `domain-name-servers2` varchar(50) NOT NULL,
				  `domain-name` varchar(150) NOT NULL,
				  `routers` varchar(255) NOT NULL,
				  `subnet-mask` varchar(50) NOT NULL,
				  `tftp-server-name` varchar(128) NOT NULL,
				  `server-name` varchar(128) NOT NULL,
				  `next-server` varchar(128) NOT NULL,
				  `pxe_filename` varchar(255) NOT NULL,
				  `pxe_root-path` varchar(255) NOT NULL,
				  `option-176` text NOT NULL,
				  PRIMARY KEY (`ID`),
				  KEY `sharednet_name` (`sharednet_name`,`scopename`,`subnet`),
				  KEY `subnet` (`subnet`),
				  KEY `range1` (`range1`),
				  KEY `range2` (`range2`)
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,"artica_backup");	
		}
		
		if(!$this->FIELD_EXISTS("dhcpd_sharednets","wins-server","artica_backup")){	
			$sql="ALTER TABLE `dhcpd_sharednets` ADD `wins-server` VARCHAR( 50 ) NOT NULL";
			$this->QUERY_SQL($sql,'artica_backup');
		}			

		

		

					
		


		
	}
	
function checkTablesSamba(){
		if(!$this->TABLE_EXISTS('samba_hosts','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `samba_hosts` (
			  `hostname` varchar(60) NOT NULL,
			  `ou` varchar(128) NOT NULL,
			  `RootDir` varchar(255) NOT NULL,
			  `workgroup` varchar(90) NOT NULL,
			  `global_settings` LONGTEXT NOT NULL,
			  PRIMARY KEY (`hostname`),
			  KEY `ou` (`ou`)
			) ";

		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		} 
		
		if(!$this->FIELD_EXISTS("samba_hosts","ipaddr","artica_backup")){	
			$sql="ALTER TABLE `samba_hosts` ADD `ipaddr` VARCHAR( 90 ) NOT NULL ,ADD INDEX ( `ipaddr` )";
			$this->QUERY_SQL($sql,'artica_backup');
		}	
		
		if(!$this->FIELD_EXISTS("samba_hosts","global_settings","artica_backup")){	
			$sql="ALTER TABLE `samba_hosts` ADD `global_settings` LONGTEXT NOT NULL ";
			$this->QUERY_SQL($sql,'artica_backup');
		}			

		
if(!$this->TABLE_EXISTS('samba_hosts_share','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `samba_hosts_share` (
			  `ID` int(10) NOT NULL AUTO_INCREMENT,
			  `share_name` varchar(40) NOT NULL,
			  `share_path` varchar(255) NOT NULL,
			  `share_params` longtext NOT NULL,
			  `enabled` smallint(1) NOT NULL DEFAULT '1',
			  `hostname` varchar(90) NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `share_name` (`share_name`),
			  KEY `hostname` (`hostname`),
			  KEY `share_path` (`share_path`)
			  )";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		} 		

		 
		
	
}

	
function Check_smtp_logs_table(){



	/*if(!$this->TABLE_EXISTS('master_cf','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `master_cf` (
			  `SERVICE_NAME` varchar(50) NOT NULL,
			  `TYPE` varchar(20) NOT NULL,
			  `hostname` varchar(255) NOT NULL,
			  `PRIVATE` varchar(50) NOT NULL,
			  `UNIPRIV` varchar(50) NOT NULL,
			  `CHROOT` varchar(3) NOT NULL,
			  `WAKEUP` varchar(50) NOT NULL,
			  `MAXPROC` varchar(50) NOT NULL,
			  `COMMAND` text NOT NULL,
			  `zmd5` varchar(90) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `SERVICE_NAME` (`SERVICE_NAME`,`PRIVATE`,`UNIPRIV`,`CHROOT`,`WAKEUP`,`MAXPROC`,`TYPE`),
			  KEY `hostname` (`hostname`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}	
	}	

	*/

	
	
	if(!$this->TABLE_EXISTS('mimedefang_disclaimer','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `mimedefang_disclaimer` (
			`zmd5` VARCHAR(90) NOT NULL,
			`mailfrom` VARCHAR(255) NOT NULL,
			`mailto` VARCHAR(255) NOT NULL,
			`textcontent` TEXT NOT NULL,
			`htmlcontent` TEXT NOT NULL,
			 PRIMARY KEY (`zmd5`),
			 KEY `mailfrom` (`mailfrom`),
			 KEY `mailto` (`mailto`)
			 ) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}	
	

	
	
	
	if(!$this->TABLE_EXISTS('mimedefang_autocompress','artica_backup')){
		$sql="CREATE TABLE IF NOT EXISTS `mimedefang_autocompress` (
			`zmd5` VARCHAR(90) NOT NULL,
			`mailfrom` VARCHAR(255) NOT NULL,
			`mailto` VARCHAR(255) NOT NULL,
			`maxsize` INT(5) NOT NULL,
			`uncompress` smallint(1) NOT NULL,
			 PRIMARY KEY (`zmd5`),
			 KEY `mailfrom` (`mailfrom`),
			 KEY `mailto` (`mailto`),
			 KEY `maxsize` (`maxsize`),
			 KEY `uncompress` (`uncompress`)
			 ) ENGINE=MYISAM DEFAULT CHARSET=latin1;";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}	

	
		
		$sql="CREATE TABLE IF NOT EXISTS `mimedefang_extensions` (
			`zmd5` VARCHAR(90) NOT NULL,
			`mailfrom` VARCHAR(255) NOT NULL,
			`mailto` VARCHAR(255) NOT NULL,
			`extensions` TEXT,
			`regexnames` TEXT,
			`notification` TEXT,
			PRIMARY KEY (`zmd5`),
			KEY `mailfrom` (`mailfrom`),
			KEY `mailto` (`mailto`)
			) ENGINE=MYISAM DEFAULT CHARSET=latin1;";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}

				
		
	
	if(!$this->TABLE_EXISTS('mimedefang_filehosting','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `mimedefang_filehosting` (
			`zmd5` VARCHAR(90) NOT NULL,
			`mailfrom` VARCHAR(255) NOT NULL,
			`mailto` VARCHAR(255) NOT NULL,
			`maxsize` INT(5) NOT NULL,
			 PRIMARY KEY (`zmd5`),
			 KEY `mailfrom` (`mailfrom`),
			 KEY `mailto` (`mailto`),
			 KEY `maxsize` (`maxsize`)
			 ) ENGINE=MYISAM DEFAULT CHARSET=latin1;";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}
	
	
	if(!$this->FIELD_EXISTS("mimedefang_autocompress","uncompress","artica_backup")){
		$sql="ALTER TABLE `mimedefang_autocompress` ADD `uncompress` TINYINT( 1 ) NOT NULL, ADD INDEX( `uncompress` )";
		$this->QUERY_SQL($sql,'artica_backup');
	}	
	


	
	
	if(!$this->TABLE_EXISTS('postfinder','artica_events')){	
		
		$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`postfinder` (
				`md5` VARCHAR( 90 ) NOT NULL ,
				`date_start` datetime NOT NULL DEFAULT '1970-01-01 08:00:00' ,
				`date_end` datetime NOT NULL DEFAULT '1970-01-01 08:00:00' ,
				`finish` INT( 1 ) NOT NULL DEFAULT '0',
				`search_datas` LONGTEXT NOT NULL ,
				`pattern` VARCHAR( 255 ) NOT NULL ,
				`msg_num` INT( 2 ) NOT NULL ,
				PRIMARY KEY ( `md5` ) ,
				INDEX ( `date_start` , `date_end` , `finish`,`msg_num` )
				) ENGINE=MYISAM DEFAULT CHARSET=latin1;";
		$this->QUERY_SQL($sql,'artica_events');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	} 

		

	if(!$this->TABLE_EXISTS('postfix_regex_words','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`postfix_regex_words` (
			`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`words` VARCHAR( 255 ) NOT NULL ,
			`hostname` VARCHAR( 255 ) NOT NULL ,
			`enabled` INT( 1 ) NOT NULL DEFAULT '1',
			KEY `words` (`words`),
			KEY `hostname` (`hostname`),
			INDEX(`enabled`)
			) ENGINE=MYISAM DEFAULT CHARSET=latin1;";		
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	} 
	
	
	if(!$this->TABLE_EXISTS('postfix_relais_domains_users','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `postfix_relais_domains_users` (
		  `email` varchar(255) NOT NULL,
		  `password` varchar(255) NOT NULL,
		  `domain` varchar(80) NOT NULL,
		  `ou` varchar(255) NOT NULL,
		  PRIMARY KEY (`email`),
		  KEY `domain` (`domain`),
		  KEY `ou` (`ou`)) ENGINE=MYISAM DEFAULT CHARSET=latin1;";		
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}  	
	
	
	if(!$this->TABLE_EXISTS('amavis_bypass_rcpt','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`amavis_bypass_rcpt` (`pattern` VARCHAR( 255 ) NOT NULL ,UNIQUE (`pattern`))";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}		
	

	if(!$this->TABLE_EXISTS('postfix_duplicate_maps','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `postfix_duplicate_maps` (
			  `pattern` varchar(255)  NOT NULL,
			  `relay` varchar(128)  NOT NULL,
			  `port` int(11) NOT NULL,
			  `ou` varchar(256)  NOT NULL,
			  `nextdomain` varchar(255)  NOT NULL,
			  UNIQUE KEY `pattern` (`pattern`),
			  KEY `relay` (`relay`),
			  KEY `ou` (`ou`)) ENGINE=MYISAM DEFAULT CHARSET=latin1;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}	
	
	
	if(!$this->TABLE_EXISTS('postfix_aliases_domains','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `postfix_aliases_domains` (
			  `alias` varchar(255) NOT NULL,
			  `ou` varchar(128) NOT NULL,
			  `domain` varchar(255) NOT NULL,
			  PRIMARY KEY (`alias`),
			  KEY `ou` (`ou`),
			  KEY `domain` (`domain`)) ENGINE=MYISAM DEFAULT CHARSET=latin1;";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}		
	} 	
	
	if(!$this->TABLE_EXISTS('quotaroot','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`quotaroot` (
			`uid` VARCHAR( 128 ) NOT NULL ,
			`block-softlimit` BIGINT UNSIGNED ,
			`block-hardlimit` BIGINT UNSIGNED ,
			`inode-softlimit` BIGINT UNSIGNED ,
			`inode-hardlimit` BIGINT UNSIGNED ,
			`partition` VARCHAR( 128 ) NOT NULL ,
			`enabled` TINYINT( 1 ) NOT NULL ,
			`GraceTime` INT UNSIGNED ,
			PRIMARY KEY ( `uid` ) ,
			INDEX ( `block-softlimit` , `block-hardlimit` , `inode-softlimit` , `inode-hardlimit` )
			) ENGINE=MYISAM DEFAULT CHARSET=latin1;";
	$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}		
	} 	
	
	if(!$this->FIELD_EXISTS("quotaroot","enabled","artica_backup")){
		$sql="ALTER TABLE `quotaroot` ADD `enabled` TINYINT( 1 ) NOT NULL ";
		$this->QUERY_SQL($sql,'artica_backup');
	}	

	if(!$this->FIELD_EXISTS("quotaroot","GraceTime","artica_backup")){
		$sql="ALTER TABLE `quotaroot` ADD `GraceTime` INT UNSIGNED ";
		$this->QUERY_SQL($sql,'artica_backup');
	}	

	
	
	if(!$this->TABLE_EXISTS('repquota','artica_events')){		
		$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`repquota` (
			`uid` VARCHAR( 128 ) NOT NULL ,
			`blockused` BIGINT UNSIGNED ,
			`filesusers` BIGINT UNSIGNED ,
			`blockLimit` BIGINT UNSIGNED ,
			`Fileslimit` BIGINT UNSIGNED ,
			PRIMARY KEY ( `uid` ) ,
			INDEX ( `blockused` , `filesusers`,`blockLimit`,`Fileslimit` )
			) ENGINE=MYISAM DEFAULT CHARSET=latin1;";
		$this->QUERY_SQL($sql,'artica_events');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	} 

	if(!$this->FIELD_EXISTS("repquota","blockLimit","artica_events")){
		$sql="ALTER TABLE `repquota` ADD `blockLimit` BIGINT UNSIGNED ";
		$this->QUERY_SQL($sql,'artica_events');
	}	

	if(!$this->FIELD_EXISTS("repquota","Fileslimit","artica_events")){
		$sql="ALTER TABLE `repquota` ADD `Fileslimit` BIGINT UNSIGNED ";
		$this->QUERY_SQL($sql,'artica_events');
	}	
	
	
	if(!$this->TABLE_EXISTS('postfix_whitelist_con','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `postfix_whitelist_con` (
			  `ipaddr` varchar(255) NOT NULL,
			  `hostname` varchar(255) NOT NULL,
			  PRIMARY KEY (`ipaddr`),
			  KEY `hostname` (`hostname`))";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	} 

	
 
	

	


	
	if(!$this->TABLE_EXISTS('miltergreylist_objects','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `miltergreylist_objects` (
			  `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			  `instance` varchar(128)  NOT NULL,
			  `type` varchar(50)  NOT NULL,
			  `enabled` smallint(1)  NOT NULL,
			  `objectname` varchar(128)  NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `instance` (`instance`),
			  KEY `type` (`type`),
			  KEY `enabled` (`enabled`),
			  KEY `objectname` (`objectname`)
			) ENGINE=MYISAM DEFAULT CHARSET=latin1;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	} 

	if(!$this->TABLE_EXISTS('miltergreylist_items','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `miltergreylist_items` (
			  `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			  `groupid` BIGINT UNSIGNED NOT NULL,
			  `enabled` smallint(1)  NOT NULL,
			  `item` varchar(128)  NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `groupid` (`groupid`),
			  KEY `enabled` (`enabled`),
			  KEY `item` (`item`)
			) ENGINE=MYISAM DEFAULT CHARSET=latin1;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	} 	
	
	
	if(!$this->TABLE_EXISTS('postfix_global_blacklist','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`postfix_global_blacklist` (
			`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`sender` VARCHAR( 255 ) NOT NULL ,
			`hostname` VARCHAR( 255 ) NOT NULL ,
			`enabled` INT( 1 ) NOT NULL DEFAULT '1',
			`score` INT( 10 ) NOT NULL DEFAULT '0',
			KEY `sender` (`sender`),
			KEY `hostname` (`hostname`),
			INDEX(`enabled`,`score`)
			) ENGINE=MYISAM DEFAULT CHARSET=latin1;";		
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}
	
	if(!$this->FIELD_EXISTS("postfix_global_blacklist","score","artica_backup")){
		$sql="ALTER TABLE `postfix_global_blacklist` ADD `score` INT( 10 ) NOT NULL DEFAULT '0',ADD INDEX ( `score` )";
		$this->QUERY_SQL($sql,'artica_backup');
	}	

	

	
	
	
	if(!$this->TABLE_EXISTS('contacts_whitelist','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`contacts_whitelist` (
			`sender` VARCHAR( 255 ) NOT NULL,
			`uid` VARCHAR( 255 ) NOT NULL,
			`enabled` smallint(1),
			`manual` smallint(1),
			`md5` VARCHAR(90),
			PRIMARY KEY (`md5`),
			KEY `sender` (`sender`),
			KEY `uid` (`uid`),
			KEY `manual` (`manual`),
			INDEX(`enabled`)
			) ENGINE=MYISAM;";		
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}
	
	if(!$this->TABLE_EXISTS('contacts_blacklist','artica_backup')){
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`contacts_blacklist` (
			`sender` VARCHAR( 255 ) NOT NULL,
			`uid` VARCHAR( 255 ) NOT NULL,
			`enabled` smallint(1),
			`manual` smallint(1),
			`Junk` smallint(1),
			`md5` VARCHAR(90),
			PRIMARY KEY (`md5`),
			KEY `sender` (`sender`),
			KEY `uid` (`uid`),
			KEY `manual` (`manual`),
			KEY `Junk` (`Junk`),
			INDEX(`enabled`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}	
	
	if(!$this->FIELD_EXISTS("contacts_blacklist","Junk","artica_backup")){
		$sql="ALTER TABLE `contacts_blacklist` ADD `Junk` smallint( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `Junk` )";
		$this->QUERY_SQL($sql,'artica_backup');
	}
	
		

	
	if(!$this->TABLE_EXISTS('mail_con_err_stats','artica_events')){	
		
		$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`mail_con_err_stats` (
			`zmd5` VARCHAR( 120 ) NOT NULL ,
			`conx` INT( 5 ) NOT NULL ,
			`zweek` INT( 2 ) NOT NULL ,
			`zday` INT( 2 ) NOT NULL ,
			`zhour` INT( 2 ) NOT NULL ,
			`hostname` VARCHAR( 255 ) NOT NULL ,
			`ipaddr` VARCHAR( 40 ) NOT NULL ,
			`smtp_err` VARCHAR( 50 ) NOT NULL ,
			`zDate` DATETIME NOT NULL,
			PRIMARY KEY ( `zmd5` ) ,
			INDEX ( `conx` , `zweek` , `zday` , `zhour` , `ipaddr` , `smtp_err` ),
			KEY `zDate` (`zDate`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_events');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}

		if(!$this->TABLE_EXISTS('redirectmail','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `redirectmail` (
			  `id` int(11) NOT NULL auto_increment,
			  `from` varchar(255) default NULL,
			  `mail` varchar(255) default NULL,
			  `del` smallint(1) NOT NULL default '0',
					  PRIMARY KEY  (`id`),
					  KEY `from` (`from`),
					  KEY `del` (`del`)
					  ) ENGINE=MYISAM;";	

			$this->QUERY_SQL($sql,"artica_backup");
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			
		}
		
		
		if(!$this->TABLE_EXISTS('postfix_viptrack','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`postfix_viptrack` (
			`email` VARCHAR( 128 ) NOT NULL ,
			`enabled` INT( 1 ) NOT NULL DEFAULT '1',
			PRIMARY KEY ( `email` )
			) ENGINE=MYISAM;";	
			$this->QUERY_SQL($sql,"artica_backup");
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			
		}

		
if(!$this->TABLE_EXISTS('viptrack_connections','artica_events')){	
			$sql="CREATE TABLE IF NOT EXISTS `viptrack_connections` (
			  `zmd5` varchar(90) NOT NULL,
			  `domain_from` varchar(128) NOT NULL,
			  `domain_to` varchar(128) NOT NULL,
			  `from` varchar(255) NOT NULL,
			  `to` varchar(255) NOT NULL,
			  `zDate` datetime NOT NULL,
			  `bounce_error` varchar(50) NOT NULL,
			  `smtp_sender` varchar(40) NOT NULL,
			  `Country` varchar(40) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `zDate` (`zDate`,`bounce_error`),
			  KEY `domain_from` (`domain_from`),
			  KEY `domain_to` (`domain_to`),
			  KEY `from` (`from`),
			  KEY `to` (`to`),
			  KEY `smtp_sender` (`smtp_sender`,`Country`)) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,"artica_events");
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			
		}

		
if(!$this->TABLE_EXISTS('viptrack_content','artica_events')){	
			$sql="CREATE TABLE IF NOT EXISTS `viptrack_content` (
				  `zmd5` varchar(90) NOT NULL,
				  `domain_from` varchar(128) NOT NULL,
				  `domain_to` varchar(128) NOT NULL,
				  `from` varchar(255) NOT NULL,
				  `to` varchar(255) NOT NULL,
				  `zDate` datetime NOT NULL,
				  `subject` varchar(255) NOT NULL,
				  `bounce_error` varchar(50) NOT NULL,
				  `smtp_sender` varchar(40) NOT NULL,
				  `Country` varchar(40) NOT NULL,
				  `size` BIGINT UNSIGNED NOT NULL,
				  PRIMARY KEY (`zmd5`),
				  KEY `zDate` (`zDate`,`bounce_error`,`size`),
				  KEY `domain_from` (`domain_from`),
				  KEY `domain_to` (`domain_to`),
				  KEY `subject` (`subject`),
				  KEY `from` (`from`),
				  KEY `to` (`to`),
				  KEY `smtp_sender` (`smtp_sender`,`Country`)
					) ENGINE=MYISAM;";
	$this->QUERY_SQL($sql,"artica_events");
	if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}					
		
if(!$this->TABLE_EXISTS('mails_stats','artica_events')){
		$sql="CREATE TABLE IF NOT EXISTS `mails_stats` (
			  `zmd5` varchar(90) NOT NULL,
			  `zDate` datetime NOT NULL,
			  `instance` varchar(255) NOT NULL,
			  `sender` varchar(255) NOT NULL,
			  `sender_domain` varchar(255) NOT NULL,
			  `recipient` varchar(255) NOT NULL,
			  `recipient_domain` varchar(255) NOT NULL,
			  `sender_ip` varchar(255) NOT NULL,
			  `recipient_ip` varchar(255) NOT NULL,
			  `smtpcode` int(2) NOT NULL,
			  `mailsize` int(5) NOT NULL,
			  `artica_meta` smallint(1) NOT NULL DEFAULT '0',
			  UNIQUE KEY `zmd5` (`zmd5`),
			  KEY `instance` (`instance`),
			  KEY `sender` (`sender`),
			  KEY `sender_domain` (`sender_domain`),
			  KEY `recipient` (`recipient`),
			  KEY `recipient_domain` (`recipient_domain`),
			  KEY `sender_ip` (`sender_ip`),
			  KEY `recipient_ip` (`recipient_ip`),
			  KEY `smtpcode` (`smtpcode`),
			  KEY `artica_meta` (`artica_meta`),
			  KEY `zDate` (`zDate`)
			) ";
			$this->QUERY_SQL($sql,"artica_events");
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}



	if(!$this->TABLE_EXISTS('amavis_event','artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS `amavis_event` (
			  `zDate` datetime NOT NULL,
			  `ID` int(10) NOT NULL AUTO_INCREMENT,
			  `from` varchar(255) NOT NULL,
			  `to` varchar(255) NOT NULL,
			  `subject` varchar(255) NOT NULL,
			  `size` BIGINT UNSIGNED NOT NULL,
			  `bounce_error` varchar(50) NOT NULL,
			  `country` varchar(25) NOT NULL,
			  `city` varchar(30) NOT NULL,
			  `from_domain` varchar(128) NOT NULL,
			  `to_domain` varchar(128) NOT NULL,
			  `ipaddr` varchar(40) NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `size` (`size`,`bounce_error`),
			  KEY `zDate` (`zDate`),
			  KEY `from` (`from`),
			  KEY `to` (`to`),
			  KEY `bounce_error` (`bounce_error`),
			  KEY `country` (`country`),
			  KEY `city` (`city`),
			  KEY `from_domain` (`from_domain`),
			  KEY `to_domain` (`to_domain`),
			  KEY `ipaddr` (`ipaddr`)) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,"artica_events");
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}
	
	
if(!$this->TABLE_EXISTS('amavis_event_hours','artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS `amavis_event_hours` (
				 `zmd5` VARCHAR( 90 ), 
				  `zDate` date NOT NULL,
				  `hour` int(2) NOT NULL,
				  `domain_from` varchar(128) NOT NULL,
				  `domain_to` varchar(128) NOT NULL,
				  `size` BIGINT UNSIGNED NOT NULL,
				  `messages` int(10) NOT NULL,
				  `bounce_error` varchar(50) NOT NULL,
				  `country` VARCHAR( 20 ) NOT NULL ,
				  `ipaddr` VARCHAR( 40 ),
				  PRIMARY KEY  (`zmd5`),
				  KEY `zDate` (`zDate`,`hour`,`size`,`messages`),
				  KEY `domain_from` (`domain_from`),
				  KEY `domain_to` (`domain_to`),
				  KEY `country` (`country`),
				  KEY `ipaddr` (`ipaddr`),
				  KEY `bounce_error` (`bounce_error`)
				  ) ENGINE=MYISAM;"; 	
				$this->QUERY_SQL($sql,"artica_events");
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}

	if(!$this->TABLE_EXISTS('postfix_bad_domains','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `postfix_bad_domains` (
				  `domain` varchar(128) NOT NULL,
				  `hostname` varchar(255) NOT NULL,
				  PRIMARY KEY (`domain`),
				  KEY `hostname` (`hostname`)
				)  ENGINE=MYISAM;"; 	
				$this->QUERY_SQL($sql,"artica_backup");
				if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}	
	
	
	
			

		
	if(!$this->TABLE_EXISTS('bandwith_stats','artica_events')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`bandwith_stats` (
				`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`zDate` DATETIME NOT NULL ,
				`bandwith` BIGINT( 10 ) NOT NULL ,
				KEY `zDate` (`zDate`),
				KEY `bandwith` (`bandwith`)
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,"artica_events");
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
	if(!$this->TABLE_EXISTS('picasa','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `picasa` (
				  `uid` varchar(200) NOT NULL,
				  `enabled` smallint(1) NOT NULL default '0',
				  `username` varchar(150) NOT NULL,
				  `password` varchar(150) NOT NULL,
				  `localfolder` varchar(255) NOT NULL,
				  `album_name` varchar(50) NOT NULL,
				  `ByPassProxy` smallint(1) NOT NULL default '1',
				  `CreateAlbum` smallint(1) NOT NULL default '0',
				  PRIMARY KEY  (`uid`)
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,"artica_backup");
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}

		$this->CheckTablesAwstats();
	}

public function CheckTablesAwstats(){
	
	if(!$this->TABLE_EXISTS('awstats_files','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`awstats_files` (
			`servername` VARCHAR( 255 ) NOT NULL ,
			`awstats_file` VARCHAR( 80 ) NOT NULL ,
			`content` BLOB NOT NULL ,
			KEY `servername` (`servername`),
			KEY `awstats_file` (`awstats_file`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,"artica_backup");
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}	
	}

	
	if(!$this->TABLE_EXISTS('awstats','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`awstats` (`website` VARCHAR( 255 ) NOT NULL ,
			`key` VARCHAR( 100 ) NOT NULL ,`value` VARCHAR( 255 ) NOT NULL ,
			KEY `website` (`website`),
			KEY `key` (`key`),
			KEY `value` (`value`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,"artica_backup");
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}	
	
}



public function CheckTablesPostfilter(){
		if(!$this->DATABASE_EXISTS("postfilter")){$this->CREATE_DATABASE("postfilter");}
		if(!$this->DATABASE_EXISTS("postfilter")){return false;}
		
		if(!$this->TABLE_EXISTS('spamtrap','postfilter')){
			$sql="CREATE TABLE spamtrap (
				_key CHAR(64) NOT NULL default '',
				_value CHAR(32) NOT NULL default '',
				_timestamp TIMESTAMP,
				UNIQUE INDEX (_key)
			) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
	
		}

	if(!$this->TABLE_EXISTS('dha','postfilter')){
			$sql="CREATE TABLE dha (
					_key CHAR(64) NOT NULL default '',
					_value CHAR(32) NOT NULL default '',
					_timestamp TIMESTAMP,
					UNIQUE INDEX (_key)
				) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
	
		}

	if(!$this->TABLE_EXISTS('greylist_white','postfilter')){
			$sql="CREATE TABLE greylist_white (
				_key CHAR(64) NOT NULL default '',
				_value CHAR(32) NOT NULL default 'auto-whitelisted',
				_timestamp TIMESTAMP,
				UNIQUE INDEX (_key)
			) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
	
		}			
		
	if(!$this->TABLE_EXISTS('greylist','postfilter')){
			$sql="CREATE TABLE greylist (
				_sender CHAR(64) NOT NULL default '',
				_recipient CHAR(64) NOT NULL default '',
				_ip char(40) NOT NULL default '',
				_last_seen TIMESTAMP,
				_first_seen TIMESTAMP,
				_count SMALLINT UNSIGNED NOT NULL default 0,
				UNIQUE KEY _ip (_ip,_sender,_recipient),
				KEY _last_seen (_last_seen),
				KEY _first_seen (_first_seen)
			) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
	
		}
		
		if(!$this->TABLE_EXISTS('honeypot','postfilter')){
			$sql="CREATE TABLE honeypot (
					_key CHAR(64) NOT NULL default '',
					_value CHAR(32) NOT NULL default '',
					_timestamp TIMESTAMP,
					UNIQUE INDEX (_key)
				) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
	
		}		
		
		

		if(!$this->TABLE_EXISTS('throttle','postfilter')){
			$sql="CREATE TABLE throttle (
					_address CHAR(64) NOT NULL default '',
					_count_max MEDIUMINT UNSIGNED NOT NULL default 0,
					_count_curr MEDIUMINT UNSIGNED NOT NULL default 1,
					_rcpt_max MEDIUMINT UNSIGNED NOT NULL default 0,
					_rcpt_curr MEDIUMINT UNSIGNED NOT NULL default 1,
					_quota_max INT UNSIGNED NOT NULL default 0,
					_quota_curr INT UNSIGNED NOT NULL default 0,
					_disabled INT UNSIGNED NOT NULL default 0,
					_date INT UNSIGNED NOT NULL default 0,
					_time_period MEDIUMINT UNSIGNED NOT NULL default 0,
					_priority TINYINT UNSIGNED NOT NULL default 0,
					UNIQUE KEY (_address)
				) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
	
		}
		
		if(!$this->TABLE_EXISTS('captcha','postfilter')){
			$sql="CREATE TABLE captcha (
				_sender CHAR(64) NOT NULL DEFAULT '',
				_recipient CHAR(64) NOT NULL DEFAULT '',
				_captcha CHAR(32),
				_timestamp TIMESTAMP,
				_try TIMESTAMP,
				_probes TINYINT UNSIGNED DEFAULT 0,
				_used TINYINT UNSIGNED DEFAULT 0,
				UNIQUE INDEX (_sender,_recipient)
			) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
	
		}	
		
		if(!$this->TABLE_EXISTS('cookie','postfilter')){
			$sql="CREATE TABLE cookie (
				_address CHAR(64) NOT NULL DEFAULT '',
				_cookie CHAR(32),
				_timestamp TIMESTAMP,
				UNIQUE INDEX (_address)
			) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
	
		}	

		if(!$this->TABLE_EXISTS('banned_client','postfilter')){
			$sql="CREATE TABLE banned_client (
				_key CHAR(64) NOT NULL default '',
				_value CHAR(32) NOT NULL default '',
				_timestamp TIMESTAMP,
				UNIQUE INDEX (_key)
			) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
	
		}

		if(!$this->TABLE_EXISTS('banned_sender','postfilter')){
			$sql="CREATE TABLE banned_sender (
					_key CHAR(64) NOT NULL default '',
					_value CHAR(32) NOT NULL default '',
					_timestamp TIMESTAMP,
					UNIQUE INDEX (_key)
				) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
	
		}	

		if(!$this->TABLE_EXISTS('banned_helo','postfilter')){
			$sql="CREATE TABLE banned_helo (
				_key CHAR(64) NOT NULL default '',
				_value CHAR(32) NOT NULL default '',
				_timestamp TIMESTAMP,
				UNIQUE INDEX (_key)
			) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
	
		}	

		if(!$this->TABLE_EXISTS('blacklist','postfilter')){
			$sql="CREATE TABLE blacklist (
				_key CHAR(64) NOT NULL default '',
				_value CHAR(32) NOT NULL default '',
				_timestamp TIMESTAMP,
				UNIQUE INDEX (_key)
			) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
	
		}	

		if(!$this->TABLE_EXISTS('whitelist','postfilter')){
			$sql="CREATE TABLE whitelist (
				_key CHAR(64) NOT NULL default '',
				_value CHAR(32) NOT NULL default '',
				_timestamp TIMESTAMP,
				UNIQUE INDEX (_key)
			) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
	
		}	

		if(!$this->TABLE_EXISTS('helo','postfilter')){
			$sql="CREATE TABLE helo (
				_key CHAR(64) NOT NULL default '',
				_value CHAR(32) NOT NULL default '',
				_timestamp TIMESTAMP,
				UNIQUE INDEX (_key)
			) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
	
		}

		if(!$this->TABLE_EXISTS('user_whitelist','postfilter')){
			$sql="CREATE TABLE user_whitelist (
				_userid INT UNSIGNED NOT NULL,
				_address CHAR(64) NOT NULL default '',
				_type BOOL DEFAULT 0,
				_timestamp TIMESTAMP,
				INDEX (_userid),
				UNIQUE INDEX (_userid, _address)
			) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
	
		}
		
		if(!$this->TABLE_EXISTS('user','postfilter')){
			$sql="CREATE TABLE user (
				_userid INT UNSIGNED NOT NULL AUTO_INCREMENT,
				_master INT UNSIGNED NOT NULL DEFAULT 0,
				_address CHAR(64) NOT NULL default '',
				_passwd CHAR(40) DEFAULT '',
				_type BOOL DEFAULT 0,
				_mode BOOL DEFAULT 0,
				_hidden BOOL DEFAULT 0,
				_filter CHAR(255) NOT NULL DEFAULT 'permit',
				_lang CHAR(2) DEFAULT '',
				PRIMARY KEY (_userid),
				INDEX (_master),
				UNIQUE INDEX (_address)
			) ENGINE=MYISAM;	"; 
			$this->QUERY_SQL($sql,'postfilter');
			$sql="ALTER TABLE user AUTO_INCREMENT=1;";
			$this->QUERY_SQL($sql,'postfilter');
		}	

			if(!$this->TABLE_EXISTS('config','postfilter')){
			$sql="CREATE TABLE config (
				_version INT UNSIGNED NOT NULL AUTO_INCREMENT,
				_config MEDIUMTEXT,
				_timestamp TIMESTAMP,
				UNIQUE INDEX (_version)
			) ENGINE=MYISAM"; 
			$this->QUERY_SQL($sql,'postfilter');
		}

			if(!$this->TABLE_EXISTS('report','postfilter')){
			$sql="CREATE TABLE report (
				_sender CHAR(64),
				_recipient CHAR(64) NOT NULL DEFAULT '',
				_client_name CHAR(128),
				_client_address CHAR(40),
				_username CHAR(64) NOT NULL DEFAULT '',
				_class CHAR(32),
				_policy CHAR(32),
				_report BOOL DEFAULT 1,
				_timestamp TIMESTAMP,
				INDEX (_recipient)
			) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
		}			

			if(!$this->TABLE_EXISTS('statistics','postfilter')){
			$sql="CREATE TABLE statistics (
				_entry CHAR(64) NOT NULL DEFAULT '',
				_type CHAR(64) NOT NULL DEFAULT '',
				_info CHAR(128) NOT NULL DEFAULT '',
				_count INT UNSIGNED NOT NULL DEFAULT 0,
				UNIQUE KEY (_entry, _type)
			) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'postfilter');
		}

		return true;
	}
	
	public function checkRoundCubeTables($database){
		return;
		$posix_getuid=posix_getuid();
		if($posix_getuid==0){echo "Starting......: ".date("H:i:s")." roundcube checking $database.contactgroupmembers\n";}
		if(!$this->TABLE_EXISTS('contactgroupmembers',$database)){
			if($posix_getuid==0){echo "Starting......: ".date("H:i:s")." roundcube $database.contactgroupmembers did not exists (create it)\n";}
			
		$sql="CREATE TABLE IF NOT EXISTS `contactgroupmembers` (
  `contactgroup_id` int(10) UNSIGNED NOT NULL,
  `contact_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  PRIMARY KEY (`contactgroup_id`, `contact_id`),
  CONSTRAINT `contactgroup_id_fk_contactgroups` FOREIGN KEY (`contactgroup_id`)
    REFERENCES `contactgroups`(`contactgroup_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `contact_id_fk_contacts` FOREIGN KEY (`contact_id`)
    REFERENCES `contacts`(`contact_id`) ON DELETE CASCADE ON UPDATE CASCADE) 
	";
		$this->QUERY_SQL($sql,$database);
		if(!$this->ok){
			writelogs($this->mysql_error,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			if($posix_getuid==0){echo "Starting......: ".date("H:i:s")." roundcube $this->mysql_error\n";}}
		}else{
			if($posix_getuid==0){echo "Starting......: ".date("H:i:s")." roundcube $database.contactgroupmembers exists\n";}
		}
		
		if($posix_getuid==0){echo "Starting......: ".date("H:i:s")." roundcube checking $database.contactgroups\n";}
		if(!$this->TABLE_EXISTS('contactgroups',$database)){
			if($posix_getuid==0){echo "Starting......: ".date("H:i:s")." roundcube $database.contactgroups did not exists (create it)\n";}
			$sql="CREATE TABLE IF NOT EXISTS `contactgroups` (
			  `contactgroup_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
			  `changed` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
			  `del` smallint(1) NOT NULL DEFAULT '0',
			  `name` varchar(128) NOT NULL DEFAULT '',
			  PRIMARY KEY(`contactgroup_id`),
			  CONSTRAINT `user_id_fk_contactgroups` FOREIGN KEY (`user_id`)
			    REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
			  INDEX `contactgroups_user_index` (`user_id`,`del`)
			)";

		
			$this->QUERY_SQL($sql,$database);
			if(!$this->ok){
				writelogs($this->mysql_error,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				if($posix_getuid==0){echo "Starting......: ".date("H:i:s")." roundcube $this->mysql_error\n";}}
		}else{
			if($posix_getuid==0){echo "Starting......: ".date("H:i:s")." roundcube $database.contactgroups exists\n";}
		}
		
		if($posix_getuid==0){echo "Starting......: ".date("H:i:s")." roundcube checking identities.contactgroups\n";}
		if(!$this->FIELD_EXISTS("identities","changed",$database)){	
			if($posix_getuid==0){echo "Starting......: ".date("H:i:s")." roundcube $database.identities.changed did not exists (create it)\n";}
			$sql="ALTER TABLE `identities` ADD INDEX `user_identities_index` (`user_id`, `del`);";	
			$this->QUERY_SQL($sql,$database);
			if(!$this->ok){if($posix_getuid==0){echo "Starting......: ".date("H:i:s")." roundcube $this->mysql_error\n";}}
			$sql="ALTER TABLE `identities` ADD `changed` datetime NOT NULL DEFAULT '1000-01-01 00:00:00' AFTER `user_id`;";
			$this->QUERY_SQL($sql,$database);
			if(!$this->ok){if($posix_getuid==0){echo "Starting......: ".date("H:i:s")." roundcube $this->mysql_error\n";}}
		}
		
		if(!$this->TABLE_EXISTS('notes',$database)){	
					$sql="CREATE TABLE IF NOT EXISTS `notes` (
					  `id` int(11) NOT NULL auto_increment,
					  `nid` int(11) NOT NULL,
					  `width` int(11) default '0',
					  `height` int(11) default '0',
					  `pos_x` int(11) default '0',
					  `pos_y` int(11) default '0',
					  `text` text,
					  `user_id` int(10) unsigned NOT NULL,
					  `timestamp` timestamp NOT NULL default '0000-00-00 00:00:00',
					  PRIMARY KEY  (`id`),
					  KEY `user_id` (`user_id`)
					) ENGINE=MYISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
					$this->QUERY_SQL($sql,$database);
					if(!$this->ok){if($posix_getuid==0){echo "Starting......: ".date("H:i:s")." roundcube notes table: $this->mysql_error\n";}}
		
					$sql="ALTER TABLE `notes` ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;";		
					if(!$this->ok){if($posix_getuid==0){echo "Starting......: ".date("H:i:s")." roundcube notes table: $this->mysql_error\n";}}
			}
		
		if($posix_getuid==0){echo "Starting......: ".date("H:i:s")." roundcube checking done\n";}
	}
	public function checkAgentTables(){
			if(!$this->TABLE_EXISTS('computers_drives','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`computers_drives` (
				`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`MAC` VARCHAR( 25 ) NOT NULL ,
				`letter` VARCHAR( 1 ) NOT NULL ,
				`path` VARCHAR( 255 ) NOT NULL ,
				INDEX ( `MAC` , `letter` , `path` )
				) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,'artica_backup');
			}
			
			if(!$this->TABLE_EXISTS('computers_tasks','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `computers_tasks` (
					  `ID` int(11) NOT NULL AUTO_INCREMENT,
					  `MAC` varchar(60) NOT NULL,
					  `task_type` varchar(20) NOT NULL,
					  `schedule` varchar(80) NOT NULL,
					  `task_enabled` smallint(1) NOT NULL,
					  `path` varchar(255) NOT NULL,
					  PRIMARY KEY (`ID`),
					  KEY `MAC` (`MAC`,`task_type`,`task_enabled`)
					) ENGINE=MYISAM;";	
			$this->QUERY_SQL($sql,'artica_backup');
			}
			
			
			if(!$this->TABLE_EXISTS('computers_tasks','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`computers_events` (
				`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`MAC` VARCHAR( 80 ) NOT NULL ,
				`zDate` DATETIME NOT NULL ,
				`events` TEXT NOT NULL ,
				`events_type` VARCHAR( 60 ) NOT NULL ,
				INDEX ( `MAC` , `zDate` , `events_type` )
				) ENGINE=MYISAM;";	
				$this->QUERY_SQL($sql,'artica_backup'); 
			}
			
		if(!$this->TABLE_EXISTS('computers_orders','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`computers_orders` (
						`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`taskid` INT( 10 ) NOT NULL ,
						`task_type` VARCHAR( 20 ) NOT NULL ,
						`parameters` TEXT NOT NULL ,
						`MAC` VARCHAR( 80 ) NOT NULL ,
						INDEX ( `taskid` , `task_type` , `MAC` )
						)";	
				$this->QUERY_SQL($sql,'artica_backup'); 
			}
			
			
		if(!$this->TABLE_EXISTS('computers_parameters','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`computers_parameters` (
				`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`MAC` VARCHAR( 50 ) NOT NULL ,
				`parameters` TEXT NOT NULL ,
				INDEX ( `MAC` )
				) ENGINE=MYISAM;";	
				$this->QUERY_SQL($sql,'artica_backup'); 
			}	
	}
	

	
	
	
	
	
	public function CheckTableWebsites(){}
	public function CheckTableVirus(){}

	public function check_emailing_tables(){
		if(posix_getuid()==0){$ASROOT=true;}
		if(!$this->TABLE_EXISTS('emailing_db_paths','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `emailing_db_paths` (
			  `ID` int(11) NOT NULL AUTO_INCREMENT,
			  `filepath` varchar(255) NOT NULL,
			  `databasename` varchar(255) NOT NULL,
			  `progress` int(2) NOT NULL,
			  `finish` smallint(1) NOT NULL DEFAULT '0',
			  `reports_import` text NOT NULL,
			  `ou` varchar(255) NOT NULL,
			  `zDate` datetime NOT NULL,
			  `progress_export` int(11) NOT NULL,
			  `options` TINYTEXT NOT NULL,
			  `merged` INT( 1 ) NOT NULL,
			  PRIMARY KEY (`ID`),
			  UNIQUE KEY `databasename` (`databasename`),
			  KEY `finish` (`finish`),
			  KEY `ou` (`ou`),
			  KEY `zDate` (`zDate`)
			) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,'artica_backup');
		}
		
		if(!$this->FIELD_EXISTS("emailing_db_paths","zDate","artica_backup")){
				$sql="ALTER TABLE `emailing_db_paths` ADD `zDate` datetime NOT NULL,ADD INDEX ( `zDate` )";
				$this->QUERY_SQL($sql,'artica_backup');
		}
		
		if(!$this->FIELD_EXISTS("emailing_db_paths","merged","artica_backup")){
			$sql="ALTER TABLE `emailing_db_paths` ADD `merged` INT( 1 ) NOT NULL ,ADD INDEX ( `merged` )";
			$this->QUERY_SQL($sql,'artica_backup');
			}

		if(!$this->FIELD_EXISTS("emailing_db_paths","progress_export","artica_backup")){
				$sql="ALTER TABLE `emailing_db_paths` ADD `progress_export` int(11) NOT NULL";
				$this->QUERY_SQL($sql,'artica_backup');
		}	

		if(!$this->FIELD_EXISTS("emailing_db_paths","options","artica_backup")){
				$sql="ALTER TABLE `emailing_db_paths` ADD `options` TINYTEXT NOT NULL";
				$this->QUERY_SQL($sql,'artica_backup');
		}

		if(!$this->TABLE_EXISTS('emailing_templates','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`emailing_templates` (
					`ID` INT( 2 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`zdate` DATETIME NOT NULL ,
					`template_name` VARCHAR( 255 ) NOT NULL ,
					`template_datas` longtext NOT NULL,
					`ou` VARCHAR( 255 ) NOT NULL ,
					`template_css` longtext NOT NULL,
					`subject` VARCHAR( 255 ) NOT NULL,
					 `advopts` MEDIUMTEXT NOT NULL,
					 KEY `ou` (`ou`)
				)";		
			$this->QUERY_SQL($sql,'artica_backup');
		}
		
	if(!$this->TABLE_EXISTS('emailing_tbl_files','artica_backup')){			
		$sql="CREATE TABLE emailing_tbl_files (
		     ID tinyint(3) unsigned NOT NULL auto_increment,
		     bin_data longblob NOT NULL,
		     template_id INT( 10 ) NOT NULL,
		     filename varchar(50) NOT NULL,
		     filesize varchar(50) NOT NULL,
		     filetype varchar(50) NOT NULL,
		     PRIMARY KEY (ID),
		     KEY `template_id` (`template_id`))";		
			$this->QUERY_SQL($sql,'artica_backup');	
		}
		
		
		if(!$this->TABLE_EXISTS('openldap_proxy','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `openldap_proxy` (
			`ID` BIGINT UNSIGNED NOT NULL auto_increment,
			`hostname` VARCHAR(255) NOT NULL,
			`port` smallint(5) NOT NULL,
			`username` VARCHAR(128) NOT NULL,
			`password` VARCHAR(128) NOT NULL,
			`suffix` VARCHAR(255) NOT NULL,
			`suffixlink` VARCHAR(255) NOT NULL,
			`enabled` smallint(1) NOT NULL,
			`cnxstring` VARCHAR(255) NOT NULL,
			`articabranch` smallint(1) NOT NULL,
			`params` TEXT NOT NULL,
			 PRIMARY KEY (`ID`),
			 KEY `hostname` (`hostname`),
			 KEY `port` (`port`),
			 KEY `enabled` (`enabled`),
			 KEY `articabranch` (`articabranch`),
			 KEY `cnxstring` (`cnxstring`)
			 )";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}
	if(!$this->TABLE_EXISTS('openldap_proxyattrs','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `openldap_proxyattrs` (
			`ID` BIGINT UNSIGNED NOT NULL auto_increment,
			`proxyid` BIGINT UNSIGNED NOT NULL,
			`attribute` VARCHAR(90) NOT NULL,
			`type` VARCHAR(90) NOT NULL,
			`match` VARCHAR(90) NOT NULL,
			 PRIMARY KEY (`ID`),
			 KEY `attribute` (`attribute`),
			 KEY `type` (`type`),
			 KEY `match` (`match`),
			 KEY `proxyid` (`proxyid`)
			 )";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}
	
	
	
	if(!$this->FIELD_EXISTS("openldap_proxy","suffix","artica_backup")){
		$sql="ALTER TABLE `openldap_proxy` ADD `suffix` VARCHAR( 255 ) NOT NULL";
		$this->QUERY_SQL($sql,'artica_backup');
	}	
	if(!$this->FIELD_EXISTS("openldap_proxy","cnxstring","artica_backup")){
		$sql="ALTER TABLE `openldap_proxy` ADD `cnxstring` VARCHAR( 255 ) NOT NULL, ADD INDEX (`cnxstring`)";
		$this->QUERY_SQL($sql,'artica_backup');
	}	
	if(!$this->FIELD_EXISTS("openldap_proxy","articabranch","artica_backup")){
		$sql="ALTER TABLE `openldap_proxy` ADD `articabranch` smallint( 1 ) NOT NULL, ADD INDEX (`articabranch`)";
		$this->QUERY_SQL($sql,'artica_backup');
	}	
	if(!$this->FIELD_EXISTS("openldap_proxy","suffixlink","artica_backup")){
		$sql="ALTER TABLE `openldap_proxy` ADD `suffixlink` VARCHAR( 255 ) NOT NULL";
		$this->QUERY_SQL($sql,'artica_backup');
	}	
		
	if(!$this->TABLE_EXISTS('emailing_mailers','artica_backup')){			
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`emailing_mailers` (
					`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`smtpserver` VARCHAR( 255 ) NOT NULL ,
					`parameters` LONGTEXT NOT NULL ,
					`ou` VARCHAR( 255 ) NOT NULL ,
					INDEX ( `ou` ))";
			$this->QUERY_SQL($sql,'artica_backup');	
		}
		
	if(!$this->TABLE_EXISTS('emailing_campain_linker','artica_backup')){			
			$sql="CREATE TABLE IF NOT EXISTS `emailing_campain_linker` (
				  `ID` int(5) NOT NULL AUTO_INCREMENT,
				  `template_id` int(5) NOT NULL,
				  `database_id` int(5) NOT NULL,
				  `parameters` longtext NOT NULL,
				  `finish_queue_processing` smallint(1) NOT NULL DEFAULT '0',
				  `queue_builder_pourc` INT( 1 ) NOT NULL DEFAULT '0',
				  `zdate` datetime NOT NULL,
				  `name` varchar(255) NOT NULL,
				  `ou` varchar(255) NOT NULL,
				  `locked` INT( 1 ) NOT NULL DEFAULT '0',
				  PRIMARY KEY (`ID`),
				  KEY `template_id` (`template_id`),
				  KEY `database_id` (`database_id`),
				  KEY `finish_queue_processing` (`finish_queue_processing`),
				  KEY `ou` (`ou`)
				) ";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("emailing_campain_linker.artica_backup -> $this->mysql_error\n\n$sql\n",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}	
	}

	if(!$this->TABLE_EXISTS('emailing_campain_imap','artica_backup')){			
			$sql="CREATE TABLE IF NOT EXISTS `emailing_campain_imap` (
			  `account_name` varchar(255) NOT NULL,
			  `parameters` longtext NOT NULL,
			  `ou` varchar(255) NOT NULL,
			  `enabled` smallint(1) NOT NULL,
			  PRIMARY KEY (`account_name`),
			  UNIQUE KEY `account_name` (`account_name`),
			  KEY `ou` (`ou`)
			) ";
			$this->QUERY_SQL($sql,'artica_backup');	
	}
	
	
	
	if(!$this->TABLE_EXISTS('emailing_campain_imap_events','artica_backup')){			
			$sql="CREATE TABLE IF NOT EXISTS `emailing_campain_imap_events` (
					  `ID` int(11) NOT NULL AUTO_INCREMENT,
					  `zDate` datetime NOT NULL,
					  `subject` varchar(255) NOT NULL,
					  `content` longtext NOT NULL,
					  `ou` varchar(255) NOT NULL,
					  `account` varchar(255) NOT NULL,
					  PRIMARY KEY (`ID`),
					  KEY `zDate` (`zDate`),
					  KEY `ou` (`ou`),
					  KEY `account` (`account`))";
			$this->QUERY_SQL($sql,'artica_backup');	
	} 

	
	
	if(!$this->TABLE_EXISTS('emailing_campain_blacklist','artica_backup')){			
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`emailing_campain_blacklist` (
			`email` VARCHAR( 255 ) NOT NULL ,
			`reason` VARCHAR( 255 ) NOT NULL ,
			UNIQUE (`email`))";	
			$this->QUERY_SQL($sql,'artica_backup');	
			
	}

	
	
	
	if(!$this->FIELD_EXISTS("emailing_campain_linker","queue_builder_pourc","artica_backup")){
				$sql="ALTER TABLE `emailing_campain_linker` ADD `queue_builder_pourc` INT( 1 ) NOT NULL DEFAULT '0'";
				$this->QUERY_SQL($sql,'artica_backup');
	}	
	
	if(!$this->FIELD_EXISTS("emailing_campain_linker","locked","artica_backup")){
				$sql="ALTER TABLE `emailing_campain_linker` ADD `locked` INT( 1 ) NOT NULL DEFAULT '0'";
				$this->QUERY_SQL($sql,'artica_backup');
	}		
	
	
if(!$this->TABLE_EXISTS('emailing_campain_queues','artica_backup')){			
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`emailing_campain_queues` (
					`ID` 				INT( 10 ) NOT NULL AUTO_INCREMENT,
					`body` 				BLOB NOT NULL ,
					`from` 				VARCHAR( 255 ) NOT NULL ,
					`rcpt_to` 			VARCHAR( 255 ) NOT NULL ,
					`msmtp` 			BLOB NOT NULL ,
					`sended` 			INT( 1 ) NOT NULL DEFAULT '0',
					`msg_size` 			INT( 5 ) NOT NULL DEFAULT '0',
					`campain_linker_id` INT( 10 ) NOT NULL ,
					`ou` 		   		VARCHAR( 255 ) NOT NULL ,
					`time_created` 		DATETIME NOT NULL ,
					`time_sended`  		DATETIME NOT NULL ,
					`template_id`  		INT( 10 ) NOT NULL ,
					`contact_id`   		INT( 10 ) NOT NULL ,
					`unique_key`   		VARCHAR( 255 ) NOT NULL,
					 PRIMARY KEY (`ID`),
					 KEY `sended` (`sended`),
					 KEY `rcpt_to` (`rcpt_to`),
					 KEY `ou` (`ou`),
					 INDEX ( `campain_linker_id` , `template_id` , `contact_id` )
					)";	
		$this->QUERY_SQL($sql,'artica_backup');	
	}
	
	
	
	if(!$this->TABLE_EXISTS('emailing_campain_events','artica_backup')){			
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`emailing_campain_events` (
				`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`campain_linker_id` INT( 5 ) NOT NULL ,
				`zDate` DATETIME NOT NULL ,
				`time_duration` VARCHAR( 255 ) NOT NULL ,
				`template_id` INT( 5 ) NOT NULL ,
				`database_id` INT( 5 ) NOT NULL ,
				`messages_number` INT( 5 ) NOT NULL ,
				`massmailing_size` INT( 10 ) NOT NULL ,
				`events_details` LONGTEXT NOT NULL,
				`task_success` INT( 1 ) NOT NULL DEFAULT '0',
				 KEY `campain_linker_id` ( `campain_linker_id`),
				 KEY `zDate` (`zDate`))";

				$this->QUERY_SQL($sql,'artica_backup');	
				if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}	
		}
	}

	public function CheckTableEmailingContacts($tablename,$merged=array()){
		if(!$this->TABLE_EXISTS($tablename,'artica_backup')){	
				$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`$tablename` (
				`ID` INT( 10 ) NOT NULL AUTO_INCREMENT ,
				`gender` VARCHAR( 10 ) NOT NULL ,
				`firstname` VARCHAR( 35 ) NOT NULL ,
				`lastname` VARCHAR( 255 ) NOT NULL ,
				`email` VARCHAR( 255 ) NOT NULL ,
				`domain` VARCHAR( 90 ) NOT NULL ,
				`phone` VARCHAR( 65 ) NOT NULL ,
				`city` VARCHAR( 35 ) NOT NULL ,
				`cp` VARCHAR( 10 ) NOT NULL ,
				`postaladdress` VARCHAR( 255 ) NOT NULL ,
				PRIMARY KEY ( `ID` ) ,
			    UNIQUE KEY `email` (`email`),
				KEY `lastname` (`lastname`),
				KEY `domain` (`domain`),
				KEY `city` (`city`),
				KEY `cp` (`cp`)
				)";
				
		if(count($merged)>1){		
			$sql=$sql. " TYPE=MERGE UNION=(".implode(",",$merged).") INSERT_METHOD=LAST";
			$sql=str_replace("UNIQUE KEY","KEY",$sql);
			$sql=str_replace("AUTO_INCREMENT","",$sql);
			$sql=str_replace("PRIMARY","",$sql);
		}		
				
		$this->QUERY_SQL($sql,'artica_events');
		writelogs("$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		
		if(!$this->ok){
			$this->mysql_error=$this->mysql_error."\n".$sql;
			writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		
	}

	public function CheckTableThinClient(){
		if(!$this->TABLE_EXISTS('thinclient_hardware_modules','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`thinclient_hardware_modules` (`module` VARCHAR( 60 ) NOT NULL ,UNIQUE (`module`))";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		if(!$this->TABLE_EXISTS('thinclient_package_modules','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`thinclient_package_modules` (`package` VARCHAR( 60 ) NOT NULL ,UNIQUE (`package`))";		
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}

		
		if(!$this->TABLE_EXISTS('thinclient_computers','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`thinclient_computers` (`uid` VARCHAR( 90 ) NOT NULL ,`parameters` LONGTEXT NOT NULL ,UNIQUE (`uid`))";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		
		
		if(!$this->TABLE_EXISTS('thinclient_compile_logs','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `thinclient_compile_logs` (
			  `ID` int(11) NOT NULL auto_increment,
			  `zdate` datetime NOT NULL,
			  `event` longtext NOT NULL,
			  `subject` varchar(255) NOT NULL,
			  PRIMARY KEY  (`ID`),
			  KEY `zdate` (`zdate`),
			  KEY `subject` (`subject`))";		
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		
		if(!$this->TABLE_EXISTS('virtualbox_watchdog','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`virtualbox_watchdog` (`uuid` VARCHAR( 255 ) NOT NULL ,UNIQUE (`uuid`)) ";		
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		
		if(!$this->TABLE_EXISTS('virtualbox_nat','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`virtualbox_nat` (
			`vboxport` INT( 3 ) NOT NULL ,
			`vboxid` VARCHAR( 100 ) NOT NULL ,
			`localport` INT( 3 ) NOT NULL ,
			INDEX ( `vboxid` ) ,
			UNIQUE (
			`localport`
			))";
				
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$sql="ALTER TABLE `virtualbox_nat` DROP INDEX `vboxport`";
			$this->QUERY_SQL($sql,'artica_backup');
			$sql="ALTER TABLE `virtualbox_nat` ADD UNIQUE (`localport`)";
			$this->QUERY_SQL($sql,'artica_backup');
		}
		
		
	}

	public function CheckTableIMAP(){
		if(!$this->TABLE_EXISTS('mbx_con','artica_events')){
			$sql="CREATE TABLE IF NOT EXISTS `mbx_con` (
			  `ID` INT UNSIGNED NOT NULL auto_increment,
			  `zDate` datetime NOT NULL,
			  `mbx_service` varchar(30) NOT NULL,
			  `client_name` varchar(90) NOT NULL,
			  `client_ip` varchar(20) NOT NULL,
			  `uid` varchar(50) NOT NULL,
			  `imap_server` varchar(255) NOT NULL,
			  PRIMARY KEY  (`ID`),
			  KEY `zDate` (`zDate`,`mbx_service`,`client_name`,`client_ip`,`uid`),
			  KEY `imap_server` (`imap_server`)
			)";	
			$this->QUERY_SQL($sql,'artica_events');
		}
		if(!$this->TABLE_EXISTS('mbx_migr_users','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`mbx_migr_users` (
				`zmd5` VARCHAR( 90 ) NOT NULL ,
				`mbx_migr_id` INT( 10 ) NOT NULL ,
				`ou` VARCHAR( 90 ) NOT NULL ,
				`imap_server` VARCHAR( 255 ) NOT NULL ,
				`imapr_server` VARCHAR( 255 ) NOT NULL ,
				`usessl` smallint( 1 ) NOT NULL DEFAULT '0',
				`usesslr` smallint( 1 ) NOT NULL DEFAULT '0',
				`username` VARCHAR( 90 ) NOT NULL ,
				`password` VARCHAR( 90 ) NOT NULL,
				`usernamer` VARCHAR( 90 ) NOT NULL ,
				`passwordr` VARCHAR( 90 ) NOT NULL,				
				`cert_fingerprint` VARCHAR( 128 ) NOT NULL,
				`cert_fingerprintr` VARCHAR( 128 ) NOT NULL,
				`uid` VARCHAR( 90 ) NOT NULL ,
				`imported` INT( 1 ) NOT NULL DEFAULT '0',
				`mbx_created` INT( 1 ) NOT NULL DEFAULT '0',
				`mbxfolders` LONGTEXT NOT NULL,
				`maxsize` INT UNSIGNED NOT NULL DEFAULT '0',
				`maxage` INT UNSIGNED NOT NULL DEFAULT '0',
				`createfolders` smallint(1) NOT NULL DEFAULT '0',
				`readonly` smallint(1) NOT NULL DEFAULT '1',
				`AsGateway` smallint(1) NOT NULL DEFAULT '0',
				`verbosed` smallint(1) NOT NULL DEFAULT '0',
				`scheduled` smallint(1) NOT NULL DEFAULT '0',
				`PID` INT( 5 ) NOT NULL DEFAULT '0',
				`events` LONGTEXT NOT NULL,
				 KEY `mbx_migr_id` ( `mbx_migr_id` ),
				 KEY `ou` ( `ou` ),
				 KEY `username` ( `username` ),
				 KEY `mbx_created` ( `mbx_created` ),
				 KEY `uid` ( `uid` ),
				 UNIQUE (`zmd5`)
				 )";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}

		if(!$this->FIELD_EXISTS("mbx_migr_users","scheduled","artica_backup")){$sql="ALTER TABLE `mbx_migr_users` ADD `scheduled` smallint( 1 ) NOT NULL";$this->QUERY_SQL($sql,'artica_backup');}
		if(!$this->FIELD_EXISTS("mbx_migr_users","createfolders","artica_backup")){$sql="ALTER TABLE `mbx_migr_users` ADD `createfolders` smallint( 1 ) NOT NULL";$this->QUERY_SQL($sql,'artica_backup');}
		if(!$this->FIELD_EXISTS("mbx_migr_users","AsGateway","artica_backup")){$sql="ALTER TABLE `mbx_migr_users` ADD `AsGateway` smallint( 1 ) NOT NULL";$this->QUERY_SQL($sql,'artica_backup');}		
		if(!$this->FIELD_EXISTS("mbx_migr_users","readonly","artica_backup")){$sql="ALTER TABLE `mbx_migr_users` ADD `readonly` smallint( 1 ) NOT NULL DEFAULT '1'";$this->QUERY_SQL($sql,'artica_backup');}
		if(!$this->FIELD_EXISTS("mbx_migr_users","verbosed","artica_backup")){$sql="ALTER TABLE `mbx_migr_users` ADD `verbosed` smallint( 1 ) NOT NULL DEFAULT '0'";$this->QUERY_SQL($sql,'artica_backup');}
		if(!$this->FIELD_EXISTS("mbx_migr_users","cert_fingerprint","artica_backup")){$sql="ALTER TABLE `mbx_migr_users` ADD `cert_fingerprint` varchar( 128 ) NOT NULL";$this->QUERY_SQL($sql,'artica_backup');}
		if(!$this->FIELD_EXISTS("mbx_migr_users","mbxfolders","artica_backup")){$sql="ALTER TABLE `mbx_migr_users` ADD `mbxfolders` LONGTEXT NOT NULL";$this->QUERY_SQL($sql,'artica_backup');}		
		if(!$this->FIELD_EXISTS("mbx_migr_users","maxsize","artica_backup")){$sql="ALTER TABLE `mbx_migr_users` ADD `maxsize` INT UNSIGNED NOT NULL";$this->QUERY_SQL($sql,'artica_backup');}
		if(!$this->FIELD_EXISTS("mbx_migr_users","maxage","artica_backup")){$sql="ALTER TABLE `mbx_migr_users` ADD `maxage` INT UNSIGNED NOT NULL";$this->QUERY_SQL($sql,'artica_backup');}		
		
		if(!$this->FIELD_EXISTS("mbx_migr_users","imapr_server","artica_backup")){$sql="ALTER TABLE `mbx_migr_users` ADD `imapr_server` varchar(255) NOT NULL";$this->QUERY_SQL($sql,'artica_backup');}
		if(!$this->FIELD_EXISTS("mbx_migr_users","usernamer","artica_backup")){$sql="ALTER TABLE `mbx_migr_users` ADD `usernamer` varchar(90) NOT NULL";$this->QUERY_SQL($sql,'artica_backup');}
		if(!$this->FIELD_EXISTS("mbx_migr_users","passwordr","artica_backup")){$sql="ALTER TABLE `mbx_migr_users` ADD `passwordr` varchar(90) NOT NULL";$this->QUERY_SQL($sql,'artica_backup');}
		if(!$this->FIELD_EXISTS("mbx_migr_users","usesslr","artica_backup")){$sql="ALTER TABLE `mbx_migr_users` ADD `usesslr` smallint(1) NOT NULL";$this->QUERY_SQL($sql,'artica_backup');}
		if(!$this->FIELD_EXISTS("mbx_migr_users","cert_fingerprintr","artica_backup")){$sql="ALTER TABLE `mbx_migr_users` ADD `cert_fingerprintr` varchar( 128 ) NOT NULL";$this->QUERY_SQL($sql,'artica_backup');}
		
		
		
		
		if(!$this->TABLE_EXISTS('mbx_migr','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`mbx_migr` (
				`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`ou` VARCHAR( 90 ) NOT NULL ,
				`filepath` VARCHAR( 255 ) NOT NULL ,
				`imported` INT( 1 ) NOT NULL ,
				`finish` INT( 1 ) NOT NULL ,
				`members_count` INT( 10 ) NOT NULL,
				`local_domain` VARCHAR( 255 ) NOT NULL ,
				 KEY `ou` ( `ou` ),
				 KEY `imported` (`imported`),
				 KEY `local_domain` (`local_domain`),
				 KEY `finish` (`finish`)
				 )";
			$this->QUERY_SQL($sql,'artica_backup'); 
			if(!$this->ok){writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}

		}
		
		if(!$this->TABLE_EXISTS('mailmaninfos','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`mailmaninfos` (
			`list` VARCHAR( 90 ) NOT NULL PRIMARY KEY,
			`adminmail` VARCHAR( 128 ) NOT NULL ,
			`mangle` smallint( 1 ) NOT NULL ,
			`added` smallint( 1 ) NOT NULL ,
			`delete` smallint( 1 ) NOT NULL ,
			`urlhost` VARCHAR( 255 ) NOT NULL ,
			`emailhost`VARCHAR( 255 ) NOT NULL ,
			 KEY `urlhost` ( `urlhost` ),
			 KEY `emailhost` ( `emailhost` )
			 )";
			 $this->QUERY_SQL($sql,'artica_backup');  
			if(!$this->ok){writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		
		if(!$this->FIELD_EXISTS("mailmaninfos","mangle","artica_backup")){
			$sql="ALTER TABLE `mailmaninfos` ADD `mangle` INT( 1 ) NOT NULL DEFAULT '0',ADD INDEX (`mangle`)";
			$this->QUERY_SQL($sql,'artica_backup');
		}			
		
		$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`cyrus_backup` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
				`zDate` DATETIME NOT NULL ,
				`hostname` VARCHAR( 128 ) NOT NULL ,
				`directory` BIGINT UNSIGNED,
				`duration` VARCHAR( 256 ) NOT NULL ,
				`size` BIGINT UNSIGNED ,
				INDEX ( `zDate` , `directory` , `size` ,`hostname`)
				);";
		$this->QUERY_SQL($sql,'artica_events');
		
		if(!$this->TABLE_EXISTS('fetchmail_events','artica_events')){
			$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`fetchmail_events` (
				`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
				`zDate` DATETIME NOT NULL ,
				`server` VARCHAR( 128 ) NOT NULL ,
				`account` VARCHAR( 128 ) NOT NULL ,
				`size` BIGINT UNSIGNED ,
				INDEX ( `zDate` , `server` , `account` )
				);";
			
			$this->QUERY_SQL($sql,'artica_events');
			if(!$this->ok){writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		
			if(!$this->TABLE_EXISTS('fetchmail_rules','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `fetchmail_rules` (
				  `ID` int(10) NOT NULL AUTO_INCREMENT,
				  `uid` varchar(255) NOT NULL,
				  `is` varchar(255) NOT NULL,
				  `user` varchar(255) NOT NULL,
				  `pass` varchar(255) NOT NULL,
				  `orgmail` varchar(255) NOT NULL,
				  `poll` varchar(255) NOT NULL,
				  `proto` varchar(20) NOT NULL,
				  `sslproto` varchar(20) NOT NULL,
				  `keep` smallint(1) NOT NULL,
				  `enabled` smallint(1) NOT NULL,
				  `md5` varchar(120) NOT NULL,
				  `tracepolls` smallint(1) NOT NULL DEFAULT '0',
				  `fetchall` smallint(1) NOT NULL DEFAULT '0',
				  `ssl` smallint(1) NOT NULL DEFAULT '0',
				  `sslcertck` smallint(1) NOT NULL DEFAULT '0',
				  `port` int(5) NOT NULL,
				  `timeout` int(5) NOT NULL,
				  `limit` BIGINT UNSIGNED NOT NULL,
				  `interval` int(5) NOT NULL,
				  `aka` varchar(255) NOT NULL,
				  `nokeep` smallint(1) NOT NULL DEFAULT '0',
				  `multidrop` smallint(1) NOT NULL DEFAULT '0',
				  `dropdelivered` smallint(1) NOT NULL DEFAULT '0',
				  `smtp_host` VARCHAR( 75 ) NOT NULL DEFAULT '127.0.0.1',
				  `smtp_port` INT( 10 ) NOT NULL DEFAULT '25',
				  `UseDefaultSMTP` smallint(1) NOT NULL DEFAULT '1',
				  `sslfingerprint` VARCHAR( 255 ) NOT NULL,
				  `schedule` VARCHAR( 128 ) NOT NULL,
				  `folders` TEXT NOT NULL,
				  PRIMARY KEY (`ID`),
				  UNIQUE KEY `md5` (`md5`),
				  KEY `smtp_host` (`smtp_host`),
				  KEY `orgmail` (`orgmail`),
				  KEY `uid` (`uid`)
				) ";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}

		if(!$this->FIELD_EXISTS("fetchmail_rules","sslproto","artica_backup")){
			$sql="ALTER TABLE `fetchmail_rules` ADD `sslproto` VARCHAR( 20 ) NOT NULL";
			$this->QUERY_SQL($sql,'artica_backup');
		}
		
		if(!$this->FIELD_EXISTS("fetchmail_rules","UseDefaultSMTP","artica_backup")){
			$sql="ALTER TABLE `fetchmail_rules` ADD `UseDefaultSMTP` INT( 1 ) NOT NULL DEFAULT '1'";
			$this->QUERY_SQL($sql,'artica_backup');
		}		
		
		if(!$this->FIELD_EXISTS("fetchmail_rules","orgmail","artica_backup")){
			$sql="ALTER TABLE `fetchmail_rules` ADD `orgmail` VARCHAR( 255 ) NOT NULL";
			$this->QUERY_SQL($sql,'artica_backup');
		}		
		
		if(!$this->FIELD_EXISTS("fetchmail_rules","nokeep","artica_backup")){
			$sql="ALTER TABLE `fetchmail_rules` ADD `nokeep` INT( 1 ) NOT NULL DEFAULT '0'";
			$this->QUERY_SQL($sql,'artica_backup');
		}		
		
		if(!$this->FIELD_EXISTS("fetchmail_rules","limit","artica_backup")){
			$sql="ALTER TABLE `fetchmail_rules` ADD `limit` BIGINT UNSIGNED DEFAULT '0'";
			$this->QUERY_SQL($sql,'artica_backup');
		}	

		if(!$this->FIELD_EXISTS("fetchmail_rules","folders","artica_backup")){
			$sql="ALTER TABLE `fetchmail_rules` ADD `folders` TEXT";
			$this->QUERY_SQL($sql,'artica_backup');
		}		
		
		if(!$this->FIELD_EXISTS("fetchmail_rules","smtp_port","artica_backup")){
			$sql="ALTER TABLE `fetchmail_rules` ADD `smtp_port` INT( 10 ) NOT NULL DEFAULT '25'";
			$this->QUERY_SQL($sql,'artica_backup');
		}		
		if(!$this->FIELD_EXISTS("fetchmail_rules","sslcertck","artica_backup")){
			$sql="ALTER TABLE `fetchmail_rules` ADD `sslcertck` INT( 1 ) NOT NULL DEFAULT '0'";
			$this->QUERY_SQL($sql,'artica_backup');
		}		
		
		if(!$this->FIELD_EXISTS("fetchmail_rules","smtp_host","artica_backup")){
				$sql="ALTER TABLE `fetchmail_rules` ADD `smtp_host` VARCHAR( 75 ) NOT NULL,ADD INDEX ( `smtp_host` )";
				$this->QUERY_SQL($sql,'artica_backup');
		}
		
		if(!$this->FIELD_EXISTS("fetchmail_rules","sslfingerprint","artica_backup")){
			$sql="ALTER TABLE `fetchmail_rules` ADD `sslfingerprint` VARCHAR( 255 ) NOT NULL";
			$this->QUERY_SQL($sql,'artica_backup');
		}

		if(!$this->FIELD_EXISTS("fetchmail_rules","schedule","artica_backup")){
			$sql="ALTER TABLE `fetchmail_rules` ADD `schedule` VARCHAR( 128 ) NOT NULL";
			$this->QUERY_SQL($sql,'artica_backup');
		}			
		
		if(!$this->FIELD_EXISTS("fetchmail_rules","dropdelivered","artica_backup")){
			$sql="ALTER TABLE `fetchmail_rules` ADD `dropdelivered` INT( 1 ) NOT NULL DEFAULT '0'";
			$this->QUERY_SQL($sql,'artica_backup');
		}		

		if(!$this->TABLE_EXISTS('webdavusers','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `webdavusers` (
				  `uid` varchar(255) NOT NULL,
				  `Params` TEXT NOT NULL,
				  PRIMARY KEY ( `uid` ) 
				  )";	
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}


		

	
		
		
		if(!$this->TABLE_EXISTS('zarafa_backup','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `zarafa_backup` (
				  `ID` int(11) NOT NULL AUTO_INCREMENT,
				  `filepath` varchar(255) NOT NULL,
				  `filesize` BIGINT UNSIGNED NOT NULL,
				  `ztime` varchar(128) NOT NULL,
				  `zDate` DATETIME NOT NULL,
				  PRIMARY KEY (`ID`),
				  KEY (`filepath`),
				  KEY `filesize` (`filesize`),
				  KEY `zDate` (`zDate`)
				) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			}		


			
			

				
				
			$sql="CREATE TABLE IF NOT EXISTS `nics_roles` (
			`zmd5` varchar(90) NOT NULL,
			`nic` varchar(20) NOT NULL,
			`role` varchar(128) NOT NULL,
			 `status` varchar(25) NOT NULL,
			`roleconf` TEXT,
			  PRIMARY KEY (`zmd5`),
			  KEY `nic` (`nic`),
			  KEY `role` (`role`),
			  KEY `status` (`status`)
			) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,'artica_backup');				
			


			
			if(!$this->TABLE_EXISTS('vpn_auth','artica_backup')){
				$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`vpn_auth` (
				`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`type` smallint NOT NULL DEFAULT 1,
				`hostname` varchar(128) NOT NULL,
				`params` TEXT,
				PRIMARY KEY (`ID`),
				  KEY `hostname` (`hostname`),
				  KEY `type` (`type`)
				 ) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,'artica_events');
				if(!$this->ok){
					writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
					writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				}
			}

		if(!$this->TABLE_EXISTS('vpn_events','artica_events')){		
				$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`vpn_events` (
				`stime` BIGINT UNSIGNED ,
				`subject` VARCHAR( 255 ) NOT NULL ,
				`text` LONGTEXT NOT NULL ,
				`IPPARAM` VARCHAR( 255 ) NOT NULL ,
				INDEX ( `IPPARAM` ) ,
				UNIQUE (`stime`)
				) ENGINE=MYISAM;";			
				$this->QUERY_SQL($sql,'artica_events');	
				if(!$this->ok){
					writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
					writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				}
		}	
	}

	public function CheckTableEvents(){

		if(!$this->TABLE_EXISTS('events','artica_events')){	
			$sql="CREATE TABLE IF NOT EXISTS `events` (
			  	  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				  `zDate` datetime NOT NULL,
				  `event_id` INT UNSIGNED NOT NULL,
				  `event_type` int(11) NOT NULL,
				  `hostname` varchar(75) NOT NULL,
				  `process` varchar(75) NOT NULL,
				  `text` varchar(255) NOT NULL,
				  `context` varchar(75) NOT NULL DEFAULT 'unknown',
				  `sended` smallint(1) NOT NULL DEFAULT '0',
				  `content` text NOT NULL,
				  `attached_files` TEXT NOT NULL,
				  `recipient` VARCHAR( 255 ) NULL,
				  PRIMARY KEY (`ID`),
				  KEY `zDate` (`zDate`,`event_id`,`event_type`,`process`,`hostname`),
				  KEY `context` (`context`),
				  KEY `sended` (`sended`)) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_events');
		}
		
		$sql="CREATE TABLE IF NOT EXISTS `updateutilityev` (
				  `zDate` datetime NOT NULL,
				  `filesize` INT UNSIGNED NOT NULL,
				  `filesnum` int(11) NOT NULL,
				  `isSuccess` smallint(1) NOT NULL,
				 `details` LONGTEXT NOT NULL,
				  PRIMARY KEY (`zDate`),
				  KEY `filesize` (`filesize`),
				  KEY `filesnum` (`filesnum`),
				  KEY `isSuccess` (`isSuccess`)
				  ) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_events');	

		
		

					
		
			if(!$this->TABLE_EXISTS('avgreports','artica_events')){	
			$sql="CREATE TABLE IF NOT EXISTS `avgreports` (
			  	  `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				  `zDate` datetime NOT NULL,
				  `loadavg` DOUBLE( 100, 2 ),
				  `cpur` DOUBLE( 100, 2 ),
				  `memr` DOUBLE( 100, 2 ),
				  `psreport` TEXT NOT NULL,
				  `lsofreport` TEXT NOT NULL,
				  `iotopreport` TEXT NOT NULL,
				  PRIMARY KEY (`ID`),
				  KEY `loadavg` (`loadavg`),
				  KEY `cpur` (`cpur`),
				  KEY `memr` (`memr`)
				  
				  ) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_events');
		}		
		
		
		if(!$this->TABLE_EXISTS('debian_mirror_events','artica_events')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`debian_mirror_events` (`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,`zDate` DATETIME NOT NULL ,`subject` VARCHAR( 255 ) NOT NULL ,`text` LONGTEXT NOT NULL ,INDEX ( `zDate` ))";
			$this->QUERY_SQL($sql,'artica_events');
				if(!$this->ok){
					writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
					writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				}	
		}		
		
		
		
		
		if(!$this->FIELD_EXISTS("events","context","artica_events")){
			 	writelogs("create events.context",__FUNCTION__,__FILE__,__LINE__);
				$sql="ALTER TABLE `events` ADD `context` VARCHAR( 75 ) NOT NULL DEFAULT 'unknown',ADD INDEX ( context )";
				$this->QUERY_SQL($sql,'artica_events');
				if(!$this->ok){
					writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
					writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				}
		}
		
		if(!$this->FIELD_EXISTS("events","recipient","artica_events")){
				writelogs("create events.recipient",__FUNCTION__,__FILE__,__LINE__);
				$sql="ALTER TABLE `events` ADD `recipient` VARCHAR( 255 ) NOT NULL";
				$this->QUERY_SQL($sql,'artica_events');
				if(!$this->ok){
					writelogs("$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				}				
		}		

		if(!$this->FIELD_EXISTS("events","sended","artica_events")){
				$sql="ALTER TABLE `events` ADD `sended` INT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( sended )";
				$this->QUERY_SQL($sql,'artica_events');
			}
			
			if(!$this->FIELD_EXISTS("events","attached_files","artica_events")){
				$sql="ALTER TABLE `events` ADD `attached_files` TEXT NOT NULL ";
				$this->QUERY_SQL($sql,'artica_events');
			}

			if(!$this->FIELD_EXISTS("events","sended","artica_events")){
				$sql="ALTER TABLE `events` ADD `sended` INT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( sended )";
				$this->QUERY_SQL($sql,'artica_events');
			}	
		if(!$this->TABLE_EXISTS('cyrus_backup_events','artica_events')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`cyrus_backup_events` (
					`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`zDate` DATETIME NOT NULL ,
					`local_ressource` VARCHAR( 50 ) NOT NULL ,
					`events` TINYTEXT NOT NULL ,
					`success` INT( 1 ) NOT NULL DEFAULT '0',
					INDEX ( `local_ressource` , `success` )
					) ENGINE=MYISAM;";
			 
			$this->QUERY_SQL($sql,'artica_events');
			}
	
	if(!$this->FIELD_EXISTS("cyrus_backup_events","remote_ressource","artica_events")){
		$sql="ALTER TABLE `cyrus_backup_events` ADD `remote_ressource` VARCHAR( 125 ) NOT NULL ,ADD INDEX ( remote_ressource )";
		$this->QUERY_SQL($sql,'artica_events');
	}
	
	if(!$this->TABLE_EXISTS('backup_events','artica_events')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`backup_events` (
					`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`task_id` INT( 10 ) NOT NULL ,
					`zdate` DATETIME NOT NULL ,
					`backup_source` VARCHAR( 255 ) NOT NULL ,
					`event` MEDIUMTEXT NOT NULL ,
					INDEX ( `task_id` , `zdate` , `backup_source` )) ENGINE=MYISAM;";
			 
			$this->QUERY_SQL($sql,'artica_events');
			}	
			
	if(!$this->TABLE_EXISTS('quarantine_report_users','artica_backup')){				
		$sql="CREATE TABLE IF NOT EXISTS `quarantine_report_users` (
			  `userid` varchar(255) NOT NULL,
			  `MaxDays` smallint(1) NOT NULL,
			  `hour_execution` int(2) NOT NULL,
			  `min_execution` int(2) NOT NULL,
			  `type` smallint(1) NOT NULL,
			  `enabled` smallint(1) NOT NULL,
			  `parameters` TEXT NOT NULL,
			  PRIMARY KEY (`userid`),
			  KEY `enabled` (`enabled`),
			  KEY `type` (`type`)) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("quarantine_report_users:: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
	}

	
	public function CheckOutOfOfficeTable(){
		$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`OutOfOffice` (
			`zMD5` VARCHAR( 100 ) NOT NULL ,
			`uid` VARCHAR( 90 ) NOT NULL ,
			`zDate` DATETIME NOT NULL ,
			`mailfrom` VARCHAR( 90 ) NOT NULL ,
			PRIMARY KEY ( `zMD5` ) ,
			INDEX ( `uid` , `mailfrom` )
			) ENGINE=MYISAM;";
		
		if(!$this->TABLE_EXISTS('OutOfOffice','artica_events')){$this->QUERY_SQL($sql,'artica_events');}	
	}

	public function CheckTables_gluster(){
		if(!$this->TABLE_EXISTS('glusters_clients','artica_backup')){				
		$sql="CREATE TABLE IF NOT EXISTS `glusters_clients` (
			  `ID` int(11) NOT NULL AUTO_INCREMENT,
			  `client_ip` varchar(90) NOT NULL,
			  `hostname` varchar(128) NOT NULL,
			  `client_port` int(3) NOT NULL,
			  `client_notified` smallint(1) NOT NULL,
			  `uuid`  varchar(128) NOT NULL,
			  `state`  varchar(128) NOT NULL,
			  `parameters` text NOT NULL,
			  `NotifToDelete` smallint(1) NOT NULL DEFAULT '0',
			  PRIMARY KEY (`ID`),
			  UNIQUE KEY `client_ip` (`client_ip`),
			  UNIQUE KEY `uuid` (`uuid`),
			  KEY `client_notified` (`client_notified`),
			  KEY `state` (`state`),
			  KEY `hostname` (`hostname`),
			  KEY `NotifToDelete` (`NotifToDelete`)
			) ENGINE=MYISAM;";
		
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("glusters_clients:: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		
		$this->FIELD_CHECKS("glusters_clients","NotifToDelete","artica_backup","int",11,true);
		$this->FIELD_CHECKS("glusters_clients","hostname","artica_backup","varchar",128,true);
		$this->FIELD_CHECKS("glusters_clients","uuid","artica_backup","varchar",128,true,true);
		$this->FIELD_CHECKS("glusters_clients","state","artica_backup","varchar",128,true);			
		
		
		
	if(!$this->TABLE_EXISTS('glusters_servers','artica_backup')){				
		$sql="CREATE TABLE IF NOT EXISTS `glusters_servers` (
		  `ID` int(11) NOT NULL AUTO_INCREMENT,
		  `server_ip` varchar(90) NOT NULL,
		  `server_port` int(3) NOT NULL,
		  `hostname` varchar(128) NOT NULL,
		  `parameters` text NOT NULL,
		  PRIMARY KEY (`ID`),
		  UNIQUE KEY `server_ip` (`server_ip`),
		  KEY `hostname` (`hostname`)
		  
		  ) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("glusters_servers:: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		
	if(!$this->TABLE_EXISTS('glusters_clientssize','artica_backup')){				
		$sql="CREATE TABLE IF NOT EXISTS `glusters_clientssize` (
		  `ID` int(11) NOT NULL AUTO_INCREMENT,
		  `client_ip` varchar(90) NOT NULL,
		  `hostname` varchar(128) NOT NULL,
		  `SIZE` varchar(40) NOT NULL,
		  `USED` varchar(40) NOT NULL,
		  `AVAILABLE` varchar(40) NOT NULL,
		  `POURC` tinyint(2) NOT NULL,
		  `DIRNAME` varchar(255) NOT NULL,
		  PRIMARY KEY (`ID`),
		  KEY `client_ip` (`client_ip`),
		  KEY `hostname` (`hostname`),
		  KEY `POURC` (`POURC`),
		  KEY `DIRNAME` (`DIRNAME`)
		  
		  ) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("glusters_clientssize:: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		
		if(!$this->TABLE_EXISTS('glusters_volumes','artica_backup')){				
		$sql="CREATE TABLE IF NOT EXISTS `glusters_volumes` (
		  `ID` int(11) NOT NULL AUTO_INCREMENT,
		  `volume_name` varchar(90) NOT NULL,
		  `volume_type` varchar(128) NOT NULL,
		  `state` varchar(50) NOT NULL,
		  PRIMARY KEY (`ID`),
		  UNIQUE KEY `volume_name` (`volume_name`),
		  KEY `state` (`state`)
		 
		  ) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("glusters_volumes:: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}		
		
		
			
	
		
		
	if(!$this->TABLE_EXISTS('gluster_paths','artica_backup')){				
			$sql="CREATE TABLE IF NOT EXISTS `gluster_paths` (
				  `ID` int(10) NOT NULL AUTO_INCREMENT,
				  `cluster_path` varchar(255) NOT NULL,
				  `zmd` varchar(90) NOT NULL,
				  PRIMARY KEY (`ID`),
				  UNIQUE KEY `cluster_path` (`cluster_path`),
				  UNIQUE KEY `zmd` (`zmd`)) ENGINE=MYISAM;";		
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("gluster_paths:: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		
		if(!$this->TABLE_EXISTS('gluster_clients_brick','artica_backup')){				
			$sql="CREATE TABLE IF NOT EXISTS `gluster_clients_brick` (
				`brickname` VARCHAR( 20 ) NOT NULL ,
				`source` VARCHAR( 255 ) NOT NULL ,INDEX ( `source` ) ,UNIQUE (`brickname`)) ENGINE=MYISAM;";		
				$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("gluster_clients_brick:: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}		

	}

	function BuildTables(){
		if(!$this->UseMysql){
			$q=new lib_sqlite();
			$q->CheckDatabases();
			return;
		}
		 if(!$this->DATABASE_EXISTS('artica_events')){if(!$this->CREATE_DATABASE('artica_events')){return null;}}
		 if(!$this->DATABASE_EXISTS('artica_backup')){if(!$this->CREATE_DATABASE('artica_backup')){return null;}}	
		 
		 $this->Check_quarantine_table();
		 $this->check_mysql_dbtables();
		 $this->Check_iptables_table();
		 $this->check_storage_table();
		 $this->Check_smtp_logs_table();
		 $this->CheckTables_rsync();
		 $this->CheckTables_dar();
		 $this->CheckTableIMAP();
		 $this->CheckTableVirus();
		 $this->CheckTableEvents();	
		 $this->CheckTable_dansguardian();	
		 $this->CheckTables_repositories();
		 $this->CheckTables_openvpn();
		 $this->CheckOutOfOfficeTable();
		 $this->checkAgentTables();
		 $this->CheckTablesSquid();
		 $this->CheckTablesPostfilter();
		 $this->check_emailing_tables();
		 $this->Check_reports_table();
		 $this->CheckTables_gluster();
		 $this->CheckTableWebsites();
		 $this->CheckTableThinClient();
		 $this->CheckTables_qos();
		 $this->CheckTablesAwstats();
		
		 $this->check_vps_tables();
		 $this->check_networks_tables();
		 $this->checkTablesSamba();
		 $this->check_storage_table();
		if(!$this->FIELD_EXISTS("smtp_logs_day","emails","artica_events")){
			$sql="ALTER TABLE `smtp_logs_day` ADD `emails` int(10) NOT NULL ";
			$this->QUERY_SQL($sql,'artica_events');
		}	
	if(!$this->TABLE_EXISTS('smtp_logs_day','artica_events')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`smtp_logs_day` (
				`key` VARCHAR( 75 ) NOT NULL ,
				`day` DATE NOT NULL ,
				`delivery_domain` VARCHAR( 80 ) NOT NULL ,
				`bounce_error` VARCHAR( 80 ) NOT NULL ,
				`emails` int(10) NOT NULL,
				PRIMARY KEY ( `key` ) ,
				INDEX ( `day` , `delivery_domain` , `bounce_error` )
				) ENGINE=MYISAM;";
						
				 $this->QUERY_SQL($sql,'artica_events');
		}	
		
		
	 $this->QUERY_SQL("CREATE TABLE IF NOT EXISTS `artica_backup`.`whitelist_ign2` (`signatures` VARCHAR( 128 ) NOT NULL PRIMARY KEY)");
	
	
		//ALTER TABLE `quarantine` CHANGE `file_path` `file_path` VARCHAR( 255 ) NOT NULL 
		
		if(!$this->TABLE_EXISTS('spam_events','artica_events')){
			
			$sql="CREATE TABLE IF NOT EXISTS `spam_events` (
					  `msgid` varchar(90) NOT NULL,
					  `zDate` datetime NOT NULL,
					  `mailfrom` varchar(255) NOT NULL,
					  `rcpt_to` varchar(255) NOT NULL,
					  `filter` int(11) NOT NULL,
					  `ipfrom` varchar(25) NOT NULL,
					  `mailfrom_domain` varchar(255) NOT NULL,
					  `rcpt_to_domain` varchar(255) NOT NULL,
					  `spam_level` varchar(50) NOT NULL,
					  PRIMARY KEY  (`msgid`),
					  KEY `mailfrom` (`mailfrom`),
					  KEY `rcpt_to` (`rcpt_to`),
					  KEY `filter` (`filter`),
					  KEY `zDate` (`zDate`),
					  KEY `ipfrom` (`ipfrom`),
					  KEY `mailfrom_domain` (`mailfrom_domain`),
					  KEY `rcpt_to_domain` (`rcpt_to_domain`),
					  KEY `spam_level` (`spam_level`)
					) ENGINE=MYISAM;";
						
				 $this->QUERY_SQL($sql,'artica_events');
		}	
		
			
		if(!$this->DATABASE_EXISTS('artica_backup')){if(!$this->CREATE_DATABASE('artica_backup')){return null;}}		

		if(!$this->TABLE_EXISTS('imapsync','artica_backup')){
			if($GLOBALS["DEBUG"]){writelogs("Table imapsync does not exists....",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			$sql="CREATE TABLE IF NOT EXISTS `imapsync` (
			  `ID` int(11) NOT NULL AUTO_INCREMENT,
			  `uid` varchar(255) NOT NULL,
			  `imap_server` varchar(255) NOT NULL,
			  `username` varchar(255) NOT NULL,
			  `password` varchar(255) NOT NULL,
			  `ssl` smallint(1) NOT NULL DEFAULT '0',
			  `enabled` smallint(1) NOT NULL,
			  `state` smallint(1) NOT NULL,
			  `state_event` longtext NOT NULL,
			  `pid` int(5) NOT NULL,
			  `zDate` datetime NOT NULL,
			  `CronSchedule` varchar(90) NOT NULL,
			  `parameters` text NOT NULL,
			  `folders` text NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `uid` (`uid`),
			  KEY `imap_server` (`imap_server`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
			if($GLOBALS["DEBUG"]){writelogs("$sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			if(!$this->ok){echo "$sql\n$this->mysql_error\n";}else{writelogs("SUCCESS!",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		
		
	if(!$this->FIELD_EXISTS("imapsync","parameters","artica_backup")){
		$sql="ALTER TABLE `imapsync` ADD `parameters` text NOT NULL";
		$this->QUERY_SQL($sql,"artica_backup");
	}		
		
		if(!$this->FIELD_EXISTS("imapsync","folders","artica_backup")){
		$sql="ALTER TABLE `imapsync` ADD `folders` text NOT NULL";
		$this->QUERY_SQL($sql,"artica_backup");
	}
		
		if(!$this->TABLE_EXISTS('dspam_stats','artica_events')){	
		$sql="create table dspam_stats (
				  uid smallint unsigned primary key,
				  spam_learned int not null,
				  innocent_learned int not null,
				  spam_misclassified int not null,
				  innocent_misclassified int not null,
				  spam_corpusfed int not null,
				  innocent_corpusfed int not null,
				  spam_classified int not null,
				  innocent_classified int not null
				) ENGINE=MYISAM;";
		
		$this->QUERY_SQL($sql,'artica_events');
		}
		
		if(!$this->TABLE_EXISTS('dspam_token_data','artica_events')){	
		$sql="create table dspam_token_data (
			  uid smallint unsigned not null,
			  token bigint unsigned not null,
			  spam_hits int not null,
			  innocent_hits int not null,
			  last_hit date not null
			) ENGINE=MYISAM PACK_KEYS=1;";
		$this->QUERY_SQL($sql,'artica_events');
		}
		
		if(!$this->TABLE_EXISTS('dspam_signature_data','artica_events')){	
		$sql="create table dspam_signature_data (
			  uid smallint unsigned not null,
			  signature char(32) not null,
			  data blob not null,
			  length smallint not null,
			  created_on date not null
			) ENGINE=MYISAM max_rows=2500000 avg_row_length=8096;";	
		$this->QUERY_SQL($sql,'artica_events');
		}	
		
		
		$sql="CREATE TABLE IF NOT EXISTS `mirror_logs` (
			 `ID` BIGINT UNSIGNED NOT NULL auto_increment,
			 `zDate` datetime NOT NULL,
			 `pid` BIGINT UNSIGNED NOT NULL,
			 `starton` BIGINT UNSIGNED NOT NULL,
			 `endon` BIGINT UNSIGNED NOT NULL,
			 `filesnumber` BIGINT UNSIGNED NOT NULL,
			 `totalsize` BIGINT UNSIGNED NOT NULL,
			 `error` varchar(255),
			  PRIMARY KEY  (`ID`),
			  KEY `zDate` (`zDate`),
			  KEY `pid` (`pid`),
			  KEY `starton` (`starton`),
			  KEY `filesnumber` (`filesnumber`),
			  KEY `totalsize` (`totalsize`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_events');
		
		
		if(!$this->TABLE_EXISTS('smtp_logs','artica_events')){	
				$sql="CREATE TABLE IF NOT EXISTS `smtp_logs` (
				  `id` BIGINT UNSIGNED NOT NULL auto_increment,
				  `newmsg_id` bigint(20) default NULL,
				  `bounce_qp` int(11) default NULL,
				  `bytes` int(11) NOT NULL default '0',
				  `sender_user` varchar(128) NOT NULL default '',
				  `sender_domain` varchar(128) NOT NULL default '',
				  `delivery_id` bigint(20) default NULL,
				  `delivery_user` varchar(128) NOT NULL default '',
				  `delivery_domain` varchar(128) NOT NULL default '',
				  `delivery_success` enum('yes','no') NOT NULL default 'no',
				  `time_stamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
				  `delivery_id_text` varchar(128) NOT NULL,
				  `msg_id_text` varchar(128) NOT NULL,
				  `time_connect` datetime NOT NULL,
				  `time_sended` datetime NOT NULL,
				  `bounce_error` varchar(255) NOT NULL,
				  `SPAM` int(11) NOT NULL,
				  `filter_reject` varchar(255) NOT NULL,
				  `time_amavis` datetime NOT NULL,
				  `Country` varchar(25) NOT NULL,
				  `Region` varchar(30) default NULL,
				  `kas` int(3) NOT NULL default '0',
				  `infected` smallint(1) NOT NULL default '0',
				  `spammy` smallint(1) NOT NULL default '0',
				  `blacklisted` smallint(1) NOT NULL default '0',
				  `whitelisted` smallint(1) NOT NULL default '0',
				  `smtp_sender` varchar(55) default NULL,
				  `transaction` TEXT NOT NULL,
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `bounce_qp` (`bounce_qp`),
				  UNIQUE KEY `newmsg_id` (`newmsg_id`),
				  KEY `sender_domain` (`sender_domain`),
				  KEY `delivery_domain` (`delivery_domain`),
				  KEY `delivery_id_text` (`delivery_id_text`),
				  KEY `msg_id_text` (`msg_id_text`),
				  KEY `time_connect` (`time_connect`),
				  KEY `time_sended` (`time_sended`),
				  KEY `SPAM` (`SPAM`),
				  KEY `filter_reject` (`filter_reject`),
				  KEY `time_amavis` (`time_amavis`),
				  KEY `Country` (`Country`),
				  KEY `Region` (`Region`),
				  KEY `kas` (`kas`),
				  KEY `infected` (`infected`),
				  KEY `spammy` (`spammy`),
				  KEY `blacklisted` (`blacklisted`),
				  KEY `whitelisted` (`whitelisted`),
				  KEY `smtp_sender` (`smtp_sender`)
				) ENGINE=MYISAM;";	
				$this->QUERY_SQL($sql,'artica_events');
		}else{
			$this->Check_smtp_logs_table();		
		}

		if(!$this->TABLE_EXISTS('dspam_preferences','artica_events')){	
		$sql="
			CREATE TABLE IF NOT EXISTS `dspam_preferences` (
			  `uid` smallint(5) unsigned NOT NULL,
			  `preference` varchar(32) NOT NULL,
			  `value` varchar(64) NOT NULL,
			  UNIQUE KEY `id_preferences_01` (`uid`,`preference`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_events');
		}
		
		if(!$this->TABLE_EXISTS('dspam_virtual_uids','artica_events')){	
		$sql="create table dspam_virtual_uids (
			  uid smallint unsigned primary key AUTO_INCREMENT,
			  username varchar(128)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_events');
		}		
				
		

	
	if(!$this->TABLE_EXISTS('storage','artica_backup')){		
	$sql="CREATE TABLE IF NOT EXISTS `storage` (
			  `MessageID` varchar(80) NOT NULL,
			  `zDate` datetime NOT NULL,
			  `mailfrom` varchar(255) NOT NULL,
			  `subject` varchar(255) NOT NULL,
			  `MessageBody` text NOT NULL,
			  `organization` varchar(80) NOT NULL,
			  `uid` varchar(50) NOT NULL,
			  `file_path` varchar(255) default NULL,
			  `original_messageid` varchar(80) NOT NULL,
			  `message_size` int(10) NOT NULL,
			  `mailto` varchar(255) NOT NULL,
			  `mailfrom_domain` varchar(255) NOT NULL,
			  `commandline` varchar(200) NOT NULL,
			  `ExecuteAfter` varchar(200) NOT NULL,
			  `BinMessg` longblob NOT NULL,
			  `filename` varchar(128) NOT NULL,
			  `filesize` int(10) NOT NULL,
			  PRIMARY KEY  (`MessageID`),
			  KEY `MessageID` (`MessageID`),
			  KEY `mailfrom` (`mailfrom`),
			  KEY `organization` (`organization`),
			  KEY `uid` (`uid`),
			  KEY `file_path` (`file_path`),
			  KEY `original_messageid` (`original_messageid`),
			  KEY `mailto` (`mailto`),
			  KEY `mailfrom_domain` (`mailfrom_domain`),
			  KEY `filename` (`filename`),
			  FULLTEXT KEY `MessageBody` (`MessageBody`)
			) ENGINE=MyISAM;";
		$this->QUERY_SQL($sql,'artica_backup');
	}
	
	
	
	if(!$this->TABLE_EXISTS('storage_recipients','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `storage_recipients` (
				  `id` int(10) NOT NULL auto_increment,
				  `MessageID` varchar(80) NOT NULL,
				  `recipient` varchar(255) NOT NULL,
				  `recipient_domain` varchar(255) NOT NULL,
				  	PRIMARY KEY  (`id`),
				  	KEY `MessageID` (`MessageID`),
				  	KEY `recipient` (`recipient`),
				  	KEY `recipient_domain` (`recipient_domain`)
				) ENGINE=MyISAM;";
	
			$this->QUERY_SQL($sql,'artica_backup');
	}
	
	
	
	
	
	if(!$this->TABLE_EXISTS('orgmails','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `orgmails` (
			  `MessageID` varchar(255) NOT NULL,
			  `message_path` varchar(255) NOT NULL,
			  `MessageSize` int(20) NOT NULL,
			  PRIMARY KEY  (`MessageID`),
			  UNIQUE KEY `message_path` (`message_path`)
			) ENGINE=MyISAM;";
	
			$this->QUERY_SQL($sql,'artica_backup');
	}	
	
	if(!$this->TABLE_EXISTS('artica_whitelist','artica_backup')){	
			$sql=" CREATE TABLE IF NOT EXISTS `artica_backup`.`artica_whitelist` (
						`zMD5` VARCHAR( 90 ) NOT NULL ,
						`mailfrom` VARCHAR( 255 ) NOT NULL ,
						`mailto` VARCHAR( 255 ) NOT NULL ,
						`zDate` DATETIME NOT NULL ,
						PRIMARY KEY ( `zMD5` ) ,
						KEY `mailfrom` (`mailfrom`),
						KEY `mailto` (`mailto`),
						INDEX ( `zDate` )) ENGINE=MyISAM;";
			$this->QUERY_SQL($sql,'artica_backup');
	
 			}
 			
 			
	if(!$this->TABLE_EXISTS('notify','artica_events')){	
			$sql="CREATE TABLE IF NOT EXISTS `notify` (
				  `daemon` varchar(20) NOT NULL,
				  `event_text` varchar(255) NOT NULL,
				  `error_id` int(2) NOT NULL,
				  `zMD5` varchar(80) NOT NULL,
				  PRIMARY KEY  (`zMD5`),
				  KEY `daemon` (`daemon`,`error_id`)
				) ENGINE=MyISAM;";
			$this->QUERY_SQL($sql,'artica_events');
			}
			
	if(!$this->TABLE_EXISTS('audit2','artica_events')){			
		$sql="CREATE TABLE IF NOT EXISTS `audit2` (
			  `id` INT UNSIGNED unsigned NOT NULL auto_increment,
			  `log_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
			  `username` varchar(18) default '',
			  `useraddr` varchar(18) default '',
			  `op_name` varchar(255) default '',
			  `err_msg` text,
			  `op_msg` text,
			  `share` varchar(255) default '',
			  `cnt` smallint(6) default '1',
			  PRIMARY KEY  (`id`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_events');
		}
		
		

		
		
		
	
		$sql="CREATE TABLE IF NOT EXISTS `sys_events` (
				  `md5` varchar(90) NOT NULL,
				  `ID` int(20) NOT NULL auto_increment,
				  `zDate` datetime NOT NULL,
				  `type` smallint(1) NOT NULL,
				  `event_text` varchar(255) NOT NULL,
				  `daemon` varchar(20) NOT NULL,
				  `msg_id` varchar(20) default NULL,
				  PRIMARY KEY  (`ID`),
				  UNIQUE KEY `md5` (`md5`),
				  KEY `type` (`type`,`daemon`),
				  KEY `msg_id` (`msg_id`)
				) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_events');
				
		
	if(!$this->TABLE_EXISTS('what2','artica_events')){			
		$sql="CREATE TABLE what2 (
			  what varchar(50) NOT NULL,
			  visible smallint(1) default '0',
			  description varchar(255) default '',
			  PRIMARY KEY  (what)
			) ENGINE=MYISAM;";
		
		$this->QUERY_SQL($sql,'artica_events');
		
		$sql="INSERT INTO `what2` (`what`, `visible`, `description`) VALUES ('connect', 1, ''),
				('disconnect', 1, ''),
				('disk_free', 0, ''),
				('get_quota', 0, ''),
				('set_quota', 0, ''),
				('get_shadow_copy_data', 0, ''),
				('statvfs', 0, ''),
				('opendir', 1, ''),
				('readdir', 0, ''),
				('seekdir', 0, ''),
				('telldir', 0, ''),
				('rewinddir', 0, ''),
				('mkdir', 1, ''),
				('rmdir', 1, ''),
				('closedir', 1, ''),
				('open', 1, ''),
				('close', 1, ''),
				('read', 1, ''),
				('pread', 0, ''),
				('write', 1, ''),
				('pwrite', 0, ''),
				('lseek', 0, ''),
				('rename', 1, ''),
				('fsync', 0, ''),
				('stat', 0, ''),
				('fstat', 0, ''),
				('lstat', 0, ''),
				('unlink', 1, ''),
				('chmod', 1, ''),
				('fchmod', 1, ''),
				('chown', 0, ''),
				('fchown', 0, ''),
				('chdir', 1, ''),
				('getwd', 0, ''),
				('utime', 1, ''),
				('ftruncate', 0, ''),
				('lock', 0, ''),
				('symlink', 0, ''),
				('readlink', 0, ''),
				('link', 0, ''),
				('mknod', 0, ''),
				('realpath', 0, ''),
				('fget_nt_acl', 0, ''),
				('get_nt_acl', 0, ''),
				('fset_nt_acl', 0, ''),
				('set_nt_acl', 0, ''),
				('chmod_acl', 1, ''),
				('fchmod_acl', 1, ''),
				('sys_acl_get_entry', 0, ''),
				('sys_acl_get_tag_type', 0, ''),
				('sys_acl_get_permset', 0, ''),
				('sys_acl_get_qualifier', 0, ''),
				('sys_acl_get_file', 0, ''),
				('sys_acl_get_fd', 0, ''),
				('sys_acl_clear_perms', 0, ''),
				('sys_acl_add_perm', 0, ''),
				('sys_acl_to_text', 0, ''),
				('sys_acl_init', 0, ''),
				('sys_acl_create_entry', 0, ''),
				('sys_acl_set_tag_type', 0, ''),
				('sys_acl_set_qualifier', 0, ''),
				('sys_acl_set_permset', 0, ''),
				('sys_acl_valid', 0, ''),
				('sys_acl_set_file', 0, ''),
				('sys_acl_set_fd', 0, ''),
				('sys_acl_delete_def_file', 0, ''),
				('sys_acl_get_perm', 0, ''),
				('sys_acl_free_text', 0, ''),
				('sys_acl_free_acl', 0, ''),
				('sys_acl_free_qualifier', 0, ''),
				('getxattr', 0, ''),
				('lgetxattr', 0, ''),
				('fgetxattr', 0, ''),
				('listxattr', 0, ''),
				('llistxattr', 0, ''),
				('flistxattr', 0, ''),
				('removexattr', 0, ''),
				('lremovexattr', 0, ''),
				('fremovexattr', 0, ''),
				('setxattr', 0, ''),
				('lsetxattr', 0, ''),
				('fsetxattr', 0, ''),
				('aio_read', 0, ''),
				('aio_write', 0, ''),
				('aio_return', 0, ''),
				('aio_cancel', 0, ''),
				('aio_error', 0, ''),
				('aio_fsync', 0, ''),
				('aio_suspend', 0, '')";
		$this->QUERY_SQL($sql,'artica_events');
		
	}
	
	
	if(!$this->TABLE_EXISTS('shares','artica_events')){			
		$sql="CREATE TABLE IF NOT EXISTS `shares` (
			  `id` int(11) NOT NULL auto_increment,
			  `share` varchar(255) NOT NULL,
			  UNIQUE KEY `id` (`id`)
			) AUTO_INCREMENT=1 ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_events');
	}
	
	if(!$this->TABLE_EXISTS('dansguardian_sites','artica_events')){
		$sql="CREATE TABLE IF NOT EXISTS `dansguardian_sites` (
				`website_md5` VARCHAR( 90 ) NOT NULL ,
				`website` VARCHAR( 255 ) NOT NULL ,
				UNIQUE (`website_md5`)
				) ENGINE=MYISAM;";
		
		$this->QUERY_SQL($sql,'artica_events');
		
	}
	
	if(!$this->TABLE_EXISTS('dansguardian_sitesinfos','artica_backup')){
		$sql="CREATE TABLE IF NOT EXISTS `dansguardian_sitesinfos` (
		  `website` varchar(255) NOT NULL,
		  `category` varchar(255) NOT NULL,
		  `community` smallint(1) NOT NULL DEFAULT '0',
		  `squidguard` smallint(1) NOT NULL DEFAULT '0',
		  `dbpath` varchar(255) NOT NULL,
		  `country` varchar(255) NOT NULL DEFAULT 'unknown',
		  `ipaddr` varchar(40) NOT NULL,
		  UNIQUE KEY `website` (`website`),
		  KEY `dbpath` (`dbpath`),
		  KEY `category` (`category`),
		  KEY `community` (`community`),
		  KEY `country` (`country`),
		  KEY `ipaddr` (`ipaddr`),
		  KEY `squidguard` (`squidguard`)) ENGINE=MYISAM;";

		$this->QUERY_SQL($sql,'artica_backup');
		if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__LINE__);}
	}
	
	if(!$this->TABLE_EXISTS('dansguardian_uris','artica_events')){
				$sql="CREATE TABLE IF NOT EXISTS `dansguardian_uris` (
			  `uri_MD5` varchar(90) NOT NULL,
			  `uri` varchar(255) NOT NULL,
			  `site_md5` varchar(90) NOT NULL,
			  UNIQUE KEY `uri_MD5` (`uri_MD5`),
			  KEY `site_md5` (`site_md5`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,'artica_events');
		}

		
	
	if(!$this->TABLE_EXISTS("mails_events","artica_events")){
		$sql="CREATE TABLE IF NOT EXISTS `mails_events` (
			  `ID` INT UNSIGNED NOT NULL auto_increment,
			  `mailfrom` varchar(255) NOT NULL,
			  `rcpt_to` varchar(255) NOT NULL,
			  `zDate` datetime NOT NULL,
			  `mailfrom_domain` varchar(255) NOT NULL,
			  `rcpt_to_domain` varchar(255) default NULL,
			  `relayhost` varchar(20) NOT NULL,
			  `Country` varchar(75) NOT NULL,
			  `Region` varchar(55) NOT NULL,
			  PRIMARY KEY  (`ID`),
			  KEY `rcpt_to_domain` (`rcpt_to_domain`),
			  KEY `rcpt_to` (`rcpt_to`),
			  KEY `zDate` (`zDate`),
			  KEY `mailfrom_domain` (`mailfrom_domain`),
			  KEY `relayhost` (`relayhost`),
			  KEY `Country` (`Country`),
			  KEY `Region` (`Region`)
				) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_events');
	}
	
	if(!$this->FIELD_EXISTS("mails_events","mailto","artica_events")){	
		$sql="ALTER TABLE `mails_events` ADD `mailto` VARCHAR( 255 ) NOT NULL ,ADD INDEX ( mailto )";	
		$this->QUERY_SQL($sql,'artica_events');
	}
	if(!$this->FIELD_EXISTS("mails_events","Country","artica_events")){
		$sql="ALTER TABLE `mails_events` ADD `Country` VARCHAR( 75 ) NOT NULL ,ADD INDEX ( Country )";
		$this->QUERY_SQL($sql,'artica_events');
	}
	
	if(!$this->FIELD_EXISTS("mails_events","Region","artica_events")){
		$sql="ALTER TABLE `mails_events` ADD `Region` VARCHAR( 55 ) NOT NULL ,ADD INDEX ( Region )";
		$this->QUERY_SQL($sql,'artica_events');
	}	
	
	if(!$this->FIELD_EXISTS("mails_events","City","artica_events")){
		$sql="ALTER TABLE `mails_events` ADD `City` VARCHAR( 75 ) NOT NULL ,ADD INDEX ( City )";
		$this->QUERY_SQL($sql,'artica_events');
	}	
	
	
	if(!$this->FIELD_EXISTS("mails_events","message_id","artica_events")){
		$sql="ALTER TABLE `mails_events` ADD `message_id` VARCHAR(120) NOT NULL ,ADD INDEX (message_id)";
		$this->QUERY_SQL($sql,'artica_events');
	}

	if(!$this->FIELD_EXISTS("mails_events","subject","artica_events")){
		$sql="ALTER TABLE `mails_events` ADD `subject` VARCHAR(120) NOT NULL ,ADD INDEX (subject)";
		$this->QUERY_SQL($sql,'artica_events');
	}		
	
	if(!$this->FIELD_EXISTS("mails_events","infected","artica_events")){
		$sql="ALTER TABLE `mails_events` ADD `infected` smallint(1) NOT NULL ,ADD INDEX ( infected )";
		$this->QUERY_SQL($sql,'artica_events');
	}		
	
	if(!$this->FIELD_EXISTS("mails_events","spammy","artica_events")){
		$sql="ALTER TABLE `mails_events` ADD `spammy` smallint(1) NOT NULL ,ADD INDEX ( spammy )";
		$this->QUERY_SQL($sql,'artica_events');
	}

	if(!$this->FIELD_EXISTS("mails_events","spam","artica_events")){
		$sql="ALTER TABLE `mails_events` ADD `spam` smallint(1) NOT NULL ,ADD INDEX ( spam )";
		$this->QUERY_SQL($sql,'artica_events');
	}		

	if(!$this->FIELD_EXISTS("mails_events","banned","artica_events")){
		$sql="ALTER TABLE `mails_events` ADD `banned` smallint(1) NOT NULL ,ADD INDEX (banned)";
		$this->QUERY_SQL($sql,'artica_events');
	}	
}

public	function CheckTables_repositories(){
		
		$sql="CREATE TABLE IF NOT EXISTS `debian_packages_logs` (
			  `ID` int(5) NOT NULL auto_increment,
			  `zDate` datetime NOT NULL,
			  `package_name` varchar(255) NOT NULL,
			  `events` text NOT NULL,
			  `install_type` varchar(50) NOT NULL,
			  PRIMARY KEY  (`ID`),
			  KEY `package_name` (`package_name`),
			  KEY `install_type` (`install_type`)
			)  ENGINE=MYISAM;";
		
		$this->QUERY_SQL($sql,'artica_backup');
		

		

		if(!$this->TABLE_EXISTS('logon_scripts','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`logon_scripts` (`gpid` INT( 10 ) NOT NULL ,`script_code` TEXT NOT NULL ,PRIMARY KEY ( `gpid` ))";
			$this->QUERY_SQL($sql,'artica_backup');
		}
		
		if(!$this->TABLE_EXISTS('logon_scriptsusers','artica_backup')){	
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`logon_scriptsusers` (`uid` VARCHAR( 90 ) NOT NULL ,`script_code` TEXT NOT NULL ,PRIMARY KEY ( `uid` ))";
			$this->QUERY_SQL($sql,'artica_backup');
		}		
		
		
	}
public function CheckTables_openvpn(){
		if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}
		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`hamachi` (
		`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`pattern` TEXT NOT NULL)  ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,'artica_backup');
		include_once(dirname(__FILE__)."/class.patch.tables.fw.inc");
		patch_vpn_tables();
		
		

	}
	public function  CheckTables_qos(){
		
		if($this->TABLE_EXISTS('qos_eth','artica_backup')){
			$this->QUERY_SQL("DROP TABLE `qos_eth`",'artica_backup');
		}
		
		if($this->TABLE_EXISTS('qos_class','artica_backup')){
			$this->QUERY_SQL("DROP TABLE `qos_class`",'artica_backup');
		}
	
		
		

		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`qos_rules` (
			`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`class_id` INT( 5 ) NOT NULL ,
			`name` VARCHAR( 255 ) NOT NULL ,
			`sip` VARCHAR( 255 ) NOT NULL ,
			`sport` VARCHAR( 255 ) NOT NULL ,
			`dip` VARCHAR( 255 ) NOT NULL ,
			`dport` VARCHAR( 255 ) NOT NULL ,
			`proto` VARCHAR( 5 ) NOT NULL ,
			`enabled` INT( 1 ) NOT NULL,
			KEY `class_id` (`class_id`),
			KEY `enabled` (`enabled`)
			) ENGINE=MYISAM"; 		
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs($this->mysql_error,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			

		
	}
	

	
	public function CheckTables_dar(){

	}
	public function CheckTable_dansguardian(){


		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `dansguardian_groups` (
			  `ID` int(11) NOT NULL AUTO_INCREMENT,
			  `RuleID` int(5) NOT NULL,
			  `group_id` int(10) NOT NULL,
			  `dngroup` VARCHAR( 255 ) NOT NULL,
			  PRIMARY KEY (`ID`),
			  UNIQUE KEY `group_id` (`group_id`),
			  KEY `RuleID` (`RuleID`)
			) ENGINE=MYISAM";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){$this->mysql_error=$this->mysql_error."\n$sql";writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		
		
		if(!$this->FIELD_EXISTS("dansguardian_groups","dngroup","artica_backup")){
			$sql="ALTER TABLE `dansguardian_groups` ADD `dngroup` VARCHAR(255) NOT NULL";
			$this->QUERY_SQL($sql,"artica_backup");
			if(!$this->ok){writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			}		
		
		
			
			$sql="CREATE TABLE IF NOT EXISTS `dansguardian_personal_categories` (
				  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				  `category` varchar(255) NOT NULL,
				  `pattern` varchar(255) NOT NULL,
				  `category_type` varchar(80) NOT NULL,
				  `RuleID` int(10) NOT NULL,
				  `PublicMode` smallint(1) NOT NULL,
				  `Meta` smallint(1) NOT NULL,
				  PRIMARY KEY (`ID`),
				  KEY `category` (`category`),
				  KEY `pattern`(`pattern`),
				  KEY `category_type` (`category_type`),
				  KEY `PublicMode` (`PublicMode`),
				  KEY `Meta` (`Meta`),
				  KEY `RuleID` (`RuleID`)
				) ENGINE=MYISAM";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){$this->mysql_error=$this->mysql_error."\n$sql";}
			
			if(!$this->FIELD_EXISTS("dansguardian_personal_categories","Meta","artica_backup")){
				$sql="ALTER TABLE `dansguardian_personal_categories` ADD `Meta` smallint(1) NOT NULL ,ADD INDEX ( `Meta` )";
				$this->QUERY_SQL($sql,"artica_backup");
				if(!$this->ok){writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
			}


		

		
		
			 $sql="CREATE TABLE IF NOT EXISTS `dansguardian_files` (
			  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			  `filename` varchar(50) NOT NULL,
			  `pattern` text NOT NULL,
			  `infos` varchar(125) NOT NULL,
			  `RuleID` int(2) NOT NULL,
			  `enabled` smallint(1) NOT NULL DEFAULT '1',
			  `userdefined` smallint(1) NOT NULL DEFAULT '0',
			  `uid` VARCHAR( 90 ) NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `filename` (`filename`),
			  KEY `RuleID` (`RuleID`),
			  KEY `enabled` (`enabled`),
			  KEY `userdefined` (`userdefined`),
			  KEY `uid` (`uid`)
			) ENGINE=MYISAM";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){$this->mysql_error=$this->mysql_error."\n$sql";}
		
		
		if(!$this->FIELD_EXISTS("dansguardian_files","uid","artica_backup")){
			$sql="ALTER TABLE `dansguardian_files` ADD `uid` VARCHAR( 90 ) NOT NULL ,ADD INDEX ( `uid` )";
			$this->QUERY_SQL($sql,"artica_backup");
			if(!$this->ok){writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
		}
		if(!$this->TABLE_EXISTS('loop_disks','artica_backup')){
			 $sql="CREATE TABLE IF NOT EXISTS `loop_disks` (
				  `path` varchar(255) NOT NULL,
				  `size` BIGINT UNSIGNED NOT NULL,
			 	  `maxfds` BIGINT UNSIGNED NOT NULL,
				  `loop_dev` varchar(128) NOT NULL,
				  `disk_name` varchar(255) NOT NULL,
				  UNIQUE KEY `path` (`path`),
				  UNIQUE KEY `disk_name` (`disk_name`),
				  KEY `size` (`size`,`loop_dev`)
				) ENGINE=MYISAM";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){$this->mysql_error=$this->mysql_error."\n$sql";}	 
		}

		if(!$this->FIELD_EXISTS("loop_disks","disk_name","artica_backup")){
			$this->QUERY_SQL('ALTER TABLE `loop_disks` ADD `disk_name` VARCHAR( 255 ) NOT NULL,ADD UNIQUE (`disk_name`)','artica_backup');
			if(!$this->ok){$this->mysql_error=$this->mysql_error."\n$sql";}
		}	
		if(!$this->FIELD_EXISTS("loop_disks","maxfds","artica_backup")){
			$this->QUERY_SQL('ALTER TABLE `loop_disks` ADD `maxfds` bigint( 10 ) NOT NULL','artica_backup');
			if(!$this->ok){$this->mysql_error=$this->mysql_error."\n$sql";}
		}			 
		
		if(!$this->TABLE_EXISTS('dansguardian_rules','artica_backup')){
			 $sql="CREATE TABLE IF NOT EXISTS `dansguardian_rules` (
				  `RuleID` smallint(1) NOT NULL AUTO_INCREMENT,
				  `RuleOrder` smallint(1) NOT NULL,
				  `RuleName` varchar(120) NOT NULL,
				  `RuleText` text NOT NULL,
				  PRIMARY KEY (`RuleID`),
				  KEY `RuleOrder` (`RuleOrder`,`RuleName`)) ENGINE=MYISAM";
			 $this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){$this->mysql_error=$this->mysql_error."\n$sql";}
		}
		
		if(!$this->TABLE_EXISTS('dansguardian_weightedphraselist','artica_backup')){
			 $sql="CREATE TABLE IF NOT EXISTS `dansguardian_weightedphraselist` (
			  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			  `category_name` varchar(75) NOT NULL,
			  `pattern` varchar(255) NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `category_name` (`category_name`,`pattern`)) ENGINE=MYISAM";
			 $this->QUERY_SQL($sql,'artica_backup'); 
			 
			if(!$this->ok){
				writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->mysql_error=$this->mysql_error."\n$sql";}
			} 
		
		if(!$this->TABLE_EXISTS('dansguardian_ipgroups','artica_backup')){
			 $sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`dansguardian_ipgroups` (
				`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
				`pattern` VARCHAR( 128 ) NOT NULL ,
				`RuleID` INT( 1 ) NOT NULL ,
				INDEX ( `pattern` , `RuleID` )
				) ENGINE=MYISAM";
			 $this->QUERY_SQL($sql,'artica_backup'); 
			 
			 if(!$this->ok){
			 	writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			 	$this->mysql_error=$this->mysql_error."\n$sql";
			 }
		} 
		
		
		

		if(!$this->TABLE_EXISTS('dansguardian_whitelists','artica_events')){
			 $sql="CREATE TABLE IF NOT EXISTS `dansguardian_whitelists` (
				  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				  `zDate` datetime NOT NULL,
				  `uri` varchar(255) NOT NULL,
				  `uid` varchar(255) NOT NULL,
				  PRIMARY KEY (`ID`),
				  UNIQUE KEY `ID` (`ID`),
				  KEY `uri` (`uri`),
				  KEY `uid` (`uid`)
				) ENGINE=MYISAM";
			 $this->QUERY_SQL($sql,'artica_events'); 
			 
			 if(!$this->ok){
			 	$this->mysql_error=$this->mysql_error."\n$sql";
			 	writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			 }
		}
		

		

	
		if(!$this->FIELD_EXISTS("dansguardian_ipgroups","uid","artica_backup")){
				$sql="ALTER TABLE `dansguardian_ipgroups` ADD `uid` VARCHAR( 90 ) NOT NULL ";
				$this->QUERY_SQL($sql,"artica_backup");
				if(!$this->ok){writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
			}		
			
		
	
		$this->CheckTablesSquid();
		return true;
		
		
	}

	public function CheckTablesSquid(){
		if(!$this->TABLE_EXISTS('squid_block','artica_backup')){
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`squid_block` (
			`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`uri` VARCHAR( 255 ) NOT NULL ,`task_type` VARCHAR( 90 ) NOT NULL ,
			`zDate` DATETIME NOT NULL ,INDEX ( `task_type` , `zDate` ) ,UNIQUE (`uri`)) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,"artica_backup");
		}
		
		if(!$this->TABLE_EXISTS('squid_white','artica_backup')){
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`squid_white` (
			`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`uri` VARCHAR( 255 ) NOT NULL ,`task_type` VARCHAR( 90 ) NOT NULL ,
			`zDate` DATETIME NOT NULL ,INDEX ( `task_type` , `zDate` ) ,UNIQUE (`uri`)) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,"artica_backup");
		}	
//squid_nodes_settings

		

		
	
		
		

		
			


				
		
		
		if(!$this->TABLE_EXISTS('squid_speed','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`squid_speed` (
			`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`domain` VARCHAR( 255 ) NOT NULL ,
			`refresh_pattern_min` INT( 5 ) NOT NULL DEFAULT '0',
			`refresh_pattern_perc` INT( 2 ) NOT NULL DEFAULT '20',
			`refresh_pattern_max` INT( 5 ) NOT NULL DEFAULT '4320',
			`refresh_pattern_options` VARCHAR( 255 ) NOT NULL ,
			UNIQUE KEY `domain` (`domain`),
			INDEX ( `domain` )
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,"artica_backup");	
		}
		

		
		

		

		
		
			
		
		if(!$this->TABLE_EXISTS('squid_ssl','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`squid_ssl` (
			`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`website_name` VARCHAR( 255 ) NOT NULL ,
			`type` VARCHAR( 40 ) NOT NULL ,
			`enabled` INT( 1 ) NOT NULL DEFAULT '0',
			INDEX ( `type` , `enabled`,`website_name` )) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,"artica_backup");
			}
			
		if(!$this->TABLE_EXISTS('squid_header_access','artica_backup')){		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`squid_header_access` (
			`header` VARCHAR( 90 ) NOT NULL PRIMARY KEY,
			`replacewith` VARCHAR(255),
			`active` smallint( 1 ) NOT NULL ,
			`allow` smallint( 1 ) NOT NULL DEFAULT '1',
			INDEX ( `allow`,`active`,`header` ),
			KEY `replacewith` (`replacewith`)
			) ENGINE=MYISAM;";
			$this->QUERY_SQL($sql,"artica_backup");
			if($this->ok){
				$this->QUERY_SQL("INSERT IGNORE INTO squid_header_access (header) VALUES ('Allow'),('Authorization'),('WWW-Authenticate'),('Proxy-Authorization'),('Proxy-Authenticate'),('Cache-Control'),('Content-Encoding'),('Content-Length'),('Content-Type'),('Date'),('Expires'),('Host'),('If-Modified-Since'),('Last-Modified'),('Location'),('Pragma'),('Accept'),('Accept-Charset'),('Accept-Encoding'),('Accept-Language'),('Content-Language'),('Mime-Version'),('Retry-After'),('Title'),('Connection'),('All'),('X-Forwarded-For'),('User-Agent'),('Via'),('Referer')","artica_backup");
			}
			
		}

		

		
			
			
		if($this->FIELD_TYPE("squid_ssl","website_name","artica_backup")<>"varchar(255)"){
			$sql="ALTER TABLE `squid_ssl` CHANGE `website_name` `website_name` VARCHAR( 255 ) NOT NULL";
			$this->QUERY_SQL($sql,"artica_backup");
		}
			

	if(!$this->TABLE_EXISTS('updates_categories','artica_backup')){		
			writelogs("Creating 'updates_categories/artica_backup",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$sql="CREATE TABLE IF NOT EXISTS `updates_categories` (
				  `filename` varchar(128) NOT NULL,
				  `categories` varchar(50) NOT NULL,
				  `filesize` int(3) NOT NULL,
				  `progress` smallint(1) NOT NULL,
				  `finish` smallint(1) NOT NULL DEFAULT '0',
				  `zDate` datetime NOT NULL,
				  `subject` varchar(255) NOT NULL,
				  `zmd5` varchar(90) NOT NULL,
				  PRIMARY KEY (`filename`),
				  UNIQUE KEY `zmd5` (`zmd5`),
				  KEY `categories` (`categories`,`filesize`,`progress`,`finish`),
				  KEY `zDate` (`zDate`)
				) ENGINE=MYISAM;";		
			$this->QUERY_SQL($sql,"artica_backup");
			if(!$this->ok){
				writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			}	
	}
	
	if(!$this->FIELD_EXISTS("updates_categories","zmd5","artica_backup")){
		$sql="ALTER TABLE `updates_categories` ADD `zmd5` VARCHAR( 90 ) NOT NULL,ADD UNIQUE KEY `zmd5` (`zmd5`)";
		$this->QUERY_SQL($sql,"artica_backup");
		if(!$this->ok){writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}	
		
		if(!$this->TABLE_EXISTS("mysql_events",'artica_events')){
		$sql="CREATE TABLE IF NOT EXISTS `mysql_events` (
			  `ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			  `zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `description` text NOT NULL,
			  `function` varchar(50) NOT NULL,
			  `category` varchar(50) NOT NULL,
			  `process` varchar(50) NOT NULL,
			  `line` INT UNSIGNED NOT NULL,
			  `servername` varchar(128) NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `function` (`function`,`process`,`line`),
			  KEY `servername` (`servername`),
			  KEY `category` (`category`),
			  KEY `zDate` (`zDate`)
			) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,"artica_events"); 
		if(!$this->ok){
			writelogs("Fatal: $this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$this->mysql_error=$this->mysql_error."\n$sql";
		}

	}

	


		


		$this->squid_events_sites_day();
						
		
		if(!$this->TABLE_EXISTS('squid_adzapper','artica_backup')){	
		
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`squid_adzapper` (
				`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`uri` VARCHAR( 255 ) NOT NULL ,
				`uri_type` VARCHAR( 20 ) NOT NULL ,
				`enabled` INT( 1 ) NOT NULL DEFAULT '1',
				`zmd5` VARCHAR( 120 ) NOT NULL ,
				 KEY `uri` (`uri`),
				 KEY `uri_type` (`uri_type`),
				 KEY `enabled` (`enabled`),
				 UNIQUE (`zmd5`)) ENGINE=MYISAM;";		
		
				$this->QUERY_SQL($sql,'artica_backup');
				if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}
				
		if(!$this->TABLE_EXISTS('squid_templates','artica_backup')){			
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`squid_templates` (
			`TEMPLATE_NAME` VARCHAR( 80 ) NOT NULL ,
			`TEMPLATE_DATA` TEXT NOT NULL ,
			`TEMPLATE_DATA_SOURCE` TEXT NOT NULL ,
			 PRIMARY KEY ( `TEMPLATE_NAME` )
			 ) ENGINE=MYISAM";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
				
	
	
}	
// --------------------------------------------------------------------------------------------------------------------------------------------------	
	
	public function squid_events_sites_day_patch(){
	if(!$this->FIELD_EXISTS("squid_events_sites_day","category","artica_events")){
			$sql="ALTER TABLE `squid_events_sites_day` ADD `category` VARCHAR( 90 ) NOT NULL DEFAULT '1',ADD INDEX ( `category` )";
			$this->QUERY_SQL($sql,"artica_events");
			if(!$this->ok){
				writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			}
		}
	}
	public function squid_events_sites_day($check=true){
			if($check){
				if($this->TABLE_EXISTS('squid_events_sites_day','artica_events')){$this->squid_events_sites_day_patch();return;}
			}	
			
			$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`squid_events_sites_day` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`days` DATE NOT NULL ,
			`websites` VARCHAR( 255 ) NOT NULL ,
			`website_size` INT( 10 ) NOT NULL ,
			`website_hits` INT( 10 ) NOT NULL ,
			`category` VARCHAR( 90 ) NOT NULL,
			`country` VARCHAR( 50 ) NOT NULL,
			KEY `category` (`category`),
			KEY `country` (`country`),
			INDEX ( `days` , `websites` , `website_size` , `website_hits` )
			) ENGINE=MYISAM;"; 
			$this->QUERY_SQL($sql,"artica_events");
			if(!$this->ok){writelogs("$this->mysql_error\n$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}		
		}
	function CheckTables_rsync(){
		if(!$this->TABLE_EXISTS('rsync_events','artica_events')){	
					$sql="
						CREATE TABLE IF NOT EXISTS `rsync_events` (
					  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
					  `folder_md5` varchar(90) NOT NULL,
					  `date_start` datetime NOT NULL,
					  `date_end` datetime NOT NULL,
					  `events` text NOT NULL,
					  `path` varchar(255)  NOT NULL,
					  `failed` smallint(1) NOT NULL,
					  `sent_size` float NOT NULL,
					  `speed` float NOT NULL,
					  `numfiles` int(5) NOT NULL,
					  `storage_server` varchar(80) NOT NULL,
					  PRIMARY KEY (`ID`),
					  KEY `failed` (`failed`),
					  KEY `storage_server` (`storage_server`),
					  KEY `folder_md5` (`folder_md5`)) ENGINE=MYISAM;";		
								
					$this->QUERY_SQL($sql,'artica_events');
					if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}
				
			if(!$this->TABLE_EXISTS('dar_events','artica_events')){	
					$sql="
					CREATE TABLE IF NOT EXISTS `dar_events` (
					  `ID` INT UNSIGNED NOT NULL auto_increment,
					  `date_start` datetime NOT NULL,
					  `date_end` datetime NOT NULL,
					  `db_path` varchar(255) NOT NULL,
					  `xml` varchar(255) NOT NULL,
					  `source` varchar(255) NOT NULL,
					  `failed` smallint(1) NOT NULL default '0',
					  `builded` smallint(1) NOT NULL,
					  `error` varchar(255) NOT NULL,
					  PRIMARY KEY  (`ID`),
					  KEY `date_start`  (`date_start`),
					  KEY `date_end` (`date_end`),
					  KEY `db_path` (`db_path`),
					  KEY `xml` (`xml`),
					  KEY `source` (`source`),
					  KEY `failed` (`failed`),
					  KEY `builded` (`builded`)) ENGINE=MYISAM;";
	
					$this->QUERY_SQL($sql,'artica_events');
					if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}

		if(!$this->TABLE_EXISTS('backup_folders','artica_backup')){			
				
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`backup_folders` (
					`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
					`taskid` INT( 10 ) NOT NULL ,
					`path` VARCHAR( 255 ) NOT NULL ,
					`zmd5` VARCHAR( 90 ) NOT NULL ,
					`recursive` INT( 1 ) NOT NULL DEFAULT '0',
						KEY `taskid`  (`taskid`),
						KEY `path`  (`path`),
						KEY `zmd5`  (`zmd5`)
					) ENGINE=MYISAM;"; 
					$this->QUERY_SQL($sql,'artica_backup');
					if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	
		}
		
			if(!$this->TABLE_EXISTS('rsync_server','artica_events')){			
				
			$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`rsync_server` (
					`ID` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
					`ip_address` VARCHAR( 90 ) NOT NULL ,
					`zDate` DATETIME NOT NULL ,
					`transaction_size` INT( 20 ) NOT NULL ,
					INDEX ( `ip_address` , `zDate` )) ENGINE=MYISAM;"; 
					$this->QUERY_SQL($sql,'artica_events');
					if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	
		}

		
			if(!$this->TABLE_EXISTS('auditd_dir','artica_backup')){			
				$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`auditd_dir` (
				`key` VARCHAR( 120 ) NOT NULL ,`dir` VARCHAR( 255 ) NOT NULL ,UNIQUE (`key`)) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,'artica_backup');
				if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			}
			
			
			if(!$this->TABLE_EXISTS('auditd_files','artica_backup')){			
				$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`auditd_files` (
				`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
				`time` DATETIME NOT NULL ,
				`file` VARCHAR( 255 ) NOT NULL ,
				`syscall` VARCHAR( 20 ) NOT NULL ,
				`uid` VARCHAR( 120 ) NOT NULL ,
				`gid` VARCHAR( 120 ) NOT NULL ,
				`executable` VARCHAR( 255 ) NOT NULL ,
				`key_path` VARCHAR( 110 ) NOT NULL ,
				 KEY `time` (`time`),
				 KEY `file` (`file`),
				 KEY `syscall` (`syscall`),
				 KEY `uid` (`uid`),
				 KEY `gid` (`gid`),
				 KEY `executable` (`executable`),
				 KEY `key_path` (`key_path`)) ENGINE=MYISAM;";
				$this->QUERY_SQL($sql,'artica_backup');
				if(!$this->ok){writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			} 
			
			
			

							
	
	}
function check_networks_tables(){
	

	
	
	

	
	

	
	
}
function check_vps_tables(){
	$error=array();
	if(!$this->TABLE_EXISTS('lxc_machines','artica_backup')){		
		$sql="CREATE TABLE IF NOT EXISTS `lxc_machines` (
			  `ID` int(2) NOT NULL AUTO_INCREMENT,
			  `uid` varchar(255) NOT NULL,
			  `machine_name` varchar(255) NOT NULL,
			  `hostname` varchar(125) NOT NULL,
			  `state` varchar(40) NOT NULL,
			  `enabled` smallint(1) NOT NULL DEFAULT '1',
			  `ipaddr` varchar(40) NOT NULL,
			  `template` varchar(60) NOT NULL,
			  `autostart` smallint(1) NOT NULL,
			  `rootpwd` varchar(255) NOT NULL,
			  `root_directory` varchar(255) NOT NULL,
			  `events` blob NOT NULL,
			  `VirtualInterface` varchar(20) NOT NULL,
			  `cgroup` BLOB NOT NULL,
			  `ChangeMac` smallint(1) NOT NULL,
			  `MacAddr` VARCHAR( 55 ),
			  `UsePhys` INT( 1 ) NOT NULL DEFAULT '0',
			  `PhysNic` VARCHAR( 15 ) NOT NULL,
			  PRIMARY KEY (`ID`),
			  UNIQUE KEY `MacAddr` (`MacAddr`),
			  UNIQUE KEY `PhysNic` (`PhysNic`),
			  KEY `enabled` (`enabled`),
			  KEY `template` (`template`),
			  KEY `autostart` (`autostart`),
			  KEY `hostname` (`hostname`),
			  KEY `uid` (`uid`),
			  KEY `VirtualInterface` (`VirtualInterface`)
			) ";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){
				$error[]="$this->mysql_error (line ".__LINE__.")";
				writelogs("Fatal: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			}
			
		if(!$this->FIELD_EXISTS("lxc_machines","events","artica_backup")){	
			$sql="ALTER TABLE `lxc_machines` ADD `events` BLOB NOT NULL";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){$error[]="$this->mysql_error (line ".__LINE__.")";}
		}
		
		if(!$this->FIELD_EXISTS("lxc_machines","MacAddr","artica_backup")){	
			$sql="ALTER TABLE `lxc_machines` ADD `MacAddr` VARCHAR( 55 ),ADD UNIQUE `MacAddr` (`MacAddr`)";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){$error[]="$this->mysql_error (line ".__LINE__.")";}
		}	

		if(!$this->FIELD_EXISTS("lxc_machines","ChangeMac","artica_backup")){	
			$sql="ALTER TABLE `lxc_machines` ADD `ChangeMac` smallint(1)";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){$error[]="$this->mysql_error (line ".__LINE__.")";}
		}		

		if(!$this->FIELD_EXISTS("lxc_machines","VirtualInterface","artica_backup")){	
			$sql="ALTER TABLE `lxc_machines` ADD `VirtualInterface` VARCHAR( 20 ) NOT NULL";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){$error[]="$this->mysql_error (line ".__LINE__.")";}
		}
		
		if(!$this->FIELD_EXISTS("lxc_machines","cgroup","artica_backup")){	
			$sql="ALTER TABLE `lxc_machines` ADD `cgroup` BLOB NOT NULL ";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){$error[]="$this->mysql_error (line ".__LINE__.")";}
		}		
		
		if(!$this->FIELD_EXISTS("lxc_machines","ChangeMac","artica_backup")){	
			$sql="ALTER TABLE `lxc_machines` ADD `ChangeMac` INT( 1 ) NOT NULL DEFAULT '0',
			ADD `MacAddr` VARCHAR( 55 ) NOT NULL ,ADD UNIQUE (`MacAddr`)";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){$error[]="$this->mysql_error (line ".__LINE__.")";}
		}	
		
		if(!$this->FIELD_EXISTS("lxc_machines","UsePhys","artica_backup")){	
			$sql="ALTER TABLE `lxc_machines` ADD `UsePhys` INT( 1 ) NOT NULL DEFAULT '0', ADD `PhysNic` VARCHAR( 15 ) NOT NULL,ADD UNIQUE (`PhysNic`)";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){$error[]="$this->mysql_error (line ".__LINE__.")";}
		}		

		if(!$this->FIELD_EXISTS("lxc_machines","UsePhys","artica_backup")){	
			$sql="ALTER TABLE `lxc_machines` ADD `UsePhys` INT( 1 ) NOT NULL DEFAULT '0'";
			$this->QUERY_SQL($sql,'artica_backup');
			if(!$this->ok){$error[]="$this->mysql_error (line ".__LINE__.")";}
		}	
		
		
		if(count($error)>0){
			writelogs(@implode("\n",$error),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return @implode("<br>",$error);
		}
	}


    public function CheckSnort(){
    	
    	if(!$this->DATABASE_EXISTS("snort")){$this->CREATE_DATABASE("snort");}
    	
    	
		if(!$this->TABLE_EXISTS('schema','snort')){	
			$sql="CREATE TABLE IF NOT EXISTS `schema` ( vseq        INT      UNSIGNED NOT NULL,
		                      ctime       DATETIME NOT NULL,
		                      PRIMARY KEY (vseq));";
			
			$this->QUERY_SQL($sql,"snort");
			$this->QUERY_SQL("INSERT INTO `schema`  (vseq, ctime) VALUES ('107', now());","snort");       
		}
		
		if(!$this->TABLE_EXISTS('event','snort')){	
			$sql="CREATE TABLE IF NOT EXISTS event  ( sid 	  INT 	   UNSIGNED NOT NULL,
                      cid 	  INT 	   UNSIGNED NOT NULL,
                      signature   INT      UNSIGNED NOT NULL, 
                      timestamp 	   DATETIME NOT NULL,
                      PRIMARY KEY (sid,cid),
                      INDEX       sig (signature),
                      INDEX       time (timestamp));";		
		$this->QUERY_SQL($sql,"snort");
		}
	




	if(!$this->TABLE_EXISTS('signature','snort')){	
			$sql="CREATE TABLE IF NOT EXISTS signature ( sig_id       INT          UNSIGNED NOT NULL AUTO_INCREMENT,
                         sig_name     VARCHAR(255) NOT NULL,
                         sig_class_id INT          UNSIGNED NOT NULL,
                         sig_priority INT          UNSIGNED,
                         sig_rev      INT          UNSIGNED,
                         sig_sid      INT          UNSIGNED,
                         sig_gid      INT          UNSIGNED,
                         PRIMARY KEY (sig_id),
                         INDEX   sign_idx (sig_name(20)),
                         INDEX   sig_class_id_idx (sig_class_id));";		
		$this->QUERY_SQL($sql,"snort");
		}

	if(!$this->TABLE_EXISTS('sig_reference','snort')){	
			$sql="CREATE TABLE IF NOT EXISTS sig_reference (sig_id  INT    UNSIGNED NOT NULL,
                            ref_seq INT    UNSIGNED NOT NULL,
                            ref_id  INT    UNSIGNED NOT NULL,
                            PRIMARY KEY(sig_id, ref_seq));";		
		$this->QUERY_SQL($sql,"snort");
		}

	if(!$this->TABLE_EXISTS('reference','snort')){	
			$sql="CREATE TABLE IF NOT EXISTS reference (  ref_id        INT         UNSIGNED NOT NULL AUTO_INCREMENT,
                          ref_system_id INT         UNSIGNED NOT NULL,
                          ref_tag       TEXT NOT NULL,
                          PRIMARY KEY (ref_id));";		
		$this->QUERY_SQL($sql,"snort");
		}

if(!$this->TABLE_EXISTS('reference_system','snort')){	
			$sql="CREATE TABLE IF NOT EXISTS reference_system ( ref_system_id   INT         UNSIGNED NOT NULL AUTO_INCREMENT,
                                ref_system_name VARCHAR(20),
                                PRIMARY KEY (ref_system_id));";		
		$this->QUERY_SQL($sql,"snort");
		}

if(!$this->TABLE_EXISTS('sig_class','snort')){	
			$sql="CREATE TABLE IF NOT EXISTS sig_class ( sig_class_id        INT    UNSIGNED NOT NULL AUTO_INCREMENT,
                         sig_class_name      VARCHAR(60) NOT NULL,
                         PRIMARY KEY (sig_class_id),
                         INDEX       (sig_class_id),
                         INDEX       (sig_class_name));";		
		$this->QUERY_SQL($sql,"snort");
		}

# store info about the sensor supplying data
if(!$this->TABLE_EXISTS('sensor','snort')){	
			$sql="CREATE TABLE IF NOT EXISTS sensor ( sid	  INT 	   UNSIGNED NOT NULL AUTO_INCREMENT,
                      hostname    TEXT,
                      interface   TEXT,
                      filter	  TEXT,
                      detail	  TINYINT,
                      encoding	  TINYINT,
                      last_cid    INT      UNSIGNED NOT NULL,
                      PRIMARY KEY (sid));";		
		$this->QUERY_SQL($sql,"snort");
		}

# All of the fields of an ip header
if(!$this->TABLE_EXISTS('iphdr','snort')){	
			$sql="CREATE TABLE IF NOT EXISTS iphdr  ( sid 	  INT 	   UNSIGNED NOT NULL,
                      cid 	  INT 	   UNSIGNED NOT NULL,
                      ip_src      INT      UNSIGNED NOT NULL,
                      ip_dst      INT      UNSIGNED NOT NULL,
                      ip_ver      TINYINT  UNSIGNED,
                      ip_hlen     TINYINT  UNSIGNED,
                      ip_tos  	  TINYINT  UNSIGNED,
                      ip_len 	  SMALLINT UNSIGNED,
                      ip_id    	  SMALLINT UNSIGNED,
                      ip_flags    TINYINT  UNSIGNED,
                      ip_off      SMALLINT UNSIGNED,
                      ip_ttl   	  TINYINT  UNSIGNED,
                      ip_proto 	  TINYINT  UNSIGNED NOT NULL,
                      ip_csum 	  SMALLINT UNSIGNED,
                      PRIMARY KEY (sid,cid),
                      INDEX ip_src (ip_src),
                      INDEX ip_dst (ip_dst));";		
		$this->QUERY_SQL($sql,"snort");
		}

# All of the fields of a tcp header
if(!$this->TABLE_EXISTS('tcphdr','snort')){	
			$sql="CREATE TABLE IF NOT EXISTS tcphdr(  sid 	  INT 	   UNSIGNED NOT NULL,
                      cid 	  INT 	   UNSIGNED NOT NULL,
                      tcp_sport   SMALLINT UNSIGNED NOT NULL,
                      tcp_dport   SMALLINT UNSIGNED NOT NULL,
                      tcp_seq     INT      UNSIGNED,
                      tcp_ack     INT      UNSIGNED,
                      tcp_off     TINYINT  UNSIGNED,
                      tcp_res     TINYINT  UNSIGNED,
                      tcp_flags   TINYINT  UNSIGNED NOT NULL,
                      tcp_win     SMALLINT UNSIGNED,
                      tcp_csum    SMALLINT UNSIGNED,
                      tcp_urp     SMALLINT UNSIGNED,
                      PRIMARY KEY (sid,cid),
                      INDEX       tcp_sport (tcp_sport),
                      INDEX       tcp_dport (tcp_dport),
                      INDEX       tcp_flags (tcp_flags));";		
		$this->QUERY_SQL($sql,"snort");
		}

# All of the fields of a udp header
if(!$this->TABLE_EXISTS('udphdr','snort')){	
			$sql="CREATE TABLE IF NOT EXISTS udphdr(  sid 	  INT 	   UNSIGNED NOT NULL,
                      cid 	  INT 	   UNSIGNED NOT NULL,
                      udp_sport   SMALLINT UNSIGNED NOT NULL,
                      udp_dport   SMALLINT UNSIGNED NOT NULL,
                      udp_len     SMALLINT UNSIGNED,
                      udp_csum    SMALLINT UNSIGNED,
                      PRIMARY KEY (sid,cid),
                      INDEX       udp_sport (udp_sport),
                      INDEX       udp_dport (udp_dport));";		
		$this->QUERY_SQL($sql,"snort");
		}

# All of the fields of an icmp header
if(!$this->TABLE_EXISTS('icmphdr','snort')){	
			$sql="CREATE TABLE IF NOT EXISTS icmphdr( sid 	  INT 	   UNSIGNED NOT NULL,
                      cid 	  INT  	   UNSIGNED NOT NULL,
                      icmp_type   TINYINT  UNSIGNED NOT NULL,
                      icmp_code   TINYINT  UNSIGNED NOT NULL,
                      icmp_csum   SMALLINT UNSIGNED,
                      icmp_id     SMALLINT UNSIGNED,
                      icmp_seq    SMALLINT UNSIGNED,
                      PRIMARY KEY (sid,cid),
                      INDEX       icmp_type (icmp_type));";		
		$this->QUERY_SQL($sql,"snort");
		}

# Protocol options
if(!$this->TABLE_EXISTS('opt','snort')){	
			$sql="CREATE TABLE IF NOT EXISTS opt    ( sid         INT      UNSIGNED NOT NULL,
                      cid         INT      UNSIGNED NOT NULL,
                      optid       INT      UNSIGNED NOT NULL,
                      opt_proto   TINYINT  UNSIGNED NOT NULL,
                      opt_code    TINYINT  UNSIGNED NOT NULL,
                      opt_len     SMALLINT,
                      opt_data    TEXT,
                      PRIMARY KEY (sid,cid,optid));";		
		$this->QUERY_SQL($sql,"snort");
		}

# Packet payload
if(!$this->TABLE_EXISTS('data','snort')){	
			$sql="CREATE TABLE IF NOT EXISTS data   ( sid           INT      UNSIGNED NOT NULL,
                      cid           INT      UNSIGNED NOT NULL,
                      data_payload  TEXT,
                      PRIMARY KEY (sid,cid));";		
		$this->QUERY_SQL($sql,"snort");
		}

# encoding is a lookup table for storing encoding types
if(!$this->TABLE_EXISTS('encoding','snort')){	
			$sql="CREATE TABLE encoding(encoding_type TINYINT UNSIGNED NOT NULL,
                      encoding_text TEXT NOT NULL,
                      PRIMARY KEY (encoding_type));";		
		$this->QUERY_SQL($sql,"snort");
		$this->QUERY_SQL("INSERT INTO encoding (encoding_type, encoding_text) VALUES (0, 'hex');","snort");
		$this->QUERY_SQL("INSERT INTO encoding (encoding_type, encoding_text) VALUES (1, 'base64');","snort");
		$this->QUERY_SQL("INSERT INTO encoding (encoding_type, encoding_text) VALUES (2, 'ascii');","snort");
		}


# detail is a lookup table for storing different detail levels
if(!$this->TABLE_EXISTS('detail','snort')){	
			$sql="CREATE TABLE detail  (detail_type TINYINT UNSIGNED NOT NULL,
                      detail_text TEXT NOT NULL,
                      PRIMARY KEY (detail_type));";		
		$this->QUERY_SQL($sql,"snort");
		$this->QUERY_SQL("INSERT INTO detail (detail_type, detail_text) VALUES (0, 'fast');","snort");
		$this->QUERY_SQL("INSERT INTO detail (detail_type, detail_text) VALUES (1, 'full');","snort");
    	
    }
   }
	
}

?>
