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
	include_once(dirname(__FILE__).  "/ressources/smtp/class.smtp.loader.inc");
	include_once(dirname(__FILE__).'/ressources/class.mime.parser.inc');
	include_once(dirname(__FILE__).'/ressources/class.rfc822.addresses.inc');
	include_once(dirname(__FILE__).'/ressources/class.maillog.tools.inc');
	if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
	
	if($argv[1]=="--path"){import_quarantine($argv[2]);exit;}
	
start();


function start(){
	
	$sock=new sockets();
	$unix=new unix();
	
	if(!$GLOBALS["VERBOSE"]){
		$pidtime="/etc/artica-postfix/pids/exec.mimedefang.quarantine.php.start.time";
		if($unix->file_time_min($pidtime)<5){return;}
		@unlink($pidtime);
		@file_put_contents($pidtime, time());
	}
	
	$postgres=new postgres_sql();
	$postgres->SMTP_TABLES();
	$storage_path="/var/spool/MD-Quarantine";
	$unix=new unix();
	$rmdir=$unix->find_program("rmdir");
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidpath);
	if($unix->process_exists($pid)){squid_admin_mysql(2, "Already process $pid running.. Aborting",__FUNCTION__,__FILE__,__LINE__,"postfix");return;}
	@file_put_contents($pidpath,getmypid());
	$c=0;
	$q=new postgres_sql();
	$q->SMTP_TABLES();
	echo "Scanning $storage_path\n";
	 
	if ($handle = opendir($storage_path)) {	
		while (false !== ($file = readdir($handle))) {
			if ($file == "." OR $file == "..") {continue;}
			if(substr($file, 0,1)=='.'){continue;}
			
			if(preg_match("#^[0-9]+-[0-9]+-[0-9]+-[0-9]+$#", $file)){
				if(!scan_subdir("$storage_path/$file")){
					continue;
				}
				echo "removing $storage_path/$file\n";
				system("$rmdir $storage_path/$file");
				
			}
			
			if(!preg_match("#^qdir-#", $file)){
				echo "$file no match ^qdir-\n";
				continue;}
			echo "Analyze $storage_path/$file\n";
			$path="$storage_path/$file";
			if(!is_file("$path/ENTIRE_MESSAGE")){
				echo "Analyze $path/ENTIRE_MESSAGE no such file\n";
				continue;}
			if($GLOBALS["VERBOSE"]){echo "Scanning $path\n";}
			import_quarantine($path);
				
		}
	}
	
	CleanDatabase();
	
}

function scan_subdir($directory){
	echo "Scanning $directory\n";
	if (!$handle = opendir($directory)) {return false;}
	while (false !== ($file = readdir($handle))) {
		if ($file == "." OR $file == "..") {continue;}
		if(substr($file, 0,1)=='.'){continue;}
		if(!preg_match("#^qdir-#", $file)){echo "$file no match ^qdir-\n";continue;}
		$path="$directory/$file";
		if(!is_file("$path/ENTIRE_MESSAGE")){echo "Analyze $path/ENTIRE_MESSAGE no such file\n";continue;}
		if($GLOBALS["VERBOSE"]){echo "Scanning $path\n";}
		if(!import_quarantine($path)){return false;}
	}
	
	return true;
	
	
}

function import_quarantine($directory){
	if($directory==null){return;}
	if(!is_dir($directory)){return;}
	if(!is_file("$directory/ENTIRE_MESSAGE")){
		if($GLOBALS["VERBOSE"]){echo "$directory/ENTIRE_MESSAGE no such file\n";}
		return;
	}
	if($GLOBALS["VERBOSE"]){echo "Scanning directory $directory\n";}
	$MimeDefangQuarteMail=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangQuarteMail"));
	$MimeDefangQuartDest=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangQuartDest"));
	$MimeDefangMaxQuartime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangMaxQuartime"));
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$SENDMAIL_QID=trim(@file_get_contents("$directory/SENDMAIL-QID"));
	

	if(is_file("$directory/MimeDefangQuarteMail")){
		$MimeDefangQuarteMail=intval(@file_get_contents("$directory/MimeDefangQuarteMail"));
	}
	
	if(is_file("$directory/MimeDefangQuartDest")){
		$MimeDefangQuartDest2=trim(@file_get_contents("$directory/MimeDefangQuartDest"));
		if(strpos($MimeDefangQuartDest2,"@")>0){
            $MimeDefangQuartDest=$MimeDefangQuartDest2;
        }
	}
	if(is_file("$directory/MimeDefangMaxQuartime")){
		$MimeDefangMaxQuartime=intval(@file_get_contents("$directory/MimeDefangMaxQuartime"));
	}
	
	$FAILED=0;
	if(is_file("$directory/FAILED")){
		$FAILED=intval(@file_get_contents("$directory/FAILED"));
	}
		
	if($FAILED>4){$MimeDefangQuarteMail=0;}
	
	
	if($MimeDefangQuarteMail==1){
		
		if($MimeDefangQuartDest==null){return false;}
		$mailfrom=trim(@file_get_contents("$directory/SENDER"));
		$mailfrom=str_replace("<", "", $mailfrom);
		$mailfrom=str_replace(">", "", $mailfrom);
		
		if($GLOBALS["VERBOSE"]){echo "$SENDMAIL_QID: Forward to $MimeDefangQuartDest from <$mailfrom>\n";}
		$finalbody=@file_get_contents("$directory/ENTIRE_MESSAGE");
		$smtp=new smtp();
		$params["helo"]=$unix->hostname_g();
		$params["host"]="127.0.0.1";
		if(!$smtp->connect($params)){
			$FAILED++;
			@file_put_contents("$directory/FAILED",$FAILED);
			if($GLOBALS["VERBOSE"]){echo "$SENDMAIL_QID: Could connect to `127.0.0.1`\n";}
			squid_admin_mysql(1, "Connect: Could not send quarantine message to `127.0.0.1` ", "Error $smtp->error_number: Could not connect to `$TargetHostname` $smtp->error_text",__FILE__,__LINE__);
			exit();
		}
		
		if(!$smtp->send(array("from"=>$mailfrom,"recipients"=>$MimeDefangQuartDest,"body"=>$finalbody,"headers"=>null))){
			$FAILED++;
			@file_put_contents("$directory/FAILED",$FAILED);
			if($GLOBALS["VERBOSE"]){echo "$SENDMAIL_QID: Could send to `127.0.0.1`\n";}
			squid_admin_mysql(1, "Could not send quarantine message data to `127.0.0.1` ", "Error $smtp->error_number: Could not connect to `$TargetHostname` $smtp->error_text",__FILE__,__LINE__);
			$smtp->quit();
			exit();
		}
		$smtp->quit();
		$maillog=new maillog_tools();
		
		$ARRAY["MESSAGE_ID"]=$SENDMAIL_QID;
		$ARRAY["HOSTNAME"]="localhost";
		$ARRAY["IPADDR"]="0.0.0.0";
		$ARRAY["SENDER"]=$mailfrom;
		$ARRAY["REJECTED"]="Forward quarantine";
		$ARRAY["SEQUENCE"]=55;
		$ARRAY["RECIPIENT"]=null;
		$maillog->berkleydb_relatime_write($SENDMAIL_QID,$ARRAY);
		
		
		echo "$directory/ENTIRE_MESSAGE (success)\n";
		shell_exec("$rm -rf \"$directory\"");
		return true;
		
	}
	
	
	
	
	$msgmd5=md5_file("$directory/ENTIRE_MESSAGE");
	$last_modified = filemtime("$directory/ENTIRE_MESSAGE");
	$filesize=@filesize("$directory/ENTIRE_MESSAGE");
	$htmlmess=ExportToHtml("$directory/ENTIRE_MESSAGE");
	
	if($MimeDefangMaxQuartime==0){$MimeDefangMaxQuartime=129600;}
	if($GLOBALS["VERBOSE"]){echo "Retention time.: {$MimeDefangMaxQuartime}Mn\n";}
	$final=strtotime("+{$MimeDefangMaxQuartime} minutes",$last_modified);
	$q=new postgres_sql();
	
	$zdate=date("Y-m-d H:i:s",$last_modified);

	if($GLOBALS["VERBOSE"]){echo "SENDMAIL_QID...: $SENDMAIL_QID\n";}
	if($GLOBALS["VERBOSE"]){echo "Message MD5....: $msgmd5\n";}
	if($GLOBALS["VERBOSE"]){echo "Message Date...: $last_modified ($zdate)\n";}
	if($GLOBALS["VERBOSE"]){echo "Size...........: $filesize\n";}
	
	//usr/local/ArticaStats/bin/psql -h /var/run/ArticaStats -U ArticaStats proxydb
	
	$filecontent_gz=$unix->FILE_TEMP().".gz";
	$unix=new unix();
	if(!$unix->compress("$directory/ENTIRE_MESSAGE", $filecontent_gz)){
		@unlink($filecontent_gz);
		echo "No... Compress error\n";
		return;
	}
	
	@chmod($filecontent_gz,0777);
	
	if($q->isRemote){
		$oid=$q->IMPORT_FILEDATA($filecontent_gz);
		if($oid==0){
			echo "No... IMPORT_FILEDATA error\n";
			@unlink($filecontent_gz);
			return;
		}
		@unlink($filecontent_gz);
		
		$q->QUERY_SQL("INSERT INTO quardata (zdate,msgmd5,final,contentid) VALUES ('$zdate','$msgmd5','$final',$oid ) ON CONFLICT DO NOTHING");
		if(!$q->ok){
			echo $q->mysql_error."\n";
			echo "No... PostgreSQL error\n";
			return false;
		}
		
	}else{
		$q->QUERY_SQL("INSERT INTO quardata (zdate,msgmd5,final,contentid) VALUES ('$zdate','$msgmd5','$final',lo_import('$filecontent_gz') ) ON CONFLICT DO NOTHING");
		if(!$q->ok){
			echo $q->mysql_error."\n";
			echo "No... PostgreSQL error\n";
			return false;
		}
	}
	
	$f=explode("\n",@file_get_contents("$directory/HEADERS"));
	
	foreach ($f as $index=>$line){
		if(preg_match("#Subject:\s+(.*)#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "Subject........: {$re[1]}\n";}
			$Subject=utf8_encode($re[1]);
		}
		if(preg_match("#From:\s+(.*)#i", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "From...........: {$re[1]}\n";}
			$FromHeader=$re[1];
			$FromHeader=str_replace("<", "", $FromHeader);
			$FromHeader=str_replace(">", "", $FromHeader);
			$FromHeader=trim($FromHeader);
			if(preg_match("#(.*?)\s+#", $FromHeader,$re)){$FromHeader=$re[1];}
		}
	}
	
	$mailsTo_array=array();
	$f=explode("\n",@file_get_contents("$directory/RECIPIENTS"));
	foreach ($f as $index=>$line){
		$line=trim($line);
		if($line==null){continue;}
		$line=str_replace("<", "", $line);
		$line=str_replace(">", "", $line);
		if(strpos($line, "@")==0){continue;}
		if($GLOBALS["VERBOSE"]){echo "Recipient......: {$line}\n";}
		$mailsTo_array[$line]=$line;
	}
	
	$mailfrom=trim(@file_get_contents("$directory/SENDER"));
	if($GLOBALS["VERBOSE"]){echo "Sender.........: {$mailfrom}\n";}
	if($mailfrom==null){$mailfrom=$FromHeader;}
	$mailfrom=str_replace("<", "", $mailfrom);
	$mailfrom=str_replace(">", "", $mailfrom);
	
	if(is_file("$directory/REPORT")){
		$ASREPORT=base64_encode(@file_get_contents("$directory/REPORT"));
	}


	

	
	
	$Subject=str_replace("'", "`", $Subject);
	$mailfromz=explode("@",$mailfrom);
	$domainfrom=$mailfromz[1];
	
	if(!$q->FIELD_EXISTS("quarmsg","msgid")){
		$q->ADD_FIELD("quarmsg", "msgid", "varchar(60)");
		$q->create_index("quarmsg","msgid",array("msgid"));
	}
	if(!$q->FIELD_EXISTS("quarmsg","htmlmess")){$q->ADD_FIELD("quarmsg", "htmlmess", "TEXT");}
	if(!$q->FIELD_EXISTS("quarmsg","htmlsize")){$q->ADD_FIELD("quarmsg", "htmlsize", "BIGINT");}
	$htmlsize=strlen($htmlmess);
	$prefix="INSERT INTO quarmsg (zdate,final,msgmd5,msgid,size,subject,mailfrom,mailto,domainfrom,domainto,htmlmess,htmlsize ) VALUES ";
	
	if(strlen($Subject)>255){$Subject=substr($Subject, 0,250)."...";}
	
	$f=array();
    foreach ($mailsTo_array as $a=>$mailto){
		$mailto=trim(strtolower($mailto));
		if($mailto==null){continue;}
		$mailtoz=explode("@",$mailto);
		$domainto=$mailtoz[1];
		$f[]="('$zdate','$final','$msgmd5','$SENDMAIL_QID','$filesize','$Subject','$mailfrom','$mailto','$domainfrom','$domainto','$htmlmess','$htmlsize')";
		
	}
	
	if(count($f)==0){
		echo "No... count(f)=0\n";
		shell_exec("$rm -rf \"$directory\"");
		return false;
	}
	
	
	$final_sql=$prefix." ".@implode(",", $f);
	$q->QUERY_SQL($final_sql);
	if(!$q->ok){
		echo $q->mysql_error."\n$final_sql\n";
		echo "No... PostgreSQL error\n";
		return false;
	}
	
	$SENDMAIL_QID=trim(@file_get_contents("$directory/SENDMAIL-QID"));
	$unix->ToSyslog("[$SENDMAIL_QID]: from=<$mailfrom> [$Subject] $directory/ENTIRE_MESSAGE success to Quarantine");
	echo "$directory/ENTIRE_MESSAGE (success)\n";
	shell_exec("$rm -rf \"$directory\"");
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
	$q->QUERY_SQL("DELETE FROM quarmsg WHERE final < ".time());
	
	$results=$q->QUERY_SQL("SELECT msgmd5,contentid FROM quardata WHERE final < ".time());
	while($ligne=@pg_fetch_assoc($results)){
		$msgmd5=$ligne["msgmd5"];
		$contentid=$ligne["contentid"];
		if($contentid>0){
			$q->QUERY_SQL("select lo_unlink($contentid)");
		}
		$q->QUERY_SQL("DELETE FROM quardata WHERE msgmd5='$msgmd5'");
		
	}
	
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
}
