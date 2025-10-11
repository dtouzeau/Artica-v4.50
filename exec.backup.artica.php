<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
ini_set('memory_limit','1000M');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.backup.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/ressources/class.snapshots.blacklists.inc');
include_once(dirname(__FILE__).'/ressources/class.replicate.services.inc');
include_once(dirname(__FILE__).'/ressources/externals/class.aesCrypt.inc');
$GLOBALS["NOT_RESTORE_NETWORK"]=false;
$GLOBALS["SEND_META"]=false;
$GLOBALS["SNAPSHOT_NO_DELETE"]=false;
$GLOBALS["SNAPSHOT_NO_MYSQL"]=false;
$GLOBALS["NODELETE"]=false;
$GLOBALS["WIZARD_PROGRESS"]=0;
if(preg_match("#--nodelete#",$GLOBALS["COMMANDLINE"])){$GLOBALS["NODELETE"]=true;}
if(preg_match("#--meta-ping#",$GLOBALS["COMMANDLINE"])){$GLOBALS["SEND_META"]=true;}
if(preg_match("#--not-remove#",$GLOBALS["COMMANDLINE"])){$GLOBALS["SNAPSHOT_NO_DELETE"]=true;}
if(preg_match("#--nomysql#",$GLOBALS["COMMANDLINE"])){$GLOBALS["SNAPSHOT_NO_MYSQL"]=true;}
if(preg_match("#--wizard=([0-9]+)#",$GLOBALS["COMMANDLINE"],$re)){$GLOBALS["WIZARD_PROGRESS"]=intval($re[1]);}

if(isset($argv[1])){
    if($argv[1]=="--restore"){restore();exit();}
    if($argv[1]=="--restore-squid"){restore_squidlogs($argv[2]);exit();}
    if($argv[1]=="--snapshot"){snapshot();exit();}
    if($argv[1]=="--snapshot-id"){snapshot_restore_sql($argv[2]);exit();}
    if($argv[1]=="--snapshot-file"){snapshot_restore($argv[2]);exit();}
    if($argv[1]=="--snapshot-import"){snapshot_import();exit();}
    if($argv[1]=="--snapshot-retreive"){snapshot_retreive();exit();}
    if($argv[1]=="--snapshot-remove"){snapshot_remove($argv[2]);exit();}
    if($argv[1]=="--test-nas"){$GLOBALS["VERBOSE"]=true;TestNas(null);exit();}
    if($argv[1]=="--prepare-download"){snapshot_download($argv[2]);exit;}
    if($argv[1]=="--snapshot-uploaded"){snapshot_uploaded($argv[2]);exit;}
    if($argv[1]=="--set-schedule"){set_schedule();exit;}
    if($argv[1]=="--replicate"){replicate_services_after_restoring();exit;}



}
    backupevents("Depreciated....");
    exit();

function set_schedule():bool{
    $unix=new unix();
    $BackupArticaSnaps=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaSnaps"));

    if($BackupArticaSnaps==0){
        if(is_file("/etc/cron.d/backup-snaphosts")){
            @unlink("/etc/cron.d/backup-snaphosts");
            $unix->go_exec("/etc/init.d/cron reload");
            return true;
        }
    }

    $Array     = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaSnapsSched"));
    $unix->Popuplate_cron_make("backup-snaphosts",
        "{$Array["MIN"]} {$Array["HOUR"]} */{$Array["DAY"]} * *","/usr/sbin/artica-phpfpm-service  -create-snapshot");
    return true;

}

function backupevents($text):bool{
	$unix=new unix();
	$unix->events($text,"/var/log/artica-backup.log");
    $GLOBALS["EVENTS"][]=$text;
    snapshot_syslog($text);
	return true;
}

function TestNas($addedfolder="system-backup"):bool{
	$sock=new sockets();
	$unix=new unix();
	$hostname=$unix->hostname_g();
	$BackupArticaBackUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackUseNas"));
	if($BackupArticaBackUseNas==0){
		if($GLOBALS["VERBOSE"]){echo "WARNING: Backup To NAS feature is disabled ($BackupArticaBackUseNas). This feature is aborted...\n";}
		testNas_progress(100,"{success}");
		return false;
	}
	$BackupArticaBackNASIpaddr=$sock->GET_INFO("BackupArticaBackNASIpaddr");
	$BackupArticaBackNASFolder=$sock->GET_INFO("BackupArticaBackNASFolder");
	$BackupArticaBackNASUser=$sock->GET_INFO("BackupArticaBackNASUser");
	$BackupArticaBackNASPassword=$sock->GET_INFO("BackupArticaBackNASPassword");
	
	
	$mount=new mount("/var/log/artica-postfix/backup.debug");
	$mountPoint="/mnt/BackupArticaBackNAS";
	
	if($mount->ismounted($mountPoint)){ 
		testNas_progress(100,"{success}");
		return true;
    }
	
	
	testNas_progress(50,"{mounting}");
	if(!$mount->smb_mount($mountPoint,$BackupArticaBackNASIpaddr,$BackupArticaBackNASUser,$BackupArticaBackNASPassword,$BackupArticaBackNASFolder)){
		squid_admin_mysql(2, "Mounting //$BackupArticaBackNASIpaddr/$BackupArticaBackNASFolder failed",__FUNCTION__,__FILE__,__LINE__);
		testNas_progress(110,"{failed}");
        snapshot_syslog("Error: Mounting //$BackupArticaBackNASIpaddr/$BackupArticaBackNASFolder failed");
		return false;
				
	}
	
	if(!$mount->ismounted($mountPoint)){
        snapshot_syslog("Error: Mounting //$BackupArticaBackNASIpaddr/$BackupArticaBackNASFolder failed");
		testNas_progress(110,"{failed}");
		return false;
	}
	
	$t=time();
	@file_put_contents("$mountPoint/$t", "#");
	if(!is_file("$mountPoint/$t")){
		squid_admin_mysql(2, "$BackupArticaBackNASUser@$BackupArticaBackNASIpaddr/$BackupArticaBackNASFolder/* permission denied.\n",__FUNCTION__,__FILE__,__LINE__);
		$mount->umount($mountPoint);
        snapshot_syslog("Error: Permission denied on shared folder $BackupArticaBackNASFolder");
        echo "Permission denied on shared folder $BackupArticaBackNASFolder\n";
		testNas_progress(110,"{failed} Permission denied on $BackupArticaBackNASFolder");
		return false;
	}
	@unlink("$mountPoint/$t");	
	if($addedfolder<>null){
		@mkdir("$mountPoint/$hostname/$addedfolder",0755,true);
	}
    snapshot_syslog("Success: Mouting $BackupArticaBackNASUser@$BackupArticaBackNASIpaddr");
	testNas_progress(100,"{success}");
	return true;
}

function backup_ldap($Workdir){
	$unix=new unix();
	$slapcat=$unix->find_program("slapcat");
	$gzip=$unix->find_program("gzip");
	$nice=$unix->EXEC_NICE();
	if(!is_file($slapcat)){ squid_admin_mysql(2, "Error, slapcat, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }
	if(!is_file($gzip)){ squid_admin_mysql(2, "Error, gzip, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }	
	$cmd=trim("$nice $slapcat|$gzip >$Workdir/ldap_database.gz 2>&1");
	exec($cmd,$results);
	
	if($GLOBALS["VERBOSE"]){echo $cmd."\n".@implode("\n", $results)."\n";}
	
	$size=filesize("$Workdir/ldap_database.gz");
	$size=$size/1024;
	$size=round($size/1024,2);
	squid_admin_mysql(2, "ldap_database.gz ({$size}M)\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__);
	
	$SLAPD_CONF=$unix->SLAPD_CONF_PATH();
	$results=array();
	$cmd=trim("$nice $gzip -c $SLAPD_CONF > /$Workdir/ldap.conf.gz 2>&1");
	exec($cmd,$results);
	if($GLOBALS["VERBOSE"]){echo $cmd."\n".@implode("\n", $results)."\n";}
	
	$size=filesize("$Workdir/ldap.conf.gz");
	$GLOBALS["ARRAY_CONTENT"]["ldap.conf.gz"]=$size;
	$size=$size/1024;
	$size=round($size,2);
	squid_admin_mysql(2, "ldap.conf.gz ({$size}K)\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__);
}

function backup_nginx($BaseWorkDir){
	@chdir("/etc/nginx");
	$unix=new unix();
	$tar=$unix->find_program("tar");
	system("cd /etc/nginx");
	@mkdir("$BaseWorkDir/nginx",0755,true);
	shell_exec("$tar czf $BaseWorkDir/nginx/tarball.tgz *");
	
}




function backup_artica_settings($BaseWorkDir){
	$BLACKLIST=artica_settings_blacklists();
	
	if (!$handle = opendir("/etc/artica-postfix/settings/Daemons")) {echo "Failed open /etc/artica-postfix/settings/Daemons\n";return;}
	@mkdir("$BaseWorkDir/Daemons",0755,true);
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="/etc/artica-postfix/settings/Daemons/$filename";
		if(preg_match("#-[0-9]+$#", $filename)){
			@unlink($targetFile);
			continue;
		}
		if(preg_match("#\{#", $filename)){
			@unlink($targetFile);
			continue;
		}
		
		
		if(is_dir($targetFile)){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		if(is_file("$BaseWorkDir/Daemons/$filename")){@unlink("$BaseWorkDir/Daemons/$filename");}
		if($GLOBALS["VERBOSE"]){echo "$targetFile -> $BaseWorkDir/Daemons/$filename\n";}
		copy($targetFile, "$BaseWorkDir/Daemons/$filename");
		$GLOBALS["ARRAY_CONTENT"]["Daemons/$filename"]=@filesize("$BaseWorkDir/Daemons/$filename");
	}
	echo "Backup /etc/artica-postfix/settings/Daemons done\n";
	squid_admin_mysql(2, "settings/Daemons done\n",__FUNCTION__,__FILE__,__LINE__);
	
}

function snapshot_retreive(){
	$unix=new unix();
	progress(5,"{receive_snapshot}...");
	$CMDLINE_FINALE=null;
	$SnapShotRemote=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotRemote")));
	$REMOTE_SERVER=$SnapShotRemote["REMOTE_SERVER"];
	$LISTEN_PORT=$SnapShotRemote["LISTEN_PORT"];
	$REMOTE_ADMIN=$SnapShotRemote["REMOTE_ADMIN"];
	$REMOTE_PASSWORD=$SnapShotRemote["REMOTE_PASSWORD"];
	if(isset($SnapShotRemote["CLUSTER_COMMAND"]));
	$php=$unix->LOCATE_PHP5_BIN();
	
	$CMDLINE_FINALE=$SnapShotRemote["CLUSTER_COMMAND"];
	
	$MAIN_URI="https://$REMOTE_SERVER:$LISTEN_PORT";
	echo "url: $MAIN_URI using $REMOTE_ADMIN/$REMOTE_PASSWORD account\n";
	$creds=base64_encode(serialize(array("ADM"=>$REMOTE_ADMIN,"PASS"=>md5($REMOTE_PASSWORD))));
	$MAIN_URL="$MAIN_URI/listen.snapshots.php?creds=$creds";
	$curl=new ccurl("$MAIN_URL&hello=yes");
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		echo $curl->error."\n";
		progress(110,"{protocol_error}: $curl->error...");
		return;
	}
	if(preg_match("#<ERROR>(.*?)</ERROR>#is", $curl->data,$re)){
		echo $re[1]."\n";
		progress(110,"$REMOTE_SERVER: {$re[1]}");
		return;
	}
	
	if(!preg_match("#<ANSWER>HELLO</ANSWER>#is",  $curl->data,$re)){
		echo $re[1]."\n";
		progress(110,"{protocol_error}");
		return;
	}
	
	progress(10,"{prepare_snapshot}...");
	$curl=new ccurl("$MAIN_URL&prepare-snapshot=yes");
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		echo $MAIN_URL."\n";
		echo $curl->error."\n";
		progress(110,"{protocol_error}: $curl->error...");
		return;
	}
	sleep(1);
	for($i=0;$i<40;$i++){
		$curl=new ccurl("$MAIN_URL&status-snapshot=yes");
		$curl->NoHTTP_POST=true;
		if(!$curl->get()){
			progress(110,"{protocol_error}: $curl->error...");
			return;
		}
		if(!preg_match("#<ANSWER>(.+?)</ANSWER>#is",  $curl->data,$re)){
			echo $re[1]."\n";
			progress(110,"{protocol_error}");
			return;
		}
		
		$array=unserialize(base64_decode($re[1]));
		$prc=intval($array["POURC"]);
		progress(15,"{prepare_snapshot}:{$array["TEXT"]}...{$prc}%");
		if($prc>100){
			squid_admin_mysql(0, "Failed building snapshot.tar.gz ({$array["TEXT"]}) from $REMOTE_SERVER", null,__FILE__,__LINE__);
			progress(110,"{prepare_snapshot} {failed}");
			return;
		}
		if($prc==100){break;}
		sleep(1);
		
	}
	$curl=new ccurl("https://$REMOTE_SERVER:$LISTEN_PORT/ressources/logs/web/snapshot.tar.gz");
	progress(15,"{dowloading}...snapshot.tar.gz");
	$targetpath=$unix->FILE_TEMP();
	if(!$curl->GetFile($targetpath)){
		squid_admin_mysql(0, "Failed retreive snapshot.tar.gz ($curl->error) from $REMOTE_SERVER", null,__FILE__,__LINE__);
		progress(110,"{dowloading} {failed2}: $curl->error...");
		return;
	}
	
	$size=@filesize($targetpath);
	if($size<100){
		squid_admin_mysql(0, "Failed retreive snapshot.tar.gz ($size bytes) from $REMOTE_SERVER", null,__FILE__,__LINE__);
		progress(110,"{dowloading} {failed2}: $size < 100bytes...");
		return;
	}
	
	if(!$GLOBALS["NODELETE"]){
		progress(15,"{cleaning}...snapshot.tar.gz");
		$curl=new ccurl("$MAIN_URL&clean-snapshot=yes");
		$curl->NoHTTP_POST=true;
		$curl->get();
	}
	snapshot_restore($targetpath);
	$CMDLINE_FINALE=$CMDLINE_FINALE. " --cluster";
	$CMDLINE_FINALE_LOG=str_replace(dirname(__FILE__),"",$CMDLINE_FINALE);
	squid_admin_mysql(2, "Cluster: Execute $CMDLINE_FINALE_LOG after restoring snapshot", null,__FILE__,__LINE__);
	if($CMDLINE_FINALE<>null){shell_exec("$php $CMDLINE_FINALE");}
	
	
	squid_admin_mysql(1, "Success replicate snapshot from $REMOTE_SERVER", null,__FILE__,__LINE__);
	if(!$GLOBALS["NODELETE"]){
		@mkdir("/home/artica/snapshots",0755,true);
		$zmd5=md5_file($targetpath);
		$DiskFile="/home/artica/snapshots/$zmd5.tar.gz";
		if(!@copy($targetpath, $DiskFile)){
			@unlink($targetpath);
			echo "$targetpath -> $DiskFile failed\n";
			progress(110,"{failed}");
		}
		
		@unlink($targetpath);

	}
	
	
	
}


function snapshot_restore_sql($ID){
	
	$unix=new unix();
	$q=new mysql();
	$sock=new sockets();
	$sock->SET_INFO("BackupArticaRestoreNetwork", 1);
	$sql="SELECT zmd5,`snap` FROM `snapshots` WHERE ID='$ID'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_snapshots"));
	$zmd5=$ligne["zmd5"];
	$DiskFile="/home/artica/snapshots/$zmd5.tar.gz";
	$FILE_TEMP=$unix->FILE_TEMP().".tar.gz";
	if(!is_file($DiskFile)){
		@file_put_contents($FILE_TEMP, $ligne["snap"]);
	}else{
		@unlink($FILE_TEMP);
		@copy($DiskFile, $FILE_TEMP);
	}
	snapshot_restore($FILE_TEMP);
}



function snapshot_import_progress($purc,$text){
	backupevents("$purc) $text");
	$array=array("POURC"=>$purc,"TEXT"=>$text);
	@file_put_contents(PROGRESS_DIR."/snapshot.upload.progress", serialize($array));
	@chmod(PROGRESS_DIR."/snapshot.upload.progress",0755);
}
function testNas_progress($purc,$text){
	backupevents("$purc) $text");
	$array=array("POURC"=>$purc,"TEXT"=>$text);
	@file_put_contents(PROGRESS_DIR."/backup.test.progress", serialize($array));
	@chmod(PROGRESS_DIR."/backup.test.progress",0755);
}

function snapshot_uploaded($filename){
	if(preg_match("#^(.+?)\.(gz|aes)$#", $filename,$re)){$FirstPart=$re[1];}
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$UPLOAD_PATH="/usr/share/artica-postfix/ressources/conf/upload";
	$path="$UPLOAD_PATH/$filename";
	if(!is_file($path)){
		echo "(374) \"$path\" no such file\n";
		progress_uploaded(110,"{failed}");
		return false;
	}
	echo "(378) \"$path\"\n";
	$size=filesize($path);
	$pathExe=$unix->shellEscapeChars($path);
	echo "$filename: Size = $size (".FormatBytes($size/1024,true).")\n";
	
	if(preg_match("#\.aes$#", $filename)){
		progress_uploaded(20,"{decrypt} $filename....");
        if(!is_file("/usr/bin/ccdecrypt")){
            echo "/usr/bin/ccdecrypt No such file...\n";
            progress_uploaded(110,"{decrypt} $path {failed}....");
            @unlink($path);
            return false;
        }
        $targetDecryptedFile=str_replace(".aes", "", $path);
        $targetFileName=basename($targetDecryptedFile);
        $final_file="$UPLOAD_PATH/$targetFileName";
        $final_file=str_replace("(","",$final_file);
        $final_file=str_replace(")","",$final_file);

		$password=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapshotUploadedPassword"));

        echo "Decrypt $filename into $targetDecryptedFile\n";
        $tmppass=$unix->FILE_TEMP();
        @file_put_contents($tmppass,"$password\n$password\n");
        shell_exec("/usr/bin/ccdecrypt --keyfile $tmppass --suffix .aes ".$unix->shellEscapeChars($path));
        @unlink($tmppass);
        if(!is_file($targetDecryptedFile)){
            echo "$targetDecryptedFile No such file...\n";
            progress_uploaded(110,"{decrypt} $filename {failed}....");
            @unlink($path);
            return false;
        }

		echo "Copy $targetDecryptedFile into $final_file\n";
        if(is_file($final_file)){ @unlink($final_file); }
        @copy($targetFileName,$final_file);
        if(!is_file($final_file)){
            echo "$final_file copy failed...\n";
            progress_uploaded(110,"{decrypt} $filename {failed}....");
            @unlink($path);
            return false;
        }
		@unlink($path);
		$path=$final_file;
		$pathExe=$unix->shellEscapeChars($path);
	}
	progress_uploaded(30,"{checking} {container}....");
	
	
	$tar=$unix->find_program("tar");
	if(!$unix->VerifyTar($path)){
		progress_uploaded(110,"{failed} {corrupted} {container}....");
		exit();
		shell_exec("$rm -f $UPLOAD_PATH/*");
		return false;
	}
	$TEMP_DIR=$unix->TEMP_DIR()."/".time();
	@mkdir("$TEMP_DIR",0755,true);
	if(!is_dir($TEMP_DIR)){
		echo "Could not create $TEMP_DIR\n";
		shell_exec("$rm -f $UPLOAD_PATH/*");
		return false;
	}
	
	
	echo "Uncompress into $TEMP_DIR\n";
	
	echo "$tar xzvf $pathExe -C $TEMP_DIR/\n";
	system("$tar xzf $pathExe -C  $TEMP_DIR/");
	$find=$unix->find_program("find");
	echo "Listing $TEMP_DIR...\n";
	system("$find $TEMP_DIR/*");
	@unlink($path);
	
	if(!is_file("$TEMP_DIR/ARRAY_CONTENT")){
		echo "$TEMP_DIR/ARRAY_CONTENT no such file\n";
		progress_uploaded(110,"{checking} {container} {failed}");
		shell_exec("$rm -rf $TEMP_DIR");
		shell_exec("$rm -f $UPLOAD_PATH/*");
		return;
	}
	progress_uploaded(30,"{compressing} {container}....");
	$temp=$unix->FILE_TEMP();
	chdir($TEMP_DIR);
	progress_uploaded(60,"{compressing} {to} $temp");
	system("cd  $TEMP_DIR");
	system("$tar czf $temp *");
	system("cd /root");
	chdir("/root");

    $FirstPart=md5_file($temp);
	$SnapShotsStorageDirectory=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsStorageDirectory"));
	if($SnapShotsStorageDirectory==null){$SnapShotsStorageDirectory="/home/artica/snapshots";}
	if(!is_dir($SnapShotsStorageDirectory)){@mkdir($SnapShotsStorageDirectory,0755,true);}
	
	
	$TargetFileName="$FirstPart.tar.gz";
	$SnapShotsPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsPassword"));
	
	if($SnapShotsPassword<>null){
		progress_uploaded(65,"{encrypt} $TargetFileName");
		$crypt = new AESCrypt($SnapShotsPassword);
		$date = date("Y-m-d");
		$time = date("H:i:s");
		$crypt->setExtText(array( $crypt::CREATED_DATE=>$date, $crypt::CREATED_TIME=>$time ) );
		$data=file_get_contents($temp);
		@unlink($temp);
		file_put_contents($temp, $crypt->encrypt( $data) );
		$TargetFileName="$FirstPart.aes";
	}else{
		echo "No password set, leave the container uncrypted...\n";
	}
	
	if(is_file("$SnapShotsStorageDirectory/$TargetFileName")){@unlink("$SnapShotsStorageDirectory/$TargetFileName");}
	if(!@copy($temp, "$SnapShotsStorageDirectory/$TargetFileName")){
		@unlink($temp);
		progress_uploaded(110,"{failed} to copy $temp");
		shell_exec("rm -f $UPLOAD_PATH/*");
		return;
	}
	
	@unlink($temp);
	$BackupArticaBackUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackUseNas"));
	if($BackupArticaBackUseNas==1){
		if(!snapshot_to_nas($TargetFileName)){
			progress_uploaded(110,"N.A.S {failed}");
			@unlink("$SnapShotsStorageDirectory/$TargetFileName");
			return false;
		}
	}

    system("/usr/share/artica-postfix/bin/articarest -scan-snapshot");
	progress_uploaded(100,"{success}");
	shell_exec("rm -f $UPLOAD_PATH/*");
	
	
}


function snapshot_import(){
	$sock=new sockets();
	ini_set('memory_limit','1000M');
	$ARRAY=unserialize($sock->GET_INFO("SnapshotUpload"));
	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/snapshot.upload.progress";
	$xdate=$ARRAY["xdate"];
	$size=$ARRAY["size"];
	$zmd5=$ARRAY["zmd5"];
	$filePath=$ARRAY["filepath"];
	$DiskFile="/home/artica/snapshots/$zmd5.tar.gz";
	echo "Date: $xdate\n";
	echo "Size: $size\n";
	echo "MD5.: $zmd5\n";
	echo "File: $filePath\n";
	echo "Disk file: $DiskFile\n";
	
	
	if(!is_file($filePath)){
		echo "No such file!\n";
		snapshot_import_progress(110,"{failed}: ".basename($filePath));
		return;
	}
	
	snapshot_import_progress(15,"{importing}: ".basename($filePath));
	
	@mkdir("/home/artica/snapshots",0755,true);
	if(is_file($DiskFile)){@unlink($DiskFile);}
	if(!@copy($filePath, $DiskFile)){
		@unlink($filePath);
		echo "$filePath -> $DiskFile failed\n";
		snapshot_import_progress(110,"{failed}");
	}
	
	
	
	@unlink($filePath);
	
	
	$q=new mysql();
	
	for($i=0;$i<6;$i++){
		snapshot_import_progress(50,"{importing}: ".basename($filePath)." $i/5");
		$q->QUERY_SQL("INSERT IGNORE INTO `snapshots` (zDate,snap,size,content,zmd5)
				VALUES ('$xdate','','$size','','$zmd5')","artica_snapshots");
		
		if($q->ok){
			snapshot_import_progress(100,"{success}: ".basename($filePath));
			return;
		}
		
		echo $q->mysql_error."\n";
		sleep(3);		
	}
	
	snapshot_import_progress(110,"{failed}: ".basename($filePath));
	
}


function snapshot_restore($tarball){
	backupevents("Restoring $tarball wizard={$GLOBALS["WIZARD_PROGRESS"]}");
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$BaseWorkDir=$unix->TEMP_DIR()."/".time();
	$tar=$unix->find_program("tar");
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$php=$unix->LOCATE_PHP5_BIN();
	if($GLOBALS["SEND_META"]){$GLOBALS["NOT_RESTORE_NETWORK"]=true;}
	
	$SnapShotsStorageDirectory=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsStorageDirectory"));
	if($SnapShotsStorageDirectory==null){$SnapShotsStorageDirectory="/home/artica/snapshots";}
	if(!is_dir($SnapShotsStorageDirectory)){@mkdir($SnapShotsStorageDirectory,0755,true);}
	$BackupArticaBackUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackUseNas"));
	$SnapShotsPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsPassword"));
    backupevents("Container: $tarball use NAS=$BackupArticaBackUseNas");
	$FullPath="$SnapShotsStorageDirectory/$tarball";

    if($GLOBALS["WIZARD_PROGRESS"]>0){$BackupArticaBackUseNas=0;}
	
	if($BackupArticaBackUseNas==1){
		progress(30,"{mounting}");
		$mountPoint="/mnt/BackupArticaBackNAS";
		if(!mount_nas()){
            backupevents("Failed to mount resource.");
			progress(110,"{failed} {mount}");
			return false;
		}
		$hostname=$unix->hostname_g();
		$SharedWorkdir="$mountPoint/$hostname/snapshots";
		$FullPath="$SharedWorkdir/$tarball";
	}

    backupevents("Restoring $FullPath");
	progress(10,"{restoring} $tarball");
	echo $FullPath."\n";
	if(!is_file($FullPath)){
		progress(110,"{failed}");
        backupevents("$FullPath no such file");
        return false;
	}
	@mkdir($BaseWorkDir,0755,true);
	
	
	if(preg_match("#\.aes$#", $tarball)){
		progress(10,"{decrypt} $tarball");
		$crypt = new AESCrypt($SnapShotsPassword);
		echo "Decrypt $FullPath into $BaseWorkDir/tarball.tar.gz\n";
		$data=@file_get_contents($FullPath);
		@file_put_contents("$BaseWorkDir/tarball.tar.gz", $crypt->decrypt($data));
		$FullPath="$BaseWorkDir/tarball.tar.gz";
	}
	progress(15,"{extracting}");
	echo "$tar xpf $FullPath -C $BaseWorkDir/\n";
	system("$tar xpf $FullPath -C $BaseWorkDir/");

    backupevents("Restoring SQLite $BaseWorkDir)");
    restore_artica_sqlite($BaseWorkDir);
	
	if($BackupArticaBackUseNas==1){$mount=new mount();if($mount->ismounted($mountPoint)){ $mount->umount($mountPoint);}}
		
	if(is_file("$BaseWorkDir/ARRAY_CONTENT")){$GLOBALS["ARRAY_CONTENT"]=unserialize(@file_get_contents("$BaseWorkDir/ARRAY_CONTENT"));}
	
	if(is_file("$BaseWorkDir/TRUNCATE_TABLES")){
		$TRUNCATE_TABLES=unserialize(@file_get_contents("$BaseWorkDir/TRUNCATE_TABLES"));
		if(is_array($TRUNCATE_TABLES)) {
            @unlink("$BaseWorkDir/TRUNCATE_TABLES");
            foreach ($TRUNCATE_TABLES as $database => $tables) {
                progress(20, "{cleaning} $database");
                backupevents("Cleaning database $database");
                foreach ($tables as $tablename => $none) {
                    if ($database == "artica_backup") {
                        echo "Cleaning $tablename\n";
                        $q = new mysql();
                        $q->QUERY_SQL("TRUNCATE TABLE `$tablename`", "artica_backup");
                        continue;
                    }
                    if ($database == "squidlogs") {
                        echo "Cleaning $tablename\n";
                        $q = new mysql_squid_builder();
                        $q->QUERY_SQL("TRUNCATE TABLE `$tablename`");
                        continue;
                    }
                }
            }
        }
	}else{
        backupevents("TRUNCATE_TABLES no such file");
		echo "$BaseWorkDir/TRUNCATE_TABLES no such file\n";
	}
	
	progress(30,"{restoring} squidlogs");
	echo "-> restore_squidlogs....\n";
	restore_squidlogs($BaseWorkDir);
    backupevents("Restoring Artica Backup");
	progress(40,"{restoring} artica_backup");
	
	$ARRAY=artica_backup_blacklists();
	restore_artica_backup($BaseWorkDir,$ARRAY["artica_backup_blacklists"]);
	
	progress(45,"{restoring} PostGreSQL");
	restore_postgresql($BaseWorkDir);
	
	progress(50,"{restoring} Artica settings");
	restore_artica_settings($BaseWorkDir);
	progress(60,"{restoring} Open LDAP");
	Restore_ldap($BaseWorkDir);
	progress(70,"{restoring} Reverse Proxy");
	restore_nginx($BaseWorkDir);
	progress(75,"{restoring} PowerDNS");
	restore_powerdns($BaseWorkDir);
	progress(76,"{cleaning}...");
	shell_exec("$rm -rf $BaseWorkDir");
	
	
	replicate_services_after_restoring();	
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	
	if($SQUIDEnable==1){
		if(!is_file("/etc/init.d/squid")){ system("/usr/sbin/artica-phpfpm-service -install-proxy"); }
	}else{
		if(is_file("/etc/init.d/squid")){ system("/usr/sbin/artica-phpfpm-service -uninstall-proxy"); }
	}
	
	if($SQUIDEnable==1){
		if(is_file($squidbin)){
			progress(99,"{reconfigure_server}, {please_wait}...");
			system("$php /usr/share/artica-postfix/exec.squid.global.access.php"); 
		}
	}
    progress(99,"{synchronize}...");
	system("$php /usr/share/artica-postfix/exec.synchronize-settings.php");
	system("$php /usr/share/artica-postfix/exec.status.php --process1");
	
	progress(100,"{success}...");
    squid_admin_mysql(2, "Success restoring snapshot $tarball", null,__FILE__,__LINE__);
    $infos["FILENAME"]=$tarball;
    $infos["EVENTS"]= $GLOBALS["EVENTS"];
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SnapShotRestaured",serialize($infos));
    system("/usr/share/artica-postfix/bin/articarest -scan-snapshot");
	return true;
}

function build_syslog():bool{
    $md5 = null;
    $tfile = "/etc/rsyslog.d/00-snapshot-backup.conf";
    if (is_file($tfile)) {$md5 = md5_file($tfile);}
    $h = array();
    $h[] = "if  (\$programname  == 'snapshots') then {";
    $h[] = BuildRemoteSyslogs('snapshots-backup');
    $h[] = "\t-/var/log/snapshots-backup.log";
    $h[] = "\t& stop";
    $h[] = "}";
    $h[] = "";
    @file_put_contents($tfile, @implode("\n", $h));
    $md52 = md5_file($tfile);
    if ($md52 == $md5) {return true;}
    shell_exec("/etc/init.d/rsyslog restart");
    return true;
}
function snapshot_syslog($text):bool{
    if(preg_match("#password=(.*?)(\s|$)#",$text,$re)){
        $text=str_replace($re[1],"****");
    }
    if(!function_exists("syslog")){return false;}
    openlog("snapshots", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}

function snapshot(){
    system("/usr/sbin/artica-phpfpm-service -create-snapshot");
}

function snapshot_to_nas($TargetFileName){
	$unix=new unix();
	$SnapShotsStorageDirectory=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsStorageDirectory"));
	if($SnapShotsStorageDirectory==null){$SnapShotsStorageDirectory="/home/artica/snapshots";}
	if(!is_dir($SnapShotsStorageDirectory)){@mkdir($SnapShotsStorageDirectory,0755,true);}
	$mountPoint="/mnt/BackupArticaBackNAS";
	progress(66,"{mounting}");
	$mv=$unix->find_program("mv");
	$mount=new mount();
	if($mount->ismounted($mountPoint)){ $mount->umount($mountPoint);}
	if(!TestNas("snapshots")){
        snapshot_syslog("Error: Failed to mount NAS (".__LINE__.")");
		squid_admin_mysql(1, "Mounting NAS filesystem report false",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	$hostname=$unix->hostname_g();
	$SharedWorkdir="$mountPoint/$hostname/snapshots";
	if(is_file("$SharedWorkdir/$TargetFileName")){@unlink("$SharedWorkdir/$TargetFileName");}

	progress(67,"{copy}");
	if(!@copy("$SnapShotsStorageDirectory/$TargetFileName", "$SharedWorkdir/$TargetFileName")){
		echo "Failed to copy $SnapShotsStorageDirectory/$TargetFileName to $SharedWorkdir/$TargetFileName\n";
		@unlink("$SharedWorkdir/$TargetFileName");
		squid_admin_mysql(1, "$SharedWorkdir/$TargetFileName backup failed",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
    snapshot_syslog("moving  $SnapShotsStorageDirectory/* $SharedWorkdir");
	shell_exec("$mv -f -n $SnapShotsStorageDirectory/* $SharedWorkdir/");
	progress(68,"{umount}");
	$mount->umount($mountPoint);
	return true;

}

function snapshot_download($filename){
	$unix=new unix();
	$SnapShotsStorageDirectory=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsStorageDirectory"));
	if($SnapShotsStorageDirectory==null){$SnapShotsStorageDirectory="/home/artica/snapshots";}
	$BackupArticaBackUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackUseNas"));
	
	progress(20,"{mounting}: $filename");
	
	if($BackupArticaBackUseNas==1){
		progress(30,"{mounting}");
		$mountPoint="/mnt/BackupArticaBackNAS";
		if(!mount_nas()){return false;}
		$hostname=$unix->hostname_g();
		$SharedWorkdir="$mountPoint/$hostname/snapshots";
		$SnapShotsStorageDirectory=$SharedWorkdir;
	}
	
	if(!is_file("$SnapShotsStorageDirectory/$filename")){
		echo "$SnapShotsStorageDirectory/$filename no such file\n";
		progress(110,"$filename {failed}");
		if($BackupArticaBackUseNas==1){$mount=new mount();if($mount->ismounted($mountPoint)){ $mount->umount($mountPoint);}}
		return;
	}
	
	progress(50,"$filename {copy}");
	$targetf="/usr/share/artica-postfix/ressources/web/logs/$filename";
	$Sourcef="$SnapShotsStorageDirectory/$filename";
	$md5source=md5_file($Sourcef);
	
	
	
	if(is_file($targetf)){@unlink($targetf);}
	if(!@copy("$SnapShotsStorageDirectory/$filename", $targetf)){
		echo "$SnapShotsStorageDirectory/$filename copy failed\n";
		$errors= error_get_last();
		echo "COPY ERROR: ".$errors['type'];
    	echo "\n".$errors['message'];
    	echo "\nTarget: $targetf";
		progress(110,"$filename {copy} {failed}");
		if($BackupArticaBackUseNas==1){$mount=new mount();if($mount->ismounted($mountPoint)){ $mount->umount($mountPoint);}}
		return false;
	}
	
	$md5Dest=md5_file($targetf);
	if($md5source<>$md5Dest){
		echo "Source.....: $md5source\n";
		echo "Destination: $md5Dest\n";
		echo "----------------------------------\n";
		echo "Match failed\n";
		progress(110,"$filename {corrupted}");
		@unlink($md5Dest);
		return;
	}
	
	@chmod($targetf,0755);
    echo "[OK]: $targetf\n";
	progress(100,"$filename {copy} {success}");
	if($BackupArticaBackUseNas==1){$mount=new mount();if($mount->ismounted($mountPoint)){ $mount->umount($mountPoint);}}
}

function mount_nas(){
	$BackupArticaBackLocalFolder=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackLocalFolder"));
	$mountPoint="/mnt/BackupArticaBackNAS";
	$mount=new mount();
	if($mount->ismounted($mountPoint)){ $mount->umount($mountPoint);}
	if(!TestNas("snapshots")){return false;}
	return true;
}



function snapshot_remove($filename){
	$unix=new unix();
	$BackupArticaBackUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackUseNas"));
	$SnapShotsStorageDirectory=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsStorageDirectory"));
	if($SnapShotsStorageDirectory==null){$SnapShotsStorageDirectory="/home/artica/snapshots";}
	progress(15,"{removing} $filename");
	if($BackupArticaBackUseNas==1){
		progress(30,"{mounting}");
		$mountPoint="/mnt/BackupArticaBackNAS";
		if(!mount_nas()){
			progress(110,"{failed}");
			return false;}
		$hostname=$unix->hostname_g();
		$SharedWorkdir="$mountPoint/$hostname/snapshots";
		$SnapShotsStorageDirectory=$SharedWorkdir;
	}
	
	if(!is_file("$SnapShotsStorageDirectory/$filename")){
		progress(110,"{removing} $filename {failed} No FILE");
		echo "$SnapShotsStorageDirectory/$filename no such file\n";
		if($BackupArticaBackUseNas==1){$mount=new mount();if($mount->ismounted($mountPoint)){ $mount->umount($mountPoint);}}
		return false;
	}
		
	progress(90,"{removing} $filename");
    $md5=md5_file("$SnapShotsStorageDirectory/$filename");
    if($md5<>null){
        $q=new lib_sqlite("/home/artica/SQLITE/snapshots.db");
        $q->QUERY_SQL("DELETE FROM snapshots WHERE zmd5='$md5'");
    }
	@unlink("$SnapShotsStorageDirectory/$filename"); 
	
	progress(95,"{scanning} $SnapShotsStorageDirectory");
    system("/usr/share/artica-postfix/bin/articarest -scan-snapshot");
	progress(100,"{scanning} {success}");
    return true;
}
function backup_mysql_artica_backup($BaseWorkDir){
	$unix=new unix();
	$password=null;
	$mysqldump=$unix->find_program("mysqldump");
	$gzip=$unix->find_program("gzip");
	$sock=new sockets();
	$BackupArticaBackAllDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackAllDB"));
	
	if(!is_file($gzip)){ squid_admin_mysql(2, "Error, gzip, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }
	if(!is_file($mysqldump)){ squid_admin_mysql(2, "Error, mysqldump, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }
	
	$nice=$unix->EXEC_NICE();
	$q=new mysql();
	$LIST_TABLES_ARTICA_BACKUP=$q->LIST_TABLES_ARTICA_BACKUP();
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	
	$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password artica_backup");
	@mkdir("$BaseWorkDir/artica_backup",0755,true);
	$c=0;

    foreach ($LIST_TABLES_ARTICA_BACKUP as $table_name=>$val){
		if($q->COUNT_ROWS($table_name,"artica_backup")==0){continue;}
		$cmd="$prefix $table_name | $gzip > $BaseWorkDir/artica_backup/$table_name.gz";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
		$c++;
			
	}
	
	
	
	$LIST_TABLES_ARTICA_SQUIDLOGS=$q->LIST_TABLES_ARTICA_SQUIDLOGS();
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	
	$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password squidlogs");
	@mkdir("$BaseWorkDir/squidlogs",0755,true);
	$BLACKLIST["tables_day"]=true;
	$BLACKLIST["quotachecked"]=true;
	$BLACKLIST["cached_total"]=true;
	$BLACKLIST["mimedefang_parts"]=true;
	$BLACKLIST["mimedefang_stats"]=true;
	
	
	$q=new mysql_squid_builder();
    foreach ($LIST_TABLES_ARTICA_SQUIDLOGS as $table_name=>$val){
		if(preg_match("#[0-9]+#", $table_name)){continue;}
		if(preg_match("#[0-9]+#", $table_name)){continue;}
		if(preg_match("#^www_#", $table_name)){continue;}
		if(preg_match("#^visited_#", $table_name)){continue;}
		if(preg_match("#^youtube_#", $table_name)){continue;}
		if(isset($BLACKLIST[$table_name])){continue;}
		if($q->COUNT_ROWS($table_name)==0){continue;}
		$cmd="$prefix $table_name | $gzip > $BaseWorkDir/squidlogs/$table_name.gz";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
		$c++;
	}
	
	$LIST_TABLES_ARTICA_OCSWEB=$q->LIST_TABLES_ARTICA_OCSWEB();
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	
	$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password ocsweb");
	@mkdir("$BaseWorkDir/ocsweb",0755,true);
	
	foreach ($LIST_TABLES_ARTICA_OCSWEB as $table_name=>$val){
		$cmd="$prefix $table_name | $gzip > $BaseWorkDir/ocsweb/$table_name.gz";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
		$c++;
	}

	if($BackupArticaBackAllDB==1) {
        $DATABASE_LIST = $q->DATABASE_LIST();
        unset($DATABASE_LIST["squidlogs"]);
        unset($DATABASE_LIST["ocsweb"]);
        unset($DATABASE_LIST["artica_backup"]);
        unset($DATABASE_LIST["artica_events"]);
        unset($DATABASE_LIST["mysql"]);
        foreach ($DATABASE_LIST as $database=>$val){
            $prefix = trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password $database | $gzip > $BaseWorkDir/DB_$database.gz");
            if ($GLOBALS["VERBOSE"]) {
                echo "$prefix\n";
            }
            shell_exec($prefix);
        }
    }
	squid_admin_mysql(2, "Artica Databases $c tables done\n",__FUNCTION__,__FILE__,__LINE__);
}

function backup_mysql_powerdns($BaseWorkDir){
	$unix=new unix();
	$sock=new sockets();
	$password=null;
	$mysqldump=$unix->find_program("mysqldump");
	$gzip=$unix->find_program("gzip");
	
	
	if(!is_file($gzip)){ squid_admin_mysql(2, "Error, gzip, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }
	if(!is_file($mysqldump)){ squid_admin_mysql(2, "Error, mysqldump, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }
	
	if(!$unix->is_socket("/var/run/mysqld/mysqld.sock")){
		squid_admin_mysql(2, "Error,/var/run/mysqld/mysqld.sock no such socket",__FUNCTION__,__FILE__,__LINE__); 
		return false; 
	}
	
	$q=new mysql();
	if(!$q->DATABASE_EXISTS("powerdns")){
		backupevents("Database PowerDNS doesn't exists...");
		return true;}
	$nice=$unix->EXEC_NICE();	
	
	
	$LIST_TABLES_POWERDNS=$q->LIST_TABLES_POWERDNS();
	backupevents(count($LIST_TABLES_POWERDNS)." tables to backup...");
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	
	$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password powerdns");
	@mkdir("$BaseWorkDir/powerdns",0755,true);
	
	$c=0;
    foreach ($LIST_TABLES_POWERDNS as $table_name=>$val){
		$cmd="$prefix $table_name | $gzip > $BaseWorkDir/powerdns/$table_name.gz";
		backupevents("$cmd");
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
		$c++;
	}

	squid_admin_mysql(2, "PowerDNS Databases $c tables done\n",__FUNCTION__,__FILE__,__LINE__);	
}

function progress($purc,$text):bool{
    snapshot_syslog("$purc) $text");
    backupevents("$purc% - $text");
    $unix=new unix();
    $unix->framework_progress($purc,$text,"backup.artica.progress");
    return true;
}
function progress_uploaded($purc,$text):bool{
    snapshot_syslog("$purc) $text");
    $unix=new unix();
    $unix->framework_progress($purc,$text,"backup.upload.progress");
    return true;
}
function restore_TestNas(){
	$unix=new unix();
	$sock=new sockets();
	$BackupArticaRestoreNASIpaddr=$sock->GET_INFO("BackupArticaRestoreNASIpaddr");
	$BackupArticaRestoreNASFolder=$sock->GET_INFO("BackupArticaRestoreNASFolder");
	$BackupArticaRestoreNASUser=$sock->GET_INFO("BackupArticaRestoreNASUser");
	$BackupArticaRestoreNASPassword=$sock->GET_INFO("BackupArticaRestoreNASPassword");
	$BackupArticaRestoreNASFolderSource=$sock->GET_INFO("BackupArticaRestoreNASFolderSource");
	$BackupArticaRestoreNetwork=$sock->GET_INFO("BackupArticaRestoreNetwork");


	$mount=new mount("/var/log/artica-postfix/backup.debug");
	$mountPoint="/mnt/BackupArticaRestoreNAS";

	if($mount->ismounted($mountPoint)){ return true; }

	@mkdir($mountPoint,0755,true);

	if(!$mount->smb_mount($mountPoint,$BackupArticaRestoreNASIpaddr,$BackupArticaRestoreNASUser,$BackupArticaRestoreNASPassword,$BackupArticaRestoreNASFolder)){
		squid_admin_mysql(2, "Mounting //$BackupArticaRestoreNASIpaddr/$BackupArticaRestoreNASFolder failed",__FUNCTION__,__FILE__,__LINE__);
		return false;

	}

	if(!$mount->ismounted($mountPoint)){
		return false;
	}
	return true;
}

function restore(){
	
	$sock=new sockets();
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	if($GLOBALS["VERBOSE"]){
		echo "PID: $pidfile\n";
	}
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){
		$TTL=$unix->PROCESS_TTL($pid);
		if($TTL<240){return;}
		$kill=$unix->find_program("kill");
		unix_system_kill_force($pid);
	}
	
	@file_put_contents($pidfile, getmypid());
	$hostname=$unix->hostname_g();
	
	
	progress(10,"{mounting}");
	if(!restore_TestNas()){
		squid_admin_mysql(2, "Mounting NAS filesystem report false",__FUNCTION__,__FILE__,__LINE__);
		progress(100,"{failed}");
		return;
	
	}
	
	$BackupArticaRestoreNASIpaddr=$sock->GET_INFO("BackupArticaRestoreNASIpaddr");
	$BackupArticaRestoreNASFolder=$sock->GET_INFO("BackupArticaRestoreNASFolder");
	$BackupArticaRestoreNASUser=$sock->GET_INFO("BackupArticaRestoreNASUser");
	$BackupArticaRestoreNASPassword=$sock->GET_INFO("BackupArticaRestoreNASPassword");
	$BackupArticaRestoreNASFolderSource=$sock->GET_INFO("BackupArticaRestoreNASFolderSource");
	$BackupArticaRestoreNetwork=$sock->GET_INFO("BackupArticaRestoreNetwork");
	$mountPoint="/mnt/BackupArticaRestoreNAS";	
	$BackupArticaRestoreNASFolderSource=str_replace("\\", "/", $BackupArticaRestoreNASFolderSource);
	
	$sourceDir="$mountPoint/$BackupArticaRestoreNASFolderSource";
	$sourceDir=str_replace("//", "/", $sourceDir);
	
	if(!is_file("$sourceDir/BKVERSION.txt")){
		progress(100,"{failed} BKVERSION.txt no such file");
		$mount=new mount("/var/log/artica-postfix/backup.debug");
		if($mount->ismounted($mountPoint)){ $mount->umount($mountPoint);}
		return;
	}
	
	$time=trim(@file_get_contents("$sourceDir/BKVERSION.txt"));
	progress(15,"{backup} ".date("Y-m-d H:i:s"));
	progress(20,"{restoring_ldap_database}, {please_wait}...");
	Restore_ldap($sourceDir);
	progress(40,"{restoring_artica_settings}, {please_wait}...");
	restore_artica_settings($sourceDir);
	progress(50,"{restoring_artica_databases}, {please_wait}...");
	restore_artica_backup($sourceDir);
	progress(55,"{restoring_artica_databases}, {please_wait}...");
	restore_artica_sqlite($sourceDir);
	progress(60,"{restoring_artica_databases}, {please_wait}...");
	restore_ocsweb($sourceDir);	
	progress(80,"{restoring_artica_databases}, {please_wait}...");
	restore_squidlogs($sourceDir);	
	progress(82,"{restoring} PowerDNS, {please_wait}...");
	restore_powerdns($sourceDir);	
	progress(90,"{reconfigure_server}, {please_wait}...");
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$php=$unix->LOCATE_PHP5_BIN();
	if(is_file($squidbin)){shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force"); }
	progress(100,"{success}");
	$mount=new mount("/var/log/artica-postfix/backup.debug");
	if($mount->ismounted($mountPoint)){ $mount->umount($mountPoint);}
	
	if($BackupArticaRestoreNetwork==1){
		$unix->THREAD_COMMAND_SET("$php /usr/share/artica-postfix/exec.virtuals-ip.php --build");
	}
	
	return;
	
}
function Restore_ldap($sourceDir):bool{

    if(!is_file("/etc/init.d/slapd")){
        echo "OpenLDAP not installed\n";
        return true;
    }

	$unix=new unix();
	$gunzip=$unix->find_program("gunzip");
	$slapadd=$unix->find_program("slapadd");
	$rm=$unix->find_program("rm");
	$ldap_databases="/var/lib/ldap";
	$SLAPD_CONF=$unix->SLAPD_CONF_PATH();
	$SLAPD_CONF_GZ="$sourceDir/ldap.conf.gz";
	$LDAP_DB="$sourceDir/ldap_database.gz";
	$TMP=$unix->FILE_TEMP();
	if(!is_file($SLAPD_CONF_GZ)){
		squid_admin_mysql(2, "{failed} ldap.conf.gz no such file",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	if($GLOBALS["VERBOSE"]){echo "Extract $LDAP_DB\n";}
	shell_exec("$gunzip $LDAP_DB -c >$TMP");
	
	
	if($GLOBALS["VERBOSE"]){echo "Stopping LDAP\n";}
	shell_exec("/etc/init.d/slapd stop --force");
	if($GLOBALS["VERBOSE"]){echo "Restoring slapd.conf\n";}
	shell_exec("$gunzip $SLAPD_CONF_GZ -c >$SLAPD_CONF");
	if($GLOBALS["VERBOSE"]){echo "Removing $ldap_databases\n";}
	shell_exec("$rm -f  $ldap_databases/* >/dev/null 2>&1");
	if($GLOBALS["VERBOSE"]){echo "Restoring database....\n";}
	shell_exec("$slapadd -v -c -l $TMP -f $SLAPD_CONF >/dev/null 2>&1");
	if($GLOBALS["VERBOSE"]){echo "Starting slapd\n";}
	shell_exec("/etc/init.d/slapd start --force");
	@unlink($TMP);
    backupevents("Restoring OpenLDAP database success");
    return true;
}
function restore_nginx($sourceDir):bool{
	if(!is_dir($sourceDir."/nginx")){
        return true;
    }
	if(!is_file("$sourceDir/nginx/tarball.tgz")){
        return true;

    }
	$unix=new unix();
	$tar=$unix->find_program("tar");
	
	@mkdir("/etc/nginx",0755,true);
	shell_exec("$tar xpf $sourceDir/nginx/tarball.tgz -C /etc/nginx/");
    backupevents("Restoring Reverse-Proxy parameters success");
    return true;
}
function restore_artica_settings($sourceDir):bool{
	if (!$handle = opendir("$sourceDir/Daemons")) {
        backupevents("Failed open $sourceDir/Daemons");
        echo "Failed open $sourceDir/Daemons\n";
        return true;
    }
	$BackupArticaRestoreNetwork=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaRestoreNetwork"));
	if($GLOBALS["NOT_RESTORE_NETWORK"]){$BackupArticaRestoreNetwork=0;}

	
	$BLACKLIST=artica_settings_blacklists();
	
	if($BackupArticaRestoreNetwork==0){
		$BLACKLIST["EnableKerbAuth"]=true;
		$BLACKLIST["KerbAuthInfos"]=true;
		$BLACKLIST["SambaBindInterface"]=true;
		$BLACKLIST["SambaSMBConf"]=true;
		$BLACKLIST["NTPDConf"]=true;
		$BLACKLIST["SquidGuardServerName"]=true;
		$BLACKLIST["SquidWCCPL3LocIP"]=true;
		$BLACKLIST["SambaSecondPartConf"]=true;
		$BLACKLIST["EnableSquidRemoteMySQL"]=true;
		$BLACKLIST["squidRemostatisticsServer"]=true;
		$BLACKLIST["squidRemostatisticsPort"]=true;
		$BLACKLIST["squidRemostatisticsUser"]=true;
		$BLACKLIST["squidRemostatisticsPassword"]=true;
		$BLACKLIST["UseRemoteUfdbguardService"]=true;
		$BLACKLIST["BackupArticaRestoreNASIpaddr"]=true;
		$BLACKLIST["BackupArticaRestoreNASFolder"]=true;
		$BLACKLIST["BackupArticaRestoreNASUser"]=true;
		$BLACKLIST["BackupArticaRestoreNASPassword"]=true;
		$BLACKLIST["BackupArticaRestoreNASFolderSource"]=true;
		$BLACKLIST["BackupArticaRestoreNetwork"]=true;
		$BLACKLIST["BackupSquidLogsUseNas"]=true;
		$BLACKLIST["BackupSquidLogsNASIpaddr"]=true;
		$BLACKLIST["BackupSquidLogsNASFolder"]=true;
		$BLACKLIST["BackupSquidLogsNASUser"]=true;
		$BLACKLIST["BackupSquidLogsNASPassword"]=true;
		$BLACKLIST["BackupSquidStatsUseNas"]=true;
		$BLACKLIST["BackupSquidStatsNASIpaddr"]=true;
		$BLACKLIST["BackupSquidStatsNASFolder"]=true;
		$BLACKLIST["BackupSquidStatsNASUser"]=true;
		$BLACKLIST["BackupSquidStatsNASPassword"]=true;
		$BLACKLIST["NetWorkBroadCastVLANAsIpAddr"]=true;
		$BLACKLIST["ArticaDHCPSettings"]=true;
		$BLACKLIST["HASettings"]=true;
		$BLACKLIST["resolvConf"]=true;
		$BLACKLIST["UseADAsNameServer"]=true;
		$BLACKLIST["OVHNetConfig"]=true;
		$BLACKLIST["ufdbCatInterface"]=true;
		
	}
	
	
	$c=0;$size=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		$c++;
		$SourceFile="$sourceDir/Daemons/$filename";
		$targetFile="/etc/artica-postfix/settings/Daemons/$filename";
		if(is_dir($SourceFile)){continue;}
		
		if(is_file($targetFile)){@unlink($targetFile);}
		if(!copy($SourceFile, $targetFile)){
			echo "Restoring $SourceFile Failed\n";
			continue;
		}
		
		$size=$size+@filesize($SourceFile);
	}
	
	$size=FormatBytes($size/1024,true);
    backupevents("Restoring $c Parameters ($size) done");
    return true;
	
}
function restore_artica_sqlite($sourceDir):bool{
	if (!$handle = opendir("$sourceDir/SQLITE")) {
        backupevents("Failed open $sourceDir/SQLITE");
        echo "Failed open $sourceDir/SQLITE\n";
        return false;
    }
	
	@mkdir("/home/artica/SQLITE",0755,true);
    $BLACKLIST["interfaces.db"]=true;
    $BLACKLIST["webconsole.db"]=true;

	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		$SourceFile="$sourceDir/SQLITE/$filename";
        backupevents("Restoring SQLite database $filename");
		echo "Restoring SQLITE/$filename\n";
		if(is_file("/home/artica/SQLITE/$filename")){@unlink("/home/artica/SQLITE/$filename");}
		@copy($SourceFile, "/home/artica/SQLITE/$filename");
		@chmod("/home/artica/SQLITE/$filename",0755);
		@chown("/home/artica/SQLITE/$filename","www-data");
        @chgrp("/home/artica/SQLITE/$filename","www-data");
	}

    return true;
	
}
function restore_artica_backup($sourceDir,$blacklists=array()):bool{

    if(!is_dir("$sourceDir/artica_backup")){
        backupevents("artica_backup no such directory continue next...");
        echo "$sourceDir/artica_backup no such directory...\n";
        return true;
    }

	if (!$handle = opendir("$sourceDir/artica_backup")) {echo "Failed open $sourceDir/artica_backup\n";return true;}
	$password=null;
	$unix=new unix();
	$sock=new sockets();
	$BackupArticaRestoreNetwork=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaRestoreNetwork"));
	$gunzip=$unix->find_program("gunzip");
	$mysql=$unix->find_program("mysql");
	$BLACKLIST=array();
	$BLACKLIST["zarafa_orphaned.gz"]=true;
	$BLACKLIST["dashboard_volume_day.gz"]=true;
	
	
	if($GLOBALS["NOT_RESTORE_NETWORK"]){$BackupArticaRestoreNetwork=0;}
	
	foreach ($blacklists as $tablename=>$value){
		$BLACKLIST["$tablename.gz"]=true;
		
	}
	
	if($BackupArticaRestoreNetwork==0){
		$BLACKLIST["nic_routes.gz"]=true;
		$BLACKLIST["nics.gz"]=true;
		$BLACKLIST["nics_bridge.gz"]=true;
		$BLACKLIST["nics_roles.gz"]=true;
		$BLACKLIST["nics_switch.gz"]=true;
		$BLACKLIST["nics_vde.gz"]=true;
		$BLACKLIST["nics_virtuals.gz"]=true;
		$BLACKLIST["nics_vlan.gz"]=true;
		$BLACKLIST["networks_infos.gz"]=true;
		$BLACKLIST["dhcpd_sharednets.gz"]=true;
		$BLACKLIST["dhcpd_fixed.gz"]=true;
		$BLACKLIST["iptables_bridge.gz"]=true;
		$BLACKLIST["net_hosts.gz"]=true;
		$BLACKLIST["arpcache.gz"]=true;
		$BLACKLIST["artica_clusters.gz"]=true;
	}
	
	$nice=$unix->EXEC_NICE();
	$q=new mysql();
	
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	$prefix=trim("$mysql --force -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password artica_backup");

	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		$SourceFile="$sourceDir/artica_backup/$filename";
		if(is_dir($SourceFile)){continue;}
		$cmd=trim("$nice $gunzip -c $SourceFile |$prefix");
		echo "Restoring artica_backup/$filename\n";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		system($cmd);
	}

	squid_admin_mysql(2, "Restoring artica_backup done\n",__FUNCTION__,__FILE__,__LINE__);
    return true;
}
function restore_powerdns($sourceDir){
	if(!is_dir("$sourceDir/powerdns")){
		backupevents("restore_powerdns:: $sourceDir/powerdns no such directory");
		echo "$sourceDir/powerdns no such directory\n";
		return true;
	}
	if (!$handle = opendir("$sourceDir/powerdns")) {
		backupevents("restore_powerdns:: Failed open $sourceDir/powerdns");
		echo "Failed open $sourceDir/powerdns\n";
		return;
	}
	$password=null;
	$unix=new unix();
	$sock=new sockets();
	$gunzip=$unix->find_program("gunzip");
	$mysql=$unix->find_program("mysql");
	$BLACKLIST=array();
	$nice=$unix->EXEC_NICE();
	$q=new mysql();
	
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	$prefix=trim("$mysql --force -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password powerdns");
	
	backupevents("Scanning ...$sourceDir/powerdns");
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		$SourceFile="$sourceDir/powerdns/$filename";
		backupevents("Importing $SourceFile");
		if(is_dir($SourceFile)){
			backupevents("$SourceFile is a directory, aborting");
			continue;
		}
		$cmd=trim("$nice $gunzip -c $SourceFile |$prefix");
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		backupevents("$cmd");
		shell_exec($cmd);
	}
	
	squid_admin_mysql(2, "Restoring PowerDNS done\n",__FUNCTION__,__FILE__,__LINE__);	
	
}
function restore_ocsweb($sourceDir){

	if (!$handle = opendir("$sourceDir/ocsweb")) {echo "Failed open $sourceDir/ocsweb\n";return;}
	$password=null;
	$unix=new unix();
	$sock=new sockets();
	$BackupArticaRestoreNetwork=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaRestoreNetwork"));
	$gunzip=$unix->find_program("gunzip");
	$mysql=$unix->find_program("mysql");
	$BLACKLIST=array();
	$nice=$unix->EXEC_NICE();
	$q=new mysql();

	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	$prefix=trim("$mysql --force -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password ocsweb");

	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		$SourceFile="$sourceDir/ocsweb/$filename";
		if(is_dir($SourceFile)){continue;}
		$cmd=trim("$nice $gunzip -c $SourceFile |$prefix");
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
	}

	squid_admin_mysql(2, "Restoring ocsweb done\n",__FUNCTION__,__FILE__,__LINE__);
}
function restore_postgresql($sourceDir):bool{
	if (!$handle = opendir("$sourceDir/PostGreSQL")) {echo "Failed open $sourceDir/PostGreSQL\n";return true;}
	
	$InfluxUseRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemote"));
	$dsn =  "--host='/var/run/ArticaStats'";
	
	if ($InfluxUseRemote==1) {
		$InfluxUseRemoteIpaddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemote");
		$InfluxUseRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemotePort"));
		if ($InfluxUseRemotePort==0) { $InfluxUseRemotePort = 5432;}
		$dsn =  "--host=$InfluxUseRemoteIpaddr --port=$InfluxUseRemotePort";
	
	}
	
	$prefix="/usr/local/ArticaStats/bin/pg_restore -Fc --no-password --username=ArticaStats --dbname=proxydb $dsn ";
    $c=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$SourceFile="$sourceDir/PostGreSQL/$filename";
		if(is_dir($SourceFile)){continue;}
        $c++;
		echo "Restoring PostGreSQL $filename\n";
		$cmd=trim("$prefix $SourceFile");
		system($cmd);
	}

    backupevents("Restored $c PostGreSQL tables");
	return true;
}
function restore_squidlogs($sourceDir){

    if(!is_dir("$sourceDir/squidlogs")){
        echo "$sourceDir/squidlogs no such directory\n";
        return;
    }
	if (!$handle = opendir("$sourceDir/squidlogs")) {echo "Failed open $sourceDir/squidlogs\n";return;}
	$password=null;
	$unix=new unix();
	
	if(!$unix->is_socket("/var/run/mysqld/squid-db.sock")){squid_admin_mysql(2, "Error,/var/run/mysqld/squid-db.sock no such socket",__FUNCTION__,__FILE__,__LINE__); return false; }
	
	$sock=new sockets();
	$gunzip=$unix->find_program("gunzip");
	$mysql=$unix->find_program("mysql");
	$BLACKLIST=array();
	$nice=$unix->EXEC_NICE();
	$q=new mysql_squid_builder();
	
	$sock=new sockets();
	$BackupArticaRestoreNetwork=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaRestoreNetwork"));
	if($GLOBALS["NOT_RESTORE_NETWORK"]){$BackupArticaRestoreNetwork=0;}

	$BLACKLIST["FAMILY_SITES_DAY.gz"]=true;
	$BLACKLIST["FULL_USERS_DAY.gz"]=true;
	$BLACKLIST["FAMILY_SITES_DAY.gz"]=true;
	
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	$prefix=trim("$mysql --force -S /var/run/mysqld/squid-db.sock -u {$q->mysql_admin}$password squidlogs");

	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		$SourceFile="$sourceDir/squidlogs/$filename";
		if(is_dir($SourceFile)){continue;}
		echo "Restoring Proxy database/$filename\n";
		$cmd=trim("$nice $gunzip -c $SourceFile |$prefix");
		system($cmd);
	}

	
}
?>