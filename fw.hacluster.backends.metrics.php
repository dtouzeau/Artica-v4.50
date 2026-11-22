<?php
/**
 * HaCluster Backend Metrics Dashboard (onglet Metrics de fw.hacluster.backends2.php)
 * Repart de la maquette stitch_hacluster_load_balancer_metrics (2026-11-06) : kit bcm-
 * (carte blanche, bord #e2e8f0, rayon 6px, liseré d'état de 3px), le design des
 * tableaux de bord proxy.
 *
 *   Vue d'ensemble — 4 tuiles, répartition des hits (donut), codes HTTP par classe,
 *                    table unique Backends (classement + santé)
 *   Détail         — temps de réponse, volume, connexions, états de terminaison
 *   Live           — /latest toutes les 30 s, un point ajouté par nouvelle fenêtre
 *
 * API (GET, enveloppe {success,data}) :
 *   /hacluster/connstats/backends?range=X          — agrégat par backend
 *   /hacluster/connstats/backends/health?range=X    — error_rate, retry_rate
 *   /hacluster/connstats/backends/{name}?range=X    — fenêtres d'un backend
 *   /hacluster/connstats/latest                     — fenêtre en cours (Live)
 * Les temps (*_ms) sont des millisecondes : %Tc et %Tw de HAProxy.
 */

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
// siem_tiny_filters() : filtres de l'en-tete (TINY).
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors',1);ini_set('error_reporting',E_ALL);}

$users=new usersMenus();
if(!$users->AsProxyMonitor){exit();}

// ── Dispatcher ──────────────────────────────────────────────────
if(isset($_GET["main-view"]))       {main_view();       exit;}
if(isset($_GET["overview-charts"])) {overview_charts();  exit;}
if(isset($_GET["detail-charts"]))   {detail_charts();    exit;}
if(isset($_GET["live-poll"]))       {live_poll();        exit;}
page();

// ── Helpers ─────────────────────────────────────────────────────

function _valid_range(string $r):string{
    $ok=["1h","6h","24h","48h","7d","30d"];
    return in_array($r,$ok)?$r:"1h";
}

function _range_date_fmt(string $range):string{
    switch($range){
        case "7d": case "30d": return 'M d H:00';
        case "48h": return 'M d H:i';
        default: return 'H:i';
    }
}

/** Format bytes to human-readable (input is bytes, not KB). */
function _fmt_bytes(int $bytes):string{
    if($bytes>=1073741824) return sprintf('%.1f GB',$bytes/1073741824);
    if($bytes>=1048576) return sprintf('%.1f MB',$bytes/1048576);
    if($bytes>=1024) return sprintf('%.1f KB',$bytes/1024);
    return "$bytes B";
}

/** Durée en ms -> array(valeur, unité) : « 24.6 ms » sous la seconde, « 2.4 s » au-delà. */
function bcm_ms($ms):array{
    $ms=floatval($ms);
    if($ms>=1000){ return array(sprintf('%.1f',$ms/1000),"s"); }
    if($ms>=100){ return array(sprintf('%.0f',$ms),"ms"); }
    return array(sprintf('%.1f',$ms),"ms");
}

function bcm_ms_txt($ms):string{
    $p=bcm_ms($ms);
    return $p[0]." ".$p[1];
}

/** Jeton à %s : traduit D'ABORD, puis la valeur est placée (sinon %s n'existe pas encore). */
function bcm_tr(string $token,string $val):string{
    $tpl=new template_admin();
    return str_replace("%s",$val,$tpl->_ENGINE_parse_body("{".$token."}"));
}

function bcm_pct($rate):string{
    return sprintf('%.2f%%',floatval($rate)*100);
}

/** Seuils de santé (inchangés) : temps en ms, taux en fraction. */
function bcm_thresholds():array{
    return array("err_warn"=>0.01,"err_crit"=>0.10,"retry_warn"=>0.01,"retry_crit"=>0.05,
        "rt_warn"=>10000,"rt_crit"=>30000);
}

/** État d'un backend : ok | warn | crit. */
function bcm_state(float $err,float $retry,float $rtMs):string{
    $t=bcm_thresholds();
    if($err>$t["err_crit"] || $retry>$t["retry_crit"] || $rtMs>$t["rt_crit"]){ return "crit"; }
    if($err>$t["err_warn"] || $retry>$t["retry_warn"] || $rtMs>$t["rt_warn"]){ return "warn"; }
    return "ok";
}

function bcm_rt_state(float $rtMs):string{
    $t=bcm_thresholds();
    if($rtMs>$t["rt_crit"]){ return "crit"; }
    if($rtMs>$t["rt_warn"]){ return "warn"; }
    return "ok";
}

/** Accents de la marque (émeraude/teal par défaut, recolorés en marque blanche). */
function bcm_accents():array{
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488",
        "accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return $ac;
}

function bcm_palette():array{
    $ac=bcm_accents();
    return array($ac["accent"],'#0ea5e9','#f59e0b','#005447','#ec4899','#005447','#14b8a6','#f97316');
}

function bcm_rgba(string $hex,string $alpha):string{
    $h=ltrim(trim($hex),'#');
    if(strlen($h)==3){$h=$h[0].$h[0].$h[1].$h[1].$h[2].$h[2];}
    if(!preg_match('/^[0-9a-fA-F]{6}$/',$h)){return $hex;}
    return sprintf("rgba(%d,%d,%d,%s)",hexdec(substr($h,0,2)),hexdec(substr($h,2,2)),hexdec(substr($h,4,2)),$alpha);
}

function bcm_api(string $path){
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API($path));
    if(!is_object($json) || empty($json->success) || !isset($json->data) || !is_object($json->data)){ return null; }
    return $json->data;
}

/** Pastille d'état : icône ET mot, jamais la couleur seule. */
function bcm_pill(string $state):string{
    $map=array("ok"=>array("fa-circle-check","{bcm_healthy}"),"warn"=>array("fa-triangle-exclamation","{bcm_warning}"),
        "crit"=>array("fa-circle-xmark","{bcm_critical}"));
    if(!isset($map[$state])){ $state="ok"; }
    return "<span class='ak-pill ak-".($state=="crit"?"err":$state)."'><i class='fas {$map[$state][0]}' aria-hidden='true'></i>{$map[$state][1]}</span>";
}

/**
 * Feuille du kit, une fois par requête, concaténée HORS de _ENGINE_parse_body().
 */
function bcm_style():string{
    if(!empty($GLOBALS["BCM_CSS_DONE"])){return "";}
    $GLOBALS["BCM_CSS_DONE"]=true;
    $ac=bcm_accents();
    $a=$ac["accent"]; $ad=$ac["accent_dark"]; $as=$ac["accent_soft"];
    return "<style>
.bcm-wrap{margin-top:4px}
.bcm-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:16px}
.bcm-tile{position:relative;display:flex;flex-direction:column;padding:14px 16px 12px 19px;height:100%}
.bcm-tile:before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;border-radius:6px 0 0 6px;background:$a}
.bcm-st-warn.bcm-tile:before{background:#f59e0b}.bcm-st-crit.bcm-tile:before{background:#ef4444}.bcm-st-off.bcm-tile:before{background:#94a3b8}
.bcm-head{display:flex;align-items:flex-start;justify-content:space-between;gap:8px}
.bcm-label{font-size:11px;font-weight:500;line-height:14px;letter-spacing:.04em;text-transform:uppercase;color:#64748b}
.bcm-val{display:flex;align-items:baseline;gap:6px;flex-wrap:wrap;font-size:28px;font-weight:700;line-height:34px;letter-spacing:-.03em;margin-top:4px;font-variant-numeric:tabular-nums}
.bcm-unit{font-size:14px;font-weight:500;letter-spacing:0;color:#64748b}
.bcm-sub{font-size:13px;color:#64748b;line-height:18px}
.bcm-foot{margin-top:auto;padding-top:10px;border-top:1px solid #f1f5f9;margin-top:12px;font-size:12px;line-height:16px;color:#64748b;display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.bcm-dot{display:inline-block;width:8px;height:8px;border-radius:9999px;margin-right:5px;vertical-align:middle}
.bcm-sec{padding:16px 18px;margin-bottom:16px}
.bcm-sec-h{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap}
.bcm-sec-t{font-size:16px;font-weight:600;line-height:24px;letter-spacing:-.01em}
.bcm-sec-t i{color:$ad;margin-right:8px}
.bcm-sec-s{font-size:12px;line-height:16px;color:#64748b;margin-top:2px}
.bcm-duo{display:grid;grid-template-columns:minmax(0,7fr) minmax(0,5fr);gap:16px}
.bcm-trio{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.bcm-donut{position:relative;height:230px}
.bcm-donut-c{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none}
.bcm-donut-v{font-size:24px;font-weight:700;letter-spacing:-.02em;line-height:28px}
.bcm-donut-l{font-size:11px;font-weight:500;letter-spacing:.04em;text-transform:uppercase;color:#64748b}
.bcm-list{list-style:none;margin:14px 0 0 0;padding:0;border-top:1px solid #f1f5f9}
.bcm-li{display:grid;grid-template-columns:14px minmax(0,1fr) auto 64px;gap:10px;align-items:center;padding:9px 2px;border-bottom:1px solid #f1f5f9;font-size:13px}
.bcm-sw{width:10px;height:10px;border-radius:3px;display:inline-block}
.bcm-nm{font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bcm-mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;color:#64748b;font-weight:400}
.bcm-num{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
.bcm-share{justify-self:end;font-size:12px;font-weight:600;padding:2px 7px;border-radius:4px;background:#f1f5f9;color:#334155}
.bcm-chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.bcm-cc{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;padding:4px 9px;border-radius:4px;border:1px solid #e2e8f0;background:#f8fafc;white-space:nowrap}
.bcm-cc span{font-weight:400;color:#64748b;font-variant-numeric:tabular-nums}
.bcm-sb{margin-bottom:14px}
.bcm-sb-h{display:flex;justify-content:space-between;gap:10px;font-size:13px;margin-bottom:5px}
.bcm-sb-h .bcm-mono{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bcm-sb-bar{display:flex;height:12px;border-radius:9999px;overflow:hidden;background:#f1f5f9}
.bcm-sb-bar>span{display:block;height:100%}
.bcm-note{font-size:12px;color:#64748b;margin-top:8px;line-height:16px}
.bcm-tbl{font-size:13px}.bcm-tbl th{white-space:nowrap}
.bcm-tr{cursor:pointer;transition:background .15s}
.bcm-tr:hover td{background:#f8fafc}
.bcm-tr:focus-visible{outline:2px solid $ad;outline-offset:-2px}
.bcm-tr.bcm-sel td{background:".bcm_rgba($a,".05")."}
.bcm-tr.bcm-sel td:first-child{box-shadow:inset 3px 0 0 $a}
.bcm-rank{width:26px;height:26px;border-radius:9999px;background:#f1f5f9;color:#64748b;font-size:12px;font-weight:700;display:inline-flex;align-items:center;justify-content:center}
.bcm-top3 .bcm-rank{background:$as;color:$ad}
.bcm-selb{display:none;font-size:11px;font-weight:600;color:$ad;background:$as;border-radius:4px;padding:1px 6px;margin-left:6px}
.bcm-sel .bcm-selb{display:inline-block}
.bcm-meter{margin-top:6px;min-width:90px}
.bcm-t-warn{color:#b45309}.bcm-t-crit{color:#b91c1c}
.bcm-empty{display:flex;align-items:center;justify-content:center;gap:10px;min-height:120px;color:#64748b;font-size:13px;background:#f8fafc;border:1px dashed #e2e8f0;border-radius:6px;padding:16px;text-align:center}
.bcm-dh{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;margin-bottom:16px;flex-wrap:wrap}
.bcm-dh-t{font-size:16px;font-weight:600;display:flex;align-items:center;gap:8px}
#bcm-live-pill i{animation:bcmblink 1s infinite}
.bcm-chart{position:relative;height:280px}
.bcm-chart-s{position:relative;height:200px}
.bcm-lg{display:flex;gap:14px;flex-wrap:wrap;font-size:12px;color:#334155}
.bcm-lg i{margin-right:5px}
.bcm-ln{display:inline-block;width:14px;height:0;border-top:2px solid;vertical-align:middle;margin-right:6px}
.bcm-ln-d{border-top-style:dashed}
.bcm-kv{display:flex;justify-content:space-between;gap:10px;margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9;font-size:12px;color:#64748b}
.bcm-kv b{color:#0f172a;font-weight:600}
.bcm-term{list-style:none;margin:0;padding:0}
.bcm-term li{padding:7px 0;border-bottom:1px solid #f1f5f9}
.bcm-term li:last-child{border-bottom:0}
.bcm-term-h{display:flex;justify-content:space-between;gap:10px;font-size:13px}
.bcm-flag{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:700;margin-right:8px}
@keyframes bcmblink{0%,100%{opacity:1}50%{opacity:.35}}
@media(max-width:1200px){.bcm-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}.bcm-duo,.bcm-trio{grid-template-columns:minmax(0,1fr)}}
@media(prefers-reduced-motion:reduce){.bcm-tr{transition:none}#bcm-live-pill i{animation:none}}
</style>";
}

// ── Page entry point ────────────────────────────────────────────

function page():void{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{backends} {statistics}",
        "fas fa-chart-bar",
        "{hacluster_backends_metrics_explain}",
        "$page?main-view=yes&range=1h");
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Hôte de l'onglet : en-tête (TINY) + conteneurs + JS ─────────

function main_view():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=_valid_range($_GET["range"]??"1h");

    // La liste des backends couvre la plus large fenetre (30d) : elle n'est pas
    // reemise au changement de periode, la selection reste donc en place.
    $names=array();
    $all=bcm_api("/hacluster/connstats/backends?range=30d");
    if(is_object($all)){
        foreach(($all->backends??array()) as $b){
            $n=(string)($b->server_name??"");
            if($n!==""){$names[$n]=req_html($n);}
        }
    }
    ksort($names);
    $sel_backend=(string)($_GET["backend"]??"");
    if(!isset($names[$sel_backend])){ $sel_backend=count($names)?(string)key($names):""; }

    $fields=array();
    $fields[]=array("TYPE"=>"segment","ID"=>"bcm-range","LABEL"=>"{period}","VALUE"=>$range,
        "OPTIONS"=>array("1h"=>"1h","6h"=>"6h","24h"=>"24h","48h"=>"48h","7d"=>"7d","30d"=>"30d"),"JS"=>"BcmRange");
    if(count($names)>0){
        $fields[]=array("TYPE"=>"select","ID"=>"bcm-backend-sel","LABEL"=>"{backend}","VALUE"=>$sel_backend,
            "OPTIONS"=>$names,"JS"=>"BcmBackendChange");
    }
    $fields[]=array("TYPE"=>"action","LABEL"=>"{refresh}","ICON"=>"fas fa-sync-alt","JS"=>"BcmRefresh");
    // Live a son propre bouton (id) : son etat se lit sur le bouton lui-meme.
    $live="<a id='bcm-live-btn' class='siem-tinyf-bt' href=\"javascript:blur();\" OnClick=\"BcmToggleLive()\">"
         ."<i class='fas fa-broadcast-tower'></i> {live}</a>";
    $TINY_ARRAY=array("TITLE"=>"{hb2_title}","ICO"=>"fas fa-server","EXPL"=>"{hacluster_backends_metrics_explain}",
        "URL"=>"hacluster-backends2","BUTTONS"=>siem_tiny_filters($fields,$live));
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $h=array();
    $h[]="<div id='bcm-overview'></div>";
    $h[]="<div id='bcm-detail'></div>";

    // Tout le JS de navigation est declare ICI, par l'hote : l'en-tete arrive
    // avant les fragments, un changement de liste ne doit pas tomber sur une
    // fonction pas encore definie.
    $h[]="<script>";
    $h[]="var _bcmRange='$range',_bcmLive=false,_bcmLiveTimer=null,_bcmLiveOverviewTimer=null;";
    $h[]="function BcmMarkRange(r){var i=['1h','6h','24h','48h','7d','30d'].indexOf(r);if(i<0){return false;}\$('#bcm-range a').removeClass('on').eq(i).addClass('on');return true;}";
    $h[]="function BcmBackend(){var e=document.getElementById('bcm-backend-sel');return e?'&backend='+encodeURIComponent(e.value):'';}";
    $h[]="function BcmMarkRow(){var e=document.getElementById('bcm-backend-sel');var v=e?e.value:'';\$('.bcm-tr').each(function(){\$(this).toggleClass('bcm-sel',this.getAttribute('data-b')===v);});}";
    $h[]="function BcmRange(r){";
    $h[]="  if(!BcmMarkRange(r)){return;}";
    $h[]="  _bcmRange=r;";
    $h[]="  if(_bcmLive){BcmStopLive();}";
    $h[]="  BcmRefresh();";
    $h[]="}";
    $h[]="function BcmRefresh(){";
    $h[]="  LoadAjax('bcm-overview','$page?overview-charts=yes&range='+_bcmRange+BcmBackend());";
    $h[]="  LoadAjax('bcm-detail','$page?detail-charts=yes&range='+_bcmRange+BcmBackend()+(_bcmLive?'&live=1':''));";
    $h[]="}";
    $h[]="function BcmBackendChange(){";
    $h[]="  BcmMarkRow();";
    $h[]="  LoadAjax('bcm-detail','$page?detail-charts=yes&range='+_bcmRange+BcmBackend()+(_bcmLive?'&live=1':''));";
    $h[]="}";
    // Clic sur une ligne de la table : la liste de l'en-tete suit, puis le detail.
    $h[]="function BcmSelect(row){";
    $h[]="  var v=row.getAttribute('data-b');var e=document.getElementById('bcm-backend-sel');";
    $h[]="  if(e){for(var i=0;i<e.options.length;i++){if(e.options[i].value===v){e.selectedIndex=i;break;}}}";
    $h[]="  BcmBackendChange();";
    $h[]="  var d=document.getElementById('bcm-detail');if(d&&d.scrollIntoView){d.scrollIntoView({behavior:'smooth',block:'start'});}";
    $h[]="}";

    // Live mode toggle
    $h[]="function BcmToggleLive(){";
    $h[]="  if(_bcmLive){BcmStopLive();return;}";
    $h[]="  _bcmLive=true;";
    $h[]="  \$('#bcm-live-btn').addClass('bcm-on');";
    $h[]="  _bcmRange='1h';BcmMarkRange('1h');";
    $h[]="  LoadAjax('bcm-overview','$page?overview-charts=yes&range=1h'+BcmBackend());";
    $h[]="  LoadAjax('bcm-detail','$page?detail-charts=yes&range=1h&live=1'+BcmBackend());";
    $h[]="  _bcmLiveOverviewTimer=setInterval(function(){";
    $h[]="    LoadAjaxSilent('bcm-overview','$page?overview-charts=yes&range=1h'+BcmBackend());";
    $h[]="  },60000);";
    $h[]="}";
    $h[]="function BcmStopLive(){";
    $h[]="  _bcmLive=false;";
    $h[]="  \$('#bcm-live-btn').removeClass('bcm-on');";
    $h[]="  \$('#bcm-live-pill').hide();";
    $h[]="  if(_bcmLiveTimer){clearInterval(_bcmLiveTimer);_bcmLiveTimer=null;}";
    $h[]="  if(_bcmLiveOverviewTimer){clearInterval(_bcmLiveOverviewTimer);_bcmLiveOverviewTimer=null;}";
    $h[]="}";

    $h[]="BcmRefresh();";
    $h[]="$jstiny";
    $h[]="</script>";

    // Feuilles hors du moteur de traduction (accolades CSS).
    $css="<style>@keyframes bcmblink{0%,100%{opacity:1}50%{opacity:.35}}"
        ."#tiny-button #bcm-live-btn.bcm-on{background:#ef4444;border-color:#ef4444;color:#fff}"
        ."#tiny-button #bcm-live-btn.bcm-on i{animation:bcmblink 1s infinite}"
        ."@media(prefers-reduced-motion:reduce){#tiny-button #bcm-live-btn.bcm-on i{animation:none}}</style>";
    echo siem_tiny_filters_style().$css.$tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Modèle de la vue d'ensemble ──────────────────────────────────

/** Agrégat + santé de la période, triés par hits : tous les widgets s'en servent. */
function bcm_model(string $range):array{
    $m=array("range"=>$range,"ok"=>false,"bucket"=>"","rows"=>array(),"hits"=>0,"read"=>0,"up"=>0,
        "rt"=>0,"warn"=>0,"crit"=>0);
    $d=bcm_api("/hacluster/connstats/backends?range=$range");
    if(!is_object($d)){ return $m; }
    $m["ok"]=true;
    $m["bucket"]=(string)($d->bucket??"");
    $health=array();
    $hd=bcm_api("/hacluster/connstats/backends/health?range=$range");
    if(is_object($hd)){
        foreach(($hd->backends??array()) as $b){
            $health[(string)($b->server_name??"")]=array(floatval($b->error_rate??0),floatval($b->retry_rate??0));
        }
    }
    $wrt=0;
    foreach(($d->backends??array()) as $b){
        $name=(string)($b->server_name??"");
        if($name===""){ continue; }
        $hits=floatval($b->hits??0);
        $rt=floatval($b->avg_response_time_ms??0);
        $r=array("name"=>$name,"ip"=>(string)($b->server_ip??""),"port"=>intval($b->server_port??0),
            "hits"=>$hits,"rt"=>$rt,"min"=>floatval($b->min_response_time_ms??0),"max"=>floatval($b->max_response_time_ms??0),
            "read"=>floatval($b->total_bytes_read??0),"up"=>floatval($b->total_bytes_uploaded??0),
            "conns"=>intval($b->active_conns??0),"codes"=>(array)($b->status_codes??array()),
            "err"=>null,"retry"=>null);
        if(isset($health[$name])){ $r["err"]=$health[$name][0]; $r["retry"]=$health[$name][1]; }
        $r["state"]=bcm_state(floatval($r["err"]),floatval($r["retry"]),$rt);
        if($r["state"]=="warn"){ $m["warn"]++; }
        if($r["state"]=="crit"){ $m["crit"]++; }
        $m["hits"]+=$hits; $m["read"]+=$r["read"]; $m["up"]+=$r["up"];
        $wrt+=$rt*$hits;
        $m["rows"][]=$r;
    }
    // Moyenne pondérée par les hits : la moyenne des moyennes donnait autant de
    // poids à un backend de 10 requêtes qu'à un backend de 100 000.
    if($m["hits"]>0){ $m["rt"]=$wrt/$m["hits"]; }
    usort($m["rows"],function($a,$b){ return ($b["hits"]<=>$a["hits"]); });
    return $m;
}

function bcm_step_token(string $bucket):string{
    $map=array("raw5m"=>"{bcm_step_raw5m}","agg30m"=>"{bcm_step_agg30m}","hourly"=>"{bcm_step_hourly}",
        "daily"=>"{bcm_step_daily}","weekly"=>"{bcm_step_weekly}");
    return isset($map[$bucket])?$map[$bucket]:"";
}

function bcm_addr(array $r):string{
    if($r["ip"]===""){ return ""; }
    return req_html($r["ip"].($r["port"]>0?":".$r["port"]:""));
}

// ── Tuiles ──

function bcm_tile(string $state,string $label,string $icon,string $val,string $unit,string $sub,string $foot,string $extra=""):string{
    $u=$unit!==""?"<span class='bcm-unit'>$unit</span>":"";
    return "<div class='ak-card bcm-tile bcm-st-$state'><div class='bcm-head'><span class='bcm-label'>$label</span>"
        ."<span class='ak-ico ak-ico-sm ak-ico-sq'><i class='$icon' aria-hidden='true'></i></span></div>"
        ."<div class='bcm-val'>$val$u$extra</div><div class='bcm-sub'>$sub</div>"
        ."<div class='bcm-foot'>$foot</div></div>";
}

function bcm_kpi_hits(array $m):string{
    $n=count($m["rows"]);
    if($n==0){ return bcm_tile("off","{bcm_hits}","fas fa-arrow-right-arrow-left","&mdash;","","{bcm_total_requests}","{no_data}"); }
    return bcm_tile("ok","{bcm_hits}","fas fa-arrow-right-arrow-left",number_format($m["hits"]),"","{bcm_total_requests}",
        "<span><i class='fas fa-layer-group' aria-hidden='true'></i>&nbsp;".bcm_tr("bcm_across",(string)$n)."</span>");
}

function bcm_kpi_rt(array $m):string{
    if(count($m["rows"])==0){ return bcm_tile("off","{bcm_avg_rt}","fas fa-gauge-high","&mdash;","","{bcm_rt_sub}","{bcm_rt_note}"); }
    $st=bcm_rt_state($m["rt"]);
    $p=bcm_ms($m["rt"]);
    return bcm_tile($st,"{bcm_avg_rt}","fas fa-gauge-high",$p[0],$p[1],"{bcm_rt_sub}",
        "<span><i class='fas fa-circle-info' aria-hidden='true'></i>&nbsp;{bcm_rt_note}</span>",bcm_pill($st));
}

function bcm_kpi_bandwidth(array $m):string{
    if(count($m["rows"])==0){ return bcm_tile("off","{bcm_bandwidth}","fas fa-download","&mdash;","","{bcm_transferred}","{no_data}"); }
    $parts=explode(" ",_fmt_bytes(intval($m["read"]+$m["up"])),2);
    return bcm_tile("ok","{bcm_bandwidth}","fas fa-download",$parts[0],isset($parts[1])?$parts[1]:"","{bcm_transferred}",
        "<span class='bcm-mono'>&darr; "._fmt_bytes(intval($m["read"]))." {bcm_download} &middot; &uarr; "._fmt_bytes(intval($m["up"]))." {bcm_upload}</span>");
}

function bcm_kpi_backends(array $m):string{
    $n=count($m["rows"]);
    if($n==0){ return bcm_tile("off","{bcm_backends}","fas fa-server","0","","{bcm_answered}","{no_data}"); }
    $st=$m["crit"]>0?"crit":($m["warn"]>0?"warn":"ok");
    $foot="<span><span class='bcm-dot' style='background:#f59e0b'></span>".bcm_tr("bcm_n_warning",(string)$m["warn"])."</span>"
         ."<span><span class='bcm-dot' style='background:#ef4444'></span>".bcm_tr("bcm_n_critical",(string)$m["crit"])."</span>";
    return bcm_tile($st,"{bcm_backends}","fas fa-server",(string)$n,"","{bcm_answered}",$foot);
}

function bcm_tiles(array $m):string{
    return "<div class='bcm-tiles'>".bcm_kpi_hits($m).bcm_kpi_rt($m).bcm_kpi_bandwidth($m).bcm_kpi_backends($m)."</div>";
}

// ── Répartition des hits ──

function bcm_hits_card(array $m):string{
    $h ="<div class='ak-card bcm-sec'><div class='bcm-sec-h'><div class='bcm-sec-t'><i class='fas fa-chart-pie' aria-hidden='true'></i>{bcm_hits_dist}</div>";
    $step=bcm_step_token($m["bucket"]);
    if($step!==""){ $h.="<span class='ak-tag'>$step</span>"; }
    $h.="</div>";
    if($m["hits"]<=0){ return $h."<div class='bcm-empty'><i class='fas fa-chart-pie' aria-hidden='true'></i>{no_data}</div></div>"; }
    $pal=bcm_palette();
    $slices=array();
    $other=0;
    foreach($m["rows"] as $i=>$r){
        if($i<8){ $slices[]=array("name"=>$r["name"],"addr"=>bcm_addr($r),"hits"=>$r["hits"],"color"=>$pal[$i % count($pal)]); }
        else { $other+=$r["hits"]; }
    }
    if($other>0){ $slices[]=array("name"=>"","addr"=>"","hits"=>$other,"color"=>"#cbd5e1"); }
    $uid="bcmd".substr(md5(uniqid('',true)),0,10);
    $h.="<div class='bcm-donut'><canvas id='$uid' role='img' aria-label='{bcm_hits_dist}'></canvas>";
    $h.="<div class='bcm-donut-c'><div class='bcm-donut-v'>".number_format($m["hits"])."</div><div class='bcm-donut-l'>{bcm_hits}</div></div></div>";
    $h.="<ul class='bcm-list'>";
    $vals=array(); $cols=array(); $labels=array();
    foreach($slices as $s){
        $pct=sprintf('%.1f%%',$s["hits"]/$m["hits"]*100);
        $nm=$s["name"]===""?"{bcm_other}":req_html($s["name"]);
        $ad=$s["addr"]!==""?" <span class='bcm-mono'>({$s["addr"]})</span>":"";
        $h.="<li class='bcm-li'><span class='bcm-sw' style='background:{$s["color"]}'></span><span class='bcm-nm'>$nm$ad</span>"
           ."<span class='bcm-num'>".number_format($s["hits"])."</span><span class='bcm-share'>$pct</span></li>";
        $vals[]=$s["hits"]; $cols[]=$s["color"]; $labels[]=$s["name"];
    }
    $h.="</ul></div>";
    $GLOBALS["BCM_DONUT"]=array("uid"=>$uid,"v"=>$vals,"c"=>$cols,"n"=>$labels);
    return $h;
}

function bcm_hits_js():string{
    if(empty($GLOBALS["BCM_DONUT"])){ return ""; }
    $d=$GLOBALS["BCM_DONUT"];
    $tpl=new template_admin();
    foreach($d["n"] as $i=>$n){ if($n===""){ $d["n"][$i]=$tpl->_ENGINE_parse_body("{bcm_other}"); } }
    $json=json_encode(array("v"=>$d["v"],"c"=>$d["c"],"n"=>$d["n"]),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_PARTIAL_OUTPUT_ON_ERROR);
    return "<script>(function(){var D=$json;var el=document.getElementById('{$d["uid"]}');if(!el||typeof Chart==='undefined'){return;}"
        ."var calm=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;"
        ."new Chart(el,{type:'doughnut',data:{labels:D.n,datasets:[{data:D.v,backgroundColor:D.c,borderColor:'#fff',borderWidth:2,borderRadius:3,spacing:1,hoverOffset:6}]},"
        ."options:{responsive:true,maintainAspectRatio:false,cutout:'74%',animation:calm?false:{duration:700},"
        ."plugins:{legend:{display:false},datalabels:{display:false},tooltip:{backgroundColor:'#0f172a',padding:10,cornerRadius:6,"
        ."callbacks:{label:function(c){return ' '+c.label+': '+Number(c.parsed).toLocaleString();}}}}}});})();</script>";
}

// ── Codes HTTP par classe ──

/** Classe d'un code : 1xx…5xx, « tcp » pour 0 ou une valeur non numérique. */
function bcm_code_class(string $code):string{
    if(!preg_match('/^[1-5][0-9][0-9]$/',$code)){ return "tcp"; }
    return $code[0]."xx";
}

function bcm_classes():array{
    return array("1xx"=>"#005447","2xx"=>"#10b981","3xx"=>"#0ea5e9","4xx"=>"#f59e0b","5xx"=>"#ef4444","tcp"=>"#94a3b8");
}

function bcm_status_card(array $m):string{
    $h ="<div class='ak-card bcm-sec'><div class='bcm-sec-h'><div class='bcm-sec-t'><i class='fas fa-list-check' aria-hidden='true'></i>{bcm_status_codes}</div>";
    $step=bcm_step_token($m["bucket"]);
    if($step!==""){ $h.="<span class='ak-tag'>$step</span>"; }
    $h.="</div>";
    $cls=bcm_classes();
    $tot=array_fill_keys(array_keys($cls),0);
    foreach($m["rows"] as $r){ foreach($r["codes"] as $c=>$n){ $tot[bcm_code_class((string)$c)]+=intval($n); } }
    if(array_sum($tot)<=0){ return $h."<div class='bcm-empty'><i class='fas fa-list-check' aria-hidden='true'></i>{no_data}</div></div>"; }
    $h.="<div class='bcm-chips'>";
    foreach($cls as $k=>$col){
        if($k=="1xx" && $tot[$k]==0){ continue; }
        $lbl=$k=="tcp"?"{bcm_tcp}":$k;
        $h.="<span class='bcm-cc'><span class='bcm-dot' style='background:$col;margin:0'></span>$lbl <span>".number_format($tot[$k])."</span></span>";
    }
    $h.="</div>";
    foreach($m["rows"] as $i=>$r){
        if($i>=12){ break; }
        $sum=0; $byc=array_fill_keys(array_keys($cls),0); $detail=array();
        foreach($r["codes"] as $c=>$n){ $n=intval($n); $sum+=$n; $byc[bcm_code_class((string)$c)]+=$n; $detail[]=req_html((string)$c).": ".number_format($n); }
        if($sum<=0){ continue; }
        $parts=array(); $bar="";
        foreach($cls as $k=>$col){
            if($byc[$k]<=0){ continue; }
            $w=round($byc[$k]/$sum*100,2);
            $bar.="<span style='width:$w%;background:$col'></span>";
            $parts[]=sprintf('%.1f%%',$byc[$k]/$sum*100)." ".($k=="tcp"?"TCP":$k);
        }
        $title=implode(" · ",$detail);
        $h.="<div class='bcm-sb' title=\"$title\"><div class='bcm-sb-h'><span class='bcm-nm'>".req_html($r["name"])." <span class='bcm-mono'>(".number_format($sum).")</span></span>"
           ."<span class='bcm-mono'>".implode(" · ",$parts)."</span></div><div class='bcm-sb-bar' role='img' aria-label=\"".req_html($r["name"]).": ".implode(", ",$parts)."\">$bar</div></div>";
    }
    $h.="<div class='bcm-note'>{bcm_status_note}</div></div>";
    return $h;
}

// ── Table Backends (classement + santé) ──

function bcm_backends_table(array $m,string $selected):string{
    $t=bcm_thresholds();
    $h ="<div class='ak-card' style='margin-bottom:16px'><div class='bcm-sec-h' style='padding:16px 18px 0 18px'><div>";
    $h.="<div class='bcm-sec-t'><i class='fas fa-ranking-star' aria-hidden='true'></i>{bcm_backends}</div><div class='bcm-sec-s'>{bcm_table_sub}</div></div>";
    $h.="<span class='ak-tag' style='text-transform:none;letter-spacing:0'>{bcm_thresholds}</span></div>";
    if(count($m["rows"])==0){
        return $h."<div style='padding:0 18px 18px'><div class='bcm-empty'><i class='fas fa-chart-column' aria-hidden='true'></i>{bcm_no_backend}</div></div></div>";
    }
    $h.="<div style='overflow-x:auto'><table class='ak-tbl bcm-tbl'><thead><tr>";
    $h.="<th>#</th><th>{bcm_backend}</th><th>{bcm_hits}</th><th>{bcm_avg_rt}</th><th>{bcm_bandwidth}</th>";
    $h.="<th>{bcm_error_rate}</th><th>{bcm_retry_rate}</th><th style='text-align:right'>{bcm_active_conns}</th><th>{bcm_status}</th></tr></thead><tbody>";
    $top=$m["rows"][0]["hits"];
    $ac=bcm_accents();
    foreach($m["rows"] as $i=>$r){
        $h.=bcm_backend_row($i,$r,$m["hits"],$top,$selected,$t,$ac);
    }
    $h.="</tbody></table></div></div>";
    return $h;
}

function bcm_rate_cell($rate,float $warn,float $crit):string{
    if($rate===null){ return "<span class='bcm-mono'>&mdash;</span>"; }
    $rate=floatval($rate);
    $st=$rate>$crit?"crit":($rate>$warn?"warn":"ok");
    $w=min(100,round($rate/$crit*100,1));
    $tc=$st=="ok"?"":" bcm-t-$st";
    return "<span class='bcm-mono$tc' style='font-weight:600'>".bcm_pct($rate)."</span>"
        ."<div class='ak-bar ak-".($st=="crit"?"err":$st)." bcm-meter' title='".bcm_pct($crit)."'><span style='width:$w%'></span></div>";
}

function bcm_backend_row(int $i,array $r,float $total,float $top,string $selected,array $t,array $ac):string{
    $pal=bcm_palette();
    $color=$i<8?$pal[$i % count($pal)]:"#94a3b8";
    $share=$total>0?sprintf('%.1f%%',$r["hits"]/$total*100):"&mdash;";
    $w=$top>0?round($r["hits"]/$top*100,1):0;
    $cls="bcm-tr".($i<3?" bcm-top3":"").($r["name"]===$selected?" bcm-sel":"");
    $b=req_html($r["name"]);
    $h ="<tr class='$cls' data-b=\"$b\" tabindex='0' role='button' onclick=\"BcmSelect(this)\" onkeydown=\"if(event.key==='Enter'){BcmSelect(this);}\">";
    $h.="<td><span class='bcm-rank'>".($i+1)."</span></td>";
    $h.="<td><div style='font-weight:600'>$b<span class='bcm-selb'>{bcm_selected}</span></div><div class='bcm-mono'>".bcm_addr($r)."</div></td>";
    $h.="<td style='min-width:170px'><div style='display:flex;justify-content:space-between;gap:8px'><span class='bcm-num' style='font-weight:600'>".number_format($r["hits"])."</span><span class='bcm-mono'>$share</span></div>"
       ."<div class='ak-bar bcm-meter'><span style='width:$w%;background:$color'></span></div></td>";
    $rtc=bcm_rt_state($r["rt"])=="ok"?"":" bcm-t-".bcm_rt_state($r["rt"]);
    $h.="<td><div class='bcm-num$rtc' style='text-align:left;font-weight:600'>".bcm_ms_txt($r["rt"])."</div>"
       ."<div class='bcm-mono'>min ".bcm_ms_txt($r["min"])." &ndash; max ".bcm_ms_txt($r["max"])."</div></td>";
    $h.="<td class='bcm-num' style='text-align:left'>"._fmt_bytes(intval($r["read"]+$r["up"]))."</td>";
    $h.="<td>".bcm_rate_cell($r["err"],$t["err_warn"],$t["err_crit"])."</td>";
    $h.="<td>".bcm_rate_cell($r["retry"],$t["retry_warn"],$t["retry_crit"])."</td>";
    $h.="<td class='bcm-num' style='font-weight:600'>".number_format($r["conns"])."</td>";
    $h.="<td>".bcm_pill($r["state"])."</td>";
    $h.="</tr>";
    return $h;
}

// ── Fragment : vue d'ensemble ────────────────────────────────────

function overview_charts():void{
    $tpl=new template_admin();
    $range=_valid_range($_GET["range"]??"1h");
    $selected=(string)($_GET["backend"]??"");
    $m=bcm_model($range);

    $h=array();
    $h[]="<div class='ak bcm-wrap'>";
    $h[]=bcm_tiles($m);
    if(!$m["ok"]){
        $h[]="<div class='ak-card bcm-sec'><div class='bcm-empty'><i class='fas fa-circle-info' aria-hidden='true'></i>{no_data}</div></div>";
    }else{
        $h[]="<div class='bcm-duo'><div>".bcm_hits_card($m)."</div><div>".bcm_status_card($m)."</div></div>";
        $h[]=bcm_backends_table($m,$selected);
    }
    $h[]="</div>";
    $h[]="<script>NoSpinner();if(typeof BcmMarkRow==='function'){BcmMarkRow();}</script>";
    echo bcm_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).bcm_hits_js();
}

// ── Fragment : détail du backend choisi ──────────────────────────

/** Libellé d'un état de terminaison HAProxy (2 caractères : cause puis phase). */
function bcm_term_label(string $flag):string{
    if($flag==="--" || $flag===""){ return "{bcm_tc_normal}"; }
    $cause=array("C"=>"{bcm_tc_client}","S"=>"{bcm_tc_server}","P"=>"{bcm_tc_proxy}","R"=>"{bcm_tc_resource}","I"=>"{bcm_tc_internal}",
        "D"=>"{bcm_tc_down}","L"=>"{bcm_tc_local}","K"=>"{bcm_tc_killed}","U"=>"{bcm_tc_up}","c"=>"{bcm_tc_ctimeout}","s"=>"{bcm_tc_stimeout}");
    $phase=array("R"=>"{bcm_tp_request}","Q"=>"{bcm_tp_queue}","C"=>"{bcm_tp_connect}","H"=>"{bcm_tp_headers}","D"=>"{bcm_tp_data}",
        "L"=>"{bcm_tp_last}","T"=>"{bcm_tp_tarpit}");
    $a=substr($flag,0,1); $b=substr($flag,1,1);
    $out=array();
    if(isset($cause[$a])){ $out[]=$cause[$a]; }
    if(isset($phase[$b])){ $out[]=$phase[$b]; }
    return count($out)?implode(" ",$out):"";
}

function bcm_term_card(array $agg):string{
    $h ="<div class='ak-card bcm-sec' style='margin-bottom:0'><div class='bcm-sec-h'><div><div class='bcm-sec-t'><i class='fas fa-flag-checkered' aria-hidden='true'></i>{bcm_term}</div>"
       ."<div class='bcm-sec-s'>{bcm_term_note}</div></div></div>";
    $states=array();
    foreach($agg as $k=>$v){ $v=intval($v); if($v>0){ $states[(string)$k]=$v; } }
    if(count($states)==0){ return $h."<div class='bcm-empty'><i class='fas fa-flag-checkered' aria-hidden='true'></i>{no_data}</div></div>"; }
    arsort($states);
    $total=array_sum($states);
    $h.="<ul class='bcm-term'>";
    $n=0;
    foreach($states as $flag=>$cnt){
        if(++$n>8){ break; }
        $col="#475569";
        if($flag!=="--"){ $col=(strtolower(substr($flag,0,1))==="c")?"#d97706":"#dc2626"; }
        $pct=$cnt/$total*100;
        $h.="<li><div class='bcm-term-h'><span><span class='bcm-flag' style='color:$col'>".req_html($flag)."</span><span class='bcm-mono'>".bcm_term_label($flag)."</span></span>"
           ."<span class='bcm-num'>".number_format($cnt)." <span class='bcm-mono'>".sprintf('%.1f%%',$pct)."</span></span></div>"
           ."<div class='ak-bar bcm-meter'><span style='width:".round($pct,1)."%;background:$col'></span></div></li>";
    }
    $h.="</ul></div>";
    return $h;
}

function detail_charts():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $range=_valid_range($_GET["range"]??"1h");
    $fmt=_range_date_fmt($range);
    $liveMode=intval($_GET["live"]??0);
    $sel_backend=(string)($_GET["backend"]??"");

    // L'agrégat donne la liste, l'adresse et les états de terminaison du backend.
    $agg=bcm_api("/hacluster/connstats/backends?range=$range");
    $byName=array();
    if(is_object($agg)){
        foreach(($agg->backends??array()) as $b){
            $n=(string)($b->server_name??"");
            if($n!==""){ $byName[$n]=$b; }
        }
    }
    if(count($byName)==0){
        echo bcm_style().$tpl->_ENGINE_parse_body("<div class='ak bcm-wrap'><div class='ak-card bcm-sec'><div class='bcm-empty'><i class='fas fa-server' aria-hidden='true'></i>{bcm_no_backend}</div></div></div>")
            ._detail_nav_js($page,$liveMode);
        return;
    }
    ksort($byName);
    // Un backend choisi dans l'en-tete mais absent de cette periode garde son
    // nom (il affichera « No data ») : basculer en silence sur le premier
    // ferait mentir la liste de l'en-tete.
    if($sel_backend===""){ $sel_backend=(string)key($byName); }
    $me=isset($byName[$sel_backend])?$byName[$sel_backend]:null;
    $addr="";
    if(is_object($me) && (string)($me->server_ip??"")!==""){
        $addr=req_html((string)$me->server_ip.(intval($me->server_port??0)>0?":".intval($me->server_port):""));
    }

    $ts=bcm_api("/hacluster/connstats/backends/".rawurlencode($sel_backend)."?range=$range");
    $bucket=is_object($ts)?(string)($ts->bucket??""):"";
    $S=array("x"=>array(),"avg"=>array(),"min"=>array(),"max"=>array(),"q"=>array(),"read"=>array(),"up"=>array(),
        "act"=>array(),"srv"=>array(),"fe"=>array(),"be"=>array(),"hits"=>array());
    if(is_object($ts)){
        foreach(($ts->windows??array()) as $w){
            $t=intval($w->timestamp??0);
            $b=$w->backends[0]??null;
            $S["x"][]=$t>0?date($fmt,$t):"";
            $S["avg"][]=is_object($b)?round(floatval($b->avg_response_time_ms??0),1):0;
            $S["min"][]=is_object($b)?intval($b->min_response_time_ms??0):0;
            $S["max"][]=is_object($b)?intval($b->max_response_time_ms??0):0;
            $S["q"][]=is_object($b)?round(floatval($b->avg_queue_time_ms??0),1):0;
            $S["read"][]=is_object($b)?intval($b->total_bytes_read??0):0;
            $S["up"][]=is_object($b)?intval($b->total_bytes_uploaded??0):0;
            $S["act"][]=is_object($b)?intval($b->active_conns??0):0;
            $S["srv"][]=is_object($b)?intval($b->server_conns??0):0;
            $S["fe"][]=is_object($b)?intval($b->frontend_conns??0):0;
            $S["be"][]=is_object($b)?intval($b->backend_conns??0):0;
            $S["hits"][]=is_object($b)?intval($b->hits??0):0;
        }
    }

    $h=array();
    $h[]="<div class='ak bcm-wrap'>";
    $h[]=bcm_detail_head($sel_backend,$addr,$bucket,$liveMode);
    if(count($S["x"])==0){
        $h[]="<div class='ak-card bcm-sec'><div class='bcm-empty'><i class='fas fa-chart-area' aria-hidden='true'></i>{no_data}</div></div>";
        $h[]="</div>";
        echo bcm_style().$tpl->_ENGINE_parse_body(implode("\n",$h))._detail_nav_js($page,$liveMode);
        return;
    }
    $u=array("rt"=>"bcmrt".substr(md5(uniqid('',true)),0,8),"bw"=>"bcmbw".substr(md5(uniqid('',true)),0,8),"cn"=>"bcmcn".substr(md5(uniqid('',true)),0,8));
    $h[]=bcm_rt_card($S,$u["rt"]);
    $h[]="<div class='bcm-trio'>";
    $h[]="<div>".bcm_bw_card($S,$u["bw"])."</div>";
    $h[]="<div>".bcm_conn_card($S,$u["cn"])."</div>";
    $h[]="<div>".bcm_term_card(is_object($me)?(array)($me->termination_states??array()):array())."</div>";
    $h[]="</div></div>";
    $h[]="<script>NoSpinner();</script>";

    $labels=array();
    foreach(array("avg"=>"{bcm_avg}","max"=>"{bcm_max}","min"=>"{bcm_min}","q"=>"{bcm_queue}","dl"=>"{bcm_download}",
        "ul"=>"{bcm_upload}","act"=>"{bcm_c_active}","srv"=>"{bcm_c_server}","fe"=>"{bcm_c_frontend}","be"=>"{bcm_c_backend}") as $k=>$v){
        $labels[$k]=$tpl->_ENGINE_parse_body($v);
    }
    echo bcm_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).bcm_detail_js($S,$u,$labels)._detail_nav_js($page,$liveMode);
}

function bcm_detail_head(string $name,string $addr,string $bucket,int $live):string{
    $ac=bcm_accents();
    $h ="<div class='ak-card bcm-dh'><div><div class='bcm-dh-t'><span class='bcm-dot' style='background:{$ac["accent"]};margin:0'></span>"
       ."{bcm_detail}: ".req_html($name).($addr!==""?" <span class='bcm-mono'>($addr)</span>":"")."</div>";
    $step=bcm_step_token($bucket);
    if($step!==""){ $h.="<div class='bcm-sec-s'>$step</div>"; }
    $h.="</div>";
    $h.="<span id='bcm-live-pill' class='ak-pill ak-err'".($live?"":" style='display:none'")."><i class='fas fa-circle' aria-hidden='true'></i>{live} &middot; {bcm_live_note}</span>";
    $h.="</div>";
    return $h;
}

function bcm_rt_card(array $S,string $uid):string{
    $ac=bcm_accents();
    // Moyenne de la série pondérée par les hits de chaque fenêtre.
    $w=0; $n=0;
    foreach($S["avg"] as $i=>$v){ $w+=$v*$S["hits"][$i]; $n+=$S["hits"][$i]; }
    $avg=$n>0?bcm_ms_txt($w/$n):"&mdash;";
    $h ="<div class='ak-card bcm-sec'><div class='bcm-sec-h'><div class='bcm-sec-t'><i class='fas fa-chart-area' aria-hidden='true'></i>{bcm_response_time}</div>";
    $h.="<div class='bcm-lg'><span><span class='bcm-ln' style='border-color:{$ac["accent_dark"]}'></span>{bcm_avg} ($avg)</span>"
       ."<span><span class='bcm-ln bcm-ln-d' style='border-color:#94a3b8'></span>{bcm_bounds}</span>"
       ."<span><span class='bcm-ln' style='border-color:#f59e0b'></span>{bcm_queue}</span></div></div>";
    $h.="<div class='bcm-chart'><canvas id='$uid' role='img' aria-label='{bcm_response_time}'></canvas></div></div>";
    return $h;
}

function bcm_bw_card(array $S,string $uid):string{
    $tot=0; $peak=0;
    foreach($S["read"] as $i=>$v){ $s=$v+$S["up"][$i]; $tot+=$s; if($s>$peak){$peak=$s;} }
    $h ="<div class='ak-card bcm-sec' style='margin-bottom:0'><div class='bcm-sec-h'><div class='bcm-sec-t'><i class='fas fa-download' aria-hidden='true'></i>{bcm_bandwidth}</div>";
    $h.="<div class='bcm-lg'><span><span class='bcm-ln' style='border-color:#0d9488'></span>{bcm_download}</span><span><span class='bcm-ln bcm-ln-d' style='border-color:#0ea5e9'></span>{bcm_upload}</span></div></div>";
    $h.="<div class='bcm-chart-s'><canvas id='$uid' role='img' aria-label='{bcm_bandwidth}'></canvas></div>";
    $h.="<div class='bcm-kv'><span>{bcm_peak_window} <b>"._fmt_bytes(intval($peak))."</b></span><span>{bcm_total} <b>"._fmt_bytes(intval($tot))."</b></span></div></div>";
    return $h;
}

function bcm_conn_card(array $S,string $uid):string{
    $cur=count($S["act"])?$S["act"][count($S["act"])-1]:0;
    $h ="<div class='ak-card bcm-sec' style='margin-bottom:0'><div class='bcm-sec-h'><div class='bcm-sec-t'><i class='fas fa-plug' aria-hidden='true'></i>{bcm_conns}</div>";
    $h.="<span class='bcm-mono'>".bcm_tr("bcm_current","<b style='color:#0f172a'>".number_format($cur)."</b>")."</span></div>";
    $h.="<div class='bcm-chart-s'><canvas id='$uid' role='img' aria-label='{bcm_conns}'></canvas></div>";
    $h.="<div class='bcm-kv'><span>{bcm_conns_note}</span></div></div>";
    return $h;
}

/** Scripts des trois graphiques du détail, émis HORS du moteur de traduction. */
function bcm_detail_js(array $S,array $u,array $L):string{
    $ac=bcm_accents();
    // Mise à l'échelle du volume : une seule unité pour la série (le Live la réutilise).
    $mx=0; foreach(array_merge($S["read"],$S["up"]) as $v){ if($v>$mx){$mx=$v;} }
    $div=1; $unit="B";
    if($mx>=1073741824){ $div=1073741824; $unit="GB"; } elseif($mx>=1048576){ $div=1048576; $unit="MB"; } elseif($mx>=1024){ $div=1024; $unit="KB"; }
    $rd=array(); $up=array();
    foreach($S["read"] as $i=>$v){ $rd[]=round($v/$div,2); $up[]=round($S["up"][$i]/$div,2); }
    $cfg=array("x"=>$S["x"],"avg"=>$S["avg"],"max"=>$S["max"],"min"=>$S["min"],"q"=>$S["q"],"rd"=>$rd,"up"=>$up,
        "act"=>$S["act"],"srv"=>$S["srv"],"fe"=>$S["fe"],"be"=>$S["be"],"div"=>$div,"unit"=>$unit,"u"=>$u,"L"=>$L,
        "a"=>$ac["accent_dark"],"a1"=>bcm_rgba($ac["accent_dark"],".22"),"a0"=>bcm_rgba($ac["accent_dark"],"0"),"pr"=>count($S["x"])<3?4:0);
    $json=json_encode($cfg,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_PARTIAL_OUTPUT_ON_ERROR);
    $js=<<<'JS'
(function(){var C=__CFG__;if(typeof Chart==='undefined'){return;}
var calm=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
function ms(v){v=Number(v)||0;return v>=1000?(v/1000).toFixed(1)+' s':(v>=100?v.toFixed(0):v.toFixed(1))+' ms';}
function grad(a,z){return function(c){var r=c.chart.chartArea;if(!r){return a;}var g=c.chart.ctx.createLinearGradient(0,r.top,0,r.bottom);g.addColorStop(0,a);g.addColorStop(1,z);return g;};}
function kill(n){if(window[n]){try{window[n].destroy();}catch(e){}window[n]=null;}}
var tip={backgroundColor:'#0f172a',titleColor:'#e2e8f0',bodyColor:'#fff',padding:10,cornerRadius:6,boxPadding:4,usePointStyle:true};
var sx={grid:{display:false},border:{display:false},ticks:{color:'#94a3b8',maxRotation:0,autoSkip:true,maxTicksLimit:10}};
function sy(cb){return {beginAtZero:true,border:{display:false},grid:{color:'#f1f5f9'},ticks:{color:'#94a3b8',maxTicksLimit:5,callback:cb}};}
var base={responsive:true,maintainAspectRatio:false,animation:calm?false:{duration:600,easing:'easeOutQuart'},interaction:{mode:'index',intersect:false}};
function opts(cb,lab){return Object.assign({},base,{plugins:{legend:{display:false},datalabels:{display:false},tooltip:Object.assign({},tip,{callbacks:{label:function(c){return ' '+c.dataset.label+': '+lab(c.parsed.y);}}})},scales:{x:sx,y:sy(cb)}});}
var el=document.getElementById(C.u.rt);
if(el){kill('_bcmRtChart');window._bcmRtChart=new Chart(el,{type:'line',data:{labels:C.x.slice(),datasets:[
{label:C.L.avg,data:C.avg.slice(),borderColor:C.a,backgroundColor:grad(C.a1,C.a0),fill:'origin',borderWidth:2.5,tension:.35,pointRadius:C.pr,pointHoverRadius:5},
{label:C.L.max,data:C.max.slice(),borderColor:'#94a3b8',borderDash:[5,4],borderWidth:1.3,tension:.35,pointRadius:0,fill:false},
{label:C.L.min,data:C.min.slice(),borderColor:'#cbd5e1',borderDash:[5,4],borderWidth:1.3,tension:.35,pointRadius:0,fill:false},
{label:C.L.q,data:C.q.slice(),borderColor:'#f59e0b',borderWidth:1.6,tension:.35,pointRadius:0,fill:false}]},options:opts(function(v){return ms(v);},ms)});}
el=document.getElementById(C.u.bw);
if(el){kill('_bcmBwChart');window._bcmBwDiv=C.div;window._bcmBwUnit=C.unit;window._bcmBwChart=new Chart(el,{type:'line',data:{labels:C.x.slice(),datasets:[
{label:C.L.dl,data:C.rd.slice(),borderColor:'#0d9488',backgroundColor:grad('rgba(13,148,136,.22)','rgba(13,148,136,0)'),fill:'origin',borderWidth:2.2,tension:.35,pointRadius:C.pr},
{label:C.L.ul,data:C.up.slice(),borderColor:'#0ea5e9',borderDash:[4,3],borderWidth:1.8,tension:.35,pointRadius:0,fill:false}]},
options:opts(function(v){return v+' '+C.unit;},function(v){return Number(v).toFixed(2)+' '+C.unit;})});}
el=document.getElementById(C.u.cn);
if(el){kill('_bcmConnChart');var o=opts(function(v){return v;},function(v){return Number(v).toLocaleString();});o.plugins.legend={display:true,position:'bottom',labels:{usePointStyle:true,boxWidth:8,padding:10,font:{size:11},color:'#334155'}};
window._bcmConnChart=new Chart(el,{type:'bar',data:{labels:C.x.slice(),datasets:[
{label:C.L.act,data:C.act.slice(),backgroundColor:'#0d9488',borderRadius:3,maxBarThickness:10},
{label:C.L.srv,data:C.srv.slice(),backgroundColor:'#34d399',borderRadius:3,maxBarThickness:10},
{label:C.L.fe,data:C.fe.slice(),backgroundColor:'#0ea5e9',borderRadius:3,maxBarThickness:10},
{label:C.L.be,data:C.be.slice(),backgroundColor:'#80aaa3',borderRadius:3,maxBarThickness:10}]},options:o});}
})();
JS;
    return "<script>".str_replace("__CFG__",$json,$js)."</script>";
}

// ── Live : ajout d'un point par nouvelle fenêtre ─────────────────

function _detail_nav_js(string $page,int $liveMode):string{
    if(!$liveMode){ return ""; }
    $js=<<<'JS'
(function(){
  if(typeof _bcmLiveTimer!=='undefined'&&_bcmLiveTimer){clearInterval(_bcmLiveTimer);}
  var lastTs=0,maxPoints=60;
  function push(chart,lbl,vals){
    if(!chart||!chart.data){return;}
    chart.data.labels.push(lbl);
    for(var i=0;i<vals.length&&i<chart.data.datasets.length;i++){chart.data.datasets[i].data.push(vals[i]);}
    if(chart.data.labels.length>maxPoints){chart.data.labels.shift();chart.data.datasets.forEach(function(ds){ds.data.shift();});}
    chart.update('none');
  }
  _bcmLiveTimer=setInterval(function(){
    if(typeof _bcmLive==='undefined'||!_bcmLive){return;}
    $.ajax({url:'__PAGE__?live-poll=yes',dataType:'json',success:function(resp){
      if(!resp||!resp.success||!resp.data){return;}
      var d=resp.data;if(!d.timestamp||d.timestamp<=lastTs){return;}
      lastTs=d.timestamp;
      var sel=document.getElementById('bcm-backend-sel');if(!sel){return;}
      var f=null,bs=d.backends||[];
      for(var i=0;i<bs.length;i++){if(bs[i].server_name===sel.value){f=bs[i];break;}}
      if(!f){return;}
      var dt=new Date(d.timestamp*1000);
      var lbl=('0'+dt.getHours()).slice(-2)+':'+('0'+dt.getMinutes()).slice(-2);
      // Même ordre et mêmes unités que le rendu initial : ms pour les temps.
      push(window._bcmRtChart,lbl,[Math.round((f.avg_response_time_ms||0)*10)/10,f.max_response_time_ms||0,f.min_response_time_ms||0,Math.round((f.avg_queue_time_ms||0)*10)/10]);
      var div=window._bcmBwDiv||1;
      push(window._bcmBwChart,lbl,[Math.round((f.total_bytes_read||0)/div*100)/100,Math.round((f.total_bytes_uploaded||0)/div*100)/100]);
      push(window._bcmConnChart,lbl,[f.active_conns||0,f.server_conns||0,f.frontend_conns||0,f.backend_conns||0]);
    }});
  },30000);
})();
JS;
    return "<script>".str_replace("__PAGE__",$page,$js)."</script>";
}

// ── Live poll: proxies /hacluster/connstats/latest to JS ────────

function live_poll():void{
    header("Content-Type: application/json");
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/connstats/latest");
    $json=json_decode($raw);
    if(!is_object($json)){
        echo json_encode(["success"=>false,"error"=>"backend_unavailable"]);
        return;
    }
    echo $raw;
}
