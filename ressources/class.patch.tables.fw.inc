<?php


function patch_vpn_tables(){

	$q=new mysql();
	$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`openvpn_clients` (
		`uid` VARCHAR(256) NOT NULL PRIMARY KEY,
		`ComputerOS` VARCHAR(60) NOT NULL,
		`zipcontent` longblob,
		`zipsize` BIGINT UNSIGNED,
		 KEY `ComputerOS` (`ComputerOS`),
		 KEY `uid` (`uid`) ) ENGINE=MYISAM;";
		
	$q->QUERY_SQL($sql,'artica_backup');
	
		
	$sql="CREATE TABLE IF NOT EXISTS `vpnclient` (
					  `ID` int(11) NOT NULL AUTO_INCREMENT,
					  `servername` varchar(180) NOT NULL,
					  `serverport` int(3) NOT NULL,
					  `admin` varchar(50) NOT NULL,
					  `password` varchar(128) NOT NULL,
					  `connexion_name` varchar(128) NOT NULL,
					  `sitename` varchar(128) NOT NULL,
					  `IP_START` varchar(25) NOT NULL,
					  `netmask` varchar(25) NOT NULL,
					  `ethlisten` varchar(10) NOT NULL,
					  `keypassword` varchar(255) NOT NULL,
					  `connexion_type` smallint(1) NOT NULL,
					  `ca_bin` longblob NOT NULL,
					  `key_bin` longblob NOT NULL,
					  `cert_bin` longblob NOT NULL,
					  `ovpn` longblob NOT NULL,
					  `routes` text NOT NULL,
					  `enabled` smallint(1) NOT NULL,
					  `routes_additionnal` TEXT NOT NULL,
					  PRIMARY KEY (`ID`),
					  UNIQUE KEY (`connexion_name`),
					  KEY `sitename` (`sitename`),
					  KEY `ethlisten` (`ethlisten`),
					  KEY `connexion_type` (`connexion_type`),
					  KEY `enabled` (`enabled`)
					) ENGINE=MYISAM;";
		
	
	$q->QUERY_SQL($sql,'artica_backup');
		
	if(!$q->FIELD_EXISTS("vpnclient","enabled","artica_backup")){
		$sql="ALTER TABLE `vpnclient` ADD `enabled` smallint(1) NOT NULL ,ADD INDEX ( `enabled` )";
		$q->QUERY_SQL($sql,"artica_backup");if(!$q->ok){writelogs("Fatal: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
	}
	
	if(!$q->FIELD_EXISTS("vpnclient","ethlisten","artica_backup")){
		$sql="ALTER TABLE `vpnclient` ADD `ethlisten` varchar(10) NOT NULL ,ADD INDEX ( `ethlisten` )";
		$q->QUERY_SQL($sql,"artica_backup");if(!$q->ok){writelogs("Fatal: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
	}
	
	if(!$q->FIELD_EXISTS("vpnclient","keypassword","artica_backup")){
		$sql="ALTER TABLE `vpnclient` ADD `keypassword` varchar(255) NOT NULL";
		$q->QUERY_SQL($sql,"artica_backup");if(!$q->ok){writelogs("Fatal: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
	}
	
	if(!$q->FIELD_EXISTS("vpnclient","routes_additionnal","artica_backup")){
		$sql="ALTER TABLE `vpnclient` ADD `routes_additionnal` TEXT NOT NULL";
		$q->QUERY_SQL($sql,"artica_backup");if(!$q->ok){writelogs("Fatal: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
	}
	
	if(!$q->FIELD_EXISTS("vpnclient","wakeupip","artica_backup")){
		$sql="ALTER TABLE `vpnclient` ADD `wakeupip` VARCHAR( 50 ) NOT NULL ";
		$q->QUERY_SQL($sql,"artica_backup");if(!$q->ok){writelogs("Fatal: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
	}
	
	if(!$q->FIELD_EXISTS("vpnclient","remote_site_routes","artica_backup")){
		$sql="ALTER TABLE `vpnclient` ADD `remote_site_routes` LONGTEXT NOT NULL";
		$q->QUERY_SQL($sql,"artica_backup");if(!$q->ok){writelogs("Fatal: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
	}
	if(!$q->FIELD_EXISTS("vpnclient","wakeup_results","artica_backup")){
		$sql="ALTER TABLE `vpnclient` ADD `wakeup_results` TEXT NOT NULL ,ADD `wakeupok` INT( 1 ) NOT NULL DEFAULT '0'";
		$q->QUERY_SQL($sql,"artica_backup");if(!$q->ok){writelogs("Fatal: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
	}
	
	if(!$q->FIELD_EXISTS("vpnclient","wakeupok","artica_backup")){
		$sql="ALTER TABLE `vpnclient` ADD `wakeupok` INT( 1 ) NOT NULL DEFAULT '0'";
		$q->QUERY_SQL($sql,"artica_backup");if(!$q->ok){writelogs("Fatal: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
	}
	
	if(!$q->FIELD_EXISTS("vpnclient","EnableAuth","artica_backup")){
		$sql="ALTER TABLE `vpnclient` ADD `EnableAuth` INT( 1 ) NOT NULL DEFAULT '0',ADD `AuthUsername` VARCHAR( 128 ) NOT NULL ,ADD `AuthPassword` VARCHAR( 255 ) NOT NULL";
		$q->QUERY_SQL($sql,"artica_backup");if(!$q->ok){writelogs("Fatal: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
	}
	
	if(!$q->FIELD_EXISTS("vpnclient","FixedIPAddr","artica_backup")){
		$sql="ALTER TABLE `vpnclient` ADD `FixedIPAddr` int( 3 ) NOT NULL DEFAULT '0'";
		$q->QUERY_SQL($sql,"artica_backup");if(!$q->ok){writelogs("Fatal: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return false;}
	}

	
}


function patch_firewall_tables(){





	

	

	


}
?>