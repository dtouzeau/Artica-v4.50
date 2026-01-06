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
    $array["{hourly}"]="$page?popup=yes&agent_id=$ID&range=hour";
    $array["{daily}"]="$page?popup=yes&agent_id=$ID&range=day";
    $array["3 {days}"]="$page?popup=yes&agent_id=$ID&range=3days";
    echo $tpl->tabs_default($array);
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
