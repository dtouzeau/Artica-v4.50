<?php
$GLOBALS["TITLENAME"]="DNS Cache service";
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.resolv.conf.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--dns-timeout"){dns_timeout();exit;}
if($argv[1]=="--safesearchs"){SafeSearchs();exit;}
if($argv[1]=="--watchdog"){watchdog();exit;}


function uninstall():bool{
    build_progress(25, "{uninstalling}");
    touch("/etc/artica-postfix/DoNotUseLocalDNSCache");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DoNotUseLocalDNSCache",1);
    $unix=new unix();
    system("/usr/sbin/artica-phpfpm-service -uninstall-dnscache");
    stop();
    $monit_file = "/etc/monit/conf.d/dns_cache.monitrc";
    if(is_file("/etc/dnscache-upstream.conf")){@unlink("/etc/dnscache-upstream.conf");}
    build_progress(50, "{uninstalling}");
    if(is_file($monit_file)){
        @unlink($monit_file);

    }

    build_progress(60, "{reconfiguring}");
    if(is_file("/etc/init.d/go-shield-server")) {
        $unix->go_exec("/etc/init.d/go-shield-server restart");
    }
    if(!is_file("/etc/cron.d/dnscache-watchdog")){
        @unlink("/etc/cron.d/dnscache-watchdog");
        UNIX_RESTART_CRON();
    }
    build_progress(65, "{reconfiguring} {used_dns}");
    $php=$unix->LOCATE_PHP5_BIN();


    if(is_file("/etc/init.d/squid")) {
        build_progress(70, "{reconfiguring} {dns_used_by_the_proxy_service}");
        system("/usr/sbin/artica-phpfpm-service -proxy-dns");
    }
    if(is_file("/etc/init.d/unbound")){
        build_progress(75, "{reconfiguring} {APP_UNBOUND}");
        shell_exec("/usr/sbin/artica-phpfpm-service -restart-unbound");
    }
    //

    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    build_progress(100, "{uninstalling} {success}");
    return true;
}

function watchdog():bool{
    $unix=new unix();
    return     $unix->Popuplate_cron_delete("dnscache-watchdog");
}
function watch():bool{
       return true;
}

function dns_timeout(){
}
function install():bool{
    $unix=new unix();
    $srcfile="/usr/share/artica-postfix/bin/dnsproxy";
    if(!is_file($srcfile)){
        return false;
    }
    if(is_file("/etc/artica-postfix/DoNotUseLocalDNSCache")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DoNotUseLocalDNSCache",1);
        echo "/etc/artica-postfix/DoNotUseLocalDNSCache !!\n";
        return false;
    }
    $NoInternetAccess=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoInternetAccess"));

    if($NoInternetAccess==1){
        build_progress(110, "{failed} NoInternetAccess==1");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DoNotUseLocalDNSCache",1);
        return true;
    }
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    if($EnableDNSDist==1){
        build_progress(110, "{failed} {APP_DNSDITS} {installed}");
        return true;
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DoNotUseLocalDNSCache",0);



    build_progress(25, "{installing}");
    build_progress(40, "{installing} {reconfiguring}");
    system("/usr/sbin/artica-phpfpm-service -install-dnscache");
    build_progress(50, "{installing} {starting_service}");
    $php=$unix->LOCATE_PHP5_BIN();
    if(is_file("/etc/init.d/squid-dns")){
        build_progress(55, "{uninstalling} Proxy DNS service");
        shell_exec("$php /usr/share/artica-postfix/exec.squid.dns.php --uninstall");
    }

    if(is_file("/etc/init.d/unbound")){
        build_progress(60, "{installing} {starting_service}");
        shell_exec("/usr/sbin/artica-phpfpm-service -restart-unbound");
    }
    if(is_file("/etc/init.d/dnsdist")){
        build_progress(60, "{installing} {starting_service}");
        shell_exec("/etc/init.d/dnsdist restart");
    }
    if(is_file("/etc/init.d/squid")) {
        build_progress(70, "{reconfiguring} {dns_used_by_the_proxy_service}");
        system("/usr/sbin/artica-phpfpm-service -proxy-dns");
    }



    if(is_file("/etc/init.d/go-shield-server")) {
        $unix->go_exec("/etc/init.d/go-shield-server restart");
    }

    start(true);
    build_progress(100, "{starting} {success}");
    return false;
}
function build(){



}

function build_progress($prc,$txt){
    $unix=new unix();
    $unix->framework_progress($prc,$txt,"dnscache.progress");

}

function restart()
{
    build_progress(25, "{stopping_service}");
    $unix = new unix();
    $pidfile = "/etc/artica-postfix/pids/exec.dnscache.php.restart.pid";
    $pid = $unix->get_pid_from_file($pidfile);
    if ($unix->process_exists($pid, basename(__FILE__))) {
        $time = $unix->PROCCESS_TIME_MIN($pid);
        _out("[info] Already Artica task running PID $pid since {$time}mn");
        build_progress(110, "{stopping_service} {failed}");
        return false;
    }

    @file_put_contents($pidfile, getmypid());
    stop(true);
    build_progress(50, "{starting_service}");
    SafeSearchs();

    start();
    build_progress(100, "{starting_service} {success}");
    return true;
}



function destbin():string{
    $dstfile="/usr/sbin/dnscache";
    return $dstfile;
}


function _out($text){
    echo "Service.......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("dnscache", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}
function _out_monit($text):bool{
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("monit", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}

function SafeSearchs():bool{
    $unix=new unix();
    $unix->Popuplate_cron_make("safesearch-resolves","0 */8 * * *","exec.dnscache.php --safesearchs");

    $ipclass=new IP();
    $SafeApiQwantCom=gethostbyname("safeapi.qwant.com");
    _out("safeapi.qwant.com: $SafeApiQwantCom ");
    if($ipclass->isValid($SafeApiQwantCom)) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SafeApiQwantCom", "$SafeApiQwantCom");
    }

    $ForceSafeSearchGoogle=gethostbyname("forcesafesearch.google.com");
    _out("forcesafesearch.google.com: $ForceSafeSearchGoogle ");
    if($ipclass->isValid($ForceSafeSearchGoogle)) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ForceSafeSearchGoogle","$ForceSafeSearchGoogle");
    }


    $SafeDuckduckgo=gethostbyname("safe.duckduckgo.com");
    _out("safe.duckduckgo.com: $SafeDuckduckgo ");
    if($ipclass->isValid($SafeDuckduckgo)) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SafeDuckduckgo","$SafeDuckduckgo");
    }

    $StrictBingCom=gethostbyname("strict.bing.com");
    if($ipclass->isValid($StrictBingCom)) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("StrictBingCom","$StrictBingCom");
    }
    $FamilySearchYandexCom=gethostbyname("familysearch.yandex.com");
    if($ipclass->isValid($StrictBingCom)) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FamilySearchYandexCom","$FamilySearchYandexCom");
    }

    $SafesearchBraveCom=gethostbyname("safesearch.brave.com");
    if($ipclass->isValid($SafesearchBraveCom)) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SafesearchBraveCom","$SafesearchBraveCom");
    }

    $SafesearchPixabayCom=gethostbyname("safesearch.pixabay.com");

    if($ipclass->isValid($SafesearchPixabayCom)) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SafesearchPixabayCom","$SafesearchPixabayCom");
    }

    $RestrictYoutubeCom=gethostbyname("restrict.youtube.com");
    $RestrictModerateYoutubeCom=gethostbyname("restrictmoderate.youtube.com");

    if($ipclass->isValid($RestrictYoutubeCom)) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RestrictYoutubeCom","$RestrictYoutubeCom");
    }

    if($ipclass->isValid($RestrictModerateYoutubeCom)) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RestrictModerateYoutubeCom","$RestrictModerateYoutubeCom");
    }

    return true;
}



function start($aspid = false){
    system("/usr/sbin/artica-phpfpm-service -start-dnscache -debug");
}
function stop():bool{
    system("/usr/sbin/artica-phpfpm-service -stop-dnscache -debug");
    return true;
}