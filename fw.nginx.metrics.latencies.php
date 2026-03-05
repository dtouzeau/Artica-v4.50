<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["js"])){js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["graph"])){graph();exit;}
if(isset($_GET["chart-data-js"])){chart_data_js();exit;}
if(isset($_GET["realtime-data"])){realtime_data();exit;}

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["js"]);
    $sock=new socksngix($ID);
    $servicename=$sock->GetServiceName();
    return $tpl->js_dialog2("#$ID - $servicename {latency}", "$page?tabs=$ID");
}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["tabs"]);
    $array["{realtime}"]="$page?graph=yes&time=realtime&serviceid=$ID";
    $array["{today}"]="$page?graph=yes&time=today&serviceid=$ID";
    $array["{last_48_h}"]="$page?graph=yes&time=48h&serviceid=$ID";
    $array["{this_week}"]="$page?graph=yes&time=week&serviceid=$ID";
    echo $tpl->tabs_default($array);
    return true;
}

function graph():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"] ?? 0);
    $time=trim($_GET["time"] ?? "realtime");
    if($serviceid<1){return false;}

    if($time=="realtime"){
        $html[]="<div style='margin:10px'>";
        $html[]="<div id='realtime-area-$serviceid'></div>";
        $html[]="</div>";
        $html[]="<script>";
        $html[]="LoadAjax('realtime-area-$serviceid','$page?realtime-data=yes&serviceid=$serviceid');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    // today, 48h, week: show widgets + line chart + error chart
    $endpoint=build_metrics_endpoint($time,$serviceid);
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX($endpoint);
    $json=json_decode($data);

    if(!$json || !isset($json->Status) || !$json->Status || !isset($json->Data)){
        $err=isset($json->Error) ? $json->Error : "{protocol_error}";
        echo $tpl->_ENGINE_parse_body($tpl->div_warning($err));
        return false;
    }

    $points=$json->Data;
    if(!is_array($points) || count($points)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data_available}"));
        return false;
    }

    // compute summary from points
    $totalCount=0;$totalErrors=0;$sumAvg=0;$globalMax=0;$globalMin=PHP_FLOAT_MAX;
    foreach($points as $p){
        $totalCount+=intval($p->count ?? 0);
        $totalErrors+=intval($p->errors ?? 0);
        $sumAvg+=floatval($p->avg_ms ?? 0);
        $mx=floatval($p->max_ms ?? 0);
        $mn=floatval($p->min_ms ?? 0);
        if($mx>$globalMax) $globalMax=$mx;
        if($mn<$globalMin && $mn>0) $globalMin=$mn;
    }
    $avgMs=count($points)>0 ? round($sumAvg/count($points),1) : 0;
    $globalMax=round($globalMax,1);
    if($globalMin==PHP_FLOAT_MAX) $globalMin=0;
    $globalMin=round($globalMin,1);

    $w_avg=$tpl->widget_style1($avgMs>500?"yellow-bg":"green-bg","fa-solid fa-gauge-high","{latency}","$avgMs ms");
    $w_max=$tpl->widget_style1($globalMax>1000?"red-bg":"navy-bg","fa-solid fa-arrow-up","{maximum}","$globalMax ms");
    $w_min=$tpl->widget_style1("lazur-bg","fa-solid fa-arrow-down","{minimum}","$globalMin ms");

    $html[]="<div style='margin:10px'>";

    $html[]="<table style='width:100%'><tr>";
    $html[]="<td style='width:20%'>$w_avg</td>";
    $html[]="<td style='width:20%;padding-left:5px'>$w_max</td>";
    $html[]="<td style='width:20%;padding-left:5px'>$w_min</td>";
    $html[]="</tr></table>";

    // latency line chart
    $html[]="<div class='ibox' style='margin-top:10px'><div class='ibox-title'><h5>{latency} (ms)</h5></div>";
    $html[]="<div class='ibox-content'><canvas id='chart-latency-$serviceid' height='140'></canvas></div></div>";

    // errors bar chart
    if($totalErrors>0){
        $html[]="<div class='ibox' style='margin-top:10px'><div class='ibox-title'><h5>{errors} & {probes}</h5></div>";
        $html[]="<div class='ibox-content'><canvas id='chart-errors-$serviceid' height='100'></canvas></div></div>";
    }

    $html[]="</div>";

    $html[]="<script>";
    $html[]="Loadjs('$page?chart-data-js=yes&serviceid=$serviceid&time=$time');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function build_metrics_endpoint(string $time, int $serviceid):string{
    switch($time){
        case "today": return "/backends-scanner/metrics/today/$serviceid";
        case "48h":   return "/backends-scanner/metrics/48h/$serviceid";
        case "week":  return "/backends-scanner/metrics/week/$serviceid";
        default:      return "/backends-scanner/metrics/today/$serviceid";
    }
}

function chart_data_js():bool{
    header("content-type: application/x-javascript");
    $serviceid=intval($_GET["serviceid"] ?? 0);
    $time=trim($_GET["time"] ?? "today");
    if($serviceid<1){echo "console.error('no serviceid');";return false;}

    $endpoint=build_metrics_endpoint($time,$serviceid);
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX($endpoint);
    $json=json_decode($data);

    if(!$json || !isset($json->Status) || !$json->Status || !isset($json->Data)){
        echo "console.error('no metrics data');";
        return false;
    }

    $points=$json->Data;
    if(!is_array($points) || count($points)==0){
        echo "console.error('empty metrics');";
        return false;
    }

    $dateFmt=($time=="week") ? "m-d H:00" : "m-d H:i";
    $labels=[];$avg=[];$max=[];$min=[];$errors=[];$counts=[];
    $hasErrors=false;

    foreach($points as $p){
        $ts=strtotime($p->timestamp ?? "");
        $labels[]=$ts ? date($dateFmt,$ts) : "";
        $avg[]=round(floatval($p->avg_ms ?? 0),1);
        $max[]=round(floatval($p->max_ms ?? 0),1);
        $min[]=round(floatval($p->min_ms ?? 0),1);
        $e=intval($p->errors ?? 0);
        $errors[]=$e;
        if($e>0) $hasErrors=true;
        $counts[]=intval($p->count ?? 0);
    }

    $labelsJson=json_encode($labels);
    $js=[];
    $js[]="(function(){";

    // latency line chart
    $js[]="try{ var el=document.getElementById('chart-latency-$serviceid');";
    $js[]="if(el){new Chart(el,{type:'line',data:{";
    $js[]="labels:$labelsJson,";
    $js[]="datasets:[";
    $js[]="{label:'Average',data:".json_encode($avg).",borderColor:'#1ab394',backgroundColor:'rgba(26,179,148,0.1)',fill:true,tension:0.3,pointRadius:1,borderWidth:2},";
    $js[]="{label:'Maximum',data:".json_encode($max).",borderColor:'#ed5565',backgroundColor:'transparent',fill:false,tension:0.3,pointRadius:0,borderWidth:1,borderDash:[4,4]},";
    $js[]="{label:'Minimum',data:".json_encode($min).",borderColor:'#1c84c6',backgroundColor:'transparent',fill:false,tension:0.3,pointRadius:0,borderWidth:1,borderDash:[4,4]}";
    $js[]="]},options:{responsive:true,interaction:{mode:'index',intersect:false},";
    $js[]="scales:{y:{beginAtZero:true,title:{display:true,text:'ms'}}},";
    $js[]="plugins:{legend:{position:'top'},tooltip:{callbacks:{label:function(ctx){return ctx.dataset.label+': '+ctx.parsed.y+' ms'}}}}";
    $js[]="}})}}catch(e){console.error('chart-latency',e);}";

    // errors + probes chart
    if($hasErrors){
        $js[]="try{ var el=document.getElementById('chart-errors-$serviceid');";
        $js[]="if(el){new Chart(el,{type:'bar',data:{";
        $js[]="labels:$labelsJson,";
        $js[]="datasets:[";
        $js[]="{label:'Probes',data:".json_encode($counts).",backgroundColor:'rgba(26,179,148,0.3)',borderColor:'#1ab394',borderWidth:1,yAxisID:'y',order:2},";
        $js[]="{label:'Errors',data:".json_encode($errors).",backgroundColor:'rgba(237,85,101,0.7)',borderColor:'#ed5565',borderWidth:1,yAxisID:'y1',order:1}";
        $js[]="]},options:{responsive:true,interaction:{mode:'index',intersect:false},";
        $js[]="scales:{y:{type:'linear',position:'left',beginAtZero:true,title:{display:true,text:'Probes'}},";
        $js[]="y1:{type:'linear',position:'right',beginAtZero:true,grid:{drawOnChartArea:false},title:{display:true,text:'Errors'}}},";
        $js[]="plugins:{legend:{position:'top'}}";
        $js[]="}})}}catch(e){console.error('chart-errors',e);}";
    }

    $js[]="})();";
    echo implode("\n",$js);
    return true;
}

function realtime_data():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $serviceid=intval($_GET["serviceid"] ?? 0);
    if($serviceid<1){return false;}

    // fetch overall average
    $avgData=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/backends-scanner/average/$serviceid");
    $avgJson=json_decode($avgData);
    $avgMs=0;$sampleCount=0;$oldest="";$newest="";
    if($avgJson && isset($avgJson->Status) && $avgJson->Status && isset($avgJson->Data)){
        $d=$avgJson->Data;
        $avgMs=round(floatval($d->average_ms ?? 0),1);
        $sampleCount=intval($d->sample_count ?? 0);
        $oldest=$d->oldest_sample ?? "";
        $newest=$d->newest_sample ?? "";
    }

    // run a live scan
    $scanData=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/backends-scanner/run/$serviceid");
    $scanJson=json_decode($scanData);
    $entries=[];
    if($scanJson && isset($scanJson->Status) && $scanJson->Status && isset($scanJson->Data)){
        $entries=is_array($scanJson->Data) ? $scanJson->Data : [];
    }

    // widgets
    $w_avg=$tpl->widget_style1($avgMs>500?"yellow-bg":"green-bg","fa-solid fa-gauge-high","{latency}","$avgMs ms");


    $newestFmt=$newest ? date("Y-m-d H:i:s",strtotime($newest)) : "-";
    $oldestFmt=$oldest ? date("Y-m-d H:i:s",strtotime($oldest)) : "-";


    $currentBackends=count($entries);
    $currentErrors=0;$currentMaxMs=0;
    foreach($entries as $e){
        if(!empty($e->error)) $currentErrors++;
        $ms=floatval($e->latency_ms ?? 0);
        if($ms>$currentMaxMs) $currentMaxMs=$ms;
    }
    $currentMaxMs=round($currentMaxMs,1);
    $w_backends=$tpl->widget_style1($currentErrors>0?"yellow-bg":"green-bg","fa-solid fa-server","{backends}","$currentBackends");

    $html[]="<table style='width:100%'><tr>";
    $html[]="<td style='width:25%'>$w_avg</td>";
    $html[]="<td style='width:25%;padding-left:5px'>$w_backends</td>";
    $html[]="</tr></table>";

    // per-backend table
    if(count($entries)>0){
        $html[]="<div class='ibox' style='margin-top:10px'><div class='ibox-title'><h5>{backends} {latency} ({realtime}) ($newestFmt)</h5></div>";
        $html[]="<div class='ibox-content'>";

        $t=time();
        $html[]="<table id='table-$t' class='footable table table-stripped' data-page-size='50'>";
        $html[]="<thead><tr>";
        $html[]="<th data-sortable=true data-type='text'>{backend}</th>";
        $html[]="<th data-sortable=true data-type='number'>{latency} (ms)</th>";
        $html[]="<th data-sortable=true data-type='number'>HTTP {status}</th>";
        $html[]="<th data-sortable=false>{error}</th>";
        $html[]="<th data-sortable=false style='width:30%'>&nbsp;</th>";
        $html[]="</tr></thead><tbody>";

        $maxLatency=1;
        foreach($entries as $e){
            $ms=floatval($e->latency_ms ?? 0);
            if($ms>$maxLatency) $maxLatency=$ms;
        }

        $TRCLASS=null;
        foreach($entries as $e){
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

            $backend=htmlspecialchars($e->backend ?? "",ENT_QUOTES);
            $ms=round(floatval($e->latency_ms ?? 0),1);
            $status=intval($e->status ?? 0);
            $error=htmlspecialchars($e->error ?? "",ENT_QUOTES);

            // color based on latency and status
            if(!empty($e->error) || $status>=500){
                $barColor="#ed5565";
                $statusBadge="<span class='badge' style='background:#ed5565;color:#fff'>$status</span>";
            }elseif($status>=400){
                $barColor="#f8ac59";
                $statusBadge="<span class='badge' style='background:#f8ac59;color:#fff'>$status</span>";
            }elseif($ms>500){
                $barColor="#f8ac59";
                $statusBadge="<span class='badge' style='background:#1ab394;color:#fff'>$status</span>";
            }else{
                $barColor="#1ab394";
                $statusBadge="<span class='badge' style='background:#1ab394;color:#fff'>$status</span>";
            }

            if($status==0) $statusBadge="<span class='badge' style='background:#ed5565;color:#fff'>-</span>";

            $pct=$maxLatency>0 ? round(($ms/$maxLatency)*100) : 0;
            $bar="<div style='background:#f5f5f5;border-radius:3px;height:18px;width:100%'>";
            $bar.="<div style='background:$barColor;height:18px;border-radius:3px;width:{$pct}%;min-width:2px'></div></div>";

            $errorCell=$error ? "<span style='color:#ed5565'>$error</span>" : "<span style='color:#1ab394'>OK</span>";

            $html[]="<tr class='$TRCLASS'>";
            $html[]="<td><span style='font-family:monospace'>$backend</span></td>";
            $html[]="<td><strong>$ms</strong></td>";
            $html[]="<td>$statusBadge</td>";
            $html[]="<td>$errorCell</td>";
            $html[]="<td>$bar</td>";
            $html[]="</tr>";
        }

        $html[]="</tbody></table>";
        $html[]="<script>NoSpinner();";
        $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
        $html[]="$(document).ready(function(){ $('#table-$t').footable({";
        $html[]="\"sorting\":{\"enabled\":true}";
        $html[]="});});</script>";

        $html[]="</div></div>";
    }else{
        $html[]="<div style='margin-top:10px'>".$tpl->div_info("{no_data_available}")."</div>";
    }

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
