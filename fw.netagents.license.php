<?php
// Artica Meta - licence Artica d'UN noeud (fenetre ouverte depuis la liste des agents).
// Maquette stitch_artica_meta_license (2026-11-16). Lecture seule : Meta ne peut ni renouveler
// ni saisir une cle sur un noeud. Donnees : /netagents/get/{id} (dernier contact) et
// /netagents/status/{id} -> artica_license (reponse brute de /license/status du noeud,
// rafraichie toutes les 5 minutes par l'agent).
// L'edition se lit sur le NOEUD (gold_license / entreprise_license / Info.license_status),
// jamais sur CORP_GOLD()/CORP_LICENSE() du serveur Meta lui-meme (ancien bug).
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
include_once(dirname(__FILE__)."/ressources/class.netagents.tenants.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
// Donnees d'un noeud : reservees aux comptes Meta (meme porte que les actions de noeud).
if(!function_exists("meta_node_admin") || !meta_node_admin()){
    natn_refuse("Refused: Artica Meta node license (no Artica Meta privilege)",isset($_GET["popup"])?"popup":"license-js");
}
if(isset($_GET["popup"])){nal_popup();exit;}
nal_js();

function nal_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $AgentObj=new ArticaNetAgents($id);
    $hostname=$AgentObj->GetAgentHostname();
    if(strlen($hostname)<1){$hostname="#$id";}
    return $tpl->js_dialog12("<i class='".ico_certificate."'></i> ".nal_h($hostname)." &mdash; {license}","$page?popup=yes&id=$id",1050);
}

// Texte venu du noeud : echappe, accolades comprises (_ENGINE_parse_body les lirait comme jetons).
function nal_h($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}

// Jeton traduit, pour y substituer un nombre ou une date apres traduction.
function nal_t($tok):string{
    static $c=array();
    if(!isset($c[$tok])){ $tpl=new template_admin(); $c[$tok]=$tpl->_ENGINE_parse_body($tok); }
    return $c[$tok];
}
function nal_tn($tok,$v):string{ return str_replace("%s",(string)$v,nal_t($tok)); }

// Horodatage Go (RFC3339) ou epoch -> epoch ; 0 pour une valeur vide ou le temps nul de Go.
function nal_epoch($v):int{
    if(is_numeric($v)){ return intval($v)>0?intval($v):0; }
    $v=trim((string)$v);
    if($v=="" || strpos($v,"0001-01-01")===0 || strpos($v,"1970-01-01")===0){return 0;}
    $t=strtotime($v);
    return $t===false?0:intval($t);
}

// Age « il y a 3 h » avec les jetons deja traduits de la console (dkimg_ago_*).
function nal_ago($t):string{
    $t=intval($t);
    if($t<=0){ return ""; }
    $d=time()-$t;
    if($d<60){ return nal_t("{dkimg_ago_now}"); }
    if($d<3600){ return nal_tn("{dkimg_ago_min}",intval($d/60)); }
    if($d<86400){ return nal_tn("{dkimg_ago_h}",intval($d/3600)); }
    $days=intval($d/86400);
    if($days<14){ return $days==1?nal_t("{dkimg_ago_d1}"):nal_tn("{dkimg_ago_d}",$days); }
    if($days<60){ return nal_tn("{dkimg_ago_w}",intval($days/7)); }
    if($days<365){ return nal_tn("{dkimg_ago_mo}",intval($days/30)); }
    $y=intval($days/365);
    return $y==1?nal_t("{dkimg_ago_y1}"):nal_tn("{dkimg_ago_y}",$y);
}

// Le modele de la fenetre, lu UNE fois.
// kind : gold | active | soon | grace | expired | invalid | community ; error = fiche illisible ;
// none = le noeud n'a pas (encore) remonte de licence.
function nal_model($id):array{
    $m=array("id"=>$id,"error"=>false,"none"=>false,"kind"=>"community","days"=>0,"final"=>0,
        "number"=>"","about"=>"","company"=>"","request_by"=>"","checked"=>0,"last_seen"=>0);
    // La fiche de l'agent passe par le controle de perimetre du demon (tenants) : sans elle,
    // rien n'est affiche.
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/get/$id"),true);
    if(!is_array($json) || (isset($json["Status"]) && !$json["Status"])){
        $m["error"]=true; return $m;
    }
    $m["last_seen"]=nal_epoch(isset($json["last_seen"])?$json["last_seen"]:"");
    $agent=new ArticaNetAgents($id);
    $st=$agent->Status();
    $lic=isset($st["artica_license"])?$st["artica_license"]:null;
    if(!is_array($lic) || !isset($lic["Info"]) || !is_array($lic["Info"])){
        $m["none"]=true; return $m;
    }
    $info=$lic["Info"];
    $status=strtolower((string)(isset($info["license_status"])?$info["license_status"]:""));
    $m["final"]=nal_epoch(isset($info["FINAL_TIME"])?$info["FINAL_TIME"]:0);
    $m["number"]=trim((string)(isset($info["license_number"])?$info["license_number"]:""));
    $m["about"]=trim((string)(isset($info["ABOUT_PP"])?$info["ABOUT_PP"]:""));
    $m["company"]=trim((string)(isset($info["COMPANY"])?$info["COMPANY"]:""));
    $m["request_by"]=trim((string)(isset($info["REQUEST_BY"])?$info["REQUEST_BY"]:""));
    $m["checked"]=nal_epoch(isset($info["TIME"])?$info["TIME"]:0);
    if($m["final"]>0){ $m["days"]=(int)floor(($m["final"]-time())/86400); }
    if(!empty($lic["gold_license"])){
        $m["kind"]="gold";
    }elseif(strpos($status,"grace_period")!==false){
        $m["kind"]="grace";
    }elseif(strpos($status,"license_invalid")!==false){
        $m["kind"]="invalid";
    }elseif(strpos($status,"license_expired")!==false){
        $m["kind"]="expired";
    }elseif(!empty($lic["entreprise_license"])){
        // Corporate encore actif apres la date de fin = periode de grace cote noeud.
        if($m["final"]>0 && $m["final"]<time()){ $m["kind"]="grace"; }
        elseif($m["final"]>0 && $m["days"]<=30){ $m["kind"]="soon"; }
        else{ $m["kind"]="active"; }
    }
    return $m;
}

// Bandeau d'edition : tuile, nom, pastille d'etat, phrase, et a droite le temps restant.
function nal_hero(array $m):string{
    $k=$m["kind"];
    $def=array(
        "gold"=>array("fas fa-award","{nal_gold}","","","{nal_gold_text}"),
        "active"=>array("fas fa-building-shield","{nal_enterprise}","ok","fas fa-circle-check|{nal_active}","{nal_ent_text}"),
        "soon"=>array("fas fa-building-shield","{nal_enterprise}","warn","fas fa-hourglass-half|{nal_soon}","{nal_ent_text}"),
        "grace"=>array("fas fa-building-shield","{nal_enterprise}","warn","fas fa-triangle-exclamation|{nal_grace}","{nal_grace_text}"),
        "expired"=>array("fas fa-building-circle-xmark","{nal_enterprise}","err","fas fa-circle-xmark|{nal_expired}","{nal_expired_text}"),
        "invalid"=>array("fas fa-building-circle-xmark","{nal_enterprise}","err","fas fa-ban|{nal_invalid}","{nal_invalid_text}"),
        "community"=>array("fas fa-users","{nal_community}","","","{nal_community_text}"),
    );
    $d=$def[$k];
    $pill="";
    if($d[3]!==""){
        list($pi,$pl)=explode("|",$d[3]);
        $pill="<span class='nal-pill nal-pill-{$d[2]}'><i class='$pi'></i> $pl</span>";
    }
    $h=array();
    $h[]="<section class='nal-card nal-hero nal-k-$k'>";
    $h[]="<span class='nal-tile'><i class='{$d[0]}'></i></span>";
    $h[]="<div class='nal-hero-b'><div class='nal-ed'><span class='nal-ed-n'>{$d[1]}</span>$pill</div><div class='nal-ed-s'>{$d[4]}</div></div>";
    $h[]=nal_left($m);
    $h[]="</section>";
    return @implode("",$h);
}

// Temps restant : un nombre et une date, colore selon le delai. Pas de jauge : la duree
// de la licence n'est pas connue, un ratio n'aurait pas de plafond reel.
function nal_left(array $m):string{
    $k=$m["kind"];
    if($k==="community"){ return ""; }
    if($k==="gold"){
        return "<div class='nal-left'><div class='nal-left-n nal-c-gold'><i class='fas fa-infinity'></i> {nal_never_expires}</div></div>";
    }
    if($m["final"]<=0){
        return "<div class='nal-left'><div class='nal-left-n nal-c-mute'>&mdash;</div><div class='nal-left-d'>{nal_expire_date}: &mdash;</div></div>";
    }
    $days=$m["days"];
    if($m["final"]<time()){
        $ago=intval((time()-$m["final"])/86400);
        $txt=$ago<1?nal_t("{nal_expired_today}"):($ago==1?nal_t("{nal_expired_ago1}"):nal_tn("{nal_expired_ago}",$ago));
        $tone="err";
    }else{
        $txt=$days<1?nal_t("{nal_expires_today}"):($days==1?nal_t("{nal_day_left}"):nal_tn("{nal_days_left}",$days));
        $tone=$days>30?"ok":($days>7?"warn":"err");
    }
    return "<div class='nal-left'><div class='nal-left-n nal-c-$tone'>".nal_h($txt)."</div>"
        ."<div class='nal-left-d'>{nal_expire_date}: <b>".date("Y-m-d",$m["final"])."</b></div></div>";
}

// Une carte du detail : icone, libelle, valeur (HTML deja echappe), aide grise.
function nal_field($icon,$label,$valueHtml,$help,$extra=""){
    return "<article class='nal-card nal-f'><div class='nal-f-l'><i class='$icon'></i> $label</div>"
        ."<div class='nal-f-v'>$valueHtml$extra</div><div class='nal-help'>$help</div></article>";
}

function nal_details(array $m):string{
    $dash="<span class='nal-c-mute'>&mdash;</span>";
    $f=array();
    $num=$dash; $copy="";
    if($m["number"]!==""){
        $num="<code class='nal-key' id='nal-key-{$m["id"]}'>".nal_h($m["number"])."</code>";
        $copy="<button type='button' class='nal-copy' data-key='nal-key-{$m["id"]}' aria-label=\"".nal_h(nal_t("{nal_copy_aria}"))."\">"
            ."<i class='far fa-copy'></i> <span>{nal_copy}</span></button>";
    }
    $f[]=nal_field("fas fa-certificate","{nal_license_number}",$num,"{nal_license_number_help}",$copy);
    $f[]=nal_field("fas fa-graduation-cap","{nal_type}",$m["about"]===""?$dash:nal_h($m["about"]),"{nal_type_help}");
    $f[]=nal_field("fas fa-city","{nal_company}",$m["company"]===""?$dash:nal_h($m["company"]),"{nal_issued_help}");
    $f[]=nal_field("fas fa-user-tie","{nal_requested_by}",$m["request_by"]===""?$dash:nal_h($m["request_by"]),"{nal_issued_help}");
    if($m["kind"]==="gold"){
        $exp="<span>{nal_never_expires}</span>";
    }elseif($m["final"]>0){
        $d=$m["days"];
        if($m["final"]<time()){ $ago=intval((time()-$m["final"])/86400); $rel=$ago<1?nal_t("{nal_expired_today}"):($ago==1?nal_t("{nal_expired_ago1}"):nal_tn("{nal_expired_ago}",$ago)); $tone="err"; }
        else{ $rel=$d<1?nal_t("{nal_expires_today}"):($d==1?nal_t("{nal_in_day1}"):nal_tn("{nal_in_days}",$d)); $tone=$d>30?"ok":($d>7?"warn":"err"); }
        $exp="<span class='nal-mono'>".date("Y-m-d",$m["final"])."</span> <span class='nal-chip nal-chip-$tone'>".nal_h($rel)."</span>";
    }else{
        $exp=$dash;
    }
    $f[]=nal_field("fas fa-calendar-xmark","{nal_expire_date}",$exp,"{nal_expire_help}");
    $chk=$dash;
    if($m["checked"]>0){
        $chk="<span>".nal_ago($m["checked"])."</span> <span class='nal-sub'>(".date("Y-m-d H:i",$m["checked"]).")</span>";
    }
    $f[]=nal_field("fas fa-clock-rotate-left","{nal_last_update}",$chk,"{nal_last_update_help}");
    return "<section class='nal-grid'>".@implode("",$f)."</section>";
}

// Fraicheur : le noeud remonte toutes les 5 minutes ; au-dela de 15 minutes, la licence
// affichee peut etre perimee.
function nal_fresh(array $m):string{
    if($m["last_seen"]<=0){
        return "<div class='nal-note nal-note-warn'><i class='fas fa-plug-circle-xmark'></i> <span>{nal_never_seen}</span></div>";
    }
    $age=time()-$m["last_seen"];
    if($age>900){
        return "<div class='nal-note nal-note-warn'><i class='fas fa-plug-circle-xmark'></i> <span>"
            .nal_tn("{nal_stale}","<b>".nal_ago($m["last_seen"])."</b>")."</span></div>";
    }
    return "<div class='nal-note'><i class='fas fa-circle-info'></i> <span>"
        .nal_tn("{nal_fresh}","<b>".nal_ago($m["last_seen"])."</b>")."</span></div>";
}

// Ce qui se passe a l'expiration : trois etapes, l'etape courante marquee. Ouvert d'office
// pendant la grace et apres l'expiration ; absent pour Gold et Community.
function nal_lifecycle(array $m):string{
    $k=$m["kind"];
    if($k==="gold" || $k==="community"){ return ""; }
    // Etape marquee : la prochaine (expire bientot) ou celle ou se trouve le noeud (grace,
    // expiree). Une licence refusee n'est sur aucune etape de cette chronologie.
    $cur=array("active"=>0,"soon"=>1,"grace"=>2,"expired"=>3,"invalid"=>0);
    $c=$cur[$k];
    $s1=$m["final"]>0?nal_tn("{nal_step1_text}","<b>".date("Y-m-d",$m["final"])."</b>"):nal_t("{nal_step1_text_nodate}");
    $steps=array(
        array("fas fa-calendar-xmark","{nal_step1}",$s1),
        array("fas fa-hourglass-half","{nal_step2}","{nal_step2_text}"),
        array("fas fa-users","{nal_step3}","{nal_step3_text}"),
    );
    $h=array();
    $h[]="<details class='nal-card nal-life'".($c>=2?" open":"")."><summary><i class='fas fa-route'></i> <span>{nal_lifecycle}</span><i class='fas fa-chevron-down nal-chev'></i></summary>";
    $h[]="<ol class='nal-steps'>";
    foreach($steps as $i=>$s){
        $n=$i+1;
        $here=($n==$c);
        $cls=$n<$c?"nal-st-done":($here?"nal-st-here":"");
        $tag=$here?"<span class='nal-here'>".($c==1?"{nal_next}":"{nal_now}")."</span>":"";
        $h[]="<li class='$cls'><span class='nal-st-ico nal-st-$n'><i class='{$s[0]}'></i></span><div class='nal-st-t'>{$s[1]}</div><div class='nal-st-s'>{$s[2]}</div>$tag</li>";
    }
    $h[]="</ol></details>";
    return @implode("",$h);
}

function nal_style():string{
    $A="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$A=$a["accent"];}
    }
    return "<style>
.nal,.nal *{box-sizing:border-box}
.nal{color:#0f172a;padding:4px 2px}
.nal-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.05)}
.nal-hero{display:flex;align-items:center;gap:18px;padding:22px 24px;margin-bottom:16px}
.nal-tile{width:56px;height:56px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;font-size:24px;flex:0 0 auto;border:1px solid}
.nal-k-gold .nal-tile,.nal-k-active .nal-tile,.nal-k-soon .nal-tile,.nal-k-grace .nal-tile{background:#fef3c7;border-color:#fde68a;color:#b45309}
.nal-k-expired .nal-tile,.nal-k-invalid .nal-tile{background:#fef2f2;border-color:#fecaca;color:#b91c1c}
.nal-k-community .nal-tile{background:#f1f5f9;border-color:#e2e8f0;color:#475569}
.nal-hero-b{flex:1 1 auto;min-width:0}
.nal-ed{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.nal-ed-n{font-size:22px;font-weight:700;line-height:28px}
.nal-ed-s{font-size:13px;line-height:20px;color:#3d4947;margin-top:6px;max-width:620px}
.nal-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 12px;border-radius:999px;font-size:13px;font-weight:600;border:1px solid}
.nal-pill-ok{background:#ecfdf5;border-color:#a7f3d0;color:#047857}
.nal-pill-warn{background:#fffbeb;border-color:#fcd34d;color:#92400e}
.nal-pill-err{background:#fef2f2;border-color:#fecaca;color:#b91c1c}
.nal-left{flex:0 0 auto;min-width:260px;border-left:1px solid #e2e8f0;padding-left:24px}
.nal-left-n{font-size:22px;font-weight:700;line-height:30px;white-space:nowrap}
.nal-left-d{font-size:13px;color:#3d4947;margin-top:6px}
.nal-c-ok{color:#047857}.nal-c-warn{color:#b45309}.nal-c-err{color:#b91c1c}.nal-c-gold{color:#b45309;font-size:20px}.nal-c-mute{color:#94a3b8}
.nal-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px}
.nal-f{padding:16px 20px;min-width:0}
.nal-f-l{font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#64748b}
.nal-f-l i{width:16px;text-align:center;margin-right:4px}
.nal-f-v{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:15px;font-weight:600;margin:10px 0 8px;min-height:30px;overflow-wrap:anywhere}
.nal-help{font-size:12px;line-height:18px;color:#64748b}
.nal-key{font-family:monospace;font-size:14px;font-weight:600;color:#0f172a;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:4px 10px}
.nal-copy{margin-left:auto;display:inline-flex;align-items:center;gap:6px;border:1px solid #cbd5e1;background:#fff;border-radius:8px;padding:5px 12px;font-size:13px;color:#0f172a;cursor:pointer}
.nal-copy:hover{border-color:$A;color:$A}
.nal-copy:focus-visible,.nal-life summary:focus-visible{outline:2px solid $A;outline-offset:2px}
.nal-mono{font-family:monospace}
.nal-sub{font-size:13px;font-weight:400;color:#64748b}
.nal-chip{font-size:13px;font-weight:600;padding:2px 8px;border-radius:6px;border:1px solid}
.nal-chip-ok{background:#ecfdf5;border-color:#a7f3d0;color:#047857}
.nal-chip-warn{background:#fffbeb;border-color:#fcd34d;color:#92400e}
.nal-chip-err{background:#fef2f2;border-color:#fecaca;color:#b91c1c}
.nal-note{display:flex;align-items:center;gap:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 16px;font-size:13px;color:#334155;margin-bottom:16px}
.nal-note i{color:#64748b}
.nal-note-warn{background:#fffbeb;border-color:#fcd34d;color:#78350f}
.nal-note-warn i{color:#b45309}
.nal-life{padding:0;margin-bottom:6px}
.nal-life summary{display:flex;align-items:center;gap:10px;padding:14px 20px;font-size:14px;font-weight:600;cursor:pointer;list-style:none}
.nal-life summary::-webkit-details-marker{display:none}
.nal-chev{margin-left:auto;color:#64748b;transition:transform .15s}
.nal-life[open] .nal-chev{transform:rotate(180deg)}
.nal-steps{list-style:none;margin:0;padding:20px 24px 22px;border-top:1px solid #e2e8f0;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;position:relative}
.nal-steps li{text-align:center;position:relative}
.nal-st-ico{width:44px;height:44px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:17px;border:2px solid #cbd5e1;background:#fff;color:#64748b}
.nal-st-1{border-color:#f59e0b;color:#b45309}.nal-st-2{border-color:#d97706;color:#b45309}.nal-st-3{border-color:#94a3b8;color:#475569}
.nal-st-t{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-top:10px}
.nal-st-s{font-size:13px;line-height:19px;color:#3d4947;margin-top:4px}
.nal-st-done{opacity:.6}
.nal-st-here .nal-st-ico{box-shadow:0 0 0 4px rgba(217,119,6,.18)}
.nal-here{display:inline-block;margin-top:8px;font-size:12px;font-weight:700;padding:2px 10px;border-radius:999px;background:#fffbeb;border:1px solid #fcd34d;color:#92400e}
.nal-empty{text-align:center;padding:48px 24px}
.nal-empty i{font-size:40px;color:#94a3b8}
.nal-empty-t{font-size:18px;font-weight:700;margin-top:14px}
.nal-empty-s{font-size:13px;line-height:20px;color:#3d4947;margin-top:6px}
@media(prefers-reduced-motion:reduce){.nal-chev{transition:none}}
@media(max-width:1200px){.nal-grid{grid-template-columns:minmax(0,1fr)}.nal-hero{flex-wrap:wrap}.nal-left{flex:1 1 100%;border-left:0;padding-left:74px}}
</style>";
}

// Copier le numero : presse-papiers moderne, repli execCommand (console en http).
function nal_js_copy($id):string{
    $ok=json_encode(nal_t("{nal_copied}"));
    return "<script>
(function(){
var b=document.querySelector('.nal-copy[data-key=\"nal-key-$id\"]'); if(!b){return;}
b.onclick=function(){
 var el=document.getElementById(b.getAttribute('data-key')); if(!el){return false;}
 var txt=el.textContent, lab=b.getElementsByTagName('span')[0], old=lab?lab.textContent:'';
 var done=function(){ if(lab){ lab.textContent=$ok; setTimeout(function(){ lab.textContent=old; },1800); } };
 var fallback=function(){ var t=document.createElement('textarea'); t.value=txt; t.style.position='fixed'; t.style.left='-9999px';
  document.body.appendChild(t); t.select(); try{ document.execCommand('copy'); done(); }catch(e){} document.body.removeChild(t); };
 if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(txt).then(done,fallback); } else { fallback(); }
 return false;
};
})();
</script>";
}

function nal_popup(){
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $m=nal_model($id);
    $h=array();
    $h[]="<div class='nal'>";
    if($m["error"] || $m["none"]){
        $h[]="<div class='nal-card nal-empty'><i class='fas fa-plug-circle-xmark'></i>"
            ."<div class='nal-empty-t'>{nal_none_title}</div><div class='nal-empty-s'>{nal_none_text}</div></div>";
        $h[]="</div>";
        echo nal_style().$tpl->_ENGINE_parse_body(@implode("",$h));
        return;
    }
    $h[]=nal_hero($m);
    $h[]=nal_details($m);
    $h[]=nal_fresh($m);
    $h[]=nal_lifecycle($m);
    $h[]="</div>";
    echo nal_style().$tpl->_ENGINE_parse_body(@implode("",$h)).nal_js_copy($id);
}
