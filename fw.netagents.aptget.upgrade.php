<?php
// awc_table_style(): feuille de style partagee des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
/**
 * Netagent software updates window (Stitch redesign, real data only).
 * Lists the Debian packages a remote node can upgrade and starts their installation ON the node.
 * APIs: GET /netagents/packages/list/{id} (cache: 1 h, refreshed every 30 min, re-read 3 min after an upgrade),
 *       GET /netagents/status/{id} (aptget: apt_running, last_upgrade, last_results),
 *       POST /netagents/packages/upgrade/{id}/{name}, POST /netagents/packages/upgrade/all/{id},
 *       POST /netagents/packages/dist-upgrade/{id}, POST /netagents/packages/fetch/{id}
 */
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
include_once dirname(__FILE__) . '/ressources/class.sockets.inc';
include_once dirname(__FILE__) . '/ressources/class.netagent.artica.inc';

if(isset($_GET["table-start"])){aptu_start();exit;}
if(isset($_GET["main"])){aptu_main();exit;}
if(isset($_GET["upgrade-single-js"])){upgrade_single_js();exit;}
if(isset($_POST["upgrade-single"])){upgrade_single();exit;}
if(isset($_GET["upgrade-all-js"])){upgrade_all_js();exit;}
if(isset($_POST["upgrade-all"])){upgrade_all();exit;}
if(isset($_GET["dist-upgrade-js"])){dist_upgrade_js();exit;}
if(isset($_POST["dist-upgrade"])){dist_upgrade();exit;}
if(isset($_GET["refresh-js"])){refresh_js();exit;}
if(isset($_POST["refresh-fetch"])){refresh_fetch();exit;}
js();

function js():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $users=new usersMenus();
    if(!meta_can_view($users)){
        return $tpl->js_error("{no_privileges}");
    }
    $id=intval($_GET["id"]);
    $net=new ArticaNetAgents($id);
    $AgentName=$net->GetAgentHostname();
    return $tpl->js_dialog4("{aptu_title} - $AgentName", "$page?table-start=yes&id=$id", 900);
}

// ==================== Helpers ====================

function aptu_fmt($token,$value):string{
    $tpl=new template_admin();
    return str_replace("%s",$value,$tpl->_ENGINE_parse_body($token));
}

// Go timestamps: RFC3339; the zero time (0001-01-01) means "unknown".
function aptu_ts($v):int{
    if(is_numeric($v)){return intval($v);}
    $v=trim((string)$v);
    if($v==""){return 0;}
    $ts=strtotime($v);
    return ($ts===false || $ts<=0)?0:$ts;
}

function aptu_ago($ts):string{
    $ts=intval($ts);
    if($ts<=0){return "";}
    $d=time()-$ts;
    if($d<60){return "{aptu_ago_now}";}
    if($d<3600){return aptu_fmt("{aptu_ago_m}",intval($d/60));}
    if($d<86400){return aptu_fmt("{aptu_ago_h}",intval($d/3600));}
    return aptu_fmt("{aptu_ago_d}",intval($d/86400));
}

function aptu_ago_span($ts):string{
    $ts=intval($ts);
    if($ts<=0){return "";}
    $tpl=new template_admin();
    return "<span title=\"".$tpl->time_to_date($ts,true)."\">".aptu_ago($ts)."</span>";
}

// dpkg reports Installed-Size in KiB; anything else is shown as received.
function aptu_size($v):string{
    $v=trim((string)$v);
    if($v==""){return "&mdash;";}
    if(is_numeric($v)){
        $kb=floatval($v);
        if($kb<=0){return "&mdash;";}
        return FormatBytes($kb,true);
    }
    return htmlspecialchars($v);
}

function aptu_deny():bool{
    $users=new usersMenus();
    if(meta_can_view($users)){return false;}
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body("{no_privileges}");
    return true;
}

// One model for the whole window: package list (daemon cache) + node APT state (status cache).
function aptu_model($id):array{
    $m=array("id"=>$id,"err"=>"","nodata"=>false,"host"=>"","upg"=>0,"installed"=>0,"last_fetch"=>0,
        "last_apt"=>0,"fetch_error"=>"","dist"=>false,"packages"=>array(),"running"=>false,"last_up"=>0,
        "last_res"=>false);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/packages/list/$id"));
    if(!is_object($json)){
        $m["err"]="{error}";
    }elseif(isset($json->Status) && !$json->Status){
        $m["err"]=(string)($json->Error ?? "{error}");
    }elseif(isset($json->message) && !isset($json->packages) && !isset($json->last_fetch)){
        $m["nodata"]=true;
    }else{
        $m["host"]=(string)($json->hostname ?? "");
        $m["upg"]=intval($json->upgradable_count ?? 0);
        $m["installed"]=intval($json->installed_count ?? 0);
        $m["last_fetch"]=aptu_ts($json->last_fetch ?? "");
        $m["last_apt"]=aptu_ts($json->last_apt_update ?? "");
        $m["fetch_error"]=trim((string)($json->fetch_error ?? ""));
        $m["dist"]=!empty($json->dist_upgrade_required);
        $pk=array();
        if(isset($json->packages) && is_array($json->packages)){
            foreach($json->packages as $p){
                $pk[]=array("name"=>(string)($p->name ?? ""),"version"=>(string)($p->version ?? ""),
                    "new"=>(string)($p->new_version ?? ""),"arch"=>(string)($p->architecture ?? ""),
                    "desc"=>(string)($p->description ?? ""),"size"=>(string)($p->installed_size ?? ""));
            }
        }
        $m["packages"]=$pk;
    }

    $st=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id"),true);
    if(is_array($st) && isset($st["aptget"]) && is_array($st["aptget"])){
        $a=$st["aptget"];
        $m["running"]=!empty($a["apt_running"]);
        $m["last_up"]=intval($a["last_upgrade"] ?? 0);
        $m["last_res"]=!empty($a["last_results"]);
        if(!empty($a["dist_upgrade_required"])){$m["dist"]=true;}
    }
    if($m["host"]==""){
        $net=new ArticaNetAgents($id);
        $m["host"]=(string)$net->GetAgentHostname();
    }
    return $m;
}

// ==================== Window ====================

function aptu_start():bool{
    $page=CurrentPageName();
    $id=intval($_GET["id"]);
    echo "<div class='aptu'><div id='aptu-toast-$id' role='status' aria-live='polite'></div><div id='aptu-main-$id'></div></div>"
        ."<script>LoadAjaxSilent('aptu-main-$id','$page?main=yes&id=$id');</script>";
    return true;
}

function aptu_main():bool{
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    if(aptu_deny()){return false;}
    $m=aptu_model($id);

    $html=array();
    $html[]=aptu_head($m);
    if($m["err"]!=""){
        $html[]=aptu_banner_error($m);
        echo aptu_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).aptu_js($m);
        return true;
    }
    if($m["nodata"]){
        $html[]=aptu_empty_nodata($m);
        echo aptu_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).aptu_js($m);
        return true;
    }
    $html[]=aptu_hero();
    $html[]="<div class='aptu-tiles'>".aptu_tile_waiting($m).aptu_tile_last($m).aptu_tile_running($m)."</div>";
    $html[]=aptu_banner_fetch($m);
    $html[]=aptu_banner_dist($m);
    if(count($m["packages"])==0){
        $html[]=aptu_empty_uptodate($m);
        echo aptu_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).aptu_js($m);
        return true;
    }
    $html[]=aptu_list($m);
    echo awc_table_style().aptu_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).aptu_js($m)
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"aptu-rows-$id","aptu-pager-$id");
    return true;
}

// ==================== Widgets ====================

function aptu_style():string{
    if(isset($GLOBALS["APTU_STYLE"])){return "";}
    $GLOBALS["APTU_STYLE"]=true;
    $acc="#006a62";$soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$acc=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.aptu{font-size:14px;color:#111827}
.aptu *{box-sizing:border-box}
.aptu-head{display:flex;align-items:center;gap:16px;padding:4px 0 16px 0;border-bottom:1px solid #e5e7eb;margin-bottom:16px}
.aptu-head-ico{flex:0 0 40px;height:40px;border-radius:10px;background:$soft;color:$acc;display:flex;align-items:center;justify-content:center;font-size:18px}
.aptu-head-main{flex:1 1 auto;min-width:0}
.aptu-head-t{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:19px;font-weight:600;line-height:1.3}
.aptu-node{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:500;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;padding:3px 11px;color:#334155;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.aptu-head-sub{font-size:13px;color:#6b7280;margin-top:4px}
.aptu-head-sub .aptu-dot{margin:0 6px;color:#cbd5e1}
.aptu-head-acts{display:flex;gap:8px;align-items:center;flex:0 0 auto}
.aptu-btn{display:inline-flex;align-items:center;gap:7px;border-radius:8px;padding:7px 14px;font-size:13px;font-weight:600;border:1px solid #d1d5db;background:#fff;color:#1f2937;cursor:pointer;white-space:nowrap;line-height:1.2;text-decoration:none}
.aptu-btn:hover{background:#f8fafc;color:#111827;text-decoration:none}
.aptu-btn:focus-visible,.aptu-link:focus-visible,.aptu-search input:focus-visible{outline:2px solid $acc;outline-offset:2px}
.aptu-btn-primary{background:$acc;border-color:$acc;color:#fff}
.aptu-btn-primary:hover{filter:brightness(.93);background:$acc;color:#fff}
.aptu-btn-warn{background:#b45309;border-color:#b45309;color:#fff}
.aptu-btn-warn:hover{filter:brightness(.93);background:#b45309;color:#fff}
.aptu-btn-ghost{border-color:transparent;background:transparent;color:#374151}
.aptu-btn-sm{padding:5px 11px}
.aptu-btn[disabled],.aptu-btn[aria-disabled=true]{opacity:.5;cursor:not-allowed;filter:none}
.aptu-hero{background:#fff;border:1px solid #e5e7eb;border-left:4px solid $acc;border-radius:12px;padding:18px 20px;margin-bottom:16px}
.aptu-hero-t{font-size:16px;font-weight:600;margin:0 0 6px 0}
.aptu-hero-p{font-size:14px;line-height:1.6;color:#374151}
.aptu-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.aptu-chip{display:inline-flex;align-items:center;gap:7px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:999px;padding:4px 12px;font-size:13px;color:#374151}
.aptu-chip i,.aptu-chip svg{color:$acc}
.aptu-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:16px}
.aptu-tile{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;min-height:84px}
.aptu-tile-l{font-size:13px;color:#6b7280;margin-bottom:6px}
.aptu-tile-v{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap}
.aptu-big{font-size:26px;font-weight:700;line-height:1;color:#111827}
.aptu-tile-s{font-size:13px;color:#6b7280}
.aptu-tile-run{border-color:#bfdbfe;background:#eff6ff}
.aptu-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 10px;font-size:13px;font-weight:600;white-space:nowrap}
.aptu-ok{background:#ecfdf5;color:#047857}
.aptu-err{background:#fef2f2;color:#b91c1c}
.aptu-info{background:#dbeafe;color:#1d4ed8}
.aptu-off{background:#f1f5f9;color:#475569}
.aptu-link{color:$acc;font-weight:600;font-size:13px;text-decoration:none;cursor:pointer}
.aptu-link:hover{text-decoration:underline;color:$acc}
.aptu-ban{display:flex;gap:14px;align-items:flex-start;border-radius:12px;padding:14px 16px;margin-bottom:16px;border:1px solid}
.aptu-ban-ico{flex:0 0 34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:15px}
.aptu-ban-main{flex:1 1 auto;min-width:0}
.aptu-ban-t{font-weight:600;font-size:14px;margin-bottom:3px}
.aptu-ban-p{font-size:13px;line-height:1.55}
.aptu-ban-raw{font-family:monospace;font-size:13px;opacity:.85;margin-top:4px;word-break:break-word}
.aptu-ban-tech{display:inline-block;font-family:monospace;font-size:13px;opacity:.75;margin-left:6px}
.aptu-ban-warn{background:#fffbeb;border-color:#fcd9a0;color:#78350f}
.aptu-ban-warn .aptu-ban-ico{background:#fef3c7;color:#b45309}
.aptu-ban-dist{background:#f2f6f6;border-color:#bfd4d1;color:#003d33}
.aptu-ban-dist .aptu-ban-ico{background:#e6eeed;color:#005447}
.aptu-ban-err{background:#fef2f2;border-color:#fecaca;color:#7f1d1d}
.aptu-ban-err .aptu-ban-ico{background:#fee2e2;color:#b91c1c}
.aptu-toast{display:flex;align-items:center;gap:10px;border-radius:10px;padding:10px 14px;margin-bottom:12px;font-size:13px;font-weight:500;border:1px solid}
.aptu-toast-ok{background:#ecfdf5;border-color:#a7f3d0;color:#065f46}
.aptu-toast-err{background:#fef2f2;border-color:#fecaca;color:#7f1d1d}
.aptu-toast-info{background:#eff6ff;border-color:#bfdbfe;color:#1e3a8a}
.aptu-toast span{flex:1 1 auto}
.aptu-toast button{border:0;background:transparent;color:inherit;cursor:pointer;font-size:14px}
.aptu-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden}
.aptu-bar{display:flex;align-items:center;gap:12px;padding:14px 16px;border-bottom:1px solid #e5e7eb}
.aptu-search{flex:1 1 auto;position:relative;max-width:420px}
.aptu-search i,.aptu-search svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:13px}
.aptu-search input{width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px 12px 8px 32px;font-size:14px;background:#fff}
.aptu-count{margin-left:auto;font-size:13px;color:#6b7280;white-space:nowrap}
.aptu-count b{color:#111827}
.aptu .awc-wrap{margin:0}
.aptu .awc-card{border:0;border-radius:0;box-shadow:none}
.aptu .awc-tbl thead th{font-size:13px}
.aptu-pkg-n{font-weight:600;font-size:14px;color:#111827;word-break:break-all}
.aptu-arch{display:inline-block;margin-left:6px;font-size:13px;color:#475569;background:#f1f5f9;border-radius:4px;padding:1px 6px;vertical-align:1px}
.aptu-desc{font-size:13px;color:#6b7280;margin-top:2px;max-width:330px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.aptu-ver{font-family:monospace;font-size:13px;color:#4b5563;white-space:nowrap}
.aptu-new{font-family:monospace;font-size:13px;color:$acc;font-weight:700;white-space:nowrap}
.aptu-arrow{color:#9ca3af;text-align:center}
.aptu .awc-tbl td.aptu-size{font-size:13px;color:#4b5563;white-space:nowrap;text-align:right}
.aptu-empty{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:36px 24px;text-align:center}
.aptu-empty-ico{width:56px;height:56px;border-radius:50%;margin:0 auto 12px auto;display:flex;align-items:center;justify-content:center;font-size:24px}
.aptu-empty-ok .aptu-empty-ico{background:#ecfdf5;color:#059669}
.aptu-empty-wait .aptu-empty-ico{background:$soft;color:$acc}
.aptu-empty-t{font-size:16px;font-weight:600;margin-bottom:6px}
.aptu-empty-p{font-size:14px;color:#6b7280;margin-bottom:16px;line-height:1.55}
.aptu-spin{animation:aptuspin 1.2s linear infinite}
@keyframes aptuspin{to{transform:rotate(360deg)}}
@media (prefers-reduced-motion:reduce){.aptu-spin{animation:none}}
@media (max-width:820px){.aptu-tiles{grid-template-columns:1fr}.aptu-head{flex-wrap:wrap}}
</style>";
}

function aptu_head($m):string{
    $id=$m["id"];
    $sub=array();
    if($m["last_fetch"]>0){$sub[]=aptu_fmt("{aptu_checked}",aptu_ago_span($m["last_fetch"]));}
    elseif(!$m["nodata"] && $m["err"]==""){$sub[]="{aptu_never_checked}";}
    if($m["last_apt"]>0){$sub[]=aptu_fmt("{aptu_catalog}",aptu_ago_span($m["last_apt"]));}
    $subH=count($sub)>0?"<div class='aptu-head-sub'>".@implode("<span class='aptu-dot' aria-hidden='true'>&middot;</span>",$sub)."</div>":"";
    $host=htmlspecialchars($m["host"]);
    return "<div class='aptu-head'>"
        ."<div class='aptu-head-ico' aria-hidden='true'><i class='fas fa-box-open'></i></div>"
        ."<div class='aptu-head-main'><div class='aptu-head-t'>{aptu_title}"
        ."<span class='aptu-node' title=\"$host\"><i class='fas fa-server' aria-hidden='true'></i>$host</span></div>$subH</div>"
        ."<div class='aptu-head-acts'>"
        ."<a href='javascript:blur();' class='aptu-btn aptu-btn-ghost' OnClick=\"Loadjs('fw.netagents.aptget.reports.php?id=$id');\"><i class='fas fa-file-lines' aria-hidden='true'></i>{aptu_reports}</a>"
        ."<button type='button' class='aptu-btn' OnClick=\"AptuCheck$id();\"><i class='fas fa-arrows-rotate' aria-hidden='true'></i>{aptu_check_again}</button>"
        ."</div></div>";
}

function aptu_hero():string{
    return "<div class='aptu-hero'><div class='aptu-hero-t'>{aptu_hero_title}</div>"
        ."<div class='aptu-hero-p'>{aptu_hero_text}</div>"
        ."<div class='aptu-chips'>"
        ."<span class='aptu-chip'><i class='fas fa-server' aria-hidden='true'></i>{aptu_chip_node}</span>"
        ."<span class='aptu-chip'><i class='fas fa-circle-check' aria-hidden='true'></i>{aptu_chip_newer}</span>"
        ."<span class='aptu-chip'><i class='fas fa-file-lines' aria-hidden='true'></i>{aptu_chip_report}</span>"
        ."</div></div>";
}

function aptu_tile_waiting($m):string{
    $sub=$m["installed"]>0?"<span class='aptu-tile-s'>".aptu_fmt("{aptu_out_of}",number_format($m["installed"]))."</span>":"";
    return "<div class='aptu-tile'><div class='aptu-tile-l'>{aptu_waiting}</div>"
        ."<div class='aptu-tile-v'><span class='aptu-big'>".number_format($m["upg"])."</span>$sub</div></div>";
}

function aptu_tile_last($m):string{
    $id=$m["id"];
    if($m["last_up"]<=0){
        return "<div class='aptu-tile'><div class='aptu-tile-l'>{aptu_last_up}</div>"
            ."<div class='aptu-tile-v'><span class='aptu-pill aptu-off'>{aptu_never}</span></div></div>";
    }
    $res=$m["last_res"]
        ?"<span class='aptu-pill aptu-ok'><i class='fas fa-check' aria-hidden='true'></i>{aptu_succeeded}</span>"
        :"<span class='aptu-pill aptu-err'><i class='fas fa-xmark' aria-hidden='true'></i>{aptu_with_errors}</span>";
    return "<div class='aptu-tile'><div class='aptu-tile-l'>{aptu_last_up}</div>"
        ."<div class='aptu-tile-v'><span style='font-weight:600'>".aptu_ago_span($m["last_up"])."</span>$res</div>"
        ."<div style='margin-top:6px'><a href='javascript:blur();' class='aptu-link' OnClick=\"Loadjs('fw.netagents.aptget.reports.php?id=$id');\">{aptu_see_report}</a></div></div>";
}

function aptu_tile_running($m):string{
    if(!$m["running"]){
        return "<div class='aptu-tile'><div class='aptu-tile-l'>{aptu_running_t}</div>"
            ."<div class='aptu-tile-v'><span class='aptu-pill aptu-off'><i class='fas fa-circle' aria-hidden='true' style='font-size:8px'></i>{aptu_idle}</span></div></div>";
    }
    return "<div class='aptu-tile aptu-tile-run'><div class='aptu-tile-l'>{aptu_running_t}</div>"
        ."<div class='aptu-tile-v'><span class='aptu-pill aptu-info'><span class='aptu-spin' style='display:inline-flex'><i class='fas fa-arrows-rotate' aria-hidden='true'></i></span>{aptu_running}</span></div>"
        ."<div class='aptu-tile-s' style='margin-top:6px'>{aptu_paused}</div></div>";
}

function aptu_banner_fetch($m):string{
    $e=$m["fetch_error"];
    // A reverse-connected node always answers "connection refused" to a direct fetch: not an incident.
    if($e=="" || preg_match("#connection refused#i",$e)){return "";}
    $id=$m["id"];
    return "<div class='aptu-ban aptu-ban-warn' role='alert'><div class='aptu-ban-ico' aria-hidden='true'><i class='fas fa-triangle-exclamation'></i></div>"
        ."<div class='aptu-ban-main'><div class='aptu-ban-t'>{aptu_fetch_err_t}</div><div class='aptu-ban-raw'>".htmlspecialchars($e)."</div></div>"
        ."<button type='button' class='aptu-btn aptu-btn-sm' OnClick=\"AptuCheck$id();\"><i class='fas fa-arrows-rotate' aria-hidden='true'></i>{aptu_check_again}</button></div>";
}

function aptu_banner_dist($m):string{
    if(!$m["dist"]){return "";}
    $id=$m["id"];
    $page=CurrentPageName();
    $dis=$m["running"]?" disabled title=\"{aptu_paused_tip}\"":"";
    return "<div class='aptu-ban aptu-ban-dist'><div class='aptu-ban-ico' aria-hidden='true'><i class='fas fa-layer-group'></i></div>"
        ."<div class='aptu-ban-main'><div class='aptu-ban-t'>{aptu_dist_t}</div><div class='aptu-ban-p'>{aptu_dist_text}<span class='aptu-ban-tech'>apt dist-upgrade</span></div></div>"
        ."<button type='button' class='aptu-btn aptu-btn-warn aptu-btn-sm'$dis OnClick=\"Loadjs('$page?dist-upgrade-js=yes&id=$id');\"><i class='fas fa-layer-group' aria-hidden='true'></i>{aptu_full_upgrade_btn}&hellip;</button></div>";
}

function aptu_banner_error($m):string{
    $id=$m["id"];
    return "<div class='aptu-ban aptu-ban-err' role='alert'><div class='aptu-ban-ico' aria-hidden='true'><i class='fas fa-circle-xmark'></i></div>"
        ."<div class='aptu-ban-main'><div class='aptu-ban-t'>{aptu_err_t}</div><div class='aptu-ban-raw'>".htmlspecialchars($m["err"])."</div></div>"
        ."<button type='button' class='aptu-btn aptu-btn-sm' OnClick=\"AptuCheck$id();\"><i class='fas fa-arrows-rotate' aria-hidden='true'></i>{aptu_check_again}</button></div>";
}

function aptu_empty_uptodate($m):string{
    $id=$m["id"];
    $when=$m["last_fetch"]>0?" ".aptu_fmt("{aptu_checked}",aptu_ago_span($m["last_fetch"])):"";
    return "<div class='aptu-empty aptu-empty-ok'><div class='aptu-empty-ico' aria-hidden='true'><i class='fas fa-circle-check'></i></div>"
        ."<div class='aptu-empty-t'>{aptu_uptodate_t}</div><div class='aptu-empty-p'>{aptu_uptodate_text}$when</div>"
        ."<button type='button' class='aptu-btn' OnClick=\"AptuCheck$id();\"><i class='fas fa-arrows-rotate' aria-hidden='true'></i>{aptu_check_again}</button></div>";
}

function aptu_empty_nodata($m):string{
    $id=$m["id"];
    return "<div class='aptu-empty aptu-empty-wait'><div class='aptu-empty-ico' aria-hidden='true'><i class='fas fa-cloud-arrow-down'></i></div>"
        ."<div class='aptu-empty-t'>{aptu_nodata_t}</div><div class='aptu-empty-p'>{aptu_nodata_text}</div>"
        ."<button type='button' class='aptu-btn aptu-btn-primary' OnClick=\"AptuCheck$id();\"><i class='fas fa-arrows-rotate' aria-hidden='true'></i>{aptu_check_now}</button></div>";
}

function aptu_list($m):string{
    $id=$m["id"];
    $page=CurrentPageName();
    $n=count($m["packages"]);
    $countTxt=$n==1?"{aptu_one_update}":aptu_fmt("{aptu_n_updates}","<b>".number_format($n)."</b>");
    $dis=$m["running"]?" disabled title=\"{aptu_paused_tip}\"":" title=\"{aptu_chip_newer}\"";

    $h=array();
    $h[]="<div class='aptu-card'><div class='aptu-bar'>";
    $h[]="<div class='aptu-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
        ."<input type='search' placeholder=\"{aptu_search}\" aria-label=\"{aptu_search}\" oninput=\"AptuFilter$id(this.value)\"></div>";
    $h[]="<span class='aptu-count'>$countTxt</span>";
    $h[]="<button type='button' class='aptu-btn aptu-btn-primary'$dis OnClick=\"Loadjs('$page?upgrade-all-js=yes&id=$id');\"><i class='fas fa-circle-arrow-up' aria-hidden='true'></i>"
        .aptu_fmt("{aptu_upgrade_all_n}",number_format($n))."</button>";
    $h[]="</div>";
    $h[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $h[]="<thead><tr><th>{aptu_col_program}</th><th>{aptu_col_installed}</th><th aria-hidden='true'></th><th>{aptu_col_available}</th>"
        ."<th style='text-align:right'>{aptu_col_size}</th><th style='width:1%'></th></tr></thead>";
    $h[]="<tbody id='aptu-rows-$id'>";
    foreach($m["packages"] as $p){
        $h[]=aptu_row($m,$p);
    }
    $h[]="</tbody></table></div>";
    $h[]="<div class='awc-foot'><span>$countTxt</span><span class='awc-pg' id='aptu-pager-$id'></span></div>";
    $h[]="</div></div></div>";
    return @implode("\n",$h);
}

function aptu_row($m,$p):string{
    $id=$m["id"];
    $page=CurrentPageName();
    $name=htmlspecialchars($p["name"]);
    $arch=$p["arch"]!=""?"<span class='aptu-arch'>".htmlspecialchars($p["arch"])."</span>":"";
    $desc=trim($p["desc"]);
    $descH=$desc!=""?"<div class='aptu-desc' title=\"".htmlspecialchars($desc)."\">".htmlspecialchars($desc)."</div>":"";
    $ver=$p["version"]!=""?htmlspecialchars($p["version"]):"&mdash;";
    $new=($p["new"]!="" && $p["new"]!=$p["version"])?"<span class='aptu-new'>".htmlspecialchars($p["new"])."</span>":"&mdash;";
    $label=aptu_fmt("{aptu_upgrade_named}",$name);
    $nameEnc=urlencode($p["name"]);
    $dis=$m["running"]?" disabled title=\"{aptu_paused_tip}\"":"";
    $btn="<button type='button' class='aptu-btn aptu-btn-sm'$dis aria-label=\"$label\" OnClick=\"Loadjs('$page?upgrade-single-js=yes&id=$id&name=$nameEnc');\">"
        ."<i class='fas fa-circle-arrow-up' aria-hidden='true'></i>{aptu_upgrade}</button>";
    return "<tr><td><span class='aptu-pkg-n'>$name</span>$arch$descH</td>"
        ."<td><span class='aptu-ver'>$ver</span></td>"
        ."<td class='aptu-arrow' aria-hidden='true'><i class='fas fa-arrow-right'></i></td>"
        ."<td>$new</td><td class='aptu-size'>".aptu_size($p["size"])."</td><td style='white-space:nowrap'>$btn</td></tr>";
}

// Page JS: search filter, "Check again", and a light refresh while APT runs on the node.
function aptu_js($m):string{
    $id=$m["id"];
    $page=CurrentPageName();
    $run=$m["running"]?"true":"false";
    return "<script>NoSpinner();"
        ."function AptuReload$id(){if(!document.getElementById('aptu-main-$id')){return;}LoadAjaxSilent('aptu-main-$id','$page?main=yes&id=$id');}"
        ."function AptuFilter$id(q){q=String(q||'').toLowerCase().trim();var tb=document.getElementById('aptu-rows-$id');if(!tb){return;}"
        ."for(var i=0;i<tb.rows.length;i++){var r=tb.rows[i];var hit=q===''||r.textContent.toLowerCase().indexOf(q)>=0;r.setAttribute('data-filtered',hit?'0':'1');r.style.display=hit?'':'none';}"
        ."var pg=document.getElementById('aptu-pager-$id');if(pg){pg.style.display=q===''?'':'none';}if(q===''&&typeof tb.__awcRedraw==='function'){tb.__awcRedraw();}}"
        ."function AptuCheck$id(){Loadjs('$page?refresh-js=yes&id=$id');}"
        ."if(window.AptuTimer$id){clearTimeout(window.AptuTimer$id);window.AptuTimer$id=null;}"
        ."if($run){window.AptuTimer$id=setTimeout(function(){window.AptuTimer$id=null;if(document.querySelector('.sweet-alert.visible')){return;}AptuReload$id();},20000);}"
        ."</script>";
}

// ==================== Actions ====================

// Shared JS for an action: SweetAlert confirmation stating the consequences, POST, then
// toast + reload inside the window, or an alert when the action was started from another page.
function aptu_action_js($id,$title,$textHtml,$btnLabel,$btnColor,$post,$host):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $started=aptu_fmt("{aptu_started}",htmlspecialchars($host));
    $cancel=$tpl->_ENGINE_parse_body("{aptu_cancel}");
    $enc=function($s){return json_encode(html_entity_decode($s,ENT_QUOTES,"UTF-8"),JSON_INVALID_UTF8_SUBSTITUTE);};
    $dismiss=htmlspecialchars(html_entity_decode($tpl->_ENGINE_parse_body("{aptu_dismiss}"),ENT_QUOTES,"UTF-8"),ENT_QUOTES,"UTF-8");
    $postJs=json_encode($post);
    $js=array();
    $js[]="(function(){";
    $js[]="var started=".json_encode($started,JSON_INVALID_UTF8_SUBSTITUTE).";";
    $js[]="function toast(kind,html){var t=document.getElementById('aptu-toast-$id');if(!t){return false;}"
        ."var ico=kind==='ok'?'fa-paper-plane':(kind==='err'?'fa-circle-xmark':'fa-spinner');"
        ."t.innerHTML='<div class=\"aptu-toast aptu-toast-'+kind+'\"><i class=\"fas '+ico+'\" aria-hidden=\"true\"></i><span>'+html+'</span>"
        ."<button type=\"button\" aria-label=\"$dismiss\" onclick=\"this.parentNode.parentNode.innerHTML=\\'\\';\">&times;</button></div>';return true;}";
    $js[]="function esc(s){return String(s).replace(/[&<>\"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;'}[c];});}";
    $js[]="swal({title:".$enc($title).",text:".json_encode($textHtml,JSON_INVALID_UTF8_SUBSTITUTE).",html:true,type:'warning',showCancelButton:true,"
        ."cancelButtonText:".$enc($cancel).",confirmButtonColor:'$btnColor',confirmButtonText:".$enc($btnLabel).",closeOnConfirm:true},function(ok){";
    $js[]="  if(!ok){return;}";
    $js[]="  \$.post('$page',$postJs,function(data){data=\$.trim(String(data||''));";
    $js[]="    if(data!==''){if(!toast('err',esc(data))){setTimeout(function(){swal({title:'',text:data,type:'error'});},150);}return;}";
    $js[]="    if(!toast('ok',started)){setTimeout(function(){swal({title:'',text:\$('<div>').html(started).text(),type:'success'});},150);return;}";
    $js[]="    if(typeof AptuReload$id==='function'){setTimeout(AptuReload$id,4000);setTimeout(AptuReload$id,190000);}";
    $js[]="  });";
    $js[]="});";
    $js[]="})();";
    return @implode("\n",$js);
}

function aptu_host($id):string{
    $net=new ArticaNetAgents($id);
    return (string)$net->GetAgentHostname();
}

function upgrade_single_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $name=trim((string)($_GET["name"] ?? ""));
    if(aptu_deny()){return;}
    $m=aptu_model($id);
    $vline="";
    foreach($m["packages"] as $p){
        if($p["name"]!==$name){continue;}
        if($p["version"]!="" && $p["new"]!=""){
            $vline="<span style='font-family:monospace'>".htmlspecialchars($p["version"])." &rarr; <b>".htmlspecialchars($p["new"])."</b></span><br>";
        }
        break;
    }
    $title=aptu_fmt("{aptu_confirm_one_t}",$name);
    $text=$vline.$tpl->_ENGINE_parse_body("{aptu_confirm_one_text}");
    echo aptu_action_js($id,$title,$text,$tpl->_ENGINE_parse_body("{aptu_upgrade}"),"#059669",
        array("upgrade-single"=>"yes","id"=>$id,"name"=>$name),$m["host"]);
}

function upgrade_single(){
    $tpl=new template_admin();
    if(aptu_deny()){return;}
    $id=intval($_POST["id"]);
    $name=trim((string)($_POST["name"] ?? ""));
    $nameEnc=rawurlencode($name);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/packages/upgrade/$id/$nameEnc",array()));
    if(!is_object($json)){echo $tpl->_ENGINE_parse_body("{error}");return;}
    if(isset($json->Status) && !$json->Status){
        echo $name.": ".(string)($json->Error ?? "error");
        return;
    }
    admin_tracks("Meta: upgraded package $name on agent $id");
}

function upgrade_all_js():bool{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    if(aptu_deny()){return false;}
    $m=aptu_model($id);
    $n=count($m["packages"]);
    if($n==0){$n=$m["upg"];}
    $host=$m["host"];
    $title=aptu_fmt("{aptu_confirm_all_t}",number_format($n));
    $text=aptu_fmt("{aptu_confirm_all_text}","<b>".htmlspecialchars($host)."</b>");
    echo aptu_action_js($id,$title,$text,aptu_fmt("{aptu_install_n}",number_format($n)),"#059669",
        array("upgrade-all"=>"yes","id"=>$id),$host);
    return true;
}

function upgrade_all():bool{
    $tpl=new template_admin();
    if(aptu_deny()){return false;}
    $id=intval($_POST["id"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/packages/upgrade/all/$id",array()));
    if(!is_object($json)){echo $tpl->_ENGINE_parse_body("{error}");return false;}
    if(isset($json->Status) && !$json->Status){
        echo (string)($json->Error ?? "error");
        return false;
    }
    return admin_tracks("Meta: upgrade all packages on agent $id");
}

function dist_upgrade_js():void{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    if(aptu_deny()){return;}
    $host=aptu_host($id);
    $title=aptu_fmt("{aptu_confirm_dist_t}",$host);
    $text=$tpl->_ENGINE_parse_body("{aptu_confirm_dist_text}");
    echo aptu_action_js($id,$title,$text,$tpl->_ENGINE_parse_body("{aptu_run_full}"),"#b45309",
        array("dist-upgrade"=>"yes","id"=>$id),$host);
}

function dist_upgrade():void{
    $tpl=new template_admin();
    if(aptu_deny()){return;}
    $id=intval($_POST["id"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/packages/dist-upgrade/$id",[]));
    if(!is_object($json)){echo $tpl->_ENGINE_parse_body("{error}");return;}
    if(isset($json->Status)&&!$json->Status){
        echo (string)($json->Error ?? "error");
        return;
    }
    admin_tracks("Meta: dist-upgrade triggered on agent $id");
}

// ==================== Check again (fetch from the node) ====================

function refresh_js():bool{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    if(aptu_deny()){return false;}
    $asking=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{aptu_asking}"),ENT_QUOTES,"UTF-8"));
    $js=array();
    $js[]="(function(){var t=document.getElementById('aptu-toast-$id');";
    $js[]="if(t){t.innerHTML='<div class=\"aptu-toast aptu-toast-info\"><i class=\"fas fa-arrows-rotate\" aria-hidden=\"true\"></i><span></span></div>';t.querySelector('span').textContent=$asking+'...';}";
    $js[]="\$.post('$page',{'refresh-fetch':'yes','id':'$id'},function(data){data=\$.trim(String(data||''));";
    $js[]=" if(data!==''){if(t){t.innerHTML='<div class=\"aptu-toast aptu-toast-err\"><i class=\"fas fa-circle-xmark\" aria-hidden=\"true\"></i><span></span></div>';t.querySelector('span').textContent=data;}return;}";
    $js[]=" var r=function(){if(typeof AptuReload$id==='function'){AptuReload$id();}};";
    $js[]=" setTimeout(r,4000);setTimeout(function(){r();if(t){t.innerHTML='';}},12000);";
    $js[]="});})();";
    echo @implode("\n",$js);
    return true;
}

function refresh_fetch():bool{
    $tpl=new template_admin();
    if(aptu_deny()){return false;}
    $id=intval($_POST["id"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/netagents/packages/fetch/$id",array()));
    if(is_object($json) && isset($json->Status) && !$json->Status){
        echo (string)($json->Error ?? "error");
        return false;
    }
    return true;
}
