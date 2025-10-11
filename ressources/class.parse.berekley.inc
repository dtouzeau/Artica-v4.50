<?php

class parse_berekley_dbs{
	public $ok=false;
	private $asmeta=false;
	
	
	function __construct($asmeta=false){
		$this->asmeta=$asmeta;
		
	}
	
	public function SQUID_QUOTASIZE_CREATE_TABLE_PREFIX($tablename){
		return "INSERT IGNORE INTO `$tablename` ( `zmd5`,`ipaddr`,`MAC`,`uid`,familysite,`day`,`hour`,`size`,`category`) VALUES ";
	}
	
	public function SQUID_QUOTASIZE_CREATE_TABLE_WEEK_STRING($tablename,$ENGINE){
		
		return "CREATE TABLE IF NOT EXISTS `$tablename` (
		`zmd5` varchar(90) NOT NULL,
		`familysite` varchar(128) NOT NULL,
		`ipaddr` varchar(50) NOT NULL DEFAULT '',
		`day` smallint(2) NOT NULL,
		`hour` smallint(2) NOT NULL,
		`uid` varchar(128) NOT NULL,
		`MAC` varchar(20) NOT NULL,
		`size` BIGINT UNSIGNED NOT NULL,
		`category` varchar(90) NOT NULL,
		PRIMARY KEY `zmd5` (`zmd5`),
		KEY `familysite` (`familysite`),
		KEY `ipaddr` (`ipaddr`),
		KEY `uid` (`uid`),
		KEY `category` (`category`),
		KEY `hour` (`hour`),
		KEY `day` (`day`),
		KEY `MAC` (`MAC`)
		) ENGINE=$ENGINE;";
		
	}
	
	public function SQUID_QUOTASIZE_CREATE_TABLE_MONTH_STRING($tablename){
	
		return "CREATE TABLE IF NOT EXISTS `$tablename` (
		`zmd5` varchar(90) NOT NULL,
		`familysite` varchar(128) NOT NULL,
		`ipaddr` varchar(50) NOT NULL DEFAULT '',
		`day` smallint(2) NOT NULL,
		`uid` varchar(128) NOT NULL,
		`MAC` varchar(20) NOT NULL,
		`size` BIGINT UNSIGNED NOT NULL,
		`category` varchar(90) NOT NULL,
		PRIMARY KEY `zmd5` (`zmd5`),
		KEY `familysite` (`familysite`),
		KEY `ipaddr` (`ipaddr`),
		KEY `uid` (`uid`),
		KEY `category` (`category`),
		KEY `day` (`day`),
		KEY `MAC` (`MAC`)
		) ENGINE=MYISAM;";
	
	}	
	
	public function SQUID_CACHED_SITES($path){
		$this->ok=false;
		if(!is_file($path)){return;}
		$db_con = dba_open($path, "r","db4");
		if(!$db_con){return false;}
		$mainkey=dba_firstkey($db_con);
		$f=array();
		while($mainkey !=false){
			$val=0;
			$mac=null;
			$mainkey=mysql_escape_string2($mainkey);
			$size=dba_fetch($mainkey,$db_con);
			
			$f[]="('$mainkey','$size')";
			$mainkey=dba_nextkey($db_con);
		}
		dba_close($db_con);
		return $f;
		
	}
	

	public function SQUID_CACHED_SITES_TABLE_PREFIX($tablename){
		return "INSERT IGNORE INTO `$tablename` (`familysite`,`size`) VALUES ";
	}
	
	
	public function SQUID_CACHED_SITES_TABLE_STRING($tablename,$ENGINE){
	
		return "CREATE TABLE IF NOT EXISTS `$tablename` (
		`familysite` varchar(128) NOT NULL,
		`size` BIGINT UNSIGNED NOT NULL,
		PRIMARY KEY `familysite` (`familysite`),
		KEY `familysite` (`familysite`),
		KEY `size` (`size`)
		) ENGINE=$ENGINE;";
	
	}	
	
	
	public function SQUID_QUOTASIZES($path){
		
		$this->ok=false;
		if(!is_file($path)){return;}
		$db_con = dba_open($path, "r","db4");
		if(!$db_con){return false;}
		
		$qCommon=new mysql_squid_builder();
		$mainkey=dba_firstkey($db_con);
		
		while($mainkey !=false){
				$val=0;
				$mac=null;
				$data=unserialize(dba_fetch($mainkey,$db_con));
				$mainkey=dba_nextkey($db_con);
				if(!is_array($data)){continue;}
		
				
				if(!isset($data["HOURLY"])){continue;}
				if(!isset($data["WWW"])){continue;}
				$category=null;
				$ipaddr=mysql_escape_string2($data["IPADDR"]);
				if(isset($data["MAC"])){
					$mac=trim(strtolower($data["MAC"]));
					if(strlen($mac)>17){$mac=clean_mac($mac);}
					$mac=mysql_escape_string2($mac);
				}
				
				$uid=mysql_escape_string2($data["UID"]);
				$familysite=mysql_escape_string2($data["WWW"]);
				if(isset($data["category"])){$category=mysql_escape_string2($data["category"]);}
		
				if($uid==null){$uid=$qCommon->UID_FROM_MAC($data["MAC"]);}
				if($uid==null){$uid=$qCommon->UID_FROM_IP($data["IPADDR"]);}
				$uid=mysql_escape_string2($uid);
		
				$length=strlen($ipaddr)+strlen($mac)+strlen($uid)+strlen($familysite);
				if($length==0){continue;}
		
				while (list ($day, $array) = each ($data["HOURLY"]) ){
					if($GLOBALS["VERBOSE"]){echo "**********************************\n DAY $day \n**********************************\n\n";}
					while (list ($hour, $size) = each ($array) ){

					$md5=md5("'$ipaddr','$mac','$uid','$familysite','$day','$hour','$size','$category'");
					//INSERT IGNORE INTO `$tablename` ( `zmd5`,`ipaddr`,`MAC`,`uid`,familysite,`day`,`hour`,`size`,`category`) VALUES
					//('5a13ec042f76dfdfd395f1d1e77b6979','10.33.18.148','58:94:6b:5f:68:30','cvejux','estat.com','26','17','2630','')
					$wwwUH[]="('$md5','$ipaddr','$mac','$uid','$familysite','$day','$hour','$size','$category')";
					if($GLOBALS["VERBOSE"]){echo "('$md5','$ipaddr','$mac','$uid','$familysite','$day','$hour','$size','$category')\n";}
					}
		
				}
		
		}
		
		dba_close($db_con);
		$this->ok=true;
		return $wwwUH;
		
	}
	
	
	public function TIME_FROM_DAY_INT($intval){
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CDay=str_replace("_", "", $CDay);
		return strtotime("$Cyear-$CMonth-$CDay 00:00:00");
	}	
	
	public function TIME_FROM_HOUR_INT($intval){
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CHour=substr($intval,8,2);
		return strtotime("$Cyear-$CMonth-$CDay $CHour:00:00");
	}	
	
	public function MIME_PARSE_TABLE_PREFIX($tablename){
		return "INSERT IGNORE INTO `$tablename` (`zmd5`,`zDate`,`mime_type`,`uid`,`ipaddr`,`MAC`,`size`) VALUES ";
	}
	
	public function MIME_PARSE_TABLE_STRING($tablename){
		
		return "CREATE TABLE IF NOT EXISTS `$tablename` (
		`zmd5` varchar(90) NOT NULL,
		`zDate` DATE NOT NULL,
		`mime_type` varchar(128) NOT NULL,
		`uid` varchar(128) NOT NULL,
		`ipaddr` varchar(90) NOT NULL,
		`MAC` varchar(20) NOT NULL,
		`size` BIGINT UNSIGNED NOT NULL,
		PRIMARY KEY `zmd5` (`zmd5`),
		KEY `mime_type` (`mime_type`),
		KEY `zDate` (`zDate`),
		KEY `uid` (`uid`),
		KEY `MAC` (`MAC`),
		KEY `size` (`size`),
		KEY `ipaddr` (`ipaddr`)
		) ENGINE=MYISAM;";
	}
	
	public function PROTO_PARSE_TABLE_STRING($tablename){
	
		return "CREATE TABLE IF NOT EXISTS `$tablename` (
		`zmd5` varchar(90) NOT NULL,
		`zDate` DATE NOT NULL,
		`proto` varchar(40) NOT NULL,
		`hits` BIGINT UNSIGNED NOT NULL,
		`size` BIGINT UNSIGNED NOT NULL,
		PRIMARY KEY `zmd5` (`zmd5`),
		KEY `proto` (`proto`),
		KEY `zDate` (`zDate`),
		KEY `hits` (`hits`),
		KEY `size` (`size`)
		) ENGINE=MYISAM;";
	}	
	
	public function PROTO_PARSE_TABLE_PREFIX($tablename){
		return "INSERT IGNORE INTO `$tablename` (`zmd5`,`zDate`,`proto`,`hits`,`size`) VALUES ";
	}
	
	public function PROTO_PARSE_DB($filename,$xdate){
		$time=$this->TIME_FROM_DAY_INT($xdate);
		$date=date("Y-m-d",$time);
	
		$db_con = dba_open($filename, "r","db4");
		if(!$db_con){echo "DB open $filename failed\n";return false;}
		$mainkey=dba_firstkey($db_con);
		$MAIN=array();
	
		while($mainkey !=false){
			$data=unserialize(dba_fetch($mainkey,$db_con));
			$mainkey=dba_nextkey($db_con);
	
			while (list ($proto, $array) = each ($data) ){
				if(trim($proto)==null){continue;}
				if(!preg_match("#^([A-Z]+)$#", $proto)){continue;}
				$HIT=$array["HIT"];
				$SIZE=$array["SIZE"];
				if(!isset($MAIN[$proto])){
					$MAIN[$proto]["HIT"]=$HIT;
					$MAIN[$proto]["SIZE"]=$SIZE;
					continue;
				}
				$MAIN[$proto]["HIT"]=$MAIN[$proto]["HIT"]+$HIT;
				$MAIN[$proto]["SIZE"]=$MAIN[$proto]["SIZE"]+$SIZE;

				}
		}
	
		@dba_close($db_con);
		
		
		while (list ($proto, $array) = each ($MAIN) ){
			$HIT=$array["HIT"];
			$SIZE=$array["SIZE"];
			$md5=md5($xdate.$proto.serialize($array));
			$f[]="('$md5','$date','$proto','$HIT','$SIZE')";
			if($GLOBALS["VERBOSE"]){echo "('$md5','$date','$proto','$HIT','$SIZE')\n";}
		}
		
		if(count($f)>0){return $f; }
		return false;
	}	
	
	public function MIME_PARSE_DB($filename,$xdate){
	
	
		$time=$this->TIME_FROM_DAY_INT($xdate);
		$tablename=date("Ym",$time)."_mime";


		$date=date("Y-m-d",$time);
	
		$db_con = dba_open($filename, "r","db4");
		if(!$db_con){
			echo "DB open $filename failed\n";
			return false;
		}
	
		$mainkey=dba_firstkey($db_con);
	
		while($mainkey !=false){
			$val=0;
	
			$data=unserialize(dba_fetch($mainkey,$db_con));
			$mainkey=dba_nextkey($db_con);
	
			while (list ($filetype, $array) = each ($data) ){
				if($filetype=="-"){continue;}
				if(trim($filetype)==null){continue;}
				if(strpos($filetype, "%20")>0){continue;}
					
				$SIZE=$array["SIZE"];
				$MAC=$array["MAC"];
					
				if(strlen($MAC)>17){$MAC=$this->clean_mac($MAC);}
				$UID=$array["UID"];
				$IPADDR=$array["IPADDR"];
				$md5=md5($date.$filetype.serialize($array));
				$f[]="('$md5','$date','$filetype','$UID','$IPADDR','$MAC','$SIZE')";
				if($GLOBALS["VERBOSE"]){echo "('$md5','$date','$filetype','$UID','$IPADDR','$MAC','$SIZE')\n";}
			}
		}
	
		@dba_close($db_con);
		if(count($f)>0){return $f; }
		return false;
	}
	
	public function NOT_CACHED_WEEK_PARSE_TABLE_STRING($tablename){
	
		return "CREATE TABLE IF NOT EXISTS `$tablename` (
		`zmd5` varchar(90) NOT NULL,
		`familysite` varchar(128) NOT NULL,
		`hits` BIGINT UNSIGNED NOT NULL,
		`size` BIGINT UNSIGNED NOT NULL,
		PRIMARY KEY `zmd5` (`zmd5`),
		KEY `familysite` (`familysite`),
		KEY `hits` (`hits`),
		KEY `size` (`size`)
		) ENGINE=MYISAM;";
	}
	
	public function PROTO_DAY_PARSE_TABLE_STRING($tablename){
	
		return "CREATE TABLE IF NOT EXISTS `$tablename` (
		`zmd5` varchar(90) NOT NULL,
		`familysite` varchar(128) NOT NULL,
		`proto` varchar(60) NOT NULL,
		`hits` BIGINT UNSIGNED NOT NULL,
		`size` BIGINT UNSIGNED NOT NULL,
		PRIMARY KEY `zmd5` (`zmd5`),
		KEY `familysite` (`familysite`),
		KEY `proto` (`proto`),
		KEY `hits` (`hits`),
		KEY `size` (`size`)
		) ENGINE=MYISAM;";
	}	
	
	public function NOT_CACHED_WEEK_TABLE_PREFIX($tablename){
		return "INSERT IGNORE INTO `$tablename` (`zmd5`,`familysite`,`hits`,`size`) VALUES ";
	}

	public function NOT_CACHED_WEEK_PARSE_DB($filename){
	
			$db_con = dba_open($filename, "r","db4");
			if(!$db_con){echo "DB open $filename failed\n"; return false; }
			$mainkey=dba_firstkey($db_con);
		
		while($mainkey !=false){
			$val=0;
			$data=dba_fetch($mainkey,$db_con);
			$array=unserialize($data);
			$mainkey=dba_nextkey($db_con);
			$familysite=trim($mainkey);
			if($familysite==null){continue;}
			$SIZE=$array["SIZE"];
			$HIT=$array["HIT"];
			$md5=md5($familysite.$SIZE.$HIT);
			$familysite=mysql_escape_string2($familysite);
			$f[]="('$md5','$familysite','$HIT','$SIZE')";
			if($GLOBALS["VERBOSE"]){echo "('$md5','$familysite','$HIT','$SIZE')\n";}
			
		}
	
		@dba_close($db_con);
		if(count($f)>0){return $f; }
		return false;
	}	
	
	public function PROTO_DAY_TABLE_PREFIX($tablename){
		return "INSERT IGNORE INTO `$tablename` (`zmd5`,`proto`,`familysite`,`hits`,`size`) VALUES ";
	}
	
	
	public function PROTO_DAY_PARSE_DB($filename){
		$f=array();
		$db_con = dba_open($filename, "r","db4");
		if(!$db_con){echo "DB open $filename failed\n"; return false; }
		$familysite=dba_firstkey($db_con);
	
		while($familysite !=false){
			$familysite=trim($familysite);
			if($familysite==null){continue;}
			$data=dba_fetch($familysite,$db_con);
			$MAIN=unserialize($data);
			while (list ($PROTO, $array) = each ($MAIN) ){
				$SIZE=$array["SIZE"];
				$HIT=$array["HIT"];
				$md5=md5($familysite.$PROTO.$SIZE.$HIT);
				$familysite=mysql_escape_string2($familysite);
				$f[]="('$md5','$PROTO','$familysite','$HIT','$SIZE')";
				if($GLOBALS["VERBOSE"]){echo "('$md5','$PROTO','$familysite','$HIT','$SIZE')\n";}
			}
			$familysite=dba_nextkey($db_con);
		}
	
		@dba_close($db_con);
		if(count($f)>0){return $f; }
		return false;
	}	
	
	
	
	public function ACCESS_PARSE_TABLE_STRING($tablename){
	
		return "CREATE TABLE IF NOT EXISTS `$tablename` (
		`zmd5` varchar(90) NOT NULL,
		`zDate` DATE NOT NULL,
		`familysite` varchar(128) NOT NULL,
		`category` varchar(60) NOT NULL,
		`hits` BIGINT UNSIGNED NOT NULL,
		`size` BIGINT UNSIGNED NOT NULL,
		`hour` smallint(2) NOT NULL,
		`uid` varchar(128) NOT NULL,
		`ipaddr` varchar(90) NOT NULL,
		`MAC` varchar(20) NOT NULL,
		PRIMARY KEY `zmd5` (`zmd5`),
		KEY `familysite` (`familysite`),
		KEY `category` (`category`),
		KEY `zDate` (`zDate`),
		KEY `hits` (`hits`),
		KEY `hour` (`hour`),
		KEY `MAC` (`MAC`),
		KEY `uid` (`uid`),
		KEY `ipaddr` (`ipaddr`),
		KEY `size` (`size`)
		) ENGINE=MYISAM;";
	}	
	
	
	public function ACCESS_PARSE_TABLE_PREFIX($tablename){
		return "INSERT IGNORE INTO `$tablename` (`zmd5`,`zDate`,`hour`,`familysite`,`category`,`hits`,`size`,`MAC`,`uid`,`ipaddr`) VALUES ";
	}
	
	
	public function MYSQL_ERROR_PARSE_DB($filename){
		if(!is_file($filename)){return;}
		include_once(dirname(__FILE__)."/class.realtime-buildsql.inc");
		$db_con = dba_open($filename, "w","db4");
		if(!$db_con){echo "DB open $filename failed\n";return false;}
		$mainkey=dba_firstkey($db_con);
		$q=new mysql_squid_builder();
		
		$c=0;
		while($mainkey !=false){
			$tablename=$mainkey;
			$qeries=unserialize(dba_fetch($mainkey,$db_con));
			$LOOP=false;
			$c++;
			if(count($qeries)==0){
				dba_delete($mainkey, $db_con);
				$mainkey=dba_nextkey($db_con);
				continue;
			}
			
			
			if(preg_match("#^RTTH_[0-9]+#",$tablename)){
				REALTIME_RTTH_TABLE($tablename);
				$LOOP=true;
			}
			
			if(preg_match("#^cachehour_[0-9]+#",$tablename)){
				REALTIME_cachehour_FULL($tablename);
				$LOOP=true;
			}
			
			if($tablename=="UserAutDB"){$LOOP=true;}
			
			if(!$LOOP){
				$mainkey=dba_nextkey($db_con);
				continue;
			}
			
			$newQueries=$qeries;
			while (list ($index, $sql) = each ($qeries) ){
					$q->QUERY_SQL($sql);
					if($q->ok){unset($newQueries[$index]);}
				}
				
			dba_replace($mainkey,serialize($newQueries),$db_con);
			$mainkey=dba_nextkey($db_con);
	
		}
	
		@dba_close($db_con);
		if($c==0){@unlink($filename);}
		
		return false;
	}	
	
	public function ACCESS_PARSE_DB($filename,$xtime){
		$db_con = dba_open($filename, "r","db4");
		if(!$db_con){echo "DB open $filename failed\n";return false;}
		$mainkey=dba_firstkey($db_con);
		
		$date=date("Y-m-d H:i:s",$xtime);
		$hour=date("H",$xtime);
	
		while($mainkey !=false){
			$array=unserialize(dba_fetch($mainkey,$db_con));
			
			$mainkey=dba_nextkey($db_con);
			$ipaddr=$array["IPADDR"];
			$MAC=$array["MAC"];
			$uid=$array["UID"];
			$familysite=$array["WWW"];
			$category=$array["category"];
			$hits=$array["HITS"];
			$SIZE=$array["SIZE"];
			
			if($uid==null){ $uid=$this->MacToUid($MAC); }
			if($uid==null){ $uid=$this->IpToUid($ipaddr); }
			
			
			$md5=md5($date.$mainkey.serialize($array));
			if(strlen($MAC)>17){$MAC=$this->clean_mac($MAC);}
			$line="('$md5','$date','$hour','$familysite','$category','$hits','$SIZE','$MAC','$uid','$ipaddr')";
			$f[]=$line;
			if($GLOBALS["VERBOSE"]){echo "$line\n";}
				
		}
	
		@dba_close($db_con);
		if(count($f)>0){return $f; }
		return false;
	}	
	
	
	private function clean_mac($MAC){
		$f=explode(":",$MAC);
		foreach ( $f as $index=>$line ){
			if(strlen($line)>2){$line=substr($line, strlen($line)-2,2);$f[$index]=$line;continue;}
		}
		return @implode(":", $MAC);
	}
	
	private function MacToUid($mac=null){
		if($mac==null){return;}
		if(!isset($GLOBALS["USERSDB"])){$GLOBALS["USERSDB"]=unserialize(@file_get_contents("/etc/squid3/usersMacs.db"));}
		if(!isset($GLOBALS["USERSDB"]["MACS"][$mac]["UID"])){return;}
		if($GLOBALS["USERSDB"]["MACS"][$mac]["UID"]==null){return;}
		return trim($GLOBALS["USERSDB"]["MACS"][$mac]["UID"]);
	
	}
	private function IpToUid($ipaddr=null){
		if($ipaddr==null){return;}
		if(!isset($GLOBALS["USERSDB"])){$GLOBALS["USERSDB"]=unserialize(@file_get_contents("/etc/squid3/usersMacs.db"));}
		if(!isset($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"])){return;}
		if($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"]==null){return;}
		$uid=trim($GLOBALS["USERSDB"]["MACS"][$ipaddr]["UID"]);
	
	}
	
}