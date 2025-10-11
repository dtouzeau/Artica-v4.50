<?php

function URBACKUP_PID():int{
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/urbackupsrv.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    return intval($GLOBALS["CLASS_UNIX"]->PIDOF("/usr/bin/urbackupsrv"));
}

function APP_URBACKUP():string{
    $Enable         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableURBackup"));
    $initd          = "/etc/init.d/urbackupsrv";
    $statusfile     = PROGRESS_DIR."/urbackupsrv.status";
    $CODE_NAME      = "APP_URBACKUP";
    $CODE_NAME_TEXT = "{".$CODE_NAME."}";
    $scrfile        = "exec.urbackup-server.php";
    $APP_INSTALLED  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_URBACKUP_INSTALLED"));
    if($APP_INSTALLED==0){$Enable=0;}

    $log[]="Application Installed: $APP_INSTALLED";
    $log[]="Application Enabled..: $Enable";
    $log[]="init.d...............: $initd";

    if(!is_file($initd)){
        if($Enable==1){
            squid_admin_mysql(0, "$CODE_NAME_TEXT seems enabled but install corrupted [action=install]","$initd missing",__FILE__,__LINE__);
            $GLOBALS["CLASS_UNIX"]->framework_exec("$scrfile --install");
            @file_put_contents($statusfile,"");
            return "";
        }
        @file_put_contents($statusfile,"");
        return "";
    }


    $l[]="[$CODE_NAME]";
    $l[]="service_name=$CODE_NAME";
    $l[]="service_cmd=$initd";
    $l[]="family=proxy";
    $l[]="watchdog_features=1";
    $l[]="service_disabled=$Enable";


    if($Enable==0){
        $l[]="running=0\ninstalled=0";
        $l[]="";
        squid_admin_mysql(0, "$CODE_NAME_TEXT seems not enabled but install exists [action=uninstall]",
            @implode("\n",$log),__FILE__,__LINE__);
        $GLOBALS["CLASS_UNIX"]->framework_exec("$scrfile --uninstall");
        @file_put_contents($statusfile,@implode("\n",$l));
        return implode("\n",$l);
    }
    $master_pid=URBACKUP_PID();
    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){squid_admin_mysql(0, "{warning} $CODE_NAME_TEXT {service_stopped} [{action}={start}]", null,__FILE__,__LINE__);}
            $GLOBALS["CLASS_UNIX"]->framework_exec("$scrfile --start");

        }

        $l[]="master_pid=$master_pid";
        $l[]="running=0";
        $l[]="installed=1";
        $l[]="";
        @file_put_contents($statusfile,@implode("\n",$l));
        return implode("\n",$l);

    }

    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid,$CODE_NAME);
    $l[]="";
    @file_put_contents($statusfile,@implode("\n",$l));
    return implode("\n",$l);
}



