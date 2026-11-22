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
include_once(dirname(__FILE__)."/ressources/class.lowmem.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// Constantes avant l'aiguillage : un define() plus bas n'est jamais exécuté par les handlers AJAX.
define("DSTT_LIMIT",10);
define("DSTT_CONV_LIMIT",500);
define("DSTT_PARTNERS",5);
define("DSPS_EPHEMERAL",32768);
define("DSPS_LIMIT",30);
define("DSPS_LIMIT_MAX",100);
define("DSST_DB_MAX",1073741824);
define("DSST_LOST_WARN",1.0);
define("DSST_SAVE_STALE",120);
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors',1);ini_set('error_reporting',E_ALL);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
if(isset($_GET["darkstats-db-status"])){darkstats_db_status();exit;}
if(isset($_GET["remove-database-js"])){remove_database_js();exit;}
if(isset($_GET["uninstall-js"])){uninstall_js();exit;}
if(isset($_GET["install-js"])){install_js();exit;}
if(isset($_POST["uninstall"])){uninstall_perform();exit;}
if(isset($_POST["remove-database"])){remove_database_peform();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["tab-traffic"])){tab_traffic();exit;}
if(isset($_GET["tab-top-hosts"])){tab_top_hosts();exit;}
if(isset($_GET["tab-protocols"])){tab_protocols();exit;}
if(isset($_GET["tab-ports"])){tab_ports();exit;}
if(isset($_GET["tab-interfaces"])){tab_interfaces();exit;}
if(isset($_GET["tab-hosts"])){tab_hosts();exit;}
if(isset($_GET["tab-status"])){tab_status();exit;}
if(isset($_GET["ports-list"])){ports_list();exit;}
if(isset($_GET["chart-overview"])){chart_overview();exit;}
if(isset($_GET["chart-top-hosts"])){chart_top_hosts();exit;}
if(isset($_GET["chart-protocols"])){chart_protocols();exit;}
if(isset($_GET["chart-iface-ts"])){chart_interface_timeseries();exit;}
if(isset($_GET["hosts-table"])){hosts_table();exit;}
if(isset($_GET["status-host"])){status_host();exit;}

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
    // Les clés minute/heure sont en UTC (DarkStats/keys.go) : on les convertit
    // dans le fuseau de l'appliance avant de les afficher.
    if($len>=10 && ctype_digit(substr($raw,0,10))){
        $ts=gmmktime(intval(substr($raw,8,2)),$len>=12?intval(substr($raw,10,2)):0,0,
            intval(substr($raw,4,2)),intval(substr($raw,6,2)),intval(substr($raw,0,4)));
        $raw=date($len>=12?"YmdHi":"YmdH",$ts);
        $len=strlen($raw);
    }
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

// ─── Helper: one Chart.js instance per slot, destroyed before redraw ───
function _ds_chart_helper_js():string{
    return "window.DsChart=window.DsChart||function(s,id,cfg){window.DsCharts=window.DsCharts||{};"
        ."if(window.DsCharts[s]){try{window.DsCharts[s].destroy();}catch(e){}window.DsCharts[s]=null;}"
        ."var c=document.getElementById(id);if(!c||typeof Chart==='undefined'){return;}window.DsCharts[s]=new Chart(c,cfg);};";
}

// ─── Main page shell ───
function Start(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html=$tpl->page_header("{network_traffic_statistics}",
        "fas fa-chart-area","{network_traffic}","$page?tabs=yes",
        "darkstats","darkstats-progress",false,"darkstats-div");
    echo $tpl->_ENGINE_parse_body($html);
}
function remove_database_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $js="LoadAjax('darkstats-div','$page?tabs=yes');";
    return $tpl->js_confirm_delete("{darkstats_empty_confirm}","remove-database","yes",$js,true);
}
function uninstall_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $js="LoadAjax('darkstats-div','$page?tabs=yes');";
    return $tpl->js_confirm_delete("{darkstats_uninstall_confirm}","uninstall","yes",$js,true);
}
// Les trois actions n'avaient aucune vérification de droit côté serveur.
function _ds_can_manage():bool{
    $tpl=new template_admin();
    return $tpl->IsSecurity("AsSystemAdministrator");
}
function remove_database_peform():bool{
    if(!_ds_can_manage()){$tpl=new template_admin();echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/darkstats/empty",array());
    return admin_tracks("Empty the DarkStats Database");
}
function install_js():bool{
    if(!_ds_can_manage()){$tpl=new template_admin();$tpl->js_error("{ERROR_NO_PRIVS2}");return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/darkstats/install");
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $js="LoadAjax('darkstats-div','$page?tabs=yes');";
    echo $js;
    return admin_tracks("Install the DarkStats Database");
}
function uninstall_perform():bool{
    if(!_ds_can_manage()){$tpl=new template_admin();echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/darkstats/uninstall");
    return admin_tracks("Uninstall the DarkStats Database");
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

    $TINY_ARRAY["TITLE"]="{network_traffic_statistics}";
    $TINY_ARRAY["ICO"]="fas fa-chart-area";
    $TINY_ARRAY["EXPL"]="{network_traffic}: <span id='darkstats-db-status'></span><br>{IPAUDIT_EXPLAIN}";

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"))==1 OR intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"))==1){
        $html[]=$tpl->div_warning($tpl->_ENGINE_parse_body("{darkstats_warnin_disabled}"));
        $html[]="<script>";
        $html[]="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    // under 2500 MB of RAM articarest does not run DarkStats (low-memory mode): explain it, not empty tabs
    $lowmem=lowmem_warning_text(lowmem_state($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/lowmem"),lowmem_meminfo_bytes()));
    if($lowmem<>""){
        $html=array();
        $html[]=$tpl->div_warning($lowmem);
        $html[]="<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $DisableDarkStats=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableDarkStats"));

    if($DisableDarkStats==0) {
        $topbuttons[] = array("Loadjs('$page?remove-database-js=yes')", "fa-solid fa-broom", "{empty_database}");
        $topbuttons[] = array("Loadjs('$page?uninstall-js=yes')", ico_trash, "{uninstall_feature}");
    }else{
        $topbuttons[] = array("Loadjs('$page?install-js=yes')", ico_cd, "{install_feature}");
    }

    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo $tpl->_ENGINE_parse_body($tpl->tabs_default($array));
    $refresh=$tpl->RefreshInterval_js("darkstats-db-status",$page,"darkstats-db-status=yes");
    echo "<script>$jstiny;$refresh</script>";
}

function darkstats_db_status():bool{
    $tpl=new template_admin();
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/darkstats/health");
    $json=json_decode($raw);

    if(!is_object($json)||!property_exists($json,'success')||!$json->success){
        $status="<span class='badge' style='background:#c0392b;color:#fff'>{darkstats_offline}</span>";
    }else{
        $d=$json->data;
        $color=$d->running?'#27ae60':'#c0392b';
        $statusText=$d->running?'{running}':'{stopped}';
        // {database_size} est « Quarantine and backup size » (jeton d'une autre page).
        $size="{darkstats_db_size}: ".FormatBytes(intval($d->real_file_size??0)/1024);
        $status="<span class='badge' style='background:$color;color:white'>$statusText $size</span>";
    }
    echo $tpl->_ENGINE_parse_body($status);
    return true;
}

// ─── Tab: Top Talkers ───
function tab_top_hosts(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $h=[];
    $h[]="<div id='ds-top-hosts'></div>";
    $h[]="<script>LoadAjax('ds-top-hosts','$page?chart-top-hosts=yes&range=day');</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ─── Tab: Protocols — un seul hôte, rechargé par période (?chart-protocols=yes) ───
function tab_protocols(){
    $page=CurrentPageName();
    echo "<div id='ds-protocols'></div><script>LoadAjax('ds-protocols','$page?chart-protocols=yes&range=day');</script>";
}

// ─── Tab: Ports ───
function tab_ports(){
    $page=CurrentPageName();
    echo "<div id='ds-ports'></div><script>LoadAjax('ds-ports','$page?ports-list=yes&range=day');</script>";
}


// ─── Tab: Status (Health + Overview + DNS) ───
function tab_status(){
    $page=CurrentPageName();
    echo "<div id='ds-status'></div><script>LoadAjax('ds-status','$page?status-host=yes');</script>";
}

// ─── Health widget ───

// ─── Overview stats widget ───

// ─── DNS status widget ───

// ─── Tab: Network traffic (maquette Stitch « network traffic statistics dashboard », 2026-11-20) ───
// Un modèle (dsnt_model) lit les routes une fois ; un widget par fonction.
function tab_traffic(){
    $page=CurrentPageName();
    echo "<div id='ds-traffic'></div><script>LoadAjax('ds-traffic','$page?chart-overview=yes&range=day');</script>";
}
function dsnt_ranges():array{
    return array("last15m"=>"{dsnt_r_15m}","hour"=>"{dsnt_r_1h}","day"=>"{dsnt_r_24h}","week"=>"{dsnt_r_7d}","month"=>"{dsnt_r_30d}");
}
// Traduire PUIS substituer ; texte brut, l'appelant l'échappe.
function dsnt_t($tpl,string $token,array $vals=array()):string{
    $txt=$tpl->_ENGINE_parse_body("{".$token."}");
    $txt=trim(preg_replace('#\s*<br\s*/?>\s*#i',' ',str_replace("[br]","<br>",$txt)));
    $txt=html_entity_decode($txt,ENT_QUOTES,"UTF-8");
    foreach($vals as $v){$p=strpos($txt,"%s");if($p===false){break;}$txt=substr_replace($txt,$v,$p,2);}
    return $txt;
}
function dsnt_h($tpl,string $token,array $vals=array()):string{return htmlspecialchars(dsnt_t($tpl,$token,$vals));}
function dsnt_json(string $uri){
    $j=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($uri));
    if(!is_object($j)||!property_exists($j,"success")||!$j->success){return null;}
    return $j->data;
}
function dsnt_bytes($b):string{
    $b=floatval($b);
    if($b>=1099511627776){return sprintf('%.1f TB',$b/1099511627776);}
    if($b>=1073741824){return sprintf('%.1f GB',$b/1073741824);}
    if($b>=1048576){return sprintf('%.1f MB',$b/1048576);}
    if($b>=1024){return sprintf('%.1f KB',$b/1024);}
    return sprintf('%d B',$b);
}
function dsnt_count($n):string{
    $n=floatval($n);
    if($n>=1e9){return sprintf('%.1f G',$n/1e9);}
    if($n>=1e6){return sprintf('%.1f M',$n/1e6);}
    if($n>=1e4){return sprintf('%.1f k',$n/1e3);}
    return number_format($n);
}
function dsnt_model(string $range):array{
    $m=array("range"=>$range,"disabled"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableDarkStats"))==1,
        "health"=>null,"cfg"=>null,"ov"=>null,"labels"=>array(),"series"=>array(),"totals"=>array(),
        "sum"=>array(),"peak"=>null,"busiest"=>"","busiest_pct"=>0);
    if($m["disabled"]){return $m;}
    $m["health"]=dsnt_json("/darkstats/health");
    if($m["health"]===null || empty($m["health"]->running)){return $m;}
    $m["cfg"]=dsnt_json("/darkstats/config");
    $m["ov"]=dsnt_json("/darkstats/overview?range=$range");
    $data=dsnt_json("/darkstats/export/traffic?range=$range");
    if(!is_array($data)){return $m;}

    $names=array();
    foreach($data as $pt){
        if(isset($pt->interfaces) && is_object($pt->interfaces)){
            foreach($pt->interfaces as $n=>$v){$names[(string)$n]=true;}
        }
    }
    $names=array_keys($names);
    sort($names);
    foreach($names as $n){$m["series"][$n]=array();$m["totals"][$n]=0;}
    $peakV=-1;
    foreach($data as $i=>$pt){
        $label=_ds_fmt_time((string)($pt->time??""),$range);
        $m["labels"][]=$label;
        $sum=0;$topN="";$topV=-1;
        foreach($names as $n){
            $v=(isset($pt->interfaces->$n))?floatval($pt->interfaces->$n):0;
            $m["series"][$n][]=$v;
            $m["totals"][$n]+=$v;
            $sum+=$v;
            if($v>$topV){$topV=$v;$topN=$n;}
        }
        $m["sum"][]=$sum;
        if($sum>$peakV){$peakV=$sum;$m["peak"]=array("bytes"=>$sum,"label"=>$label,"card"=>$topN);}
    }
    $all=array_sum($m["totals"]);
    if($all>0){
        arsort($m["totals"]);
        $m["busiest"]=(string)key($m["totals"]);
        $m["busiest_pct"]=intval(round(100*reset($m["totals"])/$all));
    }
    return $m;
}

function dsnt_style():string{
    if(!empty($GLOBALS["DSNT_CSS"])){return "";}
    $GLOBALS["DSNT_CSS"]=true;
    return "<style>
.dsnt{margin:10px 0 20px 0;color:#1f2937}
.dsnt *{box-sizing:border-box}
.dsnt-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.dsnt-bar{display:flex;justify-content:flex-end;align-items:center;gap:10px;margin-bottom:14px}
.dsnt-bar span{font-size:13px;color:#6b7280;font-weight:600}
.dsnt-seg{display:inline-flex;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:3px;gap:2px}
.dsnt-seg a{padding:6px 14px;border-radius:7px;font-size:13px;font-weight:600;color:#374151;text-decoration:none;white-space:nowrap}
.dsnt-seg a:hover{background:#e6eeed;color:#003d33}
.dsnt-seg a.on{background:#005447;color:#fff}
.dsnt-seg a:focus-visible,.dsnt-chip:focus-visible,.dsnt-link:focus-visible,.dsnt-btn:focus-visible{outline:2px solid #005447;outline-offset:2px}
.dsnt-banner{display:flex;gap:20px;align-items:flex-start;padding:24px;border-left:4px solid #005447;margin-bottom:16px}
.dsnt-bico{flex:0 0 44px;height:44px;border-radius:50%;background:#e6eeed;color:#005447;display:flex;align-items:center;justify-content:center;font-size:19px}
.dsnt-btxt{flex:1 1 auto;min-width:0}
.dsnt-btxt h2{margin:0 0 6px 0;font-size:20px;font-weight:600;line-height:1.3;color:#1f2937}
.dsnt-btxt p{margin:0;font-size:14px;color:#4b5563;line-height:1.55}
.dsnt-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
.dsnt-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;background:#f2f6f6;color:#005447;font-size:13px;border:1px solid #e6eeed}
.dsnt-pill.warn{background:#fffbeb;color:#92400e;border-color:#fde68a}
.dsnt-notice{display:flex;gap:10px;align-items:flex-start;margin-bottom:16px;padding:12px 16px;border-radius:10px;font-size:14px;background:#fffbeb;color:#92400e;border:1px solid #fde68a}
.dsnt-notice i{margin-top:3px}
.dsnt-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:16px}
.dsnt-tile{padding:16px 18px;border-radius:10px;border-color:#eef0f3;min-width:0;display:flex;flex-direction:column}
.dsnt-tl{font-size:13px;font-weight:600;color:#4b5563;text-transform:uppercase;letter-spacing:.04em}
.dsnt-tv{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:8px 0 6px 0;min-height:40px}
.dsnt-big{font-size:28px;font-weight:600;line-height:1.2;color:#1f2937;white-space:nowrap}
.dsnt-big.none{color:#9ca3af}
.dsnt-card-name{display:inline-flex;align-items:baseline;gap:10px;min-width:0}
.dsnt-card-name code{font-family:monospace;font-size:18px;font-weight:600;background:#f3f4f6;padding:2px 8px;border-radius:4px;color:#1f2937;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dsnt-card-name span{font-size:16px;color:#4b5563;white-space:nowrap}
.dsnt-cap{font-size:13px;color:#6b7280;line-height:1.45;margin:auto 0 0 0}
.dsnt-chart{padding:20px 22px;margin-bottom:16px}
.dsnt-ch-h{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:14px}
.dsnt-ch-h h3{margin:0;font-size:16px;font-weight:600;color:#1f2937}
.dsnt-ch-h p{margin:4px 0 0 0;font-size:13px;color:#6b7280;max-width:640px}
.dsnt-legend{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}
.dsnt-chip{display:inline-flex;align-items:center;gap:8px;padding:5px 12px;border-radius:999px;border:1px solid #d1d5db;background:#fff;font-size:13px;color:#1f2937;cursor:pointer}
.dsnt-chip i{width:10px;height:10px;border-radius:50%;display:inline-block}
.dsnt-chip code{font-family:monospace;font-size:13px;font-weight:600}
.dsnt-chip[aria-pressed='false']{opacity:.45;text-decoration:line-through}
.dsnt-canvas{position:relative;height:320px}
.dsnt-ch-f{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-top:14px;padding-top:12px;border-top:1px solid #eef0f3;font-size:13px;color:#4b5563}
.dsnt-ch-f code{font-family:monospace;background:#f3f4f6;padding:1px 6px;border-radius:4px}
.dsnt-ch-f b{color:#1f2937}
.dsnt-read{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;padding:16px 20px}
.dsnt-read div{display:flex;gap:12px;align-items:flex-start;font-size:14px;color:#374151;line-height:1.5}
.dsnt-read div>i{flex:0 0 32px;height:32px;border-radius:8px;background:#f2f6f6;color:#005447;display:flex;align-items:center;justify-content:center}
.dsnt-link{color:#005447;font-weight:600;text-decoration:underline;cursor:pointer}
.dsnt-link:hover{color:#003d33}
.dsnt-empty{text-align:center;padding:48px 16px;color:#6b7280;font-size:14px;line-height:1.55}
.dsnt-empty>i{font-size:32px;color:#9ca3af;display:block;margin-bottom:12px}
.dsnt-empty b{display:block;color:#1f2937;font-size:16px;margin-bottom:4px}
.dsnt-btn{display:inline-flex;align-items:center;gap:8px;margin-top:16px;padding:9px 18px;border-radius:8px;font-size:14px;font-weight:600;background:#005447;color:#fff;text-decoration:none;cursor:pointer}
.dsnt-btn:hover{background:#003d33;color:#fff;text-decoration:none}
@media (max-width:1200px){.dsnt-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}.dsnt-read{grid-template-columns:minmax(0,1fr)}}
@media (prefers-reduced-motion:no-preference){.dsnt-tile,.dsnt-chart{animation:dsntIn .2s ease-out}}
@keyframes dsntIn{from{opacity:0}to{opacity:1}}
</style>";
}

function dsnt_period_bar(string $range):string{
    $page=CurrentPageName();
    $h=array("<div class='dsnt-bar'><span>{dsnt_period}</span><div class='dsnt-seg' role='group'>");
    foreach(dsnt_ranges() as $k=>$label){
        $on=$k===$range?" class='on' aria-current='true'":"";
        $h[]="<a href='javascript:blur();'$on OnClick=\"LoadAjax('ds-traffic','$page?chart-overview=yes&range=$k');\">$label</a>";
    }
    $h[]="</div></div>";
    return implode("",$h);
}

function dsnt_banner($tpl,array $m):string{
    $h=array("<div class='dsnt-card dsnt-banner'><div class='dsnt-bico' aria-hidden='true'><i class='fas fa-chart-area'></i></div>");
    $h[]="<div class='dsnt-btxt'><h2>{dsnt_banner_title}</h2><p>{dsnt_banner_text}</p><div class='dsnt-chips'>";
    $h[]="<span class='dsnt-pill'><i class='fas fa-eye-slash'></i>{dsnt_chip_content}</span>";
    $c=$m["cfg"];
    if(is_object($c) && isset($c->minute_retention_days)){
        $h[]="<span class='dsnt-pill'><i class='fas fa-clock'></i>".dsnt_h($tpl,"dsnt_chip_keep",array(
            strval(intval($c->minute_retention_days)),strval(intval($c->hour_retention_days)),strval(intval($c->day_retention_days))))."</span>";
    }
    $hl=$m["health"];
    if(is_object($hl)){
        $n=is_array($hl->interfaces??null)?count($hl->interfaces):0;
        $h[]="<span class='dsnt-pill'><i class='fas fa-ethernet'></i>".dsnt_h($tpl,$n==1?"dsnt_chip_cards1":"dsnt_chip_cards",array(strval($n)))."</span>";
        if(!empty($hl->running)){
            $ts=strtotime((string)($hl->last_flush??""));
            $ago=$ts?dsnt_ago($tpl,time()-$ts):"";
            $txt=$ago!==""?dsnt_h($tpl,"dsnt_chip_collecting_ago",array($ago)):"{dsnt_chip_collecting}";
            $title=$ts?" title='".date("Y-m-d H:i:s",$ts)."'":"";
            $h[]="<span class='dsnt-pill'$title><i class='fas fa-circle-check'></i>$txt</span>";
        }else{
            $h[]="<span class='dsnt-pill warn'><i class='fas fa-circle-pause'></i>{dsnt_chip_stopped}</span>";
        }
    }
    $h[]="</div></div></div>";
    return implode("",$h);
}
function dsnt_ago($tpl,int $s):string{
    if($s<60){return dsnt_t($tpl,"dsnt_ago_s",array(strval(max(0,$s))));}
    if($s<5400){return dsnt_t($tpl,"dsnt_ago_m",array(strval(intval(round($s/60)))));}
    if($s<172800){return dsnt_t($tpl,"dsnt_ago_h",array(strval(intval(round($s/3600)))));}
    return dsnt_t($tpl,"dsnt_ago_d",array(strval(intval(round($s/86400)))));
}
// Sparkline SVG du total (pas de canvas dans une tuile).
function dsnt_spark(array $vals):string{
    $n=count($vals);
    if($n<2){return "";}
    $max=max($vals);if($max<=0){return "";}
    $w=110;$hh=36;$pts=array();
    foreach($vals as $i=>$v){$pts[]=round($i*$w/($n-1),1).",".round($hh-2-($v/$max)*($hh-4),1);}
    $line=implode(" ",$pts);
    return "<svg width='$w' height='$hh' viewBox='0 0 $w $hh' aria-hidden='true'><polygon points='0,$hh $line $w,$hh' fill='#e6eeed'/><polyline points='$line' fill='none' stroke='#005447' stroke-width='2' stroke-linejoin='round'/></svg>";
}
function dsnt_tiles($tpl,array $m):string{
    $ov=$m["ov"];
    $none="<span class='dsnt-big none'>—</span>";
    $h=array("<div class='dsnt-tiles'>");
    $v=is_object($ov)?"<span class='dsnt-big'>".dsnt_bytes($ov->total_bytes??0)."</span>".dsnt_spark($m["sum"]):$none;
    $h[]="<div class='dsnt-card dsnt-tile'><div class='dsnt-tl'>{dsnt_t_data}</div><div class='dsnt-tv'>$v</div><p class='dsnt-cap'>{dsnt_t_data_help}</p></div>";
    $v=is_object($ov)?"<span class='dsnt-big'>".dsnt_bytes($ov->bytes_per_sec??0)."/s</span>":$none;
    $h[]="<div class='dsnt-card dsnt-tile'><div class='dsnt-tl'>{dsnt_t_speed}</div><div class='dsnt-tv'>$v</div><p class='dsnt-cap'>{dsnt_t_speed_help}</p></div>";
    if(is_object($ov)){
        $v4=floatval($ov->ipv4_packets??0);$v6=floatval($ov->ipv6_packets??0);
        $pct6=($v4+$v6)>0?intval(round(100*$v6/($v4+$v6))):0;
        $cap=dsnt_h($tpl,"dsnt_t_packets_help",array(number_format(floatval($ov->packets_per_sec??0)),strval($pct6)));
        $v="<span class='dsnt-big'>".dsnt_count($ov->total_packets??0)."</span>";
    }else{$cap="{dsnt_t_packets_sub}";$v=$none;}
    $h[]="<div class='dsnt-card dsnt-tile'><div class='dsnt-tl'>{dsnt_t_packets}</div><div class='dsnt-tv'>$v</div><p class='dsnt-cap'>$cap</p></div>";
    if($m["busiest"]!==""){
        $nm=htmlspecialchars($m["busiest"]);
        $v="<span class='dsnt-card-name'><code title='$nm'>$nm</code><span>".dsnt_h($tpl,"dsnt_t_share",array(strval($m["busiest_pct"])))."</span></span>";
        $n=count($m["totals"]);
        $cap=dsnt_h($tpl,$n==1?"dsnt_t_busy_help1":"dsnt_t_busy_help",array(strval($n)));
    }else{$v=$none;$cap="{dsnt_t_busy_none}";}
    $h[]="<div class='dsnt-card dsnt-tile'><div class='dsnt-tl'>{dsnt_t_busy}</div><div class='dsnt-tv'>$v</div><p class='dsnt-cap'>$cap</p></div>";
    $h[]="</div>";
    return implode("",$h);
}
function dsnt_colors():array{return array('#005447','#e67e22','#2e86de','#c0392b','#16a085','#7f8c8d','#d4ac0d','#1f2937');}

// Rend [html, script] : le script reste hors de _ENGINE_parse_body().
function dsnt_chart($tpl,array $m):array{
    $step=array("last15m"=>"minute","hour"=>"minute","day"=>"hour","week"=>"day","month"=>"day");
    $unit=$step[$m["range"]];
    $h=array("<div class='dsnt-card dsnt-chart'>");
    if(count($m["labels"])==0){
        if($m["health"]===null || empty($m["health"]->running)){
            $h[]="<div class='dsnt-empty'><i class='fas fa-circle-pause'></i><b>{dsnt_chip_stopped}</b>{dsnt_stopped_note}</div></div>";
        }else{
            $h[]="<div class='dsnt-empty'><i class='fas fa-hourglass-half'></i><b>{dsnt_empty_title}</b>{dsnt_empty_text}</div></div>";
        }
        return array(implode("",$h),"");
    }
    $colors=dsnt_colors();
    $names=array_keys($m["series"]);
    $mx=count($m["sum"])?max($m["sum"]):0;
    $units=array(array(1099511627776,"TB"),array(1073741824,"GB"),array(1048576,"MB"),array(1024,"KB"),array(1,"B"));
    $div=1;$uname="B";
    foreach($units as $u){if($mx>=$u[0]){$div=$u[0];$uname=$u[1];break;}}
    $h[]="<div class='dsnt-ch-h'><div><h3>{dsnt_per_$unit}</h3><p>{dsnt_sub_$unit}</p></div><div class='dsnt-legend' role='group'>";
    $ds=array();
    foreach($names as $i=>$n){
        $c=$colors[$i%count($colors)];
        $e=htmlspecialchars($n);
        $h[]="<button type='button' class='dsnt-chip' aria-pressed='true' data-i='$i' OnClick=\"DsntToggle(this);\"><i style='background:$c'></i><code>$e</code>".dsnt_bytes(array_sum($m["series"][$n]))."</button>";
        $scaled=array();
        foreach($m["series"][$n] as $v){$scaled[]=round($v/$div,3);}
        $ds[]=array("label"=>$n,"data"=>$scaled,"borderColor"=>$c,"backgroundColor"=>$c."33","fill"=>$i==0?"origin":"-1",
            "tension"=>0.3,"pointRadius"=>0,"pointHitRadius"=>6,"borderWidth"=>2);
    }
    $h[]="</div></div>";
    $id="dsnt-c-".md5($m["range"].microtime());
    $total=array_sum($m["sum"]);
    $aria=dsnt_h($tpl,"dsnt_aria",array(dsnt_bytes($total)));
    $h[]="<div class='dsnt-canvas'><canvas id='$id' role='img' aria-label=\"$aria\"></canvas></div>";
    $foot="";
    if($m["peak"]!==null && $m["peak"]["bytes"]>0){
        $foot=dsnt_t($tpl,"dsnt_peak",array("\x01".dsnt_bytes($m["peak"]["bytes"])."\x02",$m["peak"]["label"],"\x03".$m["peak"]["card"]."\x04"));
        $foot=str_replace(array("\x01","\x02","\x03","\x04"),array("<b>","</b>","<code>","</code>"),htmlspecialchars($foot));
    }
    $h[]="<div class='dsnt-ch-f'><span><i class='fas fa-mountain' style='margin-right:6px'></i>$foot</span><span>{dsnt_point_$unit}</span></div></div>";

    $J=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $tot=json_encode(dsnt_t($tpl,"dsnt_total"),$J);
    $s="<script>(function(){var L=".json_encode($m["labels"],$J).",D=".json_encode($ds,$J).",TOT=$tot,U='$uname',DIV=$div;"
        ._ds_js_fmt_bytes()
        ."function draw(){".str_replace("window.DsChart=window.DsChart||","window.DsChart=window.DsChart||",_ds_chart_helper_js())
        ."var rm=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;"
        ."DsChart('traffic','$id',{type:'line',data:{labels:L,datasets:D},options:{responsive:true,maintainAspectRatio:false,animation:rm?false:{duration:250},"
        ."interaction:{mode:'index',intersect:false},"
        ."scales:{x:{grid:{display:false},ticks:{autoSkip:true,maxRotation:0,font:{size:13}}},y:{stacked:true,beginAtZero:true,ticks:{font:{size:13},callback:function(v){return (Math.round(v*100)/100)+' '+U;}}}},"
        ."plugins:{legend:{display:false},datalabels:{display:false},tooltip:{callbacks:{label:function(x){return x.dataset.label+': '+_fmtB(x.parsed.y*DIV);},"
        ."footer:function(items){var t=0;for(var i=0;i<items.length;i++){t+=items[i].parsed.y;}return TOT+': '+_fmtB(t*DIV);}}}}}});}"
        ."window.DsntToggle=function(b){var c=window.DsCharts&&window.DsCharts.traffic;if(!c){return;}var i=parseInt(b.getAttribute('data-i'),10);"
        ."var v=c.isDatasetVisible(i);c.setDatasetVisibility(i,!v);c.update();b.setAttribute('aria-pressed',v?'false':'true');};"
        ."if(typeof Chart!=='undefined'){draw();return;}"
        ."var e=document.createElement('script');e.src='angular/js/plugins/chartJs/Chart.min.4.5.1.js';e.onload=draw;document.head.appendChild(e);})();</script>";
    return array(implode("",$h),$s);
}
function dsnt_read():string{
    return "<div class='dsnt-card dsnt-read'>"
        ."<div><i class='fas fa-mountain' aria-hidden='true'></i><span>{dsnt_read_spike}</span></div>"
        ."<div><i class='fas fa-scale-balanced' aria-hidden='true'></i><span>{dsnt_read_similar}</span></div>"
        ."<div><i class='fas fa-magnifying-glass' aria-hidden='true'></i><span>{dsnt_read_who} <a class='dsnt-link' href='javascript:blur();' OnClick=\"DsntTab(1);\">{top_talkers}</a> · <a class='dsnt-link' href='javascript:blur();' OnClick=\"DsntTab(5);\">{dsnt_hosts}</a></span></div>"
        ."</div>";
}
// Hôte de l'onglet, rechargé à chaque changement de période (?chart-overview=yes&range=).
function chart_overview(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=$_GET["range"]??"day";
    if(!isset(dsnt_ranges()[$range])){$range="day";}
    $m=dsnt_model($range);
    $js="<script>NoSpinner();window.DsntTab=function(n){var h=document.getElementById('ds-traffic');var c=h?h.closest('.tabs-container'):null;var a=c?c.querySelectorAll('ul.nav-tabs>li>a'):[];if(a.length>n){a[n].click();}};</script>";
    if($m["disabled"]){
        $html="<div class='dsnt'><div class='dsnt-card dsnt-empty'><i class='fas fa-chart-area'></i><b>{dsnt_off_title}</b>{dsnt_off_text}<br>"
            ."<a class='dsnt-btn' href='javascript:blur();' OnClick=\"Loadjs('$page?install-js=yes');\"><i class='fas fa-power-off'></i>{install_feature}</a></div></div>";
        echo dsnt_style().$tpl->_ENGINE_parse_body($html).$js;
        return;
    }
    $h=array("<div class='dsnt'>",dsnt_period_bar($range),dsnt_banner($tpl,$m));
    if($m["health"]===null || empty($m["health"]->running)){
        $h[]="<div class='dsnt-notice'><i class='fas fa-circle-pause'></i><span>{dsnt_stopped_note}</span></div>";
    }
    $h[]=dsnt_tiles($tpl,$m);
    list($ch,$cjs)=dsnt_chart($tpl,$m);
    $h[]=$ch;
    $h[]=dsnt_read();
    $h[]="</div>";
    echo dsnt_style().$tpl->_ENGINE_parse_body(implode("",$h)).$cjs.$js;
}

// ─── Top talkers (maquette stitch_top_talkers) ───
// ---- DSTT model begin
// Classement : /darkstats/hosts (même requête que /darkstats/top, mais avec le nom DNS
// et first/last seen). Partenaires : /darkstats/conversations, filtré par adresse ; le démon
// ne garde que les plus grosses conversations de chaque minute, d'où « principaux ».
function dstt_is_local(string $ip):bool{
    if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){
        $l=ip2long($ip);
        if($l===false){return false;}
        $nets=array(array("10.0.0.0",8),array("172.16.0.0",12),array("192.168.0.0",16),array("127.0.0.0",8),
            array("169.254.0.0",16),array("224.0.0.0",4),array("255.255.255.255",32),array("0.0.0.0",8));
        foreach($nets as $n){
            $mask=$n[1]==0?0:(~0<<(32-$n[1]))&0xFFFFFFFF;
            if(($l&$mask)==(ip2long($n[0])&$mask)){return true;}
        }
        return false;
    }
    if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)){
        $b=inet_pton($ip);
        if($b===false){return false;}
        $f=ord($b[0]);$s=ord($b[1]);
        if(($f&0xFE)==0xFC){return true;}              // fc00::/7
        if($f==0xFE && ($s&0xC0)==0x80){return true;}  // fe80::/10
        if($f==0xFF){return true;}                     // multicast
        if($ip=="::1"){return true;}
        return false;
    }
    return false;
}
function dstt_services():array{
    return array(20=>array("FTP","dstt_svc_ftp"),21=>array("FTP","dstt_svc_ftp"),22=>array("SSH","dstt_svc_ssh"),
        25=>array("SMTP","dstt_svc_mail"),53=>array("DNS","dstt_svc_dns"),67=>array("DHCP","dstt_svc_addr"),
        68=>array("DHCP","dstt_svc_addr"),80=>array("HTTP","dstt_svc_web"),88=>array("Kerberos","dstt_svc_auth"),
        110=>array("POP3","dstt_svc_mail"),123=>array("NTP","dstt_svc_time"),143=>array("IMAP","dstt_svc_mail"),
        161=>array("SNMP","dstt_svc_monitor"),389=>array("LDAP","dstt_svc_dir"),443=>array("HTTPS","dstt_svc_secure_web"),
        445=>array("SMB","dstt_svc_files"),465=>array("SMTPS","dstt_svc_mail"),514=>array("Syslog","dstt_svc_logs"),
        587=>array("SMTP","dstt_svc_mail"),636=>array("LDAPS","dstt_svc_dir"),853=>array("DNS over TLS","dstt_svc_dns"),
        993=>array("IMAPS","dstt_svc_mail"),995=>array("POP3S","dstt_svc_mail"),1194=>array("OpenVPN","dstt_svc_vpn"),
        3128=>array("Proxy","dstt_svc_proxy"),3306=>array("MySQL","dstt_svc_db"),3389=>array("RDP","dstt_svc_rdp"),
        5060=>array("SIP","dstt_svc_phone"),5432=>array("PostgreSQL","dstt_svc_db"),8080=>array("HTTP","dstt_svc_web"),
        51820=>array("WireGuard","dstt_svc_vpn"),137=>array("NetBIOS","dstt_svc_dns"),138=>array("NetBIOS","dstt_svc_files"),
        139=>array("NetBIOS","dstt_svc_files"),1900=>array("SSDP","dstt_svc_discovery"),5353=>array("mDNS","dstt_svc_discovery"),
        5355=>array("LLMNR","dstt_svc_dns"));
}
function dstt_proto_name(int $p):string{
    if($p==6){return "TCP";}
    if($p==17){return "UDP";}
    if($p==1 || $p==58){return "ICMP";}
    return "IP $p";
}
// Port de service d'une conversation : un port connu d'abord, sinon le plus petit (l'autre est éphémère).
function dstt_service_port($c):int{
    $sp=intval($c->src_port??0);$dp=intval($c->dst_port??0);
    $svc=dstt_services();
    if(isset($svc[$dp])){return $dp;}
    if(isset($svc[$sp])){return $sp;}
    if($sp==0){return $dp;}
    if($dp==0){return $sp;}
    return min($sp,$dp);
}
function dstt_ts($v):int{
    if(!is_string($v) || $v==""){return 0;}
    try{$d=new DateTime($v);return $d->getTimestamp();}catch(Exception $e){return 0;}
}
function dstt_model(string $range):array{
    $m=array("range"=>$range,"state"=>"ok","health"=>null,"cfg"=>null,"rows"=>array(),"max"=>0,"total"=>0,"local"=>0,"internet"=>0);
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableDarkStats"))==1){$m["state"]="off";return $m;}
    $m["health"]=dsnt_json("/darkstats/health");
    if($m["health"]===null){$m["state"]="unreachable";return $m;}
    if(empty($m["health"]->running)){$m["state"]="stopped";return $m;}
    $hosts=dsnt_json("/darkstats/hosts?range=$range&limit=".DSTT_LIMIT);
    if(!is_array($hosts)){$m["state"]="unreachable";return $m;}
    if(count($hosts)==0){$m["state"]="empty";return $m;}
    $names=array();
    foreach($hosts as $h){
        $ip=(string)($h->ip??"");
        if($ip==""){continue;}
        $bi=floatval($h->bytes_in??0);$bo=floatval($h->bytes_out??0);
        $local=dstt_is_local($ip);
        $m[$local?"local":"internet"]++;
        $name=trim((string)($h->hostname??""));
        if($name!==""){$names[$ip]=$name;}
        $m["rows"][]=array("ip"=>$ip,"name"=>$name,"local"=>$local,"in"=>$bi,"out"=>$bo,"total"=>$bi+$bo,
            "pin"=>floatval($h->packets_in??0),"pout"=>floatval($h->packets_out??0),
            "first"=>dstt_ts($h->first_seen??""),"last"=>dstt_ts($h->last_seen??""),"partners"=>array());
        $m["total"]+=$bi+$bo;
        if($bi+$bo>$m["max"]){$m["max"]=$bi+$bo;}
    }
    if(count($m["rows"])==0){$m["state"]="empty";return $m;}
    $convs=dsnt_json("/darkstats/conversations?range=$range&limit=".DSTT_CONV_LIMIT);
    if(!is_array($convs)){$convs=array();}
    foreach($m["rows"] as $i=>$r){
        $agg=array();
        foreach($convs as $c){
            $s=(string)($c->src_ip??"");$d=(string)($c->dst_ip??"");
            if($s===$r["ip"]){$other=$d;}elseif($d===$r["ip"]){$other=$s;}else{continue;}
            if($other===""){continue;}
            $proto=intval($c->protocol??0);
            $port=($proto==1||$proto==58)?0:dstt_service_port($c);
            $k="$other|$proto|$port";
            if(!isset($agg[$k])){$agg[$k]=array("ip"=>$other,"name"=>$names[$other]??"","local"=>dstt_is_local($other),"proto"=>$proto,"port"=>$port,"bytes"=>0);}
            $agg[$k]["bytes"]+=floatval($c->bytes??0);
        }
        usort($agg,function($a,$b){ if($a["bytes"]==$b["bytes"]){return 0;} return $a["bytes"]<$b["bytes"]?1:-1; });
        $m["rows"][$i]["partners"]=array_slice($agg,0,DSTT_PARTNERS);
    }
    return $m;
}
// ---- DSTT model end
// ---- DSTT widgets begin
function dstt_range_label($tpl,string $range):string{
    $r=dsnt_ranges();
    return dsnt_t($tpl,trim($r[$range],"{}"));
}
function dstt_period_bar(string $range):string{
    $page=CurrentPageName();
    $h=array("<div class='dstt-bar'><div class='dstt-bar-t'><h3>{dstt_title}</h3><span>{dstt_title_sub}</span></div>");
    $h[]="<div class='dstt-bar-p'><span>{dsnt_period}</span><div class='dsnt-seg' role='group'>";
    foreach(dsnt_ranges() as $k=>$label){
        $on=$k===$range?" class='on' aria-current='true'":"";
        $h[]="<a href='javascript:blur();'$on OnClick=\"DsttRange('$k');\">$label</a>";
    }
    $h[]="</div></div></div>";
    return implode("",$h);
}
function dstt_banner($tpl,array $m):string{
    $h=array("<div class='dsnt-card dsnt-banner'><div class='dsnt-bico' aria-hidden='true'><i class='fas fa-ranking-star'></i></div>");
    $h[]="<div class='dsnt-btxt'><h2>{dstt_banner_title}</h2><p>{dstt_banner_text}</p><div class='dsnt-chips'>";
    $h[]="<span class='dsnt-pill'><i class='fas fa-eye-slash'></i>{dsnt_chip_content}</span>";
    $h[]="<span class='dsnt-pill'><i class='fas fa-house-signal'></i>{dstt_chip_apart}</span>";
    $hl=$m["health"];
    if(is_object($hl)){
        if(!empty($hl->running)){
            $ts=strtotime((string)($hl->last_flush??""));
            $ago=$ts?dsnt_ago($tpl,time()-$ts):"";
            $txt=$ago!==""?dsnt_h($tpl,"dsnt_chip_collecting_ago",array($ago)):"{dsnt_chip_collecting}";
            $title=$ts?" title='".date("Y-m-d H:i:s",$ts)."'":"";
            $h[]="<span class='dsnt-pill'$title><i class='fas fa-circle-check'></i>$txt</span>";
        }else{
            $h[]="<span class='dsnt-pill warn'><i class='fas fa-circle-pause'></i>{dsnt_chip_stopped}</span>";
        }
    }
    $h[]="</div></div></div>";
    return implode("",$h);
}
function dstt_tile_busiest($tpl,array $m):string{
    $r=$m["rows"][0];
    $ip=htmlspecialchars($r["ip"]);
    $name=$r["name"]!==""?htmlspecialchars($r["name"]):$ip;
    $cap=dsnt_t($tpl,"dstt_t_busiest_cap",array("\x01".dsnt_bytes($r["total"])."\x02",dstt_range_label($tpl,$m["range"])));
    $cap=str_replace(array("\x01","\x02"),array("<b>","</b>"),htmlspecialchars($cap));
    $sub=$r["name"]!==""?"<div class='dstt-tile-ip'>$ip</div>":"";
    return "<div class='dsnt-card dstt-tile'><div class='dstt-tile-h'><span>{dstt_t_busiest}</span><i class='fas fa-crown' aria-hidden='true'></i></div>"
        ."<div class='dstt-tile-v' title='$name'>$name</div>$sub"
        ."<p class='dstt-tile-c'><i class='fas fa-right-left' aria-hidden='true'></i><span>$cap</span></p></div>";
}
function dstt_tile_top($tpl,array $m):string{
    $n=count($m["rows"]);
    $sub=dsnt_h($tpl,$n==1?"dstt_t_top_sub1":"dstt_t_top_sub",array(strval($n)));
    return "<div class='dsnt-card dstt-tile'><div class='dstt-tile-h'><span>".dsnt_h($tpl,"dstt_t_top",array(strval($n)))."</span><i class='fas fa-sitemap' aria-hidden='true'></i></div>"
        ."<div class='dstt-tile-v big'>".dsnt_bytes($m["total"])."</div><div class='dstt-tile-ip'>$sub</div>"
        ."<p class='dstt-tile-c'><i class='fas fa-chart-pie' aria-hidden='true'></i><span>{dstt_t_top_cap}</span></p></div>";
}
function dstt_tile_where($tpl,array $m):string{
    $val=dsnt_h($tpl,"dstt_t_where_val",array(strval($m["local"]),strval($m["internet"])));
    return "<div class='dsnt-card dstt-tile'><div class='dstt-tile-h'><span>{dstt_t_where}</span><i class='fas fa-location-dot' aria-hidden='true'></i></div>"
        ."<div class='dstt-tile-v'>$val</div>"
        ."<div class='dstt-badges'><span class='dstt-loc local'><i class='fas fa-house'></i>{$m["local"]} {dstt_local}</span>"
        ."<span class='dstt-loc inet'><i class='fas fa-globe'></i>{$m["internet"]} {dstt_internet}</span></div>"
        ."<p class='dstt-tile-c'><i class='fas fa-circle-info' aria-hidden='true'></i><span>{dstt_t_where_cap}</span></p></div>";
}
function dstt_tiles($tpl,array $m):string{
    return "<div class='dstt-tiles'>".dstt_tile_busiest($tpl,$m).dstt_tile_top($tpl,$m).dstt_tile_where($tpl,$m)."</div>";
}
function dstt_loc_badge(bool $local,bool $short=false):string{
    if($local){return "<span class='dstt-loc local'><i class='fas fa-house'></i>".($short?"{dstt_local}":"{dstt_on_network}")."</span>";}
    return "<span class='dstt-loc inet'><i class='fas fa-globe'></i>{dstt_internet}</span>";
}
function dstt_seen($tpl,int $ts):string{
    if($ts<=0){return "<span class='dstt-seen'>—</span>";}
    $s=time()-$ts;
    $txt=$s<60?dsnt_t($tpl,"dstt_just_now"):dsnt_t($tpl,"dstt_ago",array(dsnt_ago($tpl,$s)));
    return "<span class='dstt-seen' title='".date("Y-m-d H:i:s",$ts)."'>".htmlspecialchars($txt)."</span>";
}
function dstt_service($tpl,array $p):string{
    $proto=dstt_proto_name($p["proto"]);
    if($proto=="ICMP"){return "ICMP (".dsnt_t($tpl,"dstt_svc_ping").")";}
    $svc=dstt_services();
    if($p["port"]>0 && isset($svc[$p["port"]])){
        return $svc[$p["port"]][0]." (".dsnt_t($tpl,$svc[$p["port"]][1]).") · $proto {$p["port"]}";
    }
    return $p["port"]>0?"$proto {$p["port"]}":$proto;
}
function dstt_partners($tpl,array $r,string $pid):string{
    $h=array("<div class='dstt-panel' id='$pid' hidden><div class='dstt-panel-h'><div><b>{dstt_partners}</b><span>{dstt_partners_help}</span></div>");
    if($r["first"]>0){
        $h[]="<span class='dstt-first' title='".date("Y-m-d H:i:s",$r["first"])."'>".dsnt_h($tpl,"dstt_first_seen",array(dsnt_ago($tpl,max(0,time()-$r["first"]))))."</span>";
    }
    $h[]="</div>";
    if(count($r["partners"])==0){
        $h[]="<p class='dstt-none'>{dstt_no_partner}</p></div>";
        return implode("",$h);
    }
    $h[]="<div class='dstt-plist'>";
    foreach($r["partners"] as $p){
        $ip=htmlspecialchars($p["ip"]);
        $id=$p["name"]!==""?htmlspecialchars($p["name"])." <span>($ip)</span>":$ip;
        $h[]="<div class='dstt-prow'><div class='dstt-pid'><span class='dstt-mono'>$id</span>".dstt_loc_badge($p["local"],true)."</div>"
            ."<span class='dstt-svc'>".htmlspecialchars(dstt_service($tpl,$p))."</span>"
            ."<span class='dstt-pvol'>".dsnt_bytes($p["bytes"])."</span></div>";
    }
    $h[]="</div></div>";
    return implode("",$h);
}
function dstt_row($tpl,array $m,int $i):string{
    $r=$m["rows"][$i];
    $rank=$i+1;
    $ip=htmlspecialchars($r["ip"]);
    $pid="dstt-p-".md5($r["ip"]);
    $copy="<button type='button' class='dstt-copy' aria-label=\"".dsnt_h($tpl,"dstt_copy")."\" title=\"".dsnt_h($tpl,"dstt_copy")."\" data-ip='$ip' OnClick=\"DsttCopy(event,this);\"><i class='fas fa-copy'></i></button>";
    if($r["name"]!==""){
        $nm=htmlspecialchars($r["name"]);
        $ident="<div class='dstt-name' title='$nm'>$nm</div><div class='dstt-ip'><span class='dstt-mono'>$ip</span>$copy</div>";
    }else{
        $ident="<div class='dstt-name dstt-mono'>$ip $copy</div><div class='dstt-noname'>{dstt_no_name}</div>";
    }
    $w=$m["max"]>0?round(100*$r["total"]/$m["max"],1):0;
    $pi=$r["total"]>0?round(100*$r["in"]/$r["total"],1):0;
    $po=$r["total"]>0?round(100-$pi,1):0;
    $tin=dsnt_h($tpl,"dstt_bar_recv",array(dsnt_bytes($r["in"])));
    $tout=dsnt_h($tpl,"dstt_bar_sent",array(dsnt_bytes($r["out"])));
    $bar="<div class='dstt-track'><div class='dstt-fill' style='width:$w%'><i class='in' style='width:$pi%' title=\"$tin\"></i><i class='out' style='width:$po%' title=\"$tout\"></i></div></div>";
    $pk=dsnt_h($tpl,"dstt_packets",array(number_format($r["pin"]),number_format($r["pout"])));
    $nums="<div class='dstt-num' title=\"$pk\"><b>".dsnt_bytes($r["total"])."</b><span>↓ ".dsnt_bytes($r["in"])." · ↑ ".dsnt_bytes($r["out"])."</span></div>";
    $lbl=dsnt_h($tpl,"dstt_show_partners");
    $h=array("<div class='dstt-item' data-ip='$ip'>");
    $h[]="<div class='dstt-row' role='button' tabindex='0' aria-expanded='false' aria-controls='$pid' OnClick=\"DsttToggle(this);\" onkeydown=\"if(event.key==='Enter'||event.key===' '){DsttToggle(this);event.preventDefault();}\">";
    $h[]="<span class='dstt-rank".($rank==1?" first":"")."'>$rank</span><div class='dstt-ident'>$ident</div>";
    $h[]="<div class='dstt-locc'>".dstt_loc_badge($r["local"])."</div><div class='dstt-barc'>$bar</div>$nums";
    $h[]="<div class='dstt-seenc'>".dstt_seen($tpl,$r["last"])."</div>";
    $h[]="<span class='dstt-chev' aria-label=\"$lbl\" title=\"$lbl\"><i class='fas fa-chevron-down'></i></span></div>";
    $h[]=dstt_partners($tpl,$r,$pid);
    $h[]="</div>";
    return implode("",$h);
}
function dstt_ranking($tpl,array $m):string{
    $n=count($m["rows"]);
    $h=array("<div class='dsnt-card dstt-rank-card'><div class='dstt-rh'><div><h3>".dsnt_h($tpl,"dstt_rank_title",array(strval($n)))."</h3><p>{dstt_rank_help}</p></div><p class='dstt-ex'>{dstt_example}</p></div>");
    $h[]="<div class='dstt-legend'><div><span><i class='in'></i><b>{dstt_received}</b> — {dstt_received_help}</span><span><i class='out'></i><b>{dstt_sent}</b> — {dstt_sent_help}</span></div><span>{dstt_click_hint}</span></div>";
    $h[]="<div class='dstt-rows'>";
    for($i=0;$i<$n;$i++){$h[]=dstt_row($tpl,$m,$i);}
    $h[]="</div><div class='dstt-foot'><span><i class='fas fa-circle-info'></i>{dstt_footer}</span><span>".dsnt_h($tpl,$n==1?"dstt_footer_count1":"dstt_footer_count",array(strval($n)))."</span></div></div>";
    return implode("",$h);
}
function dstt_state_card(string $ico,string $cls,string $title,string $text,string $btn=""):string{
    return "<div class='dsnt-card dsnt-empty dstt-state $cls'><i class='fas $ico'></i><b>$title</b>$text$btn</div>";
}
function dstt_state_off():string{
    $page=CurrentPageName();
    return dstt_state_card("fa-chart-area","","{dsnt_off_title}","{dsnt_off_text}",
        "<br><a class='dsnt-btn' href='javascript:blur();' OnClick=\"Loadjs('$page?install-js=yes');\"><i class='fas fa-power-off'></i>{install_feature}</a>");
}
function dstt_state_stopped():string{
    return dstt_state_card("fa-circle-pause","warn","{dstt_stopped_title}","{dstt_stopped_text}",
        "<br><a class='dsnt-btn' href='javascript:blur();' OnClick=\"DsttTab(6);\"><i class='fas fa-heart-pulse'></i>{dstt_open_status}</a>");
}
function dstt_state_empty(string $range):string{
    $next=$range=="day"?"month":"day";
    $lbl=$next=="day"?"{dstt_show_24h}":"{dstt_show_30d}";
    return dstt_state_card("fa-hourglass-half","","{dstt_empty_title}","{dstt_empty_text}",
        "<br><a class='dsnt-btn' href='javascript:blur();' OnClick=\"DsttRange('$next');\"><i class='fas fa-clock-rotate-left'></i>$lbl</a>");
}
function dstt_state_unreachable():string{
    return dstt_state_card("fa-plug-circle-xmark","err","{dstt_err_title}","{dstt_err_text}");
}
function dstt_style():string{
    return "<style>
.dstt-bar{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:14px;flex-wrap:wrap}
.dstt-bar-t{display:flex;align-items:baseline;gap:10px;min-width:0}
.dstt-bar-t h3{margin:0;font-size:16px;font-weight:600;color:#1f2937}
.dstt-bar-t span{font-size:13px;color:#6b7280}
.dstt-bar-p{display:flex;align-items:center;gap:10px}
.dstt-bar-p>span{font-size:13px;color:#6b7280;font-weight:600}
.dstt-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:16px}
.dstt-tile{padding:18px 20px;display:flex;flex-direction:column;min-width:0}
.dstt-tile-h{display:flex;justify-content:space-between;align-items:center;font-size:13px;font-weight:600;color:#4b5563;text-transform:uppercase;letter-spacing:.04em}
.dstt-tile-h i{color:#005447}
.dstt-tile-v{margin-top:10px;font-size:18px;font-weight:600;color:#1f2937;line-height:1.35;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dstt-tile-v.big{font-size:26px}
.dstt-tile-ip{font-family:monospace;font-size:13px;color:#6b7280;margin-top:4px}
.dstt-tile-c{display:flex;gap:8px;align-items:flex-start;margin:auto 0 0 0;padding-top:12px;border-top:1px solid #f1f5f9;font-size:13px;color:#4b5563;line-height:1.45}
.dstt-tile .dstt-tile-c{margin-top:14px}
.dstt-tile-c i{margin-top:3px;color:#6b7280}
.dstt-tile-c b{color:#1f2937}
.dstt-badges{display:flex;gap:6px;margin-top:8px}
.dstt-loc{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:13px;font-weight:600;white-space:nowrap}
.dstt-loc.local{background:#e6eeed;color:#005447}
.dstt-loc.inet{background:#f3f4f6;color:#374151}
.dstt-loc i{font-size:11px}
.dstt-rank-card{overflow:hidden;margin-bottom:16px}
.dstt-rh{display:flex;justify-content:space-between;gap:24px;padding:18px 22px 14px 22px;border-bottom:1px solid #f1f5f9}
.dstt-rh h3{margin:0;font-size:16px;font-weight:600;color:#1f2937}
.dstt-rh p{margin:4px 0 0 0;font-size:13px;color:#6b7280}
.dstt-rh .dstt-ex{max-width:380px;font-style:italic;margin:0;text-align:right}
.dstt-legend{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:10px 22px;background:#f9fafb;border-bottom:1px solid #f1f5f9;font-size:13px;color:#6b7280;flex-wrap:wrap}
.dstt-legend>div{display:flex;gap:20px;flex-wrap:wrap}
.dstt-legend b{color:#1f2937;font-weight:600}
.dstt-legend i{display:inline-block;width:12px;height:12px;border-radius:3px;margin-right:6px;vertical-align:-1px}
.dstt-legend i.in,.dstt-fill i.in{background:#005447}
.dstt-legend i.out,.dstt-fill i.out{background:#80aaa3}
.dstt-item{border-bottom:1px solid #f1f5f9}
.dstt-item:last-child{border-bottom:0}
.dstt-row{display:grid;grid-template-columns:28px minmax(0,1.6fr) 160px minmax(160px,360px) 150px 100px 28px;align-items:center;gap:16px;padding:12px 22px;min-height:64px;cursor:pointer}
.dstt-row:hover{background:#fafbfb}
.dstt-row:focus-visible,.dstt-copy:focus-visible{outline:2px solid #005447;outline-offset:2px}
.dstt-rank{width:28px;height:28px;border-radius:50%;background:#e6eeed;color:#005447;font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center}
.dstt-rank.first{background:#005447;color:#fff}
.dstt-ident{min-width:0}
.dstt-name{font-size:14px;font-weight:600;color:#1f2937;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dstt-ip{display:flex;align-items:center;gap:4px;margin-top:2px}
.dstt-mono{font-family:monospace;font-size:13px;color:#6b7280}
.dstt-name.dstt-mono{color:#1f2937;font-size:14px}
.dstt-noname{font-size:13px;color:#9ca3af;font-style:italic;margin-top:2px}
.dstt-copy{border:0;background:transparent;color:#6b7280;padding:2px 6px;border-radius:4px;cursor:pointer;font-size:12px}
.dstt-copy:hover{background:#e6eeed;color:#005447}
.dstt-copy.ok{color:#005447}
.dstt-track{height:10px;border-radius:5px;background:#f1f5f9;overflow:hidden}
.dstt-fill{height:100%;display:flex;border-radius:5px;overflow:hidden}
.dstt-fill i{display:block;height:100%}
.dstt-num{text-align:right;font-variant-numeric:tabular-nums}
.dstt-num b{display:block;font-size:14px;color:#1f2937}
.dstt-num span{font-size:12px;color:#6b7280;white-space:nowrap}
.dstt-seenc{text-align:right;font-size:13px;color:#6b7280;white-space:nowrap}
.dstt-chev{color:#6b7280;text-align:center;transition:transform .15s}
.dstt-row[aria-expanded='true'] .dstt-chev{transform:rotate(180deg)}
.dstt-panel{background:#f9fafb;padding:16px 24px;border-top:1px solid #f1f5f9}
.dstt-panel[hidden]{display:none}
.dstt-panel-h{display:flex;justify-content:space-between;align-items:baseline;gap:16px;margin-bottom:10px}
.dstt-panel-h b{font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#1f2937;margin-right:10px}
.dstt-panel-h span{font-size:13px;color:#6b7280}
.dstt-first{font-family:monospace;white-space:nowrap}
.dstt-plist{background:#fff;border:1px solid #e5e7eb;border-radius:8px}
.dstt-prow{display:grid;grid-template-columns:minmax(0,1fr) auto 100px;gap:16px;align-items:center;padding:9px 14px;border-bottom:1px solid #f1f5f9}
.dstt-prow:last-child{border-bottom:0}
.dstt-pid{display:flex;align-items:center;gap:10px;min-width:0}
.dstt-pid .dstt-mono{color:#1f2937;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dstt-pid .dstt-mono span{color:#6b7280}
.dstt-pid .dstt-loc{font-size:12px;padding:2px 8px}
.dstt-svc{font-size:12px;color:#374151;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:2px 8px;white-space:nowrap}
.dstt-pvol{text-align:right;font-size:13px;font-weight:600;color:#1f2937;font-variant-numeric:tabular-nums}
.dstt-none{margin:0;font-size:13px;color:#6b7280}
.dstt-foot{display:flex;justify-content:space-between;gap:16px;padding:12px 22px;border-top:1px solid #f1f5f9;font-size:13px;color:#6b7280}
.dstt-foot i{margin-right:6px}
.dstt-state.warn>i{color:#d97706}
.dstt-state.err>i{color:#b91c1c}
.dstt-state:not(.warn):not(.err)>i{color:#005447}
@media (max-width:1200px){.dstt-tiles{grid-template-columns:minmax(0,1fr)}.dstt-row{grid-template-columns:28px minmax(0,1.4fr) 150px 240px 130px 90px 24px}}
@media (prefers-reduced-motion:no-preference){.dstt-fill{animation:dsttGrow .3s ease-out}.dstt-panel{animation:dsntIn .15s ease-out}}
@keyframes dsttGrow{from{transform:scaleX(0);transform-origin:left}to{transform:scaleX(1);transform-origin:left}}
</style>";
}
function dstt_js(string $range):string{
    $page=CurrentPageName();
    $J=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $tpl=new template_admin();
    $cfg=json_encode(array("page"=>$page,"range"=>$range,"copied"=>dsnt_t($tpl,"dstt_copied")),$J);
    return "<script>NoSpinner();(function(){var C=$cfg;
window.DsttOpen=window.DsttOpen||{};
window.DsttRange=function(r){window.DsttCur=r;LoadAjax('ds-top-hosts',C.page+'?chart-top-hosts=yes&range='+encodeURIComponent(r));};
window.DsttTab=function(n){var h=document.getElementById('ds-top-hosts');var c=h?h.closest('.tabs-container'):null;var a=c?c.querySelectorAll('ul.nav-tabs>li>a'):[];if(a.length>n){a[n].click();}};
function build(row){var id=row.getAttribute('aria-controls');var p=document.getElementById(id);if(p){return p;}var t=document.getElementById('dsho-ptpl');var it=row.parentNode;if(!t||!t.content){return null;}
var n=t.content.firstElementChild.cloneNode(true);n.id=id;n.setAttribute('hidden','');var f=n.querySelectorAll('[data-f]');
for(var i=0;i<f.length;i++){var k=f[i].getAttribute('data-f');if(k==='dt'){if(it.getAttribute('data-dt')!=='1'){f[i].parentNode.removeChild(f[i]);}continue;}f[i].textContent=it.getAttribute('data-'+k)||'';}
it.appendChild(n);return n;}
function setOpen(row,open){var p=open?build(row):document.getElementById(row.getAttribute('aria-controls'));row.setAttribute('aria-expanded',open?'true':'false');if(p){if(open){p.removeAttribute('hidden');}else{p.setAttribute('hidden','');}}}
window.DsttToggle=function(row){var it=row.parentNode;var ip=it.getAttribute('data-ip');var open=row.getAttribute('aria-expanded')!=='true';setOpen(row,open);if(open){window.DsttOpen[ip]=1;}else{delete window.DsttOpen[ip];}};
window.DsttCopy=function(ev,b){ev.stopPropagation();var ip=b.getAttribute('data-ip');var done=function(){var o=b.innerHTML;b.className='dstt-copy ok';b.textContent=C.copied;setTimeout(function(){b.className='dstt-copy';b.innerHTML=o;},1500);};
if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(ip).then(done,function(){fb();});}else{fb();}
function fb(){var t=document.createElement('textarea');t.value=ip;t.style.position='fixed';t.style.opacity='0';document.body.appendChild(t);t.select();try{document.execCommand('copy');done();}catch(e){}document.body.removeChild(t);}};
var rows=document.querySelectorAll('#ds-top-hosts .dstt-item');
for(var i=0;i<rows.length;i++){if(window.DsttOpen[rows[i].getAttribute('data-ip')]){setOpen(rows[i].querySelector('.dstt-row'),true);}}
window.DsttCur=C.range;
if(window.DsttTimer){clearInterval(window.DsttTimer);}
window.DsttTimer=setInterval(function(){var h=document.getElementById('ds-top-hosts');
if(!h||!document.body.contains(h)){clearInterval(window.DsttTimer);window.DsttTimer=null;return;}
if(h.offsetParent===null){return;}
if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible,.swal2-shown')){return;}
LoadAjaxSilent('ds-top-hosts',C.page+'?chart-top-hosts=yes&range='+encodeURIComponent(window.DsttCur||C.range));},60000);
})();</script>";
}
// ---- DSTT widgets end
// Hôte de l'onglet, rechargé à chaque changement de période (?chart-top-hosts=yes&range=).
function chart_top_hosts(){
    $tpl=new template_admin();
    $range=$_GET["range"]??"day";
    if(!isset(dsnt_ranges()[$range])){$range="day";}
    $m=dstt_model($range);
    if($m["state"]=="off"){
        echo dsnt_style().dstt_style().$tpl->_ENGINE_parse_body("<div class='dsnt'>".dstt_state_off()."</div>").dstt_js($range);
        return;
    }
    $h=array("<div class='dsnt'>",dstt_period_bar($range),dstt_banner($tpl,$m));
    if($m["state"]=="ok"){
        $h[]=dstt_tiles($tpl,$m);
        $h[]=dstt_ranking($tpl,$m);
    }elseif($m["state"]=="stopped"){
        $h[]=dstt_state_stopped();
    }elseif($m["state"]=="empty"){
        $h[]=dstt_state_empty($range);
    }else{
        $h[]=dstt_state_unreachable();
    }
    $h[]="</div>";
    echo dsnt_style().dstt_style().$tpl->_ENGINE_parse_body(implode("",$h)).dstt_js($range);
}

// ─── Protocols (maquette stitch_artica_traffic_protocols_redesign) ───
// ---- DSPR model begin
// /darkstats/protocols : octets et paquets par protocole, toutes cartes additionnées, liste NON triée
// (map Go). /darkstats/ports?protocol=tcp|udp : volume par port de DESTINATION (metrics.go).
function dspr_ports_max():int{return 5;}
function dspr_protos():array{
    // numéro IP => array(token nom courant, token exemple, couleur)
    return array(6=>array("dspr_n_tcp","dspr_d_tcp","#005447"),17=>array("dspr_n_udp","dspr_d_udp","#e67e22"),
        1=>array("dspr_n_icmp","dspr_d_icmp","#2e86de"),58=>array("dspr_n_icmp6","dspr_d_icmp6","#16a085"),
        47=>array("dspr_n_gre","dspr_d_gre","#d4ac0d"),50=>array("dspr_n_esp","dspr_d_esp","#c0392b"));
}
function dspr_model(string $range):array{
    $m=array("range"=>$range,"state"=>"ok","health"=>null,"cfg"=>null,"rows"=>array(),"bytes"=>0,"packets"=>0,"ports"=>array());
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableDarkStats"))==1){$m["state"]="off";return $m;}
    $m["health"]=dsnt_json("/darkstats/health");
    if($m["health"]===null){$m["state"]="unreachable";return $m;}
    if(empty($m["health"]->running)){$m["state"]="stopped";return $m;}
    $m["cfg"]=dsnt_json("/darkstats/config");
    $data=dsnt_json("/darkstats/protocols?range=$range");
    if(!is_array($data)){$m["state"]="unreachable";return $m;}
    $known=dspr_protos();
    foreach($data as $p){
        $num=intval($p->protocol??0);
        $b=floatval($p->bytes??0);$k=floatval($p->packets??0);
        if($b<=0 && $k<=0){continue;}
        $tech=(string)($p->name??"");
        if(!isset($known[$num]) || $tech=="" || strpos($tech,"proto_")===0){$tech="";}
        $m["rows"][]=array("num"=>$num,"tech"=>$tech,"bytes"=>$b,"packets"=>$k,"known"=>isset($known[$num]),
            "color"=>isset($known[$num])?$known[$num][2]:"#7f8c8d");
        $m["bytes"]+=$b;$m["packets"]+=$k;
    }
    if(count($m["rows"])==0 || $m["bytes"]<=0){$m["state"]="empty";return $m;}
    usort($m["rows"],function($a,$b){ if($a["bytes"]==$b["bytes"]){return $a["num"]-$b["num"];} return $a["bytes"]<$b["bytes"]?1:-1; });
    foreach($m["rows"] as $r){
        if($r["num"]!=6 && $r["num"]!=17){continue;}
        $f=$r["num"]==6?"tcp":"udp";
        $ports=dsnt_json("/darkstats/ports?range=$range&protocol=$f&limit=".dspr_ports_max());
        $list=array();
        if(is_array($ports)){
            foreach($ports as $pt){
                $list[]=array("port"=>intval($pt->port??0),"bytes"=>floatval($pt->bytes??0),"packets"=>floatval($pt->packets??0));
            }
            usort($list,function($a,$b){ if($a["bytes"]==$b["bytes"]){return $a["port"]-$b["port"];} return $a["bytes"]<$b["bytes"]?1:-1; });
        }
        $m["ports"][$r["num"]]=array_slice($list,0,dspr_ports_max());
    }
    return $m;
}
// ---- DSPR model end
// ---- DSPR widgets begin
function dspr_pct(float $part,float $total):string{
    if($total<=0 || $part<=0){return "0 %";}
    $p=100*$part/$total;
    if($p<0.1){return "&lt; 0.1 %";}
    if($p<10){return sprintf("%.1f %%",$p);}
    return sprintf("%d %%",round($p));
}
function dspr_name($tpl,array $r):string{
    $k=dspr_protos();
    return $r["known"]?dsnt_t($tpl,$k[$r["num"]][0]):dsnt_t($tpl,"dspr_n_other");
}
function dspr_sub($tpl,array $r):string{
    $k=dspr_protos();
    if(!$r["known"]){return dsnt_t($tpl,"dspr_d_other",array(strval($r["num"])));}
    return $r["tech"]." · ".dsnt_t($tpl,$k[$r["num"]][1]);
}
function dspr_banner($tpl,array $m):string{
    $h=array("<div class='dsnt-card dsnt-banner'><div class='dsnt-bico' aria-hidden='true'><i class='fas fa-diagram-project'></i></div>");
    $h[]="<div class='dsnt-btxt'><h2>{dspr_banner_title}</h2><p>{dspr_banner_text}</p><div class='dsnt-chips'>";
    $hl=$m["health"];
    if(is_object($hl)){
        $n=is_array($hl->interfaces??null)?count($hl->interfaces):0;
        if($n>0){$h[]="<span class='dsnt-pill'><i class='fas fa-network-wired'></i>".dsnt_h($tpl,$n==1?"dsnt_chip_cards1":"dsnt_chip_cards",array(strval($n)))."</span>";}
    }
    $h[]="<span class='dsnt-pill'><i class='fas fa-eye-slash'></i>{dsnt_chip_content}</span>";
    $c=$m["cfg"];
    if(is_object($c) && intval($c->day_retention_days??0)>0){
        $h[]="<span class='dsnt-pill'><i class='fas fa-clock-rotate-left'></i>".dsnt_h($tpl,"dspr_chip_keep",array(strval(intval($c->day_retention_days))))."</span>";
    }
    $h[]="</div></div></div>";
    return implode("",$h);
}
function dspr_period_bar($tpl,string $range):string{
    $h=array("<div class='dspr-bar'><div class='dspr-bar-p'><span id='dspr-plbl'>{dsnt_period}</span><div class='dsnt-seg' role='radiogroup' aria-labelledby='dspr-plbl'>");
    foreach(dsnt_ranges() as $k=>$label){
        $on=$k===$range;
        $h[]="<a href='javascript:blur();' role='radio' aria-checked='".($on?"true":"false")."'".($on?" class='on'":"")." OnClick=\"DsprRange('$k');\">$label</a>";
    }
    $h[]="</div></div><a class='dspr-refresh' href='javascript:blur();' OnClick=\"DsprRange(window.DsprCur);\"><i class='fas fa-rotate' aria-hidden='true'></i>{dspr_refresh}</a></div>";
    return implode("",$h);
}
// 30 jours lus dans l'étage « jour », qui n'en garde que day_retention_days.
function dspr_keep_note($tpl,array $m):string{
    $c=$m["cfg"];
    if($m["range"]!="month" || !is_object($c)){return "";}
    $d=intval($c->day_retention_days??0);
    if($d<=0 || $d>=30){return "";}
    return "<div class='dspr-note'><i class='fas fa-circle-info' aria-hidden='true'></i><span>".dsnt_h($tpl,"dspr_keep_note",array(strval($d)))."</span></div>";
}
function dspr_summary($tpl,array $m):string{
    $top=$m["rows"][0];
    $who=dspr_name($tpl,$top)." (".($top["tech"]!==""?$top["tech"]:strval($top["num"])).")";
    $txt=dsnt_t($tpl,"dspr_summary",array(dstt_range_label($tpl,$m["range"]),"\x01".$who."\x02","\x01".html_entity_decode(dspr_pct($top["bytes"],$m["bytes"]))."\x02"));
    $txt=str_replace(array("\x01","\x02"),array("<b>","</b>"),htmlspecialchars($txt));
    $n=count($m["rows"]);
    $vals=array(dsnt_bytes($m["bytes"]),dsnt_count($m["packets"]));
    if($n>1){array_unshift($vals,strval($n));}
    $sub=dsnt_h($tpl,$n==1?"dspr_summary_sub1":"dspr_summary_sub",$vals);
    return "<div class='dspr-sum'><h3>$txt</h3><p>$sub</p></div>";
}
// Anneau SVG : circonférence 100, chaque tranche = sa part en %.
function dspr_donut($tpl,array $m):string{
    $acc=0;$c=array();$alt=array();
    foreach($m["rows"] as $r){
        $p=100*$r["bytes"]/$m["bytes"];
        if($p<=0){continue;}
        $c[]="<circle cx='21' cy='21' r='15.9155' fill='none' stroke='{$r["color"]}' stroke-width='6' stroke-dasharray='".round($p,3)." ".round(100-$p,3)."' stroke-dashoffset='".round(25-$acc,3)."'></circle>";
        $acc+=$p;
        $alt[]=dspr_name($tpl,$r)." ".html_entity_decode(dspr_pct($r["bytes"],$m["bytes"]));
    }
    $label=dsnt_h($tpl,"dspr_donut_alt",array(implode(", ",$alt)));
    return "<div class='dspr-donut'><svg viewBox='0 0 42 42' role='img' aria-label=\"$label\"><circle cx='21' cy='21' r='15.9155' fill='none' stroke='#f3f4f6' stroke-width='6'></circle>".implode("",$c)."</svg>"
        ."<div class='dspr-dc'><b>".dsnt_bytes($m["bytes"])."</b><span>".dsnt_h($tpl,"dspr_in_period",array(dstt_range_label($tpl,$m["range"])))."</span></div></div>";
}
function dspr_ports($tpl,array $m,array $r,string $pid):string{
    $list=$m["ports"][$r["num"]]??array();
    $h=array("<div class='dspr-panel' id='$pid' hidden><p class='dspr-ph'>{dspr_ports_help}</p>");
    if(count($list)==0){
        $h[]="<p class='dspr-none'>{dspr_no_port}</p>";
    }else{
        $svc=dstt_services();
        $share=dsnt_h($tpl,"dspr_col_share",array($r["tech"]));
        $h[]="<table class='dspr-pt'><thead><tr><th>{dspr_col_port}</th><th>{dspr_col_volume}</th><th>{dspr_col_packets}</th><th>$share</th></tr></thead><tbody>";
        foreach($list as $p){
            $name=dsnt_h($tpl,"dspr_port",array(strval($p["port"])));
            if(isset($svc[$p["port"]])){$name.=" <span>".htmlspecialchars($svc[$p["port"]][0]." · ".dsnt_t($tpl,$svc[$p["port"]][1]))."</span>";}
            $h[]="<tr><td>$name</td><td>".dsnt_bytes($p["bytes"])."</td><td>".dsnt_count($p["packets"])."</td><td>".dspr_pct($p["bytes"],$r["bytes"])."</td></tr>";
        }
        $h[]="</tbody></table>";
    }
    $h[]="<a class='dsnt-link dspr-open' href='javascript:blur();' OnClick=\"DsprTab(3);\">{dspr_open_ports} <i class='fas fa-arrow-right' aria-hidden='true'></i></a></div>";
    return implode("",$h);
}
function dspr_row($tpl,array $m,array $r):string{
    $pct=dspr_pct($r["bytes"],$m["bytes"]);
    $w=round(100*$r["bytes"]/$m["bytes"],2);
    $avg=$r["packets"]>0?dsnt_h($tpl,"dspr_per_packet",array(dsnt_bytes($r["bytes"]/$r["packets"]))):"";
    $pk=dsnt_h($tpl,"dspr_packets",array(dsnt_count($r["packets"])));
    $more="";$panel="";
    if($r["num"]==6 || $r["num"]==17){
        $pid="dspr-p-".$r["num"];
        $more="<button type='button' class='dspr-more' aria-expanded='false' aria-controls='$pid' data-k='{$r["num"]}' OnClick=\"DsprToggle(this);\">{dspr_see_ports} <i class='fas fa-chevron-down' aria-hidden='true'></i></button>";
        $panel=dspr_ports($tpl,$m,$r,$pid);
    }
    return "<div class='dspr-item'><div class='dspr-row'><i class='dspr-dot' style='background:{$r["color"]}' aria-hidden='true'></i>"
        ."<div class='dspr-id'><b>".htmlspecialchars(dspr_name($tpl,$r))."</b><span>".htmlspecialchars(dspr_sub($tpl,$r))."</span>$more</div>"
        ."<div class='dspr-track'><i style='width:$w%;background:{$r["color"]}'></i></div><span class='dspr-pct'>$pct</span>"
        ."<div class='dspr-num'><b>".dsnt_bytes($r["bytes"])."</b><span>$pk".($avg!==""?" · $avg":"")."</span></div></div>$panel</div>";
}
function dspr_card($tpl,array $m):string{
    $h=array("<div class='dsnt-card dspr-card'><div class='dspr-ch'><h3>{dspr_card_title}</h3><p>{dspr_card_help}</p></div><div class='dspr-grid'>");
    $h[]=dspr_donut($tpl,$m);
    $h[]="<div class='dspr-list'>";
    foreach($m["rows"] as $r){$h[]=dspr_row($tpl,$m,$r);}
    $h[]="</div></div></div>";
    return implode("",$h);
}
function dspr_read():string{
    return "<div class='dsnt-card dspr-read'><h4>{dspr_read_title}</h4><div class='dsnt-read'>"
        ."<div><i class='fas fa-scale-balanced' aria-hidden='true'></i><span>{dspr_read_1}</span></div>"
        ."<div><i class='fas fa-magnifying-glass' aria-hidden='true'></i><span>{dspr_read_2} <a class='dsnt-link' href='javascript:blur();' OnClick=\"DsprTab(1);\">{top_talkers}</a></span></div>"
        ."<div><i class='fas fa-ruler' aria-hidden='true'></i><span>{dspr_read_3}</span></div></div></div>";
}
function dspr_state_empty($tpl,string $range):string{
    $next=$range=="day"?"month":"day";
    $lbl=$next=="day"?"{dstt_show_24h}":"{dstt_show_30d}";
    return dstt_state_card("fa-hourglass-half","",dsnt_h($tpl,"dspr_empty_title",array(dstt_range_label($tpl,$range))),"{dstt_empty_text}",
        "<br><a class='dsnt-btn' href='javascript:blur();' OnClick=\"DsprRange('$next');\"><i class='fas fa-clock-rotate-left'></i>$lbl</a>");
}
function dspr_state_stopped():string{
    return dstt_state_card("fa-circle-pause","warn","{dstt_stopped_title}","{dspr_stopped_text}",
        "<br><a class='dsnt-btn' href='javascript:blur();' OnClick=\"DsprTab(6);\"><i class='fas fa-heart-pulse'></i>{dstt_open_status}</a>");
}
function dspr_style():string{
    return "<style>
.dspr-bar{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:12px;flex-wrap:wrap}
.dspr-bar-p{display:flex;align-items:center;gap:10px}
.dspr-bar-p>span{font-size:13px;color:#6b7280;font-weight:600}
.dspr-refresh{display:inline-flex;align-items:center;gap:8px;padding:7px 14px;border-radius:8px;font-size:13px;font-weight:600;color:#005447;text-decoration:none}
.dspr-refresh:hover{background:#e6eeed;color:#003d33;text-decoration:none}
.dspr-refresh:focus-visible,.dspr-more:focus-visible,.dspr-open:focus-visible{outline:2px solid #005447;outline-offset:2px}
.dspr-note{display:flex;gap:10px;align-items:flex-start;margin-bottom:14px;padding:10px 14px;border-radius:10px;font-size:13px;background:#f9fafb;color:#4b5563;border:1px solid #e5e7eb}
.dspr-note i{margin-top:3px}
.dspr-sum{margin:4px 0 16px 0}
.dspr-sum h3{margin:0;font-size:18px;font-weight:600;color:#111827;line-height:1.4}
.dspr-sum h3 b{color:#005447}
.dspr-sum p{margin:4px 0 0 0;font-size:13px;color:#6b7280}
.dspr-card{padding:22px 24px;margin-bottom:16px}
.dspr-ch{padding-bottom:14px;margin-bottom:18px;border-bottom:1px solid #eef0f3}
.dspr-ch h3{margin:0;font-size:16px;font-weight:600;color:#1f2937}
.dspr-ch p{margin:4px 0 0 0;font-size:13px;color:#6b7280}
.dspr-grid{display:grid;grid-template-columns:320px minmax(0,1fr);gap:32px;align-items:start}
.dspr-donut{position:relative;width:280px;height:280px;margin:0 auto}
.dspr-donut svg{width:100%;height:100%;transform:rotate(0deg)}
.dspr-dc{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center}
.dspr-dc b{font-size:26px;font-weight:600;color:#111827;line-height:1.2}
.dspr-dc span{font-size:13px;color:#6b7280;margin-top:4px}
.dspr-list{min-width:0}
.dspr-item{border-bottom:1px solid #f1f5f9}
.dspr-item:last-child{border-bottom:0}
.dspr-row{display:grid;grid-template-columns:12px minmax(0,1fr) minmax(120px,220px) 64px 220px;gap:16px;align-items:center;padding:12px 0;min-height:56px}
.dspr-dot{width:12px;height:12px;border-radius:50%;display:block}
.dspr-id{min-width:0}
.dspr-id b{display:block;font-size:14px;font-weight:600;color:#1f2937}
.dspr-id>span{display:block;font-size:13px;color:#6b7280;line-height:1.4}
.dspr-more{border:0;background:transparent;padding:2px 0;margin-top:4px;font-size:13px;font-weight:600;color:#005447;cursor:pointer}
.dspr-more:hover{color:#003d33;text-decoration:underline}
.dspr-more svg,.dspr-more i{transition:transform .15s;margin-left:4px}
.dspr-more[aria-expanded='true'] svg,.dspr-more[aria-expanded='true'] i{transform:rotate(180deg)}
.dspr-track{height:8px;border-radius:4px;background:#f3f4f6;overflow:hidden}
.dspr-track i{display:block;height:100%;border-radius:4px;min-width:2px}
.dspr-pct{font-size:14px;font-weight:600;color:#1f2937;text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
.dspr-num{text-align:right;font-variant-numeric:tabular-nums;min-width:0}
.dspr-num b{display:block;font-size:14px;color:#1f2937}
.dspr-num span{font-size:13px;color:#6b7280;white-space:nowrap}
.dspr-panel{background:#f9fafb;border:1px solid #eef0f3;border-radius:10px;padding:14px 16px;margin:0 0 14px 28px}
.dspr-panel[hidden]{display:none}
.dspr-ph{margin:0 0 10px 0;font-size:13px;color:#6b7280}
.dspr-none{margin:0 0 10px 0;font-size:13px;color:#6b7280}
.dspr-pt{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#1f2937}
.dspr-pt th{text-align:left;font-weight:600;color:#4b5563;padding:8px 12px;border-bottom:1px solid #e5e7eb;background:#fff}
.dspr-pt td{padding:8px 12px;border-bottom:1px solid #f1f5f9;font-variant-numeric:tabular-nums}
.dspr-pt tr:last-child td{border-bottom:0}
.dspr-pt th+th,.dspr-pt td+td{text-align:right}
.dspr-pt td span{color:#6b7280;margin-left:6px}
.dspr-open{display:inline-block;margin-top:10px;font-size:13px}
.dspr-read{padding:16px 20px}
.dspr-read h4{margin:0 0 12px 0;font-size:13px;font-weight:700;color:#1f2937;text-transform:uppercase;letter-spacing:.05em}
.dspr-read .dsnt-read{padding:0}
@media (max-width:1200px){.dspr-grid{grid-template-columns:minmax(0,1fr)}.dspr-row{grid-template-columns:12px minmax(0,1fr) 140px 60px 200px}.dspr-num span{white-space:normal}}
@media (prefers-reduced-motion:no-preference){.dspr-track i{animation:dsttGrow .25s ease-out}.dspr-donut svg{animation:dsntIn .25s ease-out}.dspr-panel{animation:dsntIn .15s ease-out}}
</style>";
}
function dspr_js(string $range):string{
    $page=CurrentPageName();
    $J=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $cfg=json_encode(array("page"=>$page,"range"=>$range),$J);
    return "<script>NoSpinner();(function(){var C=$cfg;
window.DsprOpen=window.DsprOpen||{};
window.DsprRange=function(r){r=r||C.range;window.DsprCur=r;LoadAjax('ds-protocols',C.page+'?chart-protocols=yes&range='+encodeURIComponent(r));};
window.DsprTab=function(n){var h=document.getElementById('ds-protocols');var c=h?h.closest('.tabs-container'):null;var a=c?c.querySelectorAll('ul.nav-tabs>li>a'):[];if(a.length>n){a[n].click();}};
function setOpen(b,open){var p=document.getElementById(b.getAttribute('aria-controls'));b.setAttribute('aria-expanded',open?'true':'false');if(p){if(open){p.removeAttribute('hidden');}else{p.setAttribute('hidden','');}}}
window.DsprToggle=function(b){var k=b.getAttribute('data-k');var open=b.getAttribute('aria-expanded')!=='true';setOpen(b,open);if(open){window.DsprOpen[k]=1;}else{delete window.DsprOpen[k];}};
var bs=document.querySelectorAll('#ds-protocols .dspr-more');
for(var i=0;i<bs.length;i++){if(window.DsprOpen[bs[i].getAttribute('data-k')]){setOpen(bs[i],true);}}
window.DsprCur=C.range;
if(window.DsprTimer){clearInterval(window.DsprTimer);}
window.DsprTimer=setInterval(function(){var h=document.getElementById('ds-protocols');
if(!h||!document.body.contains(h)){clearInterval(window.DsprTimer);window.DsprTimer=null;return;}
if(h.offsetParent===null){return;}
if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible,.swal2-shown')){return;}
LoadAjaxSilent('ds-protocols',C.page+'?chart-protocols=yes&range='+encodeURIComponent(window.DsprCur||C.range));},60000);
})();</script>";
}
// ---- DSPR widgets end
// Hôte de l'onglet, rechargé à chaque changement de période (?chart-protocols=yes&range=).
function chart_protocols(){
    $tpl=new template_admin();
    $range=$_GET["range"]??"day";
    if(!isset(dsnt_ranges()[$range])){$range="day";}
    $m=dspr_model($range);
    $css=dsnt_style().dstt_style().dspr_style();
    $js=dspr_js($range);
    $h=array("<div class='dsnt dspr'>");
    if($m["state"]=="off"){
        $h[]=dstt_state_off();
    }else{
        $h[]=dspr_banner($tpl,$m);
        $h[]=dspr_period_bar($tpl,$range);
        if($m["state"]=="unreachable"){$h[]=dstt_state_unreachable();}
        elseif($m["state"]=="stopped"){$h[]=dspr_state_stopped();}
        else{
            $h[]=dspr_keep_note($tpl,$m);
            if($m["state"]=="empty"){$h[]=dspr_state_empty($tpl,$range);}
            else{$h[]=dspr_summary($tpl,$m);$h[]=dspr_card($tpl,$m);$h[]=dspr_read();}
        }
    }
    $h[]="</div>";
    echo $css.$tpl->_ENGINE_parse_body(implode("",$h)).$js;
}


// ─── Tab: Interfaces (maquette stitch_network_interfaces_traffic_console) ───
// Écarté de la maquette (inventé) : « Ready to record when data crosses card », « Large = web
// services », « Legacy standard / Next-generation 128-bit addresses », version du collecteur,
// simulateurs d'état. Pas de sens entrant/sortant ni de débit par carte : le démon n'en garde pas.
function tab_interfaces(){
    $page=CurrentPageName();
    echo "<div id='ds-iface-ts'></div><script>LoadAjax('ds-iface-ts','$page?chart-iface-ts=yes&range=day');</script>";
}
// ---- DSIF model begin
// Libellé, zone (interfaces.db, lecture seule) et adresse IPv4 (articarest) de chaque carte.
function dsif_nics():array{
    $out=array();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $rows=$q->QUERY_SQL("SELECT Interface,NICNAME,netzone FROM nics");
    if(is_array($rows)){
        foreach($rows as $r){
            $n=trim((string)($r["Interface"]??""));
            if($n===""){continue;}
            $out[$n]=array("label"=>trim((string)($r["NICNAME"]??"")),"zone"=>trim((string)($r["netzone"]??"")),"ip"=>"");
        }
    }
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interfaces"));
    if(is_object($j) && isset($j->interfaces) && is_array($j->interfaces)){
        foreach($j->interfaces as $if){
            $n=trim((string)($if->Name??""));
            if($n===""){continue;}
            if(!isset($out[$n])){$out[$n]=array("label"=>"","zone"=>"","ip"=>"");}
            if(isset($if->Addresses) && is_array($if->Addresses)){
                foreach($if->Addresses as $a){
                    $ip=preg_replace('#/.*$#',"",(string)$a);
                    if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){$out[$n]["ip"]=$ip;break;}
                }
            }
        }
    }
    return $out;
}
function dsif_stat($o,string $k):float{
    return (is_object($o) && isset($o->$k))?floatval($o->$k):0.0;
}
function dsif_model(string $range,string $sel):array{
    $m=array("range"=>$range,"disabled"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableDarkStats"))==1,
        "health"=>null,"cfg"=>null,"running"=>false,"cards"=>array(),"total_bytes"=>0.0,"total_pkts"=>0.0,
        "seen"=>0,"sel"=>"","points"=>array(),"nics"=>array());
    if($m["disabled"]){return $m;}
    $m["health"]=dsnt_json("/darkstats/health");
    $m["running"]=is_object($m["health"]) && !empty($m["health"]->running);
    $m["cfg"]=dsnt_json("/darkstats/config");
    $m["nics"]=dsif_nics();
    $watched=array();
    if(is_object($m["health"]) && isset($m["health"]->interfaces) && is_array($m["health"]->interfaces)){
        foreach($m["health"]->interfaces as $n){$watched[(string)$n]=true;}
    }
    $data=$m["running"]?dsnt_json("/darkstats/interfaces?range=$range"):null;
    $byName=array();
    if(is_array($data)){
        foreach($data as $e){
            $n=(string)($e->name??"");
            if($n===""){continue;}
            $s=$e->stats??null;
            $c=array("name"=>$n,"bytes"=>dsif_stat($s,"Bytes"),"pkts"=>dsif_stat($s,"Packets"),
                "v4"=>dsif_stat($s,"IPv4Packets"),"v6"=>dsif_stat($s,"IPv6Packets"),"tcp"=>dsif_stat($s,"TCPPackets"),
                "udp"=>dsif_stat($s,"UDPPackets"),"icmp"=>dsif_stat($s,"ICMPPackets"),"other"=>dsif_stat($s,"OtherPkts"),
                "idle"=>false,"gone"=>count($watched)>0 && !isset($watched[$n]));
            if($c["bytes"]<=0 && $c["pkts"]<=0){continue;}
            $byName[$n]=$c;
        }
    }
    uasort($byName,function($a,$b){return $b["bytes"]<=>$a["bytes"];});
    foreach($byName as $c){$m["total_bytes"]+=$c["bytes"];$m["total_pkts"]+=$c["pkts"];}
    $m["seen"]=count($byName);
    // Cartes écoutées sans trafic sur la période : en fin de liste, grisées.
    $idle=array();
    foreach(array_keys($watched) as $n){
        if(!isset($byName[$n])){$idle[$n]=array("name"=>$n,"bytes"=>0,"pkts"=>0,"v4"=>0,"v6"=>0,"tcp"=>0,"udp"=>0,"icmp"=>0,"other"=>0,"idle"=>true,"gone"=>false);}
    }
    ksort($idle);
    $m["cards"]=array_slice(array_merge(array_values($byName),array_values($idle)),0,12);
    if($sel!=="" && isset($byName[$sel])){$m["sel"]=$sel;}
    elseif(count($byName)>0){$m["sel"]=(string)key($byName);}
    if($m["sel"]!==""){
        $ts=dsnt_json("/darkstats/interface/".rawurlencode($m["sel"])."/timeseries?range=$range");
        if(is_array($ts)){
            foreach($ts as $pt){
                $m["points"][]=array("label"=>_ds_fmt_time((string)($pt->time??""),$range),
                    "bytes"=>dsif_stat($pt->stats??null,"Bytes"),"pkts"=>dsif_stat($pt->stats??null,"Packets"));
            }
        }
    }
    return $m;
}
// ---- DSIF model end
// ---- DSIF widgets begin
function dsif_card($m,string $n):array{
    foreach($m["cards"] as $c){if($c["name"]===$n){return $c;}}
    return array();
}
function dsif_nic($m,string $n):array{
    return isset($m["nics"][$n])?$m["nics"][$n]:array("label"=>"","zone"=>"","ip"=>"");
}
function dsif_icon(string $n):string{
    if(preg_match('#^(br|virbr|docker)#',$n)){return "fas fa-network-wired";}
    if(preg_match('#^bond#',$n)){return "fas fa-link";}
    if(preg_match('#^(wl|wlan)#',$n)){return "fas fa-wifi";}
    if(preg_match('#^(tun|tap|wg|ppp|ipsec|vti)#',$n)){return "fas fa-shield-halved";}
    return "fas fa-ethernet";
}
function dsif_unit(string $range):string{
    $u=array("last15m"=>"minute","hour"=>"minute","day"=>"hour","week"=>"day","month"=>"day");
    return $u[$range]??"minute";
}
function dsif_pct(float $a,float $b):string{
    if($b<=0){return "0";}
    $p=100*$a/$b;
    if($p>0 && $p<1){return "&lt;1";}
    return strval(intval(round($p)));
}
// Mini donut IPv4 / IPv6 (SVG, mesuré contre tous les paquets IP de la carte).
function dsif_donut(float $v4,float $v6):string{
    $t=$v4+$v6;
    if($t<=0){return "";}
    $r=8;$c=2*M_PI*$r;$d=round($c*$v6/$t,2);
    return "<svg width='22' height='22' viewBox='0 0 22 22' aria-hidden='true'><circle cx='11' cy='11' r='$r' fill='none' stroke='#005447' stroke-width='4'/>"
        ."<circle cx='11' cy='11' r='$r' fill='none' stroke='#80aaa3' stroke-width='4' stroke-dasharray='$d ".round($c,2)."' transform='rotate(-90 11 11)'/></svg>";
}
function dsif_banner($tpl,array $m):string{
    $h=array("<div class='dsnt-card dsnt-banner'><div class='dsnt-bico' aria-hidden='true'><i class='fas fa-ethernet'></i></div>");
    $h[]="<div class='dsnt-btxt'><h2>{dsif_banner_title}</h2><p>{dsif_banner_text}</p><div class='dsnt-chips'>";
    $h[]="<span class='dsnt-pill'><i class='fas fa-eye-slash'></i>{dsif_chip_content}</span>";
    $c=$m["cfg"];
    if(is_object($c) && isset($c->minute_retention_days)){
        $h[]="<span class='dsnt-pill'><i class='fas fa-clock'></i>".dsnt_h($tpl,"dsnt_chip_keep",array(
            strval(intval($c->minute_retention_days)),strval(intval($c->hour_retention_days??0)),strval(intval($c->day_retention_days??0))))."</span>";
    }
    $hl=$m["health"];
    if(is_object($hl)){
        $n=is_array($hl->interfaces??null)?count($hl->interfaces):0;
        $h[]="<span class='dsnt-pill'><i class='fas fa-ethernet'></i>".dsnt_h($tpl,$n==1?"dsnt_chip_cards1":"dsnt_chip_cards",array(strval($n)))."</span>";
        if($m["running"]){
            $ts=strtotime((string)($hl->last_flush??""));
            $ago=$ts?dsnt_ago($tpl,time()-$ts):"";
            $txt=$ago!==""?dsnt_h($tpl,"dsnt_chip_collecting_ago",array($ago)):"{dsnt_chip_collecting}";
            $title=$ts?" title='".date("Y-m-d H:i:s",$ts)."'":"";
            $h[]="<span class='dsnt-pill'$title><i class='fas fa-circle-check'></i>$txt</span>";
        }else{
            $h[]="<span class='dsnt-pill warn'><i class='fas fa-circle-pause'></i>{dsnt_chip_stopped}</span>";
        }
    }
    $h[]="</div></div></div>";
    return implode("",$h);
}
function dsif_period_bar($tpl,array $m):string{
    $need=array("week"=>7,"month"=>30);
    $note="";
    $c=$m["cfg"];
    if(isset($need[$m["range"]]) && is_object($c) && isset($c->day_retention_days)){
        $kept=intval($c->day_retention_days);
        if($kept>0 && $kept<$need[$m["range"]]){
            $note="<span class='dsif-keep'><i class='fas fa-circle-info'></i>".dsnt_h($tpl,"dsif_kept_note",array(strval($kept)))."</span>";
        }
    }
    $h=array("<div class='dsif-pbar'><p>{dsif_period_help}</p><div class='dsif-pright'>$note<div class='dsnt-seg' role='group' aria-label='{dsnt_period}'>");
    foreach(dsnt_ranges() as $k=>$label){
        $on=$k===$m["range"]?" class='on' aria-current='true'":"";
        $h[]="<a href='javascript:blur();'$on OnClick=\"DsifRange('$k');\">$label</a>";
    }
    $h[]="</div></div></div>";
    return implode("",$h);
}
function dsif_row($tpl,array $m,array $c):string{
    $n=$c["name"];
    $nic=dsif_nic($m,$n);
    $e=htmlspecialchars($n,ENT_QUOTES);
    $title=$nic["label"]!==""?htmlspecialchars($nic["label"]):$e;
    $sub=array();
    if($nic["label"]!==""){$sub[]="<code>$e</code>";}
    if($nic["ip"]!==""){$sub[]="<span class='dsif-mono'>".htmlspecialchars($nic["ip"])."</span>";}
    if($nic["zone"]!==""){$sub[]="<span>".dsnt_h($tpl,"dsif_zone",array($nic["zone"]))."</span>";}
    if($c["gone"]){$sub[]="<span class='dsif-tag'><i class='fas fa-eye-slash'></i>{dsif_gone}</span>";}
    $cls="dsif-row";
    if($c["idle"]){$cls.=" idle";}
    if($c["gone"]){$cls.=" gone";}
    $selected=$n===$m["sel"];
    if($selected){$cls.=" on";}
    $cell="<div class='dsif-c-card'><span class='dsif-ico' aria-hidden='true'><i class='".dsif_icon($n)."'></i></span><span class='dsif-names'><b title='$e'>$title</b><span class='dsif-sub'>".implode("",$sub)."</span></span></div>";
    if($c["idle"]){
        return "<div class='$cls'>$cell<div class='dsif-c-idle'><span class='dsif-tag'><i class='fas fa-moon'></i>{dsif_no_traffic}</span><span>{dsif_idle_help}</span></div></div>";
    }
    $pct=dsif_pct($c["bytes"],$m["total_bytes"]);
    $w=$m["total_bytes"]>0?round(100*$c["bytes"]/$m["total_bytes"],1):0;
    $avg=$c["pkts"]>0?$c["bytes"]/$c["pkts"]:0;
    $hint=$avg>=1000?"{dsif_avg_large}":($avg<300?"{dsif_avg_small}":"{dsif_avg_mixed}");
    $aria=$selected?"true":"false";
    $h=array("<div class='$cls' role='option' tabindex='0' aria-selected='$aria' data-n='$e' OnClick=\"DsifSel(this.getAttribute('data-n'));\" OnKeyDown=\"DsifKey(event,this);\">$cell");
    $h[]="<div class='dsif-num'><b>".dsnt_bytes($c["bytes"])."</b><span>".dsnt_h($tpl,"dsif_of_total",array(html_entity_decode($pct)." %"))."</span></div>";
    $h[]="<div class='dsif-share'><span class='dsif-track'><span style='width:$w%'></span></span><span>$pct %</span></div>";
    $h[]="<div class='dsif-num'><b>".dsnt_count($c["pkts"])."</b><span>{dsif_packets_sub}</span></div>";
    $h[]="<div class='dsif-num dsif-c-avg' title='{dsif_avg_hint}'><b>".($avg>0?dsnt_bytes($avg):"—")."</b><span>$hint</span></div>";
    $ipt=$c["v4"]+$c["v6"];
    $ipv=$ipt>0?dsif_donut($c["v4"],$c["v6"])."<span>".dsnt_h($tpl,"dsif_v6_pct",array(html_entity_decode(dsif_pct($c["v6"],$ipt))." %"))."</span>":"<span>—</span>";
    $h[]="<div class='dsif-ipv'>$ipv</div></div>";
    return implode("",$h);
}
function dsif_list($tpl,array $m):string{
    $h=array("<div class='dsnt-card dsif-list'><div class='dsif-lh'><div><h3>{dsif_list_title}");
    $h[]=" <span class='dsif-count'>".dsnt_h($tpl,$m["seen"]==1?"dsif_seen1":"dsif_seen",array(strval($m["seen"])))."</span></h3>";
    $h[]="<p>{dsif_list_help}</p></div>";
    if($m["total_bytes"]>0){
        $h[]="<div class='dsif-total'>".dsnt_h($tpl,"dsif_total",array("\x01".dsnt_bytes($m["total_bytes"])."\x02","\x01".dsnt_count($m["total_pkts"])."\x02"))."</div>";
    }
    $h[]="</div><div class='dsif-head' aria-hidden='true'><span>{dsif_col_card}</span><span>{dsif_col_data}</span><span>{dsif_col_share}</span><span>{dsif_col_packets}</span><span class='dsif-c-avg'>{dsif_col_avg}</span><span>{dsif_col_ipv}</span></div>";
    $h[]="<div role='listbox' aria-label='{dsif_list_title}'>";
    foreach($m["cards"] as $c){$h[]=dsif_row($tpl,$m,$c);}
    $h[]="</div></div>";
    return str_replace(array("\x01","\x02"),array("<b>","</b>"),implode("",$h));
}
function dsif_empty(string $ico,string $title,string $text,string $btn=""):string{
    return "<div class='dsnt-card dsnt-empty dsif-empty'><i class='$ico'></i><b>$title</b>$text".($btn!==""?"<br>$btn":"")."</div>";
}
// Graphique de la carte choisie : deux séries (données, paquets), une seule visible.
function dsif_detail($tpl,array $m):array{
    $n=$m["sel"];
    $nic=dsif_nic($m,$n);
    $e=htmlspecialchars($n);
    $unit=dsif_unit($m["range"]);
    $title=$nic["label"]!==""?htmlspecialchars($nic["label"]):$e;
    $h=array("<div class='dsnt-card dsif-detail'><div class='dsnt-ch-h'><div><h3>$title");
    if($nic["label"]!==""){$h[]=" <code class='dsif-badge'>$e</code>";}
    if($nic["ip"]!==""){$h[]=" <span class='dsif-mono dsif-ip'>".htmlspecialchars($nic["ip"])."</span>";}
    if($nic["zone"]!==""){$h[]=" <span class='dsif-zone'>".dsnt_h($tpl,"dsif_zone",array($nic["zone"]))."</span>";}
    $h[]="</h3><p>{dsnt_sub_$unit}</p></div>";
    if(count($m["points"])==0){
        $h[]="</div><div class='dsnt-empty dsif-empty'><i class='fas fa-moon'></i><b>".dsnt_h($tpl,"dsif_sel_empty",array($n))."</b>{dsif_sel_empty_text}</div></div>";
        return array(implode("",$h),"");
    }
    $h[]="<div class='dsnt-seg' role='tablist' aria-label='{dsif_metric}'><a href='javascript:blur();' role='tab' aria-selected='true' class='on' data-k='0' OnClick=\"DsifMetric(this,0);\">{dsif_data}</a>"
        ."<a href='javascript:blur();' role='tab' aria-selected='false' data-k='1' OnClick=\"DsifMetric(this,1);\">{dsif_packets}</a></div></div>";
    $labels=array();$bytes=array();$pkts=array();$peak=null;
    foreach($m["points"] as $p){
        $labels[]=$p["label"];$bytes[]=$p["bytes"];$pkts[]=$p["pkts"];
        if($peak===null || $p["bytes"]>$peak["bytes"]){$peak=$p;}
    }
    $mx=max($bytes);
    $div=1;$uname="B";
    foreach(array(array(1099511627776,"TB"),array(1073741824,"GB"),array(1048576,"MB"),array(1024,"KB")) as $u){if($mx>=$u[0]){$div=$u[0];$uname=$u[1];break;}}
    $scaled=array();
    foreach($bytes as $v){$scaled[]=round($v/$div,3);}
    $peakTxt="";
    if($peak!==null && $peak["bytes"]>0){
        $peakTxt=htmlspecialchars(dsnt_t($tpl,"dsif_peak",array($peak["label"],dsnt_bytes($peak["bytes"]))));
    }
    $h[]="<div class='dsif-peak'><span><i class='fas fa-arrow-trend-up'></i><b>$peakTxt</b> <span>{dsif_peak_help}</span></span><span>".dsnt_h($tpl,"dsif_res_$unit",array(strval(count($labels))))."</span></div>";
    $id="dsif-c-".md5($n.$m["range"].microtime());
    $aria=dsnt_h($tpl,"dsif_aria",array($n,dsnt_bytes(array_sum($bytes))));
    $h[]="<div class='dsnt-canvas'><canvas id='$id' role='img' aria-label=\"$aria\"></canvas></div>";
    $h[]=dsif_kinds($tpl,$m);
    $h[]="</div>";
    $J=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $lData=json_encode(dsnt_t($tpl,"dsif_data"),$J);
    $lPk=json_encode(dsnt_t($tpl,"dsif_packets"),$J);
    $ds=array(
        array("label"=>"d","data"=>$scaled,"borderColor"=>"#005447","backgroundColor"=>"rgba(0,84,71,0.12)","fill"=>"origin","tension"=>0.3,"pointRadius"=>0,"pointHitRadius"=>8,"borderWidth"=>2),
        array("label"=>"p","data"=>$pkts,"borderColor"=>"#005447","backgroundColor"=>"rgba(0,84,71,0.12)","fill"=>"origin","tension"=>0.3,"pointRadius"=>0,"pointHitRadius"=>8,"borderWidth"=>2,"hidden"=>true),
    );
    $s="<script>(function(){var L=".json_encode($labels,$J).",D=".json_encode($ds,$J).",B=".json_encode($bytes,$J).",P=".json_encode($pkts,$J).",U='$uname',LD=$lData,LP=$lPk;"
        ._ds_js_fmt_bytes()
        ."function cnt(v){v=Number(v)||0;if(v>=1e9){return (v/1e9).toFixed(1)+' G';}if(v>=1e6){return (v/1e6).toFixed(1)+' M';}if(v>=1e4){return (v/1e3).toFixed(1)+' k';}return Math.round(v).toLocaleString();}"
        ."function draw(){"._ds_chart_helper_js()
        ."var rm=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;"
        ."DsChart('iface','$id',{type:'line',data:{labels:L,datasets:D},options:{responsive:true,maintainAspectRatio:false,animation:rm?false:{duration:250},"
        ."interaction:{mode:'index',intersect:false},"
        ."scales:{x:{grid:{display:false},ticks:{autoSkip:true,maxRotation:0,font:{size:13}}},y:{beginAtZero:true,ticks:{font:{size:13},callback:function(v){var c=window.DsCharts&&window.DsCharts.iface;if(c&&c.isDatasetVisible(1)){return cnt(v);}return (Math.round(v*100)/100)+' '+U;}}}},"
        ."plugins:{legend:{display:false},datalabels:{display:false},tooltip:{callbacks:{label:function(x){var i=x.dataIndex;return [LD+': '+_fmtB(B[i]),LP+': '+cnt(P[i])];}}}}}});}"
        ."window.DsifMetric=function(a,k){var c=window.DsCharts&&window.DsCharts.iface;var s=a.parentNode.querySelectorAll('a');for(var i=0;i<s.length;i++){var on=s[i]===a;s[i].className=on?'on':'';s[i].setAttribute('aria-selected',on?'true':'false');}"
        ."if(!c){return;}c.setDatasetVisibility(0,k===0);c.setDatasetVisibility(1,k===1);c.update();};"
        ."if(typeof Chart!=='undefined'){draw();return;}"
        ."var e=document.createElement('script');e.src='angular/js/plugins/chartJs/Chart.min.4.5.1.js';e.onload=draw;document.head.appendChild(e);})();</script>";
    return array(implode("",$h),$s);
}
// Répartition par protocole et par version d'IP de la carte choisie, sur toute la période.
function dsif_kinds($tpl,array $m):string{
    $c=dsif_card($m,$m["sel"]);
    if(count($c)==0){return "";}
    $parts=array(array("tcp","#005447","TCP","dsif_k_tcp"),array("udp","#2e86de","UDP","dsif_k_udp"),
        array("icmp","#e67e22","ICMP","dsif_k_icmp"),array("other","#7f8c8d","{dsif_other}","dsif_k_other"));
    $tot=0;foreach($parts as $p){$tot+=$c[$p[0]];}
    $h=array("<div class='dsif-two'><div class='dsif-box'><div class='dsif-bh'><h4>{dsif_kind_title}</h4><span>".dsnt_h($tpl,"dsif_pkts_total",array(number_format($tot)))."</span></div><p>{dsif_kind_help}</p>");
    if($tot>0){
        $h[]="<div class='dsif-stack' role='img' aria-label='{dsif_kind_title}'>";
        foreach($parts as $p){$w=round(100*$c[$p[0]]/$tot,2);if($w>0){$h[]="<span style='width:$w%;background:{$p[1]}'></span>";}}
        $h[]="</div><div class='dsif-leg'>";
        foreach($parts as $p){
            $h[]="<div><i style='background:{$p[1]}'></i><span><b>{$p[2]} ".dsif_pct($c[$p[0]],$tot)." %</b>{".$p[3]."}</span></div>";
        }
        $h[]="</div>";
    }else{$h[]="<p class='dsif-none'>—</p>";}
    $h[]="</div>";
    $ipt=$c["v4"]+$c["v6"];
    $h[]="<div class='dsif-box'><div class='dsif-bh'><h4>{dsif_ip_title}</h4></div><p>{dsif_ip_help}</p>";
    if($ipt>0){
        $h[]="<div class='dsif-ipg'>";
        foreach(array(array("v4","IPv4","v4"),array("v6","IPv6","v6")) as $p){
            $h[]="<div class='dsif-ipc'><div><span>".dsnt_h($tpl,"dsif_ip_packets",array($p[1]))."</span><b>".dsif_pct($c[$p[0]],$ipt)." %</b><em>".dsnt_h($tpl,"dsif_pkts_total",array(number_format($c[$p[0]])))."</em></div><span class='dsif-vb {$p[2]}'>{$p[2]}</span></div>";
        }
        $w=round(100*$c["v4"]/$ipt,2);
        $h[]="</div><div class='dsif-track wide' role='img' aria-label='IPv4 ".dsif_pct($c["v4"],$ipt)." %'><span style='width:$w%'></span></div>";
    }else{$h[]="<p class='dsif-none'>—</p>";}
    $h[]="</div></div>";
    return implode("",$h);
}
function dsif_foot($tpl,array $m):string{
    $drop="";
    if(is_object($m["health"]) && isset($m["health"]->packets_dropped)){
        $drop="<span>".dsnt_h($tpl,"dsif_dropped",array(number_format(floatval($m["health"]->packets_dropped))))."</span>";
    }
    return "<div class='dsif-foot'><span><i class='fas fa-circle-question'></i>{dsif_packet_def}</span>$drop</div>";
}
function dsif_js(string $range,string $sel):string{
    $page=CurrentPageName();
    $r=json_encode($range);$s=json_encode($sel,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
    return "<script>NoSpinner();(function(){var R=$r,S=$s;"
        ."function go(r,s,silent){var u='$page?chart-iface-ts=yes&range='+r+'&iface='+encodeURIComponent(s);if(silent){LoadAjaxSilent('ds-iface-ts',u);}else{LoadAjax('ds-iface-ts',u);}}"
        ."window.DsifRange=function(r){go(r,S,false);};"
        ."window.DsifSel=function(n){if(n===S){return;}go(R,n,true);};"
        ."window.DsifKey=function(ev,el){if(ev.key==='Enter'||ev.key===' '){ev.preventDefault();DsifSel(el.getAttribute('data-n'));}};"
        ."window.DsifTab=function(n){var h=document.getElementById('ds-iface-ts');var c=h?h.closest('.tabs-container'):null;var a=c?c.querySelectorAll('ul.nav-tabs>li>a'):[];if(a.length>n){a[n].click();}};"
        ."})();</script>";
}
function dsif_style():string{
    return "<style>
.dsif-pbar{display:flex;justify-content:space-between;align-items:center;gap:16px;margin:0 0 16px 0}
.dsif-pbar p{margin:0;font-size:13px;color:#6b7280;max-width:520px}
.dsif-pright{display:flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:flex-end}
.dsif-keep{font-size:13px;color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:999px;padding:3px 10px;display:inline-flex;gap:6px;align-items:center}
.dsif-list{margin-bottom:16px;overflow:hidden}
.dsif-lh{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding:18px 22px 12px 22px;background:#fafbfb}
.dsif-lh h3{margin:0;font-size:16px;font-weight:600;color:#1f2937}
.dsif-lh p{margin:4px 0 0 0;font-size:13px;color:#6b7280;max-width:720px}
.dsif-count{display:inline-block;margin-left:8px;font-size:13px;font-weight:500;color:#374151;background:#f3f4f6;border-radius:999px;padding:2px 10px;vertical-align:middle}
.dsif-total{font-size:13px;color:#4b5563;white-space:nowrap}
.dsif-total b{color:#1f2937}
.dsif-head,.dsif-row{display:grid;grid-template-columns:minmax(0,2.4fr) minmax(0,1.1fr) minmax(0,1.5fr) minmax(0,1fr) minmax(0,1.2fr) minmax(0,.8fr);gap:16px;align-items:center;padding:0 22px}
.dsif-head{padding-top:10px;padding-bottom:10px;border-top:1px solid #eef0f3;border-bottom:1px solid #eef0f3;font-size:13px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em}
.dsif-head span:nth-child(2),.dsif-head span:nth-child(4),.dsif-head span:nth-child(5),.dsif-head span:nth-child(6){text-align:right}
.dsif-row{min-height:72px;border-bottom:1px solid #f1f3f5;border-left:3px solid transparent;padding-left:19px;cursor:pointer;box-sizing:border-box}
.dsif-row:last-child{border-bottom:0}
.dsif-row:hover{background:#f8faf9}
.dsif-row.on{background:#f2f6f6;border-left-color:#005447}
.dsif-row:focus-visible{outline:2px solid #005447;outline-offset:-2px}
.dsif-row.idle{cursor:default;background:#fcfcfd;color:#9ca3af}
.dsif-row.gone{opacity:.7}
.dsif-c-card{display:flex;align-items:center;gap:12px;min-width:0}
.dsif-ico{flex:0 0 38px;height:38px;border-radius:10px;background:#f2f6f6;color:#005447;display:flex;align-items:center;justify-content:center;font-size:16px}
.dsif-row.on .dsif-ico{background:#005447;color:#fff}
.dsif-row.idle .dsif-ico{background:#f3f4f6;color:#9ca3af}
.dsif-names{display:flex;flex-direction:column;min-width:0;gap:3px}
.dsif-names b{font-size:15px;font-weight:600;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dsif-row.idle .dsif-names b{color:#6b7280}
.dsif-sub{display:flex;flex-wrap:wrap;gap:4px 10px;font-size:13px;color:#6b7280;align-items:center}
.dsif-sub code,.dsif-badge{font-family:monospace;font-size:13px;background:#f3f4f6;color:#374151;padding:1px 6px;border-radius:4px}
.dsif-mono{font-family:monospace;font-size:13px}
.dsif-tag{display:inline-flex;align-items:center;gap:5px;font-size:13px;color:#4b5563;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:999px;padding:1px 8px}
.dsif-c-idle{grid-column:2 / -1;display:flex;justify-content:space-between;align-items:center;gap:12px;font-size:13px;color:#6b7280}
.dsif-num{text-align:right;display:flex;flex-direction:column;gap:2px;min-width:0}
.dsif-num b{font-size:15px;font-weight:600;color:#1f2937;font-variant-numeric:tabular-nums;white-space:nowrap}
.dsif-num span{font-size:13px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dsif-share{display:flex;align-items:center;gap:10px;font-size:13px;color:#374151;font-variant-numeric:tabular-nums;white-space:nowrap}
.dsif-track{flex:1 1 auto;max-width:130px;height:8px;border-radius:999px;background:#e5e7eb;overflow:hidden;display:block}
.dsif-track>span{display:block;height:100%;background:#005447;border-radius:999px;min-width:3px}
.dsif-track.wide{max-width:none;margin-top:14px;background:#bfd4d1}
.dsif-ipv{display:flex;align-items:center;justify-content:flex-end;gap:8px;font-size:13px;color:#374151;white-space:nowrap}
.dsif-detail{padding:20px 22px;margin-bottom:16px}
.dsif-detail h3{display:flex;flex-wrap:wrap;align-items:center;gap:10px}
.dsif-ip{color:#4b5563;font-size:13px;font-weight:400}
.dsif-zone{font-size:13px;font-weight:500;color:#005447;background:#e6eeed;border-radius:6px;padding:2px 8px}
.dsif-peak{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:14px;padding:10px 14px;border-radius:10px;background:#f2f6f6;border:1px solid #e6eeed;font-size:13px;color:#4b5563}
.dsif-peak b{color:#005447;margin-left:6px}
.dsif-peak i{color:#005447}
.dsif-two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:18px}
.dsif-box{border:1px solid #e5e7eb;border-radius:10px;padding:16px 18px;min-width:0}
.dsif-bh{display:flex;justify-content:space-between;align-items:baseline;gap:12px}
.dsif-bh h4{margin:0;font-size:15px;font-weight:600;color:#1f2937}
.dsif-bh span{font-size:13px;color:#6b7280;white-space:nowrap}
.dsif-box>p{margin:4px 0 12px 0;font-size:13px;color:#6b7280}
.dsif-stack{display:flex;height:12px;border-radius:999px;overflow:hidden;background:#e5e7eb}
.dsif-stack span{display:block;height:100%}
.dsif-leg{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 20px;margin-top:14px}
.dsif-leg div{display:flex;gap:8px;align-items:flex-start;font-size:13px;color:#6b7280;line-height:1.4}
.dsif-leg i{flex:0 0 10px;height:10px;border-radius:2px;margin-top:4px}
.dsif-leg b{display:block;color:#1f2937;font-weight:600}
.dsif-ipg{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.dsif-ipc{display:flex;justify-content:space-between;align-items:center;gap:8px;border:1px solid #eef0f3;border-radius:8px;padding:12px 14px;min-width:0}
.dsif-ipc>div{display:flex;flex-direction:column;gap:2px;min-width:0}
.dsif-ipc span{font-size:13px;font-weight:600;color:#4b5563;text-transform:uppercase;letter-spacing:.04em}
.dsif-ipc b{font-size:22px;font-weight:600;color:#1f2937}
.dsif-ipc em{font-style:normal;font-size:13px;color:#6b7280;font-family:monospace}
.dsif-vb{flex:0 0 40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px!important;text-transform:none!important}
.dsif-vb.v4{background:#005447;color:#fff!important}
.dsif-vb.v6{background:#e6eeed;color:#005447!important}
.dsif-none{color:#9ca3af;font-size:14px}
.dsif-empty>svg,.dsif-empty>i{font-size:32px;color:#9ca3af;display:block;margin:0 auto 12px auto}
.dsif-empty>svg.dsif-warn,.dsif-empty>i.dsif-warn{color:#d97706}
.dsif-foot{display:flex;justify-content:space-between;gap:16px;padding:12px 4px;font-size:13px;color:#6b7280}
.dsif-foot i{margin-right:6px}
@media (max-width:1200px){.dsif-two{grid-template-columns:minmax(0,1fr)}.dsif-c-avg{display:none!important}
.dsif-head,.dsif-row{grid-template-columns:minmax(0,2.4fr) minmax(0,1.1fr) minmax(0,1.5fr) minmax(0,1fr) minmax(0,.8fr)}}
@media (prefers-reduced-motion:no-preference){.dsif-list,.dsif-detail{animation:dsntIn .2s ease-out}}
</style>";
}
// ---- DSIF widgets end
// Hôte de l'onglet (?chart-iface-ts=yes&range=&iface=), rechargé au changement de période ou de carte.
function chart_interface_timeseries(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=(string)($_GET["range"]??"day");
    if(!isset(dsnt_ranges()[$range])){$range="day";}
    $sel=preg_replace('/[^A-Za-z0-9_.:@-]/',"",(string)($_GET["iface"]??""));
    $m=dsif_model($range,$sel);
    $js=dsif_js($range,$m["sel"]);
    if($m["disabled"]){
        $html="<div class='dsnt'>".dsif_empty("fas fa-chart-area","{dsnt_off_title}","{dsnt_off_text}",
            "<a class='dsnt-btn' href='javascript:blur();' OnClick=\"Loadjs('$page?install-js=yes');\"><i class='fas fa-power-off'></i>{install_feature}</a>")."</div>";
        echo dsnt_style().$tpl->_ENGINE_parse_body($html).$js;
        return;
    }
    $h=array("<div class='dsnt'>",dsif_banner($tpl,$m),dsif_period_bar($tpl,$m));
    $cjs="";
    if(!$m["running"]){
        $h[]=dsif_empty("fas fa-circle-pause dsif-warn","{dsif_stopped_title}","{dsif_stopped_text}",
            "<a class='dsnt-btn' href='javascript:blur();' OnClick=\"DsifTab(6);\"><i class='fas fa-heart-pulse'></i>{dsif_open_status}</a>");
    }elseif($m["seen"]==0){
        $alt=$range==="day"?"week":"day";
        $lbl=$range==="day"?"{dsif_try_7d}":"{dsif_try_24h}";
        if(count($m["cards"])>0){$h[]=dsif_list($tpl,$m);}
        $h[]=dsif_empty("fas fa-hourglass-half","{dsif_empty_title}","{dsif_empty_text}",
            "<a class='dsnt-btn' href='javascript:blur();' OnClick=\"DsifRange('$alt');\"><i class='fas fa-clock-rotate-left'></i>$lbl</a>");
    }else{
        $h[]=dsif_list($tpl,$m);
        list($dh,$cjs)=dsif_detail($tpl,$m);
        $h[]=$dh;
    }
    $h[]=dsif_foot($tpl,$m);
    $h[]="</div>";
    echo dsnt_style().dsif_style().$tpl->_ENGINE_parse_body(implode("",$h)).$cjs.$js;
}

// ─── Hosts (maquette stitch_artica_hosts_console_redesign) ───
// ---- DSHO model begin
// /darkstats/hosts : jusqu'à N adresses triées par volume total ; reçu = ce qui lui est adressé,
// envoyé = ce qu'elle émet (metrics.go). Une adresse renvoyée est mise en file de résolution
// inverse (déjà en cache = ignorée) : le nom peut apparaître au rafraîchissement suivant.
// On lit toujours 501 lignes : la 501e dit seulement « plus de 500 ».
function dsho_sizes():array{return array(50,200,500);}
function dsho_cap():int{return 500;}
function dsho_params():array{
    $range=(string)($_GET["range"]??"day");
    if(!isset(dsnt_ranges()[$range])){$range="day";}
    $limit=intval($_GET["limit"]??50);
    if(!in_array($limit,dsho_sizes())){$limit=50;}
    $where=(string)($_GET["where"]??"all");
    if(!in_array($where,array("all","local","inet"))){$where="all";}
    $q=trim((string)($_GET["q"]??""));
    $q=preg_replace('/[^\p{L}\p{N}\.\-_:]/u',"",$q);
    if(mb_strlen($q,"UTF-8")>64){$q=mb_substr($q,0,64,"UTF-8");}
    return array("range"=>$range,"limit"=>$limit,"where"=>$where,"q"=>$q);
}
function dsho_model(array $p):array{
    $m=array("p"=>$p,"state"=>"ok","health"=>null,"dns"=>null,"all"=>array(),"rows"=>array(),"more"=>false,
        "n_all"=>0,"n_local"=>0,"n_inet"=>0,"named"=>0,"matched"=>0,"max"=>0);
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableDarkStats"))==1){$m["state"]="off";return $m;}
    $m["health"]=dsnt_json("/darkstats/health");
    if($m["health"]===null){$m["state"]="unreachable";return $m;}
    if(empty($m["health"]->running)){$m["state"]="stopped";return $m;}
    $m["dns"]=dsnt_json("/darkstats/dns/status");
    $hosts=dsnt_json("/darkstats/hosts?range={$p["range"]}&limit=".(dsho_cap()+1));
    if(!is_array($hosts)){$m["state"]="unreachable";return $m;}
    if(count($hosts)>dsho_cap()){$m["more"]=true;$hosts=array_slice($hosts,0,dsho_cap());}
    $rank=0;
    foreach($hosts as $h){
        $ip=(string)($h->ip??"");
        if($ip==""){continue;}
        $bi=floatval($h->bytes_in??0);$bo=floatval($h->bytes_out??0);
        $local=dstt_is_local($ip);
        $name=trim((string)($h->hostname??""));
        $m["all"][]=array("ip"=>$ip,"name"=>$name,"local"=>$local,"in"=>$bi,"out"=>$bo,"total"=>$bi+$bo,
            "pin"=>floatval($h->packets_in??0),"pout"=>floatval($h->packets_out??0),
            "first"=>dstt_ts($h->first_seen??""),"last"=>dstt_ts($h->last_seen??""),"rank"=>$rank++);
        $m["n_all"]++;
        $m[$local?"n_local":"n_inet"]++;
        if($name!==""){$m["named"]++;}
    }
    if($m["n_all"]==0){$m["state"]="empty";return $m;}
    $q=mb_strtolower($p["q"],"UTF-8");
    foreach($m["all"] as $r){
        if($p["where"]=="local" && !$r["local"]){continue;}
        if($p["where"]=="inet" && $r["local"]){continue;}
        if($q!=="" && strpos(mb_strtolower($r["ip"]." ".$r["name"],"UTF-8"),$q)===false){continue;}
        $m["matched"]++;
        if(count($m["rows"])<$p["limit"]){
            $m["rows"][]=$r;
            if($r["total"]>$m["max"]){$m["max"]=$r["total"];}
        }
    }
    if($m["matched"]==0){$m["state"]=$q!==""?"nomatch":"nowhere";}
    return $m;
}
// ---- DSHO model end
// ---- DSHO widgets begin
function dsho_count_txt(array $m,int $n):string{return $m["more"] && $n>=dsho_cap()?dsho_cap()."+":strval($n);}
function dsho_banner($tpl,$health,$dns):string{
    $h=array("<div class='dsnt-card dsnt-banner'><div class='dsnt-bico' aria-hidden='true'><i class='fas fa-laptop-house'></i></div>");
    $h[]="<div class='dsnt-btxt'><h2>{dsho_banner_title}</h2><p>{dsho_banner_text}</p><div class='dsnt-chips'>";
    if(is_object($health)){
        $n=is_array($health->interfaces??null)?count($health->interfaces):0;
        if($n>0){$h[]="<span class='dsnt-pill'><i class='fas fa-network-wired'></i>".dsnt_h($tpl,$n==1?"dsnt_chip_cards1":"dsnt_chip_cards",array(strval($n)))."</span>";}
    }
    $h[]="<span class='dsnt-pill'><i class='fas fa-eye-slash'></i>{dsnt_chip_content}</span>";
    if(is_object($dns)){
        $h[]=!empty($dns->enabled)?"<span class='dsnt-pill'><i class='fas fa-signature'></i>{dsho_chip_names}</span>"
            :"<span class='dsnt-pill warn'><i class='fas fa-signature'></i>{dsho_chip_names_off}</span>";
    }
    $h[]="</div></div></div>";
    return implode("",$h);
}
function dsho_seg(string $id,string $label,array $opts,string $cur,string $jsfn):string{
    $h=array("<div class='dsnt-seg dsho-seg' role='radiogroup' aria-label=\"$label\" id='$id'>");
    foreach($opts as $k=>$txt){
        $on=$k===$cur;
        $h[]="<a href='javascript:blur();' role='radio' data-k='$k' aria-checked='".($on?"true":"false")."'".($on?" class='on'":"")." OnClick=\"$jsfn('$k');\">$txt</a>";
    }
    $h[]="</div>";
    return implode("",$h);
}
function dsho_toolbar($tpl,array $p):string{
    $where=array("all"=>"{dsho_f_all} <span class='dsho-n' data-c='all'></span>","local"=>"{dstt_on_network} <span class='dsho-n' data-c='local'></span>","inet"=>"{dstt_internet} <span class='dsho-n' data-c='inet'></span>");
    $sizes=array();
    foreach(dsho_sizes() as $s){$sizes[]="<option value='$s'".($s==$p["limit"]?" selected":"").">".dsnt_h($tpl,"dsho_n_devices",array(strval($s)))."</option>";}
    $ph=dsnt_h($tpl,"dsho_search_ph");$clr=dsnt_h($tpl,"dsho_clear");
    $q=htmlspecialchars($p["q"],ENT_QUOTES);
    $ex=array();
    foreach(array("192.168.1.","printer",".lan") as $e){$ex[]="<button type='button' OnClick=\"DshoExample('$e');\">$e</button>";}
    return "<div class='dsnt-card dsho-tools'><div class='dsho-trow'>"
        ."<div class='dsho-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i><input type='search' id='dsho-q' value='$q' placeholder=\"$ph\" aria-label=\"$ph\" autocomplete='off'>"
        ."<button type='button' class='dsho-clr' aria-label=\"$clr\" title=\"$clr\" OnClick=\"DshoClear();\"><i class='fas fa-xmark'></i></button></div>"
        .dsho_seg("dsho-where",dsnt_h($tpl,"dsho_where_label"),$where,$p["where"],"DshoWhere")
        .dsho_seg("dsho-range",dsnt_h($tpl,"dsnt_period"),dsnt_ranges(),$p["range"],"DshoRange")
        ."</div><div class='dsho-trow2'><label for='dsho-max'>{dsho_show}</label><select id='dsho-max' OnChange=\"DshoMax(this.value);\">".implode("",$sizes)."</select>"
        ."<a class='dspr-refresh' href='javascript:blur();' OnClick=\"DshoReload(false);\"><i class='fas fa-rotate' aria-hidden='true'></i>{dspr_refresh}</a>"
        ."<span class='dsho-ex'>{dsho_examples} ".implode("<i aria-hidden='true'>·</i>",$ex)."</span></div></div>";
}
function dsho_summary($tpl,array $m):string{
    $r=dstt_range_label($tpl,$m["p"]["range"]);
    $txt=dsnt_h($tpl,$m["n_all"]==1?"dsho_sum1":"dsho_sum",array(dsho_count_txt($m,$m["n_all"]),$r,strval($m["n_local"]),strval($m["n_inet"])));
    $sub=dsnt_h($tpl,"dsho_sum_names",array(strval($m["named"]),dsho_count_txt($m,$m["n_all"])));
    if($m["more"]){$sub.=" · ".dsnt_h($tpl,"dsho_sum_capped",array(strval(dsho_cap())));}
    $pl=$m["n_all"]>0?round(100*$m["n_local"]/$m["n_all"],1):0;
    $bar="<div class='dsho-dist'><span>{dsho_dist}</span><div class='dsho-dtrack' role='img' aria-label=\"".dsnt_h($tpl,"dsho_dist_alt",array(strval($m["n_local"]),strval($m["n_inet"])))."\"><i style='width:$pl%'></i></div>"
        ."<span class='dsho-lg'><i class='l'></i>".dsnt_h($tpl,"dsho_lg_local",array(strval($m["n_local"])))."</span><span class='dsho-lg'><i class='n'></i>".dsnt_h($tpl,"dsho_lg_inet",array(strval($m["n_inet"])))."</span></div>";
    return "<div class='dsnt-card dsho-sum'><h3>$txt</h3><p>$sub</p>$bar</div>";
}
function dsho_names($tpl,$dns):string{
    if(!is_object($dns)){return "<div class='dsnt-card dsho-names'><h4><i class='fas fa-signature' aria-hidden='true'></i>{dsho_names_title}</h4><p>{dsho_names_help}</p></div>";}
    if(empty($dns->enabled)){
        return "<div class='dsnt-card dsho-names'><h4><i class='fas fa-signature' aria-hidden='true'></i>{dsho_names_title}<span class='dsho-badge off'>{dsho_off}</span></h4><p>{dsho_names_off}</p></div>";
    }
    $ok=intval($dns->resolved_ok??0);$tried=intval($dns->cache_size??0);
    $ql=intval($dns->queue_length??0);$qc=intval($dns->queue_capacity??0);$fl=intval($dns->in_flight??0);
    $w=$qc>0?min(100,round(100*$ql/$qc,1)):0;
    $found=dsnt_h($tpl,"dsho_names_found",array(number_format($ok),number_format($tried)));
    $wait=dsnt_h($tpl,"dsho_names_wait",array(number_format($ql),number_format($fl)));
    $cap=$qc>0?dsnt_h($tpl,"dsho_names_queue",array(number_format($ql),number_format($qc))):"";
    return "<div class='dsnt-card dsho-names'><h4><i class='fas fa-signature' aria-hidden='true'></i>{dsho_names_title}<span class='dsho-badge'>{dsho_on}</span></h4><p>{dsho_names_help}</p>"
        ."<div class='dsho-nk'><span><i class='fas fa-check' aria-hidden='true'></i>$found</span></div>"
        ."<div class='dsho-nk'><span>$wait</span><span class='dsho-cap'>$cap</span></div>"
        ."<div class='dsho-qtrack' role='img' aria-label=\"$cap\"><i style='width:$w%'></i></div></div>";
}
function dsho_ago($tpl,int $ts,string $tok):string{
    if($ts<=0){return "—";}
    $s=time()-$ts;
    return $s<60?dsnt_t($tpl,"dstt_just_now"):dsnt_t($tpl,$tok,array(dsnt_ago($tpl,$s)));
}
function dsho_row($tpl,array $m,array $r):string{
    $ip=htmlspecialchars($r["ip"]);
    $pid="dsho-p-".md5($r["ip"]);
    $cp=dsnt_h($tpl,"dstt_copy");
    $copy="<button type='button' class='dsho-copy' aria-label=\"$cp\" title=\"$cp\" data-ip='$ip'><i class='fas fa-copy'></i></button>";
    if($r["name"]!==""){
        $nm=htmlspecialchars($r["name"]);
        $ident="<div class='dsho-name' title='$nm'>$nm</div><div class='dsho-ip'><span class='dsho-mono'>$ip</span>$copy</div>";
    }else{
        $ident="<div class='dsho-name dsho-mono'>$ip $copy</div><div class='dsho-noname'>{dstt_no_name}</div>";
    }
    $w=$m["max"]>0?round(100*$r["total"]/$m["max"],1):0;
    $pi=$r["total"]>0?round(100*$r["in"]/$r["total"],1):0;
    $alt=dsnt_h($tpl,"dsho_bar_alt",array(dsnt_bytes($r["in"]),dsnt_bytes($r["out"])));
    $bar="<div class='dstt-track' role='img' aria-label=\"$alt\"><div class='dstt-fill' style='width:$w%'><i class='in' style='width:$pi%'></i><i class='out' style='width:".round(100-$pi,1)."%'></i></div></div>"
        ."<div class='dsho-io'>↓ ".dsnt_bytes($r["in"])." · ↑ ".dsnt_bytes($r["out"])."</div>";
    $pk=dsnt_h($tpl,"dspr_packets",array(dsnt_count($r["pin"]+$r["pout"])));
    $lastT=$r["last"]>0?" title='".date("Y-m-d H:i:s",$r["last"])."'":"";
    $firstT=$r["first"]>0?" title='".date("Y-m-d H:i:s",$r["first"])."'":"";
    $last="<span class='dsho-last'$lastT>".htmlspecialchars(dsho_ago($tpl,$r["last"],"dstt_ago"))."</span>"
        .($r["first"]>0?"<span class='dsho-first'$firstT>".dsnt_h($tpl,"dsho_first",array(dsnt_ago($tpl,max(0,time()-$r["first"]))))."</span>":"");
    $lbl=dsnt_h($tpl,"dsho_details");
    $h=array("<div class='dsho-item' data-ip='$ip' data-name=\"".htmlspecialchars(mb_strtolower($r["name"]!==""?$r["name"]:$r["ip"],"UTF-8"))."\" data-where='".($r["local"]?0:1)."' data-in='{$r["in"]}' data-total='{$r["total"]}' data-last='{$r["last"]}' data-rank='{$r["rank"]}' ".dsho_details_attrs($tpl,$r).">");
    $h[]="<div class='dsho-row' role='button' tabindex='0' aria-expanded='false' aria-controls='$pid' aria-label=\"$lbl\">";
    $h[]="<span class='dsho-chev' aria-hidden='true'><i class='fas fa-chevron-right'></i></span><div class='dsho-ident'>$ident</div>";
    $h[]="<div>".dstt_loc_badge($r["local"])."</div><div class='dsho-barc'>$bar</div>";
    $h[]="<div class='dsho-num'><b>".dsnt_bytes($r["total"])."</b><span>$pk</span></div><div class='dsho-seen'>$last</div></div>";
    $h[]="</div>";
    return implode("",$h);
}
// Valeurs du détail portées par la ligne ; le panneau est construit au premier dépliage
// (500 lignes à détail pré-rendu = 1,7 Mo de fragment rechargé toutes les 60 s).
function dsho_details_attrs($tpl,array $r):string{
    $d=function($ts){return $ts>0?date("Y-m-d H:i:s",$ts):"—";};
    $pk=$r["pin"]+$r["pout"];
    $v=array("df"=>$d($r["first"]),"dl"=>$d($r["last"]),
        "di"=>dsnt_t($tpl,"dsho_d_in",array(dsnt_bytes($r["in"]),dsnt_count($r["pin"]))),
        "do"=>dsnt_t($tpl,"dsho_d_in",array(dsnt_bytes($r["out"]),dsnt_count($r["pout"]))),
        "da"=>$pk>0?dsnt_bytes($r["total"]/$pk):"—","dt"=>$r["rank"]<10?"1":"0");
    $a=array();
    foreach($v as $k=>$x){$a[]="data-$k=\"".htmlspecialchars($x,ENT_QUOTES)."\"";}
    return implode(" ",$a);
}
function dsho_panel_tpl():string{
    return "<template id='dsho-ptpl'><div class='dsho-panel'><dl>"
        ."<div><dt>{dsho_d_first}</dt><dd data-f='df'></dd></div><div><dt>{dsho_d_last}</dt><dd data-f='dl'></dd></div>"
        ."<div><dt>{dstt_received}</dt><dd data-f='di'></dd></div><div><dt>{dstt_sent}</dt><dd data-f='do'></dd></div>"
        ."<div><dt>{dsho_d_avg}</dt><dd data-f='da'></dd></div></dl>"
        ."<a class='dsnt-link' data-f='dt' href='javascript:blur();' OnClick=\"DshoTab(1);\">{dsho_d_talkers} <i class='fas fa-arrow-right' aria-hidden='true'></i></a></div></template>";
}
function dsho_th(string $key,string $label,string $cls=""):string{
    $s=$key=="total"?"descending":"none";
    return "<button type='button' class='dsho-th $cls' data-s='$key' aria-sort='$s' OnClick=\"DshoSort('$key');\">$label <i class='fas fa-sort' aria-hidden='true'></i></button>";
}
function dsho_list($tpl,array $m):string{
    $h=array("<div class='dsnt-card dsho-card'><div class='dsho-ch'><div><h3>{dsho_list_title}</h3><p>{dsho_list_help}</p></div>"
        ."<div class='dstt-legend dsho-legend'><span><i class='in'></i>{dstt_received}</span><span><i class='out'></i>{dstt_sent}</span></div></div>");
    $h[]="<div class='dsho-head'><span></span>".dsho_th("name","{dsho_c_device}").dsho_th("where","{dsho_c_where}").dsho_th("in","{dsho_c_io}").dsho_th("total","{dsho_c_total}").dsho_th("last","{dsho_c_last}")."</div>";
    $h[]="<div class='dsho-rows' id='dsho-rows'>";
    foreach($m["rows"] as $r){$h[]=dsho_row($tpl,$m,$r);}
    $h[]="</div>".dsho_footer($tpl,$m).dsho_panel_tpl()."</div>";
    return implode("",$h);
}
function dsho_footer($tpl,array $m):string{
    $shown=count($m["rows"]);
    $txt=dsnt_h($tpl,"dsho_showing",array(strval($shown),dsho_count_txt($m,$m["matched"])));
    $btn="";
    foreach(dsho_sizes() as $s){
        if($s>$m["p"]["limit"] && $m["matched"]>$shown){
            $btn="<button type='button' class='dsho-more' OnClick=\"DshoMax('$s');\">".dsnt_h($tpl,"dsho_show_n",array(strval($s)))."</button>";
            break;
        }
    }
    return "<div class='dsho-foot'><span>$txt $btn</span><span><i class='fas fa-circle-info' aria-hidden='true'></i>".dsnt_h($tpl,"dsho_cap_note",array(strval(dsho_cap())))."</span></div>";
}
function dsho_state_nomatch($tpl,string $q):string{
    return dstt_state_card("fa-magnifying-glass","",dsnt_h($tpl,"dsho_nomatch_title",array($q)),"{dsho_nomatch_text}",
        "<br><a class='dsnt-btn' href='javascript:blur();' OnClick=\"DshoClear();\"><i class='fas fa-xmark'></i>{dsho_clear}</a>");
}
function dsho_state_nowhere():string{
    return dstt_state_card("fa-filter","","{dsho_nowhere_title}","{dsho_nowhere_text}",
        "<br><a class='dsnt-btn' href='javascript:blur();' OnClick=\"DshoWhere('all');\"><i class='fas fa-list'></i>{dsho_show_all}</a>");
}
function dsho_state_empty($tpl,string $range):string{
    $next=$range=="day"?"month":"day";
    $lbl=$next=="day"?"{dstt_show_24h}":"{dstt_show_30d}";
    return dstt_state_card("fa-hourglass-half","",dsnt_h($tpl,"dspr_empty_title",array(dstt_range_label($tpl,$range))),"{dstt_empty_text}",
        "<br><a class='dsnt-btn' href='javascript:blur();' OnClick=\"DshoRange('$next');\"><i class='fas fa-clock-rotate-left'></i>$lbl</a>");
}
function dsho_state_stopped():string{
    return dstt_state_card("fa-circle-pause","warn","{dstt_stopped_title}","{dsho_stopped_text}",
        "<br><a class='dsnt-btn' href='javascript:blur();' OnClick=\"DshoTab(6);\"><i class='fas fa-heart-pulse'></i>{dstt_open_status}</a>");
}
function dsho_style():string{
    return "<style>
.dsho-tools{padding:14px 16px;margin-bottom:16px}
.dsho-trow{display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.dsho-trow2{display:flex;align-items:center;gap:12px;margin-top:10px;flex-wrap:wrap;font-size:13px;color:#6b7280}
.dsho-trow2 label{margin:0;font-weight:600;color:#4b5563}
.dsho-trow2 select{height:32px;border:1px solid #d1d5db;border-radius:8px;padding:0 8px;font-size:13px;color:#1f2937;background:#fff}
.dsho-search{position:relative;flex:0 1 360px;min-width:240px}
.dsho-search>svg,.dsho-search>i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6b7280;font-size:13px}
.dsho-search input{width:100%;height:38px;border:1px solid #d1d5db;border-radius:8px;padding:0 36px;font-size:14px;color:#1f2937}
.dsho-search input:focus{outline:2px solid #005447;outline-offset:1px;border-color:#005447}
.dsho-search input::-webkit-search-cancel-button{display:none}
.dsho-clr{position:absolute;right:6px;top:50%;transform:translateY(-50%);border:0;background:transparent;color:#6b7280;padding:4px 8px;border-radius:6px;cursor:pointer}
.dsho-clr:hover{background:#e6eeed;color:#005447}
.dsho-seg a{display:inline-flex;align-items:center;gap:6px}
.dsho-n{display:inline-block;min-width:22px;padding:0 6px;border-radius:999px;background:#f3f4f6;color:#374151;font-size:13px;font-weight:600;text-align:center}
.dsho-n:empty{display:none}
.dsho-seg a.on .dsho-n{background:rgba(255,255,255,.2);color:#fff}
.dsho-ex{display:inline-flex;align-items:center;gap:6px;margin-left:auto}
.dsho-ex button{border:1px solid #e5e7eb;background:#f9fafb;border-radius:6px;padding:2px 8px;font-family:monospace;font-size:13px;color:#1f2937;cursor:pointer}
.dsho-ex button:hover{background:#e6eeed;border-color:#bfd4d1;color:#005447}
.dsho-ex i{font-style:normal;color:#9ca3af}
.dsho-top{display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1fr);gap:16px;margin-bottom:16px}
.dsho-sum{padding:18px 22px}
.dsho-sum h3{margin:0;font-size:18px;font-weight:600;color:#111827;line-height:1.4}
.dsho-sum p{margin:4px 0 0 0;font-size:13px;color:#6b7280}
.dsho-dist{display:flex;align-items:center;gap:14px;margin-top:14px;padding-top:14px;border-top:1px solid #f1f5f9;font-size:13px;color:#4b5563;flex-wrap:wrap}
.dsho-dtrack{flex:1 1 200px;height:10px;border-radius:5px;background:#80aaa3;overflow:hidden;min-width:120px}
.dsho-dtrack i{display:block;height:100%;background:#005447}
.dsho-lg{display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
.dsho-lg i{width:10px;height:10px;border-radius:2px;display:inline-block}
.dsho-lg i.l{background:#005447}.dsho-lg i.n{background:#80aaa3}
.dsho-names{padding:18px 20px}
.dsho-names h4{display:flex;align-items:center;gap:8px;margin:0;font-size:16px;font-weight:600;color:#1f2937}
.dsho-names h4>svg,.dsho-names h4>i{color:#005447}
.dsho-badge{margin-left:auto;padding:2px 10px;border-radius:999px;background:#e6eeed;color:#005447;font-size:13px;font-weight:600}
.dsho-badge.off{background:#f3f4f6;color:#4b5563}
.dsho-names p{margin:8px 0 12px 0;font-size:13px;color:#4b5563;line-height:1.5}
.dsho-nk{display:flex;justify-content:space-between;gap:10px;font-size:13px;color:#1f2937;margin-top:6px}
.dsho-nk svg,.dsho-nk i{color:#005447;margin-right:6px}
.dsho-cap{color:#6b7280;white-space:nowrap}
.dsho-qtrack{height:6px;border-radius:3px;background:#f3f4f6;overflow:hidden;margin-top:8px}
.dsho-qtrack i{display:block;height:100%;background:#005447;min-width:0}
.dsho-card{overflow:hidden;margin-bottom:16px}
.dsho-ch{display:flex;justify-content:space-between;gap:24px;padding:18px 22px 12px 22px}
.dsho-ch h3{margin:0;font-size:16px;font-weight:600;color:#1f2937}
.dsho-ch p{margin:4px 0 0 0;font-size:13px;color:#6b7280;max-width:640px}
.dsho-legend{background:transparent;border:0;padding:0;align-self:flex-start}
.dsho-legend span{display:inline-flex;align-items:center}
.dsho-head,.dsho-row{display:grid;grid-template-columns:20px minmax(0,1.5fr) 170px minmax(160px,240px) 130px 170px;gap:16px;align-items:center;padding:0 22px}
.dsho-head{background:#f9fafb;border-top:1px solid #f1f5f9;border-bottom:1px solid #f1f5f9;min-height:40px}
.dsho-th{border:0;background:transparent;text-align:left;font-size:13px;font-weight:600;color:#4b5563;padding:8px 0;cursor:pointer;white-space:nowrap}
.dsho-th svg,.dsho-th i{color:#9ca3af;margin-left:4px}
.dsho-th[aria-sort='ascending'],.dsho-th[aria-sort='descending']{color:#005447}
.dsho-th[aria-sort='ascending'] svg,.dsho-th[aria-sort='descending'] svg{color:#005447}
.dsho-th:focus-visible,.dsho-row:focus-visible,.dsho-copy:focus-visible,.dsho-more:focus-visible,.dsho-ex button:focus-visible,.dsho-clr:focus-visible,.dsho-trow2 select:focus-visible{outline:2px solid #005447;outline-offset:2px}
.dsho-item{border-bottom:1px solid #f1f5f9}
.dsho-item:last-child{border-bottom:0}
.dsho-row{min-height:64px;padding-top:10px;padding-bottom:10px;cursor:pointer}
.dsho-row:hover{background:#fafbfb}
.dsho-chev{color:#9ca3af;transition:transform .15s}
.dsho-row[aria-expanded='true'] .dsho-chev{transform:rotate(90deg)}
.dsho-ident{min-width:0}
.dsho-name{font-size:14px;font-weight:600;color:#1f2937;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dsho-ip{display:flex;align-items:center;gap:4px;margin-top:2px}
.dsho-mono{font-family:monospace;font-size:13px;color:#6b7280}
.dsho-name.dsho-mono{color:#1f2937;font-size:14px}
.dsho-noname{font-size:13px;color:#6b7280;font-style:italic;margin-top:2px}
.dsho-copy{border:0;background:transparent;color:#6b7280;padding:2px 6px;border-radius:4px;cursor:pointer;font-size:13px}
.dsho-copy:hover{background:#e6eeed;color:#005447}
.dsho-copy.ok{color:#005447;font-family:inherit}
.dsho-io{font-size:13px;color:#4b5563;margin-top:6px;white-space:nowrap}
.dsho-num b{display:block;font-size:14px;color:#1f2937}
.dsho-num span{font-size:13px;color:#6b7280;white-space:nowrap}
.dsho-last{display:block;font-size:14px;color:#1f2937;text-decoration:underline dotted #9ca3af}
.dsho-first{display:block;font-size:13px;color:#6b7280;margin-top:2px}
.dsho-panel{background:#f9fafb;padding:14px 22px 16px 58px;border-top:1px solid #f1f5f9}
.dsho-panel[hidden]{display:none}
.dsho-panel dl{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin:0 0 10px 0}
.dsho-panel dt{font-size:13px;font-weight:600;color:#4b5563}
.dsho-panel dd{margin:2px 0 0 0;font-size:13px;color:#1f2937;font-variant-numeric:tabular-nums}
.dsho-foot{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:12px 22px;border-top:1px solid #f1f5f9;background:#f9fafb;font-size:13px;color:#4b5563;flex-wrap:wrap}
.dsho-foot svg,.dsho-foot i{margin-right:6px}
.dsho-more{margin-left:10px;border:1px solid #bfd4d1;background:#fff;color:#005447;border-radius:8px;padding:5px 12px;font-size:13px;font-weight:600;cursor:pointer}
.dsho-more:hover{background:#e6eeed}
@media (max-width:1200px){.dsho-top{grid-template-columns:minmax(0,1fr)}.dsho-head,.dsho-row{grid-template-columns:20px minmax(0,1.3fr) 150px 170px 110px 120px}.dsho-first{display:none}.dsho-panel dl{grid-template-columns:repeat(3,minmax(0,1fr))}.dsho-ex{margin-left:0}}
@media (prefers-reduced-motion:no-preference){.dsho-panel{animation:dsntIn .15s ease-out}.dsho-dtrack i,.dsho-qtrack i{animation:dsttGrow .25s ease-out}}
</style>";
}
function dsho_js(array $p):string{
    $page=CurrentPageName();
    $J=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $tpl=new template_admin();
    $cfg=json_encode(array("page"=>$page,"copied"=>dsnt_t($tpl,"dstt_copied")),$J);
    $st=json_encode(array("range"=>$p["range"],"limit"=>$p["limit"],"where"=>$p["where"],"q"=>$p["q"],"sort"=>"total","dir"=>-1),$J);
    return "<script>NoSpinner();(function(){var C=$cfg;
window.DshoS=$st;window.DshoOpen=window.DshoOpen||{};
function url(){var S=window.DshoS;return C.page+'?hosts-table=yes&range='+encodeURIComponent(S.range)+'&limit='+S.limit+'&where='+S.where+'&q='+encodeURIComponent(S.q);}
window.DshoReload=function(silent){if(silent){LoadAjaxSilent('dsho-host',url());}else{LoadAjax('dsho-host',url());}};
function seg(id,k){var a=document.querySelectorAll('#'+id+' a');for(var i=0;i<a.length;i++){var on=a[i].getAttribute('data-k')===k;a[i].className=on?'on':'';a[i].setAttribute('aria-checked',on?'true':'false');}}
window.DshoRange=function(r){window.DshoS.range=r;seg('dsho-range',r);DshoReload(false);};
window.DshoWhere=function(w){window.DshoS.where=w;seg('dsho-where',w);DshoReload(false);};
window.DshoMax=function(n){window.DshoS.limit=parseInt(n,10)||50;var s=document.getElementById('dsho-max');if(s){s.value=String(window.DshoS.limit);}DshoReload(false);};
window.DshoClear=function(){var q=document.getElementById('dsho-q');if(q){q.value='';q.focus();}window.DshoS.q='';DshoReload(false);};
window.DshoExample=function(v){var q=document.getElementById('dsho-q');if(q){q.value=v;}window.DshoS.q=v;DshoReload(false);};
window.DshoTab=function(n){var h=document.getElementById('dsho-host');var c=h?h.closest('.tabs-container'):null;var a=c?c.querySelectorAll('ul.nav-tabs>li>a'):[];if(a.length>n){a[n].click();}};
window.DshoCounts=function(c){var s=document.querySelectorAll('#dsho-where .dsho-n');for(var i=0;i<s.length;i++){var k=s[i].getAttribute('data-c');s[i].textContent=(c&&c[k]!==undefined)?c[k]:'';}};
function build(row){var id=row.getAttribute('aria-controls');var p=document.getElementById(id);if(p){return p;}var t=document.getElementById('dsho-ptpl');var it=row.parentNode;if(!t||!t.content){return null;}
var n=t.content.firstElementChild.cloneNode(true);n.id=id;n.setAttribute('hidden','');var f=n.querySelectorAll('[data-f]');
for(var i=0;i<f.length;i++){var k=f[i].getAttribute('data-f');if(k==='dt'){if(it.getAttribute('data-dt')!=='1'){f[i].parentNode.removeChild(f[i]);}continue;}f[i].textContent=it.getAttribute('data-'+k)||'';}
it.appendChild(n);return n;}
function setOpen(row,open){var p=open?build(row):document.getElementById(row.getAttribute('aria-controls'));row.setAttribute('aria-expanded',open?'true':'false');if(p){if(open){p.removeAttribute('hidden');}else{p.setAttribute('hidden','');}}}
window.DshoToggle=function(row){var ip=row.parentNode.getAttribute('data-ip');var open=row.getAttribute('aria-expanded')!=='true';setOpen(row,open);if(open){window.DshoOpen[ip]=1;}else{delete window.DshoOpen[ip];}};
window.DshoCopy=function(ev,b){ev.stopPropagation();var ip=b.getAttribute('data-ip');var done=function(){var o=b.innerHTML;b.className='dsho-copy ok';b.textContent=C.copied;setTimeout(function(){b.className='dsho-copy';b.innerHTML=o;},1500);};
function fb(){var t=document.createElement('textarea');t.value=ip;t.style.position='fixed';t.style.opacity='0';document.body.appendChild(t);t.select();try{document.execCommand('copy');done();}catch(e){}document.body.removeChild(t);}
if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(ip).then(done,fb);}else{fb();}};
window.DshoApplySort=function(){var S=window.DshoS;var box=document.getElementById('dsho-rows');if(!box){return;}
var it=Array.prototype.slice.call(box.children);
it.sort(function(a,b){var k=S.sort,x,y;if(k==='name'){x=a.getAttribute('data-name');y=b.getAttribute('data-name');return x<y?-S.dir:(x>y?S.dir:0);}
var m={where:'data-where',in:'data-in',total:'data-total',last:'data-last'}[k];x=parseFloat(a.getAttribute(m))||0;y=parseFloat(b.getAttribute(m))||0;
if(x===y){return (parseInt(a.getAttribute('data-rank'),10)-parseInt(b.getAttribute('data-rank'),10));}return x<y?-S.dir:S.dir;});
for(var i=0;i<it.length;i++){box.appendChild(it[i]);}
var th=document.querySelectorAll('#dsho-host .dsho-th');for(var j=0;j<th.length;j++){var on=th[j].getAttribute('data-s')===S.sort;th[j].setAttribute('aria-sort',on?(S.dir>0?'ascending':'descending'):'none');}};
window.DshoSort=function(k){var S=window.DshoS;if(S.sort===k){S.dir=-S.dir;}else{S.sort=k;S.dir=(k==='name'||k==='where')?1:-1;}DshoApplySort();};
window.DshoAfter=function(){DshoApplySort();var r=document.querySelectorAll('#dsho-host .dsho-item');for(var i=0;i<r.length;i++){if(window.DshoOpen[r[i].getAttribute('data-ip')]){setOpen(r[i].querySelector('.dsho-row'),true);}}};
var host=document.getElementById('dsho-host');
if(host&&!host.getAttribute('data-b')){host.setAttribute('data-b','1');
host.addEventListener('click',function(e){var c=e.target.closest?e.target.closest('.dsho-copy'):null;if(c){DshoCopy(e,c);return;}var r=e.target.closest?e.target.closest('.dsho-row'):null;if(r&&host.contains(r)){DshoToggle(r);}});
host.addEventListener('keydown',function(e){if(e.key!=='Enter'&&e.key!==' '){return;}var t=e.target;if(t&&t.classList&&t.classList.contains('dsho-row')){DshoToggle(t);e.preventDefault();}});}
var q=document.getElementById('dsho-q');
if(q&&!q.getAttribute('data-b')){q.setAttribute('data-b','1');q.addEventListener('input',function(){var v=q.value;window.DshoTyped=Date.now();clearTimeout(window.DshoDeb);window.DshoDeb=setTimeout(function(){window.DshoS.q=v;DshoReload(true);},500);});}
if(window.DshoTimer){clearInterval(window.DshoTimer);}
window.DshoTimer=setInterval(function(){var h=document.getElementById('dsho-host');
if(!h||!document.body.contains(h)){clearInterval(window.DshoTimer);window.DshoTimer=null;return;}
if(h.offsetParent===null){return;}
if(window.DshoTyped&&Date.now()-window.DshoTyped<5000){return;}
if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible,.swal2-shown')){return;}
DshoReload(true);},60000);
DshoReload(false);
})();</script>";
}
// ---- DSHO widgets end
// Onglet : bandeau et barre d'outils fixes, liste rechargée dans #dsho-host (?hosts-table=yes).
function tab_hosts(){
    $tpl=new template_admin();
    $p=dsho_params();
    $css=dsnt_style().dstt_style().dspr_style().dsho_style();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableDarkStats"))==1){
        echo $css.$tpl->_ENGINE_parse_body("<div class='dsnt dsho'>".dstt_state_off()."</div>")."<script>NoSpinner();</script>";
        return;
    }
    $health=dsnt_json("/darkstats/health");
    $dns=is_object($health) && !empty($health->running)?dsnt_json("/darkstats/dns/status"):null;
    $h=array("<div class='dsnt dsho'>",dsho_banner($tpl,$health,$dns),dsho_toolbar($tpl,$p),"<div id='dsho-host'></div></div>");
    echo $css.$tpl->_ENGINE_parse_body(implode("",$h)).dsho_js($p);
}
function hosts_table(){
    $tpl=new template_admin();
    $p=dsho_params();
    $m=dsho_model($p);
    $h=array();
    $counts="null";
    if($m["state"]=="off"){$h[]=dstt_state_off();}
    elseif($m["state"]=="unreachable"){$h[]=dstt_state_unreachable();}
    elseif($m["state"]=="stopped"){$h[]=dsho_state_stopped();}
    elseif($m["state"]=="empty"){$h[]=dsho_state_empty($tpl,$p["range"]);}
    else{
        $counts=json_encode(array("all"=>dsho_count_txt($m,$m["n_all"]),"local"=>strval($m["n_local"]),"inet"=>strval($m["n_inet"])));
        $h[]="<div class='dsho-top'>".dsho_summary($tpl,$m).dsho_names($tpl,$m["dns"])."</div>";
        if($m["state"]=="nomatch"){$h[]=dsho_state_nomatch($tpl,$p["q"]);}
        elseif($m["state"]=="nowhere"){$h[]=dsho_state_nowhere();}
        else{$h[]=dsho_list($tpl,$m);}
    }
    echo $tpl->_ENGINE_parse_body(implode("",$h))."<script>NoSpinner();if(window.DshoCounts){DshoCounts($counts);}if(window.DshoAfter){DshoAfter();}</script>";
}

// ─── Ports (maquette stitch_artica_ports_statistics) ───
// ---- DSPS model begin
// /darkstats/ports ne compte que le port de DESTINATION : la réponse d'un téléchargement
// revient vers un port temporaire du poste. On le dit à l'écran (famille « reply »).
function dsps_family(int $port):string{
    $svc=dstt_services();
    if(isset($svc[$port])){return "known";}
    if($port>=DSPS_EPHEMERAL){return "reply";}
    return "other";
}
function dsps_model(string $range,int $limit):array{
    $m=array("range"=>$range,"limit"=>$limit,"state"=>"ok","health"=>null,"rows"=>array(),"total"=>0,"max"=>0,
        "tcp"=>0,"udp"=>0,"other"=>0,"reply_bytes"=>0,"reply_n"=>0);
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableDarkStats"))==1){$m["state"]="off";return $m;}
    $m["health"]=dsnt_json("/darkstats/health");
    if($m["health"]===null){$m["state"]="unreachable";return $m;}
    if(empty($m["health"]->running)){$m["state"]="stopped";return $m;}
    $ports=dsnt_json("/darkstats/ports?range=$range&limit=$limit");
    if(!is_array($ports)){$m["state"]="unreachable";return $m;}
    foreach($ports as $p){
        $port=intval($p->port??0);
        $proto=strtolower((string)($p->protocol??""));
        if(!in_array($proto,array("tcp","udp"))){$proto="other";}
        $b=floatval($p->bytes??0);
        $fam=dsps_family($port);
        $m["rows"][]=array("port"=>$port,"proto"=>$proto,"bytes"=>$b,"packets"=>floatval($p->packets??0),"fam"=>$fam);
        $m["total"]+=$b;
        $m[$proto]+=$b;
        if($b>$m["max"]){$m["max"]=$b;}
        if($fam=="reply"){$m["reply_bytes"]+=$b;$m["reply_n"]++;}
    }
    if(count($m["rows"])==0){$m["state"]="empty";}
    return $m;
}
// ---- DSPS model end
// ---- DSPS widgets begin
function dsps_pct(float $part,float $total):string{
    if($total<=0 || $part<=0){return "0";}
    $v=100*$part/$total;
    return $v>=10?strval(intval(round($v))):sprintf('%.1f',$v);
}
function dsps_service_name($tpl,array $r):array{
    $svc=dstt_services();
    if($r["fam"]=="known"){
        $s=$svc[$r["port"]];
        return array($s[0]." (".dsnt_t($tpl,$s[1]).")",dsnt_t($tpl,"dsps_sub_known"));
    }
    if($r["fam"]=="reply"){return array(dsnt_t($tpl,"dsps_reply_port",array(strval($r["port"]))),dsnt_t($tpl,"dsps_sub_reply"));}
    return array(dsnt_t($tpl,"dsps_port_n",array(strval($r["port"]))),dsnt_t($tpl,"dsps_sub_other"));
}
function dsps_period_bar(string $range):string{
    $h=array("<div class='dstt-bar'><div class='dstt-bar-t'><h3>{dsps_title}</h3><span>{dsps_title_sub}</span></div>");
    $h[]="<div class='dstt-bar-p'><span>{dsnt_period}</span><div class='dsnt-seg' role='group'>";
    foreach(dsnt_ranges() as $k=>$label){
        $on=$k===$range?" class='on' aria-current='true'":"";
        $h[]="<a href='javascript:blur();'$on OnClick=\"DspsRange('$k');\">$label</a>";
    }
    $h[]="</div></div></div>";
    return implode("",$h);
}
function dsps_banner($tpl,array $m):string{
    $h=array("<div class='dsnt-card dsnt-banner'><div class='dsnt-bico' aria-hidden='true'><i class='fas fa-door-open'></i></div>");
    $h[]="<div class='dsnt-btxt'><h2>{dsps_banner_title}</h2><p>{dsps_banner_text}</p><div class='dsnt-chips'>";
    $h[]="<span class='dsnt-pill'><i class='fas fa-eye-slash'></i>{dsps_chip_content}</span>";
    $h[]="<span class='dsnt-pill'><i class='fas fa-tags'></i>{dsps_chip_named}</span>";
    $hl=$m["health"];
    if(is_object($hl)){
        if(!empty($hl->running)){
            $ts=strtotime((string)($hl->last_flush??""));
            $ago=$ts?dsnt_ago($tpl,time()-$ts):"";
            $txt=$ago!==""?dsnt_h($tpl,"dsnt_chip_collecting_ago",array($ago)):"{dsnt_chip_collecting}";
            $title=$ts?" title='".date("Y-m-d H:i:s",$ts)."'":"";
            $h[]="<span class='dsnt-pill'$title><i class='fas fa-circle-check'></i>$txt</span>";
        }else{
            $h[]="<span class='dsnt-pill warn'><i class='fas fa-circle-pause'></i>{dsnt_chip_stopped}</span>";
        }
    }
    $h[]="</div></div></div>";
    return implode("",$h);
}
function dsps_tile_busiest($tpl,array $m):string{
    $r=$m["rows"][0];
    list($name,)=dsps_service_name($tpl,$r);
    $cap=dsnt_t($tpl,"dsps_t_busiest_cap",array("\x01".dsnt_bytes($r["bytes"])."\x02",dstt_range_label($tpl,$m["range"]),"\x01".dsps_pct($r["bytes"],$m["total"])." %\x02"));
    $cap=str_replace(array("\x01","\x02"),array("<b>","</b>"),htmlspecialchars($cap));
    $n=htmlspecialchars($name);
    return "<div class='dsnt-card dstt-tile'><div class='dstt-tile-h'><span>{dsps_t_busiest}</span><i class='fas fa-crown' aria-hidden='true'></i></div>"
        ."<div class='dstt-tile-v' title='$n'>$n</div><div class='dstt-tile-ip'>".strtoupper($r["proto"])." {$r["port"]}</div>"
        ."<p class='dstt-tile-c'><span>$cap</span></p></div>";
}
function dsps_tile_mix($tpl,array $m):string{
    $t=$m["tcp"]+$m["udp"]+$m["other"];
    $pt=$t>0?round(100*$m["tcp"]/$t,1):0;$pu=$t>0?round(100*$m["udp"]/$t,1):0;
    $line="<span><i class='in'></i><b>TCP ".dsnt_bytes($m["tcp"])."</b> (".dsps_pct($m["tcp"],$t)." %)</span><span><i class='out'></i><b>UDP ".dsnt_bytes($m["udp"])."</b> (".dsps_pct($m["udp"],$t)." %)</span>";
    if($m["other"]>0){$line.="<span><i class='oth'></i><b>{dsps_other} ".dsnt_bytes($m["other"])."</b></span>";}
    return "<div class='dsnt-card dstt-tile'><div class='dstt-tile-h'><span>{dsps_t_mix}</span><i class='fas fa-scale-balanced' aria-hidden='true'></i></div>"
        ."<div class='dsps-mix-l'>$line</div>"
        ."<div class='dstt-track dsps-mix-b' role='img' aria-label='TCP $pt % · UDP $pu %'><div class='dstt-fill' style='width:100%'><i class='in' style='width:$pt%'></i><i class='out' style='width:$pu%'></i></div></div>"
        ."<p class='dstt-tile-c'><span>{dsps_t_mix_cap}</span></p></div>";
}
function dsps_tile_reply($tpl,array $m):string{
    $v=$m["reply_n"]>0?dsnt_h($tpl,$m["reply_n"]==1?"dsps_t_reply_val1":"dsps_t_reply_val",array(dsnt_bytes($m["reply_bytes"]),strval($m["reply_n"]))):"—";
    return "<div class='dsnt-card dstt-tile'><div class='dstt-tile-h'><span>{dsps_t_reply}</span><i class='fas fa-arrow-turn-down' aria-hidden='true'></i></div>"
        ."<div class='dstt-tile-v'>$v</div><div class='dstt-tile-ip'>".dsnt_h($tpl,"dsps_t_reply_range",array(strval(DSPS_EPHEMERAL),"65535"))."</div>"
        ."<p class='dstt-tile-c'><span>{dsps_t_reply_cap}</span></p></div>";
}
function dsps_tiles($tpl,array $m):string{
    return "<div class='dstt-tiles'>".dsps_tile_busiest($tpl,$m).dsps_tile_mix($tpl,$m).dsps_tile_reply($tpl,$m)."</div>";
}
function dsps_toolbar($tpl):string{
    $ph=dsnt_h($tpl,"dsps_search_ph");
    $h=array("<div class='dsps-tools'><div class='dsnt-seg dsps-proto' role='group' aria-label='TCP / UDP'>");
    foreach(array("all"=>"{dsps_all}","tcp"=>"TCP","udp"=>"UDP") as $k=>$l){
        $on=$k=="all"?" class='on' aria-current='true'":"";
        $h[]="<a href='javascript:blur();'$on data-p='$k' OnClick=\"DspsProto('$k');\">$l</a>";
    }
    $h[]="</div><label class='dsps-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i><input type='search' id='dsps-q' aria-label=\"$ph\" placeholder=\"$ph\" oninput=\"DspsApply();\"></label>";
    $h[]="<div class='dsps-sw'><div><b id='dsps-hide-l'>{dsps_hide_reply}</b><span>{dsps_hide_reply_help}</span></div>";
    $h[]="<button type='button' class='dsps-switch' id='dsps-hide' role='switch' aria-checked='false' aria-labelledby='dsps-hide-l' OnClick=\"DspsHide(this);\"><i></i></button></div></div>";
    return implode("",$h);
}
function dsps_row($tpl,array $m,int $i):string{
    $r=$m["rows"][$i];
    list($name,$sub)=dsps_service_name($tpl,$r);
    $rank=$i+1;
    $w=$m["max"]>0?round(100*$r["bytes"]/$m["max"],1):0;
    $pico=array("tcp"=>"fa-link","udp"=>"fa-paper-plane","other"=>"fa-circle-question");
    $plbl=$r["proto"]=="other"?dsnt_t($tpl,"dsps_other"):strtoupper($r["proto"]);
    $fam=array("known"=>array("fa-tag","{dsps_fam_known}"),"other"=>array("fa-circle-question","{dsps_fam_other}"),"reply"=>array("fa-arrow-turn-down","{dsps_fam_reply}"));
    $f=$fam[$r["fam"]];
    $search=htmlspecialchars(strtolower($r["port"]." ".$name." ".$r["proto"]));
    $pk=dsnt_h($tpl,"dsps_packets",array(dsnt_count($r["packets"])));
    return "<div class='dsps-row' data-proto='{$r["proto"]}' data-fam='{$r["fam"]}' data-s=\"$search\">"
        ."<span class='dstt-rank".($rank==1?" first":"")."'>$rank</span>"
        ."<div class='dstt-ident'><div class='dstt-name'>".htmlspecialchars($name)."</div><div class='dsps-sub'>".htmlspecialchars($sub)."</div></div>"
        ."<span class='dsps-proto-chip {$r["proto"]}'><i class='fas {$pico[$r["proto"]]}' aria-hidden='true'></i>".htmlspecialchars($plbl)." {$r["port"]}</span>"
        ."<span class='dsps-fam {$r["fam"]}'><i class='fas {$f[0]}' aria-hidden='true'></i>{$f[1]}</span>"
        ."<div class='dstt-track'><div class='dstt-fill' style='width:$w%'><i class='".($r["proto"]=="udp"?"out":"in")."' style='width:100%'></i></div></div>"
        ."<div class='dstt-num'><b>".dsnt_bytes($r["bytes"])."</b><span>".dsps_pct($r["bytes"],$m["total"])." % · $pk</span></div>"
        ."</div>";
}
function dsps_list($tpl,array $m):string{
    $n=count($m["rows"]);
    $page=CurrentPageName();
    $h=array("<div class='dsnt-card dstt-rank-card'><div class='dstt-rh'><div><h3>".dsnt_h($tpl,"dsps_rank_title",array(strval($n)))."</h3><p>{dsps_rank_help}</p></div>");
    $h[]="<p class='dsps-total'>".str_replace(array("\x01","\x02"),array("<b>","</b>"),htmlspecialchars(dsnt_t($tpl,"dsps_total",array("\x01".dsnt_bytes($m["total"])."\x02"))))."</p></div>";
    $h[]=dsps_toolbar($tpl);
    $h[]="<div class='dsps-head' aria-hidden='true'><span>#</span><span>{dsps_col_service}</span><span>{dsps_col_port}</span><span>{dsps_col_family}</span><span>{dsps_col_share}</span><span>{dsps_col_volume}</span></div>";
    $h[]="<div class='dsps-rows' id='dsps-rows'>";
    for($i=0;$i<$n;$i++){$h[]=dsps_row($tpl,$m,$i);}
    $h[]="</div><div class='dsnt-empty dsps-nomatch' id='dsps-nomatch' hidden><i class='fas fa-filter-circle-xmark'></i><b>{dsps_nomatch_title}</b>{dsps_nomatch_text}<br>"
        ."<a class='dsnt-btn' href='javascript:blur();' OnClick=\"DspsClear();\"><i class='fas fa-eraser'></i>{dsps_clear}</a></div>";
    $more="";
    if($m["limit"]<DSPS_LIMIT_MAX && $n>=$m["limit"]){
        $more="<a class='dsnt-link' href='javascript:blur();' OnClick=\"DspsMore();\">".dsnt_h($tpl,"dsps_more",array(strval(DSPS_LIMIT_MAX)))."</a>";
    }
    $h[]="<div class='dstt-foot'><span>".dsnt_h($tpl,$n==1?"dsps_showing1":"dsps_showing",array(strval($n)))."</span>$more</div></div>";
    return implode("",$h);
}
function dsps_read():string{
    return "<div class='dsnt-card dsnt-read'>"
        ."<div><i class='fas fa-globe' aria-hidden='true'></i><span>{dsps_read_web}</span></div>"
        ."<div><i class='fas fa-arrow-turn-down' aria-hidden='true'></i><span>{dsps_read_reply}</span></div>"
        ."<div><i class='fas fa-magnifying-glass' aria-hidden='true'></i><span>{dsps_read_who} <a class='dsnt-link' href='javascript:blur();' OnClick=\"DspsTab(1);\">{top_talkers}</a></span></div>"
        ."</div>";
}
function dsps_state($m):string{
    $page=CurrentPageName();
    if($m["state"]=="off"){
        return dstt_state_card("fa-chart-area","","{dsnt_off_title}","{dsnt_off_text}",
            "<br><a class='dsnt-btn' href='javascript:blur();' OnClick=\"Loadjs('$page?install-js=yes');\"><i class='fas fa-power-off'></i>{install_feature}</a>");
    }
    if($m["state"]=="stopped"){
        return dstt_state_card("fa-circle-pause","warn","{dstt_stopped_title}","{dsps_stopped_text}",
            "<br><a class='dsnt-btn' href='javascript:blur();' OnClick=\"DspsTab(6);\"><i class='fas fa-heart-pulse'></i>{dstt_open_status}</a>");
    }
    if($m["state"]=="empty"){
        $next=$m["range"]=="day"?"month":"day";
        $lbl=$next=="day"?"{dstt_show_24h}":"{dstt_show_30d}";
        return dstt_state_card("fa-hourglass-half","","{dstt_empty_title}","{dstt_empty_text}",
            "<br><a class='dsnt-btn' href='javascript:blur();' OnClick=\"DspsRange('$next');\"><i class='fas fa-clock-rotate-left'></i>$lbl</a>");
    }
    return dstt_state_card("fa-plug-circle-xmark","err","{dstt_err_title}","{dstt_err_text}");
}
function dsps_style():string{
    return "<style>
.dsps-mix-l{display:flex;flex-wrap:wrap;gap:6px 16px;margin-top:12px;font-size:13px;color:#6b7280}
.dsps-mix-l b{color:#1f2937;font-weight:600}
.dsps-mix-l i{display:inline-block;width:10px;height:10px;border-radius:2px;margin-right:6px}
.dsps-mix-l i.in{background:#005447}.dsps-mix-l i.out{background:#80aaa3}.dsps-mix-l i.oth{background:#9ca3af}
.dsps-mix-b{margin-top:10px}
.dsps-total{margin:0;font-size:13px;color:#6b7280;white-space:nowrap}
.dsps-total b{color:#1f2937}
.dsps-tools{display:flex;align-items:center;gap:16px;padding:12px 22px;background:#f9fafb;border-bottom:1px solid #f1f5f9;flex-wrap:wrap}
.dsps-proto a{padding:5px 12px}
.dsps-search{position:relative;margin:0;font-weight:400}
.dsps-search i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:13px}
.dsps-search input{width:260px;height:34px;padding:0 10px 0 32px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;background:#fff;color:#1f2937}
.dsps-search input:focus{outline:2px solid #005447;outline-offset:1px;border-color:#bfd4d1}
.dsps-sw{margin-left:auto;display:flex;align-items:center;gap:12px;text-align:right}
.dsps-sw b{display:block;font-size:13px;color:#1f2937;font-weight:600}
.dsps-sw span{font-size:12px;color:#6b7280}
.dsps-switch{position:relative;width:40px;height:22px;border-radius:11px;border:0;background:#d1d5db;cursor:pointer;flex:0 0 40px;padding:0}
.dsps-switch i{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;transition:left .15s}
.dsps-switch[aria-checked='true']{background:#005447}
.dsps-switch[aria-checked='true'] i{left:21px}
.dsps-switch:focus-visible,.dsps-proto a:focus-visible{outline:2px solid #005447;outline-offset:2px}
.dsps-head,.dsps-row{display:grid;grid-template-columns:28px minmax(0,1.6fr) 120px 190px minmax(140px,300px) 160px;gap:16px;align-items:center;padding:0 22px}
.dsps-head{padding-top:10px;padding-bottom:10px;border-bottom:1px solid #f1f5f9;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#6b7280}
.dsps-head span:last-child{text-align:right}
.dsps-row{min-height:56px;padding-top:8px;padding-bottom:8px;border-bottom:1px solid #f1f5f9}
.dsps-row:last-child{border-bottom:0}
.dsps-row[hidden]{display:none}
.dsps-sub{font-size:12px;color:#6b7280;margin-top:2px}
.dsps-proto-chip{display:inline-flex;align-items:center;gap:6px;padding:3px 9px;border-radius:6px;font-family:monospace;font-size:13px;font-weight:600;white-space:nowrap;justify-self:start}
.dsps-proto-chip.tcp{background:#e6eeed;color:#005447}
.dsps-proto-chip.udp{background:#f3f4f6;color:#374151}
.dsps-proto-chip.other{background:#f3f4f6;color:#6b7280}
.dsps-proto-chip i{font-size:11px}
.dsps-fam{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:13px;white-space:nowrap;justify-self:start;background:#f3f4f6;color:#374151}
.dsps-fam.known{background:#e6eeed;color:#005447;font-weight:600}
.dsps-fam i{font-size:11px}
.dsps-nomatch[hidden]{display:none}
@media (max-width:1200px){.dsps-head,.dsps-row{grid-template-columns:28px minmax(0,1.4fr) 110px 170px 220px 140px}}
</style>";
}
function dsps_js(string $range,int $limit):string{
    $page=CurrentPageName();
    $J=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $cfg=json_encode(array("page"=>$page,"range"=>$range,"limit"=>$limit,"max"=>DSPS_LIMIT_MAX),$J);
    return "<script>NoSpinner();(function(){var C=$cfg;
var F=window.DspsF=window.DspsF||{p:'all',q:'',hide:false};
window.DspsCur=window.DspsCur||{range:C.range,limit:C.limit};window.DspsCur.range=C.range;window.DspsCur.limit=C.limit;
function url(){return C.page+'?ports-list=yes&range='+encodeURIComponent(window.DspsCur.range)+'&limit='+window.DspsCur.limit;}
window.DspsRange=function(r){window.DspsCur.range=r;LoadAjax('ds-ports',url());};
window.DspsMore=function(){window.DspsCur.limit=C.max;LoadAjax('ds-ports',url());};
window.DspsTab=function(n){var h=document.getElementById('ds-ports');var c=h?h.closest('.tabs-container'):null;var a=c?c.querySelectorAll('ul.nav-tabs>li>a'):[];if(a.length>n){a[n].click();}};
window.DspsApply=function(){var q=document.getElementById('dsps-q');if(q){F.q=q.value.toLowerCase().trim();}
var rows=document.querySelectorAll('#dsps-rows .dsps-row'),shown=0;
for(var i=0;i<rows.length;i++){var r=rows[i];var ok=(F.p==='all'||r.getAttribute('data-proto')===F.p)&&(!F.hide||r.getAttribute('data-fam')!=='reply')&&(F.q===''||r.getAttribute('data-s').indexOf(F.q)>=0);
if(ok){r.removeAttribute('hidden');shown++;}else{r.setAttribute('hidden','');}}
var nm=document.getElementById('dsps-nomatch');if(nm){if(shown===0&&rows.length>0){nm.removeAttribute('hidden');}else{nm.setAttribute('hidden','');}}};
window.DspsProto=function(p){F.p=p;var a=document.querySelectorAll('.dsps-proto a');for(var i=0;i<a.length;i++){var on=a[i].getAttribute('data-p')===p;a[i].className=on?'on':'';if(on){a[i].setAttribute('aria-current','true');}else{a[i].removeAttribute('aria-current');}}DspsApply();};
window.DspsHide=function(b){F.hide=b.getAttribute('aria-checked')!=='true';b.setAttribute('aria-checked',F.hide?'true':'false');DspsApply();};
window.DspsClear=function(){F.q='';F.hide=false;var q=document.getElementById('dsps-q');if(q){q.value='';}var b=document.getElementById('dsps-hide');if(b){b.setAttribute('aria-checked','false');}DspsProto('all');};
var q=document.getElementById('dsps-q');if(q){q.value=F.q;}var b=document.getElementById('dsps-hide');if(b){b.setAttribute('aria-checked',F.hide?'true':'false');}
if(document.getElementById('dsps-rows')){DspsProto(F.p);}
if(window.DspsTimer){clearInterval(window.DspsTimer);}
window.DspsTimer=setInterval(function(){var h=document.getElementById('ds-ports');
if(!h||!document.body.contains(h)){clearInterval(window.DspsTimer);window.DspsTimer=null;return;}
if(h.offsetParent===null){return;}
if(document.activeElement&&document.activeElement.id==='dsps-q'){return;}
if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible,.swal2-shown')){return;}
LoadAjaxSilent('ds-ports',url());},60000);
})();</script>";
}
// ---- DSPS widgets end
// Hôte de l'onglet, rechargé à chaque changement de période ou de taille (?ports-list=yes&range=&limit=).
function ports_list(){
    $tpl=new template_admin();
    $range=$_GET["range"]??"day";
    if(!isset(dsnt_ranges()[$range])){$range="day";}
    $limit=intval($_GET["limit"]??DSPS_LIMIT);
    if($limit!=DSPS_LIMIT_MAX){$limit=DSPS_LIMIT;}
    $m=dsps_model($range,$limit);
    $h=array("<div class='dsnt'>");
    if($m["state"]!="off"){$h[]=dsps_period_bar($range);$h[]=dsps_banner($tpl,$m);}
    if($m["state"]=="ok"){
        $h[]=dsps_tiles($tpl,$m);
        $h[]=dsps_list($tpl,$m);
        $h[]=dsps_read();
    }else{
        $h[]=dsps_state($m);
    }
    $h[]="</div>";
    echo dsnt_style().dstt_style().dsps_style().$tpl->_ENGINE_parse_body(implode("",$h)).dsps_js($range,$limit);
}

// ─── Status (maquette stitch_artica_traffic_status) ───
// ---- DSST model begin
// Un modèle pour tout l'onglet : santé, configuration, résolveur de noms, métriques internes.
// Le plafond de la base (1 GiB) est la constante MaxDBBytes du démon (DarkStats/sizecap.go).
function dsst_model():array{
    $m=array("state"=>"ok","health"=>null,"cfg"=>null,"dns"=>null,"met"=>null,"issues"=>array(),
        "lost_pct"=>null,"save_age"=>null,"flush"=>15,"sample"=>1);
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableDarkStats"))==1){$m["state"]="off";return $m;}
    $m["health"]=dsnt_json("/darkstats/health");
    if($m["health"]===null){$m["state"]="unreachable";return $m;}
    $m["cfg"]=dsnt_json("/darkstats/config");
    if(empty($m["health"]->running)){$m["state"]="stopped";return $m;}
    $m["dns"]=dsnt_json("/darkstats/dns/status");
    $m["met"]=dsnt_json("/darkstats/internal/metrics");
    $h=$m["health"];
    $tot=floatval($h->packets_total??0);$drop=floatval($h->packets_dropped??0);
    if($tot+$drop>0){$m["lost_pct"]=100*$drop/($tot+$drop);}
    if(is_object($m["cfg"])){
        $m["flush"]=max(1,intval($m["cfg"]->flush_interval_sec??15));
        $m["sample"]=max(1,intval($m["cfg"]->capture->sample_rate??1));
    }
    $ts=dstt_ts((string)($h->last_flush??""));
    if($ts>0){$m["save_age"]=max(0,time()-$ts);}
    if($m["lost_pct"]!==null && $m["lost_pct"]>=DSST_LOST_WARN){$m["issues"][]="lost";}
    if($m["save_age"]!==null && $m["save_age"]>max(DSST_SAVE_STALE,4*$m["flush"])){$m["issues"][]="stale";}
    if(is_object($m["met"]) && intval($m["met"]->aggregator->flush_errors??0)>0){$m["issues"][]="saveerr";}
    if($m["sample"]>1){$m["issues"][]="sample";}
    if(count($m["issues"])>0){$m["state"]="warn";}
    return $m;
}
// ---- DSST model end
// ---- DSST widgets begin
function dsst_lost_txt(float $p):string{
    if($p<=0){return "0 %";}
    if($p<0.01){return "< 0.01 %";}
    return ($p>=10?sprintf('%.0f',$p):sprintf('%.2f',$p))." %";
}
function dsst_ago($tpl,int $s):string{
    return $s<5?dsnt_t($tpl,"dstt_just_now"):dsnt_t($tpl,"dstt_ago",array(dsnt_ago($tpl,$s)));
}
function dsst_verdict($tpl,array $m):string{
    $page=CurrentPageName();
    $st=$m["state"];
    $map=array("ok"=>array("ok","fa-circle-check"),"warn"=>array("warn","fa-triangle-exclamation"),"stopped"=>array("err","fa-circle-pause"),
        "off"=>array("off","fa-power-off"),"unreachable"=>array("err","fa-plug-circle-xmark"));
    list($cls,$ico)=$map[$st];
    $h=array("<div class='dsnt-card dsst-verdict $cls'><div class='dsst-vico' aria-hidden='true'><i class='fas $ico'></i></div><div class='dsst-vtxt'>");
    $hl=$m["health"];
    $cards=is_object($hl)&&is_array($hl->interfaces??null)?$hl->interfaces:array();
    $n=count($cards);
    if($st=="ok"){
        $ago=$m["save_age"]!==null?dsnt_ago($tpl,$m["save_age"]):"";
        $txt=$ago!==""?dsnt_t($tpl,$n==1?"dsst_ok_text1":"dsst_ok_text",array(strval($n),$ago)):dsnt_t($tpl,"dsst_ok_text_nots");
        $h[]="<h2>{dsst_ok_title}</h2><p>".htmlspecialchars($txt)."</p>";
    }elseif($st=="warn"){
        $first=$m["issues"][0];
        $titles=array("lost"=>"{dsst_w_lost_title}","stale"=>"{dsst_w_stale_title}","saveerr"=>"{dsst_w_saveerr_title}","sample"=>"{dsst_w_sample_title}");
        $h[]="<h2>{$titles[$first]}</h2>";
        foreach($m["issues"] as $is){
            if($is=="lost"){$t=dsnt_t($tpl,"dsst_w_lost",array(dsst_lost_txt($m["lost_pct"])));}
            elseif($is=="stale"){$t=dsnt_t($tpl,"dsst_w_stale",array(dsnt_ago($tpl,$m["save_age"])));}
            elseif($is=="saveerr"){$t=dsnt_t($tpl,"dsst_w_saveerr",array(number_format(intval($m["met"]->aggregator->flush_errors))));}
            else{$t=dsnt_t($tpl,"dsst_w_sample",array(strval($m["sample"])));}
            $h[]="<p>".htmlspecialchars($t)."</p>";
        }
    }elseif($st=="stopped"){
        $h[]="<h2>{dstt_stopped_title}</h2><p>{dsst_stopped_text}</p>";
    }elseif($st=="off"){
        $h[]="<h2>{dsnt_off_title}</h2><p>{dsnt_off_text}</p>"
            ."<a class='dsnt-btn' href='javascript:blur();' OnClick=\"Loadjs('$page?install-js=yes');\"><i class='fas fa-power-off'></i>{install_feature}</a>";
    }else{
        $h[]="<h2>{dstt_err_title}</h2><p>{dstt_err_text}</p>";
    }
    if($st=="ok"||$st=="warn"){
        $h[]="<div class='dsnt-chips'>";
        if($n>0){
            $h[]="<span class='dsnt-pill'><i class='fas fa-ethernet'></i>".dsnt_h($tpl,"dsst_chip_watch",array(implode(", ",$cards)))."</span>";
        }
        $h[]="<span class='dsnt-pill'><i class='fas fa-eye-slash'></i>{dsnt_chip_content}</span>";
        $c=$m["cfg"];
        if(is_object($c) && isset($c->minute_retention_days)){
            $h[]="<span class='dsnt-pill'><i class='fas fa-database'></i>".dsnt_h($tpl,"dsst_chip_hist",array(
                strval(intval($c->minute_retention_days)),strval(intval($c->hour_retention_days)),strval(intval($c->day_retention_days))))."</span>";
        }
        $h[]="</div>";
    }
    $h[]="</div></div>";
    return implode("",$h);
}
function dsst_tile(string $title,string $ico,string $value,string $sub,string $cap,string $cls=""):string{
    return "<div class='dsnt-card dstt-tile dsst-tile'><div class='dstt-tile-h'><span>$title</span><i class='fas $ico' aria-hidden='true'></i></div>"
        ."<div class='dsst-tv $cls'>$value</div>".($sub!==""?"<div class='dsst-tsub'>$sub</div>":"")."<p class='dsst-cap'>$cap</p></div>";
}
function dsst_tile_counted($tpl,array $m):string{
    return dsst_tile("{dsst_t_counted}","fa-box",dsnt_count($m["health"]->packets_total??0),"","{dsst_t_counted_cap}");
}
function dsst_tile_lost($tpl,array $m):string{
    if($m["lost_pct"]===null){return dsst_tile("{dsst_t_lost}","fa-circle-exclamation","—","","{dsst_t_lost_cap}","none");}
    $warn=$m["lost_pct"]>=DSST_LOST_WARN?"warn":"";
    $sub=dsnt_h($tpl,"dsps_packets",array(number_format(floatval($m["health"]->packets_dropped??0))));
    return dsst_tile("{dsst_t_lost}","fa-circle-exclamation",dsst_lost_txt($m["lost_pct"]),$sub,"{dsst_t_lost_cap}",$warn);
}
function dsst_tile_save($tpl,array $m):string{
    $cap=dsnt_h($tpl,"dsst_t_save_cap",array(strval($m["flush"])));
    if($m["save_age"]===null){return dsst_tile("{dsst_t_save}","fa-floppy-disk","—","",$cap,"none");}
    $ts=time()-$m["save_age"];
    $warn=in_array("stale",$m["issues"])?"warn":"";
    return dsst_tile("{dsst_t_save}","fa-floppy-disk","<span title='".date("Y-m-d H:i:s",$ts)."'>".htmlspecialchars(dsst_ago($tpl,$m["save_age"]))."</span>",
        date("H:i:s",$ts),$cap,$warn);
}
function dsst_donut(float $used,float $max):string{
    $p=$max>0?min(1,max(0,$used/$max)):0;
    $r=15;$c=2*M_PI*$r;$d=round($c*$p,2);
    return "<svg width='40' height='40' viewBox='0 0 40 40' aria-hidden='true'><circle cx='20' cy='20' r='$r' fill='none' stroke='#e6eeed' stroke-width='6'/>"
        ."<circle cx='20' cy='20' r='$r' fill='none' stroke='".($p>=0.9?"#b45309":"#005447")."' stroke-width='6' stroke-dasharray='$d ".round($c,2)."' transform='rotate(-90 20 20)'/></svg>";
}
function dsst_tile_db($tpl,array $m):string{
    $size=floatval($m["health"]->real_file_size??0);
    $cap=preg_replace('/\.0 /',' ',dsnt_bytes(DSST_DB_MAX));
    $lbl=dsnt_h($tpl,"dsst_t_db_of",array(dsnt_bytes($size),$cap));
    $v="<span class='dsst-db' role='img' aria-label=\"$lbl\">".dsst_donut($size,DSST_DB_MAX)."<span>".dsnt_bytes($size)."</span></span>";
    return dsst_tile("{dsst_t_db}","fa-database",$v,dsnt_h($tpl,"dsst_t_db_sub",array($cap)),"{dsst_t_db_cap}");
}
function dsst_tiles($tpl,array $m):string{
    return "<div class='dsst-tiles'>".dsst_tile_counted($tpl,$m).dsst_tile_lost($tpl,$m).dsst_tile_save($tpl,$m).dsst_tile_db($tpl,$m)."</div>";
}
function dsst_onoff(bool $on):string{
    return $on?"<span class='dsst-st on'><i class='fas fa-circle-check'></i>{dsst_on}</span>":"<span class='dsst-st'><i class='fas fa-circle-minus'></i>{dsst_off}</span>";
}
function dsst_kept($tpl,array $m):string{
    $c=$m["cfg"];
    $h=array("<div class='dsnt-card dsst-card'><h3>{dsst_kept_title}</h3><p class='dsst-help'>{dsst_kept_help}</p>");
    if(!is_object($c)){$h[]="<p class='dsst-help'>{dsst_cfg_none}</p></div>";return implode("",$h);}
    $ret=array(array("{dsst_r_min}",intval($c->minute_retention_days??0)),array("{dsst_r_hour}",intval($c->hour_retention_days??0)),array("{dsst_r_day}",intval($c->day_retention_days??0)));
    $mx=max(1,max($ret[0][1],$ret[1][1],$ret[2][1]));
    $h[]="<div class='dsst-ret'>";
    foreach($ret as $r){
        $w=round(100*$r[1]/$mx,1);
        $d=dsnt_h($tpl,$r[1]==1?"dsst_days1":"dsst_days",array(strval($r[1])));
        $h[]="<div class='dsst-bar-row'><div class='dsst-bar-l'><span>{$r[0]}</span><b>$d</b></div><div class='dstt-track'><div class='dstt-fill' style='width:$w%'><i class='in' style='width:100%'></i></div></div></div>";
    }
    $h[]="</div><ul class='dsst-facts'>";
    $cap=$c->capture??null;
    $pat=is_object($cap)&&is_array($cap->excluded_patterns??null)?$cap->excluded_patterns:array();
    // Motifs regex du démon (^docker.*) rendus en jokers lisibles (docker*).
    $glob=array();
    foreach($pat as $pp){$glob[]=str_replace(array(".*","\\."),array("*","."),trim((string)$pp,"^$"));}
    $patTxt=count($glob)?htmlspecialchars(implode(", ",$glob)):"—";
    $h[]="<li><i class='fas fa-filter' aria-hidden='true'></i><div><b>{dsst_ignored}</b> <span class='dsst-mono'>$patTxt</span><span>{dsst_ignored_help}</span></div></li>";
    $h[]="<li><i class='fas fa-layer-group' aria-hidden='true'></i><div><b>{dsst_stacked}</b> ".dsst_onoff(is_object($cap)&&!empty($cap->exclude_stacked))."<span>{dsst_stacked_help}</span></div></li>";
    $smp=$m["sample"]>1?dsnt_h($tpl,"dsst_sampling_n",array(strval($m["sample"]))):"{dsst_sampling_all}";
    $h[]="<li><i class='fas fa-gauge' aria-hidden='true'></i><div><b>{dsst_sampling}</b> $smp<span>{dsst_sampling_help}</span></div></li>";
    $h[]="<li><i class='fas fa-satellite-dish' aria-hidden='true'></i><div><b>{dsst_listen}</b> ".dsst_onoff(is_object($cap)&&!empty($cap->promiscuous))."<span>{dsst_listen_help}</span></div></li>";
    $h[]="</ul></div>";
    return implode("",$h);
}
function dsst_names($tpl,array $m):string{
    $d=$m["dns"];
    $on=is_object($d)&&!empty($d->enabled);
    $h=array("<div class='dsnt-card dsst-card'><div class='dsst-ch'><h3>{dsst_names_title}</h3>".dsst_onoff($on)."</div><p class='dsst-help'>{dsst_names_help}</p>");
    if(!is_object($d)){$h[]="<p class='dsst-help'>{dsst_names_none}</p></div>";return implode("",$h);}
    $cache=intval($d->cache_size??0);$ok=intval($d->resolved_ok??0);
    $ql=intval($d->queue_length??0);$qc=intval($d->queue_capacity??0);
    $rows=array(array("{dsst_names_found}",dsnt_h($tpl,"dsst_of_addr",array(number_format($ok),number_format($cache))),$cache>0?100*$ok/$cache:0),
        array("{dsst_names_wait}",dsnt_h($tpl,"dsst_of_cap",array(number_format($ql),number_format($qc))),$qc>0?100*$ql/$qc:0));
    foreach($rows as $r){
        $w=round($r[2],1);
        $busy=($r[0]=="{dsst_names_wait}" && $w>=80)?" dsst-busy":"";
        $h[]="<div class='dsst-bar-row$busy'><div class='dsst-bar-l'><span>{$r[0]}</span><b>{$r[1]}</b></div><div class='dstt-track'><div class='dstt-fill' style='width:$w%'><i class='in' style='width:100%'></i></div></div></div>";
    }
    $h[]="<div class='dsst-now'><span><i class='fas fa-circle' aria-hidden='true'></i>{dsst_names_now}</span><b>".number_format(intval($d->in_flight??0))."</b></div>";
    $h[]="<p class='dsst-cap dsst-foot'>{dsst_names_foot}</p></div>";
    return implode("",$h);
}
function dsst_cards($tpl,array $m):string{
    return "<div class='dsst-two'>".dsst_kept($tpl,$m).dsst_names($tpl,$m)."</div>";
}
function dsst_details($tpl,array $m):string{
    $met=$m["met"];$h=$m["health"];$c=$m["cfg"];
    $kv=array();
    $num=function($v){return $v===null?"—":number_format(floatval($v));};
    $ag=is_object($met)&&is_object($met->aggregator??null)?$met->aggregator:null;
    $kv[]=array("{dsst_d_decode}",$num(is_object($met)?($met->decode_errors_total??null):null));
    $kv[]=array("{dsst_d_restarts}",$num(is_object($met)?($met->capture_restarts_total??null):null));
    $kv[]=array("{dsst_d_saveerr}",$num($ag?($ag->flush_errors??null):null));
    $kv[]=array("{dsst_d_saves}",$num($ag?($ag->flush_count??null):null));
    $kv[]=array("{dsst_d_events}",$num($ag?($ag->events_processed??null):null));
    $kv[]=array("{dsst_d_slots}",$num($ag?($ag->slots_dropped??null):null));
    $lc=dstt_ts((string)($h->last_compact??""));
    $kv[]=array("{dsst_d_compact}",$lc>0?"<span title='".date("Y-m-d H:i:s",$lc)."'>".htmlspecialchars(dsst_ago($tpl,max(0,time()-$lc)))."</span>":"—");
    $kv[]=array("{dsst_d_file}","<span class='dsst-mono'>".htmlspecialchars((string)($h->db_path??"—"))."</span>");
    if(is_object($c) && is_object($c->limits??null)){
        $l=$c->limits;
        $kv[]=array("{dsst_d_limits}",dsnt_h($tpl,"dsst_d_limits_val",array(number_format(intval($l->top_hosts_per_minute??0)),number_format(intval($l->top_ports_per_minute??0)),number_format(intval($l->top_conversations_per_minute??0)))));
    }
    $o=array("<div class='dsnt-card dsst-details'><button type='button' class='dsst-dh' aria-expanded='false' aria-controls='dsst-dbody' OnClick=\"DsstDetails(this);\">"
        ."<span><i class='fas fa-terminal' aria-hidden='true'></i>{dsst_details}</span><i class='fas fa-chevron-down dsst-chev' aria-hidden='true'></i></button>"
        ."<dl class='dsst-dl' id='dsst-dbody' hidden>");
    foreach($kv as $r){$o[]="<div><dt>{$r[0]}</dt><dd>{$r[1]}</dd></div>";}
    $o[]="</dl></div>";
    return implode("",$o);
}
function dsst_manage($tpl,array $m):string{
    $page=CurrentPageName();
    $h=array("<div class='dsnt-card dsst-card dsst-manage'><h3>{dsst_manage_title}</h3><p class='dsst-help warn'>{dsst_manage_help}</p>");
    if($m["state"]=="off"){
        $h[]="<div class='dsst-act'><span class='dsst-aico'><i class='fas fa-power-off'></i></span><div><b>{install_feature}</b><span>{dsst_install_help}</span></div>"
            ."<a class='dsnt-btn' href='javascript:blur();' OnClick=\"Loadjs('$page?install-js=yes');\">{install_feature}</a></div></div>";
        return implode("",$h);
    }
    $h[]="<div class='dsst-act'><span class='dsst-aico'><i class='fas fa-broom'></i></span><div><b>{empty_database}</b><span>{dsst_empty_help}</span></div>"
        ."<a class='dsst-obtn' href='javascript:blur();' OnClick=\"Loadjs('$page?remove-database-js=yes');\">{empty_database}</a></div>";
    $h[]="<div class='dsst-act'><span class='dsst-aico red'><i class='fas fa-trash-can'></i></span><div><b>{uninstall_feature}</b><span>{dsst_uninstall_help}</span></div>"
        ."<a class='dsst-obtn red' href='javascript:blur();' OnClick=\"Loadjs('$page?uninstall-js=yes');\">{uninstall_feature}</a></div>";
    $h[]="</div>";
    return implode("",$h);
}
function dsst_style():string{
    return "<style>
.dsst-verdict{display:flex;gap:20px;align-items:flex-start;padding:24px;border-left:4px solid #005447;margin-bottom:16px}
.dsst-verdict.warn{border-left-color:#d97706}.dsst-verdict.err{border-left-color:#b91c1c}.dsst-verdict.off{border-left-color:#9ca3af}
.dsst-vico{flex:0 0 48px;height:48px;border-radius:50%;background:#e6eeed;color:#005447;display:flex;align-items:center;justify-content:center;font-size:22px}
.dsst-verdict.warn .dsst-vico{background:#fffbeb;color:#b45309}.dsst-verdict.err .dsst-vico{background:#fef2f2;color:#b91c1c}.dsst-verdict.off .dsst-vico{background:#f3f4f6;color:#4b5563}
.dsst-vtxt{flex:1 1 auto;min-width:0}
.dsst-vtxt h2{margin:0 0 6px 0;font-size:20px;font-weight:600;color:#1f2937}
.dsst-vtxt p{margin:0 0 4px 0;font-size:14px;color:#4b5563;line-height:1.55}
.dsst-vtxt .dsnt-btn{margin-top:12px}
.dsst-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:16px}
.dsst-tv{margin-top:12px;font-size:26px;font-weight:600;color:#1f2937;line-height:1.2;font-variant-numeric:tabular-nums;white-space:nowrap}
.dsst-tv.warn{color:#b45309}.dsst-tv.none{color:#9ca3af}
.dsst-tsub{font-size:13px;color:#6b7280;margin-top:4px;font-variant-numeric:tabular-nums}
.dsst-cap{margin:auto 0 0 0;padding-top:14px;font-size:13px;color:#6b7280;line-height:1.45}
.dsst-db{display:inline-flex;align-items:center;gap:10px}
.dsst-two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px;align-items:start}
.dsst-busy .dstt-fill i.in{background:#d97706}.dsst-busy .dsst-bar-l b{color:#b45309}
.dsst-card{padding:20px 24px;display:flex;flex-direction:column;min-width:0}
.dsst-card h3{margin:0;font-size:16px;font-weight:600;color:#1f2937}
.dsst-ch{display:flex;justify-content:space-between;align-items:center;gap:12px}
.dsst-help{margin:4px 0 14px 0;font-size:13px;color:#6b7280;line-height:1.5}
.dsst-help.warn{color:#92400e}
.dsst-bar-row{margin-bottom:12px}
.dsst-bar-l{display:flex;justify-content:space-between;gap:12px;font-size:13px;color:#374151;margin-bottom:5px}
.dsst-bar-l b{font-weight:600;color:#1f2937;white-space:nowrap;font-variant-numeric:tabular-nums}
.dsst-facts{list-style:none;margin:8px 0 0 0;padding:12px 0 0 0;border-top:1px solid #f1f5f9}
.dsst-facts li{display:flex;gap:12px;align-items:flex-start;padding:7px 0;font-size:13px;color:#374151}
.dsst-facts li>i{flex:0 0 18px;margin-top:3px;color:#005447;text-align:center}
.dsst-facts b{font-weight:600;color:#1f2937}
.dsst-facts span:not(.dsst-st):not(.dsst-mono){display:block;color:#6b7280;margin-top:2px}
.dsst-mono{font-family:monospace;font-size:13px;color:#1f2937;background:#f3f4f6;padding:1px 6px;border-radius:4px;word-break:break-word}
.dsst-st{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:999px;font-size:12px;font-weight:600;background:#f3f4f6;color:#4b5563;white-space:nowrap}
.dsst-st.on{background:#e6eeed;color:#005447}
.dsst-now{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb;font-size:13px;color:#374151;margin-top:4px}
.dsst-now i{font-size:8px;color:#005447;margin-right:8px;vertical-align:2px}
.dsst-now b{font-size:18px;color:#005447;font-variant-numeric:tabular-nums}
.dsst-foot{border-top:1px solid #f1f5f9;margin-top:14px}
.dsst-details{margin-bottom:16px;overflow:hidden}
.dsst-dh{display:flex;width:100%;justify-content:space-between;align-items:center;padding:16px 24px;border:0;background:#fff;font-size:15px;font-weight:600;color:#1f2937;cursor:pointer;text-align:left}
.dsst-dh i.fa-terminal{color:#005447;margin-right:10px}
.dsst-dh:focus-visible,.dsst-obtn:focus-visible{outline:2px solid #005447;outline-offset:2px}
.dsst-chev{color:#6b7280;transition:transform .15s}
.dsst-dh[aria-expanded='true'] .dsst-chev{transform:rotate(180deg)}
.dsst-dl{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 32px;margin:0;padding:4px 24px 18px 24px;border-top:1px solid #f1f5f9}
.dsst-dl[hidden]{display:none}
.dsst-dl div{display:flex;justify-content:space-between;gap:16px;padding:8px 0;border-bottom:1px solid #f8fafc;font-size:13px}
.dsst-dl dt{font-weight:400;color:#6b7280}
.dsst-dl dd{margin:0;color:#1f2937;font-weight:600;text-align:right;font-variant-numeric:tabular-nums;min-width:0}
.dsst-manage{margin-bottom:16px}
.dsst-act{display:flex;align-items:center;gap:16px;padding:14px 16px;border:1px solid #e5e7eb;border-radius:10px;margin-top:10px}
.dsst-act>div{flex:1 1 auto;min-width:0}
.dsst-act b{display:block;font-size:14px;color:#1f2937}
.dsst-act>div span{font-size:13px;color:#6b7280}
.dsst-aico{flex:0 0 40px;height:40px;border-radius:50%;background:#e6eeed;color:#005447;display:flex;align-items:center;justify-content:center}
.dsst-aico.red{background:#fef2f2;color:#b91c1c}
.dsst-act .dsnt-btn{margin-top:0;white-space:nowrap}
.dsst-obtn{flex:0 0 auto;padding:8px 16px;border-radius:8px;border:1px solid #005447;color:#005447;font-size:14px;font-weight:600;text-decoration:none;white-space:nowrap;background:#fff}
.dsst-obtn:hover{background:#e6eeed;color:#003d33;text-decoration:none}
.dsst-obtn.red{border-color:#b91c1c;color:#b91c1c}
.dsst-obtn.red:hover{background:#fef2f2;color:#991b1b}
@media (max-width:1200px){.dsst-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}.dsst-two{grid-template-columns:minmax(0,1fr)}}
@media (prefers-reduced-motion:no-preference){.dsst-tile{animation:dsntIn .2s ease-out}}
</style>";
}
function dsst_js():string{
    $page=CurrentPageName();
    return "<script>NoSpinner();(function(){
window.DsstOpen=window.DsstOpen||false;
window.DsstDetails=function(b){var o=b.getAttribute('aria-expanded')!=='true';b.setAttribute('aria-expanded',o?'true':'false');var d=document.getElementById('dsst-dbody');if(d){if(o){d.removeAttribute('hidden');}else{d.setAttribute('hidden','');}}window.DsstOpen=o;};
if(window.DsstOpen){var b=document.querySelector('#ds-status .dsst-dh');if(b){DsstDetails(b);}}
if(window.DsstTimer){clearInterval(window.DsstTimer);}
window.DsstTimer=setInterval(function(){var h=document.getElementById('ds-status');
if(!h||!document.body.contains(h)){clearInterval(window.DsstTimer);window.DsstTimer=null;return;}
if(h.offsetParent===null){return;}
if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible,.swal2-shown')){return;}
LoadAjaxSilent('ds-status','$page?status-host=yes');},30000);
})();</script>";
}
// ---- DSST widgets end
// Hôte de l'onglet, rechargé toutes les 30 s (?status-host=yes).
function status_host(){
    $tpl=new template_admin();
    $m=dsst_model();
    $h=array("<div class='dsnt'>",dsst_verdict($tpl,$m));
    if($m["state"]=="ok"||$m["state"]=="warn"){
        $h[]=dsst_tiles($tpl,$m);
        $h[]=dsst_cards($tpl,$m);
        $h[]=dsst_details($tpl,$m);
    }
    // Désactivé : le verdict porte déjà le bouton d'installation, pas de carte en double.
    if($m["state"]!="unreachable" && $m["state"]!="off"){$h[]=dsst_manage($tpl,$m);}
    $h[]="</div>";
    echo dsnt_style().dstt_style().dsst_style().$tpl->_ENGINE_parse_body(implode("",$h)).dsst_js();
}
