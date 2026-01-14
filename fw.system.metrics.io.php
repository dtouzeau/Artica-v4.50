<?php
/**
 * System I/O Metrics Dashboard
 *
 * Displays system and per-process I/O metrics using Chart.js.
 * Data is collected every 3 minutes for 24 hours (detailed) and
 * daily aggregates are kept for 30 days.
 *
 * API Endpoints Used:
 * - GET /iomonitor/status - Check if IOMonitor is running
 * - GET /iomonitor/stats - Get database statistics
 * - GET /iomonitor/metrics/system?hours=N - System-wide I/O metrics
 * - GET /iomonitor/metrics/system/daily?days=N - Daily system aggregates
 * - GET /iomonitor/metrics/top?hours=N&limit=N - Top I/O consuming processes
 * - GET /iomonitor/metrics/process/{name}?hours=N - Per-process history
 * - GET /iomonitor/processes?hours=N - List of processes with I/O activity
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
if(isset($_GET["processhist-js"])){process_hist_js();exit;}
if(isset($_GET["processes-list"])){processes_list();exit;}
if(isset($_GET["processes-metrics"])){top_processes();exit;}
if(isset($_GET["top-widgets"])){top_widgets();exit;}
if(isset($_GET["api-status"])) { api_status(); exit; }
if(isset($_GET["api-stats"])) { api_stats(); exit; }
if(isset($_GET["system-metrics"])){system_metrics();exit;}
if(isset($_GET["system-metrics-build"])){system_metrics_build();exit;}
if(isset($_GET["api-system-metrics"])) { api_system_metrics(); exit; }
if(isset($_GET["process-hist-popup"])){process_hist_popup();exit;}

if(isset($_GET["api-daily-metrics"])) { api_daily_metrics(); exit; }
if(isset($_GET["api-top-processes"])) { api_top_processes(); exit; }
if(isset($_GET["api-process-history"])) { api_process_history(); exit; }
if(isset($_GET["api-processes-list"])) { api_processes_list(); exit; }
if(isset($_GET["api-start"])) { api_start(); exit; }
if(isset($_GET["api-stop"])) { api_stop(); exit; }
if(isset($_GET["status"])){status();exit;}

// Default: show main page
page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{io_metrics_title}","fas fa-chart-line","{io_mon_explain}",
        "$page?status=yes","io-metrics","progress-metrics-io-restart",false,"table-loader-metrics-io");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}

function top_widgets(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<table style='width:100%;'>";
    $status=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/iomonitor/stats"),true);
    if(!isset($status["data"])){
        $html[]="<tr>";
        $html[]="<td style='padding:2px'>".$tpl->widget_style1("red-bg",ico_error,"Protocol error","{error}")."</td>";
        $html[]="</tr>";
        $html[]="</table>";
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }

    $db_size_human=$status["data"]["db_size_human"];
    $running=intval($status["data"]["running"]);
    if($running==0){
        $running_widget=$tpl->widget_style1("red-bg",ico_stop,"{status}","{stopped}");
    }else{
        $running_widget=$tpl->widget_style1("navy-bg",ico_run,"{status}","{running}");;
    }

    $timestamp="";
    $read=0;
    $write=0;
    $stats=json_decode(api_call("/iomonitor/metrics/system?hours=1"),true);
    if(isset($stats["data"])){
        foreach ($stats["data"] as $stat){
            $timestamp=$tpl->time_to_date($stat["timestamp"],true);
            $read=intval($stat["read"]);
            $write=intval($stat["write"]);

        }
    }
    if(strlen($timestamp)==0){
        $process_widget=$tpl->widget_style1("gray-bg",ico_terminal,"{read}/{write}","-");
    }else{
        $readStr=formatWidg($read);
        $write=formatWidg($write);
        $process_widget=$tpl->widget_style1("navy-bg",ico_terminal,
            "{read}/{write} - $timestamp","$readStr&nbsp;/&nbsp;$write");
    }

    $html[]="<table style='width:100%;margin-top:15px'>";
    $html[]="<tr>";
    $html[]="<td style='padding:2px'>".$tpl->widget_style1("lazur-bg",ico_database,"{database_size}",$db_size_human)."</td>";
    $html[]="<td style='padding:2px'>$process_widget</td>";
    $html[]="<td style='padding:2px'>$running_widget</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function formatWidg($value):string{
    if($value<1024){
        return "$value Bytes";
    }
    $readK=intval($value)/1024;
    return FormatBytes($readK);
}
function top_processes():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $last_hour=$tpl->_ENGINE_parse_body("{last_hour}");
    $last_6_hours=$tpl->_ENGINE_parse_body("{last_6_hours}");
    $last_24_hours=$tpl->_ENGINE_parse_body("{last_24_h}");
    $last_week=$tpl->_ENGINE_parse_body("{last_week}");

    echo "<div style=\"padding:10px;\">
        <h2>$last_hour</h2>
        <div style=\"position:relative;height:200px;width:100%;\"><canvas id=\"topProcessChart1\"></canvas></div>
        <hr>
        <h2>$last_6_hours</h2>
        <div style=\"position:relative;height:200px;width:100%;\"><canvas id=\"topProcessChart6\"></canvas></div>
        <hr>
        <h2>$last_24_hours</h2>
        <div style=\"position:relative;height:200px;width:100%;\"><canvas id=\"topProcessChart24\"></canvas></div>
        <hr>
    </div>
    <script src=\"js/chart.js\"></script>
    <script>
(function () {
  window.formatBytes = window.formatBytes || function (bytes) {
    const n = Number(bytes);
    if (!isFinite(n) || n <= 0) return \"0 B\";
    const units = [\"B\", \"KB\", \"MB\", \"GB\", \"TB\", \"PB\"];
    const i = Math.min(Math.floor(Math.log(n) / Math.log(1024)), units.length - 1);
    const v = n / Math.pow(1024, i);
    return (v >= 10 || i === 0 ? v.toFixed(0) : v.toFixed(1)) + \" \" + units[i];
  };

  window.__topProcCharts = window.__topProcCharts || {};

  function buildBarConfig(labels, reads, writes) {
    return {
      type: \"bar\",
      data: {
        labels: labels,
        datasets: [
          { label: \"Read\", data: reads, backgroundColor: \"rgba(26,179,148,0.8)\", borderColor: \"#1ab394\", borderWidth: 1 },
          { label: \"Write\", data: writes, backgroundColor: \"rgba(237,85,101,0.8)\", borderColor: \"#ed5565\", borderWidth: 1 }
        ]
      },
      options: {
        indexAxis: \"y\",
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: {
          tooltip: {
            callbacks: {
              label: function(ctx) { return ctx.dataset.label + \": \" + window.formatBytes(ctx.raw); }
            }
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            ticks: {
              callback: function(v) { return window.formatBytes(v); }
            }
          }
        }
      }
    };
  }

  async function renderTopProcessChart(canvasId, hours, key) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) { console.error(\"Canvas not found: \" + canvasId); return; }

    try {
      const r = await fetch(\"$page?api-top-processes=yes&hours=\" + hours + \"&limit=20\", { cache: \"no-store\" });
      const data = await r.json();
      if (!data.data || data.data.length === 0) {
        console.log(\"No process data for \" + hours + \"h\");
        return;
      }

      const labels = [];
      const reads = [];
      const writes = [];

      for (const p of data.data) {
        labels.push(p.process_name || p.name || \"unknown\");
        reads.push(Number(p.total_read || p.read) || 0);
        writes.push(Number(p.total_write || p.write) || 0);
      }

      if (window.__topProcCharts[key]) {
        try { window.__topProcCharts[key].destroy(); } catch(e) {}
      }

      const ctx = canvas.getContext(\"2d\");
      window.__topProcCharts[key] = new Chart(ctx, buildBarConfig(labels, reads, writes));
    } catch(e) {
      console.error(\"Top process chart error:\", e);
    }
  }

  setTimeout(function() {
    renderTopProcessChart(\"topProcessChart1\", 1, \"top_1h\");
    renderTopProcessChart(\"topProcessChart6\", 6, \"top_6h\");
    renderTopProcessChart(\"topProcessChart24\", 24, \"top_24h\");
    // renderTopProcessChart(\"topProcessChart168\", 168, \"top_week\");
  }, 100);
})();
    </script>";
    return true;
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
        <div style=\"position:relative;height:150px;width:100%;\"><canvas id=\"systemChart\"></canvas></div>
        <hr>
        <h2>$last_6_hours</h2>
        <div style=\"position:relative;height:150px;width:100%;\"><canvas id=\"systemChart2\"></canvas></div>
        <hr>
        <h2>$last_24_hours</h2>
        <div style=\"position:relative;height:150px;width:100%;\"><canvas id=\"systemChart3\"></canvas></div>
    </div>
    <script src=\"js/chart.js\"></script>
    <script>
(function () {
  window.formatBytes = window.formatBytes || function (bytes) {
    const n = Number(bytes);
    if (!isFinite(n) || n <= 0) return \"0 B\";
    const units = [\"B\", \"KB\", \"MB\", \"GB\", \"TB\", \"PB\"];
    const i = Math.min(Math.floor(Math.log(n) / Math.log(1024)), units.length - 1);
    const v = n / Math.pow(1024, i);
    return (v >= 10 || i === 0 ? v.toFixed(0) : v.toFixed(1)) + \" \" + units[i];
  };

  window.__ioCharts = window.__ioCharts || {};

  function buildConfig(labels, reads, writes) {
    return {
      type: \"line\",
      data: {
        labels: labels,
        datasets: [
          { label: \"Read\", data: reads, borderColor: \"#1ab394\", backgroundColor: \"rgba(26,179,148,0.2)\", fill: true, tension: 0.4 },
          { label: \"Write\", data: writes, borderColor: \"#ed5565\", backgroundColor: \"rgba(237,85,101,0.2)\", fill: true, tension: 0.4 }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: {
          tooltip: {
            callbacks: {
              label: function(ctx) { return ctx.dataset.label + \": \" + window.formatBytes(ctx.raw); }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(v) { return window.formatBytes(v); }
            }
          }
        }
      }
    };
  }

  async function renderChart(canvasId, hours, key) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) { console.error(\"Canvas not found: \" + canvasId); return; }

    try {
      const r = await fetch(\"$page?api-system-metrics=yes&hours=\" + hours, { cache: \"no-store\" });
      const data = await r.json();
      if (!data.data || data.data.length === 0) {
        console.log(\"No data for \" + hours + \"h\");
        return;
      }

      const labels = [];
      const reads = [];
      const writes = [];
      const today = new Date();
      today.setHours(0,0,0,0);

      for (const p of data.data) {
        const d = new Date(p.timestamp * 1000);
        const dDay = new Date(d); dDay.setHours(0,0,0,0);
        let label;
        if (dDay.getTime() === today.getTime()) {
          label = d.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
        } else {
          label = d.toLocaleDateString([], {month: 'short', day: 'numeric'}) + ' ' + d.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
        }
        labels.push(label);
        reads.push(Number(p.read) || 0);
        writes.push(Number(p.write) || 0);
      }

      if (window.__ioCharts[key]) {
        try { window.__ioCharts[key].destroy(); } catch(e) {}
      }

      const ctx = canvas.getContext(\"2d\");
      window.__ioCharts[key] = new Chart(ctx, buildConfig(labels, reads, writes));
    } catch(e) {
      console.error(\"Chart error:\", e);
    }
  }

  setTimeout(function() {
    renderChart(\"systemChart\", 1, \"io_1h\");
    renderChart(\"systemChart2\", 6, \"io_6h\");
    renderChart(\"systemChart3\", 24, \"io_24h\");
    renderChart(\"systemChart4\", 168, \"io_week\");
  }, 100);
})();
    </script>";
    return true;
}
function system_metrics_build():bool{
    $page=CurrentPageName();

echo "(function () {
  window.formatBytes = window.formatBytes || function (bytes) {
    const n = Number(bytes);
    if (!isFinite(n) || n <= 0) return \"0 B\";
    const units = [\"B\", \"KB\", \"MB\", \"GB\", \"TB\", \"PB\"];
    const i = Math.min(Math.floor(Math.log(n) / Math.log(1024)), units.length - 1);
    const v = n / Math.pow(1024, i);
    return (v >= 10 || i === 0 ? v.toFixed(0) : v.toFixed(1)) + \" \" + units[i];
  };

  window.__ioCharts = window.__ioCharts || {};

  function buildConfig(labels, reads, writes) {
    return {
      type: \"line\",
      data: {
        labels: labels,
        datasets: [
          { label: \"Read Bytes\", data: reads, fill: true, tension: 0.4 },
          { label: \"Write Bytes\", data: writes, fill: true, tension: 0.4 }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        parsing: false,
        normalized: true,
        plugins: {
          tooltip: {
            callbacks: {
              label: (ctx) => ctx.dataset.label + \": \" + window.formatBytes(ctx.raw)
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: (v) => window.formatBytes(v)
            }
          }
        }
      }
    };
  }

  async function renderOrUpdateChart(canvasId, hours, key) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const existing = window.__ioCharts[key];
    if (existing && existing.canvas !== canvas) {
      try { existing.destroy(); } catch (e) {}
      window.__ioCharts[key] = null;
    }

    const r = await fetch(\"fw.system.metrics.io.php?api-system-metrics=yes&hours=\" + hours, {
      cache: \"no-store\"
    });
    const data = await r.json();
    if (!data.data || data.data.length === 0) return;

    const labels = [];
    const reads = [];
    const writes = [];

    for (const p of data.data) {
      labels.push(new Date(p.timestamp * 1000).toLocaleTimeString());
      reads.push(Number(p.read) || 0);
      writes.push(Number(p.write) || 0);
    }

    const chart = window.__ioCharts[key];
    if (chart && typeof chart.update === \"function\") {
      chart.data.labels = labels;
      chart.data.datasets[0].data = reads;
      chart.data.datasets[1].data = writes;
      chart.update(\"none\");
      return;
    }

    const ctx = canvas.getContext(\"2d\");
    window.__ioCharts[key] = new Chart(ctx, buildConfig(labels, reads, writes));
  }

  renderOrUpdateChart(\"systemChart\", 1, \"io_1h\").catch(console.error);
  renderOrUpdateChart(\"systemChart2\", 6, \"io_6h\").catch(console.error);
  
})();
";
return true;
}




function api_call($endpoint) {
    return $GLOBALS["CLASS_SOCKETS"]->REST_API($endpoint);
}

function api_status() {
    header('Content-Type: application/json');
    echo api_call("/iomonitor/status");
}

function api_stats() {
    header('Content-Type: application/json');
    echo api_call("/iomonitor/stats");
}

function api_system_metrics() {
    header('Content-Type: application/json');
    $hours = isset($_GET["hours"]) ? intval($_GET["hours"]) : 24;
    echo api_call("/iomonitor/metrics/system?hours={$hours}");
}

function api_daily_metrics() {
    header('Content-Type: application/json');
    $days = isset($_GET["days"]) ? intval($_GET["days"]) : 30;
    echo api_call("/iomonitor/metrics/system/daily?days={$days}");
}

function api_top_processes() {
    header('Content-Type: application/json');
    $hours = isset($_GET["hours"]) ? intval($_GET["hours"]) : 24;
    $limit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 20;
    echo api_call("/iomonitor/metrics/top?hours={$hours}&limit={$limit}");
}

function api_process_history() {
    header('Content-Type: application/json');
    $name = isset($_GET["name"]) ? urlencode($_GET["name"]) : "";
    $hours = isset($_GET["hours"]) ? intval($_GET["hours"]) : 24;
    echo api_call("/iomonitor/metrics/process/{$name}?hours={$hours}");
}

function api_processes_list() {
    header('Content-Type: application/json');
    $hours = isset($_GET["hours"]) ? intval($_GET["hours"]) : 24;
    echo api_call("/iomonitor/processes?hours={$hours}");
}

function api_start() {
    header('Content-Type: application/json');
    echo api_call("/iomonitor/start");
}

function api_stop() {
    header('Content-Type: application/json');
    echo api_call("/iomonitor/stop");
}
function process_hist_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $name=$_GET["processhist-js"];
    $hours=$_GET["hours"];
    $namec=urlencode($name);
    return $tpl->js_dialog2("$name - $hours {hours}","$page?process-hist-popup=$namec&hours=$hours");
}
function process_hist_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ProcessName = isset($_GET["process-hist-popup"]) ? $_GET["process-hist-popup"] : "";
    $hours = isset($_GET["hours"]) ? intval($_GET["hours"]) : 24;
    $ProcessNameSafe = htmlspecialchars($ProcessName);
    $ProcessNameJs = addslashes($ProcessName);
    $ProcessNameUrl = urlencode($ProcessName);

    echo "<div style=\"padding:10px;\">
        <h3><i class='fa fa-terminal'></i> $ProcessNameSafe</h3>
        <div style=\"position:relative;height:250px;width:100%;\"><canvas id=\"processHistChart\"></canvas></div>
    </div>
    <script src=\"js/chart.js\"></script>
    <script>
(function () {
  window.formatBytes = window.formatBytes || function (bytes) {
    const n = Number(bytes);
    if (!isFinite(n) || n <= 0) return \"0 B\";
    const units = [\"B\", \"KB\", \"MB\", \"GB\", \"TB\", \"PB\"];
    const i = Math.min(Math.floor(Math.log(n) / Math.log(1024)), units.length - 1);
    const v = n / Math.pow(1024, i);
    return (v >= 10 || i === 0 ? v.toFixed(0) : v.toFixed(1)) + \" \" + units[i];
  };

  function buildConfig(labels, reads, writes) {
    return {
      type: \"line\",
      data: {
        labels: labels,
        datasets: [
          { label: \"Read\", data: reads, borderColor: \"#1ab394\", backgroundColor: \"rgba(26,179,148,0.2)\", fill: true, tension: 0.4 },
          { label: \"Write\", data: writes, borderColor: \"#ed5565\", backgroundColor: \"rgba(237,85,101,0.2)\", fill: true, tension: 0.4 }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: {
          tooltip: {
            callbacks: {
              label: function(ctx) { return ctx.dataset.label + \": \" + window.formatBytes(ctx.raw); }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(v) { return window.formatBytes(v); }
            }
          }
        }
      }
    };
  }

  async function renderProcessChart() {
    const canvas = document.getElementById(\"processHistChart\");
    if (!canvas) return;

    try {
      const r = await fetch(\"$page?api-process-history=yes&name=$ProcessNameUrl&hours=$hours\", { cache: \"no-store\" });
      const data = await r.json();
      if (!data.data || data.data.length === 0) {
        canvas.parentElement.innerHTML = '<div class=\"alert alert-info\">{no_data_available}</div>';
        return;
      }

      const labels = [];
      const reads = [];
      const writes = [];
      const today = new Date();
      today.setHours(0,0,0,0);

      for (const p of data.data) {
        const d = new Date(p.timestamp * 1000);
        const dDay = new Date(d); dDay.setHours(0,0,0,0);
        let label;
        if (dDay.getTime() === today.getTime()) {
          label = d.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
        } else {
          label = d.toLocaleDateString([], {month: 'short', day: 'numeric'}) + ' ' + d.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
        }
        labels.push(label);
        reads.push(Number(p.read_bytes) || 0);
        writes.push(Number(p.write_bytes) || 0);
      }

      const ctx = canvas.getContext(\"2d\");
      new Chart(ctx, buildConfig(labels, reads, writes));
    } catch(e) {
      console.error(\"Process history chart error:\", e);
    }
  }

  setTimeout(renderProcessChart, 100);
})();
    </script>";
    return true;
}
function processes_list():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $hours = isset($_GET["hours"]) ? intval($_GET["hours"]) : 24;
    $ajaxRefresh = isset($_GET["ajax"]) ? true : false;

    // Fetch processes list from API
    $response = api_call("/iomonitor/metrics/top?hours={$hours}&limit=100");
    $data = json_decode($response, true);

    $html = array();

    // Only show dropdown on initial load, not on AJAX refresh
    if (!$ajaxRefresh) {
        $html[] = "<div style='padding:10px;'>";
        $html[] = "<div style='margin-bottom:15px;'>";
        $html[] = "<label>{time_period}: </label>";
        $html[] = "<select onchange=\"LoadAjax('processes-list-content','$page?processes-list=yes&ajax=1&hours='+this.value);\" class='form-control' style='width:auto;display:inline-block;'>";
        $periods = array(1 => "{last_hour}", 6 => "{last_6_hours}", 24 => "{last_24_h}");
        foreach($periods as $h => $label) {
            $selected = ($h == $hours) ? "selected" : "";
            $html[] = "<option value='$h' $selected>$label</option>";
        }
        $html[] = "</select>";
        $html[] = "</div>";
        $html[] = "<div id='processes-list-content'>";
    }

    if (!isset($data["data"]) || count($data["data"]) == 0) {
        $html[] = $tpl->div_info("{no_data_available}");
    } else {
        $processes = $data["data"];

        $html[] = "<table class='table table-bordered table-striped table-hover'>";
        $html[] = "<thead><tr>";
        $html[] = "<th style='width:5%;'>#</th>";
        $html[] = "<th style='width:35%;'>{process_name}</th>";
        $html[] = "<th style='width:20%;text-align:right;'>{read}</th>";
        $html[] = "<th style='width:20%;text-align:right;'>{write}</th>";
        $html[] = "<th style='width:20%;text-align:right;'>{total}</th>";
        $html[] = "</tr></thead><tbody>";

        $rank = 1;
        foreach ($processes as $proc) {
            $nameEnc="";
            if(isset($proc["name"])) {
                $nameEnc = urlencode($proc["name"]);
            }
            $name = isset($proc["process_name"]) ? htmlspecialchars($proc["process_name"]) : (isset($proc["name"]) ? htmlspecialchars($proc["name"]) : "unknown");
            $read = isset($proc["total_read"]) ? intval($proc["total_read"]) : (isset($proc["read"]) ? intval($proc["read"]) : 0);
            $write = isset($proc["total_write"]) ? intval($proc["total_write"]) : (isset($proc["write"]) ? intval($proc["write"]) : 0);
            $total = $read + $write;

            $readStr = FormatBytes($read);
            $writeStr = FormatBytes($write);
            $totalStr = FormatBytes($total);

            $rowStyle = ($rank <= 3) ? "style='background-color:#fff3cd;'" : "";


            $name=$tpl->td_href($name,"","Loadjs('$page?processhist-js=$nameEnc&hours=$hours');");

            $html[] = "<tr $rowStyle>";
            $html[] = "<td style='width:1%' nowrap>$rank</td>";
            $html[] = "<td style='width:99%'><i class='fa fa-terminal'></i> <strong>$name</strong></td>";
            $html[] = "<td style='text-align:right;width:1%' nowrap><span class='label label-success'>$readStr</span></td>";
            $html[] = "<td style='text-align:right;width:1%' nowrap><span class='label label-danger'>$writeStr</span></td>";
            $html[] = "<td style='text-align:right;width:1%' nowrap><strong>$totalStr</strong></td>";
            $html[] = "</tr>";
            $rank++;
        }

        $html[] = "</tbody></table>";
    }

    if (!$ajaxRefresh) {
        $html[] = "</div></div>";
    }

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function status():bool {
    $page = CurrentPageName();
    $tpl = new template_admin();

    $html=array();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:100%'><div id='io-widgets'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:100%'>";
    $tabs["{your_system}"]="$page?system-metrics=yes";
    $tabs["{top_processes}"]="$page?processes-metrics=yes";
    $tabs["{processes}"]="$page?processes-list=yes";
    $html[]=$tpl->tabs_default($tabs);
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $refresh=$tpl->RefreshInterval_js("io-widgets",$page,"top-widgets=yes");
    $html[]=$refresh;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

    $html = '
<div class="wrapper wrapper-content">
    <div class="row">
        <div class="col-lg-12">
            <!-- Header -->
            <div class="io-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="border: none; padding: 0; margin: 0;">System I/O Metrics</h3>
                        <small>Monitoring disk I/O activity per process</small>
                    </div>
                    <div>
                        <span id="service-status" class="status-badge status-stopped">Loading...</span>
                        <button class="btn btn-sm btn-primary" onclick="toggleService()" id="toggle-btn">Start</button>
                        <button class="btn btn-sm btn-default" onclick="refreshAll()"><i class="fa fa-refresh"></i> Refresh</button>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="io-card" id="stats-card">
                <h3>Statistics</h3>
                <div class="stats-grid" id="stats-grid">
                    <div class="stat-box">
                        <div class="value" id="stat-db-size">-</div>
                        <div class="label">Database Size</div>
                    </div>
                    <div class="stat-box">
                        <div class="value" id="stat-collection">3 min</div>
                        <div class="label">Collection Interval</div>
                    </div>
                    <div class="stat-box">
                        <div class="value" id="stat-detailed">24h</div>
                        <div class="label">Detailed Retention</div>
                    </div>
                    <div class="stat-box">
                        <div class="value" id="stat-daily">30d</div>
                        <div class="label">Daily Retention</div>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="io-card">
                <div class="tab-nav">
                    <button class="active" onclick="showTab(\'system\')">System Overview</button>
                    <button onclick="showTab(\'processes\')">Top Processes</button>
                    <button onclick="showTab(\'process-detail\')">Process Detail</button>
                    <button onclick="showTab(\'daily\')">Daily Trends</button>
                </div>

                <!-- System Overview Tab -->
                <div id="tab-system" class="tab-content active">
                    <div class="btn-group-io">
                        <button class="btn btn-sm btn-default" onclick="loadSystemMetrics(1)">1 Hour</button>
                        <button class="btn btn-sm btn-default" onclick="loadSystemMetrics(6)">6 Hours</button>
                        <button class="btn btn-sm btn-primary" onclick="loadSystemMetrics(24)">24 Hours</button>
                    </div>
                    <div class="chart-container">
                        <canvas id="systemChart"></canvas>
                    </div>
                </div>

                <!-- Top Processes Tab -->
                <div id="tab-processes" class="tab-content">
                    <div class="btn-group-io">
                        <button class="btn btn-sm btn-default" onclick="loadTopProcesses(1)">1 Hour</button>
                        <button class="btn btn-sm btn-default" onclick="loadTopProcesses(6)">6 Hours</button>
                        <button class="btn btn-sm btn-primary" onclick="loadTopProcesses(24)">24 Hours</button>
                    </div>
                    <div class="chart-container">
                        <canvas id="processesChart"></canvas>
                    </div>
                    <table class="process-table" id="processes-table">
                        <thead>
                            <tr>
                                <th>Process</th>
                                <th>Total Read</th>
                                <th>Total Write</th>
                                <th>Samples</th>
                                <th>I/O Distribution</th>
                            </tr>
                        </thead>
                        <tbody id="processes-tbody">
                        </tbody>
                    </table>
                </div>

                <!-- Process Detail Tab -->
                <div id="tab-process-detail" class="tab-content">
                    <div style="margin-bottom: 15px;">
                        <label>Select Process: </label>
                        <select id="process-select" onchange="loadProcessDetail()" style="width: 300px; padding: 5px;">
                            <option value="">-- Select a process --</option>
                        </select>
                        <div class="btn-group-io" style="display: inline-block; margin-left: 10px;">
                            <button class="btn btn-sm btn-default" onclick="loadProcessDetail(1)">1h</button>
                            <button class="btn btn-sm btn-default" onclick="loadProcessDetail(6)">6h</button>
                            <button class="btn btn-sm btn-primary" onclick="loadProcessDetail(24)">24h</button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="processDetailChart"></canvas>
                    </div>
                </div>

                <!-- Daily Trends Tab -->
                <div id="tab-daily" class="tab-content">
                    <div class="btn-group-io">
                        <button class="btn btn-sm btn-default" onclick="loadDailyMetrics(7)">7 Days</button>
                        <button class="btn btn-sm btn-default" onclick="loadDailyMetrics(14)">14 Days</button>
                        <button class="btn btn-sm btn-primary" onclick="loadDailyMetrics(30)">30 Days</button>
                    </div>
                    <div class="chart-container">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const page = "' . $page . '";
let systemChart = null;
let processesChart = null;
let processDetailChart = null;
let dailyChart = null;
let currentProcessHours = 24;

// Format bytes to human readable
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return "0 B";
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ["B", "KB", "MB", "GB", "TB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + " " + sizes[i];
}

// Show tab
function showTab(tabName) {
    document.querySelectorAll(".tab-content").forEach(t => t.classList.remove("active"));
    document.querySelectorAll(".tab-nav button").forEach(b => b.classList.remove("active"));
    document.getElementById("tab-" + tabName).classList.add("active");
    event.target.classList.add("active");

    // Load data for the tab
    if (tabName === "system") loadSystemMetrics(24);
    if (tabName === "processes") loadTopProcesses(24);
    if (tabName === "process-detail") loadProcessesList();
    if (tabName === "daily") loadDailyMetrics(30);
}

// Load service status
function loadStatus() {
    fetch(page + "?api-status=yes")
        .then(r => r.json())
        .then(data => {
            const running = data.data && data.data.running;
            const badge = document.getElementById("service-status");
            const btn = document.getElementById("toggle-btn");
            if (running) {
                badge.textContent = "Running";
                badge.className = "status-badge status-running";
                btn.textContent = "Stop";
                btn.className = "btn btn-sm btn-danger";
            } else {
                badge.textContent = "Stopped";
                badge.className = "status-badge status-stopped";
                btn.textContent = "Start";
                btn.className = "btn btn-sm btn-success";
            }
        });
}

// Load stats
function loadStats() {
    fetch(page + "?api-stats=yes")
        .then(r => r.json())
        .then(data => {
            if (data.data) {
                document.getElementById("stat-db-size").textContent = data.data.db_size_human || "-";
            }
        });
}

// Toggle service
function toggleService() {
    const badge = document.getElementById("service-status");
    const isRunning = badge.textContent === "Running";
    const endpoint = isRunning ? "?api-stop=yes" : "?api-start=yes";
    fetch(page + endpoint)
        .then(r => r.json())
        .then(() => {
            loadStatus();
            if (!isRunning) {
                setTimeout(refreshAll, 2000);
            }
        });
}

// Load system metrics
function loadSystemMetrics(hours) {

}

// Load top processes
function loadTopProcesses(hours) {
    fetch(page + "?api-top-processes=yes&hours=" + hours + "&limit=15")
        .then(r => r.json())
        .then(data => {
            if (!data.data || data.data.length === 0) {
                return;
            }

            const processes = data.data.slice(0, 10);
            const labels = processes.map(p => p.name);
            const reads = processes.map(p => p.total_read);
            const writes = processes.map(p => p.total_write);

            if (processesChart) processesChart.destroy();

            const ctx = document.getElementById("processesChart").getContext("2d");
            processesChart = new Chart(ctx, {
                type: "bar",
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Read",
                        data: reads,
                        backgroundColor: "#17a2b8"
                    }, {
                        label: "Write",
                        data: writes,
                        backgroundColor: "#28a745"
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ": " + formatBytes(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatBytes(value);
                                }
                            }
                        }
                    }
                }
            });

            // Update table
            const tbody = document.getElementById("processes-tbody");
            const maxIO = Math.max(...data.data.map(p => p.total_io));

            tbody.innerHTML = data.data.map(p => {
                const readPct = (p.total_read / maxIO * 100).toFixed(1);
                const writePct = (p.total_write / maxIO * 100).toFixed(1);
                return `<tr onclick="selectProcess(\'${p.name}\')">
                    <td><strong>${p.name}</strong></td>
                    <td>${formatBytes(p.total_read)}</td>
                    <td>${formatBytes(p.total_write)}</td>
                    <td>${p.samples}</td>
                    <td style="width: 200px;">
                        <div class="bytes-bar">
                            <div class="bytes-bar-fill bytes-bar-read" style="width: ${readPct}%; display: inline-block;"></div>
                            <div class="bytes-bar-fill bytes-bar-write" style="width: ${writePct}%; display: inline-block;"></div>
                        </div>
                    </td>
                </tr>`;
            }).join("");
        });
}

// Select process from table
function selectProcess(name) {
    document.getElementById("process-select").value = name;
    showTab("process-detail");
    loadProcessDetail();
}

// Load processes list for dropdown
function loadProcessesList() {
    fetch(page + "?api-processes-list=yes&hours=24")
        .then(r => r.json())
        .then(data => {
            if (!data.data) return;

            const select = document.getElementById("process-select");
            const currentValue = select.value;
            select.innerHTML = \'<option value="">-- Select a process --</option>\';

            data.data.forEach(name => {
                const opt = document.createElement("option");
                opt.value = name;
                opt.textContent = name;
                select.appendChild(opt);
            });

            if (currentValue) {
                select.value = currentValue;
            }
        });
}

// Load process detail
function loadProcessDetail(hours) {
    if (hours) currentProcessHours = hours;

    const processName = document.getElementById("process-select").value;
    if (!processName) return;

    fetch(page + "?api-process-history=yes&name=" + encodeURIComponent(processName) + "&hours=" + currentProcessHours)
        .then(r => r.json())
        .then(data => {
            if (!data.data || data.data.length === 0) {
                return;
            }

            const labels = [];
            const reads = [];
            const writes = [];

            data.data.forEach(point => {
                const d = new Date(point.timestamp * 1000);
                labels.push(d.toLocaleTimeString());
                reads.push(point.read_bytes);
                writes.push(point.write_bytes);
            });

            if (processDetailChart) processDetailChart.destroy();

            const ctx = document.getElementById("processDetailChart").getContext("2d");
            processDetailChart = new Chart(ctx, {
                type: "line",
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Read Bytes",
                        data: reads,
                        borderColor: "#17a2b8",
                        backgroundColor: "rgba(23, 162, 184, 0.1)",
                        fill: true,
                        tension: 0.4
                    }, {
                        label: "Write Bytes",
                        data: writes,
                        borderColor: "#28a745",
                        backgroundColor: "rgba(40, 167, 69, 0.1)",
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: "I/O History: " + processName
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ": " + formatBytes(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatBytes(value);
                                }
                            }
                        }
                    }
                }
            });
        });
}

// Load daily metrics
function loadDailyMetrics(days) {
    fetch(page + "?api-daily-metrics=yes&days=" + days)
        .then(r => r.json())
        .then(data => {
            if (!data.data || data.data.length === 0) {
                return;
            }

            const labels = data.data.map(d => d.date);
            const avgReads = data.data.map(d => d.avg_read_bytes);
            const avgWrites = data.data.map(d => d.avg_write_bytes);
            const maxReads = data.data.map(d => d.max_read_bytes);
            const maxWrites = data.data.map(d => d.max_write_bytes);

            if (dailyChart) dailyChart.destroy();

            const ctx = document.getElementById("dailyChart").getContext("2d");
            dailyChart = new Chart(ctx, {
                type: "bar",
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Avg Read",
                        data: avgReads,
                        backgroundColor: "rgba(23, 162, 184, 0.7)"
                    }, {
                        label: "Avg Write",
                        data: avgWrites,
                        backgroundColor: "rgba(40, 167, 69, 0.7)"
                    }, {
                        label: "Max Read",
                        data: maxReads,
                        backgroundColor: "rgba(23, 162, 184, 0.3)",
                        borderColor: "#17a2b8",
                        borderWidth: 1
                    }, {
                        label: "Max Write",
                        data: maxWrites,
                        backgroundColor: "rgba(40, 167, 69, 0.3)",
                        borderColor: "#28a745",
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ": " + formatBytes(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatBytes(value);
                                }
                            }
                        }
                    }
                }
            });
        });
}

// Refresh all data
function refreshAll() {
    loadStatus();
    loadStats();
    loadSystemMetrics(24);
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function() {
    loadStatus();
    loadStats();
    loadSystemMetrics(24);

    // Auto-refresh every 3 minutes
    setInterval(function() {
        loadStatus();
        loadStats();
        // Refresh active tab
        const activeTab = document.querySelector(".tab-nav button.active");
        if (activeTab) {
            if (activeTab.textContent.includes("System")) loadSystemMetrics(24);
            if (activeTab.textContent.includes("Processes")) loadTopProcesses(24);
        }
    }, 180000);
});
</script>
</body>
</html>';

    echo $html;
}
function main_page() {
    $page = CurrentPageName();
    $tpl = new template_admin();

    $html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>System I/O Metrics</title>
    <script src="angular/js/plugins/chartJs/Chart.min.js"></script>
    <style>
        .io-card {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            margin-bottom: 20px;
            padding: 20px;
        }
        .io-card h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #f8f9fa;
            border-radius: 4px;
            padding: 15px;
            text-align: center;
        }
        .stat-box .value {
            font-size: 24px;
            font-weight: bold;
            color: #1ab394;
        }
        .stat-box .label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
        }
        .btn-group-io {
            margin-bottom: 15px;
        }
        .btn-group-io button {
            margin-right: 5px;
        }
        .process-table {
            width: 100%;
            border-collapse: collapse;
        }
        .process-table th, .process-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .process-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .process-table tr:hover {
            background: #f8f9fa;
            cursor: pointer;
        }
        .bytes-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        .bytes-bar-fill {
            height: 100%;
            border-radius: 4px;
        }
        .bytes-bar-read {
            background: #17a2b8;
        }
        .bytes-bar-write {
            background: #28a745;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-running {
            background: #d4edda;
            color: #155724;
        }
        .status-stopped {
            background: #f8d7da;
            color: #721c24;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .tab-nav {
            display: flex;
            border-bottom: 2px solid #eee;
            margin-bottom: 20px;
        }
        .tab-nav button {
            padding: 10px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
        }
        .tab-nav button.active {
            color: #1ab394;
            border-bottom-color: #1ab394;
        }
    </style>
</head>
<body>
<div class="wrapper wrapper-content">
    <div class="row">
        <div class="col-lg-12">
            <!-- Header -->
            <div class="io-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="border: none; padding: 0; margin: 0;">System I/O Metrics</h3>
                        <small>Monitoring disk I/O activity per process</small>
                    </div>
                    <div>
                        <span id="service-status" class="status-badge status-stopped">Loading...</span>
                        <button class="btn btn-sm btn-primary" onclick="toggleService()" id="toggle-btn">Start</button>
                        <button class="btn btn-sm btn-default" onclick="refreshAll()"><i class="fa fa-refresh"></i> Refresh</button>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="io-card" id="stats-card">
                <h3>Statistics</h3>
                <div class="stats-grid" id="stats-grid">
                    <div class="stat-box">
                        <div class="value" id="stat-db-size">-</div>
                        <div class="label">Database Size</div>
                    </div>
                    <div class="stat-box">
                        <div class="value" id="stat-collection">3 min</div>
                        <div class="label">Collection Interval</div>
                    </div>
                    <div class="stat-box">
                        <div class="value" id="stat-detailed">24h</div>
                        <div class="label">Detailed Retention</div>
                    </div>
                    <div class="stat-box">
                        <div class="value" id="stat-daily">30d</div>
                        <div class="label">Daily Retention</div>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="io-card">
                <div class="tab-nav">
                    <button class="active" onclick="showTab(\'system\')">System Overview</button>
                    <button onclick="showTab(\'processes\')">Top Processes</button>
                    <button onclick="showTab(\'process-detail\')">Process Detail</button>
                    <button onclick="showTab(\'daily\')">Daily Trends</button>
                </div>

                <!-- System Overview Tab -->
                <div id="tab-system" class="tab-content active">
                    <div class="btn-group-io">
                        <button class="btn btn-sm btn-default" onclick="loadSystemMetrics(1)">1 Hour</button>
                        <button class="btn btn-sm btn-default" onclick="loadSystemMetrics(6)">6 Hours</button>
                        <button class="btn btn-sm btn-primary" onclick="loadSystemMetrics(24)">24 Hours</button>
                    </div>
                    <div class="chart-container">
                        <canvas id="systemChart"></canvas>
                    </div>
                </div>

                <!-- Top Processes Tab -->
                <div id="tab-processes" class="tab-content">
                    <div class="btn-group-io">
                        <button class="btn btn-sm btn-default" onclick="loadTopProcesses(1)">1 Hour</button>
                        <button class="btn btn-sm btn-default" onclick="loadTopProcesses(6)">6 Hours</button>
                        <button class="btn btn-sm btn-primary" onclick="loadTopProcesses(24)">24 Hours</button>
                    </div>
                    <div class="chart-container">
                        <canvas id="processesChart"></canvas>
                    </div>
                    <table class="process-table" id="processes-table">
                        <thead>
                            <tr>
                                <th>Process</th>
                                <th>Total Read</th>
                                <th>Total Write</th>
                                <th>Samples</th>
                                <th>I/O Distribution</th>
                            </tr>
                        </thead>
                        <tbody id="processes-tbody">
                        </tbody>
                    </table>
                </div>

                <!-- Process Detail Tab -->
                <div id="tab-process-detail" class="tab-content">
                    <div style="margin-bottom: 15px;">
                        <label>Select Process: </label>
                        <select id="process-select" onchange="loadProcessDetail()" style="width: 300px; padding: 5px;">
                            <option value="">-- Select a process --</option>
                        </select>
                        <div class="btn-group-io" style="display: inline-block; margin-left: 10px;">
                            <button class="btn btn-sm btn-default" onclick="loadProcessDetail(1)">1h</button>
                            <button class="btn btn-sm btn-default" onclick="loadProcessDetail(6)">6h</button>
                            <button class="btn btn-sm btn-primary" onclick="loadProcessDetail(24)">24h</button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="processDetailChart"></canvas>
                    </div>
                </div>

                <!-- Daily Trends Tab -->
                <div id="tab-daily" class="tab-content">
                    <div class="btn-group-io">
                        <button class="btn btn-sm btn-default" onclick="loadDailyMetrics(7)">7 Days</button>
                        <button class="btn btn-sm btn-default" onclick="loadDailyMetrics(14)">14 Days</button>
                        <button class="btn btn-sm btn-primary" onclick="loadDailyMetrics(30)">30 Days</button>
                    </div>
                    <div class="chart-container">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const page = "' . $page . '";
let systemChart = null;
let processesChart = null;
let processDetailChart = null;
let dailyChart = null;
let currentProcessHours = 24;

// Format bytes to human readable
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return "0 B";
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ["B", "KB", "MB", "GB", "TB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + " " + sizes[i];
}

// Show tab
function showTab(tabName) {
    document.querySelectorAll(".tab-content").forEach(t => t.classList.remove("active"));
    document.querySelectorAll(".tab-nav button").forEach(b => b.classList.remove("active"));
    document.getElementById("tab-" + tabName).classList.add("active");
    event.target.classList.add("active");

    // Load data for the tab
    if (tabName === "system") loadSystemMetrics(24);
    if (tabName === "processes") loadTopProcesses(24);
    if (tabName === "process-detail") loadProcessesList();
    if (tabName === "daily") loadDailyMetrics(30);
}

// Load service status
function loadStatus() {
    fetch(page + "?api-status=yes")
        .then(r => r.json())
        .then(data => {
            const running = data.data && data.data.running;
            const badge = document.getElementById("service-status");
            const btn = document.getElementById("toggle-btn");
            if (running) {
                badge.textContent = "Running";
                badge.className = "status-badge status-running";
                btn.textContent = "Stop";
                btn.className = "btn btn-sm btn-danger";
            } else {
                badge.textContent = "Stopped";
                badge.className = "status-badge status-stopped";
                btn.textContent = "Start";
                btn.className = "btn btn-sm btn-success";
            }
        });
}

// Load stats
function loadStats() {
    fetch(page + "?api-stats=yes")
        .then(r => r.json())
        .then(data => {
            if (data.data) {
                document.getElementById("stat-db-size").textContent = data.data.db_size_human || "-";
            }
        });
}

// Toggle service
function toggleService() {
    const badge = document.getElementById("service-status");
    const isRunning = badge.textContent === "Running";
    const endpoint = isRunning ? "?api-stop=yes" : "?api-start=yes";
    fetch(page + endpoint)
        .then(r => r.json())
        .then(() => {
            loadStatus();
            if (!isRunning) {
                setTimeout(refreshAll, 2000);
            }
        });
}

// Load system metrics
function loadSystemMetrics(hours) {
    fetch(page + "?api-system-metrics=yes&hours=" + hours)
        .then(r => r.json())
        .then(data => {
            if (!data.data || data.data.length === 0) {
                return;
            }

            const labels = [];
            const reads = [];
            const writes = [];

            data.data.forEach(point => {
                const d = new Date(point.timestamp * 1000);
                labels.push(d.toLocaleTimeString());
                reads.push(point.read);
                writes.push(point.write);
            });

            if (systemChart) systemChart.destroy();

            const ctx = document.getElementById("systemChart").getContext("2d");
            systemChart = new Chart(ctx, {
                type: "line",
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Read Bytes",
                        data: reads,
                        borderColor: "#17a2b8",
                        backgroundColor: "rgba(23, 162, 184, 0.1)",
                        fill: true,
                        tension: 0.4
                    }, {
                        label: "Write Bytes",
                        data: writes,
                        borderColor: "#28a745",
                        backgroundColor: "rgba(40, 167, 69, 0.1)",
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ": " + formatBytes(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatBytes(value);
                                }
                            }
                        }
                    }
                }
            });
        });
}

// Load top processes
function loadTopProcesses(hours) {
    fetch(page + "?api-top-processes=yes&hours=" + hours + "&limit=15")
        .then(r => r.json())
        .then(data => {
            if (!data.data || data.data.length === 0) {
                return;
            }

            const processes = data.data.slice(0, 10);
            const labels = processes.map(p => p.name);
            const reads = processes.map(p => p.total_read);
            const writes = processes.map(p => p.total_write);

            if (processesChart) processesChart.destroy();

            const ctx = document.getElementById("processesChart").getContext("2d");
            processesChart = new Chart(ctx, {
                type: "bar",
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Read",
                        data: reads,
                        backgroundColor: "#17a2b8"
                    }, {
                        label: "Write",
                        data: writes,
                        backgroundColor: "#28a745"
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ": " + formatBytes(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatBytes(value);
                                }
                            }
                        }
                    }
                }
            });

            // Update table
            const tbody = document.getElementById("processes-tbody");
            const maxIO = Math.max(...data.data.map(p => p.total_io));

            tbody.innerHTML = data.data.map(p => {
                const readPct = (p.total_read / maxIO * 100).toFixed(1);
                const writePct = (p.total_write / maxIO * 100).toFixed(1);
                return `<tr onclick="selectProcess(\'${p.name}\')">
                    <td><strong>${p.name}</strong></td>
                    <td>${formatBytes(p.total_read)}</td>
                    <td>${formatBytes(p.total_write)}</td>
                    <td>${p.samples}</td>
                    <td style="width: 200px;">
                        <div class="bytes-bar">
                            <div class="bytes-bar-fill bytes-bar-read" style="width: ${readPct}%; display: inline-block;"></div>
                            <div class="bytes-bar-fill bytes-bar-write" style="width: ${writePct}%; display: inline-block;"></div>
                        </div>
                    </td>
                </tr>`;
            }).join("");
        });
}

// Select process from table
function selectProcess(name) {
    document.getElementById("process-select").value = name;
    showTab("process-detail");
    loadProcessDetail();
}

// Load processes list for dropdown
function loadProcessesList() {
    fetch(page + "?api-processes-list=yes&hours=24")
        .then(r => r.json())
        .then(data => {
            if (!data.data) return;

            const select = document.getElementById("process-select");
            const currentValue = select.value;
            select.innerHTML = \'<option value="">-- Select a process --</option>\';

            data.data.forEach(name => {
                const opt = document.createElement("option");
                opt.value = name;
                opt.textContent = name;
                select.appendChild(opt);
            });

            if (currentValue) {
                select.value = currentValue;
            }
        });
}

// Load process detail
function loadProcessDetail(hours) {
    if (hours) currentProcessHours = hours;

    const processName = document.getElementById("process-select").value;
    if (!processName) return;

    fetch(page + "?api-process-history=yes&name=" + encodeURIComponent(processName) + "&hours=" + currentProcessHours)
        .then(r => r.json())
        .then(data => {
            if (!data.data || data.data.length === 0) {
                return;
            }

            const labels = [];
            const reads = [];
            const writes = [];

            data.data.forEach(point => {
                const d = new Date(point.timestamp * 1000);
                labels.push(d.toLocaleTimeString());
                reads.push(point.read_bytes);
                writes.push(point.write_bytes);
            });

            if (processDetailChart) processDetailChart.destroy();

            const ctx = document.getElementById("processDetailChart").getContext("2d");
            processDetailChart = new Chart(ctx, {
                type: "line",
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Read Bytes",
                        data: reads,
                        borderColor: "#17a2b8",
                        backgroundColor: "rgba(23, 162, 184, 0.1)",
                        fill: true,
                        tension: 0.4
                    }, {
                        label: "Write Bytes",
                        data: writes,
                        borderColor: "#28a745",
                        backgroundColor: "rgba(40, 167, 69, 0.1)",
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: "I/O History: " + processName
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ": " + formatBytes(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatBytes(value);
                                }
                            }
                        }
                    }
                }
            });
        });
}

// Load daily metrics
function loadDailyMetrics(days) {
    fetch(page + "?api-daily-metrics=yes&days=" + days)
        .then(r => r.json())
        .then(data => {
            if (!data.data || data.data.length === 0) {
                return;
            }

            const labels = data.data.map(d => d.date);
            const avgReads = data.data.map(d => d.avg_read_bytes);
            const avgWrites = data.data.map(d => d.avg_write_bytes);
            const maxReads = data.data.map(d => d.max_read_bytes);
            const maxWrites = data.data.map(d => d.max_write_bytes);

            if (dailyChart) dailyChart.destroy();

            const ctx = document.getElementById("dailyChart").getContext("2d");
            dailyChart = new Chart(ctx, {
                type: "bar",
                data: {
                    labels: labels,
                    datasets: [{
                        label: "Avg Read",
                        data: avgReads,
                        backgroundColor: "rgba(23, 162, 184, 0.7)"
                    }, {
                        label: "Avg Write",
                        data: avgWrites,
                        backgroundColor: "rgba(40, 167, 69, 0.7)"
                    }, {
                        label: "Max Read",
                        data: maxReads,
                        backgroundColor: "rgba(23, 162, 184, 0.3)",
                        borderColor: "#17a2b8",
                        borderWidth: 1
                    }, {
                        label: "Max Write",
                        data: maxWrites,
                        backgroundColor: "rgba(40, 167, 69, 0.3)",
                        borderColor: "#28a745",
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ": " + formatBytes(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return formatBytes(value);
                                }
                            }
                        }
                    }
                }
            });
        });
}

// Refresh all data
function refreshAll() {
    loadStatus();
    loadStats();
    loadSystemMetrics(24);
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function() {
    loadStatus();
    loadStats();
    loadSystemMetrics(24);

    // Auto-refresh every 3 minutes
    setInterval(function() {
        loadStatus();
        loadStats();
        // Refresh active tab
        const activeTab = document.querySelector(".tab-nav button.active");
        if (activeTab) {
            if (activeTab.textContent.includes("System")) loadSystemMetrics(24);
            if (activeTab.textContent.includes("Processes")) loadTopProcesses(24);
        }
    }, 180000);
});
</script>
</body>
</html>';

    echo $html;
}
