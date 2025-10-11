<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);


if(isset($argv[1])){
    if($argv[1]=="--clean"){CheckOldDays();exit;}
    if($argv[1]=="--move-directory"){move_directory();exit;}
}


function move_directory():bool{
    $unix=new unix();
    $LogSyncMoveDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSyncMoveDir"));
    if($LogSyncMoveDir==null){
        echo "No Directory to move...\n";
        return true;
    }
    $rsync=$unix->find_program("rsync");

    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";

    $unix=new unix();
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
        _out("Already PID $pid exists, aborting");
        return false;
    }
    @file_put_contents($pidfile,getmypid());
    $pid=$unix->PIDOF_PATTERN("rsync.*?remove-source-files");
    if($unix->process_exists($pid)){
        echo "An another instance already running...\n";
        return true;
    }
    $LogSinkWorkDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkWorkDir");
    if($LogSinkWorkDir==null){$LogSinkWorkDir="/home/syslog/logs_sink";}

    if(!is_dir($LogSinkWorkDir)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LogSinkWorkDir",$LogSyncMoveDir);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LogSyncMoveDir","");
        shell_exec("/etc/init.d/rsyslog restart");
        return true;
    }


    if(!is_dir($LogSyncMoveDir)){@mkdir($LogSyncMoveDir,0755,true);}
    $TEMP_DIR=$unix->TEMP_DIR();
    $find=$unix->find_program("find");
    $cmds[]="$rsync";
    $cmds[]="-avzh";
    $cmds[]="--temp-dir=$TEMP_DIR";
    $cmds[]="--remove-source-files";
    $cmds[]="$LogSinkWorkDir/* $LogSyncMoveDir/";
    $cmds[]="2>&1";
    $cmdline=@implode(" ",$cmds);
    echo $cmdline."\n";
    shell_exec("/etc/init.d/rsyslog stop");

    exec($cmdline,$results);
    if($unix->check_rsync_error($results)){
        return false;
    }

    $cmdline="$find $LogSinkWorkDir -type d -empty -delete";
    echo $cmdline."\n";
    shell_exec($cmdline);
    if(!is_dir($LogSinkWorkDir)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LogSinkWorkDir",$LogSyncMoveDir);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LogSyncMoveDir","");
    }
    shell_exec("/etc/init.d/rsyslog restart");
    return true;
}



startx();


function startx():bool{
    $LogSinkWorkDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkWorkDir");
    if($LogSinkWorkDir==null){$LogSinkWorkDir="/home/syslog/logs_sink";}
    $SRCDIR=$LogSinkWorkDir;
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
    $timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
    $unix=new unix();
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
        _out("Already PID $pid exists, aborting" , "MAIN", __FILE__, __LINE__);
        exit();
    }

    if(!$GLOBALS["FORCE"]){
        $timelast=$unix->file_time_min($timefile);
        if($timelast<5){
            _out("Too short time to execute, need at least 5minutes ( current:$timelast minutes)");
            return false;
        }
    }
    if(is_file($timefile)){@unlink($timefile);}
    @file_put_contents($timefile,time());

    _out("Executing backup Log sink data task to a remote resource..");
    $EnableSyslogLogSink=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSyslogLogSink"));
    $LogSynBackupEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynBackupEnable"));
    if($EnableSyslogLogSink==0){$LogSynBackupEnable=0;}
    if($LogSynBackupEnable==0){
        _out("Backup is disabled, aborting...");
        return false;
    }
    $LogSynBackupResource=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynBackupResource"));

    echo "Using the resource \"$LogSynBackupResource\"\n";
    $MAINDIR="/automounts/$LogSynBackupResource";
    echo "Testing the resource...[$MAINDIR]\n";
    if(!is_dir($MAINDIR)){
        _out($MAINDIR." no such directory");
        squid_admin_mysql(0,"{logs_sink} Unable to access to mount point $LogSynBackupResource",null,__FILE__,__LINE__);
        return false;
    }
    $hostname=php_uname('n');
    $MAINDIR="$MAINDIR/$hostname";
    echo "Testing the resource...[$MAINDIR]\n";
    if(!is_dir($MAINDIR)){
        if(!is_mounted($LogSynBackupResource)){
            _out("$LogSynBackupResource resource is not mounted");
            return false;
        }
        @mkdir($MAINDIR,0755,true);
    }
    $MAINDIR="$MAINDIR/log_sink";
    echo "Testing the resource...[$MAINDIR]\n";
    if(!is_dir($MAINDIR)){
        if(!is_mounted($LogSynBackupResource)){
            _out("$LogSynBackupResource resource is not mounted");
            squid_admin_mysql(0,"{logs_sink} resource is not mounted",null,__FILE__,__LINE__);
            return false;
        }
        @mkdir($MAINDIR,0755,true);
    }

    echo "Testing the resource in write mode...[$MAINDIR]\n";
    $fname="$MAINDIR/".time();
    @touch($fname);
    if(!is_file($fname)){
        if(!is_mounted($LogSynBackupResource)){
            _out("$LogSynBackupResource resource is not mounted");
            squid_admin_mysql(0,"{logs_sink} resource is not mounted",null,__FILE__,__LINE__);
            return false;
        }

        squid_admin_mysql(0,"{logs_sink} $MAINDIR permission denied",null,__FILE__,__LINE__);
        return false;
    }

    $rsync=$unix->find_program("rsync");
    if(!is_file($rsync)){
        squid_admin_mysql(0,"{logs_sink} $MAINDIR missing rsync binary",null,__FILE__,__LINE__);
        _out("rsync no such binray");
        return false;
    }

    $TEMP_DIR=$unix->TEMP_DIR();

    $cmd[]=$rsync;
    $cmd[]="-rtD";
    $cmd[]="--temp-dir=$TEMP_DIR";
    $cmd[]="--no-p --no-g --no-o";
    $cmd[]="$SRCDIR/* $MAINDIR/";
    $cmd[]="--stats -v";
    $cmd[]="2>&1";

    $cmdline=@implode(" ",$cmd);
    exec($cmdline,$results);

    if($unix->check_rsync_error($results)){
        return false;
    }

    $report=check_rsync_report($results);
    squid_admin_mysql(2,"{success} {backuping} {logs_sink}",$report,__FILE__,__LINE__);
    if(!CheckOldDays()){
        squid_admin_mysql(0,"{logs_sink} There was an issue while cleaning old logs",__FILE__,__LINE__);
    }
    return true;
}

function CheckOldDays():bool{
    $LogSinkWorkDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkWorkDir");
    if($LogSinkWorkDir==null){$LogSinkWorkDir="/home/syslog/logs_sink";}
    $unix=new unix();
    $find=$unix->find_program("find");
    exec("$find $LogSinkWorkDir -type f 2>&1",$results);
    $filesArray=array();
    foreach ($results as $fullpath){
        $filename=basename($fullpath);
        if(!preg_match("#^([0-9-]+)_.*?\.gz$#",$filename,$re)){continue;}
        $timestamp=strtotime($re[1]." 00:00:00");
        $filesArray[$fullpath]=$timestamp;
    }
    
    if(count($filesArray)==0){return true;}
    $dbtemp="/home/artica/SQLITE/LogSink_temp.db";
    if(is_file($dbtemp)){@unlink($dbtemp);}
    $q=new lib_sqlite("/home/artica/SQLITE/LogSink_temp.db");

    $q->QUERY_SQL("CREATE TABLE sdays (`ID` INTEGER PRIMARY KEY AUTOINCREMENT, fullpath TEXT NOT NULL UNIQUE, `sdate` INTEGER NOT NULL DEFAULT 0)");

    foreach ($filesArray as $fullpath=>$timestamp){
        $q->QUERY_SQL("INSERT INTO sdays(fullpath,sdate) VALUES ('$fullpath','$timestamp')");
        if(!$q->ok){
            _out("SQL Error $q->mysql_error");
            if(is_file($dbtemp)){@unlink($dbtemp);}
            return false;
        }
    }

    $LogSynBackupRetention=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynBackupRetention"));
    if($LogSynBackupRetention==0){$LogSynBackupRetention=7;}
    $remove_before= strtotime("-$LogSynBackupRetention days");
    echo "Remove files before ".date("Y-m-d H:i:s",$remove_before). "($remove_before)\n";

    $results=$q->QUERY_SQL("SELECT fullpath FROM sdays WHERE sdate < $remove_before");
    if(!$q->ok){
        _out("SQL Error $q->mysql_error");
        if(is_file($dbtemp)){@unlink($dbtemp);}
        return false;
    }
    $logstext=array();
    $cleanedsize=0;
    $cleanedfiles=0;
    foreach ($results as $index=>$ligne){
        $fullpath=$ligne["fullpath"];
        echo "Remove[$index]: $fullpath\n";
        if(!is_file($fullpath)){continue;}
        $size=filesize($fullpath);
        $cleanedfiles++;
        $cleanedsize=$cleanedsize+$size;
        $size_text=$unix->FormatBytes($size/1024);
        $logstext[]=basename($fullpath)."( $size_text )";
        @unlink($fullpath);
        _out("Cleaning, removing old Log ".basename($fullpath)."( $size_text )");

    }
    if(is_file($dbtemp)){@unlink($dbtemp);}
    if($cleanedfiles==0){return true;}

    $size_text=$unix->FormatBytes($cleanedsize/1024);
    squid_admin_mysql(1,"{logs_sink} {cleaned} $cleanedfiles {files} ( $size_text ) ",
        @implode("\n",$logstext),__FILE__,__LINE__);

    return true;
}


function check_rsync_report($results):string{
    if(!is_array($results)){return "No report ?";}
    $unix=new unix();
    foreach ($results as $line){

        if(preg_match("#Number of regular files transferred#",$line)){
            _out($line);
            $f[]=$line;
            continue;
        }
        if(preg_match("#Total transferred file size:\s+(.+?)\s+#",$line,$re)){
            $sum=str_replace(",","",$re[1]);
            $sum=str_replace(".","",$sum);
            $Total=$unix->FormatBytes(intval($sum)/1024);
            $f[]="Total transferred file size: $Total";
            _out("Total transferred file size: $Total");
        }
    }

    return @implode("\n",$f);
}


function is_mounted($resource):bool{
    $f=explode("\n",@file_get_contents("/proc/mounts"));
    foreach ($f as $line){
        if(!preg_match("#\/automounts/$resource\s+#",$line)){continue;}
        return true;
    }
    return false;
}


function _out($text):bool{
    echo date("H:i:s")." [INIT]: $text\n";
    if(!function_exists("openlog")){return false;}
    openlog("rsyslogd", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, "[logsink_backup]: $text");
    closelog();
    return true;
}
