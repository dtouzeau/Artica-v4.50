<?php
$GLOBALS["FORCE"]=false;$GLOBALS["DEBUG"]=false;$GLOBALS["WAITFORATIME"]=false;$GLOBALS["WAITFORATIME"]=false;$GLOBALS["WAITFORATIME_SEQUENCE"]=0;
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if(is_array($argv)){if(preg_match("#--debug#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;}if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if(is_array($argv)){if(preg_match("#--waitForATime=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["WAITFORATIME"]=true;$GLOBALS["WAITFORATIME_SEQUENCE"]=$re[1];}}
if(count($argv)>0){sleep(5);}
if(!is_numeric($GLOBALS["WAITFORATIME_SEQUENCE"])){$GLOBALS["WAITFORATIME_SEQUENCE"]=0;}
$GLOBALS["WAITFORATIME_SEQUENCE"]=$GLOBALS["WAITFORATIME_SEQUENCE"]+1;
if($GLOBALS["WAITFORATIME_SEQUENCE"]>9){exit();}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["posix_getuid"]=0;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__).  "/ressources/smtp/class.smtp.loader.inc");
include_once(dirname(__FILE__).'/ressources/class.mime.parser.inc');
include_once(dirname(__FILE__).'/ressources/class.rfc822.addresses.inc');

$GLOBALS["MultipleAdvLoad"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MultipleAdvLoad"));
$GLOBALS["MultipleAdvUseMemory"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MultipleAdvUseMemory"));
$GLOBALS["MultipleAdvMaxRunningProcs"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MultipleAdvMaxRunningProcs"));
if(!is_numeric($GLOBALS["MultipleAdvUseMemory"])){$GLOBALS["MultipleAdvUseMemory"]=0;}
if(!is_numeric($GLOBALS["MultipleAdvLoad"])){$GLOBALS["MultipleAdvLoad"]=1;}
if(!is_numeric($GLOBALS["MultipleAdvMaxRunningProcs"])){$GLOBALS["MultipleAdvMaxRunningProcs"]=20;}
if(count($argv)>1){	if($argv[1]=="--instance-send"){$domainname=$argv[2];$instance=$argv[3];$instanceid=$argv[4];$prefix="[$domainname/$instanceid][$instance]";}}else{$prefix="ROUTER";}
if(count($argv)==1){smtp::events("Mode router executed","MAIN",__FILE__,__LINE__);ScanSpoolEvents("Executed",__LINE__);}
$ProcessesRunned=checkMaxProcessNumber();

	if($ProcessesRunned>0){
			$unix=new unix();
			$cmdline=@implode(" ", $argv);
			$MultipleAdvMaxRunningProcs=$GLOBALS["MultipleAdvMaxRunningProcs"]+1;
			smtp::events("{$prefix}Max Process reached {$GLOBALS["CURRENT_INSTANCES_LOADED"]} die()","MAIN",__FILE__,__LINE__);
			//$unix->THREAD_COMMAND_SET($cmdline);
			exit();	
		}

	if(CheckLoad()){
			$unix=new unix();
			$cmdline=@implode(" ", $argv);
			smtp::events("{$prefix}Server Loaded -> {$GLOBALS["DEBUG_LOG_LOAD"]}  die()....","MAIN",__FILE__,__LINE__);
			//$unix->THREAD_COMMAND_SET($cmdline);
			exit();
	}

	if($GLOBALS["WAITFORATIME"]){
		smtp::events("{$prefix}Sleeping 5 seconds, sequence={$GLOBALS["WAITFORATIME_SEQUENCE"]}","MAIN",__FILE__,__LINE__);	
		sleep(5);
	}


	$GLOBALS["QUEUE_DIRECTORY_ERROR"]="/var/spool/artica-adv-errors";

	if($GLOBALS["VERBOSE"]){echo "->QueueDirectoryConfig();\n";}
	QueueDirectoryConfig();
	if($GLOBALS["VERBOSE"]){echo "->QueueDirectoryConfig();DONE....\n";}
	if($argv[1]=="--remount"){QueueDirectoryRemount();exit;}
	if($argv[1]=="--instance-send"){InstanceSend($argv[2],$argv[3],$argv[4]);exit;}
	if($argv[1]=="--config"){$GLOBALS["VERBOSE"]=true;PopulateConfig($argv[2],$argv[3]);exit;}
	if($argv[1]=="--sendmail"){$GLOBALS["VERBOSE"]=true;sendtestmail($argv[2],$argv[3]);exit;}


smtp::events("{$prefix}-> Scan_Pools();","MAIN",__FILE__,__LINE__);	
Scan_Pools();
if($GLOBALS["MultipleAdvUseMemory"]==1){
	$GLOBALS["QUEUE_DIRECTORY"]="/var/spool/artica-adv";
	Scan_Pools();
}

function QueueDirectoryConfig(){
	if($GLOBALS["MultipleAdvUseMemory"]==0){$GLOBALS["QUEUE_DIRECTORY"]="/var/spool/artica-adv";return;}
	if(QueueDirectoryIsMounted()){$GLOBALS["QUEUE_DIRECTORY"]="/var/spool/artica-advmem";return;}
	$MultipleAdvMemorySize=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MultipleAdvMemorySize"));
	if(!is_numeric($MultipleAdvMemorySize)){$GLOBALS["QUEUE_DIRECTORY"]="/var/spool/artica-adv";return;}
	if($MultipleAdvMemorySize==0){$GLOBALS["QUEUE_DIRECTORY"]="/var/spool/artica-adv";return;}
	$unix=new unix();
	$mount=$unix->find_program("mount");	
	@mkdir("/var/spool/artica-advmem",0755,true);
	$cmd="$mount -t tmpfs -o size={$MultipleAdvMemorySize}M tmpfs /var/spool/artica-advmem";
	shell_exec($cmd);
	$GLOBALS["QUEUE_DIRECTORY"]="/var/spool/artica-adv";
}

function checkMaxProcessNumber(){
	exec("/usr/bin/pgrep -f \"".basename(__FILE__)."\" 2>&1",$results);
	$CurrentInstances=count($results);
	$maxInstances=$GLOBALS["MultipleAdvMaxRunningProcs"]+1;
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$GLOBALS["CURRENT_INSTANCES_LOADED"]=" $CurrentInstances instance(s)/$maxInstances instance(s) Load:$internal_load";
	if($CurrentInstances>$maxInstances){return $CurrentInstances;}
	return 0;
}


function CheckLoad(){
	if(!is_numeric($GLOBALS["MultipleAdvLoad"])){$GLOBALS["MultipleAdvLoad"]=1;}
	if($GLOBALS["MultipleAdvLoad"]==0){return false;}
	if($GLOBALS["MultipleAdvLoad"]==1){
		if(system_is_overloaded(__FILE__)){
			$array_load=sys_getloadavg();
			$internal_load=$array_load[0];	
			$GLOBALS["DEBUG_LOG_LOAD"]=$internal_load;
			return true;
		}
	}
	
	if($GLOBALS["MultipleAdvLoad"]>1){
		$array_load=sys_getloadavg();
		$internal_load=$array_load[0];
		$GLOBALS["DEBUG_LOG_LOAD"]=$internal_load;
		if($internal_load>=$GLOBALS["MultipleAdvLoad"]){return true;}
	}

	return false;
}

function QueueDirectoryIsMounted(){
	$f=explode("\n",@file_get_contents("/proc/mounts"));
	if($GLOBALS["VERBOSE"]){echo count($f)." items in /proc/mounts\n";}
	foreach ( $f as $index=>$line ){if(preg_match("#^tmpfs.+?artica-advmem tmpfs#", $line)){return true;}
	}return false;
	
}
function QueueDirectoryRemount(){
	$unix=new unix();
	if(QueueDirectoryIsMounted()){
		$umount=$unix->find_program("umount");
		$cmd="$umount /var/spool/artica-advmem";
		for($i=0;$i<50;$i++){shell_exec("$cmd");if(!QueueDirectoryIsMounted()){break;}}
		if(QueueDirectoryIsMounted()){echo "Failed to umount `$cmd`\n";return ;}
		
	}
	QueueDirectoryConfig();
}


function Scan_Pools(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$CacheFile="/etc/artica-postfix/smtp-senderadv.router.cache";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$nohup=$unix->find_program("nohup");
		$php5=$unix->LOCATE_PHP5_BIN();
		$cmd=$nohup ." ".$php5." ".__FILE__." >/dev/null 2>&1 &";
		smtp::events("Mode router $pid already exists, stop function > $cmd...",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
		$unix->THREAD_COMMAND_SET($php5." ".__FILE__);
	}
	
	@file_put_contents($pidfile, getmypid());
	$TimeConfig=$unix->file_time_min($CacheFile);
	if($TimeConfig>6){@unlink($CacheFile);}

	if(!is_file($CacheFile)){
		$q=new mysql();
		if($q->COUNT_ROWS("postfix_smtp_advrt", "artica_backup")==0){smtp::events("Mode router No instance left....",__FUNCTION__,__FILE__,__LINE__);return;}
		$sql="SELECT * FROM postfix_smtp_advrt WHERE enabled=1";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		$countInstances=mysqli_num_rows($results);
		smtp::events("Mode router MySQL $countInstances rows to scan...",__FUNCTION__,__FILE__,__LINE__);
		if(!$q->ok){smtp::events("Mysql error !!! $q->mysql_error ",__FUNCTION__,__FILE__,__LINE__);return;}
		smtp::events("Mode router MySQL $countInstances domains to to scan...",__FUNCTION__,__FILE__,__LINE__);
		$t=time();
		while ($ligne = mysqli_fetch_assoc($results)) {$f[]=$ligne;ScanSpool($ligne);}
		$took=$unix->distanceOfTimeInWords($t,time());
		smtp::events("Mode router, scanning MySQL $countInstances domains done $took",__FUNCTION__,__FILE__,__LINE__);
		@file_put_contents($CacheFile, serialize($f));
		
	}else{
		$results=unserialize(@file_get_contents($CacheFile));
		$countInstances=count($results);
		smtp::events("Mode router CACHED $countInstances rows to scan...",__FUNCTION__,__FILE__,__LINE__);
		$t=time();
		while(list( $index, $ligne ) = each ($results)){ScanSpool($ligne);}
		$took=$unix->distanceOfTimeInWords($t,time());
		smtp::events("Mode router CACHED, scanning $countInstances domains done $took",__FUNCTION__,__FILE__,__LINE__);
	}
}
	
	
	
function ScanSpool($SQLIne){
		$unix=new unix();
		$GLOBALS["CLASS_UNIX"]=$unix;
		$php5=$unix->LOCATE_PHP5_BIN();
		$domainname=$SQLIne["domainname"];
		$instance=$SQLIne["hostname"];
		$nohup=$unix->find_program("nohup");
		$params=unserialize(base64_decode($SQLIne["params"]));
		$directory="{$GLOBALS["QUEUE_DIRECTORY"]}/$instance/$domainname";
		//smtp::events("Scanning `$directory` ",__FUNCTION__,__FILE__,__LINE__);	
		if(!is_dir($directory)){return;}
		if($GLOBALS["VERBOSE"]){smtp::events("Scanning `$directory` ",__FUNCTION__,__FILE__,__LINE__);}
		
		if (!$handle = opendir($directory)) {smtp::events("glob failed $directory in Line: ",__FUNCTION__,__FILE__,__LINE__);return;}	
		
	
		if(!isset($params["max_msg_per_connection"])){ScanSpoolEvents("{warning} max_msg_per_connection is not set..", __LINE__);$params["max_msg_per_connection"]=5;}
		if(!isset($params["max_msg_rate"])){$params["max_msg_rate"]=600;}
		if(!isset($params["max_smtp_out"])){$params["max_smtp_out"]=5;}
		if(!is_numeric($params["max_msg_per_connection"])){$params["max_msg_per_connection"]=5;}
		if(!is_numeric($params["max_msg_rate"])){$params["max_msg_rate"]=600;}
		if(!is_numeric($params["max_msg_rate_timeout"])){$params["max_msg_rate_timeout"]=300;}
		
		
		$max_msg_per_connection=$params["max_msg_per_connection"];
		$max_msg_rate=$params["max_msg_rate"];
		$max_smtp_out=$params["max_smtp_out"];
		
		//smtp::events("Scanning `$directory` Max messages per instance: $max_msg_per_connection,max_msg_rate: $max_msg_rate,Max instances:$max_smtp_out",__FUNCTION__,__FILE__,__LINE__);
		if(!aiguilleur_max_msg_rate($directory,$params)){return;}
		
		
		
		$c=0;
		$dMESS=0;
		$smtpInstances=array();
		$DetectedInstances=array();
		ScanSpoolEvents("Starting loop in $directory",__LINE__);
		$CountDeScannedMessageFiles=0;
		$INSTANCESEXEC=array();
		while (false !== ($filename = readdir($handle))) {
			$CountDeScannedMessageFiles++;
			$fullpath="$directory/$filename";
			if(is_dir($fullpath)){
				$DirName=basename($fullpath);
				if($GLOBALS["DEBUG"]){smtp::events("$fullpath is a directory `$DirName` -> next",__FUNCTION__,__FILE__,__LINE__);}
				if(is_numeric($DirName)){$DetectedInstances[]=$DirName;}
				continue;
			}
			$ext=$unix->file_ext($fullpath);
			if($ext<>"routing"){if($GLOBALS["DEBUG"]){smtp::events("$fullpath <> routing -> next",__FUNCTION__,__FILE__,__LINE__);}continue;}
			if($c>=$max_smtp_out){$c=0;}
			$dMESS++;
			if(count($smtpInstances[$c])>=$max_msg_per_connection){
				//smtp::events("Instance $c =". count($smtpInstances[$c])." >= $max_msg_per_connection -> Trying the next instance " .$c+1,__FUNCTION__,__FILE__,__LINE__);
				$c++;continue;
			}
			
			
			$InstanceQueueMessagesNumber=InstanceQueueMessagesNumber("$directory/$c");
			if($InstanceQueueMessagesNumber>=$max_msg_per_connection){
				ScanSpoolExecuteProcess($domainname,$instance,$c);
				$c++;continue;
			}
			
			$smtpInstances[$c][]=$fullpath;
			if($GLOBALS["DEBUG"]){echo "Instance $c, add $fullpath ". count($smtpInstances[$c]) ." items/$max_msg_per_connection\n";}
			$c++;
			if($c>=$max_smtp_out){$c=0;}
		}
		
		
		$directoriesOfInstances=$GLOBALS["CLASS_UNIX"]->dirdir($directory);
		if(is_array($directoriesOfInstances)){
			while(list( $a, $b ) = each ($directoriesOfInstances)){
				$InstanceNum=basename($a);
				if(!is_numeric($InstanceNum)){ScanSpoolEvents("`$InstanceNum` is not a numeric and a part of $a",__LINE__);continue;}
				ScanSpoolExecuteProcess($domainname,$instance,$InstanceNum);
			}
		}

		ScanSpoolEvents("Stopping loop in $directory ($CountDeScannedMessageFiles messages) " .count($smtpInstances)." SMTP instances /". count($directoriesOfInstances)." already created instances",__LINE__);
		
		while(list( $InstanceNum, $messages ) = each ($smtpInstances)){
			
			if(!is_dir("$directory/$InstanceNum")){
				if($GLOBALS["VERBOSE"]){echo "Writting new mails in $directory/$InstanceNum\n";} 
				@mkdir("$directory/$InstanceNum");
				$cmes=0;
				while(list( $messageIndex, $messagePath ) = each ($messages)){
					$cmes++;
					@copy($messagePath, "$directory/$InstanceNum/".basename($messagePath));
					@unlink($messagePath);
				}
				
				ScanSpoolEvents("[$domainname]: Put $cmes messages in Instance Number $InstanceNum (Max Message per connection=$max_msg_per_connection)",__LINE__);
				if(!$GLOBALS["VERBOSE"]){ScanSpoolExecuteProcess($domainname,$instance,$InstanceNum);}
				
			}else{
				if(!$GLOBALS["VERBOSE"]){ScanSpoolExecuteProcess($domainname,$instance,$InstanceNum);}
				
			}
		}
		
		if($dMESS==0){
			if(count($DetectedInstances)>0){
				if($GLOBALS["VERBOSE"]){echo count($DetectedInstances) ." Detected instances\n";} 
				while(list( $index, $InstanceNum ) = each ($DetectedInstances)){
					$cmd="$nohup $php5 ".__FILE__." --instance-send $domainname $instance $InstanceNum >/dev/null 2>&1 &";
					if(!$GLOBALS["VERBOSE"]){shell_exec($cmd);}
				}
	
				return;
			}
		}
		
		
		
		//smtp::events("[$instance/$domainname]: $dMESS messages file...",__FUNCTION__,__FILE__,__LINE__);
		if($dMESS==0){
			if(is_file("$directory/TIMESTAMP")){@unlink("$directory/TIMESTAMP");}
			if(is_file("$directory/max_msg_rate")){@unlink("$directory/max_msg_rate");}
			
		
		}
		@rmdir($directory);
		
		
}

function ScanSpoolExecuteProcess($domainname,$instance,$processNum){
	if(isset($GLOBALS["INSTANCE_EXEC"][$domainname][$instance][$processNum])){return;}
		$php5=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
		$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
		$cmd="$nohup $php5 ".__FILE__." --instance-send $domainname $instance $processNum >/dev/null 2>&1 &";
		ScanSpoolEvents($cmd,__LINE__);
		$GLOBALS["INSTANCE_EXEC"][$domainname][$instance][$processNum]=true;
		shell_exec($cmd);
		ScanSpoolEvents("$cmd DONE",__LINE__);
}

function ScanSpoolEvents($text,$line){
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	$logFile="/var/log/artica-router.log";
	if(!is_dir(dirname($logFile))){return;}
	if (is_file($logFile)) {$size=filesize($logFile);if($size>9000000){unlink($logFile);}}
	$date=date('m-d H:i:s');
	$f = @fopen($logFile, 'a');
	$file=basename($file);
	$final="$date $file:[{$GLOBALS["MYPID"]}]:[$line] $text\n";
	@fwrite($f,$final );
	@fclose($f);		
	
}

function aiguilleur_max_msg_rate($directory,$params){
	if($params["max_msg_rate"]==0){return true;}
	if(is_numeric($params["max_msg_rate"])){$params["max_msg_rate"]=600;}
	if(!is_numeric($params["max_msg_rate_timeout"])){$params["max_msg_rate_timeout"]=300;}	
	if(!is_file("$directory/max_msg_rate")){@file_put_contents("$directory/max_msg_rate",time());return true;}	
	
	$time=$GLOBALS["CLASS_UNIX"]->file_time_sec("$directory/max_msg_rate");
	if($GLOBALS["DEBUG"]){smtp::events("max_msg_rate: time = $time",__FUNCTION__,__FILE__,__LINE__);}
	if($time<$params["max_msg_rate"]){return true;}
	
	if($time<$params["max_msg_rate_timeout"]){
		smtp::events("Scanning max_msg_rate banned creating sub-queues  {$time}s/{$params["max_msg_rate_timeout"]}s",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	
	@unlink("$directory/max_msg_rate");
	@file_put_contents("$directory/max_msg_rate",time());
	return true;
	
	
}

function ConnectionRateHour($mx,$instance,$domainname,$max,$config){
	
	if(!isset($max)){smtp::events("[$mx/$domainname] MAX value is empty, assuming 500 !!",__FUNCTION__,__FILE__,__LINE__);$max=500;}
	if($max==0){return true;}
	if($max==1){smtp::events("[$mx/$domainname] MAX value is too less 1/hour, assuming 500 !!",__FUNCTION__,__FILE__,__LINE__);$max=500;}
	
	$timefile="{$GLOBALS["QUEUE_DIRECTORY"]}/$instance/$domainname/TIMESTAMP";
	
	$datas=unserialize(@file_get_contents($timefile));
	if(!is_array($datas)){$datas[$mx]["STARTTIME"]=time();}
	if(!isset($datas[$mx]["STARTTIME"])){$datas[$mx]["STARTTIME"]=time();$datas[$mx]["COUNT"]=0;}
	
	$unix=new unix();
	$difference = (time() - $datas[$mx]["STARTTIME"]); 	 
	if($difference>3600){$datas[$mx]["COUNT"]=0;$datas[$mx]["STARTTIME"]=time();} 
	
	$count=$datas[$mx]["COUNT"];
	if(!is_numeric($count)){$count=0;}
	$count=$count+1;
	if($count>$max){
		smtp::events("[$mx/$domainname] $mytime -> $count/$max per hour ($difference seconds/3600) sleeping 10s",__FUNCTION__,__FILE__,__LINE__);
		sleep(10);
		return false;
	}
	$datas[$mx]["COUNT"]=$count;
	smtp::events("[$mx/$domainname][$instance] $difference seconds/3600 -> $count/$max per hour {$config["CURRENT_ARRAY_QUEUE"]} messages in queue",__FUNCTION__,__FILE__,__LINE__);
	for($i=0;$i<50;$i++){
		if(@file_put_contents($timefile, serialize($datas),LOCK_EX)){break;}
		usleep(2000);
	}
	return true;	
}

function InstanceQueueMessagesNumber($directory){
	if(!is_dir($directory)){return 0;}
	if (!$handle = opendir($directory)) {return 0;}
	$c=0;
	while (false !== ($filename = readdir($handle))) {if($filename==".."){continue;}if($filename=="."){continue;}$c++;}
	return $c;
	
}


function InstanceSend($domainname,$instance,$instanceid){
	
	if($GLOBALS["VERBOSE"]){echo "InstanceSend($domainname,$instance,$instanceid)\n";}
	
	if(is_file("/etc/artica-postfix/stop-smtp-instances")){
		if($GLOBALS["VERBOSE"]){echo "/etc/artica-postfix/stop-smtp-instances exists, aborting....\n";}
		return null;
	}
	
	$MY_CMDLINE=__FILE__." --instance-send $domainname $instance $instanceid";
	$RESTART_ME_NO_QUEUE=false;
	if($GLOBALS["VERBOSE"]){echo "RUNNING IN DEBUG MODE\n";}
	$unix=new unix();
	$GLOBALS["CLASS_UNIX"]=$unix;
	$GLOBALS["LOGPERDOMAIN"]=$domainname;
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");	
	$directory="{$GLOBALS["QUEUE_DIRECTORY"]}/$instance/$domainname/$instanceid";
	
	$pidfile="/etc/artica-postfix/pids/".md5($directory).".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	
	
	if($unix->process_exists($pid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "$pid PID already exists... Aborting...\n";}
		exit();
	}
	
	@file_put_contents($pidfile, getmypid(),LOCK_EX);
	
	
	if(!is_dir($directory)){if($GLOBALS["VERBOSE"]){echo "$directory no such directory\n";}return; }
		
	if (!$handle = opendir($directory)) {smtp::events("glob failed $directory in Line: ",__FUNCTION__,__FILE__,__LINE__);	return;}
	
	
	$config=PopulateConfig($instance,$domainname);
	$config["CurrentDomain"]=$domainname;
	$config["CurrentInstance"]=$instance;
	$config["MAIL_WORKING_DIRECTORY"]=$directory;
	$config["PHP5_BIN"]=$php5;
	$config["NOHUP_BIN"]=$nohup;
	if($config["debug_parameters"]==1){smtp::events("[$domainname/$instanceid] Scanning `$directory` ",__FUNCTION__,__FILE__,__LINE__);}
	
	
	if(!is_array($config)){smtp::events("[$domainname/$instanceid] FATAL settings are not correcly applied go back to default values",__FUNCTION__,__FILE__,__LINE__);}
	if($config["debug_parameters"]==1){
		smtp::events("[$domainname/$instanceid] $domainname connected, start loop",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("[$domainname/$instanceid] Config: change_timecode     = {$config["change_timecode"]}",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("[$domainname/$instanceid] Config: wait_xs_per_message = {$config["wait_xs_per_message"]}",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("[$domainname/$instanceid] Config: max_cnt_hour        = {$config["max_cnt_hour"]}",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("[$domainname/$instanceid] Config: bind_address        = {$config["bind_address"]}",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("[$domainname/$instanceid] Config: CNX_421             = {$config["CNX_421"]}",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("[$domainname/$instanceid] Config: CurrentDomain       = {$config["CurrentDomain"]}",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("[$domainname/$instanceid] Config: msgs_ttl            = {$config["msgs_ttl"]}",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("[$domainname/$instanceid] Config: instance_ipaddr     = {$config["instance_ipaddr"]}",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("[$domainname/$instanceid] Config: instance_name       = {$config["instance_name"]}",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("[$domainname/$instanceid] Config: smtp_authenticate   = {$config["smtp_authenticate"]} ({$config["AUTH-USER"]})",__FUNCTION__,__FILE__,__LINE__);
	}
	
	$GLOBALQUEUE=LoadMessagesInQueue($directory,$config,$handle);
	if($config["min_subqueue_msgs"]>0){
		if($config["debug_parameters"]==1){smtp::events("[$domainname/$instanceid] QUEUE =  ".count($GLOBALQUEUE["FULL"])." in full queue, ".count($GLOBALQUEUE["FORCED"])." forced queue",__FUNCTION__,__FILE__,__LINE__);}
		if(count($GLOBALQUEUE["FULL"]==1)){$RESTART_ME_NO_QUEUE=true;}
		if(count($GLOBALQUEUE["FULL"])>=$config["min_subqueue_msgs"]){$ARRAY_QUEUE=$GLOBALQUEUE["FULL"];}else{$ARRAY_QUEUE=$GLOBALQUEUE["FORCED"];}
	}else{
		$ARRAY_QUEUE=$GLOBALQUEUE["FULL"];
	}
	
	if(!is_array($ARRAY_QUEUE)){
		if($RESTART_ME_NO_QUEUE){
			if($config["debug_parameters"]==1){smtp::events("$nohup $php5 $MY_CMDLINE --waitForATime={$GLOBALS["WAITFORATIME_SEQUENCE"]} >/dev/null 2>&1 &",__FUNCTION__,__FILE__,__LINE__);}
			shell_exec("$nohup $php5 $MY_CMDLINE --waitForATime={$GLOBALS["WAITFORATIME_SEQUENCE"]} >/dev/null 2>&1 &");
		}
		return;
	}
	
	if($GLOBALS["VERBOSE"]){echo "\$ARRAY_QUEUE = ". count($ARRAY_QUEUE) ." elements\n";}
	
	if(count($ARRAY_QUEUE)==0){
		if($RESTART_ME_NO_QUEUE){
			shell_exec("$nohup $php5 $MY_CMDLINE --waitForATime >/dev/null 2>&1 &");
			smtp::events("$nohup $php5 $MY_CMDLINE --waitForATime >/dev/null 2>&1 &",__FUNCTION__,__FILE__,__LINE__);
		}
		return;
	}
	
	$smtp=new smtp();
	$NOresolvMX=false;
	if($config["smtp_authenticate"]==1){
		if($config["AUTH-SMTP"]<>null){
			$TargetHostname=$config["AUTH-SMTP"];
			$NOresolvMX=true;
		}
	}
	
	if(!$NOresolvMX){
		if(trim($config["ForceMX"])<>null){$TargetHostname=$config["ForceMX"];}else{$TargetHostname=$smtp->ResolveMXDomain($domainname);}
	}
	$config["CURRENT_ARRAY_QUEUE"]=count($ARRAY_QUEUE);
	$config["TargetHostname"]=$TargetHostname;
	$config["TargetHostname-SOURCE"]=$TargetHostname;
	if($config["bind_address"]==null){$config["bind_address"]=$config["instance_ipaddr"];}
	
	if($config["debug_parameters"]==1){smtp::events("[$domainname/$instanceid] $domainname -> MX =  `$TargetHostname` Max connexion per hour=\"{$config["max_cnt_hour"]}\" Bin IP addr: `{$config["bind_address"]}`",__FUNCTION__,__FILE__,__LINE__);}	
	if($TargetHostname==null){smtp::events("[$domainname/$instanceid] $domainname -> Could not found MX for domain `$domainname`",__FUNCTION__,__FILE__,__LINE__);CHECK_DEAD_MAILS($directory,$config);return;}
	if(ERROR_CHECK_CNX_421($TargetHostname,$config)){CHECK_DEAD_MAILS($directory,$config);return;}
	if(!ConnectionRateHour($TargetHostname,$instance,$domainname,$config["max_cnt_hour"],$config)){
		smtp::events("[$domainname/$instanceid] Connection rate exceed...",__FUNCTION__,__FILE__,__LINE__);
		CHECK_DEAD_MAILS($directory,$config);
		return;
	}
	
	$params["host"]=$TargetHostname;
	$params["helo"]=$instance;
	if($config["MailHeloRandomize"]==1){
		if(is_array($config["MAILHELO_RANDOMIZE"])){
			if(count($config["MAILHELO_RANDOMIZE"])>0){
				$heloindex=rand(0, count($config["MAILHELO_RANDOMIZE"])-1);
				$params["helo"]=$config["MAILHELO_RANDOMIZE"][$heloindex];
				if(trim($params["helo"])==null){$params["helo"]=$instance;}
			}
		}
	}
	$params["helo"]=$instance;
	$params["bindto"]=$config["bind_address"];
	if($config["debug_parameters"]==1){$params["debug"]=true;}
	
	if($config["smtp_authenticate"]==1){
		$params["auth"]=true;
		$params["user"]=$config["AUTH-USER"];
		$params["pass"]=$config["AUTH-PASS"];
		if($config["AUTH-SMTP"]<>null){
			$params["host"]=$config["AUTH-SMTP"];
		}
	}	
	
	
	
	
	if(!$smtp->connect($params)){
		smtp::events("[$domainname/$instanceid] $domainname -> Could not connect to  `$TargetHostname`",__FUNCTION__,__FILE__,__LINE__);
		CheckConnectionError($smtp->error_number, $smtp->error_text, $TargetHostname,$config);
		CHECK_DEAD_MAILS($directory,$config);
		return;
	}
	
	
	$c=0;
	if($config["debug_parameters"]==1){smtp::events("[$domainname/$instanceid] $domainname -> Connected, sending " .count($ARRAY_QUEUE)." Messages Local ip:{$params["bindto"]}",__FUNCTION__,__FILE__,__LINE__);}
	$CheckLoadNum=round(count($ARRAY_QUEUE)/2);
	$SendCount=0;
	while (list ($filename, $seconds) = each ($ARRAY_QUEUE) ){
		if($filename==".."){continue;}
		if($filename=="."){continue;}
		$recipient=null;
		$fullpath="$directory/$filename";
		$SendCount++;
		$datas=unserialize(@file_get_contents($fullpath));
		if($CheckLoadNum>0){if($SendCount>$CheckLoadNum){if(CheckLoad()){sleep(5);};$SendCount=0;}}
		
		if(!is_array($datas)){smtp::events("$fullpath ($seconds seconds TTL)  no such array",__FUNCTION__,__FILE__,__LINE__);@unlink($fullpath);continue;}
		$sourcefile=$datas["mail_data"];
		if(isset($datas["TIMESAVED"])){$datas["TIMESAVED"]=time();XWriteTofile($fullpath, serialize($datas));}
		
		if(!is_file($sourcefile)){
			smtp::events("Message data file `$sourcefile` ($seconds seconds TTL) no such file, remove $fullpath",__FUNCTION__,__FILE__,__LINE__);
			@unlink($fullpath);
			continue;
		}
		$c++;
		if($GLOBALS["VERBOSE"]){echo "Open $sourcefile\n";} 
		$GLOBALS["MAIL_NEW_MAIL_FROM"]=null;
		
		$MAILDATA=@file_get_contents($sourcefile);
		if($config["change_timecode"]==1){$MAILDATA=ChangeTimeCode($MAILDATA,$config);}
		$MAILDATA=KillBadHeader($MAILDATA,$config);
		if(isset($datas["original_recipient"])){$recipient=$datas["original_recipient"];}
		if($recipient==null){if(isset($datas["recipients"])){$recipient=$datas["recipients"];}}
		if($GLOBALS["MAIL_NEW_MAIL_FROM"]<>null){$datas["sender"]=$GLOBALS["MAIL_NEW_MAIL_FROM"];}
		
		if(!$smtp->send(array("from"=>$datas["sender"],"recipients"=>$recipient,"body"=>$MAILDATA))){
			smtp::events("[$domainname/$instanceid] [MessageNum:$c] ($seconds seconds TTL) Failed from=<{$datas["sender"]}> to=<$recipient>",__FUNCTION__,__FILE__,__LINE__);
			if(Checkerror($smtp->error_number,$smtp->error_text, $fullpath,$config)){continue;}
			smtp::events("[". basename($fullpath)."] ($seconds seconds TTL) Failed",__FUNCTION__,__FILE__,__LINE__);
			$CNX_421_FILE=$GLOBALS["QUEUE_DIRECTORY_ERROR"]."/$domainname/$instance/$TargetHostname/CNX_421";
			if(is_file($CNX_421_FILE)){break;}
			increment_error_in_msg($fullpath,$config);
			continue;
		}
		$clog=$c;
		if(strlen($clog)==1){$clog="0$clog";}
		if(strlen($clog)==2){$clog="0$clog";}
		if(strlen($clog)==3){$clog="0$clog";}
				
		
		if($config["debug_parameters"]==1){smtp::events("[$domainname/$instanceid] [MessageNum:$clog] From[myip={$params["bindto"]} - $SendCount/$CheckLoadNum -] Success[$smtp->error_number] from=<{$datas["sender"]}> to=<$recipient>",__FUNCTION__,__FILE__,__LINE__);}
		@unlink($sourcefile);
		@unlink($fullpath);
		if($config["wait_xs_per_message"]>0){sleep($config["wait_xs_per_message"]);}
	}
	
	if($config["debug_parameters"]==1){smtp::events("Closing connection...",__FUNCTION__,__FILE__,__LINE__);}
	
	$smtp->quit();
	@rmdir($directory);
	
	$cmd="$nohup $php5 ".__FILE__." >/dev/null 2>&1 &";
	if($config["debug_parameters"]==1){smtp::events("$cmd");}
	
	if(CheckLoad()){
		$ProcessesRunned=checkMaxProcessNumber();
		if($ProcessesRunned>0){
			smtp::events("[$domainname/$instanceid][$instance] Server loaded,`{$GLOBALS["DEBUG_LOG_LOAD"]}` and max processes is executed die()",__FUNCTION__,__FILE__,__LINE__);
			exit();
		}
		smtp::events("[$domainname/$instanceid][$instance] Server loaded,{$GLOBALS["DEBUG_LOG_LOAD"]} sleep 5 seconds and schedule \"$cmd\"",__FUNCTION__,__FILE__,__LINE__);
		sleep(5);
		$unix=new unix();
		$unix->THREAD_COMMAND_SET("$nohup $php5 ".__FILE__);
		exit();
	}else{
		$ProcessesRunned=checkMaxProcessNumber();
		smtp::events("[$domainname/$instanceid][$instance] {$GLOBALS["CURRENT_INSTANCES_LOADED"]}",__FUNCTION__,__FILE__,__LINE__);
		if($ProcessesRunned==0){
			smtp::events("[$domainname/$instanceid][$instance] Executing the router....",__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmd);
		}
	}
	
}

function LoadMessagesInQueue($directory,$config,$handle){
	
	$unix=new unix();
	$c=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename==".."){continue;}
		if($filename=="."){continue;}
		$recipient=null;
		$c++;
		$timesec=$unix->file_time_sec("$directory/$filename");
		
		$msgs["FULL"][$filename]=$timesec;
		
		
		
		if($timesec>$config["min_subqueue_msgs_ttl"]){
			$msgs["FORCED"][$filename]=$timesec;
		}else{
			if($config["debug_parameters"]==1){smtp::events("$timesec seconds, need {$config["min_subqueue_msgs_ttl"]} seconds to force",__FUNCTION__,__FILE__,__LINE__);}
		}
	}
	
	if($c==0){@rmdir($directory);return 0;}
	if($config["debug_parameters"]==1){smtp::events("$directory $c messages in queue",__FUNCTION__,__FILE__,__LINE__);}
	return $msgs;
}

function increment_error_in_msg($fullpath,$config){
	$datas=unserialize(@file_get_contents($fullpath));
	if(!isset($datas["msg_error"])){$datas["msg_error"]=1;}
	$datas["msg_error"]=$datas["msg_error"]+1;
	smtp::events("[". basename($fullpath)."] Error number {$datas["msg_error"]} MAX = {$config["max_err_per_message"]}",__FUNCTION__,__FILE__,__LINE__);
	if($datas["msg_error"]>$config["max_err_per_message"]){BOUNCE($fullpath,$config);return true;}
	XWriteTofile($fullpath, serialize($datas));
	return false;
	
}


function CheckConnectionError($error_number,$error_text,$TargetHostname,$config){
	smtp::events("Error: [$error_number] TargetHostname=$TargetHostname ($error_text)",__FUNCTION__,__FILE__,__LINE__);
	$mainDir=$GLOBALS["QUEUE_DIRECTORY_ERROR"]."/{$config["CurrentDomain"]}/{$config["CurrentInstance"]}/$TargetHostname";
	if(!is_dir($mainDir)){
		smtp::events("Error: Creating \"$mainDir\" Directory",__FUNCTION__,__FILE__,__LINE__);
		@mkdir($mainDir,0755,true);
		if(!is_dir($mainDir)){smtp::events("Fatal ERROR: Failed to create \"$mainDir\" Directory",__FUNCTION__,__FILE__,__LINE__);}
	}	
	
	if($error_number==450){$error_number=421;}
	if($error_number==420){$error_number=421;}
	
	if($error_number==421){
		if(!is_file("$mainDir/CNX_421")){
			smtp::events("Error: [$error_number] CNX_421 = {$config["CNX_421"]}Mn",__FUNCTION__,__FILE__,__LINE__);
			smtp::events("Error: [$error_number] STAMP $mainDir/CNX_421",__FUNCTION__,__FILE__,__LINE__);		
			@file_put_contents("$mainDir/CNX_421", time());	
		}
		if($config["CNX_421_MOVE"]==1){if(trim($config["CNX_421_HOST"])<>null){ERROR_CHECK_CNX_421_MOVE($TargetHostname,$config);}else{smtp::events("[{$config["CurrentDomain"]}/{$config["instance_name"]}/$TargetHostname] WARNING !!! CNX_421_HOST token is null !!",__FUNCTION__,__FILE__,__LINE__);}}		
		return;	
	}
	
	if($error_number==554){
		smtp::events("Error: [$error_number] ($error_text) Bounce all messages in {$config["MAIL_WORKING_DIRECTORY"]} queue",__FUNCTION__,__FILE__,__LINE__);
		DELETE_ALL_MESSAGES($error_number,$error_text,$TargetHostname,$config);
		return;	
	}	
	
	smtp::events("Error: [$error_number] Not filtered",__FUNCTION__,__FILE__,__LINE__);
	
}


function Checkerror($errornumber,$error_text,$transportfile,$config){
	
	if(preg_match("#MailBox quota excedeed#i", $error_text)){$errornumber=550;}
	
	if($errornumber==220){return false;}
	if($errornumber==250){return false;}
	if($errornumber==420){$errornumber=421;}
	if($errornumber=="4.2.0"){$errornumber=421;}
	if($errornumber==450){$errornumber=421;}
	if($errornumber==450){$errornumber=421;}
	
	$TargetHostname=$config["TargetHostname"];
	$mainDir=$GLOBALS["QUEUE_DIRECTORY_ERROR"]."/{$config["CurrentDomain"]}/{$config["CurrentInstance"]}/$TargetHostname";
	
	if($errornumber==421){
		if(!is_file("$mainDir/CNX_421")){
			smtp::events("Error: [$errornumber] CNX_421 = {$config["CNX_421"]}Mn",__FUNCTION__,__FILE__,__LINE__);
			smtp::events("Error: [$errornumber] STAMP $mainDir/CNX_421",__FUNCTION__,__FILE__,__LINE__);		
			@file_put_contents("$mainDir/CNX_421", time());	
		}
		if($config["CNX_421_MOVE"]==1){if(trim($config["CNX_421_HOST"])<>null){ERROR_CHECK_CNX_421_MOVE($TargetHostname,$config);}else{smtp::events("[{$config["CurrentDomain"]}/{$config["instance_name"]}/$TargetHostname] WARNING !!! CNX_421_HOST token is null !!",__FUNCTION__,__FILE__,__LINE__);}}		
		return true;	
	}	
	
	
	$todel["5.1.1"]=true;
	$todel["5.7.1"]=true;
	$todel["554"]=true;
	$todel["550"]=true;
	$todel["552"]=true;
	$todel["421"]=true;
	$datas=unserialize(@file_get_contents($transportfile));
	$prefix="from=<{$datas["sender"]}> to=<{$datas["recipients"]}> ";
	$datas["TargetHostname-SOURCE"]=$config["TargetHostname-SOURCE"];
	$datas["TargetHostname"]=$config["TargetHostname"];
	$datas["BOUNCE_ERROR"]="$error_text";
	$datas["BOUNCE_ERROR_NUM"]=$errornumber;
	$config["BOUNCE_ERROR"]="$error_text";
	$config["BOUNCE_ERROR_NUM"]=$errornumber;		
	if(!XWriteTofile($transportfile, serialize($datas))){smtp::events("$prefix STAMP ".basename($transportfile)." FAILED !!!!",__FUNCTION__,__FILE__,__LINE__);}
	

	if($todel[$errornumber]){
		smtp::events("$prefix [$errornumber] = `$error_text`",__FUNCTION__,__FILE__,__LINE__);
		BOUNCE($transportfile,$config);
		return true;
	}
	
	smtp::events("$prefix [$errornumber] not filtered or mail is OK",__FUNCTION__,__FILE__,__LINE__);
	return false;
	
	
	
}

function XWriteTofile($filepath,$datas){
	
	if (!$f = @fopen($filepath, 'w')) {
		smtp::events("$filepath  open error",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	if (@fwrite($f, $datas) === FALSE) {
		smtp::events("$filepath  write error",__FUNCTION__,__FILE__,__LINE__);
		@fclose($f);
		return false;	
	}
	
	@fclose($f);
	return true;
	
}

function DELETE_ALL_MESSAGES($errornumber,$error_text,$TargetHostname,$config){
	$mainDir=$config["MAIL_WORKING_DIRECTORY"];
	if(!is_dir($mainDir)){smtp::events("$mainDir no such directory",__FUNCTION__,__FILE__,__LINE__);return;}
	smtp::events("Parsing $mainDir",__FUNCTION__,__FILE__,__LINE__);
	if (!$handle = opendir($mainDir)) {smtp::events("glob failed $mainDir",__FUNCTION__,__FILE__,__LINE__);	return;}
	while (false !== ($filename = readdir($handle))) {
		if($filename==".."){continue;}
		if($filename=="."){continue;}
		$recipient=null;
		$fullpath="$mainDir/$filename";
		$datas=unserialize(@file_get_contents($fullpath));
		$datas["BOUNCE_ERROR"]=$error_text;
		$datas["BOUNCE_ERROR_NUM"]=$errornumber;	
		$config["BOUNCE_ERROR"]=$error_text;
		$config["BOUNCE_ERROR_NUM"]=$errornumber;	
		XWriteTofile($fullpath, serialize($datas));
		smtp::events("Bounce: ". basename($fullpath),__FUNCTION__,__FILE__,__LINE__);	
		BOUNCE($fullpath, $config);
	}
	
}



function ERROR_CHECK_CNX_421($TargetHostname,$config){
	if(!isset($config)){smtp::events("{warning} config is not populated !",__FUNCTION__,__FILE__,__LINE__);}
	if(!isset($config["CurrentDomain"])){smtp::events("{warning} config is not populated !",__FUNCTION__,__FILE__,__LINE__);}
	if(!isset($config["CurrentInstance"])){smtp::events("{warning} config is not populated !",__FUNCTION__,__FILE__,__LINE__);}
	$mainDir=$GLOBALS["QUEUE_DIRECTORY_ERROR"]."/{$config["CurrentDomain"]}/{$config["CurrentInstance"]}/$TargetHostname";
	
	
	
	if(!is_file("$mainDir/CNX_421")){return false;}
	$timeFile=$GLOBALS["CLASS_UNIX"]->file_time_min("$mainDir/CNX_421");
	
	if($timeFile>$config["CNX_421"]){@unlink("$mainDir/CNX_421");return false;}
	smtp::events("[{$config["CurrentDomain"]}/{$config["instance_name"]}/$TargetHostname] \"$mainDir/CNX_421\" -> 421 connection wait {$timeFile}mn/{$config["CNX_421"]}mn",__FUNCTION__,__FILE__,__LINE__);
	
	
	if($config["CNX_421_MOVE"]==1){
		if(trim($config["CNX_421_HOST"])<>null){
				ERROR_CHECK_CNX_421_MOVE($TargetHostname,$config);
		}else{
			smtp::events("[{$config["CurrentDomain"]}/{$config["instance_name"]}/$TargetHostname] WARNING !!! CNX_421_HOST token is null !!",__FUNCTION__,__FILE__,__LINE__);
		}
	}
	
	
	return true;
}

function ERROR_CHECK_CNX_421_MOVE($TargetHostname,$config){
		$directory=$config["MAIL_WORKING_DIRECTORY"];
		if(!is_dir($directory)){smtp::events("[{$config["CurrentDomain"]}/{$config["instance_name"]}/$TargetHostname]: $directory no such directory",__FUNCTION__,__FILE__,__LINE__);return;}
		smtp::events("[{$config["CurrentDomain"]}/{$config["instance_name"]}/$TargetHostname]: Move all messages from $directory",__FUNCTION__,__FILE__,__LINE__);
		if (!$handle = opendir($directory)) {smtp::events("glob failed $directory in Line: ",__FUNCTION__,__FILE__,__LINE__);	return;}
		
		$Nextdirectory="{$GLOBALS["QUEUE_DIRECTORY"]}/{$config["CNX_421_HOST"]}/{$config["CurrentDomain"]}";
		@mkdir($Nextdirectory,0755,true);
		
		$c=0;
		while (false !== ($filename = readdir($handle))) {
			if($filename==".."){continue;}
			if($filename=="."){continue;}
			$recipient=null;
			$fullpath="$directory/$filename";
			if(!is_file($fullpath)){continue;}
			
			$datas=unserialize(@file_get_contents($fullpath));
			if(!is_array($datas)){continue;}
			
			$sourcefile=$datas["mail_data"];
			if(trim($sourcefile)==null){
				smtp::events("[FAILED]: mail_data token not set in $fullpath",__FUNCTION__,__FILE__,__LINE__);
				continue;}
			
			if(!is_file($sourcefile)){
				smtp::events("[FAILED]: $sourcefile, no such file",__FUNCTION__,__FILE__,__LINE__);
				continue;				
			}
			
			$BaseNameSourceFile=basename($sourcefile);
			if(is_file("$Nextdirectory/$BaseNameSourceFile")){
				smtp::events("[FAILED]:  to copy data message to $Nextdirectory/$BaseNameSourceFile file already exists",__FUNCTION__,__FILE__,__LINE__);
				continue;
			}
			
			if (!copy($sourcefile,"$Nextdirectory/$BaseNameSourceFile")) {
				smtp::events("[FAILED]:  to copy data message to $Nextdirectory/$BaseNameSourceFile",__FUNCTION__,__FILE__,__LINE__);
				continue;
			}
			
			$datas["mail_data"]="$Nextdirectory/$BaseNameSourceFile";
			@file_put_contents("$Nextdirectory/$filename", serialize($datas));
			@unlink($sourcefile);
			@unlink($fullpath);
			$c++;
		}
	
		if($c>0){
			smtp::events("Success move $c messages to {$config["CNX_421_HOST"]}",__FUNCTION__,__FILE__,__LINE__);
			shell_exec("{$config["NOHUP_BIN"]} {$config["PHP5_BIN"]} ".__FILE__." >/dev/null 2>&1 &");	
		}
			
	
}

function CHECK_DEAD_MAILS($directory,$config){
	if(!is_dir($directory)){return;}
	smtp::events("Check dead mails in $directory",__FUNCTION__,__FILE__,__LINE__);
	if (!$handle = opendir($directory)) {smtp::events("glob failed $directory in Line: ",__FUNCTION__,__FILE__,__LINE__);	return;}
	$c=0;
	$D=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename==".."){continue;}
		if($filename=="."){continue;}
		$recipient=null;
		$fullpath="$directory/$filename";
		if(!is_file($fullpath)){continue;}
		$c++;
		
		if($config["msgs_ttl"]==0){smtp::events(basename($fullpath)." DEAD: msgs_ttl = 0",__FUNCTION__,__FILE__,__LINE__);BOUNCE($fullpath,$config);$D++;continue;}
		$datas=unserialize(@file_get_contents($fullpath));
		if(isset($datas["TIMESAVED"])){$TTL=$GLOBALS["CLASS_UNIX"]->calc_time_min($datas["TIMESAVED"]);}
		if(!isset($datas["TIMESAVED"])){$TTL=$GLOBALS["CLASS_UNIX"]->file_time_min($fullpath);}
		
		if($TTL>$config["msgs_ttl"]){
			smtp::events("Message $filename {$TTL}mn seems execeed time {$config["msgs_ttl"]}Mn",__FUNCTION__,__FILE__,__LINE__);
			$datas["BOUNCE_ERROR"]="{$TTL}mn, Exceed Time to live ({$config["msgs_ttl"]} minutes)";
			$datas["BOUNCE_ERROR_NUM"]="421";
			$config["BOUNCE_ERROR"]="{$TTL}mn, Exceed Time to live ({$config["msgs_ttl"]} minutes)";
			$config["BOUNCE_ERROR_NUM"]="421";	
			smtp::events(basename($fullpath)." DEAD: {$config["BOUNCE_ERROR"]}",__FUNCTION__,__FILE__,__LINE__);
			@file_put_contents($fullpath, serialize($datas));	
			$D++;
			BOUNCE($fullpath,$config);
		}
	}
	if($D>0){smtp::events("Check $D dead mails done",__FUNCTION__,__FILE__,__LINE__);}else{smtp::events("No dead mails done still $c messages",__FUNCTION__,__FILE__,__LINE__);}
	if($c==0){smtp::events("Removing sub-queue $directory",__FUNCTION__,__FILE__,__LINE__);@rmdir($directory);}
}
function BOUNCE($fullpath,$config){
	if($config["debug_parameters"]==1){smtp::events("Message: \"$fullpath\"",__FUNCTION__,__FILE__,__LINE__);}
	$datas=unserialize(@file_get_contents($fullpath));	
	
	if($config["debug_parameters"]==1){while (list ($key, $val) = each ($datas)){smtp::events("[$key] = \"$val\"",__FUNCTION__,__FILE__,__LINE__);}}
	
	$sourcefile=$datas["mail_data"];
	$sourcefileBASE=basename($sourcefile);
	if(!is_file($sourcefile)){
		smtp::events("Message data file `$sourcefile` no such file, remove $fullpath",__FUNCTION__,__FILE__,__LINE__);
		if(is_file($fullpath)){@unlink($fullpath);}
		return;
	}
	
	if($config["debug_parameters"]==1){smtp::events("sourcefile.................:$sourcefile",__FUNCTION__,__FILE__,__LINE__);}
	
	
	
	$MAILDATA=@file_get_contents($sourcefile);	
	$instance=$config["CurrentInstance"];
	$instance_ipaddr=$config["instance_ipaddr"];
	if(trim($config["bounce_to"])==null){$config["bounce_to"]=$datas["sender"];}
	if(!isset($datas["timestamp"])){$datas["timestamp"]=filemtime($sourcefile);}
	$ArrivalDate=date("D, d M Y H:i:s",$datas["timestamp"]);
	
	
	if(!isset($datas["BOUNCE_ERROR_NUM"])){$datas["BOUNCE_ERROR_NUM"]=421;}
	if(!preg_match("#^.+?@.+#", $config["bounce_to"])){$config["bounce_to"]=$datas["sender"];}
	
	$messageID=md5($MAILDATA.time().$fullpath);	
	if(!isset($datas["BOUNCE_ERROR"])){$datas["BOUNCE_ERROR"]="Exceed Time to live ({$config["msgs_ttl"]} minutes)";}
	$random_hash = md5(date('r', time()));
	$boundary="$random_hash/$instance";
	if($config["instance_ipaddr"]==$config["bind_address"]){$config["bind_address"]="127.0.0.1";}
	if(!preg_match("#.+?@.+#",$config["bounce_to"])){smtp::events("Failed, recipient of bounce messages <{$config["bounce_to"]}> is corrupted",__FUNCTION__,__FILE__,__LINE__);return;}
	if(preg_match("#^([0-9\.]+)\s+(.+)#", $datas["BOUNCE_ERROR"],$re)){$datas["BOUNCE_ERROR"]=$re[2];$datas["BOUNCE_ERROR_NUM"]=$re[1];}	
	
	if(isset($config["BOUNCE_ERROR"])){$datas["BOUNCE_ERROR"]=$config["BOUNCE_ERROR"];}
	if(isset($config["BOUNCE_ERROR_NUM"])){$datas["BOUNCE_ERROR_NUM"]=$config["BOUNCE_ERROR_NUM"];}
	
	if($config["debug_parameters"]==1){
		smtp::events("sourcefile.................: $sourcefile",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("instance...................: $instance",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("instance_ipaddr............: $instance_ipaddr",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("MAILDATA...................: ". strlen($MAILDATA)." Bytes",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("ArrivalDate................: $ArrivalDate",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("bounce_from................: {$config["bounce_from"]}",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("bounce_to..................: {$config["bounce_to"]}",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("messageID..................: $messageID",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("Boundary...................: $boundary",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("bind_address...............: {$config["bind_address"]}",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("TargetHostname-SOURCE......: {$datas["TargetHostname-SOURCE"]}",__FUNCTION__,__FILE__,__LINE__);
		
		
		
	}
	 
if(!isset($datas["TargetHostname"])){$datas["TargetHostname"]="unkwown.server.name";}
	
	
		
$body[]="Return-Path: <{$config["bounce_from"]}>";
$body[]="X-Original-To: {$datas["bounce_to"]}";
$body[]="Delivered-To: {$datas["bounce_to"]}";
$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
$body[]="From: {$config["bounce_from"]} (Mail Delivery System)";
$body[]="Subject: Undelivered Mail Returned to Sender";
$body[]="To: {$datas["bounce_to"]}";
$body[]="Auto-Submitted: auto-replied";
$body[]="MIME-Version: 1.0";
$body[]="Content-Type: multipart/report; report-type=delivery-status;";
$body[]="	boundary=\"$boundary\"";
$body[]="Content-Transfer-Encoding: 8bit";
$body[]="Message-Id: <$random_hash@$instance>";
$body[]="";
$body[]="This is a MIME-encapsulated message.";
$body[]="";
$body[]="--$boundary";
$body[]="Content-Description: Notification";
$body[]="Content-Type: text/plain; charset=us-ascii";
$body[]="";
$body[]="This is the mail system at host $instance.";
$body[]="";
$body[]="I'm sorry to have to inform you that your message could not";
$body[]="be delivered to one or more recipients. It's attached below.";
$body[]="";
$body[]="For further assistance, please send mail to postmaster.";
$body[]="";
$body[]="If you do so, please include this problem report. You can";
$body[]="delete your own text from the attached returned message.";
$body[]="";
$body[]="                   The mail system";
$body[]="";
$body[]="<{$datas["recipients"]}>: host {$instance}[{$datas["TargetHostname"]}] said:";
$body[]="   {$datas["BOUNCE_ERROR_NUM"]} {$datas["BOUNCE_ERROR"]}.";
$body[]="";
$body[]="--$boundary";
$body[]="Content-Description: Delivery report";
$body[]="Content-Type: message/delivery-status";
$body[]="";
$body[]="Reporting-MTA: dns; $instance";
$body[]="X-Postfix-Sender: rfc822; {$datas["sender"]}";
$body[]="Arrival-Date: $ArrivalDate (CET)";
$body[]="";
$body[]="Final-Recipient: rfc822; {$datas["recipients"]}";
$body[]="Action: failed";
$body[]="Status: {$datas["BOUNCE_ERROR_NUM"]}";
$body[]="Remote-MTA: dns; {$datas["TargetHostname"]}";
$body[]="Diagnostic-Code: smtp; {$datas["BOUNCE_ERROR"]}.";
$body[]="    ";
$body[]="";
$body[]="--$boundary";
$body[]="Content-Description: Undelivered Message";
$body[]="Content-Type: message/rfc822";
$body[]="Content-Transfer-Encoding: 8bit";
$body[]="";
$body[]=$MAILDATA;
$body[]="";
$body[]="";
$body[]="--$boundary--";
$body[]="";	
	
	$smtp=new smtp();
	if($config["bounce_use_instance"]==0){
		$tt=explode("@",$config["bounce_to"]);
		$domainname=$tt[1];
		$TargetHostname=$smtp->ResolveMXDomain($tt[1]);
	}else{
		$TargetHostname=$config["instance_ipaddr"];
	}
	
	
	$paramsSMTP["host"]=$TargetHostname;
	$paramsSMTP["helo"]=$config["instance_name"];
	$paramsSMTP["bindto"]=$config["bind_address"];
	if($TargetHostname==null){
		smtp::events("from=<{$config["bounce_from"]}> to=<{$config["bounce_to"]}> no hostname tp send email, aborting",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	if($config["debug_parameters"]==1){smtp::events("from=<{$config["bounce_from"]}> to=<{$config["bounce_to"]}> via \"$TargetHostname\"",__FUNCTION__,__FILE__,__LINE__);}
	$smtp=new smtp($paramsSMTP);
	smtp::events("-> Connect({$paramsSMTP["host"]}:25)",__FUNCTION__,__FILE__,__LINE__);
	if(!$smtp->connect($paramsSMTP)){
			$paramsSMTP["bindto"]="127.0.0.1";
			if(!$smtp->connect($paramsSMTP)){
				unset($paramsSMTP["bindto"]);
				if(!$smtp->connect($paramsSMTP)){
					smtp::events("FAILED, unable to connect to the target server....",__FUNCTION__,__FILE__,__LINE__);
					if($config["debug_parameters"]==1){smtp::events("Deleting the message $fullpath",__FUNCTION__,__FILE__,__LINE__);}
					@unlink($fullpath);	
					@unlink($sourcefile);					
					return;
				}
			}
	}
	if($config["debug_parameters"]==1){smtp::events("OK -> Connected, send email From=<{$config["bounce_from"]}> to=<{$config["bounce_to"]}>",__FUNCTION__,__FILE__,__LINE__);}
	$finalbody=@implode("\r\n", $body);
	$finalheader=null;
	
	
	if(!$smtp->send(array("from"=>$config["bounce_from"],"recipients"=>$config["bounce_to"],"body"=>$finalbody,"headers"=>$finalheader))){
			Checkerror($smtp->error_number,$smtp->error_text, $fullpath,$config);
			smtp::events("Fatal ERROR WHILE SENDING Bounce closing connection",__FUNCTION__,__FILE__,__LINE__);
			$smtp->quit();
			if($config["debug_parameters"]==1){smtp::events("Deleting the message $fullpath",__FUNCTION__,__FILE__,__LINE__);}
			@unlink($fullpath);	
			@unlink($sourcefile);
			return;
	}
	
	if($config["debug_parameters"]==1){smtp::events("Success sending bounce trough [{$TargetHostname}:25]",__FUNCTION__,__FILE__,__LINE__);}
	$smtp->quit();
	smtp::events("Bounce Error Success From=<{$config["bounce_from"]}> to=<{$config["bounce_to"]}> {$datas["BOUNCE_ERROR"]}",__FUNCTION__,__FILE__,__LINE__);
@unlink($fullpath);	
@unlink($sourcefile);	
	
}	


function PopulateConfig($instance,$domain){
	$DEBUG=false;
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	if($GLOBALS["VERBOSE"]){echo "Populate Config for instance to $instance -> $domain\n";}
	$CacheDirParams="{$GLOBALS["QUEUE_DIRECTORY_ERROR"]}/Config/$instance/$domain";	
	$LoadCache=false;
	if(is_file("$CacheDirParams/parameters")){
		$TTL=$GLOBALS["CLASS_UNIX"]->file_time_min("$CacheDirParams/parameters");
		if($GLOBALS["VERBOSE"]){echo "$CacheDirParams/parameters -> TTL = {$TTL}mn\n";}
		if($TTL<6){
			$params=unserialize(@file_get_contents("$CacheDirParams/parameters"));
			$LoadCache=true;
		}
	}
	
	if($GLOBALS["VERBOSE"]){$LoadCache=false;}
	if(!is_array($params)){$LoadCache=false;}
		
if(!$LoadCache){
		$q=new mysql();
		include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
		$sql="SELECT params FROM postfix_smtp_advrt WHERE hostname='$instance' AND domainname='$domain'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){smtp::events("[$instance/$domain]: FATAL: MYSQL ERROR !!! $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
		$params=unserialize(base64_decode($ligne["params"]));
		
}
	
	if(!is_array($params)){smtp::events("[$instance/$domain]: FATAL: `params` field is not an array, assuming default values",__FUNCTION__,__FILE__,__LINE__);}
	$cerr=array();
	if(!isset($params["max_msg_per_connection"])){$params["max_msg_per_connection"]=5;$cerr[]="max_msg_per_connection";}
	if(!isset($params["max_msg_rate"])){$params["max_msg_rate"]=600;$cerr[]="max_msg_rate";}
	if(!isset($params["max_msg_rate_timeout"])){$params["max_msg_rate_timeout"]=300;$cerr[]="max_msg_rate";}
	if(!isset($params["max_smtp_out"])){$params["max_smtp_out"]=5;$cerr[]="max_smtp_out";}
	if(!isset($params["change_timecode"])){$params["change_timecode"]=1;$cerr[]="change_timecode";}
	if(!isset($params["max_cnt_hour"])){$params["max_cnt_hour"]=1;$cerr[]="max_cnt_hour";}
	if(!isset($params["bind_address"])){$params["bind_address"]=null;}
	if(!isset($params["wait_xs_per_message"])){$params["wait_xs_per_message"]=0;$cerr[]="wait_xs_per_message";}
	if(!isset($params["max_err_per_message"])){$params["max_err_per_message"]=3;$cerr[]="max_err_per_message";}
	if(!isset($params["bounce_use_instance"])){$params["bounce_use_instance"]=0;$cerr[]="bounce_use_instance";}
	
	
	
	if(!isset($params["CNX_421"])){$params["CNX_421"]=30;$cerr[]="CNX_421";}
	if(!isset($params["CNX_421_MOVE"])){$params["CNX_421_MOVE"]=0;$cerr[]="CNX_421_MOVE";}
	
	
	
	if(!isset($params["msgs_ttl"])){$params["msgs_ttl"]=300;$cerr[]="msgs_ttl";}
	if(!isset($params["bounce_from"])){$params["bounce_from"]="MAILER-DAEMON";$cerr[]="bounce_from";}
	if(!isset($params["bounce_to"])){$params["bounce_to"]=null;$cerr[]="bounce_to";}
	if(!isset($params["min_subqueue_msgs"])){$params["min_subqueue_msgs"]=5;$cerr[]="min_subqueue_msgs";}
	if(!isset($params["min_subqueue_msgs_ttl"])){$params["min_subqueue_msgs_ttl"]=30;$cerr[]="min_subqueue_msgs_ttl";}
	if(!isset($params["smtp_authenticate"])){$params["smtp_authenticate"]=0;$cerr[]="smtp_authenticate";}
	if(!isset($params["AUTH-USER"])){$params["AUTH-USER"]=null;}
	if(!isset($params["AUTH-PASS"])){$params["AUTH-PASS"]=null;}
	if(!isset($params["AUTH-SMTP"])){$params["AUTH-SMTP"]=null;}
	
	
		
	if($params["smtp_authenticate"]==1){
		if($params["AUTH-USER"]==null){$params["smtp_authenticate"]=0;}
		if($params["AUTH-PASS"]==null){$params["smtp_authenticate"]=0;}
	}
	
	$main=new maincf_multi($instance);
	$params["instance_ipaddr"]=$main->ip_addr;
	$params["instance_name"]=$instance;
	
	if($params["instance_ipaddr"]==null){$params["instance_ipaddr"]=gethostbyname($instance);}
	if($params["instance_ipaddr"]==null){$params["instance_ipaddr"]="127.0.0.1";}
	
	$cerrBads=array();
	if(!is_numeric($params["max_msg_per_connection"])){$params["max_msg_per_connection"]=5;$cerrBads[]="max_msg_per_connection";}
	if(!is_numeric($params["max_msg_rate"])){$params["max_msg_rate"]=600;$cerrBads[]="max_msg_rate";}
	if(!is_numeric($params["change_timecode"])){$params["change_timecode"]=1;$cerrBads[]="change_timecode";}	
	if(!is_numeric($params["max_cnt_hour"])){$params["max_cnt_hour"]=500;$cerrBads[]="max_cnt_hour";}
	if(!is_numeric($params["wait_xs_per_message"])){$params["wait_xs_per_message"]=0;$cerrBads[]="wait_xs_per_message";}	
	if(!is_numeric($params["max_msg_rate_timeout"])){$params["max_msg_rate_timeout"]=300;$cerrBads[]="max_msg_rate_timeout";}
	if(!is_numeric($params["CNX_421"])){$params["CNX_421"]=30;$cerrBads[]="CNX_421";}
	if(!is_numeric($params["msgs_ttl"])){$params["msgs_ttl"]=300;$cerrBads[]="msgs_ttl";}
	if(!is_numeric($params["max_err_per_message"])){$params["max_err_per_message"]=3;$cerrBads[]="max_err_per_message";}
	if(!is_numeric($params["min_subqueue_msgs"])){$params["min_subqueue_msgs"]=5;$cerrBads[]="min_subqueue_msgs";}
	if(!is_numeric($params["min_subqueue_msgs_ttl"])){$params["min_subqueue_msgs_ttl"]=30;$cerrBads[]="min_subqueue_msgs_ttl";}
	if(!is_numeric($params["debug_parameters"])){$params["debug_parameters"]=0;$cerrBads[]="debug_parameters";}
	if(!is_numeric($params["CNX_421_MOVE"])){$params["CNX_421_MOVE"]=0;$cerrBads[]="CNX_421_MOVE";}
	if(!is_numeric($params["bounce_use_instance"])){$params["bounce_use_instance"]=0;$cerrBads[]="bounce_use_instance";}
	if(!is_numeric($params["smtp_authenticate"])){$params["smtp_authenticate"]=0;$cerrBads[]="smtp_authenticate";}
	if(!is_numeric($params["MailBodyRandomize"])){$params["MailBodyRandomize"]=0;}
	if(!is_numeric($params["MailSubjectRandomize"])){$params["MailSubjectRandomize"]=0;}
	if(!is_numeric($params["MailBodyRandomizeReplace"])){$params["MailBodyRandomizeReplace"]=0;}
	$params["BODY_REPLACE_PREPARED"]=array();
	if($GLOBALS["VERBOSE"]){$params["debug_parameters"]=1;}
	if($params["debug_parameters"]==1){$DEBUG=true;}
	
	
	
	if($params["MailBodyRandomizeReplace"]==1){
		if($GLOBALS["VERBOSE"]){echo "MailBodyRandomizeReplace: Enabled\n";}
		$BODY_REPLACE=$params["BODY_REPLACE"];
		if($GLOBALS["VERBOSE"]){echo "\$BODY_REPLACE: arrayof " .count($BODY_REPLACE)." items\n";}
		
		
		while (list ($keyword, $keyword_array) = each ($BODY_REPLACE) ){
			if($GLOBALS["VERBOSE"]){echo "MailBodyRandomizeReplace: Keyword: `$keyword` -> ".count($keyword_array)." items\n";}
			if(count($keyword_array)==0){
				if($DEBUG){smtp::events("\$BODY_REPLACE: keyword `$keyword` has no value -> skip it",__FUNCTION__,__FILE__,__LINE__);}
				continue;
			}
			while (list ($a, $b) = each ($keyword_array) ){
				if($GLOBALS["VERBOSE"]){echo "MailBodyRandomizeReplace: Keyword: `$keyword` -> `$a` item\n";}
				if(trim($a)==null){
					if($DEBUG){smtp::events("\$BODY_REPLACE: keyword `$keyword` `$a`,`$b` is null -> skip it",__FUNCTION__,__FILE__,__LINE__);}
					continue;
				}
				
				$params["BODY_REPLACE_PREPARED"][$keyword][]=$a;
				if($GLOBALS["VERBOSE"]){echo "MailBodyRandomizeReplace: Keyword: `$keyword` as ". count($params["BODY_REPLACE_PREPARED"][$keyword]) ." items\n";}
			}
				
		}
		if($GLOBALS["VERBOSE"]){echo "MailBodyRandomizeReplace: ". count($params["BODY_REPLACE_PREPARED"])." keywords\n";}
		if(count($params["BODY_REPLACE_PREPARED"])==0){$params["MailBodyRandomizeReplace"]=0;}
	}
	
	
	
	
	if($params["bounce_from"]==null){$params["bounce_from"]="MAILER-DAEMON";$cerrBads[]="bounce_from";}
	if($params["CNX_421"]<2){$params["CNX_421"]=30;$cerrBads[]="CNX_421";}
	if($params["debug_parameters"]==1){
		if(count($cerr)){smtp::events("[$instance/$domain]: These tokens has been used with default values (missing values):".@implode(",", $cerr),__FUNCTION__,__FILE__,__LINE__);}
		if(count($cerrBads)){smtp::events("[$instance/$domain]: These tokens has been returned back to default settings (wrong values):".@implode(",", $cerr),__FUNCTION__,__FILE__,__LINE__);}	
	}
	if($GLOBALS["VERBOSE"]){while(list( $index, $line ) = each ($params)){echo "$index......:  `$line`\n"; }}
	
	
	if(!is_dir($CacheDirParams)){@mkdir($CacheDirParams,0755,true);}
	@file_put_contents("$CacheDirParams/parameters", serialize($params));
	reset($params);
	return $params;
}


function KillBadHeader($maildata,$config){
	$GLOBALS["MAIL_NEW_MAIL_FROM"]=null;
	$GLOBALS["MAIL_NEW_MAIL_SOFT"]=null;
	$DEBUG=false;
	$maildata=str_replace("\r", "", $maildata);
	$tb=explode("\n", $maildata);
	
	if($config["debug_parameters"]==1){
		$DEBUG=TRUE;
		smtp::events("Exploded in ".count($tb)." lines",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("MailSoftRandomize.............: {$config["MailSoftRandomize"]}",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("MailFromRandomize.............: {$config["MailFromRandomize"]}",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("MailBodyRandomize.............: {$config["MailBodyRandomize"]}",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("MailSubjectRandomize..........: {$config["MailSubjectRandomize"]}",__FUNCTION__,__FILE__,__LINE__);
		smtp::events("MailBodyRandomizeReplace......: {$config["MailBodyRandomizeReplace"]}",__FUNCTION__,__FILE__,__LINE__);
	}
	
	if(preg_match("#^From\s+.+?@.+?[0-9]+#", $tb[0])){unset($tb[0]);}
	if(preg_match("#^From\s+.+?@.+?[0-9]+#", $tb[0])){unset($tb[0]);}
	
	if($config["MailFromRandomize"]==1){
		if(is_array($config["MAILFROM_RANDOMIZE"])){
			if(count($config["MAILFROM_RANDOMIZE"])>0){
				$index=rand(0, count($config["MAILFROM_RANDOMIZE"])-1);
				$GLOBALS["MAIL_NEW_MAIL_FROM"]=$config["MAILFROM_RANDOMIZE"][$index];
				if($GLOBALS["MAIL_NEW_MAIL_FROM"]<>null){
					$d=0;
					while(list( $num, $line ) = each ($tb)){
						$d++;if($d>100){break;}
						if(preg_match("#^Return-Path:#", $line)){$tb[$num]="Return-Path: <{$GLOBALS["MAIL_NEW_MAIL_FROM"]}>";}
						if(preg_match("#^From:.*?<(.+?)>#", $line,$re)){$tb[$num]=str_replace($re[1], $GLOBALS["MAIL_NEW_MAIL_FROM"], $tb[$num]);}
						if(preg_match("#^In-Reply-To:#", $line)){$tb[$num]="In-Reply-To: <{$GLOBALS["MAIL_NEW_MAIL_FROM"]}>";}
					}reset($tb);
				}
			}
		}
	}
	
	if($config["MailSoftRandomize"]==1){
		if(is_array($config["MAILSOFT_RANDOMIZE"])){
			if(count($config["MAILSOFT_RANDOMIZE"])>0){
				$index=rand(0, count($config["MAILSOFT_RANDOMIZE"])-1);
				$GLOBALS["MAIL_NEW_MAIL_SOFT"]=$config["MAILSOFT_RANDOMIZE"][$index];
				if($DEBUG){smtp::events("MAIL_NEW_MAIL_SOFT: `{$GLOBALS["MAIL_NEW_MAIL_SOFT"]}`",__FUNCTION__,__FILE__,__LINE__);}
				if($GLOBALS["MAIL_NEW_MAIL_SOFT"]<>null){
					$d=0;
					while(list( $num, $line ) = each ($tb)){
						$d++;if($d>100){break;}
						if(preg_match("#^X-Mailer:#", $line)){
							if($DEBUG){smtp::events("MailSoftRandomize: Found $line",__FUNCTION__,__FILE__,__LINE__);}
							$tb[$num]="X-Mailer: {$GLOBALS["MAIL_NEW_MAIL_SOFT"]}";
						}
						if(preg_match("#^User-Agent:#", $line,$re)){$tb[$num]="User-Agent: {$GLOBALS["MAIL_NEW_MAIL_SOFT"]}";}
					}reset($tb);
				}
			}
		}
	}
	
	if($config["MailSubjectRandomize"]==1){
			$d=0;
			$add=generatePassword();
			while(list( $num, $line ) = each ($tb)){
				$d++;if($d>100){break;}
				if(preg_match("#Subject:\s+(.+)#", $line,$re)){
					if($DEBUG){smtp::events("MailSubjectRandomize: Found $line -> add $add",__FUNCTION__,__FILE__,__LINE__);}
					$tb[$num]="Subject: {$re[1]} - $add";
				}
			}reset($tb);
	}
	
	
	
	if($config["MailBodyRandomizeReplace"]==1){
		if($DEBUG){smtp::events("`KillBadHeader() -> ReplaceBodyRandomize`",__FUNCTION__,__FILE__,__LINE__);}
		$tb=ReplaceBodyRandomize($tb,$config);
	}
	if($config["MailBodyRandomize"]==1){
		if($DEBUG){smtp::events("`KillBadHeader() -> generatePassword`",__FUNCTION__,__FILE__,__LINE__);}
		$tb[]=generatePassword();
	}


	return @implode("\r\n", $tb);
	
	
}

function ReplaceBodyRandomize($tb,$config){
	$DEBUG=false;
	$BODY_REPLACE_PREPARED=$config["BODY_REPLACE_PREPARED"];
	if($config["debug_parameters"]==1){
		$DEBUG=true;
		if($DEBUG){smtp::events("ReplaceBodyRandomize() -> BODY_REPLACE_PREPARED = arrayof ".count($BODY_REPLACE_PREPARED)." items",__FUNCTION__,__FILE__,__LINE__);}
	}
	
	
	while (list ($keyword, $keywordlist) = each ($BODY_REPLACE_PREPARED) ){
		$rand=rand(0, count($keywordlist)-1);
		$value=$keywordlist[$rand];
		if($value==null){
			if($DEBUG){smtp::events("ReplaceBodyRandomize() -> `$keyword` have no values...aborting",__FUNCTION__,__FILE__,__LINE__);}
			continue;
		}
		
		if(is_array($value)){
			if($DEBUG){smtp::events("ReplaceBodyRandomize() -> `$keyword` value = Array() -> skip it...aborting",__FUNCTION__,__FILE__,__LINE__);}
			continue;
		}
		
		reset($tb);
		if($DEBUG){smtp::events("ReplaceBodyRandomize() -> replace  `$keyword` by `$value` in ".count($tb)." lines",__FUNCTION__,__FILE__,__LINE__);}
		while(list( $num, $line ) = each ($tb)){
			$tb[$num]=str_replace($keyword, $value, $line);
		}
		
	}
	reset($tb);
	if($DEBUG){smtp::events("ReplaceBodyRandomize() -> Finish...",__FUNCTION__,__FILE__,__LINE__);}
	return $tb;
}


function generatePassword($length=9, $strength=2) {
	$vowels = 'aeuy';
	$consonants = 'bdghjmnpqrstvz';
	if ($strength & 1) {
		$consonants .= 'BDGHJLMNPQRSTVWXZ';
	}
	if ($strength & 2) {
		$vowels .= "AEUY";
	}
	if ($strength & 4) {
		$consonants .= '23456789';
	}
	if ($strength & 8) {
		$consonants .= '@#$%';
	}
 
	$password = '';
	$alt = time() % 2;
	for ($i = 0; $i < $length; $i++) {
		if ($alt == 1) {
			$password .= $consonants[(rand() % strlen($consonants))];
			$alt = 0;
		} else {
			$password .= $vowels[(rand() % strlen($vowels))];
			$alt = 1;
		}
	}
	return $password;
}

function ChangeTimeCode($maildata,$config){
	

	
	$maildata=str_replace("\r", "", $maildata);
	$tb=explode("\n", $maildata);
	while(list( $index, $line ) = each ($tb)){
		if(preg_match("#^Date:\s+(.+?)\+#i", $line,$re)){
			if($config["debug_parameters"]==1){smtp::events("Change Time Stamp value `{$re[1]}` in line \"{$tb[$index]}\"",__FUNCTION__,__FILE__,__LINE__);}
			$tb[$index]=str_replace($re[1], date("D, d M Y H:i:s")." ", $tb[$index]);
			reset($tb);
			return @implode("\r\n", $tb);
		}
	}
	smtp::events("Could not find Time Stamp from ".count($tb)." lines",__FUNCTION__,__FILE__,__LINE__);
	reset($tb);
	return @implode("\r\n", $tb);
	
}

function sendtestmail($from,$to){
	$unix=new unix();
	$smtp=new smtp();
	$tt=explode("@",$to);
	$domainname=$tt[1];
	$TargetHostname=$smtp->ResolveMXDomain($tt[1]);
	echo "Connect: $TargetHostname\n ";
	$params["host"]=$TargetHostname;
	$params["helo"]=$unix->hostname_g();
	
	
	if(!$smtp->connect($params)){
		smtp::events("[$domainname] $domainname -> Could not connect to  `$TargetHostname`",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
$body[]="Return-Path: <$from>";
$body[]="X-Original-To: $to";
$body[]="Date: ". date("D, d M Y H:i:s"). " +0100 (CET)";
$body[]="From: $from (Test sender)";
$body[]="Subject: Test mail ".date("D, d M Y H:i:s");
$body[]="To: $to";
$body[]="Auto-Submitted: auto-replied";
$body[]="MIME-Version: 1.0";	
$body[]="";
$body[]="This is a tests mail";	
	
$MAILDATA=@implode("\r\n", $body);
if(!$smtp->send(array("from"=>$from,"recipients"=>$to,"body"=>$MAILDATA))){echo "Failed\n";return ;}	
$smtp->quit();
echo "Success from=<$from> to=<$to>\n";
}
?>
