<?php
$GLOBALS["SCHEDULE_ID"]=0;if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-server.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-multi.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
$GLOBALS["FORCE"]=false;
$GLOBALS["MULTI"]=false;
$GLOBALS["NOMONIT"]=false;
$GLOBALS["DEBUG"]=false;
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if($argv[1]=="--backup"){perform_db_backup(0,$argv[2],$argv[3],1);exit();}
if($argv[1]=="--restore"){perform_db_restore($argv[2],$argv[3],$argv[4]);exit();}


if($argv[1]=="--help"){
	echo "usage: --backup database directory\n";
	exit();
}

if(systemMaxOverloaded()){
	squid_admin_mysql(2, "This system is too many overloaded, die()",__FUNCTION__,__FILE__,__LINE__,"backup");
	exit();
}

start();
function start(){
	$unix=new unix();
	$q=new mysql();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidfileTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=$unix->get_pid_from_file($pidfile);
	
	
	if($unix->process_exists($pid,basename(__FILE__))){
		squid_admin_mysql(2, "Already process $pid exists",__FUNCTION__,__FILE__,__LINE__,"backup");
		exit();
	}
	@file_put_contents($pidfile, getmypid());
	if($q->COUNT_ROWS("mysqldb_backup","artica_backup")==0){
		squid_admin_mysql(2, "Nothing to do ...",__FUNCTION__,__FILE__,__LINE__,"backup");
		exit();
	}
	
	$sql="SELECT * FROM `mysqldb_backup` WHERE enabled=1";
	
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){
		squid_admin_mysql(2, "$q->mysql_error\n$sql",__FUNCTION__,__FILE__,__LINE__,"backup");
	}
	
	$rows=mysqli_num_rows($results);
	if($rows==0){
		squid_admin_mysql(2, "No backup to do ...",__FUNCTION__,__FILE__,__LINE__,"backup");
		exit();
	}
	
	squid_admin_mysql(2, "$rows database(s) to backup",__FUNCTION__,__FILE__,__LINE__,"backup");
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$database=$ligne["database"];
		$InstanceID=$ligne["InstanceID"];
		$targetDir=$ligne["targetDir"];
		$compress=$ligne["compress"];
		$MaxDay=$ligne["MaxDay"];
		if(!is_numeric($MaxDay)){$MaxDay=90;}
		if(!is_numeric($InstanceID)){$InstanceID=0;}
		squid_admin_mysql(2, "Starting backup `$database` (instance $InstanceID, compress=$compress)",__FUNCTION__,__FILE__,__LINE__,"backup");
		perform_db_backup($InstanceID,$database,$targetDir,$compress);	
		perform_db_clean($InstanceID,$database,$MaxDay);
		squid_admin_mysql(2, "Finish backup `$database`...",__FUNCTION__,__FILE__,__LINE__,"backup");	
		
	}
	
}

function perform_db_restore($InstanceID,$database,$sourcefile){
	$unix=new unix();
	$q=new mysql();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidfileTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=$unix->get_pid_from_file($pidfile);
	$prefix=null;
	$suffix=" < \"$sourcefile\"";
	
	if($unix->process_exists($pid,basename(__FILE__))){
		squid_admin_mysql(2, "$database: Already process $pid exists",__FUNCTION__,__FILE__,__LINE__,"mysql-restore");
		exit();
	}
	@file_put_contents($pidfile, getmypid());

	if(!is_file($sourcefile)){
		squid_admin_mysql(2, "$database: `$sourcefile` no such file...",__FUNCTION__,__FILE__,__LINE__,"mysql-restore");
		return;
	}
	
	$mysqlbin=$unix->find_program("mysql");
	$gunzip=$unix->find_program("gunzip");
	
	if(!is_file($mysqlbin)){
		squid_admin_mysql(2, "$database: `mysql` no such binary...",__FUNCTION__,__FILE__,__LINE__,"mysql-restore");
		return;
	}	
	
	$info=pathinfo($sourcefile);
	$extension=strtolower($info["extension"]);
	echo "Extension: $extension\n";
	if($extension=="gz"){
		if(!is_file($gunzip)){
			squid_admin_mysql(2, "$database: `gunzip` no such binary...",__FUNCTION__,__FILE__,__LINE__,"mysql-restore");
			return;
		}
		$prefix="$gunzip < \"$sourcefile\"|";
		$suffix=null;
	}	
		
		
	//gunzip < {database.sql.gz} |mysql -u {username} -p{password} {database}
	//$mysql -u {username} -p{password} {database} < database.sql
	
	
	$password=null;
	$options=" --max_allowed_packet=1G";
	

	
	$q=new mysql();
	if($q->mysql_password<>null){
		$q->mysql_password=$unix->shellEscapeChars($q->mysql_password);
		$password=" -p$q->mysql_password ";
	}
		
	if($q->mysql_server=="127.0.0.1"){
		$servcmd=" -u $q->mysql_admin$password --socket=/var/run/mysqld/mysqld.sock";
	}else{
		$servcmd=" -u $q->mysql_admin$password --host=$q->mysql_server --port=$q->mysql_port";		
	}
		
	if($InstanceID>0){
		$q=new mysql_multi($InstanceID);
		if($q->mysql_password<>null){
			$q->mysql_password=$unix->shellEscapeChars($q->mysql_password);
			$password=" -p$q->mysql_password ";}
			$servcmd=" -u $q->mysql_admin$password --socket=$q->SocketPath";
	}

	$cmdline="$prefix$mysqlbin $servcmd$options $database$suffix 2>&1";		
	$t=time();
	exec($cmdline,$results);
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	squid_admin_mysql(2, "$database: restore {took} $took\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__,"mysql-restore");
	
}


function perform_db_backup($InstanceID,$database,$targetDir,$compress){
	$unix=new unix();
	$piped=null;
	if(isset($GLOBALS["ALREADY$InstanceID$database"])){return;}
	$GLOBALS["ALREADY$InstanceID$database"]=true;
	$targetFilename=$targetDir."/".date("Y-m-d").".$database.sql";
	$logfile="/var/log/dump.$database.log";
	if(!is_dir($targetDir)){squid_admin_mysql(2, "$database:[$InstanceID]:$targetDir no such directory",__FUNCTION__,__FILE__,__LINE__, "backup");return false;}
	$t=time();
	@file_put_contents("$targetDir/$t", time());
	if(!is_file("$targetDir/$t")){squid_admin_mysql(2, "$database:[$InstanceID]:$targetDir permission denied",__FUNCTION__,__FILE__,__LINE__, "backup");return false;}
	@unlink("$targetDir/$t");
	if(!is_numeric($InstanceID)){$InstanceID=0;}
	
	$mysqldump=$unix->find_program("mysqldump");
	if(!is_file($mysqldump)){
		squid_admin_mysql(2, "$database:[$InstanceID]:mysqldump no such binary",__FUNCTION__,__FILE__,__LINE__, "backup");
		return false;
	}
	
	if($compress==1){
		$gzip=$unix->find_program("gzip");
		if(!is_file($gzip)){
			squid_admin_mysql(2, "$database:[$InstanceID]:gzip no such binary",__FUNCTION__,__FILE__,__LINE__, "backup");
			return false;
		}	
		$piped=" |$gzip -9 >";
		$targetFilename=$targetFilename.".gz";
	}
	
	if(is_file($targetFilename)){
		$size=filesize($targetFilename);
		squid_admin_mysql(2, "$database:[$InstanceID]: $targetFilename already exists ( $size bytes)...",__FUNCTION__,__FILE__,__LINE__, "backup");
		return false;	
	}
	$password=null;
	$options="--add-drop-database --opt --skip-lock-table --log-error=$logfile";
	

	
	$q=new mysql();
	if($q->mysql_password<>null){
		$q->mysql_password=$unix->shellEscapeChars($q->mysql_password);
		$password=" -p$q->mysql_password ";
	}
		
	if($q->mysql_server=="127.0.0.1"){
		$servcmd=" --socket=/var/run/mysqld/mysqld.sock ";
	}else{
		$servcmd=" --host=$q->mysql_server --port=$q->mysql_port ";		
	}
	
	$cmdline="$mysqldump -u $q->mysql_admin$password $servcmd $options $database$piped \"$targetFilename\" 2>&1";		
	$cmdlineTXT=str_replace($q->mysql_password, "****", $cmdline);
	
	if($InstanceID>0){
		$q=new mysql_multi($InstanceID);
		if($q->mysql_password<>null){
			$q->mysql_password=$unix->shellEscapeChars($q->mysql_password);
			$password=" -p$q->mysql_password ";}
			$cmdline="$mysqldump -u $q->mysql_admin$password --socket=$q->SocketPath $options $database$piped \"$targetFilename\" 2>&1";
			$cmdlineTXT=str_replace($q->mysql_password, "****", $cmdline);
		
	}	
	
	
	
	if(is_file($logfile)){@unlink($logfile);}
	if($GLOBALS["VERBOSE"]){echo "$cmdline\n";}
	$t=time();
	shell_exec($cmdline);
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	$size=filesize($targetFilename);
	$sizeH=FormatBytes($size/1024);
	$mysqllog=@file_get_contents($logfile);
	$md5=md5("$database$InstanceID");
	$date=date('Y-m-d');
	$q->QUERY_SQL("DELETE FROM mysqldb_backup_containers WHERE `fullpath`='$targetFilename'","artica_backup");
	$sql="INSERT IGNORE INTO mysqldb_backup_containers (`md5`,`fullpath`,`duration`,`zDate`,`size`) VALUES ('$md5','$targetFilename','$took','$date','$size')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		squid_admin_mysql(2, "$database:[$InstanceID]: $q->mysql_error\n$sql", __FUNCTION__, __FILE__, __LINE__, "backup");
	}
	squid_admin_mysql(2, "$database:[$InstanceID]: backup success {took} $took, size:$sizeH\n$cmdlineTXT\n$mysqllog", __FUNCTION__, __FILE__, __LINE__, "backup");
	
}

function perform_db_clean($InstanceID,$database,$MaxDay){
	if(!is_numeric($MaxDay)){$MaxDay=90;}
	$md5=md5("$database$InstanceID");
	$sql="SELECT * FROM mysqldb_backup_containers WHERE `md5`='$md5' AND zDate<DATE_SUB(NOW(),INTERVAL $MaxDay DAY)";
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){squid_admin_mysql(2, "$database:[$InstanceID]: $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "backup");return;}
	$c=0;
	while ($ligne = mysqli_fetch_assoc($results)) {
		$fullpath=$ligne["fullpath"];
		@unlink($fullpath);
		if(!is_file($fullpath)){
			$c++;
			$q->QUERY_SQL("DELETE FROM mysqldb_backup_containers WHERE fullpath='$fullpath'");
		}
	}
	
	
	
	if($c>0){
			squid_admin_mysql(2, "$database:[$InstanceID]: $c deleted container(s)", __FUNCTION__, __FILE__, __LINE__, "backup");
	}
	
}

	
