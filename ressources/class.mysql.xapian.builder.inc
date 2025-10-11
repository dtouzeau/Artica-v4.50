<?php


class mysql_xapian_builder{
	
	
	
	function build(){
		$q=new mysql();
		
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`xapian_folders` (
			`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`directory` varchar( 255 ) NOT NULL ,
			`ressourcename` varchar( 255 ) NOT NULL ,
			`ressourceexplain` varchar( 255 ) NOT NULL ,
			`depth` INT( 3 ) NOT NULL DEFAULT '0',
			`maxsize` INT( 5 ) NOT NULL DEFAULT '60',
			`sample-size` BIGINT UNSIGNED DEFAULT '512',
			`lang` VARCHAR( 40 ) NOT NULL DEFAULT 'english',
			`autmountdn` VARCHAR( 255 ) NOT NULL,
			`DatabasePath` VARCHAR( 255 ) NOT NULL,
			`DatabaseSize` BIGINT UNSIGNED,
			`indexed` BIGINT UNSIGNED DEFAULT '0',
			`SkippedFiles`  BIGINT UNSIGNED DEFAULT '0',
			`WebCopyID` BIGINT UNSIGNED DEFAULT '0',
			`DiplayFullPath` smallint(1) DEFAULT 0,
			`AllowDownload` smallint(1) DEFAULT 0,
			`ScannedTime` datetime	DEFAULT '1970-01-01 08:00:00',
			KEY `ScannedTime` (`ScannedTime`),
			KEY `autmountdn` (`autmountdn`),
			KEY `DatabasePath` (`DatabasePath`),
			INDEX ( `depth` , `maxsize`,`indexed`,`DatabaseSize`)
			)  ENGINE=MyISAM";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error."\n";return;}
			
			
		if($q->TABLE_EXISTS('xapian_folders','artica_backup')){
			$this->FIELD_CHECKS("xapian_folders", "indexed", "artica_backup", "BIGINT",100,true);
			$this->FIELD_CHECKS("xapian_folders", "DiplayFullPath", "artica_backup", "smallint",1,true);
			$this->FIELD_CHECKS("xapian_folders", "AllowDownload", "artica_backup", "smallint",1,true);
			$this->FIELD_CHECKS("xapian_folders", "WebCopyID", "artica_backup", "BIGINT",100,true);
			$this->FIELD_CHECKS("xapian_folders", "autmountdn", "artica_backup", "VARCHAR",255,true);
			$this->FIELD_CHECKS("xapian_folders", "DatabasePath", "artica_backup", "VARCHAR",255,true);
			$this->FIELD_CHECKS("xapian_folders", "DatabaseSize", "artica_backup", "BIGINT",100,false);
			if(!$q->FIELD_EXISTS("xapian_folders", "hostname", "artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `hostname` VARCHAR( 128 ) NULL","artica_backup");
			}
			if(!$q->FIELD_EXISTS("xapian_folders", "sfolder", "artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `sfolder` VARCHAR( 128 ) NULL","artica_backup");
			}
			if(!$q->FIELD_EXISTS("xapian_folders", "tfolder", "artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `tfolder` VARCHAR( 128 ) NULL","artica_backup");
			}
			if(!$q->FIELD_EXISTS("xapian_folders", "stemming", "artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `stemming` VARCHAR(40) NOT NULL DEFAULT 'none'","artica_backup");
			}
			if(!$q->FIELD_EXISTS("xapian_folders", "zorder", "artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `zorder` INT( 10 ) NOT NULL DEFAULT 1","artica_backup");
			}
			
			if(!$q->FIELD_EXISTS("xapian_folders", "enabled", "artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `enabled` smallint( 1 ) NOT NULL DEFAULT 1","artica_backup");
			}			
			
			if(!$q->FIELD_EXISTS("xapian_folders", "reindex", "artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `reindex` smallint( 1 ) NOT NULL DEFAULT 0","artica_backup");
			}
			if(!$q->FIELD_EXISTS("xapian_folders", "GetError", "artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `GetError` smallint( 1 ) NOT NULL DEFAULT 0","artica_backup");
			}
			if(!$q->FIELD_EXISTS("xapian_folders", "GetErrorText", "artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `GetErrorText` TEXT NULL","artica_backup");
			}
			if(!$q->FIELD_EXISTS("xapian_folders", "password", "artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `password` VARCHAR( 128 ) NULL","artica_backup");
			}
			if(!$q->FIELD_EXISTS("xapian_folders", "workgroup", "artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `workgroup` VARCHAR( 128 ) NULL","artica_backup");
			}
			if(!$q->FIELD_EXISTS("xapian_folders", "username", "artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `username` VARCHAR( 128 ) NULL","artica_backup");
			}
			if(!$q->FIELD_EXISTS("xapian_folders", "DistanceTime", "artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `DistanceTime` VARCHAR( 128 ) NULL","artica_backup");
			}
		
			if(!$q->FIELD_EXISTS("xapian_folders", "ztype", "artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `ztype` VARCHAR( 40 ) NOT NULL DEFAULT 'smb'","artica_backup");
			}
			if(!$q->FIELD_EXISTS("xapian_folders", "filesnumber", "artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `filesnumber` INT UNSIGNED NOT NULL DEFAULT 0","artica_backup");
			}
			if(!$q->FIELD_EXISTS("xapian_folders", "SkippedFiles", "artica_backup")){
				$q->QUERY_SQL("ALTER TABLE `xapian_folders` ADD `SkippedFiles` INT UNSIGNED NOT NULL DEFAULT 0","artica_backup");
			}			
			
			
		}
	return true;}
	
	private function FIELD_CHECKS($table,$field,$database,$type,$size,$key=false,$unique=false){
		$size_text=null;
		$q=new mysql();
		if(!$q->FIELD_EXISTS($table,$field,$database)){
			if($key){$key_text=",ADD INDEX ( `$field` )";}
			if($unique){$key_text=",ADD UNIQUE KEY `$field` (`$field`)";}
			if($size>0){$size_text="( $size )";}
			$sql="ALTER TABLE `$table` ADD `$field` $type$size_text NOT NULL$key_text";
			$q->QUERY_SQL($sql,$database);
			if(!$this->ok){
				writelogs("Fatal: $q->mysql_error Adding field $field in $table/$database $type($size) $sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
				
		}else{
			$Filedtype=$q->FIELD_TYPE($table,$field,$database);
			if($size>0){$size_text="($size)";}
			$MyType=strtolower("$type$size_text");
			if($MyType<>$Filedtype){
				$sql="ALTER TABLE `$table` CHANGE `$field` `$field` $type$size_text";
				$q->QUERY_SQL($sql,$database);
			}
				
		}
	}
	
}