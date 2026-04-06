<?php
// DarkStats — Network Traffic Dashboard
// Charts: Traffic overview, Top Talkers, Protocols, Ports, Interface timeseries, Hosts table
//
// API Endpoints (articarest /darkstats/*):
//   GET /darkstats/health           → running status, interfaces, packet counters
//   GET /darkstats/overview?range=  → aggregated totals (bytes, packets, pps, bps)
//   GET /darkstats/export/traffic?range= → per-interface bytes time-series
//   GET /darkstats/top?range=&dimension=hosts&limit= → top talkers
//   GET /darkstats/protocols?range= → protocol breakdown
//   GET /darkstats/ports?range=&protocol=tcp&limit= → port distribution
//   GET /darkstats/interface/{name}/timeseries?range= → single iface time-series
//   GET /darkstats/hosts?range=&limit= → host list with DNS
//   GET /darkstats/dns/status       → DNS resolver queue info

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors',1);ini_set('error_reporting',E_ALL);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["tab-traffic"])){tab_traffic();exit;}
if(isset($_GET["tab-top-hosts"])){tab_top_hosts();exit;}
if(isset($_GET["tab-protocols"])){tab_protocols();exit;}
if(isset($_GET["tab-ports"])){tab_ports();exit;}
if(isset($_GET["tab-interfaces"])){tab_interfaces();exit;}
if(isset($_GET["tab-hosts"])){tab_hosts();exit;}
if(isset($_GET["tab-status"])){tab_status();exit;}
if(isset($_GET["ports-table"])){ports_table();exit;}
if(isset($_GET["chart-overview"])){chart_overview();exit;}
if(isset($_GET["chart-top-hosts"])){chart_top_hosts();exit;}
if(isset($_GET["chart-protocols"])){chart_protocols();exit;}
if(isset($_GET["chart-ports"])){chart_ports();exit;}
if(isset($_GET["chart-iface-ts"])){chart_interface_timeseries();exit;}
if(isset($_GET["hosts-table"])){hosts_table();exit;}
if(isset($_GET["health-widget"])){health_widget();exit;}
if(isset($_GET["dns-widget"])){dns_status_widget();exit;}
if(isset($_GET["overview-stats"])){overview_stats_widget();exit;}

Start();

// ─── Helper: human-readable bytes ───
function _ds_fmt_bytes($bytes){
    if($bytes>=1099511627776) return sprintf('%.2f TB',$bytes/1099511627776);
    if($bytes>=1073741824) return sprintf('%.2f GB',$bytes/1073741824);
    if($bytes>=1048576) return sprintf('%.2f MB',$bytes/1048576);
    if($bytes>=1024) return sprintf('%.2f KB',$bytes/1024);
    return sprintf('%d B',$bytes);
}

// ─── Helper: format raw time key "202603270218" → readable label ───
function _ds_fmt_time($raw,$range='hour'){
    // Format: YYYYMMDDHHmm (minute tier), YYYYMMDDHH (hour tier), YYYYMMDD (day tier)
    $len=strlen($raw);
    if($len>=12){
        // minute: 202603270218 → "02:18" or "Mar 27 02:18"
        $h=substr($raw,8,2);$m=substr($raw,10,2);
        if($range==='day'||$range==='week'){
            $mon=intval(substr($raw,4,2));$d=intval(substr($raw,6,2));
            $months=['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return ($months[$mon]??$mon)." $d $h:$m";
        }
        return "$h:$m";
    }
    if($len>=10){
        // hour: 2026032702 → "02:00" or "Mar 27 02h"
        $h=substr($raw,8,2);
        if($range==='week'||$range==='month'){
            $mon=intval(substr($raw,4,2));$d=intval(substr($raw,6,2));
            $months=['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return ($months[$mon]??$mon)." $d {$h}h";
        }
        return "{$h}:00";
    }
    if($len>=8){
        // day: 20260327 → "Mar 27"
        $mon=intval(substr($raw,4,2));$d=intval(substr($raw,6,2));
        $months=['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return ($months[$mon]??$mon)." $d";
    }
    return $raw;
}

// ─── Helper: JS function for human-readable bytes in Chart.js callbacks ───
function _ds_js_fmt_bytes(){
    return "function _fmtB(v){if(v>=1073741824)return(v/1073741824).toFixed(2)+' GB';"
        ."if(v>=1048576)return(v/1048576).toFixed(2)+' MB';"
        ."if(v>=1024)return(v/1024).toFixed(1)+' KB';return v+' B';}";
}

// ─── Helper: range selector HTML ───
function _ds_range_selector($divTarget,$chartAction,$currentRange,$extra=''):string{
    $page=CurrentPageName();
    $ranges=['last15m'=>'15 min','hour'=>'Hour','day'=>'Day','week'=>'Week','month'=>'Month'];
    $h=[];
    $h[]="<div class='btn-group btn-group-sm' style='margin-bottom:8px;margin-top:10px'>";
    foreach($ranges as $k=>$label){
        $active=($k===$currentRange)?'btn-primary':'btn-default';
        $h[]="<button class='btn $active' onclick=\"LoadAjax('$divTarget','$page?$chartAction=yes&range=$k$extra');\">$label</button>";
    }
    $h[]="</div>";
    return implode("",$h);
}

// ─── Main page shell ───
function Start(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html=$tpl->page_header("{network_traffic_statistics}","fas fa-chart-area","{network_traffic}","$page?tabs=yes");
    echo $tpl->_ENGINE_parse_body($html);
}

// ─── Tabs ───
function tabs(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $array=[
        "{network_traffic}"    =>"$page?tab-traffic=yes",
        "{top_talkers}"        =>"$page?tab-top-hosts=yes",
        "{protocols}"          =>"$page?tab-protocols=yes",
        "{ports}"              =>"$page?tab-ports=yes",
        "{interfaces}"         =>"$page?tab-interfaces=yes",
        "{hosts}"              =>"$page?tab-hosts=yes",
        "{status}"             =>"$page?tab-status=yes",
    ];
    echo $tpl->_ENGINE_parse_body($tpl->tabs_default($array));
}

// ─── Tab: Traffic overview ───
function tab_traffic(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $h=[];
    $h[]="<div id='ds-traffic'></div>";
    $h[]="<script>LoadAjax('ds-traffic','$page?chart-overview=yes&range=last15m');</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ─── Tab: Top Talkers ───
function tab_top_hosts(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $h=[];
    $h[]="<div id='ds-top-hosts'></div>";
    $h[]="<script>LoadAjax('ds-top-hosts','$page?chart-top-hosts=yes&range=last15m');</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ─── Tab: Protocols — pie left, port bandwidth timeline right ───
function tab_protocols(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $h=[];
    $h[]="<div class='row'>";
    $h[]="<div class='col-md-5'><div id='ds-protocols'></div></div>";
    $h[]="<div class='col-md-7'><div id='ds-proto-traffic'></div></div>";
    $h[]="</div>";
    $h[]="<script>";
    $h[]="LoadAjax('ds-protocols','$page?chart-protocols=yes&range=last15m');";
    $h[]="LoadAjax('ds-proto-traffic','$page?chart-overview=yes&range=last15m');";
    $h[]="</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ─── Tab: Ports — chart left, table right ───
function tab_ports(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $h=[];
    $h[]="<div class='row'>";
    $h[]="<div class='col-md-6'><div id='ds-ports'></div></div>";
    $h[]="<div class='col-md-6'><div id='ds-ports-table'></div></div>";
    $h[]="</div>";
    $h[]="<script>";
    $h[]="LoadAjax('ds-ports','$page?chart-ports=yes&range=last15m');";
    $h[]="LoadAjax('ds-ports-table','$page?ports-table=yes&range=last15m');";
    $h[]="</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ─── Tab: Interfaces ───
function tab_interfaces(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $h=[];
    $h[]="<div id='ds-iface-ts'></div>";
    $h[]="<script>LoadAjax('ds-iface-ts','$page?chart-iface-ts=yes&range=last15m');</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ─── Tab: Hosts ───
function tab_hosts(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $h=[];
    $h[]="<div id='ds-hosts'></div>";
    $h[]="<script>LoadAjax('ds-hosts','$page?hosts-table=yes&range=last15m');</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ─── Tab: Status (Health + Overview + DNS) ───
function tab_status(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $h=[];
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-heartbeat'></i> {service_status}</h5></div>";
    $h[]="<div class='ibox-content' id='ds-health'></div></div>";
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-tachometer-alt'></i> {overview}</h5></div>";
    $h[]="<div class='ibox-content' id='ds-overview-stats'></div></div>";
    $h[]="<div class='ibox'><div class='ibox-title'><h5><i class='fas fa-search'></i> DNS {status}</h5></div>";
    $h[]="<div class='ibox-content' id='ds-dns'></div></div>";
    $h[]="<script>";
    $h[]="LoadAjax('ds-health','$page?health-widget=yes');";
    $h[]="LoadAjax('ds-overview-stats','$page?overview-stats=yes&range=last15m');";
    $h[]="LoadAjax('ds-dns','$page?dns-widget=yes');";
    $h[]="</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ─── Health widget ───
function health_widget(){
    $tpl=new template_admin();
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/darkstats/health");
    $json=json_decode($raw);
    if(!is_object($json)||!property_exists($json,'success')||!$json->success){
        echo "<span class='badge' style='background:#c0392b;color:#fff'>DarkStats Offline</span>";
        echo "<p class='text-muted' style='margin-top:8px'>{service_not_running}</p>";
        return;
    }
    $d=$json->data;
    $color=$d->running?'#27ae60':'#c0392b';
    $statusText=$d->running?'Running':'Stopped';
    $h=[];
    $h[]="<span class='badge' style='background:$color;color:#fff;font-size:13px;padding:5px 12px'>DarkStats $statusText</span>";
    $tpl2=new template_admin();
    $tpl2->table_form_field_text("{interfaces}",implode(', ',$d->interfaces??[]),"fas fa-ethernet");
    $tpl2->table_form_field_text("{packets}",number_format($d->packets_total??0),"fas fa-box");
    $tpl2->table_form_field_text("{dropped}",number_format($d->packets_dropped??0),"fas fa-exclamation-triangle");
    $tpl2->table_form_field_text("DB",htmlspecialchars($d->db_size??"N/A"),"fas fa-database");
    $h[]=$tpl2->table_form_compile();
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ─── Overview stats widget ───
function overview_stats_widget(){
    $tpl=new template_admin();
    $range=$_GET["range"]??"last15m";
    if(!in_array($range,['last15m','hour','day','week','month'])){$range='last15m';}
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/darkstats/overview?range=$range");
    $json=json_decode($raw);
    if(!is_object($json)||!property_exists($json,'success')||!$json->success){
        echo "<p class='text-muted'>{no_data}</p>";
        return;
    }
    $d=$json->data;
    $tpl2=new template_admin();
    $tpl2->table_form_field_text("{total} {bytes}",_ds_fmt_bytes($d->total_bytes??0),"fas fa-download");
    $tpl2->table_form_field_text("{total} {packets}",number_format($d->total_packets??0),"fas fa-cubes");
    $tpl2->table_form_field_text("Bytes/s",_ds_fmt_bytes($d->bytes_per_sec??0)."/s","fas fa-tachometer-alt");
    $tpl2->table_form_field_text("Pkt/s",number_format($d->packets_per_sec??0,1),"fas fa-wave-square");
    $tpl2->table_form_field_text("IPv4 / IPv6",number_format($d->ipv4_packets??0)." / ".number_format($d->ipv6_packets??0),"fas fa-globe");
    echo $tpl->_ENGINE_parse_body($tpl2->table_form_compile());
}

// ─── DNS status widget ───
function dns_status_widget(){
    $tpl=new template_admin();
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/darkstats/dns/status");
    $json=json_decode($raw);
    if(!is_object($json)||!property_exists($json,'success')||!$json->success){
        echo "<p class='text-muted'>DNS {unavailable}</p>";
        return;
    }
    $d=$json->data;
    $tpl2=new template_admin();
    $tpl2->table_form_field_bool("{enabled}",$d->enabled??false,"fas fa-power-off");
    $tpl2->table_form_field_text("{workers}",($d->alive_workers??0)."/".($d->workers??0),"fas fa-cogs");
    $tpl2->table_form_field_text("{queue}",($d->queue_length??0)."/".($d->queue_capacity??0),"fas fa-stream");
    $tpl2->table_form_field_text("{cache}",number_format($d->cache_size??0)." (".number_format($d->resolved_ok??0)." {resolved})","fas fa-memory");
    echo $tpl->_ENGINE_parse_body($tpl2->table_form_compile());
}

// ─── Traffic overview line chart ───
function chart_overview(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=$_GET["range"]??"last15m";
    if(!in_array($range,['last15m','hour','day','week','month'])){$range='last15m';}

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/darkstats/export/traffic?range=$range");
    $json=json_decode($raw);

    $h=[];
    $h[]=_ds_range_selector('ds-traffic','chart-overview',$range);

    if(!is_object($json)||!property_exists($json,'success')||!$json->success||!is_array($json->data)||count($json->data)==0){
        $h[]="<p class='text-danger'>{no_data}</p>";
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    $labels=[];
    $datasets=[];
    $ifaceNames=[];

    foreach($json->data as $point){
        $labels[]=_ds_fmt_time($point->time??'',$range);
        if(is_object($point->interfaces)){
            foreach($point->interfaces as $name=>$bytes){
                if(!isset($datasets[$name])){
                    $datasets[$name]=[];
                    $ifaceNames[]=$name;
                }
            }
        }
    }
    foreach($json->data as $point){
        foreach($ifaceNames as $name){
            $val=isset($point->interfaces->$name)?$point->interfaces->$name:0;
            $datasets[$name][]=sprintf('%.2f',$val/1048576);
        }
    }

    $colors=['#1c84c6','#ed5565','#1ab394','#f8ac59','#ab7df6','#23c6c8','#676a6c','#2ecc71'];
    $chartDatasets=[];
    $ci=0;
    foreach($datasets as $name=>$data){
        $color=$colors[$ci%count($colors)];
        $dataStr='['.implode(',',$data).']';
        $safeName=addslashes($name);
        $chartDatasets[]="{label:'$safeName',data:$dataStr,borderColor:'$color',backgroundColor:'$color',tension:0.3,pointRadius:2,borderWidth:2,fill:false}";
        $ci++;
    }

    $labelsJs="['".implode("','",$labels)."']";
    $datasetsJs=implode(",\n",$chartDatasets);
    $t=time().mt_rand(100,999);

    $h[]="<canvas id='ds-traffic-chart-$t' height='80'></canvas>";
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";
    $h[]="<script>NoSpinner();";
    $h[]="new Chart(document.getElementById('ds-traffic-chart-$t'),{";
    $h[]="type:'line',data:{labels:$labelsJs,datasets:[$datasetsJs]},";
    $h[]="options:{responsive:true,interaction:{mode:'index',intersect:false},";
    $h[]="scales:{x:{type:'category',ticks:{maxRotation:45,font:{size:10}}},";
    $h[]="y:{beginAtZero:true,title:{display:true,text:'MB'}}},";
    $h[]="plugins:{legend:{position:'bottom'},tooltip:{callbacks:{label:function(ctx){";
    $h[]="return ctx.dataset.label+': '+parseFloat(ctx.parsed.y).toFixed(2)+' MB';";
    $h[]="}}}}";
    $h[]="}});";
    $h[]="</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ─── Top Talkers horizontal bar chart ───
function chart_top_hosts(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=$_GET["range"]??"last15m";
    if(!in_array($range,['last15m','hour','day','week','month'])){$range='last15m';}

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/darkstats/top?range=$range&dimension=hosts&limit=10");
    $json=json_decode($raw);

    $h=[];
    $h[]=_ds_range_selector('ds-top-hosts','chart-top-hosts',$range);

    if(!is_object($json)||!property_exists($json,'success')||!$json->success||!is_array($json->data)||count($json->data)==0){
        $h[]="<p class='text-muted'>{no_data}</p>";
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    $labels=[];
    $mbIn=[];
    $mbOut=[];
    foreach($json->data as $host){
        $label=!empty($host->hostname)?$host->hostname:($host->ip??"?");
        $labels[]=addslashes($label);
        $mbIn[]=sprintf('%.2f',($host->bytes_in??0)/1048576);
        $mbOut[]=sprintf('%.2f',($host->bytes_out??0)/1048576);
    }

    $labelsJs="['".implode("','",$labels)."']";
    $inData='['.implode(',',$mbIn).']';
    $outData='['.implode(',',$mbOut).']';
    $t=time().mt_rand(100,999);

    $h[]="<canvas id='ds-top-hosts-$t' height='150'></canvas>";
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";
    $h[]="<script>NoSpinner();";
    $h[]="new Chart(document.getElementById('ds-top-hosts-$t'),{";
    $h[]="type:'bar',";
    $h[]="data:{labels:$labelsJs,datasets:[";
    $h[]="{label:'In (MB)',data:$inData,backgroundColor:'#1c84c6',borderRadius:3},";
    $h[]="{label:'Out (MB)',data:$outData,backgroundColor:'#ed5565',borderRadius:3}";
    $h[]="]},";
    $h[]="options:{indexAxis:'y',responsive:true,";
    $h[]="scales:{x:{beginAtZero:true,title:{display:true,text:'MB'}}},";
    $h[]="plugins:{legend:{position:'bottom'},tooltip:{callbacks:{label:function(ctx){";
    $h[]="return ctx.dataset.label+': '+parseFloat(ctx.parsed.x).toFixed(2)+' MB';";
    $h[]="}}}}}";
    $h[]="});";
    $h[]="</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ─── Protocol distribution doughnut ───
function chart_protocols(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=$_GET["range"]??"last15m";
    if(!in_array($range,['last15m','hour','day','week','month'])){$range='last15m';}

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/darkstats/protocols?range=$range");
    $json=json_decode($raw);

    $h=[];
    $h[]=_ds_range_selector('ds-protocols','chart-protocols',$range);

    if(!is_object($json)||!property_exists($json,'success')||!$json->success||!is_array($json->data)||count($json->data)==0){
        $h[]="<p class='text-muted'>{no_data}</p>";
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    $labels=[];
    $data=[];
    $colors=['#1c84c6','#ed5565','#1ab394','#f8ac59','#ab7df6','#23c6c8','#676a6c','#2ecc71'];
    foreach($json->data as $proto){
        $labels[]=addslashes($proto->name??"proto-".($proto->protocol??0));
        $data[]=sprintf('%.2f',($proto->bytes??0)/1048576);
    }

    $labelsJs="['".implode("','",$labels)."']";
    $dataJs='['.implode(',',$data).']';
    $usedColors=array_slice($colors,0,count($labels));
    $colorsJs="['".implode("','",$usedColors)."']";
    $t=time().mt_rand(100,999);

    $h[]="<canvas id='ds-proto-chart-$t' height='180'></canvas>";
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";
    $h[]="<script>NoSpinner();";
    $h[]="new Chart(document.getElementById('ds-proto-chart-$t'),{";
    $h[]="type:'doughnut',";
    $h[]="data:{labels:$labelsJs,datasets:[{data:$dataJs,backgroundColor:$colorsJs,borderWidth:2,borderColor:'#fff'}]},";
    $h[]="options:{responsive:true,cutout:'55%',plugins:{legend:{position:'right',labels:{padding:12,usePointStyle:true}},";
    $h[]="tooltip:{callbacks:{label:function(ctx){var v=ctx.parsed;var t=ctx.dataset.data.reduce(function(a,b){return a+b},0);";
    $h[]="var pct=t>0?((v/t)*100).toFixed(1)+'%':'0%';";
    $h[]="return ctx.label+': '+parseFloat(v).toFixed(2)+' MB ('+pct+')';";
    $h[]="}}}}}";
    $h[]="});";
    $h[]="</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ─── Port distribution bar chart ───
function chart_ports(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=$_GET["range"]??"last15m";
    if(!in_array($range,['last15m','hour','day','week','month'])){$range='last15m';}

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/darkstats/ports?range=$range&limit=15");
    $json=json_decode($raw);

    $h=[];
    $h[]=_ds_range_selector('ds-ports','chart-ports',$range);

    if(!is_object($json)||!property_exists($json,'success')||!$json->success||!is_array($json->data)||count($json->data)==0){
        $h[]="<p class='text-muted'>{no_data}</p>";
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    $labels=[];
    $dataBytes=[];
    $bgColors=[];
    $colors=['#1c84c6','#23c6c8','#1ab394','#ab7df6','#f8ac59','#ed5565','#676a6c','#2ecc71',
             '#3498db','#e74c3c','#9b59b6','#f39c12','#1abc9c','#34495e','#e67e22'];
    foreach($json->data as $i=>$port){
        $labels[]=($port->port??0).'/'.($port->protocol??'tcp');
        $dataBytes[]=sprintf('%.2f',($port->bytes??0)/1048576);
        $bgColors[]=$colors[$i%count($colors)];
    }

    $labelsJs="['".implode("','",$labels)."']";
    $dataJs='['.implode(',',$dataBytes).']';
    $colorsJs="['".implode("','",$bgColors)."']";
    $t=time().mt_rand(100,999);

    $h[]="<canvas id='ds-ports-chart-$t' height='180'></canvas>";
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";
    $h[]="<script>NoSpinner();";
    $h[]="new Chart(document.getElementById('ds-ports-chart-$t'),{";
    $h[]="type:'bar',";
    $h[]="data:{labels:$labelsJs,datasets:[{label:'MB',data:$dataJs,backgroundColor:$colorsJs,borderRadius:3}]},";
    $h[]="options:{responsive:true,scales:{y:{beginAtZero:true,title:{display:true,text:'MB'}}},";
    $h[]="plugins:{legend:{display:false},tooltip:{callbacks:{label:function(ctx){";
    $h[]="return ctx.label+': '+parseFloat(ctx.parsed.y).toFixed(2)+' MB';";
    $h[]="}}}}}";
    $h[]="});";
    $h[]="</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ─── Interface timeseries (dual-axis: bytes + packets) ───
function chart_interface_timeseries(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=$_GET["range"]??"last15m";
    $iface=$_GET["iface"]??"";
    if(!in_array($range,['last15m','hour','day','week','month'])){$range='last15m';}

    // Get available interfaces
    $ifaces_raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/darkstats/interfaces?range=$range");
    $ifaces_json=json_decode($ifaces_raw);
    $ifaceList=[];
    if(is_object($ifaces_json)&&property_exists($ifaces_json,'success')&&$ifaces_json->success&&is_array($ifaces_json->data)){
        foreach($ifaces_json->data as $entry){
            $ifaceList[]=$entry->name??'';
        }
    }
    if(count($ifaceList)==0){$ifaceList[]='eth0';}
    if($iface===''||!in_array($iface,$ifaceList)){$iface=$ifaceList[0];}

    $iface_url=urlencode($iface);
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/darkstats/interface/$iface_url/timeseries?range=$range");
    $json=json_decode($raw);

    $h=[];

    // Interface selector + range buttons
    $h[]="<div style='display:flex;gap:10px;align-items:center;margin-bottom:8px;flex-wrap:wrap'>";
    $h[]="<select id='ds-iface-sel' class='form-control' style='width:auto;display:inline-block' onchange=\"var i=this.value;LoadAjax('ds-iface-ts','$page?chart-iface-ts=yes&range=$range&iface='+encodeURIComponent(i));\">";
    foreach($ifaceList as $ifn){
        $sel=($ifn===$iface)?'selected':'';
        $ifn_safe=htmlspecialchars($ifn);
        $h[]="<option value='$ifn_safe' $sel>$ifn_safe</option>";
    }
    $h[]="</select>";
    $extra="&iface=".urlencode($iface);
    $h[]=_ds_range_selector('ds-iface-ts','chart-iface-ts',$range,$extra);
    $h[]="</div>";

    if(!is_object($json)||!property_exists($json,'success')||!$json->success||!is_array($json->data)||count($json->data)==0){
        $h[]="<p class='text-muted'>{no_data}</p>";
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    $labels=[];
    $mb=[];
    $pkts=[];
    foreach($json->data as $point){
        $labels[]=_ds_fmt_time($point->time??'',$range);
        $mb[]=sprintf('%.2f',($point->stats->Bytes??0)/1048576);
        $pkts[]=$point->stats->Packets??0;
    }

    $labelsJs="['".implode("','",$labels)."']";
    $mbJs='['.implode(',',$mb).']';
    // packets stored in a hidden JS array for tooltip display
    $pktsJs='['.implode(',',$pkts).']';
    $t=time().mt_rand(100,999);

    $h[]="<canvas id='ds-iface-chart-$t' height='80'></canvas>";
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";
    $h[]="<script>NoSpinner();";
    $h[]="var _ifPkts{$t}=$pktsJs;";
    $h[]="new Chart(document.getElementById('ds-iface-chart-$t'),{";
    $h[]="type:'line',data:{labels:$labelsJs,datasets:[";
    $h[]="{label:'MB',data:$mbJs,borderColor:'#1c84c6',backgroundColor:'#1c84c6',tension:0.3,pointRadius:2,borderWidth:2,fill:false}";
    $h[]="]},options:{responsive:true,";
    $h[]="scales:{x:{type:'category',ticks:{maxRotation:45,font:{size:10}}},";
    $h[]="y:{beginAtZero:true,title:{display:true,text:'MB'}}},";
    $h[]="plugins:{legend:{display:false},tooltip:{callbacks:{label:function(ctx){";
    $h[]="var p=_ifPkts{$t}"."[ctx.dataIndex]||0;";
    $h[]="return [ctx.dataset.label+': '+parseFloat(ctx.parsed.y).toFixed(2)+' MB','Packets: '+p];";
    $h[]="}}}}}";
    $h[]="});";
    $h[]="</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ─── Hosts FooTable ───
function hosts_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=$_GET["range"]??"last15m";
    if(!in_array($range,['last15m','hour','day','week','month'])){$range='last15m';}
    $limit=intval($_GET["limit"]??50);
    if($limit<10||$limit>500){$limit=50;}

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/darkstats/hosts?range=$range&limit=$limit");
    $json=json_decode($raw);

    $h=[];
    $h[]=_ds_range_selector('ds-hosts','hosts-table',$range);

    if(!is_object($json)||!property_exists($json,'success')||!$json->success||!is_array($json->data)||count($json->data)==0){
        $h[]="<p class='text-muted'>{no_data}</p>";
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    $t=time().mt_rand(100,999);
    $h[]="<table id='ds-hosts-tbl-$t' class='footable table table-stripped' data-page-size='50'>";
    $h[]="<thead><tr>";
    $h[]="<th data-sortable=true data-type='text'>IP</th>";
    $h[]="<th data-sortable=true data-type='text'>{hostname}</th>";
    $h[]="<th data-sortable=true data-type='number' data-sort-value='{{bytes_in}}'>Bytes In</th>";
    $h[]="<th data-sortable=true data-type='number' data-sort-value='{{bytes_out}}'>Bytes Out</th>";
    $h[]="<th data-sortable=true data-type='number' data-sort-value='{{pkts_in}}'>Pkts In</th>";
    $h[]="<th data-sortable=true data-type='number' data-sort-value='{{pkts_out}}'>Pkts Out</th>";
    $h[]="</tr></thead><tbody>";

    $TRCLASS=null;
    foreach($json->data as $host){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ip=htmlspecialchars($host->ip??"");
        $hostname_val=htmlspecialchars($host->hostname??"");
        $bi=$host->bytes_in??0;
        $bo=$host->bytes_out??0;
        $pi=$host->packets_in??0;
        $po=$host->packets_out??0;
        $h[]="<tr class='$TRCLASS'>";
        $h[]="<td><span style='font-family:monospace'>$ip</span></td>";
        $h[]="<td>$hostname_val</td>";
        $h[]="<td data-sort-value='$bi'>"._ds_fmt_bytes($bi)."</td>";
        $h[]="<td data-sort-value='$bo'>"._ds_fmt_bytes($bo)."</td>";
        $h[]="<td data-sort-value='$pi'>".number_format($pi)."</td>";
        $h[]="<td data-sort-value='$po'>".number_format($po)."</td>";
        $h[]="</tr>";
    }

    $h[]="</tbody>";
    $h[]="<tfoot><tr><td colspan='6'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $h[]="</table>";
    $h[]="<script>NoSpinner();";
    $h[]=implode("\n",$tpl->ICON_SCRIPTS);
    $h[]="$(document).ready(function(){\$('#ds-hosts-tbl-$t').footable({";
    $h[]="\"filtering\":{\"enabled\":true},\"sorting\":{\"enabled\":true},\"paging\":{\"size\":{$GLOBALS["FOOTABLE_PSIZE"]}}";
    $h[]="});});</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ─── Ports FooTable ───
function ports_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=$_GET["range"]??"last15m";
    if(!in_array($range,['last15m','hour','day','week','month'])){$range='last15m';}

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/darkstats/ports?range=$range&limit=30");
    $json=json_decode($raw);

    $h=[];
    $h[]=_ds_range_selector('ds-ports-table','ports-table',$range);

    if(!is_object($json)||!property_exists($json,'success')||!$json->success||!is_array($json->data)||count($json->data)==0){
        $h[]="<p class='text-muted'>{no_data}</p>";
        echo $tpl->_ENGINE_parse_body(implode("\n",$h));
        return;
    }

    $t=time().mt_rand(100,999);
    $h[]="<table id='ds-ports-tbl-$t' class='footable table table-stripped' data-page-size='30'>";
    $h[]="<thead><tr>";
    $h[]="<th data-sortable=true data-type='number'>{port}</th>";
    $h[]="<th data-sortable=true data-type='text'>{protocol}</th>";
    $h[]="<th data-sortable=true data-type='number'>{size}</th>";
    $h[]="<th data-sortable=true data-type='number'>{packets}</th>";
    $h[]="</tr></thead><tbody>";

    $TRCLASS=null;
    foreach($json->data as $port){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $pn=$port->port??0;
        $proto=htmlspecialchars($port->protocol??'tcp');
        $b=$port->bytes??0;
        $pk=$port->packets??0;
        $h[]="<tr class='$TRCLASS'>";
        $h[]="<td><span style='font-family:monospace'>$pn</span></td>";
        $h[]="<td><span class='badge' style='background:#1c84c6;color:#fff'>$proto</span></td>";
        $h[]="<td data-sort-value='$b'>"._ds_fmt_bytes($b)."</td>";
        $h[]="<td data-sort-value='$pk'>".number_format($pk)."</td>";
        $h[]="</tr>";
    }

    $h[]="</tbody>";
    $h[]="<tfoot><tr><td colspan='4'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $h[]="</table>";
    $h[]="<script>NoSpinner();";
    $h[]=implode("\n",$tpl->ICON_SCRIPTS);
    $h[]="$(document).ready(function(){\$('#ds-ports-tbl-$t').footable({";
    $h[]="\"filtering\":{\"enabled\":true},\"sorting\":{\"enabled\":true},\"paging\":{\"size\":15}";
    $h[]="});});</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}
