<?php

function artica_notifs_pid(){
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/artica-smtp-notif.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("artica-postfix/bin/artica-smtpd");
}
function artica_notifs_check_version(){

    $MasterBin="/usr/share/artica-postfix/bin/artica-smtpd";
    $DestBin="/usr/sbin/artica-smtpd";
    $md51=md5_file($MasterBin);
    $md52=null;
    if(is_file($DestBin)){
        $md52=md5_file($DestBin);
    }
    if($md51==$md52){return true;}
    squid_admin_mysql(0, "Updating Artica notification service with new version [{action}={start}]", null,__FILE__,__LINE__);
    $GLOBALS["CLASS_UNIX"]->framework_exec("exec.smtpd.php --restart");
    return true;
}


function artica_notifs():string{
    $srvname="Artica notifications service";
    $init="/etc/init.d/artica-notifs";
    $l[] = "";

    $l[] = "[ARTICA_NOTIFS]";
    $l[] = "service_name=ARTICA_NOTIFS";
    $l[] = "service_cmd=$init";
    $l[] = "family            = system";
    $l[] = "watchdog_features = 1";
    $l[] = "installed         = 1";
    $l[] = "service_disabled  = 1";

    $master_pid=artica_notifs_pid();

    if(!is_file("$init")){
        squid_admin_mysql(1, "$srvname not installed [action=install]", null,__FILE__,__LINE__);
        $GLOBALS["CLASS_UNIX"]->framework_exec("exec.smtpd.php --install");
    }

    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            squid_admin_mysql(1, "$srvname {stopped} [{action}={start}]", null,__FILE__,__LINE__);
            shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} $init start");
        }
        $l[]="running=0";
        @file_put_contents(PROGRESS_DIR."/ARTICA_NOTIFS.status",@implode("\n",$l));
        return implode("\n",$l);
    }

    $l[]="running=1";
    artica_notifs_check_version();
    $l[]=GetMemoriesOf($master_pid);
    $l[]="";
    @file_put_contents(PROGRESS_DIR."/ARTICA_NOTIFS.status",@implode("\n",$l));
    return implode("\n",$l);

}
