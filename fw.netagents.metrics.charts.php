<?php
/**
 * Agent Metrics Charts Example
 *
 * This file demonstrates how to display CPU, Load, and Memory charts
 * for a network agent using Chart.js and the articarest API.
 *
 * API Endpoints Used:
 * - GET /netagents/metrics/{id}?range=hour|day|3days
 *
 * Usage:
 * - Include this file in your webconsole
 * - Call display_agent_metrics_charts($agent_id) to render charts
 * - Or access directly: fw.netagents.metrics.charts.php?agent_id=1&range=hour
 */

// Include the socket class for API communication
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["popup"])){StartPoint();exit;}
if(isset($_GET["memory"])){memory_pie();exit;}
js();

/**
 * Fetch metrics data from the API
 *
 * @param int $agent_id Agent ID
 * @param string $range Time range: hour, day, 3days
 * @return array|null Metrics data or null on error
 */

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["agent-metrics-js"]);
    $ArticaNetAgents=new ArticaNetAgents($id);
    $Hostname=$ArticaNetAgents->GetAgentHostname();
    return $tpl->js_dialog3("#$id: $Hostname","$page?tabs=yes&agent_id=$id",950);
}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=$_GET["agent_id"];
    $array["{memory}"]="$page?memory=yes&agent_id=$ID";
    $array["{hourly}"]="$page?popup=yes&agent_id=$ID&range=hour";
    $array["{daily}"]="$page?popup=yes&agent_id=$ID&range=day";
    $array["3 {days}"]="$page?popup=yes&agent_id=$ID&range=3days";
    echo $tpl->tabs_default($array);
    return true;
}
function memory_pie():bool{
    $agent_id=intval($_GET["agent_id"]);
    $tpl=new template_admin();
    $t=time();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$agent_id"));

    if(!is_object($json)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{failed_to_contact_agent}"));
        return false;
    }
    if(isset($json->Status) && !$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error??"{unknown_error}")));
        return false;
    }
    if(!isset($json->psmem) || !is_object($json->psmem) || empty($json->psmem->programs)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        echo "<script>NoSpinner();</script>";
        return true;
    }

    $psmem      = $json->psmem;
    $programs   = $psmem->programs;          // []PsMemUsage — values in KiB
    $totalRAM   = floatval($psmem->total_ram  ?? 0);
    $totalSwap  = floatval($psmem->total_swap ?? 0);
    $hasPSS     = (bool)($psmem->has_pss      ?? false);
    $hasSwapPSS = (bool)($psmem->has_swap_pss ?? false);

    // Sort descending by total (agent already sorts, but ensure it)
    usort($programs, fn($a,$b) => $b->total <=> $a->total);

    // Pie: top 10 + "other" bucket
    $pieLabels=[];
    $pieData=[];
    $otherKib=0.0;
    foreach($programs as $i=>$prog){
        if($i < 10){
            $pieLabels[]=htmlspecialchars($prog->program);
            $pieData[]=round($prog->total, 1);
        }else{
            $otherKib+=$prog->total;
        }
    }
    if($otherKib > 0){
        $pieLabels[]="other";
        $pieData[]=round($otherKib, 1);
    }

    $pieLabelsJson=json_encode($pieLabels);
    $pieDataJson=json_encode($pieData);

    // Info bar values
    $usedKib=array_sum(array_map(fn($p)=>$p->total, $programs));
    $usedFmt=FormatBytes($usedKib);
    $ramFmt=FormatBytes($totalRAM);
    $swapFmt=FormatBytes($totalSwap);
    $pssNote=$hasPSS
        ? "<span class='label label-primary' style='color:#fff'>PSS</span>"
        : "<span class='label label-default' style='color:#fff'>{no_pss}</span>";

    // Table rows
    $showSwap=$hasSwapPSS || array_sum(array_map(fn($p)=>$p->swap, $programs)) > 0;
    $rows="";
    $TRCLASS=null;
    foreach($programs as $prog){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $name=htmlspecialchars($prog->program);
        $cnt=intval($prog->count);
        $priv=FormatBytes($prog->private);
        $shr=FormatBytes($prog->shared);
        $tot=FormatBytes($prog->total);
        $swap="-";
        $swapInt=$prog->swap??0;
        if($swapInt>0) {
            $swap = $showSwap ? FormatBytes($swapInt) : "";
        }
        $strongOn="<span class='text-muted'>";
        $strongOff="</span>";
        if($prog->total>(500*1024)){
            $strongOn="<strong>";
            $strongOff="</strong>";
        }
        if($prog->total>(1024*1024)){
            $strongOn="<strong class='text-warning'>";
            $strongOff="</strong>";
        }
        if($prog->total>(2048*1024)){
            $strongOn="<strong class='text-danger'>";
            $strongOff="</strong>";
        }
        $swapTd=$showSwap?"<td style='text-align:left'>$strongOn$swap$strongOff</td>":"";
        $rows.="<tr class='$TRCLASS'>";
        $rows.="<td><i class='fas fa-microchip'></i>&nbsp;$strongOn$name$strongOff</td>";
        $rows.="<td style='text-align:left'>$strongOn$cnt$strongOff</td>";
        $rows.="<td style='text-align:left'>$strongOn$priv$strongOff</td>";
        $rows.="<td style='text-align:left'>$strongOn$shr$strongOff</td>";
        $rows.="<td style='text-align:left'>$strongOn$tot$strongOff</td>";
        $rows.=$swapTd;
        $rows.="</tr>";
    }
    $swapTh=$showSwap?"<th data-sortable=true data-type='numeric'>{swap}</th>":"";

    $html=[];
    // Info bar
    $html[]="<div style='padding:6px 10px;margin-top:10px;margin-bottom:10px;background:#f5f5f5;border-left:3px solid #1c84c6;font-size:12px'>";
    $html[]="$pssNote &nbsp;";
    $html[]="{used}: <strong>$usedFmt</strong> &nbsp;|&nbsp; RAM: <strong>$ramFmt</strong>";
    if($totalSwap>0){$html[].=" &nbsp;|&nbsp; Swap: <strong>$swapFmt</strong>";}
    $html[]="</div>";

    // Chart full-width with legend on the right, table below
    $html[]="<div style='position:relative;height:480px;margin-bottom:20px'><canvas id='psmem-pie-$t'></canvas></div>";

    $html[]="<table id='psmem-table-$t' class='footable table table-stripped' data-page-size='25'>";
    $html[]="<thead><tr>";
    $html[]="<th data-sortable=true data-type='text'>{program}</th>";
    $html[]="<th data-sortable=true data-type='numeric'>{processes}</th>";
    $html[]="<th data-sortable=true data-type='numeric'>{private}</th>";
    $html[]="<th data-sortable=true data-type='numeric'>{shared}</th>";
    $html[]="<th data-sortable=true data-type='numeric'>{total}</th>";
    $html[]=$swapTh;
    $html[]="</tr></thead>";
    $html[]="<tbody>$rows</tbody>";
    $html[]="<tfoot><tr><td colspan='6'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";

    $html[]="<script>
var chartColors=['#36A2EB','#FF6384','#4BC0C0','#FF9F40','#9966FF','#FFCD56','#C9CBCF','#7BC225','#E74C3C','#3498DB','#95A5A6'];
var psmemCtx=document.getElementById('psmem-pie-$t').getContext('2d');
new Chart(psmemCtx,{
    type:'doughnut',
    data:{
        labels:$pieLabelsJson,
        datasets:[{data:$pieDataJson,backgroundColor:chartColors}]
    },
    options:{
        responsive:true,maintainAspectRatio:false,
        plugins:{
            legend:{position:'right',labels:{boxWidth:12,font:{size:11},padding:6}},
            tooltip:{callbacks:{label:function(c){
                var kb=c.raw;
                if(kb>1048576) return c.label+': '+(kb/1048576).toFixed(1)+' GB';
                if(kb>1024)    return c.label+': '+(kb/1024).toFixed(1)+' MB';
                return c.label+': '+kb.toFixed(0)+' KB';
            }}}
        }
    }
});
NoSpinner();
\$('#psmem-table-$t').footable({
    'filtering':{'enabled':true},
    'sorting':{'enabled':true},
    'paging':{'size':25}
});
</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function fetch_agent_metrics($agent_id, $range = 'hour') {
    $sock = new sockets();
    $data = $sock->REST_API("/netagents/metrics/$agent_id?range=$range");

    if (empty($data)) {
        return null;
    }

    $json = json_decode($data, true);
    if (!is_array($json) || isset($json['Error'])) {
        return null;
    }

    return $json;
}

/**
 * Display the full metrics charts page
 *
 * @param int $agent_id Agent ID
 * @param string $range Time range: hour, day, 3days
 */
function display_agent_metrics_charts($agent_id, $range = 'hour') {
    $metrics = fetch_agent_metrics($agent_id, $range);

    if (!$metrics) {
        echo "<div class='alert alert-warning'>No metrics data available for this agent #$agent_id.</div>";
        return;
    }

    // Prepare data for charts
    $labels = [];
    $cpu_load_1 = [];
    $cpu_load_5 = [];
    $cpu_load_15 = [];
    $memory_pct = [];
    $memory_used = [];

    foreach ($metrics['metrics'] as $point) {
        $timestamp = strtotime($point['timestamp']);
        $labels[] = date('H:i', $timestamp);
        $cpu_load_1[] = round($point['cpu_load_1'], 2);
        $cpu_load_5[] = round($point['cpu_load_5'], 2);
        $cpu_load_15[] = round($point['cpu_load_15'], 2);
        $memory_pct[] = round($point['memory_pct'], 1);
        $memory_used[] = round($point['memory_used'] / (1024 * 1024 * 1024), 2); // Convert to GB
    }

    $labels_json = json_encode($labels);
    $cpu_load_1_json = json_encode($cpu_load_1);
    $cpu_load_5_json = json_encode($cpu_load_5);
    $cpu_load_15_json = json_encode($cpu_load_15);
    $memory_pct_json = json_encode($memory_pct);
    $memory_used_json = json_encode($memory_used);

    $tpl=new template_admin();
    $hostname = htmlspecialchars($metrics['hostname']);
    $from = $tpl->time_to_date(strtotime($metrics['from']),true);
    $to = $tpl->time_to_date(strtotime($metrics['to']),true);
    $count = $metrics['count'];
    $mem_stat=$tpl->_ENGINE_parse_body("{mem_stat}");
    $memory_used=$tpl->_ENGINE_parse_body("{memory_used}");
    $memory_text=$tpl->_ENGINE_parse_body("{memory}");
    $cpu_load=$tpl->_ENGINE_parse_body("{cpu_load}");
    $statistics=$tpl->_ENGINE_parse_body("{statistics}");
    $records=$tpl->_ENGINE_parse_body("{records}");
    $ico=ico_arrow_right;
    echo $tpl->_ENGINE_parse_body(<<<HTML
<div class="row" style="margin-top:10px">
    <div class="col-lg-12">
        <div class="ibox">
            <div class="ibox-title">
                <h5 style="font-size:18px">$statistics: $hostname</h5>
                <div class="ibox-tools">
                    <strong style="font-size:18px">$from <i class="$ico"></i> $to</strong>
                    <span class="label label-primary">$count $records</span>
                </div>
            </div>
            <div class="ibox-content">
                <!-- CPU Load Chart -->
                <div class="row">
                    <div class="col-lg-12">
                        <h4>$cpu_load</h4>
                        <div style="height: 300px;">
                            <canvas id="cpuLoadChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Memory Charts -->
                <div class="row" style="margin-top: 30px;">
                    <div class="col-lg-6">
                        <h4>$mem_stat (%)</h4>
                        <div style="height: 250px;">
                            <canvas id="memoryPctChart"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <h4>$memory_used (GB)</h4>
                        <div style="height: 250px;">
                            <canvas id="memoryUsedChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
// Chart.js configuration
var chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: {
            display: true,
            ticks: {
                maxTicksLimit: 20
            }
        },
        y: {
            beginAtZero: true
        }
    },
    plugins: {
        legend: {
            position: 'top',
        }
    },
    elements: {
        point: {
            radius: 1
        },
        line: {
            tension: 0.3
        }
    }
};

// CPU Load Chart
var cpuLoadCtx = document.getElementById('cpuLoadChart').getContext('2d');
var cpuLoadChart = new Chart(cpuLoadCtx, {
    type: 'line',
    data: {
        labels: {$labels_json},
        datasets: [
            {
                label: 'Load 1min',
                data: {$cpu_load_1_json},
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                fill: false
            },
            {
                label: 'Load 5min',
                data: {$cpu_load_5_json},
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                fill: false
            },
            {
                label: 'Load 15min',
                data: {$cpu_load_15_json},
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                fill: false
            }
        ]
    },
    options: chartOptions
});

// Memory Percentage Chart
var memoryPctCtx = document.getElementById('memoryPctChart').getContext('2d');
var memoryPctChart = new Chart(memoryPctCtx, {
    type: 'line',
    data: {
        labels: {$labels_json},
        datasets: [{
            label: '$memory_text %',
            data: $memory_pct_json,
            borderColor: 'rgb(153, 102, 255)',
            backgroundColor: 'rgba(153, 102, 255, 0.2)',
            fill: true
        }]
    },
    options: Object.assign({}, chartOptions, {
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        }
    })
});

// Memory Used Chart
var memoryUsedCtx = document.getElementById('memoryUsedChart').getContext('2d');
var memoryUsedChart = new Chart(memoryUsedCtx, {
    type: 'line',
    data: {
        labels: $labels_json,
        datasets: [{
            label: '$memory_text (GB)',
            data: $memory_used_json,
            borderColor: 'rgb(255, 159, 64)',
            backgroundColor: 'rgba(255, 159, 64, 0.2)',
            fill: true
        }]
    },
    options: chartOptions
});

// Function to reload metrics with different range
function loadMetrics(agentId, range) {
    window.location.href = 'fw.netagents.metrics.charts.php?agent_id=' + agentId + '&range=' + range;
}
</script>
HTML);
}

/**
 * Get JavaScript code for AJAX-based chart updates
 * Use this for dynamic chart updates without page reload
 *
 * @param int $agent_id Agent ID
 * @return string JavaScript code
 */
function get_metrics_ajax_js($agent_id) {
    return <<<JS
<script>
function fetchMetricsAjax(agentId, range, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '/netagents/metrics/' + agentId + '?range=' + range, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var data = JSON.parse(xhr.responseText);
            callback(data);
        }
    };
    xhr.send();
}

function updateCharts(data) {
    var labels = [];
    var cpuLoad1 = [];
    var cpuLoad5 = [];
    var cpuLoad15 = [];
    var memoryPct = [];

    data.metrics.forEach(function(point) {
        var d = new Date(point.timestamp);
        labels.push(d.getHours() + ':' + String(d.getMinutes()).padStart(2, '0'));
        cpuLoad1.push(point.cpu_load_1.toFixed(2));
        cpuLoad5.push(point.cpu_load_5.toFixed(2));
        cpuLoad15.push(point.cpu_load_15.toFixed(2));
        memoryPct.push(point.memory_pct.toFixed(1));
    });

    // Update chart data
    cpuLoadChart.data.labels = labels;
    cpuLoadChart.data.datasets[0].data = cpuLoad1;
    cpuLoadChart.data.datasets[1].data = cpuLoad5;
    cpuLoadChart.data.datasets[2].data = cpuLoad15;
    cpuLoadChart.update();

    memoryPctChart.data.labels = labels;
    memoryPctChart.data.datasets[0].data = memoryPct;
    memoryPctChart.update();
}

// Auto-refresh every 5 minutes
setInterval(function() {
    fetchMetricsAjax({$agent_id}, currentRange, updateCharts);
}, 300000);
</script>
JS;
}

function StartPoint():bool{
    $tpl=new template_admin();
    if (isset($_GET['agent_id'])) {
        $agent_id = intval($_GET['agent_id']);
        $range = isset($_GET['range']) ? $_GET['range'] : 'hour';

        // Validate range
        if (!in_array($range, ['hour', 'day', '3days'])) {
            $range = 'hour';
        }
    }

    display_agent_metrics_charts($agent_id,$range);
    return true;
}

// Direct access handling



?>
