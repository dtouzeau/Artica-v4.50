<?php
// awc_table_style(): feuille de style partagee des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
/**
 * Netagent update history window (Stitch redesign, real data only).
 * Shows the APT upgrade reports a remote node keeps and sends with its status.
 * Each report: timestamp, success, package_name (empty = all updates), duration, packages[], error, apt_output.
 * APIs: GET /netagents/packages/reports/{id} (all decoded reports), GET /netagents/status/{id} (aptget.apt_running)
 * The details are rendered inline from the list: the per-index route counted undecodable reports the list
 * had skipped, so the eye button could open the wrong report.
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
include_once dirname(__FILE__) . '/ressources/class.sockets.inc';
include_once dirname(__FILE__) . '/ressources/class.netagent.artica.inc';

if (isset($_GET["table"])) { aptr_main(); exit; }
js();

function js(): bool {
    $page = CurrentPageName();
    $tpl = new template_admin();
    $users = new usersMenus();
    if (!meta_can_view($users)) {
        return $tpl->js_error("{no_privileges}");
    }
    $id = intval($_GET["id"]);
    $net = new ArticaNetAgents($id);
    $AgentName = $net->GetAgentHostname();
    return $tpl->js_dialog12("{aptr_title} - $AgentName", "$page?table=yes&id=$id", 880);
}

// ==================== Helpers ====================

function aptr_fmt($token,$value):string{
    $tpl=new template_admin();
    return str_replace("%s",$value,$tpl->_ENGINE_parse_body($token));
}

function aptr_ts($v):int{
    $v=trim((string)$v);
    if($v==""){return 0;}
    try{
        $dt=new DateTime($v);
        $ts=$dt->getTimestamp();
        return $ts>0?$ts:0;
    }catch(Exception $e){
        return 0;
    }
}

function aptr_ago($ts):string{
    $ts=intval($ts);
    if($ts<=0){return "&mdash;";}
    $d=time()-$ts;
    if($d<60){return "{aptr_ago_now}";}
    if($d<3600){return aptr_fmt("{aptr_ago_m}",intval($d/60));}
    if($d<86400){return aptr_fmt("{aptr_ago_h}",intval($d/3600));}
    return aptr_fmt("{aptr_ago_d}",intval($d/86400));
}

function aptr_ago_span($ts):string{
    $ts=intval($ts);
    if($ts<=0){return "&mdash;";}
    $tpl=new template_admin();
    return "<span title=\"".$tpl->time_to_date($ts,true)."\">".aptr_ago($ts)."</span>";
}

// Go duration text ("2m13.456s", "1h2m3s", "350ms") -> "2 min 13 s". Unknown formats are shown as received.
function aptr_duration($d):string{
    $d=trim((string)$d);
    if($d==""){return "";}
    if(!preg_match_all('#([0-9]+(?:\.[0-9]+)?)(h|ms|m|s|us|µs|ns)#u',$d,$mm,PREG_SET_ORDER)){return htmlspecialchars($d);}
    $sec=0.0;
    foreach($mm as $x){
        $v=floatval($x[1]);
        switch($x[2]){
            case "h": $sec+=$v*3600;break;
            case "m": $sec+=$v*60;break;
            case "s": $sec+=$v;break;
            case "ms": $sec+=$v/1000;break;
            default: $sec+=$v/1000000;
        }
    }
    if($sec<1){return "&lt; 1 s";}
    $sec=intval(round($sec));
    $h=intval($sec/3600);$m=intval(($sec%3600)/60);$s=$sec%60;
    if($h>0){return "$h h".($m>0?" $m min":"");}
    if($m>0){return "$m min".($s>0?" $s s":"");}
    return "$s s";
}

// One model: reports sorted newest first + node APT state.
function aptr_model($id):array{
    $m=array("id"=>$id,"err"=>"","host"=>"","reports"=>array(),"ok"=>0,"ko"=>0,"changed"=>0,"running"=>false);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/packages/reports/$id"));
    if(!is_object($json)){
        $m["err"]="{error}";
    }elseif(isset($json->Status) && !$json->Status){
        $m["err"]=(string)($json->Error ?? "{error}");
    }else{
        $list=array();
        if(isset($json->reports) && is_array($json->reports)){
            foreach($json->reports as $r){
                $pk=array();
                if(isset($r->packages) && is_array($r->packages)){
                    foreach($r->packages as $p){
                        $pk[]=array("name"=>(string)($p->name ?? ""),"old"=>(string)($p->old_version ?? ""),"new"=>(string)($p->new_version ?? ""));
                    }
                }
                $list[]=array("ts"=>aptr_ts($r->timestamp ?? ""),"raw_ts"=>(string)($r->timestamp ?? ""),
                    "ok"=>!empty($r->success),"pkg"=>trim((string)($r->package_name ?? "")),
                    "dur"=>(string)($r->duration ?? ""),"packages"=>$pk,"error"=>trim((string)($r->error ?? "")),
                    "log"=>(string)($r->apt_output ?? ""));
            }
        }
        usort($list,function($a,$b){ return $b["ts"]-$a["ts"]; });
        foreach($list as $r){
            if($r["ok"]){$m["ok"]++;}else{$m["ko"]++;}
            $m["changed"]+=count($r["packages"]);
        }
        $m["reports"]=$list;
    }
    $st=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id"),true);
    if(is_array($st) && isset($st["aptget"]) && is_array($st["aptget"])){
        $m["running"]=!empty($st["aptget"]["apt_running"]);
    }
    $net=new ArticaNetAgents($id);
    $m["host"]=(string)$net->GetAgentHostname();
    return $m;
}

// ==================== Window ====================

function aptr_main(): bool {
    $tpl = new template_admin();
    $users = new usersMenus();
    if (!meta_can_view($users)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_privileges}"));
        return false;
    }
    $id = intval($_GET["id"]);
    $m=aptr_model($id);

    $html=array();
    $html[]="<div class='aptr'>";
    $html[]=aptr_head($m);
    if($m["err"]!=""){
        $html[]=aptr_banner_error($m);
    }elseif(count($m["reports"])==0){
        $html[]=aptr_hero();
        $html[]=aptr_empty($m);
    }else{
        $html[]=aptr_hero();
        $html[]=aptr_banner_last_failed($m);
        $html[]="<div class='aptr-tiles'>".aptr_tile_last($m).aptr_tile_results($m).aptr_tile_changed($m)."</div>";
        $html[]=aptr_list($m);
    }
    $html[]="</div>";
    echo aptr_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).aptr_js($id);
    return true;
}

// ==================== Widgets ====================

function aptr_style():string{
    if(isset($GLOBALS["APTR_STYLE"])){return "";}
    $GLOBALS["APTR_STYLE"]=true;
    $acc="#005447";$soft="#e6eeed";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$acc=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.aptr{font-size:14px;color:#111827}
.aptr *{box-sizing:border-box}
.aptr-head{display:flex;align-items:center;gap:14px;padding:2px 0 16px 0;border-bottom:1px solid #e5e7eb;margin-bottom:16px}
.aptr-head-ico{flex:0 0 40px;height:40px;border-radius:10px;background:$soft;color:$acc;display:flex;align-items:center;justify-content:center;font-size:18px}
.aptr-head-main{flex:1 1 auto;min-width:0}
.aptr-head-t{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:19px;font-weight:600;line-height:1.3}
.aptr-node{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:500;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;padding:3px 11px;color:#334155;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.aptr-head-sub{font-size:13px;color:#6b7280;margin-top:3px}
.aptr-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 10px;font-size:13px;font-weight:600;white-space:nowrap}
.aptr-ok{background:#ecfdf5;color:#047857}
.aptr-ko{background:#fef2f2;color:#b91c1c}
.aptr-info{background:#dbeafe;color:#1d4ed8}
.aptr-off{background:#f1f5f9;color:#475569}
.aptr-hero{background:#fff;border:1px solid #e5e7eb;border-left:4px solid $acc;border-radius:12px;padding:18px 20px;margin-bottom:16px}
.aptr-hero-t{font-size:16px;font-weight:600;margin:0 0 6px 0}
.aptr-hero-p{font-size:14px;line-height:1.6;color:#374151}
.aptr-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.aptr-chip{display:inline-flex;align-items:center;gap:7px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:999px;padding:4px 12px;font-size:13px;color:#374151}
.aptr-chip i,.aptr-chip svg{color:$acc}
.aptr-ban{display:flex;gap:14px;align-items:flex-start;border-radius:12px;padding:14px 16px;margin-bottom:16px;border:1px solid}
.aptr-ban-ico{flex:0 0 34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px}
.aptr-ban-main{flex:1 1 auto;min-width:0}
.aptr-ban-t{font-weight:600;font-size:14px;margin-bottom:4px}
.aptr-mono{font-family:monospace;font-size:13px;word-break:break-word;white-space:pre-wrap}
.aptr-ban-warn{background:#fffbeb;border-color:#fcd9a0;color:#78350f}
.aptr-ban-warn .aptr-ban-ico{background:#fef3c7;color:#b45309}
.aptr-ban-err{background:#fef2f2;border-color:#fecaca;color:#7f1d1d}
.aptr-ban-err .aptr-ban-ico{background:#fee2e2;color:#b91c1c}
.aptr-link{color:$acc;font-weight:600;font-size:13px;text-decoration:none;cursor:pointer;background:none;border:0;padding:0;white-space:nowrap}
.aptr-link:hover{text-decoration:underline;color:$acc}
.aptr-btn{display:inline-flex;align-items:center;gap:7px;border-radius:8px;padding:6px 12px;font-size:13px;font-weight:600;border:1px solid #d1d5db;background:#fff;color:#1f2937;cursor:pointer;white-space:nowrap;line-height:1.2}
.aptr-btn:hover{background:#f8fafc}
.aptr-btn-primary{background:$acc;border-color:$acc;color:#fff}
.aptr-btn-primary:hover{background:#003d33;border-color:#003d33;color:#fff}
.aptr-btn:focus-visible,.aptr-link:focus-visible,.aptr-row-h:focus-visible{outline:2px solid $acc;outline-offset:2px}
.aptr-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:16px}
.aptr-tile{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;min-height:86px}
.aptr-tile-l{display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:13px;color:#6b7280;margin-bottom:6px}
.aptr-tile-v{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap}
.aptr-big{font-size:24px;font-weight:700;line-height:1.1;color:#111827}
.aptr-mid{font-size:16px;font-weight:600;color:#111827}
.aptr-tile-s{font-size:13px;color:#6b7280}
.aptr-bar{display:flex;height:8px;border-radius:999px;overflow:hidden;background:#f1f5f9;margin-top:8px}
.aptr-bar span{display:block;height:100%}
.aptr-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden}
.aptr-card-h{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #e5e7eb;background:#f8fafc}
.aptr-card-t{font-weight:600;font-size:14px}
.aptr-card-s{font-size:13px;color:#6b7280}
.aptr-row{border-bottom:1px solid #eef0f3;border-left:3px solid transparent}
.aptr-row:last-child{border-bottom:0}
.aptr-row-ko{border-left-color:#fecaca}
.aptr-row-h{display:flex;align-items:center;gap:14px;width:100%;padding:12px 16px;background:#fff;border:0;text-align:left;cursor:pointer;color:inherit}
.aptr-row-h:hover{background:#f8fafc}
.aptr-st{flex:0 0 32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px}
.aptr-what{flex:1 1 auto;min-width:0}
.aptr-what-t{font-weight:600;font-size:14px;color:#111827;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.aptr-what-s{font-size:13px;color:#6b7280;margin-top:2px}
.aptr-what-s .aptr-dot{margin:0 6px;color:#cbd5e1}
.aptr-chg{flex:0 0 auto;font-size:13px;color:#374151;white-space:nowrap}
.aptr-chev{flex:0 0 auto;color:#6b7280;transition:transform .15s}
.aptr-row.open .aptr-chev{transform:rotate(180deg)}
.aptr-det{display:none;padding:0 16px 16px 62px}
.aptr-row.open .aptr-det{display:block}
.aptr-panel{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:14px}
.aptr-why{background:#fef2f2;border:1px solid #fecaca;color:#7f1d1d;border-radius:8px;padding:12px 14px;margin-bottom:12px}
.aptr-why-t{font-weight:600;font-size:13px;margin-bottom:4px;display:flex;align-items:center;gap:7px}
.aptr-sec-t{font-weight:600;font-size:14px;margin:0 0 8px 0}
.aptr-none{font-size:13px;color:#6b7280;margin-bottom:12px}
.aptr .awc-wrap{margin:0 0 12px 0}
.aptr .awc-tbl thead th{font-size:13px}
.aptr-ver{font-family:monospace;font-size:13px;color:#4b5563;white-space:nowrap}
.aptr-new{font-family:monospace;font-size:13px;color:$acc;font-weight:700;white-space:nowrap}
.aptr-more{display:none}
.aptr-tbl-all .aptr-more{display:table-row}
.aptr-logbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.aptr-hint{font-size:13px;color:#6b7280;flex:1 1 auto}
.aptr-log{display:none;margin-top:10px;background:#0f172a;color:#e2e8f0;font-family:monospace;font-size:13px;line-height:1.5;padding:12px 14px;border-radius:8px;max-height:320px;overflow:auto;white-space:pre-wrap;word-break:break-word}
.aptr-log.open{display:block}
.aptr-empty{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:36px 24px;text-align:center}
.aptr-empty-ico{width:56px;height:56px;border-radius:50%;margin:0 auto 12px auto;display:flex;align-items:center;justify-content:center;font-size:24px;background:$soft;color:$acc}
.aptr-empty-t{font-size:16px;font-weight:600;margin-bottom:6px}
.aptr-empty-p{font-size:14px;color:#6b7280;margin-bottom:16px;line-height:1.55}
@media (prefers-reduced-motion:reduce){.aptr-chev{transition:none}}
@media (max-width:820px){.aptr-tiles{grid-template-columns:1fr}}
</style>";
}

function aptr_head($m):string{
    $host=htmlspecialchars($m["host"]);
    $state=$m["running"]
        ?"<span class='aptr-pill aptr-info'><i class='fas fa-arrows-rotate' aria-hidden='true'></i>{aptr_running}</span>"
        :"";
    return "<div class='aptr-head'><div class='aptr-head-ico' aria-hidden='true'><i class='fas fa-clock-rotate-left'></i></div>"
        ."<div class='aptr-head-main'><div class='aptr-head-t'>{aptr_title}<span class='aptr-node' title=\"$host\"><i class='fas fa-server' aria-hidden='true'></i>$host</span></div>"
        ."<div class='aptr-head-sub'>{aptr_subtitle}</div></div>$state</div>";
}

function aptr_hero():string{
    return "<div class='aptr-hero'><div class='aptr-hero-t'>{aptr_hero_title}</div><div class='aptr-hero-p'>{aptr_hero_text}</div>"
        ."<div class='aptr-chips'>"
        ."<span class='aptr-chip'><i class='fas fa-server' aria-hidden='true'></i>{aptr_chip_node}</span>"
        ."<span class='aptr-chip'><i class='fas fa-code-compare' aria-hidden='true'></i>{aptr_chip_versions}</span>"
        ."<span class='aptr-chip'><i class='fas fa-file-lines' aria-hidden='true'></i>{aptr_chip_log}</span>"
        ."</div></div>";
}

function aptr_banner_last_failed($m):string{
    $r=$m["reports"][0];
    if($r["ok"]){return "";}
    $id=$m["id"];
    $err=$r["error"]!=""?"<div class='aptr-mono'>".htmlspecialchars($r["error"])."</div>":"";
    return "<div class='aptr-ban aptr-ban-warn' role='alert'><div class='aptr-ban-ico' aria-hidden='true'><i class='fas fa-triangle-exclamation'></i></div>"
        ."<div class='aptr-ban-main'><div class='aptr-ban-t'>{aptr_last_failed}</div>$err</div>"
        ."<button type='button' class='aptr-link' OnClick=\"AptrOpen$id(0);\">{aptr_show_report}</button></div>";
}

function aptr_banner_error($m):string{
    return "<div class='aptr-ban aptr-ban-err' role='alert'><div class='aptr-ban-ico' aria-hidden='true'><i class='fas fa-circle-xmark'></i></div>"
        ."<div class='aptr-ban-main'><div class='aptr-ban-t'>{aptr_err_t}</div><div class='aptr-mono'>".htmlspecialchars($m["err"])."</div></div></div>";
}

function aptr_status_pill($ok):string{
    return $ok
        ?"<span class='aptr-pill aptr-ok'><i class='fas fa-check' aria-hidden='true'></i>{aptr_succeeded}</span>"
        :"<span class='aptr-pill aptr-ko'><i class='fas fa-xmark' aria-hidden='true'></i>{aptr_failed}</span>";
}

function aptr_tile_last($m):string{
    $r=$m["reports"][0];
    return "<div class='aptr-tile'><div class='aptr-tile-l'><span>{aptr_last_update}</span>".aptr_status_pill($r["ok"])."</div>"
        ."<div class='aptr-tile-v'><span class='aptr-mid'>".aptr_ago_span($r["ts"])."</span></div></div>";
}

function aptr_tile_results($m):string{
    $n=$m["ok"]+$m["ko"];
    $okp=$n>0?round($m["ok"]*100/$n,2):0;
    $kop=$n>0?100-$okp:0;
    $txt=aptr_fmt("{aptr_n_succeeded}",$m["ok"])." &middot; ".aptr_fmt("{aptr_n_failed}",$m["ko"]);
    $kept=$n==1?"{aptr_one_report}":aptr_fmt("{aptr_n_reports}",$n);
    return "<div class='aptr-tile'><div class='aptr-tile-l'><span>{aptr_results}</span><span>$kept</span></div>"
        ."<div class='aptr-mid' style='font-size:15px'>$txt</div>"
        ."<div class='aptr-bar' role='img' aria-label=\"".strip_tags($txt)."\"><span style='width:$okp%;background:#059669'></span><span style='width:$kop%;background:#dc2626'></span></div></div>";
}

function aptr_tile_changed($m):string{
    $n=count($m["reports"]);
    $in=$n==1?"{aptr_in_one_report}":aptr_fmt("{aptr_in_n_reports}",$n);
    return "<div class='aptr-tile'><div class='aptr-tile-l'><span>{aptr_programs_changed}</span><span>{aptr_debian_packages}</span></div>"
        ."<div class='aptr-tile-v'><span class='aptr-big'>".number_format($m["changed"])."</span><span class='aptr-tile-s'>$in</span></div></div>";
}

function aptr_list($m):string{
    $n=count($m["reports"]);
    $h=array();
    $h[]="<div class='aptr-card'><div class='aptr-card-h'><span class='aptr-card-t'>{aptr_list_title}</span>"
        ."<span class='aptr-card-s'>{aptr_list_hint}</span></div>";
    foreach($m["reports"] as $i=>$r){
        $h[]=aptr_row($m["id"],$i,$r);
    }
    $h[]="</div>";
    return @implode("\n",$h);
}

function aptr_row($id,$i,$r):string{
    $tpl=new template_admin();
    $what=$r["pkg"]!=""?htmlspecialchars($r["pkg"]):"{aptr_all_updates}";
    $dur=aptr_duration($r["dur"]);
    $sub=aptr_ago_span($r["ts"]).($dur!=""?"<span class='aptr-dot' aria-hidden='true'>&middot;</span>$dur":"");
    $nchg=count($r["packages"]);
    $chg=$nchg==0?"{aptr_no_change}":($nchg==1?"{aptr_one_change}":aptr_fmt("{aptr_n_changes}",$nchg));
    $st=$r["ok"]
        ?"<span class='aptr-st aptr-ok' aria-hidden='true'><i class='fas fa-check'></i></span>"
        :"<span class='aptr-st aptr-ko' aria-hidden='true'><i class='fas fa-xmark'></i></span>";
    $when=$r["ts"]>0?$tpl->time_to_date($r["ts"],true):htmlspecialchars($r["raw_ts"]);
    $label=aptr_fmt("{aptr_show_details_of}",$when);
    $cls="aptr-row".($r["ok"]?"":" aptr-row-ko");
    return "<div class='$cls' id='aptr-row-$id-$i'>"
        ."<button type='button' class='aptr-row-h' aria-expanded='false' aria-controls='aptr-det-$id-$i' aria-label=\"$label\" OnClick=\"AptrToggle$id($i);\">"
        ."$st<span class='aptr-what'><span class='aptr-what-t' style='display:block'>$what</span><span class='aptr-what-s' style='display:block'>$sub</span></span>"
        ."<span class='aptr-chg'>$chg</span>".aptr_status_pill($r["ok"])
        ."<span class='aptr-chev' aria-hidden='true'><i class='fas fa-chevron-down'></i></span></button>"
        ."<div class='aptr-det' id='aptr-det-$id-$i'>".aptr_detail($id,$i,$r)."</div></div>";
}

function aptr_detail($id,$i,$r):string{
    $h=array();
    $h[]="<div class='aptr-panel'>";
    if(!$r["ok"] || $r["error"]!=""){
        $err=$r["error"]!=""?htmlspecialchars($r["error"]):"{aptr_no_error_text}";
        $h[]="<div class='aptr-why'><div class='aptr-why-t'><i class='fas fa-circle-xmark' aria-hidden='true'></i>{aptr_why_failed}</div><div class='aptr-mono'>$err</div></div>";
    }
    $h[]=aptr_changes_table($id,$i,$r["packages"]);
    if(trim($r["log"])!=""){
        $h[]="<div class='aptr-logbar'>"
            ."<button type='button' class='aptr-btn' aria-expanded='false' aria-controls='aptr-log-$id-$i' OnClick=\"AptrLog$id($i,this);\"><i class='fas fa-terminal' aria-hidden='true'></i>{aptr_show_log}</button>"
            ."<span class='aptr-hint'>{aptr_log_hint}</span>"
            ."<button type='button' class='aptr-btn' aria-label=\"{aptr_copy_log}\" OnClick=\"AptrCopy$id($i,this);\"><i class='fas fa-copy' aria-hidden='true'></i>{aptr_copy}</button></div>";
        $h[]="<pre class='aptr-log' id='aptr-log-$id-$i'>".htmlspecialchars($r["log"])."</pre>";
    }
    $h[]="</div>";
    return @implode("\n",$h);
}

function aptr_changes_table($id,$i,$packages):string{
    if(count($packages)==0){
        return "<div class='aptr-none'><i class='fas fa-circle-info' aria-hidden='true'></i> {aptr_none_changed}</div>";
    }
    $h=array();
    $h[]="<div class='aptr-sec-t'>{aptr_what_changed}</div>";
    $h[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact' id='aptr-tbl-$id-$i'>";
    $h[]="<thead><tr><th>{aptr_col_program}</th><th>{aptr_col_before}</th><th aria-hidden='true'></th><th>{aptr_col_after}</th></tr></thead><tbody>";
    foreach($packages as $k=>$p){
        $cls=$k>=10?" class='aptr-more'":"";
        $old=$p["old"]!=""?htmlspecialchars($p["old"]):"&mdash;";
        $new=$p["new"]!=""?htmlspecialchars($p["new"]):"&mdash;";
        $h[]="<tr$cls><td><strong>".htmlspecialchars($p["name"])."</strong></td><td><span class='aptr-ver'>$old</span></td>"
            ."<td style='color:#9ca3af' aria-hidden='true'><i class='fas fa-arrow-right'></i></td><td><span class='aptr-new'>$new</span></td></tr>";
    }
    $h[]="</tbody></table></div></div></div>";
    if(count($packages)>10){
        $h[]="<div style='margin:-4px 0 12px 0'><button type='button' class='aptr-link' OnClick=\"document.getElementById('aptr-tbl-$id-$i').classList.add('aptr-tbl-all');this.parentNode.style.display='none';\">"
            .aptr_fmt("{aptr_show_all}",count($packages))."</button></div>";
    }
    return @implode("\n",$h);
}

function aptr_empty($m):string{
    $id=$m["id"];
    return "<div class='aptr-empty'><div class='aptr-empty-ico' aria-hidden='true'><i class='fas fa-clock-rotate-left'></i></div>"
        ."<div class='aptr-empty-t'>{aptr_empty_t}</div><div class='aptr-empty-p'>{aptr_empty_text}</div>"
        ."<button type='button' class='aptr-btn aptr-btn-primary' OnClick=\"Loadjs('fw.netagents.aptget.upgrade.php?id=$id');\"><i class='fas fa-box-open' aria-hidden='true'></i>{aptr_open_updates}</button></div>";
}

function aptr_js($id):string{
    $tpl=new template_admin();
    $hide=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{aptr_hide_log}"),ENT_QUOTES,"UTF-8"),JSON_INVALID_UTF8_SUBSTITUTE);
    $show=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{aptr_show_log}"),ENT_QUOTES,"UTF-8"),JSON_INVALID_UTF8_SUBSTITUTE);
    $copied=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{aptr_copied}"),ENT_QUOTES,"UTF-8"),JSON_INVALID_UTF8_SUBSTITUTE);
    return "<script>NoSpinner();"
        ."function AptrToggle$id(i){var r=document.getElementById('aptr-row-$id-'+i);if(!r){return;}var o=r.classList.toggle('open');var b=r.querySelector('.aptr-row-h');if(b){b.setAttribute('aria-expanded',o?'true':'false');}}"
        ."function AptrOpen$id(i){var r=document.getElementById('aptr-row-$id-'+i);if(!r){return;}if(!r.classList.contains('open')){AptrToggle$id(i);}if(r.scrollIntoView){r.scrollIntoView({block:'start',behavior:'smooth'});}}"
        ."function AptrLog$id(i,btn){var l=document.getElementById('aptr-log-$id-'+i);if(!l){return;}var o=l.classList.toggle('open');btn.setAttribute('aria-expanded',o?'true':'false');"
        ."var s=btn.lastChild;if(s&&s.nodeType===3){s.nodeValue=o?$hide:$show;}}"
        ."function AptrCopy$id(i,btn){var l=document.getElementById('aptr-log-$id-'+i);if(!l){return;}var t=l.textContent;"
        ."var done=function(){var s=btn.lastChild;if(s&&s.nodeType===3){var k=s.nodeValue;s.nodeValue=$copied;setTimeout(function(){s.nodeValue=k;},1500);}};"
        ."if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(t).then(done,function(){});return;}"
        ."var ta=document.createElement('textarea');ta.value=t;ta.style.position='fixed';ta.style.opacity='0';document.body.appendChild(ta);ta.select();try{document.execCommand('copy');done();}catch(e){}document.body.removeChild(ta);}"
        ."</script>";
}
