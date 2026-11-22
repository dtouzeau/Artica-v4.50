<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.netagents.tenants.inc");   // tuiles des tenants Artica Meta
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["profile"])){profile();exit;}
if(isset($_POST["NewName"])){ChangeName();exit;}
if(isset($_GET["members"])){members();exit;}
if(isset($_GET["members-table"])){members_table();exit;}
if(isset($_GET["link-group-js"])){link_group_js();exit;}
if(isset($_GET["link-group-popup"])){link_group_popup();exit;}
if(isset($_GET["link-js"])){link_member();exit;}
if(isset($_GET["unlink-js"])){unlink_members();exit;}
if(isset($_GET["privileges"])){privileges();exit;}
if(isset($_GET["privileges-harmp-enable"])){privileges_harmp_enable();exit;}
if(isset($_GET["privileges-access-rule"])){privileges_access_rule();exit;}
if(isset($_GET["privileges-access-rule-switch"])){privileges_access_rules_switch();exit;}

if(isset($_GET["privileges-categories"])){privileges_categories();exit;}
if(isset($_GET["privileges-categories-enable"])){privileges_categories_enable();exit;}
if(isset($_GET["privileges-hamrp"])){privileges_harmp();exit;}
// Tenants SIEM (feature 021). Dispatches EN TETE, comme tous les autres: un point d'entree AJAX
// capte plus bas laisserait js() emettre son propre LoadAjax, et l'onglet rechargerait la page
// entiere en revenant au premier onglet.
if(isset($_GET["privileges-tenants"])){privileges_tenants();exit;}
if(isset($_GET["privileges-tenants-enable"])){privileges_tenants_enable();exit;}
if(isset($_GET["privileges-meta-tenant-js"])){privileges_meta_tenant_js();exit;}
if(isset($_POST["privileges-meta-tenant"])){privileges_meta_tenant();exit;}
if(isset($_GET["privileges-docker"])){privileges_docker();exit;}
if(isset($_POST["privs-docker"])){privileges_docker_save();exit;}
if(isset($_POST["privs"])){privileges_save();exit;}
js();

/**
 * Lit un attribut LDAP renvoye par external_ad_search::member_infos().
 *
 * member_infos() renvoie un tableau VIDE quand le DN est introuvable ou que
 * l'annuaire est injoignable, et l'annuaire omet purement et simplement les
 * attributs non renseignes. Les appelants lisaient $hash[0]["attr"][0] sans
 * garde: trois avertissements PHP par acces, et une valeur null propagee
 * jusqu'a field_info().
 */
function groupsad_attr($hash,$key,$default=""){
    if(!is_array($hash)){return $default;}
    if(!isset($hash[0]) || !is_array($hash[0])){return $default;}
    if(!isset($hash[0][$key])){return $default;}
    $val=$hash[0][$key];
    // Un attribut LDAP se presente sous forme de tableau indexe (avec une cle
    // "count"), mais "dn" est renvoye comme une chaine simple.
    if(is_array($val)){
        return isset($val[0])?$val[0]:$default;
    }
    return $val;
}

function js():bool{
    $ActiveDirectoryIndex="None";
    $urlExtension=null;
    $dn=$_GET["dn"];
    $dnenc=urlencode($dn);

    if(isset($_GET["connection-id"])) {
        $ActiveDirectoryIndex = intval($_GET["connection-id"]);
        if ($ActiveDirectoryIndex > 0) {
            if($ActiveDirectoryIndex==123456789){
                $ct=new external_ad_search(null);
                $params=$ct->FindParametersByDN($dn);
                $ActiveDirectoryIndex=intval($params["CONNECTION_ID"]);
            }
            $urlExtension = "&connection-id=$ActiveDirectoryIndex";
        }
    }
    if(isset($_GET["function"])){$urlExtension=$urlExtension."&function={$_GET["function"]}";}
    $page=CurrentPageName();
    $tpl=new template_admin();

    $ct=new external_ad_search(null,$ActiveDirectoryIndex);
    $hash=$ct->member_infos($dn);
    $GroupName=groupsad_attr($hash,"samaccountname");
    if($GroupName===""){$GroupName=$dn;}

    return $tpl->js_dialog5(addslashes(htmlspecialchars($GroupName)),"$page?tabs=$dnenc$urlExtension");
}


function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ActiveDirectoryIndex="None";$urlExtension=null;
    if(isset($_GET["connection-id"])){$ActiveDirectoryIndex=$_GET["connection-id"];$urlExtension="&connection-id=$ActiveDirectoryIndex";}
    if(isset($_GET["function"])){$urlExtension=$urlExtension."&function={$_GET["function"]}";}
    $EnablePersonalCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePersonalCategories"));
    $EnableManagedReverseProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableManagedReverseProxy"));

    if($EnablePersonalCategories==1){
        $q=new postgres_sql();
        $ligne=pg_fetch_array($q->QUERY_SQL("SELECT COUNT(*) AS tcount FROM personal_categories WHERE PublicMode='1'"));
        if(intval($ligne["tcount"])==0){$EnablePersonalCategories=0;}
    }

    $dn=$_GET["tabs"];
    $dnenc=urlencode($dn);
    $ct=new external_ad_search(null,$ActiveDirectoryIndex);
    $hash=$ct->member_infos($dn);
    // Un onglet sans libelle est invisible: on retombe sur le nom commun puis
    // sur le DN plutot que de produire une cle vide.
    $GroupName=groupsad_attr($hash,"samaccountname");
    if($GroupName===""){$GroupName=groupsad_attr($hash,"cn");}
    if($GroupName===""){$GroupName=$dn;}
    $array=array();
    $array[$GroupName]="$page?profile=$dnenc$urlExtension";
    $array["{members}"]="$page?members=$dnenc$urlExtension";
    $array["{privileges}"]="$page?privileges=$dnenc$urlExtension";
    $array["{docker_privs_tab}"]="$page?privileges-docker=$dnenc$urlExtension";
    // Pas d'onglet « SIEM tenants »: les tenants sont une SECTION de l'ecran des privileges
    // (siem_tenants_tiles()), comme sur fw.groups.ldap.php. L'objection d'origine -- un formulaire
    // s'applique en bloc alors qu'un tenant se bascule immediatement -- est levee par
    // field_switch_tile(), qui ecrit en AJAX et ne s'inscrit pas au formulaire. Reste l'argument
    // decisif: c'est dans les privileges que l'on vient chercher ce qu'un groupe a le droit de voir.
    if($EnablePersonalCategories==1){
        $array["{categories}"]="$page?privileges-categories=$dnenc$urlExtension";
    }
    if($EnableManagedReverseProxy==1){
        $array["{APP_HAMRP}"]="$page?privileges-hamrp=$dnenc$urlExtension";
    }
    $SQUIDEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==1){
        $simpleAcls=acls_api_get("/proxy/simple-acls");
        $saCount=is_array($simpleAcls)?count($simpleAcls):0;
        VERBOSE("$saCount",__LINE__);
        if($saCount>0){
            $array["{access_rules}"]="$page?privileges-access-rule=$dnenc$urlExtension";
        }
    }


    echo $tpl->tabs_default($array);
}

function members(){
    $urlExtension=null;
    if(isset($_GET["connection-id"])){$ActiveDirectoryIndex=$_GET["connection-id"];$urlExtension="&connection-id=$ActiveDirectoryIndex";}
    if(isset($_GET["function"])){$urlExtension=$urlExtension."&function={$_GET["function"]}";}
    $page=CurrentPageName();
    $dn=$_GET["members"];
    $dnenc=urlencode($dn);
    $html="<div id='MembersOfTheGroup' style='margin-top:10px'></div>
	<script>LoadAjaxTiny('MembersOfTheGroup','$page?members-table=$dnenc$urlExtension');</script>";
    echo $html;
}


function members_table(){
    $ActiveDirectoryIndex="None";$urlExtension=null;
    if(isset($_GET["connection-id"])){$ActiveDirectoryIndex=$_GET["connection-id"];$urlExtension="&connection-id=$ActiveDirectoryIndex";}
    if(isset($_GET["function"])){$urlExtension=$urlExtension."&function={$_GET["function"]}";}
    $dn=$_GET["members-table"];
    $ct=new external_ad_search(null,$ActiveDirectoryIndex);
    $hash=$ct->member_infos($dn);
    // Le repli placait une CHAINE vide dans "member": la ligne suivante lisait
    // alors $memberof["count"] sur une chaine (null sous PHP 8, avec
    // avertissement). Et un groupe sans aucun membre n'expose pas du tout
    // l'attribut "member" -- ce cas n'etait pas couvert non plus.
    $memberof=array("count"=>0);
    if(isset($hash[0]["member"]) && is_array($hash[0]["member"])){
        $memberof=$hash[0]["member"];
    }
    if(!isset($memberof["count"])){$memberof["count"]=0;}
    $md5=md5($dn);
    $tpl=new template_admin();

    $rows=array();
    for ($i=0;$i<$memberof["count"];$i++) {
        // "count" peut sur-declarer les entrees presentes (annuaire tronquant
        // la reponse au-dela d'une limite serveur).
        if(!isset($memberof[$i])){continue;}
        $gpid=$memberof[$i];
        $hash=$ct->member_infos($gpid);
        if(!isset($hash[0])){
            continue;
        }
        $rows[]=grpad_member_row($gpid,$hash,$urlExtension,$tpl);
    }
    $shown=count($rows);

    $html=array();
    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{members} ({$memberof["count"]})</th>";
    $html[]="</tr></thead><tbody id='grpad-mbr-rows'>";
    if($shown==0){
        $html[]="<tr><td><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $shown</span>";
    $html[]="<span class='awc-pg' id='grpad-mbr-pager'></span>";
    $html[]="</div></div>";

    // Le bloc de script est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"grpad-mbr-rows","grpad-mbr-pager");
}

/** Un membre du groupe. */
function grpad_member_row($gpid,$hash,$urlExtension,$tpl):string{
    $dnuser=groupsad_attr($hash,"dn");
    // samaccountname est facultatif: sans repli, la ligne affichait un lien
    // vide, donc incliquable.
    $DisplayName=groupsad_attr($hash,"samaccountname");
    if($DisplayName===""){$DisplayName=groupsad_attr($hash,"cn");}
    if($DisplayName===""){$DisplayName=$gpid;}

    $md=md5($gpid.$dnuser);
    $js="Loadjs('fw.member.ad.edit.php?dn=".urlencode($dnuser)."$urlExtension')";

    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-user'></i></span>";
    $h.="<span><span class='awc-name'>";
    // Ces valeurs viennent de l'annuaire et partaient BRUTES dans le HTML.
    $h.=$tpl->td_href(htmlspecialchars((string)$DisplayName),"{click_to_edit}",$js)."</span>";
    if($dnuser!==""){$h.="<div class='awc-meta awc-mono'>".htmlspecialchars((string)$dnuser)."</div>";}
    $h.="</span></div></td>";
    $h.="</tr>";
    return $h;
}





function profile(){
    $ActiveDirectoryIndex="None";$urlExtension=null;
    if(isset($_GET["connection-id"])){$ActiveDirectoryIndex=$_GET["connection-id"];$urlExtension="&connection-id=$ActiveDirectoryIndex";}
    if(isset($_GET["function"])){$urlExtension=$urlExtension."&function={$_GET["function"]}";}
    $page=CurrentPageName();
    $dn=$_GET["profile"];
    $tpl=new template_admin();
    $ct=new external_ad_search(null,$ActiveDirectoryIndex);
    $hash=$ct->member_infos($dn);
    $jsrestart=null;
    // member_infos() renvoie un tableau vide quand le DN est introuvable ou que
    // l'annuaire est injoignable: $hash[0] etait lu sans garde, puis chacun de
    // ses attributs egalement. Quatre d'entre eux (cn, name, samaccountname,
    // description) sont facultatifs cote annuaire.
    if(!isset($hash[0]) || !is_array($hash[0])){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{group_not_found}: ".htmlspecialchars($dn)));
        return;
    }
    $array=$hash[0];

    // Le titre du formulaire etait lu APRES le unset() de samaccountname: il
    // etait donc TOUJOURS vide. Il est capture ici, avant les suppressions.
    $samaccountname=groupsad_attr($hash,"samaccountname");

    // Premier argument = NOM du champ (identifiant DOM et cle XHR), second =
    // libelle. Trois d'entre eux portaient un jeton de traduction en position
    // de nom, ce qui produisait des identifiants a accolades sans effet utile.
    $form=array();
    $form[]=$tpl->field_info("cn","{CommonName}", groupsad_attr($hash,"cn"));
    $form[]=$tpl->field_info("name","{name}", groupsad_attr($hash,"name"));
    $form[]=$tpl->field_info("samaccountname","{member}", $samaccountname);
    $form[]=$tpl->field_info("description","{description}", groupsad_attr($hash,"description"));
    unset($array["cn"]);
    unset($array["samaccountname"]);
    unset($array["description"]);
    unset($array["objectguid"]);
    unset($array["objectsid"]);
    unset($array["member"]);
    unset($array["name"]);
    unset($array["objectclass"]);


    foreach ($array as $key=>$val){
        if(!isset($val["count"])){continue;}

        for($i=0;$i<$val["count"];$i++){
            $form[]=$tpl->field_info($key,$key, $val[$i]);
        }

    }
    echo $tpl->form_outside(htmlspecialchars($samaccountname), @implode("\n", $form),null,null,"$jsrestart","AllowAddUsers");

}

function privileges():bool{
    $ActiveDirectoryIndex=0;
    if(isset($_GET["connection-id"])){$ActiveDirectoryIndex=$_GET["connection-id"];}
    $dn=$_GET["privileges"];
    $hash["ArticaGroupPrivileges"]="";
    $tpl=new template_admin();
    // $_GET["function"] partait BRUT dans le $jsafter de form_outside, donc dans
    // du JavaScript execute: liste blanche a la lecture. La valeur par defaut
    // porte des parentheses et ne passe pas par le filtre.
    $function="blur()";
    if(isset($_GET["function"])){
        $fname=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["function"]);
        if(strlen($fname)>2){$function="$fname()";}
    }
    $AD=true;

    $EnablePostfix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix");
    if(preg_match("#sqlite:([0-9]+)#", $dn,$re)){
        $ID=$re[1];
        $AD=false;
        $grpData=acls_api_get("/admins/groups/$ID");
        if(is_array($grpData) && isset($grpData["privileges"]) && $grpData["privileges"]!=="") {
            $hash["ArticaGroupPrivileges"] = base64_decode($grpData["privileges"]);
        }

    }

    if($AD){
        $ct=new external_ad_search(null,$ActiveDirectoryIndex);
        $hash=$ct->LoadGroupDataByDN($dn);
    }
    $data=$hash["ArticaGroupPrivileges"];
    $ldap=new clladp();
    $strlen=0;
    if(!is_null($data)){
        $strlen=strlen($data);
    }
    VERBOSE("$dn $strlen bytes",__LINE__);
    $HashPrivieleges=$ldap->_ParsePrivieleges($data);
    if(!is_array($HashPrivieleges)){$HashPrivieleges=array();}

    // Les privileges sont rendus en TUILES sur deux colonnes: vingt-trois droits
    // empiles en une colonne unique obligent a lire toute la liste pour en
    // trouver un, et laissent la moitie de la largeur vide.
    $sections=array();
    $sections["{groups_allow}"]=array("AllowAddUsers","AllowAddGroup","AsOrgAdmin","AsSquidPersonalCategories");
    $sections["{proxy_allow}"]=array("AsWebStatisticsAdministrator","AsDansGuardianAdministrator",
        "AsSquidAdministrator","AsHotSpotManager","AsProxyMonitor");
    // La section messagerie n'existe que si Postfix est active: son intertitre
    // est donc ajoute DANS la condition, sinon il resterait seul en tete d'une
    // grille sans aucune tuile.
    if($EnablePostfix==1){
        $sections["{messaging_allow}"]=array("AsPostfixAdministrator","AsMailBoxAdministrator");
    }
    $sections["{administrators_allow}"]=array("AsArticaAdministrator","AsFirewallManager","AsVPNManager",
        "AsDnsAdministrator","AsDnsMonitor","ASDCHPAdmin","AsCertifsManager","AsDatabaseAdministrator",
        "AsWebMaster","AsWebSecurity","AsSambaAdministrator","AsDockerAdmin");
    // Artica Meta multi-tenant : administrateur des tenants (clients) dont le groupe ou le compte
    // est proprietaire. Seul ce droit ne donne que les pages Meta, sur ses tenants.
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMeta"))==1){
        $sections["{APP_ARTICA_META}"]=array("AsMetaAdmin");
    }

    $tiles=array();
    foreach($sections as $sectitle=>$privs){
        $tiles[]=$tpl->field_tiles_section($sectitle);
        foreach($privs as $p){
            $value=isset($HashPrivieleges[$p])?$HashPrivieleges[$p]:0;
            // AsMetaAdmin : accorde par un administrateur systeme (decision de l'infogerant).
            $privRights=($p=="AsMetaAdmin")?"AsSystemAdministrator":$p;
            $tiles[]=$tpl->field_checkbox_tile($p,"{{$p}}",$value,grpad_priv_icon($p),$p,$privRights);
        }
    }

    // Quatrieme section: les tenants SIEM que ce groupe a le droit de consulter.
    //
    // $dn est passe TEL QUEL comme cle de groupe: un DN pour un groupe Active Directory,
    // « sqlite:<id> » pour un groupe local -- les deux formes que class.privileges.inc relit, et
    // que cet ecran recoit deja (fw.system.localadmins.php pointe ici avec sqlite:<id>). Les
    // tuiles ecrivent immediatement par AJAX et ne s'inscrivent pas au formulaire: un tenant
    // n'est pas une cle du blob ArticaGroupPrivileges mais une ligne d'une table de liaison.
    $tiles=array_merge($tiles,siem_tenants_tiles($dn,CurrentPageName(),$tpl));
    // Tenants Artica Meta dont ce groupe est proprietaire : DN (source "ad") ou sqlite:<id>
    // (source "sqlite"), la cle que la connexion relit. Infogerant / administrateur systeme seulement.
    $tiles=array_merge($tiles,natn_group_owner_tiles(grpad_meta_source($dn),$dn,grpad_meta_label($dn),CurrentPageName(),"privileges-meta-tenant",$tpl));

    $form=array();
    $form[]=$tpl->field_hidden("privs", $dn);
    $form[]=$tpl->field_tiles_grid($tiles,2);

    $html[]=$tpl->form_outside("{privileges}",
        @implode("\n", $form),null,"{apply}",
        $function,"AsSystemAdministrator");

    // La feuille de style et le liant JS sont concatenes AUTOUR de l'analyse:
    // la regex #{(.+?)}# de _ENGINE_parse_body lirait chaque corps de regle CSS
    // et chaque accolade JavaScript comme un {token}.
    echo $tpl->field_tiles_css()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$tpl->field_tiles_js();
    return true;
}

// Onglet Docker (spec 005 artica-docker): les sous-privileges AsDocker* du groupe, en
// tuiles, enregistres dans le MEME blob ArticaGroupPrivileges que l'ecran {privileges}.
// privileges_save() fusionne les seules cles postees: les deux ecrans ne s'ecrasent pas.
function privileges_docker():bool{
    include_once(dirname(__FILE__)."/ressources/class.docker.manager.inc");
    $ActiveDirectoryIndex=isset($_GET["connection-id"])?$_GET["connection-id"]:0;
    $dn=$_GET["privileges-docker"];
    $tpl=new template_admin();
    $data="";
    if(preg_match("#sqlite:([0-9]+)#",$dn,$re)){
        $grpData=acls_api_get("/admins/groups/".intval($re[1]));
        if(is_array($grpData) && isset($grpData["privileges"]) && $grpData["privileges"]!==""){
            $data=base64_decode($grpData["privileges"]);
        }
    }else{
        $ct=new external_ad_search(null,$ActiveDirectoryIndex);
        $hash=$ct->LoadGroupDataByDN($dn);
        $data=isset($hash["ArticaGroupPrivileges"])?$hash["ArticaGroupPrivileges"]:"";
    }
    $ldap=new clladp();
    $Hash=$ldap->_ParsePrivieleges($data);
    if(!is_array($Hash)){$Hash=array();}

    $tiles=array();
    $family="";
    foreach(docker_priv_table() as $key=>$row){
        if($row["family"]!==$family){
            $family=$row["family"];
            $tiles[]=$tpl->field_tiles_section($family);
        }
        $value=(isset($Hash[$key]) && $Hash[$key]=="yes")?1:0;
        $icon=$row["sensitive"]?"fas fa-triangle-exclamation":"fab fa-docker";
        // field_checkbox_tile() passe $meta par htmlspecialchars(): pas d'entite HTML ici
        // (le caractere UTF-8 passe tel quel; le {token} est traduit ensuite par _ENGINE_parse_body).
        $meta=$row["perm"].($row["sensitive"]?" \xC2\xB7 {docker_priv_sensitive}":"");
        $tiles[]=$tpl->field_checkbox_tile($key,$row["label"],$value,$icon,$meta,"AsSystemAdministrator");
    }
    $form=array();
    $form[]=$tpl->field_hidden("privs-docker",$dn);
    $form[]=$tpl->field_tiles_grid($tiles,2);
    $html=array();
    $html[]=$tpl->div_explain("{docker_privs_tab}||{docker_privs_tab_explain}");
    $html[]=$tpl->form_outside("{docker_privs_tab}",@implode("\n",$form),null,"{apply}","blur()","AsSystemAdministrator");
    echo $tpl->field_tiles_css()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$tpl->field_tiles_js();
    return true;
}

// Seules les cles de docker_priv_table() sont retenues, puis privileges_save()
// fait la fusion et la trace AdminTrack comme pour l'ecran {privileges}.
// CLEAN_POST() ne fait que nettoyer $_POST en place: le reecrire ici est sans danger.
function privileges_docker_save():bool{
    include_once(dirname(__FILE__)."/ressources/class.docker.manager.inc");
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        admin_tracks("Refused: define Docker privileges on group ".(string)$_POST["privs-docker"]." (not a system administrator)");
        echo (new template_admin())->post_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $dn=(string)$_POST["privs-docker"];
    $keep=array("privs"=>$dn);
    foreach(docker_priv_table() as $key=>$row){
        if(isset($_POST[$key])){$keep[$key]=intval($_POST[$key])==1?1:0;}
    }
    $_POST=$keep;
    return privileges_save();
}



function privileges_access_rule():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $dn=$_GET["privileges-access-rule"];
    if(strlen($dn)<2){
        echo $tpl->div_error("NO DN ??");
        return false;
    }
    $dnenc=urlencode($dn);
    $ENABLEDS=array();

    // Collect enabled aclids for this dn by scanning all simple-acl privs.
    // Une requete REST par regle: le daemon n'expose pas de listing global des
    // privileges de regles simples.
    $allSimpleAcls=acls_api_get("/proxy/simple-acls");
    if(!is_array($allSimpleAcls)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{connection_failed}"));
        return false;
    }
    foreach($allSimpleAcls as $acl){
        if(!is_array($acl)){continue;}
        $aclid=intval($acl["id"] ?? 0);
        if($aclid===0){continue;}
        $privs=acls_api_get("/proxy/simple-acls/$aclid/privs");
        if(!is_array($privs)){continue;}
        foreach($privs as $priv){
            if(!is_array($priv)){continue;}
            // La cle etait lue en "dnGroup" alors que le daemon renvoie
            // "dngroup" (voir fw.members.privileges.php, qui lit la bonne):
            // la comparaison echouait TOUJOURS, $ENABLEDS restait vide et
            // chaque regle s'affichait comme desactivee, quel que soit son
            // etat reel en base.
            if(((string)($priv["dngroup"] ?? ""))===$dn){
                $ENABLEDS[$aclid]=true;
                break;
            }
        }
    }

    $rows=array();
    foreach ($allSimpleAcls as $ligne){
        if(!is_array($ligne)){continue;}
        $aclid = intval($ligne["id"] ?? $ligne["ID"] ?? 0);
        $rows[]=grpad_switch_row(
            (string)($ligne["aclName"] ?? $ligne["aclname"] ?? ""),
            (string)($ligne["zExplain"] ?? ""),
            isset($ENABLEDS[$aclid])?1:0,
            "Loadjs('$page?privileges-access-rule-switch=$dnenc&aclid=$aclid')",
            "fas fa-shield-halved",$tpl);
    }

    echo grpad_switch_table($rows,"{rule}","grpad-acl",$tpl);
    return true;
}



function privileges_harmp():bool{
    $tpl=new template_admin();
    $dn=$_GET["privileges-hamrp"];
    $dnenc=urlencode($dn);
    $page=CurrentPageName();
    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS privs (`ID` INTEGER PRIMARY KEY AUTOINCREMENT, groupid INT,dngroup TEXT )");

    // Le DN partait BRUT dans le WHERE: une apostrophe -- courante dans un nom
    // commun ("O'Brien") -- suffisait a detourner la requete.
    $dnsql=$q->sqlite_escape_string2($dn);
    $ENABLEDS=array();
    $results=$q->QUERY_SQL("SELECT groupid FROM privs WHERE dngroup='$dnsql'");
    // L'echec de CETTE requete n'etait pas verifie: seul celui de la suivante
    // l'etait.
    if(!$q->ok){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($q->mysql_error)));
        return false;
    }
    foreach ($results as $index=>$ligne){
        $ENABLEDS[$ligne["groupid"]]=true;
    }

    $results=$q->QUERY_SQL("SELECT * FROM groups");
    if(!$q->ok){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($q->mysql_error)));
        return false;
    }

    $rows=array();
    foreach ($results as $index=>$ligne){
        $groupid = intval($ligne["ID"]);
        $rows[]=grpad_switch_row((string)$ligne["groupname"],(string)$ligne["comment"],
            isset($ENABLEDS[$groupid])?1:0,
            "Loadjs('$page?privileges-harmp-enable=$dnenc&groupid=$groupid')",
            "fas fa-layer-group",$tpl);
    }

    echo grpad_switch_table($rows,"{group}","grpad-hamrp",$tpl);
    return true;
}

function privileges_categories(){
    $q=new postgres_sql();
    $tpl=new template_admin();
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS personal_categories_privs (
				 id SERIAL PRIMARY KEY,
				 category_id BIGINT,
				 dngroup VARCHAR( 512 ) )");

    if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}

    $page=CurrentPageName();
    $dn=$_GET["privileges-categories"];
    $dnenc=urlencode($dn);
    // Le DN partait BRUT dans le WHERE PostgreSQL.
    $dnsql=pg_escape_string2((string)$dn);

    $HASH=array();
    $results=$q->QUERY_SQL("SELECT id,category_id FROM personal_categories_privs WHERE dngroup='$dnsql'");
    if(!$q->ok){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($q->mysql_error)));
        return;
    }
    while ($ligne = pg_fetch_assoc($results)) {
        $HASH[$ligne["category_id"]]=$ligne["id"];
    }

    $sql="SELECT * FROM personal_categories WHERE PublicMode=1 order by categoryname";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($q->mysql_error)));
        return;
    }

    $rows=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $category_id = intval($ligne["category_id"]);
        // La description est stockee sous forme de jetons {…}: elle doit passer
        // par le moteur de traduction et ne peut donc pas etre echappee.
        $rows[]=grpad_switch_row((string)$ligne["categoryname"],
            $tpl->_ENGINE_parse_body($ligne["category_description"]),
            isset($HASH[$category_id])?1:0,
            "Loadjs('$page?privileges-categories-enable=$dnenc&category_id=$category_id')",
            "fas fa-tag",$tpl,true);
    }

    echo grpad_switch_table($rows,"{categories}","grpad-catz",$tpl);

}
function privileges_categories_enable():bool{
    if(grpad_privs_write_denied("grant personal categories",false)){return false;}
    $tpl=new template_admin();
    $dn=$_GET["privileges-categories-enable"];
    $category_id=intval($_GET["category_id"]);
    $q=new postgres_sql();
    $q->create_personal_categories();

    // Le DN partait BRUT dans le SELECT, et l'INSERT l'echappait avec
    // str_replace("'","\\'") -- une convention MySQL. PostgreSQL applique
    // standard_conforming_strings depuis la 9.1: l'antislash y est un caractere
    // ORDINAIRE, l'apostrophe terminait donc quand meme la chaine.
    $dnsql=pg_escape_string2((string)$dn);

    // QUERY_SQL() renvoie une RESSOURCE de resultat, pas une ligne: $ligne["id"]
    // valait donc toujours null (avertissement sous PHP 7, erreur fatale
    // "Cannot use object of type PgSql\Result as array" sous PHP 8). $id
    // restait a 0, si bien que la bascule INSERAIT a chaque clic sans jamais
    // supprimer: une categorie pouvait etre activee mais jamais desactivee, et
    // chaque nouvelle activation ajoutait une ligne en double.
    $ligne=$q->mysqli_fetch_array("SELECT id FROM personal_categories_privs WHERE dngroup='$dnsql' and category_id=$category_id");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}

    $id=is_array($ligne)?intval($ligne["id"] ?? 0):0;
    if($id==0){
        $q->QUERY_SQL("INSERT INTO personal_categories_privs (category_id,dngroup)
        VALUES ('$category_id','$dnsql')");
        if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
        return admin_tracks("Set personal category #$category_id for group $dn");
    }
    $q->QUERY_SQL("DELETE FROM personal_categories_privs WHERE id=$id");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
    return admin_tracks("Remove personal category #$category_id for group $dn");
}

function privileges_tenants(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $dn=$_GET["privileges-tenants"];
    $dnenc=urlencode($dn);

    $q=new postgres_sql();
    if(!siem_tenants_create_table($q)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($q->mysql_error)));
        return;
    }
    $dnsql=pg_escape_string2((string)$dn);

    $ENABLED=array();
    $results=$q->QUERY_SQL("SELECT tenant_id FROM siem_tenants_privs WHERE dngroup='$dnsql'");
    if(!$q->ok){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($q->mysql_error)));
        return;
    }
    while($ligne=pg_fetch_assoc($results)){
        $ENABLED[(string)$ligne["tenant_id"]]=true;
    }

    // Nettoyage des references orphelines: un tenant supprime cote SIEM laisse une ligne inerte
    // (plus aucun utilisateur ne peut la voir), mais la table ne doit pas croitre indefiniment.
    // Fait ici plutot que par une tache planifiee: c'est le seul moment ou la liste faisant
    // autorite est de toute facon en main.
    $available=siem_tenants_available();
    if(is_array($available) && count($ENABLED)>0){
        $orphans=array();
        foreach(array_keys($ENABLED) as $known){
            if(!isset($available[$known])){$orphans[]=pg_escape_string2((string)$known);}
        }
        if(count($orphans)>0){
            $q->QUERY_SQL("DELETE FROM siem_tenants_privs WHERE dngroup='$dnsql' AND tenant_id IN ('".@implode("','",$orphans)."')");
            foreach($orphans as $o){$ENABLED_removed=true;unset($ENABLED[$o]);}
        }
    }
    if($available===null){
        // « Liste indisponible » n'est PAS « aucun tenant »: distinguer les deux est la seule chose
        // qui empeche un administrateur de croire que les attributions ont ete perdues.
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{siem_tenants_unavailable}"));
        return;
    }

    $rows=array();
    foreach($available as $tenant_id=>$display){
        $rows[]=grpad_switch_row($display,$tenant_id,
            isset($ENABLED[$tenant_id])?1:0,
            "Loadjs('$page?privileges-tenants-enable=$dnenc&tenant_id=".urlencode($tenant_id)."')",
            "fas fa-building",$tpl);
    }

    $html=array();
    $html[]=$tpl->div_explain("{siem_tenants}||{siem_tenants_explain}");
    if(count($available)==0){
        $html[]=$tpl->div_error("{siem_tenants_none}");
        echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
        return;
    }
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html))
        .grpad_switch_table($rows,"{tenant}","grpad-siemt",$tpl);
}

function privileges_tenants_enable():bool{
    if(grpad_privs_write_denied("grant SIEM tenants",false)){return false;}
    $tpl=new template_admin();
    $dn=$_GET["privileges-tenants-enable"];
    $tenant_id=trim((string)(isset($_GET["tenant_id"])?$_GET["tenant_id"]:""));
    if($tenant_id===""){$tpl->js_error("{siem_tenants_unknown}");return false;}

    // Le tenant doit exister et etre actif AU MOMENT de la bascule. Sans ce controle, une case
    // cochee sur un tenant disparu creerait un droit que plus rien ne peut expliquer ni retirer.
    $available=siem_tenants_available();
    if($available===null){$tpl->js_error("{siem_tenants_unavailable}");return false;}
    if(!isset($available[$tenant_id])){$tpl->js_error("{siem_tenants_unknown}");return false;}

    $q=new postgres_sql();
    if(!siem_tenants_create_table($q)){$tpl->js_error($q->mysql_error);return false;}

    $dnsql=pg_escape_string2((string)$dn);
    $tidsql=pg_escape_string2($tenant_id);

    // mysqli_fetch_array() et non QUERY_SQL(): cette derniere renvoie une RESSOURCE de resultat, et
    // la lire comme un tableau laissait $id a 0 -- la bascule inserait alors a chaque clic sans
    // jamais supprimer. Le bug a deja ete paye sur personal_categories_privs.
    $ligne=$q->mysqli_fetch_array("SELECT id FROM siem_tenants_privs WHERE dngroup='$dnsql' and tenant_id='$tidsql'");
    if(!$q->ok){$tpl->js_error($q->mysql_error);return false;}

    $id=is_array($ligne)?intval($ligne["id"] ?? 0):0;
    if($id==0){
        $q->QUERY_SQL("INSERT INTO siem_tenants_privs (tenant_id,dngroup) VALUES ('$tidsql','$dnsql')");
        if(!$q->ok){$tpl->js_error($q->mysql_error);return false;}
        return admin_tracks("Grant SIEM tenant $tenant_id to group $dn");
    }
    $q->QUERY_SQL("DELETE FROM siem_tenants_privs WHERE id=$id");
    if(!$q->ok){$tpl->js_error($q->mysql_error);return false;}
    return admin_tracks("Revoke SIEM tenant $tenant_id from group $dn");
}

function privileges_access_rules_switch():bool{
    if(grpad_privs_write_denied("grant Web Filter ACL privileges",false)){return false;}
    $tpl=new template_admin();
    // privileges-access-rule-switch=$dnenc&aclid=$dnenc
    $dn=$_GET["privileges-access-rule-switch"];
    if(strlen($dn)<3){
        return $tpl->js_mysql_alert("Invalid DN");

    }
    $aclid=intval($_GET["aclid"]);
    // Check if this dn already has a priv entry for this aclid
    $privs=acls_api_get("/proxy/simple-acls/$aclid/privs");
    $existingID=0;
    if(is_array($privs)){
        foreach($privs as $priv){
            if(($priv["dnGroup"] ?? "")===$dn){
                $existingID=intval($priv["id"] ?? 0);
                break;
            }
        }
    }
    if($existingID===0){
        list($ok,$data)=acls_api_send("POST","/proxy/simple-acls/$aclid/privs",["dngroup"=>$dn]);
        if(!$ok){$tpl->js_mysql_alert(is_string($data)?$data:"API error");return false;}
    }
    return admin_tracks("Set privileges on Web Filter ACL #$aclid for group $dn");
}

function privileges_harmp_enable():bool{
    if(grpad_privs_write_denied("grant Reverse Proxy group privileges",false)){return false;}
    $tpl=new template_admin();
    $dn=$_GET["privileges-harmp-enable"];
    $groupid=intval($_GET["groupid"]);
    if($groupid==0){return false;}
    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");

    // Meme correction que privileges_categories_enable(): le DN partait BRUT
    // dans le SELECT et l'INSERT l'echappait a la mode MySQL, ce que SQLite ne
    // reconnait pas non plus (il double l'apostrophe).
    $dnsql=$q->sqlite_escape_string2((string)$dn);

    // QUERY_SQL() de lib_sqlite renvoie un TABLEAU DE LIGNES: $ligne["ID"]
    // n'existait jamais (le repli a 0 juste en dessous le confirmait), donc
    // $id restait a 0 et la bascule INSERAIT a chaque clic sans jamais
    // supprimer -- le privilege ne pouvait pas etre retire.
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM privs WHERE dngroup='$dnsql' and groupid=$groupid");
    if(!$q->ok){
        $tpl->js_mysql_alert($q->mysql_error);
        return false;
    }

    $id=is_array($ligne)?intval($ligne["ID"] ?? 0):0;
    if($id==0){
        $q->QUERY_SQL("INSERT INTO privs (groupid,dngroup) VALUES ('$groupid','$dnsql')");
        if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
        return admin_tracks("Set privileges on Reverse Proxy group #$groupid for group $dn");
    }
    $q->QUERY_SQL("DELETE FROM privs WHERE ID=$id");
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
    return admin_tracks("Delete privileges on Reverse Proxy group #$groupid for group $dn");
}


function privileges_save():bool{
    $tpl=new template_admin();
    // Granting privileges to a group is a system administrator's call: without
    // this check any console account could grant its own group any right.
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $dnRefused=isset($_POST["privs"])?(string)$_POST["privs"]:"";
        admin_tracks("Refused: define Web-console privileges on group $dnRefused (not a system administrator)");
        echo $tpl->post_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $tpl->CLEAN_POST();
    $ldap=new clladp();
    $dn=$_POST["privs"];
    $Hash=array();
    $SQLITE=false;
    $AD=true;
    if(preg_match("#sqlite:([0-9]+)#", $dn,$re)){
        $ID=$re[1];
        $AD=false;
        $grpData=acls_api_get("/admins/groups/$ID");
        $SQLITE=true;
        if(is_array($grpData) && isset($grpData["privileges"]) && $grpData["privileges"]!=="") {
            $Hash["ArticaGroupPrivileges"] = base64_decode($grpData["privileges"]);
        }
    }
    if($AD){
        $ct=new external_ad_search();
        $Hash=$ct->LoadGroupDataByDN($dn);
    }

    if(!isset($Hash["ArticaGroupPrivileges"])){
        $Hash["ArticaGroupPrivileges"]=$ldap->_ParsePrivieleges($Hash["ArticaGroupPrivileges"]);
    }

    if(!is_array($Hash["ArticaGroupPrivileges"])){
        writelogs("ldap->_ParsePrivieleges(...)",__FUNCTION__,__FILE__,__LINE__);
        $ArticaGroupPrivileges=$ldap->_ParsePrivieleges($Hash["ArticaGroupPrivileges"]);
    }else{
        $ArticaGroupPrivileges=$Hash["ArticaGroupPrivileges"];
    }
    $admLogs=array();
    if(is_array($ArticaGroupPrivileges)){
        foreach ($ArticaGroupPrivileges as $num=>$val){$GroupPrivilege[$num]=$val;}
    }
    $GroupPrivilegeNew=array();
    foreach ($_POST as $num=>$val){
        if(!is_numeric($val)){continue;}
        if($val==1){$val="yes";}else{$val="no";}
        $GroupPrivilege[$num]=$val;
    }
    foreach ($GroupPrivilege as $num=>$val){
        if($val=="no"){writelogs("[$num]=SKIP",__FUNCTION__,__FILE__,__LINE__);continue;}
        writelogs("[$num]=\"$val\"",__FUNCTION__,__FILE__,__LINE__);
        $admLogs[]="$num=$val";
        $GroupPrivilegeNew[]="[$num]=\"$val\"";
    }

    $values=@implode("\n",$GroupPrivilegeNew);

    if($SQLITE){
        $b64values=base64_encode($values);
        // Fetch current group to preserve groupName and enabled fields
        $currentGrp=acls_api_get("/admins/groups/$ID");
        $groupName=is_array($currentGrp)?($currentGrp["groupName"] ?? ""):"";
        $enabled=is_array($currentGrp)?intval($currentGrp["enabled"] ?? 1):1;
        list($ok,$errData)=acls_api_send("PUT","/admins/groups/$ID",[
            "groupName"=>$groupName,
            "privileges"=>$b64values,
            "enabled"=>$enabled,
        ]);
        if(!$ok){echo is_string($errData)?htmlspecialchars($errData):"API error";}
        return admin_tracks("Defines Web-console privileges ".@implode(",",$admLogs)." on group $dn");

    }

    $gp=new external_ad_search();
    $gp->SaveGroupPrivileges($values,$dn);
    return admin_tracks("Defines Web-console privileges ".@implode(",",$admLogs)." on group $dn");
}



function ChangeName(){
    if(grpad_privs_write_denied("rename",true)){return false;}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $newname=$_POST["NewName"];
    $group=new groups($_POST["gpid"]);
    if(trim($newname)<>null){
        if(trim(strtolower($newname)) <> trim(strtolower($group->groupName)) ){
            $gp=new groups($_POST["gpid"]);
            $actualdn=$gp->dn;
            if(preg_match('#cn=(.+?),(.+)#',$actualdn,$re)){$branch=$re[2];}
            $newdn="cn=$newname";
            $newdn2="$newdn,$branch";
            $ldap=new clladp();
            if($ldap->ExistsDN($newdn2)){return null;}
            writelogs("Rename $actualdn to $newdn",__CLASS__.'/'.__FUNCTION__,__FILE__);
            if(!$ldap->Ldap_rename_dn($newdn,$actualdn,$branch)){echo $tpl->_ENGINE_parse_body("{GROUP_RENAME} $group->groupName {to} $newname {failed}\n $ldap->ldap_last_error");}

        }
    }
    if(!$group->SaveDescription($_POST["description"])){echo $group->ldap_error;}

}

// Ecriture de privileges de groupe (blob ArticaGroupPrivileges, tenants SIEM, categories, ACL,
// reverse-proxy manage) : administrateur systeme seulement, controle COTE SERVEUR -- le verrou
// des tuiles/cases n'est qu'un affichage. Sans lui, tout compte connecte pouvait s'accorder
// AsArticaMetaAdmin ou AsSystemAdministrator sur son propre groupe. Rend true = REFUSE.
// Artica Meta multi-tenant : source et libelle d'un proprietaire de tenant pour ce groupe.
function grpad_meta_source($dn):string{
    return preg_match('#^sqlite:[0-9]+$#',(string)$dn)?"sqlite":"ad";
}
function grpad_meta_label($dn):string{
    if(preg_match('#^CN=([^,]+)#i',(string)$dn,$re)){return $re[1];}
    return (string)$dn;
}
// GET : JavaScript seulement (aucun changement d'etat) ; il POSTe la bascule.
function privileges_meta_tenant_js():bool{
    header("content-type: application/x-javascript");
    if(grpad_privs_write_denied("grant Artica Meta tenant ownership",false)){return false;}
    $dn=trim((string)$_GET["privileges-meta-tenant-js"]);
    if($dn==="" || preg_match('/[\x00-\x1f]/',$dn)){(new template_admin())->js_error("{meta_owner_invalid}");return false;}
    echo natn_group_owner_js(CurrentPageName(),"privileges-meta-tenant",$dn,intval($_GET["meta_tenant_id"] ?? 0));
    return true;
}
// POST : n'ecrit RIEN en cas de succes.
function privileges_meta_tenant():bool{
    $tpl=new template_admin();
    if(grpad_privs_write_denied("grant Artica Meta tenant ownership",true)){return false;}
    $dn=trim((string)$_POST["privileges-meta-tenant"]);
    if($dn==="" || preg_match('/[\x00-\x1f]/',$dn)){echo $tpl->post_error("{meta_owner_invalid}");return false;}
    $err=natn_group_owner_toggle(grpad_meta_source($dn),$dn,grpad_meta_label($dn),intval($_POST["meta_tenant_id"] ?? 0));
    if($err!==""){echo $tpl->post_error($err);return false;}
    return true;
}

function grpad_privs_write_denied($what,$post=false):bool{
    $users=new usersMenus();
    if($users->AsSystemAdministrator){return false;}
    $tpl=new template_admin();
    $target=isset($_POST["privs"])?(string)$_POST["privs"]:"";
    admin_tracks("Refused: $what on group $target (not a system administrator)");
    if($post){echo $tpl->post_error("{ERROR_NO_PRIVS}");}else{$tpl->js_error("{ERROR_NO_PRIVS}");}
    return true;
}
