<?php
// SP 127
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
} if (function_exists("posix_getuid")) {
    if (posix_getuid()<>0) {
        die("Cannot be used in web server mode\n\n");
    }
}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.strongswan.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');

$GLOBALS["server-conf"]=false;
$GLOBALS["IPTABLES_ETH"]=null;
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["TITLENAME"]="IPSec";

if (is_array($argv)) {
    if (preg_match("#--verbose#", implode(" ", $argv))) {
        $GLOBALS["VERBOSE"]=true;
        $GLOBALS["debug"]=true;
        $GLOBALS["DEBUG"]=true;
        ini_set('html_errors', 0);
        ini_set('display_errors', 1);
        ini_set('error_reporting', E_ALL);
    }
}
if (preg_match("#--wait#", implode(" ", $argv))) {
    $GLOBALS["WAIT"]=true;
}
if ($GLOBALS["VERBOSE"]) {
    echo "Debug mode TRUE for {$argv[1]}\n";
}
$users=new usersMenus();
if ($users->KASPERSKY_WEB_APPLIANCE) {
    exit();
}

$strongswan=new strongswan();

if ($argv[1]=="--iptables-server") {
    BuildIpTablesServer();
    exit();
}
if ($argv[1]=="--iptables-delete") {
    iptables_delete_rules();
    exit();
}

if ($argv[1]=="--argvs") {
    LoadArgvs()."\n";
    exit();
}

if ($argv[1]=="--build-tunnels") {
    build_tunnels();
    exit();
}

if ($argv[1]=="--build-auth") {
    //build_auth();
    build_tunnels();
    exit();
}

if ($argv[1]=="--unlink-auth-file") {
    auth_file_remove($argv[2]);
    exit();
}

if ($argv[1]=="--unlink-cert-file") {
    cert_file_remove($argv[2]);
    exit();
}

if ($argv[1]=="--unlink-conf-file") {
    conf_file_remove($argv[2]);
    exit();
}

if ($argv[1]=="--build-cert") {
    build_cert($argv[2], $argv[3], $argv[4]);
    exit();
}

if ($argv[1]=="--stop") {
    $GLOBALS["OUTPUT"]=true;
    stop();
    exit();
}

if ($argv[1]=="--start") {
    $GLOBALS["OUTPUT"]=true;
    start();
    exit();
}

if ($argv[1]=="--restart") {
    $GLOBALS["OUTPUT"]=true;
    restart();
    exit();
}

if ($argv[1]=="--reconfigure") {
    $GLOBALS["OUTPUT"]=true;
    reconfigure();
    exit();
}

writelogs("Starting......: ".date("H:i:s")." strongSwan Unable to understand this command-line (" .implode(" ", $argv).")", "main", __FILE__, __LINE__);

function reconfigure()
{
    build_progress("{reconfiguring}", 15);
    LoadArgvs();
    $unix=new unix();
    $ipClass=new IP();
    $sock=new sockets();
    $ini=new Bs_IniHandler();
    $LOG=array();
    build_progress("{building_files}", 25);
    $ini->loadFile("/etc/artica-postfix/settings/Daemons/ArticastrongSwanSettings");
    //Configure Strongswan.conf
    $sysctl=$unix->find_program("sysctl");
    shell_exec("$sysctl -w net.ipv4.ip_forward=1 2>&1");
    shell_exec("$sysctl -w net.ipv4.conf.all.accept_redirects=0 2>&1");
    shell_exec("$sysctl -w net.ipv4.conf.all.send_redirects=0 2>&1");
    $charon=array();
    if (!isset($ini->_params["GLOBAL"]["StrongswanEnableDNSWINS"]) || intval($ini->_params["GLOBAL"]["StrongswanEnableDNSWINS"])==0) {
        $interface="";
        if (!isset($ini->_params["GLOBAL"]["StrongswanListenInterface"]) || !empty($ini->_params["GLOBAL"]["StrongswanListenInterface"])) {
            $interface="interface = {$ini->_params["GLOBAL"]["StrongswanListenInterface"]}";
        }

        $charon[]="# strongswan.conf - strongSwan configuration file
#
# Refer to the strongswan.conf(5) manpage for details
#
# Configuration changes should be made in the included files
charon {
        load_modular = yes
        $interface
        plugins {
                include strongswan.d/charon/*.conf
        }
        # two defined file loggers
        filelog {
            charon {
                # path to the log file, specify this as section name in versions prior to 5.7.0
                path = /var/log/charon.log
                # add a timestamp prefix
                time_format = %a %b %e %T %Y
                # prepend connection name, simplifies grepping
                ike_name = yes
                # overwrite existing files
                append = no
                # increase default loglevel for all daemon subsystems
                default = 2
                # flush each line to disk
                flush_line = yes
            }
            stderr {
                # more detailed loglevel for a specific subsystem, overriding the
                # default loglevel.
                ike = 2
                knl = 3
            }
        }
        # and two loggers using syslog
        syslog {
            # prefix for each log message
            identifier = charon-custom
            # use default settings to log to the LOG_DAEMON facility
            daemon {
            }
            # very minimalistic IKE auditing logs to LOG_AUTHPRIV
            auth {
                default = -1
                ike = 0
            }
        }
}
include strongswan.d/*.conf
        ";
    } else {
        $dns2="";
        if (!isset($ini->_params["GLOBAL"]["VPN_DNS_2"]) || !empty($ini->_params["GLOBAL"]["VPN_DNS_2"])) {
            $dns2=", {$ini->_params["GLOBAL"]["VPN_DNS_2"]}";
        }


        $wins2="";
        if (!isset($ini->_params["GLOBAL"]["VPN_WINS_2"]) || !empty($ini->_params["GLOBAL"]["VPN_WINS_2"])) {
            $wins2=", {$ini->_params["GLOBAL"]["VPN_WINS_2"]}";
        }


        $interface="";
        if (!isset($ini->_params["GLOBAL"]["StrongswanListenInterface"]) || !empty($ini->_params["GLOBAL"]["StrongswanListenInterface"])) {
            $interface="interface = {$ini->_params["GLOBAL"]["StrongswanListenInterface"]}";
        }

        $dns="dns = {$ini->_params["GLOBAL"]["VPN_DNS_1"]}$dns2";
        $wins="nbns = {$ini->_params["GLOBAL"]["VPN_WINS_1"]}$wins2";
        $charon[]="# strongswan.conf - strongSwan configuration file
#
# Refer to the strongswan.conf(5) manpage for details
#
# Configuration changes should be made in the included files
charon {
    load_modular = yes
    $interface
    plugins {
        include strongswan.d/charon/*.conf
        attr{
            {$dns}
            {$wins}
        }
    }
    # two defined file loggers
    filelog {
        charon {
            # path to the log file, specify this as section name in versions prior to 5.7.0
            path = /var/log/charon.log
            # add a timestamp prefix
            time_format = %a %b %e %T %Y
            # prepend connection name, simplifies grepping
            ike_name = yes
            # overwrite existing files
            append = no
            # increase default loglevel for all daemon subsystems
            default = 2
            # flush each line to disk
            flush_line = yes
        }
        stderr {
            # more detailed loglevel for a specific subsystem, overriding the
            # default loglevel.
            ike = 2
            knl = 3
        }
    }
    # and two loggers using syslog
    syslog {
        # prefix for each log message
        identifier = charon-custom
        # use default settings to log to the LOG_DAEMON facility
        daemon {
        }
        # very minimalistic IKE auditing logs to LOG_AUTHPRIV
        auth {
            default = -1
            ike = 0
        }
    }
}
include strongswan.d/*.conf
        ";
    }
    //@file_put_contents("/etc/strongswan.conf", @implode($charon));
    $file = str_replace("\r\n", "", $charon);
    $charon_file=fopen('/etc/strongswan.conf', "w");
    foreach ($file as $lines) {
        fputs($charon_file, "$lines");
    }
    build_progress("{building_plugins}", 45);
    //Configure strongswan.d/charon/dhcp.conf
    $dhcp_plugin=array();
    if (!isset($ini->_params["GLOBAL"]["strongSwanEnableDHCP"]) || intval($ini->_params["GLOBAL"]["strongSwanEnableDHCP"])==0) {
        $dhcp_plugin[]="dhcp {
    # Always use the configured server address.
    # force_server_address = no

    # Derive user-defined MAC address from hash of IKE identity.
    # identity_lease = no

    # Interface name the plugin uses for address allocation.
    # interface =

    # Whether to load the plugin. Can also be an integer to increase the
    # priority of this plugin.
    load = yes

    # DHCP server unicast or broadcast IP address.
    # server = 255.255.255.255   
} 
        ";
    } else {
        if (!isset($ini->_params["GLOBAL"]["strongSwanDHCPForceServerAddress"]) || intval($ini->_params["GLOBAL"]["strongSwanDHCPForceServerAddress"])==0) {
            $force_server_address = "# force_server_address = no";
        } else {
            $force_server_address = "force_server_address = yes";
        }

        if (!isset($ini->_params["GLOBAL"]["strongSwanDHCPIdentityLease"]) || intval($ini->_params["GLOBAL"]["strongSwanDHCPIdentityLease"])==0) {
            $identity_lease = "# identity_lease = no";
        } else {
            $identity_lease = "identity_lease = yes";
        }

        if (!isset($ini->_params["GLOBAL"]["StrongswanDHCPListenInterface"]) || empty($ini->_params["GLOBAL"]["StrongswanDHCPListenInterface"])) {
            $interface = "# interface =";
        } else {
            $interface = "interface = {$ini->_params["GLOBAL"]["StrongswanDHCPListenInterface"]}";
        }

        if (!isset($ini->_params["GLOBAL"]["strongSwanDHCPServer"]) || empty($ini->_params["GLOBAL"]["strongSwanDHCPServer"])) {
            $dhcpserver = "# server = 255.255.255.255";
        } else {
            $dhcpserver = "server = {$ini->_params["GLOBAL"]["strongSwanDHCPServer"]}";
        }
        $dhcp_plugin[]="dhcp {
    # Always use the configured server address.
    $force_server_address

    # Derive user-defined MAC address from hash of IKE identity.
    $identity_lease

    # Interface name the plugin uses for address allocation.
    $interface

    # Whether to load the plugin. Can also be an integer to increase the
    # priority of this plugin.
    load = yes

    # DHCP server unicast or broadcast IP address.
    $dhcpserver
} 
        ";
    }
    //@file_put_contents("/etc/strongswan.d/charon/dhcp.conf", @implode($dhcp_plugin));
    $file = str_replace("\r\n", "", $dhcp_plugin);
    $dhcp_file=fopen('/etc/strongswan.d/charon/dhcp.conf', "w");
    foreach ($file as $lines) {
        fputs($dhcp_file, "$lines");
    }
    build_progress("{building_tunnels}", 75);
    $ipsec=$unix->find_program("ipsec");
    //shell_exec("$ipsec update");
    build_tunnels(false);
    build_progress("{restart}", 85);
    restart();
    build_progress("{done}", 100);
}


function restart()
{
    build_progress(25, "{stopping_service}");
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pid=$unix->get_pid_from_file($pidfile);
    if ($unix->process_exists($pid, basename(__FILE__))) {
        $time=$unix->PROCCESS_TIME_MIN($pid);
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";
        }
        build_progress_str(110, "{stopping_service} {failed}");
        return;
    }
    @file_put_contents($pidfile, getmypid());
    stop(true);
    build_progress_str(50, "{starting_service}");
    sleep(3);
    start(true);
    build_progress_str(100, "{starting_service} {success}");
}


function start($aspid=false)
{
    $unix=new unix();
    $sock=new sockets();
    $Masterbin=$unix->find_program("ipsec");
    $php=$unix->LOCATE_PHP5_BIN();
    if(!is_file("/etc/monit/conf.d/APP_STRONGSWAN_VICI.monitrc")){
        shell_exec("$php /usr/share/artica-postfix/exec.strongswan.enable.php --monit");
    }
    if(!is_file("/etc/monit/conf.d/APP_STRONGSWAN_VICI_STATS.monitrc")){
        shell_exec("$php /usr/share/artica-postfix/exec.strongswan.enable.php --monit");
    }
    if(!is_file("/etc/init.d/ipsec-stats")){
        shell_exec("$php /usr/share/artica-postfix/exec.strongswan.enable.php --init");
    }

    if(!is_file("/usr/local/lib/python2.7/dist-packages/vici/__init__.py")){
        echo "Installing python vici....\n";
        $pip=$unix->find_program("pip");
        system("$pip install vici");
    }

    if (!is_file($Masterbin)) {
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, arpd not installed\n";
        }
        return;
    }

    if (!$aspid) {
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if ($unix->process_exists($pid, basename(__FILE__))) {
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if ($GLOBALS["OUTPUT"]) {
                echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";
            }
            return;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();


    if ($unix->process_exists($pid)) {
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";
        }
        return;
    }

    @mkdir("/var/log/strongswan", 0755, true);
    @mkdir("/var/run/strongswan", 0755, true);

    $cmd="$Masterbin start";
    if ($GLOBALS["OUTPUT"]) {
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";
    }
    shell_exec($cmd);




    for ($i=1;$i<5;$i++) {
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";
        }
        sleep(1);
        $pid=PID_NUM();
        if ($unix->process_exists($pid)) {
            break;
        }
    }

    $pid=PID_NUM();
    echo $pid;
    if ($unix->process_exists($pid)) {
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";
        }
        BuildIpTablesServer();
        return true;
    } else {
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";
        }
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";
        }
    }
}

function stop($aspid=false)
{
    $unix=new unix();
    if (!$aspid) {
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if ($unix->process_exists($pid, basename(__FILE__))) {
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if ($GLOBALS["OUTPUT"]) {
                echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";
            }
            return;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();


    if (!$unix->process_exists($pid)) {
        if ($GLOBALS["OUTPUT"]) {
            echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";
        }
        return;
    }
    $pid=PID_NUM();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $kill=$unix->find_program("kill");




    if ($GLOBALS["OUTPUT"]) {
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";
    }
    unix_system_kill($pid);
    for ($i=0;$i<5;$i++) {
        $pid=PID_NUM();
        if (!$unix->process_exists($pid)) {
            break;
        }
        if ($GLOBALS["OUTPUT"]) {
            echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";
        }
        sleep(1);
    }

    $pid=PID_NUM();
    if (!$unix->process_exists($pid)) {
        if ($GLOBALS["OUTPUT"]) {
            echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";
        }
        return;
    }

    if ($GLOBALS["OUTPUT"]) {
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";
    }
    unix_system_kill_force($pid);
    for ($i=0;$i<5;$i++) {
        $pid=PID_NUM();
        if (!$unix->process_exists($pid)) {
            break;
        }
        if ($GLOBALS["OUTPUT"]) {
            echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";
        }
        sleep(1);
    }

    if ($unix->process_exists($pid)) {
        if ($GLOBALS["OUTPUT"]) {
            echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";
        }
        return;
    }
    iptables_delete_rules();
    return true;
}


function PID_NUM()
{
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/charon.pid");
    if ($unix->process_exists($pid)) {
        return $pid;
    }
    $Masterbin=$unix->find_program("charon");
    return $unix->PIDOF($Masterbin);
}

function build_progress_str($pourc, $text)
{
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/strongswan.install.php";
    if ($argv[1]=="--reconfigure") {
        $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/strongswan.build.progress";
    }
    echo "[{$pourc}%] $text\n";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["CACHEFILE"], 0755);
    if ($GLOBALS["OUTPUT"]) {
        sleep(1);
    }
}

function build_cert($name, $cn, $id)
{
    $unix=new unix();
    $ipsec=$unix->find_program("ipsec");
    
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $ca_key = "ca-".$name."-key";
    $ca_cert = "ca-".$name."-cert";
    $server_key = "server-".$name."-key";
    $server_cert = "server-".$name."-cert";
    build_progress("{rebuild}:{certificate}", 15);
    shell_exec("$ipsec pki --gen --type rsa --size 4096 --outform pem > /etc/ipsec.d/private/{$ca_key}.pem");
    if (!file_exists("/etc/ipsec.d/private/{$ca_key}.pem")) {
        build_progress("{failed} to create CA key", 110);
        return false;
    }
    build_progress("{rebuild}:{certificate}", 30);
    shell_exec("$ipsec pki --self --ca --lifetime 3650 --in /etc/ipsec.d/private/{$ca_key}.pem --type rsa --dn \"CN={$cn}\" --outform pem > /etc/ipsec.d/cacerts/{$ca_cert}.pem");
    if (!file_exists("/etc/ipsec.d/cacerts/{$ca_cert}.pem")) {
        build_progress("{failed} to create CA Cert", 110);
        return false;
    }
    build_progress("{rebuild}:{certificate}", 45);
    shell_exec("$ipsec pki --gen --type rsa --size 4096 --outform pem > /etc/ipsec.d/private/{$server_key}.pem");
    if (!file_exists("/etc/ipsec.d/private/{$server_key}.pem")) {
        build_progress("{failed} to create Server Key", 110);
        return false;
    }
    build_progress("{rebuild}:{certificate}", 60);
    shell_exec("$ipsec pki --pub --in /etc/ipsec.d/private/{$server_key}.pem --type rsa | ipsec pki --issue --lifetime 1825 --cacert /etc/ipsec.d/cacerts/{$ca_cert}.pem --cakey /etc/ipsec.d/private/{$ca_key}.pem --dn \"CN={$cn}\" --san \"{$cn}\" --san \"@{$cn}\" --flag serverAuth --flag ikeIntermediate --outform pem >  /etc/ipsec.d/certs/{$server_cert}.pem");
    if (!file_exists("/etc/ipsec.d/certs/{$server_cert}.pem")) {
        build_progress("{failed} to create Server Cert", 110);
        return false;
    }
    if (intval($id)==0) {
        $ca_cert_content = file_get_contents("/etc/ipsec.d/cacerts/{$ca_cert}.pem");
        $ca_cert_content_base64=base64_encode(serialize($ca_cert_content));
        $sql="INSERT INTO strongswan_certs (`name`,`cn`,`ca_key`,`ca_cert`,`ca_cert_content`,`server_key`,`server_cert`) VALUES ('{$name}','{$cn}','{$ca_key}','{$ca_cert}','{$ca_cert_content_base64}','{$server_key}','{$server_cert}');";
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            build_progress("{failed} $q->mysql_error", 110);
            return false;
        }
        shell_exec("$ipsec rereadall");
        sleep(2);
        
        build_progress("{rebuild}:{certificate} {done}", 100);
    }

    if (intval($id)>0) {
        $ca_cert_content = file_get_contents("/etc/ipsec.d/cacerts/{$ca_cert}.pem");
        $ca_cert_content_base64=base64_encode(serialize($ca_cert_content));
        $sql="UPDATE strongswan_certs SET `name`='$name', `cn`='$cn', `ca_key`='$ca_key',`ca_cert`='$ca_cert',`ca_cert_content`='$ca_cert_content_base64',`server_key`='$server_key',`server_cert`='$server_cert' WHERE ID='$id'";

        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            build_progress("{failed} $q->mysql_error", 110);
            return false;
        }
        shell_exec("$ipsec rereadall");
        sleep(2);
        
        build_progress("{rebuild}:{certificate} {done}", 100);
    }
}

function cert_file_remove($name)
{
    $ca_key = "ca-".$name."-key";
    $ca_cert = "ca-".$name."-cert";
    $server_key = "server-".$name."-key";
    $server_cert = "server-".$name."-cert";
    @unlink("/etc/ipsec.d/private/{$ca_key}.pem");
    @unlink("/etc/ipsec.d/cacerts/{$ca_cert}.pem");
    @unlink("/etc/ipsec.d/private/{$server_key}.pem");
    @unlink("/etc/ipsec.d/certs/{$server_cert}.pem");
}

function build_tunnels($force=true)
{
    if(!is_dir("/etc/ipsec-tunnels")) {
        @mkdir("/etc/ipsec-tunnels", 0755, true);
    }
    build_progress("{rebuild}:{tunnels}", 15);
    $unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $f=array();

    $strongSwanCachecrls=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanCachecrls");
    $strongSwanCharondebug=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanCharondebug");
    $strongSwanCharonstart=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanCharonstart"));
    $strongSwanStrictcrlpolicy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanStrictcrlpolicy"));
    $strongSwanUniqueids=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanUniqueids"));

    $strongSwanCachecrlsVal='yes';
    switch ($strongSwanCachecrls) {
        case 0:
            $strongSwanCachecrlsVal='no';
            break;
        case 1:
            $strongSwanCachecrlsVal='yes';
            break;
    }

    $strongSwanCharonstartVal='yes';
    switch ($strongSwanCharonstart) {
        case 0:
            $strongSwanCharonstartVal='no';
            break;
        case 1:
            $strongSwanCharonstartVal='yes';
            break;
    }

    $strongSwanStrictcrlpolicyVal='no';
    switch ($strongSwanStrictcrlpolicy) {
        case 0:
            $strongSwanStrictcrlpolicyVal='no';
            break;
        case 1:
            $strongSwanStrictcrlpolicyVal='yes';
            break;
        case 2:
            $strongSwanStrictcrlpolicyVal='ifuri';
            break;
    }

    $strongSwanUniqueidsVal='no';
    switch ($strongSwanUniqueids) {
        case 0:
            $strongSwanUniqueidsVal='no';
            break;
        case 1:
            $strongSwanUniqueidsVal='yes';
            break;
        case 2:
            $strongSwanUniqueidsVal='never';
            break;
        case 3:
            $strongSwanUniqueidsVal='replace';
            break;
        case 4:
            $strongSwanUniqueidsVal='keep';
            break;
    }

    $f[]="config setup";
    $f[]="  charondebug=$strongSwanCharondebug";
    $f[]="  charonstart=$strongSwanCharonstartVal";
    $f[]="  cachecrls=$strongSwanCachecrlsVal";
    $f[]="  strictcrlpolicy=$strongSwanStrictcrlpolicyVal";
    $f[]="  uniqueids=$strongSwanUniqueidsVal";
    $f[]="";



    $sql="SELECT * FROM strongswan_conns ORDER BY `order` ASC";
    $results=$q->QUERY_SQL($sql);
    $leftfirewall="no";
    foreach ($results as $index=>$ligne) {
        $g=array();
        if ($ligne["enable"]==1) {
            $f[]="include /etc/ipsec-tunnels/{$ligne["ID"]}.conf";
            $g[]="conn {$ligne["conn_name"]}";
            $PARAMS_SERIALIZED=unserialize(base64_decode("{$ligne['params']}"));
            foreach ($PARAMS_SERIALIZED as $key) {
                if ($key['name']!=='conn') {
                    if ($key['type']=='text' || $key['type']=='number') {
                        $g[]="  {$key["name"]} = {$key["value"]}";
                    }
                    if ($key['type']=='select') {
                        $_values= getVal($key["values"]);
                        //echo $_values;
                        $g[]="  {$key["name"]} = {$_values}";
                        if ($key["name"]=="leftfirewall") {
                            $leftfirewall=$_values;
                        }
                    }
                }
            };
            //$g[]="  leftupdown=/etc/strongswan.d/updown.sh";
            if ($leftfirewall=="no") {
                $g[]="  leftupdown=/usr/share/artica-postfix/bin/_updown iptables";
            }
            @file_put_contents("/etc/ipsec-tunnels/{$ligne["ID"]}.conf", @implode("\n", $g));
            build_auth($ligne["ID"]);
        }
        if ($ligne["enable"]==0) {
            $g[]=null;
            @file_put_contents("/etc/ipsec-tunnels/{$ligne["ID"]}.conf", @implode($g));
            build_auth($ligne["ID"]);
        }
    }
    @file_put_contents("/etc/ipsec.conf", @implode("\n", $f));
    update_secrets();
    $ipsec=$unix->find_program("ipsec");
    shell_exec("$ipsec update");
    if($force==true){
        build_progress("{rebuild}:{restart_service}", 90);
        shell_exec("$ipsec restart");
    }
    build_progress("{rebuild}:{done}", 100);

}

function auth_file_remove($fid)
{
    @unlink("/etc/ipsec-tunnels/{$fid}.secrets");
}

function conf_file_remove($fid)
{
    @unlink("/etc/ipsec-tunnels/{$fid}.conf");
}

function build_auth($id)
{
    if(!is_dir("/etc/ipsec-tunnels")) {
        @mkdir("/etc/ipsec-tunnels", 0755, true);
    }
    $unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $f=array();
    $g=array();
    $sql="SELECT * FROM strongswan_auth WHERE `conn_id`='$id' ORDER BY `order` ASC";
    $results=$q->QUERY_SQL($sql);
    build_progress("{rebuild}:{authentication}", 15);
    foreach ($results as $index=>$ligne) {
        if ($ligne["enable"]==1) {
            switch (intval($ligne["type"])) {
                case 1:
                    $type='PSK';
                    break;
                case 2:
                    $type='PSK';
                    break;
                case 3:
                    $type='RSA';;
                    break;
                case 4:
                    $type='XAUTH';
                    break;
                case 5:
                    $type='EAP';
                    break;
                case 6:
                    $type='NTLM';
                    break;
                case 7:
                    $type='PIN';
                    break;
                case 8:
                    $type='P12';
                    break;
                case 9:
                    $type='BLISS';
                    break;
                case 10:
                    $type='ECDSA';
                    break;
            }
            if (intval($ligne["type"])==1) {
                $sqlconn="SELECT * FROM strongswan_conns where ID={$ligne["conn_id"]}";
                $resultsconn=$q->QUERY_SQL($sqlconn);

                foreach ($resultsconn as $index=>$params) {
                    $PARAMS_SERIALIZED=unserialize(base64_decode("{$params['params']}"));
                    foreach ($PARAMS_SERIALIZED as $key) {
                        if ($key['name']=='right') {
                            $right = $key["value"];
                        }
                        elseif ($key['name']=='left') {
                            $left = $key["value"];
                        }
                    };
                    $pwd=urldecode($ligne["secret"]);
                    $f[]="{$left} {$right} : {$type} \"{$pwd}\"";
                }
            }
            if (intval($ligne["type"])==3 || intval($ligne["type"])==9 || intval($ligne["type"])==10) {
                $f[]=": {$type} {$ligne["cert"]}";
            }
            if (intval($ligne["type"])==2 || intval($ligne["type"])==4 || intval($ligne["type"])==5 ) {
                $pwd=urldecode($ligne["secret"]);
                $f[] = "{$ligne["selector"]} : {$type} \"{$pwd}\"";
            }

            @file_put_contents("/etc/ipsec-tunnels/{$ligne["conn_id"]}.secrets", @implode("\n", $f));
        }
        if ($ligne["enable"]==0) {
            $f[]=null;
            @file_put_contents("/etc/ipsec-tunnels/{$ligne["conn_id"]}.secrets", @implode($f));
        }
    }

}

function update_secrets(){
    $unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    $g=array();
    $sql1="SELECT DISTINCT conn_id,enable FROM strongswan_auth WHERE `enable`='1' ORDER BY `order` ASC";
    $results1=$q->QUERY_SQL($sql1);

    foreach ($results1 as $index=>$ligne) {
        if ($ligne["enable"]==1) {
            echo "BUILD AUTH FOR {$ligne["conn_id"]}";
            $g[]="include /etc/ipsec-tunnels/{$ligne["conn_id"]}.secrets";
        }
    }
    @file_put_contents("/etc/ipsec.secrets", @implode("\n", $g));
    $ipsec=$unix->find_program("ipsec");
    shell_exec("$ipsec rereadsecrets");
    //sleep(2);
    //restart();
    build_progress("{rebuild}:{done}", 100);
}


function getVal($arr)
{
    foreach ($arr as $key) {
        if (array_key_exists('selected', $key)) {
            if ($key['selected'] == true) {
                return $key['value'];
            }
        }
    }
}

function build_progress($text, $pourc)
{
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/strongswan.build.progress";
    echo "[{$pourc}%] $text\n";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["CACHEFILE"], 0755);
    if ($GLOBALS["OUTPUT"]) {
        sleep(1);
    }
}

function BuildIpTablesServer()
{
    if ($GLOBALS["WAIT"]) {
        sleep(5);
    }
    iptables_delete_rules();

    $unix=new unix();
    $iptables=$unix->find_program("iptables");
    if (!is_file($iptables)) {
        echo "Starting......: ".date("H:i:s")." StrongSwan iptables, no such binary\n";
        return false;
    }
    // if ($GLOBALS["VERBOSE"]) {
    //     echo "Starting......: ".date("H:i:s")." StrongSwan: hook the $IPTABLES_ETH nic\n";
    // }
    $StrongswanListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StrongswanListenInterface"));
    $iptables_interface_in= "-i $StrongswanListenInterface";
    $iptables_interface_out= "-o $StrongswanListenInterface";
    if(empty($StrongswanListenInterface)){
        $iptables_interface_in="";
        $iptables_interface_out="";
    }
    shell_exec2("$iptables -A INPUT $iptables_interface_in -p udp -m udp --sport 500 --dport 500 -j ACCEPT -m comment --comment \"ArticaStrongswanVPN\"");
    shell_exec2("$iptables -A INPUT $iptables_interface_in -p udp -m udp --sport 4500 --dport 4500 -j ACCEPT -m comment --comment \"ArticaStrongswanVPN\"");
    shell_exec2("$iptables -A INPUT -p udp --dport 1701 -j ACCEPT -m comment --comment \"ArticaStrongswanVPN\"");
    shell_exec2("$iptables -A INPUT $iptables_interface_in -p esp -j ACCEPT -m comment --comment \"ArticaStrongswanVPN\"");
    shell_exec2("$iptables -A INPUT $iptables_interface_in -p ah -j ACCEPT -m comment --comment \"ArticaStrongswanVPN\"");
    shell_exec2("$iptables -t nat -A POSTROUTING $iptables_interface_out -j MASQUERADE -m comment --comment \"ArticaStrongswanVPN\"");
    //shell_exec2("$iptables -A OUTPUT -o eth0 -p esp -j ACCEPT -m comment --comment \"ArticaStrongswanVPN\"");
    //shell_exec2("$iptables -A OUTPUT -o eth0 -p ah -j ACCEPT -m comment --comment \"ArticaStrongswanVPN\"");
    //shell_exec2("$iptables -A OUTPUT -o eth0 -p udp -m udp --sport 500 --dport 500 -j ACCEPT -m comment --comment \"ArticaStrongswanVPN\"");
    //shell_exec2("$iptables -A OUTPUT -o eth0 -p udp -m udp --sport 4500 --dport 4500 -j ACCEPT -m comment --comment \"ArticaStrongswanVPN\"");

    // shell_exec2("$iptables -A INPUT -i tun0 -j ACCEPT -m comment --comment \"ArticaStrongswanVPN\"");
    // shell_exec2("$iptables -A FORWARD -i tun0 -j ACCEPT -m comment --comment \"ArticaStrongswanVPN\"");
    // shell_exec2("$iptables -A OUTPUT -o tun0 -j ACCEPT -m comment --comment \"ArticaStrongswanVPN\"");
    // shell_exec2("$iptables -t nat -A POSTROUTING -o $IPTABLES_ETH -j MASQUERADE -m comment --comment \"ArticaStrongswanVPN\"");

    // shell_exec2("$iptables -A INPUT -i $IPTABLES_ETH -j ACCEPT -m comment --comment \"ArticaStrongswanVPN\"");
    // shell_exec2("$iptables -A FORWARD -i $IPTABLES_ETH -j ACCEPT -m comment --comment \"ArticaStrongswanVPN\"");
    // shell_exec2("$iptables -A OUTPUT -o $IPTABLES_ETH -j ACCEPT -m comment --comment \"ArticaStrongswanVPN\"");
    // shell_exec2("$iptables -t nat -A POSTROUTING -o tun0 -j MASQUERADE -m comment --comment \"ArticaStrongswanVPN\"");
    echo "Starting......: ".date("H:i:s")." StrongSwan prerouting success from $StrongswanListenInterface...\n";
}

function shell_exec2($cmd)
{
    if ($GLOBALS["VERBOSE"]) {
        echo "Starting......: ".date("H:i:s")." strongSwan: executing \"$cmd\"\n";
    }
    shell_exec($cmd);
}

function iptables_delete_rules()
{
    $unix=new unix();
    $iptables_save=$unix->find_program("iptables-save");
    $iptables_restore=$unix->find_program("iptables-restore");
    shell_exec("$iptables_save > /etc/artica-postfix/iptables.conf");
    $data=file_get_contents("/etc/artica-postfix/iptables.conf");
    $datas=explode("\n", $data);
    $pattern="#.+?ArticaStrongswanVPN#";
    $count=0;
    foreach ($datas as $num=>$ligne) {
        if ($ligne==null) {
            continue;
        }
        if (preg_match($pattern, $ligne)) {
            $count++;
            continue;
        }
        $conf=$conf . $ligne."\n";
    }

    file_put_contents("/etc/artica-postfix/iptables.new.conf", $conf);
    shell_exec("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
    echo "Starting......: ".date("H:i:s")." strongSwan cleaning iptables $count rules\n";
}

function LoadArgvs()
{
    $unix=new unix();
    $strongswan=$unix->find_program("ipsec");
    exec("$strongswan --help 2>&1", $results);
    foreach ($results as $index=>$line) {
        if (preg_match("#^\-\-(.+?)[\s\:]+#", $line, $re)) {
            $GLOBALS["STRONGSWANPARAMS"][$re[1]]=1;
        }
    }

    if ($GLOBALS["VERBOSE"]) {
        print_r($GLOBALS["STRONGSWANPARAMS"]);
    }
}
