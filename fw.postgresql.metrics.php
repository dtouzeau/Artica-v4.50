<?php
/**
 * PostgreSQL Metrics Dashboard
 * Visualises connection states, transaction rates, tuple operations,
 * cache hit ratio, database size and lock monitoring from PgRrd BoltDB data.
 *
 * Charts (6):
 *   1. Connection States   — stacked area (active, idle, idle_tx, unknown)
 *   2. Transaction Rates   — dual-axis line (commits, rollbacks)
 *   3. Tuple Operations    — line (fetch, insert, update, delete)
 *   4. Cache Hit Ratio     — line with 99% threshold
 *   5. Database Size       — filled line (MB)
 *   6. Locks & Deadlocks   — line (waiting_locks, deadlocks)
 *
 * API endpoints (all GET, {success,data} envelope):
 *   /postgresql/metrics?range=X          — auto-selects best bucket
 *   /postgresql/metrics/raw?from=&to=    — raw 5-min samples
 *   /postgresql/metrics/{bucket}?from=&to= — specific bucket
 *   /postgresql/metrics/latest           — current raw sample (live mode)
 *   /postgresql/metrics/status           — engine diagnostics
 */

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors',1);ini_set('error_reporting',E_ALL);}

$users=new usersMenus();
if(!$users->AsDatabaseAdministrator){exit();}

// ── Dispatcher ──────────────────────────────────────────────────
if(isset($_GET["main-view"]))  {main_view();  exit;}
if(isset($_GET["charts"]))     {charts();     exit;}
if(isset($_GET["live-poll"]))  {live_poll();  exit;}
if(isset($_GET["latest-kpi"])) {latest_kpi(); exit;}
page();

// ── Helpers ─────────────────────────────────────────────────────

function _valid_range(string $r):string{
    $ok=["1h","6h","24h","48h","7d","30d","60d","90d"];
    return in_array($r,$ok)?$r:"1h";
}

function _range_date_fmt(string $range):string{
    switch($range){
        case "30d": case "60d": case "90d": return 'M d';
        case "7d": return 'M d H:00';
        case "48h": return 'M d H:i';
        default: return 'H:i';
    }
}

function _is_raw(string $range):bool{
    return in_array($range,["1h","2h","30m","5m"]);
}

/** Controlled-precision float array for JS. Avoids PHP serialize_precision 50-digit bug. */
function _fmt_floats(array $a,int $d=2):string{
    return '['.implode(',',array_map(fn($v)=>sprintf("%.".$d."f",$v),$a)).']';
}

function _fmt_ints(array $a):string{
    return '['.implode(',',array_map('intval',$a)).']';
}

/** Reusable KPI widget (colored card). */
function _kpi(string $bg,string $ico,string $label,string $val):string{
    $val=htmlspecialchars($val);
    return "<div class='col-md-2 col-sm-4'><div style='background:$bg;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>"
        ."<div style='font-size:22px;font-weight:700'>$val</div>"
        ."<div style='font-size:11px'><i class='$ico'></i> $label</div></div></div>";
}

/** Reusable ibox panel with canvas and inline Chart.js. */
function _chart_panel(string $icon,string $title,string $uid,string $chartJs,string $badge=''):string{
    $h=[];
    $h[]="<div class='col-md-6'>";
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='$icon'></i>&nbsp; $title</h5>";
    if($badge){
        $h[]="<div class='ibox-tools'><span class='badge' style='background:#676a6c;color:#fff'>$badge</span></div>";
    }
    $h[]="</div><div class='ibox-content'>";
    $h[]="<div style='position:relative;height:280px'><canvas id='$uid'></canvas></div>";
    $h[]="</div></div></div>";
    $h[]="<script>$chartJs</script>";
    return implode("\n",$h);
}

// ── Page entry point ────────────────────────────────────────────

function page():void{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("PostgreSQL &raquo; {metrics}",
        "fas fa-chart-area",
        "{postgresql_metrics_explain}",
        "$page?main-view=yes&range=1h");
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Main view: range buttons + KPI + chart container ────────────

function main_view():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=_valid_range($_GET["range"]??"1h");

    // Fetch latest sample for KPI widgets
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/postgresql/metrics/latest"));
    $latest=null;
    if(is_object($json)&&!empty($json->success)&&is_object($json->data)){
        $latest=$json->data;
    }

    $h=[];

    // Range buttons
    $ranges=["1h"=>"{last_hour}","6h"=>"6h","24h"=>"{last_24_hours}","48h"=>"48h","7d"=>"{week}","30d"=>"{month}","90d"=>"90d"];
    $h[]="<div style='margin:10px 0 15px'>";
    $h[]="  <div class='btn-group' id='pg-range-btns'>";
    foreach($ranges as $key=>$label){
        $active=($key===$range)?' active':'';
        $h[]="    <button class='btn btn-sm btn-default$active' onclick=\"PgRange('$key',this)\">$label</button>";
    }
    $h[]="  </div>";
    $h[]="  <button class='btn btn-sm btn-primary' style='margin-left:10px' onclick=\"PgRefresh()\">";
    $h[]="    <i class='fas fa-sync-alt'></i> {refresh}</button>";
    $h[]="  <button id='pg-live-btn' class='btn btn-sm btn-default' style='margin-left:5px' onclick=\"PgToggleLive()\">";
    $h[]="    <i class='fas fa-broadcast-tower'></i> Live</button>";
    $h[]="  <span id='pg-live-indicator' style='display:none;margin-left:8px;color:#1ab394;font-size:12px'>";
    $h[]="    <i class='fas fa-circle' style='animation:pgblink 1s infinite'></i> {live}</span>";
    $h[]="</div>";

    // KPI widgets
    $conns=$latest?intval($latest->total??0):0;
    $active=$latest?intval($latest->active??0):0;
    $cache=$latest?floatval($latest->cache_hit_pct??0):0;
    $sizeMb=$latest?floatval($latest->db_size_mb??0):0;
    $txRate=$latest?floatval($latest->tx_commit_rate??0):0;
    $locks=$latest?intval($latest->waiting_locks??0):0;

    $cacheBg=$cache>=99?'#1ab394':($cache>=95?'#f8ac59':'#ed5565');
    $locksBg=$locks>0?'#ed5565':'#1ab394';

    $h[]="<div class='row' id='pg-kpi' style='margin-bottom:15px'>";
    $h[]=_kpi('#1c84c6','fas fa-plug','{connections}',(string)$conns);
    $h[]=_kpi('#23c6c8','fas fa-bolt','{active2}',(string)$active);
    $h[]=_kpi('#34495e','fas fa-exchange-alt','tx/s',sprintf('%.1f',$txRate));
    $h[]=_kpi($cacheBg,'fas fa-tachometer-alt','{cache} hit %',sprintf('%.2f%%',$cache));
    $h[]=_kpi('#ab7df6','fas fa-database','{size}',sprintf('%.0f MB',$sizeMb));
    $h[]=_kpi($locksBg,'fas fa-lock','{waiting} {locks}',(string)$locks);
    $h[]="</div>";

    // Chart container
    $h[]="<div id='pg-charts'></div>";

    // Navigation JS + live mode
    $h[]="<style>@keyframes pgblink{0%,100%{opacity:1}50%{opacity:0.3}}</style>";
    $h[]="<script>";
    $h[]="var _pgRange='$range',_pgLive=false,_pgLiveTimer=null,_pgKpiTimer=null;";
    $h[]="function PgRange(r,btn){";
    $h[]="  _pgRange=r;";
    $h[]="  \$('#pg-range-btns .btn').removeClass('active');";
    $h[]="  \$(btn).addClass('active');";
    $h[]="  if(_pgLive){PgStopLive();}";
    $h[]="  PgRefresh();";
    $h[]="};";
    $h[]="function PgRefresh(){";
    $h[]="  LoadAjax('pg-charts','$page?charts=yes&range='+_pgRange);";
    $h[]="};";

    // Live mode
    $h[]="function PgToggleLive(){";
    $h[]="  if(_pgLive){PgStopLive();return;}";
    $h[]="  _pgLive=true;";
    $h[]="  \$('#pg-live-btn').removeClass('btn-default').addClass('btn-danger');";
    $h[]="  \$('#pg-live-indicator').show();";
    $h[]="  _pgRange='1h';";
    $h[]="  \$('#pg-range-btns .btn').removeClass('active');";
    $h[]="  \$('#pg-range-btns .btn').first().addClass('active');";
    $h[]="  LoadAjax('pg-charts','$page?charts=yes&range=1h&live=1');";
    $h[]="  _pgKpiTimer=setInterval(function(){";
    $h[]="    LoadAjaxSilent('pg-kpi','$page?latest-kpi=yes');";
    $h[]="  },30000);";
    $h[]="};";
    $h[]="function PgStopLive(){";
    $h[]="  _pgLive=false;";
    $h[]="  \$('#pg-live-btn').removeClass('btn-danger').addClass('btn-default');";
    $h[]="  \$('#pg-live-indicator').hide();";
    $h[]="  if(_pgLiveTimer){clearInterval(_pgLiveTimer);_pgLiveTimer=null;}";
    $h[]="  if(_pgKpiTimer){clearInterval(_pgKpiTimer);_pgKpiTimer=null;}";
    $h[]="};";

    $h[]="PgRefresh();";
    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── KPI widget refresh (live mode) ──────────────────────────────

function latest_kpi():void{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/postgresql/metrics/latest"));
    $latest=null;
    if(is_object($json)&&!empty($json->success)&&is_object($json->data)){
        $latest=$json->data;
    }

    $conns=$latest?intval($latest->total??0):0;
    $active=$latest?intval($latest->active??0):0;
    $cache=$latest?floatval($latest->cache_hit_pct??0):0;
    $sizeMb=$latest?floatval($latest->db_size_mb??0):0;
    $txRate=$latest?floatval($latest->tx_commit_rate??0):0;
    $locks=$latest?intval($latest->waiting_locks??0):0;

    $cacheBg=$cache>=99?'#1ab394':($cache>=95?'#f8ac59':'#ed5565');
    $locksBg=$locks>0?'#ed5565':'#1ab394';

    $tpl=new template_admin();
    $h=[];
    $h[]=_kpi('#1c84c6','fas fa-plug','{connections}',(string)$conns);
    $h[]=_kpi('#23c6c8','fas fa-bolt','{active2}',(string)$active);
    $h[]=_kpi('#34495e','fas fa-exchange-alt','tx/s',sprintf('%.1f',$txRate));
    $h[]=_kpi($cacheBg,'fas fa-tachometer-alt','{cache} hit %',sprintf('%.2f%%',$cache));
    $h[]=_kpi('#ab7df6','fas fa-database','{size}',sprintf('%.0f MB',$sizeMb));
    $h[]=_kpi($locksBg,'fas fa-lock','{waiting} {locks}',(string)$locks);
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Charts: all 6 charts rendered ───────────────────────────────

function charts():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=_valid_range($_GET["range"]??"1h");
    $live=isset($_GET["live"]);
    $t=time();
    $dfmt=_range_date_fmt($range);
    $raw=_is_raw($range);

    // Fetch metrics
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/postgresql/metrics?range=$range"));
    if(!is_object($json)||empty($json->success)||!is_object($json->data)){
        echo "<div class='alert alert-warning'>{no_data_available}</div>";
        return;
    }
    $series=$json->data->series??[];
    if(!is_array($series)||count($series)==0){
        echo "<div class='alert alert-info'>{no_data_available}</div>";
        return;
    }

    // Build arrays
    $labels=[];
    $connActive=[];$connIdle=[];$connIdleTx=[];$connUnknown=[];
    $txCommit=[];$txRollback=[];
    $tupFetch=[];$tupInsert=[];$tupUpdate=[];$tupDelete=[];
    $cacheHit=[];$cacheMin=[];
    $dbSize=[];$dbSizeMax=[];
    $wLocks=[];$deadlocks=[];

    foreach($series as $p){
        $labels[]=date($dfmt,$p->ts??0);

        if($raw){
            $connActive[]=intval($p->active??0);
            $connIdle[]=intval($p->idle??0);
            $connIdleTx[]=intval($p->idle_tx??0);
            $connUnknown[]=intval($p->unknown??0);
            $txCommit[]=floatval($p->tx_commit_rate??0);
            $txRollback[]=floatval($p->tx_rollback_rate??0);
            $tupFetch[]=floatval($p->tup_fetch_rate??0);
            $tupInsert[]=floatval($p->tup_insert_rate??0);
            $tupUpdate[]=floatval($p->tup_update_rate??0);
            $tupDelete[]=floatval($p->tup_delete_rate??0);
            $cacheHit[]=floatval($p->cache_hit_pct??0);
            $cacheMin[]=floatval($p->cache_hit_pct??0);
            $dbSize[]=floatval($p->db_size_mb??0);
            $dbSizeMax[]=floatval($p->db_size_mb??0);
            $wLocks[]=intval($p->waiting_locks??0);
            $deadlocks[]=intval($p->deadlocks??0);
        } else {
            $connActive[]=floatval($p->avg_active??0);
            $connIdle[]=floatval($p->avg_idle??0);
            $connIdleTx[]=floatval($p->avg_idle_tx??0);
            $connUnknown[]=0;
            $txCommit[]=floatval($p->avg_tx_commit_rate??0);
            $txRollback[]=floatval($p->avg_tx_rollback_rate??0);
            $tupFetch[]=floatval($p->avg_tup_fetch_rate??0);
            $tupInsert[]=floatval($p->avg_tup_insert_rate??0);
            $tupUpdate[]=floatval($p->avg_tup_update_rate??0);
            $tupDelete[]=floatval($p->avg_tup_delete_rate??0);
            $cacheHit[]=floatval($p->avg_cache_hit_pct??0);
            $cacheMin[]=floatval($p->min_cache_hit_pct??0);
            $dbSize[]=floatval($p->avg_db_size_mb??0);
            $dbSizeMax[]=floatval($p->max_db_size_mb??0);
            $wLocks[]=floatval($p->avg_waiting_locks??0);
            $deadlocks[]=intval($p->max_deadlocks??0);
        }
    }

    $labelsJs=json_encode($labels);

    $h=[];
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.4.4.7.js'></script>";
    $h[]="<div class='row'>";

    // ── 1. Connection States (stacked area) ────────────────────
    $uid="pg-conn-$t";
    $js="new Chart(document.getElementById('$uid'),{type:'line',";
    $js.="data:{labels:$labelsJs,datasets:[";
    $js.="{label:'Active',data:"._fmt_floats($connActive,1).",borderColor:'#2196F3',backgroundColor:'rgba(33,150,243,0.3)',fill:true,tension:0.3,pointRadius:1},";
    $js.="{label:'Idle',data:"._fmt_floats($connIdle,1).",borderColor:'#4CAF50',backgroundColor:'rgba(76,175,80,0.2)',fill:true,tension:0.3,pointRadius:1},";
    $js.="{label:'Idle TX',data:"._fmt_floats($connIdleTx,1).",borderColor:'#FF9800',backgroundColor:'rgba(255,152,0,0.2)',fill:true,tension:0.3,pointRadius:1},";
    $js.="{label:'Unknown',data:"._fmt_floats($connUnknown,1).",borderColor:'#9E9E9E',backgroundColor:'rgba(158,158,158,0.15)',fill:true,tension:0.3,pointRadius:1}";
    $js.="]},options:{responsive:true,maintainAspectRatio:false,";
    $js.="scales:{x:{ticks:{maxTicksLimit:12,font:{size:10}}},y:{beginAtZero:true,stacked:true,title:{display:true,text:'connections'}}},";
    $js.="plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11}}}}}});";
    $h[]=_chart_panel('fas fa-plug','{connections}',                $uid,$js,$range);

    // ── 2. Transaction Rates (dual-axis line) ──────────────────
    $uid="pg-tx-$t";
    $js="new Chart(document.getElementById('$uid'),{type:'line',";
    $js.="data:{labels:$labelsJs,datasets:[";
    $js.="{label:'Commits/s',data:"._fmt_floats($txCommit,1).",borderColor:'#1ab394',backgroundColor:'rgba(26,179,148,0.1)',fill:true,tension:0.3,pointRadius:1,yAxisID:'y'},";
    $js.="{label:'Rollbacks/s',data:"._fmt_floats($txRollback,2).",borderColor:'#ed5565',borderDash:[5,3],tension:0.3,pointRadius:1,yAxisID:'y1'}";
    $js.="]},options:{responsive:true,maintainAspectRatio:false,";
    $js.="scales:{x:{ticks:{maxTicksLimit:12,font:{size:10}}},";
    $js.="y:{beginAtZero:true,position:'left',title:{display:true,text:'commits/s'}},";
    $js.="y1:{beginAtZero:true,position:'right',grid:{drawOnChartArea:false},title:{display:true,text:'rollbacks/s'}}},";
    $js.="plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11}}}}}});";
    $h[]=_chart_panel('fas fa-exchange-alt','Transaction Rates',    $uid,$js,$range);

    // ── 3. Tuple Operations (line) ─────────────────────────────
    $uid="pg-tup-$t";
    $js="new Chart(document.getElementById('$uid'),{type:'line',";
    $js.="data:{labels:$labelsJs,datasets:[";
    $js.="{label:'Fetch/s',data:"._fmt_floats($tupFetch,1).",borderColor:'#2196F3',tension:0.3,pointRadius:1},";
    $js.="{label:'Insert/s',data:"._fmt_floats($tupInsert,1).",borderColor:'#4CAF50',tension:0.3,pointRadius:1},";
    $js.="{label:'Update/s',data:"._fmt_floats($tupUpdate,1).",borderColor:'#FF9800',tension:0.3,pointRadius:1},";
    $js.="{label:'Delete/s',data:"._fmt_floats($tupDelete,1).",borderColor:'#f44336',tension:0.3,pointRadius:1}";
    $js.="]},options:{responsive:true,maintainAspectRatio:false,";
    $js.="scales:{x:{ticks:{maxTicksLimit:12,font:{size:10}}},y:{beginAtZero:true,title:{display:true,text:'rows/s'}}},";
    $js.="plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11}}}}}});";
    $h[]=_chart_panel('fas fa-table','Tuple Operations',            $uid,$js,$range);

    // ── 4. Cache Hit Ratio (with 99% threshold) ────────────────
    $uid="pg-cache-$t";
    $cacheMinVal=count($cacheHit)>0?min(array_merge($cacheHit,$cacheMin)):90;
    $yMin=max(0,floor($cacheMinVal)-2);
    if($yMin>95) $yMin=95;
    $js="new Chart(document.getElementById('$uid'),{type:'line',";
    $js.="data:{labels:$labelsJs,datasets:[";
    $js.="{label:'Cache Hit %',data:"._fmt_floats($cacheHit,2).",borderColor:'#1ab394',backgroundColor:'rgba(26,179,148,0.15)',fill:true,tension:0.3,pointRadius:1}";
    if(!$raw){
        $js.=",{label:'Min Hit %',data:"._fmt_floats($cacheMin,2).",borderColor:'#f8ac59',borderDash:[4,3],tension:0.3,pointRadius:1,fill:false}";
    }
    $js.="]},options:{responsive:true,maintainAspectRatio:false,";
    $js.="scales:{x:{ticks:{maxTicksLimit:12,font:{size:10}}},y:{min:$yMin,max:100,title:{display:true,text:'%'}}},";
    $js.="plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11}}},";
    $js.="annotation:{annotations:{threshold:{type:'line',yMin:99,yMax:99,borderColor:'rgba(237,85,101,0.6)',borderDash:[6,3],borderWidth:1,label:{display:true,content:'99%',position:'start',font:{size:10}}}}}";
    $js.="}}});";
    $h[]=_chart_panel('fas fa-tachometer-alt','{cache} Hit Ratio',  $uid,$js,$range);

    // ── 5. Database Size (filled line) ─────────────────────────
    $uid="pg-size-$t";
    $js="new Chart(document.getElementById('$uid'),{type:'line',";
    $js.="data:{labels:$labelsJs,datasets:[";
    $js.="{label:'Size (MB)',data:"._fmt_floats($dbSize,1).",borderColor:'#ab7df6',backgroundColor:'rgba(171,125,246,0.15)',fill:true,tension:0.3,pointRadius:1}";
    if(!$raw){
        $js.=",{label:'Max (MB)',data:"._fmt_floats($dbSizeMax,1).",borderColor:'#676a6c',borderDash:[4,3],tension:0.3,pointRadius:1,fill:false}";
    }
    $js.="]},options:{responsive:true,maintainAspectRatio:false,";
    $js.="scales:{x:{ticks:{maxTicksLimit:12,font:{size:10}}},y:{beginAtZero:false,title:{display:true,text:'MB'}}},";
    $js.="plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11}}}}}});";
    $h[]=_chart_panel('fas fa-database','{database} {size}',        $uid,$js,$range);

    // ── 6. Locks & Deadlocks ───────────────────────────────────
    $uid="pg-locks-$t";
    $js="new Chart(document.getElementById('$uid'),{type:'line',";
    $js.="data:{labels:$labelsJs,datasets:[";
    $js.="{label:'Waiting Locks',data:"._fmt_floats($wLocks,1).",borderColor:'#FF9800',backgroundColor:'rgba(255,152,0,0.15)',fill:true,tension:0.3,pointRadius:1},";
    $js.="{label:'Deadlocks',data:"._fmt_ints($deadlocks).",borderColor:'#f44336',borderDash:[5,3],tension:0.3,pointRadius:2,fill:false}";
    $js.="]},options:{responsive:true,maintainAspectRatio:false,";
    $js.="scales:{x:{ticks:{maxTicksLimit:12,font:{size:10}}},y:{beginAtZero:true,title:{display:true,text:'count'}}},";
    $js.="plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:11}}}}}});";
    $h[]=_chart_panel('fas fa-lock','{locks} &amp; Deadlocks',     $uid,$js,$range);

    $h[]="</div>"; // close row

    // ── Live mode: append polling ──────────────────────────────
    if($live){
        $h[]="<script>";
        $h[]="var _pgCharts={};";
        // Capture chart instances by UID prefix
        $h[]="(function(){";
        $h[]="  var cs=Object.values(Chart.instances);";
        $h[]="  cs.forEach(function(c){";
        $h[]="    var id=c.canvas.id;";
        $h[]="    if(id.indexOf('pg-conn-')===0) _pgCharts.conn=c;";
        $h[]="    if(id.indexOf('pg-tx-')===0) _pgCharts.tx=c;";
        $h[]="    if(id.indexOf('pg-tup-')===0) _pgCharts.tup=c;";
        $h[]="    if(id.indexOf('pg-cache-')===0) _pgCharts.cache=c;";
        $h[]="    if(id.indexOf('pg-size-')===0) _pgCharts.size=c;";
        $h[]="    if(id.indexOf('pg-locks-')===0) _pgCharts.locks=c;";
        $h[]="  });";
        $h[]="})();";

        $h[]="function _pgAppend(chart,label,vals){";
        $h[]="  if(!chart)return;";
        $h[]="  chart.data.labels.push(label);";
        $h[]="  for(var i=0;i<vals.length;i++){";
        $h[]="    if(chart.data.datasets[i]) chart.data.datasets[i].data.push(vals[i]);";
        $h[]="  }";
        $h[]="  var max=72;"; // 6 hours of 5-min samples
        $h[]="  if(chart.data.labels.length>max){";
        $h[]="    chart.data.labels.shift();";
        $h[]="    chart.data.datasets.forEach(function(ds){ds.data.shift();});";
        $h[]="  }";
        $h[]="  chart.update('none');";
        $h[]="};";

        $h[]="if(typeof _pgLiveTimer!=='undefined'&&_pgLiveTimer){clearInterval(_pgLiveTimer);}";
        $h[]="_pgLiveTimer=setInterval(function(){";
        $h[]="  \$.ajax({url:'$page?live-poll=yes',dataType:'json',success:function(d){";
        $h[]="    if(!d||!d.success||!d.data)return;";
        $h[]="    var p=d.data,lbl=new Date(p.ts*1000).toLocaleTimeString('en',{hour:'2-digit',minute:'2-digit'});";
        $h[]="    _pgAppend(_pgCharts.conn,lbl,[p.active,p.idle,p.idle_tx,p.unknown]);";
        $h[]="    _pgAppend(_pgCharts.tx,lbl,[p.tx_commit_rate,p.tx_rollback_rate]);";
        $h[]="    _pgAppend(_pgCharts.tup,lbl,[p.tup_fetch_rate,p.tup_insert_rate,p.tup_update_rate,p.tup_delete_rate]);";
        $h[]="    _pgAppend(_pgCharts.cache,lbl,[p.cache_hit_pct]);";
        $h[]="    _pgAppend(_pgCharts.size,lbl,[p.db_size_mb]);";
        $h[]="    _pgAppend(_pgCharts.locks,lbl,[p.waiting_locks,p.deadlocks]);";
        $h[]="  }});";
        $h[]="}, 30000);";
        $h[]="</script>";
    }

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Live poll: return latest sample as JSON ─────────────────────

function live_poll():void{
    header("Content-Type: application/json");
    echo $GLOBALS["CLASS_SOCKETS"]->REST_API("/postgresql/metrics/latest");
}
