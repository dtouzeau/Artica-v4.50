<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
if($GLOBALS["VERBOSE"]){ echo "Starting ".@implode("; ", $argv)."\n"; }
@chmod("/var/log/artica-webauth.log",0777);
if($argv[1]=='--patchs-backup'){patchsBackup();exit;}
if($argv[1]=='--clean-space'){clean_space();exit();}
if($argv[1]=='--apache'){Clean_apache_logs();exit();}
if($argv[1]=='--urgency'){UrgencyChecks();exit;}
if($argv[1]=='--logrotatelogs'){logrotatelogs(true);home_artica_squid();exit();}
if($argv[1]=='--squid-store-logs'){exit();}
if($argv[1]=='--used-space'){used_space();exit();}
if($argv[1]=='--cleandb'){exit();}
if($argv[1]=='--clean-logs'){CleanLOGSF();CleanLogs();}
if($argv[1]=='--buidsh'){buidsh();exit;}
if($argv[1]=='--suricata'){CleanSuricataLogs();exit;}
if($argv[1]=='--artica-meta'){ArticaMeta();exit;}
if($argv[1]=='--artica-events'){CleanDcurl();exit;}
if($argv[1]=='--tailer-size'){CleanTailerSize();exit;}
if($argv[1]=='--sshd'){CleanSSHDFailed();exit;}
if($argv[1]=='--rotate-vsftpd'){vsftpd_log();exit;}
if($argv[1]=='--dnsstats'){remove_dns_stats();exit;}
if($argv[1]=='--mem'){clean_memcached();exit;}
if($argv[1]=='--squid-urgency'){squidlogs_urgency();exit();}
if($argv[1]=='--logs-urgency'){logs_urgency(true);exit();}
if($argv[1]=='--clean-tmp1'){Clean_tmp_path(true);}
if($argv[1]=='--clean-tmp2'){Clean_tmp_path(true);logrotatelogs(true);exit();}
if($argv[1]=='--clean-tmp'){CleanLogs();logrotatelogs(true);Clean_tmp_path(true);exit();}
if($argv[1]=='--clean-sessions'){sessions_clean();Clean_tmp_path(true);logrotatelogs(true);exit();}
if($argv[1]=='--clean-install'){CleanOldInstall();exit();}
if($argv[1]=='--paths-status'){PathsStatus();exit();}
if($argv[1]=='--maillog'){maillog();exit();}
if($argv[1]=='--DirectoriesSize'){exit();}
if($argv[1]=='--cleanbin'){Cleanbin();exit();}
if($argv[1]=='--squid-caches'){squidClean();exit();}
if($argv[1]=='--rotate'){CleanRotatedFiles();exit();}
if($argv[1]=='--squid'){squidClean();Clean_tmp_path(true);exit();}
if($argv[1]=='--artica-logs'){exit();}
if($argv[1]=='--squidLogs'){exit();}
if($argv[1]=='--nginx'){nginx();exit();}
if($argv[1]=='--attachs'){Clean_attachments();exit();}
if($argv[1]=='--access-logs'){exit();}
if($argv[1]=='--articastatus'){$GLOBALS["VERBOSE"]=true;clean_articastatus();exit;}
if($argv[1]=='--proc'){CleanBadProcesses();exit;}
if($argv[1]=='--syslog'){check_syslog_file();exit;}
if($argv[1]=="--varlog"){varlog(true);exit;}
if($argv[1]=="--wp-cache"){wordpress_remove_cache();exit;}
if($argv[1]=="--checkusers"){CheckSystemUsersAndGroups();exit;}


echo "Could not understand your query '$argv[1]' ???\n";


if(systemMaxOverloaded()){
    $unix=new unix();
    squid_admin_mysql(1,"{OVERLOADED_SYSTEM}, aborting", $unix->ps_mem_report(),__FILE__,__LINE__);
	exit();
}


function clean_memcached():bool{
    $unix=new unix();

    if (!$handle = opendir("/etc/artica-postfix/settings/Daemons")) {return false;}
    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        if(!preg_match("#^ORDER_([0-9]+)#",$filename,$re)){continue;}
        $xtime=$re[1];
        $period=$unix->time_min($xtime);
        if($period<240){continue;}
        $targetFile="/etc/artica-postfix/settings/Daemons/$filename";
        @unlink($targetFile);
    }

    return true;

}


function CleanLOGSF(){
	
	$unix=new unix();
	$Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$PidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	// /etc/artica-postfix/pids/exec.clean.logs.php.squidClean.time
	
	
	
	if($GLOBALS["VERBOSE"]){echo "Pidfile: $Pidfile\n";}
	if($GLOBALS["VERBOSE"]){echo "PidTime: $PidTime\n";}
	
	$pid=$unix->get_pid_from_file($Pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
	if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";} return false;}
	
	
	@file_put_contents($Pidfile, getmypid());
	
	if(!$GLOBALS["VERBOSE"]){
		if(!$GLOBALS["FORCE"]){
			$time=$unix->file_time_min($PidTime);
			echo "$PidTime = {$time}mn\n";
			if($time<15){echo "Only each 15mn\n";exit();}
			@unlink($PidTime);
			@file_put_contents($PidTime, time());
		}
	}

    $philesight=10;

    build_progress_philesight("{cleaning_data}",$philesight++);
	Clean_tmp_path(true);
    build_progress_philesight("{cleaning_data}",$philesight++);
	varlog();
    patchsBackup();
	
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(is_file($squidbin)){
        build_progress_philesight("{cleaning_data}",$philesight++);
		squidClean();
        build_progress_philesight("{cleaning_data}",$philesight++);
		CleanTailerSize();

	}
    build_progress_philesight("{cleaning_data}",$philesight++);
    clean_sshportal();
    build_progress_philesight("{cleaning_data}",$philesight++);
	Apache_access_common_log();
    build_progress_philesight("{cleaning_data}",$philesight++);
	Firewall_logs();
    build_progress_philesight("{cleaning_data}",$philesight++);
	vsftpd_log();
    build_progress_philesight("{cleaning_data}",$philesight++);
	CleanSuricataLogs();
    build_progress_philesight("{cleaning_data}",$philesight++);

    build_progress_philesight("{cleaning_data}",$philesight++);
	CleanLogs(true);
    build_progress_philesight("{cleaning_data}",$philesight++);
	Cleanbin();
    build_progress_philesight("{cleaning_data}",$philesight++);
	logrotatelogs(true);
    build_progress_philesight("{cleaning_data}",$philesight++);
	Clean_tmp_path(true);
    build_progress_philesight("{cleaning_data}",$philesight++);
	sessions_clean();
    build_progress_philesight("{cleaning_data}",$philesight++);
	ArticaMeta(true);
    build_progress_philesight("{cleaning_data}",$philesight++);
    cleanSplunk();

    build_progress_philesight("{cleaning_data}",$philesight++);
	CleanDcurl();
    build_progress_philesight("{cleaning_data}",$philesight++);
	remove_system_backup();
    build_progress_philesight("{cleaning_data}",$philesight++);
	remove_oldsnapshots();
    build_progress_philesight("{cleaning_data}",$philesight++);
    remove_loadavg();
    build_progress_philesight("{cleaning_data}",$philesight++);
    clean_memcached();
    build_progress_philesight("{cleaning_data}",$philesight++);
    squid_tail_errors();
    build_progress_philesight("{cleaning_data}",$philesight++);
    CheckSystemUsersAndGroups();
    build_progress_philesight("{cleaning_data}",$philesight++);
	
	$rm=$unix->find_program("rm");
	if(is_dir("/var/log/influxdb")){
        build_progress_philesight("{cleaning_data}",$philesight++);
		shell_exec("$rm -rf /var/log/influxdb");
	}
	
	if(is_dir("/usr/share/artica-postfix/smb-audit")){
        build_progress_philesight("{cleaning_data}",$philesight++);
		shell_exec("$rm -rf  /usr/share/artica-postfix/smb-audit");
	}
	
	if(is_dir("/usr/share/artica-postfix/bin/install/amavisd-milter-1.4.0")){
		shell_exec("$rm -rf  /usr/share/artica-postfix/bin/install/amavisd-milter-1.4.0");
	}


    build_progress_philesight("{cleaning_data}",$philesight++);
	$dir="/usr/share/artica-postfix/ressources/conf/upload/StatsApplianceLogs";
	if(is_dir($dir)){
		$dirs=$unix->dirdir($dir);
		foreach ($dirs as $directory=>$filename){
			StatsApplianceLogs($directory);
	
		}
	}
    build_progress_philesight("{cleaning_data} $philesight",100);
	return true;
	
	
}


function CleanTailerSize(){
	CleanTailerBase("/home/artica/ufdbcounters");
	CleanTailerBase("/home/squid/tail");
	
	
}

function remove_dns_stats(){
	include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
	$q=new postgres_sql();
	$q->QUERY_SQL("DROP TABLE dns_access_days");
	$q->QUERY_SQL("DROP TABLE dns_access");
}


function remove_loadavg(){


    $BaseWorkDir="/var/log/artica-postfix/loadavg";
    if(!is_dir($BaseWorkDir)){return;}
    if (!$handle = opendir($BaseWorkDir)) {return;}
    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        $targetFile="$BaseWorkDir/$filename";
        @unlink($targetFile);
    }

}


function remove_oldsnapshots(){
	
	$BaseWorkDir="/home/artica/snapshots";
	$unix=new unix();
	if(!is_dir($BaseWorkDir)){return;}
	if (!$handle = opendir($BaseWorkDir)) {return;}
	$c=0;$size=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if(is_dir($targetFile)){continue;}
		$time=$unix->file_time_min($targetFile);
		if($time<21600){continue;}
		$c++;
		echo "Remove $targetFile\n";
		$size=$size+@filesize($targetFile);
		@unlink($targetFile);

	}
	
	if($c>0){
		$size_text=FormatBytes($size/1024);
		squid_admin_mysql(1, "$c old snapshots removed ($size_text)", null,__FILE__,__LINE__);
	}

}

	
	
	
function CleanTailerBase($HomeDir){
	$BAD["Apr"]=true;
	$BAD["Aug"]=true;
	$BAD["Feb"]=true;
	$BAD["Jan"]=true;
	$BAD["Jul"]=true;
	$BAD["Jun"]=true;
	$BAD["Mar"]=true;
	$BAD["May"]=true;
	
	if(!is_dir($HomeDir)){
		echo "$HomeDir, no such file\n";
		
		return;}
	$unix=new unix();
	$rm=$unix->find_program("rm");
	if($GLOBALS["VERBOSE"]){echo "Scanning: $HomeDir\n";}
	$dirsYear=$unix->dirdir($HomeDir);
	foreach ($dirsYear as $directory=>$none){
		if($GLOBALS["VERBOSE"]){echo "Scanning: $directory\n";}
		$basename=basename($directory);
		if(!is_numeric($basename)){
			if($GLOBALS["VERBOSE"]){echo "SKIP: $directory\n";}
			continue;}
		if($basename<>date("Y")){
			if($GLOBALS["VERBOSE"]){echo "$rm -rf $directory\n";}
			shell_exec("$rm -rf $directory");
			continue;
		}
		
		$dirsMonth=$unix->dirdir($directory);
		$CurrentMonth=intval(date("m"));
        foreach ($dirsMonth as $directory_month => $none){
			if($GLOBALS["VERBOSE"]){echo "Scanning: Month $directory_month\n";}
			$basename=basename($directory_month);
			if(isset($BAD[$basename])){
				if($GLOBALS["VERBOSE"]){echo "$rm -rf $directory_month\n";}
				shell_exec("$rm -rf $directory_month");
				continue;
			}
			if(!is_numeric($basename)){continue;}
			
			if($basename<>$CurrentMonth){
				if($GLOBALS["VERBOSE"]){echo "$rm -rf $directory_month\n";}
				shell_exec("$rm -rf $directory_month");
				continue;
			}
			
			$dirsDays=$unix->dirdir($directory_month);
			$CurrentDay=intval(date("d"));
            foreach ($dirsDays as $directory_day => $none){
				if($GLOBALS["VERBOSE"]){echo "Scanning: $directory_day - $none\n";}
				$basename=basename($directory_day);
				if(!is_numeric($basename)){continue;}
				if($basename<>$CurrentDay){
					if($GLOBALS["VERBOSE"]){echo "$rm -rf $directory_day\n";}
					shell_exec("$rm -rf $directory_day");
					continue;
				}
				
				$dirsHours=$unix->dirdir($directory_day);
				$CurrentHour=intval(date("H"));
                foreach ($dirsHours as $directory_hour => $none){
					if($GLOBALS["VERBOSE"]){echo "Scanning: $directory_hour - $none\n";}
					$basename=basename($directory_hour);
					if(!is_numeric($basename)){continue;}
					if($basename==$CurrentHour){continue;}
					if($GLOBALS["VERBOSE"]){echo "$rm -rf $directory_hour\n";}
					shell_exec("$rm -rf $directory_hour");
				}
			}
		}
	}
	
	
	
}


function ArticaMeta($aspid=false){
	$unix=new unix();
	
	$Pidfile="/etc/artica-postfix/pids/cleanArticaMetaRepos.pid";
	$PidTime="/etc/artica-postfix/pids/cleanArticaMetaRepos.time";
	
	if(!$aspid){
		$pid=$unix->get_pid_from_file($Pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";} 
			return;
		}
	}
	@file_put_contents($Pidfile, getmypid());
	
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($PidTime);
		if($time<240){echo "Only each 240mn\n";exit();}
		@unlink($PidTime);
		@file_put_contents($PidTime, time());
	}
	

	

	
	
}
function Apache_rotate(){
	$directory="/var/log/apache2";
	if(!is_dir($directory)){return;}
	$BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}

	if (!$handle = opendir($directory)) {return;}
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$path="$directory/$fileZ";
		if(!preg_match("#access-common\.log-([0-9]+)\.bz2$#", $fileZ,$re)){
			echo "$fileZ -> NO MATCH\n";
			continue;
		}
		$number=$re[1];
		$year=substr($number, 0,4);
		$month=substr($number, 4,2);
		$day=substr($number, 6,2);
		$pathRange="$BackupMaxDaysDir/$year/$month/$day";
		@mkdir($pathRange,0755,true);
		$time=strtotime("$year-$month-$day 00:00:00");
		
		echo "$path -> $pathRange/apache_access-$time.gz\n";
		if(!@copy($path, "$pathRange/apache_access-$time.gz")){
			@unlink("$pathRange/apache_access-$time.gz");
			echo "Copy Failed\n";
			continue;
		}
		
		@unlink($path);
	}
	
}

function vsftpd_log(){
	$filename="/var/log/vsftpd.log";
	if(!is_file($filename)){return;}
	$unix=new unix();
	$LogsRotateDefaultSizeRotation=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsRotateDefaultSizeRotation"));
	if($LogsRotateDefaultSizeRotation==0){$LogsRotateDefaultSizeRotation=100;}
	$BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	@mkdir($BackupMaxDaysDir,0755,true);
	$size=(@filesize($filename)/1024)/1000;
	echo "$filename {$size}MB <> {$LogsRotateDefaultSizeRotation}MB\n";
	if($size<$LogsRotateDefaultSizeRotation){return;}
	$echo=$unix->find_program("echo");
	$pathRange="$BackupMaxDaysDir/".date("Y")."/".date("m")."/" .date("d");
	$time=time();
	$TEMP_PATH=$unix->FILE_TEMP();
	@copy($filename, $TEMP_PATH);
	shell_exec("$echo \"\" >$filename");
	@mkdir($pathRange,0755,true);
	echo "Compress $TEMP_PATH to $pathRange/vsftpd-$time.gz\n";
	$unix->compress($TEMP_PATH, $pathRange."/vsftpd-$time.gz");
	@unlink($TEMP_PATH);
	squid_admin_mysql(2, "Rotate file ".basename($filename)." $size>$LogsRotateDefaultSizeRotation", null,__FILE__,__LINE__);
}

function Apache_access_common_log(){
	$filename="/var/log/apache2/access-common.log";
	if(!is_file($filename)){return;}
	$unix=new unix();
	$LogsRotateDefaultSizeRotation=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsRotateDefaultSizeRotation"));
	if($LogsRotateDefaultSizeRotation==0){$LogsRotateDefaultSizeRotation=100;}
	$BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	@mkdir($BackupMaxDaysDir,0755,true);
	$size=(@filesize($filename)/1024)/1000;
	echo "$filename {$size}MB <> {$LogsRotateDefaultSizeRotation}MB\n";
	if($size<$LogsRotateDefaultSizeRotation){return;}
	$echo=$unix->find_program("echo");
	$pathRange="$BackupMaxDaysDir/".date("Y")."/".date("m")."/" .date("d");
	$time=time();
	$TEMP_PATH=$unix->FILE_TEMP();
	echo "Copy to $TEMP_PATH\n";
	@copy($filename, $TEMP_PATH);
	shell_exec("$echo \"\" >$filename");
	@mkdir($pathRange,0755,true);
	echo "Compress $TEMP_PATH to $pathRange/apache_access-$time.gz\n";
	$unix->compress($TEMP_PATH, $pathRange."/apache_access-$time.gz");
	@unlink($TEMP_PATH);
	squid_admin_mysql(2, "Rotate Apache events ".basename($filename)." $size>$LogsRotateDefaultSizeRotation", null,__FILE__,__LINE__);
}

function Firewall_logs():bool{
	$unix=new unix();
	$filename="/var/log/firewall.log";
    if(!is_file($filename)){return false;}

	$LogsRotateDefaultSizeRotation=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsRotateDefaultSizeRotation"));
	if($LogsRotateDefaultSizeRotation==0){$LogsRotateDefaultSizeRotation=100;}
	$FireHoleStoreEvents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHoleStoreEvents"));
	$FireHoleStoreEventsMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHoleStoreEventsMaxSize"));
	if($FireHoleStoreEventsMaxSize==0){$FireHoleStoreEventsMaxSize=$LogsRotateDefaultSizeRotation;}
	$size=(@filesize($filename)/1024)/1000;
	$BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	if($size<$FireHoleStoreEventsMaxSize){return true;}
	
	if($FireHoleStoreEvents==0){
		@unlink($filename);
		$unix=new unix();$unix->RESTART_SYSLOG(true);
		return true;
	}
	
	$pathRange="$BackupMaxDaysDir/".date("Y")."/".date("m")."/" .date("d");
	$time=time();
	$TEMP_PATH=$unix->FILE_TEMP();
	echo "Copy to $TEMP_PATH\n";
	@copy($filename, $TEMP_PATH);
	@mkdir($pathRange,0755,true);
	$hostname=$unix->hostname_g();
	echo "Compress $TEMP_PATH to $pathRange/firewall-$hostname-$time.gz\n";
	$unix->compress($TEMP_PATH, "$pathRange/firewall-$hostname-$time.gz");
	@unlink($TEMP_PATH);
	squid_admin_mysql(2, "Rotate Firewall events ".basename($filename)." {$size}MB>{$FireHoleStoreEventsMaxSize}MB", null,__FILE__,__LINE__);
	
	return true;
}


function StatsApplianceLogs($directory){
	$unix=new unix();
	if (!$handle = opendir($directory)) {return;}
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$path="$directory/$fileZ";
		if($unix->file_time_min($path)>240){
			echo "Removing $path\n";
			@unlink($path);
		}
	}
	
}

function home_artica_squid(){
    $unix=new unix();
    $TargetDir="/home/artica/squid";
    if(!is_dir($TargetDir)){return;}
    if ($handle = opendir($TargetDir)) {
        while (false !== ($fileZ = readdir($handle))) {
            if ($fileZ == ".") {
                continue;
            }
            if ($fileZ == "..") {
                continue;
            }
            $fullpath = "$TargetDir/$fileZ";
            if (is_dir($fullpath)) {
                continue;
            }
            if (!preg_match("#([0-9]+)\.tgz#", $fileZ, $re)) {
                echo "$fileZ no match...\n";
                continue;
            }

            $time = $re[1];
            $minutes = $unix->time_min($time);
            if($minutes>2880){
                @unlink($fullpath);
                continue;
            }
            echo "$fullpath -> $time -> $minutes\n";
        }
    }



}

function squid_tail_errors(){

    home_artica_squid();
    $BackupMaxDaysDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));
    if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}

    $BackupMaxDaysDir=$BackupMaxDaysDir."/proxy";

    $unix=new unix();
    $gzip=$unix->find_program("gzip");
    $TargetDir="/home/logrotate/failed";
    if(!is_dir($TargetDir)){return;}
    if ($handle = opendir($TargetDir)) {
        while (false !== ($fileZ = readdir($handle))) {
            if ($fileZ == ".") {
                continue;
            }
            if ($fileZ == "..") {
                continue;
            }



            $path = "$TargetDir/$fileZ";
            $size=filesize($path);
            if (!preg_match("#(squidtail|access).log.([0-9]+)#", $fileZ, $re)) {
                echo "$fileZ no match...\n";
                continue;
            }


            if($size<5){
                @unlink($path);
                continue;
            }

            $time = $re[2];
            $year = date('Y', $time);
            $month = date('m', $time);
            $day = date('d', $time);
            $hour = date("H", $time);
            $directory = "$BackupMaxDaysDir/$year/$month/$day/$hour";
            if (!is_dir($directory)) {
                @mkdir($directory, 0755, true);
            }
            echo "$gzip -c $path >$directory/$fileZ.gz ({$size}Bytes)\n";
            shell_exec("$gzip -c $path >$directory/$fileZ.gz");
            @unlink($path);
        }
    }






}
function build_progress_philesight($text,$pourc):bool{
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"system.dirmon.progress");
    return true;

}
function CleanSuricataLogs(){
	if(!is_dir("/var/log/suricata")){return;}
	$unix=new unix();
	$sock=new sockets();
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$BackupMaxDaysDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	@mkdir($BackupMaxDaysDir,0755,true);
	$echo=$unix->find_program("echo");
	@unlink("/etc/artica-postfix/pids/CleanSuricataLogs.time");
	@file_put_contents("/etc/artica-postfix/pids/CleanSuricataLogs.time", time());
	
	if ($handle = opendir("/var/log/suricata")) {
		while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$path="/var/log/suricata/$fileZ";
				
				if(preg_match("#unified2\.alert\.#", $fileZ)){
					if($unix->file_time_min($path)>30){@unlink($path);}
				}
					
		}
	}
	
	
	$f[]="fast.log";
	$f[]="http.log";
	$f[]="keyword_perf.log";
	$f[]="packet_stats.log";
	$f[]="rule_perf.log";
	$f[]="sid_changes.log";
	$f[]="stats.log";
	$RELOAD=false;
	$pathRange="$BackupMaxDaysDir/".date("Y")."/".date("m")."/" .date("d");

	foreach ($f as $filename){
		$filepath="/var/log/suricata/$filename";
		$size=(@filesize($filepath)/1024)/1000;
		echo "$filepath {$size}MB <> {$LogsRotateDefaultSizeRotation}M\n";
		if($size>$LogsRotateDefaultSizeRotation){
			@mkdir($pathRange,0755,true);
			$unix->compress($filepath, $pathRange."/IDS-".time().".$filename.gz");
			squid_admin_mysql(2, "Rotate file $filepath $size>$LogsRotateDefaultSizeRotation", null,__FILE__,__LINE__);
			shell_exec("$echo \"\">$filepath");
			$RELOAD=true;
		}
		
	}
	
	$size=(@filesize("/var/log/suricata/eve.json")/1024)/1000;
	if($size>$LogsRotateDefaultSizeRotation){
		squid_admin_mysql(2, "Rotate file /var/log/suricata/eve.json $size>$LogsRotateDefaultSizeRotation", null,__FILE__,__LINE__);
		@mkdir($pathRange,0755,true);
		$unix->compress($filepath, $pathRange."/IDS-".time().".eve.json.gz");
		shell_exec("$echo \"\">/var/log/suricata/eve.json");
		$RELOAD=true;
		
	}

	if($RELOAD){
		shell_exec("/etc/init.d/suricata reload");
	}
	
}
function squidClean($nopid=false){
	
	
	$unix=new unix();
	$Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$PidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	// /etc/artica-postfix/pids/exec.clean.logs.php.squidClean.time
	
	
	
	if($GLOBALS["VERBOSE"]){echo "Pidfile: $Pidfile\n";}
	if($GLOBALS["VERBOSE"]){echo "PidTime: $PidTime\n";}
	
	if(!$nopid){
		$pid=$unix->get_pid_from_file($Pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";}
			return;
		}
		@file_put_contents($Pidfile, getmypid());
	}
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($PidTime);
		if($time<15){echo "Only each 15mn\n";exit();}
		@unlink($PidTime);
		@file_put_contents($PidTime, time());
	}
	


	squidlogs_urgency();

	CleanSquidClamav();
	CleanTailerSize();
	
}
function init(){
	$sock=new sockets();
	$ArticaMaxLogsSize=$sock->GET_PERFS("ArticaMaxLogsSize");
	if($ArticaMaxLogsSize<1){$ArticaMaxLogsSize=300;}
	$ArticaMaxLogsSize=$ArticaMaxLogsSize*1000;	
	$GLOBALS["ArticaMaxLogsSize"]=$ArticaMaxLogsSize;
	$GLOBALS["logs_cleaning"]=$sock->GET_NOTIFS("logs_cleaning");
	$GLOBALS["MaxTempLogFilesDay"]=$sock->GET_INFO("MaxTempLogFilesDay");
	if($GLOBALS["MaxTempLogFilesDay"]==null){$GLOBALS["MaxTempLogFilesDay"]=5;}
	
	
}


function remove_system_backup(){
	$unix=new unix();
	$hostname=$unix->hostname_g();
	$rm=$unix->find_program("rm");
	$BackupArticaBackLocalDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackLocalDir"));
	$BackupArticaBackLocalRetention=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupArticaBackLocalRetention"));
	if($BackupArticaBackLocalRetention==0){$BackupArticaBackLocalRetention=87600;}
	if($BackupArticaBackLocalDir==null){$BackupArticaBackLocalDir="/home/artica/backup";}
	if(!is_dir($BackupArticaBackLocalDir)){return;}
	if (!$handle = opendir($BackupArticaBackLocalDir)) {return;}

			
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$directory="$BackupArticaBackLocalDir/$fileZ";
		if(!is_dir($directory)){continue;}
		if($fileZ<>$hostname){shell_exec("$rm -rf $directory");}
	}
	
	
	$CurrentDirectory="$BackupArticaBackLocalDir/$hostname/system-backup";
	if(!is_dir($CurrentDirectory)){return;}
	if (!$handle = opendir($CurrentDirectory)) {return;}
	
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$workdir="$CurrentDirectory/$fileZ";
		if(!is_dir($workdir)){continue;}
		if(!is_file("$workdir/BKVERSION.txt")){continue;}
		$time=$unix->file_time_min("$workdir/BKVERSION.txt");
		if($time>$BackupArticaBackLocalRetention){
            shell_exec("$rm -rf $workdir");
        }
		
	}
	
	
}

function TempDir(){
	$unix=new unix();
	$directory=$unix->TEMP_DIR();
	if (!$handle = opendir($directory)) {return;}
	
	
	while (false !== ($fileZ = readdir($handle))) {
			if($fileZ=="."){continue;}
			if($fileZ==".."){continue;}
			if(!preg_match("#^artica-php#",$fileZ)){continue;}
			$targetf="$directory/$fileZ";
			$size=@filesize($targetf);
			$timeMin=$unix->file_time_min($targetf);
			$xsize=FormatBytes($size/1024,true);
			if($timeMin<5){continue;}
			if($timeMin>240){
				echo "$targetf $size ".FormatBytes($size/1024,true)." ({$timeMin}mn) -> REMOVE\n";
				@unlink($targetf);
				continue;
			}
			if($size>500000000){
				echo "$targetf $size ".FormatBytes($size/1024,true)." ({$timeMin}mn) -> REMOVE\n";
				@unlink($targetf);
			}
	}
	
	
}

function varlog($aspid=false){

    if($aspid){
        $unix=new unix();
        $Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $PidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
        // /etc/artica-postfix/pids/exec.clean.logs.php.varlog.time
        if($GLOBALS["VERBOSE"]){echo "Pidfile: $Pidfile\n";}
        if($GLOBALS["VERBOSE"]){echo "PidTime: $PidTime\n";}

        $pid=$unix->get_pid_from_file($Pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";}
            return false;
        }
        $time=$unix->file_time_min($PidTime);
        if($time<15){echo "Only each 15mn\n";exit();}
        @unlink($PidTime);
        @file_put_contents($PidTime, time());
    }

    nginx_log();
    check_syslog_file();
    wordpress_remove_cache();
    check_viruses();
    $to_remove[]="/var/log/alternatives.log.4.gz";
    $to_remove[]="/var/log/alternatives.log.7.gz";
    $to_remove[]="/var/log/alternatives.log.3.gz";
    $to_remove[]="/var/log/alternatives.log.6.gz";
    $to_remove[]="/var/log/alternatives.log.9.gz";
    $to_remove[]="/var/log/alternatives.log.2.gz";
    $to_remove[]="/var/log/alternatives.log.5.gz";
    $to_remove[]="/var/log/alternatives.log.8.gz";

    $to_remove[]="/var/log/apport.log.2.gz";
    $to_remove[]="/var/log/apport.log.5.gz";
    $to_remove[]="/var/log/apport.log.3.gz";
    $to_remove[]="/var/log/apport.log.4.gz";
    $to_remove[]="/var/log/apport.log.6.gz";
    $to_remove[]="/var/log/apport.log.7.gz";

    $to_remove[]="/var/log/auth.log.4.gz";
    $to_remove[]="/var/log/auth.log.3.gz";
    $to_remove[]="/var/log/auth.log.2.gz";

    $to_remove[]="/var/log/dpkg.log.2.gz";
    $to_remove[]="/var/log/dpkg.log.3.gz";
    $to_remove[]="/var/log/dpkg.log.4.gz";
    $to_remove[]="/var/log/dpkg.log.5.gz";
    $to_remove[]="/var/log/dpkg.log.6.gz";
    $to_remove[]="/var/log/dpkg.log.7.gz";
    $to_remove[]="/var/log/dpkg.log.8.gz";
    $to_remove[]="/var/log/dpkg.log.9.gz";


    $to_remove[]="/var/log/kern.log.1.gz";
    $to_remove[]="/var/log/kern.log.2.gz";
    $to_remove[]="/var/log/kern.log.3.gz";
    $to_remove[]="/var/log/kern.log.4.gz";

    $to_remove[]="/var/log/syslog.2.gz";
    $to_remove[]="/var/log/syslog.3.gz";
    $to_remove[]="/var/log/syslog.6.gz";
    $to_remove[]="/var/log/syslog.4.gz";
    $to_remove[]="/var/log/syslog.5.gz";
    $to_remove[]="/var/log/syslog.7.gz";
    $to_remove[]="/etc/cron.d/DEBIAN_INSTALL_PACKAGE_PROXY";
    $to_remove[]="/etc/cron.d/1";

    foreach ($to_remove as $path){
        if(!is_file($path)){continue;}
        @unlink($path);
    }



    $trmCt[]="alternatives";
    $trmCt[]="apport";
    $trmCt[]="auth";
    $trmCt[]="btmp";
    $trmCt[]="wtmp";
    $trmCt[]="dpkg";
    $trmCt[]="kern";
    $trmCt[]="syslog";
    $trmCt[]="debug";
    $trmCt[]="messages";
    $trmCt[]="user";
    $trmCt[]="daemon";
    foreach ($trmCt as $pattern){
        for($i=0;$i<10;$i++){
            $fname="/var/log/$pattern.log.$i";
            if(!is_file($fname)){continue;}
            @unlink($fname);
        }
    }


	$unix=new unix();
	if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){
		$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir");
		if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; }
	}
	echo "******* varlog () *********\n";
	
	$rsync=$unix->find_program("rsync");
	$rm=$unix->find_program("rm");
	if(!is_file($rsync)){$unix->DEBIAN_INSTALL_PACKAGE("rsync");}

    if(!isset($GLOBALS["ArticaMaxLogsSize"])){init();}
    if(!is_numeric($GLOBALS["ArticaMaxLogsSize"])){init();}
	$sock=new sockets();
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$LogsDirectoryStorage=$sock->GET_INFO("LogsDirectoryStorage");
	if(trim($LogsDirectoryStorage)==null){$LogsDirectoryStorage="/home/logs-backup";}
	$echo=$unix->find_program("echo");
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}

	$ArticaProxyStatisticsBackupFolder=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaProxyStatisticsBackupFolder"));
	if($ArticaProxyStatisticsBackupFolder==null){$ArticaProxyStatisticsBackupFolder="/home/artica/squid/backup-statistics";}
	$LogRotateAccessMerged="$LogRotatePath/merged";

    if(is_file("/usr/share/nginx/nginx/syslog")){
        @unlink("/var/share/nginx/nginx/syslog");
    }
	
	
	$REMOVE_OLD_DIRS[]=$ArticaProxyStatisticsBackupFolder;
	$REMOVE_OLD_DIRS[]="/home/artica/squid/dbexport";
	$REMOVE_OLD_DIRS[]=$LogRotateAccessMerged;
	$REMOVE_OLD_DIRS[]="/var/log/exim4";

	foreach ($REMOVE_OLD_DIRS as $directory){
		if(!is_dir($directory)){continue;}
		system("$rm -rf $directory");
	}


	
	if(!is_file("/var/log/artica-postfix/squid-logger-start.log")){@touch("/var/log/artica-postfix/squid-logger-start.log");}

	$other[]="{$GLOBALS["ARTICALOGDIR"]}/ufdbcat-tail.debug";
	$other[]="{$GLOBALS["ARTICALOGDIR"]}/haproxy.debug";
    $other[]="{$GLOBALS["ARTICALOGDIR"]}/adagent.debug";

	$cicap[]="/var/log/c-icap/access.log";

	
	
	if(is_dir("/usr/share/artica-postfix/ressources/ressources")){
		shell_exec("$rm -rf /usr/share/artica-postfix/ressources/ressources");
	}

	foreach ($other as $filepath){
		if(!is_file($filepath)){continue;}
		$size=(@filesize($filepath)/1024)/1000;
		echo "$filepath {$size}MB <> {$LogsRotateDefaultSizeRotation}M\n";
		if($size>50){
			shell_exec("$echo \"\" >$filepath");
            $unix->ToSyslog("Cleaning $filepath {$size}Mb",false,"clean-db");
		}
		
		
 	}

 	$CICAP_RESTART=false;
	foreach ($cicap as $index=>$filepath){
 		if(!is_file($filepath)){continue;}
 		$size=(@filesize($filepath)/1024)/1000;
 		echo "$filepath {$size}MB <> {$LogsRotateDefaultSizeRotation}M\n";
 		if($size>$LogsRotateDefaultSizeRotation){
            $unix->ToSyslog("Cleaning $filepath {$size}Mb",false,"clean-db");
 			shell_exec("$echo \"\" >$filepath");
 			$CICAP_RESTART=true;
 		}
 	}	
 	
 	
 	if($CICAP_RESTART){
 		if(is_file("/etc/init.d/c-icap")){
            $unix->CICAP_SERVICE_EVENTS("Reloading ICAP service",__FILE__,__LINE__);
 			system('/etc/init.d/c-icap reload');
	 		if(is_file("/etc/init.d/c-icap-access")){
	 			system("/etc/init.d/c-icap-access restart");
	 		}
 		}
 		
 	}
	
	
	$q=new mysql_storelogs();
	echo " * * * $LogsDirectoryStorage * * *\n";
	if(is_dir($LogsDirectoryStorage)){
		if ($handle = opendir($LogsDirectoryStorage)) {
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$filename="$LogsDirectoryStorage/$fileZ";
				$q->events("Injecting $filename",__FUNCTION__,__LINE__);
				$q->InjectFile($filename,null);
					
			}
		}
	}
	echo " * * * * * * * * * * * * * * * * * * * * * * *\n";
	clean_articastatus();
	
	
	echo " * * * * * * * * * /var/log/squid * * * * * * * * *\n";
if(is_dir("/var/log/squid")){
	if ($handle = opendir("/var/log/squid")) {
		while (false !== ($fileZ = readdir($handle))) {
			if($fileZ=="."){continue;}
			if($fileZ==".."){continue;}
			$path="/var/log/squid/$fileZ";
			if(is_dir($path)){continue;}
			$ztime=$unix->file_time_min($path);
			if($ztime>5760){
				echo "$path {$ztime}mn -> REMOVE\n";
                $unix->ToSyslog("Removing $path",false,"clean-db");
				@unlink($path);
				continue;
			}
		}
	}
}
echo " * * * * * * * * * * * * * * * * * * * * *\n";	
echo " * * * * * * * * * /var/log/suricata * * * * * * * * *\n";
if(is_dir("/var/log/suricata")){
	if ($handle = opendir("/var/log/suricata")) {
		while (false !== ($fileZ = readdir($handle))) {
			if($fileZ=="."){continue;}
			if($fileZ==".."){continue;}
			$path="/var/log/suricata/$fileZ";
			if(is_dir($path)){continue;}
			$ztime=$unix->file_time_min($path);
			if($ztime>5760){
                $unix->ToSyslog("Removing $path",false,"clean-db");
				echo "$path {$ztime}mn -> REMOVE\n";
				@unlink($path);
				continue;
			}
		}
	}
}

echo " * * * * * * * * * * * * * * * * * * * * *\n";	
	echo " * * * /var/log/squid * * *\n";
	if(is_dir("/var/log/squid")){
		if ($handle = opendir("/var/log/squid")) {
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$path="/var/log/squid/$fileZ";
				if(is_dir($path)){continue;}
				if(!preg_match("#ufdbguardd\.log\.[0-9]+$#", $fileZ)){continue;}
				echo "$path remove \n";
                $unix->ToSyslog("Removing $path",false,"clean-db");
				@unlink($path);;
			}
		}	
	}

	$BackupMaxDays=$sock->GET_INFO("BackupMaxDays");
	$BackupMaxDaysDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));
	$BackupMaxDaysAccess=$sock->GET_INFO("BackupMaxDaysAccess");
	if(!is_numeric($BackupMaxDaysAccess)){$BackupMaxDaysAccess=365;}
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if(!is_numeric($BackupMaxDays)){$BackupMaxDays=365;}
	$BackupMaxHours=$BackupMaxDays*24;
	$BackupMaxMins=$BackupMaxHours*60;
	
	$BackupMaxDaysAccess=$BackupMaxDaysAccess*24;
	$BackupMaxDaysAccess=$BackupMaxDaysAccess*60;
	
	
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	
	
	
	if(is_dir($BackupMaxDaysDir)){
		if ($handle = opendir($BackupMaxDaysDir)) { 
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$filename="$BackupMaxDaysDir/$fileZ";
				$mins=$unix->file_time_min($filename);
				
				if(preg_match("#^access\.#", $filename)){
					if($mins>=$BackupMaxDaysAccess){
						$q->events("Removing $filename $mins>=BackupMaxDaysAccess:$BackupMaxDaysAccess",__FUNCTION__,__LINE__);
						echo "Removing $filename\n";
                        $unix->ToSyslog("Removing $filename",false,"clean-db");
						@unlink($filename);
					}
					continue;
				}
				
				
				if($GLOBALS["VERBOSE"]){echo "$filename = {$mins}Mn\n";}
				if($mins>=$BackupMaxMins){
					$q->events("Removing $filename $mins>=BackupMaxMins:$BackupMaxMins",__FUNCTION__,__LINE__);
					echo "Removing $filename\n";
                    $unix->ToSyslog("Removing $filename",false,"clean-db");
					@unlink($filename);
				}
				$q->events("Injecting $filename",__FUNCTION__,__LINE__);
				$q->InjectFile($filename);
			}
		
		}
	}	
	
	if(is_dir($LogRotatePath)){
		if ($handle = opendir($LogRotatePath)) {
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$filename="$LogRotatePath/$fileZ";
				$mins=$unix->file_time_min($filename);
				if($GLOBALS["VERBOSE"]){echo "$filename = {$mins}Mn\n";}
				
				if($mins>=$BackupMaxMins){
					$q->events("Removing $filename $mins>=BackupMaxMins:$BackupMaxMins",__FUNCTION__,__LINE__);
					echo "Removing $filename\n";
                    $unix->ToSyslog("Removing $filename",false,"clean-db");
					@unlink($filename);
				}
				$q->events("Injecting $filename",__FUNCTION__,__LINE__);
				$q->InjectFile($filename);
			}
	
		}
	}	
	
	$LogRotatePath=$LogRotatePath."/work";
	if(is_dir($LogRotatePath)){
		
		if ($handle = opendir($LogRotatePath)) {
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$filename="$LogRotatePath/$fileZ";
				$filemd5=md5_file($filename);
				
				if(isset($ARRAYMD[$filemd5])){
                    $unix->ToSyslog("Removing $filename",false,"clean-db");
					@unlink($filename);
					continue;
				}
				
				$ARRAYMD[$filemd5]=$filename;
			}
		}
		
		
		
		if ($handle = opendir($LogRotatePath)) {
			while (false !== ($fileZ = readdir($handle))) {
				if($fileZ=="."){continue;}
				if($fileZ==".."){continue;}
				$filename="$LogRotatePath/$fileZ";
				$mins=$unix->file_time_min($filename);
				if($GLOBALS["VERBOSE"]){echo "$filename = {$mins}Mn\n";}
				
				if(preg_match("#^access\.#", $filename)){
					if($mins>=$BackupMaxDaysAccess){
						echo "Removing $filename\n";
                        $unix->ToSyslog("Removing $filename $mins>=BackupMaxDaysAccess:$BackupMaxDaysAccess",false,"clean-db");
						@unlink($filename);
						continue;
					}
					$q->events("Injecting $filename",__FUNCTION__,__LINE__);
					$q->InjectFile($filename);
					continue;
				}
				
				
				if($mins>=$BackupMaxMins){
                    $unix->ToSyslog("Removing $filename $mins>=BackupMaxMins:$BackupMaxMins",false,"clean-db");
					echo "Removing $filename\n";
					@unlink($filename);
				}
				$q->events("Injecting $filename",__FUNCTION__,__LINE__);
				$q->InjectFile($filename);
			}
	
		}
	}		
	return true;
}



function check_syslog_file():bool{
        $unix=new unix();
        $tfile="/var/log/syslog";
        if(!is_file($tfile)){return false;}
        $fsize=filesize($tfile);
        if($fsize<1073741824){ return true;}
        $tsize=$unix->FormatBytes($fsize/1024);
        squid_admin_mysql(1,"Cleaning $tfile file ( $tsize more than 1GB of data )");
        @unlink($tfile);
        $unix=new unix();$unix->RESTART_SYSLOG(true);
        return true;
}
function hacluster_syslog($text):bool{
    if(!function_exists("openlog")){return false;}
    openlog("hacluster", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}








	
function squidlogs_urgency(){}

function CleanSquidClamav(){
	$unix=new unix();
	if(!is_dir("/home/squid")){return;}
	if (!$handle = opendir("/home/squid")) {return;}
	
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		if(!preg_match("#clamav_tmp#", $fileZ)){continue;}
		$filename="/home/squid/$fileZ";
        if(is_dir($filename)){continue;}
		$time=$unix->file_time_min($filename);
		if($time>120){@unlink($filename);}
	}
	
}
function cleanSplunk(){
    $unix=new unix();
    $sock=new sockets();
    $LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
    if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
    $echo=$unix->find_program("echo");

    if(is_file("/var/log/squid/uf/access.log")){
        $size=$unix->file_size("/var/log/squid/uf/access.log");
        $size=round(($size/1024)/1000,2);
        if($size>$LogsRotateDefaultSizeRotation){
            shell_exec("$echo \" \" > /var/log/squid/uf/access.log 2>&1");
            squid_admin_mysql(1, "Cleaned Squid Splunk log access.log ({$size}MB) MAX:{$LogsRotateDefaultSizeRotation}MB", null,__FILE__,__LINE__);

        }

    }
}



function LogRotateTimeAndSize($BaseWorkDir){
	if(!is_dir($BaseWorkDir)){return;}
	$unix=new unix();
	$sock=new sockets();
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$syslog=new mysql_storelogs();
	
	if($BaseWorkDir=="/var/log/squid"){return; }
	if (!$handle = opendir($BaseWorkDir)) {return;}
	
	
	
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$filename="$BaseWorkDir/$fileZ";
		if(is_dir($filename)){continue;}
		$size=$unix->file_size($filename);
		$size=round(($size/1024)/1000,2);
		
	
	
		if($GLOBALS["VERBOSE"]){echo "Found file: $filename {$size}M !== {$LogsRotateDefaultSizeRotation}M\n";}
	
		if($fileZ=="cache.log"){continue;}
		if($fileZ=="cache-nat.log"){continue;}
		if($fileZ=="external-acl.log"){continue;}
		if($fileZ=="ufdbguardd.log"){continue;}
		if($fileZ=="access.log"){continue;}
		if($fileZ=="squidtail.log"){continue;}
		if($fileZ=="netdb.state"){continue;}
		$time=$unix->file_time_min($filename);
		$filedate=date('Y-m-d H:i:s',filemtime($filename));
		
		if(preg_match("#access\.log[0-9]+$#", $filename)){
			continue;
		}		
	
		if(preg_match("#access\.log\.[0-9]+$#", $filename)){
			continue;
		}
	
		if(preg_match("#sarg\.log\.[0-9]+$#", $filename)){
			@mkdir("/home/squid/sarg_logs");
			$syslog->events("copy $filename -> /home/squid/sarg_logs/".basename($filename).".".filemtime($filename),__FUNCTION__,__LINE__);
			if(@copy($filename, "/home/squid/sarg_logs/".basename($filename).".".filemtime($filename))){
				@unlink($filename);
			}
	
			continue;
		}
	
	
		$extension = pathinfo($filename, PATHINFO_EXTENSION);
	
		if($GLOBALS["VERBOSE"]){echo "Analyze $filename ($extension) $filedate\n";}
	
		if(is_numeric($extension)){
			$syslog->events("ROTATE_TOMYSQL $filename",__FUNCTION__,__LINE__);
			$syslog->ROTATE_TOMYSQL($filename, $filedate);
			continue;
		}
		if($extension=="gz"){
			$syslog->events("ROTATE_TOMYSQL $filename",__FUNCTION__,__LINE__);
			$syslog->ROTATE_TOMYSQL($filename, $filedate);
			continue;
		}
	
		if($extension=="state"){continue;}
	
		if($extension=="bz2"){
			$syslog->events("ROTATE_TOMYSQL $filename",__FUNCTION__,__LINE__);
			$syslog->ROTATE_TOMYSQL($filename, $filedate);
			continue;
		}
	
			
		$time=$unix->file_time_min($filename);
		echo "$filename {$time}Mn\n";
		$syslog->events("ROTATE_TOMYSQL $filename",__FUNCTION__,__LINE__);
		$syslog->ROTATE_TOMYSQL($filename, $filedate);
	
	}
	
	
		
}








function maillog():bool{
	init();

    $Workdir="/usr/share/artica-postfix";
    $files=scandir($Workdir);
    foreach ($files as $fname){
        if($fname=="."){continue;}
        if($fname==".."){continue;}
        if(is_dir("$Workdir/$fname")){continue;}
        if(is_numeric($fname)){@unlink("$Workdir/$fname");continue;}
        if(preg_match("#_[0-9]+_tmp#", $fname)){@unlink("$Workdir/$fname");}
    }

    $Workdir="/usr/share/artica-postfix/framework";
    $files=scandir($Workdir);
    foreach ($files as $fname){
        if($fname=="."){continue;}
        if($fname==".."){continue;}
        if(is_dir("$Workdir/$fname")){continue;}
        if(is_numeric($fname)){@unlink("$Workdir/$fname");continue;}
        if(preg_match("#_[0-9]+_tmp#", $fname)){@unlink("$Workdir/$fname");}
    }
    return true;
}
function CleanAllindDir($DirPath,$maxtime=180){
	if(!is_dir($DirPath)){return;}
	$unix=new unix();
	if(!isset($GLOBALS["DELETED_SIZE"])){$GLOBALS["DELETED_SIZE"]=0;}
    if(!isset($GLOBALS["DELETED_FILES"])){$GLOBALS["DELETED_FILES"]=0;}
	if (!$handle = opendir($DirPath)) {return;}
	while (false !== ($file = readdir($handle))) {
		if ($file == "."){continue;}
		if ($file == ".."){continue;}		
		$path="$DirPath/$file";
		if(is_dir($path)){continue;}
		if(preg_match("#_[0-9]+_tmp#", $file)){@unlink($path);continue;}
		if(preg_match("#^krb5#", $file)){continue;}
		if(preg_match("#\.winbindd#", $file)){continue;}
		if($unix->is_socket($path)){continue;}
		$time=$unix->file_time_min($path);
		if($time<$maxtime){continue;}
		$size=@filesize($path)/1024;
		$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
		$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
		if($GLOBALS["VERBOSE"]){echo "$path - > DELETE\n";}
		@unlink($path);

	}
}

function logs_urgency($aspid=false){
	$unix=new unix();
	
	
	$TimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if($GLOBALS["VERBOSE"]){echo "TimeFile: $TimeFile\n";}
	if($GLOBALS["VERBOSE"]){echo "Pidfile: $Pidfile\n";}
	
	if(!$GLOBALS["FORCE"]){
		$timefile=$unix->file_time_min($TimeFile);
		if($timefile<60){return;}
	}
	
	
	if($aspid){
		$pid=$unix->get_pid_from_file($Pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$timeprc=$unix->PROCCESS_TIME_MIN($pid);
			if($timeprc>60){
				$unix->KILL_PROCESS($pid,9);
			}else{
				if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";}
				return;
			}
		}
		@file_put_contents($Pidfile, getmypid());
	}

    varlog();
	$df=$unix->find_program("df");

	exec("$df -h /var/log 2>&1",$results);
	
	foreach ($results as $index=>$line){
		if(!preg_match("#^(.+?)\s+([0-9\.,]+)([A-Z])\s+([0-9\.,]+).*?\s+([0-9\.,]+)%\s+#", $line,$re)){continue;}
		$purc=$re[5];
		break;
	}
	
	if($purc<100){return;}
	$echo=$unix->find_program("echo");
	$logf["artica-router.log"]=true;
	$logf["artica-smtp.log"]=true;
	$logf["auth.log"]=true;
	$logf["daemon.log"]=true;
	$logf["debug"]=true;
	$logf["dpkg.log"]=true;
	$logf["fetchmail.log"]=true;
	$logf["kern.log"]=true;
	$logf["mail.err"]=true;
	$logf["mail.log"]=true;
	$logf["mail.warn"]=true;
	$logf["messages"]=true;

	$logf["syslog"]=true;
	$logf["user.log"]=true;
	$logf["lighttpd/access.log"]=true;
	$logf["squid/store.log"]=true;
	$logf["apache2/unix-varrunnginx-authenticator.sock/nginx.access.log"]=true;
	$logf["apache2/unix-varrunnginx-authenticator.sock/nginx.error.log"]=true;
	$logf["artica-postfix/framework.log"]=true;
	$logf["samba/log.winbindd"]=true;
	$logf["samba/log.winbindd.old"]=true;
	$logf["clamav/clamav.log"]=true;
	$logf["clamav/clamd.log"]=true;
	$logf["clamav/freshclam.log"]=true;

	foreach ($logf as $filname=>$none){
		$path="/var/log/$filname";
		if(!is_file($path)){continue;}
		shell_exec("$echo \" \" > $path 2>&1");
	}
	

	
	
}

function buidsh(){
	
	$unix=new unix();
	$array=array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","x","y","z");
	$f=$unix->dirdir("/var/log/artica-postfix");
	
	$z[]="rm -rf /var/log/artica-postfix/* >/dev/null 2>&1";
	foreach ($f as $dir=>$line){
    	reset($array);
		$z[]="echo \"Removing content of $dir\"";
		foreach ($array as $b){
			$z[]="rm -rf $dir/$b* >/dev/null 2>&1";
		}
		
		for($i=0;$i<10;$i++){
			$z[]="rm -rf $dir/$i* >/dev/null 2>&1";
		}
	}
	
	echo @implode("\n", $z);
}


function Clean_attachments(){
	$unix=new unix();
	
	CleanAllindDir("/opt/artica/share/www/attachments");
	CleanAllindDir("/var/virusmail");
	
}

function Clean_apache_logs(){
	Apache_access_common_log();
	Apache_rotate();
	$sock=new sockets();
	$LogsRotateRemoveApacheMaxSize=$sock->GET_INFO("LogsRotateRemoveApacheMaxSize");
	if(!is_numeric($LogsRotateRemoveApacheMaxSize)){$LogsRotateRemoveApacheMaxSize=50;}
	$LogsRotateDefaultSizeRotation=$sock->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$unix=new unix();
	$dirs=$unix->dirdir("/var/log/apache2");
	$echo=$unix->find_program("echo");
	$FILZ["access.log"]=true;  
	$FILZ["error.log"]=true;    
	$FILZ["nginx.access.log"]=true;    
	$FILZ["nginx.error.log"]=true;    
	$FILZ["php.log"]=true;  
	$FILZ["ldap-framework.debug"]=true;  
	
	$syslog=new mysql_storelogs();

	foreach ($dirs as $dirpath=>$none){
		reset($FILZ);
		foreach ($FILZ as $filename=>$none2){
			$filepath="$dirpath/$filename";
			if(!is_file($filepath)){continue;}
			if(is_dir($filepath)){continue;}
			$timef=$unix->file_time_min($filepath);
			if($GLOBALS["VERBOSE"]){echo "$filepath {$timef}Mn\n";}
			if($timef>2880){@unlink($filepath);continue;}
			$size=@filesize($filepath);
			$size=$size/1024;
			$size=round($size/1000,2);
			if($GLOBALS["VERBOSE"]){echo "$filepath {$size}MB\n";}
			if($LogsRotateRemoveApacheMaxSize>0){
				if($size>$LogsRotateRemoveApacheMaxSize){ 
					if($GLOBALS["VERBOSE"]){echo "$filepath -> clean\n";}
					shell_exec("$echo \" \" >$filepath");
					continue; 
				}
			}
			
			if($size>$LogsRotateDefaultSizeRotation){
				if($GLOBALS["VERBOSE"]){echo "$filepath -> rotate\n";}
				$syslog->ROTATE_TOMYSQL($filepath);
				continue;
			}
			if($GLOBALS["VERBOSE"]){echo "$filepath -> NOTHING\n";}		
		}
		
	}
	
}





function Clean_tmp_path($aspid=false){
	$unix=new unix();
	
	$PidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	// /etc/artica-postfix/pids/exec.clean.logs.php.Clean_tmp_path.time
	if($GLOBALS["VERBOSE"]){echo "PidTime: $PidTime\n";}
	
	if(!$GLOBALS["VERBOSE"]){
		$timed=$unix->file_time_min($PidTime);
		if($timed<60){return;}
	}
	
	if($aspid){
		$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidpath);
		if($unix->process_exists($pid)){
			$unix->events(basename(__FILE__).":: ".__FUNCTION__." Already process $pid running.. Aborting");
			return;
		}
		
		@file_put_contents($pidpath, getmypid());
	}
	
	$sock=new sockets();
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	if($EnableRemoteSyslogStatsAppliance==1){$EnableRemoteSyslogStatsAppliance=1;}
	if($EnableRemoteSyslogStatsAppliance==1){clean_squid_users_size(true);}
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
    $php=$unix->LOCATE_PHP5_BIN();
    if(is_link("/usr/bin/X11")){@unlink("/usr/bin/X11");}


    if(is_file($PidTime)) {
        @unlink($PidTime);
    }
	@file_put_contents($PidTime, time());
    CleanBadProcesses();
    CleanRotatedFiles();
	sessions_clean();
    TempDir();

    if(is_file("/home/artica/SQLITE/system_events.db")){
	    $size=@filesize("/home/artica/SQLITE/system_events.db");
        $sizeK=$size/1024;$sizeM=$sizeK/1024;
        if($sizeM>200){
            $sizeM=round($sizeM,1);
            squid_admin_mysql(0,"Warning! System events database exceed 200MB ({$sizeM}MB) action=[remove]", null,__FILE__,__LINE__);
            @unlink("/home/artica/SQLITE/system_events.db");
            shell_exec("$php /usr/share/artica-postfix/exec.convert-to-sqlite.php");
        }
    }


	
	
	$syslog_sql=new mysql_storelogs();
	$LogsDirectoryStorage=$sock->GET_INFO("LogsDirectoryStorage");
	if(trim($LogsDirectoryStorage)==null){$LogsDirectoryStorage="/home/logs-backup";}
	if(is_dir($LogsDirectoryStorage)){
		if ($handle = opendir($LogsDirectoryStorage)){
			while (false !== ($file = readdir($handle))) {
				if($file=="."){continue;}
				if($file==".."){continue;}
				$path="$LogsDirectoryStorage/$file";
				if(is_dir($path)){continue;}
				if($GLOBALS["VERBOSE"]){echo "$path -> INJECT\n";}
				if(!$syslog_sql->ROTATE_ACCESS_TOMYSQL($path)){continue;}
				@unlink($path);
			}
		}
	}
	
	if(is_dir("/etc/artica-postfix/pids")){
		if ($handle = opendir("/etc/artica-postfix/pids")){
			while (false !== ($file = readdir($handle))) {
				if($file=="."){continue;}
				if($file==".."){continue;}
				$path="/etc/artica-postfix/pids/$file";
				if(is_dir($path)){continue;}
				if($unix->file_time_min($path)>72000){ @unlink($path); }
			}
		}
	}
	
	if(!isset($GLOBALS["DELETED_SIZE"])){
        $GLOBALS["DELETED_SIZE"]=0;
    }
    if(!isset($GLOBALS["DELETED_FILES"])){
        $GLOBALS["DELETED_FILES"]=0;
    }

	
	
	logs_urgency();
	Clean_attachments();
	Clean_apache_logs();
	vsftpd_log();
	$tmpdir=$unix->TEMP_DIR();

	$echo=$unix->find_program("echo");
	if(is_file("/var/log/apache2/unix-varrunnginx-authenticator.sock/nginx.access.log")){ shell_exec("$echo \" \" > /var/log/apache2/unix-varrunnginx-authenticator.sock/nginx.access.log"); }
	if(is_file("/var/log/apache2/unix-varrunnginx-authenticator.sock/nginx.error.log")){ shell_exec("$echo \" \" > /var/log/apache2/unix-varrunnginx-authenticator.sock/nginx.error.log"); }	
	
		 

	if(!is_dir("/home/logrotate/work")){@mkdir("/home/logrotate/work",0755,true);}
	if(!is_dir("/var/log/artica-postfix/postqueue")){@mkdir("/var/log/artica-postfix/postqueue",0755,true);}
	$q=new mysql_storelogs();

	
	
	
	if ($handle = opendir("/home/logrotate/work")){
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="/home/logrotate/work/$file";
				if(preg_match("#^php\.log-#", $file)){@unlink($path);continue;}
				if(preg_match("#^daemon\.log#", $file)){@unlink($path);continue;}
				if(preg_match("#^debug-#", $file)){@unlink($path);continue;}
				if(preg_match("#^kern\.log#", $file)){@unlink($path);continue;}
				if(preg_match("#^messages-[0-9]+\.gz#", $file)){@unlink($path);continue;}
				if(preg_match("#^access\.#", $file)){continue;}
				if(preg_match("#^cache\.#", $file)){continue;}
				if($unix->maillog_to_backupdir($path)){continue;}
				$timef=$unix->file_time_min($path);
				if($timef>5760){
					$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
					$q->events("Removing $path, exceed 5760mn",__FUNCTION__,__LINE__);
					@unlink($path);
				}
			}
		}
	}
	
	if($LogRotatePath<>"/home/logrotate"){
		if ($handle = opendir("$LogRotatePath/work")){
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					$path="$LogRotatePath/work/$file";
					if(preg_match("#^php\.log-#", $file)){@unlink($path);continue;}
					if(preg_match("#^daemon\.log#", $file)){@unlink($path);continue;}
					if(preg_match("#^debug-#", $file)){@unlink($path);continue;}
					if(preg_match("#^kern\.log#", $file)){@unlink($path);continue;}
					if(preg_match("#^messages-[0-9]+\.gz#", $file)){@unlink($path);continue;}
					if(preg_match("#^access\.#", $file)){continue;}
					if(preg_match("#^cache\.#", $file)){continue;}
					if($unix->maillog_to_backupdir($path)){continue;}
					$timef=$unix->file_time_min($path);
					if($timef>5760){
						$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
						$q->events("Removing $path, exceed 5760mn",__FUNCTION__,__LINE__);
						@unlink($path);
					}
				}
			}
		}		
	}
	
	
	
	if(is_dir("/home/logs-backup/olds")){
		if ($handle = opendir("/home/logs-backup/olds")){
			while (false !== ($file = readdir($handle))) {
				if ($file == "."){continue;}
				if ($file == ".."){continue;}
				$path="/home/logs-backup/olds/$file";
				if(preg_match("#^php\.log-#", $file)){@unlink($path);continue;}
				if(preg_match("#^daemon\.log#", $file)){@unlink($path);continue;}
				if(preg_match("#^debug-#", $file)){@unlink($path);continue;}
				if(preg_match("#^kern\.log#", $file)){@unlink($path);continue;}
				if($unix->maillog_to_backupdir($path)){continue;}
				if($GLOBALS["VERBOSE"]){echo "$file {$timef}mn\n";}
				$timef=$unix->file_time_min($path);
				if($timef>1880){
					$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
					$q->events("Removing $path, exceed 1880mn",__FUNCTION__,__LINE__);
					@unlink($path);
				}
		
			}
		}
	}
	
	if ($handle = opendir("/root")){
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="/root/$file";
				if(is_dir($path)){continue;}
				if(is_numeric($file)){@unlink($path);continue;}
				if(preg_match("#_[0-9]+_tmp#", $file)){@unlink($path);continue;}
                if(preg_match("#hs_err_pid[0-9]+\.log#", $file)){@unlink($path);continue;}
			}
		}
	}

	if(is_dir("/opt/artica/ldap-backup")){
		if ($handle = opendir("/opt/artica/ldap-backup")){
				while (false !== ($file = readdir($handle))) {
					if ($file != "." && $file != "..") {
						$path="/opt/artica/ldap-backup/$file";
						if(preg_match("#[0-9]+-0-9]+-0-9]+-0-9]+\.tar\.gz$#",$file)){
							$time=$unix->file_time_min($path);
							if($time>7200){@unlink($path);}
							continue;
						}
						
						if(is_numeric($file)){@unlink($path);continue;}
						}
					}
			}
	}
	
	
	if ($handle = opendir($tmpdir)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="$tmpdir/$file";
				if(is_dir($path)){
					if(preg_match("#^category_#", $file)){
						$time=$unix->dir_time_min($path);
						if($time>120){ @rmdir($path); continue;}
					}
					
					if(preg_match("#^[0-9]+\.[0-9]+.[0-9]+$#", $file)){
						$time=$unix->dir_time_min($path);
						if($time>120){ @rmdir($path); continue;}
					}
					
					continue;
				}
				
				if($GLOBALS["VERBOSE"]){echo "$path ?\n";} 
				if(is_dir($path)){if($GLOBALS["VERBOSE"]){echo "$path is a directory\n";} continue;}
				
				if(preg_match("#\.gif$#", $file)){
					$time=$unix->file_time_min($path);
					if($time>1){
						$size=@filesize($path)/1024;
						$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
						$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
						if($GLOBALS["VERBOSE"]){echo "$path - > DELETE\n";}
						@unlink($path);
						continue;
					}
					
				}
				
				if(preg_match("#process1-(.+?)\.tmp$#", $file)){
					$time=$unix->file_time_min($path);
					if($time>1){
						$size=@filesize($path)/1024;
						$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
						$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
						if($GLOBALS["VERBOSE"]){echo "$path - > DELETE\n";}
						@unlink($path);
						continue;
					}
					
					continue;
					
				}
				
		if(preg_match("#^artica-.+?\.tmp#", $file)){
					$time=$unix->file_time_min($path);
					if($GLOBALS["VERBOSE"]){echo "$path - > {$time}Mn\n";}
					if($time>10){
						$size=@filesize($path)/1024;
    					$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
    					$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;						
						if($GLOBALS["VERBOSE"]){echo "$path - > DELETE\n";}
						@unlink($path);
						continue;
					}
				}else{
					if($GLOBALS["VERBOSE"]){echo "$file -> NO MATCH ^artica-.+?\.tmp \n";} 
				}
				
			if(preg_match("#^artica-php#", $file)){
					$time=$unix->file_time_min($path);
					if($GLOBALS["VERBOSE"]){echo "$path - > {$time}Mn\n";}
					if($time>10){
						$size=@filesize($path)/1024;
    					$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
    					$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;						
						if($GLOBALS["VERBOSE"]){echo "$path - > DELETE\n";}
						@unlink($path);
						continue;
					}
				}else{
					if($GLOBALS["VERBOSE"]){echo "$file -> NO MATCH ^artica-php \n";} 
				}				
			}
		}
		
	}else{
		if($GLOBALS["VERBOSE"]){echo "$tmpdir failed...\n";} 
	}
	if($GLOBALS["VERBOSE"]){echo "$tmpdir done..\n";}
	

	
}
function nginx_log():bool{
    $unix=new unix();
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $NginxMaxLogFileSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxMaxLogFileSize"));
    $echo=$unix->find_program("echo");
    if($NginxMaxLogFileSize==0){$NginxMaxLogFileSize=300;}
    $other[]="/var/log/nginx/modsecurity.log";
    $other[]="/var/log/nginx/nginx.log";
    $other[]="/var/log/nginx/error.log";
    $other[]="/var/log/nginx/crowdsec.log";
    $other[]="/var/log/nginx/webcopy.log";
    $other[]="/var/log/nginx/uf/access.log";
    $RELOAD=false;
    foreach ($other as $filename){
        if(!is_file($filename)){continue;}
        if($EnableNginx==0){
            @unlink($filename);
            continue;
        }
        $sizeKB=@filesize($filename)/1024;
        $sizeMB=$sizeKB/1024;
        if($sizeMB<$NginxMaxLogFileSize){continue;}
        shell_exec("$echo \"\" >$filename");
        squid_admin_mysql(2,"Cleanup reverse-proxy $filename log ($sizeMB)",null,__FILE__,__LINE__);
        $RELOAD=true;
    }
    if(!$RELOAD){
        return false;
    }
    shell_exec("/usr/sbin/nginx -s reload");
    return true;
}


function Cleanbin(){
		if ($handle = opendir("/usr/share/artica-postfix/bin")) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$filepath="/usr/share/artica-postfix/bin/$file";
				if(is_dir($filepath)){continue;}
				if(!preg_match("#^st[0-9A-Za-z]+$#", $file)){continue;}
				echo "Remove $filepath\n";
				@unlink($filepath);
			}
		}
	}
	
}

function IfReallyExists(){
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	$me=basename(__FILE__);
	$meRegx=str_replace(".", "\.", $me);
	$MyPID=getmypid();
	exec("pgrep -l -f $me 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#pgrep#", $line)){continue;}
		if(preg_match("#^([0-9]+)\s+$meRegx#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "IfReallyExists() {$re[1]} <> $MyPID\n";}
			if($re[1]==$MyPID){continue;}
			return true;
		}
		
	}
	
	return false;
	
}





function CheckSystemUsersAndGroups():bool{
    $unix=new unix();
    $Groups=array("root","daemon","nogroup","bin","sys","adm","tty","disk","lp","mail","news","uucp","man","proxy","kmem","dialout","fax","voice","cdrom","floppy","tape","sudo","audio","dip","www-data","backup","operator","list","irc","src","gnats","shadow","utmp","video","sasl","plugdev","staff","games","users","nogroup","input","kvm","render","crontab","netdev","ssh","mysql","apt-mirror","ssl-cert","ntp","mlocate","prads","freerad","vnstat","stunnel4","vde2-net","memcache","davfs2","ziproxy","openldap","munin","msmtp","Debian-snmp","opendkim","avahi","glances","sambashare","winbindd_priv","ArticaStats","nvram","postfix","postdrop","squid","clamav","unbound","smokeping","mosquitto","quaggavty","quagga");

    foreach ($Groups as $GroupName){
        if($unix->UnixGroupExists($GroupName)){
            if($GLOBALS["VERBOSE"]){echo "$GroupName Exists OK\n";}
            continue;
        }

        if($GLOBALS["VERBOSE"]){echo "$GroupName missing, create it\n";}
        squid_admin_mysql(1,"Creating missing system group $GroupName",null,__FILE__,__LINE__);
        $unix->SystemCreateGroup($GroupName);

    }

    $users=array("daemon","bin","sys","sync","man","lp","mail","news","uucp","proxy","www-data","backup","list","irc","gnats","nobody","_apt","mysql","apt-mirror","privoxy","ntp","redsocks","prads","freerad","vnstat","stunnel4","sshd","vde2-net","memcache","davfs2","ziproxy","proftpd","ftp","openldap","munin","msmtp","Debian-snmp","opendkim","avahi","glances","ArticaStats","postfix","squid","smokeping","mosquitto","quagga","unbound");

    foreach ($users as $UserName){
        if($unix->UnixUserExists($UserName)){
            if($GLOBALS["VERBOSE"]){echo "$UserName Exists OK\n";}
            continue;
        }

        if($GLOBALS["VERBOSE"]){echo "$UserName missing, create it\n";}
        squid_admin_mysql(1,"Creating missing system user $UserName",null,__FILE__,__LINE__);
        $unix->SystemCreateUser($UserName,$UserName);
    }

    return true;

}

function events_tail_squid($text){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	//if($GLOBALS["VERBOSE"]){echo "$text\n";}
	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile="/var/log/artica-postfix/auth-tail.debug";
	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	$GLOBALS["CLASS_UNIX"]->events(basename(__FILE__)." $date $text");
	@fwrite($f, "$pid ".basename(__FILE__)." $date $text\n");
	@fclose($f);
}



function CleanRotatedFiles():bool{

	$unix=new unix();
	$sock=new sockets();

	
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	$ApacheLogRotate=$sock->GET_INFO("ApacheLogRotate");

	if(!is_numeric($ApacheLogRotate)){$ApacheLogRotate=1;}
	if(!is_numeric($LogRotatePath)){$LogRotatePath="/home/logrotate";}	
	
	
	$DirsToScan["/var/log"]=true;
	$DirsToScan["/var/log/apache2"]=true;
	$DirsToScan["/var/log/lighttpd"]=true;
	$DirsToScan["/var/log/ejabberd"]=true;
	
	
	$apache2=$unix->dirdir("/var/log/apache2");
	foreach ($apache2 as $WorkingDir=>$ligne){$DirsToScan[$WorkingDir]=true;}

	
	
	$q=new mysql_storelogs();
    foreach($DirsToScan as $WorkingDir=>$ligne){
	if($WorkingDir=="/var/log/squid"){continue;}
	$table=$unix->DirFiles($WorkingDir,"(\.|-)[0-9]+.*?$");
	$compressed["gz"]=true;
	$compressed["bz"]=true;
	$compressed["bz2"]=true;

	foreach ($table as $filename=> $ligne){
		$path="$WorkingDir/$filename";
		if($unix->file_time_min($path)<1440){continue;}
		$filedate=date('Y-m-d H:i:s',filemtime($path));
		$q->events("Injecting $path $filedate");
		if(!$q->ROTATE_TOMYSQL($path,$filedate)){continue;}
		
	}

	return true;
}
	return true;
	
}

function UrgencyChecks(){
	$unix=new unix();
	$sock=new sockets();
	
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidpath);
	if($unix->process_exists($pid)){
		$pidtime=$unix->PROCCESS_TIME_MIN($pid);
		$unix->events("UrgencyChecks():: ".__FUNCTION__." Already process $pid running since $pidtime Mn.. Aborting");
		return;
	}
	@file_put_contents($pidpath, getmypid());


	
	Clean_tmp_path(true);
	$f=$unix->DirFiles("/var/log");
	$f[]="syslog";
	$f[]="messages";
	$f[]="user.log";
	varlog();
	clean_articastatus();

	foreach ($f as $filename){
		if($filename=="mail.log"){continue;}
		$filepath="/var/log/$filename";
		if(!is_file($filepath)){continue;}
		$size=$unix->file_size($filepath);
		$size=$size/1024;
		$size=round($size/1000,2);
		$unix->events("UrgencyChecks():: $filepath {$size}M");
		$ARRAY[$filepath]=$size;
	}
	
	$restart=false;
	
	
	if($restart){
		@chmod("/etc/init.d/syslog",0755);
		shell_exec("/etc/init.d/syslog restart");
		shell_exec("/etc/init.d/artica-syslog restart");
		shell_exec("/etc/init.d/postfix-logger restart");
	}	

	
}


function logrotatelogs($nopid=false){}


function GetSizeMB($fpath):int{
    if(!is_file($fpath)){return 0;}
    $size=(@filesize($fpath)/1024)/1000;
    return intval($size);
}


function CleanSSHDFailed(){
	$unix=new unix();
	$directory="/var/log/artica-postfix/sshd-failed";
	if(!is_dir($directory)){return;}
	
	if ($handle = opendir($directory)) {
		while (false !== ($file = readdir($handle))) {
			$filepath="$directory/$file";
			if ($file == "." ){continue;}
			if ($file == ".." ){continue;}
			if(is_dir($filepath)){continue;}
			$timefile=$unix->file_time_min($filepath);
			if($timefile<240){continue;}
			echo "Remove $filepath\n";
			@unlink($filepath);
		}
	}
	
}


function clean_sshportal(){
    $unix=new unix();
    $directory="/var/log/sshdportal";
    if(!is_dir($directory)){return;}

    if ($handle = opendir($directory)) {
        while (false !== ($file = readdir($handle))) {
            $filepath="$directory/$file";
            if ($file == "." ){continue;}
            if ($file == ".." ){continue;}
            if(is_dir($filepath)){continue;}
            $timefile=$unix->file_time_min($filepath);
            if($timefile<10080){continue;}
            echo "Remove $filepath\n";
            @unlink($filepath);
        }
    }

}







function CleanLogs($aspid=false){
    if(!isset($GLOBALS["DELETED_SIZE"])){$GLOBALS["DELETED_SIZE"]=0;}
    if(!isset($GLOBALS["DELETED_FILES"])){$GLOBALS["DELETED_FILES"]=0;}
	$unix=new unix();
	$timefile="/etc/artica-postfix/pids/exec.clean.logs.php.CleanLogs.time";
	if(!$aspid){
		$maxtime=480;
		$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidpath);
		if($unix->process_exists($pid)){
			$unix->events(basename(__FILE__).":: ".__FUNCTION__." Already process $pid running.. Aborting");
			return;
		}
		
		@file_put_contents($pidpath,getmypid());
		
		
		$timeOfFile=$unix->file_time_min($timefile);
		if($timeOfFile<$maxtime){
			return;
		}
	
	}
	
	@unlink($timefile);
	@file_put_contents($timefile,time());
	


	maillog();
    Clean_tmp_path();
	CleanOldInstall();
	clean_articastatus();
	LogRotateTimeAndSize("/var/log/samba");
	CleanBindLogs();

	$size=str_replace("&nbsp;"," ",FormatBytes($GLOBALS["DELETED_SIZE"]));
	echo "$size cleaned :  {$GLOBALS["DELETED_FILES"]} files\n";
    if(!isset($GLOBALS["UNLINKED"])){$GLOBALS["UNLINKED"]=array();}
    if(!is_array($GLOBALS["UNLINKED"])){$GLOBALS["UNLINKED"]=array();}

	if($GLOBALS["DELETED_SIZE"]>500){
		send_email_events("$size logs files cleaned",
		"{$GLOBALS["DELETED_FILES"]} files cleaned for $size free disk space:\n
		".@implode("\n",$GLOBALS["UNLINKED"]),"logs_cleaning");
	}	
	$GLOBALS["DELETED_SIZE"]=0;
	$GLOBALS["DELETED_FILES"]=0;
	init();
	cleanTmplogs();
	sessions_clean();
	$size=str_replace("&nbsp;"," ",FormatBytes($GLOBALS["DELETED_SIZE"]));
	echo "$size cleaned :  {$GLOBALS["DELETED_FILES"]} files\n";
	if($GLOBALS["DELETED_SIZE"]>500){
	    squid_admin_mysql(2,"$size logs files cleaned","{$GLOBALS["DELETED_FILES"]} files cleaned for $size free disk space:\n
		".@implode("\n",$GLOBALS["UNLINKED"]),__FILE__,__LINE__);
	}
    virus_bv_miner();
	
}


function clean_articastatus(){
	$path="/var/log";
	$directory=opendir($path);
	while ($file = readdir($directory)) {
		if($file=="."){continue;}
		if($file==".."){continue;}
		if(!is_file("$path/$file")){continue;}
		if(!preg_match("#artica-status.log.[0-9]+#", $file)){
			if($GLOBALS["VERBOSE"]){echo "SKIP: $file\n";}
			continue;}
		@unlink("$path/$file");
	}
	
	
}





function cleanTmplogs(){
	
	$f["st16WgqD"]=true;
	$f["st2rbmZC"]=true;
	$f["st7lHTjd"]=true;
	$f["st9q9qku"]=true;
	$f["stbBnixv"]=true;
	$f["stDPuj6u"]=true;
	$f["stFAEeXx"]=true;
	$f["stH9DKQm"]=true;
	$f["stjFocrx"]=true;
	$f["stLKCNJm"]=true;
	$f["stneMj5n"]=true;
	$f["stok2sFA"]=true;
	$f["stRgTmRp"]=true;
	$f["stvKhBOk"]=true;
	$f["stWpdmxs"]=true;
	$f["stXBCQtr"]=true;
	$f["stynhnC7"]=true;
	$f["styU74dl"]=true;
	$f["st2BrkvI"]=true;
	$f["st3b2g3y"]=true;
	$f["st9LHbOZ"]=true;
	$f["stau5VhG"]=true;
	$f["stBOljyq"]=true;
	$f["stE1SkGz"]=true;
	$f["stghVBHr"]=true;
	$f["sthZJVUE"]=true;
	$f["stjy3MtG"]=true;
	$f["stMrVlvh"]=true;
	$f["stojwuux"]=true;
	$f["stPrJoES"]=true;
	$f["stSk5lXh"]=true;
	$f["stvrDRNN"]=true;
	$f["stX9eicQ"]=true;
	$f["styKwAAu"]=true;
	$f["stYpIvJf"]=true;
	$f["stIFOQ6A"]=true;
	$f["stMSOCis"]=true;
	foreach ($f as $num=>$ligne){
		if(is_file("/usr/share/artica-postfix/bin/$num")){
			@unlink("/usr/share/artica-postfix/bin/$num");
		}
	
	}
	$f=array();	
	
	
	
$badfiles["100k"]=true;
$badfiles["2"]=true;
$badfiles["size"]=true;
$badfiles["versions"]=true;
$badfiles["3"]=true;
$badfiles["named_dump.db"]=true;
$badfiles["named.stats"]=true;
$badfiles["log-queries.info"]=true;
$badfiles["log-named-auth.info"]=true;
$badfiles["log-lame.info"]=true;
$badfiles["bind.pid"]=true;
$badfiles["ipp.txt"]=true;
$badfiles["debug"]=true;
$badfiles["log-update-debug.log"]=true;
$badfiles["ldap.ppu"]=true;
$badfiles["#"]=true;	
$baddirs["2000"]=true;


    foreach ($badfiles as $num=>$ligne){
		if($num==null){continue;}
		if(is_file("/usr/share/artica-postfix/$num")){@unlink("/usr/share/artica-postfix/$num");}
	}
    foreach ($baddirs as $num=>$ligne){
		if($num==null){continue;}
		if(is_dir("/usr/share/artica-postfix/$num")){shell_exec("/bin/rm -rf /usr/share/artica-postfix/$num");}
	}	
	
	$unix=new unix();
	$countfile=0;
	foreach (glob("/tmp/artica*") as $filename) {
		
	$countfile++;
		if($countfile>500){
			if(is_overloaded()){
				$unix->send_email_events("Clean Files: [/tmp/artica*]: System is overloaded ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]}",
				"The clean logs function is stopped and wait a new schedule with best performances",
				"logs_cleaning");
				exit();
			}
			$countfile=0;
		}		
		
    	$time=$unix->file_time_min($filename);
    	if($time>2){
    		$size=@filesize($filename)/1024;
    		$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
    		$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;
    		if($GLOBALS["VERBOSE"]){echo "Delete $filename\n";}
    		$unix->events(basename(__FILE__)." Delete $filename");
    		@unlink($filename);
    	}else{
    	if($GLOBALS["VERBOSE"]){echo "$filename TTL:$time \n";}
    	}
	}
	
	foreach (glob("/var/log/artica-postfix/postfix.awstats.log.*") as $filename){
		$countfile++;
		$size=@filesize($filename)/1024;
		$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
		$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1; 
		@unlink($filename);  
	}
	
	
	$countfile=0;
if($GLOBALS["VERBOSE"]){echo "/tmp/process1*\n";}
	foreach (glob("/tmp/process1*") as $filename) {
		
	$countfile++;
		if($countfile>500){
			if(is_overloaded()){
				$unix->send_email_events("Clean Files: [/tmp/process1*]: System is overloaded ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]}",
				"The clean logs function is stopped and wait a new schedule with best performances",
				"logs_cleaning");
				exit();
			}
			$countfile=0;
		}				
		
    	$time=$unix->file_time_min($filename);
    	if($time>1){
    		$size=@filesize($filename)/1024;
    		$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size; 
    		$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1;   
    		if($GLOBALS["VERBOSE"]){echo "Delete $filename\n";}
    		$unix->events(basename(__FILE__)." Delete $filename");	
    		@unlink($filename);
    	}else{
    		if($GLOBALS["VERBOSE"]){echo "$filename TTL:$time \n";}
    	}
	}
	
}


function sessions_clean_parse($directory,$CleanPHPSessionTime,$APACHE_SRC_ACCOUNT=null,$APACHE_SRC_GROUP=null){
	$CleanPHPSessionTime=$CleanPHPSessionTime-1;
	if(!is_dir($directory)){return;}
	
	if (!$handle = opendir($directory)) {return;}
	$unix=new unix();
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$filename="$directory/$fileZ";
		if(is_dir($filename)){continue;}
		$time=$unix->file_time_min($filename);
		if($time>$CleanPHPSessionTime){@unlink($filename);continue;}
		if($APACHE_SRC_ACCOUNT<>null){
			$unix->chown_func($APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP,$filename);
		}
	}
		
	
}

function CleanBadProcesses(){
    CleanProcbyPattern("\.\/yamd");
    CleanProcbyPattern("\.\/yam --daemonized");
}

function CleanProcbyPattern($pattern=null){
    $unix=new unix();
    $pgrep=$unix->find_program("pgrep");
    exec("$pgrep -lf \"$pattern\" 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if(preg_match("#pgrep#",$line)){continue;}
        if(!preg_match("#^([0-9]+)\s+(.*)#",$line,$re)){continue;}
        $pid=intval($re[1]);
        squid_admin_mysql(0,"Killing bad process [{$re[2]}]",
            @file_get_contents("/proc/$pid/status"),__FILE__,__LINE__);
        $unix->KILL_PROCESS($pid,9);
    }



}


function sessions_clean(){
	$unix=new unix();
	$sock=new sockets();
	$TimeFile="/etc/artica-postfix/pids/exec.clean.logs.php.sessions_clean.time";
	if($unix->file_time_min($TimeFile)<60){return;}
	
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	
	$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
	$CleanPHPSessionTime=$sock->GET_INFO("CleanPHPSessionTime");
	if(!is_numeric($CleanPHPSessionTime)){$CleanPHPSessionTime=1440;}
	
	sessions_clean_parse("/var/lib/php5",$CleanPHPSessionTime,$APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP);
	sessions_clean_parse("/var/lib/php5-zarafa",$CleanPHPSessionTime,$APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP);
	sessions_clean_parse("/home/squid/hotspot/sessions",$CleanPHPSessionTime,"squid","squid");
	
	sessions_clean_parse("/home/squid/error_page_sessions",$CleanPHPSessionTime,$APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP);
	sessions_clean_parse("/usr/share/artica-postfix/ressources/logs/jGrowl",360,$APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP);
	sessions_clean_parse("/usr/share/artica-postfix/ressources/logs/web/help",360,$APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP);
	
		
	sessions_clean_parse("/usr/share/artica-postfix/ressources/conf",360,$APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP);
	sessions_clean_parse("/home/squid/error_page_cache",60);
}







function CleanBindLogs(){
	$f["/var/cache/bind/log-lame.info"]=1;
	$f["/var/cache/bind/log-queries.info"]=1;
    foreach ($f as $filepath=>$none){
		$size=round(unix_file_size("$filepath")/1024);
		if($size>51200000){
			@unlink($filepath);
			$GLOBALS["DELETED_FILES"]=$GLOBALS["DELETED_FILES"]+1; 
			$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+$size;
		}
		
		
	}
	
}


function PathsStatus(){
	$f[]="/root";
	foreach (glob("/usr/share/*",GLOB_ONLYDIR) as $filename) {
		$f[]=$filename;
	}
	
	foreach ($f as $num=>$dir){
		echo "$dir\t".str_replace("&nbsp;"," ",FormatBytes(dirsize($dir)/1024))."\n";
	}
	
}




// /var/log/samba

function dirsize($path):int{
	$unix=new unix();
	
	exec($unix->find_program("du")." -b $path",$results);
	$tt=implode("",$results);
	if(preg_match("#([0-9]+)\s+#",$tt,$re)){return $re[1];}
	return 0;
}

function CleanOldInstall(){
	
	foreach (glob("/root/APP_*",GLOB_ONLYDIR) as $dirname) {
		if(!is_dir($dirname)){return;}
		$time=file_get_time_min($dirname);
		
		if($time>2880){
			echo "Removing $dirname\n";
			$GLOBALS["DELETED_SIZE"]=$GLOBALS["DELETED_SIZE"]+dirsize($dirname);
			shell_exec("/bin/rm -rf $dirname");}
		}
	
}
function is_overloaded($file=null){
	if(!isset($GLOBALS["CPU_NUMBER"])){
			$users=new usersMenus();
			$GLOBALS["CPU_NUMBER"]=intval($users->CPU_NUMBER);
	}
	
	$array_load=sys_getloadavg();
	$internal_load=$array_load[0];
	$cpunum=$GLOBALS["CPU_NUMBER"]+1.5;
	if($file==null){$file=basename(__FILE__);}

	if($internal_load>$cpunum){
		$GLOBALS["SYSTEM_INTERNAL_LOAD"]=$internal_load;
		return true;
		
	}
	return false;

	
}





function unix_file_size($path){
	$unix=new unix();
    $res=0;
    if(!isset($GLOBALS["stat"])){$GLOBALS["stat"]=null;}
	if($GLOBALS["stat"]==null){$GLOBALS["stat"]=$unix->find_program("stat");}
	$path=$unix->shellEscapeChars($path);
	exec("{$GLOBALS["stat"]} $path ",$results);
	foreach ($results as $num=>$line){
		if(preg_match("#Size:\s+([0-9]+)\s+Blocks#",$line,$re)){
			$res=$re[1];break;
		}
	}
	if(!is_numeric($res)){$res=0;}
	return $res;
}




function used_space(){
	$GLOBALS["OUTPUT"]=true;

}




function clean_space(){
	
	$unix=new unix();
	$Pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$PidTime="/etc/artica-postfix/pids/exec.clean.logs.php.clean_space.time";
	// /etc/artica-postfix/pids/exec.clean.logs.php.squidClean.time

	
	$pid=$unix->get_pid_from_file($Pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";}
		return;
	}
	@file_put_contents($Pidfile, getmypid());

	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($PidTime);
		if($time<240){echo "Only each 240mn\n";exit();}

	}
	@unlink($PidTime);
	@file_put_contents($PidTime, time());
	
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$CLEANED=array();
	
	$home_remove[]="/home/bwm-ng";
	$home_remove[]="/home/ntopng";
	$home_remove[]="/home/c-icap";
	$home_remove_files[]="/home/artica/tmp";
	
	$percent=$unix->DIRECTORY_USEPERCENT("/home");
	
	if($GLOBALS["VERBOSE"]){echo "Percent $percent\n";}
	
	if($percent>90){

        foreach ($home_remove as $dirPath){
			if(!is_dir($dirPath)){continue;}
			if(is_link($dirPath)){continue;}
			if($unix->DIRECTORY_MountedOnDirAndDismount($dirPath)>0){
				if($unix->DIRECTORY_MountedOnDirAndDismount($dirPath)>0){ continue;}
			}
			shell_exec("$rm -rf $dirPath/*");
			$CLEANED[]=$dirPath;
			
		}

        foreach ($home_remove_files as $dirPath){
			if(!is_dir($dirPath)){continue;}
			if(is_link($dirPath)){continue;}
			if($unix->DIRECTORY_MountedOnDirAndDismount($dirPath)>0){
				if($unix->DIRECTORY_MountedOnDirAndDismount($dirPath)>0){ continue;}
			}
			shell_exec("$rm -f $dirPath/*.tmp");
			shell_exec("$rm -f $dirPath/*.log");
			shell_exec("$rm -f $dirPath/*.txt");
			shell_exec("$rm -f $dirPath/*.gz");
			shell_exec("$rm -f $dirPath/*.tgz");
			shell_exec("$rm -f $dirPath/artica-*");
			$CLEANED[]=$dirPath;
				
		}		
		
		if(count($CLEANED)>0){
			$percent2=$unix->DIRECTORY_USEPERCENT("/home");
			if($percent2<$percent){
				squid_admin_mysql(2, "/home partition exceed 90% ({$percent}%) down to {$percent2}%", 
				"Cleaned directories was ".@implode("\n", $CLEANED),__FILE__,__LINE__);
			}
		}else{
			squid_admin_mysql(2, "/home partition exceed 90% ({$percent}%)",null,__FILE__,__LINE__);
		}
		
		
	}
}

function CleanDcurl(){
	$unix=new unix();
	$q=new mysql();
	if(!is_dir("/var/lib/mysql/artica_events")){return;}
	if ($handle = opendir("/var/lib/mysql/artica_events")) {
		while (false !== ($file = readdir($handle))) {
			$filepath="/var/lib/mysql/artica_events/$file";
			if ($file == "." ){continue;}
			if ($file == ".." ){continue;}
			if(is_dir($filepath)){continue;}
			
			if(preg_match("#Kav4Proxy_([0-9]+)\.frm$#", $file)){
				$timefile=$unix->file_time_min($filepath);
				$tablename=str_ireplace(".frm", "", $file);
				echo "Removing $tablename ({$timefile}Mn )\n";
				$q->QUERY_SQL("DROP TABLE `$tablename`","artica_events");
				continue;
			}
			
			if(!preg_match("#^([0-9]+)_.*?\.frm$#", $file)){continue;}
			$tablename=str_ireplace(".frm", "", $file);
			$timefile=$unix->file_time_min($filepath);
			if($timefile<10080){continue;}
			echo "Removing $tablename ({$timefile}Mn exceed 10080mn )\n";
			$q->QUERY_SQL("DROP TABLE `$tablename`","artica_events");
			
		}
	}	
	
	
	
}

function wordpress_remove_cache(){

    $directory="/root/.wp-cli/cache/core";
    if(!is_dir($directory)){return true;}
    $unix=new unix();

    if ($handle = opendir($directory)) {
        while (false !== ($file = readdir($handle))) {
            $filepath = "$directory/$file";
            if ($file == ".") {continue;}
            if ($file == "..") {continue;}
            if (is_dir($filepath)) {continue;}
            if(!preg_match("#\.zip$#",$file)){continue;}
            $Reste=$unix->file_time_min($filepath);
            if($Reste>1440){
                @unlink($filepath);
                continue;
            }
        }
    }
return true;
}



function patchsBackup(){
    $CURRENT=trim(@file_get_contents(ARTICA_ROOT."/VERSION"));
    $baseDir="/home/artica/patchsBackup";
    if(!is_dir($baseDir)){return true;}
    $unix=new unix();
    $rm=$unix->find_program("rm");
    if ($handle = opendir($baseDir)) {
        while (false !== ($filename = readdir($handle))) {
            if ($filename == ".") {continue;}
            if ($filename == "..") {continue;}
            $fname = "$baseDir/$filename";
            if(!is_dir($fname)){continue;}
            if($filename==$CURRENT){
                echo "Skip $CURRENT\n";
                continue;
            }
            echo "Must remove $fname\n";
            shell_exec("$rm -rf $fname");
            squid_admin_mysql(1,"Removing old Service Pack backups for $filename",null,__FILE__,__LINE__);
        }
    }
    return true;
}

function check_viruses(){
     $unix=new unix();
    //https://raw.githubusercontent.com/CpanelInc/tech-CSI/master/suspicious_files.txt
     $rm=$unix->find_program("rm");
     $MAIN_PATH=ARTICA_ROOT;
     $f[]="/tmp/.X25-unix/dota3.tar.gz";
     $f[]="/tmp/.X25-unix/.rsync";
     $f[]="/tmp/up.txt";
     $f[]="/var/tmp/dota3.tar.gz";
     $f[]="/var/tmp/.system925D22cronF21";
     $f[]="/var/tmp/.systemcache436621";

    $found=false;
    if(is_dir("/root/.configrc")){$found=true;}
    if(!$found) {
        foreach ($f as $fpath) {
            if (is_file($fpath)) {$found = true;break;}
        }
    }

    if($found){
        squid_admin_mysql(0,"ALERT Miner virus detected !! /root/.configrc [action=clean]",
            "/root/.configrc suspicious directory as been found, artica made the cleaning procedure 
             but you have to reboot your server",__FILE__,__LINE__);
            if(is_dir("/root/.configrc")){shell_exec("$rm -rf /root/.configrc");}
            shell_exec("/usr/bin/crontab -r");
            foreach ($f as $fpath){if(is_file($fpath)){@unlink($fpath);}}
    }

    if(is_file("$MAIN_PATH/ressources/suspicious_files.txt")) {
        $f = explode("\n", @file_get_contents("$MAIN_PATH/ressources/suspicious_files.txt"));
        foreach ($f as $filepath) {
            $filepath = trim($filepath);
            if ($filepath == null) {
                continue;
            }
            if (!is_file($filepath)) {
                continue;
            }
            squid_admin_mysql(0, "ALERT Suspicious file discovered !! $filepath [action=remove]",
                "$filepath as been discovered\nYou need to contact our technical support", __FILE__, __LINE__);
            @unlink($filepath);
        }
    }

}

function virus_bv_miner(){
    $unix=new unix();
    $killall=$unix->find_program("killall");
    $contents=array();
    $files[]="/etc/cron.d/dump.rdb";
    $files[]="/etc/cron.d/zzh";
    $files[]="/etc/cron.d/dog";
    $files[]="/etc/cron.hourly/jarvos";
    $files[]="/tmp/juremsh";
    $files[]="/tmp/newcron";
    $files[]="/tmp/nurianem";
    $files[]="/root/kc.sh";
    $files[]="/etc/init.d/jarvos";
    $files[]="/etc/red2.so";
    $files[]="/etc/zzh";

    $BVMINER=false;
    $DETECTED=array();
    foreach ($files as $file){
        if(!is_file($file)){continue;}
        $BVMINER=true;
        $DETECTED[]=$file;
        @unlink($file);
    }

    if ($handle = opendir("/var/spool/cron/crontabs")) {
        while (false !== ($filename = readdir($handle))) {
            if ($filename == ".") {continue;}
            if ($filename == "..") {continue;}
            if ($filename == "root") {continue;}
            $fname = "/var/spool/cron/crontabs/$filename";
            $DETECTED[]=$fname;
            $contents[]="Content of $fname\n";
            $contents[]=@file_get_contents($fname)."\n";
            @unlink($file);
        }
    }

    $final_text=@implode($DETECTED)."\n".@implode("\n",$contents);
    if($BVMINER){
        squid_admin_mysql(0,"INFECTED System BV:Miner-GZ [Drp] [action=clean]",
            "Discovered files\n$final_text",__FILE__,__LINE__);
        shell_exec("$killall nurianem");
        return true;
    }

    if(count($DETECTED)>0){
        squid_admin_mysql(0,"Suspicious files likely BV:Miner-GZ [Drp] [action=clean]",
            "Discovered/deleted files\n$final_text",__FILE__,__LINE__);
    }


    return true;
}
function getDirContents($dir, &$results = array()) {
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $results[] = $path;
        } else if ($value != "." && $value != "..") {
            getDirContents($path, $results);
            $results[] = $path;
        }
    }

    return $results;
}

?>