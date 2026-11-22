<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
if(isset($_GET["verbose"])){
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
}

$users=new usersMenus();
if(!$users->AsProxyMonitor){ exit(); }

if(isset($_GET["kpis"])){       kpis();        exit; }
if(isset($_GET["ts-req"])){     ts_requests(); exit; }
if(isset($_GET["ts-uniq"])){    ts_uniq();     exit; }
if(isset($_GET["top-uas"])){    top_uas();     exit; }
if(isset($_GET["top-ips"])){    top_ips();     exit; }
if(isset($_GET["top-rules"])){  top_rules();   exit; }
if(isset($_GET["start"])){  Start();   exit; }

page();


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $PROXYPAC_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PROXYPAC_VERSION");
    if($PROXYPAC_VERSION<>null){$PROXYPAC_VERSION=" v{$PROXYPAC_VERSION}";}
    $html=$tpl->page_header("{statistics}$PROXYPAC_VERSION",
        "fas fa-chart-area",
        "{wpad_service_explain}",
        "$page?start=yes",
        "proxypac-statistics","progress-proxypac-restart",false,"table-loader-proxypac-stats");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_PROXY_PAC}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

// Whitelist windows the UI exposes; anything else collapses to 24h.
function ppac_window(): string {
    $w = isset($_GET["w"]) ? trim($_GET["w"]) : "24h";
    return in_array($w, ["1h","24h","7d","15d"], true) ? $w : "24h";
}

// Return the PHP date() format string appropriate for the given window.
function ppac_label_fmt(string $window): string {
    switch ($window) {
        case "1h":  return "H:i";
        case "24h": return "H:i";
        case "7d":  return "m-d H:i";
        case "15d": return "m-d";
    }
    return "H:i";
}

function Start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $w=ppac_window();
    $PROXYPAC_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PROXYPAC_VERSION");
    if($PROXYPAC_VERSION<>null){$PROXYPAC_VERSION=" v{$PROXYPAC_VERSION}";}

    $html="
    <div class='row' style='margin-top:10px'>
      <div class='col-lg-3'><div id='ppac-kpi-req'></div></div>
      <div class='col-lg-3'><div id='ppac-kpi-uniq'></div></div>
      <div class='col-lg-3'><div id='ppac-kpi-served'></div></div>
      <div class='col-lg-3'><div id='ppac-kpi-dropped'></div></div>
    </div>

    <div class='row'>
      <div class='col-lg-6'><div class='ibox'>
        <div class='ibox-title'><h5>{requests_over_time}</h5></div>
        <div class='ibox-content'><div id='ppac-ts-req-wrap'>
          <canvas id='ppac-ts-req' height='80'></canvas>
        </div></div>
      </div></div>
      <div class='col-lg-6'><div class='ibox'>
        <div class='ibox-title'><h5>{unique_clients_over_time}</h5></div>
        <div class='ibox-content'><div id='ppac-ts-uniq-wrap'>
          <canvas id='ppac-ts-uniq' height='80'></canvas>
        </div></div>
      </div></div>
    </div>

    <div class='row'>
      <div class='col-lg-6'><div class='ibox'>
        <div class='ibox-title'><h5>{top_browsers}</h5></div>
        <div class='ibox-content'><div id='ppac-top-uas-wrap'>
          <canvas id='ppac-top-uas' height='220'></canvas>
        </div></div>
      </div></div>
      <div class='col-lg-6'><div class='ibox'>
        <div class='ibox-title'><h5>{top_sources}</h5></div>
        <div class='ibox-content'><div id='ppac-top-ips-wrap'>
          <canvas id='ppac-top-ips' height='220'></canvas>
        </div></div>
      </div></div>
    </div>

    <div class='row'>
      <div class='col-lg-12'><div class='ibox'>
        <div class='ibox-title'><h5>{top_rules}</h5></div>
        <div class='ibox-content'><div id='ppac-top-rules-wrap'>
          <canvas id='ppac-top-rules' height='100'></canvas>
        </div></div>
      </div></div>
    </div>";

    // Filtres dans le TINY (skill §2b) : la periode gouverne toute la page, elle vit dans l'en-tete.
    // Un changement recharge le constructeur (?start=yes&w=...) : le TINY revient avec le bon segment actif.
    $fields=array();
    $fields[]=array("TYPE"=>"segment","ID"=>"ppac-window","LABEL"=>"{period}","VALUE"=>$w,
        "OPTIONS"=>array("1h"=>"1h","24h"=>"24h","7d"=>"7d","15d"=>"15d"),"JS"=>"PpacPeriod");
    $topbuttons=array();
    $topbuttons[]=array("PpacRefreshAll()",ico_refresh,"{refresh}");
    $TINY=array();
    $TINY["TITLE"]="{statistics}$PROXYPAC_VERSION";
    $TINY["ICO"]="fas fa-chart-area";
    $TINY["EXPL"]="{wpad_service_explain}";
    $TINY["URL"]="proxypac-statistics";
    $TINY["BUTTONS"]=siem_tiny_filters($fields,$tpl->table_buttons($topbuttons)); // filtres PUIS boutons
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";

    // La periode est FIGEE a l'emission : le script et le TINY sont regeneres par le meme rechargement.
    $script="<script>
    NoSpinner();
    window.ppacCharts = window.ppacCharts || {};
    function PpacPeriod(w){ LoadAjax('table-loader-proxypac-stats','$page?start=yes&w='+encodeURIComponent(w)); }
    function PpacRefreshAll() {
        var w = '$w';
        Loadjs('$page?kpis=yes&w='      + w);
        Loadjs('$page?ts-req=yes&w='    + w);
        Loadjs('$page?ts-uniq=yes&w='   + w);
        Loadjs('$page?top-uas=yes&w='   + w);
        Loadjs('$page?top-ips=yes&w='   + w);
        Loadjs('$page?top-rules=yes&w=' + w);
    }
    PpacRefreshAll();
    $tiny
    </script>";

    // feuille de style hors du parseur (skill §17 regle 1)
    echo siem_tiny_filters_style().$tpl->_ENGINE_parse_body($html).$script;
}

// ============================================================================
// Chart-data handlers (implemented in tasks C3–C7).
// Stubs below keep the dispatcher safe until each handler is filled in.
// ============================================================================

function kpis() {
    header("content-type: application/x-javascript");
    $resp = $GLOBALS["CLASS_SOCKETS"]->REST_API_PROXYPAC("/status");
    $j = @json_decode($resp);
    $down = (!is_object($j) || (isset($j->Status) && $j->Status === false));

    $req     = $down ? "&mdash;" : intval($j->total_requests_24h ?? 0);
    $uniq    = $down ? "&mdash;" : intval($j->unique_ips_24h ?? 0);
    $served  = $down ? "&mdash;" : intval($j->pac_served_24h ?? 0);
    $dropped = $down ? 0         : intval($j->events_dropped ?? 0);

    $tile = function($id, $bg, $icon, $valueHtml, $label) {
        return "document.getElementById('$id').outerHTML = "
             . "\"<div id='$id' class='widget $bg p-md text-center' style='padding:15px;border-radius:4px;color:#fff'>"
             . "<i class='$icon fa-2x'></i>"
             . "<h2 style='color:#fff;margin:8px 0'>" . addslashes($valueHtml) . "</h2>"
             . "<small>" . addslashes($label) . "</small>"
             . "</div>\";";
    };

    $droppedBg = ($dropped > 0) ? "red-bg" : "gray-bg";

    $tpl = new template_admin();
    $js  = [];
    $js[] = $tile("ppac-kpi-req",     "navy-bg",   "fas fa-arrows-alt-h",         $req,     $tpl->_ENGINE_parse_body("{requests}"));
    $js[] = $tile("ppac-kpi-uniq",    "lazur-bg",  "fas fa-user-friends",         $uniq,    $tpl->_ENGINE_parse_body("{unique_clients}"));
    $js[] = $tile("ppac-kpi-served",  "blue-bg",   "fas fa-cogs",                 $served,  $tpl->_ENGINE_parse_body("{pac_served}"));
    $js[] = $tile("ppac-kpi-dropped", $droppedBg,  "fas fa-exclamation-triangle", $dropped, $tpl->_ENGINE_parse_body("{events_dropped}"));

    echo implode("\n", $js);
}
function ts_requests() {
    header("content-type: application/x-javascript");
    $w = ppac_window();
    $resp = $GLOBALS["CLASS_SOCKETS"]->REST_API_PROXYPAC("/timeseries?window=$w&metric=requests");
    ppac_emit_line("ppac-ts-req", "tsReq", $resp, $w, "{requests}");
}
function ts_uniq() {
    header("content-type: application/x-javascript");
    $w = ppac_window();
    $resp = $GLOBALS["CLASS_SOCKETS"]->REST_API_PROXYPAC("/timeseries?window=$w&metric=unique_ips");
    ppac_emit_line("ppac-ts-uniq", "tsUniq", $resp, $w, "{unique_clients}");
}

function ppac_emit_line(string $canvasId, string $chartKey, ?string $resp, string $window, string $labelKey): void {
    $tpl = new template_admin();
    $j = @json_decode($resp);
    $down = (!is_object($j) || (isset($j->Status) && $j->Status === false));
    $points = (is_object($j) && isset($j->points) && is_array($j->points)) ? $j->points : [];
    $wrapId = $canvasId . "-wrap";

    if ($down) {
        $msg = addslashes($tpl->_ENGINE_parse_body("{proxy_pac_daemon_unreachable}"));
        echo "if (window.ppacCharts['$chartKey']) { window.ppacCharts['$chartKey'].destroy(); }\n";
        echo "document.getElementById('$wrapId').innerHTML = "
           . "'<p class=\"text-center text-muted\" style=\"padding:40px\">$msg</p>';\n";
        return;
    }
    if (count($points) === 0) {
        $msg = addslashes($tpl->_ENGINE_parse_body("{no_data_for_this_window}"));
        echo "if (window.ppacCharts['$chartKey']) { window.ppacCharts['$chartKey'].destroy(); }\n";
        echo "document.getElementById('$wrapId').innerHTML = "
           . "'<p class=\"text-center text-muted\" style=\"padding:40px\">$msg</p>';\n";
        return;
    }

    $fmt = ppac_label_fmt($window);
    $labels = [];
    $values = [];
    foreach ($points as $p) {
        $labels[] = date($fmt, strtotime($p->ts));
        $values[] = intval($p->value);
    }
    $labelsJs = json_encode($labels, JSON_UNESCAPED_SLASHES);
    // Plancher PHP 7.3: pas de fonction flechee (elle casse tout le fichier).
    $valuesJs = '[' . implode(',', array_map(function($v){ return sprintf('%d', $v); }, $values)) . ']';
    $title    = addslashes($tpl->_ENGINE_parse_body($labelKey));

    echo "if (window.ppacCharts['$chartKey']) { window.ppacCharts['$chartKey'].destroy(); }\n";
    echo "document.getElementById('$wrapId').innerHTML = "
       . "'<canvas id=\"$canvasId\" height=\"80\"></canvas>';\n";
    echo "(function(){\n";
    echo "  var ctx = document.getElementById('$canvasId').getContext('2d');\n";
    echo "  window.ppacCharts['$chartKey'] = new Chart(ctx, {\n";
    echo "    type: 'line',\n";
    echo "    data: { labels: $labelsJs, datasets: [{ label: '$title', data: $valuesJs, "
       . "borderColor: '#1c84c6', backgroundColor: 'rgba(28,132,198,0.15)', tension: 0.2, fill: true }] },\n";
    echo "    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, "
       . "scales: { y: { beginAtZero: true } } }\n";
    echo "  });\n";
    echo "})();\n";
}

function top_uas() {
    header("content-type: application/x-javascript");
    $w = ppac_window();
    $resp = $GLOBALS["CLASS_SOCKETS"]->REST_API_PROXYPAC("/top-uas?window=$w&limit=10");
    ppac_emit_doughnut("ppac-top-uas", "topUas", $resp);
}

function ppac_emit_doughnut(string $canvasId, string $chartKey, ?string $resp): void {
    $tpl = new template_admin();
    $j = @json_decode($resp);
    $down = (!is_object($j) || (isset($j->Status) && $j->Status === false));
    $rows = (is_object($j) && isset($j->rows) && is_array($j->rows)) ? $j->rows : [];
    $wrapId = $canvasId . "-wrap";

    if ($down || count($rows) === 0) {
        $key = $down ? "{proxy_pac_daemon_unreachable}" : "{no_data_for_this_window}";
        $msg = addslashes($tpl->_ENGINE_parse_body($key));
        echo "if (window.ppacCharts['$chartKey']) { window.ppacCharts['$chartKey'].destroy(); }\n";
        echo "document.getElementById('$wrapId').innerHTML = "
           . "'<p class=\"text-center text-muted\" style=\"padding:40px\">$msg</p>';\n";
        return;
    }

    $labels = [];
    $values = [];
    foreach ($rows as $r) {
        $labels[] = $r->name;
        $values[] = intval($r->count);
    }
    $labelsJs = json_encode($labels, JSON_UNESCAPED_SLASHES);
    // Plancher PHP 7.3: pas de fonction flechee (elle casse tout le fichier).
    $valuesJs = '[' . implode(',', array_map(function($v){ return sprintf('%d', $v); }, $values)) . ']';
    // 7-color palette covers the 7 browser families before we'd repeat.
    $colorsJs = "['#1c84c6','#1ab394','#f8ac59','#ed5565','#23c6c8','#ab7df6','#676a6c']";

    echo "if (window.ppacCharts['$chartKey']) { window.ppacCharts['$chartKey'].destroy(); }\n";
    echo "document.getElementById('$wrapId').innerHTML = "
       . "'<canvas id=\"$canvasId\" height=\"220\"></canvas>';\n";
    echo "(function(){\n";
    echo "  var ctx = document.getElementById('$canvasId').getContext('2d');\n";
    echo "  window.ppacCharts['$chartKey'] = new Chart(ctx, {\n";
    echo "    type: 'doughnut',\n";
    echo "    data: { labels: $labelsJs, datasets: [{ data: $valuesJs, backgroundColor: $colorsJs }] },\n";
    echo "    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }\n";
    echo "  });\n";
    echo "})();\n";
}

function top_ips() {
    header("content-type: application/x-javascript");
    $w = ppac_window();
    $resp = $GLOBALS["CLASS_SOCKETS"]->REST_API_PROXYPAC("/top-ips?window=$w&limit=10");
    ppac_emit_hbar("ppac-top-ips", "topIps", $resp, "{requests}");
}

function ppac_emit_hbar(string $canvasId, string $chartKey, ?string $resp, string $valueKey, string $color = "#1c84c6"): void {
    $tpl = new template_admin();
    $j = @json_decode($resp);
    $down = (!is_object($j) || (isset($j->Status) && $j->Status === false));
    $rows = (is_object($j) && isset($j->rows) && is_array($j->rows)) ? $j->rows : [];
    $wrapId = $canvasId . "-wrap";

    if ($down || count($rows) === 0) {
        $key = $down ? "{proxy_pac_daemon_unreachable}" : "{no_data_for_this_window}";
        $msg = addslashes($tpl->_ENGINE_parse_body($key));
        echo "if (window.ppacCharts['$chartKey']) { window.ppacCharts['$chartKey'].destroy(); }\n";
        echo "document.getElementById('$wrapId').innerHTML = "
           . "'<p class=\"text-center text-muted\" style=\"padding:40px\">$msg</p>';\n";
        return;
    }

    // The shape of $rows differs between top-ips/top-uas (NamedCount: name+count)
    // and top-rules (RuleCount: rule_id+name+count). Pull the label from `name`,
    // and (for top-rules) prefix with the rule_id for clarity.
    $labels = [];
    $values = [];
    foreach ($rows as $r) {
        $label = $r->name;
        if (isset($r->rule_id)) {
            $label = "#" . intval($r->rule_id) . " " . ($r->name ?: "(no-match)");
        }
        $labels[] = $label;
        $values[] = intval($r->count);
    }
    $labelsJs = json_encode($labels, JSON_UNESCAPED_SLASHES);
    // Plancher PHP 7.3: pas de fonction flechee (elle casse tout le fichier).
    $valuesJs = '[' . implode(',', array_map(function($v){ return sprintf('%d', $v); }, $values)) . ']';
    $title    = addslashes($tpl->_ENGINE_parse_body($valueKey));
    // Canvas height for hbar: detect from caller via fixed value below; row count drives readability.
    $h = max(220, count($rows) * 28);

    echo "if (window.ppacCharts['$chartKey']) { window.ppacCharts['$chartKey'].destroy(); }\n";
    echo "document.getElementById('$wrapId').innerHTML = "
       . "'<canvas id=\"$canvasId\" height=\"$h\"></canvas>';\n";
    echo "(function(){\n";
    echo "  var ctx = document.getElementById('$canvasId').getContext('2d');\n";
    echo "  window.ppacCharts['$chartKey'] = new Chart(ctx, {\n";
    echo "    type: 'bar',\n";
    echo "    data: { labels: $labelsJs, datasets: [{ label: '$title', data: $valuesJs, backgroundColor: '$color' }] },\n";
    echo "    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, "
       . "plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } }\n";
    echo "  });\n";
    echo "})();\n";
}

function top_rules() {
    header("content-type: application/x-javascript");
    $w = ppac_window();
    $resp = $GLOBALS["CLASS_SOCKETS"]->REST_API_PROXYPAC("/top-rules?window=$w&limit=10");
    // Reuse the hbar helper; it auto-detects rule_id presence and prefixes labels.
    ppac_emit_hbar("ppac-top-rules", "topRules", $resp, "{requests}", "#1ab394");
}
