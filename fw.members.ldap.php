<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10 - Service Pack 10 HF 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

// Les repartiteurs ?enable-signature= et ?enable-firewall= appelaient
// enable_signature() et enable_firewall(), qui ne sont definies NI dans ce
// fichier NI ailleurs: les deux URL repondaient un fatal « Call to undefined
// function ». La paire appartient a la famille Suricata (elle bascule une
// signature dans suricata-rules.db et un drapeau dans suricata_sig) et n'a
// rien a faire ici; seul fw.ids.rules.php produit reellement
// « ?enable-signature= ».
// Repartiteur(s) mort(s) retire(s): rule_settings()/rule_save() ne sont
// definies NI dans ce fichier NI ailleurs, et aucun code du depot ne produit
// le parametre qui les declenchait -- ni cette page, ni une autre. Les URL
// repondaient donc un fatal « Call to undefined function ». Vestige d'un
// squelette de page recopie; les pages qui utilisent VRAIMENT ?rule-popup=
// ou ?rule-settings= definissent leur propre handler.
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["none"])){exit;}
if(isset($_GET["organizations-js"])){organizations_js();exit;}
if(isset($_GET["organizations-popup"])){organizations_popup();exit;}
// La table des organisations passe aussi par search_block (?search=...): on la
// distingue de la table des membres par le marqueur &orgs=yes, DISPATCHE AVANT
// le ?search generique ci-dessous, sinon table() (membres) l'intercepterait.
if(isset($_GET["orgs"]) && isset($_GET["search"])){organizations_table();exit;}
if(isset($_GET["org-delete-js"])){org_delete_js();exit;}
if(isset($_POST["org-delete"])){org_delete_perform();exit;}
if(isset($_GET["export-download"])){export_download();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["new-js"])){new_js();exit;}
if(isset($_GET["new-popup"])){new_popup();exit;}
if(isset($_GET["new-organization"])){new_organization();exit;}
if(isset($_GET["new-organization-js"])){new_organization_js();exit;}
if(isset($_GET["new-organization-popup"])){new_organization_popup();exit;}
if(isset($_GET["new-group"])){new_group();exit;}
if(isset($_POST["AddOU"])){new_organization_save();exit;}
if(isset($_POST["AddGroup"])){new_group_save();exit;}
if(isset($_GET["new-member"])){new_member();exit;}
if(isset($_POST["login"])){new_member_save();exit;}
if(isset($_GET["import-js"])){import_member_js();exit;}
if(isset($_GET["import-popup"])){import_member_popup();exit;}
if(isset($_GET["import-organization"])){import_member_organization();exit;}
if(isset($_GET["import-group"])){import_group();exit;}
if(isset($_GET["import-member-final"])){import_members_final();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["delete-user-js"])){delete_user_js();exit;}
if(isset($_POST["delete-user"])){delete_user();exit;}

if(isset($_GET["delete-group-ask"])){delete_group_ask();exit;}
if(isset($_POST["delete-group"])){delete_group_perform();exit;}

if(isset($_GET["export-js"])){export_js();exit;}
if(isset($_GET["export-confirm"])){export_confirm();exit;}
if(isset($_GET["export-popup"])){export_popup();exit;}
if(isset($_GET["export-final"])){export_final();exit;}
if(isset($_GET["filter-js"])){filter_js();exit;}
page();

function new_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!isset($_GET["function"])){$_GET["function"]=null;}
    $tpl->js_dialog1("{new_member}", "$page?new-popup=yes&function={$_GET["function"]}");
}
function export_confirm(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{export} {members}", "$page?export-popup=yes",550);

}
function export_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_confirm_execute("{export} {members}","none","none","Loadjs('$page?export-confirm=yes')");
}
function export_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<H2>{export} {members}</H2>";
    $html[]="<div id='export-ldap-member-progress'></div>";
    $html[]="<div id='export-ldap-member-final'></div>";

    // PROGRESS_DIR vaut ressources/logs/web : c'est la que notifs.BuildProgress
    // ecrit cote daemon. L'ancien chemin littéral (.../ressources/logs/...) n'y
    // pointe pas, et la fenetre resterait figee a 2 %.
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ldap.import.members";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/ldap.import.members.txt";
    $ARRAY["CMD"]="/openldap/export/members";
    $ARRAY["TITLE"]="{exporting} {members}";
    $ARRAY["AFTER"]="LoadAjax('export-ldap-member-final','$page?export-final=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=export-ldap-member-progress')";
    $html[]="<script>$jsrestart;</script>";
    echo $tpl->_ENGINE_parse_body($html);

}

function delete_group_perform(){
    if(mbrsldap_denied("delete a directory group",array("AllowAddGroup","AsOrgAdmin"))){return false;}
    $gid        = intval($_POST["delete-group"]);
    $grp        = new groups($gid);

    if(!$grp->Delete()){
        echo $grp->ldap_error;
        return;
    }

    return true;
}

function delete_group_ask(){
    $tpl        = new template_admin();
    $gid        = intval($_GET["delete-group-ask"]);
    $grp        = new groups($gid);
    $GroupName  = $grp->groupName;
    $md         = $_GET["md"];


    $tpl->js_confirm_delete($GroupName,"delete-group",$gid,"$('#$md').remove()");
    return true;
}

function export_final(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $file_path="/var/lib/artica-openldap/ldap_members.gz";

    if(!is_file($file_path)){
        $html[]="
                <div class=\"widget style1 red-bg\">
                    <div class=\"row\">
                        <div class=\"col-xs-4\">
                            <i class=\"fas fa-exclamation fa-5x\"></i>
                        </div>
                        <div class=\"col-xs-8 text-right\">
                            <span> {failed} </span>
                            <h2 class=\"font-bold\">ldap_members.gz Error!</h2>
                        </div>
                    </div>
                </div>
            ";

        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }
    //<i class="fas fa-exclamation"></i>
    // <i class="fas fa-file-archive"></i>
    $filesize=FormatBytes(@filesize($file_path)/1024);

    $html[]="<a href='".CurrentPageName()."?export-download=yes'>
                <div class=\"widget style1 navy-bg\">
                    <div class=\"row\">
                        <div class=\"col-xs-4\">
                            <i class=\"fas fa-file-archive fa-5x\"></i>
                        </div>
                        <div class=\"col-xs-8 text-right\">
                            <span> {members} </span>
                            <h2 class=\"font-bold\">ldap_members.gz <small style='color:white'>($filesize)</small></h2>
                        </div>
                    </div>
                </div></a>
            ";

    echo $tpl->_ENGINE_parse_body($html);
    return false;
}

function export_download(){
    // L'archive porte les mots de passe en clair : elle vit hors de l'arbre web
    // (aperms remet ressources/logs en 0755 toutes les 4 h) et n'est servie que
    // par cette page, donc sous la session de la console.
    // Le chemin est en dur : aucun parametre d'entree ne le compose.
    $file_path="/var/lib/artica-openldap/ldap_members.gz";
    if(!is_file($file_path)){
        header("HTTP/1.1 404 Not Found");
        header("content-type: text/plain");
        echo "ldap_members.gz: no such file - run the export first\n";
        return;
    }
    header("content-type: application/gzip");
    header("content-disposition: attachment; filename=\"ldap_members.gz\"");
    header("content-length: ".filesize($file_path));
    readfile($file_path);
}

function delete_user_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $uid=$_GET["delete-user-js"];
    $md=$_GET["md"];
    $tpl->js_confirm_delete($uid,"delete-user",$uid,"$('#$md').remove()");
}
function delete_user(){
    if(mbrsldap_denied("delete a directory member",array("AllowAddUsers","AsOrgAdmin"))){return false;}
    // Delete via the daemon /openldap API (no direct LDAP). The js_confirm_delete
    // caller treats any echoed text (len>3) as an error, so stay silent on success.
    $uid=$_POST["delete-user"];
    $r=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/openldap/members/".urlencode($uid)));
    if(isset($r->Status) && !$r->Status){
        echo "Failed to delete $uid: ".@$r->Error;
    }
}

function import_member_js(){
    if(mbrsldap_import_denied()){return false;}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{importing_form_text_file}", "$page?import-popup=yes");
}
function import_member_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $def="LoadAjaxTiny('import-user-div','$page?import-organization=yes')";
    $EnableMultipleOrganizations=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMultipleOrganizations"));
    if($EnableMultipleOrganizations==0) {
        $ldap = new clladp();
        $hash = $ldap->hash_get_ou(false);
        if (count($hash) > 0) {
            $def="LoadAjaxTiny('import-user-div','$page?import-group=yes');";
            $_SESSION["ADD_USER_OU"]=$hash[0];
        }

    }

    $html="
	<div id='import-user-progress'></div>		
	<div id='import-user-div'></div>
	<script>$def</script>		
	";

    echo $html;

}
function import_member_organization(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ldap=new clladp();
    $hash=$ldap->hash_get_ou(true);


    $form[]=$tpl->field_text("AddOU", "{create_a_new_organization}", null,false);



    if(count($hash)==0){
        echo $tpl->form_outside("{new_member} &raquo; {create_a_new_organization}", @implode("\n", $form),"{add_new_organisation_text}","{add}",
            "LoadAjaxTiny('import-user-div','$page?import-group=yes');","AllowAddUsers");
        return;
    }



    $form[]=$tpl->field_array_hash($hash, "ChooseOU", "{organization}", $_SESSION["ADD_USER_OU"],false);
    echo $tpl->form_outside("{import} &raquo; {your_organization}", @implode("\n", $form),null,"{next}",
        "LoadAjaxTiny('import-user-div','$page?import-group=yes');","AllowAddUsers");

}

function new_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $def="LoadAjaxTiny('create-user-div','$page?new-organization=yes')";
    $EnableMultipleOrganizations=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMultipleOrganizations"));

    if($EnableMultipleOrganizations==0) {
        $ldap = new clladp();
        $hash = $ldap->hash_get_ou(false);
        if (count($hash) > 0) {
            $def="LoadAjaxTiny('create-user-div','$page?new-group=yes&function={$_GET["function"]}');";
            $_SESSION["ADD_USER_OU"]=$hash[0];
        }

    }


    $html="
	<div id='create-user-progress'></div>		
	<div id='create-user-div'></div>
	<script>$def</script>		
	";

    echo $html;
}
// ==================== Organizations manager (list/search/add/delete) ==========

// Bouton "Organizations": ouvre la modale de gestion des organisations.
function organizations_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $tpl->js_dialog2("{organizations}","$page?organizations-popup=yes&function=$function",900);
}

// Corps de la modale: explication, bouton d'ajout, et la table recherchable. Le
// bouton d'ajout reutilise le formulaire new-organization et rafraichit la table
// via OrgTableReload (alias defini par organizations_table sur la fn search_block).
function organizations_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $addjs="Loadjs('$page?new-organization-js=yes&function=OrgTableReload')";
    $html=array();
    $html[]=$tpl->div_explain("{organizations}||{ldap_organizations_explain}");
    $html[]="<div style='margin:10px 0'><label class=\"btn btn-primary\" OnClick=\"$addjs\"><i class='fas fa-plus'></i> {new_organization}</label></div>";
    $html[]="<div style='margin-top:10px'>".$tpl->search_block($page,"","","","&orgs=yes")."</div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

// Repondeur ?orgs=yes&search=... : liste filtree des organisations (via le demon
// /openldap/ous) dans un tableau awc avec suppression par ligne.
function organizations_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $search=trim((string)($_GET["search"] ?? ""));
    if($search=="*"){$search="";}

    $ous=array();
    $r=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openldap/ous"));
    if(isset($r->Data) && is_array($r->Data)){
        foreach($r->Data as $o){$o=trim((string)$o);if($o!==""){$ous[]=$o;}}
    }
    sort($ous);

    $rows=array();
    foreach($ous as $o){
        if($search!="" && stripos($o,$search)===false){continue;}
        $oenc=rawurlencode($o);
        $del=$tpl->icon_delete("Loadjs('$page?org-delete-js=$oenc')","AllowAddUsers");
        $h ="<tr>";
        $h.="<td><div class='awc-ident'><span class='awc-ico'><i class='fas fa-building'></i></span>".
            "<span><span class='awc-name'>".htmlspecialchars($o)."</span></span></div></td>";
        $h.="<td><div class='awc-acts'>$del</div></td>";
        $h.="</tr>";
        $rows[]=$h;
    }

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $html[]="<thead><tr><th>{organization}</th><th style='text-align:right'>{delete}</th></tr></thead><tbody id='org-rows'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>".count($rows)." {organizations}</span><span class='awc-pg' id='org-pager'></span></div>";
    $html[]="</div></div>";
    // Alias stable pour que l'ajout/la suppression rafraichissent la table en
    // conservant le filtre courant (fn generee par search_block).
    if($function!=""){
        $html[]="<script>window.OrgTableReload=function(){ if(typeof ".$function."==='function'){ ".$function."(); } };</script>";
    }else{
        $html[]="<script>window.OrgTableReload=window.OrgTableReload||function(){};</script>";
    }

    $iconjs="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$iconjs
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"org-rows","org-pager");
}

// icon_delete n'a pas de confirmation: on la cable ici sur js_confirm_delete, en
// avertissant que la suppression est RECURSIVE (membres + groupes de l'org).
function org_delete_js(){
    $tpl=new template_admin();
    $org=rawurldecode((string)($_GET["org-delete-js"] ?? ""));
    $warn="{delete_this_item}: <strong style='font-size:16px'>$org</strong><br>{ldap_organizations_explain}";
    $tpl->js_confirm_delete($warn,"org-delete",$org,"OrgTableReload();",true);
}

// Suppression effective. Contrat js_confirm_delete: toute reponse de plus de 3
// caracteres est une erreur -> on n'echo QUE le message d'erreur, rien en succes.
function org_delete_perform(){
    if(mbrsldap_denied("delete an organization",array("AsOrgAdmin"))){return false;}
    $tpl=new template_admin();
    $org=trim((string)($_POST["org-delete"] ?? ""));
    if($org===""){echo $tpl->_ENGINE_parse_body("{organization}: {cannot_be_a_null_value}");return;}
    if(in_array(strtolower($org),array("users","groups","computers","pureftpd","root","_global"))){
        echo $tpl->_ENGINE_parse_body("{organization} \"$org\": {reserved}");return;
    }
    // On ne supprime qu'une organisation reellement presente dans l'annuaire.
    $ous=array();
    $r=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openldap/ous"));
    if(isset($r->Data) && is_array($r->Data)){
        foreach($r->Data as $o){$ous[]=trim((string)$o);}
    }
    if(!in_array($org,$ous,true)){echo $tpl->_ENGINE_parse_body("{unknown} {organization}: $org");return;}

    $ldap=new clladp();
    $dn="ou=$org,dc=organizations,{$ldap->suffix}";
    if(!$ldap->ldap_delete($ldap->dn_escape($dn),true)){
        echo ($ldap->ldap_last_error!=null)?$ldap->ldap_last_error:"delete failed";
        return;
    }
    admin_tracks("Deleted LDAP organization $org and all its members/groups");
}

// Bouton dedie "Nouvelle organisation": ouvre une vraie modale (Loadjs evalue la
// reponse comme du JS, donc l'opener DOIT emettre js_dialog*, pas du HTML).
function new_organization_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $tpl->js_dialog4("{create_a_new_organization}","$page?new-organization-popup=yes&function=$function",550);
}

// Corps de la modale: le champ AddOU est poste vers new_organization_save().
// form_outside ne lance son $jsafter QUE sur une reponse vide (succes), sinon il
// affiche le texte comme erreur -- new_organization_save() respecte deja ce contrat.
function new_organization_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $form[]=$tpl->field_text("AddOU","{create_a_new_organization}",null,true);
    $after="dialogInstance4.close();";
    if($function!=""){$after.="$function();";}
    $html[]=$tpl->div_explain("{create_a_new_organization}||{add_new_organisation_text}");
    $html[]=$tpl->form_outside("",@implode("\n",$form),"","{add}",$after,"AllowAddUsers");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function new_organization():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ldap=new clladp();
    $hash=$ldap->hash_get_ou(true);
    $function="";
    if(isset($_GET["function"])){
        // $_GET["function"] part BRUT dans OnClick / $jsafter: liste blanche a la lecture.
        $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    }
    $form[]=$tpl->field_text("AddOU", "{create_a_new_organization}", null);
    if(count($hash)==0){
        echo $tpl->form_outside("{new_member} &raquo; {create_a_new_organization}", @implode("\n", $form),"{add_new_organisation_text}","{add}",
            "LoadAjaxTiny('create-user-div','$page?new-group=yes&function=$function');","AllowAddUsers");
        return true;
    }
    $form[]=$tpl->field_array_hash($hash, "ChooseOU", "{organization}", $_SESSION["ADD_USER_OU"]);
    echo $tpl->form_outside("{new_member} &raquo; {your_organization}", @implode("\n", $form),null,"{next}",
        "LoadAjaxTiny('create-user-div','$page?new-group=yes&function=$function');","AllowAddUsers");

    return true;
}
function import_group(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ldap=new clladp();
    $form[]=$tpl->field_info("ou", "{organization}", $_SESSION["ADD_USER_OU"]);

    $hash_groups=$ldap->hash_groups($_SESSION["ADD_USER_OU"],1);


    $form[]=$tpl->field_text("AddGroup", "{new_group}", null,false);
    $form[]=$tpl->field_array_hash($hash_groups, "ChooseGroup", "{group2}", $_SESSION["ADD_USER_GROUP"],false);

    echo $tpl->form_outside("{import} &raquo; {$_SESSION["ADD_USER_OU"]} &raquo; {group2}", @implode("\n", $form),"{LDAP_MEMBER_IMPOURT_GROUP_EXP}","{next}",
        "LoadAjaxTiny('import-user-div','$page?import-member-final=yes');","AllowAddUsers");

}

function import_members_final(){
    $EnableVirtualDomainsInMailBoxes=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVirtualDomainsInMailBoxes");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ldap=new clladp();
    $hash_groups=$ldap->hash_groups($_SESSION["ADD_USER_OU"],1);
    if(!is_numeric($_SESSION["ADD_USER_GROUP"])){
        foreach ($hash_groups as $id=>$xname){
            if(trim(strtolower($xname))==trim(strtolower($_SESSION["ADD_USER_GROUP"]))){
                $gpid=$id;
                break;
            }
        }

    }else{
        $gpid=intval($_SESSION["ADD_USER_GROUP"]);
    }
    $ou=urlencode($_SESSION["ADD_USER_OU"]);

    $addparams="&ou=$ou&gpid=$gpid";
    if($gpid>0) {
        $html[] = "<H2>{import} {$_SESSION["ADD_USER_OU"]}/{$hash_groups[$gpid]}</H2>";
        $html[] = "<div class='alert alert-info'>{importuser_text}<br>{importuser_aliases_text}</div>";
    }else{
        $html[] = "<H2>{import} {$_SESSION["ADD_USER_OU"]}</H2>";
        $html[] = "<div class='alert alert-info'>{imporldaptuser_text2}</div>";
    }
    // Mode multi-domaines : CreateUser impose uid = adresse mail, la colonne
    // identifiant du fichier est ignoree. Le dire avant l'envoi du fichier.
    $warn="";
    if(intval($EnableVirtualDomainsInMailBoxes)==1){
        $warn=$tpl->div_warning("{import_login_forced_email_title}||{import_login_forced_email_explain}");
    }
    $upload="<div class='center'>". $tpl->button_upload("{upload}",$page,null,$addparams)."</div>";
    echo $tpl->_ENGINE_parse_body($html).$warn.$tpl->_ENGINE_parse_body($upload);


}



function new_group():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ldap=new clladp();
    if(!isset($_SESSION["ADD_USER_GROUP"])){
        $_SESSION["ADD_USER_GROUP"]=0;
    }
    if(!isset($_SESSION["ADD_USER_DOMAIN"])){
        $_SESSION["ADD_USER_DOMAIN"]="";
    }
    if(!isset($_SESSION["ADD_USER_OU"])){
        $_SESSION["ADD_USER_OU"]="";
    }

    $form[]=$tpl->field_info("ou", "{organization}", $_SESSION["ADD_USER_OU"]);

    $hash_groups=$ldap->hash_groups($_SESSION["ADD_USER_OU"],1);
    $hash_domains=$ldap->hash_get_domains_ou($_SESSION["ADD_USER_OU"]);

    $form[]=$tpl->field_text("AddGroup", "{new_group}", null);
    $form[]=$tpl->field_array_hash($hash_groups, "ChooseGroup", "{group2}", $_SESSION["ADD_USER_GROUP"]);
    if(count($hash_domains)>0) {
        $form[] = $tpl->field_array_hash($hash_domains, "ChooseDomain", "{domain}", $_SESSION["ADD_USER_DOMAIN"]);
    }else{
        $form[] = $tpl->field_hidden("ChooseDomain","");
    }
    echo $tpl->form_outside("{new_member} &raquo; {$_SESSION["ADD_USER_OU"]} &raquo; {group2}", $form,null,"{next}",
        "LoadAjaxTiny('create-user-div','$page?new-member=yes&function={$_GET["function"]}');","AllowAddUsers");
    return true;
}

function new_member(){
    $EnableVirtualDomainsInMailBoxes=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVirtualDomainsInMailBoxes");
    $tpl=new template_admin();
    $ldap=new clladp();
    $hash_groups=$ldap->hash_groups($_SESSION["ADD_USER_OU"],1);
    if(!is_numeric($_SESSION["ADD_USER_GROUP"])){
        foreach ($hash_groups as $id=>$xname){
            if(trim(strtolower($xname))==trim(strtolower($_SESSION["ADD_USER_GROUP"]))){
                $gpid=$id;
                break;
            }
        }

    }else{
        $gpid=$_SESSION["ADD_USER_GROUP"];
    }

    //$WarninEnableVirtualDomainsInMailBoxes=null;
    $form[]=$tpl->field_hidden("gpid", $gpid);
    $form[]=$tpl->field_info("ou", "{organization}", $_SESSION["ADD_USER_OU"]);
    $form[]=$tpl->field_info("GroupName", "{group2}", $hash_groups[$gpid]);
    if(strlen($_SESSION["ADD_USER_DOMAIN"])>2) {
        $form[] = $tpl->field_info("internet_domain", "{domain}", $_SESSION["ADD_USER_DOMAIN"]);
    }
    $form[]=$tpl->field_text("firstname", "{firstname}", null,true);
    $form[]=$tpl->field_text("lastname", "{lastname}", null,true);
    $form[]=$tpl->field_email("email", "{email}", null,true);
    if($EnableVirtualDomainsInMailBoxes==0){
        $form[]=$tpl->field_text("login", "{uid}", null,true);
    }else{
        $form[]=$tpl->field_hidden("login", "");

    }
    $form[]=$tpl->field_password2("password", "{password}", null,true);


    $AFTER="dialogInstance1.close();TableLoaderMyMemberearch()";
    if($_GET["function"]<>null){
        $AFTER=$_GET["function"]."();$AFTER";

    }
    if($EnableVirtualDomainsInMailBoxes==1){
        echo $tpl->_ENGINE_parse_body($tpl->div_explain("{new_member} &raquo; {$_SESSION["ADD_USER_OU"]} &raquo; {$hash_groups[$gpid]}||{WarninEnableVirtualDomainsInMailBoxes}"));
    }else{
        echo $tpl->_ENGINE_parse_body($tpl->div_explain("{new_member} &raquo; {$_SESSION["ADD_USER_OU"]} &raquo; {$hash_groups[$gpid]}||{WarninEnableVirtualDomainsInMailBoxes_none}"));
    }

    // Synchronous create via POST /openldap/members (new_member_save) — the form's
    // standard save POSTs the fields then runs $AFTER (close dialog + refresh list).
    echo $tpl->form_outside("",
        @implode("\n", $form),"","{add}",
        $AFTER,"AllowAddUsers");

}

function new_member_save(){
    if(mbrsldap_denied("create a directory member",array("AllowAddUsers","AsOrgAdmin"),intval($_POST["gpid"] ?? 0)>0)){return false;}
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    if($_POST["login"]==null){
        $m=explode("@",$_POST["email"]);
        $_POST["login"]=$m[0];
    }
    $_POST["login"]=$tpl->CLEAN_BAD_XSS($_POST["login"]);
    $_POST["firstname"]=$tpl->CLEAN_BAD_XSS($_POST["firstname"]);
    $_POST["lastname"]=$tpl->CLEAN_BAD_XSS($_POST["lastname"]);
    $_POST["email"]=$tpl->CLEAN_BAD_CHARMAIL($_POST["email"]);

    // Create synchronously through the daemon /openldap API (no queue, no direct LDAP).
    $ou=$_POST["ou"]; if($ou==null){$ou=$_SESSION["ADD_USER_OU"];}
    $payload=array(
        "login"=>$_POST["login"],
        "password"=>$_POST["password"],
        "firstname"=>$_POST["firstname"],
        "lastname"=>$_POST["lastname"],
        "email"=>$_POST["email"],
        "internet_domain"=>$_POST["internet_domain"] ?? "",
        "ou"=>$ou,
        "gpid"=>intval($_POST["gpid"]),
    );
    $r=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/openldap/members",$payload));
    if(!is_object($r)){echo "daemon unreachable";return;}
    if(isset($r->Status) && !$r->Status){echo @$r->Error;return;}
    if(isset($r->Data->success) && !$r->Data->success){echo @$r->Data->error;}
}


function new_group_save(){
    if(mbrsldap_denied("create a directory group",array("AllowAddGroup","AsOrgAdmin"))){return false;}
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $group=$tpl->CLEAN_BAD_XSS($_POST["AddGroup"]);
    $ou=$_POST["ou"];
    if($ou==null){if($_SESSION["ou"]<>null){$ou=$_SESSION["ou"];}}

    $ldap=new clladp();
    include_once(dirname(__FILE__).'/ressources/class.groups.inc');

    $groupClass=new groups();
    $list=$groupClass->samba_group_list();

    if(is_array($list)){
        foreach ($list as $ligne){
            if(trim(strtolower($ligne))==trim(strtolower($group))){
                $tpl=new templates();
                echo $tpl->_ENGINE_parse_body('{no_samba_group_in_ou}');
                exit;
            }
        }
    }

    if($group<>null){
        if(!$ldap->AddGroup($group,$ou)){echo $ldap->ldap_last_error;}
        $_SESSION["ADD_USER_GROUP"]=$ldap->generated_id;
    }else{
        $_SESSION["ADD_USER_GROUP"]=$_POST["ChooseGroup"];
    }



    $_SESSION["ADD_USER_DOMAIN"]=url_decode_special_tool($_POST["ChooseDomain"]);

}


function new_organization_save(){
    if(mbrsldap_denied("create an organization",array("AsOrgAdmin"))){return false;}
    $usr=new usersMenus();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if($_POST["AddOU"]<>null){
        $ou=$tpl->CLEAN_BAD_XSS($_POST["AddOU"]);
        if($ou=="_Global"){echo "Reserved!";exit;}
        $ldap=new clladp();
        if(!$ldap->AddOrganization($ou)){
        if ($ldap->ldap_last_error <> null) {
            if ($ldap->ldap_last_error_num <> 68) {
                echo "\n****************************************\nAdding Organization \"{$ou}\"\n********************\n";
                echo $ldap->ldap_last_error;
                return;
            }
        }
    }

        $ldap->ldap_close();
        return;
    }

    $_SESSION["ADD_USER_OU"]=$_POST["ChooseOU"];


}

function filter_js(){
    $value=intval($_GET["filter-js"]);
    $_SESSION["MEMBERS_SEARCH_FILTER"]=$value;
    // $_GET["function"] part BRUT dans OnClick / $jsafter: liste blanche a la lecture.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    VERBOSE("MEMBERS_SEARCH_FILTER={$_SESSION["MEMBERS_SEARCH_FILTER"]}",__LINE__);
    echo "$function();\n";

}

function page(){
    $tpl=new template_admin();
    if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
    if(!isset($_SESSION["MEMBERS_SEARCH"])){$_SESSION["MEMBERS_SEARCH"]="";}
    if($_SESSION["MEMBERS_SEARCH"]==null){$_SESSION["MEMBERS_SEARCH"]="";}
    // Comptage synchrone par le daemon : recherche paginee, donc non plafonnee
    // par le sizelimit 500 de slapd.conf, et sans le +1 de count(ldap_get_entries()).
    $CountOfLDAPMembers=0;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/openldap/count/members"));
    if(is_object($json) && isset($json->Status) && $json->Status && isset($json->Data->count)){
        $CountOfLDAPMembers=intval($json->Data->count);
    }

    $filters[0]="{members}";
    $filters[1]="{users_and_groups}";
    $filters[2]="{groups2}";

    if(!isset($_SESSION["MEMBERS_SEARCH_FILTER"])){$_SESSION["MEMBERS_SEARCH_FILTER"]=1;}
    $title=$filters[$_SESSION["MEMBERS_SEARCH_FILTER"]];

    $subtitle="{search_ldap_members}";
    if($CountOfLDAPMembers>0){$subtitle="{search_ldap_members} - $CountOfLDAPMembers {members}";}
    $html=$tpl->page_header("{search} $title","fad fa-users",
        $subtitle,
        null,
        "local-ldap-members",
        "progress-firehol-restart",true,
        "table-loader-my-members"
    );




    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: {my_members}",$html);
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}

function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ldap=new clladp();
    $t=time();
    $stringtofind=url_decode_special_tool($_GET["search"]);
    $stringtofind="*$stringtofind*";
    $stringtofind=str_replace("**", "*", $stringtofind);
    $stringtofind=str_replace("**", "*", $stringtofind);
    $filter=$_SESSION["MEMBERS_SEARCH_FILTER"];
    // $_GET["function"] part BRUT dans OnClick / $jsafter: liste blanche a la lecture.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));

    $filters[0]="{members}";
    $filters[1]="{users_and_groups}";
    $filters[2]="{groups2}";

    if(!isset($_SESSION["MEMBERS_SEARCH_FILTER"])){$_SESSION["MEMBERS_SEARCH_FILTER"]=1;}
    $title=$filters[$_SESSION["MEMBERS_SEARCH_FILTER"]];
    $CLUSTER_CLIENT=false;
    $EnableLDAPSyncProv=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProv"));
    $EnableLDAPSyncProvClient=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProvClient");
    if($EnableLDAPSyncProv==1){
        if($EnableLDAPSyncProvClient==1){
            $CLUSTER_CLIENT=true;
        }
    }


    $MAIN_HASH=array();
    VERBOSE("Filter=$filter",__LINE__);

    if($filter==1) {
        $MAIN_HASH = $ldap->UserAndGroupSearch(null, $stringtofind, 100);
    }

    if($filter==0) {
        $MAIN_HASH = $ldap->UserAndGroupSearch(null, $stringtofind, 100,false,true);

    }
    if($filter==2) {
        $MAIN_HASH = $ldap->UserAndGroupSearch(null, $stringtofind, 100,true,false);

    }
    //<i class="fas fa-file-export"></i>

    $filters_bts["{users_and_groups}"]="Loadjs('$page?filter-js=1&function=$function');";
    $filters_bts["{members}"]="Loadjs('$page?filter-js=0&function=$function');";
    $filters_bts["{groups2}"]="Loadjs('$page?filter-js=2&function=$function');";


    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-bottom: 15px'>";
    if($filter==1 OR $filter==0) {
        if(!$CLUSTER_CLIENT) {
            $btns[] = $tpl->button_label_table("{new_member}",
                "Loadjs('$page?new-js=yes&function=$function')", "far fa-user-plus", "AllowAddUsers");
            $btns[] = $tpl->button_label_table("{new_organization}",
                "Loadjs('$page?new-organization-js=yes&function=$function')", "fas fa-building", "AllowAddUsers");
        }
    }
    if($filter==1 OR $filter==2) {
        if(!$CLUSTER_CLIENT) {
            $btns[] = $tpl->button_label_table("{import}", "Loadjs('$page?import-js=yes')", "fas fa-file-import", "AsSystemAdministrator");
        }
        $btns[] = $tpl->button_label_table("{export}", "Loadjs('$page?export-js=yes')", "fas fa-file-export", "AllowAddUsers");
        if(!$CLUSTER_CLIENT) {
            $btns[] = $tpl->button_label_table("{new_group}", "Loadjs('fw.member.ldap.edit.php?new-group-js=yes&function=$function')", "fas fa-users-medical", "AllowAddUsers");
        }
    }
    if(!$CLUSTER_CLIENT) {
        $btns[] = $tpl->button_label_table("{organizations}",
            "Loadjs('$page?organizations-js=yes&function=$function')", "fas fa-sitemap", "AllowAddUsers");
    }
    $btns[]=$tpl->button_dropdown_table("{filter}",$filters_bts,"AllowAddUsers");
    $btns[]="</div>";

    $filterstitle[0]="{members}";
    $filterstitle[1]="{users_and_groups}";
    $filterstitle[2]="{groups2}";

    $title=$filterstitle[$_SESSION["MEMBERS_SEARCH_FILTER"]];
    $TINY_ARRAY["TITLE"]="{search} $title &laquo;$stringtofind&raquo;";
    $TINY_ARRAY["ICO"]="fad fa-users";
    $TINY_ARRAY["EXPL"]="{search_ldap_members}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";




    // Design awc-* : les lignes sont assemblees dans $rows (une fonction par
    // type d'entite) puis rendues dans la carte. Plus de footable ici: il
    // reecrit les classes du <table> et ajoute son propre pager Bootstrap.
    $rows=array();
    if(!is_array($MAIN_HASH)){$MAIN_HASH=array();}
    if(!isset($MAIN_HASH["count"])){$MAIN_HASH["count"]=0;}

    for($i=0;$i<$MAIN_HASH["count"];$i++){
        if(!isset($MAIN_HASH[$i])){continue;}
        $ligne=$MAIN_HASH[$i];
        $id=md5(serialize($ligne));
        $objectclass=isset($ligne["objectclass"])?$ligne["objectclass"]:array();
        if(!is_array($objectclass)){$objectclass=array();}

        if(in_array("posixGroup",$objectclass)){
            $rows[]=ldapm_group_row($ligne,$id,$CLUSTER_CLIENT,$tpl,$page);
            continue;
        }
        if(!isset($ligne["uid"][0])){continue;}
        if(strpos((string)$ligne["dn"],"dc=pureftpd,dc=organizations")>0){continue;}
        $rows[]=ldapm_user_row($ligne,$id,$CLUSTER_CLIENT,$ldap,$tpl,$page);
    }
    $c=count($rows);

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
    $html[]="<thead><tr>";
    $html[]="<th>{displayname}</th>";
    $html[]="<th>{email}</th>";
    $html[]="<th>{phone}</th>";
    $html[]="<th>{groups2}</th>";
    $html[]="<th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='ldapm-rows'>";
    if($c==0){
        $html[]="<tr><td colspan='5'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c {items}</span>";
    $html[]="<span class='awc-pg' id='ldapm-pager'></span>";
    $html[]="</div></div></div>";

    // Le <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un {token}.
    $js=array();
    $js[]="NoSpinner();";
    $js[]=$tpl->ICON_SCRIPTS_COMPILE();
    // Alias stable vers la fonction de rafraichissement engendree par
    // search_block, pour que les mutations rechargent la table en conservant le
    // filtre courant.
    if(strlen($function)>2){
        $js[]="function TableLoaderMyMemberearch(){ if(typeof $function==='function'){ $function(); } }";
    }
    $js[]=$headsjs;
    $script="<script>".@implode("\n",$js)."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$script
        .awc_pager_js(50,"ldapm-rows","ldapm-pager");

}

// Une ligne "groupe posix". La premiere colonne ne portait qu'une icone sans
// en-tete; elle devient la pastille d'identite du design awc.
function ldapm_group_row($ligne,$id,$CLUSTER_CLIENT,$tpl,$page):string{
    $page=CurrentPageName();
    $gidnumber=isset($ligne["gidnumber"][0])?$ligne["gidnumber"][0]:0;
    $displayname=isset($ligne["cn"][0])?(string)$ligne["cn"][0]:"";
    $gp=new groups($gidnumber);
    $NumBerOfusers=count($gp->ARRAY_MEMBERS);
    $delete=$tpl->icon_delete("Loadjs('$page?delete-group-ask=$gidnumber&md=$id')","AllowAddGroup");
    if($CLUSTER_CLIENT){$delete=$tpl->icon_delete();}
    $js="Loadjs('fw.groups.ldap.php?gpid=".rawurlencode((string)$gidnumber)."')";

    $h ="<tr id='$id'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='far fa-users'></i></span>";
    $h.="<span><span class='awc-name'>".$tpl->td_href(htmlspecialchars($displayname),"{click_to_edit}",$js)."</span>";
    $h.="<div class='awc-meta awc-mono'>GID: ".intval($gidnumber)."</div>";
    $h.="</span></div></td>";
    $h.="<td><span class='awc-none'>&mdash;</span></td>";
    $h.="<td><span class='awc-none'>&mdash;</span></td>";
    $h.="<td><span class='awc-tag'>$NumBerOfusers {members}</span></td>";
    $h.="<td><div class='awc-acts'>$delete</div></td>";
    $h.="</tr>";
    return $h;
}

// Une ligne "membre". Tous les attributs viennent de l'annuaire et partaient
// BRUTS dans le HTML: ils sont desormais echappes.
function ldapm_user_row($ligne,$id,$CLUSTER_CLIENT,$ldap,$tpl,$page):string{
    $page=CurrentPageName();
    $uid=(string)$ligne["uid"][0];
    if(isset($ligne["samaccountname"][0])){$uid=(string)$ligne["samaccountname"][0];}
    if(isset($ligne["uid"][0])){$uid=(string)$ligne["uid"][0];}

    $displayname=null;$givenname=null;$sn=null;
    $email_address=array();$telephonenumber=array();$gps=array();

    if(isset($ligne["userprincipalname"][0])){$email_address[]=(string)$ligne["userprincipalname"][0];}
    if(isset($ligne["mail"][0])){$email_address[]=(string)$ligne["mail"][0];}
    if(isset($ligne["telephonenumber"][0])){$telephonenumber[]=(string)$ligne["telephonenumber"][0];}
    if(isset($ligne["mobile"][0])){$telephonenumber[]=(string)$ligne["mobile"][0];}
    if(isset($ligne["givenname"][0])){$givenname=(string)$ligne["givenname"][0];}
    if(isset($ligne["sn"][0])){$sn=(string)$ligne["sn"][0];}

    if($givenname<>null){if($sn<>null){$displayname=trim("$givenname $sn");}}
    // Le code d'origine lisait displayname[0] sans isset(): fatal sous PHP 8
    // des qu'un compte n'en porte pas.
    if($displayname==null){
        if(isset($ligne["displayname"][0])){$displayname=trim((string)$ligne["displayname"][0]);}
    }
    if($displayname==null){$displayname=$uid;}

    $Groups=$ldap->GetUserGroups($uid);
    if(!is_array($Groups)){$Groups=array();}
    foreach($Groups as $GroupDN=>$GroupName){
        if(trim((string)$GroupName)==null){continue;}
        $jsGRP="Loadjs('fw.groups.ldap.php?gpid=".rawurlencode((string)$GroupDN)."')";
        $gps[]="<span class='awc-chip'><span class='awc-chip-ico'><i class='fas fa-users'></i></span>"
              ."<a href=\"javascript:blur();\" OnClick=\"$jsGRP\">".htmlspecialchars((string)$GroupName)."</a></span>";
        if(count($gps)>5){$gps[]="<span class='awc-none'>&hellip;</span>";break;}
    }

    $uidenc=urlencode($uid);
    $js="Loadjs('fw.member.ldap.edit.php?uid=$uidenc')";
    $delete=$tpl->icon_delete("Loadjs('$page?delete-user-js=$uidenc&md=$id')");
    if($CLUSTER_CLIENT){$delete=$tpl->icon_delete();}

    $mails=array();
    foreach($email_address as $m){$mails[]="<div>".htmlspecialchars($m)."</div>";}
    $phones=array();
    foreach($telephonenumber as $p){$phones[]="<div>".htmlspecialchars($p)."</div>";}

    $h ="<tr id='$id'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-user'></i></span>";
    $h.="<span><span class='awc-name'>".$tpl->td_href(htmlspecialchars($displayname),"{click_to_edit}",$js)."</span>";
    $h.="<div class='awc-meta awc-mono'>".htmlspecialchars($uid)."</div>";
    $h.="</span></div></td>";
    $h.="<td class='awc-mono'>".(count($mails)>0?@implode("",$mails):"<span class='awc-none'>&mdash;</span>")."</td>";
    $h.="<td class='awc-mono'>".(count($phones)>0?@implode("",$phones):"<span class='awc-none'>&mdash;</span>")."</td>";
    $h.="<td>".(count($gps)>0?"<div class='awc-chips'>".@implode("",$gps)."</div>":"<span class='awc-none'>{no_members}</span>")."</td>";
    $h.="<td><div class='awc-acts'>$delete</div></td>";
    $h.="</tr>";
    return $h;
}

function BuildRequests($stringtofind=null){
    $LIMIT=200;
    $stringtofind=trim(strtolower($stringtofind));
    $stringtofind=str_replace("  ", " ", $stringtofind);
    $stringtofind=str_replace("  ", " ", $stringtofind);
    $stringtofind=str_replace("  ", " ", $stringtofind);
    $ipClass=new IP();
    $fixed=false;

    if(preg_match("#limit\s+([0-9]+)#", $stringtofind,$re)){
        $stringtofind=trim(str_replace("limit {$re[1]}", "", $stringtofind));
        $LIMIT=$re[1];
    }


    if(strpos("  $stringtofind", "+fixed")){
        $fixed=true;
        $stringtofind=str_replace("+fixed", "", $stringtofind);
        $stringtofind=trim($stringtofind);
    }




    $ORDERL="DESC";
    $ORDERBY="ORDER BY fullhostname";
    if(strpos("  $stringtofind", "order by time")){
        $ORDERBY ="ORDER BY updated";
        $stringtofind=str_replace("order by time", "", $stringtofind);
        $stringtofind=trim($stringtofind);
    }
    if(strpos("  $stringtofind", "order by ip")){
        $ORDERBY ="ORDER BY ipaddr";
        $stringtofind=str_replace("order by ip", "", $stringtofind);
        $stringtofind=trim($stringtofind);
    }
    if(strpos("  $stringtofind", "order by name")){
        $ORDERBY ="ORDER BY ipaddr";
        $stringtofind=str_replace("order by name", "", $stringtofind);
        $stringtofind=trim($stringtofind);
    }
    if(strpos("  $stringtofind", "order by alias")){
        $ORDERBY ="ORDER BY proxyalias";
        $stringtofind=str_replace("order by alias", "", $stringtofind);
        $stringtofind=trim($stringtofind);
    }

    if(strpos("  $stringtofind", "+asc")){
        $ORDERL="ASC";
        $stringtofind=str_replace("+asc", "", $stringtofind);
        $stringtofind=trim($stringtofind);
    }
    if(strpos("  $stringtofind", "+desc")){
        $ORDERL="DESC";
        $stringtofind=str_replace("+desc", "", $stringtofind);
        $stringtofind=trim($stringtofind);
    }

    if(preg_match("#(.+?)\s+\(#", $stringtofind,$re)){$stringtofind=trim($re[1]);}

    $stringtofind=str_replace("**", "*", $stringtofind);
    $stringtofind2=str_replace("*", "", $stringtofind);
    $stringtofind=str_replace("*", ".*?", $stringtofind);

    $INET=false;
    $MAC=false;
    if($ipClass->isIPAddressOrRange($stringtofind2)){
        $OR[]="(inet '$stringtofind2' = ipaddr )";
        $INET=true;
    }

    if(!$INET){
        if(preg_match("#^[0-9\.]+$#", $stringtofind2)){
            if($stringtofind2<>null){
                $tt=explode(".",$stringtofind2);

                foreach ($tt as $index=>$value){
                    if(strlen( (string) $value)>3){$tt[$index]="0";}
                }

                if(!isset($tt[1])){$tt[1]="0";}
                if(!isset($tt[2])){$tt[2]="0";}
                if(!isset($tt[3])){$tt[3]="0";}



                $tipaddr=@implode(".", $tt);
                if($tipaddr<>"0.0.0.0"){
                    $OR[]="( inet '$tipaddr' >= ipaddr )";
                }
            }
        }
    }

    if($ipClass->IsvalidMAC($stringtofind2)){
        $OR[]="( mac='$stringtofind2' )";
        $MAC=true;
    }



    if(!$MAC){
        if($stringtofind2<>null){
            if(preg_match("#^[0-9a-z]+(:|-)[0-9a-z]+#", $stringtofind2)){
                $tt=explode(":",$stringtofind2);

                foreach ($tt as $index=>$value){if(strlen( (string) $value)>2){$tt[$index]="00";}}

                if(!isset($tt[1])){$tt[1]="00";}
                if(!isset($tt[2])){$tt[2]="00";}
                if(!isset($tt[3])){$tt[3]="00";}
                if(!isset($tt[4])){$tt[4]="00";}
                if(!isset($tt[5])){$tt[5]="00";}
                $tipaddr=@implode(":", $tt);
                if($tipaddr<>"00:00:00:00:00:00"){
                    $OR[]="( mac >='$tipaddr' )";
                    $MAC=true;
                }
            }
        }

    }

    if($INET==false){
        if($MAC==false){
            $OR[]="( fullhostname ~ '$stringtofind' )";
            $OR[]="( hostname ~ '$stringtofind' )";
            $OR[]="( hostalias1 ~ '$stringtofind' )";
            $OR[]="( hostalias2 ~ '$stringtofind' )";
            $OR[]="( hostalias3 ~ '$stringtofind' )";
            $OR[]="( hostalias4 ~ '$stringtofind' )";
        }
    }

    if($fixed){$AND1=" AND dhcpfixed=1 ";}


    return "SELECT * FROM hostsnet WHERE ( ".@implode(" OR ", $OR).")$AND1 $ORDERBY $ORDERL LIMIT $LIMIT";
}
function file_uploaded(){
    // Import CSV reserve a AsSystemAdministrator, quel que soit gpid : sans gpid, le daemon
    // (/openldap/import/members, LdapUsers parseRowB) lit la colonne Group de chaque ligne et
    // place le compte dans ce groupe (cree au besoin). Aucun parametre ne lui fait ignorer
    // cette colonne : un compte delegue (AllowAddUsers/AsOrgAdmin) creerait des comptes dans
    // un groupe porteur de privileges. Refus = 403 + admin_tracks.
    if(mbrsldap_import_denied()){return false;}
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $file=$_GET["file-uploaded"];
    $ou=urlencode($_GET["ou"]);
    $gpid=intval($_GET["gpid"]);
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ldap.import.members";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/ldap.import.members.txt";
    $ARRAY["CMD"]="/openldap/import/members?ou=$ou&gpid=$gpid&filename=".urlencode($file);
    $ARRAY["TITLE"]="{importing} $file";
    $ARRAY["AFTER"]="TableLoaderMyMemberearch();dialogInstance1.close();";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=import-user-progress')";
    echo $jsrestart;
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}

// Import CSV de comptes : AsSystemAdministrator seulement (voir file_uploaded). Rend true = REFUSE,
// apres 403 + admin_tracks. Reponse JavaScript (appele par Loadjs).
function mbrsldap_import_denied():bool{
    $users=new usersMenus();
    if($users->AsSystemAdministrator){return false;}
    if(!headers_sent()){http_response_code(403);header("content-type: application/x-javascript");}
    mbrsldap_denied("import directory members from a CSV file",array(),true,false);
    return true;
}
// Comptes et groupes de l'annuaire (creation, import, suppression) : controle COTE SERVEUR.
// $roles : droits delegues admis (AllowAddUsers, AsOrgAdmin...). Mettre un membre DANS un groupe
// ($group=true) exige AsSystemAdministrator : un groupe peut porter des privileges de la console,
// un compte delegue s'en servait pour creer un compte administrateur. Rend true = REFUSE.
function mbrsldap_denied($what,$roles,$group=false,$post=true):bool{
    $users=new usersMenus();
    if($users->AsSystemAdministrator){return false;}
    if(!$group){
        foreach($roles as $r){ if(!empty($users->$r)){return false;} }
    }
    $tpl=new template_admin();
    admin_tracks("Refused: $what (missing privilege)");
    if($post){echo $tpl->post_error("{ERROR_NO_PRIVS}");}else{$tpl->js_error("{ERROR_NO_PRIVS}");}
    return true;
}
