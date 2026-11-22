#!/usr/bin/php
<?php
// stamp 01/11/2026
$GLOBALS["WIZARD"] = false;
if (preg_match("#--verbose#", implode(" ", $argv))) {
    $GLOBALS["VERBOSE"] = true;
    $GLOBALS["VERBOSE"] = true;
    $GLOBALS["debug"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.tcpip-parser.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');

$GLOBALS["FIRST_TIME"] = false;
$GLOBALS["NO_GLOBAL_RELOAD"] = false;
$GLOBALS["AFTER_REBUILD"] = false;
$GLOBALS["AS_ROOT"] = true;
$GLOBALS["SLEEP"] = false;
$GLOBALS["RESTART"] = false;
$GLOBALS["RESTART_DNS"] = false;
$GLOBALS["ONLYROUTES"] = false;
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir");

if (function_exists("posix_getuid")) {
    if (posix_getuid() <> 0) {
        die("Cannot be used in web server mode\n\n");
    }
}
if (preg_match("#--routes-build#", implode(" ", $argv))) {
    $GLOBALS["ONLYROUTES"] = true;
}
if (preg_match("#--wizard#", implode(" ", $argv))) {
    $GLOBALS["WIZARD"] = true;
}
if (preg_match("#--sleep#", implode(" ", $argv))) {
    $GLOBALS["SLEEP"] = true;
}
if (preg_match("#--restart#", implode(" ", $argv))) {
    $GLOBALS["RESTART"] = true;
}
if (preg_match("#--afterrebuild#", implode(" ", $argv))) {
    $GLOBALS["AFTER_REBUILD"] = true;
}
if (preg_match("#--first-time#", implode(" ", $argv))) {
    $GLOBALS["FIRST_TIME"] = true;
    $GLOBALS["VERBOSE"] = true;
    $GLOBALS["VERBOSE"] = true;
    $GLOBALS["debug"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}


$DisableNetworking = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));
if ($DisableNetworking == 1) {
    build_progress(110, "Disable Networking is defined, aborting...");
    die("Disable Networking is defined, aborting...");

}


if (isset($argv[1])) {
    // --ucarp-start / --ucarp-stop : seul appelant, le script SysV hérité
    // /etc/init.d/artica-failover (plus aucun générateur ; le daemon le retire,
    // ArticaIfUP.RemoveLegacyFailover). Ces arguments n'ont jamais été traités :
    // ils tombaient dans build() à chaque boot et à chaque arrêt.
    if ($argv[1] == "--ucarp-start" || $argv[1] == "--ucarp-stop") {
        echo "Starting......: " . date("H:i:s") . " {$argv[1]}: legacy artica-failover call, nothing to do\n";
        exit(0);
    }


    if ($argv[1] == "--just-add") {
        exit();
    }
    if ($argv[1] == "--routes-del") {
        routes_del($argv[2]);
        exit;
    }
    if ($argv[1] == "--bridge-delete") {
        bridge_delete($argv[2]);
        exit;
    }
    if ($argv[1] == "--hosts") {
        $GLOBALS["RESTART_DNS"] = true;
        etc_hosts_exec();
        exit;
    }
    if ($argv[1] == "--hosts-defaults") {
        etc_hosts_exec();
        exit;
    }
    if ($argv[1] == "--iproute-progress") {
        apply_routes();
        exit;
    }
    if ($argv[1] == "--wifi") {
        Check_wifi_cards();
        exit;
    }
    if ($argv[1] == "--routes-build") {
        $GLOBALS["ONLYROUTES"] = true;
    }
    if ($argv[1] == "--reset-nic") {
        resetNic($argv[2], $argv[3]);
        exit;
    }
}

if ($GLOBALS["ONLYROUTES"]) {
    build();
    build_progress(98, "{apply_routes_to_the_system}");
    @chmod("/usr/sbin/build-routes.sh", 0755);
    shell_exec("/usr/sbin/build-routes.sh");
    build_progress(100, "{apply_routes_to_the_system} {done}");
    die();
}

if ($GLOBALS["SLEEP"]) {
    sleep(2);
}

build();


if (!$GLOBALS["RESTART"]) {
    build_progress(100, "{success}");
}

build_progress(95, "{running} /etc/init.d/artica-ifup {please_wait}");
//system("/usr/sbin/artica-phpfpm-service -restart-network");
build_progress(100, "{checking} {done}");

if (is_file("/etc/init.d/ssh")) {
    $unix = new unix();
    $php = $unix->LOCATE_PHP5_BIN();
    shell_exec("/usr/sbin/artica-phpfpm-service -configure-ssh");
}


function resetNic($eth, $ippref)
{
    $unix = new unix();
    $ip = $unix->find_program("ip");
    writelogs("PERFORMING $ip addr del $ippref dev $eth", __FUNCTION__, __FILE__, __LINE__);
    exec("$ip link set $eth down 2>&1", $results);
    writelogs("RESULT FROM DOWN is " . print_r($results, TRUE), __FUNCTION__, __FILE__, __LINE__);
    exec("$ip addr del $ippref dev $eth 2>&1", $results);
    writelogs("RESULT FROM DEL is " . print_r($results, TRUE), __FUNCTION__, __FILE__, __LINE__);
}







function VirtualsIPSyslog($text)
{
    if (!function_exists("syslog")) {
        return;
    }
    $LOG_SEV = LOG_ERR;
    openlog("artica-ifup", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
}
function wizard_progress($perc, $text)
{
    if (!$GLOBALS["WIZARD"]) {
        return;
    }
    $PROGRESS_FILE = PROGRESS_DIR . "/wizard.progress";
    $array["POURC"] = 60;
    $array["TEXT"] = "$perc) $text";
    if (!is_dir('/usr/share/artica-postfix/ressources/logs/web')) {
        @mkdir('/usr/share/artica-postfix/ressources/logs/web', 0755, true);
    }
    @file_put_contents($PROGRESS_FILE, serialize($array));
    @chmod($PROGRESS_FILE, 0755);
    $unix = new unix();
    $sourcefunction="";
    $sourceline="";
    $sourcefile="";
    if (function_exists("debug_backtrace")) {
        $trace = debug_backtrace();
        if (isset($trace[1])) {
            $sourcefile = basename($trace[1]["file"]);
            $sourcefunction = $trace[1]["function"];
            $sourceline = $trace[1]["line"];
        }

    }


    $unix->events("$perc} $text", "/var/log/artica-wizard.log", $sourcefunction, $sourceline, $sourcefile);

}
function build_progress_watchdog($text, $pourc){
    wizard_progress($pourc, $text);
    if($pourc<80) {
        build_progress($pourc, $text);
    }
    $echotext = $text;
    echo "Starting......: " . date("H:i:s") . " $pourc% $echotext\n";
    $cachefile = PROGRESS_DIR . "/exec.interfaces-watchdog.progress";
    $array["POURC"] = $pourc;
    $array["TEXT"] = $text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile, 0755);
}
function build_progress($pourc, $text){
    wizard_progress($pourc, $text);
    $echotext = $text;
    echo "Starting......: " . date("H:i:s") . " $pourc% $echotext\n";
    $cachefile = PROGRESS_DIR . "/reconfigure-newtork.progress";
    $array["POURC"] = $pourc;
    $array["TEXT"] = $text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile, 0755);
}
function etc_hosts_exec(){
    shell_exec("/usr/sbin/artica-phpfpm-service -hosts");
}
function hostclean($hostname)
{

    $hostname = str_replace([':', '|', '+', '²', '^', '°', '\\', '/', '*', ' ', '(', ')', '{', '}', '[', ']', '%', '$', ';', ',', '?', '=', '#', '&', '`', '"'], '', $hostname);
    return trim(strtolower($hostname));
}

function vlan_delete($ID)
{
    $sql = "SELECT * FROM nics_vlan WHERE ID='$ID'";
    $q = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    if (!is_numeric($ID)) {
        return;
    }
    if ($ID < 1) {
        return;
    }
    $unix = new unix();
    if (!isset($GLOBALS["moprobebin"])) {
        $GLOBALS["moprobebin"] = $unix->find_program("modprobe");
    }
    if (!isset($GLOBALS["vconfigbin"])) {
        $GLOBALS["vconfigbin"] = $unix->find_program("vconfig");
    }
    if (!isset($GLOBALS["ifconfig"])) {
        $GLOBALS["ifconfig"] = $unix->find_program("ifconfig");
    }
    if (!isset($GLOBALS["ethtoolbin"])) {
        $GLOBALS["ethtoolbin"] = $unix->find_program("ethtool");
    }
    if (!isset($GLOBALS["ipbin"])) {
        $GLOBALS["ipbin"] = $unix->find_program("ip");
    }
    $ligne = $q->mysqli_fetch_array($sql);
    $ID=$ligne["ID"];
    $InterfaceName = "vlan$ID";
    shell_exec("{$GLOBALS["ifconfig"]} $InterfaceName down");
    shell_exec("{$GLOBALS["vconfigbin"]} rem $InterfaceName");
    $q->QUERY_SQL("DELETE FROM nics_vlan WHERE ID='$ID'");

}




function events($text, $function = null, $line = null)
{
    $unix = new unix();

    if (function_exists("debug_backtrace")) {
        $trace = debug_backtrace();
        if (isset($trace[1])) {
            if ($function == null) {
                $function = $trace[1]["function"];
            }
            if ($line == null) {
                $line = $trace[1]["line"];
            }
        }

    }


    $unix->events($text, "/var/log/artica-network.log", false, $function, $line);


}
function event($text, $function = null, $line = null)
{
    events($text, $function, $line);
}

function build(){
    // /etc/init.d/artica-ifup n'est plus écrit ici. Son seul propriétaire est le
    // daemon (ArticaIfUP.Install, unité systemd native + script SysV homonyme).
    // Ce build() réécrivait un script v1 (Default-Start: S) puis relançait
    // update-rc.d remove/defaults : les liens rc ne couvraient plus les niveaux
    // 3 4 5 du script Go, `systemctl enable artica-ifup` échouait
    // (« no runlevel symlinks to modify ») avant de poser le lien
    // multi-user.target.wants, et l'unité ne démarrait plus au boot : aucune
    // adresse IPv4 avant le repli de ChecksRoutes (30 s à 2 min).
    // Le script v1 n'était de toute façon jamais exécuté : l'unité native le masque.
    $unix = new unix();
    $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . ".pid";
    $pid = @file_get_contents($pidfile);

    $GLOBALS["ipbin"] = $unix->find_program("ip");

    if ($GLOBALS["FIRST_TIME"]) {
        if (is_file("/etc/artica-postfix/settings/Daemons/NetworksConfiguredFirstTime")) {
            $GLOBALS["FIRST_TIME"] = false;
        }
    }
    if ($unix->process_exists($pid, basename(__FILE__))) {
        echo "Starting......: " . date("H:i:s") . " Building networks already executed PID: $pid\n";
        echo "*******************************************************************************\n";
        echo @file_get_contents("/proc/$pid/cmdline") . "\n";
        echo "*******************************************************************************\n";
    }
    $cacheFile = "/etc/artica-postfix/Interfaces.gob";
    $BondCachefile = "/etc/artica-postfix/bonds.gob";

    @unlink($cacheFile);
    @unlink($BondCachefile);
    @file_put_contents($pidfile, getmypid());
    build_progress(40, "{checking} bridges");
    $prc=40;
    $prc++;build_progress($prc,"{building} ".__LINE__);
    system("/usr/sbin/artica-phpfpm-service -build-dhcp-interfaces");
    $prc++;build_progress($prc,"{building} ".__LINE__);
    system("/usr/sbin/artica-phpfpm-service -optimize-os");
    build_progress(76, "{checking} WIFI...");
    Check_wifi_cards();
    shell_exec("/usr/sbin/artica-phpfpm-service -network-systemd");

    if (!is_file("/etc/artica-postfix/settings/Daemons/NetworksConfiguredFirstTime")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NetworksConfiguredFirstTime", time());
    }
    if (is_file('/usr/sbin/update-rc.d')) {
        if (is_file('/etc/init.d/networking')) {
            shell_exec("/usr/sbin/update-rc.d -f networking disable  >/dev/null 2>&1");
            @copy("/etc/init.d/networking", "/etc/init.d/networking.back");
            @unlink("/etc/init.d/networking");
        }
    }

    if (is_file("/bin/iptables-iproute2.sh")) {
        @unlink("/bin/iptables-iproute2.sh");
    }

    $inter[] = "# This file describes the network interfaces available on your system";
    $inter[] = "## and how to activate them. For more information, see interfaces(5).";
    $inter[] = "";
    $inter[] = "## The loopback network interface";
    $inter[] = "auto lo";
    $inter[] = "iface lo inet loopback";
    $inter[] = "";
    $inter[] = "";
    if (is_file("/etc/network/interfaces")) {
        @file_put_contents("/etc/network/interfaces", @implode("\n", $inter));
    }
    squid_admin_mysql(2, "Network configuration was successfully refreshed", null, __FILE__, __LINE__);
    echo "Starting......: " . date("H:i:s") . " done...\n";
    build_progress(100,"{configuring} {done}");
}



function ifconfig_tests()
{
    $unix = new unix();
    $array = array();
    $cmd = $unix->find_program("ifconfig") . " -s";
    exec($cmd, $results);
    foreach ($results as $index => $line) {
        if (preg_match("#^(.+?)\s+[0-9]+#", $line, $re)) {
            $array[trim($re[1])] = trim($re[1]);
        }
    }
    print_r($array);

}








function ifconfig_parse($path = null)
{


}

function iproute_progress($pourc, $text)
{
    wizard_progress($pourc, $text);
    $echotext = $text;
    echo "Starting......: " . date("H:i:s") . " $pourc% $echotext\n";
    $cachefile = "/usr/share/artica-postfix/ressources/logs/routes.check.progress";
    $array["POURC"] = $pourc;
    $array["TEXT"] = $text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile, 0755);
}






function routes_fromfile()
{

    if (!is_file("/etc/artica-postfix/ROUTES.CACHES.TABLES")) {
        echo "Starting......: " . date("H:i:s") . " Building routes, no cache file\n";
        return;
    }
    $array=array();
    $unix = new unix();
    $ip = $unix->find_program("ip");

    $f = explode("\n", @file_get_contents("/etc/iproute2/rt_tables"));
    foreach ($f as $ligne){
        if (preg_match("#^([0-9]+)\s+(.+)#", $ligne, $re)) {
            $tableID = $re[1];
            if ($tableID == 255) {
                continue;
            }
            if ($tableID == 254) {
                continue;
            }
            if ($tableID == 253) {
                continue;
            }
            $array[$tableID] = $re[2];
        }

    }
    foreach ($array as $id => $ligne) {
        shell_exec("$ip route flush table $ligne");
    }


    $array = unserialize("/etc/artica-postfix/ROUTES.CACHES.TABLES");
    $TABLES = $array["TABLES"];
    $NEXT = $array["NEXT"];
    $CMDS = $array["CMDS"];

    foreach ($CMDS as $id=>$cmdline){
        shell_exec($cmdline);
    }


    $f[] = "255\tlocal";
    $f[] = "254\tmain";
    $f[] = "253\tdefault";
    $f[] = "0\tunspec";
    $c = 1;
    if (count($TABLES) > 0) {
        foreach ($TABLES as $id=>$ligne){
            $f[] = "$c\t$ligne";

        }

        file_put_contents("/etc/iproute2/rt_tables", @implode("\n", $f));
        foreach ($NEXT as $id=>$cmdline){
            echo "$cmdline\n";
            shell_exec($cmdline);
        }
    }
}



function routes_main_build(){


    if (count($GLOBALS["SCRIPTS"]) == 0) {
        echo "No route to build\n";
        return;
    }

    foreach ($GLOBALS["SCRIPTS"] as $line) {
        $line = trim($line);
        if ($line == null) {
            continue;
        }
        $md = md5($line);
        if (isset($AL[$md])) {
            continue;
        }
        $AL[$md] = true;
        echo "Starting......: " . date("H:i:s") . " `$line`\n";
        system($line);
    }


}


function Check_wifi_cards():int{

    $EnableIwConfig = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIwConfig"));
    $unix = new unix();
    $Files = $unix->DirFiles("/etc/init.d", "wifi-wlan[0-9]+");
    $ip = $unix->find_program("ip");
    $ifconfig = $unix->find_program("ifconfig");
    $iw = $unix->find_program("iw");
    $prc=76;
    foreach ($Files as $net => $nothing) {
        echo "Scannning $net\n";
        build_progress($prc, "{checking} $net...");
        if (!preg_match("#wifi-(.+)#", $net, $re)) {
            echo "Scannning $net - SKIP\n";
            continue;
        }
        $Interface = $re[1];
        echo "Interface: $Interface\n";
        $nic = new system_nic($Interface);

        if ($EnableIwConfig == 0) {
            echo "EnableIwConfig $Interface ---> 0 !\n";
            $nic->enabled = 0;
        }

        if ($nic->enabled == 1) {
            echo "Scannning $Interface - Enabled OK\n";
            continue;
        }

        squid_admin_mysql(1, "{uninstalling} Wifi interface $Interface");
        remove_service("/etc/init.d/$net");
        if (is_file("/etc/wpa_supplicant/$Interface.conf")) {
            @unlink("/etc/wpa_supplicant/$Interface.conf");
        }

        if (is_file("/etc/monit/conf.d/APP_WPA_SUPPLIANT_$Interface.monitrc")) {
            @unlink("/etc/monit/conf.d/APP_WPA_SUPPLIANT_$Interface.monitrc");
            shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
        }

        shell_exec("$ip link set $Interface down");
        if (is_file($ifconfig)) {
            shell_exec("$ifconfig $Interface down");
        }
        if (is_file($iw)) {
            shell_exec("$iw dev $Interface del");
        }

    }
    return $prc;

}


function apply_routes():bool{

    iproute_progress(30, "{configuring}");

    echo "Building Scripts....\n";

    $sh = array();
    $sh[] = "#!/bin/sh -e";
    $sh[] = "";

    foreach ($GLOBALS["SCRIPTS_ROUTES"] as $line) {
        if (preg_match("#^\##", $line)) {
            $sh[] = $line;
            continue;
        }
        if (strpos($line, "||") > 0) {
            $sh[] = $line;
            continue;
        }
        $sh[] = $line . " || true";
    }


    $sh[] = "";
    @file_put_contents("/tmp/routes-progress.sh", @implode("\n", $sh));
    iproute_progress(100, "{configuring} {done}");
    return true;
}






function MULTIPATH_GATEWAY($eth, $RouteName = null):bool{
    $q = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $sql = "CREATE TABLE IF NOT EXISTS `multipath` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`nic` TEXT NOT NULL,`weight` INTEGER NOT NULL DEFAULT 1, `gateway` TEXT NOT NULL)";
    $q->QUERY_SQL($sql);
    $sql = "SELECT * FROM `multipath` WHERE `nic`='$eth' ORDER BY `weight`";
    $results = $q->QUERY_SQL($sql);
    if (count($results) == 0) {
        return false;
    }
    $ipclass = new IP();
    $ROUTENAMET = null;
    if ($RouteName <> null) {
        $ROUTENAMET = " table $RouteName";
    }

    $GATEWAYS = array();

    foreach ($results as $index => $ligne) {
        $ligne["gateway"] = trim($ligne["gateway"]);
        if ($ligne["gateway"] == "0.0.0.0") {
            continue;
        }
        $weight = intval($ligne["weight"]);
        if ($weight == 0) {
            $weight = 1;
        }
        if (!$ipclass->isIPAddress($ligne["gateway"])) {
            continue;
        }
        $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] Add Multipath gateway {$ligne["gateway"]} for $eth [" . __LINE__ . "]";
        $GATEWAYS[$ligne["gateway"]] = $weight;
    }

    if (count($GATEWAYS) == 0) {
        return false;
    }

    $nic = new system_nic($eth);
    $GATEWAYS[$nic->GATEWAY] = $nic->metric;

    $prefix = "{$GLOBALS["ipbin"]} route add default scope global$ROUTENAMET ";

    foreach ($GATEWAYS as $gateway => $weight) {
        $GLOBALS["SCRIPTS_ROUTES"][] = "# gateway: $gateway weight $weight";
        $GLOBALS["SCRIPTS_ROUTES"][] = "{$GLOBALS["ipbin"]} route add $gateway$ROUTENAMET dev $eth";
        $f[] = "nexthop via $gateway dev $eth  weight $weight";

    }
    $GLOBALS["SCRIPTS_ROUTES"][] = $prefix . " " . @implode(" ", $f);
    return true;

}
function maskToCIDR($mask) {

    $NetMasks["255.255.255.255"]=32;
    $NetMasks["255.255.255.254"]=31;
    $NetMasks["255.255.255.252"]=30;
    $NetMasks["255.255.255.248"]=29;
    $NetMasks["255.255.255.240"]=28;
    $NetMasks["255.255.255.224"]=27;
    $NetMasks["255.255.255.192"]=26;
    $NetMasks["255.255.255.128"]=25;
    $NetMasks["255.255.255.0"]=24;
    $NetMasks["255.255.254.0"]=23;
    $NetMasks["255.255.252.0"]=22;
    $NetMasks["255.255.248.0"]=21;
    $NetMasks["255.255.240.0"]=20;
    $NetMasks["255.255.224.0"]=19;
    $NetMasks["255.255.192.0"]=18;
    $NetMasks["255.255.128.0"]=17;
    $NetMasks["255.255.0.0"]=16;
    $NetMasks["255.254.0.0"]=15;
    $NetMasks["255.252.0.0"]=14;
    $NetMasks["255.248.0.0"]=13;
    $NetMasks["255.240.0.0"]=12;
    $NetMasks["255.224.0.0"]=11;
    $NetMasks["255.192.0.0"]=10;
    $NetMasks["255.128.0.0"]=9;
    $NetMasks["255.0.0.0"]=8;
    $NetMasks["254.0.0.0"]=7;
    $NetMasks["252.0.0.0"]=6;
    $NetMasks["248.0.0.0"]=5;
    $NetMasks["240.0.0.0"]=4;
    $NetMasks["224.0.0.0"]=3;
    $NetMasks["192.0.0.0"]=2;
    $NetMasks["128.0.0.0"]=1;
    $NetMasks["0.0.0.0"]=0;

    if(isset($NetMasks[$mask])){
        return $NetMasks[$mask];
    }

    if(strpos($mask, ".") == 0) {
        return $mask;
    }

    $octets = explode('.', $mask);

    // Convert each octet to binary and concatenate
    $binaryMask = '';
    foreach ($octets as $octet) {
        $binaryMask .= str_pad(decbin($octet), 8, '0', STR_PAD_LEFT);
    }

    // Count the number of 1's in the binary string
    return substr_count($binaryMask, '1');
}

function routes_table_sources($ruleid, $eth, $RouteName){

    if(preg_match("#^veth[0-9]+#",$eth)){
        return;
    }
    $unix = new unix();
    $MAINNET="";
    $q = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $ipBin=$GLOBALS["ipbin"];
    $EnableIwConfig = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIwConfig"));
    $NetworkAdvancedRouting = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetworkAdvancedRouting"));
    $NetworkAdvancedRoutingHErmetic = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetworkAdvancedRoutingHErmetic"));
    if ($NetworkAdvancedRouting == 0) {
        $NetworkAdvancedRoutingHErmetic = 0;
    }
    if ($ruleid == 999999) {
        $RouteName = "main";
    }


    if ($EnableIwConfig == 0) {
        if (preg_match("#^(wlan|wlp)[0-9]+#", $eth)) {
            $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "]: $eth Skipped (EnableIwConfig = 0 )";
            return;
        }
    }


    $route = $unix->find_program("route");
    $nicClass = new system_nic($eth);

    $GLOBALS["SCRIPTS_ROUTES"][] = "#";
    $GLOBALS["SCRIPTS_ROUTES"][] = "#";
    $GLOBALS["SCRIPTS_ROUTES"][] = "#";
    $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] ------------------------------------------------------";
    $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] Routing rule $ruleid for Interface [$eth] Route $RouteName";


    if ($nicClass->enabled == 0) {
        if ($ruleid <> 999999) {
            $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] $eth is disabled";
            return;
        }
    }
    if ($nicClass->UseSPAN == 1) {
        $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] $eth is on SPAN MODE";
        return;
    }

    if ($ruleid <> 999999) {
        $TBRZ = explode(".", $nicClass->IPADDR);
        $MAINNET = "$TBRZ[0].$TBRZ[1].$TBRZ[2].0";

        $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] $eth --> IP Address: $nicClass->IPADDR Net:$MAINNET/$nicClass->NETMASK";
        $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] $eth --> Default route \"$nicClass->GATEWAY\" Add in maintable=$nicClass->defaultroute";
        $GLOBALS["SCRIPTS_ROUTES"][] = "#";
        $GLOBALS["SCRIPTS_ROUTES"][] = "#";
        $GLOBALS["SCRIPTS_ROUTES"][] = "while ip rule delete from $nicClass->IPADDR 2>/dev/null; do true; done";
    }

    if ($NetworkAdvancedRoutingHErmetic == 0) {
        if ($nicClass->GATEWAY <> null) {
            if ($nicClass->GATEWAY <> "0.0.0.0") {
                if ($nicClass->defaultroute == 1) {
                    if (!isset($GLOBALS["DEFAULT_METRIC"])) {
                        $GLOBALS["DEFAULT_METRIC"] = 0;
                    }
                    if ($nicClass->metric > $GLOBALS["DEFAULT_METRIC"]) {
                        $GLOBALS["DEFAULT_METRIC"] = $nicClass->metric;
                    }
                    if (intval($nicClass->metric < $GLOBALS["DEFAULT_METRIC"])) {
                        $GLOBALS["DEFAULT_METRIC"]++;
                        $nicClass->metric = $GLOBALS["DEFAULT_METRIC"];
                    }
                    $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] Add the default route defined for $eth metric " . $nicClass->metric;
                    $GLOBALS["SCRIPTS_ROUTES"][] = "$route add -host $nicClass->GATEWAY dev $eth";
                    $GLOBALS["SCRIPTS_ROUTES"][] = "$route add default gw $nicClass->GATEWAY dev $eth metric $nicClass->metric";
                }
                $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] Add the default route in table table $RouteName and defined for $eth metric " . $nicClass->metric;


                if (!MULTIPATH_GATEWAY($eth, $RouteName)) {
                    $GLOBALS["SCRIPTS_ROUTES"][] = "{$GLOBALS["ipbin"]} route add $nicClass->GATEWAY table $RouteName dev $eth";
                    $GLOBALS["SCRIPTS_ROUTES"][] = "{$GLOBALS["ipbin"]} route add default scope global table $RouteName nexthop via $nicClass->GATEWAY dev $eth  weight $nicClass->metric";
                }
                $GLOBALS["SCRIPTS_ROUTES"][] = "#";
            }
        }
    } else {
        if($nicClass->GATEWAY=="0.0.0.0"){
            $nicClass->GATEWAY="";
        }

        if ($nicClass->GATEWAY <> null) {
            $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] Hermetic: Add the default route in table table $RouteName and defined for $eth";
            if (!MULTIPATH_GATEWAY($eth, $RouteName)) {
                $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin route add $nicClass->GATEWAY dev $eth table $RouteName ";
                $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin route add default via $nicClass->GATEWAY dev $eth table $RouteName";
            }
            $GLOBALS["SCRIPTS_ROUTES"][] = "#";


        }

    }

    $scopelink="";
    if($nicClass->IPADDR=="0.0.0.0"){
        $nicClass->IPADDR="";
    }
    if(strlen($nicClass->IPADDR)>3){
        $scopelink="scope link src $nicClass->IPADDR";
    }

    if ($ruleid <> 999999) {
        $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] All packets from $nicClass->IPADDR goes to $RouteName";
        $GLOBALS["SCRIPTS_ROUTES"][] = "#";
        $GLOBALS["SCRIPTS_ROUTES"][] = "#";
        $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] Network Interface can answering itself from $MAINNET/$nicClass->NETMASK";
        if ($NetworkAdvancedRoutingHErmetic == 0) {
            $CdirBit=maskToCIDR($nicClass->NETMASK);
            $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin rule add from $nicClass->IPADDR iif $eth lookup $RouteName prio $nicClass->metric";

            $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin route add $MAINNET/$CdirBit dev $eth proto kernel $scopelink table $RouteName";
        } else {
            $GLOBALS["SCRIPTS_ROUTES"][] = "# Hermetic Interface";
            if(strlen($nicClass->IPADDR)>3) {
                $CdirBit=maskToCIDR($nicClass->NETMASK);
                $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin route add $MAINNET/$CdirBit dev $eth src $nicClass->IPADDR table $RouteName";
            }
            if(strlen($nicClass->IPADDR)>3) {
                $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin rule add from $nicClass->IPADDR table $RouteName";
            }
            $CdirBit=maskToCIDR($nicClass->NETMASK);
            $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin rule add to $MAINNET/$CdirBit table $RouteName";
        }
        $GLOBALS["SCRIPTS_ROUTES"][] = "#";
    }


    if ($NetworkAdvancedRoutingHErmetic == 0) {
        if (!isset($GLOBALS["TABLE_MAIN"]["$MAINNET/$nicClass->NETMASK"])) {
            $CdirBit=maskToCIDR($nicClass->NETMASK);
            $GLOBALS["SCRIPTS_ROUTES"][] = "#";
            $GLOBALS["SCRIPTS_ROUTES"][] = "# 				-------------- TABLE MAIN --------------";
            $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin route add $MAINNET/$CdirBit dev $eth  proto kernel $scopelink table main";
            $GLOBALS["SCRIPTS_ROUTES"][] = "#";
            $GLOBALS["SCRIPTS_ROUTES"][] = "#";
            $GLOBALS["TABLE_MAIN"]["$MAINNET/$nicClass->NETMASK"] = true;
        }
    }

    $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] ------------- Routing rules $ruleid for DESTINATION";
    $results = $q->QUERY_SQL("SELECT * FROM routing_rules_dest WHERE ruleid='$ruleid' AND type=3 ORDER BY zOrder,metric");

    $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] routing_rules_dest ruleid='$ruleid' " . count($results) . " elements";

    if (count($results) > 0) {
        foreach ($results as $index => $ligne) {
            $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] Gateway {$ligne["gateway"]}";
            if ($ligne["gateway"] == $nicClass->GATEWAY) {
                continue;
            }
            $tablecmd = " table $RouteName";
            if ($ruleid == 999999) {
                $eth = $ligne["nic"];
            }

            $GLOBALS["SCRIPTS_ROUTES"][] = "#";
            $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] default $eth -> {$ligne["gateway"]}";
            if ($NetworkAdvancedRoutingHErmetic == 0) {
                $GLOBALS["SCRIPTS_ROUTES"][] = "$route add -host {$ligne["gateway"]} dev $eth";
                $GLOBALS["SCRIPTS_ROUTES"][] = "$route add default gw {$ligne["gateway"]} dev $eth";
            }
            $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin route add default via {$ligne["gateway"]} dev $eth$tablecmd prio {$ligne["metric"]} || true";
        }
    } else {
        if ($nicClass->defaultroute == 0) {
            $GLOBALS["SCRIPTS_ROUTES"][] = "#";
            $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] * * * * * * * * * * * * * * * * * * * * * *";
            $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] WARNING: TRY TO FIX IT --------------------";
            $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] Gateway $nicClass->GATEWAY is not a default route and no routes are defined";
            if ($nicClass->GATEWAY <> null) {
                if ($nicClass->GATEWAY <> "0.0.0.0") {
                    $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] Default gateway for $eth -> $nicClass->GATEWAY";
                    if ($NetworkAdvancedRoutingHErmetic == 0) {
                        $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin route add $nicClass->GATEWAY dev $eth table main";
                        $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin route add default via $nicClass->GATEWAY dev $eth table main prio $nicClass->metric || true";
                    }
                    $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin route add default via $nicClass->GATEWAY dev $eth table $RouteName prio $nicClass->metric || true";
                }

            }
            $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] * * * * * * * * * * * * * * * * * * * * * *";
            $GLOBALS["SCRIPTS_ROUTES"][] = "#";
        }


    }


    $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] routing_rules_src... Rule number $ruleid";
    $results = $q->QUERY_SQL("SELECT * FROM routing_rules_src WHERE ruleid='$ruleid' ORDER BY zOrder");

    foreach ($results as $index => $ligne) {
        $pattern = $ligne["pattern"];
        $gateway = $ligne["gateway"];
        $priority = $ligne["metric"];
        $priority_cmd = null;
        $tablecmd = " table $RouteName";

        if (isset($GLOBALS["routing_rules_src"][$ligne["ID"]])) {
            $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] routing_rules_src gateway:$gateway from: $pattern {$ligne["ID"]} Skipped, already added";
            continue;
        }

        if ($priority > 0) {
            $priority_cmd = " priority $priority";
        }
        $type = $ligne["type"];

        if ($type == 3) {
            $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin rule add nat $gateway from $pattern iif $eth $tablecmd$priority_cmd || true";
            continue;
        }
        if ($type == 4) {
            $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin rule add type blackhole from $pattern iif $eth$tablecmd$priority_cmd || true";
            continue;
        }

        if ($type == 5) {
            $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin rule add type prohibit from $pattern iif $eth $tablecmd$priority_cmd || true";
            continue;
        }

        if ($gateway == null) {
            $GLOBALS["SCRIPTS_ROUTES"][] = "#";
            $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] routing_rules_src All packets from $pattern go to $eth table $RouteName";
            $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin rule add from $pattern iif $eth lookup $RouteName$priority_cmd || true";
        }


    }


    $results = $q->QUERY_SQL("SELECT * FROM routing_rules_dest WHERE ruleid='$ruleid' ORDER BY zOrder,metric");

    $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] routing_rules_dest rule Number [$ruleid]...";
    foreach ($results as $index => $ligne) {
        $pattern = trim($ligne["pattern"]);
        $outiface=$ligne["outiface"];
        $priority = $ligne["metric"];
        $priority_cmd = null;
        $type = $ligne["type"];
        $scrcmd = " src $nicClass->IPADDR ";
        $proto = " proto kernel scope link ";
        $iprtadd = "$ipBin route add";
        $iprladd = "$ipBin rule add";
        $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] pattern=$pattern, priority=$priority, type=$type";
        if ($pattern == "0.0.0.0/0") {
            $pattern = "0.0.0.0/0.0.0.0";
        }
        if ($pattern == "*") {
            $pattern = "0.0.0.0/0.0.0.0";
        }
        if ($pattern == "0.0.0.0/0.0.0.0") {
            $pattern = "default";
        }

        if ($ruleid == 999999) {
            $eth = $ligne["nic"];
            $scrcmd = null;
            $proto = null;
        }

        if ($pattern == "0.0.0.0/0.0.0.0") {
            if ($ruleid <> 999999) {
                $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] $pattern refused here, use default gateway instead.";
                continue;
            }
        }
        if (isset($GLOBALS["routing_rules_dest"][$ligne["ID"]])) {
            $GLOBALS["SCRIPTS_ROUTES"][] = "# {$ligne["ID"]} already defined in memory";
            continue;
        }

        if ($priority > 0) {
            $priority_cmd = " priority $priority";
        }

        if ($type < 3) {
            if ($ligne["gateway"] <> null) {
                $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] $pattern via {$ligne["gateway"]}";
                if ($ruleid == 999999) {
                    unset($GLOBALS["ALREADYADDED"][$pattern]);
                    $GLOBALS["SCRIPTS_ROUTES"][] = "$iprtadd {$ligne["gateway"]} dev $eth table $RouteName$scrcmd$priority_cmd";
                }

                if (!isset($GLOBALS["ALREADYADDED"][$pattern])) {
                    if ($pattern <> "default") {
                        $GLOBALS["SCRIPTS_ROUTES"][] = "$iprtadd $pattern dev $eth$proto$scrcmd";
                    }
                    $GLOBALS["ALREADYADDED"][$pattern] = true;
                }

                $GLOBALS["SCRIPTS_ROUTES"][] = "$iprtadd $pattern via {$ligne["gateway"]} dev $eth table $RouteName$scrcmd$priority_cmd";
                if ($ruleid <> 999999) {
                    if ($pattern <> "default") {
                        $GLOBALS["SCRIPTS_ROUTES"][] = "$iprladd to $pattern iif $eth lookup $RouteName$priority_cmd";
                    }
                }
                continue;
            }
            $GLOBALS["SCRIPTS_ROUTES"][] = "# [" . __LINE__ . "] $pattern via $eth/$outiface Route:$RouteName";

            if (!isset($GLOBALS["ALREADYADDED"][$pattern])) {
                $GLOBALS["SCRIPTS_ROUTES"][] = "$iprtadd $pattern dev $eth$proto$scrcmd";
                $GLOBALS["ALREADYADDED"][$pattern] = true;
            }
            if ($ruleid <> 999999) {
                if ($pattern <> "default") {
                    $GLOBALS["SCRIPTS_ROUTES"][] = "$iprladd to $pattern iif $eth lookup $RouteName$priority_cmd";
                }
            }
            continue;
        }
        if (!isset($GLOBALS["ALREADYADDED"][$pattern])) {
            if(strlen($outiface)<2) {
                $GLOBALS["SCRIPTS_ROUTES"][] = "$iprtadd $pattern dev $eth$proto$scrcmd$priority_cmd";
            }else{
                if(strlen($RouteName)>1) {
                    $GLOBALS["SCRIPTS_ROUTES"][] = "$iprtadd $pattern dev $eth$proto$scrcmd table $RouteName $priority_cmd";
                }
            }
            $GLOBALS["ALREADYADDED"][$pattern] = true;
        }
        if ($ruleid <> 999999) {
            if ($pattern <> "default") {
                $GLOBALS["SCRIPTS_ROUTES"][] = "$iprladd to $pattern iif $eth lookup $RouteName$priority_cmd";
            }
        }

    }

    $GLOBALS["SCRIPTS_ROUTES"][] = "$ipBin route flush cache || true";

}




function routes_standard_interfaces()
{
    $f[] = "# ------------------------------------------------------\n\n";
    $NetworkAdvancedRouting = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetworkAdvancedRouting"));

    if ($NetworkAdvancedRouting == 1) {
        return "";
    }

    $NetBuilder = new system_nic();
    $q = new lib_sqlite("/home/artica/SQLITE/interfaces.db");

    $NetBuilder->LoadTools();
    $sql = "SELECT Interface,IPADDR,NETMASK,GATEWAY,metric FROM nics WHERE enabled=1";
    $results = $q->QUERY_SQL($sql);
    if (!$q->ok) {
        return "# $q->mysql_error";
    }


    $prot = "proto kernel scope link src";
    $add = "{$GLOBALS["ipbin"]} route add";

    foreach ($results as $index => $ligne) {
        $metric_text = null;
        $gateway = $ligne["GATEWAY"];
        $netmask = $ligne["NETMASK"];
        $ipaddr = $ligne["IPADDR"];
        $Interface = $ligne["Interface"];
        $Interface = $NetBuilder->NicToOther($Interface);
        if(preg_match("#^veth[0-9]+#",$Interface)){
            continue;
        }

        $sed = "/usr/bin/sed";
        $grep = "/usr/bin/grep";
        $f[] = "# ------------- ($index) Clear routes for $Interface $ipaddr/$netmask";
        $f[] = "{$GLOBALS["echobin"]} \"Clear routes for $Interface\"";
        $f[] = "interface_$Interface=$({$GLOBALS["ipbin"]} route | $grep \"dev $Interface\"| $sed -e 's/\s$//g')";
        $f[] = "IFS=\"\n\"";
        $f[] = "for i in \$interface_$Interface; do";
        $f[] = "\t{$GLOBALS["echobin"]} \"'{$GLOBALS["ipbin"]} route del \$i'\"";
        $f[] = "/bin/sh -c \"{$GLOBALS["ipbin"]} route del \$i\"";
        $f[] = "done\n\n";

        if ($ipaddr == "0.0.0.0") {
            continue;
        }
        if ($ipaddr == null) {
            continue;
        }
        if ($netmask == "0.0.0.0") {
            continue;
        }
        if ($netmask == null) {
            continue;
        }
        if ($gateway == "0.0.0.0") {
            $gateway = null;
        }
        if ($gateway == "no") {
            $gateway = null;
        }

        $cdir = routes_standard_interfaces_GetCDIRNetwork($ipaddr, $netmask);
        $metric = intval($ligne["metric"]);
        if ($metric > 0) {
            $metric_text = " metric $metric";
        }
        $cmd = "$add $cdir dev $Interface $prot $ipaddr$metric_text";
        $f[] = "{$GLOBALS["echobin"]} \"$cmd\"";
        $f[] = "/bin/sh -c \"$cmd\"";
    }
    $f[] = "# ------------------------------------------------------\n\n";
    return @implode("\n", $f);
}

function routes_standard_interfaces_GetCDIRNetwork($ipaddr, $network)
{
    exec("/usr/share/artica-postfix/bin/ipcalc $ipaddr/$network 2>&1", $results);
    foreach ($results as $index => $line) {
        if (preg_match("#Network:\s+([0-9\.]+)\/([0-9]+)#i", $line, $re)) {
            if ($GLOBALS["VERBOSE"]) {
                echo "$ipaddr/$network  = `$re[1]/$re[2]`\n";
            }
            return "$re[1]/$re[2]";
        }
    }

    $re = explode(".", $ipaddr);
    return "$re[0].$re[1].$re[2].0/$network";

}

function routes_del($md5)
{
    $unix = new unix();
    $dev="";
    $route = $unix->find_program("route");
    $q = new mysql();
    $sql = "SELECT * FROM nic_routes WHERE `zmd5`='$md5'";
    $ligne = mysqli_fetch_array($q->QUERY_SQL($sql, "artica_backup"));
    $type = $ligne["type"];
    $ttype = "-net";

    if ($type == 2) {
        $ttype = "-host";
    }

    $NetBuilder = new system_nic();
    if ($NetBuilder->IsBridged($ligne["nic"])) {
        $ligne["nic"] = $ligne["BridgedTo"];
    }

    if ($ligne["nic"] <> null) {
        $dev = " dev {$ligne["nic"]}";
    }


    $cmd = "$route del $ttype {$ligne["pattern"]} gw {$ligne["gateway"]}$dev";
    if ($GLOBALS["VERBOSE"]) {
        echo $cmd . "\n";
    }
    shell_exec("$cmd >/dev/null 2>&1");
    $sql = "DELETE FROM nic_routes WHERE `zmd5`='$md5'";
    $q->QUERY_SQL($sql, "artica_backup");
    if (!$q->ok) {
        echo $q->mysql_error;
        return;
    }


}





function ifupifdown()
{
    return;
}
function articalogon()
{
    if (!is_file("/etc/artica-postfix/network.first.settings")) {
        return;
    }
    $f = explode(";", @file_get_contents("/etc/artica-postfix/network.first.settings"));
    //l.Add(IP+';'+Gayteway+';'+netmask+';'+DNS);
    $IPADDR = $f[0];
    $GATEWAY = $f[1];
    $NETMASK = $f[2];
    $DNS1 = $f[3];
    $eth = $f[4];

    $nics = new system_nic($eth);
    $nics->eth = $eth;
    $nics->IPADDR = $IPADDR;
    $nics->NETMASK = $NETMASK;
    $nics->GATEWAY = $GATEWAY;
    $nics->DNS1 = $DNS1;
    $nics->dhcp = 0;
    $nics->enabled = 1;
    $nics->NoReboot = true;
    $nics->SaveNic();
    dev_shm();
    build();
    echo "Settings $eth ($IPADDR) done...\n";

}
function bridge_delete($ID)
{

    $q = new mysql();
    $nicbr = "br$ID";
    $NetBuilder = new system_nic();
    $NetBuilder->LoadTools();
    $NICS = $NetBuilder->BuildBridges_getlinked();
    foreach ($NICS as $a=>$b){
        $q->QUERY_SQL("UPDATE `nics` SET Bridged=0, BridgedTo='' WHERE Interface='$b'", "artica_backup");
        $GLOBALS["SCRIPTS_DEL"][] = "{$GLOBALS["brctlbin"]} delif $nicbr $b";
        $GLOBALS["SCRIPTS_DEL"][] = "{$GLOBALS["ifconfig"]} $b down";

    }

    $GLOBALS["SCRIPTS_DEL"][] = "{$GLOBALS["ifconfig"]} $nicbr down";
    $GLOBALS["SCRIPTS_DEL"][] = "{$GLOBALS["brctlbin"]} delbr $nicbr";
    $q->QUERY_SQL("DELETE FROM `nics_bridge` WHERE ID='$ID'", "artica_backup");

    foreach ($GLOBALS["SCRIPTS_DEL"] as $ligne){
        echo "Starting......: " . date("H:i:s") . " `$ligne`\n";
        shell_exec("$ligne");

    }

    bridge_deletemanu($nicbr);
}

function bridge_deletemanu($eth)
{
    $NetBuilder = new system_nic();
    $NetBuilder->LoadTools();
    if (!$NetBuilder->IfBridgeExists($eth)) {
        return;
    }

    exec("{$GLOBALS["brctlbin"]} show $eth 2>&1", $result);
    foreach ($result as $ligne){
        if (preg_match("#.*\s+.*?\s+.*?\s+([a-z\.0-9]+)$#", $ligne, $re)) {
            if (strtolower(trim($re[1]) == "interfaces")) {
                continue;
            }
            echo "Removing $re[1]\n";
            $GLOBALS["SCRIPTS_DEL"][] = "{$GLOBALS["brctlbin"]} delif $eth $re[1]";
            $GLOBALS["SCRIPTS_DEL"][] = "{$GLOBALS["ifconfig"]} $re[1] down";
            continue;
        }

        if (preg_match("#\s+\s+([a-z\.0-9]+)$#", $ligne, $re)) {
            if (strtolower(trim($re[1]) == "interfaces")) {
                continue;
            }
            echo "Removing $re[1]\n";
            $GLOBALS["SCRIPTS_DEL"][] = "{$GLOBALS["brctlbin"]} delif $eth $re[1]";
            $GLOBALS["SCRIPTS_DEL"][] = "{$GLOBALS["ifconfig"]} $re[1] down";
        }


    }

    $GLOBALS["SCRIPTS_DEL"][] = "{$GLOBALS["ifconfig"]} $eth down";
    $GLOBALS["SCRIPTS_DEL"][] = "{$GLOBALS["brctlbin"]} delbr $eth";

    foreach ($GLOBALS["SCRIPTS_DEL"] as $ligne){
        echo "Starting......: " . date("H:i:s") . " `$ligne`\n";
        shell_exec("$ligne");

    }

}






function remove_service($INITD_PATH)
{
    if (!is_file($INITD_PATH)) {
        return;
    }
    system("$INITD_PATH stop");
    if (is_file('/usr/sbin/update-rc.d')) {
        shell_exec("/usr/sbin/update-rc.d -f " . basename($INITD_PATH) . " remove >/dev/null 2>&1");
    }
    if (is_file('/sbin/chkconfig')) {
        shell_exec("/sbin/chkconfig --del " . basename($INITD_PATH) . " >/dev/null 2>&1");
    }
    if (is_file($INITD_PATH)) {
        @unlink($INITD_PATH);
    }
}

//

?>

