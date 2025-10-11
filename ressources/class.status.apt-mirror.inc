<?php

function APT_MIRROR_PID():int{
    $pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("perl /bin/apt-mirror");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    return 0;
}

function APT_MIRROR_WEB_PID(){
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/debian-mirror-http.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
        return $pid;
    }
    return $GLOBALS["CLASS_UNIX"]->PIDOF("/usr/sbin/apt-mirror");

}

function APT_MIRROR_WEB():string{
    $Enable         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAptMirror"));
    $rphp           = "{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]}";
    $root           =  $GLOBALS["BASE_ROOT"];
    $initd          = "/etc/init.d/apt-mirror";
    $statusfile     = PROGRESS_DIR."/apt-mirror-web.status";
    $CODE_NAME      = "APP_APT_MIRROR_WEB";
    $CODE_NAME_TEXT = "{".$CODE_NAME."}";
    $APP_INSTALLED  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APT_MIRROR_INSTALLED"));
    if($APP_INSTALLED==0){$Enable=0;}

    $log[]="Application Installed: $APP_INSTALLED";
    $log[]="Application Enabled..: $Enable";
    $log[]="init.d...............: $initd";

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
        //shell_exec("$rphp $root/$scrfile --uninstall 2>&1 &");
        @file_put_contents($statusfile,@implode("\n",$l));
        return implode("\n",$l);
    }
    $master_pid=APT_MIRROR_WEB_PID();
    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        $l[]="master_pid=$master_pid";
        $l[]="running=0";
        $l[]="installed=1";
        $l[]="";
        @file_put_contents($statusfile,@implode("\n",$l));
        return implode("\n",$l);
    }

    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid,"APP_APT_MIRROR");
    $l[]="";
    @file_put_contents($statusfile,@implode("\n",$l));
    return implode("\n",$l);
}


function APT_MIRROR():string{
    $Enable         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAptMirror"));
    $rphp           = "{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]}";
    $root           =  $GLOBALS["BASE_ROOT"];
    $initd          = "/etc/init.d/apt-mirror";
    $statusfile     = PROGRESS_DIR."/apt-mirror.status";
    $CODE_NAME      = "APP_APT_MIRROR";
    $CODE_NAME_TEXT = "{".$CODE_NAME."}";
    $APP_INSTALLED  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APT_MIRROR_INSTALLED"));
    if($APP_INSTALLED==0){$Enable=0;}

    $log[]="Application Installed: $APP_INSTALLED";
    $log[]="Application Enabled..: $Enable";
    $log[]="init.d...............: $initd";
/*
    if(!is_file($initd)){
        if($Enable==1){
            squid_admin_mysql(0, "$CODE_NAME_TEXT seems enabled but install corrupted [action=install]","$initd missing",__FILE__,__LINE__);
            shell_exec("$rphp $root/$scrfile --install 2>&1 &");
            @file_put_contents($statusfile,"");
            return "";
        }
        @file_put_contents($statusfile,"");
        return "";
    }
*/

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
        //shell_exec("$rphp $root/$scrfile --uninstall 2>&1 &");
        @file_put_contents($statusfile,@implode("\n",$l));
        return implode("\n",$l);
    }
    $master_pid=APT_MIRROR_PID();
    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        $l[]="master_pid=$master_pid";
        $l[]="running=0";
        $l[]="installed=1";
        $l[]="";
        @file_put_contents($statusfile,@implode("\n",$l));
        return implode("\n",$l);

    }

    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid);
    $l[]="";
    @file_put_contents($statusfile,@implode("\n",$l));
    return implode("\n",$l);
}



