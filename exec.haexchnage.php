<?php
//https://sysadminblogger.wordpress.com/tag/exchange-2016-haproxy/
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["TITLENAME"]="Load-Balancer Daemon";
$GLOBALS["OUTPUT"]=false;
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
$GLOBALS["NOCONF"]=false;
$GLOBALS["WIZARD"]=false;
$GLOBALS["BY_SCHEDULE"]=false;
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.dnsmasq.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.haproxy.inc");
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/ressources/class.munin.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--noconf#",implode(" ",$argv))){$GLOBALS["NOCONF"]=true;}
if(preg_match("#--wizard#",implode(" ",$argv))){$GLOBALS["WIZARD"]=true;}
if(preg_match("#--byschedule#",implode(" ",$argv))){$GLOBALS["BY_SCHEDULE"]=true;}


if($argv[1]=="--build"){build();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--iptables-remove"){iptables_delete_all();exit();}
if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--uninstall"){uninstall();exit();}
if($argv[1]=="--monit"){monit_config();exit();}
if($argv[1]=="--rotate"){rotate();exit();}


function restart($aspid=false){
    $unix=new unix();
    $sock=new sockets();
    $Masterbin="/usr/sbin/ha-exchange";

    if(!is_file($Masterbin)){
        build_progress_restart(110,"{not_installed}");
        if($GLOBALS["OUTPUT"]){OutTxt("/usr/sbin/ha-exchange not installed");}
        return;
    }

    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            build_progress_restart(110,"{already_running}");
            if($GLOBALS["OUTPUT"]){OutTxt("Already Artica task running PID $pid since {$time}mn");}
            return;
        }
        @file_put_contents($pidfile, getmypid());
    }

    build_progress_restart(50,"{stopping_service}");
    stop(true);
    build_progress_restart(80,"{building}");
    build();
    build_progress_restart(90,"{starting_service}");
    if(!start(true)){
        build_progress_restart(110,"{starting_service} {failed}");
        return false;
    }
    build_progress_restart(100,"{success}");
    return true;
}
function build_progress_restart($pourc,$text){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress", serialize($array));
    @chmod("/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress",0755);


}
function build_progress_stop($pourc,$text){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/haproxy-stop.progress", serialize($array));
    @chmod("/usr/share/artica-postfix/ressources/logs/web/haproxy-stop.progress.txt",0755);


}

function build_errorpage($HTTPCODE,$title,$content){

    $f[]="HTTP/1.1 $HTTPCODE $title";
    $f[]="Cache-Control: no-cache";
    $f[]="Connection: close";
    $f[]="Content-Type: text/html";
    $f[]="Retry-After: 60";
    $f[]="";

    $f[]="<html>";
    $f[]="<head>";
    $f[]="    <meta charset=\"utf-8\">";
    $f[]="    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">";
    $f[]="    <title>$HTTPCODE $title</title>";
    $f[]="   <style type=\"text/css\" >";
    $f[]="::after, ::before { -webkit-box-sizing: border-box;-moz-box-sizing: border-box; box-sizing: border-box; }";
    $f[]=".gray-bg, .bg-muted { background-color: #f3f3f4;}";
    $f[]="body {color: #676a6c;font-family: 'lato','Trebuchet MS', 'Helvetica', sans-serif;font-size: 13px;background-color: #2f4050;overflow-x: hidden; margin: 0; }";
    $f[]="html, body {height: 100%;}";
    $f[]="* { -webkit-box-sizing: border-box;-moz-box-sizing: border-box; box-sizing: border-box; }";
    $f[]="html { font-family: sans-serif; -webkit-text-size-adjust: 100%; }";
    $f[]=".middle-box { max-width: 400px; z-index: 100; margin: 0 auto;  padding-top: 40px; }";
    $f[]=".fadeInDown { -webkit-animation-name: fadeInDown; animation-name: fadeInDown; }";
    $f[]=".animated { -webkit-animation-duration: 1s; animation-duration: 1s; -webkit-animation-fill-mode: both; animation-fill-mode: both;}";
    $f[]=".text-center { text-align: center; }";
    $f[]="* {-webkit-box-sizing: border-box;-moz-box-sizing: border-box; box-sizing: border-box; }";
    $f[]="0% {opacity: 0;-webkit-transform: translateY(-20px);transform: translateY(-20px);}";
    $f[]="100% {opacity: 1;-webkit-transform: translateY(0);transform: translateY(0);}";
    $f[]="0% {opacity: 0;-webkit-transform: translateY(-20px);-ms-transform: translateY(-20px);transform: translateY(-20px);}";
    $f[]="100% {opacity: 1;-webkit-transform: translateY(0);-ms-transform: translateY(0);transform: translateY(0);}";
    $f[]=".middle-box h1 {font-size: 170px;}";
    $f[]="h1, h2, h3, h4, h5, h6 {font-weight: normal; }";
    $f[]="h1 {font-size: 30px;}";
    $f[]="h1, h2, h3, h4, h5, h6 {font-weight: 100; }";
    $f[]=".h1, h1 { font-size: 36px; }";
    $f[]=".h1, .h2, .h3, h1, h2, h3 {margin-top: 20px;margin-bottom: 10px;}";
    $f[]=".h1, .h2, .h3, .h4, .h5, .h6, h1, h2, h3, h4, h5, h6 {font-family: inherit;font-weight: 500;line-height: 1.1; color: inherit; }";
    $f[]="h1 {margin: .67em 0;margin-top: 0.67em; margin-bottom: 0.67em; font-size: 2em;}";
    $f[]="* { -webkit-box-sizing: border-box; -moz-box-sizing: border-box; box-sizing: border-box; }";
    $f[]="h3, h4, h5 { margin-top: 5px; font-weight: 600;}";
    $f[]="h3 {  margin-top: -120px;font-size: 16px; }";
    $f[]="</style>";
    $f[]="</head>";
    $f[]="";
    $f[]="<body class=\"gray-bg\">";
    $f[]="    <div class=\"middle-box text-center animated fadeInDown\">";
    $f[]="        <h1>$HTTPCODE</h1>";
    $f[]="        <h3 class=\"font-bold\">$title</h3>";
    $f[]="        <div class=\"error-desc\">";
    $f[]="            $content";
    $f[]="        </div>";
    $f[]="    </div>";
    $f[]="</body>";
    $f[]="</html>";
    @file_put_contents("/etc/haexchange.$HTTPCODE.http",@implode("\n",$f));
    @chmod("/etc/haexchange.$HTTPCODE.http",0750);
}



function build_database(){


    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $sql="CREATE TABLE IF NOT EXISTS `haexchange` (
		        `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`backendname` TEXT NOT NULL,
				`ipaddr` TEXT ,
				`listen_port` INTEGER NOT NULL,
				`bweight` INTEGER NOT NULL DEFAULT '1',
				`enabled` INTEGER NOT NULL DEFAULT '1',
                `status` INTEGER NOT NULL DEFAULT '0',
                `mapi` INTEGER NOT NULL DEFAULT '1',
                `rpc` INTEGER NOT NULL DEFAULT '1',
                `owa` INTEGER NOT NULL DEFAULT '1',
                `eas` INTEGER NOT NULL DEFAULT '1',
                `ecp` INTEGER NOT NULL DEFAULT '1',
                `ews` INTEGER NOT NULL DEFAULT '1',
                `oab` INTEGER NOT NULL DEFAULT '1',
                `autodiscover` INTEGER NOT NULL DEFAULT '1',
                `smtp` INTEGER NOT NULL DEFAULT '1',
                `imap` INTEGER NOT NULL DEFAULT '1',
                `imaps` INTEGER NOT NULL DEFAULT '1', 
                `exchtype` INTEGER NOT NULL DEFAULT '1',                
				`options` TEXT)";

    $q->QUERY_SQL($sql);
    if(!$q->ok){OutTxt($q->mysql_error);return false;}



    return true;
}


function install(){
    $unix=new unix();
    build_progress_restart(10,"{installing}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableHaExchange", 1);
    $php=$unix->LOCATE_PHP5_BIN();

    $unix->CreateUnixUser("haexchange","haexchange");

    if(is_file("/etc/init.d/nginx")){
        build_progress_restart(15,"{uninstalling} {APP_NGINX}");
        shell_exec("/usr/sbin/artica-phpfpm-service -nginx-uninstall");
    }

    if(is_file("/etc/init.d/proxy-pac")){
        build_progress_restart(20,"{uninstalling} {APP_PROXY_PAC}");
        shell_exec("/usr/sbin/artica-phpfpm-service -uninstall-proxypac");
    }
    if(is_file("/etc/init.d/haproxy")){
        build_progress_restart(25,"{uninstalling} {APP_HAPROXY}");
        shell_exec("/usr/sbin/artica-phpfpm-service -uninstall-proxypac");
    }

    build_progress_restart(30,"{installing}");
    install_service();
    monit_config();
    $basename=basename(__FILE__);
    $unix->Popuplate_cron_make("haexchange-rotate", "5 0 * * *", "$basename --rotate --byschedule");
    system("/etc/init.d/cron reload");

    if(!UDPServerRun()){
        system("/usr/sbin/artica-phpfpm-service -reconfigure-syslog");
        if(UDPServerRun()){
            system("/etc/init.d/rsyslog restart");
        }
    }


    build_progress_restart(40,"{installing}");
    echo "[".__LINE__."]:Stopping...\n";
    build_progress_restart(50,"{stopping}");
    stop(true);
    echo "[".__LINE__."]:Starting...\n";
    build_progress_restart(60,"{starting}");
    start(true);
    echo "[".__LINE__."]:Starting OK\n";
    build_progress_restart(70,"{starting} OK");
    system("/usr/sbin/artica-phpfpm-service -reconfigure-syslog");
    build_progress_restart(100,"{success}");
    sleep(1);
    build_progress_restart(100,"{success}");
    sleep(1);
    build_progress_restart(100,"{success}");
}

function CountOfNodes($type){
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM haexchange WHERE $type=1 AND enabled=1");

    if(!$q->ok){
        $GLOBALS["SQL"]=$q->mysql_error;
        OutTxt($q->mysql_error);
    }
    OutTxt("Protocol $type: {$ligne["tcount"]} node(s)");
    return intval($ligne["tcount"]);
}


function monit_config(){
    $unix=new unix();
    $f=array();
    $php=$unix->LOCATE_PHP5_BIN();
    $f[]="check process APP_HAPROXY_EXCHANGE";
    $f[]="with pidfile /var/run/ha-exchange.pid";
    $f[]="start program = \"/etc/init.d/ha-exchange start --monit\"";
    $f[]="stop program =  \"/etc/init.d/ha-exchange stop --monit\"";
    $f[]="if 5 restarts within 5 cycles then timeout";


    $SCRIPT=array();
    $SCRIPT[]="#!/bin/sh";
    $SCRIPT[]="";
    $SCRIPT[]="$php ".__FILE__." --rotate";
    $SCRIPT[]="";
    @file_put_contents("/usr/sbin/ha-exchange-rotate.sh", @implode("\n", $SCRIPT));
    @chmod("/usr/sbin/ha-exchange-rotate.sh", 0755);
    @file_put_contents("/etc/monit/conf.d/APP_HAPROXY_EXCHANGE.monitrc", @implode("\n", $f));
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    return true;
}
function UDPServerRun(){
    $f=explode("\n",@file_get_contents("/etc/rsyslog.conf"));
    foreach ($f as $num=>$ligne){
        $ligne=trim($ligne);
        if(substr($ligne, 0,1)=="#"){continue;}
        if(!preg_match("#UDPServerRun#", $ligne)){continue;}
        return true;

    }
}

function build_certificate($commname){
    $keyout="/etc/ssl/certs/ha-exchange.pem";
    if(!is_dir("/etc/ssl/certs")){@mkdir("/etc/ssl/certs",0755,true);}
    $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $sql="SELECT `UsePrivKeyCrt`,`SquidCert`,`crt`,`privkey`,`Squidkey`,`bundle` 
    FROM sslcertificates WHERE CommonName='$commname'";
    $ligne=$q->mysqli_fetch_array($sql);

    if(!isset($ligne["UsePrivKeyCrt"])){$ligne["UsePrivKeyCrt"]=0;}
    if(!isset($ligne["bundle"])){$ligne["bundle"]=null;}
    if(!isset($ligne["privkey"])){$ligne["privkey"]=null;}
    if(!isset($ligne["crt"])){$ligne["crt"]=null;}

    if(!$q->ok){
        OutTxt($q->mysql_error);
        return false;
    }

    $ligne["privkey"]=trim(str_replace("\\n", "\n", $ligne["privkey"]));
    $ligne["crt"]=trim(str_replace("\\n", "\n", $ligne["crt"]));
    $ligne["bundle"]=trim(str_replace("\\n", "\n", $ligne["bundle"]));
    $CertificateData=null;

    if(intval($ligne["UsePrivKeyCrt"])==1) {
        OutTxt("CommonName: `$commname`, UsePrivKeyCrt = 1");
        $strlen = strlen($ligne["crt"]);
        $strlen2 = strlen($ligne["privkey"]);
        $strlen3 = strlen($ligne["bundle"]);
        if($strlen<20){$ligne["UsePrivKeyCrt"]=0;}
        if($strlen2<20){$ligne["UsePrivKeyCrt"]=0;}
        if($strlen3>20){
            @file_put_contents("$keyout", "{$ligne["crt"]}\n{$ligne["bundle"]}\n{$ligne["privkey"]}");
            return true;
        }

        @file_put_contents("$keyout", "{$ligne["crt"]}\n{$ligne["privkey"]}\n");
        return true;
    }

    if(intval($ligne["UsePrivKeyCrt"])==0){

        $sql="SELECT `UsePrivKeyCrt`,`easyrsa`,`SquidCert`,`Squidkey`,`crt`,`privkey`,`srca` FROM sslcertificates WHERE CommonName='$commname'";
        $ligne=$q->mysqli_fetch_array($sql);
        if(!$q->ok){
            echo $q->mysql_error."\n";
            return false;
        }
        $privatekey=str_replace("\\n", "\n", $ligne["srca"]);
        $SquidCert=$ligne["SquidCert"];
        $strlen=strlen($SquidCert);
        $strlen2=strlen($privatekey);
        $strlen3=strlen($ligne["Squidkey"]);
        if($ligne["easyrsa"]==1){$strlen2=0;}
        if($strlen2==0){
            if($strlen3>20){
                $privatekey=str_replace("\\n", "\n", $ligne["Squidkey"]);
            }
        }

        if($SquidCert==null){
            OutTxt("ALERT! SquidCert certificate data is null!");
            return false;
        }

        $SquidCert=str_replace("\\n", "\n",$SquidCert);
        $final="$SquidCert\n$privatekey\n";
        $final=str_replace("\n\n","\n",$final);
        @file_put_contents("$keyout", $final);
        return true;
    }

}

function NoExchange2010($type=null) {
    $TYPEQ=null;

    if($type<>null){
        $TYPEQ=" AND $type=1";
    }
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $sql="SELECT count(ID) as tcount FROM haexchange WHERE enabled=1 AND exchtype=0{$TYPEQ}";
    $ligne=$q->mysqli_fetch_array($sql);
    OutTxt("$type: Exchange 2010 = {$ligne["tcount"]}");
    if(intval($ligne["tcount"])>0){
        return false;
    }
    return true;

}

function build_https_backends($type=null){
    $LIMIT=null;
    $TYPEQ=null;
    $rPort=443;
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $LIMIT="LIMIT 1";
    }
    if($type<>null){
        $TYPEQ=" AND $type=1";
    }

    $suffix="check check-ssl verify none";
    if($type=="smtp"){
        $suffix="check";
        $rPort=25;
    }
    if($type=="imaps"){
        $suffix="check";
        $rPort=993;
    }

    $f=array();
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $sql="SELECT ipaddr,ID,bweight FROM haexchange WHERE enabled=1{$TYPEQ} ORDER BY bweight $LIMIT";
    $results=$q->QUERY_SQL($sql);

    if(!$q->ok){
        $GLOBALS["SQL"]=$q->mysql_error;
        OutTxt($q->mysql_error);
    }

    foreach ($results as $ligne) {
        $bweight=$ligne["bweight"];
        $backendname="exchsrv{$ligne["ID"]}";
        $f[] = "\tserver $backendname {$ligne["ipaddr"]}:$rPort weight $bweight $suffix";
    }
    return @implode("\n",$f);
}

function build(){
    $unix=new unix();

    if(!is_dir("/var/lib/ha-exchange")){
        @mkdir("/var/lib/ha-exchange",0755,true);
    }
    @chmod("/var/lib/ha-exchange",0755);
    @chown("/var/lib/ha-exchange","haexchange");
    @chgrp("/var/lib/ha-exchange","haexchange");
    $unix->CreateUnixUser("www-data","www-data","Web services");

    $HaProxyMaxConn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterMaxConn"));
    $HaProxyCPUS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaProxyCPUS"));
    $HaExchangeCertif=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaExchangeCertif"));
    $HaExchangeInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaExchangeInterface"));
    $HaExchangeOutInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaExchangeOutInterface"));
    $HaExchangeBalance=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaExchangeBalance"));
    if($HaProxyMaxConn<2000){$HaProxyMaxConn=2000;}
    if($HaExchangeBalance==null){$HaExchangeBalance="roundrobin";}
    $CertPath="/etc/ssl/certs/ha-exchange.pem";

    if( ($HaExchangeCertif==null) OR ($HaExchangeInterface==null) OR (build_database()==false) ){
        OutTxt("FATAL! Missing required parameter");
        if(is_file("/etc/ha-exchange.cfg")){@unlink("/etc/ha-exchange.cfg");}
        return false;
    }

    OutTxt("Building $HaExchangeCertif certificate...");
    if(!build_certificate($HaExchangeCertif)){
        echo "Certificate $HaExchangeCertif failed\n";
        if(is_file("/etc/ha-exchange.cfg")){@unlink("/etc/ha-exchange.cfg");}
        return false;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $COUNT=$q->COUNT_ROWS("haexchange");
    if($COUNT==0){
        OutTxt("No node as been defined, aborting");
        if(is_file("/etc/ha-exchange.cfg")){@unlink("/etc/ha-exchange.cfg");}
        return false;
    }

    $HaExchangeIP=$unix->InterfaceToIPv4($HaExchangeInterface);

    $HaExchangeOutIP=null;
    if($HaExchangeOutInterface<>null){
        $HaExchangeOutIP=$unix->InterfaceToIPv4($HaExchangeOutInterface);
        if($HaExchangeOutIP==$HaExchangeIP){$HaExchangeOutIP=null;}
    }

    $f[]="global";
    $f[]="\tlog         127.0.0.1 local2 info";
    $f[]="\tchroot      /var/lib/ha-exchange";
    $f[]="\tpidfile     /var/run/ha-exchange.pid";
    $f[]="\tlog-tag     haexchange";
    $f[]="\tuser        www-data";
    $f[]="\tgroup       www-data";
    $f[]="\tdaemon";
    $f[]="\tstats socket /var/run/ha-exchange.stat mode 600 level admin";
    if($HaProxyCPUS>1){
        $f[]="\tnbproc           $HaProxyCPUS";
        for($i=1;$i<$HaProxyCPUS+1;$i++){
            $cpumap=$i-1;
            $f[]="\tcpu-map           $i $cpumap";
        }
    }
    $f[]="\tssl-default-bind-options no-sslv3";
    $f[]="#\tssl-default-bind-ciphers ECDH+AESGCM:DH+AESGCM:ECDH+AES256:DH+AES256:ECDH+AES128:DH+AES:RSA+AESGCM:RSA+AES:!aNULL:!MD5:!DSS";
    $f[]="\tssl-default-server-options no-sslv3";
    $f[]="#\tssl-default-server-ciphers ECDH+AESGCM:DH+AESGCM:ECDH+AES256:DH+AES256:ECDH+AES128:DH+AES:RSA+AESGCM:RSA+AES:!aNULL:!MD5:!DSS";
    $f[]="\ttune.ssl.default-dh-param 2048";
    $f[]="\tssl-server-verify none";
    $f[]="";
    $f[]="";
    $f[]="defaults";
    $f[]="\tmode                    http";
    $f[]="\tlog                     global";
    $f[]="\toption                  httplog";
    $f[]="\toption                  dontlognull";
    $f[]="\toption                  redispatch";
    $f[]="#\toption                  contstats ";
    $f[]="\tretries                 3";
    $f[]="\ttimeout http-request    10s";
    $f[]="\ttimeout queue           1m";
    $f[]="\ttimeout connect         10s";
    $f[]="\ttimeout client          15m # this value should be rather high with Exchange";
    $f[]="\ttimeout server          15m # this value should be rather high with Exchange";
    $f[]="\ttimeout http-keep-alive 10s";
    $f[]="\ttimeout check           10s";
    $f[]="\tmaxconn                 $HaProxyMaxConn";

    if($HaExchangeOutIP<>null){
        $f[]="\tsource $HaExchangeOutIP";
    }

    $mapi=CountOfNodes("mapi");
    $rpc=CountOfNodes("rpc");
    $owa=CountOfNodes("owa");
    $autodiscover=CountOfNodes("autodiscover");
    $eas=CountOfNodes("eas");
    $ecp=CountOfNodes("ecp");
    $ews=CountOfNodes("ews");
    $oab=CountOfNodes("oab");

    $f[]="";
    $f[]="";
    $f[]="frontend fe_exchange";
    $f[]="#\thttp-response set-header Strict-Transport-Security max-age=31536000;\ includeSubdomains;\ preload";
    $f[]="\thttp-response set-header X-Frame-Options SAMEORIGIN";
    $f[]="\thttp-response set-header X-Content-Type-Options nosniff";
    $f[]="\tmode http";
    $f[]="\tbind $HaExchangeIP:80";
    $f[]="\tbind $HaExchangeIP:443 ssl crt $CertPath";
    $f[]="\toption forwardfor except 127.0.0.0/8";
    $f[]="\thttp-request set-header X-Client-IP %[src]";
    $f[]="\thttp-request set-header X-Forwarded-Port %[dst_port]";
    $f[]="\thttp-request add-header X-Forwarded-Proto https if { ssl_fc }";
    $f[]="\tredirect scheme https code 301 if !{ ssl_fc }   # redirect 80 -> 443 (for owa)";
    $f[]="\terrorfile 403 /etc/haexchange.403.http";
    build_errorpage(403,"Forbidden",
        "Sorry, Your Request is forbidden by administrative rules.");
    build_errorpage(503,"Service Unavailable",
        "Sorry, but No Microsoft Exchange server is available to handle this request.");

    $f[]="\terrorfile 503 /etc/haexchange.503.http";
    if($autodiscover>0) {
        $f[] = "\tacl autodiscover url_beg /Autodiscover";
        $f[] = "\tacl autodiscover url_beg /autodiscover";
    }
    if($mapi>0) {
        $f[] = "\tacl mapi url_beg /mapi";
    }
    if($rpc>0) {
        $f[] = "\tacl rpc url_beg /rpc";
    }
    if($owa>0) {
        $f[] = "\tacl owa url_beg /owa";
        $f[] = "\tacl owa url_beg /OWA";
    }
    if($eas>0) {
        $f[] = "\tacl eas url_beg /Microsoft-Server-ActiveSync";
    }
    if($ecp>0) {
        $f[] = "\tacl ecp url_beg /ecp";
    }
    if($ews>0) {
        $f[] = "\tacl ews url_beg /EWS";
        $f[] = "\tacl ews url_beg /ews";
    }
    if($oab>0) {
        $f[] = "\tacl oab url_beg /OAB";
    }
    if($autodiscover>0) {
        $f[] = "\tuse_backend be_exchange_autodiscover if autodiscover";
    }
    if($mapi>0) {
        $f[] = "\tuse_backend be_exchange_mapi if mapi";
    }
    if($rpc>0) {
        $f[] = "\tuse_backend be_exchange_rpc if rpc";
    }
    if($owa>0) {
        $f[] = "\tuse_backend be_exchange_owa if owa";
    }
    if($eas>0) {
        $f[] = "\tuse_backend be_exchange_eas if eas";
    }
    if($ecp>0) {
        $f[] = "\tuse_backend be_exchange_ecp if ecp";
    }
    if($ews>0) {
        $f[] = "\tuse_backend be_exchange_ews if ews";
    }
    if($oab>0) {
        $f[] = "\tuse_backend be_exchange_oab if oab";
    }
    $f[]="\tdefault_backend be_exchange";
    $f[]="";
    $f[]=" ";
    $f[]="";
    $f[]="#------------------------------";
    $f[]="# Back-end section";
    $f[]="#------------------------------";
    $f[]="";
    if($autodiscover>0) {
        $f[] = "backend be_exchange_autodiscover";
        $f[] = "\tmode http";
        $f[] = "\tbalance $HaExchangeBalance";
        $f[] = "\toption log-health-checks";
        if(NoExchange2010("autodiscover")) {
            $f[] = "\toption httpchk GET /autodiscover/healthcheck.htm";
            $f[] = "\thttp-check expect status 200";
        }else{
            $f[] = "\thttp-check expect ! rstatus ^5";
        }
        $f[] = build_https_backends("autodiscover");
        $f[]="";
        $f[]="";
    }
    if($mapi>0) {
        $f[] = "backend be_exchange_mapi";
        $f[] = "\tmode http";
        $f[] = "\tbalance $HaExchangeBalance";
        $f[] = "\toption log-health-checks";
        if(NoExchange2010("mapi")) {
            $f[] = "\toption httpchk GET /mapi/healthcheck.htm";
            $f[] = "\thttp-check expect status 200";
        }else{
            $f[] = "\thttp-check expect ! rstatus ^5";
        }
        $f[] = build_https_backends("mapi");
        $f[] = "";
        $f[] = "";
    }
    if($rpc>0) {
        $f[] = "backend be_exchange_rpc";
        $f[] = "\tmode http";
        $f[] = "\tbalance $HaExchangeBalance";
        $f[] = "\toption log-health-checks";
        if(NoExchange2010("rpc")) {
            $f[] = "\toption httpchk GET /rpc/healthcheck.htm";
            $f[] = "\thttp-check expect status 200";
        }else{
            $f[] = "\thttp-check expect ! rstatus ^5";
        }
        $f[] = build_https_backends("rpc");
        $f[] = "";
        $f[] = "";
    }

    if($owa>0) {
        $f[] = "backend be_exchange_owa";
        $f[] = "\tmode http";
        $f[] = "\tbalance $HaExchangeBalance";
        $f[] = "\toption log-health-checks";
        if(NoExchange2010("owa")) {
            $f[] = "\toption httpchk GET /owa/healthcheck.htm";
            $f[] = "\thttp-check expect status 200";
        }else{
            $f[] = "\thttp-check expect ! rstatus ^5";
        }
        $f[] = build_https_backends("owa");
        $f[] = "";
        $f[] = "";
    }
    if($eas>0) {
        $f[] = "backend be_exchange_eas";
        $f[] = "\tmode http";
        $f[] = "\tbalance $HaExchangeBalance";
        $f[] = "\toption log-health-checks";
        if(NoExchange2010("eas")) {
            $f[] = "\toption httpchk GET /microsoft-server-activesync/healthcheck.htm";
            $f[] = "\thttp-check expect status 200";
        }else{
            $f[] = "\thttp-check expect ! rstatus ^5";
        }
        $f[] = build_https_backends("eas");
        $f[] = "";
        $f[] = "";
    }
    if($ecp>0) {
        $f[] = "backend be_exchange_ecp";
        $f[] = "\tmode http";
        $f[] = "\tbalance $HaExchangeBalance";
        $f[] = "\toption log-health-checks";
        if(NoExchange2010("ecp")) {
            $f[] = "\toption httpchk GET /ecp/healthcheck.htm";
            $f[] = "\thttp-check expect status 200";
        }else{
            $f[] = "\thttp-check expect ! rstatus ^5";
        }
        $f[] = build_https_backends("ecp");
        $f[] = "";
        $f[] = "";
    }

    if($ews>0) {
        $f[] = "backend be_exchange_ews";
        $f[] = "\tmode http";
        $f[] = "\tbalance $HaExchangeBalance";
        $f[] = "\toption log-health-checks";
        if(NoExchange2010("ews")) {
            $f[] = "\toption httpchk GET /ews/healthcheck.htm";
            $f[] = "\thttp-check expect status 200";
        }else{
            $f[] = "\thttp-check expect ! rstatus ^5";
        }
        $f[] = build_https_backends("ews");
        $f[] = "";
        $f[] = "";
    }

    if($oab>0) {
        $f[] = "backend be_exchange_oab";
        $f[] = "\tmode http";
        $f[] = "\tbalance $HaExchangeBalance";
        $f[] = "\toption log-health-checks";
        if(NoExchange2010("oab")) {
            $f[] = "\toption httpchk GET /oab/healthcheck.htm";
            $f[] = "\thttp-check expect status 200";
        }else{
            $f[] = "\thttp-check expect ! rstatus ^5";
        }
        $f[] = build_https_backends("oab");
        $f[] = "";
        $f[] = "";
    }
    $f[]="backend be_exchange";
    $f[]="\tmode http";
    $f[] = "\thttp-check expect ! rstatus ^5";
    $f[]="\tbalance $HaExchangeBalance";
    $f[] = build_https_backends(null);
    $f[]="";
    $f[]="";
    $f[]="#######################################";
    $f[]="# End of Exchange's own protocols,";
    $f[]="# STMP and IMAP next";
    $f[]="########################################";
    $f[]="";
    $f[]="";
    $smtp=CountOfNodes("smtp");
    if($smtp>0) {
        $f[] = "frontend fe_exchange_smtp";
        $f[] = "\tmode tcp";
        $f[] = "\toption tcplog";
        $f[] = "\tbind $HaExchangeIP:25 name smtp # VIP";
        $f[] = "\tdefault_backend be_exchange_smtp";
        $f[] = "";
        $f[] = "";
        $f[] = "backend be_exchange_smtp";
        $f[] = "\tmode tcp";
        $f[] = "\tbalance $HaExchangeBalance";
        $f[] = "\toption log-health-checks";
        $f[] = build_https_backends("smtp");
        $f[] = "";
        $f[] = "";
    }

    $imaps=CountOfNodes("imaps");
    if($imaps>0) {
        $f[] = "frontend fe_exchange_imaps";
        $f[] = "\tmode tcp";
        $f[] = "\toption tcplog";
        $f[] = "#   bind $HaExchangeIP:143 name imap";
        $f[] = "\tbind $HaExchangeIP:993 name imaps";
        $f[] = "\tdefault_backend be_exchange_imaps";
        $f[] = " ";
        $f[] = "backend be_exchange_imaps";
        $f[] = "\tmode tcp";
        $f[] = "\tbalance $HaExchangeBalance";
        $f[] = "\toption log-health-checks";
        $f[] = "#   stick store-request src";
        $f[] = "#   stick-table type ip size 200k expire 30m";
        $f[] = "#   option tcp-check";
        $f[] = "#   tcp-check connect port 143";
        $f[] = "#   tcp-check expect string * OK";
        $f[] = "#   tcp-check connect port 993 ssl";
        $f[] = "#   tcp-check expect string * OK";
        $f[] = build_https_backends("imaps");
        $f[] = "";
        $f[] = "";
    }
    @file_put_contents("/etc/ha-exchange.cfg", @implode("\n",$f));
}


function uninstall(){
    build_progress_restart(20,"{uninstalling}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableHaExchange", 0);
    remove_service("/etc/init.d/ha-exchange");
    @unlink("/etc/monit/conf.d/APP_HAPROXY_EXCHANGE.monitrc");
    @unlink("/etc/cron.d/haexchange-rotate");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    shell_exec("/etc/init.d/cron reload");
    build_progress_restart(100,"{success}");
}
function remove_service($INITD_PATH){
    if(!is_file($INITD_PATH)){return;}
    system("$INITD_PATH stop");
    if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
    if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
    if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}
function install_service(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/ha-exchange";
    $php5script=basename(__FILE__);
    $daemonbinLog="Exchange Load-Balancer Daemon";



    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         ha-exchange";
    $f[]="# Required-Start:    \$local_fs \$syslog";
    $f[]="# Required-Stop:     \$local_fs \$syslog";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: $daemonbinLog";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";

    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="\t$php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="\t;;";
    $f[]="";
    $f[]="\tstop)";
    $f[]="\t$php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="\t;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="\t$php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
    $f[]="\t;;";
    $f[]="";
    $f[]=" reload)";
    $f[]="\t$php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
    $f[]="\t;;";
    $f[]="";
    $f[]=" reconfigure)";
    $f[]="\t$php /usr/share/artica-postfix/$php5script --build \$2 \$3";
    $f[]="\t;;";
    $f[]="";
    $f[]="\t*)";
    $f[]="\techo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
    $f[]="\texit 1";
    $f[]="\t;;";
    $f[]="esac";
    $f[]="exit 0\n";


    echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }


}
function replicate_daemon(){
    $unix=new unix();
    $DaemonPath="/usr/sbin/ha-exchange";
    $HAPROXY=$unix->find_program("haproxy");

    $mdsrc=md5_file($HAPROXY);
    $mddst=md5_file($DaemonPath);
    if($mdsrc<>$mddst){
        if(is_file($DaemonPath)){@unlink($DaemonPath);}
        @copy($HAPROXY,$DaemonPath);
    }
}

function reload(){
    if(!$GLOBALS["NOCONF"]){
        build_progress_restart(20,"{building_settings}");
        build();
    }
    if(!isRunning()){
        build_progress_restart(50,"{starting_service}");
        start(true);
        if(!isRunning()){build_progress_restart(110,"{starting_service} {failed}");return;}
        build_progress_restart(100,"{starting_service} {success}");
        return;
    }

    $DaemonPath="/usr/sbin/ha-exchange";
    replicate_daemon();
    $CONFIG="/etc/ha-exchange.cfg";
    $PIDFILE="/var/run/ha-exchange.pid";
    $EXTRAOPTS=null;
    $pids=@implode(" ", pidsarr());
    build_progress_restart(90,"{reloading} $pids");

    $cmd="$DaemonPath -f \"$CONFIG\" -p $PIDFILE -D $EXTRAOPTS -sf $pids 2>&1";
    exec($cmd,$results);
    foreach ($results as $num=>$ligne){
        OutTxt("$ligne");
    }
    build_progress_restart(100,"{reloading} {success}");
}

function isRunning(){
    $running=false;
    $unix=new unix();
    $f=pidsarr();
    foreach ($f as $pid){
        if($unix->process_exists($pid)){
            return true;
        }
    }

    return false;
}

function pidsarr(){
    $R=array();
    $f=file("/var/run/ha-exchange.pid");
    foreach ($f as $num=>$ligne){
        $ligne=trim($ligne);
        if(!is_numeric($ligne)){continue;}
        $R[]=$ligne;
    }
    return $R;
}






function start($aspid=false){
    $unix=new unix();
    $Masterbin="/usr/sbin/ha-exchange";
    replicate_daemon();
   

    if(!is_file($Masterbin)){
        if($GLOBALS["OUTPUT"]){OutTxt("ha-exchange not installed");}
        build_progress_stop(110,"{failed}");
        return false;
    }

    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            OutTxt("Already Artica task running PID $pid since {$time}mn");
            build_progress_stop(110,"{failed}");
            return true;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();

    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        OutTxt("Service already started $pid since {$timepid}Mn...");
        build_progress_stop(100,"{success}");
        return true;
    }
    if(!is_file("/etc/ha-exchange.cfg")){
        OutTxt("/etc/ha-exchange.cfg no such file");
        build_progress_stop(110,"{failed}");
        return false;
    }

    $nohup=$unix->find_program("nohup");
    chmod($Masterbin,0755);
    build_progress_stop(50,"{starting}");
    $cmd="$nohup $Masterbin -f /etc/ha-exchange.cfg -D -p /var/run/ha-exchange.pid  >/dev/null 2>&1 &";

    OutTxt("service");
    shell_exec($cmd);

    $prc=50;
    for($i=1;$i<5;$i++){
        build_progress_restart(95,"{starting_service} $i/5");
        build_progress_stop($prc++,"{starting_service} $i/5");
        OutTxt("waiting $i/5");
        sleep(1);
        $pid=PID_NUM();
        if($unix->process_exists($pid)){break;}
    }

    $pid=PID_NUM();
    if($unix->process_exists($pid)){
        OutTxt("Success PID $pid");
        build_progress_stop(100,"{starting_service} {success}");
    }else{
        OutTxt("Failed");
        OutTxt("$cmd");
        build_progress_stop(110,"{starting_service} {failed}");
        return false;
    }

    OutTxt("Success !");
    build_progress_stop(100,"{starting_service} {success}");
    return true;

}



function PID_NUM(){

    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/ha-exchange.pid");
    if($unix->process_exists($pid)){return $pid;}
    $Masterbin="/usr/sbin/ha-exchange";
    return $unix->PIDOF($Masterbin);

}

function stop($aspid=false){
    $unix=new unix();
    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
            build_progress_stop(110,"{failed}");
            return;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();


    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
        build_progress_stop(100,"{success}");
        return;
    }
    $pid=PID_NUM();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $kill=$unix->find_program("kill");



    build_progress_stop(50,"{stopping_service} pid:$pid");
    OutTxtSTP("service Shutdown pid $pid...");
    unix_system_kill($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        OutTxtSTP("service waiting pid:$pid $i/5...");
        sleep(1);
    }

    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        OutTxtSTP("service success...");
        build_progress_stop(100,"{stopping_service} {success}");
        return;
    }

    OutTxtSTP("service shutdown - force - pid $pid...");
    unix_system_kill_force($pid);
    build_progress_stop(60,"{stopping_service} pid:$pid");
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        OutTxtSTP("service waiting pid:$pid $i/5...");
        sleep(1);
    }

    if($unix->process_exists($pid)){
        OutTxtSTP("service failed...");
        build_progress_stop(110,"{stopping_service} pid:$pid {failed}");
        return;
    }
    build_progress_stop(100,"{stopping_service} {success}");

}
function OutTxtSTP($text){
    $text=trim($text);
    echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $text\n";

}
function OutTxt($text){
    $text=trim($text);
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $text\n";
}



function shell_exec2($cmd){
    OutTxt("$cmd");
    shell_exec($cmd);

}

function rotate(){
    $unix=new unix();
    $echo=$unix->find_program("echo");
    $php=$unix->LOCATE_PHP5_BIN();
    $LogRotatePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath");
    $BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
    if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
    if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}

    $MeDir="$BackupMaxDaysDir/haproxy";
    $LastRotate=$unix->file_time_min("/etc/artica-postfix/pids/haproxy-rotate-cache.time");
    $SquidLogRotateFreq=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogRotateFreq"));
    if($SquidLogRotateFreq<10){$SquidLogRotateFreq=1440;}

    $MUSTROTATE=true;
    if($GLOBALS["FORCE"]){$MustRotateAt=0;$MUSTROTATE=true;}
    if($GLOBALS["PROGRESS"]){$MustRotateAt=0;$MUSTROTATE=true;}
    $BackupSquidLogsUseNas=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas");

    $size=@filesize("/var/log/haproxy.log");
    $ROTATED=false;
    $size=$size/1024;
    $size=round($size/1024);

    if(!$MUSTROTATE){
        if($LastRotate<$SquidLogRotateFreq){
            if($size>=$MustRotateAt){$MUSTROTATE=true;}
        }
    }


    $suffix_time=time();
    if(!$MUSTROTATE){return;}

    squid_admin_mysql(2,"Haproxy Backup source file","/var/log/haproxy.log = {$size}MB ");
    if(!@copy("/var/log/haproxy.log", "$LogRotatePath/haproxy.log.$suffix_time")){
        @unlink("$LogRotatePath/haproxy.log.$suffix_time");
        squid_admin_mysql(0, "[LOG ROTATION]: Unable to duplicate source log!", "/var/log/haproxy.log -> $LogRotatePath/haproxy.log.$suffix_time ",__FILE__,__LINE__);
        return;
    }

    shell_exec("$echo \"\" >/var/log/haproxy.log");
    $targetfile="$MeDir/haproxy-".date("Y-m-d-H-i").".gz";
    if(!$unix->compress("$LogRotatePath/haproxy.log.$suffix_time", $targetfile)){
        squid_admin_mysql(0, "[LOG ROTATION]: Unable to compress source log!", "$LogRotatePath/haproxy.log.$suffix_time -> $targetfile",__FILE__,__LINE__);
        return;

    }

    squid_admin_mysql(2,"Backup source file {success}","$LogRotatePath/haproxy.log.$suffix_time",__FILE__,__LINE__);

    if($BackupSquidLogsUseNas==1){
        shell_exec("$php /usr/share/artica-postfix/exec.squid.rotate.php --backup-haproxy");
    }

}


