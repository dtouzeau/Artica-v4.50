<?php
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
$GLOBALS["FORCE"]=false;
$GLOBALS["EXECUTED_AS_ROOT"]=true;
$GLOBALS["RUN_AS_DAEMON"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["DISABLE_WATCHDOG"]=false;
if(preg_match("#--nowachdog#",$GLOBALS["COMMANDLINE"])){$GLOBALS["DISABLE_WATCHDOG"]=true;}
if(preg_match("#--force#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",$GLOBALS["COMMANDLINE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);

if($argv[1]=="--pfl"){pflogsumm($argv[2]);exit;}
if($argv[1]=="--rotate"){postfix_rotate();exit;}
if($argv[1]=="--convert"){convert3xlogs();exit;}
if($argv[1]=="--repair"){recover_temporary_logs();exit;}



postfix_rotate();


function postfix_rotate(){
	$unix=new unix();
	
	$timefile="/etc/artica-postfix/pids/postfix.rotate.time";
	$timeExec=$unix->file_time_min($timefile);
	if($timeExec<1380){
		squid_admin_mysql(2, "[SMTP] cannot rotate mail.log before 1380mn (current {$timeExec}mn)", null,__FILE__,__LINE__);
		return;
	}
	@unlink($timefile);
	@file_put_contents($timefile, time());
	$LogRotatePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}

	$BackupMaxDaysDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	if(!is_dir("$LogRotatePath/postfix")){@mkdir($LogRotatePath."/postfix",0755,true);}



	$SourceFilePath="$LogRotatePath/postfix/".time().".log";
	if(!@copy("/var/log/mail.log", $SourceFilePath)){
		squid_admin_mysql(0, "[SMTP]: Fatal, unable to copy /var/log/mail.log to $SourceFilePath", null,__FILE__,__LINE__);
		return;
	}
	
	$echo=$unix->find_program("echo");
	shell_exec("$echo \"\" >/var/log/mail.log");
	$unix=new unix();$unix->RESTART_SYSLOG(true);
	
	
	$hier=strtotime( '-1 days' );
	$hiertime=date("Y-m-d");
	$FinalDirectory="$BackupMaxDaysDir/mail/".date("Y",$hier)."/".date("m",$hier)."/".date("d",$hier);
	if(!is_dir($FinalDirectory)){@mkdir($FinalDirectory,0755,true);}
	$targetcompressed="$FinalDirectory/mail-$hiertime.gz";
	$targetReport="$FinalDirectory/mail-report.txt";
	if(!$unix->compress($SourceFilePath, $targetcompressed)){
		squid_admin_mysql(0, "[SMTP]: Fatal, unable to compress $SourceFilePath to $targetcompressed", null,__FILE__,__LINE__);
		return;
	}
	
	if(!pflogsumm($SourceFilePath,$targetReport)){
		squid_admin_mysql(0, "[SMTP]: Fatal, unable to create report from $SourceFilePath", null,__FILE__,__LINE__);
		
	}
	
	@unlink($SourceFilePath);
	
	$BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
	if($BackupSquidLogsUseNas==1){
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php /usr/share/artica-postfix/exec.squid.rotate.php --backup-to-nas-mail");
	}
	convert3xlogs();
	recover_temporary_logs();
	
}

function recover_temporary_logs(){
	$unix=new unix();
	
	$BackupMaxDaysDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	
	$LogRotatePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	$LogRotatePath=$LogRotatePath."/postfix";
	$zcat=$unix->find_program("zcat");
	$cat=$unix->find_program("cat");
	$tail=$unix->find_program("tail");
	$Files=$unix->DirFiles($LogRotatePath,"\.(gz|log)$");
	if(count($Files)==0){
		echo "$LogRotatePath -> \.(gz|log)$ -> No file\n";
		return;}
	foreach ($Files as $none=>$filename){
		$lines=array();
		$FullPath="$LogRotatePath/$filename";
		$filememtime=filemtime($FullPath);
		
		$FileExtension=$unix->file_extension($filename);
		echo "$FullPath Time:$filememtime extention:$FileExtension\n";
		
		if($FileExtension=="gz"){
			exec("$zcat $FullPath|$tail -n 5",$lines);
		}
		if($FileExtension=="log"){
			exec("$cat $FullPath|$tail -n 5",$lines);
		}
		
		$TimeToCalc=0;
		foreach ($lines as $ligne){
			if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.+?\s+#", $ligne,$re)){
				$sourcedate=$re[1]." ".$re[2]." ".$re[3];
				$SourceTime=strtotime($sourcedate);
				if($SourceTime>$TimeToCalc){$TimeToCalc=$SourceTime;}
			}
		}
		$FinalDirectory="$BackupMaxDaysDir/mail/".date("Y",$TimeToCalc)."/".date("m",$TimeToCalc)."/".date("d",$TimeToCalc);
		@mkdir($FinalDirectory,0755,true);
		$targetcompressed="$FinalDirectory/mail-$SourceTime.gz";
		
		if(is_file($targetcompressed)){
			squid_admin_mysql(0, "[SMTP]: Unable to convert mail-$SourceTime.gz to new dir ( file exists)", "$targetcompressed exists",__FILE__,__LINE__);
			continue;
		}
		if($FileExtension=="gz"){
			if(!@copy($FullPath, $targetcompressed)){
				squid_admin_mysql(0, "[SMTP]: Unable to backup mail-$SourceTime.gz to new dir ( copy error)", "$FullPath to $targetcompressed ERROR",__FILE__,__LINE__);
				continue;
			}
		}
		if($FileExtension=="log"){
			if(!$unix->compress($FullPath, $targetcompressed)){
				squid_admin_mysql(0, "[SMTP]: Unable to compress mail-$SourceTime.gz to new dir ( compress error)", "$FullPath to $targetcompressed ERROR",__FILE__,__LINE__);
				continue;
			}
			
		}
		
		
		echo "Success converting $FullPath to $targetcompressed\n";
		@unlink($FullPath);
		continue;
		
	}
	
}


function convert3xlogs(){
	
	if(!is_dir("/home/postfix/logrotate")){return;}
	$BackupMaxDaysDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	
	$unix=new unix();
	$Files=$unix->DirFiles("/home/postfix/logrotate","[0-9]+-[0-9]+-[0-9]+\.gz");
	if(count($Files)==0){
		if(!@rmdir("/home/postfix/logrotate")){
			squid_admin_mysql(1, "[SMTP]: Unable to remove /home/postfix/logrotate directory", null,__FILE__,__LINE__);
		}
	return;
	}
	
	
	$zcat=$unix->find_program("zcat");
	$tail=$unix->find_program("tail");
	
	foreach ($Files as $none=>$filename){
		$lines=array();
		$FullPath="/home/postfix/logrotate/$filename";
		$filememtime=filemtime($FullPath);
		exec("$zcat $FullPath|$tail -n 5",$lines);
		$TimeToCalc=0;
		foreach ($lines as $ligne){
			if(preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.+?\s+#", $ligne,$re)){
				$sourcedate=$re[1]." ".$re[2]." ".$re[3];
				$SourceTime=strtotime($sourcedate);
				if($SourceTime>$TimeToCalc){$TimeToCalc=$SourceTime;}
			}
		}
		$FinalDirectory="$BackupMaxDaysDir/mail/".date("Y",$TimeToCalc)."/".date("m",$TimeToCalc)."/".date("d",$TimeToCalc);
		@mkdir($FinalDirectory,0755,true);
		if(is_file("$FinalDirectory/$filename")){
			squid_admin_mysql(0, "[SMTP]: Unable to convert $filename to new dir ( file exists)", "$FinalDirectory/$filename exists",__FILE__,__LINE__);
			continue;
		}
		if(!@copy($FullPath, "$FinalDirectory/$filename")){
			squid_admin_mysql(0, "[SMTP]: Unable to backup $filename to new dir ( copy error)", "$FullPath to $FinalDirectory/$filename ERROR",__FILE__,__LINE__);
			continue;			
		}
		echo "Success converting $FullPath to $FinalDirectory/$filename\n";
		@unlink($FullPath);
		continue;
		
	}
	if(!@rmdir("/home/postfix/logrotate")){
		squid_admin_mysql(1, "[SMTP]: Unable to remove /home/postfix/logrotate directory", null,__FILE__,__LINE__);
	}
}

function pflogsumm($filename,$targetReport){
	$unix=new unix();
	$tmpfile=$unix->FILE_TEMP();
	$binary="/usr/share/artica-postfix/bin/pflogsumm.pl";
	@chmod("$binary",0755);
	echo "$binary $filename >$targetReport\n";
	system("$binary $filename >$targetReport");
	if(is_file($targetReport)){
		@unlink("/etc/artica-postfix/settings/Daemons/LasPostFixReport");
		@copy($targetReport, "/etc/artica-postfix/settings/Daemons/LasPostFixReport");
		return true;
	}
}
	



?>





