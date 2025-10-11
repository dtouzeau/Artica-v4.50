<?php
include_once(dirname(__FILE__)."/class.shorewall.stats.inc");
class shorewall_stats{
	private $q;
	private $BackupDir;
	
	function shorewall_stats(){
		$this->q=new mysql_shorewall();
		$this->q->CheckTables();
	}
	
	public function ComprimHours(){
		$sock=new sockets();
		$this->BackupDir=$sock->GET_INFO("ShorewallBackupdir");
		if($this->BackupDir==null){$this->BackupDir="/home/shorewall/backup-stats";}
		$currentHour=date("YmdH");
		$TABLES=$this->q->LIST_TABLES_HOURS();
		$current="FWH_$currentHour";
		while (list ($tablesource, $xtime) = each ($TABLES) ){
			if($tablesource==$current){continue;}
			if($GLOBALS["VERBOSE"]){echo "ComprimHoursPerform($tablesource,$xtime)\n";}
			if(!$this->ComprimHoursPerform($tablesource,$xtime)){continue;}
			if(!$this->TableToCSVGz($tablesource)){continue;}
			$this->q->QUERY_SQL("DROP TABLE `$tablesource`");
		}
		
	}
	
	
	private function ComprimHoursPerform($tablesource,$xtime){
		if(!is_numeric($xtime)){return false;}
		$currentDay=date("Ymd",$xtime);
		$tablework="FWD_$currentDay";
		$f=array();
		if(!$this->q->BuildDayTable($xtime)){return false;}
		
		$sql="SELECT COUNT(*) AS `HITS`, HOUR(ZDATE) as `HOUR`,
				ZONE_FROM,ZONE_TO,ACTION,NIC_IN,NIC_OUT,MAC_SRC,IP_SRC,IP_DST,PROTO,PORT
				FROM `$tablesource`
				GROUP BY HOUR,ZONE_FROM,ZONE_TO,ACTION,NIC_IN,NIC_OUT,MAC_SRC,IP_SRC,IP_DST,PROTO,PORT";
		$results=$this->q->QUERY_SQL($sql);
		if(!$this->q->ok){
			echo "{$this->q->mysql_error}\n$sql\n";
			return false;
		}
		
		$prefix="INSERT IGNORE INTO `$tablework` 
		(`ZMD5`,`HITS`,`HOUR`,`ZONE_FROM`,`ZONE_TO`,`ACTION`,`NIC_IN`,`NIC_OUT`,`MAC_SRC`,`IP_SRC`,`IP_DST`,`PROTO`,`PORT`) VALUES ";
		
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$ZMD5=md5(serialize($ligne));
			$HITS=$ligne["HITS"];
			$HOUR=$ligne["HOUR"];
			$ZONE_FROM=$ligne["ZONE_FROM"];
			$ZONE_TO=$ligne["ZONE_TO"];
			$ACTION=$ligne["ACTION"];
			$NIC_IN=$ligne["NIC_IN"];
			$NIC_OUT=$ligne["NIC_OUT"];
			$MAC_SRC=$ligne["MAC_SRC"];
			$IP_SRC=$ligne["IP_SRC"];
			$IP_DST=$ligne["IP_DST"];
			$PROTO=$ligne["PROTO"];
			$PORT=$ligne["PORT"];
			$f[]="('$ZMD5','$HITS','$HOUR','$ZONE_FROM','$ZONE_TO','$ACTION','$NIC_IN','$NIC_OUT','$MAC_SRC','$IP_SRC','$IP_DST','$PROTO','$PORT')";
			
			if(count($f)>500){
				$this->q->QUERY_SQL($prefix.@implode(",", $f));
				if(!$this->q->ok){ echo "{$this->q->mysql_error}\n"; return false; } 
				$f=array();
			}
		}
				
		if(count($f)>0){
			$this->q->QUERY_SQL($prefix.@implode(",", $f));
			if(!$this->q->ok){echo "{$this->q->mysql_error}\n";return false;}
			$f=array();
		}	

		if($GLOBALS["VERBOSE"]){echo "$tablework -> TRUE\n";}
		return true;
				
		
	}
	
	private function TableToCSVGz($tablename){
		$unix=new unix();
		$tmpdir=$unix->TEMP_DIR();
		@chmod($tmpdir,0777);
		if(is_file("$tmpdir/$tablename.csv")){@unlink("$tmpdir/$tablename.csv");}
		$sql="SELECT * FROM `$tablename` INTO OUTFILE '$tmpdir/$tablename.csv' FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\\n'";
		$this->q->QUERY_SQL($sql);
		if(!$this->q->ok){ echo "{$this->q->mysql_error}\n"; return false; }
		@mkdir($this->BackupDir,0755,true);
		$unix->compress("$tmpdir/$tablename.csv", "$this->BackupDir/$tablename.gz");
		@unlink("$tmpdir/$tablename.csv");
		return true;
	}
	
	
}