<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(!isset($argv[1])){
    echo "Wrong command line\n";
    die();
}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--reload"){reload();exit;}
if($argv[1]=="--syslog"){build_syslog();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--chkey"){ChangeKeys();exit;}
if($argv[1]=="--nginx"){nginx_configuration();exit;}

echo "Unable to understand $argv[1]\n";

function uninstall():bool{
    build_progress(25, "{uninstalling}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableRustDeskServer",0);
    $unix=new unix();
    stop();
    $unix->remove_service("/etc/init.d/rustdesk");

    build_progress(60, "{reconfiguring}");
    $monit_file = "/etc/monit/conf.d/APP_RUSTDESK.monitrc";
    build_progress(70, "{uninstalling}");
    if(is_file($monit_file)){
        @unlink($monit_file);
        $unix->MONIT_RELOAD();
    }
    $syslog_conf="/etc/rsyslog.d/00_rustdesk.conf";
    if(is_file($syslog_conf)){
        @unlink($syslog_conf);
        $unix->RESTART_SYSLOG();
    }
    build_progress(70, "{uninstalling}");
    $nginx_file                 = "/usr/local/ArticaWebConsole/webplugins/rustdesk.conf";
    if(is_file($nginx_file)){
        @unlink($nginx_file);
        $unix->RELOAD_WEBCONSOLE();
    }

    build_progress(80, "{uninstalling}");
   // $unix->Popuplate_cron_delete("crowdsec-tasks");
   // $unix->Popuplate_cron_delete("crowdsec-update");
    build_progress(100, "{uninstalling} {success}");
    return true;
}
function action_reload():bool{
    $unix=new unix();
    $pid=CROWDSEC_PID();
    if($unix->process_exists($pid)){
        _out("[INFO]: Reloading PID: $pid");
        $unix->KILL_PROCESS($pid,1);
        build_reload(100,"{reloading} {success}");
        return true;
    }
    _out("[ERROR]: Not running...");
    return start(false);

}
function reload():bool{
    build();
    build_reload(50,"{reloading}");
    build();
    build_reload(70,"{reloading}");
    if(!action_reload()){
        return build_reload(110,"{reloading} {failed}");
    }
    return build_reload(100,"{reloading} {success}");
}
function install():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableRustDeskServer",1);
    build_progress(25, "{installing}");
    $unix=new unix();
    build_progress(30, "{installing}");
    $unix->create_service_php(basename(__FILE__),"rustdesk");
    build_progress(40, "{installing} {reconfiguring}");
    if(start(true)){
        build_progress(50, "{installing} {success}");

    }
    $file=basename(__FILE__);
    //$unix->Popuplate_cron_make("crowdsec-tasks","*/5 * * * *","$file --tasks");
    //$unix->Popuplate_cron_make("crowdsec-update","59 2 */2 * *","$file --update");
    build_progress(100, "{installing} {success}");
    return true;
}
function build_progress($prc,$txt):bool{
    if($GLOBALS["MONIT"]){return true;}
    $unix=new unix();
    return $unix->framework_progress($prc,$txt,"rustdesk.install.progress");
}
function build_progress_restart($prc,$txt):bool{
    if($GLOBALS["MONIT"]){return true;}
    $unix=new unix();
    return $unix->framework_progress($prc,$txt,"rustdesk.restart.progress");
}
function build_reload($prc,$txt):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$txt,"rustdesk.reconfigure.progress");
}
function restart():bool{
    $unix = new unix();
    if($GLOBALS["MONIT"]){
        $RESTART=false;
        $bbrpid=RUSTDESK_BBR_PID();
        $bbspid=RUSTDESK_BBS_PID();
        if($unix->process_exists($bbrpid)){
            @file_put_contents("/var/run/rustdesk-bbr.pid",$bbrpid);
        }else{
            $RESTART=true;
        }
        if($unix->process_exists($bbspid)){
            @file_put_contents("/var/run/rustdesk-bbs.pid",$bbspid);
        }else{
            $RESTART=true;
        }
        if(!$RESTART){
            exit(0);
        }
    }


    build_progress_restart(25, "{restarting_service}");

    $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
    $pid = $unix->get_pid_from_file($pidfile);
    if ($unix->process_exists($pid, basename(__FILE__))) {
        $time = $unix->PROCCESS_TIME_MIN($pid);
        _out("Already Artica task running PID $pid since {$time}mn");
        return build_progress_restart(110, "{restarting_service} {failed}");

    }
    if($GLOBALS["MONIT"]) {
        squid_admin_mysql(0,"Ask to restart RusDesk service by the watchdog",null,__FILE__,__LINE__);
    }
    @file_put_contents($pidfile, getmypid());
    build_progress_restart(30, "{stopping_service}");
    if(!stop(true)){
        return  build_progress_restart(110, "{stopping_service} {failed}");
    }
    build_progress_restart(50, "{starting_service}");
    if(start(true)) {
        return build_progress_restart(100, "{starting_service} {success}");
    }
    return build_progress_restart(110, "{starting_service} {failed}");

}
function GetServerPort():int{
    $ServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RustDeskServerPort"));
    if($ServerPort==0){return 21117;}
    return $ServerPort;
}
function build_monit():bool{
    $srcmd5=null;
    $unix=new unix();
    $monit_file = "/etc/monit/conf.d/APP_RUSTDESK.monitrc";
    $PIDFILE_BBS = "/var/run/rustdesk-bbs.pid";
    $PIDFILE_BBR = "/var/run/rustdesk-bbr.pid";
    $php=$unix->LOCATE_PHP5_BIN();
    $ServerIP="127.0.0.1";

    $ServerPort=GetServerPort();

    $start="$php ".__FILE__." --start --monit";
    $stop="$php ".__FILE__." --stop --monit";
    $restart="$php ".__FILE__." --restart --monit";

    $f[] = "check process APP_RUSTDESKBBS with pidfile $PIDFILE_BBS";
    $f[] = "\tstart program = \"$start\"";
    $f[] = "\tstop program = \"$stop\"";
    $f[] = "\trestart program = \"$restart\"";
    $f[] = "\tif failed host $ServerIP port $ServerPort type tcp then restart";
    $f[] = "";
    $f[] = "check process APP_RUSTDESKBBR with pidfile $PIDFILE_BBR";
    $f[] = "\tstart program = \"$start\"";
    $f[] = "\tstop program = \"$stop\"";
    $f[] = "\trestart program = \"$restart\"";
    $f[] = "";

    @file_put_contents($monit_file, @implode("\n", $f));
    $srcdest = md5_file($monit_file);
    if ($srcdest == $srcmd5) {return true;}
    $unix->MONIT_RELOAD();
    return true;
}
function _out($text):bool{
    echo "Service.......: ".date("H:i:s")." [SERVICE]: $text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("rustdesk", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}
function build_syslog():bool{
    $unix=new unix();
    $conf="/etc/rsyslog.d/00_rustdesk.conf";
    $EnableRustDeskServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRustDeskServer"));
    if ($EnableRustDeskServer==0){
        if(is_file($conf)){
            @unlink($conf);
            $unix->RESTART_SYSLOG(true);
        }
        return true;
    }



    if(!is_dir("/var/log/rustdesk")){@mkdir("/var/log/rustdesk",0755,true);}
    $EnableRustDeskServer=$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableRustDeskServer",1);
    if($EnableRustDeskServer==0){
        if($GLOBALS["VERBOSE"]){
            echo "$conf EnableRustDeskServer=$EnableRustDeskServer ABORT\n";
        }
        if(is_file($conf)){
            @unlink($conf);
            $unix->RESTART_SYSLOG(true);
        }
        return true;
    }
    $md5_start=null;
    if(is_file($conf)){$md5_start=md5_file($conf);}
    if($GLOBALS["VERBOSE"]){
        echo "$conf MD5:$md5_start\n";
    }

    $remote= BuildRemoteSyslogs("rustdesk","local-rustdesk");
    $h[]="input(type=\"imfile\" file=\"/var/log/rustdesk/rustdeskbbs.log\"  Tag=\"rustdesk:\")";
    $h[]="input(type=\"imfile\" file=\"/var/log/rustdesk/rustdeskbbr.log\"  Tag=\"rustdesk:\")";
    $h[]="if  (\$programname =='rustdesk') then {";
    $h[]=buildlocalsyslogfile("/var/log/rustdesk/rustdesk.log");
    $h[]=$remote;
    $h[]="& stop";
    $h[]="}";
    $h[]="";

    @file_put_contents($conf,@implode("\n", $h));
    $md5_end=md5_file($conf);
    if($GLOBALS["VERBOSE"]){
        echo "$conf MD5-2:$md5_end\n";
    }


    if($md5_end<>$md5_start) {
        _out("Starting: Updating Syslog configuration...");
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }
    return true;
}
function RUSTDESK_BBS_PID():int{
    $unix = new unix();
    $pid=$unix->get_pid_from_file("/var/run/rustdesk-bbs.pid");
    if($unix->process_exists($pid)){return $pid;}
    return $unix->PIDOF("/usr/local/bin/hbbs");
}
function RUSTDESK_BBR_PID():int{
    $unix = new unix();
    $pid=$unix->get_pid_from_file("/var/run/rustdesk-bbr.pid");
    if($unix->process_exists($pid)){return $pid;}
    return $unix->PIDOF("/usr/local/bin/hbbr");
}
function start($aspid = false):bool{
    $unix = new unix();
    $Masterbin = "/usr/local/bin/hbbs";
    if (!is_file($Masterbin)) {
        _out("ERROR: BBS Not installed! missing $Masterbin");
        return false;
    }
    $EnableRustDeskServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRustDeskServer"));

    if($EnableRustDeskServer==0){
        _out("ERROR: BBS Not Active! ( EnableRustDeskServer == 0)");
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
    $pid = RUSTDESK_BBS_PID();

    if ($unix->process_exists($pid)) {
        $timepid = $unix->PROCCESS_TIME_MIN($pid);
        _out("[INFO]: Starting HBS Service already started $pid since {$timepid}Mn...");
        @file_put_contents("/var/run/rustdesk-bbs.pid",$pid);
        return start_bbr();
    }
    _out("[INFO]: Starting HBS Service building watchdog (Monit)");
    build_monit();
    build_syslog();
    @chmod($Masterbin,0755);
    $nohup = $unix->find_program("nohup");
    $RustDeskEncryptedOnly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RustDeskEncryptedOnly"));
    list($PUBKEY,$SECKEY)=GetKeys();

    $K="";
    if($RustDeskEncryptedOnly==1){
        $K=" -k \"$PUBKEY\"";
    }


    $cmdFallback = "$nohup $Masterbin$K >/var/log/rustdesk/rustdeskbbs.log 2>&1 &";
    $sh[]="#!/bin/bash";
    $sh[]="export DB_URL=\"/home/artica/SQLITE/rustdesk.db\"";
    $sh[]="export KEY_PRIV=\"$SECKEY\"";
    $sh[]="export KEY_PUB=\"$PUBKEY\"";
    $sh[]="export ENCRYPTED_ONLY=\"$RustDeskEncryptedOnly\"";
    $sh[]=$cmdFallback;
    $sh[]="";
    @file_put_contents("/usr/local/bin/rustdesk-start.sh",@implode("\n",$sh));
    @chmod("/usr/local/bin/rustdesk-start.sh",0755);


    $cmd = "$Masterbin";
    _out("[INFO]: Starting HBS Service ");

    $f = $unix->go_exec("/usr/local/bin/rustdesk-start.sh");
    if (!$f){
        _out("[WARNING]: Starting......(Fallback Method) !");
        shell_exec($cmdFallback);
    }

    for ($i = 1; $i < 5; $i++) {
        _out("Starting, waiting $i/5");
        sleep(1);
        $pid = RUSTDESK_BBS_PID();
        if ($unix->process_exists($pid)) {
            break;
        }
    }

    $pid = RUSTDESK_BBS_PID();

    if ($unix->process_exists($pid)) {
        _out("[INFO]: Starting HBS Service Success PID $pid");
        @file_put_contents("/var/run/rustdesk-bbs.pid",$pid);
        return start_bbr();
    }

    _out("[ERROR]: Starting BBS failed $cmd");
    return false;

}
function genKeyPairs():bool{
    $PUBKEY=null;
    $SECKEY=null;
    @chmod("/usr/local/bin/rustdesk-utils",0755);
    exec("/usr/local/bin/rustdesk-utils genkeypair 2>&1",$results);
    _out("[INFO]: Generate a new Key Pairs for encryption");
    foreach ($results as $ligne){
        $ligne=trim($ligne);
        if($ligne==null){continue;}
        if(preg_match("#^Public Key:\s+(.+)#",$ligne,$re)){
            $PUBKEY=$re[1];
        }
        if(preg_match("#^Secret Key:\s+(.+)#",$ligne,$re)){
            $SECKEY=$re[1];
        }
    }
    if($PUBKEY==null){
        _out("[ERROR]: rustdesk-utils, Unable to generate RustDesk Public Key key pairs");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RustDeskKeys",base64_encode(serialize(array())));
        return false;
    }
    if($SECKEY==null){
        _out("[ERROR]: rustdesk-utils, Unable to generate RustDesk SecKey key pairs");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RustDeskKeys",base64_encode(serialize(array())));
        return false;
    }

    _out("rustdesk-utils, Public Key: ".strlen($PUBKEY)." SecKey ".strlen($SECKEY));
    
    $FINAL["PUBKEY"]=$PUBKEY;
    $FINAL["SECKEY"]=$SECKEY;
    $data=base64_encode(serialize($FINAL));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RustDeskKeys",$data);
    return true;
}
function ChangeKeys():bool{
    build_progress(20,"{change} {key}...");
    if(!genKeyPairs()){
        return build_progress(110,"{change} {key} {failed}...");
    }
    build_progress(50,"{stopping_service}...");
    stop(true);
    build_progress(90,"{starting_service}...");
    if(!start(true)){
        return build_progress(110,"{starting_service} {failed}...");
    }
    return build_progress(100,"{change} {key} {success}...");
}
function GetKeys():array{
    $Data=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RustDeskKeys");
    if(!is_null($Data)){
        if(strlen($Data)>6){
            $sMain=unserialize(base64_decode($Data));
            if(isset($sMain["PUBKEY"])){
                $PUBKEY=$sMain["PUBKEY"];
                $SECKEY=$sMain["SECKEY"];
                return array($PUBKEY,$SECKEY);
            }
        }
    }

    genKeyPairs();
    if(!isset($GLOBALS["AntiloopGetKeys"])){
        $GLOBALS["AntiloopGetKeys"]=0;
    }
    $GLOBALS["AntiloopGetKeys"]= $GLOBALS["AntiloopGetKeys"]+1;
    if( $GLOBALS["AntiloopGetKeys"]>3){
        _out("[FATAL]: Generate a new Key Pairs for encryption error");
        return array("","");
    }
    return GetKeys();
}


function start_bbr($aspid = false):bool{
    $unix = new unix();
    $Masterbin = "/usr/local/bin/hbbr";
    if (!is_file($Masterbin)) {
        _out("ERROR: Not installed! missing $Masterbin");
        return false;
    }
    $EnableRustDeskServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRustDeskServer"));

    if($EnableRustDeskServer==0){
        _out("ERROR: Not Active! ( EnableRustDeskServer == 0)");
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
    $pid = RUSTDESK_BBR_PID();

    if ($unix->process_exists($pid)) {
        $timepid = $unix->PROCCESS_TIME_MIN($pid);
        _out("Service BBR already started $pid since {$timepid}Mn...");
        @file_put_contents("/var/run/rustdesk-bbr.pid",$pid);
        return true;
    }

    $K=null;
    list($PUBKEY,$SECKEY)=GetKeys();
    $RustDeskEncryptedOnly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RustDeskEncryptedOnly"));
    if($RustDeskEncryptedOnly==1){
        $K=" -k \"$PUBKEY\"";
    }


    @chmod($Masterbin,0755);
    $nohup = $unix->find_program("nohup");
    $cmdFallback = "$nohup $Masterbin$K >>/var/log/rustdesk/rustdeskbbr.log 2>&1 &";
    $sh[]="#!/bin/bash";
    $sh[]="export DB_URL=\"/home/artica/SQLITE/rustdesk.db\"";
    $sh[]="export KEY_PRIV=\"$SECKEY\"";
    $sh[]="export KEY_PUB=\"$PUBKEY\"";
    $sh[]="export ENCRYPTED_ONLY=\"$RustDeskEncryptedOnly\"";
    $sh[]=$cmdFallback;
    $sh[]="";
    @file_put_contents("/usr/local/bin/rustdesk-bbr.sh",@implode("\n",$sh));
    @chmod("/usr/local/bin/rustdesk-bbr.sh",0755);

    _out("[INFO]: Starting BBR service");

    $f = $unix->go_exec("/usr/local/bin/rustdesk-bbr.sh");
    if (!$f){
        _out("[WARNING]: Starting BBR (Fallback Method) !");
        shell_exec($cmdFallback);
    }

    for ($i = 1; $i < 5; $i++) {
        _out("[INFO]: Starting, waiting $i/5");
        sleep(1);
        $pid = RUSTDESK_BBS_PID();
        if ($unix->process_exists($pid)) {
            break;
        }
    }

    $pid = RUSTDESK_BBR_PID();
    if ($unix->process_exists($pid)) {
        _out("[INFO]: Starting BBR Success PID $pid");
        @file_put_contents("/var/run/rustdesk-bbr.pid",$pid);
        return true;
    }

    _out("[ERROR]: Starting BBR failed");
    return false;

}
function stop_bbr():bool{
    $unix = new unix();
    $pid = RUSTDESK_BBR_PID();

    if (!$unix->process_exists($pid)) {
        _out("[INFO]: Stopping BBR service already stopped...");
        return true;
    }
    $pid = RUSTDESK_BBR_PID();
    _out("[INFO]: Stopping BBR service Shutdown pid $pid...");

    unix_system_kill($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = RUSTDESK_BBR_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("[INFO]: Stopping BBR service waiting pid:$pid $i/5...");
        sleep(1);
    }

    $pid = RUSTDESK_BBR_PID();
    if (!$unix->process_exists($pid)) {
        _out("[INFO]: Stopping BBR service success...");
        return true;
    }

    _out("[WARNING]: Stopping BBR service  shutdown - force - pid $pid...");

    unix_system_kill_force($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = RUSTDESK_BBR_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("[INFO]: Stopping BBR service waiting pid:$pid $i/5...");
        sleep(1);
    }

    if ($unix->process_exists($pid)) {
        _out("[INFO]: Stopping BBR Service Failed...");
        return false;
    }

    return true;
}
function stop($aspid = false):bool{
    $unix = new unix();
    if (!$aspid) {
        $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
        $pid = $unix->get_pid_from_file($pidfile);
        if ($unix->process_exists($pid, basename(__FILE__))) {
            $time = $unix->PROCCESS_TIME_MIN($pid);
            _out("Service hbbs Already Artica task running PID $pid since {$time}mn");
            return stop_bbr();
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid = RUSTDESK_BBS_PID();


    if (!$unix->process_exists($pid)) {
        _out("Stopping service hbbs already stopped...");
        return stop_bbr();
    }
    $pid = RUSTDESK_BBS_PID();
    _out("Stopping service hbbs Shutdown pid $pid...");

    unix_system_kill($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = RUSTDESK_BBS_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("Stopping service hbbs waiting pid:$pid $i/5...");
        sleep(1);
    }

    $pid = RUSTDESK_BBS_PID();
    if (!$unix->process_exists($pid)) {
        _out("Stopping service success...");
        return stop_bbr();
    }

    _out("Stopping service shutdown - force - pid $pid...");

    unix_system_kill_force($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = RUSTDESK_BBS_PID();
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

    return stop_bbr();
}
function nginx_configuration():bool{
    $unix                       = new unix();
    $nginx_file                 = "/usr/local/ArticaWebConsole/webplugins/rustdesk.conf";
    $md51=null;
    if(is_file($nginx_file)){
        $md51=md5_file($nginx_file);
    }

    $f[] = "location ^~ /rustdesk-web/ {";
    $f[] = "\t\tproxy_set_header        Host \$host;";
    $f[] = "\t\tproxy_set_header        X-Real-IP \$remote_addr;";
    $f[] = "\t\tproxy_set_header        X-Forwarded-For \$proxy_add_x_forwarded_for;";
    $f[] = "\t\tproxy_set_header        X-Forwarded-Proto \$scheme;";
    $f[] = "\t\tproxy_pass          http://127.0.0.1:21114;";
    $f[] = "\t\tproxy_read_timeout  90;";
    $f[] = "}\n";
    @file_put_contents($nginx_file,@implode("\n",$f));

    $md52=md5_file($nginx_file);


    if($md52==$md51){
        _out("$nginx_file no changes");
        return true;
    }
    _out("$nginx_file Reloading web console");
    $unix->RELOAD_WEBCONSOLE();
    return true;

}