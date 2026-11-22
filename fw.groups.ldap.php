<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
// grpad_switch_table()/grpad_switch_row() et siem_tenants_*(): briques partagees avec
// fw.groups.ad.php, qui rend le meme onglet « SIEM tenants » pour un groupe Active Directory.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.netagents.tenants.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["tabs"])){tabs();exit;}
// Dispatches EN TETE de fichier, au-dessus de js(): un ?xxx=yes rattrape plus bas laisserait
// d'abord partir la modale de js_dialog5, et l'onglet se rechargerait en boucle.
if(isset($_GET["privileges-tenants"])){privileges_tenants();exit;}
if(isset($_GET["privileges-tenants-enable"])){privileges_tenants_enable();exit;}
if(isset($_GET["privileges-meta-tenant-js"])){privileges_meta_tenant_js();exit;}
if(isset($_POST["privileges-meta-tenant"])){privileges_meta_tenant();exit;}
if(isset($_GET["profile"])){profile();exit;}
if(isset($_POST["NewName"])){ChangeName();exit;}
if(isset($_GET["members"])){members();exit;}
if(isset($_GET["members-table"])){members_table();exit;}
if(isset($_GET["link-group-js"])){link_group_js();exit;}
if(isset($_GET["link-group-popup"])){link_group_popup();exit;}
if(isset($_GET["link-js"])){link_member();exit;}
if(isset($_GET["unlink-js"])){unlink_members();exit;}
if(isset($_GET["privileges"])){privileges();exit;}
if(isset($_POST["privs"])){privileges_save();exit;}
js();

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=$_GET["gpid"];
    $gp=new groups($gpid);
    $tpl->js_dialog5("{organization} $gp->ou &raquo; {group2} {$gp->groupName}","$page?tabs=$gpid");
}
function link_group_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=$_GET["link-group-js"];
    $gp=new groups($gpid);
    $tpl->js_dialog6("{link_user} &raquo; {organization} $gp->ou &raquo; {group2} {$gp->groupName}","$page?link-group-popup=$gpid",650);
}
function link_group_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=$_GET["link-group-popup"];
    $gp=new groups($gpid);
    $ldap=new clladp();
    $HASH=$ldap->hash_users_ou($gp->ou);

    $tpl=new template_admin();
    $html[]=$tpl->_ENGINE_parse_body("
		<table id='table-my-userid-linkgrps' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";




    $TRCLASS=null;
    foreach ($HASH as $uid=>$DisplayName){
        $text_class=null;
        if(trim($uid)==null){continue;}
        if(isset($gp->members_array[$uid])){continue;}
        $md=md5($gpid.$uid);
        $memberenc=urlencode($uid);
        $js="Loadjs('fw.member.ldap.edit.php?uid=$memberenc')";
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td>". $tpl->td_href($DisplayName,"{click_to_edit}",$js)."</td>";
        $html[]="<td style='width:1%' nowrap>".$tpl->icon_select("Loadjs('$page?link-js=$gpid&uid=$memberenc&gpid=$gpid&md=$md')","AllowAddGroup") ."</td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-my-userid-linkgrps').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function link_member(){
    if(grpldap_privs_write_denied("add a member",false)){return false;}
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $gpid=$_GET["link-js"];
    $uid=$_GET["uid"];

    $ldap=new clladp();
    if($ldap->AddUserToGroup($gpid, $uid)){
        echo "$('#{$_GET["md"]}').remove();\n";
        echo "LoadAjaxTiny('MembersOfTheGroup','$page?members-table=$gpid');";
    }

}

function tabs(){
    $page=CurrentPageName();
    $gpid=$_GET["tabs"];
    $tpl=new template_admin();
    $gp=new groups($gpid);
    $EnableNginx = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $icon_group=ico_group;
    $icon_user=ico_users;
    $icon_privs=ico_lock;
    $icon_earth=ico_earth;
    $array["<i class='$icon_group'></i> $gp->groupName"]="$page?profile=$gpid";
    $array["<i class='$icon_user'></i> {members}"]="$page?members=$gpid";
    $array["<i class='$icon_privs'></i> {privileges}"]="$page?privileges=$gpid";
    if($EnableNginx==1){
        $array["<i class='$icon_earth'></i> {websites}"]="fw.nginx.privileges.php?gpid=$gpid";

    }
    // Pas d'onglet « SIEM tenants » ici: les tenants sont une SECTION de l'ecran des privileges
    // (siem_tenants_tiles()). C'est la que l'administrateur vient chercher ce qu'un groupe a le
    // droit de voir, et deux entrees pour un meme reglage laissent toujours croire qu'elles font
    // deux choses differentes.


    echo $tpl->tabs_default($array);
}

function members():bool{
    $page=CurrentPageName();
    $gpid=$_GET["members"];
    $html="<div id='MembersOfTheGroup'></div>
	<script>LoadAjaxTiny('MembersOfTheGroup','$page?members-table=$gpid');</script>";
    echo $html;
    return true;
}
function unlink_members(){
    if(grpldap_privs_write_denied("remove a member",false)){return false;}
    $gpid=$_GET["unlink-js"];
    $uid=$_GET["uid"];

    $gp=new groups($gpid);
    if($gp->DeleteUserFromThisGroup($uid)){
        echo "$('#{$_GET["md"]}').remove();\n";

    }

}

function members_table(){
    VERBOSE("START",__LINE__);
    $gpid       = $_GET["members-table"];
    $groups     = new groups($gpid);
    $ou         = $groups->ou;
    $ouenc      = urlencode($ou);
    $ldap       = new clladp();

    $page=CurrentPageName();
    $tpl=new template_admin();

    $CLUSTER_CLIENT=false;
    $EnableLDAPSyncProv=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProv"));
    $EnableLDAPSyncProvClient=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProvClient");
    if($EnableLDAPSyncProv==1){
        if($EnableLDAPSyncProvClient==1){
            $CLUSTER_CLIENT=true;
        }
    }

    VERBOSE("QUERY_DONE",__LINE__);

    if(!$CLUSTER_CLIENT) {
        $html[] = $tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>
			" . $tpl->button_label_table("{link_user}", "Loadjs('$page?link-group-js=$gpid')", "fa-plus", "AllowAddGroup") . "
			
			</div>");
    }

    $html[]=$tpl->_ENGINE_parse_body("
<table id='table-my-userid-FromGroup' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{displayname}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{email}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{phone}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{mobile}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;
    VERBOSE("LOOPING",__LINE__);
    $c=0;
    foreach ($groups->members as $member){
        $text_class     = null;
        $c++;
        $md             = md5($gpid.$member);
        $memberenc      = urlencode($member);
        $hash           = $ldap->hash_get_user_data_fast($member);
        if(!isset($hash["displayName"])){
            $hash["displayName"]="";
        }
        if(!isset($hash["mail"])){
            $hash["mail"]="root$c@unknown.com";
        }
        $sn=$hash["givenname"];
        $givenname=$hash["givenname"];
        $displayName    = trim($hash["displayName"]);
        $mail           = $hash["mail"];
        $telephonenumber= $hash["telephonenumber"];
        $mobile         = $hash["mobile"];
        if(strlen($displayName)<2){
            $displayName=trim("$givenname $sn");
        }
        if(strlen($displayName)<2){
            $displayName="$mail";
        }

        if($telephonenumber==null){$telephonenumber=$tpl->icon_nothing();}
        if($mobile==null){$mobile=$tpl->icon_nothing();}
        $unlink=$tpl->icon_unlink("Loadjs('$page?unlink-js=$gpid&ou=$ouenc&uid=$memberenc&gpid=$gpid&md=$md')","AllowAddGroup");
    
        if($CLUSTER_CLIENT){
            $unlink=$tpl->icon_unlink();
        }


        $js="Loadjs('fw.member.ldap.edit.php?uid=$memberenc')";
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td>". $tpl->td_href($displayName,"{click_to_edit}",$js)."</td>";
        $html[]="<td>". $tpl->td_href($mail,"{click_to_edit}",$js)."</td>";
        $html[]="<td style='width:1%' nowrap>$telephonenumber</td>";
        $html[]="<td style='width:1%' nowrap>$mobile</td>";
        $html[]="<td style='width:1%' nowrap>$unlink</td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-my-userid-FromGroup').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
</script>";
    VERBOSE("LOOPING DONE",__LINE__);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}



function profile(){
    $page=CurrentPageName();
    $gpid=$_GET["profile"];
    $tpl=new template_admin();
    $gp=new groups($gpid);

    $CLUSTER_CLIENT=false;
    $EnableLDAPSyncProv=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProv"));
    $EnableLDAPSyncProvClient=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProvClient");
    if($EnableLDAPSyncProv==1){
        if($EnableLDAPSyncProvClient==1){
            $CLUSTER_CLIENT=true;
        }
    }

    $bton="{apply}";
    if($CLUSTER_CLIENT){$bton=null;}

    $form[]=$tpl->field_hidden("gpid", $gpid);
    $form[]=$tpl->field_text("NewName", "{group2}", $gp->groupName);
    $form[]=$tpl->field_text("description", "{group_description}", $gp->description);
    $html[]=$tpl->form_outside("{parameters}", @implode("\n", $form),null,$bton,null,"AllowAddGroup");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function privileges():bool{
    $gpid=$_GET["privileges"];
    $group=new groups($gpid);
    $hash=$group->LoadDatas($gpid);
    $tpl=new template_admin();
    $priv=new usersMenus();
    if($gpid==544){
        include_once(dirname(__FILE__)."/ressources/class.translate.rights.inc");
        $pp=new TranslateRights();
        $pt=$pp->GetPrivsArray();
        foreach ($pt as $num=>$ligne){$hash["ArticaGroupPrivileges"][$num]="yes";}
    }
    $HashPrivieleges=$hash["ArticaGroupPrivileges"];
    if(!is_array($HashPrivieleges)){$HashPrivieleges=array();}
    if($_SESSION["uid"]==-100){
        $priv->AsArticaAdministrator=true;
        $priv->AsFirewallManager=true;
        $priv->AsVPNManager=true;
    }

    // Les privileges sont rendus en TUILES sur deux colonnes: vingt et un droits
    // empiles en une colonne unique obligent a lire toute la liste pour en
    // trouver un, et laissent la moitie de la largeur vide.
    $sections=array();
    $sections["{groups_allow}"]=array("AllowAddUsers","AllowAddGroup","AsOrgAdmin");
    $sections["{proxy_allow}"]=array("AsWebStatisticsAdministrator","AsDansGuardianAdministrator",
        "AsSquidAdministrator","AsHotSpotManager","AsProxyMonitor");
    $sections["{administrators_allow}"]=array("AsArticaAdministrator","AsFirewallManager","AsVPNManager",
        "AsDnsAdministrator","ASDCHPAdmin","AsDatabaseAdministrator","AsDockerAdmin","AsSambaAdministrator");
    // Artica Meta multi-tenant : administrateur des tenants (clients) dont le groupe est proprietaire.
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMeta"))==1){
        $sections["{APP_ARTICA_META}"]=array("AsMetaAdmin");
    }

    $tiles=array();
    foreach($sections as $sectitle=>$privs){
        $tiles[]=$tpl->field_tiles_section($sectitle);
        foreach($privs as $p){
            // AsDockerAdmin est le seul droit dont le VERROU differe du nom du
            // champ: il se pilote avec AsSystemAdministrator.
            $privRights=($p=="AsDockerAdmin" || $p=="AsMetaAdmin")?"AsSystemAdministrator":$p;
            $value=isset($HashPrivieleges[$p])?$HashPrivieleges[$p]:0;
            $tiles[]=$tpl->field_checkbox_tile($p,"{{$p}}",$value,grpad_priv_icon($p),$p,$privRights);
        }
    }

    // Quatrieme section: les tenants SIEM que ce groupe a le droit de consulter.
    //
    // Ces tuiles vivent dans la MEME grille que les droits ci-dessus parce que c'est le seul
    // endroit ou l'administrateur vient chercher « ce que ce groupe a le droit de voir ». Elles
    // ne sont pourtant PAS des droits de la meme nature: un tenant n'est pas une cle du blob
    // ArticaGroupPrivileges, c'est une ligne d'une table de liaison (un groupe peut en porter
    // plusieurs, et l'utilisateur cumule ceux de tous ses groupes). D'ou field_switch_tile(),
    // qui ecrit immediatement par AJAX et ne s'inscrit pas au formulaire: si ces cases etaient
    // postees avec « Apply », leurs noms atterriraient dans le blob de privileges du groupe.
    $tiles=array_merge($tiles,siem_tenants_tiles(siem_tenants_groupkey($gpid),CurrentPageName(),$tpl));
    // Tenants Artica Meta dont ce groupe est proprietaire (cle = gidNumber, source "ldap" : ce que
    // la connexion relit, comme les tenants SIEM). Infogerant / administrateur systeme seulement.
    $tiles=array_merge($tiles,natn_group_owner_tiles("ldap",siem_tenants_groupkey($gpid),(string)$group->groupName,CurrentPageName(),"privileges-meta-tenant",$tpl));

    $form=array();
    $form[]=$tpl->field_hidden("privs", $gpid);
    $form[]=$tpl->field_tiles_grid($tiles,2);

    $html[]=$tpl->form_outside("", @implode("\n", $form),null,"{apply}","blur()","AsSystemAdministrator");

    // La feuille de style et le liant JS sont concatenes AUTOUR de l'analyse:
    // la regex #{(.+?)}# de _ENGINE_parse_body lirait chaque corps de regle CSS
    // et chaque accolade JavaScript comme un {token}.
    echo $tpl->field_tiles_css()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$tpl->field_tiles_js();
    return true;
}


function privileges_save(){
    if(grpldap_privs_write_denied("define Web-console privileges",true)){return false;}
    $ldap=new clladp();
    // On instancie le groupe plutot que d'appeler $ldap->GroupDatas(): celui-ci
    // ne rend que main_array, or il faut AUSSI la liste des objectClass (voir
    // plus bas). Le constructeur appelle deja LoadDatas() -- qui ne prend aucun
    // argument, contrairement a ce que laissent croire quelques appelants.
    $gpid=$_POST["privs"];
    $group=new groups($gpid);
    $Hash=$group->main_array;

    if(!is_array($Hash["ArticaGroupPrivileges"])){
        writelogs("ldap->_ParsePrivieleges(...)",__FUNCTION__,__FILE__,__LINE__);
        $ArticaGroupPrivileges=$ldap->_ParsePrivieleges($Hash["ArticaGroupPrivileges"]);
    }else{
        $ArticaGroupPrivileges=$Hash["ArticaGroupPrivileges"];
    }

    // Les deux tableaux sont initialises AVANT d'etre remplis.
    //
    // $GroupPrivilegeNew ne recevait sa premiere valeur que dans la branche « ce droit est
    // accorde »: un groupe dont TOUS les droits sont a « no » -- l'etat de tout groupe
    // fraichement cree -- laissait la variable a null, et implode() sur null leve une TypeError
    // en PHP 8. Le @ ne l'attrapait pas: il masque les avertissements, pas les exceptions. Le
    // resultat etait un 500 a corps vide, donc une page qui ne dit rien du tout.
    $GroupPrivilege=array();
    $GroupPrivilegeNew=array();

    if(is_array($ArticaGroupPrivileges)){
        foreach ($ArticaGroupPrivileges as $num=>$val){
            $GroupPrivilege[$num]=$val;
        }
    }


    foreach ($_POST as $num=>$val){
        // `privs` porte l'identifiant du groupe, pas un droit. Il est numerique, donc la boucle
        // le prenait pour une case a cocher: un groupe d'identifiant 1 se serait vu ecrire un
        // droit nomme [privs]="yes" dans son propre blob.
        if($num=="privs"){continue;}
        if(!is_numeric($val)){continue;}
        if($val==1){$val="yes";}else{$val="no";}
        $GroupPrivilege[$num]=$val;
    }
    foreach ($GroupPrivilege as $num=>$val){
        if($val=="no"){writelogs("[$num]=SKIP",__FUNCTION__,__FILE__,__LINE__);continue;}
        writelogs("[$num]=\"$val\"",__FUNCTION__,__FILE__,__LINE__);
        $GroupPrivilegeNew[]="[$num]=\"$val\"";
    }

    // Aucun droit accorde => une chaine vide, ce qui est un etat legitime et doit s'ecrire.
    $values=@implode("\n",$GroupPrivilegeNew);
    $update_array["ArticaGroupPrivileges"][0]=$values;

    // ArticaGroupPrivileges n'est declare que par l'objectClass AUXILIAIRE
    // ArticaSettings (postfix.schema, OID 1.3.6.1.4.1.15347.2.3.1.2). Un groupe
    // qui ne la porte pas fait refuser l'ecriture par le schema -- « Object class
    // violation », erreur LDAP 65 -- et l'ecran ne montrait RIEN parce que la
    // branche d'erreur mourait elle-meme sur un count() (cf. class.ldap.inc).
    //
    // Tous les groupes ne l'ont pas: add_new_group() la pose, mais les groupes
    // arrivant par import ou par une version anterieure ne l'ont jamais eue. On
    // la rajoute donc a la volee, dans le MEME modify -- l'attribut objectClass
    // etant remplace en bloc par ldap_modify(), on renvoie la liste complete.
    if(!isset($group->objectClasses["ArticaSettings"])){
        $classes=array_keys($group->objectClasses);
        $classes[]="ArticaSettings";
        $update_array["objectClass"]=array_values(array_unique($classes));
    }

    if(!$ldap->Ldap_modify($Hash["dn"],$update_array)){
        // form_outside() traite toute reponse non vide comme une erreur: on
        // n'echo QUE le message, jamais sur le chemin nominal.
        $tpl=new template_admin();
        echo $tpl->post_error($ldap->ldap_last_error);
    }


}

/**
 * Cle de groupe pour siem_tenants_privs.
 *
 * Un groupe LDAP est identifie par son gidNumber -- c'est EXACTEMENT la valeur que
 * class.privileges.inc passe a SiemTenantsPrivs() en resolvant les groupes posixGroup
 * (`$this->SiemTenantsPrivs($gidnumber)`). Ecrire ici la moindre autre forme (le DN, le cn)
 * produirait une attribution que la resolution ne retrouverait jamais: la case resterait cochee
 * a l'ecran et l'utilisateur ne verrait aucun tenant, sans le moindre message.
 *
 * Le controle numerique n'est donc pas defensif par principe: il refuse une cle qui ne pourrait
 * pas etre relue. Il garantit aussi qu'aucune valeur LDAP ne peut ressembler a un DN de groupe
 * Active Directory, les deux formes cohabitant dans la meme colonne.
 */
function siem_tenants_groupkey($raw=null){
    if($raw===null){
        $raw=isset($_GET["privileges-tenants"])?$_GET["privileges-tenants"]:
            (isset($_GET["privileges-tenants-enable"])?$_GET["privileges-tenants-enable"]:"");
    }
    $gpid=trim((string)$raw);
    if($gpid===""||!is_numeric($gpid)){return "";}
    return (string)intval($gpid);
}


function privileges_tenants(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=siem_tenants_groupkey();
    if($gpid===""){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{siem_tenants_unknown_group}"));
        return;
    }

    if(!class_exists("postgres_sql")){include_once(dirname(__FILE__)."/ressources/class.postgres.inc");}
    $q=new postgres_sql();
    if(!siem_tenants_create_table($q)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($q->mysql_error)));
        return;
    }
    $gpsql=pg_escape_string2($gpid);

    $ENABLED=array();
    $results=$q->QUERY_SQL("SELECT tenant_id FROM siem_tenants_privs WHERE dngroup='$gpsql'");
    if(!$q->ok){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($q->mysql_error)));
        return;
    }
    while($ligne=pg_fetch_assoc($results)){
        $ENABLED[(string)$ligne["tenant_id"]]=true;
    }

    // Nettoyage des references orphelines, comme cote AD: un tenant supprime cote SIEM laisse une
    // ligne inerte que plus personne ne peut voir ni retirer par l'interface.
    $available=siem_tenants_available();
    if(is_array($available) && count($ENABLED)>0){
        $orphans=array();
        foreach(array_keys($ENABLED) as $known){
            if(!isset($available[$known])){$orphans[]=pg_escape_string2((string)$known);}
        }
        if(count($orphans)>0){
            $q->QUERY_SQL("DELETE FROM siem_tenants_privs WHERE dngroup='$gpsql' AND tenant_id IN ('".@implode("','",$orphans)."')");
            foreach($orphans as $o){unset($ENABLED[$o]);}
        }
    }
    if($available===null){
        // « Liste indisponible » n'est PAS « aucun tenant »: les confondre ferait croire a
        // l'administrateur que les attributions ont ete perdues.
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{siem_tenants_unavailable}"));
        return;
    }

    $rows=array();
    foreach($available as $tenant_id=>$display){
        $rows[]=grpad_switch_row($display,$tenant_id,
            isset($ENABLED[$tenant_id])?1:0,
            "Loadjs('$page?privileges-tenants-enable=".urlencode($gpid)."&tenant_id=".urlencode($tenant_id)."')",
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
        .grpad_switch_table($rows,"{tenant}","grpldap-siemt",$tpl);
}

// Bascule "proprietaire d'un tenant Artica Meta" de ce groupe LDAP (tuile, Loadjs). Meme droit que
// les autres privileges du groupe (AsSystemAdministrator), puis natn_group_owner_toggle() qui
// remplace les proprietaires du tenant et invalide les privileges caches.
// GET : JavaScript seulement (aucun changement d'etat) ; il POSTe la bascule.
function privileges_meta_tenant_js():bool{
    header("content-type: application/x-javascript");
    if(grpldap_privs_write_denied("grant Artica Meta tenant ownership",false)){return false;}
    $ref=siem_tenants_groupkey($_GET["privileges-meta-tenant-js"]);
    if($ref===""){(new template_admin())->js_error("{meta_owner_invalid}");return false;}
    echo natn_group_owner_js(CurrentPageName(),"privileges-meta-tenant",$ref,intval($_GET["meta_tenant_id"] ?? 0));
    return true;
}
// POST : n'ecrit RIEN en cas de succes. Libelle relu cote serveur (nom du groupe LDAP).
function privileges_meta_tenant():bool{
    $tpl=new template_admin();
    if(grpldap_privs_write_denied("grant Artica Meta tenant ownership",true)){return false;}
    $ref=siem_tenants_groupkey($_POST["privileges-meta-tenant"]);
    if($ref===""){echo $tpl->post_error("{meta_owner_invalid}");return false;}
    $g=new groups($ref);
    $label=substr(trim((string)$g->groupName),0,255);
    $err=natn_group_owner_toggle("ldap",$ref,$label,intval($_POST["meta_tenant_id"] ?? 0));
    if($err!==""){echo $tpl->post_error($err);return false;}
    return true;
}

function privileges_tenants_enable():bool{
    if(grpldap_privs_write_denied("grant SIEM tenants",false)){return false;}
    $tpl=new template_admin();
    $gpid=siem_tenants_groupkey();
    if($gpid===""){$tpl->js_error("{siem_tenants_unknown_group}");return false;}
    $tenant_id=trim((string)(isset($_GET["tenant_id"])?$_GET["tenant_id"]:""));
    if($tenant_id===""){$tpl->js_error("{siem_tenants_unknown}");return false;}

    // Le tenant doit exister et etre actif AU MOMENT de la bascule: sans ce controle, une case
    // cochee sur un tenant disparu creerait un droit que plus rien n'explique ni ne retire.
    $available=siem_tenants_available();
    if($available===null){$tpl->js_error("{siem_tenants_unavailable}");return false;}
    if(!isset($available[$tenant_id])){$tpl->js_error("{siem_tenants_unknown}");return false;}

    if(!class_exists("postgres_sql")){include_once(dirname(__FILE__)."/ressources/class.postgres.inc");}
    $q=new postgres_sql();
    if(!siem_tenants_create_table($q)){$tpl->js_error($q->mysql_error);return false;}

    $gpsql=pg_escape_string2($gpid);
    $tidsql=pg_escape_string2($tenant_id);

    // mysqli_fetch_array() et non QUERY_SQL(): cette derniere renvoie une RESSOURCE de resultat, et
    // la lire comme un tableau laisse $id a 0 -- la bascule inserait alors a chaque clic sans
    // jamais supprimer. Le bug a deja ete paye sur personal_categories_privs.
    $ligne=$q->mysqli_fetch_array("SELECT id FROM siem_tenants_privs WHERE dngroup='$gpsql' and tenant_id='$tidsql'");
    if(!$q->ok){$tpl->js_error($q->mysql_error);return false;}

    $id=is_array($ligne)?intval($ligne["id"] ?? 0):0;
    if($id==0){
        $q->QUERY_SQL("INSERT INTO siem_tenants_privs (tenant_id,dngroup) VALUES ('$tidsql','$gpsql')");
        if(!$q->ok){$tpl->js_error($q->mysql_error);return false;}
        return admin_tracks("Grant SIEM tenant $tenant_id to LDAP group gid $gpid");
    }
    $q->QUERY_SQL("DELETE FROM siem_tenants_privs WHERE id=$id");
    if(!$q->ok){$tpl->js_error($q->mysql_error);return false;}
    return admin_tracks("Revoke SIEM tenant $tenant_id from LDAP group gid $gpid");
}



function ChangeName(){
    if(grpldap_privs_write_denied("rename",true)){return false;}

    $branch=null;
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
function grpldap_privs_write_denied($what,$post=false):bool{
    $users=new usersMenus();
    if($users->AsSystemAdministrator){return false;}
    $tpl=new template_admin();
    $target=isset($_POST["privs"])?(string)$_POST["privs"]:"";
    admin_tracks("Refused: $what on group $target (not a system administrator)");
    if($post){echo $tpl->post_error("{ERROR_NO_PRIVS}");}else{$tpl->js_error("{ERROR_NO_PRIVS}");}
    return true;
}
