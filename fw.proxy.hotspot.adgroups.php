<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");
include_once(dirname(__FILE__)."/ressources/class.ad-agent.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["item-js"])){ad_js();exit;}
if(isset($_GET["item-deny-js"])){ad_deny_js();exit;}
if(isset($_GET["ad-popup"])){ad_popup();exit;}
if(isset($_GET["ad-deny-popup"])){ad_deny_popup();exit;}
if(isset($_GET["unlink"])){ad_remove();exit;}
if(isset($_GET["unlink-denied"])){ad_denied_remove();exit;}


if(isset($_POST["ADDDN"])){ad_save();exit;}
if(isset($_POST["AD_DENY_DN"])){ad_deny_save();exit;}
if(isset($_GET["list-js"])){list_js();exit;}
if(isset($_GET["list-popup"])){list_popup();exit;}
if(isset($_GET["block-user-js"])){block_user_js();exit;}
if(isset($_GET["block-user-popup"])){block_user_popup();exit;}
if(isset($_GET["unlink_block_ad_user_dn"])){block_ad_user_remove();exit;}
if(isset($_GET["link_block_ad_user_dn"])){block_ad_user_save();exit;}
if(isset($_GET["options-js"])){options_js();exit;}
if(isset($_GET["options-popup"])){options_popup();exit;}
if(isset($_POST["hotspotoptions"])){save_hotspot_options();exit;}



page();

function save_hotspot_options(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
}
function options_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $options=intval($_GET["options-js"]);
    $tpl->js_dialog1("{options}", "$page?options-popup=$options",750);
}
function options_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $HotspotRecursive = intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotspotRecursive")));
    $form[]=$tpl->field_section(null,"{recursive_search_explain}");
    $form[]=$tpl->field_hidden("hotspotoptions","1");
    $form[]=$tpl->field_checkbox("HotspotRecursive","{recursive_search}",$HotspotRecursive,false);
    $rf="dialogInstance1.close();LoadAjax('table-loader-adgroups-hotspot','$page?table=yes');";
    $hml[]=$tpl->form_outside("{options}", @implode("\n", $form),null,"{apply}","$rf","AsSystemAdministrator");
    echo @implode("\n", $hml);

}
function ad_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$net=intval($_GET["item-js"]);
	return $tpl->js_dialog1("{activedirectory_group} - {allow}", "$page?ad-popup=$net",750);
}
function ad_deny_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $net=intval($_GET["item-deny-js"]);
    return $tpl->js_dialog1("{activedirectory_group} - {deny}", "$page?ad-deny-popup=$net",750);
}

function block_user_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $DN=$_GET["block-user-js"];
    $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
    if($EnableExternalACLADAgent==0){
        $adClass=new external_ad_search();


        $settings=$adClass->FindParametersByDN($DN);
        if(!isset($settings["LDAP_SERVER"])){
            // Pentest 2026-11-04 : block-user-js etait recopie sans encodage dans la page (XSS).
            $tpl->js_error("Unable to find paramaters of ".req_html($DN));
            return false;
        }
    }




    $DNEnc=urlencode($DN);
    // Pentest 2026-11-04 : block-user-js etait recopie sans encodage dans la page (XSS).
    // Le titre est decode (html_entity_decode) puis rendu en HTML : double encodage.
    $tpl->js_dialog1("{block} {members} ".req_html(req_html($DN)), "$page?block-user-popup=$DNEnc",750);
}
function block_user_popup(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $DN=$_GET["block-user-popup"];
    $encodedval=urlencode($DN);
    $decodedval=urldecode($DN);
    $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
    $HotspotRecursive = intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotspotRecursive")));
    if($EnableExternalACLADAgent==1){
        $adagent=new ADAgent();
        if($HotspotRecursive==0){
            $adgroupattr=$adagent->getNonRecursiveMembersOfGroup("$encodedval");
            $data = json_decode($adgroupattr, TRUE);

            $ARRAY=$data;

            if($GLOBALS["VERBOSE"]){foreach ($ARRAY as $line){VERBOSE($line,__LINE__);}}
        }
        else {
            $adgroupattr=$adagent->geRecursiveMembersOfGroup("$encodedval");
            $data = json_decode($adgroupattr, TRUE);

            $ARRAY=$data;

            if($GLOBALS["VERBOSE"]){foreach ($ARRAY as $line){VERBOSE($line,__LINE__);}}
        }

    }
    else {
        $adClass=new external_ad_search();


        $settings=$adClass->FindParametersByDN($DN);
        $settingsEncoded=base64_encode(serialize($settings));
        $ad=new external_ad_search($settingsEncoded);
        $ARRAY=$ad->HashUsersFromGroupDN($DN,$HotspotRecursive);
        if($GLOBALS["VERBOSE"]){foreach ($ad->VERBOSED_ARRAY as $line){VERBOSE($line,__LINE__);}}
    }

    // json_decode() rend null sur une reponse invalide et HashUsersFromGroupDN()
    // peut ne rien rendre: le foreach etait FATAL sous PHP 8.
    if(!is_array($ARRAY)){$ARRAY=array();}
    $Groups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotBlockMembers"));
    if(!is_array($Groups)){$Groups=array();}

    $jsafter="LoadAjax('wizard-primary_node-for-steps','$page?block-user-popup=$encodedval')";
    $rows=array();
    foreach ($ARRAY as $index=>$DNUser){
        $DisplayName=strtolower(hsad_member_name($DNUser,$EnableExternalACLADAgent==1,$HotspotRecursive!=0));
        if($DisplayName===""){continue;}
        // Le nom partait NON ENCODE dans l'URL: une esperluette ou un espace
        // dans un identifiant tronquait le parametre.
        $enc=urlencode($DisplayName);
        $blocked=isset($Groups[$decodedval."-".$DisplayName]);
        $action=$blocked?"unlink_block_ad_user":"link_block_ad_user";
        $toggle=$tpl->icon_check($blocked?1:0,
            // Pentest 2026-11-04 : block-user-popup etait recopie sans encodage dans la page (XSS).
            "Loadjs('$page?{$action}_dn=".req_url($decodedval)."&{$action}_member=$enc');$jsafter",
            null,"AsSquidAdministrator");
        $rows[]=hsad_member_row($DisplayName,hsad_member_key($DNUser),$toggle,$blocked);
    }

    // Le DN partait BRUT dans l'en-tete.
    $table[]=hsad_member_table($rows,"{members} <small>".htmlspecialchars((string)$DN)."</small>",true,$tpl);
    $tpl=new template_admin();
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $table))
        ."<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>"
        .awc_pager_js(50,"hsad-mbr-rows","hsad-mbr-pager");

}
function list_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $DN=$_GET["list-js"];
    $addon="";
    if(isset($_GET["denied"])){
        $addon="&denied=yes";
    }
    $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
    if($EnableExternalACLADAgent==0){
        $adClass=new external_ad_search();


        $settings=$adClass->FindParametersByDN($DN);
        if(!isset($settings["LDAP_SERVER"])){
            // Pentest 2026-11-04 : list-js etait recopie sans encodage dans la page (XSS).
            $tpl->js_error("Unable to find paramaters of ".req_html($DN));
            return false;
        }
    }




    $DNEnc=urlencode($DN);
    // Pentest 2026-11-04 : list-js etait recopie sans encodage dans la page (XSS).
    return $tpl->js_dialog1("{members} ".req_html(req_html($DN)), "$page?list-popup=$DNEnc$addon",750);
}
function ad_remove():bool{
  	$DN=$_GET["unlink"];
	$md=$_GET["md"];
    $Groups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotLimitAdGroups"));
    unset($Groups[$DN]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HotSpotLimitAdGroups",serialize($Groups));
    header("content-type: application/x-javascript");
    echo ("$('#$md').remove();\n");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
    return admin_tracks("Remove Active Directory group $DN from the Hotspot authorized list");
}
function ad_denied_remove():bool{
    $DN=$_GET["unlink-denied"];
    $md=$_GET["md"];
    $Groups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotLimitDeniedAdGroups"));
    unset($Groups[$DN]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HotSpotLimitDeniedAdGroups",serialize($Groups));
    header("content-type: application/x-javascript");
    echo ("$('#$md').remove();\n");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
    return admin_tracks("Remove Active Directory group $DN from the Hotspot deny list");
}

function ad_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();


    if($_POST["ADDDN"]==null){
        $tpl->post_error("Please select a an Active Directory group");
        return false;
    }
    $Groups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotLimitAdGroups"));
    $Groups[$_POST["ADDDN"]]=True;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HotSpotLimitAdGroups",serialize($Groups));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
    return admin_tracks("Add {$_POST["ADDDN"]} into authorized Active Directory group for the HotSpot");
}
function ad_deny_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();


    if($_POST["AD_DENY_DN"]==null){
        $tpl->post_error("Please select a an Active Directory group");
        return false;
    }

    $DeniedGroups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotLimitDeniedAdGroups"));
    $DeniedGroups[$_POST["AD_DENY_DN"]]=True;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HotSpotLimitDeniedAdGroups",serialize($DeniedGroups));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
    return admin_tracks("Add {$_POST["ADDDN"]} into deny Active Directory group for the HotSpot");
}

function list_popup(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $DN=$_GET["list-popup"];
    $encodedval=urlencode($DN);
    $HotspotRecursive = intval(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotspotRecursive")));
    $EnableExternalACLADAgent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
    if($EnableExternalACLADAgent==1){
        $adagent=new ADAgent();
        if ($HotspotRecursive==0){
            $adgroupattr=$adagent->getNonRecursiveMembersOfGroup("$encodedval");
            $data = json_decode($adgroupattr, TRUE);
            $ARRAY=$data;

        } else {
            $adgroupattr=$adagent->getAttrOf("$encodedval");
            $data = json_decode($adgroupattr, TRUE);
            $ARRAY=$data[0]["data"]["member"];
        }


        //print_r($ARRAY);
        if($GLOBALS["VERBOSE"]){foreach ($ARRAY as $line){VERBOSE($line,__LINE__);}}
    }
    else {
        $adClass=new external_ad_search();


        $settings=$adClass->FindParametersByDN($DN);
        $settingsEncoded=base64_encode(serialize($settings));
        $ad=new external_ad_search($settingsEncoded);
        $ARRAY=$ad->HashUsersFromGroupDN($DN,$HotspotRecursive);
        if($GLOBALS["VERBOSE"]){foreach ($ad->VERBOSED_ARRAY as $line){VERBOSE($line,__LINE__);}}
    }
    // La fermeture "</div>" etait emise INCONDITIONNELLEMENT alors que le bloc
    // de boutons ne s'ouvre que hors mode "denied": la vue des groupes refuses
    // sortait une balise orpheline.
    $denied=isset($_GET["denied"]);
    if(!$denied) {
        $table[] = "<div class=\"btn-group\" data-toggle=\"buttons\">";
        $table[] = "<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?block-user-js=$encodedval');\">";
        $table[] = "<i class='fa fa-plus'></i> {block} {members} </label>";
        $table[] = "</div>";
    }

    if(!is_array($ARRAY)){$ARRAY=array();}
    $rows=array();
    foreach ($ARRAY as $index=>$DNUser){
        $DisplayName=hsad_member_name($DNUser,$EnableExternalACLADAgent==1,$HotspotRecursive!=0);
        if($DisplayName===""){continue;}
        $rows[]=hsad_member_row($DisplayName,hsad_member_key($DNUser),null,$denied);
    }

    $table[]=hsad_member_table($rows,"{members} <small>".htmlspecialchars((string)$DN)."</small>",false,$tpl);
    $tpl=new template_admin();
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $table))
        ."<script>NoSpinner();</script>"
        .awc_pager_js(50,"hsad-mbr-rows","hsad-mbr-pager");



}

function ad_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$bt="{add}";
	$safter="dialogInstance1.close();LoadAjax('table-loader-adgroups-hotspot','$page?table=yes');";

	$form[]=$tpl->field_browse_adgroups("ADDDN", "{browse}:by-dn");
	$html=$tpl->form_outside("{browse_active_directory_groups}", @implode("\n", $form),"",$bt,"LoadAjax('table-loader-adgroups-hotspot','$page?table=yes');$safter","AsHotSpotManager");
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function ad_deny_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $bt="{add}";
    $safter="dialogInstance1.close();LoadAjax('table-loader-adgroups-hotspot','$page?table=yes');";

    $form[]=$tpl->field_browse_adgroups("AD_DENY_DN", "{browse}:by-dn");
    $html=$tpl->form_outside("{browse_active_directory_groups}", @implode("\n", $form),"",$bt,"LoadAjax('table-loader-adgroups-hotspot','$page?table=yes');$safter","AsHotSpotManager");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function page():bool{
    $page=CurrentPageName();
	$tpl=new template_admin();
	$add="Loadjs('$page?item-js=0');";
    $add_deny="Loadjs('$page?item-deny-js=0');";
    $options="Loadjs('$page?options-js=true');";

    $bouts="	<div class=\"btn-group\" data-toggle=\"buttons\">
    	<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {active_directory_groups} ({allow})</label>
    	<label class=\"btn btn btn-info\" OnClick=\"$add_deny\"><i class='fa fa-plus'></i> {active_directory_groups} ({deny})</label>
    	<label class=\"btn btn btn-primary\" OnClick=\"$options\"><i class='fa fa-gears'></i> {options} </label>
    </div>";

    $TINY_ARRAY["TITLE"]="{web_portal_authentication}: {active_directory_groups}";
    $TINY_ARRAY["ICO"]="fab fa-windows";
    $TINY_ARRAY["EXPL"]="{hotspot_ad_explain}";
    $TINY_ARRAY["URL"]="hotspot-config";
    $TINY_ARRAY["BUTTONS"]=$bouts;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html="
	<div class='ibox-content'>
        <div id='table-loader-adgroups-hotspot' style='padding-top:20px;width:100%'></div>
    </div>
    <script>
        LoadAjax('table-loader-adgroups-hotspot','$page?table=yes');
        $jstiny
    </script>";

	echo $tpl->_ENGINE_parse_body($html);
    return true;
}


/**
 * Le nom affichable d'un membre renvoye par l'annuaire.
 *
 * Selon le chemin emprunte, une entree est soit une CHAINE (le DN), soit un
 * TABLEAU indexe par "data" (agent ACL externe). Les deux tableaux de cette page
 * calculaient ensuite md5($DNUser) sans distinguer les cas: avec un tableau,
 * md5() leve une TypeError FATALE sous PHP 8.
 */
function hsad_member_name($entry,$externalAgent,$recursive):string{
    if($externalAgent){
        if(is_array($entry)){
            if(isset($entry["data"]["sAMAccountName"])){
                return (string)$entry["data"]["sAMAccountName"];
            }
            return "";
        }
        if(!$recursive){return "";}
        // Mode recursif: l'agent renvoie le DN complet, on garde le CN.
        $parts=explode(",",(string)$entry);
        return str_replace("CN=","",$parts[0]);
    }
    return (string)$entry;
}

/** Une cle stable pour la ligne, quel que soit le type de l'entree. */
function hsad_member_key($entry):string{
    return md5(is_array($entry)?serialize($entry):(string)$entry);
}

/** Un membre du groupe, avec ou sans interrupteur de blocage. */
function hsad_member_row($name,$key,$toggle,$denied):string{
    $h ="<tr id='$key'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='".($denied?"awc-ico awc-ico-off":"awc-ico")."'><i class='fad fa-user'></i></span>";
    // Le nom vient de l'annuaire et partait BRUT dans le HTML.
    $h.="<span class='awc-name'>".($name!==""
        ?htmlspecialchars($name)
        :"<span class='awc-none'>&mdash;</span>")."</span>";
    $h.="</div></td>";
    if($toggle!==null){
        $h.="<td class='center'>$toggle</td>";
    }
    $h.="</tr>";
    return $h;
}

/** Le tableau des membres d'un groupe. */
function hsad_member_table($rows,$title,$withToggle,$tpl):string{
    $cols=$withToggle?2:1;
    $h=array();
    $h[]="<div class='awc-card'><div class='awc-scroll'>";
    $h[]="<table class='awc-tbl'><thead><tr>";
    $h[]="<th>$title</th>";
    if($withToggle){$h[]="<th class='center'>{block}</th>";}
    // Les deux tableaux declaraient TROIS colonnes d'en-tete pour deux cellules
    // (voire une seule): les lignes ne s'alignaient pas sur l'en-tete.
    $h[]="</tr></thead><tbody id='hsad-mbr-rows'>";
    if(count($rows)==0){
        $h[]="<tr><td colspan='$cols'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $h[]=@implode("\n",$rows);
    }
    $h[]="</tbody></table></div>";
    $h[]="<div class='awc-foot'><span>{showing} ".count($rows)."</span>";
    $h[]="<span class='awc-pg' id='hsad-mbr-pager'></span></div></div>";
    return @implode("\n",$h);
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $Groups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotLimitAdGroups"));
    if(!is_array($Groups)){$Groups=array();}
    $DeniedGroups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotLimitDeniedAdGroups"));
    if(!is_array($DeniedGroups)){$DeniedGroups=array();}

    $rows=array();
    foreach ($DeniedGroups as $DN=>$NONE){
        if(trim((string)$DN)==null){continue;}
        $rows[]=hsad_group_row($DN,true,$page,$tpl);
    }
    foreach ($Groups as $DN=>$NONE){
        if(trim((string)$DN)==null){continue;}
        $rows[]=hsad_group_row($DN,false,$page,$tpl);
    }
    $CountDeGroups=count($rows);

    // Sans aucun groupe declare, le portail laisse passer tout l'annuaire: la
    // ligne le dit explicitement plutot que de laisser un tableau vide.
    $title=($CountDeGroups==0)?"{AllowOnlyGroups}":"{ad_groups}";
    if($CountDeGroups==0){
        $rows[]="<tr><td><div class='awc-ident'>"
            ."<span class='awc-ico'><i class='fad fa-users'></i></span>"
            ."<span class='awc-name'>{all_ad_groups}</span></div></td>"
            ."<td class='center'><span class='awc-badge awc-badge-on'>{allow}</span></td>"
            ."<td class='center'><span class='awc-none'>&mdash;</span></td></tr>";
    }

	$table=array();
    $table[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $table[]="<table class='awc-tbl'><thead><tr>";
    $table[]="<th>$title ($CountDeGroups)</th>";
    $table[]="<th class='center'>{status}</th>";
    $table[]="<th class='center'>{actions}</th>";
    // L'en-tete declarait QUATRE colonnes dont deux vides et sans libelle.
    $table[]="</tr></thead><tbody id='hsad-grp-rows'>";
    $table[]=@implode("\n",$rows);
    $table[]="</tbody></table></div>";
    $table[]="<div class='awc-foot'>";
    $table[]="<span>{showing} $CountDeGroups</span>";
    $table[]="<span class='awc-pg' id='hsad-grp-pager'></span>";
    $table[]="</div></div></div>";

	echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $table))
        ."<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>"
        .awc_pager_js(50,"hsad-grp-rows","hsad-grp-pager");
}

/** Un groupe Active Directory autorise ou refuse a l'acces au portail. */
function hsad_group_row($DN,$denied,$page,$tpl):string{
    $id=md5($DN);
    $DNEnc=urlencode($DN);
    $suffix=$denied?"&denied=yes":"";
    $js="Loadjs('$page?list-js=$DNEnc&md=$id$suffix')";
    $list=$tpl->icon_list($js,"AsHotSpotManager");
    $remove=$tpl->icon_delete("Loadjs('$page?".($denied?"unlink-denied":"unlink")."=$DNEnc&md=$id')","AsHotSpotManager");

    $h ="<tr id='$id'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='".($denied?"awc-ico awc-ico-off":"awc-ico")."'><i class='fad fa-users'></i></span>";
    $h.="<span class='awc-name'>";
    // Le DN vient de l'annuaire et partait BRUT dans le HTML.
    $h.=$tpl->td_href(htmlspecialchars((string)$DN),"{click_to_edit}","$js;")."</span>";
    $h.="</div></td>";
    $h.="<td class='center'>".($denied
        ?"<span class='awc-badge' style='background:#ba1a1a'>{deny}</span>"
        :"<span class='awc-badge awc-badge-on'>{allow}</span>")."</td>";
    $h.="<td class='center'><div class='awc-acts'>$list$remove</div></td>";
    $h.="</tr>";
    return $h;
}

function block_ad_user_remove(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $DN=$_GET["unlink_block_ad_user_dn"];
    $MEMBER=$_GET["unlink_block_ad_user_member"];

    // unserialize() rend false quand le reglage est vide: unset() sur un
    // booleen puis serialize() ecrasait le reglage par "b:0;".
    $Groups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotBlockMembers"));
    if(!is_array($Groups)){$Groups=array();}
    unset($Groups[$DN."-".$MEMBER]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HotSpotBlockMembers",serialize($Groups));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
}

function block_ad_user_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $DN=$_GET["link_block_ad_user_dn"];
    $MEMBER=$_GET["link_block_ad_user_member"];
    // unserialize() rend false quand le reglage est vide: affecter une cle sur
    // un booleen est une Error FATALE depuis PHP 8.1.
    $Groups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotBlockMembers"));
    if(!is_array($Groups)){$Groups=array();}
    $Groups[$DN."-".$MEMBER]=True;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HotSpotBlockMembers",serialize($Groups));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
}