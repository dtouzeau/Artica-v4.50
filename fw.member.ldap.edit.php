<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.contacts.inc");
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["profile"])){profile();exit;}
if(isset($_POST["password"])){change_password_save();exit;}
if(isset($_POST["NewGroup"])){new_group_save();exit;}
if(isset($_POST["uid"])){profile_save();exit;}
if(isset($_GET["groups"])){groups();exit;}
if(isset($_GET["groups-table"])){groups_table();exit;}
if(isset($_GET["new-group-js"])){new_group_js();exit;}
if(isset($_GET["new-group-popup"])){new_group_popup();exit;}

if(isset($_GET["link-group-js"])){link_group_js();exit;}
if(isset($_GET["link-group-popup"])){link_group_popup();exit;}
if(isset($_GET["link-group-table"])){link_group_table();exit;}
if(isset($_GET["link-group-perform"])){link_group_perform();exit;}
if(isset($_GET["unlink-js"])){group_js_unlink();exit;}
if(isset($_GET["ch-pass"])){change_password_js();exit;}
if(isset($_GET["ch-pass-popup"])){change_password_popup();exit;}

js();

function js(){
	$page=CurrentPageName();
	$uid=$_GET["uid"];
	$uidenc=urlencode($_GET["uid"]);
	$ct=new user($uid);
	$tpl=new template_admin();
	if(!isset($_GET["function"])){$_GET["function"]=null;}
	$tpl->js_dialog4($ct->DisplayName, "$page?tabs=$uidenc&function={$_GET["function"]}");
}

function change_password_js(){
    $page=CurrentPageName();
    $uid=$_GET["ch-pass"];
    $uidenc=urlencode($_GET["ch-pass"]);
    $ct=new user($uid);
    $tpl=new template_admin();
    $tpl->js_dialog5($ct->DisplayName, "$page?ch-pass-popup=$uidenc");
}

function change_password_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $uid=$_GET["ch-pass-popup"];
    $ct=new user($uid);
    $tpl->field_hidden("ch-pass",$uid);
    $form[]=$tpl->field_password2("password", "{password}", null,true);
    echo $tpl->form_outside("{change_password}: $ct->DisplayName", @implode("\n", $form),null,
        "{apply}","dialogInstance5.close();","AllowAddUsers");

}
function change_password_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ct=new user($_POST["ch-pass"]);
    $ct->password=$_POST["password"];
    $ct->SaveUser();
    admin_tracks("Change LDAP password of member {$_POST["ch-pass"]}");
}

function new_group_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $uid=null;
	$uidenc=null;
	$ou=$_GET["ou"];
	$ouenc=urlencode($ou);
	if(isset($_GET["uid"])){	$uid=$_GET["uid"];}
	if($uid<>null){
		$uidenc=urlencode($uid);
		$uid_title=" &raquo; {member} $uid";
	}

    $EnableMultipleOrganizations=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMultipleOrganizations"));
	if( $EnableMultipleOrganizations==0){
        if($EnableMultipleOrganizations==0) {
            $ldap = new clladp();
            $hash = $ldap->hash_get_ou(false);
            $ouenc=urlencode($hash[0]);
            $ou=$hash[0];
        }
    }
	$tpl->js_dialog5("{new_group} >> {organization} $ou{$uid_title}","$page?new-group-popup=yes&ou=$ouenc&uid=$uidenc&function={$_GET["function"]}");
}

function link_group_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$uidenc=null;
	$ou=$_GET["ou"];
	$ouenc=urlencode($ou);
	$uid=$_GET["uid"];
	if($uid<>null){
		$uidenc=urlencode($uid);
		$uid_title=" &raquo; {member} $uid";
	}
	$tpl->js_dialog5("{link_group} >> {organization} $ou{$uid_title}","$page?link-group-popup=yes&ou=$ouenc&uid=$uidenc");
	
}
function link_group_perform(){
	header("content-type: application/x-javascript");
	$gpid=$_GET["link-group-perform"];
	$md=$_GET["md"];
	$uid=$_GET["uid"];
	$page=CurrentPageName();
	$uidenc=urlencode($uid);
	$ldap=new clladp();
	if($ldap->AddUserToGroup($gpid, $uid)){
		$js[]="$('#$md').remove();";
		$js[]="if(document.getElementById('table-my-userid-groups')){ LoadAjaxTiny('user-groups-table','$page?groups-table=$uidenc');}";
		$js[]="if(document.getElementById('table-loader-my-members')){ TableLoaderMyMemberearch();}";
		echo @implode("\n", $js);
	}
	
}

function group_js_unlink(){
	$gpid=$_GET["unlink-js"];
	$md=$_GET["md"];
	$uid=$_GET["uid"];
	$ldap=new clladp();
	if($ldap->GroupDeleteUser($gpid, $uid)){
		$js[]="$('#$md').remove();";
		$js[]="if(document.getElementById('table-loader-my-members')){ TableLoaderMyMemberearch();}";
		echo @implode("\n", $js);
	}
	
}

function link_group_table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ou=$_GET["ou"];
	$uid=$_GET["uid"];
	$uidenc=urlencode($uid);
	$group = new groups ( );
	$hash_group = $group->list_of_groups ( $ou, 1 );
	$users = new user($uid);
	$usergroups = $users->Groups_list();
	

	
	$html[]=$tpl->_ENGINE_parse_body("
<table id='table-link-userid-groups' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{groupname}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
    $TRCLASS=null;
    foreach ($hash_group as $gpid=>$GroupName){
		if(isset($usergroups[$gpid])){continue;}
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md5=md5($gpid);
		$html[]="<tr class='$TRCLASS' id='$md5'>";
		$html[]="<td>$GroupName</td>";
		$html[]="<td style='width:1%' nowrap>".$tpl->icon_select("Loadjs('$page?link-group-perform=$gpid&uid=$uidenc&md=$md5')","AllowAddGroup") ."</td>";
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
$(document).ready(function() { $('#table-link-userid-groups').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function link_group_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$uidenc=null;
	$ou=$_GET["ou"];
	$ouenc=urlencode($ou);
	$uid=$_GET["uid"];
	$uidenc=urlencode($uid);
	
	echo "<div id='link-group-to-user'></div>
	<script>LoadAjaxTiny('link-group-to-user','$page?link-group-table=yes&ou=$ouenc&uid=$uidenc');</script>
			
	";
	
	
}

function new_group_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	if($_GET["uid"]<>null){
		$uid_title=" {member} &raquo; {$_GET["uid"]}";
		$uidenc=urlencode($_GET["uid"]);}
	$form[]=$tpl->field_info("ou", "{organization}", $_GET["ou"]);
	if($_GET["uid"]<>null){$form[]=$tpl->field_info("uid", "{member}", $_GET["uid"]);}
	$form[]=$tpl->field_text("NewGroup", "{groupname}", null,true);


    if($_GET["function"]<>null){
        $js[]="{$_GET["function"]}();";
    }
	$js[]="if(document.getElementById('table-my-userid-groups')){ LoadAjaxTiny('user-groups-table','$page?groups-table=$uidenc');}";
	$js[]="if(document.getElementById('table-loader-my-members')){ TableLoaderMyMemberearch();}";
	$js[]="dialogInstance5.close();";
	$jsafter=@implode("\n", $js);
	$html[]=$tpl->form_outside("{new_group}", @implode("\n", $form),null,"{add}",$jsafter,"AllowAddGroup");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function new_group_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$group=$_POST["NewGroup"];
	$ou=$_POST["ou"];
	$uid=$_POST["uid"];
	
	if($ou==null){if($_SESSION["ou"]<>null){$ou=$_SESSION["ou"];}}
	$ldap=new clladp();
	$groupClass=new groups();
	$list=$groupClass->samba_group_list();
	
	if(is_array($list)){
        foreach ($list as $num=>$ligne){
			if(trim(strtolower($ligne))==trim(strtolower($group))){
				$tpl=new templates();
				echo $tpl->_ENGINE_parse_body('{no_samba_group_in_ou}');
				return;
			}
		}
	}
	
	if(!$ldap->AddGroup($group,$ou)){echo $ldap->ldap_last_error;}
	
	if($uid<>null){
		$generated_id=$ldap->GroupIDFromName($ou, $group);
		
		writelogs("Link gpid $generated_id to $uid",__FUNCTION__,__FILE__,__LINE__);
		$group=new groups($generated_id);
		if(!$group->AddUsertoThisGroup($uid)){
			echo "Failed to add user $uid into $ldap->generated_id....\n";
		}
		
	}
	
	
}

function tabs(){
    clean_xss_deep();
	$uid=$_GET["tabs"];
	$uidenc=urlencode($_GET["tabs"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ct=new user($uid);
	$array[$ct->DisplayName]="$page?profile=$uidenc&function={$_GET["function"]}";
	$array["{groups2}"]="$page?groups=$uidenc&function={$_GET["function"]}";
    $LighttpdArticaClientAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaClientAuth"));
    if($LighttpdArticaClientAuth==1){
        $sarray["uid"]=$uid;
        $sarray["type"]="LLDAP";
        $sarray["displayname"]=$ct->DisplayName;
        $sdata=urlencode(base64_encode(serialize($sarray)));
        $array["{client_certificate}"]="fw.member.client.cert.php?sdata=$sdata";
    }
	echo $tpl->tabs_default($array);
}

function profile(){
	$uid=$_GET["profile"];
    $uidEncoded=urlencode($uid);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ct=new user($uid);

    $CLUSTER_CLIENT=false;
    $EnableLDAPSyncProv=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProv"));
    $EnableLDAPSyncProvClient=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProvClient");
    if($EnableLDAPSyncProv==1){
        if($EnableLDAPSyncProvClient==1){
            $CLUSTER_CLIENT=true;
        }
    }
    $btn="{apply}";
    if($CLUSTER_CLIENT){$btn=null;}

	$form[]=$tpl->field_hidden("uid", $uid);
	$form[]=$tpl->field_info("ou", "{organization}",$ct->ou);
    $form[]=$tpl->field_info("ident", "{login_credentials}",$uid);
	$form[]=$tpl->field_text("givenName", "{firstname}", $ct->givenName,true);
	$form[]=$tpl->field_text("sn", "{lastname}", $ct->sn,true);
	$form[]=$tpl->field_text("mail", "{email}", $ct->mail,true);

	$form[]=$tpl->field_section("{work}");
	$form[]=$tpl->field_text("title", "{working_title}", $ct->title,false);
	$form[]=$tpl->field_text("telephoneNumber", "{phone}", $ct->telephoneNumber,false);
	$form[]=$tpl->field_text("mobile", "{mobile}", $ct->mobile,false);
	$jsrestart="blur();";
	if(!$CLUSTER_CLIENT){$tpl->form_add_button("{change_password}","Loadjs('$page?ch-pass=$uidEncoded')");}
	echo $tpl->form_outside("$uid", @implode("\n", $form),null,$btn,"$jsrestart","AllowAddUsers");
}





function profile_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();
	
	$ct=new user($_POST["uid"]);
	unset($_POST["uid"]);
	unset($_POST["ou"]);
	foreach ($_POST as $key=>$val){
		$ct->$key=$val;
	}
	
	$ct->SaveUser();
    admin_tracks("Saving LDAP member {$_POST["uid"]}");
    return true;
}

function groups(){
	$uid=urlencode($_GET["groups"]);
	$page=CurrentPageName();
	echo "<div id='user-groups-table'></div>
	<script>LoadAjaxTiny('user-groups-table','$page?groups-table=$uid');</script>		
	";
	
}

function groups_table(){
	$uid=$_GET["groups-table"];
	$users = new user($uid);
	$ou=$users->ou;
	$ouenc=urlencode($ou);
	$uidenc=urlencode($uid);
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

    if(!$CLUSTER_CLIENT) {
        $html[] = $tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>
			" . $tpl->button_label_table("{link_group}", "Loadjs('$page?link-group-js=yes&ou=$ouenc&uid=$uidenc')", "fa-plus", "AllowAddGroup")
            . $tpl->button_label_table("{new_group}", "Loadjs('$page?new-group-js=yes&ou=$ouenc&uid=$uidenc');", "fa-plus", "AllowAddGroup") . "
			</div>");
    }
	
$html[]=$tpl->_ENGINE_parse_body("
<table id='table-my-userid-groups' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
$html[]="<thead>";
$html[]="<tr>";
$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{displayname}</th>";
$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
$html[]="</tr>";
$html[]="</thead>";
$html[]="<tbody>";


$groups = $users->Groups_list();
$sambagroups = array ("515" => true, "548" => true, "544" => true, "551" => true, "512" => true, "514" => true, "513" => true, 550 => true, 552 => true );
$TRCLASS=null;
foreach ($groups as $gpid=>$GroupName){
	$text_class=null;
	$md=md5($gpid.$GroupName);
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}


    $unlink=$tpl->icon_unlink("Loadjs('$page?unlink-js=$gpid&ou=$ouenc&uid=$uidenc&gpid=$gpid&md=$md')","AllowAddGroup");
    if($CLUSTER_CLIENT){$unlink=$tpl->icon_unlink();}
	$html[]="<tr class='$TRCLASS' id='$md'>";
	$html[]="<td>". $tpl->td_href($GroupName,"{click_to_edit}","Loadjs('fw.groups.ldap.php?gpid=$gpid')")."</td>";
	$html[]="<td style='width:1%' nowrap>$unlink</td>";
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
$(document).ready(function() { $('#table-my-userid-groups').footable({ \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
</script>";

echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}


