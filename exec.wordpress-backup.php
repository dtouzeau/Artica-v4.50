<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["SCHEDULE_ID"]=0;if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.backup.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");


if($argv[1]=="--sizes-backup"){sizes_backup();exit();}
if($argv[1]=="--exec"){start();exit();}
if($argv[1]=="--dirs"){ScanDirs();exit();}
if($argv[1]=="--remove-dirs"){RemoveDirs();exit();}
if($argv[1]=="--ftp"){ftp_backup();exit();}
if($argv[1]=="--export"){ExportSingleWebsite($argv[2]);exit();}
if($argv[1]=="--import"){ImportSingleWebsite($argv[2]);exit();}



function start(){
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$unix=new unix();
	$me=basename(__FILE__);
	
	if($unix->process_exists(@file_get_contents($pidfile),$me)){
		if($GLOBALS["VERBOSE"]){echo " --> Already executed.. ". @file_get_contents($pidfile). " aborting the process\n";}
		squid_admin_mysql(2, "--> Already executed.. ". @file_get_contents($pidfile). " aborting the process", __FUNCTION__, __FILE__, __LINE__, "zarafa");
		exit();
	}
	
	@file_put_contents($pidfile, getmypid());
	
	
	$WordpressBackupParams=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WordpressBackupParams")));
	if(!isset($WordpressBackupParams["FTP_ENABLE"])){$WordpressBackupParams["FTP_ENABLE"]=0;}
	if(!isset($WordpressBackupParams["DEST"])){$WordpressBackupParams["DEST"]="/home/wordpress-backup";}
	if($WordpressBackupParams["DEST"]==null){$WordpressBackupParams["DEST"]="/home/wordpress-backup";}
	
	
	ScanFreeWebs($WordpressBackupParams);
	$t=time();
	build_progress_fullback("{backup} FTP ?",95);
	ftp_backup($WordpressBackupParams);
	sizes_backup();
	build_progress_fullback("{done}",100);
}


function ScanFreeWebs($WordpressBackupParams){
	
	
	$sql="SELECT * FROM freeweb WHERE groupware='WORDPRESS'";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		build_progress_fullback("MySQL Failed..",110);
		return;
	}
	@mkdir($WordpressBackupParams["DEST"]);
	build_progress_fullback("{starting}..",10);
	if($GLOBALS["OUTPUT"]){echo "Destination {$WordpressBackupParams["DEST"]}\n";}
	$countMax=mysqli_num_rows($results);
	$i=1;
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$servername=$ligne["servername"];
		$perc=round($i/$countMax)*100;
		$percT=10;
		if($perc>10){
			$percT=$perc;
		}
		if($perc>95){$percT=95;}
		build_progress_fullback("$servername {backup} MySQL",$percT);
		mysql_backup($WordpressBackupParams,$servername);
		build_progress_fullback("$servername {backup} {directory}",$percT);
		directory_backup($WordpressBackupParams,$servername);
		$BaseWorkDir=$WordpressBackupParams["DEST"]."/$servername/".date("Y-m-d-H")."h";
		@file_put_contents("$BaseWorkDir/config.serialize", base64_encode(serialize($ligne)));
		build_progress_fullback("$servername {backup} {success}",$percT);
		$i++;
	}
	build_progress_fullback("{backup} {success}",95);
	
}

function build_progress_fullback($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/wordpress.fullbackup.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/wordpress.fullbackup.progress",0755);

}

function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}

function ImportSingleWebsite($filename){
	$unix=new unix();
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.import.$filename.progress";
	$password=null;
	$gzip=$unix->find_program("gzip");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$gunzip=$unix->find_program("gunzip");
	$mysql=$unix->find_program("mysql");
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress("$filename: Checking",20);
	$content_file=dirname(__FILE__)."/ressources/conf/upload/$filename";
	echo "Checking $content_file\n";
	if(!is_file($content_file)){
		build_progress("$filename: $content_file no such file",110);
		return;
	}
	echo "TMP_FILE:".$unix->TEMP_DIR()."/".time()."\n";
	$TMP_PATH=$unix->TEMP_DIR()."/".time();

	@mkdir($TMP_PATH,0755,true);
	@copy($content_file, "$TMP_PATH/$filename");
	
	$serialize="$TMP_PATH/config.serialize";
	
	echo "serialize:".$serialize."\n";
	
	
	
	//@unlink($content_file);
	build_progress("$filename: {extracting}",30);
	echo "Extracting $TMP_PATH/$filename to $TMP_PATH\n";
	
	system("tar xvf $TMP_PATH/$filename -C $TMP_PATH/");
	if(!is_file("$TMP_PATH/wordpress.tar.gz")){
		build_progress("$filename: wordpress.tar.gz no such file",110);
		return;
	}
	if(!is_file("$TMP_PATH/database.gz")){
		build_progress("$filename: database.gz no such file",110);
		return;
	}	
	
	
	$size=@filesize($serialize);
	echo "Checking $serialize {$size}Bytes\n";
	if(!is_file("$serialize")){
		build_progress("$filename: config.seralize no such file",110);
		return;
	}	
	
	build_progress("$filename: Extracting parameters..",30);
	$content=@file_get_contents($serialize);
	
	$content=unserialize(base64_decode($content));
	print_r($content);
	
	
	if(!isset($content["servername"])){
		build_progress("$filename: config.seralize no such servername key",110);
		return;
	}
	$ADD=false;
	$q=new mysql();
	$servername=$content["servername"];
	
	while (list ($index, $data) = each ($content) ){
		$fieldAdds[]="`$index`";
		$fieldAddsVals[]="'".mysql_escape_string2($data)."'";
		$FieldsEdit[]="`$index` = '".mysql_escape_string2($data)."'";
		
		
	}
	
	$sqlADD="INSERT IGNORE INTO freeweb (".@implode(",", $fieldAdds).") VALUES (".@implode(",", $fieldAddsVals).")";
	$sqlEdit="UPDATE freeweb SET ".@implode(",", $FieldsEdit)." WHERE servername='$servername'";
	
	$ligne=@mysqli_fetch_array($q->QUERY_SQL("SELECT * from freeweb WHERE servername='$servername'","artica_backup"));
	if(!$q->ok){
		build_progress("$filename: {failed}",110);
		echo "Fatal $q->mysql_error SELECT * from freeweb WHERE servername='$servername'\n";
		return;
	}
	
	$sql=$sqlEdit;
	if(trim($ligne["servername"])==null){$ADD=true;}
	if($ADD){$sql=$sqlADD;}
	build_progress("$filename: importing parameters..",35);
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		build_progress("$filename: importing parameters {failed}",110);
		echo "Fatal $q->mysql_error\n";
		return;
	}
	
	$free=new freeweb($servername);
	
	if(!$free->patchTable()){
		build_progress("$filename: Patching tables {failed}",110);
		echo "Fatal $q->mysql_error\n";
		
		$q->QUERY_SQL("DELETE from freeweb WHERE servername='$servername'","artica_backup");
		return;
	}
	
	$WORKDIR=$free->www_dir;
	$database=$free->mysql_database;
	
	//$WORKDIR=$content["www_dir"];
	//$database=$content["mysql_database"];
	
	echo '$WORKDIR:'.$WORKDIR."\n";
	echo '$database:'.$database."\n";
	
	
	build_progress("$filename: Restoring $WORKDIR..",40);
	@mkdir($WORKDIR,0755,true);
	system("tar xf $TMP_PATH/wordpress.tar.gz -C $WORKDIR/");
	build_progress("$filename: Restoring $WORKDIR OK..",45);
	
	build_progress("$filename: Restoring DATABASE $database..",50);
	
	shell_exec("$gunzip -c $TMP_PATH/database.gz >$TMP_PATH/database.sql");
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	$t=time();
	
	
	//echo "$mysql --force -u root{$password} -S /var/run/mysqld/mysqld.sock -e 'CREATE DATABASE IF NOT EXISTS $database'\n";
	exec("$mysql --force -u root{$password} -S /var/run/mysqld/mysqld.sock -e 'CREATE DATABASE IF NOT EXISTS $database' 2>&1",$results);
	//echo "$mysql --force -u root{$password} -S /var/run/mysqld/mysqld.sock $database < $TMP_PATH/database.sql\n";
	exec("$mysql --force -u root{$password} -S /var/run/mysqld/mysqld.sock $database < $TMP_PATH/database.sql 2>&1",$results);
	
	foreach ($results as $index=>$line){
		$line=trim($line);
		if($line==null){continue;}
		echo "$line\n";
		if(preg_match("^ERROR\s+[0-9]+#",$line)){
			build_progress("$filename: Restoring DATABASE $database {failed}",110);
			return;
		}
	
	}
	
	
	
	build_progress("$filename: Removing temporary directory",55);
	shell_exec("$rm -rf $TMP_PATH");
	build_progress("$filename: Reconfiguring $servername",55);
	system("$php /usr/share/artica-postfix/exec.wordpress.php \"$servername\"");
	build_progress("$filename: Adding $servername to web service",60);
	build_progress("$filename: $servername {done}",100);
	@unlink($content_file);
}


function ExportSingleWebsite($servername){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.export.$servername.progress";
	$unix=new unix();
	$DBADD=false;
	$mysqldump=$unix->find_program("mysqldump");
	$q=new mysql();
	$free=new freeweb($servername);
	$gzip=$unix->find_program("gzip");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$database=$free->mysql_database;
	if($database==null){$database=$free->CreateDatabaseName();$DBADD=true;}
	build_progress("$servername: Backup database $database",20);
	if(!$q->DATABASE_EXISTS($database)){
		build_progress("Backup $servername: database $database Failed ( No such database )",110);
		return false;
	}
	
	if($DBADD){
		$free->mysql_database=$database;
		$free->CreateSite(true);
		$free=new freeweb($servername);
	}
	
	$TMP_PATH=$unix->TEMP_DIR();
	$BaseWorkDir="$TMP_PATH/$servername";
	@mkdir("$BaseWorkDir",0755,true);
	
	$q=new mysql();
	$ligneDump=@mysqli_fetch_array($q->QUERY_SQL("SELECT * from freeweb WHERE servername='$servername'","artica_backup"));
	while (list ($index, $data) = each ($ligneDump) ){
		if(is_numeric($index)){continue;}
		echo "Dumping $index = $data\n";
		$TODUMP[$index]=$data;
		
	}
	
	if(count($TODUMP)==0){
		build_progress("Dumping parameters failed",110);
		return;
	}
	
	@file_put_contents("$BaseWorkDir/config.serialize", base64_encode(serialize($TODUMP)));
	
	$TODUMP=unserialize(base64_decode(@file_get_contents("$BaseWorkDir/config.serialize")));
	if(count($TODUMP)==0){
		build_progress("Dumping parameters failed",110);
		return;
	}
	build_progress("Dumping parameters $BaseWorkDir/config.serialize success",22);
	$nice=$unix->EXEC_NICE();
	$q=new mysql();
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	
	$t=time();
	$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password $database");
	$cmdline="$prefix | $gzip > $BaseWorkDir/database.gz";
	shell_exec($cmdline);
	
	$took=$unix->distanceOfTimeInWords($t,time());
	$size=FormatBytes(@filesize("$BaseWorkDir/database.gz")/1024);
	build_progress("Backup database $database $size",25);
	$WORKDIR=$free->www_dir;
	build_progress("Backup directory $WORKDIR",25);
	echo "Backup directory $WORKDIR";
	if(!is_dir($WORKDIR)){
		build_progress("Backup directory $WORKDIR Failed ( No such directory )",110);
		return false;
	}
	chdir($WORKDIR);
	system("$nice $tar cfz $BaseWorkDir/wordpress.tar.gz *");
	$took=$unix->distanceOfTimeInWords($t,time());
	$size=FormatBytes(@filesize("$BaseWorkDir/wordpress.tar.gz")/1024);	
	build_progress("Backup directory $WORKDIR $size",50);
	sleep(4);
	build_progress("Creating container...",50);
	chdir($BaseWorkDir);
	@mkdir("/home/artica/wordress-exported",0755,true);
	if(is_file("/home/artica/wordress-exported/$servername.tar.gz")){@unlink("/home/artica/wordress-exported/$servername.tar.gz");}
	system("$nice $tar cvfz /home/artica/wordress-exported/$servername.tar.gz *");
	sleep(4);
	build_progress("Cleaning...",95);
	chdir("/root");
	shell_exec("$rm -rf $BaseWorkDir");
	build_progress("Creating container done...",100);
	
	
	
}


function mysql_backup($WordpressBackupParams,$servername){
	$unix=new unix();
	$mysqldump=$unix->find_program("mysqldump");
	$q=new mysql();
	$free=new freeweb($servername);
	$password=null;
	$gzip=$unix->find_program("gzip");
	$database=$free->mysql_database;
	echo "Backup database $database";
	if(!$q->DATABASE_EXISTS($database)){
		apache_admin_mysql(0, "$servername cannot backup a non-existent database $database", null,__FILE__,__LINE__);
		return false;
	}
	$BaseWorkDir=$WordpressBackupParams["DEST"]."/$servername/".date("Y-m-d-H")."h";
	@mkdir("$BaseWorkDir",0755,true);
	$nice=$unix->EXEC_NICE();
	$q=new mysql();
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	
	$t=time();
	$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password $database");
	$cmdline="$prefix | $gzip > $BaseWorkDir/database.gz";
	shell_exec($cmdline);	
	
	$took=$unix->distanceOfTimeInWords($t,time());
	$size=FormatBytes(@filesize("$BaseWorkDir/database.gz")/1024);
	if($GLOBALS["OUTPUT"]){echo "$database MySQL Took $took gz = $size\n";}
	
	apache_admin_mysql(2, "$servername database $database backuped $size (took $took)", null,__FILE__,__LINE__);
	
	
	
	
	
}
function directory_backup($WordpressBackupParams,$servername){
	$unix=new unix();
	$tar=$unix->find_program("tar");
	$q=new mysql();
	$free=new freeweb($servername);
	$gzip=$unix->find_program("gzip");
	$WORKDIR=$free->www_dir;
	echo "Backup directory $WORKDIR";
	if(!is_dir($WORKDIR)){
		apache_admin_mysql(0, "$servername cannot backup a non-existent directory $WORKDIR", null,__FILE__,__LINE__);
		return false;
	}
	$BaseWorkDir=$WordpressBackupParams["DEST"]."/$servername/".date("Y-m-d-H")."h";
	@mkdir("$BaseWorkDir",0755,true);
	$nice=$unix->EXEC_NICE();
	$t=time();
	chdir($WORKDIR);
	if($GLOBALS["OUTPUT"]){echo "Compressing $BaseWorkDir/wordpress.tar.gz\n";}
	shell_exec("$nice $tar cfz $BaseWorkDir/wordpress.tar.gz *");
	$took=$unix->distanceOfTimeInWords($t,time());
	$size=FormatBytes(@filesize("$BaseWorkDir/wordpress.tar.gz")/1024);
	if($GLOBALS["OUTPUT"]){echo "Compressing wordpress.tar.gz took $took size= $size";}
	apache_admin_mysql(2, "$servername directory backuped $size (took $took)", null,__FILE__,__LINE__);


}

function ftp_backup($WordpressBackupParams){
	$sock=new sockets();
	$mount=new mount();
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$FTP_ENABLE=intval($WordpressBackupParams["FTP_ENABLE"]);
	if($FTP_ENABLE==0){ echo "FTP disbabled\n"; return;}
	
	
	$FTP_SERVER=$WordpressBackupParams["FTP_ENABLE"];
	$FTP_USER=$WordpressBackupParams["FTP_USER"];
	$FTP_PASS=$WordpressBackupParams["FTP_PASS"];
	$FTP_SERVER=$WordpressBackupParams["FTP_SERVER"];
	$mntDir="/home/artica/mnt-wordpress-".time();
	@mkdir($mntDir,0755,true);
	
	if(!$mount->ftp_mount($mntDir, $FTP_SERVER, $FTP_USER, $FTP_PASS)){
		apache_admin_mysql(0,"Unable to mount FTP $FTP_USER@$FTP_SERVER",null,__FILE__,__LINE__);
		return;
	}
	
	$FTPDir="$mntDir/".$unix->hostname_g()."/wordpress-backup";
	
	
	echo "Starting copy... in $FTPDir\n";
	if($GLOBALS["VERBOSE"]){echo "Checks $FTPDir\n"; }
	if(!is_dir($FTPDir)){
		if($GLOBALS["VERBOSE"]){echo "$FTPDir no such directory\n";}
		@mkdir($FTPDir,0755,true);
	}
	
	
	if(!is_dir($FTPDir)){
		
		apache_admin_mysql(0,"Fatal FTP $FTP_USER@$FTP_SERVER $FTPDir permission denied",null,__FILE__,__LINE__);
		$mount->umount($mntDir);
		@rmdir($mntDir);
		
		return;
	}
	


	
	$directories_servernames=$unix->dirdir($WordpressBackupParams["DEST"]);
	$cp=$unix->find_program("cp");
	
	while (list ($directory, $ext) = each ($directories_servernames) ){
		$dirRoot=basename($directory);
		$TargetDirectory="$FTPDir/$dirRoot";
		
		if(!is_dir($TargetDirectory)){
			if($GLOBALS["VERBOSE"]){echo "Create directory $TargetDirectory\n";}
			@mkdir($TargetDirectory,0755,true);
		}
		
		if(!is_dir($TargetDirectory)){
				apache_admin_mysql(0,"Fatal FTP $FTP_USER@$FTP_SERVER $TargetDirectory permission denied",__FILE__,__LINE__); 
				continue; 
		}
		
		
		if($GLOBALS["VERBOSE"]){echo "Scaning $directory\n";}
		$directories_conteners=$unix->dirdir($directory);
		foreach ($directories_conteners as $directoryTime=>$ext){
			$dirRootTime=basename($directoryTime);
			$TargetDirectory="$FTPDir/$dirRoot/$dirRootTime";
			@mkdir($TargetDirectory,0755,true);
			if(!is_dir($TargetDirectory)){apache_admin_mysql(0,"Fatal FTP $FTP_USER@$FTP_SERVER $TargetDirectory permission denied",__FILE__,__LINE__); continue; }
			if(!is_file("$directoryTime/database.gz")){
				apache_admin_mysql(0,"Fatal $directoryTime/database.gz no such file, skip",null,__FILE__,__LINE__); 
				continue; 
			}
			
			$t=time();
			$results=array();
			if($GLOBALS["VERBOSE"]){echo "Copy $directoryTime/* -> $TargetDirectory\n";}
			exec("$cp -rf $directoryTime/* $TargetDirectory/ 2>&1",$results);
			foreach ($results as $a=>$b){
				if(preg_match("#cannot#i",$b)){
					apache_admin_mysql(0,"Fatal Copy error $b, skip",$b,__FILE__,__LINE__);
				}
			}
			
			
			$took=$unix->distanceOfTimeInWords($t,time());
			if(!is_file("$TargetDirectory/database.gz")){
				apache_admin_mysql(0,"Fatal $TargetDirectory/database.gz permission denied, skip",null,__FILE__,__LINE__);
				continue;
			}
			shell_exec("$rm -rf $directoryTime");
		}
	}
		
	$mount->umount($mntDir);
	@rmdir($mntDir);
	return;		
}

function sizes_backup(){
	$sock=new sockets();
	$WordpressBackupParams=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WordpressBackupParams")));
	if(!isset($WordpressBackupParams["FTP_ENABLE"])){$WordpressBackupParams["FTP_ENABLE"]=0;}
	if(!isset($WordpressBackupParams["DEST"])){$WordpressBackupParams["DEST"]="/home/wordpress-backup";}
	if($WordpressBackupParams["DEST"]==null){$WordpressBackupParams["DEST"]="/home/wordpress-backup";}
	if(!is_dir($WordpressBackupParams["DEST"])){return;}
	$unix=new unix();
	$size=$unix->DIRSIZE_KO($WordpressBackupParams["DEST"]);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WordpressBackupSize", $size);
	@chmod("/etc/artica-postfix/settings/Daemons/WordpressBackupSize",0755);
	
}

