<?php
/**
 * Conntrackd Dashboard — Service status, live stats, historical charts.
 * Data from /conntrackd/* REST API (bbolt-backed metrics + live kernel data).
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}

if(isset($_GET["charts"]))      { render_charts(); exit; }
if(isset($_GET["live-panel"]))  { render_live(); exit; }
if(isset($_GET["top-panel"]))   { render_top(); exit; }
Start();

function _cd_api($path){
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($path));
    if(!is_object($json) || !property_exists($json,'success') || !$json->success) return null;
    return $json->data;
}

function _cd_fmt($range){
    $m=array('1h'=>'H:i','2h'=>'H:i','6h'=>'H:00','24h'=>'H:00','48h'=>'H:00','7d'=>'D d','30d'=>'M d');
    return isset($m[$range])?$m[$range]:'H:i';
}

function _sw($bg,$ico,$label,$val){
    return "<div class='col-md-2 col-sm-4'><div style='background:$bg;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>"
         ."<div style='font-size:20px;font-weight:700'>$val</div>"
         ."<div style='font-size:11px'><i class='$ico'></i> $label</div></div></div>";
}

// ── Main page ────────────────────────────────────────────────────────────────

function Start(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    // Status
    $st=_cd_api("/conntrackd/status");
    $running=false; $pid='—'; $version='—'; $count=0; $max=0; $pct=0;
    if(is_object($st)){
        $running=!empty($st->running);
        $pid=$running?intval($st->pid):'—';
        $version=htmlspecialchars($st->version ?? '—');
        $count=intval($st->count ?? 0);
        $max=intval($st->max ?? 0);
        $pct=intval($st->percentage ?? 0);
    }

    $pctColor='#1ab394'; if($pct>50) $pctColor='#f8ac59'; if($pct>80) $pctColor='#ed5565';
    $statusBg=$running?'#1ab394':'#ed5565';
    $statusTxt=$running?'{running}':'{stopped}';

    $h=array();

    // Summary widgets
    $h[]="<div class='row' style='margin:10px 0 15px'>";
    $h[]="<div class='col-md-2 col-sm-4'><div style='background:$statusBg;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>";
    $h[]="<div style='font-size:18px;font-weight:700'>$statusTxt</div>";
    $h[]="<div style='font-size:11px'><i class='fas fa-heartbeat'></i> PID: $pid</div></div></div>";
    $h[]=_sw('#1c84c6','fas fa-link','{connections}',number_format($count));
    $h[]=_sw('#34495e','fas fa-database','nf_conntrack_max',number_format($max));
    $h[]="<div class='col-md-2 col-sm-4'><div style='background:$pctColor;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>";
    $h[]="<div style='font-size:20px;font-weight:700'>$pct%</div>";
    $h[]="<div style='font-size:11px'><i class='fas fa-percentage'></i> {usage}</div></div></div>";
    $h[]=_sw('#676a6c','fas fa-code-branch','v'.$version,'conntrackd');
    $h[]="</div>";

    // Range selector
    $ranges=array('1h'=>'{last_hour}','6h'=>'6h','24h'=>'24h','7d'=>'7d','30d'=>'30d');
    $h[]="<div style='margin-bottom:15px'>";
    $h[]="  <div class='btn-group' id='cd-range-btns'>";
    foreach($ranges as $v=>$l){
        $act=($v==='1h')?' active':'';
        $h[]="    <button class='btn btn-sm btn-default$act' onclick=\"CdRange('$v',this)\">$l</button>";
    }
    $h[]="  </div>";
    $h[]="  <button class='btn btn-sm btn-primary' style='margin-left:10px' onclick=\"CdRefresh();\">";
    $h[]="    <i class='fas fa-sync-alt'></i> {refresh}</button>";
    $h[]="</div>";

    // Panels
    $h[]="<div id='cd-charts'></div>";
    $h[]="<div class='row'>";
    $h[]="<div class='col-md-6'><div id='cd-live'></div></div>";
    $h[]="<div class='col-md-6'><div id='cd-top'></div></div>";
    $h[]="</div>";

    // JS
    $h[]="<script>";
    $h[]="var _cdRange='1h';";
    $h[]="function CdRange(r,btn){_cdRange=r;\$('#cd-range-btns .btn').removeClass('active');\$(btn).addClass('active');CdRefresh();}";
    $h[]="function CdRefresh(){";
    $h[]="  LoadAjax('cd-charts','$page?charts=yes&range='+_cdRange);";
    $h[]="  LoadAjaxSilent('cd-live','$page?live-panel=yes');";
    $h[]="  LoadAjaxSilent('cd-top','$page?top-panel=yes');";
    $h[]="}";
    $h[]="CdRefresh();";
    $h[]="</script>";

    $TINY_ARRAY["TITLE"]="Conntrackd";
    $TINY_ARRAY["ICO"]="fas fa-project-diagram";
    $TINY_ARRAY["EXPL"]="{conntrackd_explain}";
    $TINY_ARRAY["URL"]="system-conntrackd";
    $TINY_ARRAY["BUTTONS"]=null;
    $h[]="<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Charts ───────────────────────────────────────────────────────────────────

function render_charts(){
    $tpl=new template_admin();
    $activeText=$tpl->javascript_parse_text("{active2}");
    $updatedTexT=$tpl->javascript_parse_text("{updated}");
    $createdText=$tpl->javascript_parse_text("{created}");
    $destroyedText=$tpl->javascript_parse_text("{destroyed}");
    $range=trim($_GET["range"] ?? '24h');
    $fmt=_cd_fmt($range);

    $data=_cd_api("/conntrackd/metrics?range=$range");
    if(!is_array($data) || empty($data)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $isAgg=isset($data[0]->avg_count);
    $labels=array(); $counts=array(); $pcts=array(); $active=array();
    $created=array(); $updated=array(); $destroyed=array();
    $bytes=array(); $packets=array();
    $maxCounts=array(); $minCounts=array();
    $pr=$isAgg?'2':'1';

    foreach($data as $p){
        $labels[]=date($fmt,$p->timestamp);
        if($isAgg){
            $counts[]=sprintf('%.0f',$p->avg_count);
            $pcts[]=sprintf('%.1f',$p->avg_percent);
            $active[]=sprintf('%.0f',$p->avg_active);
            $maxCounts[]=sprintf('%.0f',$p->max_count);
            $minCounts[]=sprintf('%.0f',$p->min_count);
            $created[]=$p->total_created;
            $updated[]=$p->total_updated;
            $destroyed[]=$p->total_destroyed;
            $bytes[]=sprintf('%.2f',$p->total_bytes/1048576);
            $packets[]=$p->total_packets;
        } else {
            $counts[]=$p->count;
            $pcts[]=$p->percentage;
            $active[]=$p->active_connections;
            $created[]=$p->connections_created;
            $updated[]=$p->connections_updated;
            $destroyed[]=$p->connections_destroyed;
            $bytes[]=sprintf('%.2f',$p->traffic_bytes/1048576);
            $packets[]=$p->traffic_packets;
        }
    }

    $labelsJS="['".implode("','",$labels)."']";

    $h=array();
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.4.4.7.js'></script>";

    // ── 1. Connections + Usage % (dual axis) ──
    $uid1='cd-conn-'.uniqid();
    $ds1=array();
    if($isAgg && !empty($maxCounts)){
        $ds1[]="{label:'{connections} (peak)',data:[".implode(',',$maxCounts)."],borderColor:'rgba(231,76,60,0.3)',borderDash:[5,3],pointRadius:0,tension:0.3,fill:false,borderWidth:1.5}";
        $ds1[]="{label:'{connections} (min)',data:[".implode(',',$minCounts)."],borderColor:'rgba(39,174,96,0.3)',borderDash:[5,3],pointRadius:0,tension:0.3,fill:false,borderWidth:1.5}";
    }
    $ds1[]="{label:'{connections}',data:[".implode(',',$counts)."],borderColor:'rgb(54,162,235)',backgroundColor:'rgba(54,162,235,0.08)',pointRadius:$pr,tension:0.3,fill:true,borderWidth:2}";
    $ds1[]="{label:'$activeText',data:[".implode(',',$active)."],borderColor:'rgb(75,192,192)',pointRadius:0,tension:0.3,fill:false,borderWidth:2}";
    $ds1[]="{label:'%',data:[".implode(',',$pcts)."],borderColor:'rgb(255,99,132)',borderDash:[5,5],pointRadius:0,tension:0.3,fill:false,yAxisID:'pct',borderWidth:1.5}";

    $h[]=_panel('fas fa-link','{connections}','cd-conn',$uid1,$labelsJS,'['.implode(',',$ds1).']',
        "y:{beginAtZero:true,title:{display:true,text:'{connections}'}},pct:{position:'right',beginAtZero:true,max:100,title:{display:true,text:'%'},grid:{drawOnChartArea:false}}");

    // ── 2. Connection Lifecycle (created/updated/destroyed) ──
    $uid2='cd-life-'.uniqid();
    $ds2=array();
    $ds2[]="{label:'$createdText',data:[".implode(',',$created)."],borderColor:'rgb(75,192,192)',backgroundColor:'rgba(75,192,192,0.08)',pointRadius:$pr,tension:0.3,fill:true,borderWidth:2}";
    $ds2[]="{label:'$updatedTexT',data:[".implode(',',$updated)."],borderColor:'rgb(54,162,235)',pointRadius:$pr,tension:0.3,fill:false,borderWidth:2}";
    $ds2[]="{label:'$destroyedText',data:[".implode(',',$destroyed)."],borderColor:'rgb(255,99,132)',pointRadius:$pr,tension:0.3,fill:false,borderWidth:2}";

    $h[]=_panel('fas fa-recycle','{lifecycle}','cd-life',$uid2,$labelsJS,'['.implode(',',$ds2).']',
        "y:{beginAtZero:true,title:{display:true,text:'{connections}'}}");

    // ── 3. Traffic (bytes + packets, bar) ──
    $uid3='cd-traffic-'.uniqid();
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-tachometer-alt'></i>&nbsp; {bandwidth}</h5></div>";
    $h[]="<div class='ibox-content'><div style='position:relative;height:260px'><canvas id='$uid3'></canvas></div></div></div>";
    $h[]="<script>";
    $h[]="new Chart(document.getElementById('$uid3'),{type:'bar',";
    $h[]="data:{labels:$labelsJS,datasets:[";
    $h[]="{label:'MB',data:[".implode(',',$bytes)."],backgroundColor:'rgba(54,162,235,0.7)',borderRadius:3,yAxisID:'bytes'},";
    $h[]="{label:'Packets',data:[".implode(',',$packets)."],backgroundColor:'rgba(255,159,64,0.7)',borderRadius:3,yAxisID:'pkts'}";
    $h[]="]},";
    $h[]="options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
    $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:12}}},";
    $h[]="scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:20}},";
    $h[]="bytes:{position:'left',beginAtZero:true,title:{display:true,text:'MB'}},";
    $h[]="pkts:{position:'right',beginAtZero:true,title:{display:true,text:'Packets'},grid:{drawOnChartArea:false}}}}});";
    $h[]="</script>";

    $h[]="<script>NoSpinner();</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

function _panel($icon,$title,$prefix,$uid,$labelsJS,$dsJS,$yScales){
    $h=array();
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='$icon'></i>&nbsp; $title</h5></div>";
    $h[]="<div class='ibox-content'><div style='position:relative;height:280px'><canvas id='$uid'></canvas></div></div></div>";
    $h[]="<script>";
    $h[]="new Chart(document.getElementById('$uid'),{type:'line',";
    $h[]="data:{labels:$labelsJS,datasets:$dsJS},";
    $h[]="options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
    $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:12}},";
    $h[]="tooltip:{callbacks:{label:function(c){return c.dataset.label+': '+c.parsed.y.toLocaleString();}}}},";
    $h[]="scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:20}},$yScales}}});";
    $h[]="</script>";
    return implode("\n",$h);
}

// ── Live stats panel ─────────────────────────────────────────────────────────

function render_live(){
    $tpl=new template_admin();
    $live=_cd_api("/conntrackd/live");
    $destroyedText=$tpl->javascript_parse_text("{destroyed}");
    if(!is_object($live)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $h=array();
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-microchip'></i>&nbsp; {realtime_requests}</h5></div>";
    $h[]="<div class='ibox-content'>";

    // Daemon stats
    $d=$live->daemon ?? new stdClass();
    $h[]="<table class='table table-condensed' style='font-size:12px'>";
    $h[]="<tr><td><strong>{active_connections}</strong></td><td style='text-align:right'>".number_format(intval($d->active_connections ?? 0))."</td></tr>";
    $h[]="<tr><td><strong>{created}</strong></td><td style='text-align:right'>".number_format(intval($d->connections_created ?? 0))."</td></tr>";
    $h[]="<tr><td><strong>{updated}</strong></td><td style='text-align:right'>".number_format(intval($d->connections_updated ?? 0))."</td></tr>";
    $h[]="<tr><td><strong>$destroyedText</strong></td><td style='text-align:right'>".number_format(intval($d->connections_destroyed ?? 0))."</td></tr>";

    $trafficBytes=intval($d->traffic_bytes ?? 0);
    $trafficMB=($trafficBytes>0)?sprintf('%.1f',$trafficBytes/1048576):'0';
    $h[]="<tr><td><strong>{bandwidth}</strong></td><td style='text-align:right'>$trafficMB MB</td></tr>";
    $h[]="<tr><td><strong>Packets</strong></td><td style='text-align:right'>".number_format(intval($d->traffic_packets ?? 0))."</td></tr>";
    $h[]="</table>";

    // CPU stats pie
    $cpuStats=(array)($live->cpu_stats ?? array());
    if(!empty($cpuStats)){
        $cpuLabels=array(); $cpuInserts=array(); $cpuDrops=array();
        $colors=array('#3498db','#e74c3c','#2ecc71','#f39c12','#9b59b6','#1abc9c','#e67e22','#34495e');
        foreach($cpuStats as $c){
            $cpuLabels[]='CPU '.intval($c->cpu ?? 0);
            $cpuInserts[]=intval($c->insert ?? 0);
            $cpuDrops[]=intval($c->drop ?? 0);
        }
        $uid='cd-cpu-'.uniqid();

        $h[]="<h5 style='margin-top:10px'><i class='fas fa-chart-pie'></i> CPU {distribution}</h5>";
        $h[]="<div style='position:relative;height:180px'><canvas id='$uid'></canvas></div>";
        $h[]="<script src='angular/js/plugins/chartJs/Chart.min.4.4.7.js'></script>";
        $h[]="<script>";

        $totalInserts=array_sum($cpuInserts);
        if($totalInserts>0){
            $labelsJS=json_encode($cpuLabels);
            $valsJS='['.implode(',',$cpuInserts).']';
            $clrsJS="['".implode("','",array_slice($colors,0,count($cpuLabels)))."']";
            $h[]="new Chart(document.getElementById('$uid'),{type:'doughnut',";
            $h[]="data:{labels:$labelsJS,datasets:[{data:$valsJS,backgroundColor:$clrsJS,borderWidth:0,hoverOffset:6}]},";
            $h[]="options:{responsive:true,maintainAspectRatio:false,cutout:'50%',";
            $h[]="plugins:{legend:{position:'right',labels:{font:{size:10},boxWidth:8,padding:4}},";
            $h[]="tooltip:{callbacks:{label:function(c){return c.label+': '+c.parsed.toLocaleString()+' inserts';}}}}}});";
        } else {
            $h[]="document.getElementById('$uid').parentNode.innerHTML='<p class=\"text-muted\" style=\"text-align:center;padding:20px\">{no_data}</p>';";
        }
        $h[]="</script>";
    }

    $h[]="</div></div>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Top sources/destinations panel ───────────────────────────────────────────

function render_top(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $srcData=_cd_api("/conntrackd/top-sources?limit=10&sort=connections");
    $dstData=_cd_api("/conntrackd/top-destinations?limit=10&sort=connections");

    $h=array();

    // Top sources
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-arrow-right'></i>&nbsp; {top} {sources}</h5></div>";
    $h[]="<div class='ibox-content' style='padding:0'>";
    if(is_object($srcData) && is_array($srcData->sources) && !empty($srcData->sources)){
        $h[]="<table class='table table-condensed' style='margin:0;font-size:12px'>";
        $h[]="<thead><tr style='background:#f5f5f5'><th>{ipaddr}</th><th style='text-align:right'>{connections}</th><th style='text-align:right'>{bandwidth}</th></tr></thead><tbody>";
        $filteredSrc=array_values(array_filter($srcData->sources,function($s){$ip=$s->ip??'';return $ip!=='127.0.0.1'&&substr($ip,0,4)!=='127.'&&$ip!=='::1';}));
        $maxConns=!empty($filteredSrc)?intval($filteredSrc[0]->connections ?? 1):1;
        foreach($filteredSrc as $s){
            $ip=htmlspecialchars($s->ip ?? '');
            $conns=intval($s->connections ?? 0);
            $bw=sprintf('%.1f',intval($s->bytes ?? 0)/1024);
            $barW=$maxConns>0?intval($conns/$maxConns*100):0;
            $h[]="<tr>";
            $h[]="<td><span style='font-family:monospace'>$ip</span></td>";
            $h[]="<td style='text-align:right'><div style='display:inline-block;width:60px;height:12px;background:#eee;border-radius:3px;margin-right:5px;vertical-align:middle'><div style='width:{$barW}%;height:100%;background:#3498db;border-radius:3px'></div></div>".number_format($conns)."</td>";
            $h[]="<td style='text-align:right;color:#999'>$bw KB</td>";
            $h[]="</tr>";
        }
        $h[]="</tbody></table>";
    } else {
        $h[]="<p style='padding:15px;color:#999'>{no_data}</p>";
    }
    $h[]="</div></div>";

    // Top destinations
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-arrow-left'></i>&nbsp; {top} {destinations}</h5></div>";
    $h[]="<div class='ibox-content' style='padding:0'>";
    if(is_object($dstData) && is_array($dstData->destinations) && !empty($dstData->destinations)){
        $h[]="<table class='table table-condensed' style='margin:0;font-size:12px'>";
        $h[]="<thead><tr style='background:#f5f5f5'><th>{ipaddr}</th><th style='text-align:right'>{connections}</th><th style='text-align:right'>{bandwidth}</th></tr></thead><tbody>";
        $filteredDst=array_values(array_filter($dstData->destinations,function($d){$ip=$d->ip??'';return $ip!=='127.0.0.1'&&substr($ip,0,4)!=='127.'&&$ip!=='::1';}));
        $maxConns=!empty($filteredDst)?intval($filteredDst[0]->connections ?? 1):1;
        foreach($filteredDst as $d){
            $ip=htmlspecialchars($d->ip ?? '');
            $conns=intval($d->connections ?? 0);
            $bw=sprintf('%.1f',intval($d->bytes ?? 0)/1024);
            $barW=$maxConns>0?intval($conns/$maxConns*100):0;
            $h[]="<tr>";
            $h[]="<td><span style='font-family:monospace'>$ip</span></td>";
            $h[]="<td style='text-align:right'><div style='display:inline-block;width:60px;height:12px;background:#eee;border-radius:3px;margin-right:5px;vertical-align:middle'><div style='width:{$barW}%;height:100%;background:#e74c3c;border-radius:3px'></div></div>".number_format($conns)."</td>";
            $h[]="<td style='text-align:right;color:#999'>$bw KB</td>";
            $h[]="</tr>";
        }
        $h[]="</tbody></table>";
    } else {
        $h[]="<p style='padding:15px;color:#999'>{no_data}</p>";
    }
    $h[]="</div></div>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}
