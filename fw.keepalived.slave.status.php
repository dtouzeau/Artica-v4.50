<?php
// Service Pack 9 2026-10-10
// Failover (keepalived) status on a SECONDARY node: is this backup ready to take over, what it
// would take over, when it last received its settings from the primary.
//
// Stitch redesign 2026-11-22 (stitch_artica_failover_dashboard_redesign). REAL DATA ONLY:
//   GET /keepalived/nodes               instances copied from the primary (service_state, fault_reason,
//                                       foreign_speakers, last_sync = Unix seconds, health-check settings)
//   GET /keepalived/instance?primaryid= services, virtual_ips, track_interfaces of one instance
//   GET /keepalived/parameters          log_detail
//   PROGRESS_DIR/keepalived.progress    last start/stop/restart/reload result
// Health-check defaults are the ones articarest writes for go-failover-checker
// (keepalived/keepalived.go BuildConfig): disk 95 %, RAM 95 %, load 4, web test
// http://articatech.com, DNS test cloudflare.com.
// The left column (keepalived-status, keepalived_status_html(), refreshed every 30 s by
// RefreshInterval_js) is kept exactly as it was. One model (kasv_model) feeds every widget.
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}
// Inclusion INCONDITIONNELLE: awc_table_style() vit ici.
include_once(dirname(__FILE__) . "/ressources/class.statistics_appliance.inc");
$users = new usersMenus();
if (!$users->AsVPNManager) {
    exit();
}
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
if (isset($_GET["main"])) {
    main();
    exit;
}
if (isset($_GET["keepalived-status"])) {
    keepalived_status();
    exit;
}
if (isset($_GET["kasv-top"])) {
    kasv_top();
    exit;
}
if (isset($_GET["kasv-right"])) {
    kasv_right();
    exit;
}
if (isset($_GET["build-progress"])) {
    build_progress();
    exit;
}
if (isset($_GET["action-js"])) {
    kasv_action_js();
    exit;
}
if (isset($_GET["stop-js"])) {
    kasv_stop_js();
    exit;
}
if (isset($_POST["kasv-stop"])) {
    kasv_stop_save();
    exit;
}
page();

// Kept for callers that still load it: reconfigure, then redraw the page.
function build_progress()
{
    echo keepalived_jsaction("/keepalived/reconfigure");
}

// Every action reports into keepalived.progress (articarest), and at 100% the
// whole block is redrawn: status, last operation and instances.
function keepalived_jsaction($route): string
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    return $tpl->framework_buildjs($route, "keepalived.progress", "keepalived.log",
        "progress-firehol-restart", "LoadAjax('table-loader','$page?main=yes');");
}

// The service state, from articarest (/keepalived/status). Drawn with the
// page, then refreshed every 30 s by RefreshInterval_js().
function keepalived_status_html(): string
{
    $tpl = new template_admin();
    $json = json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/keepalived/status"));
    if (json_last_error() > JSON_ERROR_NONE || !is_object($json)) {
        return $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data " . json_last_error(), "{error}"));
    }
    if (!$json->Status) {
        return $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars((string)$json->Error)));
    }
    $ini = new Bs_IniHandler();
    $ini->loadString((string)($json->Info ?? ""));
    return $tpl->SERVICE_STATUS($ini, "APP_KEEPALIVED", keepalived_jsaction("/keepalived/restart"));
}

function keepalived_status()
{
    echo keepalived_status_html();
}

function page()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $KEEPALIVED_VERSION = htmlspecialchars((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_VERSION"));
    $btn = $tpl->button_inline("{online_help}", "s_PopUp('https://wiki.articatech.com/en/artica-failover-service','1024','800')", "fa-solid fa-headset", null, null, "btn-blue");

    $html = "
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_KEEPALIVED} v$KEEPALIVED_VERSION</h1><p>{kasv_header_explain}</p>$btn</div>
	</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content' style='background:#f8fafc'>
	<div id='table-loader'></div>
	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/failover-status');
	LoadAjax('table-loader','$page?main=yes');
	</script>";

    $tpl = new templates();
    if (isset($_GET["main-page"])) {
        $tpl = new template_admin('Artica: Failover Status', $html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Actions ───────────────────────────────────────────────────────────────

// Restart / reload / start run at once (they change nothing a user would lose).
// No admin_tracks() here: fw.progress.php logs "Launch task <title> (<route>)" for every
// framework_buildjs() action, this one included.
function kasv_action_js()
{
    header("content-type: application/x-javascript");
    $map = array("restart" => "/keepalived/restart", "reload" => "/keepalived/reload", "start" => "/keepalived/start");
    $a = (string)($_GET["action-js"] ?? "");
    if (!isset($map[$a])) {
        return;
    }
    echo keepalived_jsaction($map[$a]);
}

// Stop releases the shared addresses when this node is active: confirmed first. The
// confirmation posts kasv-stop (answered empty), then runs the stop through fw.progress.php,
// which logs it.
function kasv_stop_js()
{
    $tpl = new template_admin();
    $tpl->js_dialog_confirm_action("{kasv_stop_confirm}", "kasv-stop", "none", keepalived_jsaction("/keepalived/stop"));
}
function kasv_stop_save()
{
    // Nothing to do: an empty answer lets the confirmation run the stop.
}

// ── Model ─────────────────────────────────────────────────────────────────

function kasv_model(): array
{
    $m = array("nodes_ok" => false, "groups" => array(), "debug" => 0, "primaries" => array(), "last_sync" => 0,
        "conflicts" => array(), "readiness" => "empty", "fault" => "", "admin_stats" => false);
    $users = new usersMenus();
    $m["admin_stats"] = (bool)$users->AsSquidAdministrator;

    $params = json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/keepalived/parameters"), true);
    $m["debug"] = is_array($params) ? intval($params["log_detail"] ?? 0) : 0;

    $r = json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/keepalived/nodes"), true);
    if (is_array($r) && !empty($r["Status"])) {
        $m["nodes_ok"] = true;
    }
    $nodes = (is_array($r) && is_array($r["nodes"] ?? null)) ? $r["nodes"] : array();

    $states = array();
    foreach ($nodes as $node) {
        if (!is_array($node)) {
            continue;
        }
        $id = intval($node["ID"] ?? 0);
        $KA = json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/keepalived/instance?primaryid=$id"), true);
        $KA = (is_array($KA) && is_array($KA["instance"] ?? null)) ? $KA["instance"] : array();
        $g = array(
            "id" => $id,
            "name" => (string)($node["primary_node_name"] ?? ""),
            "iface" => (string)($node["interface"] ?? ""),
            "vrid" => intval($node["virtual_router_id"] ?? 0),
            "priority" => intval($node["priority"] ?? 0),
            "nopreempt" => intval($node["nopreempt"] ?? 0),
            "state" => strtoupper(trim((string)($node["service_state"] ?? ""))),
            "fault" => trim((string)($node["fault_reason"] ?? "")),
            "foreign" => is_array($node["foreign_speakers"] ?? null) ? $node["foreign_speakers"] : array(),
            "services" => is_array($KA["services"] ?? null) ? $KA["services"] : array(),
            "vips" => is_array($KA["virtual_ips"] ?? null) ? $KA["virtual_ips"] : array(),
            "tracks" => is_array($KA["track_interfaces"] ?? null) ? $KA["track_interfaces"] : array(),
            "checks" => kasv_checks($node),
            "interval" => intval($node["interval"] ?? 0),
            "fall" => intval($node["fall"] ?? 0),
            "rise" => intval($node["rise"] ?? 0),
        );
        // A state that is not a VRRP state is a health-check failure the secondary reported
        // in its place (keepalived/listener.go): treat it as a fault and show the text.
        if (!in_array($g["state"], array("", "INIT", "BACKUP", "MASTER", "FAULT", "STOP", "UP"))) {
            if ($g["fault"] == "") {
                $g["fault"] = trim((string)($node["service_state"] ?? ""));
            }
            $g["state"] = "FAULT";
        }
        $m["groups"][] = $g;
        $states[] = $g["state"];
        if ($g["state"] == "FAULT" && $m["fault"] == "" && $g["fault"] != "") {
            $m["fault"] = $g["fault"];
        }
        $ip = trim((string)($node["primaryNodeIP"] ?? ""));
        $port = intval($node["primaryNodePort"] ?? 0);
        if ($ip != "") {
            $m["primaries"][$ip] = $ip . ($port > 0 ? ":$port" : "");
        }
        $ls = intval($node["last_sync"] ?? 0);
        if ($ls > $m["last_sync"]) {
            $m["last_sync"] = $ls;
        }
        foreach ($g["foreign"] as $f) {
            if (is_array($f)) {
                $m["conflicts"][] = array("vrid" => $g["vrid"], "ip" => (string)($f["ip"] ?? ""),
                    "count" => intval($f["count"] ?? 0), "last_seen" => intval($f["last_seen"] ?? 0),
                    "line" => (string)($f["last_line"] ?? ""));
            }
        }
    }

    if (count($m["groups"]) > 0) {
        if (in_array("FAULT", $states)) {
            $m["readiness"] = "fault";
        } elseif (in_array("MASTER", $states)) {
            $m["readiness"] = "active";
        } elseif (count(array_diff($states, array("BACKUP", "UP"))) == 0) {
            $m["readiness"] = "ready";
        } elseif (in_array("INIT", $states)) {
            $m["readiness"] = "starting";
        } else {
            $m["readiness"] = "stopped";
        }
    }
    return $m;
}

// The health checks go-failover-checker runs for one instance, with articarest's defaults.
function kasv_checks(array $n): array
{
    $c = array();
    $disk = intval($n["disklimit"] ?? 0);
    $ram = intval($n["ramlimit"] ?? 0);
    $load = intval($n["loadlimit"] ?? 0);
    $web = trim((string)($n["testDom"] ?? ""));
    $dns = trim((string)($n["testDomDNS"] ?? ""));
    $c[] = array("on" => intval($n["checkDisk"] ?? 0) == 1, "tok" => "kasv_chk_disk", "v" => strval($disk > 0 ? $disk : 95));
    $c[] = array("on" => intval($n["checkRam"] ?? 0) == 1, "tok" => "kasv_chk_ram", "v" => strval($ram > 0 ? $ram : 95));
    $c[] = array("on" => intval($n["checkLoad"] ?? 0) == 1, "tok" => "kasv_chk_load", "v" => strval($load > 0 ? $load : 4));
    $c[] = array("on" => intval($n["enableProxyCurl"] ?? 0) == 1, "tok" => "kasv_chk_web", "v" => ($web != "" ? $web : "http://articatech.com"));
    $c[] = array("on" => intval($n["enableDnsResolver"] ?? 0) == 1, "tok" => "kasv_chk_dns", "v" => ($dns != "" ? $dns : "cloudflare.com"));
    return $c;
}

function kasv_h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8");
}

// Token resolved first, then its placeholders replaced.
function kasv_tr(string $tok, array $repl = array()): string
{
    $tpl = new template_admin();
    $t = $tpl->_ENGINE_parse_body("{" . $tok . "}");
    foreach ($repl as $k => $v) {
        $t = str_replace($k, $v, $t);
    }
    return $t;
}

function kasv_ago(int $ts): string
{
    if ($ts <= 0) {
        return "&mdash;";
    }
    $d = max(1, time() - $ts);
    if ($d < 60) {
        return "{kasv_ago_now}";
    }
    if ($d < 3600) {
        return kasv_tr("kasv_ago_min", array("%s" => strval(intval($d / 60))));
    }
    if ($d < 86400) {
        return kasv_tr("kasv_ago_hour", array("%s" => strval(intval($d / 3600))));
    }
    return kasv_tr("kasv_ago_day", array("%s" => strval(intval($d / 86400))));
}

// ── Hosts ─────────────────────────────────────────────────────────────────

function main()
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $m = kasv_model();
    $html = array();
    $html[] = "<div class='kasv'>";
    $html[] = kasv_banner();
    $html[] = "<div id='kasv-top' class='kasv-stack'>" . kasv_top_html($m) . "</div>";
    $html[] = "<div class='kasv-cols'>";
    $html[] = "<div id='keepalived-status'>" . keepalived_status_html() . "</div>";
    $html[] = "<div class='kasv-stack'><div id='kasv-right' class='kasv-stack'>" . kasv_right_html($m) . "</div>" . kasv_actions() . "</div>";
    $html[] = "</div></div>";

    // Every 30 s: the left status as before, and the two hosts of this page.
    $html[] = "<script>NoSpinner();\n" . $tpl->RefreshInterval_js("keepalived-status", $page, "keepalived-status=yes", 30) . "</script>";
    echo awc_table_style() . kasv_style() . $tpl->_ENGINE_parse_body(@implode("\n", $html)) . kasv_js($page);
}

function kasv_top()
{
    $tpl = new template_admin();
    echo $tpl->_ENGINE_parse_body(kasv_top_html(kasv_model()));
}
function kasv_right()
{
    $tpl = new template_admin();
    echo $tpl->_ENGINE_parse_body(kasv_right_html(kasv_model()));
}

function kasv_top_html(array $m): string
{
    $h = kasv_readiness($m);
    if (count($m["conflicts"]) > 0) {
        $h .= kasv_conflict($m);
    }
    return $h;
}

function kasv_right_html(array $m): string
{
    if (!$m["nodes_ok"]) {
        return kasv_unreachable();
    }
    if (count($m["groups"]) == 0) {
        return kasv_empty();
    }
    $h = "<div class='kasv-sec-head'><div><h3 class='kasv-sec-t'>{kasv_groups_title}</h3><p class='kasv-muted'>{kasv_groups_text}</p></div>"
        . "<span class='kasv-count'>" . kasv_tr("kasv_groups_count", array("%s" => strval(count($m["groups"])))) . "</span></div>";
    foreach ($m["groups"] as $g) {
        $h .= kasv_group($g, $m);
    }
    return $h;
}

// ── Widgets ───────────────────────────────────────────────────────────────

function kasv_banner(): string
{
    $chips = array(array("fas fa-rotate", "{kasv_chip_copy}"), array("fas fa-stethoscope", "{kasv_chip_health}"),
        array("fas fa-arrow-right-arrow-left", "{kasv_chip_auto}"));
    $c = "";
    foreach ($chips as $ch) {
        $c .= "<span class='kasv-chip'><i class='{$ch[0]}' aria-hidden='true'></i>{$ch[1]}</span>";
    }
    return "<section class='kasv-banner'><div class='kasv-banner-ico' aria-hidden='true'><i class='fas fa-shield-heart'></i></div>"
        . "<div class='kasv-banner-txt'><h2 class='kasv-banner-t'>{kasv_banner_title}</h2><p class='kasv-banner-p'>{kasv_banner_text}</p>"
        . "<div class='kasv-chips'>$c</div></div></section>";
}

function kasv_readiness(array $m): string
{
    $map = array(
        "ready" => array("kasv-r-ok", "fas fa-circle-check", "{kasv_r_ready}", "kasv_r_ready_text"),
        "active" => array("kasv-r-active", "fas fa-crown", "{kasv_r_active}", "kasv_r_active_text"),
        "fault" => array("kasv-r-bad", "fas fa-triangle-exclamation", "{kasv_r_fault}", "kasv_r_fault_text"),
        "starting" => array("kasv-r-idle", "fas fa-hourglass-half", "{kasv_r_starting}", "kasv_r_starting_text"),
        "stopped" => array("kasv-r-idle", "fas fa-circle-stop", "{kasv_r_stopped}", "kasv_r_stopped_text"),
        "empty" => array("kasv-r-idle", "fas fa-link", "{kasv_r_empty}", "kasv_r_empty_text"),
    );
    $r = $map[$m["readiness"]];
    $text = kasv_tr($r[3], array("%s" => strval(count($m["groups"]))));
    if ($m["readiness"] == "fault" && $m["fault"] != "") {
        $text .= " <span class='kasv-reason'>" . kasv_h($m["fault"]) . "</span>";
    }
    if (!$m["nodes_ok"]) {
        $r = array("kasv-r-bad", "fas fa-plug-circle-xmark", "{kasv_r_unknown}", "");
        $text = "{kasv_r_unknown_text}";
    }

    $prim = count($m["primaries"]) > 0 ? implode("<br>", array_map("kasv_h", $m["primaries"])) : "&mdash;";
    $sync = kasv_ago($m["last_sync"]);
    $syncCls = "";
    $syncIco = "fas fa-clock";
    $syncNote = "";
    if ($m["last_sync"] > 0 && (time() - $m["last_sync"]) > 86400) {
        $syncCls = " kasv-warn";
        $syncIco = "fas fa-clock-rotate-left";
        $syncNote = "<span class='kasv-help kasv-warn'>{kasv_sync_old}</span>";
    }
    $tpl = new template_admin();
    $exact = $m["last_sync"] > 0 ? kasv_h($tpl->time_to_date($m["last_sync"], true)) : "";
    $dbg = $m["debug"] == 1 ? "<span class='kasv-pill kasv-pill-warn'>{kasv_on}</span>" : "<span class='kasv-pill'>{kasv_off}</span>";

    return "<section class='kasv-card kasv-ready $r[0]'><div class='kasv-ready-head'><span class='kasv-ready-ico' aria-hidden='true'><i class='$r[1]'></i></span>"
        . "<div><h3 class='kasv-ready-t'>$r[2]</h3><p class='kasv-ready-p'>$text</p></div></div>"
        . "<div class='kasv-facts'>"
        . "<div><span class='kasv-fact-l'>{kasv_primary}</span><span class='kasv-fact-v kasv-mono'>$prim</span></div>"
        . "<div><span class='kasv-fact-l'>{kasv_last_copy}</span><span class='kasv-fact-v$syncCls'>" . ($m["last_sync"] > 0 ? "<i class='$syncIco' aria-hidden='true'></i> " : "") . "<span title='$exact'>$sync</span></span>$syncNote</div>"
        . "<div><span class='kasv-fact-l'>{kasv_logging} $dbg</span><span class='kasv-help'>{kasv_logging_help}</span></div>"
        . "</div></section>";
}

function kasv_conflict(array $m): string
{
    $tpl = new template_admin();
    $rows = "";
    foreach ($m["conflicts"] as $c) {
        $when = $c["last_seen"] > 0 ? kasv_h($tpl->time_to_date($c["last_seen"], true)) : "&mdash;";
        $rows .= "<li>" . kasv_tr("kasv_conflict_row", array("%g" => strval($c["vrid"]),
                "%i" => "<span class='kasv-mono' title='" . kasv_h($c["line"]) . "'>" . kasv_h($c["ip"]) . "</span>",
                "%n" => strval($c["count"]), "%t" => $when)) . "</li>";
    }
    return "<section class='kasv-card kasv-alert' role='alert'><span class='kasv-alert-ico' aria-hidden='true'><i class='fas fa-triangle-exclamation'></i></span>"
        . "<div><h3 class='kasv-card-t'>{kasv_conflict_title}</h3><ul class='kasv-conflicts'>$rows</ul><p class='kasv-muted'>{kasv_conflict_text}</p></div></section>";
}

function kasv_state_pill(string $state): string
{
    $map = array(
        "BACKUP" => array("kasv-st-ok", "fas fa-circle-check", "{kasv_st_backup}"),
        "UP" => array("kasv-st-ok", "fas fa-circle-check", "{kasv_st_backup}"),
        "MASTER" => array("kasv-st-active", "fas fa-crown", "{kasv_st_master}"),
        "FAULT" => array("kasv-st-bad", "fas fa-triangle-exclamation", "{kasv_st_fault}"),
        "STOP" => array("kasv-st-off", "fas fa-circle-stop", "{kasv_st_stop}"),
        "INIT" => array("kasv-st-off", "fas fa-hourglass-half", "{kasv_st_init}"),
        "" => array("kasv-st-off", "fas fa-circle-question", "{kasv_st_unknown}"),
    );
    $s = isset($map[$state]) ? $map[$state] : $map[""];
    $raw = $state != "" ? "<span class='kasv-raw'>(" . kasv_h($state) . ")</span>" : "";
    return "<span class='kasv-st $s[0]'><i class='$s[1]' aria-hidden='true'></i>$s[2]</span>$raw";
}

function kasv_group(array $g, array $m): string
{
    $stats = "";
    if ($m["admin_stats"]) {
        $js = "Loadjs('fw.keepalived.stats.php?stats-js={$g["id"]}&name=" . urlencode($g["name"]) . "')";
        $stats = "<button type='button' class='kasv-btn-q' onclick=\"$js\"><i class='fas fa-chart-line' aria-hidden='true'></i>{kasv_statistics}</button>";
    }
    $name = $g["name"] != "" ? kasv_h($g["name"]) : "VRID " . $g["vrid"];
    $h = "<article class='kasv-card kasv-group'><div class='kasv-group-head'><div class='kasv-group-name'><h4>$name</h4>" . kasv_state_pill($g["state"]) . "</div>$stats</div>";
    if ($g["state"] == "FAULT" && $g["fault"] != "") {
        $h .= "<div class='kasv-fault'><i class='fas fa-circle-exclamation' aria-hidden='true'></i><span><b>{kasv_why_fault}</b> " . kasv_h($g["fault"]) . "</span></div>";
    }
    $handback = $g["nopreempt"] == 1 ? "{kasv_no}" : "{kasv_yes}";
    $h .= "<div class='kasv-gfacts'>"
        . "<div><span class='kasv-fact-l'>{kasv_iface}</span><span class='kasv-fact-v kasv-mono'>" . ($g["iface"] != "" ? kasv_h($g["iface"]) : "&mdash;") . "</span></div>"
        . "<div><span class='kasv-fact-l'>{kasv_vrid}</span><span class='kasv-fact-v'>" . $g["vrid"] . " <small class='kasv-raw'>VRID</small></span></div>"
        . "<div><span class='kasv-fact-l'>{kasv_priority}</span><span class='kasv-fact-v'>" . $g["priority"] . "</span><span class='kasv-help'>{kasv_priority_help}</span></div>"
        . "<div><span class='kasv-fact-l'>{kasv_handback}</span><span class='kasv-fact-v'>$handback</span><span class='kasv-help'>{kasv_handback_help}</span></div>"
        . "</div>";
    $h .= kasv_group_services($g);
    $h .= kasv_group_vips($g);
    $h .= kasv_group_checks($g);
    return $h . "</article>";
}

function kasv_group_services(array $g): string
{
    $tags = "";
    foreach ($g["services"] as $s) {
        if (!is_array($s)) {
            continue;
        }
        $off = intval($s["enable"] ?? 1) != 1;
        $tags .= "<span class='kasv-tag" . ($off ? " kasv-tag-off" : "") . "'><i class='fas fa-shield' aria-hidden='true'></i>" . kasv_h($s["service"] ?? "")
            . ($off ? " <small>{kasv_disabled}</small>" : "") . "</span>";
    }
    if ($tags == "") {
        $tags = "<span class='kasv-muted'>{kasv_no_service}</span>";
    }
    return "<div class='kasv-block'><div class='kasv-block-head'><span class='kasv-block-t'>{kasv_protects}</span><span class='kasv-help'>{kasv_protects_help}</span></div><div class='kasv-tags'>$tags</div></div>";
}

function kasv_group_vips(array $g): string
{
    $tags = "";
    foreach ($g["vips"] as $v) {
        if (!is_array($v) || empty($v["virtual_ip"])) {
            continue;
        }
        $ip = kasv_h($v["virtual_ip"]);
        $mask = trim((string)($v["netmask"] ?? ""));
        if ($mask != "") {
            $ip .= "/" . kasv_h($mask);
        }
        $dev = trim((string)($v["dev"] ?? ""));
        if ($dev == "") {
            $dev = $g["iface"];
        }
        $off = intval($v["enable"] ?? 1) != 1;
        $tags .= "<span class='kasv-vip" . ($off ? " kasv-tag-off" : "") . "'><span class='kasv-dot' aria-hidden='true'></span><span class='kasv-mono'>$ip</span>"
            . ($dev != "" ? " &middot; <span class='kasv-mono'>" . kasv_h($dev) . "</span>" : "") . ($off ? " <small>{kasv_disabled}</small>" : "") . "</span>";
    }
    if ($tags == "") {
        $tags = "<span class='kasv-muted'>{kasv_no_vip}</span>";
    }
    return "<div class='kasv-block'><div class='kasv-block-head'><span class='kasv-block-t'>{kasv_shared}</span><span class='kasv-help'>{kasv_shared_help}</span></div><div class='kasv-tags'>$tags</div></div>";
}

function kasv_group_checks(array $g): string
{
    $items = "";
    $any = false;
    foreach ($g["checks"] as $c) {
        if (!$c["on"]) {
            continue;
        }
        $any = true;
        $items .= "<span class='kasv-check'><span class='kasv-dot' aria-hidden='true'></span>" . kasv_tr($c["tok"], array("%s" => kasv_h($c["v"]))) . "</span>";
    }
    if (!$any) {
        $items = "<span class='kasv-muted'>{kasv_only_services}</span>";
    }
    $rhythm = array();
    if ($g["interval"] > 0) {
        $rhythm[] = kasv_tr("kasv_every", array("%s" => strval($g["interval"])));
    }
    if ($g["fall"] > 0) {
        $rhythm[] = kasv_tr("kasv_fall", array("%s" => strval($g["fall"])));
    }
    if ($g["rise"] > 0) {
        $rhythm[] = kasv_tr("kasv_rise", array("%s" => strval($g["rise"])));
    }
    $tracks = array();
    foreach ($g["tracks"] as $t) {
        if (is_array($t) && intval($t["enable"] ?? 1) == 1 && trim((string)($t["interface"] ?? "")) != "") {
            $tracks[] = kasv_h($t["interface"]);
        }
    }
    $foot = "";
    if (count($rhythm) > 0) {
        $foot .= "<span><i class='fas fa-rotate' aria-hidden='true'></i> " . implode(" &middot; ", $rhythm) . "</span>";
    }
    if (count($tracks) > 0) {
        $foot .= "<span><i class='fas fa-ethernet' aria-hidden='true'></i> {kasv_watched_if} <span class='kasv-mono'>" . implode(", ", $tracks) . "</span></span>";
    }
    return "<div class='kasv-checks'><div class='kasv-block-head'><span class='kasv-block-t'><i class='fas fa-stethoscope' aria-hidden='true'></i> {kasv_checks_title}</span><span class='kasv-help'>{kasv_checks_help}</span></div>"
        . "<div class='kasv-tags'>$items</div>" . ($foot != "" ? "<div class='kasv-checks-foot'>$foot</div>" : "") . "</div>";
}

function kasv_empty(): string
{
    return "<section class='kasv-card kasv-empty'><div class='kasv-empty-ico' aria-hidden='true'><i class='fas fa-link'></i></div>"
        . "<h3 class='kasv-card-t'>{kasv_empty_title}</h3><p class='kasv-muted'>{kasv_empty_text}</p>"
        . "<button type='button' class='kasv-btn-q' onclick=\"s_PopUp('https://wiki.articatech.com/en/artica-failover-service','1024','800')\"><i class='fas fa-headset' aria-hidden='true'></i>{online_help}</button></section>";
}

function kasv_unreachable(): string
{
    return "<section class='kasv-card kasv-alert kasv-alert-bad' role='alert'><span class='kasv-alert-ico' aria-hidden='true'><i class='fas fa-plug-circle-xmark'></i></span>"
        . "<div><h3 class='kasv-card-t'>{kasv_unreach_title}</h3><p class='kasv-muted'>{kasv_unreach_text}</p></div></section>";
}

function kasv_actions(): string
{
    $page = CurrentPageName();
    $btns = array(
        array("Loadjs('$page?action-js=restart')", "fas fa-rotate", "{restart}", "{kasv_restart_help}", ""),
        array("Loadjs('$page?action-js=reload')", "fas fa-bolt", "{kasv_reload}", "{kasv_reload_help}", ""),
        array("Loadjs('$page?action-js=start')", "fas fa-circle-play", "{start_service}", "{kasv_start_help}", ""),
        array("Loadjs('$page?stop-js=yes')", "fas fa-circle-stop", "{stop_service}", "{kasv_stop_help}", " kasv-act-stop"),
    );
    $b = "";
    foreach ($btns as $x) {
        $b .= "<button type='button' class='kasv-act$x[4]' onclick=\"$x[0]\"><span class='kasv-act-t'><i class='$x[1]' aria-hidden='true'></i>$x[2]</span><span class='kasv-act-s'>$x[3]</span></button>";
    }
    return "<section class='kasv-card kasv-actions'><h3 class='kasv-card-t'>{kasv_actions_title}</h3><p class='kasv-muted'>{kasv_actions_text}</p>"
        . "<div class='kasv-acts'>$b</div><div id='kasv-last'>" . kasv_last_operation() . "</div></section>";
}

// The last start/stop/restart/reload/reconfigure result, read from the progress file articarest
// writes (notifs.BuildProgress): a serialized {POURC, TEXT}.
function kasv_last_operation(): string
{
    $path = PROGRESS_DIR . "/keepalived.progress";
    if (!is_file($path)) {
        return "";
    }
    $a = @unserialize((string)@file_get_contents($path), array("allowed_classes" => false));
    if (!is_array($a) || !isset($a["POURC"])) {
        return "";
    }
    $pourc = intval($a["POURC"]);
    $cls = "kasv-last-run";
    $ico = "fas fa-spinner";
    $state = "{kasv_op_running}";
    if ($pourc == 100) {
        $cls = "kasv-last-ok";
        $ico = "fas fa-circle-check";
        $state = "{kasv_op_ok}";
    }
    if ($pourc > 100) {
        $cls = "kasv-last-err";
        $ico = "fas fa-circle-exclamation";
        $state = "{kasv_op_failed}";
    }
    $tpl = new template_admin();
    $when = kasv_h($tpl->time_to_date(filemtime($path), true));
    return "<div class='kasv-last $cls'><span class='kasv-last-l'>{lbst_last_operation}</span><i class='$ico' aria-hidden='true'></i>"
        . "<span class='kasv-last-m'>" . kasv_h($a["TEXT"] ?? "") . "</span><span class='kasv-last-t'>$when &middot; $state</span></div>";
}

// ── JS / CSS ──────────────────────────────────────────────────────────────

function kasv_js(string $page): string
{
    return "<script>
function KasvModalOpen(){
    if(document.querySelector('.modal.in, .modal.show')){ return true; }
    var sw=document.querySelector('.sweet-alert.visible, .sweet-alert.showSweetAlert');
    return !!(sw && sw.offsetParent!==null);
}
function KasvReload(force){
    if(!document.getElementById('kasv-right')){ return; }
    if(!force && (document.hidden || KasvModalOpen())){ return; }
    LoadAjaxSilent('kasv-top','$page?kasv-top=yes');
    LoadAjaxSilent('kasv-right','$page?kasv-right=yes');
}
(function(){
    if(window.KasvTimer){ clearInterval(window.KasvTimer); }
    window.KasvTimer=setInterval(function(){
        if(!document.getElementById('kasv-right')){ clearInterval(window.KasvTimer); window.KasvTimer=null; return; }
        KasvReload(false);
    },30000);
})();
</script>";
}

function kasv_style(): string
{
    $accent = "#005447";
    $soft = "#e6eeed";
    if (function_exists("brand_ui_accents") && function_exists("brand_palette")) {
        $a = brand_ui_accents(brand_palette());
        if (!empty($a["accent"])) {
            $accent = $a["accent"];
        }
        if (!empty($a["accent_soft"])) {
            $soft = $a["accent_soft"];
        }
    }
    return "<style>
.kasv{display:flex;flex-direction:column;gap:20px;color:#0f172a;font-size:14px}
.kasv *{box-sizing:border-box}
.kasv h2,.kasv h3,.kasv h4,.kasv p,.kasv ul{margin:0}
.kasv-stack{display:flex;flex-direction:column;gap:20px;min-width:0}
.kasv-stack:empty{display:none}
.kasv-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.kasv-card-t{font-size:16px;font-weight:600}
.kasv-muted{color:#475569;font-size:13px;line-height:1.5;margin-top:4px!important}
.kasv-help{display:block;color:#64748b;font-size:13px;line-height:1.4;margin-top:2px}
.kasv-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px}
.kasv-raw{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;color:#64748b;font-weight:400}
.kasv-banner{display:flex;gap:18px;align-items:flex-start;background:$soft;border:1px solid #bfd4d1;border-radius:16px;padding:24px}
.kasv-banner-ico{flex:0 0 48px;height:48px;border-radius:50%;background:$accent;color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px}
.kasv-banner-txt{flex:1;min-width:0}
.kasv-banner-t{font-size:20px;font-weight:600;line-height:1.3}
.kasv-banner-p{font-size:14px;line-height:1.55;color:#334155;margin-top:6px!important;max-width:820px}
.kasv-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
.kasv-chip{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#334155;background:#fff;border:1px solid #bfd4d1;border-radius:999px;padding:4px 12px}
.kasv-chip i,.kasv-chip svg{color:$accent}
.kasv-ready{padding:20px 24px;border-left:4px solid #64748b}
.kasv-ready-head{display:flex;gap:14px;align-items:flex-start}
.kasv-ready-ico{flex:0 0 40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;background:#f1f5f9;color:#64748b}
.kasv-ready-t{font-size:18px;font-weight:600}
.kasv-ready-p{font-size:14px;color:#334155;margin-top:4px!important;line-height:1.5}
.kasv-r-ok{border-left-color:#15803d}.kasv-r-ok .kasv-ready-ico{background:#dcfce7;color:#15803d}.kasv-r-ok .kasv-ready-t{color:#166534}
.kasv-r-active{border-left-color:$accent}.kasv-r-active .kasv-ready-ico{background:$soft;color:$accent}.kasv-r-active .kasv-ready-t{color:$accent}
.kasv-r-bad{border-left-color:#dc2626}.kasv-r-bad .kasv-ready-ico{background:#fee2e2;color:#dc2626}.kasv-r-bad .kasv-ready-t{color:#991b1b}
.kasv-reason{display:inline-block;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;color:#7f1d1d;background:#fef2f2;border-radius:6px;padding:1px 8px}
.kasv-facts{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-top:16px;padding-top:16px;border-top:1px solid #f1f5f9}
.kasv-fact-l{display:flex;align-items:center;gap:8px;font-size:13px;color:#64748b}
.kasv-fact-v{display:block;font-size:15px;font-weight:600;margin-top:2px}
.kasv-fact-v i,.kasv-fact-v svg{color:#15803d}
.kasv-warn,.kasv-warn i,.kasv-warn svg{color:#b45309!important}
.kasv-pill{display:inline-block;font-size:13px;font-weight:600;border-radius:999px;padding:0 9px;background:#f1f5f9;color:#475569}
.kasv-pill-warn{background:#fef3c7;color:#92400e}
.kasv-alert{display:flex;gap:14px;align-items:flex-start;padding:18px 24px;background:#fffbeb;border-color:#fde68a}
.kasv-alert-ico{flex:0 0 36px;height:36px;border-radius:10px;background:#fef3c7;color:#d97706;display:flex;align-items:center;justify-content:center;font-size:16px}
.kasv-alert .kasv-card-t{color:#92400e}
.kasv-alert-bad{background:#fef2f2;border-color:#fecaca}.kasv-alert-bad .kasv-alert-ico{background:#fee2e2;color:#dc2626}.kasv-alert-bad .kasv-card-t{color:#991b1b}
.kasv-conflicts{padding-left:18px!important;margin-top:6px!important;font-size:14px;color:#78350f;line-height:1.6}
.kasv-cols{display:grid;grid-template-columns:350px minmax(0,1fr);gap:24px;align-items:start}
.kasv-sec-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.kasv-sec-t{font-size:17px;font-weight:600}
.kasv-count{font-size:13px;color:$accent;background:$soft;border:1px solid #bfd4d1;border-radius:999px;padding:2px 12px;white-space:nowrap}
.kasv-group{padding:20px 24px;display:flex;flex-direction:column;gap:16px}
.kasv-group-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.kasv-group-name{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.kasv-group-name h4{font-size:16px;font-weight:600}
.kasv-st{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;border-radius:999px;padding:2px 10px}
.kasv-st-ok{background:#dcfce7;color:#166534}
.kasv-st-active{background:$soft;color:$accent;border:1px solid #bfd4d1}
.kasv-st-bad{background:#fee2e2;color:#991b1b}
.kasv-st-off{background:#f1f5f9;color:#475569}
.kasv-fault{display:flex;gap:8px;align-items:flex-start;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 12px;font-size:13px;color:#7f1d1d}
.kasv-gfacts{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;padding:14px 0;border-top:1px solid #f1f5f9;border-bottom:1px solid #f1f5f9}
.kasv-block-head{display:flex;justify-content:space-between;align-items:baseline;gap:12px;flex-wrap:wrap;margin-bottom:8px}
.kasv-block-head .kasv-help{margin:0}
.kasv-block-t{font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#334155}
.kasv-tags{display:flex;flex-wrap:wrap;gap:8px}
.kasv-tag,.kasv-vip,.kasv-check{display:inline-flex;align-items:center;gap:6px;font-size:13px;border-radius:8px;padding:4px 10px;background:#f1f5f9;color:#0f172a;border:1px solid #e2e8f0}
.kasv-tag i,.kasv-tag svg{color:$accent}
.kasv-vip{background:$soft;border-color:#bfd4d1;border-radius:999px}
.kasv-check{background:#fff}
.kasv-dot{width:7px;height:7px;border-radius:50%;background:#15803d;display:inline-block}
.kasv-tag-off{background:#f8fafc;color:#64748b;text-decoration:line-through}
.kasv-tag small,.kasv-vip small{font-size:13px;text-decoration:none}
.kasv-tag-off .kasv-dot{background:#94a3b8}
.kasv-checks{background:#f8fafc;border:1px solid #eef2f6;border-radius:10px;padding:14px 16px}
.kasv-checks .kasv-block-t i,.kasv-checks .kasv-block-t svg{color:$accent}
.kasv-checks-foot{display:flex;flex-wrap:wrap;gap:6px 20px;margin-top:10px;padding-top:10px;border-top:1px solid #e2e8f0;font-size:13px;color:#475569}
.kasv-btn-q{display:inline-flex;align-items:center;gap:8px;background:#fff;color:#0f172a;border:1px solid #cbd5e1;border-radius:8px;padding:6px 14px;font-size:13px;font-weight:600;cursor:pointer}
.kasv-btn-q:hover{border-color:$accent;color:$accent}
.kasv-empty{text-align:center;padding:36px 24px;display:flex;flex-direction:column;align-items:center;gap:6px}
.kasv-empty-ico{width:60px;height:60px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:8px}
.kasv-empty .kasv-muted{max-width:520px}
.kasv-empty .kasv-btn-q{margin-top:10px}
.kasv-actions{padding:20px 24px}
.kasv-acts{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:14px}
.kasv-act{display:flex;flex-direction:column;align-items:flex-start;gap:4px;text-align:left;background:#fff;border:1px solid #cbd5e1;border-radius:10px;padding:12px 14px;cursor:pointer}
.kasv-act:hover{border-color:$accent}
.kasv-act-t{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:600;color:#0f172a}
.kasv-act-t i,.kasv-act-t svg{color:$accent}
.kasv-act-s{font-size:13px;color:#64748b;line-height:1.35}
.kasv-act-stop .kasv-act-t,.kasv-act-stop .kasv-act-t i,.kasv-act-stop .kasv-act-t svg{color:#b91c1c}
.kasv-act-stop:hover{border-color:#dc2626;background:#fef2f2}
.kasv-btn-q:focus-visible,.kasv-act:focus-visible{outline:2px solid $accent;outline-offset:2px}
.kasv-last{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:14px;padding:10px 14px;border-radius:10px;font-size:13px;background:#f8fafc;border:1px solid #e2e8f0}
.kasv-last-l{font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#475569}
.kasv-last-m{flex:1;min-width:0;font-weight:500;word-break:break-word}
.kasv-last-t{color:#64748b;white-space:nowrap}
.kasv-last-ok i,.kasv-last-ok svg{color:#15803d}
.kasv-last-err{background:#fef2f2;border-color:#fecaca}.kasv-last-err i,.kasv-last-err svg{color:#dc2626}
.kasv-last-run i,.kasv-last-run svg{color:#64748b}
@media(max-width:1200px){.kasv-cols{grid-template-columns:minmax(0,1fr)}.kasv-gfacts{grid-template-columns:repeat(2,minmax(0,1fr))}.kasv-acts{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (prefers-reduced-motion:no-preference){.kasv-ready-ico{animation:kasvIn .2s ease-out}@keyframes kasvIn{from{opacity:0}to{opacity:1}}.kasv-act,.kasv-btn-q{transition:border-color .15s,background .15s}}
</style>";
}
