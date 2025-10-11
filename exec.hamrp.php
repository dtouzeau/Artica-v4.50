<?php
$GLOBALS["TITLENAME"]="Managed Reverse Proxy";
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.harmp.inc");
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
if($argv[1]=="--newnode"){connect_node();exit;}
if($argv[1]=="--newnodessh"){connect_node_ssh();exit;}
if($argv[1]=="--syncnodes"){SynchronizeNodes();exit;}
if($argv[1]=="--update-agent"){UpdateAgents($argv[2]);exit;}
if($argv[1]=="--sync-single"){SynchronizeNode($argv[2]);exit;}
if($argv[1]=="--sync-group"){SynchronizeGroup($argv[2]);exit;}
if($argv[1]=="--push-nginx"){PushNginx($argv[2]);exit;}
if($argv[1]=="--installv2"){installv2($argv[2],$argv[3],$argv[4]);exit;}



function uninstall():bool{
    build_progress(25, "{uninstalling}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableManagedReverseProxy",0);
    return build_progress(100, "{uninstalling} {success}");
}
function install():bool{
    $unix=new unix();
    build_progress(25, "{installing}");
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.wizards.php --uninstall-all");
    build_progress(30, "{installing}");
    $unix->framework_exec("exec.convert-to-sqlite.php --hamrp");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableManagedReverseProxy",1);
    return build_progress(100, "{installing} {success}");
}
function build_progress($prc,$txt):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$txt,"hamrp.progress");
}
function build_progress_connect($prc,$txt):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$txt,"harmp.connect.progress");
}

function connect_node_ssh():bool{
    $ConfigSSH=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDeployAgent"));
    $Config["ipaddr"]=$ConfigSSH["SERVER"];
    $Config["port"]=$ConfigSSH["RPORT"];
    $Config["UseSSL"]=1;
    $Config["add-node"]=$ConfigSSH["add-ssh"];
    echo "Connect Node to SSH Group ID: {$ConfigSSH["add-ssh"]}\n";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HarmpNewNode",base64_encode(serialize($Config)));
    return connect_node();
}
function connect_node():bool{
    $Config=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HarmpNewNode")));
    $unix=new unix();
    $unix->framework_execute("exec.convert-to-sqlite.php --harmp");
    $port=intval($Config["port"]);
    $host=$Config["ipaddr"];
    $UseSSL=$Config["UseSSL"];
    $Groupid=$Config["add-node"];
    if($port==0){
        $port=9503;
    }
    build_progress_connect(20,"{connecting} $host:$port..");
    $harmp=new harmpnode();
    echo "Set Group ID as $Groupid\n";
    $harmp->SetNode($host,$port,$UseSSL,$Groupid);
    $harmp->SetMyuuid($unix->GetUniqueID());

    if(!$harmp->LinkNode()){

        echo $harmp->EndPoint."\n";
        echo $harmp->mysql_error;
        build_progress_connect(110,"{failed} $harmp->mysql_error..");
        echo @implode("\n",$harmp->Traces);
        return false;
    }
    build_progress_connect(100,"{success} $host:$port..");
    echo @implode("\n",$harmp->Traces);
    return true;
}

function installv2_progress($uuid,$prc,$txt):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$txt,"system.installsoft.$uuid.progress");
}

function installv2($product,$key,$uuid):bool{
    installv2_progress($uuid,50,"{installing}");
    $ha=new harmpnode($uuid);
    if(!$ha->UpgradeSoft($product,$key)){
        return  installv2_progress($uuid,110,"{installing} {failed}");
    }
    installv2_progress($uuid,80,"{synchronize}");
    SynchronizeNode($uuid);
    return  installv2_progress($uuid,100,"{installing} {success}");
}

function UpdateAgents($groupid):bool{
    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
    $sql="SELECT uuid FROM hamrp WHERE groupid=$groupid ORDER BY zOrder";
    $results=$q->QUERY_SQL($sql);
    $f=0;
    $c=0;
    $p=10;
    build_progress_connect(10,"{starting} {update_agent}");
    foreach ($results as $ligne){
        $uuid=$ligne["uuid"];
        echo "Update $uuid\n";
        $ha=new harmpnode($uuid);
        $c++;
        $p++;
        if($p>95){$p=95;}
        build_progress_connect($p,"{starting} {update_agent} $uuid");
        if(!$ha->UpdateAgent()){
            echo @implode("\n",$ha->Traces);
            $f++;
            echo "Update $uuid {failed}\n";
            continue;
        }
        $p++;
        if($p>95){$p=95;}
        build_progress_connect($p,"{starting} {synchronize} $uuid");
        sleep(2);
        $ha->SyncSettings();

    }
    if($f>0){
        build_progress_connect(110,"$f {failed}");
        return false;
    }
    build_progress_connect(100,"$c nodes {success}");
    return true;
}

function SynchronizeNode($uuid):bool{

    build_progress_connect(50,"{synchronize} $uuid");
    $ha=new harmpnode($uuid);
    if(!$ha->SyncSettings()){
        build_progress_connect(110,"{synchronize} $uuid {failed}");
        return false;
    }
    return build_progress_connect(100,"{synchronize} $uuid {success}");
}
function SynchronizeGroup_progress($gpid,$prc,$txt):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$txt,"harmp.refresh.$gpid.progress");
}
function PushNginx_progress($gpid,$prc,$txt):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$txt,"harmp.nginx.$gpid.progress");
}

function PushCertCenter($groupid):bool{
    $db="/home/artica/SQLITE/certificates.$groupid.db";
    if(!is_file($db)){
        echo "Certificates Center: $db no such file!\n";
        return false;
    }
    $unix=new unix();
    $Tempfile=$unix->FILE_TEMP().".db";
    if(!$unix->compress($db,$Tempfile)){
        echo  "Certificates Center: ".$GLOBALS["COMPRESSOR_ERROR"]."\n";
        return false;

    }
    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
    $sql="SELECT uuid,enabled FROM hamrp WHERE groupid=$groupid ORDER BY zOrder";
    $results=$q->QUERY_SQL($sql);

    $c=20;
    $failed=0;
    foreach ($results as $ligne){
        $uuid=$ligne["uuid"];
        if(intval($ligne["enabled"])==0){
            echo "Certificates Center: Synchronize $uuid disabled, skip it\n";
            continue;
        }
        echo "Certificates Center: Synchronize $uuid\n";
        $c++;
        PushNginx_progress($groupid,$c,"{synchronize}");
        $harmp=new harmpnode($uuid);
        if(!$harmp->SyncCertCenter($Tempfile)){
            $failed++;
        }
    }
    @unlink($Tempfile);

    if ($failed>0){
        echo "Certificates Center: {synchronize} {failed} for $failed nodes\n";
    }
    return true;

}
function PushNginx($groupid):bool{

    $groupid=intval($groupid);
    if($groupid==0){
        return PushNginx_progress($groupid,110,"No groupid defined!");
    }

    $db="/home/artica/SQLITE/nginx.$groupid.db";
    PushNginx_progress($groupid,20,"{compressing}");

    if(!PushCertCenter($groupid)){
        return PushNginx_progress($groupid,110,"Synchronize certificate center {failed}");
    }

    if(!is_file($db)){
        echo "Failed to stat $db\n";
        return PushNginx_progress($groupid,110,"{compressing} {failed}");
    }
    $unix=new unix();
    $Tempfile=$unix->FILE_TEMP().".db";
    if(!$unix->compress($db,$Tempfile)){
        echo  $GLOBALS["COMPRESSOR_ERROR"]."\n";
        return PushNginx_progress($groupid,110,"{compressing} {failed}");

    }

    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
    $sql="SELECT uuid,enabled FROM hamrp WHERE groupid=$groupid ORDER BY zOrder";
    $results=$q->QUERY_SQL($sql);

    $c=20;
    $failed=0;
    foreach ($results as $ligne){
        $uuid=$ligne["uuid"];
        echo "Synchronize $uuid\n";

        if(intval($ligne["enabled"])==0){
            echo "Synchronize $uuid disabled, skip it\n";
            continue;
        }

        $c++;
        PushNginx_progress($groupid,$c,"{synchronize}");
        $harmp=new harmpnode($uuid);
        if(!$harmp->SyncNGinx($Tempfile)){
            $failed++;
        }
        $c++;
        PushNginx_progress($groupid,$c,"{synchronize} $uuid {status}");
        if(!$harmp->SyncSettings()){
            $failed++;
        }

    }
    @unlink($Tempfile);

    if ($failed>0){
        return PushNginx_progress($groupid,110,"{synchronize} {failed} for $failed nodes");
    }
    return PushNginx_progress($groupid,100,"{synchronize} {success}");

}

function SynchronizeGroup($groupid):bool{

    SynchronizeGroup_progress($groupid,20,"{synchronize}");
    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
    if(!$q->FIELD_EXISTS("hamrp","CpuPourc")){
        $q->QUERY_SQL("ALTER TABLE hamrp ADD CpuPourc TEXT NOT NULL DEFAULT '0'");
        $q->QUERY_SQL("ALTER TABLE hamrp ADD MemRow TEXT NOT NULL DEFAULT '0.0,0,0'");
    }

    if(!$q->FIELD_EXISTS("hamrp","DistributionName")){
        $q->QUERY_SQL("ALTER TABLE hamrp ADD DistributionName TEXT NOT NULL DEFAULT ''");
    }

    $sql="SELECT uuid FROM hamrp WHERE groupid=$groupid ORDER BY zOrder";
    $results=$q->QUERY_SQL($sql);

    $c=20;
    $failed=0;
    foreach ($results as $ligne){
        $uuid=$ligne["uuid"];
        echo "Synchronize $uuid\n";
        $c++;
        SynchronizeGroup_progress($groupid,$c,"{synchronize}");
        if(!SynchronizeNode($uuid)){
            $failed++;
        }
    }

    if($failed>0 ){
        return SynchronizeGroup_progress($groupid,110,"{synchronize} {failed} $failed {nodes}");

    }

    return SynchronizeGroup_progress($groupid,100,"{synchronize} {success}");


}


function SynchronizeNodes():bool{

    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".md5(__FUNCTION__).".pid";
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
       return false;

    }
    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
    if(!$q->FIELD_EXISTS("hamrp","CpuPourc")){
        $q->QUERY_SQL("ALTER TABLE hamrp ADD CpuPourc TEXT NOT NULL DEFAULT '0'");
        $q->QUERY_SQL("ALTER TABLE hamrp ADD MemRow TEXT NOT NULL DEFAULT '0.0,0,0'");
    }
    if(!$q->FIELD_EXISTS("hamrp","NginxRun")){
        $q->QUERY_SQL("ALTER TABLE hamrp ADD NginxRun INTEGER NOT NULL DEFAULT '0'");
        $q->QUERY_SQL("ALTER TABLE hamrp ADD HaProxyRun INTEGER NOT NULL DEFAULT '0'");
    }

    $sql="SELECT uuid FROM hamrp ORDER BY zOrder";
    $results=$q->QUERY_SQL($sql);

    foreach ($results as $ligne){
        $uuid=$ligne["uuid"];
        echo "Synchronize $uuid\n";
        $ha=new harmpnode($uuid);
        $ha->SyncSettings();
    }

    return true;

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
    $h[]="input(type=\"imfile\" file=\"/usr/local/ncpa/var/log/ncpa_listener.log\"  Tag=\"rustdesk:\")";
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