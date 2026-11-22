<?php
/**
 * TreeSize - explorateur de stockage (maquette stitch_disk_usage_visualizer_ui).
 * Spec : docs/superpowers/specs/2026-10-04-treesize-explorer-design.md
 *
 * Cartes et bandeau = systeme de fichiers du dossier parcouru (/treesize/fs) ;
 * explorateur = enfants du scan (/treesize/children), enrichis par le daemon
 * des droits/proprietaire du moment. Aucune valeur d'exemple de la maquette.
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
// Inclusions INCONDITIONNELLES au premier niveau (kit awc + fonctions pures).
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.treesize.ui.inc");
if(is_file(dirname(__FILE__)."/ressources/class.brand.inc")){
    include_once(dirname(__FILE__)."/ressources/class.brand.inc");
}

$users=new usersMenus();
if(!$users->AsSystemAdministrator){exit();}

if(isset($_GET["verbose"])){
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
}

if(isset($_GET["main"])){main();exit;}
if(isset($_GET["scan-status"])){scan_status();exit;}
if(isset($_GET["scan-js"])){scan_js();exit;}
if(isset($_GET["scan-popup"])){scan_popup();exit;}
if(isset($_POST["path"])){start_scan();exit;}
if(isset($_GET["stop-scan-js"])){stop_scan_js();exit;}
if(isset($_GET["quick-js"])){quick_js();exit;}
if(isset($_GET["quick-popup"])){quick_popup();exit;}
if(isset($_GET["chart-js"])){chart_js();exit;}
if(isset($_GET["chart-popup"])){chart_popup();exit;}
if(isset($_GET["history-js"])){history_js();exit;}
if(isset($_GET["history-popup"])){history_popup();exit;}

page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{disk_usage_analyzer}",
        "fas fa-chart-pie","{disk_usage}: {disk_usage_analyzer_desc}",
        "$page?main=yes","treesize","progress-treesize",false,"table-loader");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
}

// ---------------------------------------------------------------------------
// Acces au daemon. Chaque fonction rend un tableau (json_decode assoc), ou
// null quand la route est absente ou en erreur.

function tsz_get($endpoint){
    $j=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($endpoint),true);
    return is_array($j)?$j:null;
}

function tsz_param_path():string{
    return tsz_clean_path($_GET["path"] ?? "/");
}

function tsz_q($path):string{
    return urlencode($path);
}

// Octets -> texte (FormatBytes prend des KILO-octets).
function tsz_bytes($bytes):string{
    return FormatBytes(floatval($bytes)/1024,true);
}

function tsz_t($token):string{
    $tpl=new template_admin();
    return $tpl->_ENGINE_parse_body($token);
}

// ---------------------------------------------------------------------------
// Kit CSS porte a la page. Une feuille par requete, concatenee APRES l'analyse
// du moteur (la regex #{(.+?)}# prendrait chaque corps de regle pour un jeton).

function tsz_accents():array{
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488",
        "accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return $ac;
}

function tsz_style():string{
    if(isset($GLOBALS["TSZ_CSS_DONE"])){return "";}
    $GLOBALS["TSZ_CSS_DONE"]=true;
    $ac=tsz_accents();
    $ad=$ac["accent_dark"];
    $mono="ui-monospace,SFMono-Regular,Menlo,Consolas,monospace";
    // Seules les regles propres a la page : le reste vient du kit ak- (angular.css.php).
    return "<style>
.tsz-wrap{display:flex;flex-direction:column;gap:20px;margin-top:6px}
.tsz-card-warn{border-color:#fde68a;background:#fffbeb}
.tsz-card-crit{border-color:#fecdd3;background:#fff1f2}
.tsz-card-crit .ak-fig-v{color:#9f1239}
.tsz-card small{font-size:16px;font-weight:600;margin-left:4px}
.tsz-card .ak-tag{white-space:normal}
.tsz-banner{padding:20px}
.tsz-banner-warn{border-color:#fde68a}
.tsz-banner-crit{border-color:#fecdd3}
.tsz-banner-head{display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;align-items:flex-start}
.tsz-banner-title{font-size:14px;font-weight:600;color:#0f172a;margin:0}
.tsz-banner-sub{font-size:13px;color:#475569;margin:2px 0 0 0}
.tsz-banner-fig{font-family:$mono;font-size:13px;color:#475569;white-space:nowrap}
.tsz-banner-fig b{color:#0f172a}
.tsz-banner-crit .tsz-banner-fig b{color:#e11d48}
.tsz-track{height:12px;margin:14px 0}
.tsz-legend{display:flex;flex-wrap:wrap;gap:20px;font-size:13px;color:#475569;padding-top:12px;border-top:1px solid #f1f5f9}
.tsz-legend .ak-dot{margin-right:6px;vertical-align:middle}
.tsz-ex-head{flex-wrap:wrap;padding:18px 20px}
.tsz-ex-title{display:flex;gap:12px;align-items:center;min-width:0}
.tsz-ex-title h3{font-size:16px;font-weight:600;color:#0f172a;margin:0}
.tsz-ex-title p{font-size:13px;font-weight:400;color:#64748b;margin:2px 0 0 0}
.tsz-mono{font-family:$mono;font-size:13px;background:#f1f5f9;border-radius:4px;padding:1px 6px;color:#334155;word-break:break-all}
.tsz-crumbs{display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;align-items:center;padding:10px 20px;background:#f8fafc;border-bottom:1px solid #f1f5f9;font-family:$mono;font-size:13px}
.tsz-crumbs a{color:#475569;cursor:pointer;text-decoration:none}
.tsz-crumbs a:hover{color:$ad}
.tsz-crumbs .tsz-sep{color:#cbd5e1;margin:0 6px}
.tsz-crumbs .tsz-here{font-weight:600;color:#0f172a}
.tsz-counts{font-family:inherit;font-size:13px;color:#94a3b8}
.tsz-row{cursor:pointer}
.tsz-row-absent td{opacity:.55}
.tsz-name{display:flex;gap:12px;align-items:center;min-width:0}
.tsz-dirname{font-size:13px;font-weight:600;color:#0f172a}
.tsz-dirname .ak-tag{margin-left:6px;vertical-align:middle}
.tsz-dirpath{font-size:13px;color:#94a3b8;word-break:break-all}
.tsz-size{font-family:$mono;font-size:13px;font-weight:700;color:#0f172a;white-space:nowrap}
.tsz-usage{display:flex;align-items:center;gap:10px;min-width:150px}
.tsz-usage .ak-bar{flex:1;height:8px}
.tsz-usage-pct{font-family:$mono;font-size:13px;font-weight:700;color:#475569;width:44px}
.tsz-owner{font-family:$mono;font-size:13px;color:#475569;white-space:nowrap}
.tsz-mtime{font-size:13px;color:#475569;white-space:nowrap}
.tsz-dash{color:#cbd5e1}
.tsz-menu .dropdown-menu{min-width:170px}
</style>";
}

// ---------------------------------------------------------------------------
// Cartes KPI : une fonction par carte.

function tsz_card($label,$value,$sub,$level,$icon,$pill):string{
    $card=$level=="ok"?"":" tsz-card-$level";
    $h ="<div class='ak-fig tsz-card$card'><div class='ak-fig-txt'>";
    $h.="<span class='ak-fig-l'>$label</span>";
    $h.="<span class='ak-fig-v'>$value$sub</span>";
    $h.="<div>$pill</div></div>";
    $h.="<span class='ak-ico ak-ico-sq ".tsz_tone($level)."'><i class='$icon'></i></span>";
    return $h."</div>";
}

// Niveau de la page (ok/warn/crit) -> ton du kit ak-.
function tsz_tone($level):string{
    if($level=="crit"){return "ak-err";}
    if($level=="warn"){return "ak-warn";}
    if($level=="ok"){return "ak-ok";}
    return "";
}

function tsz_pill($text,$level=""):string{
    return "<span class='ak-tag ".tsz_tone($level)."'>$text</span>";
}

function tsz_level_token($level):string{
    if($level=="crit"){return "{tsz_level_crit}";}
    if($level=="warn"){return "{tsz_level_warn}";}
    return "{tsz_level_ok}";
}

function tsz_card_total($fs):string{
    $sub=htmlspecialchars(trim(($fs["device"] ?? "")." · ".($fs["fstype"] ?? "")," ·"));
    $pill=$sub==""?"":tsz_pill("<span style='font-family:monospace'>$sub</span>");
    return tsz_card("{tsz_total_space}",tsz_bytes($fs["total"]),"","ok","fas fa-database",$pill);
}

function tsz_card_used($fs):string{
    $pct=(float)($fs["used_pct"] ?? 0);
    $level=tsz_level($pct,TSZ_FS_WARN,TSZ_FS_CRIT);
    $icon=$level=="ok"?"fas fa-hard-drive":"fas fa-triangle-exclamation";
    return tsz_card("{tsz_used_space}",tsz_bytes($fs["used"]),"<small>($pct%)</small>",$level,$icon,
        tsz_pill(tsz_level_token($level),$level));
}

function tsz_card_free($fs):string{
    $level=tsz_level((float)($fs["used_pct"] ?? 0),TSZ_FS_WARN,TSZ_FS_CRIT);
    $pill=tsz_pill("{tsz_reserved}: ".tsz_bytes($fs["reserved"] ?? 0));
    return tsz_card("{tsz_free_space}",tsz_bytes($fs["avail"]),"",$level=="ok"?"ok":"warn","fas fa-chart-pie",$pill);
}

function tsz_card_inodes($fs):string{
    $pct=tsz_inodes_pct($fs);
    if($pct===null){
        return tsz_card("{tsz_inodes}","{tsz_not_applicable}","","ok","fas fa-sitemap",
            tsz_pill("{tsz_no_inodes_explain}"));
    }
    $level=tsz_level($pct,TSZ_FS_WARN,TSZ_FS_CRIT);
    $tpl=new template_admin();
    $used=floatval($fs["inodes_total"])-floatval($fs["inodes_free"]);
    $txt=$tpl->FormatNumber($used)." / ".$tpl->FormatNumber($fs["inodes_total"]);
    return tsz_card("{tsz_inodes}","$pct%","",$level,"fas fa-sitemap",tsz_pill($txt,$level));
}

function tsz_cards($fs):string{
    return "<div class='ak-grid-4'>".tsz_card_total($fs).tsz_card_used($fs).tsz_card_free($fs).tsz_card_inodes($fs)."</div>";
}

// ---------------------------------------------------------------------------
// Bandeau du montage.

function tsz_banner($fs):string{
    $pct=(float)($fs["used_pct"] ?? 0);
    $level=tsz_level($pct,TSZ_FS_WARN,TSZ_FS_CRIT);
    $title=str_replace(array("%m","%d"),
        array(htmlspecialchars($fs["mountpoint"] ?? "/"),htmlspecialchars($fs["device"] ?? "?")),
        tsz_t("{tsz_banner_title}"));
    $subTok=$level=="crit"?"{tsz_banner_crit}":($level=="warn"?"{tsz_banner_warn}":"{tsz_banner_ok}");
    $threshold=$level=="crit"?TSZ_FS_CRIT:TSZ_FS_WARN;
    $sub=str_replace("%s","$threshold%",tsz_t($subTok));
    $fig=str_replace(array("%a","%t"),array(tsz_bytes($fs["avail"]),tsz_bytes($fs["total"])),tsz_t("{tsz_left_of}"));
    $w=max(0,min(100,$pct));
    $cls=$level=="ok"?"":" tsz-banner-$level";
    $h ="<div class='ak-card ak-card-pad tsz-banner$cls'>";
    $h.="<div class='tsz-banner-head'><div><p class='tsz-banner-title'>$title</p><p class='tsz-banner-sub'>$sub</p></div>";
    $h.="<div class='tsz-banner-fig'><b>$pct%</b> | $fig</div></div>";
    $h.="<div class='ak-bar ".tsz_tone($level)." tsz-track'><span style='width:$w%'></span></div>";
    $h.=tsz_legend($fs);
    return $h."</div>";
}

function tsz_legend($fs):string{
    $h ="<div class='tsz-legend'>";
    $h.="<span><span class='ak-dot ak-err'></span>{tsz_used}: ".tsz_bytes($fs["used"])."</span>";
    $h.="<span><span class='ak-dot ak-ok'></span>{tsz_free}: ".tsz_bytes($fs["avail"])."</span>";
    $h.="<span><span class='ak-dot ak-off'></span>{tsz_reserved}: ".tsz_bytes($fs["reserved"] ?? 0)."</span>";
    return $h."</div>";
}

// ---------------------------------------------------------------------------
// Explorateur.

function tsz_explorer_head($path):string{
    $h ="<div class='ak-card-h tsz-ex-head'><div class='tsz-ex-title'>";
    $h.="<span class='ak-ico ak-ico-sq'><i class='fas fa-folder-open'></i></span>";
    $h.="<div><h3>{tsz_explorer}</h3><p>{tsz_explorer_explain} <span class='tsz-mono'>".htmlspecialchars($path)."</span></p></div>";
    $h.="</div>".awc_search_bar("tsz-q","{tsz_filter}")."</div>";
    return $h;
}

function tsz_breadcrumb($path,$dir,$nbChildren):string{
    $parts=tsz_breadcrumb_parts($path);
    $last=count($parts)-1;
    $links=array();
    foreach($parts as $i=>$p){
        $label=$i==0?"<i class='fas fa-house'></i>":htmlspecialchars($p[0]);
        if($i==$last){ $links[]="<span class='tsz-here'>$label</span>"; continue; }
        $links[]="<a onclick=\"TszGo(".tsz_js_string($p[1]).")\">$label</a>";
    }
    list($dirs,$files)=tsz_dir_counts($dir,$nbChildren);
    $tpl=new template_admin();
    $counts=str_replace(array("%d","%f"),array($tpl->FormatNumber($dirs),$tpl->FormatNumber($files)),tsz_t("{tsz_counts}"));
    return "<div class='tsz-crumbs'><div>".implode("<span class='tsz-sep'>/</span>",$links)."</div><span class='tsz-counts'>$counts</span></div>";
}

function tsz_cell_name($row,$badges,$level):string{
    $path=(string)($row["path"] ?? "");
    $name=basename($path);
    if($name===""){$name=$path;}
    $icon="fas fa-folder";
    if(in_array("mount",$badges)){$icon="fas fa-hard-drive";}
    if(in_array("absent",$badges)){$icon="fas fa-folder-xmark";}
    $b="";
    $badgeTone=array("critical"=>"ak-err","mount"=>"ak-ok","absent"=>"ak-off");
    foreach($badges as $k){ $b.="<span class='ak-tag ".($badgeTone[$k] ?? "")."'>{tsz_badge_$k}</span>"; }
    return "<div class='tsz-name'><span class='ak-ico ak-ico-sm ak-ico-sq ".tsz_tone($level)."'><i class='$icon'></i></span><div style='min-width:0'>"
        ."<div class='tsz-dirname'>".htmlspecialchars($name)."$b</div>"
        ."<div class='tsz-dirpath'>".htmlspecialchars($path)."</div></div></div>";
}

function tsz_cell_type($row):string{
    $t=trim((string)($row["fstype"] ?? ""));
    if($t===""){return "<span class='tsz-dash'>&#8212;</span>";}
    return "<span class='ak-chip'>".htmlspecialchars($t)."</span>";
}

function tsz_cell_size($row):string{
    return "<span class='tsz-size'>".tsz_bytes($row["size"] ?? 0)."</span>";
}

function tsz_cell_usage($share,$level):string{
    $w=max(0,min(100,$share));
    return "<div class='tsz-usage'><div class='ak-bar ".tsz_tone($level)."'><span style='width:$w%'></span></div>"
        ."<span class='tsz-usage-pct'>$share%</span></div>";
}

function tsz_cell_owner($row):string{
    $t=tsz_mode_owner_text($row);
    if($t===""){return "<span class='tsz-dash'>&#8212;</span>";}
    return "<span class='tsz-owner'>".htmlspecialchars($t)."</span>";
}

function tsz_cell_mtime($row):string{
    // Texte deja traduit : pas de htmlspecialchars (cf. tsz_relative_text).
    $txt=tsz_relative_text($row["mod_time"] ?? 0,time(),"tsz_t");
    if($txt===""){return "<span class='tsz-dash'>&#8212;</span>";}
    return "<span class='tsz-mtime'>$txt</span>";
}

// Menu ⋮ : le clic DOIT remonter jusqu'a document, ou Bootstrap 3 ecoute ses
// dropdowns (un stopPropagation ici empechait le menu de jamais s'ouvrir). C'est
// la ligne qui ignore les clics venus du menu (tsz_row).
function tsz_cell_menu($row):string{
    $page=CurrentPageName();
    $js=tsz_js_string($row["path"] ?? "/");
    $enc="encodeURIComponent($js)";
    $items=array(
        array("fas fa-folder-open","{tsz_open}","TszGo($js)"),
        array("fas fa-gauge-high","{quick_size}","Loadjs('$page?quick-js=yes&path='+$enc)"),
        array("fas fa-chart-pie","{tsz_distribution}","Loadjs('$page?chart-js=yes&path='+$enc)"),
        array("fas fa-chart-line","{history}","Loadjs('$page?history-js=yes&path='+$enc)"),
    );
    $li="";
    foreach($items as $it){ $li.="<li><a href='javascript:void(0)' onclick=\"{$it[2]}\"><i class='{$it[0]}'></i>&nbsp; {$it[1]}</a></li>"; }
    return "<div class='btn-group tsz-menu'>"
        ."<button type='button' class='btn btn-default btn-xs dropdown-toggle' data-toggle='dropdown'><i class='fas fa-ellipsis-vertical'></i></button>"
        ."<ul class='dropdown-menu dropdown-menu-right'>$li</ul></div>";
}

function tsz_row($row,$parentSize):string{
    $share=tsz_share($row["size"] ?? 0,$parentSize);
    $level=tsz_level($share,TSZ_ROW_WARN,TSZ_ROW_CRIT);
    $badges=tsz_row_badges($row,$parentSize);
    $cls=in_array("absent",$badges)?" tsz-row-absent":"";
    $h ="<tr class='tsz-row$cls' onclick=\"if($(event.target).closest('.tsz-menu').length){return;}TszGo(".tsz_js_string($row["path"] ?? "/").")\">";
    $h.="<td>".tsz_cell_name($row,$badges,$level)."</td>";
    $h.="<td>".tsz_cell_type($row)."</td>";
    $h.="<td>".tsz_cell_size($row)."</td>";
    $h.="<td>".tsz_cell_usage($share,$level)."</td>";
    $h.="<td>".tsz_cell_owner($row)."</td>";
    $h.="<td>".tsz_cell_mtime($row)."</td>";
    $h.="<td class='awc-right'>".tsz_cell_menu($row)."</td>";
    return $h."</tr>";
}

// Pas de scan pour ce chemin : taille directe bornee (comportement d'origine).
function tsz_no_scan($path):string{
    $tpl=new template_admin();
    $q=tsz_get("/treesize/quick?path=".tsz_q($path));
    if(!empty($q["success"])){
        $d=$q["data"];
        $warn=!empty($d["partial"])?"{no_cached_data}||{showing_quick_size} ({partial}: 5s).":"{no_cached_data}||{showing_quick_size}.";
        $h=$tpl->div_warning($warn);
        $h.="<table class='awc-tbl'><tbody>";
        $h.="<tr><th>{path}</th><td>".htmlspecialchars($d["path"] ?? $path)."</td></tr>";
        $h.="<tr><th>{total_size}</th><td>".htmlspecialchars($d["size_human"] ?? "")."</td></tr>";
        $h.="<tr><th>{files}</th><td>".$tpl->FormatNumber($d["file_count"] ?? 0)."</td></tr>";
        $h.="<tr><th>{directories}</th><td>".$tpl->FormatNumber($d["dir_count"] ?? 0)."</td></tr>";
        return $h."</tbody></table>";
    }
    return $tpl->div_warning("{no_data_available}||{please_run_scan}.");
}

function tsz_table($rows,$parentSize):string{
    $trs=array();
    foreach($rows as $row){
        if(!is_array($row)){continue;}
        $trs[]=tsz_row($row,$parentSize);
    }
    $h ="<div class='awc-scroll'><table class='awc-tbl'><thead><tr>";
    $h.="<th>{tsz_col_name}</th><th>{tsz_col_type}</th><th>{tsz_col_size}</th><th>{tsz_col_usage}</th>";
    $h.="<th>{tsz_col_owner}</th><th>{tsz_col_mtime}</th><th class='awc-right'>{actions}</th>";
    $h.="</tr></thead><tbody id='tsz-rows'>".implode("\n",$trs)."</tbody></table></div>";
    $h.="<div class='awc-foot'><span>{showing} ".count($trs)." {directories}</span><span class='awc-pg' id='tsz-pager'></span></div>";
    return $h;
}

// ---------------------------------------------------------------------------
// En-tete (TINY) et scan.

function tsz_tiny($path,$scanning):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $enc=tsz_q($path);
    $btns=array();
    if($scanning){
        $btns[]=array("Loadjs('$page?stop-scan-js=yes&path=$enc');",ico_stop,"{stop}");
    }else{
        $btns[]=array("Loadjs('$page?scan-js=yes&path=$enc');",ico_run,"{start_scan}");
    }
    $btns[]=array("Loadjs('$page?chart-js=yes&path=$enc');","fas fa-chart-pie","{tsz_distribution}");
    $btns[]=array("Loadjs('$page?history-js=yes&path=$enc');","fas fa-chart-line","{history}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{disk_usage_analyzer}";
    $TINY_ARRAY["ICO"]="fas fa-chart-pie";
    $TINY_ARRAY["EXPL"]="{disk_usage}: {disk_usage_analyzer_desc}";
    $TINY_ARRAY["URL"]="treesize";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($btns);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

// Bandeau de progression, rafraichi par ArticaRefresh tant que #tsz-scan existe.
function scan_status(){
    header("content-type: application/x-javascript");
    $h="";
    $st=tsz_get("/treesize/status");
    if(!empty($st["data"]["scanning"])){
        $p=tsz_get("/treesize/scan/progress");
        $pr=$p["data"]["progress"] ?? array();
        $tpl=new template_admin();
        $txt=str_replace(array("%p","%d","%f"),array(
            htmlspecialchars((string)($pr["current_path"] ?? "")),
            $tpl->FormatNumber($pr["dirs_scanned"] ?? 0),
            $tpl->FormatNumber($pr["files_scanned"] ?? 0)),tsz_t("{tsz_scan_running}"));
        $h="<div class='ak-banner ak-ok'><i class='fas fa-spinner fa-spin'></i>&nbsp; $txt</div>";
    }
    echo "if(document.getElementById('tsz-scan')){document.getElementById('tsz-scan').innerHTML=base64_decode('".base64_encode($h)."');}";
}

function scan_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $path=tsz_param_path();
    $tpl->js_dialog6("{start_scan}","$page?scan-popup=yes&path=".tsz_q($path),650);
}

function scan_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $path=tsz_param_path();
    $after="dialogInstance6.close();LoadAjaxSilent('table-loader','$page?main=yes&path=".tsz_q($path)."');";
    $html=array();
    $html[]=$tpl->field_text("path","{path}",$path);
    $html[]=$tpl->field_hidden("exclude","");
    $html[]=$tpl->field_hidden("cross_fs","0");
    echo $tpl->form_outside("",$html,"","{run}",$after);
    return true;
}

// form_outside : rien en cas de succes, le message d'erreur sinon.
function start_scan(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $path=tsz_clean_path($_POST["path"] ?? "/");
    $post=array("path"=>$path,"exclude"=>(string)($_POST["exclude"] ?? ""),"cross_fs"=>intval($_POST["cross_fs"] ?? 0)==1?"true":"false");
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/treesize/scan",$post),true);
    $err=tsz_scan_error($json);
    if($err!==""){
        // Echappe AVANT l'analyse : un « {error} » de repli est ainsi traduit.
        echo $tpl->_ENGINE_parse_body("{error}: ".htmlspecialchars(strip_tags(str_replace("<br>"," ",$err))));
        return;
    }
    admin_tracks("Scanning the TreeSize of $path");
}

function stop_scan_js(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/treesize/scan/stop",array());
    admin_tracks("Stop the TreeSize scan");
    echo "LoadAjaxSilent('table-loader','$page?main=yes&path=".tsz_q(tsz_param_path())."');";
}

// ---------------------------------------------------------------------------
// Fragment principal.

function main(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $path=tsz_param_path();

    $fs=tsz_get("/treesize/fs?path=".tsz_q($path));
    $status=tsz_get("/treesize/status");
    $scanning=!empty($status["data"]["scanning"]);
    $children=tsz_get("/treesize/children?path=".tsz_q($path)."&limit=100");
    $dir=tsz_get("/treesize/dir?path=".tsz_q($path));

    $html=array();
    $html[]="<div class='tsz-wrap ak'>";
    $html[]="<div id='tsz-scan'></div>";
    if(tsz_fs_valid($fs)){
        $html[]=tsz_cards($fs["data"]);
        $html[]=tsz_banner($fs["data"]);
    }
    $html[]="<div class='ak-card'>";
    $html[]=tsz_explorer_head($path);

    $rows=array();
    if(!empty($children["success"]) && is_array($children["data"] ?? null)){
        $rows=$children["data"];
    }
    $html[]=tsz_breadcrumb($path,$dir,count($rows));

    $hasTable=false;
    if(empty($children["success"])){
        $html[]="<div style='padding:16px 20px'>".tsz_no_scan($path)."</div>";
    }elseif(count($rows)==0){
        $html[]="<div style='padding:16px 20px'>".$tpl->div_info("{no_subdirectories}")."</div>";
    }else{
        $parentSize=floatval($dir["data"]["size"] ?? 0);
        if($parentSize<=0){
            foreach($rows as $r){ $parentSize+=floatval($r["size"] ?? 0); }
        }
        $html[]=tsz_table($rows,$parentSize);
        $hasTable=true;
    }
    $html[]="</div></div>";

    $refresh=$tpl->RefreshInterval_Loadjs("tsz-scan",$page,"scan-status=yes",3);
    $js ="<script>NoSpinner();\n";
    $js.="function TszGo(p){LoadAjaxSilent('table-loader','$page?main=yes&path='+encodeURIComponent(p));}\n";
    // Menu ⋮ en position fixe : sinon .awc-scroll (overflow) le coupe sur les dernieres lignes.
    $js.="$(document).off('shown.bs.dropdown.tsz').on('shown.bs.dropdown.tsz','.tsz-menu',function(){"
        ."var r=this.getBoundingClientRect();var m=$(this).find('.dropdown-menu');"
        ."m.css({position:'fixed',top:r.bottom+'px',left:Math.max(8,r.right-m.outerWidth())+'px',right:'auto'});});\n";
    $js.=tsz_tiny($path,$scanning)."\n";
    $js.="Loadjs('$page?scan-status=yes');\n$refresh\n</script>";

    $out=tsz_style().$tpl->_ENGINE_parse_body(implode("\n",$html)).$js;
    if($hasTable){
        $out.=awc_table_style().awc_pager_js(25,"tsz-rows","tsz-pager").awc_filter_js("tsz-q","tsz-rows","tsz-pager");
    }else{
        $out.=awc_table_style();
    }
    echo $out;
}

// ---------------------------------------------------------------------------
// Fenetres : taille rapide, repartition, historique.

function quick_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog6("{quick_size}","$page?quick-popup=yes&path=".tsz_q(tsz_param_path()),650);
}

function quick_popup(){
    $tpl=new template_admin();
    $path=tsz_param_path();
    $json=tsz_get("/treesize/quick?path=".tsz_q($path));
    if(empty($json["success"])){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars((string)($json["error"] ?? "{error}"))));
        return;
    }
    $d=$json["data"];
    $h=array();
    $h[]="<table class='awc-tbl'><tbody>";
    $h[]="<tr><th style='width:30%'>{path}</th><td>".htmlspecialchars($d["path"] ?? $path)."</td></tr>";
    $h[]="<tr><th>{total_size}</th><td><strong>".htmlspecialchars($d["size_human"] ?? "")."</strong></td></tr>";
    $h[]="<tr><th>{apparent_size}</th><td>".tsz_bytes($d["apparent_sz"] ?? 0)."</td></tr>";
    $h[]="<tr><th>{files}</th><td>".$tpl->FormatNumber($d["file_count"] ?? 0)."</td></tr>";
    $h[]="<tr><th>{directories}</th><td>".$tpl->FormatNumber($d["dir_count"] ?? 0)."</td></tr>";
    $h[]="</tbody></table>";
    echo awc_table_style().$tpl->_ENGINE_parse_body(implode("\n",$h));
}

function chart_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog6("{tsz_distribution}","$page?chart-popup=yes&path=".tsz_q(tsz_param_path()),1000);
}

function tsz_bytes_js():string{
    return "function TszFmt(b){if(!b){return '0 B';}var k=1024,s=['B','KB','MB','GB','TB','PB'],i=Math.floor(Math.log(b)/Math.log(k));return parseFloat((b/Math.pow(k,i)).toFixed(2))+' '+s[i];}\n";
}

function chart_popup(){
    $tpl=new template_admin();
    $path=tsz_param_path();
    $bar=tsz_get("/treesize/chart/bar?path=".tsz_q($path)."&limit=15");
    if(empty($bar["success"]) || !isset($bar["data"])){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_data_available}||{please_run_scan}."));
        return;
    }
    $data=json_encode($bar["data"],JSON_HEX_TAG);
    $h="<div class='row'><div class='col-md-6'><canvas id='tsz-bar' style='max-height:480px'></canvas></div>"
      ."<div class='col-md-6'><canvas id='tsz-pie' style='max-height:480px'></canvas></div></div>";
    $js="<script>\n".tsz_bytes_js()
       ."var tszBar=$data;tszBar.options=tszBar.options||{};tszBar.options.plugins=tszBar.options.plugins||{};\n"
       ."tszBar.options.plugins.tooltip={callbacks:{label:function(c){return c.label+': '+TszFmt(c.raw);}}};\n"
       ."tszBar.options.scales={x:{ticks:{callback:function(v){return TszFmt(v);}}}};\n"
       ."new Chart(document.getElementById('tsz-bar').getContext('2d'),tszBar);\n"
       ."var tszPie=JSON.parse(JSON.stringify($data));tszPie.type='pie';\n"
       ."tszPie.options={responsive:true,plugins:{legend:{position:'right'},tooltip:{callbacks:{label:function(c){return c.label+': '+TszFmt(c.raw);}}}}};\n"
       ."new Chart(document.getElementById('tsz-pie').getContext('2d'),tszPie);\n</script>";
    echo $h.$js;
}

function history_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog6("{history}","$page?history-popup=yes&path=".tsz_q(tsz_param_path()),1000);
}

function history_popup(){
    $tpl=new template_admin();
    $path=tsz_param_path();
    $days=intval($_GET["days"] ?? 30);
    if($days<1){$days=30;}
    $json=tsz_get("/treesize/chart/timeline?path=".tsz_q($path)."&days=$days");
    $labels=$json["data"]["data"]["labels"] ?? array();
    $sizes=$json["data"]["data"]["datasets"][0]["data"] ?? array();
    if(empty($json["success"]) || !is_array($labels) || count($labels)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_history_data}||{run_multiple_scans}."));
        return;
    }
    $fmt=array();
    foreach($labels as $ts){ $fmt[]=date("Y-m-d H:i",intval($ts)); }
    $ac=tsz_accents();
    $cfg=array("type"=>"line","data"=>array("labels"=>$fmt,"datasets"=>array(array(
        "label"=>tsz_t("{size}"),"data"=>$sizes,"borderColor"=>$ac["accent"],
        "backgroundColor"=>$ac["accent_soft"],"fill"=>true,"tension"=>0.1))),
        "options"=>array("responsive"=>true,"plugins"=>array("legend"=>array("display"=>false)),
            "scales"=>array("y"=>array("beginAtZero"=>false))));
    $data=json_encode($cfg,JSON_HEX_TAG);
    echo "<canvas id='tsz-history' style='max-height:400px'></canvas><script>\n".tsz_bytes_js()
        ."var tszH=$data;tszH.options.plugins.tooltip={callbacks:{label:function(c){return TszFmt(c.raw);}}};\n"
        ."tszH.options.scales.y.ticks={callback:function(v){return TszFmt(v);}};\n"
        ."new Chart(document.getElementById('tsz-history').getContext('2d'),tszH);\n</script>";
}
