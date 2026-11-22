<?php
// Failover (keepalived) parameters, laid out like fw.haproxy.status.php:
// feature cards, service actions, last operation, service status and a
// parameters card edited in a dialog. Everything goes through articarest.
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}
$users = new usersMenus();
if (!$users->AsVPNManager) {
    exit();
}
$secondary_nodeIsenable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));

if ($secondary_nodeIsenable == 1) {
    echo "Failover secondary_node is installed";
    die();
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
if (isset($_GET["flat-config"])) {
    flat_config();
    exit;
}
if (isset($_GET["config-js"])) {
    config_js();
    exit;
}
if (isset($_GET["config-popup"])) {
    config_popup();
    exit;
}
if (isset($_POST["keepalived-global-def"])) {
    keepalived_save_global_def();
    exit;
}
if (isset($_GET["build-progress"])) {
    build_progress();
    exit;
}
page();

// Kept for callers that still load it: reconfigure, then redraw the page.
function build_progress()
{
    echo keepalived_jsaction("/keepalived/reconfigure");
}

// Every action reports into keepalived.progress (articarest), and at 100% the
// whole block is redrawn: status, last operation and parameters.
function keepalived_jsaction($route): string
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    return $tpl->framework_buildjs($route, "keepalived.progress", "keepalived.log",
        "progress-firehol-restart", "LoadAjax('table-loader','$page?main=yes');");
}

function page()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $KEEPALIVED_VERSION = htmlspecialchars((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_VERSION"));
    $about = $tpl->_ENGINE_parse_body("{keepalived_what_is}");
    $about = str_replace("#Keepalived#", "<a href=\"https://www.keepalived.org\" target='_NEW' style='text-decoration:underline;font-weight:bold'>Keepalived</a>", $about);
    $btn = $tpl->button_inline("{online_help}", "s_PopUp('https://wiki.articatech.com/en/artica-failover-service','1024','800')", "fa-solid fa-headset", null, null, "btn-blue");

    $html = "
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_KEEPALIVED} v$KEEPALIVED_VERSION</h1><p>$about</p>$btn</div>
	</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content' style='background:#f8fafc'>
	<div id='table-loader'></div>
	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/failover-parameters');
	LoadAjax('table-loader','$page?main=yes');
	</script>";

    $tpl = new templates();
    if (isset($_GET["main-page"])) {
        $tpl = new template_admin('Artica: Failover Parameters', $html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function kast_style(): string
{
    return "<style>
.kas-acts{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
.kas-act{display:flex;align-items:center;justify-content:center;gap:10px;height:48px;border-radius:12px;font-size:14px;font-weight:600;color:#fff!important;text-decoration:none!important;cursor:pointer;box-shadow:0 1px 2px rgba(15,23,42,.08);transition:background .15s}
.kas-act-teal{background:#0f766e}.kas-act-teal:hover{background:#115e59}
.kas-act-red{background:#e11d48}.kas-act-red:hover{background:#be123c}
.kas-act-amber{background:#d97706}.kas-act-amber:hover{background:#b45309}
.kas-last{margin-top:14px;display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:12px;padding:10px 16px;border-radius:12px}
.kas-last-ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
.kas-last-err{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239}
.kas-last-run{background:#f1f5f9;border:1px solid #e2e8f0;color:#334155}
.kas-last-time{font-size:11px;opacity:.8;white-space:nowrap}
.kas-cols{display:grid;grid-template-columns:350px minmax(0,1fr);gap:24px;margin-top:24px;align-items:start}
.kas-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.kas-card-head{padding-bottom:16px;border-bottom:1px solid #f1f5f9;margin-bottom:6px}
.kas-card-title{font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#0f172a;display:flex;align-items:center;gap:8px}
.kas-card-title i{color:#0d9488}
.kas-card-sub{font-size:12px;color:#64748b;margin-top:3px}
@media(max-width:1100px){.kas-acts{grid-template-columns:repeat(2,minmax(0,1fr))}.kas-cols{grid-template-columns:1fr}}
@media(max-width:600px){.kas-acts{grid-template-columns:1fr}}
</style>";
}

// The last start/stop/restart/reload/reconfigure result, read from the
// progress file articarest writes (notifs.BuildProgress): a serialized
// {POURC, TEXT}.
function kast_last_operation(): string
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
    $text = htmlspecialchars((string)($a["TEXT"] ?? ""));
    $class = "kas-last-run";
    $ico = "fas fa-spinner";
    if ($pourc == 100) {
        $class = "kas-last-ok";
        $ico = "fas fa-circle-check";
    }
    if ($pourc > 100) {
        $class = "kas-last-err";
        $ico = "fas fa-circle-exclamation";
    }
    $tpl = new template_admin();
    $when = $tpl->time_to_date(filemtime($path), true);
    return "<div class='kas-last $class'><span><i class='$ico'></i>&nbsp;&nbsp;<strong>{lbst_last_operation}:</strong> $text</span>" .
        "<span class='kas-last-time'>" . htmlspecialchars($when) . "</span></div>";
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

function main()
{
    $tpl = new template_admin();
    $page = CurrentPageName();

    $jsrestart = keepalived_jsaction("/keepalived/restart");
    $jsreload = keepalived_jsaction("/keepalived/reload");
    $jsstop = keepalived_jsaction("/keepalived/stop");
    $jsstart = keepalived_jsaction("/keepalived/start");

    $html[] = "<div class='kas-acts'>";
    $html[] = "<a class='kas-act kas-act-teal' href=\"javascript:blur();\" OnClick=\"$jsrestart\"><i class='fas fa-rotate'></i> {restart}</a>";
    $html[] = "<a class='kas-act kas-act-teal' href=\"javascript:blur();\" OnClick=\"$jsreload\"><i class='fas fa-bolt' style='color:#fcd34d'></i> {reload}</a>";
    $html[] = "<a class='kas-act kas-act-red' href=\"javascript:blur();\" OnClick=\"$jsstop\"><i class='fas fa-circle-stop'></i> {stop_service}</a>";
    $html[] = "<a class='kas-act kas-act-amber' href=\"javascript:blur();\" OnClick=\"$jsstart\"><i class='fas fa-circle-play'></i> {start_service}</a>";
    $html[] = "</div>";
    $html[] = kast_last_operation();

    $html[] = "<div class='kas-cols'>";
    $html[] = "<div id='keepalived-status'>" . keepalived_status_html() . "</div>";
    $html[] = "<div class='kas-card'><div class='kas-card-head'>" .
        "<div class='kas-card-title'><i class='fas fa-sliders'></i> {parameters}</div>" .
        "</div>" .
        "<div id='keepalived-flat-config'></div></div>";
    $html[] = "</div>";

    // Every 30 s: each pass asks articarest for the status again.
    $html[] = "<script>" . $tpl->RefreshInterval_js("keepalived-status", $page, "keepalived-status=yes", 30) .
        "\nLoadAjaxSilent('keepalived-flat-config','$page?flat-config=yes');</script>";
    echo kast_style() . $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

// The log level comes from articarest (GET /keepalived/parameters).
function keepalived_log_detail(): int
{
    $json = json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/keepalived/parameters"), true);
    return is_array($json) ? intval($json["log_detail"] ?? 0) : 0;
}

function flat_config()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $tpl->table_form_field_js("Loadjs('$page?config-js=yes')", "AsVPNManager");
    $tpl->table_form_field_bool("{debug_mode}", keepalived_log_detail(), ico_bug);
    echo $tpl->table_form_compile();
}

function config_js()
{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $tpl->js_dialog2("{parameters}", "$page?config-popup=yes", 650);
}

function config_popup()
{
    $page = CurrentPageName();
    $tpl = new template_admin();

    $form[] = $tpl->field_hidden("keepalived-global-def", '1');
    $form[] = $tpl->field_checkbox("keepalived_log_detail", "{debug_mode}", keepalived_log_detail(), false, null);

    // On success: close, then follow the reconfigure in the progress bar. At
    // 100% the whole block is redrawn, parameters card included.
    $jsafter = "dialogInstance2.close();" . keepalived_jsaction("/keepalived/reconfigure");

    $html[] = $tpl->form_outside("", $form, "", "{apply}", $jsafter, "AsVPNManager", true);
    echo $tpl->_ENGINE_parse_body($html);
}

function keepalived_save_global_def(): bool
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    // articarest enregistre le niveau et redemarre keepalived (-D est un argument
    // de la ligne de commande, un reload ne l'applique pas).
    $raw = $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/keepalived/parameters/save",
        array("log_detail" => (string)intval($_POST["keepalived_log_detail"])));
    $json = json_decode((string)$raw, true);
    if (!is_array($json) || empty($json["Status"])) {
        $error = is_array($json) ? ($json["Error"] ?? $json["error"] ?? "") : trim((string)$raw);
        echo $tpl->post_error($error == "" ? "articarest: no answer" : $error);
        return false;
    }
    return true;
}
