<?php
include_once(dirname(__FILE__)."/class.ccurl.inc");
function dnscrypt_proxy(){

    $EnableDNSCryptProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSCryptProxy"));


    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSCRYPT_PROXY_VERSION");
    $l[]="[APP_DNSCRYPT_PROXY]";
    $l[]="service_name=APP_DNSCRYPT_PROXY";
    $l[]="service_cmd=/etc/init.d/dnscrypt-proxy";
    $l[]="master_version=$version";
    $l[]="service_disabled=$EnableDNSCryptProxy";
    $l[]="family=dns";
    $l[]="watchdog_features=1";

    if($EnableDNSCryptProxy==0){return implode("\n",$l);}

    if(!is_file("/etc/init.d/dnscrypt-proxy")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSCryptProxy",0);
        $nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
        shell_exec2("$nohup /usr/sbin/artica-phpfpm-service -restart-unbound >/dev/null 2>&1 &");
        return implode("\n",$l);
    }

    $master_pid=dnscrypt_proxy_pid();



    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            $ServerRunSince=$GLOBALS["CLASS_UNIX"]->ServerRunSince();
            if($ServerRunSince>3){squid_admin_mysql(0, "DnsCrypt Proxy service {stopped} [{action}={start}]", null,__FILE__,__LINE__);}
            $nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
            shell_exec2("$nohup /etc/init.d/dnscrypt-proxy start >/dev/null 2>&1 &");
        }
        $l[]="running=0\ninstalled=1";
        $l[]="";
        return implode("\n",$l);
    }
    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid,"APP_DNSCRYPT_PROXY");
    $l[]="";
    return implode("\n",$l);
}


function unbound(){
    $cmdprf="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix";
    if($GLOBALS["VERBOSE"]){echo "UNBOUND START....\n";}
    $UnboundEnabled        = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled");
    $EnableDNSFirewall     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFirewall"));
    $CORP_LICENSE          = $GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();

    if($EnableDNSFirewall==1){
        if(!$CORP_LICENSE){
            squid_admin_mysql(0,"{APP_UNBOUND} {disable} {APP_DNS_FIREWALL} {license_error}",
                null,__FILE__,__LINE__);
            shell_exec2("$cmdprf/exec.unbound.php --uninstall-firewall  >/dev/null 2>&1 &");
        }
    }



    if(!is_file("/etc/init.d/unbound")){
        if($UnboundEnabled==1){
            squid_admin_mysql(0,"DNS Cache service is enabled but script is not installed [action=install]",
                null,__FILE__,__LINE__);
            shell_exec("/usr/sbin/artica-phpfpm-service -install-unbound  >/dev/null 2>&1 &");
            return null;
        }
    }

    $unbound_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundVersion");
    $l[]="[APP_UNBOUND]";
    $l[]="service_name=APP_UNBOUND";
    $l[]="service_cmd=/etc/init.d/unbound";
    $l[]="master_version=$unbound_version";
    $l[]="service_disabled=1";
    $l[]="family=dns";
    $l[]="watchdog_features=1";

    $master_pid=unbound_pid();


    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            $ServerRunSince=$GLOBALS["CLASS_UNIX"]->ServerRunSince();
            if($ServerRunSince>3){squid_admin_mysql(0, "DNS Cache service {stopped} [{action}={start}]", null,__FILE__,__LINE__);}
            $nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
            shell_exec2("$nohup /etc/init.d/unbound start >/dev/null 2>&1 &");
        }
        $l[]="running=0\ninstalled=1";
        $l[]="";

        return implode("\n",$l);
    }
    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid,"APP_UNBOUND");
    $l[]="";

    $EnableUnboundBlackLists=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnboundBlackLists"));
    if($EnableUnboundBlackLists==1){
        $time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.unbound.blacklists_download.time");
        if($time>384){
            shell_exec2("$cmdprf/exec.unbound.php --blacklists-download  >/dev/null 2>&1 &");
        }

    }
    $RESTART_CRON=false;
    if($EnableDNSFirewall==1){
        if(!is_file("/etc/cron.d/dns-firewall-stats")) {
            $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("dns-firewall-stats", "* * * * *", "exec.firewall-dns-stats.php");
            $RESTART_CRON=true;
        }

    }else{
        if(is_file("/etc/cron.d/dns-firewall-stats")){
            @unlink("/etc/cron.d/dns-firewall-stats");
            $RESTART_CRON=true;
        }
    }
    if($RESTART_CRON){
        UNIX_RESTART_CRON();
    }

    $unbound_control=$GLOBALS["CLASS_UNIX"]->find_program("unbound-control");
    if(is_file($unbound_control)){
        $ftime=PROGRESS_DIR."/unbound.control.stats";
        $time=$GLOBALS["CLASS_UNIX"]->file_time_min($ftime);
        if($time>10){
            if(is_file($ftime)){@unlink($ftime);}
            $cmd="$unbound_control -c /etc/unbound/unbound.conf stats >$ftime 2>&1";
            shell_exec($cmd);
        }
    }

    $EnableDNSRootInts=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSRootInts"));
    if($EnableDNSRootInts==1){
        $time=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/unbound/root.hints");
        if($time>1880){
            $tmp=$GLOBALS["CLASS_UNIX"]->FILE_TEMP();

            $curl=new ccurl("https://www.internic.net/domain/named.cache");
            if($curl->GetFile($tmp)){
                @unlink("/etc/unbound/root.hints");
                @copy($tmp, "/etc/unbound/root.hints");
                @unlink($tmp);
                squid_admin_mysql(2, "[DNS]: Success refresh named.cache from www.internic.net", null,__FILE__,__LINE__);
                system("/etc/init.d/unbound reload");
            }

        }
    }


    return implode("\n",$l);
}

//---------------------------------------------------------------------------------------------------




function unbound_pid(){
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/unbound.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    $Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("unbound");
    return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);
}
function dnscrypt_proxy_pid(){
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/dnscrypt-proxy.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    $Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("dnscrypt-proxy");
    return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);
}