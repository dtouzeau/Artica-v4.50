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
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . "/ressources/class.keepalived.inc");
$GLOBALS["TITLENAME"] = "KeepAlived HA";
if ($argv[1] == "--monit") {
    monit();
    exit;
}
if ($argv[1] == "--init") {
    init();
    exit;
}
if ($argv[1] == "--enable") {
    enable();
    exit;
}
if ($argv[1] == "--disable") {
    disable();
    exit;
}
if ($argv[1] == "--enable-secondary_node") {
    enable_secondary_node();
    exit;
}
if ($argv[1] == "--disable-secondary_node") {
    disable_secondary_node();
    exit;
}

if ($argv[1] == "--start") {
    $GLOBALS["OUTPUT"] = true;
    start();
    exit();
}
if ($argv[1] == "--stop") {
    $GLOBALS["OUTPUT"] = true;
    stop();
    exit();
}
if ($argv[1] == "--restart") {
    $GLOBALS["OUTPUT"] = true;
    restart();
    exit();
}
if ($argv[1] == "--reconfigure") {
    reconfigure();
    exit;
}

if ($argv[1] == "--syslog") {
    $GLOBALS["OUTPUT"] = true;
    build_syslog();
    exit();
}

if ($argv[1] == "--sync-nodes") {
    sync_nodes($argv[2], $argv[3]);
    exit;
}
if ($argv[1] == "--setup-nodes") {
    setup_nodes($argv[2]);
    exit;
}
if ($argv[1] == "--node-delete-vips") {
    nodes_delete_vips($argv[2], $argv[3]);
    exit;
}

if ($argv[1] == "--node-delete-services") {
    nodes_delete_services($argv[2], $argv[3]);
    exit;
}
if ($argv[1] == "--node-delete-tracks") {
    nodes_delete_tracks($argv[2], $argv[3]);
    exit;
}
if ($argv[1] == "--node-delete") {
    nodes_delete($argv[2], $argv[3]);
    exit;
}

if ($argv[1] == "--action-delete-nodes-vips") {
    action_delete_nodes_vips($argv[2]);
    exit;
}


if ($argv[1] == "--action-delete-nodes-services") {
    action_delete_nodes_services($argv[2]);
    exit;
}

if ($argv[1] == "--action-delete-nodes-tracks") {
    action_delete_nodes_tracks($argv[2]);
    exit;
}

if ($argv[1] == "--action-delete-nodes") {
    action_delete_nodes($argv[2]);
    exit;
}

if ($argv[1] == "--delete-primary_node") {
    delete_primary_node($argv[2]);
    exit;
}

if ($argv[1] == "--sync-debug") {
    sync_debugMode();
    exit;
}


function enable()
{
    $unix = new unix();
    build_progress("{enable_service}", 15);
    $php = $unix->LOCATE_PHP5_BIN();
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
    if (!$q->FIELD_EXISTS("ID", "keepalived_primary_nodes")) {
        system("$php /usr/share/artica-postfix/exec.convert-to-sqlite.php --keepalived --force");
        keepalived_syslog_secondary_node("Creating keepalived db");
    }

    $secondary_nodeIsenable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));
    if ($secondary_nodeIsenable == 1) {
        build_progress("{keepalived_secondary_node_installed}", 110);
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_KEEPALIVED_ENABLE", 1);
    build_progress("{building_configuration}", 50);
    init();
    monit();
    state(true);
    cron(true);
    build_progress("{restart_service}", 60);
    restart();
    build_progress("{checking_service}", 70);
    $pid = PID_NUM();
    if (!$unix->process_exists($pid)) {
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state start APP_KEEPALIVED");
    }

    $pid = PID_NUM();
    for ($i = 0; $i < 6; $i++) {
        if ($unix->process_exists($pid)) {
            echo "keepalived service successfully started\n";
            break;
        }
        echo "keepalived service Waiting PID $pid....\n";
        sleep(1);
        $pid = PID_NUM();

    }

//    $pid = PID_NUM();
//    if (!$unix->process_exists($pid)) {
//        echo "keepalived service not started....\n";
//        exec("ps -aux | grep -i keepalived 2>&1", $cmdlineresults);
//        foreach ($cmdlineresults as $line) {
//            echo "Found process: $line\n";
//
//
//        }
//        build_progress("{failed}", 110);
//        return false;
//    }
    build_syslog();
    build_progress("{done}", 100);
    return true;
}


function enable_secondary_node()
{
    $unix = new unix();
    build_progress("{enable_service}", 15);
    $php = $unix->LOCATE_PHP5_BIN();
    keepalived_syslog_secondary_node("Enable failover secondary_node feature");
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");

    if (!$q->FIELD_EXISTS("ID", "keepalived_primary_nodes")) {
        system("$php /usr/share/artica-postfix/exec.convert-to-sqlite.php --keepalived --force");
        keepalived_syslog_secondary_node("Creating keepalived db");
    }

    $primary_nodeIsenable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE"));
    if ($primary_nodeIsenable == 1) {
        build_progress("{keepalived_primary_node_installed}", 110);
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_KEEPALIVED_ENABLE_SLAVE", 1);

    build_progress("{building_configuration}", 50);
    init();
    monit();
    state(true);
    cron(true);
    build_progress("{restart_service}", 60);
    restart();
    build_progress("{checking_service}", 70);
    $pid = PID_NUM();
    if (!$unix->process_exists($pid)) {
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state start APP_KEEPALIVED");
    }

    $pid = PID_NUM();
    for ($i = 0; $i < 6; $i++) {
        if ($unix->process_exists($pid)) {
            echo "keepalived service successfully started\n";
            break;
        }
        echo "keepalived service Waiting PID $pid....\n";
        sleep(1);
        $pid = PID_NUM();

    }

//    $pid = PID_NUM();
//    if (!$unix->process_exists($pid)) {
//        echo "keepalived service not started....\n";
//        exec("ps -aux | grep -i keepalived 2>&1", $cmdlineresults);
//        foreach ($cmdlineresults as $line) {
//            echo "Found process: $line\n";
//
//
//        }
//        build_progress("{failed}", 110);
//        return false;
//    }
    build_syslog();
    build_progress("{done}", 100);
    return true;
}

function disable()
{
    $unix = new unix();
    build_progress("{disable_service}", 15);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_KEEPALIVED_ENABLE", 0);
    build_progress("{building_configuration}", 50);
    $php = $unix->LOCATE_PHP5_BIN();
    build_progress("{stopping_service}", 60);
    state(false);
    cron(false);
    stop();
    remove_service("/etc/init.d/keepalive");
    build_progress("{removing_startup_scripts}", 70);
    if (is_file("/etc/monit/conf.d/APP_KEEPALIVED.monitrc")) {
        @unlink("/etc/monit/conf.d/APP_KEEPALIVED.monitrc");
        $unix->reload_monit();
    }
    build_progress("{restarting_artica_status}", 90);
    system("/etc/init.d/artica-status restart --force");

    build_progress("{done}", 100);
}

function disable_secondary_node()
{
    $unix = new unix();
    build_progress("{disable_service}", 15);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_KEEPALIVED_ENABLE_SLAVE", 0);
    build_progress("{building_configuration}", 50);
    $php = $unix->LOCATE_PHP5_BIN();
    build_progress("{stopping_service}", 60);
    state(false);
    cron(false);
    stop();
    remove_service("/etc/init.d/keepalive");
    build_progress("{removing_startup_scripts}", 70);
    if (is_file("/etc/monit/conf.d/APP_KEEPALIVED.monitrc")) {
        @unlink("/etc/monit/conf.d/APP_KEEPALIVED.monitrc");
        $unix->reload_monit();
    }
    build_progress("{restarting_artica_status}", 90);
    system("/etc/init.d/artica-status restart --force");

    build_progress("{done}", 100);
}

function start($aspid = false)
{
    $unix = new unix();
    $sock = new sockets();
    $keepalived_global_settings = new keepalived_global_settings();
    $primary_nodebin = $unix->find_program("keepalived");
    $php = $unix->LOCATE_PHP5_BIN();
    state(true);
    cron(true);
    build_syslog();
    if (!is_file("/etc/monit/conf.d/APP_KEEPALIVED.monitrc")) {
        monit();
    }
    if (!is_file($primary_nodebin)) {
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]}, keepalived not installed\n";
        }
        return;
    }
    if (!$aspid) {
        $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
        $pid = $unix->get_pid_from_file($pidfile);
        if ($unix->process_exists($pid, basename(__FILE__))) {
            $time = $unix->PROCCESS_TIME_MIN($pid);
            if ($GLOBALS["OUTPUT"]) {
                echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";
            }
            return;
        }
        @file_put_contents($pidfile, getmypid());
    }
    $pid = PID_NUM();


    if ($unix->process_exists($pid)) {
        $timepid = $unix->PROCCESS_TIME_MIN($pid);
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";
        }
        return;
    }
    if (!is_dir("/var/run/keepalived")) {
        @mkdir("/var/run/keepalived", 0755, true);
    }

    if (!file_exists("/var/log/keepalived.log")) {
        @file_put_contents("/var/log/keepalived.log", "");
    }

    $dtl = "";
    if ($keepalived_global_settings->log_detail == 1) {
        $dtl = "-D";
    }

    $cmd = "$primary_nodebin $dtl -S 0 -p /var/run/keepalived/keepalived.pid";
    if ($GLOBALS["OUTPUT"]) {
        echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service\n";
    }
    shell_exec($cmd);
    for ($i = 1; $i < 5; $i++) {
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";
        }
        sleep(1);
        $pid = PID_NUM();
        if ($unix->process_exists($pid)) {
            break;
        }
    }

    $pid = PID_NUM();
    echo $pid;
    if ($unix->process_exists($pid)) {
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";
        }
        return true;
    } else {
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";
        }
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";
        }
    }


}

function cron($action)
{
    $unix = new unix();
    $php = $unix->LOCATE_PHP5_BIN();
    if ($action) {
        if (!is_file("/etc/cron.d/keepalives-ping-nodes")) {
            $unix->Popuplate_cron_make("keepalives-ping-nodes", "0,2,4,6,8,10,12,14,16,18,20,22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54,56,58 * * * *", "exec.keepalived-state.php --ping");
            shell_exec("/etc/init.d/cron reload");

        }

    }
}

function state($action)
{
    if ($action) {
        if (!is_file("/etc/keepalived/keepalived-state.sh")) {
            $unix = new unix();
            $php = $unix->LOCATE_PHP5_BIN();
            $INITD_PATH = "/etc/keepalived/keepalived-state.sh";
            $php5script = "exec.keepalived-state.php";
            $daemonbinLog = "keepalived server";

            $f = array();
            $f[] = "#!/bin/sh";
            $f[] = "### BEGIN INIT INFO";
            $f[] = "# Provides:         keepalived-server";
            $f[] = "# Required-Start:    \$local_fs \$syslog";
            $f[] = "# Required-Stop:     \$local_fs \$syslog";
            $f[] = "# Should-Start:";
            $f[] = "# Should-Stop:";
            $f[] = "# Default-Start:     3 4 5";
            $f[] = "# Default-Stop:      0 1 6";
            $f[] = "# Short-Description: $daemonbinLog";
            $f[] = "# chkconfig: - 80 75";
            $f[] = "# description: $daemonbinLog";
            $f[] = "### END INIT INFO";

            $f[] = "$php /usr/share/artica-postfix/$php5script --state \$1 \$2 \$3";
            $f[] = "";


            echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
            @unlink($INITD_PATH);
            @file_put_contents($INITD_PATH, @implode("\n", $f));
            @chmod($INITD_PATH, 0755);
        }
    }
}

function stop($aspid = false)
{
    $unix = new unix();
    if (!$aspid) {
        $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
        $pid = $unix->get_pid_from_file($pidfile);
        if ($unix->process_exists($pid, basename(__FILE__))) {
            $time = $unix->PROCCESS_TIME_MIN($pid);
            if ($GLOBALS["OUTPUT"]) {
                echo "Stopping......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";
            }
            return;
        }
        @file_put_contents($pidfile, getmypid());
    }
    $pid = PID_NUM();
    if (!$unix->process_exists($pid)) {
        if ($GLOBALS["OUTPUT"]) {
            echo "Stopping......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";
        }
        return;
    }
    $pid = PID_NUM();
    $nohup = $unix->find_program("nohup");
    $php5 = $unix->LOCATE_PHP5_BIN();
    $kill = $unix->find_program("kill");

    if ($GLOBALS["OUTPUT"]) {
        echo "Stopping......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";
    }
    unix_system_kill($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = PID_NUM();
        if (!$unix->process_exists($pid)) {
            break;
        }
        if ($GLOBALS["OUTPUT"]) {
            echo "Stopping......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";
        }
        sleep(1);
    }

    $pid = PID_NUM();
    if (!$unix->process_exists($pid)) {
        if ($GLOBALS["OUTPUT"]) {
            echo "Stopping......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";
        }
        return;
    }

    if ($GLOBALS["OUTPUT"]) {
        echo "Stopping......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";
    }
    unix_system_kill_force($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = PID_NUM();
        if (!$unix->process_exists($pid)) {
            break;
        }
        if ($GLOBALS["OUTPUT"]) {
            echo "Stopping......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";
        }
        sleep(1);
    }

    if ($unix->process_exists($pid)) {
        if ($GLOBALS["OUTPUT"]) {
            echo "Stopping......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";
        }
        return;
    }
    return true;

}

function restart()
{
    build_progress("{stopping_service}", 25);
    $unix = new unix();
    $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
    $pid = $unix->get_pid_from_file($pidfile);
    if ($unix->process_exists($pid, basename(__FILE__))) {
        $time = $unix->PROCCESS_TIME_MIN($pid);
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";
        }
        build_progress("{stopping_service} {failed}", 110);
        return;
    }
    @file_put_contents($pidfile, getmypid());
    stop(true);
    build_progress("{starting_service}", 50);
    sleep(3);
    start(true);
    build_progress("{starting_service} {success}", 100);
}


function reconfigure()
{
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();



    if (!is_dir("/etc/keepalived")) {
        @mkdir("/etc/keepalived", 0755, true);
    }
    $hostname = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname");
    build_progress("{rebuild}:{conf}", 15);
    //Build Global Def
    $checkConf = false;
    state(true);
    $f = array();
    $keepalived_global_settings = new keepalived_global_settings();
    $f[] = "global_defs {";
    //$f[]="   enable_script_security";
    $f[] = "   max_auto_priority";

    $f[] = "}";
    //Build VTI
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
    $sql = "SELECT * FROM `keepalived_primary_nodes` WHERE enable='1'";
    $results = $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo $q->mysql_error_html();
    }
    $conf=array();
    foreach ($results as $index => $ligne) {
        $checkConf = true;
        $count_services = $q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM keepalived_services WHERE primary_node_id='{$ligne["ID"]}' AND enable='1'");
        $testDom=$ligne["testDom"];
        $testDomDNS=$ligne["testDomDNS"];
        if ($testDom==null){
            $testDom="http://articatech.com";
        }
        if($testDomDNS==null){
            $testDomDNS="cloudflare.com";
        }

        $checkDisk=intval($ligne["checkDisk"]);
        $checkRam=intval($ligne["checkRam"]);
        $checkLoad=intval($ligne["checkLoad"]);
        $disklimit=intval($ligne["disklimit"]);
        if ($disklimit==0){
            $disklimit=95;
        }

        $ramlimit=intval($ligne["ramlimit"]);
        if ($ramlimit==0){
            $ramlimit=95;
        }

        $loadlimit=intval($ligne["loadlimit"]);
        if ($loadlimit==0){
            $loadlimit=4;
        }

        $dnstesttimeout=intval($ligne["dnstesttimeout"]);
        if ($dnstesttimeout==0){
            $dnstesttimeout=3;
        }

        $proxytesttimeout=intval($ligne["proxytesttimeout"]);
        if ($proxytesttimeout==0){
            $proxytesttimeout=3;
        }

        $enableProxyCurl=intval($ligne["enableProxyCurl"]);
        $checkDebugLevel=intval($ligne["checkDebugLevel"]);
        $enableDnsResolver=intval($ligne["enableDnsResolver"]);
        //CREATE HEATH CHEKERS
        $sql_services = "SELECT * FROM keepalived_services WHERE primary_node_id='{$ligne["ID"]}' AND enable='1' ORDER BY ID";
        $results_services = $q->QUERY_SQL($sql_services);
        if (!$q->ok) {
            echo $q->mysql_error_html();
        }
        $unix = new unix();
        $php = $unix->LOCATE_PHP5_BIN();
        $INITD_PATH = "/etc/keepalived/config.yaml";
        $php5script = "exec.keepalived-state.php";
        $s = array();
        $s[] = "#!/bin/bash";
        $s[] = "### BEGIN INIT INFO";
        $s[] = "# Provides:         keepalived-health-checks-{$ligne["ID"]}";
        $s[] = "### END INIT INFO";
        $SQUIDEnable            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
        $EnablePostfix          = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
        $UnboundEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
        $EnablePDNS= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
        $PowerDNSEnableRecursor = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableRecursor"));
        $EnableDNSDist = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
        $DoNotUseLocalDNSCache= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DoNotUseLocalDNSCache"));
        $DoNotUseLocalDNSCache= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DoNotUseLocalDNSCache"));
        $EnableHaProxy= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaProxy"));
        $Enablehacluster= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
        $EnableNginx= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));

        $yamlConfig = array(
            "checks" => array(
                "disk"=>boolval($checkDisk),
                "load"=> boolval($checkLoad),
                "ram" =>boolval($checkRam),
                "dnscache"=>false,
                "dnsdist"=>false,
                "hacluster"=>false,
                "haproxy"=>false,
                "postfix"=>false,
                "powerdns"=>false,
                "powerdnsrecursor"=>false,
                "unbound"=>false,
                "proxycurl"=>boolval($enableProxyCurl),
                "dnsresolver"=>boolval($enableDnsResolver),
                "nginx"=>false,
            ),
            "debug"=>$checkDebugLevel,
            "instaceid"=>intval($ligne["ID"]),
            "lav"=>array(
                "disklimit"=>$disklimit,
                "dnstestdom"=>$testDomDNS,
                "dnstesttimeout"=> $dnstesttimeout,
                "loadlimit"=> $loadlimit,
                "proxytestdom"=>$testDom,
                "proxytesttimeout"=>$proxytesttimeout,
                "ramlimit"=>$ramlimit,
            ),
            "pid"=>array(
                "dnscachepid"=>"",
                "dnsfirewallpid"=>"",
                "haclusterpid"=>"",
                "haproxypid"=>"",
                "postfixpid"=>"",
                "powerdnspid"=>"",
                "powerdnsrecursorpid"=>"",
                "proxypid"=>"",
                "unboundpid"=>"",
                "nginxpid"=>"",
            ),
        );
        foreach ($results_services as $index => $services) {

            if($services['service']=="Proxy" && $SQUIDEnable==1) {
                $yamlConfig["checks"]["proxy"]=true;
                $yamlConfig["pid"]["proxypid"]=$services["script"];
            }


            if($services['service']=="PowerDNS" && $EnablePDNS==1) {
                $yamlConfig["checks"]["powerdns"]=true;
                $yamlConfig["pid"]["powerdnspid"]=$services["script"];
                if ($PowerDNSEnableRecursor==1){
                    $yamlConfig["checks"]["powerdnsrecursor"]=true;
                    $yamlConfig["pid"]["powerdnsrecursorpid"]=$services["script"];
                }
            }
            if($services['service']=="Unbound" && $UnboundEnabled==1) {
                $yamlConfig["checks"]["unbound"]=true;
                $yamlConfig["pid"]["unboundpid"]=$services["script"];
            }
            if($services['service']=="DNSFirewall" && $EnableDNSDist==1) {
                $yamlConfig["checks"]["dnsdist"]=true;
                $yamlConfig["pid"]["dnsfirewallpid"]=$services["script"];
            }
            if($services['service']=="DNSCache" && $DoNotUseLocalDNSCache==0) {
                $yamlConfig["checks"]["dnscache"]=true;
                $yamlConfig["pid"]["dnscachepid"]=$services["script"];
            }
            if($services['service']=="SMTP" && $EnablePostfix==1) {
                $yamlConfig["checks"]["postfix"]=true;
                $yamlConfig["pid"]["postfixpid"]=$services["script"];
            }
            if($services['service']=="HACluster" && $Enablehacluster==1) {
                $yamlConfig["checks"]["hacluster"]=true;
                $yamlConfig["pid"]["haclusterpid"]=$services["script"];
            }
            if($services['service']=="HAProxy" && $EnableHaProxy==1) {
                $yamlConfig["checks"]["haproxy"]=true;
                $yamlConfig["pid"]["haproxypid"]=$services["script"];
            }
            if($services['service']=="Nginx" && $EnableNginx==1) {
                $yamlConfig["checks"]["nginx"]=true;
                $yamlConfig["pid"]["nginxpid"]=$services["script"];
            }


        }

        @unlink($INITD_PATH);
        array_push($conf,$yamlConfig);
        //$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FAILOVER-YAML-CONFIG", serialize($yamlConfig));
        //CREATE CONF FILE

        if (intval($count_services["tcount"]) > 0 && is_file("/usr/share/artica-postfix/bin/go-shield/go-failover-checker")) {

            $f[] = "vrrp_script chk_vi_{$ligne["ID"]} {";
            $f[] = "   script \"/usr/share/artica-postfix/bin/go-shield/go-failover-checker -instance={$ligne["ID"]}\"";
            if (!empty($ligne["interval"])) {
                $f[] = "   interval {$ligne["interval"]}";
            }
            if (!empty($ligne["fall"])) {
                $f[] = "   fall {$ligne["fall"]}";
            }
            if (!empty($ligne["rise"])) {
                $f[] = "   rise {$ligne["rise"]}";
            }
            if (!empty($ligne["weight"])) {
                $f[] = "   weight {$ligne["weight"]}";
            }
            if (!empty($ligne["timeout"])) {
                $f[] = "   timeout {$ligne["timeout"]}";
            }
            $f[] = "}";
        }
        if($ligne["nopreempt"] == 1) {
            $ligne["state"] = "BACKUP";
        }
        $f[] = "";
        $f[] = "! vrrp_primary_node for {$ligne["primary_node_name"]}";
        $f[] = "vrrp_instance VI_{$ligne["ID"]} {";
        if (intval($ligne["use_vmac"])==1){
            $f[] = "   use_vmac";
        }
        if (intval($ligne["vmac_xmit_base"])==1 && intval($ligne["use_vmac"])==1){
            $f[] = "   vmac_xmit_base";
        }
        $f[] = "   state {$ligne["state"]}";
        $f[] = "   interface {$ligne["interface"]}";
        $f[] = "   virtual_router_id {$ligne["virtual_router_id"]}";
        $f[] = "   priority {$ligne["priority"]}";
        $f[] = "   advert_int {$ligne["advert_int"]}";
        if ($ligne["state"] == "BACKUP" && $ligne["nopreempt"] == 1) {
            $f[] = "   nopreempt";
        }
        if ($ligne["unicast_src_ip"] == 1) {
            $f[] = "   unicast_src_ip {$unix->InterfaceToIPv4($ligne["interface"])}";
            $f[] = "   unicast_ttl 10";

        }

        if ($ligne["auth_enable"] == 1) {
            $f[] = "   authentication {";
            $f[] = "     auth_type {$ligne["auth_type"]}";
            $f[] = "     auth_pass {$ligne["auth_pass"]}";
            $f[] = "   }";
        }
        $f[] = "   virtual_ipaddress {";
        $sql_vip = "SELECT * FROM keepalived_virtual_interfaces WHERE primary_node_id='{$ligne["ID"]}' AND enable='1' ORDER BY ID";
        $results_vips = $q->QUERY_SQL($sql_vip);
        if (!$q->ok) {
            echo $q->mysql_error_html();
        }
        foreach ($results_vips as $index => $vips) {
            $dev = (empty($vips["dev"])) ? "" : "dev {$vips["dev"]}";
            if (intval($ligne["use_vmac"])==0) {
                $f[] = "     {$vips["virtual_ip"]}/{$vips["netmask"]} $dev label {$vips["label"]}";
            }
            else {
                $f[] = "     {$vips["virtual_ip"]}/{$vips["netmask"]} $dev";
            }

        }
        $f[] = "   }";

        $sql_count_secondary_nodes = "SELECT COUNT(*) as Tcount from keepalived_secondary_nodes WHERE primary_node_id='{$ligne["ID"]}' ";
        $ligne_count_secondary_nodes = $q->mysqli_fetch_array($sql_count_secondary_nodes);
        if (!$q->ok) {
            $secondary_nodes_count = $q->mysql_error;
        } else {
            $secondary_nodes_count = $ligne_count_secondary_nodes["Tcount"];
        }
        if ($ligne["unicast_src_ip"] == 1) {
            $ttl = ($ligne["enable_peers_ttl"] == 0) ? "" : "min_ttl {$ligne["min_peers_ttl"]} max_ttl {$ligne["max_peers_ttl"]}";
            if (intval($ligne['isPrimaryNode']) == 1) {
                if ($secondary_nodes_count > 0) {
                    $f[] = "   unicast_peer {";
                    $sql_secondary_nodes = "SELECT * from keepalived_secondary_nodes WHERE primary_node_id='{$ligne["ID"]}'";
                    $results_secondary_nodes = $q->QUERY_SQL($sql_secondary_nodes);
                    if (!$q->ok) {
                        echo $q->mysql_error_html();
                    }
                    foreach ($results_secondary_nodes as $index => $secondary_node) {
                        $f[] = "     {$secondary_node["secondary_node_ip"]} $ttl";
                    }
                    $f[] = "   }";
                }
            } else {
                $peers = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FAILOVER-NODES-INFO-{$ligne["ID"]}"));
                $peers = explode(',', $peers['secondary_node_ip']);
                if (($key = array_search("{$unix->InterfaceToIPv4($ligne["interface"])}", $peers)) !== false) {
                    unset($peers[$key]);
                }
                $f[] = "   unicast_peer {";
                foreach ($peers as $index => $peer) {

                    $f[] = "     $peer $ttl";
                }

                $f[] = "   }";
            }
        }

        $sql_count_tracks = "SELECT COUNT(*) as Tcount from keepalived_track_interfaces WHERE primary_node_id='{$ligne["ID"]}' AND enable='1'";
        $ligne_count_tracks = $q->mysqli_fetch_array($sql_count_tracks);
        if (!$q->ok) {
            $tracks_count = $q->mysql_error;
        } else {
            $tracks_count = $ligne_count_tracks["Tcount"];
        }
        if ($tracks_count > 0) {
            $f[] = "   track_interface {";
            $sql_tracks = "SELECT * FROM keepalived_track_interfaces WHERE primary_node_id='{$ligne["ID"]}' AND enable='1' ORDER BY ID";
            $results_tracks = $q->QUERY_SQL($sql_tracks);
            if (!$q->ok) {
                echo $q->mysql_error_html();
            }
            foreach ($results_tracks as $index => $tracks) {
                $weight = (empty($tracks["weight"])) ? "" : "weight {$tracks["weight"]}";
                $f[] = "     {$tracks["interface"]} $weight";
            }
            $f[] = "   }";
        }
        if (intval($count_services["tcount"]) > 0) {
            $f[] = "   track_script {";
            $f[] = "     chk_vi_{$ligne["ID"]}";
            $f[] = "   }";
        }

        $notify = "";
        if ($ligne["notifty_enable"] == 1) {
            $notify = "; {$ligne["notifty"]}";
        }
        $f[] = "   notify \"/etc/keepalived/keepalived-state.sh$notify\"";
        $f[] = "}";
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FAILOVER-YAML-CONFIG", serialize($conf));

    //$GLOBALS["CLASS_SOCKETS"]->REST_API("/failover/build/yaml");
    //shell_exec("/usr/share/artica-postfix/bin/go-shield/go-failover-checker --compile");
    $kill = $unix->find_program("kill");
    $keepalived = $unix->find_program("keepalived");
    if ($checkConf) {
        @file_put_contents("/etc/keepalived/keepalived.conf.tmp", @implode("\n", $f));

        build_progress("{checking}:{conf}", 50);
        exec("$keepalived -t -f /etc/keepalived/keepalived.conf.tmp 2>&1", $serviceRes);
        foreach ($serviceRes as $line) {
            if (preg_match("#(?:Unexpected+)#", $line, $re)) {
                file_put_contents("/usr/share/artica-postfix/ressources/logs/web/keepalived.log", implode("\n", array_reverse($serviceRes)));
                build_progress("{failed}", 110);
                return false;
            }
        }
        unlink("/etc/keepalived/keepalived.conf.tmp");
    }
    @file_put_contents("/etc/keepalived/keepalived.conf", @implode("\n", $f));
    build_progress("{reloading}:{service}", 90);
    if ($checkConf) {
        shell_exec("$kill -s $($keepalived --signum=RELOAD) $(cat /var/run/keepalived/keepalived.pid)");
        build_progress("{success}", 100);
    } else {
        restart();
        build_progress("{success}", 100);
    }


}

function sync_debugMode()
{
    $unix = new unix();
    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        build_progress("{license_error}", 110);
        die();
    }
    $php = $unix->LOCATE_PHP5_BIN();
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
    build_progress("{getting primary_node info}", 10);
    //$results=$q->QUERY_SQL("SELECT * FROM keepalived_primary_nodes WHERE enable=1 ORDER BY ID");
    $results = $q->QUERY_SQL("SELECT * FROM keepalived_secondary_nodes WHERE enable='1' ");
    $prc = 15;
    $FINAL = true;
    $debug = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("keepalived_log_detail"));
    foreach ($results as $index => $ligne) {
        build_progress("{sending order to } {$ligne["secondary_node_ip"]}", $prc);
        $array = array();
        $ID = $ligne["ID"];
        $array["action"] = 'sync-debug-nodes';
        $array["secondary_node"]["primary_node_id"] = $ligne["primary_node_id"];
        $array["secondary_node"]["secondary_node_can_overwrite_settings"] = $ligne["secondary_node_can_overwrite_settings"];
        $array["secondary_node"]["secondary_node_ip"] = $ligne["secondary_node_ip"];
        $array["secondary_node"]["synckey"] = $ligne["synckey"];
        $array["secondary_node"]["enable"] = $ligne["enable"];
        $array["secondary_node"]["primaryNodeIP"] = $ligne["primary_node_ip"];
        $array["debug"] = $debug;

        $URI = "https://{$ligne["secondary_node_ip"]}:{$ligne["secondary_node_port"]}";
        echo $URI;
        build_progress("{$ligne["secondary_node_ip"]} says hello {$ligne["secondary_node_ip"]} ?", $prc++);

        if (!POST_INFOS($URI, $array, $ID)) {
            $FINAL = false;
            $q->QUERY_SQL("UPDATE keepalived_secondary_nodes SET status=1, errortext='{$GLOBALS["ERROR_INFO"]}' WHERE ID={$ligne["ID"]}");
        }
    }

    if (!$FINAL) {
        build_progress("{errors}", 110);
        return;
    }
    build_progress("{success}", 100);
}

function sync_nodes($primary_node_id = 0, $secondary_node_id = 0)
{
    $unix = new unix();
    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        build_progress("{license_error}", 110);
        die();
    }
    if ($primary_node_id == 0) {
        build_progress("empty_request}", 110);
        die();
    }

    $php = $unix->LOCATE_PHP5_BIN();
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
    $GET_UNICAST = $q->mysqli_fetch_array("SELECT unicast_src_ip FROM keepalived_primary_nodes WHERE ID='$primary_node_id' ");
    $query = "";
    if (intval($GET_UNICAST["unicast_src_ip"]) == 1) {
        $secondary_node_id = 0;
    }
    if (intval($secondary_node_id) > 0) {
        $query = "ID = '$secondary_node_id' AND";
    }

    build_progress("{getting primary_node info}", 10);
    //$results=$q->QUERY_SQL("SELECT * FROM keepalived_primary_nodes WHERE enable=1 ORDER BY ID");
    $GET_ALL_NODE = $q->mysqli_fetch_array("SELECT group_concat(secondary_node_ip) as secondary_node_ip FROM keepalived_secondary_nodes WHERE primary_node_id='$primary_node_id'");
    $results = $q->QUERY_SQL("SELECT * FROM keepalived_secondary_nodes WHERE $query primary_node_id='$primary_node_id' ");
    $prc = 15;
    $FINAL = true;
    foreach ($results as $index => $ligne) {
        build_progress("{sending order to } {$ligne["secondary_node_ip"]}", $prc);
        $array = array();
        $ID = $ligne["ID"];

        $array["action"] = 'sync-keepalived-nodes';
        $array["secondary_node"]["primary_node_id"] = $ligne["primary_node_id"];
        $array["secondary_node"]["secondary_node_can_overwrite_settings"] = $ligne["secondary_node_can_overwrite_settings"];
        $array["secondary_node"]["secondary_node_ip"] = $ligne["secondary_node_ip"];
        $array["secondary_node"]["synckey"] = $ligne["synckey"];
        $array["secondary_node"]["enable"] = $ligne["enable"];
        $array["secondary_node"]["primaryNodeIP"] = $ligne["primary_node_ip"];
        $array["secondary_node"]["last_sync"] = $ligne["last_sync"];
        $primary_node = $q->mysqli_fetch_array("SELECT * FROM keepalived_primary_nodes WHERE ID='{$ligne["primary_node_id"]}'");

        $GET_ALL_NODE ['secondary_node_ip'] = $GET_ALL_NODE['secondary_node_ip'] . ",{$unix->InterfaceToIPv4($primary_node["interface"])}";
        $array['TOKEN'] = serialize($GET_ALL_NODE);

        //NODE INFO
        $array["interface"] = $primary_node["interface"];
        $array["virtual_router_id"] = intval($primary_node["virtual_router_id"]);
        $array["priority"] = intval($ligne["priority"]);
        $array["nopreempt"] = intval($ligne["nopreempt"]);
        $array["advert_int"] = intval($primary_node["advert_int"]);
        $array["unicast_src_ip"] = intval($primary_node["unicast_src_ip"]);
        $array["enable_peers_ttl"] = intval($primary_node["enable_peers_ttl"]);
        $array["min_peers_ttl"] = intval($primary_node["min_peers_ttl"]);
        $array["max_peers_ttl"] = intval($primary_node["max_peers_ttl"]);
        $array["auth_enable"] = intval($primary_node["auth_enable"]);
        $array["auth_type"] = $primary_node["auth_type"];
        $array["auth_pass"] = $primary_node["auth_pass"];
        $array["notifty_enable"] = intval($primary_node["notifty_enable"]);
        $array["notifty"] = $primary_node["notifty"];
        $array["interval"] = intval($primary_node["interval"]);
        $array["fall"] = intval($primary_node["fall"]);
        $array["rise"] = intval($primary_node["rise"]);
        $array["weight"] = intval($primary_node["weight"]);
        $array["timeout"] = intval($primary_node["timeout"]);
        $array["testDom"] = $primary_node["testDom"];
        $array["testDomDNS"]=$primary_node["testDomDNS"];
        $array["enableProxyCurl"] = intval($primary_node["enableProxyCurl"]);

        $array["disklimit"] = intval($primary_node["disklimit"]);
        $array["ramlimit"] = intval($primary_node["ramlimit"]);
        $array["loadlimit"] = intval($primary_node["loadlimit"]);
        $array["checkDisk"] = intval($primary_node["checkDisk"]);
        $array["checkRam"] = intval($primary_node["checkRam"]);
        $array["checkLoad"] = intval($primary_node["checkLoad"]);
        $array["dnstesttimeout"] = intval($primary_node["dnstesttimeout"]);
        $array["proxytesttimeout"] = intval($primary_node["proxytesttimeout"]);
        $array["checkDebugLevel"] = intval($primary_node["checkDebugLevel"]);
        $array["enableDnsResolver"] = intval($primary_node["enableDnsResolver"]);

        $array["isPrimaryNode"] = intval($primary_node["isPrimaryNode"]);
        $array["primaryNodeIP"] = $ligne["primary_node_ip"];
        $array["primaryNodePort"] = intval($primary_node["primaryNodePort"]);
        $array["primaryNodeID"] = intval($primary_node["primaryNodeID"]);
        $array["enable"] = intval($primary_node["enable"]);
        $array["synckey"] = $primary_node["synckey"];
        $array["use_vmac"] = intval($primary_node["use_vmac"]);
        $array["vmac_xmit_base"] = intval($primary_node["vmac_xmit_base"]);
        //SERVICE INFO
        $services = $q->QUERY_SQL("SELECT * FROM keepalived_services WHERE primary_node_id='{$ligne["primary_node_id"]}' ");
        $aggregated_services_ids = $q->mysqli_fetch_array("SELECT group_concat(ID) as aggregated_ids FROM keepalived_services WHERE primary_node_id='{$ligne["primary_node_id"]}' ORDER BY ID");
        $array['aggregated_services_ids'] = $aggregated_services_ids["aggregated_ids"];
        foreach ($services as $index => $service) {
            $array["SERVICE"]["{$service["ID"]}"]["primary_node_id"] = $service["primary_node_id"];
            $array["SERVICE"]["{$service["ID"]}"]["service"] = $service["service"];
            $array["SERVICE"]["{$service["ID"]}"]["script"] = $service["script"];
            $array["SERVICE"]["{$service["ID"]}"]["enable"] = $service["enable"];
            $array["SERVICE"]["{$service["ID"]}"]["synckey"] = $service["synckey"];

        }
        //VIPS INFO
        $vips = $q->QUERY_SQL("SELECT * FROM keepalived_virtual_interfaces WHERE primary_node_id='{$ligne["primary_node_id"]}' ");
        $aggregated_vip_ids = $q->mysqli_fetch_array("SELECT group_concat(ID) as aggregated_ids FROM keepalived_virtual_interfaces WHERE primary_node_id='{$ligne["primary_node_id"]}' ORDER BY ID");

        $array['aggregated_vip_ids'] = $aggregated_vip_ids["aggregated_ids"];
        foreach ($vips as $index => $vip) {
            $array["VIP"]["{$vip["ID"]}"]["primary_node_id"] = $vip["primary_node_id"];
            $array["VIP"]["{$vip["ID"]}"]["virtual_ip"] = $vip["virtual_ip"];
            $array["VIP"]["{$vip["ID"]}"]["netmask"] = $vip["netmask"];
            $array["VIP"]["{$vip["ID"]}"]["dev"] = $vip["dev"];
            $array["VIP"]["{$vip["ID"]}"]["virtual_interface"] = $vip["virtual_interface"];
            $array["VIP"]["{$vip["ID"]}"]["enable"] = $vip["enable"];
            $array["VIP"]["{$vip["ID"]}"]["synckey"] = $vip["synckey"];

        }

        //TRACKS INFO
        $tracks = $q->QUERY_SQL("SELECT * FROM keepalived_track_interfaces WHERE primary_node_id='{$ligne["primary_node_id"]}'ORDER BY ID");
        $aggregated_track_ids = $q->mysqli_fetch_array("SELECT group_concat(ID) as aggregated_ids FROM keepalived_track_interfaces WHERE primary_node_id='{$ligne["primary_node_id"]}' ORDER BY ID");
        $array['aggregated_track_ids'] = $aggregated_track_ids["aggregated_ids"];
        foreach ($tracks as $index => $track) {
            $array["TRACK"]["{$track["ID"]}"]["id"] = $track["ID"];
            $array["TRACK"]["{$track["ID"]}"]["primary_node_id"] = $track["primary_node_id"];
            $array["TRACK"]["{$track["ID"]}"]["interface"] = $track["interface"];
            $array["TRACK"]["{$track["ID"]}"]["weight"] = $track["weight"];
            $array["TRACK"]["{$track["ID"]}"]["enable"] = $track["enable"];
            $array["TRACK"]["{$track["ID"]}"]["synckey"] = $track["synckey"];

        }
        $URI = "https://{$ligne["secondary_node_ip"]}:{$ligne["secondary_node_port"]}";
        echo $URI;
        build_progress("{$ligne["secondary_node_ip"]} says hello {$ligne["secondary_node_ip"]}", $prc++);

        if (!POST_INFOS($URI, $array, $ID)) {
            $FINAL = false;
            $q->QUERY_SQL("UPDATE keepalived_secondary_nodes SET status=1, errortext='{$GLOBALS["ERROR_INFO"]}' WHERE ID={$ligne["ID"]}");
        }
    }

    if (!$FINAL) {
        build_progress("{errors}", 110);
        return;
    }
    build_progress("{success}", 100);
}

function nodes_delete_vips($primary_node_id = 0, $synckey = 0)
{
    $unix = new unix();
    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        build_progress("{license_error}", 110);
        die();
    }

    if ($primary_node_id == 0 || $synckey == 0) {
        build_progress("empty_request}", 110);
        die();
    }

    $php = $unix->LOCATE_PHP5_BIN();
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");


    //$results=$q->QUERY_SQL("SELECT * FROM keepalived_primary_nodes WHERE enable=1 ORDER BY ID");
    $results = $q->QUERY_SQL("SELECT * FROM keepalived_secondary_nodes WHERE primary_node_id='$primary_node_id' ");
    $prc = 15;
    $FINAL = true;
    foreach ($results as $index => $ligne) {
        $array = array();
        $ID = $ligne["ID"];
        $array["action"] = 'delete-keepalived-vips';
        $array['vip']['synckey'] = $synckey;
        $array['primaryNodeID'] = $primary_node_id;
        $primary_nodeInfo = $q->mysqli_fetch_array("SELECT * FROM keepalived_primary_nodes WHERE ID='$primary_node_id'");
        $array["primaryNodeIP"] = $ligne["primary_node_ip"];
        $array["primaryNodePort"] = $primary_nodeInfo["primaryNodePort"];
        $array["secondary_node"]["primary_node_id"] = $ligne["primary_node_id"];
        $array["secondary_node"]["secondary_node_can_overwrite_settings"] = $ligne["secondary_node_can_overwrite_settings"];
        $array["secondary_node"]["secondary_node_ip"] = $ligne["secondary_node_ip"];
        $array["secondary_node"]["synckey"] = $ligne["synckey"];
        $URI = "https://{$ligne["secondary_node_ip"]}:{$ligne["secondary_node_port"]}";
        echo $URI;
        build_progress("{$ligne["secondary_node_ip"]} says hello {$ligne["secondary_node_ip"]} ?", $prc++);

        if (!POST_INFOS($URI, $array, $ID)) {
            $FINAL = false;
            $q->QUERY_SQL("UPDATE keepalived_secondary_nodes SET status=1, errortext='{$GLOBALS["ERROR_INFO"]}' WHERE ID={$ligne["ID"]}");
        }
    }

    if (!$FINAL) {
        build_progress("{errors}", 110);
        return;
    }
    build_progress("{success}", 100);
}


function nodes_delete_services($primary_node_id = 0, $synckey = 0)
{
    $unix = new unix();
    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        build_progress("{license_error}", 110);
        die();
    }

    if ($primary_node_id == 0 || $synckey == 0) {
        build_progress("empty_request}", 110);
        die();
    }

    $php = $unix->LOCATE_PHP5_BIN();
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");

    build_progress("Getting Service info of $primary_node_id and  $synckey", 10);
    //$results=$q->QUERY_SQL("SELECT * FROM keepalived_primary_nodes WHERE enable=1 ORDER BY ID");
    $results = $q->QUERY_SQL("SELECT * FROM keepalived_secondary_nodes WHERE primary_node_id='$primary_node_id' ");
    $prc = 15;
    $FINAL = true;
    foreach ($results as $index => $ligne) {
        build_progress("Send service delete order to {$ligne["secondary_node_ip"]}", 10);
        $array = array();
        $ID = $ligne["ID"];
        $array["action"] = 'delete-keepalived-services';
        $array['service']['synckey'] = $synckey;
        $array['primaryNodeID'] = $primary_node_id;
        $primary_nodeInfo = $q->mysqli_fetch_array("SELECT * FROM keepalived_primary_nodes WHERE ID='$primary_node_id'");
        $array["primaryNodeIP"] = $ligne["primary_node_ip"];
        $array["primaryNodePort"] = $primary_nodeInfo["primaryNodePort"];
        $array["secondary_node"]["primary_node_id"] = $ligne["primary_node_id"];
        $array["secondary_node"]["secondary_node_can_overwrite_settings"] = $ligne["secondary_node_can_overwrite_settings"];
        $array["secondary_node"]["secondary_node_ip"] = $ligne["secondary_node_ip"];
        $array["secondary_node"]["synckey"] = $ligne["synckey"];
        $URI = "https://{$ligne["secondary_node_ip"]}:{$ligne["secondary_node_port"]}";
        echo $URI;
        build_progress("{$ligne["secondary_node_ip"]} says hello {$ligne["secondary_node_ip"]} ?", $prc++);

        if (!POST_INFOS($URI, $array, $ID)) {
            $FINAL = false;
            $q->QUERY_SQL("UPDATE keepalived_secondary_nodes SET status=1, errortext='{$GLOBALS["ERROR_INFO"]}' WHERE ID={$ligne["ID"]}");
        }
    }

    if (!$FINAL) {
        build_progress("{errors}", 110);
        return;
    }
    build_progress("{success}", 100);
}

function nodes_delete_tracks($primary_node_id = 0, $synckey = 0)
{
    $unix = new unix();
    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        build_progress("{license_error}", 110);
        die();
    }

    if ($primary_node_id == 0 || $synckey == 0) {
        build_progress("empty_request}", 110);
        die();
    }

    $php = $unix->LOCATE_PHP5_BIN();
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");


    //$results=$q->QUERY_SQL("SELECT * FROM keepalived_primary_nodes WHERE enable=1 ORDER BY ID");
    $results = $q->QUERY_SQL("SELECT * FROM keepalived_secondary_nodes WHERE primary_node_id='$primary_node_id' ");
    $prc = 15;
    $FINAL = true;
    foreach ($results as $index => $ligne) {
        $array = array();
        $ID = $ligne["ID"];
        $array["action"] = 'delete-keepalived-tracks';
        $array['track']['synckey'] = $synckey;
        $array['primaryNodeID'] = $primary_node_id;
        $primary_nodeInfo = $q->mysqli_fetch_array("SELECT * FROM keepalived_primary_nodes WHERE ID='$primary_node_id'");
        $array["primaryNodeIP"] = $ligne["primary_node_ip"];
        $array["primaryNodePort"] = $primary_nodeInfo["primaryNodePort"];
        $array["secondary_node"]["primary_node_id"] = $ligne["primary_node_id"];
        $array["secondary_node"]["secondary_node_can_overwrite_settings"] = $ligne["secondary_node_can_overwrite_settings"];
        $array["secondary_node"]["secondary_node_ip"] = $ligne["secondary_node_ip"];
        $array["secondary_node"]["synckey"] = $ligne["synckey"];
        $URI = "https://{$ligne["secondary_node_ip"]}:{$ligne["secondary_node_port"]}";
        echo $URI;
        build_progress("{$ligne["secondary_node_ip"]} says hello {$ligne["secondary_node_ip"]} ?", $prc++);

        if (!POST_INFOS($URI, $array, $ID)) {
            $FINAL = false;
            $q->QUERY_SQL("UPDATE keepalived_secondary_nodes SET status=1, errortext='{$GLOBALS["ERROR_INFO"]}' WHERE ID={$ligne["ID"]}");
        }
    }

    if (!$FINAL) {
        build_progress("{errors}", 110);
        return;
    }
    build_progress("{success}", 100);
}

function delete_primary_node($primary_node_id = 0)
{
    $unix = new unix();
    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        build_progress("{license_error}", 110);
        die();
    }

    if ($primary_node_id == 0) {
        build_progress("empty_request}", 110);
        die();
    }

    $php = $unix->LOCATE_PHP5_BIN();
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");


    //$results=$q->QUERY_SQL("SELECT * FROM keepalived_primary_nodes WHERE enable=1 ORDER BY ID");
    build_progress("{deleting_primary_node}", 110);
    $FINAL = true;
    $sql_count = "SELECT COUNT(*) as Tcount, * FROM keepalived_primary_nodes WHERE ID='$primary_node_id'";
    $primary_node = $q->mysqli_fetch_array($sql_count);
    if (!$q->ok) {
        $count = $q->mysql_error;
    } else {
        $count = $primary_node["Tcount"];
    }
    //DELETE
    if ($count > 0) {
        //DELETE VIPS
        $sql = "SELECT * FROM keepalived_virtual_interfaces WHERE primary_node_id='{$primary_node["ID"]}'";
        $results = $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error_html();
        }

        foreach ($results as $index => $ligne) {
            $vips = new keepalived_vips($ligne['primary_node_id'], $ligne['ID']);
            $vips->delete(false);
        }
        //DELETE SERVICES
        $sql = "SELECT * FROM keepalived_services WHERE primary_node_id='{$primary_node["ID"]}'";
        $results = $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error_html();
        }
        foreach ($results as $index => $ligne) {
            $services = new keepalived_services($ligne['primary_node_id'], $ligne['ID']);
            $services->delete(false);
        }

        //DELETE TRACKS
        $sql = "SELECT * FROM keepalived_track_interfaces WHERE primary_node_id='{$primary_node["ID"]}'";
        $results = $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error_html();
        }
        foreach ($results as $index => $ligne) {
            $tracks = new keepalived_trackinterfaces($ligne['primary_node_id'], $ligne['ID']);
            $tracks->delete(false);
        }
        //DELETE SLAVES
        $sql = "SELECT * FROM keepalived_secondary_nodes WHERE primary_node_id='{$primary_node["ID"]}'";
        $results = $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error_html();
        }
        foreach ($results as $index => $ligne) {
            $secondary_node = new keepalived_secondary_nodes($ligne['primary_node_id'], $ligne['ID']);
            $secondary_node->delete(false);
        }

    }

    if (!$FINAL) {
        build_progress("{errors}", 110);
        return;
    }
    build_progress("{success}", 100);

}

function nodes_delete($primary_node_id = 0, $secondary_node_id = 0)
{
    $unix = new unix();
    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        build_progress("{license_error}", 110);
        die();
    }

    if ($primary_node_id == 0 || $secondary_node_id == 0) {
        build_progress("empty_request}", 110);
        die();
    }

    $php = $unix->LOCATE_PHP5_BIN();
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");


    //$results=$q->QUERY_SQL("SELECT * FROM keepalived_primary_nodes WHERE enable=1 ORDER BY ID");
    $results = $q->QUERY_SQL("SELECT * FROM keepalived_secondary_nodes WHERE ID='$secondary_node_id' AND primary_node_id='$primary_node_id' ");
    $prc = 15;
    $FINAL = true;
    foreach ($results as $index => $ligne) {
        $array = array();
        $ID = $ligne["ID"];
        $array["action"] = 'delete-keepalived-nodes';
        $array['primaryNodeID'] = $primary_node_id;
        $array["secondary_node"]["primary_node_id"] = $ligne["primary_node_id"];
        $array["secondary_node"]["secondary_node_can_overwrite_settings"] = $ligne["secondary_node_can_overwrite_settings"];
        $array["secondary_node"]["secondary_node_ip"] = $ligne["secondary_node_ip"];
        $array["secondary_node"]["synckey"] = $ligne["synckey"];
        $URI = "https://{$ligne["secondary_node_ip"]}:{$ligne["secondary_node_port"]}";
        echo $URI;
        build_progress("{$ligne["secondary_node_ip"]} says hello {$ligne["secondary_node_ip"]} ?", $prc++);

        if (!POST_INFOS($URI, $array, $ID)) {
            $FINAL = false;
            $q->QUERY_SQL("UPDATE keepalived_secondary_nodes SET status=1, errortext='{$GLOBALS["ERROR_INFO"]}' WHERE ID={$ligne["ID"]}");
        }
    }

    if (!$FINAL) {
        build_progress("{errors}", 110);
        return;
    }

    build_progress("{success}", 100);
}

function POST_INFOS($uri, $posted = array(), $ID = 0)
{
    $unix = new unix();

    $x = json_encode($posted, JSON_FORCE_OBJECT);
    $post_data["{$posted["action"]}"] = true;
    $post_data["secondary_node_ip"] = $posted["secondary_node"]["secondary_node_ip"];
    $post_data["post_data"] = base64_encode(serialize($x));
    if (isset($posted["debug"])) {
        $post_data["debug"] = $posted["debug"];
    }
    keepalived_syslog_master("Notify node: $uri");
    $uri_final = "$uri/nodes.listener.php";
    if ($GLOBALS["VERBOSE"]) {
        echo "$uri_final\n";
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate", "Cache-Control: no-cache,must revalidate", 'Expect:'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_URL, "$uri_final");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSLVERSION, 'all');
    curl_setopt($ch, CURLOPT_SSLVERSION, 'all');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_NOPROXY, "*");
    $data = curl_exec($ch);
    $CURLINFO_HTTP_CODE = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errno = curl_errno($ch);
    echo $data;

    if ($curl_errno == 28) {
        keepalived_syslog_secondary_node("$uri Error 28 Connection timed out...");
        $GLOBALS["ERROR_INFO"] = "{connection_timed_out}";
        return false;
    }


    if (preg_match("#<ERROR>(.+?)</ERROR>#is", $data, $re)) {
        keepalived_syslog_master("Error $uri says {$re[1]}");
        $GLOBALS["ERROR_INFO"] = $re[1];
        return false;
    }

    if (preg_match("#<STATUS>([0-9]+)</STATUS>#is", $data, $re)) {
        keepalived_syslog_master("$uri Entering in setup mode, waiting feedbacks");
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $q->QUERY_SQL("UPDATE keepalived_secondary_nodes SET status={$re[1]}, errortext='' WHERE ID=$ID");
        return true;
    }

    if (preg_match("#RETURNED_TRUE#is", $data)) {
        keepalived_syslog_master("$uri return True for order");
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $q->QUERY_SQL("UPDATE keepalived_secondary_nodes SET status=2, errortext='' WHERE ID=$ID");
        return true;
    }


    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    if ($GLOBALS["VERBOSE"]) {
        echo "CURLINFO_HTTP_CODE = $CURLINFO_HTTP_CODE $curl_errno=$curl_errno\n";
    }

}

function setup_nodes($data = null)
{
    if ($data == null) {
        client_report(3, "Empty post data...");
        return false;
    }
    $unix = new unix();
    $php = $unix->LOCATE_PHP5_BIN();

    $data = unserialize(base64_decode($data));
    $data = json_decode($data, TRUE);

    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
    $sql_count_nodes = "SELECT COUNT(*) as Tcount, * FROM keepalived_primary_nodes WHERE primaryNodeID='{$data["primaryNodeID"]}' AND synckey='{$data["secondary_node"]["synckey"]}'";
    $ligne_count_nodes = $q->mysqli_fetch_array($sql_count_nodes);
    if (!$q->ok) {
        $count_nodes = $q->mysql_error;
    } else {
        $count_nodes = $ligne_count_nodes["Tcount"];
    }
    //UPDATE
    if ($count_nodes > 0) {
        if ($ligne_count_nodes['secondaryNodeIsDisconnected'] == 0 || intval($data["secondary_node"]["secondary_node_can_overwrite_settings"]) == 0) {
            $keepalived_node = new keepalives_primary_nodes($ligne_count_nodes['ID']);
            $keepalived_node->primary_node_name = $unix->hostname_g();
            $keepalived_node->interface = $data["interface"];
            $keepalived_node->primary_node_state = "BACKUP";
            $keepalived_node->virtual_router_id = intval($data["virtual_router_id"]);
            $keepalived_node->priority = intval($data["priority"]);
            $keepalived_node->advert_int = intval($data["advert_int"]);
            $keepalived_node->use_vmac = intval($data["use_vmac"]);
            $keepalived_node->vmac_xmit_base = intval($data["vmac_xmit_base"]);
            $keepalived_node->nopreempt = intval($data["nopreempt"]);
            $keepalived_node->unicast_src_ip = $data["unicast_src_ip"];
            $keepalived_node->enable_peers_ttl = intval($data["enable_peers_ttl"]);
            $keepalived_node->min_peers_ttl = intval($data["min_peers_ttl"]);
            $keepalived_node->max_peers_ttl = intval($data["max_peers_ttl"]);
            $keepalived_node->auth_enable = intval($data["auth_enable"]);
            $keepalived_node->auth_type = $data["auth_type"];
            $keepalived_node->auth_pass = $data["auth_pass"];
            $keepalived_node->notifty_enable = intval($data["notifty_enable"]);
            $keepalived_node->notifty = $data["notifty"];
            $keepalived_node->interval = intval($data["interval"]);
            $keepalived_node->fall = intval($data["fall"]);
            $keepalived_node->rise = intval($data["rise"]);
            $keepalived_node->weight = intval($data["weight"]);
            $keepalived_node->timeout = intval($data["timeout"]);
            $keepalived_node->testDom = $data["testDom"];
            $keepalived_node->testDomDNS=$data["testDomDNS"];
            $keepalived_node->enableProxyCurl = intval($data["enableProxyCurl"]);

            $keepalived_node->disklimit = intval($data["disklimit"]);
            $keepalived_node->ramlimit = intval($data["ramlimit"]);
            $keepalived_node->loadlimit = intval($data["loadlimit"]);
            $keepalived_node->checkDisk = intval($data["checkDisk"]);
            $keepalived_node->checkRam = intval($data["checkRam"]);
            $keepalived_node->checkLoad = intval($data["checkLoad"]);
            $keepalived_node->dnstesttimeout = intval($data["dnstesttimeout"]);
            $keepalived_node->proxytesttimeout = intval($data["proxytesttimeout"]);
            $keepalived_node->checkDebugLevel = intval($data["checkDebugLevel"]);
            $keepalived_node->enableDnsResolver = intval($data["enableDnsResolver"]);


            $enable = intval($data["secondary_node"]["enable"]);
            if (intval($data["enable"]) == 0) {
                $enable = intval($data["enable"]);
            }
            $keepalived_node->enable = $enable;
            $keepalived_node->status = 0;
            $keepalived_node->isPrimaryNode = 0;
            $keepalived_node->primaryNodeIP = $data["secondary_node"]["primaryNodeIP"];
            $keepalived_node->primaryNodePort = intval($data["primaryNodePort"]);
            $keepalived_node->primaryNodeID = intval($data["primaryNodeID"]);
            $keepalived_node->secondaryNodeIsDisconnected = intval($data["secondary_node"]["secondary_node_can_overwrite_settings"]);
            $keepalived_node->lastSync = $data["secondary_node"]["last_sync"];
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FAILOVER-NODES-INFO-$keepalived_node->primary_node_id", $data['TOKEN']);
            $keepalived_node->save(false, false);


            //SERVICES
            $aggregated_services_ids = explode(',', $data['aggregated_services_ids']);
            if (strlen($aggregated_services_ids[0]) > 0) {
                foreach ($aggregated_services_ids as $id) {
                    $service_info = $q->mysqli_fetch_array("SELECT COUNT(*) as Tcount, * FROM keepalived_services WHERE synckey='{$data["SERVICE"]["{$id}"]['synckey']}'");
                    if ($service_info ['Tcount'] > 0) {
                        $keepalived_services = new keepalived_services($ligne_count_nodes['ID'], $service_info['ID']);
                        $keepalived_services->primary_node_id = $ligne_count_nodes['ID'];
                        $keepalived_services->service = $data["SERVICE"]["{$id}"]['service'];
                        $keepalived_services->script = $data["SERVICE"]["{$id}"]['script'];
                        $keepalived_services->enable = $data["SERVICE"]["{$id}"]['enable'];
                        $keepalived_services->synckey = $data["SERVICE"]["{$id}"]['synckey'];
                        $keepalived_services->save(false, false);
                    } else {
                        $keepalived_services = new keepalived_services();
                        $keepalived_services->service_id = 0;
                        $keepalived_services->primary_node_id = $ligne_count_nodes['ID'];
                        $keepalived_services->service = $data["SERVICE"]["{$id}"]['service'];
                        $keepalived_services->script = $data["SERVICE"]["{$id}"]['script'];
                        $keepalived_services->enable = $data["SERVICE"]["{$id}"]['enable'];
                        $keepalived_services->synckey = $data["SERVICE"]["{$id}"]['synckey'];
                        $keepalived_services->save(false, false);
                    }
                }
            }
            //VIPS
            $aggregated_vip_ids = explode(',', $data['aggregated_vip_ids']);
            if (strlen($aggregated_vip_ids[0]) > 0) {
                foreach ($aggregated_vip_ids as $id) {
                    $vip_info = $q->mysqli_fetch_array("SELECT COUNT(*) as Tcount, * FROM keepalived_virtual_interfaces WHERE synckey='{$data["VIP"]["{$id}"]['synckey']}'");
                    $dev = $data["VIP"]["{$id}"]['dev'];
                    $array = $unix->NETWORK_ALL_INTERFACES();
                    foreach ($array as $ifname => $ifconfig) {
                        $ipaddress = $ifconfig["IPADDR"];
                        if ($ipaddress == $data["VIP"]["{$id}"]['dev']) {
                            $dev = $ifname;
                            break;
                        }
                    }
                    if ($vip_info['Tcount'] > 0) {
                        $keepalived_vips = new keepalived_vips($ligne_count_nodes['ID'], $vip_info['ID']);
                        $keepalived_vips->primary_node_id = $ligne_count_nodes['ID'];
                        $keepalived_vips->dev = $dev;
                        $keepalived_vips->virtual_ip = $data["VIP"]["{$id}"]['virtual_ip'];
                        $keepalived_vips->netmask = $data["VIP"]["{$id}"]['netmask'];
                        $keepalived_vips->enable = $data["VIP"]["{$id}"]['enable'];
                        $keepalived_vips->synckey = $data["VIP"]["{$id}"]['synckey'];
                        $keepalived_vips->save(false, false);
                    } else {
                        $keepalived_vips = new keepalived_vips();
                        $keepalived_vips->primary_node_id = $ligne_count_nodes['ID'];
                        $keepalived_vips->dev = $dev;
                        $keepalived_vips->virtual_ip = $data["VIP"]["{$id}"]['virtual_ip'];
                        $keepalived_vips->netmask = $data["VIP"]["{$id}"]['netmask'];
                        $keepalived_vips->enable = $data["VIP"]["{$id}"]['enable'];
                        $keepalived_vips->synckey = $data["VIP"]["{$id}"]['synckey'];
                        $keepalived_vips->save(false, false);
                    }
                }
            }
            //TRACKS
            $aggregated_track_ids = explode(',', $data['aggregated_track_ids']);
            if (strlen($aggregated_track_ids[0]) > 0) {
                foreach ($aggregated_track_ids as $id) {
                    $tracks_info = $q->mysqli_fetch_array("SELECT COUNT(*) as Tcount, * FROM keepalived_track_interfaces WHERE synckey='{$data["TRACK"]["{$id}"]['synckey']}'");
                    if ($tracks_info['Tcount'] > 0) {
                        $keepalived_trackinterfaces = new keepalived_trackinterfaces($ligne_count_nodes['ID'], $tracks_info['ID']);
                        $keepalived_trackinterfaces->primary_node_id = $ligne_count_nodes['ID'];;
                        $keepalived_trackinterfaces->interface = $data["TRACK"]["{$id}"]['interface'];
                        $keepalived_trackinterfaces->weight = $data["TRACK"]["{$id}"]['weight'];
                        $keepalived_trackinterfaces->enable = $data["TRACK"]["{$id}"]['enable'];
                        $keepalived_trackinterfaces->synckey = $data["TRACK"]["{$id}"]['synckey'];
                        $keepalived_trackinterfaces->save(false, false);
                    } else {
                        $keepalived_trackinterfaces = new keepalived_trackinterfaces();
                        $keepalived_trackinterfaces->primary_node_id = $ligne_count_nodes['ID'];;
                        $keepalived_trackinterfaces->interface = $data["TRACK"]["{$id}"]['interface'];
                        $keepalived_trackinterfaces->weight = $data["TRACK"]["{$id}"]['weight'];
                        $keepalived_trackinterfaces->enable = $data["TRACK"]["{$id}"]['enable'];
                        $keepalived_trackinterfaces->synckey = $data["TRACK"]["{$id}"]['synckey'];
                        $keepalived_trackinterfaces->save(false, false);
                    }
                }
            }
            build_syslog();
            reconfigure();
            keepalived_syslog_secondary_node("[SETUP]: Report, {success}: ready for production mode 123-{$ligne_count_nodes['ID']}");
            $q->QUERY_SQL("UPDATE keepalived_primary_nodes SET status=100,errortext='OK' WHERE ID='{$ligne_count_nodes['ID']}'");
            client_report(100, "OK", "{$data["primaryNodeIP"]}:{$data["primaryNodePort"]}", $data["primaryNodeID"], $data["secondary_node"]["synckey"]);
            return true;
        } else {
            keepalived_syslog_secondary_node("[SETUP]: Report, {failed}: Slave {$data["secondary_node"]["secondary_node_ip"]} is disconnected from farm");
            client_report(120, "WARNING", "{$data["primaryNodeIP"]}:{$data["primaryNodePort"]}", $data["primaryNodeID"], $data["secondary_node"]["synckey"]);
            return false;
        }

    } //INSERT
    else {
        $keepalived_node = new keepalives_primary_nodes();
        $keepalived_node->primary_node_name = $unix->hostname_g();
        $keepalived_node->interface = $data["interface"];
        $keepalived_node->primary_node_state = "BACKUP";
        $keepalived_node->virtual_router_id = intval($data["virtual_router_id"]);
        $keepalived_node->priority = intval($data["priority"]);
        $keepalived_node->advert_int = intval($data["advert_int"]);
        $keepalived_node->use_vmac = intval($data["use_vmac"]);
        $keepalived_node->vmac_xmit_base = intval($data["vmac_xmit_base"]);
        $keepalived_node->nopreempt = intval($data["nopreempt"]);
        $keepalived_node->unicast_src_ip = $data["unicast_src_ip"];
        $keepalived_node->enable_peers_ttl = intval($data["enable_peers_ttl"]);
        $keepalived_node->min_peers_ttl = intval($data["min_peers_ttl"]);
        $keepalived_node->max_peers_ttl = intval($data["max_peers_ttl"]);
        $keepalived_node->auth_enable = intval($data["auth_enable"]);
        $keepalived_node->auth_type = $data["auth_type"];
        $keepalived_node->auth_pass = $data["auth_pass"];
        $keepalived_node->notifty_enable = intval($data["notifty_enable"]);
        $keepalived_node->notifty = $data["notifty"];
        $keepalived_node->interval = intval($data["interval"]);
        $keepalived_node->fall = intval($data["fall"]);
        $keepalived_node->rise = intval($data["rise"]);
        $keepalived_node->weight = intval($data["weight"]);
        $keepalived_node->timeout = intval($data["timeout"]);
        $keepalived_node->testDom= $data["testDom"];
        $keepalived_node->testDomDNS=$data["testDomDNS"];
        $keepalived_node->enableProxyCurl = intval($data["enableProxyCurl"]);

        $keepalived_node->disklimit = intval($data["disklimit"]);
        $keepalived_node->ramlimit = intval($data["ramlimit"]);
        $keepalived_node->loadlimit = intval($data["loadlimit"]);
        $keepalived_node->checkDisk = intval($data["checkDisk"]);
        $keepalived_node->checkRam = intval($data["checkRam"]);
        $keepalived_node->checkLoad = intval($data["checkLoad"]);
        $keepalived_node->dnstesttimeout = intval($data["dnstesttimeout"]);
        $keepalived_node->proxytesttimeout = intval($data["proxytesttimeout"]);
        $keepalived_node->checkDebugLevel = intval($data["checkDebugLevel"]);
        $keepalived_node->enableDnsResolver = intval($data["enableDnsResolver"]);

        $keepalived_node->enable = intval($data["enable"]);
        $keepalived_node->status = 0;
        $keepalived_node->isPrimaryNode = 0;
        $keepalived_node->primaryNodeIP = $data["secondary_node"]["primaryNodeIP"];
        $keepalived_node->primaryNodePort = intval($data["primaryNodePort"]);
        $keepalived_node->primaryNodeID = intval($data["primaryNodeID"]);
        $keepalived_node->synckey = $data["secondary_node"]["synckey"];
        $keepalived_node->secondaryNodeIsDisconnected = intval($data["secondary_node"]["secondary_node_can_overwrite_settings"]);
        $keepalived_node->lastSync = $data["secondary_node"]["last_sync"];
        $keepalived_node->save(false, false);
        $last_id = $keepalived_node->last_id;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FAILOVER-NODES-INFO-$last_id", $data['TOKEN']);
        //SERVICES
        $aggregated_services_ids = explode(',', $data['aggregated_services_ids']);
        if (strlen($aggregated_services_ids[0]) > 0) {
            foreach ($aggregated_services_ids as $id) {
                $keepalived_services = new keepalived_services();
                $keepalived_services->service_id = 0;
                $keepalived_services->primary_node_id = $last_id;
                $keepalived_services->service = $data["SERVICE"]["{$id}"]['service'];
                $keepalived_services->script = $data["SERVICE"]["{$id}"]['script'];
                $keepalived_services->enable = $data["SERVICE"]["{$id}"]['enable'];
                $keepalived_services->synckey = $data["SERVICE"]["{$id}"]['synckey'];
                $keepalived_services->save(false, false);
            }
        }
        //VIPS
        $aggregated_vip_ids = explode(',', $data['aggregated_vip_ids']);
        if (strlen($aggregated_vip_ids[0]) > 0) {
            foreach ($aggregated_vip_ids as $id) {
                $dev = $data["VIP"]["{$id}"]['dev'];
                $array = $unix->NETWORK_ALL_INTERFACES();
                foreach ($array as $ifname => $ifconfig) {
                    $ipaddress = $ifconfig["IPADDR"];
                    if ($ipaddress == $data["VIP"]["{$id}"]['dev']) {
                        $dev = $ifname;
                        break;
                    }
                }
                $keepalived_vips = new keepalived_vips();
                $keepalived_vips->virtualip_id = 0;
                $keepalived_vips->primary_node_id = $last_id;
                $keepalived_vips->dev = $dev;
                $keepalived_vips->virtual_ip = $data["VIP"]["{$id}"]['virtual_ip'];
                $keepalived_vips->netmask = $data["VIP"]["{$id}"]['netmask'];
                $keepalived_vips->enable = $data["VIP"]["{$id}"]['enable'];
                $keepalived_vips->synckey = $data["VIP"]["{$id}"]['synckey'];
                $keepalived_vips->save(false, false);
            }
        }

        //TRACKS
        $aggregated_track_ids = explode(',', $data['aggregated_track_ids']);
        if (strlen($aggregated_track_ids[0]) > 0) {
            foreach ($aggregated_track_ids as $id) {
                $keepalived_trackinterfaces = new keepalived_trackinterfaces();
                $keepalived_trackinterfaces->trackinterfaces_id = 0;
                $keepalived_trackinterfaces->primary_node_id = $last_id;
                $keepalived_trackinterfaces->interface = $data["TRACK"]["{$id}"]['interface'];
                $keepalived_trackinterfaces->weight = $data["TRACK"]["{$id}"]['weight'];
                $keepalived_trackinterfaces->enable = $data["TRACK"]["{$id}"]['enable'];
                $keepalived_trackinterfaces->synckey = $data["TRACK"]["{$id}"]['synckey'];
                $keepalived_trackinterfaces->save(false, false);
            }
        }
        build_syslog();
        reconfigure();
        restart();
        keepalived_syslog_secondary_node("[SETUP]: Report, {success}: ready for production mode");
        $q->QUERY_SQL("UPDATE keepalived_primary_nodes SET status=100,errortext='OK' WHERE ID=$last_id");

        client_report(100, "OK", "{$data["primaryNodeIP"]}:{$data["primaryNodePort"]}", $data["primaryNodeID"], $data["secondary_node"]["synckey"]);


    }

}

function action_delete_nodes_vips($data = null)
{
    if ($data == null) {
        client_report(3, "Empty post data...");
        return false;
    }
    $unix = new unix();
    $php = $unix->LOCATE_PHP5_BIN();

    $data = unserialize(base64_decode($data));
    $data = json_decode($data, TRUE);

    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");

    $sql_count = "SELECT COUNT(*) as Tcount, * FROM keepalived_virtual_interfaces WHERE  synckey='{$data["vip"]["synckey"]}'";
    $ligne = $q->mysqli_fetch_array($sql_count);
    if (!$q->ok) {
        $count = $q->mysql_error;
    } else {
        $count = $ligne["Tcount"];
    }
    //DELETE
    if ($count > 0) {
        $sql_secondary_nodeIsDisconnected = "SELECT * FROM keepalived_primary_nodes WHERE synckey='{$data["secondary_node"]["synckey"]}' ";
        $secondary_nodeIsDisconnected = $q->mysqli_fetch_array($sql_secondary_nodeIsDisconnected);
        if ($secondary_nodeIsDisconnected['secondaryNodeIsDisconnected'] == 0) {
            $keepalived_vips = new keepalived_vips($ligne['primary_node_id'], $ligne['ID']);
            $keepalived_vips->delete(true, false);
            keepalived_syslog_secondary_node("[SETUP]: Report, {success}: vip deleted from {$data["secondary_node"]["secondary_node_ip"]}");
            client_report(100, "OK", "{$data["primaryNodeIP"]}:{$data["primaryNodePort"]}", $data["primaryNodeID"], $data["secondary_node"]["synckey"]);
            return true;
        } else {
            keepalived_syslog_secondary_node("[SETUP]: Report, {failed}: Slave {$data["secondary_node"]["secondary_node_ip"]} is disconnected from farm");
            client_report(120, "WARNING", "{$data["primaryNodeIP"]}:{$data["primaryNodePort"]}", $data["primaryNodeID"], $data["secondary_node"]["synckey"]);
            return false;
        }

    }
    keepalived_syslog_secondary_node("[SETUP]: Report, {failed}: Virtual Nic not found");
    client_report(110, "ERROR", "{$data["primaryNodeIP"]}:{$data["primaryNodePort"]}", $data["primaryNodeID"], $data["secondary_node"]["synckey"]);
    return false;
}


function action_delete_nodes_tracks($data = null)
{
    if ($data == null) {
        client_report(3, "Empty post data...");
        return false;
    }
    $unix = new unix();
    $php = $unix->LOCATE_PHP5_BIN();

    $data = unserialize(base64_decode($data));
    $data = json_decode($data, TRUE);

    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");

    $sql_count = "SELECT COUNT(*) as Tcount, * FROM keepalived_track_interfaces WHERE  synckey='{$data["track"]["synckey"]}'";
    $ligne = $q->mysqli_fetch_array($sql_count);
    if (!$q->ok) {
        $count = $q->mysql_error;
    } else {
        $count = $ligne["Tcount"];
    }
    //DELETE
    if ($count > 0) {
        $sql_secondary_nodeIsDisconnected = "SELECT * FROM keepalived_primary_nodes WHERE synckey='{$data["secondary_node"]["synckey"]}' ";
        $secondary_nodeIsDisconnected = $q->mysqli_fetch_array($sql_secondary_nodeIsDisconnected);
        if ($secondary_nodeIsDisconnected['secondaryNodeIsDisconnected'] == 0) {
            $keepalived_tracks = new keepalived_trackinterfaces($ligne['primary_node_id'], $ligne['ID']);
            $keepalived_tracks->delete(true, false);
            keepalived_syslog_secondary_node("[SETUP]: Report, {success}: track interface deleted from {$data["secondary_node"]["secondary_node_ip"]}");
            client_report(100, "OK", "{$data["primaryNodeIP"]}:{$data["primaryNodePort"]}", $data["primaryNodeID"], $data["secondary_node"]["synckey"]);
            return true;
        } else {
            keepalived_syslog_secondary_node("[SETUP]: Report, {failed}: Slave {$data["secondary_node"]["secondary_node_ip"]} is disconnected from farm");
            client_report(120, "WARNING", "{$data["primaryNodeIP"]}:{$data["primaryNodePort"]}", $data["primaryNodeID"], $data["secondary_node"]["synckey"]);
            return false;
        }

    }
    keepalived_syslog_secondary_node("[SETUP]: Report, {failed}: Track interface not found");
    client_report(110, "ERROR", "{$data["primaryNodeIP"]}:{$data["primaryNodePort"]}", $data["primaryNodeID"], $data["secondary_node"]["synckey"]);
    return false;
}

function action_delete_nodes_services($data = null)
{
    if ($data == null) {
        client_report(3, "Empty post data...");
        return false;
    }
    $unix = new unix();
    $php = $unix->LOCATE_PHP5_BIN();

    $data = unserialize(base64_decode($data));
    $data = json_decode($data, TRUE);

    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");

    $sql_count = "SELECT COUNT(*) as Tcount, * FROM keepalived_services WHERE  synckey='{$data["service"]["synckey"]}'";
    $ligne = $q->mysqli_fetch_array($sql_count);
    if (!$q->ok) {
        $count = $q->mysql_error;
    } else {
        $count = $ligne["Tcount"];
    }
    //DELETE
    if ($count > 0) {
        $sql_secondary_nodeIsDisconnected = "SELECT * FROM keepalived_primary_nodes WHERE synckey='{$data["secondary_node"]["synckey"]}' ";
        $secondary_nodeIsDisconnected = $q->mysqli_fetch_array($sql_secondary_nodeIsDisconnected);
        if ($secondary_nodeIsDisconnected['secondaryNodeIsDisconnected'] == 0) {
            $keepalived_services = new keepalived_services($ligne['primary_node_id'], $ligne['ID']);
            $keepalived_services->delete(true, false);
            keepalived_syslog_secondary_node("[SETUP]: Report, {success}: service deleted from {$data["secondary_node"]["secondary_node_ip"]}");

            client_report(100, "OK", "{$data["primaryNodeIP"]}:{$data["primaryNodePort"]}", $data["primaryNodeID"], $data["secondary_node"]["synckey"]);
            return true;
        } else {
            keepalived_syslog_secondary_node("[SETUP]: Report, {failed}: Slave {$data["secondary_node"]["secondary_node_ip"]} is disconnected from farm");
            client_report(120, "WARNING", "{$data["primaryNodeIP"]}:{$data["primaryNodePort"]}", $data["primaryNodeID"], $data["secondary_node"]["synckey"]);
            return false;
        }

    }
    keepalived_syslog_secondary_node("[SETUP]: Report, {failed}: Peer not found");
    client_report(110, "ERROR", "{$data["primaryNodeIP"]}:{$data["primaryNodePort"]}", $data["primaryNodeID"], $data["secondary_node"]["synckey"]);
    return false;
}


function action_delete_nodes($data = null)
{
    if ($data == null) {
        client_report(3, "Empty post data...");
        return false;
    }
    $unix = new unix();
    $php = $unix->LOCATE_PHP5_BIN();

    $data = unserialize(base64_decode($data));
    $data = json_decode($data, TRUE);

    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
    $sql_count = "SELECT COUNT(*) as Tcount, * FROM keepalived_primary_nodes WHERE primaryNodeID='{$data["primaryNodeID"]}' AND synckey='{$data["secondary_node"]["synckey"]}'";
    $ligne_count = $q->mysqli_fetch_array($sql_count);
    if (!$q->ok) {
        $count = $q->mysql_error;
    } else {
        $count = $ligne_count["Tcount"];
    }
    //DELETE
    if ($count > 0) {
        //DELETE VIPS
        $sql = "SELECT * FROM keepalived_virtual_interfaces WHERE primary_node_id='{$ligne_count["ID"]}'";
        $results = $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error_html();
        }

        foreach ($results as $index => $ligne) {
            $vips = new keepalived_vips($ligne['primary_node_id'], $ligne['ID']);
            $vips->delete(false, false);
        }

        //DELETE SERVICES
        $sql = "SELECT * FROM keepalived_services WHERE primary_node_id='{$ligne_count["ID"]}'";
        $results = $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error_html();
        }

        foreach ($results as $index => $ligne) {
            $services = new keepalived_services($ligne['primary_node_id'], $ligne['ID']);
            $services->delete(false, false);
        }

        //DELETE TRACKS
        $sql = "SELECT * FROM keepalived_track_interfaces WHERE primary_node_id='{$ligne_count["ID"]}'";
        $results = $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error_html();
        }
        foreach ($results as $index => $ligne) {
            $tracks = new keepalived_trackinterfaces($ligne['ID']);
            $tracks->delete(false, false);
        }
        //DELETE primary_node
        $primary_node = new keepalives_primary_nodes($ligne_count["ID"]);
        $primary_node->delete(true, false);

        keepalived_syslog_secondary_node("[SETUP]: Report, {success}: {$data["secondary_node"]["secondary_node_ip"]}");
        return true;
    }
    keepalived_syslog_secondary_node("[SETUP]: Report, {failed}: primary_node not found in  {$data["secondary_node"]["secondary_node_ip"]}");
    client_report(110, "ERROR", "{$data["primaryNodeIP"]}:{$data["primaryNodePort"]}", $data["primaryNodeID"], $data["secondary_node"]["synckey"]);
    return false;
}

function build_syslog()
{
    $md5 = null;
    $tfile = "/etc/rsyslog.d/keepalived.conf";
    if (is_file($tfile)) {
        $md5 = md5_file($tfile);
    }
    $unix = new unix();
    $php = $unix->LOCATE_PHP5_BIN();
    $h = array();
    $h[] = "if  (\$programname  contains 'Keepalived') then {";
    $h[] = BuildRemoteSyslogs('keepalived');
    $h[] = "\t-/var/log/keepalived.log";

    $h[] = "\t& stop";
    $h[] = "}";
    $h[] = "";
    @file_put_contents($tfile, @implode("\n", $h));

    $md52 = md5_file($tfile);
    if ($md52 <> $md5) {
        system("/usr/sbin/artica-phpfpm-service -reconfigure-syslog");
    }

    if (!is_file("/etc/rsyslog.d/keepalived-master.conf")) {
        if (!file_exists("/var/log/keepalived-master.log")) {
            @file_put_contents("/var/log/keepalived-master.log", "");
        }
        $unix = new unix();
        $php = $unix->LOCATE_PHP5_BIN();
        $h = array();
        $h[] = "if  (\$programname  contains 'keepalived-master') then {";

        $h[] = "\t-/var/log/keepalived-master.log";

        $h[] = "\t& stop";
        $h[] = "}";
        $h[] = "";
        @file_put_contents("/etc/rsyslog.d/keepalived-master.conf", @implode("\n", $h));
        system("/usr/sbin/artica-phpfpm-service -reconfigure-syslog");
    }

    if (!is_file("/etc/rsyslog.d/keepalived-slave.conf")) {
        if (!file_exists("/var/log/keepalived-slave.log")) {
            @file_put_contents("/var/log/keepalived-slave.log", "");
        }
        $unix = new unix();
        $php = $unix->LOCATE_PHP5_BIN();
        $h = array();
        $h[] = "if  (\$programname  contains 'keepalived-slave') then {";

        $h[] = "\t-/var/log/keepalived-slave.log";

        $h[] = "\t& stop";
        $h[] = "}";
        $h[] = "";
        @file_put_contents("/etc/rsyslog.d/keepalived-slave.conf", @implode("\n", $h));
        system("/usr/sbin/artica-phpfpm-service -reconfigure-syslog");
    }
}

function client_report($status, $error = null, $uri = null, $primaryNodeID = null, $secondary_nodeID = null)
{
    $unix = new unix();
    keepalived_syslog_secondary_node("Report status $status ($error)");
    $post_data["primaryNodeID"] = $primaryNodeID;
    $post_data["secondary_nodeID"] = $secondary_nodeID;
    $post_data["keepalivedStatus"] = $status;
    $post_data["keepalivedError"] = $error;
    $post_data["hostname"] = $unix->hostname_g();
    $uri_final = "https://$uri/nodes.listener.php";
    if ($GLOBALS["VERBOSE"]) {
        echo "$uri_final\n";
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate", "Cache-Control: no-cache,must revalidate", 'Expect:'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_URL, "$uri_final");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSLVERSION, 'all');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_NOPROXY, "*");
    $data = curl_exec($ch);
    $CURLINFO_HTTP_CODE = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_errno = curl_errno($ch);

    if ($curl_errno > 0) {
        keepalived_syslog_secondary_node("$uri_final Error $curl_errno ...");
        return false;
    }


}

function keepalived_syslog_master($text)
{
    echo $text . "\n";
    if (!function_exists("syslog")) {
        return;
    }
    $LOG_SEV = LOG_INFO;
    openlog("keepalived-master", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
}

function keepalived_syslog_secondary_node($text)
{
    echo $text . "\n";
    if (!function_exists("syslog")) {
        return;
    }
    $LOG_SEV = LOG_INFO;
    openlog("keepalived-slave", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
}

function monit()
{
    $unix = new unix();
    $php = $unix->LOCATE_PHP5_BIN();
    $sh[] = "#!/bin/sh";
    $sh[] = "$php  /usr/share/artica-postfix/exec.keepalived.php --start --monit >/dev/null";
    $sh[] = "";

    @file_put_contents("/usr/sbin/keepalived-start.sh", @implode("\n", $sh));
    $sh = array();
    $sh[] = "#!/bin/sh";
    $sh[] = "$php  /usr/share/artica-postfix/exec.keepalived.php --stop --monit >/dev/null";
    $sh[] = "";
    @file_put_contents("/usr/sbin/keepalived-stop.sh", @implode("\n", $sh));


    @chmod("/usr/sbin/keepalived-start.sh", 0755);
    @chmod("/usr/sbin/keepalived-stop.sh", 0755);

    @unlink("/etc/monit/conf.d/APP_KEEPALIVED.monitrc");
    $f = array();
    $f[] = "check process APP_KEEPALIVED with pidfile /var/run/keepalived/keepalived.pid";
    $f[] = "\tstart program = \"/etc/init.d/keepalived start\"";
    $f[] = "\tstop program = \"/etc/init.d/keepalived stop\"";
    $f[] = "\tif 5 restarts within 5 cycles then timeout";
    $f[] = "";
    @file_put_contents("/etc/monit/conf.d/APP_KEEPALIVED.monitrc", @implode("\n", $f));
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
}

function init()
{
    if (is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")) {
        return;
    }
    $unix = new unix();
    $php = $unix->LOCATE_PHP5_BIN();
    $INITD_PATH = "/etc/init.d/keepalived";
    $php5script = "exec.keepalived.php";
    $daemonbinLog = "keepalived server";


    $f = array();
    $f[] = "#!/bin/sh";
    $f[] = "### BEGIN INIT INFO";
    $f[] = "# Provides:         keepalived-server";
    $f[] = "# Required-Start:    \$local_fs \$syslog";
    $f[] = "# Required-Stop:     \$local_fs \$syslog";
    $f[] = "# Should-Start:";
    $f[] = "# Should-Stop:";
    $f[] = "# Default-Start:     3 4 5";
    $f[] = "# Default-Stop:      0 1 6";
    $f[] = "# Short-Description: $daemonbinLog";
    $f[] = "# chkconfig: - 80 75";
    $f[] = "# description: $daemonbinLog";
    $f[] = "### END INIT INFO";

    $f[] = "";
    $f[] = "/sbin/sysctl -w net.ipv4.ip_nonlocal_bind=1 >/dev/null 2>&1 || true";
    $f[] = "/sbin/sysctl -w net.ipv4.ip_forward=1 >/dev/null 2>&1 || true";
    $f[] = "case \"\$1\" in";
    $f[] = " start)";
    $f[] = "    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[] = "    ;;";
    $f[] = "";
    $f[] = "  stop)";
    $f[] = "    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[] = "    ;;";
    $f[] = "";
    $f[] = " restart)";
    $f[] = "    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
    $f[] = "    ;;";
    $f[] = "";
    $f[] = " reconfigure)";
    $f[] = "    $php /usr/share/artica-postfix/$php5script --reconfigure \$2 \$3";
    $f[] = "    ;;";
    $f[] = "";
    $f[] = " reload)";
    $f[] = "    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
    $f[] = "    ;;";
    $f[] = "";
    $f[] = "  *)";
    $f[] = "    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
    $f[] = "    exit 1";
    $f[] = "    ;;";
    $f[] = "esac";
    $f[] = "exit 0\n";
    $f[] = "";

    echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH, 0755);

    if (is_file('/usr/sbin/update-rc.d')) {
        shell_exec("/usr/sbin/update-rc.d -f " . basename($INITD_PATH) . " defaults >/dev/null 2>&1");
    }

    if (is_file('/sbin/chkconfig')) {
        shell_exec("/sbin/chkconfig --add " . basename($INITD_PATH) . " >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " . basename($INITD_PATH) . " on >/dev/null 2>&1");
    }
}

function PID_NUM()
{

    $unix = new unix();
    $pid = $unix->get_pid_from_file("/var/run/keepalived/keepalived.pid");
    if ($unix->process_exists($pid)) {
        return $pid;
    }
    $primary_nodebin = $unix->find_program("starter");
    // return $unix->PIDOF_PATTERN("$primary_nodebin --start");
    return $unix->PIDOF("/usr/sbin/keepalived");

}

function build_progress($text, $pourc)
{
    $GLOBALS["CACHEFILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    echo "[{$pourc}%] $text\n";
    $array["POURC"] = $pourc;
    $array["TEXT"] = $text;
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["CACHEFILE"], 0755);
    if ($GLOBALS["OUTPUT"]) {
        sleep(1);
    }
}
function remove_service($INITD_PATH)
{
    if (!is_file($INITD_PATH)) {
        return;
    }
    system("$INITD_PATH stop");
    system("ipsec stop");

    if (is_file('/usr/sbin/update-rc.d')) {
        shell_exec("/usr/sbin/update-rc.d -f ipsec remove >/dev/null 2>&1");
    }

    if (is_file('/sbin/chkconfig')) {
        shell_exec("/sbin/chkconfig --del ipsec >/dev/null 2>&1");

    }

    if (is_file($INITD_PATH)) {
        @unlink($INITD_PATH);
    }
}