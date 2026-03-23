<?php

$GLOBALS["MAIN_TITLE"]["filedesc"]="{APP_SQUID} {file_descriptors}";
$GLOBALS["MAIN_TITLE"]["squid_cache"]="{APP_SQUID} {requests}";
$GLOBALS["MAIN_TITLE"]["squidmem"]="{proxy_memory}";
$GLOBALS["MAIN_TITLE"]["system_cpu"]="{system} {cpu} {percentage}";
$GLOBALS["MAIN_TITLE"]["system_memory"]="{system} {memory} MB";
$GLOBALS["MAIN_TITLE"]["proxy_users"]="{APP_PROXY} {members}";


include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}
if(isset($_GET["popup"])){popup_start();exit;}
if(isset($_GET["popup2"])){popup();exit;}
if(isset($_POST["dhclient_mac"])){tests();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["results-popup"])){results_popup();exit;}
if(isset($_GET["popup-latency"])){popup_latency();exit;}
if(isset($_GET["popup-explain-load"])){popup_load();exit;}
if(isset($_GET["ct-chart"])){ct_chart_render();exit;}
if(isset($_GET["iface-popup"])){popup_iface();exit;}
if(isset($_GET["iface-chart"])){iface_chart_render();exit;}
js();


function js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $img=$_GET["img"];
    $title=$GLOBALS["MAIN_TITLE"][$img];
    $flat="";
    if(isset($_GET["flat"])){$flat="&flat=yes";}
    if(isset($_GET["iface"])){
        $iface=$_GET["iface"];
        return $tpl->js_dialog6("{interface}: ".$iface, "$page?iface-popup=$iface",1200);
    }

	return $tpl->js_dialog6($title, "$page?tabs=$img$flat",1200);
}
function _iface_date_format($range){
    $map=array('1h'=>'H:i','2h'=>'H:i','6h'=>'H:00','24h'=>'H:00','48h'=>'H:00','7d'=>'D d','30d'=>'M d','60d'=>'M Y','90d'=>'M Y');
    return isset($map[$range]) ? $map[$range] : 'H:i';
}

function popup_iface(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $iface=htmlspecialchars(trim($_GET["iface-popup"]));

    // Current values
    $latestJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/interfaces/metrics/".urlencode($iface)."/latest"));
    $curRx='—'; $curTx='—';
    if(is_object($latestJson) && !empty($latestJson->success) && is_object($latestJson->data)){
        $curRx=intval($latestJson->data->rx_mib ?? 0);
        $curTx=intval($latestJson->data->tx_mib ?? 0);
    }

    // Current hour
    $hourJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/interfaces/metrics/".urlencode($iface)."/current-hour"));
    $hourRx='—'; $hourTx='—';
    if(is_object($hourJson) && !empty($hourJson->success) && is_object($hourJson->data)){
        $hourRx=intval($hourJson->data->total_rx ?? 0);
        $hourTx=intval($hourJson->data->total_tx ?? 0);
    }

    $h=array();
    // Summary widgets
    $h[]="<div class='row' style='margin:10px 0 15px'>";
    $h[]="<div class='col-md-3'><div style='background:#1c84c6;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>";
    $h[]="<div style='font-size:22px;font-weight:700'>$curRx MiB</div>";
    $h[]="<div style='font-size:11px'><i class='fas fa-arrow-down'></i> RX ({last_sample})</div></div></div>";
    $h[]="<div class='col-md-3'><div style='background:#f8ac59;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>";
    $h[]="<div style='font-size:22px;font-weight:700'>$curTx MiB</div>";
    $h[]="<div style='font-size:11px'><i class='fas fa-arrow-up'></i> TX ({last_sample})</div></div></div>";
    $h[]="<div class='col-md-3'><div style='background:#1ab394;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>";
    $h[]="<div style='font-size:22px;font-weight:700'>$hourRx MiB</div>";
    $h[]="<div style='font-size:11px'><i class='fas fa-arrow-down'></i> RX ({this_hour})</div></div></div>";
    $h[]="<div class='col-md-3'><div style='background:#34495e;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>";
    $h[]="<div style='font-size:22px;font-weight:700'>$hourTx MiB</div>";
    $h[]="<div style='font-size:11px'><i class='fas fa-arrow-up'></i> TX ({this_hour})</div></div></div>";
    $h[]="</div>";

    // Range buttons
    $ranges=array("1h"=>"{last_hour}","6h"=>"6h","24h"=>"24h","7d"=>"7d","30d"=>"30d","90d"=>"90d");
    $h[]="<div style='margin-bottom:15px'>";
    $h[]="  <div class='btn-group' id='if-range-btns'>";
    foreach($ranges as $val=>$label){
        $active=($val==='24h')?' active':'';
        $h[]="    <button class='btn btn-sm btn-default$active' onclick=\"IfRange('$val',this)\">$label</button>";
    }
    $h[]="  </div>";
    $h[]="  <button class='btn btn-sm btn-primary' style='margin-left:10px' onclick=\"IfRefresh();\">";
    $h[]="    <i class='fas fa-sync-alt'></i> {refresh}</button>";
    $h[]="</div>";

    // Chart area
    $h[]="<div id='if-chart-area'></div>";

    // JS
    $ifEnc=urlencode($iface);
    $h[]="<script>";
    $h[]="var _ifRange='24h';";
    $h[]="function IfRange(r,btn){";
    $h[]="  _ifRange=r;";
    $h[]="  \$('#if-range-btns .btn').removeClass('active');";
    $h[]="  \$(btn).addClass('active');";
    $h[]="  IfRefresh();";
    $h[]="};";
    $h[]="function IfRefresh(){";
    $h[]="  LoadAjax('if-chart-area','$page?iface-chart=yes&iface=$ifEnc&range='+_ifRange);";
    $h[]="};";
    $h[]="IfRefresh();";
    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

function iface_chart_render(){
    $tpl=new template_admin();
    $iface=trim($_GET["iface"] ?? '');
    $range=trim($_GET["range"] ?? '24h');
    $allowed=array('1h','2h','6h','24h','48h','7d','30d','60d','90d');
    if(!in_array($range,$allowed)) $range='24h';
    $fmt=_iface_date_format($range);
    $ifEnc=urlencode($iface);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/interfaces/metrics/$ifEnc?range=$range"));
    if(!is_object($json) || empty($json->success) || !is_array($json->data) || empty($json->data)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $points=$json->data;
    $isAggregate=isset($points[0]->avg_rx);

    $labels=array();
    $rxAvg=array(); $txAvg=array();
    $rxMax=array(); $txMax=array();
    $rxTotal=array(); $txTotal=array();

    foreach($points as $p){
        $labels[]=date($fmt,$p->timestamp);
        if($isAggregate){
            $rxAvg[]=sprintf('%.1f',$p->avg_rx);
            $txAvg[]=sprintf('%.1f',$p->avg_tx);
            $rxMax[]=sprintf('%.0f',$p->max_rx);
            $txMax[]=sprintf('%.0f',$p->max_tx);
            $rxTotal[]=intval($p->total_rx);
            $txTotal[]=intval($p->total_tx);
        } else {
            $rxAvg[]=intval($p->rx_mib);
            $txAvg[]=intval($p->tx_mib);
        }
    }

    $labelsJS="['".implode("','",$labels)."']";
    $pr=$isAggregate?'2':'1';
    $eIface=htmlspecialchars(strtoupper($iface));
    $res=$isAggregate?($points[0]->period ?? $range):$range;

    $h=array();
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    // ── RX/TX Average bandwidth chart ──
    $uid1='if-bw-'.uniqid();
    $rxJS='['.implode(',',$rxAvg).']';
    $txJS='['.implode(',',$txAvg).']';

    $ds=array();
    if($isAggregate && !empty($rxMax)){
        $rxMaxJS='['.implode(',',$rxMax).']';
        $txMaxJS='['.implode(',',$txMax).']';
        $ds[]="{label:'RX (peak)',data:$rxMaxJS,borderColor:'rgba(54,162,235,0.3)',borderDash:[5,3],pointRadius:0,tension:0.3,fill:false,borderWidth:1.5}";
        $ds[]="{label:'TX (peak)',data:$txMaxJS,borderColor:'rgba(255,159,64,0.3)',borderDash:[5,3],pointRadius:0,tension:0.3,fill:false,borderWidth:1.5}";
    }
    $rxLabel=$isAggregate?'RX (avg MiB)':'RX (MiB)';
    $txLabel=$isAggregate?'TX (avg MiB)':'TX (MiB)';
    $ds[]="{label:'$rxLabel',data:$rxJS,borderColor:'rgb(54,162,235)',backgroundColor:'rgba(54,162,235,0.08)',pointRadius:$pr,tension:0.3,fill:true,borderWidth:2}";
    $ds[]="{label:'$txLabel',data:$txJS,borderColor:'rgb(255,159,64)',backgroundColor:'rgba(255,159,64,0.08)',pointRadius:$pr,tension:0.3,fill:true,borderWidth:2}";
    $dsJS='['.implode(',',$ds).']';

    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-network-wired'></i>&nbsp; $eIface — {bandwidth}</h5>";
    $h[]="<div class='ibox-tools'><span class='badge' style='background:#676a6c;color:#fff'>$res</span></div></div>";
    $h[]="<div class='ibox-content'><div style='position:relative;height:300px'><canvas id='$uid1'></canvas></div></div></div>";
    $h[]="<script>";
    $h[]="new Chart(document.getElementById('$uid1'),{type:'line',";
    $h[]="data:{labels:$labelsJS,datasets:$dsJS},";
    $h[]="options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
    $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:15}},";
    $h[]="tooltip:{callbacks:{label:function(c){return c.dataset.label+': '+c.parsed.y.toLocaleString()+' MiB';}}}},";
    $h[]="scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:24}},";
    $h[]="y:{beginAtZero:true,title:{display:true,text:'MiB'}}}}});";
    $h[]="</script>";

    // ── Total volume bar chart (aggregates only) ──
    if($isAggregate && !empty($rxTotal)){
        $uid2='if-vol-'.uniqid();
        $rxTotJS='['.implode(',',$rxTotal).']';
        $txTotJS='['.implode(',',$txTotal).']';

        $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-chart-bar'></i>&nbsp; $eIface — {total} {bandwidth}</h5></div>";
        $h[]="<div class='ibox-content'><div style='position:relative;height:260px'><canvas id='$uid2'></canvas></div></div></div>";
        $h[]="<script>";
        $h[]="new Chart(document.getElementById('$uid2'),{type:'bar',";
        $h[]="data:{labels:$labelsJS,datasets:[";
        $h[]="{label:'RX {total} (MiB)',data:$rxTotJS,backgroundColor:'rgba(54,162,235,0.7)',borderRadius:3},";
        $h[]="{label:'TX {total} (MiB)',data:$txTotJS,backgroundColor:'rgba(255,159,64,0.7)',borderRadius:3}";
        $h[]="]},";
        $h[]="options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
        $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:15}},";
        $h[]="tooltip:{callbacks:{label:function(c){return c.dataset.label+': '+c.parsed.y.toLocaleString()+' MiB';}}}},";
        $h[]="scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:24}},";
        $h[]="y:{beginAtZero:true,title:{display:true,text:'MiB'}}}}});";
        $h[]="</script>";
    }

    $h[]="<script>NoSpinner();</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}
function nf_conntrack_count(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    // Current values
    $latestJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/conntrack/latest"));
    $curCount='—'; $curMax='—'; $curPct='—'; $pctColor='#676a6c';
    if(is_object($latestJson) && !empty($latestJson->success) && is_object($latestJson->data)){
        $d=$latestJson->data;
        $curCount=number_format(intval($d->count));
        $curMax=number_format(intval($d->max));
        $pct=intval($d->percentage);
        $curPct="$pct%";
        if($pct<50) $pctColor='#1ab394';
        elseif($pct<80) $pctColor='#f8ac59';
        else $pctColor='#ed5565';
    }

    $h=array();
    // Summary widgets
    $h[]="<div class='row' style='margin:10px 0 15px'>";
    $h[]="<div class='col-md-4'><div style='background:#1c84c6;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>";
    $h[]="<div style='font-size:22px;font-weight:700'>$curCount</div>";
    $h[]="<div style='font-size:11px'><i class='fas fa-link'></i> {connections}</div></div></div>";
    $h[]="<div class='col-md-4'><div style='background:#34495e;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>";
    $h[]="<div style='font-size:22px;font-weight:700'>$curMax</div>";
    $h[]="<div style='font-size:11px'><i class='fas fa-database'></i> nf_conntrack_max</div></div></div>";
    $h[]="<div class='col-md-4'><div style='background:$pctColor;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>";
    $h[]="<div style='font-size:22px;font-weight:700'>$curPct</div>";
    $h[]="<div style='font-size:11px'><i class='fas fa-percentage'></i> {percentage}</div></div></div>";
    $h[]="</div>";

    // Range buttons
    $ranges=array("1h"=>"{last_hour}","6h"=>"6h","24h"=>"24h","7d"=>"7d","30d"=>"30d","90d"=>"90d");
    $h[]="<div style='margin-bottom:15px'>";
    $h[]="  <div class='btn-group' id='ct-range-btns'>";
    foreach($ranges as $val=>$label){
        $active=($val==='24h')?' active':'';
        $h[]="    <button class='btn btn-sm btn-default$active' onclick=\"CtRange('$val',this)\">$label</button>";
    }
    $h[]="  </div>";
    $h[]="  <button class='btn btn-sm btn-primary' style='margin-left:10px' onclick=\"CtRefresh();\">";
    $h[]="    <i class='fas fa-sync-alt'></i> {refresh}</button>";
    $h[]="</div>";

    // Chart area
    $h[]="<div id='ct-chart-area'></div>";

    // JS controller
    $h[]="<script>";
    $h[]="var _ctRange='24h';";
    $h[]="function CtRange(r,btn){";
    $h[]="  _ctRange=r;";
    $h[]="  \$('#ct-range-btns .btn').removeClass('active');";
    $h[]="  \$(btn).addClass('active');";
    $h[]="  CtRefresh();";
    $h[]="};";
    $h[]="function CtRefresh(){";
    $h[]="  LoadAjax('ct-chart-area','$page?ct-chart=yes&range='+_ctRange);";
    $h[]="};";
    $h[]="CtRefresh();";
    $h[]="</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

function _ct_date_format($range){
    $map=array('1h'=>'H:i','2h'=>'H:i','6h'=>'H:00','24h'=>'H:00','48h'=>'H:00','7d'=>'D d','30d'=>'M d','60d'=>'M d','90d'=>'M Y');
    return isset($map[$range]) ? $map[$range] : 'H:i';
}

function ct_chart_render(){
    $tpl=new template_admin();
    $range=trim($_GET["range"] ?? '24h');
    $allowed=array('1h','2h','6h','24h','48h','7d','30d','60d','90d');
    if(!in_array($range,$allowed)) $range='24h';
    $fmt=_ct_date_format($range);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/conntrack/metrics?range=$range"));
    if(!is_object($json) || empty($json->success) || !is_array($json->data) || empty($json->data)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $points=$json->data;
    $isAggregate=isset($points[0]->avg_count);

    $labels=array(); $counts=array(); $percents=array();
    $minCounts=array(); $maxCounts=array(); $maxPcts=array();

    foreach($points as $p){
        $labels[]=date($fmt,$p->timestamp);
        if($isAggregate){
            $counts[]=sprintf('%.0f',$p->avg_count);
            $percents[]=sprintf('%.1f',$p->avg_percent);
            $minCounts[]=sprintf('%.0f',$p->min_count);
            $maxCounts[]=sprintf('%.0f',$p->max_count);
            $maxPcts[]=sprintf('%.0f',$p->max_percent);
        } else {
            $counts[]=intval($p->count);
            $percents[]=intval($p->percentage);
        }
    }

    $labelsJS="['".implode("','",$labels)."']";
    $countsJS='['.implode(',',$counts).']';
    $percentsJS='['.implode(',',$percents).']';
    $pr=$isAggregate?'2':'1';

    $h=array();
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    // ── Connection count chart ──
    $uid1='ct-count-'.uniqid();
    $dsCnt=array();
    if($isAggregate && !empty($maxCounts)){
        $maxJS='['.implode(',',$maxCounts).']';
        $minJS='['.implode(',',$minCounts).']';
        $dsCnt[]="{label:'{connections} (peak)',data:$maxJS,borderColor:'rgba(231,76,60,0.4)',borderDash:[5,3],pointRadius:0,tension:0.3,fill:false,borderWidth:1.5}";
        $dsCnt[]="{label:'{connections} (min)',data:$minJS,borderColor:'rgba(39,174,96,0.4)',borderDash:[5,3],pointRadius:0,tension:0.3,fill:false,borderWidth:1.5}";
    }
    $avgLabel=$isAggregate?'{connections} (avg)':'{connections}';
    $dsCnt[]="{label:'$avgLabel',data:$countsJS,borderColor:'rgb(54,162,235)',backgroundColor:'rgba(54,162,235,0.08)',pointRadius:$pr,tension:0.3,fill:true,borderWidth:2}";
    $dsCntJS='['.implode(',',$dsCnt).']';

    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-link'></i>&nbsp; nf_conntrack_count</h5>";
    $res=$isAggregate?($points[0]->period ?? $range):$range;
    $h[]="<div class='ibox-tools'><span class='badge' style='background:#676a6c;color:#fff'>$res</span></div></div>";
    $h[]="<div class='ibox-content'><div style='position:relative;height:300px'><canvas id='$uid1'></canvas></div></div></div>";
    $h[]="<script>";
    $h[]="new Chart(document.getElementById('$uid1'),{type:'line',";
    $h[]="data:{labels:$labelsJS,datasets:$dsCntJS},";
    $h[]="options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
    $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:15}},";
    $h[]="tooltip:{callbacks:{label:function(c){return c.dataset.label+': '+c.parsed.y.toLocaleString();}}}},";
    $h[]="scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:24}},";
    $h[]="y:{beginAtZero:true,title:{display:true,text:'{connections}'}}}}});";
    $h[]="</script>";

    // ── Usage percentage chart ──
    $uid2='ct-pct-'.uniqid();
    $dsPct=array();
    if($isAggregate && !empty($maxPcts)){
        $maxPctJS='['.implode(',',$maxPcts).']';
        $dsPct[]="{label:'{percentage} (peak)',data:$maxPctJS,borderColor:'rgba(231,76,60,0.4)',borderDash:[5,3],pointRadius:0,tension:0.3,fill:false,borderWidth:1.5}";
    }
    $pctLabel=$isAggregate?'{percentage} (avg)':'{percentage}';
    $dsPct[]="{label:'$pctLabel',data:$percentsJS,borderColor:'rgb(255,99,132)',backgroundColor:'rgba(255,99,132,0.08)',pointRadius:$pr,tension:0.3,fill:true,borderWidth:2}";
    $dsPctJS='['.implode(',',$dsPct).']';

    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-percentage'></i>&nbsp; {percentage} nf_conntrack</h5></div>";
    $h[]="<div class='ibox-content'><div style='position:relative;height:250px'><canvas id='$uid2'></canvas></div></div></div>";
    $h[]="<script>";
    $h[]="new Chart(document.getElementById('$uid2'),{type:'line',";
    $h[]="data:{labels:$labelsJS,datasets:$dsPctJS},";
    $h[]="options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
    $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:15}}},";
    $h[]="scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:24}},";
    $h[]="y:{beginAtZero:true,max:100,title:{display:true,text:'%'}}}}});";
    $h[]="</script>";

    $h[]="<script>NoSpinner();</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

function tabs():bool{
    $img=$_GET["tabs"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    if($img=="nf_conntrack_count"){
        nf_conntrack_count();
        return true;
    }


    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
    $flat="";
    if(isset($_GET["flat"])){$flat="&flat=yes";}


    if($img=="load"){
        $array["{load}"]="$page?popup-explain-load=yes";
    }

    $array["{this_hour}"]="$page?popup=$img&period=hourly$flat";
    $array["{today}"]="$page?popup=$img&period=day$flat";
    $array["{yesterday}"]="$page?popup=$img&period=yesterday$flat";
    $array["{this_week}"]="$page?popup=$img&period=week$flat";
    $array["{this_month}"]="$page?popup=$img&period=month$flat";
    $array["{this_year}"]="$page?popup=$img&period=year$flat";
    if($DisablePostGres==0) {
        if ($img == "squidlatency") {
            $array["{data}"] = "$page?popup-latency=yes";
        }
    }

    echo $tpl->tabs_default($array);
    return true;
}
function popup_load(){
    $tpl=new template_admin();
    $div=$tpl->div_explain("{load_avg}||<p style='font-size:16px;line-height: 28pt;padding:20px'>{sysloadexplain}</p>");
    $html[]="<div style='margin-top:48px;padding-left: 91px;padding-right: 91px;'>";
    $html[]="$div";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
}

function popup_start():bool{
    $page=CurrentPageName();
    $img=$_GET["popup"];
    $period=$_GET["period"];
    $flat="";
    if(isset($_GET["flat"])){$flat="&flat=yes";}
    echo "<div id='progress-rrd-task'></div><div id='popup-rrd-load'></div><script>LoadAjax('popup-rrd-load','$page?popup2=$img&period=$period$flat')</script>";
    return true;
}
function popup(){
    $t=time();
	$tpl=new template_admin();
	$page=CurrentPageName();
    $sock=new sockets();
    $img=$_GET["popup2"];
    $period=$_GET["period"];
    $flat="";$flat2="";
    if(isset($_GET["flat"])){$flat=".flat";$flat2="&flat=yes";}
    $path=dirname(__FILE__)."/img/squid/$img-$period$flat.png";

    if($img=="squidlatency"){
        if(!is_file($path)){
            $sock->REST_API("/proxy/graphs/latency");
        }
    }

    $js = $tpl->framework_buildjs("/rrd/allimages", "progress-rrd.compile",
        "progress-rrd.log","progress-rrd-task","LoadAjax('popup-rrd-load','$page?popup2=yes$img&period=$period$flat2');");


    $gengraphs=$tpl->button_autnonome("{run_this_task_now}",$js,ico_run,null,450,"btn-danger");

    if(!is_file($path)){
        echo $tpl->div_error("{error_graphic_is_not_generated}<br><strong>img/squid/$img-$period.png {no_such_file}</strong><div style='margin: 30px;text-align: right'>$gengraphs</div>");
        return false;
    }
    $title=$GLOBALS["MAIN_TITLE"][$img];
    $html="
<H2>$title</H2>
<center style='margin:20px'><img src='/img/squid/$img-$period$flat.png?t=$t'></center>";
echo $tpl->_ENGINE_parse_body($html);
return true;
}

function popup_latency(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $TRCLASS=null;
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{sitename}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{latency}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $q=new postgres_sql();
    $results=$q->QUERY_SQL("SELECT sitename,AVG(latency) as latency FROM statscom_latency GROUP BY sitename ORDER BY latency DESC LIMIT 100");
    $class_text="";
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }

    while ($ligne = pg_fetch_assoc($results)) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $site=$ligne["sitename"];
        $latency=$ligne["latency"];
        $latency=round($latency,2);
        $latency=msToString($latency);


        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:99%' nowrap><span class='$class_text'>$site</span></td>";
        $html[]="<td style='width:1%' class='right' nowrap>$latency</center></td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";



    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body($html);

}
function secondsToMinutes($seconds) {
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    return [$minutes, $remainingSeconds];
}

function msToString($ms){
    $unit="ms";
    if($ms>0){
        $unit="s";
        $ms=$ms /1000;
    }


    if($ms>60){

        list($minutes, $remainingSeconds) = secondsToMinutes($ms);
        if($remainingSeconds>0) {
            $unit="";
            $ms = "{$minutes}mn,{$remainingSeconds}s";
        }else{
            $ms=$minutes;
            $unit="mn";
        }
    }
    return "$ms{$unit}";
}