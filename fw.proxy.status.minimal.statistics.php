<?php
/**
 * MicroStats Dashboard — Proxy access statistics with Chart.js.
 * Data from /microstats/* REST API (bbolt-backed, multi-tier aggregation).
 *
 * Features: Summary widgets, top sites/users/categories charts,
 *           group-by selector (user/site/category × hits/size),
 *           drill-down into specific IPs, domains, categories.
 */
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["dashboard"]))    { render_dashboard(); exit; }
if(isset($_GET["group-chart"]))  { render_group_chart(); exit; }
if(isset($_GET["drill-chart"]))  { render_drill_chart(); exit; }
if(isset($_GET["drill-table"]))  { render_drill_table(); exit; }
if(isset($_GET["sites-detail"])) { render_sites_detail(); exit; }
if(isset($_GET["users-detail"])) { render_users_detail(); exit; }
if(isset($_GET["bw-detail"]))    { render_bw_detail(); exit; }
if(isset($_GET["content"]))      { page_content(); exit; }
page();

// ── Colors ──────────────────────────────────────────────────────────────────

function _ms_colors(){
    return array('#3498db','#e74c3c','#2ecc71','#f39c12','#9b59b6','#1abc9c','#e67e22','#34495e','#d35400','#c0392b','#16a085','#8e44ad');
}
function _ms_bg_colors(){
    return array('rgba(52,152,219,0.7)','rgba(231,76,60,0.7)','rgba(46,204,113,0.7)','rgba(243,156,18,0.7)','rgba(155,89,182,0.7)','rgba(26,188,156,0.7)','rgba(230,126,34,0.7)','rgba(52,73,94,0.7)','rgba(211,84,0,0.7)','rgba(192,57,43,0.7)','rgba(22,160,133,0.7)','rgba(142,68,173,0.7)');
}

function _ms_cat_name($id){
    if($id==='uncategorized' || $id==='') return 'Uncategorized';
    if(!is_numeric($id)) return $id;
    $catz=new mysql_catz();
    $name=$catz->CategoryIntToStr(intval($id));
    return (strlen($name)>0) ? $name : $id;
}

function _ms_format_bytes($bytes){
    if($bytes>=1073741824) return sprintf('%.1f GB',$bytes/1073741824);
    if($bytes>=1048576) return sprintf('%.1f MB',$bytes/1048576);
    if($bytes>=1024) return sprintf('%.1f KB',$bytes/1024);
    return $bytes.' B';
}

/**
 * Map a MicroStats period label to (from, to) Unix timestamps used as
 * /microstats/* `from`/`to` query parameters. Without this filter the
 * backend returns the full retention window of the tier (raw5m=15d,
 * hourly=30d, daily=90d, weekly=180d, monthly=365d), which is why old
 * load-test data ("example.com") leaked into the "today" view.
 *
 * TODO(dtouzeau): replace the stub with one of these semantics —
 *
 *  Option A — Calendar boundaries (literal label):
 *      current  : strtotime(date('Y-m-d H:00'))           → now   // top of current hour
 *      hourly   : strtotime('today')                       → now   // midnight today
 *      daily    : strtotime('monday this week 00:00')      → now
 *      weekly   : strtotime('first day of this month 00:00') → now
 *      monthly  : strtotime(date('Y').'-01-01 00:00')      → now
 *
 *  Option B — Sliding window (matches existing $hoursMap @ render_users_detail):
 *      current=now-3600, hourly=now-86400, daily=now-7*86400,
 *      weekly=now-30*86400, monthly=now-365*86400
 *
 *  Option C — Tier-aligned single bucket (sparse but precise):
 *      current=last raw5m bucket, hourly=last hourly bucket, etc.
 *
 * Returning array(0,0) keeps the legacy "no filter" behaviour.
 */
function _ms_time_range($period){
    $now=time();
    switch($period){
        case 'current': return array(strtotime(date('Y-m-d H:00:00')),                $now); // start of current hour
        case 'hourly':  return array(strtotime('today'),                               $now); // midnight today
        case 'daily':   return array(strtotime('monday this week 00:00:00'),           $now); // Monday this week
        case 'weekly':  return array(strtotime('first day of this month 00:00:00'),    $now); // 1st of this month
        case 'monthly': return array(strtotime(date('Y').'-01-01 00:00:00'),           $now); // Jan 1 this year
    }
    return array(0, 0);
}

function _ms_period_params($period){
    list($from,$to)=_ms_time_range($period);
    $q="period=".urlencode($period);
    if($from>0) $q.="&from=$from";
    if($to>0)   $q.="&to=$to";
    return $q;
}

function _ms_api($path){
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($path));
    if(!is_object($json) || !property_exists($json,'success') || !$json->success) return null;
    return $json->data;
}

// ── Main page ────────────────────────────────────────────────────────────────

/**
 * Ouverte par le menu gauche HaCluster, la page n'est l'onglet d'aucune page
 * parente: sans page_header() elle n'avait ni bandeau de titre ni #tiny-button
 * ou ecrire son TINY. Depuis l'onglet de fw.proxy.status.php, le bandeau est
 * celui du parent et le contenu se rend directement.
 */
function page(){
    if(!isset($_GET["hacluster"])){ return page_content(); }
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html=$tpl->page_header("{SQUID_STATS}","fa-solid fa-chart-pie","{microstats_explain}",
        "$page?content=yes&hacluster=yes","hacluster-statistics");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{SQUID_STATS}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// ── Tableau de bord (kit mss-, 2026-11-06) ───────────────────────────────────
//
// Cet onglet de fw.proxy.status.php suit le design des tuiles du tableau de bord
// proxy (kit pxs- de la page parente : carte blanche, bord #e2e8f0, rayon 6px,
// liseré de 3px, intitulé 11px en capitales, valeur 28px/700). Le kit est porté
// ici sous le préfixe mss- : l'onglet est une requête à part, les fonctions de
// la page parente n'y existent pas.
// Toutes les mesures viennent de MicroStats : /microstats/summary (totaux),
// /microstats/timeline (une valeur par fenêtre : sparklines et courbe),
// /microstats/top/* et /microstats/bandwidth/categories (classements).

/** Périodes proposées : clé MicroStats => libellé. Liste blanche de ?period=. */
function mss_periods():array{
    return array('current'=>'{this_hour}','hourly'=>'{today}','daily'=>'{mss_this_week}',
        'weekly'=>'{mss_this_month}','monthly'=>'{this_year}');
}

function mss_period($p):string{
    $p=trim((string)$p);
    return array_key_exists($p,mss_periods())?$p:'current';
}

/** Accents de la marque (émeraude/teal par défaut, recolorés en marque blanche). */
function mss_accents():array{
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488",
        "accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return $ac;
}

/** Teinte de chaque mesure ; la première est l'accent de la marque. */
function mss_tones():array{
    $ac=mss_accents();
    return array("requests"=>$ac["accent"],"bandwidth"=>"#005447","sites"=>"#0ea5e9",
        "members"=>"#f59e0b","latency"=>"#005447");
}

/** Palette des classements et du donut : l'accent, puis des teintes distinctes. */
function mss_palette():array{
    $ac=mss_accents();
    return array($ac["accent"],'#005447','#0ea5e9','#f59e0b','#ec4899','#005447','#14b8a6','#f97316','#64748b','#84cc16');
}

/** #rgb / #rrggbb -> rgba() ; une couleur non hexadécimale est rendue telle quelle. */
function mss_rgba(string $hex,string $alpha):string{
    $h=ltrim(trim($hex),'#');
    if(strlen($h)==3){$h=$h[0].$h[0].$h[1].$h[1].$h[2].$h[2];}
    if(!preg_match('/^[0-9a-fA-F]{6}$/',$h)){return $hex;}
    return sprintf("rgba(%d,%d,%d,%s)",hexdec(substr($h,0,2)),hexdec(substr($h,2,2)),hexdec(substr($h,4,2)),$alpha);
}

/**
 * Feuille du kit, une fois par requête, concaténée HORS de _ENGINE_parse_body()
 * (sa regex prendrait chaque corps de règle pour un {jeton}).
 */
function mss_style():string{
    if(!empty($GLOBALS["MSS_CSS_DONE"])){return "";}
    $GLOBALS["MSS_CSS_DONE"]=true;
    $ac=mss_accents();
    $a=$ac["accent"]; $ad=$ac["accent_dark"]; $as=$ac["accent_soft"];
    return "<style>
.mss-wrap{margin-top:4px}
.mss-card{transition:box-shadow .15s,border-color .15s}
.mss-card:hover{box-shadow:0 4px 12px -2px rgba(15,23,42,.08);border-color:#cbd5e1}
.mss-tiles{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:16px;margin:0 0 16px 0}
.mss-tiles4{grid-template-columns:repeat(4,minmax(0,1fr))}
.mss-tile{position:relative;display:flex;flex-direction:column;padding:12px 14px 10px 17px;min-width:0;height:100%}
.mss-tile:before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;border-radius:6px 0 0 6px;background:$a}
.mss-off.mss-tile:before{background:#94a3b8}
.mss-head{display:flex;align-items:center;justify-content:space-between;gap:8px}
.mss-label{font-size:11px;font-weight:500;line-height:14px;letter-spacing:.04em;text-transform:uppercase;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mss-chip{width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-size:13px;flex:0 0 auto}
.mss-val{font-size:28px;font-weight:700;line-height:32px;letter-spacing:-.03em;color:#0f172a;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mss-off .mss-val{color:#94a3b8}
.mss-unit{font-size:13px;font-weight:400;letter-spacing:0;color:#64748b;margin-left:4px}
.mss-spark{display:block;width:100%;height:36px;margin-top:8px}
.mss-spark-none{height:36px;margin-top:8px;border-bottom:1px dashed #e2e8f0}
.mss-foot{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:auto;padding-top:6px;height:22px;font-size:12px;line-height:16px;color:#64748b}
.mss-foot>span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0}
.mss-a{color:$ad!important;text-decoration:none!important;font-weight:500;white-space:nowrap;cursor:pointer;border-radius:3px;line-height:16px}
.mss-a:hover{text-decoration:underline!important}
.mss-a:focus-visible,.mss-row:focus-visible{outline:2px solid $ad;outline-offset:2px}
.mss-sec{padding:16px 18px;margin-bottom:16px;min-width:0}
.mss-sec-h{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px;flex-wrap:wrap}
.mss-sec-t{font-size:16px;font-weight:600;line-height:24px;letter-spacing:-.01em;color:#0f172a}
.mss-sec-t i{color:$ad;margin-right:8px}
.mss-sec-s{font-size:12px;line-height:16px;color:#64748b;margin-top:2px}
.mss-legend{display:flex;gap:10px;flex-wrap:wrap}
.mss-lg{display:flex;align-items:center;gap:10px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc}
.mss-lg-dot{width:10px;height:10px;border-radius:9999px;flex:0 0 auto}
.mss-lg-l{font-size:11px;font-weight:500;letter-spacing:.04em;text-transform:uppercase;color:#64748b;line-height:14px}
.mss-lg-v{font-size:16px;font-weight:700;color:#0f172a;line-height:20px;letter-spacing:-.01em}
.mss-chart{position:relative;height:280px}
.mss-note{font-size:12px;color:#64748b;margin-top:10px;display:flex;align-items:center;gap:6px}
.mss-empty{display:flex;align-items:center;justify-content:center;gap:10px;min-height:160px;color:#64748b;font-size:13px;background:#f8fafc;border:1px dashed #e2e8f0;border-radius:6px;text-align:center;padding:16px}
.mss-duo{display:grid;grid-template-columns:minmax(0,7fr) minmax(0,5fr);gap:16px}
.mss-rank{list-style:none;margin:0;padding:0}
.mss-row{display:grid;grid-template-columns:28px minmax(0,1fr) 92px 52px;align-items:center;gap:12px;padding:8px 6px;border-radius:6px;cursor:pointer;transition:background .15s}
.mss-row:hover{background:#f8fafc}
.mss-row+.mss-row{border-top:1px solid #f1f5f9}
.mss-pos{width:24px;height:24px;border-radius:9999px;background:#f1f5f9;color:#64748b;font-size:11px;font-weight:700;display:inline-flex;align-items:center;justify-content:center}
.mss-top3 .mss-pos{background:$as;color:$ad}
.mss-name{font-size:13px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:18px}
.mss-sub{font-size:12px;color:#64748b;line-height:16px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mss-bar{margin-top:6px}
.mss-num{font-size:13px;font-weight:700;color:#0f172a;text-align:right;white-space:nowrap}
.mss-pct{font-size:12px;color:#64748b;text-align:right;white-space:nowrap}
.mss-donut{position:relative;height:220px;margin-bottom:12px}
.mss-donut-c{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none}
.mss-donut-v{font-size:22px;font-weight:700;color:#0f172a;letter-spacing:-.02em;line-height:26px}
.mss-donut-l{font-size:11px;font-weight:500;letter-spacing:.04em;text-transform:uppercase;color:#64748b}
.mss-cat{grid-template-columns:14px minmax(0,1fr) 84px 52px}
.mss-sw{width:10px;height:10px;border-radius:3px;display:inline-block}
.mss-row.mss-static{cursor:default}
@media(max-width:1200px){.mss-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}
.mss-tiles>div:last-child:nth-child(odd){grid-column:span 2}
.mss-duo{grid-template-columns:minmax(0,1fr)}}
@media(prefers-reduced-motion:reduce){.mss-card,.mss-row{transition:none}}
</style>";
}

// ── Modèle ──

/** Lit une fois les totaux et la série de la période ; tous les widgets s'en servent. */
function mss_model(string $period):array{
    $q=_ms_period_params($period);
    $m=array("period"=>$period,"summary"=>null,"series"=>false,"step"=>0,"points"=>array());
    $s=_ms_api("/microstats/summary?$q");
    if(is_object($s) && isset($s->summary) && is_object($s->summary)){ $m["summary"]=$s->summary; }
    $t=_ms_api("/microstats/timeline?$q");
    if(is_object($t) && isset($t->points) && is_array($t->points)){
        $m["series"]=true;
        $m["step"]=intval($t->step ?? 0);
        foreach($t->points as $p){
            if(!is_object($p)){continue;}
            $m["points"][]=array("ts"=>intval($p->ts ?? 0),"hits"=>floatval($p->hits ?? 0),
                "size"=>floatval($p->size ?? 0),"rt"=>floatval($p->avg_response_time ?? 0),
                "sites"=>floatval($p->sites ?? 0),"ips"=>floatval($p->ips ?? 0));
        }
    }
    return $m;
}

/** Une colonne de la série. */
function mss_col(array $m,string $k):array{
    $out=array();
    foreach($m["points"] as $p){ $out[]=$p[$k]; }
    return $out;
}

/**
 * Libellé d'une fenêtre. Les buckets jour/semaine/mois sont posés en UTC par le
 * démon : les lire en heure locale décalerait la date d'un jour à l'ouest.
 */
function mss_ts_label(int $ts,string $period):string{
    switch($period){
        case 'current':
        case 'hourly':  return date('H:i',$ts);
        case 'daily':   return gmdate('D d',$ts);
        case 'weekly':  return gmdate('d M',$ts);
    }
    return gmdate('M Y',$ts);
}

/** « Peak at 14:05 » : la fenêtre la plus haute d'une colonne, rien sans trafic. */
function mss_peak_foot(array $m,string $k,string $token):string{
    $best=-1; $at=0;
    foreach($m["points"] as $p){
        // Une fenêtre sans requête n'a pas de temps de réponse qui compte.
        if($p["hits"]<=0){ continue; }
        if($p[$k]>$best){ $best=$p[$k]; $at=$p["ts"]; }
    }
    if($best<=0){ return ""; }
    $tpl=new template_admin();
    return str_replace("%s",mss_ts_label($at,$m["period"]),$tpl->_ENGINE_parse_body("{".$token."}"));
}

/** Sous-titre de la courbe : la taille d'une fenêtre. */
function mss_step_token(array $m):string{
    $map=array('current'=>'{mss_step_5m}','hourly'=>'{mss_step_hour}','daily'=>'{mss_step_day}',
        'weekly'=>'{mss_step_week}','monthly'=>'{mss_step_month}');
    return isset($map[$m["period"]])?$map[$m["period"]]:'{mss_step_5m}';
}

/** La dernière fenêtre est-elle encore ouverte (valeur partielle) ? */
function mss_last_open(array $m):bool{
    $n=count($m["points"]);
    if($n==0){ return false; }
    $last=$m["points"][$n-1]["ts"];
    if($m["step"]>0){ return $last+$m["step"]>time(); }
    return true; // semaine ou mois en cours
}

// ── Composants ──

/** Sparkline en SVG : aire en dégradé sous une ligne, échelle 0 → maximum. */
function mss_spark(array $vals,string $color):string{
    $n=count($vals);
    if($n<2){ return "<div class='mss-spark-none' aria-hidden='true'></div>"; }
    $max=max($vals);
    if($max<=0){ $max=1; }
    $line=array();
    foreach($vals as $i=>$v){
        $x=$i*100/($n-1);
        $y=34-($v/$max)*30;
        $line[]=sprintf('%s%.2f,%.2f',$i==0?'M':'L',$x,$y);
    }
    $d=implode(' ',$line);
    $gid='mssg'.substr(md5(uniqid('',true)),0,10);
    return "<svg class='mss-spark' viewBox='0 0 100 36' preserveAspectRatio='none' aria-hidden='true'>"
        ."<defs><linearGradient id='$gid' x1='0' y1='0' x2='0' y2='1'>"
        ."<stop offset='0' stop-color='$color' stop-opacity='.30'/><stop offset='1' stop-color='$color' stop-opacity='0'/>"
        ."</linearGradient></defs>"
        ."<path d='$d L100,36 L0,36 Z' fill='url(#$gid)'/>"
        ."<path d='$d' fill='none' stroke='$color' stroke-width='1.8' stroke-linejoin='round' stroke-linecap='round' vector-effect='non-scaling-stroke'/>"
        ."</svg>";
}

/**
 * Une tuile : intitulé, pastille d'icône, valeur, sparkline, note de pied, lien.
 * $t : LABEL, ICON, TONE, VALUE, UNIT, SPARK (array), FOOT, LINK (js), OFF.
 */
function mss_tile(array $t):string{
    $tone=$t["TONE"];
    $off=!empty($t["OFF"]);
    $unit=(isset($t["UNIT"]) && $t["UNIT"]!=="")?"<span class='mss-unit'>{$t["UNIT"]}</span>":"";
    $h ="<div class='ak-card mss-card mss-tile".($off?" mss-off":"")."'>";
    $h.="<div class='mss-head'><span class='mss-label'>{$t["LABEL"]}</span>";
    $h.="<span class='mss-chip' style='background:".mss_rgba($tone,".12").";color:$tone'><i class='{$t["ICON"]}' aria-hidden='true'></i></span></div>";
    $h.="<div class='mss-val'>{$t["VALUE"]}$unit</div>";
    if(empty($t["NOSPARK"])){ $h.=mss_spark($off?array():$t["SPARK"],$tone); }
    $link="";
    if(!$off && isset($t["LINK"]) && $t["LINK"]!==""){
        $link="<a class='mss-a' href=\"javascript:blur();\" OnClick=\"{$t["LINK"]}\">{details}&nbsp;&rsaquo;</a>";
    }
    $h.="<div class='mss-foot'><span>".(isset($t["FOOT"])?$t["FOOT"]:"")."</span>$link</div>";
    $h.="</div>";
    return $h;
}

function mss_kpi_requests(array $m):string{
    $s=$m["summary"]; $tones=mss_tones();
    return mss_tile(array("LABEL"=>"{requests}","ICON"=>"fas fa-arrow-pointer","TONE"=>$tones["requests"],
        "VALUE"=>is_object($s)?number_format(intval($s->total_hits)):"&mdash;","OFF"=>!is_object($s),
        "SPARK"=>mss_col($m,"hits"),"FOOT"=>mss_peak_foot($m,"hits","mss_peak_at")));
}

function mss_kpi_bandwidth(array $m):string{
    $s=$m["summary"]; $tones=mss_tones();
    $val="&mdash;"; $unit="";
    if(is_object($s)){
        $parts=explode(' ',_ms_format_bytes(intval($s->total_size)),2);
        $val=$parts[0]; $unit=isset($parts[1])?$parts[1]:"";
    }
    return mss_tile(array("LABEL"=>"{mss_bandwidth}","ICON"=>"fas fa-download","TONE"=>$tones["bandwidth"],
        "VALUE"=>$val,"UNIT"=>$unit,"OFF"=>!is_object($s),"SPARK"=>mss_col($m,"size"),
        "FOOT"=>mss_peak_foot($m,"size","mss_peak_at"),"LINK"=>"MsBwDetail()"));
}

function mss_kpi_sites(array $m):string{
    $s=$m["summary"]; $tones=mss_tones();
    return mss_tile(array("LABEL"=>"{websites}","ICON"=>"fas fa-globe","TONE"=>$tones["sites"],
        "VALUE"=>is_object($s)?number_format(intval($s->unique_sites)):"&mdash;","OFF"=>!is_object($s),
        "SPARK"=>mss_col($m,"sites"),"FOOT"=>mss_peak_foot($m,"sites","mss_peak_at"),"LINK"=>"MsSitesDetail()"));
}

function mss_kpi_members(array $m):string{
    $s=$m["summary"]; $tones=mss_tones();
    return mss_tile(array("LABEL"=>"{mss_members}","ICON"=>"fas fa-users","TONE"=>$tones["members"],
        "VALUE"=>is_object($s)?number_format(intval($s->unique_ips)):"&mdash;","OFF"=>!is_object($s),
        "SPARK"=>mss_col($m,"ips"),"FOOT"=>mss_peak_foot($m,"ips","mss_peak_at"),"LINK"=>"MsUsersDetail()"));
}

function mss_kpi_latency(array $m):string{
    $s=$m["summary"]; $tones=mss_tones();
    // avg_response_time est en microsecondes (même conversion que l'ancienne tuile).
    $rt=array();
    foreach(mss_col($m,"rt") as $v){ $rt[]=$v/1000; }
    return mss_tile(array("LABEL"=>"{response_time}","ICON"=>"fas fa-gauge-high","TONE"=>$tones["latency"],
        "VALUE"=>is_object($s)?number_format(intval($s->avg_response_time)/1000):"&mdash;","UNIT"=>is_object($s)?"ms":"",
        "OFF"=>!is_object($s),"SPARK"=>$rt,"FOOT"=>mss_peak_foot($m,"rt","mss_slowest_at")));
}

function mss_tiles(array $m):string{
    $t=array(mss_kpi_requests($m),mss_kpi_bandwidth($m),mss_kpi_sites($m),mss_kpi_members($m),mss_kpi_latency($m));
    return "<div class='mss-tiles'><div>".implode("</div><div>",$t)."</div></div>";
}

/** Courbe du trafic : requêtes (aire, axe gauche) et volume (ligne, axe droit). */
function mss_traffic_card(array $m):string{
    $tones=mss_tones(); $s=$m["summary"];
    $h ="<div class='ak-card mss-card mss-sec'><div class='mss-sec-h'><div>";
    $h.="<div class='mss-sec-t'><i class='fas fa-chart-area' aria-hidden='true'></i>{mss_traffic}</div>";
    $h.="<div class='mss-sec-s'>".mss_step_token($m)."</div></div>";
    if(is_object($s)){
        $h.="<div class='mss-legend'>";
        $h.="<div class='mss-lg'><span class='mss-lg-dot' style='background:{$tones["requests"]}'></span><div><div class='mss-lg-l'>{requests}</div><div class='mss-lg-v'>".number_format(intval($s->total_hits))."</div></div></div>";
        $h.="<div class='mss-lg'><span class='mss-lg-dot' style='background:{$tones["bandwidth"]}'></span><div><div class='mss-lg-l'>{mss_bandwidth}</div><div class='mss-lg-v'>"._ms_format_bytes(intval($s->total_size))."</div></div></div>";
        $h.="</div>";
    }
    $h.="</div>";
    if(!$m["series"]){
        // Sans totaux non plus, le démon ne répond pas : ce n'est pas une question de version.
        $why=is_object($s)?"{mss_series_missing}":"{no_data}";
        return $h."<div class='mss-empty'><i class='fas fa-circle-info' aria-hidden='true'></i>$why</div></div>";
    }
    if(array_sum(mss_col($m,"hits"))<=0){
        return $h."<div class='mss-empty'><i class='fas fa-chart-area' aria-hidden='true'></i>{mss_no_traffic}</div></div>";
    }
    $uid='msstl'.substr(md5(uniqid('',true)),0,10);
    $h.="<div class='mss-chart'><canvas id='$uid' role='img' aria-label='{mss_traffic}'></canvas></div>";
    if(mss_last_open($m)){
        $h.="<div class='mss-note'><i class='fas fa-ellipsis' aria-hidden='true'></i>{mss_in_progress}</div>";
    }
    $h.="</div>";
    $GLOBALS["MSS_TRAFFIC_UID"]=$uid;
    return $h;
}

/**
 * Script de la courbe, émis HORS du moteur de traduction (accolades du JS).
 * Les libellés traduits arrivent déjà résolus par $labels.
 */
function mss_traffic_js(array $m,array $labels):string{
    if(empty($GLOBALS["MSS_TRAFFIC_UID"])){ return ""; }
    $uid=$GLOBALS["MSS_TRAFFIC_UID"];
    $tones=mss_tones();
    $x=array();
    foreach($m["points"] as $p){ $x[]=mss_ts_label($p["ts"],$m["period"]); }
    $flags=JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT;
    $cfg=array("x"=>$x,"hits"=>mss_col($m,"hits"),"size"=>mss_col($m,"size"),
        "open"=>mss_last_open($m),"c1"=>$tones["requests"],"c2"=>$tones["bandwidth"],
        "a1"=>mss_rgba($tones["requests"],".28"),"z1"=>mss_rgba($tones["requests"],"0"),
        "a2"=>mss_rgba($tones["bandwidth"],".14"),"z2"=>mss_rgba($tones["bandwidth"],"0"),
        "l1"=>$labels["requests"],"l2"=>$labels["bandwidth"]);
    $json=json_encode($cfg,$flags);
    return "<script>(function(){var C=$json;var el=document.getElementById('$uid');
if(!el||typeof Chart==='undefined'){return;}
function fb(b){var u=['B','KB','MB','GB','TB'],i=0;b=Number(b)||0;while(b>=1024&&i<u.length-1){b/=1024;i++;}return (i?b.toFixed(1):b)+' '+u[i];}
function fnum(v){v=Number(v)||0;if(v>=1e6){return (v/1e6).toFixed(1)+'M';}if(v>=1e3){return (v/1e3).toFixed(1)+'k';}return String(v);}
function grad(a,z){return function(c){var r=c.chart.chartArea;if(!r){return a;}var g=c.chart.ctx.createLinearGradient(0,r.top,0,r.bottom);g.addColorStop(0,a);g.addColorStop(1,z);return g;};}
var last=C.x.length-1;var pr=C.x.length<3?4:0;
function dash(c){return (C.open&&c.p1DataIndex===last)?[5,4]:undefined;}
var calm=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
new Chart(el,{type:'line',data:{labels:C.x,datasets:[
{label:C.l1,data:C.hits,yAxisID:'y',borderColor:C.c1,backgroundColor:grad(C.a1,C.z1),fill:'origin',borderWidth:2.5,tension:.35,pointRadius:pr,pointHoverRadius:5,pointHoverBackgroundColor:'#fff',pointHoverBorderWidth:2,segment:{borderDash:dash}},
{label:C.l2,data:C.size,yAxisID:'y1',borderColor:C.c2,backgroundColor:grad(C.a2,C.z2),fill:'origin',borderWidth:2,tension:.35,pointRadius:pr,pointHoverRadius:5,pointHoverBackgroundColor:'#fff',pointHoverBorderWidth:2,segment:{borderDash:dash}}]},
options:{responsive:true,maintainAspectRatio:false,animation:calm?false:{duration:700,easing:'easeOutQuart'},
interaction:{mode:'index',intersect:false},
plugins:{legend:{display:false},tooltip:{backgroundColor:'#0f172a',titleColor:'#e2e8f0',bodyColor:'#fff',padding:10,cornerRadius:6,boxPadding:4,usePointStyle:true,
callbacks:{label:function(c){return ' '+c.dataset.label+': '+(c.datasetIndex===1?fb(c.parsed.y):Number(c.parsed.y).toLocaleString());}}}},
scales:{x:{grid:{display:false},border:{display:false},ticks:{color:'#94a3b8',maxRotation:0,autoSkip:true,maxTicksLimit:12}},
y:{beginAtZero:true,border:{display:false},grid:{color:'#f1f5f9'},ticks:{color:'#94a3b8',maxTicksLimit:5,callback:function(v){return fnum(v);}}},
y1:{beginAtZero:true,position:'right',border:{display:false},grid:{drawOnChartArea:false},ticks:{color:'#94a3b8',maxTicksLimit:5,callback:function(v){return fb(v);}}}}}});
})();</script>";
}

/** Carte du classement : type (sites, membres, catégories) × critère (requêtes, volume). */
function mss_top_card(string $requests_text):string{
    $h ="<div class='ak-card mss-card mss-sec'><div class='mss-sec-h'><div>";
    $h.="<div class='mss-sec-t'><i class='fas fa-ranking-star' aria-hidden='true'></i>{mss_top10}</div>";
    $h.="<div class='mss-sec-s'>{mss_rank_hint}</div></div>";
    $h.="</div><div id='ms-group-chart'></div></div>";
    return $h;
}

/** Donut des catégories par volume, mesuré contre le volume TOTAL de la période. */
function mss_categories_card(array $m):string{
    $h ="<div class='ak-card mss-card mss-sec'><div class='mss-sec-h'><div>";
    $h.="<div class='mss-sec-t'><i class='fas fa-chart-pie' aria-hidden='true'></i>{mss_categories}</div>";
    $h.="<div class='mss-sec-s'>{mss_top10_volume}</div></div></div>";

    $data=_ms_api("/microstats/bandwidth/categories?"._ms_period_params($m["period"])."&limit=10");
    if(!is_object($data) || !isset($data->entries) || !is_array($data->entries) || empty($data->entries)){
        return $h."<div class='mss-empty'><i class='fas fa-chart-pie' aria-hidden='true'></i>{no_data}</div></div>";
    }
    $pal=mss_palette();
    $sum=0; $rows=array();
    foreach($data->entries as $i=>$e){
        $size=floatval($e->size ?? 0);
        $sum+=$size;
        $rows[]=array("key"=>(string)($e->key ?? 'uncategorized'),"size"=>$size,"color"=>$pal[$i % count($pal)]);
    }
    $total=is_object($m["summary"])?floatval($m["summary"]->total_size):0;
    if($total<$sum){ $total=$sum; }
    // Le reste du volume forme une part « Autres » : sans elle, le donut ferait
    // croire que les dix premières catégories sont tout le trafic.
    $other=$total-$sum;

    $uid='msscat'.substr(md5(uniqid('',true)),0,10);
    $h.="<div class='mss-donut'><canvas id='$uid' role='img' aria-label='{mss_categories}'></canvas>";
    $h.="<div class='mss-donut-c'><div class='mss-donut-v'>"._ms_format_bytes(intval($total))."</div><div class='mss-donut-l'>{mss_total_volume}</div></div></div>";
    $h.="<ul class='mss-rank'>";
    foreach($rows as $r){
        $pct=$total>0?$r["size"]/$total*100:0;
        $rk=req_js($r["key"]);
        $label=req_html(_ms_cat_name($r["key"]));
        $h.="<li class='mss-row mss-cat' tabindex='0' role='button' title=\"$label\" onclick=\"MsDrill('category','$rk')\" onkeydown=\"if(event.key==='Enter'){MsDrill('category','$rk');}\">";
        $h.="<span class='mss-sw' style='background:{$r["color"]}'></span><span class='mss-name'>$label</span>";
        $h.="<span class='mss-num'>"._ms_format_bytes(intval($r["size"]))."</span><span class='mss-pct'>".sprintf('%.1f',$pct)."%</span></li>";
    }
    if($other>0){
        $h.="<li class='mss-row mss-cat mss-static'><span class='mss-sw' style='background:#cbd5e1'></span><span class='mss-name'>{mss_other}</span>";
        $h.="<span class='mss-num'>"._ms_format_bytes(intval($other))."</span><span class='mss-pct'>".sprintf('%.1f',$total>0?$other/$total*100:0)."%</span></li>";
    }
    $h.="</ul></div>";

    $vals=array(); $cols=array(); $names=array();
    foreach($rows as $r){ $vals[]=$r["size"]; $cols[]=$r["color"]; $names[]=_ms_cat_name($r["key"]); }
    if($other>0){ $vals[]=$other; $cols[]='#cbd5e1'; $names[]="{mss_other}"; }
    $GLOBALS["MSS_CAT"]=array("uid"=>$uid,"vals"=>$vals,"cols"=>$cols,"names"=>$names);
    return $h;
}

function mss_categories_js():string{
    if(empty($GLOBALS["MSS_CAT"])){ return ""; }
    $c=$GLOBALS["MSS_CAT"];
    $tpl=new template_admin();
    $names=array();
    foreach($c["names"] as $n){ $names[]=$tpl->_ENGINE_parse_body($n); }
    $flags=JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT;
    $json=json_encode(array("v"=>$c["vals"],"c"=>$c["cols"],"n"=>$names),$flags|JSON_PARTIAL_OUTPUT_ON_ERROR);
    return "<script>(function(){var D=$json;var el=document.getElementById('{$c["uid"]}');
if(!el||typeof Chart==='undefined'){return;}
function fb(b){var u=['B','KB','MB','GB','TB'],i=0;b=Number(b)||0;while(b>=1024&&i<u.length-1){b/=1024;i++;}return (i?b.toFixed(1):b)+' '+u[i];}
var calm=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
new Chart(el,{type:'doughnut',data:{labels:D.n,datasets:[{data:D.v,backgroundColor:D.c,borderColor:'#fff',borderWidth:2,borderRadius:4,spacing:1,hoverOffset:6}]},
options:{responsive:true,maintainAspectRatio:false,cutout:'74%',animation:calm?false:{animateRotate:true,duration:800,easing:'easeOutQuart'},
plugins:{legend:{display:false},tooltip:{backgroundColor:'#0f172a',padding:10,cornerRadius:6,usePointStyle:true,
callbacks:{label:function(c){return ' '+c.label+': '+fb(c.parsed);}}}}}});
})();</script>";
}

// ── Contenu de l'onglet ──

function page_content(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $period=mss_period($_GET["period"] ?? 'current');

    $TINY_ARRAY["TITLE"]   = "{SQUID_STATS}";
    $TINY_ARRAY["ICO"]     = "fa-solid fa-chart-pie";
    $TINY_ARRAY["EXPL"]    = "{microstats_explain}";
    $TINY_ARRAY["URL"]     = "proxy-status";
    if(isset($_GET["hacluster"])){
        $TINY_ARRAY["URL"]     = "hacluster-statistics";
    }
    $TINY_ARRAY["BUTTONS"] = null;

    $HaClusterClient=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient");
    if($HaClusterClient==1) {
        $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";
        echo $tpl->div_warning("{hacluster_no_statistics}");
        echo "<script>$jstiny</script>";
        return false;
    }

    // La période gouverne tout l'onglet : elle va dans l'en-tête, avec Refresh.
    $fields=array();
    $fields[]=array("TYPE"=>"segment","ID"=>"ms-period","LABEL"=>"{period}","VALUE"=>$period,
        "OPTIONS"=>mss_periods(),"JS"=>"MsPeriod");
    // Le classement se règle aussi depuis l'en-tête : la page n'a plus de
    // contrôle dans son corps. Les listes ne sont pas réémises au changement de
    // période, la sélection de l'opérateur reste donc en place.
    $fields[]=array("TYPE"=>"select","ID"=>"ms-group-type","LABEL"=>"{mss_top10}","VALUE"=>"sites",
        "OPTIONS"=>array("sites"=>"{websites}","users"=>"{mss_members}","categories"=>"{mss_categories}"),
        "JS"=>"MsGroupRefresh");
    $fields[]=array("TYPE"=>"select","ID"=>"ms-group-by","LABEL"=>"{sort_by}","VALUE"=>"hits",
        "OPTIONS"=>array("hits"=>"{requests}","size"=>"{mss_bandwidth}"),"JS"=>"MsGroupRefresh");
    $fields[]=array("TYPE"=>"action","LABEL"=>"{refresh}","ICON"=>"fas fa-sync-alt","JS"=>"MsRefresh");
    $TINY_ARRAY["BUTTONS"]=siem_tiny_filters($fields);
    $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";

    $keys=json_encode(array_keys(mss_periods()));
    $h=array();
    $h[]="<div id='ms-dashboard'></div>";
    $h[]="<script>";
    $h[]="var _msPeriod='$period';";
    $h[]="function MsPeriod(p){";
    $h[]="  var i=$keys.indexOf(p); if(i<0){return;}";
    $h[]="  _msPeriod=p;";
    $h[]="  \$('#ms-period a').removeClass('on').eq(i).addClass('on');";
    $h[]="  MsRefresh();";
    $h[]="}";
    $h[]="function MsRefresh(){";
    $h[]="  LoadAjax('ms-dashboard','$page?dashboard=yes&period='+_msPeriod);";
    $h[]="}";
    // Appelée par les listes de l'en-tête avec leur valeur : elle l'ignore et
    // relit les DEUX listes, pour qu'un seul endroit sache quels filtres existent.
    $h[]="function MsGroupRefresh(){";
    $h[]="  if(!document.getElementById('ms-group-chart')){return;}";
    $h[]="  var t=\$('#ms-group-type').val()||'sites';";
    $h[]="  var b=\$('#ms-group-by').val()||'hits';";
    $h[]="  LoadAjax('ms-group-chart','$page?group-chart=yes&period='+_msPeriod+'&type='+encodeURIComponent(t)+'&by='+encodeURIComponent(b));";
    $h[]="}";
    $h[]="MsRefresh();";
    $h[]="$jstiny";
    $h[]="</script>";

    echo siem_tiny_filters_style().$tpl->_ENGINE_parse_body(implode("\n",$h));
    return true;
}

// ── Dashboard ────────────────────────────────────────────────────────────────

function render_dashboard(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $period=mss_period($_GET["period"] ?? 'current');
    $requests_text=replace_accents($tpl->_ENGINE_parse_body("{requests}"));
    $m=mss_model($period);

    $h=array();
    $h[]="<div class='ak mss-wrap'>";
    $h[]=mss_tiles($m);
    // Le détail ouvert par une tuile s'affiche juste sous les tuiles.
    $h[]="<div id='ms-drill-area'></div>";
    $h[]=mss_traffic_card($m);
    $h[]="<div class='mss-duo'>";
    $h[]="<div>".mss_top_card($requests_text)."</div>";
    $h[]="<div>".mss_categories_card($m)."</div>";
    $h[]="</div>";
    $h[]="</div>";

    $h[]="<script>";
    $h[]="function MsDrill(dtype,key){";
    $h[]="  LoadAjax('ms-drill-area','$page?drill-chart=yes&period=$period&dtype='+dtype+'&key='+encodeURIComponent(key));";
    $h[]="}";
    $h[]="function MsDrillTable(dtype,key){";
    $h[]="  LoadAjax('ms-drill-area','$page?drill-table=yes&period=$period&dtype='+dtype+'&key='+encodeURIComponent(key));";
    $h[]="}";
    $h[]="function MsSitesDetail(){";
    $h[]="  LoadAjax('ms-drill-area','$page?sites-detail=yes&period=$period');";
    $h[]="}";
    $h[]="function MsUsersDetail(){";
    $h[]="  LoadAjax('ms-drill-area','$page?users-detail=yes&period=$period');";
    $h[]="}";
    $h[]="function MsBwDetail(){";
    $h[]="  LoadAjax('ms-drill-area','$page?bw-detail=yes&period=$period');";
    $h[]="}";
    $h[]="MsGroupRefresh();";
    $h[]="NoSpinner();";
    $h[]="</script>";

    $labels=array("requests"=>$requests_text,
        "bandwidth"=>replace_accents($tpl->_ENGINE_parse_body("{mss_bandwidth}")));
    // Les détails (drill) gardent leurs tableaux awc : la feuille accompagne le fragment.
    echo awc_table_style().mss_style().$tpl->_ENGINE_parse_body(implode("\n",$h))
        .mss_traffic_js($m,$labels).mss_categories_js();
}

// ── Classement (Top 10) ──────────────────────────────────────────────────────

function render_group_chart(){
    $tpl=new template_admin();
    $requests_text=replace_accents($tpl->_ENGINE_parse_body("{requests}"));
    $period=mss_period($_GET["period"] ?? 'current');
    $type=trim($_GET["type"] ?? 'sites');
    $by=trim($_GET["by"] ?? 'hits');

    $allowed_types=array('sites','users','categories','ips');
    $allowed_by=array('hits','size');
    if(!in_array($type,$allowed_types)) $type='sites';
    if(!in_array($by,$allowed_by)) $by='hits';

    $data=_ms_api("/microstats/top/$type?"._ms_period_params($period)."&limit=10&by=$by");
    if(!is_object($data) || !isset($data->entries) || !is_array($data->entries) || empty($data->entries)){
        echo mss_style().$tpl->_ENGINE_parse_body("<div class='mss-empty'><i class='fas fa-ranking-star' aria-hidden='true'></i>{no_data}</div>");
        return;
    }

    // La part se mesure contre le total de la période, pas contre la somme du Top 10.
    $total=0;
    $sum=_ms_api("/microstats/summary?"._ms_period_params($period));
    if(is_object($sum) && isset($sum->summary) && is_object($sum->summary)){
        $total=floatval($by==='size'?$sum->summary->total_size:$sum->summary->total_hits);
    }
    $max=0;
    foreach($data->entries as $e){
        $v=floatval($by==='size'?($e->size ?? 0):($e->hits ?? 0));
        if($v>$max){$max=$v;}
    }
    $drillType=($type==='users'||$type==='ips')?'ip':'site';
    if($type==='categories') $drillType='category';
    $ac=mss_accents();

    $h=array();
    $h[]="<ol class='mss-rank'>";
    foreach($data->entries as $i=>$e){
        $h[]=mss_rank_row($i,$e,$type,$by,$total,$max,$drillType,$requests_text,$ac);
    }
    $h[]="</ol>";
    echo mss_style().$tpl->_ENGINE_parse_body(implode("\n",$h));
}

/** Une entrée du classement : rang, nom, barre relative au premier, valeur, part du total. */
function mss_rank_row(int $i,$e,string $type,string $by,float $total,float $max,string $drillType,string $requests_text,array $ac):string{
    $rawKey=(string)($e->key ?? '?');
    $name=($type==='categories')?req_html(_ms_cat_name($rawKey)):req_html($rawKey);
    $hits=floatval($e->hits ?? 0);
    $size=floatval($e->size ?? 0);
    $v=($by==='size')?$size:$hits;
    $valTxt=($by==='size')?_ms_format_bytes(intval($size)):number_format($hits);
    $sub=($by==='size')?number_format($hits)." ".req_html($requests_text):_ms_format_bytes(intval($size));
    $w=$max>0?round($v/$max*100,1):0;
    $pct=$total>0?sprintf('%.1f%%',$v/$total*100):"&mdash;";
    $k=req_js($rawKey);
    $grad="linear-gradient(90deg,{$ac["accent_dark"]},{$ac["accent"]})";
    $cls=($i<3)?"mss-row mss-top3":"mss-row";
    $h ="<li class='$cls' tabindex='0' role='button' title=\"$name\" onclick=\"MsDrill('$drillType','$k')\" onkeydown=\"if(event.key==='Enter'){MsDrill('$drillType','$k');}\">";
    $h.="<span class='mss-pos'>".($i+1)."</span>";
    $h.="<div style='min-width:0'><div class='mss-name'>$name</div><div class='mss-sub'>$sub</div>";
    $h.="<div class='ak-bar mss-bar'><span style='width:$w%;background:$grad'></span></div></div>";
    $h.="<span class='mss-num'>$valTxt</span><span class='mss-pct'>$pct</span>";
    $h.="</li>";
    return $h;
}

// ── Drill-down charts ────────────────────────────────────────────────────────

// Pentest 2026-11-04 : cle d exploration, site, membre (donnee externe) affiche sans encodage (XSS stockee).
function render_drill_chart(){
    $tpl=new template_admin();
    $requests_text=replace_accents($tpl->_ENGINE_parse_body("{requests}"));
    $page=CurrentPageName();
    $period=trim($_GET["period"] ?? 'daily');
    $dtype=trim($_GET["dtype"] ?? '');
    $key=trim($_GET["key"] ?? '');
    if($key==='') return;

    $eKey=req_html($key);

    // Build query based on drill type
    $filter='';
    $title='';
    $icon='';
    switch($dtype){
        case 'ip':
            $filter="&ip=".urlencode($key);
            $title="<i class='fas fa-desktop'></i> $eKey";
            $icon='fas fa-desktop';
            break;
        case 'site':
            $filter="&site=".urlencode($key);
            $title="<i class='fas fa-globe'></i> $eKey";
            $icon='fas fa-globe';
            break;
        case 'category':
            // Use by-category endpoint with top_sites
            $catData=_ms_api("/microstats/by-category?"._ms_period_params($period)."&category=".urlencode($key));
            _render_category_drilldown($catData,$key,$period,$page);
            return;
        default:
            return;
    }

    // Query records for this IP or site
    $data=_ms_api("/microstats/query?"._ms_period_params($period)."$filter&limit=50");
    if(!is_object($data) || !is_array($data->records) || empty($data->records)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $records=$data->records;

    // Aggregate by the other dimension (if drilling by IP → show sites, if by site → show IPs)
    $agg=array();
    foreach($records as $r){
        if($dtype==='ip'){
            $k=$r->site ?? '?';
        } else {
            $k=$r->ip ?? '?';
            if(isset($r->user) && strlen($r->user)>0) $k=$r->user;
        }
        if(!isset($agg[$k])) $agg[$k]=array('hits'=>0,'size'=>0);
        $agg[$k]['hits']+=intval($r->hits ?? 0);
        $agg[$k]['size']+=intval($r->size ?? 0);
    }
    arsort($agg);
    $agg=array_slice($agg,0,15,true);

    $labels=array_keys($agg);
    $hitsArr=array(); $sizeArr=array();
    foreach($agg as $v){
        $hitsArr[]=$v['hits'];
        $sizeArr[]=sprintf('%.2f',$v['size']/1048576);
    }

    $labelsJS=json_encode($labels);
    $hitsJS='['.implode(',',$hitsArr).']';
    $sizeJS='['.implode(',',$sizeArr).']';
    $uid='drill-'.uniqid();
    $subLabel=($dtype==='ip')?'{websites}':'{members}';

    $h=array();
    $h[]="<div class='ibox' style='margin-top:15px'><div class='ibox-title'>";
    $h[]="<h5>$title</h5>";
    $h[]="<div class='ibox-tools'>";
    $h[]="<button class='btn btn-xs btn-default' onclick=\"\$('#ms-drill-area').html('')\"><i class='fas fa-times'></i></button>";
    $h[]="</div></div>";
    $h[]="<div class='ibox-content'>";
    $h[]="<div style='position:relative;height:320px'><canvas id='$uid'></canvas></div>";
    $h[]="</div></div>";

    // Detail table link
    $h[]="<div style='text-align:right;margin-bottom:10px'>";
    $h[]="<button class='btn btn-sm btn-default' onclick=\"MsDrillTable('$dtype','".req_js($key)."')\">";
    $h[]="<i class='fas fa-table'></i> {show_table}</button>";
    $h[]="</div>";

    $h[]="<script>";
    $h[]="new Chart(document.getElementById('$uid'),{type:'bar',";
    $h[]="data:{labels:$labelsJS,datasets:[";
    $h[]="{label:'$requests_text',data:$hitsJS,backgroundColor:'rgba(52,152,219,0.7)',borderRadius:3,yAxisID:'y'},";
    $h[]="{label:'{bandwidth} (MB)',data:$sizeJS,backgroundColor:'rgba(231,76,60,0.7)',borderRadius:3,yAxisID:'y1'}";
    $h[]="]},";
    $h[]="options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,";
    $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:12}}},";
    $h[]="scales:{y:{ticks:{font:{size:11}}},";
    $h[]="y1:{position:'top',beginAtZero:true,grid:{drawOnChartArea:false}}}}});";
    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

function _render_category_drilldown($catData,$key,$period,$page){

    $tpl=new template_admin();
    $requests_text=replace_accents($tpl->_ENGINE_parse_body("{requests}"));
    $eKey=req_html(_ms_cat_name($key));

    if(!is_object($catData) || !is_array($catData->categories) || empty($catData->categories)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $cat=$catData->categories[0];
    $totalHits=intval($cat->hits ?? 0);
    $totalSize=_ms_format_bytes(intval($cat->size ?? 0));
    $uSites=intval($cat->unique_sites ?? 0);
    $uUsers=intval($cat->unique_users ?? 0);
    $uIps=intval($cat->unique_ips ?? 0);
    $topSites=(array)($cat->top_sites ?? array());

    $h=array();
    $h[]="<div class='ibox' style='margin-top:15px'><div class='ibox-title'>";
    $h[]="<h5><i class='fas fa-tag'></i>&nbsp; {category}: $eKey</h5>";
    $h[]="<div class='ibox-tools'>";
    $h[]="<button class='btn btn-xs btn-default' onclick=\"\$('#ms-drill-area').html('')\"><i class='fas fa-times'></i></button>";
    $h[]="</div></div>";
    $h[]="<div class='ibox-content'>";

    // Mini summary
    // Mêmes tuiles que le tableau de bord, sans sparkline : la catégorie n'a pas de série.
    $tones=mss_tones();
    $mini=array(
        mss_tile(array("LABEL"=>$requests_text,"ICON"=>"fas fa-arrow-pointer","TONE"=>$tones["requests"],"VALUE"=>number_format($totalHits),"NOSPARK"=>true)),
        mss_tile(array("LABEL"=>"{mss_bandwidth}","ICON"=>"fas fa-download","TONE"=>$tones["bandwidth"],"VALUE"=>$totalSize,"NOSPARK"=>true)),
        mss_tile(array("LABEL"=>"{websites}","ICON"=>"fas fa-globe","TONE"=>$tones["sites"],"VALUE"=>number_format($uSites),"NOSPARK"=>true)),
        mss_tile(array("LABEL"=>"{mss_members}","ICON"=>"fas fa-users","TONE"=>$tones["members"],"VALUE"=>number_format(max($uUsers,$uIps)),"NOSPARK"=>true)));
    $h[]="<div class='ak mss-wrap'><div class='mss-tiles mss-tiles4'><div>".implode("</div><div>",$mini)."</div></div></div>";

    // Top sites bar
    if(!empty($topSites)){
        $labels=array(); $hitsArr=array(); $sizeArr=array();
        $colors=_ms_bg_colors();
        foreach(array_slice($topSites,0,10) as $ts){
            $labels[]=req_html($ts->site ?? $ts->key ?? '?');
            $hitsArr[]=intval($ts->hits ?? 0);
            $sizeArr[]=sprintf('%.2f',intval($ts->size ?? 0)/1048576);
        }
        $labelsJS=json_encode($labels);
        $hitsJS='['.implode(',',$hitsArr).']';
        $colorsJS="['".implode("','",array_slice($colors,0,count($labels)))."']";
        $uid='catdrill-'.uniqid();

        $h[]="<div style='position:relative;height:280px'><canvas id='$uid'></canvas></div>";
        $h[]="<script>";
        $h[]="new Chart(document.getElementById('$uid'),{type:'bar',";
        $h[]="data:{labels:$labelsJS,datasets:[{label:'$requests_text',data:$hitsJS,backgroundColor:$colorsJS,borderRadius:4}]},";
        $h[]="options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,";
        $h[]="plugins:{legend:{display:false}},";
        $h[]="scales:{x:{beginAtZero:true},y:{ticks:{font:{size:11}}}}}});";
        $h[]="</script>";
    }

    $h[]="</div></div>";
    echo mss_style().$tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Drill-down table ─────────────────────────────────────────────────────────

function render_drill_table(){
    $tpl=new template_admin();
    $requests_text=replace_accents($tpl->_ENGINE_parse_body("{requests}"));
    $period=trim($_GET["period"] ?? 'daily');
    $dtype=trim($_GET["dtype"] ?? '');
    $key=trim($_GET["key"] ?? '');
    if($key==='') return;

    $filter='';
    switch($dtype){
        case 'ip':   $filter="&ip=".urlencode($key); break;
        case 'site': $filter="&site=".urlencode($key); break;
        case 'category': $filter=""; break; // TODO: category filter not in query API
        default: return;
    }

    $data=_ms_api("/microstats/query?"._ms_period_params($period)."$filter&limit=100");
    if(!is_object($data) || !is_array($data->records) || empty($data->records)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $eKey=req_html($key);

    $h=array();
    $h[]="<div class='ibox' style='margin-top:15px'><div class='ibox-title'>";
    $h[]="<h5><i class='fas fa-table'></i>&nbsp; $eKey — {details}</h5>";
    $h[]="<div class='ibox-tools'>";
    $h[]="<button class='btn btn-xs btn-default' onclick=\"\$('#ms-drill-area').html('')\"><i class='fas fa-times'></i></button>";
    $h[]="</div></div>";
    $h[]="<div class='ibox-content' style='padding:0'>";

    $rows=array();
    foreach($data->records as $r){
        $rows[]=ms_drill_row($r);
    }
    $shown=count($rows);

    $h[]="<div class='awc-scroll'>";
    $h[]="<table class='awc-tbl'><thead><tr>";
    $h[]="<th>{ipaddr}</th>";
    $h[]="<th>{websites}</th>";
    $h[]="<th>{category}</th>";
    $h[]="<th class='center'>$requests_text</th>";
    $h[]="<th class='center'>{bandwidth}</th>";
    $h[]="<th class='center'>{response_time}</th>";
    $h[]="</tr></thead><tbody id='ms-drill-rows'>";
    $h[]=implode("\n",$rows);
    $h[]="</tbody></table></div>";
    $h[]="<div class='awc-foot'>";
    $h[]="<span>{showing} $shown</span>";
    $h[]="<span class='awc-pg' id='ms-drill-pager'></span>";
    $h[]="</div>";
    $h[]="</div></div>";
    $h[]="<script>NoSpinner();\n".implode("\n",$tpl->ICON_SCRIPTS)."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(implode("\n",$h))
        .awc_pager_js(50,"ms-drill-rows","ms-drill-pager");
}

/**
 * Une requete enregistree. L'adresse et le nom d'utilisateur tenaient deja la
 * meme cellule; ils passent en identite avec l'utilisateur en sous-titre.
 */
function ms_drill_row($r):string{
    // Pentest 2026-11-04 : ip, utilisateur, site, categorie (donnee externe) affiche sans encodage (XSS stockee).
    $rawIp=(string)($r->ip ?? '');
    $rawSite=(string)($r->site ?? '');
    $ip=req_html($rawIp);
    $user=req_html((string)($r->user ?? ''));
    $site=req_html($rawSite);
    $cat=req_html(_ms_cat_name($r->category ?? ''));
    $hits=number_format(intval($r->hits ?? 0));
    $size=_ms_format_bytes(intval($r->size ?? 0));
    $rt=sprintf('%.0f ms',intval($r->avg_rt ?? 0)/1000);

    // Le site partait dans l'attribut onclick sans echappement HTML (seul
    // addslashes etait applique): un guillemet dans le nom de domaine rompait
    // l'attribut. L'adresse, elle, etait echappee DEUX fois.
    $ipClick="onclick=\"MsDrill('ip','".req_js($rawIp)."')\" style='cursor:pointer'";
    $siteClick="onclick=\"MsDrill('site','".req_js($rawSite)."')\" style='cursor:pointer'";

    $h ="<tr>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-desktop'></i></span>";
    $h.="<span><span class='awc-name'><a href=\"javascript:blur();\" $ipClick>$ip</a></span>";
    if($user!==''){$h.="<div class='awc-meta'>$user</div>";}
    $h.="</span></div></td>";
    $h.="<td><a href=\"javascript:blur();\" class='awc-name' $siteClick>$site</a></td>";
    $h.="<td><span class='awc-badge' style='background:#34495e'>$cat</span></td>";
    $h.="<td class='center'>$hits</td>";
    $h.="<td class='center'>$size</td>";
    $h.="<td class='center awc-mono'>$rt</td>";
    $h.="</tr>";
    return $h;
}

// ── Sites detail (websites widget drill-down) ────────────────────────────────

function render_sites_detail(){
    $tpl=new template_admin();
    $requests_text=replace_accents($tpl->_ENGINE_parse_body("{requests}"));
    $page=CurrentPageName();
    $period=trim($_GET["period"] ?? 'daily');

    $data=_ms_api("/microstats/query?"._ms_period_params($period)."&limit=1000");
    if(!is_object($data) || !is_array($data->records) || empty($data->records)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $records=$data->records;

    // Aggregate by time window for timeline
    $timeBuckets=array();
    $statusCodes=array();
    $fmt='H:i';
    if($period==='daily'||$period==='weekly') $fmt='M d';
    if($period==='monthly') $fmt='M Y';

    foreach($records as $r){
        $ts=intval($r->first ?? 0);
        $bucket=date($fmt,$ts);
        if(!isset($timeBuckets[$bucket])){
            $timeBuckets[$bucket]=array('hits'=>0,'size'=>0,'ts'=>$ts);
        }
        $timeBuckets[$bucket]['hits']+=intval($r->hits ?? 0);
        $timeBuckets[$bucket]['size']+=intval($r->size ?? 0);

        $code=strval($r->status ?? 0);
        if(!isset($statusCodes[$code])) $statusCodes[$code]=0;
        $statusCodes[$code]+=intval($r->hits ?? 0);
    }

    uasort($timeBuckets,function($a,$b){ return $a['ts']-$b['ts']; });

    $labels=array_keys($timeBuckets);
    $hitsArr=array(); $sizeArr=array();
    foreach($timeBuckets as $b){
        $hitsArr[]=$b['hits'];
        $sizeArr[]=sprintf('%.2f',$b['size']/1048576);
    }

    $labelsJS=json_encode($labels);
    $hitsJS='['.implode(',',$hitsArr).']';
    $sizeJS='['.implode(',',$sizeArr).']';

    // Status code colors
    $codeColors=array(
        '200'=>'#2ecc71','204'=>'#27ae60','206'=>'#1abc9c',
        '301'=>'#3498db','302'=>'#2980b9','304'=>'#1c84c6',
        '400'=>'#f39c12','401'=>'#e67e22','403'=>'#d35400','404'=>'#e74c3c',
        '500'=>'#c0392b','502'=>'#962d22','503'=>'#7b241c',
    );
    arsort($statusCodes);
    $codeLabels=array(); $codeVals=array(); $codeClrs=array();
    $defaultClrs=_ms_colors();
    $ci=0;
    $totalHits=0;
    foreach($statusCodes as $code=>$hits){
        $codeLabels[]="HTTP $code";
        $codeVals[]=$hits;
        $totalHits+=$hits;
        $codeClrs[]=isset($codeColors[$code])?$codeColors[$code]:$defaultClrs[$ci % count($defaultClrs)];
        $ci++;
    }
    $codeLabelsJS=json_encode($codeLabels);
    $codeValsJS='['.implode(',',$codeVals).']';
    $codeClrsJS="['".implode("','",$codeClrs)."']";

    $uid1='sd-line-'.uniqid();
    $uid2='sd-codes-'.uniqid();

    $h=array();
    $h[]="<div class='ibox' style='margin-top:15px'><div class='ibox-title'>";
    $h[]="<h5><i class='fas fa-globe'></i>&nbsp; {websites} — {details}</h5>";
    $h[]="<div class='ibox-tools'>";
    $h[]="<button class='btn btn-xs btn-default' onclick=\"\$('#ms-drill-area').html('')\"><i class='fas fa-times'></i></button>";
    $h[]="</div></div>";
    $h[]="<div class='ibox-content'>";

    $h[]="";

    // Row: timeline (left 8 cols) + doughnut (right 4 cols)
    $h[]="<div class='row'>";

    // Timeline
    $h[]="<div class='col-md-8'>";
    $h[]="<h5 style='margin-top:0'><i class='fas fa-chart-line'></i> $requests_text &amp; {bandwidth}</h5>";
    $h[]="<div style='position:relative;height:280px'><canvas id='$uid1'></canvas></div>";
    $h[]="</div>";

    // Doughnut
    $h[]="<div class='col-md-4'>";
    $h[]="<h5 style='margin-top:0'><i class='fas fa-chart-pie'></i> HTTP {status}</h5>";
    $h[]="<div style='position:relative;height:200px'><canvas id='$uid2'></canvas></div>";

    // Status codes legend table
    $h[]="<table class='awc-tbl awc-tbl-compact' style='margin-top:8px'>";
    $h[]=ms_legend_head("{http_status}",$requests_text);
    $h[]="<tbody>";
    foreach($statusCodes as $code=>$hits){
        $h[]=ms_legend_row(isset($codeColors[$code])?$codeColors[$code]:'#676a6c',
            req_html($code),number_format($hits),$totalHits>0?sprintf('%.1f',$hits/$totalHits*100):'0');
    }
    $h[]="</tbody></table>";
    $h[]="</div>";
    $h[]="</div>";

    $h[]="</div></div>";

    // Charts JS
    $h[]="<script>";
    $h[]="new Chart(document.getElementById('$uid1'),{type:'line',";
    $h[]="data:{labels:$labelsJS,datasets:[";
    $h[]="{label:'$requests_text',data:$hitsJS,borderColor:'#3498db',backgroundColor:'rgba(52,152,219,0.08)',tension:0.3,fill:true,pointRadius:2,borderWidth:2,yAxisID:'y'},";
    $h[]="{label:'{bandwidth} (MB)',data:$sizeJS,borderColor:'#e74c3c',backgroundColor:'rgba(231,76,60,0.08)',tension:0.3,fill:true,pointRadius:2,borderWidth:2,yAxisID:'y1'}";
    $h[]="]},";
    $h[]="options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
    $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:12}},";
    $h[]="tooltip:{callbacks:{label:function(c){return c.datasetIndex===0?c.dataset.label+': '+c.parsed.y.toLocaleString():c.dataset.label+': '+c.parsed.y.toFixed(2)+' MB';}}}},";
    $h[]="scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:16}},";
    $h[]="y:{type:'linear',position:'left',beginAtZero:true,title:{display:true,text:'$requests_text'}},";
    $h[]="y1:{type:'linear',position:'right',beginAtZero:true,title:{display:true,text:'MB'},grid:{drawOnChartArea:false}}}}});";

    $h[]="new Chart(document.getElementById('$uid2'),{type:'doughnut',";
    $h[]="data:{labels:$codeLabelsJS,datasets:[{data:$codeValsJS,backgroundColor:$codeClrsJS,borderWidth:0,hoverOffset:8}]},";
    $h[]="options:{responsive:true,maintainAspectRatio:false,cutout:'55%',";
    $h[]="plugins:{legend:{display:false},";
    $h[]="tooltip:{callbacks:{label:function(ctx){return ctx.label+': '+ctx.parsed.toLocaleString();}}}}}});";

    $h[]="</script>";
    $h[]="<script>NoSpinner();</script>";

    echo awc_table_style().$tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Users detail (members widget drill-down) ─────────────────────────────────

function render_users_detail(){
    $tpl=new template_admin();
    $requests_text=replace_accents($tpl->_ENGINE_parse_body("{requests}"));
    $period=trim($_GET["period"] ?? 'daily');

    // Map microstats period to users-history hours
    $hoursMap=array('current'=>1,'hourly'=>24,'daily'=>168,'weekly'=>720,'monthly'=>2160);
    $hours=isset($hoursMap[$period])?$hoursMap[$period]:168;

    $chartData=_ms_api("/squid/users-history/chart?hours=$hours");
    $hasTimeline=(is_object($chartData) && is_array($chartData->labels) && !empty($chartData->labels));

    // Per-user bandwidth breakdown
    $bwData=_ms_api("/microstats/bandwidth/users?"._ms_period_params($period)."&limit=10");

    $h=array();
    $h[]="<div class='ibox' style='margin-top:15px'><div class='ibox-title'>";
    $h[]="<h5><i class='fas fa-users'></i>&nbsp; {members} — {details}</h5>";
    $h[]="<div class='ibox-tools'>";
    $h[]="<button class='btn btn-xs btn-default' onclick=\"\$('#ms-drill-area').html('')\"><i class='fas fa-times'></i></button>";
    $h[]="</div></div>";
    $h[]="<div class='ibox-content'>";

    $h[]="";

    if($hasTimeline){
        $labels=json_encode($chartData->labels);
        $users='['.implode(',',(array)$chartData->users).']';

        $hasMax=isset($chartData->max_users) && is_array($chartData->max_users);
        $uid='ud-line-'.uniqid();

        $h[]="<h5 style='margin-top:0'><i class='fas fa-chart-line'></i> {members}</h5>";
        $h[]="<div style='position:relative;height:280px'><canvas id='$uid'></canvas></div>";
        $h[]="<script>";
        $h[]="(function(){";
        $h[]="var ds=[";
        if($hasMax){
            $maxUsers='['.implode(',',(array)$chartData->max_users).']';
            $h[]="{label:'{members} (peak)',data:$maxUsers,borderColor:'rgba(230,126,34,0.35)',borderDash:[5,3],pointRadius:0,tension:0.3,fill:false,borderWidth:1.5},";
        }
        $h[]="{label:'{members}',data:$users,borderColor:'#e67e22',backgroundColor:'rgba(230,126,34,0.08)',tension:0.3,fill:true,pointRadius:2,borderWidth:2}";
        $h[]="];";
        $h[]="new Chart(document.getElementById('$uid'),{type:'line',";
        $h[]="data:{labels:$labels,datasets:ds},";
        $h[]="options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
        $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:12}}},";
        $h[]="scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:20}},";
        $h[]="y:{beginAtZero:true,title:{display:true,text:'{members}'}}}}});";
        $h[]="})();";
        $h[]="</script>";
    } else {
        $h[]=$tpl->div_info("{no_data}");
    }

    // Per-user horizontal bar
    if(is_object($bwData) && is_array($bwData->entries) && !empty($bwData->entries)){
        $uLabels=array(); $uHits=array(); $uSize=array();
        $colors=_ms_bg_colors();
        foreach($bwData->entries as $e){
            $uLabels[]=req_html($e->key ?? '?');
            $uHits[]=intval($e->hits ?? 0);
            $uSize[]=sprintf('%.2f',intval($e->size ?? 0)/1048576);
        }
        $uLabelsJS=json_encode($uLabels);
        $uHitsJS='['.implode(',',$uHits).']';
        $uSizeJS='['.implode(',',$uSize).']';
        $uColorsJS="['".implode("','",array_slice($colors,0,count($uLabels)))."']";
        $uid2='ud-bar-'.uniqid();

        $h[]="<h5 style='margin-top:20px'><i class='fas fa-chart-bar'></i> {top} {members}</h5>";
        $h[]="<div style='position:relative;height:280px'><canvas id='$uid2'></canvas></div>";
        $h[]="<script>";
        $h[]="new Chart(document.getElementById('$uid2'),{type:'bar',";
        $h[]="data:{labels:$uLabelsJS,datasets:[";
        $h[]="{label:'$requests_text',data:$uHitsJS,backgroundColor:$uColorsJS,borderRadius:3,yAxisID:'y'},";
        $h[]="{label:'{bandwidth} (MB)',data:$uSizeJS,backgroundColor:'rgba(231,76,60,0.6)',borderRadius:3,yAxisID:'y1'}";
        $h[]="]},";
        $h[]="options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,";
        $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:12}}},";
        $h[]="scales:{y:{ticks:{font:{size:11}}},";
        $h[]="y1:{position:'top',beginAtZero:true,grid:{drawOnChartArea:false}}}}});";
        $h[]="</script>";
    }

    $h[]="</div></div>";
    $h[]="<script>NoSpinner();</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Bandwidth detail (bandwidth widget drill-down) ───────────────────────────

function render_bw_detail(){
    $tpl=new template_admin();
    $period=trim($_GET["period"] ?? 'daily');

    // Fetch records for bandwidth timeline
    $data=_ms_api("/microstats/query?"._ms_period_params($period)."&limit=1000");
    $hasTimeline=(is_object($data) && is_array($data->records) && !empty($data->records));

    // Top 10 users by bandwidth
    $usersData=_ms_api("/microstats/bandwidth/users?"._ms_period_params($period)."&limit=10");

    $h=array();
    $h[]="<div class='ibox' style='margin-top:15px'><div class='ibox-title'>";
    $h[]="<h5><i class='fas fa-download'></i>&nbsp; {bandwidth} — {details}</h5>";
    $h[]="<div class='ibox-tools'>";
    $h[]="<button class='btn btn-xs btn-default' onclick=\"\$('#ms-drill-area').html('')\"><i class='fas fa-times'></i></button>";
    $h[]="</div></div>";
    $h[]="<div class='ibox-content'>";

    $h[]="";

    // ── Bandwidth timeline ──
    if($hasTimeline){
        $fmt='H:i';
        if($period==='daily'||$period==='weekly') $fmt='M d';
        if($period==='monthly') $fmt='M Y';

        $timeBuckets=array();
        foreach($data->records as $r){
            $ts=intval($r->first ?? 0);
            $bucket=date($fmt,$ts);
            if(!isset($timeBuckets[$bucket])){
                $timeBuckets[$bucket]=array('size'=>0,'ts'=>$ts);
            }
            $timeBuckets[$bucket]['size']+=intval($r->size ?? 0);
        }
        uasort($timeBuckets,function($a,$b){ return $a['ts']-$b['ts']; });

        $labels=array_keys($timeBuckets);
        $sizeArr=array();
        foreach($timeBuckets as $b){
            $sizeArr[]=sprintf('%.2f',$b['size']/1048576);
        }

        $labelsJS=json_encode($labels);
        $sizeJS='['.implode(',',$sizeArr).']';
        $uid1='bw-line-'.uniqid();

        $h[]="<div class='row'><div class='col-md-8'>";
        $h[]="<h5 style='margin-top:0'><i class='fas fa-chart-line'></i> {bandwidth}</h5>";
        $h[]="<div style='position:relative;height:280px'><canvas id='$uid1'></canvas></div>";
        $h[]="</div>";
    }

    // ── Top 10 users doughnut ──
    if(is_object($usersData) && is_array($usersData->entries) && !empty($usersData->entries)){
        $uLabels=array(); $uVals=array();
        $colors=_ms_colors();
        foreach($usersData->entries as $e){
            $uLabels[]=req_html($e->key ?? '?');
            $uVals[]=sprintf('%.2f',intval($e->size ?? 0)/1048576);
        }
        $uLabelsJS=json_encode($uLabels);
        $uValsJS='['.implode(',',$uVals).']';
        $uColorsJS="['".implode("','",array_slice($colors,0,count($uLabels)))."']";
        $uid2='bw-pie-'.uniqid();

        if($hasTimeline){ $h[]="<div class='col-md-4'>"; }
        else { $h[]="<div class='row'><div class='col-md-6 col-md-offset-3'>"; }

        $h[]="<h5 style='margin-top:0'><i class='fas fa-chart-pie'></i> {top} {members}</h5>";
        $h[]="<div style='position:relative;height:200px'><canvas id='$uid2'></canvas></div>";

        // Legend table
        $h[]="<table class='awc-tbl awc-tbl-compact' style='margin-top:8px'>";
        $h[]=ms_legend_head("{members}","{bandwidth}");
        $h[]="<tbody>";
        $totalSize=array_sum(array_map('floatval',$uVals));
        foreach($usersData->entries as $i=>$e){
            $mb=$uVals[$i];
            $h[]=ms_legend_row($colors[$i % count($colors)],
                req_html($e->key ?? '?'),"$mb MB",
                $totalSize>0?sprintf('%.1f',floatval($mb)/$totalSize*100):'0');
        }
        $h[]="</tbody></table>";
        $h[]="</div>";

        if($hasTimeline){ $h[]="</div>"; } // close row
        else { $h[]="</div>"; }
    } elseif($hasTimeline){
        $h[]="</div>"; // close row from timeline col
    }

    $h[]="</div></div>";

    // ── Charts JS ──
    $h[]="<script>";

    if($hasTimeline){
        $h[]="new Chart(document.getElementById('$uid1'),{type:'line',";
        $h[]="data:{labels:$labelsJS,datasets:[";
        $h[]="{label:'{bandwidth} (MB)',data:$sizeJS,borderColor:'#2ecc71',backgroundColor:'rgba(46,204,113,0.1)',tension:0.3,fill:true,pointRadius:2,borderWidth:2}";
        $h[]="]},";
        $h[]="options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
        $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:12}},";
        $h[]="tooltip:{callbacks:{label:function(c){return c.dataset.label+': '+c.parsed.y.toFixed(2)+' MB';}}}},";
        $h[]="scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:16}},";
        $h[]="y:{beginAtZero:true,title:{display:true,text:'MB'}}}}});";
    }

    if(isset($uid2)){
        $h[]="new Chart(document.getElementById('$uid2'),{type:'doughnut',";
        $h[]="data:{labels:$uLabelsJS,datasets:[{data:$uValsJS,backgroundColor:$uColorsJS,borderWidth:0,hoverOffset:8}]},";
        $h[]="options:{responsive:true,maintainAspectRatio:false,cutout:'55%',";
        $h[]="plugins:{legend:{display:false},";
        $h[]="tooltip:{callbacks:{label:function(ctx){return ctx.label+': '+ctx.parsed.toFixed(2)+' MB';}}}}}});";
    }

    $h[]="</script>";
    $h[]="<script>NoSpinner();</script>";

    echo awc_table_style().$tpl->_ENGINE_parse_body(implode("\n",$h));
}

/** En-tete commun aux deux legendes (codes HTTP et membres). */
function ms_legend_head($labelTitle,$valueTitle):string{
    $h ="<thead><tr>";
    $h.="<th style='width:4px;padding-left:10px;padding-right:0'></th>";
    $h.="<th>$labelTitle</th>";
    $h.="<th class='awc-right'>$valueTitle</th>";
    $h.="<th class='awc-right'>%</th>";
    $h.="</tr></thead>";
    return $h;
}

/**
 * Une entree de legende de graphique: pastille de couleur, libelle, valeur et
 * part. Les deux legendes (codes HTTP et membres) rendaient exactement le meme
 * balisage a deux endroits differents.
 */
function ms_legend_row($color,$label,$value,$pct):string{
    $h ="<tr>";
    $h.="<td style='width:4px;padding-left:10px;padding-right:0'><span class='awc-swatch' style='background:$color'></span></td>";
    $h.="<td><strong>$label</strong></td>";
    $h.="<td class='awc-right awc-nowrap'>$value</td>";
    $h.="<td class='awc-right awc-muted'>$pct%</td>";
    $h.="</tr>";
    return $h;
}
