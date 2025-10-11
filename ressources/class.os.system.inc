<?php
class usb{
	var $uuid;
	var $path;
	var $LABEL;
	var $TYPE;
	var $SEC_TYPE;
	var $mounted;
	var $vendor;
	var $model;
	var $product;
	var $manufacturer;
	var $id;
	var $speed;
	var $size;
	var $pourc;
	var $ID_MODEL;
	var $ID_VENDOR;
	var $img;
	var $ID_FS_TYPE;
	
public function __construct($uuid=null){
		if($uuid<>null){
			$this->uuid=$uuid;
			$this->lOADINFOS();
		}
	}
	

	
	
function getPartypename($id){
		if(strlen($id)==1){$id="0$id";}
		include_once(dirname(__FILE__)."/class.os.system.datas.inc");
		$p=new os_system_datas();
		$part_type=$p->part_type_array();
		return $part_type[$id];
	}
	
	
	public function isMounted($dev){
		if($dev==null){return false;}
		$sock=new sockets();
		$dev=$sock->getFrameWork("cmd.php?check-dev=$dev");
		$dev=str_replace("/","\/",$dev);
		//echo $dev;
		$datas=explode("\n",@file_get_contents("/proc/mounts"));
		foreach ($datas as $num=>$val){
			if(preg_match("#$dev\s+(.+?)\s+(.+?)\s+#",$val,$re)){return $re[1];}
		}
		return false;
		
	}
	
	
	private function lOADINFOS(){
		if(empty($_GLOBAL["usb_list"])){
			if(!file_exists(dirname(__FILE__).'/usb.scan.inc')){return null;}
			include_once(dirname(__FILE__).'/usb.scan.inc');
			if(!is_array($_GLOBAL["usb_list"])){return null;}	
		}
		
		if(!is_array($_GLOBAL["disks_list"])){
			if(is_file(dirname(__FILE__).'/usb.scan.inc')){include dirname(__FILE__).'/usb.scan.inc';}

		}
		unset($array);
		$array=$_GLOBAL["usb_list"][$this->uuid];
		if($GLOBALS["VERBOSE"]){echo "USB::lOADINFOS() $this->uuid: label:{$array["LABEL"]}\n";}
		if($GLOBALS["VERBOSE"]){echo "USB::lOADINFOS() $this->uuid: TYPE:{$array["TYPE"]}\n";}
		if($GLOBALS["VERBOSE"]){echo "USB::lOADINFOS() $this->uuid: SEC_TYPE:{$array["SEC_TYPE"]}\n";}
		if(isset($array["mounted"])){if($GLOBALS["VERBOSE"]){echo "USB::lOADINFOS() $this->uuid: mounted:{$array["mounted"]}\n";}}
		if($GLOBALS["VERBOSE"]){echo "USB::lOADINFOS() $this->uuid: ID_VENDOR:{$array["ID_VENDOR"]}\n";}
		if($GLOBALS["VERBOSE"]){echo "USB::lOADINFOS() $this->uuid: ID_FS_TYPE:{$array["ID_FS_TYPE"]}\n";}
		
		$this->path=trim($array["PATH"]);
		$this->img="usb-128.png";
	
		$this->LABEL=trim($array["LABEL"]);
		$this->TYPE=trim($array["TYPE"]);
		$this->SEC_TYPE=trim($array["SEC_TYPE"]);
		if(isset($array["mounted"])){$this->mounted=trim($array["mounted"]);}
		$this->ID_MODEL=trim($array["ID_MODEL"]);
		$this->ID_VENDOR=trim($array["ID_VENDOR"]);
		$this->ID_FS_TYPE=trim($array["ID_FS_TYPE"]);
	
	if(preg_match("#(.+?)[0-9]+$#",$this->path,$ri)){
		if(is_array($_GLOBAL["disks_list"]["{$ri[1]}"])){
			if(is_array($_GLOBAL["disks_list"]["{$ri[1]}"]["PARTITIONS"])){
			$array2=$_GLOBAL["disks_list"]["{$ri[1]}"];
			$this->model=trim($array2["ID_MODEL_1"]);
			if($this->model==null){$this->model=trim($array2["ID_MODEL_2"]);}
			$this->size=trim($array2["SIZE"]);
			$this->img="disk-128.png";
			
			}
		}
	}		
		
	$tbl=explode(";",$array["model"]);
	
		if(is_array($tbl)){
			if(count($tbl)>3){
			if(trim($tbl[0])<>null){$this->vendor=trim($tbl[0]);}
			$this->model=trim($tbl[1]);
			$this->product=trim($tbl[2]);
			$this->manufacturer=trim($tbl[3]);
			$this->id=trim($tbl[4]);
			$this->speed=trim($tbl[5]);
			if($this->speed=="Mbps"){$this->speed="?? Mbps";}
			}
		}
		
	if(preg_match("#(.+?);(.+?);(.+?);([0-9]+)%#",$array["SIZE"],$re)){
	 $this->size=$re[1];
	 $this->pourc=$re[4];
	}


	if($this->LABEL==null){$this->LABEL=$this->path;}
	if(trim($this->model)==null){if($this->ID_MODEL<>null){$this->model=$this->ID_MODEL;}}
	if(trim($this->vendor)==null){if($this->ID_VENDOR<>null){$this->vendor=$this->ID_VENDOR;}}
	
		
		
	}
	
}




function ProcessExistsBypattern($pattern){
	$pgrep=LOCATE_PGREP();
	$array=ProcessListBypattern($pattern);
	events_class("ProcessExistsBypattern:: found an array of ". count($array)." rows");
	if(count($array)>0){return true;}
	return false;
}

function ProcessListBypattern($pattern){
	if($_GET["LOCATE_PGREP"]==null){$_GET["LOCATE_PGREP"]=LOCATE_PGREP();}
	$pgrep=$_GET["LOCATE_PGREP"];
	$tmp=OS_FILE_TEMP();
	$cmd="$pgrep -f \"$pattern\" >$tmp 2>&1";
	events_class("ProcessListBypattern:: $cmd");
	system($cmd);
	$datas=explode("\n",@file_get_contents($tmp));
	@unlink($tmp);
	foreach ($datas as $index=>$line){
		if(preg_match("#^([0-9]+)#",$line,$re)){
			events_class("ProcessListBypattern:: found={$re[1]}");
			$array[]=$re[1];
		}
		
	}
	
	if(!is_array($array)){return array();}
	return $array;
}

	function OS_FILE_TEMP(){
		if(function_exists("sys_get_temp_dir")){
			return tempnam(sys_get_temp_dir(), 'artica-php');
		}
		return "/tmp/artica-php-".md5(date("Y-m-d H:i:s"));
		
	}



function LOCATE_PGREP(){
	if(is_file("/usr/bin/pgrep")){return "/usr/bin/pgrep";}
	if(is_file("/bin/pgrep")){return "/bin/pgrep";}
}

function ListOnlyDirectories($path){
	$dir_handle = @opendir($path);
	while ($file = readdir($dir_handle)) {
		if($file=='.'){continue;}
		if($file=='..'){continue;}
		
  		$results[]=$file;
	}

	closedir($dir_handle);
	if(!is_array($results)){return array();}
	return $results;
	
}

function events_class($text){
	if(!is_array($_GET["FILE_DEBUG"])){return ;}
	$f=new debuglogs();
	for($i=0;$i<count($_GET["FILE_DEBUG"]);$i++){
			$f->events($text,$_GET["FILE_DEBUG"][$i]);
		}
	}
function getSystemMemInfo(){
		$data = explode("\n", file_get_contents("/proc/meminfo"));
		$meminfo = array();
		foreach ($data as $line) {
			if(trim($line)==null){continue;}
			if(strpos(" $line", ":")==0){continue;}
			list($key, $val) = explode(":", $line);
			$val=trim($val);
			if(preg_match("#^([0-9\.]+)\s+#", $val,$re)){$val=$re[1];}
			$meminfo[$key] = trim($val);
		}
		return $meminfo;
	}	

	
function system_is_overloaded($file=null):bool{
	$d=new debuglogs();
	if($file<>null) {
		$file = basename($file);
	}
	$unix=new unix();
	$notlog["exec.squid.blacklists.php"]=true;
	$notlog["exec.squid-tail-injector.php"]=true;
	$notlog["exec.squid.ad.import.php"]=true;
	$notlog["exec.status.php"]=true;
	$notlog["exec.squid.stats.php"]=true;
	$notlog["exec.smtp-senderadv.php"]=true;
	
	if(!isset($notlog[$file])){$notlog[$file]=false;}
	
	if(!isset($GLOBALS["CPU_NUMBER"])){
		$GLOBALS["CPU_NUMBER"]=$unix->CPU_NUMBER();
	}
	
	$array_mem=getSystemMemInfo();
	$MemFree=$array_mem["MemFree"];
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$GLOBALS["SYSTEM_INTERNAL_MEM"]=$MemFree;
	$GLOBALS["SYSTEM_INTERNAL_MEMM"]=round($MemFree/1024);
	$GLOBALS["SYSTEM_INTERNAL_LOAD"]=$internal_load;
	if($MemFree<51200){
		$d->events("Load: Not enough memory {$MemFree}KB < 102400 ","/var/log/artica-postfix/syslog.log");
		os_system_ToSyslog("Not enough memory {$MemFree}KB < 102400");
		return true;
	}

	$cpunum=$GLOBALS["CPU_NUMBER"];
	if($cpunum==0){$cpunum=$unix->CPU_NUMBER();$GLOBALS["CPU_NUMBER"]=$cpunum;}
	if($cpunum==0){$cpunum=2;$GLOBALS["CPU_NUMBER"]=$cpunum;}

	if(!$unix->IsProductionTime()){
		$cpunum=$GLOBALS["CPU_NUMBER"]+5;
	}else{
		$cpunum=$cpunum+2;
	}

	$called=null;
	if($GLOBALS["VERBOSE"]){
	if(function_exists("debug_backtrace")){
		try {
			$trace=@debug_backtrace();
			if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
			} catch (Exception $e) {}
		}	
		if(!$notlog[$file]){
			$d->events("Load: $internal_load max>=$cpunum  $called","/var/log/artica-postfix/syslog.log");
		}
	}

	if($file==null){$file=basename(__FILE__);}
	
	$GLOBALS["SYSTEM_MAX_LOAD"]=$cpunum;
	if($internal_load>=$cpunum){
		if(function_exists("debug_backtrace")){try {$trace=@debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}} catch (Exception $e) {}}		
		$d->events("Load: OVERLOADED $called","/var/log/artica-postfix/syslog.log");
		if($GLOBALS["VERBOSE"]){echo "$file system_is_overloaded():: Load: $internal_load max=$cpunum SYSTEM OVERLOADED $called\n";}
		
		if(isset($notlog[$file])){
			if(!$notlog[$file]){
				os_system_ToSyslog("OVERLOADED [$internal_load] ( Max: $cpunum) $called ");
				$d->events("$file system_is_overloaded():: Load: $internal_load max=$cpunum SYSTEM OVERLOADED $called","/var/log/artica-postfix/artica-status.debug");
			}
		}

		if(function_exists("squid_admin_mysql")) {
			if(!isset($GLOBALS["OVERLOADED_SYSTEM_REPORT"])) {
				squid_admin_mysql(2, "{OVERLOADED_SYSTEM}: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",
					ps_report(), __FILE__, __LINE__);
				$GLOBALS["OVERLOADED_SYSTEM_REPORT"]=true;
			}
		}


		return true;
		
	}
	//$d->events("$file system_is_overloaded():: CPU: $cpunum, load: $internal_load, max=$cpunum GOOD","/var/log/artica-postfix/artica-status.debug");
	return false;

	
}
function ps_report():string{
	$TT[]="<table>";
	$TT[]="<tr><th>%CPU</th><th>%MEM</th><TH>VSZ</TH><th>RSS</th><th>CMD</th></tr>";
	exec("ps -aux 2>&1",$lines);
	foreach ($lines as $line) {
		if (!preg_match("#^(.*?)\s+([0-9]+)\s+([0-9\.]+)\s+([0-9\.]+)\s+([0-9]+)\s+([0-9]+)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.+)#", trim($line), $re)) {
			continue;
		}
		$PERC_CPU = $re[3];
		$PERC_MEM = $re[4];
		$VSZ      = $re[5];
		$RSS      = $re[6];
		$CMD      = $re[11];
		if (intval($PERC_CPU) == 0) {
			if (intval($PERC_MEM) == 0)
				continue;
		}

		$CMD=str_replace("/usr/bin/php /usr/share/artica-postfix/","(Artica) ",$CMD);
		$CMD=str_replace("/usr/bin/php -q /usr/share/artica-postfix/","(Artica) ",$CMD);
		$CMD=str_replace("/usr/bin/python3","(python3)",$CMD);
		$CMD=str_replace("/usr/bin/memcached -s /var/run/memcached.sock -a 777","(MemCached)",$CMD);
		$CMD=str_replace("/usr/share/munin/munin-update","(Munin)",$CMD);
		$CMD=str_replace("/bin/ufdbguardd -c /etc/squid3/ufdbGuard.conf","(Web-Filtering)",$CMD);
		if(preg_match("#--kid squid-([0-9]+) -YC -f .*?squid\.conf#",$CMD,$ri)){
			$CMD="Proxy (instance {$ri[1]})";
		}

		$TT[]="<tr>";
		$TT[]="<td width=1% nowrap><strong>$PERC_CPU</strong></td>";
		$TT[]="<td width=1% nowrap><strong>$PERC_MEM</strong></td>";
		$TT[]="<td width=1% nowrap>$VSZ</td>";
		$TT[]="<td width=1% nowrap>$RSS</td>";
		$TT[]="<td width=99%>$CMD</td>";
		$TT[]="</tr>";




	}
	$TT[]="</table>";
	return @implode("",$TT);
}

function os_system_ToSyslog($text,$error=false){

	if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
	if(function_exists("openlog")){openlog("class.os.system.inc", LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}


function systemMaxOverloaded(){
	$d=new debuglogs();
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	if(!isset($GLOBALS["systemMaxOverloaded"])){
			$sock=new sockets();
			$GLOBALS["systemMaxOverloaded"]=$sock->GET_INFO("systemMaxOverloaded");
	}
	if($GLOBALS["systemMaxOverloaded"]==null){$GLOBALS["systemMaxOverloaded"]=17;}
	if($internal_load>=$GLOBALS["systemMaxOverloaded"]){
		if($GLOBALS["VERBOSE"]){echo "systemMaxOverloaded():: Load: $internal_load/{$GLOBALS["systemMaxOverloaded"]} SYSTEM REALLY OVERLOADED die all processes during this load\n";}
		$GLOBALS["SYSTEM_INTERNAL_LOAD"]=$internal_load;
		$d->events(basename(__FILE__)." systemMaxOverloaded():: Load: $internal_load/{$GLOBALS["systemMaxOverloaded"]} SYSTEM REALLY OVERLOADED die all processes during this load","/var/log/artica-postfix/artica-status.debug");
		return true;
	}
	
}

function SquidStatisticsTasksOverTime(){
	if(!isset($GLOBALS["ArticaProxyStatisticsMaxTime"])){
		$sock=new sockets();
		$GLOBALS["ArticaProxyStatisticsMaxTime"]=$sock->GET_INFO("ArticaProxyStatisticsMaxTime");
		if(!is_numeric($GLOBALS["ArticaProxyStatisticsMaxTime"])){$GLOBALS["ArticaProxyStatisticsMaxTime"]=420;}
		if($GLOBALS["ArticaProxyStatisticsMaxTime"]<5){$GLOBALS["ArticaProxyStatisticsMaxTime"]=420;}
	}
	
	if(is_file("/var/run/squid-stats-central.stop")){
		if($GLOBALS["VERBOSE"]){echo "/var/run/squid-stats-central.stop -> STOP !\n";}
		return true;}
	
	$unix=new unix();
	$time=0;
	if(is_file("/var/run/squid-stats-central.run")){
		$time=$unix->file_time_min("/var/run/squid-stats-central.run");
		if($GLOBALS["VERBOSE"]){echo "SquidStatisticsTasksOverTime(): Last execution: {$time}Mn MAX:{$GLOBALS["ArticaProxyStatisticsMaxTime"]}mn\n";}
	}
	
	
	if($time>=$GLOBALS["ArticaProxyStatisticsMaxTime"]){
		if($GLOBALS["VERBOSE"]){echo "/var/run/squid-stats-central.run {$time}Mn > {$GLOBALS["ArticaProxyStatisticsMaxTime"]}Mn STOP! \n";}
		
		
		return true;}
		
		if($GLOBALS["VERBOSE"]){
			$reste=$GLOBALS["ArticaProxyStatisticsMaxTime"]-$time;
			echo "SquidStatisticsTasksOverTime(): Again :{$reste}Mn OK\n";}
	return false;
}

function LOCATE_PROGRAM($file):string{
	$array[]="/usr/sbin";
	$array[]="/usr/bin";
	$array[]="/bin";
	$array[]="/sbin";
	$array[]="/usr/local/bin";
	$array[]="/usr/local/sbin";
	$array[]="/usr/bin/X11";
	foreach ($array as $path){
		if(is_file("$path/$file")){return "$path/$file";}
	}
	return "";
}


function LOCATE_SYSLOG_CONF(){
	if(is_file('/etc/syslog.conf')){return '/etc/syslog.conf';}
	if(is_file('/etc/syslog-ng/syslog-ng.conf')){return '/etc/syslog-ng/syslog-ng.conf';}
	if(is_file('/etc/rsyslog.d/50-default.conf')){return '/etc/rsyslog.d/50-default.conf';}
	if(is_file('/etc/rsyslog.conf')){return '/etc/rsyslog.conf';}
}
function LOCATE_SYSLOG_NG_CONF():string{
  if(is_file('/etc/syslog-ng/syslog-ng.conf')){ return '/etc/syslog-ng/syslog-ng.conf';}
  if(is_file('/etc/syslog-ng.conf')){return '/etc/syslog-ng.conf';}
  return "";
}
//############################################################################## 
//############################################################################## 
function MAILLOG_FROM_SYSLOGNG():string{
	$path=LOCATE_SYSLOG_NG_CONF();
	   if(!is_file($path)){return "";}
	   $l=explode("\n",@file_get_contents($path));
	   foreach ($l as $line){
	   		if(preg_match("#^destination.+?mail\s+.+?\((.+?)\)#",$line,$re)){
		   		$file=$re[1];
		   		$file=str_replace('"','',$file);
		   		$file=str_replace("'",'',$file);
		   		return $file;
	   		}
	   }
	   return "";
   }

function LOCATE_PHP5_BIN():string{
  if(isset($GLOBALS["LOCATE_PHP5_BIN"])){return $GLOBALS["LOCATE_PHP5_BIN"];}
  if(is_file('/usr/bin/php5')){$GLOBALS["LOCATE_PHP5_BIN"]='/usr/bin/php5';return '/usr/bin/php5';}
  if(is_file('/usr/bin/php')){$GLOBALS["LOCATE_PHP5_BIN"]='/usr/bin/php';return '/usr/bin/php';}
  return "";

}

function LOCATE_MAILLOG_PATH(){

if($_GET["CLASS_DEBUG"]){echo "MAILLOG_PATH()\n";}
	if(is_file(LOCATE_SYSLOG_NG_CONF())){
		if($_GET["CLASS_DEBUG"]){echo "RETURN MAILLOG_FROM_SYSLOGNG()\n";}
	   return MAILLOG_FROM_SYSLOGNG();
		}
	
	$logconfig=LOCATE_SYSLOG_CONF();
	$filedatas=@file_get_contents($logconfig);
	if($_GET["CLASS_DEBUG"]){echo "$logconfig=". strlen($filedatas)." bytes\n";}
	
	if(preg_match("#mail\.=info.+?-([\/a-zA-Z_0-9\.]+)?#is",$filedatas,$re)){
		return $re[1];
	}
	  
	if(preg_match("#mail\.\*.+?-([\/a-zA-Z_0-9\.]+)?#is",$filedatas,$re)){
		return $re[1];
	}
	if(preg_match('#destination mailinfo[\s\{a-z]+\("(.+?)"#is',$filedatas,$re)){
		return $re[1];
	}
	
if($_GET["CLASS_DEBUG"]){echo "RETURN FAILED FROM $logconfig\n";}

}  

?>
