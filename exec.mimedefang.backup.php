#!/usr/bin/php
<?php
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	$GLOBALS["EXECUTED_AS_ROOT"]=true;
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
	include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
	if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
	if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
	if($argv[1]=="--path"){import_backup_file($argv[2]);}
start();


function start(){
	
	$sock=new sockets();
	$unix=new unix();
	
	if(!$GLOBALS["VERBOSE"]){
		$pidtime="/etc/artica-postfix/pids/exec.mimedefang.backup.php.start.time";
		if($unix->file_time_min($pidtime)<5){
			events_backup("{$pidtime}mn... wait 5mn [".__LINE__."]");
			return;}
		@unlink($pidtime);
		@file_put_contents($pidtime, time());
	}
	
	$postgres=new postgres_sql();
	$postgres->SMTP_TABLES();
	$storage_path="/var/spool/MIMEDefang/BACKUP";
	$unix=new unix();
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidpath);
	if($unix->process_exists($pid)){squid_admin_mysql(2, "Already process $pid running.. Aborting",__FUNCTION__,__FILE__,__LINE__,"postfix");return;}
	@file_put_contents($pidpath,getmypid());
	$c=0;
	events_backup("Scanning $storage_path [".__LINE__."]");
	if ($handle = opendir($storage_path)) {	
		while (false !== ($file = readdir($handle))) {
			if ($file == "." OR $file == "..") {continue;}
			if(substr($file, 0,1)=='.'){if($GLOBALS["VERBOSE"]){echo "skipped: `$file`\n";}continue;}
			if(!preg_match("#.email$#", $file)){continue;}
			$path="$storage_path/$file";
			if(import_backup_file($path)){$c++;}
				
		}
	}
	events_backup("CleanDatabase... [".__LINE__."]");
	CleanDatabase();
	
}

function import_backup_file($filepath){
	if($GLOBALS["VERBOSE"]){echo "Import $filepath\n";}
	
	$dirname=dirname($filepath);
	$filename=basename($filepath);
	$msgid=str_replace(".email", "", $filename);
	$filecontent=$dirname."/".str_replace(".email", ".msg", $filename);
	
	if(!is_file($filecontent)){
		events_backup("$filecontent no such file [".__LINE__."]");
		echo "$filecontent no such file\n";
		@unlink($filepath);
		return true;
	}
	$last_modified = filemtime($filepath);
	
	//$FinalLog="$Subject|||$Sender|||$recipt|||$body_hash|||$body_length||$rententiontime";
	
	$F=explode("|||",@file_get_contents($filepath));
	
	if(count($F)<5){
		echo "Truncated file index : $filepath !\n";
		events_backup("Truncated file index : $filepath ! [".__LINE__."]");
		return false;
		
	}
	
	$q=new postgres_sql();
	if(!$q->FIELD_EXISTS("backupmsg", "htmlmess")){$q->ADD_FIELD("backupmsg", "htmlmess", "TEXT");}
	if(!$q->FIELD_EXISTS("backupmsg", "msgid")){$q->ADD_FIELD("backupmsg", "msgid", "varchar(60)");}
	$zdate=date("Y-m-d H:i:s",$last_modified);
	$subject=str_replace("'", "`", $F[0]);
	$mailfrom=$F[1];
	$mailfrom=str_replace("<", "", $mailfrom);
	$mailfrom=str_replace(">", "", $mailfrom);
	
	$htmlmess=base64_encode(ExportToHtml($filecontent));
	
	$mailfromz=explode("@",$mailfrom);
	$domainfrom=$mailfromz[1];
	$mailto_line=$F[2];
	$hash=$F[3];
	$retentiontime=$F[5];
	$filesize=@filesize($filecontent);
	$msgmd5=md5_file($filecontent);
	$final=strtotime("+{$retentiontime} minutes",$last_modified);

	$prefix="INSERT INTO backupmsg (zdate,final,msgmd5,msgid,htmlmess,size,subject,mailfrom,mailto,domainfrom,domainto ) VALUES ";
	
	$mailsTo_array=explode(";",$mailto_line);
	
	$f=array();
	while (list ($a, $mailto) = each ($mailsTo_array)){
		$mailto=trim(strtolower($mailto));
		$mailto=str_replace("<", "", $mailto);
		$mailto=str_replace(">", "", $mailto);
		if($mailto==null){continue;}
		$mailtoz=explode("@",$mailto);
		$domainto=$mailtoz[1];
		
		$ligne2=pg_fetch_array($q->QUERY_SQL("SELECT id FROM backupmsg WHERE msgmd5='$msgmd5' AND mailto='$mailto'"));
		if(intval($ligne2["id"])>0){continue;}
		
		
		$f[]="('$zdate','$final','$msgmd5','$msgid','$htmlmess','$filesize','$subject','$mailfrom','$mailto','$domainfrom','$domainto')";
		
	}
	
	if(count($f)==0){
		events_backup("No... count(f)=0 [".__LINE__."]");
		echo "No... count(f)=0\n";
		@unlink($filepath);
		@unlink($filecontent);
		return false;
		
	}
	
	
	$final_sql=$prefix." ".@implode(",", $f);
	$q->QUERY_SQL($final_sql);
	if(!$q->ok){
		echo $q->mysql_error."\n$final_sql\n";
		echo "No... PostgreSQL error\n";
		events_backup($q->mysql_error);
		return false;
	}
	
	$filecontent_gz="$filecontent.gz";
	$unix=new unix();
	if(!$unix->compress($filecontent, $filecontent_gz)){
		@unlink($filecontent_gz);
		echo "No... Compress error\n";
		events_backup("Compress error");
		return;
	}
	@chmod("/var/spool/MIMEDefang", 0755);
	@chmod($filecontent_gz,0777);
	@chown($filecontent_gz,"ArticaStats");
	chgrp($filecontent_gz, "ArticaStats");
	
	$ligne2=pg_fetch_array($q->QUERY_SQL("SELECT contentid FROM backupdata WHERE msgmd5='$msgmd5'"));
	if(intval($ligne2["contentid"])==0){
		$q->QUERY_SQL("INSERT INTO backupdata (zdate,msgmd5,final,contentid) VALUES ('$zdate','$msgmd5','$final',lo_import('$filecontent_gz') ) ON CONFLICT DO NOTHING");
		if(!$q->ok){
			echo $q->mysql_error."\n";
			echo "No... PostgreSQL error\n";
			events_backup("$q->mysql_error [".__LINE__."]");
			return false;
		}
		events_backup("$filepath (success)\n$filecontent (success) [".__LINE__."]");
		echo "$filepath (success)\n$filecontent (success)\n";
	}
	@unlink($filepath);
	@unlink($filecontent);	
	@unlink($filecontent_gz);
	return true;
}
function ExportToHtml($file_mho){
	$unix=new unix();

	if(!is_file($file_mho)){return null;}
	if(!is_file("/usr/bin/mhonarc")){return null;}
	$prefix=$unix->EXEC_NICE();
	$TEMP_DIR=$unix->TEMP_DIR();
	$md5=md5_file($file_mho);
	$attachmentdir="/$TEMP_DIR/$md5";


	$file_mho=str_replace('$','\$',$file_mho);
	$file_mho=str_replace('!','\!',$file_mho);
	$file_mho=str_replace('&','\&',$file_mho);
	$file_mho=str_replace(';','\;',$file_mho);
	$workingfile="/$attachmentdir/$md5.html";



	@mkdir($attachmentdir,0755,true);
	$cmd="$prefix/usr/bin/mhonarc ";
	$cmd=$cmd."-attachmentdir $attachmentdir ";
	$cmd=$cmd."-nodoc ";
	$cmd=$cmd."-nofolrefs ";
	$cmd=$cmd."-nomsgpgs ";
	$cmd=$cmd."-nospammode ";
	$cmd=$cmd."-nosubjectthreads ";
	$cmd=$cmd."-idxfname storage ";
	$cmd=$cmd."-nosubjecttxt \"no subject\" ";
	$cmd=$cmd."-single ";
	$cmd=$cmd." $file_mho ";
	$cmd=$cmd." >$workingfile 2>&1";
	$results=system($cmd);
	$datas=@file_get_contents($workingfile);
	@unlink($workingfile);
	shell_exec("/bin/rm -rf $attachmentdir");

	$datas=str_replace("'", "''", $datas);
	return utf8_encode($datas);

}

function CleanDatabase(){
	$sock=new sockets();
	$unix=new unix();
	
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($unix->file_time_min($pidtime)<120){return;}
	
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM backupmsg WHERE final < ".time());
	
	$results=$q->QUERY_SQL("SELECT msgmd5,contentid FROM backupdata WHERE final < ".time());
	while($ligne=@pg_fetch_assoc($results)){
		$msgmd5=$ligne["msgmd5"];
		$contentid=$ligne["contentid"];
		if($contentid>0){
			$q->QUERY_SQL("select lo_unlink($contentid)");
		}
		$q->QUERY_SQL("DELETE FROM backupdata WHERE msgmd5='$msgmd5'");
		
	}
	
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
}

function events_backup($text){
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	$filename=basename(__FILE__);
	$logFile="{$GLOBALS["ARTICALOGDIR"]}/postfix-logger.debug";
	$size=filesize($logFile);
	if($size>5000000){unlink($logFile);}
	error_log("[{$GLOBALS["MYPID"]}]: $filename $text");
}
