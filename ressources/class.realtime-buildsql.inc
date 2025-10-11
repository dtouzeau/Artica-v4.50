<?php



function REALTIME_searchwords($TimeCache){

	REALTIME_SEND_MYSQL("CREATE TABLE IF NOT EXISTS `squidlogs`.`searchwords_$TimeCache` (
	`zmd5` VARCHAR(128) PRIMARY KEY,
	`sitename` varchar(90) NOT NULL,
	`zDate` datetime NOT NULL,
	`ipaddr` VARCHAR(40),
	`hostname` VARCHAR(128),
	`uid` VARCHAR(40) NOT NULL,
	`MAC` VARCHAR(20) NOT NULL,
	`account` INT UNSIGNED NOT NULL,
	`familysite` varchar(128) NOT NULL,
	`words` VARCHAR(255) NOT NULL,
	KEY `ipaddr`(`ipaddr`),
	KEY `sitename`(`sitename`),
	KEY `familysite`(`familysite`),
	KEY `zDate`(`zDate`),
	KEY `hostname`(`hostname`),
	KEY `uid`(`uid`),
	KEY `MAC`(`MAC`),
	KEY `words`(`words`),
	KEY `account`(`account`)
	) ENGINE=MYISAM;");
}

function REALTIME_RTTH_TABLE($tablename){
	REALTIME_SEND_MYSQL("CREATE TABLE IF NOT EXISTS `squidlogs`.`$tablename` (
	`xtime` INT UNSIGNED NOT NULL,
	`sitename` VARCHAR(255) NOT NULL,
	`ipaddr` VARCHAR(60) NOT NULL,
	`uid` VARCHAR(60) NOT NULL,
	`MAC` VARCHAR(90) NOT NULL,
	`size` INT UNSIGNED NOT NULL,
	KEY `sitename` (`sitename`),
	KEY `ipaddr` (`ipaddr`),
	KEY `xtime` (`xtime`),
	KEY `uid` (`uid`),
	KEY `MAC` (`MAC`),
	KEY `size` (`size`)
	) ENGINE=MYISAM");


}

function HYPERCACHE_APACHE_TABLE($tablename){
	REALTIME_SEND_MYSQL("CREATE TABLE IF NOT EXISTS `squidlogs`.`$tablename` (
	`zmd5` VARCHAR(128) PRIMARY KEY,
	`zDate` DATETIME NOT NULL,
	`size` INT UNSIGNED NOT NULL,
	KEY `zDate` (`zDate`),
	KEY `size` (`size`)
	) ENGINE=MYISAM"
			);


}

function REALTIME_RTTH($TimeCache){
	REALTIME_SEND_MYSQL("CREATE TABLE IF NOT EXISTS `squidlogs`.`RTTH_$TimeCache` (
	`xtime` INT UNSIGNED NOT NULL,
	`sitename` VARCHAR(255) NOT NULL,
	`ipaddr` VARCHAR(60) NOT NULL,
	`uid` VARCHAR(60) NOT NULL,
	`MAC` VARCHAR(90) NOT NULL,
	`size` INT UNSIGNED NOT NULL,
	KEY `sitename` (`sitename`),
	KEY `ipaddr` (`ipaddr`),
	KEY `xtime` (`xtime`),
	KEY `uid` (`uid`),
	KEY `MAC` (`MAC`),
	KEY `size` (`size`)
	) ENGINE=MYISAM");


}
function REALTIME_sizehour($TimeCache){
	REALTIME_SEND_MYSQL("CREATE TABLE IF NOT EXISTS `sizehour_$TimeCache` (
	`zDate` datetime NOT NULL,
	`size` BIGINT UNSIGNED,
	cached smallint(1),
	KEY `zDate` (`zDate`),
	KEY `cached` (`cached`),
	KEY `size` (`size`)
	) ENGINE=MYISAM;");
}
function REALTIME_youtubehours($TimeCache){
	REALTIME_SEND_MYSQL("CREATE TABLE IF NOT EXISTS `youtubehours_$TimeCache` (
	`zDate` datetime NOT NULL,
	`ipaddr` VARCHAR(40),
	`hostname` VARCHAR(128),
	`uid` VARCHAR(40) NOT NULL,
	`MAC` VARCHAR(20) NOT NULL,
	`account` INT UNSIGNED NOT NULL,
	`youtubeid` VARCHAR(60) NOT NULL,
	KEY `ipaddr`(`ipaddr`),
	KEY `zDate`(`zDate`),
	KEY `hostname`(`hostname`),
	KEY `uid`(`uid`),
	KEY `MAC`(`MAC`),
	KEY `account`(`account`)
	) ENGINE=MYISAM;");
}

function REALTIME_quotatemp($TimeCache){
	REALTIME_SEND_MYSQL("CREATE TABLE IF NOT EXISTS `quotatemp_$TimeCache` (
	`xtime` datetime NOT NULL,
	`keyr` VARCHAR(90) NOT NULL,
	`ipaddr` VARCHAR(40),
	`familysite` VARCHAR(128),
	`servername` VARCHAR(255),
	`uid` VARCHAR(40) NOT NULL,
	`ou` VARCHAR(128) NOT NULL,
	`MAC` VARCHAR(20) NOT NULL,
	`size` BIGINT UNSIGNED NOT NULL,
	KEY `ipaddr`(`ipaddr`),
	KEY `familysite`(`familysite`),
	KEY `keyr`(`keyr`),
	KEY `xtime`(`xtime`),
	KEY `ou`(`ou`),
	KEY `servername`(`servername`),
	KEY `uid`(`uid`),
	KEY `MAC`(`MAC`)
	) ENGINE=MYISAM;");
	

}


function REALTIME_cachehour($TimeCache){
	REALTIME_cachehour_FULL("cachehour_$TimeCache");
	
}

function cache_rate_day($TimeCache){
	cache_rate_day_FULL("{$TimeCache}_cacherated");

}
function cache_rate_year($TimeCache){cache_rate_year_FULL("{$TimeCache}_cacherated");}

function cache_rate_day_FULL($tablename){
	REALTIME_SEND_MYSQL("CREATE TABLE IF NOT EXISTS `$tablename` (
	`zDate` datetime PRIMARY KEY,
	`zrate` FLOAT,
	KEY `zrate` (`zrate`),
	KEY `zDate` (`zDate`)) ENGINE=MYISAM;");	
	
	
}

function cache_rate_total_FULL(){
	REALTIME_SEND_MYSQL("CREATE TABLE IF NOT EXISTS `CACHE_RATES` (
	`zDate` date PRIMARY KEY,
	`zrate` FLOAT,
	KEY `zrate` (`zrate`),
	KEY `zDate` (`zDate`)) ENGINE=MYISAM;");


}

function cache_perf_day_FULL($tablename){
	REALTIME_SEND_MYSQL("CREATE TABLE IF NOT EXISTS `$tablename` (
	`zDate` datetime PRIMARY KEY,
	`client_http_hits` FLOAT,
	`client_http_requests` FLOAT,
	`client_http_kbytes_out` FLOAT,
	KEY `client_http_hits` (`client_http_hits`),
	KEY `client_http_requests` (`client_http_requests`),
	KEY `client_http_kbytes_out` (`client_http_kbytes_out`),
	KEY `zDate` (`zDate`)) ENGINE=MYISAM;");


}

function cache_rate_year_FULL($tablename){cache_rate_day_FULL($tablename);}


function REALTIME_cachehour_FULL($tablename){
	REALTIME_SEND_MYSQL("CREATE TABLE IF NOT EXISTS `$tablename` (
	`zDate` datetime NOT NULL,
	`size` BIGINT UNSIGNED,
	cached smallint(1),
	`familysite` VARCHAR(128) NOT NULL,
	KEY `zDate` (`zDate`),
	KEY `cached` (`cached`),
	KEY `size` (`size`),
	KEY `familysite` (`familysite`)
	) ENGINE=MYISAM;");
}
function REALTIME_squidhour($TimeCache){
	REALTIME_SEND_MYSQL("CREATE TABLE IF NOT EXISTS `squidhour_$TimeCache` (
	`sitename` varchar(90) NOT NULL,
	`ID` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`uri` varchar(255) NOT NULL,
	`TYPE` varchar(50) NOT NULL,
	`REASON` varchar(255) NOT NULL,
	`CLIENT` varchar(50) NOT NULL DEFAULT '',
	`hostname` varchar(120) NOT NULL DEFAULT '',
	`zDate` datetime NOT NULL,
	`zMD5` CHAR(32) NOT NULL,
	`uid` varchar(128) NOT NULL,
	`remote_ip` varchar(20) NOT NULL,
	`country` varchar(20) NOT NULL,
	`category` varchar(60) NOT NULL,
	`QuerySize` BIGINT UNSIGNED NOT NULL,
	`cached` smallint(1) NOT NULL DEFAULT '0',
	`MAC` varchar(20) NOT NULL,
	PRIMARY KEY (`ID`),
	UNIQUE KEY `zMD5` (`zMD5`),
	KEY `sitename` (`sitename`),
	KEY `TYPE`(`TYPE`),
	KEY `CLIENT` (`CLIENT`),
	KEY `uri` (`uri`),
	KEY `hostname` (`hostname`),
	KEY `zDate` (`zDate`),
	KEY `cached` (`cached`),
	KEY `remote_ip` (`remote_ip`),
	KEY `uid` (`uid`),
	KEY `country` (`country`),
	KEY `MAC` (`MAC`)
	) ENGINE=MYISAM;");
	
}

function REALTIME_SEND_MYSQL($sql){
	
	if(class_exists("mysql_squid_builder")){
		$q=new mysql_squid_builder();
		$q->QUERY_SQL($sql);
		return $q->ok;
	}
	
	if(function_exists("SEND_MYSQL")){
		SEND_MYSQL($sql);
		return;
		
	}
	
}