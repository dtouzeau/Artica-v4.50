<?php
function APP_ARTICAFSMON_PID_PATH():string{
    return "/var/run/artica-monitorfs.pid";
}
function APP_ARTICAFSMON_INIT_PATH():string{
    return "/etc/init.d/artica-fsmonitor";
}
function APP_ARTICAFSMON_BIN_PATH():string{
    return "/usr/sbin/artica-fsmonitor";
}

function APP_ARTICAFSMON_PID_NUM(){

    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file(APP_ARTICAFSMON_PID_PATH());
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
        return $pid;
    }

    $pid=$GLOBALS["CLASS_UNIX"]->PIDOF(APP_ARTICAFSMON_BIN_PATH());
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
        return $pid;
    }
    return 0;
}

function APP_ARTICAFSMON():string{
    $PHP_SCR="exec.fsmonitor.php";
    $status_file=PROGRESS_DIR."/APP_ARTICAFSMON.status";
    $servicepath=APP_ARTICAFSMON_INIT_PATH();
    $l[]="[APP_ARTICAFSMON]";
    $l[]="service_name=APP_ARTICAFSMON";
    $l[]="service_cmd=$servicepath";
    $l[]="family=proxy";
    $l[]="watchdog_features=1";
    $productname="File Monitoring service";

    $ServiceEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFSMon"));

    if($ServiceEnabled==1){
        if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: ServiceEnabled report [YES]\n";}
        if(!is_file($servicepath)){
            $GLOBALS["CLASS_UNIX"]->framework_exec("$PHP_SCR --install");
            squid_admin_mysql(0, "$productname seems enabled but install corrupted [{action}={install}]","$servicepath missing\nExecute: $PHP_SCR --install",__FILE__,__LINE__);
            @file_put_contents($status_file,@implode("\n",$l));
            @chown($status_file,"www-data");
            return implode("\n",$l);
        }
    }

    if($ServiceEnabled==0){
        if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: ServiceEnabled report  [NO]\n";}
        if(is_file($servicepath)){
            $GLOBALS["CLASS_UNIX"]->framework_exec("$PHP_SCR --uninstall");
            squid_admin_mysql(0, "$productname seems disabled but installed [{action}={uninstall}]","$servicepath exists\nExecute $PHP_SCR --uninstall",__FILE__,__LINE__);
            @file_put_contents($status_file,@implode("\n",$l));
            @chown($status_file,"www-data");
            return implode("\n",$l);
        }
        @file_put_contents($status_file,@implode("\n",$l));
        @chown($status_file,"www-data");
        return implode("\n",$l);
    }

    $master_version="0.0.0";
    $l[]="master_version=$master_version";
    $l[]="service_disabled=$ServiceEnabled";
    $master_pid=APP_ARTICAFSMON_PID_NUM();

    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
                squid_admin_mysql(0, "{warning} $productname is {stopped} [{action}={start}]",
                    null,__FILE__,__LINE__);
            }
            $GLOBALS["CLASS_UNIX"]->framework_exec("$PHP_SCR --start");
        }

        $l[]="master_pid=$master_pid";
        $l[]="running=0";
        $l[]="installed=1";
        $l[]="";
        @file_put_contents($status_file,@implode("\n",$l));
        @chown($status_file,"www-data");
        return implode("\n",$l);
    }

    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid);
    $l[]="";
    @file_put_contents($status_file,@implode("\n",$l));
    @chown($status_file,"www-data");
    return implode("\n",$l);


}