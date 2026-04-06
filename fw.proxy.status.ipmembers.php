<?php
/**
 * Proxy Users & Websites History — Chart.js 4.x dashboards + live members table.
 * Data from REST API /squid/users-history/* (bbolt-backed, 90-day retention).
 *
 * Tabs: Charts (users/websites/connections over time) + Members (live IP table).
 */
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["charts"]))        { render_charts(); exit; }
if(isset($_GET["charts-render"])) { charts_render(); exit; }
page();

// ── Main page ────────────────────────────────────────────────────────────────

function js():bool{
    $tpl  = new template_admin();
    $page = CurrentPageName();
    return $tpl->js_dialog6("{members}",$page,990);
}

function page(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();

    $TINY_ARRAY["TITLE"]   = "{members} / {tcp_address}";
    $TINY_ARRAY["ICO"]     = "fa-regular fa-people-group";
    $TINY_ARRAY["EXPL"]    = "{proxy_service_about}";
    $TINY_ARRAY["URL"]     = "proxy-status";
    $TINY_ARRAY["BUTTONS"] = null;
    $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";

    $h = [];
    $h[] = "<div style='margin-top:10px' id='lastQuarUsr'>";
    $h[] = "</div>";
    $h[] = "<script>$jstiny";
    $h[] = "LoadAjaxSilent('lastQuarUsr','$page?charts=yes&hours=24');</script>";
    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Charts tab ───────────────────────────────────────────────────────────────

function render_charts(): void {
    $tpl   = new template_admin();
    $page  = CurrentPageName();
    $hours = intval($_GET["hours"] ?? 24);
    if ($hours < 1) $hours = 24;

    $h = [];

    // Summary widgets from Valkey
    $currentUsers = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("METRICS_PROXY_CLIENTS_NUMBER"));
    $maxUsers     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("METRICS_MAX_PROXY_CLIENTS_NUMBER"));

    $h[] = "<div class='row' style='margin-bottom:15px;margin-top:10px'>";
    $h[] = _sw('#34495e', 'fas fa-users', '{members}', $currentUsers);
    $h[] = _sw('#1c84c6', 'fas fa-crown', '{peak} (7d)', $maxUsers);

    // DB stats
    $statsJson = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/users-history/stats"));
    if (is_object($statsJson) && !empty($statsJson->success) && is_object($statsJson->data ?? null)) {
        $sd = $statsJson->data;
        $h[] = _sw('#1ab394', 'fas fa-database', '{db_size}', htmlspecialchars($sd->db_size_human ?? '—'));
        $h[] = _sw('#676a6c', 'fas fa-chart-bar', '{samples}', intval($sd->raw_entries ?? 0));
    }
    $h[] = "</div>";

    // Time range selector
    $ranges = [
        1    => '{last_hour}',
        24   => '{last_day}',
        48   => '{yesterday}',
        168  => '{last_week}',
        720  => '{last_month}',
        2160 => '{last_quarter}',
    ];
    $h[] = "<div style='margin-bottom:15px'>";
    $h[] = "  <div class='btn-group' id='uh-range-btns'>";
    foreach ($ranges as $hrs => $label) {
        $active = ($hrs === $hours) ? ' active' : '';
        $h[] = "    <button class='btn btn-sm btn-default$active' onclick=\"UHRange($hrs,this)\">$label</button>";
    }
    $h[] = "  </div>";
    $h[] = "  <button class='btn btn-sm btn-primary' style='margin-left:10px' onclick=\"UHRefresh();\">";
    $h[] = "    <i class='fas fa-sync-alt'></i> {refresh}</button>";
    $h[] = "</div>";

    // Chart area
    $h[] = "<div id='uh-chart-area'></div>";

    // JS controller
    $h[] = "<script>";
    $h[] = "var _uhHours=$hours;";
    $h[] = "function UHRange(hrs,btn){";
    $h[] = "  _uhHours=hrs;";
    $h[] = "  \$('#uh-range-btns .btn').removeClass('active');";
    $h[] = "  \$(btn).addClass('active');";
    $h[] = "  UHRefresh();";
    $h[] = "}";
    $h[] = "function UHRefresh(){";
    $h[] = "  LoadAjax('uh-chart-area','$page?charts-render=yes&hours='+_uhHours);";
    $h[] = "}";
    $h[] = "</script>";

    // Inline render for initial load
    $h[] = render_chart_content($hours);

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

function _sw(string $bg, string $ico, string $label, $val): string {
    $val = htmlspecialchars((string)$val);
    return "<div class='col-md-3 col-sm-6'>"
         . "<div style='background:$bg;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>"
         . "<div style='font-size:22px;font-weight:700'>$val</div>"
         . "<div style='font-size:11px'><i class='$ico'></i> $label</div>"
         . "</div></div>";
}

// ── Chart AJAX reload ─────────────────────────────────────────────────────────

function charts_render(): void {
    $tpl   = new template_admin();
    $hours = intval($_GET["hours"] ?? 24);
    if ($hours < 1) $hours = 24;
    echo $tpl->_ENGINE_parse_body(render_chart_content($hours));
}

// ── Chart content (loaded inline or via AJAX) ────────────────────────────────

function render_chart_content(int $hours): string {
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/squid/users-history/chart?hours=$hours"
    ));

    if (!is_object($json) || empty($json->success) || !is_object($json->data ?? null)) {
        $tpl = new template_admin();
        return $tpl->div_info("{no_data}");
    }

    $d      = $json->data;
    $labels = (array)($d->labels ?? []);
    $users  = (array)($d->users ?? []);
    $sites  = (array)($d->websites ?? []);
    $conns  = (array)($d->conns ?? []);

    if (empty($labels)) {
        $tpl = new template_admin();
        return $tpl->div_info("{no_data}");
    }

    $hasMax    = isset($d->max_users) && is_array($d->max_users);
    $maxUsers  = $hasMax ? (array)$d->max_users : [];
    $maxSites  = $hasMax ? (array)($d->max_websites ?? []) : [];
    $maxConns  = $hasMax ? (array)($d->max_conns ?? []) : [];

    $labelsJS = json_encode($labels);

    $h = [];
    $h[] = "<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    // ── Users chart ──
    $h[] = _build_chart(
        'fas fa-users', '{members}', $labelsJS,
        $users, $maxUsers, '#2196F3', 'rgba(33,150,243,0.1)', '{members}', $hasMax, 'uc'
    );

    // ── Websites chart ──
    $h[] = _build_chart(
        'fas fa-globe', '{websites}', $labelsJS,
        $sites, $maxSites, '#4CAF50', 'rgba(76,175,80,0.1)', '{websites}', $hasMax, 'wc'
    );

    // ── Connections chart ──
    $h[] = _build_chart(
        'fas fa-plug', '{connections}', $labelsJS,
        $conns, $maxConns, '#FF9800', 'rgba(255,152,0,0.1)', '{connections}', $hasMax, 'cc'
    );

    $h[] = "<script>NoSpinner();</script>";
    return implode("\n", $h);
}

function _build_chart(
    string $icon, string $title, string $labelsJS,
    array $avg, array $max, string $color, string $bgColor,
    string $yLabel, bool $hasMax, string $prefix
): string {
    $uid = $prefix . '-' . uniqid();

    // Build avg dataset values with sprintf
    $avgVals = '[' . implode(',', array_map(function($v){ return sprintf('%.1f', floatval($v)); }, $avg)) . ']';

    $datasets = [];
    $avgLabel = $hasMax ? addslashes($yLabel) . ' (avg)' : addslashes($yLabel);
    $datasets[] = "{label:'$avgLabel',data:$avgVals,borderColor:'$color',backgroundColor:'$bgColor',"
                . "tension:0.3,fill:true,pointRadius:1,borderWidth:2}";

    if ($hasMax && !empty($max)) {
        $maxVals = '[' . implode(',', array_map(function($v){ return sprintf('%.0f', floatval($v)); }, $max)) . ']';
        // Lighter dashed line for peak
        $maxColor = str_replace('rgb(', 'rgba(', $color);
        $maxColor = str_replace(')', ',0.4)', $maxColor);
        $peakLabel = addslashes($yLabel) . ' (peak)';
        $datasets[] = "{label:'$peakLabel',data:$maxVals,borderColor:'$maxColor',borderDash:[5,3],"
                    . "tension:0.3,fill:false,pointRadius:0,borderWidth:1.5}";
    }

    $dsJS = '[' . implode(',', $datasets) . ']';

    $h = [];
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='$icon'></i>&nbsp; $title</h5></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = "<div style='position:relative;height:280px'><canvas id='$uid'></canvas></div>";
    $h[] = "</div></div>";
    $h[] = "<script>";
    $h[] = "new Chart(document.getElementById('$uid'),{type:'line',";
    $h[] = "data:{labels:$labelsJS,datasets:$dsJS},";
    $h[] = "options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
    $h[] = "plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:15}}},";
    $h[] = "scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:24}},";
    $h[] = "y:{beginAtZero:true,title:{display:true,text:'$yLabel'}}}}});";
    $h[] = "</script>";
    return implode("\n", $h);
}

// ── Members table tab ────────────────────────────────────────────────────────


