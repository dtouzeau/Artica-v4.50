#!/usr/bin/php -q
<?php
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
$DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
if($DisablePostGres==1){die();}
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="InfluxDB Daemon";
$GLOBALS["PROGRESS"]=false;
$GLOBALS["MIGRATION"]=false;

if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--migration#",implode(" ",$argv),$re)){$GLOBALS["MIGRATION"]=true;}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.influx.inc');
include_once(dirname(__FILE__).'/ressources/class.ftp.client.inc');

$GLOBALS["ARGVS"]=implode(" ",$argv);
if(isset($argv[1])) {
    if ($argv[1] == "--dbsize") {
        InfluxDbSize();
        exit;
    }
    if ($argv[1] == "--delete-backup") {
        DeleteBackup($argv[2]);
        exit;
    }
    if ($argv[1] == "--restore") {
        RestoreBackup($argv[2]);
        exit;
    }
    if ($argv[1] == "--ftp-validator") {
        validate_ftp();
        exit;
    }
    if ($argv[1] == "--ftp-list") {
        print_r(backup_ftp_list());
        exit;
    }
    if ($argv[1] == "--ftp-sync") {
        backup_ftp_sync();
        exit;
    }
}


backup();
exec_verif_packages_php();

function DebianVersion(){
	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];
}

function DeleteBackup($ID){
    $q=new lib_sqlite("/home/artica/SQLITE_TEMP/pg_tables.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM backup WHERE ID=$ID");
    $filename=$ligne["filename"];
    if(is_file($filename)){@unlink($filename);}
    $q->QUERY_SQL("DELETE FROM backup WHERE ID=$ID");
}
function RestoreBackup($sourcepath){
    $InFluxBackupDatabaseDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("InFluxBackupDatabaseDir");
    if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}

    if(!is_file($sourcepath)){
        build_progress_idb("{failed} $sourcepath no such file",110);
        return;
    }

    if(!is_dir($InFluxBackupDatabaseDir)){@mkdir($InFluxBackupDatabaseDir,0755,true);}
    $DestPath="$InFluxBackupDatabaseDir/snapshot.imported.".date("Y-m-d-H-i").".gz";
    if(is_file($DestPath)){@unlink($DestPath);}
    build_progress_idb("{copy} $sourcepath",30);

    if(!@copy($sourcepath,$DestPath)){
        build_progress_idb("{copy} $sourcepath {failed}",110);
        @unlink($sourcepath);
        @unlink($DestPath);
        return;
    }
    build_progress_idb("{remove} $sourcepath",50);
    @unlink($sourcepath);
    build_progress_idb("{scanning} $InFluxBackupDatabaseDir",80);
    ScanBackup();
    build_progress_idb("{done}",100);
}
function backup_syslog($text){
    if(!function_exists("openlog")){return false;}
    openlog("postgres", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}
function backup(){
	build_progress_idb("{backup_database}",20);
	$unix=new unix();
	$targetFilename="/home/ArticaStatsBackup/backup.db";
	$su=$unix->find_program("su");
	@mkdir("/home/ArticaStatsBackup",0777,true);
	@chmod("/home/ArticaStatsBackup",0777);
	if(is_file($targetFilename)){@unlink($targetFilename);}
	$BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
	$InFluxBackupDatabaseDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("InFluxBackupDatabaseDir");
	if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}
	$CompressFileName="$InFluxBackupDatabaseDir/snapshot.".date("Y-m-d-H-i").".gz";
	$ContainersToDelete=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostGresBackupMaxContainers"));
	if($ContainersToDelete==0){$ContainersToDelete=3;}
	
	@mkdir($InFluxBackupDatabaseDir,0755,true);
	
	
	if(is_file($CompressFileName)){
        backup_syslog("Backup: $CompressFileName already exists, aborting..");
		build_progress_idb("{backup_database} already exists",110);
	}

    backup_syslog("Backup: Backuping to $targetFilename");
	$cmdline="$su -c \"/usr/local/ArticaStats/bin/pg_dumpall -c --if-exists -S ArticaStats -f $targetFilename -h /var/run/ArticaStats\" ArticaStats";
	echo $cmdline."\n";
	$results[]=$cmdline;
	exec($cmdline,$results);
	build_progress_idb("{backup_database}",30);

	if(!is_file($targetFilename)){
        backup_syslog("Backup: Backup failed $targetFilename no such file");
		echo "$targetFilename No such file\n";
		foreach ($results as $num=>$val){
            backup_syslog("Backup: $val");
			echo "$val\n";
			
		}
		squid_admin_mysql(0, "Snapshot BigData database {failed} ( processing )", @implode("\n", $results),__FILE__,__LINE__);
		build_progress_idb("{backup_database} {failed}",110);
		return;
	}



    backup_syslog("Backup: Compressing $targetFilename to $CompressFileName");
	build_progress_idb("{compressing}",50);
	echo "Compress $targetFilename\n";
	echo "Destination $CompressFileName\n";
	if(!$unix->compress($targetFilename, $CompressFileName)){
        backup_syslog("Backup: Compressing $targetFilename failed");
		build_progress_idb("{compressing} {failed}",110);
		squid_admin_mysql(0, "Snaphost BigData database {failed} ( compress )", null,__FILE__,__LINE__);
		@unlink($targetFilename);
		@unlink($CompressFileName);
		return;
		
	}
	@unlink($targetFilename);
	$sizeBytes=@filesize($CompressFileName);
	$size=FormatBytes($sizeBytes/1024);
	squid_admin_mysql(2, "Backup [".basename($CompressFileName)."] BigData database ($size) done", null,__FILE__,__LINE__);
    backup_syslog("Backup: Compressing $targetFilename Success");

    $q=new lib_sqlite("/home/artica/SQLITE_TEMP/pg_tables.db");

    $sql="CREATE TABLE IF NOT EXISTS `backup` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`filename` TEXT,
		`filesize` integer,
		`zdate` INTEGER
    )";

    $q->QUERY_SQL($sql);
    $t=time();
    $sql="INSERT INTO backup (filename,filesize,zdate) VALUES ('$CompressFileName','$sizeBytes','$t')";
    $q->QUERY_SQL($sql);

	build_progress_idb("{scanning}",80);
	$unix=new unix();
	$ls=$unix->find_program("ls");
	$head=$unix->find_program("head");
	$xargs=$unix->find_program("xargs");
	
	
	// ************************ REMOVE OLD FILES ************************
	$tmpfile=$unix->FILE_TEMP().".sh";
	$sh=array();
	$sh[]="#! /bin/sh";
	$sh[]="$ls -d -1tr \"$InFluxBackupDatabaseDir/\"* | $head -n -$ContainersToDelete|$xargs -d '\n' rm -f";
	$sh[]="";
	@file_put_contents($tmpfile, @implode("\n", $sh));
	@chmod($tmpfile,0755);
	system($tmpfile);
	@unlink($tmpfile);
	// ************************ ************************ *****************
	
	if($BackupSquidLogsUseNas==1){
        backup_syslog("Backup: move containers to NAS");
		$php=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		system("$nohup $php /usr/share/artica-postfix/exec.squid.rotate.php --backup-postgres");
	}

	ScanBackup();
	InfluxDbSize();
	build_progress_idb("{backup_database} {success}",100);
}

function ScanBackup(){

    $PostgreSQLFTPEnable        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPEnable"));
	$InFluxBackupDatabaseDir    = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("InFluxBackupDatabaseDir");
	if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}
	$q=new lib_sqlite("/home/artica/SQLITE_TEMP/pg_tables.db");

    $sql="CREATE TABLE IF NOT EXISTS `backup` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`filename` TEXT,
		`filesize` integer,
		`zdate` INTEGER
    )";

    $q->QUERY_SQL($sql);
    $unix=new unix();


	$results=$q->QUERY_SQL("SELECT * FROM backup");
	foreach ($results as $index=>$ligne){
	    $filename=$ligne["filename"];
	    $ID=$ligne["ID"];
	    $INDB[$filename]=$ligne["ID"];
        if(!is_file($filename)){
            backup_syslog("Backup: missing container $filename, remove it from table");
            $q->QUERY_SQL("DELETE FROM backup WHERE ID=$ID");
            continue;
        }


    }
    $ARRAY=array();
    $patnz=$unix->DirFiles($InFluxBackupDatabaseDir,"\.gz$");
	foreach ($patnz as $filepath=>$none){
		$filepath="$InFluxBackupDatabaseDir/$filepath";
		if(isset($INDB[$filepath])){continue;}
		$filesize=@filesize($filepath);
		$filetime=filemtime($filepath);
		$ARRAY[$filepath]=$filesize;
        backup_syslog("Backup: container $filepath, {$filesize}bytes");
        $sql="INSERT INTO backup (filename,filesize,zdate) VALUES ('$filepath','$filesize','$filetime')";
        $q->QUERY_SQL($sql);
	}

	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("InfluxDBRestoreArray", serialize($ARRAY));
	if($PostgreSQLFTPEnable==1){
        backup_syslog("Backup: synchronize to FTP");
        backup_ftp_sync();}
	
}

function exec_verif_packages_php(){
    $unix=new unix();
    if(!is_file("/etc/artica-postfix/VERIF_PKG_TIME")){
        $php=$unix->LOCATE_PHP5_BIN();
        $nohup=$unix->find_program("nohup");
        shell_exec("$nohup $php /usr/share/artica-postfix/exec.verif.packages.php >/dev/null 2>&1 &");

    }else{
        $time=$unix->file_time_min("/etc/artica-postfix/VERIF_PKG_TIME");
        if($time>10080){
            $php=$unix->LOCATE_PHP5_BIN();
            $nohup=$unix->find_program("nohup");
            shell_exec("$nohup $php /usr/share/artica-postfix/exec.verif.packages.php >/dev/null 2>&1 &");
        }
    }
}


function InfluxDbSize(){
    $unix=new unix();
    exec_verif_packages_php();
    if(system_is_overloaded()){die();}
	$dir="/home/ArticaStatsDB";
	$size=$unix->DIRSIZE_KO($dir);
	$partition=$unix->DIRPART_INFO($dir);
	echo "Directory: $dir ($size KB)\n";
	$TOT=$partition["TOT"];
	$percent=($size/$TOT)*100;
	$percent=round($percent,3);
	
	
	echo "$dir: $size Partition $TOT\n";

	$ARRAY["PERCENTAGE"]=$percent;
	$ARRAY["SIZEKB"]=$size;
	$ARRAY["PART"]=$TOT;

    echo "Percent: {$percent}%\n";
	
	if($GLOBALS["VERBOSE"]){print_r($ARRAY);};
	@unlink(PROGRESS_DIR."/InfluxDB.state");
	@file_put_contents(PROGRESS_DIR."/InfluxDB.state", serialize($ARRAY));
	
}
function build_progress_idb($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/postgres.backup.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function build_progress_ftp($text,$pourc){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/postgres.ftp.validator.progress", serialize($array));
    @chmod("/usr/share/artica-postfix/ressources/logs/postgres.ftp.validator.progress",0755);

}

function backup_ftp_sync(){
    $unix                       =  new unix();
    $InFluxBackupDatabaseDir    = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("InFluxBackupDatabaseDir");
    $PostgreSQLFTPServer        = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPServer"));
    $PostgreSQLFTPPassive       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPPassive"));
    $PostgreSQLFTPTLS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPTLS"));
    $PostgreSQLFTPDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPDir"));
    $PostgreSQLFTPUser=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPUser"));
    $PostgreSQLFTPPass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPPass"));
    if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}

    $patnz=$unix->DirFiles($InFluxBackupDatabaseDir,"\.gz$");

    $List=backup_ftp_list();

    if(count($List)==0){
        backup_syslog("Backup: synchronize to FTP, FTP synchronize task aborted due to error");
        squid_admin_mysql(0,"PostgreSQL backup, FTP synchronize task aborted due to error",null,__FILE__,__LINE__);
        return false;
    }

    $ftp=new ftp_client($PostgreSQLFTPServer,$PostgreSQLFTPUser,$PostgreSQLFTPPass,21,5);
    if($PostgreSQLFTPPassive==1){$ftp->setPassive();}
    if($PostgreSQLFTPTLS==1){$ftp->SetTLS();}

    if(!$ftp->connect()) {
        backup_syslog("Backup: FTP Backup Unable to connect with error: $ftp->error");
        squid_admin_mysql(0,"PostgreSQL FTP Backup Unable to connect with error: $ftp->error",null,__FILE__,__LINE__);
        return array();
    }
    if(!$ftp->cd($PostgreSQLFTPDir)){
        backup_syslog("Backup: FTP Backup Unable to enter into $PostgreSQLFTPDir: $ftp->error");
        squid_admin_mysql(0,"PostgreSQL FTP Backup Unable to enter into $PostgreSQLFTPDir: $ftp->error",null,__FILE__,__LINE__);
        return array();
    }

    foreach ($List as $filename){
        if(!preg_match("#\.gz$#",$filename)){continue;}
        $ON_FTP[$filename]=true;

    }
    $fsize=0;
    foreach ($patnz as $filename=>$none){
        $filepath="$InFluxBackupDatabaseDir/$filename";
        if(isset($ON_FTP[$filename])){
            echo "$filename already uploaded...\n";
            continue;
        }
        echo "Uploading  $filepath to $PostgreSQLFTPDir/$filename...\n";
        backup_syslog("Backup: FTP Uploading to $PostgreSQLFTPDir/$filename");
        if(!$ftp->put($filepath,$filename)){
            echo "[PUT] $PostgreSQLFTPDir/$filename $ftp->error\n";
            backup_syslog("Backup: $PostgreSQLFTPDir/$filename permission denied  or timeout");
            build_progress_ftp("PostgreSQL FTP Backup [PUT] $PostgreSQLFTPDir/$filename {permission_denied}  {or} {timeout}",110);
            $ftp->close();
           return false;
        }
        $fsize=$fsize+filesize($filepath);
    }

    $categories_files=backup_list_categories();
    if(count($categories_files)==0){
        if($fsize>0){
            $fsizeK=$fsize/1024;
            $fsizeM=round($fsizeK/1024,2);
            backup_syslog("Backup: FTP Backup Success Uploading {$fsizeM}MB of files on $PostgreSQLFTPServer");
            squid_admin_mysql(2,"PostgreSQL FTP Backup Success Uploading {$fsizeM}MB of files on $PostgreSQLFTPServer",
                null,__FILE__,__LINE__);
        }
        $ftp->close();
        return true;
    }

    echo "I'm here:".$ftp->pwd()."\n";
    if(!$ftp->cd("categories_backup")) {
        echo "Cannot cd to categories_backup, try to create the directory\n";
        $ftp->mkdir("categories_backup");

        if (!$ftp->cd("categories_backup")) {
            echo "I'm here:" . $ftp->pwd() . "\n";
            echo "Cannot enter into $PostgreSQLFTPDir/categories_backup, permission denied\n";
            $ftp->close();
            if($fsize>0){
                $fsizeK=$fsize/1024;
                $fsizeM=round($fsizeK/1024,2);
                squid_admin_mysql(2,"PostgreSQL FTP Backup Success Uploading {$fsizeM}MB of files on $PostgreSQLFTPServer",
                    null,__FILE__,__LINE__);
            }
            return true;
        }
    }

    foreach ($categories_files as $srcpath=>$none){
        $TargetFileName=basename($srcpath);
        echo "Uploading $srcpath -> $PostgreSQLFTPDir/categories_backup/$TargetFileName\n";
        if(!$ftp->put($srcpath,$TargetFileName)){
            echo "Uploading $PostgreSQLFTPDir/categories_backup/$TargetFileName failed\n";
            $ftp->close();
            if($fsize>0){
                $fsizeK=$fsize/1024;
                $fsizeM=round($fsizeK/1024,2);
                squid_admin_mysql(2,"PostgreSQL FTP Backup Success Uploading {$fsizeM}MB of files on $PostgreSQLFTPServer",
                    null,__FILE__,__LINE__);
            }
            return true;
        }

        $fsize=$fsize+filesize($srcpath);

    }

    if($fsize>0){
        $fsizeK=$fsize/1024;
        $fsizeM=round($fsizeK/1024,2);
        squid_admin_mysql(2,"PostgreSQL FTP Backup Success Uploading {$fsizeM}MB of files on $PostgreSQLFTPServer",
            null,__FILE__,__LINE__);
    }

    $ftp->close();
    return true;


}

function backup_list_categories(){
    $BaseWorkDir="/home/artica/categories_backup";
    if(!is_dir($BaseWorkDir)){return array();}
    $main=array();
    $handle = opendir($BaseWorkDir);
    if(!$handle){return false;}
    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        $targetFile="$BaseWorkDir/$filename";
        if(is_dir($targetFile)){continue;}
        $main[$targetFile]=true;
    }
    return $main;
}

function backup_ftp_list(){
    $PostgreSQLFTPServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPServer"));
    $PostgreSQLFTPPassive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPPassive"));
    $PostgreSQLFTPTLS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPTLS"));
    $PostgreSQLFTPDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPDir"));
    $PostgreSQLFTPUser=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPUser"));
    $PostgreSQLFTPPass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPPass"));

    $ftp=new ftp_client($PostgreSQLFTPServer,$PostgreSQLFTPUser,$PostgreSQLFTPPass,21,5);
    if($PostgreSQLFTPPassive==1){$ftp->setPassive();}
    if($PostgreSQLFTPTLS==1){$ftp->SetTLS();}

    if(!$ftp->connect()) {
        squid_admin_mysql(0,"PostgreSQL FTP Backup Unable to connect with error: $ftp->error",null,__FILE__,__LINE__);
        return array();
    }
    if(!$ftp->cd($PostgreSQLFTPDir)){
        squid_admin_mysql(0,"PostgreSQL FTP Backup Unable to enter into $PostgreSQLFTPDir: $ftp->error",null,__FILE__,__LINE__);
        return array();
    }

    $FINAL=$ftp->ls();
    $ftp->close();
    return $FINAL;
}

function validate_ftp(){
    $unix=new unix();
    $FTP_PASSIVE_TEXT=null;
    $TLS_TEXT=null;
    
    $PostgreSQLFTPEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPEnable"));
    $PostgreSQLFTPServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPServer"));
    $PostgreSQLFTPPassive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPPassive"));
    $PostgreSQLFTPTLS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPTLS"));
    $PostgreSQLFTPDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPDir"));
    $PostgreSQLFTPUser=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPUser"));
    $PostgreSQLFTPPass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostgreSQLFTPPass"));

    if($PostgreSQLFTPEnable==0){
        build_progress_ftp("{feature_disabled}",100);
        return false;
    }

    if($PostgreSQLFTPServer==null){
        build_progress_ftp("No FTP server",110);
        return false;
    }

    $FTP_SERVER=$PostgreSQLFTPServer;
    $TARGET_DIR=$PostgreSQLFTPDir;
    $USERNAME=$PostgreSQLFTPUser;
    $PASSWORD=$PostgreSQLFTPPass;
    $FTP_PASSIVE=$PostgreSQLFTPPassive;
    $TLS=$PostgreSQLFTPTLS;

    echo "PASSIVE........: $PostgreSQLFTPPassive\n";

    if($FTP_PASSIVE==1){
        echo "Passive mode is enabled...\n";
        $FTP_PASSIVE_TEXT=" with PASSIVE";
    }
    if($TLS==1){
        $TLS_TEXT=" with TLS";
    }

    if(strlen($PASSWORD)==0){
        echo "Warning, no password as been defined...\n";
    }

    echo "Connecting{$FTP_PASSIVE_TEXT}{$TLS_TEXT} to $USERNAME@ftp://$FTP_SERVER/$TARGET_DIR...\n";
    build_progress_ftp("{connecting}: $FTP_SERVER",20);

    if($GLOBALS["VERBOSE"]){
        echo "Username: $USERNAME\n";
        echo "Password: $PASSWORD\n";
        echo "Target Directory: $TARGET_DIR \n";
    }


    $ftp=new ftp_client($FTP_SERVER,$USERNAME,$PASSWORD,21,5);
    if($FTP_PASSIVE==1){
        echo "PASSIVE MODE Enabled...\n";
        $ftp->setPassive();
    }
    if($TLS==1){
        $ftp->SetTLS();
    }
    build_progress_ftp("{connecting}: TCP $FTP_SERVER:21",25);
    $fp=@fsockopen($FTP_SERVER, 21, $errno, $errstr, 4);
    if(!$fp){
        echo "Unable to TCP connect $FTP_SERVER:21 with error $errno: $errstr\n";
        build_progress_ftp("TCP {failed}",110);
        die();
    }


    build_progress_ftp("{connecting}: FTP $FTP_SERVER:21",30);
    if(!$ftp->connect()) {
        echo "Unable to connect with error: $ftp->error\n";
        build_progress_ftp("FTP {failed}",110);
        die();
    }

    build_progress_ftp("{connected}: $FTP_SERVER, {checking} $TARGET_DIR",40);
    echo "Current directory: ".$ftp->pwd()."\n";


    if(!$ftp->cd($TARGET_DIR)){
        echo "[CD] $TARGET_DIR $ftp->error\n";
        if(!$ftp->mkdir($TARGET_DIR)){
            echo "Unable to create directory $TARGET_DIR\n";
            build_progress_ftp("[CD] $TARGET_DIR {permission_denied}",110);
            $ftp->close();
            die();
        }

        if(!$ftp->cd($TARGET_DIR)){
            echo "Unable to enter into directory $TARGET_DIR\n";
            build_progress_ftp("[CD] $TARGET_DIR {permission_denied}",110);
            $ftp->close();
            die();
        }

    }

    echo "Current directory: ".$ftp->pwd()."\n";


    build_progress_ftp("{connected}: $FTP_SERVER, {checking} $TARGET_DIR",45);
    $tmpfile=$unix->FILE_TEMP();
    $data=null;
    echo "Build temporary file: $tmpfile\n";
    for($i=0;$i<10000;$i++){
        $data=$data."AAAAAAAAAAAAAAAAAA";
    }

    $datal=strlen($data);
    $datal=$datal/1024;
    $datal=$datal/1024;
    echo "Temporary file size: $datal Mb\n";
    @file_put_contents($tmpfile,$data);
    build_progress_ftp("{connected}: $FTP_SERVER, {checking} $TARGET_DIR",50);
    echo "Uploading $tmpfile to ".$ftp->pwd()."\n";
    if(!$ftp->put($tmpfile,basename($tmpfile))){
        echo "[PUT] $TARGET_DIR $ftp->error\n";
        build_progress_ftp("[PUT] $TARGET_DIR {permission_denied}  {or} {timeout}",110);
        $ftp->close();
        @unlink($tmpfile);
        die();
    }

    echo "Temporary $tmpfile uploaded - success, remove uploaded file now...\n";
    @unlink($tmpfile);
    build_progress_ftp("{connected}: $FTP_SERVER, {checking} DEL",60);
    if(!$ftp->delete(basename($tmpfile))){
        echo "[DEL] $TARGET_DIR $ftp->error\n";
        build_progress_ftp("[DEL] $TARGET_DIR {permission_denied}",110);
        $ftp->close();
        die();
    }

    echo "Creating temporary folder $TARGET_DIR/".basename($tmpfile);
    build_progress_ftp("{connected}: $FTP_SERVER, {checking} MKDIR",70);
    if(!$ftp->mkdir(basename($tmpfile))){
        echo "[MKDIR] $TARGET_DIR $ftp->error\n";
        build_progress_ftp("[MKDIR] $TARGET_DIR {permission_denied}",110);
        $ftp->close();
        die();

    }
    echo "Removing temporary folder $TARGET_DIR/".basename($tmpfile);
    build_progress_ftp("{connected}: $FTP_SERVER, {checking} RMDIR",80);
    if(!$ftp->rmdir(basename($tmpfile))){
        echo "[RMDIR] $TARGET_DIR $ftp->error\n";
        build_progress_ftp("[RMDIR] $TARGET_DIR {permission_denied}",110);
        $ftp->close();
        die();
    }

    print_r($ftp->ls());

    $ftp->close();
    build_progress_ftp("{success}",100);

}
?>