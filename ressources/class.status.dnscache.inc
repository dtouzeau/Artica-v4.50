<?php
function dnscache_pid():int{
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file(dnscache_pid_path());
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    return intval($GLOBALS["CLASS_UNIX"]->PIDOF(dnscache_destbin()));
}
function dnscache_pid_path():string{
    return "/var/run/artica-dnscache.pid";
}
function dnscache_destbin():string{
    $dstfile="/usr/sbin/dnscache";
    return $dstfile;
}

function dnscache():string{
    $servicepath="/etc/init.d/dnscache";
    $l[]="[APP_LOCAL_DNSCACHE]";
    $l[]="service_name=APP_LOCAL_DNSCACHE";
    $l[]="service_cmd=$servicepath";
    $l[]="family=proxy";
    $l[]="watchdog_features=1";
    $productname="Local DNS Cache service";
    $cachefile=PROGRESS_DIR."/dnscache.status";

    if(is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")){
        if(is_file("/etc/init.d/dnscache")){
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.dnscache.php --uninstall");
        }
        $l[]="service_disabled=0";
        return @implode("\n",$l);
    }

    $DoNotUseLocalDNSCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DoNotUseLocalDNSCache"));

    if(is_file("/etc/artica-postfix/DoNotUseLocalDNSCache")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DoNotUseLocalDNSCache",1);
        $DoNotUseLocalDNSCache=1;
    }

    if($DoNotUseLocalDNSCache==1){
        if($GLOBALS["VERBOSE"]){echo "APP_LOCAL_DNSCACHE:: Installed report [NO]\n";}
        if(is_file($servicepath)){
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.dnscache.php --uninstall");
            squid_admin_mysql(0, "$productname {disabled} [{action}={uninstall}]","$servicepath exists",__FILE__,__LINE__);
            @file_put_contents($cachefile,@implode("\n",$l));
            return implode("\n",$l);
        }
        @file_put_contents($cachefile,@implode("\n",$l));
        return implode("\n",$l);
    }
    if($DoNotUseLocalDNSCache==0){
        $service_disabled=1;
        if($GLOBALS["VERBOSE"]){echo "APP_LOCAL_DNSCACHE:: Installed report [YES]\n";}
        if(!is_file($servicepath)){
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.dnscache.php --install");
            squid_admin_mysql(0, "$productname {enabled} [{action}={install}]","$servicepath missing",__FILE__,__LINE__);
            @file_put_contents($cachefile,@implode("\n",$l));
            return implode("\n",$l);
        }
    }

    $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_LOCAL_DNSCACHE_VERSION");

    $l[]="master_version=$master_version";
    $l[]="service_disabled=$service_disabled";
    $master_pid=dnscache_pid();

    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
                squid_admin_mysql(0, "{warning} $productname is {stopped} [{action}={start}]",
                    null,__FILE__,__LINE__);
            }
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.dnscache.php --start");
        }

        $l[]="master_pid=$master_pid";
        $l[]="running=0";
        $l[]="installed=1";
        $l[]="";
        return implode("\n",$l);
    }

    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid,"APP_LOCAL_DNSCACHE");
    $l[]="";
    @file_put_contents($cachefile,@implode("\n",$l));
    return implode("\n",$l);


}