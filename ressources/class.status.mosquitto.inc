<?php
// mosquitto MQTT message broker
// /etc/init.d/mosquitto
function mosquitto_pid():int{
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/mosquitto.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    $Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("mosquitto");
    return intval($GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin));
}

function mosquitto():string{
    $servicepath="/etc/init.d/mosquitto";
    $l[]="[APP_MOSQUITTO]";
    $l[]="service_name=APP_MOSQUITTO";
    $l[]="service_cmd=$servicepath";
    $l[]="family=proxy";
    $l[]="watchdog_features=1";
    $productname="Mosquitto MQTT message broker";

    $MosquittoEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MosquittoEnabled"));

    if($MosquittoEnabled==1){
        if($GLOBALS["VERBOSE"]){echo "APP_MOSQUITTO:: MosquittoEnabled report [YES]\n";}
        if(!is_file($servicepath)){
            $cmd=PHPART."/exec.mosquitto.php --install >/dev/null 2>&1 &";
            squid_admin_mysql(0, "$productname seems enabled but install corrupted [action=install]","$servicepath missing\nExecute: $cmd",__FILE__,__LINE__);
            shell_exec($cmd);
            return implode("\n",$l);
        }
    }

    if($MosquittoEnabled==0){
        if($GLOBALS["VERBOSE"]){echo "APP_MOSQUITTO:: MosquittoEnabled report [NO]\n";}
        if(is_file($servicepath)){
            $cmd=PHPART."/exec.mosquitto.php --uninstall >/dev/null 2>&1 &";
            if($GLOBALS["VERBOSE"]){echo "APP_MOSQUITTO:: Execute $cmd\n";}
            squid_admin_mysql(0, "$productname seems disabled but installed [action=uninstall]","$servicepath exists\nExecute $cmd",__FILE__,__LINE__);
            shell_exec($cmd);
            return implode("\n",$l);
        }
        return implode("\n",$l);
    }

    $master_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MOSQUITTO_VERSION");

    $l[]="master_version=$master_version";
    $l[]="service_disabled=$MosquittoEnabled";
    $master_pid=mosquitto_pid();

    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
                squid_admin_mysql(0, "{warning} $productname is {stopped} [{action}={start}]",
                    null,__FILE__,__LINE__);
            }
            $cmd=trim(PHPART."/exec.mosquitto.php --start >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }

        $l[]="master_pid=$master_pid";
        $l[]="running=0";
        $l[]="installed=1";
        $l[]="";
        return implode("\n",$l);
    }

    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid);
    $l[]="";
    return implode("\n",$l);


}