<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.modsectools.inc");
include_once(dirname(__FILE__)."/ressources/class.decisionip.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
include_once("ressources/class.resolv.conf.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["main-stats"])){main_stats();exit;}
if(isset($_GET["chart-data"])){chart_data();exit;}
if(isset($_GET["form"])){form_search();exit;}
if(isset($_GET["zoom-js"])){zoomjs();exit;}
if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
page();



function page(): bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html[]= $tpl->page_header("DecisionIP {realtime_graphs}",ico_chart_line,"{DecisionIP_statistics_explain}",
        "$page?main-stats=yes","decisionip-stats","progress-decifw-stats",false,"table-decifw-stats");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function chart_data(){
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $charts = new DecisionIPCharts();
    $stats = $charts->getChartStats();

    if ($stats === null) {
        echo json_encode([
            'Status' => false,
            'Error' => 'Unable to fetch statistics',
            'Data' => null
        ]);
    } else {
        echo json_encode([
            'Status' => true,
            'Data' => $stats
        ]);
    }
}

function main_stats(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $charts = new DecisionIPCharts();

    $html = [];

    // ApexCharts CDN
   // $html[] = '<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>';

    // CSS Styles
    $html[] = '<style>
        .decisionip-dashboard {
            padding: 15px;
        }
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background-color: #1ab394;
            border-radius: 5px;
            padding: 15px 20px;
            margin-bottom: 10px;
            margin-top: 10px;
            text-align: center;
            color: #fff;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .stat-value {
            font-family: "lato", "Trebuchet MS", "Helvetica", sans-serif;
            font-size: 30px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 2.9;
            letter-spacing: 1px;
        }
        .charts-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .chart-box {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .chart-box.full-width {
            grid-column: 1 / -1;
        }
        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
            border-left: 4px solid #667eea;
            padding-left: 10px;
        }
        .top-ips-table {
            width: 100%;
            border-collapse: collapse;
        }
        .top-ips-table th,
        .top-ips-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .top-ips-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #667eea;
            text-transform: uppercase;
            font-size: 0.8rem;
        }
        .top-ips-table tr:hover {
            background: #f8f9fa;
        }
        .ip-badge {
            background: #e8eaf6;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9rem;
        }
        .count-badge {
            background: #667eea;
            color: #fff;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 600;
        }
        .prefix-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .refresh-status {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            padding: 8px 15px;
            background: #e8f5e9;
            border-radius: 20px;
            width: fit-content;
            font-size: 0.85rem;
            color: #2e7d32;
        }
        .refresh-dot {
            width: 8px;
            height: 8px;
            background: #4caf50;
            border-radius: 50%;
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .loading-msg {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>';

    // HTML Structure
    $html[] = '<div class="decisionip-dashboard">';

    // Refresh indicator
    //$html[] =$tpl->_ENGINE_parse_body( '<div class="refresh-status" style="visibility: hidden;"><div class="refresh-dot"></div><span>{live_updates} - {refresh_every} 5 {seconds}</span></div>');

    // Stats summary cards
    $html[] =$tpl->_ENGINE_parse_body('<div class="stats-summary">
        <div class="stat-card">
            <div class="stat-value" id="totalBlocked">0</div>
            <div class="stat-label">{total_blocked}</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="totalUniqueIPs">0</div>
            <div class="stat-label">{unique_ips}</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="last5MinTotal">0</div>
            <div class="stat-label">{last_5_minutes}</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" id="activePrefixes">0</div>
            <div class="stat-label">{active_categories}</div>
        </div>
    </div>');

    // Time series chart (full width)
    $html[] = $tpl->_ENGINE_parse_body('<div class="charts-row">
        <div class="chart-box full-width">
            <div class="chart-title">{realtime_blocked_events} (5 {minutes})</div>
            <div id="timeSeriesChart"></div>
        </div>
    </div>');

    // Pie and Bar charts
    $html[] = $tpl->_ENGINE_parse_body('<div class="charts-row">
        <div class="chart-box">
            <div class="chart-title">{blocked_by_categories} ({last_5_minutes})</div>
            <div id="prefixPieChart"></div>
        </div>
        <div class="chart-box">
            <div class="chart-title">{top_categories} - {total_blocked}</div>
            <div id="prefixBarChart"></div>
        </div>
    </div>');

    // Top IPs table
    $html[] =$tpl->_ENGINE_parse_body( '<div class="charts-row">
        <div class="chart-box full-width">
            <div class="chart-title">{top_ipaddr}</div>
            <div id="topIPsContainer">
                <div class="loading-msg">{loading}...</div>
            </div>
        </div>
    </div>');

    $html[] = '</div>'; // End dashboard

    // JavaScript - wrapped in IIFE to avoid global scope conflicts
    $html[] = '<script>
(function() {
    // Clean up any existing instance
    if (window.DecisionIPStats) {
        if (window.DecisionIPStats.interval) {
            clearInterval(window.DecisionIPStats.interval);
        }
        if (window.DecisionIPStats.timeSeriesChart) {
            window.DecisionIPStats.timeSeriesChart.destroy();
        }
        if (window.DecisionIPStats.prefixPieChart) {
            window.DecisionIPStats.prefixPieChart.destroy();
        }
        if (window.DecisionIPStats.prefixBarChart) {
            window.DecisionIPStats.prefixBarChart.destroy();
        }
    }

    // Create namespace
    window.DecisionIPStats = {
        timeSeriesChart: null,
        prefixPieChart: null,
        prefixBarChart: null,
        interval: null,
        prefixColors: {
            "dipaa": "#FF6384",
            "dipspam": "#36A2EB",
            "dipentor": "#FFCE56",
            "dippx": "#4BC0C0",
            "dipvpn": "#9966FF",
            "diphoney": "#FF9F40"
        },
        prefixLabels: {
            "dipaa": "Anti-Attack",
            "dipspam": "Spam",
            "dipentor": "Entorno",
            "dippx": "Proxy",
            "dipvpn": "VPN",
            "diphoney": "Honeypot"
        }
    };

    var DIP = window.DecisionIPStats;

    function initCharts() {
        var tsEl = document.querySelector("#timeSeriesChart");
        var pieEl = document.querySelector("#prefixPieChart");
        var barEl = document.querySelector("#prefixBarChart");

        if (!tsEl || !pieEl || !barEl) {
            console.error("DecisionIP: Chart containers not found");
            return false;
        }

        // Time Series Chart
        DIP.timeSeriesChart = new ApexCharts(tsEl, {
            chart: {
                type: "area",
                height: 300,
                animations: { enabled: true, easing: "linear", dynamicAnimation: { speed: 1000 } },
                toolbar: { show: true },
                background: "transparent"
            },
            stroke: { curve: "smooth", width: 2 },
            fill: {
                type: "gradient",
                gradient: { shadeIntensity: 1, opacityFrom: 0.5, opacityTo: 0.1, stops: [0, 100] }
            },
            dataLabels: { enabled: false },
            series: [],
            xaxis: {
                type: "datetime",
                labels: { datetimeFormatter: { hour: "HH:mm:ss" } }
            },
            yaxis: { title: { text: "Events" } },
            legend: { position: "top" },
            tooltip: { x: { format: "HH:mm:ss" } }
        });
        DIP.timeSeriesChart.render();

        // Pie Chart
        DIP.prefixPieChart = new ApexCharts(pieEl, {
            chart: { type: "donut", height: 300 },
            series: [],
            labels: [],
            colors: Object.values(DIP.prefixColors),
            legend: { position: "bottom" },
            plotOptions: {
                pie: {
                    donut: {
                        size: "60%",
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: "Total",
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce(function(a, b) { return a + b; }, 0);
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: { enabled: false }
        });
        DIP.prefixPieChart.render();

        // Bar Chart
        DIP.prefixBarChart = new ApexCharts(barEl, {
            chart: { type: "bar", height: 300, toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, borderRadius: 4, distributed: true } },
            series: [{ name: "Blocked", data: [] }],
            xaxis: { categories: [] },
            colors: Object.values(DIP.prefixColors),
            legend: { show: false },
            dataLabels: { enabled: true }
        });
        DIP.prefixBarChart.render();

        return true;
    }

    function updateCharts(data) {
        if (!data) return;

        var totalBlockedEl = document.getElementById("totalBlocked");
        var totalUniqueIPsEl = document.getElementById("totalUniqueIPs");
        var last5MinTotalEl = document.getElementById("last5MinTotal");
        var activePrefixesEl = document.getElementById("activePrefixes");

        if (totalBlockedEl) totalBlockedEl.textContent = formatNumber(data.total_blocked || 0);
        if (totalUniqueIPsEl) totalUniqueIPsEl.textContent = formatNumber(data.total_unique_ips || 0);

        var last5MinTotal = 0;
        var prefixes = data.prefixes || {};
        var topPrefixes = data.top_prefixes || [];

        for (var prefix in prefixes) {
            if (prefixes.hasOwnProperty(prefix)) {
                last5MinTotal += prefixes[prefix].last_5min || 0;
            }
        }
        if (last5MinTotalEl) last5MinTotalEl.textContent = formatNumber(last5MinTotal);
        if (activePrefixesEl) activePrefixesEl.textContent = topPrefixes.length;

        // Time Series
        if (data.time_series && data.time_series.length > 0 && DIP.timeSeriesChart) {
            var seriesData = {};
            var allPrefixes = [];

            data.time_series.forEach(function(point) {
                for (var p in point.counts) {
                    if (point.counts.hasOwnProperty(p) && allPrefixes.indexOf(p) === -1) {
                        allPrefixes.push(p);
                    }
                }
            });

            allPrefixes.forEach(function(p) { seriesData[p] = []; });

            data.time_series.forEach(function(point) {
                var timestamp = point.timestamp * 1000;
                allPrefixes.forEach(function(p) {
                    seriesData[p].push({x: timestamp, y: point.counts[p] || 0});
                });
            });

            var series = [];
            for (var p in seriesData) {
                if (seriesData.hasOwnProperty(p)) {
                    series.push({
                        name: DIP.prefixLabels[p] || p.toUpperCase(),
                        data: seriesData[p],
                        color: DIP.prefixColors[p] || "#888"
                    });
                }
            }
            DIP.timeSeriesChart.updateSeries(series);
        }

        // Pie Chart
        if (topPrefixes.length > 0 && DIP.prefixPieChart) {
            var pieLabels = topPrefixes.map(function(p) { return DIP.prefixLabels[p.prefix] || p.prefix.toUpperCase(); });
            var pieSeries = topPrefixes.map(function(p) { return p.last_5min || 0; });
            DIP.prefixPieChart.updateOptions({ labels: pieLabels });
            DIP.prefixPieChart.updateSeries(pieSeries);
        }

        // Bar Chart
        if (topPrefixes.length > 0 && DIP.prefixBarChart) {
            var sorted = topPrefixes.slice().sort(function(a, b) { return b.count - a.count; });
            var barCategories = sorted.map(function(p) { return DIP.prefixLabels[p.prefix] || p.prefix.toUpperCase(); });
            var barData = sorted.map(function(p) { return p.count || 0; });
            DIP.prefixBarChart.updateOptions({ xaxis: { categories: barCategories } });
            DIP.prefixBarChart.updateSeries([{ name: "Blocked", data: barData }]);
        }

        updateTopIPsTable(prefixes);
    }

    function updateTopIPsTable(prefixes) {
        var container = document.getElementById("topIPsContainer");
        if (!container) return;

        var allTopIPs = [];

        for (var prefix in prefixes) {
            if (prefixes.hasOwnProperty(prefix)) {
                var topIPs = prefixes[prefix].top_ips || [];
                topIPs.forEach(function(ip) {
                    allTopIPs.push({ ip: ip.ip, count: ip.count, prefix: prefix });
                });
            }
        }

        allTopIPs.sort(function(a, b) { return b.count - a.count; });
        allTopIPs = allTopIPs.slice(0, 20);

        if (allTopIPs.length === 0) {
            container.innerHTML = "<div class=\"loading-msg\">No blocked IPs detected yet</div>";
            return;
        }

        var html = "<table class=\"top-ips-table\">";
        html += "<thead><tr><th>#</th><th>IP</th><th>'.$tpl->_ENGINE_parse_body("{categories}").'</th><th>Count</th></tr></thead><tbody>";

        allTopIPs.forEach(function(item, index) {
            var color = DIP.prefixColors[item.prefix] || "#888";
            var label = DIP.prefixLabels[item.prefix] || item.prefix.toUpperCase();
            html += "<tr><td>" + (index + 1) + "</td>";
            html += "<td><span class=\"ip-badge\">" + escapeHtml(item.ip) + "</span></td>";
            html += "<td><span class=\"prefix-badge\" style=\"background:" + color + ";color:#fff\">" + escapeHtml(label) + "</span></td>";
            html += "<td><span class=\"count-badge\">" + formatNumber(item.count) + "</span></td></tr>";
        });

        html += "</tbody></table>";
        container.innerHTML = html;
    }

    function formatNumber(num) {
        if (num >= 1000000) return (num / 1000000).toFixed(1) + "M";
        if (num >= 1000) return (num / 1000).toFixed(1) + "K";
        return num.toString();
    }

    function escapeHtml(text) {
        var div = document.createElement("div");
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function fetchData() {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "' . $page . '?chart-data=yes", true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var result = JSON.parse(xhr.responseText);
                    if (result.Status && result.Data) {
                        updateCharts(result.Data);
                    }
                } catch (e) {
                    console.error("DecisionIP: Error parsing data", e);
                }
            }
        };
        xhr.send();
    }

    function init() {
        if (typeof ApexCharts === "undefined") {
            console.error("DecisionIP: ApexCharts not loaded");
            return;
        }
        if (initCharts()) {
            fetchData();
            DIP.interval = setInterval(fetchData, 5000);
        }
    }

    // Initialize when ready
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        // Small delay to ensure DOM is ready in AJAX context
        setTimeout(init, 100);
    }
})();
</script>';

    echo $tpl->_ENGINE_parse_body($html);
}
