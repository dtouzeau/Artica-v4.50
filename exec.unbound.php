<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["WIZARD"]=false;
$GLOBALS["MONIT"]=false;
$GLOBALS["DEBUG_SERVICE"]=false;
$GLOBALS["RELOADONLY"]=false;$GLOBALS["PREBUILD"]=false;
$GLOBALS["TITLENAME"]="HyperCache DNS Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--wizard#",implode(" ",$argv),$re)){$GLOBALS["WIZARD"]=true;}
if(preg_match("#--reload-only#",implode(" ",$argv),$re)){$GLOBALS["RELOADONLY"]=true;}
if(preg_match("#--monit#",implode(" ",$argv),$re)){$GLOBALS["MONIT"]=true;}
if(preg_match("#--debug#",implode(" ",$argv),$re)){$GLOBALS["DEBUG_SERVICE"]=true;}

include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__)."/ressources/externals/Net/DNS2.inc");
include_once(dirname(__FILE__)."/ressources/class.unbound.certificates.inc");
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.powerdns.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');

$GLOBALS["ARGVS"]=implode(" ",$argv);

if($argv[1]=="--binver"){echo unbound_version_bin()."\n";exit;}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){restart();exit();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
if($argv[1]=="--blacklists-enable"){$GLOBALS["OUTPUT"]=true;blacklists_enable();exit();}
if($argv[1]=="--blacklists-disable"){$GLOBALS["OUTPUT"]=true;blacklists_disable();exit();}
if($argv[1]=="--blacklists-download"){$GLOBALS["OUTPUT"]=true;blacklists_download();exit();}
if($argv[1]=="--StevenBlack"){$GLOBALS["OUTPUT"]=true;StevenBlack();exit();}
if($argv[1]=="--uninstall-dns"){uninstall_dns();exit;}
if($argv[1]=="--install-dns"){install_dns();exit;}
if($argv[1]=="--snmp"){SNMP();exit;}
if($argv[1]=="--install-redis"){install_redis();exit;}
if($argv[1]=="--uninstall-redis"){uninstall_redis();exit;}
if($argv[1]=="--start-redis"){start_redis_service();exit;}
if($argv[1]=="--stop-redis"){stop_redis_service();exit;}
if($argv[1]=="--restart-redis"){restart_redis_service();exit;}
if($argv[1]=="--watchdog"){unbound_watchdog();exit;}
if($argv[1]=="--redis-remove"){remove_redis_database();exit;}
if($argv[1]=="--install-firewall"){install_firewall();exit;}
if($argv[1]=="--uninstall-firewall"){uninstall_firewall();exit;}
if($argv[1]=="--syslog"){build_syslog();exit;}
if($argv[1]=="--destroy-log"){destroy_log();}
if($argv[1]=="--monit"){exit;}


function _out($text):bool{
    $date=date("H:i:s");
    $STAT="INIT";
    if(preg_match("#^\[(.+?)\]\s+(.+)#",$text,$re)){$STAT=$re[1];$text=$re[2];}
    echo "$STAT......: $date DNS Cache service: $text\n";
    if(!function_exists("openlog")){return true;}
    openlog("unbound", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}

function reload(): bool{
    _out("[Reloading] Building configuration");
    build();
    $unix=new unix();
    $unboundcontrol=$unix->find_program("unbound-control");
    _out("[Reloading] Service");
    system("$unboundcontrol -c /etc/unbound/unbound.conf reload");
    $unix->go_exec("exec.squid.global.access.php --limits");
    return true;
}

function firewall_progress($text,$prc){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"dnsfw.install.progress");
}
function syslog_pprogress($text,$prc){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"dns.syslog.progress");

}

function rsyslog_compliance():bool{
    $MAJOR=0;
    $MINOR=0;
    exec("/usr/sbin/rsyslogd -v 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if(preg_match("#^rsyslogd\s+([0-9\.]+)#",$line,$re)){
            $tb=explode(".",$re[1]);
            $MAJOR=intval($tb[0]);
            $MINOR=intval($tb[1]);
        }
    }
    _out("Syslog Daemon version: Major:$MAJOR Minor:$MINOR");
    if($MAJOR<8){return false;}
    if($MINOR<2208){return false;}
    return true;

}



function build_syslog_statscom($prefix):array{

    $UnboundStatsCom=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsCom"));
    if($UnboundStatsCom==0){
        _out("Statistics Appliance: $UnboundStatsCom");
        return array("","");
    }

    if(!rsyslog_compliance()){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnboundStatsCom",0);
        return array("","");
    }

    $unix=new unix();
    $uuid=$unix->GetUniqueID();
    $hostname=$unix->hostname_g();
    $tplname=strtolower($prefix);
    $tplname=str_replace("-","",$tplname);

    $h=array();
    if(!is_file("/etc/rsyslog.d/00_mmjsonparse.conf")){
        @file_put_contents("/etc/rsyslog.d/00_mmjsonparse.conf","\nmodule(load=\"mmjsonparse\")\n");
    }


    $h[]="set $!zuuid = \"$uuid\";";
    $h[]="set $!tnow = $\$now-unixtimestamp;";
    $h[]="set $!myhost =\"$hostname\";";
    $h[]="set $!pointer =\"$prefix\";";
    $h[]="template(name=\"$tplname\" type=\"list\" option.jsonf=\"on\") {";
    $h[]="\tproperty(outname=\"pointer\" name=\"$!pointer\" format=\"jsonf\")";
    $h[]="\tproperty(outname=\"host\" name=\"$!myhost\" format=\"jsonf\")";
    $h[]="\tproperty(outname=\"syslog-tag\" name=\"syslogtag\" format=\"jsonf\")";
    $h[]="\tproperty(outname=\"message\" name=\"msg\" format=\"jsonf\")";
    $h[]="property(outname=\"uuid\" name=\"$!zuuid\" format=\"jsonf\" datatype=\"string\" onEmpty=\"null\")";
    $h[]="property(outname=\"now\" name=\"$!tnow\" format=\"jsonf\" datatype=\"string\" onEmpty=\"null\")";
    $h[]="}";

    $EnableStatsCommunicator=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatsCommunicator"));
    if($EnableStatsCommunicator==1) {
        $statscom = "\taction(type=\"omfwd\" Target=\"127.0.0.1\" Port=\"1899\" Protocol=\"tcp\" Template=\"$tplname\" queue.type=\"direct\" )";
        return array(@implode("\n",$h),$statscom);
    }


    $UnboundStatsComAddress = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsComAddress"));
    $UnboundStatsComPort = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsComPort"));
    $UnboundStatsComTCP = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsComTCP"));
    $UnboundStatsComUseSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsComUseSSL"));
    $UnboundStatsComCertificate = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsComCertificate"));
    if($UnboundStatsComAddress==null){
        $UnboundStatsComAddress="127.0.0.1";
        $UnboundStatsComPort=1899;
        $UnboundStatsComTCP=1;
    }
    if ($UnboundStatsComPort == 0) {
        $UnboundStatsComPort = 514;
    }
    $proto = "udp";
    if ($UnboundStatsComTCP == 1) {
        $proto = "tcp";
    }

    $statscom = "\taction(type=\"omfwd\" Target=\"$UnboundStatsComAddress\" Port=\"$UnboundStatsComPort\" Template=\"$tplname\" Protocol=\"$proto\" queue.type=\"direct\" compression.Mode=\"single\")";

    return array(@implode("\n",$h),$statscom);

}

function build_syslog(){
    syslog_pprogress(50,"{building_configuration}");
    syslog_unbound();
    syslog_pprogress(60,"{building_configuration}");

    syslog_pprogress(65,"{building_configuration}");

    $tfile      = "/etc/rsyslog.d/dns-firewall.conf";
    $oldmd      = crc32_file($tfile);
    echo "$tfile [$oldmd]\n";
    $statscom=null;
    $add_rules = BuildRemoteSyslogs("dnsfw","dns-firewall");

    $h=array();
    $stats=build_syslog_statscom("DNS-FW-TRAP-1");

    if($stats[0]<>null){
        $h[]=$stats[0];
    }

    $h[]="";
    $h[]="if  (\$programname =='dns-firewall') then {";
    if($stats[1]<>null) {$h[] = $stats[1]; }
    if(strlen($add_rules)>3) {$h[] = $add_rules;}
    $h[] ="\t-/var/log/dns-firewall.log";
    $h[] ="\t&stop";
    $h[]="\t}";
    $h[]="";
    @file_put_contents($tfile,@implode("\n",$h));
    $newmd      = crc32_file($tfile);
    echo "$tfile [OK]\n";
    syslog_pprogress(80,"{building_configuration}");
    if($oldmd<>$newmd){
        syslog_pprogress(90,"{reloading}");
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }
    syslog_pprogress(100,"{success}");
}



function syslog_unbound(){
    $UnboundLogSyslogDoNotStorelogsLocally=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundLogSyslogDoNotStorelogsLocally"));

    $h=array();
    $stats=build_syslog_statscom("DNS-EVT-TRAP-1");
    if($stats[0]<>null){
        $h[]=$stats[0];
    }
    
    $destdfile="/etc/rsyslog.d/unbound.conf";
    echo "Building $destdfile\n";
    $md5=crc32_file($destdfile);
    $h[]="if  (\$programname =='unbound') then {";
    if($stats[1]<>null) {$h[] = $stats[1]; }
    $remote_unbound=BuildRemoteSyslogs("unbound","unbound");
    if($UnboundLogSyslogDoNotStorelogsLocally==0) {
        $h[] = "\t-/var/log/unbound.log";
    }
    if($remote_unbound<>null){ $h[] = $remote_unbound; }
    $h[]="\tstop";
    $h[]="}";
    $h[]="";
    @file_put_contents($destdfile,@implode("\n", $h));
    $md52=crc32_file($destdfile);
    if($md5==$md52){return true;}
    echo "Building $destdfile DONE\n";
    $unix=new unix();$unix->RESTART_SYSLOG(true);
    return true;
}

function install_firewall(){
    firewall_progress("{installing}",50);
    $unix=new unix();
    if(!$unix->CORP_LICENSE()){
        firewall_progress("{ERROR_NO_LICENSE}",110);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSFirewall",0);
        restart();
        build_syslog();
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSFirewall",1);
    restart();
    build_syslog();
    firewall_progress("{installing} {success}",100);
    return true;
}
function uninstall_firewall(){
    firewall_progress("{uninstall}",50);
    if(is_file("/etc/unbound/artica-rpz.local")){@unlink("/etc/unbound/artica-rpz.local");}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSFirewall",0);
    restart();
    firewall_progress("{uninstall} {success}",100);

}

function SNMP(){
    $unix=new unix();
    $monit=$unix->find_program("monit");

    exec("$monit -B -c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state status APP_UNBOUND 2>&1",$results1);

    $Running=0;
    $memory=0;
    $uptime=0;
    $cpu=0;
    foreach ($results1 as $line){
        $line=trim($line);
        if(preg_match("#^status\s+(.+?)$#",$line,$re)){
            if(strtolower(trim($re[1]))=="running"){
                $Running=1;
            }
            continue;
        }
        if(preg_match("#^memory\s+.*?\[(.*?)\]#i",$line,$re)){
            $memory=str_replace(" ","",$re[1]);
            continue;
        }
        if(preg_match("#^uptime\s+(.+?)$#",$line,$re)){
            $uptime=uptime2Time($re[1]);
            continue;
        }
        if(preg_match("#^cpu\s+([0-9\.]+)%$#",$line,$re)){
            $cpu=$re[1];
        }

    }

    $fline="running:$Running memory:$memory uptime:$uptime cpu:$cpu";
    @file_put_contents("/etc/unbound/statistics/unbound_status",$fline);
    @file_put_contents("/etc/unbound/statistics/dnsfilterd_status","running:0 memory:0 uptime:0 cpu:0");

    if(!is_file("/etc/init.d/dnsfilterd")){return;}
    exec("$monit -B -c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state status APP_DNSFILTERD",$results);

    $Running=0;
    $memory=0;
    $uptime=0;
    $cpu=0;
    foreach ($results as $line){
        $line=trim($line);
        if(preg_match("#^status\s+(.+?)$#",$line,$re)){
            if(strtolower($re[1])=="running"){
                $Running=1;
            }
            continue;
        }
        if(preg_match("#^memory\s+.*?\[(.*?)\]#i",$line,$re)){
            $memory=str_replace(" ","",$re[1]);
            continue;
        }
        if(preg_match("#^uptime\s+(.+?)$#",$line,$re)){
            $uptime=uptime2Time($re[1]);
            continue;
        }
        if(preg_match("#^cpu\s+([0-9\.]+)%$#",$line,$re)){
            $cpu=$re[1];
        }

    }

    $fline="running:$Running memory:$memory uptime:$uptime cpu:$cpu";
    @file_put_contents("/etc/unbound/statistics/dnsfilterd_status",$fline);
}

function uptime2Time($string){

    $H=array();
    if(preg_match("#([0-9]+)d#",$string,$re)){
        $hours=intval($re[1])*24;
        $minutes=$hours*60;
        $seconds=$minutes*60;
        $H[]=$seconds;
        $string=str_replace("{$re[1]}d","",$string);

    }
    if(preg_match("#([0-9]+)h#",$string,$re)){
        $minutes=intval($re[1])*60;
        $seconds=$minutes*60;
        $H[]=$seconds;
        $string=str_replace("{$re[1]}h","",$string);

    }
    if(preg_match("#([0-9]+)m#",$string,$re)){
        $seconds=intval($re[1])*60;
        $H[]=$seconds;
        $string=str_replace("{$re[1]}m","",$string);
    }
    if(preg_match("#([0-9]+)s#",$string,$re)){
        $seconds=intval($re[1]);
        $H[]=$seconds;

    }

    $t=0;
    foreach ($H as $number){
        $t=$t+$number;

    }
    return $t;
}



function destroy_log(){
    $unix=new unix();
    $filelog="/var/log/unbound.log";
    $filelog_size=@filesize($filelog);
    $filelog_text=$unix->FormatBytes($filelog_size/1024);
    squid_admin_mysql(0,"{APP_UNBOUND} {logfile_exceed_rule} ($filelog_text), remove it");
    @unlink($filelog);
    @touch($filelog);
    restart();
    $unix=new unix();$unix->RESTART_SYSLOG(true);

}
function restart():bool {
    system("/usr/sbin/artica-phpfpm-service -restart-unbound");
    return true;
}
function install():bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();


    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/apache-htcacheclean.service.dsh-also";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/apache2.service.dsh-also";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/multi-user.target.wants/apache2.service";

    foreach ($ToKillSys as $fname){
        if(is_file($fname)){@unlink($fname);}
    }
    system("/usr/sbin/artica-phpfpm-service -install-unbound");
    return true;
}
function start($aspid=false): bool{
    $unix=new unix();
    $unix->go_exec("/usr/sbin/artica-phpfpm-service -start-unbound");
    return true;
}
function valid_hostname($pattern):bool{
    if(!preg_match("#^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$#",$pattern)){return false;}
    return true;
}

function fix_unbound($tmpfile): bool{

    $f=explode("\n", @file_get_contents($tmpfile));
    @unlink($tmpfile);
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}

        if(preg_match("#Cannot assign requested address for ([0-9\.]+)#", $line,$re)){
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {$re[1]} bad address...\n";}
            build();
            return micro_start();

        }

        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $line\n";}

    }
    return false;
}

function micro_start():bool{
    $unix=new unix();
    $Masterbin=$unix->find_program("unbound");
    $cmd="$Masterbin -c /etc/unbound/unbound.conf >/dev/null 2>&1 &";
    shell_exec($cmd);
    for($i=1;$i<5;$i++){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
        sleep(1);
        $pid=PID_NUM();
        if($unix->process_exists($pid)){return true;}
    }
    return false;
}


function build_progress($pourc,$text): bool{
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/unbound.install.php";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    if($GLOBALS["WIZARD"]){
        $array["POURC"]=63;
        $array["TEXT"]=$text;
        @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/wizard.progress", serialize($array));
    }


    @chmod($GLOBALS["CACHEFILE"],0755);
    return true;
}

function blacklists_disable(){
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableUnboundBlackLists", 0);
    build_progress(25, "{disable_feature}");

    build();
    build_progress(50, "{building_configuration}");
    reload();
    build_progress(100, "{success}");
}

function blacklists_enable(){
    $cachefile="/home/artica/squid/pgl.yoyo.org/cache";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableUnboundBlackLists", 1);
    @unlink($cachefile);
    build_progress(25, "{downloading} {database}");
    if(!blacklists_download()){
        build_progress(110, "{failed}");
        return;
    }
    build_progress(50, "{building_configuration}");
    build();
    build_progress(100, "{success}");
}

function unbound_watchdog_interface(){
    $INT=array();
    $f=explode("\n",@file_get_contents("/etc/unbound/unbound.conf"));
    foreach ($f as $line){
        $line=trim($line);
        if(preg_match("#^interface:\s+([0-9\.]+)$#",$line,$re)){
            echo "unbound_watchdog:: Interface {$re[1]}\n";
            $INT[$re[1]]=$re[1];
        }
    }

    $ListenInterface=null;
    foreach ($INT as $ipaddr=>$none){
        if($ipaddr=="127.0.0.1"){continue;}
        if($ipaddr==null){continue;}
        $ListenInterface=$ipaddr;
        break;
    }

    if($ListenInterface==null){
        echo "unbound_watchdog:: Interface not found\n";
        return "127.0.0.1";
    }
    echo "unbound_watchdog:: Interface = $ListenInterface\n";
    return $ListenInterface;
}

function unbound_watchdog():bool{
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pid=$unix->get_pid_from_file($pidfile);

    if($unix->process_exists($pid)){
        echo "Another process currently running PID $pid\n";
        die();
    }
    @file_put_contents($pidfile,getmypid());


    $results=array();

    $unbound_control=$unix->find_program("unbound-control");

    if(is_file($unbound_control)){
        exec("$unbound_control status 2>&1",$results);
        $RUN=false;
        foreach ($results as $line){
            if(preg_match("#is running#",$line)){
                echo "unbound_watchdog:: OK, Unbound is running\n";
                $unix->ToSyslog("OK DNS service cache is UP","unbound-watchdog");
                $RUN=true;
                break;
            }

        }

        if(!$RUN){
            squid_admin_mysql(0,"{APP_UNBOUND} did not respond [ {action} = {restart} ]",
                @implode("\n",$results),__FILE__,__LINE__);
            $unix->ToSyslog("FATAL DNS service cache is DOWN","unbound-watchdog");
            shell_exec("/usr/sbin/artica-phpfpm-service -restart-unbound");
            return false;

        }

    }
    $UnBoundWatchdogIPsTxt=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundWatchdogIPs");
    if($UnBoundWatchdogIPsTxt==null){
        $UnBoundWatchdogIPsTxt="crawl-66-249-66-*.googlebot.com,crawl-66-249-65-*.googlebot.com";
    }
    $ListenInterface=unbound_watchdog_interface();

    $confs=explode(",",$UnBoundWatchdogIPsTxt);
    $rrand=rand(0,count($confs)-1);
    $HostsResolvR=$confs[$rrand];


    $Rand2=rand(1,254);
    $host=str_replace("*",$Rand2,$HostsResolvR);
    echo "Resolve[$rrand]: $HostsResolvR ($host)";
    if($HostsResolvR==null){
        $unix->ToSyslog("Fatal, cannot verify Local DNS Cache without hosts to resolve","unbound-watchdog");
        return false;
    }

    $NOERROR        = false;
    $result         = array();
    $r              = new Net_DNS2_Resolver(array('nameservers' => array($ListenInterface), "timeout" => 4));

    try {
        $result = $r->query($host);

    } catch(Net_DNS2_Exception $e) {
        $message=$e->getMessage();
        echo " $message (failed)\n";

        if(preg_match("#refuses.*?for policy reasons#",$message)){
            $NOERROR=true;
        }

        if(!$NOERROR) {
            squid_admin_mysql(0, "{APP_UNBOUND}: $message [ {action} = {restart} ]",
                @implode("\n", $results), __FILE__, __LINE__);
            $unix->ToSyslog("FATAL $message", "unbound-watchdog");
            shell_exec("/usr/sbin/artica-phpfpm-service -restart-unbound");
            return false;
        }
    }
    $addr="-";
    foreach ($result->answer as $index=>$rr){
        if(property_exists($rr,"address")){
            $addr=$rr->address;
            $unix->ToSyslog("Success, $host = $addr","unbound-watchdog");
            echo " $addr (success)\n";
            return true;
        }
    }

    $unix->ToSyslog("FATAL unable to resolve $host","unbound-watchdog");
    squid_admin_mysql(0,"{APP_UNBOUND}: unable to resolve $host [ {action} = {restart} ]",
        null,__FILE__,__LINE__);
    echo " $addr (failed)\n";
    shell_exec("/usr/sbin/artica-phpfpm-service -restart-unbound");
    return false;


}

function blacklists_download():bool{
    $unix=new unix();
    $NoInternetAccess=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoInternetAccess"));
    if($NoInternetAccess==1){
        return true;
    }
    @mkdir("/home/artica/squid/pgl.yoyo.org",0755,true);
    $cachefile="/home/artica/squid/pgl.yoyo.org/cache";
    $timef="/etc/artica-postfix/pids/exec.unbound.blacklists_download.time";

    if(!$GLOBALS["FORCE"]){
        $xtime=$unix->file_time_min("$timef");
        if($xtime<384){
            echo "Require 384mn, current $xtime\n";
            return true;}
        @unlink($timef);
        @file_put_contents($timef, time());
    }


    $tempfile=$unix->FILE_TEMP();
    $curl=new ccurl("http://pgl.yoyo.org/adservers/serverlist.php?hostformat=hosts;showintro=0&mimetype=plaintext");
    $curl->NoHTTP_POST=true;
    if(!$curl->GetFile($tempfile)){
        squid_admin_mysql(0,"Unable to download DNS blacklists $curl->error", null, __FILE__,__LINE__);
        /*StevenBlack();
        justdomains();
        Cameleon();
        zeustracker();
        notrack1();
        hostfilenet();*/
        return false;
    }
    $md5_src=crc32_file($cachefile);
    $md5_dst=crc32_file($tempfile);
    if($md5_src==$md5_dst){@unlink($tempfile);return false;}
    $q=new mysql();
    if(!$q->TABLE_EXISTS("unbound_blacklists", "artica_backup")){
        $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `unbound_blacklists` (  `hostname` varchar(255) NOT NULL,  enabled smallint(1) NOT NULL DEFAULT 1, PRIMARY KEY (`hostname`), KEY `enabled` (`enabled`) ) ENGINE=MYISAM;");
    }

    $count1=$q->COUNT_ROWS("unbound_blacklists", "artica_backup");
    $sq=array();
    $f=explode("\n",@file_get_contents($tempfile));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#127\.0\.0\.1\s+(.+)#", $line,$re)){continue;}
        $sq[]="('{$re[1]}','1')";
        if(count($sq)>500){
            $q->QUERY_SQL("INSERT IGNORE INTO unbound_blacklists ( `hostname`,`enabled`) VALUES ".@implode(",", $sq),"artica_backup");
            if(!$q->ok){return false;}
            $sq=array();
        }

    }
    if(count($sq)>0){
        $q->QUERY_SQL("INSERT IGNORE INTO unbound_blacklists ( `hostname`,`enabled`) VALUES ".@implode(",", $sq),"artica_backup");
        if(!$q->ok){return false;}
    }

    /*StevenBlack();
    justdomains();
    Cameleon();
    zeustracker();
    notrack1();
    hostfilenet();*/
    $count2=$q->COUNT_ROWS("unbound_blacklists", "artica_backup");
    $final=$count2-$count1;
    if($final>0){
        squid_admin_mysql(2, "Success updated $final blacklist websites for DNS Cache", null,__FILE__,__LINE__);
        build();
        reload();
    }
    @unlink($tempfile);
    return true;
}
function StevenBlack():bool{
    $unix=new unix();
    @mkdir("/home/artica/squid/pgl.yoyo.org",0755,true);
    $cachefile="/home/artica/squid/pgl.yoyo.org/StevenBlackCache";

    $tempfile=$unix->FILE_TEMP();
    $curl=new ccurl("https://raw.githubusercontent.com/StevenBlack/hosts/master/hosts");
    $curl->NoHTTP_POST=true;
    if(!$curl->GetFile($tempfile)){
        squid_admin_mysql(0,"Unable to download DNS StevenBlack blacklists $curl->error", null, __FILE__,__LINE__);
        return false;
    }
    $md5_src=crc32_file($cachefile);
    $md5_dst=crc32_file($tempfile);
    if($md5_src==$md5_dst){@unlink($tempfile);return false;}

    @copy($tempfile, $cachefile);
    $q=new mysql();
    $sq=array();
    $f=explode("\n",@file_get_contents($tempfile));
    foreach ($f as $line){
        $line=trim($line);
        if(substr($line, 0,1)=="#"){
            if($GLOBALS["VERBOSE"]){echo "SKIP $line\n";}
            continue;}
        if(strpos($line, "localhost")){
            if($GLOBALS["VERBOSE"]){echo "SKIP $line\n";}
            continue;}
        if(!preg_match("#^([0-9\.]+)\s+(.+)#", $line,$re)){
            if($GLOBALS["VERBOSE"]){echo "SKIP $line\n";}
            continue;}
        $re[2]=trim($re[2]);
        if($re[2]==null){continue;}
        $sq[]="('{$re[2]}','1')";
        if($re[2]==null){continue;}
        if($GLOBALS["VERBOSE"]){echo "{$re[2]}\n";}

        if(count($sq)>500){
            $q->QUERY_SQL("INSERT IGNORE INTO unbound_blacklists ( `hostname`,`enabled`) VALUES ".@implode(",", $sq),"artica_backup");
            if(!$q->ok){return false;}
            $sq=array();
        }

    }
    if(count($sq)>0){
        $q->QUERY_SQL("INSERT IGNORE INTO unbound_blacklists ( `hostname`,`enabled`) VALUES ".@implode(",", $sq),"artica_backup");
        if(!$q->ok){return false;}
    }
    @unlink($tempfile);
    return true;
}
function Cameleon(){
    $unix=new unix();
    $sq=array();
    @mkdir("/home/artica/squid/pgl.yoyo.org",0755,true);
    $cachefile="/home/artica/squid/pgl.yoyo.org/cameleon";

    $tempfile=$unix->FILE_TEMP();
    $curl=new ccurl("http://sysctl.org/cameleon/hosts");
    $curl->NoHTTP_POST=true;
    if(!$curl->GetFile($tempfile)){
        squid_admin_mysql(0,"Unable to download DNS cameleon blacklists $curl->error", null, __FILE__,__LINE__);
        return false;
    }
    $md5_src=crc32_file($cachefile);
    $md5_dst=crc32_file($tempfile);
    if($md5_src==$md5_dst){@unlink($tempfile);return true;}

    @copy($tempfile, $cachefile);
    $q=new mysql();
    $f=explode("\n",@file_get_contents($tempfile));
    foreach ($f as $line){
        $line=trim($line);
        if(substr($line, 0,1)=="#"){continue;}
        if(strpos($line, "localhost")){continue;}
        if(preg_match("#(.+?)\##", $line,$re)){$line=trim($re[1]);}
        if(!preg_match("#^([0-9\.]+)\s+(.+)#", $line,$re)){continue;}
        $re[2]=trim($re[2]);
        if($re[2]==null){continue;}
        if($GLOBALS["VERBOSE"]){echo "{$re[2]}\n";}

        $sq[]="('{$re[2]}','1')";
        if(count($sq)>500){
            $q->QUERY_SQL("INSERT IGNORE INTO unbound_blacklists ( `hostname`,`enabled`) VALUES ".@implode(",", $sq),"artica_backup");
            if(!$q->ok){return false;}
            $sq=array();
        }

    }
    if(count($sq)>0){
        $q->QUERY_SQL("INSERT IGNORE INTO unbound_blacklists ( `hostname`,`enabled`) VALUES ".@implode(",", $sq),"artica_backup");
        if(!$q->ok){return false;}
    }
    @unlink($tempfile);
    return true;
}

function hostfilenet() :bool{
    $unix=new unix();
    @mkdir("/home/artica/squid/pgl.yoyo.org",0755,true);
    $cachefile="/home/artica/squid/pgl.yoyo.org/ad_servers";
    $sq=array();
    $tempfile=$unix->FILE_TEMP();
    $curl=new ccurl("https://hosts-file.net/ad_servers.txt");
    $curl->NoHTTP_POST=true;
    if(!$curl->GetFile($tempfile)){
        squid_admin_mysql(0,"Unable to download DNS hosts-file.net blacklists $curl->error", null, __FILE__,__LINE__);
        return false;
    }
    $md5_src=crc32_file($cachefile);
    $md5_dst=crc32_file($tempfile);
    if($md5_src==$md5_dst){@unlink($tempfile);return true;}

    @copy($tempfile, $cachefile);

    $q=new mysql();
    $f=explode("\n",@file_get_contents($tempfile));
    foreach ($f as $line){
        $line=trim($line);
        if(substr($line, 0,1)=="#"){continue;}
        if(strpos($line, "localhost")){continue;}
        if(preg_match("#(.+?)\##", $line,$re)){$line=trim($re[1]);}
        if(!preg_match("#^([0-9\.]+)\s+(.+)#", $line,$re)){continue;}
        $re[2]=trim($re[2]);
        if($re[2]==null){continue;}
        if($GLOBALS["VERBOSE"]){echo "$re[2]\n";}
        $sq[]="('{$re[2]}','1')";
        if(count($sq)>500){
            $q->QUERY_SQL("INSERT IGNORE INTO unbound_blacklists ( `hostname`,`enabled`) VALUES ".@implode(",", $sq),"artica_backup");
            if(!$q->ok){return false;}
            $sq=array();
        }

    }
    if(count($sq)>0){
        $q->QUERY_SQL("INSERT IGNORE INTO unbound_blacklists ( `hostname`,`enabled`) VALUES ".@implode(",", $sq),"artica_backup");
        if(!$q->ok){return false;}
    }

    @unlink($tempfile);
    return true;
}
function install_dns(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    build_progress(25, "{installing}");
    squid_admin_mysql(2,"{installing} DNS Cache Advanced DNS feature",null,__FILE__,__LINE__);
    shell_exec("$php /usr/share/artica-postfix/exec.pdns.php --mysql --force");
    shell_exec("$php /usr/share/artica-postfix/exec.pdns.php --mysql --force");
    build_progress(30, "{configuring}");
    build();
    build_progress(90, "{restarting_service}");
    system("/usr/sbin/artica-phpfpm-service -restart-unbound");
    build_progress(100, "{done}");
}

function uninstall_dns(){
    squid_admin_mysql(1,"Removing DNS Cache Advanced DNS feature",null,__FILE__,__LINE__);
    build_progress(25, "{uninstall}");

    build_progress(30, "{configuring}");
    build();
    build_progress(90, "{restarting_service}");
    system("/usr/sbin/artica-phpfpm-service -restart-unbound");
    build_progress(100, "{done}");
}



function install_statscom(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();
    $title="{installing}";
    build_progress(50, "{installing}");
    $UnboundStatsCom=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundStatsCom"));


    if(!$unix->CORP_LICENSE()){
        build_progress(110,"$title {failed} {license_error}");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnboundStatsCom",0);
        uninstall_statscom();
        shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --logging");
        return false;
    }


    if($UnboundStatsCom==0){
        return uninstall_statscom();
    }
    build_progress(80, "{installing}");



    $unix=new unix();$unix->RESTART_SYSLOG(true);
    build_progress(100,"$title {success}");
}




function testunbound(){


    //gateway.icloud.com,www.apple.com,www.ibm.com,www.microsoft.com,clients1.google.com,graph.facebook.com

}
function uninstall(){
    system("/usr/sbin/artica-phpfpm-service -uninstall-unbound");
}

function remove_service($INITD_PATH){
    if(!is_file($INITD_PATH)){return;}
    system("$INITD_PATH stop");
    if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
    if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
    if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}
function stop($aspid=false):bool{
    system("/usr/sbin/artica-phpfpm-service -stop-unbound");
    return true;
}
function unbound_version_bin():int{

    $version=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundVersion"));
    if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)$#",$version,$re)){
        return intval("{$re[1]}{$re[2]}{$re[3]}");
    }
    if(preg_match("#^([0-9]+)\.([0-9]+)$#",$version,$re)){
        return intval("{$re[1]}{$re[2]}0");
    }
    return 0;
}

function PID_NUM(): int{
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/unbound.pid");
    if($unix->process_exists($pid)){return intval($pid);}
    $Masterbin=$unix->find_program("unbound");
    return intval($unix->PIDOF($Masterbin));
}





function mul($x):bool{
    if ($x % 2 == 0) {
        return TRUE;
    }else{
        return FALSE;
    }
}


function PID_REDIS(): int{
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/unbound-database.pid");
    if($unix->process_exists($pid)){return intval($pid);}
    return intval($unix->PIDOF("/usr/sbin/unbound-db"));
}
function stop_redis_service($aspid=false):bool{
    $GLOBALS["TITLENAME"]="DNS Cache memory database backend";
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

    $pid=PID_REDIS();


    if(!$unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";
        return true;
    }
    $pid=PID_REDIS();

    echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";
    unix_system_kill($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_REDIS();
        if(!$unix->process_exists($pid)){break;}
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";
        sleep(1);
    }

    $pid=PID_REDIS();
    if(!$unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";
        return true;
    }

    echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_REDIS();
        if(!$unix->process_exists($pid)){break;}
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";
        sleep(1);
    }

    if($unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";
        return false;
    }

    return true;

}

function restart_redis_service(){
    build_progress(20,"{stopping}");
    stop_redis_service(true);
    build_progress(50,"{reconfiguring}");
    build_redis_service();
    build_progress(80,"{starting}");
    if(!start_redis_service(true)){
        build_progress(110,"{failed}");
        return;
    }
    build_progress(100,"{success}");
}

function build_redis_progress($text,$prc){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"unbound-redis.progress");

}

function remove_redis_database(){
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $redis_cli=$unix->find_program("redis-cli");
    squid_admin_mysql(2,"{uninstalling} {APP_REDIS} {APP_UNBOUND} database",2);
    build_redis_progress("{flush_database}",20);
    system("$redis_cli -h 127.0.0.1 -p 21647 flushall");
    build_redis_progress("{stopping_service}",30);
    stop_redis_service(true);
    build_redis_progress("{remove_database}",50);

   shell_exec("$rm -rf /var/lib/unbound/redis/*");

    build_redis_progress("{starting_service}",80);
    if(!start_redis_service(true)){
        build_redis_progress("{starting_service} {failed}",110);
        return false;
    }
    build_redis_progress("{starting_service} {success}",100);
    return true;
}

function start_redis_service($aspid=false):bool{
    $GLOBALS["TITLENAME"]="DNS Cache memory database backend";


    $cf="/etc/unbound/database.conf";
    $unix=new unix();
    $Masterbin=$unix->find_program("redis-server");

    if(!is_file($Masterbin)) {
        if (is_file("/usr/sbin/unbound-db")) {
            @copy("/usr/sbin/unbound-db", "/usr/sbin/redis-server");
            $Masterbin = "/usr/sbin/redis-server";
        }
    }

    if(!is_file($Masterbin)){
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, redis-server not installed\n";
        return false;
    }

    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_REDIS();

    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";
        return true;
    }

    if(is_file("/usr/sbin/unbound-db")){
        $md51=crc32_file("/usr/sbin/unbound-db");
        $md52=crc32_file($Masterbin);
        if($md51<>$md52){@unlink("/usr/sbin/unbound-db");}
    }
    if(!is_file("/usr/sbin/unbound-db")){@copy($Masterbin,"/usr/sbin/unbound-db");}
    @chmod("/usr/sbin/unbound-db",0755);

    $cmdline="/usr/sbin/unbound-db $cf";
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";
    shell_exec($cmdline);

    for($i=1;$i<5;$i++){
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";
        sleep(1);
        $pid=PID_REDIS();
        if($unix->process_exists($pid)){break;}
    }

    $pid=PID_REDIS();
    if($unix->process_exists($pid)) {
        echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";
        return true;
    }

    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmdline\n";
    return false;

}

function AdRecords($ipaddr,$netb,$domain,$guidnumber,$nTDSDSA,$ttl):string{
    $Site   = "Default-First-Site-Name";
    $p389   = "0 100 389";
    $p3268  = "0 100 3268";
    $lsuf   = "_ldap._tcp";
    $kerb   = "_kerberos._tcp";
    $dz     = "DomainDnsZones";
    $df     = "ForestDnsZones";
    $f[]="";
    $f[]="";
    $f[]="\t#Realm: $domain --> ($netb) $ipaddr with a TTL of $ttl seconds";
    
    $f[]="\tlocal-zone: \"_msdcs.$domain\" typetransparent ";
    $f[]="\tprivate-domain: \"_msdcs.$domain\" ";
    $f[]="\tlocal-data: \"@._msdcs.$domain. $ttl IN NS $netb.$domain.\" ";
    $f[]="\tlocal-data: \"$kerb.$Site._sites.dc._msdcs.$domain.  $ttl IN SRV 0  100 88 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"$lsuf.$Site._sites.dc._msdcs.$domain.  $ttl IN SRV $p389 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"$kerb.dc._msdcs.$domain.  $ttl IN SRV 0 100 88 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"$lsuf.dc._msdcs.$domain.  $ttl IN SRV $p389 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"$lsuf.$guidnumber.domains._msdcs.$domain. $ttl IN SRV $p389 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"$nTDSDSA._msdcs.$domain.  $ttl IN CNAME $netb.$domain.\" ";
    $f[]="\tlocal-data: \"gc._msdcs.$domain.  $ttl IN A $ipaddr\" ";
    $f[]="\tlocal-data: \"$lsuf.$Site._sites.gc._msdcs.$domain. $ttl IN SRV $p3268 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"$lsuf.gc._msdcs.$domain.  $ttl IN SRV  $p3268 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"$lsuf.pdc._msdcs.$domain.  $ttl IN SRV $p389 $netb.$domain.\" ";
    $f[]="\tlocal-zone: \"$domain\" typetransparent ";
    $f[]="\tprivate-domain: \"$domain\" ";
    $f[]="\tlocal-data: \"@.$domain. $ttl IN A $ipaddr\" ";
    $f[]="\tlocal-data: \"_gc._tcp.$Site._sites.$domain.  $ttl IN SRV  $p3268 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"$kerb.$Site._sites.$domain. $ttl IN SRV 0 100 88 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"$lsuf.$Site._sites.$domain.  $ttl IN SRV $p389 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"_gc._tcp.$domain.  $ttl IN SRV  $p3268 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"$kerb.$domain.  $ttl IN SRV 0 100 88 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"_kpasswd._tcp.$domain.  $ttl IN SRV 0 100 464 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"$lsuf.$domain.  $ttl IN SRV $p389 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"_kerberos._udp.$domain.  $ttl IN SRV 0 100 88 $netb.$domain.\"";
    $f[]="\tlocal-data: \"_kpasswd._udp.$domain.  $ttl IN SRV 0 100 464 $netb.$domain.\"";
    $f[]="\tlocal-data: \"$kerb.$domain. $ttl IN A $ipaddr\"";
    $f[]="\tlocal-data: \"$kerb $ttl IN A $ipaddr\"";
    $f[]="\tlocal-data: \"$dz.$domain. $ttl IN A $ipaddr\" ";
    $f[]="\tlocal-data: \"$lsuf.$Site._sites.$dz.$domain. $ttl IN SRV $p389 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"$lsuf.$dz.$domain.  $ttl IN SRV $p389 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"$df.$domain.  $ttl IN A $ipaddr\" ";
    $f[]="\tlocal-data: \"$lsuf.$Site._sites.$df.$domain. $ttl IN SRV $p389 $netb.$domain.\" ";
    $f[]="\tlocal-data: \"$lsuf.$df.$domain.  $ttl IN SRV $p389 $netb.$domain.\" ";



    return strval(@implode("\n",$f));
}