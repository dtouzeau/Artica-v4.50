<?php
/**
 * Network Interface Metrics Charts (BoltDB-based netif-metrics API)
 * Per-interface bandwidth, packets, errors & drops with period tabs.
 * Uses Chart.js 4.x with category scale (no date adapter).
 *
 * Entry:  Loadjs('fw.netagents.network.ifaces.metrics.php?netif-js={agent_id}')
 * API:    GET /netagents/netif-metrics/{id}/interfaces
 *         GET /netagents/netif-metrics/{id}/metrics?interface=X&period=Y
 * Patch 2026-18-03
 */
include_once(dirname(__FILE__).'/ressources/class.template-admin.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();
if(!$users->AsSystemAdministrator){exit();}

if(isset($_GET["netif-js"]))           {js();                exit;}
if(isset($_GET["tabs"]))               {tabs();              exit;}
if(isset($_GET["charts"]))             {charts();            exit;}
js();

// ── Helpers ─────────────────────────────────────────────────────

function aid():int{
    return intval($_GET["agent_id"]??$_GET["netif-js"]??0);
}

function valid_period(string $p):string{
    $ok=["last-hour","today","this-week","last-7-days","this-month","last-30-days"];
    return in_array($p,$ok)?$p:"last-hour";
}

/** Period string sanitized for use in DOM element IDs. */
function period_key(string $p):string{
    return str_replace('-','',$p);
}

// ── Dialog opener ───────────────────────────────────────────────

function js():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["netif-js"]??0);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/get/$id"));
    $hostname=(is_object($json)&&!empty($json->hostname))
        ?htmlspecialchars($json->hostname):"Agent #$id";

    $tpl->js_dialog7(
        "<i class='fas fa-chart-area'></i> #$id: $hostname — {network_metrics}",
        "$page?tabs=yes&agent_id=$id",1050
    );
}

// ── Period tabs ─────────────────────────────────────────────────

function tabs():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=aid();

    // Popup shell: container div for charts content
    echo "<div id='netif-content-$id'></div>";
    echo "<script>LoadAjax('netif-content-$id','$page?charts=yes&agent_id=$id&period=last-hour');</script>";
}

// ── Charts (interface selector + chart rendering in one response) ──

function charts():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=aid();
    $period=valid_period($_GET["period"]??"last-hour");
    $pk=period_key($period);
    $sel_iface=$_GET["interface"]??"";

    // ── Fetch tracked interfaces ──
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/netif-metrics/$id/interfaces");
    $json=json_decode($raw);

    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        $err=is_object($json)?htmlspecialchars($json->Error??"{error}"):"{failed_to_contact_agent}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    $ifaces=$json->interfaces??[];
    if(empty($ifaces)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_interfaces_found}"));
        return;
    }

    // Default to first interface
    if(empty($sel_iface)){$sel_iface=$ifaces[0];}

    $h=[];

    // ── Period buttons ──
    $h[]="<div style='margin-bottom:12px;margin-top:8px'>";
    $periods=["last-hour"=>"{last_hour}","today"=>"{today}","this-week"=>"{this_week}","last-7-days"=>"{last_7_days}","this-month"=>"{this_month}"];
    foreach($periods as $pval=>$plabel){
        $active=($pval===$period)?"btn-primary":"btn-white";
        $h[]="<button type='button' class='btn btn-sm $active' onclick=\"netifPeriod_$id('$pval')\">$plabel</button>";
    }
    $h[]="</div>";

    // ── Interface selector row ──
    $options="";
    foreach($ifaces as $name){
        $esc=htmlspecialchars($name);
        $selected=($name===$sel_iface)?"selected":"";
        $options.="<option value='$esc' $selected>$esc</option>";
    }

    $sel_safe=htmlspecialchars($sel_iface);
    $iface_encoded=urlencode($sel_iface);

    $h[]="<div style='padding:8px 12px;margin-bottom:12px;background:#f9f9f9;border:1px solid #e7eaec;border-radius:3px'>";
    $h[]="  <div class='row'>";
    $h[]="    <div class='col-md-4'>";
    $h[]="      <label style='font-size:11px;color:#999;text-transform:uppercase;margin-bottom:3px'>";
    $h[]="        <i class='fas fa-network-wired'></i> {select_interface}</label>";
    $h[]="      <select id='netif-sel-$id' class='form-control' onchange=\"netifReload_$id()\">";
    $h[]=$options;
    $h[]="      </select>";
    $h[]="    </div>";
    $h[]="    <div class='col-md-8' style='padding-top:22px'>";

    // ── Fetch metrics ──
    $metrics_raw=$GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/netagents/netif-metrics/$id/metrics?interface=$iface_encoded&period=$period"
    );
    $mj=json_decode($metrics_raw);

    // Link state badge (from raw data)
    $resolution="";
    if(is_object($mj)){
        $resolution=$mj->resolution??"";
    }
    $is_raw=($resolution==="5min");

    if($is_raw&&!empty($mj->raw)){
        $last=end($mj->raw);
        if(is_object($last)){
            $oper=!empty($last->oper_up);
            $carrier=!empty($last->carrier_up);
            $operCls=$oper?"label-primary":"label-danger";
            $operTxt=$oper?"UP":"DOWN";
            $carrCls=$carrier?"label-primary":"label-warning";
            $carrTxt=$carrier?"CARRIER":"NO CARRIER";
            $h[]="<span class='label $operCls' style='color:#fff;font-size:12px;padding:4px 10px'>$operTxt</span> ";
            $h[]="<span class='label $carrCls' style='color:#fff;font-size:12px;padding:4px 10px'>$carrTxt</span>";
        }
    }elseif(!empty($resolution)){
        $h[]="<span class='label label-default' style='color:#fff;font-size:11px;padding:3px 8px'>".htmlspecialchars($resolution)."</span>";
    }
    $h[]=" &nbsp; <span style='color:#999;font-size:11px'>$sel_safe</span>";

    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="</div>";

    // ── Build chart data arrays ──
    $labels=[];
    $rx_bw=[];$tx_bw=[];
    $rx_bw_max=[];$tx_bw_max=[];
    $rx_pps=[];$tx_pps=[];
    $rx_pps_max=[];$tx_pps_max=[];
    $rx_err=[];$tx_err=[];
    $rx_drop=[];$tx_drop=[];
    $has_metrics=false;

    if(is_object($mj)&&(!isset($mj->Status)||$mj->Status!==false)){
        if($is_raw&&!empty($mj->raw)){
            $has_metrics=true;
            $date_fmt="H:i";

            foreach($mj->raw as $pt){
                $ts=intval($pt->time??0);
                $labels[]=($ts>0)?date($date_fmt,$ts):"";
                $rx_bw[]=round(($pt->rx_bps??0)/1024,2);
                $tx_bw[]=round(($pt->tx_bps??0)/1024,2);
                $rx_pps[]=intval($pt->rx_pps??0);
                $tx_pps[]=intval($pt->tx_pps??0);
                $rx_err[]=round(($pt->rx_err_rate??0)/1000,3);
                $tx_err[]=round(($pt->tx_err_rate??0)/1000,3);
                $rx_drop[]=round(($pt->rx_drop_rate??0)/1000,3);
                $tx_drop[]=round(($pt->tx_drop_rate??0)/1000,3);
            }
        }elseif(!$is_raw&&!empty($mj->rollups)){
            $has_metrics=true;
            $date_fmt=($resolution==="hourly")?"M d H:00":"M d";

            foreach($mj->rollups as $pt){
                $ts=intval($pt->time??0);
                $labels[]=($ts>0)?date($date_fmt,$ts):"";
                $rx_bw[]=round(($pt->rx_bps_avg??0)/1024,2);
                $tx_bw[]=round(($pt->tx_bps_avg??0)/1024,2);
                $rx_bw_max[]=round(($pt->rx_bps_max??0)/1024,2);
                $tx_bw_max[]=round(($pt->tx_bps_max??0)/1024,2);
                $rx_pps[]=intval($pt->rx_pps_avg??0);
                $tx_pps[]=intval($pt->tx_pps_avg??0);
                $rx_pps_max[]=intval($pt->rx_pps_max??0);
                $tx_pps_max[]=intval($pt->tx_pps_max??0);
                $rx_err[]=round(($pt->rx_err_total??0)/1000,2);
                $tx_err[]=round(($pt->tx_err_total??0)/1000,2);
                $rx_drop[]=round(($pt->rx_drop_total??0)/1000,2);
                $tx_drop[]=round(($pt->tx_drop_total??0)/1000,2);
            }
        }
    }

    if(!$has_metrics||empty($labels)){
        $h[]="<div class='alert alert-info' style='margin-top:15px'>";
        $h[]="  <i class='fas fa-info-circle'></i>&nbsp;&nbsp;{no_data}";
        $h[]="</div>";
        $h[]=build_nav_js($id,$page,$period);
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    // ── Auto-scale bandwidth unit ──
    $unit="KB/s";
    $all_bw=array_merge($rx_bw,$tx_bw);
    if(!empty($rx_bw_max)){$all_bw=array_merge($all_bw,$rx_bw_max,$tx_bw_max);}
    $maxBw=!empty($all_bw)?max($all_bw):0;

    if($maxBw>1024){
        $unit="MB/s";
        $rx_bw=array_map(fn($v)=>round($v/1024,2),$rx_bw);
        $tx_bw=array_map(fn($v)=>round($v/1024,2),$tx_bw);
        if(!empty($rx_bw_max)){
            $rx_bw_max=array_map(fn($v)=>round($v/1024,2),$rx_bw_max);
            $tx_bw_max=array_map(fn($v)=>round($v/1024,2),$tx_bw_max);
        }
    }

    // ── Render charts HTML + script ──
    $h[]=build_charts($labels,$rx_bw,$tx_bw,$rx_bw_max,$tx_bw_max,
        $rx_pps,$tx_pps,$rx_pps_max,$tx_pps_max,
        $rx_err,$tx_err,$rx_drop,$tx_drop,$unit,$is_raw);

    $h[]=build_nav_js($id,$page,$period);
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Build Charts HTML + inline Chart.js ─────────────────────────

function build_charts(
    array $labels,array $rx_bw,array $tx_bw,array $rx_bw_max,array $tx_bw_max,
    array $rx_pps,array $tx_pps,array $rx_pps_max,array $tx_pps_max,
    array $rx_err,array $tx_err,array $rx_drop,array $tx_drop,
    string $unit,bool $is_raw
):string{
    $t=time();

    // JSON encode with controlled float precision (avoid 50-digit php serialize_precision floats)
    $fmtF=fn(array $a,int $d=2):string=>'['.implode(',',array_map(fn($v)=>sprintf("%.".$d."f",$v),$a)).']';
    $fmtI=fn(array $a):string=>'['.implode(',',array_map('intval',$a)).']';
    $labelsJs=json_encode($labels);
    $rxBwJs=$fmtF($rx_bw);
    $txBwJs=$fmtF($tx_bw);
    $rxBwMaxJs=$fmtF($rx_bw_max);
    $txBwMaxJs=$fmtF($tx_bw_max);
    $rxPpsJs=$fmtI($rx_pps);
    $txPpsJs=$fmtI($tx_pps);
    $rxPpsMaxJs=$fmtI($rx_pps_max);
    $txPpsMaxJs=$fmtI($tx_pps_max);
    $rxErrJs=$fmtF($rx_err,3);
    $txErrJs=$fmtF($tx_err,3);
    $rxDropJs=$fmtF($rx_drop,3);
    $txDropJs=$fmtF($tx_drop,3);

    $hasErrors=(array_sum($rx_err)+array_sum($tx_err)+array_sum($rx_drop)+array_sum($tx_drop))>0;

    $h=[];

    // ── Bandwidth chart (350px) ──
    $h[]="<div class='ibox'>";
    $h[]="  <div class='ibox-title'><h5><i class='fas fa-tachometer-alt' style='color:#1c84c6'></i>&nbsp; {bandwidth}</h5></div>";
    $h[]="  <div class='ibox-content'><div style='height:350px'><canvas id='nif-bw-$t'></canvas></div></div>";
    $h[]="</div>";

    // ── Packets + Errors side by side (250px) ──
    $h[]="<div class='row'>";
    $h[]="  <div class='col-md-6'>";
    $h[]="    <div class='ibox'>";
    $h[]="      <div class='ibox-title'><h5><i class='fas fa-stream' style='color:#ab7df6'></i>&nbsp; {packets}</h5></div>";
    $h[]="      <div class='ibox-content'><div style='height:250px'><canvas id='nif-pkt-$t'></canvas></div></div>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='col-md-6'>";
    $h[]="    <div class='ibox'>";
    $h[]="      <div class='ibox-title'><h5><i class='fas fa-exclamation-triangle' style='color:#ed5565'></i>&nbsp; {errors} &amp; Drops</h5></div>";
    $h[]="      <div class='ibox-content'><div style='height:250px'>";
    if($hasErrors){
        $h[]="<canvas id='nif-err-$t'></canvas>";
    }else{
        $h[]="<div style='text-align:center;padding:60px 10px;color:#999'>";
        $h[]="  <i class='fas fa-check-circle' style='color:#1ab394;font-size:28px'></i><br><br>";
        $h[]="  {no_data}</div>";
    }
    $h[]="</div></div></div></div></div>";

    // ── Chart.js script ──
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";
    $h[]="<script>";

    // Shared options
    $h[]="var _nifOpts=function(yLabel){return{";
    $h[]="  responsive:true,maintainAspectRatio:false,";
    $h[]="  interaction:{mode:'index',intersect:false},";
    $h[]="  scales:{x:{ticks:{maxTicksLimit:20,font:{size:10}}},y:{beginAtZero:true,title:{display:!!yLabel,text:yLabel||''}}},";
    $h[]="  elements:{point:{radius:1,hoverRadius:4},line:{tension:0.3,borderWidth:2}},";
    $h[]="  plugins:{legend:{position:'top',labels:{usePointStyle:true,padding:12}},tooltip:{mode:'index',intersect:false}}";
    $h[]="};};";

    // Bandwidth chart
    $h[]="(function(){";
    $h[]="var ds=[";
    $h[]="  {label:'RX ($unit)',data:$rxBwJs,borderColor:'rgb(28,132,198)',backgroundColor:'rgba(28,132,198,0.08)',fill:true},";
    $h[]="  {label:'TX ($unit)',data:$txBwJs,borderColor:'rgb(248,172,89)',backgroundColor:'rgba(248,172,89,0.08)',fill:true}";
    if(!$is_raw&&!empty($rx_bw_max)){
        $h[]=",{label:'RX Peak ($unit)',data:$rxBwMaxJs,borderColor:'rgba(28,132,198,0.4)',borderDash:[5,5],fill:false,borderWidth:1,pointRadius:0}";
        $h[]=",{label:'TX Peak ($unit)',data:$txBwMaxJs,borderColor:'rgba(248,172,89,0.4)',borderDash:[5,5],fill:false,borderWidth:1,pointRadius:0}";
    }
    $h[]="];";
    $h[]="new Chart(document.getElementById('nif-bw-$t'),{type:'line',data:{labels:$labelsJs,datasets:ds},options:_nifOpts('$unit')});";
    $h[]="})();";

    // Packets chart
    $h[]="(function(){";
    $h[]="var ds=[";
    $h[]="  {label:'RX pkt/s',data:$rxPpsJs,borderColor:'rgb(54,162,235)',backgroundColor:'rgba(54,162,235,0.05)',fill:false},";
    $h[]="  {label:'TX pkt/s',data:$txPpsJs,borderColor:'rgb(171,125,246)',backgroundColor:'rgba(171,125,246,0.05)',fill:false}";
    if(!$is_raw&&!empty($rx_pps_max)){
        $h[]=",{label:'RX Peak pkt/s',data:$rxPpsMaxJs,borderColor:'rgba(54,162,235,0.4)',borderDash:[5,5],fill:false,borderWidth:1,pointRadius:0}";
        $h[]=",{label:'TX Peak pkt/s',data:$txPpsMaxJs,borderColor:'rgba(171,125,246,0.4)',borderDash:[5,5],fill:false,borderWidth:1,pointRadius:0}";
    }
    $h[]="];";
    $h[]="new Chart(document.getElementById('nif-pkt-$t'),{type:'line',data:{labels:$labelsJs,datasets:ds},options:_nifOpts('pkt/s')});";
    $h[]="})();";

    // Errors chart (only if data)
    if($hasErrors){
        $errType=$is_raw?"line":"bar";
        $errUnit=$is_raw?"rate/s":"total";
        $fill=$is_raw?",fill:false":"";

        $h[]="(function(){";
        $h[]="var opts=_nifOpts('');";
        if(!$is_raw){$h[]="opts.scales.x.stacked=true;opts.scales.y.stacked=true;";}
        $h[]="new Chart(document.getElementById('nif-err-$t'),{type:'$errType',data:{labels:$labelsJs,datasets:[";
        $h[]="  {label:'RX Errors ($errUnit)',data:$rxErrJs,borderColor:'rgb(237,85,101)',backgroundColor:'rgba(237,85,101,0.3)'$fill},";
        $h[]="  {label:'TX Errors ($errUnit)',data:$txErrJs,borderColor:'rgb(248,172,89)',backgroundColor:'rgba(248,172,89,0.3)'$fill},";
        $h[]="  {label:'RX Drops ($errUnit)',data:$rxDropJs,borderColor:'rgb(201,203,207)',backgroundColor:'rgba(201,203,207,0.3)'$fill},";
        $h[]="  {label:'TX Drops ($errUnit)',data:$txDropJs,borderColor:'rgb(255,205,86)',backgroundColor:'rgba(255,205,86,0.3)'$fill}";
        $h[]="]},options:opts});";
        $h[]="})();";
    }

    $h[]="</script>";
    return implode("\n",$h);
}

// ── Navigation JS ───────────────────────────────────────────────

function build_nav_js(int $id,string $page,string $period):string{
    $h=[];
    $h[]="<script>";
    $h[]="function netifReload_$id(){";
    $h[]="  var iface=document.getElementById('netif-sel-$id').value;";
    $h[]="  LoadAjax('netif-content-$id','$page?charts=yes&agent_id=$id&period=$period&interface='+encodeURIComponent(iface));";
    $h[]="}";
    $h[]="function netifPeriod_$id(p){";
    $h[]="  var iface=document.getElementById('netif-sel-$id').value;";
    $h[]="  LoadAjax('netif-content-$id','$page?charts=yes&agent_id=$id&period='+p+'&interface='+encodeURIComponent(iface));";
    $h[]="}";
    $h[]="</script>";
    return implode("\n",$h);
}
