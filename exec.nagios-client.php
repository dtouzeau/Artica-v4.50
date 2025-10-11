<?php
$GLOBALS["TITLENAME"]="Nagios Client";
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.nginx.certificate.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--build"){build();exit;}


function uninstall(){
    build_progress(25, "{uninstalling}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableNagiosClient",0);
    $unix=new unix();
    $unix->remove_service("/etc/init.d/ncpa");
    $php=$unix->LOCATE_PHP5_BIN();
    $monit_file = "/etc/monit/conf.d/APP_NAGIOS_CLIENT.monitrc";
    build_progress(50, "{uninstalling}");
    if(is_file($monit_file)){
        @unlink($monit_file);
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    }
    $other[]="/usr/local/ncpa/var/log/ncpa_passive.log";
    $other[]="/usr/local/ncpa/var/log/ncpa_listener.log";
    $other[]="/etc/rsyslog.d/00_nagios.conf";
    foreach ($other as $fpath){
        if(is_file($fpath)){
            @unlink($fpath);
        }
    }

    $f[]="/etc/init.d/ncpa_listener";
    $f[]="/etc/init.d/ncpa_passive";
    foreach ($f as $fpath){
        if(is_file($fpath)){
            $unix->remove_service($fpath);
        }
    }


    build_progress(100, "{uninstalling} {success}");
}

function ssl_certificate(){
    $UfdbUseInternalServiceCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalServiceCertificate"));

    if($UfdbUseInternalServiceCertificate==null){
        _out("INFO: No ssl certificate defined...");
        if(is_file("/home/artica/web_templates/cert.pm")) {@unlink("/home/artica/web_templates/cert.pm");}
        if(is_file("/home/artica/web_templates/key.pm")) {@unlink("/home/artica/web_templates/key.pm");}
        return true;
    }
    $nginx=new nginx_certificate($UfdbUseInternalServiceCertificate);
    $nginx->GetConf();
    $cert_path=$nginx->ssl_certificate_path;
    $key_path=$nginx->ssl_certificate_key;

    if($GLOBALS["VERBOSE"]){
        echo "Copy: $key_path -> \"/home/artica/web_templates/key.pm\"\n";
        echo "Copy: $cert_path -> \"/home/artica/web_templates/cert.pm\"\n";
    }

    if(!is_file($cert_path)){
        _out("INFO:$cert_path no such file, cannot generate SSL");
        return true;}
    if(!is_file($key_path)){
        _out("INFO:$key_path no such file, cannot generate SSL");
        return true;}
    if(is_file("/home/artica/web_templates/cert.pm")) {@unlink("/home/artica/web_templates/cert.pm");}
    if(is_file("/home/artica/web_templates/key.pm")) {@unlink("/home/artica/web_templates/key.pm");}
    _out("SSL Certificate: $UfdbUseInternalServiceCertificate web_templates/cert.pm and web_templates/key.pm success");
    @copy($cert_path,"/home/artica/web_templates/cert.pm");
    @copy($key_path,"/home/artica/web_templates/key.pm");

    return true;
}

function install():bool{

    build_progress(25, "{installing}");
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();

    if(!$unix->SystemCreateUser("nagios","nagios")){
        build_progress(110, "{installing} {failed} - creating users");
        return false;
    }

    $f[]="/etc/init.d/ncpa_listener";
    $f[]="/etc/init.d/ncpa_passive";
    foreach ($f as $fpath){
        if(is_file($fpath)){
            $unix->remove_service($fpath);
        }
    }


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableNagiosClient",1);
    $unix->create_service("ncpa",__FILE__);
    build_progress(40, "{installing} {reconfiguring}");
    shell_exec("$php /usr/share/artica-postfix/exec.nginx.single.php --web-error-pages");
    build_progress(50, "{installing} {starting_service}");
    start(true);
    return build_progress(100, "{installing} {success}");
}
function LISTENER_PID():int{
   $unix=new unix();
   $pid=$unix->get_pid_from_file("/usr/local/ncpa/var/run/ncpa_listener.pid");
   if($unix->process_exists($pid)){return $pid;}
   return 0;
}
function PASSIVE_PID():int{
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/usr/local/ncpa/var/run/ncpa_passive.pid");
    if($unix->process_exists($pid)){return $pid;}
    return 0;
}


function build(){
    build_syslog();
    build_monit();
    $unix=new unix();
    $NagiosClientInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosClientInterface"));
    $NagiosClientPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosClientPort"));
    $NagiosAdminPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosAdminPassword"));
    $NagiosCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosCertificate"));

    $NagiosAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NagiosAPIKey"));
    if($NagiosAPIKey==null){$NagiosAPIKey="mytoken";}

    if($NagiosClientPort==0){$NagiosClientPort=5693;}
    if($NagiosAdminPassword==null){$NagiosAdminPassword="None";}

    $NagiosClientIP="0.0.0.0";
    if($NagiosClientInterface<>null){
        $NagiosClientIP=$unix->InterfaceToIPv4($NagiosClientInterface);
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NagiosClientIP",$NagiosClientIP);


    $f[]="[general]";
    $f[]="check_logging = 1";
    $f[]="check_logging_time = 30";
    $f[]="all_partitions = 1";
    $f[]="exclude_fs_types = aufs,autofs,binfmt_misc,cifs,cgroup,configfs,debugfs,devpts,devtmpfs,encryptfs,efivarfs,fuse,fusectl,hugetlbfs,mqueue,nfs,overlayfs,proc,pstore,rpc_pipefs,securityfs,selinuxfs,smb,sysfs,tmpfs,tracefs,nfsd,xenfs";
    $f[]="default_units = Gi";
    $f[]="";
    $f[]="[listener]";
    $f[]="uid = nagios";
    $f[]="gid = nagios";
    $f[]="ip = $NagiosClientIP";
    $f[]="port = $NagiosClientPort";
    $f[]="";
    $f[]="ssl_version = TLSv1_2";
    if($NagiosCertificate==null) {
        $f[] = "certificate = adhoc";
    }else {
        $cert = new nginx_certificate($NagiosCertificate);
        $cert->GetConf();
        $cert_path = $cert->ssl_certificate_path;
        $key_path = $cert->ssl_certificate_key;
        $f[] = "certificate = $cert_path,$key_path";
    }
    $f[]="loglevel = info";
    $f[]="logfile = var/log/ncpa_listener.log";
    $f[]="pidfile = var/run/ncpa_listener.pid";
    $f[]="admin_gui_access = 1";
    $f[]="admin_password = $NagiosAdminPassword";
    $f[]="admin_auth_only = 0";
    $f[]="";
    $f[]="#";
    $f[]="# Comma separated list of allowed hosts that can access the API (and GUI)";
    $f[]="# Supported types: IPv4, IPv4-mapped IPv6, IPv6, hostnames";
    $f[]="# Hostname wildcards are not supported.";
    $f[]="#";
    $f[]="# Exmaple IPv4: 192.168.23.15";
    $f[]="# Example IPv4 subnet: 192.168.0.0/28";
    $f[]="# Example IPv4-mapped IPv6: ::ffff:192.168.1.15";
    $f[]="# Example IPv6: 2001:0db8:85a3:0000:0000:8a2e:0370:7334";
    $f[]="# Example hostname: asterisk.mydomain.com";
    $f[]="# Example mixed types: 192.168.23.15, 192.168.0.0/28, ::ffff:192.168.1.15, 2001:0db8:85a3:0000:0000:8a2e:0370:7334, asterisk.mydomain.com";
    $f[]="#";
    $f[]="# allowed_hosts =";
    $f[]="";
    $f[]="";
    $f[]="[api]";
    $f[]="community_string = $NagiosAPIKey";
    $f[]="";
    $f[]="#";
    $f[]="# -------------------------------";
    $f[]="# Passive Configuration (daemon)";
    $f[]="# -------------------------------";
    $f[]="#";
    $f[]="";
    $f[]="[passive]";
    $f[]="";
    $f[]="handlers = None";
    $f[]="uid = nagios";
    $f[]="gid = nagios";
    $f[]="sleep = 300";
    $f[]="loglevel = info";
    $f[]="logfile = var/log/ncpa_passive.log";
    $f[]="pidfile = var/run/ncpa_passive.pid";
    $f[]="";
    $f[]="[nrdp]";
    $f[]="";
    $f[]="#";
    $f[]="# Connection settings to the NRDP server";
    $f[]="# parent = NRDP server location (ex: http://<address>/nrdp)";
    $f[]="# token = NRDP server token used to send NRDP results";
    $f[]="#";
    $f[]="parent =";
    $f[]="token =";
    $f[]="hostname = NCPA 2";
    $f[]="connection_timeout = 10";
    $f[]="";
    $f[]="[kafkaproducer]";
    $f[]="";
    $f[]="hostname = None";
    $f[]="servers = localhost:9092";
    $f[]="clientname = NCPA-Kafka";
    $f[]="topic = ncpa";
    $f[]="";
    $f[]="";
    $f[]="[plugin directives]";
    $f[]="";
    $f[]="plugin_path = plugins/";
    $f[]="follow_symlinks = 0";
    $f[]="plugin_timeout = 59";
    $f[]="";
    @file_put_contents("/usr/local/ncpa/etc/ncpa.cfg",@implode("\n",$f));
    _out("/usr/local/ncpa/etc/ncpa.cfg Success");
}

function build_progress($prc,$txt):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$txt,"nagios.progress");

}
function build_progress_restart($prc,$txt):bool{
    if(isset($GLOBALS["MONIT"])) {
        if ($GLOBALS["MONIT"]) {
            return false;
        }
    }
    $unix=new unix();
    return $unix->framework_progress($prc,$txt,"nagios.client.progress");

}




function restart():bool{
    build_progress(25, "{stopping_service}");
    $unix = new unix();
    $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
    $pid = $unix->get_pid_from_file($pidfile);
    if ($unix->process_exists($pid, basename(__FILE__))) {
        $time = $unix->PROCCESS_TIME_MIN($pid);
        _out("Already Artica task running PID $pid since {$time}mn");
        build_progress_restart(110, "Already executed...");
        return false;
    }
    if($GLOBALS["MONIT"]) {
        squid_admin_mysql(0,"Ask to restart Web error page service by the watchdog",null,__FILE__,__LINE__);
    }

    @file_put_contents($pidfile, getmypid());

    build_progress_restart(50, "{stopping_service}");
    stop(true);
    build_progress_restart(55, "{reconfiguring}");
    build();
    build_progress_restart(60, "{starting_service}");
    sleep(3);
    build_syslog();
    build_monit();
    if(start(true)) {
        build_progress_restart(100, "{starting_service} {success}");
        return true;
    }

    build_progress_restart(110, "{starting_service} {failed}");
    return false;
}

function build_monit():bool{
    $srcmd5=null;
    $unix=new unix();
    $monit_file = "/etc/monit/conf.d/APP_NAGIOS_CLIENT.monitrc";
    $PIDFILE_PATH="/usr/local/ncpa/var/run/ncpa_listener.pid";
    $ServerIP=null;

    $php=$unix->LOCATE_PHP5_BIN();
    $me=__FILE__;
    $f[] = "check process APP_NAGIOS_CLIENT with pidfile $PIDFILE_PATH";
    $f[] = "\tstart program = \"$php $me --start --monit\"";
    $f[] = "\tstop program = \"$php $me --stop --monit\"";
    $f[] = "\trestart program = \"$php $me --restart --monit\"";
    if($ServerIP<>null) {
      //  $f[] = "\tif failed host $ServerIP port $ServerPort type tcp then restart";
    }
    $f[] = "";

    @file_put_contents($monit_file, @implode("\n", $f));
    $srcdest = md5_file($monit_file);
    if ($srcdest == $srcmd5) {return true;}
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    return true;
}


function _out($text){
    echo "Service.......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("nagios", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}
function build_syslog():bool{
    $conf="/etc/rsyslog.d/00_nagios.conf";
    $EnableNagiosClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNagiosClient"));
    if($EnableNagiosClient==0){
        if(is_file($conf)){
            @unlink($conf);
            $unix=new unix();$unix->RESTART_SYSLOG(true);
        }
        return true;
    }
    $md5_start=null;
    if(is_file($conf)){$md5_start=md5_file($conf);}
    $h[]="input(type=\"imfile\" file=\"/usr/local/ncpa/var/log/ncpa_passive.log\"  Tag=\"nagios:\")";

    $h[]="if  (\$programname =='nagios') then {";
    $h[]=buildlocalsyslogfile("/var/log/nagios-client.log");
    $h[]="& stop";
    $h[]="}";
    $h[]="";

    @file_put_contents($conf,@implode("\n", $h));
    $md5_end=md5_file($conf);
    if($md5_end==$md5_start) {return true;}
    _out("Starting: Updating Syslog configuration...");
    $unix=new unix();$unix->RESTART_SYSLOG(true);
    return true;

}


function start($aspid = false){
    $unix = new unix();
    $Masterbin = "/usr/local/ncpa/ncpa_listener";

    if (!is_file($Masterbin)) {
        _out("$Masterbin not installed");
        return false;
    }

    if (!$aspid) {
        $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
        $pid = $unix->get_pid_from_file($pidfile);
        if ($unix->process_exists($pid, basename(__FILE__))) {
            $time = $unix->PROCCESS_TIME_MIN($pid);
                _out("Already Artica task running PID $pid since {$time}mn");
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }
    $pid = LISTENER_PID();

    if ($unix->process_exists($pid)) {
        $timepid = $unix->PROCCESS_TIME_MIN($pid);
        _out("Service already started $pid since {$timepid}Mn...");
        return start_passive();
    }
    _out("Service: building watchdog (Monit)");
    build_monit();
    $D[]="/usr/local/ncpa/etc";
    $D[]="/usr/local/ncpa/var";
    $D[]="/usr/local/ncpa/var/run";
    $D[]="/usr/local/ncpa/var/log";

    foreach ($D as $path){
        if(!is_dir($path)){
            @mkdir($path,0755,true);
        }
        @chown($path,"nagios");
        @chgrp($path,"nagios");
        @chmod($path,0755);
    }


    $f[]="/usr/local/ncpa/etc/ncpa.cfg";
    $f[]="/usr/local/ncpa/etc/ncpa.cfg.d";
    $f[]="/usr/local/ncpa/etc/ncpa.cfg.d/README.txt";
    $f[]="/usr/local/ncpa/etc/ncpa.cfg.d/example.cfg";
    $f[]="/usr/local/ncpa/etc/ncpa.cfg.sample";
    $f[]="/usr/local/ncpa/var/log/ncpa_listener.log";
    $f[]="/usr/local/ncpa/var/log/ncpa_passive.log";


    foreach ($f as $path){
        @chown($path,"nagios");
        @chgrp($path,"nagios");
        @chmod($path,0755);
    }

    @chmod($Masterbin,0755);


    $nohup = $unix->find_program("nohup");
    $cmdFallback = "$nohup $Masterbin > /dev/null 2>&1 &";
    $cmd = "$Masterbin --start";
   _out("Starting service");

    $f = $unix->go_exec($cmd);
    if (!$f){
        _out("Starting......(Fallback Method) !");
        shell_exec($cmdFallback);
    }

    for ($i = 1; $i < 5; $i++) {
        _out("Starting, waiting $i/5");
        sleep(1);
        $pid = LISTENER_PID();
        if ($unix->process_exists($pid)) {
            break;
        }
    }

    $pid = LISTENER_PID();
    echo $pid;
    if ($unix->process_exists($pid)) {
        _out("Starting Success PID $pid");
        return start_passive();
    }

    _out("Starting failed $cmd");
    return false;

}
function start_passive(){
    $unix = new unix();
    $Masterbin = "/usr/local/ncpa/ncpa_passive";

    if (!is_file($Masterbin)) {
        _out("$Masterbin not installed");
        return false;
    }

    $pid = PASSIVE_PID();


    if ($unix->process_exists($pid)) {
        $timepid = $unix->PROCCESS_TIME_MIN($pid);
        _out("Passive service already started $pid since {$timepid}Mn...");
        return true;
    }
    @chmod($Masterbin,0755);
    $nohup = $unix->find_program("nohup");
    $cmdFallback = "$nohup $Masterbin > /dev/null 2>&1 &";
    $cmd = "$Masterbin --start";
    _out("Starting Passive service");

    $f = $unix->go_exec($cmd);
    if (!$f){
        _out("Starting......(Fallback Method) !");
        shell_exec($cmdFallback);
    }

    for ($i = 1; $i < 5; $i++) {
        _out("Starting Passive service, waiting $i/5");
        sleep(1);
        $pid = PASSIVE_PID();
        if ($unix->process_exists($pid)) {
            break;
        }
    }

    $pid = PASSIVE_PID();

    if ($unix->process_exists($pid)) {
        _out("Starting Passive service Success PID $pid");
        return true;
    }

    _out("Starting Passive service failed $cmd");
    return false;

}
function stop($aspid = false){
    $unix = new unix();
    if (!$aspid) {
        $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
        $pid = $unix->get_pid_from_file($pidfile);
        if ($unix->process_exists($pid, basename(__FILE__))) {
            $time = $unix->PROCCESS_TIME_MIN($pid);
            _out("service Already Artica task running PID $pid since {$time}mn");
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid = LISTENER_PID();


    if (!$unix->process_exists($pid)) {
        _out("Stopping service already stopped...");
        return stop_passive();
    }
    $pid = LISTENER_PID();
    _out("Stopping service Shutdown pid $pid...");

    unix_system_kill($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = LISTENER_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("Stopping service waiting pid:$pid $i/5...");
        sleep(1);
    }

    $pid = LISTENER_PID();
    if (!$unix->process_exists($pid)) {
        _out("Stopping service success...");
        return stop_passive();
    }

    _out("Stopping service shutdown - force - pid $pid...");

    unix_system_kill_force($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = LISTENER_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("Stopping service waiting pid:$pid $i/5...");
        sleep(1);
    }

    if ($unix->process_exists($pid)) {
        _out("service failed...");
        return false;
    }

    return stop_passive();
}
function stop_passive():bool{
    $unix = new unix();

    $pid = PASSIVE_PID();


    if (!$unix->process_exists($pid)) {
        _out("Stopping Passive service already stopped...");
        return true;
    }
    $pid = PASSIVE_PID();
    _out("Stopping Passive service Shutdown pid $pid...");

    unix_system_kill($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = PASSIVE_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("Stopping service waiting pid:$pid $i/5...");
        sleep(1);
    }

    $pid = PASSIVE_PID();
    if (!$unix->process_exists($pid)) {
        _out("Stopping Passive service success...");
        return true;
    }

    _out("Stopping service shutdown - force - pid $pid...");

    unix_system_kill_force($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = PASSIVE_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("Stopping Passive service waiting pid:$pid $i/5...");
        sleep(1);
    }

    if ($unix->process_exists($pid)) {
        _out("Stopping Passive service  failed...");
        return false;
    }

    return true;
}