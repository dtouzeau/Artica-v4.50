<?php
/**
 * DecisionIP Sent Events Dashboard
 * Displays metrics about posted events to DecisionIP server
 * Uses Chart.js for visualization
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"] = new sockets();
$users = new usersMenus();
if (!$users->AsFirewallManager) { exit(); }

if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
}

if(isset($_GET["widgets-top"])){widget_tops();exit;}
if (isset($_GET["metrics-summary"])) { api_metrics_summary(); exit; }
if (isset($_GET["daily-metrics"])) { api_daily_metrics(); exit; }
if (isset($_GET["hourly-metrics"])) { api_hourly_metrics(); exit; }
if (isset($_GET["category-breakdown"])) { api_category_breakdown(); exit; }
if (isset($_GET["buffer-count"])) { api_buffer_count(); exit; }
if (isset($_GET["main-content"])) { main_content(); exit; }

page();

function page() {
    $page = CurrentPageName();
    $tpl = new template_admin();

    $html = [];
    $html[] = $tpl->page_header(
        "DecisionIP {sent_events}",
        ico_chart_line,
        "{DecisionIP_sent_events_explain}",
        "$page?main-content=yes",
        "decisionip-sent",
        "progress-decisionip-sent",
        false,
        "table-decisionip-sent"
    );
    if (isset($_GET["main-page"])) {
        $tpl = new template_admin(null, $html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}
function main_content() {
    $page = CurrentPageName();
    $tpl = new template_admin();
    $html = [];

    // Chart.js CDN
    // https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js
    //$html[] = '<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>';

    // CSS Styles
    $html[] = '<style>
        .decisionip-sent-dashboard {
            padding: 20px;
            background: #f5f7fa;
            min-height: 100vh;
        }

        /* Widget Cards Row */
        .widgets-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .widget-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 25px;
            color: #fff;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .widget-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .widget-card.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .widget-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .widget-card.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .widget-card.dark {
            background: linear-gradient(135deg, #434343 0%, #000000 100%);
        }

        .widget-icon {
            font-size: 2.5rem;
            opacity: 0.8;
            margin-bottom: 10px;
        }

        .widget-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 5px;
            font-family: "Segoe UI", sans-serif;
        }

        .widget-label {
            font-size: 0.9rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .widget-sub {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-top: 8px;
        }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }

        .chart-container {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }

        .chart-container.full-width {
            grid-column: 1 / -1;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-title-icon {
            width: 8px;
            height: 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 4px;
        }

        .chart-actions {
            display: flex;
            gap: 10px;
        }

        .chart-btn {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #fff;
            color: #666;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .chart-btn:hover, .chart-btn.active {
            background: #667eea;
            color: #fff;
            border-color: #667eea;
        }

        .chart-canvas-wrapper {
            position: relative;
            height: 300px;
        }

        .chart-canvas-wrapper.tall {
            height: 400px;
        }

        /* Category Legend */
        .category-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #666;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }

        /* Status Indicator */
        .status-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: #e8f5e9;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #2e7d32;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            background: #4caf50;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.9); }
        }

        .status-bar.error {
            background: #ffebee;
            color: #c62828;
        }

        .status-bar.error .status-dot {
            background: #f44336;
            animation: none;
        }

        /* Loading State */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .widgets-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>';

    // HTML Structure
    $html[]="<div id='widgets-top'></div>";

    $html[] = '<div class="decisionip-sent-dashboard">';

    // Hourly Trend Chart - Primary (full-width, tall)
    $html[] = $tpl->_ENGINE_parse_body('
        <div class="charts-grid">
            <div class="chart-container full-width">
                <div class="chart-header">
                    <div class="chart-title">
                        <div class="chart-title-icon"></div>
                        {hourly_trend} - {posted_vs_errors} ({today})
                    </div>
                </div>
                <div class="chart-canvas-wrapper tall">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>
    ');

    // Daily Trend and Top Categories Row
    $html[] = $tpl->_ENGINE_parse_body('
        <div class="charts-grid">
            <div class="chart-container">
                <div class="chart-header">
                    <div class="chart-title">
                        <div class="chart-title-icon"></div>
                        {daily_trend}
                    </div>
                    <div class="chart-actions">
                        <button class="chart-btn active" onclick="setDailyView(7)">7 {days}</button>
                        <button class="chart-btn" onclick="setDailyView(3)">3 {days}</button>
                    </div>
                </div>
                <div class="chart-canvas-wrapper">
                    <canvas id="dailyTrendChart"></canvas>
                </div>
            </div>
            <div class="chart-container">
                <div class="chart-header">
                    <div class="chart-title">
                        <div class="chart-title-icon"></div>
                        {top_categories}
                    </div>
                </div>
                <div class="chart-canvas-wrapper">
                    <canvas id="categoryBarChart"></canvas>
                </div>
            </div>
        </div>
    ');

    // Pie Charts Row
    $html[] = $tpl->_ENGINE_parse_body('
        <div class="charts-grid">
            <div class="chart-container">
                <div class="chart-header">
                    <div class="chart-title">
                        <div class="chart-title-icon"></div>
                        {events_by_category}
                    </div>
                </div>
                <div class="chart-canvas-wrapper">
                    <canvas id="categoryPieChart"></canvas>
                </div>
            </div>
            <div class="chart-container">
                <div class="chart-header">
                    <div class="chart-title">
                        <div class="chart-title-icon"></div>
                        {errors_by_category}
                    </div>
                </div>
                <div class="chart-canvas-wrapper">
                    <canvas id="errorsPieChart"></canvas>
                </div>
            </div>
        </div>
    ');

    $html[] = '</div>'; // End dashboard

    $js=$tpl->RefreshInterval_js("widgets-top",$page,"widgets-top=yes");

    // JavaScript
    $html[] = '<script>
(function() {
    "use strict";

    const API_BASE = "' . $page . '";
    let charts = {};
    let refreshInterval = null;

    // Category colors
    const categoryColors = {
        "WAF": "#FF6384",
        "NGINX-403": "#36A2EB",
        "NGINX-444": "#FFCE56",
        "SPAM": "#4BC0C0",
        "BOT": "#9966FF",
        "ATTACK": "#FF9F40",
        "DEFAULT": "#C9CBCF"
    };

    function getCategoryColor(category) {
        for (const [key, color] of Object.entries(categoryColors)) {
            if (category.toUpperCase().includes(key)) {
                return color;
            }
        }
        // Generate consistent color for unknown categories
        let hash = 0;
        for (let i = 0; i < category.length; i++) {
            hash = category.charCodeAt(i) + ((hash << 5) - hash);
        }
        const hue = hash % 360;
        return `hsl(${hue}, 70%, 50%)`;
    }

    function formatNumber(num) {
        if (num >= 1000000) return (num / 1000000).toFixed(1) + "M";
        if (num >= 1000) return (num / 1000).toFixed(1) + "K";
        return num.toString();
    }
    async function fetchJSON(endpoint) {
        try {
            const response = await fetch(API_BASE + endpoint);
            return await response.json();
        } catch (e) {
            console.error("Fetch error:", e);
            return null;
        }
    }



    async function loadBufferCount() {
        const data = await fetchJSON("?buffer-count=yes");
        if (data && data.status) {
            const el = document.getElementById("bufferCount");
            if (el) el.textContent = formatNumber(data.count || 0);
        }
    }

    async function loadDailyMetrics() {
        const data = await fetchJSON("?daily-metrics=yes");
        if (!data || !data.status || !data.data) return null;
        return data.data;
    }

    async function loadHourlyMetrics() {
        const data = await fetchJSON("?hourly-metrics=yes");
        if (!data || !data.status || !data.data) return null;
        return data.data;
    }

    async function loadCategoryBreakdown(days = 7) {
        const data = await fetchJSON("?category-breakdown=yes&days=" + days);
        if (!data || !data.status || !data.data) return null;
        return data.data;
    }

    function initDailyTrendChart() {
        const ctx = document.getElementById("dailyTrendChart");
        if (!ctx) return;

        charts.dailyTrend = new Chart(ctx, {
            type: "line",
            data: {
                labels: [],
                datasets: [
                    {
                        label: "Posted",
                        data: [],
                        borderColor: "#11998e",
                        backgroundColor: "rgba(17, 153, 142, 0.1)",
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: "Errors",
                        data: [],
                        borderColor: "#f5576c",
                        backgroundColor: "rgba(245, 87, 108, 0.1)",
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: "index" },
                plugins: {
                    legend: { position: "top" }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    function initCategoryPieChart() {
        const ctx = document.getElementById("categoryPieChart");
        if (!ctx) return;

        charts.categoryPie = new Chart(ctx, {
            type: "doughnut",
            data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: "right", labels: { boxWidth: 12 } }
                }
            }
        });
    }

    function initErrorsPieChart() {
        const ctx = document.getElementById("errorsPieChart");
        if (!ctx) return;

        charts.errorsPie = new Chart(ctx, {
            type: "doughnut",
            data: { labels: [], datasets: [{ data: [], backgroundColor: [] }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: "right", labels: { boxWidth: 12 } }
                }
            }
        });
    }

    function initHourlyChart() {
        const ctx = document.getElementById("hourlyChart");
        if (!ctx) return;

        charts.hourly = new Chart(ctx, {
            type: "line",
            data: {
                labels: [],
                datasets: [
                    {
                        label: "Posted",
                        data: [],
                        borderColor: "#11998e",
                        backgroundColor: "rgba(17, 153, 142, 0.1)",
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: "Errors",
                        data: [],
                        borderColor: "#f5576c",
                        backgroundColor: "rgba(245, 87, 108, 0.1)",
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: "index" },
                plugins: { legend: { position: "top" } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    function initCategoryBarChart() {
        const ctx = document.getElementById("categoryBarChart");
        if (!ctx) return;

        charts.categoryBar = new Chart(ctx, {
            type: "bar",
            data: {
                labels: [],
                datasets: [{
                    label: "Events",
                    data: [],
                    backgroundColor: []
                }]
            },
            options: {
                indexAxis: "y",
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    }

    async function updateDailyTrendChart(dailyData) {
        if (!charts.dailyTrend || !dailyData) return;

        const labels = dailyData.map(d => d.date);
        const posted = dailyData.map(d => d.total_posted || 0);
        const errors = dailyData.map(d => d.total_errors || 0);

        charts.dailyTrend.data.labels = labels;
        charts.dailyTrend.data.datasets[0].data = posted;
        charts.dailyTrend.data.datasets[1].data = errors;
        charts.dailyTrend.update();
    }

    async function updateCategoryCharts(summary) {
        if (!summary) return;

        const categories = summary.by_category || {};
        const errors = summary.errors_by_category || {};

        // Category Pie
        if (charts.categoryPie) {
            const labels = Object.keys(categories);
            const data = Object.values(categories);
            const colors = labels.map(l => getCategoryColor(l));

            charts.categoryPie.data.labels = labels;
            charts.categoryPie.data.datasets[0].data = data;
            charts.categoryPie.data.datasets[0].backgroundColor = colors;
            charts.categoryPie.update();
        }

        // Errors Pie
        if (charts.errorsPie) {
            const labels = Object.keys(errors);
            const data = Object.values(errors);
            const colors = labels.map(l => getCategoryColor(l));

            charts.errorsPie.data.labels = labels;
            charts.errorsPie.data.datasets[0].data = data;
            charts.errorsPie.data.datasets[0].backgroundColor = colors;
            charts.errorsPie.update();
        }

        // Category Bar (sorted)
        if (charts.categoryBar) {
            const sorted = Object.entries(categories)
                .sort((a, b) => b[1] - a[1])
                .slice(0, 10);

            const labels = sorted.map(s => s[0]);
            const data = sorted.map(s => s[1]);
            const colors = labels.map(l => getCategoryColor(l));

            charts.categoryBar.data.labels = labels;
            charts.categoryBar.data.datasets[0].data = data;
            charts.categoryBar.data.datasets[0].backgroundColor = colors;
            charts.categoryBar.update();
        }
    }

    async function updateHourlyChart(hourlyData) {
        if (!charts.hourly || !hourlyData) return;

        const labels = hourlyData.map(h => {
            const hour = h.hour.split("-").pop();
            return hour + ":00";
        });
        const posted = hourlyData.map(h => h.total_posted || 0);
        const errors = hourlyData.map(h => h.total_errors || 0);

        charts.hourly.data.labels = labels;
        charts.hourly.data.datasets[0].data = posted;
        charts.hourly.data.datasets[1].data = errors;
        charts.hourly.update();
    }

    window.setDailyView = async function(days) {
        document.querySelectorAll(".chart-actions .chart-btn").forEach(btn => {
            btn.classList.remove("active");
            if (btn.textContent.includes(days)) btn.classList.add("active");
        });

        const data = await loadCategoryBreakdown(days);
        if (data && data.daily_breakdown) {
            // Rebuild daily data from breakdown
            const dailyMap = {};
            for (const [cat, dates] of Object.entries(data.daily_breakdown)) {
                for (const [date, count] of Object.entries(dates)) {
                    if (!dailyMap[date]) dailyMap[date] = { date, total_posted: 0, total_errors: 0 };
                    dailyMap[date].total_posted += count;
                }
            }
            const dailyData = Object.values(dailyMap).sort((a, b) => a.date.localeCompare(b.date));
            updateDailyTrendChart(dailyData);
        }
    };

    async function refreshAll() {
        const summary = await loadMetricsSummary();
        await loadBufferCount();

        const [dailyData, hourlyData] = await Promise.all([
            loadDailyMetrics(),
            loadHourlyMetrics()
        ]);

        updateDailyTrendChart(dailyData);
        updateCategoryCharts(summary);
        updateHourlyChart(hourlyData);
    }
    async function loadMetricsSummary() {
        const data = await fetchJSON("?metrics-summary=yes");
        if (!data || !data.status) {
            return null;
        }
        return data.data;
    }    

    function init() {
        initDailyTrendChart();
        initCategoryPieChart();
        initErrorsPieChart();
        initHourlyChart();
        initCategoryBarChart();

        refreshAll();

        // Auto-refresh every 30 seconds
        refreshInterval = setInterval(refreshAll, 30000);
    }

    // Cleanup on page unload
    window.addEventListener("beforeunload", function() {
        if (refreshInterval) clearInterval(refreshInterval);
        Object.values(charts).forEach(chart => {
            if (chart && chart.destroy) chart.destroy();
        });
    });

    // Initialize
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        setTimeout(init, 100);
    }
})();
'.$js.'
</script>';

    echo $tpl->_ENGINE_parse_body($html);
}

function widget_tops():bool{
    $tpl=new template_admin();
    $data = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/decisionip/metrics"),true);
   // [days_tracked] => 1 [errors_by_category] => Array ( ) [last_updated] => 1769975280 [retention_days] =>
   // 7 [start_time] => 1769967223 [success_rate] => 100 [total_errors] => 0
   // [total_posted] => 174 [uptime_seconds] => 8199 )


    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    if(!isset($data["total_posted"])){
        $html[]="<td>".$tpl->widget_style1("gray-bg","fas fa-comment-alt-check","{sent}","0")."</td>";
        $html[]="<td style='padding-left: 5px;'>".$tpl->widget_style1("gray-bg","fas fa-exclamation-circle","{error}","0")."</td>";
        $html[]="<td style='padding-left: 5px;'>".$tpl->widget_style1("gray-bg","fas fa-exclamation-circle","{error}","0")."</td>";
        $html[]="<td style='padding-left: 5px;'>".$tpl->widget_style1("gray-bg","fad fa-calendar-day","{days}","0")."</td>";
        $html[]="</tr>";
        $html[]="</table>";
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        return true;

    }

    $uptime_seconds = intval($data["uptime_seconds"]);
    $days_tracked = $data["days_tracked"];
    $total_posted = intval($data["total_posted"] ?? 0);
    $postedPerHour = ($uptime_seconds > 0) ? round(($total_posted / $uptime_seconds) * 3600, 2) : 0;
    if ($total_posted == 0) {
        $html[] = "<td>" . $tpl->widget_style1("gray-bg", "fas fa-comment-alt-check", "{sent}", "0") . "</td>";
    } else {
        $html[] = "<td>" . $tpl->widget_style1("green-bg", "fas fa-comment-alt-check", "{sent} $postedPerHour/{hour}", $tpl->FormatNumber($total_posted)) . "</td>";
    }

    if(intval($data["total_errors"])==0) {
        $html[] = "<td style='padding-left: 5px;'>" . $tpl->widget_style1("green-bg", "fas fa-exclamation-circle", "{error}", "0") . "</td>";

    }else{
        $html[] = "<td style='padding-left: 5px;'>" . $tpl->widget_style1("yellow-bg", "fas fa-exclamation-circle", "{posted_errors}", $tpl->FormatNumber($data["total_errors"])) . "</td>";
    }

    $html[]="<td style='padding-left: 5px;'>".$tpl->widget_style1("navy-bg","fad fa-calendar-day","{days}",$days_tracked)."</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

// API Functions
function api_metrics_summary() {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache');

    $data = $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/decisionip/metrics");
    if (empty($data)) {
        echo json_encode(['status' => false, 'error' => 'API unavailable']);
        return;
    }

    $json = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['status' => false, 'error' => 'Invalid JSON response']);
        return;
    }

    echo json_encode(['status' => true, 'data' => $json]);
}

function api_daily_metrics() {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache');

    $data = $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/decisionip/metrics/daily");
    if (empty($data)) {
        echo json_encode(['status' => false, 'error' => 'API unavailable']);
        return;
    }

    $json = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['status' => false, 'error' => 'Invalid JSON response']);
        return;
    }

    echo json_encode([
        'status' => isset($json['status']) ? $json['status'] : true,
        'data' => isset($json['data']) ? $json['data'] : $json
    ]);
}

function api_hourly_metrics() {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache');

    $data = $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/decisionip/metrics/hourly");
    if (empty($data)) {
        echo json_encode(['status' => false, 'error' => 'API unavailable']);
        return;
    }

    $json = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['status' => false, 'error' => 'Invalid JSON response']);
        return;
    }

    echo json_encode([
        'status' => isset($json['status']) ? $json['status'] : true,
        'data' => isset($json['data']) ? $json['data'] : $json
    ]);
}

function api_category_breakdown() {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache');

    $days = isset($_GET['days']) ? intval($_GET['days']) : 7;
    if ($days < 1 || $days > 7) $days = 7;

    $data = $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/decisionip/metrics/categories/{$days}");
    if (empty($data)) {
        echo json_encode(['status' => false, 'error' => 'API unavailable']);
        return;
    }

    $json = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['status' => false, 'error' => 'Invalid JSON response']);
        return;
    }

    echo json_encode([
        'status' => isset($json['status']) ? $json['status'] : true,
        'data' => isset($json['data']) ? $json['data'] : $json
    ]);
}

function api_buffer_count() {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache');

    $data = $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/decisionip/buffer/count");
    if (empty($data)) {
        echo json_encode(['status' => false, 'error' => 'API unavailable', 'count' => 0]);
        return;
    }

    $json = json_decode($data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['status' => false, 'error' => 'Invalid JSON response', 'count' => 0]);
        return;
    }

    echo json_encode([
        'status' => isset($json['status']) ? $json['status'] : true,
        'count' => isset($json['count']) ? $json['count'] : 0
    ]);
}
