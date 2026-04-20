<?php
/**
 * System OS Metrics Dashboard
 *
 * Displays CPU, memory, and load average metrics using Chart.js.
 * Data is collected every 3 minutes with 90 days retention.
 *
 * API Endpoints Used:
 * - GET /sysmonitor/status - Check if SysMonitor is running
 * - GET /sysmonitor/stats - Get database statistics and latest metrics
 * - GET /sysmonitor/metrics?hours=N - Historical system metrics
 * - GET /sysmonitor/latest - Most recent metric
 * - GET /sysmonitor/start - Start monitoring
 * - GET /sysmonitor/stop - Stop monitoring
 */

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");

$GLOBALS["CLASS_SOCKETS"] = new sockets();
$tpl = new template_admin();

if(isset($_GET["verbose"])){
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
}
if(!$tpl->xPrivs()){ exit(); }
clean_xss_deep();
if(isset($_GET["purge-js"])){purge_js();exit;}
if(isset($_GET["top-widgets"])){top_widgets();exit;}
if(isset($_GET["api-status"])) { api_status(); exit; }
if(isset($_GET["api-stats"])) { api_stats(); exit; }
if(isset($_GET["api-metrics"])) { api_metrics(); exit; }
if(isset($_GET["api-chart"])) { api_chart(); exit; }
if(isset($_GET["api-latest"])) { api_latest(); exit; }
if(isset($_GET["api-start"])) { api_start(); exit; }
if(isset($_GET["api-stop"])) { api_stop(); exit; }
if(isset($_POST["purge"])) { api_purge(); exit; }
if(isset($_GET["system-metrics"])){system_metrics();exit;}
if(isset($_GET["cpu-metrics"])){cpu_metrics();exit;}
if(isset($_GET["memory-metrics"])){memory_metrics();exit;}
if(isset($_GET["load-metrics"])){load_metrics();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}

// Default: show main page
page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{os_metrics_title}","fas fa-microchip","{os_mon_explain}",
        "$page?status=yes","os-metrics","progress-metrics-os-restart",false,"table-loader-metrics-os");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }

    echo $tpl->_ENGINE_parse_body($html);
}
function purge_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_confirm_delete("{remove_database}",
        "purge","yes","LoadAjaxSilent('os-widgets','$page?top-widgets=yes');");
}

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog6("{os_metrics_title}","$page?tabs=yes",1024);
}

function top_widgets(){
    $tpl=new template_admin();
    $html=array();
    $page=CurrentPageName();
    $status=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/sysmonitor/stats"),true);

    if(!isset($status["data"])){
        $html[]="<table style='width:100%;'>";
        $html[]="<tr>";
        $html[]="<td style='padding:2px'>".$tpl->widget_style1("red-bg",ico_error,"Protocol error","{error}")."</td>";
        $html[]="</tr>";
        $html[]="</table>";
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }

    $db_size_human=$status["data"]["db_size_human"];
    $running=intval($status["data"]["running"]);
    $num_cpu=intval($status["data"]["num_cpu"]);

    if($running==0){
        $running_widget=$tpl->widget_style1("red-bg",ico_stop,"{status}","{stopped}");
    }else{
        $running_widget=$tpl->widget_style1("navy-bg",ico_run,"{status}","{running}");
    }

    // Get latest metrics
    $cpu_percent="-";
    $mem_percent="-";
    $load_avg="-";
    $mem_used="-";

    if(isset($status["data"]["latest"])){
        $latest=$status["data"]["latest"];
        $cpu_percent=round($latest["cpu_percent"],1)."%";
        $mem_percent=round($latest["mem_percent"],1)."%";
        $mem_used=formatMemory($latest["mem_used_mb"]);
    }

    $btn = array();
    $btn[0]["margin"] = 0;
    $btn[0]["name"] = "{remove_database}";
    $btn[0]["icon"] = ico_trash;
    $btn[0]["js"] = "Loadjs('$page?purge-js=yes')";

    $html[]="<table style='width:100%;margin-top:15px'>";
    $html[]="<tr>";
    $html[]="<td style='padding:2px'>".$tpl->widget_style1("navy-bg",ico_database,"{database_size}",$db_size_human,$btn)."</td>";
    $html[]="<td style='padding:2px'>".$tpl->widget_style1("lazur-bg","fas fa-memory","CPU/{memory}","$cpu_percent $mem_percent <small style='color:white'>($mem_used)</small>")."</td>";
    $html[]="<td style='padding:2px'>$running_widget</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function formatMemory($mb){
    if($mb < 1024){
        return $mb." MB";
    }
    return round($mb/1024, 1)." GB";
}

function system_metrics():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $last_hour=$tpl->_ENGINE_parse_body("{last_hour}");
    $last_6_hours=$tpl->_ENGINE_parse_body("{last_6_hours}");
    $last_24_hours=$tpl->_ENGINE_parse_body("{last_24_h}");
    $last_week=$tpl->_ENGINE_parse_body("{last_week}");

    echo "<div style=\"padding:10px;\">
        <h2>$last_hour</h2>
        <div style=\"position:relative;height:200px;width:100%;\"><canvas id=\"sysChart1\"></canvas></div>
        <hr>
        <h2>$last_6_hours</h2>
        <div style=\"position:relative;height:200px;width:100%;\"><canvas id=\"sysChart6\"></canvas></div>
        <hr>
        <h2>$last_24_hours</h2>
        <div style=\"position:relative;height:200px;width:100%;\"><canvas id=\"sysChart24\"></canvas></div>
        <hr>
        <h2>$last_week</h2>
        <div style=\"position:relative;height:200px;width:100%;\"><canvas id=\"sysChart168\"></canvas></div>
    </div>
    <script src=\"js/chart.js\"></script>
    <script>
(function () {
  window.__sysCharts = window.__sysCharts || {};

  function buildConfig(labels, cpu, mem, load1) {
    return {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          { label: 'CPU %', data: cpu, borderColor: '#1ab394', backgroundColor: 'rgba(26,179,148,0.1)', fill: true, tension: 0.4, spanGaps: true, yAxisID: 'y' },
          { label: 'Memory %', data: mem, borderColor: '#ed5565', backgroundColor: 'rgba(237,85,101,0.1)', fill: true, tension: 0.4, spanGaps: true, yAxisID: 'y' },
          { label: 'Load (1m)', data: load1, borderColor: '#f8ac59', backgroundColor: 'rgba(248,172,89,0.1)', fill: false, tension: 0.4, spanGaps: true, yAxisID: 'y1' }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false, animation: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { tooltip: { callbacks: { label: function(ctx) {
          if (ctx.raw === null) return null;
          if (ctx.dataset.label === 'Load (1m)') return ctx.dataset.label + ': ' + ctx.raw.toFixed(2);
          return ctx.dataset.label + ': ' + ctx.raw.toFixed(1) + '%';
        }}}},
        scales: {
          y:  { type:'linear', display:true, position:'left', beginAtZero:true, max:100, title:{display:true,text:'Percentage (%)'}, ticks:{callback:function(v){return v+'%';}} },
          y1: { type:'linear', display:true, position:'right', beginAtZero:true, title:{display:true,text:'Load Average'}, grid:{drawOnChartArea:false} }
        }
      }
    };
  }

  async function renderChart(canvasId, hours, key) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return;
    try {
      var r = await fetch('$page?api-chart=yes&hours=' + hours, { cache: 'no-store' });
      var json = await r.json();
      if (!json.success || !json.data || !json.data.labels) return;
      var d = json.data;
      if (window.__sysCharts[key]) { try { window.__sysCharts[key].destroy(); } catch(e) {} }
      window.__sysCharts[key] = new Chart(canvas.getContext('2d'), buildConfig(d.labels, d.cpu, d.mem, d.load1));
    } catch(e) { console.error('Chart error:', e); }
  }

  setTimeout(function() {
    renderChart('sysChart1', 1, 'sys_1h');
    renderChart('sysChart6', 6, 'sys_6h');
    renderChart('sysChart24', 24, 'sys_24h');
    renderChart('sysChart168', 168, 'sys_week');
  }, 100);
})();
    </script>";
    return true;
}

function cpu_metrics():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $last_hour=$tpl->_ENGINE_parse_body("{last_hour}");
    $last_6_hours=$tpl->_ENGINE_parse_body("{last_6_hours}");
    $last_24_hours=$tpl->_ENGINE_parse_body("{last_24_h}");
    $last_week=$tpl->_ENGINE_parse_body("{last_week}");

    echo "<div style=\"padding:10px;\">
        <h2>$last_hour</h2>
        <div style=\"position:relative;height:150px;width:100%;\"><canvas id=\"cpuChart1\"></canvas></div>
        <hr>
        <h2>$last_6_hours</h2>
        <div style=\"position:relative;height:150px;width:100%;\"><canvas id=\"cpuChart6\"></canvas></div>
        <hr>
        <h2>$last_24_hours</h2>
        <div style=\"position:relative;height:150px;width:100%;\"><canvas id=\"cpuChart24\"></canvas></div>
        <hr>
        <h2>$last_week</h2>
        <div style=\"position:relative;height:150px;width:100%;\"><canvas id=\"cpuChart168\"></canvas></div>
    </div>
    <script src=\"js/chart.js\"></script>
    <script>
(function () {
  window.__cpuCharts = window.__cpuCharts || {};

  function buildConfig(labels, cpu) {
    return {
      type: 'line', data: { labels: labels, datasets: [
        { label: 'CPU %', data: cpu, borderColor: '#1ab394', backgroundColor: 'rgba(26,179,148,0.3)', fill: true, tension: 0.4, spanGaps: true }
      ]},
      options: { responsive: true, maintainAspectRatio: false, animation: false,
        plugins: { tooltip: { callbacks: { label: function(ctx) { if (ctx.raw === null) return null; return ctx.dataset.label + ': ' + ctx.raw.toFixed(1) + '%'; } }}},
        scales: { y: { beginAtZero: true, max: 100, ticks: { callback: function(v) { return v + '%'; } } } }
      }
    };
  }

  async function renderChart(canvasId, hours, key) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return;
    try {
      var r = await fetch('$page?api-chart=yes&hours=' + hours, { cache: 'no-store' });
      var json = await r.json();
      if (!json.success || !json.data || !json.data.labels) return;
      var d = json.data;
      if (window.__cpuCharts[key]) { try { window.__cpuCharts[key].destroy(); } catch(e) {} }
      window.__cpuCharts[key] = new Chart(canvas.getContext('2d'), buildConfig(d.labels, d.cpu));
    } catch(e) { console.error('CPU Chart error:', e); }
  }

  setTimeout(function() {
    renderChart('cpuChart1', 1, 'cpu_1h');
    renderChart('cpuChart6', 6, 'cpu_6h');
    renderChart('cpuChart24', 24, 'cpu_24h');
    renderChart('cpuChart168', 168, 'cpu_week');
  }, 100);
})();
    </script>";
    return true;
}

function memory_metrics():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $last_hour=$tpl->_ENGINE_parse_body("{last_hour}");
    $last_6_hours=$tpl->_ENGINE_parse_body("{last_6_hours}");
    $last_24_hours=$tpl->_ENGINE_parse_body("{last_24_h}");
    $last_week=$tpl->_ENGINE_parse_body("{last_week}");

    echo "<div style=\"padding:10px;\">
        <h2>$last_hour</h2>
        <div style=\"position:relative;height:150px;width:100%;\"><canvas id=\"memChart1\"></canvas></div>
        <hr>
        <h2>$last_6_hours</h2>
        <div style=\"position:relative;height:150px;width:100%;\"><canvas id=\"memChart6\"></canvas></div>
        <hr>
        <h2>$last_24_hours</h2>
        <div style=\"position:relative;height:150px;width:100%;\"><canvas id=\"memChart24\"></canvas></div>
        <hr>
        <h2>$last_week</h2>
        <div style=\"position:relative;height:150px;width:100%;\"><canvas id=\"memChart168\"></canvas></div>
    </div>
    <script src=\"js/chart.js\"></script>
    <script>
(function () {
  window.__memCharts = window.__memCharts || {};

  function formatMB(mb) { if (mb < 1024) return mb + ' MB'; return (mb / 1024).toFixed(1) + ' GB'; }

  function buildConfig(labels, memPercent, memUsed) {
    return {
      type: 'line', data: { labels: labels, datasets: [
        { label: 'Memory %', data: memPercent, borderColor: '#ed5565', backgroundColor: 'rgba(237,85,101,0.3)', fill: true, tension: 0.4, spanGaps: true, yAxisID: 'y' },
        { label: 'Used (MB)', data: memUsed, borderColor: '#1c84c6', backgroundColor: 'rgba(28,132,198,0.1)', fill: false, tension: 0.4, spanGaps: true, yAxisID: 'y1' }
      ]},
      options: { responsive: true, maintainAspectRatio: false, animation: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { tooltip: { callbacks: { label: function(ctx) {
          if (ctx.raw === null) return null;
          if (ctx.dataset.label === 'Used (MB)') return 'Used: ' + formatMB(ctx.raw);
          return ctx.dataset.label + ': ' + ctx.raw.toFixed(1) + '%';
        }}}},
        scales: {
          y:  { type:'linear', display:true, position:'left', beginAtZero:true, max:100, title:{display:true,text:'Percentage (%)'}, ticks:{callback:function(v){return v+'%';}} },
          y1: { type:'linear', display:true, position:'right', beginAtZero:true, title:{display:true,text:'Memory (MB)'}, grid:{drawOnChartArea:false}, ticks:{callback:function(v){return formatMB(v);}} }
        }
      }
    };
  }

  async function renderChart(canvasId, hours, key) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return;
    try {
      var r = await fetch('$page?api-chart=yes&hours=' + hours, { cache: 'no-store' });
      var json = await r.json();
      if (!json.success || !json.data || !json.data.labels) return;
      var d = json.data;
      if (window.__memCharts[key]) { try { window.__memCharts[key].destroy(); } catch(e) {} }
      window.__memCharts[key] = new Chart(canvas.getContext('2d'), buildConfig(d.labels, d.mem, d.mem_used_mb));
    } catch(e) { console.error('Memory Chart error:', e); }
  }

  setTimeout(function() {
    renderChart('memChart1', 1, 'mem_1h');
    renderChart('memChart6', 6, 'mem_6h');
    renderChart('memChart24', 24, 'mem_24h');
    renderChart('memChart168', 168, 'mem_week');
  }, 100);
})();
    </script>";
    return true;
}

function load_metrics():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $last_hour=$tpl->_ENGINE_parse_body("{last_hour}");
    $last_6_hours=$tpl->_ENGINE_parse_body("{last_6_hours}");
    $last_24_hours=$tpl->_ENGINE_parse_body("{load_average_last24h}");
    $last_week=$tpl->_ENGINE_parse_body("{load_average_last7days}");

    echo "<div style=\"padding:10px;\">
        <h2>$last_hour</h2>
        <div style=\"position:relative;height:150px;width:100%;\"><canvas id=\"loadChart1\"></canvas></div>
        <hr>
        <h2>$last_6_hours</h2>
        <div style=\"position:relative;height:150px;width:100%;\"><canvas id=\"loadChart6\"></canvas></div>
        <hr>
        <h2>$last_24_hours</h2>
        <div style=\"position:relative;height:150px;width:100%;\"><canvas id=\"loadChart24\"></canvas></div>
        <hr>
        <h2>$last_week</h2>
        <div style=\"position:relative;height:150px;width:100%;\"><canvas id=\"loadChart168\"></canvas></div>
    </div>
    <script src=\"js/chart.js\"></script>
    <script>
(function () {
  window.__loadCharts = window.__loadCharts || {};

  function buildConfig(labels, load1, load5, load15) {
    return {
      type: 'line', data: { labels: labels, datasets: [
        { label: 'Load 1m', data: load1, borderColor: '#f8ac59', backgroundColor: 'rgba(248,172,89,0.2)', fill: true, tension: 0.4, spanGaps: true },
        { label: 'Load 5m', data: load5, borderColor: '#1c84c6', backgroundColor: 'rgba(28,132,198,0.1)', fill: false, tension: 0.4, spanGaps: true },
        { label: 'Load 15m', data: load15, borderColor: '#23c6c8', backgroundColor: 'rgba(35,198,200,0.1)', fill: false, tension: 0.4, spanGaps: true }
      ]},
      options: { responsive: true, maintainAspectRatio: false, animation: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { tooltip: { callbacks: { label: function(ctx) { if (ctx.raw === null) return null; return ctx.dataset.label + ': ' + ctx.raw.toFixed(2); } }}},
        scales: { y: { beginAtZero: true, title: { display: true, text: 'Load Average' } } }
      }
    };
  }

  async function renderChart(canvasId, hours, key) {
    var canvas = document.getElementById(canvasId);
    if (!canvas) return;
    try {
      var r = await fetch('$page?api-chart=yes&hours=' + hours, { cache: 'no-store' });
      var json = await r.json();
      if (!json.success || !json.data || !json.data.labels) return;
      var d = json.data;
      if (window.__loadCharts[key]) { try { window.__loadCharts[key].destroy(); } catch(e) {} }
      window.__loadCharts[key] = new Chart(canvas.getContext('2d'), buildConfig(d.labels, d.load1, d.load5, d.load15));
    } catch(e) { console.error('Load Chart error:', e); }
  }

  setTimeout(function() {
    renderChart('loadChart1', 1, 'load_1h');
    renderChart('loadChart6', 6, 'load_6h');
    renderChart('loadChart24', 24, 'load_24h');
    renderChart('loadChart168', 168, 'load_week');
  }, 100);
})();
    </script>";
    return true;
}

function api_call($endpoint) {
    return $GLOBALS["CLASS_SOCKETS"]->REST_API($endpoint);
}

function api_status() {
    header('Content-Type: application/json');
    echo api_call("/sysmonitor/status");
}

function api_stats() {
    header('Content-Type: application/json');
    echo api_call("/sysmonitor/stats");
}

function api_metrics() {
    header('Content-Type: application/json');
    $hours = isset($_GET["hours"]) ? intval($_GET["hours"]) : 24;
    echo api_call("/sysmonitor/metrics?hours={$hours}");
}

function api_chart() {
    header('Content-Type: application/json');
    $hours = isset($_GET["hours"]) ? intval($_GET["hours"]) : 24;
    echo api_call("/sysmonitor/chart?hours={$hours}");
}

function api_latest() {
    header('Content-Type: application/json');
    echo api_call("/sysmonitor/latest");
}

function api_start() {
    header('Content-Type: application/json');
    echo api_call("/sysmonitor/start");
}

function api_stop() {
    header('Content-Type: application/json');
    echo api_call("/sysmonitor/stop");
}

function api_purge():bool {
    header('Content-Type: application/json');
    $GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/sysmonitor/purge");
    return true;
}

function tabs():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $tabs["{overview}"]="$page?system-metrics=yes";
    $tabs["CPU"]="$page?cpu-metrics=yes";
    $tabs["{memory}"]="$page?memory-metrics=yes";
    $tabs["{load_average}"]="$page?load-metrics=yes";
    echo $tpl->_ENGINE_parse_body($tpl->tabs_default($tabs));
    return true;
}

function status():bool {
    $page = CurrentPageName();
    $tpl = new template_admin();

    $html=array();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:100%'><div id='os-widgets'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:100%'>";
    $tabs["{overview}"]="$page?system-metrics=yes";
    $tabs["CPU"]="$page?cpu-metrics=yes";
    $tabs["{memory}"]="$page?memory-metrics=yes";
    $tabs["{load_average}"]="$page?load-metrics=yes";
    $html[]=$tpl->tabs_default($tabs);
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $refresh=$tpl->RefreshInterval_js("os-widgets",$page,"top-widgets=yes");
    $html[]=$refresh;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
