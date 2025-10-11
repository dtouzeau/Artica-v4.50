<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["NOTIMECHECK"]=false;
$GLOBALS["SERV_NAME"]="debian-mirror";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');


writelogs("Initialize... \"{$argv[1]}\"",__FUNCTION__,__FILE__,__LINE__);

$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_UNIX"]=new unix();

writelogs("Initialize done..... \"{$argv[1]}\"",__FUNCTION__,__FILE__,__LINE__);
if($argv[1]=="--debian-size"){debian_size();exit();}
if($argv[1]=="--pid"){$GLOBALS["VERBOSE"]=true;echo RSYNC_PID()."\n";exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--kill"){$GLOBALS["OUTPUT"]=true;kill_process();exit();}
if($argv[1]=="--logs"){$GLOBALS["OUTPUT"]=true;ChecksLogs();exit();}
if($argv[1]=="--schedule"){$GLOBALS["OUTPUT"]=true;rsync_mirror_execute(true);ChecksLogs();exit();}


if($argv[1]=="--start-exec-manu"){
	$GLOBALS["NOTIMECHECK"]=true;
	writelogs("Starting Execution... -> rsync_mirror_execute() -> manual",__FUNCTION__,__FILE__,__LINE__);
	$GLOBALS["OUTPUT"]=true;
	rsync_mirror_execute(true);
	exit();
}

if($argv[1]=="--start-exec"){
	writelogs("Starting Execution... -> rsync_mirror_execute()",__FUNCTION__,__FILE__,__LINE__);
	$GLOBALS["OUTPUT"]=true;
	rsync_mirror_execute(false);
	exit();
}


exit();

function start(){
	$SERV_NAME=$GLOBALS["SERV_NAME"];
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME cannot be started by init.d ...\n";}
	
}


function RSYNC_PID(){
	$unix=new unix();
	$MirrorDebianDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorDebianDir");
	if($MirrorDebianDir==null){$MirrorDebianDir="/home/mirrors/Debian";}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("rsync.+?$MirrorDebianDir");
	return $master_pid;
}

function kill_process(){
	$SERV_NAME=$GLOBALS["SERV_NAME"];
	$sock=new sockets();
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]:$SERV_NAME Already task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
		
	$MirrorDebianMaxExecTime=$sock->GET_INFO("MirrorDebianMaxExecTime");
	if(!is_numeric($MirrorDebianMaxExecTime)){exit();}
	if($MirrorDebianMaxExecTime==0){exit();}
	if($MirrorDebianMaxExecTime<5){exit();}
	
	
	$pid=RSYNC_PID();
	$processtime=$unix->PROCCESS_TIME_MIN($pid);
	if($processtime<$MirrorDebianMaxExecTime){exit();}
	
	
	
	WriteToSyslog("rsync, killing pid $pid TTL = {$processtime}mn, exceed {$MirrorDebianMaxExecTime}mn");
	$kill=$unix->find_program("kill");
	unix_system_kill_force($pid);
	
	
}


function stop(){
	
	$SERV_NAME=$GLOBALS["SERV_NAME"];
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]:$SERV_NAME Already task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$pid=RSYNC_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME already stopped...\n";}
		return;
	}
	
	
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME with a ttl of {$time}mn\n";}
	
	$MirrorDebianDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorDebianDir");
	if($MirrorDebianDir==null){$MirrorDebianDir="/home/mirrors/Debian";}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME with a mirror located in \"$MirrorDebianDir\"\n";}
	
	
	$kill=$unix->find_program("kill");
	for($i=0;$i<10;$i++){
		$pid=RSYNC_PID();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME kill pid $pid..\n";}
			unix_system_kill_force($pid);
		}else{
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME wait $i/10\n";}
		sleep(1);
	}
	$pid=RSYNC_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME success...\n";}
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME Failed...\n";}	
	
}




function rsync_mirror_execute($scheduled=false){
	
	
	$SERV_NAME=$GLOBALS["SERV_NAME"];
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/DEBIAN_MIRROR_EXECUTION.TIME";
	
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		writelogs("$SERV_NAME Already task running PID $pid since {$time}mn",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]:$SERV_NAME Already task running PID $pid since {$time}mn\n";}
		return;
	}	
	
	writelogs("$SERV_NAME Checking PID ?",__FUNCTION__,__FILE__,__LINE__);
	$RSYNC_PID=RSYNC_PID();
	if($unix->process_exists($RSYNC_PID)){
		$time=$unix->PROCCESS_TIME_MIN($RSYNC_PID);
		writelogs("rsync task running PID $pid since {$time}mn",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]:$SERV_NAME rsync task running PID $pid since {$time}mn\n";}
		return;
	}	
	$MirrorEnableDebianSchedule=$sock->GET_INFO("MirrorEnableDebianSchedule");
	if(!is_numeric($MirrorEnableDebianSchedule)){$MirrorEnableDebianSchedule=0;}
	
	if($MirrorEnableDebianSchedule==1){
		if(!$scheduled){return;}
	}
	
	if($MirrorEnableDebianSchedule==0){
		if(!$GLOBALS["NOTIMECHECK"]){
			$MirrorDebianEachMn=$sock->GET_INFO("MirrorDebianEachMn");
			if(!is_numeric($MirrorDebianEachMn)){$MirrorDebianEachMn=2880;}
			$pidtimeExec=$unix->file_time_min($pidtime);
			if($pidtimeExec<$MirrorDebianEachMn){writelogs("{$pidtimeExec}mn, require {$MirrorDebianEachMn}mn",__FUNCTION__,__FILE__,__LINE__);return;}	
		}
	}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	ChecksLogs(true);
	
	$rsync=$unix->find_program("rsync");
	writelogs("$SERV_NAME rsync -> $rsync",__FUNCTION__,__FILE__,__LINE__);
	
	if(!is_file($rsync)){
		writelogs("$SERV_NAME rsync no such binary",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]:$SERV_NAME rsync no such binary\n";}
		return;
	}
	$sock=new sockets();
	$MirrorEnableDebian=$sock->GET_INFO("MirrorEnableDebian");
	$MirrorDebianDir=$sock->GET_INFO("MirrorDebianDir");
	$MirrorDebianBW=$sock->GET_INFO("MirrorDebianBW");
	if(!is_numeric($MirrorEnableDebian)){$MirrorEnableDebian=0;}
	if(!is_numeric($MirrorDebianBW)){$MirrorDebianBW=500;}
	if($MirrorDebianDir==null){$MirrorDebianDir="/home/mirrors/Debian";}
	$MirrorDebianExclude=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorDebianExclude")));
	$SOURCES=FALSE;
	$nice=$unix->EXEC_NICE();
	if($MirrorDebianExclude["source"]==1){$SOURCES=true;}
	unset($MirrorDebianExclude["source"]);
	// port 873;
	
	writelogs("$SERV_NAME starting building command lines",__FUNCTION__,__FILE__,__LINE__);
	$cmds[]="--links";
	$cmds[]="--hard-links";
	$cmds[]="--times";
	$cmds[]="--delete-delay";
	$cmds[]="-prltvHSB8192";
	$cmds[]="--timeout 3600";
	$cmds[]="--stats";
	$cmds[]="--verbose";
	$cmds[]="--recursive";
	$cmds[]="--delay-updates";
	$cmds[]="--log-file=$MirrorDebianDir/rsync.log";
	$cmds[]="--files-from :indices/files/typical.files ";
	$cmds[]="--max-delete=40000 ";
	$cmds[]="--delay-updates";
	$cmds[]="--delete";
	$cmds[]="--delete-excluded";
	
	$EXCLUDES[]=".~tmp~";
	$EXCLUDES[]="Packages*";
	$EXCLUDES[]="Sources*";
	$EXCLUDES[]="Release*";
	$EXCLUDES[]="InRelease";
	$EXCLUDES[]="i18n/*";
	$EXCLUDES[]="ls-lR*";
	
	@mkdir("$MirrorDebianDir",0755,true);
	
	
	
	
	while (list ($ARCH, $enabled) = each ($MirrorDebianExclude) ){
		if(!is_numeric($enabled)){continue;}
		if($enabled==0){continue;}
		
		$EXCLUDES[]="-$ARCH-";
		$EXCLUDES[]="binary-$ARCH/";
		$EXCLUDES[]="Contents-$ARCH.gz";
		$EXCLUDES[]="Contents-udeb-$ARCH.gz";
		$EXCLUDES[]="Contents-$ARCH.diff/";
		$EXCLUDES[]="arch-$ARCH.files";
		$EXCLUDES[]="arch-$ARCH.list.gz";
		$EXCLUDES[]="*_$ARCH.deb";
		$EXCLUDES[]="*_$ARCH.udeb";
		$EXCLUDES[]="*_$ARCH.changes";
		writelogs("$SERV_NAME ".count($EXCLUDES)." excludes...",__FUNCTION__,__FILE__,__LINE__);
		
	}	
	
	if($SOURCES){
		$EXCLUDES[]="source/"; 
		$EXCLUDES[]="*.tar.gz"; 
		$EXCLUDES[]="*.diff.gz"; 
		$EXCLUDES[]="*.tar.bz2"; 
		$EXCLUDES[]="*.tar.xz"; 
		$EXCLUDES[]="*.diff.bz2"; 
		$EXCLUDES[]="*.dsc";
	}
	
	writelogs("$SERV_NAME ".count($EXCLUDES)." excludes...",__FUNCTION__,__FILE__,__LINE__);
	@mkdir("/etc/rsync",0755,true);
	@file_put_contents("/etc/rsync/mirdebexcl.txt", @implode("\n", $EXCLUDES)."\n");
	$cmds[]="--exclude-from '/etc/rsync/mirdebexcl.txt'";
	
	if($MirrorDebianBW>0){
		$cmds[]="--bwlimit=$MirrorDebianBW";
	}
	$cmds[]="rsync://ftp2.fr.debian.org/debian/ $MirrorDebianDir/";
	
	$cmdline=trim("$nice $rsync ". @implode(" ", $cmds));
	$nohup=$unix->find_program("nohup");
	
	writelogs("$cmdline",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$nohup $cmdline >/dev/null 2>&1 &");
	writelogs("$SERV_NAME Finish -> ChecksLogs()",__FUNCTION__,__FILE__,__LINE__);
	//ChecksLogs();
	
	
}

function ChecksLogs($noexec=false){
	$unix=new unix();
	$RSYNC_PID=RSYNC_PID();
	if($unix->process_exists($RSYNC_PID)){
		$time=$unix->PROCCESS_TIME_MIN($RSYNC_PID);
		writelogs("rsync task running PID $RSYNC_PID since {$time}mn",__FUNCTION__,__FILE__,__LINE__);
		
		return;
	}
	
	
	$MUST_RESTART=false;
	$ERROR=null;
	$sock=new sockets();
	
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$MirrorEnableDebian=$sock->GET_INFO("MirrorEnableDebian");
	$MirrorDebianDir=$sock->GET_INFO("MirrorDebianDir");
	$MirrorDebianBW=$sock->GET_INFO("MirrorDebianBW");
	if(!is_numeric($MirrorEnableDebian)){$MirrorEnableDebian=0;}
	if(!is_numeric($MirrorDebianBW)){$MirrorDebianBW=500;}
	if($MirrorDebianDir==null){$MirrorDebianDir="/home/mirrors/Debian";}	
	
	$filename="$MirrorDebianDir/rsync.log";
	if(!is_file($filename)){return;}
	$fileLOGS=$filename;
	$fp = @fopen($filename, "r");
	if(!$fp){if($GLOBALS["DEBUG_GREP"]){echo "$filename BAD FD\n";}return array();}
	
	$t=array();
	$c=0;
	$ARRAY=array();
	while(!feof($fp)){
		$line = trim(fgets($fp, 4096));
		if($line==null){continue;}
		if(strpos($line, "failed: No such file or directory")>0){continue;}
		
		
		if(preg_match("#^([0-9\/:\s]+)\s+\[([0-9]+)\]\s+MOTD:#", $line,$re)){
			$date=$re[1];$time=strtotime($date);$PID=$re[2];
			$NewDate=date("Y-m-d",$time);
			if(!isset($ARRAY[$PID]["START"])){$ARRAY[$PID]["START"]=$time;}
			$ARRAY[$PID]["STOP"]=$time;
			continue;
		}
				
		if(preg_match("#^([0-9\/:\s]+)\s+\[([0-9]+)\]\s+receiving file list#", $line,$re)){
			$date=$re[1];$time=strtotime($date);$PID=$re[2];
			$NewDate=date("Y-m-d",$time);
			if(!isset($ARRAY[$PID]["START"])){$ARRAY[$PID]["START"]=$time;}
			$ARRAY[$PID]["STOP"]=$time;
			continue;
		}
		
		if(preg_match("#^([0-9\/:\s]+)\s+\[([0-9]+)\]\s+rsync error:#", $line,$re)){
			$date=$re[1];
			$time=strtotime($date);
			$PID=$re[2];
			$NewDate=date("Y-m-d",$time);
			echo "Detected error on $NewDate $PID $line\n";
			if(strpos("#SIGINT, SIGTERM, or SIGHUP#", $line)>0){$MUST_RESTART=true;}
			$ARRAY[$PID]["STOP"]=$time;
			$ARRAY[$PID]["ERROR"]=$line;
			$ERROR=$line;
			continue;
		}
		
		if(!preg_match("#^([0-9\/:\s]+)\s+\[([0-9]+)\]\s+.*?\s+(.+)#", $line,$re)){
			echo "Unable to preg $line\n";
			continue;
		}
		
		$date=$re[1];
		$time=strtotime($date);
		$PID=$re[2];
		$NewDate=date("Y-m-d",$time);
		$filemame=$re[3];
		$filepath="$MirrorDebianDir/$filemame";
		
		if(!isset($ARRAY[$PID]["START"])){
			$ARRAY[$PID]["START"]=$time;
		}
		
		if(!isset($ARRAY[$PID]["FILES"])){$ARRAY[$PID]["FILES"]=0;}
		if(!isset($ARRAY[$PID]["SIZE"])){$ARRAY[$PID]["SIZE"]=0;}
		$ARRAY[$PID]["FILES"]++;
		$ARRAY[$PID]["STOP"]=$time;
		
		if(!is_file($filepath)){continue;}
		$ARRAY[$PID]["SIZE"]=$ARRAY[$PID]["SIZE"]+@filesize($filepath);
		
		
		

	}
	
	@fclose($fp);
	
	if($MUST_RESTART){
		if(!$noexec){
			shell_exec("$nohup $php ".__FILE__." --start-exec-manu >/dev/null 2>&1 &");
		}
	}
	
	if(count($ARRAY)==0){
		@unlink($filename);
		return;
	}
	
	$q=new mysql();
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
		$q->QUERY_SQL($sql,'artica_events');
		if(!$q->ok){return;}
		$f=array();
		while (list ($PID, $datas) = each ($ARRAY) ){
			
			$date=date('Y-m-d H:i:s',$datas["STOP"]);
			
			if(date('Y',$datas["STOP"])==1970){
				if(date('Y',$datas["START"])==1970){continue;}
				$date=date('Y-m-d H:i:s',$datas["START"]);
			}
			
			$datas["ERROR"]=mysql_escape_string2($datas["ERROR"]);
			$f[]="('$date','$PID','{$datas["START"]}','{$datas["STOP"]}','{$datas["FILES"]}','{$datas["totalsize"]}','{$datas["ERROR"]}')";
			
		}
		
		if(count($f)>0){
			$sql="INSERT IGNORE INTO mirror_logs (zDate,pid,starton,endon,filesnumber,totalsize,error) 
					VALUES ".@implode(",", $f);
			$q->QUERY_SQL($sql,'artica_events');
			if(!$q->ok){return;}
			
		}
		
		@file_put_contents($fileLOGS,"\n");
	
}


function debian_size(){
	$sock=new sockets();
	$unix=new unix();
	$MirrorEnableDebian=$sock->GET_INFO("MirrorEnableDebian");
	$MirrorDebianDir=$sock->GET_INFO("MirrorDebianDir");
	$MirrorDebianBW=$sock->GET_INFO("MirrorDebianBW");
	if(!is_numeric($MirrorEnableDebian)){$MirrorEnableDebian=0;}
	if(!is_numeric($MirrorDebianBW)){$MirrorDebianBW=500;}
	if($MirrorDebianDir==null){$MirrorDebianDir="/home/mirrors/Debian";}

	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	$extime=$unix->file_time_min($pidTime);
	if($GLOBALS["VERBOSE"]){echo "{$extime}Mn\n";}
	

	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		writelogs("Already process exists pid $pid running since {$time}mn",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	
	
	@file_put_contents($pidfile, getmypid());
	
	
	if(!$GLOBALS["VERBOSE"]){
		$TIME=$unix->file_time_min($pidTime);
		if($unix->file_time_min($pidTime)<30){
			writelogs("`$pidTime` {$TIME}mn, require 30mn, aborting",__FUNCTION__,__FILE__,__LINE__);
			ChecksLogs();
			return;
		}
	}

	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	if(is_overloaded()){if($GLOBALS["VERBOSE"]){echo "{OVERLOADED_SYSTEM}...\n";}return;}
	
	$nice=$unix->EXEC_NICE();
	$du=$unix->find_program("du");
	if($GLOBALS["VERBOSE"]){echo "$nice $du -s -b $MirrorDebianDir 2>&1\n";}
	writelogs("$nice $du -s -b $MirrorDebianDir 2>&1",__FUNCTION__,__FILE__,__LINE__);
	exec("$nice $du -s -b $MirrorDebianDir 2>&1",$results);
	writelogs("Done...",__FUNCTION__,__FILE__,__LINE__);

	foreach ($results as $num=>$val){
		if(!preg_match("#^([0-9\.]+)\s+#",$val,$re)){continue;}
		if($GLOBALS["VERBOSE"]){echo "{$re[1]} Bytes...\n";}
		$sock->SET_INFO("MirrorDebianDirSize", $re[1]);
	}
	ChecksLogs();
}
function is_overloaded($file=null){
	if(!isset($GLOBALS["CPU_NUMBER"])){$users=new usersMenus();$GLOBALS["CPU_NUMBER"]=intval($users->CPU_NUMBER);}
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$cpunum=$GLOBALS["CPU_NUMBER"]+1.5;
	if($file==null){$file=basename(__FILE__);}
	if($internal_load>$cpunum){$GLOBALS["SYSTEM_INTERNAL_LOAD"]=$internal_load;return true;}
	return false;


}

?>