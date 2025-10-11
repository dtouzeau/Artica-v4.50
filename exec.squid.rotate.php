<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.acls.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
$GLOBALS["FORCE"]=false;
$GLOBALS["PROGRESS"]=false;
if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["FORCE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
writelogs("Task::{$GLOBALS["SCHEDULE_ID"]}:: Executed with ".@implode(" ", $argv)." ","MAIN",__FILE__,__LINE__);

if(isset($argv[1])) {
    if ($argv[1] == "--backup-to-nas-rdpprody") {
        backup_rdpproxy();
        exit();
    }
    if ($argv[1] == "--backup-to-nas-mail") {
        backup_mail();
        exit();
    }

    if($argv[1]=="--unbound"){build(true);}
    if($argv[1]=="--dnsdist-service"){build(true);exit();}

    if ($argv[1] == "--test-nas") {
        BackupToNas_tests();
        exit();
    }
    if ($argv[1] == "--purge") {
        purge_legal_logs();
        exit();
    }
    if ($argv[1] == "--backup-postgres") {
        backup_postgres();
        exit();
    }
    if ($argv[1] == "--backup-haproxy") {
        backup_haproxy();
        exit();
    }
    if ($argv[1] == "--backup-hacluster") {
        backup_hacluster();
        exit();
    }
    if ($argv[1] == "--backup-proxy") {
        backup_proxy();
        exit();
    }
    if($argv[1]=="--mount"){BackupToNasMount();exit;}

    if($argv[1]=="--sysapp"){
        move_backup_to_syslog_appliance();
        exit;
    }

    if($GLOBALS["PROGRESS"] OR $GLOBALS["VERBOSE"] OR $GLOBALS["FORCE"]){
        xrun();
        exit;
    }
    if($GLOBALS["SCHEDULE_ID"]>0){
        xrun();
        exit;
    }

    $unix=new unix();
    $unix->ToSyslog("[Legal Logs]: Unable to understand $argv[1]",false,basename(__FILE__));
    die("Unable to understand $argv[1]");

}
xrun();

function build_progress_rotation($text,$pourc):bool{
	if(!$GLOBALS["PROGRESS"]){return false;}
	echo "$pourc% $text\n";
	$cachefile=PROGRESS_DIR."/squid.rotate.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	return true;
}

function backup_haproxy():bool{
	$BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
	if($BackupSquidLogsUseNas==0){exit();}
	$BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	if(!is_dir("$BackupMaxDaysDir/haproxy")){return false;}
	BackupToNas("$BackupMaxDaysDir/haproxy");
    return true;
}
function backup_hacluster():bool{
    $BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
    if($BackupSquidLogsUseNas==0){return false;}
    $BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
    if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
    if(!is_dir("$BackupMaxDaysDir/hacluster")){return false;}
    BackupToNas("$BackupMaxDaysDir/hacluster");
    return true;
}


function backup_postgres():bool{
	$BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
	if($BackupSquidLogsUseNas==0){return false;}
	$sock=new sockets();
	$InFluxBackupDatabaseDir=$sock->GET_INFO("InFluxBackupDatabaseDir");
	if($InFluxBackupDatabaseDir==null){$InFluxBackupDatabaseDir="/home/artica/influx/backup";}
	BackupToNas($InFluxBackupDatabaseDir);
    return true;
}
function backup_mail(){
	$BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
	if($BackupSquidLogsUseNas==0){exit();}
	$BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	BackupToNas("$BackupMaxDaysDir/mail");
}
function backup_rdpproxy(){
    $BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
    if($BackupSquidLogsUseNas==0){exit();}
    $BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
    if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
    BackupToNas("$BackupMaxDaysDir/rdpproxy");

}
function backup_proxy(){
    $BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
    if($BackupSquidLogsUseNas==0){exit();}
    $BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
    if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
    BackupToNas("$BackupMaxDaysDir/proxy");

}


function ifdirMounted($directory):bool{
	
	$f=explode("\n",@file_get_contents("/proc/mounts"));
	foreach ( $f as $index=>$line ){
		if(strpos("    $line", "$directory")>0){return true;}
		
	}

	return false;
}

function purge_legal_logs(){
	$unix=new unix();
	$Pidfile="/etc/artica-postfix/pids/exec.squid.rotate.php.purge_legal_logs.pid";
	$PidTime="/etc/artica-postfix/pids/exec.squid.rotate.php.purge_legal_logs.time";
	$ServerRunSince=$unix->ServerRunSince();
	
	if($ServerRunSince<5){
		if($GLOBALS["VERBOSE"]){echo "Uptime {$ServerRunSince}Mn, require at least 5mn".__FUNCTION__."()\n";}
		exit();
	}
	
	$pid=$unix->get_pid_from_file($Pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Aborting Task already running pid $pid ".__FUNCTION__."()\n";}
		return;
	}
	
	if(!$GLOBALS["FORCE"]){
		$TimeExec=$unix->file_time_min($PidTime);
		if($TimeExec<720){
			if($GLOBALS["VERBOSE"]){echo "Aborting Task {$TimeExec}mn, require 20mn ".__FUNCTION__."()\n";}
			return;
		}
	}
	
	@unlink($PidTime);
	@file_put_contents($PidTime, time());
	@file_put_contents($Pidfile, getmypid());

	$BackupMaxDaysDir2=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));
	if($BackupMaxDaysDir2==null){$BackupMaxDaysDir2="/home/logrotate_backup";}
	if(!is_dir($BackupMaxDaysDir2)){exit();}
	analyze_destination_directory($BackupMaxDaysDir2);
	
}


function ss5_log($BackupMaxDaysDir){
	
	if(!is_file("/var/log/ss5/ss5.log")){return;}
	$unix=new unix();
	$syslog=new mysql_storelogs();
	
	$LogsRotateDefaultSizeRotation=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsRotateDefaultSizeRotation");
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}
	$FinalDirectory="$BackupMaxDaysDir/proxy/".date("Y")."/".date("m")."/".date("d");
	if(!is_dir($FinalDirectory)){@mkdir($FinalDirectory,0755,true);}
	
	if(!is_dir($FinalDirectory)){
		squid_admin_mysql(0, "[Legal Logs]: Unable to rotate /var/log/ss5/ss5.log (permission denied on $BackupMaxDaysDir)",$FinalDirectory,__FILE__,__LINE__);
	}
	
	$size=@filesize("/var/log/ss5/ss5.log");
	$size=$size/1024;
	$size=$size/1024;
	$syslog->events("/var/log/ss5/ss5.log........: {$size}M",__FUNCTION__,__LINE__);
	if($size<$LogsRotateDefaultSizeRotation){
		$syslog->events("/var/log/ss5/ss5.log........: {$size}M but need {$LogsRotateDefaultSizeRotation}M, aborting",__FUNCTION__,__LINE__);
		return;
	}
	$destfile="$FinalDirectory/ss5.".time().".gz";
	$syslog->events("/var/log/ss5/ss5.log........: Compress to $destfile",__FUNCTION__,__LINE__);
	if(!$unix->compress("/var/log/ss5/ss5.log", $destfile)){
		squid_admin_mysql(0, "[Legal Logs]: Unable to compress /var/log/ss5/ss5.log to $destfile",$GLOBALS["COMPRESSOR_ERROR"],__FILE__,__LINE__);
		@unlink($destfile);
		return;
	}
	squid_admin_mysql(1, "Restart Socks Proxy for log rotation task.", null,__FILE__,__LINE__);
	@unlink("/var/log/ss5/ss5.log");
	shell_exec("/etc/init.d/ss5 restart");
	
}

function unbound_log($BackupMaxDaysDir=null):bool{
    $SourceFile="/var/log/unbound.log";
    if(!is_file($SourceFile)){return false;}
    $size=@filesize($SourceFile);
    if($size<10){return false;}
    $unix=new unix();

    $LogRotatePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath");
    $FinalDirectory="$BackupMaxDaysDir/proxy/".date("Y")."/".date("m")."/".date("d");
    if(!is_dir($FinalDirectory)){@mkdir($FinalDirectory,0755,true);}

    $CopiedFile="$LogRotatePath/unbound.log";
    if(is_file($CopiedFile)){
        $time=filemtime($CopiedFile);
        $destfile="$FinalDirectory/dns-cache.$time.gz";
        if(is_file($destfile)){$destfile="$FinalDirectory/dns-cache.".time().".gz";}
        if(!$unix->compress($CopiedFile,$destfile)){
            squid_admin_mysql(0, "Unable to compress $CopiedFile to $destfile",$GLOBALS["COMPRESSOR_ERROR"],__FILE__,__LINE__);
            return false;
        }
        @unlink($CopiedFile);
    }

    if(!@copy($SourceFile,$CopiedFile)){
        @unlink($CopiedFile);
        squid_admin_mysql(0, "[Legal Logs]: Unable to move $SourceFile to $CopiedFile",null,__FILE__,__LINE__);
        return false;
    }

    $echo=$unix->find_program("echo");
    shell_exec("$echo \"\" >$SourceFile");
    $unbound_control=$GLOBALS["CLASS_UNIX"]->find_program("unbound-control");
    $cmd="$unbound_control -c /etc/unbound/unbound.conf log_reopen 2>&1";
    shell_exec($cmd);

    $destfile="$FinalDirectory/dns-cache.".time().".gz";
    if(!$unix->compress($CopiedFile, $destfile)){
        squid_admin_mysql(0, "[Legal Logs]: Unable to compress $CopiedFile to $destfile",$GLOBALS["COMPRESSOR_ERROR"],__FILE__,__LINE__);
        @unlink($destfile);
        return false;
    }
    @unlink($CopiedFile);
    shell_exec("/usr/sbin/artica-phpfpm-service -logrotate-nas");
    return true;
}


function dnsdist_log($BackupMaxDaysDir=null):bool{
    $SourceFile="/var/log/dnsdist-service.log";
    if(!is_file($SourceFile)){return false;}
    $size=@filesize($SourceFile);
    if($size<10){return false;}
    $unix=new unix();

    $LogRotatePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath");
    $FinalDirectory="$BackupMaxDaysDir/proxy/".date("Y")."/".date("m")."/".date("d");
    if(!is_dir($FinalDirectory)){@mkdir($FinalDirectory,0755,true);}

    $CopiedFile="$LogRotatePath/dnsdist-service.log";
    if(is_file($CopiedFile)){
        $time=filemtime($CopiedFile);
        $destfile="$FinalDirectory/dnsdist-service.$time.gz";
        if(is_file($destfile)){$destfile="$FinalDirectory/dnsdist-service.".time().".gz";}
        if(!$unix->compress($CopiedFile,$destfile)){
            squid_admin_mysql(0, "Unable to compress $CopiedFile to $destfile",$GLOBALS["COMPRESSOR_ERROR"],__FILE__,__LINE__);
            return false;
        }
        @unlink($CopiedFile);
    }

    if(!@copy($SourceFile,$CopiedFile)){
        @unlink($CopiedFile);
        squid_admin_mysql(0, "[Legal Logs]: Unable to move $SourceFile to $CopiedFile",null,__FILE__,__LINE__);
        return false;
    }

    $echo=$unix->find_program("echo");
    shell_exec("$echo \"\" >$SourceFile");
    $dnsdist=$GLOBALS["CLASS_UNIX"]->find_program("dnsdist");
    $cmd="$dnsdist -C /etc/dnsdist.conf -e 'mylogaction:reload()' 2>&1";
    shell_exec($cmd);


//    $unbound_control=$GLOBALS["CLASS_UNIX"]->find_program("unbound-control");
//    $cmd="$unbound_control -c /etc/unbound/unbound.conf log_reopen 2>&1";
//    shell_exec($cmd);

    $destfile="$FinalDirectory/dnsdist-service.".time().".gz";
    if(!$unix->compress($CopiedFile, $destfile)){
        squid_admin_mysql(0, "[Legal Logs]: Unable to compress $CopiedFile to $destfile",$GLOBALS["COMPRESSOR_ERROR"],__FILE__,__LINE__);
        @unlink($destfile);
        return false;
    }
    @unlink($CopiedFile);
    return true;
}

function dnsdistfw_log($BackupMaxDaysDir=null):bool{
    $SourceFile="/var/log/dnsfw.log";
    if(!is_file($SourceFile)){return false;}
    $size=@filesize($SourceFile);
    if($size<10){return false;}
    $unix=new unix();

    $LogRotatePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath");
    $FinalDirectory="$BackupMaxDaysDir/proxy/".date("Y")."/".date("m")."/".date("d");
    if(!is_dir($FinalDirectory)){@mkdir($FinalDirectory,0755,true);}

    $CopiedFile="$LogRotatePath/dnsfw.log";
    if(is_file($CopiedFile)){
        $time=filemtime($CopiedFile);
        $destfile="$FinalDirectory/dnsfw.$time.gz";
        if(is_file($destfile)){$destfile="$FinalDirectory/dnsfw.".time().".gz";}
        if(!$unix->compress($CopiedFile,$destfile)){
            squid_admin_mysql(0, "Unable to compress $CopiedFile to $destfile",$GLOBALS["COMPRESSOR_ERROR"],__FILE__,__LINE__);
            return false;
        }
        @unlink($CopiedFile);
    }

    if(!@copy($SourceFile,$CopiedFile)){
        @unlink($CopiedFile);
        squid_admin_mysql(0, "[Legal Logs]: Unable to move $SourceFile to $CopiedFile",null,__FILE__,__LINE__);
        return false;
    }

    $echo=$unix->find_program("echo");
    shell_exec("$echo \"\" >$SourceFile");
    $dnsdist=$GLOBALS["CLASS_UNIX"]->find_program("dnsdist");
    $cmd="$dnsdist -C /etc/dnsdist.conf -e 'mylogaction:reload()' 2>&1";
    shell_exec($cmd);
    $destfile="$FinalDirectory/dnsfw.".time().".gz";
    if(!$unix->compress($CopiedFile, $destfile)){
        squid_admin_mysql(0, "[Legal Logs]: Unable to compress $CopiedFile to $destfile",$GLOBALS["COMPRESSOR_ERROR"],__FILE__,__LINE__);
        @unlink($destfile);
        return false;
    }
    @unlink($CopiedFile);
    shell_exec("/usr/sbin/artica-phpfpm-service -logrotate-nas");
    return true;
}

function xrun(){
	$timefile="/etc/artica-postfix/pids/exec.squid.rotate.php.build.time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();

	
	
	$pid=$unix->PIDOF_PATTERN(basename(__FILE__));
	$MyPid=getmypid();
	if($MyPid<>$pid){
		if($unix->process_exists($pid)){
			$timeFile=$unix->PROCESS_TIME_INT($pid);
			$pidCmdline=@file_get_contents("/proc/$pid/cmdline");
			if($timeFile<30){
				echo "Already PID $pid is running since {$timeFile}Mn\n";
				squid_admin_mysql(1, "[Legal Logs]: Skip task, already running $pid since {$timeFile}Mn", "Running: $pidCmdline",__FILE__,__LINE__);
				exit();
			}else{
			squid_admin_mysql(1, "[Legal Logs]: Killing old task $pid running more than 30mn ({$timeFile}Mn)", "Running: $pidCmdline",__FILE__,__LINE__);
			$unix->KILL_PROCESS($pid);
			}
			}
			}
	
			@file_put_contents($pidfile, getmypid());
	
			if(!$GLOBALS["FORCE"]){
		$time=$unix->file_time_min($timefile);
			if($time<60){echo "Only each 60mn\n";exit();}
					@unlink($timefile);
					@file_put_contents($timefile, time());
			}
	
	build();

}


function move_backup_to_syslog_appliance():bool{
    $LegalLogArticaClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaClient"));
    $unix=new unix();
    if(!$unix->CORP_LICENSE()){return false;}
    if($LegalLogArticaClient==0){return false;}
    $BackupMaxDaysDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));
    if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
    $YearsPath=$BackupMaxDaysDir."/proxy";
    move_backup_to_syslog_appliance_years($YearsPath);
    return true;

}

function move_backup_to_syslog_appliance_years($YearsPath):bool{
    echo "Scanning years from $YearsPath\n";
    if (!$handle = opendir($YearsPath)) {return false;}
    while (false !== ($file = readdir($handle))) {
            if ($file == ".") {continue;}
            if ($file == "..") {continue;}
            if(!is_numeric($file)){continue;}
            $FullPath="$YearsPath/$file";
            if(!is_dir($FullPath)){
                echo "Skip directory $FullPath\n";
                continue;
            }
            echo "Found Year $file\n";
            move_backup_to_syslog_appliance_months($FullPath);

    }

    return true;
}
function move_backup_to_syslog_appliance_months($MonthsPath):bool{
    echo "Scanning Months from $MonthsPath\n";
    if (!$handle = opendir($MonthsPath)) {return false;}
    while (false !== ($file = readdir($handle))) {
        if ($file == ".") {continue;}
        if ($file == "..") {continue;}
        if(!is_numeric($file)){continue;}
        $FullPath="$MonthsPath/$file";
        if(!is_dir($FullPath)){continue;}
        echo "Found Month $file\n";
        move_backup_to_syslog_appliance_days($FullPath);

    }
    return true;
}
function move_backup_to_syslog_appliance_days($DaysPath):bool{
    echo "Scanning Days from $DaysPath\n";
    if (!$handle = opendir($DaysPath)) {return false;}
    while (false !== ($file = readdir($handle))) {
        if ($file == ".") {continue;}
        if ($file == "..") {continue;}
        if(!is_numeric($file)){continue;}
        $FullPath="$DaysPath/$file";
        if(!is_dir($FullPath)){continue;}
        echo "Found Day $file\n";
        move_backup_to_syslog_appliance_hours($FullPath);
    }
    return true;
}
function move_backup_to_syslog_appliance_hours($FinaPath):bool{
    echo "Scanning Files inside $FinaPath\n";
    if (!$handle = opendir($FinaPath)) {return false;}
    while (false !== ($file = readdir($handle))) {
        if ($file == ".") {continue;}
        if ($file == "..") {continue;}
        $FullPath="$FinaPath/$file";
        if(!is_file($FullPath)){continue;}
        echo "Found file $FullPath\n";
        if(!move_backup_to_syslog_appliance_upload($FullPath)){ return false; }
        @unlink($FullPath);
    }

    return true;
}
function move_backup_to_syslog_appliance_upload($FileToUpload):bool{
    $unix=new unix();
    $verb=null;
    if(!preg_match("#\.gz$#",$FileToUpload)){
        echo "$FileToUpload, not a gz file\n";
        return false;
    }
    $LegalLogArticaServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaServer"));
    $LegalLogArticaPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaPort"));
    $LegalLogArticaName="$LegalLogArticaServer:$LegalLogArticaPort";

    $CURLOPT_HTTPHEADER[]="Pragma: no-cache,must-revalidate";
    $CURLOPT_HTTPHEADER[]="Cache-Control: no-cache,must revalidate";
    $CURLOPT_HTTPHEADER[]="Expect:";

    if($GLOBALS['VERBOSE']){$verb="?verbose=true";}

    $MAIN_URI = "https://{$LegalLogArticaServer}:$LegalLogArticaPort/nodes.listener.php{$verb}";
    $ch = curl_init($MAIN_URI);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');

    $md5_file=md5_file($FileToUpload);

    $cFile = curl_file_create($FileToUpload);

    $post=array(
        'file' => $cFile,
        'legalslogs-upload' => 'yes',
        'target'=>$FileToUpload,
        'md5'=>$md5_file
    );


    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $errno=curl_errno($ch);
    $CURLINFO_HTTP_CODE=intval(curl_getinfo($ch,CURLINFO_HTTP_CODE));


    if($errno>0){
        $unix->ToSyslog("[Legal Logs]: Unable to post $FileToUpload to $LegalLogArticaName Network error $errno",
            false,basename(__FILE__));
        squid_admin_mysql(1,"Unable to post $FileToUpload to $LegalLogArticaName Network error $errno");
        return false;
    }

    if($CURLINFO_HTTP_CODE<>200) {
        $unix->ToSyslog("[Legal Logs]: Unable to post $FileToUpload to $LegalLogArticaName HTTP error $CURLINFO_HTTP_CODE",
            false,basename(__FILE__));
        squid_admin_mysql(1,"Unable to post $FileToUpload to $LegalLogArticaName HTTP error $CURLINFO_HTTP_CODE",null,__FILE__,__LINE__);
        return false;
    }


    return true;
}

function mv_dir_source($BackupMaxDaysDir):bool{
    if($BackupMaxDaysDir=="/home/logrotate_backup"){return true;}
    if(is_link($BackupMaxDaysDir)){
        $BackupMaxDaysDir_src=@readlink($BackupMaxDaysDir);
        if($BackupMaxDaysDir_src=="/home/logrotate_backup"){return true;}
    }
    $unix=new unix();
    $syslog=new mysql_storelogs();
    $mv=$unix->find_program("mv");
    $hostname=$unix->hostname_g();
    if(!is_dir("$BackupMaxDaysDir/$hostname")) {@mkdir("$BackupMaxDaysDir/$hostname", 0755, true);}
    if(!is_dir("$BackupMaxDaysDir/$hostname")) {
        $syslog->events("[Legal Logs]: $BackupMaxDaysDir/$hostname no such directory or permission denied",__FUNCTION__,__LINE__);
        return false;
    }
    $ttmp=time();
    @touch("$BackupMaxDaysDir/$hostname/$ttmp");
    if(!is_file("$BackupMaxDaysDir/$hostname/$ttmp")){
        $syslog->events("[Legal Logs]: $BackupMaxDaysDir/$hostname/$ttmp  permission denied",__FUNCTION__,__LINE__);
        return false;
    }
    @unlink("$BackupMaxDaysDir/$hostname/$ttmp");
    exec("$mv /home/logrotate_backup/* $BackupMaxDaysDir/$hostname/ --ignore-permissions 2>&1",$results );
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        $syslog->events("[Legal Logs]: $line",__FUNCTION__,__LINE__);
    }

    return true;
}


function build($OnlyUnbound=false):bool{
	$unix           = new unix();
	$ls             = $unix->find_program("ls");
	$squidbin       = $unix->LOCATE_SQUID_BIN();
    $ROTATED        = false;
	$syslog=new mysql_storelogs();
	$SquidLogRotateFreq=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogRotateFreq"));
	if($SquidLogRotateFreq<10){$SquidLogRotateFreq=1440;}
	$LastRotate=$unix->file_time_min("/etc/artica-postfix/pids/squid-rotate-cache.time");
	$LogsRotateDefaultSizeRotation=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsRotateDefaultSizeRotation");
    $LegalLogArticaClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaClient"));
	if(!is_numeric($LogsRotateDefaultSizeRotation)){$LogsRotateDefaultSizeRotation=100;}

	$BackupMaxDaysDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	$BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
	
	$SquidRotateAutomount=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomount"));
	$SquidRotateAutomountRes=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomountRes"));
	$SquidRotateAutomountFolder=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomountFolder"));
	$LogRotatePath=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath"));
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}

    move_backup_to_syslog_appliance();



	
	
	if($LegalLogArticaClient==0) {
        if ($SquidRotateAutomount == 1) {
            shell_exec("$ls /automounts/$SquidRotateAutomountRes >/dev/null 2>&1");
            if (ifdirMounted("/automounts/$SquidRotateAutomountRes")) {
                $BackupSquidLogsUseNas = 0;
                $BackupMaxDaysDir = "/automounts/$SquidRotateAutomountRes/$SquidRotateAutomountFolder";

            } else {
                $syslog->events("/automounts/$SquidRotateAutomountRes not mounted",
                    __FUNCTION__, __LINE__);
                squid_admin_mysql(1, "[ROTATE],Auto-mount $SquidRotateAutomountRes not mounted", null, __FILE__, __LINE__);
            }

        }
    }
	
	$BackupMaxDaysDir=str_replace("//", "/", $BackupMaxDaysDir);
	$BackupMaxDaysDir=str_replace("\\", "/", $BackupMaxDaysDir);
	if(!is_dir($BackupMaxDaysDir)){@mkdir($BackupMaxDaysDir,0755,true);}

	if(!is_dir($BackupMaxDaysDir)){
		$syslog->events("[Legal Logs]: $BackupMaxDaysDir not such directory or permission denied",__FUNCTION__,__LINE__);
		squid_admin_mysql(1, "[ROTATE],$BackupMaxDaysDir not such directory or permission denied", null,__FILE__,__LINE__);
		if($SquidRotateAutomount==1){
			$BackupMaxDaysDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));
			if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
			if(!is_dir($BackupMaxDaysDir)){@mkdir($BackupMaxDaysDir,0755,true);}
			$syslog->events("[Legal Logs]: Return back to $BackupMaxDaysDir",__FUNCTION__,__LINE__);
		}else{
			return false;
		}
	}

    mv_dir_source($BackupMaxDaysDir);


	$InFluxBackupDatabaseDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("InFluxBackupDatabaseDir");
	if($InFluxBackupDatabaseDir==null){
		$InFluxBackupDatabaseDir="/home/artica/influx/backup";
	}
	

	$LogRotateAccess="$LogRotatePath/access";
	$LogRotateTail="$LogRotatePath/tail";
	$LogRotateCache="$LogRotatePath/cache";

	$syslog->events("SquidLogRotateFreq...............: {$SquidLogRotateFreq}Mn",__FUNCTION__,__LINE__);
	$syslog->events("LastRotate.......................: {$LastRotate}Mn",__FUNCTION__,__LINE__);
	$syslog->events("Working directory................: $LogRotatePath",__FUNCTION__,__LINE__);
	$syslog->events("Launch rotation when exceed......: {$LogsRotateDefaultSizeRotation}M",__FUNCTION__,__LINE__);
	$syslog->events("Final storage directory..........: {$BackupMaxDaysDir}",__FUNCTION__,__LINE__);
	$syslog->events("Backup files to a NAS............: {$BackupSquidLogsUseNas}",__FUNCTION__,__LINE__);
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));

	
	ss5_log($BackupMaxDaysDir);
    unbound_log($BackupMaxDaysDir);
    dnsdist_log($BackupMaxDaysDir);
    dnsdistfw_log($BackupMaxDaysDir);
    if($OnlyUnbound){$SQUIDEnable=0;}
	
	if($SQUIDEnable==1) {
        if ($handle = opendir("/var/run/squid")) {
            while (false !== ($file = readdir($handle))) {
                if ($file == ".") {
                    continue;
                }
                if ($file == "..") {
                    continue;
                }
                $path = "/var/run/squid/$file";
                if (preg_match("#\.[0-9]+\.status$#", $file)) {
                    $time = $unix->file_time_min($path);
                    if ($time > 1440) {
                        $syslog->events("[Legal Logs]: Removing $path", __FUNCTION__, __LINE__);
                        @unlink($path);
                    }
                    continue;
                }
                if (preg_match("#\.[0-9]+\.state$#", $file)) {
                    $time = $unix->file_time_min($path);
                    if ($time > 1440) {
                        $syslog->events("[Legal Logs]: Removing $path", __FUNCTION__, __LINE__);
                        @unlink($path);
                    }
                    continue;
                }
            }
        }
    }
    if($LegalLogArticaClient==1) {$SQUIDEnable=1;}
    if($SQUIDEnable==0) {
        GrabDirectory($LogRotatePath);
        if($BackupSquidLogsUseNas==1){
            build_progress_rotation("Backup to N.A.S",50);
            BackupToNas($BackupMaxDaysDir);
            build_progress_rotation("Backup to N.A.S BigData backups",50);
            BackupToNas($InFluxBackupDatabaseDir,false);

        }else{
            build_progress_rotation("Scanning $BackupMaxDaysDir",50);
            analyze_destination_directory($BackupMaxDaysDir);
        }

        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("BackupMaxDaysDirCurrentSize", $unix->DIRSIZE_KO($BackupMaxDaysDir));
        @chmod("/etc/artica-postfix/settings/Daemons/BackupMaxDaysDirCurrentSize",0777);
        build_progress_rotation("Scanning {success}",100);
        return true;
    }


    if(!is_dir($LogRotateTail)){@mkdir($LogRotateTail,0755,true);}

	$syslog->events("[Legal Logs]: Analyze /var/log/squid directory for cache.log",__FUNCTION__,__LINE__);
	if (!$handle = opendir("/var/log/squid")){
		build_progress_rotation("Unable to open /var/log/squid",110);
		$syslog->events("[Legal Logs]: Unable to open /var/log/squid directory.",__FUNCTION__,__LINE__);
		return false;
		
	}
	
	build_progress_rotation("Scanning /var/log/squid",40);
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
			$path="/var/log/squid/$file";
			if(is_dir($path)){continue;}
			if(!preg_match("#^cache\.log\.[0-9]+$#", $file)){continue;}
            if($LegalLogArticaClient==1) {@unlink($path);continue; }


			@mkdir("$LogRotateCache",0755,true);
			$destfile="$LogRotateCache/$file.".time().".log";
			if(!@copy($path, $destfile)){
				$syslog->events("[Legal Logs]: Unable to copy $path to $destfile",__FUNCTION__,__LINE__);
				@unlink($destfile);
				continue;			
			}
			$syslog->events("[Legal Logs]: Removed $path",__FUNCTION__,__LINE__);
			@unlink($path);
		}
	}
	
	

	GrabDirectory("/var/log/squid");
	GrabDirectory($LogRotatePath);
    access_tail_errors($BackupMaxDaysDir,$syslog);
	
	
	$syslog->events("[Legal Logs]: Analyze $LogRotateAccess for access.log",__FUNCTION__,__LINE__);
	
	if (!$handle = opendir($LogRotateAccess)){
		$syslog->events("[Legal Logs]: Unable to open $LogRotateAccess directory.",__FUNCTION__,__LINE__);
		return false;
	}
	

	
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
			$path="$LogRotateAccess/$file";
			echo "OPEN $path\n";
			if(is_dir($path)){continue;}
			if(!preg_match("#^access\.log#", $file)){continue;}
			range_fichier_source($path,$BackupMaxDaysDir);
			$ROTATED=true;
		}
	}
	
	
	if ($handle = opendir($LogRotateTail)){
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				$path="$LogRotateTail/$file";
				echo "OPEN $path\n";
				if(is_dir($path)){continue;}
				if(!preg_match("#^squidtail\.log#", $file)){continue;}
				range_fichier_tail($path,$BackupMaxDaysDir);
				$ROTATED=true;
			}
		}	
			
	}
	
	
	
	if(!$ROTATED){return false;}


	$syslog->events("[Legal Logs]: Analyze /home/logrotate/work for access.log",__FUNCTION__,__LINE__);
	build_progress_rotation("Scanning /home/logrotate/work",45);
	analyze_directory("/home/logrotate/work",$BackupMaxDaysDir);
	
	$BackupMaxDaysDir2=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));
	if($BackupMaxDaysDir2==null){$BackupMaxDaysDir2="/home/logrotate_backup";}
	
	if($BackupMaxDaysDir2<>$BackupMaxDaysDir){
		build_progress_rotation("Scanning $BackupMaxDaysDir2",46);
		$syslog->events("[Legal Logs]: $BackupMaxDaysDir2 is different of $BackupMaxDaysDir",__FUNCTION__,__LINE__);
		analyze_directory($BackupMaxDaysDir2,$BackupMaxDaysDir);
	}
	
	
	build_progress_rotation("Scanning /home/logrotate/merged",47);
	analyze_garbage_directory("/home/logrotate/merged",$BackupMaxDaysDir,1440);
	
	build_progress_rotation("Scanning $LogRotateCache",48);
	analyze_cache_directory($LogRotateCache,$BackupMaxDaysDir);
	
	build_progress_rotation("Scanning /home/logrotate/work",49);
	analyze_cache_directory("/home/logrotate/work",$BackupMaxDaysDir);
	
	build_progress_rotation("Scanning /home/squid/cache-logs",49);
	analyze_cache_directory("/home/squid/cache-logs",$BackupMaxDaysDir);
	

	if($BackupSquidLogsUseNas==1){
		build_progress_rotation("Backup to N.A.S",50);
		BackupToNas($BackupMaxDaysDir);
		build_progress_rotation("Backup to N.A.S BigData backups",50);
		BackupToNas($InFluxBackupDatabaseDir,false);
				
	}else{
		build_progress_rotation("Scanning $BackupMaxDaysDir",50);
		analyze_destination_directory($BackupMaxDaysDir);
	}
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("BackupMaxDaysDirCurrentSize", $unix->DIRSIZE_KO($BackupMaxDaysDir));
	@chmod("/etc/artica-postfix/settings/Daemons/BackupMaxDaysDirCurrentSize",0777);
	return true;
}

function GrabDirectory($DirectorySource){
	$LogRotatePath=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath"));
    $LegalLogArticaClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaClient"));
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	$LogRotateAccess="$LogRotatePath/access";
	$LogRotateTail="$LogRotatePath/tail";
	$syslog=new mysql_storelogs();

	$syslog->events("[Legal Logs]: Analyze $DirectorySource directory for access.log",__FUNCTION__,__LINE__);
	if (!$handle = opendir($DirectorySource)){
		$syslog->events("[Legal Logs]: Unable to open $DirectorySource directory.",__FUNCTION__,__LINE__);
		return;
	
	}
	
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
			$path="$DirectorySource/$file";
			if(is_dir($path)){continue;}
				
			if(preg_match("#^childs-access\.log\.[0-9]+$#", $file)){
				$destfile="$LogRotateAccess/$file.".time().".log";
				if($LegalLogArticaClient==1){@unlink($destfile);continue;}
				if(!@copy($path, $destfile)){
					$syslog->events("[Legal Logs]: Unable to copy $path to $destfile",__FUNCTION__,__LINE__);
					@unlink($destfile);
					continue;
				}
				$syslog->events("[Legal Logs]: Removed $path",__FUNCTION__,__LINE__);
				@unlink($path);
				continue;
			}
				
			$syslog->events("[Legal Logs]: Analyze $file ^squidtail\.log\.[0-9]+$",__FUNCTION__,__LINE__);
			if(preg_match("#^squidtail\.log\.[0-9]+$#", $file)){
				$destfile="$LogRotateTail/$file";
                if($LegalLogArticaClient==1){@unlink($destfile);continue;}
				if(!@copy($path, $destfile)){
					$syslog->events("[Legal Logs]: Unable to copy $path to $destfile",__FUNCTION__,__LINE__);
					@unlink($destfile);
					continue;
				}
				$syslog->events("[Legal Logs]: Removed $path",__FUNCTION__,__LINE__);
				@unlink($path);
				continue;
			}
			$syslog->events("Analyze $file ^access\.log\.[0-9]+$",__FUNCTION__,__LINE__);
				
			if(!preg_match("#^access\.log\.[0-9]+$#", $file)){continue;}
				
				
			$destfile="$LogRotateAccess/$file.".time().".log";
            if($LegalLogArticaClient==1){@unlink($destfile);continue;}
			if(!@copy($path, $destfile)){
				$syslog->events("[Legal Logs]: Unable to copy $path to $destfile",__FUNCTION__,__LINE__);
				@unlink($destfile);
				continue;
			}
			$syslog->events("[Legal Logs]: Removed $path",__FUNCTION__,__LINE__);
			@unlink($path);
		}
	}	
	
}



function analyze_destination_directory($path){
	$unix=new unix();
	$find=$unix->find_program("find");
	$SquidRotateClean=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateClean"));
    if($SquidRotateClean==0){return;}
	
	if($GLOBALS["VERBOSE"]){echo "$find \"$path\" 2>&1\n";}
	exec("$find \"$path\" 2>&1",$results);

    $BackupMaxDays=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDays"));
    if($BackupMaxDays<5){$BackupMaxDays=365;}

	

	foreach ($results as $filepath){
	    if($GLOBALS["VERBOSE"]){echo "analyze_destination_directory $filepath\n";}
	    if(is_dir($filepath)){continue;}
	    $timem=filemtime($filepath);
	    $time=$unix->file_time_min($filepath);
        $timeDay=round($time/1440);
        if($timeDay<$BackupMaxDays){continue;}
        $f=array();
        @unlink($filepath);
        $f[]="This log file is stored in the directory for {$timeDay} Days (since ".date("Y-m-d H:i:s",$timem).")";
        $f[]="The maximal defined storage period is $BackupMaxDays Days";
        squid_admin_mysql(1,"[Legal Logs]: Removed legal log $filepath",@implode("\n",$f),__FILE__,__LINE__);
		
	}

	
}


function analyze_garbage_directory($directory,$BackupMaxDaysDir,$maxtime){
	$syslog=new mysql_storelogs();
	$unix=new unix();
	if(!is_dir($directory)){
		$syslog->events("[Legal Logs]: $directory is not a directory, aborting",__FUNCTION__,__LINE__);
		return;
	}
	if (!$handle = opendir($directory)){
		$syslog->events("[Legal Logs]: $directory failed to parse",__FUNCTION__,__LINE__);
		return;
	}
	
	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		$path="$directory/$file";
		if(is_dir($path)){continue;}
		if(!preg_match("#^access\.log#", $file)){continue;}
		$time=$unix->file_time_min($path);
		if($time<$maxtime){continue;}
		range_fichier_source($path,$BackupMaxDaysDir);
	}
	
	
}






function analyze_directory($directory,$BackupMaxDaysDir){
	$syslog=new mysql_storelogs();
	if(!is_dir($directory)){
		$syslog->events("[Legal Logs]: $directory is not a directory, aborting",__FUNCTION__,__LINE__);
		return;
	}
	if (!$handle = opendir($directory)){
		$syslog->events("[Legal Logs]: $directory failed to parse",__FUNCTION__,__LINE__);
		return;
	}
	
	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		$path="$directory/$file";
		if(is_dir($path)){continue;}
		if(preg_match("#^dmesg\.[0-9]+\.gz$#", $file)){@unlink($path);continue;}
		if(!preg_match("#^access\.log#", $file)){continue;}
		range_fichier_source($path,$BackupMaxDaysDir);
	}
	
}

function analyze_cache_directory($directory,$BackupMaxDaysDir){
	$syslog=new mysql_storelogs();
	if(!is_dir($directory)){
		$syslog->events("[Legal Logs]: $directory is not a directory, aborting",__FUNCTION__,__LINE__);
		return;
	}
	if (!$handle = opendir($directory)){
		$syslog->events("[Legal Logs]: $directory failed to parse",__FUNCTION__,__LINE__);
		return;
	}

	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		$path="$directory/$file";
		if(is_dir($path)){continue;}
		if(!preg_match("#^cache\.log#", $file)){continue;}
		range_fichier_cache($path,$BackupMaxDaysDir);
	}

}


function range_fichier_cache($filepath,$BackupMaxDaysDir):bool{
    $LegalLogArticaClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaClient"));

    if($LegalLogArticaClient==1){
        @unlink($filepath);
        return true;
    }

	$syslog=new mysql_storelogs();
	$unix=new unix();
	$ext=$unix->file_extension($filepath);

	$LogRotatePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	$LogRotateCache="$LogRotatePath/cache";
	$LogRotateCacheFailed="$LogRotatePath/cache-failed";
	@mkdir($LogRotateCache,0755,true);
	$basename=basename($filepath);
	$syslog->events("[Legal Logs]: Analyze $filepath [$ext] ",__FUNCTION__,__LINE__);
	if($ext=="gz"){
		if(preg_match("#\.tar\.gz$#", $basename)){
			$syslog->events("[Legal Logs]: $filepath is a tarball!",__FUNCTION__,__LINE__);
			return false;
		}
	
		$syslog->events("[Legal Logs]: Extract $filepath",__FUNCTION__,__LINE__);
		$ExtractedFile="$LogRotateCache/$basename.log";
		if(!$unix->uncompress($filepath,$ExtractedFile )){
			@unlink($ExtractedFile);
			$syslog->events("[Legal Logs]: Unable to extract $filepath to $ExtractedFile",__FUNCTION__,__LINE__);
			return false;
		}
		$syslog->events("[Legal Logs]: Removing $filepath [$ext] ",__FUNCTION__,__LINE__);
		@unlink($filepath);
		$filepath=$ExtractedFile;
	}
	
	$unix=new unix();
	$ztimes=cache_logs_getdates($filepath);
	if(!$ztimes){
		$syslog->events("[Legal Logs]: Failed to parse $filepath",__FUNCTION__,__LINE__);
		@mkdir($LogRotateCacheFailed,0755,true);
		if(@copy($filepath, "$LogRotateCacheFailed/$basename")){
			@unlink($filepath);
		}
		return false;
	}
	
	$xdatefrom=$ztimes[0];
	$NewFileName="cache-".filename_from_arraydates($ztimes);
	
	$FinalDirectory="$BackupMaxDaysDir/proxy/".date("Y",$xdatefrom)."/".date("m",$xdatefrom)."/".date("d",$xdatefrom);
	@mkdir($FinalDirectory,0755,true);
	
	if(!is_dir($FinalDirectory)){
		$syslog->events("[Legal Logs]: Unable to create $FinalDirectory directory permission denied",__FUNCTION__,__LINE__);
		return false;
	}
	
	if(!$unix->compress($filepath, "$FinalDirectory/$NewFileName")){
		@unlink("$FinalDirectory/$NewFileName");
		$syslog->events("[Legal Logs]: Unable to compress $FinalDirectory/$NewFileName permission denied",__FUNCTION__,__LINE__);
		return false;
	}
	
	$syslog->events("[Legal Logs]: Success to create $FinalDirectory/$NewFileName",__FUNCTION__,__LINE__);
	$syslog->events("[Legal Logs]: Removing source file $filepath",__FUNCTION__,__LINE__);
	@unlink($filepath);	
	return true;
	

}




function filename_from_arraydates($ztimes,$suffix=null):string{
	$unix=new unix();
	$hostname=$unix->hostname_g();
	$xdatefrom=$ztimes[0];
	$xdateTo=$ztimes[1];
	$dateFrom=date("Y-m-d_H-i-s",$xdatefrom);
	$dateTo=date("Y-m-d_H-i-s",$xdateTo);
	if($suffix<>null){$suffix=".$suffix.";}
	return "$hostname.{$suffix}$dateFrom--$dateTo.gz";
}


function access_tail_errors($BackupMaxDaysDir,$syslog):bool{

    $LogRotatePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath");
    if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
    $LogRotateAccessFailed="$LogRotatePath/failed";
    if(!is_dir($LogRotateAccessFailed)){return true;}
    $syslog->events("[Legal Logs]: Analyze $LogRotateAccessFailed for squidtail",__FUNCTION__,__LINE__);
    $unix=new unix();

    if ($handle = opendir($LogRotateAccessFailed)){
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                $path="$LogRotateAccessFailed/$file";
                echo "OPEN $path\n";
                if(is_dir($path)){continue;}
                if(!preg_match("#^squidtail\.log#", $file)){continue;}
                if(!range_fichier_tail($path,$BackupMaxDaysDir)){
                    $xtime=$unix->file_time_min($path);
                    if($xtime>172800){@unlink($path);}
                }
            }
        }

    }
return true;
}


function range_fichier_tail($filepath,$BackupMaxDaysDir):bool{

    $LegalLogArticaClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaClient"));

    if($LegalLogArticaClient==1){
        @unlink($filepath);
        return true;
    }

	$syslog=new mysql_storelogs();
	$unix=new unix();
	$ext=$unix->file_extension($filepath);
	$basename=basename($filepath);
	$LogRotatePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	$LogRotateAccess="$LogRotatePath/access";
	$LogRotateAccessFailed="$LogRotatePath/failed";

	$syslog->events("[Legal Logs]: Analyze $filepath [$ext] ",__FUNCTION__,__LINE__);
	if($ext=="gz"){
		if(preg_match("#\.tar\.gz$#", $basename)){
			$syslog->events("[Legal Logs]: $filepath is a tarball!",__FUNCTION__,__LINE__);
			return false;
		}
	
		$syslog->events("[Legal Logs]: Extract $filepath",__FUNCTION__,__LINE__);
		$ExtractedFile="$LogRotateAccess/$basename.log";
		if(!$unix->uncompress($filepath,$ExtractedFile )){
			@unlink($ExtractedFile);
			$syslog->events("[Legal Logs]: Unable to extract $filepath to $ExtractedFile",__FUNCTION__,__LINE__);
			return false;
		}
		$syslog->events("Removing $filepath [$ext] ",__FUNCTION__,__LINE__);
		@unlink($filepath);
		$filepath=$ExtractedFile;
	}	
	
	
	$unix=new unix();
	$ztimes=access_tail_getdates($filepath);
	if(!$ztimes){
		$syslog->events("[Legal Logs]: Failed to parse $filepath",__FUNCTION__,__LINE__);
		@mkdir($LogRotateAccessFailed,0755,true);
		$FailedDestination="$LogRotateAccessFailed/$basename";
        if($FailedDestination<>$filepath) {
            if (@copy($filepath, "$LogRotateAccessFailed/$basename")) {
                @unlink($filepath);
            }
        }
		return false;
	}
	
	
	$xdatefrom=$ztimes[0];
	$NewFileName="access-tail.".filename_from_arraydates($ztimes);
	
	$FinalDirectory="$BackupMaxDaysDir/proxy/".date("Y",$xdatefrom)."/".date("m",$xdatefrom)."/".date("d",$xdatefrom);
	@mkdir($FinalDirectory,0755,true);
	
	if(!is_dir($FinalDirectory)){
		$syslog->events("[Legal Logs]: Unable to create $FinalDirectory directory permission denied",__FUNCTION__,__LINE__);
		return false;
	}
	
	if(!$unix->compress($filepath, "$FinalDirectory/$NewFileName")){
		@unlink("$FinalDirectory/$NewFileName");
		$syslog->events("Unable to compress $FinalDirectory/$NewFileName permission denied",__FUNCTION__,__LINE__);
		return false;
	}
	
	$syslog->events("[Legal Logs]: Success to create $FinalDirectory/$NewFileName",__FUNCTION__,__LINE__);
	$syslog->events("[Legal Logs]: Removing source file $filepath",__FUNCTION__,__LINE__);
	@unlink($filepath);
	return true;
}


function access_tail_getdates($file){
    $months=array("Jan"=>"01","Feb"=>"02" ,"Mar"=>"03","Apr"=>"04", "May"=>"05","Jun"=>"06", "Jul"=>"07", "Aug"=>"08", "Sep"=>"09", "Oct"=>"10","Nov"=>"11", "Dec"=>"12");
	$unix=new unix();

	$YEAROK["2012"]=true;
	$YEAROK["2013"]=true;
	$YEAROK["2014"]=true;
	$YEAROK["2015"]=true;
    $YEAROK["2016"]=true;
    $YEAROK["2017"]=true;
    $YEAROK["2018"]=true;
    $YEAROK["2019"]=true;
	$YEAROK[date("Y")]=true;


	$array=$unix->readlastline($file,8);
	if(!is_array($array)){return false;}
	$Ttime=0;
	foreach ($array as $filname=>$line){
        $line=trim($line);
        if($line==null){continue;}
	    if(strpos($line,":::")==0){

            if(preg_match("#\[([0-9]+)\/([a-zA-Z]+)\/([0-9]+):([0-9:]+).*?\]\s+#",$line,$re)){
                $Day=$re[1];
                $Month=$months[$re[2]];
                $Year=$re[3];
                $Hour=$re[4];
                $xtime=strtotime("$Year-$Month-$Day $Hour");
                $zdate=date("Y-m-d H:i:s",$xtime);
                $zDyear=date("Y",$xtime);
                if(!isset($YEAROK[$zDyear])){continue;}
                $time=strtotime($zdate);
                if($time>$Ttime){$Ttime=$time;}
                continue;
            }else{
                if($GLOBALS["VERBOSE"]){echo "<$line>\n";}
                if($GLOBALS["VERBOSE"]){echo "#\[([0-9]+)\/([a-zA-Z]+)\/([0-9]+):([0-9:]+).*?\]\s+# NO MATCH\n";}
            }
            continue;
	    }

		$re=explode(":::", $line);
        if(count($re)<4){continue;}
		$xtime=strtotime($re[4]);
		$zdate=date("Y-m-d H:i:s",$xtime);
		$zDyear=date("Y",$xtime);
		if(!isset($YEAROK[$zDyear])){continue;}
		$time=strtotime($zdate);
		if($time>$Ttime){$Ttime=$time;}
	}

	if($Ttime==0){return false;}
	echo "$file Last Time $Ttime: ".date("Y-m-d H:i:s",$Ttime)."\n";
	$LAST_TIME=$Ttime;

	$array=$unix->readFirstline($file,8);
	if(!is_array($array)){
	    if($GLOBALS["VERBOSE"]){echo "unix->readFirstline($file,8) not an array...\n";}
	    return false;
	}
	$MyTime=time();
	$Ttime=0;
    foreach ($array as $line){
        $line=trim($line);
        if($line==null){
            if($GLOBALS["VERBOSE"]){echo "NULL LINE <$line>\n";}
            continue;}

        if(strpos($line,":::")==0){
            if(preg_match("#\[([0-9]+)\/([a-zA-Z]+)\/([0-9]+):([0-9:]+).*?\]\s+#",$line,$re)){

                $Day=$re[1];
                $Month=$months[$re[2]];
                $Year=$re[3];
                $Hour=$re[4];


                $xtime=strtotime("$Year-$Month-$Day $Hour");
                if($GLOBALS["VERBOSE"]){echo "strtotime($Year-$Month-$Day $Hour) === $xtime\n";}
                $zdate=date("Y-m-d H:i:s",$xtime);
                $zDyear=date("Y",$xtime);
                if(!isset($YEAROK[$zDyear])){
                    if($GLOBALS["VERBOSE"]){echo "YEAROK[$zDyear]=False\n";}
                    continue;}
                $time=strtotime($zdate);
                if($time>$Ttime){$Ttime=$time;}
                continue;
            }else{
                if($GLOBALS["VERBOSE"]){echo "<$line>\n";}
                if($GLOBALS["VERBOSE"]){echo "#\[([0-9]+)\/([a-zA-Z]+)\/([0-9]+):([0-9:]+).*?\]\s+# NO MATCH\n";}

            }
            continue;
        }


		$re=explode(":::", $line);
		if(count($re)<4){continue;}
		$xtime=strtotime($re[4]);
		$zdate=date("Y-m-d H:i:s",$xtime);
		$zDyear=date("Y",$xtime);
		if(!isset($YEAROK[$zDyear])){continue;}
		$time=strtotime($zdate);
		if($time<$Ttime){$Ttime=$time;}
	}

	if($Ttime==0){return false;}


	if($Ttime==$MyTime){return false;}
	echo "$file First Time $Ttime: ".date("Y-m-d H:i:s",$Ttime)."\n";
	$FIRST_TIME=$Ttime;


	return array($FIRST_TIME,$LAST_TIME);
}


function range_fichier_source($filepath,$BackupMaxDaysDir):bool{
    $LegalLogArticaClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaClient"));

    if($LegalLogArticaClient==1){
        @unlink($filepath);
        return true;
    }

	$syslog=new mysql_storelogs();
	$unix=new unix();
	$ext=$unix->file_extension($filepath);
	$LogRotatePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	$LogRotateAccess="$LogRotatePath/access";
	$LogRotateAccessFailed="$LogRotatePath/failed";
	$LogRotateAccessMerged="$LogRotatePath/merged";
	$SquidRotateMergeFiles=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateMergeFiles");
	if(!is_numeric($SquidRotateMergeFiles)){$SquidRotateMergeFiles=0;}

	$basename=basename($filepath);
	if($basename=="access.merged.log"){return false;}
	
	$syslog->events("[Legal Logs]: Analyze $filepath [$ext] ",__FUNCTION__,__LINE__);
	if($ext=="gz"){
		if(preg_match("#\.tar\.gz$#", $basename)){
			$syslog->events("[Legal Logs]: $filepath is a tarball!",__FUNCTION__,__LINE__);
			return false;
		}
		
		$syslog->events("[Legal Logs]: Extract $filepath",__FUNCTION__,__LINE__);
		$ExtractedFile="$LogRotateAccess/$basename.log";
		if(!$unix->uncompress($filepath,$ExtractedFile )){
			@unlink($ExtractedFile);
			$syslog->events("[Legal Logs]: Unable to extract $filepath to $ExtractedFile",__FUNCTION__,__LINE__);
			return false;
		}
		$syslog->events("[Legal Logs]: Removing $filepath [$ext] ",__FUNCTION__,__LINE__);
		@unlink($filepath);
		$filepath=$ExtractedFile;
	}
	
	$unix=new unix();
	$ztimes=access_logs_getdates($filepath);
	if(!$ztimes){
		$syslog->events("[Legal Logs]: Failed to parse $filepath",__FUNCTION__,__LINE__);
		@mkdir($LogRotateAccessFailed,0755,true);
		if(@copy($filepath, "$LogRotateAccessFailed/$basename")){
			@unlink($filepath);
		}
		return false;
	}
	
	
	
	
	$xdatefrom=$ztimes[0];
	$NewFileName=filename_from_arraydates($ztimes);
	
	if($SquidRotateMergeFiles==1){
		@mkdir($LogRotateAccessMerged,0755,true);
		if(!is_dir($LogRotateAccessMerged)){
			$syslog->events("[Legal Logs]: Unable to create Merged directory $LogRotateAccessMerged",__FUNCTION__,__LINE__);
		}else{
			if(!@copy($filepath, "$LogRotateAccessMerged/$basename")){
				@unlink("$LogRotateAccessMerged/$basename");
				$syslog->events("[Legal Logs]: Unable to copy $filepath -> $LogRotateAccessMerged/$basename",__FUNCTION__,__LINE__);
			}
		}
		
	}
	
	
	$FinalDirectory="$BackupMaxDaysDir/proxy/".date("Y",$xdatefrom)."/".date("m",$xdatefrom)."/".date("d",$xdatefrom);
	@mkdir($FinalDirectory,0755,true);
		
	if(!is_dir($FinalDirectory)){
		$syslog->events("[Legal Logs]: Unable to create $FinalDirectory directory permission denied",__FUNCTION__,__LINE__);
		return false;
	}
	
	if(!$unix->compress($filepath, "$FinalDirectory/$NewFileName")){
		@unlink("$FinalDirectory/$NewFileName");
		$syslog->events("[Legal Logs]: Unable to compress $FinalDirectory/$NewFileName permission denied",__FUNCTION__,__LINE__);
		return false;
	}
	
	$syslog->events("[Legal Logs]: Success to create $FinalDirectory/$NewFileName",__FUNCTION__,__LINE__);
	$syslog->events("[Legal Logs]: Removing source file $filepath",__FUNCTION__,__LINE__);
	@unlink($filepath);
	return true;
}

function cache_logs_getdates($file){

	$unix=new unix();
	
	$Ttime=0;
	$array=$unix->readlastline($file,5);
	foreach ($array as $line){
		if(!preg_match("#^([0-9\/]+)\s+([0-9:]+)#", $line,$re)){continue;}
		
		$time=strtotime("{$re[1]} {$re[2]}");
		if($time>$Ttime){$Ttime=$time;}
	}
	if($Ttime==0){return false;}
	echo "$file Last Time $Ttime: ".date("Y-m-d H:i:s",$Ttime)."\n";
	$LAST_TIME=$Ttime;
	
	$array=$unix->readFirstline($file,5);
	if(!is_array($array)){return false;}
	$MyTime=time();
	$Ttime=$MyTime;
	foreach ($array as $line){
		if(!preg_match("#^([0-9\/]+)\s+([0-9:]+)#", $line,$re)){continue;}
		$time=strtotime("{$re[1]} {$re[2]}");
		if($time<$Ttime){$Ttime=$time;}
	}
	if($Ttime==$MyTime){return false;}
	echo "$file First Time $Ttime: ".date("Y-m-d H:i:s",$Ttime)."\n";
	$FIRST_TIME=$Ttime;
	
	
	return array($FIRST_TIME,$LAST_TIME);
	
}

function access_logs_getdates($file){
	$unix=new unix();
	
	$YEAROK["2012"]=true;
	$YEAROK["2013"]=true;
	$YEAROK["2014"]=true;
	$YEAROK["2015"]=true;
	$YEAROK["2016"]=true;
	$YEAROK["2017"]=true;
	$YEAROK[date("Y")]=true;
	
	
	$array=$unix->readlastline($file,8);
	if(!is_array($array)){return false;}
	$Ttime=0;
	foreach ($array as $line){
		if(!preg_match("#^([0-9\.]+)\s+([\-0-9]+)\s+([0-9\.]+)#", $line,$re)){continue;}
		$zdate=date("Y-m-d H:i:s",$re[1]);
		$zDyear=date("Y",$re[1]);
		if(!isset($YEAROK[$zDyear])){continue;}
		$time=strtotime($zdate);
		if($time>$Ttime){$Ttime=$time;}
	}
	
	if($Ttime==0){return false;}
	echo "$file Last Time $Ttime: ".date("Y-m-d H:i:s",$Ttime)."\n";
	$LAST_TIME=$Ttime;
	
	$array=$unix->readFirstline($file,8);
	if(!is_array($array)){return false;}
	$MyTime=time();
	$Ttime=$MyTime;
    foreach ($array as $line){
		if(!preg_match("#^([0-9\.]+)\s+([\-0-9]+)\s+([0-9\.]+)#", $line,$re)){continue;}
		$zdate=date("Y-m-d H:i:s",$re[1]);
		$zDyear=date("Y",$re[1]);
		if(!isset($YEAROK[$zDyear])){continue;}
		$time=strtotime($zdate);
		if($time<$Ttime){$Ttime=$time;}
	}
	
	if($Ttime==0){return false;}
	
	
	if($Ttime==$MyTime){return false;}
	echo "$file First Time $Ttime: ".date("Y-m-d H:i:s",$Ttime)."\n";
	$FIRST_TIME=$Ttime;
	
	
	return array($FIRST_TIME,$LAST_TIME);
}

function BackupToNas_tests():bool{
	$unix=new unix();
	$myHostname=$unix->hostname_g();
	$mount=new mount("/var/log/artica-postfix/logrotate.debug");
	$BackupSquidLogsNASIpaddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASIpaddr");
	$BackupSquidLogsNASFolder=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASFolder");
	$BackupSquidLogsNASUser=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASUser");
	$BackupSquidLogsNASPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASPassword");
	$BackupSquidLogsNASRetry=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASRetry");
	if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}	
	
	$GLOBALS["OUPUT_MOUNT_CLASS"]=true;
	build_progress("{APP_SQUID}::{use_remote_nas}", 10);
	
	echo "smb://$BackupSquidLogsNASIpaddr/$BackupSquidLogsNASFolder [$BackupSquidLogsNASUser]\n";
	
	if($BackupSquidLogsNASIpaddr==null){
		build_progress("{APP_SQUID}::{use_remote_nas} {disabled}", 110);
		echo "Backup via NAS is disabled, skip\n";
		return false;
	}
	
	
	build_progress("{APP_SQUID}::{use_remote_nas} TEST -1-", 20);
	$mountPoint="/mnt/BackupSquidLogsUseNas";
	if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
		echo "Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr\n";
		build_progress("{APP_SQUID}::{use_remote_nas} {failed}", 110);
		if($BackupSquidLogsNASRetry==0){return false;}
		sleep(3);
		build_progress("{APP_SQUID}::{use_remote_nas} TEST -2-", 30);
		$mount=new mount("/var/log/artica-postfix/logrotate.debug");
		if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
			echo "Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr\n";
			build_progress("{APP_SQUID}::{use_remote_nas} {failed}", 110);
			return false;
		}
	
	}	
	build_progress("{APP_SQUID}::{use_remote_nas}", 40);
	echo "Hostname=$myHostname $BackupSquidLogsNASIpaddr/$BackupSquidLogsNASFolder\n";
	$BackupMaxDaysDir="$mountPoint/artica-backup-syslog";
	@mkdir("$BackupMaxDaysDir",0755,true);
	
	if(!is_dir($BackupMaxDaysDir)){
		echo "Fatal $BackupMaxDaysDir permission denied\n";
		build_progress("{APP_SQUID}::{use_remote_nas} {failed}", 110);
		$mount->umount($mountPoint);
		return false;
	}	
	build_progress("{APP_SQUID}::{use_remote_nas}", 50);
	
	$t=time();
	@file_put_contents("$BackupMaxDaysDir/$t", time());
	if(!is_file("$BackupMaxDaysDir/$t")){
		echo "Fatal $BackupMaxDaysDir permission denied ($BackupMaxDaysDir/$t) test failed\n";
		$mount->umount($mountPoint);
		build_progress("{APP_SQUID}::{use_remote_nas} {failed}", 110);
		return false;
	}
	build_progress("{APP_SQUID}::{use_remote_nas} {success}", 95);
	build_progress("{APP_SQUID}::{use_remote_nas} {success}", 96);
	build_progress("{APP_SQUID}::{use_remote_nas} {success}", 97);
	build_progress("{APP_SQUID}::{use_remote_nas} {success}", 98);
	build_progress("{APP_SQUID}::{use_remote_nas} {success}", 99);
	build_progress("{APP_SQUID}::{use_remote_nas} {success}", 100);
	@unlink("$BackupMaxDaysDir/$t");
	$mount->umount($mountPoint);
	sleep(5);
	return true;
	
	
}
function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "{$pourc}% $text\n";
	@file_put_contents(PROGRESS_DIR."/squid.nas.storage.progress", serialize($array));
	@chmod(PROGRESS_DIR."/squid.nas.storage.progress",0755);
	sleep(1);
}

function BackupToNasMount(){
    if(is_file("/etc/artica-postfix/BackupSquidLogsDest")){@unlink("/etc/artica-postfix/BackupSquidLogsDest");}
    $mountPoint="/mnt/BackupSquidLogsUseNas";
    $BackupSquidLogsNASIpaddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASIpaddr");
    $BackupSquidLogsNASFolder=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASFolder");
    $BackupSquidLogsNASUser=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASUser");
    $BackupSquidLogsNASPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASPassword");
    $BackupSquidLogsNASRetry=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASRetry");
    $BackupSquidLogsNASFolder2=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASFolder2");
    if($BackupSquidLogsNASFolder2==null){$BackupSquidLogsNASFolder2="artica-backup-syslog";}

    if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}
    $mount=new mount("/var/log/artica-postfix/logrotate.debug");
    $syslog=new mysql_storelogs();

    if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
        $syslog->events("[Legal Logs]: Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr",__FUNCTION__,__LINE__);

        if($BackupSquidLogsNASRetry==0){return false;}
        sleep(3);
        $mount=new mount("/var/log/artica-postfix/logrotate.debug");
        if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
            $syslog->events("[Legal Logs]: Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr",__FUNCTION__,__LINE__);
            return false;
        }

    }

    if($BackupSquidLogsNASFolder2<>null){
        $BackupMaxDaysDir="$mountPoint/$BackupSquidLogsNASFolder2";
    }else{
        $BackupMaxDaysDir=$mountPoint;
    }
    @file_put_contents("/etc/artica-postfix/BackupSquidLogsDest",$BackupMaxDaysDir);
    return true;
}

function BackupToNas($directory,$AnalyzeDestination=true):bool{
		if(!is_dir($directory)){return false;}
		$syslog=new mysql_storelogs();
		$unix=new unix();
		$myHostname=$unix->hostname_g();
		$DirSuffix=basename($directory);
		$mount=new mount("/var/log/artica-postfix/logrotate.debug");
		$BackupSquidLogsNASIpaddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASIpaddr");
		$BackupSquidLogsNASFolder=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASFolder");
		$BackupSquidLogsNASUser=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASUser");
		$BackupSquidLogsNASPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASPassword");
		$BackupSquidLogsNASRetry=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASRetry");
		$BackupSquidLogsNASFolder2=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASFolder2");
		if(!is_numeric($BackupSquidLogsNASRetry)){$BackupSquidLogsNASRetry=0;}
        $SquidRotateEnableCrypt=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateEnableCrypt"));

        if($SquidRotateEnableCrypt==1){
            shell_exec("/usr/share/artica-postfix/bin/artica-rotate -crypt-logs");
        }
		
		if($BackupSquidLogsNASFolder2==null){$BackupSquidLogsNASFolder2="artica-backup-syslog";}
		if($BackupSquidLogsNASIpaddr==null){
            $syslog->events("[Legal Logs]: Backup via NAS is disabled, skip",__FUNCTION__,__LINE__);
			return false;
		}

		$mountPoint="/mnt/BackupSquidLogsUseNas";
		if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
			$syslog->events("[Legal Logs]: Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr",__FUNCTION__,__LINE__);
				
			if($BackupSquidLogsNASRetry==0){return false;}
			sleep(3);
			$mount=new mount("/var/log/artica-postfix/logrotate.debug");
			if(!$mount->smb_mount($mountPoint,$BackupSquidLogsNASIpaddr,$BackupSquidLogsNASUser,$BackupSquidLogsNASPassword,$BackupSquidLogsNASFolder)){
				$syslog->events("[Legal Logs]: Unable to connect to NAS storage system (1): $BackupSquidLogsNASUser@$BackupSquidLogsNASIpaddr",__FUNCTION__,__LINE__);
				return false;
			}

		}

		
		$syslog->events("[Legal Logs]: Hostname=$myHostname Suffix = $DirSuffix $BackupSquidLogsNASIpaddr/$BackupSquidLogsNASFolder",__FUNCTION__,__LINE__);
		
		if($BackupSquidLogsNASFolder2<>null){
			$BackupMaxDaysDir="$mountPoint/$BackupSquidLogsNASFolder2";
		}else{
			$BackupMaxDaysDir=$mountPoint;
		}
		
		
		@mkdir("$BackupMaxDaysDir",0755,true);

		if(!is_dir($BackupMaxDaysDir)){
			$syslog->events("[Legal Logs]: Fatal $BackupMaxDaysDir permission denied",__FUNCTION__,__LINE__);
			if($GLOBALS["VERBOSE"]){echo "Fatal $BackupMaxDaysDir permission denied\n";}
			squid_admin_mysql(0,"[Legal Logs]: FATAL $BackupMaxDaysDir permission denied",null,__FILE__,__LINE__);
			$mount->umount($mountPoint);
			return false;
		}


		$t=time();
		@file_put_contents("$BackupMaxDaysDir/$t", time());
		if(!is_file("$BackupMaxDaysDir/$t")){
			$syslog->events("[Legal Logs]: Fatal $BackupMaxDaysDir permission denied ($BackupMaxDaysDir/$t) test failed",__FUNCTION__,__LINE__);
			squid_admin_mysql(0,"[Legal Logs]: FATAL $BackupMaxDaysDir permission denied",null,__FILE__,__LINE__);
			$mount->umount($mountPoint);
			return false;
		}

		
		@unlink("$BackupMaxDaysDir/$t");
		moveAllFiles("$directory",$BackupMaxDaysDir);
		
		
		if($AnalyzeDestination){
			analyze_destination_directory($BackupMaxDaysDir."/proxy");
		}
		$mount->umount($mountPoint);
		return true;
}

function moveAllFiles($directory_from,$directoryTo):bool{
	$unix=new unix();
	$find=$unix->find_program("find");
	if($GLOBALS["VERBOSE"]){echo "$find \"$directory_from\" 2>&1\n";}
	exec("$find \"$directory_from/\" 2>&1",$results);
	foreach ($results as $filepath){
		if(is_dir($filepath)){continue;}
		$filename=basename($filepath);
		$dirname=dirname($filepath);
		$dirname=str_replace($directory_from, "", $dirname);
		$nextDir="$directoryTo/$dirname";
		$nextDir=str_replace("//", "/", $nextDir);
		if($GLOBALS["VERBOSE"]){echo "moveAllFiles: $filepath -> $nextDir\n";}
		if(!is_dir($nextDir)){@mkdir($nextDir,0755,true);}
		if(!is_dir($nextDir)){
			squid_admin_mysql(0,"SYSLOG: FATAL $nextDir permission denied",null,__FILE__,__LINE__);
			return false;
		}
		$NextFile="$nextDir/$filename";
		if(preg_match("#snapshot\.db$#", $filename)){@unlink($NextFile);}
		$NextFile=str_replace("//", "/", $NextFile);
		
		$md5FileSource=md5_file($filepath);
		if(is_file($NextFile)){
			$md5FileDest=md5_file($NextFile);
			if($md5FileDest==$md5FileSource){
				if($GLOBALS["VERBOSE"]){echo "moveAllFiles: $filepath -> Already copied remove source\n";}
				@unlink($filepath);
				continue;
			}else{
				squid_admin_mysql(0,"[Legal Logs]: FATAL $filename cannot be copied (same file exists but integrity differ)",null,__FILE__,__LINE__);
				continue;
			}
		}
		
		
		
		@copy($filepath,$NextFile);
		if(!is_file($NextFile)){
			squid_admin_mysql(0,"[Legal Logs]: FATAL $filename permission denied or disk full (task aborted)",null,__FILE__,__LINE__);
			return false;
		}
		$md5FileDest=md5_file($NextFile);
		if($md5FileDest<>$md5FileSource){
			squid_admin_mysql(0,"[Legal Logs]: FATAL $filename corrupted, aborting (task aborted)",null,__FILE__,__LINE__);
			return false;
		}
		if($GLOBALS["VERBOSE"]){ echo "moveAllFiles: $filepath -> $NextFile Success\n";}
		@unlink($filepath);
	}

	return true;
}