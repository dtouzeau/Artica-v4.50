<?php
/**
 * Agent TreeSize - Remote disk usage analyzer
 * Displays disk usage data pushed from remote netagents via bbolt snapshots
 * APIs: /netagents/treesize/{id}/ (children, top, stats, history, latest, dir)
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
include_once dirname(__FILE__) . '/ressources/class.sockets.inc';
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["directory-table"])){directory_table();exit;}
if(isset($_GET["top-dirs"])){top_dirs();exit;}
if(isset($_GET["chart-data"])){chart_data();exit;}
if(isset($_GET["history-chart"])){history_chart();exit;}
js();

function js():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $users=new usersMenus();
    if(!$users->AsArticaMetaAdmin){
        return $tpl->js_error("{no_privileges}");
    }
    $id=$_GET["id"];
    $net=new ArticaNetAgents($id);
    $AgentName=$net->GetAgentHostname();
    return $tpl->js_dialog7("{disk_usage_analyzer} - $AgentName", "$page?tabs=$id", 950);
}

function tabs(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["tabs"]);

    // Check if treesize data exists for this agent
    $stats=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/treesize/$id/stats"));
    if(isset($stats->Status) && !$stats->Status){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning($stats->Error ?? "{error}"));
        return;
    }
    if(!isset($stats->exists) || !$stats->exists){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data_available}"));
        return;
    }

    // Scan summary bar
    $dirEntries=intval($stats->dir_entries ?? 0);
    $dbSizeHuman=$stats->db_size_human ?? "0 B";
    $latest=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/treesize/$id/latest?path=/"));
    $scanInfo="";
    if(isset($latest->end_time) && $latest->end_time > 0){
        $scanDate=$tpl->time_to_date($latest->end_time, true);
        $totalSizeHuman=FormatBytes(($latest->total_size ?? 0) / 1024);
        $totalFiles=$tpl->FormatNumber($latest->total_files ?? 0);
        $totalDirs=$tpl->FormatNumber($latest->total_dirs ?? 0);
        $scanInfo="<div class='alert alert-info' style='margin-bottom:10px'>";
        $scanInfo.="<i class='fas fa-info-circle'></i> <strong>{last_scan}:</strong> $scanDate";
        $scanInfo.=" &mdash; <strong>{total_size}:</strong> $totalSizeHuman";
        $scanInfo.=" &mdash; <strong>{files}:</strong> $totalFiles";
        $scanInfo.=" &mdash; <strong>{directories}:</strong> $totalDirs";
        $scanInfo.="</div>";
    }

    $html=array();
    $html[]=$scanInfo;

    // Tab navigation
    $html[]="<ul class='nav nav-tabs' id='agent-treesize-tabs'>";
    $html[]="<li class='active'><a data-toggle='tab' href='#ats-browser-$id'><i class='fas fa-folder-tree'></i> {directory_browser}</a></li>";
    $html[]="<li><a data-toggle='tab' href='#ats-top-$id'><i class='fas fa-sort-amount-down'></i> Top</a></li>";
    $html[]="<li><a data-toggle='tab' href='#ats-charts-$id'><i class='fas fa-chart-pie'></i> {charts}</a></li>";
    $html[]="<li><a data-toggle='tab' href='#ats-history-$id'><i class='fas fa-chart-line'></i> {history}</a></li>";
    $html[]="</ul>";

    // Tab content
    $html[]="<div class='tab-content' style='padding:15px;background:#fff;border:1px solid #ddd;border-top:none'>";

    // Directory Browser tab
    $html[]="<div id='ats-browser-$id' class='tab-pane fade in active'>";
    $html[]="<div class='input-group' style='margin-bottom:15px'>";
    $html[]="<span class='input-group-addon'><i class='fas fa-folder'></i></span>";
    $html[]="<input type='text' class='form-control' id='ats-browse-path' value='/' placeholder='/' onkeydown=\"if(event.key==='Enter'){AtsBrowse();return false;}\">";
    $html[]="<span class='input-group-btn'>";
    $html[]="<button class='btn btn-primary' onclick=\"AtsBrowse()\"><i class='fas fa-search'></i> {browse}</button>";
    $html[]="</span>";
    $html[]="</div>";
    $html[]="<div id='ats-directory-content'></div>";
    $html[]="</div>";

    // Top Directories tab
    $html[]="<div id='ats-top-$id' class='tab-pane fade'>";
    $html[]="<div id='ats-top-content'></div>";
    $html[]="</div>";

    // Charts tab
    $html[]="<div id='ats-charts-$id' class='tab-pane fade'>";
    $html[]="<div class='row'>";
    $html[]="<div class='col-md-6'><canvas id='ats-bar-chart' style='max-height:500px'></canvas></div>";
    $html[]="<div class='col-md-6'><canvas id='ats-pie-chart' style='max-height:500px'></canvas></div>";
    $html[]="</div>";
    $html[]="</div>";

    // History tab
    $html[]="<div id='ats-history-$id' class='tab-pane fade'>";
    $html[]="<div id='ats-history-content'></div>";
    $html[]="</div>";

    $html[]="</div>"; // tab-content

    // JavaScript
    $html[]="<script>";
    $html[]="var atsBarChart=null;";
    $html[]="var atsPieChart=null;";
    $html[]="function AtsBrowse(){";
    $html[]="    var p=document.getElementById('ats-browse-path').value||'/';";
    $html[]="    LoadAjax('ats-directory-content','$page?directory-table=yes&id=$id&path='+encodeURIComponent(p));";
    $html[]="}";
    $html[]="function AtsNavigate(path){";
    $html[]="    document.getElementById('ats-browse-path').value=path;";
    $html[]="    AtsBrowse();";
    $html[]="}";
    $html[]="function AtsLoadTop(){";
    $html[]="    LoadAjax('ats-top-content','$page?top-dirs=yes&id=$id&path=/');";
    $html[]="}";
    $html[]="function AtsLoadCharts(){";
    $html[]="    Loadjs('$page?chart-data=yes&id=$id&path=/');";
    $html[]="}";
    $html[]="function AtsLoadHistory(){";
    $html[]="    LoadAjax('ats-history-content','$page?history-chart=yes&id=$id&path=/');";
    $html[]="}";
    $html[]="\$('a[data-toggle=\"tab\"]').on('shown.bs.tab',function(e){";
    $html[]="    var t=\$(e.target).attr('href');";
    $html[]="    if(t=='#ats-top-$id'){AtsLoadTop();}";
    $html[]="    if(t=='#ats-charts-$id'){AtsLoadCharts();}";
    $html[]="    if(t=='#ats-history-$id'){AtsLoadHistory();}";
    $html[]="});";
    $html[]="AtsBrowse();";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function directory_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["id"]);
    $path=$_GET["path"] ?? "/";

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/treesize/$id/children?path=".urlencode($path)."&limit=100"));
    if(isset($json->Status) && !$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_data_available}"));
        return;
    }

    $entries=$json->entries ?? [];
    if(!is_array($entries) || count($entries)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_subdirectories}"));
        return;
    }

    // Breadcrumb navigation
    $pathParts=explode("/",trim($path,"/"));
    $breadcrumb=[];
    $breadcrumb[]="<a href=\"javascript:AtsNavigate('/')\">/</a>";
    $buildPath="";
    foreach($pathParts as $part){
        if($part==""){continue;}
        $buildPath.="/".$part;
        $breadcrumb[]="<a href=\"javascript:AtsNavigate('".addslashes($buildPath)."')\">".htmlspecialchars($part)."</a>";
    }

    $html=array();
    $html[]="<nav aria-label='breadcrumb'><ol class='breadcrumb' style='margin-bottom:10px'>".implode(" / ",$breadcrumb)."</ol></nav>";

    // Calculate total for percentage bars
    $totalSize=0;
    foreach($entries as $entry){
        $totalSize+=($entry->size ?? 0);
    }

    $html[]="<table class='table table-hover table-striped'>";
    $html[]="<thead><tr>";
    $html[]="<th style='width:45%'>{name}</th>";
    $html[]="<th style='width:20%'>{size}</th>";
    $html[]="<th style='width:15%'>{files}</th>";
    $html[]="<th style='width:20%'>{percentage}</th>";
    $html[]="</tr></thead><tbody>";

    foreach($entries as $entry){
        $entryPath=$entry->path ?? "";
        $name=basename($entryPath);
        if($name==""){$name=$entryPath;}

        $size=$entry->size ?? 0;
        $sizeHuman=FormatBytes($size/1024);
        $fileCount=$entry->file_count ?? 0;
        $dirCount=$entry->dir_count ?? 0;
        if($fileCount==0 && $dirCount==0){continue;}

        $pct=0;
        if($totalSize>0){$pct=round(($size/$totalSize)*100,1);}

        $barColor="success";
        if($pct>50){$barColor="warning";}
        if($pct>75){$barColor="danger";}

        $html[]="<tr onclick=\"AtsNavigate('".addslashes($entryPath)."')\" style='cursor:pointer'>";
        $html[]="<td><i class='fas fa-folder text-$barColor'></i> ".htmlspecialchars($name)."</td>";
        $html[]="<td>$sizeHuman</td>";
        $html[]="<td>".$tpl->FormatNumber($fileCount)." <small class='text-muted'>($dirCount dirs)</small></td>";
        $html[]="<td><div class='progress' style='margin-bottom:0'>";
        $html[]="<div class='progress-bar progress-bar-$barColor' style='width:{$pct}%'>$pct%</div>";
        $html[]="</div></td>";
        $html[]="</tr>";
    }

    $html[]="</tbody></table>";
    $html[]="<div class='well well-sm'><strong>{total}:</strong> ".FormatBytes($totalSize/1024)."</div>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function top_dirs(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["id"]);
    $path=$_GET["path"] ?? "/";

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/treesize/$id/top?path=".urlencode($path)."&limit=50"));
    if(isset($json->Status) && !$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_data_available}"));
        return;
    }

    $entries=$json->entries ?? [];
    if(!is_array($entries) || count($entries)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data_available}"));
        return;
    }

    $maxSize=$entries[0]->size ?? 1;
    $html=array();
    $html[]="<table class='table table-hover table-striped'>";
    $html[]="<thead><tr>";
    $html[]="<th>{path}</th>";
    $html[]="<th style='width:15%'>{size}</th>";
    $html[]="<th style='width:10%'>{files}</th>";
    $html[]="<th style='width:25%'>{percentage}</th>";
    $html[]="</tr></thead><tbody>";

    $TRCLASS=null;
    foreach($entries as $entry){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $entryPath=$entry->path ?? "";
        $size=$entry->size ?? 0;
        $sizeHuman=FormatBytes($size/1024);
        $fileCount=$entry->file_count ?? 0;

        $pct=0;
        if($maxSize>0){$pct=round(($size/$maxSize)*100,1);}

        $barColor="info";
        if($pct>50){$barColor="warning";}
        if($pct>80){$barColor="danger";}

        $html[]="<tr class='$TRCLASS' onclick=\"AtsNavigate('".addslashes($entryPath)."')\" style='cursor:pointer'>";
        $html[]="<td><i class='fas fa-folder'></i> ".htmlspecialchars($entryPath)."</td>";
        $html[]="<td nowrap>$sizeHuman</td>";
        $html[]="<td>".$tpl->FormatNumber($fileCount)."</td>";
        $html[]="<td><div class='progress' style='margin-bottom:0'>";
        $html[]="<div class='progress-bar progress-bar-$barColor' style='width:{$pct}%'>$pct%</div>";
        $html[]="</div></td>";
        $html[]="</tr>";
    }

    $html[]="</tbody></table>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function chart_data(){
    header("content-type: application/x-javascript");
    $id=intval($_GET["id"]);
    $path=$_GET["path"] ?? "/";

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/treesize/$id/top?path=".urlencode($path)."&limit=15"));
    if(isset($json->Status) && !$json->Status){
        echo "console.error('No treesize data');";
        return;
    }

    $entries=$json->entries ?? [];
    if(!is_array($entries) || count($entries)==0){
        echo "console.error('No entries');";
        return;
    }

    $labels=[];
    $sizes=[];
    $colors=[
        '#1ab394','#23c6c8','#1c84c6','#f8ac59','#ed5565',
        '#ab7df6','#5e6d82','#3498db','#2ecc71','#e74c3c',
        '#9b59b6','#f39c12','#1abc9c','#e67e22','#34495e'
    ];
    foreach($entries as $i => $entry){
        $name=basename($entry->path ?? "");
        if($name==""){$name=$entry->path ?? "/";}
        $labels[]=addslashes($name);
        $sizes[]=$entry->size ?? 0;
    }

    $labelsJson=json_encode($labels);
    $sizesJson=json_encode($sizes);
    $colorsJson=json_encode(array_slice($colors,0,count($labels)));

    $js=[];
    $js[]="function atsFmtBytes(b){";
    $js[]="    if(b===0)return '0 B';var k=1024;var s=['B','KB','MB','GB','TB','PB'];";
    $js[]="    var i=Math.floor(Math.log(b)/Math.log(k));";
    $js[]="    return parseFloat((b/Math.pow(k,i)).toFixed(2))+' '+s[i];";
    $js[]="}";

    // Destroy existing charts
    $js[]="var _c1=Chart.getChart('ats-bar-chart');if(_c1){_c1.destroy();}";
    $js[]="var _c2=Chart.getChart('ats-pie-chart');if(_c2){_c2.destroy();}";

    // Bar chart (horizontal)
    $js[]="var ctx1=document.getElementById('ats-bar-chart').getContext('2d');";
    $js[]="atsBarChart=new Chart(ctx1,{";
    $js[]="    type:'bar',";
    $js[]="    data:{labels:$labelsJson,datasets:[{label:'Size',data:$sizesJson,backgroundColor:$colorsJson}]},";
    $js[]="    options:{";
    $js[]="        indexAxis:'y',responsive:true,";
    $js[]="        plugins:{legend:{display:false},title:{display:true,text:'Top Directories'}},";
    $js[]="        scales:{x:{ticks:{callback:function(v){return atsFmtBytes(v);}}}}";
    $js[]="    }";
    $js[]="});";

    // Pie chart
    $js[]="var ctx2=document.getElementById('ats-pie-chart').getContext('2d');";
    $js[]="atsPieChart=new Chart(ctx2,{";
    $js[]="    type:'pie',";
    $js[]="    data:{labels:$labelsJson,datasets:[{data:$sizesJson,backgroundColor:$colorsJson}]},";
    $js[]="    options:{";
    $js[]="        responsive:true,";
    $js[]="        plugins:{";
    $js[]="            legend:{position:'right'},";
    $js[]="            title:{display:true,text:'{disk_usage}'},";
    $js[]="            tooltip:{callbacks:{label:function(ctx){return ctx.label+': '+atsFmtBytes(ctx.raw);}}}";
    $js[]="        }";
    $js[]="    }";
    $js[]="});";

    echo implode("\n",$js);
}

function history_chart(){
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $path=$_GET["path"] ?? "/";
    $days=intval($_GET["days"] ?? 30);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/treesize/$id/history?path=".urlencode($path)."&days=$days"));
    if(isset($json->Status) && !$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_data_available}"));
        return;
    }

    $entries=$json->entries ?? [];
    if(!is_array($entries) || count($entries)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_history_data}"));
        return;
    }

    $labels=[];
    $sizes=[];
    $files=[];
    foreach($entries as $entry){
        $labels[]=date("Y-m-d H:i",$entry->timestamp ?? 0);
        $sizes[]=$entry->size ?? 0;
        $files[]=$entry->files ?? 0;
    }

    $chartConfig=json_encode([
        "type"=>"line",
        "data"=>[
            "labels"=>$labels,
            "datasets"=>[
                [
                    "label"=>"Size",
                    "data"=>$sizes,
                    "borderColor"=>"#1c84c6",
                    "backgroundColor"=>"rgba(28,132,198,0.1)",
                    "fill"=>true,
                    "tension"=>0.1,
                    "yAxisID"=>"y"
                ],
                [
                    "label"=>"Files",
                    "data"=>$files,
                    "borderColor"=>"#1ab394",
                    "backgroundColor"=>"rgba(26,179,148,0.1)",
                    "fill"=>false,
                    "tension"=>0.1,
                    "yAxisID"=>"y1"
                ]
            ]
        ],
        "options"=>[
            "responsive"=>true,
            "plugins"=>[
                "title"=>["display"=>true,"text"=>"Disk Usage Over Time"],
                "tooltip"=>["callbacks"=>[]]
            ],
            "scales"=>[
                "y"=>["type"=>"linear","position"=>"left","beginAtZero"=>false],
                "y1"=>["type"=>"linear","position"=>"right","beginAtZero"=>false,"grid"=>["drawOnChartArea"=>false]]
            ]
        ]
    ]);

    $html=array();
    $html[]="<canvas id='ats-history-chart' style='max-height:400px'></canvas>";
    $html[]="<script>";
    $html[]="function atsHistFmt(b){";
    $html[]="    if(b===0)return '0 B';var k=1024;var s=['B','KB','MB','GB','TB','PB'];";
    $html[]="    var i=Math.floor(Math.log(b)/Math.log(k));";
    $html[]="    return parseFloat((b/Math.pow(k,i)).toFixed(2))+' '+s[i];";
    $html[]="}";
    $html[]="var _hc=Chart.getChart('ats-history-chart');if(_hc){_hc.destroy();}";
    $html[]="var hCfg=$chartConfig;";
    $html[]="hCfg.options.plugins.tooltip.callbacks={";
    $html[]="    label:function(ctx){";
    $html[]="        if(ctx.datasetIndex===0){return 'Size: '+atsHistFmt(ctx.raw);}";
    $html[]="        return 'Files: '+ctx.raw.toLocaleString();";
    $html[]="    }";
    $html[]="};";
    $html[]="hCfg.options.scales.y.ticks={callback:function(v){return atsHistFmt(v);}};";
    $html[]="var ctx=document.getElementById('ats-history-chart').getContext('2d');";
    $html[]="new Chart(ctx,hCfg);";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}
