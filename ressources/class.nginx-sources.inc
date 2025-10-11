<?php


class nginx_sources{
	public $servername;
	public $ipaddr;
	public $port;
	public $ssl;
	public $sslport;
	public $enabled;
	public $ForceRedirect=0;
	public $IsCas=0;
	public $ssl_commname=null;
	public $forceddomain=0;
	public $proxy_read_timeout=300;
	public $proxy_send_timeout=300;
	public $proxy_buffering=1;
	public $database=null;
	public $proxy_cache_min_uses=1;
	public $proxy_buffer_size=8;
	public $proxy_buffers="8 8k";
	public $proxy_cookie_domain=1;
	function __construct($cache_peer_id=0){
		if(!is_numeric($cache_peer_id)){return;}
		if($cache_peer_id>0){$this->load($cache_peer_id);}
		
	}
	

	
	
	private function load($cache_peer_id){
		
		if(!isset($GLOBALS["nginx_sources"][$cache_peer_id])){
			$q=new mysql_squid_builder();
			$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_sources WHERE ID='$cache_peer_id'"));
			$GLOBALS["nginx_sources"][$cache_peer_id]=$ligne;
		}else{
			$ligne=$GLOBALS["nginx_sources"][$cache_peer_id];
		}
			
		$this->servername=$ligne["servername"];
		$this->ipaddr=$ligne["ipaddr"];
		$this->port=$ligne["port"];
		$this->ssl=$ligne["ssl"];
		$this->sslport=$ligne["sslport"];
		$this->enabled=$ligne["enabled"];
		$this->ForceRedirect=$ligne["ForceRedirect"];
		$this->IsCas=$ligne["IsCas"];
		$this->ssl_commname=$ligne["ssl_commname"];
		$this->forceddomain=$ligne["forceddomain"];
		$this->proxy_read_timeout=$ligne["proxy_read_timeout"];
		$this->proxy_send_timeout=intval($ligne["proxy_send_timeout"]);
		$this->proxy_buffering=$ligne["proxy_buffering"];
		$this->proxy_cache_min_uses=$ligne["proxy_cache_min_uses"];
		$this->proxy_buffer_size=$ligne["proxy_buffer_size"];
		$this->proxy_max_temp_file_size=intval($ligne["proxy_max_temp_file_size"]);
		$this->proxy_temp_file_write_size=$ligne["proxy_temp_file_write_size"];
		$this->proxy_cache_min_uses=$ligne["proxy_cache_min_uses"];
		$this->proxy_buffers=$ligne["proxy_buffers"];
		$this->proxy_cookie_domain=$ligne["proxy_cookie_domain"];
		if($this->proxy_send_timeout==0){$this->proxy_send_timeout=300;}
	}
	
	public function PatchTables(){
		if(isset($GLOBALS[__CLASS__.__FUNCTION__])){return;}
		$q=new mysql_squid_builder();
		$sql="CREATE TABLE IF NOT EXISTS `reverse_sources` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `servername` CHAR(255)  NOT NULL,
			  `ipaddr` CHAR(60)  NOT NULL,
			  `port` smallint(3)  NOT NULL,
			  `ssl` smallint(1)  NOT NULL,
			  `sslport` smallint(3)  NOT NULL DEFAULT '443',
			  `enabled` smallint(1)  NOT NULL DEFAULT '1',
			  `ssl_remotecert` smallint(1)  NOT NULL DEFAULT '0',
			  `ForceRedirect` smallint(1)  NOT NULL DEFAULT '0',
			  `IsCas` smallint(1)  NOT NULL DEFAULT '0',
			  `Imported` smallint(1)  NOT NULL DEFAULT '0',
			  `ssl_commname` CHAR(128) NULL,
			  `forceddomain` CHAR(255) NULL,
			  `remote_path` CHAR(255) NULL,
			  `certificate` CHAR(128) NULL,
				
			  `cacheid` INT(10) NOT NULL DEFAULT '0',
			  PRIMARY KEY (`ID`),
			  KEY `ipaddr` (`ipaddr`),
			  KEY `port` (`port`),
			  KEY `ssl_remotecert` (`ssl_remotecert`),
			  KEY `IsCas` (`IsCas`),
			  KEY `sslport` (`sslport`),
			  KEY `enabled` (`enabled`)
			)  ENGINE = MYISAM;";
			$q->QUERY_SQL($sql,$this->database);
			
			$GLOBALS[__CLASS__.__FUNCTION__]=true;
			if(!$q->ok){echo $q->mysql_error_html();return;}
			$this->PatchFields();
	}
	
	private function PatchFields(){
		if(isset($GLOBALS[__CLASS__.__FUNCTION__])){return;}
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("reverse_sources", "sslport")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `sslport` smallint(3) NOT NULL DEFAULT '443', ADD INDEX ( `sslport`)");if(!$q->ok){echo "<p class=text-error>$q->mysql_error\nALTER TABLE `reverse_sources` ADD `sslport` smallint(3) NOT NULL, ADD INDEX ( `sslport` )</p>";}}
		if(!$q->FIELD_EXISTS("reverse_sources", "sslport")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `sslport` smallint(3) NOT NULL DEFAULT '443', ADD INDEX ( `sslport`)");if(!$q->ok){echo "<p class=text-error>$q->mysql_error\nALTER TABLE `reverse_sources` ADD `sslport` smallint(3) NOT NULL, ADD INDEX ( `sslport` )</p>";}}
		if(!$q->FIELD_EXISTS("reverse_sources", "ForceRedirect")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `ForceRedirect` smallint(1) NOT NULL DEFAULT '1' , ADD INDEX ( `ForceRedirect`)");if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";}}
		if(!$q->FIELD_EXISTS("reverse_sources", "ssl_commname")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `ssl_commname` CHAR(128) NULL");}
		if(!$q->FIELD_EXISTS("reverse_sources", "IsCas")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `IsCas` smallint(1) NOT NULL DEFAULT '0',ADD INDEX ( `IsCas`)");}	
		if(!$q->FIELD_EXISTS("reverse_sources", "isSuccess")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `isSuccess` smallint(1) NOT NULL DEFAULT '1', ADD INDEX ( `isSuccess`)");}
		if(!$q->FIELD_EXISTS("reverse_sources", "isSuccesstxt")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `isSuccesstxt` TEXT");}
		if(!$q->FIELD_EXISTS("reverse_sources", "isSuccessTime")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `isSuccessTime` datetime");}
		if(!$q->FIELD_EXISTS("reverse_sources", "proxy_read_timeout")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `proxy_read_timeout` SMALLINT(5) NOT NULL DEFAULT '300'");}
		if(!$q->FIELD_EXISTS("reverse_sources", "OnlyTCP")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `OnlyTCP` smallint(1) NOT NULL DEFAULT '0'");}
		if(!$q->FIELD_EXISTS("reverse_sources", "Imported")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `Imported` smallint(1) NOT NULL DEFAULT '0',ADD INDEX ( `Imported`)");}
		if(!$q->FIELD_EXISTS("reverse_sources", "proxy_cookie_domain")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `proxy_cookie_domain` smallint(1) NOT NULL DEFAULT '1',ADD INDEX ( `proxy_cookie_domain`)");}
		if(!$q->FIELD_EXISTS("reverse_sources", "remote_path")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `remote_path` CHAR(255) NULL");}
		if(!$q->FIELD_EXISTS("reverse_sources", "cacheid")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `cacheid` INT(10) NOT NULL DEFAULT '0'");}
		if(!$q->FIELD_EXISTS("reverse_sources", "ssl_remotecert")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `ssl_remotecert` smallint(1) NOT NULL DEFAULT '0'");}
		if(!$q->FIELD_EXISTS("reverse_sources", "certificate")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `certificate` CHAR(128) NULL");}
		
		
		
		
		$GLOBALS[__CLASS__.__FUNCTION__]=true;
	}
	
	private function IsSubstitutions(){
		if(isset($GLOBALS["IsSubstitutions"])){return $GLOBALS["IsSubstitutions"];}
		$GLOBALS["IsSubstitutions"]=false;
		$ARRAY=$this->LoadParams();
		while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
			if(preg_match("#http_substitutions_filter#", $a)){
				$GLOBALS["IsSubstitutions"]=true;
				break;
			}
	
		}
		return $GLOBALS["IsSubstitutions"];
	}

	private function LoadParams(){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.":Loading\n";}
		$unix=new unix();
		return $unix->NGINX_COMPILE_PARAMS();
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.":Done...\n";}
	}
	
	public function DeleteSource($sourceid){
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("UPDATE `reverse_www` SET cache_peer_id=0 WHERE cache_peer_id='$sourceid'");
		$q->QUERY_SQL("DELETE FROM reverse_sources WHERE ID='$sourceid'");
		if(!$q->ok){echo $q->mysql_error;return;}
		$q->QUERY_SQL("DELETE FROM reverse_privs WHERE sourceid='$sourceid'");
	
	
	}
	
	
	
	
}