<?php
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"] = new sockets();

if (function_exists("posix_getuid")) {
    if (posix_getuid() <> 0) {
        die("Cannot be used in web server mode\n\n");
    }
}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
$GLOBALS["FORCE"] = false;
$GLOBALS["RECONFIGURE"] = false;
$GLOBALS["SWAPSTATE"] = false;
$GLOBALS["NOSQUIDOUTPUT"] = true;
$GLOBALS["TITLENAME"] = "Categories Service";
if (preg_match("#--verbose#", implode(" ", $argv))) {
    $GLOBALS["VERBOSE"] = true;
    $GLOBALS["OUTPUT"] = true;
    $GLOBALS["debug"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
if (preg_match("#--output#", implode(" ", $argv))) {
    $GLOBALS["OUTPUT"] = true;
}
if (preg_match("#schedule-id=([0-9]+)#", implode(" ", $argv), $re)) {
    $GLOBALS["SCHEDULE_ID"] = $re[1];
}
if (preg_match("#--force#", implode(" ", $argv), $re)) {
    $GLOBALS["FORCE"] = true;
}
if (preg_match("#--reconfigure#", implode(" ", $argv), $re)) {
    $GLOBALS["RECONFIGURE"] = true;
}
if(isset($argv[1])){
    if($argv[1]=="--delete-databases"){delete_databases();exit;}
    if($argv[1]=="--start"){start();exit;}
    if($argv[1]=="--install"){install();exit;}
    if($argv[1]=="--uninstall"){uninstall();exit;}
    if($argv[1]=="--restart"){restart();exit;}
    if($argv[1]=="--check"){check_migration();exit;}
    if($argv[1]=="--reload"){reload();exit;}
    if($argv[1]=="--stop"){stop();exit;}
}



function build_progress_install($text,$pourc):bool{
   $unix=new unix();
   return $unix->framework_progress($pourc,$text,"articapcap.progress");

}
function install():bool{
    $unix=new unix();
    build_progress_install("{install_service}", 20);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaPSnifferDaemon", 1);
    build_progress_install("{install_service}", 35);
    install_service();

    build_progress_install("{install_service}", 40);
    monit_config();
    build_progress_install("{install_service}", 50);
    update_binary();
    build_progress_install("{install_service}", 55);
    start();
    return build_progress_install("{install_service} {success}", 100);


}

function uninstall():bool{
    $unix=new unix();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaPSnifferDaemon", 0);
    build_progress_install("{uninstall_service}", 20);
    @unlink("/etc/monit/conf.d/APP_ARTICAPCP.monitrc");
    $unix->MONIT_RELOAD();
    build_progress_install("{uninstall_service}", 30);
    $unix->remove_service("/etc/init.d/articapcap");
    build_progress_install("{uninstall_service}", 50);
    build_progress_install("{uninstall_service}", 80);
    build_progress_install("{uninstall_service} {done}", 100);
    return true;
}

function monit_config():bool{
    $f=array();
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $me=__FILE__;
    $f[]="check process APP_ARTICAPCP";
    $f[]="\twith pidfile /var/run/articapsniffer.pid";
    $f[]="\tstart program = \"$php $me --start --monit\"";
    $f[]="\tstop program =  \"$php $me --stop --monit\"";
    $f[]="\trestart program =  \"$php $me --restart --monit\"";
    $f[]="\tif 5 restarts within 5 cycles then timeout";
    $f[]="";
    @file_put_contents("/etc/monit/conf.d/APP_ARTICAPCP.monitrc", @implode("\n", $f));
    $unix->MONIT_RELOAD();
    return true;
}

function reload():bool{
    $unix=new unix();
    $pid=PID_NUM();

    if($unix->process_exists($pid)){
        _out("[RELOADING]: $pid...");
        $kill=$unix->find_program("kill");
        shell_exec("$kill -SIGHUP $pid");
        return true;
    }

    start();
    return true;
}

function restart():bool{


    build_progress_restart("{stopping} {APP_ARTICAPCAP}",10);
    if(!stop()){
        return build_progress_restart("{stopping} {APP_ARTICAPCAP} {failed}",110);
    }
    build_progress_restart("{starting} {APP_ARTICAPCAP}",50);
    if(!start()){
        return build_progress_restart("{starting} {APP_ARTICAPCAP} {failed}",110);

    }
    return build_progress_restart("{starting} {APP_ARTICAPCAP} {success}",100);
}

function start():bool{
    $unix=new unix();

    $ArticaPSnifferInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferInterface"));
    if($ArticaPSnifferInterface==null){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaPSnifferInterface","eth0");
    }
    $restart_prc=50;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaPSnifferEnableHTTP",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaPSnifferSyslogKernel",1);

    build_progress_restart("{starting} {APP_ARTICAPCAP} {update}",$restart_prc++);
    update_binary();
    build_progress_restart("{starting} {APP_ARTICAPCAP}...",$restart_prc++);

    $cmd="/usr/sbin/articapsniffer";
    _out("[STARTING]: service");
    $sh=$unix->sh_command($cmd);
    $unix->go_exec($sh);

    for($i=1;$i<5;$i++){
        build_progress_restart("{starting} {APP_ARTICAPCAP} {waiting}",$restart_prc++);
        _out("[STARTING]: Waiting $i/5");
        sleep(1);
        $pid=PID_NUM();
        if($unix->process_exists($pid)){break;}
    }

    $pid=PID_NUM();
    if($unix->process_exists($pid)){
        build_progress_restart("{starting} {APP_ARTICAPCAP} {success}",$restart_prc++);
        _out("[STARTING]: Success PID $pid");
        return true;

    }

    _out("[STARTING]: Failed");
    _out("[STARTING]: $cmd");
    return false;

}

function reload_local_dns():bool{
    if(is_file("/etc/init.d/unbound")){
        shell_exec("/etc/init.d/unbound reconfigure");

    }
    return true;
}



function install_service():bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/articapcap";
    $php5script=basename(__FILE__);
    $daemonbinLog="Artica PCAP Filter";


    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         articapcap";
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
    $f[]=" reload)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart|reload} (+ '--verbose' for more infos)\"";
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
function update_binary():bool{
    $srcfile="/usr/share/artica-postfix/bin/articapsniffer";
    $dstfile="/usr/sbin/articapsniffer";

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
        @chmod($dstfile,0755);
        return true;
    }
    _out("$dstfile Updating $dstfile ...");
    if(is_file($dstfile)){@unlink($dstfile);}
    @copy($srcfile,$dstfile);
    @chmod($dstfile,0755);
    return true;

}

function remove_service($INITD_PATH):bool{
    if(!is_file($INITD_PATH)){return true;}
    system("$INITD_PATH stop");
    if(is_file('/usr/sbin/update-rc.d')){ shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1"); }
    if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
    if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
    return true;
}
function build_progress_restart($text,$pourc):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"articapcap.restart");
}

function build_progress_delete($text,$pourc):bool{
    $cachefile=PROGRESS_DIR."/dansguardian2.databases.delete.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
    return true;

}

function stop($aspid=false):bool{
    $LOGBIN="Categories Service";
    $unix=new unix();
    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            _out("[STOPPING]: $LOGBIN Service Already Artica task running PID $pid since {$time}mn");
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();


    if(!$unix->process_exists($pid)){
        _out("[STOPPING]: $LOGBIN service already stopped...");
        return true;
    }
    $pid=PID_NUM();

    _out("[STOPPING]: $LOGBIN service Shutdown pid $pid...");


    unix_system_kill($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        _out("[STOPPING]: $LOGBIN service waiting pid:$pid $i/5...");
        sleep(1);
    }

    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        _out("[STOPPING]: $LOGBIN service success...");
        return true;
    }

    build_progress("{stopping_service} $pid ( force)",30);
    _out("[STOPPING]: $LOGBIN service shutdown - force - pid $pid...");
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        _out("[STOPPING]: $LOGBIN service waiting pid:$pid $i/5...");
        sleep(1);
    }

    if(!$unix->process_exists($pid)){
        _out("[STOPPING]: $LOGBIN service success...");
        return true;
    }

    _out("[STOPPING]: service failed...");
    return false;

}

function PID_NUM():int{
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/articapsniffer.pid");
    if($unix->process_exists($pid)){return $pid;}
    return $unix->PIDOF_PATTERN("[articapsniffer]");

}
function _out($text):bool{
    echo "Starting......: ".date("H:i:s")." [INIT]: $text\n";
    if(!function_exists("openlog")){return false;}
    openlog("articapcap", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}