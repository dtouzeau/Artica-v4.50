<?php
/**
 * Squid Latency Statistics — Chart.js 4.x dashboards.
 * Data from REST API /latencystats/* (BoltDB-backed, multi-tier retention).
 *
 * Summary widgets, latency distribution chart, top slow sites table,
 * and per-site trend popups.
 */
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["charts"]))           { render_charts(); exit; }
if(isset($_GET["top-slow"]))         { render_top_slow_section(); exit; }
if(isset($_GET["site-trend"]))       { site_trend_popup(); exit; }
if(isset($_GET["site-trend-chart"])) { site_trend_chart(); exit; }
if(isset($_GET["js"]))               { js(); exit; }
page();

// ── Dialog opener ────────────────────────────────────────────────────────────

function js(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $tpl->js_dialog6("{latencies}", $page, 990);
}

// ── Time label formatting ────────────────────────────────────────────────────

function ts_fmt(string $period): string {
    switch ($period) {
        case 'current': return 'H:i';
        case 'hourly':  return 'M d H:i';
        case 'daily':   return 'M d';
        default:        return 'Y-m-d';
    }
}

// ── Summary widget helper ────────────────────────────────────────────────────

function _sw(string $bg, string $ico, string $label, $val): string {
    $val = htmlspecialchars((string)$val);
    return "<div class='col-md-2 col-sm-4'>"
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

    // Period selector button group
    $h[] = "<div style='margin-bottom:15px'>";
    $h[] = "  <div class='btn-group' id='lat-period-btns'>";
    $periods = ['current' => '5min', 'hourly' => '{hourly}', 'daily' => '{daily}', 'weekly' => '{weekly}', 'monthly' => '{monthly}'];
    foreach ($periods as $key => $label) {
        $active = ($key === 'current') ? ' active' : '';
        $h[] = "    <button class='btn btn-sm btn-default$active' onclick=\"LatPeriod('$key',this);\">$label</button>";
    }
    $h[] = "  </div>";
    $h[] = "  <button class='btn btn-sm btn-primary' style='margin-left:10px' onclick=\"LatRefresh();\">";
    $h[] = "    <i class='fas fa-sync-alt'></i> {refresh}</button>";
    $h[] = "</div>";

    // Charts container
    $h[] = "<div id='latency-charts'></div>";

    // JS
    $h[] = "<script>";
    $h[] = "var _latPeriod='current';";
    $h[] = "function LatPeriod(p,btn){";
    $h[] = "  _latPeriod=p;";
    $h[] = "  \$('#lat-period-btns .btn').removeClass('active');";
    $h[] = "  \$(btn).addClass('active');";
    $h[] = "  LatRefresh();";
    $h[] = "}";
    $h[] = "function LatRefresh(){";
    $h[] = "  LoadAjax('latency-charts','$page?charts=yes&period='+_latPeriod);";
    $h[] = "}";
    $h[] = "LatRefresh();";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Charts rendering ─────────────────────────────────────────────────────────

function render_charts(): void {
    $tpl    = new template_admin();
    $valid  = ['current', 'hourly', 'daily', 'weekly', 'monthly'];
    $period = isset($_GET["period"]) && in_array($_GET["period"], $valid) ? $_GET["period"] : 'current';

    $h = [];
    $h[] = "<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    // 1. Summary widgets
    $h[] = render_summary($period);

    // 2. Distribution histogram
    $h[] = render_distribution($period);

    // 3. Top slowest sites
    $h[] = render_top_slow($period);

    $h[] = "<script>NoSpinner();</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Summary widgets ──────────────────────────────────────────────────────────

function render_summary(string $period): string {
    $tpl  = new template_admin();
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/latencystats/summary?period=$period"));

    if (!is_object($json) || empty($json->success) || !is_object($json->data->summary ?? null)) {
        return $tpl->div_info("{no_data}");
    }

    $s = $json->data->summary;
    $h = [];
    $h[] = "<div class='row' style='margin-bottom:20px'>";
    $h[] = _sw('#1c84c6', 'fas fa-clock',                '{average} (ms)',  intval($s->avg_latency_ms ?? 0));
    $h[] = _sw('#e67e22', 'fas fa-tachometer-alt',        'P95 (ms)',        intval($s->p95_latency_ms ?? 0));
    $h[] = _sw('#e74c3c', 'fas fa-exclamation-triangle',  'P99 (ms)',        intval($s->p99_latency_ms ?? 0));
    $h[] = _sw('#34495e', 'fas fa-arrow-up',              'Max (ms)',        intval($s->max_latency_ms ?? 0));
    $h[] = _sw('#1ab394', 'fas fa-chart-bar',             '{samples}',       number_format(intval($s->total_samples ?? 0)));
    $h[] = _sw('#676a6c', 'fas fa-globe',                 '{websites}',      number_format(intval($s->unique_sites ?? 0)));
    $h[] = "</div>";
    return implode("\n", $h);
}

// ── Distribution histogram ───────────────────────────────────────────────────

function render_distribution(string $period): string {
    $tpl  = new template_admin();
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/latencystats/distribution?period=$period"));

    if (!is_object($json) || empty($json->success) || !is_array($json->data->buckets ?? null)) {
        return $tpl->div_info("{no_data}");
    }

    $buckets = $json->data->buckets;
    $labels  = [];
    $counts  = [];
    $colors  = [];
    $kept    = 0;

    foreach ($buckets as $b) {
        if (intval($b->count) == 0 && intval($b->lower_ms) > 10000) continue;
        $upper = intval($b->upper_ms);
        if ($upper <= 1)        $labels[] = '<1ms';
        elseif ($upper <= 1000) $labels[] = '<' . $upper . 'ms';
        else                    $labels[] = '<' . sprintf('%.1f', $upper / 1000) . 's';
        $counts[] = intval($b->count);
        $kept++;
    }

    if ($kept == 0) return $tpl->div_info("{no_data}");

    // Green-to-red gradient: interpolate per bucket
    for ($i = 0; $i < $kept; $i++) {
        $ratio = $kept > 1 ? $i / ($kept - 1) : 0;
        $r = intval(46  + (231 - 46)  * $ratio);
        $g = intval(204 + (76  - 204) * $ratio);
        $b2 = intval(113 + (60  - 113) * $ratio);
        $colors[] = "rgba($r,$g,$b2,0.75)";
    }

    $labelsJS = "['" . implode("','", $labels) . "']";
    $countsJS = "[" . implode(",", $counts) . "]";
    $colorsJS = "['" . implode("','", $colors) . "']";

    $uid = 'dist-' . uniqid();
    $h = [];
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-chart-bar'></i>&nbsp; {latency_distribution}</h5></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = "<div style='position:relative;height:300px'><canvas id='$uid'></canvas></div>";
    $h[] = "</div></div>";
    $h[] = "<script>";
    $h[] = "new Chart(document.getElementById('$uid'),{type:'bar',";
    $h[] = "data:{labels:$labelsJS,datasets:[{label:'{requests}',data:$countsJS,backgroundColor:$colorsJS,borderWidth:1}]},";
    $h[] = "options:{responsive:true,maintainAspectRatio:false,";
    $h[] = "plugins:{legend:{display:false},title:{display:false}},";
    $h[] = "scales:{x:{ticks:{maxRotation:45,autoSkip:true}},y:{beginAtZero:true,title:{display:true,text:'{requests}'}}}}});";
    $h[] = "</script>";
    return implode("\n", $h);
}

// ── Top slowest sites ────────────────────────────────────────────────────────

function render_top_slow(string $period, string $by = 'p95'): string {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $validBy = ['avg','p95','p99','max'];
    if (!in_array($by, $validBy)) $by = 'p95';

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/latencystats/top/slow?period=$period&by=$by&limit=15"
    ));

    if (!is_object($json) || empty($json->success) || !is_array($json->data->entries ?? null)) {
        return $tpl->div_info("{no_data}");
    }

    $entries = $json->data->entries;
    if (empty($entries)) return $tpl->div_info("{no_data}");

    $labels   = [];
    $values   = [];
    $colors   = [];
    $rawSites = [];

    foreach ($entries as $e) {
        $site = $e->site ?? '';
        $rawSites[] = $site;
        $labels[] = strlen($site) > 30 ? substr($site, 0, 30) . '...' : $site;
        $ms = intval($e->{$by . '_ms'} ?? 0);
        $values[] = $ms;
        if ($ms < 200)       $colors[] = 'rgba(46,204,113,0.7)';
        elseif ($ms < 1000)  $colors[] = 'rgba(230,126,34,0.7)';
        else                 $colors[] = 'rgba(231,76,60,0.7)';
    }

    $chartH = max(250, count($entries) * 28);
    $labelsJS = "[" . implode(",", array_map('json_encode', $labels)) . "]";
    $valuesJS = "[" . implode(",", $values) . "]";
    $colorsJS = "['" . implode("','", $colors) . "']";

    // Build JS array of raw site names for click handler
    $sitesJS = "[" . implode(",", array_map('json_encode', $rawSites)) . "]";

    $uid = 'topslow-' . uniqid();

    // Sort selector buttons
    $sortBtns = [];
    foreach (['avg' => 'Avg', 'p95' => 'P95', 'p99' => 'P99', 'max' => 'Max'] as $k => $lbl) {
        $cls = ($k === $by) ? 'btn-primary btn-xs' : 'btn-default btn-xs';
        $sortBtns[] = "<button class='btn $cls' onclick=\"LatTopSlow('$k');\">$lbl</button>";
    }
    $sortHtml = implode(' ', $sortBtns);

    $h = [];
    $h[] = "<div id='top-slow-container'>";
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-sort-amount-down'></i>&nbsp; {top_slowest_sites} (" . strtoupper($by) . ")</h5>";
    $h[] = "<div class='ibox-tools'>$sortHtml</div></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = "<div style='position:relative;height:{$chartH}px'><canvas id='$uid'></canvas></div>";
    $h[] = "</div></div></div>";
    $h[] = "<script>";
    $h[] = "var _latTopSlowSites=$sitesJS;";
    $h[] = "function LatTopSlow(by){LoadAjax('top-slow-container','$page?top-slow=yes&period='+_latPeriod+'&by='+by);}";
    $h[] = "(function(){";
    $h[] = "var ctx=document.getElementById('$uid');";
    $h[] = "var chart=new Chart(ctx,{type:'bar',";
    $h[] = "data:{labels:$labelsJS,datasets:[{label:'" . strtoupper($by) . " (ms)',data:$valuesJS,backgroundColor:$colorsJS}]},";
    $h[] = "options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,";
    $h[] = "onClick:function(e,els){if(els.length>0){var idx=els[0].index;var site=_latTopSlowSites[idx];";
    $h[] = "Loadjs('$page?site-trend=yes&sitename='+encodeURIComponent(site)+'&period='+_latPeriod);}},";
    $h[] = "plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return c.parsed.x+' ms';}}}},";
    $h[] = "scales:{x:{beginAtZero:true,title:{display:true,text:'ms'}},y:{ticks:{font:{size:11}}}}}});";
    $h[] = "})();";
    $h[] = "</script>";
    return implode("\n", $h);
}

// ── Top slow section reload (for sort button clicks) ─────────────────────────

function render_top_slow_section(): void {
    $tpl    = new template_admin();
    $period = $_GET["period"] ?? "current";
    $by     = $_GET["by"] ?? "p95";
    $valid  = ['current','hourly','daily','weekly','monthly'];
    if (!in_array($period, $valid)) $period = 'current';

    $h = [];
    $h[] = "<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";
    $h[] = render_top_slow($period, $by);
    $h[] = "<script>NoSpinner();</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Per-site trend drill-down (opened as dialog) ─────────────────────────────

function site_trend_popup(): void {
    $tpl      = new template_admin();
    $page     = CurrentPageName();
    $sitename = $_GET["sitename"] ?? "";
    $period   = $_GET["period"] ?? "current";
    $valid    = ['current','hourly','daily','weekly','monthly'];
    if (!in_array($period, $valid)) $period = 'current';

    if ($sitename === "") {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $url = "$page?site-trend-chart=yes&sitename=" . urlencode($sitename) . "&period=$period";
    $tpl->js_dialog1("{latency_trend}", $url, 850);
}

// ── Site trend chart content (loaded inside dialog) ──────────────────────────

function site_trend_chart(): void {
    $tpl      = new template_admin();
    $sitename = $_GET["sitename"] ?? "";
    $period   = $_GET["period"] ?? "current";
    $valid    = ['current','hourly','daily','weekly','monthly'];
    if (!in_array($period, $valid)) $period = 'current';
    $fmt = ts_fmt($period);

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/latencystats/trend/" . urlencode($sitename) . "?period=$period"
    ));

    if (!is_object($json) || empty($json->success) || !is_array($json->data->points ?? null)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $points  = $json->data->points;
    $labels  = [];
    $avgData = [];
    $p95Data = [];
    $p99Data = [];

    foreach ($points as $pt) {
        $labels[]  = date($fmt, intval($pt->ts ?? 0));
        $avgData[] = intval($pt->avg_ms ?? 0);
        $p95Data[] = intval($pt->p95_ms ?? 0);
        $p99Data[] = intval($pt->p99_ms ?? 0);
    }

    if (empty($labels)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $labelsJS = "['" . implode("','", $labels) . "']";
    $avgJS    = "[" . implode(",", $avgData) . "]";
    $p95JS    = "[" . implode(",", $p95Data) . "]";
    $p99JS    = "[" . implode(",", $p99Data) . "]";

    $safeSite = htmlspecialchars($sitename);
    $uid = 'trend-' . uniqid();

    $h = [];
    $h[] = "<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-chart-line'></i>&nbsp; $safeSite</h5></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = "<div style='position:relative;height:300px'><canvas id='$uid'></canvas></div>";
    $h[] = "</div></div>";
    $h[] = "<script>";
    $h[] = "new Chart(document.getElementById('$uid'),{type:'line',";
    $h[] = "data:{labels:$labelsJS,datasets:[";
    $h[] = "{label:'{average} (ms)',data:$avgJS,borderColor:'#3498db',backgroundColor:'rgba(52,152,219,0.1)',fill:true,tension:0.3,pointRadius:2,borderWidth:2},";
    $h[] = "{label:'P95 (ms)',data:$p95JS,borderColor:'#e67e22',backgroundColor:'transparent',borderDash:[5,5],tension:0.3,pointRadius:1,borderWidth:2,fill:false},";
    $h[] = "{label:'P99 (ms)',data:$p99JS,borderColor:'#e74c3c',backgroundColor:'transparent',borderDash:[2,2],tension:0.3,pointRadius:1,borderWidth:2,fill:false}";
    $h[] = "]},options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
    $h[] = "plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:15}},";
    $h[] = "tooltip:{callbacks:{label:function(c){return c.dataset.label+': '+c.parsed.y+' ms';}}}},";
    $h[] = "scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:20}},y:{beginAtZero:true,title:{display:true,text:'ms'}}}}});";
    $h[] = "NoSpinner();";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}
