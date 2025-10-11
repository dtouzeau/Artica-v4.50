<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["RELOAD"]=false;
$GLOBALS["TITLENAME"]="Load-Balancer Daemon";
$GLOBALS["OUTPUT"]=false;
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
$GLOBALS["NOCONF"]=false;
$GLOBALS["WIZARD"]=false;
$GLOBALS["BY_SCHEDULE"]=false;
$GLOBALS["tproxy_ip"]="127.0.0.1";

if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){
        $GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;
        $GLOBALS["DEBUG"]=true;
        ini_set('html_errors',0);
        ini_set('display_errors', 1);
        ini_set('error_reporting', E_ALL);
}
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.certificates.inc");
include_once(dirname(__FILE__)."/ressources/class.munin.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
$GLOBALS["MONIT"]=false;
if(preg_match("#--monit#",implode(" ",$argv))){$GLOBALS["MONIT"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--noconf#",implode(" ",$argv))){$GLOBALS["NOCONF"]=true;}
if(preg_match("#--wizard#",implode(" ",$argv))){$GLOBALS["WIZARD"]=true;}
if(preg_match("#--byschedule#",implode(" ",$argv))){$GLOBALS["BY_SCHEDULE"]=true;}
if($argv[1]=="--destroy-log"){destroy_log($argv[2]);}
if($argv[1]=="--build"){build();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--reload-transparent"){$GLOBALS["OUTPUT"]=true;reload_transparent();exit();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--uninstall"){uninstall();exit();}
if($argv[1]=="--keytab"){keytab($argv[2]);exit;}
if($argv[1]=="--ad-wizard"){ad_wizard();exit;}

if($argv[1]=="--start-fw"){start_transparent();}
if($argv[1]=="--stop-fw"){stop_transparent();}
if($argv[1]=="--adkey"){adkey();}
if($argv[1]=="--reload-cron"){reload_cron_perform();exit;}
if($argv[1]=="--reload-conf"){reload_cron();exit;}
if($argv[1]=="--hacluster-wizard-renew"){ad_wizard_cron();exit;}
if($argv[1]=="--rebuildif"){rebuild_only_if();exit;}
if($argv[1]=="--hacluster-clean"){hacluster_clean();exit;}

function reload_cron_perform(){
    $unix=new unix();
    $timefile="/var/run/hacluster-reload.time";

    $timeExec=$unix->file_time_min($timefile);
    if($timeExec<90){
        echo "Cannot reload before 90mn - current is {$timeExec}mn\n";
        return true;
    }
    @unlink($timefile);
    @file_put_contents($timefile,time());

    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        squid_admin_mysql(1,"Cannot Reload HaCluster (process is not running)",null,__FILE__,__LINE__);
        return false;
    }
    $why="(by schedule)";
    build_progress_restart(90,"{reloading} $why");
    hacluster_syslog("Reloading HaCluster service");
    $cmd=reload_command();
    exec($cmd,$results);
    $LOGS[]=" * * * * * Reloading explicit ports * * * * *";
    foreach ($results as $ligne){$LOGS[]=$ligne;}
    $HaClusterTransParentMode       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));
    $LOGS[]=" * * * * * Reloading transparent ports * * * * *";

    if($HaClusterTransParentMode==0){
        $LOGS[]="Not active";
        squid_admin_mysql(1,"Scheduled HaCluster reloading service task report",@implode("\n",$LOGS),__FILE__,__LINE__);
        return true;
    }

    exec("/usr/sbin/hacluster-transparent -f /etc/hacluster/tproxy.conf -k reconfigure >/dev/null 2>&1",$squidr);
    foreach ($squidr as $ligne){$LOGS[]=$ligne;}
    squid_admin_mysql(1,"Scheduled HaCluster reloading service task report",@implode("\n",$LOGS),__FILE__,__LINE__);
    return true;
}

function hacluster_config_path():string{
    return "/etc/hacluster/hacluster.cfg";
}
function hacluster_pid_path():string{
    return "/var/run/hacluster.pid";
}
function reload_command():string{
    $unix=new unix();
    $hacluster=$unix->find_program("hacluster");
    $CONFIG=hacluster_config_path();
    $PIDFILE=hacluster_pid_path();
    $pids=@implode(" ", pidsarr());
    $EXTRAOPTS=null;
    $cmd="$hacluster -f \"$CONFIG\" -p $PIDFILE -D $EXTRAOPTS -sf $pids 2>&1";
    return $cmd;
}
function GetStatusInfo():array{
    $unix=new unix();
    $echo=$unix->find_program("echo");
    $socat=$unix->find_program("socat");
    $status="$echo \"show info\"|$socat stdio /run/hacluster/admin.sock 2>&1";
    $show_info[]="Status of the Service:";
    $show_info[]="-------------------------------------";
    exec($status,$show_info);
    return $show_info;
}

function rebuild_only_if():bool{
    $sfile=hacluster_config_path();
    $md51=md5_file($sfile);
    if(!build()){return false;}
    $md52=md5_file($sfile);
    if($md51==$md52){return false;}

    hacluster_syslog("Reloading HaCluster service");
    $cmd=reload_command();
    exec($cmd,$results);
    foreach ($results as $ligne){
        echo "Starting......: ".date("H:i:s")." {$GLOBALS["TITLENAME"]} $ligne\n";
    }
    return true;
}

function restart($aspid=false){
    $unix=new unix();

    $Masterbin=$unix->find_program("hacluster");

    if(!is_file($Masterbin)){
        build_progress_restart(110,"{not_installed}");
        if($GLOBALS["OUTPUT"]){echo "ReStarting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, hacluster not installed\n";}
        return false;
    }

    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            build_progress_restart(110,"{already_running}");
            if($GLOBALS["OUTPUT"]){echo "ReStarting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    hacluster_syslog("Restarting HaCluster service");
    build_progress_restart(50,"{stopping_service}");
    stop(true);
    build_progress_restart(80,"{building}");
    build();

    reload_cron();
    monit_config();
    build_progress_restart(90,"{starting_service}");
    if(!start(true)){
        build_progress_restart(110,"{starting_service} {failed}");
        return false;
    }
    build_progress_restart(100,"{success}");
    return true;
}
function build_progress_restart($pourc,$text):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"hacluster.progress");
}
function build_progress_stop($pourc,$text):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"hacluster-stop.progress");

}
function build_progress_keytab($pourc,$text):bool{
    $unix=new unix();
   return $unix->framework_progress($pourc,$text,"hacluster.ticket.progress");
}
function build_progress_ad_wizard($pourc,$text):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"hacluster.wizard.progress");
}

function keytab($filename,$nonotify=false):bool{
    $unix=new unix();
    $keypath="/usr/share/artica-postfix/ressources/conf/upload/$filename";
    if(!is_file($keypath)){
        build_progress_keytab(110,"$filename no such file!");
        return false;
    }


    $destinationfile="/home/artica/PowerDNS/Cluster/storage/krb5.keytab";
    $destinationpath=dirname($destinationfile);
    if(!is_file($destinationpath)){@mkdir($destinationpath,0755,true);}
    @chmod($destinationpath,0755);


        if (is_file($destinationfile)) {
            @unlink($destinationfile);
        }
        if (!copy($keypath, $destinationfile)) {
            build_progress_keytab(110, "$filename {failed} copy");
            @unlink($keypath);
            return false;
        }


    @unlink($keypath);

    if(!is_file($destinationfile)){
        echo "$destinationfile no such file!\n";
        build_progress_keytab(100,"{failed}");
        return false;
    }

    $md5=md5_file($destinationfile);
    @file_put_contents("$destinationpath/krb5.md5",$md5);
    build_progress_keytab(100,"{success}");


    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $q->QUERY_SQL("UPDATE hacluster_backends SET status=0");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    if(!$nonotify) {
        shell_exec("$php /usr/share/artica-postfix/exec.hacluster.connect.php --connect >/dev/null 2>&1 &");
    }
    return true;
}

function adkey(){
    $haClusterAD=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
    print_r($haClusterAD);
}

function ad_wizard_cron():bool{
    $unix=new unix();
    $haClusterAD=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
    $kerberosActiveDirectoryHost=$haClusterAD["kerberosActiveDirectoryHost"];
    $myhostname=php_uname("n");
    $msktutil=$unix->find_program("msktutil");

    $cmd="$msktutil --server $kerberosActiveDirectoryHost  --verbose --auto-update --keytab /etc/squid3/krb5.keytab --host $myhostname -N 2>&1";
    exec($cmd,$results);

    $dst_keytab="/usr/share/artica-postfix/ressources/conf/upload/krb5.keytab";
    if(is_file($dst_keytab)){@unlink($dst_keytab);}
    @copy("/etc/squid3/krb5.keytab",$dst_keytab);
    if(!keytab("krb5.keytab",true)){
        squid_admin_mysql(0,"HaCluster: krb5.keytab copy {failed} after renew certificate",@implode($results),__FILE__,__LINE__);

        return false;
    }

    hacluster_syslog_master("HaCluster: Renewed certificate");
    squid_admin_mysql(1,"HaCluster: Renewed certificate",@implode($results),__FILE__,__LINE__);
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.hacluster.connect.php --kerberos-renew >/dev/null 2>&1 &");
    return true;

}
function hacluster_syslog_master($text){
    echo $text."\n";
    if(!function_exists("syslog")){return;}
    $LOG_SEV=LOG_INFO;
    openlog("hacluster", LOG_PID , LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
}

function ad_wizard(){
    $unix=new unix();
    $kinit=$unix->find_program("kinit");
    $kdestroy=$unix->find_program("kdestroy");
    $klist=$unix->find_program("klist");
    $echo=$unix->find_program("echo");
    $haClusterAD=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
    $kerberosRealm=strtoupper($haClusterAD["kerberosRealm"]);
    $kerberosRealmsown=strtolower($kerberosRealm);
    $kerberosActiveDirectoryHost=$haClusterAD["kerberosActiveDirectoryHost"];
    $myhostname=php_uname("n");



    $f[]="[logging]";
    $f[]="\tdefault = FILE:/var/log/krb5libs.log";
    $f[]="\tkdc = FILE:/var/log/krb5kdc.log";
    $f[]="\tadmin_server = FILE:/var/log/kadmind.log";
    $f[]="";
    $f[]="[libdefaults]";
    $f[]="\tdefault_keytab_name = /etc/squid3/krb5.keytab";
    $f[]="\tdefault_realm = $kerberosRealm";
    $f[]="\tdns_lookup_realm = false";
    $f[]="\tdns_lookup_kdc = true";
    $f[]=" default_tgs_enctypes = aes256-cts-hmac-sha1-96 rc4-hmac des-cbc-crc des-cbc-md5";
    $f[]=" default_tkt_enctypes = aes256-cts-hmac-sha1-96 rc4-hmac des-cbc-crc des-cbc-md5";
    $f[]=" permitted_enctypes = aes256-cts-hmac-sha1-96 rc4-hmac des-cbc-crc des-cbc-md5";
    $f[]="";
    $f[]="[realms]";
    $f[]="\t$kerberosRealmsown = {";
    $f[]="\t\tkdc = $kerberosActiveDirectoryHost:88";
    $f[]="\t\tadmin_server = $kerberosActiveDirectoryHost:749";
    $f[]="\t}";
    $f[]="";
    $f[]="[domain_realm]";
    $f[]="\t.$kerberosRealmsown = $kerberosRealm";
    $f[]="\t$kerberosRealmsown = $kerberosRealm";
    $f[]="";

    @mkdir("/etc/kerberos/tickets",0755,true);
    @file_put_contents("/etc/krb.conf", @implode("\n", $f));
    @file_put_contents("/etc/krb5.conf", @implode("\n", $f));
    unset($f);
    build_progress_ad_wizard(30,"{kerberaus_authentication}");
    shell_exec($kdestroy);

    $KerberosPassword=$haClusterAD["KerberosPassword"];
    $KerberosUsername=$haClusterAD["KerberosUsername"];

    $password     = $unix->shellEscapeChars($KerberosPassword);
    $username     = $KerberosUsername;

    if(strpos($username,"@")){
        $usernameR=explode("@",$username);
        $username=$usernameR[0];
    }
    $username     = $unix->shellEscapeChars($username);
    exec("$klist 2>&1",$res);
    $line=@implode("",$res);
    $res=array();
    if(strpos($line,"No credentials cache found")>0) {
        echo $line . " -> initialize..\n";
        $cmd = "$echo $password|$kinit {$username} 2>&1";
        exec($cmd, $kinit_array);
        foreach ($kinit_array as $a) {
            echo "$a\n";
            if (preg_match("#Clock skew too great while#", $a)) {
                echo "           * * * * * * * * * * * * * * * * * * *\n";
                echo "           * *                               * *\n";
                echo "           * * Please check the system clock ! *\n";
                echo "           * *   Time differ with the AD     * *\n";
                echo "           * *                               * *\n";
                echo "           * * * * * * * * * * * * * * * * * * *\n";
                build_progress_ad_wizard(110, "Clock skew too great");
                return false;
            }

        }
    }
    build_progress_ad_wizard(40,"{kerberaus_authentication}");
    $KLIST=false;
    exec("$klist 2>&1",$res);
    foreach ($res as $a){
        echo "$a\n";
        if(preg_match("#Default principal:(.+)#",$a,$re)){
            build_progress_ad_wizard(50, "{kerberaus_authentication} {success}");
            $KLIST=true;
            break;
        }
    }
    if(!$KLIST){return false;}

    $msktutil=$unix->find_program("msktutil");
    if(!is_dir("/etc/squid3")){@mkdir("/etc/squid3",0755,true);}

    $zmd[]="$msktutil";
    $zmd[]="--server $kerberosActiveDirectoryHost";
    $zmd[]="--precreate --host $myhostname -b cn=computers --service HTTP";
    $zmd[]="--no-reverse-lookups --verbose";
    $zmd[]="-k /etc/squid3/krb5.keytab";
    $zmd[]="--description \"Artica Cluster load-balancer\"";
    $zmd[]="--enctypes 28 -N 2>&1";
    $SAVECONF=false;
    $cmd=@implode(" ",$zmd);
    exec($cmd,$results);
    foreach ($results as $line){
        if(preg_match("#Determining default LDAP base:\s+(.+)#",$line,$re)){
            $haClusterAD["kerberosActiveDirectorySuffix"]=trim($re[1]);
            $SAVECONF=true;
        }
        if(preg_match("#Found Principal:\s+(.+)#",$line,$re)){
            $haClusterAD["KerberosSPN"]=trim($re[1]);
            $SAVECONF=true;
        }
        if(preg_match("#Error: Another computer account.*?has the principal HTTP\/(.+)#",$line,$re)){
            echo " * * * * $line  * * * * \n";
            build_progress_ad_wizard(110, "HTTP/{$re[1]} {duplicated_record}");
            return false;
        }


        if(preg_match("#Error was: Insufficient access#",$line)){
            build_progress_ad_wizard(110, "Insufficient access");
            return false;
        }

        if(preg_match("#continue with wrong UPN#i", $line)){
            build_progress_ad_wizard(110, "wrong UPN");
            return false;
        }
        if(preg_match("#Is your kerberos ticket expired#i", $line)){
            build_progress_ad_wizard(110, "kerberos ticket expired");
            return false;
        }

        echo "$line\n";
    }

    if($SAVECONF){
        $haClusterADS=serialize($haClusterAD);
        $haClusterADE=base64_encode($haClusterADS);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterAD",$haClusterADE);
    }
    $results=array();
    $cmd="$msktutil --server $kerberosActiveDirectoryHost  --verbose --auto-update --keytab /etc/squid3/krb5.keytab --host $myhostname -N 2>&1";
    exec($cmd,$results);
    foreach ($results as $line){
        echo "$line\n";
    }



    if(!is_file("/etc/squid3/krb5.keytab")){
        build_progress_ad_wizard(110, "krb5.keytab {failed}");
        return false;

    }
    $dst_keytab="/usr/share/artica-postfix/ressources/conf/upload/krb5.keytab";
    if(is_file($dst_keytab)){@unlink($dst_keytab);}
    build_progress_ad_wizard(60, "{building} krb5.keytab {success}");
    @copy("/etc/squid3/krb5.keytab",$dst_keytab);
    if(!keytab("krb5.keytab")){
        build_progress_ad_wizard(110, "krb5.keytab copy {failed}");
        return false;
    }
    $results=array();
    $klist=$unix->find_program("klist");
    echo "$klist -k /etc/squid3/krb5.keytab\n";
    exec("$klist -k /etc/squid3/krb5.keytab 2>&1",$results);
    foreach ($results as $line){
        if(!preg_match("#[0-9]+\s+(.+)#",$line,$re)){
            echo "No matches $line\n";
            continue;
        }
        $kvno=$re[1];
        $kvno=trim($kvno);
        echo "Adding KVNO: $kvno\n";
        $haClusterAD["KVNO"][$kvno]=true;
    }

    if(count($haClusterAD["KVNO"])>0){
        $haClusterADS=serialize($haClusterAD);
        $haClusterADE=base64_encode($haClusterADS);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterAD",$haClusterADE);
    }
    $cur=basename(__FILE__);

    $unix->Popuplate_cron_make("hacluster-wizard-renew","5 1 * * *","$cur --hacluster-wizard-renew");
    UNIX_RESTART_CRON();

    build_progress_ad_wizard(100, "{success}");
    return true;

}
function install():bool{
    $unix=new unix();
    build_progress_restart(20,"{installing}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Enablehacluster", 1);

    $php=$unix->LOCATE_PHP5_BIN();

    build_progress_restart(30,"{installing}");
    hacluster_install_service();



    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    build_progress_restart(40,"{installing}");
    echo "[".__LINE__."]:Stopping...\n";
    build_progress_restart(50,"{stopping}");
    stop(true);
    echo "[".__LINE__."]:Starting...\n";
    build_progress_restart(55,"{starting}");
    start(true);
    echo "[".__LINE__."]:Starting OK\n";
    build_progress_restart(60,"{please_wait}...");
    system("/usr/sbin/artica-phpfpm-service -reconfigure-syslog");
    build_progress_restart(65,"{please_wait}");
    system("/usr/sbin/artica-phpfpm-service -install-ntp");
    build_progress_restart(75,"{please_wait}");
    system("$php /usr/share/artica-postfix/exec.logrotate.php --reconfigure");
    UNIX_RESTART_CRON();

    build_progress_restart(80,"{please_wait}");
    if(is_file("/etc/init.d/squid")){
        squid_admin_mysql(0,"Removing proxy service and all associated service!",null,__FILE__,__LINE__);
        system("/usr/sbin/artica-phpfpm-service -uninstall-proxy");
    }

    build_progress_restart(85,"{please_wait}");
    if(is_file("/etc/init.d/firehol")){
        shell_exec("/usr/sbin/artica-phpfpm-service -uninstall-firewall");
    }

    if(!is_file("/etc/init.d/dnsdist")){
        if(!is_file("/etc/init.d/unbound")){
            build_progress_restart(90,"{please_wait} {installing} {APP_UNBOUND}");
            system("/usr/sbin/artica-phpfpm-service -install-unbound");
        }
    }
    return build_progress_restart(100,"{success}");

}





function hacluster_clean():bool{
    return true;
}




function hacluster_syslog($text):bool{
    if(!function_exists("openlog")){return false;}
    openlog("hacluster", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}


function start_transparent_uninstall_service():bool{
    $unix=new unix();
    if(is_file("/etc/init.d/hacluster-transparent")) {
        $unix->remove_service("/etc/init.d/hacluster-transparent");
    }

    if(is_file("/etc/monit/conf.d/APP_HAPROXY_CLUSTER_TRANSPARENT.monitrc")){
        @unlink("/etc/monit/conf.d/APP_HAPROXY_CLUSTER_TRANSPARENT.monitrc");
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    }
    return true;
}

function uninstall():bool{
    $unix=new unix();
    build_progress_restart(20,"{uninstalling}");
    squid_admin_mysql(0,"{uninstall} HaCluster!",null,__FILE__,__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Enablehacluster", 0);
    $unix->remove_service("/etc/init.d/hacluster");
    $unix->remove_service("/etc/init.d/hacluster-tail");
    @unlink("/etc/cron.d/hacluster-monthly");
    @unlink("/etc/cron.d/hacluster-yearly");
    @unlink("/etc/monit/conf.d/APP_HAPROXY_CLUSTER.monitrc");
    @unlink("/etc/monit/conf.d/APP_HAPROXY_CLUSTER_TAIL.monitrc");
    @unlink("/etc/cron.d/hacluster-rotate");
    @unlink("/etc/artica-postfix/HACLUSTER_LASTCONNS");
    @unlink("/etc/artica-postfix/HACLUSTER_LASTBYTES");
    @unlink("/home/artica/rrd/hacluster-queries.rrd");
    @unlink("/home/artica/rrd/hacluster-bandwidth.rrd");
    @unlink("/etc/cron.d/hacluster-reload");
    @unlink("/etc/cron.d/hacluster-stats");
    @unlink("/etc/cron.d/hacluster-clean");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    shell_exec("/etc/init.d/cron reload");

    if(is_file("/etc/rsyslog.d/hacluster.conf")){
        @unlink("/etc/rsyslog.d/hacluster.conf");
        @unlink("/etc/rsyslog.d/hacluster-client.conf");
        $unix->RESTART_SYSLOG(true);
    }
    if(is_file("/etc/rsyslog.d/hacluster-client.conf")){
        @unlink("/etc/rsyslog.d/hacluster-client.conf");
        $unix->RESTART_SYSLOG(true);
    }
    if(is_file("/etc/init.d/hacluster-transparent")) {
        start_transparent_uninstall_service();
    }
    $munin=new munin_plugins();
    $munin->build();
    return build_progress_restart(100,"{success}");
}




function start_transparent_install_monit(){

    if(is_file("/etc/monit/conf.d/APP_HAPROXY_CLUSTER_TRANSPARENT.monitrc")){return;}
    $f[]="check process APP_HAPROXY_CLUSTER_TRANSPARENT";
    $f[]="with pidfile /var/run/hacluster/squid.pid";
    $f[]="start program = \"/etc/init.d/hacluster-transparent start --monit\"";
    $f[]="stop program =  \"/etc/init.d/hacluster-transparent stop --monit\"";
    $f[]="if 5 restarts within 5 cycles then timeout";
    @file_put_contents("/etc/monit/conf.d/APP_HAPROXY_CLUSTER_TRANSPARENT.monitrc",@implode("\n",$f));
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");


}


function start_transparent_install_service(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/hacluster-transparent";
    $php5script="exec.hacluster.php";
    $daemonbinLog="Load-Balancer Daemon transparent method";



    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         artica-hacluster-transparent";
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
    $f[]="    $php /usr/share/artica-postfix/$php5script --start-fw \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop-fw \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop-fw \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start-fw \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reload)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start-fw \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reconfigure)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start-fw \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
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

    start_transparent_install_monit();

    $EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));


    if($EnableipV6==0){
        echo "Activate ipv6 configuration...\n";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableipV6",1);
        shell_exec("/usr/share/artica-postfix/bin/articarest -sysctl");
        shell_exec("$php /usr/share/artica-postfix/exec.sysctl.php --start");
    }

}

function hacluster_install_service():bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/hacluster";
    $php5script="exec.hacluster.php";
    $daemonbinLog="Load-Balancer Daemon";
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         artica-hacluster";
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
    $f[]=" reload)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reconfigure)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";

    $oldmd5="";
    if(is_file($INITD_PATH)){
        $oldmd5=md5_file($INITD_PATH);
    }

    @file_put_contents($INITD_PATH, @implode("\n", $f));
    $newMd5=md5_file($INITD_PATH);
    @chmod($INITD_PATH,0755);
    if($newMd5<>$oldmd5) {
        echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
        if (is_file('/usr/sbin/update-rc.d')) {
            shell_exec("/usr/sbin/update-rc.d -f " . basename($INITD_PATH) . " defaults >/dev/null 2>&1");
        }

        if (is_file('/sbin/chkconfig')) {
            shell_exec("/sbin/chkconfig --add " . basename($INITD_PATH) . " >/dev/null 2>&1");
            shell_exec("/sbin/chkconfig --level 345 " . basename($INITD_PATH) . " on >/dev/null 2>&1");
        }
    }

    $BaseNameInit=basename($INITD_PATH);
    $systemdFile="/etc/systemd/system/$BaseNameInit.service";
    $oldmd5="";
    if(is_file($systemdFile)){
        $oldmd5=md5_file($systemdFile);
    }

    $systemd[]="[Unit]";
    $systemd[]="Description=$BaseNameInit";
    $systemd[]="DefaultDependencies=no";
    $systemd[]="";
    $systemd[]="[Service]";
    $systemd[]="Restart=no";
    $systemd[]="PIDFile=/var/run/automount.pid";
    $systemd[]="ExecStart=$php /usr/share/artica-postfix/$php5script --start";
    $systemd[]="ExecStop=$php /usr/share/artica-postfix/$php5script --stop";
    $systemd[]="";
    $systemd[]="[Install]";
    $systemd[]="WantedBy=default.target";
    @file_put_contents($systemdFile,@implode("\n",$systemd));
    $newMd5=md5_file($INITD_PATH);

    if($newMd5==$oldmd5){
        return true;
    }

    if(is_file("/usr/bin/systemctl")){
        shell_exec("/usr/bin/systemctl enable $BaseNameInit");
    }
    return true;
}

function hacluster_tail_service(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/hacluster-tail";
    $php5script="exec.hacluster-tail-init.php";
    $daemonbinLog="Load-Balancer tail Daemon";



    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         hacluster-tail";
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
    $f[]=" reload)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reconfigure)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
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
function reload_transparent(){
    _out("[STOPPING]: Transparent Reconfiguring service...");
    start_transparent_squid_config();
    _out("[STOPPING]: Transparent Service Reloading service...");
    shell_exec("/usr/sbin/hacluster-transparent -f /etc/hacluster/tproxy.conf -k reconfigure");
}

function reload():bool{
    if(!$GLOBALS["NOCONF"]){
        build_progress_restart(20,"{building_settings}");
        build();
        reload_cron();


    }
    if(!isRunning()){
        build_progress_restart(50,"{starting_service}");
        start(true);
        if(!isRunning()){build_progress_restart(110,"{starting_service} {failed}");return false;}
        build_progress_restart(100,"{starting_service} {success}");
        reload_cron();

        monit_config();
        return true;
    }

    monit_config();
    build_progress_restart(90,"{reloading}");
    hacluster_syslog("Reloading HaCluster service");
    $cmd=reload_command();
    exec($cmd,$results);
    foreach ($results as $ligne){
        echo "Starting......: ".date("H:i:s")." {$GLOBALS["TITLENAME"]} $ligne\n";
    }
    return build_progress_restart(100,"{reloading} {success}");
}

function isRunning():bool{
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
    $f=file("/var/run/hacluster.pid");
    foreach ($f as $ligne){
        $ligne=trim($ligne);
        if(!is_numeric($ligne)){continue;}
        $R[]=$ligne;
    }
    return $R;
}


function start_transparent_squid_config(){

    $unix=new unix();
    $hostname=$unix->hostname_g();

    $php=$unix->LOCATE_PHP5_BIN();
    $chown=$unix->find_program("chown");
    $MKDIRS[]="/etc/squid3";
    $MKDIRS[]="/usr/share/squid3/icons";
    $MKDIRS[]="/var/log/hacluster";
    $MKDIRS[]="/var/cache/squid";
    $MKDIRS[]="/var/run/squid";


    foreach ($MKDIRS as $directory){
        if(!is_dir($directory)){@mkdir($directory,0755,true);}
        @chown($directory,"squid");
        @chgrp($directory,"squid");
    }

    if(!is_file("/etc/squid3/mime.conf")) {
        if (is_file("/etc/squid3/mime.conf.default")) {
            @copy("/etc/squid3/mime.conf.default", "/etc/squid3/mime.conf");
        }
    }
    if(!is_file("/etc/squid3/mime.conf")) {
        system("/usr/sbin/artica-phpfpm-service -proxy-mimeconf");
    }
    $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
    $HaClusterTransParentDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentDebug"));
    $HaClusterTransParentCertif=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentCertif"));
    $HaClusterTransparentBalance=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransparentBalance"));
    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    $HaClusterTransParentCPU=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentCPU"));
    if($CPU_NUMBER==0){$CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->getFrameWork("services.php?CPU-NUMBER=yes"));}
    if($HaClusterTransparentBalance==null){$HaClusterTransparentBalance="leastconn";}
    $squid_certificate=new squid_certificate();

    if(intval($HaClusterGBConfig["HaClusterDecryptSSL"])==1){
        $HaClusterTransParentCertif=$HaClusterGBConfig["HaClusterCertif"];
    }

    $ssl_crtd_path="/etc/squid3/ssl/ssl_db";
    $php = $unix->LOCATE_PHP5_BIN();
    $chown = $unix->find_program("chown");
    $chmod = $unix->find_program("chmod");
    $ssl_crtd="/lib/squid3/security_file_certgen";

    if(!is_dir("/etc/squid3/ssl/ssl_db")){
        system("$ssl_crtd  -c -s $ssl_crtd_path -M 4MB");
    }
    $ssl_line=$squid_certificate->BuildSquidCertificate($HaClusterTransParentCertif);
    $ssl_bump_line="ssl-bump $ssl_line";

    $f[]="pid_filename /var/run/hacluster/squid.pid";
    $f[]="debug_options ALL,1";
    $f[]="visible_hostname Tproxy-$hostname";
    $f[]="http_port 127.0.0.1:3150";
    $f[]="http_port {$GLOBALS["tproxy_ip"]}:3154 tproxy";
    $f[]="https_port {$GLOBALS["tproxy_ip"]}:3155 tproxy $ssl_bump_line";
    $f[]="pinger_enable off";
    $f[]="http_access allow all";
    $f[]="# Balance mode : $HaClusterTransparentBalance";

    $algo["source"]=" sourcehash";
    $algo["roundrobin"]=" round-robin";
    $algo["leastconn"]=" default";

    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");

    $sql="SELECT *  FROM `hacluster_backends`  WHERE enabled=1 AND status=100 AND isDisconnected=0 ORDER BY bweight";
    $results = $q->QUERY_SQL($sql);
    $cache_peer_access=array();
    $f[]="# ".count($results)." Backends servers";
    foreach ($results as $index=>$ligne) {
        $ID = $ligne["ID"];
        scopes_filter($ID);
        $f[]=@implode("\n",$GLOBALS["SCOPES_ERR"]);
        $ProxyName="T-{$ID}";
        $fname="/etc/hacluster/scope$ID.acl";
        if(is_file($fname)){
            $f[]="\tacl Scope{$ID} src \"$fname\"";
            $cache_peer_access[]="cache_peer_access $ProxyName allow Scope{$ID}";
        }
    }

    foreach ($results as $index=>$ligne) {
        $ID=$ligne["ID"];
        $listen_ip=$ligne["listen_ip"];
        $bweight=intval($ligne["bweight"]);
        if($bweight==0){$bweight=1;}
        if(isset($LWEIGHT[$bweight])){$bweight=$ID;}
        $LWEIGHT[$bweight]=true;
        $balance=$algo[$HaClusterTransparentBalance];
        $f[]="cache_peer $listen_ip parent 47887  0  proxy-only no-delay{$balance} no-query no-tproxy name=T-{$ID} connect-timeout=5 seconds connect-fail-limit=5 weight=$bweight";
    }
//
    if(count($cache_peer_access)>0){
        $f[]=@implode("\n",$cache_peer_access);
    }
    $f[]="never_direct allow all";
    $f[]="forwarded_for on";
    $f[]="retry_on_error on";
    $f[]="sslcrtd_program /usr/lib/squid3/security_file_certgen -s /etc/squid3/ssl/ssl_db  -M 8MB";
    $f[]="sslcrtd_children 32 startup=5 idle=1 queue-size=64";
    $f[]="acl ssl_step1 at_step SslBump1";
    $f[]="acl ssl_step2 at_step SslBump2";
    $f[]="acl ssl_step3 at_step SslBump3";
    $f[]="ssl_bump peek ssl_step1";
    $f[]="ssl_bump splice all";
    if($CPU_NUMBER>2) {
        if ($HaClusterTransParentCPU > 1) {
            $f[] = "workers $HaClusterTransParentCPU";
        }
        $Cores=1;
        for($i=1;$i<$HaClusterTransParentCPU+1;$i++){
            $Cores++;
            $process_numbers[]=$i;
            $cores[]=$Cores;

        }

        $f[]="cpu_affinity_map process_numbers=".@implode(",",$process_numbers)." cores=".@implode(",",$cores);

    }


    $f[]="cache_log /var/log/hacluster/hacluster-transparent.log";
    if($HaClusterTransParentDebug==1){
        $f[]="access_log stdio:/var/log/hacluster/access.log";
        $f[]="debug_options ALL,1";
    }else{
        $f[]="access_log none all";

        $f[]="debug_options ALL,0";
    }


    $f[]="cache_mem 1024 MB";
    $f[]="maximum_object_size_in_memory 1 MB";
    $f[]="memory_cache_mode network";
    $f[]="max_filedescriptors 65535";
    @file_put_contents("/etc/hacluster/tproxy.conf",@implode("\n",$f));
    shell_exec("ulimit -n 65535");
   _out("[RECONFIGURE]: /etc/hacluster/tproxy.conf success");
}

function scopes_filter($ID){
    $GLOBALS["SCOPES_ERR"]=array();
    $fname="/etc/hacluster/scope$ID.acl";
    if(is_file($fname)){@unlink($fname);}
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");


    $IP=new IP();
    $ligne=$q->mysqli_fetch_array("SELECT scopes FROM hacluster_backends WHERE ID=$ID");
    $scopes=unserialize(base64_decode($ligne["scopes"]));

    $items=array();
    foreach ($scopes as $line){
        $line=trim($line);
        if(!$IP->IsACDIROrIsValid($line)){
            $GLOBALS["SCOPES_ERR"][]="#[$ID]: $line - Error";
            continue;}
        $items[]=$line;
    }
    if(count($items)==0){return true;}
    @file_put_contents($fname,@implode("\n",$items));
    return true;

}

function reload_cron(){

    $cronfile="/etc/cron.d/hacluster-reload";
    $HaClusterReloadEach=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterReloadEach"));
    if($HaClusterReloadEach=="999"){
        if(is_file($cronfile)){
            @unlink($cronfile);
            UNIX_RESTART_CRON();
        }
        return true;
    }

    $md5=md5_file($cronfile);
    $unix=new unix();
    $unix->Popuplate_cron_make("hacluster-reload","10 */$HaClusterReloadEach * * *","exec.hacluster.php --reload-cron");
    $md52=md5_file($cronfile);
    if($md5==$md52){return true;}
    UNIX_RESTART_CRON();
    return true;

}

function build(){
    //https://docs.diladele.com/administrator_guide_stable/active_directory_extra/redundancy/haproxy_proxy_protocol.html
    $unix=new unix();
    if(!$unix->SystemUserExists("squid")){
        $unix->SystemCreateUser("squid","squid");
    }
    $dirs[]     = "/etc/hacluster";
    $dirs[]     = "/var/run/hacluster";
    $dirs[]     = "/var/lib/hacluster";
    $ListenIP   = null;
    $php        = $unix->LOCATE_PHP5_BIN();
    $httpcheck  = true;

    $HaClusterInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterInterface"));
    $HaClusterPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterPort"));
    $EnableZabbixAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableZabbixAgent"));
    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");
    $major=0;
    $minor=0;
    if(preg_match("#^([0-9]+)\.([0-9]+)#",$version,$re)){
        $major=intval($re[1]);
        $minor=intval($re[2]);
    }
    if($major>1){if($minor<4){$httpcheck=false;}}
    if($major>2){$httpcheck=true;}

    if(is_file("/etc/init.d/firehol")){
        shell_exec("/usr/sbin/artica-phpfpm-service -uninstall-firewall");
    }

    if(!is_file("/etc/cron.d/hacluster-syslog")){
        $f[]="PATH=PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
        $f[]="MAILTO=\"\"";
        $f[]="50 4 * * *      root    /etc/init.d/rsyslog restart >/dev/null 2>&1";
        $f[]="";
        @file_put_contents("/etc/cron.d/hacluster-syslog",@implode("\n",$f));
        shell_exec("/etc/init.d/cron reload");
        $f=array();
    }
    reload_cron();

    if($HaClusterPort==0){$HaClusterPort=3128;}

    if($HaClusterInterface<>null){
        $ListenIP=$unix->InterfaceToIPv4($HaClusterInterface);
    }

    $bind=":$HaClusterPort";
    if($ListenIP<>null){$bind="$ListenIP:$HaClusterPort";}
    $HaClusterBalance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterBalance");
    if($HaClusterBalance==null){$HaClusterBalance="leastconn";}
    if($HaClusterBalance=="roundrobin"){$HaClusterBalance="static-rr";}
    foreach ($dirs as $directory){
        if(!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }
        @chmod($directory,0755);
        @chown($directory,"squid");
        @chgrp($directory,"squid");
    }

    if(is_file("/etc/init.d/squid")){
        squid_admin_mysql(0,"[HaCluster] uninstall proxy service and all associated services!",
            null,__FILE__,__LINE__);
        system("/usr/sbin/artica-phpfpm-service -uninstall-proxy");
    }

    $HaClusterProto=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterProto");
    if($HaClusterProto==null){$HaClusterProto="tcp";}

    $HaClusterWorkers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterWorkers"));
    $HaClusterMaxConn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterMaxConn"));
    $HaClusterTHreads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTHreads"));

    $HaClusterTimeOutConnect=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeOutConnect"));
    $HaClusterSeverTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterSeverTimeout"));
    $HaClusterClientTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClientTimeout"));

    if($HaClusterTimeOutConnect==0){$HaClusterTimeOutConnect=10;}
    if($HaClusterSeverTimeout==0){$HaClusterSeverTimeout=30;}
    if($HaClusterClientTimeout==0){$HaClusterClientTimeout=300;}

    if($HaClusterMaxConn<2000){$HaClusterMaxConn=2000;}
    if($HaClusterWorkers==0){$HaClusterWorkers=1;}
    $f[]="# Major: $major, minor: $minor";
    $f[]="global";
    $f[]="    log               127.0.0.1 local0 notice";
    $f[]="    stats socket      /run/hacluster/admin.sock mode 660 level admin";
    $f[]="    stats timeout     30s";
    $f[]="    user              root";
    $f[]="    group             root";
    $f[]="    daemon";
    $f[]="    maxconn           $HaClusterMaxConn";
    $f[]="    ulimit-n          2010000";

    if($HaClusterWorkers>1){
        $cpumap=$HaClusterWorkers-1;
        $f[]="\tnbthread           $HaClusterWorkers";
        $f[]="\tcpu-map auto:1/1-$HaClusterWorkers 0-$cpumap";
    }



    $f[]="";
    $f[]="defaults";
    $f[]="    log     global";
//    $f[]="    mode    http";
//    $f[]="    option  httplog";
    $f[]="    log-format  %{+Q}o:::client_ip=%ci:::client_port=%cp:::datetime_of_request=[%tr]:::frontend_name_transport=%ft:::backend_name=%b:::server_name=%s:::time_to_receive_full_request=%TR:::Tw=%Tw:::Tc=%Tc:::response_time=%Tr:::active_time_of_request=%Ta:::status_code=%ST:::bytes_read=%B:::captured_request_cookie=%CC:::captured_response_cookie=%CS:::termination_state_with_cookie_status=%tsc:::actconn=%ac:::feconn=%fc:::beconn=%bc:::srv_conn=%sc:::retries=%rc:::srv_queue=%sq:::backend_queue=%bq:::captured_request_headers_default_style=%hr:::captured_response_headers_default_style=%hs:::server_ip=%si:::server_port=%sp:::frontend_name=%f:::http_method=%HM:::http_request_uri_without_query=%HP:::http_request_query_string=%HQ:::http_request_uri=%HU:::bytes_uploaded=%U:::ssl_ciphers=%sslc:::ssl_version=%sslv:::%[capture.res.hdr(0)]";

    $f[]="    option  dontlognull";

    if($HaClusterTimeOutConnect>0){
        $f[]="    timeout connect {$HaClusterTimeOutConnect}s";
    }
    if($HaClusterSeverTimeout>0){
        $f[]="    timeout server {$HaClusterSeverTimeout}s";
    }
    if($HaClusterClientTimeout>0){
        $f[]="    timeout client {$HaClusterClientTimeout}s";
    }
    $HaClusterTimeOutHTTPRequest=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeOutHTTPRequest"));
    $HaClusterTimeOutHTTPKeepAlive=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeOutHTTPKeepAlive"));
    $HaClusterTimeOutQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeOutQueue"));
    $HaClusterTimeOutTunnel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeOutTunnel"));
    $HaClusterTimeOutClientFin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeOutClientFin"));
    $HaClusterTimeOutServerFin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTimeOutServerFin"));


    if($HaClusterTimeOutHTTPRequest>0){
        $f[]="    timeout http-request {$HaClusterTimeOutHTTPRequest}s";
    }
    if($HaClusterTimeOutHTTPKeepAlive>0){
        $f[]="    timeout http-keep-alive {$HaClusterTimeOutHTTPKeepAlive}s";
    }
    if($HaClusterTimeOutQueue>0){
        $f[]="    timeout queue {$HaClusterTimeOutQueue}s";
    }
    if($HaClusterTimeOutTunnel>0){
        $f[]="    timeout tunnel {$HaClusterTimeOutTunnel}s";
    }
    if($HaClusterTimeOutClientFin>0){
        $f[]="    timeout client-fin {$HaClusterTimeOutClientFin}s";
    }
    if($HaClusterTimeOutServerFin>0){
        $f[]="    timeout server-fin {$HaClusterTimeOutServerFin}s";
    }
    $f[]="option log-health-checks";
    $f[]="";


    $f[] = "frontend hacluster";
    $f[] = "\tbind\t$bind";
    $f[] = "\tmode $HaClusterProto";
    $f[] = "\toption\t{$HaClusterProto}log";
    if($HaClusterProto=="http"){
        $f[] = "\toption forwardfor";
        $f[] = "\toption http-server-close";
        $f[] = "\toption accept-invalid-http-request";
        $f[] = "\tcapture request header Host len 1024";
        $f[] = "\tcapture request header Content-Type len 1024";
        $f[] = "\tcapture request header User-Agent len 1024";
        $f[] = "\tcapture request header Referer len 1024";
        $f[] = "\tcapture request header X-Forwarded-For len 1024";
        $f[] = "\tcapture response header Content-Type len 1024";
        $f[] = "\tcapture cookie Cookie_2 len 100";
        $f[] = "\thttp-request set-header mode mode:http";
        $f[] = "\thttp-request capture hdr(mode)  len 10";

    }
    $f[] ="\tdefault_backend proxys";

    $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));
    $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
    $HaClusterDisableProxyProtocol=intval($HaClusterGBConfig["HaClusterDisableProxyProtocol"]);
    $HaClusterUseHaClient=intval($HaClusterGBConfig["HaClusterUseHaClient"]);


    $HaClusterCheckInt=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterCheckInt"));
    $HaClusterCheckFall=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterCheckFall"));
    $HaClusterCheckRise=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterCheckRise"));
    if($HaClusterCheckInt==0){$HaClusterCheckInt=10;}
    if($HaClusterCheckInt<5){$HaClusterCheckInt=5;}
    if($HaClusterCheckFall==0){$HaClusterCheckFall=5;}
    if($HaClusterCheckFall<2){$HaClusterCheckInt=2;}
    if($HaClusterCheckRise==0){$HaClusterCheckRise=2;}


    if($HaClusterTransParentMode==1){
        $HaClusterTransparentBalance=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransparentBalance"));
        if($HaClusterTransparentBalance==null){$HaClusterTransparentBalance="leastconn";}

    }

    $backend_opts[]="\tbalance $HaClusterBalance";
    if($HaClusterBalance=="source"){
        $backend_opts[]="\thash-type consistent";
        $backend_opts[]="\tstick-table type ip size 1m expire 1h";
        $backend_opts[]="\tstick on src";
    }

    $backend_opts[] = "\tlog-tag rt-requests";
    $backend_opts[] = "\tmode $HaClusterProto";
    if($HaClusterUseHaClient==0) {
        if($httpcheck) {
            $backend_opts[] = "\toption httpchk";
            $backend_opts[] = "\thttp-check send meth GET uri /squid-internal-mgr/info";
        }
    }
    $backend_options=@implode("\n",$backend_opts);

    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");

    $sql="SELECT *  FROM `hacluster_backends`  WHERE enabled=1 AND status=100 AND isDisconnected=0 ORDER BY bweight";
    $results = $q->QUERY_SQL($sql);


    $f[]="# Disable proxy protocol = $HaClusterDisableProxyProtocol";
    $f[]="# Use HaCluster Client = $HaClusterUseHaClient";
    $send_proxy=" send-proxy";
    if($HaClusterDisableProxyProtocol==1){$send_proxy=null;}

    foreach ($results as $index=>$ligne) {
        $ID=$ligne["ID"];
        $listen_port=$ligne["listen_port"];
        $listen_ip=$ligne["listen_ip"];
        $bweight=intval($ligne["bweight"]);
        if($bweight==0){$bweight=1;}
        $check="check inter {$HaClusterCheckInt}s fall {$HaClusterCheckFall} rise {$HaClusterCheckRise}";
        if($HaClusterUseHaClient==1){
            $check="agent-check agent-inter {$HaClusterCheckInt}s agent-addr $listen_ip  agent-port 27899";
        }
        $SERVERS[$ID]="\tserver proxy{$ID} {$listen_ip}:{$listen_port}{$send_proxy} weight $bweight $check";

    }


    $f[]="# ".count($results)." Backends servers";
    $multiple_backends=array();
    foreach ($results as $index=>$ligne) {
        $ID = $ligne["ID"];
        scopes_filter($ID);
        $f[]=@implode("\n",$GLOBALS["SCOPES_ERR"]);
        $fname="/etc/hacluster/scope$ID.acl";
        if(is_file($fname)){
            $f[]="\tacl Scope{$ID} src -f \"$fname\"";
            $f[]="\tuse_backend backend_$ID if Scope{$ID}";
            $multiple_backends[]="";
            $multiple_backends[]="backend backend_$ID";
            $multiple_backends[]=$backend_options;
            $multiple_backends[]=$SERVERS[$ID];
            $multiple_backends[]="";
        }
    }

    $f[]=@implode("\n",$multiple_backends);
    $f[]="backend proxys";


    $f[]=$backend_options;
    foreach ($SERVERS as $ID=>$serv){
        $f[]=$serv;
    }


    $f[] = "";
    $f[] = "";
    if($EnableZabbixAgent==1) {
        $f[] = "";
        $f[] = "frontend stats";
        $f[]="      mode            http";
        $f[] = "    bind            127.0.0.1:44787";
        $f[] = "    stats           enable";
        $f[] = "    stats uri       /stats";
        $f[] = "    stats refresh   10s";
        $f[] = "    stats admin     if TRUE";

    }
    $f[] = "";
    $f[] = "";

    if(is_file("/etc/hacluster/hacluster.cfg")) {
        @file_put_contents("/etc/hacluster/hacluster-pre.cfg", @implode("\n", $f));
        if (!haclusterCheck()) {
            return false;
        }
    }
    @file_put_contents("/etc/hacluster/hacluster.cfg", @implode("\n", $f));
    zabbix_proxy_config();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HACLUSTER_CONFIG_FAILED","");
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, /etc/hacluster/hacluster.cfg done\n";
    return true;


}
function haclusterCheck(){
    $unix=new unix();
    $Masterbin=$unix->find_program("haproxy");
    $nextbin=dirname($Masterbin)."/hacluster";

    exec("$nextbin -c -f /etc/hacluster/hacluster-pre.cfg 2>&1",$results);
    foreach ($results as $line){
        if(preg_match("#Fatal errors found in configuration#i",$line)){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HACLUSTER_CONFIG_FAILED",base64_encode(@implode("\n",$results)));
            return false;
        }
    }

    return true;

}

function zabbix_proxy_config(){

    $EnableZabbixAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableZabbixAgent"));
    if($EnableZabbixAgent==0){return false;}
    $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));

    $Zabbix_file="/etc/zabbix/zabbix_agentd.d/HaCluster_transparent.conf";
    if($HaClusterTransParentMode==0){
        if(!is_file($Zabbix_file)){return false;}
        @unlink($Zabbix_file);
        shell_exec("/etc/init.d/zabbix-agent restart");
        return true;
    }

    if(!is_file($Zabbix_file)) {

        $f[] = "#HaCluster TRansaprent 3.4 template";
        $f[] = "";
        $f[] = "# Cache information for squid:";
        $f[] = "UserParameter=HaClusterTransparent.disk_hits_as_of_hit_requests,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Disk hits as % of hit requests:'|cut -d':' -f3|cut -d',' -f1|tr -d ' %'";
        $f[] = "UserParameter=HaClusterTransparent.hits_as_of_all_requests,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Hits as % of all requests:'|cut -d':' -f3|cut -d',' -f1|tr -d ' %'";
        $f[] = "UserParameter=HaClusterTransparent.hits_as_of_bytes_sent,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Hits as % of bytes sent:'|cut -d':' -f3|cut -d',' -f1|tr -d ' %'";
        $f[] = "UserParameter=HaClusterTransparent.mean_object_size,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Mean Object Size:'|cut -d':' -f2|awk '{print $1}'";
        $f[] = "UserParameter=HaClusterTransparent.memory_hits_as_of_hit_requests,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Memory hits as % of hit requests:'|cut -d':' -f3|cut -d',' -f1|tr -d ' %'";
        $f[] = "UserParameter=HaClusterTransparent.storage_mem_capacity,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Storage Mem capacity:'|cut -d':' -f2|awk '{print $1}'|tr -d ' %'";
        $f[] = "UserParameter=HaClusterTransparent.storage_mem_size,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Storage Mem size:'|cut -d':' -f2|awk '{print $1}'";
        $f[] = "UserParameter=HaClusterTransparent.storage_swap_capacity,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Storage Swap capacity:'|cut -d':' -f2|awk '{print $1}'|tr -d ' %'";
        $f[] = "UserParameter=HaClusterTransparent.storage_swap_size,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Storage Swap size:'|cut -d':' -f2|awk '{print $1}'";
        $f[] = "";
        $f[] = "# Connection information for squid";
        $f[] = "UserParameter=HaClusterTransparent.average_http_requests_per_minute_since_start,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Average HTTP requests per minute since start:'|cut -d':' -f2| tr -d ' \\t'";
        $f[] = "UserParameter=HaClusterTransparent.average_icp_messages_per_minute_since_start,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Average ICP messages per minute since start:'|cut -d':' -f2| tr -d ' \\t'";
        $f[] = "UserParameter=HaClusterTransparent.number_of_clients_accessing_cache,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Number of clients accessing cache:'|cut -d':' -f2| tr -d ' \\t'";
        $f[] = "UserParameter=HaClusterTransparent.number_of_htcp_messages_received,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Number of HTCP messages received:'|cut -d':' -f2| tr -d ' \\t'";
        $f[] = "UserParameter=HaClusterTransparent.number_of_htcp_messages_sent,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Number of HTCP messages sent:'|cut -d':' -f2| tr -d ' \\t'";
        $f[] = "UserParameter=HaClusterTransparent.number_of_http_requests_received,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Number of HTTP requests received:'|cut -d':' -f2| tr -d ' \\t'";
        $f[] = "UserParameter=HaClusterTransparent.number_of_icp_messages_received,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Number of ICP messages received:'|cut -d':' -f2| tr -d ' \\t'";
        $f[] = "UserParameter=HaClusterTransparent.number_of_icp_messages_sent,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Number of ICP messages sent:'|cut -d':' -f2| tr -d ' \\t'";
        $f[] = "UserParameter=HaClusterTransparent.number_of_queued_icp_replies,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Number of queued ICP replies:'|cut -d':' -f2| tr -d ' \\t'";
        $f[] = "UserParameter=HaClusterTransparent.request_failure_ratio,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Request failure ratio:'|cut -d':' -f2| tr -d ' \\t'";
        $f[] = "";
        $f[] = "# File descriptor usage for squid";
        $f[] = "UserParameter=HaClusterTransparent.available_number_of_file_descriptors,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Available number of file descriptors:'|cut -d':' -f2|awk '{print $1}'";
        $f[] = "UserParameter=HaClusterTransparent.files_queued_for_open,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Files queued for open:'|cut -d':' -f2|awk '{print $1}'";
        $f[] = "UserParameter=HaClusterTransparent.largest_file_desc_currently_in_use,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Largest file desc currently in use:'|cut -d':' -f2|awk '{print $1}'";
        $f[] = "UserParameter=HaClusterTransparent.maximum_number_of_file_descriptors,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Maximum number of file descriptors:'|cut -d':' -f2|awk '{print $1}'";
        $f[] = "UserParameter=HaClusterTransparent.number_of_file_desc_currently_in_use,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Number of file desc currently in use:'|cut -d':' -f2|awk '{print $1}'";
        $f[] = "UserParameter=HaClusterTransparent.reserved_number_of_file_descriptors,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Reserved number of file descriptors:'|cut -d':' -f2|awk '{print $1}'";
        $f[] = "UserParameter=HaClusterTransparent.store_disk_files_open,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Store Disk files open:'|cut -d':' -f2|awk '{print $1}'";
        $f[] = "";
        $f[] = "# Median Service Times (seconds)";
        $f[] = "UserParameter=HaClusterTransparent.cache_hits,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Cache Hits:'|cut -d':' -f2|tr -s ' '|awk '{print $1}'";
        $f[] = "UserParameter=HaClusterTransparent.cache_misses,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Cache Misses:'|cut -d':' -f2|tr -s ' '|awk '{print $1}'";
        $f[] = "UserParameter=HaClusterTransparent.dns_lookups,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'DNS Lookups:'|cut -d':' -f2|tr -s ' '|awk '{print $1}'";
        $f[] = "UserParameter=HaClusterTransparent.http_requests_all,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'HTTP Requests (All):'|cut -d':' -f2|tr -s ' '|awk '{print $1}'";
        $f[] = "UserParameter=HaClusterTransparent.icp_queries,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'ICP Queries:'|cut -d':' -f2|tr -s ' '|awk '{print $1}'";
        $f[] = "UserParameter=HaClusterTransparent.near_hits,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Near Hits:'|cut -d':' -f2|tr -s ' '|awk '{print $1}'";
        $f[] = "UserParameter=HaClusterTransparent.not_modified_replies,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Not-Modified Replies:'|cut -d':' -f2|tr -s ' '|awk '{print $1}'";
        $f[] = "";
        $f[] = "# Resource usage for squid";
        $f[] = "UserParameter=HaClusterTransparent.cpu_usage,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'CPU Usage:'|cut -d':' -f2|tr -d '%'|tr -d ' \\t'";
        $f[] = "UserParameter=HaClusterTransparent.maximum_resident_size,/usr/bin/squidclient -h 127.0.0.1 -p 3150 mgr:info 2>&1|grep 'Maximum Resident Size:'|cut -d':' -f2|awk '{print $1}'";
        $f[] = "";
        @file_put_contents($Zabbix_file, @implode("\n", $f));
        shell_exec("/etc/init.d/zabbix-agent restart");
    }

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
    return false;
}

function start($aspid=false):bool{
    $unix=new unix();
    $Masterbin=$unix->find_program("haproxy");

    if(!is_file($Masterbin)){
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, haproxy not installed\n";
        build_progress_stop(110,"{failed}");
        return false;}

    $nextbin=dirname($Masterbin)."/hacluster";

    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $Masterbin -> $nextbin\n";
    if(is_file($nextbin)) { @unlink($nextbin);  }
    @copy($Masterbin, $nextbin);
    @chmod($nextbin, 0755);



    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            _out("[STARTING] Connected Service already Artica task running PID $pid since {$time}mn");
            build_progress_stop(110,"{failed}");
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();

    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        _out("[STARTING] Connected Service already started $pid since {$timepid}Mn...");
        start_transparent();
        build_progress_stop(100,"{success}");
        return true;
    }
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));

    if($Enablehacluster==0){
        _out("[STARTING] Connected Service feature is disabled (see Enablehacluster)");
        build_progress_stop(110,"{failed} {disabled}");
        return false;
    }

    if(!is_file("/etc/hacluster/hacluster.cfg")){
        _out("[STARTING] Connected Service /etc/hacluster/hacluster.cfg no such file");
        build_progress_stop(110,"{failed}");
        return false;
    }

    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");


    if(!UDPServerRun()){
        _out("[STARTING] Connected Service Syslog server is not ready, prepare it");
        system("/usr/sbin/artica-phpfpm-service -reconfigure-syslog");
        if(UDPServerRun()){
            $unix=new unix();$unix->RESTART_SYSLOG(true);
        }else{
            _out("[STARTING] Connected Service Failed to prepare syslog engine");
        }
    }else{
        _out("[STARTING] Connected Service Syslog server [OK]");
    }
    build_progress_stop(43,"{starting}");
    build();
    build_progress_stop(44,"{starting}");
    remove_limits();
    build_progress_stop(45,"{starting}");
    hacluster_install_service();
    build_progress_stop(50,"{starting}");
    _out("[STARTING] Connected Service HaCluster service...");
    $cmd="$nohup $nextbin -f /etc/hacluster/hacluster.cfg -D -p /var/run/hacluster.pid  >/tmp/hacluster.start 2>&1 &";
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
    shell_exec($cmd);



    $prc=50;
    for($i=1;$i<5;$i++){
        build_progress_restart(95,"{starting_service} $i/5");
        build_progress_stop($prc++,"{starting_service} $i/5");
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
        sleep(1);
        $pid=PID_NUM();
        if($unix->process_exists($pid)){break;}
    }

    $f=explode("\n",@file_get_contents("/tmp/hacluster.start"));
    foreach ($f as $line){
        if(trim($line)==null){continue;}
        _out("[STARTING] Connected Service $line");
    }

    $pid=PID_NUM();
    if($unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
        build_progress_stop(100,"{starting_service} {success}");
        return start_transparent();
    }
    _out("[STARTING] Connected Service HaCluster service Failed");
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
    build_progress_stop(110,"{starting_service} {failed}");
    return false;



}
function PID_NUM():int{
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/hacluster.pid");
    if( $unix->process_exists($pid) ){ return $pid; }
    $Masterbin=$unix->find_program("hacluster");
    return $unix->PIDOF($Masterbin);
}

function stop_transparent_check_stopped($filetmp):bool{

    $f=explode("\n",@file_get_contents($filetmp));

    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#WARNING: #",$line)){continue;}
        if(preg_match("#FATAL: failed to send signal [0-9] to Squid instance#i",$line)){
            @unlink($filetmp);
            return true;
        }
        if(preg_match("#FATAL: failed to open .*?\.pid#i",$line)){
            @unlink($filetmp);
            return true;
        }
        echo $line."\n";
    }
    @unlink($filetmp);
    return false;
}

function stop_transparent_check_childs(){

    $unix=new unix();
    $pgrep=$unix->find_program("pgrep");
    exec("$pgrep -lf \"kid squid-[0-9]+ -f \/etc\/hacluster\/\" 2>&1",$results);
    $array=array();
    foreach ($results as $line){
        if(preg_match("#^[0-9]+\s+.*?pgrep#",$line)){continue;}
        if(!preg_match("#^([0-9]+)\s+#",$line,$re)){continue;}
        $array[]=$re[1];

    }

    return $array;
}


function stop($aspid=false):bool{
    $unix=new unix();
    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            _out("[STOPPING] Service Already Artica task running PID $pid since {$time}mn");
            build_progress_stop(110,"{failed}");
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();

    if(!$unix->process_exists($pid)){
        _out("[STOPPING] Connected Service already stopped...");
        build_progress_stop(100,"{success}");
        return stop_transparent();
    }
    $pid=PID_NUM();
    build_progress_stop(50,"{stopping_service} pid:$pid");
    _out("[STOPPING] Connected Service Shutdown pid $pid...");
    unix_system_kill($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
        sleep(1);
    }

    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        _out("[STOPPING] Connected Service success...");
        build_progress_stop(100,"{stopping_service} {success}");
        return stop_transparent();
    }

    _out("[KILLING] Connected service shutdown - force - pid $pid");
    unix_system_kill_force($pid);
    build_progress_stop(60,"{stopping_service} pid:$pid");
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
        sleep(1);
    }

    $Masterbin=$unix->find_program("haproxy");
    $nextbin=dirname($Masterbin)."/hacluster";

    $pids=$unix->PIDOF_PATTERN_ALL($nextbin);
    if( count($pids)>0){
        _out(sprintf("[KILLING] %s childs...\n",count($pids)));
        foreach ($pids as $pidghost=>$none){
            _out(sprintf("[KILLING] %s child...\n",$pidghost));
            unix_system_kill_force($pidghost);
        }
    }

    $pid=PID_NUM();
    if($unix->process_exists($pid)){
        _out("[STOPPING] Connected service failed...");
        build_progress_stop(110,"{stopping_service} pid:$pid {failed}");
        return false;
    }
    _out("[STOPPING] Connected service Success...");
    build_progress_stop(100,"{stopping_service} {success}");
    return stop_transparent();
}

function sources_white(){
    $unix=new unix();
    $sources=array();
    $iptables_save=$unix->find_program("iptables-save");
    exec("$iptables_save 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#-A PREROUTING -s (.+?)\/32.*?-j RETURN#",$line,$re)){
            if(preg_match("#^-A\s+(.+)#",$line,$re)){
                $sources[]=$re[1];
            }

        }
    }
    return $sources;
}
function stop_transparent():bool{
    $unix=new unix();
    $ip=$unix->find_program("ip");
    $iptables=$unix->find_program("iptables");

    $DETECTED=if_iptables_divert();
    _out("[STOPPING]: Transparent Service $DETECTED Firewall Rules");
    while($DETECTED>0){
        _out("[STOPPING]: Remove Firewall Rules");
        shell_exec("$iptables -t mangle -D PREROUTING -p tcp -m socket -m comment --comment HaCluster -j DIVERT >/dev/null 2>&1");
        shell_exec("$iptables -t mangle -D PREROUTING -p tcp -m tcp --dport 80 -m comment --comment HaCluster -j TPROXY --on-port 3154 --on-ip 127.0.0.1 --tproxy-mark 0x6f/0xffffffff");
        shell_exec("$iptables -t mangle -D PREROUTING -p tcp -m tcp --dport 443 -m comment --comment HaCluster -j TPROXY --on-port 3155 --on-ip 127.0.0.1 --tproxy-mark 0x6f/0xffffffff");
        shell_exec("$iptables -t mangle -D DIVERT -m comment --comment HaCluster -j MARK --set-xmark 111 >/dev/null 2>&1");
        shell_exec("$iptables -t mangle -D DIVERT -m comment --comment HaCluster -j ACCEPT >/dev/null 2>&1");
        shell_exec("$iptables -t nat -D POSTROUTING -j MASQUERADE -m comment --comment HaCluster >/dev/null 2>&1");

        $DETECTED=if_iptables_divert();
        _out("[STOPPING]: Transparent Service $DETECTED Firewall Rules");


    }

    $sources_white=sources_white();
    if(count($sources_white)>0){
        foreach ($sources_white as $ipaddr){
            _out("[STOPPING]: Transparent Service Removing $ipaddr from transparent method");
            shell_exec("$iptables -t mangle -D $ipaddr");

        }
    }



    if(if_ip_rule()){
        while(if_ip_rule()==true) {
            _out("[STOPPING]: Transparent Service Remove IP rule MARK 111");
            shell_exec("$ip rule del fwmark 111 lookup 100");
        }

    }

    if(if_table_tproxy()){
        _out("[STOPPING]: Transparent Service Clean table tproxy");
        shell_exec("$ip route del local 0.0.0.0/0 dev lo table 100");
    }

    $pid=start_transparent_pid();

    if(!$unix->process_exists($pid)){
        _out("[STOPPING]: Transparent Service instance Already stopped");
        return true;
    }

    $tempfile=$unix->FILE_TEMP();
    if($unix->process_exists($pid)){
        _out("[STOPPING]: Transparent Service instance pid $pid");
        shell_exec("/usr/sbin/hacluster-transparent -f /etc/hacluster/tproxy.conf -k shutdown >$tempfile 2>&1");
        if(stop_transparent_check_stopped($tempfile)){
            sleep(1);
        }
    }
    sleep(1);
    $pid=start_transparent_pid();
    if(!$unix->process_exists($pid)){
        _out("[STOPPING]: Transparent Service instance success");
        return stop_transparent_childs();
    }

    for($i=0;$i<5;$i++){
        _out("[STOPPING]: Transparent Service instance ($i/5)");
        shell_exec("/usr/sbin/hacluster-transparent -f /etc/hacluster/tproxy.conf -k shutdown >$tempfile 2>&1");
        if(stop_transparent_check_stopped($tempfile)){
            sleep(1);
        }
        $pid=start_transparent_pid();
        if(!$unix->process_exists($pid)){
            _out("[STOPPING]: Transparent Service instance success");
            return stop_transparent_childs();
        }
        sleep(1);

    }

    return false;

}
function stop_transparent_childs():bool{
    $unix=new unix();
    $pids=stop_transparent_check_childs();
    if(count($pids)==0){
        _out("[STOPPING]: Transparent Service instance success (no child)");
        return true;
    }

    while( count($pids)>0 ){
        foreach ($pids as $pid){
            _out("[STOPPING]: Transparent Service instance kill child pid $pid");
            $unix->KILL_PROCESS($pid,9);
        }
        sleep(1);
        $pids=stop_transparent_check_childs();
    }
    _out("[STOPPING]: Transparent Service instance kill child(s) OK");
    return true;
}
function start_transparent():bool{
    $HaClusterTransParentMode       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));
    $HaClusterTransParentMasquerade = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMasquerade"));

    if($HaClusterTransParentMode==0){
        _out("[STARTING] Transparent instance not enabled");
        if(is_file("/etc/init.d/hacluster-transparent")) {
            _out("Uninstall Transparent service..");
            start_transparent_uninstall_service();
        }

        return true;
    }

    $unix       = new unix();
    $ip         = $unix->find_program("ip");
    $iptables   = $unix->find_program("iptables");
    $squidbin   = $unix->LOCATE_SQUID_BIN();
    $EnableVLANs=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVLANs");
    if($EnableVLANs==1){$rpfilter=0;}

    _out("[RECONFIGURE]: Transparent Service Enable Kernel as Gateway");
    shell_exec("/usr/sbin/artica-phpfpm-service -firewall-tune >/dev/null 2>&1");


    $DETECTED = if_iptables_divert();
    _out("[RECONFIGURE]: Transparent Service $DETECTED detected rules");
    if ($DETECTED > 5) {
        $DETECTED = 1;
    }


    if ($DETECTED < 5) {
        if ($DETECTED > 0) {
            stop_transparent();
        }

        _out("[STARTING]: Transparent Service Add Firewall rules..");
        shell_exec("$iptables -t mangle -N DIVERT >/dev/null 2>&1");
        shell_exec("$iptables -t mangle -A PREROUTING -p tcp -m socket -j DIVERT -m comment --comment \"HaCluster\"");
        shell_exec("$iptables -t mangle -A DIVERT -j MARK --set-mark 111 -m comment --comment \"HaCluster\"");
        shell_exec("$iptables -t mangle -A DIVERT -j ACCEPT -m comment --comment \"HaCluster\"");

        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        $results=$q->QUERY_SQL("SELECT listen_ip FROM hacluster_backends");
        foreach ($results as $index=>$ligne){
            $listen_ip=$ligne["listen_ip"];
            _out("[STARTING]: Transparent Service whitelist $listen_ip");
            shell_exec("$iptables -t mangle -A PREROUTING -p tcp -s $listen_ip -j RETURN -m comment --comment \"HaCluster\"");
        }


        shell_exec("$iptables -t mangle -A PREROUTING -p tcp --dport 80 -j TPROXY --tproxy-mark 111 --on-port 3154 --on-ip {$GLOBALS["tproxy_ip"]} -m comment --comment \"HaCluster\"");
        shell_exec("$iptables -t mangle -A PREROUTING -p tcp --dport 443 -j TPROXY --tproxy-mark 111 --on-port 3155 --on-ip {$GLOBALS["tproxy_ip"]} -m comment --comment \"HaCluster\"");
    }


    if (!if_ip_rule()) {
        _out("[STARTING]: Transparent Service Add IP rule MARK 111");
        shell_exec("$ip rule add fwmark 111 lookup 100");

    }

    if (!if_table_tproxy()) {
        _out("[STARTING]: Transparent Service Populate table tproxy");
        shell_exec("$ip route add local 0.0.0.0/0 dev lo table 100");
    }

    if($HaClusterTransParentMasquerade==1){
        if (!if_masquerade()) {
            _out("[STARTING]: Transparent Service MASQUERADING");
            shell_exec("$iptables -t nat -A POSTROUTING -j MASQUERADE -m comment --comment \"HaCluster\"");
        }

    }else{
        _out("[STARTING]: Transparent Service MASQUERADING");
        shell_exec("$iptables -t nat -D POSTROUTING -j MASQUERADE -m comment --comment \"HaCluster\"");

    }
    start_transparent_squid_config();
    if(!is_file("/etc/init.d/hacluster-transparent")) {
        start_transparent_install_service();
    }
    if (is_file("/usr/sbin/hacluster-transparent")) { @unlink("/usr/sbin/hacluster-transparent");}
    @copy($squidbin, "/usr/sbin/hacluster-transparent");
    @chmod("/usr/sbin/hacluster-transparent", 0755);

    $pid = start_transparent_pid();
    if ( $unix->process_exists($pid) ) {
        $pidtime=$unix->PROCCESS_TIME_MIN($pid);
        _out("[STARTING]: Transparent Service instance already running PID: $pid since $pidtime minute(s)");
        return true;
    }

    @chown("/var/log/hacluster","squid");
    @chgrp("/var/log/hacluster","squid");
    if(is_file("/var/log/hacluster/hacluster-transparent.log")){
        @chown("/var/log/hacluster/hacluster-transparent.log","squid");
        @chgrp("/var/log/hacluster/hacluster-transparent.log","squid");
    }
    _out("[STARTING]: Transparent Service damemon transparent instance...");
    exec("/usr/sbin/hacluster-transparent -s -f /etc/hacluster/tproxy.conf 2>&1",$results_start);
    foreach ($results_start as $line){
        _out("[STARTING]: &laquo;$line&raquo;");
    }


    $pid = start_transparent_pid();
    if ( $unix->process_exists($pid) ) {
        _out("[STARTING]: Transparent Service instance Sucess PID $pid");
        return true;
    }

    _out("[STARTING]: Transparent Service instance FAILED !");
    return false;
}
function _out($text):bool{
    $addon=null;
    if($GLOBALS["MONIT"]){$addon=" (by monitor)";}
    echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} $text$addon\n";
    hacluster_syslog("$text$addon - Artica");
    return true;
}
function start_transparent_pid():int{
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/hacluster/squid.pid");
    if($unix->process_exists($pid )){return $pid;}
    return $unix->PIDOF("/usr/sbin/hacluster-transparent");
}

function if_masquerade():bool{
    $unix=new unix();
    $iptables_save=$unix->find_program("iptables-save");
    exec("$iptables_save 2>&1",$results);
    foreach ($results as $line) {
        if(preg_match("#-A POSTROUTING\s+.*?\s+-j MASQUERADE#",$line)){
            return true;
        }
    }
    return false;
}

function if_iptables_divert():int{

    $unix=new unix();
    $iptables_save=$unix->find_program("iptables-save");
    exec("$iptables_save 2>&1",$results);
    echo "Reconfiguring.: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Firewall ". count($results)." rules\n";
    $DETECTED=0;
    foreach ($results as $line) {
        $line = trim($line);
        if ($line == null) {
            continue;
        }

        if(preg_match("#-A PREROUTING -p tcp -m socket -m comment#i",$line)){
            $DETECTED++;
            continue;
        }

        if(preg_match("#-A DIVERT -m comment --comment HaCluster -j MARK --set-xmark#i",$line)){
            $DETECTED++;
            continue;
        }

        if(preg_match("#-A DIVERT -m comment --comment HaCluster -j ACCEPT#i",$line)){
            $DETECTED++;
            continue;
        }

        if(preg_match("#-A PREROUTING -p tcp -m tcp --dport 80 -m comment --comment HaCluster#",$line)){
            $DETECTED++;
        }
        if(preg_match("#-A PREROUTING -p tcp -m tcp --dport 443 -m comment --comment HaCluster#",$line)){
            $DETECTED++;
        }
    }

    return $DETECTED;


}

function if_table_tproxy():bool{
    $unix=new unix();
    $ip=$unix->find_program("ip");
    exec("$ip route show table 100 2>&1",$results);
    foreach ($results as $line) {
        $line = trim($line);
        if ($line == null) {
            continue;
        }
        if (preg_match("#local default dev lo#i", $line)) {
            return true;
        }
    }

    return false;
}

function if_ip_rule():bool{

    $unix=new unix();
    $ip=$unix->find_program("ip");
    exec("$ip rule show 2>&1",$results);

    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#from all fwmark 0x6f lookup tproxy#i",$line)){return true;}
    }
    return false;
}


function rsyslog_conf():bool{
    $unix=new unix();
    system("/usr/sbin/artica-phpfpm-service -reconfigure-syslog");
    return true;
}

function remove_limits():bool{
    $unix=new unix();
    $unix->SystemSecurityLimitsConf();
    return true;
}




