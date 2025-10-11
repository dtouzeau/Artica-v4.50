<?php
if(function_exists("posix_getuid")){
    if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
}
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
if($SQUIDEnable==0){die("Proxy is not Enabled, aborting");}
//SP139
//SP119,SP 221
$GLOBALS["YESCGROUP"]=true;
$GLOBALS["BY_FORCE_RECONFIGURE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["DUMP"]=false;
$GLOBALS["MONIT"]=false;
$GLOBALS["CRASHED"]=false;
$GLOBALS["BY_CACHE_LOGS"]=false;
$GLOBALS["BY_STATUS"]=false;
$GLOBALS["BY_CLASS_UNIX"]=false;
$GLOBALS["BY_FRAMEWORK"]=false;
$GLOBALS["BY_OTHER_SCRIPT"]=false;
$GLOBALS["BY_ARTICA_INSTALL"]=false;
$GLOBALS["BY_RESET_CACHES"]=false;
$GLOBALS["BY_CRON"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["KILL_ALL"]=false;
$GLOBALS["URGENCY"]=false;
$GLOBALS["START_PROGRESS"]=false;
$GLOBALS["WIZARD"]=false;
$GLOBALS["OUTPUT"]=true;
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--monit#",implode(" ",$argv))){$GLOBALS["MONIT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];exit;}
if(preg_match("#reconfigure-count=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE_COUNT"]=$re[1];}

if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--dump#",implode(" ",$argv),$re)){$GLOBALS["DUMP"]=true;}
if(preg_match("#--crashed#",implode(" ",$argv),$re)){$GLOBALS["CRASHED"]=true;}
if(preg_match("#--cache-logs#",implode(" ",$argv),$re)){$GLOBALS["BY_CACHE_LOGS"]=true;}
if(preg_match("#--exec-status#",implode(" ",$argv),$re)){$GLOBALS["BY_STATUS"]=true;}
if(preg_match("#--class-unix#",implode(" ",$argv),$re)){$GLOBALS["BY_CLASS_UNIX"]=true;}
if(preg_match("#--framework#",implode(" ",$argv),$re)){$GLOBALS["BY_FRAMEWORK"]=true;}
if(preg_match("#--script=(.+)#",implode(" ",$argv),$re)){$GLOBALS["BY_OTHER_SCRIPT"]=$re[1];}
if(preg_match("#--bydaemon#",implode(" ",$argv),$re)){$GLOBALS["BY_ARTICA_INSTALL"]=true;}
if(preg_match("#--bycron#",implode(" ",$argv),$re)){$GLOBALS["BY_CRON"]=true;}
if(preg_match("#--byForceReconfigure#",implode(" ",$argv),$re)){$GLOBALS["BY_FORCE_RECONFIGURE"]=true;}
if(preg_match("#--by-reset-caches#",implode(" ",$argv),$re)){$GLOBALS["BY_RESET_CACHES"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--kill-all#",implode(" ",$argv),$re)){$GLOBALS["KILL_ALL"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--urgency#",implode(" ",$argv),$re)){$GLOBALS["URGENCY"]=true;$GLOBALS["URGENCY"]=true;}
if(preg_match("#--start-progress#",implode(" ",$argv),$re)){$GLOBALS["START_PROGRESS"]=true;}
if(preg_match("#--wizard#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;$GLOBALS["WIZARD"]=true;}

$GLOBALS["AS_ROOT"]=true;
$GLOBALS["MYPID"]=getmypid();
$GLOBALS["NO_KILL_MYSQL"]=true;
$GLOBALS["STAMP_MAX_RESTART"]="/etc/artica-postfix/SQUID_STAMP_RESTART";
$GLOBALS["STAMP_MAX_RESTART_TTL"]="/etc/artica-postfix/STAMP_MAX_RESTART_TTL";
$GLOBALS["STAMP_MAX_PING"]="/etc/artica-postfix/SQUID_STAMP_MAX_PING";
$GLOBALS["STAMP_FAILOVER"]="/etc/artica-postfix/SQUID_FAILOVER";
$GLOBALS["STAMP_REBOOT"]="/etc/artica-postfix/SQUID_REBOOT";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.booster.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.watchdog.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.icap.manager.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}


$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
if($SQUIDEnable==0){exit();}

$unix=new unix();
$squidbin=$unix->LOCATE_SQUID_BIN();
if(!is_file($squidbin)){exit();}

$GLOBALS["ARGVS"]=implode(" ",$argv);


if(isset($argv[1])){

    echo "Execute.......: ".date("H:i:s")." running {$argv[1]}\n";
    if($argv[1]=="--shm"){dev_shm();exit();}
    if($argv[1]=="--monit-reload-timeout"){monit_reload_timeout();exit;}

    if($argv[1]=="--cache-mem"){squid_cache_mem_current();exit();}
    if($argv[1]=="--caches-size"){caches_size();exit();}
    if($argv[1]=="--cnx"){squid_conx();exit();}
    if($argv[1]=="--mem"){exit();}
    if($argv[1]=="--mem-month"){exit();}
    if($argv[1]=="--externals"){exit;}
    if($argv[1]=="--empty-access"){empty_access_log();exit;}
    if($argv[1]=="--storage-infos"){squid_get_storage_info();exit();}
    if($argv[1]=="--check-status"){check_status();exit();}
    if($argv[1]=="--external-acl-children-more"){external_acl_children_more();exit();}
    if($argv[1]=="--ha-up"){CHECK_HA_MASTER_UP(true);exit();}
    if($argv[1]=="--ha"){CHECK_HA_MASTER_UP();exit();}
    if($argv[1]=="--caches-center"){$GLOBALS["OUTPUT"]=true;caches_center();exit();}
    if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop_squid();exit();}
    if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start_squid();exit();}
    if($argv[1]=="--restart"){restart_squid();exit();}
    if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload_squid();exit();}
    if($argv[1]=="--squidz"){$GLOBALS["OUTPUT"]=true;squidz();exit();}


    if($argv[1]=="--swapstate"){$GLOBALS["OUTPUT"]=true;$GLOBALS["SWAPSTATE"]=true;restart_squid();exit();}
    if($argv[1]=="--memboosters"){$GLOBALS["OUTPUT"]=true;MemBoosters();exit();}




    if($argv[1]=="--ping"){PING_GATEWAY();exit();}
    if($argv[1]=="--kerberos"){kerberos_auth_tests();exit;}

    
    if($argv[1]=="--logs"){CheckOldCachesLog();exit();}
    if($argv[1]=="--DeletedCaches"){DeletedCaches();exit();}
    if($argv[1]=="--CleanMemBoosters"){CleanMemBoosters();exit();}
    if($argv[1]=="--ufdb"){exit();}
    if($argv[1]=="--www"){exit();}
    if($argv[1]=="--squid-store-status"){$GLOBALS["OUTPUT"]=true;ALL_STATUS();exit();}
    if($argv[1]=="--swap-watch"){SwapWatchdog();exit();}
    if($argv[1]=="--checkufdbguard"){exit();}
    if($argv[1]=="--dns"){exit();}
    if($argv[1]=="--ufdbthreads"){exit();}
    if($argv[1]=="--sizes"){CheckAvailableSize();exit();}
    if($argv[1]=="--rqs"){die("Depreciated");}
    if($argv[1]=="--rqsb"){die("Depreciated");}
    if($argv[1]=="--all-status"){ALL_STATUS();exit();}

    if($argv[1]=="--icap"){C_ICAP_CLIENTS();exit();}
    if($argv[1]=="--disable-snmp"){disable_snmp();exit();}
    if($argv[1]=="--mem-status"){squid_mem_status(true);exit();}

    if($argv[1]=="--test-port"){exit();}
    if($argv[1]=="--start-progress"){$GLOBALS["START_PROGRESS"]=true;start_squid();exit();}
    if($argv[1]=="--categories"){exit();}
    if($argv[1]=="--bandwidth-cron"){BANDWIDTH_MONITOR_CRON();exit();}
    if($argv[1]=="--bandwidth-run"){BANDWIDTH_MONITOR();exit();}
    if($argv[1]=="--wifidog"){exit();}
    if($argv[1]=="--taskset"){taskset();exit();}
    if($argv[1]=="--notify-monit-start"){notify_start();exit();}
    if($argv[1]=="--notify-monit-stop"){notify_stop();exit();}
    if($argv[1]=="--notify-monit-ssldb"){notify_ssl_db();exit();}
    if($argv[1]=="--incident"){$unix=new unix();$unix->IncidentSquid($argv[2]);}
}




if($GLOBALS["VERBOSE"]){echo "start_watchdog()\n";}
$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
if($SQUIDEnable==0){
    echo "Proxy is not enabled, aborting task.\n";
    die();
}

start_watchdog();
function notify_ssl_db(){
    $pid="/etc/artica-postfix/cron.1/".basename(__FILE__).".".__FUNCTION__.".time";

    $unix=new unix();
    if(is_file($pid)){
        $xtime=$unix->file_time_min($pid);
        if($xtime<2){die();}
    }
    squid_admin_mysql(2, "Maintenance performed on the ssl_db directory by the watchdog", null,"monit",__LINE__);

    @unlink($pid);
    @file_put_contents($pid,time());
}
function _out($text):bool{
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("squid", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}

function monit_reload_timeout():bool{
    $unix=new unix();
    $SquidMgrListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
    $DoubleCheckText=null;
    $ErrorNum=0;
    $Error="curl_init no such function";


    if(function_exists("curl_init")){
        $uri="http://127.0.0.1:$SquidMgrListenPort/squid-internal-mgr/utilization";
        $ch=curl_init();
        if($GLOBALS["VERBOSE"]){echo "Testing $uri\n";}
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOPROXY,"*");
        curl_setopt($ch, CURLOPT_INTERFACE,"127.0.0.1");
        $resp = curl_exec($ch);
        $Error=curl_error($ch);
        $ErrorNum=curl_errno($ch);
        if($GLOBALS["VERBOSE"]){
            echo "$ErrorNum - $Error\n";
            echo $resp."\n";
        }

        // 7 - Connection refused

        if($ErrorNum==0){
            _out("[PORT_TIMEOUT]: Do nothing $uri return OK");
            return true;

        }
        $DoubleCheckText="double-check: $uri return Error $ErrorNum  $Error";
        _out("[PORT_TIMEOUT]: $DoubleCheckText");

    }



    $squid_pid=$unix->SQUID_PID();
    $process_time=$unix->PROCCESS_TIME_MIN($squid_pid);
    if($process_time<5){
        _out("[PORT_TIMEOUT]: Do nothing due to process time too short ({$process_time}Mn)");
        return true;
    }

    _out("[PORT_TIMEOUT]: Warning Detected timeout from the watchdog PID:$squid_pid TTL:{$process_time}Mn");




    if($process_time>5){
        $results[]="$DoubleCheckText";
        $tail=$unix->find_program("tail");
        $grep=$unix->find_program("grep");
        $results[]="----------- Monitor behavior -------------------";
        exec("$grep --binary-file=text APP_SQUID /var/log/monit.log|$tail -n 50 2>&1",$results);
        $results[]="----------- Proxy behavior -------------------";
        exec("$tail -n 50 /var/log/squid/cache.log 2>&1",$results);
        squid_admin_mysql(0,"Restarting Proxy service due to error.$ErrorNum ($Error) after {$process_time}Mn Time to Live",
            @implode("\n",$results),__FILE__,__LINE__);
        shell_exec("/usr/sbin/artica-phpfpm-service -restart-proxy");
        return true;

    }

    _out("[PORT_TIMEOUT]: Do nothing due to process time too short ({$process_time}Mn)");
    return true;
}

function notify_start():bool{
    $pid="/etc/artica-postfix/cron.1/".basename(__FILE__).".".__FUNCTION__.".time";

    $unix=new unix();
    if(is_file($pid)){
        $xtime=$unix->file_time_min($pid);
        if($xtime<2){die();}
    }

    squid_admin_mysql(0, "[watchdog] {start} {APP_PROXY}", null,"artica.squid.start.sh",__LINE__);
    @unlink($pid);
    @file_put_contents($pid,time());
    return true;

}
function notify_stop(){
    $pid="/etc/artica-postfix/cron.1/".basename(__FILE__).".".__FUNCTION__.".time";

    $unix=new unix();
    if(is_file($pid)){
        $xtime=$unix->file_time_min($pid);
        if($xtime<2){die();}
    }
    squid_admin_mysql(0, "Watchdog Daemon stop the proxy service", null,"artica.squid.stop.sh",__LINE__);

    @unlink($pid);
    @file_put_contents($pid,time());

}
function PING_GATEWAY_DEFAULT_PARAMS($MonitConfig){
    if(!is_array($MonitConfig)){$MonitConfig=array();}
    if(!isset($MonitConfig["ENABLE_PING_GATEWAY"])){$MonitConfig["ENABLE_PING_GATEWAY"]=0;}
    if(!isset($MonitConfig["MAX_PING_GATEWAY"])){$MonitConfig["MAX_PING_GATEWAY"]=10;}
    if(!isset($MonitConfig["PING_FAILED_RELOAD_NET"])){$MonitConfig["PING_FAILED_RELOAD_NET"]=0;}
    if(!isset($MonitConfig["PING_FAILED_REPORT"])){$MonitConfig["PING_FAILED_REPORT"]=1;}
    if(!isset($MonitConfig["PING_FAILED_REBOOT"])){$MonitConfig["PING_FAILED_REBOOT"]=0;}
    if(!isset($MonitConfig["PING_FAILED_FAILOVER"])){$MonitConfig["PING_FAILED_FAILOVER"]=0;}
    if(!is_numeric($MonitConfig["ENABLE_PING_GATEWAY"])){$MonitConfig["ENABLE_PING_GATEWAY"]=0;}
    if(!is_numeric($MonitConfig["MAX_PING_GATEWAY"])){$MonitConfig["MAX_PING_GATEWAY"]=10;}
    if(!is_numeric($MonitConfig["PING_FAILED_RELOAD_NET"])){$MonitConfig["PING_FAILED_RELOAD_NET"]=0;}
    if(!is_numeric($MonitConfig["PING_FAILED_REPORT"])){$MonitConfig["PING_FAILED_REPORT"]=1;}
    if(!is_numeric($MonitConfig["PING_FAILED_REBOOT"])){$MonitConfig["PING_FAILED_REBOOT"]=0;}
    if(!is_numeric($MonitConfig["PING_FAILED_FAILOVER"])){$MonitConfig["PING_FAILED_FAILOVER"]=0;}
    return $MonitConfig;
}



function disable_snmp(){
    $squid=new squidbee();
    if($squid->snmp_enable==1){
        squid_admin_mysql(1,"SNMP feature was disabled by watchdog",null,__FILE__,__LINE__);
        $squid->snmp_enable=0;
        $squid->SaveToLdap();
    }

}

function watchdog_config_default(){
    $w=new squid_watchdog();
    return $w->MonitConfig;
}

function ALL_STATUS($aspid=false){

    $unix=new unix();


    if(!is_file("/usr/sbin/mgr-info")){
        $ln=$unix->find_program("ln");
        shell_exec("$ln -sf/usr/share/artica-postfix/exec.cmdline.squid.cache.mem.php /usr/sbin/mgr-info");
        @chmod(dirname(__FILE__)."/exec.cmdline.squid.cache.mem.php", 0755);
    }

    if($GLOBALS["VERBOSE"]){$GLOBALS["OUTPUT"]=true;}

    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")."Already `task` running PID $pid since {$time}mn\n";}
            return;
        }
        @file_put_contents($pidfile, getmypid());
    }



    empty_access_log();
    build_progress_status("squid_cache_mem_current()",9);
    squid_cache_mem_current();
    build_progress_status("dev_shm()",9);
    kerberos_auth_tests();
    dev_shm();
    build_progress_status("taskset()",9);
    taskset();
    build_progress_status("CRON_NECESSARIES()",9);
    CRON_NECESSARIES();
    build_progress_status("C_ICAP_CLIENTS()",22);
    C_ICAP_CLIENTS();
    build_progress_status("eCapClamav {status}",30);
    eCapClamav();
    squid_conx();
    build_progress_status("Done...",100);
}






function empty_access_log():bool{
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){return true;}
    $SquidNoAccessLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNoAccessLogs"));
    if($SquidNoAccessLogs==1){return true;}
    $LogsWarninStop=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsWarninStop");
    if($LogsWarninStop==1){return true;}
    $SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
    if($SquidUrgency==1){return true;}
    if(!is_file("/etc/init.d/squid")){return true;}

    $unix=new unix();
    $filename="/var/log/squid/access.log";

    $created=$unix->file_time_min($filename);
    if($created<3){return true;}
    $fsize=@filesize($filename);
    if($fsize>10){return true;}
    $LOGS[]="The log access $filename is alive since {$created} minutes and the size is only $fsize bytes length";

    @unlink($filename);
    @touch($filename);
    @chown($filename,"squid");
    @chgrp($filename,"squid");
    $unix->go_exec("/usr/sbin/artica-phpfpm-service -reload-proxy");
    $LOGS[]=date("Y-m-d H:i:s")." Reloading Proxy configuration [DONE]";
    squid_admin_mysql(1,"Suspicious Proxy real-time access log inactivity [action=reload]",@implode("\n",$LOGS),__FILE__,__LINE__);
    return true;
}

function build_progress_status($text,$pourc){
    $cachefile="/usr/share/artica-postfix/ressources/logs/squid.reload.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);

}
function build_progress_status2($text,$pourc){
    $cachefile=PROGRESS_DIR."/squid.status.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}







function build_progress_restart($text,$pourc){
    if($GLOBALS["VERBOSE"]){echo "******************** {$pourc}% $text ********************\n";}

    $cachefile="/usr/share/artica-postfix/ressources/logs/squid.restart.progress";

    if($GLOBALS["URGENCY"]){
        $cachefile=PROGRESS_DIR."/squid.urgency.disable.progress";
    }
    $array["prfunction"]=__FUNCTION__;
    $trace=debug_backtrace();
   foreach ($trace as $index=>$ligne){
       $array["TRACES"][$index]["FILE"]=basename($ligne["file"]);
       $array["TRACES"][$index]["function"]=$ligne["function"];
       $array["TRACES"][$index]["line"]=$ligne["line"];
    }

    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;


    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
    zwriteprogress($pourc,$text);

}
function build_progress_reload($text,$pourc){
    if($GLOBALS["VERBOSE"]){echo "******************** {$pourc}% $text ********************\n";}else{
        echo "Starting......: ".date("H:i:s")." {$pourc}% $text\n";
    }
    $cachefile="/usr/share/artica-postfix/ressources/logs/squid.reload.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
    Events($text);
}
function build_progress_start($text,$pourc){
    if(!$GLOBALS["START_PROGRESS"]){return;}
    echo "Starting......: ".date("H:i:s")." {$pourc}% $text\n";
    $cachefile=PROGRESS_DIR."/squid.start.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    $array["prfunction"]=__FUNCTION__;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
    sleep(1);
}


function start_watchdog(){
    if($GLOBALS["VERBOSE"]){$GLOBALS["FORCE"]=true;}
    $pidtime="/etc/artica-postfix/pids/exec.squid.watchdog.php.start_watchdog.time";
    $pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pidtimeNTP="/etc/artica-postfix/pids/exec.squid.watchdog.php.start_watchdog.ntp.time";
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0){ return false; }
    $unix=new unix();
    if($unix->ServerRunSince()<3){
        return false;
    }

    $pid=$unix->get_pid_from_file($pidFile);

    if($unix->process_exists($pid)){
        $pptime=$unix->PROCCESS_TIME_MIN($pid,10);
        if($GLOBALS["VERBOSE"]){echo "Process already running PID $pid since {$pptime}Mn\n";}
        return false;
    }

    @file_put_contents($pidFile, getmypid());
    $time=$unix->file_time_min($pidtime);
    if($time<5){return false;}
    @unlink($pidtime);
    @file_put_contents($pidtime,time());

    $NtpdateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtpdateAD"));
    $EnableFailover=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFailover"));
    $GLOBALS["EnableFailover"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFailover");

    if(!is_file("/etc/squid3/squid.conf")){
        squid_admin_mysql(0, "Proxy service: missing main configuration [action=reconfigure]",
            null, __FILE__, __LINE__);
        $unix->framework_exec("exec.squid.php --build");
        return false;
    }

    if($NtpdateAD==1){
        $pidtimeNTPT=$unix->file_time_min($pidtimeNTP);
        if($pidtimeNTPT>120){
            $unix->ToSyslog("Running time synchronization with the Active Directory server",false,"ntpdate");
            $unix->framework_exec("exec.kerbauth.php --ntpdate");
            @unlink($pidtimeNTP);
            @file_put_contents($pidtimeNTP, time());
        }
    }

    if(!is_file("/usr/share/squid3/icons/silk/page_white_powerpoint.png")){
        $tar=$unix->find_program("tar");
        if(!is_dir("/usr/share/squid3/icons/silk")){@mkdir("/usr/share/squid3/icons/silk");}
        squid_admin_mysql(1,"Installing Proxy service images",null,__FILE__,__LINE__);
        shell_exec("$tar -xf /usr/share/artica-postfix/bin/install/squid/squid-images.tar.gz -C /usr/share/squid3/icons/silk/");

    }

    @unlink($pidtime);
    @file_put_contents($pidtime,time());

    $pid=SQUID_PID();
    $processtime=$unix->PROCCESS_TIME_MIN($pid);
    if(!$GLOBALS["FORCE"]){
        if($processtime<2){
            return false;
        }
    }
    $GLOBALS["NOCHECKPID"]=true;

    $functions=array("CRON_NECESSARIES","PARANOID_MODE_CLEAN","TEST_ACTIVE_DIRECTORY","CheckActiveDirectoryEmergency","ALL_STATUS","CheckOldCachesLog","DeletedCaches","caches_center","caches_size");

    Events("* * * * * * * * * * * * * * * * START WATCHDOG * * * * * * * * * * * * * * * *");
    Events("Testing Memory");
    $unix->framework_exec("exec.system.CheckMemory.php");
    $GLOBALS["ALL_SCORES"]=0;

    foreach ($functions as $func) {
        if(!function_exists($func)){continue;}
        try {
            Events("Testing $func()");
            call_user_func($func);
            $GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after $func()";
            if(system_is_overloaded(__FILE__)){
                squid_admin_mysql(1,"Proxy Watchdog: {OVERLOADED_SYSTEM} after $func()",__FILE__,__LINE__);
                return true;
            }

        } catch (Exception $e) {
            Events("Fatal while running function $func ($e)");

        }
    }
    $MonitConfig=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchdogMonitConfig"));
    $MonitConfig["EnableFailover"]=$EnableFailover;
    $MonitConfig=watchdog_config_default();


    if(!$GLOBALS["VERBOSE"]){
        if($time<$MonitConfig["MIN_INTERVAL"]){
            return false;
        }
    }

    $STAMP_MAX_RESTART_TIME=$unix->file_time_min($GLOBALS["STAMP_MAX_RESTART"]);
    if($STAMP_MAX_RESTART_TIME>60){@unlink($GLOBALS["STAMP_MAX_RESTART"]);}


    if($MonitConfig["watchdog"]==0){
        if($GLOBALS["VERBOSE"]){echo "Watchdog is disabled...\n";}
        return true;
    }

    if($processtime<5){return false;}


    $functions=array("PING_GATEWAY",
        "SwapWatchdog","Checks_Winbindd",
        "CHECK_SQUID_EXTERNAL_LDAP","MemBoosters","MaxSystemLoad",
        "CheckAvailableSize");


    foreach ($functions as $func) {
        if(!function_exists($func)){continue;}
        try {
            call_user_func($func);
            $GLOBALS["ALL_SCORES_WHY"][]="score: {$GLOBALS["ALL_SCORES"]} after $func()";
            if(system_is_overloaded(__FILE__)){
                squid_admin_mysql(1," {OVERLOADED_SYSTEM} after $func()",__FILE__,__LINE__);
                return true;
            }

        } catch (Exception $e) {
            Events("Fatal while running function $func ($e)");

        }
    }
    Events("* * * * * * * * * * * * * * * * END WATCHDOG * * * * * * * * * * * * * * * *");
    return true;
}


function CRON_NECESSARIES():bool{
    $unix=new unix();
    if(is_file("/etc/cron.d/squid-ping-port")){
        @unlink("/etc/cron.d/squid-ping-port");
        $unix->go_exec("/etc/init.d/cron reload");
    }

    if(is_file("/etc/cron.d/squid-watch-sockets")){
        @unlink("/etc/cron.d/squid-watch-sockets");
        $unix->go_exec("/etc/init.d/cron reload");
    }
    if(is_file("/etc/cron.d/squid-watchdogport")){@unlink("/etc/cron.d/squid-watchdogport");}
    if(is_file("/etc/cron.d/proxy-test-port")){@unlink("/etc/cron.d/proxy-test-port");}

    if(is_file("/etc/cron.d/squid-statsmembers5mn")){@unlink("/etc/cron.d/squid-statsmembers5mn");}

    if(!is_file("/usr/share/squid3/icons/silk/bigshield-256.png")){
        @copy("/usr/share/artica-postfix/img/bigshield-256.png","/usr/share/squid3/icons/silk/bigshield-256.png");
    }

    if(!is_file("/usr/share/squid3/icons/silk/logo-artica-64.png")){
        @copy("/usr/share/artica-postfix/img/logo-artica-64.png","/usr/share/squid3/icons/silk/logo-artica-64.png");
    }

    if(!is_file("/etc/cron.d/artica-squid-5min")){
        $unix->Popuplate_cron_make("artica-squid-5min","*/6 * * * *","exec.squidMins.php");
        $unix->go_exec("/etc/init.d/cron reload");
    }
    if(!is_file("/etc/artica-postfix/SQUID_TEMPLATE_DONE")){
        squid_admin_mysql(1, "SQUID_TEMPLATE_DONE: No such file, launch build template action...", null,__FILE__,__LINE__);
        $unix->framework_exec("exec.squid.php --tpl-save");
    }

    if(!is_dir("/home/squid/cache-logs")) {
        @mkdir("/home/squid/cache-logs", 0755, true);
    }

    return true;

}


function MaxSystemLoad(){
    $unix=new unix();
    $shutdown=$unix->find_program("shutdown");
    $nohup=$unix->find_program("nohup");
    $MonitConfig=watchdog_config_default();


    if($MonitConfig["LOAD_TESTS"]==0){return;}


    $array_load=sys_getloadavg();
    $internal_load=$array_load[0];


    $LOAD_MAX=$MonitConfig["LOAD_MAX"];
    $LOAD_MAX_ACTION=$MonitConfig["LOAD_MAX_ACTION"];


    $array_mem=getSystemMemInfo();
    $MemFree=$array_mem["MemFree"];
    $MemFree=round($MemFree/1024);


    if($MonitConfig["MinFreeMem"]>0){
        $report=$unix->ps_mem_report();
        if($MemFree<$MonitConfig["MinFreeMem"]){
            squid_admin_mysql(2, "No memory free: {$MemFree}MB, Need at least {$MonitConfig["MinFreeMem"]}MB",$report,__FILE__,__LINE__);
            if($MonitConfig["MaxLoadFailOver"]==1){
                $GLOBALS["ALL_SCORES"]++;
                $GLOBALS["ALL_SCORES_WHY"][]="No memory free: {$MemFree}MB, Need at least {$MonitConfig["MinFreeMem"]}MB";
                return;
            }

        }
    }


    if($internal_load>$LOAD_MAX){
        $report=$unix->ps_mem_report();
        system_is_overloaded();
        $GLOBALS["ALL_SCORES_WHY"][]="{OVERLOADED_SYSTEM} Load: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, system {$GLOBALS["SYSTEM_INTERNAL_MEMM"]}MB memory free";
        if($LOAD_MAX_ACTION=="reboot"){
            squid_admin_mysql(0, "{OVERLOADED_SYSTEM} Load: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: reboot the server", $report,__FILE__,__LINE__);
            shell_exec("$nohup $shutdown -r -t 5 >/dev/null 2>&1 &");
            return;
        }

        if($LOAD_MAX_ACTION=="restart"){
            squid_admin_mysql(0, "{OVERLOADED_SYSTEM} Load: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: [ {action} = {restart} ]", $report,__FILE__,__LINE__);
            restart_squid(true);
            return;
        }

        squid_admin_mysql(0, "{OVERLOADED_SYSTEM} Load: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: {OVERLOADED_SYSTEM} [action=none]", $report,__FILE__,__LINE__);

    }




    if($MonitConfig["MaxLoad"]>0){
        if($internal_load>$MonitConfig["MaxLoad"]){
            if($MonitConfig["MaxLoadFailOver"]==1){
                $GLOBALS["ALL_SCORES"]++;
                system_is_overloaded();
                squid_admin_mysql(2, "{OVERLOADED_SYSTEM} Load: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}", "System reach {$MonitConfig["MaxLoadFailOver"]} value",__FILE__,__LINE__);
                $GLOBALS["ALL_SCORES_WHY"][]="{OVERLOADED_SYSTEM} Load: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, system {$GLOBALS["SYSTEM_INTERNAL_MEMM"]}MB memory free";
            }

            if($MonitConfig["MaxLoadReboot"]==1){
                squid_admin_mysql(0, "{OVERLOADED_SYSTEM} Load: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: reboot the server", "Watchdog system, reboot the server",__FILE__,__LINE__);
                $unix=new unix();
                $shutdown=$unix->find_program("shutdown");
                $nohup=$unix->find_program("nohup");
                shell_exec("$nohup $shutdown -r -t 5 >/dev/null 2>&1 &");
                return;
            }
        }

    }



}

function swap_state():bool{
    $unix=new unix();
    $caches=$unix->SQUID_CACHE_FROM_SQUIDCONF();
    foreach ($caches as $directory=>$type){
        if(strtolower($type)=="rock"){continue;}
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." scanning cache $directory\n";}
        foreach (glob("$directory/swap.*") as $filename) {
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." removing $filename\n";}
            @unlink($filename);
        }
    }

    return true;
}

function CheckOldCachesLog():bool{


    $unix=new unix();
    foreach (glob("/var/log/squid/cache.log.*") as $filename) {
        if($GLOBALS["VERBOSE"]){echo "Move $filename to /home/squid/cache-logs\n";}
        Events("Move $filename to /home/squid/cache-logs");
        @copy($filename, "/home/squid/cache-logs/".basename($filename));
        @unlink($filename);
    }

    foreach (glob("/home/squid/cache-logs/*") as $filename) {
        $ext=$unix->file_ext($filename);
        if(is_numeric($ext)){
            Events("Compress $filename to $filename.gz");
            if($unix->compress($filename, "$filename.gz")){@unlink($filename);}
            continue;
        }

        if($ext=="gz"){
            $time=$unix->file_time_min($filename);
            if($GLOBALS["VERBOSE"]){echo "$filename  = {$time}Mn\n";}
            if($time>4320){
                Events("Remove $filename (exceed 3 days on disk...)");
                @unlink($filename);
            }
        }

    }

    if($GLOBALS["VERBOSE"]){echo "CheckOldCachesLog:: END\n";}
    return true;
}

function UFDBGUARD_PID_NUM(){
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/ufdbguard/ufdbguardd.pid");
    if($unix->process_exists($pid)){
        return $pid;

    }
    $Masterbin=$unix->find_program("ufdbguardd");
    if(!is_file($Masterbin)){return 0;}
    return $unix->PIDOF($Masterbin);
}



function CHECK_HA_MASTER_UP($MustUp=false){

    $unix=new unix();
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){if($GLOBALS["VERBOSE"]){echo "License error\n";}return;		}
    $MAIN=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HASettings")));
    if(!isset($MAIN["SLAVE"])){if($GLOBALS["VERBOSE"]){echo "I'm the slave,nothing to do.\n";}return;}

    include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
    $proto="http";
    if($MAIN["SLAVE_SSL"]==1){$proto="https";}
    $SLAVE_PORT=$MAIN["SLAVE_PORT"];
    $IP=$MAIN["SLAVE"];
    $uri="$proto://$IP:$SLAVE_PORT/nodes.listener.php";
    $Hooked_interface=$MAIN["eth"];
    $nic=new system_nic($Hooked_interface);
    $ProductionIP=$nic->ucarp_vip;
    $WgetBindIpAddress=$MAIN["WgetBindIpAddress"];
    $ifconfig=$unix->find_program("ifconfig");

    exec("$ifconfig $Hooked_interface:ucarp 2>&1",$results);
    $ProductionIP_regex=str_replace(".", "\.", $ProductionIP);

    $available=false;
    foreach ($results as $num=>$line){
        if(preg_match("#$ProductionIP_regex#", $line)){
            $available=true;
            break;
        }
    }

    $LL[]="";
    $LL[]="URI: $uri";
    $LL[]= "Hooked Interface.: $Hooked_interface";
    $LL[]= "Main Interface...: $ProductionIP";
    $LL[]= "Bind Interface...: $WgetBindIpAddress";
    $LL[]= "Local available..: $available";
    $LL[]= "Must UP..........: $MustUp";

    if($GLOBALS["VERBOSE"]){
        echo @implode("\n", $LL);


    }

    if(!$available){
        if($MustUp){
            squid_admin_mysql(1, "FailOver: Master must be UP: Notify slave $IP to DOWN","Uri:$uri\nScript: /usr/share/ucarp/vip-$Hooked_interface-up.sh\n".@implode("\n", $LL),__FILE__,__LINE__);
            shell_exec("/usr/share/ucarp/vip-$Hooked_interface-up.sh");
            $curl=new ccurl($uri,true,$WgetBindIpAddress);
            $curl->parms["UCARP_DOWN"]=$ProductionIP;
            if(!$curl->get()){
                squid_admin_mysql(0, "FailOver: Unable to notify slave $IP for order [DOWN]","Error:$curl->error\n".
                    $uri.@implode("\n", $LL),__FILE__,__LINE__);}
            return;
        }
    }

    if($available){
        if($MustUp){
            $curl=new ccurl($uri,true,$WgetBindIpAddress);
            $curl->parms["UCARP_DOWN"]=$ProductionIP;
            if($GLOBALS["VERBOSE"]){echo "Notify slave down...\n";}
            if(!$curl->get()){squid_admin_mysql(0, "FailOver: Unable to notify slave $IP for order [DOWN]","Error:$curl->error\n".$uri.@implode("\n", $LL),__FILE__,__LINE__);}
            if(preg_match("#<RESULTS>(.*?)</RESULTS>#is", $curl->data,$re)){
                if($re[1]=="DOWN_OK"){squid_admin_mysql(1, "FailOver: Master is UP: slave $IP as been notified to be DOWN".@implode("\n", $LL),$uri,__FILE__,__LINE__);}
            }
            return;
        }
    }

    if($GLOBALS["VERBOSE"]){echo "Nothing to do....\n";}

}
function REBOOTING_SYSTEM(){
    $MonitConfig=watchdog_config_default();
    $unix=new unix();

    $timex=$unix->file_time_min($GLOBALS["STAMP_REBOOT"]);
    if($timex < $MonitConfig["REBOOT_INTERVAL"]){
        Events("Cannot reboot, need to wait {$MonitConfig["REBOOT_INTERVAL"]}mn, current is {$timex}mn");
        return;
    }


    squid_admin_mysql(0,"Reboot the system",null,  __FILE__, __LINE__);
    $shutdown=$unix->find_program("shutdown");
    @unlink($GLOBALS["STAMP_REBOOT"]);
    @file_put_contents($GLOBALS["STAMP_REBOOT"], time());
    sleep(5);
    shell_exec("$shutdown -rF now");

}

function CICAP_PID_PATH():string{
    return '/var/run/c-icap/c-icap.pid';
}

function CICAP_PID_NUM():int{
    $filename=CICAP_PID_PATH();
    $pid=trim(@file_get_contents($filename));
    $unix=new unix();
    if($unix->process_exists($pid)){return $pid;}
    return $unix->PIDOF($unix->find_program("c-icap"));
}

function reload_squid($aspid=false){
    $unix=new unix();
    $sock=new sockets();
    $squidbin=$unix->LOCATE_SQUID_BIN();
    $TimeFile="/etc/artica-postfix/pids/reloadsquid.time";
    $PidFile="/etc/artica-postfix/pids/reloadsquid.pid";


    if(!is_file($squidbin)){
        if($GLOBALS["OUTPUT"]){echo "Reloading.......: Proxy Service, not installed\n";}
        return;
    }




    if(!$aspid){

        $pid=$unix->get_pid_from_file($PidFile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $TimeMin=$unix->PROCCESS_TIME_MIN($pid);
            build_progress_reload("Already task running PID $pid since {$TimeMin}mn",100);
            return;
        }

    }
    @file_put_contents($PidFile, getmypid());

    $SquidCacheReloadTTL=$sock->GET_INFO("SquidCacheReloadTTL");
    if(!is_numeric($SquidCacheReloadTTL)){$SquidCacheReloadTTL=10;}

    $pid=SQUID_PID();
    if(!$unix->process_exists($pid)){start_squid(true);return;}
    $TimeMin=$unix->PROCCESS_TIME_MIN($pid);
    $php5=$unix->LOCATE_PHP5_BIN();
    echo "Reloading.......: ".date("H:i:s")." Building mime type..\n";
    system("/usr/sbin/artica-phpfpm-service -proxy-mimeconf");

    if($GLOBALS["FORCE"]){
        squid_admin_mysql(1, "Reload::{$GLOBALS["BY_OTHER_SCRIPT"]} Reloading + FORCE", null,__FILE__,__LINE__);
        echo "Reloading.....: ".date("H:i:s")." Proxy Service, Force enabled...\n";
    }
    $xtime=$unix->file_time_min($TimeFile);
    if(!$GLOBALS["FORCE"]){

        if($xtime<$SquidCacheReloadTTL){
            build_progress_reload("Aborted need at least {$SquidCacheReloadTTL}mn",100);
            echo "Reloading.......: ".date("H:i:s")." Proxy Service, Reload squid PID $pid aborted, need at least {$SquidCacheReloadTTL}mn current {$xtime}mn\n";
            return;
        }
    }



    @unlink($TimeFile);
    @file_put_contents($TimeFile, time());
    $suffix=get_action_script_source();

    build_progress_reload("Reloading PID $pid",10);
    echo "Reloading.....: ".date("H:i:s")." {reloading_proxy_service} PID:$pid running since {$TimeMin}Mn $suffix...\n";
    squid_admin_mysql(2, "{reloading} {APP_PROXY} (last reload was {$xtime}Mn) service by [{$GLOBALS["BY_OTHER_SCRIPT"]}] PID:$pid {running} {since} {$TimeMin}Mn $suffix",null,__FILE__,__LINE__);

    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $executed=null;


    $EnableTransparent27=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransparent27"));

    $SystemInfoCache="/etc/squid3/squid_get_system_info.db";

    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    $CicapEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"));
    if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}

    @unlink($SystemInfoCache);
    build_progress_reload("Reloading PID $pid",15);
    if(is_file("/etc/squid3/url_rewrite_program.deny.db")){ @unlink("/etc/squid3/url_rewrite_program.deny.db"); }
    $GLOBALS["SQUIDBIN"]=$unix->LOCATE_SQUID_BIN();
    echo "Reloading.....: ".date("H:i:s")." Proxy Service, Checking transparent mode..\n";

    build_progress_reload("Reloading PID $pid",20);

    build_progress_reload("Reloading PID $pid",25);
    if($EnableTransparent27==1){
        if(is_file("/etc/init.d/squid-nat")){
            echo "Reloading......: ".date("H:i:s")." Proxy Service, Reloading squid-nat\n";
            shell_exec("/etc/init.d/squid-nat reload");
        }

    }

    build_progress_reload("Reloading PID $pid",30);
    if($CicapEnabled==1){
        echo "Reloading......: ".date("H:i:s")." Proxy Service, Reloading C-ICAP service\n";
        $unix->CICAP_SERVICE_EVENTS("Reloading ICAP service",__FILE__,__LINE__);
        shell_exec("$nohup /etc/init.d/c-icap reload >/dev/null 2>&1 ");
    }
    build_progress_reload("Reloading PID $pid",35);

    if(!is_file($GLOBALS["SQUIDBIN"])){
        build_progress_reload("Reloading PID {failed}",100);
        return;
    }
    echo "Reloading.....: ".date("H:i:s")." Proxy Service, With binary {$GLOBALS["SQUIDBIN"]} PID $pid\n";
    build_progress_reload("Reloading PID $pid",45);
    echo "Reloading.....: ".date("H:i:s")." Proxy Service, Reloading artica-status\n";
    shell_exec("$nohup $php5 /etc/init.d/artica-status reload >/dev/null 2>&1 &");
    shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.clean.logs.php --squid-caches --force >/dev/null 2>&1 &");
    build_progress_reload("Reloading Main proxy service PID $pid",50);
    squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);

    $unix->go_exec("/usr/sbin/artica-phpfpm-service -reload-proxy");



    $EnableTransparent27=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransparent27"));
    if($EnableTransparent27==1){
        build_progress_reload("Reloading Proxy NAT service...",60);
        system("$nohup /etc/init.d/squid-nat reload --script=".basename(__FILE__)." >/dev/null 2>&1 &");
    }
    build_progress_reload("Reloading PID $pid",100);
}

function external_acl_children_more(){
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
        return false;
    }

    $SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));

    if(!is_numeric($SquidClientParams["external_acl_children"])){$SquidClientParams["external_acl_children"]=5;}
    if(!is_numeric($SquidClientParams["external_acl_startup"])){$SquidClientParams["external_acl_startup"]=1;}
    if(!is_numeric($SquidClientParams["external_acl_idle"])){$SquidClientParams["external_acl_idle"]=1;}


    $external_acl_children=$SquidClientParams["external_acl_children"];
    $external_acl_startup=$SquidClientParams["external_acl_startup"];
    $external_acl_idle=$SquidClientParams["external_acl_idle"];

    $external_acl_children=$external_acl_children+2;
    $external_acl_startup=$external_acl_startup+2;
    $external_acl_idle=$external_acl_idle+2;

    squid_admin_mysql(2, "ACL Children: from {$SquidClientParams["external_acl_children"]}/{$SquidClientParams["external_acl_startup"]}/{$SquidClientParams["external_acl_idle"]} to $external_acl_children/$external_acl_startup/$external_acl_idle","",__FILE__,__LINE__);
    $SquidClientParams["external_acl_children"]=$external_acl_children;
    $SquidClientParams["external_acl_startup"]=$external_acl_startup;
    $SquidClientParams["external_acl_idle"]=$external_acl_idle;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidClientParams", base64_encode(serialize($SquidClientParams)));
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");
    return true;
}

function TEST_ACTIVE_DIRECTORY(){
    $ADD=true;
    $watch=new squid_watchdog();
    $unix=new unix();
    if($unix->ServerRunSince()<3){return;}
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    $CHECK_AD=intval($watch->MonitConfig["CHECK_AD"]);
    if($EnableKerbAuth==0){$ADD=false;}
    if($CHECK_AD==0){$ADD=false;}

    $php5=$unix->LOCATE_PHP5_BIN();
    $nice=$unix->EXEC_NICE();

    if(!is_file("/etc/cron.d/artica-ping-cloud")){
        $f=array();
        $f[]="MAILTO=\"\"";
        $f[]="15 0,2,4,6,8,10,12,14,16,18,20,22 * * * *  root $nice $php5 /usr/share/artica-postfix/exec.web-community-filter.php --bycron >/dev/null 2>&1";
        $f[]="";
        @file_put_contents("/etc/cron.d/artica-ping-cloud", @implode("\n", $f));

    }


    if(!$ADD){
        if(is_file("/etc/cron.d/artica-ads-watchdog")){
            @unlink("/etc/cron.d/artica-ads-watchdog");
        }
        return;
    }


}




function PING_GATEWAY(){
    $sock=new sockets();
    $unix=new unix();
    $MonitConfig=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchdogMonitConfig")));
    $MonitConfig=PING_GATEWAY_DEFAULT_PARAMS($MonitConfig);

    if($MonitConfig["ENABLE_PING_GATEWAY"]==0){return;}
    if(!isset($MonitConfig["PING_GATEWAY"])){$MonitConfig["PING_GATEWAY"]=null;}
    $PING_GATEWAY=$MonitConfig["PING_GATEWAY"];

    if($PING_GATEWAY==null){
        $TCP_NICS_STATUS_ARRAY=$unix->NETWORK_ALL_INTERFACES();
        if(isset($TCP_NICS_STATUS_ARRAY["eth0"])){$PING_GATEWAY=$TCP_NICS_STATUS_ARRAY["eth0"]["GATEWAY"];}
        if($PING_GATEWAY==null){if(isset($TCP_NICS_STATUS_ARRAY["eth1"])){$PING_GATEWAY=$TCP_NICS_STATUS_ARRAY["eth1"]["GATEWAY"];}	}
    }

    if($PING_GATEWAY==null){Events("No IP address defined in the configuration, aborting test...");return;}
    if(!$unix->isIPAddress($PING_GATEWAY)){Events("\"$PING_GATEWAY\" not a valid ip address");return;}

    $STAMP_MAX_PING=intval(trim(@file_get_contents($GLOBALS["STAMP_MAX_PING"])));
    if(!is_numeric($STAMP_MAX_PING)){$STAMP_MAX_PING=1;}
    if($STAMP_MAX_PING<1){$STAMP_MAX_PING=1;}

    if($GLOBALS["VERBOSE"]){echo "PING $PING_GATEWAY STAMP_MAX_PING=$STAMP_MAX_PING\n";}

    if($unix->PingHost($PING_GATEWAY,true)){
        Events("PingHost($PING_GATEWAY -> TRUE OK");
        if($STAMP_MAX_PING>1){
            @file_put_contents($GLOBALS["STAMP_MAX_PING"], 1);
        }
        return;
    }
    Events("PingHost($PING_GATEWAY -> FALSE -> PING_FAILED_RELOAD_NET={$MonitConfig["PING_FAILED_RELOAD_NET"]}");
    if($MonitConfig["PING_FAILED_RELOAD_NET"]==1){
        squid_admin_mysql(1,"Reloading network Ping $PING_GATEWAY failed","Reloading network  $PING_GATEWAY:\nThe $PING_GATEWAY ping failed, Artica will restart network");
        $report=$unix->NETWORK_REPORT();
        ToSyslog("kernel: [  Artica-Net] Start Network [artica-ifup] (".basename(__FILE__)."/".__LINE__.")" );
        shell_exec("/usr/sbin/artica-phpfpm-service -restart-network --script=".basename(__FILE__)."/".__FUNCTION__);
        if($unix->PingHost($PING_GATEWAY,true)){
            squid_admin_mysql(2,"Relink network success","Relink network success after ping failed on $PING_GATEWAY:\nThe $PING_GATEWAY ping failed, Artica as restarted network and ping is now success.\nHere it is the network report when Ping failed\n$report");
            return;
        }

    }

    $MAX_PING_GATEWAY=$MonitConfig["MAX_PING_GATEWAY"];
    $STAMP_MAX_PING=$STAMP_MAX_PING+1;
    Events("$PING_GATEWAY not available - $STAMP_MAX_PING time(s) / $MAX_PING_GATEWAY Max");
    @file_put_contents($GLOBALS["STAMP_MAX_PING"], $STAMP_MAX_PING);
    if($STAMP_MAX_PING < $MAX_PING_GATEWAY){
        Events("$STAMP_MAX_PING < $MAX_PING_GATEWAY ABORTING...");
        return;}

    $UfdbguardSMTPNotifs=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbguardSMTPNotifs")));
    if(!isset($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
    if(!is_numeric($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]=0;}
    @file_put_contents($GLOBALS["STAMP_MAX_PING"], 1);


    if($MonitConfig["PING_FAILED_REPORT"]==1){
        $report=$unix->NETWORK_REPORT();
        squid_admin_mysql(1,"Unable to ping $PING_GATEWAY","$report");

    }

    if($MonitConfig["PING_FAILED_FAILOVER"]==1){
        $GLOBALS["ALL_SCORES_WHY"][]="function ".__FUNCTION__." return failed";
        $GLOBALS["ALL_SCORES"]++;
    }
    if($MonitConfig["PING_FAILED_REBOOT"]==1){
        REBOOTING_SYSTEM();
    }


}








function squidz($aspid=false){
    $unix=new unix();
    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            squid_admin_mysql(2, "restart_squid::Already task running PID $pid since {$time}mn", __FUNCTION__, __FILE__, __LINE__, "proxy");
            return;
        }
        @file_put_contents($pidfile, getmypid());
    }
    echo date("Y/m/d H:i:s")." Arti| Stopping Squid\n";
    echo date("Y/m/d H:i:s")." Arti| Please wait....\n";
    stop_squid(true);
    $squidbin=$unix->LOCATE_SQUID_BIN();
    $su_bin=$unix->find_program("su");
    $t1=time();



    exec("$su_bin squid -c \"$squidbin -z\" 2>&1",$results);
    echo date("Y/m/d H:i:s")." Arti| Checking caches `$squidbin`....Please wait\n";
    foreach ($results as $index=>$val){
        echo $val."\n";
    }


    $execnice=$unix->EXEC_NICE();
    $nohup=$unix->find_program("nohup");
    $chown=$unix->find_program("chown");
    $tail=$unix->find_program("tail");

    $GetCachesInsquidConf=$unix->SQUID_CACHE_FROM_SQUIDCONF();
    foreach ($GetCachesInsquidConf as $CacheDirectory=>$type){
        echo date("Y/m/d H:i:s")." Arti| Lauching a chown task in background mode on `$CacheDirectory`... this could take a while....\n";
        $unix->chmod_alldirs(0755, $CacheDirectory);
        $cmd="$execnice$nohup $chown -R squid:squid $CacheDirectory >/dev/null 2>&1 &";
        echo date("Y/m/d H:i:s")." Arti| $cmd\n";
        shell_exec($cmd);

    }

    echo date("Y/m/d H:i:s")." Arti| Starting squid....Please wait\n";
    start_squid(true);
    sleep(5);

    exec("$tail -n 100 /var/log/squid/cache.log 2>&1",$results2);
    foreach ($results2 as $index=>$val){
        echo $val."\n";}

    echo date("Y/m/d H:i:s")." Arti| Done...\n";
    echo date("Y/m/d H:i:s")." Arti| Took ". $unix->distanceOfTimeInWords($t1,time())."\n";
}


function CheckAvailableSize(){
    $unix=new unix();
    $GetCachesInsquidConf=$unix->SQUID_CACHE_FROM_SQUIDCONF();



    foreach ($GetCachesInsquidConf as $CacheDirectory=>$type){
        $free=$unix->DIR_STATUS($CacheDirectory);
        $POURC=$free["POURC"];
        $SIZE=round(($free["SIZE"]/1024));
        $MOUNTED=$free["MOUNTED"];



        if($GLOBALS["VERBOSE"]){
            echo "********\n$CacheDirectory Used:{$POURC}%\n********\nMonted on $MOUNTED\nSize: {$free["SIZE"]} {$SIZE}MB\n";
        }
        if($POURC>99){
            $GLOBALS["ALL_SCORES"]++;
            $GLOBALS["ALL_SCORES_WHY"][]="$CacheDirectory Used:{$POURC}% on $MOUNTED";
            squid_admin_mysql(0, "$CacheDirectory Used:{$POURC}% on $MOUNTED", "
					Partition on: $MOUNTED ( {$SIZE}M )
					You need to clean this cache to make free space",__FILE__,__LINE__);
        }


    }

}

function CheckAllports(){
    $unix=new unix();
    return $unix->SQUID_ALL_PORTS();

}






function get_action_script_source():string{
    $suffix="";
    if(!isset($GLOBALS["BY_WATCHDOG"])){$GLOBALS["BY_WATCHDOG"]=false;}
    if($GLOBALS["MONIT"]){$suffix=" (by system monitor)";}
    if($GLOBALS["CRASHED"]){$suffix= " ( after a crash !)";}
    if($GLOBALS["BY_CACHE_LOGS"]){$suffix= " ( ordered by logs monitor )";}
    if($GLOBALS["BY_STATUS"]){$suffix=" ( by Monitor )";}
    if($GLOBALS["BY_CLASS_UNIX"]){$suffix=" (by class.unix.inc)";}
    if($GLOBALS["BY_FRAMEWORK"]){$suffix=" (by Framework)";}
    if($GLOBALS["BY_WATCHDOG"]){$suffix=" (by Watchdog)";}

    if($GLOBALS["BY_ARTICA_INSTALL"]){$suffix=" (by artica-install)";}
    if($GLOBALS["BY_FORCE_RECONFIGURE"]){$suffix=" (after {building_settings})";}
    if($GLOBALS["BY_RESET_CACHES"]){$suffix=" (after reset caches)";}
    if(strlen($GLOBALS["BY_OTHER_SCRIPT"])>2){$suffix=" (by other script {$GLOBALS["BY_OTHER_SCRIPT"]})";}
    if($GLOBALS["KILL_ALL"]){$suffix=" - Force Kill - $suffix";}
    return $suffix;
}

function zwriteprogress($perc,$text){
    if(!$GLOBALS["WIZARD"]){return;}
    $PROGRESS_FILE=PROGRESS_DIR."/wizard.progress";
    $array["POURC"]=82;
    $array["TEXT"]="({$perc}%) $text";
    @file_put_contents($PROGRESS_FILE, serialize($array));
    @chmod($PROGRESS_FILE,0755);

}


function restart_squid($aspid=false){
    echo "Restart.......: ".date("H:i:s")." Proxy Service....\n";
    $unix=new unix();
    build_progress_restart("{please_wait}", 10);
    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            echo "Restart.......: ".date("H:i:s")." Already task running PID $pid since {$time}mn, aborting...\n";
            squid_admin_mysql(2, "restart_squid::Already task running PID $pid since {$time}mn",  __FILE__, __LINE__);
            build_progress_restart("{failed}: Already task running PID $pid since {$time}mn", 110);
            return;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $squidbin=$unix->LOCATE_SQUID_BIN();
    if(!is_file($squidbin)){
        build_progress_restart("{failed}", 110);
        $time=$unix->PROCCESS_TIME_MIN($pid);
        echo "Restart.......: ".date("H:i:s")." Already `task` running PID $pid since {$time}mn\n";
        return;
    }


    $t1=time();
    echo "Restart.......: ".date("H:i:s")." Restarting Squid-cache...\n";

    $suffix=null;
    $reconfigure=null;
    $suffix=get_action_script_source();


    if($GLOBALS["RECONFIGURE"]){$reconfigure=" - with reconfigure";}

    build_progress_restart("{stopping_service}", 20);
    stop_squid(true);
    $date=date("Y-m-d H:i:s");
    squid_admin_mysql(1, "Restarting Squid-Cache service: $suffix$reconfigure",
        "$suffix - $date\n a process ask to restart it\n",__FILE__,__LINE__);



    $php5=$unix->LOCATE_PHP5_BIN();
    if($GLOBALS["RECONFIGURE"]){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Reconfiguring Squid-cache...\n";}
        build_progress_restart("{building_parameters}", 30);
        system("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
    }

    if($GLOBALS["OUTPUT"]){echo "Restart.......: ".date("H:i:s")." Stopping Squid...\n";}



    if($GLOBALS["SWAPSTATE"]){
        $GLOBALS["FORCE"]=true;
        swap_state();
    }
    if($GLOBALS["OUTPUT"]){echo "Restart.......: Starting Squid...\n";}
    build_progress_restart("{starting_service} L".__LINE__, 40);
    start_squid(true);


    $took=$unix->distanceOfTimeInWords($t1,time());
    $EnableTransparent27=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransparent27"));
    if($EnableTransparent27==1){
        if(is_file("/etc/init.d/squid-nat")){
            build_progress_restart("{restart_cache_nat}", 60);
            if($GLOBALS["OUTPUT"]){echo "Restart.......: Restarting Cache NAT\n";}
            shell_exec("/etc/init.d/squid-nat restart --force 2>&1 >> /usr/share/artica-postfix/ressources/logs/web/restart.squid");
        }
    }


    build_progress_restart("{starting_service} {done}", 100);
    squid_admin_mysql(2, "{APP_PROXY} {restarted} {took}: $took",  __FILE__, __LINE__);
    return true;

}

function DeletedCaches(){
    $unix=new unix();
    $dirs=$unix->dirdir("/home/squid");
    $rm=$unix->find_program("rm");
    foreach ($dirs as $CacheDirectory=>$type){
        if(!preg_match("#-delete-[0-9]+#", $CacheDirectory)){continue;}
        Events("Found an old cache: $CacheDirectory");
        shell_exec("$rm -rf $CacheDirectory");
    }

}

function Checks_Winbindd(){
    $sock=new sockets();
    $unix=new unix();
    $chmod=$unix->find_program("chmod");
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));

    if(!is_file("/etc/init.d/winbind")){return;}

    if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
    if($EnableKerbAuth==0){return;}

    if(winbind_is_run()){
        Events("Winbind OK pid:{$GLOBALS["WINBINDPID"]}...");
        return;}
    squid_admin_mysql(2, "Winbindd not running, start it...", __FILE__, __LINE__);
    Events("Start Winbind...");
    $php=$unix->LOCATE_PHP5_BIN();
    exec("$php /usr/share/artica-postfix/exec.winbindd.php --start 2>&1",$results);


    if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
    squid_admin_mysql(1,"Winbindd service was started",@implode("\n", $results)."\n$executed");



    Events(@implode("\n", $results));

    if(!winbind_is_run()){ $GLOBALS["ALL_SCORES_WHY"][]="function ".__FUNCTION__." return failed";$GLOBALS["ALL_SCORES"]++; }

}

function winbind_is_run(){
    $GLOBALS["WINBINDPID"]=0;
    $pidfile="/var/run/samba/winbindd.pid";
    $unix=new unix();
    $GLOBALS["WINBINDPID"]=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($GLOBALS["WINBINDPID"])){return true;}
    $winbindbin=$unix->find_program("winbindd");
    $GLOBALS["WINBINDPID"]=$unix->PIDOF($winbindbin);
    if($unix->process_exists($GLOBALS["WINBINDPID"])){return true;}

    return false;

}

function Events($text){

    if(function_exists("debug_backtrace")){
        $trace=debug_backtrace();
        if(isset($trace[1])){
            $sourcefile=basename($trace[1]["file"]);
            $sourcefunction=$trace[1]["function"];
            $sourceline=$trace[1]["line"];
        }

    }

    $unix=new unix();
    $unix->ToSyslog($text,false,"squid-watchdog");
    $unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
}

function PROXY_TESTS_PORTS_EVENTS($text){
    if(function_exists("debug_backtrace")){
        $trace=debug_backtrace();
        if(isset($trace[1])){
            $sourcefile=basename($trace[1]["file"]);
            $sourcefunction=$trace[1]["function"];
            $sourceline=$trace[1]["line"];
        }

    }
    $unix=new unix();
    $unix->events($text,"/var/log/artica.proxy.watchdog.test.ports.log",false,$sourcefunction,$sourceline);
}

function ChecksInstances(){
    $unix=new unix();
    $pidof=$unix->find_program("pidof");



}

function CleanMemBoosters(){
    return;
}


function MemBoosters(){

    if(count(SwapesList())==0){
        if($GLOBALS["VERBOSE"]){echo "\n******** SWAP (none - no swap ) *******\n";}
        return;
    }

    if($GLOBALS["VERBOSE"]){echo "Membooster (Verbose) \n";}
    $swapiness=intval(trim(@file_get_contents("/proc/sys/vm/swappiness")));
    if($GLOBALS["VERBOSE"]){echo "SWAPINESS = {$swapiness}%\n";}

    if($swapiness>5){
        squid_admin_mysql(2,"Swapiness set to 5%","The SWAPINESS was {$swapiness}%:\nIt will be modified to 5% for MemBoosters",__FILE__,__LINE__);
        @file_put_contents("/proc/sys/vm/swappiness", "5");
    }
}

function FailOverParams(){


    if(isset($GLOBALS["FailOverParams"])){return $GLOBALS["FailOverParams"];}

    $FailOverArticaParams=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FailOverArticaParams")));
    if(!isset($FailOverArticaParams["ExternalPageToCheck"])){$FailOverArticaParams["ExternalPageToCheck"]=1;}
    if(!isset($FailOverArticaParams["squid-internal-mgr-info"])){$FailOverArticaParams["squid-internal-mgr-info"]=1;}

    if(!is_numeric($FailOverArticaParams["squid-internal-mgr-info"])){$FailOverArticaParams["squid-internal-mgr-info"]=1;}
    if(!is_numeric($FailOverArticaParams["ExternalPageToCheck"])){$FailOverArticaParams["ExternalPageToCheck"]=1;}
    $GLOBALS["FailOverParams"]=$FailOverArticaParams;
    return $GLOBALS["FailOverParams"];
}

function RESTARTING_SQUID_WHY($MonitConfig,$explain){

    if(!is_array($MonitConfig)){
        $MonitConfig=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchdogMonitConfig")));
        $MonitConfig=watchdog_config_default();

    }

    $unix=new unix();
    $unix->IncidentSquid("Proxy Service: $explain");

    Events($explain, __FUNCTION__, __FILE__, __LINE__, "proxy");
    $MAX_RESTART=$MonitConfig["MAX_RESTART"];
    if(!is_numeric($MAX_RESTART)){$MAX_RESTART=2;}
    $STAMP_MAX_RESTART=STAMP_MAX_RESTART_GET();
    if($STAMP_MAX_RESTART >= $MAX_RESTART){
        Events("Restarting Squid aborted, max $MAX_RESTART restarts has already been made (waiting Squid restart correctly to return back to 0)...");
        return;
    }

    $SquidCacheReloadTTL=$MonitConfig["SquidCacheReloadTTL"];
    $unix=new unix();
    $timex=$unix->file_time_min($GLOBALS["STAMP_MAX_RESTART_TTL"]);
    if($timex<$SquidCacheReloadTTL){return;}
    @unlink($GLOBALS["STAMP_MAX_RESTART_TTL"]);
    @file_put_contents($GLOBALS["STAMP_MAX_RESTART_TTL"], time());

    STAMP_MAX_RESTART_SET();
    $STAMP_MAX_RESTART++;
    if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}

    system_admin_events($explain, __FUNCTION__, __FILE__, __LINE__, "proxy");
    Events("Restarting squid Max restarts: $STAMP_MAX_RESTART/$MAX_RESTART");

    if(!isset($GLOBALS["RESTART_SQUID_WHY_EVTS"])){$GLOBALS["RESTART_SQUID_WHY_EVTS"]=array();}
    if(!is_array($GLOBALS["RESTART_SQUID_WHY_EVTS"])){$GLOBALS["RESTART_SQUID_WHY_EVTS"]=array();}

    $infos=@implode("\n", $GLOBALS["RESTART_SQUID_WHY_EVTS"]);

    squid_admin_mysql(1,"Ask to restart Squid-cache: $sourcefunction"
        ,"Restarting squid Max restarts: $STAMP_MAX_RESTART/$MAX_RESTART\n$explain\n$infos",__FILE__,__LINE__);
    $GLOBALS["BY_WATCHDOG"]=true;
    restart_squid(true);
}


function STAMP_MAX_RESTART_GET():int{
    $STAMP_MAX_RESTART=@file_get_contents($GLOBALS["STAMP_MAX_RESTART"]);
    if(!is_numeric($STAMP_MAX_RESTART)){$STAMP_MAX_RESTART=0;}
    return $STAMP_MAX_RESTART;
}
function STAMP_MAX_RESTART_SET():bool{
    $STAMP_MAX_RESTART=STAMP_MAX_RESTART_GET();
    $STAMP_MAX_RESTART++;
    @file_put_contents($GLOBALS["STAMP_MAX_RESTART"], $STAMP_MAX_RESTART);
    return true;
}
function STAMP_MAX_RESTART_RESET():bool{
    @file_put_contents($GLOBALS["STAMP_MAX_RESTART"], 0);
    return true;
}








function Checks_external_webpage($MonitConfig){
    include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
    $sock=new sockets();
    $StartTime=time();
    $unix=new unix();
    $tcp=new networking();

    if(!is_array($MonitConfig)){
        $sock=new sockets();
        $MonitConfig=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchdogMonitConfig")));
    }
    $MonitConfig=watchdog_config_default($MonitConfig);
    if($MonitConfig["TestExternalWebPage"]==0){return;}
    $FailOverArticaParams=FailOverParams();


    $ALL_IPS_GET_ARRAY=$tcp->ALL_IPS_GET_ARRAY();
    unset($ALL_IPS_GET_ARRAY["127.0.0.1"]);
    foreach ($ALL_IPS_GET_ARRAY as $index=>$val){$IPZ[]=$index;}
    $IPZ_COUNT=count($IPZ);
    if($IPZ_COUNT==1){$choosennet=$IPZ[0];}else{$choosennet=$IPZ[rand(0,$IPZ_COUNT-1)];}

    $uri=$MonitConfig["ExternalPageToCheck"];
    if($MonitConfig["ExternalPageListen"]=="127.0.0.1"){$MonitConfig["ExternalPageListen"]=null;}
    if($MonitConfig["ExternalPageListen"]==null){$MonitConfig["ExternalPageListen"]=$choosennet;}

    if($GLOBALS["VERBOSE"]){echo "Checks_external_webpage(): choosennet=$choosennet({$MonitConfig["ExternalPageListen"]})\n";}

    $URLAR=parse_url($uri);
    if(isset($URLAR["host"])){$sitename=$URLAR["host"];}
    $ipClass=new IP();
    if(!$ipClass->isValid($sitename)){
        $ip=gethostbyname($sitename);
        if(!$ipClass->isValid($ip)){
            squid_admin_mysql(0, "Unable to resolve $sitename from $uri",
                "It seems the server is unable to resolve $uri");
            return;
        }
    }else{
        $ip=$sitename;
    }




    $uri=str_replace("%T", time(), $uri);
    $http_port=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
    $SquidBinIpaddr="127.0.0.1";
    if(!is_numeric($http_port)){
        $http_port=squid_get_alternate_port();
        $SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
        if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}

        if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
            $SquidBinIpaddr=$re[1];
            if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
            $http_port=$re[2];
        }

    }

    $curl=new ccurl($uri,true);

    $t0=time();

    $curl->ArticaProxyServerEnabled="yes";
    $curl->ArticaProxyServerName=$SquidBinIpaddr;
    $curl->interface="127.0.0.1";

    $GLOBALS["RESTART_SQUID_WHY_EVTS"][]="Local interface: $curl->interface\n";


    if($MonitConfig["ExternalPageUsername"]<>null){
        $curl->interface=$MonitConfig["ExternalPageListen"];
        $curl->ArticaProxyServerUsername=$MonitConfig["ExternalPageUsername"];
        $curl->ArticaProxyServerUserPassword=$MonitConfig["ExternalPagePassword"];
    }

    if($GLOBALS["VERBOSE"]){
        echo "{$uri}:Using SQUID + $curl->interface/{$MonitConfig["ExternalPageListen"]} -> $curl->ArticaProxyServerUsername@$curl->ArticaProxyServerName\n";
    }
    $curl->ArticaProxyServerPort=$http_port;
    $curl->NoHTTP_POST=true;
    $curl->Timeout=$MonitConfig["MgrInfosMaxTimeOut"];

    $GLOBALS["RESTART_SQUID_WHY_EVTS"][]="Connection to: $SquidBinIpaddr:$http_port";

    if(!$curl->get()){
        $took=$unix->distanceOfTimeInWords($StartTime,time(),true);


        if( ($curl->CURLINFO_HTTP_CODE==403)  OR ($curl->CURLINFO_HTTP_CODE==407) ){

            foreach ($curl->CURL_ALL_INFOS as $index=>$val){
                if($GLOBALS["VERBOSE"]){echo "$index: $val\n";}
                $tr[]="$index: $val";
            }
            return;
        }

        $GLOBALS["RESTART_SQUID_WHY_EVTS"][]="Task took: $took";
        $GLOBALS["RESTART_SQUID_WHY_EVTS"][]="CURLINFO_HTTP_CODE:: $curl->CURLINFO_HTTP_CODE";
        $GLOBALS["ALL_SCORES_WHY"][]="function ".__FUNCTION__." return failed";
        $GLOBALS["ALL_SCORES"]++;
        foreach ($curl->CURL_ALL_INFOS as $index=>$val){
            if($GLOBALS["VERBOSE"]){echo "$index: $val\n";}
            $GLOBALS["RESTART_SQUID_WHY_EVTS"][]="$index: $val";
        }

        if($GLOBALS["VERBOSE"]){echo "CURL8ERR:".$curl->error."\n\n";}
        if(preg_match("#407 Proxy Authentication#i",$curl->error)){
            Events("Watchdog receive authentication, this is not expected for $uri !");
            return;
        }

        if($GLOBALS["VERBOSE"]){echo $curl->data;}
        RESTARTING_SQUID_WHY($MonitConfig,
            "Unable to download \"$uri\" from Interface:$curl->interface with error `$curl->error`");


        return;
    }

    if($GLOBALS["VERBOSE"]){echo "***** SUCCESS *****\n";}
    if($GLOBALS["VERBOSE"]){echo $curl->data;}

    $datas=$curl->data;
    $length=strlen($datas);
    $unit="bytes";
    if($length>1024){$length=$length/1024;$unit="Ko";}
    if($length>1024){$length=$length/1024;$unit="Mo";}
    $length=round($length,2);
    STAMP_MAX_RESTART_RESET();
    Events("Success Internet should be available webpage length:{$length}$unit Took:".$unix->distanceOfTimeInWords($t0,time(),true));

}

function SQUID_PID(){
    $unix=new unix();
    return $unix->SQUID_PID();
}

function ToSyslog($text){
    Events("$text");
    if($GLOBALS["VERBOSE"]){echo $text."\n";}
    if(!function_exists("syslog")){return;}
    $file=basename(__FILE__);
    $LOG_SEV=LOG_INFO;
    openlog($file, LOG_PID , LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
}



function start_squid($aspid=false){
    $GLOBALS["LOGS"]=array();
    $suffix=null;
    if($GLOBALS["MONIT"]){$suffix=" (by system monitor)";}
    if($GLOBALS["BY_CACHE_LOGS"]){$suffix=" (by cache.log monitor)";}
    if($GLOBALS["BY_STATUS"]){$suffix=" (by Artica monitor)";}
    if($GLOBALS["BY_CLASS_UNIX"]){$suffix=" (by Artica class.unix.inc)";}
    if($GLOBALS["BY_FRAMEWORK"]){$suffix=" (by Artica framework)";}
    if($GLOBALS["BY_OTHER_SCRIPT"]){$suffix=" (by other script)";}
    if($GLOBALS["BY_ARTICA_INSTALL"]){$suffix=" (by artica-install)";}
    if($GLOBALS["BY_FORCE_RECONFIGURE"]){$suffix=" (after {building_settings})";}


    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $sock=new sockets();
    $SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
    $NtpdateAD=$sock->GET_INFO("NtpdateAD");
    if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
    if(!is_numeric($NtpdateAD)){$NtpdateAD=0;}
    $squidbin=$unix->LOCATE_SQUID_BIN();
    if(!is_file($squidbin)){
        build_progress_start("Not installed",110);
        if($GLOBALS["OUTPUT"]){echo "Restart......: Proxy Service, not installed\n";}
        return false;
    }



    if($GLOBALS["MONIT"]){

        $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
        if($SQUIDEnable==0){
            if(is_file("/etc/monit/conf.d/APP_SQUID.monitrc")){
                @unlink("/etc/monit/conf.d/APP_SQUID.monitrc");
                shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");

            }
            return false;
        }

        if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];$called="Called by $function() from line $line";}}
        $pid=SQUID_PID();
        if($unix->process_exists($pid)){
            $ps=$unix->find_program("ps");
            $grep=$unix->find_program("grep");
            exec("$ps aux|$grep squid 2>&1",$results);
            squid_admin_mysql(1, "{APP_MONIT} ordered to start {APP_PROXY} but is still in memory PID $pid ??",
                "I cannot accept this order, see details\n".@implode("\n", $results)
                ,__FILE__,__LINE__);
            return false;
        }



        squid_admin_mysql(0, "Monit ordered to start Proxy service",$called,__FILE__,__LINE__);
    }




    if($SQUIDEnable==0){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Squid is disabled...\n";}
        build_progress_start("Proxy service is disabled",110);
        return false;
    }



    if(is_file("/etc/artica-postfix/squid.lock")){
        $time=$unix->file_time_min("/etc/artica-postfix/squid.lock");
        if($time<15){
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Proxy is locked (since {$time}Mn ) ...\n";}
            build_progress_start(" Proxy is locked (since {$time}Mn",110);
            return false;
        }
        @unlink("/etc/artica-postfix/squid.lock");
    }


    $pids=$unix->PIDOF_PATTERN_ALL("exec.squid.watchdog.php --start");
    if(count($pids)>2){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Too many instances ". count($pids)." starting squid, kill them!\n";}
        $mypid=getmypid();
        foreach ($pids as $pid=>$ligne){
            if($pid==$mypid){continue;}
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." killing $pid\n";}
            unix_system_kill_force($pid);
        }

    }

    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);

            if($time<5){
                build_progress_start("Task Already running PID $pid since {$time}mn",110);
                Events("Task Already running PID $pid since {$time}mn");
                if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." Already task running PID $pid since {$time}mn, Aborting operation (".__LINE__.")\n";}
                return false;
            }
            squid_admin_mysql(0,"Too long time for artica task PID $pid {running} {since} {$time}mn", "Process will be killed");
            Tosyslog("Too long time for artica task PID $pid running since {$time}mn -> kill");
            unix_system_kill_force($pid);
        }
        @file_put_contents($pidfile, getmypid());
    }

    $squidbin=$unix->find_program("squid");
    if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
    if(!is_file($squidbin)){
        build_progress_start("Not installed",110);
        squid_admin_mysql(2, "{APP_PROXY} not seems to be installed",
            __FUNCTION__, __FILE__, __LINE__, "proxy");
        return false;
    }

    $pid=SQUID_PID();
    if($unix->process_exists($pid)){
        SendLogs("Already pid $pid running, aborting\n");
        return true;
    }

    if(!$unix->SystemUserExists("squid")){
        $unix->SystemCreateUser("squid","squid",null,null);
    }

    @chmod($squidbin,0755);

    if(!is_file("/etc/cron.d/artica-squid-5min")){
        $unix->Popuplate_cron_make("artica-squid-5min","*/6 * * * *","exec.squidMins.php");
        UNIX_RESTART_CRON();
    }


    build_progress_start("Preparing proxy service",50);
    $squid_checks=new squid_checks();
    $squid_checks->squid_parse();
    build_progress_start("{starting_proxy_service}",60);
    $pid=SQUID_PID();
    if($GLOBALS["CRASHED"]){
        for($i=0;$i<10;$i++){
            sleep(1);
            $pid=SQUID_PID();
            if($unix->process_exists($pid)){continue;}
            break;
        }

        squid_admin_mysql(2,"No need to start Proxy service after a crash",
            "It seems the watchdog detect a crash but after 10s the proxy still running\nOperation is aborted",__FILE__,__LINE__);
        return false;

    }




    $t1=time();
    build_progress_start("Checking caches",71);
    SendLogs("Checking caches...");
    $cacheBooster=new squidbooster();
    $cacheBooster->cache_booster();

    build_progress_start("Checking caches",73);

    SendLogs("Checking caches done...");

    build_progress_start("Checking Ports",75);
    SendLogs("Checking Ports...");
    $array=CheckAllports();
    SendLogs("Checking ". count($array) ." ports");

    foreach ($array as $port=>$ligne){
        $portZ=$unix->PIDOF_BY_PORT($port);
        SendLogs("Checking port $port - ". count($portZ) ." process(es)");
        if(count($portZ)>0){
            foreach ($portZ as $pid=>$ligne){
                SendLogs("Checking port $port - killing pid $pid");
                shell_exec("kill -9 $pid >/dev/null 2>&1");
            }
        }
    }

    build_progress_start("Checking SHM",75);

    SendLogs("Starting squid $squidbin....");
    $echo=$unix->find_program("echo");
    $size=round(@filesize("/var/log/squid/cache.log")/1024,2)/1024;
    if($size>50){
        squid_admin_mysql(2, "Cleaning cache.log {$size}MB", null,__FILE__,__LINE__);
        @copy("/var/log/squid/cache.log", "/var/log/squid/cache.log.".time());
        shell_exec("$echo \" \"> /var/log/squid/cache.log 2>&1");
    }
    @chmod($squidbin,0755);
    @chmod("/var/log/squid",0755);
    if(is_link("/var/log/squid")){ @chmod(readlink("/var/log/squid"),0755); }
    squid_admin_mysql(1,"{starting_proxy_service} $suffix",@implode("\n", $GLOBALS["LOGS"]),__FILE__,__LINE__);
    $GLOBALS["LOGS"]=$unix->go_exec_out("/usr/sbin/artica-phpfpm-service -start-proxy");

    $PRC=40;
    $MAXPRC=60;
    $AB=0;
    $TESTFAILED=false;

    foreach ($GLOBALS["LOGS"] as $line){
        if(preg_match("#FATAL: Bungled#", $line)){
            squid_admin_mysql(1,"Alert: Bungled configuration when starting Proxy",$line,__FILE__,__LINE__);
            $TESTFAILED=true;
            break;
        }


    }
    if($TESTFAILED){
        $TESTFAILED=false;

        if(!is_file("/etc/artica-postfix/SQUID_TEST_FAILED")){
            build_progress_start("Reconfigure Proxy service",80);
            system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
        }

        $GLOBALS["LOGS"]=$unix->go_exec_out("$squidbin -f /etc/squid3/squid.conf");
        foreach ($GLOBALS["LOGS"] as $line){
            if(preg_match("#FATAL: Bungled#", $line)){
                squid_admin_mysql(1,"Alert: Bungled configuration after reconfiguring Proxy",
                    $line,__FILE__,__LINE__);
                $TESTFAILED=true;
                break;
            }
        }
    }

    if($TESTFAILED){
        @touch("/etc/artica-postfix/SQUID_TEST_FAILED");
        build_progress_start("Start Proxy service {failed}",110);
        exit();
    }



    @unlink("/etc/artica-postfix/SQUID_TEST_FAILED");


    for($i=0;$i<10;$i++){
        $PRC++;
        if($PRC>$MAXPRC-1){$PRC=$MAXPRC-1;}
        build_progress_start("{starting_service} $i/10",85);
        build_progress_restart("{starting_service} $i/10 L".__LINE__, $PRC);
        $pid=SQUID_PID();
        if($unix->process_exists($pid)){SendLogs("Starting squid started pid $pid...");break;}
        ToSyslog("Starting squid waiting $i/10s");
        SendLogs("Starting squid waiting $i/10s");
        sleep(1);
    }

    if(!$unix->process_exists($pid)){
        build_progress_start("{failed}",110);
        SendLogs("Starting Squid failed to start...");
        ToSyslog("Starting Squid failed to start...");
        if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
        squid_admin_mysql(0,"{APP_PROXY} failed to start $suffix",@implode("\n", $GLOBALS["LOGS"])."\n$executed");
        build_progress_restart("{starting_service} {failed}", 110);
        return false;
    }

    SendLogs("Starting Squid Tests if it listen all connections....");
    for($i=0;$i<10;$i++){
        $PRC++;
        build_progress_restart("{starting_service} $i/10", $PRC);
        build_progress_start("{checking} $i/10",90);
        if(is_started()){SendLogs("Starting squid listen All connections OK");break;}
        SendLogs("Starting squid listen All connections... waiting $i/10");
        sleep(1);
    }

    $took=$unix->distanceOfTimeInWords($t1,time());

    SendLogs("Starting Squid success to start PID $pid...");
    if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
    taskset();
    build_progress_restart("{starting_service}", $PRC++);
    build_progress_start("Restarting cache-tail",91);
    $unix->go_exec("/etc/init.d/cache-tail restart");
    build_progress_restart("{starting_service}", $PRC++);
    build_progress_start("Restarting access-tail",92);
    $unix->go_exec("/etc/init.d/squid-tail restart");
    build_progress_restart("{starting_service}", $PRC++);
    build_progress_restart("{starting_proxy_service}", $PRC++);
    build_progress_start("{done}",100);
    build_progress_restart("{starting_proxy_service} {success}",100);
    squid_admin_mysql(2, "{starting_proxy_service} {success} PID $pid {took} $took",@implode("\n", $GLOBALS["LOGS"]),  __FILE__, __LINE__);
    SendLogs("Starting Squid done...");
    if(is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");}

}
function is_started(){


    $unix=new unix();
    $tail=$unix->find_program("tail");
    $tempfile=$unix->FILE_TEMP();
    system("$tail -n 1500 /var/log/squid/cache.log >$tempfile 2>&1");
    $f=file($tempfile);
    krsort($f);
    @unlink($tempfile);
    foreach ($f as $val){
        if(preg_match("#Accepting HTTP Socket connections#i", $val)){
            SendLogs("Detected:$val...");
            return true;}

    }

    return false;

}

function is_kerberos(){

    $f=explode("\n",@file_get_contents("/etc/squid3/authenticate.conf"));
    foreach ($f as $line){
        if(preg_match("#auth_param negotiate program.*?kerberos#",$line)){return true;}
    }
    return false;

}


function filedescriptors(){}

function kerberos_auth_tests(){
    $unix=new unix();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KERBEROS_AUTH_ERR","");
    return true;
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
    if(!is_file("/lib/squid3/negotiate_kerberos_auth_test")){return false;}
    if(!is_kerberos()){
        echo "Not a kerberos configuration...\n";
        return false;}
    $hostname=gethostname();
    $cmd="/lib/squid3/negotiate_kerberos_auth_test $hostname 2>&1";
    if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
    exec($cmd,$results);
    foreach ($results as $line) {
        $line=trim($line);
        if($line==null){continue;}
        if($GLOBALS["VERBOSE"]){echo $line."\n";}
        if(preg_match("#^(.+?)\|(.+)#",$line,$re)){
            $t[]=$re[2];
        }
        if(preg_match("#^Token:\s+(.+)#",$line,$re)){
            if($GLOBALS["VERBOSE"]){echo "TOKEN == <{$re[1]}>\n";}
            if(trim($re[1])=="NULL"){$re[1]=null;}
            $ticket=trim($re[1]);
            break;
        }
    }
    if($GLOBALS["VERBOSE"]){echo "ticket===$ticket\n";}
    if($ticket==null){
        $filetime=$unix->file_time_min($pidfile);
        if($filetime>5){
            $subj=null;
            $text=@implode("\n",$t);
            if(preg_match("#Unspecified GSS failure#",$text)){$subj="Unspecified GSS failure";}
            squid_admin_mysql(0,"Kerberos authentication failed $subj (see content)",$text,__FILE__,__LINE__);
        }

        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KERBEROS_AUTH_ERR",@implode("<br>",$t));
        return false;
    }
    return true;
}



function stop_squid_analyze($array){
    foreach ($array as $num=>$ligne){
        if(preg_match("#is a subnetwork of#i", $ligne)){continue;}
        if(preg_match("#is ignored to keep splay tree#i", $ligne)){continue;}
        if(preg_match("#You should probably remove#i", $ligne)){continue;}
        if(preg_match("#Warning: empty ACL#i", $ligne)){continue;}


        if(preg_match("#No running copy#i", $ligne)){
            SendLogs("Stopping Squid-Cache service \"$ligne\" [anaylyze]");
            return true;
        }

        if(preg_match("#Illegal instruction#i", $ligne)){
            SendLogs("Stopping Squid-Cache service \"$ligne\"");
            return true;
        }

        if(preg_match("#ERROR: Could not send signal [0-9]+ to process [0-9]+.*?No such process#", $ligne)){
            if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service \"$ligne\"\n";}
            return true;
        }

        SendLogs("Stopping Squid-Cache service \"$ligne\"");
    }
    return false;
}


function stop_squid($aspid=false){


    if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];$called="Called by $function() from line $line";}}
    $GLOBALS["LOGS"]=array();
    $unix=new unix();
    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")."Already `task` running PID $pid since {$time}mn\n";}
            return;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $MonitConfig=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchdogMonitConfig")));
    $MonitConfig=watchdog_config_default($MonitConfig);
    $STOP_SQUID_TIMEOUT=$MonitConfig["StopMaxTTL"];
    if(!isset($MonitConfig["STOP_SQUID_MAXTTL_DAEMON"])){$MonitConfig["STOP_SQUID_MAXTTL_DAEMON"]=5;}
    $STOP_SQUID_MAXTTL_DAEMON=$MonitConfig["STOP_SQUID_MAXTTL_DAEMON"];
    $SquidCachesProxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled"));
    $monit=$unix->find_program("monit");

    if(!is_numeric($STOP_SQUID_TIMEOUT)){$STOP_SQUID_TIMEOUT=60;}
    if(!is_numeric($STOP_SQUID_MAXTTL_DAEMON)){$STOP_SQUID_MAXTTL_DAEMON=5;}
    if($STOP_SQUID_TIMEOUT<5){$STOP_SQUID_TIMEOUT=5;}


    $squidbin=$unix->find_program("squid");


    if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
    if(!is_file($squidbin)){
        squid_admin_mysql(2, "{APP_PROXY} not seems to be installed", __FUNCTION__, __FILE__, __LINE__, "proxy");
        return;
    }
    $suffix=" (by unknown process)";
    if($GLOBALS["MONIT"]){$suffix=" (by system monitor)";}
    if($GLOBALS["CRASHED"]){$suffix= " ( after a crash )";}
    if($GLOBALS["BY_CACHE_LOGS"]){$suffix= " ( ordered by logs monitor )";}
    if($GLOBALS["BY_STATUS"]){$suffix=" ( by Artica monitor )";}
    if($GLOBALS["BY_CLASS_UNIX"]){$suffix=" (by Artica class.unix.inc)";}
    if($GLOBALS["BY_FRAMEWORK"]){$suffix=" (by Artica framework)";}
    if($GLOBALS["BY_OTHER_SCRIPT"]){$suffix=" (by other script)";}
    if($GLOBALS["BY_ARTICA_INSTALL"]){$suffix=" (by artica-install)";}
    if($GLOBALS["BY_FORCE_RECONFIGURE"]){$suffix=" (after {building_settings})";}

    if($GLOBALS["MONIT"]){
        if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];$called="Called by $function() from line $line";}}
        $pid=SQUID_PID();
        if($unix->process_exists($pid)){
            $ps=$unix->find_program("ps");
            $grep=$unix->find_program("grep");
            exec("$ps aux|$grep squid 2>&1",$results);
            return;
        }
        squid_admin_mysql(2, "Monit ordered to stop squid",$called);

    }

    if($GLOBALS["BY_ARTICA_INSTALL"]){
        $pid=SQUID_PID();
        if($unix->process_exists($pid)){
            $ps=$unix->find_program("ps");
            $grep=$unix->find_program("grep");
            exec("$ps aux|$grep squid 2>&1",$results);
            return;
        }
        squid_admin_mysql(2, "artica-install ordered to stop squid",$called);

    }

   $unix->go_exec("/usr/sbin/artica-phpfpm-service -stop-proxy");

    echo "Stopping......: ".date("H:i:s")." Squid-Cache service starting watchdog";
    system("$monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state monitor APP_SQUID");

    if(is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");}
    if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];$executed="Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";}}
    squid_admin_mysql(2, "{success} to stop {APP_PROXY}",@implode("\n", $GLOBALS["LOGS"]), __FILE__, __LINE__, "proxy");

}






function KillGhosts(){

    $unix=new unix();
    $pgrep=$unix->find_program("pgrep");
    exec("$pgrep -l -f \"squid.*?-[0-9]+\)\" 2>&1",$results);
    $kill   = $unix->find_program("kill");


    foreach ($results as $num=>$ligne){
        if(preg_match("#pgrep#", $ligne)){continue;}
        if(!preg_match("#^([0-9]+)\s+(.+)#", $ligne,$re)){SendLogs("Skipping $ligne");continue;}
        $pid=$re[1];
        $cmdline=$re[2];
        unix_system_kill_force($pid);
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache process \"$cmdline\" process PID $pid\n";}

    }


    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service seems stopped search ntlm_auth processes...\n";}
    exec("$pgrep -l -f \"ntlm_auth.*?--helper-proto\" 2>&1",$results);
    foreach ($results as $num=>$ligne){
        if(preg_match("#pgrep#", $ligne)){continue;}
        if(!preg_match("#^([0-9]+)\s+\(ntlm_auth#", $ligne,$re)){SendLogs("Skipping $ligne");continue;}
        $pid=$re[1];
        unix_system_kill_force($pid);
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service ntlm_auth process PID $pid\n";}

    }

    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service seems stopped search external_acl_squid processes..\n";}
    exec("$pgrep -l -f \"external_acl_squid.php\" 2>&1",$results);
    foreach ($results as $num=>$ligne){
        if(preg_match("#pgrep#", $ligne)){continue;}
        if(!preg_match("#^([0-9]+)\s+.*#", $ligne,$re)){continue;}
        $pid=$re[1];
        unix_system_kill_force($pid);
        SendLogs("Stopping external_acl_squid process PID $pid");

    }


    $squidbin=$unix->LOCATE_SQUID_BIN();
    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service seems stopped search $squidbin processes...\n";}
    exec("$pgrep -l -f \"$squidbin\" 2>&1",$results);
    foreach ($results as $num=>$ligne){
        if(preg_match("#pgrep#", $ligne)){continue;}
        if(preg_match("#squid27#", $ligne)){continue;}
        if(!preg_match("#^([0-9]+)\s+.*#", $ligne,$re)){continue;}
        $pid=$re[1];
        unix_system_kill_force($pid);
        SendLogs("Stopping squid process PID $pid");

    }
    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service seems stopped search $squidbin (sub-daemons) processes...\n";}
    exec("$pgrep -l -f \"--kid squid-\" 2>&1",$results);
    foreach ($results as $num=>$ligne){
        if(preg_match("#pgrep#", $ligne)){continue;}
        if(!preg_match("#^([0-9]+)\s+.*#", $ligne,$re)){continue;}
        $pid=$re[1];
        shell_exec("$kill -9 $pid >/dev/null 2>&1");
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." Squid-Cache service killing process PID $pid\n";}

    }
}


function CurlGet($cmd){

    $unix=new unix();
    return $unix->squidclient($cmd);

}



function SendLogs($text){
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." $text\n";}
    $GLOBALS["LOGS"][]=$text;
}
function squid_get_alternate_port(){
    $f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
    foreach ($f as $num=>$ligne){
        if(preg_match("#(transparent|tproxy|intercept)#i", trim($ligne))){continue;}
        if(preg_match("#http_port\s+([0-9]+)$#", trim($ligne),$re)){return $re[1];}
        if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)$#", trim($ligne),$re)){return "{$re[1]}:{$re[2]}";}

        if(preg_match("#http_port\s+([0-9]+)\s+#", trim($ligne),$re)){return $re[1];}
        if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)\s+#", trim($ligne),$re)){return "{$re[1]}:{$re[2]}";}
    }

}
function squid_watchdog_events($text){
    $unix=new unix();
    if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
    $unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);


    $text="[WATCHDOG]: $text (L.$sourceline)";
    if(!function_exists("openlog")){return true;}
    openlog("squid", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;

}


function root_squid_version(){
    if(isset($GLOBALS["root_squid_version"])){return $GLOBALS["root_squid_version"];}
    $unix=new unix();
    $squidbin=$unix->find_program("squid");
    if($squidbin==null){$squidbin=$unix->find_program("squid3");}
    exec("$squidbin -v 2>&1",$results);
    foreach ($results as $num=>$val){
        if(preg_match("#Squid Cache: Version.*?([0-9\.\-a-z]+)#", $val,$re)){
            $GLOBALS["root_squid_version"]= trim($re[1]);
            return $GLOBALS["root_squid_version"];
        }
    }

}

function squidclient($cmd){

    if(!isset($GLOBALS["SQUIDCLIENT"])){
        $sock=new sockets();
        $SquidMgrListenPort=trim($sock->GET_INFO("SquidMgrListenPort"));
        $unix=new unix();
        if( !is_numeric($SquidMgrListenPort) OR ($SquidMgrListenPort==0) ){
            $SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
            if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}
            $http_port=squid_get_alternate_port();

            if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
                $SquidBinIpaddr=$re[1];
                if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
                $http_port=$re[2];
            }

        }else{
            $SquidBinIpaddr="127.0.0.1";
            $http_port=$SquidMgrListenPort;
        }
        $squidclient=$unix->find_program("squidclient");
        $GLOBALS["SQUIDCLIENT"]="$squidclient -T 5 -h 127.0.0.1 -p $http_port mgr";

    }

    exec($GLOBALS["SQUIDCLIENT"].":$cmd 2>&1",$results);


    if($GLOBALS["VERBOSE"]){echo $GLOBALS["SQUIDCLIENT"].":$cmd ". count($results)." lines\n";}
    return @implode("\n", $results);

}

function is31(){
    if(isset($GLOBALS["is31"])){return $GLOBALS["is31"];}
    $root_squid_version=root_squid_version();
    if($GLOBALS["VERBOSE"]){echo "Version: $root_squid_version\n";}
    $data=null;
    $GLOBALS["is31"]=false;
    $VER=explode(".",$root_squid_version);
    if($VER[0]<4){
        if($VER[1]<2){
            if($GLOBALS["VERBOSE"]){echo "$root_squid_version -> is 3.1.x\n";}
            $GLOBALS["is31"]=true;return true;}
    }
    return false;

}









function squid_cache_mem_current(){
    $unix=new unix();
    $results=explode("\n",$unix->squidclient("info"));

    foreach ($results as $ligne){
        if(preg_match("#Storage Mem size:\s+([0-9]+)\s+([A-Z]+)#", $ligne,$re)){
            if(strtoupper($re[2])=="MB"){
                $re[1]=$re[1]*1024;
            }
            if(strtoupper($re[2])=="GB"){
                $re[1]=$re[1]*1024;
                $re[1]=$re[1]*1024;

            }
            $array["CUR"]=$re[1];
        }

        if(!preg_match("#Storage Mem capacity:\s+([0-9\.]+).*?used,\s+([0-9\.]+).*?free#", $ligne,$re)){
            if($GLOBALS["VERBOSE"]){echo $ligne." no match\n";}
            continue;}
        $array["USED"]=$re[1];
        $array["FREE"]=$re[2];
        @file_put_contents(PROGRESS_DIR."/Storage.Mem.capacity", serialize($array));
        @chmod(PROGRESS_DIR."/Storage.Mem.capacity",0755);
        return;
    }


}

function squid_get_storage_info(){
}



function eCapClamav(){
    $sock=new sockets();
    $EnableeCapClamav=0;
    if($EnableeCapClamav==0){return;}
    $unix=new unix();
    $ln=$unix->find_program("ln");
    $chown=$unix->find_program("chown");
    shell_exec("$ln -sf /var/lib/clamav /usr/share/clamav >/dev/null 2>&1");
    shell_exec("$chown -R squid:squid /var/lib/clamav >/dev/null 2>&1");

}


function squid_get_system_info(){
    $unix=new unix();

    $fileCache="/etc/squid3/squid_get_system_info.db";
    if($unix->file_time_min($fileCache)<30){
        $dats=unserialize(@file_get_contents($fileCache));
    }
    if(!is_array($dats)){$dats=array();}
    if(count($dats)<2){
        @unlink($fileCache);
        $dats=$unix->squid_get_system_info();
        @file_put_contents("/etc/squid3/squid_get_system_info.db",serialize($dats));
    }

    return base64_encode(serialize($dats));
}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
    $tmp1 = round((float) $number, $decimals);
    while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
        $tmp1 = $tmp2;
    return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}





function SwapesList(){
    $unix       = new unix();
    $swaplist   = array();



    $results=explode("\n",@file_get_contents("/proc/swaps"));

    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^(.+?)\s+(.+)\s+([0-9]+)\s+([0-9]+)\s+#",$line,$re)){continue;}
        $swaplist[]=$re[1];
    }

    return $swaplist;
}



function SwapWatchdog(){

    $unix=new unix();
    $MonitConfig=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchdogMonitConfig")));
    $MonitConfig=watchdog_config_default($MonitConfig);


    if(count(SwapesList())==0){
        if($GLOBALS["VERBOSE"]){echo "\n******** SWAP (none - no swap ) *******\n";}
        return true;
    }



    if($GLOBALS["VERBOSE"]){echo "\n******** SWAP *******\n";}
    if($MonitConfig["SWAP_MONITOR"]==0){return true;}



    include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");
    $sys=new systeminfos();
    if(intval($sys->swap_used)==0){
        echo "No Swap used..., aborting...\n";
        return true;
    }

    $pourc=round(($sys->swap_used/$sys->swap_total)*100);
    $freeMemory=$unix->TOTAL_MEMORY_MB_FREE();
    $SwapMemoryused=$sys->swap_used;
    ToSyslog("SwapWatchdog(): {$sys->swap_used}MB used Current {$pourc}% Free Memory: {$freeMemory}MB, min:{$MonitConfig["SWAP_MIN"]}% MAX:{$MonitConfig["SWAP_MAX"]}%");

    if($pourc<$MonitConfig["SWAP_MIN"]){return true;}
    if(!isset($MonitConfig["SWAP_MIN"])){$MonitConfig["SWAP_MIN"]=5;}
    if(!isset($MonitConfig["SWAP_MAX"])){$MonitConfig["SWAP_MAX"]=55;}


    $ps_text[]="There is not enough memory to clean the swap";
    $ps_text[]="Current configuration was: Free Swap memory over than {$MonitConfig["SWAP_MAX"]}%";
    $ps_text[]="Your current Swap file using: {$SwapMemoryused}M - {$pourc}% - $sys->swap_used/$sys->swap_total";
    $ps_text[]="Memory free on your system:{$freeMemory}M";
    $ps_text[]="You will find here a snapshot of current tasks";
    $ps_text[]=$unix->ps_mem_report();
    $ps_mail=@implode("\n", $ps_text);

    if($pourc>$MonitConfig["SWAP_MAX"]){
        if($SwapMemoryused<$freeMemory){
            squid_admin_mysql(0, "[ALERT] REBOOT server!!! Swap exceed rule {$pourc}% max: {$MonitConfig["SWAP_MAX"]}%",$ps_mail,__FILE__,__LINE__);
            shell_exec($unix->find_program("shutdown")." -rF now");
            exit();
        }


        squid_admin_mysql(1, "Cleaning SWAP current: {$pourc}% max:{$MonitConfig["SWAP_MAX"]}%","clean the swap ({$SwapMemoryused}M/{$freeMemory}M)\n$ps_mail",__FILE__,__LINE__);
        SwapWatchdog_FreeSync();
        exit();

    }
    squid_admin_mysql(1, "Cleaning SWAP current:{$pourc}% min:{$MonitConfig["SWAP_MIN"]}%","clean the swap ({$SwapMemoryused}M/{$freeMemory}M)\n$ps_mail",__FILE__,__LINE__);
    SwapWatchdog_FreeSync();

}




function SwapWatchdog_FreeSync(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();
    $GLOBALS["ALL_SCORES"]++;
    $GLOBALS["ALL_SCORES_WHY"][]="Launch purge Swap procedure";
    shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.swapoff.php >/dev/null 2>&1 &");

}


function caches_center($aspid=false){
    $unix=new unix();
    $umount=$unix->find_program("umount");
    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")."Already `task` running PID $pid since {$time}mn\n";}
            return;
        }
        @file_put_contents($pidfile, getmypid());
    }

    if(system_is_overloaded(__FILE__)){ return; }



    $rm=$unix->find_program("rm");

    $q=new lib_sqlite("/home/artica/SQLITE/caches.db");



    $sql="SELECT * FROM squid_caches_center WHERE `remove`=1";
    $results=$q->QUERY_SQL($sql,"artica_backup");
    if(!$q->ok){squid_admin_mysql(1, "MySQL error $q->mysql_error", "$q->mysql_error");return;}
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $cache_dir=$ligne["cache_dir"];
        $cache_type=$ligne["cache_type"];
        if($cache_type=="Cachenull"){
            $q->QUERY_SQL("DELETE FROM squid_caches_center WHERE ID=$ID","artica_backup");
            continue;
        }


        if($cache_type=="tmpfs"){
            $cache_dir="/home/squid/cache/MemBooster$ID";
        }

        if(is_link($cache_dir)){$cache_dir=readlink($cache_dir);}
        shell_exec("$rm -rf $cache_dir");
        squid_admin_mysql(1, "Cache $cache_dir was deleted from DISK", "ID=$ID\ndirectory=$cache_dir");
        $q->QUERY_SQL("DELETE FROM squid_caches_center WHERE ID=$ID","artica_backup");

        if($cache_type=="tmpfs"){
            shell_exec("$umount -l $cache_dir");
        }

        if(!$q->ok){squid_admin_mysql(1, "MySQL error $q->mysql_error", "ID=$ID\ndirectory=$cache_dir");}
    }

    if($GLOBALS["VERBOSE"]){echo "Cache Center done\n";}


}
function parse_cpu_content($content):array{
    $MAIN=array();
    $lines=explode("\n",$content);

    foreach ($lines as $line){
        if(preg_match("#Last 15 minutes#",$line)){break;}
        if(preg_match("#^(.+?)=(.+)#",$line,$re)){
            $MAIN[trim(strtolower($re[1]))]=trim(strtolower($re[2]));
        }
    }
    if($GLOBALS["VERBOSE"]){echo "parse_cpu_content -> ".count($MAIN)." records\n";}
    return $MAIN;
}



function go_squid_auth_watchdog(){
    $unix=new unix();
    $gosquidauth_dst = "/lib/squid3/go-squid-auth";
    $md52=null;
    $gosquidauth_src=ARTICA_ROOT."/bin/go-shield/client/external_acls_ldap/bin/go-squid-auth";
    if(!is_file($gosquidauth_src)) {return true;}
    $md51=md5_file($gosquidauth_src);
    if (is_file($gosquidauth_dst)) {$md52=md5_file($gosquidauth_dst);}
    $squidbin=$unix->LOCATE_SQUID_BIN();
    if($md51==$md52){return true;}
        for($i=0;$i<10;$i++){
            shell_exec("/usr/sbin/artica-phpfpm-service -reload-proxy");
            @copy($gosquidauth_src,$gosquidauth_dst);
            @chmod($gosquidauth_dst,0755);
            $md52=md5_file($gosquidauth_dst);
            if($md51==$md52){
                squid_admin_mysql(1,"Installing the new proxy helper plugin go-squid-auth",null,__FILE__,__LINE__);
                return true;
            }
        }
    return true;
}

function go_squid_auth_ad_agent_watchdog(){
    $unix=new unix();
    $gosquidauth_dst = "/lib/squid3/external_acls_ad_agent";
    $md52=null;
    $gosquidauth_src=ARTICA_ROOT."/bin/go-shield/client/external_acls_gc/bin/external_acls_ad_agent";
    if(!is_file($gosquidauth_src)) {return true;}
    $md51=md5_file($gosquidauth_src);
    if (is_file($gosquidauth_dst)) {$md52=md5_file($gosquidauth_dst);}

    if($md51==$md52){return true;}
    for($i=0;$i<10;$i++){
        shell_exec("/usr/sbin/artica-phpfpm-service -reload-proxy");
        @copy($gosquidauth_src,$gosquidauth_dst);
        @chmod($gosquidauth_dst,0755);
        $md52=md5_file($gosquidauth_dst);
        if($md51==$md52){
            squid_admin_mysql(1,"Installing the new proxy helper plugin go-squid-auth-ad-agent",null,__FILE__,__LINE__);
            return true;
        }
    }
    return true;
}





# Service ID 3

function C_ICAP_CLIENTS_SCAN():array{
    $MAIN=array();
    $f=explode("\n",@file_get_contents("/etc/squid3/icap.conf"));
    foreach ($f as $line){
        $line=trim($line);
        if(!preg_match("#\#\s+Service\s+ID\s+([0-9]+)\s+#",$line,$re)){
            continue;
        }
        $MAIN[$re[1]]=true;
    }
    return $MAIN;
}

function C_ICAP_CLIENTS($aspid=false){
    $C_ICAP_CLIENTS_SCAN=C_ICAP_CLIENTS_SCAN();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("C_ICAP_CLIENTS_SCAN",serialize($C_ICAP_CLIENTS_SCAN));
    $unix=new unix();
    $PidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";

    if(!$aspid){
        $pid=$unix->get_pid_from_file($PidFile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $TimeMin=$unix->PROCCESS_TIME_MIN($pid);
            echo "Alreay process exists $pid since $TimeMin minutes\n";
            return;
        }

    }
    @file_put_contents($PidFile, getmypid());
    $EnableClamavInCiCap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavInCiCap"));
    $cicap_client=$unix->find_program("c-icap-client");
    if(!is_file($cicap_client)){
        if(is_file("/etc/cron.d/artica-icap-checks")){
            @unlink("/etc/cron.d/artica-icap-checks");
            system("/etc/init.d/cron reload");
        }
    }
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");

    if(!$q->TABLE_EXISTS("c_icap_services")){return;}
    $q->QUERY_SQL("UPDATE c_icap_services SET `status`=0 WHERE `enabled`=0");



    $sql="SELECT * FROM c_icap_services WHERE enabled=1";
    $results = $q->QUERY_SQL($sql);
    if(count($results)==0){
        if(is_file("/etc/cron.d/artica-icap-checks")){
            @unlink("/etc/cron.d/artica-icap-checks");
            system("/etc/init.d/cron reload");
        }
        return;
    }

    if(!is_file("/etc/cron.d/artica-icap-checks")){
        $unix->Popuplate_cron_make("artica-icap-checks",
            "0,2,4,6,8,10,12,14,16,18,20,22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54,56,58 * * * *",
            basename(__FILE__)." --icap");

    }

    $php=$unix->LOCATE_PHP5_BIN();
    $METHODS["reqmod_precache"]="REQMOD";
    $METHODS["respmod_precache"]="RESPMOD";

    foreach ($results as $index=>$ligne){
        //$service_name_text=$ligne["service_name"];
        $ID=$ligne["ID"];
        $addr=trim($ligne["ipaddr"]);
        $port=$ligne["listenport"];
        $service=$ligne["icap_server"];
        $status=$ligne["status"];

        $fp=@fsockopen($addr, $port, $errno, $errstr, 2);
        if(!$fp){
            $q->QUERY_SQL("UPDATE c_icap_services SET `status`=2 WHERE ID=$ID");
            continue;
        }


        $cmdline="$cicap_client -i $addr -p $port -s $service -method {$METHODS[$ligne["respmod"]]} 2>&1";

        if($ID==1 OR $ID==2){
            if($EnableClamavInCiCap==0){continue;}
        }

        if($GLOBALS["VERBOSE"]){echo "[$ID] $cmdline\n";}

        if($ID==12){
            $service="webfilter";
        }



        $Mresults[]="Service ID:$ID $addr:$port/$service/{$METHODS[$ligne["respmod"]]}";


        if(C_ICAP_CLIENTS_CHECK($cmdline)){
            if($status==5){
                squid_admin_mysql(0,"ICAP $service SUCCESS for $HEADER_LOG [{action}={enbable}]",null,__FILE__,__LINE__);
                shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --icap-silent --nowatch");
            }
            $q->QUERY_SQL("UPDATE c_icap_services SET `status`=1 WHERE ID=$ID");
            continue;
        }
        $FINAL=false;

        $HEADER_LOG="$addr:$port/$service ({$METHODS[$ligne["respmod"]]})";

        for($i=1;$i<6;$i++){
            if($status<>5) {
                squid_admin_mysql(1, "ICAP $service FAILED ($i/5) for $HEADER_LOG [{action}={notify}]",
                    @implode("\n", $Mresults) . @implode("\n", $GLOBALS["MRESULTS"]), __FILE__, __LINE__);
            }
            sleep(3);
            if(C_ICAP_CLIENTS_CHECK($cmdline)){
                if($status==5){
                    squid_admin_mysql(0,"ICAP $service SUCCESS for $HEADER_LOG [{action}={enable}]",null,__FILE__,__LINE__);
                }

                $q->QUERY_SQL("UPDATE c_icap_services SET `status`=1 WHERE ID=$ID");
                $FINAL=true;
                break;
            }
        }

        if(!$FINAL){
            if($status==5){continue;}
            squid_admin_mysql(0,"ICAP $service FAILED for $HEADER_LOG [{action}={disable}]",
                @implode("\n", $Mresults).
                @implode("\n", $GLOBALS["MRESULTS"]),__FILE__,__LINE__);
                $q->QUERY_SQL("UPDATE c_icap_services SET `status`=5,`enabled`=1 WHERE ID=$ID");
                shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --icap-silent --nowatch");
        }else{
            $q->QUERY_SQL("UPDATE c_icap_services SET `status`=1 WHERE ID=$ID");
        }
    }


}

function C_ICAP_CLIENTS_CHECK($cmdline){
    exec($cmdline,$Mresults);
    $GLOBALS["MRESULTS"]=$Mresults;
    if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}


    foreach ($Mresults as $index=>$line){
        if(preg_match("#200 OK#i", $line)){ return true; }
        if(preg_match("#404 Service not found#i", $line)){ return false; }
        if(preg_match("#Failed to connect#i", $line)){ return false;}
    }

    squid_admin_mysql(1,"Unknown ICAP status",@implode("\n", $Mresults),__FILE__,__LINE__);

    return true;
}

function check_status(){

    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    build_progress_status2("{checking_proxy_service}",20);
    sleep(1);
    $pid=SQUID_PID();
    echo "PID: $pid\n";
    if($unix->process_exists($pid)){
        $ttl=$unix->PROCCESS_TIME_MIN($pid);
        build_progress_status2("{APP_SQUID}: {running} {since} {$ttl}Mn",30);
        echo "Running since {$ttl}Mn\n";
    }else{
        build_progress_status2("{APP_SQUID}: {operation} {starting_service}",30);
        start_squid(true);
    }

    $pid=SQUID_PID();
    echo "PID: $pid\n";
    if(!$unix->process_exists($pid)){
        build_progress_status2("{APP_SQUID}: {failed}",110);
    }
    $GLOBALS["FORCE"]=true;
    build_progress_status2("{APP_SQUID}: {remove_cached_datas}",50);
    sleep(1);
    echo "Remove ALL_SQUID_STATUS\n";
    @unlink("/usr/share/artica-postfix/ressources/logs/web/squid.status.html");
    @unlink("/usr/share/artica-postfix/ressources/logs/web/status.right.image.cache");
    if(is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");}
    $cmd="$php /usr/share/artica-postfix/exec.status.php --all-squid --nowachdog >/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS 2>&1";
    echo "$cmd\n";
    shell_exec($cmd);
    build_progress_status2("{APP_SQUID}: {checking_caches}",55);
    sleep(1);
    build_progress_status2("{APP_SQUID}: {checking_caches}",60);
    sleep(1);
    build_progress_status2("{APP_SQUID}: {checking_caches}",61);
    sleep(1);
    build_progress_status2("{APP_SQUID}: {checking_caches}",62);

    sleep(1);

    build_progress_status2("{APP_SQUID}: {checking_caches}",63);
    ALLKIDS(true);
    sleep(1);




    build_progress_status2("{APP_SQUID}: {done}",100);
    sleep(1);
}

function squid_conx(){
    $unix=new unix();
    $unix->SQUID_ACTIVE_REQUESTS();
}


function caches_size(){
    $unix=new unix();
    $cache_file=PROGRESS_DIR."/squid_caches_center.db";
    $ARRAY=array();
    if($unix->file_time_min($cache_file)<120){return;}


    $q=new lib_sqlite("/home/artica/SQLITE/caches.db");
    if($q->COUNT_ROWS("squid_caches_center")>0){
        $sql="SELECT * FROM squid_caches_center WHERE `enabled`=1 AND `remove`=0 ORDER BY zOrder";
        $results=$q->QUERY_SQL($sql);

        foreach ($results as $index=>$ligne){
            $cachename=$ligne["cachename"];
            $cache_dir=$ligne["cache_dir"];
            $cache_type=$ligne["cache_type"];
            if($cache_type=="Cachenull"){continue;}
            $cache_partition=$unix->DIRPART_OF($cache_dir);
            $ARRAY["CACHES_SIZE"][$cache_partition]=$unix->DIRECTORY_FREEM($cache_dir);
            $ARRAY[$cache_dir]["NAME"]=$cachename;
            $ARRAY[$cache_dir]["PARTITION"]=$cache_partition;
            $ARRAY[$cache_dir]["DIRPART_INFO"]=$unix->DIRPART_INFO($cache_dir);
            $ARRAY[$cache_dir]["SIZE"]=$ARRAY["CACHES_SIZE"][$cache_partition];





        }

    }
if(!isset($GLOBALS["CACHES_SIZE"])){$GLOBALS["CACHES_SIZE"]=array();}
    $caches=$unix->SQUID_CACHE_FROM_SQUIDCONF_FULL();
    foreach ($caches as $cache_dir=>$line){
        if(isset($ARRAY[$cache_dir])){continue;}
        $cachename=basename($cache_dir);
        $cache_partition=$unix->DIRPART_OF($cache_dir);
        $ARRAY["CACHES_SIZE"][$cache_partition]=$unix->DIRECTORY_FREEM($cache_dir);
        $ARRAY[$cache_dir]["NAME"]=$cachename;
        $ARRAY[$cache_dir]["PARTITION"]=$cache_partition;
        $ARRAY[$cache_dir]["DIRPART_INFO"]=$unix->DIRPART_INFO($cache_dir);
        if(!isset($GLOBALS["CACHES_SIZE"][$cache_partition])){
            $GLOBALS["CACHES_SIZE"][$cache_partition]=0;
        }
        $ARRAY[$cache_dir]["SIZE"]=$GLOBALS["CACHES_SIZE"][$cache_partition];


    }

    if(is_file($cache_file)) {
        @unlink($cache_file);
    }
    @file_put_contents($cache_file, serialize($ARRAY));
    @chmod($cache_file, 0777);



}




function dev_shm(){
    $NotifyTime="/etc/artica-postfix/pids/squid.dev.shm";
    if(!is_dir("/run/shm")){return;}
    $sock=new sockets();
    $MonitConfig=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchdogMonitConfig")));
    $MonitConfig=watchdog_config_default($MonitConfig);



    $unix=new unix();
    $percent=$unix->TMPFS_USEPERCENT("/run/shm");


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("StatusDevSHMPerc", $percent);
    @chmod("/etc/artica-postfix/settings/Daemons/StatusDevSHMPerc", 0755);

    if($percent>90){
        if($percent<98){
            $NotifyTimeEx=$unix->file_time_min($NotifyTime);
            if($NotifyTimeEx>10){
                squid_admin_mysql(2, "{warning} Shared memory exceed 90%!", "The /dev/shm memory partition exceed 90%, at 100% the proxy will crash!");
                return;
            }
        }
    }

    if($percent>98){
        if($MonitConfig["watchdog"]==1){
            squid_admin_mysql(0, "{warning} Shared memory exceed 98% ({$percent}%)[ {action} = {restart} ]", "The /dev/shm memory partition exceed 98%, at 100% the proxy will crash!, in this case, the proxy will be restarted.");
            $GLOBALS["BY_OTHER_SCRIPT"]="function SHM";
            $GLOBALS["FORCE"]=true;
            restart_squid(true);
        }else{
            squid_admin_mysql(0, "{warning} Shared memory exceed 98% ({$percent}%) [action=notify]", "The /dev/shm memory partition exceed 98%, at 100% the proxy will crash!");
        }
    }

}







function CheckActiveDirectoryEmergency(){


    $f=explode("\n",@file_get_contents("/etc/squid3/non_ntlm.access"));
    $sock=new sockets();
    $ActiveDirectoryEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryEmergency"));
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));

    if($EnableKerbAuth==0){
        $f=array();
        $f[]="# Nothing to do... ";
        $f[]="# This file is used for Allow users surfing trough Internet when there are issues on Active Directory ";
        $f[]="";
        @file_put_contents("/etc/squid3/non_ntlm.access", @implode("\n", $f));
        return;
    }

    if($ActiveDirectoryEmergency==1){return;}
    $ENABLED=false;
    foreach ($f as $line){
        if(preg_match("#http_access allow all#", $line)){
            $ENABLED=true;
            break;
        }

    }



    if(!$ENABLED){return;}

    if($ENABLED){
        $f=array();
        $f[]="# Nothing to do... ";
        $f[]="# This file is used for Allow users surfing trough Internet when there are issues on Active Directory ";
        $f[]="";
        @file_put_contents("/etc/squid3/non_ntlm.access", @implode("\n", $f));

        squid_admin_mysql(1, "Re-Activate Authentication mode after Active Directory Emergency mode [action=reload proxy]", "",__FILE__,__LINE__);
        $unix=new unix();
        squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
        system("/usr/sbin/artica-phpfpm-service -reload-proxy");
    }

}







function BANDWIDTH_MONITOR_CRON(){
    $sock=new sockets();
    $cronfile="/etc/cron.d/artica-squid-watchband";
    $SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
    if($SquidPerformance>1){
        if(is_file($cronfile)){@unlink("$cronfile"); system("/etc/init.d/cron reload"); }
        return;
    }

    $watchdog=new squid_watchdog();
    $MonitConfig=$watchdog->MonitConfig;
    if($MonitConfig["CHECK_BANDWITDH"]==0){
        if(is_file($cronfile)){@unlink("$cronfile"); system("/etc/init.d/cron reload"); }
        return;
    }



    $per[5]="0,5,10,15,20,25,30,35,40,45,50,55 * * * *";
    $per[10]="0,10,20,30,40,45,50 * * * *";
    $per[30]="0,30 * * * *";
    $per[60]="0 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * *";
    $per[120]="0 0,2,4,6,8,10,12,14,16,18,20,22 * * *";
    $per[360]="0 0,6,12,18 * * *";
    $per[720]="0 0,12 * * *";
    $per[1440]="0 0 * * *";

    $unix=new unix();
    $nice=$unix->EXEC_NICE();
    $php=$unix->LOCATE_PHP5_BIN();
    $CHECK_BANDWITDH_INTERVAL=$MonitConfig["CHECK_BANDWITDH_INTERVAL"];
    if(!is_numeric($CHECK_BANDWITDH_INTERVAL)){$CHECK_BANDWITDH_INTERVAL=5;}

    $f[]="MAILTO=\"\"";
    $f[]=$per[$CHECK_BANDWITDH_INTERVAL]." $nice $php ".__FILE__." --bandwidth-run >/dev/null 2>&1";
    $f[]="";

    @file_put_contents($cronfile, @implode("\n", $f));
    @chmod($cronfile,0644);
    system("/etc/init.d/cron reload");
    unset($f);

}

function BANDWIDTH_MONITOR(){
    $sock=new sockets();
    $SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
    if($SquidPerformance>1){return;}
    $watchdog=new squid_watchdog();
    $MonitConfig=$watchdog->MonitConfig;
    if($MonitConfig["CHECK_BANDWITDH"]==0){return;}
    $CHECK_BANDWITDH_INTERVAL=$MonitConfig["CHECK_BANDWITDH_INTERVAL"];
    $influx=new influx();
    if(!is_numeric($CHECK_BANDWITDH_INTERVAL)){$CHECK_BANDWITDH_INTERVAL=5;}
    $olddate=strtotime("-{$CHECK_BANDWITDH_INTERVAL} minutes",time());
    $CHECK_BANDWITDH_SIZE=intval($MonitConfig["CHECK_BANDWITDH_INTERVAL"]);


    $query_date=date("Y-m-d H:i:s",$olddate);
    $postgres=new postgres_sql();
    $sql="select sum(SIZE) as size from access_log where zdate > '$olddate'";
    $ligne=@pg_fetch_assoc($postgres->QUERY_SQL($sql));


    $size=($ligne["size"]/1024);
    $size=round($size/1024,2);
    if($GLOBALS["VERBOSE"]){echo "Since ".date("Y-m-d H:i:s",$olddate)."- Size: {$size}MB\n";}



    if($GLOBALS["VERBOSE"]){echo "{$size}MB must be higher than {$CHECK_BANDWITDH_SIZE}MB\n";}
    if($size<$CHECK_BANDWITDH_SIZE){return;}

    $EXCEED_SIZE=$size;

    $REPORT[]="Report bandwidth usage since: ".date("{l} {F} d H:i:s",$olddate);

    $ipclass=new IP();
    $sql="select sum(size) as size,ipaddr,mac,userid from access_log where zdate > '$olddate' group by IPADDR,MAC,USERID order by size desc";
    $results=$postgres->QUERY_SQL($sql);



    while($ligne=@pg_fetch_assoc($results)){
        $users2=array();

        $size=$ligne["size"]/1024;
        $size=round($size/1024,2);
        if($size==0){continue;}
        if($size<1){continue;}
        if($CHECK_BANDWITDH_SIZE>1){if($size<2){continue;}}
        $IPADDR=$ligne["ipaddr"];
        $users2[]=$IPADDR;
        $MAC=trim($ligne["mac"]);

        $USERID=$ligne["userid"];
        if($USERID<>null){
            $users2[]=$USERID;
        }
        if($ipclass->IsvalidMAC($MAC)){
            $users2[]=$MAC;
        }


        $REPORT[]="User: ".@implode(", ", $users2)." {$size}MB used";

        if($GLOBALS["VERBOSE"]){echo "Since ".date("Y-m-d H:i:s",$olddate)."- $IPADDR,$MAC,$USERID Size: {$size}MB\n";}
    }



    $catz=new mysql_catz();
    $sql="select sum(SIZE) as size,familysite from access_log group by familysite where zdate > '{$olddate}' ORDER by size desc";
    $results=$postgres->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $size=$ligne["size"]/1024;
        $size=round($size/1024,2);
        if($size==0){continue;}
        if($size<1){continue;}
        $FAMILYSITE=$ligne["familysite"];
        $category=$catz->GET_CATEGORIES($FAMILYSITE);
        if($category<>null){$category_text=" (category:$category)";}
        $REPORT[]="Web site: $FAMILYSITE {$size}MB used$category_text";


    }

    squid_admin_mysql(0, "Bandwidth usage {$EXCEED_SIZE}MB exceed {$CHECK_BANDWITDH_SIZE}MB",
        @implode("\n", $REPORT),__FILE__,__LINE__);

}


function CHECK_SQUID_EXTERNAL_LDAP(){

    $sock=new sockets();
    if(!$sock->SQUID_IS_EXTERNAL_LDAP()){return;}

    $unix=new unix();
    $filetime="/etc/artica-postfix/pids/".md5(__FILE__.__FUNCTION__);
    $TimeExec=$unix->file_time_min($filetime);

    $EXTERNAL_LDAP_AUTH_PARAMS=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternalAuth")));
    $ldap_server=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
    $ldap_port=intval($EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"]);
    if($ldap_port==0){$ldap_port=389;}
    $ldap_suffix=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"];
    $CONNECTION=@ldap_connect($ldap_server,$ldap_port);

    if(!$CONNECTION){
        if($TimeExec>30){
            @unlink($filetime);
            squid_admin_mysql(0, "Connection to LDAP server failed $ldap_server:$ldap_port", null,__FILE__,__LINE__);
            @file_put_contents($filetime, time());
        }
        return;

    }
    @ldap_set_option($CONNECTION, LDAP_OPT_PROTOCOL_VERSION, 3);
    @ldap_set_option($CONNECTION, LDAP_OPT_REFERRALS, 0);
    @ldap_set_option($CONNECTION, LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD
    @ldap_set_option($CONNECTION, LDAP_OPT_REFERRALS, 0);

    $userdn=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];
    $ldap_password=$EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"];
    $BIND=@ldap_bind($CONNECTION, $userdn, $ldap_password);

    if(!$BIND){
        $error=@ldap_err2str(@ldap_errno($CONNECTION));
        if (@ldap_get_option($CONNECTION, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$error=$error." $extended_error";}
        @ldap_close($CONNECTION);
        if($TimeExec>30){
            @unlink($filetime);
            squid_admin_mysql(0, "Authenticate to LDAP server $ldap_server:$ldap_port failed $error", $error,__FILE__,__LINE__);
            @file_put_contents($filetime, time());
        }
        return ;
    }
    @unlink($filetime);
    @ldap_close($CONNECTION);

}





function PARANOID_MODE_CLEAN(){}
function taskset(){
    $processes=array();
    $cores=array();
    $unix=new unix();
    $taskset=$unix->find_program("taskset");
    $f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^cpu_affinity_map process_numbers=(.+?)\s+cores=(.+?)#",$line,$re)){continue;}
        $re[1]=trim($re[1]);
        $re[2]=trim($re[2]);
        if(strpos($re[1], ",")>0){
            $processes=explode(",",$re[1]);
            $cores=explode(",",$re[2]);
        }else{
            $processes[]=$re[1];
            $cores[]=$re[2];
        }
        break;

    }
    if(count($processes)==0){
        if($GLOBALS["VERBOSE"]){echo "No cpu_affinity_map found\n";}
        return;}

    foreach ($processes as $index=>$ProcessNumber){
        $pid=intval(KidGetPid($ProcessNumber));
        if($GLOBALS["VERBOSE"]){echo "$ProcessNumber -> $pid\n";}
        if($pid==0){continue;}

        $CpuSetGet=CpuSetGet($pid);
        $expected=$cores[$index]-1;
        echo "Process Number $ProcessNumber PID $pid task:{$CpuSetGet} Expected $expected\n";
        if($CpuSetGet<>$expected){
            if($GLOBALS["VERBOSE"]){echo "Setting PID $pid to $expected\n";}

            $cmd="$taskset -a -c -p $expected $pid";
            if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
            shell_exec("$cmd");
        }
    }
}





function KidGetPid($number){
    $unix=new unix();
    $pgrep=$unix->find_program("pgrep");
    exec("$pgrep -l -f \"\(squid-$number\" 2>&1",$results);
    foreach ($results as $num=>$line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#pgrep#", $line)){continue;}
        if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
        return $re[1];
    }


}

function CpuSetGet($pid){
    $unix=new unix();
    $taskset=$unix->find_program("taskset");
    exec("$taskset -c -p $pid 2>&1",$results);
    foreach ($results as $num=>$line){
        if(!preg_match("#$pid.*?:\s+(.+)#",$line,$re)){continue;}
        if(strpos($re[1], "-")>0){return 0;}
        return intval($re[1])+1;
    }


}