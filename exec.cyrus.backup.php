<?php
$GLOBALS["SIMULATE"]=false;
$GLOBALS["NOTIME"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["SYS_CAT"]="cyrus-backup";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--simulate#",implode(" ",$argv))){$GLOBALS["SIMULATE"]=true;}
if(preg_match("#--notime#",implode(" ",$argv))){$GLOBALS["NOTIME"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
$GLOBALS["LOGFILE"]="/var/log/cyrus-backup.debug";
$GLOBALS["MOUNT_POINT"]="/home/artica/mounts/cyrus-mount-backup";
$GLOBALS["SYSTEM_INTERNAL_LOAD"]=0;
$GLOBALS["MOUNTED_PATH_FINAL"]=null;
$_GET["LOGFILE"]=$GLOBALS["LOGFILE"];
$GLOBALS["DATE_START"]=time();
$unix=new unix();
$sock=new sockets();
$GLOBALS["CLASS_UNIX"]=$unix;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$x=$unix->process_number_me($argv);
if($x>0){die("This process is already executed $x times\n\n");}


if($argv[1]=="--testnas"){tests_nas().killNas();exit();}
if($argv[1]=="--test-nas"){tests_nas().killNas();exit();}
exec_resources();

exit();

function tests_nas(){
	$sock=new sockets();
	$unix=new unix();
	$failed="***********************\n** FAILED **\n***********************\n";
	$success="***********************\n******* SUCCESS *******\n***********************\n";
	if(!isset($GLOBALS["CyrusBackupNas"])){$GLOBALS["CyrusBackupNas"]=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CyrusBackupNas")));}
	$CyrusBackupNas=$GLOBALS["CyrusBackupNas"];
	if(!isset($CyrusBackupNas["hostname"])){return;}
	if($CyrusBackupNas["hostname"]==null){return;}
	if(!is_numeric($CyrusBackupNas["notifs"])){$CyrusBackupNas["notifs"]=0;}

	if($GLOBALS["VERBOSE"]){
		if($CyrusBackupNas["notifs"]==1){
			$unix->SendEmailConfigured($CyrusBackupNas,"Test-message","This is a content");
		}
	}
	
	
	if($GLOBALS["VERBOSE"]){
		while (list ($index, $line) = each ($CyrusBackupNas) ){
			echo "$index.........: $line\n";
		}
	}

	$mount=new mount($GLOBALS["LOGFILE"]);
	$NasFolder=$CyrusBackupNas["folder"];
	$NasFolder=str_replace('\\\\', '/', $NasFolder);
	if(strpos($NasFolder, "/")>0){$f=explode("/",$NasFolder);$NasFolder=$f[0];}
	

	
	
	
	if($mount->ismounted($GLOBALS["MOUNT_POINT"])){
		if($GLOBALS["VERBOSE"]){echo $success.@implode("\n", $GLOBALS["MOUNT_EVENTS"]);}
		return true;
	}
	
	if(!$mount->smb_mount($GLOBALS["MOUNT_POINT"],$CyrusBackupNas["hostname"],
		$CyrusBackupNas["username"],$CyrusBackupNas["password"],$NasFolder)){
		if($GLOBALS["VERBOSE"]){echo $failed.@implode("\n", $GLOBALS["MOUNT_EVENTS"]);return;}
	}
	
	if($GLOBALS["VERBOSE"]){echo $success.@implode("\n", $GLOBALS["MOUNT_EVENTS"]);}
	return true;

}
function killNas(){
	$sock=new sockets();
	$mount=new mount($GLOBALS["LOGFILE"]);
	if($mount->ismounted($GLOBALS["MOUNT_POINT"])){$mount->umount($GLOBALS["MOUNT_POINT"]);}

}

function LoadConfig(){
	if(isset($GLOBALS["CyrusBackupNas"])){return;}
	$sock=new sockets();
	$GLOBALS["CyrusBackupNas"]=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CyrusBackupNas")));
	if(!is_numeric($GLOBALS["CyrusBackupNas"]["NAS_ENABLE"])){$GLOBALS["CyrusBackupNas"]["NAS_ENABLE"]=0;}
	if(!is_numeric($GLOBALS["CyrusBackupNas"]["COMPRESS_ENABLE"])){$GLOBALS["CyrusBackupNas"]["COMPRESS_ENABLE"]=1;}
	
	
}



function exec_webdav(){
	$mount=new mount();
	$unix=new unix();
	
	$mount_point=$GLOBALS["MOUNT_POINT"];
	@mkdir($mount_point,0755,true);
	$server=$GLOBALS["CyrusBackupNas"]["WEBDAV_SERVER"];
	$username=$GLOBALS["CyrusBackupNas"]["WEBDAV_USER"];
	$password=$GLOBALS["CyrusBackupNas"]["WEBDAV_PASSWORD"];
	$path=$GLOBALS["CyrusBackupNas"]["WEBDAV_DIR"];
	if($mount->ismounted($mount_point)){$mount->umount($mount_point);}
	
	if($GLOBALS["VERBOSE"]){echo "davfs:WEBDAV_DIR...: $path\n";}
	if($GLOBALS["VERBOSE"]){echo "davfs:WEBDAV_SERVER: $server\n";}
	
	
	if(!$mount->davfs_mount($mount_point,$server,$username,$password,$path)){
		if($GLOBALS["VERBOSE"]){echo $mount->events_compile()."\n";}
		squid_admin_mysql(0, "Unable to connect to $server", $mount->events_compile(),__FILE__,__LINE__);
		return;
	}
	
	$path=$mount->davfs_path($mount_point,$server,$username,$password,$path);
	if($GLOBALS["VERBOSE"]){echo "davfs_path: $path\n";}
	
	
	$hostname=$unix->hostname_g();
	$GLOBALS["DIRBYTES"]=date("YmdH");
	$GLOBALS["MOUNTED_PATH__BACKUPDIR"]="{$GLOBALS["MOUNT_POINT"]}/$path/$hostname";
	$GLOBALS["MOUNTED_PATH_FINAL"]="{$GLOBALS["MOUNT_POINT"]}/$path/$hostname/{$GLOBALS["DIRBYTES"]}";
	$GLOBALS["MOUNTED_PATH_FINAL"]=str_replace("//", "/", $GLOBALS["MOUNTED_PATH_FINAL"]);
	if($GLOBALS["VERBOSE"]){echo "MOUNTED_PATH_FINAL: {$GLOBALS["MOUNTED_PATH_FINAL"]}\n";}
	
	if(!is_dir($GLOBALS["MOUNTED_PATH_FINAL"])){
		if($GLOBALS["VERBOSE"]){echo "Create -> {$GLOBALS["MOUNTED_PATH_FINAL"]}\n";}
		@mkdir($GLOBALS["MOUNTED_PATH_FINAL"],0755,true);
		if(!is_dir($GLOBALS["MOUNTED_PATH_FINAL"])){
			squid_admin_mysql(0,"Unable to backup: Permission denied on WebDAV resource $server","For creating directory {$GLOBALS["MOUNTED_PATH_FINAL"]}",__FILE__,__LINE__);
			killNas();
			return;
		}
	}
	
	$t=time();
	@touch("{$GLOBALS["MOUNTED_PATH_FINAL"]}/$t");
	if(!is_file("{$GLOBALS["MOUNTED_PATH_FINAL"]}/$t")){
		squid_admin_mysql(0,"Unable to backup: Permission denied on WebDAV",
		"resource {{$GLOBALS["MOUNTED_PATH_FINAL"]}/$t}",__FILE__,__LINE__);
		killNas();
		return;
	}
	
	@unlink("{$GLOBALS["MOUNTED_PATH_FINAL"]}/$t");
	
	if($GLOBALS["VERBOSE"]){echo "backup_ldap():\n";}
	backup_ldap();
	if($GLOBALS["VERBOSE"]){echo "backup_cyrus():\n";}
	backup_cyrus();
	remove_containers();	
	killNas();
	
	
}


function exec_resources(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){
		$TIMEF=$unix->PROCCESS_TIME_MIN($pid);
		squid_admin_mysql(1,"An Artica task already running PID $pid since {$TIMEF}Mn","Aborted",__FILE__,__LINE__);
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	LoadConfig();
	if($GLOBALS["CyrusBackupNas"]["WEBDAV_ENABLE"]==1){exec_webdav();}
	if($GLOBALS["CyrusBackupNas"]["NAS_ENABLE"]==0){return;}
	$TimeStart=time();
	
	if(!tests_nas()){ squid_admin_mysql(0,"Unable to backup cyrus-mailboxes",null,__FILE__,__LINE__); return; }
	$hostname=$unix->hostname_g();
	$GLOBALS["DIRBYTES"]=date("YmdH");
	$GLOBALS["MOUNTED_PATH__BACKUPDIR"]="{$GLOBALS["MOUNT_POINT"]}/$hostname";
	$GLOBALS["MOUNTED_PATH_FINAL"]="{$GLOBALS["MOUNT_POINT"]}/$hostname/{$GLOBALS["DIRBYTES"]}";
	if(!is_dir($GLOBALS["MOUNTED_PATH_FINAL"])){
		@mkdir($GLOBALS["MOUNTED_PATH_FINAL"],0755,true);
		if(!is_dir($GLOBALS["MOUNTED_PATH_FINAL"])){
			squid_admin_mysql(0,"Unable to backup: Permission denied on NAS",null,__FILE__,__LINE__);
			return;
		}
	}
	backup_ldap();
	backup_cyrus();
	
	$report[]="Started at : ".date("Y-m-d H:i:s",$TimeStart);
	$report[]="End at : ".date("Y-m-d H:i:s");
	$report[]="Duration: ".$unix->distanceOfTimeInWords($TimeStart,time());
	@file_put_contents("{$GLOBALS["MOUNTED_PATH_FINAL"]}/report.txt", @implode("\r\n", $report));
	remove_containers();
	killNas();
}

function remove_containers(){
	$q=new mysql();
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$hostname=$unix->hostname_g();
	if(!is_numeric($GLOBALS["CyrusBackupNas"]["maxcontainer"])){$GLOBALS["CyrusBackupNas"]["maxcontainer"]=3;}
	$sql="SELECT * FROM cyrus_backup WHERE hostname='$hostname' ORDER BY directory DESC";
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(mysqli_num_rows($results) < $GLOBALS["CyrusBackupNas"]["maxcontainer"] ) {return;}
	$c=0;
	while ($ligne = mysqli_fetch_assoc($results)) {
		$c++;
		if($c<$GLOBALS["CyrusBackupNas"]["maxcontainer"]){continue;}
		$directory=$ligne["directory"];
		if(!is_dir("{$GLOBALS["MOUNTED_PATH__BACKUPDIR"]}/$directory")){
			$q->QUERY_SQL("DELETE FROM cyrus_backup WHERE directory='$directory' AND hostname='$hostname'","artica_events");
			continue;
		}
		shell_exec("$rm -rf {$GLOBALS["MOUNTED_PATH__BACKUPDIR"]}/$directory");
		squid_admin_mysql(0,"Deleted container $directory",__FILE__,__LINE__);
		$q->QUERY_SQL("DELETE FROM cyrus_backup WHERE directory='$directory' AND hostname='$hostname'","artica_events");
	}	
}

function backup_cyrus(){
	$unix=new unix();
	$tempdir=$unix->TEMP_DIR();
	
	
	$q=new mysql();
	
	$users=new usersMenus();
	if(!$users->cyrus_imapd_installed){
		squid_admin_mysql(0,"Unable to backup: cyrus-impad NOT Installed",null,__FILE__,__LINE__);
		return true;
	}

	$partition_default=$users->cyr_partition_default;
	$config_directory=$users->cyr_config_directory;
	$tar=$unix->find_program("tar");
	$su=$unix->find_program("su");
	$rsync=$unix->find_program("rsync");

	@mkdir("{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap",0755,true);
	
	if(!is_dir("{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap")){
		
		squid_admin_mysql(0,__LINE__."]: Unable to backup: Permission denied","On NAS {$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap no such directory",__FILE__,__LINE__);
		return;
	}
	
	if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap OK\n";}
		

	if(!is_file("$users->ctl_mboxlist")){
		squid_admin_mysql(0,"Unable to backup: ctl_mboxlist no such binary",null,__FILE__,__LINE__);
		return;	
	}

	$L=array();
	$T=array();
	
	@chmod("$tempdir",0777);
	
	$cmd="$su - cyrus -c \"$users->ctl_mboxlist -d >$tempdir/mailboxlist.txt\"";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	exec($cmd,$results);
	
	if(!is_file("$tempdir/mailboxlist.txt")){
		squid_admin_mysql(0,"Unable to backup: unable to export mailbox list",
		"file $tempdir/mailboxlist.txt not exists\n****\n$cmd\n****\n\n".implode("\n",$results),__FILE__,__LINE__);
	}else{
		if($GLOBALS["CyrusBackupNas"]["COMPRESS_ENABLE"]==0){
			if(!@copy("$tempdir/mailboxlist.txt", "{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/mailboxlist.txt")){
				squid_admin_mysql(0,"Unable to backup: Permission denied on resource",
				"{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap permission denied");
				@unlink("$tempdir/mailboxlist.txt");
				return;
			}
			$size=@filesize("{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/mailboxlist.txt");
			$size=FormatBytes($size/1024);
			squid_admin_mysql(2,"mailboxlist.txt - $size - success",null,__FILE__,__LINE__);
		}
		
		if($GLOBALS["CyrusBackupNas"]["COMPRESS_ENABLE"]==1){
			if(!$unix->compress("$tempdir/mailboxlist.txt", "{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/mailboxlist.txt.gz")){
				squid_admin_mysql(0,"Unable to backup: Permission denied on resource",
				"{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap permission denied");
				@unlink("$tempdir/mailboxlist.txt");
				return;
			}
			$size=@filesize("{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/mailboxlist.txt.gz");
			$size=FormatBytes($size/1024);
			squid_admin_mysql(2,"mailboxlist.txt.gz - $size - success",null,__FILE__,__LINE__);
		}
		
		
	}
	
	
	
	$results=array();
	if($GLOBALS["CyrusBackupNas"]["COMPRESS_ENABLE"]==0){
		if(!is_file($rsync)){
			squid_admin_mysql(0,"Rsync is not present, backup operation will be stopped...",null,__FILE__,__LINE__);
			return false;
		}
		squid_admin_mysql(2,"Starting backup $partition_default and $config_directory",null,__FILE__,__LINE__);
		$cmd="$rsync -vaR --delete --delete-after $partition_default $config_directory {$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap";
		$t=time();
		exec($cmd,$results);
		squid_admin_mysql(2,"Backup: {took} ".$unix->distanceOfTimeInWords($t,time()),@implode("\n", $results),__FILE__,__LINE__);
		InsertToMysql();
		return;
		
		
		
	}
	
	@chdir($partition_default);
	$nice=$unix->EXEC_NICE();
	if($GLOBALS["CyrusBackupNas"]["COMPRESS_ENABLE"]==1){
		squid_admin_mysql(2,"Starting Compressing $partition_default",null,__FILE__,__LINE__);
		$cmd="$nice $tar -Pcjf {$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/mail-data-backup.tar.bz2 * >{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/mail-data-backup.report.txt";
	}
	
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	shell_exec($cmd);
	$data=@file_get_contents("{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/mail-data-backup.report.txt");
	if(!is_file("{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/mail-data-backup.tar.bz2")){
		squid_admin_mysql(0,"Unable to backup: mail-data-backup.tar.bz2 Permission denied or compression failed",
		$data,__FILE__,__LINE__);
		return;
	}

	
	
	$size=@filesize("{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/mail-data-backup.tar.bz2");
	$size=FormatBytes($size/1024);
	squid_admin_mysql(2,"cyrus-imap/mail-data-backup.tar.bz2 - $size - success",$data,__FILE__,__LINE__);
		
	$results=array();
	
	
	@chdir($config_directory);
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	squid_admin_mysql(2,"Starting Compressing $config_directory",null,__FILE__,__LINE__);
	
	$cmd="$nice $tar -Pcjf {$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/configdirectory.tar.bz2 * >{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/configdirectory.report.txt";
	shell_exec($cmd);
	$data=@file_get_contents("{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/configdirectory.report.txt");
	if(!is_file("{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/configdirectory.tar.bz2")){
		squid_admin_mysql(0,"Unable to backup: configdirectory.tar.bz2 Permission denied or compression failed",$data,__FILE__,__LINE__);
		return;
	}
	$size=@filesize("{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/configdirectory.tar.bz2");
	$size=FormatBytes($size/1024);
	squid_admin_mysql(2,"cyrus-imap/mail-data-backup.tar.bz2 - $size - success",$data,__FILE__,__LINE__);
	InsertToMysql();
}

function InsertToMysql(){
	$unix=new unix();
	$date_start=$GLOBALS["DATE_START"];
	$size=$unix->DIRSIZE_BYTES("{$GLOBALS["MOUNTED_PATH_FINAL"]}");
	$date_end=time();
	$calculate=$unix->distanceOfTimeInWords($date_start,time());
	$zDate=date("Y-m-d H:i:s");
	$hostname=$unix->hostname_g();
	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`cyrus_backup` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
				`zDate` DATETIME NOT NULL ,
				`hostname` VARCHAR( 128 ) NOT NULL ,
				`duration` VARCHAR( 256 ) NOT NULL ,
				`directory` BIGINT UNSIGNED,
				`size` BIGINT UNSIGNED ,
				INDEX ( `zDate` , `directory` , `size` ,`hostname`)
				);";
	$q->QUERY_SQL($sql,'artica_events');
	$q->QUERY_SQL("INSERT IGNORE INTO `cyrus_backup` (`zDate`,`hostname`,`duration`,`directory`,`size`) VALUES('$zDate','$hostname','$calculate','{$GLOBALS["DIRBYTES"]}','$size')","artica_events");
	
	if(!$q->ok){squid_admin_mysql(0,"$q->mysql_error",__FILE__,__LINE__);}
	
	$size=$size/1024;
	$size=$size/1024;
	squid_admin_mysql(2,"Cyrus backup: Success $calculate in {$GLOBALS["MOUNTED_PATH_FINAL"]}",null,__FILE__,__LINE__);
	
}


function backup_ldap(){
	$unix=new unix();
	$slapcat=$unix->find_program("slapcat");
	if($slapcat==null){
		squid_admin_mysql(0,"Unable to find slapcat binary",null,__FILE__,__LINE__);
		return false;
	}
	$tempdir=$unix->TEMP_DIR();
	shell_exec("$slapcat -l $tempdir/ldap.ldif");

	@mkdir("{$GLOBALS["MOUNTED_PATH_FINAL"]}/ldap_backup",0755,true);
	if(!is_dir("{$GLOBALS["MOUNTED_PATH_FINAL"]}/ldap_backup")){
		squid_admin_mysql(0,"Unable to backup: Permission denied on ressource",null,__FILE__,__LINE__);
		@unlink("$tempdir/ldap.ldif");
		return false;
	}
	
	if($GLOBALS["CyrusBackupNas"]["COMPRESS_ENABLE"]==0){

		if(!@copy("$tempdir/ldap.ldif", "{$GLOBALS["MOUNTED_PATH_FINAL"]}/ldap_backup/ldap.ldif")){
			squid_admin_mysql(0,"Unable to backup: Permission denied on ressource",null,__FILE__,__LINE__);
			@unlink("$tempdir/ldap.ldif");
			return false;
		}
	
	}
	
	if($GLOBALS["CyrusBackupNas"]["COMPRESS_ENABLE"]==1){
		if(!$unix->compress("$tempdir/ldap.ldif", "{$GLOBALS["MOUNTED_PATH_FINAL"]}/ldap_backup/ldap.ldif.gz")){
			squid_admin_mysql(0,"Unable to backup: Permission denied on ressource",null,__FILE__,__LINE__);
			@unlink("$tempdir/ldap.ldif");
			return false;
		}
		
	}
	
	
	$ldap=new clladp();
	if(!@file_put_contents("{$GLOBALS["MOUNTED_PATH_FINAL"]}/ldap_backup/suffix",$ldap->suffix)){
		squid_admin_mysql(0,"Unable to backup: Permission denied on ressource",null,__FILE__,__LINE__);
	}
	@unlink("$tempdir/ldap.ldif");
}


