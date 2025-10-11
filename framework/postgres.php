<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.hd.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");

if(isset($_GET["service-status"])){service_status();exit;}
if(isset($_GET["install-tgz"])){install_tgz();exit;}
if(isset($_GET["is-installed"])){is_installed();exit;}
if(isset($_GET["searchlogs"])){searchlogs();exit;}
if(isset($_GET["InfluxDBPassword"])){InfluxDBPassword();exit;}
if(isset($_GET["backup"])){backup();exit;}
if(isset($_GET["backup-delete"])){backup_delete();exit;}
if(isset($_GET["backup-restore"])){backup_restore();exit;}
if(isset($_GET["version"])){version();exit;}
if(isset($_GET["move-restore"])){move_restore();exit;}
if(isset($_GET["restore-scandir"])){restore_scan_dir();exit;}
if(isset($_GET["restore-progress"])){restore_progress();exit;}
if(isset($_GET["restart-silent"])){restart_silent();exit;}
if(isset($_GET["restore-progress-server"])){restore_server_progress();exit;}
if(isset($_GET["refresh-progress"])){refresh_progress();exit;}
if(isset($_GET["remote-progress"])){remote_progress();exit;}
if(isset($_GET["disconnect-progress"])){disconnect_progress();exit;}
if(isset($_GET["php5-pgsql"])){php5_pgsql();exit;}
if(isset($_GET["remove-database"])){remove_database();exit;}
if(isset($_GET["PostGresSQLDatabaseDirectory"])){PostGresSQLDatabaseDirectory();exit;}

if(isset($_GET["syslog-query"])){SYSLOG_QUERY();exit;}
if(isset($_GET["influx-client"])){influx_client();exit;}
if(isset($_GET["clean-failed-queue"])){clean_failed_queue();exit;}
if(isset($_GET["purge"])){purge();exit;}
if(isset($_GET["ftp-validator"])){ftp_validator();exit;}


foreach ($_GET as $num=>$ligne){$a[]="$num=$ligne";}
writelogs_framework("Unable to understand ".@implode("&",$a),__FUNCTION__,__FILE__,__LINE__);


function is_installed(){
	
	if(!is_file("/usr/local/ArticaStats/bin/postgres")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("POSTGRESSQL_INSTALLED", 0);
		@chmod("/etc/artica-postfix/settings/Daemons/POSTGRESSQL_INSTALLED",0777);
		return;
	}
	
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("POSTGRESSQL_INSTALLED", 1);
		@chmod("/etc/artica-postfix/settings/Daemons/POSTGRESSQL_INSTALLED",0777);
}


function PostGresSQLDatabaseDirectory(){
	
	$directory="/home/ArticaStatsDB";
	
	if(is_link($directory)){$directory=@readlink($directory);}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PostGresSQLDatabaseDirectory", $directory);
	
	
}


function backup_delete(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 /usr/share/artica-postfix/exec.postgres.backup.php --delete-backup {$_GET["backup-delete"]} 2>&1 >/dev/null";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function purge(){

    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.statistics.purge.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.statistics.purge.progress.txt";


    $days=intval($_GET["purge"]);
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.clean.postgres.php --run $days >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function searchlogs(){
    $search=trim(base64_decode($_GET["cachelogs"]));
    $target_file=PROGRESS_DIR."/ArticaStatsDB.log";
    $source_file="/var/log/postgres.log";

    $unix=new unix();
    $tail=$unix->find_program("tail");
    $grep=$unix->find_program("grep");
    $rp=500;
    if(is_numeric($_GET["rp"])){$rp=intval($_GET["rp"]);}

    if($search==null){

        $cmd="$tail -n $rp $source_file >$target_file 2>&1";
        writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        return;
    }

    $search=$unix->StringToGrep($search);
    $cmd="$grep --binary-files=text -i -E '$search' $source_file 2>&1|$tail -n $rp >$target_file 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$cmd");



}

function restore_progress(){
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/postgres.restore.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/postgres.restore.progress.txt";
	
	$unix=new unix();
	$filename=$unix->shellEscapeChars($_GET["restore-progress"]);

	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$php5 /usr/share/artica-postfix/exec.postgres.restore.php $filename >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function remove_database(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/postgres.remove.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/postgres.remove.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postgres.php --remove-database >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}




function refresh_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.refresh.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/influxdb.refresh.progress.txt";
	
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.influxdb.php --refresh-progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function restore_server_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb-restart.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/influxdb.restart.progress.txt";
	
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.influxdb.php --restore-server {$_GET["server"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function restart_silent(){
	
	system("/etc/init.d/artica-postgres restart");
}



function remote_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb-restart.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/influxdb.restart.progress.txt";
	
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.influxdb.php --remote-progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function disconnect_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb-restart.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/influxdb.restart.progress.txt";
	
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.influxdb.php --disconnect-progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}



function clean_failed_queue(){
	$migration=null;
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/postgres.cleanfailed.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/postgres.cleanfailed.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	if(isset($_GET["migration"])){$migration=" --migration";}
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.PostgreSQL-failed.php --clean >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function install_tgz(){
	$migration=null;
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.install.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/influxdb.install.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.influxdb.php --install {$_GET["key"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function backup_restore(){

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/postgres.backup.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/postgres.backup.progress.txt";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $filepath=$unix->shellEscapeChars($_GET["backup-restore"]);
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.postgres.backup.php --restore $filepath >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function ftp_validator(){
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/postgres.ftp.validator.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/postgres.ftp.validator.log";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.postgres.backup.php --ftp-validator >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function backup(){
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/postgres.backup.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/postgres.backup.progress.txt";

	@unlink($ARRAY["PROGRESS_FILE"]);
	@unlink($ARRAY["LOG_FILE"]);
	@touch($ARRAY["PROGRESS_FILE"]);
	@touch($ARRAY["LOG_FILE"]);
	@chmod($ARRAY["PROGRESS_FILE"],0777);
	@chmod($ARRAY["LOG_FILE"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.postgres.backup.php >{$ARRAY["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	
}

function service_status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.status.php --influx --nowachdog >/usr/share/artica-postfix/ressources/logs/APP_INFLUXDB.status 2>&1");
}



function version(){
	
	if(isset($GLOBALS["postgres_version"])){return $GLOBALS["postgres_version"];}
	exec("/usr/local/ArticaStats/bin/postgres -V 2>&1",$results);
	foreach ($results as $key=>$value){
		if(preg_match("#([0-9\.]+)#", $value,$re)){
			$GLOBALS["postgres_version"]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "VERSION: $value...\n";}
			echo "<articadatascgi>{$GLOBALS["postgres_version"]}</articadatascgi>";
		}
	}
}
	
function move_restore(){
	$filename=$_GET["filename"];
	$content_dir="/usr/share/artica-postfix/ressources/conf/upload/";
	if(!is_file("$content_dir/$filename")){return;}
	
	
	$InFluxBackupDatabaseDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("InFluxBackupDatabaseDir");
	if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}
	$destfile="$InFluxBackupDatabaseDir/$filename";
	
	@mkdir($InFluxBackupDatabaseDir,0755);
	if(is_file($destfile)){@unlink($destfile);}
	if(!@copy("$content_dir/$filename", $destfile)){
		@unlink("$content_dir/$filename");
		return;
	}
	@unlink("$content_dir/$filename");
	$unix=new unix();
	$files=$unix->DirFiles("$InFluxBackupDatabaseDir");
	
	while (list ($num, $val) = each ($files)){
		$ARRAY[$num]=@filesize("$InFluxBackupDatabaseDir/$num");
	}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("InfluxDBRestoreArray", serialize($ARRAY));
	
	
}

function InfluxDBPassword(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.influxdb.php --InfluxDBPassword /dev/null 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function restore_scan_dir(){
	
	$content_dir=$_GET["restore-scandir"];
	$unix=new unix();
	writelogs_framework("Scanning $content_dir" ,__FUNCTION__,__FILE__,__LINE__);
	$files=$unix->DirFiles($content_dir);

	while (list ($num, $val) = each ($files)){
		writelogs_framework("Found $content_dir/$num" ,__FUNCTION__,__FILE__,__LINE__);
		$ARRAY["$content_dir/$num"]=@filesize("$content_dir/$num");
	}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("InfluxDBRestoreArray", serialize($ARRAY));


}

function influx_client(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.iptables-stats-app.php --restart /dev/null 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.syslog-stats-app /dev/null 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	//local7
	
}


function SYSLOG_QUERY(){

	$preprend=$_GET["prepend"];

	$pattern=trim(base64_decode($_GET["syslog-query"]));
	if($pattern=="yes"){$pattern=null;}
	$pattern=str_replace("  "," ",$pattern);
	$pattern=str_replace(" ","\s+",$pattern);
	$pattern=str_replace(".","\.",$pattern);
	$pattern=str_replace("*",".+?",$pattern);
	$pattern=str_replace("/","\/",$pattern);
	$syslogpath=$_GET["syslog-path"];
	$maxrows=0;
	if($syslogpath==null){

		exec("/usr/share/artica-postfix/bin/artica-install --whereis-syslog",$results);
		foreach ($results as $num=>$ligne){
			if(preg_match('#SYSLOG:"(.+?)"#',$ligne,$re)){
				$syslogpath=$re[1];
				break;
				writelogs_framework("artica-install --whereis-syslog $syslogpath" ,__FUNCTION__,__FILE__,__LINE__);
			}else{
				writelogs_framework("$ligne no match" ,__FUNCTION__,__FILE__,__LINE__);
			}
		}


	}
	$unix=new unix();
	$grepbin=$unix->find_program("grep");
	$tail = $unix->find_program("tail");
	if($tail==null){return;}
	if(isset($_GET["prefix"])){
		if(trim($_GET["prefix"])<>null){
			if(strpos($_GET["prefix"], ",")>0){$_GET["prefix"]="(".str_replace(",", "|", $_GET["prefix"]).")";}
			$_GET["prefix"]=str_replace("*",".*?",$_GET["prefix"]);
			$pattern="{$_GET["prefix"]}.*?\[[0-9]+\].*?$pattern";
		}
	}

	if($preprend<>null){
		$grep="$grepbin '$preprend'";
		if(strpos($preprend, ",")>0){$grep="$grepbin -E '(".str_replace(",", "|", $preprend).")'";}
	}

	writelogs_framework("Pattern \"$pattern\"" ,__FUNCTION__,__FILE__,__LINE__);
	if(isset($_GET["rp"])){$maxrows=$_GET["rp"];}
	if($maxrows==0){$maxrows=500;}


	if(strlen($pattern)>1){
		if(($preprend<>null) && (strlen($preprend)>3)){
			$preprend="'".$preprend."'";
			if(strpos($preprend, ",")>0){$preprend=" -E '(".str_replace(",", "|", $preprend).")'";}
			$grep="$grepbin $preprend|$grepbin -i -E '$pattern'";}
			else{
				$grep="$grepbin -i -E '$pattern'";
			}
	}

	unset($results);
	$l=$unix->FILE_TEMP();

	if($grep<>null){
		$cmd="$tail -n 5000 $syslogpath|$grep|$tail -n $maxrows 2>&1";
	}else{
		$cmd="$tail -n $maxrows $syslogpath 2>&1";
	}


	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	if(count($results)<3){
		$maxrows=$maxrows+2000;
		if($grep<>null){
			$cmd="$tail -n 5000 $syslogpath|$grep |$tail -n $maxrows 2>&1";
		}else{
			$cmd="$tail -n $maxrows $syslogpath 2>&1";
		}
		writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
		exec($cmd,$results);
	}

	if(count($results)<3){
		$maxrows=$maxrows+5000;
		if($grep<>null){
			$cmd="$grep $syslogpath|$tail -n $maxrows 2>&1";
		}else{
			$cmd="$tail -n $maxrows $syslogpath 2>&1";
		}
		writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
		exec($cmd,$results);
	}


	@file_put_contents(PROGRESS_DIR."/syslog.query", @implode("\n", $results));
	@chmod(PROGRESS_DIR."/syslog.query", 0755);
}

?>