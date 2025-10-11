<?php
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
    $Masterbin=$unix->find_program("haproxy");

    if(!is_file($Masterbin)){
        build_progress_restart(110,"{not_installed}");
        if($GLOBALS["OUTPUT"]){echo "ReStarting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, haproxy not installed\n";}
        return;
    }

    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            build_progress_restart(110,"{already_running}");
            if($GLOBALS["OUTPUT"]){echo "ReStarting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
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
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/haproxy.progress", serialize($array));

    if($GLOBALS["WIZARD"]){
        $array["POURC"]=25;
        $array["TEXT"]=$text;
        @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/wizard.progress", serialize($array));
    }

    @chmod("/usr/share/artica-postfix/ressources/logs/web/haproxy.progress",0755);


}
function build_progress_stop($pourc,$text){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/haproxy-stop.progress", serialize($array));
    @chmod("/usr/share/artica-postfix/ressources/logs/web/haproxy-stop.progress.txt",0755);


}
function install(){
    $unix=new unix();
    build_progress_restart(20,"{installing}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableHaProxy", 1);

    $EnableHaProxyWizardRun=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaProxyWizardRun"));
    $php=$unix->LOCATE_PHP5_BIN();


    if($EnableHaProxyWizardRun==1){
        if(!install_wizard()){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableHaProxy", 1);
            build_progress_restart(110,"{failed}");
            return;
        }
    }

    build_progress_restart(30,"{installing}");
    haproxy_install_service();
    monit_config();




    $f=array();
    $f[]="check process APP_HAPROXY_TAIL";
    $f[]="with pidfile /var/run/haproxy-tail.pid";
    $f[]="start program = \"/etc/init.d/haproxy-tail start --monit\"";
    $f[]="stop program =  \"/etc/init.d/haproxy-tail stop --monit\"";
    $f[]="if 5 restarts within 5 cycles then timeout";
    @file_put_contents("/etc/monit/conf.d/APP_HAPROXY_TAIL.monitrc", @implode("\n", $f));
    $f=array();
    //********************************************************************************************************************
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
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
    system("$php /usr/share/artica-postfix/exec.logrotate.php --reconfigure");
    build_progress_restart(100,"{success}");
    sleep(1);
    build_progress_restart(100,"{success}");
    sleep(1);
    build_progress_restart(100,"{success}");
}


function monit_config(){
    $unix=new unix();
    $f=array();
    $monitconf="/etc/monit/conf.d/APP_HAPROXY.monitrc";
    $EnableHaProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaProxy"));
    if($EnableHaProxy==0){
        if(is_file($monitconf)){
            @unlink($monitconf);
            shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
        }
        return false;
    }
    $php=$unix->LOCATE_PHP5_BIN();
    $f[]="check process APP_HAPROXY";
    $f[]="with pidfile /var/run/haproxy.pid";
    $f[]="start program = \"/etc/init.d/haproxy start --monit\"";
    $f[]="stop program =  \"/etc/init.d/haproxy stop --monit\"";
    $f[]="if 5 restarts within 5 cycles then timeout";
    $f[]="";
    $f[]="check file HAProxyLog with path /var/log/haproxy.log";
    $f[]="\tif not exist then alert";
    $f[]="\tif size > 1500 MB then exec \"/usr/sbin/haproxy-rotate.sh\"";
    $md5=@md5_file($monitconf);
    $SCRIPT=array();
    $SCRIPT[]="#!/bin/sh";
    $SCRIPT[]="";
    $SCRIPT[]="$php /usr/share/artica-postfix/exec.haproxy.php --rotate";
    $SCRIPT[]="";
    @file_put_contents("/usr/sbin/haproxy-rotate.sh", @implode("\n", $SCRIPT));
    @chmod("/usr/sbin/haproxy-rotate.sh", 0755);
    @file_put_contents($monitconf, @implode("\n", $f));
    $md51=@md5_file($monitconf);
    if($md51<>$md5) {
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    }
    if(!is_file("/etc/cron.d/haproxy-rotate")){
        $unix->Popuplate_cron_make("haproxy-rotate","0 0 * * *","exec.haproxy.php --rotate");
    }


    return true;
}

function install_wizard(){

    $EnableHaProxyWizard=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaProxyWizard"));
    $local_port=$EnableHaProxyWizard["HA_PORT"];
    echo "Local Port..............: $local_port\n";

    $MAINARRAY=array();
    foreach ($EnableHaProxyWizard["BACKENDS"] as $server=>$val){
        build_progress_restart(30,"{notify} $server");
        $remote_port=install_wizard_remote_port($server);

        if($remote_port==0){echo "Notify $server failed...but continue anyway\n";}
        echo "Local Port..............: $local_port\n";
        echo "$server......: Proxy port:$remote_port\n";
        if(preg_match("#^(.+?):([0-9]+)#", $server,$re)){
            $MAINARRAY[$re[1]]=$remote_port;
        }

    }
    $q=new mysql();
    $sql="SELECT servicename from haproxy WHERE listen_port='$local_port'";
    $ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
    if(trim($ligne["servicename"])==null){
        build_progress_restart(35,"{build_settings} {listen_port} $local_port");
        $ha=new haproxy_multi("HTTP_Proxies_Balancer");
        $ha->dispatch_mode="leastconn";
        $ha->loadbalancetype=2;
        $ha->enabled=1;
        $ha->MainConfig["NTLM_COMPATIBILITY"]=1;
        $ha->MainConfig["asSquidArtica"]=1;
        $ha->listen_port=$local_port;
        $ha->listen_ip="0.0.0.0";
        if(!$ha->save()){
            return false;
        }
    }

    $c=0;
    foreach ($MAINARRAY as $server=>$port){
        $c++;
        $servername=str_replace(".", "_", $server);
        build_progress_restart(35,"{build_settings} $server:$port");
        $hap=new haproxy_backends("HTTP_Proxies_Balancer", "{$servername}_$port");
        if(!is_numeric($hap->MainConfig["inter"])){$hap->MainConfig["inter"]=60000;}
        if(!is_numeric($hap->MainConfig["fall"])){$hap->MainConfig["fall"]=3;}
        if(!is_numeric($hap->MainConfig["rise"])){$hap->MainConfig["rise"]=2;}
        if(!is_numeric($hap->MainConfig["maxconn"])){$hap->MainConfig["maxconn"]=10000;}
        $hap->listen_ip=$server;
        $hap->listen_port=$port;
        $hap->bweight=$c;
        $hap->MainConfig["asSquidArtica"]=1;


        if(!$hap->save()){return false;}

    }

    reset($EnableHaProxyWizard["BACKENDS"]);
    foreach ($EnableHaProxyWizard["BACKENDS"] as $server=>$val){
        build_progress_restart(40,"{notify} {reconfigure} $server");
        $url="https://$server/haproxy.listener.php?haproxy-reconfigure=yes";
        $curl=new ccurl($url);
        $curl->Timeout=10;
        $curl->NoLocalProxy();
        $curl->noproxyload=true;
        $curl->NoHTTP_POST=true;
    }

    if(!preg_match("#<MY_PORT>([0-9]+)</MY_PORT>#", $curl->data,$re)){
        echo "Protocol error on My port\n";
    }
    return true;
}

function install_wizard_remote_port($server){


    $url="https://$server/haproxy.listener.php?haproxy-port=yes";
    $curl=new ccurl($url);
    $curl->Timeout=20;
    $curl->NoLocalProxy();
    $curl->noproxyload=true;
    $curl->NoHTTP_POST=true;

    if(!$curl->get()){
        echo $curl->error."\n";
        return 0;
    }

    if(!preg_match("#<MY_PORT>([0-9]+)</MY_PORT>#s", $curl->data,$re)){

        if(preg_match("#<ERROR>(.+?)</ERROR>#s", $curl->data,$re)){
            echo "Error from $server\n{$re[1]}\n";
        }
        return 0;
    }

    return $re[1];



}

function uninstall(){
    build_progress_restart(20,"{uninstalling}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableHaProxy", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("AsHapProxyAppliance", 0);
    remove_service("/etc/init.d/haproxy");
    remove_service("/etc/init.d/haproxy-tail");
    @unlink("/etc/cron.d/haproxy-monthly");
    @unlink("/etc/cron.d/haproxy-yearly");
    @unlink("/etc/monit/conf.d/APP_HAPROXY.monitrc");
    @unlink("/etc/monit/conf.d/APP_HAPROXY_TAIL.monitrc");
    @unlink("/etc/cron.d/haproxy-rotate");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    shell_exec("/etc/init.d/cron reload");

    $munin=new munin_plugins();
    $munin->build();


    build_progress_restart(100,"{success}");
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
function haproxy_install_service(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/haproxy";
    $php5script="exec.haproxy.php";
    $daemonbinLog="Load-Balancer Daemon";



    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         artica-haproxy";
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
    haproxy_tail_service();

}
function haproxy_tail_service(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/haproxy-tail";
    $php5script="exec.haproxy-tail-init.php";
    $daemonbinLog="Load-Balancer tail Daemon";



    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         haproxy-tail";
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
    $unix=new unix();
    $HAPROXY=$unix->find_program("haproxy");
    $CONFIG="/etc/haproxy/haproxy.cfg";
    $PIDFILE="/var/run/haproxy.pid";
    $EXTRAOPTS=null;
    $pids=@implode(" ", pidsarr());
    build_progress_restart(90,"{reloading} $pids");

    $cmd="$HAPROXY -f \"$CONFIG\" -p $PIDFILE -D $EXTRAOPTS -sf $pids 2>&1";
    exec($cmd,$results);
    foreach ($results as $ligne){
        echo "Starting......: ".date("H:i:s")." {$GLOBALS["TITLENAME"]} $ligne\n";
    }
    build_progress_restart(100,"{reloading} {success}");
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

function pidsarr():array{
    $R=array();
    $f=file("/var/run/haproxy.pid");
    foreach ($f as $ligne){
        $ligne=trim($ligne);
        if(!is_numeric($ligne)){continue;}
        $R[]=$ligne;
    }
    return $R;
}



function build():bool{

    $DenyHaproxyConf=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DenyHaproxyConf"));
    remove_limits();
    if($DenyHaproxyConf==1){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, Configuration is blocked..\n";}
        return false;
    }


    $hap=new haproxy();
    $conf=$hap->buildconf();
    @unlink("/etc/haproxy/haproxy.cfg");
    if(trim($conf)==null){return false;}
    if(!is_dir("/etc/haproxy")) {
        @mkdir("/etc/haproxy", 0755, true);
    }
    @file_put_contents("/etc/haproxy/haproxy.cfg", $conf);
    Transparents_modes();
    rsyslog_conf();
    return true;

}
function remove_limits():bool{
    $unix=new unix();
    $unix->SystemSecurityLimitsConf();
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

function start($aspid=false){
    $unix=new unix();
    $sock=new sockets();
    $Masterbin=$unix->find_program("haproxy");

    if(!is_file($Masterbin)){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, haproxy not installed\n";}
        build_progress_stop(110,"{failed}");
        return;
    }

    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
            build_progress_stop(110,"{failed}");
            return;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();

    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
        build_progress_stop(100,"{success}");
        return;
    }
    $EnableHaProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaProxy"));

    if(!is_file("/etc/haproxy/haproxy.cfg")){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/haproxy/haproxy.cfg no such file\n";}
        build_progress_stop(110,"{failed}");
        return;
    }

    if($EnableHaProxy==0){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableHaProxy)\n";}
        build_progress_stop(110,"{failed} {disabled}");
        return;
    }

    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");


    if(!UDPServerRun()){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} syslog server is not ready, prepare it\n";}
        system("/usr/sbin/artica-phpfpm-service -reconfigure-syslog");
        if(UDPServerRun()){
            $unix=new unix();$unix->RESTART_SYSLOG(true);
        }else{
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed to prepare syslog engine\n";}
        }
    }else{
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} syslog server [OK]\n";}
    }
    build_progress_stop(50,"{starting}");
    $cmd="$nohup $Masterbin -f /etc/haproxy/haproxy.cfg -D -p /var/run/haproxy.pid  >/dev/null 2>&1 &";

    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
    monit_config();
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

    $pid=PID_NUM();
    if($unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
        build_progress_stop(100,"{starting_service} {success}");
    }else{
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
        build_progress_stop(110,"{starting_service} {failed}");
        return false;
    }

    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success !\n";}
    build_progress_stop(100,"{starting_service} {success}");
    return true;

}



function PID_NUM(){

    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/haproxy.pid");
    if($unix->process_exists($pid)){return $pid;}
    $Masterbin=$unix->find_program("haproxy");
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
        build_progress_stop(100,"{stopping_service} {success}");
        return;
    }

    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
    unix_system_kill_force($pid);
    build_progress_stop(60,"{stopping_service} pid:$pid");
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
        sleep(1);
    }

    if($unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
        build_progress_stop(110,"{stopping_service} pid:$pid {failed}");
        return;
    }
    build_progress_stop(100,"{stopping_service} {success}");

}

function Transparents_modes(){
    // Depreciated function for the moment
    return true;
    iptables_delete_all();
    $unix=new unix();
    $iptables=$unix->find_program("iptables");
    $sysctl=$unix->find_program("sysctl");
    $sql="SELECT * FROM haproxy WHERE enabled=1 AND transparent=1";
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." {$GLOBALS["TITLENAME"]} building configuration failed $q->mysql_error Transparents_modes()\n";return;}}
    if(count($results)==0){
        echo "Starting......: ".date("H:i:s")." {$GLOBALS["TITLENAME"]} building configuration no transparent configurations...\n";
        return;
    }
    shell_exec("$sysctl -w net.ipv4.ip_forward=1 2>&1");
    shell_exec("$sysctl -w net.ipv4.conf.default.send_redirects=0 2>&1");
    shell_exec("$sysctl -w net.ipv4.conf.all.send_redirects=0 2>&1");
    shell_exec("$sysctl -w net.ipv4.conf.eth0.send_redirects=0 2>&1");
    shell_exec("$iptables -P FORWARD ACCEPT");

    return;

    while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
        $listen_add="127.0.0.1";
        $next_port=$ligne["listen_port"];
        $listen_ip=$ligne["listen_ip"];
        $transparent_port=$ligne["transparentsrcport"];
        if($transparent_port<1){continue;}
        echo "Starting......: ".date("H:i:s")." {$GLOBALS["TITLENAME"]} building configuration transparent request from $listen_ip:$transparent_port and redirect to $listen_add:$next_port\n";

        shell_exec2("$iptables -t nat -A PREROUTING -i eth0 -p tcp --dport $transparent_port -j ACCEPT -m comment --comment \"ArticaHAProxy\"");
        shell_exec2("$iptables -t nat -A PREROUTING -p tcp --dport $transparent_port -j REDIRECT --to-ports $next_port -m comment --comment \"ArticaHAProxy\"");
        shell_exec2("$iptables -t nat -A POSTROUTING -j MASQUERADE -m comment --comment \"ArticaHAProxy\"");
        shell_exec2("$iptables -t mangle -A PREROUTING -p tcp --dport $next_port -j DROP -m comment --comment \"ArticaHAProxy\"");
    }

}

function shell_exec2($cmd){
    echo "Starting......: ".date("H:i:s")." {$GLOBALS["TITLENAME"]} $cmd\n";
    shell_exec($cmd);

}

function rsyslog_conf(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    shell_exec("$nohup /usr/sbin/artica-phpfpm-service -reconfigure-syslog 2>&1 &");



}

function iptables_delete_all(){
    $unix=new unix();
    $iptables_save=$unix->find_program("iptables-save");
    $iptables_restore=$unix->find_program("iptables-restore");
    system("$iptables_save > /etc/artica-postfix/iptables.conf");
    $data=file_get_contents("/etc/artica-postfix/iptables.conf");
    $datas=explode("\n",$data);
    $conf=null;
    $pattern="#.+?ArticaHAProxy#";
    foreach ($datas as $num=>$ligne){
        if($ligne==null){continue;}
        if(preg_match($pattern,$ligne)){continue;}
        $conf=$conf . $ligne."\n";
    }

    file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
    system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
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

    $partition=$unix->DIRPART_INFO($LogRotatePath);
    $AIV=$partition["AIV"];
    $AIV=$AIV/1024;
    $AIV=round($AIV/1024);
    if($size>$AIV){
        squid_admin_mysql(0,"[LOG ROTATION]: Haproxy {$size}M > available size ({$AIV}M) [action=remove log file]","/var/log/haproxy.log = {$size}MB ");
        @unlink("/var/log/haproxy.log");
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }

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


