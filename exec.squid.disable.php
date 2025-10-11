<?php
$GLOBALS["PERCENT_PR"]=0;
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.postgres.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
if(preg_match("#--percent-pr=([0-9])+#",implode(" ",$argv),$re)){$GLOBALS["PERCENT_PR"]=$re[1];}


if(isset($argv[1])){
    if($argv[1]=="--monit"){SQUID_MONIT();exit;}
    if($argv[1]=="--remove"){remove();exit;}
    if($argv[1]=="--install"){install();exit;}
    if($argv[1]=="--squid-service"){create_squid_service();SQUID_MONIT();buildSyslog();exit;}
    if($argv[1]=="--restart-upgrade"){restart_upgrade();exit;}
    if($argv[1]=="--syslog"){buildSyslog();}
    if($argv[1]=="--snapshot-restored"){snaphost_restored();exit;}
    if($argv[1]=="--start-true"){start_success();exit;}
    if($argv[1]=="--start-failed"){start_failed();exit;}
    if($argv[1]=="--start-report"){start_report();exit;}
    if($argv[1]=="--cpu-alert"){SQUID_MONIT_CPU_ALERT($argv[2]);exit(0);}
    if($argv[1]=="--memory-alert"){SQUID_MONIT_MEM_ALERT($argv[2]);exit(0);}

}
//

function build_progress($pourc,$text){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/squid.disable.progress", serialize($array));
    @chmod("/usr/share/artica-postfix/ressources/logs/squid.disable.progress",0755);
    if($GLOBALS["PERCENT_PR"]>0) {
        $array["POURC"] = $GLOBALS["PERCENT_PR"];
        $array["TEXT"] = "{$pourc}% - $text";
        @file_put_contents(PROGRESS_DIR."/wizard.progress",
            serialize($array));
    }

}
function build_progress_monit($pourc,$text){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/squid.monit.progress", serialize($array));
    @chmod("/usr/share/artica-postfix/ressources/logs/squid.monit.progress",0755);
}
function reconfigure_reboot(){
    $ReloadProxyAfterReboot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ReloadProxyAfterReboot"));
    $RestoreSnapshotProxyAfterReboot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RestoreSnapshotProxyAfterReboot"));
    if($RestoreSnapshotProxyAfterReboot==1){
        $ReloadProxyAfterReboot=0;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ReloadProxyAfterReboot",0);
    }





    if($ReloadProxyAfterReboot==0){
        if(is_file("/etc/cron.d/squid-reboot")){
            @unlink("/etc/cron.d/squid-reboot");
            UNIX_RESTART_CRON();
            return true;
        }
        return true;
    }
    $md5=null;
    if(is_file("/etc/cron.d/squid-reboot")){
        $md5=md5_file("/etc/cron.d/squid-reboot");
    }
    $unix=new unix();
    $unix->Popuplate_cron_make("squid-reboot","@reboot","exec.squid-reboot.php");
    $md51=md5_file("/etc/cron.d/squid-reboot");
    if($md51==$md5){
        UNIX_RESTART_CRON();
        return true;
    }
    return true;

}
function snaphost_restored(){
    squid_admin_mysql(1,"Starting Proxy service using snapshot restoration",null,__FILE__,__LINE__);
}
function start_failed(){
    squid_admin_mysql(0,"Proxy service failed to start using init",null,__FILE__,__LINE__);
}
function start_success(){
   squid_admin_mysql(2,"Proxy service success to start using init",null,__FILE__,__LINE__);
}
function start_report():bool{
    $unix=new unix();
    $addtext=null;
    $pid=$unix->SQUID_PID();
    if($unix->process_exists($pid)){
        $addtext="- But is running -";
        $results[]="Proxy service is running PID: $pid since ".$unix->PROCESS_TTL_TEXT($pid);
    }

    $ps=$unix->find_program("ps");
    $tail=$unix->find_program("tail");
    $results[]="Current processes:";
    $results[]="------------------------------------";
    exec("$ps -auxww 2>&1",$results);
    $results[]="Last Proxy service logs:";
    $results[]="------------------------------------";
    exec("$tail -n 200 /var/log/squid/cache.log 2>&1",$results);
    squid_admin_mysql(1, "{starting} Proxy service$addtext (by init - see report)", $results, __FILE__, __LINE__);
    return true;
}




function install(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDEnable", 1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Enablehacluster", 0);

    system("/usr/sbin/artica-phpfpm-service -install-proxy");

    buildSyslog();

    build_progress(61, "{create_the_service}");
    $unix->Popuplate_cron_make("squid-storedir-5mn","*/5 * * * *","exec.squid.php.storedir.php");
    $unix->Popuplate_cron_make("squid-client-list", "*/10 * * * *", "exec.squidclient.mgr.clientlist.php");
    $unix->Popuplate_cron_make("squid-storedir-1h","5 * * * *","exec.squid.php.storedir.php --force");

    UNIX_RESTART_CRON();

    build_progress(90, "{restart_status_service}");
    system("/etc/init.d/artica-status restart --force");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    system("/usr/sbin/artica-phpfpm-service -build-pam");
    build_progress(95, "{restart_status_service}");
    system("/usr/sbin/artica-phpfpm-service -nsswitch");
    system("$php /usr/share/artica-postfix/exec.logrotate.php --reconfigure");
    build_progress(98, "{restart_status_service}");
    if(is_file("/etc/init.d/munin-node")){system("$php /usr/share/artica-postfix/exec.munin.php --reconfigure");}
    build_progress(100, "{done}");

    if(is_file("/etc/init.d/zabbix-agent")){
        system("/etc/init.d/zabbix-agent restart");
    }



}
function build_syslog_internetwatch():bool{
    $unix=new unix();
    $tfile      = "/etc/rsyslog.d/artica-internetwatch.conf";
    $oldmd      = null;
    if(is_file($tfile)) {
        $oldmd = md5_file($tfile);
    }
    echo "$tfile [$oldmd]\n";
    $add_rules = BuildRemoteSyslogs("internetwatch");

    $h=array();
    $h[]="";
    $h[]="if  (\$programname =='internetwatch') then {";
    if(strlen($add_rules)>3) {$h[] = $add_rules;}
    $h[] ="\t-/var/log/internetwatch.log";
    $h[] ="\t&stop";
    $h[]="\t}";
    $h[]="";
    @file_put_contents($tfile,@implode("\n",$h));
    $newmd      = md5_file($tfile);
    echo "$tfile [OK]\n";
    if($oldmd<>$newmd){
        $unix->RESTART_SYSLOG();
    }
    return true;
}

function buildSyslog():bool{
    build_syslog_activedirectory();
    build_syslog_internetwatch();
   return true;
}

function build_syslog_activedirectory():bool{
    $md51=null;
    $sfile="/etc/rsyslog.d/activedirectory.conf";
    if(is_file($sfile)) {
        $md51=md5_file($sfile);
    }

        $h[] = "if  (\$programname =='activedirectory') then {";
        $h[] = buildlocalsyslogfile("/var/log/activedirectory.log");
        $h[] = "\t& stop";
        $h[] = "}";

        $h[] = "if  (\$programname =='artica-proxy-auth') then {";
        $h[] = buildlocalsyslogfile("/var/log/activedirectory.log");
        $h[] = "\t& stop";
        $h[] = "}";

        @file_put_contents($sfile, @implode("\n", $h));

    $md52=md5_file($sfile);
    if($md51==$md52){return true;}
    $unix=new unix();$unix->RESTART_SYSLOG(true);
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





function remove(){
    squid_admin_mysql(0, "Removing the proxy service and all associated softwares", null,__FILE__,__LINE__);
    $unix=new unix();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDEnable",0);

    build_progress(10, "{stopping} Watchdogs");
    $servicesStop[]="monit";
    $servicesStop[]="artica-status";
    $servicesStop[]="cron";
    foreach ($servicesStop as $init){
        build_progress(15, "{stopping} $init");
        shell_exec("/etc/init.d/$init stop");

    }

    $php=$unix->LOCATE_PHP5_BIN();
    $servicesINIT[]="squid";
    $servicesINIT[]="squidguard-http";
    $servicesINIT[]="squid-nat";
    $servicesINIT[]="squid-tail";
    $servicesINIT[]="iptables-transparent";
    $servicesINIT[]="hypercache-web";
    $servicesINIT[]="ufdb-tail";
    $servicesINIT[]="wanproxy-parent";
    $servicesINIT[]="wanproxy-childs";
    $servicesINIT[]="zipproxy";
    $servicesINIT[]="ntlm-monitor";
    $servicesINIT[]="proxy-pac";
    $servicesINIT[]="squid-tail-size";
    $servicesINIT[]="squid-tail";
    $servicesINIT[]="cache-scheduler";
    $servicesINIT[]="cache-httpd";
    $servicesINIT[]="ufdb-client";
    $servicesINIT[]="ufdb";
    $servicesINIT[]="microhotspot";
    $servicesINIT[]="hotspot-web";
    $servicesINIT[]="wifidog";
    $servicesINIT[]="c-icap";
    $servicesINIT[]="c-icap-access";
    $servicesINIT[]="c-icap-watchdog";
    $servicesINIT[]="cache-tail";
    $servicesINIT[]="kav4proxy";
    $servicesINIT[]="cntlm";
    $servicesINIT[]="winbind";
    $servicesINIT[]="cache-tail";


    $pourc=30;

    $SERVICES["/etc/init.d/proxy-pac"]="/usr/sbin/artica-phpfpm-service -uninstall-proxypac";
    $SERVICES["/etc/init.d/ufdb"]="/usr/sbin/artica-phpfpm-service -uninstall-ufdb";
    $SERVICES["/etc/init.d/privoxy"]="exec.privoxy.php --remove";
    $SERVICES["/etc/init.d/wanproxy"]="exec.wanproxy.php --uninstall";
    $SERVICES["/etc/init.d/zipproxy"]="exec.zipproxy.php --uninstall";
    $SERVICES["/etc/init.d/ziproxy"]="exec.zipproxy.php --uninstall";
    $SERVICES["/etc/init.d/c-icap"]="/usr/sbin/artica-phpfpm-service -uninstall-cicap";
    $SERVICES["/etc/init.d/itcharter"]="/usr/sbin/artica-phpfpm-service -uninstall-itcharter";
    $SERVICES["/etc/init.d/squid-dns"]="exec.squid.dns.php --uninstall";

    foreach ($SERVICES as $initd=>$script){
        if(is_file($initd)){
            build_progress($pourc++, "{removing} $initd");
            shell_exec("$php /usr/share/artica-postfix/$script");
        }
    }

    $scvname["squid"]="{APP_SQUID}";
    $scvname["ufdb"]="{APP_UFDBGUARDD}";

    foreach ($servicesINIT as $filename){
        $filename_text=$filename;
        if(isset($scvname[$filename])){$filename_text=$scvname[$filename];}
        build_progress($pourc++, "{removing} $filename_text");
        remove_service("/etc/init.d/$filename");
    }
    $tokens[]="EnableSS5";
    $tokens[]="kavicapserverEnabled";
    $tokens[]="SQUIDEnable";
    $tokens[]="EnableUfdbGuard";
    $tokens[]="EnableUfdbGuard2";
    $tokens[]="EnableCNTLM";
    $tokens[]="PrivoxyEnabled";
    $tokens[]="EnableKerbAuth";
    $tokens[]="EnableSquidMicroHotSpot";
    $tokens[]="EnableTransparent27";
    $tokens[]="EnableArticaHotSpot";
    $tokens[]="PrivoxyEnabled";
    $tokens[]="WindowsUpdateCaching";
    $tokens[]="EnableQuotaSize";
    $tokens[]="SquidCachesProxyEnabled";
    $tokens[]="HyperCacheStoreID";

    $cron[]="proxy-filedesc-monitor";
    $cron[]="artica-ufdb-dbs";
    $cron[]="exec.postgres.hypercache.hourly.php";
    $cron[]="proxy-count-users";
    $cron[]="artica-squid-watchdog";
    $cron[]="squid-repos";
    $cron[]="artica-squid-5min";
    $cron[]="squid-storedir-5mn";
    $cron[]="squid-rotate";
    $cron[]="artica-calamaris";
    $cron[]="squidping";
    $cron[]="squid-ping-port";
    $cron[]="hotspot-task";
    $cron[]="squid-client-list";
    $cron[]="squid-run-c";
    $cron[]="squid-chown";
    $cron[]="squid-negotiateauthenticator-5mn";
    $cron[]="squid-cpus";
    $cron[]="squid-storedir-1h";
    $cron[]="squid-office365";
    $cron[]="proxy-rrd";
    $cron[]="access-parser-logs";
    $cron[]="artica-clean-RTTSize";
    $cron[]="access-parser-members";
    $cron[]="access-parser-members-h";
    $cron[]="squid-notifications";
    $cron[]="squid-analyze-url";
    $cron[]="proxy-active-requests";

    $cron_reboot=false;
    foreach ($cron as $filename){
        if(is_file("/etc/cron.d/$filename")){@unlink("/etc/cron.d/$filename");$cron_reboot=true;}

    }
    if(SQUID_MONIT_REMOVE()){
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    }

    $handle = opendir("/etc/cron.d");
    if($handle) {
        while (false !== ($filename = readdir($handle))) {
            if ($filename == ".") {continue;}
            if ($filename == "..") {continue;}
            $targetFile = "/etc/cron.d/$filename";
            if(!preg_match("#^squidsch-#",$filename)){continue;}
            @unlink($targetFile);
            $cron_reboot=true;
        }
    }

    foreach ($servicesStop as $init){
        build_progress(70, "{starting} $init");
        shell_exec("/etc/init.d/$init stop");

    }

    if($cron_reboot){UNIX_RESTART_CRON();}

    $tables[]="workers_cnx";
    $tables[]="workers_stats";
    $q=new postgres_sql();
    foreach ($tables as $table){
        $q->QUERY_SQL("TRUNCATE TABLE $table");
        build_progress($pourc++, "{reset_table} {$table}");
    }


    foreach ($tokens as $filename){
        build_progress($pourc++, "{disable} $filename");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("$filename", 0);
    }

    $directories[]="/home/artica/squidclient";
    $directories[]="/home/artica/squid";
    $rm=$unix->find_program("rm");
    foreach ($directories as $directory){
        if(is_dir($directory)){
            shell_exec("$rm -rf $directory");
        }

    }


    build_progress(80, "{remove_service} {APP_UFDBWEBSIMPLE}");
    if(is_file("/usr/share/artica-postfix/exec.ufdb-lighthttp.php")) {
        system("$php /usr/share/artica-postfix/exec.ufdb-lighthttp.php --uninstall-web");
    }
    system("/usr/sbin/artica-phpfpm-service -iptables-routers");

    if(is_file("/etc/monit/conf.d/APP_SQUID.monitrc")) {
        @unlink("/etc/monit/conf.d/APP_SQUID.monitrc");
    }
    if(is_file("/etc/monit/conf.d/APP_SQUID_CACHE_TAIL.monitrc")) {
        @unlink("/etc/monit/conf.d/APP_SQUID_CACHE_TAIL.monitrc");
    }

    if(is_file("/root/squid-good.tgz")){@unlink("/root/squid-good.tgz");}

    build_progress(90, "{restart_status_service}");
    system("/etc/init.d/artica-status restart --force");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    system("/usr/sbin/artica-phpfpm-service -build-pam");
    build_progress(95, "{restart_status_service}");
    system("/usr/sbin/artica-phpfpm-service -nsswitch");
    build_progress(97, "{restart_status_service}");
    if(is_file("/etc/init.d/munin-node")){
        system("$php /usr/share/artica-postfix/exec.munin.php --reconfigure");
    }
    remove_service("/etc/init.d/squid");
    build_progress(100, "{done}");



}

function restart_upgrade(){
    $unix       = new unix();
    $php        = $unix->LOCATE_PHP5_BIN();
    $kill       = $unix->find_program("kill");
    $nohup      = $unix->find_program("nohup");
    $Aroot      = ARTICA_ROOT;
    if(is_file("/usr/sbin/proxy-logs-monitor")){
        build_progress(10,"Migrating Proxy monitor...");
        for($i=1;$i<6;$i++) {
            $pid = $unix->PIDOF("/usr/sbin/proxy-logs-monitor");
            if (!$unix->process_exists($pid)) {
                break;
            }
            echo "Killing PID $pid";
            shell_exec("$kill -9 $pid");
        }
        shell_exec(@unlink("/usr/sbin/proxy-logs-monitor"));
        if(is_file("$Aroot/exec.cache-logs.php")){@unlink("$Aroot/exec.cache-logs.php");}
    }
    build_progress(30,"Stopping Artica-status");
    shell_exec("/etc/init.d/artica-status restart");
    build_progress(50,"Reinstalling Service...");
    buildSyslog();
    build_progress(82,"{restarting_proxy_service}");
    shell_exec("$nohup /etc/init.d/squid restart --force >/dev/null 2>&1 &");
    sleep(3);
    build_progress(83,"Restarting Proxy Watchdog");
    shell_exec("/etc/init.d/cache-tail restart");



    build_progress(90,"Force inventory");
    $unix->ToSyslog("Running $php $Aroot/exec.status.php --process1 --force","SYSTEM-UPGRADE");
    shell_exec("$php $Aroot/exec.status.php --process1 --force");
    $unix->ToSyslog("Running $php $Aroot/exec.apt-get.php --grubpc","SYSTEM-UPGRADE");
    build_progress(95,"Force inventory");
    shell_exec("$php $Aroot/exec.apt-get.php --grubpc >/dev/null 2>&1");
    build_progress(100,"{done}....");

}




function url_checking(){
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $UrlCheckingEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlCheckingEnable"));
    if($SQUIDEnable==0){$UrlCheckingEnable=0;}

    if($UrlCheckingEnable==0){
        if(is_file("/etc/cron.d/squid-url-checker")){
            @unlink("/etc/cron.d/squid-url-checker");
            UNIX_RESTART_CRON();
        }
        return true;
    }

    if(is_file("/etc/cron.d/squid-url-checker")){
        return true;
    }

    $unix=new unix();
    $unix->Popuplate_cron_make("squid-url-checker","* * * * *","exec.squid.url.checker.php");
    UNIX_RESTART_CRON();
    return true;
}

function SQUID_MONIT_REMOVE():bool{
    $f[]="/etc/monit/conf.d/ssl_db.monitrc";
    $f[]="/etc/monit/conf.d/APP_SQUID.monitrc";
    $f[]="/etc/monit/conf.d/APP_SQUID_4755.monitrc";
    $f[]="/etc/monit/conf.d/APP_SQUID_CACHE_TAIL.monitrc";
    $REMOVED=false;
    foreach ($f as $fpath){
        if(is_file($fpath)){
            @unlink($fpath);$REMOVED=true;
        }
    }
    return $REMOVED;
}
function SQUID_MONIT_CPU_ALERT($CPU):bool{
    $unix=new unix();
    $ps=$unix->find_program("ps");
    $grep=$unix->find_program("grep");
    exec("$ps -auxwww | $grep squid 2>&1",$results);
    squid_admin_mysql(0,"Proxy service reach CPU alert > {$CPU}% [{action}]=[{restart}]",
        @implode("\n",$results),__FILE__,__LINE__);

    $unix->go_exec("/usr/sbin/artica-phpfpm-service -restart-proxy");
    return true;
}
function SQUID_MONIT_MEM_ALERT($PMEM):bool{

    $unix=new unix();
    $ps=$unix->find_program("ps");
    $grep=$unix->find_program("grep");
    exec("$ps -auxwww | $grep squid 2>&1",$results);
    squid_admin_mysql(0,"Proxy service reach memory alert > {$PMEM}% [{action}]=[{restart}]",
        @implode("\n",$results),__FILE__,__LINE__);
    $unix->go_exec("/usr/sbin/artica-phpfpm-service -restart-proxy");
    return true;
}
function SQUID_MONIT(){
    url_checking();

    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0){
        if(SQUID_MONIT_REMOVE()){
            shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
        }
        return;
    }
    $unix=new unix();

    $UrlNetAnalyze=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrlNetAnalyze"));

    if($UrlNetAnalyze==1){
        build_syslog_internetwatch();
        if(!is_file("/etc/cron.d/squid-analyze-url")) {
            $unix->Popuplate_cron_make("squid-analyze-url", "*/5 * * * *", "/usr/share/artica-postfix/bin/internetwatch");
            $unix->go_exec("/etc/init.d/cron restart");
        }
    }else{
        if(is_file("/etc/cron.d/squid-analyze-url")) {
            @unlink("/etc/cron.d/squid-analyze-url");
            $unix->go_exec("/etc/init.d/cron restart");
        }
    }
    build_progress_monit(100,"{watchdog} {success}");


}