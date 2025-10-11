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
include_once(dirname(__FILE__)."/ressources/class.hadocker.inc");
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



function update():bool{
    $haproxy="/usr/local/sbin/haproxy";
    $Masterbin="/usr/local/sbin/hadocker";

    if(!is_file($haproxy)){
        _out("HaDocker installing latest version 2.8");
        if(!download_haproxy28()){
            return false;
        }
    }

    if(!haproxy_version_compatible()){
        _out("HaDocker Incompatible application: installing latest version 2.8");
        if(!download_haproxy28()){
            return false;
        }
    }
    if(!haproxy_version_compatible()){
        _out("HaDocker Incompatible application: installing latest version 2.8");
        return false;
    }

    $md51="";
    if(is_file($Masterbin)){
        $md51=md5_file($Masterbin);
    }
    $md52=md5_file($haproxy);
    if($md51==$md52) {
        return true;
    }
    @unlink($Masterbin);
    @copy($haproxy,$Masterbin);
    @chmod($Masterbin,0755);
    return true;

}

function download_haproxy28():bool{
    $haproxy="/usr/local/sbin/haproxy";
    $unix=new unix();
    $tempfile=$unix->FILE_TEMP().".tar.gz";

    _out("Downloading latest version 2.8");
    $curl=new ccurl("http://articatech.net/download/Debian10-haproxy/2.8.0.tar.gz");
    if(!$curl->GetFile($tempfile)){
        _out("Downloading latest version 2.8 failed with error ".$curl->error);
        return false;
    }
    _out("Extracting latest version 2.8...");
    $tar=$unix->find_program("tar");
    system("$tar xf $tempfile -C /");
    @unlink($tempfile);
    if(is_file($haproxy)){
        return true;
    }
    return false;
}
function haproxy_version_compatible():bool{
    $unix=new unix();
    $version=$unix->HaProxyVersion("/usr/local/sbin/haproxy");

    if(strlen($version)<2){
        _out("HaDocker unable to detect versioning: $version");
        return false;}
    if(!preg_match("#^([0-9]+)\.([0-9]+)#",$version,$re)){return false;}

    $Major=intval($re[1]);
    $Minor=intval($re[2]);
    _out("HaDocker source daemon: $Major.$Minor");
    if($Major>1){
        if($Minor>7){
            return true;
        }
    }
    return false;

}

function restart($aspid=false):bool{
    $unix=new unix();
    $Masterbin="/usr/local/sbin/hadocker";

    if(!is_file($Masterbin)){
        build_progress_restart(110,"{not_installed}");
        _out("Error, hadocker not installed");
        return false;
    }

    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            build_progress_restart(110,"{already_running}");
            _out("Already Artica task running PID $pid since {$time}mn");
            return false;
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
function build_progress_restart($pourc,$text):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"hadocker.progress");
}
function build_progress_stop($pourc,$text):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"hadocker-stop.progress");
}
function install():bool{
    $unix=new unix();
    build_progress_restart(20,"{installing}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDockerService", 1);
    $php=$unix->LOCATE_PHP5_BIN();
    build_progress_restart(30,"{installing}");
    hadocker_install_service();
    monit_config();


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
    return build_progress_restart(100,"{success}");
}


function monit_config():bool{
    $unix=new unix();
    $f=array();
    $monitconf="/etc/monit/conf.d/APP_hadocker.monitrc";
    $EnableDockerService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDockerService"));
    if($EnableDockerService==0){
        if(is_file($monitconf)){
            @unlink($monitconf);
            $unix->MONIT_RELOAD();
        }
        return false;
    }
    $php=$unix->LOCATE_PHP5_BIN();
    $f[]="check process APP_HADOCKER";
    $f[]="with pidfile /var/run/hadocker.pid";
    $f[]="start program = \"/etc/init.d/hadocker start --monit\"";
    $f[]="stop program =  \"/etc/init.d/hadocker stop --monit\"";
    $f[]="if 5 restarts within 5 cycles then timeout";
    $f[]="";
    $f[]="check file hadockerLog with path /var/log/hadocker.log";
    $f[]="\tif not exist then alert";
    $f[]="\tif size > 1500 MB then exec \"/usr/sbin/hadocker-rotate.sh\"";
    $md5=@md5_file($monitconf);
    $SCRIPT=array();
    $SCRIPT[]="#!/bin/sh";
    $SCRIPT[]="";
    $SCRIPT[]="$php /usr/share/artica-postfix/exec.hadocker.php --rotate";
    $SCRIPT[]="";
    @file_put_contents("/usr/sbin/hadocker-rotate.sh", @implode("\n", $SCRIPT));
    @chmod("/usr/sbin/hadocker-rotate.sh", 0755);
    @file_put_contents($monitconf, @implode("\n", $f));
    $md51=@md5_file($monitconf);
    if($md51<>$md5) {
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    }
    if(!is_file("/etc/cron.d/hadocker-rotate")){
        $unix->Popuplate_cron_make("hadocker-rotate","0 0 * * *","exec.hadocker.php --rotate");
    }


    return true;
}
function uninstall():bool{
    $unix=new unix();
    build_progress_restart(20,"{uninstalling}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Enablehadocker", 0);
    $unix->remove_service("/etc/init.d/hadocker");
    $unix->remove_service("/etc/init.d/hadocker-tail");
    @unlink("/etc/cron.d/hadocker-monthly");
    @unlink("/etc/cron.d/hadocker-yearly");
    @unlink("/etc/monit/conf.d/APP_HADOCKER.monitrc");
    @unlink("/etc/monit/conf.d/APP_HADOCKER_TAIL.monitrc");
    @unlink("/etc/cron.d/hadocker-rotate");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    shell_exec("/etc/init.d/cron reload");
    build_progress_restart(100,"{success}");
    return true;
}

function hadocker_install_service():bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/hadocker";
    $php5script="exec.hadocker.php";
    $daemonbinLog="Load-Balancer Daemon";



    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         artica-hadocker";
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
    return hadocker_tail_service();

}
function hadocker_tail_service():bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/hadocker-tail";
    $php5script="exec.hadocker-tail-init.php";
    $daemonbinLog="Load-Balancer tail Daemon";

    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         hadocker-tail";
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
    return true;

}
function reload():bool{
    if(!$GLOBALS["NOCONF"]){
        build_progress_restart(20,"{building_settings}");
        build();
    }
    if(!isRunning()){
        build_progress_restart(50,"{starting_service}");
        start(true);
        if(!isRunning()){build_progress_restart(110,"{starting_service} {failed}");return false;}
        build_progress_restart(100,"{starting_service} {success}");
        return true;
    }
    $unix=new unix();
    $hadocker=$unix->find_program("hadocker");
    $CONFIG="/etc/hadocker/hadocker.cfg";
    $PIDFILE="/var/run/hadocker.pid";
    $EXTRAOPTS=null;
    $pids=@implode(" ", pidsarr());
    build_progress_restart(90,"{reloading} $pids");

    $cmd="$hadocker -f \"$CONFIG\" -p $PIDFILE -D $EXTRAOPTS -sf $pids 2>&1";
    exec($cmd,$results);
    foreach ($results as $ligne){
        echo "Starting......: ".date("H:i:s")." {$GLOBALS["TITLENAME"]} $ligne\n";
    }
    build_progress_restart(100,"{reloading} {success}");
    return true;
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
    $f=file("/var/run/hadocker.pid");
    foreach ($f as $ligne){
        $ligne=trim($ligne);
        if(!is_numeric($ligne)){continue;}
        $R[]=$ligne;
    }
    return $R;
}
function build():bool {
    $DenyhadockerConf=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DenyhadockerConf"));

    if($DenyhadockerConf==1){
        return _out("Error, Configuration is blocked..");
    }
    $HaProxyMaxConn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterMaxConn"));
    $HaProxyCPUS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaProxyCPUS"));
    $HaProxyMemoryCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaProxyMemoryCache"));
    $HaProxyMaxMemoryObjects =intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaProxyMaxMemoryObjects"));
    if($HaProxyMaxMemoryObjects==0){$HaProxyMaxMemoryObjects=10000;}
    if($HaProxyMaxConn<2000){$HaProxyMaxConn=2000;}
    $unix=new unix();
    $unix->SystemSecurityLimitsConf();

    $f=array();
    $f[]="# CPU(s) $HaProxyCPUS";
    $f[]="global";
    $f[]="\tlog	127.0.0.1 local0";
    $f[]="\tmaxconn	$HaProxyMaxConn";

    if($HaProxyCPUS>1){
        $cpumap=$HaProxyCPUS-1;
        $f[]="\tnbthread           $HaProxyCPUS";
        $f[]="\tcpu-map auto:1/1-$HaProxyCPUS 0-$cpumap";
    }

    $f[]="\tuid	0";
    $f[]="\tgid	0";
    $f[]="\tchroot	/tmp";
    $f[]="\tulimit-n          2010000";
    $f[]="\tstats socket /var/run/hadocker.stat mode 600 level admin";
    $f[]="\tdaemon";
    $f[]="#\tdebug";
    $f[]="#\tquiet";
    $f[]="";
    $f[]="defaults";
    $f[]="\tlog\tglobal";
    $f[]="\t".logformat();
    $f[]="\tmode\thttp";
    $f[]="\toption\tdontlognull";
    $f[]="\tretries\t3";
    $f[]="\toption\tredispatch";
    $f[]="\tmaxconn\t$HaProxyMaxConn";
    $f[]="\ttimeout connect\t5000";
    $f[]="\ttimeout client\t50000";
    $f[]="\ttimeout server\t50000";
    $f[]="";

    if($HaProxyMemoryCache>0) {
        $f[] = "cache memorycache";
        $f[] = "\ttotal-max-size $HaProxyMemoryCache";
        $f[] = "\tmax-object-size $HaProxyMaxMemoryObjects";
        $f[] = "\tmax-age 7200";
        $f[] = "";
    }

    $f[] = "frontend http_port";
    $f[] = "\tbind\t0.0.0.0:80 name http_port";
    $f[]= "\tcapture request header Host len 1024";
    $f[]= "\tcapture request header Content-Type len 1024";
    $f[]= "\tcapture request header User-Agent len 1024";
    $f[]= "\tcapture request header Referer len 1024";
    $f[]= "\tcapture request header X-Forwarded-For len 1024";
    $f[]= "\tcapture response header Content-Type len 1024";
    $f[]= "\tcapture cookie Cookie_2 len 100";
    $f[]= "\thttp-request set-header mode mode:http";
    $f[]= "\thttp-request capture hdr(mode)  len 10";

    $f[]="frontend admin_page";
    $f[]="\tbind\t127.0.0.1:64748";
    $f[]="\tmode http";
    $f[]="\tstats enable";
    $f[]="\tstats refresh 10s";
    $f[]="\tstats uri /stats";
    $f[]="";
    $f[]="";
    if(!is_dir("/etc/hadocker")){
        @mkdir("/etc/hadocker",755,true);
    }
    @file_put_contents("/etc/hadocker/hadocker.cfg", @implode("\n", $f));
    rsyslog_conf();
    return true;
}
function logformat():string{
    $f[]="%Ts";
    $f[]="%ci"; //client_ip
    $f[]="%H"; //hostname;
    $f[]="%b"; //backend_name;
    $f[]="%bi"; //backend_source_ip;
    $f[]="%bp"; //backend_source_port;
    $f[]="%s"; //server_name
    $f[]="%si"; //server_IP
    $f[]="%sp"; //server_port
    $f[]="%r"; //http_request
    $f[]="%ST"; // status_code;
    $f[]="%B"; //bytes_read
    $f[]="%U"; //bytes_uploaded
    $f[]="%ts"; //Terminaison state
    return "log-format HADOCKS:::".@implode(":::", $f);

}

function UDPServerRun():bool{
    $f=explode("\n",@file_get_contents("/etc/rsyslog.conf"));
    foreach ($f as $ligne){
        $ligne=trim($ligne);
        if(substr($ligne, 0,1)=="#"){continue;}
        if(!preg_match("#UDPServerRun#", $ligne)){continue;}
        return true;
    }
    return false;
}
function _out($text):bool{
    echo "Service.......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $text\n";
    $LOG_SEV = LOG_INFO;
    $text="[HADOCKER]: $text";
    if (!function_exists("openlog")) {return false;}
    openlog("docker", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}

function start($aspid=false):bool{
    $unix=new unix();
    $Masterbin="/usr/local/sbin/hadocker";


    if(!update()){
       _out("HaDocker not installed");
        build_progress_stop(110,"{failed}");
        return false;
    }

    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            _out("Already Artica task running PID $pid since {$time}mn");
            return build_progress_stop(110,"{failed}");
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();

    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        _out("Service already started $pid since {$timepid}Mn...");
        return build_progress_stop(100,"{success}");

    }
    $EnableDockerService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDockerService"));

    if(!is_file("/etc/hadocker/hadocker.cfg")){
        if(!build()){
            _out("Building configuration failed");
            return false;
        }
        _out("/etc/hadocker/hadocker.cfg no such file");
        build_progress_stop(110,"{failed}");
        return false;
    }

    if($EnableDockerService==0){
        _out("Service disabled (see EnableDockerService)");
        build_progress_stop(110,"{failed} {disabled}");
        return false;
    }

    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");


    if(!UDPServerRun()){
        _out("Syslog server is not ready, prepare it");
        system("/usr/sbin/artica-phpfpm-service -reconfigure-syslog");
        if(UDPServerRun()){
            $unix=new unix();$unix->RESTART_SYSLOG(true);
        }else{
            _out("Failed to prepare syslog engine");
        }
    }else{
        _out("Syslog server [OK]");
    }
    build_progress_stop(50,"{starting} service...");
    $cmd="$nohup $Masterbin -f /etc/hadocker/hadocker.cfg -D -p /var/run/hadocker.pid  >/dev/null 2>&1 &";
    monit_config();
    shell_exec($cmd);

    $prc=50;
    for($i=1;$i<5;$i++){
        build_progress_restart(95,"{starting_service} $i/5");
        build_progress_stop($prc++,"{starting_service} $i/5");
       _out("Starting waiting $i/5");
        sleep(1);
        $pid=PID_NUM();
        if($unix->process_exists($pid)){break;}
    }

    $pid=PID_NUM();
    if($unix->process_exists($pid)){
        _out("Success PID $pid");
        build_progress_stop(100,"{starting_service} {success}");
    }else{
        _out("Starting Failed");
        _out("Failed with $cmd");
        build_progress_stop(110,"{starting_service} {failed}");
        return false;
    }

    _out("Starting Success !");
    return build_progress_stop(100,"{starting_service} {success}");
}
function PID_NUM():int{
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/hadocker.pid");
    if($unix->process_exists($pid)){return $pid;}
    $Masterbin="/usr/local/sbin/hadocker";
    return $unix->PIDOF($Masterbin);
}

function stop($aspid=false):bool{
    $unix=new unix();
    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            _out("service Already Artica task running PID $pid since {$time}mn");
            build_progress_stop(110,"{failed}");
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();


    if(!$unix->process_exists($pid)){
        _out("Service already stopped...");
        return build_progress_stop(100,"{success}");
    }
    $pid=PID_NUM();
   
    build_progress_stop(50,"{stopping_service} pid:$pid");
   _out("Service Shutdown pid $pid...");

    unix_system_kill($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        _out("Service waiting pid:$pid $i/5...");
        sleep(1);
    }

    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        _out("service success...");
        return build_progress_stop(100,"{stopping_service} {success}");

    }

    _out("service shutdown - force - pid $pid...");
    unix_system_kill_force($pid);
    build_progress_stop(60,"{stopping_service} pid:$pid");
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        _out("Service waiting pid:$pid $i/5...");
        sleep(1);
    }

    if($unix->process_exists($pid)){
        _out("Service failed...");
        build_progress_stop(110,"{stopping_service} pid:$pid {failed}");
        return false;
    }
    return build_progress_stop(100,"{stopping_service} {success}");
}
function rsyslog_conf():bool{
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    shell_exec("$nohup /usr/sbin/artica-phpfpm-service -reconfigure-syslog >/dev/null 2>&1 &");
    return true;
}
function rotate(){
    $unix=new unix();
    $echo=$unix->find_program("echo");
    $php=$unix->LOCATE_PHP5_BIN();
    $LogRotatePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath");
    $BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
    if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
    if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}

    $MeDir="$BackupMaxDaysDir/hadocker";
    $LastRotate=$unix->file_time_min("/etc/artica-postfix/pids/hadocker-rotate-cache.time");
    $SquidLogRotateFreq=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogRotateFreq"));
    if($SquidLogRotateFreq<10){$SquidLogRotateFreq=1440;}

    $MUSTROTATE=true;
    if($GLOBALS["FORCE"]){$MustRotateAt=0;$MUSTROTATE=true;}
    if($GLOBALS["PROGRESS"]){$MustRotateAt=0;$MUSTROTATE=true;}
    $BackupSquidLogsUseNas=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas");

    $size=@filesize("/var/log/hadocker.log");
    $ROTATED=false;
    $size=$size/1024;
    $size=round($size/1024);

    $partition=$unix->DIRPART_INFO($LogRotatePath);
    $AIV=$partition["AIV"];
    $AIV=$AIV/1024;
    $AIV=round($AIV/1024);
    if($size>$AIV){
        squid_admin_mysql(0,"[LOG ROTATION]: hadocker {$size}M > available size ({$AIV}M) [action=remove log file]","/var/log/hadocker.log = {$size}MB ");
        @unlink("/var/log/hadocker.log");
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }

    if(!$MUSTROTATE){
        if($LastRotate<$SquidLogRotateFreq){
            if($size>=$MustRotateAt){$MUSTROTATE=true;}
        }
    }


    $suffix_time=time();
    if(!$MUSTROTATE){return;}

    squid_admin_mysql(2,"hadocker Backup source file","/var/log/hadocker.log = {$size}MB ");
    if(!@copy("/var/log/hadocker.log", "$LogRotatePath/hadocker.log.$suffix_time")){
        @unlink("$LogRotatePath/hadocker.log.$suffix_time");
        squid_admin_mysql(0, "[LOG ROTATION]: Unable to duplicate source log!", "/var/log/hadocker.log -> $LogRotatePath/hadocker.log.$suffix_time ",__FILE__,__LINE__);
        return;
    }

    shell_exec("$echo \"\" >/var/log/hadocker.log");
    $targetfile="$MeDir/hadocker-".date("Y-m-d-H-i").".gz";
    if(!$unix->compress("$LogRotatePath/hadocker.log.$suffix_time", $targetfile)){
        squid_admin_mysql(0, "[LOG ROTATION]: Unable to compress source log!", "$LogRotatePath/hadocker.log.$suffix_time -> $targetfile",__FILE__,__LINE__);
        return;

    }

    squid_admin_mysql(2,"Backup source file {success}","$LogRotatePath/hadocker.log.$suffix_time",__FILE__,__LINE__);

    if($BackupSquidLogsUseNas==1){
        shell_exec("$php /usr/share/artica-postfix/exec.squid.rotate.php --backup-hadocker");
    }

}


