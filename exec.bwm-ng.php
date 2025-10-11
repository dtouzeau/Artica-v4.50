<?php
exit(); // disabled on 2015-02-14
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Bandwidth Monitor NG";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
$sock=new sockets();
$DisableBWMng=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableBWMng"));
if($DisableBWMng==1){stop();exit();}


if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--rotate"){$GLOBALS["OUTPUT"]=true;rotate();exit();}
if($argv[1]=="--purge"){$GLOBALS["OUTPUT"]=true;purge();exit();}




function restart($aspid=false) {
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
		
	stop(true);
	start(true);
	
}

function clean(){
	
	
}

function rotate(){
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$TimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";

	if($GLOBALS["VERBOSE"]){echo "TimeFile=$TimeFile\n";}
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	$xtime=$unix->file_time_min($TimeFile);
	if(!$GLOBALS['VERBOSE']){
		if($xtime<5){return;}
	}
	

	@unlink($TimeFile,time());
	@file_put_contents($TimeFile, time());
	
	$q=new mysql();
	$echo =$unix->find_program("echo");
	if(!$q->DATABASE_EXISTS("bwmng")){ $q->CREATE_DATABASE("bwmng"); }
	if(!$q->DATABASE_EXISTS("bwmng",true)){return;}
	@copy("/home/artica/bwm-ng/interfaces.csv", "/home/artica/bwm-ng/interfaces.csv.".time());
	@unlink("/home/artica/bwm-ng/interfaces.csv");
	
	$files=$unix->DirFiles("/home/artica/bwm-ng");
	
	if(system_is_overloaded(__FILE__)){
        if(system_is_overloaded(basename(__FILE__))){squid_admin_mysql(1, "{OVERLOADED_SYSTEM}, aborting the task...", ps_report(), __FILE__, __LINE__);exit();}
		
		return;}
	
	while (list($filename,$notused)=each($files)){
		if($filename=="interfaces.csv"){continue;}
		$filepath="/home/artica/bwm-ng/$filename";
		
		$filetime=$unix->file_time_min($filepath);
		if($filetime>60){@unlink($filepath);continue;}
		
		if($GLOBALS["VERBOSE"]){echo "Open $filepath {$filetime}mn\n";}
		
		$row = 1;
		if (($handle = fopen($filepath, "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
				$num = count($data);
				if($num==0){continue;}
					$row++;
					$uniq_key=md5(serialize($data));
					$Unix_Timestamp=$data[0];
					if(!is_numeric($Unix_Timestamp)){continue;}
					$Interface_Name=$data[1];
					if(trim($Interface_Name)==null){
						print_r($data);
						continue;}
					$BytesOut=intval($data[2]);
					$BytesIn=intval($data[3]);
					$BytesTotal=$data[4];
					$PacketsOut=$data[5];
					$PacketsIn=$data[6];
					$PacketsTotal=$data[7];
					if(($BytesOut==0) && ($BytesIn==0)){continue;}
					
					$Date=date("Y-m-d H:i:s",$Unix_Timestamp);
					$tableT=date("YmdH",$Unix_Timestamp)."_bwmrt";
					
					if($Interface_Name=="total"){
						$array_total[$tableT][]="('$uniq_key','$Date','$BytesOut','$BytesIn')";
						continue;
					}
					$table=date("YmdH",$Unix_Timestamp)."_bwmrh";
					$array_eths[$table][]="('$uniq_key','$Interface_Name','$Date','$BytesOut','$BytesIn')";
				}
			fclose($handle);
			if(system_is_overloaded(__FILE__)){break;}
		}
		
		
		if($GLOBALS["VERBOSE"]){echo "$filepath CLOSED: ".count($array_eths)." eths, ". count($array_total)." total\n";}
		
		if(array_to_interfaces($array_eths)){
			if(array_to_total($array_total)){
				if($GLOBALS["VERBOSE"]){echo "$filepath > DELETE\n";}
				@unlink($filepath);
			}
		}else{
			@unlink($filepath);
		}
		
		$array_eths=array();
		$array_total=array();

	}
	
	restart(true);
	if(system_is_overloaded(__FILE__)){
		if($GLOBALS["VERBOSE"]){echo "OVERLOADED !!!!\n";}
		return;}
	build_days();
	build_current_time();
	
}
function LIST_TABLES_TOTAL_HOUR(){
	$q=new mysql();
	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'bwmng' AND table_name LIKE '%_bwmrt'";
	$results=$q->QUERY_SQL($sql,"mysql");
	if(!$q->ok){writelogs("Fatal Error: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysqli_num_rows($results)."\n";}

	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if(preg_match("#[0-9]+_bwmrt$#", $ligne["c"])){
			$array[$ligne["c"]]=$ligne["c"];
		}
	}
	return $array;

}
function LIST_TABLES_NIC_HOUR(){
	$q=new mysql();
	$array=array();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'bwmng' 
			AND table_name LIKE '%_bwmrh'";
	$results=$q->QUERY_SQL($sql,"mysql");
	if(!$q->ok){writelogs("Fatal Error: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysqli_num_rows($results)."\n";}

	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if(preg_match("#[0-9]+_bwmrh$#", $ligne["c"])){
			$array[$ligne["c"]]=$ligne["c"];
		}
	}
	return $array;

}
function TIME_FROM_TABLE($tablename){
	preg_match("#([0-9]+)_([a-z]+)$#", $tablename,$re);
	$intval=$re[1];
	$Cyear=substr($intval, 0,4);
	$CMonth=substr($intval,4,2);
	$CDay=substr($intval,6,2);
	$CDay=str_replace("_", "", $CDay);
	$CHour=substr($intval,8,2);
	if(trim($CHour)==null){$CHour="00";}
	return strtotime("$Cyear-$CMonth-$CDay $CHour:00:00");
}

function build_days(){
	$tableT=date("YmdH")."_bwmrt";
	$tableR=date("YmdH")."_bwmrh";
	$tables=LIST_TABLES_TOTAL_HOUR();
	$q=new mysql();
	while (list($tablename,$arrz)=each($tables)){
		if($tableT==$tablename){continue;}
		if(!_build_days_total($tablename)){continue;}
		$q->QUERY_SQL("DROP TABLE $tablename","bwmng");
	}
	$tables=LIST_TABLES_NIC_HOUR();
	$q=new mysql();
	while (list($tablename,$arrz)=each($tables)){
		if($tableT==$tablename){continue;}
		if(!_build_days_hours($tablename)){continue;}
		$q->QUERY_SQL("DROP TABLE $tablename","bwmng");
	}	
	
	purge();
	
}
function _build_days_hours($tablesource){
	$xtime=TIME_FROM_TABLE($tablesource);
	$q=new mysql();
	$tablename=date("Ymd",$xtime)."_bwmdh";

	if($GLOBALS['VERBOSE']){echo "$tablesource -> $xtime -> $tablename\n";}

	if(!$q->TABLE_EXISTS($tablename,"bwmng")){
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
		`zMD5` varchar(90) NOT NULL,
		`interface` varchar(20) NOT NULL,
		`zhour` smallint(2) NOT NULL,
		`BytesOut` BIGINT UNSIGNED,
		`BytesIn` BIGINT UNSIGNED,
		PRIMARY KEY (`zMD5`),
		KEY `zhour` (`zhour`),
		KEY `interface` (`interface`)
		) ENGINE=MYISAM;";

		$q->QUERY_SQL($sql,"bwmng");
		if(!$q->ok){echo $q->mysql_error."\n"; return false;}
	}

	$sql="SELECT AVG(BytesOut) as BytesOut, AVG(BytesIn) as BytesIn, HOUR(zDate) as hours,interface
	FROM `$tablesource` GROUP BY HOUR(zDate),interface ORDER BY HOUR(zDate)";
	$results=$q->QUERY_SQL($sql,"bwmng");
	if(mysqli_num_rows($results)==0){
		if($GLOBALS["VERBOSE"]){echo "$sql\nOnly ". mysqli_num_rows($results)." results\n";}
		return true;
	}

	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){

		$zmd5=md5(serialize($ligne));
		$sql="INSERT IGNORE INTO bwmng.`$tablename` (`zMD5`,`zhour`,`BytesOut`,`BytesIn`,`interface`)
		VALUES ('$zmd5','{$ligne["hours"]}','{$ligne["BytesOut"]}','{$ligne["BytesIn"]}','{$ligne["interface"]}')";
		$q->QUERY_SQL($sql,"bwmng");
		if(!$q->ok){echo $q->mysql_error."\n"; return false;}
	}

	return true;
}


function _build_days_total($tablesource){
	$xtime=TIME_FROM_TABLE($tablesource);
	$q=new mysql();
	$tablename=date("Ymd",$xtime)."_bwmdt";
	
	if($GLOBALS['VERBOSE']){echo "$tablesource -> $xtime -> $tablename\n";}
	
	if(!$q->TABLE_EXISTS($tablename,"bwmng")){
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
		`zMD5` varchar(90) NOT NULL,
		`zhour` smallint(2) NOT NULL,
		`BytesOut` BIGINT UNSIGNED,
		`BytesIn` BIGINT UNSIGNED,
		PRIMARY KEY (`zMD5`),
		KEY `zhour` (`zhour`)
		) ENGINE=MYISAM;";
	
		$q->QUERY_SQL($sql,"bwmng");
		if(!$q->ok){echo $q->mysql_error."\n"; return false;}
	}
	
	$sql="SELECT AVG(BytesOut) as BytesOut, AVG(BytesIn) as BytesIn, HOUR(zDate) as hours
	FROM `$tablesource` GROUP BY HOUR(zDate) ORDER BY HOUR(zDate)";
	$results=$q->QUERY_SQL($sql,"bwmng");
	if(mysqli_num_rows($results)==0){
		if($GLOBALS["VERBOSE"]){echo "$sql\nOnly ". mysqli_num_rows($results)." results\n";}
		return true; 
	}	
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		
		$zmd5=md5(serialize($ligne));
		$sql="INSERT IGNORE INTO bwmng.`$tablename` (`zMD5`,`zhour`,`BytesOut`,`BytesIn`) 
		VALUES ('$zmd5','{$ligne["hours"]}','{$ligne["BytesOut"]}','{$ligne["BytesIn"]}')";
		$q->QUERY_SQL($sql,"bwmng");
		if(!$q->ok){echo $q->mysql_error."\n"; return false;}
	}
	
	return true;
}

function array_to_interfaces($array){
	
	if(!is_array($array)){return true;}
	$q=new mysql();
	
	
	while (list($tablename,$arrz)=each($array)){
		if(!$q->TABLE_EXISTS($tablename,"bwmng")){ 
			$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
				 `zMD5` varchar(90) NOT NULL,
		  		`interface` varchar(20) NOT NULL,
		  		`zDate` datetime,
		  		`BytesOut` BIGINT UNSIGNED,
		  		`BytesIn` BIGINT UNSIGNED,
		 		 PRIMARY KEY (`zMD5`),
		  		KEY `interface` (`interface`),
		  		KEY `zDate` (`zDate`)
				) ENGINE=MEMORY;";
			
			$q->QUERY_SQL($sql,"bwmng");
			if(!$q->ok){return false;}
		}
		
		echo "$tablename -> ".count($arrz)."\n";
		$sql="INSERT IGNORE INTO `$tablename` (`zMD5`,`interface`,`zDate`,`BytesOut`,`BytesIn`) VALUES ".@implode(",", $arrz);
		$q->QUERY_SQL($sql,"bwmng");
		if(!$q->ok){echo $q->mysql_error."\n"; return false;}
		
	}
	return true;
}
function array_to_total($array){

	if(!is_array($array)){return true;}
	$q=new mysql();


	while (list($tablename,$arrz)=each($array)){
		if(!$q->TABLE_EXISTS($tablename,"bwmng")){
			$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			`zMD5` varchar(90) NOT NULL,
			`zDate` datetime,
			`BytesOut` BIGINT UNSIGNED,
			`BytesIn` BIGINT UNSIGNED,
			PRIMARY KEY (`zMD5`),
			KEY `zDate` (`zDate`)
			) ENGINE=MEMORY;";
				
			$q->QUERY_SQL($sql,"bwmng");
			if(!$q->ok){
				echo $q->mysql_error."\n";
				return false;}
		}

		echo "$tablename -> ".count($arrz)."\n";
		$sql="INSERT IGNORE INTO bwmng.`$tablename` (`zMD5`,`zDate`,`BytesOut`,`BytesIn`) VALUES ".@implode(",", $arrz);
		$q->QUERY_SQL($sql,"bwmng");
		if(!$q->ok){
			echo $q->mysql_error."\n";
			return false;}

	}
	
	return true;
}

function LIST_TABLES_HOUR(){
	if(isset($GLOBALS["LIST_TABLES_HOUR"])){return $GLOBALS["LIST_TABLES_HOUR"];}
	$array=array();
	$q=new mysql();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'bwmng' AND table_name LIKE '%_bwmdh'";
	$results=$q->QUERY_SQL($sql,"bwmng");
	if(!$q->ok){writelogs("Fatal Error: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysqli_num_rows($results)."\n";}

	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if(preg_match("#[0-9]+_bwmdh$#", $ligne["c"])){
			$GLOBALS["LIST_TABLES_HOUR"][$ligne["c"]]=$ligne["c"];
			$array[$ligne["c"]]=$ligne["c"];
		}
	}
	return $array;

}
function LIST_TABLES_DAY(){
	if(isset($GLOBALS["LIST_TABLES_DAY"])){return $GLOBALS["LIST_TABLES_DAY"];}
	$array=array();
	$q=new mysql();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'bwmng' 
			AND table_name LIKE '%_bwmdt'";
	$results=$q->QUERY_SQL($sql,"bwmng");
	if(!$q->ok){writelogs("Fatal Error: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysqli_num_rows($results)."\n";}

	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if(preg_match("#[0-9]+_bwmdt$#", $ligne["c"])){
			$GLOBALS["LIST_TABLES_DAY"][$ligne["c"]]=$ligne["c"];
			$array[$ligne["c"]]=$ligne["c"];
		}
	}
	return $array;

}

function purge(){
	
	$q=new mysql();
	$currentDay=date("Ymd")."_bwmdh";
	$currentDayT=date("Ymd")."_bwmdt";
	$LIST_TABLES_HOUR=LIST_TABLES_HOUR();
	
	while (list($tablename,$arrz)=each($LIST_TABLES_HOUR)){
		if($currentDay==$tablename){continue;}
		echo "Cleaning $tablename\n";
		$q->QUERY_SQL("DROP TABLE `$tablename`","bwmng");
		
	}
	
	
	$LIST_TABLES_DAY=LIST_TABLES_DAY();
	while (list($tablename,$arrz)=each($LIST_TABLES_DAY)){
		if($currentDayT==$tablename){continue;}
		$xtime=TIME_FROM_TABLE($tablename);
		
		
		if(_purgeMonth($tablename)){
			echo "Cleaning $tablename ". date("Y-m-d",$xtime)."\n";
			$q->QUERY_SQL("DROP TABLE `$tablename`","bwmng");
			
		}
	
	}	
	
}

function _purgeMonth($tablesource){
	
	$xtime=TIME_FROM_TABLE($tablesource);
	$q=new mysql();
	$tablename=date("Ym",$xtime)."_bwmdm";
	$zDay=date("d",$xtime);
	if($GLOBALS['VERBOSE']){echo "$tablesource -> $xtime -> $tablename\n";}
	
	if(!$q->TABLE_EXISTS($tablename,"bwmng")){
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
		`zMD5` varchar(90) NOT NULL,
		`zDay` smallint(2) NOT NULL,
		`BytesOut` BIGINT UNSIGNED,
		`BytesIn` BIGINT UNSIGNED,
		PRIMARY KEY (`zMD5`),
		KEY `zDay` (`zDay`)
		) ENGINE=MYISAM;";
	
		$q->QUERY_SQL($sql,"bwmng");
		if(!$q->ok){echo $q->mysql_error."\n"; return false;}
		}
	
		$sql="SELECT AVG(BytesOut) as BytesOut, AVG(BytesIn) as BytesIn FROM `$tablesource`";
		$results=$q->QUERY_SQL($sql,"bwmng");
	if(mysqli_num_rows($results)==0){
		if($GLOBALS["VERBOSE"]){echo "$sql\nOnly ". mysqli_num_rows($results)." results\n";}
		return true;
		}
	
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
	
		$zmd5=md5(serialize($ligne).$zDay);
		$sql="INSERT IGNORE INTO bwmng.`$tablename` (`zMD5`,`zDay`,`BytesOut`,`BytesIn`)
		VALUES ('$zmd5','{$zDay}','{$ligne["BytesOut"]}','{$ligne["BytesIn"]}')";
		$q->QUERY_SQL($sql,"bwmng");
		if(!$q->ok){echo $q->mysql_error."\n"; return false;}
		}
	
		return true;	
	
	
}

function build_current_time(){
	
	$q=new mysql();
	$tableT=date("YmdH")."_bwmrt";
	$sql="SELECT AVG(BytesOut) as BytesOut, AVG(BytesIn) as BytesIn, MINUTE(zDate) as minutes 
		FROM `$tableT` GROUP BY MINUTE(zDate) ORDER BY MINUTE(zDate)";
	$results=$q->QUERY_SQL($sql,"bwmng");
	if(mysqli_num_rows($results)<2){ 
		if($GLOBALS["VERBOSE"]){echo "$sql\nOnly ". mysqli_num_rows($results)." results\n";}
		return; }
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($GLOBALS["VERBOSE"]){
			echo "{$ligne["minutes"]}mn - IN : ".FormatBytes(round($ligne["BytesIn"]/1024))."\n";
			echo "{$ligne["minutes"]}mn - OUT:".FormatBytes(round($ligne["BytesOut"]/1024))."\n";
		}
		
		$xdata[]=round($ligne["BytesOut"]/1024);
		$ydata[]=$ligne["minutes"];
		
		$xdata1[]=round($ligne["BytesIn"]/1024);
		$ydata1[]=$ligne["minutes"];
	}
	
	$cacheFile=PROGRESS_DIR."/BWMRT_OUT.db";
	@file_put_contents($cacheFile, serialize(array($ydata,$xdata)));
	@chmod($cacheFile,0755);

	$cacheFile=PROGRESS_DIR."/BWMRT_IN.db";
	@file_put_contents($cacheFile, serialize(array($ydata1,$xdata1)));
	@chmod($cacheFile,0755);	
}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("bwm-ng");
	$q=new mysql();
	
	
	

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	$EnableBwmNG=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBwmNG"));
	
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($SquidPerformance>2){$EnableBwmNG=0;}


	if($EnableBwmNG==0){
		if(is_dir("/home/artica/bwm-ng")){
			$rm=$unix->find_program("rm");
			shell_exec("$rm -rf /home/artica/bwm-ng");
		}
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableKerbAuth,EnableCNTLM)\n";}
		return;
	}
	
	
	

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$ETHZ=array();
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	unset($NETWORK_ALL_INTERFACES["lo"]);
	foreach ($NETWORK_ALL_INTERFACES as $eth=>$xmain){
		if($GLOBALS["VERBOSE"]){echo "Report $eth {$xmain["IPADDR"]} state:{$xmain["STATE"]}\n";}
		if($xmain["STATE"]=="UNKNOWN"){$xmain["STATE"]="UP";}
		$eth=trim($eth);
		if($eth==null){continue;}
		if($xmain["IPADDR"]=="0.0.0.0"){continue;}
        if($xmain["STATE"]=="yes"){$xmain["STATE"]="UP";}
		if($xmain["STATE"]<>"UP"){continue;}
		if($GLOBALS["VERBOSE"]){echo "Added $eth {$xmain["IPADDR"]}\n";}
		$ETHZ[]=$eth;
	}
	
	if(count($ETHZ)==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} no interface found\n";}
		return;
	}	
	$interfaces_txt=@implode(",", $ETHZ);
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Listens on $interfaces_txt\n";}
	@mkdir("/home/artica/bwm-ng",0755,true);
	if(is_file("/home/artica/bwm-ng/interfaces.csv")){
		@copy("/home/artica/bwm-ng/interfaces.csv","/home/artica/bwm-ng/interfaces.csv.".time());
	}
	$cmd="$nohup $Masterbin -D -t 5000 -o csv  -u bits -T rate -c 0 -a 0 --interfaces $interfaces_txt > /home/artica/bwm-ng/interfaces.csv 2>&1 &";
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	
	shell_exec($cmd);
	sleep(1);
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

	
	


}

function PID_NUM(){
	$unix=new unix();
	return $unix->PIDOF($unix->find_program("bwm-ng"),true);
}




