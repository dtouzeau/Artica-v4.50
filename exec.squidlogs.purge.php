<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.mysql.dump.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.mount.inc');

$_GET["LOGFILE"]="/var/log/artica-postfix/dansguardian-logger.debug";
$GLOBALS["MAXDAYS"]=0;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#maxdays=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["MAXDAYS"]=$re[1];}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

$unix=new unix();
if($unix->process_number_me($argv)>0){die("Already executed\n\n");}

if($argv[1]=="--test-nas"){tests_nas(true);exit();}
if($argv[1]=="--quotas"){CleanQuotas(true);exit();}
if($argv[1]=="--remove-all"){removeall(true);exit();}
if($argv[1]=="--numeric-members"){remove_numeric_members(true);exit();}
if($argv[1]=="--backup"){backup_all(true);exit();}



purge();


function CleanQuotas(){
	$sock=new sockets();
	$unix=new unix();
	$q=new mysql_squid_builder();
	$flic=@file_get_contents(base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2EvLmxpYw=="));
	if(preg_match("#TRUE#is", $flic)){$LICENSE=1;}
	$ArticaProxyStatisticsBackupFolder=GetMountPoint();
	$ArticaProxyStatisticsBackupDays=$sock->GET_INFO("ArticaProxyStatisticsBackupDays");
	if(!is_numeric($ArticaProxyStatisticsBackupDays)){$ArticaProxyStatisticsBackupDays=90;}
	if($GLOBALS["MAXDAYS"]>0){$ArticaProxyStatisticsBackupDays=$GLOBALS["MAXDAYS"];}
	if($LICENSE==0){$ArticaProxyStatisticsBackupDays=5;}
	$mysqldump=$unix->find_program("mysqldump");
	$tar=$unix->find_program("tar");
	if($GLOBALS["VERBOSE"]){"Max Day: $ArticaProxyStatisticsBackupDays; folder:$ArticaProxyStatisticsBackupFolder\n";}	

	
	

	
	$mysqldump_prefix="$mysqldump $q->MYSQL_CMDLINES --skip-add-locks --insert-ignore --quote-names --skip-add-drop-table --verbose --force $q->database ";
	$sql="SELECT tablename,zDate FROM quota_temp WHERE zDate<DATE_SUB(NOW(),INTERVAL $ArticaProxyStatisticsBackupDays DAY)";
	$results=$q->QUERY_SQL($sql);
	
	$t=time();
	$DeleteTables=0;
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$tablename=$ligne["tablename"];
		$TableKey=$tablename;
		$day=$ligne["zDate"];
		$DayTime=strtotime("$day 00:00:00");
		$container="$ArticaProxyStatisticsBackupFolder/$tablename.sql";
		if($GLOBALS["VERBOSE"]){echo "Container: $container\n";}
		$cmdline="$mysqldump_prefix$tablename >$container";
		echo "To backup $tablename ($day)\n";
		
		$resultsZ=array();
		exec($cmdline,$resultsZ);
			
		if(!TestDump($resultsZ,$container)){
			squid_admin_mysql(1, "Fatal Error: day: Dump failed $tablename - $day",__FUNCTION__,__FILE__,__LINE__,"backup");
			KillMountPoint();
			return;
		}
		
		@chdir($ArticaProxyStatisticsBackupFolder);
		$cmdline="$tar cfz $container.tar.gz $container 2>&1";
		$resultsZ=array();
		exec($cmdline,$resultsZ);
		
		
		if(!$unix->TARGZ_TEST_CONTAINER("$container.tar.gz")){
			squid_admin_mysql(1, "Fatal Error: tar $container failed",__FUNCTION__,__FILE__,__LINE__,"backup");
			@unlink($container);
			@unlink("$container.tar.gz");
			KillMountPoint();
			return;
		}	
		$size=@filesize($container);
		$TotalSize=$TotalSize+$size;
		@unlink($container);
		$DeleteTables++;
		$q->QUERY_SQL("DROP TABLE $tablename");
		
	}
	
	if($DeleteTables>0){
		$TotalSize=FormatBytes($TotalSize/1024);
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		squid_admin_mysql(2, "Success backup and purge $DeleteTables table(s) ($TotalSize)", "took:$took",__FILE__,__LINE__);
		squid_admin_mysql(1, "Success backup and purge $DeleteTables table(s) ($TotalSize) took:$took",__FUNCTION__,__FILE__,__LINE__,"backup");
	
	}
	
	KillMountPoint();
	
	$q->QUERY_SQL("DROP TABLE `quota_temp`");
	
	
}

function purge(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		squid_admin_mysql(1, "Already executed pid $pid since {$timepid}",__FUNCTION__,__FILE__,__LINE__,"reports");
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$sock=new sockets();
	$users=new usersMenus();
	$LICENSE=0;
	$mysqldump=$unix->find_program("mysqldump");
	$tar=$unix->find_program("tar");
	$EnableSquidRemoteMySQL=$sock->GET_INFO("EnableSquidRemoteMySQL");
	if(!is_numeric($EnableSquidRemoteMySQL)){$EnableSquidRemoteMySQL=0;}
	if($EnableSquidRemoteMySQL==1){return ;}
	
	if(!is_file($mysqldump)){
		echo "mysqldump, no such binary\n";
		squid_admin_mysql(0, "mysqldump, no such binary", "Backup process cannot be performed");
		squid_admin_mysql(1, "mysqldump, no such binary",__FUNCTION__,__FILE__,__LINE__,"backup");
		return;
	}
	
	if(!is_file($tar)){
		echo "tar, no such binary\n";
		squid_admin_mysql(0, "tar, no such binary", "Backup process cannot be performed");
		squid_admin_mysql(1, "tar, no such binary",__FUNCTION__,__FILE__,__LINE__,"backup");
		return;
	}	
	
	$flic=@file_get_contents(base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2EvLmxpYw=="));
	if(preg_match("#TRUE#is", $flic)){$LICENSE=1;}
	
	$ArticaProxyStatisticsBackupDays=$sock->GET_INFO("ArticaProxyStatisticsBackupDays");
	$ArticaProxyStatisticsBackupFolder=GetMountPoint();
	$BackupSquidStatsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidStatsUseNas"));
	
	if($BackupSquidStatsUseNas==0){
		$BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
		if($BackupSquidLogsUseNas==1){$BackupSquidStatsUseNas=1;}
		
	}

	
	
	
	if($BackupSquidStatsUseNas==0){
		$ArticaProxyStatisticsBackupFolder=$sock->GET_INFO("ArticaProxyStatisticsBackupFolder");
		if($ArticaProxyStatisticsBackupFolder==null){$ArticaProxyStatisticsBackupFolder="/home/artica/squid/backup-statistics";}
		$percent=$unix->DIRECTORY_USEPERCENT($ArticaProxyStatisticsBackupFolder);
		if($percent>90){
			squid_admin_mysql(0, "Fatal backup partition is over 90% {$percent}%, aborting backup",
			"Directory is :$ArticaProxyStatisticsBackupFolder",__FILE__,__LINE__,"backup",null,__FILE__,__LINE__);
			squid_admin_mysql(0,"Fatal backup partition is over 90% {$percent}%, aborting backup","Directory is :$ArticaProxyStatisticsBackupFolder",__FILE__,__LINE__);
			exit();
		}
	}
	
	
	$ArticaProxyStatisticsBackupFolderORG=$ArticaProxyStatisticsBackupFolder;
	if(!is_numeric($ArticaProxyStatisticsBackupDays)){$ArticaProxyStatisticsBackupDays=90;}
	if($GLOBALS["MAXDAYS"]>0){$ArticaProxyStatisticsBackupDays=$GLOBALS["MAXDAYS"];}
	if($LICENSE==0){$ArticaProxyStatisticsBackupDays=5;}
	if(!ScanDays()){if($GLOBALS["VERBOSE"]){
		squid_admin_mysql(2, "ScanDay() report failed",__FILE__,__LINE__);
		echo "Failed...\n";}
		KillMountPoint();
		return;
	}
	
	if($GLOBALS["VERBOSE"]){"Max Day: $ArticaProxyStatisticsBackupDays; folder:$ArticaProxyStatisticsBackupFolder\n";}
	$q=new mysql_squid_builder(true);
	
	$sql="SELECT tablename,zDate FROM tables_day WHERE zDate<DATE_SUB(NOW(),INTERVAL $ArticaProxyStatisticsBackupDays DAY)";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){squid_admin_mysql(0, "Fatal Error: $q->mysql_error",__FILE__,__LINE__,"backup");return;}
	squid_admin_mysql(1, "Items: ".mysqli_num_rows($results),__FUNCTION__,__FILE__,__LINE__,"backup");
	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysqli_num_rows($results)."\n";}
	
	
	squid_admin_mysql(2,"Day retentions are: {$ArticaProxyStatisticsBackupDays} Days - ".mysqli_num_rows($results)." tables to purge",__FILE__,__LINE__);
	
	
	@mkdir("$ArticaProxyStatisticsBackupFolder",0755,true);
	if(!is_dir($ArticaProxyStatisticsBackupFolder)){
		squid_admin_mysql(0, "Fatal $ArticaProxyStatisticsBackupFolder permission denied", "Backup process cannot be performed",__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "$ArticaProxyStatisticsBackupFolder permission denied\n";}
		squid_admin_mysql(1, "Fatal Error: $ArticaProxyStatisticsBackupFolder permission denied",__FUNCTION__,__FILE__,__LINE__,"backup");
		KillMountPoint();
		return;
	}
	
	$t=time();
	if(!@file_put_contents("$ArticaProxyStatisticsBackupFolder/$t", time())){
		squid_admin_mysql(0, "Fatal $ArticaProxyStatisticsBackupFolder  write error", "Backup process cannot be performed",__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "$ArticaProxyStatisticsBackupFolder write error\n";}
		squid_admin_mysql(1, "Fatal Error: $ArticaProxyStatisticsBackupFolder write error..",__FUNCTION__,__FILE__,__LINE__,"backup");
		KillMountPoint();
		return;		
	}
	
	if(!is_file("$ArticaProxyStatisticsBackupFolder/$t")){
		squid_admin_mysql(0, "Fatal $ArticaProxyStatisticsBackupFolder permission denied", "Backup process cannot be performed",__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "$ArticaProxyStatisticsBackupFolder permission denied\n";}
		squid_admin_mysql(1, "Fatal Error: $ArticaProxyStatisticsBackupFolder permission denied",__FUNCTION__,__FILE__,__LINE__,"backup");
		KillMountPoint();
		return;		
	}
	
	@unlink("$ArticaProxyStatisticsBackupFolder/$t");
	$DeleteTables=0;
	$TotalSize=0;
	

	
	
	$mysqldump_prefix="$mysqldump $q->MYSQL_CMDLINES --skip-add-locks --insert-ignore --quote-names --skip-add-drop-table --verbose --force $q->database ";
	
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$tablename=$ligne["tablename"];
		$TableKey=$tablename;
		$day=$ligne["zDate"];
		$DayTime=strtotime("$day 00:00:00");
		echo "To backup $tablename ($day)\n";
		
		$container="$ArticaProxyStatisticsBackupFolder/squidlogs.$day.".time().".sql";
		if(is_file($container)){sleep(1);}
		$container="$ArticaProxyStatisticsBackupFolder/squidlogs.$day.".time().".sql";
		
		if(!@file_put_contents($container, time())){
			if($GLOBALS["VERBOSE"]){echo "$container permission denied\n";}
			squid_admin_mysql(0, "Fatal Error: $container permission denied", "Backup process cannot be performed",__FILE__,__LINE__);
			squid_admin_mysql(1, "Fatal Error: $container permission denied",__FUNCTION__,__FILE__,__LINE__,"backup");
			KillMountPoint();
			return;			
		}
		
		@unlink($container);
		
		$tablesB=array();
		
		if($q->TABLE_EXISTS($tablename)){$tablesB[$tablename]=true;}
		else{if($GLOBALS["VERBOSE"]){echo "$tablename no such table, continue\n";}}
		$tableTMP=date("Ymd",$DayTime)."_hour";
		if($q->TABLE_EXISTS($tableTMP)){$tablesB[$tableTMP]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tableTMP no such table, continue\n";}}
		
		$tableTMP=date("Ymd",$DayTime)."_members";
		if($q->TABLE_EXISTS($tableTMP)){$tablesB[$tableTMP]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tableTMP no such table, continue\n";}}

		$tableTMP=date("Ymd",$DayTime)."_visited";
		if($q->TABLE_EXISTS($tableTMP)){$tablesB[$tableTMP]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tableTMP no such table, continue\n";}}

		$tableTMP=date("Ymd",$DayTime)."_blocked";
		if($q->TABLE_EXISTS($tableTMP)){$tablesB[$tableTMP]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tableTMP no such table, continue\n";}}		
		
		$tableTMP="searchwordsD_".date("Ymd",$DayTime)."";
		if($q->TABLE_EXISTS($tableTMP)){$tablesB[$tableTMP]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tableTMP no such table, continue\n";}}		

		$tableTMP="UserSizeD_".date("Ymd",$DayTime)."";
		if($q->TABLE_EXISTS($tableTMP)){$tablesB[$tableTMP]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tableTMP no such table, continue\n";}}				
		
		$tableTMP="youtubeday_".date("Ymd",$DayTime)."";
		if($q->TABLE_EXISTS($tableTMP)){$tablesB[$tableTMP]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tableTMP no such table, continue\n";}}	
		
		$tableTMP="quotaday_".date("Ymd",$DayTime)."";
		if($q->TABLE_EXISTS($tableTMP)){$tablesB[$tableTMP]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tableTMP no such table, continue\n";}}
		
		$tableTMP=date("Ymd",$DayTime)."_catfam";
		if($q->TABLE_EXISTS($tableTMP)){$tablesB[$tableTMP]=true;}else{if($GLOBALS["VERBOSE"]){echo "$tableTMP no such table, continue\n";}}
		
		$c=array();
		while (list ($a, $b) = each ($tablesB)){$c[]=$a;}
		reset($tablesB);
			
		echo "Backup tables: ".@implode(", ", $c)."\n";
		
		
		
		if(count($tablesB)>0){
			
			
			$cmdline="$mysqldump_prefix".@implode(" ", $c)." >$container";
			if($GLOBALS["VERBOSE"]){echo "\n*******\n$cmdline\n*******\n";}
			$resultsZ=array();
			exec($cmdline,$resultsZ);
			
			if(!TestDump($resultsZ,$container)){
				squid_admin_mysql(1, "Fatal Error: day: Dump failed $day",__FUNCTION__,__FILE__,__LINE__,"backup");
				KillMountPoint();
				return;					
			}
			
			$size=@filesize($container);
			chdir($ArticaProxyStatisticsBackupFolder);
			
			$cmdline="$tar cfz $container.tar.gz $container 2>&1";
			$resultsZ=array();
			exec($cmdline,$resultsZ);
			while (list ($a, $b) = each ($resultsZ)){
				echo "Compress: `$b`\n";
			}
			
			if(!$unix->TARGZ_TEST_CONTAINER("$container.tar.gz")){
				squid_admin_mysql(0,"Error $container failed",__FILE__,__LINE__);
				squid_admin_mysql(1, "Fatal Error: tar $container failed",__FUNCTION__,__FILE__,__LINE__,"backup");
				@unlink($container);
				@unlink("$container.tar.gz");
				KillMountPoint();
				return;
			}			
			
			
			$TotalSize=$TotalSize+$size;
			@unlink($container);
			
			
			reset($tablesB);
			while (list ($tablename, $line) = each ($tablesB)){
				if($GLOBALS["VERBOSE"]){echo "Delete table `$tablename`\n";}
				if(!$q->DELETE_TABLE($tablename)){
					if($GLOBALS["VERBOSE"]){echo "Delete $tablename failed $q->mysql_error ...\n";}
					squid_admin_mysql(1, "Fatal Error: Delete $tablename failed $q->mysql_error ",__FUNCTION__,__FILE__,__LINE__,"backup");
					KillMountPoint();
					return;				
				}
				
				$DeleteTables++;
				
			}
			
		}
		if($GLOBALS["VERBOSE"]){echo "Delete table `$TableKey` from tables_day\n";}
		$q->QUERY_SQL("DELETE FROM tables_day WHERE tablename='$TableKey'");
		
		
	}
	
	
	
	$container="$ArticaProxyStatisticsBackupFolder/squidlogs.FULL.sql";
	$resultsZ=array();
	$cmd="$mysqldump_prefix >$container";
	exec($cmd,$resultsZ);
	chdir($ArticaProxyStatisticsBackupFolder);
	$cmdline="$tar cfz $container.tar.gz $container 2>&1";
	exec($cmdline);
	if(!$unix->TARGZ_TEST_CONTAINER("$container.tar.gz")){
		squid_admin_mysql(0,"Error $container.tar.gz, not a valid compressed file",__FILE__,__LINE__);
		squid_admin_mysql(1, "Error $container.tar.gz, not a valid compressed file",__FUNCTION__,__FILE__,__LINE__,"backup");
		@unlink("$container.tar.gz");
	}else{
		$size=@filesize($container);
		$TotalSize=$TotalSize+$size;
		@unlink("$container");
	}
	
	
	
	if($DeleteTables>0){
		$TotalSize=FormatBytes($TotalSize/1024);
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		squid_admin_mysql(2, "Success backup and purge $DeleteTables table(s) ($TotalSize)", "took:$took",__FILE__,__LINE__);
		squid_admin_mysql(1, "Success backup and purge $DeleteTables table(s) ($TotalSize) took:$took",__FUNCTION__,__FILE__,__LINE__,"backup");
		
	}
	CleanQuotas();
	KillMountPoint();
	
}

function TestDump($array,$container){
	$unix=new unix();
	return $unix->Mysql_TestDump($array, $container);
}

function events($text){

	if(function_exists("debug_backtrace")){
		$trace=@debug_backtrace();
		if(isset($trace[1])){
			$file=basename(__FILE__);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
		}
	}

	ufdbguard_admin_events($text,$function,$file,$line);
}

function PurgeOldRepos(){
	$sock=new sockets();
	$unix=new unix();
	$BackupSquidStatsUseNas=$sock->GET_INFO("BackupSquidStatsUseNas");
	if(!is_numeric($BackupSquidStatsUseNas)){$BackupSquidStatsUseNas=0;}
	$ArticaProxyStatisticsBackupFolderORG=$sock->GET_INFO("ArticaProxyStatisticsBackupFolder");
	if($ArticaProxyStatisticsBackupFolderORG==null){$ArticaProxyStatisticsBackupFolderORG="/home/artica/squid/backup-statistics";}	
	if($BackupSquidStatsUseNas==0){return;}
	$ArticaProxyStatisticsBackupFolder=GetMountPoint();
	
	if(!$GLOBALS["MountedNAS"]){return ;}
	
	$files=$unix->DirFiles("/home/artica/squid/backup-statistics");
	events("Scanning the old storage systems /home/artica/squid/backup-statistics.. ".count($files)." file(s)");
	
	
	while (list ($basename, $none) = each ($files) ){
		$filepath="$ArticaProxyStatisticsBackupFolderORG/$basename";
		if($GLOBALS["VERBOSE"]){echo "Checking \"$filepath\"\n";}
		$size=@filesize($filepath);
		if($size<20){events("Removing $filepath");@unlink($filepath);continue;}
		if(!@copy($filepath, "$ArticaProxyStatisticsBackupFolder/$basename")){
			events("copy Failed $filepath to \"$ArticaProxyStatisticsBackupFolder/$basename\" permission denied...");
			continue;
		}
		events("Move $filepath to $ArticaProxyStatisticsBackupFolder success...");
		@unlink($filepath);
	}
	
	
	
	$files=$unix->DirFiles($ArticaProxyStatisticsBackupFolderORG);
	events("Scanning the old storage systems.. ".count($files)." file(s)");
	
	
	while (list ($basename, $none) = each ($files) ){
		$filepath="$ArticaProxyStatisticsBackupFolderORG/$basename";
		if($GLOBALS["VERBOSE"]){echo "Checking \"$filepath\"\n";}
		$size=@filesize($filepath);
		if($size<20){events("Removing $filepath");@unlink($filepath);continue;}
		if(!@copy($filepath, "$ArticaProxyStatisticsBackupFolder/$basename")){
			events("copy Failed $filepath to \"$ArticaProxyStatisticsBackupFolder/$basename\" permission denied...");
			continue;
		}
		events("Move $filepath to $ArticaProxyStatisticsBackupFolder success...");
		@unlink($filepath);
	}	
	
	
}

function KillMountPoint(){
	if(!$GLOBALS["MountedNAS"]){return false;}
	$GLOBALS["MOUNT"]->umount("/mnt/BackupSquidStatsUseNas");
	$GLOBALS["MountedNAS"]=false;
	unset($GLOBALS["GetMountPoint"]);
}


function GetMountPoint(){
	if(isset($GLOBALS["GetMountPoint"])){return $GLOBALS["GetMountPoint"];}
	$users=new usersMenus();
	$sock=new sockets();
	
	$GLOBALS["GetMountPoint"]=$sock->GET_INFO("ArticaProxyStatisticsBackupFolder");
	if($GLOBALS["GetMountPoint"]==null){$GLOBALS["GetMountPoint"]="/home/artica/squid/backup-statistics";}
	$ArticaProxyStatisticsBackupDays=$sock->GET_INFO("ArticaProxyStatisticsBackupDays");

	
	$BackupSquidStatsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidStatsUseNas"));
	$BackupSquidStatsNASIpaddr=$sock->GET_INFO("BackupSquidStatsNASIpaddr");
	$BackupSquidStatsNASFolder=$sock->GET_INFO("BackupSquidStatsNASFolder");
	$BackupSquidStatsNASUser=$sock->GET_INFO("BackupSquidStatsNASUser");
	$BackupSquidStatsNASPassword=$sock->GET_INFO("BackupSquidStatsNASPassword");
	$BackupSquidStatsNASRetry=$sock->GET_INFO("BackupSquidStatsNASRetry");
	
	
	
	if($BackupSquidStatsUseNas==0){
		$BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
		if($BackupSquidLogsUseNas==1){$BackupSquidStatsUseNas=1;}
		$BackupSquidStatsNASIpaddr=$sock->GET_INFO("BackupSquidLogsNASIpaddr");
		$BackupSquidStatsNASFolder=$sock->GET_INFO("BackupSquidLogsNASFolder");
		$BackupSquidStatsNASUser=$sock->GET_INFO("BackupSquidLogsNASUser");
		$BackupSquidStatsNASPassword=$sock->GET_INFO("BackupSquidLogsNASPassword");
	
	}
	
	

	
	
	
	if(!is_numeric($BackupSquidStatsNASRetry)){$BackupSquidStatsNASRetry=0;}

	$GLOBALS["MountedNAS"]=false;
	$GLOBALS["MOUNT"]=new mount("/var/log/artica-postfix/logrotate.debug");
	if($BackupSquidStatsUseNas==0){ return $GLOBALS["GetMountPoint"];}
	
	$mountPoint="/mnt/BackupSquidStatsUseNas";
	if(!$GLOBALS["MOUNT"]->smb_mount($mountPoint,$BackupSquidStatsNASIpaddr,$BackupSquidStatsNASUser,$BackupSquidStatsNASPassword,$BackupSquidStatsNASFolder)){
		if($BackupSquidStatsNASRetry==0){
			squid_admin_mysql(0, "Unable to connect to NAS storage system (1): $BackupSquidStatsNASUser@$BackupSquidStatsNASIpaddr",__FILE__,__LINE__);
			return $GLOBALS["GetMountPoint"];
			
		}
		
		events("Unable to connect to NAS storage system (1): $BackupSquidStatsNASUser@$BackupSquidStatsNASIpaddr");
		sleep(3);
		$GLOBALS["MOUNT"]=new mount("/var/log/artica-postfix/logrotate.debug");
		 if(!$GLOBALS["MOUNT"]->smb_mount($mountPoint,$BackupSquidStatsNASIpaddr,$BackupSquidStatsNASUser,$BackupSquidStatsNASPassword,$BackupSquidStatsNASFolder)){
			squid_admin_mysql(0, "Unable to connect to NAS storage system (2): $BackupSquidStatsNASUser@$BackupSquidStatsNASIpaddr",__FILE__,__LINE__);
			events("Unable to connect to NAS storage system (2): $BackupSquidStatsNASUser@$BackupSquidStatsNASIpaddr");
			return $GLOBALS["GetMountPoint"];
		}
		
		
		
		
			
				
	}
	$GLOBALS["GetMountPoint"]="$mountPoint/backup-statistics/".$users->hostname;
	if($GLOBALS["VERBOSE"]){echo "\n ***** MOUNTED ****\n\n";}
	$GLOBALS["MountedNAS"]=true;
	PurgeOldRepos();
	
	return $GLOBALS["GetMountPoint"];
	
	
}

function removeall(){
	$unix=new unix();
	$array=array();
	$tables=ScanDays(true);
	$q=new mysql_squid_builder();
	while (list ($tablename, $line) = each ($tables)){
		$array[$tablename]=$tablename;
		
	}
	
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs' AND table_name LIKE '%'";
	$results=$q->QUERY_SQL($sql);
	if($GLOBALS["VERBOSE"]){echo $sql." => ". mysqli_num_rows($results)."\n";}
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			if(preg_match("#[0-9]+_.*?#", $ligne["c"])){
				$array[$ligne["c"]]=$ligne["c"];
				continue;
			}
			
			if(preg_match("#www_.*?#", $ligne["c"])){
				$array[$ligne["c"]]=$ligne["c"];
				continue;
			}
			
			if(preg_match("#youtube_.*?#", $ligne["c"])){
				$array[$ligne["c"]]=$ligne["c"];
				continue;
			}
			
		}
		
		while (list ($tablename, $line) = each ($array)){
			echo "removing $tablename\n";
			$q->QUERY_SQL("DROP TABLE `$tablename`");
		
		}
		

$Clean["tables_day"]=true;
$Clean["UserAgents"]=true;
$Clean["members_uid"]=true;
$Clean["tables_hours"]=true;
$Clean["visited_sites"]=true;
$Clean["visited_sites_catz"]=true;
$Clean["visited_sites_days"]=true;
$Clean["visited_sites_tot"]=true;

while (list ($tablename, $line) = each ($Clean)){
	echo "Purge $tablename\n";
	$q->QUERY_SQL("TRUNCATE TABLE `$tablename`");

}	
$cmd=$unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.squid-db.php --databasesize --force --verbose >/dev/null 2>&1";
echo $cmd."\n";
shell_exec($cmd);
	
}




function ScanDays($onlyTable=false){}

function tests_nas(){
	$sock=new sockets();
	$BackupSquidStatsUseNas=$sock->GET_INFO("BackupSquidStatsUseNas");
	$MySQLSyslogType=$sock->GET_INFO("MySQLSyslogType");
	$EnableSyslogDB=$sock->GET_INFO("EnableSyslogDB");
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}
	if(!is_numeric($MySQLSyslogType)){$MySQLSyslogType=1;}
	if(!is_numeric($BackupSquidStatsUseNas)){$BackupSquidStatsUseNas=0;}
	$users=new usersMenus();


	$mount=new mount("/var/log/artica-postfix/logrotate.debug");

	if($BackupSquidStatsUseNas==0){echo "Backup using NAS is not enabled\n";return;}
	$BackupSquidStatsNASIpaddr=$sock->GET_INFO("BackupSquidStatsNASIpaddr");
	$BackupSquidStatsNASFolder=$sock->GET_INFO("BackupSquidStatsNASFolder");
	$BackupSquidStatsNASUser=$sock->GET_INFO("BackupSquidStatsNASUser");
	$BackupSquidStatsNASPassword=$sock->GET_INFO("BackupSquidStatsNASPassword");
	$BackupSquidStatsNASRetry=$sock->GET_INFO("BackupSquidStatsNASRetry");
	if(!is_numeric($BackupSquidStatsNASRetry)){$BackupSquidStatsNASRetry=0;}

	$failed="***********************\n** FAILED **\n***********************\n";
	$success="***********************\n******* SUCCESS *******\n***********************\n";

	$mountPoint="/mnt/BackupSquidStatsUseNas";
	if(!$mount->smb_mount($mountPoint,$BackupSquidStatsNASIpaddr,
			$BackupSquidStatsNASUser,$BackupSquidStatsNASPassword,$BackupSquidStatsNASFolder)){

		if($BackupSquidStatsNASRetry==1){
			sleep(3);
			$mount=new mount("/var/log/artica-postfix/logrotate.debug");
			if(!$mount->smb_mount($mountPoint,$BackupSquidStatsNASIpaddr,$BackupSquidStatsNASUser,$BackupSquidStatsNASPassword,$BackupSquidStatsNASFolder)){
				echo "$failed\nUnable to connect to NAS storage system: $BackupSquidStatsNASUser@$BackupSquidStatsNASIpaddr\n";
				echo @implode("\n", $GLOBALS["MOUNT_EVENTS"]);
				return;
			}
		}else{
			echo "$failed\nUnable to connect to NAS storage system: $BackupSquidStatsNASUser@$BackupSquidStatsNASIpaddr\n";
			echo @implode("\n", $GLOBALS["MOUNT_EVENTS"]);
			return;
		}
	}
		

	$BackupMaxDaysDir="$mountPoint/backup-statistics/$users->hostname";

	@mkdir($BackupMaxDaysDir,0755,true);
	if(!is_dir($BackupMaxDaysDir)){
		echo "$failed$BackupSquidStatsNASUser@$BackupSquidStatsNASIpaddr/$BackupSquidStatsNASFolder/backup-statistics/$users->hostname permission denied.\n";
		$mount->umount($mountPoint);
		return;
	}

	$t=time();
	@file_put_contents("$BackupMaxDaysDir/$t", "#");
	if(!is_file("$BackupMaxDaysDir/$t")){
		echo "$failed$BackupSquidStatsNASUser@$BackupSquidStatsNASIpaddr/$BackupSquidStatsNASFolder/backup-statistics/$users->hostname/* permission denied.\n";
		$mount->umount($mountPoint);
		return;
	}
	@unlink("$BackupMaxDaysDir/$t");
	$mount->umount($mountPoint);
	echo "$success";

}
function remove_numeric_members(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		squid_admin_mysql(1, "Already executed pid $pid since {$timepid}",__FUNCTION__,__FILE__,__LINE__,"reports");
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
		return;
	}
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM members_uid WHERE uid REGEXP '^[0-9]+$'");
	
	$tables=$q->LIST_TABLES_WWWUID();
	while (list ($tablename, $ligne) = each ($tables) ){
		if(!preg_match("#^www_[0-9]+$#", $ligne)){continue;}
		$q->QUERY_SQL("DROP TABLE $tablename");
	}
	$tables=$q->LIST_TABLES_DAYS();
	while (list ($tablename, $ligne) = each ($tables) ){
		$q->QUERY_SQL("DELETE FROM $tablename WHERE uid REGEXP '^[0-9]+$'");
		
	}
	

	
	
	

	
	
	$tables=$q->LIST_TABLES_MEMBERS();
	while (list ($tablename, $ligne) = each ($tables) ){
		$q->QUERY_SQL("DELETE FROM $tablename WHERE uid REGEXP '^[0-9]+$'");
	
	}	
	
	
	
}

function backup_all(){
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		squid_admin_mysql(1, "Already executed pid $pid since {$timepid}",__FUNCTION__,__FILE__,__LINE__,"reports");
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
		return;
	}
	
	
	$mysqldump=$unix->find_program("mysqldump");
	$bzip2=$unix->find_program("bzip2");
	$directory=GetMountPoint();
	$time=time();
	$target_file="$directory/squidlogs-$time.tar.bz2";
	if(!is_dir(dirname($target_file))){@mkdir(dirname($target_file),0755,true);}
	$bzip2_cmd="| $bzip2 ";
	$Socket=" -S /var/run/mysqld/squid-db.sock -u root";
	
	$cmd="$mysqldump$Socket --single-transaction --skip-add-locks --skip-lock-tables squidlogs $bzip2_cmd> $target_file 2>&1";
	shell_exec($cmd);
}

