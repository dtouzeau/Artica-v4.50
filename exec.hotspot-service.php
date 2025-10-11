<?php
$GLOBALS["TITLENAME"]="HotSpot service";
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
if($argv[1]=="--syslog"){build_syslog();exit;}


function uninstall(){
    build_progress(25, "{uninstalling}");
    $unix=new unix();
    $unix->remove_service("/etc/init.d/artica-hotspot");
    $php=$unix->LOCATE_PHP5_BIN();
    $monit_file = "/etc/monit/conf.d/HOTSPOT_WEB.monitrc";
    build_progress(50, "{uninstalling}");
    if(is_file($monit_file)){
        @unlink($monit_file);
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    }
    build_progress(60, "{reconfiguring}");
    shell_exec("$php /usr/share/artica-postfix/exec.hotspot.templates.php");
    $unix->go_exec("/etc/init.d/go-shield-server restart");
    build_progress(100, "{uninstalling} {success}");
}

function ssl_certificate(){
    $web_templates="/home/artica/web_templates";
    $HotSpotListenSSLCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenSSLCertificate"));

    $tcert="$web_templates/hotspot-cert.pm";
    $tkey="$web_templates/hotspot-key.pm";
    if($HotSpotListenSSLCertificate==null){
        _out("INFO: No ssl certificate defined...");
        if(is_file($tcert)) {@unlink($tcert);}
        if(is_file($tkey)) {@unlink($tkey);}
        return true;
    }
    $nginx=new nginx_certificate($HotSpotListenSSLCertificate);
    $nginx->GetConf();
    $cert_path=$nginx->ssl_certificate_path;
    $key_path=$nginx->ssl_certificate_key;
    if(!is_file($cert_path)){
        _out("INFO:$cert_path no such file, cannot generate SSL");
        return true;}
    if(!is_file($key_path)){
        _out("INFO:$key_path no such file, cannot generate SSL");
        return true;}

    if(!is_dir($web_templates)){@mkdir($web_templates,0755,true);}
    if(is_file($tcert)) {@unlink($tcert);}
    if(is_file($tkey)) {@unlink($tkey);}
    _out("SSL Certificate: $HotSpotListenSSLCertificate web_templates/cert.pm and web_templates/key.pm success");
    _out("SSL Certificate: Copy $cert_path");
    _out("SSL Certificate: Copy $key_path");
    @copy($cert_path,$tcert);
    @copy($key_path,$tkey);

    return true;
}

function install(){
    build_progress(25, "{installing}");
    $unix=new unix();
    $php5script=basename(__FILE__);
    $php=$unix->LOCATE_PHP5_BIN();
    update_binary();
    $nextbin        = "/usr/sbin/hotspot-web";
    $pidfile        = PIDFILE_PATH();
    $Config["BINARY"]=$nextbin;
    $Config["PIDFILE"]=$pidfile;
    $Config["INITD_PATH"]="/etc/init.d/artica-hotspot";
    $Config["PHP_BUILDER"]="$php5script --build";
    $Config["PHP_KILLER"]="$php5script --stop";
    $Config["BINARY_OPTS"]=null;
    $Config["COMPLIANCE"]=false;
    $unix->create_service_compliance($Config);
    build_progress(40, "{installing} {reconfiguring}");
    shell_exec("$php /usr/share/artica-postfix/exec.hotspot.templates.php");
    build_progress(50, "{installing} {starting_service}");
    if(start(true)){
        build_progress(100, "{installing} {success}");
        $unix->go_exec("/etc/init.d/go-shield-server restart");
        return true;
    }
    build_progress(110, "{starting} {failed}");
    return false;
}
function PIDFILE_PATH():string{
    return "/var/run/hotspot-web.pid";
}

function build(){
    update_binary();
    build_syslog();
    build_monit();
    ssl_certificate();
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.hotspot.templates.php");
}

function build_progress($prc,$txt){
    $unix=new unix();
    $unix->framework_progress($prc,$txt,"hotspot.progress");

}

function restart():bool{
    build_progress(25, "{stopping_service}");
    $unix = new unix();
    $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
    $pid = $unix->get_pid_from_file($pidfile);
    if ($unix->process_exists($pid, basename(__FILE__))) {
        $time = $unix->PROCCESS_TIME_MIN($pid);
        _out("Already Artica task running PID $pid since {$time}mn");
        build_progress(110, "{stopping_service} {failed}");
        return false;
    }
    if($GLOBALS["MONIT"]) {
        squid_admin_mysql(0,"Ask to restart Web error page service by the watchdog",null,__FILE__,__LINE__);
    }
    @file_put_contents($pidfile, getmypid());
    stop(true);
    build_progress(50, "{starting_service}");
    sleep(3);
    if(start(true)) {
        build_progress(100, "{starting_service} {success}");
        return true;
    }
    build_progress(110, "{starting_service} {failed}");
    return false;
}

function build_monit():bool{
    $srcmd5=null;
    $unix=new unix();
    $monit_file = "/etc/monit/conf.d/HOTSPOT_WEB.monitrc";
    $PIDFILE_PATH=PIDFILE_PATH();
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $ServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenPort"));
    $HotSpotBindInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotBindInterface"));
    if($ServerPort==0){$ServerPort=8025;}
    $ServerIP="127.0.0.1";

    if($HotSpotBindInterface<>null){
        $ipaddr=$unix->InterfaceToIPv4($HotSpotBindInterface);
        if($ipaddr<>null){
            $ServerIP=$ipaddr;
        }
    }

    if($EnableNginx==1){
        $ServerIP="127.0.0.1";
        $ServerPort=8577;
    }

    if (is_file($monit_file)) {
        $srcmd5=md5_file($monit_file);
    }

    $f[] = "check process HOTSPOT_WEB with pidfile $PIDFILE_PATH";
    $f[] = "\tstart program = \"/etc/init.d/artica-hotspot start\"";
    $f[] = "\tstop program = \"/etc/init.d/artica-hotspot stop\"";
    $f[] = "\trestart program = \"/etc/init.d/artica-hotspot restart --monit\"";
    $f[] = "\tif failed host $ServerIP port $ServerPort type tcp then restart";
    $f[] = "";

    @file_put_contents($monit_file, @implode("\n", $f));
    $srcdest = md5_file($monit_file);
    if ($srcdest == $srcmd5) {return true;}
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    return true;
}

function update_binary(){
    $srcfile="/usr/share/artica-postfix/bin/hotspot-web";
    $dstfile="/usr/sbin/hotspot-web";

    if(!is_file($srcfile)){
        _out("$srcfile no such file!");
        return false;

    }
    $md52=null;
    $md51=md5_file($srcfile);
    if(is_file($dstfile)){
        $md52=md5_file($dstfile);
    }
    if($md51==$md52){
        _out("$dstfile up-to-date..");
        @chmod("/usr/sbin/hotspot-web",0755);
        return true;
    }
    _out("$dstfile Updating $dstfile ...");
    if(is_file($dstfile)){@unlink($dstfile);}
    @copy($srcfile,$dstfile);
    @chmod("/usr/sbin/hotspot-web",0755);
    return true;

}
function _out($text){
    echo "Service.......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("hotspot-web", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}
function build_syslog(){
    $confold="/etc/rsyslog.d/hotspot.conf";
    if(is_file($confold)){@unlink($confold);}
    $conf="/etc/rsyslog.d/00_hotspot.conf";
    $md5_start=null;
    if(is_file($conf)){$md5_start=md5_file($conf);}
    $h[]="if  (\$programname =='hotspot') then {";
    $h[]=BuildRemoteSyslogs("hotspot","web");
    $h[]=buildlocalsyslogfile("/var/log/squid/hotspot.log");
    $h[]="& stop";
    $h[]="}";
    $h[]="";

    @file_put_contents($conf,@implode("\n", $h));
    $md5_end=md5_file($conf);
    if($md5_end<>$md5_start) {
        _out("Starting: Updating Syslog configuration...");
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }

}

function HOTSPOT_WEB_PID():int{
    $unix = new unix();
    $pid=$unix->get_pid_from_file("/var/run/hotspot-web.pid");
    if($unix->process_exists($pid)){return intval($pid);}
    return intval($unix->PIDOF("/usr/sbin/hotspot-web"));
}

function start($aspid = false){
    $unix = new unix();
    $Masterbin = "/usr/sbin/hotspot-web";
    if (!is_file($Masterbin)) {
        update_binary();
    }
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
    $pid = HOTSPOT_WEB_PID();

    if ($unix->process_exists($pid)) {
        $timepid = $unix->PROCCESS_TIME_MIN($pid);
        _out("Service already started $pid since {$timepid}Mn...");
        return true;
    }
    _out("Service: building watchdog (Monit)");
    build_monit();
    _out("Service: Check update...");
    update_binary();
    _out("Service: Check Syslog...");
    build_syslog();
    _out("Service: SSL certificate...");
    ssl_certificate();
    @chmod($Masterbin,0755);
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.hotspot.templates.php");
    $HotSpotBindInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotBindInterface"));
    if($HotSpotBindInterface<>null){
        $HotSpotBindInterfaceIP=$unix->InterfaceToIPv4($HotSpotBindInterface);
        _out("Service: Listens $HotSpotBindInterface -> $HotSpotBindInterfaceIP");

    }


    $nohup = $unix->find_program("nohup");
    $cmdFallback = "$nohup $Masterbin > /dev/null 2>&1 &";
    $cmd = "$Masterbin";
   _out("Starting service");

    $f = $unix->go_exec($cmd);
    if (!$f){
        _out("Starting......(Fallback Method) !");
        shell_exec($cmdFallback);
    }

    for ($i = 1; $i < 5; $i++) {
        _out("Starting, waiting $i/5");
        sleep(1);
        $pid = HOTSPOT_WEB_PID();
        if ($unix->process_exists($pid)) {
            break;
        }
    }

    $pid = HOTSPOT_WEB_PID();
    echo $pid;
    if ($unix->process_exists($pid)) {
        _out("Starting Success PID $pid");
        return true;
    }

    _out("Starting failed $cmd");
    return false;

}
function stop($aspid = false)
{
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

    $pid = HOTSPOT_WEB_PID();


    if (!$unix->process_exists($pid)) {
        _out("Stopping service already stopped...");
        killByPort();
        return true;
    }
    $pid = HOTSPOT_WEB_PID();
    _out("Stopping service Shutdown pid $pid...");

    unix_system_kill($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = HOTSPOT_WEB_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("Stopping service waiting pid:$pid $i/5...");
        sleep(1);
    }

    $pid = HOTSPOT_WEB_PID();
    if (!$unix->process_exists($pid)) {
        _out("Stopping service success...");
        killByPort();
        return true;
    }

    _out("Stopping service shutdown - force - pid $pid...");

    unix_system_kill_force($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = HOTSPOT_WEB_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("Stopping service waiting pid:$pid $i/5...");
        sleep(1);
    }


    if ($unix->process_exists($pid)) {
        _out("service failed...");
        killByPort();
        return false;
    }
    killByPort();
    return true;
}

function killByPort(){

    $unix=new unix();
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    if($EnableNginx==1){
        $unix->KILL_PROCESSES_BY_PORT(8577);
        return true;
    }
    $HotSpotListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenPort"));

	if( $HotSpotListenPort == 0) {
        $HotSpotListenPort = 8025;
	}
    $unix->KILL_PROCESSES_BY_PORT($HotSpotListenPort);
    return true;
}