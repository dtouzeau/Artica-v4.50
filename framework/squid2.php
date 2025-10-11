<?php
$GLOBALS["ARTPATH"]="/usr/share/artica-postfix";
ini_set('error_reporting', E_ALL);
if(isset($_GET["verbose"])){
    ini_set('display_errors', 1);
    ini_set('html_errors',0);
    ini_set('display_errors', 1);

    $GLOBALS["VERBOSE"]=true;
}
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
if(isset($_GET["disable-proxy-service"])){disable_proxy_service();exit;}
if(isset($_GET["kwts-check"])){kwts_check();exit;}
if(isset($_GET["kwts-monit"])){kwts_monit();exit;}


if(isset($_GET["urlsdb-search"])){urlsdb_search();exit;}
if(isset($_GET["urlsdb-upload"])){urlsdb_uppload();exit;}
if(isset($_GET["import-acls-items"])){import_acls_items();exit;}
if(isset($_GET["identd-enable"])){identd_enable();exit;}
if(isset($_GET["identd-disable"])){identd_disable();exit;}
if(isset($_GET["ntlmfake-enable"])){ntlmfake_enable();exit;}
if(isset($_GET["ntlmfake-disable"])){ntlmfake_disable();exit;}
if(isset($_GET["disable-cache"])){disable_cache();exit;}
if(isset($_GET["disable-hypercache-urgency"])){disable_urgency_storeid();exit;}
if(isset($_GET["disable-mactouid-urgency"])){disable_urgency_mactouid();exit;}
if(isset($_GET["hypercache-status"])){hypercache_status();exit;}
if(isset($_GET["purge-dns"])){purge_dns();}
if(isset($_GET["icap-silent"])){icap_silent();exit;}
if(isset($_GET["acls-dynamic"])){acls_dynamics();exit;}
if(isset($_GET["categories-hot"])){categories_hot();exit;}
if(isset($_GET["tcpkeepalive-defaults"])){tcpkeepalive_defaults();exit;}

if(isset($_GET["ftp-params"])){ftp_parameters();exit;}
if(isset($_GET["stored-objects"])){stored_objects();exit;}
if(isset($_GET["quotasize-install"])){quotasize_install();exit;}
if(isset($_GET["quota-size-status"])){quotasize_status();exit;}
if(isset($_GET["quotasize-status"])){quotasize_status();exit;}
if(isset($_GET["quotasize-uninstall"])){quotasize_uninstall();exit;}
if(isset($_GET["reload-progress"])){reload_progress();exit;}
if(isset($_GET["dump-hour-progress"])){DUMP_HOUR_PROGRESS();exit;}
if(isset($_GET["windows-update-delete"])){windows_update_delete();exit;}
if(isset($_GET["wizard-ports"])){wizard_ports();exit;}

if(isset($_GET["caches-rules-progress"])){caches_rules_progress();exit;}
if(isset($_GET["ntlm-install-progress"])){ntlm_install_progress();exit;}
if(isset($_GET["ntlm-uninstall-progress"])){ntlm_uninstall_progress();exit;}
if(isset($_GET["ntlm-reconfigure-progress"])){ntlm_reconfigure_progress();exit;}
if(isset($_GET["ntlm-monitor-status"])){ntlm_monitor_status();exit;}
if(isset($_GET["kerbauth-squid-progress"])){ntlm_squid_compile();exit;}
if(isset($_GET["run-calamaris"])){run_calamaris();exit;}
//EnableHotSpotInSquid
if(isset($_GET["quotarules-status-progress"])){quotarules_status_progress();exit;}
if(isset($_GET["clean-logs-emergency"])){clean_logs_emergency();exit;}
if(isset($_GET["rebuild-and-restart"])){rebuild_and_restart();exit;}
if(isset($_GET["rebuild-and-restart-complete"])){rebuild_and_restart_complete();exit;}
if(isset($_GET["squidclient-ipcache"])){squidclient_ipcache();exit;}


if(isset($_GET["emergency-activedirectory-progress"])){activedirectory_emergency_progress();exit;}
if(isset($_GET["kerberos-manual-conf"])){kerberos_manual_conf();exit;}
if(isset($_GET["acls-peer-manual-conf"])){acls_peer_conf();exit;}


if(isset($_GET["SquidReloadInpublicAlias"])){SquidReloadInpublicAlias();exit;}
if(isset($_GET["import-old-logs-files"])){import_old_logs_files();exit;}
if(isset($_GET["build-templates-background"])){squid_templates_background();exit;}
if(isset($_GET["squidclient-mgr-storedir"])){squidclient_mgr_storedir();exit;}
if(isset($_GET["watchdog-bandwidth"])){watchdog_bandwidth();exit;}
if(isset($_GET["remove-influxdb"])){remove_influx_db();exit;}
if(isset($_GET["disable-influxdb"])){disable_influx_db();exit;}
if(isset($_GET["enable-influxdb"])){enable_influx_db();exit;}
if(isset($_GET["test-ssl-port"])){test_ssl_port();exit;}
if(isset($_GET["cached-kerberos-tickets"])){cached_kerberos_tickets();exit;}

if(isset($_GET["squid-conf-ports"])){squid_ports_conf();exit;}
if(isset($_GET["squid-conf-ssl"])){squid_ssl_conf();exit;}
if(isset($_GET["squid-conf-externals"])){squid_externals_conf();exit;}


if(isset($_GET["saveSquidPortContent"])){squid_ports_conf_save();exit;}
if(isset($_GET["saveSquidSSLContent"])){squid_ssl_conf_save();exit;}
if(isset($_GET["ssl-satus"])){ssl_status();exit;}

if(isset($_GET["saveSquidExternalContent"])){squid_external_conf_save();exit;}
if(isset($_GET["watchdog-restart"])){watchdog_restart();exit;}

if(isset($_GET["global-ufdb-client"])){squid_ufdbclient();exit;}
if(isset($_GET["global-timeouts-center"])){squid_timeouts();exit;}
if(isset($_GET["global-common-cache"])){configure_cache();exit;}
if(isset($_GET["global-common-center"])){global_common_center();exit;}
if(isset($_GET["global-outgoing-center"])){global_outgoing_center();exit;}
if(isset($_GET["global-reply-access-center"])){global_reply_access_center();exit;}
if(isset($_GET["global-logging-center"])){global_logging_center();exit;}
if(isset($_GET["global-plugins-center"])){global_plugins_center();exit;}
if(isset($_GET["global-denysources-center"])){global_denysources_center();exit;}
if(isset($_GET["global-httpaccess-default"])){http_access_default();exit;}
if(isset($_GET["global-caches-tuning"])){global_cachestweak_center();exit;}
if(isset($_GET["global-caches-tuning-restart"])){global_cachestweak_center_restart();exit;}
if(isset($_GET["build-general-config"])){global_general_config();exit;}
if(isset($_GET["outgoingmark"])){outgoingmark();exit;}

if(isset($_GET["explain-this-rule"])){explain_this_rule();exit;}
if(isset($_GET["explain-all-rules"])){explain_all_rules();exit;}

if(isset($_GET["siege-status"])){siege_status();exit;}
if(isset($_GET["siege-stop"])){siege_stop();exit;}
if(isset($_GET["analyze-access"])){siege_analyze();exit;}
if(isset($_GET["schedule-purge"])){schedule_purge();exit;}

if(isset($_GET["global-ports-center"])){global_ports_center();exit;}
if(isset($_GET["disable-ufdb-urgency"])){squid_disable_ufdbemergency();exit;}
if(isset($_GET["ufdb-ini-status-write"])){ufdb_ini_status();exit;}
if(isset($_GET["ssl-rules"])){squid_ssl_rules();exit;}


if(isset($_GET["monit-config"])){monit_config();}
if(isset($_GET["install-cache-service"])){cache_install();exit;}
if(isset($_GET["enable-cache-service"])){cache_enable();exit;}
if(isset($_GET["disable-cache-service"])){cache_disable();exit;}

if(isset($_GET["enable-restful-service"])){restful_enable();exit;}
if(isset($_GET["disable-restful-service"])){restful_disable();exit;}
if(isset($_GET["restart-upgrade"])){restart_upgrade();exit;}
if(isset($_GET["testTheWhiteLists"])){testTheWhiteLists();exit;}

if(isset($_GET["create-cache-wizard"])){create_cache_wizard();exit;}
if(isset($_GET["itchart-build"])){it_chart_build();exit;}
if(isset($_GET["allow-80443-port"])){allow_8083_port();exit;}
if(isset($_GET["reload-squid-cache"])){reload_squid_cache();exit;}
if(isset($_GET["logsfinder"])){logsfinder();exit;}
if(isset($_GET["useragents-rules"])){squid_useragents_rules();exit;}
if(isset($_GET["purge-delete"])){purge_delete();exit;}
if(isset($_GET["purge-deleteuri"])){purge_deleteuri();exit;}

if(isset($_GET["quick-stop"])){quick_stop();exit();}
if(isset($_GET["quick-start"])){quick_start();exit();}
if(isset($_GET["quick-restart"])){exit();}
if(isset($_GET["dnsdist-status"])){dnsdist_status();exit;}
if(isset($_GET["ntpad-ntlm"])){ntpad_ntlm();exit;}
if(isset($_GET["squid-service"])){squid_service();exit;}
if(isset($_GET["proxy-lb-install"])){squid_lb_install();exit;}
if(isset($_GET["proxy-lb-uninstall"])){squid_lb_uninstall();exit;}
if(isset($_GET["proxy-lb-reload"])){squid_lb_reload();exit;}
if(isset($_GET["proxy-lb-restart"])){squid_lb_restart();exit;}
if(isset($_GET["proxy-lb-events"])){squid_lb_events();exit;}
if(isset($_GET["acls-bulk"])){acls_bulk();exit;}
if(isset($_GET["acls-import-file"])){acls_import_file();exit;}
if(isset($_GET["acls-export-rule"])){acls_export_rule();exit;}
if(isset($_GET["readonly"])){readonly_on();exit;}
if(isset($_GET["readonly-off"])){readonly_off();exit;}
if(isset($_GET["compress-access"])){compress_access_log();exit;}
if(isset($_GET["import-blacklist"])){import_blacklist();}
if(isset($_GET["export-blacklists"])){export_blacklists();exit;}
if(isset($_GET["check-domain"])){check_domain();exit;}


foreach ($_GET as $num=>$line){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!...`" .@implode(",",$f)."`","main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);

//-------------------------------------------------------------------------------------------------------
function hypercache_status():bool{
    $unix=new unix();
    $unix->framework_exec("exec.status.php --hypercache");
    return true;

}
function import_blacklist():bool{
    $unix=new unix();
    $file=$_GET["import-blacklist"];
    $unix->framework_execute("exec.squid.global.whitelists.php --import \"$file\"",
        "squid.wb.import.progress","squid.wb.import.txt");
    return true;
}
function check_domain():bool{
    $domain=$_GET["check-domain"];
    $unix=new unix();
    $unix->framework_execute("bin/checkdomain -domain \"$domain\"",
        "checkdomain.progress",
        "checkdomain.log");
    return true;
}


function export_blacklists():bool{
    $unix=new unix();
    $unix->framework_execute("exec.squid.php --export-blacklists",
        "squid.export.progress","squid.export.txt");
    return true;
}
function acls_import_file():bool{
    $file=base64_encode($_GET["acls-import-file"]);
    $unix=new unix();
    return $unix->framework_execute("exec.squid.acls.parse.php --import $file","acls.parse","acls.logs");

}
function squid_ufdbclient():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.squid.global.access.php",
        "squid.access.center.progress",
        "squid.access.center.progress.log");

}
function acls_export_rule():bool{
    $ID=intval($_GET["acls-export-rule"]);
    $unix=new unix();
    return $unix->framework_execute("exec.squid.acls.parse.php --export-rule $ID","acls.parse","acls.logs");
}

function compress_access_log():bool{
    $unix=new unix();
    $fname=$_GET["compress-access"];
    $unix->framework_execute("exec.squid.php --compress-access-log $fname",
        "squid.access.compress.progress",
        "squid.access.compress.log");
    return true;
}


function squidclient_mgr_storedir():bool{
    $unix=new unix();
    $data=$unix->squidclient("storedir",true);
    @unlink("/usr/share/artica-postfix/ressources/logs/web/storedir.cache");
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/storedir.cache", $data);
    return true;
}
function tcpkeepalive_defaults():bool{
    $tcp_keepalive_time=intval(@file_get_contents("/proc/sys/net/ipv4/tcp_keepalive_time"));
    $tcp_keepalive_intvl=intval(@file_get_contents("/proc/sys/net/ipv4/tcp_keepalive_intvl"));
    $tcp_keepalive_probes=intval(@file_get_contents("/proc/sys/net/ipv4/tcp_keepalive_probes"));
    $CONF["ide"]=$tcp_keepalive_time;
    $CONF["interval"]=$tcp_keepalive_intvl;
    $CONF["probe"]=$tcp_keepalive_probes;
    @file_put_contents(PROGRESS_DIR."/kernel_tcp_keepalive",serialize($CONF));
    return true;
}

function readonly_on(){
    $unix=new unix();
    $unix->framework_execute("exec.squid.readonly.php --on",
        "squid.readonly.progress",
        "squid.readonly.progress.txt");
}
function readonly_off(){
    $unix=new unix();
    $unix->framework_execute("exec.squid.readonly.php --off",
        "squid.readonly.progress",
        "squid.readonly.progress.txt");
}
function dnsdist_status(){
    $unix=new unix();
    $cmdline    = "exec.status.php --squid-dnsdist";
    $pr         = null;
    $pl         = null;

    $unix->framework_execute($cmdline,$pr,$pl);
}
function squid_lb_install(){
    $unix=new unix();
    $unix->framework_execute("exec.squid.dns.php --install","proxydns.progress","proxydns.log");
}
function squid_lb_uninstall(){
    $unix=new unix();
    $unix->framework_execute("exec.squid.dns.php --uninstall","proxydns.progress","proxydns.log");
}
function squid_lb_reload(){
    $unix=new unix();
    $unix->framework_execute("exec.squid.dns.php --reload","proxydns.progress","proxydns.log");
}
function squid_lb_restart(){
    $unix=new unix();
    $unix->framework_execute("exec.squid.dns.php --restart","proxydns.restart.progress","proxydns.restart.log");
}
function squid_lb_events(){

    $unix=new unix();
    $unix->framework_search_syslog($_GET["proxy-lb-events"],
        "/var/log/squid/dns.log",
        "proxylb.events.syslog","
        ");
}


function ntpad_ntlm(){
    $unix=new unix();
    $unix->framework_exec("exec.nltm.connect.php --ntpad");
}
function squid_service(){
    $unix=new unix();
    $unix->framework_exec("exec.squid.disable.php --squid-service");
}

function quick_stop() {
    $unix=new unix();
    $cmdline    = "bin/squid-service stop";
    $pr         = "squid.quick.progress";
    $pl         = "squid.quick.log";
    $unix->framework_execute($cmdline,$pr,$pl);
}
function quick_start() {
    $unix=new unix();
    $cmdline    = "bin/squid-service start";
    $pr         = "squid.quick.progress";
    $pl         = "squid.quick.log";
    $unix->framework_execute($cmdline,$pr,$pl);
}

function kwts_check(){
    $unix=new unix();
    $unix->framework_exec("exec.c-icap.install.php --kwts-check");
}
function kwts_monit() {
    $unix=new unix();
    $unix->framework_exec("exec.c-icap.install.php --kwts-monit");
}





function explain_this_rule(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $ID=intval($_GET["explain-this-rule"]);
    if($ID==0){
        writelogs_framework("ID === 0 ..?." ,__FUNCTION__,__FILE__,__LINE__);
        return;
    }
    $cmd="$nohup $php5 {$GLOBALS["ARTPATH"]}/exec.proxy.acls.explains.php --rule $ID >/dev/null 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function explain_all_rules(){
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/sync.rules.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/sync.rules.progress.log";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($ARRAY["PROGRESS_FILE"], serialize($array));
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $cmd="$nohup $php5 {$GLOBALS["ARTPATH"]}/exec.proxy.acls.explains.php >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function stringToRegex34($str){
    $str = str_replace("/", "\/", $str);
    $str = str_replace("?", "\?", $str);
    $str = str_replace("*", "\*", $str);
    $str = str_replace(".", "\.", $str);
    return $str;
}

function purge_deleteuri(){
    $url=stringToRegex34($_GET["purge-deleteuri"]);
    $domain=stringToRegex34($_GET["domain"]);
    $unix = new unix();
    $proxyIP = "127.0.0.1";
    $proxyport = $unix->squid_internal_port();
    $proxyaddr = "$proxyIP:$proxyport";
    $nohup = $unix->find_program("nohup");
    $purge = $unix->find_program("purge");
    $pattern="$domain.*$url";
    $cmd = "$nohup $purge -p $proxyaddr -c /etc/squid3/caches.conf -e \"$pattern\" -P 1 >/dev/null 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function purge_delete(){
    $pattern = stringToRegex34($_GET["purge-delete"]);
    $unix = new unix();
    $proxyIP = "127.0.0.1";
    $proxyport = $unix->squid_internal_port();
    $proxyaddr = "$proxyIP:$proxyport";
    $nohup = $unix->find_program("nohup");
    $purge = $unix->find_program("purge");
    $cmd = "$nohup $purge -p $proxyaddr -c /etc/squid3/caches.conf -e \"$pattern\" -P 1 >/dev/null 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}



function urlsdb_uppload(){
    $filename=$_GET["urlsdb-upload"];
    $gpid=intval($_GET["groupid"]);
    if($gpid==0){return false;}
    $WorkPath = "/etc/squid3/acls/urlsdb/$gpid";
    $tempfile="/usr/share/artica-postfix/ressources/conf/upload/$filename";
    if(!is_file($tempfile)){return false;}
    if(!is_dir($WorkPath)){@mkdir($WorkPath,0755,true);}
    $cmd="/usr/share/artica-postfix/external_acl_urlsfetchdb.py $gpid \"$filename\" >/usr/share/artica-postfix/ressources/logs/external_acl_urlsfetchdb.$gpid.logs 2>&1";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    return true;
}

function urlsdb_search(){
    $LIMIT=50;
    $unix=new unix();
    $search=$_GET["urlsdb-search"];
    $gpid=intval($_GET["gpid"]);

    $source="/etc/squid3/acls/urlsdb/$gpid/SOURCE";
    $dest="/usr/share/artica-postfix/ressources/logs/urlsdb.$gpid.log";

    if(preg_match("#^(.*?)LIMIT\s+([0-9]+)#i",$search,$re)){
        $search=trim($re[1]);
        $LIMIT=intval($re[2]);
    }

    $head=$unix->find_program("head");
    if($search==null){
        $cmd="$head -n $LIMIT $source >$dest 2>&1";
        writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        @chmod($dest,0755);
        return true;
    }

    $search=str_replace(".","\.",$search);
    $search=str_replace("/","\/",$search);
    $search=str_replace("*",".*?",$search);
    $grep=$unix->find_program("grep");
    $cmd="$grep -E \"$search\" $source| $head -n $LIMIT >$dest 2>&1";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chmod($dest,0755);
    return true;


}




function acls_peer_conf(){
    $unix=new unix();
    $nohup      = $unix->find_program("nohup");
    $basesrc    = "acls_peer.conf";
    $errfile    = PROGRESS_DIR."/$basesrc.error";
    $srcfile    = "/etc/squid3/$basesrc";
    $bakfile    = "$srcfile.bak";
    $testfile   = UPLOAD_DIR."/$basesrc";
    if(!is_file($testfile)){return false;}
    if(is_file($bakfile)){@unlink($bakfile);}
    if(is_file($errfile)){@unlink($errfile);}
    @copy($srcfile,$bakfile);
    @unlink($srcfile);
    @copy($testfile,$srcfile);
    @chown($srcfile,"squid");
    @chmod($srcfile,0755);
    @unlink($testfile);
    $squidbin=$unix->LOCATE_SQUID_BIN();
    exec("$squidbin -f /etc/squid3/squid.conf -k check 2>&1",$results);

    foreach ($results as $line){
        if(preg_match("#(unrecognized|FATAL|Bungled|Segmentation fault)#", $line)){
            @file_put_contents($errfile,$line);
            @chmod($errfile,0755);
            @unlink($srcfile);
            @copy($bakfile,$srcfile);
            @chown($srcfile,"squid");
            @chmod($srcfile,0755);
            return false;
        }
    }
    shell_exec("$nohup /usr/sbin/artica-phpfpm-service -reload-proxy >/dev/null 2>&1 &");
    return true;

}

function kerberos_manual_conf(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $errfile="/usr/share/artica-postfix/ressources/logs/authenticate.error";
    $srcfile="/etc/squid3/authenticate.conf";
    $bakfile="/etc/squid3/authenticate.conf.bak";
    $testfile="/usr/share/artica-postfix/ressources/conf/upload/authenticate.conf";
    if(!is_file("/usr/share/artica-postfix/ressources/conf/upload/authenticate.conf")){return false;}
    if(is_file($bakfile)){@unlink($bakfile);}
    if(is_file($errfile)){@unlink($errfile);}
    @copy($srcfile,$bakfile);
    @unlink($srcfile);
    @copy($testfile,$srcfile);
    @chown($srcfile,"squid");
    @chmod($srcfile,0755);
    @unlink($testfile);
    $squidbin=$unix->LOCATE_SQUID_BIN();
    exec("$squidbin -f /etc/squid3/squid.conf -k check 2>&1",$results);

    foreach ($results as $line){
        if(preg_match("#(unrecognized|FATAL|Bungled|Segmentation fault)#", $line)){
            @file_put_contents($errfile,$line);
            @chmod($errfile,0755);
            @unlink($srcfile);
            @copy($bakfile,$srcfile);
            @chown($srcfile,"squid");
            @chmod($srcfile,0755);
            return false;
        }
    }
    shell_exec("$nohup /usr/sbin/artica-phpfpm-service -reload-proxy >/dev/null 2>&1 &");
    return true;

}

function purge_dns(){

    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.dns.purge.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.dns.purge.progress.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.purge.dns.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

//---------------------------------------------------------------------------------------------------
function watchdog_bandwidth(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd="$nohup $php /usr/share/artica-postfix/exec.squid.watchdog.php --bandwidth-cron >/dev/null 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function reload_squid_cache(){
    $unix=new unix();
    $squidbin=$unix->LOCATE_SQUID_BIN();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();

    if( is_file($squidbin)){
        squid_admin_mysql(2, "{reloading_proxy_service} by framework (".__FUNCTION__.")", null,__FILE__,__LINE__);
        system("/usr/sbin/artica-phpfpm-service -reload-proxy");
    }

    if(is_file("/etc/init.d/fail2ban")){
        $cmd="$nohup $php /usr/share/artica-postfix/exec.fail2ban.php --build >/dev/null 2>&1 &";
        writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
    }


}

function categories_hot(){
    $CATEGORIES_HOT=unserialize(@file_get_contents("/var/log/squid/category.array"));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CATEGORIES_HOT", count($CATEGORIES_HOT));
}

function disable_cache(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd="$nohup $php /usr/share/artica-postfix/exec.squid.php --disable-cache >/dev/null 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function schedule_purge(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $EnableSquidPurge=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidPurge"));

    if($EnableSquidPurge==0){
        if(is_file("/etc/cron.d/artica-squid-purge")){
            @unlink("/etc/cron.d/artica-squid-purge");
            shell_exec("$nohup /etc/init.d/cron restart >/dev/null 2>&1 &");

        }
        return;
    }

    $SquidPurgeTime=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidPurge"));
    $tt=explode(":",$SquidPurgeTime);
    $schedule=intval($tt[1])." ".intval($tt[0])." * * *";
    $unix->Popuplate_cron_make("artica-squid-purge",$schedule,"exec.purge.php");



}



function squid_templates_background(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd="$nohup $php /usr/share/artica-postfix/exec.squid.templates.php --force --progress >/dev/null 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

    $cmd=trim("$nohup $php /usr/share/artica-postfix/exec.c-icap.php --template >/dev/null 2>&1 &");
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

    $cmd=trim("$nohup $php /usr/share/artica-postfix/exec.privoxy.php --template >/dev/null 2>&1 &");
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}




function quotasize_status(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 /usr/share/artica-postfix/exec.status.php --squid-tail-size >/usr/share/artica-postfix/ressources/databases/QUOTA_SIZE_STATUS 2>&1";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}


function disable_urgency_mactouid(){
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    ;
    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($ARRAY["PROGRESS_FILE"], serialize($array));
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --uuidurgency >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function disable_urgency_storeid(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.urgency.hypercache.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.urgency.hypercache.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.hypercache-dedup.php --urgency >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}


function ftp_parameters(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --ftp >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function icap_silent(){
    $unix=new unix();
    $unix->framework_execute("exec.squid.global.access.php --icap-silent",
    "squid.access.center.progress","squid.access.center.progress.log");
}

function monit_config(){

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.monit.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.monit.progress.log";
    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($ARRAY["PROGRESS_FILE"], serialize($array));
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.disable.php --monit >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}





function http_access_default(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --http-access-default >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function cache_install(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --install-cache >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function cache_enable(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --enable-cache >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function restful_enable(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --enable-restful >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}




function restful_disable(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --disable-restful >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function cache_disable(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --disable-cache >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function acls_bulk(){
    $unix=new unix();
    $unix->framework_exec("exec.squid.acls.bulk.php");
}




function acls_dynamics(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.acls.dynamic.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.acls.dynamic.progress.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.acls.dynamics.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}





function identd_enable(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --identd-enable >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function ntlmfake_enable(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --ntlmfake-enable >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function ntlmfake_disable(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --ntlmfake-disable >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function identd_disable(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --identd-disable >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}



function disable_proxy_service(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.disable.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.disable.progress.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup /usr/sbin/artica-phpfpm-service -uninstall-proxy >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);

    squid_admin_mysql(0, "Removing proxy service and all associated service!",
        null, __FILE__, __LINE__);

    shell_exec($cmd);


}

function quotasize_uninstall(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.quotasize.object.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.quotasize.object.progress.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.quotasize.php --remove >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function quotasize_install(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.quotasize.object.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.quotasize.object.progress.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.quotasize.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function logsfinder(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.logsfinder.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.logsfinder.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $python=$unix->find_program("python");
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $python /usr/share/artica-postfix/squid-logssearch.py {$_GET["logsfinder"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function caches_rules_progress(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.cached.sitesinfos.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.cached.sitesinfos.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.cached.sitesinfos.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function ntlm_uninstall_progress(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ntlm.service.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ntlm.service.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.ntlm-monitor.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function run_calamaris(){

    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/calamaris.run.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/calamaris.run.progress.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.calamaris.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function ntlm_reconfigure_progress(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ntlm.service.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ntlm.service.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.ntlm-monitor.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function ntlm_install_progress(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ntlm.service.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ntlm.service.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.ntlm-monitor.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function stored_objects(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.purge.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.purge.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.purge.php --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}



function ufdbcat_check_categories(){
}

function reload_progress(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.reload.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.reload.progress.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.php --kreconfigure --force --verbose >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}

function DUMP_HOUR_PROGRESS(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/DUMP_HOUR_PROGRESS";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/DUMP_HOUR_PROGRESS.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.interface-size.php --dump-hour --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}




function rebuild_and_restart(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["CACHEFILE"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.rebuild-restart.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function rebuild_and_restart_complete(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    @unlink("/root/squid-good.tgz");
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.complete-rebuild.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["CACHEFILE"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.rebuild-restart.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}


function clean_logs_emergency(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.cleanlogs.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.cleanlogs.progress.txt";
    @mkdir("/usr/share/artica-postfix/ressources/logs/web",0755,true);
    @unlink($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["CACHEFILE"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.cleanlogs-emergency.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}


function activedirectory_emergency_progress(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.urgency.disable.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.ad.emergency.progress.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["CACHEFILE"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.urgency.remove.php --ad-on >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}





//-----------------------------------------------------------------------------------------------------------------------------------
function remove_influx_db(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.remove.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/influxdb.remove.progress.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.influxdb.php --remove-db >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
//-----------------------------------------------------------------------------------------------------------------------------------
function test_ssl_port(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.testssl.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.testssl.progress.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.testssslports.php {$_GET["ID"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}



function watchdog_restart(){
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.watchdog.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.watchdog.progress.log";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
    @chmod($GLOBALS["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.init-tail-cache.php --restart >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    $unix->ToSyslog($cmd);
    shell_exec($cmd);

}
function quotarules_status_progress(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.quotasband.status.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.quotasband.status.progress.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.quotaband.php --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function SquidReloadInpublicAlias(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/SquidReloadInpublicAlias.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/SquidReloadInpublicAlias.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);

    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.php --SquidReloadInpublicAlias >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function disable_influx_db(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.remove.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/influxdb.remove.progress.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.influxdb.php --disable-db >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function enable_influx_db(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.remove.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/influxdb.remove.progress.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.influxdb.php --enable-db >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}



function allow_8083_port(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.ports.80.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.ports.80.progress.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid80443.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function import_old_logs_files(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.statistics.import.local.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.statistics.import.local.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.influx.import.php --scandir >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}



function it_chart_build(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/ichart.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/itchart.progress.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.itchart.php --build-rules >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function ntlm_squid_compile(){
    $unix=new unix();
    $unix->framework_execute("exec.squid.global.access.php --auth",
        "squid.access.center.progress","squid.access.center.progress.log");


}
function global_cachestweak_center(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --cache-tweaks >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    return true;
}

function global_general_config(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.general.config.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/squid.general.config.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --general >{$GLOBALS["LOGSFILES"]} 2>&1 &";

    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    return true;
}

function global_cachestweak_center_restart(){
    $unix=new unix();
    $unix->framework_execute("exec.squid.global.access.php --cache-tweaks --restart"
    ,"squid.access.center.progress", "squid.access.center.progress.log");
}

function global_denysources_center(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --denys-sources >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}


function global_reply_access_center(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --reply-access >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}

function global_outgoing_center(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --outgoingaddr >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function outgoingmark(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --outgoingmark >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}



function global_common_center(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --common >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function configure_cache(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --configure-cache >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}

function siege_stop(){
    $unix=new unix();
    $siege="/usr/share/artica-postfix/bin/siege";
    if(!is_file($siege)){$siege=$unix->find_program("siege");}
    $PID=$unix->PIDOF($siege);
    if(!$unix->process_exists($PID)){return false;}
    $kill=$unix->find_program("kill");
    shell_exec("$kill $PID");
}

function siege_analyze(){

    $fname=$_GET["analyze-access"];
    writelogs_framework("----------------------------------> $fname",__FUNCTION__,__FILE__,__LINE__);

    $unix=new unix();
    $unix->framework_execute("exec.siege.php --analyze-access \"$fname\"","access.log.parser",
        "access.log.parser.debug");

}

function siege_status(){
    $unix=new unix();
    $f[]="[PROCESS]";

    $siege="/usr/share/artica-postfix/bin/siege";
    if(!is_file($siege)){$siege=$unix->find_program("siege");}
    if(!is_file($siege)){
        $f[]="running=0";
        @file_put_contents(PROGRESS_DIR."/siege.status",@implode("\n",$f));
        return true;
    }
    $PID=$unix->PIDOF($siege);
    if(!$unix->process_exists($PID)){
        $f[]="running=0";
        @file_put_contents(PROGRESS_DIR."/siege.status",@implode("\n",$f));
        return true;
    }
    $f[]="running=1";
    $f[]=$unix->GetMemoriesOf($PID);
    @file_put_contents(PROGRESS_DIR."/siege.status",@implode("\n",$f));
    return true;

}

function global_logging_center(){
    $unix=new unix();



    if(!is_file("/etc/init.d/squid")){
        return false;
    }

    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --logging >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    return true;
}

function global_plugins_center(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup /usr/sbin/artica-phpfpm-service -proxy-plugins >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function squid_useragents_rules(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --usersagents >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function global_ports_center(){
    $cmdadd=null;
    if(isset($_GET["restart"])){$cmdadd=" --restart";}
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --ports{$cmdadd} --firehol --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function restart_upgrade(){
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.disable.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.disable.progress.log";
    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    $array["POURC"]=2;
    $array["TEXT"]="{please_wait}";
    @file_put_contents($ARRAY["PROGRESS_FILE"], serialize($array));
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.disable.php --restart-upgrade >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function squid_timeouts(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR ."/squid.access.center.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR ."/squid.access.center.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.global.access.php --timeouts >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function squid_ssl_rules():bool{
    $unix=new unix();
    $unix->framework_execute("exec.squid.ssl.rules.php","squid.ssl.rules.progress","squid.ssl.rules.progress.log");
    return true;
}

function squid_disable_ufdbemergency(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.urgency.disable.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.urgency.disable.progress.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.urgency.remove.php --ufdb-off >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function squid_ports_conf(){
    @unlink("/usr/share/artica-postfix/ressources/logs/web/squid-ports.conf");
    @copy("/etc/squid3/listen_ports.conf","/usr/share/artica-postfix/ressources/logs/web/squid-ports.conf");
    @chmod("/usr/share/artica-postfix/ressources/logs/web/squid-ports.conf",0755);
}
function squid_ssl_conf(){
    @unlink("/usr/share/artica-postfix/ressources/logs/web/squid-ssl.conf");
    @copy("/etc/squid3/ssl.conf","/usr/share/artica-postfix/ressources/logs/web/squid-ssl.conf");
    @chmod("/usr/share/artica-postfix/ressources/logs/web/squid-ssl.conf",0755);

}
function squid_externals_conf(){
    @unlink("/usr/share/artica-postfix/ressources/logs/web/squid-extern.conf");
    @copy("/etc/squid3/external_acls.conf","/usr/share/artica-postfix/ressources/logs/web/squid-extern.conf");
    @chmod("/usr/share/artica-postfix/ressources/logs/web/squid-extern.conf",0755);
}

function squid_external_conf_save(){
    $datas=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/externals.conf");
    writelogs_framework(strlen($datas)/1024 ." Ko",__FUNCTION__,__FILE__,__LINE__);
    if($datas==null){

        echo "<articadatascgi>". base64_encode("Fatal NO CONTENT!!")."</articadatascgi>";
        return;
    }
    @unlink("/usr/share/artica-postfix/ressources/logs/web/externals.conf");
    @unlink("/etc/squid3/external_acls.bak");
    @copy("/etc/squid3/external_acls.conf","/etc/squid3/external_acls.bak");
    @file_put_contents("/etc/squid3/external_acls.conf", $datas);

    if(!test_squid_conf()){
        @unlink("/etc/squid3/external_acls.conf");
        @copy("/etc/squid3/external_acls.bak","/etc/squid3/external_acls.conf");
        return;
    }

    @unlink("/etc/squid3/external_acls.bak");
    squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
    $cmd="/etc/init.d/squid reload --script=".basename(__FILE__);
    shell_exec("$cmd >/dev/null 2>&1");

}

function test_squid_conf(){
    $unix=new unix();
    $squidbin=$unix->find_program("squid");
    if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
    $SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();
    writelogs_framework("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",__FUNCTION__,__FILE__,__LINE__);
    exec("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",$results);
    foreach ($results as $index=>$ligne){
        if(strpos($ligne,"| WARNING:")>0){continue;}
        if(preg_match("#ERROR: Failed#", $ligne)){
            writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
            echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
            return false;
        }

        if(preg_match("#Segmentation fault#", $ligne)){
            writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
            echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
            return false;
        }

        if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
            $f[]="Squid `$ligne`, aborting configuration, keep the old one...\n";
            writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
            if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
                $Buggedline=$ri[1];
                $tt=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
                for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
                    $lineNumber=$i+1;
                    if(trim($tt[$i])==null){continue;}
                    $f[]="[line:$lineNumber]: {$tt[$i]}";
                }
            }

            echo "<articadatascgi>". base64_encode(@implode("\n", $f))."</articadatascgi>";
            return false;
        }
    }

    return true;

}

function squid_ssl_conf_save(){
    $unix=new unix();
    $datas=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/ssl.conf");
    writelogs_framework(strlen($datas)/1024 ." Ko",__FUNCTION__,__FILE__,__LINE__);
    if($datas==null){

        echo "<articadatascgi>". base64_encode("Fatal NO CONTENT!!")."</articadatascgi>";
        return;
    }
    @unlink("/usr/share/artica-postfix/ressources/logs/web/ssl.conf");

    @unlink("/etc/squid3/ssl.conf.bak");
    @copy("/etc/squid3/listen_ports.conf","/etc/squid3/ssl.conf.bak");


    @file_put_contents("/etc/squid3/ssl.conf", $datas);
    @chown("/etc/squid3/ssl.conf", "squid");
    $squidbin=$unix->find_program("squid");
    if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
    $SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();
    writelogs_framework("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",__FUNCTION__,__FILE__,__LINE__);
    exec("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",$results);
    foreach ($results as $index=>$ligne){
        if(strpos($ligne,"| WARNING:")>0){continue;}
        if(preg_match("#ERROR: Failed#", $ligne)){
            writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
            @unlink("/etc/squid3/ssl.conf");
            @copy("/etc/squid3/ssl.conf.bak","/etc/squid3/ssl.conf");
            echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
            return ;
        }

        if(preg_match("#Segmentation fault#", $ligne)){
            writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
            @unlink("/etc/squid3/ssl.conf");
            @copy("/etc/squid3/ssl.conf.bak","/etc/squid3/ssl.conf");
            echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
            return ;
        }


        if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
            $f[]="Squid `$ligne`, aborting configuration, keep the old one...\n";
            writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
            if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
                $Buggedline=$ri[1];
                $tt=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
                for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
                    $lineNumber=$i+1;
                    if(trim($tt[$i])==null){continue;}
                    $f[]="[line:$lineNumber]: {$tt[$i]}";
                }
            }
            @unlink("/etc/squid3/ssl.conf");
            @copy("/etc/squid3/ssl.conf.bak","/etc/squid3/ssl.conf");
            echo "<articadatascgi>". base64_encode(@implode("\n", $f))."</articadatascgi>";
            return;
        }

    }
    @unlink("/etc/squid3/listen_ports.conf.bak");
    squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
    $cmd="/etc/init.d/squid reload --script=".basename(__FILE__);
    shell_exec("$cmd >/dev/null 2>&1");


}

function squid_ports_conf_save(){
    $unix=new unix();
    $datas=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/squid_ports.conf");
    writelogs_framework(strlen($datas)/1024 ." Ko",__FUNCTION__,__FILE__,__LINE__);
    if($datas==null){

        echo "<articadatascgi>". base64_encode("Fatal NO CONTENT!!")."</articadatascgi>";
        return;
    }
    @unlink("/usr/share/artica-postfix/ressources/logs/web/squid_ports.conf");

    @unlink("/etc/squid3/listen_ports.conf.bak");
    @copy("/etc/squid3/listen_ports.conf","/etc/squid3/listen_ports.conf.bak");


    @file_put_contents("/etc/squid3/listen_ports.conf", $datas);
    @chown("/etc/squid3/listen_ports.conf", "squid");
    $squidbin=$unix->find_program("squid");
    if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
    $SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();
    writelogs_framework("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",__FUNCTION__,__FILE__,__LINE__);
    exec("$squidbin -f /etc/squid3/squid.conf -k parse 2>&1",$results);
    foreach ($results as $index=>$ligne){
        if(strpos($ligne,"| WARNING:")>0){continue;}
        if(preg_match("#ERROR: Failed#", $ligne)){
            writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
            @unlink("/etc/squid3/listen_ports.conf");
            @copy("/etc/squid3/listen_ports.conf.bak","/etc/squid3/listen_ports.conf");
            echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
            return ;
        }

        if(preg_match("#Segmentation fault#", $ligne)){
            writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
            @unlink("/etc/squid3/listen_ports.conf");
            @copy("/etc/squid3/listen_ports.conf.bak","/etc/squid3/listen_ports.conf");
            echo "<articadatascgi>". base64_encode("Squid `$ligne`, aborting configuration")."</articadatascgi>";
            return ;
        }


        if(preg_match("#(unrecognized|FATAL|Bungled)#", $ligne)){
            $f[]="Squid `$ligne`, aborting configuration, keep the old one...\n";
            writelogs_framework("$ligne ->FALSE",__FUNCTION__,__FILE__,__LINE__);
            if(preg_match("#line ([0-9]+):#", $ligne,$ri)){
                $Buggedline=$ri[1];
                $tt=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
                for($i=$Buggedline-2;$i<$Buggedline+2;$i++){
                    $lineNumber=$i+1;
                    if(trim($tt[$i])==null){continue;}
                    $f[]="[line:$lineNumber]: {$tt[$i]}";
                }
            }
            @unlink("/etc/squid3/listen_ports.conf");
            @copy("/etc/squid3/listen_ports.conf.bak","/etc/squid3/listen_ports.conf");
            echo "<articadatascgi>". base64_encode(@implode("\n", $f))."</articadatascgi>";
            return;
        }

    }
    @unlink("/etc/squid3/listen_ports.conf.bak");
    squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
    $cmd="/etc/init.d/squid reload --script=".basename(__FILE__);
    shell_exec("$cmd >/dev/null 2>&1");

}

function ufdb_ini_status(){
    $unix=new unix();
    $cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --ufdb --nowachdog >/usr/share/artica-postfix/ressources/interface-cache/UFDB_STATUS 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function ntlm_monitor_status(){
    $cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --ntlm-monitor --nowachdog >/usr/share/artica-postfix/ressources/interface-cache/APP_NTLM_MONITOR_STATUS 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}



function create_cache_wizard(){

    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.newcache.center.progress";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.cache.wizard.php > /usr/share/artica-postfix/ressources/logs/web/squid.newcache.center.log 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function cached_kerberos_tickets(){
    $unix=new unix();
    $klist=$unix->find_program("klist");
    exec("$klist -k /etc/squid3/krb5.keytab -t 2>&1",$results);
    $c=0;
    foreach ($results as $num=>$line){
        $line=trim($line);
        $tr=explode(" ",$line);
        if(!is_numeric($tr[0])){continue;}
        $num=trim($tr[0]);
        $date=trim($tr[1])." ".trim($tr[2]);
        $tickets=trim($tr[3]);
        $array[$c]["NUM"]=$num;
        $array[$c]["DATE"]=$date;
        $array[$c]["ticket"]=$tickets;
        $c++;
    }

    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/kerberos-tickets-squid", serialize($array));
    @chmod("/usr/share/artica-postfix/ressources/logs/web/kerberos-tickets-squid",0755);
}

function windows_update_delete(){
    $path=$_GET["windows-update-delete"];
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 /usr/share/artica-postfix/exec.windowsupdate.php --delete \"$path\"";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}



function import_acls_items(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/acls.import.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/acls.import.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $filepath=$_GET["import-acls-items"];
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $groupid=$_GET["groupid"];
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.acls.import.php \"$filepath\" $groupid >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
//if(!is_file($path)){$path=dirname(__FILE__)."/ressources/conf/upload/$path";$GLOBALS["UPLOADED"]=true;}
}
function testTheWhiteLists(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.test.acl.group.php --whitelists");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

