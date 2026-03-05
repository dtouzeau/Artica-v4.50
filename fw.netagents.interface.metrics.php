<?php
// Agent Network Interface Metrics Charts
// Displays bandwidth, packets, errors & dropped charts per interface using Chart.js
//
// Entry points:
//   ?agent-interface-metrics-js={id}  → opens dialog
//   ?popup=yes&agent_id={id}          → dialog shell with auto-load
//   ?charts=yes&agent_id={id}&interface=X&range=Y → chart content (AJAX)
//
// API Endpoints:
//   GET /netagents/interfaces/details/{id}  → interface list (cached 2h)
//   GET /netagents/interfaces/{id}/metrics/{interface}?range=hour|day|week|month&aggregated=1

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}

if(isset($_GET["charts"])){display_charts();exit;}
if(isset($_GET["popup"])){popup_shell();exit;}
js();

// ─── Open the dialog ───
function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["agent-interface-metrics-js"]);
    $AgentObj=new ArticaNetAgents($id);
    $hostname=$AgentObj->GetAgentHostname();
    if(strlen($hostname)<1){$hostname="#$id";}
    return $tpl->js_dialog3("<i class='".ico_speed."'></i> $hostname — {interface} {statistics}","$page?popup=yes&agent_id=$id",1050);
}

// ─── Dialog shell: container div + initial load ───
function popup_shell():bool{
    $page=CurrentPageName();
    $agent_id=intval($_GET["agent_id"]??0);
    if($agent_id<=0){
        $tpl=new template_admin();
        echo $tpl->div_error("Invalid agent ID");
        return false;
    }
    echo "<div id='ifmetrics-content'></div>";
    echo "<script>LoadAjax('ifmetrics-content','$page?charts=yes&agent_id=$agent_id&range=day');</script>";
    return true;
}

// ─── Main chart content (loaded via AJAX) ───
function display_charts():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $agent_id=intval($_GET["agent_id"]??0);
    $range=$_GET["range"]??"day";
    $sel_iface=$_GET["interface"]??"";

    if(!in_array($range,["hour","day","week","month"])){$range="day";}

    // ── Fetch interface list ──
    $ifaces_raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/interfaces/details/$agent_id");
    $ifaces_json=json_decode($ifaces_raw,true);
    $interfaces=array();
    if(is_array($ifaces_json)&&isset($ifaces_json["interfaces"])){
        foreach($ifaces_json["interfaces"] as $iface){
            $n=$iface["name"]??"";
            if($n==="lo"){continue;}
            $interfaces[]=$iface;
        }
    }

    // Fallback: use /netagents/interfaces/{id} if details cache not ready
    if(count($interfaces)===0){
        $list_raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/interfaces/$agent_id");
        $list_json=json_decode($list_raw,true);
        if(is_array($list_json)&&isset($list_json["interfaces"])){
            foreach($list_json["interfaces"] as $iface){
                $n=$iface["name"]??"";
                if($n==="lo"){continue;}
                $interfaces[]=$iface;
            }
        }
    }

    if(count($interfaces)===0){
        echo $tpl->div_warning("{no_data} — {interface} metrics are collected every 5 minutes.");
        return true;
    }

    // Default to first interface
    if(empty($sel_iface)){$sel_iface=$interfaces[0]["name"];}

    // ── Current interface details ──
    $cur=null;
    foreach($interfaces as $iface){
        if($iface["name"]===$sel_iface){$cur=$iface;break;}
    }

    // ── Fetch metrics ──
    $iface_encoded=urlencode($sel_iface);
    $metrics_raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/interfaces/$agent_id/metrics/$iface_encoded?range=$range&aggregated=1");
    $metrics_json=json_decode($metrics_raw,true);
    $metrics=array();
    $count=0;
    if(is_array($metrics_json)&&isset($metrics_json["metrics"])){
        $metrics=$metrics_json["metrics"];
        $count=intval($metrics_json["count"]??count($metrics));
    }

    $sel_safe=htmlspecialchars($sel_iface);
    $f=array();

    // ── Interface selector + range buttons row ──
    $f[]="<div class='row' style='margin-bottom:15px'>";
    $f[]="  <div class='col-md-5'>";
    $f[]="    <label style='font-size:12px;color:#888;margin-bottom:3px'><i class='".ico_nic."'></i> {interface}</label>";
    $f[]="    <select id='ifmetrics-iface-sel' class='form-control' onchange=\"ifmetricsReload()\">";
    foreach($interfaces as $iface){
        $n=htmlspecialchars($iface["name"]);
        $selected=($iface["name"]===$sel_iface)?"selected":"";
        $mac=!empty($iface["mac_address"])?" (".$iface["mac_address"].")":"";
        $st=strtoupper($iface["oper_state"]??"");
        $f[]="      <option value='$n' $selected>$n$mac — $st</option>";
    }
    $f[]="    </select>";
    $f[]="  </div>";
    $f[]="  <div class='col-md-7' style='padding-top:22px'>";
    foreach(["hour"=>"{last_hour}","day"=>"{last_24_hours}","week"=>"{last_week}","month"=>"{last_month}"] as $r=>$label){
        $active=($r===$range)?"btn-primary":"btn-white";
        $f[]="    <button type='button' class='btn btn-sm $active' onclick=\"ifmetricsRange('$r')\">$label</button>";
    }
    $f[]="    <span class='label label-default' style='margin-left:10px'>$count {records}</span>";
    $f[]="  </div>";
    $f[]="</div>";

    // ── Interface detail strip ──
    if($cur){
        $f[]=build_interface_strip($cur);
    }

    // ── Charts ──
    if(count($metrics)===0){
        $f[]="<div class='alert alert-info' style='margin-top:15px'>";
        $f[]="  <i class='".ico_infoi."'></i>&nbsp;&nbsp;";
        $f[]="  {no_data} — <strong>$sel_safe</strong>. Metrics are collected every 5 minutes.";
        $f[]="</div>";
    }else{
        $f[]=build_charts_html($metrics,$range,$sel_safe,$agent_id,$page);
    }

    // ── Navigation JS ──
    $f[]="<script>";
    $f[]="function ifmetricsReload(){";
    $f[]="  var iface=document.getElementById('ifmetrics-iface-sel').value;";
    $f[]="  LoadAjax('ifmetrics-content','$page?charts=yes&agent_id=$agent_id&interface='+encodeURIComponent(iface)+'&range=$range');";
    $f[]="}";
    $f[]="function ifmetricsRange(r){";
    $f[]="  var iface=document.getElementById('ifmetrics-iface-sel').value;";
    $f[]="  LoadAjax('ifmetrics-content','$page?charts=yes&agent_id=$agent_id&interface='+encodeURIComponent(iface)+'&range='+r);";
    $f[]="}";
    $f[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}

// ─── Interface detail strip (compact info bar) ───
function build_interface_strip(array $iface):string{
    $h=array();
    $name=htmlspecialchars($iface["name"]);
    $state=strtolower($iface["oper_state"]??"unknown");
    $state_class=($state==="up")?"primary":(($state==="down")?"danger":"warning");

    $h[]="<div style='background:#f9f9f9;border:1px solid #e7eaec;border-radius:3px;padding:10px 15px;margin-bottom:15px'>";
    $h[]="  <div class='row'>";

    // State
    $h[]="    <div class='col-md-2 col-sm-4' style='margin-bottom:5px'>";
    $h[]="      <span style='color:#999;font-size:10px;text-transform:uppercase'>{status}</span><br>";
    $h[]="      <span class='label label-$state_class'>".strtoupper($state)."</span>";
    $h[]="    </div>";

    // MAC
    $mac=$iface["mac_address"]??"";
    if(!empty($mac)){
        $h[]="    <div class='col-md-2 col-sm-4' style='margin-bottom:5px'>";
        $h[]="      <span style='color:#999;font-size:10px;text-transform:uppercase'>MAC</span><br>";
        $h[]="      <span style='font-family:monospace;font-size:12px'>".htmlspecialchars($mac)."</span>";
        $h[]="    </div>";
    }

    // Speed
    $speed=$iface["speed"]??"";
    if(!empty($speed)){
        $h[]="    <div class='col-md-2 col-sm-4' style='margin-bottom:5px'>";
        $h[]="      <span style='color:#999;font-size:10px;text-transform:uppercase'>{speed}</span><br>";
        $h[]="      <strong>".htmlspecialchars($speed)."</strong>";
        $h[]="    </div>";
    }

    // MTU
    $mtu=intval($iface["mtu"]??0);
    if($mtu>0){
        $h[]="    <div class='col-md-1 col-sm-4' style='margin-bottom:5px'>";
        $h[]="      <span style='color:#999;font-size:10px;text-transform:uppercase'>MTU</span><br>";
        $h[]="      <strong>$mtu</strong>";
        $h[]="    </div>";
    }

    // Driver
    $driver=$iface["driver"]??"";
    if(!empty($driver)){
        $h[]="    <div class='col-md-2 col-sm-4' style='margin-bottom:5px'>";
        $h[]="      <span style='color:#999;font-size:10px;text-transform:uppercase'>{driver}</span><br>";
        $h[]="      ".htmlspecialchars($driver);
        $h[]="    </div>";
    }

    // IPs
    $ips=array();
    if(!empty($iface["ipv4_addresses"])){
        foreach($iface["ipv4_addresses"] as $addr){
            $ips[]="<span class='label' style='background:#1ab394;color:#fff;margin:1px;font-size:11px;font-family:monospace'>".htmlspecialchars($addr["cidr"]??"")."</span>";
        }
    }
    if(count($ips)>0){
        $h[]="    <div class='col-md-3 col-sm-4' style='margin-bottom:5px'>";
        $h[]="      <span style='color:#999;font-size:10px;text-transform:uppercase'>IPv4</span><br>";
        $h[]="      ".implode(" ",$ips);
        $h[]="    </div>";
    }

    $h[]="  </div>";
    $h[]="</div>";
    return implode("\n",$h);
}

// ─── Build Chart.js HTML + script ───
function build_charts_html(array $metrics,string $range,string $iface_safe,int $agent_id,string $page):string{
    $labels=array();
    $rx_bytes_rate=array();
    $tx_bytes_rate=array();
    $rx_packets_rate=array();
    $tx_packets_rate=array();
    $rx_errors=array();
    $tx_errors=array();
    $rx_dropped=array();
    $tx_dropped=array();

    // Date format based on range
    $date_fmt=($range==="hour")?"H:i":(($range==="month")?"m/d H:i":(($range==="week")?"D H:i":"H:i"));

    $total_rx=0;$total_tx=0;$peak_rx=0;$peak_tx=0;
    foreach($metrics as $point){
        $ts=strtotime($point["timestamp"]??"");
        $labels[]=($ts>0)?date($date_fmt,$ts):"";
        $rx=round(floatval($point["rx_bytes_rate"]??0),2);
        $tx=round(floatval($point["tx_bytes_rate"]??0),2);
        $rx_bytes_rate[]=$rx;
        $tx_bytes_rate[]=$tx;
        $rx_packets_rate[]=round(floatval($point["rx_packet_rate"]??0),2);
        $tx_packets_rate[]=round(floatval($point["tx_packet_rate"]??0),2);
        $rx_errors[]=intval($point["rx_errors"]??0);
        $tx_errors[]=intval($point["tx_errors"]??0);
        $rx_dropped[]=intval($point["rx_dropped"]??0);
        $tx_dropped[]=intval($point["tx_dropped"]??0);
        $total_rx+=intval($point["rx_bytes"]??0);
        $total_tx+=intval($point["tx_bytes"]??0);
        if($rx>$peak_rx){$peak_rx=$rx;}
        if($tx>$peak_tx){$peak_tx=$tx;}
    }

    $labels_json=json_encode($labels);
    $rx_bytes_json=json_encode($rx_bytes_rate);
    $tx_bytes_json=json_encode($tx_bytes_rate);
    $rx_pkts_json=json_encode($rx_packets_rate);
    $tx_pkts_json=json_encode($tx_packets_rate);
    $rx_err_json=json_encode($rx_errors);
    $tx_err_json=json_encode($tx_errors);
    $rx_drop_json=json_encode($rx_dropped);
    $tx_drop_json=json_encode($tx_dropped);

    // Summary totals
    $total_rx_fmt=FormatBytes($total_rx/1024);
    $total_tx_fmt=FormatBytes($total_tx/1024);
    $peak_rx_fmt=format_rate($peak_rx);
    $peak_tx_fmt=format_rate($peak_tx);

    // Has any errors/drops?
    $has_errors=(array_sum($rx_errors)+array_sum($tx_errors)+array_sum($rx_dropped)+array_sum($tx_dropped))>0;

    $h=array();

    // ── Summary widgets ──
    $h[]="<div class='row' style='margin-bottom:15px'>";
    $h[]="  <div class='col-md-3 col-sm-6'>";
    $h[]="    <div style='text-align:center;padding:10px;background:#f8f8f8;border-radius:3px'>";
    $h[]="      <div style='color:#1c84c6;font-size:22px;font-weight:700'>$total_rx_fmt</div>";
    $h[]="      <div style='color:#999;font-size:11px;text-transform:uppercase'><i class='".ico_download."'></i> {total} RX</div>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='col-md-3 col-sm-6'>";
    $h[]="    <div style='text-align:center;padding:10px;background:#f8f8f8;border-radius:3px'>";
    $h[]="      <div style='color:#f8ac59;font-size:22px;font-weight:700'>$total_tx_fmt</div>";
    $h[]="      <div style='color:#999;font-size:11px;text-transform:uppercase'><i class='".ico_upload."'></i> {total} TX</div>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='col-md-3 col-sm-6'>";
    $h[]="    <div style='text-align:center;padding:10px;background:#f8f8f8;border-radius:3px'>";
    $h[]="      <div style='color:#1c84c6;font-size:22px;font-weight:700'>$peak_rx_fmt</div>";
    $h[]="      <div style='color:#999;font-size:11px;text-transform:uppercase'><i class='".ico_speed."'></i> Peak RX</div>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='col-md-3 col-sm-6'>";
    $h[]="    <div style='text-align:center;padding:10px;background:#f8f8f8;border-radius:3px'>";
    $h[]="      <div style='color:#f8ac59;font-size:22px;font-weight:700'>$peak_tx_fmt</div>";
    $h[]="      <div style='color:#999;font-size:11px;text-transform:uppercase'><i class='".ico_speed."'></i> Peak TX</div>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="</div>";

    // ── Bandwidth Chart ──
    $h[]="<div class='ibox'>";
    $h[]="  <div class='ibox-title'><h5><i class='".ico_speed."' style='color:#1c84c6'></i>&nbsp;&nbsp;{bandwidth}</h5></div>";
    $h[]="  <div class='ibox-content'>";
    $h[]="    <div style='height:280px'><canvas id='ifm-bw-chart'></canvas></div>";
    $h[]="  </div>";
    $h[]="</div>";

    // ── Packets Chart ──
    $h[]="<div class='ibox'>";
    $h[]="  <div class='ibox-title'><h5><i class='".ico_networks."' style='color:#ab7df6'></i>&nbsp;&nbsp;{packets}</h5></div>";
    $h[]="  <div class='ibox-content'>";
    $h[]="    <div style='height:220px'><canvas id='ifm-pkt-chart'></canvas></div>";
    $h[]="  </div>";
    $h[]="</div>";

    // ── Errors & Dropped Chart (only if there's data) ──
    if($has_errors){
        $h[]="<div class='ibox'>";
        $h[]="  <div class='ibox-title'><h5><i class='fas fa-exclamation-triangle' style='color:#ed5565'></i>&nbsp;&nbsp;{errors} &amp; Dropped</h5></div>";
        $h[]="  <div class='ibox-content'>";
        $h[]="    <div style='height:220px'><canvas id='ifm-err-chart'></canvas></div>";
        $h[]="  </div>";
        $h[]="</div>";
    }else{
        $h[]="<div class='text-center' style='padding:8px;color:#999'>";
        $h[]="  <i class='".ico_check."' style='color:#1ab394'></i>&nbsp;&nbsp;No errors or dropped packets in this period";
        $h[]="</div>";
    }

    // ── Chart.js Script ──
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";
    $h[]="<script>";

    // formatBytes JS helper
    $h[]="function ifmFmtBytes(b){";
    $h[]="  if(b>=1073741824) return (b/1073741824).toFixed(2)+' GB/s';";
    $h[]="  if(b>=1048576) return (b/1048576).toFixed(2)+' MB/s';";
    $h[]="  if(b>=1024) return (b/1024).toFixed(2)+' KB/s';";
    $h[]="  return b.toFixed(0)+' B/s';";
    $h[]="}";

    // Common chart options
    $h[]="var ifmBaseOpts={";
    $h[]="  responsive:true,maintainAspectRatio:false,";
    $h[]="  interaction:{mode:'index',intersect:false},";
    $h[]="  scales:{x:{display:true,ticks:{maxTicksLimit:20,font:{size:10}}},y:{beginAtZero:true}},";
    $h[]="  elements:{point:{radius:1,hoverRadius:4},line:{tension:0.3,borderWidth:2}},";
    $h[]="  plugins:{legend:{position:'top',labels:{usePointStyle:true,padding:15}}}";
    $h[]="};";

    // Bandwidth chart
    $h[]="(function(){";
    $h[]="var opts=JSON.parse(JSON.stringify(ifmBaseOpts));";
    $h[]="opts.scales.y.ticks={callback:function(v){return ifmFmtBytes(v);}};";
    $h[]="opts.plugins.tooltip={callbacks:{label:function(ctx){return ctx.dataset.label+': '+ifmFmtBytes(ctx.parsed.y);}}};";
    $h[]="new Chart(document.getElementById('ifm-bw-chart'),{";
    $h[]="  type:'line',";
    $h[]="  data:{labels:$labels_json,datasets:[";
    $h[]="    {label:'RX (Download)',data:$rx_bytes_json,borderColor:'rgb(28,132,198)',backgroundColor:'rgba(28,132,198,0.08)',fill:true},";
    $h[]="    {label:'TX (Upload)',data:$tx_bytes_json,borderColor:'rgb(248,172,89)',backgroundColor:'rgba(248,172,89,0.08)',fill:true}";
    $h[]="  ]},options:opts";
    $h[]="});";
    $h[]="})();";

    // Packets chart
    $h[]="(function(){";
    $h[]="var opts=JSON.parse(JSON.stringify(ifmBaseOpts));";
    $h[]="opts.plugins.tooltip={callbacks:{label:function(ctx){return ctx.dataset.label+': '+ctx.parsed.y.toFixed(1)+' pkt/s';}}};";
    $h[]="new Chart(document.getElementById('ifm-pkt-chart'),{";
    $h[]="  type:'line',";
    $h[]="  data:{labels:$labels_json,datasets:[";
    $h[]="    {label:'RX Packets',data:$rx_pkts_json,borderColor:'rgb(54,162,235)',backgroundColor:'rgba(54,162,235,0.05)',fill:false},";
    $h[]="    {label:'TX Packets',data:$tx_pkts_json,borderColor:'rgb(171,125,246)',backgroundColor:'rgba(171,125,246,0.05)',fill:false}";
    $h[]="  ]},options:opts";
    $h[]="});";
    $h[]="})();";

    // Errors chart (only if data exists)
    if($has_errors){
        $h[]="(function(){";
        $h[]="var opts=JSON.parse(JSON.stringify(ifmBaseOpts));";
        $h[]="new Chart(document.getElementById('ifm-err-chart'),{";
        $h[]="  type:'line',";
        $h[]="  data:{labels:$labels_json,datasets:[";
        $h[]="    {label:'RX Errors',data:$rx_err_json,borderColor:'rgb(237,85,101)',backgroundColor:'rgba(237,85,101,0.08)',fill:false},";
        $h[]="    {label:'TX Errors',data:$tx_err_json,borderColor:'rgb(248,172,89)',backgroundColor:'rgba(248,172,89,0.08)',fill:false},";
        $h[]="    {label:'RX Dropped',data:$rx_drop_json,borderColor:'rgb(201,203,207)',backgroundColor:'rgba(201,203,207,0.08)',fill:false},";
        $h[]="    {label:'TX Dropped',data:$tx_drop_json,borderColor:'rgb(255,205,86)',backgroundColor:'rgba(255,205,86,0.08)',fill:false}";
        $h[]="  ]},options:opts";
        $h[]="});";
        $h[]="})();";
    }

    $h[]="</script>";
    return implode("\n",$h);
}

// ─── Format bytes/sec rate for display ───
function format_rate(float $bytes_per_sec):string{
    if($bytes_per_sec>=1073741824){return number_format($bytes_per_sec/1073741824,2)." GB/s";}
    if($bytes_per_sec>=1048576){return number_format($bytes_per_sec/1048576,2)." MB/s";}
    if($bytes_per_sec>=1024){return number_format($bytes_per_sec/1024,2)." KB/s";}
    return number_format($bytes_per_sec,0)." B/s";
}
