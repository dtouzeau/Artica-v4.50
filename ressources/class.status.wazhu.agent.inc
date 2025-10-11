<?php
function WHAZU_PIDFILE_PATH($daemon="agentd"):string{
    $DirFiles=$GLOBALS["CLASS_UNIX"]->DirFiles("/var/ossec/var/run","^wazuh-.*?-[0-9]+");
    $svc=array();
    foreach ($DirFiles as $fname=>$none){
        if(preg_match("#wazuh-(.+?)-([0-9]+)\.pid$#",$fname,$re)){
            $svc[$re[1]]="/var/ossec/var/run/$fname";
        }
    }
    if(!isset($svc[$daemon])){
        return "/var/ossec/var/run/wazuh-$daemon.pid";
    }
    return $svc[$daemon];
}
function APP_WHAZU_AGENT():string{
    $MasterBin  = "/var/ossec/bin/wazuh-control";
    $servicepath="/etc/init.d/wazuh-agent";
    $EnableWazhuCLient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWazhuCLient"));

    if($EnableWazhuCLient==1){
        if(!is_file($servicepath)){
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.wazhu.client.php --install");
            squid_admin_mysql(0, "Wazhu Agent seems enabled but install corrupted [{action}={install}]","$servicepath missing\n",__FILE__,__LINE__);
             return "";
        }
    }

    if($EnableWazhuCLient==0){
        if(is_file($servicepath)){
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.wazhu.client.php --uninstall");
            squid_admin_mysql(0, "Wazhu Agent seems disabled but installed [{action}={uninstall}]","$servicepath exists\n",__FILE__,__LINE__);
        }
        return "";
    }
    
    if(!is_file($MasterBin)){
        $GLOBALS["CLASS_UNIX"]->framework_exec("exec.wazhu.client.php --uninstall");
        squid_admin_mysql(0, "Wazhu Agent seems enable but not really installed [{action}={uninstall}]","$servicepath exists\n",__FILE__,__LINE__);

    }

    $l[]=APP_WHAZU_AGENTD();
    $l[]=APP_WHAZU_EXECD();
    $l[]=APP_WHAZU_LOGCOLLECTOR();
    $l[]=APP_WHAZU_MODULESD();
    $l[]=APP_WHAZU_SYSCHECKD();
    $statefile="/var/ossec/var/run/wazuh-agentd.state";
    $statefile_dest=PROGRESS_DIR."/wazuh-agentd.state";
    if(is_file($statefile_dest)){@unlink($statefile_dest);}
    @copy($statefile,$statefile_dest);
    @chown($statefile_dest,"www-data");
    @file_put_contents(PROGRESS_DIR."/APP_WHAZU_AGENT.status",@implode("\n",$l));
    return @implode("\n",$l);
}
function APP_WHAZU_AGENTD_PID(){
    $pid= $GLOBALS["CLASS_UNIX"]->get_pid_from_file(WHAZU_PIDFILE_PATH());
    if( $GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    return intval( $GLOBALS["CLASS_UNIX"]->PIDOF("/var/ossec/bin/wazuh-agentd"));
}
function APP_WHAZU_EXECD_PID(){
    $pid= $GLOBALS["CLASS_UNIX"]->get_pid_from_file(WHAZU_PIDFILE_PATH("wazuh-execd"));
    if( $GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    return intval( $GLOBALS["CLASS_UNIX"]->PIDOF("/var/ossec/bin/wazuh-execd"));
}
function APP_WHAZU_LOGCOLLECTOR_PID(){
    $pid= $GLOBALS["CLASS_UNIX"]->get_pid_from_file(WHAZU_PIDFILE_PATH("wazuh-logcollector"));
    if( $GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    return intval( $GLOBALS["CLASS_UNIX"]->PIDOF("/var/ossec/bin/wazuh-logcollector"));
}
function APP_WHAZU_MODULESD_PID(){
    $pid= $GLOBALS["CLASS_UNIX"]->get_pid_from_file(WHAZU_PIDFILE_PATH("wazuh-modulesd"));
    if( $GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    return intval( $GLOBALS["CLASS_UNIX"]->PIDOF("/var/ossec/bin/wazuh-modulesd"));
}
function APP_WHAZU_SYSCHECKD_PID(){
    $pid= $GLOBALS["CLASS_UNIX"]->get_pid_from_file(WHAZU_PIDFILE_PATH("wazuh-syscheckd"));
    if( $GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    return intval( $GLOBALS["CLASS_UNIX"]->PIDOF("/var/ossec/bin/wazuh-syscheckd"));
}

function APP_WHAZU_SYSCHECKD():string{
    $APP_WAZHU_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_WHAZU_SYSCHECKD");
    $servicepath="/etc/init.d/wazuh-agent";
    $l[]="[APP_WHAZU_SYSCHECKD]";
    $l[]="service_name=APP_WHAZU_SYSCHECKD";
    $l[]="service_cmd=$servicepath";
    $l[]="family=proxy";
    $l[]="watchdog_features=1";
    $l[]="master_version=$APP_WAZHU_VERSION";
    $l[]="service_disabled=1";
    $master_pid=APP_WHAZU_MODULESD_PID();

    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
                squid_admin_mysql(0, "Warning Wazuh SYSCHECKD is stopped [{action}={start}]",
                    null,__FILE__,__LINE__);
            }
            $cmd=trim("$servicepath start >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }

        $l[]="master_pid=$master_pid";
        $l[]="running=0";
        $l[]="installed=1";
        $l[]="";
        return implode("\n",$l);
    }

    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid,"APP_WHAZU_SYSCHECKD");
    $l[]="";
    return implode("\n",$l);

}
function APP_WHAZU_MODULESD():string{
    $APP_WAZHU_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_WAZHU_VERSION");
    $servicepath="/etc/init.d/wazuh-agent";
    $l[]="[APP_WHAZU_MODULESD]";
    $l[]="service_name=APP_WHAZU_MODULESD";
    $l[]="service_cmd=$servicepath";
    $l[]="family=proxy";
    $l[]="watchdog_features=1";
    $l[]="master_version=$APP_WAZHU_VERSION";
    $l[]="service_disabled=1";
    $master_pid=APP_WHAZU_MODULESD_PID();

    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
                squid_admin_mysql(0, "Warning Wazuh MODULESD is stopped [{action}={start}]",
                    null,__FILE__,__LINE__);
            }
            $cmd=trim("$servicepath start >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }

        $l[]="master_pid=$master_pid";
        $l[]="running=0";
        $l[]="installed=1";
        $l[]="";
        return implode("\n",$l);
    }

    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid,"APP_WHAZU_MODULESD");
    $l[]="";
    return implode("\n",$l);

}
function APP_WHAZU_LOGCOLLECTOR():string{
    $APP_WAZHU_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_WAZHU_VERSION");
    $servicepath="/etc/init.d/wazuh-agent";
    $l[]="[APP_WHAZU_LOGCOLLECTOR]";
    $l[]="service_name=APP_WHAZU_LOGCOLLECTOR";
    $l[]="service_cmd=$servicepath";
    $l[]="family=proxy";
    $l[]="watchdog_features=1";
    $l[]="master_version=$APP_WAZHU_VERSION";
    $l[]="service_disabled=1";
    $master_pid=APP_WHAZU_LOGCOLLECTOR_PID();

    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
                squid_admin_mysql(0, "Warning Wazuh LOGCOLLECTOR is stopped [{action}={start}]",
                    null,__FILE__,__LINE__);
            }
            $cmd=trim("$servicepath start >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }

        $l[]="master_pid=$master_pid";
        $l[]="running=0";
        $l[]="installed=1";
        $l[]="";
        return implode("\n",$l);
    }

    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid,"APP_WHAZU_LOGCOLLECTOR");
    $l[]="";
    return implode("\n",$l);

}
function APP_WHAZU_EXECD():string{
    $APP_WAZHU_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_WAZHU_VERSION");
    $servicepath="/etc/init.d/wazuh-agent";
    $l[]="[APP_WHAZU_EXECD]";
    $l[]="service_name=APP_WHAZU_EXECD";
    $l[]="service_cmd=$servicepath";
    $l[]="family=proxy";
    $l[]="watchdog_features=1";
    $l[]="master_version=$APP_WAZHU_VERSION";
    $l[]="service_disabled=1";
    $master_pid=APP_WHAZU_EXECD_PID();

    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
                squid_admin_mysql(0, "Warning Wazuh execd is stopped [{action}={start}]",
                    null,__FILE__,__LINE__);
            }
            $cmd=trim("$servicepath start >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }

        $l[]="master_pid=$master_pid";
        $l[]="running=0";
        $l[]="installed=1";
        $l[]="";
        return implode("\n",$l);
    }

    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid,"APP_WHAZU_EXECD");
    $l[]="";
    return implode("\n",$l);

}
function APP_WHAZU_AGENTD():string{
    $APP_WAZHU_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_WAZHU_VERSION");
    $servicepath="/etc/init.d/wazuh-agent";
    $l[]="[APP_WHAZU_AGENTD]";
    $l[]="service_name=APP_WHAZU_AGENTD";
    $l[]="service_cmd=$servicepath";
    $l[]="family=proxy";
    $l[]="watchdog_features=1";
    $l[]="master_version=$APP_WAZHU_VERSION";
    $l[]="service_disabled=1";
    $master_pid=APP_WHAZU_AGENTD_PID();

    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
                squid_admin_mysql(0, "Warning Whazu Agent is stopped [{action}={start}]",
                    null,__FILE__,__LINE__);
            }
            $cmd=trim("$servicepath start >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }

        $l[]="master_pid=$master_pid";
        $l[]="running=0";
        $l[]="installed=1";
        $l[]="";
        return implode("\n",$l);
    }

    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid,"APP_WHAZU_AGENTD");
    $l[]="";
    return implode("\n",$l);

}