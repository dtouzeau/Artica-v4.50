<?php
/**
 * Squid Process Performance — Chart.js 4.x dashboards.
 * Data from REST API /squid/process-metrics/* (bbolt-backed, 90-day retention).
 *
 * Charts:  Connections over time, CPU usage per worker, Requests per worker,
 *          Bandwidth per worker, DNS benchmark scores.
 */
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["charts"]))  { render_charts(); exit; }
if(isset($_POST["collect"])) { do_collect(); exit; }
page();

// ── Collect trigger ──────────────────────────────────────────────────────────

function do_collect(): void {
    $tpl = new template_admin();
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/squid/process-metrics/collect", []));
    if (is_object($json) && !empty($json->success)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {collect_now}: OK"));
    } else {
        $err = is_object($json) ? htmlspecialchars($json->error ?? '{error}') : '{error}';
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
    }
}

// ── Colors ───────────────────────────────────────────────────────────────────

function worker_colors(): array {
    return [
        'rgb(54,162,235)',   // blue
        'rgb(255,99,132)',   // red
        'rgb(75,192,192)',   // teal
        'rgb(255,159,64)',   // orange
        'rgb(153,102,255)', // purple
        'rgb(255,206,86)',   // yellow
        'rgb(201,203,207)', // grey
        'rgb(0,200,83)',     // green
    ];
}

function worker_bg_colors(): array {
    return [
        'rgba(54,162,235,0.08)',
        'rgba(255,99,132,0.08)',
        'rgba(75,192,192,0.08)',
        'rgba(255,159,64,0.08)',
        'rgba(153,102,255,0.08)',
        'rgba(255,206,86,0.08)',
        'rgba(201,203,207,0.08)',
        'rgba(0,200,83,0.08)',
    ];
}

// ── Time label formatting ────────────────────────────────────────────────────

function ts_format(int $hours): string {
    if ($hours > 168) return 'M d';
    if ($hours > 48)  return 'M d H:i';
    return 'H:i';
}

// ── Main page ────────────────────────────────────────────────────────────────

function page(): void {
    $tpl  = new template_admin();
    $page = CurrentPageName();

    // Tiny page header
    $TINY_ARRAY["TITLE"]   = "{statistics}: {performance}";
    $TINY_ARRAY["ICO"]     = "fa-solid fa-jet-fighter";
    $TINY_ARRAY["EXPL"]    = "{proxy_service_about}";
    $TINY_ARRAY["URL"]     = "proxy-status";
    $TINY_ARRAY["BUTTONS"] = null;
    $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";

    $h = [];

    // Status badge
    $h[] = "<div id='perf-status' style='margin-bottom:10px'></div>";

    // Time range selector
    $h[] = "<div style='margin-bottom:15px'>";
    $h[] = "  <div class='btn-group' id='perf-range-btns'>";
    $ranges = [6 => '6h', 24 => '24h', 72 => '3d', 168 => '7d', 720 => '30d'];
    foreach ($ranges as $hrs => $label) {
        $active = ($hrs === 24) ? ' active' : '';
        $h[] = "    <button class='btn btn-sm btn-default$active' onclick=\"PerfRange($hrs,this);\">$label</button>";
    }
    $h[] = "  </div>";
    $h[] = "  <button class='btn btn-sm btn-primary' style='margin-left:10px' onclick=\"PerfRefresh();\">";
    $h[] = "    <i class='fas fa-sync-alt'></i> {refresh}</button>";
    $h[] = "  <button class='btn btn-sm btn-default' style='margin-left:5px' onclick=\"PerfCollect();\">";
    $h[] = "    <i class='fas fa-download'></i> {collect_now}</button>";
    $h[] = "</div>";

    // Charts container
    $h[] = "<div id='perf-charts'></div>";

    // JS
    $h[] = "<script>";
    $h[] = "var _perfHours=24;";
    $h[] = "function PerfRange(hrs,btn){";
    $h[] = "  _perfHours=hrs;";
    $h[] = "  \$('#perf-range-btns .btn').removeClass('active');";
    $h[] = "  \$(btn).addClass('active');";
    $h[] = "  PerfRefresh();";
    $h[] = "}";
    $h[] = "function PerfRefresh(){";
    $h[] = "  LoadAjax('perf-charts','$page?charts=yes&hours='+_perfHours);";
    $h[] = "}";
    $h[] = "function PerfCollect(){";
    $h[] = "  \$('#perf-status').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i> {collecting}...</div>');";
    $h[] = "  \$.post('$page',{collect:1},function(r){";
    $h[] = "    \$('#perf-status').html(r);";
    $h[] = "    setTimeout(function(){ \$('#perf-status').html(''); PerfRefresh(); },3000);";
    $h[] = "  });";
    $h[] = "}";
    $h[] = "PerfRefresh();";
    $h[] = "$jstiny";
    $h[] = "</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Charts rendering ─────────────────────────────────────────────────────────

function render_charts(): void {
    $tpl   = new template_admin();
    $hours = intval($_GET["hours"] ?? 24);
    if ($hours < 1) $hours = 24;
    $fmt = ts_format($hours);

    $h = [];
    $h[] = "<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    // ── 1. Status summary ──
    $statusJson = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/process-metrics/status"));
    $latestJson = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/process-metrics/latest"));

    $h[] = render_summary($statusJson, $latestJson);

    // ── 2. Connections chart ──
    $h[] = render_connections_chart($hours, $fmt);

    // ── 3. Workers: CPU + Requests + Bandwidth ──
    $h[] = render_workers_charts($hours, $fmt);

    // ── 4. DNS benchmarks ──
    $h[] = render_dns_chart($hours, $fmt);

    $h[] = "<script>NoSpinner();</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n", $h));
}

// ── Summary widgets ──────────────────────────────────────────────────────────

function render_summary($statusJson, $latestJson): string {
    $h = [];
    $running  = false;
    $dbSize   = '—';
    $samples  = 0;

    if (is_object($statusJson) && !empty($statusJson->success) && is_object($statusJson->data ?? null)) {
        $d = $statusJson->data;
        $running  = !empty($d->running);
        $dbSize   = htmlspecialchars($d->db_size_human ?? '—');
        $samples  = intval($d->counts->samples ?? 0);
    }

    $cnx      = '—';
    $workers  = 0;
    if (is_object($latestJson) && !empty($latestJson->success) && is_object($latestJson->data ?? null)) {
        $cnx     = intval($latestJson->data->cnx ?? 0);
        $workers = count((array)($latestJson->data->workers ?? []));
    }

    $statusBadge = $running
        ? "<span class='badge' style='background:#1ab394;color:#fff'>{running}</span>"
        : "<span class='badge' style='background:#ed5565;color:#fff'>{stopped}</span>";

    $h[] = "<div class='row' style='margin-bottom:20px'>";
    $h[] = _sw('#34495e', 'fas fa-plug', '{connections}', $cnx);
    $h[] = _sw('#1ab394', 'fas fa-microchip', '{workers}', $workers);
    $h[] = _sw('#1c84c6', 'fas fa-database', '{database_size}', $dbSize);
    $h[] = _sw('#676a6c', 'fas fa-chart-bar', '{samples}', $samples);
    $h[] = "<div class='col-md-3 col-sm-6'><div style='background:#f5f5f5;padding:12px 15px;border-radius:4px;text-align:center'>";
    $h[] = "<div style='font-size:16px;font-weight:700'>$statusBadge</div>";
    $h[] = "<div style='font-size:12px;margin-top:4px'><i class='fas fa-heartbeat'></i> {collector}</div>";
    $h[] = "</div></div>";
    $h[] = "</div>";
    return implode("\n", $h);
}

function _sw(string $bg, string $ico, string $label, $val): string {
    $val = htmlspecialchars((string)$val);
    return "<div class='col-md-2 col-sm-4'>"
         . "<div style='background:$bg;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>"
         . "<div style='font-size:22px;font-weight:700'>$val</div>"
         . "<div style='font-size:11px'><i class='$ico'></i> $label</div>"
         . "</div></div>";
}

// ── Connections chart ────────────────────────────────────────────────────────

function render_connections_chart(int $hours, string $fmt): string {
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/squid/process-metrics/connections?hours=$hours"
    ));
    if (!is_object($json) || empty($json->success) || !is_object($json->data ?? null)) {
        $tpl = new template_admin();
        return $tpl->div_info("{no_data}");
    }

    $points = (array)($json->data->points ?? []);
    $resolution = $json->data->resolution ?? 'raw';
    if (empty($points)) {
        $tpl = new template_admin();
        return $tpl->div_info("{no_data}");
    }

    $labels = []; $cnxAvg = []; $cnxMax = [];
    $hasMax = ($resolution !== 'raw');
    foreach ($points as $p) {
        $labels[]  = date($fmt, $p->ts ?? 0);
        $cnxAvg[]  = sprintf('%.1f', $p->cnx ?? 0);
        if ($hasMax) $cnxMax[] = sprintf('%.0f', $p->max_cnx ?? 0);
    }

    $labelsJS = "['" . implode("','", $labels) . "']";
    $avgJS    = "[" . implode(",", $cnxAvg) . "]";

    $datasets = [];
    $datasets[] = "{label:'{connections}" . ($hasMax ? " (avg)" : "") . "',"
                . "data:$avgJS,borderColor:'rgb(54,162,235)',backgroundColor:'rgba(54,162,235,0.1)',"
                . "tension:0.3,fill:true,pointRadius:1,borderWidth:2}";
    if ($hasMax) {
        $maxJS = "[" . implode(",", $cnxMax) . "]";
        $datasets[] = "{label:'{connections} (max)',"
                    . "data:$maxJS,borderColor:'rgba(255,99,132,0.6)',borderDash:[5,3],"
                    . "tension:0.3,fill:false,pointRadius:0,borderWidth:1.5}";
    }
    $dsJS = "[" . implode(",", $datasets) . "]";

    $uid = 'cnx-' . uniqid();
    $h = [];
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-plug'></i>&nbsp; {graph_number_of_connections}</h5>";
    $h[] = "<div class='ibox-tools'><span class='badge' style='background:#676a6c;color:#fff'>$resolution</span></div></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = "<div style='position:relative;height:280px'><canvas id='$uid'></canvas></div>";
    $h[] = "</div></div>";
    $h[] = "<script>";
    $h[] = "new Chart(document.getElementById('$uid'),{type:'line',";
    $h[] = "data:{labels:$labelsJS,datasets:$dsJS},";
    $h[] = "options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
    $h[] = "plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:15}}},";
    $h[] = "scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:20}},y:{beginAtZero:true,title:{display:true,text:'{connections}'}}}}});";
    $h[] = "</script>";
    return implode("\n", $h);
}

// ── Workers charts (CPU + Requests + Bandwidth) ─────────────────────────────

function render_workers_charts(int $hours, string $fmt): string {
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/squid/process-metrics/workers?hours=$hours"
    ));
    if (!is_object($json) || empty($json->success) || !is_object($json->data ?? null)) {
        return '';
    }

    $points = (array)($json->data->points ?? []);
    $resolution = $json->data->resolution ?? 'raw';
    if (empty($points)) return '';

    $labels   = [];
    $cpuData  = []; // kid => [values]
    $reqData  = [];
    $bwData   = [];

    foreach ($points as $p) {
        $labels[] = date($fmt, $p->ts ?? 0);
        $workers = (array)($p->workers ?? []);
        foreach ($workers as $w) {
            $kid = $w->kid ?? '?';
            $cpuData[$kid][] = sprintf('%.3f', $w->cpu_usage ?? 0);
            $reqData[$kid][] = sprintf('%.3f', $w->requests ?? 0);
            $kbIn  = floatval($w->kbytes_in ?? 0);
            $bwData[$kid][]  = sprintf('%.2f', $kbIn);
        }
    }

    $labelsJS = "['" . implode("','", $labels) . "']";
    $colors   = worker_colors();
    $bgColors = worker_bg_colors();

    $h = [];
    // CPU chart
    $h[] = _build_worker_chart(
        'fas fa-microchip', '{percentage} CPU', $resolution,
        $labelsJS, $cpuData, $colors, $bgColors, '%', 'cpu'
    );

    // Requests chart
    $h[] = _build_worker_chart(
        'fas fa-exchange-alt', '{graph_number_of_requests_seconds}', $resolution,
        $labelsJS, $reqData, $colors, $bgColors, 'req/s', 'req'
    );

    // Bandwidth chart
    $h[] = _build_worker_chart(
        'fas fa-tachometer-alt', '{bandwidth} (KB/s in)', $resolution,
        $labelsJS, $bwData, $colors, $bgColors, 'KB/s', 'bw'
    );

    return implode("\n", $h);
}

function _build_worker_chart(
    string $icon, string $title, string $resolution,
    string $labelsJS, array $kidData, array $colors, array $bgColors,
    string $unit, string $prefix
): string {
    $datasets = [];
    $i = 0;
    foreach ($kidData as $kid => $values) {
        $color = $colors[$i % count($colors)];
        $bg    = $bgColors[$i % count($bgColors)];
        $valJS = "[" . implode(",", $values) . "]";
        $datasets[] = "{label:'Kid $kid',data:$valJS,borderColor:'$color',backgroundColor:'$bg',"
                    . "tension:0.3,fill:true,pointRadius:1,borderWidth:2}";
        $i++;
    }
    $dsJS = "[" . implode(",", $datasets) . "]";
    $uid = $prefix . '-' . uniqid();

    $h = [];
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='$icon'></i>&nbsp; $title</h5>";
    $h[] = "<div class='ibox-tools'><span class='badge' style='background:#676a6c;color:#fff'>$resolution</span></div></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = "<div style='position:relative;height:260px'><canvas id='$uid'></canvas></div>";
    $h[] = "</div></div>";
    $h[] = "<script>";
    $h[] = "new Chart(document.getElementById('$uid'),{type:'line',";
    $h[] = "data:{labels:$labelsJS,datasets:$dsJS},";
    $h[] = "options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
    $h[] = "plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:15}}},";
    $h[] = "scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:20}},y:{beginAtZero:true,title:{display:true,text:'$unit'}}}}});";
    $h[] = "</script>";
    return implode("\n", $h);
}

// ── DNS benchmark chart ──────────────────────────────────────────────────────

function render_dns_chart(int $hours, string $fmt): string {
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/squid/process-metrics/dns?hours=$hours"
    ));
    if (!is_object($json) || empty($json->success) || !is_object($json->data ?? null)) {
        return '';
    }

    $points = (array)($json->data->points ?? []);
    if (empty($points)) return '';

    // Collect all server names across all points
    $allServers = [];
    $hasUnbound = false;
    foreach ($points as $p) {
        if (isset($p->unbound_score) && floatval($p->unbound_score) > 0) $hasUnbound = true;
        foreach ((array)($p->servers ?? []) as $srv) {
            $name = $srv->name ?? '';
            if ($name !== '') $allServers[$name] = true;
        }
    }
    $serverNames = array_keys($allServers);
    if (empty($serverNames) && !$hasUnbound) return '';

    $labels    = [];
    $srvData   = []; // name => [scores]
    $unboundData = [];

    foreach ($points as $p) {
        $labels[] = date($fmt, $p->ts ?? 0);
        // Build a lookup for this point's servers
        $srvScores = [];
        foreach ((array)($p->servers ?? []) as $srv) {
            $srvScores[$srv->name ?? ''] = floatval($srv->score ?? 0);
        }
        foreach ($serverNames as $name) {
            $srvData[$name][] = sprintf('%.1f', $srvScores[$name] ?? 0);
        }
        $unboundData[] = sprintf('%.1f', $p->unbound_score ?? 0);
    }

    $labelsJS = "['" . implode("','", $labels) . "']";
    $colors   = ['rgb(54,162,235)', 'rgb(255,99,132)', 'rgb(75,192,192)',
                 'rgb(255,159,64)', 'rgb(153,102,255)', 'rgb(255,206,86)'];

    $datasets = [];
    $i = 0;
    foreach ($srvData as $name => $values) {
        $color  = $colors[$i % count($colors)];
        $valJS  = "[" . implode(",", $values) . "]";
        $eName  = addslashes(htmlspecialchars($name));
        $datasets[] = "{label:'$eName',data:$valJS,borderColor:'$color',tension:0.3,fill:false,pointRadius:2,borderWidth:2}";
        $i++;
    }
    if ($hasUnbound) {
        $ubJS = "[" . implode(",", $unboundData) . "]";
        $datasets[] = "{label:'Unbound (local)',data:$ubJS,borderColor:'rgb(0,200,83)',borderDash:[6,3],tension:0.3,fill:false,pointRadius:2,borderWidth:2}";
    }
    $dsJS = "[" . implode(",", $datasets) . "]";

    $uid = 'dns-' . uniqid();
    $h = [];
    $h[] = "<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-tachometer-alt'></i>&nbsp; DNS {benchmarks} (ms)</h5></div>";
    $h[] = "<div class='ibox-content'>";
    $h[] = "<div style='position:relative;height:260px'><canvas id='$uid'></canvas></div>";
    $h[] = "</div></div>";
    $h[] = "<script>";
    $h[] = "new Chart(document.getElementById('$uid'),{type:'line',";
    $h[] = "data:{labels:$labelsJS,datasets:$dsJS},";
    $h[] = "options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
    $h[] = "plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:15}},";
    $h[] = "tooltip:{callbacks:{label:function(c){return c.dataset.label+': '+c.parsed.y.toFixed(1)+' ms';}}}},";
    $h[] = "scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:20}},y:{beginAtZero:true,title:{display:true,text:'ms'}}}}});";
    $h[] = "</script>";
    return implode("\n", $h);
}
