<?php
if(!function_exists("posix_getuid")){echo "posix_getuid !! not exists\n";}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.offlineimap.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if($argv[1]=="--backup-md5"){backup_md5($argv[2]);exit();}
if($argv[1]=="--accounts"){accounts();exit();}
if($argv[1]=="--member"){member($argv[2]);exit();}
if($argv[1]=="--check"){checkTask($argv[2]);exit();}
if($argv[1]=="--folders"){mailboxes_folders($argv[2]);exit();}
if($argv[1]=="--schedules"){mailboxes_schedules();exit();}



function backup_md5($md5){
	$sock=new sockets();
	$unix=new unix();
	$q=new mysql();
	$backend_root="/root/.offlineimap";
	
	$pidfile="/var/run/offlineimap-$md5.pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		$timemin=$unix->PROCCESS_TIME_MIN($pid);
		$timefile=buildlogs("PID $pid running since {$timemin}mn , aborting",__FUNCTION__,__LINE__);
		LogsToMysqlMD5($md5);
		return;
	}
	$mypid=getmypid();
	@file_put_contents($pidfile, $mypid);
	if($GLOBALS["VERBOSE"]){echo "$pidfile -> $mypid\n";}
	$OfflineImapBackupTool=$sock->GET_INFO("OfflineImapBackupTool");
	if(!is_numeric($OfflineImapBackupTool)){$OfflineImapBackupTool=0;}
	
	$sql="SELECT * FROM mbxs_backup WHERE zmd5='$md5'";
	
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	if(!$q->ok){
		buildlogs("$q->mysql_error",__FUNCTION__,__LINE__);
		LogsToMysqlMD5($md5);
		return;
	}	
	$uid=$ligne["uid"];
	$account=$ligne["account"];
	$imapserv=$ligne["imapserv"];
	$MySqlQuery=$ligne;
	
	$OfflineImapWKDir=$sock->GET_INFO("OfflineImapWKDir");
	$OfflineImapBackupDir=$sock->GET_INFO("OfflineImapBackupDir");
	if($OfflineImapBackupDir==null){$OfflineImapBackupDir="%HOME%/mailbackups";}
	if($OfflineImapWKDir==null){$OfflineImapWKDir="/home/artica/mailbackups";}	
	
	
	if($OfflineImapBackupTool==0){
		buildlogs("This feature is disabled, aborting",__FUNCTION__,__LINE__);
		LogsToMysqlMD5($md5);
		return;
	}
	$fileZ=build_remote_backup_settings($MySqlQuery);
	$fileConf=$fileZ[0];
	$TargetDir=$fileZ[1];
	$logfile="/var/log/".basename($unix->FILE_TEMP());
	if(!is_file($fileConf)){
		LogsToMysqlMD5($md5);
		return;
	}
	
	if(!is_dir($TargetDir)){
		buildlogs("$TargetDir no such directory",__FUNCTION__,__LINE__);
		LogsToMysqlMD5($md5);
		return;		
	}
	
	$offlineimap=$unix->find_program("offlineimap");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$NICE=EXEC_NICE();	
	$t=time();
	$CacheLocal="$backend_root/Account-{$ligne["uid"]}";
	$CacheRemote="$backend_root/Account-{$ligne["account"]}";
	
	if(is_dir($CacheLocal)){shell_exec("$rm -rf $CacheLocal");}
	if(is_dir($CacheRemote)){shell_exec("$rm -rf $CacheRemote");}
	
	$cmd="$NICE$offlineimap -u basic -c $fileConf -l $logfile 2>&1";
	exec($cmd,$results);
	
	
	if(count($results)>0){
		foreach ($results as $num=>$ligne){
			buildlogs($ligne,__FUNCTION__,__LINE__);
		}
	}
	$results=explode("\n",@file_get_contents($logfile));
	if(count($results)>0){
			foreach ($results as $num=>$ligne){
				buildlogs($ligne,__FUNCTION__,__LINE__);
			}
		}
	@unlink($logfile);
	$offlimap=new offlineimap();
	$FinalDir=$offlimap->maildir_parse($OfflineImapBackupDir, $uid);
	@mkdir($FinalDir,0755,true);
	$FinalFile="$FinalDir/".date("YmdH")."-$account@$imapserv.tar.bz2";
	chdir($TargetDir);
	$cmd="$tar -cjf $FinalFile *";
	shell_exec($cmd);
	chdir("/root");
	$cmd="$rm -rf $TargetDir/";
	shell_exec($cmd);
	
	$FinalFileSize=$unix->file_size_human($FinalFile);
	buildlogs("$FinalFile: $FinalFileSize",__FUNCTION__,__LINE__);
	buildlogs("Execution done took:".$unix->distanceOfTimeInWords($t,time()),__FUNCTION__,__LINE__);
	LogsToMysqlMD5($md5);
	

	$imapserv=addslashes($imapserv);
	$account=addslashes($account);
	$FinalFileSize=$unix->file_size($FinalFile);
	$FinalFile=addslashes($FinalFile);
	$q->QUERY_SQL("DELETE FROM mbxs_backup_storage WHERE filepath='$FinalFile'","artica_backup");
	
	$sql="INSERT IGNORE INTO mbxs_backup_storage (`zDate`,`filepath`,`filesize`,`imapserv`,`account`,`zmd5`) 
	VALUES (NOW(),'$FinalFile','$FinalFileSize','$imapserv','$account','$md5')";
	$q->QUERY_SQL($sql,"artica_backup");
	
	
	
}



function build_remote_backup_settings($ligne){
	
	$sock=new sockets();
	$unix=new unix();
	$ligne2=unserialize(base64_decode($ligne["config"]));
	$OfflineImapWKDir=$sock->GET_INFO("OfflineImapWKDir");
	$OfflineImapBackupDir=$sock->GET_INFO("OfflineImapBackupDir");
	if($OfflineImapBackupDir==null){$OfflineImapBackupDir="%HOME%/mailbackups";}
	if($OfflineImapWKDir==null){$OfflineImapWKDir="/home/artica/mailbackups";}
		
	
	$offlimap=new offlineimap();
	$offlimap->LocalType="maildir";
	
	$offlimap->maildir_path=$OfflineImapWKDir;
	$offlimap->uid=$ligne["uid"];
	
	$offlimap->RemoteType="imap";
	$offlimap->remote_imap=$ligne["imapserv"];
	$offlimap->remote_password=$ligne2["password"];
	$offlimap->remote_username=$ligne["account"];
	$offlimap->remote_ssl=$ligne2["UseSSL"];
	
	$tmpfile=$unix->FILE_TEMP();
	$conf=$offlimap->buildconf();
	if($conf==null){
		buildlogs("Error while building configuration",__FUNCTION__,__LINE__);
		return;
	}
	@file_put_contents($tmpfile, $conf);
	return array($tmpfile,$offlimap->maildir_final);
}



function buildlogs($text,$function,$line){
	echo "$function:: $text in line $line\n";
	$GLOBALS["EV"][]="$function:: $text in line $line";
	
}
function LogsToMysqlMD5($md5){
	$final=base64_encode(serialize($GLOBALS["EV"]));
	unset($GLOBALS["EV"]);
	$sql="INSERT INTO mbxs_backup (zDate,content,zmd5) VALUES ('".date("Y-m-d H:i:s")."','$final','$md5')";
	$q=new mysql();
	if(!$q->TABLE_EXISTS("mbxs_backup", "artica_events")){$q->BuildTables();}
	$q->QUERY_SQL($sql,"artica_events");
}

