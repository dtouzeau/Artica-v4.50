<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// api_meminfo() (psmh_model) : rien dans la chaine d'inclusion ne charge ce fichier.
include_once(dirname(__FILE__)."/ressources/class.system.meminfo.inc");

// Onglet « Processus » de fw.system.memory.config.php : mémoire consommée par
// exécutable, historisée par articarest (module PsMemHistory, base SQLite
// ps_mem.db, 7 jours au plus). Remplace l'ancienne lecture de la table PostgreSQL
// ps_mem, que plus rien n'alimentait.
$users=new usersMenus();
if(!$users->AsSystemAdministrator){exit();}

if(isset($_GET["table"])){psmh_table();exit;}
if(isset($_GET["zoom-js"])){psmh_zoom_js();exit;}
if(isset($_GET["zoom-popup"])){psmh_zoom_popup();exit;}
if(isset($_GET["zoom-chart"])){psmh_zoom_chart();exit;}

// Chargé sans paramètre comme fragment d'onglet : on tombe sur start().
start();

// Liste fermée, identique à PsMemHistory.Periods côté daemon.
function psmh_periods():array{
    return array("hour"=>"1h","today"=>"{today}","yesterday"=>"{yesterday}","24h"=>"24h","7d"=>"7d");
}
function psmh_period():string{
    $p=isset($_GET["period"])?(string)$_GET["period"]:"today";
    $all=psmh_periods();
    return isset($all[$p])?$p:"today";
}
// Nom d'exécutable tel que le daemon le stocke (même filtre que procNameRe).
function psmh_proc():string{
    $p=isset($_GET["proc"])?(string)$_GET["proc"]:"";
    if(!preg_match('/^[A-Za-z0-9._+@:-][A-Za-z0-9._+@: -]{0,127}$/',$p)){return "";}
    return $p;
}
// Appel REST : rend [données, erreur].
function psmh_api(string $uri):array{
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API($uri);
    $json=json_decode((string)$resp,true);
    if(!is_array($json)){
        $raw=trim((string)$resp);
        if($raw==""){$raw="articarest: no answer";}
        return array(null,substr($raw,0,300));
    }
    if(empty($json["success"])){
        return array(null,isset($json["error"])?(string)$json["error"]:"error");
    }
    return array(isset($json["data"])?$json["data"]:array(),"");
}
function psmh_kb($kb):string{
    return FormatBytes(intval($kb),true);
}

function start():bool{
    $page=CurrentPageName();
    $period=psmh_period();
    $html=array();
    $html[]="<div id='psmh-host' style='margin-top:10px'></div>";
    $html[]="<script>";
    // Déclarée ici, pas dans le fragment : le sélecteur de l'en-tête doit marcher dès
    // que le TINY arrive.
    $html[]="function PsmhPeriod(p){ LoadAjax('psmh-host','$page?table=yes&period='+encodeURIComponent(p)); }";
    $html[]="LoadAjax('psmh-host','$page?table=yes&period=$period');";
    $html[]="</script>";
    echo implode("\n",$html);
    return true;
}

function psmh_tiny(string $period):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $fields=array();
    $fields[]=array("TYPE"=>"segment","ID"=>"psmh-period","LABEL"=>"{psmh_period_label}","VALUE"=>$period,
        "OPTIONS"=>psmh_periods(),"JS"=>"PsmhPeriod");
    $topbuttons=array();
    $topbuttons[]=array("LoadAjax('psmh-host','$page?table=yes&period=$period');",ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{memory_info}";
    $TINY_ARRAY["ICO"]="fad fa-memory";
    $TINY_ARRAY["EXPL"]="{psmh_explain}";
    $TINY_ARRAY["URL"]="system-memory";
    $TINY_ARRAY["BUTTONS"]=siem_tiny_filters($fields,$tpl->table_buttons($topbuttons));
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

// ---------------------------------------------------------------- model

// psmh_model: one pass over the daemon, read by every widget.
function psmh_model(string $period):array{
    $m=array("err"=>"","items"=>array(),"total"=>0,"last"=>0,"first"=>0,"memtotal"=>0,"others"=>0,"listed"=>0);
    list($data,$err)=psmh_api("/system/memory/processes/top?period=$period&limit=200");
    if($err!=""){$m["err"]=$err;return $m;}
    $m["items"]=isset($data["items"])&&is_array($data["items"])?$data["items"]:array();
    $m["total"]=intval(isset($data["total_avg_kb"])?$data["total_avg_kb"]:0);
    $m["last"]=intval(isset($data["last_sample"])?$data["last_sample"]:0);
    $m["first"]=intval(isset($data["first_sample"])?$data["first_sample"]:0);
    $sum=0;
    foreach($m["items"] as $it){$sum+=intval($it["avg_kb"]);}
    $m["listed"]=$sum;
    // total_avg_kb covers EVERY executable, the list stops at 200: the rest is
    // shown as one "Other executables" line (ignored under 1 MiB, the daemon's own floor).
    if($m["total"]-$sum>=1024){$m["others"]=$m["total"]-$sum;}
    $mem=api_meminfo();
    if(!empty($mem["ok"])){$m["memtotal"]=intval($mem["MAIN"]["memtotal"]);}
    return $m;
}
function psmh_pct($kb,$total):float{
    return $total>0?round($kb*100/$total,1):0.0;
}
// Peak / average, only when the peak is at least twice the average.
function psmh_spike(int $avg,int $max):float{
    if($avg<=0){return 0.0;}
    $r=round($max/$avg,1);
    return $r>=2?$r:0.0;
}

// ---------------------------------------------------------------- table fragment

function psmh_table():bool{
    $tpl=new template_admin();
    $period=psmh_period();
    $jstiny=psmh_tiny($period);
    $m=psmh_model($period);
    if($m["err"]!=""){
        echo siem_tiny_filters_style().$tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($m["err"])))
            ."<script>NoSpinner();$jstiny</script>";
        return false;
    }
    $h=array();
    $h[]="<div class='psmh ak'>";
    $h[]=psmh_summary($m);
    if(count($m["items"])>0){
        $h[]=psmh_toolbar($m);
        $h[]=psmh_ranking($m,$period);
    }
    $h[]="</div>";
    echo siem_tiny_filters_style().psmh_style()
        .$tpl->_ENGINE_parse_body(implode("\n",$h))
        ."<script>NoSpinner();$jstiny</script>".psmh_js();
    return true;
}

// Summary band: total, top consumer, data window.
function psmh_summary(array $m):string{
    if($m["last"]==0){
        return "<section class='ak-card psmh-sum psmh-sum-empty'><i class='fas fa-hourglass-half' aria-hidden='true'></i> {collecting_data}...</section>";
    }
    if(count($m["items"])==0){
        return "<section class='ak-card psmh-sum psmh-sum-empty'>{no_data}</section>";
    }
    return "<section class='ak-card psmh-sum'>".psmh_sum_total($m).psmh_sum_top($m).psmh_sum_window($m)."</section>";
}

function psmh_sum_total(array $m):string{
    $tpl=new template_admin();
    $ram="";
    if($m["memtotal"]>0){
        $pct=psmh_pct($m["total"],$m["memtotal"]);
        $of=str_replace("%s",psmh_kb($m["memtotal"]),$tpl->_ENGINE_parse_body("{psmh_of_ram}"));
        $acc=psmh_colors()["accent"];
        $ram="<div class='psmh-ram'><span class='psmh-donut' data-peity='{&quot;fill&quot;:[&quot;$acc&quot;,&quot;#e3f0f8&quot;],&quot;innerRadius&quot;:21,&quot;radius&quot;:28}' role='img' aria-label='$pct % $of'>".min($m["total"],$m["memtotal"])."/".$m["memtotal"]."</span>"
            ."<div class='psmh-ram-t'><b>$pct %</b> $of</div></div>";
    }
    return "<div class='psmh-z psmh-z-total'><div><div class='psmh-big'>".psmh_kb($m["total"])."</div>"
        ."<div class='psmh-cap'>{psmh_total_caption}</div></div>$ram</div>";
}

function psmh_sum_top(array $m):string{
    $top=$m["items"][0];
    $pct=psmh_pct(intval($top["avg_kb"]),$m["total"]);
    return "<div class='psmh-z psmh-z-top'><div class='ak-fig-l'>{psmh_top_consumer}</div>"
        ."<div class='psmh-top-n'><span>".htmlspecialchars((string)$top["proc"])."</span>"
        ."<span class='ak-tag ak-ok'>$pct % {psmh_of_process_memory}</span></div>"
        ."<div class='psmh-cap'>{psmh_average_size}: <b>".psmh_kb(intval($top["avg_kb"]))."</b></div></div>";
}

function psmh_sum_window(array $m):string{
    $tpl=new template_admin();
    $h="<div class='psmh-z psmh-z-win'>";
    $h.="<div class='psmh-cap'>{last_sample}: <b>".htmlspecialchars($tpl->time_to_date($m["last"],true))."</b></div>";
    if($m["first"]>0){
        $h.="<div class='psmh-cap'>{psmh_data_since}: <b>".htmlspecialchars($tpl->time_to_date($m["first"],true))."</b></div>";
    }
    $h.="<div class='ak-tag psmh-badge psmh-note'><i class='fas fa-clock' aria-hidden='true'></i> {psmh_sampled_every}</div>";
    return $h."</div>";
}

function psmh_toolbar(array $m):string{
    $n=count($m["items"]);
    return "<div class='psmh-bar'><label class='ak-search psmh-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
        ."<input type='search' id='psmh-q' placeholder='{psmh_search}' aria-label='{psmh_search}' oninput=\"PsmhApply(1);\"></label>"
        ."<div class='psmh-count'>{showing} <b id='psmh-shown'>".min(25,$n)."</b> / <b id='psmh-total'>$n</b> {processes}</div></div>";
}

function psmh_ranking(array $m,string $period):string{
    $h=array();
    $h[]="<div class='ak-card'><div class='psmh-scroll'><table class='ak-tbl psmh-tbl'>";
    $h[]="<thead><tr><th class='psmh-c-rank'>#</th><th>{process}</th>";
    $h[]="<th>{share} <i class='fas fa-circle-info' title='{psmh_share_help}' aria-label='{psmh_share_help}'></i></th>";
    $h[]="<th class='ak-num ak-nowrap'>{average}</th><th class='ak-num ak-nowrap'>{peak}</th><th class='ak-num ak-nowrap'>{last_sample}</th><th class='psmh-c-go'></th></tr></thead>";
    $h[]="<tbody id='psmh-rows'>";
    $rank=0;
    foreach($m["items"] as $it){
        $rank++;
        $h[]=psmh_row($it,$m["total"],$period,$rank);
    }
    $h[]="</tbody>";
    $h[]="<tbody id='psmh-other'>".psmh_row_other($m)."</tbody>";
    $h[]="<tbody><tr class='psmh-nomatch' id='psmh-nomatch'><td colspan='7'>{no_data}</td></tr></tbody>";
    $h[]="</table></div>";
    $h[]="<div class='ak-card-f'><span id='psmh-range'></span><span class='ak-pg' id='psmh-pager'></span></div>";
    $h[]="</div>";
    return implode("\n",$h);
}

// One row of the ranking.
function psmh_row(array $it,int $total,string $period,int $rank):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $proc=(string)$it["proc"];
    $avg=intval($it["avg_kb"]);
    $max=intval($it["max_kb"]);
    $lastkb=intval($it["last_kb"]);
    $nprocs=intval($it["max_procs"]);
    $pct=psmh_pct($avg,$total);
    $js="Loadjs('$page?zoom-js=yes&proc=".rawurlencode($proc)."&period=$period');";
    $name=htmlspecialchars($proc,ENT_QUOTES);
    $aria=htmlspecialchars(str_replace("%s",$proc,$tpl->_ENGINE_parse_body("{psmh_open_history}")),ENT_QUOTES);

    $count=$nprocs==1?"1 {psmh_process_one}":"$nprocs {processes}";
    $off=$lastkb>0?"":"<span class='ak-tag ak-off psmh-badge'><i class='fas fa-power-off' aria-hidden='true'></i>{psmh_not_running}</span>";
    $lastTxt=$lastkb>0?psmh_kb($lastkb):"<span class='ak-muted'>-</span>";
    $peak=psmh_kb($max);
    $sp=psmh_spike($avg,$max);
    if($sp>0){
        $say=htmlspecialchars(str_replace("%s",(string)$sp,$tpl->_ENGINE_parse_body("{psmh_peak_ratio}")),ENT_QUOTES);
        $peak.=" <span class='ak-tag ak-warn psmh-badge' title='$say' aria-label='$say'><i class='fas fa-arrow-trend-up' aria-hidden='true'></i>x$sp</span>";
    }
    $h="<tr class='psmh-row' data-name='".htmlspecialchars(strtolower($proc),ENT_QUOTES)."' tabindex='0' role='button' aria-label='$aria' OnClick=\"$js\" onkeydown=\"if(event.key==='Enter')this.click();\">";
    $h.="<td class='psmh-c-rank'>$rank</td>";
    $h.="<td><div class='psmh-name'><span>$name</span>$off</div><div class='ak-help'>$count</div></td>";
    $h.="<td class='psmh-c-share'><div class='psmh-sh'><div class='ak-bar psmh-track'><span style='width:".min(100,$pct)."%'></span></div><span class='psmh-pct'>$pct%</span></div></td>";
    $h.="<td class='ak-num ak-nowrap psmh-avg'>".psmh_kb($avg)."</td>";
    $h.="<td class='ak-num ak-nowrap'>$peak</td>";
    $h.="<td class='ak-num ak-nowrap'>$lastTxt</td>";
    $h.="<td class='psmh-c-go'><i class='fas fa-chevron-right' aria-hidden='true'></i></td>";
    return $h."</tr>";
}

// Everything the 200-line list does not show, as one line.
function psmh_row_other(array $m):string{
    if($m["others"]<=0){return "";}
    $pct=psmh_pct($m["others"],$m["total"]);
    return "<tr class='psmh-other'><td class='psmh-c-rank'>-</td><td><span class='psmh-other-n'>{psmh_other}</span></td>"
        ."<td class='psmh-c-share'><div class='psmh-sh'><div class='ak-bar ak-off psmh-track'><span style='width:".min(100,$pct)."%'></span></div><span class='psmh-pct'>$pct%</span></div></td>"
        ."<td class='ak-num ak-nowrap'>".psmh_kb($m["others"])."</td><td class='ak-num ak-nowrap ak-muted'>-</td><td class='ak-num ak-nowrap ak-muted'>-</td><td></td></tr>";
}

// Search + pager: filter the rows, then page through what matches (25 per page).
function psmh_js():string{
    $js=<<<'JS'
<script>
(function(){
 window.PsmhPage=1;
 window.PsmhApply=function(page){
  var tb=document.getElementById('psmh-rows'); if(!tb){return;}
  var q=((document.getElementById('psmh-q')||{}).value||'').toLowerCase().trim();
  var rows=tb.rows, match=[], size=25, i;
  for(i=0;i<rows.length;i++){ if(q==='' || rows[i].getAttribute('data-name').indexOf(q)>-1){ match.push(rows[i]); } }
  var pages=Math.max(1,Math.ceil(match.length/size));
  if(page){ window.PsmhPage=page; }
  if(window.PsmhPage>pages){ window.PsmhPage=pages; }
  var cur=window.PsmhPage;
  for(i=0;i<rows.length;i++){ rows[i].style.display='none'; }
  for(i=(cur-1)*size;i<Math.min(match.length,cur*size);i++){ match[i].style.display=''; }
  var other=document.getElementById('psmh-other'); if(other){ other.style.display=(q==='')?'':'none'; }
  var nm=document.getElementById('psmh-nomatch'); if(nm){ nm.style.display=(match.length===0)?'table-row':'none'; }
  var shown=Math.max(0,Math.min(match.length,cur*size)-(cur-1)*size);
  var s=document.getElementById('psmh-shown'); if(s){ s.innerHTML=shown; }
  var t=document.getElementById('psmh-total'); if(t){ t.innerHTML=match.length; }
  var r=document.getElementById('psmh-range');
  if(r){ r.innerHTML= match.length ? ((cur-1)*size+1)+'&ndash;'+((cur-1)*size+shown)+' / '+match.length : ''; }
  var box=document.getElementById('psmh-pager'); if(!box){ return; }
  if(pages<=1){ box.innerHTML=''; return; }
  var h='', end=Math.min(pages,cur+2), start=Math.max(1,end-4); end=Math.min(pages,start+4);
  h+='<button type="button" '+(cur==1?'disabled class="off" ':'')+'data-p="'+(cur-1)+'" aria-label="Previous">&lsaquo;</button>';
  for(var p=start;p<=end;p++){ h+='<button type="button" data-p="'+p+'"'+(p==cur?' class="cur" aria-current="page"':'')+'>'+p+'</button>'; }
  h+='<button type="button" '+(cur==pages?'disabled class="off" ':'')+'data-p="'+(cur+1)+'" aria-label="Next">&rsaquo;</button>';
  box.innerHTML=h;
  var bs=box.getElementsByTagName('button');
  for(var k=0;k<bs.length;k++){ bs[k].onclick=function(){ PsmhApply(parseInt(this.getAttribute('data-p'),10)); }; }
 };
 window.PsmhApply(1);
 var go=function(){ if(window.jQuery && jQuery.fn.peity){ jQuery('.psmh-donut').peity('donut'); } };
 if(window.jQuery && !jQuery.fn.peity){ jQuery.ajax({url:'js/jquery.peity.min.js',dataType:'script',cache:true}).done(go); } else { go(); }
})();
</script>
JS;
    return $js;
}

function psmh_colors():array{
    $c=array("accent"=>"#10b981","soft"=>"rgba(16,185,129,.12)","primary"=>"#006a62");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $p=brand_palette();
        $a=brand_ui_accents($p);
        if(!empty($a["accent"])){$c["accent"]=$a["accent"];}
        if(!empty($a["accent_soft"])){$c["soft"]=$a["accent_soft"];}
        // The stock accent_dark is under AA for text: keep the awc primary
        // unless the appliance carries its own brand colours.
        if(!empty($p["colors_on"]) && !empty($a["accent_dark"])){$c["primary"]=$a["accent_dark"];}
    }
    return $c;
}

function psmh_style():string{
    if(isset($GLOBALS["PSMH_STYLE"])){return "";}
    $GLOBALS["PSMH_STYLE"]=true;
    $c=psmh_colors();
    $P=$c["primary"];
    // Seules les regles propres a la page : carte, table, recherche, jauges, etiquettes,
    // tuiles et pagination viennent du kit ak- (angular.css.php).
    return "<style>
.psmh{margin-top:4px}
.psmh-sum{display:flex;align-items:stretch;gap:24px;min-height:120px;padding:20px 28px;margin-bottom:24px}
.psmh-sum-empty{align-items:center;justify-content:center;gap:10px;font-size:15px;color:#3d4947}
.psmh-z{display:flex;flex-direction:column;justify-content:center;gap:4px;min-width:0}
.psmh-z+.psmh-z{border-left:1px solid var(--ak-border);padding-left:24px}
.psmh-z-total{flex:1.3 1 0;flex-direction:row;align-items:center;justify-content:space-between;gap:20px}
.psmh-z-top{flex:1.2 1 0}
.psmh-z-win{flex:1 1 0}
.psmh-big{font-size:32px;font-weight:700;line-height:1.1;color:$P}
.psmh-cap{font-size:13px;color:#3d4947}
.psmh-cap b{color:var(--ak-text)}
.psmh-ram{display:flex;align-items:center;gap:12px;flex:0 0 auto}
.psmh-ram-t{font-size:13px;color:#3d4947;max-width:110px;line-height:1.35}
.psmh-ram-t b{color:var(--ak-text)}
.psmh-top-n{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.psmh-top-n>span:first-child{font-size:18px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%}
.psmh-note{margin-top:4px;align-self:flex-start}
.psmh-badge{display:inline-flex;align-items:center;gap:4px}
.psmh-bar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;flex-wrap:wrap}
.psmh-search{flex:0 1 400px;min-width:240px}
.psmh-count{font-size:13px;color:#3d4947;white-space:nowrap}
.psmh-scroll{overflow-x:auto}
.psmh-tbl th{white-space:nowrap}
.psmh-c-rank{width:44px;color:var(--ak-muted);text-align:center}
.psmh-c-go{width:36px;color:var(--ak-muted);text-align:center}
.psmh-c-share{width:30%;min-width:180px}
.psmh-row{cursor:pointer;transition:background .15s}
.psmh-row:hover,.psmh-row:focus{background:#f6faf9}
.psmh-row:focus-visible{outline:2px solid $P;outline-offset:-2px}
.psmh-name{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.psmh-name>span:first-child{font-size:15px;font-weight:600;color:$P}
.psmh-sh{display:flex;align-items:center;gap:10px}
.psmh-track{flex:1 1 auto;max-width:180px}
.psmh-pct{font-size:13px;font-weight:600;min-width:44px}
.psmh-avg{font-weight:600}
.psmh-other td{background:#fafbfb}
.psmh-other-n{font-style:italic;color:#3d4947}
.psmh-nomatch{display:none}
.psmh-nomatch td{text-align:center;color:var(--ak-muted);padding:24px}
.psmh-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:16px}
.psmh-kpi-v{font-size:20px;font-weight:700}
.psmh-dhead{display:flex;justify-content:flex-end;margin-bottom:12px}
@media(max-width:1200px){.psmh-sum{flex-direction:column}.psmh-z+.psmh-z{border-left:0;padding-left:0;border-top:1px solid var(--ak-border);padding-top:16px}.psmh-track{max-width:100px}}
@media(prefers-reduced-motion:reduce){.psmh *{transition:none!important}}
</style>";
}

function psmh_zoom_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $proc=psmh_proc();
    if($proc==""){return false;}
    $period=psmh_period();
    return $tpl->js_dialog2(htmlspecialchars($proc)." - {psmh_memory_usage}",
        "$page?zoom-popup=yes&proc=".rawurlencode($proc)."&period=$period",900);
}

function psmh_zoom_popup():bool{
    $page=CurrentPageName();
    $proc=psmh_proc();
    if($proc==""){return false;}
    $period=psmh_period();
    $pe=rawurlencode($proc);
    echo "<div id='psmh-zoom'></div><script>LoadAjaxSilent('psmh-zoom','$page?zoom-chart=yes&proc=$pe&period=$period');</script>";
    return true;
}

function psmh_zoom_chart():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $proc=psmh_proc();
    if($proc==""){return false;}
    $period=psmh_period();
    $pe=rawurlencode($proc);

    // The dialog follows the page period (header selector): no second selector here.
    $all=psmh_periods();
    $head="<div class='psmh-dhead'><span class='ak-tag psmh-badge'><i class='fas fa-calendar' aria-hidden='true'></i> {psmh_period_label}: {$all[$period]}</span></div>";

    list($data,$err)=psmh_api("/system/memory/processes/series?proc=$pe&period=$period");
    if($err!=""){
        echo psmh_style().$tpl->_ENGINE_parse_body($head.$tpl->div_error(htmlspecialchars($err)));
        return false;
    }
    $points=isset($data["points"])&&is_array($data["points"])?$data["points"]:array();
    $hourly=intval(isset($data["resolution"])?$data["resolution"]:0)>=3600;
    if(count($points)==0){
        echo psmh_style().$tpl->_ENGINE_parse_body($head."<div class='awc-empty'>{no_data}</div>");
        return true;
    }

    $labels=array(); $avg=array(); $peak=array();
    $head.=psmh_zoom_kpis($points);
    $fmt=($period=="7d")?"d/m H:00":"H:i";
    foreach($points as $p){
        $labels[]=json_encode(date($fmt,intval($p["ts"])));
        $avg[]=sprintf('%.1f',intval($p["kb"])/1024);
        $peak[]=sprintf('%.1f',intval($p["max_kb"])/1024);
    }
    $id="psmh-chart-".md5($proc.$period.microtime());
    $lAvg=json_encode($tpl->javascript_parse_text("{average}")." (MB)");
    $lPeak=json_encode($tpl->javascript_parse_text("{peak}")." (MB)");

    $datasets="{label:$lAvg,data:[".implode(",",$avg)."],borderColor:'#006a62',backgroundColor:'rgba(16,185,129,.12)',fill:true,tension:.25,pointRadius:0,borderWidth:2}";
    if($hourly){
        $datasets.=",{label:$lPeak,data:[".implode(",",$peak)."],borderColor:'#865300',borderDash:[4,4],fill:false,tension:.25,pointRadius:0,borderWidth:1.5}";
    }

    $js=array();
    $js[]="<script>";
    $js[]="(function(){";
    $js[]="function go(){ var c=document.getElementById('$id'); if(!c){return;}";
    $js[]=" new Chart(c,{type:'line',data:{labels:[".implode(",",$labels)."],datasets:[$datasets]},";
    $js[]=" options:{responsive:true,maintainAspectRatio:false,animation:false,interaction:{mode:'index',intersect:false},";
    $js[]="  scales:{y:{beginAtZero:true,ticks:{callback:function(v){return v+' MB';}}},x:{ticks:{maxTicksLimit:12,autoSkip:true}}},";
    $js[]="  plugins:{legend:{display:".($hourly?"true":"false")."}}}});";
    $js[]="}";
    $js[]="if(typeof Chart!=='undefined'){go();return;}";
    $js[]="var s=document.createElement('script');s.src='angular/js/plugins/chartJs/Chart.min.4.5.1.js';s.onload=go;document.head.appendChild(s);";
    $js[]="})();";
    $js[]="</script>";

    echo psmh_style().$tpl->_ENGINE_parse_body($head)
        ."<div style='position:relative;height:340px'><canvas id='$id'></canvas></div>"
        .implode("\n",$js);
    return true;
}

// Three tiles above the chart, computed from the same series.
function psmh_zoom_kpis(array $points):string{
    $sum=0; $n=0; $max=0; $last=0;
    foreach($points as $p){
        $kb=intval($p["kb"]); $sum+=$kb; $n++; $last=$kb;
        $mk=max($kb,intval(isset($p["max_kb"])?$p["max_kb"]:0));
        if($mk>$max){$max=$mk;}
    }
    $avg=$n>0?intval($sum/$n):0;
    $tpl=new template_admin();
    $spike="";
    $sp=psmh_spike($avg,$max);
    if($sp>0){
        $say=htmlspecialchars(str_replace("%s",(string)$sp,$tpl->_ENGINE_parse_body("{psmh_peak_ratio}")),ENT_QUOTES);
        $spike=" <span class='ak-tag ak-warn psmh-badge' title='$say' aria-label='$say'><i class='fas fa-arrow-trend-up' aria-hidden='true'></i>x$sp</span>";
    }
    return "<div class='psmh-kpis'>"
        ."<div class='ak-fig'><div class='ak-fig-txt'><div class='ak-fig-l'>{average}</div><div class='psmh-kpi-v'>".psmh_kb($avg)."</div></div></div>"
        ."<div class='ak-fig'><div class='ak-fig-txt'><div class='ak-fig-l'>{peak}</div><div class='psmh-kpi-v'>".psmh_kb($max)."$spike</div></div></div>"
        ."<div class='ak-fig'><div class='ak-fig-txt'><div class='ak-fig-l'>{last_sample}</div><div class='psmh-kpi-v'>".psmh_kb($last)."</div></div></div>"
        ."</div>";
}
