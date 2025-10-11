<?php

$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FLUSH"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--flush#",implode(" ",$argv))){$GLOBALS["FLUSH"]=true;}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');

    if(isset($argv[1])) {
        if ($argv[1] == "--corrupted") {
            repair_corrupted();
            exit();
        }
        if ($argv[1] == "--clean-tmd") {
            clean_tmd();
            exit();
        }
        if ($argv[1] == "--sys") {exit;}
    }


function clean_tmd(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".MAIN.pid";
	$pidfileTime="/etc/artica-postfix/pids/exec.mysql.clean.php.clean_tmd.time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){squid_admin_mysql(2, "Already process $pid exists",__FUNCTION__,__FILE__,__LINE__,"clean");exit();}
	
	$timeExec=$unix->file_time_min($pidfileTime);
	if($timeExec<240){return;}
	
	@unlink($pidfileTime);
	@file_put_contents($pidfileTime, time());
	@file_put_contents($pidfile, getmypid());
	
	
	$SIZES=0;
	$Dirs=$unix->dirdir("/var/lib/mysql");
	foreach ($Dirs as $directory=>$none){
		$Files=$unix->DirFiles($directory,"\.[0-9]+\.TMD$");
		foreach ($Files as $filename=>$none){
			$fullpath="$directory/$filename";
			if($unix->file_time_min($fullpath)<240){continue;}
			$SIZES=$SIZES+@filesize($fullpath);
			@unlink($fullpath);
				
		}
		
		$Files=$unix->DirFiles($directory,"\.TMD-[0-9]+$");
		foreach ($Files as $filename=>$none){
			$fullpath="$directory/$filename";
			if($unix->file_time_min($fullpath)<240){continue;}
			$SIZES=$SIZES+@filesize($fullpath);
			@unlink($fullpath);
		
		}		

		
	}
	
	if(is_dir("/opt/squidsql/data")){
		
		$Dirs=$unix->dirdir("/opt/squidsql/data");
        foreach ($Dirs as $directory=>$none){
		
			$Files=$unix->DirFiles($directory,"\.[0-9]+\.TMD$");
			foreach ($Files as $filename=>$none){
				$fullpath="$directory/$filename";
				if($unix->file_time_min($fullpath)<240){continue;}
				$SIZES=$SIZES+@filesize($fullpath);
				@unlink($fullpath);
		
			}
		
			$Files=$unix->DirFiles($directory,"\.TMD-[0-9]+$");
			foreach ($Files as $filename=>$none){
				$fullpath="$directory/$filename";
				if($unix->file_time_min($fullpath)<240){continue;}
				$SIZES=$SIZES+@filesize($fullpath);
				@unlink($fullpath);
		
			}
		
		
		}		
		
		
	}
	
	
	
}

function repair_corrupted(){
	$q=new mysql();
	$unix=new unix();
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".md5(__FUNCTION__).".pid";
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		return;
	}
	
	$myisamchk=$unix->find_program("myisamchk");
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"$myisamchk\"",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#pgrep#", $line)){continue;}
		if(preg_match("#^[0-9]+\s+#", $line)){
			writelogs("$line already executed",@implode("\r\n", $results),__FUNCTION__,__FILE__,__LINE__);
			return;
		}
	}	

	if(!$GLOBALS["FORCE"]){
		if(!$GLOBALS['VERBOSE']){
			$timefile="/etc/artica-postfix/pids/MySQLRepairDBTime.time";
			$timex=$unix->file_time_min($timefile);
			if($timex<240){return;}
			@unlink($timefile);
			@file_put_contents($timefile, time());
		}
	}
	
	$databases=$q->DATABASE_LIST_SIMPLE();
    foreach ($databases as $database=>$comment){
		$tables=$q->TABLES_STATUS_CORRUPTED($database);
		if($GLOBALS["VERBOSE"]){echo "Checking database $database `$comment` Store ".count($tables)." suspicious tables\n";}
		if(count($tables)>0){
            foreach ($tables as $table=>$why){
				if($GLOBALS["VERBOSE"]){echo "Table `$table` is on status: `$why`\n";}
				repair_action($database,$table,$why);
				
			}
		}else{
			if($GLOBALS["VERBOSE"]){echo "Database $database is CLEAN !\n";}
		}
	
	}

	
}

function repair_action($database,$tablename,$expl){
	$unix=new unix();
	$q=new mysql();
	
	if(preg_match("#Can.*?t find file#", $expl)){
		squid_admin_mysql(2, "$tablename is destroyed, remove it..",__FUNCTION__,__FILE__,__LINE__);
		echo "Removing table $database/$tablename\n";
		$q->DELETE_TABLE($tablename, $database);
		return;
	}
	
	
	if(preg_match("#is marked as crashed#", $expl)){
		$results=array();
		$t=time();
		if(is_file("/var/lib/mysql/$database/$tablename.TMD")){
			@copy("/var/lib/mysql/$database/$tablename.TMD", "/var/lib/mysql/$database/$tablename.TMD-".time());
			@unlink("/var/lib/mysql/$database/$tablename.TMD");
		}
			
		$myisamchk=$unix->find_program("myisamchk");
		$cmd="$myisamchk -r /var/lib/mysql/$database/$tablename.MYI";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		exec($cmd,$results);
		$took=$unix->distanceOfTimeInWords($t,time());
		squid_admin_mysql(2, "$tablename repaired took: $took",@implode("\r\n", $results),__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	
	if($GLOBALS["VERBOSE"]){echo "$tablename nothing to do...\n";}
	
}
