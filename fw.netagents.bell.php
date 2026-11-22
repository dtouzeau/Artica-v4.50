<?php
// Artica Meta - alertes systeme d'UN noeud (fenetre ouverte depuis la pastille cloche de la
// liste des agents et des groupes). Maquette stitch_artica_meta_bell (2026-11-16).
// Lecture seule : une alerte n'a ni date, ni identifiant, ni acquittement. L'agent du noeud
// (ArticaLightAgent, internal/artica/bell.go) la recalcule toutes les 5 minutes et elle
// disparait d'elle-meme quand sa cause est corrigee.
// Donnees : /netagents/get/{id} (dernier contact, controle de perimetre tenant) puis
// /netagents/status/{id} -> artica_bell {count, notifications[{title,description,severity}]}
// et time (date de collecte du statut).
// La pastille de la liste ne compte que DANGER + WARN : le bandeau compte de la meme facon et
// nomme les INFO a part.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
include_once(dirname(__FILE__)."/ressources/class.netagents.tenants.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){ include_once(dirname(__FILE__)."/ressources/class.sockets.inc"); }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
// Meme porte que la liste des agents, sur les DEUX points d'entree (l'ancienne URL de la
// fenetre n'en avait aucune).
if(!function_exists("meta_can_view") || !meta_can_view()){
    natn_refuse("Refused: Artica Meta node alerts (no Artica Meta privilege)",isset($_GET["notification"])?"popup":"bell-js");
}
if(isset($_GET["notification"])){nab_popup();exit;}
nab_js();

function nab_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $net=new ArticaNetAgents($id);
    $name=$net->GetAgentHostname();
    if(strlen($name)<1){$name="#$id";}
    return $tpl->js_dialog6("<i class='fas fa-bell'></i> {nab_title} &mdash; ".nab_h($name),"$page?notification=$id",1000);
}

// Texte venu du noeud : echappe, accolades comprises (_ENGINE_parse_body les lirait comme jetons).
function nab_h($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}
// Jeton traduit, pour y substituer un nombre ou une duree apres traduction.
function nab_t($tok):string{
    static $c=array();
    if(!isset($c[$tok])){ $tpl=new template_admin(); $c[$tok]=$tpl->_ENGINE_parse_body($tok); }
    return $c[$tok];
}
function nab_tn($tok,$v):string{ return str_replace("%s",(string)$v,nab_t($tok)); }

// Horodatage Go (RFC3339) ou epoch -> epoch ; 0 pour une valeur vide ou le temps nul de Go.
function nab_epoch($v):int{
    if(is_numeric($v)){ return intval($v)>0?intval($v):0; }
    $v=trim((string)$v);
    if($v=="" || strpos($v,"0001-01-01")===0 || strpos($v,"1970-01-01")===0){return 0;}
    $t=strtotime($v);
    return $t===false?0:intval($t);
}
// Age « il y a 3 h » avec les jetons deja traduits de la console (dkimg_ago_*).
function nab_ago($t):string{
    $t=intval($t);
    if($t<=0){ return ""; }
    $d=time()-$t;
    if($d<60){ return nab_t("{dkimg_ago_now}"); }
    if($d<3600){ return nab_tn("{dkimg_ago_min}",intval($d/60)); }
    if($d<86400){ return nab_tn("{dkimg_ago_h}",intval($d/3600)); }
    $days=intval($d/86400);
    if($days<14){ return $days==1?nab_t("{dkimg_ago_d1}"):nab_tn("{dkimg_ago_d}",$days); }
    if($days<60){ return nab_tn("{dkimg_ago_w}",intval($days/7)); }
    if($days<365){ return nab_tn("{dkimg_ago_mo}",intval($days/30)); }
    $y=intval($days/365);
    return $y==1?nab_t("{dkimg_ago_y1}"):nab_tn("{dkimg_ago_y}",$y);
}

// Les trois niveaux, dans l'ordre d'affichage.
function nab_levels():array{
    return array(
        "crit"=>array("tok"=>"{nab_crit}","ico"=>"fas fa-circle-exclamation"),
        "warn"=>array("tok"=>"{nab_warn}","ico"=>"fas fa-triangle-exclamation"),
        "info"=>array("tok"=>"{nab_info}","ico"=>"fas fa-circle-info"),
    );
}
// DANGER / WARN / INFO de l'agent ; une gravite absente ou inconnue est une information
// (l'ancienne page la traitait deja ainsi).
function nab_level($sev):string{
    $sev=strtoupper(trim((string)$sev));
    if($sev=="DANGER"){return "crit";}
    if($sev=="WARN" || $sev=="WARNING"){return "warn";}
    return "info";
}

// Le modele de la fenetre, lu UNE fois.
// error = fiche illisible (refus de perimetre compris) ; none = aucun statut d'alertes recu.
function nab_model($id):array{
    $m=array("id"=>$id,"error"=>false,"none"=>false,"last_seen"=>0,"collected"=>0,
        "groups"=>array("crit"=>array(),"warn"=>array(),"info"=>array()),"total"=>0);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/get/$id"),true);
    if(!is_array($json) || (isset($json["Status"]) && !$json["Status"])){
        $m["error"]=true; return $m;
    }
    $m["last_seen"]=nab_epoch(isset($json["last_seen"])?$json["last_seen"]:"");
    $agent=new ArticaNetAgents($id);
    $st=$agent->Status();
    if(!isset($st["artica_bell"]) || !is_array($st["artica_bell"])){
        $m["none"]=true; return $m;
    }
    $m["collected"]=nab_epoch(isset($st["time"])?$st["time"]:"");
    $list=isset($st["artica_bell"]["notifications"])?$st["artica_bell"]["notifications"]:array();
    if(!is_array($list)){$list=array();}
    foreach($list as $n){
        if(!is_array($n)){continue;}
        $title=trim((string)(isset($n["title"])?$n["title"]:""));
        $desc=trim((string)(isset($n["description"])?$n["description"]:""));
        if($title=="" && $desc==""){continue;}
        $m["groups"][nab_level(isset($n["severity"])?$n["severity"]:"")][]=array("title"=>$title,"desc"=>$desc);
        $m["total"]++;
    }
    return $m;
}
function nab_worst(array $m):string{
    foreach(array("crit","warn","info") as $k){ if(count($m["groups"][$k])>0){return $k;} }
    return "ok";
}

// « 1 critical alert, 2 warnings, 1 information »
function nab_headline(array $m):string{
    $parts=array();
    $map=array("crit"=>array("{nab_h_crit1}","{nab_h_critn}"),"warn"=>array("{nab_h_warn1}","{nab_h_warnn}"),
        "info"=>array("{nab_h_info1}","{nab_h_infon}"));
    foreach($map as $k=>$toks){
        $c=count($m["groups"][$k]);
        if($c==0){continue;}
        $parts[]=nab_tn($c==1?$toks[0]:$toks[1],$c);
    }
    if(count($parts)==0){ return nab_t("{nab_h_none}"); }
    return implode(", ",$parts);
}

function nab_chips(array $m):string{
    $h=array();
    foreach(nab_levels() as $k=>$lv){
        $c=count($m["groups"][$k]);
        $cls=$c>0?"nab-chip nab-$k":"nab-chip nab-zero";
        $h[]="<span class='$cls'><i class='{$lv["ico"]}' aria-hidden='true'></i> {$lv["tok"]} <b>".($c>0?$c:"&mdash;")."</b></span>";
    }
    return "<div class='nab-chips'>".implode("",$h)."</div>";
}

function nab_summary(array $m):string{
    $w=nab_worst($m);
    $ico=array("crit"=>"fas fa-circle-exclamation","warn"=>"fas fa-triangle-exclamation",
        "info"=>"fas fa-circle-info","ok"=>"fas fa-circle-check");
    return "<div class='nab-sum nab-sum-$w'>"
        ."<div class='nab-tile nab-tile-$w' aria-hidden='true'><i class='{$ico[$w]}'></i></div>"
        ."<div class='nab-sum-txt'><div class='nab-head'>".nab_headline($m)."</div>"
        ."<div class='nab-expl'>{nab_explain}</div></div>"
        .nab_chips($m)
        ."</div>";
}

// Date de collecte du statut + dernier contact ; bandeau orange au-dela de 15 min sans contact.
function nab_fresh(array $m):string{
    $parts=array();
    if($m["collected"]>0){
        $parts[]=nab_tn("{nab_collected}",nab_ago($m["collected"])." (".date("Y-m-d H:i",$m["collected"]).")");
    }
    if($m["last_seen"]>0){
        $parts[]=nab_tn("{nab_contact}",nab_ago($m["last_seen"]));
    }
    $line=count($parts)>0?implode(" <span class='nab-dot' aria-hidden='true'>&bull;</span> ",$parts):"";
    $stale=$m["last_seen"]<=0 || (time()-$m["last_seen"])>900;
    if(!$stale){
        return "<div class='nab-fresh'><span class='nab-fresh-l'><i class='fas fa-clock' aria-hidden='true'></i> $line</span>"
            ."<span class='nab-pill nab-pill-ok'><i class='fas fa-circle-check' aria-hidden='true'></i> {nab_fresh}</span></div>";
    }
    $msg=$m["last_seen"]>0?nab_tn("{nab_last_report}","<b>".nab_ago($m["last_seen"])."</b>"):nab_t("{nab_never_seen}");
    $h="<div class='nab-stale' role='status'><i class='fas fa-plug-circle-exclamation' aria-hidden='true'></i>"
        ."<div><div>$msg</div>".($line!=""?"<div class='nab-stale-sub'>$line</div>":"")."</div>"
        ."<span class='nab-pill nab-pill-warn'><i class='fas fa-clock' aria-hidden='true'></i> {nab_stale_pill}</span></div>";
    return $h;
}

// « Current: x, Available: y » (contrôles de version de l'agent) : version courante et
// version disponible mises en evidence ; tout autre texte est affiche tel quel.
function nab_desc($desc):string{
    if($desc==""){return "";}
    if(preg_match('/^Current:\s*(.+?),\s*Available:\s*(.+)$/',$desc,$re)){
        return "<div class='nab-desc'>{nab_current} <span class='nab-ver'>".nab_h($re[1])."</span>"
            ." <i class='fas fa-arrow-right nab-arrow' aria-hidden='true'></i> "
            ."{nab_available} <span class='nab-ver nab-ver-new'>".nab_h($re[2])."</span></div>";
    }
    return "<div class='nab-desc'>".nab_h($desc)."</div>";
}

function nab_group($k,array $lv,array $items):string{
    if(count($items)==0){return "";}
    $h=array();
    $h[]="<section class='nab-grp' aria-labelledby='nab-g-$k'>";
    $h[]="<h3 class='nab-grp-t nab-t-$k' id='nab-g-$k'><span class='nab-bullet' aria-hidden='true'></span>{$lv["tok"]} <span class='nab-grp-n'>&middot; ".count($items)."</span></h3>";
    $h[]="<ul class='nab-list'>";
    foreach($items as $it){
        $title=$it["title"]!=""?nab_h($it["title"]):nab_h($it["desc"]);
        $desc=$it["title"]!=""?nab_desc($it["desc"]):"";
        $h[]="<li class='nab-card nab-card-$k'>"
            ."<i class='{$lv["ico"]} nab-card-ico' aria-hidden='true'></i>"
            ."<div class='nab-card-body'><span class='nab-sr'>{nab_sev_sr} {$lv["tok"]}. </span>"
            ."<div class='nab-card-t'>$title</div>$desc</div>"
            ."<span class='nab-pill nab-pill-$k' aria-hidden='true'><i class='{$lv["ico"]}'></i> {$lv["tok"]}</span>"
            ."</li>";
    }
    $h[]="</ul></section>";
    return implode("",$h);
}

function nab_empty($ico,$cls,$titleTok,$textTok):string{
    return "<div class='nab-empty $cls'><i class='$ico' aria-hidden='true'></i>"
        ."<div class='nab-empty-t'>$titleTok</div><div class='nab-empty-s'>$textTok</div></div>";
}

function nab_style():string{
    return "<style>
.nab{font-size:13px;color:#0f172a;line-height:1.45}
.nab-sum{position:sticky;top:0;z-index:2;display:flex;align-items:center;gap:16px;padding:16px 20px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;margin-bottom:12px}
.nab-sum-crit{background:#fef6f6;border-color:#fbd5d5}.nab-sum-warn{background:#fffbf0;border-color:#fde7b0}
.nab-tile{flex:0 0 44px;width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff}
.nab-tile-crit{background:#dc2626}.nab-tile-warn{background:#d97706}.nab-tile-info{background:#2563eb}.nab-tile-ok{background:#16a34a}
.nab-sum-txt{flex:1 1 auto;min-width:0}
.nab-head{font-size:18px;font-weight:600;line-height:1.3;margin-bottom:2px}
.nab-expl{color:#475569;font-size:13px}
.nab-chips{display:flex;gap:8px;flex:0 0 auto;flex-wrap:wrap;justify-content:flex-end}
.nab-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:13px;font-weight:600;white-space:nowrap}
.nab-chip b{display:inline-block;min-width:20px;text-align:center;padding:0 6px;border-radius:999px;background:rgba(255,255,255,.85);font-weight:700}
.nab-crit{background:#fef2f2;color:#b91c1c}.nab-warn{background:#fffbeb;color:#b45309}.nab-info{background:#eff6ff;color:#1d4ed8}
.nab-zero{background:#f1f5f9;color:#64748b}
.nab-fresh{display:flex;align-items:center;justify-content:space-between;gap:12px;color:#475569;margin:0 4px 14px}
.nab-fresh-l>i{color:#64748b;margin-right:4px}
.nab-dot{color:#94a3b8;margin:0 4px}
.nab-stale{display:flex;align-items:flex-start;gap:12px;background:#fffbeb;border-left:3px solid #d97706;border-radius:6px;padding:10px 14px;margin:0 0 14px;color:#78350f}
.nab-stale>i{font-size:18px;color:#d97706;margin-top:1px}.nab-stale>div{flex:1 1 auto}
.nab-stale-sub{color:#92400e;margin-top:2px}
.nab-pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:13px;font-weight:600;white-space:nowrap;flex:0 0 auto}
.nab-pill-ok{background:#dcfce7;color:#166534}.nab-pill-warn{background:#fef3c7;color:#92400e}
.nab-pill-crit{background:#fef2f2;color:#b91c1c}.nab-pill-info{background:#eff6ff;color:#1d4ed8}
.nab-grp{margin-bottom:14px}
.nab-grp-t{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin:0 0 8px 2px;display:flex;align-items:center;gap:6px}
.nab-grp-n{color:#64748b;font-weight:600;letter-spacing:0}
.nab-bullet{width:8px;height:8px;border-radius:50%;background:currentColor;display:inline-block}
.nab-t-crit{color:#b91c1c}.nab-t-warn{color:#b45309}.nab-t-info{color:#1d4ed8}
.nab-list{list-style:none;margin:0;padding:0}
.nab-card{display:flex;align-items:center;gap:14px;min-height:56px;padding:12px 16px;margin-bottom:8px;background:#fff;border:1px solid #e2e8f0;border-left:4px solid #94a3b8;border-radius:8px;box-shadow:0 1px 2px rgba(15,23,42,.05);animation:nabIn .15s ease-out}
.nab-card-crit{border-left-color:#dc2626}.nab-card-warn{border-left-color:#d97706}.nab-card-info{border-left-color:#2563eb}
.nab-card-ico{font-size:22px;flex:0 0 28px;text-align:center}
.nab-card-crit .nab-card-ico{color:#dc2626}.nab-card-warn .nab-card-ico{color:#d97706}.nab-card-info .nab-card-ico{color:#2563eb}
.nab-card-body{flex:1 1 auto;min-width:0;overflow-wrap:anywhere}
.nab-card-t{font-size:14px;font-weight:600;color:#0f172a}
.nab-desc{color:#475569;font-size:13px;margin-top:2px}
.nab-ver{font-family:SFMono-Regular,Menlo,Consolas,monospace;font-weight:600;color:#334155}
.nab-ver-new{color:#15803d}.nab-arrow{color:#94a3b8;font-size:11px;margin:0 4px}
.nab-sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
.nab-empty{text-align:center;padding:36px 20px 28px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;margin-bottom:12px}
.nab-empty>i{font-size:48px;color:#94a3b8}.nab-empty.nab-ok>i{color:#16a34a}
.nab-empty-t{font-size:16px;font-weight:600;margin-top:10px}
.nab-empty-s{color:#64748b;margin-top:4px}
@keyframes nabIn{from{opacity:0}to{opacity:1}}
@media (prefers-reduced-motion:reduce){.nab-card{animation:none}}
@media (max-width:860px){.nab-sum{flex-wrap:wrap}.nab-chips{width:100%;justify-content:flex-start;padding-left:60px}}
</style>";
}

function nab_popup(){
    $tpl=new template_admin();
    $id=intval($_GET["notification"]);
    $m=nab_model($id);
    $h=array();
    $h[]="<div class='nab'>";
    if($m["error"] || $m["none"]){
        $h[]=nab_empty("fas fa-circle-question","","{nab_na_title}","{nab_na_text}");
    }elseif($m["total"]==0){
        $h[]=nab_empty("fas fa-circle-check","nab-ok","{nab_h_none}","{nab_empty_text}");
        $h[]=nab_fresh($m);
    }else{
        $h[]=nab_summary($m);
        $h[]=nab_fresh($m);
        foreach(nab_levels() as $k=>$lv){ $h[]=nab_group($k,$lv,$m["groups"][$k]); }
    }
    $h[]="</div><script>NoSpinner();</script>";
    echo nab_style().$tpl->_ENGINE_parse_body(@implode("",$h));
}
