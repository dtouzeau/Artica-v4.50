<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.dansguardian.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once("ressources/class.ldap-extern.inc");
include_once(dirname(__FILE__)."/ressources/class.ufdbrules.meta.inc");

if(isset($_POST["ID"])){rule_edit_save();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["tab"])){tabs();exit;}

js();

function js(){
	$page=CurrentPageName();
	$ID=isset($_GET["ID"])?intval($_GET["ID"]):-1;
	$tpl=new template_admin();
	$rulename="{new_rule}";
	if($ID==0){$rulename="{default}";}
	if($ID>0){
		$q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
		$rows=$q->QUERY_SQL_PARAMS("SELECT groupname FROM webfilter_rules WHERE ID=?",array($ID));
		$rulename=isset($rows[0]["groupname"])?(string)$rows[0]["groupname"]:"#$ID";
		// buildADialog() pose le titre dans « title: '$title' » SANS echappement, et
		// javascript_parse_text() ne neutralise les apostrophes que des jetons resolus,
		// pas d'un nom relu en base. htmlspecialchars(ENT_QUOTES) traite le balisage ET
		// l'apostrophe; l'antislash, qu'il laisse passer, ouvrirait une sequence dans ce
		// meme litteral et casserait l'ouverture de la modale sans un mot a l'ecran.
		$rulename=str_replace("\\","",htmlspecialchars($rulename,ENT_QUOTES));
	}
	$tpl->js_dialog($rulename,"$page?tab=$ID");
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	// L'identifiant part dans les URL des onglets, donc dans des attributs HTML et du
	// JavaScript construits par tabs_default(). C'est un entier: on le convertit.
	$ID=isset($_GET["tab"])?intval($_GET["tab"]):-1;
	$array=array();
	$array["{rule}"]="$page?popup=$ID";
	if($ID>-1){
		$array["{blacklists}"]="fw.ufdb.rules.categories.php?ID=$ID&modeblk=0";
		$array["{whitelists}"]="fw.ufdb.rules.categories.php?ID=$ID&modeblk=1";
		$array["{period}"]="fw.ufdb.rules.time.php?ID=$ID";
	}
	echo $tpl->tabs_default($array);
}

/**
 * ufdbre_css — la feuille de l'editeur de regle.
 *
 * Meme langage visuel que fw.ufdb.rules.time.php (design « Vigil Sentinel »):
 * les deux vivent dans LA MEME modale, comme deux onglets l'un de l'autre. Une regle
 * rendue en form_outside a cote d'un planificateur en cartes blanches a 1px, c'etait
 * deux interfaces differentes dans une seule fenetre.
 *
 * A CONCATENER APRES _ENGINE_parse_body(): son motif #{(.+?)}# lirait chaque corps de
 * regle CSS comme un {token} et declencherait une recherche memcached par regle.
 */
function ufdbre_css(){
    if(!empty($GLOBALS["UFDBRE_CSS_DONE"])){return "";}
    $GLOBALS["UFDBRE_CSS_DONE"]=true;
    return "<style>
.ufdbre{color:#0f172a;font-size:13px}
.ufdbre *{box-sizing:border-box}
.ufdbre>*+*{margin-top:16px}
.ufdbre-note{display:flex;gap:12px;align-items:flex-start;border-radius:12px;padding:14px 16px}
.ufdbre-note-i{font-size:17px;line-height:20px;flex:0 0 auto}
.ufdbre-note-t{font-weight:600;margin:0 0 3px}
.ufdbre-note-b{line-height:1.55;margin:0}
.ufdbre-note-info{background:linear-gradient(90deg,#f0f9ff,rgba(239,246,255,.6));border:1px solid rgba(186,230,253,.8);color:#0c4a6e}
.ufdbre-note-info .ufdbre-note-i{color:#0284c7}
.ufdbre-note-info .ufdbre-note-t{color:#082f49}
.ufdbre-note-warn{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239}
.ufdbre-note-warn .ufdbre-note-i{color:#e11d48}
.ufdbre-note-warn .ufdbre-note-t{color:#881337}
.ufdbre-card{background:rgba(248,250,252,.7);border:1px solid rgba(226,232,240,.9);border-radius:12px;padding:18px}
.ufdbre-h{display:flex;align-items:center;gap:8px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b;margin:0 0 14px}
.ufdbre-grid{display:flex;flex-wrap:wrap;gap:16px}
.ufdbre-f{flex:1 1 240px;min-width:0}
.ufdbre-f-sm{flex:0 0 130px}
.ufdbre-lab{display:block;font-size:13px;font-weight:500;color:#334155;margin:0 0 6px}
.ufdbre-hint{font-size:12px;color:#64748b;margin:4px 0 0}
.ufdbre-in,.ufdbre-sel{width:100%;background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:9px 12px;font-size:13px;color:#1e293b;box-shadow:0 1px 2px rgba(15,23,42,.04);outline:0}
.ufdbre-in:focus,.ufdbre-sel:focus{border-color:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,.2)}
.ufdbre-in[readonly]{background:#f1f5f9;color:#64748b}
.ufdbre-toggles{display:flex;flex-wrap:wrap;gap:10px 24px;margin-top:14px}
.ufdbre-tg{display:inline-flex;align-items:flex-start;gap:9px;cursor:pointer;line-height:18px}
.ufdbre-tg input{width:16px;height:16px;margin:1px 0 0;accent-color:#0f172a;cursor:pointer;flex:0 0 auto}
.ufdbre-tg span{font-size:13px;color:#334155}
.ufdbre-right{display:flex;justify-content:flex-end;gap:8px;margin-top:4px}
.ufdbre-bt{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:8px;font-size:13px;font-weight:600;padding:9px 18px;color:#fff!important;background:#059669;text-decoration:none!important;cursor:pointer;box-shadow:0 1px 3px rgba(15,23,42,.1)}
.ufdbre-bt:hover{background:#047857}
.ufdbre-err{display:none;margin-top:4px}
@media(max-width:640px){.ufdbre-f,.ufdbre-f-sm{flex:1 1 100%}}
</style>";
}

/** ufdbre_note — un encart d'information ou d'avertissement. */
function ufdbre_note($kind,$title,$body){
    $ico=($kind=="warn")?"fa-triangle-exclamation":"fa-circle-info";
    $h ="<div class='ufdbre-note ufdbre-note-$kind'>";
    $h.="<div class='ufdbre-note-i'><i class='fas $ico'></i></div><div>";
    if($title!==""){ $h.="<p class='ufdbre-note-t'>$title</p>"; }
    $h.="<p class='ufdbre-note-b'>$body</p></div></div>";
    return $h;
}

/** ufdbre_select — une liste deroulante de l'editeur. */
function ufdbre_select($id,$options,$selected){
    $h="<select class='ufdbre-sel' id='".htmlspecialchars($id)."'>";
    foreach($options as $v=>$label){
        // Comparaison en CHAINE: "none" compare de facon lache a 0 vaut vrai en PHP 7,
        // et l'option "aucune" ressortait selectionnee a la place de la vraie.
        $sel=((string)$v===(string)$selected)?" selected":"";
        $h.="<option value='".htmlspecialchars((string)$v)."'$sel>".$label."</option>";
    }
    return $h."</select>";
}

/** ufdbre_toggle — une case a cocher legendee. */
function ufdbre_toggle($id,$label,$checked){
    return "<label class='ufdbre-tg'><input type='checkbox' id='".htmlspecialchars($id)."'"
        .($checked?" checked":"")."><span>$label</span></label>";
}

/** ufdbre_identity_card — nom, rang et portee de la regle. */
function ufdbre_identity_card($ligne,$AsDefaultRule,$DefaultPosition){
    $h ="<section class='ufdbre-card'>";
    $h.="<h2 class='ufdbre-h'><i class='fas fa-tag'></i> {identity}</h2>";
    $h.="<div class='ufdbre-grid'>";
    if($AsDefaultRule){
        // La regle par defaut n'a ni nom ni activation: elle s'applique toujours, et
        // seule sa POSITION dans la pile se choisit.
        $h.="<div class='ufdbre-f'><label class='ufdbre-lab'>{rule_name}</label>"
           ."<input class='ufdbre-in' type='text' value=\"{default}\" readonly>"
           ."<p class='ufdbre-hint'>{ufdb_explain_default_rule}</p></div>";
        $h.="<div class='ufdbre-f'><label class='ufdbre-lab' for='ufdbre-pos'>{position}</label>"
           .ufdbre_select("ufdbre-pos",array(0=>"{at_the_top_rules}",1=>"{at_the_end_of_rules}"),$DefaultPosition)
           ."</div>";
        $h.="</div></section>";
        return $h;
    }
    $h.="<div class='ufdbre-f'><label class='ufdbre-lab' for='ufdbre-name'>{rule_name}</label>"
       ."<input class='ufdbre-in' type='text' id='ufdbre-name' value=\""
       .htmlspecialchars((string)$ligne["groupname"],ENT_QUOTES)."\">"
       // Le nom saisi est normalise a l'enregistrement (minuscules, accents retires,
       // ponctuation supprimee): il sert d'identifiant a ufdbGuard. L'ancien formulaire
       // le reecrivait sans le dire, et l'operateur ne le decouvrait qu'en relisant.
       ."<p class='ufdbre-hint'>{rule_name_normalized_explain}</p></div>";
    $h.="<div class='ufdbre-f ufdbre-f-sm'><label class='ufdbre-lab' for='ufdbre-order'>{order}</label>"
       ."<input class='ufdbre-in' type='number' min='0' id='ufdbre-order' value='"
       .intval($ligne["zOrder"])."'></div>";
    $h.="</div>";
    $h.="<div class='ufdbre-toggles'>"
       .ufdbre_toggle("ufdbre-enabled","{enabled}",intval($ligne["enabled"])==1)
       .ufdbre_toggle("ufdbre-allsystems","{AllSystems}",intval($ligne["AllSystems"])==1)
       ."</div>";
    $h.="</section>";
    return $h;
}

/** ufdbre_behavior_card — ce que la regle fait du trafic qu'elle capte. */
function ufdbre_behavior_card($ligne){
    $groupmode=array(0=>"{banned}",1=>"{filtered}",2=>"{exception}");
    $ENDOFRULES=array("any"=>"{ufdb_any}","none"=>"{ufdb_none}");
    $h ="<section class='ufdbre-card'>";
    $h.="<h2 class='ufdbre-h'><i class='fas fa-shield-halved'></i> {rule_behavior}</h2>";
    $h.="<div class='ufdbre-grid'>";
    $h.="<div class='ufdbre-f'><label class='ufdbre-lab' for='ufdbre-mode'>{rule_behavior}</label>"
       .ufdbre_select("ufdbre-mode",$groupmode,intval($ligne["groupmode"]))
       ."<p class='ufdbre-hint'>{rule_behavior_explain}</p></div>";
    $h.="<div class='ufdbre-f'><label class='ufdbre-lab' for='ufdbre-end'>{finish_rule_by}</label>"
       .ufdbre_select("ufdbre-end",$ENDOFRULES,(string)$ligne["endofrule"])
       ."<p class='ufdbre-hint'>{finish_rule_by_explain}</p></div>";
    $h.="</div></section>";
    return $h;
}
/**
 * ufdbre_js — la sauvegarde du formulaire.
 *
 * Ecrite a la main plutot que par form_outside() parce que la maquette de cet onglet
 * n'est pas celle de form_outside. Le CONTRAT, lui, reste celui de la console:
 * une reponse de 3 caracteres ou moins vaut succes, tout le reste EST le message
 * d'erreur. Le controle de privilege est fait cote SERVEUR dans rule_edit_save(),
 * pas par la simple absence du bouton.
 *
 * A CONCATENER APRES _ENGINE_parse_body(): les accolades du JavaScript seraient lues
 * comme des jetons de traduction.
 */
function ufdbre_js($ID,$AsDefaultRule,$closeOnSuccess){
    $page=CurrentPageName();
    $ID=intval($ID);
    $tpl=new template_admin();
    $err=json_encode($tpl->javascript_parse_text("{error}"));
    $ok =json_encode($tpl->javascript_parse_text("{ok}"));

    $js ="<script>\n";
    $js.="function UfdbReSave(){\n";
    $js.="  var p={'ID':'$ID'};\n";
    if($AsDefaultRule){
        $js.="  p['defaultPosition']=document.getElementById('ufdbre-pos').value;\n";
    }else{
        $js.="  var n=document.getElementById('ufdbre-name').value.replace(/^\\s+|\\s+$/g,'');\n";
        $js.="  if(n===''){ swal({title:".json_encode($tpl->javascript_parse_text("{rule_name}")).",type:'warning',"
            ."confirmButtonColor:'#1c84c6',confirmButtonText:$ok}); return; }\n";
        $js.="  p['groupname']=n;\n";
        $js.="  p['zOrder']=document.getElementById('ufdbre-order').value;\n";
        // Les cases doivent partir en 0/1: non cochee, une case HTML n'est tout
        // simplement pas envoyee, et la colonne restait a son ancienne valeur --
        // decocher « active » ne desactivait donc jamais rien.
        $js.="  p['enabled']=document.getElementById('ufdbre-enabled').checked?'1':'0';\n";
        $js.="  p['AllSystems']=document.getElementById('ufdbre-allsystems').checked?'1':'0';\n";
    }
    $js.="  p['groupmode']=document.getElementById('ufdbre-mode').value;\n";
    $js.="  p['endofrule']=document.getElementById('ufdbre-end').value;\n";
    $js.="  $.post('$page',p,function(r){\n";
    $js.="    if(r && r.length>3){ swal({title:$err,text:r,type:'error',"
        ."confirmButtonColor:'#ed5565',confirmButtonText:$ok}); return; }\n";
    $js.="    if(document.getElementById('table-loader-ufdbrules-service')){\n";
    $js.="      LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');}\n";
    if($closeOnSuccess){ $js.="    BootstrapDialog1.close();\n"; }
    $js.="  });\n";
    $js.="}\n";
    $js.="</script>";
    return $js;
}

function popup(){
    $ID=isset($_GET["popup"])?intval($_GET["popup"]):-1;
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;

    $AsDefaultRule=false;
    $DefaultPosition=0;
    $ligne=array();

    if($ID>0){
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $rows=$q->QUERY_SQL_PARAMS("SELECT * FROM webfilter_rules WHERE ID=?",array($ID));
        $ligne=isset($rows[0])?$rows[0]:array();
    }elseif($ID==0){
        $ligne=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DansGuardianDefaultMainRule"));
        if(!is_array($ligne)){ $ligne=array(); }
        $ligne["groupname"]="default";
        // defaultPosition n'existe pas sur une installation neuve: y acceder sans garde
        // levait un avertissement PHP 8 avant meme d'afficher le formulaire.
        $DefaultPosition=isset($ligne["defaultPosition"])?intval($ligne["defaultPosition"]):0;
        $AsDefaultRule=true;
    }else{
        $ligne["groupname"]="Rule_".time();
        $ligne["enabled"]=1;
        // Une regle locale se place apres les regles Artica Meta (premiers rangs).
        $metaMax=ufdbrules_meta_max_zorder();
        if($metaMax!==null && $metaMax>=0){ $ligne["zOrder"]=$metaMax+1; }
    }
    // Regle ecrite par Artica Meta (ou regle par defaut tenue) : affichee, jamais enregistrable.
    $metaLocked=ufdbrules_meta_locked_rule($ID);

    // Valeurs par defaut. Un seul passage: l'ancienne version testait isset() puis
    // is_numeric() sur les memes cles, si bien qu'une valeur non numerique deja posee
    // par isset() passait a travers la seconde garde selon l'ordre des lignes.
    $defaults=array("endofrule"=>"any","groupmode"=>1,"naughtynesslimit"=>50,"embeddedurlweight"=>0,
        "GoogleSafeSearch"=>0,"UseExternalWebPage"=>0,"UseSecurity"=>0,"enabled"=>1,
        "searchtermlimit"=>30,"bypass"=>0,"zOrder"=>0,"AllSystems"=>0);
    foreach($defaults as $k=>$v){
        if(!isset($ligne[$k])){ $ligne[$k]=$v; continue; }
        if(is_numeric($v) && !is_numeric($ligne[$k])){ $ligne[$k]=$v; }
    }
    if($ligne["endofrule"]===null || $ligne["endofrule"]===""){ $ligne["endofrule"]="any"; }

    $html=array();
    $html[]="<div class='ufdbre' style='margin-top:10px'>";
    if($metaLocked){
        $html[]=ufdbrules_meta_banner($tpl,($ID==0)?"default":$ID);
    }
    if(intval($ligne["groupmode"])==0){
        $html[]=ufdbre_note("warn","{navigation_banned}","{navigation_banned_text}");
    }
    if(intval($ligne["AllSystems"])==1){
        $html[]=ufdbre_note("info","{AllSystems}","{AllSystemsDansExpl}");
    }
    $html[]=ufdbre_identity_card($ligne,$AsDefaultRule,$DefaultPosition);
    $html[]=ufdbre_behavior_card($ligne);
    if(!$metaLocked){
        $html[]="<div class='ufdbre-right'><a class='ufdbre-bt' href=\"javascript:blur();\" OnClick=\"UfdbReSave()\">"
            ."<i class='fas fa-check'></i> ".($ID<0?"{add}":"{apply}")."</a></div>";
    }
    $html[]="</div>";

    echo ufdbre_css()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .ufdbre_js($ID,$AsDefaultRule,($ID<0));
}
/**
 * ufdbre_normalize_name — le nom devient un identifiant ufdbGuard.
 *
 * Minuscules, accents retires, ponctuation supprimee: c'est ce que le compilateur de
 * regles attend. La liste de caracteres etait ecrite en 23 str_replace successifs et
 * elle OUBLIAIT l'apostrophe -- precisement le caractere qui refermait le litteral SQL
 * quelques lignes plus bas. Une liste BLANCHE dit ce qui est permis au lieu d'enumerer
 * sans fin ce qui ne l'est pas.
 */
function ufdbre_normalize_name($name){
    $name=replace_accents(trim((string)$name));
    $name=ufdbre_lower($name);
    $name=str_replace(array("\\","/","+","="),"_",$name);
    // Lettres et chiffres de TOUTE ecriture, plus l'espace, le point, le tiret et le
    // souligne. Une liste ASCII aurait efface le « l » barre d'un « szkola » polonais,
    // que l'ancienne liste noire, elle, conservait: on ne remplace pas une desinfection
    // trop laxiste par une desinfection qui perd des caracteres en silence. Les valeurs
    // partent en parametres lies, donc l'Unicode ne presente plus aucun risque SQL.
    $clean=@preg_replace('#[^\p{L}\p{N} ._\-]#u','',$name);
    // preg_replace rend null si PCRE n'a pas le support UTF-8 ou si l'entree n'est pas
    // de l'UTF-8 valide: on retombe alors sur l'ASCII plutot que sur une chaine vide.
    if($clean===null){ $clean=preg_replace('#[^a-zA-Z0-9 ._\-]#','',$name); }
    $clean=preg_replace('#\s+#',' ',$clean);
    return trim((string)$clean);
}

/** ufdbre_lower — minuscules, mbstring present ou non (un appel nu est une fatale). */
function ufdbre_lower($s){
    if(function_exists("mb_strtolower")){ return mb_strtolower($s,"UTF-8"); }
    return strtolower($s);
}

function rule_edit_save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    // Cacher le bouton n'est pas un controle: ce POST repond a toute session
    // authentifiee, quelle que soit l'interface qu'on lui a montree.
    if(!$users->AsDansGuardianAdministrator){ echo "{access_denied}"; return false; }

    $ID=intval($_POST["ID"]);
    // Verrou Artica Meta, relu cote serveur : regle Meta (ou regle par defaut tenue) en lecture
    // seule ; une regle locale ne se place pas avant une regle Meta.
    if(ufdbrules_meta_locked_rule($ID)){ return ufdbrules_meta_refuse_text(); }
    if($ID<0 && !isset($_POST["zOrder"])){
        $metaMax=ufdbrules_meta_max_zorder();
        if($metaMax===null){ return ufdbrules_meta_refuse_text(); }
        if($metaMax>=0){ $_POST["zOrder"]=$metaMax+1; }
    }
    if($ID!=0 && isset($_POST["zOrder"]) && ufdbrules_meta_zorder_refused($_POST["zOrder"])){
        return ufdbrules_meta_refuse_text("{ufdbrules_meta_move_above}");
    }
    $tpl->CLEAN_POST();

    // LISTE BLANCHE de colonnes, avec leur type. L'ancienne version construisait le SQL
    // en parcourant $_POST: le NOM DE COLONNE venait de la cle du POST et la valeur
    // n'etait pas echappee (utf8_encode() convertit un encodage, il n'echappe rien).
    // Un groupname contenant une apostrophe -- que la desinfection ne retirait pas --
    // refermait le litteral et poursuivait la requete. Ici les noms de colonnes sont
    // des litteraux de ce tableau, jamais quelque chose que le navigateur a envoye.
    $columns=array(
        "groupname"  =>"text",
        "enabled"    =>"int",
        "zOrder"     =>"int",
        "AllSystems" =>"int",
        "groupmode"  =>"int",
        "endofrule"  =>"enum:any,none",
    );

    $values=array();
    foreach($columns as $col=>$type){
        if(!isset($_POST[$col])){ continue; }
        $v=$_POST[$col];
        if($type=="int"){ $values[$col]=intval($v); continue; }
        if(strpos($type,"enum:")===0){
            $allowed=explode(",",substr($type,5));
            $values[$col]=in_array((string)$v,$allowed,true)?(string)$v:$allowed[0];
            continue;
        }
        $values[$col]=(string)$v;
    }

    if(isset($values["groupname"])){
        $values["groupname"]=ufdbre_normalize_name($values["groupname"]);
        // « default » est reserve a la regle 0: une regle nommee ainsi la masquerait
        // dans toutes les recherches par nom.
        if($values["groupname"]==="default" || $values["groupname"]===""){
            $values["groupname"]="rule_".time();
        }
    }

    // ---- Regle par defaut: un reglage, pas une ligne de table -------------------
    if($ID==0){
        $DEFAULTARRAY=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DansGuardianDefaultMainRule"));
        if(!is_array($DEFAULTARRAY)){ $DEFAULTARRAY=array(); }
        foreach($values as $k=>$v){ $DEFAULTARRAY[$k]=$v; }
        if(isset($_POST["defaultPosition"])){
            $DEFAULTARRAY["defaultPosition"]=(intval($_POST["defaultPosition"])==1)?1:0;
        }
        $DEFAULTARRAY["groupname"]="default";
        $DEFAULTARRAY["enabled"]=1;
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(base64_encode(serialize($DEFAULTARRAY)),"DansGuardianDefaultMainRule");
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
        return admin_tracks("Saving Web-filter rule default");
    }

    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");

    // Nom deja pris: on suffixe. Le comptage sert de suffixe, donc il doit exclure la
    // regle qu'on est en train d'enregistrer.
    if(isset($values["groupname"])){
        $rows=$q->QUERY_SQL_PARAMS("SELECT ID FROM webfilter_rules WHERE groupname=? AND ID<>?",
            array($values["groupname"],$ID));
        $n=is_array($rows)?count($rows):0;
        if($n>0){ $values["groupname"]=$values["groupname"]."_".($n+1); }
    }
    if(count($values)==0){ echo "{error}"; return false; }

    $cols=array_keys($values);
    $params=array_values($values);
    if($ID<0){
        $sql="INSERT INTO webfilter_rules (`".implode("`,`",$cols)."`) VALUES ("
            .implode(",",array_fill(0,count($cols),"?")).")";
    }else{
        $set=array();
        foreach($cols as $c){ $set[]="`$c`=?"; }
        $sql="UPDATE webfilter_rules SET ".implode(",",$set)." WHERE ID=?";
        $params[]=$ID;
    }
    $q->QUERY_SQL_PARAMS($sql,$params);
    if(!$q->ok){
        // Le texte SQLite nomme tables et colonnes -- et l'ancienne version renvoyait
        // en plus la requete elle-meme au navigateur.
        writelogs("rule_edit_save($ID): ".$q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
        echo "{database_error}";
        return false;
    }

    // Renumerotation compacte: sans elle, deux regles finissent par partager un rang.
    $c=0;
    $results=$q->QUERY_SQL("SELECT ID FROM webfilter_rules ORDER BY zOrder");
    if(is_array($results)){
        foreach($results as $ligne){
            $q->QUERY_SQL_PARAMS("UPDATE webfilter_rules SET zOrder=? WHERE ID=?",
                array($c,intval($ligne["ID"])));
            $c++;
        }
    }

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/weberror/rules");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
    return admin_tracks("Saving Web-filter rule ".(isset($values["groupname"])?$values["groupname"]:"#$ID"));
}
