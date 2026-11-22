<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
// awc_table_style() / awc_pager_js(): pager partage des tableaux d'entites (include inconditionnel).
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(!ifisright()){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// ---- dispatch (AVANT page()) ----
if(isset($_GET["build-page"])){dnsq_build();exit;}
if(isset($_GET["collector"])){dnsq_collector_host();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["zoom"])){zoom();exit;}
if(isset($_GET["zoom-answer-js"])){zoom_answer_js();exit;}
if(isset($_GET["zoom-answer-popup"])){zoom_answer_popup();exit;}
page();

function ifisright(){
    $users=new usersMenus();
    if($users->AsProxyMonitor){return true;}
    if($users->AsWebStatisticsAdministrator){return true;}
    if($users->AsDnsAdministrator){return true;}
    if($users->AsFirewallManager){return true;}
    return false;
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    // Onglet de fw.proxy.relatime.php : ni en-tete ni TINY (ils remplaceraient ceux de la page parente).
    if(isset($_GET["notitle"])){
        echo "<div id='dnsq-main'></div><script>LoadAjax('dnsq-main','$page?build-page=yes&notitle=yes');</script>";
        return;
    }
    $html=$tpl->page_header("{DNS_QUERIES}",ico_eye,"{dnsq_header_explain}","$page?build-page=yes","dns-queries",null,false,"dnsq-main");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

/* ============================================================================================
 * Maquette stitch_dns_queries_log (2026-11-19). Donnees : GET /dns/collector/events/{max}/{search}
 * (lignes JSON de /var/log/dns-queries.log, plus recentes d'abord : ts, ip, port, proto, qname,
 * qtype, rcode, msg_type, identity, latency, category_id, response_ip, answer_ips, edns_options_data)
 * et GET /dnstap/metrics/status (running, uptime, log_loopback, internal{...}). Les compteurs par
 * resultat ne portent que sur les lignes affichees (« in these N queries »).
 * Ecarte de la maquette car invente ou absent : rien de chiffre au-dela de ces deux sources.
 * ============================================================================================ */

// Acces demon : hors de la tranche des widgets (le harnais les remplace).
function dnsq_api_status(){
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnstap/metrics/status"),true);
    if(!is_array($json) || empty($json["success"]) || !isset($json["data"]) || !is_array($json["data"])){return null;}
    return $json["data"];
}
function dnsq_api_events(int $max,string $search){
    return json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dns/collector/events/$max/".rawurlencode($search)));
}
function dnsq_category_name(int $category):array{
    if(isset($GLOBALS["DNSQ_CATZ"][$category])){return $GLOBALS["DNSQ_CATZ"][$category];}
    $out=array("name"=>"","desc"=>"");
    if($category>0){
        if(!isset($GLOBALS["DNSQ_CATDESC"])){
            $catz=new mysql_catz(true);
            $GLOBALS["DNSQ_CATDESC"]=$catz->categories_descriptions();
        }
        $d=$GLOBALS["DNSQ_CATDESC"];
        if(isset($d[$category]["categoryname"])){
            $out=array("name"=>(string)$d[$category]["categoryname"],"desc"=>(string)($d[$category]["category_description"] ?? ""));
        }
    }
    $GLOBALS["DNSQ_CATZ"][$category]=$out;
    return $out;
}

// ---- DNSQ widgets begin ----
function dnsq_h($v):string{
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),req_html((string)$v));
}
function dnsq_t(string $token):string{
    if(!isset($GLOBALS["DNSQ_TPL"])){$GLOBALS["DNSQ_TPL"]=new template_admin();}
    $t=$GLOBALS["DNSQ_TPL"]->_ENGINE_parse_body("{".$token."}");
    $args=func_get_args(); array_shift($args);
    foreach($args as $a){ $p=strpos($t,"%s"); if($p===false){break;} $t=substr_replace($t,(string)$a,$p,2); }
    return $t;
}
function dnsq_num($n):string{
    return number_format((float)$n,0,"."," ");
}
function dnsq_ago(int $t):string{
    if($t<=0){return "—";}
    $d=time()-$t; if($d<0){$d=0;}
    if($d<60){return dnsq_t("dnsq_ago_s",$d);}
    if($d<3600){return dnsq_t("dnsq_ago_min",intval($d/60));}
    if($d<86400){return dnsq_t("dnsq_ago_h",intval($d/3600));}
    return dnsq_t("dnsq_ago_d",intval($d/86400));
}
// Duree Go ("76h3m12.5s") -> "3d 4h".
function dnsq_uptime(string $s):string{
    $sec=0;
    if(preg_match_all('/([0-9.]+)(h|ms|m|s)/',$s,$m,PREG_SET_ORDER)){
        foreach($m as $x){
            $v=floatval($x[1]);
            if($x[2]=="h"){$sec+=$v*3600;}elseif($x[2]=="m"){$sec+=$v*60;}elseif($x[2]=="s"){$sec+=$v;}
        }
    }
    $sec=intval($sec);
    if($sec<=0){return "";}
    $d=intval($sec/86400);$h=intval(($sec%86400)/3600);$mi=intval(($sec%3600)/60);
    if($d>0){return "{$d}d {$h}h";}
    if($h>0){return "{$h}h {$mi}m";}
    return "{$mi}m";
}
// Code de resultat DNS -> ton, icone, mot courant (le code technique reste en sous-titre).
function dnsq_rcode(string $rcode):array{
    $r=strtoupper(trim($rcode));
    $map=array("NOERROR"=>array("ok","fa-solid fa-circle-check","{dnsq_r_found}"),
        "NXDOMAIN"=>array("warn","fa-solid fa-circle-question","{dnsq_r_nx}"),
        "REFUSED"=>array("err","fa-solid fa-ban","{dnsq_r_refused}"),
        "SERVFAIL"=>array("err","fa-solid fa-triangle-exclamation","{dnsq_r_servfail}"),
        "FORMERR"=>array("warn","fa-solid fa-circle-exclamation","{dnsq_r_formerr}"));
    if(isset($map[$r])){return array("code"=>$r,"tone"=>$map[$r][0],"ico"=>$map[$r][1],"label"=>$map[$r][2]);}
    return array("code"=>$r,"tone"=>"off","ico"=>"fa-solid fa-circle-info","label"=>$r===""?"—":dnsq_h($r));
}
function dnsq_qtype_label(string $t):string{
    $map=array("A"=>"{dnsq_t_a}","AAAA"=>"{dnsq_t_aaaa}","MX"=>"{dnsq_t_mx}","PTR"=>"{dnsq_t_ptr}","HTTPS"=>"{dnsq_t_https}",
        "TXT"=>"{dnsq_t_txt}","CNAME"=>"{dnsq_t_cname}","NS"=>"{dnsq_t_ns}","SRV"=>"{dnsq_t_srv}","SOA"=>"{dnsq_t_soa}","SVCB"=>"{dnsq_t_https}");
    $u=strtoupper(trim($t));
    return isset($map[$u])?$map[$u]:"";
}
function dnsq_status_model($st):array{
    $M=array("known"=>false,"running"=>false,"uptime"=>"","loopback"=>false,"processed"=>null,"skipped"=>null,"not_logged"=>null,"log_lines"=>null,"log_errors"=>null,"parse_errors"=>null);
    if(!is_array($st)){return $M;}
    $M["known"]=true;
    $M["running"]=!empty($st["running"]);
    $M["uptime"]=dnsq_uptime((string)($st["uptime"] ?? ""));
    $M["loopback"]=!empty($st["log_loopback"]);
    $in=(isset($st["internal"]) && is_array($st["internal"]))?$st["internal"]:array();
    foreach(array("processed"=>"events_processed","skipped"=>"events_skipped","not_logged"=>"events_not_logged","log_lines"=>"log_lines","log_errors"=>"log_errors","parse_errors"=>"parse_errors") as $k=>$src){
        if(isset($in[$src])){$M[$k]=intval($in[$src]);}
    }
    return $M;
}
// Une ligne JSON du journal -> ce que montrent la table, le resume et la fenetre de detail.
function dnsq_item(array $d):array{
    $answers=array();
    if(isset($d["answer_ips"]) && is_array($d["answer_ips"])){
        foreach($d["answer_ips"] as $a){ $a=trim((string)$a); if($a!==""){$answers[]=$a;} }
    }
    $resp=trim((string)($d["response_ip"] ?? ""));
    if($resp==="0.0.0.0"){$resp="";}
    $identity=trim((string)($d["identity"] ?? ""));
    $short=explode(".",$identity);
    $lat=trim((string)($d["latency"] ?? ""));
    if($lat==="-"){$lat="";}
    return array("ts"=>strtotime((string)($d["ts"] ?? ""))?:0,"ip"=>trim((string)($d["ip"] ?? "")),"port"=>intval($d["port"] ?? 0),
        "proto"=>trim((string)($d["proto"] ?? "")),"qname"=>trim((string)($d["qname"] ?? "")),"qtype"=>strtoupper(trim((string)($d["qtype"] ?? ""))),
        "rcode"=>strtoupper(trim((string)($d["rcode"] ?? ""))),"msg_type"=>trim((string)($d["msg_type"] ?? "")),
        "identity"=>$identity,"server"=>(string)$short[0],"upstream"=>$resp,"answers"=>$answers,"latency"=>$lat,
        "category"=>intval($d["category_id"] ?? 0),"edns"=>trim((string)($d["edns_options_data"] ?? "")));
}
function dnsq_events_model(string $raw):array{
    $MAIN=(new template_admin())->format_search_protocol($raw);
    $max=intval($MAIN["MAX"] ?? 50); if($max<1){$max=50;}
    $term=trim((string)($MAIN["TERM"] ?? ""));
    $M=array("error"=>"","max"=>$max,"term"=>$term,"short"=>(strlen($term)>0 && strlen($term)<3),"rows"=>array(),
        "rcodes"=>array(),"devices"=>0,"names"=>0);
    $json=dnsq_api_events($max,strlen($term)<3?"NONE":$term);
    if(!is_object($json)){ $M["error"]="{dnsq_daemon_unreachable}"; return $M; }
    if(empty($json->Status)){ $M["error"]=dnsq_h((string)($json->Error ?? "")); return $M; }
    $events=(isset($json->Events) && is_array($json->Events))?$json->Events:array();
    $dev=array();$names=array();
    foreach($events as $line){
        $line=trim((string)$line);
        if($line===""){continue;}
        $d=json_decode($line,true);
        if(!is_array($d) || !isset($d["qname"])){continue;}
        $it=dnsq_item($d);
        $it["raw"]=$line;
        $M["rows"][]=$it;
        $rc=$it["rcode"]===""?"-":$it["rcode"];
        if(!isset($M["rcodes"][$rc])){$M["rcodes"][$rc]=0;}
        $M["rcodes"][$rc]++;
        if($it["ip"]!==""){$dev[$it["ip"]]=true;}
        if($it["qname"]!==""){$names[strtolower($it["qname"])]=true;}
    }
    arsort($M["rcodes"]);
    $M["devices"]=count($dev);
    $M["names"]=count($names);
    return $M;
}

function dnsq_style():string{
    if(isset($GLOBALS["DNSQ_CSS"])){return "";}
    $GLOBALS["DNSQ_CSS"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$dark="#047857";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
    }
    return "<style>
.dnsq{color:#0f172a;font-size:13px;margin-top:10px}
.dnsq *{box-sizing:border-box}
.dnsq-banner{border-radius:8px;border-left:4px solid $accent;background:$soft;padding:16px 20px;margin-bottom:16px}
.dnsq-banner-t{font-size:15px;font-weight:600;margin-bottom:4px}
.dnsq-banner-p{color:#3d4947;line-height:19px}
.dnsq-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.dnsq-chip{display:inline-flex;align-items:center;gap:7px;padding:4px 12px;border-radius:999px;background:#fff;border:1px solid #cbd5e1;font-size:13px}
.dnsq-chip svg,.dnsq-chip i{color:$dark}
.dnsq-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06);padding:16px 20px;margin-bottom:16px}
.dnsq-strip{display:flex;flex-wrap:wrap;align-items:center;gap:10px 22px}
.dnsq-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;font-size:13px;font-weight:600;white-space:nowrap;border:1px solid transparent}
.dnsq-t-ok{background:$soft;color:$dark;border-color:rgba(16,185,129,.35)}
.dnsq-t-warn{background:#ffddb9;color:#865300}
.dnsq-t-err{background:#ffdad6;color:#ba1a1a}
.dnsq-t-off{background:#e3f0f8;color:#3d4947}
.dnsq-mut{color:#64748b}
.dnsq-kv{white-space:nowrap}
.dnsq-kv b{font-family:monospace;font-size:13px;color:#0f172a}
.dnsq-kv.dnsq-bad,.dnsq-kv.dnsq-bad b{color:#865300}
.dnsq-tag{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:6px;background:#e3f0f8;color:#3d4947;font-size:13px}
.dnsq-grow{flex:1}
.dnsq-lbl{display:block;font-weight:600;margin-bottom:8px}
.dnsq-search{position:relative}
.dnsq-search i,.dnsq-search svg{position:absolute;left:16px;top:16px;color:#64748b}
.dnsq-search input{width:100%;height:48px;border:1px solid #cbd5e1;border-radius:8px;padding:0 120px 0 44px;font-size:14px;background:#fff}
.dnsq-search input:focus{outline:2px solid $accent;outline-offset:0;border-color:$accent}
.dnsq-go{position:absolute;right:6px;top:6px;height:36px;border:0;border-radius:6px;background:$dark;color:#fff;font-weight:600;padding:0 16px;cursor:pointer}
.dnsq-ex{display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin-top:10px}
.dnsq-ex button{font-family:monospace;font-size:13px;border:1px solid #e2e8f0;background:#f8fafc;border-radius:6px;padding:3px 9px;cursor:pointer;color:#0f172a}
.dnsq-ex button:hover{border-color:$accent;color:$dark}
.dnsq-help{color:#64748b;margin-top:8px;line-height:18px}
.dnsq-sum{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin:0 2px 12px}
.dnsq-sum-l{margin-right:8px}
.dnsq-tbl{width:100%;border-collapse:collapse}
.dnsq-tbl th{text-align:left;font-size:13px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em;padding:12px 14px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.dnsq-tbl td{padding:12px 14px;border-top:1px solid #f1f5f9;vertical-align:top}
.dnsq-tbl tbody tr:hover td{background:#f8fafc}
.dnsq-mono{font-family:monospace;font-size:13px}
.dnsq-sub{display:block;font-size:13px;color:#64748b;margin-top:2px}
.dnsq-name{font-size:14px;font-weight:600;word-break:break-all}
.dnsq-qt{display:inline-block;font-family:monospace;font-size:13px;background:#f1f5f9;border-radius:4px;padding:0 5px;margin-right:4px;color:#3d4947}
.dnsq-code{display:block;font-family:monospace;font-size:13px;color:#64748b;margin-top:3px}
.dnsq-more{border:0;background:none;color:$dark;font-weight:600;cursor:pointer;padding:0 0 0 4px;font-size:13px}
.dnsq-act{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;color:#3d4947;cursor:pointer}
.dnsq-act:hover{background:#e3f0f8}
.dnsq-act:focus-visible,.dnsq-more:focus-visible,.dnsq-ex button:focus-visible,.dnsq-go:focus-visible{outline:2px solid $accent;outline-offset:2px}
.dnsq-r{text-align:right;white-space:nowrap}
.dnsq-nw{white-space:nowrap}
.dnsq-cat a{color:#3d4947}
.dnsq-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 14px;border-top:1px solid #e2e8f0;color:#64748b}
.dnsq-pg{display:flex;gap:4px}
.dnsq-pg a{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border:1px solid #cbd5e1;border-radius:4px;background:#fff;color:#0f172a;text-decoration:none;font-size:13px;font-weight:600}
.dnsq-pg a.awc-pg-cur{background:$dark;border-color:$dark;color:#fff}
.dnsq-pg a.awc-pg-off{color:#cbd5e1;cursor:default}
.dnsq-empty{text-align:center;padding:36px 20px}
.dnsq-empty-ico{width:60px;height:60px;border-radius:50%;background:$soft;color:$dark;display:inline-flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:10px}
.dnsq-empty-ico.dnsq-e-err{background:#ffdad6;color:#ba1a1a}
.dnsq-empty-t{font-size:16px;font-weight:600;margin-bottom:6px}
.dnsq-empty-p{color:#3d4947;line-height:20px;max-width:620px;margin:0 auto}
.dnsq-link{border:0;background:none;color:$dark;font-weight:600;cursor:pointer;margin-top:10px}
.dnsq-sec{margin-bottom:14px}
.dnsq-sec-t{font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#64748b;margin-bottom:6px}
.dnsq-dl{display:grid;grid-template-columns:180px 1fr;gap:6px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px}
.dnsq-dl>div:nth-child(odd){color:#64748b}
.dnsq-dl>div:nth-child(even){word-break:break-all}
.dnsq-ips{display:flex;flex-wrap:wrap;gap:6px}
.dnsq-ips span{font-family:monospace;font-size:13px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:2px 8px}
.dnsq details summary{cursor:pointer;color:$dark;font-weight:600;margin-top:4px}
@media (max-width:1200px){.dnsq-c-srv,.dnsq-c-cat{display:none}}
@media (prefers-reduced-motion:no-preference){.dnsq-tbl tbody tr{animation:dnsqIn .15s ease-out}@keyframes dnsqIn{from{opacity:.3}to{opacity:1}}}
</style>";
}
function dnsq_js(string $page):string{
    return "<script>
function DnsqSearch(){var e=document.getElementById('dnsq-q');var s=e?e.value:'';LoadAjax('dnsq-results','$page?search='+encodeURIComponent(s));}
function DnsqEx(v){var e=document.getElementById('dnsq-q');if(!e){return;}e.value=v;DnsqSearch();}
function DnsqClear(){var e=document.getElementById('dnsq-q');if(e){e.value='';}DnsqSearch();}
</script>";
}

function dnsq_banner():string{
    return "<div class='dnsq-banner'><div class='dnsq-banner-t'>{dnsq_banner_title}</div><div class='dnsq-banner-p'>{dnsq_banner_text}</div>"
        ."<div class='dnsq-chips'><span class='dnsq-chip'><i class='fa-solid fa-magnifying-glass' aria-hidden='true'></i>{dnsq_chip_search}</span>"
        ."<span class='dnsq-chip'><i class='fa-solid fa-lock' aria-hidden='true'></i>{dnsq_chip_readonly}</span>"
        ."<span class='dnsq-chip'><i class='fa-solid fa-clock-rotate-left' aria-hidden='true'></i>{dnsq_chip_newest}</span></div></div>";
}
// Bande d'etat du collecteur : rafraichie seule (?collector=yes).
function dnsq_collector(array $S):string{
    if(!$S["known"]){
        return "<div class='dnsq-strip'><span class='dnsq-pill dnsq-t-off'><i class='fa-solid fa-circle-question' aria-hidden='true'></i>{dnsq_col_unknown}</span><span class='dnsq-mut'>{dnsq_col_unknown_help}</span></div>";
    }
    $h=array();
    if($S["running"]){
        $h[]="<span class='dnsq-pill dnsq-t-ok' role='status'><i class='fa-solid fa-circle-check' aria-hidden='true'></i>{dnsq_col_running}</span>";
        if($S["uptime"]!==""){$h[]="<span class='dnsq-mut'>".dnsq_t("dnsq_col_for",$S["uptime"])."</span>";}
    }else{
        $h[]="<span class='dnsq-pill dnsq-t-err' role='status'><i class='fa-solid fa-circle-xmark' aria-hidden='true'></i>{dnsq_col_stopped}</span>";
    }
    $kv=function($label,$v,$warn=false,$help=""){
        if($v===null){return "";}
        $bad=$warn && $v>0;
        return "<span class='dnsq-kv".($bad?" dnsq-bad":"")."'".($help!==""?" title=\"$help\"":"").">".($bad?"<i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i> ":"")."$label: <b>".dnsq_num($v)."</b></span>";
    };
    $h[]=$kv("{dnsq_col_lines}",$S["log_lines"]);
    $h[]=$kv("{dnsq_col_notlogged}",$S["not_logged"],false,"{dnsq_col_notlogged_help}");
    $h[]=$kv("{dnsq_col_skipped}",$S["skipped"],true,"{dnsq_col_skipped_help}");
    $h[]=$kv("{dnsq_col_errors}",$S["log_errors"],true,"{dnsq_col_errors_help}");
    $h[]="<span class='dnsq-grow'></span>";
    $h[]=$S["loopback"]?"<span class='dnsq-tag' title=\"{dnsq_loop_on_help}\"><i class='fa-solid fa-eye' aria-hidden='true'></i>{dnsq_loop_on}</span>"
        :"<span class='dnsq-tag' title=\"{dnsq_loop_off_help}\"><i class='fa-solid fa-eye-slash' aria-hidden='true'></i>{dnsq_loop_off}</span>";
    return "<div class='dnsq-strip'>".implode("",$h)."</div>";
}
function dnsq_search_card(string $value):string{
    $ex=array("example.com","192.168.1.25","NXDOMAIN","REFUSED","AAAA","max 500");
    $b="";
    foreach($ex as $e){ $b.="<button type='button' OnClick=\"DnsqEx('".dnsq_h($e)."')\">".dnsq_h($e)."</button>"; }
    return "<div class='dnsq-card'><label class='dnsq-lbl' for='dnsq-q'>{dnsq_search_label}</label>"
        ."<div class='dnsq-search'><i class='fa-solid fa-magnifying-glass' aria-hidden='true'></i>"
        ."<input type='search' id='dnsq-q' value=\"".dnsq_h($value)."\" placeholder=\"{dnsq_search_ph}\" autocomplete='off' OnKeyDown=\"if(event.key==='Enter'){event.preventDefault();DnsqSearch();}\">"
        ."<button type='button' class='dnsq-go' OnClick='DnsqSearch()'>{dnsq_search_go}</button></div>"
        ."<div class='dnsq-ex'><span class='dnsq-mut'>{dnsq_examples}</span>$b</div>"
        ."<div class='dnsq-help'>{dnsq_search_help}</div></div>";
}
function dnsq_summary(array $M):string{
    $n=count($M["rows"]);
    $h=array();
    $h[]="<span class='dnsq-sum-l'>".dnsq_t($n==1?"dnsq_sum_one":"dnsq_sum_n",$n)." &middot; ".dnsq_t("dnsq_sum_devices",$M["devices"])." &middot; ".dnsq_t("dnsq_sum_names",$M["names"])."</span>";
    foreach($M["rcodes"] as $code=>$c){
        $r=dnsq_rcode($code==="-"?"":(string)$code);
        $h[]="<span class='dnsq-pill dnsq-t-{$r["tone"]}'><i class='{$r["ico"]}' aria-hidden='true'></i>{$r["label"]} $c</span>";
    }
    $h[]="<span class='dnsq-mut' style='font-style:italic'>".dnsq_t("dnsq_sum_scope",$n)."</span>";
    return "<div class='dnsq-sum'>".implode("",$h)."</div>";
}
function dnsq_result_cell(array $it):string{
    $r=dnsq_rcode($it["rcode"]);
    return "<span class='dnsq-pill dnsq-t-{$r["tone"]}'><i class='{$r["ico"]}' aria-hidden='true'></i>{$r["label"]}</span>"
        .($r["code"]!==""?"<span class='dnsq-code'>".dnsq_h($r["code"])."</span>":"");
}
function dnsq_answer_cell(array $it,string $page):string{
    $n=count($it["answers"]);
    if($n==0){return "<span class='dnsq-mut'>—</span>";}
    $h="<span class='dnsq-mono'>".dnsq_h($it["answers"][0])."</span>";
    if($n>1){
        $enc=base64_encode(json_encode($it["answers"]));
        $h.="<button type='button' class='dnsq-more' OnClick=\"Loadjs('$page?zoom-answer-js=".urlencode($enc)."')\">".dnsq_t("dnsq_more",$n-1)."</button>";
    }
    return $h;
}
function dnsq_category_cell(array $it):string{
    $c=dnsq_category_name($it["category"]);
    if($c["name"]===""){return "<span class='dnsq-mut'>{dnsq_uncategorized}</span>";}
    $js="Loadjs('fw.ufdb.categories.php?category-js={$it["category"]}')";
    return "<span class='dnsq-tag dnsq-cat'><a href=\"javascript:blur();\" OnClick=\"$js\" title=\"".dnsq_h($c["desc"])."\">".dnsq_h($c["name"])."</a></span>";
}
function dnsq_row(array $it,string $page):string{
    $lbl=dnsq_qtype_label($it["qtype"]);
    $date=$it["ts"]>0?date("Y-m-d H:i:s",$it["ts"]):"";
    $zoom="Loadjs('$page?zoom-js=".urlencode(base64_encode($it["raw"]))."')";
    $h="<tr>";
    $h.="<td class='dnsq-nw' title=\"".dnsq_h($date)."\">".dnsq_ago($it["ts"])."</td>";
    $h.="<td class='dnsq-nw'><span class='dnsq-mono'>".dnsq_h($it["ip"])."</span>".($it["port"]>0?"<span class='dnsq-mut dnsq-mono'>:".$it["port"]."</span>":"")
        .($it["edns"]!==""?"<span class='dnsq-sub dnsq-mono'>".dnsq_h($it["edns"])."</span>":"")."</td>";
    $h.="<td><span class='dnsq-name'>".dnsq_h($it["qname"])."</span><span class='dnsq-sub'>"
        .($it["qtype"]!==""?"<span class='dnsq-qt'>".dnsq_h($it["qtype"])."</span>":"").$lbl."</span></td>";
    $h.="<td class='dnsq-nw'>".dnsq_result_cell($it)."</td>";
    $h.="<td class='dnsq-nw'>".dnsq_answer_cell($it,$page)."</td>";
    $h.="<td class='dnsq-c-cat'>".dnsq_category_cell($it)."</td>";
    $h.="<td class='dnsq-r dnsq-mono'>".($it["latency"]!==""?dnsq_h($it["latency"]):"<span class='dnsq-mut'>—</span>")."</td>";
    $h.="<td class='dnsq-c-srv dnsq-nw'>".($it["server"]!==""?dnsq_h($it["server"]):"<span class='dnsq-mut'>—</span>")
        .($it["upstream"]!==""?"<span class='dnsq-sub dnsq-mono'>".dnsq_h($it["upstream"])."</span>":"")."</td>";
    $h.="<td><button type='button' class='dnsq-act' aria-label=\"{dnsq_details}\" title=\"{dnsq_details}\" OnClick=\"$zoom\"><i class='fa-solid fa-magnifying-glass-plus' aria-hidden='true'></i></button></td>";
    $h.="</tr>";
    return $h;
}
function dnsq_empty(string $ico,string $title,string $text,string $extra="",bool $err=false):string{
    return "<div class='dnsq-card dnsq-empty'><span class='dnsq-empty-ico".($err?" dnsq-e-err":"")."' aria-hidden='true'><i class='$ico'></i></span>"
        ."<div class='dnsq-empty-t'>$title</div><div class='dnsq-empty-p'>$text</div>$extra</div>";
}
function dnsq_table(array $M,string $page):string{
    $rows=array();
    foreach($M["rows"] as $it){$rows[]=dnsq_row($it,$page);}
    $h=array();
    $h[]="<div class='dnsq-card' style='padding:0;overflow-x:auto'><table class='dnsq-tbl'><thead><tr>";
    $h[]="<th>{dnsq_c_when}</th><th>{dnsq_c_device}</th><th>{dnsq_c_name}</th><th>{dnsq_c_result}</th><th>{dnsq_c_answer}</th>";
    $h[]="<th class='dnsq-c-cat'>{category}</th><th class='dnsq-r'>{dnsq_c_latency}</th><th class='dnsq-c-srv'>{dnsq_c_server}</th><th><span style='position:absolute;left:-9999px'>{dnsq_details}</span></th>";
    $h[]="</tr></thead><tbody id='dnsq-rows'>".implode("",$rows)."</tbody></table>";
    $h[]="<div class='dnsq-foot'><span>".dnsq_t("dnsq_showing",count($rows))."</span><span class='dnsq-pg' id='dnsq-pager'></span></div></div>";
    return implode("",$h);
}
// Contenu de la page : bandeau, etat du collecteur, carte de recherche, hote des resultats, TINY.
function dnsq_build(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $S=dnsq_status_model(dnsq_api_status());
    $h=array();
    $h[]="<div class='dnsq'>";
    $h[]=dnsq_banner();
    $h[]="<div class='dnsq-card' id='dnsq-collector'>".dnsq_collector($S)."</div>";
    $h[]=dnsq_search_card("");
    $h[]="<div id='dnsq-results'></div></div>";
    $topbuttons=array();
    $topbuttons[]=array("DnsqSearch();",ico_refresh,"{refresh}");
    $TINY=array();
    $TINY["TITLE"]="{DNS_QUERIES}";
    $TINY["ICO"]=ico_eye;
    $TINY["EXPL"]="{dnsq_header_explain}";
    $TINY["URL"]="dns-queries";
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
    if(isset($_GET["notitle"])){$jstiny="";}
    echo dnsq_style().dnsq_js($page).$tpl->_ENGINE_parse_body(implode("",$h))
        ."<script>NoSpinner();DnsqSearch();".$tpl->RefreshInterval_js("dnsq-collector",$page,"collector=yes",10).$jstiny."</script>";
}
function dnsq_collector_host(){
    $tpl=new template_admin();
    echo dnsq_style().$tpl->_ENGINE_parse_body(dnsq_collector(dnsq_status_model(dnsq_api_status())));
}
// ?search= : resume + table, ou l'etat vide qui explique pourquoi il n'y a rien.
function search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $raw=(string)$_GET["search"];
    $M=dnsq_events_model($raw);
    $h=array();
    if($M["short"]){
        $h[]="<div class='dnsq-help' style='margin:0 2px 10px'><i class='fa-solid fa-circle-info' aria-hidden='true'></i> {dnsq_short_term}</div>";
    }
    if($M["error"]!==""){
        $h[]=dnsq_empty("fa-solid fa-plug-circle-xmark","{dnsq_err_title}",$M["error"],"",true);
    }elseif(count($M["rows"])==0){
        if(strlen($M["term"])>=3){
            $h[]=dnsq_empty("fa-solid fa-magnifying-glass",dnsq_t("dnsq_nomatch",dnsq_h($M["term"])),"{dnsq_nomatch_text}",
                "<button type='button' class='dnsq-link' OnClick='DnsqClear()'>{dnsq_clear}</button>");
        }else{
            $S=dnsq_status_model(dnsq_api_status());
            if($S["known"] && !$S["running"]){
                $h[]=dnsq_empty("fa-solid fa-plug-circle-xmark","{dnsq_stopped_title}","{dnsq_stopped_text}","",true);
            }else{
                $h[]=dnsq_empty("fa-solid fa-satellite-dish","{dnsq_none_title}",$S["loopback"]?"{dnsq_none_text_loop}":"{dnsq_none_text}");
            }
        }
    }else{
        $h[]=dnsq_summary($M);
        $h[]=dnsq_table($M,$page);
    }
    echo dnsq_style().$tpl->_ENGINE_parse_body(implode("",$h))."<script>NoSpinner();</script>".awc_pager_js(50,"dnsq-rows","dnsq-pager");
}
// Fenetre de detail d'une requete : sections lisibles, puis tous les champs bruts repliés.
function zoom(){
    $tpl=new template_admin();
    $raw=base64_decode((string)($_GET["data"] ?? ""),true);
    $d=($raw===false)?null:json_decode($raw,true);
    if(!is_array($d)){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{no_data}")); return; }
    $it=dnsq_item($d);
    $v=function($x){ return trim((string)$x)===""?"<span class='dnsq-mut'>—</span>":dnsq_h($x); };
    $ips="<span class='dnsq-mut'>—</span>";
    if(count($it["answers"])>0){ $ips="<div class='dnsq-ips'>"; foreach($it["answers"] as $a){$ips.="<span>".dnsq_h($a)."</span>";} $ips.="</div>"; }
    $c=dnsq_category_name($it["category"]);
    $lbl=dnsq_qtype_label($it["qtype"]);
    $sec=function($t,$rows){ $h="<div class='dnsq-sec'><div class='dnsq-sec-t'>$t</div><div class='dnsq-dl'>"; foreach($rows as $k=>$x){$h.="<div>$k</div><div>$x</div>";} return $h."</div></div>"; };
    $h=array();
    $h[]="<div class='dnsq' style='margin:0'>";
    $h[]=$sec("{dnsq_z_question}",array("{dnsq_c_name}"=>"<span class='dnsq-name'>".$v($it["qname"])."</span>",
        "{dnsq_z_type}"=>$v($it["qtype"]).($lbl!==""?" <span class='dnsq-mut'>· $lbl</span>":""),"{dnsq_z_proto}"=>$v($it["proto"])));
    $h[]=$sec("{dnsq_c_device}",array("{dnsq_z_ip}"=>"<span class='dnsq-mono'>".$v($it["ip"])."</span>","{dnsq_z_port}"=>$it["port"]>0?$it["port"]:"<span class='dnsq-mut'>—</span>",
        "{dnsq_z_edns}"=>$v($it["edns"])));
    $h[]=$sec("{dnsq_c_answer}",array("{dnsq_c_result}"=>dnsq_result_cell($it),"{dnsq_z_answers}"=>$ips,
        "{dnsq_c_latency}"=>$v($it["latency"]),"{category}"=>$c["name"]===""?"<span class='dnsq-mut'>{dnsq_uncategorized}</span>":dnsq_h($c["name"])));
    $h[]=$sec("{dnsq_z_servers}",array("{dnsq_c_server}"=>$v($it["identity"]),"{dnsq_z_upstream}"=>"<span class='dnsq-mono'>".$v($it["upstream"])."</span>",
        "{dnsq_z_msgtype}"=>$v($it["msg_type"])));
    $h[]=$sec("{dnsq_c_when}",array("{dnsq_z_date}"=>$it["ts"]>0?dnsq_h(date("Y-m-d H:i:s",$it["ts"]))." <span class='dnsq-mut'>(".dnsq_ago($it["ts"]).")</span>":"—"));
    $h[]="<details><summary>{dnsq_z_raw}</summary><div class='dnsq-dl' style='margin-top:8px'>".dnsq_raw_fields($d)."</div></details>";
    $h[]="</div>";
    echo dnsq_style().$tpl->_ENGINE_parse_body(implode("",$h));
}
function dnsq_raw_fields($data,string $prefix=""):string{
    $f=array();
    foreach((array)$data as $key=>$value){
        $k=$prefix===""?(string)$key:"$prefix.$key";
        if(is_array($value) || is_object($value)){ $f[]=dnsq_raw_fields((array)$value,$k); continue; }
        $value=(string)$value;
        if($value==="" || $value==="-"){continue;}
        $f[]="<div class='dnsq-mono'>".dnsq_h($k)."</div><div class='dnsq-mono'>".dnsq_h($value)."</div>";
    }
    return implode("",$f);
}
function zoom_answer_popup(){
    $tpl=new template_admin();
    // JSON, plus de unserialize() sur une donnee venue de l'URL.
    $raw=base64_decode((string)($_GET["zoom-answer-popup"] ?? ""),true);
    $ips=($raw===false)?null:json_decode($raw,true);
    if(!is_array($ips) || count($ips)==0){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{no_data}")); return; }
    $h="<div class='dnsq' style='margin:0'><div class='dnsq-sec-t'>".dnsq_t("dnsq_answers_n",count($ips))."</div><div class='dnsq-ips'>";
    foreach($ips as $ip){ $h.="<span>".dnsq_h((string)$ip)."</span>"; }
    echo dnsq_style().$tpl->_ENGINE_parse_body($h."</div></div>");
}
// ---- DNSQ widgets end ----

function zoom_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $data=urlencode((string)$_GET["zoom-js"]);
    $tpl->js_dialog2("{dnsq_details}","$page?zoom=yes&data=$data",680);
}
function zoom_answer_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $data=urlencode((string)$_GET["zoom-answer-js"]);
    $tpl->js_dialog2("{dnsq_c_answer}","$page?zoom-answer-popup=$data",520);
}
