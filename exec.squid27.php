<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";exit();}}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["SERVICE_NAME"]="Squid-Cache NAT front-end";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();exit();}

function restart($nopid=false){
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    if(!$nopid){
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
            return;
        }
    }
    @file_put_contents($pidfile, getmypid());
    stop(true);
    build();
    start(true);
}

function reload($nopid=false){
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    if(!$nopid){
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
            return;
        }
    }
    @file_put_contents($pidfile, getmypid());

    $sock=new sockets();
    $EnableTransparent27=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransparent27"));
    if($EnableTransparent27==0){
        if($GLOBALS["OUTPUT"]){echo "Reload........: [INIT]: {$GLOBALS["SERVICE_NAME"]} Disabled ( see EnableTransparent27 )...\n";}
        return;
    }


    build();
    $masterbin=$unix->find_program("squid27");
    if(!is_file($masterbin)){
        if($GLOBALS["OUTPUT"]){echo "Reload........: [INIT]: {$GLOBALS["SERVICE_NAME"]} not installed\n";}
        return;
    }
    $pid=squid_27_pid();
    if($unix->process_exists($pid)){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        if($GLOBALS["OUTPUT"]){echo "Reload........: [INIT]: {$GLOBALS["SERVICE_NAME"]} Service running since {$time}Mn...\n";}
        shell_exec("$masterbin -f /etc/squid27/squid.conf -k reconfigure");
        return;
    }
    start(true);
}

function NETWORK_ALL_INTERFACES(){
    if(isset($GLOBALS["NETWORK_ALL_INTERFACES"])){return $GLOBALS["NETWORK_ALL_INTERFACES"];}
    $unix=new unix();
    $GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES(true);
    unset($GLOBALS["NETWORK_ALL_INTERFACES"]["127.0.0.1"]);
}
function squidnat(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/squid-nat";
    $php5script="exec.squid27.php";
    $daemonbinLog="Squid-Cache NAT front-end";
    if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}


    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         squid-nat";
    $f[]="# Required-Start:    \$local_fs \$syslog \$squid";
    $f[]="# Required-Stop:     \$local_fs \$syslog \$squid";
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
    $f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reconfigure)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reload)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
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


function build(){
    $sock=new sockets();
    $unix=new unix();
    $ini=new Bs_IniHandler();
    $IPADDRSSL=array();
    $IPADDRSSL2=array();
    if(!is_file("/etc/init.d/squid-nat")){squidnat();}
    $ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');

    $visible_hostname=$unix->hostname_g();

    if(strpos($visible_hostname, ".")>0){
        $visible_hostnameTR=explode(".",$visible_hostname);
        $visible_hostnameTR[0]=$visible_hostnameTR[0]."-nat";
        $visible_hostname=@implode(".", $visible_hostnameTR);
    }else{
        $visible_hostname="nat-$visible_hostname";
    }


    $SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");

    $ini->loadString($ArticaSquidParameters);
    NETWORK_ALL_INTERFACES();
    $LISTEN_PORT=intval($ini->_params["NETWORK"]["LISTEN_PORT"]);
    $ICP_PORT=intval(trim($ini->_params["NETWORK"]["ICP_PORT"]));
    $certificate_center=$ini->_params["NETWORK"]["certificate_center"];
    $SSL_BUMP=intval($ini->_params["NETWORK"]["SSL_BUMP"]);
    $LogsWarninStop=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsWarninStop"));
    $SquidAllowRemotePorts=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAllowRemotePorts"));
    $ssl=false;
    if($ICP_PORT==0){$ICP_PORT=3130;}
    if($LISTEN_PORT==0){$LISTEN_PORT=3128;}
    $squid=new squidbee();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $IPADDRS=array();


    $ParentSquid27Port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ParentSquid27Port"));
    if($ParentSquid27Port==0){$ParentSquid27Port=13298;}



    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [CONF]: {$GLOBALS["SERVICE_NAME"]} visible hostname........: $visible_hostname\n";}
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [CONF]: {$GLOBALS["SERVICE_NAME"]} ICP Port................: $ICP_PORT\n";}


    $sql="SELECT * FROM proxy_ports WHERE enabled=1 and is_nat=1";
    $results = $q->QUERY_SQL($sql);
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [CONF]: {$GLOBALS["SERVICE_NAME"]} ".mysqli_num_rows($results)." ports\n";}


    $f[]="# --------- proxy_ports enabled=1 and is_nat=1 -> ". mysqli_num_rows($results)." ports";

    $MAINSSL=array();

    foreach ($results as $index=>$ligne){
        $ipaddr=$ligne["ipaddr"];
        $xport=$ligne["port"];
        $ssl=intval($ligne["UseSSL"]);
        $eth=$ligne["nic"];
        $transparent_text=null;
        $ipaddr="0.0.0.0";
        $transparent=" transparent";

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $eth $ipaddr:$xport\n";}

        if($eth<>null){
            $ipaddr=$GLOBALS["NETWORK_ALL_NICS"][$eth]["IPADDR"];
            if($ipaddr==null){
                $conf[]="# --------- table proxy_ports $eth $ipaddr:$xport -> Hardware Error [".__LINE__."]\n";
                $conf[]="# --------- http $ipaddr -> Hardware Error -> 0.0.0.0 [".__LINE__."]\n";
                $ipaddr="0.0.0.0";
            }
        }


        $f[]="# --------- Port $xport listen on $eth ($ipaddr) UseSSL=$ssl certificate={$ligne["sslcertificate"]}";




        if($ssl==1){
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $ipaddr:$xport: SSL Intercept...........: Yes - $ssl_port\n";}
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $ipaddr:$xport: Certificate.............: {$ligne["sslcertificate"]}\n";}
            $squidR=new squid_certificate($ligne["sslcertificate"]);
            $MAINSSL=$squidR->SaveCertificate($ligne["sslcertificate"],false,false,false,true);
            $f[]=$MAINSSL[0];
            $key=$MAINSSL[1]["key"];
            $certificate=$MAINSSL[1]["certificate"];
            $f[]="#";
            $f[]=@implode("\n", $squidR->LOGS);
            $f[]="https_port $ipaddr:$xport transparent cert=$certificate key=$key";
            continue;
        }
        $f[]="http_port $ipaddr:$xport$transparent";


    }




        $k=array();
        $NetworkScannerMasks=$sock->GET_INFO('NetworkScannerMasks');
        $tbl=explode("\n",$NetworkScannerMasks);
        if(is_array($tbl)){
            foreach ($tbl as $cidr){
                if(trim($cidr)==null){continue;} $k[$cidr]=$cidr;
            }
        }


        if(count($k==0)){$f[]="acl localnet src all";}
        if(count($k>0)){
            foreach ($k as $l){$s[]=$l;}
            $f[]="acl localnet src " . implode(" ",$s);
        }


    $f[]="snmp_port 0";
    $f[]="acl all src all";
    $f[]="acl manager proto cache_object";
    $f[]="acl localhost src 127.0.0.1/32";
    $f[]="acl to_localhost dst 127.0.0.0/8 0.0.0.0/32";
    $f[]="acl SSL_ports port \"/etc/squid3/acls/SSLPorts\"";
    $f[]="acl Safe_ports port 80		# http";
    $f[]="acl Safe_ports port 21		# ftp";
    $f[]="acl Safe_ports port 443		# https";
    $f[]="acl Safe_ports port 70		# gopher";
    $f[]="acl Safe_ports port 210		# wais";
    $f[]="acl Safe_ports port 1025-65535	# unregistered ports";
    $f[]="acl Safe_ports port 280		# http-mgmt";
    $f[]="acl Safe_ports port 488		# gss-http";
    $f[]="acl Safe_ports port 591		# filemaker";
    $f[]="acl Safe_ports port 777		# multiling http";
    $f[]="acl CONNECT method CONNECT";
    $f[]="";
    $f[]="";
    $f[]="http_access allow manager localhost";
    $f[]="http_access deny manager";
    if($SquidAllowRemotePorts==0) {
        $f[] = "http_access deny !Safe_ports";
        $f[] = "http_access deny CONNECT !SSL_ports";
    }
    $f[]="http_access allow localnet";
    $f[]="http_access deny all";

    $f[]="icp_access allow localnet";
    $f[]="icp_access deny all";

    $f[]="cache_peer 127.0.0.1\tparent\t$ParentSquid27Port\t3130\tdefault";
    $f[]="never_direct allow all";
    $f[]="cache_mem 64 MB";
    $f[]="maximum_object_size_in_memory 64 KB";
    $f[]="memory_replacement_policy lru";


    $LOGFORMAT[]="%>a";
    $LOGFORMAT[]="%[ui";
    $LOGFORMAT[]="%[un";
    $LOGFORMAT[]="[%tl]";
    $LOGFORMAT[]="\"%rm %ru HTTP/%rv\"";
    $LOGFORMAT[]="%Hs";
    $LOGFORMAT[]="%<st";
    $LOGFORMAT[]="%Ss:";
    $LOGFORMAT[]="%Sh";
    $LOGFORMAT[]="UserAgent:\"%{User-Agent}>h\"";
    $LOGFORMAT[]="Forwarded:\"%{X-Forwarded-For}>h\"";

    $f[]="logformat common MAC:00:00:00:00:00:00 ".@implode(" ", $LOGFORMAT);

    $f[]="access_log none";
    $f[]="cache_store_log none";
    if($LogsWarninStop==0){
        $f[]="logfile_rotate 10";
    }
    if($LogsWarninStop==1){
        $f[]="logfile_rotate 0";
    }

    $f[]="# emulate_httpd_log off";
    $f[]="log_ip_on_direct on";
    $f[]="mime_table /etc/squid27/mime.conf";
    $f[]="# log_mime_hdrs off";
    $f[]="pid_filename /var/run/squid/squid-nat.pid";
    $f[]="debug_options ALL,1";
    $f[]="log_fqdn on";
    $f[]="client_netmask 255.255.255.255";
    $f[]="strip_query_terms off";
    $f[]="buffered_logs on";
    $f[]="netdb_filename /var/log/squid/netdb_nat.state";
    if($LogsWarninStop==0){
        $f[]="cache_log /var/log/squid/cache-nat.log";
    }
    if($LogsWarninStop==1){
        $f[]="cache_log /dev/null";
    }
    $f[]="#url_rewrite_program";
    $f[]="# url_rewrite_children 5";
    $f[]="# url_rewrite_concurrency 0";
    $f[]="# url_rewrite_host_header on";
    $f[]="refresh_pattern .		0	20%	4320";
    $f[]="cache_effective_user squid";
    $f[]="cache_effective_group squid";
    $f[]="httpd_suppress_version_string on";
    $f[]="visible_hostname $visible_hostname";
    $f[]="cache_dir null /tmp";
    $f[]="# icon_directory /usr/share/squid27/icons";
    $f[]="# error_directory /usr/share/squid27/errors/English";
    $f[]="forwarded_for on";
    $f[]="client_db on";
    $f[]="";

    CheckFilesAndSecurity();

    @file_put_contents("/etc/squid27/squid.conf", @implode("\n", $f));
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} /etc/squid27/squid.conf done\n";}


}

function start($nopid=false){
    $unix=new unix();

    $sock=new sockets();

    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    if(!$nopid){
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
            return;
        }
    }



    $pid=squid_27_pid();
    if($unix->process_exists($pid)){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already running since {$time}Mn...\n";}
        return;
    }

    $EnableTransparent27=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransparent27"));
    if($EnableTransparent27==0){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Disabled ( see EnableTransparent27 )...\n";}
        return;
    }
    $masterbin=$unix->find_program("squid27");
    if(!is_file($masterbin)){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Not installed...\n";}
        return;
    }

    CheckFilesAndSecurity();
    $squid_27_version=squid_27_version();
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service v$squid_27_version\n";}
    $cmd="$masterbin -f /etc/squid27/squid.conf -sD";
    shell_exec($cmd);

    $c=1;
    for($i=0;$i<10;$i++){
        sleep(1);
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service waiting $c/10\n";}
        $pid=squid_27_pid();
        if($unix->process_exists($pid)){
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Success PID $pid\n";}
            break;
        }
        $c++;
    }

    $pid=squid_27_pid();
    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Failed\n";}
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $cmd\n";}
    }

}

function CheckFilesAndSecurity(){
    $unix=new unix();
    $f[]="/var/spool/squid27";
    $f[]="/usr/share/squid27";
    foreach ($f as $val){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} checking \"$val\"\n";}
        if(!is_dir($val)){@mkdir($val,0755,true);}
        $unix->chown_func("squid","squid","$val/*");
    }

}



function stop(){

    $unix=new unix();

    $sock=new sockets();
    $masterbin=$unix->find_program("squid27");

    $SquidHotSpotPort=$sock->GET_INFO("SquidHotSpotPort");
    $ArticaHotSpotPort=$sock->GET_INFO("ArticaHotSpotPort");
    $ArticaSSLHotSpotPort=$sock->GET_INFO("ArticaSSLHotSpotPort");
    $ArticaSplashHotSpotPort=$sock->GET_INFO("ArticaSplashHotSpotPort");
    $ArticaSplashHotSpotPortSSL=$sock->GET_INFO("ArticaSplashHotSpotPortSSL");
    if(!is_numeric($ArticaHotSpotPort)){$ArticaHotSpotPort=0;}
    if(!is_numeric($ArticaSplashHotSpotPort)){$ArticaSplashHotSpotPort=16080;}
    if(!is_numeric($ArticaSplashHotSpotPortSSL)){$ArticaSplashHotSpotPortSSL=16443;}


    $pid=squid_27_pid();
    if(!is_file($masterbin)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Not installed\n";}
        return;

    }

    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already stopped...\n";}
        return;
    }

    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $kill=$unix->find_program("kill");





    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
    shell_exec("$masterbin -f /etc/squid27/squid.conf -k shutdown");
    for($i=0;$i<5;$i++){
        $pid=squid_27_pid();
        if(!$unix->process_exists($pid)){break;}
        shell_exec("$masterbin -f /etc/squid27/squid.conf -k shutdown");
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
        sleep(1);
    }

    $pid=squid_27_pid();
    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
        return;
    }

    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}

    shell_exec("$masterbin -f /etc/squid27/squid.conf -k kill");
    for($i=0;$i<5;$i++){
        $pid=squid_27_pid();
        if(!$unix->process_exists($pid)){break;}
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
        shell_exec("$masterbin -f /etc/squid27/squid.conf -k kill");
        sleep(1);
    }

    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success stopped...\n";}
        return;
    }

    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Killing processes\n";}


    for($i=0;$i<5;$i++){
        $pid=squid_27_pid();
        if(!$unix->process_exists($pid)){break;}
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} kill pid:$pid $i/5...\n";}
        $unix->KILL_PROCESS($pid,9);

    }
    $pid=squid_27_pid();

    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success stopped...\n";}
        return;
    }


    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}

}

function squid_27_version(){
    $unix=new unix();
    if(isset($GLOBALS["squid_27_version"])){return $GLOBALS["squid_27_version"];}
    $squidbin=$unix->find_program("squid27");
    if(!is_file($squidbin)){return "0.0.0";}
    exec("$squidbin -v 2>&1",$results);
    foreach ($results as $num=>$val){
        if(preg_match("#Squid Cache: Version\s+(.+)#", $val,$re)){
            $GLOBALS["squid_27_version"]=trim($re[1]);
            return $GLOBALS["squid_27_version"];
        }
    }
}

function squid_27_pid(){
    $unix=new unix();
    $pid=$unix->get_pid_from_file('/var/run/squid/squid-nat.pid');
    if($unix->process_exists($pid)){return $pid;}
    return $unix->PIDOF_PATTERN("squid.*?-f /etc/squid27/squid.conf");
}