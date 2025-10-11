<?php
if(isset($argv[1])){if($argv[1]=="--clean"){die();}}

$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
$GLOBALS["FORCE"]=false;
$GLOBALS["EXECUTED_AS_ROOT"]=true;
$GLOBALS["RUN_AS_DAEMON"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["DISABLE_WATCHDOG"]=false;
if(preg_match("#--nowachdog#",$GLOBALS["COMMANDLINE"])){$GLOBALS["DISABLE_WATCHDOG"]=true;}
if(preg_match("#--force#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",$GLOBALS["COMMANDLINE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}



if($argv[1]=="--run"){run();exit();}

	if(system_is_overloaded(basename(__FILE__))){
        squid_admin_mysql(1, "{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: {OVERLOADED_SYSTEM}, aborting task",
            ps_report(),__FILE__,__LINE__);
	}
//exec.logrotate.php --reconfigure
if($argv[1]=="--moveolds"){moveolds2();exit();}
if($argv[1]=="--reconfigure"){reconfigure();exit();}
if($argv[1]=="--mysql"){exit();}
if($argv[1]=="--var"){CheckLogStorageDir($argv[2]);exit();}

if($argv[1]=="--purge-nas"){exit();}
if($argv[1]=="--squid"){exit();}
if($argv[1]=="--convert"){exit();}
if($argv[1]=="--test-nas"){tests_nas(true);exit();}





	$sock=new sockets();
	$ArticaMaxLogsSize=$sock->GET_PERFS("ArticaMaxLogsSize");
	if($ArticaMaxLogsSize<1){$ArticaMaxLogsSize=300;}
	$GLOBALS["ArticaMaxLogsSize"]=$ArticaMaxLogsSize;
	

$unix=new unix();
$logrotate=$unix->find_program("logrotate");if(!is_file($logrotate)){echo "logrotate no such file\n";}

$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$timefile="/etc/artica-postfix/pids/logrotate.time";
$pid=file_get_contents("$pidfile");

if($unix->process_exists($pid,basename(__FILE__))){
	$timeMin=$unix->PROCCESS_TIME_MIN($pid);
	squid_admin_mysql(2, "Already executed PID $pid since $timeMin Minutes",null,__FILE__,__LINE__);
	if($timeMin>240){
		squid_admin_mysql(2, "Too many TTL, $pid will be killed",null,__FILE__,__LINE__);
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}else{
		exit();
	}
}

$logrotate_pid=$unix->PIDOF($logrotate);
if($unix->process_exists($logrotate_pid)){
	$time=$unix->PROCCESS_TIME_MIN($logrotate_pid);
	squid_admin_mysql(2, "Warning, a logrotate task PID $logrotate_pid still {running} {since} {$time}Mn, Aborted task",__FUNCTION__,__FILE__,__LINE__,"logrotate");
	exit();
}



@file_put_contents($pidfile, getmypid());
$time=$unix->file_time_min($timefile);
if(!$GLOBALS["FORCE"]){if($time<30){squid_admin_mysql(2, "No less than 30mn (current {$time}Mn)",__FUNCTION__,__FILE__,__LINE__,"logrotate");exit();}}
@unlink($timefile);
@file_put_contents($timefile, time());
moveolds2();
reconfigure();
$cmd=$unix->EXEC_NICE().$logrotate." -s /var/log/logrotate.state /etc/logrotate.conf 2>&1";
if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
$t=time();
$results[]="Results of : $cmd";
exec($cmd,$results);
$took=$unix->distanceOfTimeInWords($t,time(),true);
rotate_events("Success took: $took\n\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__,"logrotate");



function run(){
	$sock=new sockets();	
	$unix=new unix();
	$logrotate=$unix->find_program("logrotate");
	if(!is_file($logrotate)){return;}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/logrotate.". __FUNCTION__.".time";
	$pid=@file_get_contents("$pidfile");
	if($unix->process_exists($pid,basename(__FILE__))){exit();}
	@file_put_contents($pidfile, getmypid());
	$time=$unix->file_time_min($timefile);
	if($time<60){events("No less than 1h or delete $timefile file",__FUNCTION__,__FILE__,__LINE__,"logrotate");exit();}
	@unlink($timefile);
	@file_put_contents($timefile, time());	
	
	reconfigure();
	$cmd=$unix->EXEC_NICE().$logrotate." -s /var/log/logrotate.state /etc/logrotate.conf 2>&1";
	events("Executing: $cmd");
	rotate_events("Last scan {$time}mn, Executing: $cmd",__FUNCTION__,__FILE__,__LINE__,"logrotate");
	$t=time();
	exec($cmd,$results);
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	if(count($results)>3) {
        squid_admin_mysql(2, "LOGROTATE: {took} $took (see notification content)", @implode("\n", $results), __FILE__, __LINE__);
    }
        events("Success took: $took\n".@implode("<br>", $results));
}






function ConvertGZToBzip($filesource){
	$t=time();
	$fromTime=time();
	$fileDest=str_replace(".gz", ".bz2", $filesource);
	$unix=new unix();
	$gunzip=$unix->find_program("gunzip");
	$bzip2=$unix->find_program("bzip2");
	$cmd="$gunzip --to-stdout \"$filesource\" | $bzip2 > \"$fileDest\"";
	shell_exec($cmd);
	$took=$unix->distanceOfTimeInWords($fromTime,time(),true);
	squid_admin_mysql(2, "File $filesource as been converted to bz2, took: $took",__FUNCTION__,__FILE__,__LINE__,"logrotate");
	if(!is_file($fileDest)){return null;}
	return $fileDest;
}

function CheckLogStorageDir($DirPath=null){
	$DirPath=rtrim($DirPath, '/');
	if($DirPath=="/var/log"){return;}
	
		
	$unix=new unix();
	
	//$dir=new DirectoryIterator("/var/log");

	
	if($unix->FILE_IS_LINK("/var/log")){
		$realpath=$unix->FILE_REALPATH("/var/log");
		echo "/var/log is a symbolic link to $realpath <> $DirPath\n";
		if($realpath==$DirPath){return true;}
		
	}
	
	if(!is_dir($DirPath)){
		echo "Creating $DirPath\n";
		@mkdir($DirPath,0755,true);
	}
	
	if(!is_dir($DirPath)){
		echo "Creating $DirPath failed, permissions denied\n";
		return;
		
	}
	
	$t=time();
	$mv=$unix->find_program("mv");
	$cp=$unix->find_program("cp");
	$rm=$unix->find_program("rm");	
	$ln=$unix->find_program("ln");	
	
	$tmpdir="/var/syslog-transfered-$t";
	echo "rename /var/log to $tmpdir\n";
	shell_exec("$mv /var/log $tmpdir");
	
	if(!is_dir($tmpdir)){
		echo "Rename /var/log /var/syslog-transfered-$t failed no such directory\n";
		return;
	}
	
	echo "linking /var/log -> $DirPath\n";
	if(is_dir("/var/log")){
		$cmd="$rm -rf /var/log && $ln -s -f $DirPath /var/log";
	}else{
		$cmd="$ln -s -f $DirPath /var/log";
	}
	echo $cmd."\n";
	
	shell_exec($cmd);
	
	if(!$unix->FILE_IS_LINK("/var/log")){
		echo "Failed linking /var/log to $DirPath go back\n";
		shell_exec("$rm -rf /var/log");
		shell_exec("$mv $tmpdir /var/log");
		return;
	}else{
		echo "success linking /var/log to ". $unix->FILE_REALPATH("/var/log")." go back\n";
	}
	
	

	echo "Copy $tmpdir to $DirPath\n";
	shell_exec("$cp -ru $tmpdir/* $DirPath/");
	echo "remove olddir  $tmpdir\n";
	shell_exec("$rm -rf $tmpdir 2>&1");
	
	
}

function tests_nas(){
	$sock=new sockets();
	$BackupSquidLogsUseNas=$sock->GET_INFO("BackupSquidLogsUseNas");
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	$EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	if(!is_numeric($BackupSquidLogsUseNas)){$BackupSquidLogsUseNas=0;}
	
	
	$mount=new mount("/var/log/artica-postfix/logrotate.debug");
	
	if($BackupSquidLogsUseNas==0){echo "Backup using NAS is not enabled\n";return;}
	$BackupSquidLogsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
	$BackupSquidLogsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
	$BackupSquidLogsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
	$BackupSquidLogsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
	$BackupSquidLogsNASRetry=$sock->GET_INFO("BackupSquidLogsNASRetry");
	if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}
	
	$failed="***********************\n** FAILED **\n***********************\n";
	$success="***********************\n******* SUCCESS *******\n***********************\n";
	
	$mountPoint="/mnt/BackupSquidLogsUseNas";
	if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,
			$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
		
		if($BackupSquidLogsNASRetry==1){
			sleep(3);
			$mount=new mount("/var/log/artica-postfix/logrotate.debug");
			if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
				echo "$failed\nUnable to connect to NAS storage system: $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr\n";
				echo @implode("\n", $GLOBALS["MOUNT_EVENTS"]);
				return;					
			}
		}else{
			echo "$failed\nUnable to connect to NAS storage system: $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr\n";
			echo @implode("\n", $GLOBALS["MOUNT_EVENTS"]);
			return;
		}
	}
			
	
	$BackupMaxDaysDir="$mountPoint/artica-backup-syslog";

	@mkdir($BackupMaxDaysDir,0755,true);
	if(!is_dir($BackupMaxDaysDir)){
		echo "$failed$BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr/$BackupSquidLogsNASFolder/artica-backup-syslog permission denied.\n";
		$mount->umount($mountPoint);
		return;
	}
	
	$t=time();
	@file_put_contents("$BackupMaxDaysDir/$t", "#");
	if(!is_file("$BackupMaxDaysDir/$t")){
		echo "$failed$BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr/$BackupSquidLogsNASFolder/artica-backup-syslog/* permission denied.\n";
		$mount->umount($mountPoint);
		return;
	}	
	@unlink("$BackupMaxDaysDir/$t");
	$mount->umount($mountPoint);
	echo "$success";
	
}








function reconfigure():bool{

	foreach (glob("/etc/logrotate.d/*") as $filename) {
		if($GLOBALS["VERBOSE"]){echo "Remove $filename\n";}
		@unlink($filename);
	}
    $unix=new unix();
    $SquidRotateEnableCrypt=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateEnableCrypt"));
    $crypt_binary="/usr/share/artica-postfix/bin/artica-rotate";
    if($SquidRotateEnableCrypt==1){
        if(!is_file("/etc/artica-postfix/legal-logs-crypt")){
            $unix->ToSyslog("[Legal Logs]: Launch encryption of log backups...");
            @chmod($crypt_binary,0755);
            $unix->go_exec("$crypt_binary -crypt-logs");
            $unix->Popuplate_cron_make("legal-logs-crypt","30 */2 * * *","$crypt_binary -crypt-logs");

        }
        return true;
    }
    $unix->Popuplate_cron_delete("legal-logs-crypt");
    return true;
}

function LoagRotateApache(){

}

function moveolds2(){

}




function moveolds(){

}

function ROTATE_COMPRESS_FILE($filename){
	$unix=new unix();
	if(!isset($GLOBALS["BZ2BIN"])){$GLOBALS["BZ2BIN"]=$unix->find_program("bzip2");;}
	$EXEC_NICE=$unix->EXEC_NICE();
	events("$filename -> Compressing");
	$cmdline="$EXEC_NICE {$GLOBALS["BZ2BIN"]} -z $filename";
	shell_exec($cmdline);
	if(!is_file("$filename.bz2")){return false;}
	$cmdline="{$GLOBALS["BZ2BIN"]} -t -v $filename.bz2 2>&1";
	exec($cmdline,$results);
	foreach ($results as $num=>$line){
		if(strpos($line,": ok")>0){return true;}
	}
	@unlink("$filename.bz2");
}








function events($text){
	
	if(function_exists("debug_backtrace")){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
			$file=basename($trace[1]["file"]);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
		}
	}
	
	if(!isset($GLOBALS["CLASS_SYSTEMLOGS"])){$GLOBALS["CLASS_SYSTEMLOGS"]=new mysql_storelogs();}
	if($GLOBALS["VERBOSE"]){echo "$text\n";}
	$GLOBALS["CLASS_SYSTEMLOGS"]->events($text,$function,$line);
}
