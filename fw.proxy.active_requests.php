<?php
/**
 * Squid Active Requests Monitor
 * Displays real-time active connections with Chart.js visualization
 *
 * API Endpoints Used:
 * - GET /squid/active-requests/current - Current stats summary
 * - GET /squid/active-requests/by-client - Stats grouped by client
 * - GET /squid/active-requests/by-hostname - Stats grouped by hostname
 * - POST /squid/active-requests/run - Trigger data collection
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.sqstats.inc");

// Action handlers
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["charts-tab"])){charts_tab();exit;}
if(isset($_GET["chart-data-js"])){chart_data_js();exit;}
if(isset($_GET["group-by-host"])){group_by_hosts();exit;}
if(isset($_GET["group-by-host-table"])){group_by_hosts_table();exit;}
if(isset($_GET["group-by-username"])){group_by_usernames();exit;}
if(isset($_GET["group-by-username-table"])){group_by_usernames_table();exit;}
if(isset($_GET["refresh-data"])){refresh_data();exit;}
if(isset($_GET["top-widgets"])){top_widgets();exit;}
if(isset($_GET["url-js"])){url_js();exit;}
if(isset($_GET["url-popup"])){url_popup();exit;}
if(isset($_GET["ip-js"])){ip_js();exit;}
if(isset($_GET["ip-popup"])){ip_popup();exit;}
if(isset($_GET["table"])){table();exit;}
js();


function MetaID():array{
    if(!isset($_GET["id"])){
        return array(0,"");
    }
    $id=intval($_GET["id"]);
    if($id>0){
        return array($id,"&id=$id");
    }
    return array(0,"");
}

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title="{active_requests}";
    list($id,$addon)=MetaID();

    if($id==0) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/current"));
    }else{
        $raw = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/realtimedb/current/$id"));
        $json = (is_object($raw) && isset($raw->total_connections))
            ? (object)["success"=>true,"data"=>$raw] : null;
    }

    $requests = 0;
    $size = "0 B";
    $ztime = "0s";

    if($json && $json->success && isset($json->data)){
        $requests = $json->data->total_connections ?? 0;
        $totalSize = $json->data->total_size ?? 0;
        $totalTime = $json->data->total_time ?? 0;
        $size = FormatBytes($totalSize/1024);
        $ztime = $tpl->FormatSeconds($totalTime);
    }

    $requests = $tpl->FormatNumber($requests);
    $title = "$title $requests {connections}, $size, $ztime";
    return $tpl->js_dialog9($title,"$page?start=yes$addon",1100);
}

function url_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $url=$_GET["url-js"];
    $urlencoded=urlencode($url);
    list($id,$addon)=MetaID();
    return $tpl->js_dialog10("{active_requests}: $url","$page?url-popup=$urlencoded$addon",750);
}

function ip_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $url=$_GET["ip-js"];
    $urlencoded=urlencode($url);
    list($id,$addon)=MetaID();
    return $tpl->js_dialog11("{active_requests}: $url","$page?ip-popup=$urlencoded$addon",990);
}

function start():bool{

    list($id,$addon)=MetaID();
    $page=CurrentPageName();
    echo "<div id='active-requests-progress'></div>";
    echo "<script>LoadAjax('active-requests-progress','$page?tabs=yes$addon');</script>";
    return true;
}

function tabs():bool{
    $page=CurrentPageName();
    list($id,$addon)=MetaID();
    $tpl=new template_admin();
    $group["{statistics}"]="$page?charts-tab=yes$addon";
    $group["{websites}"]="$page?group-by-host=yes$addon";
    $group["{members}"]="$page?group-by-username=yes$addon";
    echo $tpl->tabs_default($group);
    return true;
}

function top_widgets():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    list($id,$addon)=MetaID();
    // Get current stats from API
    if($id==0) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/current"));
    }else{
        $raw = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/realtimedb/current/$id"));
        $json = (is_object($raw) && isset($raw->total_connections))
            ? (object)["success"=>true,"data"=>$raw] : null;
    }
    $connections = 0;
    $clients = 0;
    $hostnames = 0;
    $totalSize = 0;

    if($json && $json->success && isset($json->data)){
        $connections = $json->data->total_connections ?? 0;
        $clients = $json->data->unique_clients ?? 0;
        $hostnames = $json->data->unique_hostnames ?? 0;
        $totalSize = $json->data->total_size ?? 0;
    }

    $widget_connections = base64_encode($tpl->_ENGINE_parse_body(
        $tpl->widget_style1("navy-bg", "fas fa-exchange-alt", "{connections}", $tpl->FormatNumber($connections))
    ));
    $widget_clients = base64_encode($tpl->_ENGINE_parse_body(
        $tpl->widget_style1("green-bg", "fas fa-users", "{members}", $tpl->FormatNumber($clients))
    ));
    $widget_hosts = base64_encode($tpl->_ENGINE_parse_body(
        $tpl->widget_style1("blue-bg", "fas fa-globe", "{websites}", $tpl->FormatNumber($hostnames))
    ));
    $widget_size = base64_encode($tpl->_ENGINE_parse_body(
        $tpl->widget_style1("yellow-bg", "fas fa-download", "{size}", FormatBytes($totalSize/1024))
    ));

    $html[]="if(document.getElementById('widget-connections')){";
    $html[]="  \$('#widget-connections').html(base64_decode('$widget_connections'));";
    $html[]="  \$('#widget-clients').html(base64_decode('$widget_clients'));";
    $html[]="  \$('#widget-hosts').html(base64_decode('$widget_hosts'));";
    $html[]="  \$('#widget-size').html(base64_decode('$widget_size'));";
    $html[]="}";

    header("content-type: application/x-javascript");
    echo implode("\n", $html);
    return true;
}

function charts_tab():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    list($id,$addon)=MetaID();

    // Widgets row
    $html[]="<div id='active-requests-widgets'>";
    $html[]="<table style='width:100%;margin-top:-5px'><tbody>";
    $html[]="<tr>";
    $html[]="<td style='width:25%'><span id='widget-connections'></span></td>";
    $html[]="<td style='width:25%;padding-left:5px'><span id='widget-clients'></span></td>";
    $html[]="<td style='width:25%;padding-left:5px'><span id='widget-hosts'></span></td>";
    $html[]="<td style='width:25%;padding-left:5px'><span id='widget-size'></span></td>";
    $html[]="</tr>";
    $html[]="</tbody></table>";
    $html[]="</div>";

    $jsFunc=urlencode(base64_encode("Loadjs('$page?chart-data-js=yes$addon');"));
    $refreshBtn=$tpl->button_tooltip("{refresh}","Loadjs('$page?refresh-data=$jsFunc$addon');",ico_refresh);

    // Charts row
    $html[]="<div class='row' style='margin-top:20px'>";
    $html[]="<div class='col-md-12'>";
    $html[]="<div class='ibox'>";
    $html[]="<div class='ibox-title'>";
    $html[]="<h5><i class='fas fa-chart-pie'></i> {statistics}</h5>";
    $html[]="<div class='ibox-tools'>$refreshBtn</div>";
    $html[]="</div>";
    $html[]="<div class='ibox-content'>";

    // Two charts side by side
    $html[]="<div class='row'>";

    // Pie chart - By Hostname (size)
    $html[]="<div class='col-md-6'>";
    $html[]="<h4 style='text-align:center'><i class='fas fa-globe'></i> {websites} ({size})</h4>";
    $html[]="<div style='height:350px;position:relative'>";
    $html[]="<canvas id='chart-hosts-pie'></canvas>";
    $html[]="</div>";
    $html[]="</div>";

    // Pie chart - By Client (size)
    $html[]="<div class='col-md-6'>";
    $html[]="<h4 style='text-align:center'><i class='fas fa-users'></i> {members} ({size})</h4>";
    $html[]="<div style='height:350px;position:relative'>";
    $html[]="<canvas id='chart-clients-pie'></canvas>";
    $html[]="</div>";
    $html[]="</div>";

    $html[]="</div>"; // row

    // Line chart - Connections over time (if we have history)
    $html[]="<div class='row' style='margin-top:30px'>";

    // Bar chart - Top Hostnames by connections
    $html[]="<div class='col-md-6'>";
    $html[]="<h4 style='text-align:center'><i class='fas fa-chart-bar'></i> {top} {websites} ({connections})</h4>";
    $html[]="<div style='height:300px;position:relative'>";
    $html[]="<canvas id='chart-hosts-bar'></canvas>";
    $html[]="</div>";
    $html[]="</div>";

    // Bar chart - Top Clients by connections
    $html[]="<div class='col-md-6'>";
    $html[]="<h4 style='text-align:center'><i class='fas fa-chart-bar'></i> {top} {members} ({connections})</h4>";
    $html[]="<div style='height:300px;position:relative'>";
    $html[]="<canvas id='chart-clients-bar'></canvas>";
    $html[]="</div>";
    $html[]="</div>";

    $html[]="</div>"; // row

    $html[]="</div>"; // ibox-content
    $html[]="</div>"; // ibox
    $html[]="</div>"; // col
    $html[]="</div>"; // row

    // JavaScript for widget refresh and chart loading
    $jsRefresh = $tpl->RefreshInterval_Loadjs("active-requests-widgets", $page, "top-widgets=yes$addon");

    $html[]="<script>
    $jsRefresh

    var hostsChart = null;
    var clientsChart = null;
    var hostsBarChart = null;
    var clientsBarChart = null;

    // Load charts
    Loadjs('$page?chart-data-js=yes$addon');
    </script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function chart_data_js():bool{
    header("content-type: application/x-javascript");
    list($id,$addon)=MetaID();
    // Get data from API
    if($id==0) {
        $hostsJson = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/by-hostname?limit=10"));
        $clientsJson = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/by-client?limit=10"));
    }else{
        $raw = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/realtimedb/by-hostname/$id?limit=10"));
        $hostsJson = (is_object($raw) && isset($raw->hosts))
            ? (object)["success"=>true,"data"=>$raw->hosts] : null;
        $raw = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/realtimedb/by-client/$id?limit=10"));
        $clientsJson = (is_object($raw) && isset($raw->clients))
            ? (object)["success"=>true,"data"=>$raw->clients] : null;
    }




    $js = [];

    // formatBytes helper
    $js[] = "function formatBytes(bytes) {";
    $js[] = "    if (bytes === 0) return '0 B';";
    $js[] = "    var k = 1024;";
    $js[] = "    var sizes = ['B', 'KB', 'MB', 'GB', 'TB'];";
    $js[] = "    var i = Math.floor(Math.log(bytes) / Math.log(k));";
    $js[] = "    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];";
    $js[] = "}";
    $js[] = "";

    // Color palette
    $js[] = "var chartColors = [";
    $js[] = "    '#36A2EB', '#FF6384', '#4BC0C0', '#FF9F40', '#9966FF',";
    $js[] = "    '#FFCD56', '#C9CBCF', '#7BC225', '#E74C3C', '#3498DB'";
    $js[] = "];";
    $js[] = "";

    // Destroy existing charts
    $js[] = "if(hostsChart) hostsChart.destroy();";
    $js[] = "if(clientsChart) clientsChart.destroy();";
    $js[] = "if(hostsBarChart) hostsBarChart.destroy();";
    $js[] = "if(clientsBarChart) clientsBarChart.destroy();";
    $js[] = "";

    // Prepare hosts pie chart data
    $hostLabels = [];
    $hostSizes = [];
    $hostConnections = [];

    if($hostsJson && $hostsJson->success && is_array($hostsJson->data)){
        foreach($hostsJson->data as $item){
            $hostLabels[] = $item->hostname ?? 'unknown';
            $hostSizes[] = $item->total_size ?? 0;
            $hostConnections[] = $item->connections ?? 0;
        }
    }

    $hostLabelsJson = json_encode($hostLabels);
    $hostSizesJson = json_encode($hostSizes);
    $hostConnectionsJson = json_encode($hostConnections);

    // Prepare clients pie chart data
    $clientLabels = [];
    $clientSizes = [];
    $clientConnections = [];

    if($clientsJson && $clientsJson->success && is_array($clientsJson->data)){
        foreach($clientsJson->data as $item){
            $clientLabels[] = $item->client ?? 'unknown';
            $clientSizes[] = $item->total_size ?? 0;
            $clientConnections[] = $item->connections ?? 0;
        }
    }

    $clientLabelsJson = json_encode($clientLabels);
    $clientSizesJson = json_encode($clientSizes);
    $clientConnectionsJson = json_encode($clientConnections);

    // Create Hosts Pie Chart
    $js[] = "var hostLabels = $hostLabelsJson;";
    $js[] = "var hostSizes = $hostSizesJson;";
    $js[] = "var hostConnections = $hostConnectionsJson;";
    $js[] = "";
    $js[] = "if(hostLabels.length > 0) {";
    $js[] = "    var ctx1 = document.getElementById('chart-hosts-pie').getContext('2d');";
    $js[] = "    hostsChart = new Chart(ctx1, {";
    $js[] = "        type: 'doughnut',";
    $js[] = "        data: {";
    $js[] = "            labels: hostLabels,";
    $js[] = "            datasets: [{";
    $js[] = "                data: hostSizes,";
    $js[] = "                backgroundColor: chartColors";
    $js[] = "            }]";
    $js[] = "        },";
    $js[] = "        options: {";
    $js[] = "            responsive: true,";
    $js[] = "            maintainAspectRatio: false,";
    $js[] = "            plugins: {";
    $js[] = "                legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } },";
    $js[] = "                tooltip: {";
    $js[] = "                    callbacks: {";
    $js[] = "                        label: function(ctx) {";
    $js[] = "                            return ctx.label + ': ' + formatBytes(ctx.raw);";
    $js[] = "                        }";
    $js[] = "                    }";
    $js[] = "                }";
    $js[] = "            }";
    $js[] = "        }";
    $js[] = "    });";
    $js[] = "} else {";
    $js[] = "    document.getElementById('chart-hosts-pie').parentNode.innerHTML = '<div class=\"alert alert-info\" style=\"margin-top:100px;text-align:center\">No data available</div>';";
    $js[] = "}";
    $js[] = "";

    // Create Clients Pie Chart
    $js[] = "var clientLabels = $clientLabelsJson;";
    $js[] = "var clientSizes = $clientSizesJson;";
    $js[] = "var clientConnections = $clientConnectionsJson;";
    $js[] = "";
    $js[] = "if(clientLabels.length > 0) {";
    $js[] = "    var ctx2 = document.getElementById('chart-clients-pie').getContext('2d');";
    $js[] = "    clientsChart = new Chart(ctx2, {";
    $js[] = "        type: 'doughnut',";
    $js[] = "        data: {";
    $js[] = "            labels: clientLabels,";
    $js[] = "            datasets: [{";
    $js[] = "                data: clientSizes,";
    $js[] = "                backgroundColor: chartColors";
    $js[] = "            }]";
    $js[] = "        },";
    $js[] = "        options: {";
    $js[] = "            responsive: true,";
    $js[] = "            maintainAspectRatio: false,";
    $js[] = "            plugins: {";
    $js[] = "                legend: { position: 'right', labels: { boxWidth: 12, font: { size: 11 } } },";
    $js[] = "                tooltip: {";
    $js[] = "                    callbacks: {";
    $js[] = "                        label: function(ctx) {";
    $js[] = "                            return ctx.label + ': ' + formatBytes(ctx.raw);";
    $js[] = "                        }";
    $js[] = "                    }";
    $js[] = "                }";
    $js[] = "            }";
    $js[] = "        }";
    $js[] = "    });";
    $js[] = "} else {";
    $js[] = "    document.getElementById('chart-clients-pie').parentNode.innerHTML = '<div class=\"alert alert-info\" style=\"margin-top:100px;text-align:center\">No data available</div>';";
    $js[] = "}";
    $js[] = "";

    // Create Hosts Bar Chart (connections)
    $js[] = "if(hostLabels.length > 0) {";
    $js[] = "    var ctx3 = document.getElementById('chart-hosts-bar').getContext('2d');";
    $js[] = "    hostsBarChart = new Chart(ctx3, {";
    $js[] = "        type: 'bar',";
    $js[] = "        data: {";
    $js[] = "            labels: hostLabels,";
    $js[] = "            datasets: [{";
    $js[] = "                label: 'Connections',";
    $js[] = "                data: hostConnections,";
    $js[] = "                backgroundColor: '#36A2EB',";
    $js[] = "                borderColor: '#2980B9',";
    $js[] = "                borderWidth: 1";
    $js[] = "            }]";
    $js[] = "        },";
    $js[] = "        options: {";
    $js[] = "            indexAxis: 'y',";
    $js[] = "            responsive: true,";
    $js[] = "            maintainAspectRatio: false,";
    $js[] = "            plugins: {";
    $js[] = "                legend: { display: false }";
    $js[] = "            },";
    $js[] = "            scales: {";
    $js[] = "                x: { beginAtZero: true }";
    $js[] = "            }";
    $js[] = "        }";
    $js[] = "    });";
    $js[] = "} else {";
    $js[] = "    document.getElementById('chart-hosts-bar').parentNode.innerHTML = '<div class=\"alert alert-info\" style=\"margin-top:80px;text-align:center\">No data available</div>';";
    $js[] = "}";
    $js[] = "";

    // Create Clients Bar Chart (connections)
    $js[] = "if(clientLabels.length > 0) {";
    $js[] = "    var ctx4 = document.getElementById('chart-clients-bar').getContext('2d');";
    $js[] = "    clientsBarChart = new Chart(ctx4, {";
    $js[] = "        type: 'bar',";
    $js[] = "        data: {";
    $js[] = "            labels: clientLabels,";
    $js[] = "            datasets: [{";
    $js[] = "                label: 'Connections',";
    $js[] = "                data: clientConnections,";
    $js[] = "                backgroundColor: '#4BC0C0',";
    $js[] = "                borderColor: '#16A085',";
    $js[] = "                borderWidth: 1";
    $js[] = "            }]";
    $js[] = "        },";
    $js[] = "        options: {";
    $js[] = "            indexAxis: 'y',";
    $js[] = "            responsive: true,";
    $js[] = "            maintainAspectRatio: false,";
    $js[] = "            plugins: {";
    $js[] = "                legend: { display: false }";
    $js[] = "            },";
    $js[] = "            scales: {";
    $js[] = "                x: { beginAtZero: true }";
    $js[] = "            }";
    $js[] = "        }";
    $js[] = "    });";
    $js[] = "} else {";
    $js[] = "    document.getElementById('chart-clients-bar').parentNode.innerHTML = '<div class=\"alert alert-info\" style=\"margin-top:80px;text-align:center\">No data available</div>';";
    $js[] = "}";

    echo implode("\n", $js);
    return true;
}

function refresh_data():bool{
    $tpl=new template_admin();
    $data=base64_decode($_GET["refresh-data"]);
    list($id,$addon)=MetaID();
    // Trigger data collection
    if($id==0) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/squid/active-requests/run", array()));
    }else{
        $raw = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/realtimedb/fetch/$id", array()));
        $json = (is_object($raw) && ($raw->Status ?? false))
            ? (object)["success"=>true] : null;
    }



    if($json && $json->success){
        header("content-type: application/x-javascript");
        echo $data."\n";
        return true;
    }
    $error = $json->error ?? "Unknown error";
    return $tpl->js_error($error);
}

function ip_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t = time();
    $ipaddr = $_GET["ip-popup"];
    list($id,$addon)=MetaID();
    // Get client history from API
    if($id==0) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/client/" . urlencode($ipaddr) . "/history?hours=24"));
    }else{
        $raw = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/realtimedb/client/$id/". urlencode($ipaddr) ."/history?hours=24"));
        $json = (is_object($raw) && isset($raw->history))
            ? (object)["success"=>true,"data"=>$raw->history] : null;
    }

    if(!$json || !$json->success){
        // Fallback to old SQLite method
        $dbfile = "/home/artica/SQLITE_TEMP/ProxCons.db";
        $q = new lib_sqlite($dbfile);
        $results=$q->QUERY_SQL("SELECT url,SUM(size) as size,AVG(ztime) as ztime,COUNT(*) as cnx FROM connections WHERE ipaddr='$ipaddr' GROUP BY url ORDER by size desc");

        $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
        $html[]="<thead>";
        $html[]="<tr>";
        $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
        $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{websites}</th>";
        $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap=''>{connexions}</th>";
        $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
        $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
        $html[]="</tr>";
        $html[]="</thead>";
        $html[]="<tbody>";
        $TRCLASS=null;
        foreach ($results as $index=>$ligne){
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $md=md5(serialize($ligne));
            $url=$ligne["url"];
            $cnx=$tpl->FormatNumber($ligne["cnx"]);
            $size=FormatBytes(intval($ligne["size"])/1024);
            $ztime=$tpl->FormatSeconds($ligne["ztime"]);
            $url=getLinkURL($url);
            $html[]="<tr class='$TRCLASS' id='$md'>";
            $html[]="<td style='width:1%'><i class='".ico_earth."'></i></td>";
            $html[]="<td style='width:99%'><strong>$url</strong></td>";
            $html[]="<td  style='width:1%;text-align:right'>$cnx</td>";
            $html[]="<td  style='width:1%;text-align:right'>$size</td>";
            $html[]="<td style='width:1%' nowrap>$ztime</td>";
            $html[]="</tr>";
        }
        $html[]="</tbody></table>";
        $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    // Show history from API
    $history = $json->data ?? [];

    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap=''>{connexions}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{websites}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    foreach ($history as $item){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($item));
        $timestamp = date("Y-m-d H:i:s", $item->timestamp ?? 0);
        $cnx=$tpl->FormatNumber($item->connections ?? 0);
        $hostnames = is_array($item->hostnames) ? count($item->hostnames) : 0;
        $size=FormatBytes(($item->total_size ?? 0)/1024);
        $ztime=$tpl->FormatSeconds($item->total_time ?? 0);

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:20%'>$timestamp</td>";
        $html[]="<td  style='width:1%;text-align:right'>$cnx</td>";
        $html[]="<td  style='width:1%;text-align:right'>$hostnames</td>";
        $html[]="<td  style='width:1%;text-align:right'>$size</td>";
        $html[]="<td style='width:1%' nowrap>$ztime</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody></table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function url_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t = time();
    list($id,$addon)=MetaID();
    $url = $_GET["url-popup"];

    // Get hostname history from API
    if($id==0) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/hostname/" . urlencode($url) . "/history?hours=24"));
    }else{
        $raw = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/realtimedb/hostname/$id/" . urlencode($url) . "/history?hours=24"));
        $json = (is_object($raw) && isset($raw->history))
            ? (object)["success"=>true,"data"=>$raw->history] : null;
    }

    if(!$json || !$json->success){
        return true;
    }

    // Show history from API
    $history = $json->data ?? [];

    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap=''>{connexions}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    foreach ($history as $item){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($item));
        $timestamp = date("Y-m-d H:i:s", $item->timestamp ?? 0);
        $cnx=$tpl->FormatNumber($item->connections ?? 0);
        $clients = is_array($item->clients) ? count($item->clients) : 0;
        $size=FormatBytes(($item->total_size ?? 0)/1024);
        $ztime=$tpl->FormatSeconds($item->total_time ?? 0);

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:20%'>$timestamp</td>";
        $html[]="<td  style='width:1%;text-align:right'>$cnx</td>";
        $html[]="<td  style='width:1%;text-align:right'>$clients</td>";
        $html[]="<td  style='width:1%;text-align:right'>$size</td>";
        $html[]="<td style='width:1%' nowrap>$ztime</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody></table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function getLinkMember($Member,$ipaddr,$resolve):string{
    $tpl=new template_admin();
    list($id,$addon)=MetaID();
    $page=CurrentPageName();
    $ipaddr_text=$ipaddr;
    if($resolve){
        if(!isset($_SESSION["resolved"][$ipaddr])){
            $_SESSION["resolved"][$ipaddr]=gethostbyaddr($ipaddr);
        }
        $ipaddr_text= $_SESSION["resolved"][$ipaddr];
    }
    if($Member<>"-"){
        $ipaddr_text="$ipaddr_text&nbsp;($Member)";
    }
    return $tpl->td_href($ipaddr_text,null,"Loadjs('$page?ip-js=".urlencode($ipaddr)."$addon')");
}

function group_by_hosts():bool{
    $page=CurrentPageName();
    list($id,$addon)=MetaID();
    echo "<div id='squid-active-requests-by-host'></div>";
    echo "<script>LoadAjaxSilent('squid-active-requests-by-host','$page?group-by-host-table=yes$addon');</script>";
    return true;
}

function group_by_hosts_table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t = time();
    list($id,$addon)=MetaID();
    if($id==0) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/by-hostname?limit=250"));
    }else{
        $raw = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/realtimedb/by-hostname/$id?limit=250"));
        $json = (is_object($raw) && isset($raw->hosts))
            ? (object)["success"=>true,"data"=>$raw->hosts] : null;
    }
    $jsFunc=urlencode(base64_encode("LoadAjaxSilent('squid-active-requests-by-host','$page?group-by-host-table=yes$addon');"));
    $buton=$tpl->button_tooltip("{refresh}","Loadjs('$page?refresh-data=$jsFunc$addon');",ico_refresh);

    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>$buton</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}/{connections}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    if($json && $json->success && is_array($json->data)){
        foreach ($json->data as $item){
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $md=md5(serialize($item));
            $hostname = $item->hostname ?? 'unknown';
            $connections = $tpl->FormatNumber($item->connections ?? 0);
            $clientCount = is_array($item->clients) ? count($item->clients) : 0;
            $size = FormatBytes(($item->total_size ?? 0)/1024);
            $ztime = $tpl->FormatSeconds($item->total_time ?? 0);
            $LinkUrl = getLinkURL($hostname);

            $html[]="<tr class='$TRCLASS' id='$md'>";
            $html[]="<td style='width:1%'><i class='fas fa-globe'></i></td>";
            $html[]="<td style='width:99%'><strong>$LinkUrl</strong></td>";
            $html[]="<td style='width:1%;text-align:right'>$clientCount / $connections</td>";
            $html[]="<td style='width:1%'>$size</td>";
            $html[]="<td style='width:1%' nowrap>$ztime</td>";
            $html[]="</tr>";
        }
    }


    $html[]="</tbody></table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function getLinkURL($url){
    list($id,$addon)=MetaID();
    $urlText=$url;
    if(preg_match("#(.+?):([0-9]+)#",$urlText,$re)){
        $urlText=$re[1];
    }
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->td_href($urlText,null,"Loadjs('$page?url-js=".urlencode($url)."$addon')");
}

function group_by_usernames():bool{
    $page=CurrentPageName();
    list($id,$addon)=MetaID();
    echo "<div id='squid-active-requests-by-usernames'></div>";
    echo "<script>LoadAjaxSilent('squid-active-requests-by-usernames','$page?group-by-username-table=yes$addon');</script>";
    return true;
}
function group_by_usernames_table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t = time();
    list($id,$addon)=MetaID();
    // Get data from new API
    if($id==0) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/squid/active-requests/by-client?limit=100"));
    }else{
        $raw = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/realtimedb/clients/$id?limit=100"));
        $json = (is_object($raw) && isset($raw->clients))
            ? (object)["success"=>true,"data"=>$raw->clients] : null;
    }

    $resolve=false;
    $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
    if($resolveIP2HOST==1){
        $resolve=true;
    }

    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{member}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{connections}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{websites}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    if($json && $json->success && is_array($json->data)){
        foreach ($json->data as $item){
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            $md=md5(serialize($item));
            $client = $item->client ?? 'unknown';
            $connections = $tpl->FormatNumber($item->connections ?? 0);
            $hostnames = is_array($item->hostnames) ? count($item->hostnames) : 0;
            $size = FormatBytes(($item->total_size ?? 0)/1024);
            $ztime = $tpl->FormatSeconds($item->total_time ?? 0);

            // Determine if it's an IP or username
            $isIP = filter_var($client, FILTER_VALIDATE_IP) !== false;
            $LinkMember = getLinkMember($isIP ? "-" : $client, $isIP ? $client : "", $resolve);

            $html[]="<tr class='$TRCLASS' id='$md'>";
            $html[]="<td style='width:1%'><i class='".ico_member."'></i></td>";
            $html[]="<td style='width:60%'><strong>$LinkMember</strong></td>";
            $html[]="<td style='width:1%;text-align:right'>$connections</td>";
            $html[]="<td style='width:1%;text-align:right'>$hostnames</td>";
            $html[]="<td style='width:1%'>$size</td>";
            $html[]="<td style='width:1%' nowrap>$ztime</td>";
            $html[]="</tr>";
        }
    }
    $html[]="</tbody></table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $resolve=false;
    $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
    if($resolveIP2HOST==1){
        $resolve=true;
    }
    $squidstat=new squidstat();
    if(!$squidstat->connect()){
        echo $tpl->FATAL_ERROR_SHOW_128("{connection failed}");
    }
    $data=$squidstat->makeQuery();
    echo $tpl->_ENGINE_parse_body($squidstat->makeHtmlReport($data,$resolve,$hosts_array=array(),$_GET["filter"]));
}
