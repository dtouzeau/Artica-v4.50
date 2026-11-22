<?php
// fw.nginx.DecisionIP.events.php — recent connections dropped by the DecisionIP
// reputation firewall (articarest GET /decisionip/events/{max}/{search}, which tails
// or greps /var/log/decisionip.log, one JSON object per line).
//
// Stitch redesigns: 2026-11-19 (stitch_decisionip_firewall_events_redesign) and
// 2026-11-21 (stitch_decisionip_detected_threats_console): scale strip with the exact
// counters, enabled lists, service names, shared list colours, plain details window.
// REAL DATA ONLY: the iptables LOG rule of each DecisionIP list is rate-limited
// (10/min, burst 5, DecisionIP/Fetch.go), so the log is a SAMPLE — every figure computed
// from the rows is labelled "loaded", the exact totals come from /decisionip/chart/stats.
// One model (dipev_model) feeds every widget; one widget = one function.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.modsectools.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
// awc_table_style() and friends — included at top level (never conditionally).
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["form"])){form_search();exit;}
if(isset($_GET["event-js"])){event_js();exit;}
if(isset($_GET["event-popup"])){event_popup();exit;}
page();

// ── Lists: plain-language identity of each DecisionIP prefix ─────────────
// prefix = lower-cased sub-chain name written by DecisionIP/SyslogTail.go.
// [icon, css class, label, help, colour] — colours shared with fw.nginx.DecisionIP.statistics.php.
function dipev_lists(){
    return array(
        "activeattacks"=>array("fa-bolt","dipev-t-red","{dipev_l_activeattacks}","{dipev_l_activeattacks_help}","#dc2626"),
        "anonymousrelays"=>array("fa-user-secret","dipev-t-amber","{dipev_l_anonymousrelays}","{dipev_l_anonymousrelays_help}","#d97706"),
        "anonymousrelays-novpn"=>array("fa-user-secret","dipev-t-ochre","{dipev_l_anonymousrelays_novpn}","{dipev_l_anonymousrelays_novpn_help}","#a16207"),
        "unethicalbots"=>array("fa-robot","dipev-t-slate","{dipev_l_unethicalbots}","{dipev_l_unethicalbots_help}","#64748b"),
        "honeypot"=>array("fa-bug","dipev-t-cyan","{dipev_l_honeypot}","{dipev_l_honeypot_help}","#0e7490"),
        "shadow_fortress"=>array("fa-shield-halved","dipev-t-emerald","{dipev_l_shadow_fortress}","{dipev_l_external_help}","#005447"),
        "datashield"=>array("fa-database","dipev-t-green","{dipev_l_datashield}","{dipev_l_external_help}","#15803d"),
    );
}

function dipev_list($prefix){
    $l=dipev_lists();
    if(isset($l[$prefix])){return $l[$prefix];}
    $p=($prefix=="")?"—":htmlspecialchars($prefix);
    return array("fa-shield","dipev-t-grey",$p,"","#94a3b8");
}

// Well-known ports only: a fixed, public mapping (never a guess about the actual service).
function dipev_service($port){
    $s=array(22=>"{dipev_svc_ssh}",23=>"{dipev_svc_telnet}",25=>"{dipev_svc_smtp}",53=>"{dipev_svc_dns}",
        80=>"{dipev_svc_http}",443=>"{dipev_svc_https}",445=>"{dipev_svc_smb}",3306=>"{dipev_svc_mysql}",
        3389=>"{dipev_svc_rdp}",5900=>"{dipev_svc_vnc}",8080=>"{dipev_svc_http_alt}");
    $port=intval($port);
    return isset($s[$port])?$s[$port]:"";
}

// ── Model ─────────────────────────────────────────────────────────────────

// Lists enabled in /etc/decisionip config, as DecisionIP/Fetch.go ConfiguredIpSets reads
// them. null = unknown (daemon or file unavailable): callers then show only what the log has.
function dipev_enabled_lists(){
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/config"),true);
    if(!is_array($json) || !isset($json["data"]["sections"]) || !is_array($json["data"]["sections"])){return null;}
    $S=$json["data"]["sections"];
    $known=dipev_lists();
    $on=array("honeypot"=>true);
    $vals=function($sec) use ($S){
        if(!isset($S[$sec]["list"])){return array();}
        $v=$S[$sec]["list"];
        return is_array($v)?$v:array($v);
    };
    foreach($vals("lists") as $v){ $k=trim((string)$v); if(isset($known[$k])){$on[$k]=true;} }
    if(isset($on["anonymousrelays-novpn"])){ unset($on["anonymousrelays"]); }
    foreach($vals("external_lists") as $v){ $p=explode("|",trim((string)$v)); $k=trim($p[0]); if(isset($known[$k])){$on[$k]=true;} }
    $out=array();
    foreach(array_keys($known) as $k){ if(isset($on[$k])){$out[]=$k;} }
    return $out;
}

// Exact counters kept by articarest since the protection service started.
function dipev_counters(){
    $c=array("ok"=>false,"total"=>0,"last5"=>0);
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/chart/stats"),true);
    if(!is_array($json) || empty($json["Status"]) || !isset($json["Data"]) || !is_array($json["Data"])){return $c;}
    $c["ok"]=true;
    $c["total"]=intval(isset($json["Data"]["total_blocked"])?$json["Data"]["total_blocked"]:0);
    $p=(isset($json["Data"]["prefixes"]) && is_array($json["Data"]["prefixes"]))?$json["Data"]["prefixes"]:array();
    foreach($p as $v){ $c["last5"]+=intval(isset($v["last_5min"])?$v["last_5min"]:0); }
    return $c;
}

// dipev_model — ONE events call, one config call, one counter call, one geo lookup per source.
function dipev_model($rawSearch){
    $tpl=new template_admin();
    $MAIN=$tpl->format_search_protocol($rawSearch);
    $max=intval($MAIN["MAX"]);
    $term=trim((string)$MAIN["TERM"]);
    $q=(strlen($term)<3)?"NONE":$term;
    $m=array("error"=>"","term"=>(strlen($term)<3)?"":$term,"rows"=>array(),"sources"=>array(),
        "lists"=>array(),"ports"=>array(),"first"=>0,"last"=>0,"geo"=>array(),
        "enabled"=>null,"counters"=>array("ok"=>false,"total"=>0,"last5"=>0));

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/events/$max/".rawurlencode($q));
    $json=json_decode((string)$data);
    if(!is_object($json)){
        $raw=trim(strip_tags((string)$data));
        $m["error"]=($raw!="")?substr($raw,0,300):"{dipev_daemon_unreachable}";
        return $m;
    }
    if(empty($json->Status)){
        $m["error"]=(isset($json->Error) && $json->Error!="")?(string)$json->Error:"{dipev_daemon_unreachable}";
        return $m;
    }
    $m["enabled"]=dipev_enabled_lists();
    $m["counters"]=dipev_counters();
    $logs=(isset($json->Logs) && is_array($json->Logs))?$json->Logs:array();
    foreach($logs as $line){
        $e=json_decode(trim((string)$line),true);
        if(!is_array($e) || empty($e["timestamp"])){continue;}
        $r=array(
            "ts"=>intval($e["timestamp"]),
            "prefix"=>strtolower(trim(isset($e["prefix"])?(string)$e["prefix"]:"")),
            "src"=>isset($e["src_ip"])?(string)$e["src_ip"]:"",
            "sport"=>isset($e["sport"])?intval($e["sport"]):0,
            "dst"=>isset($e["dst_ip"])?(string)$e["dst_ip"]:"",
            "dport"=>isset($e["dport"])?intval($e["dport"]):0,
            "proto"=>isset($e["proto"])?strtoupper((string)$e["proto"]):"",
            "iface"=>isset($e["in_iface"])?(string)$e["in_iface"]:"",
        );
        // What the details window needs, nothing more (raw_line/mac_raw would double the page).
        $keep=array();
        foreach(array("timestamp","prefix","src_ip","sport","dst_ip","dport","proto","in_iface","mac_src","mac_dst","ttl","len","flags") as $k){
            if(isset($e[$k])){$keep[$k]=$e[$k];}
        }
        $r["line"]=json_encode($keep);
        $m["rows"][]=$r;
        if($r["src"]!=""){$m["sources"][$r["src"]]=(isset($m["sources"][$r["src"]])?$m["sources"][$r["src"]]:0)+1;}
        $m["lists"][$r["prefix"]]=(isset($m["lists"][$r["prefix"]])?$m["lists"][$r["prefix"]]:0)+1;
        if($r["dport"]>0){$m["ports"][$r["dport"]]=(isset($m["ports"][$r["dport"]])?$m["ports"][$r["dport"]]:0)+1;}
        if($m["first"]==0 || $r["ts"]<$m["first"]){$m["first"]=$r["ts"];}
        if($r["ts"]>$m["last"]){$m["last"]=$r["ts"];}
    }
    arsort($m["lists"]);
    arsort($m["ports"]);
    $modtools=new modesctools();
    foreach(array_keys($m["sources"]) as $ip){
        $m["geo"][$ip]=dipev_geo($modtools,$ip);
    }
    return $m;
}

// dipev_geo — reads the RETURNED row only: the object's properties keep the previous
// address's values on a cache miss (the old page showed stale flags that way).
function dipev_geo($modtools,$ip){
    $g=array("country"=>"","flag"=>"","isp"=>"","hostname"=>"");
    $l=$modtools->hostinfo($ip,true);
    if(!is_array($l) || count($l)==0){return $g;}
    $g["country"]=isset($l["country_name"])?trim((string)$l["country_name"]):"";
    $g["isp"]=trim((string)(!empty($l["isp"])?$l["isp"]:(isset($l["asn_organization"])?$l["asn_organization"]:"")));
    $h=isset($l["hostname"])?trim((string)$l["hostname"]):"";
    if(strlen($h)>3 && !preg_match("#^[0-9\.:]+$#",$h)){$g["hostname"]=$h;}
    if($g["country"]!=""){
        $f=GetFlags($g["country"]);
        if($f!="" && $f!="flags/info.png"){$g["flag"]=$f;}
    }
    return $g;
}

function dipev_ago($ts){
    $tpl=new template_admin();
    $d=time()-$ts;
    if($d<0){$d=0;}
    if($d<60){return $tpl->_ENGINE_parse_body("{dipev_just_now}");}
    if($d<3600){return str_replace("%s",strval(intval($d/60)),$tpl->_ENGINE_parse_body("{dipev_ago_min}"));}
    if($d<86400){return str_replace("%s",strval(intval($d/3600)),$tpl->_ENGINE_parse_body("{dipev_ago_hours}"));}
    return str_replace("%s",strval(intval($d/86400)),$tpl->_ENGINE_parse_body("{dipev_ago_days}"));
}

// "from 18:02 to 18:41 today" or full dates on both ends.
function dipev_range($m){
    $tpl=new template_admin();
    $f=$m["first"];$l=$m["last"];
    if(date("Y-m-d",$f)==date("Y-m-d",$l) && date("Y-m-d",$l)==date("Y-m-d")){
        return str_replace(array("%from","%to"),array(date("H:i",$f),date("H:i",$l)),$tpl->_ENGINE_parse_body("{dipev_range_today}"));
    }
    return str_replace(array("%from","%to"),array(date("Y-m-d H:i",$f),date("Y-m-d H:i",$l)),$tpl->_ENGINE_parse_body("{dipev_range}"));
}

function dipev_info($token){
    return "<span class='dipev-info' tabindex='0' title=\"$token\" aria-label=\"$token\"><i class='fas fa-circle-info' aria-hidden='true'></i></span>";
}

// ── Page ──────────────────────────────────────────────────────────────────

function page(): bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("DecisionIP {firewall_events}","fas fa-eye","{dipev_header_explain}",
        "$page?form=yes","decisionip-events","progress-decifw-events",false,"table-decifw-syslog");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// form_search: banner + summary host + search card. The ?search= responder fills the
// table AND the summary (same pass as the rows) and moves the summary into place.
function form_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=array();
    $html[]="<div class='dipev' id='dipev-host'>";
    $html[]=dipev_hero(dipev_enabled_lists());
    $html[]="<div id='dipev-summary'></div>";
    $html[]=dipev_search_card($tpl,$page);
    $html[]="</div>";
    $tpl2=new template_admin();
    $ph=json_encode($tpl2->javascript_parse_text("{dipev_search_placeholder}"));
    $script="<script>
(function(){
 var host=document.getElementById('dipev-host'); if(!host){return;}
 var inp=host.querySelector('.sb-stitch input.form-control'); if(inp){inp.setAttribute('placeholder',$ph);inp.setAttribute('aria-label',$ph);}
 var grp=host.querySelector('.sb-stitch .input-group'), opts=document.getElementById('dipev-searchopts');
 if(grp && opts && !host.querySelector('.dipev-searchbox')){ var box=document.createElement('div'); box.className='dipev-card dipev-searchbox'; grp.parentNode.insertBefore(box,grp); box.appendChild(grp); box.appendChild(opts); }
 function go(v){ var i=host.querySelector('.sb-stitch input.form-control'), b=host.querySelector('.sb-stitch .sb-go'); if(!i||!b){return;}
   var s=String(v).replace(/\\s*\\bmax\\s+\\d+/ig,'').trim(), n=document.getElementById('dipev-load'), k=n?parseInt(n.value,10):250;
   if(k>0 && k!==250){ s=(s+' max '+k).trim(); } i.value=s; b.click(); }
 window.DipEvSearch=function(v){ go(v); };
 window.DipEvLoad=function(){ var i=host.querySelector('.sb-stitch input.form-control'); go(i?i.value:''); };
 window.DipEvRefresh=function(){ var b=host.querySelector('.sb-stitch .sb-go'); if(b){b.click();} };
})();
</script>";
    echo dipev_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
    return true;
}

// search: the ?search= responder — summary + table + TINY, from one model.
function search(){
    $tpl=new template_admin();
    $_SESSION["FW_SEARCH"]=isset($_GET["search"])?$_GET["search"]:"";
    $m=dipev_model(isset($_GET["search"])?$_GET["search"]:"");

    $html=array();
    if($m["error"]!=""){
        $html[]=dipev_error($m);
    }else{
        $html[]="<div id='dipev-sum-src' style='display:none'>".dipev_summary($m)."</div>";
        $html[]=dipev_honey_only($m);
        $html[]=(count($m["rows"])==0)?dipev_empty($m):dipev_table($m);
    }
    echo dipev_style().$tpl->_ENGINE_parse_body(@implode("\n",$html))."<script>".dipev_js()."</script>".dipev_tiny_script();
}

function dipev_tiny_script(){
    $tpl=new template_admin();
    $topbuttons=array();
    $topbuttons[]=array("DipEvRefresh()",ico_refresh,"{refresh}");
    $topbuttons[]=array("LoadAjax('MainContent','fw.nginx.DecisionIP.statistics.php')",ico_statistics,"{dipev_open_stats}");
    $TINY=array();
    $TINY["TITLE"]="DecisionIP {firewall_events}";
    $TINY["ICO"]="fas fa-eye";
    $TINY["EXPL"]="{dipev_header_explain}";
    $TINY["URL"]="decisionip-events";
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "<script>NoSpinner();Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";
}

// ── Widgets ───────────────────────────────────────────────────────────────

function dipev_hero($enabled){
    $tpl=new template_admin();
    $chips=array();
    $chips[]="<span class='dipev-pill'><i class='fas fa-ban' aria-hidden='true'></i>{dipev_chip_blocked}</span>";
    if(is_array($enabled)){
        $n=count($enabled);
        $txt=($n==1)?$tpl->_ENGINE_parse_body("{dipev_chip_list_one}"):str_replace("%s",strval($n),$tpl->_ENGINE_parse_body("{dipev_chip_lists}"));
        $chips[]="<span class='dipev-pill'><i class='fas fa-layer-group' aria-hidden='true'></i>$txt</span>";
    }
    $chips[]="<span class='dipev-pill'><i class='fas fa-eye' aria-hidden='true'></i>{dipev_chip_sample}</span>";
    return "<section class='dipev-hero'>".
        "<div class='dipev-hero-ico' aria-hidden='true'><i class='fas fa-shield-halved'></i></div>".
        "<div class='dipev-hero-txt'><h2 class='dipev-hero-t'>{dipev_hero_title}</h2>".
        "<p class='dipev-hero-p'>{dipev_hero_intro}</p>".
        "<div class='dipev-pills'>".implode("",$chips)."</div></div>".
        "</section>";
}

function dipev_summary($m){
    $h=array();
    $h[]=dipev_scale($m);
    if(count($m["rows"])>0){
        $h[]="<div class='dipev-tiles'>";
        $h[]=dipev_tile_events($m);
        $h[]=dipev_tile_sources($m);
        $h[]=dipev_tile_list($m);
        $h[]=dipev_tile_port($m);
        $h[]="</div>";
    }
    $h[]=dipev_filter_chips($m);
    return implode("\n",$h);
}

// Scale strip: the exact counters next to the sample, so the table is read as a sample.
function dipev_scale($m){
    $tpl=new template_admin();
    $c=$m["counters"];
    $h=array();
    $h[]="<section class='dipev-card dipev-scale'>";
    if($c["ok"]){
        $h[]="<span class='dipev-fact'><span class='dipev-dot' aria-hidden='true'></span>".str_replace("%s","<b>".number_format($c["total"])."</b>",$tpl->_ENGINE_parse_body("{dipev_scale_total}")).dipev_info("{dipev_scale_total_tip}")."</span>";
        $h[]="<span class='dipev-fact'>".str_replace("%s","<b>".number_format($c["last5"])."</b>",$tpl->_ENGINE_parse_body("{dipev_scale_last5}")).dipev_info("{dipev_scale_last5_tip}")."</span>";
    }else{
        $h[]="<span class='dipev-fact dipev-muted'><i class='fas fa-circle-info' aria-hidden='true'></i>{dipev_scale_na}</span>";
    }
    if(count($m["rows"])>0){
        $shown=str_replace(array("%n","%r"),array("<b>".number_format(count($m["rows"]))."</b>",dipev_range($m)),$tpl->_ENGINE_parse_body("{dipev_scale_shown}"));
        $h[]="<span class='dipev-fact'><i class='fas fa-clock' aria-hidden='true'></i>$shown</span>";
    }
    $h[]="<a class='dipev-link' href='javascript:blur();' onclick=\"LoadAjax('MainContent','fw.nginx.DecisionIP.statistics.php')\"><i class='fas fa-chart-column' aria-hidden='true'></i>{dipev_open_stats}</a>";
    $h[]="</section>";
    return implode("",$h);
}

function dipev_tile($icon,$label,$tip,$value,$sub,$extra=""){
    return "<div class='dipev-card dipev-tile'><div class='dipev-tile-l'><span>$label</span>".dipev_info($tip)."$extra<i class='fas $icon dipev-tile-i' aria-hidden='true'></i></div>".
        "<div class='dipev-tile-v'>$value</div><div class='dipev-tile-s'>$sub</div></div>";
}

function dipev_tile_events($m){
    return dipev_tile("fa-list-check","{dipev_events_loaded}","{dipev_events_loaded_tip}",number_format(count($m["rows"])),dipev_range($m));
}

function dipev_tile_sources($m){
    $tpl=new template_admin();
    $rep=0;
    foreach($m["sources"] as $n){ if($n>1){$rep++;} }
    $sub=($rep>0)?str_replace("%s",number_format($rep),$tpl->_ENGINE_parse_body("{dipev_sources_repeat}")):"{dipev_sources_sub}";
    return dipev_tile("fa-network-wired","{dipev_sources}","{dipev_sources_tip}",number_format(count($m["sources"])),$sub);
}

// Most active list + one bar per list (inline SVG, list colours) — counts of the rows loaded.
function dipev_tile_list($m){
    $tpl=new template_admin();
    reset($m["lists"]);
    $top=key($m["lists"]);$cnt=current($m["lists"]);
    $L=dipev_list((string)$top);
    $sub=str_replace(array("%n","%t"),array(number_format($cnt),number_format(count($m["rows"]))),$tpl->_ENGINE_parse_body("{dipev_n_of_t}"));
    $bars="";
    $max=max($m["lists"]);$x=0;$n=count($m["lists"]);
    if($n>1){
        $bars="<svg class='dipev-bars' width='".($n*9)."' height='20' viewBox='0 0 ".($n*9)." 20' aria-hidden='true'>";
        foreach($m["lists"] as $p=>$c){
            $Lb=dipev_list((string)$p);
            $hgt=max(2,round(18*$c/$max));
            $bars.="<rect fill='{$Lb[4]}' x='$x' y='".(20-$hgt)."' width='6' height='$hgt' rx='1'></rect>";
            $x+=9;
        }
        $bars.="</svg>";
    }
    return "<div class='dipev-card dipev-tile'><div class='dipev-tile-l'><span>{dipev_top_list}</span>".dipev_info("{dipev_top_list_tip}")."$bars</div>".
        "<div class='dipev-tile-v dipev-tile-vl {$L[1]}'><i class='fas {$L[0]}' aria-hidden='true'></i><span>{$L[2]}</span></div><div class='dipev-tile-s'>$sub</div></div>";
}

function dipev_tile_port($m){
    $tpl=new template_admin();
    if(count($m["ports"])==0){
        return dipev_tile("fa-bullseye","{dipev_top_service}","{dipev_top_service_tip}","—","&nbsp;");
    }
    reset($m["ports"]);
    $port=intval(key($m["ports"]));$c=current($m["ports"]);
    $sub=str_replace("%n",number_format($c),$tpl->_ENGINE_parse_body("{dipev_n_events}"));
    $svc=dipev_service($port);
    if($svc!=""){
        $val="$svc <span class='dipev-tile-hint'>".str_replace("%s",$port,$tpl->_ENGINE_parse_body("{dipev_port_lbl}"))."</span>";
    }else{
        $val=str_replace("%s",$port,$tpl->_ENGINE_parse_body("{dipev_port_n}"));
    }
    return dipev_tile("fa-bullseye","{dipev_top_service}","{dipev_top_service_tip}",$val,$sub);
}

// Filter chips — client-side, on the loaded rows (exact prefix match, unlike the daemon grep
// where "anonymousrelays" also matches "anonymousrelays-novpn"). Enabled lists with no
// event loaded still get their (greyed) chip; a disabled list appears only if the log has it.
function dipev_filter_chips($m){
    $keys=array();
    if(is_array($m["enabled"])){ foreach($m["enabled"] as $k){$keys[$k]=true;} }
    foreach($m["lists"] as $p=>$c){ $keys[(string)$p]=true; }
    if(count($keys)==0){return "";}
    $h=array();
    $h[]="<section class='dipev-card dipev-filter'>";
    $h[]="<div class='dipev-filter-l'><span>{dipev_filter_list}</span>".dipev_info("{dipev_filter_tip}")."</div>";
    $h[]="<div class='dipev-chips' role='group' aria-label='{dipev_filter_list}'>";
    $h[]="<button type='button' class='dipev-chip dipev-chip-all on' aria-pressed='true' data-p='' onclick=\"DipEvFilter('',this)\"><i class='fas fa-layer-group' aria-hidden='true'></i>{dipev_all} <span class='dipev-chip-n'>&middot; ".number_format(count($m["rows"]))."</span></button>";
    foreach(array_keys($keys) as $p){
        $c=isset($m["lists"][$p])?$m["lists"][$p]:0;
        $L=dipev_list((string)$p);
        $pj=htmlspecialchars((string)$p,ENT_QUOTES);
        $off=($c==0)?" dipev-chip-zero":"";
        $dis=($c==0)?" disabled":"";
        $h[]="<button type='button' class='dipev-chip {$L[1]}$off' aria-pressed='false' data-p='$pj'$dis onclick=\"DipEvFilter(this.getAttribute('data-p'),this)\">".
            "<span class='dipev-dot' style='background:{$L[4]}' aria-hidden='true'></span><i class='fas {$L[0]}' aria-hidden='true'></i>{$L[2]} <span class='dipev-chip-n'>&middot; ".number_format($c)."</span></button>";
    }
    $h[]="</div>";
    $h[]="<div class='dipev-note'>{dipev_filter_note}</div>";
    $h[]="</section>";
    return implode("\n",$h);
}

function dipev_search_card($tpl,$page){
    $ex=array("203.0.113.7","honeypot","3389");
    $links=array();
    foreach($ex as $e){
        $links[]="<button type='button' class='dipev-ex' onclick=\"DipEvSearch('".$e."')\">".$e."</button>";
    }
    $opts=array(250=>"{dipev_load_default}",500=>"500",1000=>"1,000",2000=>"2,000");
    $sel=array();
    foreach($opts as $v=>$t){ $sel[]="<option value='$v'>$t</option>"; }
    // The search_block renders its results right under its input: the options row and the
    // hint are moved next to the input by form_search's script, inside their own card.
    return "<section class='dipev-search'>".
        "<div class='dipev-sb'>".$tpl->search_block($page,"","","","")."</div>".
        "<div id='dipev-searchopts' class='dipev-searchopts'>".
        "<div class='dipev-search-row'>".
        "<label class='dipev-load-l' for='dipev-load'>{dipev_events_to_load}</label>".
        "<select id='dipev-load' class='dipev-select' onchange='DipEvLoad();'>".implode("",$sel)."</select>".
        "<span class='dipev-exs'><span>{dipev_examples}</span>".implode("<span aria-hidden='true'>&middot;</span>",$links)."</span>".
        "</div>".
        "<div class='dipev-note'>{dipev_search_hint}</div>".
        "</div>".
        "</section>";
}

function dipev_table($m){
    $tpl=new template_admin();
    $n=count($m["rows"]);
    $h=array();
    $h[]="<section class='dipev-card dipev-tcard'>";
    $h[]="<div class='dipev-thead'><h3 class='dipev-ttl'>{dipev_table_title}</h3></div>";
    $h[]="<div class='dipev-scroll'><table class='dipev-tbl'>";
    $h[]="<thead><tr><th>{dipev_col_when}</th><th>{dipev_col_by}</th><th>{dipev_col_source}</th><th>{dipev_col_target}</th><th><span class='sr-only'>{dipev_details}</span></th></tr></thead>";
    $h[]="<tbody id='dipev-rows'>";
    foreach($m["rows"] as $r){$h[]=dipev_row($r,$m);}
    $h[]="</tbody></table></div>";
    $showing=str_replace(array("%n","%t"),array("<b id='dipev-count-n'>$n</b>",number_format($n)),$tpl->_ENGINE_parse_body("{dipev_showing}"));
    $h[]="<div class='dipev-foot'><span class='dipev-foot-h'><i class='fas fa-hand-pointer' aria-hidden='true'></i>{dipev_click_row}</span><span id='dipev-count'>$showing</span><span class='ak-pg' id='dipev-pager'></span></div>";
    $h[]="</section>";
    return implode("\n",$h);
}

function dipev_target_html($r){
    $tpl=new template_admin();
    $svc=($r["dport"]>0)?dipev_service($r["dport"]):"";
    $out="";
    if($r["dport"]>0){
        $out.=($svc!="")?"<b class='dipev-svc'>$svc</b>":"";
        $out.="<span class='dipev-port'>".str_replace("%s",intval($r["dport"]),$tpl->_ENGINE_parse_body("{dipev_port_lbl}"))."</span>";
    }else{
        $out.="<span class='ak-muted'>—</span>";
    }
    if($r["proto"]!=""){$out.="<span class='dipev-proto'>".htmlspecialchars($r["proto"])."</span>";}
    return $out;
}

function dipev_row($r,$m){
    $L=dipev_list($r["prefix"]);
    $g=isset($m["geo"][$r["src"]])?$m["geo"][$r["src"]]:array("country"=>"","flag"=>"","isp"=>"","hostname"=>"");
    $flag=($g["flag"]!="")?"<img class='dipev-flag' src='img/".htmlspecialchars($g["flag"],ENT_QUOTES)."' alt='".htmlspecialchars($g["country"],ENT_QUOTES)."' title='".htmlspecialchars($g["country"],ENT_QUOTES)."'>":"";
    $parts=array();
    if($g["country"]!=""){$parts[]=htmlspecialchars($g["country"]);}
    if($g["hostname"]!=""){$parts[]=htmlspecialchars($g["hostname"]);}
    elseif($g["isp"]!=""){$parts[]=htmlspecialchars($g["isp"]);}
    $sub=(count($parts)>0)?implode(" &middot; ",$parts):"&nbsp;";
    $exact=date("Y-m-d H:i:s",$r["ts"]);
    $to=htmlspecialchars($r["dst"]);
    $on=($r["iface"]!="")?"<span class='dipev-iface'> {dipev_on} ".htmlspecialchars($r["iface"])."</span>":"";
    return "<tr tabindex='0' style='--dipev-c:{$L[4]}' data-p='".htmlspecialchars($r["prefix"],ENT_QUOTES)."' data-e='".base64_encode($r["line"])."'>".
        "<td class='dipev-when'><b>".htmlspecialchars(dipev_ago($r["ts"]))."</b><span class='ak-mono'>$exact</span></td>".
        "<td><span class='dipev-badge {$L[1]}' title='".htmlspecialchars($r["prefix"],ENT_QUOTES)."'><span class='dipev-dot' style='background:{$L[4]}' aria-hidden='true'></span><i class='fas {$L[0]}' aria-hidden='true'></i>{$L[2]}</span></td>".
        "<td class='dipev-src'><div>$flag<b class='ak-mono'>".htmlspecialchars($r["src"])."</b>".($r["sport"]>0?"<span class='dipev-sport ak-mono'>:".intval($r["sport"])."</span>":"")."</div><span class='dipev-sub'>$sub</span></td>".
        "<td class='dipev-tgt'><div>".dipev_target_html($r)."</div><span class='dipev-sub'>{dipev_to} $to$on</span></td>".
        "<td class='dipev-chev'><i class='fas fa-chevron-right' aria-hidden='true'></i></td></tr>";
}

// Shown above the table when the only enabled list is the always-on Honeypot trap.
function dipev_honey_only($m){
    if(!is_array($m["enabled"]) || count($m["enabled"])!=1 || $m["enabled"][0]!="honeypot"){return "";}
    return "<section class='dipev-card dipev-state dipev-state-warn' role='status'>".
        "<div class='dipev-state-ico'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i></div>".
        "<div><b class='dipev-state-t'>{dipev_honey_only_title}</b><p class='dipev-state-p'>{dipev_honey_only_text}</p>".
        "<button type='button' class='dipev-btn' onclick=\"LoadAjax('MainContent','fw.nginx.DecisionIP.php')\"><i class='fas fa-sliders' aria-hidden='true'></i>{dipev_open_settings}</button></div></section>";
}

function dipev_empty($m){
    if($m["term"]!=""){
        $tpl=new template_admin();
        $title=str_replace("%s","“".htmlspecialchars($m["term"])."”",$tpl->_ENGINE_parse_body("{dipev_nomatch_title}"));
        return "<section class='dipev-card dipev-state'><div class='dipev-state-ico'><i class='fas fa-magnifying-glass' aria-hidden='true'></i></div>".
            "<div><b class='dipev-state-t'>$title</b><p class='dipev-state-p'>{dipev_nomatch_text}</p>".
            "<button type='button' class='dipev-link' onclick=\"DipEvSearch('')\">{dipev_clear_search}</button></div></section>";
    }
    return "<section class='dipev-card dipev-state'><div class='dipev-state-ico dipev-state-ok'><i class='fas fa-circle-check' aria-hidden='true'></i></div>".
        "<div><b class='dipev-state-t'>{dipev_empty_title}</b><p class='dipev-state-p'>{dipev_empty_intro}</p>".
        "<button type='button' class='dipev-btn' onclick=\"LoadAjax('MainContent','fw.nginx.DecisionIP.statistics.php')\"><i class='fas fa-chart-column' aria-hidden='true'></i>{dipev_open_stats}</button></div></section>";
}

function dipev_error($m){
    return "<section class='dipev-card dipev-state dipev-state-err' role='alert'><div class='dipev-state-ico'><i class='fas fa-circle-exclamation' aria-hidden='true'></i></div>".
        "<div><b class='dipev-state-t'>{dipev_error_title}</b><div class='ak-mono dipev-err'>".htmlspecialchars($m["error"])."</div>".
        "<button type='button' class='dipev-btn dipev-btn-err' onclick='DipEvRefresh();'><i class='fas fa-rotate-right' aria-hidden='true'></i>{dipev_try_again}</button></div></section>";
}

// ── Event details window ──────────────────────────────────────────────────
// The row passes its own JSON log line (base64): the event is shown exactly as logged,
// with no second daemon call. Every value is escaped; nothing is executed.
function event_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $b64=preg_replace('#[^A-Za-z0-9+/=]#','',(string)$_GET["event-js"]);
    $tpl->js_dialog2("{dipev_details}","$page?event-popup=".urlencode($b64),720);
}

function dipev_kv($label,$value,$help=""){
    $hp=($help!="")?"<span class='dipev-kv-h'>$help</span>":"";
    return "<div class='dipev-kv'><div class='dipev-kv-l'>$label$hp</div><div class='dipev-kv-v'>$value</div></div>";
}

function event_popup(){
    $tpl=new template_admin();
    $users=new usersMenus();
    $b64=preg_replace('#[^A-Za-z0-9+/=]#','',(string)$_GET["event-popup"]);
    $e=json_decode((string)base64_decode($b64),true);
    if(!is_array($e) || empty($e["src_ip"])){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{dipev_details}||{dipev_details_unreadable}"));
        return;
    }
    $v=function($k) use ($e){
        if(!isset($e[$k])){return "";}
        if(is_array($e[$k])){return implode(" ",array_map("strval",$e[$k]));}
        return trim((string)$e[$k]);
    };
    $ip=$v("src_ip");
    $modtools=new modesctools();
    $g=dipev_geo($modtools,$ip);
    $L=dipev_list(strtolower($v("prefix")));
    $dport=intval($v("dport"));
    $svc=dipev_service($dport);
    $dash="—";

    // The sentence: who, from where, what, why.
    $geo=array();
    if($g["country"]!=""){$geo[]=htmlspecialchars($g["country"]);}
    if($g["isp"]!=""){$geo[]=htmlspecialchars($g["isp"]);}
    if($dport>0){
        $what=($svc!="")?"<b>$svc</b> (".str_replace("%s",$dport,$tpl->_ENGINE_parse_body("{dipev_port_lbl}")).")":"<b>".str_replace("%s",$dport,$tpl->_ENGINE_parse_body("{dipev_port_n}"))."</b>";
    }else{
        $what="<b>".htmlspecialchars(strtoupper($v("proto")))."</b>";
    }
    $pattern=(count($geo)>0)?$tpl->_ENGINE_parse_body("{dipev_sentence}"):$tpl->_ENGINE_parse_body("{dipev_sentence_nogeo}");
    $sentence=str_replace(array("%src","%geo","%what","%dst","%list","%help"),
        array("<b class='ak-mono'>".htmlspecialchars($ip)."</b>",implode(" &middot; ",$geo),$what,"<span class='ak-mono'>".htmlspecialchars($v("dst_ip"))."</span>","<b>".$tpl->_ENGINE_parse_body($L[2])."</b>",$tpl->_ENGINE_parse_body($L[3])),
        $pattern);

    $ts=intval($v("timestamp"));
    $where=array();
    $where[]=dipev_kv("{dipev_f_ip}","<span class='ak-mono'>".htmlspecialchars($ip).($v("sport")!=""?":".intval($v("sport")):"")."</span>");
    $where[]=dipev_kv("{dipev_f_hostname}",$g["hostname"]!=""?htmlspecialchars($g["hostname"]):$dash);
    $where[]=dipev_kv("{dipev_f_country}",$g["country"]!=""?htmlspecialchars($g["country"]):$dash);
    $where[]=dipev_kv("{dipev_f_isp}",$g["isp"]!=""?htmlspecialchars($g["isp"]):$dash);

    $blk=array();
    $blk[]=dipev_kv("{dipev_f_when}",$ts>0?date("Y-m-d H:i:s",$ts):$dash);
    $blk[]=dipev_kv("{dipev_col_by}","<span class='dipev-badge {$L[1]}'><span class='dipev-dot' style='background:{$L[4]}' aria-hidden='true'></span><i class='fas {$L[0]}' aria-hidden='true'></i>{$L[2]}</span>",$L[3]);
    $blk[]=dipev_kv("{dipev_f_target}","<span class='ak-mono'>".htmlspecialchars($v("dst_ip")).($dport>0?":".$dport:"")."</span> ".($svc!=""?"<span class='dipev-svc'>$svc</span> ":"")."<span class='dipev-proto'>".htmlspecialchars(strtoupper($v("proto")))."</span>");
    $blk[]=dipev_kv("{dipev_f_iface}",$v("in_iface")!=""?"<span class='ak-mono'>".htmlspecialchars($v("in_iface"))."</span>":$dash,"{dipev_iface_help}");

    $tech=array();
    $tech[]=dipev_kv("{dipev_f_mac_src}",$v("mac_src")!=""?"<span class='ak-mono'>".htmlspecialchars($v("mac_src"))."</span>":$dash,"{dipev_macsrc_help}");
    $tech[]=dipev_kv("{dipev_f_mac_dst}",$v("mac_dst")!=""?"<span class='ak-mono'>".htmlspecialchars($v("mac_dst"))."</span>":$dash,"{dipev_macdst_help}");
    $tech[]=dipev_kv("TTL",$v("ttl")!=""?intval($v("ttl")):$dash,"{dipev_ttl_help}");
    $tech[]=dipev_kv("{dipev_f_len}",$v("len")!=""?intval($v("len")):$dash,"{dipev_len_help}");
    $tech[]=dipev_kv("{dipev_f_flags}",$v("flags")!=""?"<span class='ak-mono'>".htmlspecialchars($v("flags"))."</span>":$dash,"{dipev_flags_help}");

    $html=array();
    $html[]="<div class='dipev dipev-modal'>";
    $html[]="<div class='dipev-sentence'><i class='fas fa-circle-info' aria-hidden='true'></i><div>$sentence</div></div>";
    $html[]="<div class='dipev-sec'><h4 class='dipev-sec-t'><i class='fas fa-location-crosshairs' aria-hidden='true'></i>{dipev_sec_where}</h4>".implode("",$where)."</div>";
    $html[]="<div class='dipev-sec'><h4 class='dipev-sec-t'><i class='fas fa-ban' aria-hidden='true'></i>{dipev_sec_what}</h4>".implode("",$blk)."</div>";
    $html[]="<details class='dipev-tech'><summary><i class='fas fa-microchip' aria-hidden='true'></i>{dipev_sec_tech}</summary>".implode("",$tech)."</details>";
    if($users->AsWebSecurity){
        $ipJs=urlencode($ip);
        $html[]="<div class='dipev-modal-foot'><button type='button' class='dipev-btn' onclick=\"Loadjs('fw.modsecurity.threats.php?zoom-ip-js=$ipJs')\"><i class='fas fa-user-shield' aria-hidden='true'></i>{dipev_full_profile}</button></div>";
    }
    $html[]="</div>";
    echo dipev_style().$tpl->_ENGINE_parse_body($html);
}

// ── JS / CSS ──────────────────────────────────────────────────────────────

// Moves the summary above the search box, then filters + paginates the loaded rows
// (only the matching rows are paged: a page never shows hidden rows).
function dipev_js(){
    return str_replace("__PAGE__",CurrentPageName(),'
(function(){
 var src=document.getElementById("dipev-sum-src"), dst=document.getElementById("dipev-summary");
 if(dst){ dst.innerHTML=""; if(src){ src.style.display=""; dst.appendChild(src); } }
 var tb=document.getElementById("dipev-rows"); if(!tb){return;}
 var size=50, cur=1, flt="";
 function open(tr){ if(tr && tr.getAttribute("data-e")){ Loadjs("__PAGE__?event-js="+encodeURIComponent(tr.getAttribute("data-e"))); } }
 tb.onclick=function(ev){ var t=ev.target; while(t && t.tagName!=="TR"){t=t.parentNode;} open(t); };
 tb.onkeydown=function(ev){ if(ev.key==="Enter"){ var t=ev.target; while(t && t.tagName!=="TR"){t=t.parentNode;} open(t); } };
 function rows(){ var a=[],r=tb.rows; for(var i=0;i<r.length;i++){ if(flt===""||r[i].getAttribute("data-p")===flt){a.push(r[i]);} } return a; }
 function draw(){
  var all=tb.rows, list=rows(), n=list.length, pages=Math.max(1,Math.ceil(n/size)); if(cur>pages){cur=pages;}
  for(var i=0;i<all.length;i++){ all[i].style.display="none"; }
  for(var k=0;k<n;k++){ if(k>=(cur-1)*size && k<cur*size){ list[k].style.display=""; } }
  var cn=document.getElementById("dipev-count-n"); if(cn){cn.textContent=n;}
  var box=document.getElementById("dipev-pager"); if(!box){return;}
  if(pages<=1){ box.innerHTML=""; return; }
  var h="<a href=\"javascript:blur();\" class=\""+(cur==1?"off":"")+"\" data-p=\""+(cur-1)+"\" aria-label=\"&lsaquo;\">&lsaquo;</a>";
  var end=Math.min(pages,cur+2), start=Math.max(1,end-4); end=Math.min(pages,start+4);
  for(var p=start;p<=end;p++){ h+="<a href=\"javascript:blur();\" class=\""+(p==cur?"cur":"")+"\" data-p=\""+p+"\">"+p+"</a>"; }
  h+="<a href=\"javascript:blur();\" class=\""+(cur==pages?"off":"")+"\" data-p=\""+(cur+1)+"\" aria-label=\"&rsaquo;\">&rsaquo;</a>";
  box.innerHTML=h;
  var as=box.getElementsByTagName("a");
  for(var j=0;j<as.length;j++){ as[j].onclick=function(){ var p=parseInt(this.getAttribute("data-p"),10); if(p>=1&&p<=pages&&p!=cur){cur=p;draw();} return false; }; }
 }
 window.DipEvFilter=function(p,btn){
  flt=p||""; cur=1;
  var cs=document.querySelectorAll(".dipev-chip");
  for(var i=0;i<cs.length;i++){ var on=(cs[i].getAttribute("data-p")===flt); cs[i].classList.toggle("on",on); cs[i].setAttribute("aria-pressed",on?"true":"false"); }
  draw();
 };
 draw();
})();');
}

function dipev_style(){
    if(isset($GLOBALS["DIPEV_STYLE"])){return "";}
    $GLOBALS["DIPEV_STYLE"]=true;
    $accent="#005447";$soft="#e6eeed";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.dipev{margin-top:10px;display:flex;flex-direction:column;gap:16px;color:#111827;font-size:14px}
.dipev *{box-sizing:border-box}
.dipev h2,.dipev h3,.dipev h4{margin:0}
.dipev-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.dipev-hero{display:flex;gap:20px;align-items:flex-start;background:#fff;border:1px solid #e5e7eb;border-left:4px solid $accent;border-radius:12px;padding:20px 24px;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.dipev-hero-ico{flex:0 0 48px;height:48px;border-radius:12px;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:22px}
.dipev-hero-txt{flex:1;min-width:0}
.dipev-hero-t{font-size:18px;font-weight:600;color:#111827;line-height:1.35}
.dipev-hero-p{font-size:14px;color:#374151;line-height:1.55;margin:6px 0 0;max-width:900px}
.dipev-pills{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.dipev-pill{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#374151;background:#f2f6f6;border:1px solid #e3ebea;border-radius:999px;padding:4px 12px}
.dipev-pill i,.dipev-pill svg{color:$accent}
#dipev-summary:empty{display:none}
#dipev-summary>div{display:flex;flex-direction:column;gap:16px}
.dipev-scale{display:flex;flex-wrap:wrap;align-items:center;gap:8px 0;padding:14px 20px;min-height:56px}
.dipev-fact{display:inline-flex;align-items:center;gap:8px;font-size:13px;color:#374151;padding:0 20px;border-left:1px solid #e5e7eb}
.dipev-fact:first-child{padding-left:0;border-left:0}
.dipev-fact b{color:#111827;font-size:14px;font-variant-numeric:tabular-nums}
.dipev-fact i,.dipev-fact svg{color:#6b7280}
.dipev-muted{color:#6b7280}
.dipev-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#16a34a;flex:0 0 8px}
.dipev-link{margin-left:auto;display:inline-flex;align-items:center;gap:6px;color:$accent;font-size:13px;font-weight:600;background:none;border:0;padding:0;cursor:pointer;text-decoration:none}
.dipev-link:hover{color:#003d33;text-decoration:underline}
.dipev-info{color:#9ca3af;cursor:help;font-size:13px;margin-left:6px}
.dipev-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.dipev-tile{padding:16px 20px;min-height:112px}
.dipev-tile-l{display:flex;align-items:center;font-size:13px;color:#6b7280}
.dipev-tile-i{margin-left:auto;color:#9ca3af}
.dipev-bars{margin-left:auto}
.dipev-tile-v{font-size:28px;font-weight:600;color:#111827;margin-top:6px;line-height:34px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-variant-numeric:tabular-nums}
.dipev-tile-vl{font-size:20px;display:flex;align-items:center;gap:8px;color:var(--dipev-fg)}
.dipev-tile-vl span{overflow:hidden;text-overflow:ellipsis}
.dipev-tile-hint{font-size:13px;font-weight:400;color:#6b7280}
.dipev-tile-s{font-size:13px;color:#4b5563;margin-top:4px}
.dipev-filter{padding:16px 20px}
.dipev-filter-l{display:flex;align-items:center;font-size:15px;font-weight:600;color:#111827;margin-bottom:10px}
.dipev-chips{display:flex;flex-wrap:wrap;gap:8px}
.dipev-chip{display:inline-flex;align-items:center;gap:6px;height:32px;padding:0 12px;border-radius:999px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-size:13px;font-weight:500;cursor:pointer;white-space:nowrap}
.dipev-chip:hover{border-color:var(--dipev-fill,#9ca3af)}
.dipev-chip i,.dipev-chip svg{color:var(--dipev-fg)}
.dipev-chip.on{background:var(--dipev-fg);border-color:var(--dipev-fg);color:#fff}
.dipev-chip.on i,.dipev-chip.on svg{color:#fff}
.dipev-chip-all{--dipev-fg:$accent}
.dipev-chip-zero{color:#9ca3af;cursor:default}
.dipev-chip-zero .dipev-dot,.dipev-chip-zero i,.dipev-chip-zero svg{opacity:.45}
.dipev-chip-n{font-weight:400;opacity:.85}
.dipev-note{font-size:13px;color:#6b7280;margin-top:10px}
.dipev-chip:focus-visible,.dipev-tbl tbody tr:focus-visible,.dipev-ex:focus-visible,.dipev-link:focus-visible,.dipev-btn:focus-visible,.dipev-info:focus-visible,.dipev-select:focus-visible,.dipev-tech summary:focus-visible{outline:2px solid $accent;outline-offset:2px}
.dipev-t-red{--dipev-bg:#fee2e2;--dipev-fg:#b91c1c;--dipev-fill:#dc2626}
.dipev-t-amber{--dipev-bg:#ffedd5;--dipev-fg:#9a3412;--dipev-fill:#d97706}
.dipev-t-ochre{--dipev-bg:#fef3c7;--dipev-fg:#854d0e;--dipev-fill:#a16207}
.dipev-t-slate{--dipev-bg:#e2e8f0;--dipev-fg:#334155;--dipev-fill:#64748b}
.dipev-t-cyan{--dipev-bg:#cffafe;--dipev-fg:#155e75;--dipev-fill:#0e7490}
.dipev-t-emerald{--dipev-bg:#e6eeed;--dipev-fg:#005447;--dipev-fill:#005447}
.dipev-t-green{--dipev-bg:#dcfce7;--dipev-fg:#166534;--dipev-fill:#15803d}
.dipev-t-grey{--dipev-bg:#f1f5f9;--dipev-fg:#475569;--dipev-fill:#94a3b8}
.dipev-searchbox{padding:16px 20px;margin-bottom:16px}
.dipev-sb .sb-stitch{margin:0}
.dipev-sb .sb-stitch .ibox-content{padding:0;background:transparent;border:0}
.dipev-sb .sb-stitch .input-group{width:100%;max-width:100%;margin-left:0;box-sizing:border-box}
.dipev-sb .sb-stitch .sb-go{font-size:13px;font-weight:600;background:$accent;color:#fff}
.dipev-sb .sb-stitch .sb-go:hover{background:#003d33}
.dipev-search-row{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-top:12px;padding-top:12px;border-top:1px solid #f1f2f4}
.dipev-load-l{font-size:13px;color:#374151;font-weight:500;margin:0}
.dipev-select{height:32px;border:1px solid #d1d5db;border-radius:8px;padding:0 10px;font-size:13px;background:#fff;color:#111827}
.dipev-exs{margin-left:auto;display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6b7280}
.dipev-ex{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;color:$accent;background:$soft;border:1px solid #bfd4d1;border-radius:6px;padding:2px 8px;cursor:pointer}
.dipev-ex:hover{background:#d6e5e3}
.dipev-tcard{overflow:hidden}
.dipev-thead{padding:16px 20px 8px}
.dipev-ttl{font-size:15px;font-weight:600;color:#111827}
.dipev-scroll{overflow-x:auto}
.dipev-tbl{width:100%;border-collapse:collapse}
.dipev-tbl thead th{padding:10px 16px;font-size:13px;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;text-align:left;white-space:nowrap;background:#fafafa}
.dipev-tbl tbody tr{border-top:1px solid #f1f2f4;cursor:pointer}
.dipev-tbl tbody tr:first-child{border-top:0}
.dipev-tbl tbody tr:hover{background:#f9fafb}
.dipev-tbl td{padding:10px 16px;vertical-align:middle;font-size:13px;color:#111827;height:56px}
.dipev-tbl td:first-child{box-shadow:inset 3px 0 0 var(--dipev-c,#e5e7eb)}
.dipev-when{white-space:nowrap}
.dipev-when b{display:block;font-size:14px;font-weight:600}
.dipev-when span{display:block;font-size:13px;color:#6b7280}
.dipev-badge{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;background:var(--dipev-bg);color:var(--dipev-fg);font-size:13px;font-weight:500;white-space:nowrap}
.dipev-src b{font-size:13px;font-weight:600}
.dipev-flag{width:18px;height:auto;margin-right:8px;vertical-align:-2px}
.dipev-sport{color:#6b7280;font-size:13px;margin-left:2px}
.dipev-sub{display:block;font-size:13px;color:#6b7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:380px;margin-top:2px}
.dipev-svc{font-weight:600;color:#111827;margin-right:8px}
.dipev-port{display:inline-block;padding:1px 8px;border-radius:6px;background:#f3f4f6;border:1px solid #e5e7eb;color:#374151;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;margin-right:6px}
.dipev-proto{display:inline-block;padding:1px 7px;border-radius:6px;border:1px solid #d1d5db;color:#374151;font-size:13px;font-weight:600}
.dipev-chev{width:1%;color:#9ca3af;text-align:right}
.dipev-foot{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:12px 20px;border-top:1px solid #f1f2f4;font-size:13px;color:#4b5563}
.dipev-foot-h{color:#6b7280;flex:1 1 auto;display:inline-flex;align-items:center;gap:6px}
.dipev-state{display:flex;gap:16px;align-items:flex-start;padding:24px}
.dipev-state-ico{flex:0 0 44px;height:44px;border-radius:12px;background:#f3f4f6;color:#6b7280;display:flex;align-items:center;justify-content:center;font-size:20px}
.dipev-state-ok{background:#dcfce7;color:#16a34a}
.dipev-state-t{display:block;font-size:15px;font-weight:600;color:#111827}
.dipev-state-p{max-width:720px;margin:4px 0 12px;color:#374151;font-size:14px;line-height:1.5}
.dipev-state-warn{background:#fffbeb;border-color:#fde68a;margin-bottom:16px}
.dipev-state-warn .dipev-state-ico{background:#fef3c7;color:#b45309}
.dipev-state-err{background:#fef2f2;border-color:#fecaca}
.dipev-state-err .dipev-state-ico{background:#fee2e2;color:#dc2626}
.dipev-state-err .dipev-state-t{color:#991b1b}
.dipev-err{font-size:13px;margin:4px 0 12px;word-break:break-word;color:#7f1d1d}
.dipev-btn{display:inline-flex;align-items:center;gap:8px;background:$accent;color:#fff;border:0;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer}
.dipev-btn:hover{background:#003d33}
.dipev-btn-err{background:#dc2626}.dipev-btn-err:hover{background:#b91c1c}
.dipev-modal{margin-top:0;gap:14px}
.dipev-sentence{display:flex;gap:10px;background:$soft;border:1px solid #bfd4d1;border-radius:10px;padding:12px 14px;font-size:14px;line-height:1.55;color:#1f2937}
.dipev-sentence>i,.dipev-sentence>svg{color:$accent;margin-top:3px;flex:0 0 auto}
.dipev-sec{border:1px solid #e5e7eb;border-radius:10px;padding:4px 14px 6px}
.dipev-sec-t{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#374151;padding:10px 0 6px}
.dipev-sec-t i,.dipev-sec-t svg{color:$accent}
.dipev-kv{display:flex;gap:16px;padding:8px 0;border-top:1px solid #f1f2f4}
.dipev-kv-l{flex:0 0 40%;font-size:13px;color:#6b7280}
.dipev-kv-h{display:block;font-size:13px;color:#9ca3af;margin-top:2px}
.dipev-kv-v{flex:1;font-size:13px;color:#111827;word-break:break-word}
.dipev-tech{border:1px solid #e5e7eb;border-radius:10px;padding:0 14px}
.dipev-tech summary{cursor:pointer;padding:10px 0;font-size:13px;font-weight:600;color:#374151;display:flex;align-items:center;gap:8px}
.dipev-tech[open] summary{border-bottom:1px solid #f1f2f4}
.dipev-tech .dipev-kv:first-of-type{border-top:0}
.dipev-modal-foot{text-align:right}
.sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
@media (prefers-reduced-motion:no-preference){.dipev-tbl tbody tr{transition:background .15s}}
@media(max-width:1200px){.dipev-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}.dipev-iface{display:none}.dipev-fact{padding:0 14px}}
</style>";
}
