#!/usr/bin/php
<?php
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
    if ($argv[1] == "--remove-macvlan") {
           remove_macvlan();
           exit;
       }
    if ($argv[1] == "--loopback") {
        loopback();
        exit();
    }
    if ($argv[1] == "--just-add") {
        exit();
    }
    if ($argv[1] == "--articalogon") {
        articalogon();
        exit();
    }
    if ($argv[1] == "--ifconfig") {
        ifconfig_tests();
        exit;
    }

    if ($argv[1] == "--parse-tests") {
        ifconfig_parse($argv[2]);
        exit;
    }
    if ($argv[1] == "--routes") {
        exit;
    }
    if ($argv[1] == "--routes-del") {
        routes_del($argv[2]);
        exit;
    }
    if ($argv[1] == "--vlans") {
        build();
        exit;
    }
    if ($argv[1] == "--build") {
        build();
        exit;
    }
    if ($argv[1] == "--postfix-instances") {
        exit;
    }
    if ($argv[1] == "--ping") {
        ping($argv[2]);
        exit;
    }
    if ($argv[1] == "--ifupifdown") {
        ifupifdown();
        exit;
    }
    if ($argv[1] == "--reconstruct-interface") {
        reconstruct_interface($argv[2]);
        exit;
    }
    if ($argv[1] == "--main-routes") {
        routes_main_build();
        exit;
    }
    if ($argv[1] == "--routes") {
        exit;
    }

    if ($argv[1] == "--vlans-delete") {
        vlan_delete($argv[2]);
        exit;
    }

    if ($argv[1] == "--virtip-delete") {
        virtip_delete($argv[2]);
        exit;
    }
    if ($argv[1] == "--bridge-delete") {
        bridge_delete($argv[2]);
        exit;
    }
    if ($argv[1] == "--bridge-rm") {
        bridge_deletemanu($argv[2]);
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

function ping($host)
{
    ini_set_verbosed();
    $unix = new unix();
    if ($unix->PingHost($host)) {
        echo "$host:TRUE\n";
    } else {
        echo "$host:FALSE\n";
    }

}




function loopback()
{
    $unix = new unix();
    $ifconfig = $unix->find_program("ifconfig");
    shell_exec("$ifconfig lo down");
    shell_exec("$ifconfig lo 127.0.0.1 netmask 255.255.255.0 up >/dev/null 2>&1");
    VirtualsIPSyslog("Restarting loopback...");
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

}function ScriptInfo($line,$ASSYSTEMD):string{

    $line = str_replace("\n", "", $line);
    $line = str_replace("\r", "", $line);
    $line = str_replace("\r\n", "", $line);
    if($ASSYSTEMD){
        $NewLine=$line;
        $NewLine=str_replace('"', "'", $NewLine);
        $NewLine=str_replace('#', "", $NewLine);
        $NewLine="echo \"$NewLine\" | systemd-cat -t artica-ifup";
        return $NewLine."\n".$line;
    }
    return $line;

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
function virtip_delete($ID)
{
    if (!is_numeric($ID)) {
        return;
    }
    if ($ID < 1) {
        return;
    }
    $sql = "SELECT * FROM nics_virtuals WHERE ID='$ID'";
    $q = new lib_sqlite("/home/artica/SQLITE/interfaces.db");

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
    $eth = "{$ligne["nic"]}:{$ligne["ID"]}";
    shell_exec("{$GLOBALS["ifconfig"]} $eth down");
    $q->QUERY_SQL("DELETE FROM nics_virtuals WHERE ID='$ID'", "artica_backup");


}


function reconstruct_interface($eth){
    $GLOBALS["NO_GLOBAL_RELOAD"] = true;
    if ($GLOBALS["SLEEP"]) {
        sleep(10);
    }
    build();
    ifupifdown($eth);
    shell_exec("/usr/sbin/artica-phpfpm-service -udhcp-reconf");
    shell_exec("/usr/sbin/artica-phpfpm-service -iptables-routers");
    shell_exec("/usr/sbin/artica-phpfpm-service -parprouted-check");

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

function uninstall_network():bool{
    if(!is_file("/etc/init.d/artica-ifup")){
        return true;
    }
    $unix=new unix();
    return $unix->remove_service("/etc/init.d/artica-ifup");
}
function ExecSystemd($cmdline,$AsSystemd=false):string{
    $logfile = "/var/log/net-start.log";

    if(!$AsSystemd){
        if(strpos($cmdline,">>")==0){
            $cmdline="$cmdline >> $logfile || true";
        }
        return $cmdline;
    }
    
    $cmdlineTR=str_replace('"',"'",$cmdline);
    $f[]="echo \"Running $cmdlineTR\" | systemd-cat -t artica-ifup";
    $f[]="$cmdline | systemd-cat -t artica-ifup || true";
    return @implode("\n",$f);
}
function ScriptEcho($echobin,$line,$ASSYSTEMD):string{
    if($ASSYSTEMD){
        $sh[]="$echobin \"$line\" | systemd-cat -t artica-ifup 2>&1";
        $sh[] = "$line 2>&1 | systemd-cat -t artica-ifup || true";
        return @implode("\n",$sh);
    }
    $logfile = "/var/log/net-start.log";
    $sh[]="$echobin \"$line\" >>$logfile 2>&1 || true";
    $sh[] = "$line >>$logfile 2>&1 || true";
    return @implode("\n",$sh);
}
function ScriptOut($echobin,$text,$ASSYSTEMD,$line=0):string{
    if($ASSYSTEMD){
        return "$echobin \"[$line]: $text\" | systemd-cat -t artica-ifup 2>&1";
    }
    return "$echobin \"$text\" || true";
}
function build(){
    $unix = new unix();
    $q = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $GLOBALS["SCRIPTS"]=array();
    $sock = new sockets();
    $DisableNetworking=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));

    if($DisableNetworking==1){
        uninstall_network();
    }
    $ASSYSTEMD=false;
    $systemdCat=$unix->find_program("systemd-cat");
    if(is_file($systemdCat)){
        if(is_dir("/run/systemd/journal")) {
            $ASSYSTEMD = true;
        }
    }
    $logfile = "/var/log/net-start.log";
    $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . ".pid";
    $pid = @file_get_contents($pidfile);

    $GLOBALS["ipbin"] = $unix->find_program("ip");
    $GLOBALS["SCRIPTS_DOWN"] = array();

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
    $echobin = $unix->find_program("echo");
    $logger = $unix->find_program("logger");
    $cacheFile = "/etc/artica-postfix/Interfaces.gob";
	$BondCachefile = "/etc/artica-postfix/bonds.gob";

    @unlink($cacheFile);
    @unlink($BondCachefile);
    $GLOBALS["SCRIPTS_TOP"]=array();
    $GLOBALS["SAVED_INTERFACES"] = array();
    @file_put_contents($pidfile, getmypid());
    build_progress(40, "{checking} bridges");
    $prc=40;
    $sh = array();
    $sh[] = "#!/bin/sh -e";
    $sh[] = "### BEGIN INIT INFO";
    $sh[] = "# Provides:          artica-ifup";
    $sh[] = "# Required-Start:    \$local_fs";
    $sh[] = "# Required-Stop:     \$local_fs";
    $sh[] = "# Should-Start:		";
    $sh[] = "# Should-Stop:		";
    $sh[] = "# Default-Start:     S";
    $sh[] = "# Default-Stop:      0 6";
    $sh[] = "# Short-Description: start and stop the network";
    $sh[] = "# Description:       Artica ifup service Raise network interfaces";
    $sh[] = "### END INIT INFO";
    $sh[] = "case \"\$1\" in";
    $sh[] = "start)";
    $prc++;build_progress($prc,"{building} ".__LINE__);
    system("/usr/sbin/artica-phpfpm-service -build-dhcp-interfaces");
    $prc++;build_progress($prc,"{building} ".__LINE__);
    system("/usr/sbin/artica-phpfpm-service -optimize-os");
    $prc++;build_progress($prc,"{building} ".__LINE__);
    $sh[] = "$logger -i -t network \"Artica network Script executed (start)\" || true";
    $sh[] = "$echobin \"  **** Apply Network configuration, please wait... ****\"";
    $sh[] = "$echobin \"  **** Apply modprobes... ****\"";
    $sh[] = "if [ -f /usr/sbin/artica-net-startup.sh ]; then";
    $sh[] = "\t/usr/sbin/artica-net-startup.sh";
    $sh[] = "fi";
    $sh[] = "$echobin \"  **** Apply DHCP interfaces... ****\"";
    $sh[] = "/usr/sbin/artica-phpfpm-service -start-dhcp-client || true";
    $sh[] = "$echobin \"  **** Apply Physical interfaces... ****\"";
    $sh[] = "/usr/sbin/artica-phpfpm-service -physical-interfaces || true";
    $sh[] = "$echobin \"  **** Apply Flush routes... ****\"";
    $sh[] = "/usr/sbin/artica-phpfpm-service -flush-ip-rules || true";
    $sh[] = "$echobin \"  **** Apply Build routes... ****\"";
    $sh[] = "/usr/sbin/artica-phpfpm-service -build-routes || true";
    build_progress(76, "{checking} WIFI...");
    $prc=Check_wifi_cards();
    shell_exec("/usr/sbin/artica-phpfpm-service -network-systemd");
    $sh[] = "$echobin \"\" > /var/log/net-start.log";
    $sh[] = "$echobin \"  **** Apply Network configuration, please wait... ****\"";


    $prc++;build_progress($prc,"{building} ".__LINE__);

    foreach ($GLOBALS["SCRIPTS_TOP"] as $line) {
        $line = trim($line);
        if ($line == null) {
            continue;
        }

        if (preg_match("#^(if|fi)\s+#", $line)) {
            $sh[] = "$line";
            continue;
        }

        if (preg_match("#^(if|fi|while)\s+#", $line)) {
            $sh[] = "$line";
            continue;
        }

        if (substr($line, 0, 1) == "#") {
            $sh[] = ScriptInfo($line,$ASSYSTEMD);
            continue;
        }
        $md = md5($line);

        if (isset($AL[$md])) {
            echo "Starting......: " . date("H:i:s") . " SKIPING `$line`\n";
            continue;
        }
        $AL[$md] = true;
        echo "Starting......: " . date("H:i:s") . " `$line`\n";

        if (strpos($line, "/etc/hosts") > 0) {
            $sh[] = "$line";
            continue;
        }


        if (preg_match("#\/echo\s+#", $line)) {
            $sh[] = "$line";
            continue;
        }

        if (preg_match("#^[A-Z]+=#", $line)) {
            $sh[] = "$echobin \"SET variable [$line]\" >>$logfile 2>&1";
            $sh[] = "$line";
            continue;
        }

        if (preg_match("#ifconfig\s+(.+?)\s+(.+?)netmask(.+?)\s+#", $line, $re)) {
            $sh[] = ScriptOut($echobin,"adding $re[2]/$re[3] in $re[1] interface",$ASSYSTEMD);
        }
        $sh[] =ScriptEcho($echobin,$line,$ASSYSTEMD);


    }
    foreach ($GLOBALS["SCRIPTS"] as $line) {
        $line = trim($line);
        if ($line == null) {
            continue;
        }
        if (substr($line, 0, 1) == "#") {
            $sh[] = ScriptInfo($line,$ASSYSTEMD);
            continue;
        }

        if (preg_match("#^(if|fi)\s+#", $line)) {
            $sh[] = "$line";
            continue;
        }

        if (preg_match("#^OUTPUT\s+(.+)#", $line, $re)) {
            $re[1] = str_replace('"', "'", $re[1]);
            $sh[] = "$echobin \"$re[1]\"";
            continue;
        }

        $md = md5($line);
        if (isset($AL[$md])) {
            echo "Starting......: " . date("H:i:s") . " SKIPING `$line`\n";
            continue;
        }
        if (preg_match("#\/echo\s+#", $line)) {
            $sh[] = "$line";
            continue;
        }


        if (preg_match("#^[A-Z]+=#", trim($line))) {
            $sh[] = "$echobin \"SET variable [$line]\" >>$logfile 2>&1";
            $sh[] = "$line";
            continue;
        }

        $AL[$md] = true;
        echo "Starting......: " . date("H:i:s") . " `$line`\n";

        if (strpos($line, "/etc/hosts") > 0) {
            $sh[] = "$line";
            continue;
        }


        if (preg_match("#ifconfig\s+(.+?)\s+(.+?)netmask(.+?)\s+#", $line, $re)) {
            $sh[] = ScriptOut($echobin,"adding $re[2]/$re[3] in $re[1] interface",$ASSYSTEMD,__LINE__);


        }

        if (strpos('echo "', $line) == 0) {
            $sh[] = ScriptOut($echobin,$line,$ASSYSTEMD,__LINE__);
        }
        $sh[] = ExecSystemd($line,$ASSYSTEMD);
    }
    $cmdlines = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EthtoolsCommands"));
    if (is_array($cmdlines)) {
        if (count($cmdlines) > 0) {
            $sh[] = "";
            $sh[] = "# [" . __LINE__ . "]";
            $sh[] = "# [" . __LINE__ . "] *******************************";
            $sh[] = "# [" . __LINE__ . "] ****     ETH TOOLS    ****";
            $sh[] = "# [" . __LINE__ . "] *******************************";
            $sh[] = "# [" . __LINE__ . "] See /etc/artica-postfix/settings/Daemons/EthtoolsCommands";
            $sh[] = "# [" . __LINE__ . "]";
            foreach ($cmdlines as $line) {
                $sh[] = "$line >>$logfile 2>&1 || true";
            }
        }
    }



    $GLOBALS["START_ROUTES"][] = "$echobin \"Apply network routes, please wait...\"";
    $GLOBALS["START_ROUTES"][] = "# [" . __LINE__ . "]";
    $GLOBALS["START_ROUTES"][] = "# [" . __LINE__ . "] *******************************";
    $GLOBALS["START_ROUTES"][] = "# [" . __LINE__ . "] ****     NETWORK ROUTES    ****";
    $GLOBALS["START_ROUTES"][] = "# [" . __LINE__ . "] *******************************";
    $GLOBALS["START_ROUTES"][] = "# [" . __LINE__ . "]";

    $unix = new unix();
    $echo = $unix->find_program("echo");
    $nohup = $unix->find_program("nohup");
    $php = $unix->LOCATE_PHP5_BIN();
    $prc++;build_progress($prc,"{building} ".__LINE__);

    $sh[]="$nohup /usr/sbin/artica-phpfpm-service -restart-network-services >/dev/null 2>&1 &";
    $sh[] = nics_vde_build();
    $EnableLinkBalancer = intval($sock->GET_INFO("EnableLinkBalancer"));

    if ($EnableLinkBalancer == 1) {
        if (!is_dir("/etc/firehol")) {
            @mkdir("/etc/firehol", 0755, true);
        }
        system("$php /usr/share/artica-postfix/exec.firehol.php --configure-lb");
        $linkbalancerbin = $unix->find_program("link-balancer");
        $sh[] = "# [" . __LINE__ . "] Running Link-balancer";
        $sh[] = "if [ -x /etc/firehol/link-balancer.conf ] ; then";
        $sh[] = "\t$linkbalancerbin ||true";
        $sh[] = "fi";
    }
    $prc++;build_progress($prc,"{building} ".__LINE__);
    $logger = $unix->find_program("logger");
    $sh[] = "$echo \"  ****      Apply Network configuration, done      ****\"";
    $sh[] = "$logger -i -t network \"Artica network Script terminated\" || true";
    $sh[] = ";;";
    $sh[] = "  stop)";
    $sh[] = "$logger -i -t network \"* * * * * * * * * * * * * * SUSPECTED STOPPED SERVER !!! * * * * * * * * * * * * * *\" || true";
    $sh[] = "$logger -i -t network \"Artica network Script executed (stop)\" || true";
    if (is_array($GLOBALS["SCRIPTS_DOWN"])) {
        foreach ($GLOBALS["SCRIPTS_DOWN"] as $line) {
            if (substr($line, 0, 1) == "#") {
                $sh[] = ScriptInfo($line,$ASSYSTEMD);
                continue;
            }
            $sh[] = "$line >>/var/log/net-stop.log 2>&1 || true";

        }
    }


    $php = $unix->LOCATE_PHP5_BIN();
    $sh[] = ";;";
    $sh[] = "reconfigure)";
    $sh[] = "$logger -i -t network \"Artica network Script Executed (reconfigure)\" || true";
    $sh[] = "$php " . __FILE__ . " --build --force $2 $3";
    $sh[] = "/usr/sbin/artica-phpfpm-service -restart-network";
    $sh[] = ";;";
    $sh[] = "routes)";
    $sh[] = "$echobin \"Routes applied to the system\"";
    $sh[] = "/usr/sbin/artica-phpfpm-service -reconfigure-firewall >> /var/log/net-start.log 2>&1";
    $sh[] = ";;";
    $sh[] = "*)";
    $sh[] = "$logger -i -t network \"Artica network Script executed (unknown)\" || true";
    $sh[] = " echo \"Usage: $0 {start or reconfigure only}\"";
    $sh[] = "exit 1";
    $sh[] = ";;";
    $sh[] = "esac";
    $sh[] = "exit 0\n";

    if (!is_file("/etc/artica-postfix/settings/Daemons/NetworksConfiguredFirstTime")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NetworksConfiguredFirstTime", time());
    }
    echo " * * * * /etc/init.d/artica-ifup DONE * * * *\n";
    @file_put_contents("/etc/init.d/artica-ifup", @implode("\n", $sh));
    @chmod("/etc/init.d/artica-ifup", 0755);
    if (is_file('/usr/sbin/update-rc.d')) {
        shell_exec("/usr/sbin/update-rc.d -f artica-ifup defaults >/dev/null 2>&1");

        if (is_file('/etc/init.d/networking')) {
            shell_exec("/usr/sbin/update-rc.d -f networking disable  >/dev/null 2>&1");
            @copy("/etc/init.d/networking", "/etc/init.d/networking.back");
            @unlink("/etc/init.d/networking");
        }
    }

    if (is_file('/sbin/chkconfig')) {
        shell_exec("/sbin/chkconfig --add artica-ifup >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 1234 artica-ifup on >/dev/null 2>&1");
    }
    shell_exec("/usr/sbin/update-rc.d -f artica-ifup remove >/dev/null 2>&1");
    shell_exec("/usr/sbin/update-rc.d -f artica-ifup defaults >/dev/null 2>&1");

    if (is_file("/bin/iptables-iproute2.sh")) {
        @unlink("/bin/iptables-iproute2.sh");
    }

    if (isset($GLOBALS["SCRIPTS_IPTABLES"])) {
        $sh_iptables[] = "#!/bin/sh -e";
        $sh_iptables[] = @implode("\n", $GLOBALS["SCRIPTS_IPTABLES"]);
        $sh_iptables[] = "";
        @file_put_contents("/bin/iptables-iproute2.sh", @implode("\n", $sh_iptables));
        @chmod("/bin/iptables-iproute2.sh", 0755);
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
    squid_admin_mysql(2, "Network script was successfully rebuilded", null, __FILE__, __LINE__);
    echo "Starting......: " . date("H:i:s") . " done...\n";
    $CRC32_INTERFACES_DB=crc32_file("/home/artica/SQLITE/interfaces.db");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CRC32_INTERFACES_DB",$CRC32_INTERFACES_DB);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CRC32_INTERFACES_CURRENT",$CRC32_INTERFACES_DB);
    $prc++;
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

function nics_vde_build()
{
    $unix = new unix();
    if($unix->ServerRunSince());
    $virtualports = $unix->DirFiles("/etc/init.d", "virtualport-[0-9]+");

    $sh[] = "# [" . __LINE__ . "]";
    $sh[] = "# [" . __LINE__ . "]";
    $sh[] = "# [" . __LINE__ . "] ************************************************";
    $sh[] = "# [" . __LINE__ . "] Virtual Network interfaces via virtual SWITCH";
    $sh[] = "# [" . __LINE__ . "] ************************************************";
    $sh[] = "";

    $c = 0;
    foreach ($virtualports as $numFile => $xfile) {
        $c++;
        $sh[] = "/etc/init.d/$numFile restart || true";
    }
    $sh[] = "";
    $sh[] = "";

    if ($c == 0) {
        return null;
    }
    return @implode("\n", $sh);
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




function remove_macvlan():bool{

    $unix=new unix();
    $ip=$unix->find_program("ip");
    $proc_net_dev=explode("\n",@file_get_contents("/proc/net/dev"));

    foreach ($proc_net_dev as $line) {
        $line = trim($line);
        if ($line == null) {
            continue;
        }
        if (!preg_match("#^(.+?):\s+[0-9]+#", $line, $re)) {
            continue;
        }
        $Interface=trim($re[1]);
        if(!preg_match("#^veth[0-9]+#",$Interface)){continue;}
        shell_exec("$ip link set $Interface down");
        shell_exec("$ip link delete $Interface");

    }
    return true;

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

