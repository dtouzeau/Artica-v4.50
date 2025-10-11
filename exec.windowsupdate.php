
<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";exit();}}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["SERVICE_NAME"]="HyperCache Web service";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');


$WindowsUpdateCaching=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsUpdateCaching"));
if($WindowsUpdateCaching==0){exit();}

if($argv[1]=="--build"){build();exit();}
if($argv[1]=="--partition"){DirectorySize(true);exit();}
if($argv[1]=="--whitelist"){whitelist();exit();}
if($argv[1]=="--delete"){delete($argv[2]);exit();}
if($argv[1]=="--change-dir"){change_dir();exit();}



function change_dir(){
	
	$unix=new unix();
	build_progress(15,"{change_directory}");
	
	
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/WindowsUpdateCachingDirSave")){
		echo "/etc/artica-postfix/settings/Daemons/WindowsUpdateCachingDirSave no such file\n";
		build_progress(110,"{change_directory} {failed}");
		return;
	}
	
	
	$targetDirectory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsUpdateCachingDirSave");
	
	if($targetDirectory==null) {
		echo "Target dir is null !!\n";
		build_progress(110,"{change_directory} {failed}");
		return;
	}
	
	$CurrentDirectory="/home/squid/WindowsUpdate";
	if(is_link("/home/squid/WindowsUpdate")){$CurrentDirectory=readlink($CurrentDirectory);}
	
	$targetDirectory="$targetDirectory/WindowsUpdates";
	
	echo "Target directory.: $targetDirectory/WindowsUpdates\n";
	echo "Current directory: $CurrentDirectory\n";
	
	if(!is_dir($targetDirectory)){@mkdir($targetDirectory,0755,true);}
	
	if(!is_dir($targetDirectory)){
		echo "$targetDirectory, permission denied\n";
		build_progress(110,"{change_directory} {failed}");
		return;
	}
	
	build_progress(50,"{check_directory_size}");
	$size=$unix->DIRSIZE_BYTES($CurrentDirectory,false);
	echo "$targetDirectory, $size bytes\n";
	build_progress(60,"{copy_directory_content}");
	$cp=$unix->find_program("cp");
	$rm=$unix->find_program("rm");
	$ln=$unix->find_program("ln");
	$rsync=$unix->find_program("rsync");
	if(file_exists($rsync)){
		system("$rsync -av --progress --stats -v $CurrentDirectory/ $targetDirectory/");
	}else{
		system("$cp -rfupv $CurrentDirectory/* $targetDirectory/");
	}
	build_progress(50,"{check_directory_size}");
	$size2=$unix->DIRSIZE_BYTES($targetDirectory,false);
	
	if($size2>$size){
		$nextsize=$size2-$size;
	}else{
		$nextsize=$size-$size2;
	}
	
	if($size2<>$size){
		if($nextsize>100000){
			$nextsizeK=$nextsize/1024;
			$nextsizeM=$nextsizeK/1024;
			echo "$targetDirectory, size differ $nextsize bytes ( $nextsizeK KB, $nextsizeM MB)\n";
			build_progress(110,"{change_directory} {failed}");
			return;
		}
		
	}
	
	build_progress(70,"{remove_directory}");
	if(is_link("/home/squid/WindowsUpdate")){@unlink("/home/squid/WindowsUpdate");}
	if(is_dir("/home/squid/WindowsUpdate")){@rmdir("/home/squid/WindowsUpdate");}
	if(is_dir("/home/squid/WindowsUpdate")){system("$rm -rfv /home/squid/WindowsUpdate");}
	system("$ln -sf $targetDirectory /home/squid/WindowsUpdate");
	DirectorySize(true);
	build_progress(100,"{success}");
	
}


function isBlacklisted($URL){


	$NOTNESSCAB[]="disallowedcertstl";
	$NOTNESSCAB[]="pinrulesstl";
	$NOTNESSCAB[]="wsus3setup";
	$NOTNESSCAB[]="authrootstl";
	if(preg_match("#(".@implode("|", $NOTNESSCAB).")\.cab#", $URL)){return true;}
	if(preg_match("#WUClient-SelfUpdate#",$URL)){return true;}
	return false;


}

function AddToPartialQueue($URI,$ExpectedSize,$LocalFile){
	@mkdir("{$GLOBALS["WindowsUpdateCachingDir"]}/Partials",0755,true);
	$logFile="{$GLOBALS["WindowsUpdateCachingDir"]}/Partials/Queue.log";
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$LocalFile|||$URI|||$ExpectedSize\n");
	@fclose($f);
}

function GetTargetedSize($URI){
	$ExpectedSize=0;
	$curl=new ccurl($URI);
	$curl->FollowLocation=true;
	$Headers=$curl->getHeaders();
	
	if(isset($Headers["Content-Length"])){return $Headers["Content-Length"];}
	if($ExpectedSize==0){if(isset($Headers["download_content_length"])){ return $Headers["download_content_length"]; }}
	

	while (list ($index, $value) = each ($Headers)){
		events("Failed $index $value",__LINE__);
	}
	
	return 0;
	
}

function events($text,$line=0){
	$date=@date("H:i:s");
	$logFile="/var/log/squid/windowsupdate.debug";
	$size=@filesize($logFile);
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	if($size>9000000){@unlink($logFile);@touch($logFile);@chown($logFile,"squid");@chgrp($logFile, "squid"); }
	$line="$date:[Retriever/$line]:[{$GLOBALS["MYPID"]}]: $text";
	if($GLOBALS["VERBOSE"]){echo "$line\n";}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$line\n");
	@fclose($f);


}

function update_mysql($localpath,$zUri){
	$q=new mysql_squid_builder();
	$unix=new unix();
	$ln=$unix->find_program("ln");
	$size=@filesize($localpath);
	$filemd5=@md5_file($localpath);
	$date=date("Y-m-d H:i:s");
	$sql_insert="INSERT IGNORE INTO windowsupdate (filemd5,zDate,filesize,localpath,zUri)
	VALUES('$filemd5','$date','$size','$localpath','$zUri')";
	
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT localpath,filesize FROM windowsupdate WHERE filemd5='$filemd5'"));
	if(!$q->ok){events("MySQL Failed $q->mysql_error",__LINE__);}
	
	if($ligne["localpath"]<>null){
		if(!is_file($ligne["localpath"])){
			$q->QUERY_SQL("DELETE FROM windowsupdate WHERE filemd5='$filemd5'");
			$q->QUERY_SQL($sql_insert);
			if(!$q->ok){events("MySQL Failed $q->mysql_error",__LINE__);}
			return;
		}
		
		$md5inMysql=md5_file($ligne["localpath"]);
		
		if($md5inMysql<>$filemd5){
			if($ligne["localpath"]<>$localpath){
				@unlink($ligne["localpath"]);
				shell_exec("$ln -sf $localpath {$ligne["localpath"]}");
			}
			$q->QUERY_SQL("DELETE FROM windowsupdate WHERE filemd5='$filemd5'");
			$q->QUERY_SQL($sql_insert);
			if(!$q->ok){events("MySQL Failed $q->mysql_error",__LINE__);}
			return;
		}
			
		if($md5inMysql==$filemd5){
			if($ligne["localpath"]<>$localpath){
				@unlink($localpath);
				shell_exec("$ln -sf {$ligne["localpath"]} $localpath");
				return;
			}
				
		}
		
		return;
	}
	$q->QUERY_SQL($sql_insert);
	if(!$q->ok){events("MySQL Failed $q->mysql_error",__LINE__);}
	
	
}

function xdownload_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

	$KDOWN=xFormatBytes($downloaded_size/1024);
	$KTOT=xFormatBytes($download_size/1024);
	
	if ( $download_size == 0 ){
		$progress = 0;
	}else{
		if($GLOBALS["VERBOSE"]){echo xFormatBytes($downloaded_size/1024)."/". xFormatBytes($download_size/1024)."\n";}
		$progress = round( $downloaded_size * 100 / $download_size );
	}

	if ( $progress > $GLOBALS["previousProgress"]){
		$WindowsUpdateCaching=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsUpdateCaching"));
		if($WindowsUpdateCaching==0){
			events("{$GLOBALS["DOWNLOADED_FILE"]}: Feature disabled, aborting..",__LINE__);
			@unlink($GLOBALS["TMPFILE"]);
			build_progress(0,"{failed} {$GLOBALS["DOWNLOADED_FILE"]} $progress $KDOWN/$KTOT");
			exit();
		}
		build_progress($progress,"{downloading} $KDOWN/$KTOT");
		events("Downloading {$GLOBALS["DOWNLOADED_FILE"]}: $KDOWN/$KTOT {$progress}%",__LINE__);
		$GLOBALS["previousProgress"]=$progress;
			
	}
}

function build_progressG($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/windowsupdateG.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);



}

function build_progress($pourc,$text){
	$cachefile="/usr/share/artica-postfix/ressources/logs/windowsupdate.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_build($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "build_progress_build:: {$pourc}% $text\n";
	@mkdir("/usr/share/artica-postfix/ressources/logs",0755,true);
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid.windowsupdate1.progress");
	file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid.windowsupdate1.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid.windowsupdate1.progress",0755);



}

function DirectorySize($force=false){

	$dir="/home/squid/WindowsUpdate";
	echo "Scanning $dir...\n";
	if(is_link("/home/squid/WindowsUpdate")){$dir=readlink($dir);}
	$unix=new unix();

	$time=$unix->file_time_min("/usr/share/artica-postfix/ressources/logs/web/WindowsUpdate.state");
	if(!$force){
		if($time<120){return;}
		
	}
	echo "Scanning $dir...\n";
	$directories=$unix->dirdir($dir);
	while (list ($num, $ligne) = each ($directories) ){
		$domain=basename($num);
		$size=$unix->DIRSIZE_KO_nocache($num);
		$ARRAY["DOMAINS"][$domain]=$size;
	}

	$size=$unix->DIRSIZE_KO_nocache($dir);
	$partition=$unix->DIRPART_INFO($dir);
	

	$TOT=$partition["TOT"];
	$AIV=$partition["AIV"];
	$percent=($size/$TOT)*100;
	$percent=round($percent,3);


	events("INFO: Storage $size Partition $TOT",__LINE__);

	$ARRAY["PERCENTAGE"]=$percent;
	$ARRAY["SIZEKB"]=$size;
	$ARRAY["PART"]=$TOT;
	$ARRAY["AIV"]=$AIV;

	@unlink("/usr/share/artica-postfix/ressources/logs/web/WindowsUpdate.state");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/WindowsUpdate.state", serialize($ARRAY));

}

function CheckPartitionPercentage(){
	if(!isset($GLOBALS["WindowsUpdateCachingDir"])){
		$GLOBALS["WindowsUpdateCachingDir"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsUpdateCachingDir");
		if($GLOBALS["WindowsUpdateCachingDir"]==null){$GLOBALS["WindowsUpdateCachingDir"]="/home/squid/WindowsUpdate";}
	}
	$dir=$GLOBALS["WindowsUpdateCachingDir"];
	$unix=new unix();
	$partition=$unix->DIRPART_INFO($dir);
	return $partition["POURC"];

}

function whitelist(){
	$unix=new unix();
	$q=new mysql_squid_builder();
	$f=array();
	build_progress_whitelist("{starting}",15);
	
	$sql="SELECT * FROM windowsupdates_white WHERE enabled=1";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error."\n";
		build_progress_whitelist("{failed} MySQL Error",110);
		return;
	}
	build_progress_whitelist("{building}",50);
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$ligne['ipsrc']=trim($ligne['ipsrc']);
		if($ligne['ipsrc']==null){continue;}
		echo $ligne['ipsrc']."\n";
		$f[]=$ligne['ipsrc'];
		
	}
	
	
	
	@unlink("/etc/squid3/windowsupdate.whitelist.db");
	if(count($f)>0){
		@file_put_contents("/etc/squid3/windowsupdate.whitelist.db", @implode("\n", $f));
		build_progress_whitelist("{reloading}",80);
		@chown("/etc/squid3/windowsupdate.whitelist.db","squid");
		@chmod("/etc/squid3/windowsupdate.whitelist.db",0755);
	}
	
	build_progress_whitelist("{done}",100);
}

function build_progress_whitelist($text,$pourc){
	$cachefile=PROGRESS_DIR."/squid.windowsupdate.whitelist.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function build(){
	
	if(!isset($GLOBALS["WindowsUpdateCachingDir"])){
		$GLOBALS["WindowsUpdateCachingDir"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsUpdateCachingDir");
		if($GLOBALS["WindowsUpdateCachingDir"]==null){$GLOBALS["WindowsUpdateCachingDir"]="/home/squid/WindowsUpdate";}
	}
	$WindowsUpdateCaching=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsUpdateCaching"));
	if($WindowsUpdateCaching==1){
		build_progress_build("{building} {enabled}",10);
		build_ufdb();
		build_apache_ON();
		
	}else{
		build_progress_build("{building} {disabled}",10);
		build_apache_OFF();
	}
	DirectorySize(true);
	build_progress_build("{building} {success}",100);
}

function build_ufdb(){
	
	$sock=new sockets();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	if($EnableUfdbGuard==1){
		build_progress_build("{building} {webfiltering} {enabled} OK",12);
		
	}else{
		build_progress_build("{building} {webfiltering} {activate} OK",12);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableUfdbGuard", 1);

	}
	
	if(!build_IsInSquid()){
		build_progress_build("{building} {reconfigure_proxy_service}...",14);
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		build_progress_build("{building} {reconfigure_proxy_service} {done}",16);
	}
	
	build_progress_build("{building} {webfiltering} {done}",18);
	
}




function build_IsInSquid(){
	
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	foreach ($f as $num=>$val){
		if(preg_match("#url_rewrite_program.*?\/ufdbgclient\.php#", $val)){return true;}
		
	}
}

function delete($path){
	
	if(is_file($path)){
		$size=@filesize($path);
		events("INFO: Remove $path (".xFormatBytes($size/1024).")",__LINE__);
		@unlink($path);
	}
	
	$sql="DELETE FROM windowsupdate WHERE `localpath`='$path'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		events("FATAL: MySQL error $q->mysql_error",__LINE__);
		return;
	}
	events("INFO: Remove $path fro MySQL Done",__LINE__);
}


function xFormatBytes($kbytes,$nohtml=false){

	$spacer=null;
	

	if($kbytes>1048576){
		$value=round($kbytes/1048576, 2);
		if($value>1000){
			$value=round($value/1000, 2);
			return "$value{$spacer}TB";
		}
		return "$value{$spacer}GB";
	}
	elseif ($kbytes>=1024){
		$value=round($kbytes/1024, 2);
		return "$value{$spacer}MB";
	}
	else{
		$value=round($kbytes, 2);
		return "$value{$spacer}KB";
	}
}