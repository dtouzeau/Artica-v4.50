<?php
$GLOBALS["TITLENAME"]="DNS Cache service";
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.resolv.conf.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--service"){install_service();exit;}
if($argv[1]=="--logsink"){logsink();exit;}



function uninstall():bool{
    build_progress(25, "{uninstalling}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableWazhuCLient",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WazhuClientEnrollment",0);
    $unix=new unix();
    $INITD_PATH="/etc/init.d/wazuh-agent";
    $unix->remove_service($INITD_PATH);
    build_progress(50, "{uninstalling}");
    build_progress(60, "{reconfiguring}");
    build_progress(100, "{uninstalling} {success}");
    return true;
}

function build(){
    $unix=new unix();
    $conf="/var/ossec/etc/ossec.conf";
    $WazhuClientServer = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientServer"));
    $WazhuClientServerPort   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientServerPort"));
    $WazhuClientGroup   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientGroup"));
    $WazhuClientEnrollment=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WazhuClientEnrollment"));
    if(strlen($WazhuClientServer)<3){
        return false;
    }
    $VER        = $unix->DEBIAN_VERSION();
    $xmlstr=@file_get_contents($conf);
    $ossec = new SimpleXMLElement($xmlstr);
    _out("Debian Major $VER");
    $ossec->client->server->address=$WazhuClientServer;
    $ossec->client->server->port=$WazhuClientServerPort;
    $ossec->client->{'config-profile'}="debian, debian$VER";

    if($WazhuClientEnrollment==0) {
        _out("Saving Wazhu Manager to enrollment mode");
        if (!property_exists($ossec->client, "enrollment")) {
            $ossec->client->addChild('enrollment');
            $ossec->client->enrollment->addChild('enabled', 'yes');
            $ossec->client->enrollment->addChild('groups', $WazhuClientGroup);
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WazhuClientEnrollment", 1);
        } else {
            try {
                $ossec->client->enrollment->groups[0] = $WazhuClientGroup;
            } catch (Exception $e) {
                print_r($e);
            }
        }
    }


    if (!property_exists($ossec, "labels")) {
        $ossec->addChild('labels');
        $ossec->labels->addChild("label",$unix->ArticaVersionString())->addAttribute("key","artica-version");
    }else{
        $index=findAttribute($ossec->labels->children(),"aws.instance-id");
        if($index>-1){
            $ossec->labels->label[$index]->attributes()->{'artica-version'}=$unix->ArticaVersionString();
        }
    }



    _out("Saving Wazhu Manager to $WazhuClientServer:$WazhuClientServerPort Group $WazhuClientGroup");
    $newstr=$ossec->asXML();
    $newstr=preg_replace( "/<\?xml.+?\?>/", "", $newstr );
    @file_put_contents($conf,$newstr);
    return true;


}
function logsink():bool{
    if(!logsink_config()){
        return false;
    }
    restart();
    return true;
}

function logsink_config():bool{
    $conf="/var/ossec/etc/ossec.conf";
    $xmlstr=@file_get_contents($conf);
    try {
        $ossec = new SimpleXMLElement($xmlstr);
    }
    catch(Exception $e){
        writelogs($e->getMessage(),__FUNCTION__,__FILE__,__LINE__);
        return false;
    }

    $zSyslog=array();
    foreach ($ossec->localfile as $index=>$stclass){
        $log_format=$stclass->log_format;
        $location=strval($stclass->location);
        if($log_format<>"syslog"){continue;}
        $zSyslog[$location]=true;
    }
    $LogSynWazuh=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSynWazuh"));

    if($LogSynWazuh==1){
        if(isset($zSyslog["/var/log/logsink-rtime.log"])){
            return false;
        }
        _out("Saving Wazhu to follow /var/log/logsink-rtime.log");
        $localfile=$ossec->addChild("localfile");
        $localfile->addChild("log_format","syslog");
        $localfile->addChild("location","/var/log/logsink-rtime.log");
        $newstr=$ossec->asXML();
        $newstr=preg_replace( "/<\?xml.+?\?>/", "", $newstr );
        @file_put_contents($conf,$newstr);
        return true;
        }

    $array=$ossec->xpath('/ossec_config/localfile');
    $SimpleXMLElements=array();
    $IsPresent=false;
    foreach ($array as $index=>$childs){
        $location=$childs->location;
        if($location=="/var/log/logsink-rtime.log"){
            $IsPresent=true;
            continue;
        }
        $subarray=array();
        foreach ($childs as $key=>$value){
            $subarray[$key]=strval($value);
        }
        $SimpleXMLElements[]=$subarray;
    }
    if(!$IsPresent){return false;}

    unset($ossec->localfile);

    foreach ($SimpleXMLElements as $index=>$SimpleXMLElement){
        $localfile=$ossec->addChild("localfile");
        foreach ($SimpleXMLElement as $key=>$value){
            $localfile->addChild($key,$value);
        }

    }
    $newstr=$ossec->asXML();
    $newstr=preg_replace( "/<\?xml.+?\?>/", "", $newstr );
    @file_put_contents($conf,$newstr);
    return true;

}

function findAttribute($object, $attribute):int {
    $c=0;
    foreach($object as $key => $value) {
        foreach ($value->attributes() as $a => $b) {
            if ($b == $attribute) {
                return $c;
            }
        }
        $c++;
    }
    return -1;
}

function install():bool{
    $unix=new unix();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableWazhuCLient",1);
    build_progress(25, "{installing}");
    $OS         = "Debian";
    $VER        = $unix->DEBIAN_VERSION();
    $sVers      = $unix->LINUX_VERS();
    $DIST_SUBVER= $sVers[1];
    $OSMYSHELL  = "/sbin/nologin";
    $addgroup   = "/usr/sbin/addgroup";
    $adduser    = "/usr/sbin/adduser";
    $MasterBin  = "/var/ossec/bin/wazuh-control";
    $USER="wazuh";
    $GROUP="wazuh";
    $DIR="/var/ossec";
    $DIST_NAME="debian";
    $DIST_VER=$VER;
    $WAZUH_GLOBAL_TMP_DIR="$DIR/packages_files";
    $SCRIPTS_DIR="$WAZUH_GLOBAL_TMP_DIR/agent_installation_scripts";
    $WAZUH_TMP_DIR="$WAZUH_GLOBAL_TMP_DIR/agent_config_files";
    $SCA_BASE_DIR="$SCRIPTS_DIR/sca";

    if(!is_file($MasterBin)){
        echo "$MasterBin no such file\n";
        build_progress(110, "{installing} {failed}");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableWazhuCLient",0);
        return false;
    }

    $cp=$unix->find_program("cp");
    $rm = $unix->find_program("rm");
    $chmod=$unix->find_program("chmod");
    $chown=$unix->find_program("chown");
    if(!$unix->SystemGroupExists($GROUP)){
        _out("Creating group $GROUP");
        system("$addgroup --system $GROUP");
    }
    if(!$unix->SystemUserExists($USER)){
        _out("Creating user $USER");
        system("$adduser --system --home $DIR --shell $OSMYSHELL --ingroup $GROUP $USER");
    }

    if(is_file("$SCRIPTS_DIR/gen_ossec.sh")) {
        shell_exec("$SCRIPTS_DIR/gen_ossec.sh conf agent $OS $VER $DIR >$DIR/etc/ossec.conf.new");
        @chmod("$DIR/etc/ossec.conf.new", 0660);
    }

    if(is_file("/etc/localtime")){
        shell_exec("$cp -pL /etc/localtime $DIR/etc/;");
        @chown("$DIR/etc/localtime","root");
        @chgrp("$DIR/etc/localtime",$GROUP);
    }
    if(is_file("/etc/init.d/artica-fsmonitor")){
        $unix->framework_exec("exec.fsmonitor.php --uninstall");
    }

    if(is_dir($SCA_BASE_DIR)) {
        build_progress(26, "{installing}");
        $SCA_TMP_DIR = "$SCA_BASE_DIR/$DIST_NAME/$DIST_VER";
        @mkdir("$DIR/ruleset/sca", 0755, true);

        $possiblesCA[] = "$SCA_BASE_DIR/$DIST_NAME/$DIST_VER/$DIST_SUBVER/sca.files";
        $possiblesCA[] = "$SCA_BASE_DIR/$DIST_NAME/$DIST_VER/sca.files";
        $possiblesCA[] = "$SCA_BASE_DIR/$DIST_NAME/sca.files";
        $possiblesCA[] = "$SCA_BASE_DIR/generic/sca.files";
        foreach ($possiblesCA as $sfile) {
            if (!is_file($sfile)) {
                continue;
            }
            $SCA_TMP_DIR = dirname($sfile);
            break;
        }

        $SCA_TMP_FILE = "$SCA_TMP_DIR/sca.files";
        shell_exec("$rm -f $DIR/ruleset/sca/*");
        $f = explode("\n", @file_get_contents($SCA_TMP_FILE));
        foreach ($f as $sca_file) {
            shell_exec("$cp -f $SCA_BASE_DIR/$sca_file $DIR/ruleset/sca");
        }

        shell_exec("$chmod 640 $DIR/ruleset/sca/*");
        shell_exec("$chown root:$GROUP $DIR/ruleset/sca/*");
        shell_exec("$rm -rf $SCA_BASE_DIR");
    }

    if(is_dir("$WAZUH_TMP_DIR/group")){
        build_progress(27, "{installing}");
        $list=$unix->DirFiles("$WAZUH_TMP_DIR/group");
        foreach ($list as $filename=>$none){
            shell_exec("$cp -f $filename $DIR/etc/shared/");
        }
        shell_exec("$rm -rf $WAZUH_TMP_DIR/group");
    }
    @touch("$DIR/logs/active-responses.log");
    shell_exec("$chown wazuh:wazuh $DIR/logs/active-responses.log");
    shell_exec("$chmod 0660 $DIR/logs/active-responses.log");

    if(is_file("$SCRIPTS_DIR/restore-permissions.sh")) {
        system("$SCRIPTS_DIR/restore-permissions.sh");
    }
    if(is_file("/etc/systemd/system/wazuh-agent.service")){
        @unlink("/etc/systemd/system/wazuh-agent.service");
    }
    if(is_dir($SCRIPTS_DIR)){
        system("$rm -rf $SCRIPTS_DIR");
    }
    if(is_dir($WAZUH_TMP_DIR)){
        system("$rm -rf $WAZUH_TMP_DIR");
    }
    build_progress(50, "{installing}");
    install_service();
    shell_exec("/usr/sbin/artica-phpfpm-service -permission-watch >/dev/null 2>&1 &");
    build_progress(100,"{success}");
    return true;
}
function WHAZU_PIDFILE_PATH($daemon="agentd"):string{
    $unix=new unix();
    $DirFiles=$unix->DirFiles("/var/ossec/var/run","^wazuh-.*?-[0-9]+");
    $svc=array();
    foreach ($DirFiles as $fname=>$none){
        if(preg_match("#wazuh-(.+?)-([0-9]+)\.pid$#",$fname,$re)){
            $svc[$re[1]]="/var/ossec/var/run/$fname";
        }
    }
    if(!isset($svc[$daemon])){
        return "/var/ossec/var/run/wazuh-$daemon.pid";
    }
    return $svc[$daemon];
}


function install_service() :bool{

    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/wazuh-agent";
    $php5script=basename(__FILE__);
    $daemonbinLog="Wazuh Agent";

    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         wazuh-agent";
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
    $f[]="	  exit 1";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="	  exit 1";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
    $f[]="	  exit 1";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reconfigure)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
    $f[]="	  exit 1";
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



function build_progress($prc,$txt){
    $unix=new unix();
    $unix->framework_progress($prc,$txt,"wazhu.client.progress");

}

function restart()
{
    build_progress(25, "{stopping_service}");
    $unix = new unix();
    $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
    $pid = $unix->get_pid_from_file($pidfile);
    if ($unix->process_exists($pid, basename(__FILE__))) {
        $time = $unix->PROCCESS_TIME_MIN($pid);
        _out("[info] Already Artica task running PID $pid since {$time}mn");
        build_progress(110, "{stopping_service} {failed}");
        return false;
    }

    @file_put_contents($pidfile, getmypid());
    stop(true);
    build();
    build_progress(50, "{starting_service}");
    if(start(true)) {
        build_progress(100, "{starting_service} {success}");
        return true;
    }
    build_progress(110, "{starting_service} {failed}");
    return false;
}

function _out($text){
    echo "Service.......: ".date("H:i:s")." [INIT]: Wazhu Agent $text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("wazhu", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}
function WHAZU_PID():int{
    $unix = new unix();
    $pid=$unix->get_pid_from_file(WHAZU_PIDFILE_PATH());
    if($unix->process_exists($pid)){return intval($pid);}
    return intval($unix->PIDOF("/var/ossec/bin/wazuh-agentd"));
}

function start($aspid = false){
    $unix = new unix();

    if (!$aspid) {
        $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
        $pid = $unix->get_pid_from_file($pidfile);
        if ($unix->process_exists($pid, basename(__FILE__))) {
            $time = $unix->PROCCESS_TIME_MIN($pid);
                _out("[warn] Already Artica task running PID $pid since {$time}mn");
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }
    $pid = WHAZU_PID();

    if ($unix->process_exists($pid)) {
        $timepid = $unix->PROCCESS_TIME_MIN($pid);
        _out("[info] Service already started $pid since {$timepid}Mn...");
        return true;
    }

    $chown=$unix->find_program("chown");
    shell_exec("$chown -R wazuh:wazuh /var/ossec");
    $finalCommandline="/var/ossec/bin/wazuh-control start > /var/log/wazuh-control.log 2>&1";
    $nohup = $unix->find_program("nohup");
    $cmdFallback = "$nohup $finalCommandline > /var/log/wazuh-control.log 2>&1 &";
    $cmd = "$finalCommandline";
   _out("[info] Starting service");
    $sh=$unix->sh_command($finalCommandline);
    $unix->go_exec($sh);

    $pid=$unix->PIDOF("/var/ossec/bin/wazuh-control");
    if($unix->process_exists($pid)){
        for ($i = 0; $i < 5; $i++) {
            $pid=$unix->PIDOF("/var/ossec/bin/wazuh-control");
            if (!$unix->process_exists($pid)) {
                break;
            }
            _out("[info] Starting service waiting wazuh-control pid:$pid $i/5...");
            sleep(1);
        }
    }


    for ($i = 1; $i < 5; $i++) {
        _out("[info] Starting, Waiting wazuh-agentd $i/5");
        sleep(1);
        $pid = WHAZU_PID();
        if ($unix->process_exists($pid)) {
            break;
        }
    }

    $pid = WHAZU_PID();
    echo $pid;
    if ($unix->process_exists($pid)) {
        _out("[info] Starting Success PID $pid");
        return true;
    }

    _out("[error] Starting failed $cmd");
    $f=explode("\n",@file_get_contents("/var/log/wazuh-control.log"));
    foreach ($f as $line){
        _out("[error] $line");
    }
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
            _out("[info] service Already Artica task running PID $pid since {$time}mn");
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid = WHAZU_PID();


    if (!$unix->process_exists($pid)) {
        _out("[info] Stopping service already stopped...");
        return true;
    }
    $pid = WHAZU_PID();
    _out("[info] Stopping service Shutdown pid $pid...");

    $unix->go_exec("/var/ossec/bin/wazuh-control stop");
    for ($i = 0; $i < 5; $i++) {
        $pid = WHAZU_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("[info] Stopping service waiting pid:$pid $i/5...");
        sleep(1);
    }
    $pid=$unix->PIDOF("/var/ossec/bin/wazuh-control");
    if($unix->process_exists($pid)){
        for ($i = 0; $i < 5; $i++) {
            $pid=$unix->PIDOF("/var/ossec/bin/wazuh-control");
            if (!$unix->process_exists($pid)) {
                break;
            }
            _out("[info] Stopping service waiting wazuh-control pid:$pid $i/5...");
            sleep(1);
        }
    }


    $pid = WHAZU_PID();
    if(!$unix->process_exists($pid)){
        _out("[info] Stopping {success}");
        return true;
    }

    unix_system_kill($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = WHAZU_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("[info] Stopping service waiting pid:$pid $i/5...");
        sleep(1);
    }

    $pid = WHAZU_PID();
    if (!$unix->process_exists($pid)) {
        _out("[info] Stopping service success...");
        return true;
    }

    _out("[info] Stopping service shutdown - force - pid $pid...");

    unix_system_kill_force($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = WHAZU_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("[info] Stopping service waiting pid:$pid $i/5...");
        sleep(1);
    }

    if ($unix->process_exists($pid)) {
        _out("[error] service failed...");
        return false;
    }

    return true;
}


