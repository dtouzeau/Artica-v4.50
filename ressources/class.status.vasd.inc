<?php

function VASD_PID():int{
    $pidfile="/var/opt/quest/vas/vasd/.vasd.pid";
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    $pid=$GLOBALS["CLASS_UNIX"]->PIDOF("/opt/quest/sbin/.vasd");
    return intval($pid);
}
function VASD_STATUS():string{
    $initd          = "/etc/init.d/vasd";
    $statusfile     = PROGRESS_DIR."/vasd.status";
    $CODE_NAME      = "APP_VASD";
    $CODE_NAME_TEXT = "{".$CODE_NAME."}";


    if(!is_file($initd)){
        @file_put_contents($statusfile,"");
        return "";
    }

    $l[]="[$CODE_NAME]";
    $l[]="service_name=$CODE_NAME";
    $l[]="service_cmd=$initd";
    $l[]="family=proxy";
    $l[]="watchdog_features=1";
    $l[]="service_disabled=1";


    $master_pid=VASD_PID();
    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){squid_admin_mysql(0, "{warning} $CODE_NAME_TEXT {service_stopped} [{action}={start}]", null,__FILE__,__LINE__);}
            $GLOBALS["CLASS_SOCKETS"]->go_exec("/etc/init.d/vasd start");
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



