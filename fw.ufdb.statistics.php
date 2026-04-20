<?php
/**
 * UfdbGuard Web Filtering Statistics — Chart.js 4.x dashboards.
 * Data from REST API /ufdb/stats/* and /ufdb/events/live (EventsReceiver).
 *
 * Tabs: Dashboard, Users, Categories, Rules, Real-Time, Analysis.
 */
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}

$users = new usersMenus();
if (!$users->AsDansGuardianAdministrator) { exit(); }

if(isset($_GET["dashboard"]))        { render_dashboard(); exit; }
if(isset($_GET["users"]))            { render_users(); exit; }
if(isset($_GET["user-drill"]))       { user_drill_popup(); exit; }
if(isset($_GET["user-drill-content"])){ user_drill_content(); exit; }
if(isset($_GET["categories"]))       { render_categories(); exit; }
if(isset($_GET["rules"]))            { render_rules(); exit; }
if(isset($_GET["realtime"]))         { render_realtime(); exit; }
if(isset($_GET["analysis"]))         { render_analysis_form(); exit; }
if(isset($_GET["analysis-data"]))    { render_analysis_data(); exit; }
page();

// ── Helpers ──────────────────────────────────────────────────────────────────

function _ufdb_colors(): array {
    return ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF','#FF9F40','#C9CBCF','#7BC8A4','#E7E9ED','#5B6ABF'];
}

function _ufdb_validate_range(string $range): string {
    $valid = ['5m','1h','24h','7d','30d','90d'];
    return in_array($range, $valid) ? $range : '5m';
}

function _ufdb_category_name(string $cat): string {
    if (preg_match('/^P(\d+)$/', $cat, $m)) {
        include_once("ressources/class.mysql.catz.inc");
        $catz = new mysql_catz();
        $name = $catz->CategoryIntToStr(intval($m[1]));
        if ($name !== null && $name !== '') return $name;
    }
    return $cat;
}

function _sw(string $bg, string $ico, string $label, $val): string {
    $val = htmlspecialchars((string)$val);
    return "<div class='col-md-3 col-sm-6'>"
         . "<div style='background:$bg;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>"
         . "<div style='font-size:22px;font-weight:700'>$val</div>"
         . "<div style='font-size:11px'><i class='$ico'></i> $label</div>"
         . "</div></div>";
}

// ── Main page ────────────────────────────────────────────────────────────────

function page(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();

    $h = [];

    // Range selector
    $h[] = "<div style='margin-bottom:15px;margin-top:10px'>";
    $h[] = "  <div class='btn-group' id='ufdb-range-btns'>";
    $ranges = ['5m' => '5min', '1h' => '1h', '24h' => '24h', '7d' => '7d', '30d' => '30d', '90d' => '90d'];
    foreach ($ranges as $val => $label) {
        $active = ($val === '5m') ? ' active' : '';
        $h[] = "    <button class='btn btn-sm btn-default$active' onclick=\"UfdbRange('$val',this);\">$label</button>";
    }
    $h[] = "  </div>";
    $h[] = "  <button class='btn btn-sm btn-primary' style='margin-left:10px' onclick=\"UfdbRefresh();\">";
    $h[] = "    <i class='fas fa-sync-alt'></i> {refresh}</button>";
    $h[] = "</div>";

    // Tab buttons
    $tabs = [
        'dashboard'  => ['fas fa-tachometer-alt', '{dashboard}'],
        'users'      => ['fas fa-users',          '{users}'],
        'categories' => ['fas fa-tags',            '{categories}'],
        'rules'      => ['fas fa-gavel',           '{rules}'],
        'realtime'   => ['fas fa-bolt',            '{realtime}'],
        'analysis'   => ['fas fa-search',          '{analysis}'],
    ];
    $h[] = "<ul class='nav nav-tabs' id='ufdb-tabs' style='margin-bottom:15px'>";
    foreach ($tabs as $key => $arr) {
        $active = ($key === 'dashboard') ? " class='active'" : '';
        $h[] = "  <li$active><a href='#' onclick=\"UfdbTab('$key',this);return false;\"><i class='{$arr[0]}'></i>&nbsp;{$arr[1]}</a></li>";
    }
    $h[] = "</ul>";

    // Content container
    $h[] = "<div id='ufdb-stats-content'></div>";

    // JS controller
    $h[] = "<script>";
    $h[] = "var _ufdbRange='5m',_ufdbTab='dashboard';";
    $h[] = "function UfdbRange(r,btn){";
    $h[] = "  _ufdbRange=r;";
    $h[] = "  \$('#ufdb-range-btns .btn').removeClass('active');";
    $h[] = "  \$(btn).addClass('active');";
    $h[] = "  UfdbRefresh();";
    $h[] = "}";
    $h[] = "function UfdbTab(tab,el){";
    $h[] = "  _ufdbTab=tab;";
    $h[] = "  \$('#ufdb-tabs li').removeClass('active');";
    $h[] = "  \$(el).closest('li').addClass('active');";
    $h[] = "  UfdbRefresh();";
    $h[] = "}";
    $h[] = "function UfdbRefresh(){";
    $h[] = "  var url='$page?'+_ufdbTab+'=yes';";
    $h[] = "  if(_ufdbTab!='realtime') url+='&range='+_ufdbRange;";
    $h[] = "  LoadAjax('ufdb-stats-content',url);";
    $h[] = "}";
    $h[] = "UfdbRefresh();";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Tab 1: Dashboard ─────────────────────────────────────────────────────────

function render_dashboard(): void {
    $tpl   = new template_admin();
    $range = _ufdb_validate_range($_GET["range"] ?? "5m");

    $h = [];
    $h[] = "<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    // ── Summary widgets ──
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/stats/summary?range=$range"));
    $blocks = $passes = $redirs = 0;
    if (is_object($json) && !empty($json->Status) && is_array($json->Groups ?? null)) {
        foreach ($json->Groups as $g) {
            $action = $g->keys->action ?? '';
            if ($action === 'BLOCK') $blocks = intval($g->count);
            elseif ($action === 'PASS') $passes = intval($g->count);
            elseif ($action === 'REDIR') $redirs = intval($g->count);
        }
    }
    $total = $blocks + $passes + $redirs;
    $rate  = $total > 0 ? sprintf('%.1f', ($blocks / $total) * 100) : '0';

    $h[] = "<div class='row' style='margin-bottom:20px'>";
    $h[] = _sw('#34495e', 'fas fa-globe',        '{total_events}', number_format($total));
    $h[] = _sw('#ed5565', 'fas fa-shield-alt',    '{blocked}',      number_format($blocks));
    $h[] = _sw('#1ab394', 'fas fa-check-circle',  '{passed}',       number_format($passes));
    $h[] = _sw('#1c84c6', 'fas fa-percentage',    '{block_rate}',   "$rate%");
    $h[] = "</div>";

    // ── Charts row ──
    $h[] = "<div class='row'>";

    // Doughnut: blocked categories
    $h[] = "<div class='col-md-5'>";
    $h[] = _render_dashboard_doughnut($range);
    $h[] = "</div>";

    // Bar: top blocked domains
    $h[] = "<div class='col-md-7'>";
    $h[] = _render_dashboard_domains($range);
    $h[] = "</div>";

    $h[] = "</div>";
    $h[] = "<script>NoSpinner();</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

function _render_dashboard_doughnut(string $range): string {
    $tpl    = new template_admin();
    $json   = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/ufdb/stats/top/categories?range=$range&top=8&filter_action=BLOCK"
    ));

    if (!is_object($json) || empty($json->Status) || !is_array($json->Groups ?? null) || empty($json->Groups)) {
        return $tpl->div_info("{no_data}");
    }

    $labels = [];
    $data   = [];
    foreach ($json->Groups as $g) {
        $labels[] = json_encode(_ufdb_category_name($g->keys->category ?? ''));
        $data[]   = intval($g->count);
    }

    $colors  = _ufdb_colors();
    $colorsJS = "['" . implode("','", array_slice($colors, 0, count($data))) . "']";
    $uid = 'doughnut-' . uniqid();

    $h = [];
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-chart-pie'></i>&nbsp; {blocked_categories}</h5></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = "<div style='position:relative;height:250px'><canvas id='$uid'></canvas></div>";
    $h[] = "</div></div>";
    $h[] = "<script>";
    $h[] = "new Chart(document.getElementById('$uid'),{type:'doughnut',";
    $h[] = "data:{labels:[" . implode(",", $labels) . "],datasets:[{data:[" . implode(",", $data) . "],backgroundColor:$colorsJS}]},";
    $h[] = "options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{padding:10,boxWidth:12}}}}});";
    $h[] = "</script>";
    return implode("\n", $h);
}

function _render_dashboard_domains(string $range): string {
    $tpl  = new template_admin();
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/ufdb/stats/top/domains?range=$range&top=10&filter_action=BLOCK"
    ));

    if (!is_object($json) || empty($json->Status) || !is_array($json->Groups ?? null) || empty($json->Groups)) {
        return $tpl->div_info("{no_data}");
    }

    $labels = [];
    $data   = [];
    foreach ($json->Groups as $g) {
        $domain = $g->keys->domain ?? '';
        $labels[] = json_encode(strlen($domain) > 35 ? substr($domain, 0, 35) . '...' : $domain);
        $data[] = intval($g->count);
    }

    $chartH = max(250, count($data) * 28);
    $uid = 'domains-' . uniqid();

    $h = [];
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-ban'></i>&nbsp; {top_blocked_domains}</h5></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = "<div style='position:relative;height:{$chartH}px'><canvas id='$uid'></canvas></div>";
    $h[] = "</div></div>";
    $h[] = "<script>";
    $blockedT=$tpl->javascript_parse_text("{blocked}");
    $h[] = "new Chart(document.getElementById('$uid'),{type:'bar',";
    $h[] = "data:{labels:[" . implode(",", $labels) . "],datasets:[{label:'$blockedT',data:[" . implode(",", $data) . "],backgroundColor:'#FF6384'}]},";
    $h[] = "options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,";
    $h[] = "plugins:{legend:{display:false}},";
    $h[] = "scales:{x:{beginAtZero:true},y:{ticks:{font:{size:11}}}}}});";
    $h[] = "</script>";
    return implode("\n", $h);
}
// ── Tab 2: Users ─────────────────────────────────────────────────────────────

function render_users(): void {
    $tpl   = new template_admin();
    $page  = CurrentPageName();
    $range = _ufdb_validate_range($_GET["range"] ?? "5m");

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/ufdb/stats/top/users?range=$range&top=15&filter_action=BLOCK"
    ));

    $h = [];
    $h[] = "<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    if (!is_object($json) || empty($json->Status) || !is_array($json->Groups ?? null) || empty($json->Groups)) {
        $h[] = $tpl->div_info("{no_data}");
        $h[] = "<script>NoSpinner();</script>";
        echo $tpl->_ENGINE_parse_body(implode("\n", $h));
        return;
    }

    $labels   = [];
    $data     = [];
    $rawUsers = [];
    foreach ($json->Groups as $g) {
        $user = $g->keys->username ?? '';
        $rawUsers[] = $user;
        $labels[] = json_encode(strlen($user) > 25 ? substr($user, 0, 25) . '...' : $user);
        $data[] = intval($g->count);
    }

    $usersJS = "[" . implode(",", array_map('json_encode', $rawUsers)) . "]";
    $uid = 'users-' . uniqid();

    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-users'></i>&nbsp; {top_blocked_users}</h5></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = "<div style='position:relative;height:350px'><canvas id='$uid'></canvas></div>";
    $h[] = "</div></div>";
    $h[] = "<script>";
    $h[] = "var _ufdbUserNames=$usersJS;";
    $h[] = "new Chart(document.getElementById('$uid'),{type:'bar',";
    $blockedT=$tpl->javascript_parse_text("{blocked}");
    $h[] = "data:{labels:[" . implode(",", $labels) . "],datasets:[{label:'$blockedT',data:[" . implode(",", $data) . "],backgroundColor:'#FF6384'}]},";
    $h[] = "options:{responsive:true,maintainAspectRatio:false,";
    $h[] = "onClick:function(e,els){if(els.length>0){var idx=els[0].index;var u=_ufdbUserNames[idx];";
    $h[] = "Loadjs('$page?user-drill=yes&username='+encodeURIComponent(u)+'&range=$range');}},";
    $h[] = "plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return c.parsed.y+' $blockedT';}}}},";
    $h[] = "scales:{x:{ticks:{maxRotation:45,autoSkip:true,font:{size:11}}},y:{beginAtZero:true}}}});";
    $h[] = "NoSpinner();";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

function user_drill_popup(): void {
    $tpl      = new template_admin();
    $page     = CurrentPageName();
    $username = $_GET["username"] ?? "";
    $range    = _ufdb_validate_range($_GET["range"] ?? "5m");

    if ($username === "") {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $url = "$page?user-drill-content=yes&username=" . urlencode($username) . "&range=$range";
    $tpl->js_dialog1("{user_details}", $url, 700);
}

function user_drill_content(): void {
    $tpl      = new template_admin();
    $username = $_GET["username"] ?? "";
    $range    = _ufdb_validate_range($_GET["range"] ?? "5m");

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/ufdb/stats/query?range=$range&group_by=category&filter_username=" . urlencode($username) . "&filter_action=BLOCK&top=20"
    ));

    if (!is_object($json) || empty($json->Status) || !is_array($json->Groups ?? null) || empty($json->Groups)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $safeUser = htmlspecialchars($username);
    $t = time();
    $h = [];
    $h[] = "<h4 style='margin-bottom:15px'><i class='fas fa-user'></i>&nbsp; $safeUser</h4>";
    $h[] = "<table id='table-$t' class='footable table table-stripped' data-page-size='20'>";
    $h[] = "<thead><tr>";
    $h[] = "<th data-sortable='true' data-type='text'>{category}</th>";
    $h[] = "<th data-sortable='true' data-type='number'>{blocked}</th>";
    $h[] = "</tr></thead><tbody>";

    $TRCLASS = null;
    foreach ($json->Groups as $g) {
        if ($TRCLASS == "footable-odd") { $TRCLASS = null; } else { $TRCLASS = "footable-odd"; }
        $cat   = htmlspecialchars(_ufdb_category_name($g->keys->category ?? ''));
        $count = intval($g->count);
        $h[] = "<tr class='$TRCLASS'><td>$cat</td><td>$count</td></tr>";
    }

    $h[] = "</tbody>";
    $h[] = "<tfoot><tr><td colspan='2'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $h[] = "</table>";
    $h[] = "<script>NoSpinner();\n" . implode("\n", $tpl->ICON_SCRIPTS);
    $h[] = "\$(document).ready(function(){ \$('#table-$t').footable({";
    $h[] = "\"filtering\":{\"enabled\":true},\"sorting\":{\"enabled\":true},\"paging\":{\"size\":20}";
    $h[] = "}); });</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}
// ── Tab 3: Categories ────────────────────────────────────────────────────────

function render_categories(): void {
    $tpl   = new template_admin();
    $range = _ufdb_validate_range($_GET["range"] ?? "5m");

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/ufdb/stats/top/categories?range=$range&top=12"
    ));

    $h = [];
    $h[] = "<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    if (!is_object($json) || empty($json->Status) || !is_array($json->Groups ?? null) || empty($json->Groups)) {
        $h[] = $tpl->div_info("{no_data}");
        $h[] = "<script>NoSpinner();</script>";
        echo $tpl->_ENGINE_parse_body(implode("\n", $h));
        return;
    }

    $labels = [];
    $data   = [];
    foreach ($json->Groups as $g) {
        $labels[] = json_encode(_ufdb_category_name($g->keys->category ?? ''));
        $data[] = intval($g->count);
    }

    $colors   = _ufdb_colors();
    $nColors  = count($colors);
    $colSlice = [];
    for ($i = 0; $i < count($data); $i++) { $colSlice[] = $colors[$i % $nColors]; }
    $colorsJS = "['" . implode("','", $colSlice) . "']";

    $uid = 'catpie-' . uniqid();

    $h[] = "<div class='row'><div class='col-md-8 col-md-offset-2'>";
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-tags'></i>&nbsp; {categories}</h5></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = "<div style='position:relative;height:350px'><canvas id='$uid'></canvas></div>";
    $h[] = "</div></div>";
    $h[] = "</div></div>";
    $h[] = "<script>";
    $h[] = "new Chart(document.getElementById('$uid'),{type:'pie',";
    $h[] = "data:{labels:[" . implode(",", $labels) . "],datasets:[{data:[" . implode(",", $data) . "],backgroundColor:$colorsJS}]},";
    $h[] = "options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{padding:10,boxWidth:12}}}}});";
    $h[] = "NoSpinner();";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}
// ── Tab 4: Rules ─────────────────────────────────────────────────────────────

function render_rules(): void {
    $tpl   = new template_admin();
    $range = _ufdb_validate_range($_GET["range"] ?? "5m");

    $jsonBlocks = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/ufdb/stats/query?range=$range&group_by=rule&filter_action=BLOCK"
    ));
    $jsonPasses = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/ufdb/stats/query?range=$range&group_by=rule&filter_action=PASS"
    ));

    $h = [];
    $h[] = "<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    // Merge rules from both responses
    $blockMap = [];
    $passMap  = [];
    $allRules = [];

    if (is_object($jsonBlocks) && !empty($jsonBlocks->Status) && is_array($jsonBlocks->Groups ?? null)) {
        foreach ($jsonBlocks->Groups as $g) {
            $rule = $g->keys->rule ?? '';
            $blockMap[$rule] = intval($g->count);
            $allRules[$rule] = true;
        }
    }
    if (is_object($jsonPasses) && !empty($jsonPasses->Status) && is_array($jsonPasses->Groups ?? null)) {
        foreach ($jsonPasses->Groups as $g) {
            $rule = $g->keys->rule ?? '';
            $passMap[$rule] = intval($g->count);
            $allRules[$rule] = true;
        }
    }

    if (empty($allRules)) {
        $h[] = $tpl->div_info("{no_data}");
        $h[] = "<script>NoSpinner();</script>";
        echo $tpl->_ENGINE_parse_body(implode("\n", $h));
        return;
    }

    $ruleNames  = array_keys($allRules);
    $labels     = [];
    $blockData  = [];
    $passData   = [];
    foreach ($ruleNames as $r) {
        $labels[]    = json_encode($r);
        $blockData[] = $blockMap[$r] ?? 0;
        $passData[]  = $passMap[$r] ?? 0;
    }

    $uid = 'rules-' . uniqid();

    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-gavel'></i>&nbsp; {rules}: {blocked} / {passed}</h5></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = "<div style='position:relative;height:350px'><canvas id='$uid'></canvas></div>";
    $h[] = "</div></div>";
    $h[] = "<script>";
    $h[] = "new Chart(document.getElementById('$uid'),{type:'bar',";
    $h[] = "data:{labels:[" . implode(",", $labels) . "],datasets:[";
    $blockedT=$tpl->javascript_parse_text("{blocked}");
    $passedT=$tpl->javascript_parse_text("{passed}");
    $h[] = "{label:'$blockedT',data:[" . implode(",", $blockData) . "],backgroundColor:'#ed5565'},";
    $h[] = "{label:'$passedT',data:[" . implode(",", $passData) . "],backgroundColor:'#1ab394'}";
    $h[] = "]},options:{responsive:true,maintainAspectRatio:false,";
    $h[] = "plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:15}}},";
    $h[] = "scales:{x:{stacked:true,ticks:{font:{size:11}}},y:{stacked:true,beginAtZero:true}}}});";
    $h[] = "NoSpinner();";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}
// ── Tab 5: Real-Time ─────────────────────────────────────────────────────────

function render_realtime(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();

    // Try live endpoint first
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/events/live"));
    $rules   = [];
    $window  = '5m';
    $source  = 'live';
    $fbRange = '';

    if (is_object($json) && !empty($json->Status)) {
        $rules  = is_array($json->Rules ?? null) ? $json->Rules : [];
        $window = htmlspecialchars($json->WindowDuration ?? '5m');
    }

    // If live window is empty, fall back to recent stats grouped by rule
    if (empty($rules)) {
        $source = 'recent';
        $fbRange = '1h';
        $fallback = null;
        foreach (['5m','1h','24h','7d'] as $_r) {
            $fallback = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
                "/ufdb/stats/query?range=$_r&group_by=rule"
            ));
            if (is_object($fallback) && !empty($fallback->Status) && !empty($fallback->Groups)) {
                $fbRange = $_r;
                break;
            }
        }
        if (is_object($fallback) && !empty($fallback->Status) && is_array($fallback->Groups ?? null) && !empty($fallback->Groups)) {
            foreach ($fallback->Groups as $g) {
                $rules[] = (object)[
                    'rule'   => $g->keys->rule ?? '',
                    'total'  => intval($g->count),
                    'blocks' => 0,
                    'passes' => 0,
                ];
            }
            // Enrich with block counts
            $fbBlocks = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
                "/ufdb/stats/query?range=$fbRange&group_by=rule&filter_action=BLOCK"
            ));
            $blockMap = [];
            if (is_object($fbBlocks) && !empty($fbBlocks->Status) && is_array($fbBlocks->Groups ?? null)) {
                foreach ($fbBlocks->Groups as $g) {
                    $blockMap[$g->keys->rule ?? ''] = intval($g->count);
                }
            }
            foreach ($rules as $r) {
                $r->blocks = $blockMap[$r->rule] ?? 0;
                $r->passes = $r->total - $r->blocks;
            }
        }
    }

    $t = time();
    $h = [];

    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-bolt'></i>&nbsp; {realtime}</h5>";
    $h[] = "<div class='ibox-tools'>";
    if ($source === 'live') {
        $h[] = "<span class='badge' style='background:#1c84c6;color:#fff'>$window</span> ";
    } else {
        $h[] = "<span class='badge' style='background:#f8ac59;color:#fff'>{last} $fbRange</span> ";
    }
    $h[] = "</div></div>";
    $h[] = "<div class='ibox-content'>";

    if (empty($rules)) {
        $h[] = "<p class='text-muted text-center' style='padding:30px 0'><i class='fas fa-clock' style='font-size:24px'></i><br>{no_data}</p>";
    } else {
        $h[] = "<table id='table-$t' class='footable table table-stripped' data-page-size='50'>";
        $h[] = "<thead><tr>";
        $h[] = "<th data-sortable='true' data-type='text'>{rule}</th>";
        $h[] = "<th data-sortable='true' data-type='number'>Total</th>";
        $h[] = "<th data-sortable='true' data-type='number'>{blocked}</th>";
        $h[] = "<th data-sortable='true' data-type='number'>{passed}</th>";
        $h[] = "<th data-sortable='true' data-type='number'>{block_rate} %</th>";
        $h[] = "</tr></thead><tbody>";

        $TRCLASS = null;
        foreach ($rules as $r) {
            if ($TRCLASS == "footable-odd") { $TRCLASS = null; } else { $TRCLASS = "footable-odd"; }
            $rule   = htmlspecialchars($r->rule ?? '');
            $tot    = intval($r->total ?? 0);
            $blk    = intval($r->blocks ?? 0);
            $pass   = intval($r->passes ?? 0);
            $pct    = $tot > 0 ? sprintf('%.1f', ($blk / $tot) * 100) : '0.0';
            $pctF   = floatval($pct);
            if ($pctF >= 20)     $pctStyle = "color:#fff;background:#ed5565;padding:2px 8px;border-radius:3px";
            elseif ($pctF >= 10) $pctStyle = "color:#fff;background:#f8ac59;padding:2px 8px;border-radius:3px";
            else                 $pctStyle = "color:#fff;background:#1ab394;padding:2px 8px;border-radius:3px";
            $h[] = "<tr class='$TRCLASS'>";
            $h[] = "<td><strong>$rule</strong></td>";
            $h[] = "<td>" . number_format($tot) . "</td>";
            $h[] = "<td>" . number_format($blk) . "</td>";
            $h[] = "<td>" . number_format($pass) . "</td>";
            $h[] = "<td><span style='$pctStyle'>$pct%</span></td>";
            $h[] = "</tr>";
        }

        $h[] = "</tbody></table>";
    }

    $h[] = "</div></div>";
    $h[] = "<script>NoSpinner();";
    if (!empty($rules)) {
        $h[] = implode("\n", $tpl->ICON_SCRIPTS);
        $h[] = "\$(document).ready(function(){ \$('#table-$t').footable({\"sorting\":{\"enabled\":true}}); });";
    }
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}
// ── Tab 6: Analysis ──────────────────────────────────────────────────────────

function render_analysis_form(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();

    $dims = ['username' => '{username}', 'domain' => '{domain}', 'rule' => '{rule}', 'category' => '{category}', 'action' => 'Action'];

    $h = [];
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-search'></i>&nbsp; {analysis}</h5></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = "<div class='row' style='margin-bottom:15px'>";

    // Group By 1
    $h[] = "<div class='col-md-4'>";
    $h[] = "<label>Group By 1</label>";
    $h[] = "<select id='ufdb-g1' class='form-control input-sm'>";
    foreach ($dims as $k => $v) {
        $sel = ($k === 'username') ? ' selected' : '';
        $h[] = "<option value='$k'$sel>$v</option>";
    }
    $h[] = "</select></div>";

    // Group By 2
    $h[] = "<div class='col-md-4'>";
    $h[] = "<label>Group By 2</label>";
    $h[] = "<select id='ufdb-g2' class='form-control input-sm'>";
    foreach ($dims as $k => $v) {
        $sel = ($k === 'category') ? ' selected' : '';
        $h[] = "<option value='$k'$sel>$v</option>";
    }
    $h[] = "</select></div>";

    // Load button
    $h[] = "<div class='col-md-4' style='padding-top:22px'>";
    $h[] = "<button class='btn btn-primary btn-sm' onclick=\"UfdbAnalysisLoad();\">";
    $h[] = "<i class='fas fa-play'></i> {load}</button></div>";

    $h[] = "</div></div></div>";

    // Results container
    $h[] = "<div id='analysis-results'></div>";

    $h[] = "<script>";
    $h[] = "function UfdbAnalysisLoad(){";
    $h[] = "  var g1=\$('#ufdb-g1').val(),g2=\$('#ufdb-g2').val();";
    $h[] = "  LoadAjax('analysis-results','$page?analysis-data=yes&range='+_ufdbRange+'&g1='+g1+'&g2='+g2);";
    $h[] = "}";
    $h[] = "NoSpinner();";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

function render_analysis_data(): void {
    $tpl   = new template_admin();
    $range = _ufdb_validate_range($_GET["range"] ?? "5m");
    $g1    = $_GET["g1"] ?? "username";
    $g2    = $_GET["g2"] ?? "category";

    $validDims = ['username','domain','rule','category','action'];
    if (!in_array($g1, $validDims)) $g1 = 'username';
    if (!in_array($g2, $validDims)) $g2 = 'category';

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/ufdb/stats/query?range=$range&group_by=$g1,$g2&filter_action=BLOCK&top=50"
    ));

    if (!is_object($json) || empty($json->Status) || !is_array($json->Groups ?? null) || empty($json->Groups)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $safeG1 = htmlspecialchars($g1);
    $safeG2 = htmlspecialchars($g2);
    $t = time();

    $h = [];
    $h[] = "<table id='table-$t' class='footable table table-stripped' data-page-size='50'>";
    $h[] = "<thead><tr>";
    $h[] = "<th data-sortable='true' data-type='text'>$safeG1</th>";
    $h[] = "<th data-sortable='true' data-type='text'>$safeG2</th>";
    $h[] = "<th data-sortable='true' data-type='number'>{count}</th>";
    $h[] = "</tr></thead><tbody>";

    $TRCLASS = null;
    foreach ($json->Groups as $g) {
        if ($TRCLASS == "footable-odd") { $TRCLASS = null; } else { $TRCLASS = "footable-odd"; }
        $v1 = $g->keys->{$g1} ?? '';
        $v2 = $g->keys->{$g2} ?? '';
        if ($g1 === 'category') $v1 = _ufdb_category_name($v1);
        if ($g2 === 'category') $v2 = _ufdb_category_name($v2);
        $v1 = htmlspecialchars($v1);
        $v2 = htmlspecialchars($v2);
        $count = intval($g->count);
        $h[] = "<tr class='$TRCLASS'><td>$v1</td><td>$v2</td><td>$count</td></tr>";
    }

    $h[] = "</tbody>";
    $h[] = "<tfoot><tr><td colspan='3'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $h[] = "</table>";
    $h[] = "<script>NoSpinner();\n" . implode("\n", $tpl->ICON_SCRIPTS);
    $h[] = "\$(document).ready(function(){ \$('#table-$t').footable({";
    $h[] = "\"filtering\":{\"enabled\":true},\"sorting\":{\"enabled\":true},\"paging\":{\"size\":50}";
    $h[] = "}); });</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}
