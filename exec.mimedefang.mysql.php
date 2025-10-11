<?php
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	$GLOBALS["EXECUTED_AS_ROOT"]=true;
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.mimedefang.inc');
	if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
	
start();


function start(){
	
	$storage_path="/var/spool/MIMEDefang_replaced";
	$unix=new unix();
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidpath);
	if($unix->process_exists($pid)){squid_admin_mysql(2, "Already process $pid running.. Aborting",__FUNCTION__,__FILE__,__LINE__,"postfix");return;}
	$q=new mysql_mimedefang_builder();
	if(!$q->TABLE_EXISTS("storage")){$q->CheckTables();}
	if(!$q->TABLE_EXISTS("storage")){squid_admin_mysql(2, "Fatal, `storage` table does not exists",__FUNCTION__,__FILE__,__LINE__,"postfix");return;}
	@file_put_contents($pidpath,getmypid());
	
	if ($handle = opendir("$storage_path")) {	
		while (false !== ($file = readdir($handle))) {
			if ($file == "." OR $file == "..") {if($GLOBALS["VERBOSE"]){echo "skipped: `$file`\n";}continue;}
			if(substr($file, 0,1)=='.'){if($GLOBALS["VERBOSE"]){echo "skipped: `$file`\n";}continue;}
			$path="$storage_path/$file";
			if(inject_extracted_attach($path)){$c++;}
				
		}
	}
	
	CleanDatabase();
	
}

function inject_extracted_attach($filepath){
	if($GLOBALS["VERBOSE"]){echo "Injecting $filepath\n";}
	$unix=new unix();
	$MYSQL_DATA_DIR=$unix->MYSQL_DATA_DIR();
	if(!is_dir($MYSQL_DATA_DIR)){@mkdir($MYSQL_DATA_DIR,0755,true);@chown($MYSQL_DATA_DIR,"mysql");@chgrp($MYSQL_DATA_DIR,"mysql");}
		
	$basename=basename($filepath);
	$tempfile="$MYSQL_DATA_DIR/$basename";
	$last_modified = filemtime($filepath);
	$filetime=date("Y-m-d H:i:s",$last_modified);
	$filesize=$unix->file_size($filepath);
	@copy($filepath, $tempfile);
	@chmod($tempfile, 0777);
	$q=new mysql_mimedefang_builder();
	
	$sql="SELECT `filename` FROM `storage` WHERE `filename`='$basename'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	if(strlen(trim($ligne["filename"]))>0){
		if($GLOBALS["VERBOSE"]){echo "Skipped $basename, already exists in database\n";}
		@unlink($filepath);
		return;
	}
	
	$sql = "INSERT INTO `storage` (`filename`,`filetime`,`filesize`,`filedata`) VALUES ('$basename','$filetime','$filesize',LOAD_FILE('$filepath'))";
	$q->QUERY_SQL($sql);
				
	if(!$q->ok){
		squid_admin_mysql(2, "Fatal: $q->mysql_error.",__FUNCTION__,__FILE__,__LINE__,"postfix");
		$returned=false;
	}else{
		$returned=true;
		@unlink($filepath);
	}
		
	
	@unlink($tempfile);				
	return $returned;
}

function CleanDatabase(){
	$sock=new sockets();
	$unix=new unix();
	
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($unix->file_time_min($pidtime)<120){return;}
	
	$MimeDefangFileMaxDaysStore=$sock->GET_INFO("MimeDefangFileMaxDaysStore");
	if(!is_numeric($MimeDefangFileMaxDaysStore)){$MimeDefangFileMaxDaysStore=5;}
	if($MimeDefangFileMaxDaysStore==0){return;}
	$q=new mysql_mimedefang_builder();
	$filesStart=$q->COUNT_ROWS("storage");
	if($filesStart==0){return;}
	$sql="DELETE FROM storage WHERE filetime<DATE_SUB(NOW(),INTERVAL $MimeDefangFileMaxDaysStore DAY)";
	$q->QUERY_SQL($sql);
	if(!$q->ok){squid_admin_mysql(2, "Fatal: $q->mysql_error.",__FUNCTION__,__FILE__,__LINE__,"postfix");return;}
	$filesEND=$q->COUNT_ROWS("storage");
	$diff=$filesStart-$filesEND;
	if($diff>0){squid_admin_mysql(2, "Success cleaned $diff files from attachments storage.",__FUNCTION__,__FILE__,__LINE__,"postfix");}
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
}
