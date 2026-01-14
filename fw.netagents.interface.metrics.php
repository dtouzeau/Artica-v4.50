<?php
/**
 * Agent Network Interface Metrics Charts
 *
 * This file displays network interface bandwidth and traffic charts
 * for a network agent using Chart.js and the articarest API.
 *
 * API Endpoints Used:
 * - GET /netagents/interfaces/{id} - List interfaces
 * - GET /netagents/interfaces/{id}/metrics/{interface}?range=hour|day|week|month&aggregated=1
 *
 * Usage:
 * - Include this file in your webconsole
 * - Access: fw.netagents.interface.metrics.php?agent_id=1&interface=eth0&range=day
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
if(isset($_GET["popup"])){StartPoint();exit;}
js();

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["agent-interface-metrics-js"]);
    $Hostname=getAgentHostname($id);
    return $tpl->js_dialog3("#$id: $Hostname - Interface Metrics","$page?popup=yes&agent_id=$id",950);
}

/**
 * Fetch interfaces list from the API
 */
function fetch_agent_interfaces($agent_id) {
    $sock = new sockets();
    $data = $sock->GET_INFO("/netagents/interfaces/{$agent_id}");

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
 * Fetch interface metrics from the API
 */
function fetch_interface_metrics($agent_id, $interface_name, $range = 'day') {
    $sock = new sockets();
    $interface_encoded = urlencode($interface_name);
    $data = $sock->GET_INFO("/netagents/interfaces/{$agent_id}/metrics/{$interface_encoded}?range={$range}&aggregated=1");

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
 * Format bytes for human readability
 */
function format_bytes_rate($bytes_per_sec) {
    if ($bytes_per_sec >= 1073741824) {
        return number_format($bytes_per_sec / 1073741824, 2) . ' GB/s';
    } elseif ($bytes_per_sec >= 1048576) {
        return number_format($bytes_per_sec / 1048576, 2) . ' MB/s';
    } elseif ($bytes_per_sec >= 1024) {
        return number_format($bytes_per_sec / 1024, 2) . ' KB/s';
    }
    return number_format($bytes_per_sec, 0) . ' B/s';
}

/**
 * Display the interface metrics charts page
 */
function display_interface_metrics_charts($agent_id, $interface_name = '', $range = 'day') {
    $page = CurrentPageName();

    // Get list of interfaces
    $interfaces_data = fetch_agent_interfaces($agent_id);
    if (!$interfaces_data || empty($interfaces_data['interfaces'])) {
        echo '<div class="alert alert-warning">No interfaces data available for this agent.</div>';
        return;
    }

    $interfaces = $interfaces_data['interfaces'];

    // Default to first interface if none specified
    if (empty($interface_name) && count($interfaces) > 0) {
        $interface_name = $interfaces[0]['name'];
    }

    // Build interface selector
    $interface_options = '';
    foreach ($interfaces as $iface) {
        $name = htmlspecialchars($iface['name']);
        $selected = ($iface['name'] === $interface_name) ? 'selected' : '';
        $mac = !empty($iface['mac_address']) ? " ({$iface['mac_address']})" : '';
        $state = $iface['oper_state'] ?? 'unknown';
        $interface_options .= "<option value=\"{$name}\" {$selected}>{$name}{$mac} - {$state}</option>";
    }

    // Fetch metrics for selected interface
    $metrics = fetch_interface_metrics($agent_id, $interface_name, $range);

    if (!$metrics || empty($metrics['metrics'])) {
        echo <<<HTML
<div class="row">
    <div class="col-lg-12">
        <div class="ibox">
            <div class="ibox-title">
                <h5>Interface Metrics</h5>
            </div>
            <div class="ibox-content">
                <div class="form-group">
                    <label>Select Interface:</label>
                    <select id="interfaceSelect" class="form-control" style="width: 300px; display: inline-block;" onchange="changeInterface()">
                        {$interface_options}
                    </select>
                </div>
                <div class="alert alert-info">No metrics data available for interface <strong>{$interface_name}</strong>. Metrics are collected every 5 minutes.</div>
            </div>
        </div>
    </div>
</div>
<script>
function changeInterface() {
    var iface = document.getElementById('interfaceSelect').value;
    window.location.href = '{$page}?popup=yes&agent_id={$agent_id}&interface=' + encodeURIComponent(iface) + '&range={$range}';
}
</script>
HTML;
        return;
    }

    // Prepare data for charts
    $labels = [];
    $rx_bytes_rate = [];
    $tx_bytes_rate = [];
    $rx_packets_rate = [];
    $tx_packets_rate = [];
    $rx_errors = [];
    $tx_errors = [];
    $rx_dropped = [];
    $tx_dropped = [];

    $date_format = ($range === 'hour') ? 'H:i' : (($range === 'month') ? 'm/d' : 'H:i');

    foreach ($metrics['metrics'] as $point) {
        $timestamp = strtotime($point['timestamp']);
        $labels[] = date($date_format, $timestamp);
        $rx_bytes_rate[] = round($point['rx_bytes_rate'] ?? 0, 2);
        $tx_bytes_rate[] = round($point['tx_bytes_rate'] ?? 0, 2);
        $rx_packets_rate[] = round($point['rx_packet_rate'] ?? 0, 2);
        $tx_packets_rate[] = round($point['tx_packet_rate'] ?? 0, 2);
        $rx_errors[] = $point['rx_errors'] ?? 0;
        $tx_errors[] = $point['tx_errors'] ?? 0;
        $rx_dropped[] = $point['rx_dropped'] ?? 0;
        $tx_dropped[] = $point['tx_dropped'] ?? 0;
    }

    $labels_json = json_encode($labels);
    $rx_bytes_rate_json = json_encode($rx_bytes_rate);
    $tx_bytes_rate_json = json_encode($tx_bytes_rate);
    $rx_packets_rate_json = json_encode($rx_packets_rate);
    $tx_packets_rate_json = json_encode($tx_packets_rate);
    $rx_errors_json = json_encode($rx_errors);
    $tx_errors_json = json_encode($tx_errors);
    $rx_dropped_json = json_encode($rx_dropped);
    $tx_dropped_json = json_encode($tx_dropped);

    $interface_safe = htmlspecialchars($interface_name);
    $count = $metrics['count'];

    // Get current interface info
    $current_iface = null;
    foreach ($interfaces as $iface) {
        if ($iface['name'] === $interface_name) {
            $current_iface = $iface;
            break;
        }
    }

    $iface_info = '';
    if ($current_iface) {
        $mac = htmlspecialchars($current_iface['mac_address'] ?? 'N/A');
        $speed = htmlspecialchars($current_iface['speed'] ?? 'N/A');
        $state = htmlspecialchars($current_iface['oper_state'] ?? 'unknown');
        $mtu = intval($current_iface['mtu'] ?? 0);
        $driver = htmlspecialchars($current_iface['driver'] ?? 'N/A');

        $ipv4_list = '';
        if (!empty($current_iface['ipv4_addresses'])) {
            foreach ($current_iface['ipv4_addresses'] as $addr) {
                $ipv4_list .= '<span class="label label-info" style="margin-right: 5px;">' . htmlspecialchars($addr['address']) . '</span>';
            }
        }

        $iface_info = <<<HTML
        <div class="row" style="margin-bottom: 20px;">
            <div class="col-lg-12">
                <table class="table table-bordered table-condensed" style="max-width: 600px;">
                    <tr><td><strong>MAC Address</strong></td><td>{$mac}</td></tr>
                    <tr><td><strong>Speed</strong></td><td>{$speed}</td></tr>
                    <tr><td><strong>State</strong></td><td><span class="label label-{$state_class}">{$state}</span></td></tr>
                    <tr><td><strong>MTU</strong></td><td>{$mtu}</td></tr>
                    <tr><td><strong>Driver</strong></td><td>{$driver}</td></tr>
                    <tr><td><strong>IPv4 Addresses</strong></td><td>{$ipv4_list}</td></tr>
                </table>
            </div>
        </div>
HTML;
    }

    $range_buttons = '';
    foreach (['hour' => 'Last Hour', 'day' => 'Last 24 Hours', 'week' => 'Last Week', 'month' => 'Last Month'] as $r => $label) {
        $active = ($r === $range) ? 'btn-primary' : 'btn-default';
        $range_buttons .= "<button type=\"button\" class=\"btn btn-sm {$active}\" onclick=\"changeRange('{$r}')\">{$label}</button> ";
    }

    echo <<<HTML
<div class="row">
    <div class="col-lg-12">
        <div class="ibox">
            <div class="ibox-title">
                <h5>Interface Metrics: {$interface_safe}</h5>
                <div class="ibox-tools">
                    <span class="label label-primary">{$count} data points</span>
                </div>
            </div>
            <div class="ibox-content">
                <!-- Interface Selector -->
                <div class="form-group">
                    <label>Select Interface:</label>
                    <select id="interfaceSelect" class="form-control" style="width: 300px; display: inline-block;" onchange="changeInterface()">
                        {$interface_options}
                    </select>
                </div>

                <!-- Time Range Selector -->
                <div class="btn-group" style="margin-bottom: 20px;">
                    {$range_buttons}
                </div>

                {$iface_info}

                <!-- Bandwidth Chart (RX/TX bytes per second) -->
                <div class="row">
                    <div class="col-lg-12">
                        <h4>Bandwidth (Bytes/sec)</h4>
                        <div style="height: 300px;">
                            <canvas id="bandwidthChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Packets Chart -->
                <div class="row" style="margin-top: 30px;">
                    <div class="col-lg-6">
                        <h4>Packets/sec</h4>
                        <div style="height: 250px;">
                            <canvas id="packetsChart"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <h4>Errors & Dropped</h4>
                        <div style="height: 250px;">
                            <canvas id="errorsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="angular/js/Chart.min.js"></script>
<script>
var currentAgentId = {$agent_id};
var currentInterface = '{$interface_safe}';
var currentRange = '{$range}';

function changeInterface() {
    var iface = document.getElementById('interfaceSelect').value;
    window.location.href = '{$page}?popup=yes&agent_id=' + currentAgentId + '&interface=' + encodeURIComponent(iface) + '&range=' + currentRange;
}

function changeRange(range) {
    window.location.href = '{$page}?popup=yes&agent_id=' + currentAgentId + '&interface=' + encodeURIComponent(currentInterface) + '&range=' + range;
}

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
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    var label = context.dataset.label || '';
                    var value = context.parsed.y;
                    if (context.chart.canvas.id === 'bandwidthChart') {
                        // Format as bytes
                        if (value >= 1073741824) {
                            return label + ': ' + (value / 1073741824).toFixed(2) + ' GB/s';
                        } else if (value >= 1048576) {
                            return label + ': ' + (value / 1048576).toFixed(2) + ' MB/s';
                        } else if (value >= 1024) {
                            return label + ': ' + (value / 1024).toFixed(2) + ' KB/s';
                        }
                        return label + ': ' + value.toFixed(0) + ' B/s';
                    }
                    return label + ': ' + value;
                }
            }
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

// Bandwidth Chart (RX/TX bytes per second)
var bandwidthCtx = document.getElementById('bandwidthChart').getContext('2d');
var bandwidthChart = new Chart(bandwidthCtx, {
    type: 'line',
    data: {
        labels: {$labels_json},
        datasets: [
            {
                label: 'RX (Download)',
                data: {$rx_bytes_rate_json},
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                fill: true
            },
            {
                label: 'TX (Upload)',
                data: {$tx_bytes_rate_json},
                borderColor: 'rgb(255, 159, 64)',
                backgroundColor: 'rgba(255, 159, 64, 0.1)',
                fill: true
            }
        ]
    },
    options: chartOptions
});

// Packets Chart
var packetsCtx = document.getElementById('packetsChart').getContext('2d');
var packetsChart = new Chart(packetsCtx, {
    type: 'line',
    data: {
        labels: {$labels_json},
        datasets: [
            {
                label: 'RX Packets',
                data: {$rx_packets_rate_json},
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                fill: false
            },
            {
                label: 'TX Packets',
                data: {$tx_packets_rate_json},
                borderColor: 'rgb(153, 102, 255)',
                backgroundColor: 'rgba(153, 102, 255, 0.1)',
                fill: false
            }
        ]
    },
    options: chartOptions
});

// Errors & Dropped Chart
var errorsCtx = document.getElementById('errorsChart').getContext('2d');
var errorsChart = new Chart(errorsCtx, {
    type: 'line',
    data: {
        labels: {$labels_json},
        datasets: [
            {
                label: 'RX Errors',
                data: {$rx_errors_json},
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                fill: false
            },
            {
                label: 'TX Errors',
                data: {$tx_errors_json},
                borderColor: 'rgb(255, 159, 64)',
                backgroundColor: 'rgba(255, 159, 64, 0.1)',
                fill: false
            },
            {
                label: 'RX Dropped',
                data: {$rx_dropped_json},
                borderColor: 'rgb(201, 203, 207)',
                backgroundColor: 'rgba(201, 203, 207, 0.1)',
                fill: false
            },
            {
                label: 'TX Dropped',
                data: {$tx_dropped_json},
                borderColor: 'rgb(255, 205, 86)',
                backgroundColor: 'rgba(255, 205, 86, 0.1)',
                fill: false
            }
        ]
    },
    options: chartOptions
});
</script>
HTML;
}

function StartPoint():bool{
    $tpl=new template_admin();
    $agent_id = intval($_GET['agent_id'] ?? 0);
    $interface_name = $_GET['interface'] ?? '';
    $range = $_GET['range'] ?? 'day';

    // Validate range
    if (!in_array($range, ['hour', 'day', 'week', 'month'])) {
        $range = 'day';
    }

    if ($agent_id <= 0) {
        echo '<div class="alert alert-danger">Invalid agent ID</div>';
        return false;
    }

    display_interface_metrics_charts($agent_id, $interface_name, $range);
    return true;
}
?>
