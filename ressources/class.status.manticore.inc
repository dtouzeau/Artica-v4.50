<?php

function MANTICORE_PID():int{
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/manticore/searchd.pid");
    $GLOBALS["MANTICORE_LOGS"][]="/var/run/manticore/searchd.pid = $pid";
    $GLOBALS["MANTICORE_LOGS"][]=@implode("\n",$GLOBALS["get_pid_from_file"]);
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    $searchd="/usr/bin/searchd";

    $pid=$GLOBALS["CLASS_UNIX"]->PIDOF($searchd);
    $GLOBALS["MANTICORE_LOGS"][]="$searchd = $pid";
    return $pid;
}
function MANTICORE_STATUS():string{
    $GLOBALS["MANTICORE_LOGS"]=array();
    $initd          = "/etc/init.d/manticore-search";
    $statusfile     = PROGRESS_DIR."/manticore.status";
    $TOKEN_ENABLED  = "MantiCoreSearchEnabled";
    $CODE_NAME      = "APP_MANTICORE";
    $CODE_NAME_TEXT = "{".$CODE_NAME."}";
    $PHPSCRIPT      = "exec.manticore.php";

    $ENABLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($TOKEN_ENABLED));

    $l[]="[$CODE_NAME]";
    $l[]="service_name=$CODE_NAME";
    $l[]="service_cmd=$initd";
    $l[]="family=proxy";
    $l[]="watchdog_features=1";
    $l[]="service_disabled=$ENABLED";

    if($ENABLED==0){
        if(is_file($initd)){
            squid_admin_mysql(0, "{uninstalling} $CODE_NAME_TEXT", null,__FILE__,__LINE__);
            $GLOBALS["CLASS_UNIX"]->framework_exec("$PHPSCRIPT --uninstall");
            @file_put_contents($statusfile,@implode("\n",$l));
            return @implode("\n",$l);
        }
        return @implode("\n",$l);
    }
    if($ENABLED==1){
        if(!is_file($initd)){
            squid_admin_mysql(0, "{installing} $CODE_NAME_TEXT", null,__FILE__,__LINE__);
            $GLOBALS["CLASS_UNIX"]->framework_exec("$PHPSCRIPT --install");
            @file_put_contents($statusfile,@implode("\n",$l));
            return @implode("\n",$l);
        }
    }



    $master_pid=MANTICORE_PID();
    $GLOBALS["MANTICORE_LOGS"][]="Returned: $master_pid";
    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
                squid_admin_mysql(0, "{warning} $CODE_NAME_TEXT {service_stopped} [{action}={start}]", @implode("\n",$GLOBALS["MANTICORE_LOGS"]),__FILE__,__LINE__);
            }
            $GLOBALS["CLASS_UNIX"]->framework_exec("$PHPSCRIPT --start");
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



