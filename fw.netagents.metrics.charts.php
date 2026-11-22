<?php
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js): helpers partages des
// tableaux d'entites, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
/**
 * Fenetre de metriques d'un agent Meta (js_dialog3, 950 px).
 *  - onglet Memory  : memory_pie()  -> GET /netagents/status/{id}
 *  - onglets Hourly / Daily / 3 Days : StartPoint() -> GET /netagents/metrics/{id}?range=,
 *    /netagents/cpu-history/agent/{id}, /netagents/memory-alerts/agent/{id}
 */

// Include the socket class for API communication
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["popup"])){StartPoint();exit;}
if(isset($_GET["memory"])){memory_pie();exit;}
js();

/**
 * Fetch metrics data from the API
 *
 * @param int $agent_id Agent ID
 * @param string $range Time range: hour, day, 3days
 * @return array|null Metrics data or null on error
 */

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["agent-metrics-js"]);
    $ArticaNetAgents=new ArticaNetAgents($id);
    $Hostname=$ArticaNetAgents->GetAgentHostname();
    return $tpl->js_dialog3("#$id: $Hostname","$page?tabs=yes&agent_id=$id",950);
}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["agent_id"] ?? 0);
    $array["{memory}"]="$page?memory=yes&agent_id=$ID";
    $array["{nmet_tab_hour}"]="$page?popup=yes&agent_id=$ID&range=hour";
    $array["{nmet_tab_day}"]="$page?popup=yes&agent_id=$ID&range=day";
    $array["{nmet_tab_3days}"]="$page?popup=yes&agent_id=$ID&range=3days";
    echo $tpl->tabs_default($array);
    return true;
}
// ---------------------------------------------------------------------------
// Onglet Memory (maquette stitch_agent_memory_metrics_redesign, 2026-11-15).
// Source unique : GET /netagents/status/{id} (dernier rapport de l'agent).
//  - memory       : vue systeme (total/used/available en octets, used_percent) ;
//  - memory_alert : presente seulement tant que l'agent est au-dessus de son seuil ;
//  - psmem        : programmes en KiB. ATTENTION total_ram / total_swap sont des
//                   SOMMES des programmes (PsMemPy), pas la RAM ni le swap du systeme.
// ---------------------------------------------------------------------------
function memory_pie():bool{
    $agent_id=intval($_GET["agent_id"] ?? 0);
    $tpl=new template_admin();
    $inner=isset($_GET["inner"]);
    $m=nmem_model($agent_id);

    $body=array();
    if($m["error"]!==""){
        $body[]=nmem_error($m);
    }else{
        $body[]=nmem_report_line($m);
        $body[]=nmem_summary_card($m);
        $body[]=nmem_alert_banner($m);
        if(count($m["programs"])==0){
            $body[]=nmem_empty();
        }else{
            $body[]=nmem_ranking_card($m);
            $body[]=nmem_table_card($m);
        }
    }
    $html=$tpl->_ENGINE_parse_body(@implode("\n",$body));
    if($inner){
        echo $html."<script>NmemInit($agent_id);</script>";
        return true;
    }
    echo nmem_style()."<div id='nmem-host-$agent_id' class='nmem'>$html</div>".nmem_js()
        ."<script>NoSpinner();NmemInit($agent_id);NmemTimerStart($agent_id,'".CurrentPageName()."');</script>";
    return true;
}

function nmem_h($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8"));
}

// KiB -> texte lisible (une decimale en GB, entier en MB/KB).
function nmem_fmt($kib):string{
    $kib=floatval($kib);
    if($kib>=1048576){ return str_replace(".0 "," ",sprintf("%.1f GB",$kib/1048576)); }
    if($kib>=1024){ return sprintf("%d MB",round($kib/1024)); }
    return sprintf("%d KB",round($kib));
}

function nmem_pct($v):string{
    if($v>0 && $v<1){ return "&lt;1 %"; }
    return sprintf("%d %%",round($v));
}

function nmem_ts($s):int{
    if(!is_string($s) || $s==="" || strpos($s,"0001-01-01")===0){ return 0; }
    try{ $d=new DateTime($s); return $d->getTimestamp(); }catch(Exception $e){ return 0; }
}

function nmem_model(int $agent_id):array{
    $m=array("id"=>$agent_id,"error"=>"","error_text"=>"","ts"=>0,"ram"=>0.0,"used"=>0.0,"avail"=>0.0,
        "pct"=>-1.0,"alert"=>null,"programs"=>array(),"pss"=>false,"sum"=>0.0,"swap"=>0.0,"show_swap"=>false);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$agent_id"));
    if(!is_object($json)){ $m["error"]="unreachable"; return $m; }
    if(isset($json->Status) && !$json->Status){
        $m["error"]="status"; $m["error_text"]=(string)($json->Error ?? ""); return $m;
    }
    $m["ts"]=nmem_ts($json->time ?? "");
    if(isset($json->memory) && is_object($json->memory)){
        $tot=floatval($json->memory->total_bytes ?? 0);
        if($tot>0){
            $m["ram"]=$tot/1024;
            $m["used"]=floatval($json->memory->used_bytes ?? 0)/1024;
            $m["avail"]=floatval($json->memory->available_bytes ?? 0)/1024;
            $m["pct"]=floatval($json->memory->used_percent ?? 0);
        }
    }
    if(isset($json->memory_alert) && is_object($json->memory_alert)){
        $procs=array();
        $tp=$json->memory_alert->top_processes ?? array();
        if(is_array($tp)){
            foreach($tp as $p){
                if(!is_object($p)){continue;}
                $procs[]=array("pid"=>intval($p->pid ?? 0),"name"=>(string)($p->name ?? ""),
                    "cmd"=>(string)($p->command_line ?? ""),"kib"=>floatval($p->memory_bytes ?? 0)/1024);
            }
        }
        $m["alert"]=array("ts"=>nmem_ts($json->memory_alert->timestamp ?? ""),
            "pct"=>floatval($json->memory_alert->memory_percent ?? 0),
            "threshold"=>floatval($json->memory_alert->threshold ?? 0),"procs"=>array_slice($procs,0,5));
    }
    if(isset($json->psmem) && is_object($json->psmem)){
        $m["pss"]=(bool)($json->psmem->has_pss ?? false);
        $list=$json->psmem->programs ?? array();
        if(is_array($list)){
            foreach($list as $p){
                if(!is_object($p)){continue;}
                $row=array("name"=>(string)($p->program ?? ""),"count"=>intval($p->count ?? 0),
                    "private"=>floatval($p->private ?? 0),"shared"=>floatval($p->shared ?? 0),
                    "total"=>floatval($p->total ?? 0),"swap"=>floatval($p->swap ?? 0));
                $m["sum"]+=$row["total"];
                $m["swap"]+=$row["swap"];
                $m["programs"][]=$row;
            }
        }
        usort($m["programs"],function($a,$b){ return $b["total"] <=> $a["total"]; });
        $m["show_swap"]=$m["swap"]>0;
    }
    return $m;
}

// Part de la RAM du noeud (-1 quand le noeud ne la rapporte pas).
function nmem_share(array $m,float $kib):float{
    if($m["ram"]<=0){ return -1; }
    return $kib*100/$m["ram"];
}

// Remplace %s / %p / %t dans une phrase deja traduite.
function nmem_tr(string $token,array $vals):string{
    $tpl=new template_admin();
    $t=$tpl->_ENGINE_parse_body("{".$token."}");
    foreach($vals as $k=>$v){ $t=str_replace($k,$v,$t); }
    // Texte en ligne : les [br] de fin de phrase deviennent des espaces.
    return trim(preg_replace('#\s*<br\s*/?>\s*#i',' ',$t));
}

function nmem_ago(int $ts):string{
    $d=max(0,time()-$ts);
    if($d<60){ return nmem_tr("nmem_ago_s",array("%s"=>$d)); }
    if($d<3600){ return nmem_tr("nmem_ago_min",array("%s"=>intval($d/60))); }
    if($d<172800){ return nmem_tr("nmem_ago_h",array("%s"=>intval($d/3600))); }
    return nmem_tr("nmem_ago_d",array("%s"=>intval($d/86400)));
}

function nmem_state(float $pct):array{
    if($pct>=90){ return array("crit","{nmem_state_crit}","fas fa-circle-exclamation"); }
    if($pct>=75){ return array("warn","{nmem_state_warn}","fas fa-triangle-exclamation"); }
    return array("ok","{nmem_state_ok}","fas fa-circle-check");
}

function nmem_error(array $m):string{
    $txt="".nmem_tr("nmem_unreachable",array())."";
    if($m["error"]=="status" && $m["error_text"]!==""){ $txt=nmem_h($m["error_text"]); }
    return "<div class='nmem-err' role='alert'><span class='nmem-ico'><i class='fas fa-plug-circle-xmark'></i></span><span>$txt</span></div>";
}

function nmem_report_line(array $m):string{
    if($m["ts"]==0){
        return "<div class='nmem-report'><span class='nmem-ico'><i class='far fa-clock'></i></span><span>".nmem_tr("nmem_report_unknown",array())."</span>"
            ."<span class='nmem-report-r'>{nmem_refresh_note}</span></div>";
    }
    $age=time()-$m["ts"];
    $when=nmem_tr("nmem_report_from",array("%s"=>"<b>".nmem_ago($m["ts"])."</b> (".date("H:i:s",$m["ts"]).")"));
    if($age>900){
        return "<div class='nmem-report nmem-report-old' role='status'><span class='nmem-ico'><i class='fas fa-triangle-exclamation'></i></span>"
            ."<span>$when &mdash; ".nmem_tr("nmem_report_old",array())."</span><span class='nmem-report-r'>{nmem_refresh_note}</span></div>";
    }
    return "<div class='nmem-report'><span class='nmem-ico'><i class='far fa-clock'></i></span><span>$when</span>"
        ."<span class='nmem-report-r'>{nmem_refresh_note}</span></div>";
}

function nmem_ring(float $pct,string $cls):string{
    $r=34; $c=2*M_PI*$r; $p=max(0,min(100,$pct));
    $off=sprintf("%.2f",$c*(1-$p/100)); $cs=sprintf("%.2f",$c);
    return "<svg class='nmem-ring nmem-$cls' width='84' height='84' viewBox='0 0 84 84' aria-hidden='true'>"
        ."<circle cx='42' cy='42' r='$r' class='nmem-ring-bg'/>"
        ."<circle cx='42' cy='42' r='$r' class='nmem-ring-fg' stroke-dasharray='$cs' stroke-dashoffset='$off' transform='rotate(-90 42 42)'/></svg>";
}

function nmem_summary_card(array $m):string{
    $swap="<span class='nmem-ico'><i class='fas fa-right-left'></i></span>";
    if($m["swap"]>0){
        $swap.="<span>".nmem_tr("nmem_swap_progs",array("%s"=>"<b>".nmem_fmt($m["swap"])."</b>"))."</span>";
    }else{
        $swap.="<span>{nmem_swap_none}</span>";
    }
    if($m["ram"]<=0){
        return "<div class='nmem-card'><div class='nmem-sum-na'><span class='nmem-ico'><i class='fas fa-memory'></i></span>"
            ."<span>".nmem_tr("nmem_no_sysmem",array())."</span></div><div class='nmem-swap'>$swap</div></div>";
    }
    list($cls,$label,$ico)=nmem_state($m["pct"]);
    $pct=sprintf("%d",round($m["pct"]));
    $aria=nmem_tr("nmem_ring_aria",array("%s"=>"$pct %"));
    $f=array();
    $f[]="<div class='nmem-fig'><div class='nmem-fig-l'>{nmem_used}</div><div class='nmem-fig-v'>".nmem_fmt($m["used"])."</div></div>";
    $f[]="<div class='nmem-fig'><div class='nmem-fig-l'>{nmem_available}</div><div class='nmem-fig-v nmem-fig-av'>".nmem_fmt($m["avail"])."</div></div>";
    $f[]="<div class='nmem-fig'><div class='nmem-fig-l'>{nmem_total}</div><div class='nmem-fig-v'>".nmem_fmt($m["ram"])."</div></div>";
    return "<div class='nmem-card nmem-sum'>"
        ."<div class='nmem-sum-l' role='img' aria-label=\"$aria\">"
        ."<div class='nmem-ring-w'>".nmem_ring($m["pct"],$cls)."<div class='nmem-ring-t'><b>$pct %</b><span>{nmem_used_pct}</span></div></div>"
        ."<div><div class='nmem-cap'>{nmem_system_memory}</div><span class='nmem-pill nmem-pill-$cls'><i class='$ico'></i> $label</span></div>"
        ."</div>"
        ."<div class='nmem-sum-r'><div class='nmem-figs'>".implode("",$f)."</div>"
        ."<div class='nmem-swap'>$swap</div></div>"
        ."</div>";
}

function nmem_alert_banner(array $m):string{
    if(!is_array($m["alert"])){ return ""; }
    $a=$m["alert"];
    $title=nmem_tr("nmem_alert_title",array("%p"=>sprintf("%d",round($a["pct"])),"%t"=>sprintf("%d",round($a["threshold"]))));
    $at="";
    if($a["ts"]>0){ $at="<span class='nmem-alert-at'>".nmem_tr("nmem_alert_at",array("%s"=>date("H:i",$a["ts"])))."</span>"; }
    $chips=array();
    foreach($a["procs"] as $p){
        $tip=$p["cmd"]!==""?$p["cmd"]:$p["name"];
        $chips[]="<span class='nmem-chip' title='".nmem_h($tip)."'><b>".nmem_h($p["name"])."</b>"
            .($p["pid"]>0?" <span class='nmem-muted'>pid ".$p["pid"]."</span>":"")." ".nmem_fmt($p["kib"])."</span>";
    }
    $top="";
    if(count($chips)>0){ $top="<div class='nmem-alert-top'><span>{nmem_alert_top}</span>".implode("",$chips)."</div>"; }
    return "<div class='nmem-alert' role='alert'><div class='nmem-alert-h'><span class='nmem-ico'><i class='fas fa-memory'></i></span>"
        ."<b>$title</b>$at</div>$top</div>";
}

function nmem_empty():string{
    return "<div class='nmem-card nmem-emptyc'><span class='nmem-ico'><i class='fas fa-chart-bar'></i></span><span>".nmem_tr("nmem_empty",array())."</span></div>";
}

function nmem_pss_chip(array $m):string{
    if($m["pss"]){
        return "<span class='nmem-pill nmem-pill-ok' title=\"".nmem_tr("nmem_pss_ok_help",array())."\"><i class='fas fa-circle-check'></i> {nmem_pss_ok}</span>";
    }
    return "<span class='nmem-pill nmem-pill-warn' title=\"".nmem_tr("nmem_pss_no_help",array())."\"><i class='fas fa-triangle-exclamation'></i> {nmem_pss_no}</span>";
}

function nmem_proc_word(int $n):string{
    return $n." ".($n==1?"{nmem_process_lc}":"{nmem_processes_lc}");
}

function nmem_bar_row(array $m,array $p,float $scale):string{
    $share=nmem_share($m,$p["total"]);
    $tone="";
    if($share>=50){ $tone=" nmem-bar-crit"; }elseif($share>=25){ $tone=" nmem-bar-warn"; }
    $wp=$scale>0?min(100,$p["private"]*100/$scale):0;
    $ws=$scale>0?min(100-$wp,$p["shared"]*100/$scale):0;
    $val=nmem_fmt($p["total"]).($share>=0?" &middot; ".nmem_pct($share):"");
    $aria=nmem_h($p["name"]).": ".nmem_fmt($p["total"]).($share>=0?", ".strip_tags(html_entity_decode(nmem_pct($share))):"")
        .", ".nmem_proc_word($p["count"]);
    return "<div class='nmem-bar-row' role='img' aria-label=\"$aria\">"
        ."<div class='nmem-bar-n' title='".nmem_h($p["name"])."'>".nmem_h($p["name"])."</div>"
        ."<div class='nmem-bar-c' title=\"".nmem_proc_word($p["count"])."\"><i class='fas fa-gears'></i> ".$p["count"]."</div>"
        ."<div class='nmem-bar-t$tone'><span class='nmem-bar-p' style='width:".sprintf("%.2f",$wp)."%'></span>"
        ."<span class='nmem-bar-s' style='width:".sprintf("%.2f",$ws)."%'></span></div>"
        ."<div class='nmem-bar-v'>$val</div></div>";
}

function nmem_ranking_card(array $m):string{
    $top=array_slice($m["programs"],0,10);
    // Echelle : la RAM du noeud quand il la rapporte, sinon le plus gros programme.
    $scale=$m["ram"]>0?$m["ram"]:($top[0]["total"] ?? 0);
    $rows=array();
    foreach($top as $p){ $rows[]=nmem_bar_row($m,$p,$scale); }
    $axis="";
    if($m["ram"]>0){
        $ticks=array();
        foreach(array(0,25,50,75,100) as $q){
            $ticks[]="<span style='left:$q%'>$q %</span>";
        }
        $axis="<div class='nmem-axis'><div></div><div></div><div class='nmem-axis-t'>".implode("",$ticks)."</div><div></div></div>";
    }
    if($m["ram"]<=0){ $axis="<div class='nmem-sumline'>".nmem_tr("nmem_rank_scale_max",array())."</div>"; }
    $sum=nmem_tr("nmem_rank_sum",array("%s"=>"<b>".nmem_fmt($m["sum"])."</b>"));
    $share=nmem_share($m,$m["sum"]);
    if($share>=0){ $sum.=" (".nmem_pct($share)." {nmem_of_ram})"; }
    if(!$m["pss"] && $share>100){ $sum.=" &mdash; ".nmem_tr("nmem_rank_over",array()).""; }
    return "<div class='nmem-card'>"
        ."<div class='nmem-card-h'><div><div class='nmem-title'>{nmem_rank_title} ".nmem_pss_chip($m)."</div>"
        ."<p class='nmem-explain'>".nmem_tr("nmem_rank_explain",array())."</p></div>"
        ."<div class='nmem-legend'><span><i class='nmem-sw nmem-sw-p'></i>{nmem_private}</span><span><i class='nmem-sw nmem-sw-s'></i>{nmem_shared}</span></div></div>"
        ."<div class='nmem-bars'>".implode("",$rows)."</div>$axis"
        ."<div class='nmem-sumline'>$sum</div>"
        ."</div>";
}

function nmem_table_card(array $m):string{
    $n=count($m["programs"]);
    $tr=array();
    foreach($m["programs"] as $p){
        $share=nmem_share($m,$p["total"]);
        $tds="<td class='nmem-tn'>".nmem_h($p["name"])."</td>"
            ."<td data-v='".$p["count"]."'>".$p["count"]."</td>"
            ."<td data-v='".$p["private"]."'>".nmem_fmt($p["private"])."</td>"
            ."<td data-v='".$p["shared"]."'>".nmem_fmt($p["shared"])."</td>"
            ."<td data-v='".$p["total"]."'><b>".nmem_fmt($p["total"])."</b></td>";
        if($m["ram"]>0){ $tds.="<td data-v='".sprintf("%.4f",$share)."'>".sprintf("%.1f %%",$share)."</td>"; }
        if($m["show_swap"]){ $tds.="<td data-v='".$p["swap"]."'>".($p["swap"]>0?nmem_fmt($p["swap"]):"<span class='nmem-muted'>&mdash;</span>")."</td>"; }
        $tr[]="<tr data-s='".nmem_h(strtolower($p["name"]))."'>$tds</tr>";
    }
    $cols=array(array("nmem_col_program","t"),array("nmem_col_proc","n"),array("nmem_col_private","n"),
        array("nmem_col_shared","n"),array("nmem_col_total","n"));
    if($m["ram"]>0){ $cols[]=array("nmem_col_pct","n"); }
    if($m["show_swap"]){ $cols[]=array("nmem_col_swap","n"); }
    $th=array();
    foreach($cols as $i=>$c){
        $sort=$i==4?"descending":"none";
        $th[]="<th aria-sort='$sort' class='".($c[1]=="t"?"nmem-th-t":"")."'><button type='button' data-col='$i' data-k='{$c[1]}'>{".$c[0]."} <i class='fas fa-sort'></i></button></th>";
    }
    $showAll=nmem_tr("nmem_show_all",array("%s"=>$n));
    return "<div class='nmem-card nmem-tbl-card' id='nmem-tc-{$m["id"]}' data-open='0'>"
        ."<div class='nmem-tbl-h'><button type='button' class='nmem-acc' aria-expanded='false'><i class='fas fa-chevron-right'></i> $showAll</button>"
        ."<label class='nmem-search' hidden><i class='fas fa-magnifying-glass'></i><input type='search' placeholder=\"{nmem_filter}\" aria-label=\"{nmem_filter}\"></label></div>"
        ."<div class='nmem-tbl-b' hidden><table class='nmem-tbl'><thead><tr>".implode("",$th)."</tr></thead>"
        ."<tbody>".implode("",$tr)."</tbody></table>"
        ."<div class='nmem-nomatch' hidden>".nmem_tr("nmem_nomatch",array())."</div>"
        ."<div class='nmem-foot'><span class='nmem-showing' data-tpl=\"{nmem_showing}\"></span>"
        ."<span class='nmem-pg'><button type='button' class='nmem-prev'>{nmem_prev}</button><span class='nmem-pgn' data-tpl=\"{nmem_page}\"></span>"
        ."<button type='button' class='nmem-next'>{nmem_next}</button></span></div></div></div>";
}

function nmem_style():string{
    $accent="#10b981";$dark="#0f766e";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        $accent=$a["accent"];$dark=$a["accent_dark"];
    }
    return "<style>
.nmem{--nm-acc:$accent;--nm-dark:$dark;color:#111d23;font-size:14px;padding:4px 2px 8px}
.nmem *{box-sizing:border-box}
.nmem-ico{display:inline-flex;width:16px;justify-content:center;flex:none}
.nmem-muted{color:#6d7a77}
.nmem-report{display:flex;align-items:center;gap:8px;color:#3d4947;font-size:13px;margin:6px 0 12px}
.nmem-report-r{margin-left:auto;color:#6d7a77}
.nmem-report-old{background:#fff4e5;border:1px solid #ffddb9;color:#865300;border-radius:10px;padding:8px 12px}
.nmem-card{background:#fff;border:1px solid #d5dde0;border-radius:12px;padding:18px 20px;margin-bottom:14px}
.nmem-sum{display:flex;align-items:center;gap:24px}
.nmem-sum-l{display:flex;align-items:center;gap:16px;padding-right:24px;border-right:1px solid #e3e9ec;flex:none}
.nmem-ring-w{position:relative;width:84px;height:84px}
.nmem-ring-bg{fill:none;stroke:#e3f0f8;stroke-width:9}
.nmem-ring-fg{fill:none;stroke-width:9;stroke-linecap:round}
.nmem-ok .nmem-ring-fg{stroke:#047857}.nmem-warn .nmem-ring-fg{stroke:#b26b00}.nmem-crit .nmem-ring-fg{stroke:#ba1a1a}
.nmem-ring-t{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;line-height:1.1}
.nmem-ring-t b{font-size:17px}.nmem-ring-t span{font-size:13px;color:#6d7a77}
.nmem-cap{font-size:13px;color:#6d7a77;margin-bottom:6px}
.nmem-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 10px;font-size:13px;font-weight:600;white-space:nowrap;vertical-align:middle}
.nmem-pill-ok{background:rgba(16,185,129,.12);color:#047857}
.nmem-pill-warn{background:#ffddb9;color:#865300}
.nmem-pill-crit{background:#ffdad6;color:#ba1a1a}
.nmem-sum-r{flex:1;min-width:0}
.nmem-figs{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;padding-bottom:12px;border-bottom:1px solid #eef2f4}
.nmem-fig-l{font-size:13px;color:#3d4947}
.nmem-fig-v{font-size:24px;font-weight:700;font-variant-numeric:tabular-nums}
.nmem-fig-av{color:#047857}
.nmem-swap{display:flex;align-items:center;gap:8px;font-size:13px;color:#3d4947;padding-top:10px}
.nmem-sum-na{display:flex;gap:8px;align-items:center;color:#3d4947}
.nmem-alert{background:#ffdad6;border:1px solid #f4b4ad;border-radius:12px;padding:14px 18px;margin-bottom:14px;color:#410002}
.nmem-alert-h{display:flex;align-items:center;gap:8px;color:#93000a}
.nmem-alert-at{margin-left:auto;font-size:13px}
.nmem-alert-top{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-top:10px;font-size:13px}
.nmem-chip{background:#fff;border:1px solid #f0c4be;border-radius:999px;padding:4px 12px;font-size:13px;white-space:nowrap;cursor:default}
.nmem-card-h{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}
.nmem-title{font-size:16px;font-weight:700;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.nmem-explain{font-size:13px;color:#3d4947;margin:6px 0 14px;max-width:640px;line-height:1.5}
.nmem-legend{display:flex;gap:14px;font-size:13px;color:#3d4947;white-space:nowrap}
.nmem-sw{display:inline-block;width:12px;height:12px;border-radius:3px;margin-right:6px;vertical-align:-1px}
.nmem-sw-p,.nmem-bar-p{background:var(--nm-dark)}
.nmem-sw-s,.nmem-bar-s{background:var(--nm-acc);opacity:.45}
.nmem-bar-row,.nmem-axis{display:grid;grid-template-columns:170px 64px 1fr 130px;gap:12px;align-items:center}
.nmem-bar-row{min-height:36px}
.nmem-bar-n{font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.nmem-bar-c{font-size:13px;color:#3d4947;background:#eef2f4;border-radius:999px;padding:2px 8px;white-space:nowrap;text-align:center}
.nmem-bar-t{height:14px;background:#eef2f4;border-radius:999px;overflow:hidden;display:flex}
.nmem-bar-t span{display:block;height:100%;transform-origin:left;animation:nmemGrow .5s ease-out}
.nmem-bar-warn .nmem-bar-p{background:#b26b00}.nmem-bar-warn .nmem-bar-s{background:#f0a040}
.nmem-bar-crit .nmem-bar-p{background:#ba1a1a}.nmem-bar-crit .nmem-bar-s{background:#f08070}
.nmem-bar-v{text-align:right;font-size:13px;font-variant-numeric:tabular-nums;white-space:nowrap}
@keyframes nmemGrow{from{transform:scaleX(0)}to{transform:scaleX(1)}}
@media (prefers-reduced-motion:reduce){.nmem-bar-t span{animation:none}}
.nmem-axis{margin-top:6px;padding-top:8px;border-top:1px solid #eef2f4}
.nmem-axis-t{position:relative;height:18px;font-size:13px;color:#6d7a77}
.nmem-axis-t span{position:absolute;transform:translateX(-50%);white-space:nowrap}
.nmem-axis-t span:first-child{transform:none}.nmem-axis-t span:last-child{transform:translateX(-100%)}
.nmem-sumline{font-size:13px;color:#3d4947;margin-top:12px}
.nmem-emptyc,.nmem-err{display:flex;gap:10px;align-items:center;color:#3d4947}
.nmem-err{background:#ffdad6;border:1px solid #f4b4ad;border-radius:12px;padding:14px 18px;color:#93000a;margin:8px 0}
.nmem-tbl-h{display:flex;align-items:center;justify-content:space-between;gap:12px}
.nmem-acc{background:none;border:0;padding:4px 0;font-size:14px;font-weight:700;color:var(--nm-dark);cursor:pointer}
.nmem-acc svg,.nmem-acc i{transition:transform .2s}
.nmem-tbl-card[data-open='1'] .nmem-acc svg{transform:rotate(90deg)}
.nmem-search{display:flex;align-items:center;gap:8px;border:1px solid #d5dde0;border-radius:8px;padding:0 10px;height:34px;width:260px;color:#6d7a77;margin:0;font-weight:400}
.nmem-search[hidden],.nmem-tbl-b[hidden],.nmem-nomatch[hidden]{display:none}
.nmem-search input{border:0;outline:0;flex:1;font-size:13px;min-width:0;color:#111d23}
.nmem-tbl{width:100%;border-collapse:collapse;margin-top:12px;font-size:13px;font-variant-numeric:tabular-nums}
.nmem-tbl th{border-bottom:1px solid #d5dde0;text-align:right;padding:0}
.nmem-tbl th.nmem-th-t{text-align:left}
.nmem-tbl th button{background:none;border:0;padding:8px 10px;font-weight:700;color:#3d4947;cursor:pointer;font-size:13px}
.nmem-tbl th[aria-sort='ascending'] button,.nmem-tbl th[aria-sort='descending'] button{color:var(--nm-dark)}
.nmem-tbl td{padding:8px 10px;border-bottom:1px solid #eef2f4;text-align:right;color:#111d23}
.nmem-tbl td.nmem-tn{text-align:left;font-weight:600;word-break:break-all}
.nmem-nomatch{padding:14px;color:#6d7a77;text-align:center}
.nmem-foot{display:flex;justify-content:space-between;align-items:center;margin-top:10px;font-size:13px;color:#3d4947}
.nmem-pg{display:flex;align-items:center;gap:10px}
.nmem-pg button{background:#fff;border:1px solid #d5dde0;border-radius:6px;padding:3px 10px;font-size:13px;color:#111d23}
.nmem-pg button:disabled{color:#a9b4b2;cursor:default}
.nmem button:focus-visible{outline:2px solid #006a62;outline-offset:2px}
.nmem-search:focus-within{outline:2px solid #006a62;outline-offset:1px}
.nmem-search input:focus{outline:0;box-shadow:none}
</style>";
}

function nmem_js():string{
    return "<script>
window.NmemState=window.NmemState||{};
function NmemInit(id){
  var c=document.getElementById('nmem-tc-'+id); if(!c){return;}
  var st=NmemState[id]||(NmemState[id]={open:false,q:'',col:4,dir:-1,page:0});
  var acc=c.querySelector('.nmem-acc'),srch=c.querySelector('.nmem-search'),inp=srch.querySelector('input'),
      body=c.querySelector('.nmem-tbl-b'),tb=c.querySelector('tbody'),rows=[].slice.call(tb.rows),per=25;
  function setOpen(o){st.open=o;c.setAttribute('data-open',o?'1':'0');acc.setAttribute('aria-expanded',o?'true':'false');body.hidden=!o;srch.hidden=!o;}
  function val(r,i,k){var td=r.cells[i];if(!td){return 0;}return k=='t'?td.textContent.toLowerCase():parseFloat(td.getAttribute('data-v')||'0');}
  function draw(){
    var ths=c.querySelectorAll('thead th'),k='n';
    for(var i=0;i<ths.length;i++){var b=ths[i].querySelector('button');if(i==st.col){k=b.getAttribute('data-k');ths[i].setAttribute('aria-sort',st.dir>0?'ascending':'descending');}else{ths[i].setAttribute('aria-sort','none');}}
    rows.sort(function(a,b){var x=val(a,st.col,k),y=val(b,st.col,k);return (x<y?-1:x>y?1:0)*st.dir;});
    var q=st.q.toLowerCase(),vis=[];
    rows.forEach(function(r){tb.appendChild(r);if(q===''||r.getAttribute('data-s').indexOf(q)>-1){vis.push(r);}});
    var pages=Math.max(1,Math.ceil(vis.length/per));if(st.page>=pages){st.page=pages-1;}
    var a=st.page*per,b=Math.min(vis.length,a+per);
    rows.forEach(function(r){r.style.display='none';});
    for(var j=a;j<b;j++){vis[j].style.display='';}
    c.querySelector('.nmem-nomatch').hidden=vis.length>0;
    var sh=c.querySelector('.nmem-showing');sh.textContent=sh.getAttribute('data-tpl').replace('%a',vis.length?a+1:0).replace('%b',b).replace('%c',vis.length);
    var pg=c.querySelector('.nmem-pgn');pg.textContent=pg.getAttribute('data-tpl').replace('%a',st.page+1).replace('%b',pages);
    c.querySelector('.nmem-prev').disabled=st.page<=0;c.querySelector('.nmem-next').disabled=st.page>=pages-1;
  }
  acc.onclick=function(){setOpen(!st.open);};
  inp.value=st.q;inp.oninput=function(){st.q=inp.value;st.page=0;draw();};
  c.querySelectorAll('thead button').forEach(function(b){b.onclick=function(){var i=parseInt(b.getAttribute('data-col'),10);
    if(st.col==i){st.dir=-st.dir;}else{st.col=i;st.dir=b.getAttribute('data-k')=='t'?1:-1;}st.page=0;draw();};});
  c.querySelector('.nmem-prev').onclick=function(){st.page--;draw();};
  c.querySelector('.nmem-next').onclick=function(){st.page++;draw();};
  setOpen(st.open);draw();
}
function NmemTimerStart(id,page){
  window.NmemTimers=window.NmemTimers||{};
  if(NmemTimers[id]){clearInterval(NmemTimers[id]);}
  NmemTimers[id]=setInterval(function(){
    var h=document.getElementById('nmem-host-'+id);
    if(!h){clearInterval(NmemTimers[id]);delete NmemTimers[id];return;}
    if(h.offsetParent===null){return;}
    if(document.querySelector('.sweet-alert.visible,.swal2-container')){return;}
    LoadAjaxSilent('nmem-host-'+id,page+'?memory=yes&inner=yes&agent_id='+id);
  },60000);
}
</script>";
}

// ---------------------------------------------------------------------------
// Onglets Hourly / Daily / 3 Days (maquette stitch_agent_memory_metrics_redesign2,
// 2026-11-16). Trois sources reelles :
//  - GET /netagents/metrics/{id}?range=  : un point par minute (charge 1/5/15,
//    memoire), collecte par Meta toutes les 5 min, retention 3 jours ;
//  - GET /netagents/cpu-history/agent/{id}?limit= : CPU en %, un point toutes
//    les 5 min cote agent, le plus recent en premier ;
//  - GET /netagents/memory-alerts/agent/{id}?limit= : alertes memoire passees,
//    les plus recentes en premier.
// Le nombre de coeurs n'est expose nulle part : la charge reste neutre.
// ---------------------------------------------------------------------------
function StartPoint():bool{
    $agent_id=intval($_GET["agent_id"] ?? 0);
    $range=(string)($_GET["range"] ?? "hour");
    if(!in_array($range,array("hour","day","3days"),true)){ $range="hour"; }
    $tpl=new template_admin();
    $m=nmet_model($agent_id,$range);
    $ns="nmet-$range-$agent_id";

    $body=array();
    if($m["error"]){
        $body[]=nmet_error();
    }else{
        $body[]=nmet_period_line($m);
        if(count($m["raw"])==0 && count($m["cpu"])==0){
            $body[]=nmet_empty();
        }else{
            $body[]="<div class='nmet-tiles'>".nmet_tile_cpu($m).nmet_tile_mem($m).nmet_tile_load($m)."</div>";
            $body[]=nmet_inspect_bar($m);
            $body[]=nmet_chart_mem($m);
            $body[]=nmet_chart_cpu($m);
            $body[]=nmet_chart_load($m);
        }
    }
    $html=$tpl->_ENGINE_parse_body(@implode("\n",$body));
    $data=json_encode(nmet_js_data($m),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    $init="<script>NmetInit('$ns',$data);</script>";
    if(isset($_GET["inner"])){
        echo $html.$init;
        return true;
    }
    $url=CurrentPageName()."?popup=yes&inner=yes&agent_id=$agent_id&range=$range";
    echo nmem_style().nmet_style()."<div id='$ns' class='nmem nmet'>$html</div>".nmet_js().$init
        ."<script>NoSpinner();NmetTimerStart('$ns','$url');</script>";
    return true;
}

function nmet_range_secs(string $range):int{
    if($range=="day"){ return 86400; }
    if($range=="3days"){ return 259200; }
    return 3600;
}

// Pas de dessin : la minute pour une heure, 4 min sur 24 h, 12 min sur 3 jours
// (au plus 360 colonnes sur 820 px ; les pics des tuiles viennent des points bruts).
function nmet_bucket_secs(string $range):int{
    if($range=="day"){ return 240; }
    if($range=="3days"){ return 720; }
    return 60;
}

function nmet_model(int $agent_id,string $range):array{
    $now=time();
    $m=array("id"=>$agent_id,"range"=>$range,"error"=>false,"to"=>$now,"from"=>$now-nmet_range_secs($range),
        "bucket"=>nmet_bucket_secs($range),"raw"=>array(),"cpu"=>array(),"alerts"=>array(),"ram"=>0.0,"last"=>0,"threshold"=>0.0);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/metrics/$agent_id?range=$range"));
    if(!is_object($json) || (isset($json->Status) && !$json->Status)){
        $m["error"]=true;
        return $m;
    }
    $f=nmem_ts($json->from ?? ""); $t=nmem_ts($json->to ?? "");
    if($f>0 && $t>$f){ $m["from"]=$f; $m["to"]=$t; }
    $pts=$json->metrics ?? array();
    if(is_array($pts)){
        foreach($pts as $p){
            if(!is_object($p)){continue;}
            $ts=nmem_ts($p->timestamp ?? "");
            if($ts==0){continue;}
            $m["raw"][]=array("t"=>$ts,"l1"=>floatval($p->cpu_load_1 ?? 0),"l5"=>floatval($p->cpu_load_5 ?? 0),
                "l15"=>floatval($p->cpu_load_15 ?? 0),"mem"=>floatval($p->memory_pct ?? 0),
                "total"=>floatval($p->memory_total ?? 0));
        }
    }
    usort($m["raw"],function($a,$b){ return $a["t"] <=> $b["t"]; });
    $n=count($m["raw"]);
    if($n>0){
        $m["last"]=$m["raw"][$n-1]["t"];
        $m["ram"]=$m["raw"][$n-1]["total"]/1024;
    }
    // CPU : 5 min par point cote agent -> 900 points couvrent 3 jours.
    $cj=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/cpu-history/agent/$agent_id?limit=900"));
    if(is_object($cj) && isset($cj->records) && is_array($cj->records)){
        foreach($cj->records as $r){
            if(!is_object($r)){continue;}
            $ts=nmem_ts($r->timestamp ?? "");
            if($ts<$m["from"] || $ts>$m["to"]){continue;}
            $m["cpu"][]=array("t"=>$ts,"v"=>floatval($r->percentage ?? 0));
        }
        usort($m["cpu"],function($a,$b){ return $a["t"] <=> $b["t"]; });
    }
    $aj=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/memory-alerts/agent/$agent_id?limit=300"));
    if(is_object($aj) && isset($aj->alerts) && is_array($aj->alerts)){
        foreach($aj->alerts as $a){
            if(!is_object($a)){continue;}
            $ts=nmem_ts($a->timestamp ?? "");
            if($ts<$m["from"] || $ts>$m["to"]){continue;}
            $top=array();
            $tp=$a->top_processes ?? array();
            if(is_array($tp)){
                foreach(array_slice($tp,0,3) as $p){
                    if(!is_object($p)){continue;}
                    $top[]=(string)($p->name ?? "")." ".nmem_fmt(floatval($p->memory_bytes ?? 0)/1024);
                }
            }
            $m["alerts"][]=array("t"=>$ts,"pct"=>floatval($a->memory_percent ?? 0),"thr"=>floatval($a->threshold ?? 0),"top"=>$top);
        }
        usort($m["alerts"],function($a,$b){ return $a["t"] <=> $b["t"]; });
        $na=count($m["alerts"]);
        if($na>0){ $m["threshold"]=$m["alerts"][$na-1]["thr"]; }
    }
    return $m;
}

// Moyennes par colonne de dessin ; une colonne vide n'existe pas (trou).
function nmet_buckets(array $pts,string $key,int $from,int $bucket):array{
    $acc=array();
    foreach($pts as $p){
        $b=intval(floor(($p["t"]-$from)/$bucket));
        if(!isset($acc[$b])){ $acc[$b]=array(0.0,0,0); }
        $acc[$b][0]+=$p[$key]; $acc[$b][1]++; $acc[$b][2]+=$p["t"];
    }
    ksort($acc);
    $out=array();
    foreach($acc as $a){ $out[]=array(intval($a[2]/$a[1]),round($a[0]/$a[1],2)); }
    return $out;
}

// Un ecart plus grand que ce seuil entre deux points = l'agent n'a rien envoye.
function nmet_gap_secs(int $step):int{
    return max(180,intval($step*2.5));
}

function nmet_stats(array $pts,string $key):array{
    $n=count($pts);
    if($n==0){ return array("now"=>-1,"avg"=>-1,"peak"=>-1,"peak_t"=>0); }
    $sum=0.0; $peak=-1.0; $pt=0;
    foreach($pts as $p){ $sum+=$p[$key]; if($p[$key]>$peak){ $peak=$p[$key]; $pt=$p["t"]; } }
    return array("now"=>$pts[$n-1][$key],"avg"=>$sum/$n,"peak"=>$peak,"peak_t"=>$pt);
}

function nmet_hm(int $ts,string $range):string{
    if($range=="3days"){ return date("d/m H:i",$ts); }
    return date("H:i",$ts);
}

function nmet_tone(float $pct):array{
    if($pct>=90){ return array("crit","{nmet_tone_crit}"); }
    if($pct>=75){ return array("warn","{nmet_tone_high}"); }
    return array("ok","{nmet_tone_normal}");
}

function nmet_error():string{
    return "<div class='nmem-err' role='alert'><span class='nmem-ico'><i class='fas fa-plug-circle-xmark'></i></span><span>"
        .nmem_tr("nmet_error",array())."</span></div>";
}

function nmet_empty():string{
    return "<div class='nmem-card nmem-emptyc'><span class='nmem-ico'><i class='fas fa-chart-line'></i></span><span>"
        .nmem_tr("nmet_empty",array())."</span></div>";
}

function nmet_period_line(array $m):string{
    $tok=array("hour"=>"nmet_period_hour","day"=>"nmet_period_day","3days"=>"nmet_period_3days");
    $span="<b>".date("d/m H:i",$m["from"])." &rarr; ".date("d/m H:i",$m["to"])."</b>";
    $left=nmem_tr($tok[$m["range"]],array("%s"=>$span));
    $right=nmem_tr("nmet_no_point",array());
    $cls="nmem-report nmem-report-old";
    $ico="fas fa-triangle-exclamation";
    if($m["last"]>0){
        $cls="nmem-report"; $ico="far fa-clock";
        $right=nmem_tr("nmet_last_point",array("%s"=>nmem_ago($m["last"])));
        if(time()-$m["last"]>900){
            $cls.=" nmem-report-old"; $ico="fas fa-triangle-exclamation";
            $right=nmem_tr("nmet_silent",array("%s"=>nmem_ago($m["last"])));
        }
    }
    return "<div class='$cls'><span class='nmem-ico'><i class='$ico'></i></span><span>$left</span><span class='nmem-report-r'>$right</span></div>";
}

function nmet_tile(string $label,string $value,string $tone,string $sub,string $info=""):string{
    $pill="";
    if($tone!==""){
        list($cls,$word)=nmet_tone(floatval($value));
        $pill="<span class='nmem-pill nmem-pill-$cls'>$word</span>";
    }
    return "<div class='nmet-tile'><div class='nmet-tile-h'><span>$label$info</span>$pill</div>"
        ."<div class='nmet-tile-v'>$value</div><div class='nmet-tile-s'>$sub</div></div>";
}

function nmet_tile_cpu(array $m):string{
    $s=nmet_stats(array_map(function($p){ return array("t"=>$p["t"],"v"=>$p["v"]); },$m["cpu"]),"v");
    if($s["now"]<0){
        return nmet_tile("{nmet_cpu_usage}","&mdash;","",nmem_tr("nmet_cpu_none",array()));
    }
    $sub=nmem_tr("nmet_avg_peak",array("%a"=>sprintf("%d %%",round($s["avg"])),"%p"=>sprintf("%d %%",round($s["peak"])),"%t"=>nmet_hm($s["peak_t"],$m["range"])));
    $sub.=" &middot; ".nmem_tr("nmet_sample_at",array("%s"=>nmet_hm($m["cpu"][count($m["cpu"])-1]["t"],$m["range"])));
    return nmet_tile("{nmet_cpu_usage}",sprintf("%d %%",round($s["now"])),"tone",$sub);
}

function nmet_tile_mem(array $m):string{
    $s=nmet_stats($m["raw"],"mem");
    if($s["now"]<0){ return nmet_tile("{nmet_mem_usage}","&mdash;","",""); }
    $sub=nmem_tr("nmet_avg_peak",array("%a"=>sprintf("%d %%",round($s["avg"])),"%p"=>sprintf("%d %%",round($s["peak"])),"%t"=>nmet_hm($s["peak_t"],$m["range"])));
    if($m["ram"]>0){ $sub.=" &middot; ".nmem_tr("nmet_ram",array("%s"=>nmem_fmt($m["ram"]))); }
    return nmet_tile("{nmet_mem_usage}",sprintf("%d %%",round($s["now"])),"tone",$sub);
}

function nmet_tile_load(array $m):string{
    $n=count($m["raw"]);
    $info=" <i class='fas fa-circle-info nmet-info' tabindex='0' title=\"".nmem_tr("nmet_load_help",array())."\" aria-label=\"".nmem_tr("nmet_load_help",array())."\"></i>";
    if($n==0){ return nmet_tile("{nmet_load}","&mdash;","","",$info); }
    $l=$m["raw"][$n-1];
    $sub=nmem_tr("nmet_load_sub",array("%5"=>sprintf("%.2f",$l["l5"]),"%15"=>sprintf("%.2f",$l["l15"])));
    return nmet_tile("{nmet_load}",sprintf("%.2f",$l["l1"])." <small>{nmet_1min}</small>","",$sub,$info);
}

function nmet_inspect_bar(array $m):string{
    return "<div class='nmet-inspect' aria-live='polite'><span class='nmem-ico'><i class='fas fa-crosshairs'></i></span>"
        ."<span class='nmet-inspect-t' data-idle=\"".nmem_tr("nmet_inspect_idle",array())."\">".nmem_tr("nmet_inspect_idle",array())."</span>"
        ."<span class='nmet-inspect-v' data-mem=\"{nmet_mem_usage}\" data-cpu=\"{nmet_cpu_usage}\" data-load=\"{nmet_load}\" data-nodata=\"{nmet_nodata}\"></span></div>";
}

// --- Dessin SVG commun (un helper, pas un widget) -------------------------
// Repere fixe 820x(H) mis a l'echelle par width:100% : pas de deformation.
function nmet_x(array $m,int $t):float{
    $w=820-48-8; $span=max(1,$m["to"]-$m["from"]);
    return 48+($t-$m["from"])*$w/$span;
}

function nmet_y(float $v,float $ymax,int $h):float{
    $top=10; $bot=$h-24;
    return $bot-max(0,min($v,$ymax))*($bot-$top)/$ymax;
}

// Segments d'une serie, coupes aux trous ; renvoie [polylines, bandes grises].
function nmet_paths(array $m,array $series,float $ymax,int $h,int $step,string $fillCls=""):array{
    $gap=nmet_gap_secs($step);
    $segs=array(); $cur=array(); $bands=array(); $prevT=0;
    foreach($series as $p){
        if($prevT>0 && $p[0]-$prevT>$gap){
            $segs[]=$cur; $cur=array();
            $bands[]=array($prevT,$p[0]);
        }
        $cur[]=sprintf("%.1f,%.1f",nmet_x($m,$p[0]),nmet_y($p[1],$ymax,$h));
        $prevT=$p[0];
    }
    if(count($cur)>0){ $segs[]=$cur; }
    $out="";
    foreach($segs as $s){
        if($fillCls!=="" && count($s)>1){
            $first=explode(",",$s[0]); $last=explode(",",$s[count($s)-1]); $base=sprintf("%.1f",nmet_y(0,$ymax,$h));
            $out.="<polygon class='$fillCls' points='".$first[0].",$base ".implode(" ",$s)." ".$last[0].",$base'/>";
        }
        if(count($s)==1){
            $xy=explode(",",$s[0]);
            $out.="<circle cx='{$xy[0]}' cy='{$xy[1]}' r='2' class='nmet-dot'/>";
        }
    }
    return array($out,$segs,$bands);
}

function nmet_axes(array $m,float $ymax,int $h,array $yticks,string $unit):string{
    $o=array();
    foreach($yticks as $v){
        $y=sprintf("%.1f",nmet_y($v,$ymax,$h));
        $lab=$unit=="%"?sprintf("%d%%",$v):rtrim(rtrim(sprintf("%.2f",$v),"0"),".");
        $o[]="<line x1='48' x2='812' y1='$y' y2='$y' class='nmet-grid'/><text x='40' y='$y' dy='4' text-anchor='end' class='nmet-tick'>$lab</text>";
    }
    $span=$m["to"]-$m["from"];
    $stepT=$m["range"]=="hour"?600:($m["range"]=="day"?10800:43200);
    $t0=intval(ceil($m["from"]/$stepT)*$stepT);
    for($t=$t0;$t<=$m["to"];$t+=$stepT){
        $x=nmet_x($m,$t);
        if($x<70 || $x>790){continue;}
        $o[]="<text x='".sprintf("%.1f",$x)."' y='".($h-6)."' text-anchor='middle' class='nmet-tick'>".nmet_hm($t,$m["range"])."</text>";
    }
    $o[]="<text x='48' y='".($h-6)."' text-anchor='start' class='nmet-tick'>".nmet_hm($m["from"],$m["range"])."</text>";
    $o[]="<text x='812' y='".($h-6)."' text-anchor='end' class='nmet-tick nmet-tick-b'>".nmet_hm($m["to"],$m["range"])."</text>";
    unset($span);
    return implode("",$o);
}

function nmet_bands(array $m,array $bands,int $h):string{
    $o="";
    foreach($bands as $b){
        $x1=nmet_x($m,$b[0]); $x2=nmet_x($m,$b[1]);
        $tip=nmem_tr("nmet_gap",array("%a"=>nmet_hm($b[0],$m["range"]),"%b"=>nmet_hm($b[1],$m["range"])));
        $o.="<rect x='".sprintf("%.1f",$x1)."' y='10' width='".sprintf("%.1f",max(2,$x2-$x1))."' height='".($h-34)."' class='nmet-gapband'><title>$tip</title></rect>";
    }
    return $o;
}

function nmet_nodata_text(int $h):string{
    return "<text x='430' y='".intval($h/2)."' text-anchor='middle' class='nmet-tick'>".nmem_tr("nmet_no_point",array())."</text>";
}

function nmet_svg(string $key,int $h,string $inner,string $aria):string{
    return "<svg class='nmet-svg' data-k='$key' viewBox='0 0 820 $h' role='img' aria-label=\"$aria\">$inner"
        ."<line class='nmet-cursor' x1='0' x2='0' y1='10' y2='".($h-24)."' visibility='hidden'/>"
        ."<rect class='nmet-hit' x='48' y='0' width='764' height='".($h-20)."'/></svg>";
}

function nmet_card(string $title,string $sub,string $explain,string $legend,string $svg):string{
    return "<div class='nmem-card nmet-card'><div class='nmem-card-h'><div><div class='nmem-title'>$title <span class='nmet-sub'>$sub</span></div>"
        ."<p class='nmem-explain'>$explain</p></div><div class='nmem-legend'>$legend</div></div>$svg</div>";
}

function nmet_step_label(array $m,int $step):string{
    if($step<=60){ return nmem_tr("nmet_step_1min",array()); }
    return nmem_tr("nmet_step_avg",array("%s"=>intval($step/60)));
}

function nmet_chart_mem(array $m):string{
    $h=210;
    $series=nmet_buckets($m["raw"],"mem",$m["from"],$m["bucket"]);
    list($fill,$segs,$bands)=nmet_paths($m,$series,100,$h,$m["bucket"],"nmet-area");
    $lines="";
    foreach($segs as $s){ if(count($s)>1){ $lines.="<polyline class='nmet-l nmet-l-mem' points='".implode(" ",$s)."'/>"; } }
    $thr=""; $legThr="";
    if($m["threshold"]>0){
        $y=sprintf("%.1f",nmet_y($m["threshold"],100,$h));
        $thr="<line x1='48' x2='812' y1='$y' y2='$y' class='nmet-thr'/>";
        $legThr="<span><i class='nmet-sw-thr'></i>".nmem_tr("nmet_threshold",array("%s"=>sprintf("%d %%",round($m["threshold"]))))."</span>";
    }
    $marks="";
    foreach($m["alerts"] as $a){
        $tip=nmem_tr("nmet_alert_tip",array("%t"=>nmet_hm($a["t"],$m["range"]),"%p"=>sprintf("%d %%",round($a["pct"]))));
        if(count($a["top"])>0){ $tip.=": ".nmem_h(implode(", ",$a["top"])); }
        $marks.="<circle cx='".sprintf("%.1f",nmet_x($m,$a["t"]))."' cy='".sprintf("%.1f",nmet_y($a["pct"],100,$h))."' r='5' class='nmet-alert' tabindex='0' aria-label=\"$tip\"><title>$tip</title></circle>";
    }
    $s=nmet_stats($m["raw"],"mem");
    $aria=$s["now"]<0?"{nmet_mem_usage}":nmem_tr("nmet_mem_aria",array("%a"=>sprintf("%d %%",round($s["avg"])),"%p"=>sprintf("%d %%",round($s["peak"])),"%t"=>nmet_hm($s["peak_t"],$m["range"])));
    $inner=nmet_axes($m,100,$h,array(0,25,50,75,100),"%").nmet_bands($m,$bands,$h).$fill.$lines.$thr.$marks;
    if(count($series)==0){ $inner.=nmet_nodata_text($h); }
    $explain=nmem_tr("nmet_mem_explain",array());
    if(count($m["alerts"])>0){ $explain.=" ".nmem_tr("nmet_mem_explain_alerts",array()); }
    $legend="<span><i class='nmem-sw nmet-sw-mem'></i>{nmet_mem_pct}</span>$legThr";
    return nmet_card("{nmet_mem_usage}",nmet_step_label($m,$m["bucket"]),$explain,$legend,nmet_svg("mem",$h,$inner,$aria));
}

function nmet_chart_cpu(array $m):string{
    $h=170;
    $step=max($m["bucket"],300);
    $series=nmet_buckets($m["cpu"],"v",$m["from"],$step);
    list($fill,$segs,$bands)=nmet_paths($m,$series,100,$h,$step,"nmet-area-cpu");
    $lines="";
    foreach($segs as $s){ if(count($s)>1){ $lines.="<polyline class='nmet-l nmet-l-cpu' points='".implode(" ",$s)."'/>"; } }
    $inner=nmet_axes($m,100,$h,array(0,50,100),"%");
    if(count($series)==0){
        $inner.="<text x='430' y='".intval($h/2)."' text-anchor='middle' class='nmet-tick'>".nmem_tr("nmet_cpu_none",array())."</text>";
    }
    $inner.=nmet_bands($m,$bands,$h).$fill.$lines;
    $s=nmet_stats(array_map(function($p){ return array("t"=>$p["t"],"v"=>$p["v"]); },$m["cpu"]),"v");
    $aria=$s["now"]<0?"{nmet_cpu_usage}":nmem_tr("nmet_cpu_aria",array("%a"=>sprintf("%d %%",round($s["avg"])),"%p"=>sprintf("%d %%",round($s["peak"])),"%t"=>nmet_hm($s["peak_t"],$m["range"])));
    $sub=$step<=300?nmem_tr("nmet_step_5min",array()):nmet_step_label($m,$step);
    return nmet_card("{nmet_cpu_usage}",$sub,nmem_tr("nmet_cpu_explain",array()),
        "<span><i class='nmem-sw nmet-sw-cpu'></i>{nmet_cpu_pct}</span>",nmet_svg("cpu",$h,$inner,$aria));
}

function nmet_chart_load(array $m):string{
    $h=170;
    $max=0.0;
    foreach($m["raw"] as $p){ $max=max($max,$p["l1"],$p["l5"],$p["l15"]); }
    $ymax=max(1.0,ceil($max));
    $ticks=array(0,$ymax/2,$ymax);
    $inner=nmet_axes($m,$ymax,$h,$ticks,"");
    $bands=array();
    foreach(array("l15"=>"nmet-l-l15","l5"=>"nmet-l-l5","l1"=>"nmet-l-l1") as $k=>$cls){
        $series=nmet_buckets($m["raw"],$k,$m["from"],$m["bucket"]);
        list($fill,$segs,$b)=nmet_paths($m,$series,$ymax,$h,$m["bucket"]);
        if($k=="l1"){ $bands=$b; }
        foreach($segs as $s){ if(count($s)>1){ $inner.="<polyline class='nmet-l $cls' points='".implode(" ",$s)."'/>"; } }
        $inner.=$fill;
    }
    $inner=nmet_bands($m,$bands,$h).$inner;
    if(count($m["raw"])==0){ $inner.=nmet_nodata_text($h); }
    $legend="<span><i class='nmet-sw-line nmet-l-l1'></i>{nmet_1min_s}</span><span><i class='nmet-sw-line nmet-l-l5'></i>{nmet_5min_s}</span>"
        ."<span><i class='nmet-sw-line nmet-l-l15'></i>{nmet_15min_s}</span>";
    return nmet_card("{nmet_load}",nmet_step_label($m,$m["bucket"]),nmem_tr("nmet_load_explain",array()),$legend,
        nmet_svg("load",$h,$inner,"{nmet_load}"));
}

// Donnees du curseur : memes colonnes que le dessin, pour lire ce qui est trace.
function nmet_js_data(array $m):array{
    $cstep=max($m["bucket"],300);
    return array("from"=>$m["from"],"to"=>$m["to"],"range"=>$m["range"],
        "gap"=>nmet_gap_secs($m["bucket"]),"cgap"=>nmet_gap_secs($cstep),
        "mem"=>nmet_buckets($m["raw"],"mem",$m["from"],$m["bucket"]),
        "cpu"=>nmet_buckets($m["cpu"],"v",$m["from"],$cstep),
        "l1"=>nmet_buckets($m["raw"],"l1",$m["from"],$m["bucket"]),
        "l5"=>nmet_buckets($m["raw"],"l5",$m["from"],$m["bucket"]),
        "l15"=>nmet_buckets($m["raw"],"l15",$m["from"],$m["bucket"]));
}

function nmet_style():string{
    return "<style>
.nmet-tiles{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:12px}
.nmet-tile{background:#fff;border:1px solid #d5dde0;border-radius:12px;padding:12px 16px;min-height:96px}
.nmet-tile-h{display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:14px;font-weight:600;color:#3d4947}
.nmet-tile-v{font-size:24px;font-weight:700;font-variant-numeric:tabular-nums;margin-top:2px}
.nmet-tile-v small{font-size:13px;font-weight:400;color:#6d7a77}
.nmet-tile-s{font-size:13px;color:#3d4947;line-height:1.4}
.nmet-info{color:#6d7a77;cursor:help;margin-left:4px}
.nmet-inspect{display:flex;align-items:center;gap:8px;flex-wrap:wrap;background:#fff;border:1px dashed #d5dde0;border-radius:10px;padding:8px 14px;margin-bottom:12px;font-size:13px;color:#3d4947;min-height:38px}
.nmet-inspect-t{font-weight:700;color:#111d23}
.nmet-inspect-v b{color:#111d23}
.nmet-card{padding:14px 18px 10px}
.nmet-sub{font-size:13px;font-weight:400;color:#6d7a77}
.nmet-card .nmem-explain{margin:4px 0 6px}
.nmet-svg{display:block;width:100%;height:auto;overflow:visible}
.nmet-grid{stroke:#eef2f4;stroke-width:1}
.nmet-tick{font-size:12px;fill:#6d7a77;font-variant-numeric:tabular-nums}
.nmet-tick-b{font-weight:700;fill:#111d23}
.nmet-l{fill:none;stroke-width:2;stroke-linejoin:round;stroke-linecap:round}
.nmet-l-mem{stroke:var(--nm-dark)}
.nmet-area{fill:var(--nm-dark);opacity:.12}
.nmet-l-cpu{stroke:#334155}
.nmet-area-cpu{fill:#334155;opacity:.08}
.nmet-l-l1{stroke:#0f766e;border-color:#0f766e}
.nmet-l-l5{stroke:#5ea9a2;border-color:#5ea9a2;stroke-width:1.5}
.nmet-l-l15{stroke:#7fbcb5;border-color:#7fbcb5;stroke-width:1.5;stroke-dasharray:4 3}
.nmet-dot{fill:var(--nm-dark)}
.nmet-thr{stroke:#ba1a1a;stroke-width:1.2;stroke-dasharray:5 4}
.nmet-alert{fill:#ba1a1a;stroke:#fff;stroke-width:2;cursor:help}
.nmet-alert:focus{outline:none;stroke:#006a62;stroke-width:3}
.nmet-gapband{fill:#e3e9ec;opacity:.7}
.nmet-cursor{stroke:#0f766e;stroke-width:1;stroke-dasharray:3 3}
.nmet-hit{fill:transparent;cursor:crosshair}
.nmet-sw-mem{background:var(--nm-dark)}
.nmet-sw-cpu{background:#334155}
.nmet-sw-thr{display:inline-block;width:16px;border-top:2px dashed #ba1a1a;margin-right:6px;vertical-align:3px}
.nmet-sw-line{display:inline-block;width:16px;border-top:2px solid;margin-right:6px;vertical-align:3px}
.nmet-sw-line.nmet-l-l1{border-top-color:#0f766e}
.nmet-sw-line.nmet-l-l5{border-top-color:#5ea9a2}
.nmet-sw-line.nmet-l-l15{border-top-color:#7fbcb5;border-top-style:dashed}
@media (prefers-reduced-motion:no-preference){.nmet-l{animation:nmetFade .4s ease-out}}
@keyframes nmetFade{from{opacity:0}to{opacity:1}}
</style>";
}

function nmet_js():string{
    return "<script>
window.NmetData=window.NmetData||{};
function NmetNear(arr,t,gap){
  if(!arr||!arr.length){return null;}
  var lo=0,hi=arr.length-1;
  while(lo<hi){var mid=(lo+hi)>>1;if(arr[mid][0]<t){lo=mid+1;}else{hi=mid;}}
  var best=arr[lo];if(lo>0&&Math.abs(arr[lo-1][0]-t)<Math.abs(best[0]-t)){best=arr[lo-1];}
  return Math.abs(best[0]-t)<=gap/2?best:null;
}
function NmetFmtT(ts,range){
  var d=new Date(ts*1000),p=function(n){return (n<10?'0':'')+n;};
  var hm=p(d.getHours())+':'+p(d.getMinutes());
  return range=='3days'?p(d.getDate())+'/'+p(d.getMonth()+1)+' '+hm:hm;
}
function NmetInit(ns,data){
  NmetData[ns]=data;
  var root=document.getElementById(ns);if(!root){return;}
  var bar=root.querySelector('.nmet-inspect');if(!bar){return;}
  var tEl=bar.querySelector('.nmet-inspect-t'),vEl=bar.querySelector('.nmet-inspect-v');
  var svgs=[].slice.call(root.querySelectorAll('.nmet-svg'));
  function show(t){
    var x=48+(t-data.from)*764/Math.max(1,data.to-data.from);
    svgs.forEach(function(s){var c=s.querySelector('.nmet-cursor');c.setAttribute('x1',x);c.setAttribute('x2',x);c.setAttribute('visibility','visible');});
    var m=NmetNear(data.mem,t,data.gap),c=NmetNear(data.cpu,t,data.cgap),l1=NmetNear(data.l1,t,data.gap),l5=NmetNear(data.l5,t,data.gap),l15=NmetNear(data.l15,t,data.gap);
    var nd=vEl.getAttribute('data-nodata'),parts=[];
    parts.push(vEl.getAttribute('data-mem')+': <b>'+(m?Math.round(m[1])+' %':nd)+'</b>');
    parts.push(vEl.getAttribute('data-cpu')+': <b>'+(c?Math.round(c[1])+' %':nd)+'</b>');
    parts.push(vEl.getAttribute('data-load')+': <b>'+(l1?l1[1].toFixed(2)+' / '+(l5?l5[1].toFixed(2):'-')+' / '+(l15?l15[1].toFixed(2):'-'):nd)+'</b>');
    tEl.textContent=NmetFmtT(t,data.range);vEl.innerHTML=' &middot; '+parts.join(' &middot; ');
  }
  function hide(){
    svgs.forEach(function(s){s.querySelector('.nmet-cursor').setAttribute('visibility','hidden');});
    tEl.textContent=tEl.getAttribute('data-idle');vEl.innerHTML='';
  }
  svgs.forEach(function(s){
    var hit=s.querySelector('.nmet-hit');
    hit.addEventListener('mousemove',function(e){
      var r=s.getBoundingClientRect(),vx=(e.clientX-r.left)*820/r.width;
      var t=data.from+(vx-48)*(data.to-data.from)/764;
      show(Math.max(data.from,Math.min(data.to,t)));
    });
    hit.addEventListener('mouseleave',hide);
  });
}
function NmetTimerStart(ns,url){
  window.NmetTimers=window.NmetTimers||{};
  if(NmetTimers[ns]){clearInterval(NmetTimers[ns]);}
  NmetTimers[ns]=setInterval(function(){
    var h=document.getElementById(ns);
    if(!h){clearInterval(NmetTimers[ns]);delete NmetTimers[ns];return;}
    if(h.offsetParent===null){return;}
    if(document.querySelector('.sweet-alert.visible,.swal2-container')){return;}
    LoadAjaxSilent(ns,url);
  },300000);
}
</script>";
}
