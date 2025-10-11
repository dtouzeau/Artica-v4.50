<?php
//SP119
include_once(dirname(__FILE__) . "/frame.class.inc");
include_once(dirname(__FILE__) . "/class.unix.inc");
include_once(dirname(__FILE__) . "/class.postfix.inc");
if (isset($_GET["enable"])) {
    enable_service();
    exit;
}
if (isset($_GET["disable"])) {
    disable_service();
    exit;
}
if (isset($_GET["enable-secondary_node"])) {
    enable_secondary_node_service();
    exit;
}
if (isset($_GET["disable-secondary_node"])) {
    disable_secondary_node_service();
    exit;
}
if (isset($_GET["status"])) {
    status();
    exit;
}
if (isset($_GET["restart"])) {
    restart();
    exit;
}
if (isset($_GET["start"])) {
    start();
    exit;
}
if (isset($_GET["stop"])) {
    stop();
    exit;
}
if (isset($_GET["reconfigure"])) {
    reconfigure();
    exit;
}
if (isset($_GET["syslog"])) {
    getLogs();
    exit;
}
if (isset($_GET["stats"])) {
    stats();
    exit;
}
if (isset($_GET["sync-nodes"])) {
    sync_nodes();
    exit;
}
if (isset($_GET["setup-nodes"])) {
    setup_nodes();
    exit;
}
if (isset($_GET["node-delete-vips"])) {
    nodes_delete_vips();
    exit;
}
if (isset($_GET["node-delete-tracks"])) {
    nodes_delete_tracks();
    exit;
}
if (isset($_GET["node-delete"])) {
    nodes_delete();
    exit;
}
if (isset($_GET["node-delete-services"])) {
    nodes_delete_services();
    exit;
}


if (isset($_GET["action-delete-nodes-vips"])) {
    action_delete_nodes_vips();
    exit;
}
if (isset($_GET["action-delete-nodes-services"])) {
    action_delete_nodes_services();
    exit;
}

if (isset($_GET["action-delete-nodes-tracks"])) {
    action_delete_nodes_tracks();
    exit;
}
if (isset($_GET["action-delete-nodes"])) {
    action_delete_nodes();
    exit;
}
if (isset($_GET["primary-node-delete"])) {
    primary_node_delete();
    exit;
}
if (isset($_GET["sync-debug"])) {
    sync_debug();
    exit;
}

function sync_debug()
{
    $GLOBALS["PROGRESS_FILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --sync-debug >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function enable_service()
{
    $GLOBALS["PROGRESS_FILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --enable >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function enable_secondary_node_service()
{
    $GLOBALS["PROGRESS_FILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --enable-secondary_node >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function disable_secondary_node_service()
{
    $GLOBALS["PROGRESS_FILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --disable-secondary_node >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function disable_service()
{
    $GLOBALS["PROGRESS_FILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --disable >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function status()
{
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.status";
    $cmd = "$php5 /usr/share/artica-postfix/exec.status.php --keepalived >{$GLOBALS["LOGSFILES"]} 2>&1";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function restart()
{
    $GLOBALS["CACHEFILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function start()
{
    $GLOBALS["CACHEFILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --start >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function stop()
{
    $GLOBALS["CACHEFILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --stop >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function reconfigure()
{
    $GLOBALS["CACHEFILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --reconfigure >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function getLogs()
{
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);

    $unix = new unix();
    $grep = $unix->find_program("grep");
    $tail = $unix->find_program("tail");
    $MAIN = unserialize(base64_decode($_GET["syslog"]));
    $PROTO_P = null;

    foreach ($MAIN as $val => $key) {

        writelogs_framework("$val, $key", __FUNCTION__, __FILE__, __LINE__);
        $MAIN[$key] = str_replace(".", "\.", $MAIN[$key]);
        $MAIN[$key] = str_replace("*", ".*?", $MAIN[$key]);

    }

    $max = $MAIN["MAX"];
    $date = $MAIN["DATE"];
    $PROTO = $MAIN["PROTO"];
    $SRC = $MAIN["SRC"];
    $DST = $MAIN["DST"];
    $SRCPORT = $MAIN["SRCPORT"];
    $DSTPORT = $MAIN["DSTPORT"];
    $IN = $MAIN["IN"];
    $OUT = $MAIN["OUT"];
    $MAC = $MAIN["MAC"];
    $DAEMON = $MAIN["DAEMON"];
    if ($MAIN["TERM"] <> null) {
        $TERM = ".*?{$MAIN["TERM"]}";
    }
    if ($SRC <> null) {
        $SRC_P = ".*?\/$SRC.*?";
    }
    if ($SRCPORT <> null) {
        $SRCPORT_P = ".*?:$SRCPORT.*?";
    }
    if ($MAIN["C"] == 0) {
        $TERM_P = $TERM;
    }


    $mainline = "{$TERM_P}{$SRC_P}{$PROTO_P}{$SRCPORT_P}";
    if ($TERM <> null) {
        if ($MAIN["C"] > 0) {
            $mainline = "($mainline|$TERM)";
        }
    }

    $search = "$date.*?$mainline";
    $cmd = "$grep -iE '$search' /var/log/keepalived.log |$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/keepalived.syslog 2>&1";
    writelogs_framework("$cmd", __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);

}

function stats()
{
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
    $json_file = "/usr/share/artica-postfix/ressources/logs/web/keepalived.json";
    $unix = new unix();
    $kill = $unix->find_program("kill");
    $keepalived = $unix->find_program("keepalived");
    $cp = $unix->find_program("cp");
    $cmd = "$kill -s $($keepalived --signum=JSON) $(cat /var/run/keepalived/keepalived.pid)";

    writelogs_framework("$cmd", __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
    @unlink($json_file);
    @touch($json_file);
    @chmod($json_file, 0777);
    $cmd = "$cp /tmp/keepalived.json /usr/share/artica-postfix/ressources/logs/web/keepalived.json";

    writelogs_framework("$cmd", __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);

}

function sync_nodes()
{
    $GLOBALS["CACHEFILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --sync-nodes {$_GET["id"]} {$_GET["secondary_node_id"]}>{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function setup_nodes()
{
    $GLOBALS["CACHEFILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --setup-nodes {$_GET["data"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function nodes_delete_vips()
{
    $GLOBALS["CACHEFILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --node-delete-vips {$_GET["primary_node_id"]} {$_GET["synckey"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function nodes_delete_services()
{
    $GLOBALS["CACHEFILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --node-delete-services {$_GET["primary_node_id"]} {$_GET["synckey"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function nodes_delete_tracks()
{
    $GLOBALS["CACHEFILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --node-delete-tracks {$_GET["primary_node_id"]} {$_GET["synckey"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function nodes_delete()
{
    $GLOBALS["CACHEFILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --node-delete {$_GET["primary_node_id"]} {$_GET["secondary_node_id"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function action_delete_nodes_vips()
{
    $GLOBALS["CACHEFILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --action-delete-nodes-vips {$_GET["data"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function action_delete_nodes_services()
{
    $GLOBALS["CACHEFILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --action-delete-nodes-services {$_GET["data"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}


function action_delete_nodes_tracks()
{
    $GLOBALS["CACHEFILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --action-delete-nodes-tracks {$_GET["data"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function action_delete_nodes()
{
    $GLOBALS["CACHEFILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --action-delete-nodes {$_GET["data"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function primary_node_delete()
{
    $GLOBALS["CACHEFILE"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.progress";
    $GLOBALS["LOGSFILES"] = "/usr/share/artica-postfix/ressources/logs/web/keepalived.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"], 0777);
    @chmod($GLOBALS["LOGSFILES"], 0777);
    $unix = new unix();
    $php5 = $unix->LOCATE_PHP5_BIN();
    $nohup = $unix->find_program("nohup");
    $cmd = "$nohup $php5 /usr/share/artica-postfix/exec.keepalived.php --delete-primary_node {$_GET["primary_node_id"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}