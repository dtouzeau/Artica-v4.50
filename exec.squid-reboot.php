<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

start_procedure();

function start_procedure(){
    $unix=new unix();
    $filetime="/var/run/cron-reboot-proxy.time";
    $timepid=$unix->file_time_min($filetime);

    if($timepid<3){
       zsyslog("Cannot start reconfigure proxy not before 3mn ( current is $timepid)");
        squid_admin_mysql(1,"Cannot start reconfigure proxy not before 3mn ( current is $timepid)",null,__FILE__,__LINE__);
        die();
    }
    @unlink($filetime);
    @file_put_contents($filetime,time());
   zsyslog("Starting reconfiguring proxy service procedure after rebooting");
    $php=$unix->LOCATE_PHP5_BIN();
    sleep(30);
    $t1=time();
    $LOGS=array();
    if(is_file("/etc/init.d/squid")) {
       zsyslog("Reconfiguring proxy service....");
        $LOGS[]=date("Y-m-d H:i:s")." Building Proxy configuration";
        shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --general --force --no-reload");
        $LOGS[] = date("Y-m-d H:i:s") . " Building Proxy configuration [DONE]";
    }
    if(is_file("/etc/init.d/ufdb")){
       zsyslog("Reconfiguring Web-filtering service....");
        $LOGS[]=date("Y-m-d H:i:s")." Building Web-filtering rules";
        shell_exec("$php /usr/share/artica-postfix/exec.ufdbguard.rules.php");
        $LOGS[]=date("Y-m-d H:i:s")." Building Web-filtering rules [DONE]";
        $LOGS[]=date("Y-m-d H:i:s")." Restarting Web-filtering Service";
        shell_exec("/etc/init.d/ufdb restart --reboot");
        $LOGS[]=date("Y-m-d H:i:s")." Restarting Web-filtering Service [DONE]";
    }
    if(is_file("/etc/init.d/squid")) {
        zsyslog("{reloading_proxy_service} after rebooting");
        $LOGS[]=date("Y-m-d H:i:s")." Reloading Proxy configuration";
        if(!is_dir("/var/run/squid")){@mkdir("/var/run/squid",0755,true);}
        @chown("/var/run/squid", "squid");
        @chgrp("/var/run/squid", "squid");
        $unix->go_exec("/usr/sbin/artica-phpfpm-service -reload-proxy");
        $LOGS[]=date("Y-m-d H:i:s")." Reloading Proxy configuration [DONE]";
    }

    if(is_file("/etc/init.d/theshields")){
        $LOGS[]=date("Y-m-d H:i:s")." Restarting The Shields server";
        shell_exec("/etc/init.d/theshields restart");
        $LOGS[]=date("Y-m-d H:i:s")." Restarting The Shields server [DONE]";

    }

    if(count($LOGS)>0){
       $took=$unix->distanceOfTimeInWords($t1,time(),true);
       zsyslog("Reconfiguring Proxy service after rebooting took: $took");
       squid_admin_mysql(1,"Reconfiguring Proxy service after rebooting took: $took",
            @implode("\n",$LOGS),__FILE__,__LINE__);
    }

}
function zsyslog($text){
    if(function_exists("openlog")){openlog("squid", LOG_PID , LOG_SYSLOG);}
    if(function_exists("syslog")){ syslog(LOG_INFO, "[proxy-reboot]: $text");}
    if(function_exists("closelog")){closelog();}
}