<?php
// fw.nginx.egdeguard.php — EdgeGuard (reverse-proxy web application shield).
// Feature page: explains what EdgeGuard brings, guides the link to the central
// console, then shows the real protection figures of the local journal
// (/edgeguard/events/summary) and the agent health. The journal itself is the
// modal of fw.nginx.edgeguard.php.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors',1);ini_set('error_reporting',E_ALL);}

// Server-side gate: the page used to answer any authenticated session.
if(!egp_can_view()){ exit(); }

if(isset($_GET["table"])){egp_content();exit;}
if(isset($_GET["content"])){egp_content_inner();exit;}
if(isset($_GET["file-uploaded"])){egp_upload();exit;}
if(isset($_POST["edgeguard_central_ip"])){egp_link_save();exit;}
if(isset($_GET["callback"])){egp_link_callback();exit;}
if(isset($_POST["edgeguard_unlink"])){egp_unlink_save();exit;}
if(isset($_GET["link-js"])){egp_link_js();exit;}
if(isset($_GET["unlink-js"])){egp_unlink_js();exit;}
if(isset($_GET["unlink-popup"])){egp_unlink_popup();exit;}
if(isset($_GET["link-popup"])){egp_link_popup();exit;}

page();

function egp_can_view():bool{
    $users=new usersMenus();
    return ($users->AsSystemAdministrator || $users->AsWebMaster);
}

// Linking/unlinking/uploading the enrollment file was only gated by the form
// display (form_outside "AsSystemAdministrator"), never by the handlers.
function egp_can_write():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemAdministrator;
}

function egp_t(string $key):string{
    $tpl=new template_admin();
    return trim(str_replace(array("[br]","[BR]"),array(" "," "),$tpl->_ENGINE_parse_body("{".$key."}")));
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{edgeguard}","fa-solid fa-block-brick-fire","{egp_header_explain}",
        "$page?table=yes","edgeguard-agent","progress-edgeguard-restart");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// ────────────────────────────────────────────────────────────────────────────
// Model
// ────────────────────────────────────────────────────────────────────────────

function egp_win():string{
    $w=(string)($_GET["win"] ?? "7d");
    if(!in_array($w,array("24h","7d","30d"),true)){ $w="7d"; }
    return $w;
}

function egp_license():array{
    $out=array("ok"=>true,"free"=>true,"sites"=>-1);
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/count/license"));
    if(json_last_error()!==JSON_ERROR_NONE || !is_object($j)){ return $out; }
    if(!property_exists($j,"ActiveRules")){ return $out; }
    $out["sites"]=intval($j->ActiveRules);
    // 2 websites without license, same as ReverseLicense.FreeWebsites
    if($out["sites"]<3){ return $out; }
    $out["free"]=false;
    $out["ok"]=!empty($j->Status);
    return $out;
}

function egp_model(string $win):array{
    $m=array("win"=>$win,"license"=>egp_license(),"engine"=>"","compatible"=>true,"info_ok"=>false,
        "linked"=>false,"agent"=>array(),"active"=>false,"running"=>null,"version"=>"","memory"=>0,"uptime"=>"",
        "sum"=>null,"sum_error"=>"");
    $info=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/edgeguard/agent/info"),true);
    if(is_array($info)){
        $m["info_ok"]=true;
        $m["engine"]=(string)($info["NginxVersion"] ?? "");
        // Same rule as before the redesign: a 1.27 or older web engine cannot load the agent.
        if(intval($info["NginxVersionMinor"] ?? 0)<=27 && intval($info["NginxVersionMajor"] ?? 1)<=1){ $m["compatible"]=false; }
        if(!empty($info["sites"]) && is_array($info["sites"]) && is_array($info["sites"][0])){
            $m["linked"]=true;
            $m["agent"]=$info["sites"][0];
            $m["active"]=(strtolower((string)($m["agent"]["Status"] ?? ""))==="active");
        }
    }
    $m["version"]=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_EDGEGUARD_AGENT_VER"));
    if($m["linked"]){
        $st=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/edgeguard/status"));
        if(is_object($st) && isset($st->Info)){
            $ini=new Bs_IniHandler();
            $ini->loadString((string)$st->Info);
            $p=$ini->_params["APP_EDGEGUARD_AGENT"] ?? array();
            if(is_array($p) && isset($p["running"])){
                $m["running"]=(intval($p["running"])==1);
                $m["memory"]=intval($p["master_memory"] ?? 0);
                $m["uptime"]=(string)($p["uptime"] ?? "");
                if(!empty($p["master_version"]) && $p["master_version"]!=="0.0.0"){ $m["version"]=(string)$p["master_version"]; }
            }
        }
        $map=array("24h"=>86400,"7d"=>7*86400,"30d"=>30*86400);
        $to=time(); $from=$to-$map[$win];
        $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/edgeguard/events/summary?from=$from&to=$to");
        $j=json_decode((string)$raw,true);
        if(is_array($j) && !empty($j["Status"])){
            $m["sum"]=$j;
        }else{
            $m["sum_error"]=is_array($j)?(string)($j["Error"] ?? ""):"";
        }
    }
    return $m;
}

function egp_ts($v):int{
    if(is_array($v)){
        if(isset($v["Valid"]) && !$v["Valid"]){ return 0; }
        $v=$v["String"] ?? "";
    }
    $v=trim((string)$v);
    if($v===""){ return 0; }
    try{
        // SQLite CURRENT_TIMESTAMP strings carry no zone and are UTC.
        $hasZone=preg_match('#(Z|[+-]\d\d:?\d\d|UTC)$#i',$v);
        $d=$hasZone?new DateTime($v):new DateTime($v,new DateTimeZone("UTC"));
        return intval($d->getTimestamp());
    }catch(\Exception $e){
        return 0;
    }
}

function egp_ago(int $ts):string{
    if($ts<=0){ return "&mdash;"; }
    $d=max(0,time()-$ts);
    if($d<60){ return "{egp_just_now}"; }
    if($d<3600){ return intval($d/60)." {egp_u_min} {egp_ago}"; }
    if($d<86400){ return intval($d/3600)." {egp_u_h} {egp_ago}"; }
    return intval($d/86400)." {egp_u_d} {egp_ago}";
}

function egp_n($v):string{
    return number_format(intval($v),0,"."," ");
}

function egp_reason_label(string $code):string{
    $c=trim($code);
    if($c===""){ return "{egp_unknown_reason}"; }
    $t=ucfirst(str_replace(array("_","-"),array(" "," "),strtolower($c)));
    $t=preg_replace_callback('#\b(sql|xss|rce|lfi|rfi|ssrf|csrf|xxe|ssti|dlp|ua|ip|url|api)\b#i',function($m){ return strtoupper($m[1]); },$t);
    return htmlspecialchars($t);
}

// ────────────────────────────────────────────────────────────────────────────
// Content (host refreshed every 60 s)
// ────────────────────────────────────────────────────────────────────────────

function egp_content(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $win=egp_win();

    $topbuttons=array();
    $topbuttons[]=array("Loadjs('fw.nginx.edgeguard.php?js=yes')","fa-solid fa-list","{egp_open_journal}");
    $topbuttons[]=array("s_PopUp('https://wiki.articatech.com/en/reverse-proxy/edgeguard','1024','800')",ico_support,"{egp_documentation}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{edgeguard}";
    $TINY_ARRAY["ICO"]="fa-solid fa-block-brick-fire";
    $TINY_ARRAY["EXPL"]="{egp_header_explain}";
    $TINY_ARRAY["URL"]="edgeguard-agent";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo egp_style()."<div id='egp-host' class='egp'>";
    egp_content_inner();
    echo "</div>".egp_js($win)."<script>$tiny</script>";
}

function egp_content_inner(){
    $tpl=new template_admin();
    $m=egp_model(egp_win());
    $h=array();
    $h[]=egp_hero($m);
    $h[]=egp_blockers($m);
    if(!$m["linked"]){
        $h[]=egp_benefits();
        $h[]=egp_setup($m);
    }else{
        $h[]=egp_attention($m);
        $h[]=egp_kpis($m);
        $h[]=egp_analysis($m);
        $h[]=egp_link_card($m);
    }
    echo $tpl->_ENGINE_parse_body(@implode("\n",$h))."<script>NoSpinner();</script>";
}

// ────────────────────────────────────────────────────────────────────────────
// Widgets (one function each)
// ────────────────────────────────────────────────────────────────────────────

function egp_state(array $m):array{
    if(!$m["linked"]){ return array("muted","fa-solid fa-circle-pause","{egp_pill_not_linked}"); }
    if($m["running"]===false || !$m["active"]){ return array("warn","fa-solid fa-triangle-exclamation","{egp_pill_attention}"); }
    return array("ok","fa-solid fa-shield-halved","{egp_pill_protecting}");
}

function egp_hero(array $m):string{
    $st=egp_state($m);
    $lic=$m["license"];
    $chips=array();
    $chips[]="<span class='egp-chip'><i class='fa-solid fa-magnifying-glass'></i> {egp_chip_inspected}</span>";
    $chips[]="<span class='egp-chip'><i class='fa-solid fa-sitemap'></i> {egp_chip_one_console}</span>";
    $chips[]="<span class='egp-chip'><i class='fa-solid fa-lock'></i> {egp_chip_encrypted}</span>";
    if($lic["free"]){
        $chips[]="<span class='egp-chip'><i class='fa-solid fa-gift'></i> {egp_chip_free}</span>";
    }elseif($lic["ok"]){
        $chips[]="<span class='egp-chip'><i class='fa-solid fa-certificate'></i> {egp_chip_licensed}</span>";
    }
    $protected=($st[0]==="ok");
    $flow="<div class='egp-flow' aria-hidden='true'>"
        ."<div class='egp-flow-head'><span>{egp_flow_title}</span><span class='egp-pill egp-pill-{$st[0]}'><i class='{$st[1]}'></i> {$st[2]}</span></div>"
        ."<div class='egp-node'><span class='egp-node-ico'><i class='fa-solid fa-users'></i></span><span><b>{egp_flow_visitors}</b><small>{egp_flow_visitors_sub}</small></span></div>"
        ."<div class='egp-arrows'><span class='egp-arr-ok'><i class='fa-solid fa-arrow-down'></i></span><span class='egp-arr-bad'><i class='fa-solid fa-ban'></i> {egp_flow_blocked}</span><span class='egp-arr-ok'><i class='fa-solid fa-arrow-down'></i></span></div>"
        ."<div class='egp-node egp-node-shield".($protected?" egp-node-on":"")."'><span class='egp-node-ico'><i class='fa-solid fa-shield-halved'></i></span><span><b>{egp_flow_shield}</b><small>{egp_flow_shield_sub}</small></span></div>"
        ."<div class='egp-arrows egp-arrows-2'><span class='egp-arr-ok'><i class='fa-solid fa-arrow-down'></i> {egp_flow_clean}</span></div>"
        ."<div class='egp-node'><span class='egp-node-ico egp-ico-blue'><i class='fa-solid fa-server'></i></span><span><b>{egp_flow_sites}</b><small>{egp_flow_sites_sub}</small></span>"
        .($protected?"<span class='egp-tag-ok'><i class='fa-solid fa-shield-halved'></i> {egp_flow_protected}</span>":"")."</div>"
        ."</div>";
    return "<div class='egp-hero'><div class='egp-hero-l'>"
        ."<div class='egp-eyebrow'>{egp_eyebrow}</div>"
        ."<h2 class='egp-title'>{egp_hero_title}</h2>"
        ."<p class='egp-text'>{egp_hero_text}</p>"
        ."<div class='egp-chips'>".@implode("",$chips)."</div>"
        ."</div><div class='egp-hero-r'>$flow</div></div>";
}

// License / engine problems: shown in both states, they block or break the shield.
function egp_blockers(array $m):string{
    $h=array();
    $lic=$m["license"];
    if(!$lic["free"] && !$lic["ok"]){
        $h[]=egp_callout("warn","fa-solid fa-certificate","{egp_lic_title}",
            sprintf(egp_t("egp_lic_text"),egp_n($lic["sites"])),
            "<button type='button' class='egp-btn' OnClick=\"LoadAjax('MainContent','fw.license.php');\">{egp_view_license}</button>");
    }
    if(!$m["compatible"]){
        $h[]=egp_callout("warn","fa-solid fa-microchip","{egp_engine_title}",
            sprintf(egp_t("egp_engine_text"),htmlspecialchars($m["engine"]!==""?$m["engine"]:"?")),"");
    }
    return @implode("",$h);
}

function egp_callout(string $tone,string $ico,string $title,string $text,string $action):string{
    return "<div class='egp-callout egp-callout-$tone'><span class='egp-callout-ico'><i class='$ico'></i></span>"
        ."<div class='egp-callout-b'><b>$title</b><div>$text</div></div>".($action!==""?"<div>$action</div>":"")."</div>";
}

function egp_benefits():string{
    $cards=array(
        array("fa-solid fa-shield-halved","{egp_b1_title}","{egp_b1_text}"),
        array("fa-solid fa-sitemap","{egp_b2_title}","{egp_b2_text}"),
        array("fa-solid fa-list-check","{egp_b3_title}","{egp_b3_text}"),
    );
    $h="<div class='egp-sec-title'>{egp_why}</div><div class='egp-benefits'>";
    foreach($cards as $c){
        $h.="<div class='egp-benefit'><span class='egp-benefit-ico'><i class='{$c[0]}'></i></span><div class='egp-benefit-t'>{$c[1]}</div><div class='egp-benefit-x'>{$c[2]}</div></div>";
    }
    return $h."</div>";
}

function egp_setup(array $m):string{
    $page=CurrentPageName();
    $lic=$m["license"];
    $licOk=($lic["free"] || $lic["ok"]);
    $ready=($licOk && $m["compatible"]);
    $checks="<div class='egp-checks'><div class='egp-checks-t'>{egp_prereq}</div>";
    $checks.=egp_check($licOk,"{egp_check_license}",$lic["free"]?"{egp_check_license_free}":($lic["ok"]?"{egp_check_license_ok}":"{egp_check_license_ko}"));
    $eng=htmlspecialchars($m["engine"]!==""?$m["engine"]:"?");
    $checks.=egp_check($m["compatible"],"{egp_check_engine}",$m["compatible"]?"$eng &mdash; {egp_compatible}":"$eng &mdash; {egp_too_old}");
    $checks.="</div>";

    $btn="";
    if(egp_can_write()){
        $btn="<button type='button' class='egp-btn egp-btn-p'".($ready?"":" disabled")." OnClick=\"Loadjs('$page?link-js=yes');\"><i class='fa-solid fa-link'></i> {egp_link_btn}</button>";
        if(!$ready){ $btn.="<div class='egp-why-off'>{egp_link_blocked}</div>"; }
    }else{
        $btn="<div class='egp-why-off'>{egp_need_admin}</div>";
    }
    $steps=array(
        array("1","{egp_s1_title}","{egp_s1_text}","<a href='#' class='egp-link' OnClick=\"s_PopUp('https://wiki.articatech.com/en/reverse-proxy/edgeguard','1024','800');return false;\">{egp_s1_link} <i class='fa-solid fa-arrow-up-right-from-square'></i></a>"),
        array("2","{egp_s2_title}","{egp_s2_text}",""),
        array("3","{egp_s3_title}","{egp_s3_text}",$btn),
    );
    $h="<div class='egp-setup'><div class='egp-setup-head'><div><div class='egp-sec-title'>{egp_setup_title}</div><div class='egp-sec-sub'>{egp_setup_sub}</div></div></div>";
    $h.=$checks."<ol class='egp-steps'>";
    foreach($steps as $i=>$s){
        $cur=($i==2)?" aria-current='step'":"";
        $h.="<li class='egp-step".($i==2?" egp-step-cur":"")."'$cur><span class='egp-step-n'>{$s[0]}</span><div class='egp-step-b'><div class='egp-step-t'>{$s[1]}</div><div class='egp-step-x'>{$s[2]}</div>".($s[3]!==""?"<div class='egp-step-a'>{$s[3]}</div>":"")."</div></li>";
    }
    return $h."</ol></div>";
}

function egp_check(bool $ok,string $label,string $value):string{
    $ico=$ok?"fa-solid fa-circle-check":"fa-solid fa-triangle-exclamation";
    return "<div class='egp-check egp-check-".($ok?"ok":"ko")."'><i class='$ico'></i> <b>$label</b> <span>$value</span></div>";
}

function egp_attention(array $m):string{
    $page=CurrentPageName();
    $restart=egp_restart_js();
    $btn=egp_can_write()?"<button type='button' class='egp-btn' OnClick=\"$restart\"><i class='fa-solid fa-rotate'></i> {egp_restart}</button>":"";
    if($m["running"]===false){
        return egp_callout("warn","fa-solid fa-triangle-exclamation","{egp_att_stopped_title}","{egp_att_stopped_text}",$btn);
    }
    if(!$m["active"]){
        $st=htmlspecialchars((string)($m["agent"]["Status"] ?? ""));
        return egp_callout("warn","fa-solid fa-link-slash","{egp_att_link_title}",sprintf(egp_t("egp_att_link_text"),$st!==""?$st:"?"),$btn);
    }
    return "";
}

function egp_kpis(array $m):string{
    $win=$m["win"];
    $seg="<div class='egp-seg' role='radiogroup' aria-label='{egp_window}'>";
    foreach(array("24h"=>"{egp_w_24h}","7d"=>"{egp_w_7d}","30d"=>"{egp_w_30d}") as $k=>$lab){
        $on=($k===$win);
        $seg.="<button type='button' role='radio' aria-checked='".($on?"true":"false")."' class='egp-seg-b".($on?" egp-seg-on":"")."' OnClick=\"EgpWin('$k');\">$lab</button>";
    }
    $seg.="</div>";
    $winLab=array("24h"=>"{egp_last_24h}","7d"=>"{egp_last_7d}","30d"=>"{egp_last_30d}");
    $s=$m["sum"];
    $tiles=array();
    if(!is_array($s)){
        $na="<span class='egp-muted'>&mdash;</span>";
        $tiles[]=egp_kpi("fa-solid fa-ban","red","{egp_k_blocked}",$na,"{egp_journal_unavailable}");
        $tiles[]=egp_kpi("fa-solid fa-user-secret","amber","{egp_k_sources}",$na,"");
        $tiles[]=egp_kpi("fa-solid fa-globe","blue","{egp_k_sites}",$na,"");
    }else{
        $sites=is_array($s["top_sites"] ?? null)?$s["top_sites"]:array();
        $ns=count($sites);
        $most="";
        if($ns>0){ $most="{egp_most_targeted} <b>".htmlspecialchars(egp_site_name($sites[0]))."</b>"; }
        $tiles[]=egp_kpi("fa-solid fa-ban","red","{egp_k_blocked}",egp_n($s["total"] ?? 0),$winLab[$win]);
        $tiles[]=egp_kpi("fa-solid fa-user-secret","amber","{egp_k_sources}",egp_n($s["distinct_ips"] ?? 0),"{egp_k_sources_sub}");
        $tiles[]=egp_kpi("fa-solid fa-globe","blue","{egp_k_sites}",($ns>=10?"10+":egp_n($ns)),$most);
    }
    if($m["running"]===true){
        $sub=array();
        if($m["version"]!==""){ $sub[]="{egp_version} ".htmlspecialchars($m["version"]); }
        if($m["uptime"]!==""){ $sub[]="{egp_up} ".htmlspecialchars($m["uptime"]); }
        $tiles[]=egp_kpi("fa-solid fa-shield-halved","green","{egp_k_status}","<span class='egp-ok'><i class='fa-solid fa-circle-check'></i> {egp_running}</span>",@implode(" &middot; ",$sub));
    }elseif($m["running"]===false){
        $tiles[]=egp_kpi("fa-solid fa-shield-halved","red","{egp_k_status}","<span class='egp-bad'><i class='fa-solid fa-circle-xmark'></i> {egp_stopped}</span>","");
    }else{
        $tiles[]=egp_kpi("fa-solid fa-shield-halved","grey","{egp_k_status}","<span class='egp-muted'>&mdash;</span>","{egp_status_unknown}");
    }
    return "<div class='egp-metrics-head'><div><div class='egp-sec-title'>{egp_metrics_title}</div><div class='egp-sec-sub'>{egp_metrics_sub}</div></div>$seg</div>"
        ."<div class='egp-kpis'>".@implode("",$tiles)."</div>";
}

function egp_kpi(string $ico,string $tone,string $label,string $value,string $sub):string{
    return "<div class='egp-kpi'><div class='egp-kpi-top'><span class='egp-kpi-l'>$label</span><span class='egp-kpi-ico egp-ico-$tone'><i class='$ico'></i></span></div>"
        ."<div class='egp-kpi-v'>$value</div>".($sub!==""?"<div class='egp-kpi-s'>$sub</div>":"")."</div>";
}

function egp_site_name($s):string{
    if(!is_array($s)){ return ""; }
    $n=(string)($s["service_name"] ?? "");
    if($n!==""){ return $n; }
    $id=intval($s["serviceid"] ?? 0);
    return $id>0?"#$id":egp_t("egp_unknown_site");
}

function egp_analysis(array $m):string{
    $s=$m["sum"];
    if(!is_array($s)){
        return egp_callout("warn","fa-solid fa-database","{egp_journal_unavailable}",htmlspecialchars($m["sum_error"]),"");
    }
    if(intval($s["total"] ?? 0)==0){
        return "<div class='egp-calm'><span class='egp-calm-ico'><i class='fa-solid fa-shield-halved'></i></span><div><b>{egp_calm_title}</b><div>{egp_calm_text}</div></div></div>";
    }
    return "<div class='egp-two'>".egp_card_stopped($s).egp_card_targets($s)."</div>";
}

function egp_bar_rows(array $rows,string $tone):string{
    $max=0;
    foreach($rows as $r){ if($r[1]>$max){ $max=$r[1]; } }
    $h="";
    foreach($rows as $r){
        $w=($max>0)?max(2,round($r[1]*100/$max)):0;
        $h.="<div class='egp-bar-row'><div class='egp-bar-top'><span class='egp-bar-l'>{$r[0]}</span><span class='egp-bar-n'>".egp_n($r[1])."</span></div>"
            ."<div class='egp-bar'><span class='egp-bar-$tone' style='width:$w%'></span></div></div>";
    }
    return $h;
}

function egp_card_stopped(array $s):string{
    $sev=is_array($s["by_severity"] ?? null)?$s["by_severity"]:array();
    $levels=array("critical"=>array("{egp_sev_critical}","fa-solid fa-skull-crossbones","bad"),"high"=>array("{egp_sev_high}","fa-solid fa-circle-exclamation","bad"),
        "medium"=>array("{egp_sev_medium}","fa-solid fa-triangle-exclamation","warn"),"low"=>array("{egp_sev_low}","fa-solid fa-circle-info","muted"));
    $strip="<div class='egp-sev'>";
    foreach($levels as $k=>$l){
        $strip.="<div class='egp-sev-c egp-sev-{$l[2]}'><i class='{$l[1]}'></i><span class='egp-sev-l'>{$l[0]}</span><b>".egp_n($sev[$k] ?? 0)."</b></div>";
    }
    $strip.="</div>";
    $reasons=is_array($s["by_reason"] ?? null)?$s["by_reason"]:array();
    arsort($reasons);
    $rows=array();
    foreach(array_slice($reasons,0,6,true) as $code=>$n){ $rows[]=array(egp_reason_label((string)$code),intval($n)); }
    $bars=count($rows)?egp_bar_rows($rows,"red"):"<div class='egp-muted'>&mdash;</div>";
    return "<div class='egp-card'><div class='egp-card-head'><div><div class='egp-card-t'>{egp_stopped_title}</div><div class='egp-sec-sub'>{egp_stopped_sub}</div></div>"
        ."<a href='#' class='egp-link' OnClick=\"Loadjs('fw.nginx.edgeguard.php?js=yes');return false;\">{egp_see_journal} <i class='fa-solid fa-arrow-right'></i></a></div>"
        ."<div class='egp-mini-t'>{egp_by_severity}</div>$strip<div class='egp-mini-t'>{egp_top_reasons}</div>$bars</div>";
}

function egp_card_targets(array $s):string{
    $sites=is_array($s["top_sites"] ?? null)?$s["top_sites"]:array();
    $rows=array();
    foreach(array_slice($sites,0,5) as $x){ $rows[]=array(htmlspecialchars(egp_site_name($x)),intval($x["count"] ?? 0)); }
    $ips=is_array($s["top_ips"] ?? null)?$s["top_ips"]:array();
    $ipl="";
    foreach(array_slice($ips,0,5) as $x){
        $ipl.="<div class='egp-ip'><span class='egp-mono'>".htmlspecialchars((string)($x["ip"] ?? ""))."</span><span>".egp_n($x["count"] ?? 0)." {egp_requests}</span></div>";
    }
    return "<div class='egp-card'><div class='egp-card-head'><div><div class='egp-card-t'>{egp_targets_title}</div><div class='egp-sec-sub'>{egp_targets_sub}</div></div>"
        ."<a href='#' class='egp-link' OnClick=\"Loadjs('fw.nginx.edgeguard.php?js=yes');return false;\">{egp_see_journal} <i class='fa-solid fa-arrow-right'></i></a></div>"
        ."<div class='egp-mini-t'>{egp_k_sites}</div>".(count($rows)?egp_bar_rows($rows,"blue"):"<div class='egp-muted'>&mdash;</div>")
        ."<div class='egp-mini-t'>{egp_top_ips}</div>".($ipl!==""?$ipl:"<div class='egp-muted'>&mdash;</div>")."</div>";
}

function egp_link_card(array $m):string{
    $page=CurrentPageName();
    $a=$m["agent"];
    $id=htmlspecialchars((string)($a["AgentID"] ?? ""));
    $enr=egp_ts($a["EnrolledAt"] ?? "");
    $upd=egp_ts($a["UpdatedAt"] ?? "");
    $org=(isset($a["OrgID"]) && is_array($a["OrgID"]) && !empty($a["OrgID"]["Valid"]))?intval($a["OrgID"]["Int64"] ?? 0):0;
    $st=(string)($a["Status"] ?? "");
    $badge=$m["active"]?"<span class='egp-badge egp-badge-ok'><i class='fa-solid fa-circle-check'></i> {egp_active}</span>"
        :"<span class='egp-badge egp-badge-warn'><i class='fa-solid fa-triangle-exclamation'></i> ".htmlspecialchars($st!==""?$st:"?")."</span>";
    $rows=array();
    $rows[]=egp_row("fa-solid fa-server","{egp_r_console}","<span class='egp-mono'>".htmlspecialchars((string)($a["CentralURL"] ?? ""))."</span>","{egp_r_console_sub}");
    $rows[]=egp_row("fa-solid fa-shield-halved","{egp_r_name}",htmlspecialchars((string)($a["AgentName"] ?? "")),$org>0?"{egp_customer_space} #$org":"");
    $copy=$id!==""?" <button type='button' class='egp-icobtn' aria-label='{egp_copy_id}' title='{egp_copy_id}' OnClick=\"EgpCopy('$id',this);\"><i class='fa-solid fa-copy'></i></button>":"";
    $rows[]=egp_row("fa-solid fa-fingerprint","{egp_r_id}","<span class='egp-mono'>$id</span>$copy","");
    $rows[]=egp_row("fa-solid fa-calendar-check","{egp_r_since}","<span title='".($enr>0?date("Y-m-d H:i:s",$enr):"")."'>".egp_ago($enr)."</span>","");
    $rows[]=egp_row("fa-solid fa-rotate","{egp_r_sync}","<span title='".($upd>0?date("Y-m-d H:i:s",$upd):"")."'>".egp_ago($upd)."</span>","");
    $rows[]=egp_row("fa-solid fa-link","{egp_r_status}",$badge,"");
    $btns="";
    if(egp_can_write()){
        $btns="<button type='button' class='egp-btn' OnClick=\"".egp_restart_js()."\"><i class='fa-solid fa-rotate'></i> {egp_restart}</button>"
            ."<button type='button' class='egp-btn egp-btn-danger' OnClick=\"Loadjs('$page?unlink-js=yes');\"><i class='fa-solid fa-link-slash'></i> {egp_unlink}</button>";
    }
    return "<div class='egp-card egp-linkcard'><div class='egp-card-head'><div><div class='egp-card-t'>{egp_link_card_title}</div><div class='egp-sec-sub'>{egp_link_card_sub}</div></div><div class='egp-btns'>$btns</div></div>"
        ."<div class='egp-rows'>".@implode("",$rows)."</div></div>";
}

function egp_row(string $ico,string $label,string $value,string $sub):string{
    return "<div class='egp-row'><span class='egp-row-ico'><i class='$ico'></i></span><div class='egp-row-l'>$label</div>"
        ."<div class='egp-row-v'>$value".($sub!==""?"<div class='egp-row-s'>$sub</div>":"")."</div></div>";
}

function egp_restart_js():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->framework_buildjs("nginx:/edgeguard/restart","edgeguard.restart.progress","edgeguard.restart.progress.logs",
        "progress-edgeguard-restart","EgpReload();");
}

// ────────────────────────────────────────────────────────────────────────────
// Link / unlink
// ────────────────────────────────────────────────────────────────────────────

function egp_link_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!egp_can_write()){ return false; }
    return $tpl->js_dialog2("{egp_link_dialog_title}","$page?link-popup=yes",640);
}

function egp_link_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!egp_can_write()){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{egp_no_privs}")); return false; }
    $form=array();
    $form[]=$tpl->field_text("edgeguard_central_ip","{egp_f_central}","",true,"{egp_f_central_help}");
    $form[]=$tpl->field_numeric("edgeguard_central_port","{egp_f_port}","8082","{egp_f_port_help}");
    $form[]=$tpl->field_text("edgeguard_otp","{egp_f_otp}","",true,"{egp_f_otp_help}");
    $form[]=$tpl->field_button_upload("{egp_f_file}","file-uploaded",".edgeguard","{egp_f_file_help}");
    $callback="dialogInstance2.close();Loadjs('$page?callback=yes');";
    $html=array();
    $html[]="<div class='egp-dlg-intro'>{egp_link_dialog_intro}</div>";
    $html[]=$tpl->form_outside("",$form,null,"{egp_link_submit}",$callback,"AsSystemAdministrator");
    echo egp_style().$tpl->_ENGINE_parse_body($html);
    return true;
}

function egp_upload(){
    $tpl=new template_admin();
    if(!egp_can_write()){ echo $tpl->js_display_results("{egp_no_privs}",true); return; }
    $file=basename((string)($_GET["file-uploaded"] ?? ""));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("edgeguard_agent_enroll_file",$file);
    admin_tracks("EdgeGuard: enrollment file uploaded ($file)");
    echo $tpl->js_display_results("{egp_upload_ok}",false,"{upload_success}");
}

function int_or_default($value,$default,$min=null,$max=null){
    $value=(int)$value;
    if($min!==null && $value<$min){ return $default; }
    if($max!==null && $value>$max){ return $default; }
    return $value ?: $default;
}

function egp_link_save(){
    $tpl=new template_admin();
    if(!egp_can_write()){ echo "jserror:".egp_t("egp_no_privs"); return; }
    $tpl->CLEAN_POST();
    $central=trim((string)($_POST["edgeguard_central_ip"] ?? ""));
    if($central===""){ echo "jserror:".egp_t("egp_e_central_empty"); return; }
    if(strlen($central)<4){ echo "jserror:".egp_t("egp_e_central_short"); return; }
    if(intval($_POST["edgeguard_central_port"] ?? 0)==0){ echo "jserror:".egp_t("egp_e_port_zero"); return; }
    if(strlen((string)($_POST["edgeguard_otp"] ?? ""))<4){ echo "jserror:".egp_t("egp_e_otp_short"); return; }
    if(strlen((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("edgeguard_agent_enroll_file"))<4){ echo "jserror:".egp_t("egp_e_no_file"); return; }
    $port=int_or_default($_POST["edgeguard_central_port"] ?? 0,8082,1,65535);
    $_SESSION["EDGEGUARD_URL"]=array("https://$central:$port",(string)$_POST["edgeguard_otp"]);
    admin_tracks("EdgeGuard: link requested to https://$central:$port");
}

function egp_link_callback():bool{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    if(!egp_can_write()){ return false; }
    if(!isset($_SESSION["EDGEGUARD_URL"])){
        echo "alert(".json_encode(egp_t("egp_e_session")).");";
        return false;
    }
    list($url,$otp)=$_SESSION["EDGEGUARD_URL"];
    $url=urlencode($url);
    $otp=urlencode($otp);
    echo $tpl->framework_buildjs("nginx:/edgeguard/link/agent/$url/$otp","edgeguard.restart.progress","edgeguard.restart.progress.logs",
        "progress-edgeguard-restart","EgpReload();");
    unset($_SESSION["EDGEGUARD_URL"]);
    return true;
}

function egp_unlink_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!egp_can_write()){ return false; }
    return $tpl->js_dialog2("{egp_unlink_title}","$page?unlink-popup=yes",560);
}

function egp_unlink_popup():bool{
    $tpl=new template_admin();
    if(!egp_can_write()){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{egp_no_privs}")); return false; }
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/edgeguard/agent/info"),true);
    $a=(is_array($json) && !empty($json["sites"][0]) && is_array($json["sites"][0]))?$json["sites"][0]:array();
    $callback="dialogInstance2.close();";
    if(strtolower((string)($a["Status"] ?? ""))==="active"){
        $callback.=$tpl->framework_buildjs("nginx:/edgeguard/unlink/agent","edgeguard.restart.progress","edgeguard.restart.progress.logs",
            "progress-edgeguard-restart","EgpReload();");
    }else{
        $callback.="EgpReload();";
    }
    $html=array();
    $html[]="<div class='egp-unlink'><span class='egp-unlink-ico'><i class='fa-solid fa-link-slash'></i></span><div>";
    $html[]="<div class='egp-unlink-t'>{egp_unlink_title}</div><div class='egp-unlink-x'>{egp_unlink_text}</div>";
    $html[]="<div class='egp-unlink-r'><b>{egp_r_name}:</b> ".htmlspecialchars((string)($a["AgentName"] ?? ""))."</div>";
    $html[]="<div class='egp-unlink-r'><b>{egp_r_console}:</b> <span class='egp-mono'>".htmlspecialchars((string)($a["CentralURL"] ?? ""))."</span></div>";
    $html[]="</div></div>";
    $form=array($tpl->field_hidden("edgeguard_unlink","1"));
    $html[]=$tpl->form_outside("",$form,null,"{egp_unlink}",$callback,"AsSystemAdministrator");
    echo egp_style().$tpl->_ENGINE_parse_body($html);
    return true;
}

function egp_unlink_save(){
    if(!egp_can_write()){ echo egp_t("egp_no_privs"); return; }
    admin_tracks("EdgeGuard: unlink from the central console requested");
}

// ────────────────────────────────────────────────────────────────────────────
// JS / CSS
// ────────────────────────────────────────────────────────────────────────────

function egp_js(string $win):string{
    $page=CurrentPageName();
    return "<script>
window.EgpWinV=".json_encode($win).";
function EgpReload(){ LoadAjaxSilent('egp-host','$page?content=yes&win='+encodeURIComponent(window.EgpWinV||'7d')); }
function EgpWin(w){ window.EgpWinV=w; EgpReload(); }
function EgpCopy(txt,btn){
  var done=function(){ if(btn){ btn.classList.add('egp-copied'); setTimeout(function(){btn.classList.remove('egp-copied');},1200); } };
  if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(txt).then(done,function(){}); return; }
  var t=document.createElement('textarea'); t.value=txt; t.style.position='fixed'; t.style.opacity='0';
  document.body.appendChild(t); t.select(); try{ document.execCommand('copy'); done(); }catch(e){} document.body.removeChild(t);
}
if(window.EgpTimer){ clearInterval(window.EgpTimer); }
window.EgpTimer=setInterval(function(){
  if(!document.getElementById('egp-host')){ clearInterval(window.EgpTimer); window.EgpTimer=null; return; }
  if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible')){ return; }
  EgpReload();
},60000);
</script>";
}

function egp_style():string{
    if(!empty($GLOBALS["EGP_STYLE_DONE"])){ return ""; }
    $GLOBALS["EGP_STYLE_DONE"]=true;
    $acc="#10b981";$soft="rgba(16,185,129,.12)";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$acc=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
    }
    return "<style>
.egp{color:#0f172a;font-size:14px;margin-top:10px}
.egp *{box-sizing:border-box}
.egp-hero{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(0,1fr);gap:32px;align-items:center;background:linear-gradient(135deg,$soft 0%,#ffffff 55%);border:1px solid #e2e8f0;border-radius:16px;padding:32px;box-shadow:0 1px 2px rgba(15,23,42,.06);margin-bottom:20px}
@media (max-width:1200px){.egp-hero{grid-template-columns:minmax(0,1fr)}}
.egp-eyebrow{display:inline-block;font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:$dark;background:$soft;border-radius:6px;padding:4px 10px;margin-bottom:14px}
.egp-title{font-size:30px;font-weight:700;line-height:1.2;margin:0 0 12px;color:#0f172a}
.egp-text{font-size:16px;line-height:1.6;color:#475569;margin:0 0 18px;max-width:720px}
.egp-chips{display:flex;flex-wrap:wrap;gap:8px}
.egp-chip{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #e2e8f0;border-radius:999px;padding:6px 14px;font-size:13px;color:#334155}
.egp-chip svg,.egp-chip i{color:$dark}
.egp-flow{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px;box-shadow:0 4px 16px rgba(15,23,42,.06)}
.egp-flow-head{display:flex;justify-content:space-between;align-items:center;font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#64748b;margin-bottom:12px;gap:8px}
.egp-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:4px 12px;font-size:13px;font-weight:700;text-transform:none;letter-spacing:0}
.egp-pill-ok{background:#ecfdf5;color:#15803d;border:1px solid #bbf7d0}.egp-pill-warn{background:#fffbeb;color:#b45309;border:1px solid #fde68a}.egp-pill-muted{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0}
.egp-node{display:flex;align-items:center;gap:12px;border:1px solid #e2e8f0;background:#f8fafc;border-radius:10px;padding:10px 12px}
.egp-node b{display:block;font-size:14px}.egp-node small{display:block;font-size:13px;color:#64748b}
.egp-node-ico{flex:0 0 36px;height:36px;border-radius:9px;background:#e2e8f0;color:#475569;display:flex;align-items:center;justify-content:center}
.egp-ico-blue{background:#dbeafe;color:#1d4ed8}
.egp-node-shield{border:2px solid #cbd5e1;background:#fff}
.egp-node-on{border-color:$acc;background:$soft}
.egp-node-on .egp-node-ico{background:$acc;color:#fff}
.egp-tag-ok{margin-left:auto;font-size:13px;font-weight:600;color:#15803d;background:#ecfdf5;border:1px solid #bbf7d0;border-radius:6px;padding:2px 8px;white-space:nowrap}
.egp-arrows{display:flex;justify-content:space-around;align-items:center;padding:8px 0;font-size:13px}
.egp-arrows-2{justify-content:center}
.egp-arr-ok{color:#15803d}.egp-arr-bad{color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:2px 8px;font-weight:600}
.egp-callout{display:flex;gap:14px;align-items:center;border-radius:12px;padding:14px 18px;margin-bottom:16px;border:1px solid}
.egp-callout-warn{background:#fffbeb;border-color:#fde68a;color:#78350f}
.egp-callout-ico{flex:0 0 36px;height:36px;border-radius:9px;background:#fef3c7;color:#d97706;display:flex;align-items:center;justify-content:center}
.egp-callout-b{flex:1 1 auto}.egp-callout-b b{font-size:15px}
.egp-sec-title{font-size:18px;font-weight:600;margin-bottom:4px}
.egp-sec-sub{font-size:13px;color:#64748b}
.egp-benefits{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px;margin:12px 0 24px}
@media (max-width:1200px){.egp-benefits{grid-template-columns:repeat(2,minmax(0,1fr))}}
.egp-benefit{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:22px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.egp-benefit-ico{width:44px;height:44px;border-radius:11px;background:$soft;color:$dark;display:flex;align-items:center;justify-content:center;font-size:19px;margin-bottom:14px}
.egp-benefit-t{font-size:16px;font-weight:600;margin-bottom:6px}
.egp-benefit-x{font-size:14px;line-height:1.55;color:#475569}
.egp-setup{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:22px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.egp-checks{display:flex;flex-wrap:wrap;gap:12px 28px;align-items:center;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 16px;margin:14px 0 18px}
.egp-checks-t{font-size:13px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em}
.egp-check{font-size:14px}.egp-check span{color:#475569}
.egp-check-ok svg,.egp-check-ok i{color:#16a34a}.egp-check-ko svg,.egp-check-ko i{color:#d97706}
.egp-steps{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}
@media (max-width:1200px){.egp-steps{grid-template-columns:minmax(0,1fr)}}
.egp-step{display:flex;gap:14px;border:1px solid #e2e8f0;border-radius:12px;padding:18px}
.egp-step-cur{border-color:$acc;box-shadow:0 0 0 3px $soft}
.egp-step-n{flex:0 0 32px;height:32px;border-radius:50%;background:#f1f5f9;color:#475569;font-weight:700;display:flex;align-items:center;justify-content:center}
.egp-step-cur .egp-step-n{background:$acc;color:#fff}
.egp-step-t{font-weight:600;font-size:15px;margin-bottom:4px}
.egp-step-x{font-size:13px;color:#64748b;line-height:1.5}
.egp-step-a{margin-top:12px}
.egp-why-off{font-size:13px;color:#b45309;margin-top:6px}
.egp-btn{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 16px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;font-size:14px;color:#0f172a;cursor:pointer;white-space:nowrap}
.egp-btn:hover{background:#f8fafc}
.egp-btn:focus-visible,.egp-seg-b:focus-visible,.egp-icobtn:focus-visible{outline:2px solid $acc;outline-offset:2px}
.egp-btn-p{background:$acc;border-color:$acc;color:#fff;font-weight:600}
.egp-btn-p:hover{background:$dark}
.egp-btn-p[disabled]{opacity:.5;cursor:not-allowed}
.egp-btn-danger{color:#b91c1c;border-color:#fecaca}
.egp-btn-danger:hover{background:#fef2f2}
.egp-link{font-size:13px;font-weight:600;color:#2563eb;white-space:nowrap}
.egp-metrics-head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin:6px 0 12px}
.egp-seg{display:inline-flex;background:#f1f5f9;border-radius:8px;padding:3px}
.egp-seg-b{border:0;background:transparent;padding:7px 14px;border-radius:6px;font-size:13px;color:#475569;cursor:pointer}
.egp-seg-on{background:#fff;color:#0f172a;font-weight:600;box-shadow:0 1px 2px rgba(15,23,42,.12)}
.egp-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px;margin-bottom:20px}
@media (max-width:1200px){.egp-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
.egp-kpi{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.egp-kpi-top{display:flex;justify-content:space-between;align-items:center}
.egp-kpi-l{font-size:13px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em}
.egp-kpi-ico{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center}
.egp-ico-red{background:#fef2f2;color:#dc2626}.egp-ico-amber{background:#fffbeb;color:#d97706}.egp-ico-green{background:#ecfdf5;color:#16a34a}.egp-ico-grey{background:#f1f5f9;color:#64748b}
.egp-kpi-v{font-size:32px;font-weight:700;margin-top:6px;font-variant-numeric:tabular-nums;line-height:1.15}
.egp-kpi-v .egp-ok,.egp-kpi-v .egp-bad,.egp-kpi-v .egp-muted{font-size:22px}
.egp-kpi-s{font-size:13px;color:#64748b;margin-top:4px}
.egp-ok{color:#15803d}.egp-bad{color:#b91c1c}.egp-muted{color:#94a3b8}
.egp-two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;margin-bottom:20px}
@media (max-width:1200px){.egp-two{grid-template-columns:minmax(0,1fr)}}
.egp-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.egp-card-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:12px;flex-wrap:wrap}
.egp-card-t{font-size:16px;font-weight:600}
.egp-mini-t{font-size:13px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin:14px 0 8px}
.egp-sev{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}
.egp-sev-c{display:flex;flex-direction:column;gap:2px;border-radius:8px;padding:8px 10px;border:1px solid}
.egp-sev-c b{font-size:18px;font-variant-numeric:tabular-nums}
.egp-sev-l{font-size:13px}
.egp-sev-bad{background:#fef2f2;border-color:#fecaca;color:#991b1b}.egp-sev-warn{background:#fffbeb;border-color:#fde68a;color:#92400e}.egp-sev-muted{background:#f8fafc;border-color:#e2e8f0;color:#475569}
.egp-bar-row{margin-bottom:10px}
.egp-bar-top{display:flex;justify-content:space-between;font-size:14px;margin-bottom:4px}
.egp-bar-n{font-weight:600;font-variant-numeric:tabular-nums}
.egp-bar{height:8px;border-radius:999px;background:#f1f5f9;overflow:hidden}
.egp-bar>span{display:block;height:100%;border-radius:999px}
.egp-bar-red{background:#ef4444}.egp-bar-blue{background:#3b82f6}
.egp-ip{display:flex;justify-content:space-between;font-size:14px;padding:6px 0;border-top:1px solid #f1f5f9}
.egp-mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px}
.egp-calm{display:flex;gap:14px;align-items:center;background:#ecfdf5;border:1px solid #bbf7d0;border-radius:12px;padding:16px 18px;margin-bottom:20px;color:#14532d}
.egp-calm-ico{flex:0 0 40px;height:40px;border-radius:10px;background:#16a34a;color:#fff;display:flex;align-items:center;justify-content:center}
.egp-linkcard .egp-btns{display:flex;gap:8px}
.egp-rows{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 28px}
@media (max-width:1200px){.egp-rows{grid-template-columns:minmax(0,1fr)}}
.egp-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-top:1px solid #f1f5f9}
.egp-row-ico{flex:0 0 32px;height:32px;border-radius:8px;background:#f1f5f9;color:#475569;display:flex;align-items:center;justify-content:center}
.egp-row-l{flex:0 0 140px;font-size:13px;font-weight:600;color:#64748b}
.egp-row-v{flex:1 1 auto;min-width:0;font-size:14px;overflow-wrap:anywhere}
.egp-row-s{font-size:13px;color:#64748b}
.egp-badge{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:13px;font-weight:600}
.egp-badge-ok{background:#ecfdf5;color:#15803d;border:1px solid #bbf7d0}.egp-badge-warn{background:#fffbeb;color:#b45309;border:1px solid #fde68a}
.egp-icobtn{border:0;background:transparent;color:#94a3b8;cursor:pointer;padding:3px 6px;border-radius:6px}
.egp-icobtn:hover{color:#334155;background:#f1f5f9}
.egp-copied{color:#16a34a!important}
.egp-dlg-intro{font-size:14px;color:#475569;margin:0 0 14px;line-height:1.55}
.egp-unlink{display:flex;gap:14px;margin-bottom:14px}
.egp-unlink-ico{flex:0 0 44px;height:44px;border-radius:11px;background:#fef2f2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:18px}
.egp-unlink-t{font-size:17px;font-weight:600;margin-bottom:6px}
.egp-unlink-x{font-size:14px;color:#475569;line-height:1.55;margin-bottom:10px}
.egp-unlink-r{font-size:14px;margin-top:4px}
@media (prefers-reduced-motion:no-preference){.egp-kpi,.egp-card,.egp-benefit{animation:egpIn .2s ease-out}@keyframes egpIn{from{opacity:0}to{opacity:1}}}
</style>";
}
