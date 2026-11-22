<?php
/**
 * System OS Metrics Dashboard
 *
 * Displays CPU, memory, and load average metrics using Chart.js.
 * Data is collected every 3 minutes with 90 days retention.
 * Design: /home/claude/images/stitch_disk_io_metrics (KPI cards + chart cards).
 *
 * API Endpoints Used:
 * - GET /sysmonitor/status - Check if SysMonitor is running
 * - GET /sysmonitor/stats - Get database statistics and latest metrics
 * - GET /sysmonitor/chart?hours=N - Chart series (labels, cpu, mem, load1/5/15)
 * - GET /sysmonitor/metrics?hours=N - Historical system metrics
 * - GET /sysmonitor/latest - Most recent metric
 * - GET /sysmonitor/start - Start monitoring
 * - GET /sysmonitor/stop - Stop monitoring
 */

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.metrics.kit.inc");

$GLOBALS["CLASS_SOCKETS"] = new sockets();
$tpl = new template_admin();

if(isset($_GET["verbose"])){
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
}
if(!$tpl->xPrivs()){ exit(); }
clean_xss_deep();
if(isset($_GET["purge-js"])){purge_js();exit;}
if(isset($_GET["top-widgets"])){top_widgets();exit;}
if(isset($_GET["api-status"])) { api_status(); exit; }
if(isset($_GET["api-stats"])) { api_stats(); exit; }
if(isset($_GET["api-metrics"])) { api_metrics(); exit; }
if(isset($_GET["api-chart"])) { api_chart(); exit; }
if(isset($_GET["api-latest"])) { api_latest(); exit; }
if(isset($_GET["api-start"])) { api_start(); exit; }
if(isset($_GET["api-stop"])) { api_stop(); exit; }
if(isset($_POST["purge"])) { api_purge(); exit; }
if(isset($_GET["build-tabs"])){build_tabs();exit;}
if(isset($_GET["chart-view"])){chart_view();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}

// Default: show main page
page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{os_metrics_title}","fas fa-microchip","{os_mon_explain}",
        "$page?status=yes","os-metrics","progress-metrics-os-restart",false,"table-loader-metrics-os");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }

    echo $tpl->_ENGINE_parse_body($html);
}

// Periods offered by the header, in hours. Everything that reads "period" goes through it.
function osm_periods():array{
    return array(1=>"1h",6=>"6h",24=>"24h",168=>"7d");
}
function osm_period():int{
    $p=intval($_GET["period"] ?? 1);
    $periods=osm_periods();
    if(!isset($periods[$p])){return 1;}
    return $p;
}
function osm_period_title($hours):string{
    $titles=array(1=>"{last_hour}",6=>"{last_6_hours}",24=>"{last_24_h}",168=>"{last_week}");
    return isset($titles[$hours])?$titles[$hours]:"{last_hour}";
}
// The modal opened from fw.system.status.php has no page header: a TINY there would
// hijack the parent page's buttons, so the period bar is drawn inside the body instead.
function osm_is_modal():bool{
    return intval($_GET["modal"] ?? 0)==1;
}

function purge_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_confirm_delete("{remove_database}",
        "purge","yes","LoadAjaxSilent('os-widgets','$page?top-widgets=yes');");
}

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog6("{os_metrics_title}","$page?tabs=yes",1024);
}




// "2160h0m0s" (Go duration) -> 90 ; "3m0s" -> "3 min".
function osm_retention_days($duration):int{
    if(preg_match('/^([0-9]+)h/',(string)$duration,$re)){return intval(intval($re[1])/24);}
    return 0;
}

function formatMemory($mb){
    if($mb < 1024){
        return $mb." MB";
    }
    return round($mb/1024, 1)." GB";
}

function top_widgets(){
    $tpl=new template_admin();
    $status=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/sysmonitor/stats"),true);

    if(!is_array($status) || !isset($status["data"])){
        echo mtk_css().$tpl->_ENGINE_parse_body($tpl->div_error("{error}||/sysmonitor/stats"));
        return false;
    }
    $data=$status["data"];
    $latest=isset($data["latest"]) && is_array($data["latest"]) ? $data["latest"] : array();

    $html=array();
    $html[]="<div class='mtkw'><div class='mtk-kpis'>";
    $html[]=osm_kpi_database($data);
    $html[]=osm_kpi_cpu_memory($latest);
    $html[]=osm_kpi_service($data);
    $html[]=osm_kpi_load($data,$latest);
    $html[]="</div></div>";
    echo mtk_css().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

function osm_kpi_database(array $data):string{
    list($num,$unit)=mtk_split_size($data["db_size_human"] ?? "");
    $days=osm_retention_days($data["retention"] ?? "");
    $h=array();
    $h[]="<div class='mtk-kpi'>";
    $h[]="<div class='mtk-kpi-top'><div>";
    $h[]="<div class='mtk-kpi-lbl'>{database_size}</div>";
    $h[]="<div class='mtk-kpi-val'><span class='mtk-kpi-num'>".htmlspecialchars($num)."</span>";
    $h[]="<span class='mtk-kpi-unit'>".htmlspecialchars($unit)."</span></div>";
    $h[]="</div><div class='mtk-kpi-ico'><i class='".ico_database."'></i></div></div>";
    $h[]="<div class='mtk-kpi-foot'><span><span class='mtk-chip'>{retention}</span>";
    $h[]=($days>0 ? "$days {days}" : "-")."</span></div>";
    $h[]="</div>";
    return @implode("",$h);
}

function osm_kpi_cpu_memory(array $latest):string{
    $cpu="-";$mem="-";$used="-";$when="";
    if(count($latest)>0){
        $cpu=round(floatval($latest["cpu_percent"] ?? 0),1);
        $mem=round(floatval($latest["mem_percent"] ?? 0),1);
        $used=formatMemory(intval($latest["mem_used_mb"] ?? 0));
        $ts=intval($latest["timestamp"] ?? 0);
        if($ts>0){$when=" &middot; ".date("H:i:s",$ts);}
    }
    $h=array();
    $h[]="<div class='mtk-kpi'>";
    $h[]="<div class='mtk-kpi-top'><div>";
    $h[]="<div class='mtk-kpi-lbl'>CPU / {memory}$when</div>";
    $h[]="<div class='mtk-kpi-pair'><span class='mtk-c-read'>$cpu<small>%</small></span>";
    $h[]="<span class='mtk-kpi-sep'>/</span><span class='mtk-c-write'>$mem<small>%</small></span></div>";
    $h[]="</div><div class='mtk-kpi-ico'><i class='fas fa-microchip'></i></div></div>";
    $h[]="<div class='mtk-kpi-foot'><span>";
    $h[]="<span class='mtk-dot mtk-c-read'>CPU</span>";
    $h[]="<span class='mtk-dot mtk-c-write'>{memory} ".htmlspecialchars($used)."</span>";
    $h[]="</span></div>";
    $h[]="</div>";
    return @implode("",$h);
}

function osm_kpi_service(array $data):string{
    $running=!empty($data["running"]);
    $cpus=intval($data["num_cpu"] ?? 0);
    $interval=mtk_interval_label($data["collect_interval"] ?? "");
    $h=array();
    $h[]="<div class='mtk-kpi'>";
    $h[]="<div class='mtk-kpi-top'><div>";
    $h[]="<div class='mtk-kpi-lbl'>{status}</div>";
    $h[]="<div class='mtk-kpi-val'><span class='mtk-kpi-num'>".($running?"{running}":"{stopped}")."</span></div>";
    if($running){
        $h[]="</div><div class='mtk-kpi-ico'><i class='fas fa-play'></i></div></div>";
    }else{
        $h[]="</div><div class='mtk-kpi-ico mtk-kpi-ico-off'><i class='fas fa-stop'></i></div></div>";
    }
    $h[]="<div class='mtk-kpi-foot'>";
    $h[]="<span class='mtk-live'><span class='mtk-pulse".($running?"":" mtk-pulse-off")."'></span>{processors}: $cpus</span>";
    $h[]="<span class='mtk-mono'>".htmlspecialchars($interval)."</span>";
    $h[]="</div>";
    $h[]="</div>";
    return @implode("",$h);
}

function osm_kpi_load(array $data,array $latest):string{
    $cpus=intval($data["num_cpu"] ?? 0);
    $load1="-";$foot="";$mono="";
    if(count($latest)>0){
        $l1=floatval($latest["load1"] ?? 0);
        $load1=sprintf("%.2f",$l1);
        $mono="5m ".sprintf("%.2f",floatval($latest["load5"] ?? 0))." &middot; 15m ".sprintf("%.2f",floatval($latest["load15"] ?? 0));
        // The run queue only means saturation relative to the number of cores.
        if($cpus>0 && $l1>$cpus){
            $foot="<span class='mtk-warn'><i class='fas fa-triangle-exclamation'></i> {osm_load_high}</span>";
        }else{
            $foot="<span class='mtk-ok'><i class='fas fa-check'></i> {osm_load_ok}</span>";
        }
    }
    $h=array();
    $h[]="<div class='mtk-kpi'>";
    $h[]="<div class='mtk-kpi-top'><div>";
    $h[]="<div class='mtk-kpi-lbl'>{load_average}</div>";
    $h[]="<div class='mtk-kpi-val'><span class='mtk-kpi-num'>$load1</span><span class='mtk-kpi-unit-mute'>1 min</span></div>";
    $h[]="</div><div class='mtk-kpi-ico'><i class='fas fa-gauge-high'></i></div></div>";
    $h[]="<div class='mtk-kpi-foot'>$foot".($mono==""?"":"<span class='mtk-mono'>$mono</span>")."</div>";
    $h[]="</div>";
    return @implode("",$h);
}

function status():bool {
    $page = CurrentPageName();
    $tpl = new template_admin();
    echo osm_shell($page,$tpl,false);
    return true;
}

// Modal entry (fw.system.status.php → Loadjs(?js=yes) → js_dialog6 → here).
function tabs():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    echo osm_shell($page,$tpl,true);
    return true;
}

function osm_shell($page,$tpl,$modal):string{
    $period=osm_period();
    $m=$modal?"&modal=1":"";
    $html=array();
    $html[]="<div class='mtkw'>";
    $html[]="<div id='os-widgets'></div>";
    $html[]="<div id='mtk-tabs' style='margin-top:20px'></div>";
    $html[]="</div>";
    $html[]="<script>";
    $html[]="function OsmPeriod(p){LoadAjaxSilent('mtk-tabs','$page?build-tabs=yes$m&period='+encodeURIComponent(p));}";
    $html[]=$tpl->RefreshInterval_js("os-widgets",$page,"top-widgets=yes",10);
    $html[]="LoadAjaxSilent('mtk-tabs','$page?build-tabs=yes$m&period=$period');";
    $html[]="</script>";
    return mtk_css().$tpl->_ENGINE_parse_body(@implode("\n",$html));
}

// Rebuilt on every period change: fresh tabs pointing at the new period, then the TINY
// (after the tabs, so the segment's "on" state always matches what is displayed).
function build_tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $period=osm_period();
    $modal=osm_is_modal();

    $tabs=array();
    $tabs["{overview}"]="$page?chart-view=overview&period=$period";
    $tabs["CPU"]="$page?chart-view=cpu&period=$period";
    $tabs["{memory}"]="$page?chart-view=memory&period=$period";
    $tabs["{load_average}"]="$page?chart-view=load&period=$period";

    $out=array();
    if($modal){
        $out[]=$tpl->_ENGINE_parse_body(mtk_period_inline(osm_periods(),$period,"OsmPeriod"));
    }
    $out[]=$tpl->tabs_default($tabs);

    if(!$modal){
        $fields=array();
        $fields[]=array("TYPE"=>"segment","ID"=>"mtk-period","LABEL"=>"{period}","VALUE"=>(string)$period,
            "OPTIONS"=>osm_periods(),"JS"=>"OsmPeriod");
        $topbuttons=array();
        $topbuttons[]=array("OsmPeriod('$period')",ico_refresh,"{refresh}");
        $topbuttons[]=array("Loadjs('$page?purge-js=yes')",ico_trash,"{remove_database}");
        $TINY=array();
        $TINY["TITLE"]="{os_metrics_title}";
        $TINY["ICO"]="fas fa-microchip";
        $TINY["EXPL"]="{os_mon_explain}";
        $TINY["URL"]="os-metrics";
        $TINY["BUTTONS"]=siem_tiny_filters($fields,$tpl->table_buttons($topbuttons));
        $out[]="<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";
    }
    echo siem_tiny_filters_style().mtk_css().@implode("\n",$out);
    return true;
}


// One tab pane. The overview holds two charts fed by a single fetch; the other views one.
function chart_view():bool{
    $tpl=new template_admin();
    $period=osm_period();
    $view=(string)($_GET["chart-view"] ?? "overview");
    $views=array("overview","cpu","memory","load");
    if(!in_array($view,$views,true)){$view="overview";}
    $t=time().mt_rand(100,999);

    $cards=array();
    if($view=="overview"){
        $cards[]=mtk_chart_card("mtk-$t-cm",osm_period_title($period),"{osm_chart_cpu_mem_explain}",osm_legend_cpu_mem());
        $cards[]=mtk_chart_card("mtk-$t-ld",osm_period_title($period),"{osm_chart_load_explain}",osm_legend_load());
        $specs=array(array("id"=>"mtk-$t-cm","kind"=>"cpumem"),array("id"=>"mtk-$t-ld","kind"=>"load"));
    }
    if($view=="cpu"){
        $cards[]=mtk_chart_card("mtk-$t-c",osm_period_title($period),"{osm_chart_cpu_explain}",osm_legend_cpu());
        $specs=array(array("id"=>"mtk-$t-c","kind"=>"cpu"));
    }
    if($view=="memory"){
        $cards[]=mtk_chart_card("mtk-$t-m",osm_period_title($period),"{osm_chart_mem_explain}",osm_legend_memory());
        $specs=array(array("id"=>"mtk-$t-m","kind"=>"memory"));
    }
    if($view=="load"){
        $cards[]=mtk_chart_card("mtk-$t-l",osm_period_title($period),"{osm_chart_load_explain}",osm_legend_load());
        $specs=array(array("id"=>"mtk-$t-l","kind"=>"load"));
    }
    echo mtk_css()."<div class='mtkw'>".$tpl->_ENGINE_parse_body(@implode("\n",$cards))."</div>".osm_chart_js($tpl,$period,$specs);
    return true;
}


function osm_legend_cpu_mem():string{
    return "<div class='mtk-leg'>".mtk_legend_item("#14b8a6","rgba(204,251,241,.6)","CPU %")
        .mtk_legend_item("#f43f5e","rgba(255,228,230,.6)","{memory} %")."</div>";
}
function osm_legend_cpu():string{
    return "<div class='mtk-leg'>".mtk_legend_item("#14b8a6","rgba(204,251,241,.6)","CPU %")."</div>";
}
function osm_legend_memory():string{
    return "<div class='mtk-leg'>".mtk_legend_item("#f43f5e","rgba(255,228,230,.6)","{memory} %")
        .mtk_legend_item("#64748b","transparent","{memory} (MB)")."</div>";
}
function osm_legend_load():string{
    return "<div class='mtk-leg'>".mtk_legend_item("#d97706","rgba(254,243,199,.6)","1 min")
        .mtk_legend_item("#2563eb","transparent","5 min")
        .mtk_legend_item("#94a3b8","transparent","15 min")."</div>";
}

// Chart.js renderer for one pane. Kept outside _ENGINE_parse_body (JS braces would be
// read as {tokens}); the few labels it needs are resolved here and passed as JSON.
function osm_chart_js($tpl,$period,array $specs):string{
    $page=CurrentPageName();
    $L=array(
        "memory"=>$tpl->javascript_parse_text("{memory}"),
        "used"=>$tpl->javascript_parse_text("{memory} (MB)"),
        "nodata"=>$tpl->javascript_parse_text("{no_data}"),
        "cpus"=>$tpl->javascript_parse_text("{processors}"),
    );
    $jsL=json_encode($L);
    $jsSpecs=json_encode($specs);
    $period=intval($period);
    $js='
<script>
(function(){
 var L=__L__, SPECS=__SPECS__, HOURS=__HOURS__;
 window.__osmCharts=window.__osmCharts||{};
 function ensure(cb){ if(typeof Chart!=="undefined"){cb();return;}
   var s=document.createElement("script"); s.src="angular/js/plugins/chartJs/Chart.min.4.5.1.js";
   s.onload=function(){cb();}; document.head.appendChild(s); }
 function fmtMB(v){ if(v===null||v===undefined){return "-";} if(v<1024){return Math.round(v)+" MB";} return (v/1024).toFixed(1)+" GB"; }
 function grad(hex,a){ return function(c){ var ch=c.chart, area=ch.chartArea; if(!area){return "rgba(0,0,0,0)";}
   var g=ch.ctx.createLinearGradient(0,area.top,0,area.bottom);
   g.addColorStop(0,"rgba("+hex+","+a+")"); g.addColorStop(.6,"rgba("+hex+","+(a/2.5)+")"); g.addColorStop(1,"rgba("+hex+",0)"); return g; }; }
 function line(label,data,color,fillRgb,axis,extra){
   var n=data.length, d={label:label,data:data,borderColor:color,borderWidth:2.2,tension:.4,spanGaps:true,yAxisID:axis||"y",
     pointRadius:(n>90?0:2.5),pointHoverRadius:5,pointBackgroundColor:color,pointBorderWidth:0,
     fill:!!fillRgb,backgroundColor:fillRgb?grad(fillRgb,.32):"transparent"};
   if(extra){for(var k in extra){d[k]=extra[k];}}
   return d; }
 function baseOptions(){
   return {responsive:true,maintainAspectRatio:false,animation:false,
     interaction:{mode:"index",intersect:false},
     plugins:{legend:{display:false},
       tooltip:{backgroundColor:"rgba(15,23,42,.95)",borderColor:"#334155",borderWidth:1,padding:10,cornerRadius:8,
         titleColor:"#cbd5e1",titleFont:{weight:"bold",size:11},bodyFont:{family:"monospace",size:12},boxPadding:4}},
     scales:{
       x:{grid:{display:false},border:{color:"#cbd5e1"},ticks:{color:"#64748b",font:{family:"monospace",size:10},maxRotation:0,autoSkip:true,maxTicksLimit:10}},
       y:{beginAtZero:true,grid:{color:"#f1f5f9",borderDash:[4,4]},border:{display:false},ticks:{color:"#64748b",font:{family:"monospace",size:11}}}
     }};
 }
 // Fixed 0-100 flattens an idle box (1 % CPU) into a line on the axis: fit the data, cap at 100.
 function pctMax(arrs){ var m=0; arrs.forEach(function(a){ (a||[]).forEach(function(v){ if(v!==null && v>m){m=v;} }); });
   return Math.min(100,Math.max(5,Math.ceil(m*1.25/5)*5)); }
 function pct(o,arrs){ o.scales.y.max=pctMax(arrs); o.scales.y.ticks.callback=function(v){return v+" %";};
   o.plugins.tooltip.callbacks={label:function(c){ if(c.raw===null){return null;} return " "+c.dataset.label+": "+Number(c.raw).toFixed(1)+" %"; }}; return o; }
 function build(kind,d){
   var o=baseOptions(), ds=[];
   if(kind==="cpumem"){ ds.push(line("CPU",d.cpu,"#0d9488","13,148,136")); ds.push(line(L.memory,d.mem,"#f43f5e","244,63,94")); pct(o,[d.cpu,d.mem]); }
   if(kind==="cpu"){ ds.push(line("CPU",d.cpu,"#0d9488","13,148,136")); pct(o,[d.cpu]); }
   if(kind==="memory"){
     ds.push(line(L.memory,d.mem,"#f43f5e","244,63,94"));
     ds.push(line(L.used,d.mem_used_mb,"#64748b",null,"y1",{borderWidth:1.5,borderDash:[5,4],pointRadius:0}));
     pct(o,[d.mem]);
     o.scales.y1={position:"right",beginAtZero:true,grid:{drawOnChartArea:false},border:{display:false},ticks:{color:"#64748b",font:{family:"monospace",size:11},callback:function(v){return fmtMB(v);}}};
     o.plugins.tooltip.callbacks={label:function(c){ if(c.raw===null){return null;}
       if(c.dataset.yAxisID==="y1"){return " "+c.dataset.label+": "+fmtMB(c.raw);} return " "+c.dataset.label+": "+Number(c.raw).toFixed(1)+" %"; }};
   }
   if(kind==="load"){
     ds.push(line("1 min",d.load1,"#d97706","217,119,6"));
     ds.push(line("5 min",d.load5,"#2563eb",null,"y",{borderWidth:1.6,pointRadius:0}));
     ds.push(line("15 min",d.load15,"#94a3b8",null,"y",{borderWidth:1.6,pointRadius:0}));
     o.plugins.tooltip.callbacks={label:function(c){ if(c.raw===null){return null;} return " "+c.dataset.label+": "+Number(c.raw).toFixed(2); }};
   }
   return {type:"line",data:{labels:d.labels,datasets:ds},options:o};
 }
 function empty(id){ var b=document.getElementById(id+"-box"); if(b){ b.className="mtk-empty"; b.textContent=L.nodata; } }
 ensure(function(){
   fetch("__PAGE__?api-chart=yes&hours="+HOURS,{cache:"no-store",credentials:"same-origin"})
   .then(function(r){return r.text();})
   .then(function(txt){
     var json=null; try{ json=JSON.parse(txt); }catch(e){}
     var ok=json && json.success && json.data && json.data.labels && json.data.labels.length>0;
     SPECS.forEach(function(s){
       var cv=document.getElementById(s.id); if(!cv){return;}
       if(!ok){ empty(s.id); return; }
       if(window.__osmCharts[s.kind]){ try{ window.__osmCharts[s.kind].destroy(); }catch(e){} }
       window.__osmCharts[s.kind]=new Chart(cv.getContext("2d"),build(s.kind,json.data));
     });
   })
   .catch(function(e){ console.error("os metrics chart:",e); SPECS.forEach(function(s){empty(s.id);}); });
 });
})();
</script>';
    return str_replace(array("__L__","__SPECS__","__HOURS__","__PAGE__"),array($jsL,$jsSpecs,$period,$page),$js);
}

function api_call($endpoint) {
    return $GLOBALS["CLASS_SOCKETS"]->REST_API($endpoint);
}

function api_status() {
    header('Content-Type: application/json');
    echo api_call("/sysmonitor/status");
}

function api_stats() {
    header('Content-Type: application/json');
    echo api_call("/sysmonitor/stats");
}

function api_metrics() {
    header('Content-Type: application/json');
    $hours = isset($_GET["hours"]) ? intval($_GET["hours"]) : 24;
    echo api_call("/sysmonitor/metrics?hours={$hours}");
}

function api_chart() {
    header('Content-Type: application/json');
    $hours = isset($_GET["hours"]) ? intval($_GET["hours"]) : 24;
    echo api_call("/sysmonitor/chart?hours={$hours}");
}

function api_latest() {
    header('Content-Type: application/json');
    echo api_call("/sysmonitor/latest");
}

function api_start() {
    header('Content-Type: application/json');
    echo api_call("/sysmonitor/start");
}

function api_stop() {
    header('Content-Type: application/json');
    echo api_call("/sysmonitor/stop");
}

function api_purge():bool {
    header('Content-Type: application/json');
    $GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/sysmonitor/purge");
    return true;
}
