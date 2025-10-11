<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");
include_once(dirname(__FILE__)."/ressources/class.ad-agent.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["ip_addr"])){net_save();exit;}
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
            $tpl->js_error("Unable to find paramaters of $DN");
            return false;
        }
    }




    $DNEnc=urlencode($DN);
    $tpl->js_dialog1("{block} {members} $DN", "$page?block-user-popup=$DNEnc",750);
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

    $table[]="<table class='table table-hover' style='width:100%' id='wizard-primary_node-for-steps'>
    <thead>
	<tr>
	<th>{members} ($DN)</th>
	<th style='width:1%' nowrap>&nbsp;</th>
	<th style='width:1%' nowrap>&nbsp;</th>
	</tr>
	</thead>
	<tbody>
	";
    $Groups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotBlockMembers"));
    foreach ($ARRAY as $index=>$DNUser){
        if($EnableExternalACLADAgent==1){

            $DisplayName=strtolower($DNUser["data"]["sAMAccountName"]);
        }
        else {
            $DisplayName=strtolower($DNUser);
        }

        $jsafter="LoadAjax('wizard-primary_node-for-steps','$page?block-user-popup=$encodedval')";

        if(isset($Groups[$decodedval."-".$DisplayName])){
            $checkbox = $tpl->icon_check(1, "Loadjs('$page?unlink_block_ad_user_dn=$decodedval&unlink_block_ad_user_member=$DisplayName');$jsafter", null, "AsSquidAdministrator");

        }
        else {
            $checkbox = $tpl->icon_check(0, "Loadjs('$page?link_block_ad_user_dn=$decodedval&link_block_ad_user_member=$DisplayName');$jsafter", null, "AsSquidAdministrator");

        }

        $id=md5($DNUser);
        $table[]="<tr id='$id'>";
        $table[]="<td><i class='fad fa-user'></i>&nbsp;$DisplayName</td>";
        $table[]="<td>$checkbox</td>";
        $table[]="</tr>";

    }
    $table[]="</tbody></table><script>NoSpinner();\n" . @implode("\n", $tpl->ICON_SCRIPTS) . "</script>";
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(@implode("\n", $table));

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
            $tpl->js_error("Unable to find paramaters of $DN");
            return false;
        }
    }




    $DNEnc=urlencode($DN);
    return $tpl->js_dialog1("{members} $DN", "$page?list-popup=$DNEnc$addon",750);
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
    if(!isset($_GET["denied"])) {
        $table[] = "<div class=\"btn-group\" data-toggle=\"buttons\">";
        $table[] = "<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?block-user-js=$encodedval');\">";
        $table[] = "<i class='fa fa-plus'></i> {block} {members} </label>";
    }
    $table[] = "</div>";
    $table[]="<table class='table table-hover' style='width:100%'>
    <thead>
	<tr>
	<th>{members} ($DN)</th>
	<th style='width:1%' nowrap>&nbsp;</th>
	<th style='width:1%' nowrap>&nbsp;</th>
	</tr>
	</thead>
	<tbody>
	";

    foreach ($ARRAY as $index=>$DNUser){
        if($EnableExternalACLADAgent==1){
            if($HotspotRecursive==0){

                $DisplayName=$ARRAY[$index]["data"]["sAMAccountName"];
            }
            else {
                $DisplayName=explode(",", $DNUser);
                $DisplayName=str_replace("CN=", "", $DisplayName[0]);
            }

        }
        else {
            $DisplayName=$DNUser;
        }

        $color=" text-info";
        if(isset($_GET["denied"])) {
            $color=" text-danger";
        }

        $id=md5($DNUser);
        $table[]="<tr id='$id'>";
        $table[]="<td><i class='fad fa-user$color'></i>&nbsp;$DisplayName</td>";
        $table[]="</tr>";

    }
    $table[]="</tbody></table><script>NoSpinner()</script>";
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(@implode("\n", $table));



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

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $Groups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotLimitAdGroups"));
    if(!is_array($Groups)){$Groups=array();}
    $DeniedGroups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotLimitDeniedAdGroups"));
    if(!is_array($DeniedGroups)){$DeniedGroups=array();}
    $CountDeGroups=0;
    $title="{ad_groups}";
    foreach ($Groups as $DN=>$NONE){
        if($DN==null){continue;}
        $CountDeGroups++;
    }
    foreach ($DeniedGroups as $DN=>$NONE){
        if($DN==null){continue;}
        $CountDeGroups++;
    }
    if($CountDeGroups==0){$title="{AllowOnlyGroups}";}


	$table[]="<table class='table table-hover' style='width:100%'>
    <thead>
	<tr>
	<th  width=1% nowrap></th>
	<th>$title ($CountDeGroups)</th>
	<th style='width:1%' nowrap>&nbsp;</th>
	<th style='width:1%' nowrap>&nbsp;</th>
	</tr>
	</thead>
	<tbody>
	";


    $id=md5(serialize($Groups));
    if($CountDeGroups==0){
        $table[]="<tr id='$id'>";
        $table[]="<td width='1%'><span class='label label-primary'>{allow}</span></td>";
        $table[]="<td><strong>{all_ad_groups}</strong></td>";
        $table[]="<td width='1%'>&nbsp;</td>";
        $table[]="<td width='1%'>&nbsp;</td>";
        $table[]="</tr>";
        $table[]="</tbody></table><script>NoSpinner()</script>";
        echo $tpl->_ENGINE_parse_body(@implode("\n", $table));
        return;
    }


    foreach ($DeniedGroups as $DN=>$NONE){
        if(trim($DN)==null){continue;}
        $id=md5($DN);
        $DNEnc=urlencode($DN);
        $js="Loadjs('$page?list-js=$DNEnc&md=$id&denied=yes')";

        $list=$tpl->icon_list("Loadjs('$page?list-js=$DNEnc&md=$id&denied=yes')","AsHotSpotManager");
        $remove=$tpl->icon_delete("Loadjs('$page?unlink-denied=$DNEnc&md=$id')","AsHotSpotManager");
        $table[]="<tr id='$id'>";
        $table[]="<td width='1%'><span class='label label-danger'>{deny}</span></td>";
        $table[]="<td><i class=\"fad fa-users\"></i>&nbsp;". $tpl->td_href($DN,"{click_to_edit}","$js;")."</td>";
        $table[]="<td width='1%'>$list</td>";
        $table[]="<td width='1%'>$remove</td>";
        $table[]="</tr>";

    }

    foreach ($Groups as $DN=>$NONE){
        if(trim($DN)==null){continue;}
        $id=md5($DN);
        $DNEnc=urlencode($DN);
        $js="Loadjs('$page?list-js=$DNEnc&md=$id')";

        $list=$tpl->icon_list("Loadjs('$page?list-js=$DNEnc&md=$id')","AsHotSpotManager");
        $remove=$tpl->icon_delete("Loadjs('$page?unlink=$DNEnc&md=$id')","AsHotSpotManager");
        $table[]="<tr id='$id'>";
        $table[]="<td width='1%'><span class='label label-primary'>{allow}</span></td>";
        $table[]="<td><i class=\"fad fa-users\"></i>&nbsp;". $tpl->td_href($DN,"{click_to_edit}","$js;")."</td>";
        $table[]="<td width='1%'>$list</td>";
        $table[]="<td width='1%'>$remove</td>";
        $table[]="</tr>";

    }


	$table[]="</tbody></table><script>NoSpinner()</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $table));
}


function block_ad_user_remove(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $DN=$_GET["unlink_block_ad_user_dn"];
    $MEMBER=$_GET["unlink_block_ad_user_member"];

    $Groups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotBlockMembers"));
    unset($Groups[$DN."-".$MEMBER]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HotSpotBlockMembers",serialize($Groups));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
}

function block_ad_user_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $DN=$_GET["link_block_ad_user_dn"];
    $MEMBER=$_GET["link_block_ad_user_member"];
    $Groups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotBlockMembers"));
    $Groups[$DN."-".$MEMBER]=True;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HotSpotBlockMembers",serialize($Groups));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
}