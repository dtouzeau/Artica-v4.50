<?php
include_once(dirname(__FILE__) . '/ressources/class.sockets.inc');
include_once(dirname(__FILE__)."/ressources/class.smtpd.notifications.inc");
$GLOBALS["CLASS_SOCKETS"] = new sockets();
$GLOBALS["GENPROGGNAME"] = "go.shield.server.progress";
$GLOBALS["TITLENAME"] = "Go Shield Server";
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
if(!isset($argv[1])){
    echo "Please use the following commands: --disable --stop --start --restart --update --purge --clean-log --uninstall\n";
    die();

}else{


    if ($argv[1] == "--uninstall") {disable();exit;}
    if ($argv[1] == "--disable") {disable();exit;}
    if ($argv[1] == "--restart") {$GLOBALS["OUTPUT"] = true;restart();exit();}
    if( $argv[1] == "--restart-port"){restart_port();exit;}
    if ($argv[1] == "--update") {exit();}
    if ($argv[1] == "--purge"){purge_cache();exit;}
    if ($argv[1] == "--clean-log"){clean_logs();exit;}
    if ($argv[1] == "--remove") {remove();exit;}
    if ($argv[1] == "--syslog"){;exit;}
    if ($argv[1] == "--remove-all"){remove(true);exit;}
    if ($argv[1] == "--monit"){exit;}
    if ($argv[1] == "--cron"){build_cron();}


    if($argv[1]=="--web-error-pages"){compile_web_error_page_rules();exit;}

    if($argv[1]=="--remote-categories"){remote_categories();exit;}

}



function clean_logs():bool{
    if(!is_file("/var/log/go-shield/server.log")){return false;}
    @unlink("/var/log/go-shield/server.log");
    $unix=new unix();
    $unix->RESTART_SYSLOG(true);
    server_syslog("The Go Shields Server events cleaned...");
    return true;
}
function compile_web_error_page_rules():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $results=$q->QUERY_SQL("SELECT * FROM ufdb_errors WHERE enabled=1 ORDER BY zorder");
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $default_url=GetWebErrorPageUri();

    $WebErrorPagesCompiled=array();
    foreach ($results as $index=>$ligne){
        $category=intval($ligne["category"]);
        $redirwebserv=intval($ligne["redirwebserv"]);

        $webruleid=intval($ligne["webruleid"]);
        $url=$ligne["url"];
        $redirtype=intval($ligne["redirtype"]);
        $parse_url=parse_url($url);
        $protocol=intval($ligne["protocol"]);

        if($EnableNginx==0){
            if($redirwebserv==1){
                $url=$default_url;
                $redirtype=0;
            }

        }

        $WebErrorPagesCompiled[]=array(
            "category"=>$category,
            "url"=>$url,
            "ruleid"=>$webruleid,
            "redirtype"=>$redirtype,
            "protocol"=>$protocol,
            "PARSED"=>$parse_url);
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WebErrorPagesCompiled",serialize($WebErrorPagesCompiled));
    return true;

}


function GetWebErrorPageUri():string{

    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    if($EnableNginx==1){return "";}

    $UfdbUseInternalServiceHTTPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPPort"));
    $UfdbUseInternalServiceHTTPSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPSPort"));

    $UfdbUseInternalServiceHostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHostname"));
    if($UfdbUseInternalServiceHostname==null){$UfdbUseInternalServiceHostname=php_uname("n");}
    if($UfdbUseInternalServiceHTTPPort==0){$UfdbUseInternalServiceHTTPPort=9025;}
    if($UfdbUseInternalServiceHTTPSPort==0){$UfdbUseInternalServiceHTTPSPort=9026;}
    $UfdbUseInternalServiceEnableSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceEnableSSL"));
    if($UfdbUseInternalServiceEnableSSL==1){
        return "https://$UfdbUseInternalServiceHostname:$UfdbUseInternalServiceHTTPSPort";
    }
    return "http://$UfdbUseInternalServiceHostname:$UfdbUseInternalServiceHTTPPort";

}

function purge_cache():bool{
    $Go_Shield_Server_Addr = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Addr");
    $Go_Shield_Server_Port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Port"));
    if ($Go_Shield_Server_Addr==null){$Go_Shield_Server_Addr="127.0.0.1";}
    if($Go_Shield_Server_Port==0){$Go_Shield_Server_Port=3333;}
    $cURLConnection = curl_init();
    curl_setopt($cURLConnection, CURLOPT_NOPROXY,"*");
    curl_setopt($cURLConnection, CURLOPT_URL, "http://$Go_Shield_Server_Addr:$Go_Shield_Server_Port/db/flush");
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    $resp = curl_exec($cURLConnection);
    $resp=trim(strtoupper($resp));
    $errno=curl_errno($cURLConnection);

    if($errno>0){
        $curl_error=curl_error($cURLConnection);
        squid_admin_mysql(1,"{SRN}/Purge {http_api_connection_error} $errno $curl_error [{action}]={restart}");
        server_syslog("[WATCHDOG]: ERROR Network issue $errno $curl_error while purging [RESTART]");
        restart();
        return false;
    }
    $CURLINFO_HTTP_CODE=intval(curl_getinfo($cURLConnection,CURLINFO_HTTP_CODE));
    curl_close($cURLConnection);

    if($resp=="OK"){
        server_syslog("Go Shield Server cache purged");
        return true;
    }

    server_syslog("Go Shield Server Failed to purge cache $resp HTTP $errno $CURLINFO_HTTP_CODE");

    if($CURLINFO_HTTP_CODE<>200) {
        squid_admin_mysql(1, "{SRN} {http_api_connection_error} HTTP $errno $CURLINFO_HTTP_CODE [{action}]={restart}");
        server_syslog("[WATCHDOG]: ERROR Protocol issue Error: $CURLINFO_HTTP_CODE [RESTART]");
        restart();
        return true;
    }
    return true;
}


function server_syslog($text):bool{
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("go-shield-server", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}




function remove($client=false):bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoShieldServerHide", 1);
    disable();
    return true;
}

function install_progress($prc,$text):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$text,"go.shield.server.progress");
}

function disable():bool{
    $unix = new unix();
    $monit_file = "/etc/monit/conf.d/go-shield.server.monitrc";
    $GLOBALS["CLASS_SOCKETS"]->build_progress(15, "{uninstalling}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Go_Shield_Server_Enable", 0);
    shell_exec("/usr/sbin/artica-phpfpm-service -stop-go-shield");

    install_progress(50, "{uninstalling}...");
    squid_admin_mysql(1, "{Reloading_Service_after} {uninstalling} {$GLOBALS["TITLENAME"]} ", null, __FILE__, __LINE__);

    $unix->remove_service("/etc/init.d/go-shield-server");
    install_progress(51, "{uninstalling}...");
    if (is_file($monit_file)) {
        install_progress(52, "{uninstalling} $monit_file...");
        @unlink($monit_file);
        $unix->MONIT_RELOAD();
    }

    $fcron[]="go-shield-server-purge";
    $fcron[]="go-shield-server-remote-categories";
    $fcron[]="go-shield-server-local";
    $fcron[]="go-shield-server-cnxwatch";

    $RCRON=false;
    install_progress(60, "{uninstalling}...");
    $i=60;
    foreach ($fcron as $cronfile) {
        $i++;
        install_progress($i, "{remove} $cronfile...");
        $unix->Popuplate_cron_delete($cronfile);

    }


    $sh_files[]="/sbin/go-shield-restart.sh";
    $sh_files[]="/sbin/go-shield-start.sh";
    $sh_files[]="/sbin/go-shield-stop.sh";

    install_progress($i++, "{uninstalling}...");
    foreach ($sh_files as $path){
        install_progress($i, "{remove} $path...");
        if(is_file($path)){@unlink($path);}
    }


    install_progress(80, "{uninstalling}...");

    $monit_file = "/etc/monit/conf.d/go-shield.server.monitrc";
    if (is_file($monit_file)) {
        @unlink($monit_file);
        $unix->MONIT_RELOAD();
    }

    install_progress(90, "{uninstalling}...");
    $unix->framework_exec("/usr/sbin/artica-phpfpm-service -restart-artica-status");
    install_progress(100, "{uninstalling} {done}");
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

function restart_port(){

    squid_admin_mysql(1,"{restarting} {APP_GO_SHIELD_SERVER} Issue on listen port",null,__FILE__,__LINE__);
    _out_monit("'APP_GO_SHIELD_SERVER' Stopping service (issue onp port)");
    shell_exec("/usr/sbin/artica-phpfpm-service -stop-go-shield");
    sleep(1);
    _out_monit("'APP_GO_SHIELD_SERVER' Starting service (issue onp port)");
    shell_exec("/usr/sbin/artica-phpfpm-service -start-go-shield");
    exit(0);

}

function restart():bool{
    $GLOBALS["CLASS_SOCKETS"]->build_progress(25, "{stopping_service}");
    $unix = new unix();
    $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
    $pid = $unix->get_pid_from_file($pidfile);
    if ($unix->process_exists($pid, basename(__FILE__))) {
        $time = $unix->PROCCESS_TIME_MIN($pid);
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";
        }
        $GLOBALS["CLASS_SOCKETS"]->build_progress(110, "{stopping_service} {failed}");
        return false;
    }


    if($GLOBALS["MONIT"]) {
        $pid = PID_NUM();
        if ($unix->process_exists($pid)) {
            _out_monit("'APP_GO_SHIELD_SERVER' Service is already running $pid - Aborting");
            @file_put_contents("/var/run/go-shield-server.pid",$pid);
            exit(0);
        }
        squid_admin_mysql(0,"Ask to restart filtering service by the watchdog",null,__FILE__,__LINE__);
    }

    watch_connection_cron();
    build_remote_categories_service();
    @file_put_contents($pidfile, getmypid());
    shell_exec("/usr/sbin/artica-phpfpm-service -stop-go-shield");
    $GLOBALS["CLASS_SOCKETS"]->build_progress(50, "{starting_service}");
    sleep(3);
    shell_exec("/usr/sbin/artica-phpfpm-service -start-go-shield");
    $GLOBALS["CLASS_SOCKETS"]->build_progress(100, "{starting_service} {success}");
    return true;
}

function PID_NUM():int{
    $unix = new unix();
    $pid = $unix->get_pid_from_file("/var/run/go-shield-server.pid");
    if ($unix->process_exists($pid)) {
        return $pid;
    }
    $Masterbin = $unix->find_program("go-shield-server");
    return $unix->PIDOF($Masterbin);

}

function PID_NUM_FS_WATCHER():int{
    $unix = new unix();
    $pid = $unix->get_pid_from_file("/var/run/go-shield-server-fs-watcher.pid");
    if ($unix->process_exists($pid)) {
        return $pid;
    }
    $Masterbin = $unix->find_program("go-shield-server-fs-watcher");
    return $unix->PIDOF($Masterbin);

}



function build_remote_categories_service():bool{
    $unix=new unix();
    $EnableRemoteCategoriesServices = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteCategoriesServices"));
    $unix->Popuplate_cron_delete("go-shield-server-remote-categories");
    return true;
}

function remote_categories():bool{
    $unix=new unix();
    $unix->Popuplate_cron_delete("go-shield-server-remote-categories");
    include_once(dirname(__FILE__)."/ressources/externals/Net/DNS2.inc");
    $EnableRemoteCategoriesServices = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteCategoriesServices"));
    if($EnableRemoteCategoriesServices==0){
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UFDBCAT_DNS_ERROR","");
    $RemoteCategoriesServicesRemote = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesRemote"));
    $RemoteCategoriesServicesAddress = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesAddress"));
    $RemoteCategoriesServicesPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesPort"));
    $RemoteCategoriesServicesDomain = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesDomain"));
    if ($RemoteCategoriesServicesDomain == null) {$RemoteCategoriesServicesDomain = "categories.tld";}

    if($RemoteCategoriesServicesRemote==0){
        $text_error=" DNS *.$RemoteCategoriesServicesDomain";
        $namesservers="/etc/resolv.conf";
        $dns_port=53;
        $r = new Net_DNS2_Resolver(array('nameservers' => "/etc/resolv.conf", "timeout" => 1));

    }else{
        $text_error=" $RemoteCategoriesServicesAddress:$RemoteCategoriesServicesPort *.$RemoteCategoriesServicesDomain";
        $namesservers=array($RemoteCategoriesServicesAddress);
        $dns_port=$RemoteCategoriesServicesPort;

    }

    $r = new Net_DNS2_Resolver(array('nameservers' => $namesservers, "timeout" => 1));
    $r->dns_port=$dns_port;

    try {
        $result = $r->query("articatech-non-existent-domain.zva.$RemoteCategoriesServicesDomain", 'TXT');

    } catch(Net_DNS2_Exception $e) {
        $message=$e->getMessage();
        $unix->ToSyslog("[ERROR]: $text_error $message",false,"categories-update");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UFDBCAT_DNS_ERROR","$text_error<br>$message");
        return false;
    }

    if (!property_exists($result, "answer")) {
        $unix->ToSyslog("[ERROR]: $text_error No answer ?",false,"categories-update");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UFDBCAT_DNS_ERROR","$text_error<br>{no_answer}");
        return false;
    }

    $AVAILBLE_CATEGORIES=array();
    foreach ($result->answer as $index=>$rr) {
        $data = $rr->text[0];
        if(!preg_match("#^[0-9]+\s+[0-9]+::[0-9]+:[0-9]+:[0-9]+:([0-9]+):(.+)#",$data,$re)){continue;}
        $unix->ToSyslog("[SUCCESS]: Found categories compiled on ".date("Y-m-d H:i:s",$re[1])." {$re[2]}",false,"categories-update");
        $exploded=explode("|",$re[2]);
        foreach ($exploded as $categoryid){
            $catid=intval($categoryid);
            if($catid<250){continue;}
            $AVAILBLE_CATEGORIES[$catid]="Default";
        }

    }

    foreach ($AVAILBLE_CATEGORIES as $category_id=>$none){

        try {
            $result = $r->query("articatech-non-existent-$category_id.zva.$RemoteCategoriesServicesDomain", 'TXT');

        } catch(Net_DNS2_Exception $e) {
            $message=$e->getMessage();
            $unix->ToSyslog("[ERROR]: $text_error [$category_id] -- $message",false,"categories-update");
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UFDBCAT_DNS_ERROR","$text_error<br>$message");
            return false;
        }
        if (!property_exists($result, "answer")) {
            $unix->ToSyslog("[ERROR]: $text_error [$category_id] -- No answer ?",false,"categories-update");
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UFDBCAT_DNS_ERROR","Category $category_id<br>{no_answer}");
            return false;
        }

        foreach ($result->answer as $index=>$rr) {
            $data = $rr->text[0];
            if(preg_match("#^[0-9]+\s+[0-9]+:(.+)#",$data,$re)){
                $unix->ToSyslog("[SUCCESS]: $text_error Category $category_id = $re[1]",false,"categories-update");
                $AVAILBLE_CATEGORIES[$category_id]=$re[1];
                continue;
            }
            echo "Category No matches $category_id <$data>\n";

        }

    }
    include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
    $q=new postgres_sql();

    $q->QUERY_SQL("DELETE FROM personal_categories WHERE meta=1");
    $q->QUERY_SQL("ALTER TABLE personal_categories ALTER COLUMN categorykey TYPE varchar(50);");

    $fields[]="category_id";
    $fields[]="categorykey";
    $fields[]="categoryname";
    $fields[]="categorytable";
    $fields[]="enabled";
    $fields[]="remotecatz";
    $fields[]="meta";
    $fields[]="category_icon";
    $prefix="INSERT INTO personal_categories (" .@implode(",",$fields).") VALUES ";

    foreach ($AVAILBLE_CATEGORIES as $category_id=>$categoryname ){
        $vals=array();
        $vals[]=$category_id;
        $vals[]="'$categoryname'";
        $vals[]="'$categoryname'";
        $vals[]="'category_$category_id'";
        $vals[]="1";
        $vals[]="0";
        $vals[]="1";
        $vals[]="'img/20-import.png'";

        $q->QUERY_SQL($prefix."(".@implode(",",$vals).")");
        if(!$q->ok){
            $unix->ToSyslog("[ERROR]: category $categoryname $q->mysql_error",false,"categories-update");
        }

    }


    return true;
}


function build_cron():bool{
    $unix=new unix();
    $RESTART=false;
    $fname=basename(__FILE__);

    $files[]="go-shield-server-clean-log";
    $files[]="go-shield-server-purge";
    $files[]="go-shield-server-local";
    $MD5START=array();$MD5END=array();
    foreach ($files as $cronf){
        if(!is_file("/etc/cron.d/$cronf")){continue;}
        $MD5START[$cronf]=md5_file("/etc/cron.d/$cronf");
    }

    if(!is_file("/etc/cron.d/go-shield-server-local")) {
        $unix->Popuplate_cron_make("go-shield-server-local", "* */2 * * *", "$fname --update");
    }

    $GoShieldServerPurge=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Purge"));
    $GoShieldServerUseLocalCats=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsUseLocalCats"));
    if($GoShieldServerUseLocalCats==1) {
        $python = $unix->find_program("python");
        shell_exec("$python /usr/share/artica-postfix/bin/compile-category.py --update");
    }

    $GoShieldServerPurgeAct[0]="30 0 * * *";
    $GoShieldServerPurgeAct[1]="30 0 * * 0";
    $GoShieldServerPurgeAct[2]="* */12 * * *";
    $GoShieldServerPurgeAct[3]="* */6 * * *";
    $GoShieldServerPurgeAct[4]="* */3 * * *";

    $unix->Popuplate_cron_make("go-shield-server-purge",$GoShieldServerPurgeAct[$GoShieldServerPurge],"$fname --purge");
    $unix->Popuplate_cron_make("go-shield-server-clean-log", "5 0 * * *", "$fname --clean-log");


    foreach ($files as $cronf){
        if(!is_file("/etc/cron.d/$cronf")){continue;}
        $cronf_md5=md5_file("/etc/cron.d/$cronf");
        if(!isset($MD5START[$cronf])){$MD5START[$cronf]=null;}
        if($cronf_md5<>$MD5START[$cronf]){
            $RESTART=true;
        }
    }

    if($RESTART){
        server_syslog("Restarting cron service for Reputation service scheduled tasks");
        UNIX_RESTART_CRON();
    }
 return true;
}