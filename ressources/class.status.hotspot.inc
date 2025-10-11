<?php
function HOTSPOT_WEB_PID():int{
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/hotspot-web.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    return intval($GLOBALS["CLASS_UNIX"]->PIDOF("/usr/sbin/hotspot-web"));
}
function HOTSPOT_STATUS():string{
    $servicepath="/etc/init.d/artica-hotspot";
    $l[]="[APP_HOTSPOT]";
    $l[]="service_name=APP_HOTSPOT";
    $l[]="service_cmd=";
    $l[]="family=proxy";
    $l[]="watchdog_features=1";
    $productname="HotSpot Web service";

    $enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
    if($GLOBALS["VERBOSE"]){echo "WEB_ERROR_PAGE:: UfdbUseInternalService == $enabled\n";}

    if($enabled==1){
        if($GLOBALS["VERBOSE"]){echo "APP_HOTSPOT:: EnableSquidMicroHotSpot report [YES]\n";}
        if(!is_file($servicepath)){
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.hotspot-service.php --install");
            squid_admin_mysql(0, "$productname seems enabled but install corrupted [action=install]","$servicepath missing",__FILE__,__LINE__);
            @file_put_contents(PROGRESS_DIR."/WEB_ERROR_PAGE.status",@implode("\n",$l));
            return implode("\n",$l);
        }
    }

    if($enabled==0){
        if($GLOBALS["VERBOSE"]){echo "WEB_ERROR_PAGE:: EnableSquidMicroHotSpot report [NO]\n";}
        if(is_file($servicepath)){
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.hotspot-service.php --uninstall");
            squid_admin_mysql(0, "$productname seems disabled but installed [action=uninstall]","$servicepath exists",__FILE__,__LINE__);
            @file_put_contents(PROGRESS_DIR."/APP_HOTSPOT.status",@implode("\n",$l));
            return implode("\n",$l);
        }
        @file_put_contents(PROGRESS_DIR."/APP_HOTSPOT.status",@implode("\n",$l));
        return implode("\n",$l);
    }

    $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOTWEB_VERSION");

    $l[]="master_version=$master_version";
    $l[]="service_disabled=$enabled";
    $master_pid=HOTSPOT_WEB_PID();

    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
                squid_admin_mysql(0, "Warning $productname is stopped [{action}={start}]",
                    null,__FILE__,__LINE__);
            }

            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.hotspot-service.php --start");

        }

        $l[]="master_pid=$master_pid";
        $l[]="running=0";
        $l[]="installed=1";
        $l[]="";
        @file_put_contents(PROGRESS_DIR."/APP_HOTSPOT.status",@implode("\n",$l));
        return implode("\n",$l);
    }

    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid,"APP_HOTSPOT");
    $l[]="";
    @file_put_contents(PROGRESS_DIR."/APP_HOTSPOT.status",@implode("\n",$l));
    return implode("\n",$l);


}