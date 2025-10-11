#!/usr/bin/php -q
<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="IT Charter Daemon";
$GLOBALS["PROGRESS"]=false;
$GLOBALS["MIGRATION"]=false;
$GLOBALS["OUTPUT"]=true;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;
$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--migration#",implode(" ",$argv),$re)){$GLOBALS["MIGRATION"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--build"){build_rules();exit;}
if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--monit"){build_monit();exit;}

function build_rules(){

	$unix=new unix();
	build_progress("{IT_charter}",25);
    build_progress("{IT_charter}",30);
    build_syslog();

    build_progress("{IT_charter}",35);
    $unix->framework_exec("/usr/sbin/artica-phpfpm-service -install-weberror");

	build_progress("{configuring}",70);
	squid_admin_mysql(2, "{reloading_proxy_service} (itCharts)", null,__FILE__,__LINE__);
	build_progress("{IT_charter} {reload_proxy_service}",75);
    if(is_file("/etc/init.d/theshields")) {
        system("/etc/init.d/theshields restart");
    }
    if(!is_dir("/home/artica/ITCharters")){
        @mkdir("/home/artica/ITCharters",0755,true);
    }
    $f=scandir("/home/artica/ITCharters");
    foreach ($f as $fname){
        if($fname=="."){continue;}if($fname==".."){continue;}
        @unlink("/home/artica/ITCharters/$fname");
    }

    build_progress("{IT_charter}",80);
    CheckRedirectUrl();


    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $results=$q->QUERY_SQL("SELECT ID,PdfContent FROM itcharters");
    foreach ($results as $index=>$ligne){
        $PdfContent=$ligne["PdfContent"];
        $ID=intval($ligne["ID"]);
        if(strlen($PdfContent)==0){continue;}
        _out("Saving PDF content to /home/artica/ITCharters/$ID");
        @file_put_contents("/home/artica/ITCharters/$ID",base64_decode($PdfContent));
    }
    _out("Reload Proxy service");
    build_progress("{IT_charter} {reload_proxy_service}",90);
    system("/usr/sbin/artica-phpfpm-service -reload-proxy");
	build_progress("{IT_charter} {done}",100);
	
	
	
}
function CheckRedirectUrl():bool{

    $Redirect=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartRedirectURL"));
    if(!preg_match("#^(http|https):\/\/#",$Redirect)){
        $Redirect="http://$Redirect";
    }
    $xproto="http";
    $WebErrorPageListenPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPPort"));
    if($WebErrorPageListenPort ==0){
        $WebErrorPageListenPort=9025;
    }
    $UfdbUseInternalServiceEnableSSL =  intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceEnableSSL"));
    $UfdbUseInternalServiceHTTPSPort =  intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceHTTPSPort"));
    if ($UfdbUseInternalServiceHTTPSPort == 0 ){        $UfdbUseInternalServiceHTTPSPort = 9026;}
    if($UfdbUseInternalServiceEnableSSL==1){
        $WebErrorPageListenPort=$UfdbUseInternalServiceHTTPSPort;
        $xproto="https";
    }

    $parse=parse_url($Redirect);
    $host=$parse["host"];

    if(isset($parse["port"])) {
       return _out("Redirect to host=$host port={$parse["port"]}");
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ITChartRedirectURL","$xproto://$host:$WebErrorPageListenPort");
    return _out("Redirect to host=$xproto://$host:$WebErrorPageListenPort");

}
function _out($text):bool{
    echo "Service.......: ".date("H:i:s")." [INIT]: ItCharter $text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("ItCharter", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, "[CONF]: $text");
    closelog();
    return true;
}

function build_syslog():bool{
    $fname="/etc/rsyslog.d/ITCharter.conf";
    $md51=null;
    if(is_file($fname)){
        $md51=md5_file($fname);
    }
    $f[]="if  (\$programname =='ItCharter') then {";
    $f[]=buildlocalsyslogfile("-/var/log/ITCharter.log");
    $f[]="\t& stop";
    $f[]="}\n";

    @file_put_contents("/etc/rsyslog.d/ITCharter.conf",@implode("\n",$f));
    $md52=md5_file($fname);
    if($md51==$md52){return true;}
    _out("Reloading syslog engine");
    $unix=new unix();$unix->RESTART_SYSLOG(true);
    return true;

}

function build_monit(){


    $f[]="check process APP_ITCHARTER with pidfile \"/var/run/itcharter.pid\"";
    $f[]="\tstart program = \"/etc/init.d/itcharter start\"";
    $f[]="\tstop program = \"/etc/init.d/itcharter stop\"";
    $f[]="\tif failed host 127.0.0.1 port 6123 protocol redis then restart";
    $f[]="\tif 5 restarts within 5 cycles then timeout\n";

    @file_put_contents("/etc/monit/conf.d/APP_ITCHARTER.monitrc", @implode("\n", $f));
    if(!is_file("/etc/monit/conf.d/APP_ITCHARTER.monitrc")){
        echo "/etc/monit/conf.d/APP_ITCHARTER.monitrc failed !!!\n";
    }
    echo "IT Charter: [INFO] Writing /etc/monit/conf.d/APP_ITCHARTER.monitrc\n";
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");

}

function create_init(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/itcharter";
    $php5script=basename(__FILE__);
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         itcharter";
    $f[]="# Required-Start:    \$local_fs \$syslog \$network";
    $f[]="# Required-Stop:     \$local_fs \$syslog \$network";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: itcharter";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: itcharter";
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
    $f[]=" reload)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
    $f[]="    ;;";

    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";

    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: building $INITD_PATH done...\n";}

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

function build_config(){
    $unix=new unix();
    if(!is_dir("/home/itcharter/database")){@mkdir("/home/itcharter/database",0755,true);}
    $PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
    $ITChartListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartListenInterface"));
    $ITChartDatabaseSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ITChartDatabaseSize"));
    if($ITChartListenInterface==null){$ITChartListenInterface="lo";}
    if($ITChartDatabaseSize==0){$ITChartDatabaseSize=50;}

    $redis=new Redis();
    try {
        $redis->connect('127.0.0.1','6123');
        $ClusterEnabled=intval($redis->get("ITChartClusterEnabled"));
        $ClusterMaster=trim($redis->get("ITChartClusterMaster"));
        $redis->close();
    } catch (Exception $e) {
        echo $e->getMessage()."\n";
    }



    if($PowerDNSEnableClusterMaster==1){
        if($ITChartListenInterface=="lo"){$ITChartListenInterface=null;}
    }

    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));

    if($PowerDNSEnableClusterSlave==1){
        $ClusterEnabled=1;
        $ClusterMaster=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterAddress"));
    }



        $f[]="# it in the usual form of 1k 5GB 4M and so forth:";
    $f[]="#";
    $f[]="# 1k => 1000 bytes";
    $f[]="# 1kb => 1024 bytes";
    $f[]="# 1m => 1000000 bytes";
    $f[]="# 1mb => 1024*1024 bytes";
    $f[]="# 1g => 1000000000 bytes";
    $f[]="# 1gb => 1024*1024*1024 bytes";
    $f[]="#";
    $f[]="# units are case insensitive so 1GB 1Gb 1gB are all the same.";
    $f[]="";
    $IpAddr[]="127.0.0.1";
    if($ITChartListenInterface<>null) {
       $ipaddr=$unix->InterfaceToIPv4();
       if($ipaddr<>null){
           if($ipaddr<>"127.0.0.1"){
           $IpAddr[]="$ipaddr";
           }
       }
    }
    $f[] = "bind ".@implode(" ", $IpAddr);
    $f[]="protected-mode no";
    $f[]="port 6123";
    $f[]="tcp-backlog 511";
    $f[]="unixsocket /var/run/itcharter.sock";
    $f[]="unixsocketperm 777";
    $f[]="timeout 5";
    $f[]="tcp-keepalive 300";
    $f[]="daemonize yes";
    $f[]="supervised no";
    $f[]="pidfile /var/run/itcharter.pid";
    $f[]="loglevel notice";
    $f[]="logfile ''";
    $f[]="syslog-enabled yes";
    $f[]="# Specify the syslog identity.";
    $f[]="syslog-ident ItCharter";
    $f[]="syslog-facility local0";
    $f[]="databases 16";
    $f[]="save 900 1";
    $f[]="save 300 10";
    $f[]="save 60 10000";
    $f[]="stop-writes-on-bgsave-error yes";
    $f[]="rdbcompression yes";
    $f[]="rdbchecksum yes";
    $f[]="dbfilename dump.itcharter";
    $f[]="dir /home/itcharter/database";
    if($ClusterEnabled==1) {
        $f[]="replica-read-only yes";
        $f[] = "replicaof $ClusterMaster 6123";
    }
    $f[]="# masterauth <master-password>";
    $f[]="# replica-serve-stale-data yes";
    $f[]="repl-diskless-sync no";
    $f[]="repl-diskless-sync-delay 5";
    $f[]="# repl-ping-replica-period 10";
    $f[]="repl-disable-tcp-nodelay no";
    $f[]="# repl-backlog-size 1mb";
    $f[]="# repl-backlog-ttl 3600";
    $f[]="# replica-priority 100";
    $f[]="# min-replicas-to-write 3";
    $f[]="# min-replicas-max-lag 10";
    $f[]="# min-replicas-max-lag is set to 10.";
    $f[]="# replica-announce-ip 5.5.5.5";
    $f[]="# replica-announce-port 1234";
    $f[]="# requirepass foobared";
    $f[]="maxclients 500";
    $f[]="maxmemory {$ITChartDatabaseSize}mb";
    $f[]="maxmemory-policy allkeys-lru";
    $f[]="maxmemory-samples 5";
    $f[]="# replica-ignore-maxmemory yes";
    $f[]="# lazyfree-lazy-eviction no";
    $f[]="# lazyfree-lazy-expire no";
    $f[]="# lazyfree-lazy-server-del no";
    $f[]="# replica-lazy-flush no";
    $f[]="appendonly no";
    $f[]="appendfilename \"appendonly.aof\"";
    $f[]="appendfsync everysec";
    $f[]="# appendfsync no";
    $f[]="no-appendfsync-on-rewrite no";
    $f[]="auto-aof-rewrite-percentage 100";
    $f[]="auto-aof-rewrite-min-size 64mb";
    $f[]="aof-load-truncated yes";
    $f[]="# aof-use-rdb-preamble yes";
    $f[]="lua-time-limit 5000";
    $f[]="";
    $f[]="# cluster-enabled yes";
    $f[]="# cluster-config-file nodes-6379.conf";
    $f[]="# cluster-node-timeout 15000";
    $f[]="# cluster-replica-validity-factor 10";
    $f[]="# cluster-migration-barrier 1";
    $f[]="# cluster-require-full-coverage yes";
    $f[]="# cluster-replica-no-failover no";
    $f[]="";
    $f[]="# In order to setup your cluster make sure to read the documentation";
    $f[]="# available at http://redis.io web site.";
    $f[]="# * cluster-announce-ip";
    $f[]="# * cluster-announce-port";
    $f[]="# * cluster-announce-bus-port";
    $f[]="# cluster-announce-ip 10.1.1.5";
    $f[]="# cluster-announce-port 6379";
    $f[]="# cluster-announce-bus-port 6380";
    $f[]="slowlog-log-slower-than 10000";
    $f[]="slowlog-max-len 128";
    $f[]="latency-monitor-threshold 0";
    $f[]="notify-keyspace-events \"\"";
    $f[]="list-max-ziplist-size -2";
    $f[]="list-compress-depth 0";
    $f[]="set-max-intset-entries 512";
    $f[]="zset-max-ziplist-entries 128";
    $f[]="zset-max-ziplist-value 64";
    $f[]="hll-sparse-max-bytes 3000";
    $f[]="# stream-node-max-bytes 4096";
    $f[]="# stream-node-max-entries 100";
    $f[]="activerehashing yes";
    $f[]="client-output-buffer-limit normal 0 0 0";
    $f[]="# client-output-buffer-limit replica 256mb 64mb 60";
    $f[]="client-output-buffer-limit pubsub 32mb 8mb 60";
    $f[]="# client-query-buffer-limit 1gb";
    $f[]="# proto-max-bulk-len 512mb";
    $f[]="hz 10";
    $f[]="# dynamic-hz yes";
    $f[]="aof-rewrite-incremental-fsync yes";
    $f[]="# rdb-save-incremental-fsync yes";
    $f[]="#   redis-benchmark -n 1000000 incr foo";
    $f[]="#   redis-cli object freq foo";
    $f[]="# lfu-log-factor 10";
    $f[]="# lfu-decay-time 1";
    $f[]="# activedefrag yes";
    $f[]="# active-defrag-ignore-bytes 100mb";
    $f[]="# active-defrag-threshold-lower 10";
    $f[]="# active-defrag-threshold-upper 100";
    $f[]="# active-defrag-cycle-min 5";
    $f[]="# active-defrag-cycle-max 75";
    $f[]="# active-defrag-max-scan-fields 1000";
    $f[]="";
    $f[]="";

    @file_put_contents("/etc/itchart.conf",@implode("\n",$f));

}

function restart(){

    build_progress_restart("{stopping}",10);
    if(!stop(true)){
        build_progress_restart("{stopping} {failed}",110);
        return;
    }
    build_progress_restart("{starting}",50);
    sleep(1);
    if(!start(true)){
        build_progress_restart("{starting} {failed}",110);
        return;
    }

    build_progress_restart("{starting} {success}",100);



}

function start($aspid=false){
    if(!is_dir("/home/itcharter/database")){@mkdir("/home/itcharter/database",0755,true);}
    $unix=new unix();

    $Masterbin=$unix->find_program("redis-server");
    if(!is_file($Masterbin)){
        if(is_file("/usr/sbin/itcharter")){
            @copy("/usr/sbin/itcharter","/usr/bin/redis-server");
            $Masterbin="/usr/bin/redis-server";
        }
    }

    if(!is_file($Masterbin)){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, redis-server not installed\n";}
        return false;
    }

    if(!$aspid){
       $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();

    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
        return true;
    }

    if(is_file("/usr/sbin/itcharter")){
        $md51=md5_file("/usr/sbin/itcharter");
        $md52=md5_file($Masterbin);
        if($md51<>$md52){@unlink("/usr/sbin/itcharter");}
    }
    if(!is_file("/usr/sbin/itcharter")){@copy($Masterbin,"/usr/sbin/itcharter");}
    @chmod("/usr/sbin/itcharter",0755);
    build_config();

    $cmdline="/usr/sbin/itcharter /etc/itchart.conf";
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
    shell_exec($cmdline);

    for($i=1;$i<5;$i++){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
        sleep(1);
        $pid=PID_NUM();
        if($unix->process_exists($pid)){break;}
    }

    $pid=PID_NUM();
    if($unix->process_exists($pid)) {
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";
        }
        return true;
    }

    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmdline\n";}

    return false;

}


function install(){
	$unix       = new unix();
    $chattr     = $unix->find_program("chattr");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableITChart", 1);
	build_progress(25, "{installing}");


    if(is_file("/usr/share/artica-postfix/ressources/itchartclass.py")){@unlink("/usr/share/artica-postfix/ressources/itchartclass.py");}

    if(!is_file("/etc/init.d/web-error-page")){
        $unix->framework_exec("/usr/sbin/artica-phpfpm-service -install-weberror");
    }
    if(is_file("/etc/squid3/ITCharter.conf")) {
        shell_exec("$chattr -i /etc/squid3/ITCharter.conf");
        @file_put_contents("/etc/squid3/ITCharter.conf", "\n");
    }

    build_progress(30, "{configuring}");
    create_init();
    build_progress(35, "{configuring}");
    build_monit();
	build_progress(40, "{configuring}");
    build_progress(70, "{reconfiguring}");
    build_syslog();
    build_progress(80, "{reconfiguring}");
	squid_admin_mysql(2, "{reloading_proxy_service} (itCharts)", null,__FILE__,__LINE__);
	build_progress("{IT_charter} {reload_proxy_service}",90);

    if(is_file("/etc/init.d/theshields")) {
        system("/etc/init.d/theshields restart");
    }
    if(is_file("/etc/init.d/squid")) {
        build_progress("{IT_charter} {reload_proxy_service}", 95);
        system("/usr/sbin/artica-phpfpm-service -reload-proxy");
    }
	build_progress("{IT_charter} {done}",100);
}
function uninstall(){
	$unix           = new unix();
	$SQUID_BIN      = $unix->LOCATE_SQUID_BIN();
    $chattr         = $unix->find_program("chattr");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableITChart", 0);
	build_progress(25, "{uninstalling}");
	remove_service("/etc/init.d/itcharter");

    build_progress(30, "{uninstalling}");
	if(is_file("/etc/monit/conf.d/APP_ITCHARTER.monitrc")){
        @unlink("/etc/monit/conf.d/APP_ITCHARTER.monitrc");
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    }
    build_progress(40, "{uninstalling}");
    if(is_file("/etc/rsyslog.d/ITCharter.conf")){
        @unlink("/etc/rsyslog.d/ITCharter.conf");
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }

    build_progress(50, "{uninstalling}");


    if(is_file("/etc/squid3/ITCharter.conf")) {
        shell_exec("$chattr -i /etc/squid3/ITCharter.conf");
        @file_put_contents("/etc/squid3/ITCharter.conf", "\n");
    }

    squid_admin_mysql(2, "{reloading_proxy_service} (itCharts)", null,__FILE__,__LINE__);
	build_progress("{IT_charter} {reload_proxy_service}",90);
    system("/usr/sbin/artica-phpfpm-service -reload-proxy");
	build_progress("{IT_charter} {done}",100);
	
}



function isAuthenticated(){

    $f=explode("\n",@file_get_contents("/etc/squid3/authenticate.conf"));
    foreach ($f as $line){

        if(preg_match("#acl.*?AUTHENTICATED.*?REQUIRED#",$line)){return true;}
    }
    return false;
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

function PID_NUM(){

    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/itcharter.pid");
    if($unix->process_exists($pid)){return $pid;}
    return $unix->PIDOF("/usr/sbin/itcharter");

}

function build_progress_restart($text,$pourc){
    if(is_numeric($text)){$int=$text;$text=$pourc;$pourc=$int;}
    $cachefile=PROGRESS_DIR."/ichart.restart.progress";
    echo "{$pourc}% $text\n";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}
function build_progress($text,$pourc){
	if(is_numeric($text)){$int=$text;$text=$pourc;$pourc=$int;}
	$cachefile=PROGRESS_DIR."/ichart.progress";
	echo "{$pourc}% $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function stop($aspid=false){
    $unix=new unix();
    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();


    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
        return true;
    }
    $pid=PID_NUM();

    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
    unix_system_kill($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
        sleep(1);
    }

    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
        return true;
    }

    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
        sleep(1);
    }

    if($unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
        return false;
    }

    return true;

}
?>