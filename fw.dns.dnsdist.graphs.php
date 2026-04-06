<?php
/**
 * DNS Firewall > Requests — Chart.js Dashboard
 *
 * Tabs:
 *   1. Requests Overview — DNSDistMetrics line chart (incoming/outgoing/cached) + KPI widgets
 *   2. DNS Queries       — DnsTapDB time-series (queries vs responses) + summary widgets
 *   3. Top IPs           — DnsTapDB horizontal bar (top client IPs + NXDOMAIN)
 *   4. Query Types       — DnsTapDB doughnut (A/AAAA/MX/etc.)
 *   5. Top Hosts         — DnsTapDB horizontal bar (most-queried domains)
 *   6. Categories        — DnsTapDB horizontal bar (top URL categories)
 *
 * API sources:
 *   DNSDistMetrics:  /dnsdist/metrics?range=1h|6h|24h|7d|30d
 *                    /dnsdist/metrics/latest
 *   DnsTapDB:        /dnstap/metrics/summary?period=hour|day|week|month
 *                    /dnstap/metrics/timeseries?period=X&dimension=total
 *                    /dnstap/metrics/top/ips?period=X&limit=N
 *                    /dnstap/metrics/top/qtypes?period=X
 *                    /dnstap/metrics/top/hosts?period=X&limit=N
 *                    /dnstap/metrics/top/categories?period=X&limit=N
 */

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors',1);ini_set('error_reporting',E_ALL);}

$users=new usersMenus();
if(!$users->AsDnsAdministrator&&!$users->AsProxyMonitor){exit();}

// ── Dispatcher ──────────────────────────────────────────────────
if(isset($_GET["main-view"]))         {main_view();          exit;}
if(isset($_GET["tab-requests"]))      {tab_requests();       exit;}
if(isset($_GET["tab-queries"]))       {tab_queries();        exit;}
if(isset($_GET["tab-top-ips"]))       {tab_top_ips();        exit;}
if(isset($_GET["tab-qtypes"]))        {tab_qtypes();         exit;}
if(isset($_GET["tab-top-hosts"]))     {tab_top_hosts();      exit;}
if(isset($_GET["tab-categories"]))    {tab_categories();     exit;}
page();

// ── Helpers ─────────────────────────────────────────────────────

/** Valid user-facing range keys. */
function _valid_range(string $r):string{
    return in_array($r,['1h','6h','24h','7d','30d'])?$r:'1h';
}

/** Map user-facing range to DnsTapDB period param. */
function _dtdb_period(string $r):string{
    $map=['1h'=>'hour','6h'=>'hour','24h'=>'day','7d'=>'week','30d'=>'month'];
    return $map[$r]??'hour';
}

/** Date format string for label formatting based on range. */
function _range_date_fmt(string $range):string{
    switch($range){
        case '7d': case '30d': return 'M d H:00';
        case '24h': return 'D H:i';
        default: return 'H:i';
    }
}

/** Controlled-precision float array for JS. */
function _fmt_floats(array $a,int $d=2):string{
    return '['.implode(',',array_map(fn($v)=>sprintf("%.".$d."f",$v),$a)).']';
}

/** Integer array for JS. */
function _fmt_ints(array $a):string{
    return '['.implode(',',array_map('intval',$a)).']';
}

/** Format bucket key from DnsTapDB into human-readable label. */
function _fmt_bucket(string $raw,string $range):string{
    $len=strlen($raw);
    if($len>=12){
        // 5-min tier: 202603311700 → "17:00" or "Mar 31 17:00"
        $h=substr($raw,8,2);$m=substr($raw,10,2);
        if($range==='24h'||$range==='7d'||$range==='30d'){
            $mon=intval(substr($raw,4,2));$d=intval(substr($raw,6,2));
            $months=['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return ($months[$mon]??$mon)." $d $h:$m";
        }
        return "$h:$m";
    }
    if($len>=10){
        // hour tier: 2026033117 → "17:00" or "Mar 31 17h"
        $h=substr($raw,8,2);
        if($range==='7d'||$range==='30d'){
            $mon=intval(substr($raw,4,2));$d=intval(substr($raw,6,2));
            $months=['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return ($months[$mon]??$mon)." $d {$h}h";
        }
        return "{$h}:00";
    }
    if($len>=8){
        // day tier: 20260331 → "Mar 31"
        $mon=intval(substr($raw,4,2));$d=intval(substr($raw,6,2));
        $months=['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return ($months[$mon]??$mon)." $d";
    }
    return $raw;
}

/** KPI widget (colored card). */
function _kpi(string $bg,string $ico,string $label,string $val):string{
    $val=htmlspecialchars($val);
    return "<div class='col-md-2 col-sm-4'><div style='background:$bg;color:#fff;padding:12px 15px;border-radius:4px;text-align:center;margin-bottom:10px'>"
        ."<div style='font-size:22px;font-weight:700'>$val</div>"
        ."<div style='font-size:11px'><i class='$ico'></i> $label</div></div></div>";
}

/** Reusable ibox panel wrapping a canvas + inline Chart.js. */
function _chart_panel(string $icon,string $title,string $uid,string $chartJs,string $badge='',int $height=300):string{
    $h=[];
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='$icon'></i>&nbsp; $title</h5>";
    if($badge){
        $h[]="<div class='ibox-tools'><span class='badge' style='background:#676a6c;color:#fff'>$badge</span></div>";
    }
    $h[]="</div><div class='ibox-content'>";
    $h[]="<div style='position:relative;height:{$height}px'><canvas id='$uid'></canvas></div>";
    $h[]="</div></div>";
    $h[]="<script>$chartJs</script>";
    return implode("\n",$h);
}

/** Shared Chart.js base options (JSON string for JS). */
function _base_opts():string{
    return "{responsive:true,maintainAspectRatio:false,"
        ."interaction:{mode:'index',intersect:false},"
        ."scales:{x:{display:true,ticks:{maxTicksLimit:20,font:{size:10}}},y:{beginAtZero:true}},"
        ."elements:{point:{radius:1,hoverRadius:4},line:{tension:0.3,borderWidth:2}},"
        ."plugins:{legend:{position:'top',labels:{usePointStyle:true,padding:15}}}}";
}

// ── Page entry point ────────────────────────────────────────────

function page():void{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{APP_DNSDIST}&nbsp;&raquo;&nbsp;{requests}",ico_eye,
        "{APP_DNSDIST_EXPLAIN2}","$page?main-view=yes&range=1h","dnsfw-requests","progress-dnsdist-restart",
        false,"dnsfw-graphs-loader");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Main view: range buttons + tabs + containers ────────────────

function main_view():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=_valid_range($_GET["range"]??"1h");

    $h=[];

    // ── Range button group (shared across all tabs) ──
    $ranges=['1h'=>'{last_hour}','6h'=>'{last_6_hours}','24h'=>'{last_24_hours}','7d'=>'{last_7_days}','30d'=>'{last_30_days}'];
    $h[]="<div style='margin:10px 0 15px'>";
    $h[]="  <div class='btn-group' id='dnsfw-range-btns'>";
    foreach($ranges as $key=>$label){
        $active=($key===$range)?' active':'';
        $h[]="    <button class='btn btn-sm btn-default$active' onclick=\"DnsfwRange('$key',this)\">$label</button>";
    }
    $h[]="  </div>";
    $h[]="  <button class='btn btn-sm btn-primary' style='margin-left:10px' onclick=\"DnsfwRefresh()\">";
    $h[]="    <i class='fas fa-sync-alt'></i> {refresh}</button>";
    $h[]="</div>";

    // ── Topic tabs ──
    $h[]="<div id='dnsfw-tabs'></div>";
    $h[]="<div id='dnsfw-content'></div>";

    // ── Navigation JS ──
    $h[]="<script>";
    $h[]="var _dnsfwRange='$range',_dnsfwTab='tab-requests';";
    $h[]="function DnsfwRange(r,btn){";
    $h[]="  _dnsfwRange=r;";
    $h[]="  \$('#dnsfw-range-btns .btn').removeClass('active');";
    $h[]="  \$(btn).addClass('active');";
    $h[]="  DnsfwRefresh();";
    $h[]="};";
    $h[]="function DnsfwTab(tab){";
    $h[]="  _dnsfwTab=tab;";
    $h[]="  DnsfwRefresh();";
    $h[]="};";
    $h[]="function DnsfwRefresh(){";
    $h[]="  LoadAjax('dnsfw-content','$page?'+_dnsfwTab+'=yes&range='+_dnsfwRange);";
    $h[]="};";

    // ── Build tab buttons ──
    $tabs=[
        'tab-requests'   => $tpl->_ENGINE_parse_body('{requests}'),
        'tab-queries'    => $tpl->_ENGINE_parse_body('{queries}'),
        'tab-top-ips'    => $tpl->_ENGINE_parse_body('{top_ipaddr}'),
        'tab-qtypes'     => $tpl->_ENGINE_parse_body('{type}'),
        'tab-top-hosts'  => $tpl->_ENGINE_parse_body('{top_domains}'),
        'tab-categories' => $tpl->_ENGINE_parse_body('{categories}'),
    ];
    $h[]="(function(){";
    $h[]="var thtml='<ul class=\"nav nav-tabs\" style=\"margin-bottom:15px\">';";
    foreach($tabs as $key=>$label){
        $activeClass=($key==='tab-requests')?" class=\\\"active\\\"":"";
        $h[]="thtml+='<li$activeClass><a href=\"javascript:void(0)\" onclick=\"DnsfwTabClick(this,\\'$key\\')\" data-tab=\"$key\">$label</a></li>';";
    }
    $h[]="thtml+='</ul>';";
    $h[]="document.getElementById('dnsfw-tabs').innerHTML=thtml;";
    $h[]="})();";
    $h[]="function DnsfwTabClick(el,tab){";
    $h[]="  \$('#dnsfw-tabs .nav-tabs li').removeClass('active');";
    $h[]="  \$(el).parent().addClass('active');";
    $h[]="  DnsfwTab(tab);";
    $h[]="};";

    // ── Initial load ──
    $h[]="DnsfwRefresh();";
    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ═══════════════════════════════════════════════════════════════
// TAB 1: Requests Overview (DNSDistMetrics)
// ═══════════════════════════════════════════════════════════════

function tab_requests():void{
    $tpl=new template_admin();
    $range=_valid_range($_GET["range"]??"1h");
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $t=time();

    // ── Fetch latest sample for KPI widgets ──
    $latestJson=json_decode($sock->REST_API("/dnsdist/metrics/latest"));
    $latestIn=0;$latestOut=0;$latestCached=0;$cacheRatio='--';
    if(is_object($latestJson)&&!empty($latestJson->success)&&is_object($latestJson->data)){
        $d=$latestJson->data;
        $latestIn=intval($d->incoming??0);
        $latestOut=intval($d->outgoing??0);
        $latestCached=intval($d->cached??0);
        if($latestIn>0) $cacheRatio=sprintf('%.1f%%',($latestCached/$latestIn)*100);
    }

    $h=[];

    // ── KPI widgets ──
    $h[]="<div class='row'>";
    $h[]=_kpi('#1c84c6','fas fa-arrow-down','{incoming2} /5min',number_format($latestIn));
    $h[]=_kpi('#ed5565','fas fa-arrow-up','{outgoing} /5min',number_format($latestOut));
    $h[]=_kpi('#1ab394','fas fa-database','{cached} /5min',number_format($latestCached));
    $h[]=_kpi('#ab7df6','fas fa-percentage','{cache_hit_ratio}',$cacheRatio);
    $h[]="</div>";

    // ── Fetch time series ──
    $json=json_decode($sock->REST_API("/dnsdist/metrics?range=$range"));
    if(!is_object($json)||empty($json->success)||!is_array($json->data)){
        $h[]=$tpl->div_warning("{no_data_available}");
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    $labels=[];$dataIn=[];$dataOut=[];$dataCached=[];
    $dateFmt=_range_date_fmt($range);
    foreach($json->data as $p){
        $labels[]=date($dateFmt,$p->timestamp??0);
        $dataIn[]=intval($p->incoming??0);
        $dataOut[]=intval($p->outgoing??0);
        $dataCached[]=intval($p->cached??0);
    }

    $labelsJs=json_encode($labels);
    $inJs=_fmt_ints($dataIn);
    $outJs=_fmt_ints($dataOut);
    $cachedJs=_fmt_ints($dataCached);
    $incoming=$tpl->javascript_parse_text("{incoming2}");
    // ── Line chart: 3 series ──
    $uid="dnsdist-req-$t";
    $js ="(function(){";
    $js.="var opts="._base_opts().";";
    $js.="new Chart(document.getElementById('$uid'),{type:'line',data:{";
    $js.="labels:$labelsJs,datasets:[";
    $js.="{label:'$incoming',data:$inJs,borderColor:'#1c84c6',backgroundColor:'rgba(28,132,198,0.08)',fill:true,tension:0.3,pointRadius:1},";
    $js.="{label:'{outgoing}',data:$outJs,borderColor:'#ed5565',fill:false,tension:0.3,pointRadius:1},";
    $js.="{label:'{cached}',data:$cachedJs,borderColor:'#1ab394',backgroundColor:'rgba(26,179,148,0.08)',fill:true,tension:0.3,pointRadius:1}";
    $js.="]},options:opts});";
    $js.="})();";

    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";
    $h[]=_chart_panel('fas fa-chart-line','{requests}',$uid,$js,$range);

    // ── Stacked area: incoming breakdown (outgoing + cached = incoming) ──
    $uid2="dnsdist-stack-$t";
    $js2 ="(function(){";
    $js2.="var opts="._base_opts().";";
    $js2.="opts.scales.y={stacked:true,beginAtZero:true};";
    $js2.="opts.scales.x.ticks={maxTicksLimit:20,font:{size:10}};";
    $js2.="new Chart(document.getElementById('$uid2'),{type:'line',data:{";
    $js2.="labels:$labelsJs,datasets:[";
    $js2.="{label:'{cached}',data:$cachedJs,borderColor:'#1ab394',backgroundColor:'rgba(26,179,148,0.4)',fill:true,tension:0.3,pointRadius:0},";
    $js2.="{label:'{outgoing}',data:$outJs,borderColor:'#ed5565',backgroundColor:'rgba(237,85,101,0.4)',fill:true,tension:0.3,pointRadius:0}";
    $js2.="]},options:opts});";
    $js2.="})();";

    $h[]=_chart_panel('fas fa-layer-group','{incoming_breakdown}',$uid2,$js2);
    $h[]="<script>NoSpinner();</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ═══════════════════════════════════════════════════════════════
// TAB 2: DNS Queries (DnsTapDB)
// ═══════════════════════════════════════════════════════════════

function tab_queries():void{
    $tpl=new template_admin();
    $range=_valid_range($_GET["range"]??"1h");
    $period=_dtdb_period($range);
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $t=time();

    // ── Summary widgets ──
    $sumJson=json_decode($sock->REST_API("/dnstap/metrics/summary?period=$period"));
    $totalQ=0;$totalR=0;
    if(is_object($sumJson)&&!empty($sumJson->success)&&is_object($sumJson->data)){
        $d=$sumJson->data;
        $totalQ=intval($d->total_queries??0);
        $totalR=intval($d->total_responses??0);
    }

    $h=[];
    $h[]="<div class='row'>";
    $h[]=_kpi('#1c84c6','fas fa-globe','{total_queries}',number_format($totalQ));
    $h[]=_kpi('#1ab394','fas fa-reply','{total_responses}',number_format($totalR));
    $nxRatio=($totalQ>0)?sprintf('%.1f%%',(($totalQ-$totalR)/$totalQ)*100):'--';
    $h[]=_kpi('#f8ac59','fas fa-exclamation-triangle','{unanswered_ratio}',$nxRatio);
    $h[]="</div>";

    // ── Time series ──
    $tsJson=json_decode($sock->REST_API("/dnstap/metrics/timeseries?period=$period&dimension=total"));
    if(!is_object($tsJson)||empty($tsJson->success)||!is_object($tsJson->data)||!is_array($tsJson->data->points??[])){
        $h[]=$tpl->div_warning("{no_data_available}");
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    $labels=[];$dataQ=[];$dataR=[];
    foreach($tsJson->data->points as $p){
        $labels[]=_fmt_bucket($p->bucket??'',$range);
        $dataQ[]=intval($p->count??0);
        $dataR[]=intval($p->extra??0);
    }

    $labelsJs=json_encode($labels);
    $qJs=_fmt_ints($dataQ);
    $rJs=_fmt_ints($dataR);
    $responses=$tpl->javascript_parse_text("{responses}");
    $QueriesText=$tpl->javascript_parse_text("{queries}");
    $uid="dnstap-qs-$t";
    $js ="(function(){";
    $js.="var opts="._base_opts().";";
    $js.="new Chart(document.getElementById('$uid'),{type:'line',data:{";
    $js.="labels:$labelsJs,datasets:[";
    $js.="{label:'$QueriesText',data:$qJs,borderColor:'#1c84c6',backgroundColor:'rgba(28,132,198,0.08)',fill:true,tension:0.3,pointRadius:1},";
    $js.="{label:'$responses',data:$rJs,borderColor:'#1ab394',fill:false,tension:0.3,pointRadius:1}";
    $js.="]},options:opts});";
    $js.="})();";

    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";
    $h[]=_chart_panel('fas fa-chart-area','{dns_queries_over_time}',$uid,$js,$period);
    $h[]="<script>NoSpinner();</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ═══════════════════════════════════════════════════════════════
// TAB 3: Top IPs (DnsTapDB)
// ═══════════════════════════════════════════════════════════════

function tab_top_ips():void{
    $tpl=new template_admin();
    $range=_valid_range($_GET["range"]??"1h");
    $period=_dtdb_period($range);
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $t=time();

    $json=json_decode($sock->REST_API("/dnstap/metrics/top/ips?period=$period&limit=15"));
    if(!is_object($json)||empty($json->success)||!is_object($json->data)||!is_array($json->data->items??[])){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_data_available}"));
        return;
    }

    $labels=[];$data=[];$nxdata=[];
    foreach($json->data->items as $item){
        $labels[]=$item->value??"?";
        $data[]=intval($item->count??0);
        $nxdata[]=intval($item->extra??0);
    }

    $labelsJs=json_encode($labels);
    $dataJs=_fmt_ints($data);
    $nxJs=_fmt_ints($nxdata);

    $h=[];
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    // ── Horizontal bar chart ──
    $uid="top-ips-$t";
    $barH=max(300,count($labels)*28);
    $queriesText=$tpl->javascript_parse_text("{queries}");
    $js ="(function(){";
    $js.="new Chart(document.getElementById('$uid'),{type:'bar',data:{";
    $js.="labels:$labelsJs,datasets:[";
    $js.="{label:'$queriesText',data:$dataJs,backgroundColor:'#1c84c6'},";
    $js.="{label:'NXDOMAIN',data:$nxJs,backgroundColor:'#ed5565'}";
    $js.="]},options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',";
    $js.="plugins:{legend:{position:'top',labels:{usePointStyle:true,padding:15}}},";
    $js.="scales:{x:{beginAtZero:true,stacked:true},y:{stacked:true}}}});";
    $js.="})();";

    $h[]=_chart_panel('fas fa-network-wired','{top_ipaddr}',$uid,$js,'Top 15',$barH);

    // ── FooTable with IP details ──
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-table'></i>&nbsp; {details}</h5></div>";
    $h[]="<div class='ibox-content'>";
    $h[]="<table id='iptbl-$t' class='footable table table-stripped' data-page-size='15'>";
    $h[]="<thead><tr>";
    $h[]="<th data-sortable=true data-type='text'>{ip_address}</th>";
    $h[]="<th data-sortable=true data-type='number'>$queriesText</th>";
    $h[]="<th data-sortable=true data-type='number'>NXDOMAIN</th>";
    $h[]="<th data-sortable=true data-type='number'>NXDOMAIN %</th>";
    $h[]="</tr></thead><tbody>";
    $TRCLASS=null;
    for($i=0;$i<count($labels);$i++){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $pct=($data[$i]>0)?sprintf('%.1f',$nxdata[$i]/$data[$i]*100):'0.0';
        $nxColor=floatval($pct)>20?'#ed5565':'inherit';
        $h[]="<tr class='$TRCLASS'>";
        $h[]="<td><span style='font-family:monospace'>{$labels[$i]}</span></td>";
        $h[]="<td>".number_format($data[$i])."</td>";
        $h[]="<td>".number_format($nxdata[$i])."</td>";
        $h[]="<td><span style='color:$nxColor;font-weight:600'>$pct%</span></td>";
        $h[]="</tr>";
    }
    $h[]="</tbody>";
    $h[]="<tfoot><tr><td colspan='4'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $h[]="</table>";
    $h[]="<script>NoSpinner();\n".implode("\n",$tpl->ICON_SCRIPTS);
    $h[]="$(document).ready(function(){\$('#iptbl-$t').footable({\"filtering\":{\"enabled\":true},\"sorting\":{\"enabled\":true},\"paging\":{\"size\":15}});});</script>";
    $h[]="</div></div>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ═══════════════════════════════════════════════════════════════
// TAB 4: Query Types (DnsTapDB)
// ═══════════════════════════════════════════════════════════════

function tab_qtypes():void{
    $tpl=new template_admin();
    $range=_valid_range($_GET["range"]??"1h");
    $period=_dtdb_period($range);
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $t=time();

    $json=json_decode($sock->REST_API("/dnstap/metrics/top/qtypes?period=$period"));
    if(!is_object($json)||empty($json->success)||!is_object($json->data)||!is_array($json->data->items??[])){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_data_available}"));
        return;
    }

    $labels=[];$data=[];$total=0;
    $palette=['#1c84c6','#1ab394','#f8ac59','#ed5565','#23c6c8','#ab7df6','#34495e','#e74c3c',
              '#2ecc71','#3498db','#9b59b6','#e67e22','#1abc9c','#c0392b','#2980b9','#8e44ad'];
    foreach($json->data->items as $item){
        $labels[]=$item->value??"?";
        $cnt=intval($item->count??0);
        $data[]=$cnt;
        $total+=$cnt;
    }

    $labelsJs=json_encode($labels);
    $dataJs=_fmt_ints($data);
    $colorsJs=json_encode(array_slice($palette,0,count($labels)));

    $h=[];
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    $h[]="<div class='row'>";

    // ── Doughnut chart (left) ──
    $h[]="<div class='col-md-6'>";
    $uid="qtypes-doughnut-$t";
    $js ="(function(){";
    $js.="new Chart(document.getElementById('$uid'),{type:'doughnut',data:{";
    $js.="labels:$labelsJs,datasets:[{data:$dataJs,backgroundColor:$colorsJs,borderWidth:2}]},";
    $js.="options:{responsive:true,maintainAspectRatio:false,";
    $js.="plugins:{legend:{position:'bottom',labels:{padding:12,usePointStyle:true}}}}});";
    $js.="})();";
    $h[]=_chart_panel('fas fa-chart-pie','{query_type_distribution}',$uid,$js);
    $h[]="</div>";

    // ── Table (right) ──
    $h[]="<div class='col-md-6'>";
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-list'></i>&nbsp; {query_types}</h5></div>";
    $h[]="<div class='ibox-content'>";
    $h[]="<table class='table table-stripped'>";
    $h[]="<thead><tr><th>{type}</th><th style='text-align:right'>{count}</th><th style='text-align:right'>%</th></tr></thead><tbody>";
    for($i=0;$i<count($labels);$i++){
        $pct=($total>0)?sprintf('%.1f',$data[$i]/$total*100):'0.0';
        $color=$palette[$i%count($palette)];
        $h[]="<tr><td><span style='display:inline-block;width:12px;height:12px;border-radius:50%;background:$color;margin-right:8px'></span>{$labels[$i]}</td>";
        $h[]="<td style='text-align:right'>".number_format($data[$i])."</td>";
        $h[]="<td style='text-align:right'>$pct%</td></tr>";
    }
    $h[]="</tbody></table>";
    $h[]="</div></div>";
    $h[]="</div>";

    $h[]="</div>"; // row
    $h[]="<script>NoSpinner();</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ═══════════════════════════════════════════════════════════════
// TAB 5: Top Hosts (DnsTapDB)
// ═══════════════════════════════════════════════════════════════

function tab_top_hosts():void{
    $tpl=new template_admin();
    $range=_valid_range($_GET["range"]??"1h");
    $period=_dtdb_period($range);
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $t=time();

    $json=json_decode($sock->REST_API("/dnstap/metrics/top/hosts?period=$period&limit=20"));
    if(!is_object($json)||empty($json->success)||!is_object($json->data)||!is_array($json->data->items??[])){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_data_available}"));
        return;
    }

    $labels=[];$data=[];
    foreach($json->data->items as $item){
        $labels[]=$item->value??"?";
        $data[]=intval($item->count??0);
    }

    $labelsJs=json_encode($labels);
    $dataJs=_fmt_ints($data);

    $h=[];
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";
    $queriesText=$tpl->javascript_parse_text("{queries}");
    // ── Horizontal bar chart ──
    $uid="top-hosts-$t";
    $barH=max(300,count($labels)*26);
    $js ="(function(){";
    $js.="new Chart(document.getElementById('$uid'),{type:'bar',data:{";
    $js.="labels:$labelsJs,datasets:[";
    $js.="{label:'$queriesText',data:$dataJs,backgroundColor:'#1c84c6',borderRadius:3}";
    $js.="]},options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',";
    $js.="plugins:{legend:{display:false}},";
    $js.="scales:{x:{beginAtZero:true}}}});";
    $js.="})();";

    $h[]=_chart_panel('fas fa-globe-americas','{top_queried_domains}',$uid,$js,'Top 20',$barH);

    // ── FooTable ──
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-table'></i>&nbsp; {details}</h5></div>";
    $h[]="<div class='ibox-content'>";
    $h[]="<table id='hosttbl-$t' class='footable table table-stripped' data-page-size='20'>";
    $h[]="<thead><tr>";
    $h[]="<th>#</th>";
    $h[]="<th data-sortable=true data-type='text'>{hostname}</th>";
    $h[]="<th data-sortable=true data-type='number'>$queriesText</th>";
    $h[]="</tr></thead><tbody>";
    $TRCLASS=null;
    for($i=0;$i<count($labels);$i++){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $rank=$i+1;
        $h[]="<tr class='$TRCLASS'><td>$rank</td>";
        $h[]="<td><span style='font-family:monospace'>{$labels[$i]}</span></td>";
        $h[]="<td>".number_format($data[$i])."</td></tr>";
    }
    $h[]="</tbody>";
    $h[]="<tfoot><tr><td colspan='3'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $h[]="</table>";
    $h[]="<script>NoSpinner();\n".implode("\n",$tpl->ICON_SCRIPTS);
    $h[]="$(document).ready(function(){\$('#hosttbl-$t').footable({\"filtering\":{\"enabled\":true},\"sorting\":{\"enabled\":true},\"paging\":{\"size\":20}});});</script>";
    $h[]="</div></div>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ═══════════════════════════════════════════════════════════════
// TAB 6: Categories (DnsTapDB)
// ═══════════════════════════════════════════════════════════════

function tab_categories():void{
    $tpl=new template_admin();
    $range=_valid_range($_GET["range"]??"1h");
    $period=_dtdb_period($range);
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $t=time();

    $json=json_decode($sock->REST_API("/dnstap/metrics/top/categories?period=$period&limit=20"));
    if(!is_object($json)||empty($json->success)||!is_object($json->data)||!is_array($json->data->items??[])){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_data_available}"));
        return;
    }

    include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
    $catz=new mysql_catz();

    $labels=[];$data=[];$total=0;
    $palette=['#1c84c6','#1ab394','#f8ac59','#ed5565','#23c6c8','#ab7df6','#34495e','#e74c3c',
              '#2ecc71','#3498db','#9b59b6','#e67e22','#1abc9c','#c0392b','#2980b9','#8e44ad',
              '#27ae60','#d35400','#2c3e50','#16a085'];
    foreach($json->data->items as $item){
        $catId=intval($item->value??0);
        $catName=$catz->CategoryIntToStr($catId);
        if($catName==null||$catName==='') $catName="Category #$catId";
        $labels[]=$catName;
        $cnt=intval($item->count??0);
        $data[]=$cnt;
        $total+=$cnt;
    }

    $labelsJs=json_encode($labels);
    $dataJs=_fmt_ints($data);
    $colorsJs=json_encode(array_slice($palette,0,count($labels)));

    $h=[];
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    $h[]="<div class='row'>";

    // ── Doughnut (left) ──
    $h[]="<div class='col-md-5'>";
    $uid="cat-doughnut-$t";
    $js ="(function(){";
    $js.="new Chart(document.getElementById('$uid'),{type:'doughnut',data:{";
    $js.="labels:$labelsJs,datasets:[{data:$dataJs,backgroundColor:$colorsJs,borderWidth:2}]},";
    $js.="options:{responsive:true,maintainAspectRatio:false,";
    $js.="plugins:{legend:{position:'bottom',labels:{padding:10,usePointStyle:true,font:{size:11}}}}}});";
    $js.="})();";
    $h[]=_chart_panel('fas fa-chart-pie','{categories}',$uid,$js);
    $h[]="</div>";
    $queriesText=$tpl->javascript_parse_text("{queries}");
    // ── Horizontal bar (right) ──
    $h[]="<div class='col-md-7'>";
    $uid2="cat-bar-$t";
    $barH=max(300,count($labels)*26);
    $js2 ="(function(){";
    $js2.="new Chart(document.getElementById('$uid2'),{type:'bar',data:{";
    $js2.="labels:$labelsJs,datasets:[";
    $js2.="{label:'$queriesText',data:$dataJs,backgroundColor:$colorsJs,borderRadius:3}";
    $js2.="]},options:{responsive:true,maintainAspectRatio:false,indexAxis:'y',";
    $js2.="plugins:{legend:{display:false}},";
    $js2.="scales:{x:{beginAtZero:true}}}});";
    $js2.="})();";

    $h[]=_chart_panel('fas fa-tags','{top_categories}',$uid2,$js2,'Top 20',$barH);
    $h[]="</div>";

    $h[]="</div>"; // row

    // ── Summary table ──
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-table'></i>&nbsp; {details}</h5></div>";
    $h[]="<div class='ibox-content'>";
    $h[]="<table id='cattbl-$t' class='footable table table-stripped' data-page-size='20'>";
    $h[]="<thead><tr>";
    $h[]="<th>#</th>";
    $h[]="<th data-sortable=true data-type='text'>{category}</th>";
    $h[]="<th data-sortable=true data-type='number'>$queriesText</th>";
    $h[]="<th data-sortable=true data-type='number'>%</th>";
    $h[]="</tr></thead><tbody>";
    $TRCLASS=null;
    for($i=0;$i<count($labels);$i++){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $pct=($total>0)?sprintf('%.1f',$data[$i]/$total*100):'0.0';
        $color=$palette[$i%count($palette)];
        $rank=$i+1;
        $h[]="<tr class='$TRCLASS'><td>$rank</td>";
        $h[]="<td><span style='display:inline-block;width:12px;height:12px;border-radius:50%;background:$color;margin-right:8px'></span>{$labels[$i]}</td>";
        $h[]="<td>".number_format($data[$i])."</td>";
        $h[]="<td>$pct%</td></tr>";
    }
    $h[]="</tbody>";
    $h[]="<tfoot><tr><td colspan='4'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $h[]="</table>";
    $h[]="<script>NoSpinner();\n".implode("\n",$tpl->ICON_SCRIPTS);
    $h[]="$(document).ready(function(){\$('#cattbl-$t').footable({\"filtering\":{\"enabled\":true},\"sorting\":{\"enabled\":true},\"paging\":{\"size\":20}});});</script>";
    $h[]="</div></div>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}
