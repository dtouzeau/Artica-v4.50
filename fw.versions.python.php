<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsSystemAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["list"])){pyinv_list();exit;}
page();

// Onglet « Python packages » de fw.versions.php (pas d'en-tete ni de TINY
// propres : ceux du centre de paquets restent en place).
//
// La liste vient de GET /system/python/packages (articarest, paquet pythoninv),
// qui relit les metadonnees des modules a chaque appel. L'ancienne base
// python-packages.db, construite par un exec.pip.php disparu, n'est plus lue.
function page(){
    $page=CurrentPageName();
    echo "<div id='pyinv-host'></div>";
    echo "<script>function PyInvRefresh(){LoadAjax('pyinv-host','$page?list=yes');}\nPyInvRefresh();</script>";
}

// ---------------------------------------------------------------------------
// Modele : un seul appel au daemon, lu par tous les elements de la page.
//   state   ok | old (le daemon ne connait pas la route) | err (pas de reponse)
//   rows    name, version, summary, python
//   pythons interpretes distincts rencontres ("2", "3")
function pyinv_model():array{
    $M=array("state"=>"err","rows"=>array(),"scanned_at"=>0,"pythons"=>array());
    $raw=(string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/python/packages");
    $json=json_decode($raw,true);
    if(!is_array($json)){
        // Reponse non JSON et non vide : « Not Found » d'un articarest plus ancien.
        if(trim($raw)<>""){$M["state"]="old";}
        return $M;
    }
    if(empty($json["Status"])){return $M;}
    $M["state"]="ok";
    $M["scanned_at"]=intval(isset($json["scanned_at"])?$json["scanned_at"]:0);
    if(!isset($json["packages"]) || !is_array($json["packages"])){return $M;}
    foreach($json["packages"] as $p){
        if(!is_array($p)){continue;}
        $name=trim((string)(isset($p["name"])?$p["name"]:""));
        if($name==""){continue;}
        $python=trim((string)(isset($p["python"])?$p["python"]:""));
        if($python<>""){$M["pythons"][$python]=true;}
        $M["rows"][]=array(
            "name"=>$name,
            "version"=>trim((string)(isset($p["version"])?$p["version"]:"")),
            "summary"=>trim((string)(isset($p["summary"])?$p["summary"]:"")),
            "python"=>$python,
        );
    }
    return $M;
}

// Texte traduit pour une chaine JavaScript ou un attribut : sans balise, sans
// entite HTML (la base de traduction stocke les accents en entites).
function pyinv_plain($token):string{
    $tpl=new template_admin();
    $t=$tpl->_ENGINE_parse_body($token);
    $t=preg_replace('#<br\s*/?>#i'," ",$t);
    return trim(html_entity_decode(strip_tags($t),ENT_QUOTES,"UTF-8"));
}

// ---------------------------------------------------------------------------
// Rendu. Une fonction = un element affiche.

function pyinv_style():string{
    return "<style>
.pyinv{max-width:1320px;margin:10px 0 24px}
.pyinv-hero{display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:16px}
.pyinv-hero-main{display:flex;align-items:flex-start;gap:16px;min-width:0}
.pyinv-hero h2{margin:0 0 8px;font-size:20px;font-weight:600;line-height:1.25;color:#0f172a}
.pyinv-hero p{margin:0;font-size:14px;line-height:1.6;color:#475569}
.pyinv-chips{margin-top:12px}
.pyinv-chips .ak-pill{font-weight:500;background:#f1f5f9;color:#334155}
.pyinv-side{flex:0 0 300px;padding-left:28px;border-left:1px solid #e2e8f0}
.pyinv-total{display:flex;align-items:baseline;gap:8px}
.pyinv-total b{font-size:28px;font-weight:700;line-height:1;color:#0f172a;font-variant-numeric:tabular-nums}
.pyinv-total span,.pyinv-when{font-size:13px;color:#64748b}
.pyinv-when{display:flex;align-items:center;gap:6px;margin:8px 0 14px}
.pyinv-hero p.pyinv-help{margin:10px 0 0;font-size:13px;line-height:1.45;color:#64748b}
.pyinv-tools{justify-content:space-between}
.pyinv-tools .ak-search{flex:0 1 380px}
.pyinv-counter{padding:0 8px;font-size:13px;color:#64748b;white-space:nowrap}
.pyinv-tbl{table-layout:fixed}
.pyinv-tbl th:nth-child(1){width:30%}
.pyinv-tbl th:nth-child(2){width:15%}
.pyinv-tbl td{padding-top:12px;padding-bottom:12px}
.pyinv-tbl tbody tr:hover{background:#f8fafc}
.pyinv-mod{display:flex;align-items:center;gap:12px;min-width:0}
.pyinv-mod .ak-name{font-size:14px;color:#0f172a}
.pyinv-py{display:block;margin-top:1px;font-size:13px;color:#64748b}
.pyinv-desc{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;font-size:13px;line-height:1.55;color:#475569;overflow-wrap:anywhere}
.pyinv-nodesc{font-size:13px;font-style:italic;color:#64748b}
.pyinv-state h3{margin:6px 0 0;font-size:16px;font-weight:600;color:#0f172a}
.pyinv-state p{margin:0 0 8px;max-width:560px;font-size:14px;line-height:1.55;color:#475569}
.pyinv-state>.ak-ico{margin-bottom:4px}
@media(max-width:1200px){
.pyinv-hero{flex-direction:column;align-items:stretch}
.pyinv-side{flex:0 0 auto;padding:16px 0 0;border-left:0;border-top:1px solid #e2e8f0}
}
</style>";
}

function pyinv_chip($ico,$token):string{
    return "<span class='ak-pill'><i class='$ico'></i> $token</span>";
}

// Accroche : a quoi sert la liste, en mots simples, et trois puces vraies.
function pyinv_hero_text():string{
    $h ="<div class='pyinv-hero-main'>";
    $h.="<span class='ak-ico ak-ico-lg ak-ico-sq'><i class='fas fa-cubes'></i></span>";
    $h.="<div><h2>{pyinv_title}</h2><p>{pyinv_intro}</p>";
    $h.="<div class='ak-chips pyinv-chips'>";
    $h.=pyinv_chip("fas fa-eye","{pyinv_chip_readonly}");
    $h.=pyinv_chip("fas fa-shield-halved","{pyinv_chip_live}");
    $h.=pyinv_chip("fas fa-life-ring","{pyinv_chip_support}");
    return $h."</div></div></div>";
}

// Le chiffre, l'heure de lecture et le bouton. Affiche seulement quand le
// daemon a repondu : sans lecture, pas de « 0 module ».
function pyinv_hero_side($M):string{
    if($M["state"]<>"ok"){return "";}
    $tpl=new template_admin();
    $n=count($M["rows"]);
    $label=$n==1?"{pyinv_module_installed}":"{pyinv_modules_installed}";
    $h ="<div class='pyinv-side'>";
    $h.="<div class='pyinv-total'><b>$n</b><span>$label</span></div>";
    if($M["scanned_at"]>0){
        // Traduire d'abord, remplacer ensuite : avant l'analyse le jeton n'a pas de %s.
        $when=htmlspecialchars(str_replace("%s",date("H:i:s",$M["scanned_at"]),pyinv_plain("{pyinv_read_at}")),ENT_QUOTES);
        $full=htmlspecialchars(strip_tags((string)$tpl->time_to_date($M["scanned_at"],true)),ENT_QUOTES);
        $h.="<div class='pyinv-when' title=\"$full\"><i class='far fa-clock'></i><span>$when</span></div>";
    }
    $h.="<button type='button' class='ak-btn ak-btn-soft' onclick=\"PyInvRefresh();\"><i class='fas fa-rotate'></i> {pyinv_refresh}</button>";
    $h.="<p class='pyinv-help'>{pyinv_refresh_help}</p>";
    return $h."</div>";
}

function pyinv_hero($M):string{
    return "<div class='ak-card ak-card-pad pyinv-hero'>".pyinv_hero_text().pyinv_hero_side($M)."</div>";
}

// Etat sans liste : daemon trop ancien, daemon muet, ou aucun module trouve.
function pyinv_state($M):string{
    $ico="fas fa-cubes";$tone="";$title="{pyinv_empty_title}";$text="{pyinv_empty_text}";
    if($M["state"]=="old"){$ico="fas fa-circle-up";$tone=" ak-warn";$title="{pyinv_old_title}";$text="{pyinv_old_text}";}
    if($M["state"]=="err"){$ico="fas fa-triangle-exclamation";$tone=" ak-warn";$title="{pyinv_err_title}";$text="{pyinv_err_text}";}
    $h ="<div class='ak-card'><div class='ak-empty-c pyinv-state'>";
    $h.="<span class='ak-ico ak-ico-lg ak-ico-sq$tone'><i class='$ico'></i></span>";
    $h.="<h3>$title</h3><p>$text</p>";
    if($M["state"]<>"old"){
        $h.="<button type='button' class='ak-btn ak-btn-primary' onclick=\"PyInvRefresh();\"><i class='fas fa-rotate'></i> {pyinv_try_again}</button>";
    }
    return $h."</div></div>";
}

function pyinv_toolbar():string{
    $ph=htmlspecialchars(pyinv_plain("{pyinv_search_ph}"),ENT_QUOTES);
    $h ="<div class='ak-tools pyinv-tools'>";
    $h.="<label class='ak-search'><i class='fas fa-magnifying-glass'></i>";
    $h.="<input type='text' id='pyinv-q' autocomplete='off' placeholder=\"$ph\" aria-label=\"$ph\"></label>";
    $h.="<span class='pyinv-counter' id='pyinv-counter' aria-live='polite'></span>";
    return $h."</div>";
}

// Une ligne. Les valeurs viennent de paquets tiers : echappees, et jamais
// passees au moteur de traduction (une description contenant « {x} » serait
// prise pour un jeton). $L porte les libelles deja traduits.
function pyinv_row($r,$L,$showPython):string{
    $name=htmlspecialchars($r["name"],ENT_QUOTES);
    $q=htmlspecialchars(strtolower($r["name"]." ".$r["summary"]." ".$r["version"]),ENT_QUOTES);
    $h ="<tr data-q=\"$q\">";
    $h.="<td><div class='pyinv-mod'><span class='ak-ico ak-ico-xs ak-ico-sq'><i class='fas fa-cube'></i></span>";
    $h.="<span style='min-width:0'><span class='ak-name'>$name</span>";
    if($showPython && $r["python"]<>""){
        $h.="<span class='pyinv-py'>Python ".htmlspecialchars($r["python"],ENT_QUOTES)."</span>";
    }
    $h.="</span></div></td>";
    if($r["version"]==""){
        $h.="<td><span class='ak-muted'>&#8212;</span></td>";
    }else{
        $h.="<td><span class='ak-chip'>".htmlspecialchars($r["version"],ENT_QUOTES)."</span></td>";
    }
    if($r["summary"]==""){
        $h.="<td><span class='pyinv-nodesc'>{$L["nodesc"]}</span></td>";
    }else{
        $s=htmlspecialchars($r["summary"],ENT_QUOTES);
        $h.="<td><span class='pyinv-desc' title=\"$s\">$s</span></td>";
    }
    return $h."</tr>";
}

// Le tableau et, a sa place quand la recherche ne retient rien, l'invitation
// a corriger la recherche. __PYINV_ROWS__ est remplace APRES la traduction.
function pyinv_table():string{
    $h ="<div class='ak-card' id='pyinv-card'>";
    $h.="<table class='ak-tbl pyinv-tbl'><thead><tr>";
    $h.="<th scope='col'>{pyinv_col_module}</th><th scope='col'>{pyinv_col_version}</th><th scope='col'>{pyinv_col_what}</th>";
    $h.="</tr></thead><tbody id='pyinv-rows'>__PYINV_ROWS__</tbody></table>";
    $h.="<div class='ak-card-f' id='pyinv-foot'><span id='pyinv-range'></span><span class='ak-pg' id='pyinv-pager'></span></div>";
    $h.="</div>";
    $h.="<div class='ak-card' id='pyinv-nomatch' hidden><div class='ak-empty-c pyinv-state'>";
    $h.="<i class='fas fa-magnifying-glass'></i>";
    $h.="<h3 id='pyinv-nomatch-title'></h3><p>{pyinv_nomatch_text}</p>";
    $h.="<button type='button' class='ak-link' id='pyinv-clear'>{pyinv_clear}</button>";
    return $h."</div></div>";
}

// Recherche et pagination dans le navigateur : la liste entiere est deja la
// (une centaine de lignes), chercher ne relance pas la lecture de l'appliance.
function pyinv_js($total):string{
    $T=array(
        "all"=>pyinv_plain($total==1?"{pyinv_count_one}":"{pyinv_count_all}"),
        "filtered"=>pyinv_plain("{pyinv_count_filtered}"),
        "range"=>pyinv_plain("{pyinv_range}"),
        "nomatch"=>pyinv_plain("{pyinv_nomatch_title}"),
        "prev"=>pyinv_plain("{pyinv_prev}"),
        "next"=>pyinv_plain("{pyinv_next}"),
    );
    $js='
<script>
(function(){
 var T=__T__, SIZE=25, cur=1;
 var tb=document.getElementById("pyinv-rows"); if(!tb){return;}
 var inp=document.getElementById("pyinv-q"), card=document.getElementById("pyinv-card");
 var none=document.getElementById("pyinv-nomatch"), pager=document.getElementById("pyinv-pager");
 var all=Array.prototype.slice.call(tb.rows), kept=all;
 function fill(t,a,b,c){return t.replace("%a",a).replace("%b",b).replace("%c",c).replace("%s",a);}
 function btn(label,p,cls,aria){
   var b=document.createElement("button"); b.type="button"; b.innerHTML=label;
   if(cls){b.className=cls;} if(aria){b.setAttribute("aria-label",aria);}
   if(cls==="cur"){b.setAttribute("aria-current","page");}
   b.onclick=function(){cur=p;draw();}; return b;
 }
 function draw(){
   var n=kept.length, pages=Math.max(1,Math.ceil(n/SIZE)); if(cur>pages){cur=pages;} if(cur<1){cur=1;}
   var from=(cur-1)*SIZE, to=Math.min(n,cur*SIZE), i;
   for(i=0;i<all.length;i++){all[i].style.display="none";}
   for(i=from;i<to;i++){kept[i].style.display="";}
   var q=inp.value.trim();
   document.getElementById("pyinv-counter").textContent=(q==="")?fill(T.all,all.length):fill(T.filtered,n,all.length);
   card.hidden=(n===0); none.hidden=(n!==0);
   if(n===0){document.getElementById("pyinv-nomatch-title").textContent=fill(T.nomatch,q);return;}
   pager.innerHTML="";
   // Une seule page : le compteur de la barre de recherche dit deja tout.
   document.getElementById("pyinv-range").textContent=(pages<2)?"":fill(T.range,from+1,to,n);
   document.getElementById("pyinv-foot").hidden=(pages<2);
   if(pages<2){return;}
   pager.appendChild(btn("&lsaquo;",cur-1,cur===1?"off":"",T.prev));
   var end=Math.min(pages,Math.max(cur+2,5)), start=Math.max(1,end-4);
   for(var p=start;p<=end;p++){pager.appendChild(btn(String(p),p,p===cur?"cur":"",""));}
   pager.appendChild(btn("&rsaquo;",cur+1,cur===pages?"off":"",T.next));
 }
 function filter(){
   var words=inp.value.toLowerCase().split(/\s+/).filter(function(w){return w!=="";});
   kept=all.filter(function(tr){
     var hay=tr.getAttribute("data-q")||"";
     for(var i=0;i<words.length;i++){if(hay.indexOf(words[i])<0){return false;}}
     return true;
   });
   cur=1; draw();
 }
 inp.addEventListener("input",filter);
 document.getElementById("pyinv-clear").onclick=function(){inp.value="";filter();inp.focus();};
 draw();
})();
</script>';
    return str_replace("__T__",json_encode($T),$js);
}

function pyinv_list(){
    $tpl=new template_admin();
    $M=pyinv_model();
    $html=array();
    $html[]="<div class='ak pyinv'>";
    $html[]=pyinv_hero($M);

    if($M["state"]<>"ok" || count($M["rows"])==0){
        $html[]=pyinv_state($M);
        $html[]="</div>";
        echo pyinv_style().$tpl->_ENGINE_parse_body(@implode("\n",$html))."<script>NoSpinner();</script>";
        return;
    }

    $html[]=pyinv_toolbar();
    $html[]=pyinv_table();
    $html[]="</div>";

    $L=array("nodesc"=>htmlspecialchars(pyinv_plain("{pyinv_no_desc}"),ENT_QUOTES));
    $showPython=count($M["pythons"])>1;
    $trs=array();
    foreach($M["rows"] as $r){$trs[]=pyinv_row($r,$L,$showPython);}

    // CSS, lignes et JS restent HORS du moteur de traduction.
    $body=$tpl->_ENGINE_parse_body(@implode("\n",$html));
    $body=str_replace("__PYINV_ROWS__",@implode("\n",$trs),$body);
    echo pyinv_style().$body."<script>NoSpinner();</script>".pyinv_js(count($M["rows"]));
}
