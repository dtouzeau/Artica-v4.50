<?php

ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);

$GLOBALS["NOFW"]=false;
$GLOBALS["NOCHECK"]=false;
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["NO_RELOAD"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NO_FIREHOL"]=false;
$GLOBALS["CLUSTER"]=false;
$GLOBALS["NO_EXTERNAL_SCRIPTS"]=false;
$GLOBALS["NO_VERIF_ACLS"]=false;
$GLOBALS["FIREHOL"]=false;
$GLOBALS["WATCHDOG"]=false;
$GLOBALS["PERCENT_PR"]=0;
$GLOBALS["MUST_RESTART"]=false;
$GLOBALS["NOACLS"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}

if(preg_match("#--no-acls#",implode(" ",$argv))){$GLOBALS["NOACLS"]=true;}
if(preg_match("#--nofw#",implode(" ",$argv))){$GLOBALS["NOFW"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--cluster#",implode(" ",$argv))){$GLOBALS["CLUSTER"]=true;}
if(preg_match("#--noexternal-scripts#",implode(" ",$argv))){$GLOBALS["NO_EXTERNAL_SCRIPTS"]=true;}
if(preg_match("#--noverifacls#",implode(" ",$argv))){$GLOBALS["NO_VERIF_ACLS"]=true;}
if(preg_match("#--no-reload#",implode(" ",$argv))){$GLOBALS["NO_RELOAD"]=true;}
if(preg_match("#--firehol#",implode(" ",$argv))){$GLOBALS["FIREHOL"]=true;}
if(preg_match("#--nowatch#",implode(" ",$argv))){$GLOBALS["WATCHDOG"]=false;}
if(preg_match("#--restart#",implode(" ",$argv))){$GLOBALS["MUST_RESTART"]=true;}
if(preg_match("#--percent-pr=([0-9])+#",implode(" ",$argv),$re)){$GLOBALS["PERCENT_PR"]=$re[1];}

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.ntlm.inc');
include_once(dirname(__FILE__).'/ressources/class.http_access_defaults.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.useragents.inc');
include_once(dirname(__FILE__).'/ressources/class.tcp_outgoing_interface.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.ftp.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.common.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.tcp_outgoing_mark.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.http_reply_access.inc");

$unix                   =new unix();
$SQUIDEnable            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
$SquidReconfigureAtBoot = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidReconfigureAtBoot"));
$STOP_BOOT              = true;
if($GLOBALS["FORCE"]){$STOP_BOOT=false;}

if($SQUIDEnable==0){
    build_progress("Proxy Service disabled, die..",110);
    exit();
}
if(isset($argv[1])){ if($argv[1]=="--ports"){ if($SquidReconfigureAtBoot==1){$STOP_BOOT=false;} } }

if($STOP_BOOT) {
    if ($unix->ServerRunSince() < 2) {
        squid_admin_mysql(1, "Aborting configuration, server just booting",
            implode(" ", $argv), __FILE__, __LINE__);
        build_progress("Please retry (server just booting)", 110);
        exit();
    }
}

if(is_file("/usr/share/artica-postfix/ldap.py")){@unlink("/usr/share/artica-postfix/ldap.py");}


$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
if(count($pids)>3){
    build_progress("No more than 3 processes, die..",110);
    exit();
}

if(isset($argv[1])){
    $GLOBALS["MAINCMD"]=implode(" ",$argv);
    $unix->ToSyslog("Executed with parameters [".str_replace(__FILE__,"",$GLOBALS["MAINCMD"])."] ",
        false,basename(__LINE__));
    remove_limits();missing_files();

    if(preg_match("#--no-firehol#", implode(" ",$argv),$re)){$GLOBALS["NO_FIREHOL"]=true;}
    if($argv[1]=="--die"){die();exit;}
    if($argv[1]=="--articarest"){articarest();exit;}
    if($argv[1]=="--parents"){parents();exit;}
    if($argv[1]=="--auth"){Authentication();exit;}
    if($argv[1]=="--update-adlap"){Authentication_update_adladp();exit;}
    if($argv[1]=="--nochek"){$GLOBALS["NOCHECK"]=true;$GLOBALS["NO_VERIF_ACLS"]=true;}
    if($argv[1]=="--no-caches"){deny_cache();exit();}
    if($argv[1]=="--plugins"){plugins();exit();}
    if($argv[1]=="--ports"){ports();exit();}
    if($argv[1]=="--chk-port"){CheckIptablesPorts(0);exit();}
    if($argv[1]=="--logging"){logging(0);exit();}
       if($argv[1]=="--usersagents"){usersagents();exit();}
    if($argv[1]=="--identd-enable"){identd_enable();exit();}
    if($argv[1]=="--identd-disable"){identd_disable();exit();}
    if($argv[1]=="--ntlmfake-disable"){ntlmfake_disable();exit();}
    if($argv[1]=="--ntlmfake-enable"){ntlmfake_enable();exit();}
    if($argv[1]=="--missing"){missing_files();exit();}
    if($argv[1]=="--icap"){icap_silent();exit();}
    if($argv[1]=="--icap-silent"){icap_silent();exit();}
    if($argv[1]=="--denys-sources"){DenyFromSources();exit();}
    if($argv[1]=="--parents-test"){parents_tests();exit();}
    if($argv[1]=="--common"){common(0,true);exit();}
    if($argv[1]=="--bandwidth"){acls_bandwidth();exit();}
    if($argv[1]=="--deny-final"){deny_final();exit;}
    if($argv[1]=="--http-access-conf"){http_access_conf();exit;}
    if($argv[1]=="--limits"){remove_limits();exit;}
    if($argv[1]=="--limitr"){removelimits_online();exit;}
    if($argv[1]=="--enable-acls"){enable_acls();exit();}
    if($argv[1]=="--disable-acls"){disable_acls();exit();}
    if($argv[1]=="--enable-gsb"){die();}
    if($argv[1]=="--disable-gsb"){disable_gsb();exit();}
    if($argv[1]=="--enable-hotspot"){enable_hotspot();exit();}
    if($argv[1]=="--disable-hotspot"){disable_hotspot();exit();}
    if($argv[1]=="--http-access-default"){http_access_default(true);exit();}
    if($argv[1]=="--configure-cache"){configure_cache();exit();}
    if($argv[1]=="--install-cache"){install_cache();exit();}
    if($argv[1]=="--enable-cache"){enable_cache();exit();}
    if($argv[1]=="--disable-cache"){disable_cache();exit();}
    if($argv[1]=="--enable-restful"){enable_restful();exit();}
    if($argv[1]=="--disable-restful"){disable_restful();exit();}
    if($argv[1]=="--ufdbclient"){ufdbclient();exit();}
    if($argv[1]=="--outgoingaddr"){tcp_outgoing_address();exit();}
    if($argv[1]=="--outgoingmark"){tcp_outgoing_mark();exit;}
    if($argv[1]=="--reply-access"){http_reply_access();exit;}
    if($argv[1]=="--general"){build_general_config();exit;}
    if($argv[1]=="--ftp"){squidftp();exit();}
    if($argv[1]=="--dns"){dns_tuning();exit();}
    if($argv[1]=="--cache-tweaks"){cache_tweaks();exit();}
    if($argv[1]=="--timeouts"){timeouts();exit();}
    if($argv[1]=="--mactouuid"){mactouuid();exit;}
    if($argv[1]=="--uuidurgency"){mactouuid_remove();exit;}
    if($argv[1]=="--acls"){BuildAcls();exit;}

    $unix->ToSyslog("Unknown parameters [".str_replace(__FILE__,"",$GLOBALS["MAINCMD"])."] ",
        false,basename(__FILE__));

    die();

}

$unix->ToSyslog("Executed without any parameter",
    false,basename(__FILE__));
$GLOBALS["MAINCMD"]=__FILE__;
xstart();

function BuildAcls():bool{
    $squid_ssl=new squid_ssl();
    $squid_ssl->BuildSSlBumpRulesForArticaRest();
    http_access_default(false,true);
    return true;
}
function BuildSSLForArticaRest():bool{
    $squid_ssl=new squid_ssl();
    $squid_ssl->BuildSSlBumpRulesForArticaRest();
    return true;
}
function articarest(){
    $http_access_defaults=new http_access_defaults();
    $http_access_defaults->CommandLineForArticaRest();
}



function build_progress($text,$pourc){
    $echotext=$text;
    $echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext (exec.squid.global.access.php)\n";
    $cachefile=PROGRESS_DIR ."/squid.access.center.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
    if($GLOBALS["PERCENT_PR"]>0) {
        $array["POURC"] = $GLOBALS["PERCENT_PR"];
        $array["TEXT"] = "{$pourc}% - $text";
        @file_put_contents(PROGRESS_DIR."/wizard.progress",
            serialize($array));
        @file_put_contents(PROGRESS_DIR."/msktutils.progress",
            serialize($array));
    }
    $function=null;$line=null;
    if(function_exists("debug_backtrace")){
        $trace=@debug_backtrace();
        if(isset($trace[1])){
            if(isset($trace[1]["function"])){$function=$trace[1]["function"]; }
            if(isset($trace[1]["line"])){ $line=$trace[1]["line"];}
        }
    }

    if(!function_exists("openlog")){return true;}
    openlog($echotext." [$function L.$line]", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}

function missing_files(){
    $lgpack="/usr/share/squid-langpack/templates";
    $errdet="/usr/share/artica-postfix/bin/install/squid/error-details.txt";
    if(!is_file("$lgpack/ERR_PROTOCOL_UNKNOWN")){
        if(is_file("$lgpack/ERR_ACCESS_DENIED")){
            @copy("$lgpack/ERR_ACCESS_DENIED","$lgpack/ERR_PROTOCOL_UNKNOWN");
        }
    }
    if(!is_dir($lgpack)){@mkdir($lgpack,0755,true);}
    if(is_file("$lgpack/ERR_PROTOCOL_UNKNOWN")){@chown("$lgpack/ERR_PROTOCOL_UNKNOWN","squid");}
    if(!is_file("$lgpack/error-details.txt")){@copy($errdet,"$lgpack/error-details.txt");}
    if(!is_file("/usr/share/squid3/icons/SN.png")){
        @copy("/usr/share/artica-postfix/img/SN.png","/usr/share/squid3/icons/SN.png");
    }
    $fnames[]="/usr/share/squid3/icons/SN.png";
    $fnames[]="$lgpack/ERR_PROTOCOL_UNKNOWN";
    $fnames[]="$lgpack/error-details.txt";
    foreach ($fnames as $spath){
        if(!is_file($spath)){continue;}
        @chown($spath,"squid");
    }

}
function acls_bandwidth_progress($text,$pourc){
    $echotext=$text;
    $echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile="/usr/share/artica-postfix/ressources/logs/web/squid.bandwww.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);


}
function acls_bandwidth():bool{
    @unlink("/usr/share/artica-postfix/ressources/logs/web/bandwidth.error");
    $band=new squid_bandwith_builder();
    if(!$band->compile()){
        acls_bandwidth_progress("{limit_rate} {failed}",110);
        @touch("/usr/share/artica-postfix/ressources/logs/web/bandwidth.error");
        return false;
    }
    return true;
}

function removelimits_online():bool{
    $unix=new unix();
    $sysctl=$unix->find_program("sysctl");
    shell_exec("$sysctl -w fs.nr_open=2147483584");
    $pgrep=$unix->find_program("pgrep");
    $prlimit=$unix->find_program("prlimit");
    exec("$pgrep -l -f \"(squid|squid-)\" 2>&1",$results);
    foreach ($results as $line){
        if(!preg_match("#^([0-9]+).*?squid#",$line,$re)){continue;}
        if(!$unix->process_exists($re[1])){continue;}
        echo "Fix no limit on Proxy service pid {$re[1]}\n";
        shell_exec("$prlimit --nofile=2147483584:2147483584 --pid={$re[1]}");
    }
    $results=array();
    exec("$pgrep -l -f \"unbound\" 2>&1",$results);
    foreach ($results as $line){
        if(!preg_match("#^([0-9]+)\s+#",$line,$re)){continue;}
        if(!$unix->process_exists($re[1])){continue;}
        echo "Fix no limit on unbound pid {$re[1]}\n";
        shell_exec("$prlimit --nofile=2147483584:2147483584 --pid={$re[1]}");
    }
    return true;
}

function remove_limits():bool{
    $unix=new unix();
    $unix->SystemSecurityLimitsConf();
    return removelimits_online();
}

function mactouuid_remove(){
    build_progress("{disable_emergency_mode}",10);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("MacToUidUrgency", 0);
    $unix=new unix();
    build_progress("{disable_emergency_mode}",90);
    system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
    remove_limits();
    build_progress("{disable_emergency_mode} {success}",100);
}

function mactouuid(){
    remove_limits();
    system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
}
function identd_enable(){
    build_progress("{enable}...",10);
    sleep(2);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidEnableIdentdService", 1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFakeAuth", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidExternLDAPAUTH", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidStandardLDAPAuth", 0);
    xstart();
}
function ntlmfake_enable(){
    build_progress("{enable_feature} {ntlm_fake_auth}...",10);
    sleep(2);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UseNativeKerberosAuth", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidEnableIdentdService", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidExternLDAPAUTH", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidStandardLDAPAuth", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFakeAuth", 1);
    Authentication();
}
function ntlmfake_disable(){
    build_progress("{disable_feature} {ntlm_fake_auth}...",10);
    sleep(2);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFakeAuth", 0);
    Authentication();
}
function identd_disable(){
    build_progress("{disable}...",10);
    sleep(2);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidEnableIdentdService", 0);
    xstart();
}

function crc32Of($aclfile,$array=array()):array{
    $array[$aclfile]=crc32_file($aclfile);
    $f=explode("\n",@file_get_contents($aclfile));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^acl\s+.*?\"(.+?)\"#",$line,$re)){
            $array[$re[1]]=crc32_file($re[1]);
        }

    }

    return $array;
}
function icap_silent():bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();

    if(is_file(PROGRESS_DIR."/squid-config-failed.tar.gz")){
        @unlink("/usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz");
    }

    build_progress("{building}",45);
    $crcs=array();
    $crcs=crc32Of("/etc/squid3/acls_center.conf",$crcs);
    $crcs=crc32Of("/etc/squid3/icap.conf",$crcs);


    $aclGen=new squid_acls();
    $aclGen->Build_Acls(true);
    @file_put_contents("/etc/squid3/acls_center.conf", @implode("\n",$aclGen->acls_array));

    build_progress("{building}",50);

    $icap=new icap();
    $icap->build_services();

    build_progress("{checking}",55);
    $RELOADCHECK=false;
    foreach ($crcs as $filename=>$crc){
        $crc2=crc32_file($filename);
        if($crc==$crc2){continue;}
        $RELOADCHECK=true;
    }

    if(!$RELOADCHECK){
        build_progress("{done} {no_change}",100);
        return true;
    }

    $squid_checks=new squid_checks();
    if(!$squid_checks->squid_parse()){
        build_progress("{failed2}",110);
        return false;
    }
    if(!$GLOBALS["NO_RELOAD"]) {
        build_progress("{done} {reloading_proxy_service}", 95);
        $squidbin = $unix->find_program("squid");
        if (is_file($squidbin)) {
            squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
            system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
            cluster_mode();
        }
        build_progress("{done} {reloading_proxy_service}", 96);
        system("$php /usr/share/artica-postfix/exec.squid.watchdog.php --icap");
        system("$php /usr/share/artica-postfix/exec.squid.disable.php --syslog");
    }

    build_progress("{done} {reloading_proxy_service}",100);
    return true;
}

function timeouts(){
    $unix=new unix();
    ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
    build_progress("{starting} {timeouts}",15);
    $sock=new sockets();
    $sock->REST_API("/proxy/config/timeouts/noreload");

    $squid_checks=new squid_checks();

    if(!$squid_checks->squid_parse()){
        build_progress("{failed2}",110);
        return;
    }
    if(!$GLOBALS["NO_RELOAD"]) {
        build_progress("{done} {reloading_proxy_service}", 100);
        $squidbin = $unix->find_program("squid");
        if (is_file($squidbin)) {
            squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
            system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
            cluster_mode();
        }
    }


}
function squidftp(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);


    if(is_file("/usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz")){
        @unlink("/usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz");
    }
    build_progress("{starting} {squid_ftp_user}",15);
    $squid_ftp=new squid_ftp();
    $squid_ftp->build();
    build_progress("{starting} {squid_ftp_user}",50);

    $squid_checks=new squid_checks();

    if(!$squid_checks->squid_parse()){
        build_progress("{failed2}",110);
        return;
    }
    if(!$GLOBALS["NO_RELOAD"]) {
        build_progress("{done} {reloading_proxy_service}", 100);
        $squidbin = $unix->find_program("squid");
        if (is_file($squidbin)) {
            squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
            system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
            cluster_mode();
        }
    }


}
function build_general_config_progress($text,$pourc){
    $echotext=$text;
    $echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext (exec.squid.global.access.php)\n";
    $cachefile="/usr/share/artica-postfix/ressources/logs/squid.general.config.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));


}
function build_general_config(){

    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    build_general_config_progress("{scanning}...",25);
    $md5Start=global_md5_dir();

    shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.templates.php >/dev/null 2>&1 &");
    build_general_config_progress("{configuring}",30);



    build_general_config_progress("{configuring}",50);
    if(!isset($GLOBALS["SQUIDBEE"])){$GLOBALS["SQUIDBEE"]=new squidbee();}
    $GLOBALS["SQUIDBEE"]->access_logs();

    build_general_config_progress("{configuring}",90);
    $squid_ftp=new squid_ftp();
    $squid_ftp->build();

    build_general_config_progress("{scanning}...",95);
    $md5Stop=global_md5_dir();

    if($md5Start==$md5Stop){
        build_general_config_progress("{no_change}...",100);
        return true;
    }

    $squid_checks=new squid_checks();

    if(!$squid_checks->squid_parse()){
        build_general_config_progress("{starting} {configuring} {failed}",110);
        return false;
    }

    if(!$GLOBALS["NO_RELOAD"]) {
        system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
    }
    build_general_config_progress("{configuring} {done}",100);
}


function enable_hotspot(){
    $unix=new unix();
    build_progress("{hotspot_auth} {enable_feature}",15);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSquidMicroHotSpot", 1);
    build_progress("{starting} {building_acls_proxy_objects}",35);
    $aclGen=new squid_ntlm();
    $aclGen->build();


    $squidbin=$unix->find_program("squid");
    $http_access_defaults=new http_access_defaults();
    $http_access_defaults->http_access_deny_final();

    $chmod=$unix->find_program("chmod");
    shell_exec("$chmod -R 0777 /home/squid/hotspot");

    @mkdir("/home/squid/hotspot/sessions",0755,true);
    @chown("/home/squid/hotspot/sessions","www-data");
    @chgrp("/home/squid/hotspot/sessions", "www-data");

    @mkdir("/home/squid/hotspot/caches",0755,true);
    @chown("/home/squid/hotspot/caches","www-data");
    @chgrp("/home/squid/hotspot/caches", "www-data");






    if(!$GLOBALS["NO_RELOAD"]) {
        if (is_file($squidbin)) {
            squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
            system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
            cluster_mode();
        }
    }

    $php=$unix->LOCATE_PHP5_BIN();

    if(!is_file("/etc/init.d/artica-hotspot")){
        system("$php /usr/share/artica-postfix/exec.hotspot-service.php --install");
        $unix->framework_exec("exec.hotspot-service.php --start");
    }else{
        $unix->framework_exec("exec.hotspot-service.php --restart");
    }

    build_progress("{installing} {reconfiguring}",12);
    $squid_url_rewrite=new squid_url_rewrite();
    $squid_url_rewrite->build();



    if(!is_file("/etc/init.d/redis-server")) {
        system("/usr/sbin/artica-phpfpm-service -install-redis");
    }

    build_progress("{done}",100);

}

function isIncludeFinal():bool{

    $WRITE=true;
    $f=explode("\n",@file_get_contents("/etc/squid3/http_access.conf"));
    foreach ($f as $line){
        $line=trim($line);
        if(preg_match("#^include.*?http_access_final\.conf#",$line)){$WRITE=false;break;}
    }

    if($WRITE){
        system("/usr/sbin/artica-phpfpm-service -proxy-http-access-heads");
        return true;
    }
    return false;

}

function disable_hotspot(){
    $unix       = new unix();
    $aclGen     = new squid_ntlm();
    $php        = $unix->LOCATE_PHP5_BIN();
    build_progress("{hotspot_auth} {disable_feature}",15);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSquidMicroHotSpot", 0);
    build_progress("{starting} {building_acls_proxy_objects}",35);
    $aclGen->build();

    build_progress("{starting} {building_acls_proxy_objects}",40);
    shell_exec("$php /usr/share/artica-postfix/exec.squid.templates.php --single ERR_ACCESS_DENIED");

    if(!isIncludeFinal()) {
        build_progress("{starting} {building_acls_proxy_objects}",45);
        $http_access_defaults = new http_access_defaults();
        $http_access_defaults->http_access_deny_final();
    }
    build_progress("{installing} {reconfiguring}",46);
    $squid_url_rewrite=new squid_url_rewrite();
    $squid_url_rewrite->build();


    $squidbin=$unix->find_program("squid");
    build_progress("{starting} {building_acls_proxy_objects}",50);

    if(is_file("/etc/cron.d/hotspot-task")) {
        @unlink("/etc/cron.d/hotspot-task");
    }

    if(is_file("/etc/cron.d/hotspot-databases")){
        @unlink("/etc/cron.d/hotspot-databases");
    }

    system("/etc/init.d/cron reload");
    system("$php /usr/share/artica-postfix/exec.hotspot-service.php --uninstall");
    if( is_file($squidbin)){
        squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
        system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
        cluster_mode();
    }
    build_progress("{done}",100);

}

function CleanHostpotCache(){
    $TemplateDir="/home/squid/hotspot";
    if (!$handle = opendir($TemplateDir)) {return;}
    while (false !== ($file = readdir($handle))) {
        if ($file == ".") {continue;}
        if ($file == "..") {continue;}
        if(!is_numeric($file)){continue;}
        $TargetDir2="$TemplateDir/$file/files";
        if(!is_dir($TargetDir2)){continue;}
        if (!$handle2 = opendir($TargetDir2)) {return;}
        while (false !== ($file2 = readdir($handle2))) {
            $fullpath="$TargetDir2/$file2";
            if(is_file($fullpath)){@unlink($fullpath);}
        }
    }


}

function enable_acls(){
    build_progress("{PROXY_ACLS} {enable_feature}",15);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDACLsEnabled", 1);
    xstart();
    build_progress("{done}",100);
}

function disable_gsb(){
    $unix=new unix();
    build_progress("Google Safe Browsing: {disable_feature}",15);
    sleep(1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableGoogleSafeBrowsing", 0);
    $squidbin=$unix->LOCATE_SQUID_BIN();
    build_progress("Google Safe Browsing: {reloading}",50);
    $f[]="# Google Safe Browsing is disabled";
    $f[]="#----------------------------------";
    @file_put_contents("/etc/squid3/SafeBrowsing.conf", @implode("\n", $f));

    if(is_file("/etc/cron.d/GoogleSafeCountCache")){
        @unlink("/etc/cron.d/GoogleSafeCountCache");
        UNIX_RESTART_CRON();
    }
    if(!$GLOBALS["NO_RELOAD"]) {
        system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
    }
    build_progress("{done}",100);
}


function configure_cache(){


    build_progress("{PROXY_CACHE_FEATURE} {enable_feature}",10);
    $squid_common=new squid_common();
    $squid_common->build();

    build_progress("{PROXY_CACHE_FEATURE} {enable_feature}",15);
    system("/usr/sbin/artica-phpfpm-service -proxy-build-caches");

    build_progress("{PROXY_CACHE_FEATURE} {enable_feature}",20);
    $squid_refresh_pattern=new squid_refresh_pattern();
    $squid_refresh_pattern->build();

    build_progress("{PROXY_CACHE_FEATURE} {enable_feature}",30);
    $HyperCacheSquid=new HyperCacheSquid();
    $HyperCacheSquid->build();

    squid_admin_mysql(1, "{restarting_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
    build_progress("{stopping_service}",50);
    system("/etc/init.d/squid stop");
    build_progress("{stopping_service}",50);
    system("/etc/init.d/squid stop");
    build_progress("{starting_service}",80);
    system("/usr/sbin/artica-phpfpm-service -start-proxy");

}

function install_cache(){
    build_progress("{PROXY_CACHE_FEATURE} {enable_feature}",15);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidCachesProxyEnabled", 1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidDisableHyperCacheDedup", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HyperCacheStoreID", 1);
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    system("$php /usr/share/artica-postfix/exec.squid.verify.caches.php");
    build_progress("{PROXY_CACHE_FEATURE} {enable_feature}",20);
    $squid_refresh_pattern=new squid_refresh_pattern();
    $squid_refresh_pattern->build();

    build_progress("{PROXY_CACHE_FEATURE} {enable_feature}",30);
    $HyperCacheSquid=new HyperCacheSquid();
    $HyperCacheSquid->build();
    system("/etc/init.d/squid reload");
    build_progress("{PROXY_CACHE_FEATURE} {enable_feature} {done}",100);

}
function enable_restful_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'){
    $pieces = array();
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $pieces []= $keyspace[random_int(0, $max)];
    }
    return implode('', $pieces);
}

function enable_restful(){
    build_progress("RESTFul API {enable_feature}",15);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDRESTFulEnabled", 1);

    $SquidRestFulApi=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRestFulApi"));
    echo "SquidRestFulApi = $SquidRestFulApi\n";
    if(strlen($SquidRestFulApi)<32){
        $kzy=enable_restful_str(32);
        echo "SquidRestFulApi = $kzy\n";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidRestFulApi",$kzy);
    }else{
        echo "SquidRestFulApi = $SquidRestFulApi\n";
    }


    build_progress("RESTFul API {enable_feature} {done}",100);
}
function disable_restful(){
    build_progress("RESTFul API {disable_feature}",15);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDRESTFulEnabled", 0);
    build_progress("RESTFul API {disable_feature} {done}",100);
}

function enable_cache(){
    build_progress("{PROXY_CACHE_FEATURE} {enable_feature}",15);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidCachesProxyEnabled", 1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidDisableHyperCacheDedup", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HyperCacheStoreID", 1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableAnyCache", 0);
    system("/usr/sbin/artica-phpfpm-service -proxy-build-caches");

    build_progress("{PROXY_CACHE_FEATURE} {enable_feature}",20);
    $squid_refresh_pattern=new squid_refresh_pattern();
    $squid_refresh_pattern->build();

    build_progress("{PROXY_CACHE_FEATURE} {enable_feature}",30);
    $HyperCacheSquid=new HyperCacheSquid();
    $HyperCacheSquid->build();

    squid_admin_mysql(1, "{restarting_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
    build_progress("{stopping_service}",50);
    system("/etc/init.d/squid stop");
    build_progress("{stopping_service}",50);
    system("/etc/init.d/squid stop");
    build_progress("{starting_service}",80);
    system("/usr/sbin/artica-phpfpm-service -start-proxy");
    build_progress("{PROXY_CACHE_FEATURE} {enable_feature} {done}",100);




}
function disable_cache(){
    build_progress("{PROXY_CACHE_FEATURE} {disable_feature}",15);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidCachesProxyEnabled", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidDisableHyperCacheDedup", 1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HyperCacheStoreID", 0);

    build_progress("{PROXY_CACHE_FEATURE} {disable_feature}",20);
    $squid_refresh_pattern=new squid_refresh_pattern();
    $squid_refresh_pattern->build();

    build_progress("{PROXY_CACHE_FEATURE} {disable_feature}",30);
    $HyperCacheSquid=new HyperCacheSquid();
    $HyperCacheSquid->build();


    $caches=new SquidCacheCenter();
    $caches->build();
    squid_admin_mysql(1, "{restarting_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
    build_progress("{stopping_service}",50);
    system("/etc/init.d/squid stop");
    build_progress("{stopping_service}",50);
    system("/etc/init.d/squid stop");
    build_progress("{starting_service}",80);
    system("/usr/sbin/artica-phpfpm-service -start-proxy");
    build_progress("{PROXY_CACHE_FEATURE} {disable_feature} {done}",100);
}



function disable_acls(){
    build_progress("{PROXY_ACLS} {disable_feature}",15);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDACLsEnabled", 0);
    xstart();
    build_progress("{done}",100);
}

function usersagents(){
    $unix=new unix();
    build_progress("{starting} UserAgents",15);
    $b=new useragents();
    build_progress("{starting} UserAgents",50);
    $b->ACLS_ALL();
    $squid_checks=new squid_checks();

    if(!$squid_checks->squid_parse()){
        build_progress("{failed2}",110);
        return;
    }

    if(!$GLOBALS["NO_RELOAD"]) {
        $squidbin = $unix->find_program("squid");
        if (is_file($squidbin)) {
            squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
            $articarest = $unix->find_program("artica-phpfpm-service");
            if (is_file($articarest)) {
                system("$articarest -rest-api /proxy/nohup/reload");
            } else{
                system("$squidbin -k reconfigure");
            }
            cluster_mode();
        }
    }
    build_progress("{done} {reloading_proxy_service}",100);

}

function http_reply_access(){
    $unix=new unix();
    build_progress("{starting} {building_acls_proxy_objects}",35);
    $aclGen=new squid_acls();
    $aclGen->Build_Acls(true);
    @file_put_contents("/etc/squid3/acls_center.conf", @implode("\n",$aclGen->acls_array));


    $http_reply_access=new http_reply_access();
    $http_reply_access->build();

    $squid_checks=new squid_checks();
    if(!$squid_checks->squid_parse()){
        build_progress("{starting} {building_acls_proxy_objects} {failed}",110);
        squid_admin_mysql(0, "{building_acls_proxy_objects} {failed} (".__FUNCTION__.")", @implode("\n",$squid_checks->results),__FILE__,__LINE__);
        return;
    }

    build_progress("{done} {reloading_proxy_service}",100);
    if(!$GLOBALS["NO_RELOAD"]) {
        $squidbin = $unix->find_program("squid");
        if (is_file($squidbin)) {
            squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
            system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
            cluster_mode();
        }
    }
}

function tcp_outgoing_mark(){
    $unix=new unix();
    build_progress("{starting} {building_acls_proxy_objects}",35);
    $aclGen=new squid_acls();
    $aclGen->Build_Acls(true);
    @file_put_contents("/etc/squid3/acls_center.conf", @implode("\n",$aclGen->acls_array));


    $tcp_outgoing_mark=new tcp_outgoing_mark();
    $tcp_outgoing_mark->build();

    $squid_checks=new squid_checks();
    if(!$squid_checks->squid_parse()){
        build_progress("{starting} {building_acls_proxy_objects} {failed}",110);
        squid_admin_mysql(0, "Modify outgoing mark failed (".__FUNCTION__.")", @implode("\n",$squid_checks->results),__FILE__,__LINE__);
        return;
    }

    build_progress("{done} {reloading_proxy_service}",100);
    if(!$GLOBALS["NO_RELOAD"]) {
        $squidbin = $unix->find_program("squid");
        if (is_file($squidbin)) {
            squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
            system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
            cluster_mode();
        }
    }

}

function tcp_outgoing_address(){
    $unix=new unix();

    if(!$GLOBALS["NO_VERIF_ACLS"]) {
        if (!isset($GLOBALS["ACL_BUILDED"])) {
            build_progress("{starting} {building_acls_proxy_objects}", 35);
            $aclGen = new squid_acls();
            $aclGen->Build_Acls(true);
            @file_put_contents("/etc/squid3/acls_center.conf", @implode("\n", $aclGen->acls_array));
            $GLOBALS["ACL_BUILDED"] = true;
        }
    }
    $tcp_outgoing_interface=new tcp_outgoing_interface();
    $tcp_outgoing_interface->build();

    if(!$GLOBALS["NO_VERIF_ACLS"]) {
        $squid_checks = new squid_checks();
        if (!$squid_checks->squid_parse()) {
            build_progress("{starting} {building_acls_proxy_objects} {failed}", 110);
            squid_admin_mysql(0, "Modify outgoing interface failed (" . __FUNCTION__ . ")", @implode("\n", $squid_checks->results), __FILE__, __LINE__);
            return;
        }
        build_progress("{done} {reloading_proxy_service}", 100);
        $squidbin = $unix->find_program("squid");
        if (is_file($squidbin)) {
            squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
            system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
            cluster_mode();
        }
    }
    build_progress("{done}", 100);
}

function logging($nobuildacl=0):bool{
    $unix=new unix();
    if($GLOBALS["NOACLS"]){
        $nobuildacl=1;
    }

    ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);

    $md5Calc[]="/etc/squid3/acls_center.conf";
    $md5Calc[]="/etc/squid3/logging.conf";

    foreach ($md5Calc as $filename){
        $md5Calc2[$filename]=md5_file($filename);
    }


    if(is_file("/usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz")){
        @unlink("/usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz");
    }
    build_progress("{starting} {logging}",15);
    $md5Start=global_md5_dir();

    if($nobuildacl==0){
        if(!isset($GLOBALS["ACL_BUILDED"])){
            build_progress("{starting} {logging} ACLS",30);
            $aclGen=new squid_acls();
            $aclGen->Build_Acls(true);
            @file_put_contents("/etc/squid3/acls_center.conf", @implode("\n",$aclGen->acls_array));
            $GLOBALS["ACL_BUILDED"]=true;
        }
    }

    if(!isset($GLOBALS["SQUIDBEE"])){$GLOBALS["SQUIDBEE"]=new squidbee();}
    $GLOBALS["SQUIDBEE"]->access_logs();
    build_progress("{starting} {logging}",50);

    if(!$GLOBALS["NO_VERIF_ACLS"]) {
        $squid_checks = new squid_checks();
        if (!$squid_checks->squid_parse()) {
            build_progress("{failed2}", 110);
            return false;
        }
    }

    $md5Stop=global_md5_dir();
    if($md5Start==$md5Stop){
        build_progress("{done} {no_change}",100);
        return true;
    }

    build_progress("{done} {reloading_proxy_service}",100);
    if(!$GLOBALS["NO_RELOAD"]) {
        $squidbin = $unix->find_program("squid");
        if (is_file($squidbin)) {
            squid_admin_mysql(2, "{reloading_proxy_service} after set access.log configurations (" . __FUNCTION__ . ")",
                null, __FILE__, __LINE__);
            system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
            cluster_mode();

        }
    }

    return true;
}

function common($startProgress,$single=false){

    build_progress("Building {general_settings}",$startProgress++);

    $squid_common=new squid_common();
    $squid_common->build();
    if($single){
        if(!$GLOBALS["NO_RELOAD"]) {
            $unix = new unix();
            $squidbin = $unix->LOCATE_SQUID_BIN();
            if (is_file($squidbin)) {
                squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
                system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
            }
        }
        cluster_mode();
        return;

    }
    build_progress("Building {general_settings} {done}",$startProgress++);
}

function deny_final(){
    $http_access_defaults=new http_access_defaults();
    $http_access_defaults->http_access_deny_final();
}
function connector_path():string{
    $BINFILE=ARTICA_ROOT."/bin/go-shield/client/external_acls_ldap/bin/go-squid-auth";
    return $BINFILE;
}
function update_go_squid_auth_version(){
    $unix = new unix();
    $gosquidauth_src_md5=null;
    $gosquidauth_dst_md5=null;
    $gosquidauth_src = connector_path();
    $libmem=new lib_memcached();
    $gosquidauth_dst = "/lib/squid3/go-squid-auth";
    $squidbin = $unix->LOCATE_SQUID_BIN();
    $cpbin = $unix->find_program("cp");
    if(is_file($gosquidauth_src)){$gosquidauth_src_md5 = md5_file($gosquidauth_src);}
    if(is_file($gosquidauth_dst)){$gosquidauth_dst_md5 = md5_file($gosquidauth_dst);}

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SQUID_AUTH_SRC",$gosquidauth_src_md5);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SQUID_AUTH_DST",$gosquidauth_dst_md5);

    if ($gosquidauth_src_md5 == $gosquidauth_dst_md5) {
        echo "[OK]: $gosquidauth_src_md5 == $gosquidauth_dst_md5 - Already updated\n";
        $Go_Shield_Connector_Version=trim($libmem->getKey("Go-Squid-Auth-Version"));
        if(trim($Go_Shield_Connector_Version)<>null){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Go-Squid-Auth-Version",$Go_Shield_Connector_Version);
        }
        return false;
    }


    if (is_file($gosquidauth_dst)) {@unlink($gosquidauth_dst);}

    $prc = 40;
    $UPDATED = false;
    for ($i = 1; $i < 30; $i++) {
        $i++;
        $prc++;
        shell_exec("$cpbin -f $gosquidauth_src $gosquidauth_dst");
        @chmod($gosquidauth_dst,0755);
        @chown($gosquidauth_dst,"squid");
        @chgrp($gosquidauth_dst,"squid");
        sleep(1);
        $gosquidauth_dst_md5 = md5_file($gosquidauth_dst);
        if ($gosquidauth_dst_md5 == $gosquidauth_src_md5) {
            $UPDATED = true;
            break;
        }
    }
    if ($UPDATED) {
        squid_admin_mysql(1, "The {$GLOBALS["TITLENAME"]} as been updated using md5 : $gosquidauth_src", null,__FILE__, __LINE__);
    }
    if(is_file($gosquidauth_src)){$gosquidauth_src_md5 = md5_file($gosquidauth_src);}
    if(is_file($gosquidauth_dst)){$gosquidauth_dst_md5 = md5_file($gosquidauth_dst);}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SQUID_AUTH_SRC",$gosquidauth_src_md5);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SQUID_AUTH_DST",$gosquidauth_dst_md5);


    $Go_Shield_Connector_Version=trim($libmem->getKey("Go-Squid-Auth-Version"));
    if(trim($Go_Shield_Connector_Version)<>null){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Go-Squid-Auth-Version",$Go_Shield_Connector_Version);
    }

    return true;
}

function update_go_squid_auth_ad_agent_version(){
    $unix = new unix();
    $gosquidauth_src_md5=null;
    $gosquidauth_dst_md5=null;
    $gosquidauth_src = ARTICA_ROOT."/bin/go-shield/client/external_acls_gc/bin/external_acls_ad_agent";
    $libmem=new lib_memcached();
    $gosquidauth_dst = "/lib/squid3/external_acls_ad_agent";
    $squidbin = $unix->LOCATE_SQUID_BIN();
    $cpbin = $unix->find_program("cp");
    if(is_file($gosquidauth_src)){$gosquidauth_src_md5 = md5_file($gosquidauth_src);}
    if(is_file($gosquidauth_dst)){$gosquidauth_dst_md5 = md5_file($gosquidauth_dst);}

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SQUID_AUTH_AD_AGENT_SRC",$gosquidauth_src_md5);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SQUID_AUTH_AD_AGENT_SRC",$gosquidauth_dst_md5);

    if ($gosquidauth_src_md5 == $gosquidauth_dst_md5) {
        echo "[OK]: $gosquidauth_src_md5 == $gosquidauth_dst_md5 - Already updated\n";
        $Go_Shield_Connector_Version=trim($libmem->getKey("Go-Squid-AD-Agent-Client-Version"));
        if(trim($Go_Shield_Connector_Version)<>null){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Go-Squid-AD-Agent-Client-Version",$Go_Shield_Connector_Version);
        }
        return false;
    }


    if (is_file($gosquidauth_dst)) {@unlink($gosquidauth_dst);}

    $prc = 40;
    $UPDATED = false;
    for ($i = 1; $i < 30; $i++) {
        $i++;
        $prc++;
        shell_exec("$cpbin -f $gosquidauth_src $gosquidauth_dst");
        @chmod($gosquidauth_dst,0755);
        @chown($gosquidauth_dst,"squid");
        @chgrp($gosquidauth_dst,"squid");
        sleep(1);
        $gosquidauth_dst_md5 = md5_file($gosquidauth_dst);
        if ($gosquidauth_dst_md5 == $gosquidauth_src_md5) {
            $UPDATED = true;
            break;
        }
    }
    if ($UPDATED) {
        squid_admin_mysql(1, "The {$GLOBALS["TITLENAME"]} as been updated using md5 : $gosquidauth_src", null,__FILE__, __LINE__);
    }
    if(is_file($gosquidauth_src)){$gosquidauth_src_md5 = md5_file($gosquidauth_src);}
    if(is_file($gosquidauth_dst)){$gosquidauth_dst_md5 = md5_file($gosquidauth_dst);}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SQUID_AUTH_AD_AGENT_SRC",$gosquidauth_src_md5);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SQUID_AUTH_AD_AGENT_SRC",$gosquidauth_dst_md5);


    $Go_Shield_Connector_Version=trim($libmem->getKey("Go-Squid-AD-Agent-Client-Version"));
    if(trim($Go_Shield_Connector_Version)<>null){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Go-Squid-AD-Agent-Client-Version",$Go_Shield_Connector_Version);
    }

    return true;
}

function build_go_squid_auth_syslog(){
    $conf="/etc/rsyslog.d/go-squid-auth.conf";
    $md5_start=null;
    if(is_file($conf)){$md5_start=md5_file($conf);}
    $h[]="if  (\$programname =='go-squid-auth') then {";
    $h[]=buildlocalsyslogfile("/var/log/go-shield/external_acl_ldap.log");
    $h[]="& stop";
    $h[]="}";

    @file_put_contents("/etc/rsyslog.d/go-squid-auth.conf",@implode("\n", $h));
    $md5_end=md5_file($conf);
    if($md5_end<>$md5_start) {
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }

}
function Authentication_update_adladp(){
    $squid_ntlm=new squid_ntlm();
    $squid_ntlm->ldap_auth_ad_update();

}

function Authentication(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
    $md5Start=global_md5_dir();
    $forceRestart=false;
    $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
    if($EnableExternalACLADAgent==1){
        $forceRestart = update_go_squid_auth_ad_agent_version();
    }
    else {
        $forceRestart = update_go_squid_auth_version();
    }


    build_go_squid_auth_syslog();
    if(is_file("/usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz")){
        @unlink("/usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz");
    }

    $SquidStandardLDAPAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStandardLDAPAuth"));
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    if($SquidStandardLDAPAuth==1){
        if($EnableActiveDirectoryFeature==1){
            shell_exec(" /usr/sbin/artica-phpfpm-service -ad-uninstall");
        }
        if(!is_file("/etc/init.d/slapd")){
            shell_exec("/usr/sbin/artica-phpfpm-service -install-ldap");
        }

    }

    shell_exec("$php /usr/share/artica-postfix/exec.hotspot.templates.php");

    if(!isset($GLOBALS["ACL_BUILDED"])){
        build_progress("{starting} {building_acls_proxy_objects}",35);
        $aclGen=new squid_acls();
        $aclGen->Build_Acls(true);
        @file_put_contents("/etc/squid3/acls_center.conf", @implode("\n",$aclGen->acls_array));
        $GLOBALS["ACL_BUILDED"]=true;
    }

    build_progress("{starting} {authentication}",40);
    $squid_ntlm=new squid_ntlm();
    $squid_ntlm->build();
    build_progress("{starting} {authentication}",45);
    http_access_default(55,true);



    $http_access_defaults=new http_access_defaults();
    $http_access_defaults->http_access_deny_final();

    if($GLOBALS["NO_VERIF_ACLS"]){
        system("$php /usr/share/artica-postfix/exec.negotiateauthenticator.php");
        build_progress("{done}",100);
        return true;
    }

    $md5End=global_md5_dir();

    if($md5Start==$md5End){
        if (!$forceRestart){
            build_progress("{done} {no_change}",100);
            system("$php /usr/share/artica-postfix/exec.negotiateauthenticator.php");
            return true;
        }

    }


    $squid_checks=new squid_checks();

    if(!$squid_checks->squid_parse()){
        squid_admin_mysql(0, "Check configuration failed ", null,__FILE__,__LINE__);
        build_progress("{failed2}",110);
        return false;
    }

    build_progress("{done} {reloading_proxy_service}",100);
    if(!$GLOBALS["NO_RELOAD"]) {
        $squidbin = $unix->find_program("squid");
        if (is_file($squidbin)) {
            squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
            system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
            cluster_mode();
        }
    }

    system("$php /usr/share/artica-postfix/exec.negotiateauthenticator.php");
    return true;

}

function parents_tests(){
    $GLOBALS["VERBOSE"]=true;
    $acls=new squid_parents();
    $acls->cache_peer();

}

function parents():bool{

    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();

    if(!isset($GLOBALS["ACL_BUILDED"])){
        build_progress("{starting} {building_acls_proxy_objects}",35);
        $aclGen=new squid_acls();
        $aclGen->Build_Acls(true);
        @file_put_contents("/etc/squid3/acls_center.conf", @implode("\n",$aclGen->acls_array));
        $GLOBALS["ACL_BUILDED"]=true;
    }


    build_progress("{starting} {building_parents_configuration}",35);
    $acls=new squid_parents();
    $acls->cache_peer();
    build_progress("{done} {reloading_proxy_service}",80);
    if(is_file("/etc/init.d/hypercache-service")){system("/etc/init.d/hypercache-service restart");}

    $sock=new sockets();
    if($sock->isFirehol()){
        system("$php /usr/share/artica-postfix/exec.firehol.php --reconfigure");

    }
    build_progress("{done} {reloading_proxy_service}",100);
    cluster_mode();
    exit(0);
}

function ufdbclient():bool{


    $unix=new unix();
    build_progress("{starting} {webfiltering}",55);
    $ufdbgclient=new squid_url_rewrite();
    $ufdbgclient->build();

    build_progress("{starting} {web_filter_policies}",58);
    if(!isset($GLOBALS["ACL_BUILDED"])){
        $aclGen=new squid_acls();
        $aclGen->Build_Acls(true);
        @file_put_contents("/etc/squid3/acls_center.conf", @implode("\n",$aclGen->acls_array));
        build_progress("{starting} {web_filter_policies}",59);
        $GLOBALS["ACL_BUILDED"]=true;
    }


    $squid_access_manager=new squid_access_manager();
    build_progress("{starting} {web_filter_policies}",60);
    $squid_access_manager->build_all();

    build_progress("{verify_global_configuration}",90);
    $squid_checks=new squid_checks();

    if(!$squid_checks->squid_parse()){
        build_progress("{verify_global_configuration} {failed2}",110);
        return false;
    }

    build_progress("{done} {reloading_proxy_service}",100);
    if(!$GLOBALS["NO_RELOAD"]) {
        $squidbin = $unix->find_program("squid");
        if (is_file($squidbin)) {
            squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
            system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
            cluster_mode();
        }
    }

    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.ufdbguard.rules.php");
    return true;
}


function plugins(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    if(is_file("/usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz")){
        @unlink("/usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz");
    }


    if(!is_file("/etc/squid3/logging.conf")){@touch("/etc/squid3/logging.conf");}


    build_progress("{starting} {squid_plugins}",15);
    build_progress("{starting} {authentication}",50);
    $squid_ntlm=new squid_ntlm();
    $squid_ntlm->build();


    build_progress("{starting} {webfiltering}",55);
    $ufdbgclient=new squid_url_rewrite();
    $ufdbgclient->build();

    build_progress("{verify_global_configuration}",90);
    $squid_checks=new squid_checks();

    if(!$squid_checks->squid_parse()){
        build_progress("{verify_global_configuration} {failed2}",110);
        return;
    }

    build_progress("{done} {reloading_proxy_service}",100);
    if(!$GLOBALS["NO_RELOAD"]) {
        $squidbin = $unix->find_program("squid");
        if (is_file($squidbin)) {
            squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
            system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
            cluster_mode();
        }
    }

}

function deny_cache(){
    $EnableHyperCacheProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHyperCacheProxy"));
    $unix=new unix();
    if(is_file("/usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz")){
        @unlink("/usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz");
    }

    if(!isset($GLOBALS["ACL_BUILDED"])){
        build_progress("{starting} {building_acls_proxy_objects}",20);
        $aclGen=new squid_acls();
        $aclGen->Build_Acls(true);
        @file_put_contents("/etc/squid3/acls_center.conf", @implode("\n",$aclGen->acls_array));
        $GLOBALS["ACL_BUILDED"]=true;
    }



    if($EnableHyperCacheProxy==1){
        build_progress("{starting} {deny_from_cache}",55);
        shell_exec("/usr/sbin/artica-phpfpm-service -proxy-parents");

    }


    $squid_checks=new squid_checks();

    if(!$squid_checks->squid_parse()){
        build_progress("{failed2}",110);
        return;
    }

    if(!$GLOBALS["NO_RELOAD"]){
        build_progress("{done} {reloading_proxy_service}",100);
        $squidbin=$unix->find_program("squid");
        if( is_file($squidbin)){
            squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
            system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
        }
    }

    cluster_mode();
}

function http_access_conf(){
    $aclGen=new squid_acls();
    $aclGen->Build_Acls(true);
    @file_put_contents("/etc/squid3/acls_center.conf", @implode("\n",$aclGen->acls_array));
    system("/usr/sbin/artica-phpfpm-service -proxy-http-access-heads");
}



function ports()
{
    if (isset($GLOBALS["FUNCTION_PORTS_EXECUTED"])) {
        return;
    }
    $GLOBALS["FUNCTION_PORTS_EXECUTED"] = true;
    if (!isset($GLOBALS["SQUIDBEE"])) {
        $GLOBALS["SQUIDBEE"] = new squidbee();
    }
    $unix = new unix();
    $php = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $q = new lib_sqlite("/home/artica/SQLITE/proxy.db");
    if (is_file("/usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz")) {
        @unlink("/usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz");
    }
    system("/usr/sbin/artica-phpfpm-service -repair-proxy");

    build_progress("{starting} {listen_ports}", 15);


    if (!$GLOBALS["NO_VERIF_ACLS"]) {
        if (!is_file("/etc/squid3/squid.conf")) {
            echo "* * * * /etc/squid3/squid.conf (No such file) * * * * *\n";
            build_progress("{starting} {reconfigure_proxy_service}", 30);
            system("$php /usr/share/artica-postfix/exec.squid.php --build --force --noverifacls");
            exit();
        }
    }

    if (!isset($GLOBALS["ACL_BUILDED"])) {
        build_progress("{starting} {building_acls_proxy_objects}", 40);
        $aclGen = new squid_acls();
        $aclGen->Build_Acls(true);
        @file_put_contents("/etc/squid3/acls_center.conf", @implode("\n", $aclGen->acls_array));

        build_progress("{starting} {building_acls_proxy_objects}", 45);
        system("/usr/sbin/artica-phpfpm-service -proxy-http-access-heads");

        $GLOBALS["ACL_BUILDED"] = true;
    }

    if (!is_file("/etc/squid3/logging.conf")) {
        xstart();
        exit();
    }

    build_progress("{starting} {listen_ports}", 48);
    system("/usr/sbin/artica-phpfpm-service -reconfigure-proxy");



    $GLOBALS["SQUIDBEE"]->access_logs();

    build_progress("{starting} {verify_ssl_configuration}", 55);
    $squid_ssl = new squid_ssl();
    $squid_ssl->build_ssl_path();
    $squid_ssl->build();



    build_progress("{starting} {listen_ports}", 57);
    $tcp_outgoing_interface = new tcp_outgoing_interface();
    $tcp_outgoing_interface->build();

    if (!$q->FIELD_EXISTS("proxy_ports", "AuthParentPort")) {
        $q->QUERY_SQL("ALTER TABLE proxy_ports ADD AuthParentPort INTEGER NOT NULL DEFAULT '0'");
    }

    build_progress("{starting} {authentication}", 58);
    $squid_ntlm = new squid_ntlm();
    $squid_ntlm->build();

    $squid_checks = new squid_checks();

    if (!$squid_checks->squid_parse()) {
        build_progress("{failed2}", 110);
        return;
    }
    CheckIptablesPorts(58);
    CheckNATPorts(70);

    if($GLOBALS["FIREHOL"]){
        $GLOBALS["NOFW"]=false;
    }

    if (!$GLOBALS["NOFW"]) {
        system("/usr/sbin/artica-phpfpm-service -iptables-routers");
        if($GLOBALS["FIREHOL"]) {
            $FireHolEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
            if ($FireHolEnable == 1) {
                system("$php /usr/share/artica-postfix/exec.firehol.php --build");
                if (is_file("/usr/sbin/firewall-builder.sh")) {
                    system("/usr/sbin/firewall-builder.sh");
                }
            }
        }
    }


    if ($GLOBALS["MUST_RESTART"]) {
        echo "************* MUST RESTART *************\n";
        build_progress("{restarting}", 85);
        $tmpfile = $unix->FILE_TEMP() . ".sh";
        $rm = $unix->find_program("rm");
        $sh[] = "#!/bin/sh";
        $sh[] = "/usr/sbin/artica-phpfpm-service -stop-proxy";
        $sh[] = "/usr/sbin/artica-phpfpm-service -start-proxy";
        $sh[] = "$rm -f $tmpfile";
        $sh[] = "";
        @file_put_contents($tmpfile, @implode("\n", $sh));
        @chmod($tmpfile, 0755);
        build_progress("{done} {starting_proxy_service}", 90);
        $unix->go_exec($tmpfile);
        $c = 0;
        for ($i = 90; $i < 100; $i++) {
            $c++;
            $pid = $unix->SQUID_PID();
            build_progress("{done} {starting_proxy_service} {waiting} $c", $i);
            if ($unix->process_exists($pid)) {
                break;
            }
            sleep(1);
        }
        cluster_mode();
        build_progress("{done}", 100);
        return;
    }


    build_progress("{done} {reloading_proxy_service}", 100);


    system("$php /usr/share/artica-postfix/exec.squid.disable.php --monit");
    if(!$GLOBALS["NO_RELOAD"]) {
        squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
        system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
    }
    cluster_mode();
}
function CheckNATPorts($startProgress):bool{
    $unix=new unix();
    $NAT_FIREWALL=unserialize(@file_get_contents("/etc/squid3/NAT_FIREWALL.array"));
    if(!is_array($NAT_FIREWALL)){
        $NAT_FIREWALL=array();
    }
    if(count($NAT_FIREWALL)==0){
        build_progress("{configuring_nat_ports} {disabled}",$startProgress++);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableTransparent27", 0);
        remove_service("/etc/init.d/squid-nat");
        return true;
    }

    build_progress("{configuring_nat_ports}",$startProgress++);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableTransparent27", 1);
    $php=$unix->LOCATE_PHP5_BIN();
    system("$php /usr/share/artica-postfix/exec.squid27.php --reload");
    build_progress("{configuring_nat_ports} {done}",$startProgress++);
    return true;

}

function CheckIptablesPorts($startProgress):bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $MIKROTIK_FIREWALL=unserialize(@file_get_contents("/etc/squid3/MIKROTIK_FIREWALL.array"));
    $TRANSPARENT_FIREWALL=unserialize(@file_get_contents("/etc/squid3/TRANSPARENT_FIREWALL.array"));
    $WCCP_FIREWALL=unserialize(@file_get_contents("/etc/squid3/WCCP_FIREWALL.array"));
    if(!$MIKROTIK_FIREWALL){$MIKROTIK_FIREWALL=array();}
    if(!$TRANSPARENT_FIREWALL){$TRANSPARENT_FIREWALL=array();}
    if(!$WCCP_FIREWALL){$WCCP_FIREWALL=array();}


    $MIKROTIK_FIREWALL_C=count($MIKROTIK_FIREWALL);
    $TRANSPARENT_FIREWALL_C=count($TRANSPARENT_FIREWALL);


    $SUM=$MIKROTIK_FIREWALL_C+$TRANSPARENT_FIREWALL_C;
    system("$nohup $php /usr/share/artica-postfix/exec.sysctl.php --restart >/dev/null 2>&1 &");

    build_progress("{starting} {listen_ports} - {secure_gateway}",$startProgress++);
    system("$php /usr/share/artica-postfix/exec.secure.gateway.php");

    if(is_file("/bin/artica-secure-gateway.sh")){
        build_progress("{restarting_firewall} (Secure gateway)",$startProgress++);
        shell_exec("/bin/artica-secure-gateway.sh");
    }



    if($SUM>0){
        if(count($WCCP_FIREWALL)>0){
            build_progress("{checking_wccp_mode}",$startProgress++);
            system("/usr/sbin/artica-phpfpm-service -wccp");
        }
    }else {
       if (count($WCCP_FIREWALL) > 0) {
            build_progress("{checking_wccp_mode}", $startProgress++);
            system("/usr/sbin/artica-phpfpm-service -wccp");
        }
    }


    cluster_mode();
    return true;

}

function dns_tuning(){
    $unix=new unix();
    build_progress("{starting} {DNS}",73);

    if(!isset($GLOBALS["SQUIDBEE"])){$GLOBALS["SQUIDBEE"]=new squidbee();}
    $md51=md5_file("/etc/squid3/dns.conf");
    $sock=new sockets();
    $sock->REST_API("/proxy/config/dnstuning/noreload");

    $squid_checks=new squid_checks();

    if(!$squid_checks->squid_parse()){
        build_progress("{failed2}",110);
        return false;
    }

    if(!$GLOBALS["NO_RELOAD"]) {
        build_progress("{done} {reloading_proxy_service}", 99);
        $squidbin = $unix->find_program("squid");
        if (is_file($squidbin)) {
            squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
            system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
            cluster_mode();
        }
    }

    build_progress("{done}",100);
    return true;
}

function workersCount():int{
    $f=explode("\n",@file_get_contents("/etc/squid3/caches.conf"));
    foreach ($f as $line){
        if(preg_match("#^workers\s+([0-9]+)#",$line,$re)){
            return intval($re[1]);
        }
    }
    return 0;
}

function list_ports(): array
{
    $unix=new unix();
    return $unix->SQUID_ALL_PORTS();
}

function get_filedescriptors(): int{
    $f=explode("\n",@file_get_contents("/etc/squid3/caches.conf"));
    foreach ($f as $line){
        $line=trim($line);
        if(preg_match("#^max_filedescriptors\s+([0-9]+)#",$line,$re)){
            return intval($re[1]);
        }
    }
    return 0;
}
function cache_tweaks():bool{
    $unix=new unix();

    $php5=$unix->LOCATE_PHP5_BIN();
    $md51=global_md5_dir();
    build_progress("{starting} {caches_center}",73);
    $Workers1=workersCount();
    $ports1=base64_encode(serialize(list_ports()));
    $fs1 = get_filedescriptors();

    system("/usr/sbin/artica-phpfpm-service -proxy-build-caches");
    $squid_refresh_pattern=new squid_refresh_pattern();
    $squid_refresh_pattern->build();

    $md52=global_md5_dir();
    if($md51 == $md52){
        build_progress("{success} {no_change}",100);
        return true;
    }
    $squid_checks=new squid_checks();

    if(!$squid_checks->squid_parse()){
        build_progress("{failed2}",110);
        return false;
    }
    $Workers2=workersCount();
    $ports2=base64_encode(serialize(list_ports()));
    $fs2 = get_filedescriptors();
    if($ports2<>$ports1){$GLOBALS["MUST_RESTART"]=true;}
    if($fs2<>$fs1){$GLOBALS["MUST_RESTART"]=true;}
    if($Workers2<>$Workers1){$GLOBALS["MUST_RESTART"]=true;}

    if($GLOBALS["MUST_RESTART"]){
        squid_admin_mysql(0, "Restart Proxy service to apply new settings (".__FUNCTION__.")", null,__FILE__,__LINE__);
        build_progress("{restarting_proxy_service}",70);
        shell_exec("$php5 /usr/share/artica-postfix/exec.squid.disable.php --squid-service");
        build_progress("{restarting}",85);
        $unix->go_exec_out("/usr/sbin/artica-phpfpm-service -stop-proxy");
        build_progress("{starting}",85);
        $unix->go_exec_out("/usr/sbin/artica-phpfpm-service -start-proxy");
        cluster_mode();
        build_progress("{restarting_proxy_service}",90);
        sleep(2);
        build_progress("{done}",100);
        return true;
    }

    if(!$GLOBALS["NO_RELOAD"]) {
        build_progress("{done} {reloading_proxy_service}", 99);
        $squidbin = $unix->find_program("squid");
        if (is_file($squidbin)) {
            squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
            system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
            cluster_mode();
        }
    }

    build_progress("{done}",100);
    return true;
}


function remove_service($INITD_PATH){
    if(!is_file($INITD_PATH)){return;}
    system("$INITD_PATH stop");

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");

    }

    if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function xstart(){

    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
    $pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid)){
        echo "Process already running\n";
        build_progress("{starting} {GLOBAL_ACCESS_CENTER}",110);
    }

    $xtime=$unix->file_time_min($pidTime);
    if($xtime<1){
        echo "Please wait at least 1mn\n";
        build_progress("{starting} {GLOBAL_ACCESS_CENTER}",110);
    }

    $dirs[]="/var/run/squid";
    $dirs[]="/var/cache/squid";
    $dirs[]="/var/log/squid";

    foreach ($dirs as $directory){

        @mkdir($directory,0755,true);
        @chown($directory, "squid");
        @chgrp($directory, "squid");
    }



    @file_put_contents($pidfile, getmypid());
    @unlink($pidTime);
    @file_put_contents($pidTime, time());
    $php=$unix->LOCATE_PHP5_BIN();

    ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    if(is_file("/usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz")){
        @unlink("/usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz");
    }


    $Necessaries[]="acls_peer.conf";
    $Necessaries[]="GlobalAccessManager_url_rewrite.conf";
    $Necessaries[]="url_rewrite_access.conf";
    $Necessaries[]="url_regex_nocache.conf";
    $Necessaries[]="non_ntlm.conf";
    $Necessaries[]="non_ntlm.access";
    $Necessaries[]="url_rewrite_access.conf";
    $Necessaries[]="acl_NoFilterService.conf";

    foreach ($Necessaries as $filename){
        if(!is_file("/etc/squid3/$filename")){@touch("/etc/squid3/$filename");}
        @chown("/etc/squid3/$filename","squid");
        @chgrp("/etc/squid3/$filename","squid");
    }

    @file_put_contents("/etc/squid3/acls_peer.conf", "\n");
    @file_put_contents("/etc/squid3/acls_center.conf", "\n");
    @file_put_contents("/etc/squid3/GlobalAccessManager_url_rewrite.conf", "\n");
    @file_put_contents("/etc/squid3/url_rewrite_access.conf", "\n");
    if(!is_file("/etc/squid3/url_regex_nocache.conf")){@file_put_contents("/etc/squid3/url_regex_nocache.conf", "\n");}
    if(!is_file("/etc/squid3/non_ntlm.access")){@file_put_contents("/etc/squid3/non_ntlm.access", "\n");}
    if(!is_file("/usr/share/squid-langpack/templates/ERR_PROTOCOL_UNKNOWN")){@touch("/usr/share/squid-langpack/templates/ERR_PROTOCOL_UNKNOWN");}
    errors_details();

    $size=@filesize("/etc/squid3/non_ntlm.access");
    if($size>524288){
        @unlink("/etc/squid3/non_ntlm.access");
        @touch("/etc/squid3/non_ntlm.access");
    }

    build_progress("{starting} {GLOBAL_ACCESS_CENTER}",10);
    system("/usr/sbin/artica-phpfpm-service -reconfigure-proxy");

    if($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CurrentLIC", 1);}else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CurrentLIC",0);
    }



    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    if($q->COUNT_ROWS("proxy_ports")==0){
        build_progress("{starting} {ports_conversion}",12);
        sleep(2);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("IsPortsConverted", 0);
        system("$php /usr/share/artica-postfix/exec.squid.php --ports-conversion");
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        if($q->COUNT_ROWS("proxy_ports")>0){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IsPortsConverted", 1);}

    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("IsPortsConverted", 1);
    }



    build_progress("{reconfigure} Building {watchdog}",16);
    system("$php /usr/share/artica-postfix/exec.squid.disable.php --monit");

    if(!is_file("/etc/squid3/squid.conf")){
        system("$php /usr/share/artica-postfix/exec.squid.php --build --force --noverifacls");
        exit();
    }


    CheckIptablesPorts(15);



    system("$php /usr/share/artica-postfix/exec.squid.disable.php --monit");

    $HyperCacheSquid=new HyperCacheSquid();
    $HyperCacheSquid->build();


    build_progress("{starting} {GLOBAL_ACCESS_CENTER} {authentication}",33);
    $squid_ntlm=new squid_ntlm();
    $squid_ntlm->build();


    build_progress("{starting} {GLOBAL_ACCESS_CENTER}",35);
    if(!isset($GLOBALS["ACL_BUILDED"])){
        $aclGen=new squid_acls();
        $aclGen->Build_Acls(true);
        @file_put_contents("/etc/squid3/acls_center.conf", @implode("\n",$aclGen->acls_array));
        build_progress("{starting} {GLOBAL_ACCESS_CENTER}",40);
        $GLOBALS["ACL_BUILDED"]=true;
    }


    $squid_access_manager=new squid_access_manager();
    build_progress("{starting} {GLOBAL_ACCESS_CENTER}",45);
    $squid_access_manager->build_all();
    common(45);



    build_progress("{starting} {GLOBAL_ACCESS_CENTER} {events}  {initialize}",54);
    $GLOBALS["PRC"]=54;
    if(!isset($GLOBALS["SQUIDBEE"])){$GLOBALS["SQUIDBEE"]=new squidbee();}
    build_progress("{starting} {GLOBAL_ACCESS_CENTER} {events}  {building}",60);
    $GLOBALS["BUILD_PROGRESS"]=60;
    $GLOBALS["SQUIDBEE"]->access_logs();

    build_progress("{starting} {GLOBAL_ACCESS_CENTER} {squid_ftp_user}",70);
    $squid_ftp=new squid_ftp();
    $squid_ftp->build();



    build_progress("{starting} {GLOBAL_ACCESS_CENTER} {timeouts}",71);
    $sock=new sockets();
    $sock->REST_API("/proxy/config/timeouts/noreload");

    build_progress("{starting} {GLOBAL_ACCESS_CENTER}",73);
    $icap=new icap();
    $icap->build_services();

    build_progress("{starting} Parent Proxies",74);
    system("/usr/sbin/artica-phpfpm-service -proxy-parents");

    build_progress("{starting} {GLOBAL_ACCESS_CENTER}",76);
    $acls=new squid_acls_groups();

    build_progress("{starting} {GLOBAL_ACCESS_CENTER}",77);
    $bandwith=new squid_bandwith_builder();
    $bandwith->compile();

    build_progress("{starting} {UseSSL}",78);
    $squid_ssl=new squid_ssl();
    $squid_ssl->build();

    build_progress("{starting} {DNS}",79);
    if(!isset($GLOBALS["SQUIDBEE"])){$GLOBALS["SQUIDBEE"]=new squidbee();}
    $sock=new sockets();
    $sock->REST_API("/proxy/config/dnstuning/noreload");


    build_progress("{starting} {webfiltering}",80);
    $GLOBALS["ACLS_UFDBCLIENT"]=true;
    $ufdbgclient=new squid_url_rewrite();
    $ufdbgclient->build();


    build_progress("{starting} {refresh_patterns}",90);
    $squid_refresh_pattern=new squid_refresh_pattern();
    $squid_refresh_pattern->build();

    build_progress("{starting} {tcp_outgoing_interface}",91);
    $tcp_outgoing_interface=new tcp_outgoing_interface();
    $tcp_outgoing_interface->build();

    build_progress("{starting} {tcp_outgoing_mark}",92);
    $tcp_outgoing_mark=new tcp_outgoing_mark();
    $tcp_outgoing_mark->build();

    build_progress("{starting} {reply_access_rules}",93);
    $http_reply_access=new http_reply_access();
    $http_reply_access->build();
    build_progress("{starting} {GLOBAL_ACCESS_CENTER}",95);
    http_access_default();
    build_progress("{starting} {GLOBAL_ACCESS_CENTER}",96);

    build_progress("{starting} {GLOBAL_ACCESS_CENTER}",97);
    system("/usr/sbin/artica-phpfpm-service -proxy-parents");

    if($GLOBALS["NOCHECK"]){return true;}
    build_progress("{verify_global_configuration}",98);
    $squid_checks=new squid_checks();

    if(!$squid_checks->squid_parse()){
        build_progress("{failed2}",110);
        return false;
    }
    if(!$GLOBALS["NO_RELOAD"]) {
        build_progress("{done} {reloading_proxy_service}", 99);
        $squidbin = $unix->find_program("squid");
        if (is_file($squidbin)) {
            squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
            system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
            cluster_mode();
        }
    }
    if(is_file("/etc/init.d/hypercache-service")){system("/etc/init.d/hypercache-service restart");}
    build_progress("{done} {reloading_proxy_service}",100);

}

function clean_md5_changes($path){
    $f=explode("\n",@file_get_contents($path));
    $newf=array();
    foreach ($f as $line){
        if(preg_match("#squid_get_system_info\.db#",$line)){continue;}
        $newf[]=$line;
    }
    @file_put_contents($path,@implode("\n",$newf));
    return md5_file($path);
}

function global_md5_dir(){

    echo "* * * * Scanning /etc/squid3 * * * *\n";
    @chmod("/usr/share/artica-postfix/bin/dirscan",0755);
    if(!is_file("/etc/artica-postfix/etc.squid3.hash")) {
        shell_exec("/usr/share/artica-postfix/bin/dirscan /etc/squid3/ >/etc/artica-postfix/etc.squid3.hash");
        echo "* * * * /etc/artica-postfix/etc.squid3.hash * * * *\n";
        $md5 =clean_md5_changes("/etc/artica-postfix/etc.squid3.hash");
        echo "* * * * /etc/squid3/ = $md5 * * * *\n";
        return $md5;
    }
    if(is_file("/etc/artica-postfix/etc.squid3.hash")) {
        shell_exec("/usr/share/artica-postfix/bin/dirscan /etc/squid3/ >/etc/artica-postfix/etc.squid3.hash2");
        echo "* * * * /etc/artica-postfix/etc.squid3.hash2 * * * *\n";
        $md5=clean_md5_changes("/etc/artica-postfix/etc.squid3.hash2");
        system("/usr/bin/diff /etc/artica-postfix/etc.squid3.hash /etc/artica-postfix/etc.squid3.hash2");
        @unlink("/etc/artica-postfix/etc.squid3.hash");
    }
    echo "* * * * /etc/squid3/ = $md5 * * * *\n";
    return $md5;
}

function http_access_default($StartOf=false,$NoReload=false){
    $isSquid5       = false;
    $SquidVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
    if(preg_match("#^(5|6|7)\.#",$SquidVersion)){$isSquid5=true;}
    $acls=new squid_acls_groups();
    $progress=80;
    $adprog=1;
    $RELOAD=true;
    if($NoReload){$RELOAD=false;}

    if($StartOf){
        $progress=5;
        $adprog=10;
    }
    if($RELOAD){
        $md5start=global_md5_dir();
    }


    $aclGen=new squid_acls();

    if(!isset($GLOBALS["ACL_BUILDED"])){
        $aclGen->Build_Acls(true);
        @mkdir("/home/artica/proxy/acls");
        @file_put_contents("/home/artica/proxy/acls/acls_center.conf", @implode("\n",$aclGen->acls_array));
        $progress=$progress+$adprog;
        build_progress("{starting} {GLOBAL_ACCESS_CENTER}",$progress);
        $GLOBALS["ACL_BUILDED"]=true;
    }



    //-------------------------------------------------------------------------------------------------------------
    $tcp_outgoing_tos=$acls->buildacls_bytype("tcp_outgoing_tos");
    $conf[]="#### tcp_outgoing_tos ####";
    if(count($tcp_outgoing_tos)>0){
       foreach ($tcp_outgoing_tos as $index=>$line){$conf[]="tcp_outgoing_tos $line";}
    }else{
        $conf[]="#### tcp_outgoing_tos 0 Rules ####";
    }

    $progress=$progress+$adprog;

    build_progress("{starting} {GLOBAL_ACCESS_CENTER}",$progress);
    //----------------------------------------------------------------------------------------------------
    $reply_body_max_size=$acls->buildacls_bytype("reply_body_max_size");
    if(count($reply_body_max_size)>0){
        foreach ($reply_body_max_size as $index=>$line){$conf[]="reply_body_max_size $line";}
    }

   //----------------------------------------------------------------------------------------------------
    $SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
    $progress=$progress+$adprog;build_progress("{starting} {GLOBAL_ACCESS_CENTER}",$progress);
    $conf[]="# SquidUrgency = $SquidUrgency " .basename(__FILE__) ."[".__LINE__."]";
    if($SquidUrgency==0) {
        $conf[] = $acls->buildacls_order(0);
        $conf[] = "#";
        $conf[] = "#";
        $conf[] = "# ------------------ HTTP ACCESS --------------------";
        $acls_rules = $aclGen->build_http_access(0);
        $conf[] = "# " . count($acls_rules) . " rule(s) from engine (Line " . __LINE__ . ")\n";
        if (count($acls_rules) > 0) {
            $conf[] = "# Builded acls from engine...";
            $conf[] = @implode("\n", $acls_rules);
        }
    }
    //----------------------------------------------------------------------------------------------------
    $progress=$progress+$adprog;
    build_progress("{starting} {GLOBAL_ACCESS_CENTER}",$progress);
    $http_access_defaults=new http_access_defaults();
    $conf[]=$http_access_defaults->build();
    


    $SquidExternLDAPAUTH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternLDAPAUTH"));
    $UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    if($EnableKerbAuth==1 OR $UseNativeKerberosAuth==1 OR $LockActiveDirectoryToKerberos==1){
        $SquidExternLDAPAUTH=0;
    }

    if($SquidExternLDAPAUTH==1){
        $annotate_transaction=null;
        if($isSquid5) {
            $annotate_transaction = " AnnotateFinalAccess1";
            $conf[] = "acl AnnotateFinalAccess1 annotate_transaction accessrule=ldap_auth";
        }


        $conf[]="# Allow Only user authenticated trough the remote LDAP server";
        $conf[]="http_access deny NormalPorts !ldapauth$annotate_transaction";
    }

    //----------------------------------------------------------------------------

    $progress=$progress+$adprog;
    @mkdir("/etc/squid3-temp",0755,true);
    build_progress("{starting} {GLOBAL_ACCESS_CENTER} {done}",$progress);
    @file_put_contents("/home/artica/proxy/acls/http_access.conf",@implode("\n", $conf));

    $squid_access_manager=new squid_access_manager();
    $squid_access_manager->build_all();

    if(!$RELOAD){
        build_progress("{starting} {GLOBAL_ACCESS_CENTER} {done}",100);
        return true;
    }

    $md5End=global_md5_dir();
    if($md5start==$md5End){
        build_progress("{skip} {no_change}",100);
        return true;
    }

    if(!$GLOBALS["NO_RELOAD"]) {
        system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
        build_progress("{verify_global_configuration} {done}", 100);
    }
    return true;
}


function DenyFromSources(){
    $unix=new unix();
    $CONF=false;
    build_progress("{verify_global_configuration}",50);
    $tt=explode("\n",@file_get_contents("/etc/squid3/http_access.conf"));
    foreach ($tt as $line){
        if(preg_match("#http_access deny MyBlockedIPs#i", $line));
        $CONF=true;
    }

    if(!$CONF){
        xstart();
        return;
    }


    $squid_checks=new squid_checks();

    if(!$squid_checks->squid_parse()){
        build_progress("{failed2}",110);
        return;
    }

    $squidbin=$unix->find_program("squid");
    if( is_file($squidbin)){
        squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
        system("/usr/sbin/artica-phpfpm-service -rest-api /proxy/nohup/reload");
        cluster_mode();
        build_progress("{done} {reloading_proxy_service}",100);
    }



}
function Test_config(){
    $unix=new unix();
    $squidbin=$unix->find_program("squid");
    if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}

    exec("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",$results);
    foreach ($results as $index=>$ligne){
        if(strpos($ligne,"| WARNING:")>0){continue;}
        if(preg_match("#ERROR: Failed#", $ligne)){
            echo "`$ligne`, aborting configuration\n";
            return false;
        }

        if(preg_match("#Segmentation fault#", $ligne)){
            echo "`$ligne`, aborting configuration\n";
            return ;
        }


        if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
            echo "`$ligne`, aborting configuration\n";

            if(preg_match("#GlobalAccessManager_url_rewrite\.conf#", $ligne)){
                echo "************ GlobalAccessManager_url_rewrite *********\n";
                echo @file_get_contents("/etc/squid3/GlobalAccessManager_url_rewrite.conf")."\n";
                echo "*******************************************\n";
            }


            if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
                $Buggedline=$ri[1];
                $tt=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
                for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
                    $lineNumber=$i+1;
                    if(trim($tt[$i])==null){continue;}
                    echo "[line:$lineNumber]: {$tt[$i]}\n";
                }
            }

            return false;
        }

    }

    return true;

}
function errors_details(){
    $f[]="name: SQUID_X509_V_ERR_INFINITE_VALIDATION";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Cert validation infinite loop detected\"";
    $f[]="";
    $f[]="name: SQUID_ERR_SSL_HANDSHAKE";
    $f[]="detail: \"%ssl_error_descr: %ssl_lib_error\"";
    $f[]="descr: \"Handshake with SSL server failed\"";
    $f[]="";
    $f[]="name: SQUID_X509_V_ERR_DOMAIN_MISMATCH";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Certificate does not match domainname\"";
    $f[]="";
    $f[]="name: X509_V_ERR_UNABLE_TO_GET_ISSUER_CERT";
    $f[]="detail: \"SSL Certficate error: certificate issuer (CA) not known: %ssl_ca_name\"";
    $f[]="descr: \"Unable to get issuer certificate\"";
    $f[]="";
    $f[]="name: X509_V_ERR_UNABLE_TO_GET_CRL";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Unable to get certificate CRL\"";
    $f[]="";
    $f[]="name: X509_V_ERR_UNABLE_TO_DECRYPT_CERT_SIGNATURE";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Unable to decrypt certificate's signature\"";
    $f[]="";
    $f[]="name: X509_V_ERR_UNABLE_TO_DECRYPT_CRL_SIGNATURE";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Unable to decrypt CRL's signature\"";
    $f[]="";
    $f[]="name: X509_V_ERR_UNABLE_TO_DECODE_ISSUER_PUBLIC_KEY";
    $f[]="detail: \"Unable to decode issuer (CA) public key: %ssl_ca_name\"";
    $f[]="descr: \"Unable to decode issuer public key\"";
    $f[]="";
    $f[]="name: X509_V_ERR_CERT_SIGNATURE_FAILURE";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Certificate signature failure\"";
    $f[]="";
    $f[]="name: X509_V_ERR_CRL_SIGNATURE_FAILURE";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"CRL signature failure\"";
    $f[]="";
    $f[]="name: X509_V_ERR_CERT_NOT_YET_VALID";
    $f[]="detail: \"SSL Certficate is not valid before: %ssl_notbefore\"";
    $f[]="descr: \"Certificate is not yet valid\"";
    $f[]="";
    $f[]="name: X509_V_ERR_CERT_HAS_EXPIRED";
    $f[]="detail: \"SSL Certificate expired on: %ssl_notafter\"";
    $f[]="descr: \"Certificate has expired\"";
    $f[]="";
    $f[]="name: X509_V_ERR_CRL_NOT_YET_VALID";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"CRL is not yet valid\"";
    $f[]="";
    $f[]="name: X509_V_ERR_CRL_HAS_EXPIRED";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"CRL has expired\"";
    $f[]="";
    $f[]="name: X509_V_ERR_ERROR_IN_CERT_NOT_BEFORE_FIELD";
    $f[]="detail: \"SSL Certificate has invalid start date (the 'not before' field): %ssl_subject\"";
    $f[]="descr: \"Format error in certificate's notBefore field\"";
    $f[]="";
    $f[]="name: X509_V_ERR_ERROR_IN_CERT_NOT_AFTER_FIELD";
    $f[]="detail: \"SSL Certificate has invalid expiration date (the 'not after' field): %ssl_subject\"";
    $f[]="descr: \"Format error in certificate's notAfter field\"";
    $f[]="";
    $f[]="name: X509_V_ERR_ERROR_IN_CRL_LAST_UPDATE_FIELD";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Format error in CRL's lastUpdate field\"";
    $f[]="";
    $f[]="name: X509_V_ERR_ERROR_IN_CRL_NEXT_UPDATE_FIELD";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Format error in CRL's nextUpdate field\"";
    $f[]="";
    $f[]="name: X509_V_ERR_OUT_OF_MEM";
    $f[]="detail: \"%ssl_error_descr\"";
    $f[]="descr: \"Out of memory\"";
    $f[]="";
    $f[]="name: X509_V_ERR_DEPTH_ZERO_SELF_SIGNED_CERT";
    $f[]="detail: \"Self-signed SSL Certificate: %ssl_subject\"";
    $f[]="descr: \"Self signed certificate\"";
    $f[]="";
    $f[]="name: X509_V_ERR_SELF_SIGNED_CERT_IN_CHAIN";
    $f[]="detail: \"Self-signed SSL Certificate in chain: %ssl_subject\"";
    $f[]="descr: \"Self signed certificate in certificate chain\"";
    $f[]="";
    $f[]="name: X509_V_ERR_UNABLE_TO_GET_ISSUER_CERT_LOCALLY";
    $f[]="detail: \"SSL Certficate error: certificate issuer (CA) not known: %ssl_ca_name\"";
    $f[]="descr: \"Unable to get local issuer certificate\"";
    $f[]="";
    $f[]="name: X509_V_ERR_UNABLE_TO_VERIFY_LEAF_SIGNATURE";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Unable to verify the first certificate\"";
    $f[]="";
    $f[]="name: X509_V_ERR_CERT_CHAIN_TOO_LONG";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Certificate chain too long\"";
    $f[]="";
    $f[]="name: X509_V_ERR_CERT_REVOKED";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Certificate revoked\"";
    $f[]="";
    $f[]="name: X509_V_ERR_INVALID_CA";
    $f[]="detail: \"%ssl_error_descr: %ssl_ca_name\"";
    $f[]="descr: \"Invalid CA certificate\"";
    $f[]="";
    $f[]="name: X509_V_ERR_PATH_LENGTH_EXCEEDED";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Path length constraint exceeded\"";
    $f[]="";
    $f[]="name: X509_V_ERR_INVALID_PURPOSE";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Unsupported certificate purpose\"";
    $f[]="";
    $f[]="name: X509_V_ERR_CERT_UNTRUSTED";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Certificate not trusted\"";
    $f[]="";
    $f[]="name: X509_V_ERR_CERT_REJECTED";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Certificate rejected\"";
    $f[]="";
    $f[]="name: X509_V_ERR_SUBJECT_ISSUER_MISMATCH";
    $f[]="detail: \"%ssl_error_descr: %ssl_ca_name\"";
    $f[]="descr: \"Subject issuer mismatch\"";
    $f[]="";
    $f[]="name: X509_V_ERR_AKID_SKID_MISMATCH";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Authority and subject key identifier mismatch\"";
    $f[]="";
    $f[]="name: X509_V_ERR_AKID_ISSUER_SERIAL_MISMATCH";
    $f[]="detail: \"%ssl_error_descr: %ssl_ca_name\"";
    $f[]="descr: \"Authority and issuer serial number mismatch\"";
    $f[]="";
    $f[]="name: X509_V_ERR_KEYUSAGE_NO_CERTSIGN";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Key usage does not include certificate signing\"";
    $f[]="";
    $f[]="name: X509_V_ERR_UNABLE_TO_GET_CRL_ISSUER";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"unable to get CRL issuer certificate\"";
    $f[]="";
    $f[]="name: X509_V_ERR_UNHANDLED_CRITICAL_EXTENSION";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"unhandled critical extension\"";
    $f[]="";
    $f[]="name: X509_V_ERR_KEYUSAGE_NO_CRL_SIGN";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"key usage does not include CRL signing\"";
    $f[]="";
    $f[]="name: X509_V_ERR_UNHANDLED_CRITICAL_CRL_EXTENSION";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"unhandled critical CRL extension\"";
    $f[]="";
    $f[]="name: X509_V_ERR_INVALID_NON_CA";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"invalid non-CA certificate (has CA markings)\"";
    $f[]="";
    $f[]="name: X509_V_ERR_PROXY_PATH_LENGTH_EXCEEDED";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"proxy path length constraint exceeded\"";
    $f[]="";
    $f[]="name: X509_V_ERR_KEYUSAGE_NO_DIGITAL_SIGNATURE";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"key usage does not include digital signature\"";
    $f[]="";
    $f[]="name: X509_V_ERR_PROXY_CERTIFICATES_NOT_ALLOWED";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"proxy certificates not allowed, please set the appropriate flag\"";
    $f[]="";
    $f[]="name: X509_V_ERR_INVALID_EXTENSION";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"invalid or inconsistent certificate extension\"";
    $f[]="";
    $f[]="name: X509_V_ERR_INVALID_POLICY_EXTENSION";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"invalid or inconsistent certificate policy extension\"";
    $f[]="";
    $f[]="name: X509_V_ERR_NO_EXPLICIT_POLICY";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"no explicit policy\"";
    $f[]="";
    $f[]="name: X509_V_ERR_DIFFERENT_CRL_SCOPE";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Different CRL scope\"";
    $f[]="";
    $f[]="name: X509_V_ERR_UNSUPPORTED_EXTENSION_FEATURE";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Unsupported extension feature\"";
    $f[]="";
    $f[]="name: X509_V_ERR_UNNESTED_RESOURCE";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"RFC 3779 resource not subset of parent's resources\"";
    $f[]="";
    $f[]="name: X509_V_ERR_PERMITTED_VIOLATION";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"permitted subtree violation\"";
    $f[]="";
    $f[]="name: X509_V_ERR_EXCLUDED_VIOLATION";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"excluded subtree violation\"";
    $f[]="";
    $f[]="name: X509_V_ERR_SUBTREE_MINMAX";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"name constraints minimum and maximum not supported\"";
    $f[]="";
    $f[]="name: X509_V_ERR_UNSUPPORTED_CONSTRAINT_TYPE";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"unsupported name constraint type\"";
    $f[]="";
    $f[]="name: X509_V_ERR_UNSUPPORTED_CONSTRAINT_SYNTAX";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"unsupported or invalid name constraint syntax\"";
    $f[]="";
    $f[]="name: X509_V_ERR_UNSUPPORTED_NAME_SYNTAX";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"unsupported or invalid name syntax\"";
    $f[]="";
    $f[]="name: X509_V_ERR_CRL_PATH_VALIDATION_ERROR";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"CRL path validation error\"";
    $f[]="";
    $f[]="name: X509_V_ERR_APPLICATION_VERIFICATION";
    $f[]="detail: \"%ssl_error_descr: %ssl_subject\"";
    $f[]="descr: \"Application verification failure\"";
    $f[]="";
    @mkdir("/usr/share/squid-langpack/templates");

    if(!is_dir("/usr/share/squid3/errors/fr-fr")){@mkdir("/usr/share/squid3/errors/fr-fr",0755,true);}

    @file_put_contents("/usr/share/squid-langpack/templates/error-details.txt", @implode("\n", $f));
    @file_put_contents("/usr/share/squid3/errors/fr-fr/error-details.txt", @implode("\n", $f));
}

