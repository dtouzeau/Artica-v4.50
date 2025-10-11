<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.mysql.dump.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
cpulimit();
$_GET["LOGFILE"]="/var/log/artica-postfix/dansguardian-logger.debug";
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

if($argv[1]=="--restore-all"){restore_all();}
if($argv[1]=="--restore"){restore($argv[2]);}
if($argv[1]=="--migrate-local"){migrate_local();}
if($argv[1]=="--scandays"){$GLOBALS["VERBOSE"]=true;ScanDays();}


function restore_all(){
	
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
	$ArticaProxyStatisticsRestoreFolder=$sock->GET_INFO("ArticaProxyStatisticsRestoreFolder");
	if($ArticaProxyStatisticsRestoreFolder==null){$ArticaProxyStatisticsRestoreFolder="/home/artica/squid/backup-statistics-restore";}
	if(!is_dir($ArticaProxyStatisticsRestoreFolder)){
		squid_admin_mysql(1, "$ArticaProxyStatisticsRestoreFolder no such directory",__FUNCTION__,__FILE__,__LINE__,"reports");
	}
	$SUCC=0;
	$FAI=0;
	$t=time();
	$files=$unix->DirFiles($ArticaProxyStatisticsRestoreFolder);
	while (list ($srf, $line) = each ($files)){
		$fullfilename="$ArticaProxyStatisticsRestoreFolder/$srf";
		if(restore($fullfilename,true)){$SUCC++;}else{$FAI++;}
	}
	
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	squid_admin_mysql(1, "$SUCC restored backup(s), $FAI {failed}, {took} $took",__FUNCTION__,__FILE__,__LINE__,"reports");
	ScanDays();
}


function restore($sourcefile,$nopid=false){
	
	$unix=new unix();
	$md5=md5($sourcefile);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$md5.pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$workdir="/home/".time();
	$pass=null;
	
	if(!$nopid){
		if($unix->process_exists($pid,basename(__FILE__))){
			$timepid=$unix->PROCCESS_TIME_MIN($pid);
			squid_admin_mysql(1, "Already executed pid $pid since {$timepid}",__FUNCTION__,__FILE__,__LINE__,"reports");
			if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
			return;
		}
	
		@file_put_contents($pidfile, getmypid());
	}
	
	
	$sock=new sockets();
	$users=new usersMenus();
	$LICENSE=0;
	$mysqlbin=$unix->find_program("mysql");
	$tar=$unix->find_program("tar");
	
	
	$q=new mysql();
	if(!$q->TABLE_EXISTS("squidlogs_restores", "artica_events")){$q->BuildTables();}
	$sql="SELECT fullpath FROM squidlogs_restores WHERE fullpath='$sourcefile'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	if($ligne["fullpath"]<>null){
		if($GLOBALS["VERBOSE"]){
			echo "$sourcefile, Already restored...\n";
			return true;
		}
	}
	$results[]="Compressed: ".FormatBytes(@filesize($sourcefile)/1024);
		
	if(!is_file($mysqlbin)){
		echo "mysql, no such binary\n";
		squid_admin_mysql(1, "mysql, no such binary",__FUNCTION__,__FILE__,__LINE__,"backup");
		return;
	}
	
	
	if(!is_file($tar)){
		echo "tar, no such binary\n";
		squid_admin_mysql(1, "tar, no such binary",__FUNCTION__,__FILE__,__LINE__,"backup");
		return;
	}	
	
	$tarok["gz"]=true;
	$tarok["tar"]=true;
	$tarok["tar.gz"]=true;
	
	$ext=$unix->file_extension($sourcefile);
	if(preg_match("#.tar\.gz$#", $sourcefile)){$ext="tar.gz";}
	
	if($GLOBALS["VERBOSE"]){echo "$sourcefile, ext:$ext\n";}
	if(!is_file($sourcefile)){
		if($GLOBALS["VERBOSE"]){echo "$sourcefile, ext:$ext no such file\n";}
		squid_admin_mysql(1, "$sourcefile, ext:$ext no such file",__FUNCTION__,__FILE__,__LINE__,"backup");
		return;		
	}
	if($GLOBALS["VERBOSE"]){echo "uncompress $sourcefile...\n";}
	if(!isset($tarok[$ext])){
		if($GLOBALS["VERBOSE"]){echo "uncompress $sourcefile error, unable to understand $ext\n";}
		squid_admin_mysql(1, "uncompress $sourcefile error, unable to understand $ext",__FUNCTION__,__FILE__,__LINE__,"backup");
		return;		
	}
	
	@mkdir($workdir,0755,true);
	$sourcefileSQL="$workdir/".basename($sourcefile).".sql";
	$cmd="$tar -xhf $sourcefile --to-stdout > $sourcefileSQL 2>&1";
	shell_exec("$tar -xhf $sourcefile --to-stdout > $sourcefileSQL 2>&1");
	$results[]="Uncompressed: ".FormatBytes(@filesize($sourcefileSQL)/1024);
	if($GLOBALS["VERBOSE"]){echo "uncompress $sourcefile to $sourcefileSQL done...\n";}
	
	if(!is_file($sourcefileSQL)){
		if($GLOBALS["VERBOSE"]){echo "uncompress to $sourcefileSQL error\n";}
		@rmdir($workdir);
		squid_admin_mysql(1, "uncompress $sourcefile error, $sourcefileSQL no such file",__FUNCTION__,__FILE__,__LINE__,"backup");
		return;		
	}
	
	
	$q=new mysql_squid_builder(true);
	if($q->mysql_server=="localhost"){$q->mysql_server="127.0.0.1";}
	
	if(strlen($q->mysql_password)>1){
		$q->mysql_password=$unix->shellEscapeChars($q->mysql_password);
		$pass=" -p$q->mysql_password";
	}
	
	if($q->mysql_server=="127.0.0.1"){
		$serv="--protocol=socket --socket=$q->SocketName";
	}else{
		$serv="--protocol=tcp --host=$q->mysql_server --port=$q->mysql_port";
	}
	
	$sourcefileSQLT=$unix->shellEscapeChars($sourcefileSQL);
	$cmdline="$mysqlbin --force $serv -u $q->mysql_admin{$pass} $q->database < $sourcefileSQLT 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmdline\n";} 
	$t=time();
	exec($cmdline,$results);
	@unlink($sourcefileSQL);
	@rmdir($workdir);
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	$results[]="Took:$took ";
	$q=new mysql();
	$fres=mysql_escape_string2(@implode("\n", $results));
	$sourcefile=mysql_escape_string2($sourcefile);
	$q->QUERY_SQL("INSERT IGNORE INTO squidlogs_restores (fullpath,zDate,`results`) VALUES ('$sourcefile',NOW(),'$fres');","artica_events");
	if(!$q->ok){
		squid_admin_mysql(1, "$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"backup");
		return false;
	}
	
	if(!$nopid){ScanDays();}
	return true;
}



function ScanDays(){
	
	$q=new mysql_squid_builder(true);
	$ARRAY_DAYS=array();
	$tables=$q->LIST_TABLES_dansguardian_events();
	while (list ($tablename, $line) = each ($tables)){
		$dayTime=$q->TIME_FROM_DANSGUARDIAN_EVENTS_TABLE($tablename);
		$day=date("Y-m-d",$dayTime);
		$ARRAY_DAYS[$day]=$dayTime;
		
	}
	


	
	
	$prefix="INSERT IGNORE INTO tables_day (tablename,zDate) VALUES ";
	while (list ($day, $dayTime) = each ($ARRAY_DAYS)){
		$tablename="dansguardian_events_".date("Ymd",$dayTime);
		if($GLOBALS["VERBOSE"]){echo "$day: [$tablename]\n";}
		$f[]="('$tablename','$day')";
		
	}
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo "Fatal $q->mysql_error\n";}
			squid_admin_mysql(1, "Fatal $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "backup");
			return false;
		}
	}
	
	
	return true;
}

function migrate_local(){
	
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		echo "Already executed pid $pid\n";
		return;
	}
	@file_put_contents($pidfile, getmypid());	
	
	
	$sock=new sockets();
	$mysqldump=$unix->find_program("mysqldump");
	$mysqlbin=$unix->find_program("mysql");
	$pass=null;
	
	if(!is_file($mysqldump)){
		echo "mysqldump, no such binary\n";
		return;
	}
	
	if(!is_file($mysqlbin)){
		echo "mysql, no such binary\n";
		return;
	}	
	
	$EnableSquidRemoteMySQL=$sock->GET_INFO("EnableSquidRemoteMySQL");
	if(!is_numeric($EnableSquidRemoteMySQL)){$EnableSquidRemoteMySQL=0;}
	
	if($EnableSquidRemoteMySQL==0){
		echo "Error, loopback to myslef...\n";
		return;
	}
	
	$q=new mysql_squid_builder();
	if(!$q->BD_CONNECT(true)){
		echo "Error, Database connection failed...$q->mysql_error\n";
		return;
	}
	
	$q=new mysql_squid_builder(true);
	if($q->mysql_server=="localhost"){$q->mysql_server="127.0.0.1";}
	
	if(strlen($q->mysql_password)>1){
		$q->mysql_password=$unix->shellEscapeChars($q->mysql_password);
		$pass=" -p$q->mysql_password";
	}
	
	if($q->mysql_server=="127.0.0.1"){
		$serv="--protocol=socket --socket=$q->SocketName";
	}else{
		$serv="--protocol=tcp --host=$q->mysql_server --port=$q->mysql_port";
	}	
	
	$ArticaProxyStatisticsBackupFolder=$sock->GET_INFO("ArticaProxyStatisticsBackupFolder");
	if($ArticaProxyStatisticsBackupFolder==null){$ArticaProxyStatisticsBackupFolder="/home/artica/squid/backup-statistics";}
	@mkdir($ArticaProxyStatisticsBackupFolder,0755,true);
	
	$filename="squidlogs-full.".time().".sql";
	$filepath="$ArticaProxyStatisticsBackupFolder/$filename";
	$cmdline[]=$mysqldump;
	$cmdline[]="$serv -u $q->mysql_admin{$pass}";
	$cmdline[]="--log-error=$filepath.log";
	$cmdline[]="--skip-add-locks --insert-ignore --quote-names --skip-add-drop-table --verbose $q->database";
	$cmdline[]=" > $filepath";
	$cmd=@implode(" ", $cmdline);
	echo "$cmd\n";
	exec($cmd,$results);
	
	if($unix->MYSQL_BIN_PARSE_ERROR(@file_get_contents("$filepath.log"))){
		echo "Error, $unix->mysql_error\n";
		@unlink($filepath);		
		@unlink("$filepath.log");
		return;
	}
	
	@unlink("$filepath.log");
	$filesize=@filesize($filepath);
	if($filesize<500){
		echo "Error, $filepath filesize:$filesize too low\n";
		@unlink($filepath);
		return;
	}
	
	echo "Import $filepath\n";
	$cmdline=array();
	$pass=null;
	$results=array();
	$q=new mysql_squid_builder();
	
	$cmdline[]=$mysqlbin;
	$cmdline[]="--force";
	//$cmdline[]="--verbose";
	$cmdline[]="--debug-info";
	$cmdline[]=$q->MYSQL_CMDLINES;
	$cmdline[]="--database=$q->database < $filepath 2>&1";
	$cmd=@implode(" ", $cmdline);
	echo "$cmd\n";
	exec($cmd,$results);
	
	if($unix->MYSQL_BIN_PARSE_ERROR(@implode("\n", $results))){
		
		echo "Error, $unix->mysql_error\n";
		@unlink($filepath);
		@unlink("$filepath.log");
		return;
	}	
	echo "Success, task is finish....\nDone...\nOK, close the screen...\n";
	@unlink($filepath);
	
}


