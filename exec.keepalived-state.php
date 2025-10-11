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
if ($argv[1] == "--state") {
    state($argv[2], $argv[3], $argv[4]);
    exit;
}
if ($argv[1] == "--ping") {
    ping();
    exit;
}
function state($type, $instance, $state)
{
    $unix = new unix();
    $str = explode("_", $instance);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("keepalivedstate", "$state - $instance");
    $id = intval($str[1]);

    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
    $sql = $q->mysqli_fetch_array("SELECT * FROM `keepalived_primary_nodes` WHERE ID='$id'");
    $primary_node_pid = trim(@file_get_contents("/var/run/keepalived/keepalived.pid"));
    if (!$unix->process_exists($primary_node_pid)) {
        $state = "STOP";
    }

    // 0 -> RED, 1 -> WARN, 2 -> INFO
    $severity = 0;
    $txt = "going to FAULT state due -";
    if ($state == "MASTER" || $state == "BACKUP") {
        $txt = "returns to state - ";
        $severity = 2;
    }
    squid_admin_mysql(intval($severity), "The Failover Services $txt $state", null, __FILE__, __LINE__);

    $q->QUERY_SQL("UPDATE keepalived_primary_nodes SET service_state='$state' WHERE ID='{$sql["ID"]}'");
    if (intval($sql['isPrimaryNode']) == 0) {
        notify_primary_node($sql, $state);
    }
}

function ping()
{
    $unix = new unix();
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
    $primary_nodes = $q->QUERY_SQL("SELECT * FROM `keepalived_primary_nodes` WHERE isPrimaryNode='1'");
    foreach ($primary_nodes as $index => $ligne) {
        $secondary_nodes = $q->QUERY_SQL("SELECT * FROM `keepalived_secondary_nodes` WHERE primary_node_id='{$ligne["ID"]}'");
        foreach ($secondary_nodes as $index => $secondary_node) {
            $array = array();
            $ID = $secondary_node["ID"];
            $array["action"] = 'ping-keepalived-nodes';
            $array["secondary_node"]["primary_node_id"] = $secondary_node["primary_node_id"];
            $array["secondary_node"]["secondary_node_ip"] = $secondary_node["secondary_node_ip"];
            $array["secondary_node"]["synckey"] = $secondary_node["synckey"];
            $URI = "https://{$secondary_node["secondary_node_ip"]}:{$secondary_node["secondary_node_port"]}";
            if (!POST_INFOS($URI, $array, $ID)) {
                $q->QUERY_SQL("UPDATE keepalived_secondary_nodes SET status=1, errortext='{$GLOBALS["ERROR_INFO"]}' WHERE ID={$secondary_node["ID"]}");
            }

        }

    }


}

function POST_INFOS($uri, $posted = array(), $ID = 0)
{
    $unix = new unix();

    $x = json_encode($posted, JSON_FORCE_OBJECT);
    $post_data["{$posted["action"]}"] = true;
    $post_data["secondary_node_ip"] = $posted["secondary_node"]["secondary_node_ip"];
    $post_data["post_data"] = base64_encode(serialize($x));
    keepalived_syslog_primary_node("Notify node: $uri");
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
        keepalived_syslog_primary_node("Error $uri says {$re[1]}");
        $GLOBALS["ERROR_INFO"] = $re[1];
        return false;
    }

    if (preg_match("#<STATUS>([0-9]+)</STATUS>#is", $data, $re)) {
        keepalived_syslog_primary_node("$uri Entering in setup mode, waiting feedbacks");
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $q->QUERY_SQL("UPDATE keepalived_secondary_nodes SET status={$re[1]}, errortext='' WHERE ID=$ID");
        return true;
    }

    if (preg_match("#RETURNED_TRUE#is", $data)) {
        keepalived_syslog_primary_node("$uri return True for order");
        $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
        $q->QUERY_SQL("UPDATE keepalived_secondary_nodes SET status=2, errortext='' WHERE ID=$ID");
        return true;
    }


    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    if ($GLOBALS["VERBOSE"]) {
        echo "CURLINFO_HTTP_CODE = $CURLINFO_HTTP_CODE $curl_errno=$curl_errno\n";
    }

}


function notify_primary_node($data, $state)
{
    $unix = new unix();
    $post_data["primaryNodeID"] = $data['primaryNodeID'];
    $post_data["synckey"] = $data['synckey'];
    $post_data["keepalivedServiceStatus"] = $state;
    $uri = "{$data["primaryNodeIP"]}:{$data["primaryNodePort"]}";
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

function keepalived_syslog_primary_node($text)
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