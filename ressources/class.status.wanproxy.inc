<?php
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}


function danted_pid():int{
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/danted/danted.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    $daemonbin="/usr/sbin/danted";
    return intval($GLOBALS["CLASS_UNIX"]->PIDOF($daemonbin));
}

function danted_service():string{
    return "";
    $WanproxyMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxyMode"));
    $WanProxyParentUseSock=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyParentUseSock"));
    $SQUIDEnable    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $WanProxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyEnabled"));
    if($SQUIDEnable==0){$WanProxyParentUseSock=1;}
    if($WanproxyMode=="client"){$WanProxyParentUseSock=0;}
    if($WanProxyEnabled==0){$WanProxyParentUseSock=0;}

    $l[]="[APP_SS5]";
    $l[]="service_name=APP_SS5";
    $l[]="service_cmd=/etc/init.d/danted";
    $l[]="master_version=". $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DANTED_VERSION");
    $l[]="service_disabled=$WanProxyParentUseSock";
    $l[]="family=network";
    $l[]="watchdog_features=1";
    $l[]="installed=1";

    if($WanProxyParentUseSock==0){
        if(is_file("/etc/init.d/danted")){
            squid_admin_mysql(0, "Backend SOCKS5 Proxy {installed}} [action={uninstall}]",
                "WanProxyParentUseSock token is inactive",
                __FILE__,__LINE__);
            shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.wanproxy.php --uninstall-socks");
        }

        return @implode("\n",$l);
    }

    $master_pid=danted_pid();
    if($GLOBALS["VERBOSE"]){echo "Master PID................. $master_pid\n";}

    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
                squid_admin_mysql(0, "Fatal: Backend SOCKS5 Proxy is not running [action={start_service}]", null,__FILE__,__LINE__);
            }
            shell_exec2("/etc/init.d/danted start");
        }

        $l[]="running=0";
        $l[]="";

        return implode("\n",$l);
    }


    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid,"APP_SS5");
    $l[]="";
    return implode("\n",$l);


}

function wanproxy():string{
    $WANPROXY_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WANPROXY_INSTALLED"));
    if($WANPROXY_INSTALLED==0){return "";}
    $WanProxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyEnabled"));
    if(!is_file("/etc/init.d/wanproxy")){
        if($WanProxyEnabled==1){
            squid_admin_mysql(0, "Wan Proxy compressor {not installed} [action={install}]",
                "WanProxyEnabled token is active",
                __FILE__,__LINE__);
            shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.wanproxy.php --install");
        }
        return danted_service();
    }

    $l[]="";
    $l[]="[APP_WANPROXY]";
    $l[]="service_name=APP_WANPROXY";
    $l[]="service_cmd=/etc/init.d/wanproxy";
    $l[]="master_version=1.0";
    $l[]="service_disabled=$WanProxyEnabled";
    $l[]="family=network";
    $l[]="watchdog_features=1";
    $l[]="installed=1";

    $master_pid=wanproxy_pid();
    if($GLOBALS["VERBOSE"]){echo "Master PID................. $master_pid\n";}
    if($WanProxyEnabled==0){
        shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.wanproxy.php --remove");
        @file_put_contents(PROGRESS_DIR."/wanproxy.status",@implode("\n",$l));
        return @implode("\n", $l);
    }

    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if(!is_file("/etc/init.d/wanproxy")){return "";}
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
                squid_admin_mysql(0, "Fatal: Wan Proxy compressor (parent) is not running [action={start_service}]", null,__FILE__,__LINE__);
            }
            shell_exec2("/etc/init.d/wanproxy start");
        }

        $l[]="running=0";
        $l[]="";
        if($GLOBALS["VERBOSE"]){echo "--------------- FAILED ---------------\n";}
        $l[]=danted_service();
        @file_put_contents(PROGRESS_DIR."/wanproxy.status",@implode("\n",$l));
        return implode("\n",$l);
    }


    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid,"APP_WANPROXY");
    $l[]="";
    $l[]=danted_service();
    if($GLOBALS["VERBOSE"]){echo "--------------- OK ---------------\n";}
    @file_put_contents(PROGRESS_DIR."/wanproxy.status",@implode("\n",$l));
    return implode("\n",$l);


}
function wanproxy_pid(){
    $daemonbin=$GLOBALS["CLASS_UNIX"]->find_program("wanproxy");
    return $GLOBALS["CLASS_UNIX"]->PIDOF($daemonbin);
}