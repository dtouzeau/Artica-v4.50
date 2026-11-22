<?php
/**
 * System I/O Metrics Dashboard
 *
 * Displays system and per-process I/O metrics using Chart.js.
 * Data is collected every 3 minutes for 24 hours (detailed) and
 * daily aggregates are kept for 30 days.
 * Design: /home/claude/images/stitch_disk_io_metrics, kit in ressources/class.metrics.kit.inc.
 *
 * API Endpoints Used:
 * - GET /iomonitor/status - Check if IOMonitor is running
 * - GET /iomonitor/stats - Get database statistics
 * - GET /iomonitor/metrics/system?hours=N - System-wide I/O metrics
 * - GET /iomonitor/metrics/system/daily?days=N - Daily system aggregates
 * - GET /iomonitor/metrics/top?hours=N&limit=N - Top I/O consuming processes
 * - GET /iomonitor/metrics/process/{name}?hours=N - Per-process history
 * - GET /iomonitor/processes?hours=N - List of processes with I/O activity
 */

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
// awc_table_style() / awc_pager_js() / siem_tiny_filters(): shared table and header-filter kit
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
if(isset($_GET["processhist-js"])){process_hist_js();exit;}
if(isset($_GET["process-hist-popup"])){process_hist_popup();exit;}
if(isset($_GET["processes-list"])){processes_list();exit;}
if(isset($_GET["top-widgets"])){top_widgets();exit;}
if(isset($_GET["build-tabs"])){build_tabs();exit;}
if(isset($_GET["system-view"])){system_view();exit;}
if(isset($_GET["top-view"])){top_view();exit;}
if(isset($_GET["api-status"])) { api_status(); exit; }
if(isset($_GET["api-stats"])) { api_stats(); exit; }
if(isset($_GET["api-system-metrics"])) { api_system_metrics(); exit; }
if(isset($_GET["api-daily-metrics"])) { api_daily_metrics(); exit; }
if(isset($_GET["api-top-processes"])) { api_top_processes(); exit; }
if(isset($_GET["api-process-history"])) { api_process_history(); exit; }
if(isset($_GET["api-processes-list"])) { api_processes_list(); exit; }
if(isset($_GET["api-start"])) { api_start(); exit; }
if(isset($_GET["api-stop"])) { api_stop(); exit; }
if(isset($_GET["status"])){status();exit;}

// Default: show main page
page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{io_metrics_title}","fas fa-chart-line","{io_mon_explain}",
        "$page?status=yes","io-metrics","progress-metrics-io-restart",false,"table-loader-metrics-io");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// Periods of the header, in hours. The 3-minute samples are kept 24 hours; 30 days
// comes from the daily averages, which exist for the whole system only.
function iomon_periods():array{
    return array(1=>"1h",6=>"6h",24=>"24h",720=>"30d");
}
function iomon_period():int{
    $p=intval($_GET["period"] ?? 1);
    $periods=iomon_periods();
    if(!isset($periods[$p])){return 1;}
    return $p;
}
// Per-process data has no daily aggregate: past 24 h it is the last 24 h.
function iomon_detail_hours($period):int{
    return min(24,intval($period));
}
function iomon_period_title($hours):string{
    $titles=array(1=>"{last_hour}",6=>"{last_6_hours}",24=>"{last_24_h}",720=>"{last_30_days}");
    return isset($titles[$hours])?$titles[$hours]:"{last_hour}";
}

function iomon_get($endpoint):array{
    $json=json_decode((string)api_call($endpoint),true);
    if(!is_array($json)){return array();}
    return $json;
}

function top_widgets(){
    $tpl=new template_admin();
    $status=iomon_get("/iomonitor/stats");
    if(!isset($status["data"]) || !is_array($status["data"])){
        echo mtk_css().$tpl->_ENGINE_parse_body($tpl->div_error("{protocol_error}||/iomonitor/stats"));
        return false;
    }
    $data=$status["data"];

    $last=array();
    $sys=iomon_get("/iomonitor/metrics/system?hours=1");
    if(isset($sys["data"]) && is_array($sys["data"]) && count($sys["data"])>0){
        $last=$sys["data"][count($sys["data"])-1];
    }
    $procs=array();
    $top=iomon_get("/iomonitor/metrics/top?hours=1&limit=100");
    if(isset($top["data"]) && is_array($top["data"])){$procs=$top["data"];}

    $html=array();
    $html[]="<div class='mtkw'><div class='mtk-kpis'>";
    $html[]=iomon_kpi_database($data);
    $html[]=iomon_kpi_readwrite($tpl,$last,mtk_duration_seconds($data["collection_interval"] ?? ""));
    $html[]=iomon_kpi_service($data,count($procs));
    $html[]=iomon_kpi_top_process($procs);
    $html[]="</div></div>";
    echo mtk_css().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}


function iomon_kpi_database(array $data):string{
    list($num,$unit)=mtk_split_size($data["db_size_human"] ?? "");
    $detail=mtk_duration_hours($data["detailed_retention"] ?? "");
    $daily=intval(mtk_duration_hours($data["daily_retention"] ?? "")/24);
    $value="<div class='mtk-kpi-val'><span class='mtk-kpi-num'>".htmlspecialchars($num)."</span>"
          ."<span class='mtk-kpi-unit'>".htmlspecialchars($unit)."</span></div>";
    $foot="<span><span class='mtk-chip'>{retention}</span>".($detail>0?"{$detail} h":"-")
         ." &middot; ".($daily>0?"$daily {days}":"-")."</span>";
    return mtk_kpi("{database_size}",$value,ico_database,$foot);
}

// The daemon stores what was read/written during each sample, not a rate.
function iomon_kpi_readwrite($tpl,array $last,$interval):string{
    if(count($last)==0){
        return mtk_kpi("{read} / {write}","<div class='mtk-kpi-val'><span class='mtk-kpi-num'>-</span></div>",
            "fas fa-terminal","<span>{no_data_available}</span>");
    }
    $read=floatval($last["read"] ?? 0);
    $write=floatval($last["write"] ?? 0);
    $ts=intval($last["timestamp"] ?? 0);
    $when=($ts>0)?" &middot; ".date("H:i:s",$ts):"";
    list($rn,$ru)=mtk_bytes_parts($read);
    list($wn,$wu)=mtk_bytes_parts($write);
    $value="<div class='mtk-kpi-pair'><span class='mtk-c-read'>$rn<small>$ru</small></span>"
          ."<span class='mtk-kpi-sep'>/</span><span class='mtk-c-write'>$wn<small>$wu</small></span></div>";
    $foot="<span>";
    if($interval>0){
        $foot.="<span class='mtk-dot mtk-c-read'>{read} ".mtk_bytes($read/$interval)."/s</span>";
        $foot.="<span class='mtk-dot mtk-c-write'>{write} ".mtk_bytes($write/$interval)."/s</span>";
    }
    $foot.="</span>";
    return mtk_kpi("{read} / {write}$when",$value,"fas fa-terminal",$foot);
}

function iomon_kpi_service(array $data,$nprocs):string{
    $running=!empty($data["running"]);
    $value="<div class='mtk-kpi-val'><span class='mtk-kpi-num'>".($running?"{running}":"{stopped}")."</span></div>";
    $foot="<span class='mtk-live'><span class='mtk-pulse".($running?"":" mtk-pulse-off")."'></span>"
         ."{processes}: ".intval($nprocs)."</span>"
         ."<span class='mtk-mono'>".htmlspecialchars(mtk_interval_label($data["collection_interval"] ?? ""))."</span>";
    return mtk_kpi("{status}",$value,$running?"fas fa-play":"fas fa-stop",$foot,!$running);
}

// Replaces the mock-up's latency card: the daemon measures volumes, not latency, and
// "who is writing" is the question an I/O spike actually raises.
function iomon_kpi_top_process(array $procs):string{
    if(count($procs)==0 || !is_array($procs[0])){
        return mtk_kpi("{io_top_process}","<div class='mtk-kpi-name'>-</div>","fas fa-ranking-star",
            "<span>{no_data_available}</span>");
    }
    $all=0;
    foreach($procs as $p){ if(is_array($p)){ $all+=floatval($p["total_io"] ?? 0); } }
    $first=$procs[0];
    $name=(string)($first["name"] ?? "-");
    $io=floatval($first["total_io"] ?? 0);
    $share=($all>0)?round($io*100/$all):0;
    $value="<div class='mtk-kpi-name' title=\"".htmlspecialchars($name)."\">".htmlspecialchars($name)."</div>";
    $foot="<span>".mtk_bytes($io)." &middot; {last_hour}</span>"
         ."<span class='mtk-mono'>$share %</span>";
    return mtk_kpi("{io_top_process}",$value,"fas fa-ranking-star",$foot);
}

function status():bool {
    $page = CurrentPageName();
    $tpl = new template_admin();
    $period=iomon_period();
    $html=array();
    $html[]="<div class='mtkw'>";
    $html[]="<div id='io-widgets'></div>";
    $html[]="<div id='io-tabs' style='margin-top:20px'></div>";
    $html[]="</div>";
    // Concatenated AFTER the parse: the engine would read the JS braces as {tokens}.
    $script=array();
    $script[]="<script>";
    $script[]="function IoPeriod(p){LoadAjaxSilent('io-tabs','$page?build-tabs=yes&period='+encodeURIComponent(p));}";
    $script[]=$tpl->RefreshInterval_js("io-widgets",$page,"top-widgets=yes",15);
    $script[]="LoadAjaxSilent('io-tabs','$page?build-tabs=yes&period=$period');";
    $script[]="</script>";
    echo mtk_css().$tpl->_ENGINE_parse_body(@implode("\n",$html)).@implode("\n",$script);
    return true;
}

// Rebuilt on every period change, the TINY after the tabs so the segment state matches.
function build_tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $period=iomon_period();

    $tabs=array();
    $tabs["{your_system}"]="$page?system-view=yes&period=$period";
    $tabs["{top_processes}"]="$page?top-view=yes&period=$period";
    $tabs["{processes}"]="$page?processes-list=yes&period=$period";

    $fields=array();
    $fields[]=array("TYPE"=>"segment","ID"=>"io-period","LABEL"=>"{period}","VALUE"=>(string)$period,
        "OPTIONS"=>iomon_periods(),"JS"=>"IoPeriod");
    $topbuttons=array();
    $topbuttons[]=array("IoPeriod('$period')",ico_refresh,"{refresh}");
    $TINY=array();
    $TINY["TITLE"]="{io_metrics_title}";
    $TINY["ICO"]="fas fa-chart-line";
    $TINY["EXPL"]="{io_mon_explain}";
    $TINY["URL"]="io-metrics";
    $TINY["BUTTONS"]=siem_tiny_filters($fields,$tpl->table_buttons($topbuttons));

    echo siem_tiny_filters_style().mtk_css().$tpl->tabs_default($tabs)
        ."<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";
    return true;
}

function iomon_legend_rw():string{
    return mtk_legend(array(
        mtk_legend_item("#14b8a6","rgba(204,251,241,.6)","{read}"),
        mtk_legend_item("#f43f5e","rgba(255,228,230,.6)","{write}")));
}

// Labels the JS needs, resolved here: the script itself never goes through the parser.
function iomon_js_labels($tpl):string{
    return json_encode(array(
        "read"=>$tpl->javascript_parse_text("{read}"),
        "write"=>$tpl->javascript_parse_text("{write}"),
        "nodata"=>$tpl->javascript_parse_text("{no_data_available}"),
    ));
}

function system_view():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $period=iomon_period();
    $id="io-sys-".time().mt_rand(100,999);
    $daily=($period>24);
    $explain=$daily?"{io_chart_daily_explain}":"{io_chart_system_explain}";
    $card=mtk_chart_card($id,iomon_period_title($period),$explain,iomon_legend_rw());
    $url=$daily ? "$page?api-daily-metrics=yes&days=30" : "$page?api-system-metrics=yes&hours=$period";

    $js='
<script>
(function(){
 var L=__L__, ID="__ID__", DAILY=__DAILY__;
 Mtk.ensure(function(){
  fetch("__URL__",{cache:"no-store",credentials:"same-origin"}).then(function(r){return r.text();}).then(function(txt){
   var j=null; try{ j=JSON.parse(txt); }catch(e){}
   var rows=(j && j.data && j.data.length)?j.data:[];
   var cv=document.getElementById(ID); if(!cv){return;}
   if(rows.length===0){ Mtk.empty(ID,L.nodata); return; }
   var labels=[], rd=[], wr=[];
   rows.forEach(function(p){
     if(DAILY){ labels.push(p.date); rd.push(Number(p.avg_read_bytes)||0); wr.push(Number(p.avg_write_bytes)||0); }
     else { labels.push(Mtk.timeLabel(p.timestamp)); rd.push(Number(p.read)||0); wr.push(Number(p.write)||0); }
   });
   var o=Mtk.options();
   o.scales.y.ticks.callback=function(v){return Mtk.bytes(v);};
   o.plugins.tooltip.callbacks={label:function(c){ return " "+c.dataset.label+": "+Mtk.bytes(c.raw); }};
   Mtk.keep("io-sys",new Chart(cv.getContext("2d"),{type:"line",data:{labels:labels,datasets:[
     Mtk.line(L.read,rd,"#0d9488","13,148,136"), Mtk.line(L.write,wr,"#f43f5e","244,63,94")]},options:o}));
  }).catch(function(e){ console.error("io system chart:",e); Mtk.empty(ID,L.nodata); });
 });
})();
</script>';
    $js=str_replace(array("__L__","__ID__","__DAILY__","__URL__"),
        array(iomon_js_labels($tpl),$id,$daily?"true":"false",$url),$js);
    echo mtk_css()."<div class='mtkw'>".$tpl->_ENGINE_parse_body($card)."</div>".mtk_chart_lib_js().$js;
    return true;
}

function top_view():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $period=iomon_period();
    $hours=iomon_detail_hours($period);
    $limit=15;
    $id="io-top-".time().mt_rand(100,999);
    $note=($hours<$period)?"{io_detail_kept_24h}":"";
    // One bar pair per process: the plot grows with the list instead of squeezing it.
    $card=mtk_chart_card($id,iomon_period_title($hours),"{io_chart_top_explain}",iomon_legend_rw(),$note,$limit*30+60);

    $js='
<script>
(function(){
 var L=__L__, ID="__ID__";
 Mtk.ensure(function(){
  fetch("__PAGE__?api-top-processes=yes&hours=__HOURS__&limit=__LIMIT__",{cache:"no-store",credentials:"same-origin"})
  .then(function(r){return r.text();}).then(function(txt){
   var j=null; try{ j=JSON.parse(txt); }catch(e){}
   var rows=(j && j.data && j.data.length)?j.data:[];
   var cv=document.getElementById(ID); if(!cv){return;}
   if(rows.length===0){ Mtk.empty(ID,L.nodata); return; }
   var box=document.getElementById(ID+"-box"); if(box){ box.style.height=(rows.length*30+60)+"px"; }
   var labels=[], rd=[], wr=[];
   rows.forEach(function(p){ labels.push(p.process_name||p.name||"?"); rd.push(Number(p.total_read||p.read)||0); wr.push(Number(p.total_write||p.write)||0); });
   var o=Mtk.options();
   o.indexAxis="y";
   o.interaction={mode:"index",intersect:false,axis:"y"};
   o.scales={
     x:{beginAtZero:true,grid:{color:"#f1f5f9",borderDash:[4,4]},border:{display:false},ticks:{color:"#64748b",font:{family:"monospace",size:10},callback:function(v){return Mtk.bytes(v);}}},
     y:{grid:{display:false},border:{color:"#cbd5e1"},ticks:{color:"#334155",font:{size:12,weight:"600"}}}};
   o.plugins.tooltip.callbacks={label:function(c){ return " "+c.dataset.label+": "+Mtk.bytes(c.raw); }};
   Mtk.keep("io-top",new Chart(cv.getContext("2d"),{type:"bar",data:{labels:labels,datasets:[
     {label:L.read,data:rd,backgroundColor:"rgba(13,148,136,.85)",borderRadius:4,barPercentage:.8,categoryPercentage:.7},
     {label:L.write,data:wr,backgroundColor:"rgba(244,63,94,.85)",borderRadius:4,barPercentage:.8,categoryPercentage:.7}]},options:o}));
  }).catch(function(e){ console.error("io top chart:",e); Mtk.empty(ID,L.nodata); });
 });
})();
</script>';
    $js=str_replace(array("__L__","__ID__","__PAGE__","__HOURS__","__LIMIT__"),
        array(iomon_js_labels($tpl),$id,$page,$hours,$limit),$js);
    echo mtk_css()."<div class='mtkw'>".$tpl->_ENGINE_parse_body($card)."</div>".mtk_chart_lib_js().$js;
    return true;
}

function api_call($endpoint) {
    return $GLOBALS["CLASS_SOCKETS"]->REST_API($endpoint);
}

function api_status() {
    header('Content-Type: application/json');
    echo api_call("/iomonitor/status");
}

function api_stats() {
    header('Content-Type: application/json');
    echo api_call("/iomonitor/stats");
}

function api_system_metrics() {
    header('Content-Type: application/json');
    $hours = isset($_GET["hours"]) ? intval($_GET["hours"]) : 24;
    echo api_call("/iomonitor/metrics/system?hours={$hours}");
}

function api_daily_metrics() {
    header('Content-Type: application/json');
    $days = isset($_GET["days"]) ? intval($_GET["days"]) : 30;
    echo api_call("/iomonitor/metrics/system/daily?days={$days}");
}

function api_top_processes() {
    header('Content-Type: application/json');
    $hours = isset($_GET["hours"]) ? intval($_GET["hours"]) : 24;
    $limit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 20;
    echo api_call("/iomonitor/metrics/top?hours={$hours}&limit={$limit}");
}

function api_process_history() {
    header('Content-Type: application/json');
    $name = isset($_GET["name"]) ? rawurlencode($_GET["name"]) : "";
    $hours = isset($_GET["hours"]) ? intval($_GET["hours"]) : 24;
    echo api_call("/iomonitor/metrics/process/{$name}?hours={$hours}");
}

function api_processes_list() {
    header('Content-Type: application/json');
    $hours = isset($_GET["hours"]) ? intval($_GET["hours"]) : 24;
    echo api_call("/iomonitor/processes?hours={$hours}");
}

function api_start() {
    header('Content-Type: application/json');
    echo api_call("/iomonitor/start");
}

function api_stop() {
    header('Content-Type: application/json');
    echo api_call("/iomonitor/stop");
}

function process_hist_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $name=(string)$_GET["processhist-js"];
    $hours=iomon_detail_hours(intval($_GET["hours"] ?? 24));
    if($hours<1){$hours=1;}
    $title=htmlspecialchars($name);
    return $tpl->js_dialog2("$title - $hours {hours}","$page?process-hist-popup=".urlencode($name)."&hours=$hours",900);
}

function process_hist_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $name=(string)($_GET["process-hist-popup"] ?? "");
    $hours=iomon_detail_hours(intval($_GET["hours"] ?? 24));
    if($hours<1){$hours=1;}
    $id="io-proc-".time().mt_rand(100,999);
    $card=mtk_chart_card($id,htmlspecialchars($name)." &middot; ".iomon_period_title($hours),
        "{io_chart_process_explain}",iomon_legend_rw());

    $js='
<script>
(function(){
 var L=__L__, ID="__ID__";
 Mtk.ensure(function(){
  fetch("__PAGE__?api-process-history=yes&name=__NAME__&hours=__HOURS__",{cache:"no-store",credentials:"same-origin"})
  .then(function(r){return r.text();}).then(function(txt){
   var j=null; try{ j=JSON.parse(txt); }catch(e){}
   var rows=(j && j.data && j.data.length)?j.data:[];
   var cv=document.getElementById(ID); if(!cv){return;}
   if(rows.length===0){ Mtk.empty(ID,L.nodata); return; }
   var labels=[], rd=[], wr=[];
   rows.forEach(function(p){ labels.push(Mtk.timeLabel(p.timestamp)); rd.push(Number(p.read_bytes)||0); wr.push(Number(p.write_bytes)||0); });
   var o=Mtk.options();
   o.scales.y.ticks.callback=function(v){return Mtk.bytes(v);};
   o.plugins.tooltip.callbacks={label:function(c){ return " "+c.dataset.label+": "+Mtk.bytes(c.raw); }};
   Mtk.keep("io-proc",new Chart(cv.getContext("2d"),{type:"line",data:{labels:labels,datasets:[
     Mtk.line(L.read,rd,"#0d9488","13,148,136"), Mtk.line(L.write,wr,"#f43f5e","244,63,94")]},options:o}));
  }).catch(function(e){ console.error("io process chart:",e); Mtk.empty(ID,L.nodata); });
 });
})();
</script>';
    $js=str_replace(array("__L__","__ID__","__PAGE__","__NAME__","__HOURS__"),
        array(iomon_js_labels($tpl),$id,$page,rawurlencode($name),$hours),$js);
    echo mtk_css()."<div class='mtkw' style='margin-top:-20px'>".$tpl->_ENGINE_parse_body($card)."</div>".mtk_chart_lib_js().$js;
    return true;
}

function processes_list():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $period=iomon_period();
    $hours=iomon_detail_hours($period);

    $data=iomon_get("/iomonitor/metrics/top?hours={$hours}&limit=100");
    $processes=(isset($data["data"]) && is_array($data["data"]))?$data["data"]:array();

    $html=array();
    if($hours<$period){
        $html[]="<div class='mtk-note' style='margin-top:12px'><i class='fas fa-circle-info'></i> {io_detail_kept_24h}</div>";
    }
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th class='center'>#</th>";
    $html[]="<th>{process_name}</th>";
    $html[]="<th class='center'>{read}</th>";
    $html[]="<th class='center'>{write}</th>";
    $html[]="<th class='center'>{total}</th>";
    $html[]="</tr></thead><tbody id='iop-rows'>";
    $rank=1;
    foreach($processes as $proc){
        if(!is_array($proc)){continue;}
        $html[]=iomon_process_row($tpl,$page,$proc,$rank,$hours);
        $rank++;
    }
    if($rank==1){
        $html[]="<tr><td colspan='5'><div class='awc-empty'>{no_data_available}</div></td></tr>";
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} ".($rank-1)." &middot; ".iomon_period_title($hours)."</span>";
    $html[]="<span class='awc-pg' id='iop-pager'></span>";
    $html[]="</div></div></div>";

    // awc_pager_js() AFTER the table: the <tr> must exist in the DOM.
    echo mtk_css().awc_table_style()
        ."<div class='mtkw'>".$tpl->_ENGINE_parse_body(@implode("\n",$html))."</div>"
        ."<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>"
        .awc_pager_js(50,"iop-rows","iop-pager");
    return true;
}

/**
 * One I/O-consuming process. The rank carries the top-3 highlight (an inline
 * background on the row could not be overridden by the table's hover).
 */
function iomon_process_row($tpl,$page,array $proc,$rank,$hours):string{
    $rawname=(string)($proc["process_name"] ?? ($proc["name"] ?? "unknown"));
    $nameEnc=urlencode((string)($proc["name"] ?? $rawname));
    $read =floatval($proc["total_read"]  ?? ($proc["read"]  ?? 0));
    $write=floatval($proc["total_write"] ?? ($proc["write"] ?? 0));
    $total=$read+$write;
    $top=($rank<=3);

    $link="<a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?processhist-js=$nameEnc&hours=$hours');\">"
         .htmlspecialchars($rawname)."</a>";

    $rankCell=$top
        ? "<span class='awc-badge' style='background:#865300'>$rank</span>"
        : "<span class='awc-mono'>$rank</span>";

    $h ="<tr>";
    $h.="<td class='center awc-nowrap' style='width:1%'>$rankCell</td>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-terminal'></i></span>";
    $h.="<span class='awc-name'>$link</span>";
    $h.="</div></td>";
    $h.="<td class='center awc-nowrap'><span class='awc-badge awc-badge-on'>".mtk_bytes($read)."</span></td>";
    $h.="<td class='center awc-nowrap'><span class='awc-badge' style='background:#e11d48'>".mtk_bytes($write)."</span></td>";
    $h.="<td class='center awc-nowrap'><strong>".mtk_bytes($total)."</strong></td>";
    $h.="</tr>";
    return $h;
}
