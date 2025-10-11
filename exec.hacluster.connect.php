<?php
$GLOBALS["VERBOSE"]=true;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["TITLENAME"]="Load-Balancer Daemon";
$GLOBALS["OUTPUT"]=false;
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
$GLOBALS["NOCONF"]=false;
$GLOBALS["WIZARD"]=false;
$GLOBALS["BY_SCHEDULE"]=false;
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");


if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--noconf#",implode(" ",$argv))){$GLOBALS["NOCONF"]=true;}
if(preg_match("#--wizard#",implode(" ",$argv))){$GLOBALS["WIZARD"]=true;}
if(preg_match("#--byschedule#",implode(" ",$argv))){$GLOBALS["BY_SCHEDULE"]=true;}

if($argv[1]=="--disconnect"){client_disconnect();}
if($argv[1]=="--connect"){connect_clusters();}
if($argv[1]=="--kerberos-renew"){renew_clusters();exit;}
if($argv[1]=="--setup"){client_setup();}
if($argv[1]=="--ping-syslog"){ping_syslog();}
if($argv[1]=="--connect-id"){connect_single($argv[2]);exit;}
if($argv[1]=="--client-logging"){exit;}
if($argv[1]=="--hacluster-renew"){client_renew();exit;}
if($argv[1]=="--push"){client_push_infos();exit;}

function build_progress($text,$pourc){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/hacluster.connect.progress", serialize($array));
    @chmod("/usr/share/artica-postfix/ressources/logs/web/hacluster.connect.progress",0755);


}

function build_disconnect_progress($text,$pourc){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/hacluster.disconnect.progress", serialize($array));
    @chmod("/usr/share/artica-postfix/ressources/logs/web/hacluster.disconnect.progress",0755);

}

function ping_syslog(){
    hacluster_syslog("Ping syslog");
}

function client_renew(){
    $unix=new unix();
    if(!client_kerberos()){
        return false;
    }
    system("/usr/sbin/artica-phpfpm-service -reload-proxy");
    return true;

}

function client_disconnect(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    build_disconnect_progress("{disconnecting}...",20);
    client_report(500,null);


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterTproxy",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterTproxyEnabled",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UseNativeKerberosAuth",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKerbAuth",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kerberosActiveDirectoryLBEnable",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableActiveDirectoryFeature",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UseNativeKerberosAuth",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerberosUsername",null);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kerberosRealm",null);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerberosSPN",null);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerberosPassword",null);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("kerberosActiveDirectoryHost",null);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterID",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterIP",null);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterClient",0);


    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));

    if($PowerDNSEnableClusterSlave==1) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/cluster/client/uninstall");
    }

    if($PowerDNSEnableClusterMaster==1) {
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?uninstall-cluster-master=yes");
    }
    hacluster_syslog("{disconnecting} From the HaCluster Pool");
    build_disconnect_progress("{disconnecting}...",30);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q->QUERY_SQL("DELETE FROM proxy_ports WHERE ProxyProtocol=1");
    system("$php /usr/share/artica-postfix/exec.squid.global.access.php --ports");


    build_disconnect_progress("{disconnecting}...",50);
    system("$php /usr/share/artica-postfix/exec.squid.global.access.php --logging");
    build_disconnect_progress("{disconnecting}...",80);
    system("/usr/sbin/artica-phpfpm-service -haclient-uninstall-keytab");


    build_disconnect_progress("{disconnecting}...",90);
    $sock=new sockets();
    $sock->REST_API("/reload");
    build_disconnect_progress("{disconnecting}...",91);
    build_disconnect_progress("{disconnecting}...",92);
    shell_exec("/usr/sbin/artica-phpfpm-service -proxy-snmpd");
    build_disconnect_progress("{disconnecting} {success}",100);


}

function connect_single($ID){
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");


    $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));

    $ligne=$q->mysqli_fetch_array("SELECT * FROM hacluster_backends WHERE enabled=1 AND isMaster=1");
    $MasterID=intval($ligne["ID"]);
    if($MasterID>0){
        $MASTER=$ligne["listen_ip"];
        $MASTER_PORT=$ligne["artica_port"];
    }
    $ligne=$q->mysqli_fetch_array("SELECT * FROM hacluster_backends WHERE ID=$ID");
    $artica_port=intval($ligne["artica_port"]);
    $listen_ip=$ligne["listen_ip"];
    $listen_port=$ligne["listen_port"];
    $backendname=$ligne["backendname"];
    $isMaster=$ligne["isMaster"];
    $array=array();
    $array["HaCluster"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD");
    $array["hacluster-connect"]=$listen_port;
    $array["hacluster-tproxy"]=47887;
    $array["HaClusterTransParentMode"]=$HaClusterTransParentMode;
    $array["hacluster-id"]=$ID;
    if($isMaster==1){
        $array["hacluster-ismaster"]=1;
    }else{
        if($MASTER<>null) {
            $array["hacluster-master"] = $MASTER;
            $array["hacluster-master-port"] = $MASTER_PORT;
        }
    }
    if($artica_port==0){$artica_port=9000;}
    $URI="https://$listen_ip:$artica_port";
    build_progress("$backendname says hello $backendname ?",50);
    if(!POST_INFOS($URI,$array,$ID)){
        $q->QUERY_SQL("UPDATE hacluster_backends SET status=1, errortext='{$GLOBALS["ERROR_INFO"]}' WHERE ID=$ID");
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
        build_progress("$backendname says {failed}",110);
        return;
    }
    build_progress("{success}",100);

}

function cluster_issue($ID,$text){
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $q->QUERY_SQL("UPDATE hacluster_backends SET status=1, errortext='$text' WHERE ID=$ID");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
}

function renew_clusters(){
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        build_progress("{license_error}",110);
        die();
    }

    $clusters=list_clusters();
    foreach ($clusters as $URI=>$ARR){
        $array=array();
        $array["hacluster-renew-kerberos"]=true;
        $ID=$ARR["ID"];
        $NAME=$ARR["NAME"];
        hacluster_syslog_master("Ask $NAME to renew kerberos certificate");
        if(!POST_INFOS($URI,$array,$ID)){
            cluster_issue($ID,$GLOBALS["ERROR_INFO"]);
        }

    }
}

function list_clusters(){
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM hacluster_backends WHERE enabled=1");
    $LIST=array();
    foreach ($results as $index=>$ligne) {
        $ID=$ligne["ID"];
        $artica_port = $ligne["artica_port"];
        $listen_ip = $ligne["listen_ip"];
        $backendname=$ligne["backendname"];
        $URI = "https://$listen_ip:$artica_port";
        $LIST[$URI] = array("ID"=>$ID,"NAME"=>$backendname);
    }
    return $LIST;
}

function connect_clusters(){

    $unix=new unix();
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        build_progress("{license_error}",110);
        die();
    }
    $MASTER=null;
    $MASTER_PORT=0;
    $php=$unix->LOCATE_PHP5_BIN();
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");

    if(!is_file("/etc/init.d/ntp")){
        system("/usr/sbin/artica-phpfpm-service -install-ntp");

    }
    if(!is_file("/etc/init.d/dnsdist")){
        if(!is_file("/etc/init.d/unbound")){
            build_progress("{installing} {APP_UNBOUND}",10);
            system("/usr/sbin/artica-phpfpm-service -install-unbound");
        }
    }
    build_progress("{reconfigure} {APP_UNBOUND}",10);
    system("$php /usr/share/artica-postfix/exec.unbound.php --reload");


    $ligne=$q->mysqli_fetch_array("SELECT * FROM hacluster_backends WHERE enabled=1 AND isMaster=1");
    $ID=intval($ligne["ID"]);
    if($ID>0){
        $MASTER=$ligne["listen_ip"];
        $MASTER_PORT=$ligne["artica_port"];
    }

    $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));
    $results=$q->QUERY_SQL("SELECT * FROM hacluster_backends WHERE enabled=1");

    $prc=15;
    $FINAL=true;
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $artica_port=intval($ligne["artica_port"]);
        $listen_ip=$ligne["listen_ip"];
        $listen_port=$ligne["listen_port"];
        $backendname=$ligne["backendname"];
        $isMaster=$ligne["isMaster"];
        $array=array();

        $array["hacluster-connect"]=$listen_port;
        $array["hacluster-tproxy"]=47887;
        $array["HaClusterTransParentMode"]=$HaClusterTransParentMode;
        $array["hacluster-id"]=$ID;
        if($isMaster==1){
            $array["hacluster-ismaster"]=1;
        }else{
            if($MASTER<>null) {
                $array["hacluster-master"] = $MASTER;
                $array["hacluster-master-port"] = $MASTER_PORT;
            }
        }
        if($artica_port==0){
            $artica_port=9000;
        }
        $URI="https://$listen_ip:$artica_port";
        build_progress("$backendname says hello $backendname ?",$prc++);
        if(!POST_INFOS($URI,$array,$ID)){
            $FINAL=false;
            $q->QUERY_SQL("UPDATE hacluster_backends SET status=1, errortext='{$GLOBALS["ERROR_INFO"]}' WHERE ID=$ID");
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
        }
    }





    if(!$FINAL){
        build_progress("{errors}",110);
        return;
    }

    build_progress("{success}",100);
}


function hacluster_syslog($text){
    echo $text."\n";
    if(!function_exists("syslog")){return;}
    $LOG_SEV=LOG_INFO;
    openlog("hacluster-client", LOG_PID , LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();

}
function hacluster_syslog_master($text){
    echo $text."\n";
    if(!function_exists("syslog")){return;}
    $LOG_SEV=LOG_INFO;
    openlog("hacluster", LOG_PID , LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
}

function client_download_file($filename,$targetpath){


    $unix=new unix();
    $filetemp=$unix->FILE_TEMP();
    $HaClusterIP=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterIP");
    $uri="$HaClusterIP/pdns-cluster/$filename";
    //krb5.md5

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate", "Cache-Control: no-cache,must revalidate",'Expect:'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    hacluster_syslog("[$filename]: Dowloading $uri",null,__LINE__);
    curl_setopt($ch, CURLOPT_URL, "$uri");
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    curl_setopt($ch, CURLOPT_FILE, $fp = fopen($filetemp,'w'));

    curl_exec($ch);

    $Error=curl_errno($ch);
    if($Error>0) {
        $GLOBALS["ERROR"]="[$filename]: HTTP Error $Error";
        hacluster_syslog("[$filename]: HTTP Error $Error",null,__LINE__);
        curl_close($ch);
        fclose($fp);
        return false;
    }


    fclose($fp);
    curl_close($ch);

    if(is_file($targetpath)){@unlink($targetpath);}
    hacluster_syslog("[$filename]: Copy $filetemp -> $targetpath",null,__LINE__);
    if(!@copy($filetemp,$targetpath)){
        hacluster_syslog("[$filename]: Copy Error $targetpath",null,__LINE__);
        @unlink($filetemp);
        return false;
    }
    @unlink($filetemp);
    return true;


}

function POST_INFOS($uri,$posted=array(),$ID=0){
    $unix=new unix();
    $MyUUID=$unix->GetMyHostId();
    $ArticaHttpsPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));
    $HaClusterUseAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterUseAddr");
    $RsyslogPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogPort"));
    $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));

    if(intval($HaClusterGBConfig["HaClusterDecryptSSL"])==1){
        $Certif=$HaClusterGBConfig["HaClusterCertif"];
        $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM sslcertificates WHERE CommonName='$Certif'");
        $DataCertif=base64_encode(serialize($ligne));
        $HaClusterGBConfig["CertifData"]=$DataCertif;
    }


    if($RsyslogPort==0){$RsyslogPort=514;}
    if($ArticaHttpsPort==0){$ArticaHttpsPort=9000;}
    $post_data["HaCluster"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD");


    $post_data["HaClusterUseAddr"]=$HaClusterUseAddr;
    $post_data["HaClusterPort"]=$ArticaHttpsPort;
    $post_data["HaClusterUUID"]=$MyUUID;
    $post_data["HaClusterSyslogPort"]=$RsyslogPort;
    $post_data["KerberosSynCAD"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerberosSynCAD"));
    $post_data["HaClusterGBConfig"]=base64_encode(serialize($HaClusterGBConfig));




    foreach ($posted as $key=>$val){
        $post_data[$key]=strval($val);
    }

    hacluster_syslog_master("Notify node: $uri");
    $uri_final="$uri/nodes.listener.php";
    if($GLOBALS["VERBOSE"]){echo "$uri_final\n";}

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate", "Cache-Control: no-cache,must revalidate",'Expect:'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_URL, "$uri_final");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    $data=curl_exec($ch);
    $CURLINFO_HTTP_CODE=curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $curl_errno=curl_errno($ch);


    if($curl_errno==28){
        hacluster_syslog("$uri Error 28 Connection timed out...");
        $GLOBALS["ERROR_INFO"]="{connection_timed_out}";
        return false;
    }




    if(preg_match("#<ERROR>(.+?)</ERROR>#is",$data,$re)){
        hacluster_syslog_master("Error $uri says {$re[1]}");
        $GLOBALS["ERROR_INFO"]=$re[1];
        return false;
    }

    if(preg_match("#<STATUS>([0-9]+)</STATUS>#is",$data,$re)){
        hacluster_syslog_master("$uri Entering in setup mode status={$re[1]}, waiting feedbacks");
        return true;
    }

    if(preg_match("#RETURNED_TRUE#is",$data)){
        hacluster_syslog_master("$uri Success accepted the order");
        return true;
    }


    $header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);

    if($GLOBALS["VERBOSE"]){echo "CURLINFO_HTTP_CODE = $CURLINFO_HTTP_CODE $curl_errno=$curl_errno\n";}
    return true;
}

function client_report($status,$error=null){

    $unix=new unix();
    $HaClusterID=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterID");
    $HaClusterIP=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterIP");

    hacluster_syslog("Report status $status ($error)");
    $post_data["HaClusterNode"]=$HaClusterID;
    $post_data["HaClusterStatus"]=$status;
    $post_data["HaClusterError"]=$error;
    $post_data["HaClusterArticaVersion"]=GetArticaFullVersion();
    $post_data["hostname"]=$unix->hostname_g();
    $uri_final="$HaClusterIP/nodes.listener.php";
    if($GLOBALS["VERBOSE"]){echo "$uri_final\n";}
    client_http_post($post_data);
}
function client_http_post($post_data){
    $HaClusterIP=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterIP");
    $uri_final="$HaClusterIP/nodes.listener.php";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate", "Cache-Control: no-cache,must revalidate",'Expect:'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_URL, "$uri_final");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    $data=curl_exec($ch);
    $CURLINFO_HTTP_CODE=curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $curl_errno=curl_errno($ch);

    if($curl_errno>0){
        hacluster_syslog("$uri_final Error $curl_errno ...");
        return false;
    }
    return true;

}





function client_setup_transparent_mode(){

    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));
    $ProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTproxy"));
    if(intval($ProxyPort)==0){$HaClusterTransParentMode=0;}
    hacluster_syslog("[SETUP]: Transparent Proxy port $ProxyPort [".__LINE__."]");


    $ISDETECTED_PORT=client_setup_transparent_mode_is_installed();


    if( $HaClusterTransParentMode == 1){
       if($ISDETECTED_PORT==$ProxyPort){
            hacluster_syslog("[SETUP]: [TRANSPARENT] Port $ProxyPort {already_installed}");
            return true;
        }

        hacluster_syslog("[SETUP]: [TRANSPARENT] Reconfiguring proxy server");
        system("$php /usr/share/artica-postfix/exec.squid.global.access.php --ports --firehol");
        $ISDETECTED_PORT=client_setup_transparent_mode_is_installed();
        if($ISDETECTED_PORT==$ProxyPort){
            hacluster_syslog("[SETUP]: [TRANSPARENT] Port $ProxyPort {success}");
            return true;
        }
        return false;

    }

    if($ISDETECTED_PORT==0){
        hacluster_syslog("[SETUP]: [TRANSPARENT] {uninstalled}");
        return true;
    }

    if($ISDETECTED_PORT>0){
        hacluster_syslog("[SETUP]: [TRANSPARENT] {uninstall}");
        system("$php /usr/share/artica-postfix/exec.squid.global.access.php --ports --firehol");
        $ISDETECTED_PORT=client_setup_transparent_mode_is_installed();
        if($ISDETECTED_PORT>0){
            hacluster_syslog("[SETUP]: [TRANSPARENT] {uninstall} {failed}");
            return false;
        }
        return true;
    }


    return true;
}

function client_setup_transparent_mode_is_installed(){
    $f=explode("\n",@file_get_contents("/etc/squid3/listen_ports.conf"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^\##",$line)){continue;}
        if(preg_match("#http_port [0-9\.]+:([0-9]+)\s+.*?name=HaClusterTransparent#i",$line,$re)){
            return intval($re[1]);
        }

    }
    return 0;
}



function client_kerberos(){
    shell_exec("usr/sbin/artica-phpfpm-service /usr/share/artica-postfix/bin/articarest -haclient-keytab");
    if(is_file("/etc/squid3/krb5.keytab")){
        return true;
    }
    return false;
}

function client_schedule_post_info(){
    $cronfile="/etc/cron.d/hacluster-client";
    $HaClusterClient=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient");
    if($HaClusterClient==1){
        if(!is_file($cronfile)){
            $unix=new unix();
            $unix->Popuplate_cron_make("hacluster-client","*/2 * * * *",basename(__FILE__)." --push");
            shell_exec("/etc/init.d/cron reload");
            hacluster_syslog("Success Installing POST infos schedule task");
        }
        return true;
    }
    if(is_file($cronfile)){
        @unlink($cronfile);
        shell_exec("/etc/init.d/cron reload");
    }
    return true;

}

function client_push_infos(){
    return;
    $unix=new unix();
    $TimePID="/etc/artica-postfix/pids/exec.hacluster.connect.php.push.pid";
    $imgdir="/usr/share/artica-postfix/img/squid";
    $f_last_counter_cache="/etc/artica-postfix/squid.last.counters.txt";
    $pid=$unix->get_pid_from_file($TimePID);
    if($unix->process_exists($pid)){return false;}

    @file_put_contents($TimePID,getmypid());
    $array_load=sys_getloadavg();
    $internal_load=$array_load[0];
    $ARRAY["LOAD"]=$internal_load;
    client_schedule_post_info();
    $ARRAY["REQUESTS"]=base64_encode(@file_get_contents($f_last_counter_cache));
    if(is_dir($imgdir)){
        $handle = opendir($imgdir);
        while (false !== ($filename = readdir($handle))) {
            if($filename=="."){continue;}
            if($filename==".."){continue;}
            if(!preg_match("#\.png$#",$filename)){continue;}
            $targetFile="$imgdir/$filename";
            $imgcontent=base64_encode(@file_get_contents($targetFile));
            $ARRAY["RRD"][$filename]=$imgcontent;
        }
    }

    $final_post=base64_encode(serialize($ARRAY));
    $HaClusterID=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterID");
    $realsquidversion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRealVersion");
    $post_data["HaClusterArticaVersion"]=GetArticaFullVersion();
    $post_data["HaClusterNode"]=$HaClusterID;
    $post_data["HaClusterMetrics"]=$final_post;
    $post_data["hostname"]=$unix->hostname_g();
    $post_data["realname"]=php_uname('n');
    $post_data["HaClusterProxyVersion"]=$realsquidversion;

    $post_data["HaClusterClientReport"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClientReport"));
    if(is_file("/etc/artica-postfix/hacluster-client.status")) {
        $post_data["HaClusterClientMetrics"] = @file_get_contents("/etc/artica-postfix/hacluster-client.status");
    }
    return client_http_post($post_data);
}
function GetArticaFullVersion(){
    $unix=new unix();
    $VERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
    if (is_file("/usr/share/artica-postfix/SP/$VERSION")) {
        $VERSION="$VERSION Service Pack ".trim(@file_get_contents("/usr/share/artica-postfix/SP/$VERSION"));
    }

    $hostfix=intval($unix->GetHotFixVersion());
    if($hostfix>0){
        $VERSION="$VERSION Hotfix $hostfix";
    }
    return $VERSION;
}

function client_setup(){
    $BasePath="/home/artica/PowerDNS/Cluster/storage";
    if(!is_dir($BasePath)){@mkdir($BasePath,0755,true);}
    @chmod($BasePath,0755);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterClientReport",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterClient",1);
    system("/usr/sbin/artica-phpfpm-service -install-haclient");
    return true;
}


