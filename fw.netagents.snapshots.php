<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["verbose"])){
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors',1);
    ini_set('error_reporting',E_ALL);
}

$users=new usersMenus();
if(!meta_node_admin($users)){exit;}

// JS dispatchers (content-type: javascript)
if(isset($_GET["snap-js"])){snap_js();exit;}

// POST dispatchers
if(isset($_POST["build"])){snap_build();exit;}
if(isset($_POST["restore"])){snap_restore();exit;}
if(isset($_POST["delete"])){snap_delete();exit;}
if(isset($_POST["upload"])){snap_upload();exit;}
if(isset($_POST["move_post"])){snap_move();exit;}
if(isset($_POST["move_group_post"])){snap_move_group();exit;}

// GET dispatchers (HTML)
if(isset($_GET["snap-list"])){snap_list();exit;}
if(isset($_GET["download"])){snap_download();exit;}
if(isset($_GET["move-poll"])){snap_move_poll();exit;}
if(isset($_GET["move-group-poll"])){snap_move_group_poll();exit;}

// Default
snap_form();

// ── Helpers ──────────────────────────────────────────────────────────────

function aid():int{
    return intval($_GET["snap-js"] ?? $_GET["id"] ?? $_GET["snap-list"] ?? $_GET["download"] ?? 0);
}

// Extensions d'un instantane Artica (backup/names.go isSnapshotName) : en clair,
// scelle (AES-256-GCM) ou ancien format chiffre. Les .sealed et .aes etaient refuses.
function snap_is_snapshot_name(string $f):bool{
    return (bool)preg_match('/\.tar\.gz(\.sealed|\.aes)?$/i',$f);
}

function validate_filename(string $f):bool{
    if(strlen($f)<1) return false;
    if(preg_match('/[\/\\\]|\.\./',$f)) return false;
    return true;
}

function netagent_error($json):string{
    if(is_object($json)){return htmlspecialchars($json->Error ?? "{error}");}
    return "{error}";
}

// ── Modele et widgets (refonte Stitch 2026-11-19, stitch_artica_node_snapshots_modal) ──
// Donnees : /netagents/status/{id} (nom, version, hotfix, heure du dernier rapport, instantanes)
// et /netagents/get/{id} (etat en ligne/hors ligne decide par le demon, version de l'agent).

/** Token traduit, %1/%2 remplaces APRES traduction (cache par requete). */
function snp_t($token,$args=array()):string{
    static $cache=array();
    if(!isset($cache[$token])){
        $tpl=new template_admin();
        $cache[$token]=preg_replace('#<br\s*/?>\s*$#i','',$tpl->_ENGINE_parse_body("{".$token."}"));
    }
    $s=$cache[$token];
    for($i=count($args);$i>=1;$i--){ $s=str_replace("%$i",(string)$args[$i-1],$s); }
    return $s;
}

/** Horodatage Go (Z, fuseau) -> epoch ; 0 si vide ou date nulle. */
function snp_epoch($iso):int{
    $iso=trim((string)$iso);
    if($iso==="" || strpos($iso,"0001-01-01")===0){return 0;}
    try{ $d=new DateTime($iso); return $d->getTimestamp(); }catch(\Exception $e){ return 0; }
}

/** Age relatif lisible. */
function snp_ago($ts):string{
    $ts=intval($ts);
    if($ts<=0){return "—";}
    $d=max(0,time()-$ts);
    if($d<60){return snp_t("snp_ago_now");}
    if($d<3600){return snp_t("snp_ago_min",array(intval($d/60)));}
    if($d<86400){return snp_t("snp_ago_hour",array(intval($d/3600)));}
    return snp_t("snp_ago_day",array(intval($d/86400)));
}

/** « Today, 14:32 » / « Yesterday, 09:10 » / « 2026-11-12 22:00 ». */
function snp_day_label($ts):string{
    $ts=intval($ts);
    if($ts<=0){return "—";}
    $hm=date("H:i",$ts);
    if(date("Y-m-d",$ts)===date("Y-m-d")){return snp_t("snp_today",array($hm));}
    if(date("Y-m-d",$ts)===date("Y-m-d",time()-86400)){return snp_t("snp_yesterday",array($hm));}
    return date("Y-m-d H:i",$ts);
}

/** Taille humaine depuis des octets. */
function snp_size($bytes):string{
    $b=floatval($bytes);
    if($b<=0){return "—";}
    $u=array("B","KB","MB","GB","TB");$i=0;
    while($b>=1024 && $i<count($u)-1){$b/=1024;$i++;}
    return ($i>=3?number_format($b,1):number_format($b,0))." ".$u[$i];
}

/** Protection deduite de l'extension (backup/names.go) : scelle, ancien chiffre, en clair. */
function snp_protection($filename):string{
    if(preg_match('/\.tar\.gz\.sealed$/i',$filename)){return "sealed";}
    if(preg_match('/\.tar\.gz\.aes$/i',$filename)){return "legacy";}
    return "plain";
}

/** Lit les deux routes une seule fois et rend un tableau plat pour tous les widgets. */
function snp_model(int $id,$status=null):array{
    $m=array("ok"=>false,"id"=>$id,"hostname"=>"#$id","version"=>"","sp"=>0,"hotfix"=>"","report"=>0,
        "state"=>"","agentver"=>"","snaps"=>array(),"total"=>0,"latest"=>0);
    if($status===null){ $status=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id")); }
    if(!is_object($status) || (isset($status->Status) && !$status->Status && !isset($status->hostname))){return $m;}
    $m["ok"]=true;
    if(!empty($status->hostname)){$m["hostname"]=(string)$status->hostname;}
    $m["version"]=(string)($status->artica_version ?? "");
    $m["sp"]=intval($status->artica_service_pack ?? 0);
    $m["hotfix"]=(string)($status->artica_hotfix ?? "");
    $m["report"]=snp_epoch($status->time ?? "");
    $rows=array();
    if(isset($status->artica_snapshots) && is_array($status->artica_snapshots)){
        foreach($status->artica_snapshots as $s){
            if(!is_object($s) || empty($s->filename)){continue;}
            $ts=snp_epoch($s->mod_time ?? "");
            $size=intval($s->size ?? 0);
            $rows[]=array("filename"=>(string)$s->filename,"size"=>$size,"size_human"=>(string)($s->size_human ?? ""),"ts"=>$ts);
            $m["total"]+=$size;
            if($ts>$m["latest"]){$m["latest"]=$ts;}
        }
    }
    usort($rows,function($a,$b){ return $b["ts"]-$a["ts"]; });
    $m["snaps"]=$rows;
    $ag=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/get/$id"));
    if(is_object($ag) && !(isset($ag->Status) && $ag->Status===false)){
        $m["state"]=(string)($ag->status ?? "");
        $m["agentver"]=(string)($ag->version ?? "");
    }
    return $m;
}

function snp_style():string{
    if(isset($GLOBALS["SNP_STYLE"])){return "";}
    $GLOBALS["SNP_STYLE"]=true;
    $accent="#006a62";$soft="rgba(16,185,129,.12)";$line="#99d5cf";$dark="#00504a";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.snp{display:flex;flex-direction:column;gap:14px;color:#111d23}
.snp>div:empty{display:none}
.snp-hero{display:flex;gap:16px;align-items:flex-start;background:$soft;border:1px solid $line;border-radius:12px;padding:16px 18px}
.snp-hero-ico{flex:0 0 44px;height:44px;border-radius:10px;background:#fff;border:1px solid $line;display:flex;align-items:center;justify-content:center;color:$accent;font-size:20px}
.snp-hero h3{margin:0 0 4px 0;font-size:16px;font-weight:700;color:#111d23}
.snp-hero p{margin:0;font-size:13px;line-height:1.55;color:#3d4947}
.snp-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.snp-chip{display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid $line;border-radius:999px;padding:3px 10px;font-size:12px;color:#3d4947}
.snp-chip i,.snp-chip svg{color:$accent}
.snp-node{display:flex;flex-wrap:wrap;align-items:center;gap:6px 10px;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-size:13px;color:#3d4947;background:#fff}
.snp-node strong{color:#111d23}
.snp-sep{color:#bcc9c6}
.snp-mono{font-family:monospace;font-size:12px}
.snp-tag{background:#f1f5f9;border-radius:6px;padding:1px 7px}
.snp-off{display:flex;gap:10px;align-items:flex-start;background:#fff7ed;border:1px solid #fcd9a8;border-radius:10px;padding:10px 14px;font-size:13px;color:#653e00}
.snp-off i,.snp-off svg{color:#b45309;margin-top:2px}
.snp-sum{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:10px}
.snp-sum-txt{font-size:14px;color:#3d4947}
.snp-sum-txt strong{color:#111d23}
.snp-acts{display:flex;gap:10px;flex-wrap:wrap}
.snp-help{width:100%;text-align:right;font-size:12px;color:#6d7a77;margin-top:-4px}
.snp-btn{display:inline-flex;align-items:center;gap:8px;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;border:1px solid $accent;background:#fff;color:$accent;white-space:nowrap}
.snp-btn:hover{background:$soft}
.snp-btn:focus-visible,.snp-link:focus-visible,.snp-del:focus-visible{outline:2px solid $accent;outline-offset:2px}
.snp-btn[disabled]{opacity:.6;cursor:default}
.snp-btn-primary{background:$accent;color:#fff}
.snp-btn-primary:hover{background:$dark}
.snp-btn-restore{border-color:#b45309;color:#865300;padding:5px 12px}
.snp-btn-restore:hover{background:#fff7ed}
.snp-link{border:0;background:transparent;color:#334155;font-size:13px;font-weight:600;cursor:pointer;padding:4px 6px;text-decoration:none;white-space:nowrap}
.snp-link:hover{color:$accent;text-decoration:underline}
.snp-del{border:0;background:transparent;color:#94a3b8;cursor:pointer;width:30px;height:30px;border-radius:6px}
.snp-del:hover{color:#ba1a1a;background:#fef2f2}
.snp-card{border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#fff}
.snp-tbl{width:100%;border-collapse:collapse;font-size:13px}
.snp-tbl th{background:#f8fafc;color:#3d4947;font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;text-align:left;padding:10px 14px;border-bottom:1px solid #e2e8f0}
.snp-tbl td{padding:12px 14px;border-bottom:1px solid #eef2f6;vertical-align:middle}
.snp-tbl tr:last-child td{border-bottom:0}
.snp-when{font-size:14px;font-weight:700;color:#111d23}
.snp-rel{font-size:12px;color:#6d7a77;margin-left:6px}
.snp-fname{display:block;margin-top:3px;font-family:monospace;font-size:12px;color:#6d7a77;word-break:break-all}
.snp-latest{display:inline-flex;align-items:center;gap:4px;margin-left:6px;background:$soft;color:$accent;border:1px solid $line;border-radius:6px;padding:1px 7px;font-size:11px;font-weight:700}
.snp-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 10px;font-size:12px;font-weight:600;border:1px solid transparent;white-space:nowrap}
.snp-pill-enc{background:$soft;color:$accent;border-color:$line}
.snp-pill-plain{background:#f1f5f9;color:#3d4947;border-color:#e2e8f0}
.snp-legacy{display:block;font-size:11px;color:#6d7a77;margin-top:3px}
.snp-rowacts{display:flex;justify-content:flex-end;align-items:center;gap:4px;flex-wrap:nowrap}
.snp-empty{text-align:center;padding:36px 16px}
.snp-empty .snp-empty-ico{font-size:30px;color:$accent;margin-bottom:10px}
.snp-empty h4{margin:0 0 6px 0;font-size:16px;font-weight:700}
.snp-empty p{margin:0 auto 14px auto;font-size:13px;color:#3d4947;max-width:520px;line-height:1.5}
.snp-upl{border:1px dashed $line;border-radius:12px;padding:14px 16px;background:#fbfdfd}
.snp-upl p{margin:6px 0 0 0;font-size:12px;color:#6d7a77;line-height:1.5}
.snp-upl input[type=file]{font-size:13px}
.snp-upl-acts{display:flex;gap:8px;margin-top:10px}
.snp-note{display:flex;gap:10px;align-items:flex-start;border-radius:10px;padding:10px 14px;font-size:13px;line-height:1.5}
.snp-note-info{background:$soft;border:1px solid $line;color:#00504a}
.snp-note-warn{background:#fff7ed;border:1px solid #fcd9a8;color:#653e00}
.snp-note .snp-x{margin-left:auto;border:0;background:transparent;color:inherit;cursor:pointer;font-weight:600}
.snp-foot{display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;font-size:12px;color:#6d7a77;border-top:1px solid #eef2f6;padding-top:10px}
@media (prefers-reduced-motion:reduce){.snp-btn{transition:none}}
</style>";
}

/** Bandeau d'accroche. */
function snp_hero():string{
    return "<div class='snp-hero'><span class='snp-hero-ico'><i class='fas fa-clock-rotate-left'></i></span><div>"
        ."<h3>{snp_hero_title}</h3><p>{snp_hero_text}</p>"
        ."<div class='snp-chips'>"
        ."<span class='snp-chip'><i class='fas fa-hand'></i>{snp_chip_norestore}</span>"
        ."<span class='snp-chip'><i class='fas fa-lock'></i>{snp_chip_encrypted}</span>"
        ."<span class='snp-chip'><i class='fas fa-download'></i>{snp_chip_download}</span>"
        ."</div></div></div>";
}

/** Bande d'identite du noeud : nom, version, hotfix, dernier rapport. */
function snp_node_strip(array $m):string{
    $tpl=new template_admin();
    $p=array();
    $p[]="<i class='fas fa-server' style='color:#6d7a77'></i> <strong>".htmlspecialchars($m["hostname"])."</strong>";
    if($m["version"]!==""){
        $v="Artica ".htmlspecialchars(preg_replace('/^([0-9]+\.[0-9]+)\.0+$/','$1',$m["version"]));
        if($m["sp"]>0){$v.=" SP ".$m["sp"];}
        $p[]=$v;
    }
    if($m["hotfix"]!==""){$p[]="<span class='snp-mono snp-tag'>hotfix ".htmlspecialchars($m["hotfix"])."</span>";}
    if($m["report"]>0){
        $exact=htmlspecialchars(strval($tpl->time_to_date($m["report"],true)));
        $p[]="<span title=\"$exact\">".snp_t("snp_last_report",array(snp_ago($m["report"])))."</span>";
    }
    return "<div class='snp-node'>".implode(" <span class='snp-sep'>·</span> ",$p)."</div>";
}

/** Avertissement seulement quand le demon juge le noeud hors ligne. */
function snp_offline(array $m):string{
    if($m["state"]!=="offline"){return "";}
    return "<div class='snp-off' role='status'><i class='fas fa-triangle-exclamation'></i><span>{snp_offline_text}</span></div>";
}

/** Zone d'envoi d'un fichier (repliee). */
function snp_upload_panel(int $id):string{
    $h=array();
    $h[]="<div id='snap-upload-form-$id' class='snp-upl' style='display:none'>";
    $h[]="<form id='snap-upload-frm-$id' enctype='multipart/form-data'>";
    $h[]="<input type='hidden' name='upload' value='$id'>";
    $h[]="<label for='snap-upload-file-$id' style='font-weight:600;font-size:13px'>{snp_upload_label}</label><br>";
    $h[]="<input type='file' id='snap-upload-file-$id' name='snapshot' accept='.gz,.sealed,.aes'>";
    $h[]="<p>{snap_upload_types}</p><p>{snp_upload_norestore}</p>";
    $h[]="<div class='snp-upl-acts'><button type='button' class='snp-btn snp-btn-primary' onclick=\"SnapshotUploadSubmit_$id()\"><i class='fas fa-upload'></i> {snp_upload_btn}</button>";
    $h[]="<button type='button' class='snp-btn' onclick=\"SnapshotUpload_$id()\">{snp_cancel}</button></div>";
    $h[]="</form><div id='snap-upload-result-$id' style='margin-top:8px'></div></div>";
    return implode("\n",$h);
}

/** Compteurs + boutons principaux. */
function snp_summary(array $m):string{
    $id=intval($m["id"]);
    $n=count($m["snaps"]);
    $parts=array();
    $parts[]="<strong>".($n==1?snp_t("snp_count_one"):snp_t("snp_count",array($n)))."</strong>";
    if($n>0){
        $parts[]=snp_t("snp_total",array(snp_size($m["total"])));
        $parts[]=snp_t("snp_latest_ago",array(snp_ago($m["latest"])));
    }
    $h ="<div class='snp-sum'><div class='snp-sum-txt'>".implode(" <span class='snp-sep'>·</span> ",$parts)."</div>";
    $h.="<div class='snp-acts'><button type='button' class='snp-btn' onclick=\"SnapshotUpload_$id()\"><i class='fas fa-upload'></i> {snp_upload_open}</button>";
    $h.="<button type='button' id='snap-build-btn-$id' class='snp-btn snp-btn-primary' onclick=\"SnapshotBuild_$id()\"><i class='fas fa-camera'></i> {snp_create}</button></div>";
    $h.="<div class='snp-help'>{snp_create_help}</div></div>";
    return $h;
}

/** Une ligne : date lisible, nom de fichier, taille, protection, actions. */
function snp_row(array $m,array $s,bool $latest):string{
    $id=intval($m["id"]);
    $page=CurrentPageName();
    $fname=htmlspecialchars($s["filename"]);
    $enc=urlencode($s["filename"]);
    $js=htmlspecialchars(json_encode($s["filename"]),ENT_QUOTES);
    $label=snp_day_label($s["ts"]);
    $jsLabel=htmlspecialchars(json_encode(html_entity_decode($label,ENT_QUOTES,"UTF-8")),ENT_QUOTES);
    $size=$s["size"]>0?snp_size($s["size"]):htmlspecialchars($s["size_human"]);
    $prot=snp_protection($s["filename"]);
    if($prot=="plain"){
        $pill="<span class='snp-pill snp-pill-plain'><i class='fas fa-lock-open'></i> {snp_not_encrypted}</span>";
    }else{
        $pill="<span class='snp-pill snp-pill-enc'><i class='fas fa-lock'></i> {snp_encrypted}</span>";
        if($prot=="legacy"){$pill.="<span class='snp-legacy'>{snp_legacy_format}</span>";}
    }
    $h ="<tr>";
    $h.="<td><span class='snp-when'>$label</span>".($latest?"<span class='snp-latest'><i class='fas fa-star'></i> {snp_latest}</span>":"")
        ."<span class='snp-rel'>(".snp_ago($s["ts"]).")</span><span class='snp-fname'>$fname</span></td>";
    $h.="<td style='white-space:nowrap'>$size</td>";
    $h.="<td>$pill</td>";
    $h.="<td><div class='snp-rowacts'>";
    $h.="<a class='snp-link' href='$page?download=$id&filename=$enc' target='_blank' title=\"{snp_download_help}\">{download}</a>";
    $h.="<button type='button' class='snp-link' onclick=\"SnapshotMovePicker_$id($js)\" title=\"{snap_copy_keeps_original}\">{snap_copy_to_node}</button>";
    $h.="<button type='button' class='snp-btn snp-btn-restore' onclick=\"SnapshotRestore_$id($js,$jsLabel)\">{restore}</button>";
    $h.="<button type='button' class='snp-del' aria-label=\"{snp_delete}\" title=\"{snp_delete}\" onclick=\"SnapshotDelete_$id($js,$jsLabel)\"><i class='fas fa-trash-can'></i></button>";
    $h.="</div></td>";
    $h.="</tr>";
    return $h;
}

/** Tableau, ou invitation quand le noeud n'a aucun instantane. */
function snp_table(array $m):string{
    $id=intval($m["id"]);
    if(count($m["snaps"])==0){
        return "<div class='snp-card'><div class='snp-empty'><div class='snp-empty-ico'><i class='fas fa-camera'></i></div>"
            ."<h4>{snp_empty_title}</h4><p>{snp_empty_text}</p>"
            ."<button type='button' class='snp-btn snp-btn-primary' onclick=\"SnapshotBuild_$id()\"><i class='fas fa-camera'></i> {snp_create}</button></div></div>";
    }
    $h=array();
    $h[]="<div class='snp-card'><table class='snp-tbl'><thead><tr>";
    $h[]="<th>{snp_col_snapshot}</th><th>{snp_col_size}</th><th>{snp_col_protection}</th><th style='text-align:right'>{snp_col_actions}</th>";
    $h[]="</tr></thead><tbody>";
    foreach($m["snaps"] as $i=>$s){ $h[]=snp_row($m,$s,$i==0); }
    $h[]="</tbody></table></div>";
    return implode("\n",$h);
}

/** Pied : identifiant du noeud et version de l'agent. */
function snp_footer(array $m):string{
    $r=$m["agentver"]!==""?"<span>".snp_t("snp_agent_version",array(htmlspecialchars($m["agentver"])))."</span>":"<span></span>";
    return "<div class='snp-foot'><span><i class='fas fa-shield-halved'></i> {snp_node_id} <span class='snp-mono'>#".intval($m["id"])."</span></span>$r</div>";
}

/** Compteurs + tableau : la partie relue apres chaque action. data-sig change seulement si la liste change. */
function snp_list_body(array $m):string{
    $names=array();
    foreach($m["snaps"] as $s){$names[]=$s["filename"];}
    $sig=md5(implode("|",$names));
    return "<div class='snp-list-body' data-sig='$sig' style='display:flex;flex-direction:column;gap:14px'>".snp_summary($m).snp_table($m)."</div>";
}

/** Etat du noeud illisible (demon ou cache indisponible). */
function snp_unreadable():string{
    return "<div class='snp-note snp-note-warn' role='alert'><i class='fas fa-plug-circle-xmark'></i><span>{snp_status_unreadable}</span></div>";
}

/** Texte d'un jeton pret pour une chaine JS (accents conserves, pas d'entites). */
function snp_js_text($token,$args=array()):string{
    return json_encode(html_entity_decode(snp_t($token,$args),ENT_QUOTES,"UTF-8"));
}

// ── snap_js() ────────────────────────────────────────────────────────────

function snap_js():bool{
    $id=intval($_GET["snap-js"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog6("{snapshots}","$page?id=$id",900);

}

// ── snap_form() ──────────────────────────────────────────────────────────

function snap_form(){
    $tpl=new template_admin();
    $id=intval($_GET["id"] ?? 0);
    if($id<1){echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));return;}
    $m=snp_model($id);
    if(!$m["ok"]){
        echo snp_style().$tpl->_ENGINE_parse_body("<div class='snp'>".snp_unreadable()."</div>");
        return;
    }
    $h=array();
    $h[]="<div class='snp'>";
    $h[]=snp_hero();
    $h[]=snp_node_strip($m);
    $h[]=snp_offline($m);
    $h[]="<div id='snap-build-result-$id'></div>";
    $h[]="<div id='snap-restore-result-$id'></div>";
    $h[]="<div id='snap-move-result-$id'></div>";
    $h[]=snp_upload_panel($id);
    $h[]="<div id='snap-list-$id'>".snp_list_body($m)."</div>";
    $h[]=snp_footer($m);
    $h[]="</div>";
    echo snp_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).snap_js_block($id);
}

// ── snap_list() ──────────────────────────────────────────────────────────

function snap_list(){
    $id=intval($_GET["snap-list"]);
    // flush=1 : relire l'etat du noeud au lieu du cache (suivi d'un televersement).
    if(isset($_GET["flush"])){$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/flush/$id");}
    echo snap_list_inner($id);
}

function snap_list_inner(int $id):string{
    $tpl=new template_admin();
    $m=snp_model($id);
    if(!$m["ok"]){return snp_style().$tpl->_ENGINE_parse_body(snp_unreadable());}
    return snp_style().$tpl->_ENGINE_parse_body(snp_list_body($m))."<script>NoSpinner();</script>";
}

// ── snap_download() ──────────────────────────────────────────────────────

function snap_download(){
    $id=intval($_GET["download"]);
    $filename=basename($_GET["filename"] ?? "");
    if(!validate_filename($filename)){
        http_response_code(400);
        echo "Invalid filename";
        exit();
    }

    // Direct cURL — REST_API() rejects non-JSON responses, so we bypass it for binary downloads.
    $sock="/usr/share/artica-postfix/bin/run/articarest.sock";
    $uri="http://localhost/netagents/snapshots/$id/download/".rawurlencode($filename);
    $ch=curl_init();
    curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $sock);
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    // Perimetre Meta de la session (X-Artica-Meta-Scope) : sans lui, un tenant telechargerait
    // l'instantane de n'importe quel noeud comme l'infogerant.
    $sock_scope=isset($GLOBALS["CLASS_SOCKETS"]) && is_object($GLOBALS["CLASS_SOCKETS"])?$GLOBALS["CLASS_SOCKETS"]:new sockets();
    curl_setopt($ch, CURLOPT_HTTPHEADER, $sock_scope->META_SCOPE_HEADERS());
    $data=curl_exec($ch);
    $httpCode=curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr=curl_error($ch);
    curl_close($ch);

    if($data===false||strlen($curlErr)>0){
        http_response_code(502);
        echo "Download failed: ".htmlspecialchars($curlErr);
        exit();
    }

    if($httpCode!==200){
        http_response_code($httpCode);
        // Go returns JSON error on failure
        $check=@json_decode($data);
        if(is_object($check)&&isset($check->Error)){
            echo htmlspecialchars($check->Error);
        }else{
            echo "Download failed (HTTP $httpCode)";
        }
        exit();
    }

    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Content-Length: ".strlen($data));
    echo $data;
    exit();
}

// ── snap_build() ─────────────────────────────────────────────────────────

function snap_build(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_POST["build"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/snapshots/$id/build"));
    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        echo awc_table_style().$tpl->_ENGINE_parse_body($tpl->div_error(netagent_error($json)));
        return;
    }

    // Flush status cache so subsequent polls see the new snapshot sooner
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/flush/$id");

    $h=array();
    $h[]="<div id='snap-build-msg-$id' class='snp-note snp-note-info' role='status'><i class='fas fa-spinner fa-spin'></i><span>{snp_build_started}</span>"
        ."<button type='button' class='snp-x' aria-label='{snp_dismiss}' onclick=\"\$('#snap-build-msg-$id').remove()\">&times;</button></div>";
    // Suivi : la liste du noeud est rafraichie toutes les 3 min ; on relit toutes les 10 s pendant 4 min
    // et on s'arrete quand l'ensemble des fichiers change (data-sig), pas sur un age relatif qui bouge.
    $h[]="<script>";
    $h[]="(function(){";
    $h[]="  var attempts=0,maxAttempts=24;";
    $h[]="  var sig0=\$('#snap-list-$id .snp-list-body').attr('data-sig')||'';";
    $h[]="  var iv=setInterval(function(){";
    $h[]="    attempts++;";
    $h[]="    if(!document.getElementById('snap-list-$id')){clearInterval(iv);return;}";
    $h[]="    $.get('$page?snap-list=$id&flush=1',function(r){";
    $h[]="      $('#snap-list-$id').html(r);";
    $h[]="      var sig=\$('#snap-list-$id .snp-list-body').attr('data-sig')||'';";
    $h[]="      if(sig!==sig0||attempts>=maxAttempts){";
    $h[]="        clearInterval(iv);";
    $h[]="        $('#snap-build-msg-$id').remove();";
    $h[]="      }";
    $h[]="    });";
    $h[]="  },10000);";
    $h[]="})();";
    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── snap_restore() ───────────────────────────────────────────────────────

function snap_restore(){
    $tpl=new template_admin();
    $id=intval($_POST["restore"]);
    $filename=basename($_POST["filename"] ?? "");
    if(!validate_filename($filename)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/netagents/snapshots/$id/restore/".rawurlencode($filename)
    ));
    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_error($json)));
        return;
    }

    $h=array();
    $h[]="<div id='snap-restore-msg-$id' class='snp-note snp-note-warn' role='status'><i class='fas fa-clock-rotate-left'></i><span>{snapshot_restore_started}</span>"
        ."<button type='button' class='snp-x' onclick=\"\$('#snap-restore-msg-$id').remove()\">{snp_dismiss}</button></div>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── snap_upload() ────────────────────────────────────────────────────────

function snap_upload(){
    $tpl=new template_admin();
    $id=intval($_POST["upload"]);
    if(empty($_FILES["snapshot"]["tmp_name"])){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{select_a_file}"));
        return;
    }
    $filename=basename($_FILES["snapshot"]["name"] ?? "");
    if(!snap_is_snapshot_name($filename)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{snap_upload_bad_type}"));
        return;
    }
    if(!validate_filename($filename)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }
    $binary=file_get_contents($_FILES["snapshot"]["tmp_name"]);
    if($binary===false||strlen($binary)===0){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $json=json_decode(
        $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_BINARY(
            "/netagents/snapshots/$id/upload?filename=".urlencode($filename),
            $binary
        )
    );

    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_error($json)));
        return;
    }

    // Flush cache
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/flush/$id");

    $h=array();
    // Le noeud verifie, dechiffre et renomme le fichier en arriere-plan : il
    // n'apparait dans la liste qu'ensuite. On relit la liste toutes les 10 s
    // pendant 2 minutes.
    $page=CurrentPageName();
    $h[]=$tpl->_ENGINE_parse_body("<div class='snp-note snp-note-info' id='snap-upload-msg-$id' role='status'><i class='fas fa-circle-check'></i><span>{snapshot_uploaded} {snap_upload_checked}</span></div>");
    $h[]="<div id='snap-upload-list-$id'>".snap_list_inner($id)."</div>";
    $h[]="<script>(function(){var n=0,iv=setInterval(function(){n++;";
    $h[]="if(!document.getElementById('snap-upload-list-$id')||n>12){clearInterval(iv);return;}";
    $h[]="\$.get('$page?snap-list=$id&flush=1',function(r){var d=document.getElementById('snap-upload-list-$id');if(d){d.innerHTML=r;}});";
    $h[]="},10000);})();</script>";
    echo implode("\n",$h);
}

// ── snap_delete() ────────────────────────────────────────────────────────

function snap_delete(){
    $tpl=new template_admin();
    $id=intval($_POST["delete"]);
    $filename=basename($_POST["filename"] ?? "");
    if(!validate_filename($filename)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $json=json_decode(
        $GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE(
            "/netagents/snapshots/$id/".rawurlencode($filename)
        )
    );
    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_error($json)));
        return;
    }

    // Flush cache
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/flush/$id");

    echo snap_list_inner($id);
}

// ── snap_move() ──────────────────────────────────────────────────────────

function snap_move(){
    $tpl=new template_admin();
    $source_id=intval($_POST["source_id"] ?? 0);
    $dest_id=intval($_POST["dest_id"] ?? 0);
    $filename=basename($_POST["filename"] ?? "");

    if($source_id<1||$dest_id<1||!validate_filename($filename)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $payload=array(
        "filename"=>$filename,
        "source_id"=>$source_id,
        "dest_id"=>$dest_id,
    );

    $json=json_decode(
        $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
            "/netagents/snapshots/$source_id/move",
            $payload
        )
    );

    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_error($json)));
        return;
    }

    $op_id=htmlspecialchars($json->op_id ?? "");
    if(strlen($op_id)<1){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $h=array();
    $h[]="<div id='snap-move-status-$source_id-$op_id'>";
    $h[]="  <p><i class='fa fa-spinner fa-spin'></i> {snapshot_move_started}</p>";
    $h[]="</div>";
    $h[]=snap_move_poll_js($source_id,$dest_id,$op_id);
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

function snap_move_poll(){
    $op_id=trim($_GET["move-poll"] ?? "");
    if(strlen($op_id)<1){
        http_response_code(400);
        echo json_encode(array("error"=>"missing op_id"));
        return;
    }
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/snapshots/move/status/".rawurlencode($op_id));
    if(!$resp||strlen($resp)<2){
        http_response_code(404);
        echo json_encode(array("error"=>"not found"));
        return;
    }
    header("Content-Type: application/json");
    echo $resp;
}

function snap_move_poll_js(int $source_id,int $dest_id,string $op_id):string{
    $js=array();
    $js[]="<script>";
    $js[]="(function(){";
    $js[]="  var div=document.getElementById('snap-move-status-$source_id-$op_id');";
    $js[]="  if(!div) return;";
    $sp='<i class="fa fa-spinner fa-spin"></i> ';
    $js[]="  var labels={";
    $js[]="    pending:".json_encode($sp).'+'.snp_js_text("snp_mv_pending").",";
    $js[]="    downloading:".json_encode($sp).'+'.snp_js_text("snp_mv_downloading").",";
    $js[]="    uploading:".json_encode($sp).'+'.snp_js_text("snp_mv_uploading").",";
    $js[]="    refreshing:".json_encode($sp).'+'.snp_js_text("snp_mv_refreshing").",";
    $js[]="    completed:'<span class=\"label label-primary\" style=\"color:#fff\"><i class=\"fas fa-check\"></i> {snapshot_moved}</span>',";
    $js[]="    failed:'<span class=\"label label-danger\" style=\"color:#fff\"><i class=\"fas fa-times\"></i> {snapshot_move_failed}</span>'";
    $js[]="  };";
    $js[]="  var iv=setInterval(function(){";
    $js[]="    $.get('fw.netagents.snapshots.php?move-poll=$op_id',function(r){";
    $js[]="      try{var d=JSON.parse(r);}catch(e){return;}";
    $js[]="      var s=d.status||'pending';";
    $js[]="      var html=labels[s]||labels.pending;";
    $js[]="      if(s==='failed'&&d.error) html+=' <span class=\"text-danger\">'+d.error+'</span>';";
    $js[]="      div.innerHTML='<p>'+html+'</p>';";
    $js[]="      if(d.done===true) clearInterval(iv);";
    $js[]="    }).fail(function(xhr){";
    $js[]="      if(xhr.status===404){";
    $js[]="        div.innerHTML='<p class=\"text-muted\">{snapshot_move_expired}</p>';";
    $js[]="        clearInterval(iv);";
    $js[]="      }";
    $js[]="    });";
    $js[]="  },3000);";
    $js[]="})();";
    $js[]="</script>";
    return implode("\n",$js);
}

// ── snap_move_group() ────────────────────────────────────────────────────

function snap_move_group(){
    $tpl=new template_admin();
    $source_id=intval($_POST["source_id"] ?? 0);
    $group_id=intval($_POST["group_id"] ?? 0);
    $filename=basename($_POST["filename"] ?? "");

    if($source_id<1||$group_id<1||!validate_filename($filename)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $payload=array(
        "filename"=>$filename,
        "source_id"=>$source_id,
        "group_id"=>$group_id,
    );

    $json=json_decode(
        $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
            "/netagents/snapshots/$source_id/move-group",
            $payload
        )
    );

    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(netagent_error($json)));
        return;
    }

    $op_id=htmlspecialchars($json->op_id ?? "");
    if(strlen($op_id)<1){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    // Build per-agent status table
    $h=array();
    $h[]="<div id='snap-mgrp-$source_id-$op_id' class='ibox' style='margin-top:10px'>";
    $h[]="<div class='ibox-title'><h5><i class='fas fa-share-alt'></i> {snapshot_group_move}</h5></div>";
    $h[]="<div class='ibox-content'>";
    $h[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $h[]="<thead><tr><th>{hostname}</th><th>{status}</th></tr></thead>";
    $h[]="<tbody id='snap-mgrp-tbody-$op_id'>";

    if(isset($json->agents)&&is_array($json->agents)){
        foreach($json->agents as $ag){
            if(!is_object($ag)) continue;
            $agId=intval($ag->agent_id ?? 0);
            $agHost=htmlspecialchars($ag->hostname ?? "#$agId");
            $h[]="<tr id='snap-mgrp-row-$op_id-$agId'>";
            $h[]="  <td>$agHost</td>";
            $h[]="  <td id='snap-mgrp-st-$op_id-$agId'><i class='fa fa-clock'></i> {pending}</td>";
            $h[]="</tr>";
        }
    }

    $h[]="</tbody></table></div></div></div>";
    $h[]="<div id='snap-mgrp-overall-$op_id'><i class='fa fa-spinner fa-spin'></i> {snapshot_move_started}</div>";
    $h[]="</div></div>";

    // Polling JS
    $h[]=snap_move_group_poll_js($source_id,$op_id);
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

function snap_move_group_poll(){
    $op_id=trim($_GET["move-group-poll"] ?? "");
    if(strlen($op_id)<1){
        http_response_code(400);
        echo json_encode(array("error"=>"missing op_id"));
        return;
    }
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/netagents/snapshots/move-group/status/".rawurlencode($op_id)
    );
    if(!$resp||strlen($resp)<2){
        http_response_code(404);
        echo json_encode(array("error"=>"not found"));
        return;
    }
    header("Content-Type: application/json");
    echo $resp;
}

function snap_move_group_poll_js(int $source_id,string $op_id):string{
    $js=array();
    $js[]="<script>";
    $js[]="(function(){";
    $js[]="  var statusLabels={";
    $js[]="    pending:'<i class=\"fa fa-clock\"></i> {pending}',";
    $js[]="    uploading:'<i class=\"fa fa-spinner fa-spin\"></i> {uploading}',";
    $js[]="    completed:'<span class=\"label label-primary\" style=\"color:#fff\"><i class=\"fas fa-check\"></i> {completed}</span>',";
    $js[]="    failed:'<span class=\"label label-danger\" style=\"color:#fff\"><i class=\"fas fa-times\"></i> {failed}</span>'";
    $js[]="  };";
    $js[]="  var overallLabels={";
    $js[]="    downloading:'<i class=\"fa fa-spinner fa-spin\"></i> {downloading_from_source}',";
    $js[]="    uploading:'<i class=\"fa fa-spinner fa-spin\"></i> {uploading_to_agents}',";
    $js[]="    completed:'<span class=\"label label-primary\" style=\"color:#fff\"><i class=\"fas fa-check\"></i> {snapshot_group_move_completed}</span>',";
    $js[]="    failed:'<span class=\"label label-danger\" style=\"color:#fff\"><i class=\"fas fa-times\"></i> {snapshot_group_move_failed}</span>'";
    $js[]="  };";
    $js[]="  var iv=setInterval(function(){";
    $js[]="    $.get('fw.netagents.snapshots.php?move-group-poll=$op_id',function(r){";
    $js[]="      try{var d=JSON.parse(r);}catch(e){return;}";
    $js[]="      if(d.agents){";
    $js[]="        for(var i=0;i<d.agents.length;i++){";
    $js[]="          var a=d.agents[i];";
    $js[]="          var cell=document.getElementById('snap-mgrp-st-$op_id-'+a.agent_id);";
    $js[]="          if(!cell) continue;";
    $js[]="          var lbl=statusLabels[a.status]||statusLabels.pending;";
    $js[]="          if(a.status==='failed'&&a.error) lbl+=' <span class=\"text-danger\">'+a.error+'</span>';";
    $js[]="          cell.innerHTML=lbl;";
    $js[]="        }";
    $js[]="      }";
    $js[]="      var ov=document.getElementById('snap-mgrp-overall-$op_id');";
    $js[]="      if(ov){";
    $js[]="        var olbl=overallLabels[d.status]||overallLabels.downloading;";
    $js[]="        ov.innerHTML=olbl;";
    $js[]="      }";
    $js[]="      if(d.done===true) clearInterval(iv);";
    $js[]="    }).fail(function(xhr){";
    $js[]="      if(xhr.status===404){";
    $js[]="        var ov=document.getElementById('snap-mgrp-overall-$op_id');";
    $js[]="        if(ov) ov.innerHTML='<span class=\"text-muted\">{snapshot_move_expired}</span>';";
    $js[]="        clearInterval(iv);";
    $js[]="      }";
    $js[]="    });";
    $js[]="  },3000);";
    $js[]="})();";
    $js[]="</script>";
    return implode("\n",$js);
}

// ── snap_js_block() ──────────────────────────────────────────────────────

function snap_js_block(int $id):string{
    $tpl=new template_admin();
    $page="fw.netagents.snapshots.php";
    $js=array();
    $js[]="<script>";

    // Build
    $js[]="function SnapshotBuild_$id(){";
    $js[]="  $('#snap-build-btn-$id').prop('disabled',true).html('<i class=\"fas fa-spinner fa-spin\"></i> {building}');";
    $js[]="  $.post('$page',{build:$id},function(r){";
    $js[]="    $('#snap-build-result-$id').html(r);";
    $js[]="    $('#snap-build-btn-$id').prop('disabled',false).html('<i class=\"fas fa-camera\"></i> {snp_create}');";
    $js[]="  });";
    $js[]="}";

    // Restore : ce qui change, ce qui est perdu, avant l'action
    $rt=snp_js_text("snp_restore_title");
    $rb="<ul style='text-align:left;margin:10px 0 0 0;padding-left:18px;line-height:1.5'>"
        ."<li>".html_entity_decode(snp_t("snp_restore_b1"),ENT_QUOTES,"UTF-8")."</li>"
        ."<li>".html_entity_decode(snp_t("snp_restore_b2"),ENT_QUOTES,"UTF-8")."</li>"
        ."<li>".html_entity_decode(snp_t("snp_restore_b3"),ENT_QUOTES,"UTF-8")."</li></ul>";
    $js[]="function SnapshotRestore_$id(filename,label){";
    $js[]="  var esc=function(t){return \$('<div>').text(t).html();};";
    $js[]="  swal({title:$rt.replace('%1',label),text:".json_encode($rb).".replace('%1',esc(label)),type:'warning',html:true,";
    $js[]="    showCancelButton:true,confirmButtonColor:'#b45309',confirmButtonText:".snp_js_text("snp_restore_confirm").",cancelButtonText:".snp_js_text("snp_cancel");
    $js[]="  },function(isConfirm){";
    $js[]="    if(!isConfirm) return;";
    $js[]="    $.post('$page',{restore:$id,filename:filename},function(r){";
    $js[]="      $('#snap-restore-result-$id').html(r);";
    $js[]="    });";
    $js[]="  });";
    $js[]="}";

    // Delete : supprime le fichier du noeud seulement
    $js[]="function SnapshotDelete_$id(filename,label){";
    $js[]="  swal({title:".snp_js_text("snp_delete_title").".replace('%1',label),text:".snp_js_text("snp_delete_text").",type:'warning',";
    $js[]="    showCancelButton:true,confirmButtonColor:'#ba1a1a',confirmButtonText:".snp_js_text("snp_delete_confirm").",cancelButtonText:".snp_js_text("snp_cancel");
    $js[]="  },function(isConfirm){";
    $js[]="    if(!isConfirm) return;";
    $js[]="    $.post('$page',{'delete':$id,filename:filename},function(r){";
    $js[]="      $('#snap-list-$id').html(r);";
    $js[]="    });";
    $js[]="  });";
    $js[]="}";

    // Upload: toggle form
    $js[]="function SnapshotUpload_$id(){";
    $js[]="  $('#snap-upload-form-$id').toggle();";
    $js[]="}";

    // Upload: submit via FormData
    $select_a_file=$tpl->javascript_parse_text("{select_a_file}");
    $bad_type=$tpl->javascript_parse_text("{snap_upload_bad_type}");
    $js[]="function SnapshotUploadSubmit_$id(){";
    $js[]="  var form=document.getElementById('snap-upload-frm-$id');";
    $js[]="  if(!form.snapshot.files.length){\$('#snap-upload-result-$id').html('<span class=\"text-danger\">$select_a_file</span>');return;}";
    $js[]="  var f=form.snapshot.files[0];";
    $js[]="  if(!/\.tar\.gz(\.sealed|\.aes)?$/i.test(f.name)){\$('#snap-upload-result-$id').html('<span class=\"text-danger\">$bad_type</span>');return;}";
    $js[]="  var fd=new FormData(form);";
    $js[]="  $('#snap-upload-result-$id').html('<i class=\"fa fa-spinner fa-spin\"></i>');";
    $js[]="  $.ajax({url:'$page',type:'POST',data:fd,";
    $js[]="    processData:false,contentType:false,";
    $js[]="    success:function(r){";
    $js[]="      $('#snap-list-$id').html(r);";
    $js[]="      $('#snap-upload-form-$id').hide();";
    $js[]="      $('#snap-upload-result-$id').html('');";
    $js[]="    },error:function(){\$('#snap-upload-result-$id').html('<span class=\"text-danger\">{error}</span>');}";
    $js[]="  });";
    $js[]="}";

    // Move picker opener
    $js[]="function SnapshotMovePicker_$id(filename){";
    $js[]="  Loadjs('fw.netagents.snapshots.move.php?move-js=$id&filename='+encodeURIComponent(filename));";
    $js[]="}";

    $js[]="</script>";
    return $tpl->_ENGINE_parse_body($js);
}
