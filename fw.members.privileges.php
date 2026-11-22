<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["remove"])){remove_js();exit;}
if(isset($_POST["remove"])){remove_perform();exit;}
if(isset($_GET["remove-simple-acls"])){remove_simple_acls_js();exit;}
if(isset($_POST["remove-simple-acls"])){remove_simple_acls_perform();exit;}


if(isset($_GET["search"])){table();exit;}

startx();

function privs(){
    $users=new usersMenus();
    if($users->AllowAddGroup OR $users->AllowAddUsers) {return true;}
    return false;
}
function remove_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["remove"]);
    if($ID==0){
        echo $tpl->js_error("ID 0 is unsupported!");
        return false;
    }
    // $md est ecrit dans le JavaScript de confirmation: c'est toujours un md5.
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    $row = acls_api_get("/proxy/adgroups-privs/$ID");
    // Le DN partait BRUT dans le titre de la boite de confirmation, qui est une
    // chaine JavaScript.
    $DN = is_array($row) ? htmlspecialchars((string)($row["dn"] ?? "")) : "";
    $tpl->js_confirm_delete("{privileges} &laquo;$DN&raquo; ($ID)","remove",$ID,"$('#$md').remove();");
    return true;
}
function remove_simple_acls_js():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["remove-simple-acls"]);
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    // DN is passed as optional query param to avoid an extra round-trip
    $DN = htmlspecialchars(trim((string)($_GET["dn"] ?? "")));
    $js="$('#$md').remove();";
    $tpl->js_confirm_delete("{privileges} &laquo;$DN&raquo; ($ID)","remove-simple-acls",$ID,$js);
    return true;
}
// remove() a ete retiree: doublon exact de remove_js(), et aucun bloc de
// repartition en tete de fichier ne l'appelait -- $_GET["remove"] est dirige
// vers remove_js().

function remove_perform():bool{
    $ID=intval($_POST["remove"]);
    // Fetch DN before delete for audit log
    $row = acls_api_get("/proxy/adgroups-privs/$ID");
    $DN = is_array($row) ? ($row["dn"] ?? "") : "";
    [$ok,$data] = acls_api_send("DELETE","/proxy/adgroups-privs/$ID");
    if(!$ok){echo htmlspecialchars($data);return false;}
    return admin_tracks("Remove privileges for $DN");
}
function remove_simple_acls_perform():bool{
    $ID=intval($_POST["remove-simple-acls"]);
    [$ok,$data] = acls_api_send("DELETE","/proxy/simple-acls/privs/$ID");
    if(!$ok){echo htmlspecialchars($data);return false;}
    return admin_tracks("Remove Proxy simple acls privileges for ID $ID");
}

function startx(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div id='listOfPrivileges' style='margin-top:10px'></div>";
    $html[]=$tpl->search_block($page);
    echo $tpl->_ENGINE_parse_body($html);


}
function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    if(!privs()){
        echo $tpl->div_error("{ERROR_NO_PRIVS}||{no_privileges}");
        return false;
    }
    // Le joker "*" que search_block encourage n'a pas de sens pour une comparaison
    // par sous-chaine: on le retire plutot que de le chercher litteralement.
    $search=isset($_GET["search"])?str_replace("*","",trim((string)$_GET["search"])):"";

    // Nom de la fonction de rafraichissement generee par search_block. Whitelist au
    // point de lecture: il finit BRUT dans un onclick, et arrive d'une URL que
    // n'importe qui peut forger.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));

    // Suffixe unique par RENDU: rien n'empeche deux fragments de cette page de
    // coexister dans le DOM -- constate le 19/08/2026, les identifiants comptaient 2.
    // getElementById ne voyant que le premier, le pager du second etait inerte.
    // uniqid() et non time(): deux fragments peuvent naitre dans la meme seconde.
    $uid=substr(md5(uniqid("",true)),0,8);

    $rows=array();

    // ---- Privileges portes par un groupe Active Directory ----
    $results = acls_api_get("/proxy/adgroups-privs");
    if(!is_array($results)){ $results = array(); }
    foreach ($results as $ligne){
        if(!is_array($ligne)){continue;}
        $id=intval($ligne["id"] ?? 0);
        $dn=(string)($ligne["dn"] ?? "");
        $content=(string)($ligne["content"] ?? "");
        // Le filtre porte aussi sur les droits accordes, pas seulement sur le DN.
        if(!mpriv_match($search,"$id|$dn|".@implode("|",mpriv_privs($content)))){ continue; }
        $rows[]=mpriv_row($id,$dn,null,"$page?remove=$id",$page,$tpl,$content,$uid,$function);
    }

    // ---- Privileges portes par une regle d'acces simple ----
    // Correspondance aclid -> nom de regle.
    $webfilters_simpleacls=array();
    $all_acls = acls_api_get("/proxy/simple-acls");
    if(is_array($all_acls)){
        foreach($all_acls as $acl){
            if(!is_array($acl)){continue;}
            $webfilters_simpleacls[intval($acl["id"] ?? 0)] = (string)($acl["aclname"] ?? "");
        }
    }

    // Une requete REST par regle: le daemon n'expose pas de listing global des
    // privileges de regles simples.
    foreach($webfilters_simpleacls as $aclid=>$aclname){
        $privs_for_acl = acls_api_get("/proxy/simple-acls/$aclid/privs");
        if(!is_array($privs_for_acl)){continue;}
        foreach($privs_for_acl as $p){
            if(!is_array($p)){continue;}
            $DN=(string)($p["dngroup"] ?? "");
            if($DN==""){continue;}
            $ID=intval($p["id"] ?? 0);
            if(!mpriv_match($search,"$ID|$DN|$aclname")){ continue; }
            $rows[]=mpriv_row($ID,$DN,$aclname,
                "$page?remove-simple-acls=$ID&dn=".urlencode($DN),$page,$tpl,"",$uid,$function);
        }
    }
    // ---- Tenants SIEM portes par un groupe (feature 021) ----
    //
    // Repond a « quels tenants cette personne voit-elle, et POURQUOI » : le perimetre effectif d'un
    // compte est l'union des tenants de ses groupes, et sans cette vue la seule facon de le savoir
    // serait de lire la base. Un droit qu'on ne peut pas expliquer est un droit qu'on n'ose pas
    // retirer.
    foreach(mpriv_siem_tenants() as $dnGroup=>$tenants){
        if(!mpriv_match($search,$dnGroup."|".@implode("|",$tenants))){ continue; }
        $rows[]=mpriv_siem_row($dnGroup,$tenants,$page,$tpl,$uid,$function);
    }

    $c=count($rows);

    $TINY_ARRAY["TITLE"]="{privileges}";
    $TINY_ARRAY["ICO"]="fad fa-users-crown";
    $TINY_ARRAY["EXPL"]="{privileges_list_explain}";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    if($c==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{privileges}||{noprivslist}"));
        echo "<script>$jstiny</script>";
        return false;
    }

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{DN}</th>";
    $html[]="<th>{privileges}</th>";
    $html[]="<th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='mpriv-rows-$uid'>";
    $html[]=@implode("\n",$rows);
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c {items}</span>";
    $html[]="<span class='awc-pg' id='mpriv-pager-$uid'></span>";
    $html[]="</div></div></div>";

    // Le bloc de script est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
    // ICON_SCRIPTS_COMPILE() et non $tpl->ICON_SCRIPTS: le second est une propriete
    // D'INSTANCE, donc vide des qu'une ligne est batie par une autre instance que
    // celle qui emet le script -- le bouton s'affiche alors et ne repond a aucun clic.
    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny\n</script>";

    // Le pager APRES le tableau: les <tr> doivent exister dans le DOM.
    // Pas de awc_filter_js: le filtrage est fait par le serveur (search_block), et
    // l'appel ne trouvait plus de champ #mpriv-search -- il sortait sans rien faire.
    // Pas de awc_sort_js non plus: il trie sur data-sort en parseFloat, or les deux
    // colonnes sont textuelles -- il classerait toutes les lignes sur des zeros.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$script
        .awc_pager_js(50,"mpriv-rows-$uid","mpriv-pager-$uid");
    return true;

}

/**
 * La ligne repond-elle au terme cherche ?
 *
 * Comparaison par sous-chaine, insensible a la casse, et surtout PAS une regex: le
 * terme vient d'une saisie libre. Le "#$search#" precedent faisait qu'un simple "."
 * gardait tout, et qu'un motif invalide -- une parenthese ouvrante suffit --
 * rendait false pour CHAQUE ligne: le tableau se vidait, avec un avertissement PHP.
 */
function mpriv_match($search,$text):bool{
    $search=trim((string)$search);
    if($search===""){ return true; }
    return stripos((string)$text,$search)!==false;
}

/**
 * Les droits d'un groupe, tels qu'ils sont stockes: base64 d'un texte [Cle]="yes".
 *
 * Meme motif que privileges::ParsePrivileges(), volontairement recopie plutot que
 * d'instancier clladp -- dont le constructeur ouvre une connexion LDAP dont cette
 * page n'a aucun besoin. Les entrees a valeur non-"yes" sont ecartees: c'est le
 * cas de SimpleAclsPrivs, qui porte une liste d'identifiants et non un droit.
 */
/**
 * Tenants SIEM par groupe (feature 021), sous la forme dngroup => [tenant_id, ...].
 *
 * Renvoie un tableau VIDE quand le module SIEM est desactive ou que la table n'existe pas encore
 * (aucune attribution faite) : ce n'est pas une anomalie, c'est l'etat d'une appliance ou personne
 * n'a ouvert l'onglet.
 */
function mpriv_siem_tenants():array{
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaSiem"))!=1){ return array(); }
    if(!class_exists("postgres_sql")){ include_once(dirname(__FILE__)."/ressources/class.postgres.inc"); }
    $q=new postgres_sql();
    $results=$q->QUERY_SQL("SELECT dngroup,tenant_id FROM siem_tenants_privs ORDER BY dngroup,tenant_id");
    if(!$q->ok){ return array(); }
    $out=array();
    while($ligne=pg_fetch_assoc($results)){
        $dn=(string)$ligne["dngroup"];
        if($dn===""){continue;}
        if(!isset($out[$dn])){ $out[$dn]=array(); }
        $out[$dn][]=(string)$ligne["tenant_id"];
    }
    return $out;
}

/** Une ligne « ce groupe donne acces a ces tenants ». Pas de suppression ici : elle se fait sur la
 *  fiche du groupe, ou l'operateur voit la liste complete des tenants et peut decocher. */
function mpriv_siem_row($DN,$tenants,$page,$tpl,$uid="",$function=""):string{
    $md=md5("siem|$DN|$uid");
    $DNEnc=urlencode($DN);
    $fnArg=($function!=="")?"&function=$function":"";
    $link="<span class='awc-name'><a href=\"javascript:blur();\" "
        ."onclick=\"Loadjs('fw.groups.ad.php?dn=$DNEnc$fnArg')\">"
        .htmlspecialchars($DN)."</a></span>";
    $chips=array();
    foreach($tenants as $t){
        $chips[]="<span class='awc-badge' style='background:#006a62'>".htmlspecialchars((string)$t)."</span>";
    }
    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-building'></i></span>";
    $h.="<span>$link<div class='awc-meta'>{siem_tenants}</div></span>";
    $h.="</div></td>";
    $h.="<td><div class='awc-chips'>".@implode(" ",$chips)."</div></td>";
    $h.="<td><div class='awc-acts'></div></td>";
    $h.="</tr>";
    return $h;
}

function mpriv_privs($content):array{
    $out=array();
    $raw=base64_decode((string)$content,true);
    if($raw===false){return $out;}
    if(trim($raw)==""){return $out;}
    if(preg_match_all('#\[([a-zA-Z]+)\]="(.+?)"#is',$raw,$re)){
        foreach($re[1] as $n=>$key){
            if(strtolower(trim($re[2][$n]))!=="yes"){continue;}
            $out[]=$key;
        }
    }
    return $out;
}

/**
 * Un groupe et les privileges qui lui sont attaches.
 *
 * $aclname vaut null pour les privileges portes par un groupe Active Directory.
 * Cette cellule affichait un tiret pour TOUS ces groupes: la structure
 * AdGroupPriv du daemon n'exposait que "id" et "dn", jamais la colonne "content"
 * qui porte le jeu de droits. Un operateur pouvait donc attacher des droits au
 * mauvais groupe d'annuaire sans que rien a l'ecran ne le contredise -- panne du
 * 18/08/2026, ou les droits etaient poses sur ARTICA_SMTP_ADMIN alors que le
 * compte appartenait a ARTICA_SMTP_MANAGER. Le daemon expose desormais "content"
 * (squid/acls_sqlite_queries.go) et la cellule liste les droits reellement
 * accordes.
 *
 * Les cles sont rendues comme jetons: elles sortent d'une regex [a-zA-Z]+, donc
 * rien d'arbitraire n'entre entre les accolades. Une cle sans traduction
 * s'affiche telle quelle, ce qui reste informatif.
 */
function mpriv_row($ID,$DN,$aclname,$deleteUrl,$page,$tpl,$content="",$uid="",$function=""):string{
    // $uid entre dans l'identifiant de ligne pour la meme raison que dans ceux du
    // tableau: deux fragments donneraient deux <tr> de meme id, et la suppression
    // -- $('#md').remove() -- n'en retirerait qu'un.
    $md=md5("$ID|$DN|$aclname|$uid");
    $DNEnc=urlencode($DN);
    // Le DN venait de l'annuaire et partait BRUT dans le HTML.
    $DNsafe=htmlspecialchars($DN);
    // Lien du motif awc plutot que td_href(): ce dernier impose un border-bottom et
    // un gras en ligne, etrangers a ce tableau, ou .awc-name porte deja la graisse
    // et le survol.
    // Sans nom de fonction, pas de parametre vide: fw.groups.ad.php appellerait
    // "()" au retour de la popup.
    $fnArg=($function!=="")?"&function=$function":"";
    $link="<span class='awc-name'><a href=\"javascript:blur();\" "
        ."onclick=\"Loadjs('fw.groups.ad.php?dn=$DNEnc$fnArg')\">"
        .$DNsafe."</a></span>";
    $delete=$tpl->icon_delete("Loadjs('$deleteUrl&md=$md')","AsSystemAdministrator");

    if($aclname===null){
        $names=mpriv_privs($content);
        if(count($names)==0){
            // {no_privileges} disait "VOUS n'avez pas les droits" -- un message adresse
            // a l'operateur, detourne pour decrire le groupe. Sur la ligne d'un groupe
            // sans droit, il se lisait comme un refus d'acces.
            $priv="<span class='awc-none'>{no_privilege_attached}</span>";
        }else{
            $chips=array();
            foreach($names as $n){
                $chips[]="<span class='awc-tag'>{".$n."}</span>";
            }
            $priv="<div class='awc-chips'>".@implode(" ",$chips)."</div>";
        }
    }else{
        $priv="<span class='awc-badge' style='background:#006a62'>{access_rules}</span> "
            .htmlspecialchars((string)$aclname);
    }

    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fad fa-users-crown'></i></span>";
    $h.="<span>$link</span>";
    $h.="</div></td>";
    $h.="<td>$priv</td>";
    $h.="<td><div class='awc-acts'>$delete</div></td>";
    $h.="</tr>";
    return $h;
}
