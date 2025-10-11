<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.manager.inc');
$GLOBALS["OUTPUT"]=true;
$GLOBALS["TITLENAME"]="Transparent SSH Bastion";

if($argv[1]=="--stop"){stop();exit();}
if($argv[1]=="--start"){start();exit();}
if($argv[1]=="--restart"){restart();exit();}
if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--uninstall"){uninstall();exit();}
if($argv[1]=="--reload"){reload();exit();}
if($argv[1]=="--countries"){countries();exit();}




function build_progress($pourc,$text){
    $date=date("Y-m-d H:i:s");
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/sshportal.progress";
    echo "$date: [{$pourc}%] $text\n";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["CACHEFILE"],0755);
}
function build_monit(){
    @unlink("/etc/monit/conf.d/APP_SSHPORTAL.monitrc");
    $f = array();
    $f[] = "check process APP_SSHPORTAL with pidfile /var/run/sshportal.pid";
    $f[] = "\tstart program = \"/etc/init.d/sshportal start --monit\"";
    $f[] = "\tstop program = \"/etc/init.d/sshportal stop --monit\"";
    $f[] = "\tif 5 restarts within 5 cycles then timeout";
    $f[] = "";
    @file_put_contents("/etc/monit/conf.d/APP_SSHPORTAL.monitrc", @implode("\n", $f));
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
}

function restart(){
    build_progress(10,"{stopping_service}");
    stop();
    reload_externals();
    build_progress(50,"{starting_service}");

    if(!start(true)){
        build_progress(110,"{starting_service} {failed}");
        return;
    }
    build_progress(100,"{starting_service} {success}");
}

function countries()
{

    $SSHPortalDenyCountries = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHPortalDenyCountries")));
    if (!is_array($SSHPortalDenyCountries)) {
        $SSHPortalDenyCountries = array();
    }
    $SSHPortalCR = array();
    foreach ($SSHPortalDenyCountries as $CU => $none) {
        if (trim($CU) == null) {
            continue;
        }
        $SSHPortalCR[] = $CU;

    }

    echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} SSHPortalCR Done...\n";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHPortalCR", @implode(",", $SSHPortalCR));

    $q = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $results = $q->QUERY_SQL("SELECT * FROM ngx_stream_access_module ORDER BY zorder");
    $STATUS[0] = "deny";
    $STATUS[1] = "allow";
    $sshportalAllowOnly = array();


    foreach ($results as $md5 => $ligne) {
        $item = trim($ligne["item"]);
        if ($item == null) {
            continue;
        }
        $sshportalAllowOnly[] = "$item:{$STATUS[$ligne["allow"]]}";
    }
    echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} sshportalAllowOnly Done...\n";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("sshportalAllowOnly", @implode(",", $sshportalAllowOnly));
}

function create_localhost(){
    $q          = new lib_sqlite("/home/artica/SQLITE/sshdportal.db");
    $ligne      = $q->mysqli_fetch_array("SELECT id FROM hosts WHERE name='localhost'");
    $updated_at = date("Y-m-d H:i:s");
    if(intval($ligne["id"])==0){
        $q->QUERY_SQL("INSERT INTO hosts (comment,name,url,created_at,updated_at) VALUES ('Local SSH service','localhost','ssh://root@127.0.0.1:884','$updated_at','$updated_at')");
        $ligne      = $q->mysqli_fetch_array("SELECT id FROM hosts WHERE name='localhost'");

    }

    $id=intval($ligne["id"]);
    if($id==0){
        echo "Failed to create localhost reverse proxy...\n";
        return;
    }
    $q->QUERY_SQL("INSERT OR IGNORE INTO host_host_groups (host_id,host_group_id) VALUES ($id,1)");
    $q->QUERY_SQL("INSERT OR IGNORE INTO host_group_acls (acl_id,host_group_id) VALUES (1,$id)");



}

function uninstall(){
    build_progress(10,"{uninstall} {APP_SSHPORTAL}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSSHPortal",0);
    remove_service("/etc/init.d/sshportal");

    reload_externals();

    build_progress(100,"{uninstall} {APP_SSHPORTAL} {done}");
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

function reload_externals(){
    $unix       =  new unix();
    $php        =  $unix->LOCATE_PHP5_BIN();
    if(is_file("/etc/init.d/ssh")){
        build_progress(20,"{reconfiguring} {APP_OPENSSH}");
        shell_exec("/usr/sbin/artica-phpfpm-service -restart-ssh");
    }
    if(is_file("/etc/init.d/shellinabox")){
        build_progress(25,"{reconfiguring} {APP_SHELLINABOX}");
        system("/etc/init.d/shellinabox restart");
    }
    if(is_file("/etc/init.d/fail2ban")){
        build_progress(30,"{reloading} {APP_FAIL2BAN}");
        shell_exec("$php /usr/share/artica-postfix/exec.fail2ban.php --reload");
    }
}

function install(){


    build_progress(10,"{installing} {APP_SSHPORTAL}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSSHPortal",1);
    create_service();
    build_monit();

    reload_externals();

    build_progress(50,"{starting_service} {APP_SSHPORTAL}");
    if(!start(true)){
        build_progress(110,"{starting_service} {APP_SSHPORTAL} {failed}");
        return;
    }

    build_progress(100,"{starting_service} {APP_SSHPORTAL} {success}");


}

function create_service(){
    $unix           = new unix();
    $php            = $unix->LOCATE_PHP5_BIN();
    $INITD_PATH     = "/etc/init.d/sshportal";
    $php5script     = basename(__FILE__);
    $daemonbinLog   = $GLOBALS["TITLENAME"];



    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:       ".basename($INITD_PATH);
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
    $f[]=" reconfigure)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
    $f[]="    ;;";
    $f[]=" build)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
    $f[]="    ;;";
    $f[]=" force-reload)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --force-reload \$2 \$3";
    $f[]="    ;;";
    $f[]=" reload-database)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --reload-database \$2 \$3";
    $f[]="    ;;";
    $f[]=" reload-log)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --reload-log \$2 \$3";
    $f[]="    ;;";

    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: $INITD_PATH {start|stop|restart|force-reload|reload-log|reload-database|status} (+ '--verbose' for more infos)\"";
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

function MakeSyslog(){
    $RESTART=false;

    if(!is_file("/etc/rsyslog.d/sshportal.conf")){
        $RESTART=true;
    }


    $h=array();
    $h[]="if  (\$programname =='sshportal') then {";
    $h[]="\t-/var/log/sshportal.log";
    $h[]="\t& stop";
    $h[]="}";
    $h[]="";
    @file_put_contents("/etc/rsyslog.d/sshportal.conf",@implode("\n", $h));

    if($RESTART){
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }
}

function start($aspid=false){
    $unix               = new unix();
    $Masterbin          = $unix->find_program("sshportal");
    $nohup              = $unix->find_program("nohup");
    $sshdportalAddr     = null;
    $EnableSSHPortal    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSSHPortal"));
    $sshdportalPort     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshdportalPort"));
    $sshdportalInterface= trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshdportalInterface"));
    $sshdportalTimeOut  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshdportalTimeOut"));
    $sshportalDebug     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshportalDebug"));


    if(!is_dir("/var/log/sshdportal")){@mkdir("/var/log/sshdportal",0755,true);}

    if(!is_file($Masterbin)){
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, sshportal not installed\n";
        return false;
    }

    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";
            return true;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();


    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        echo "Starting......: ". date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";
        @file_put_contents("/var/run/sshportal.pid",$pid);
        return true;
    }



    if($EnableSSHPortal==0){
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableSSHPortal)\n";
        return false;
    }




    if($sshdportalInterface<>null){ $sshdportalAddr=$unix->InterfaceToIPv4($sshdportalInterface); }
    if($sshdportalPort==0){ $sshdportalPort=2222; }

    countries();
    MakeSyslog();

    $unix->KILL_PROCESSES_BY_PORT($sshdportalPort);
    $f[]=$nohup;
    $f[]="$Masterbin server";
    if($sshportalDebug==1){$f[]="--debug";}
    $f[]="--bind-address {$sshdportalAddr}:{$sshdportalPort}";
    $f[]="--logs-location /var/log/sshdportal";
    $f[]="--db-conn /home/artica/SQLITE/sshdportal.db";
    $f[]="--idle-timeout {$sshdportalTimeOut}s";





    $cmd=@implode(" ", $f) ." >/var/log/sshdportal/sshdportal.log 2>&1 &";

    if(is_file("/var/log/sshdportal/sshdportal.log")){@unlink("/var/log/sshdportal/sshdportal.log");}

    echo "Starting......: ". date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";


    shell_exec($cmd);

    for($i=1;$i<5;$i++){
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";
        sleep(1);
        $pid=PID_NUM();
        if($unix->process_exists($pid)){break;}
    }

    $pid=PID_NUM();

    if($unix->process_exists($pid)){
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";
        @file_put_contents("/var/run/sshportal.pid",$pid);
        extract_admin();
        create_localhost();
        return true;

    }else{
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";
        return false;
    }


}

function extract_admin(){
    $q=new lib_sqlite("/home/artica/SQLITE/sshdportal.db");

    $sshdPortalManager  = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshdPortalManager"));
    $sshdPortalPassword = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshdPortalPassword"));


    //"users" ("id" integer primary key autoincrement,"created_at" datetime,"updated_at" datetime,"deleted_at" datetime,"is_admin" bool,"email" varchar(255),"name" varchar(255),"comment" va

    $ligne=$q->mysqli_fetch_array("SELECT * FROM `users` WHERE id=1");
    $Admin=$ligne["name"];
    $Password=$ligne["invite_token"];
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Manager is: $Admin $Password\n";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("sshdPortalManager",$Admin);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("sshdPortalPassword",$Password);




}

function PID_NUM(){
    $unix       = new unix();
    $pid        = $unix->get_pid_from_file("/var/run/sshportal.pid");
    if($unix->process_exists($pid)){return $pid;}
    $Masterbin  = $unix->find_program("sshportal");
    return $unix->PIDOF_PATTERN($Masterbin);
}
function stop($aspid=false){
    $unix=new unix();
    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";
            return;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();


    if(!$unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";
        return;
    }
    $pid=PID_NUM();

    echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";
    unix_system_kill($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";
        sleep(1);
    }

    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";
        return;
    }

    echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";
        sleep(1);
    }

    if($unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";
        return;
    }

}