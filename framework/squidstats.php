<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["repair-hour"])){repair_hour();exit;}
if(isset($_GET["processes-queue"])){process_queue();exit;}
if(isset($_GET["backup-stats-restore"])){restore_backup();exit;}
if(isset($_GET["backup-stats-restore-all"])){restore_all_backup();exit;}
if(isset($_GET["migrate-local"])){migrate_local();exit;}
if(isset($_GET["category-uid"])){category_uid();exit;}
if(isset($_GET["alldays"])){alldays();exit;}
if(isset($_GET["table-members-time"])){table_members_time();exit;}
if(isset($_GET["categorize-day-table"])){table_categorize_time();exit;}
if(isset($_GET["move-stats-file"])){move_stats_file();exit;}



foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);



function repair_hour(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$time=$_GET["repair-hour"];

	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.squid.stats.repair.php --repair-table-hour $time >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}


function restore_backup(){
	$unix=new unix();
	$path=$unix->shellEscapeChars(base64_decode($_GET["backup-stats-restore"]));
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();	
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.squidlogs.restore.php --restore $path >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}
function restore_all_backup(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.squidlogs.restore.php --restore-all >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}
function migrate_local(){
	$unix=new unix();
	$logfilename="/usr/share/artica-postfix/ressources/logs/web/squidlogs.restore.log";
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	@file_put_contents($logfilename, "\n");
	@chmod($logfilename, 0777);
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.squidlogs.restore.php --migrate-local >$logfilename 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}

function alldays(){
	$unix=new unix();
	$cmdline=$unix->find_program("nohup")." ".$unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.squid.stats.days.websites.php --schedule-id={$GLOBALS["SCHEDULE_ID"]} >/dev/null 2>&1 &";
	writelogs_framework("$cmdline",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmdline);
}
function category_uid(){

}



function move_stats_file(){
	$filename=$_GET["move-stats-file"];
	if(!is_file($filename)){
		echo "<articadatascgi>NO SUCH FILE</articadatascgi>";
		return;
	}
	
	$filename_size=@filesize($filename);
	$ArticaProxyStatisticsBackupFolder=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaProxyStatisticsBackupFolder"));
	if($ArticaProxyStatisticsBackupFolder==null){
		$ArticaProxyStatisticsBackupFolder="/home/artica/squid/backup-statistics";
	}
	
	$ArticaProxyStatisticsBackupFolder=$ArticaProxyStatisticsBackupFolder."/import";
	@mkdir($ArticaProxyStatisticsBackupFolder,0755,true);
	$basename=basename($filename);
	$target_path="$ArticaProxyStatisticsBackupFolder/$basename";
	if(is_file($target_path)){
		$target_path_size=@filesize($target_path);
		if($filename_size==$target_path_size){
			@unlink($filename);
			echo "<articadatascgi>SUCCESS</articadatascgi>";
			return;
		}
		@unlink($target_path);
	}
	
	if(!@copy($filename, $target_path)){
		@unlink($filename);
		@unlink($target_path);
		echo "<articadatascgi>COPY FAILED</articadatascgi>";
		return;
	}
	
	@unlink($filename);
	echo "<articadatascgi>SUCCESS</articadatascgi>";
	import_containers();
}

