<?php
/**
 * PostgreSQL » Metrics tab (fw.postgresql.status.php tabs): health of the built-in statistics database.
 *
 * Source: articarest apostgres/PgRrd (collected every 5 minutes from pg_stat_activity, pg_stat_database,
 * pg_database_size and pg_locks, database 'proxydb' only):
 *   /postgresql/metrics/latest       {success,data:{ts,total,active,idle,idle_tx,disabled,unknown,tx_commit_rate,
 *                                     tx_rollback_rate,tup_fetch_rate,tup_insert_rate,tup_update_rate,tup_delete_rate,
 *                                     deadlocks,cache_hit_pct,db_size_mb,waiting_locks}}
 *   /postgresql/metrics?range=X      {success,data:{range,bucket,series:[...]}} raw5m (1h) or aggregates (avg_, max_, min_ fields)
 *                                     (6h agg10m, 24h/48h hourly, 7d/30d daily, 90d monthly).
 * Rates are deltas between two samples. cache_hit_pct and deadlocks are CUMULATIVE since the PostgreSQL statistics
 * reset (pg_stat_database counters): the page says so instead of presenting them as 5-minute values.
 *
 * Redesign 2026-11-18 (stitch_postgresql_metrics_dashboard_redesign): value banner, verdict, 4 headline cards with
 * SVG sparklines, 6 explained charts. The fake "Live" mode was removed: it polled every 30 s data written every
 * 5 minutes and added the same sample ten times to the curves. Widgets pgm_, CSS classes pgm-, tokens pgm_.
 */

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
// siem_tiny_filters()/siem_tiny_filters_style(): les filtres d'en-tete. Inclusion
// INCONDITIONNELLE au premier niveau -- dans un bloc de bootstrap elle ne s'executerait
// jamais et la page mourrait sur « Call to undefined function siem_tiny_filters() ».
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors',1);ini_set('error_reporting',E_ALL);}

$users=new usersMenus();
if(!$users->AsDatabaseAdministrator){exit();}

// Collection runs every 5 minutes: three missed cycles means the collector is no longer fed.
// Declared above the dispatcher on purpose (handlers exit() before reaching a later define()).
define('PG_STALE_AFTER',900);

if(isset($_GET["main-view"])){main_view();exit;}
if(isset($_GET["body"])){pgm_body();exit;}
page();

function page():void{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("PostgreSQL &raquo; {metrics}","fas fa-chart-area","{pgm_explain}","$page?main-view=yes&range=1h");
    echo $tpl->_ENGINE_parse_body($html);
}

function pgm_range(string $r):string{
    return in_array($r,array("1h","6h","24h","48h","7d","30d","90d"),true)?$r:"1h";
}

// Tab content: a host reloaded by the period buttons of the header.
function main_view():void{
    $page=CurrentPageName();
    $range=pgm_range((string)($_GET["range"] ?? "1h"));
    echo "<div id='pgm-host'></div><script>"
        ."window.PgmRange='$range';"
        ."window.PgRange=function(r){window.PgmRange=r;PgRefresh();};"
        ."window.PgRefresh=function(){LoadAjaxSilent('pgm-host','$page?body=yes&range='+encodeURIComponent(window.PgmRange));};"
        ."PgRefresh();</script>";
}

// ---------------------------------------------------------------------------
// Model
// ---------------------------------------------------------------------------

// Translated plain text of a token ([br] → space), for str_replace and JavaScript strings.
function pgm_txt(string $token):string{
    static $cache=array();
    if(isset($cache[$token])){return $cache[$token];}
    $tpl=new template_admin();
    $t=(string)$tpl->_ENGINE_parse_body("{".$token."}");
    $t=preg_replace('#\s*<br\s*/?>\s*#i'," ",$t);
    $cache[$token]=trim($t);
    return $cache[$token];
}

// Same, decoded for JavaScript (Chart.js labels are text, not HTML).
function pgm_js(string $token):string{
    return json_encode(html_entity_decode(pgm_txt($token),ENT_QUOTES,"UTF-8"));
}

function pgm_age_text(int $s):string{
    if($s<60){return "<1 min";}
    if($s<3600){return intval($s/60)." min";}
    if($s<86400){return intval($s/3600)." h";}
    return intval($s/86400)." d";
}

function pgm_size(float $mb):string{
    if($mb>=1024){return sprintf("%.1f GB",$mb/1024);}
    if($mb>=10){return sprintf("%.0f MB",$mb);}
    return sprintf("%.1f MB",$mb);
}

function pgm_rate(float $v):string{
    if($v>=100){return number_format($v,0,".",",");}
    if($v>0 && $v<1){return sprintf("%.2f",$v);}
    return sprintf("%.1f",$v);
}

function pgm_model(string $range):array{
    $m=array("range"=>$range,"latest"=>null,"age"=>null,"stale"=>true,"series_error"=>"","bucket"=>"",
        "raw"=>true,"n"=>0,"s"=>array());
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/postgresql/metrics/latest"),true);
    if(is_array($j) && !empty($j["success"]) && is_array($j["data"] ?? null) && intval($j["data"]["ts"] ?? 0)>0){
        $m["latest"]=$j["data"];
        $m["age"]=max(0,time()-intval($j["data"]["ts"]));
        $m["stale"]=$m["age"]>PG_STALE_AFTER;
    }
    $keys=array("ts","total","active","idle","idletx","unknown","commit","rollback","fetch","ins","upd","del",
        "cache","cachemin","size","sizemax","locks","deadlocks");
    foreach($keys as $k){$m["s"][$k]=array();}
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/postgresql/metrics?range=$range"),true);
    if(!is_array($j) || empty($j["success"]) || !is_array($j["data"] ?? null)){
        $m["series_error"]=is_array($j)?(string)($j["error"] ?? ""):"no answer";
        return $m;
    }
    $m["bucket"]=(string)($j["data"]["bucket"] ?? "");
    $m["raw"]=($m["bucket"]==="raw5m" || $m["bucket"]==="");
    $series=is_array($j["data"]["series"] ?? null)?$j["data"]["series"]:array();
    foreach($series as $p){
        if(!is_array($p)){continue;}
        $r=$m["raw"];
        $f=function($rawKey,$aggKey)use($p,$r){ return floatval($r?($p[$rawKey] ?? 0):($p[$aggKey] ?? 0)); };
        $m["s"]["ts"][]=intval($p["ts"] ?? 0);
        $m["s"]["total"][]=$r?floatval($p["total"] ?? 0):floatval($p["max_total"] ?? ($p["avg_total"] ?? 0));
        $m["s"]["active"][]=$f("active","avg_active");
        $m["s"]["idle"][]=$f("idle","avg_idle");
        $m["s"]["idletx"][]=$f("idle_tx","avg_idle_tx");
        // "Other": background processes and sessions whose state is not reported (unknown, disabled).
        $tot=$r?floatval($p["total"] ?? 0):floatval($p["avg_total"] ?? 0);
        $m["s"]["unknown"][]=max(0.0,$tot-$f("active","avg_active")-$f("idle","avg_idle")-$f("idle_tx","avg_idle_tx"));
        $m["s"]["commit"][]=$f("tx_commit_rate","avg_tx_commit_rate");
        $m["s"]["rollback"][]=$f("tx_rollback_rate","avg_tx_rollback_rate");
        $m["s"]["fetch"][]=$f("tup_fetch_rate","avg_tup_fetch_rate");
        $m["s"]["ins"][]=$f("tup_insert_rate","avg_tup_insert_rate");
        $m["s"]["upd"][]=$f("tup_update_rate","avg_tup_update_rate");
        $m["s"]["del"][]=$f("tup_delete_rate","avg_tup_delete_rate");
        $m["s"]["cache"][]=$f("cache_hit_pct","avg_cache_hit_pct");
        $m["s"]["cachemin"][]=$f("cache_hit_pct","min_cache_hit_pct");
        $m["s"]["size"][]=$f("db_size_mb","avg_db_size_mb");
        $m["s"]["sizemax"][]=$f("db_size_mb","max_db_size_mb");
        $m["s"]["locks"][]=$r?floatval($p["waiting_locks"] ?? 0):floatval($p["avg_waiting_locks"] ?? 0);
        $m["s"]["deadlocks"][]=$f("deadlocks","max_deadlocks");
    }
    $m["n"]=count($m["s"]["ts"]);
    return $m;
}

// Latest value of a field: the latest sample, else the last point of the series, else null.
function pgm_now(array $m,string $field,string $serie){
    if(is_array($m["latest"]) && isset($m["latest"][$field])){return floatval($m["latest"][$field]);}
    if($m["n"]>0){return floatval(end($m["s"][$serie]));}
    return null;
}

// ---------------------------------------------------------------------------
// Widgets
// ---------------------------------------------------------------------------

function pgm_style():string{
    if(isset($GLOBALS["PGM_STYLE_DONE"])){return "";}
    $GLOBALS["PGM_STYLE_DONE"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        $accent=$a["accent"];$soft=$a["accent_soft"];$dark=$a["accent_dark"];
    }
    $c=array();
    $c[]=".pgm{display:flex;flex-direction:column;gap:20px;padding:10px 2px 20px;color:#0f172a;font-size:14px}";
    $c[]=".pgm-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
    $c[]=".pgm-alert{display:flex;align-items:center;gap:12px;padding:14px 20px;border:1px solid;border-radius:12px;font-weight:600}";
    $c[]=".pgm-alert.red{border-color:#fecaca;background:#fef2f2;color:#991b1b}.pgm-alert.amber{border-color:#fde68a;background:#fffbeb;color:#92400e}";
    $c[]=".pgm-ban{display:flex;gap:20px;padding:24px 28px;border-left:4px solid $accent}";
    $c[]=".pgm-ban-ico{flex:0 0 48px;height:48px;border-radius:10px;background:$soft;color:$dark;display:flex;align-items:center;justify-content:center;font-size:22px}";
    $c[]=".pgm-ban h2{margin:0 0 6px;font-size:20px;font-weight:600;color:#0f172a}";
    $c[]=".pgm-ban p{margin:0;line-height:1.6;color:#475569;max-width:1100px}";
    $c[]=".pgm-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}";
    $c[]=".pgm-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;background:#f1f5f9;border:1px solid #e2e8f0;color:#334155;font-size:13px}";
    $c[]=".pgm-chip b{font-family:ui-monospace,Menlo,Consolas,monospace;font-weight:600}";
    $c[]=".pgm-top{display:grid;grid-template-columns:minmax(0,35fr) minmax(0,65fr);gap:20px}";
    $c[]=".pgm-cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}";
    $c[]=".pgm-verdict{padding:20px 22px;display:flex;flex-direction:column;gap:14px}";
    $c[]=".pgm-pill{display:inline-flex;align-items:center;gap:8px;padding:4px 12px;border-radius:999px;border:1px solid;font-size:14px;font-weight:600;align-self:flex-start}";
    $c[]=".pgm-pill.ok{background:#ecfdf5;border-color:#a7f3d0;color:#047857}.pgm-pill.warn{background:#fffbeb;border-color:#fde68a;color:#b45309}";
    $c[]=".pgm-pill.bad{background:#fef2f2;border-color:#fecaca;color:#b91c1c}.pgm-pill.off{background:#f1f5f9;border-color:#cbd5e1;color:#475569}";
    $c[]=".pgm-sub{font-size:13px;color:#64748b}";
    $c[]=".pgm-ratio{display:flex;align-items:center;gap:18px}";
    $c[]=".pgm-ratio svg{flex:0 0 96px;width:96px;height:96px;transform:rotate(-90deg)}";
    $c[]=".pgm-ratio b{display:block;font-size:28px;font-weight:600;line-height:1.1}";
    $c[]=".pgm-ratio .k{font-size:13px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:$dark;margin:2px 0 4px}";
    $c[]=".pgm-kpi{padding:16px 18px;display:flex;flex-direction:column;gap:4px;min-height:190px}";
    $c[]=".pgm-kpi-h{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:4px 8px;font-size:13px;color:#64748b}";
    $c[]=".pgm-kpi-t{font-size:14px;font-weight:600;color:#0f172a}";
    $c[]=".pgm-kpi b{font-size:26px;font-weight:600;line-height:1.15}.pgm-kpi b small{font-size:14px;font-weight:500;color:#64748b;margin-left:4px}";
    $c[]=".pgm-kpi p{margin:0;font-size:13px;color:#475569;line-height:1.4}";
    $c[]=".pgm-kpi .spark{margin-top:auto;padding-top:8px}";
    $c[]=".pgm-tag{display:inline-block;margin:2px 0 4px;padding:1px 8px;border-radius:999px;font-size:13px;font-weight:600;white-space:nowrap}";
    $c[]=".pgm-tag.up{background:#ecfdf5;color:#047857}.pgm-tag.flat{background:#f1f5f9;color:#475569}.pgm-tag.down{background:#eff6ff;color:#1d4ed8}";
    $c[]=".pgm-kpi.bad{border-color:#fecaca;background:#fffafa}.pgm-kpi.bad b{color:#b91c1c}";
    $c[]=".pgm-kpi.okk b{color:#047857}";
    $c[]=".pgm-stale .pgm-kpi b,.pgm-stale .pgm-ratio b{color:#94a3b8}";
    $c[]=".pgm-lk{font-size:13px;color:#b45309;font-weight:600}";
    $c[]=".pgm-charts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}";
    $c[]=".pgm-chart{padding:18px 20px}";
    $c[]=".pgm-chart-h{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}";
    $c[]=".pgm-chart h3{margin:0;font-size:15px;font-weight:600;color:#0f172a}";
    $c[]=".pgm-chart p{margin:4px 0 0;font-size:13px;color:#64748b;line-height:1.5}";
    $c[]=".pgm-res{flex:0 0 auto;padding:2px 10px;border-radius:6px;background:#f1f5f9;border:1px solid #e2e8f0;font-size:13px;color:#475569;white-space:nowrap}";
    $c[]=".pgm-canvas{position:relative;height:280px;margin-top:12px}";
    $c[]=".pgm-empty{display:flex;flex-direction:column;align-items:center;text-align:center;gap:8px;padding:40px 24px;border-style:dashed}";
    $c[]=".pgm-empty-i{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;background:#f1f5f9;color:#64748b;margin-bottom:6px}";
    $c[]=".pgm-empty h3{margin:0;font-size:18px;font-weight:600}.pgm-empty p{margin:0;max-width:600px;color:#475569;line-height:1.6}";
    $c[]=".pgm-btn{margin-top:10px;display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;color:#0f172a;font-weight:600;font-size:14px;cursor:pointer}";
    $c[]=".pgm-btn:focus-visible{outline:2px solid $accent;outline-offset:2px}";
    $c[]="@media (max-width:1200px){.pgm-top{grid-template-columns:minmax(0,1fr)}.pgm-charts{grid-template-columns:minmax(0,1fr)}.pgm-cards{grid-template-columns:repeat(2,minmax(0,1fr))}}";
    return "<style>".implode("\n",$c)."</style>";
}

// 1. Problem banner: stale sample, or no sample at all.
function pgm_alert(array $m):string{
    if($m["latest"]===null){
        return "<div class='pgm-alert amber' role='status'><i class='fas fa-hourglass-half' aria-hidden='true'></i><span>{pgm_alert_none}</span></div>";
    }
    if(!$m["stale"]){return "";}
    $t=str_replace("%AGE%",pgm_age_text(intval($m["age"])),pgm_txt("pgm_alert_stale"));
    return "<div class='pgm-alert red' role='alert'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span>$t</span></div>";
}

// 2. Value banner.
function pgm_banner():string{
    return "<section class='pgm-card pgm-ban'><div class='pgm-ban-ico'><i class='fas fa-database' aria-hidden='true'></i></div>"
        ."<div><h2>{pgm_banner_title}</h2><p>{pgm_banner_text}</p><div class='pgm-chips'>"
        ."<span class='pgm-chip'><i class='fas fa-clock-rotate-left' aria-hidden='true'></i>{pgm_chip_5min}</span>"
        ."<span class='pgm-chip'><i class='fas fa-calendar-days' aria-hidden='true'></i>{pgm_chip_90d}</span>"
        ."<span class='pgm-chip'><i class='fas fa-eye' aria-hidden='true'></i>{pgm_chip_ro}</span>"
        ."<span class='pgm-chip'><i class='fas fa-database' aria-hidden='true'></i>{pgm_chip_db} <b>proxydb</b></span>"
        ."</div></div></section>";
}

// SVG donut a/100.
function pgm_donut(float $pct):string{
    $p=max(0,min(100,round($pct,2)));
    $col=$p>=99?"#10b981":($p>=95?"#f59e0b":"#ef4444");
    return "<svg viewBox='0 0 36 36' aria-hidden='true'><circle cx='18' cy='18' r='15.9155' fill='none' stroke='#e2e8f0' stroke-width='4'></circle>"
        ."<circle cx='18' cy='18' r='15.9155' fill='none' stroke='$col' stroke-width='4' stroke-linecap='round' stroke-dasharray='$p 100'></circle></svg>";
}

// 3a. Verdict: one sentence that says whether the database is fine.
function pgm_verdict(array $m):string{
    $locks=intval(pgm_now($m,"waiting_locks","locks") ?? 0);
    if($m["latest"]===null){
        $pill="<span class='pgm-pill off'><i class='fas fa-hourglass-half' aria-hidden='true'></i>{pgm_v_none}</span>";
        $sub="";
    }else{
        $ts=intval($m["latest"]["ts"]);
        $sub=str_replace(array("%AGE%","%T%"),array(pgm_age_text(intval($m["age"])),date("H:i",$ts)),pgm_txt("pgm_v_last"));
        $sub="<span class='pgm-sub' title='".date("Y-m-d H:i:s",$ts)."'>$sub</span>";
        if($m["stale"]){
            $pill="<span class='pgm-pill off'><i class='fas fa-clock' aria-hidden='true'></i>{pgm_v_stale}</span>";
        }elseif($locks>0){
            $t=str_replace("%N%",(string)$locks,pgm_txt($locks==1?"pgm_v_busy_1":"pgm_v_busy_n"));
            $pill="<span class='pgm-pill warn'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>$t</span>";
        }else{
            $pill="<span class='pgm-pill ok'><i class='fas fa-circle-check' aria-hidden='true'></i>{pgm_v_ok}</span>";
        }
    }
    $cache=pgm_now($m,"cache_hit_pct","cache");
    $ratio="";
    if($cache!==null){
        $label=htmlspecialchars(str_replace("%P%",sprintf("%.2f%%",$cache),pgm_txt("pgm_ratio_aria")),ENT_QUOTES,"UTF-8",false);
        $ratio="<div class='pgm-ratio' role='img' aria-label=\"$label\">".pgm_donut($cache)
            ."<div><b>".sprintf("%.2f%%",$cache)."</b><div class='k'>{pgm_ratio_title}</div><div class='pgm-sub'>{pgm_ratio_help}</div></div></div>";
    }
    return "<div class='pgm-card pgm-verdict'>$pill$sub$ratio</div>";
}

// Inline SVG sparkline (line + soft area). Empty when fewer than 2 points.
function pgm_spark(array $v,string $color):string{
    $n=count($v);
    if($n<2){return "";}
    $w=200;$h=40;
    $min=min($v);$max=max($v);
    $span=$max-$min;
    $pts=array();
    for($i=0;$i<$n;$i++){
        $x=round($i*$w/($n-1),1);
        $y=$span>0?round($h-2-(($v[$i]-$min)/$span)*($h-6),1):round($h/2,1);
        $pts[]="$x,$y";
    }
    $line=implode(" ",$pts);
    return "<div class='spark'><svg viewBox='0 0 $w $h' preserveAspectRatio='none' width='100%' height='$h' aria-hidden='true'>"
        ."<polygon points='0,$h $line $w,$h' fill='$color' fill-opacity='.12'></polygon>"
        ."<polyline points='$line' fill='none' stroke='$color' stroke-width='2' vector-effect='non-scaling-stroke'></polyline></svg></div>";
}

// 3b. Headline cards.
function pgm_card(string $meta,string $title,string $value,string $text,string $spark,string $cls=""):string{
    return "<div class='pgm-card pgm-kpi $cls'><div class='pgm-kpi-h'>$meta</div><span class='pgm-kpi-t'>$title</span>"
        ."<b>$value</b><p>$text</p>$spark</div>";
}

function pgm_card_size(array $m):string{
    $now=pgm_now($m,"db_size_mb","size");
    $val=$now===null?"&mdash;":pgm_size($now);
    $tag="";
    if($m["n"]>=2){
        $d=end($m["s"]["size"])-reset($m["s"]["size"]);
        $cls=$d>0.05?"up":($d<-0.05?"down":"flat");
        $sign=$d>0.05?"+":($d<-0.05?"&minus;":"");
        $txt=str_replace(array("%D%","%R%"),array($sign.pgm_size(abs($d)),$m["range"]),pgm_txt("pgm_growth"));
        $tag="<span class='pgm-tag $cls'>$txt</span>";
    }
    $text=($tag!==""?"$tag<br>":"")."{pgm_size_help}";
    return pgm_card("<span>{pgm_size_meta}</span>","{pgm_size_title}",$val,$text,pgm_spark($m["s"]["size"],"#2563eb"));
}

function pgm_card_conn(array $m):string{
    $tot=pgm_now($m,"total","total");
    $act=intval(pgm_now($m,"active","active") ?? 0);
    $idl=intval(pgm_now($m,"idle","idle") ?? 0);
    $peak=$m["n"]>0?intval(max($m["s"]["total"])):null;
    $meta="<span>{pgm_conn_meta}</span>".($peak!==null?"<span>".str_replace("%N%",(string)$peak,pgm_txt("pgm_peak"))."</span>":"");
    $oth=$tot===null?0:max(0,intval($tot)-$act-$idl-intval(pgm_now($m,"idle_tx","idletx") ?? 0));
    $txt=$tot===null?"":str_replace(array("%A%","%B%","%C%"),array((string)$act,(string)$idl,(string)$oth),pgm_txt($oth>0?"pgm_conn_split_other":"pgm_conn_split"));
    return pgm_card($meta,"{pgm_conn_title}",$tot===null?"&mdash;":(string)intval($tot),$txt,pgm_spark($m["s"]["total"],"#10b981"));
}

function pgm_card_commit(array $m):string{
    $c=pgm_now($m,"tx_commit_rate","commit");
    $r=pgm_now($m,"tx_rollback_rate","rollback");
    $val=$c===null?"&mdash;":pgm_rate($c)."<small>/s</small>";
    $txt=$r===null?"":str_replace("%N%",pgm_rate($r),pgm_txt("pgm_commit_cancel"));
    return pgm_card("<span>{pgm_commit_meta}</span>","{pgm_commit_title}",$val,$txt,pgm_spark($m["s"]["commit"],"#0ea5e9"));
}

function pgm_card_locks(array $m):string{
    $l=pgm_now($m,"waiting_locks","locks");
    $dl=pgm_now($m,"deadlocks","deadlocks");
    $dtxt=$dl===null?"":str_replace("%N%",number_format(intval($dl),0,".",","),pgm_txt("pgm_locks_deadlocks"));
    if($l===null){return pgm_card("<span>{pgm_locks_meta}</span>","{pgm_locks_title}","&mdash;",$dtxt,"");}
    $l=intval($l);
    if($l==0){
        return pgm_card("<span>{pgm_locks_meta}</span>","{pgm_locks_title}","{pgm_none}","{pgm_locks_ok} $dtxt","","okk");
    }
    return pgm_card("<span>{pgm_locks_meta}</span>","{pgm_locks_title}",(string)$l,"{pgm_locks_bad} $dtxt","","bad");
}

function pgm_top(array $m):string{
    $lk=$m["stale"] && $m["latest"]!==null?"<div class='pgm-lk'><i class='fas fa-clock' aria-hidden='true'></i> {pgm_last_known}</div>":"";
    $cls=$m["stale"]?" pgm-stale":"";
    return "<div class='pgm-top$cls'>".pgm_verdict($m)
        ."<div>$lk<div class='pgm-cards'>".pgm_card_size($m).pgm_card_conn($m).pgm_card_commit($m).pgm_card_locks($m)."</div></div></div>";
}

function pgm_resolution(string $bucket):string{
    $map=array("raw5m"=>"pgm_res_raw","agg10m"=>"pgm_res_10m","hourly"=>"pgm_res_hourly","daily"=>"pgm_res_daily",
        "weekly"=>"pgm_res_weekly","monthly"=>"pgm_res_monthly","quarterly"=>"pgm_res_monthly");
    return "{".($map[$bucket] ?? "pgm_res_raw")."}";
}

// 4. One chart card (canvas filled by pgm_charts_js()).
function pgm_chart_card(string $id,string $title,string $help,string $res):string{
    return "<div class='pgm-card pgm-chart'><div class='pgm-chart-h'><div><h3>{".$title."}</h3><p>{".$help."}</p></div>"
        ."<span class='pgm-res'>$res</span></div><div class='pgm-canvas'><canvas id='$id' role='img' aria-label=\"".htmlspecialchars(pgm_txt($title),ENT_QUOTES,"UTF-8",false)."\"></canvas></div></div>";
}

function pgm_charts(array $m):string{
    $res=pgm_resolution($m["bucket"]);
    return "<div class='pgm-charts'>"
        .pgm_chart_card("pgm-c-size","pgm_c_size","pgm_c_size_help",$res)
        .pgm_chart_card("pgm-c-conn","pgm_c_conn","pgm_c_conn_help",$res)
        .pgm_chart_card("pgm-c-tx","pgm_c_tx","pgm_c_tx_help",$res)
        .pgm_chart_card("pgm-c-rows","pgm_c_rows","pgm_c_rows_help",$res)
        .pgm_chart_card("pgm-c-cache","pgm_c_cache","pgm_c_cache_help",$res)
        .pgm_chart_card("pgm-c-locks","pgm_c_locks","pgm_c_locks_help",$res)
        ."</div>";
}

// 5. Empty state of the period.
function pgm_empty(array $m):string{
    $btn=$m["range"]!=="1h"?"<button type='button' class='pgm-btn' OnClick=\"PgRange('1h')\"><i class='fas fa-clock-rotate-left' aria-hidden='true'></i>{pgm_show_1h}</button>":"";
    return "<div class='pgm-card pgm-empty'><div class='pgm-empty-i'><i class='fas fa-chart-line' aria-hidden='true'></i></div>"
        ."<h3>{pgm_empty_title}</h3><p>{pgm_empty_text}</p>$btn</div>";
}

// Floats for JS without the serialize_precision noise.
function pgm_arr(array $a,int $d=2):string{
    return '['.implode(',',array_map(function($v) use($d){ return sprintf("%.".$d."f",$v); },$a)).']';
}

function pgm_labels(array $m):string{
    switch($m["range"]){
        case "7d": case "30d": case "90d": $f="M d"; break;
        case "48h": case "24h": $f="M d H:i"; break;
        default: $f="H:i";
    }
    $l=array();
    foreach($m["s"]["ts"] as $ts){$l[]=date($f,$ts);}
    return json_encode($l);
}

// Chart.js code, concatenated OUTSIDE _ENGINE_parse_body (every brace would be read as a token).
function pgm_charts_js(array $m):string{
    $s=$m["s"];$L=pgm_labels($m);$raw=$m["raw"];
    $n99=array_fill(0,$m["n"],99);
    $cmin=$m["n"]>0?min(array_merge($s["cache"],$s["cachemin"])):95;
    $ymin=max(0,min(98,floor($cmin)-1));
    $o="{responsive:true,maintainAspectRatio:false,animation:{duration:window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches?0:200},"
        ."interaction:{mode:'index',intersect:false},plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:13}}}},"
        ."scales:{x:{ticks:{maxTicksLimit:8,font:{size:13}}},y:{beginAtZero:true,ticks:{font:{size:13}}}}}";
    $ds=function($label,$data,$color,$extra="")use($L){ return "{label:$label,data:$data,borderColor:'$color',backgroundColor:'$color'+'22',tension:0.3,pointRadius:0,borderWidth:2$extra}"; };
    $js=array();
    $js[]="(function(){if(typeof Chart==='undefined'){return;}";
    $js[]="['pgm-c-size','pgm-c-conn','pgm-c-tx','pgm-c-rows','pgm-c-cache','pgm-c-locks'].forEach(function(id){var c=Chart.getChart?Chart.getChart(id):null;if(c){c.destroy();}});";
    $js[]="var O=function(){return $o;};";
    $js[]="var mk=function(id,cfg){var e=document.getElementById(id);if(e){new Chart(e,cfg);}};";
    // 1. Size
    $sz=array($ds(pgm_js("pgm_l_size"),pgm_arr($s["size"],1),"#2563eb",",fill:true"));
    if(!$raw){$sz[]=$ds(pgm_js("pgm_l_size_max"),pgm_arr($s["sizemax"],1),"#94a3b8",",borderDash:[5,4],fill:false");}
    $js[]="var o1=O();o1.scales.y.beginAtZero=false;o1.scales.y.title={display:true,text:'MB'};mk('pgm-c-size',{type:'line',data:{labels:$L,datasets:[".implode(",",$sz)."]},options:o1});";
    // 2. Connections (stacked)
    $cn=array($ds(pgm_js("pgm_l_active"),pgm_arr($s["active"],1),"#2563eb",",fill:true,stack:'c'"),
        $ds(pgm_js("pgm_l_idle"),pgm_arr($s["idle"],1),"#10b981",",fill:true,stack:'c'"),
        $ds(pgm_js("pgm_l_idletx"),pgm_arr($s["idletx"],1),"#f59e0b",",fill:true,stack:'c'"));
    $cn[]=$ds(pgm_js("pgm_l_unknown"),pgm_arr($s["unknown"],1),"#94a3b8",",fill:true,stack:'c'");
    $js[]="var o2=O();o2.scales.y.stacked=true;mk('pgm-c-conn',{type:'line',data:{labels:$L,datasets:[".implode(",",$cn)."]},options:o2});";
    // 3. Transactions (two axes)
    $js[]="var o3=O();o3.scales.y.title={display:true,text:'/s'};o3.scales.y1={beginAtZero:true,position:'right',grid:{drawOnChartArea:false},ticks:{font:{size:13}}};"
        ."mk('pgm-c-tx',{type:'line',data:{labels:$L,datasets:[".$ds(pgm_js("pgm_l_commit"),pgm_arr($s["commit"],2),"#10b981",",fill:true,yAxisID:'y'").","
        .$ds(pgm_js("pgm_l_rollback"),pgm_arr($s["rollback"],2),"#ef4444",",borderDash:[5,4],fill:false,yAxisID:'y1'")."]},options:o3});";
    // 4. Rows
    $js[]="var o4=O();o4.scales.y.title={display:true,text:'/s'};mk('pgm-c-rows',{type:'line',data:{labels:$L,datasets:["
        .$ds(pgm_js("pgm_l_fetch"),pgm_arr($s["fetch"],1),"#2563eb").",".$ds(pgm_js("pgm_l_ins"),pgm_arr($s["ins"],1),"#10b981").","
        .$ds(pgm_js("pgm_l_upd"),pgm_arr($s["upd"],1),"#f59e0b").",".$ds(pgm_js("pgm_l_del"),pgm_arr($s["del"],1),"#005447",",borderDash:[4,3]")."]},options:o4});";
    // 5. Cache, with the 99% reference drawn as a dataset (no annotation plugin is loaded)
    $ch=array($ds(pgm_js("pgm_l_cache"),pgm_arr($s["cache"],2),"#10b981",",fill:true"));
    if(!$raw){$ch[]=$ds(pgm_js("pgm_l_cache_min"),pgm_arr($s["cachemin"],2),"#f59e0b",",borderDash:[4,3],fill:false");}
    $ch[]=$ds(pgm_js("pgm_l_target"),pgm_arr($n99,0),"#ef4444",",borderDash:[6,4],borderWidth:1,fill:false");
    $js[]="var o5=O();o5.scales.y.beginAtZero=false;o5.scales.y.min=$ymin;o5.scales.y.max=100;o5.scales.y.title={display:true,text:'%'};"
        ."mk('pgm-c-cache',{type:'line',data:{labels:$L,datasets:[".implode(",",$ch)."]},options:o5});";
    // 6. Locks + deadlocks total
    $js[]="var o6=O();o6.scales.y1={beginAtZero:true,position:'right',grid:{drawOnChartArea:false},ticks:{font:{size:13},precision:0}};"
        ."mk('pgm-c-locks',{type:'line',data:{labels:$L,datasets:[".$ds(pgm_js("pgm_l_locks"),pgm_arr($s["locks"],1),"#f59e0b",",fill:true,yAxisID:'y'").","
        .$ds(pgm_js("pgm_l_deadlocks"),pgm_arr($s["deadlocks"],0),"#005447",",borderDash:[5,4],fill:false,yAxisID:'y1'")."]},options:o6});";
    $js[]="})();";
    return "<script>".implode("\n",$js)."</script>";
}

function pgm_tiny(string $range):string{
    $tpl=new template_admin();
    $fields=array();
    $fields[]=array("TYPE"=>"segment","ID"=>"pg-range","LABEL"=>"{period}","VALUE"=>$range,
        "OPTIONS"=>array("1h"=>"1h","6h"=>"6h","24h"=>"24h","48h"=>"48h","7d"=>"7d","30d"=>"30d","90d"=>"90d"),"JS"=>"PgRange");
    $fields[]=array("TYPE"=>"action","ICON"=>"fas fa-sync-alt","LABEL"=>"{refresh}","JS"=>"PgRefresh");
    $T=array();
    $T["TITLE"]="PostgreSQL &raquo; {metrics}";
    $T["ICO"]="fas fa-chart-area";
    $T["EXPL"]="{pgm_explain}";
    $T["BUTTONS"]=siem_tiny_filters($fields);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($T)))."');";
}

// Responder of the host: one model, every widget.
function pgm_body():void{
    $tpl=new template_admin();
    $range=pgm_range((string)($_GET["range"] ?? "1h"));
    $m=pgm_model($range);
    $h=array("<div class='pgm'>");
    $h[]=pgm_alert($m);
    $h[]=pgm_banner();
    $h[]=pgm_top($m);
    $h[]=$m["n"]>0?pgm_charts($m):pgm_empty($m);
    $h[]="</div>";
    echo pgm_style().siem_tiny_filters_style().$tpl->_ENGINE_parse_body(implode("\n",$h))
        .($m["n"]>0?pgm_charts_js($m):"")
        ."<script>NoSpinner();".pgm_tiny($range)."</script>";
}
