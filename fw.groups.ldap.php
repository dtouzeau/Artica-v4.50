<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
    $ou=$hash["ou"];
    if($gpid==544){
        include_once(dirname(__FILE__)."/ressources/class.translate.rights.inc");
        $pp=new TranslateRights();
        $pt=$pp->GetPrivsArray();
        $RemoveButton=true;
        foreach ($pt as $num=>$ligne){$hash["ArticaGroupPrivileges"][$num]="yes";}
    }
    $HashPrivieleges=$hash["ArticaGroupPrivileges"];
    if($_SESSION["uid"]==-100){
        $priv->AsArticaAdministrator=true;
        $priv->AsFirewallManager=true;
        $priv->AsVPNManager=true;
    }

    $form[]=$tpl->field_section("{groups_allow}");
    $form[]=$tpl->field_hidden("privs", $gpid);


    $trings="AllowAddUsers,AllowAddGroup,AsOrgAdmin";
    $f=explode(",",$trings);
    foreach ($f as $priv){
        $form[]=$tpl->field_checkbox($priv,"{{$priv}}",$HashPrivieleges[$priv],false,null,false,$priv);


    }

    $trings="AsWebStatisticsAdministrator,AsDansGuardianAdministrator,AsSquidAdministrator,AsHotSpotManager,AsProxyMonitor";
    $form[]=$tpl->field_section("{proxy_allow}");
    $f=explode(",",$trings);
    foreach ($f as $priv){
        $form[]=$tpl->field_checkbox($priv,"{{$priv}}",$HashPrivieleges[$priv],false,null,false,$priv);
    }

    $form[]=$tpl->field_section("{administrators_allow}");
    $trings="AsArticaAdministrator,AsFirewallManager,AsVPNManager,AsDnsAdministrator,ASDCHPAdmin,AsDatabaseAdministrator,AsDockerAdmin,AsSambaAdministrator";
    $f=explode(",",$trings);
    foreach ($f as $priv){
        $PrivName=$HashPrivieleges[$priv];
        $privRights=$priv;
        if($priv=="AsDockerAdmin"){
            $privRights="AsSystemAdministrator";
        }

        $form[]=$tpl->field_checkbox($priv,"{{$priv}}",$PrivName,false,null,false,$privRights);
        //$name,$label=null,$value=0,$disable_form=false,$explain=null,$disabled=false,$security=null

    }

    $html[]=$tpl->form_outside("{privileges}", @implode("\n", $form),null,"{apply}","blur()","AsSystemAdministrator");

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function privileges_save(){
    $ldap=new clladp();
    $Hash=$ldap->GroupDatas($_POST["privs"]);

    if(!is_array($Hash["ArticaGroupPrivileges"])){
        writelogs("ldap->_ParsePrivieleges(...)",__FUNCTION__,__FILE__,__LINE__);
        $ArticaGroupPrivileges=$ldap->_ParsePrivieleges($Hash["ArticaGroupPrivileges"]);
    }else{
        $ArticaGroupPrivileges=$Hash["ArticaGroupPrivileges"];
    }

    if(is_array($ArticaGroupPrivileges)){
        foreach ($ArticaGroupPrivileges as $num=>$val){
            $GroupPrivilege[$num]=$val;
        }
    }


    foreach ($_POST as $num=>$val){
        if(!is_numeric($val)){continue;}
        if($val==1){$val="yes";}else{$val="no";}
        $GroupPrivilege[$num]=$val;
    }
    foreach ($GroupPrivilege as $num=>$val){
        if($val=="no"){writelogs("[$num]=SKIP",__FUNCTION__,__FILE__,__LINE__);continue;}
        writelogs("[$num]=\"$val\"",__FUNCTION__,__FILE__,__LINE__);
        $GroupPrivilegeNew[]="[$num]=\"$val\"";
    }

    $values=@implode( "\n",$GroupPrivilegeNew);
    $update_array["ArticaGroupPrivileges"][0]=$values;


    if(!$ldap->Ldap_modify($Hash["dn"],$update_array)){
        echo basename(__FILE__)."\nline: ".__LINE__."\n".$ldap->ldap_last_error;
    }


}



function ChangeName(){

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