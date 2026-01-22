<?php
/**
 * TreeSize - Disk Usage Analyzer
 * Displays disk usage with interactive treemap and directory browser
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}

$users=new usersMenus();
if(!$users->AsSystemAdministrator){exit();}

if(isset($_GET["verbose"])){
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
}

// Action handlers
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["top-widgets"])){top_widgets();exit;}
if(isset($_GET["scan-js"])){scan_js();exit;}
if(isset($_GET["scan-popup"])){scan_popup();exit;}
if(isset($_POST["path"])){start_scan();exit;}
if(isset($_GET["stop-scan-js"])){stop_scan_js();exit;}
if(isset($_GET["scan-progress"])){scan_progress();exit;}
if(isset($_GET["directory-table"])){directory_table();exit;}
if(isset($_GET["chart-data"])){chart_data();exit;}
if(isset($_GET["quick-size"])){quick_size();exit;}
if(isset($_GET["history-chart"])){history_chart();exit;}

page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{disk_usage_analyzer}",
        "fas fa-chart-pie","{disk_usage}: {disk_usage_analyzer_desc}",
        "$page?main=yes","treesize","progress-treesize",false,"table-loader");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
}

function widget_dirs($jsonStats):string{
    $tpl = new template_admin();
    $totalDirs=0;

    if($jsonStats->success){
        $totalDirs=$jsonStats->data->dir_entries ?? 0;
    }
    if($totalDirs==0){
        return $tpl->widget_style1("gray-bg","fas fa-folder","{directories}",0);

    }

    return $tpl->widget_style1("navy-bg","fas fa-folder","{directories}",$tpl->FormatNumber($totalDirs));

}

function widget_status($jsonStatus):string{
    $tpl = new template_admin();

    if ($jsonStatus->success && $jsonStatus->data->scanning) {

        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/treesize/scan/progress"));

        if ($json->success && $json->data->scanning) {
            $p = $json->data->progress;
            $progress=round($p->progress, 1);
            return $tpl->widget_style1("yellow-bg", "fas fa-spinner fa-spin", "{scanning}", "$progress%");

        }
        return $tpl->widget_style1("yellow-bg","fas fa-spinner fa-spin","{scanning}","{in_progress}");
    }
    return $tpl->widget_style1("green-bg","fas fa-check-circle","{status}","{active2}");
}
function widget_scan($jsonStatus):string{
    $tpl=new template_admin();
    $page=currentPageName();
    if($jsonStatus->success && $jsonStatus->data->scanning){

        $btn = array();
        $btn[0]["margin"] = 0;
        $btn[0]["name"] = "{stop}";
        $btn[0]["icon"] = ico_stop;
        $btn[0]["js"] = "Loadjs('$page?stop-scan-js=yes');";
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/treesize/scan/progress"));

        if ($json->success && $json->data->scanning) {
            $p = $json->data->progress;
            $progress=round($p->progress, 1);
            return $tpl->widget_style1("green-bg", "fas fa-spinner fa-spin", "{scanning}",
                "$progress%",$btn);

        }
        return $tpl->widget_style1("green-bg","fas fa-spinner fa-spin","{scanning}","{in_progress}",$btn);
    }
    $btn = array();
    $btn[0]["margin"] = 0;
    $btn[0]["name"] = "{start}";
    $btn[0]["icon"] = ico_run;
    $btn[0]["js"] = "Loadjs('$page?scan-js=yes');";
    return $tpl->widget_style1("gray-bg",ico_stop,"{scanning}","{sleeping}",$btn);
}

function top_widgets():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    // Get TreeSize status from API
    $jsonStatus=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/treesize/status"));
    $jsonStats=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/treesize/stats"));


    $widget_status=base64_encode($tpl->_ENGINE_parse_body(widget_status($jsonStatus)));
    $widget_dirs=base64_encode($tpl->_ENGINE_parse_body(widget_dirs($jsonStats)));
    $widget_scan=base64_encode($tpl->_ENGINE_parse_body(widget_scan($jsonStatus)));

    $html[]="if(document.getElementById('widget-status') ){";
    $html[]="$('#widget-status').html(base64_decode('$widget_status'));";
    $html[]="$('#widget-dirs').html(base64_decode('$widget_dirs'));";
    $html[]="$('#widget-scan').html(base64_decode('$widget_scan'));";
    $html[]="}";
    header("content-type: application/x-javascript");
    echo implode("\n", $html);
    return true;

}

function scan_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->popup_no_privs();}
    return $tpl->js_dialog6("{start_scan}", "$page?scan-popup=yes",650);
}

function scan_popup():bool{
    $tpl=new template_admin();
    $html[]=$tpl->field_text("path","{path}","/");
    $html[]=$tpl->field_hidden("exclude","");
    $html[]=$tpl->field_hidden("cross_fs","0");
    echo $tpl->form_outside("",$html,"","{run}"," dialogInstance6.close();");
    return true;
}

function start_scan():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $path=$_POST["path"] ?? "/";
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/treesize/scan",$_POST));
    return admin_tracks("Scanning the TreeSize of $path");
}

function stop_scan_js(){
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/treesize/scan/stop","POST");
}

function scan_progress(){
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/treesize/scan/progress"));

    if(!$json->success){
        echo $tpl->div_error($json->error);
        return;
    }

    $progress=$json->data->progress ?? [];

    if(!$json->data->scanning){
        echo $tpl->div_success("{scan_complete}");
        return;
    }

    $currentDir=$progress->current_dir ?? "";
    $dirsScanned=$progress->dirs_scanned ?? 0;
    $filesScanned=$progress->files_scanned ?? 0;

    $html[]="<div class='alert alert-info'>";
    $html[]="<i class='fas fa-spinner fa-spin'></i> {scanning}...";
    $html[]="<br><strong>{current}:</strong> ".htmlspecialchars($currentDir);
    $html[]="<br><strong>{directories}:</strong> ".$tpl->FormatNumber($dirsScanned);
    $html[]="<br><strong>{files}:</strong> ".$tpl->FormatNumber($filesScanned);
    $html[]="</div>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function main(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    // Top widgets
    $html[]="<div id='treesize-widgets'>";
    $html[]="<table style='width:100%;margin-top:-5px'><tbody>";
    $html[]="<tr>";
    $html[]="<td style='width:25%'><span id='widget-status'></span></td>";
    $html[]="<td style='width:25%;padding-left:5px'><span id='widget-dirs'></span></td>";
    $html[]="<td style='width:25%;padding-left:5px'><span id='widget-scan'></span></td>";
    $html[]="</tr>";
    $html[]="</tbody></table>";
    $html[]="</div>";


    // Main content tabs
    $html[]="<div style='margin-top:20px'>";

    // Tab navigation
    $html[]="<ul class='nav nav-tabs' id='treesize-tabs'>";
    $html[]="<li class='active'><a data-toggle='tab' href='#tab-browser'><i class='fas fa-folder-tree'></i> {directory_browser}</a></li>";
    $html[]="<li><a data-toggle='tab' href='#tab-treemap'><i class='fas fa-chart-pie'></i> {treemap}</a></li>";
    $html[]="<li><a data-toggle='tab' href='#tab-history'><i class='fas fa-chart-line'></i> {history}</a></li>";
    $html[]="</ul>";

    // Tab content
    $html[]="<div class='tab-content' style='padding:20px;background:#fff;border:1px solid #ddd;border-top:none'>";

    // Directory Browser Tab
    $html[]="<div id='tab-browser' class='tab-pane fade in active'>";
    $html[]="<div class='row'>";
    $html[]="<div class='col-md-12'>";
    $html[]="<div class='input-group' style='margin-bottom:15px'>";
    $html[]="<span class='input-group-addon'><i class='fas fa-folder'></i></span>";
    $html[]="<input type='text' class='form-control' id='browse-path' value='/' placeholder='/' onkeydown=\"if(event.key==='Enter'){TreeSizeBrowse();return false;}\">";
    $html[]="<span class='input-group-btn'>";
    $html[]="<button class='btn btn-primary' onclick=\"TreeSizeBrowse()\"><i class='fas fa-search'></i> {browse}</button>";
    $html[]="</span>";
    $html[]="</div>";
    $html[]="</div>";
    $html[]="</div>";
    $html[]="<div id='directory-content'></div>";
    $html[]="</div>";

    // Treemap Tab
    $html[]="<div id='tab-treemap' class='tab-pane fade'>";
    $html[]="<div class='row'>";
    $html[]="<div class='col-md-6'>";
    $html[]="<canvas id='treemap-chart' style='max-height:500px'></canvas>";
    $html[]="</div>";
    $html[]="<div class='col-md-6'>";
    $html[]="<canvas id='sunburst-chart' style='max-height:500px'></canvas>";
    $html[]="</div>";
    $html[]="</div>";
    $html[]="</div>";

    // History Tab
    $html[]="<div id='tab-history' class='tab-pane fade'>";
    $html[]="<div id='history-content'></div>";
    $html[]="</div>";

    $html[]="</div>"; // tab-content
    $html[]="</div>"; // main content


    $jsRefresh=$tpl->RefreshInterval_Loadjs("treesize-widgets",$page,"top-widgets=yes");

    // JavaScript
    $html[]="<script>
    $jsRefresh
    var treemapChart=null;
    var sunburstChart=null;

    function TreeSizeBrowse(){
        var path=document.getElementById('browse-path').value || '/';
        LoadAjax('directory-content','$page?directory-table=yes&path='+encodeURIComponent(path));
    }

    function TreeSizeNavigate(path){
        document.getElementById('browse-path').value=path;
        TreeSizeBrowse();
    }

    function TreeSizeQuickSize(path){
        LoadAjax('directory-content','$page?quick-size=yes&path='+encodeURIComponent(path));
    }

    function TreeSizeLoadCharts(){
        Loadjs('$page?chart-data=yes&path=/');
    }

    function TreeSizeLoadHistory(){
        LoadAjax('history-content','$page?history-chart=yes&path=/');
    }

    // Tab change handlers
    \$('a[data-toggle=\"tab\"]').on('shown.bs.tab', function (e) {
        var target = \$(e.target).attr('href');
        if(target=='#tab-treemap'){
            TreeSizeLoadCharts();
        }else if(target=='#tab-history'){
            TreeSizeLoadHistory();
        }
    });

    // Initial load
    TreeSizeBrowse();
    </script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function directory_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $path=$_GET["path"] ?? "/";

    // Get children from API
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/treesize/children?path=".urlencode($path)."&limit=100"));

    if(!$json->success){
        $quickJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/treesize/quick?path=".urlencode($path)));


        if($quickJson->success){
            $html[]=$tpl->div_warning("{no_cached_data}||{showing_quick_size}.");
            $html[]="<table class='table table-hover table-bordered'>";
            $html[]="<tr><th>{path}</th><td>".htmlspecialchars($quickJson->data->path)."</td></tr>";
            $html[]="<tr><th>{total_size}</th><td>".$quickJson->data->size_human."</td></tr>";
            $html[]="<tr><th>{files}</th><td>".$tpl->FormatNumber($quickJson->data->file_count)."</td></tr>";
            $html[]="<tr><th>{directories}</th><td>".$tpl->FormatNumber($quickJson->data->dir_count)."</td></tr>";
            $html[]="</table>";
            echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
            return;
        }
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_data_available}||{please_run_scan}."));
        return;
    }

    $entries=$json->data ?? [];


    if(count($entries)==0){
        echo $tpl->div_info("{no_subdirectories}");
        return;
    }

    // Breadcrumb navigation
    $pathParts=explode("/", trim($path, "/"));
    $breadcrumb=[];
    $breadcrumb[]="<a href=\"javascript:TreeSizeNavigate('/')\">/</a>";
    $buildPath="";
    foreach($pathParts as $part){
        if($part==""){continue;}
        $buildPath.="/".$part;
        $breadcrumb[]="<a href=\"javascript:TreeSizeNavigate('".addslashes($buildPath)."')\">$part</a>";
    }
    $html[]="<nav aria-label='breadcrumb'><ol class='breadcrumb'>".implode(" / ", $breadcrumb)."</ol></nav>";

    // Calculate total for percentages
    $totalSize=0;
    foreach($entries as $entry){
        $totalSize+=$entry->size ?? 0;

    }

    // Directory table
    $html[]="<table class='table table-hover table-striped'>";
    $html[]="<thead><tr>";
    $html[]="<th style='width:50%'>{name}</th>";
    $html[]="<th style='width:20%'>{size}</th>";
    $html[]="<th style='width:15%'>{files}</th>";
    $html[]="<th style='width:15%'>{percentage}</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody>";

    foreach($entries as $entry){
        //var_dump($entry);
        $entryPath=$entry->path ?? "";
        $name=basename($entryPath);
        if($name==""){$name=$entryPath;}

        $size=$entry->size ?? 0;
        $sizeHuman=FormatBytes($size/1024);
        $fileCount=$entry->file_count ?? 0;
        $dirCount=$entry->dir_count ?? 0;
        if($fileCount==0){
            continue;
        }

        $pct=0;
        if($totalSize>0){
            $pct=round(($size/$totalSize)*100, 1);
        }

        // Progress bar color based on percentage
        $barColor="success";
        if($pct>50){$barColor="warning";}
        if($pct>75){$barColor="danger";}

        $html[]="<tr onclick=\"TreeSizeNavigate('".addslashes($entryPath)."')\" style='cursor:pointer'>";
        $html[]="<td><i class='fas fa-folder text-$barColor'></i> ".htmlspecialchars($name)."</td>";
        $html[]="<td>$sizeHuman</td>";
        $html[]="<td>".$tpl->FormatNumber($fileCount)." <small class='text-muted'>($dirCount dirs)</small></td>";
        $html[]="<td>";
        $html[]="<div class='progress' style='margin-bottom:0'>";
        $html[]="<div class='progress-bar progress-bar-$barColor' style='width:$pct%'>$pct%</div>";
        $html[]="</div>";
        $html[]="</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody></table>";

    // Total row
    $html[]="<div class='well'><strong>{total}:</strong> ".FormatBytes($totalSize/1024)."</div>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function chart_data(){
    header("content-type: application/x-javascript");
    $path=$_GET["path"] ?? "/";

    // Get bar chart data
    $barJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/treesize/chart/bar?path=".urlencode($path)."&limit=15"));

    if(!$barJson->success){
        echo "console.error('Failed to load chart data');";
        return;
    }

    $chartData=json_encode($barJson->data);

    $js=[];
    $js[]="// Human readable bytes formatter";
    $js[]="function formatBytes(bytes) {";
    $js[]="    if (bytes === 0) return '0 B';";
    $js[]="    var k = 1024;";
    $js[]="    var sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];";
    $js[]="    var i = Math.floor(Math.log(bytes) / Math.log(k));";
    $js[]="    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];";
    $js[]="};";
    $js[]="";
    $js[]="// Destroy existing charts";
    $js[]="if(treemapChart){treemapChart.destroy();}";
    $js[]="if(sunburstChart){sunburstChart.destroy();}";
    $js[]="";
    $js[]="// Prepare bar chart data with human readable tooltips";
    $js[]="var barChartData=$chartData;";
    $js[]="barChartData.options.plugins.tooltip={";
    $js[]="    callbacks:{";
    $js[]="        label:function(context){";
    $js[]="            return context.label + ': ' + formatBytes(context.raw);";
    $js[]="        }";
    $js[]="    }";
    $js[]="};";
    $js[]="barChartData.options.scales={";
    $js[]="    x:{";
    $js[]="        ticks:{";
    $js[]="            callback:function(value){ return formatBytes(value); }";
    $js[]="        }";
    $js[]="    }";
    $js[]="};";
    $js[]="";
    $js[]="// Create bar chart";
    $js[]="var ctx1=document.getElementById('treemap-chart').getContext('2d');";
    $js[]="treemapChart=new Chart(ctx1, barChartData);";
    $js[]="";
    $js[]="// Get sunburst/pie data - clone the data to avoid mutation";
    $js[]="var sunburstData=JSON.parse(JSON.stringify($chartData));";
    $js[]="sunburstData.type='pie';";
    $js[]="sunburstData.options={";
    $js[]="    responsive:true,";
    $js[]="    plugins:{";
    $js[]="        legend:{position:'right'},";
    $js[]="        title:{display:true,text:'Disk Usage Distribution'},";
    $js[]="        tooltip:{";
    $js[]="            callbacks:{";
    $js[]="                label:function(context){";
    $js[]="                    return context.label + ': ' + formatBytes(context.raw);";
    $js[]="                }";
    $js[]="            }";
    $js[]="        }";
    $js[]="    }";
    $js[]="};";
    $js[]="";
    $js[]="var ctx2=document.getElementById('sunburst-chart').getContext('2d');";
    $js[]="sunburstChart=new Chart(ctx2, sunburstData);";

    echo implode("\n", $js);
}

function quick_size(){
    $tpl=new template_admin();
    $path=$_GET["path"] ?? "/";

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/treesize/quick?path=".urlencode($path)));

    if(!$json->success){
        echo $tpl->div_error($json->error);
        return;
    }

    $data=$json->data;

    $html[]="<div class='panel panel-info'>";
    $html[]="<div class='panel-heading'><h3 class='panel-title'><i class='fas fa-tachometer-alt'></i> {quick_size}: ".htmlspecialchars($data->path)."</h3></div>";
    $html[]="<div class='panel-body'>";
    $html[]="<table class='table table-bordered'>";
    $html[]="<tr><th style='width:30%'>{total_size}</th><td><strong>".$data->size_human."</strong></td></tr>";
    $html[]="<tr><th>{apparent_size}</th><td>".FormatBytes($data->apparent_sz)."</td></tr>";
    $html[]="<tr><th>{files}</th><td>".$tpl->FormatNumber($data->file_count)."</td></tr>";
    $html[]="<tr><th>{directories}</th><td>".$tpl->FormatNumber($data->dir_count)."</td></tr>";
    $html[]="</table>";
    $html[]="</div>";
    $html[]="</div>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function history_chart(){
    $tpl=new template_admin();
    $path=$_GET["path"] ?? "/";
    $days=intval($_GET["days"] ?? 30);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/treesize/chart/timeline?path=".urlencode($path)."&days=$days"));

    if(!$json->success || !isset($json->data->data->labels) || count($json->data->data->labels)==0){
        $html[]=$tpl->div_warning("{no_history_data}||{run_multiple_scans}.");
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        return;
    }

    // Convert timestamps to readable dates and prepare chart data
    $labels = $json->data->data->labels ?? [];
    $sizes = $json->data->data->datasets[0]->data ?? [];

    $formattedLabels = [];
    foreach($labels as $ts){
        $formattedLabels[] = date("Y-m-d H:i", $ts);
    }

    $chartConfig = [
        "type" => "line",
        "data" => [
            "labels" => $formattedLabels,
            "datasets" => [[
                "label" => "Size",
                "data" => $sizes,
                "borderColor" => "#36A2EB",
                "backgroundColor" => "rgba(54, 162, 235, 0.1)",
                "fill" => true,
                "tension" => 0.1
            ]]
        ],
        "options" => [
            "responsive" => true,
            "plugins" => [
                "title" => [
                    "display" => true,
                    "text" => "Disk Usage Over Time"
                ],
                "tooltip" => [
                    "callbacks" => []
                ]
            ],
            "scales" => [
                "y" => [
                    "beginAtZero" => false
                ]
            ]
        ]
    ];

    $chartData = json_encode($chartConfig);

    $html[]="<div class='row'>";
    $html[]="<div class='col-md-12'>";
    $html[]="<canvas id='history-chart' style='max-height:400px'></canvas>";
    $html[]="</div>";
    $html[]="</div>";

    $html[]="<script>
    function formatBytesHistory(bytes) {
        if (bytes === 0) return '0 B';
        var k = 1024;
        var sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    var historyConfig = $chartData;

    // Add tooltip callback for human readable sizes
    historyConfig.options.plugins.tooltip.callbacks = {
        label: function(context) {
            return 'Size: ' + formatBytesHistory(context.raw);
        }
    };

    // Add Y-axis tick formatter
    historyConfig.options.scales.y.ticks = {
        callback: function(value) {
            return formatBytesHistory(value);
        }
    };

    var ctx = document.getElementById('history-chart').getContext('2d');
    new Chart(ctx, historyConfig);
    </script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
