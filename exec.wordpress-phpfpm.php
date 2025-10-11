<?php


if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}
if (function_exists("posix_getuid")) {
    if (posix_getuid() <> 0) {
        die("Cannot be used in web server mode\n\n");
    }
}
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}
include_once(dirname(__FILE__) . "/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');


if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--reload"){reload();exit;}
if($argv[1]=="--stop"){stop();exit;}


function start(){
    $GLOBALS["SERVICE_NAME"]="Wordpress (FPM)";
    $unix=new unix();



    $pid=nginx_fpm_pid();
    if($unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already running PID $pid\n";}
        @file_put_contents("/var/run/wordpress-phpfpm.pid", $pid);

        return true;
    }

    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} find old instance\n";



    $pgrep=$unix->find_program("pgrep");
    exec("$pgrep -l -f \"php-fpm.*?/etc/php/7.3/fpm/php-fpm.conf\" 2>&1",$results);

    foreach ($results as $line){
        $line=trim($line);
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} \"$line\"\n";
        if(preg_match("#pgrep#",$line)){continue;}
        if(!preg_match("#^([0-9]+)\s+#",$line,$re)){continue;}
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Killin pid {$re[1]}\n";}
        $unix->KILL_PROCESS($re[1],9);

    }
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]}...\n";

    $me=__FILE__;

    $md5=null;
    if(is_file("/usr/sbin/wordpress-phpfpm")) {
        $md5 = md5_file("/usr/sbin/wordpress-phpfpm");
    }
    $source_fpm=nginx_fpm_locate();


    if(is_file($source_fpm)){
        $md5src=md5_file($source_fpm);
        if($md5src<>$md5){
            @unlink("/usr/sbin/wordpress-phpfpm");
            @copy($source_fpm, "/usr/sbin/wordpress-phpfpm");
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} install new binary\n";}
        }
    }
    @chmod("/usr/sbin/wordpress-phpfpm",0755);
    $cmd[]="/usr/sbin/wordpress-phpfpm";
    $cmd[]="-c \"/etc/artica-postfix/php-fpm.ini\"";
    $cmd[]="--fpm-config \"/etc/artica-postfix/wordpress-phpfpm.conf\"";
    $cmd[]="--pid \"/var/run/wordpress-phpfpm.pid\" --daemonize -R >/dev/null 2>&1 &";

    if(is_file("/var/run/wordpess-phpfpm.sock")){	@unlink("/var/run/wordpess-phpfpm.sock");}
    @unlink("/var/run/wordpress-phpfpm.pid");
    $cmdline=@implode(" ", $cmd);
    echo "Starting......: ".date("H:i:s")." [INIT]: $cmdline\n";
    system($cmdline);

    for($i=0;$i<8;$i++){
        $pid=nginx_fpm_pid();
        if($unix->process_exists($pid)){break;}
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting $i/8...\n";}
        sleep(1);
    }

    $pid=nginx_fpm_pid();
    if($unix->process_exists($pid)){
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Running PID $pid\n";
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Apply permissions\n";
        @chmod("/var/run/wordpess-phpfpm.sock",0777);
        @chmod("/var/run",0755);
        return true;
    }
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} FAILED!!!!\n";}
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $cmdline\n";}
    return false;
}

function install(){
    create_service();
    restart();
    monit_config();
}

function uninstall(){

    remove_service("/etc/init.d/wordpress-fpm");
    @unlink("/etc/monit/conf.d/APP_WORDPRESS.monitrc");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");

}

function monit_config(){

    $f[]="check process APP_WORDPRESS";
    $f[]="\twith pidfile /var/run/wordpress-phpfpm.pid";
    $f[]="\tstart program = \"/etc/init.d/wordpress-fpm start --monit\"";
    $f[]="\tstop program =  \"/etc/init.d/wordpress-fpm stop --monit\"";
    $f[]="\tif failed unixsocket /var/run/wordpess-phpfpm.sock then restart";
    $f[]="\tif 10 restarts within 10 cycles then timeout";
    @file_put_contents("/etc/monit/conf.d/APP_WORDPRESS.monitrc", @implode("\n", $f));
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");

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

function restart(){
    $GLOBALS["OUTPUT"]=true;
    $GLOBALS["SERVICE_NAME"]="Wordpress (FPM)";
    stop();
    build();
    start();

}
function nginx_fpm_locate(){
    $unix=new unix();
    $source_fpm = $unix->find_program("php-fpm7.3");
    if(is_file($source_fpm)){return $source_fpm;};
    $source_fpm = $unix->find_program("php-fpm7.0");
    if(is_file($source_fpm)){return $source_fpm;};
}

function reload(){
    $GLOBALS["OUTPUT"]=true;
    $GLOBALS["SERVICE_NAME"]="WordPress (FPM)";
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    if($GLOBALS["SLEEP"]>0){
        $unix->ToSyslog("Sleeping {$GLOBALS["SLEEP"]}s","php-fpm");
        sleep($GLOBALS["SLEEP"]);}

    shell_exec("/usr/sbin/artica-phpfpm-service -phpini -debug");
    $pid=nginx_fpm_pid();
    nginx_config();
    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} stopped...\n";}
        if(!start()){
            return false;
        }
        return true;
    }

    $kill=$unix->find_program("kill");
    system("$kill -USR2 $pid");
    if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PID $pid reloaded...\n";}
    $nginx=$unix->find_program("nginx");
    if(is_file("/etc/init.d/nginx")){
        system("$nginx -s reload");
    }
    return true;

}


function build(){
    $IsDocker=false;
    if(is_file("/etc/docker-method.conf")){
        $dock=trim(@file_get_contents("/etc/docker-method.conf"));
        if($dock=="webadm"){
            $IsDocker=true;
        }
    }

    $f[]="[global]";
    $f[]="pid = /var/run/wordpress-phpfpm.pid";
    $f[]="error_log = syslog";
    $f[]="syslog.facility = daemon";
    $f[]="syslog.ident = wordpress-phpfpm";
    $f[]="log_level = notice";
    $f[]=";emergency_restart_threshold = 0";
    $f[]=";emergency_restart_interval = 0";
    $f[]=";process_control_timeout = 0";
    $f[]="; process.max = 128";
    if(!$IsDocker) {
        $f[] = "process.priority = 10";
    }else{
        $f[] = "process.priority = 64";
    }
    $f[]="daemonize = yes";
    $f[]="rlimit_files = 1024";
    $f[]=";rlimit_core = 0";
    $f[]=";events.mechanism = epoll";
    $f[]=";systemd_interval = 10";
    $f[]="";
    $f[]="";
    $f[]="";
    $f[]="[wordpress]";
    $f[]="user = www-data";
    $f[]="group = www-data";
    $f[]="listen = /var/run/wordpress-phpfpm.sock";
    $f[]="listen.owner = www-data";
    $f[]="listen.group = www-data";
    $f[]="pm = dynamic";
    $f[]="pm.max_children = 150";
    $f[]="pm.start_servers = 2";
    $f[]="pm.min_spare_servers = 1";
    $f[]="pm.max_spare_servers = 10";
    $f[]="pm.process_idle_timeout = 10s;";
    $f[]="pm.max_requests = 500";
    $f[]="pm.status_path=/fpm-wordpress-status";
    $f[]="chdir = /";
    $f[]="security.limit_extensions =";
    $f[]="php_value[file_uploads] = \"On\"";
    $f[]="php_value[allow_url_fopen] = \"On\"";
    $f[]="php_value[memory_limit] = \"2000M\"";
    $f[]="php_value[upload_max_filesize] = \"512M\"";
    $f[]="php_value[cgi.fix_pathinfo] = \"1\"";
    $f[]="php_value[session.gc_probability]  = \"1\"";
    $f[]="php_value[session.save_handler]  = \"files\"";
    $f[]="php_value[session.save_path]  = \"/var/lib/php5\"";
    $f[]=";catch_workers_output = yes";
    $f[]=";clear_env = no";
    $f[]="php_flag[display_errors] = on";
    $f[]="php_admin_value[error_log] = /var/log/apache2/php.log";
    $f[]="php_admin_flag[log_errors] = on";
    $f[]="";
    @file_put_contents("/etc/artica-postfix/wordpress-phpfpm.conf",@implode("\n",$f));
}
function stop($aspid=false){
    $GLOBALS["SERVICE_NAME"]="Wordpress (FPM)";
    $unix=new unix();
    $pid=nginx_fpm_pid();


    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} already stopped...\n";}
        return;
    }

    $pid=nginx_fpm_pid();

    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
    unix_system_kill($pid);
    for($i=0;$i<5;$i++){
        $pid=nginx_fpm_pid();
        if(!$unix->process_exists($pid)){break;}
        unix_system_kill($pid);
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
        sleep(1);
    }

    $pid=nginx_fpm_pid();
    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
        return;
    }

    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=nginx_fpm_pid();
        if(!$unix->process_exists($pid)){break;}
        unix_system_kill_force($pid);
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
        sleep(1);
    }

    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
        return;
    }else{
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
    }
}

function nginx_fpm_pid(){
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/wordpress-phpfpm.pid");
    if(!$unix->process_exists($pid)){ return $unix->PIDOF("/usr/sbin/wordpress-phpfpm");}
    return $pid;
}
function create_service(){

    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/wordpress-fpm";
    $php5script=basename(__FILE__);
    $daemonbinLog="PHP5 FastCGI Process Manager Daemon";


    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         wordpress-fpm";
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
    $f[]="    $php /usr/share/artica-postfix/$php5script --start --script \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop --script \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --restart --script \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reload)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --reload --script \$2 \$3";
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


}