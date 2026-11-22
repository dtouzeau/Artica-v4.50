<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);


// La sauvegarde du log sink et la purge des vieux journaux sont portees en Go
// (articarest logsink/backup.go, route /syslog/logsink/backup). Seul le
// deplacement du repertoire reste ici, appele par logsink.IsMovingDir().
if(isset($argv[1])){
    if($argv[1]=="--move-directory"){move_directory();exit;}
}
exit();


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



function _out($text):bool{
    echo date("H:i:s")." [INIT]: $text\n";
    if(!function_exists("openlog")){return false;}
    openlog("rsyslogd", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, "[logsink_backup]: $text");
    closelog();
    return true;
}
