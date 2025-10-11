<?php
include_once(dirname(__FILE__).'/class.maincf.multi.inc');


function saslauthd_pid_path():string{
    if(is_file('/var/run/saslauthd/saslauthd.pid')){return('/var/run/saslauthd/saslauthd.pid');}
    if(is_file('/var/run/saslauthd.pid')){return('/var/run/saslauthd.pid');}
    if(is_file('/var/run/saslauthd/saslauthd.pid')){return('/var/run/saslauthd/saslauthd.pid');}
    return "";
}

function saslauthd_pid():int{
    $unix=new unix();
    $pid=$unix->get_pid_from_file(saslauthd_pid_path());
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    $Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("saslauthd");
    return intval($GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin));

}
function sasalauthd_enabled():int{
    $EnablePostfix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix");
    if($EnablePostfix==0){return 0;}
    $main=new maincf_multi();
    $PostFixSmtpSaslEnable=intval($main->GET("PostFixSmtpSaslEnable"));
    $PostfixActiveDirectory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixActiveDirectory"));
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    $cyrus_imapd_installed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cyrus_imapd_installed"));
    if($EnableActiveDirectoryFeature==0){$PostfixActiveDirectory=0;}
    if($PostfixActiveDirectory==1){$PostFixSmtpSaslEnable=1;}
    if($cyrus_imapd_installed==1){$PostFixSmtpSaslEnable=1;}
    return $PostFixSmtpSaslEnable;
}


function saslauthd():string{
    $binpath = $GLOBALS["CLASS_UNIX"]->find_program("saslauthd");
    $APP_SASLAUTHD_VERSION=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SASLAUTHD_VERSION"));

    if (!is_file($binpath)) {
        @file_put_contents(PROGRESS_DIR."/APP_SASLAUTHD.status","");
        $l[] = "[SASLAUTHD]";
        $l[] = "service_name=APP_SASLAUTHD";
        $l[] = "master_version=$APP_SASLAUTHD_VERSION";
        $l[] = "service_cmd=saslauthd";
        $l[] = "service_disabled=0";
        return "";
    }

    $sasalauthd_enabled=sasalauthd_enabled();
    if($sasalauthd_enabled==0){
        @file_put_contents(PROGRESS_DIR."/APP_SASLAUTHD.status","");
        return "";
    }


    $master_pid = saslauthd_pid();
    $l[] = "[SASLAUTHD]";
    $l[] = "service_name=APP_SASLAUTHD";
    $l[] = "master_version=$APP_SASLAUTHD_VERSION";
    $l[] = "service_cmd=saslauthd";
    $l[] = "service_disabled=1";
    $l[] = "pid_path=";
    $l[] = "family=system";
    $l[] = "watchdog_features=1";

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
    }


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if (!$GLOBALS["DISABLE_WATCHDOG"]) {
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3) {
                squid_admin_mysql("{APP_SASLAUTHD} {stopped} {action}={start_service}",null,__FILE__,__LINE__);
                $GLOBALS["CLASS_UNIX"]->framework_exec("exec.saslauthd.php --build");
                $GLOBALS["CLASS_UNIX"]->framework_exec("exec.saslauthd.php --start");

            }
        }

        $l[] = "";
        @file_put_contents(PROGRESS_DIR."/APP_SASLAUTHD.status",@implode("\n",$l));
        return implode("\n", $l);
    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid,"SASLAUTHD");
    $l[] = "";

    if (is_file("/var/run/saslauthd/mux")) {
        @chmod("/var/run/saslauthd/mux", 0777);
    }
    @file_put_contents(PROGRESS_DIR."/APP_SASLAUTHD.status",@implode("\n",$l));
    return implode("\n", $l);

}

