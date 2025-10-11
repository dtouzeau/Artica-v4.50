<?php
# New model
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
$GLOBALS["CLASS_SOCKETS"]       = new sockets();
$GLOBALS["GENPROGGNAME"]        = "ksrn.progress";
$GLOBALS["TITLENAME"]           = "Kaspersky Security Reputation Network";
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
// COmpilateur 192.168.1.190


if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--install-server"){install_server();exit;}
if($argv[1]=="--uninstall-server"){uninstall_server();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--emergency"){emergency();exit;}
if($argv[1]=="--emergency-off"){emergency_off();exit;}
if($argv[1]=="--dnsbl"){dnsbl();exit;}
if($argv[1]=="--dnsbl-cache"){dnsbl_empty_cache();exit;}
if($argv[1]=="--ksrn-cache"){ksrnempty_cache();exit;}
if($argv[1]=="--logfile"){logfile();exit;}
if($argv[1]=="--clean-log"){clean_logs();exit;}
if($argv[1]=="--check-updates"){checkupdates();exit;}
if($argv[1]=="--update"){checkupdates();exit;}
if($argv[1]=="--update-client"){update_version();exit;}
if($argv[1]=="--restart-client"){restart_client();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--reload"){reload();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--memcache"){memcache_dump();exit;}
if($argv[1]=="--clean-cache"){memcache_clean();exit;}
if($argv[1]=="--status"){build_status();exit;}
if($argv[1]=="--purge"){purge_cache();exit;}
if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--install-kcloud"){ install_kcloud();exit;}
if($argv[1]=="--uninstall-kcloud"){  uninstall_kcloud();exit;}


//

function ksrnempty_cache(){}
function checkupdates_progress($prc,$text){}
function restart_client_progress($prc,$text){}
function memcache_dump(){}
function memcache_clean(){}
function purge_cache(){}
function build_status(){}
function restart_client(){}
function GET_PID(){
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/theshields.pid");
    if($unix->process_exists($pid)){return $pid;}
    return $unix->PIDOF_PATTERN("artica-postfix/bin/theshields");
}
function stop($aspid=false){
    $GLOBALS["TITLENAME"]="The Shields Service";
    $GLOBALS["OUTPUT"]=true;
    $unix=new unix();
    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=GET_PID();


    if(!$unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";
        return true;
    }
    $pid=GET_PID();


    echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";
    $unix->KILL_PROCESS($pid,9);
    for($i=0;$i<5;$i++){
        $pid=GET_PID();
        if(!$unix->process_exists($pid)){break;}
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";
        $unix->KILL_PROCESS($pid,9);
        sleep(1);
    }

    $pid=GET_PID();
    if(!$unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";
        return true;
    }



    echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=GET_PID();
        if(!$unix->process_exists($pid)){break;}
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";
        sleep(1);
    }

    $TheShieldsPORT = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsPORT"));
    if($TheShieldsPORT==0){$TheShieldsPORT=2004;}
    $pids=$unix->PIDOF_BY_PORT($TheShieldsPORT);
    _out(count($pids)." Processes listens $TheShieldsPORT");
    if(count($pids)>0){
        $unix->KILL_PROCESSES_BY_PORT($TheShieldsPORT);
        _out("KILLING ".count($pids)." PIDs listen port $TheShieldsPORT");
    }

    if($unix->process_exists($pid)){
        _out("Stopping service failed...");
        return false;
    }
    return true;

}
function install_server(){
    uninstall_server();
}
function uninstall_server(){
    $unix=new unix();
    squid_admin_mysql(1,"{uninstall} {SRN} ...",null,__FILE__,__LINE__);
    $unix->remove_service("/etc/init.d/theshields");
    build_monit();
    shell_exec("/etc/init.d/artica-status restart --force");

}


function start($aspid=false){
    build_monit();

}
function build(){
    build_monit();
    build_syslog();
    build_cron();
}

function reload(){}
function restart(){}
function CheckInterface(){
    $TheShieldsInterface = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsInterface");
    if($TheShieldsInterface==null){$TheShieldsInterface="lo";}
    if($TheShieldsInterface=="lo"){return "127.0.0.1";}
    $unix=new unix();
    $IPaddr=$unix->InterfaceToIPv4($TheShieldsInterface);
    if($IPaddr==null){
        _out("Calcultated Interface [NULL!]");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TheShieldsIP","127.0.0.1");
        return "127.0.0.1";
    }
    if($IPaddr=="0.0.0.0"){
        _out("Calcultated Interface [$IPaddr!]");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TheShieldsIP","127.0.0.1");
        return "127.0.0.1";
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TheShieldsIP",$IPaddr);
    return $IPaddr;
}

function build_monit(){
    $monit_file="/etc/monit/conf.d/SRN.monitrc";
    if(is_file($monit_file)){
        @unlink($monit_file);
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
        return true;
        }
    return true;
}



function update_version(){
    $unix=new unix();
    $ARROOT         = ARTICA_ROOT;
    $krsn_src       = "$ARROOT/bin/install/squid/external_acl_first";
    $old_krsn_dst       = "/lib/squid3/external_acl_first";
    $old_service        = "$ARROOT/bin/theshields";
    $php            = $unix->LOCATE_PHP5_BIN();
    if(is_file($old_krsn_dst)){
        @unlink($old_krsn_dst);
        if(is_file("/root/squid-good.tgz")){@unlink("/root/squid-good.tgz");}
        squid_admin_mysql(0, "{shield_migration} (3)", null, __FILE__, __LINE__);
        shell_exec("$php /usr/share/artica-postfix/exec.go.shield.server.php --install");

    }
    if(is_file($krsn_src)){@unlink($krsn_src);}
    if(is_file($old_service)){@unlink($old_service);}
    if(is_file("/etc/rsyslog.d/ksrn.conf")){@unlink("/etc/rsyslog.d/ksrn.conf");}
    $files[]="ksrn";
    $files[]="ksrn-clean";
    $files[]="ksrn-stats";
    $files[]="ksrn-status";
    $files[]="ksrn-purge";
    $files[]="ksrn-local";

    foreach ($files as $crf){
        $unix->Popuplate_cron_delete($crf);

    }
    return true;
}

function checkupdates($noserv=false){update_version();}

function dnsbl_empty_cache(){}

function install_kcloud(){}
function uninstall_kcloud(){}
function uninstall(){
    build_progress(50,"{uninstalling}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KSRNEnable",0);
    if(is_file("/etc/init.d/go-shield-server")) {
        build_progress(70, "{restarting}");
        shell_exec("/etc/init.d/go-shield-server restart");
    }
    build_progress(100, "{success}");

}
function emergency_off(){}
function emergency(){}
function dnsbl(){}
function build_progress_restart($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"ksrn.restart");
}
function build_progress($prc,$text):bool{
    if(is_numeric($text)){
        $old_text=$prc;
        $prc=$text;
        $text=$old_text;
    }
    $progressfile=$ProgressFile=PROGRESS_DIR."/ksrn.progress";
    $array["POURC"]=$prc;
    $array["TEXT"]=$text;
    @file_put_contents($progressfile, serialize($array));
    @chmod($progressfile,0755);
    return true;
}

function clean_logs(){
    if(!is_file("/var/log/ksrn.log")){return false;}
    @unlink("/var/log/ksrn.log");
    $unix=new unix();$unix->RESTART_SYSLOG(true);
    $unix=new unix();
    $unix->ToSyslog("The Shields events cleaned...",false,"ksrn");
    return true;
}
function logfile():bool{
    $unix=new unix();
    $sf[]="/var/log/theshields-daemon.debug";
    $sf[]="/var/log/theshields-daemon.log";
    $sf[]="/var/log/squid/ksrn.debug";
    $sf[]="/var/log/ksrn.log";
    $sf[]="/var/log/squid/theshields-client.log";

    $tmpfile=$unix->FILE_TEMP();

    foreach ($sf as $srcp){
        $final_content[]="******************** $srcp *****************";
        $final_content[]=@file_get_contents($srcp);
        $final_content[]="********************************************\n\n\n";
    }

    $target=PROGRESS_DIR."/ksrn.log.gz";
    if(is_file($target)){@unlink($target);}

    file_put_contents($tmpfile,@implode("\n",$final_content));
    build_progress("{compressing} $tmpfile",20);
    if(!$unix->compress($tmpfile,$target)){
        build_progress("{failed}",110);
        @unlink($tmpfile);
        return false;
    }
    @unlink($tmpfile);
    build_progress("{success}",100);
    return true;

}
function build_syslog(){}
function build_cron(){
    $unix=new unix();
    $RESTART=false;
    $fname=basename(__FILE__);
    $files[]="ksrn";
    $files[]="ksrn-clean";
    $files[]="ksrn-stats";
    $files[]="ksrn-status";
    $files[]="ksrn-purge";
    $files[]="ksrn-local";
    $MD5START=array();$MD5END=array();
    foreach ($files as $cronf){
        if(!is_file("/etc/cron.d/$cronf")){continue;}
        $MD5START[$cronf]=md5_file("/etc/cron.d/$cronf");
    }

    $TheShieldsPurge=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsPurge"));

    $TheShieldsUseLocalCats=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsUseLocalCats"));
    if($TheShieldsUseLocalCats==1){
        if(!is_file("/etc/cron.d/ksrn-local")) {
            $unix->Popuplate_cron_make("ksrn-local", "30 */4 * * *", "$fname --update-local");
            $python=$unix->find_program("python");
            shell_exec("$python /usr/share/artica-postfix/bin/compile-category.py --update");
        }

    }else{

       $unix->Popuplate_cron_delete("ksrn-local");
    }



    $TheShieldsPurgeAct[0]="30 0 * * *";
    $TheShieldsPurgeAct[1]="30 0 * * 0";
    $TheShieldsPurgeAct[2]="* */12 * * *";
    $TheShieldsPurgeAct[3]="* */6 * * *";
    $TheShieldsPurgeAct[4]="* */3 * * *";


    $unix->Popuplate_cron_make("ksrn-purge",$TheShieldsPurgeAct[$TheShieldsPurge],"$fname --purge");
    $unix->Popuplate_cron_make("ksrn", "35 */3 * * *", "exec.kcloud.php --kinfo");
    $unix->Popuplate_cron_make("ksrn-clean", "5 0 * * *", "$fname --clean-log");
    $unix->Popuplate_cron_make("ksrn-stats", "*/10 * * * *", "exec.ksrn.statistics.php");
    $unix->Popuplate_cron_make("ksrn-status", "* * * * *", "$fname --status");

    foreach ($files as $cronf){
        if(!is_file("/etc/cron.d/$cronf")){continue;}
        $cronf_md5=md5_file("/etc/cron.d/$cronf");
        if(!isset($MD5START[$cronf])){$MD5START[$cronf]=null;}
        if($cronf_md5<>$MD5START[$cronf]){
            $RESTART=true;
        }
    }

    if($RESTART){
        $unix->ToSyslog("Restarting cron service for Reputation service scheduled tasks",false,"krsn");
        UNIX_RESTART_CRON();
    }

}

function install(){
    build_progress(50,"{installing}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KSRNEnable",1);
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    build_progress(50,"{checking_license_status}");
    shell_exec("$php /usr/share/artica-postfix/exec.kcloud.php --kinfo");
    if(is_file("/etc/init.d/go-shield-server")) {
        build_progress(70, "{restarting}");
        shell_exec("/etc/init.d/go-shield-server restart");
    }
    build_progress(100, "{success}");


}

function _out($text):bool{
    shield_daemon($text);
    $unix=new unix();
    $unix->ToSyslog("[START] $text",false,"krsn");
    $date=date("H:i:s");
    echo "Starting......: $date [INIT]: {$GLOBALS["TITLENAME"]}, $text\n";
    return true;
}
function shield_daemon($text){
    $sdate=date("Y-m-d H:i:s");
    $lineToSave="$sdate,000 [".getmypid()."] [SCRIPT]: ".$text;
    $f = @fopen("/var/log/theshields-daemon.log", 'a');
    @fwrite($f, "$lineToSave\n");
    @fclose($f);

}