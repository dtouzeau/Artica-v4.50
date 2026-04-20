<?php
/**
 * HaCluster Backend Metrics Dashboard
 * Visualises per-backend load-balancing distribution from HaClusterServerStats BoltDB data.
 *
 * Charts:
 *   Overview  — Hits doughnut, Status-codes stacked bar, Ranking table, Health table
 *   Detail    — Response-time line, Bandwidth line, Connections bar (per-backend selector)
 *   Live mode — Polls /latest every 30s and appends data points to detail charts
 *
 * API endpoints (all GET, {success,data} envelope):
 *   /hacluster/connstats/backends?range=X          — aggregated stats per backend
 *   /hacluster/connstats/backends/ranking?range=X   — ranked by hits/response_time/bytes_read
 *   /hacluster/connstats/backends/health?range=X    — with error_rate, retry_rate
 *   /hacluster/connstats/backends/{name}?range=X    — time-series windows for one backend
 *   /hacluster/connstats/latest                     — current in-memory window (live mode)
 *   /hacluster/connstats/status                     — engine diagnostics
 */

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors',1);ini_set('error_reporting',E_ALL);}

$users=new usersMenus();
if(!$users->AsProxyMonitor){exit();}

// ── Dispatcher ──────────────────────────────────────────────────
if(isset($_GET["main-view"]))       {main_view();       exit;}
if(isset($_GET["overview-charts"])) {overview_charts();  exit;}
if(isset($_GET["detail-charts"]))   {detail_charts();    exit;}
if(isset($_GET["live-poll"]))       {live_poll();        exit;}
page();

// ── Helpers ─────────────────────────────────────────────────────

function _valid_range(string $r):string{
    $ok=["1h","6h","24h","48h","7d","30d"];
    return in_array($r,$ok)?$r:"1h";
}

function _range_date_fmt(string $range):string{
    switch($range){
        case "7d": case "30d": return 'M d H:00';
        case "48h": return 'M d H:i';
        default: return 'H:i';
    }
}

/** Controlled-precision float array for JS. Avoids PHP serialize_precision 50-digit bug. */
function _fmt_floats(array $a,int $d=2):string{
    return '['.implode(',',array_map(fn($v)=>sprintf("%.".$d."f",$v),$a)).']';
}

function _fmt_ints(array $a):string{
    return '['.implode(',',array_map('intval',$a)).']';
}

/** Format bytes to human-readable (input is bytes, not KB). */
function _fmt_bytes(int $bytes):string{
    if($bytes>=1073741824) return sprintf('%.1f GB',$bytes/1073741824);
    if($bytes>=1048576) return sprintf('%.1f MB',$bytes/1048576);
    if($bytes>=1024) return sprintf('%.1f KB',$bytes/1024);
    return "$bytes B";
}

/** Reusable KPI widget (colored card). */
function _kpi(string $bg,string $ico,string $label,string $val):string{
    $val=htmlspecialchars($val);
    return "<div class='col-md-3 col-sm-6'><div style='background:$bg;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>"
        ."<div style='font-size:24px;font-weight:700'>$val</div>"
        ."<div style='font-size:11px'><i class='$ico'></i> $label</div></div></div>";
}

/** Reusable ibox panel with canvas and inline Chart.js. */
function _chart_panel(string $icon,string $title,string $uid,string $chartJs,string $badge=''):string{
    $h=[];
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='$icon'></i>&nbsp; $title</h5>";
    if($badge){
        $h[]="<div class='ibox-tools'><span class='badge' style='background:#676a6c;color:#fff'>$badge</span></div>";
    }
    $h[]="</div><div class='ibox-content'>";
    $h[]="<div style='position:relative;height:300px'><canvas id='$uid'></canvas></div>";
    $h[]="</div></div>";
    $h[]="<script>$chartJs</script>";
    return implode("\n",$h);
}

// ── Page entry point ────────────────────────────────────────────

function page():void{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{backends} {statistics}",
        "fas fa-chart-bar",
        "{hacluster_backends_metrics_explain}",
        "$page?main-view=yes&range=1h");
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Main view: range buttons + KPI + containers ─────────────────

function main_view():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=_valid_range($_GET["range"]??"1h");

    // Fetch aggregated backends for KPI widgets
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/connstats/backends?range=$range"));
    $totalHits=0;$avgRt=0;$totalBw=0;$backendCount=0;
    if(is_object($json)&&!empty($json->success)&&is_object($json->data)){
        $backends=$json->data->backends??[];
        $backendCount=count($backends);
        foreach($backends as $b){
            $totalHits+=intval($b->hits??0);
            $totalBw+=intval($b->total_bytes_read??0)+intval($b->total_bytes_uploaded??0);
        }
        if($backendCount>0){
            $sumRt=0;
            foreach($backends as $b){$sumRt+=floatval($b->avg_response_time_ms??0);}
            $avgRt=round($sumRt/$backendCount/1000,2);
        }
    }

    $rtColor='#1ab394';
    if($avgRt>10.0) $rtColor='#f8ac59';
    if($avgRt>30.0) $rtColor='#ed5565';

    $h=[];

    // Range buttons
    $ranges=["1h"=>"{last_hour}","6h"=>"6h","24h"=>"{last_24_hours}","48h"=>"48h","7d"=>"{week}","30d"=>"{month}"];
    $h[]="<div style='margin:10px 0 15px'>";
    $h[]="  <div class='btn-group' id='bcm-range-btns'>";
    foreach($ranges as $key=>$label){
        $active=($key===$range)?' active':'';
        $h[]="    <button class='btn btn-sm btn-default$active' onclick=\"BcmRange('$key',this)\">$label</button>";
    }
    $h[]="  </div>";
    $h[]="  <button class='btn btn-sm btn-primary' style='margin-left:10px' onclick=\"BcmRefresh()\">";
    $h[]="    <i class='fas fa-sync-alt'></i> {refresh}</button>";
    $h[]="  <button id='bcm-live-btn' class='btn btn-sm btn-default' style='margin-left:5px' onclick=\"BcmToggleLive()\">";
    $h[]="    <i class='fas fa-broadcast-tower'></i> Live</button>";
    $h[]="  <span id='bcm-live-indicator' style='display:none;margin-left:8px;color:#1ab394;font-size:12px'>";
    $h[]="    <i class='fas fa-circle' style='animation:blink 1s infinite'></i> {live}</span>";
    $h[]="</div>";

    // KPI widgets
    $h[]="<div class='row' style='margin-bottom:15px'>";
    $h[]=_kpi('#1c84c6','fas fa-exchange-alt','{total} {hits}',number_format($totalHits));
    $h[]=_kpi($rtColor,'fas fa-tachometer-alt','{avg_response_time}',sprintf('%.2f s',$avgRt));
    $h[]=_kpi('#34495e','fas fa-download','{bandwidth}',_fmt_bytes($totalBw));
    $h[]=_kpi('#ab7df6','fas fa-server','{backends}',(string)$backendCount);
    $h[]="</div>";

    // Chart containers
    $h[]="<div id='bcm-overview'></div>";
    $h[]="<div id='bcm-detail'></div>";

    // Navigation JS + live mode logic
    $h[]="<style>@keyframes blink{0%,100%{opacity:1}50%{opacity:0.3}}</style>";
    $h[]="<script>";
    $h[]="var _bcmRange='$range',_bcmLive=false,_bcmLiveTimer=null,_bcmLiveOverviewTimer=null;";
    $h[]="function BcmRange(r,btn){";
    $h[]="  _bcmRange=r;";
    $h[]="  \$('#bcm-range-btns .btn').removeClass('active');";
    $h[]="  \$(btn).addClass('active');";
    $h[]="  if(_bcmLive){BcmStopLive();}";
    $h[]="  BcmRefresh();";
    $h[]="};";
    $h[]="function BcmRefresh(){";
    $h[]="  LoadAjax('bcm-overview','$page?overview-charts=yes&range='+_bcmRange);";
    $h[]="  LoadAjax('bcm-detail','$page?detail-charts=yes&range='+_bcmRange);";
    $h[]="};";

    // Live mode toggle
    $h[]="function BcmToggleLive(){";
    $h[]="  if(_bcmLive){BcmStopLive();return;}";
    $h[]="  _bcmLive=true;";
    $h[]="  \$('#bcm-live-btn').removeClass('btn-default').addClass('btn-danger');";
    $h[]="  \$('#bcm-live-indicator').show();";
    // Seed detail charts with 1h history, then start polling
    $h[]="  _bcmRange='1h';";
    $h[]="  \$('#bcm-range-btns .btn').removeClass('active');";
    $h[]="  \$('#bcm-range-btns .btn').first().addClass('active');";
    $h[]="  LoadAjax('bcm-overview','$page?overview-charts=yes&range=1h');";
    $h[]="  LoadAjax('bcm-detail','$page?detail-charts=yes&range=1h&live=1');";
    $h[]="  _bcmLiveOverviewTimer=setInterval(function(){";
    $h[]="    LoadAjaxSilent('bcm-overview','$page?overview-charts=yes&range=1h');";
    $h[]="  },60000);";
    $h[]="};";
    $h[]="function BcmStopLive(){";
    $h[]="  _bcmLive=false;";
    $h[]="  \$('#bcm-live-btn').removeClass('btn-danger').addClass('btn-default');";
    $h[]="  \$('#bcm-live-indicator').hide();";
    $h[]="  if(_bcmLiveTimer){clearInterval(_bcmLiveTimer);_bcmLiveTimer=null;}";
    $h[]="  if(_bcmLiveOverviewTimer){clearInterval(_bcmLiveOverviewTimer);_bcmLiveOverviewTimer=null;}";
    $h[]="};";

    $h[]="BcmRefresh();";
    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Overview charts: distribution across all backends ────────────

function overview_charts():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=_valid_range($_GET["range"]??"1h");
    $t=time();

    // Fetch all three endpoints in sequence (PHP is single-threaded)
    $backendsJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/connstats/backends?range=$range"));
    $rankingJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/connstats/backends/ranking?range=$range&sort_by=hits&limit=20"));
    $healthJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/connstats/backends/health?range=$range"));

    $h=[];
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    // ── Hits Distribution Doughnut ──────────────────────────────
    if(is_object($backendsJson)&&!empty($backendsJson->success)&&is_object($backendsJson->data)){
        $backends=$backendsJson->data->backends??[];
        $bucket=$backendsJson->data->bucket??"";
        if(!empty($backends)){
            $names=[];$hits=[];
            $colors=['#1c84c6','#1ab394','#f8ac59','#ed5565','#ab7df6','#23c6c8','#34495e','#e74c3c','#2ecc71','#3498db',
                     '#9b59b6','#e67e22','#1abc9c','#c0392b','#2980b9','#8e44ad'];
            foreach($backends as $b){
                $names[]=$b->server_name??"?";
                $hits[]=intval($b->hits??0);
            }
            $namesJs=json_encode($names);
            $hitsJs=_fmt_ints($hits);
            $colorsJs=json_encode(array_slice($colors,0,count($names)));
            $uid="hits-doughnut-$t";

            $js="new Chart(document.getElementById('$uid'),{type:'doughnut',";
            $js.="data:{labels:$namesJs,datasets:[{data:$hitsJs,backgroundColor:$colorsJs,borderWidth:2}]},";
            $js.="options:{responsive:true,maintainAspectRatio:false,";
            $js.="plugins:{legend:{position:'right',labels:{usePointStyle:true,padding:12,font:{size:11}}}}}});";

            $h[]="<div class='row'><div class='col-md-6'>";
            $h[]=_chart_panel('fas fa-chart-pie','{hits} {distribution}',$uid,$js,$bucket);
            $h[]="</div>";

            // ── Status Codes Stacked Bar ────────────────────────
            $uid2="status-codes-$t";
            $allCodes=[];
            foreach($backends as $b){
                $codes=(array)($b->status_codes??[]);
                foreach($codes as $code=>$cnt){
                    if(!isset($allCodes[$code])){$allCodes[$code]=0;}
                }
            }
            ksort($allCodes);
            $codeKeys=array_keys($allCodes);

            if(!empty($codeKeys)){
                $statusColors=[
                    '2'=>'rgba(26,179,148,0.85)','3'=>'rgba(35,198,200,0.85)',
                    '4'=>'rgba(248,172,89,0.85)','5'=>'rgba(237,85,101,0.85)','0'=>'rgba(103,106,108,0.6)'
                ];
                $datasets=[];
                foreach($codeKeys as $code){
                    $vals=[];
                    foreach($backends as $b){
                        $codes=(array)($b->status_codes??[]);
                        $vals[]=intval($codes[$code]??0);
                    }
                    $prefix=substr((string)$code,0,1);
                    $color=$statusColors[$prefix]??'rgba(103,106,108,0.5)';
                    $valsJs=_fmt_ints($vals);
                    $datasets[]="{label:'HTTP $code',data:$valsJs,backgroundColor:'$color'}";
                }
                $dsJs='['.implode(',',$datasets).']';

                $js2="new Chart(document.getElementById('$uid2'),{type:'bar',";
                $js2.="data:{labels:$namesJs,datasets:$dsJs},";
                $js2.="options:{responsive:true,maintainAspectRatio:false,";
                $js2.="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:10,font:{size:10}}}},";
                $js2.="scales:{x:{stacked:true,ticks:{maxRotation:45}},y:{stacked:true,beginAtZero:true,title:{display:true,text:'{requests}'}}}}});";

                $h[]="<div class='col-md-6'>";
                $h[]=_chart_panel('fas fa-list-ol','HTTP {status_codes}',$uid2,$js2,$bucket);
                $h[]="</div>";
            }
            $h[]="</div>";
        }
    }

    // ── Backend Ranking Table ────────────────────────────────────
    if(is_object($rankingJson)&&!empty($rankingJson->success)&&is_object($rankingJson->data)){
        $ranking=$rankingJson->data->ranking??[];
        if(!empty($ranking)){
            $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-trophy'></i>&nbsp; {backends} {ranking}</h5></div>";
            $h[]="<div class='ibox-content'>";
            $h[]="<table id='rank-tbl-$t' class='footable table table-stripped' data-page-size='20'>";
            $h[]="<thead><tr>";
            $h[]="<th data-sortable=true data-type='text'>#</th>";
            $h[]="<th data-sortable=true data-type='text'>{backend}</th>";
            $h[]="<th data-sortable=true data-type='number'>{hits}</th>";
            $h[]="<th data-sortable=true data-type='number'>{avg_response_time} (s)</th>";
            $h[]="<th data-sortable=true data-type='number'>Min (s)</th>";
            $h[]="<th data-sortable=true data-type='number'>Max (s)</th>";
            $h[]="<th data-sortable=true data-type='number'>{bandwidth}</th>";
            $h[]="<th data-sortable=true data-type='number'>{retries}</th>";
            $h[]="</tr></thead><tbody>";

            $TRCLASS=null;$rank=0;
            foreach($ranking as $b){
                $rank++;
                if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
                $name=htmlspecialchars($b->server_name??"?");
                $ip=htmlspecialchars($b->server_ip??"");
                $port=intval($b->server_port??0);
                $hits=number_format(intval($b->hits??0));
                $rt=sprintf('%.2f',floatval($b->avg_response_time_ms??0)/1000);
                $minRt=sprintf('%.2f',intval($b->min_response_time_ms??0)/1000);
                $maxRt=sprintf('%.2f',intval($b->max_response_time_ms??0)/1000);
                $bw=_fmt_bytes(intval($b->total_bytes_read??0)+intval($b->total_bytes_uploaded??0));
                $retries=intval($b->total_retries??0);

                $h[]="<tr class='$TRCLASS'>";
                $h[]="<td>$rank</td>";
                $h[]="<td><strong>$name</strong><br><span style='font-size:11px;color:#999'>$ip:$port</span></td>";
                $h[]="<td>$hits</td>";
                $h[]="<td>$rt</td>";
                $h[]="<td>$minRt</td>";
                $h[]="<td>$maxRt</td>";
                $h[]="<td>$bw</td>";
                $h[]="<td>$retries</td>";
                $h[]="</tr>";
            }

            $h[]="</tbody><tfoot><tr><td colspan='8'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
            $h[]="</table></div></div>";
        }
    }

    // ── Health Overview Table ────────────────────────────────────
    if(is_object($healthJson)&&!empty($healthJson->success)&&is_object($healthJson->data)){
        $healthBackends=$healthJson->data->backends??[];
        if(!empty($healthBackends)){
            $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-heartbeat'></i>&nbsp; {backends} {health} <small>{avg_response_time_tcp}</small></h5></div>";
            $h[]="<div class='ibox-content'>";
            $h[]="<table id='health-tbl-$t' class='footable table table-stripped' data-page-size='20'>";
            $h[]="<thead><tr>";
            $h[]="<th data-sortable=true data-type='text'>{backend}</th>";
            $h[]="<th data-sortable=true data-type='number'>{hits}</th>";
            $h[]="<th data-sortable=true data-type='number'>{avg_response_time} (s)</th>";
            $h[]="<th data-sortable=true data-type='number'>{error_rate}</th>";
            $h[]="<th data-sortable=true data-type='number'>{retry_rate}</th>";
            $h[]="<th data-sortable=true data-type='number'>{active_connections}</th>";
            $h[]="<th>{status}</th>";
            $h[]="</tr></thead><tbody>";

            $TRCLASS=null;
            foreach($healthBackends as $b){
                if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
                $name=htmlspecialchars($b->server_name??"?");
                $ip=htmlspecialchars($b->server_ip??"");
                $port=intval($b->server_port??0);
                $hits=number_format(intval($b->hits??0));
                $rt=sprintf('%.2f',floatval($b->avg_response_time_ms??0)/1000);
                $errRate=floatval($b->error_rate??0);
                $retryRate=floatval($b->retry_rate??0);
                $activeConns=intval($b->active_conns??0);

                // Color-coded error rate
                $errPct=sprintf('%.2f%%',$errRate*100);
                if($errRate>0.1) $errBadge="<span class='label' style='background:#ed5565;color:#fff'>$errPct</span>";
                elseif($errRate>0.01) $errBadge="<span class='label' style='background:#f8ac59;color:#fff'>$errPct</span>";
                else $errBadge="<span class='label' style='background:#1ab394;color:#fff'>$errPct</span>";

                // Color-coded retry rate
                $retryPct=sprintf('%.2f%%',$retryRate*100);
                if($retryRate>0.05) $retryBadge="<span class='label' style='background:#ed5565;color:#fff'>$retryPct</span>";
                elseif($retryRate>0.01) $retryBadge="<span class='label' style='background:#f8ac59;color:#fff'>$retryPct</span>";
                else $retryBadge="<span class='label' style='background:#1ab394;color:#fff'>$retryPct</span>";

                // Health status (thresholds in seconds — Tc includes SSL handshake)
                $status="{healthy}";$statusCls="background:#1ab394;color:#fff";
                $rtF=floatval($rt);
                if($errRate>0.1||$rtF>30.0){$status="{critical}";$statusCls="background:#ed5565;color:#fff";}
                elseif($errRate>0.01||$rtF>10.0){$status="{warning}";$statusCls="background:#f8ac59;color:#fff";}

                $h[]="<tr class='$TRCLASS'>";
                $h[]="<td><strong>$name</strong><br><span style='font-size:11px;color:#999'>$ip:$port</span></td>";
                $h[]="<td>$hits</td>";
                $h[]="<td>$rt</td>";
                $h[]="<td>$errBadge</td>";
                $h[]="<td>$retryBadge</td>";
                $h[]="<td>$activeConns</td>";
                $h[]="<td><span class='label' style='$statusCls'>$status</span></td>";
                $h[]="</tr>";
            }

            $h[]="</tbody><tfoot><tr><td colspan='7'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
            $h[]="</table></div></div>";
        }
    }

    if(count($h)<=1){
        $h[]=$tpl->div_info("{no_data}");
    }

    // FooTable init
    $h[]="<script>NoSpinner();";
    $h[]=implode("\n",$tpl->ICON_SCRIPTS);
    $h[]="$(document).ready(function(){";
    $h[]="  $('#rank-tbl-$t').footable({'sorting':{'enabled':true},'paging':{'size':20}});";
    $h[]="  $('#health-tbl-$t').footable({'sorting':{'enabled':true},'paging':{'size':20}});";
    $h[]="});</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Detail charts: per-backend time-series ──────────────────────

function detail_charts():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=_valid_range($_GET["range"]??"1h");
    $fmt=_range_date_fmt($range);
    $liveMode=intval($_GET["live"]??0);
    $sel_backend=$_GET["backend"]??"";
    $t=time();

    // Fetch backend list from aggregated endpoint
    $backendsJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/connstats/backends?range=$range"));
    $backendNames=[];
    if(is_object($backendsJson)&&!empty($backendsJson->success)){
        foreach(($backendsJson->data->backends??[]) as $b){
            $n=$b->server_name??"";
            if(strlen($n)>0){$backendNames[]=$n;}
        }
    }

    if(empty($backendNames)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data} — {backends} metrics not available yet."));
        return;
    }

    sort($backendNames);
    if(empty($sel_backend)||!in_array($sel_backend,$backendNames)){
        $sel_backend=$backendNames[0];
    }

    $h=[];

    // ── Backend selector ────────────────────────────────────────
    $h[]="<div style='padding:8px 12px;margin:15px 0 12px;background:#f9f9f9;border:1px solid #e7eaec;border-radius:3px'>";
    $h[]="  <div class='row'>";
    $h[]="    <div class='col-md-4'>";
    $h[]="      <label style='font-size:11px;color:#999;text-transform:uppercase;margin-bottom:3px'>";
    $h[]="        <i class='fas fa-server'></i> {select} {backend}</label>";
    $h[]="      <select id='bcm-backend-sel' class='form-control' onchange=\"BcmBackendChange()\">";
    foreach($backendNames as $name){
        $esc=htmlspecialchars($name);
        $selected=($name===$sel_backend)?"selected":"";
        $h[]="        <option value='$esc' $selected>$esc</option>";
    }
    $h[]="      </select>";
    $h[]="    </div>";
    $h[]="    <div class='col-md-8' style='padding-top:22px'>";
    $h[]="      <span style='color:#999;font-size:12px'>".htmlspecialchars($sel_backend)."</span>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="</div>";

    // ── Fetch time-series for selected backend ──────────────────
    $backendEncoded=urlencode($sel_backend);
    $tsJson=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/hacluster/connstats/backends/$backendEncoded?range=$range"
    ));

    $labels=[];$avgRt=[];$minRt=[];$maxRt=[];
    $bytesRead=[];$bytesUploaded=[];
    $activeConns=[];$serverConns=[];$frontendConns=[];$backendConns=[];
    $queueTime=[];
    $hasData=false;
    $bucket="";

    if(is_object($tsJson)&&!empty($tsJson->success)&&is_object($tsJson->data)){
        $bucket=$tsJson->data->bucket??"";
        $windows=$tsJson->data->windows??[];
        if(!empty($windows)){
            $hasData=true;
            foreach($windows as $w){
                $ts=intval($w->timestamp??0);
                $labels[]=($ts>0)?date($fmt,$ts):"";
                $b=$w->backends[0]??null;
                if(is_object($b)){
                    $avgRt[]=round(floatval($b->avg_response_time_ms??0)/1000,2);
                    $minRt[]=round(intval($b->min_response_time_ms??0)/1000,2);
                    $maxRt[]=round(intval($b->max_response_time_ms??0)/1000,2);
                    $bytesRead[]=intval($b->total_bytes_read??0);
                    $bytesUploaded[]=intval($b->total_bytes_uploaded??0);
                    $activeConns[]=intval($b->active_conns??0);
                    $serverConns[]=intval($b->server_conns??0);
                    $frontendConns[]=intval($b->frontend_conns??0);
                    $backendConns[]=intval($b->backend_conns??0);
                    $queueTime[]=round(floatval($b->avg_queue_time_ms??0)/1000,2);
                }else{
                    $avgRt[]=0;$minRt[]=0;$maxRt[]=0;
                    $bytesRead[]=0;$bytesUploaded[]=0;
                    $activeConns[]=0;$serverConns[]=0;$frontendConns[]=0;$backendConns[]=0;
                    $queueTime[]=0;
                }
            }
        }
    }

    if(!$hasData){
        $h[]="<div class='alert alert-info' style='margin-top:15px'>";
        $h[]="  <i class='fas fa-info-circle'></i>&nbsp;&nbsp;{no_data}";
        $h[]="</div>";
        $h[]=_detail_nav_js($page,$range,$liveMode);
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    $labelsJs=json_encode($labels);
    $avgRtJs=_fmt_floats($avgRt);
    $minRtJs=_fmt_floats($minRt);
    $maxRtJs=_fmt_floats($maxRt);
    $queueTimeJs=_fmt_floats($queueTime);

    // Auto-scale bandwidth
    $allBw=array_merge($bytesRead,$bytesUploaded);
    $maxBw=!empty($allBw)?max($allBw):0;
    $bwUnit="B";$bwDiv=1;
    if($maxBw>=1073741824){$bwUnit="GB";$bwDiv=1073741824;}
    elseif($maxBw>=1048576){$bwUnit="MB";$bwDiv=1048576;}
    elseif($maxBw>=1024){$bwUnit="KB";$bwDiv=1024;}
    $bytesReadScaled=array_map(fn($v)=>round($v/$bwDiv,2),$bytesRead);
    $bytesUploadedScaled=array_map(fn($v)=>round($v/$bwDiv,2),$bytesUploaded);
    $brJs=_fmt_floats($bytesReadScaled);
    $buJs=_fmt_floats($bytesUploadedScaled);

    $activeConnsJs=_fmt_ints($activeConns);
    $serverConnsJs=_fmt_ints($serverConns);
    $frontendConnsJs=_fmt_ints($frontendConns);
    $backendConnsJs=_fmt_ints($backendConns);

    // ── Common chart options + bandwidth divisor for live mode ──
    $h[]="<script>";
    $h[]="var _bcmBwDiv=$bwDiv;";
    $h[]="function _bcmOpts(yLabel){return{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
    $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:12}}},";
    $h[]="scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:24}},";
    $h[]="y:{beginAtZero:true,title:{display:true,text:yLabel}}}};}";
    $h[]="</script>";

    // ── Response Time Chart ─────────────────────────────────────
    $uid="rt-line-$t";
    $rtJs="var _bcmRtChart=new Chart(document.getElementById('$uid'),{type:'line',";
    $rtJs.="data:{labels:$labelsJs,datasets:[";
    $rtJs.="{label:'Max (s)',data:$maxRtJs,borderColor:'rgba(231,76,60,0.4)',borderDash:[5,3],pointRadius:0,tension:0.3,fill:false,borderWidth:1.5},";
    $rtJs.="{label:'Avg (s)',data:$avgRtJs,borderColor:'rgb(54,162,235)',backgroundColor:'rgba(54,162,235,0.08)',pointRadius:1,tension:0.3,fill:true,borderWidth:2},";
    $rtJs.="{label:'Min (s)',data:$minRtJs,borderColor:'rgba(39,174,96,0.4)',borderDash:[5,3],pointRadius:0,tension:0.3,fill:false,borderWidth:1.5},";
    $rtJs.="{label:'{queue_time} (s)',data:$queueTimeJs,borderColor:'rgba(248,172,89,0.6)',pointRadius:0,tension:0.3,fill:false,borderWidth:1.5}";
    $rtJs.="]},options:_bcmOpts('s')});";

    $h[]=_chart_panel('fas fa-tachometer-alt','{response_time}',$uid,$rtJs,$bucket);

    // ── Bandwidth Chart ─────────────────────────────────────────
    $uid2="bw-line-$t";
    $server=$tpl->javascript_parse_text("{server}");
    $download=$tpl->javascript_parse_text("{downloads_edk_section}");
    $upload=$tpl->javascript_parse_text("{uploads}");
    $bwJs="var _bcmBwChart=new Chart(document.getElementById('$uid2'),{type:'line',";
    $bwJs.="data:{labels:$labelsJs,datasets:[";
    $bwJs.="{label:'$download ($bwUnit)',data:$brJs,borderColor:'rgb(75,192,192)',backgroundColor:'rgba(75,192,192,0.08)',pointRadius:1,tension:0.3,fill:true,borderWidth:2},";
    $bwJs.="{label:'$upload ($bwUnit)',data:$buJs,borderColor:'rgb(255,159,64)',backgroundColor:'rgba(255,159,64,0.08)',pointRadius:1,tension:0.3,fill:true,borderWidth:2}";
    $bwJs.="]},options:_bcmOpts('$bwUnit')});";

    $h[]=_chart_panel('fas fa-download','{bandwidth}',$uid2,$bwJs,$bucket);

    // ── Connections Chart ────────────────────────────────────────
    $uid3="conn-bar-$t";
    $active_connections=$tpl->javascript_parse_text("{active_connections}");
    $connections=$tpl->javascript_parse_text("{connections}");
    $connJs="var _bcmConnChart=new Chart(document.getElementById('$uid3'),{type:'bar',";
    $connJs.="data:{labels:$labelsJs,datasets:[";
    $connJs.="{label:'$active_connections',data:$activeConnsJs,backgroundColor:'rgba(28,132,198,0.7)',borderWidth:0},";
    $connJs.="{label:'$server conns',data:$serverConnsJs,backgroundColor:'rgba(26,179,148,0.7)',borderWidth:0},";
    $connJs.="{label:'Frontend conns',data:$frontendConnsJs,backgroundColor:'rgba(171,125,246,0.7)',borderWidth:0},";
    $connJs.="{label:'Backend conns',data:$backendConnsJs,backgroundColor:'rgba(248,172,89,0.7)',borderWidth:0}";
    $connJs.="]},options:_bcmOpts('$connections')});";

    $h[]=_chart_panel('fas fa-plug',$connections,$uid3,$connJs,$bucket);

    // ── Navigation JS + Live polling ────────────────────────────
    $h[]=_detail_nav_js($page,$range,$liveMode);

    $h[]="<script>NoSpinner();</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Detail section navigation JS (backend selector + live polling) ──

function _detail_nav_js(string $page,string $range,int $liveMode):string{
    $h=[];
    $h[]="<script>";

    // Backend selector change handler
    $h[]="function BcmBackendChange(){";
    $h[]="  var b=document.getElementById('bcm-backend-sel').value;";
    $h[]="  var live=(typeof _bcmLive!=='undefined'&&_bcmLive)?'&live=1':'';";
    $h[]="  LoadAjax('bcm-detail','$page?detail-charts=yes&range='+_bcmRange+'&backend='+encodeURIComponent(b)+live);";
    $h[]="};";

    // Live mode polling
    if($liveMode){
        $h[]="(function(){";
        $h[]="  if(typeof _bcmLiveTimer!=='undefined'&&_bcmLiveTimer){clearInterval(_bcmLiveTimer);}";
        $h[]="  var lastTs=0;";
        $h[]="  var maxPoints=60;"; // ~5h of 5-min windows
        $h[]="  _bcmLiveTimer=setInterval(function(){";
        $h[]="    if(typeof _bcmLive==='undefined'||!_bcmLive){return;}";
        $h[]="    $.ajax({";
        $h[]="      url:'$page?live-poll=yes',";
        $h[]="      dataType:'json',";
        $h[]="      success:function(resp){";
        $h[]="        if(!resp||!resp.success||!resp.data)return;";
        $h[]="        var d=resp.data;";
        $h[]="        if(!d.timestamp||d.timestamp<=lastTs)return;";
        $h[]="        lastTs=d.timestamp;";
        // Find the selected backend in the latest window
        $h[]="        var selB=document.getElementById('bcm-backend-sel');";
        $h[]="        if(!selB)return;";
        $h[]="        var bname=selB.value;";
        $h[]="        var bs=d.backends||[];";
        $h[]="        var found=null;";
        $h[]="        for(var i=0;i<bs.length;i++){if(bs[i].server_name===bname){found=bs[i];break;}}";
        $h[]="        if(!found)return;";
        // Build time label
        $h[]="        var dt=new Date(d.timestamp*1000);";
        $h[]="        var lbl=('0'+dt.getHours()).slice(-2)+':'+('0'+dt.getMinutes()).slice(-2);";
        // Append to each chart
        $h[]="        function appendChart(chart,lbl,vals){";
        $h[]="          if(!chart||!chart.data)return;";
        $h[]="          chart.data.labels.push(lbl);";
        $h[]="          for(var i=0;i<vals.length&&i<chart.data.datasets.length;i++){";
        $h[]="            chart.data.datasets[i].data.push(vals[i]);";
        $h[]="          }";
        $h[]="          if(chart.data.labels.length>maxPoints){";
        $h[]="            chart.data.labels.shift();";
        $h[]="            chart.data.datasets.forEach(function(ds){ds.data.shift();});";
        $h[]="          }";
        $h[]="          chart.update('none');";  // no animation for live updates
        $h[]="        }";
        // Response time chart
        $h[]="        if(typeof _bcmRtChart!=='undefined'){";
        $h[]="          appendChart(_bcmRtChart,lbl,[";
        $h[]="            Math.round((found.max_response_time_ms||0)/10)/100,";
        $h[]="            Math.round((found.avg_response_time_ms||0)/10)/100,";
        $h[]="            Math.round((found.min_response_time_ms||0)/10)/100,";
        $h[]="            Math.round((found.avg_queue_time_ms||0)/10)/100";
        $h[]="          ]);";
        $h[]="        }";
        // Bandwidth chart (use same unit scaling as initial render)
        $h[]="        if(typeof _bcmBwChart!=='undefined'){";
        $h[]="          var div=(typeof _bcmBwDiv!=='undefined')?_bcmBwDiv:1024;";
        $h[]="          var br=(found.total_bytes_read||0)/div;";
        $h[]="          var bu=(found.total_bytes_uploaded||0)/div;";
        $h[]="          appendChart(_bcmBwChart,lbl,[Math.round(br*100)/100, Math.round(bu*100)/100]);";
        $h[]="        }";
        // Connections chart
        $h[]="        if(typeof _bcmConnChart!=='undefined'){";
        $h[]="          appendChart(_bcmConnChart,lbl,[";
        $h[]="            found.active_conns||0,found.server_conns||0,";
        $h[]="            found.frontend_conns||0,found.backend_conns||0";
        $h[]="          ]);";
        $h[]="        }";
        $h[]="      }";
        $h[]="    });";
        $h[]="  },30000);";  // Poll every 30 seconds
        $h[]="})();";
    }

    $h[]="</script>";
    return implode("\n",$h);
}

// ── Live poll: proxies /hacluster/connstats/latest to JS ────────

function live_poll():void{
    header("Content-Type: application/json");
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/connstats/latest");
    $json=json_decode($raw);
    if(!is_object($json)){
        echo json_encode(["success"=>false,"error"=>"backend_unavailable"]);
        return;
    }
    echo $raw;
}
