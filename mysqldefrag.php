<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.templates.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::framework/class.unix.inc\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::frame.class.inc\n";}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

if($argv[1]=="--import"){Dumpimport();exit;}
if($argv[1]=="--export"){defragMylsql();exit;}
if($argv[1]=="--innodbfpt"){defragMylsql(1);Dumpimport(1);die("DIE " .__FILE__." Line: ".__LINE__);}
if($argv[1]=="--innodbfpti"){Dumpimport(1);die("DIE " .__FILE__." Line: ".__LINE__);}

defragMylsql(0);
Dumpimport(0);
function defragMylsql($innodb_file_pertable=0){
	$users=new usersMenus();
	$tmpfile="/home/mysqldump/all-database.sql";
	$sock=new sockets();
	$sock->SET_INFO("DisableMySqlTemp", 0);
	$unix=new unix();
	$MyPidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($MyPidFile);
	if($unix->process_exists($pid,basename(__FILE__))){
		squid_admin_mysql(2, "Error, PID $pid already exists in memory, aborting", __FUNCTION__, __FILE__, __LINE__, "mysql");
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	
	$mysqldump=$unix->find_program("mysqldump");
	if(!is_file($mysqldump)){
		squid_admin_mysql(2, "Error, mysqldump no such binary, aborting", __FUNCTION__, __FILE__, __LINE__, "mysql");
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	
	if(is_file("$tmpfile")){
		squid_admin_mysql(2, "$tmpfile exists, skip export task and run importation...", __FUNCTION__, __FILE__, __LINE__, "mysql");
		Dumpimport($innodb_file_pertable);
		return;
	}
	$sock->SET_INFO("DisableMySqlTemp", 0);
	$q=new mysql();
	$mysql_admin=$q->mysql_admin;
	$mysql_server_ok=false;
	$mysql_server=$q->mysql_server;
	if($mysql_server=="127.0.0.1"){$mysql_server_ok=true;}
	if($mysql_server=="localhost"){$mysql_server_ok=true;}
	
	if(!$mysql_server_ok){
		squid_admin_mysql(2, "Error, $mysql_server not supported or remote server, aborting", __FUNCTION__, __FILE__, __LINE__, "mysql");
		die("DIE " .__FILE__." Line: ".__LINE__);		
	}
	
	
	if(trim($mysql_admin)==null){
		squid_admin_mysql(2, "Error, unable to get mysql_admin credentials", __FUNCTION__, __FILE__, __LINE__, "mysql");
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	
	if($innodb_file_pertable==1){
		squid_admin_mysql(2, "INFO, The MySQL will be turned to innodb_file_per_table", __FUNCTION__, __FILE__, __LINE__, "mysql");
	}
	
	
	$password=$q->mysql_password;
	if($password<>null){
		$passwordcmdline=" -p$password";
	}
	

	
	@mkdir("/home/mysqldump",0755,true);
	$tmpfile="/home/mysqldump/all-database.sql";
	$tmpError="/home/mysqldump/all-database.errors";
	@unlink($tmpError);
	@unlink($tmpfile);
	$t=time();
	squid_admin_mysql(2, "Starting dump all databases and tables with username $mysql_admin", __FUNCTION__, __FILE__, __LINE__, "mysql");
	$cmdline="$mysqldump -u {$mysql_admin}$passwordcmdline --add-drop-database --opt --all-databases --log-error=$tmpError >$tmpfile";
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	shell_exec($cmdline);
	$f=file($tmpError);
	
	$filesize=$unix->file_size($tmpfile);
	$filesizeText=FormatBytes($filesize/1024);
	$t2=time();
	$took=$unix->distanceOfTimeInWords($t,$t2,true);
	
	squid_admin_mysql(2, "finish dump all databases and tables filesize=$filesizeText took: $took", __FUNCTION__, __FILE__, __LINE__, "mysql");
	
	foreach ($f as $index=>$line){
		if(preg_match("#error:\s+([0-9]+)#i", $line)){
			squid_admin_mysql(2, "Failed with error $line", __FUNCTION__, __FILE__, __LINE__, "mysql");
			return;
		}
		
	}
	
	if($filesize<500){
		squid_admin_mysql(2, "Failed empty dump file...", __FUNCTION__, __FILE__, __LINE__, "mysql");
		return;
	}
	
	Dumpimport();
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	squid_admin_mysql(2, "Defrag the entire Mysql done took:$took", __FUNCTION__, __FILE__, __LINE__, "mysql");
	$sock->TOP_NOTIFY("Defrag the entire Mysql done took:$took","info");	
	
}

function Dumpimport($innodb_file_pertable=0){
	
	squid_admin_mysql(2, "Create pointer to block Artica ", __FUNCTION__, __FILE__, __LINE__, "mysql");
	@mkdir("/home/mysqldump",0755,true);
	$tmpfile="/home/mysqldump/all-database.sql";
	$tmpError="/home/mysqldump/all-database.errors";
	$t1=time();
	$sock=new sockets();
	$sock->SET_INFO("DisableMySqlTemp", 0);
	$unix=new unix();	
	$q=new mysql();
	$mysql_admin=$q->mysql_admin;
	$MYSQL_DATA_DIR=$unix->MYSQL_DATA_DIR();
	if(trim($mysql_admin)==null){
		squid_admin_mysql(2, "Error, unable to get mysql_admin credentials", __FUNCTION__, __FILE__, __LINE__, "mysql");
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	
	if(!is_file($tmpfile)){
		squid_admin_mysql(2, "Error, $tmpfile no such file", __FUNCTION__, __FILE__, __LINE__, "mysql");
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	
	
	$password=$q->mysql_password;
	if($password<>null){
		$passwordcmdline=" --password=$password";
	}
	
	$mysqlbin=$unix->find_program("mysql");
	if(!is_file($mysqlbin)){
		squid_admin_mysql(2, "Error, mysqldump no such binary, aborting", __FUNCTION__, __FILE__, __LINE__, "mysql");
		die("DIE " .__FILE__." Line: ".__LINE__);
	}	
	
	$BaseDir=$MYSQL_DATA_DIR;
	$files=$unix->DirFiles($BaseDir);
	
	
	squid_admin_mysql(2, "Info, stopping mysql", __FUNCTION__, __FILE__, __LINE__, "mysql");
	@file_put_contents("/etc/artica-postfix/mysql.stop",time()); 
	shell_exec("/etc/init.d/artica-postfix stop mysql");
	
	while (list ($index, $line) = each ($files)){
		$fsize=FormatBytes($unix->file_size("$BaseDir/$line"));
		if(preg_match("#ib_logfile#", $line)){
			squid_admin_mysql(2, "Info, removing $BaseDir/$line", __FUNCTION__, __FILE__, __LINE__, "mysql");
			@unlink("/$BaseDir/$line");
			$deleted[]=$line.": $fsize";
			continue;
		}
		
		if(preg_match("#ibdata#", $line)){
			squid_admin_mysql(2, "Info, removing $BaseDir/$line", __FUNCTION__, __FILE__, __LINE__, "mysql");
			@unlink("/$BaseDir/$line");
			$deleted[]=$line.": $fsize";
			continue;
		}

		$skipped[]=$line.": $fsize";
	}	
	
	if($innodb_file_pertable==1){
		squid_admin_mysql(2, "Info, innodb_file_per_table is enabled", __FUNCTION__, __FILE__, __LINE__, "mysql");
		$sock->SET_INFO("InnodbFilePerTable",1);
		$php5=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php5 /usr/share/artica-postfix/exec.mysql.build.php");	
		squid_admin_mysql(2, "Deleting, ibdata1,ib_logfile0,ib_logfile1 is enabled", __FUNCTION__, __FILE__, __LINE__, "mysql");
		@unlink("$MYSQL_DATA_DIR/ibdata1");
		@unlink("$MYSQL_DATA_DIR/ib_logfile0");
		@unlink("$MYSQL_DATA_DIR/ib_logfile1");
	}
	

	    
	
	@unlink("/etc/artica-postfix/mysql.stop"); 
	
	
	
	
	squid_admin_mysql(2, "Info, Cleaning Mysql directory: delete files\n".@implode($deleted, "\n")."Skipped files:\n".@implode($skipped, "\n"), __FUNCTION__, __FILE__, __LINE__, "mysql");
	squid_admin_mysql(2, "Info, starting mysql", __FUNCTION__, __FILE__, __LINE__, "mysql");
	exec("/etc/init.d/artica-postfix start mysql 2>&1",$results);	
	squid_admin_mysql(2, "Info, starting mysql done\n".@implode("\n", $results), __FUNCTION__, __FILE__, __LINE__, "mysql");unset($results);
	squid_admin_mysql(2, "Stamp artica to not trying to inject to mysql", __FUNCTION__, __FILE__, __LINE__, "mysql");
	sleep(2);
	for($i=0;$i<50;$i++){
		$q=new mysql();
		$sleep=true;
		if(!$q->DATABASE_EXISTS("artica_backup")){
			$sleep=true;
		}
		
		$pid=@file_get_contents("/var/run/mysqld/mysqld.pid");
		if($unix->process_exists($pid)){
			echo "MySQL server running PID $pid\n";
			$sleep=false;
		}else{
			$sleep=true;
		}
		
		
		if($sleep){
			echo "Sleeping 1s pid:$pid not running, artica_backup not available...\n";
			continue;
		}
		 
		
		
		break;
		
	}
	
	if(!$unix->process_exists($pid)){
		$pid=@file_get_contents("/var/run/mysqld/mysqld.pid");
		echo "MySQL server not running...\n";
		squid_admin_mysql(2, "Task aborted, MySQL did not running...", __FUNCTION__, __FILE__, __LINE__, "mysql");	
		return;
	}
			
	
	
	
	$t=time();
	@unlink($tmpError);
	$cmd="$mysqlbin --batch --force --user=$mysql_admin$passwordcmdline < $tmpfile >$tmpError 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);
	$sock->SET_INFO("DisableMySqlTemp", 0);
	$t2=time();
	$took=$unix->distanceOfTimeInWords($t,$t2,true);
	squid_admin_mysql(2, "finish restoring all databases and tables took: $took", __FUNCTION__, __FILE__, __LINE__, "mysql");	
	$ISERRORED=false;
	foreach ($f as $index=>$line){
		if(preg_match("#error:\s+([0-9]+)#i", $line)){
			squid_admin_mysql(2, "Failed with error $line", __FUNCTION__, __FILE__, __LINE__, "mysql");
			$ISERRORED=true;
		}
		
		if(preg_match("#ERROR\s+([0-9]+)#i", $line)){
			squid_admin_mysql(2, "Failed with error $line", __FUNCTION__, __FILE__, __LINE__, "mysql");
			$ISERRORED=true;
		}
		if($GLOBALS["VERBOSE"]){echo "\"$line\" Unknown....line\n";}
		
	}
	$took=$unix->distanceOfTimeInWords($t1,time(),true);
	
	if(!$ISERRORED){
		squid_admin_mysql(2, "importing the entire Mysql done took:$took", __FUNCTION__, __FILE__, __LINE__, "mysql");
		@unlink($tmpfile);
		@unlink($tmpError);
	}else{
		squid_admin_mysql(2, "Procedure Failed the mysqldump file is skipped in $tmpfile path, you can retry yourself to import datas using this commandline:\n$cmd", __FUNCTION__, __FILE__, __LINE__, "mysql");
	}
	
	$unix=new unix();
	$zaraf=$unix->find_program("zarafa-server");
	if(is_file($zaraf)){
		shell_exec("/etc/init.d/artica-postfix restart zarafa-server");
	}
	
}


