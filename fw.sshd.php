<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.openssh.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["OpenSSHStatus"])){OPenSSHStatus();exit;}
if(isset($_GET["reconfigure"])){reconfigure_js();exit;}
if(isset($_GET["private-host-key-js"])){private_host_key_js();exit;}
if(isset($_GET["private-host-key-popup"])){private_host_key_popup();exit;}
if(isset($_GET["private-host-key-tab"])){private_host_key_tab();exit;}
if(isset($_GET["private-host-key-path"])){private_host_key_path();exit;}
if(isset($_GET["private-host-key-path-start"])){private_host_key_start();exit;}
if(isset($_GET["private-host-key-path-save"])){private_host_key_save();exit;}


if(isset($_GET["HostKeyAlgorithms-js"])){HostKeyAlgorithms_js();exit;}
if(isset($_GET["HostKeyAlgorithms-popup"])){HostKeyAlgorithms_popup();exit;}
if(isset($_GET["HostKeyAlgorithms-table"])){HostKeyAlgorithms_table();exit;}
if(isset($_GET["HostKeyAlgorithms-save"])){HostKeyAlgorithms_save();exit;}


if(isset($_GET["PubkeyAcceptedAlgorithms-js"])){PubkeyAcceptedAlgorithms_js();exit;}
if(isset($_GET["PubkeyAcceptedAlgorithms-popup"])){PubkeyAcceptedAlgorithms_popup();exit;}
if(isset($_GET["PubkeyAcceptedAlgorithms-table"])){PubkeyAcceptedAlgorithms_table();exit;}
if(isset($_GET["PubkeyAcceptedAlgorithms-save"])){PubkeyAcceptedAlgorithms_save();exit;}


if(isset($_POST["SSHDInterface"])){SSHDInterface_Save();exit;}
if(isset($_POST["SSHDRBL"])){reputation_save();exit;}
if(isset($_GET["reputation-js"])){reputation_js();exit;}
if(isset($_GET["reputation-popup"])){reputation_popup();exit;}
if(isset($_POST["SSHDIptables"])){SSHDIptables();exit;}
if(isset($_GET["disable-js"])){disable_sshd_js();exit;}
if(isset($_GET["disable-popup"])){disable_sshd_popup();exit;}
if(isset($_POST["DisableSSHConfig"])){disable_sshd_save();exit;}
if(isset($_GET["js-tiny"])){js_tiny_build();exit;}
if(isset($_GET["openssh-status-config"])){status_config();exit;}
if(isset($_GET["reload-compile"])){reload_compile();exit;}
if(isset($_GET["refresh-group-js"])){group_refresh_js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["SSHOnlyShellInaBox"])){save_config();exit;}
if(isset($_POST["StrictModes"])){save_config();exit;}
if(isset($_POST["UsePAM"])){save_config();exit;}
if(isset($_POST["PermitRootLogin"])){save_config();exit;}
if(isset($_POST["PermitTunnel"])){save_config();exit;}
if(isset($_GET["sessions-js"])){sessions_js();exit;}
if(isset($_GET["sessions-popup"])){sessions_popup_start();exit;}
if(isset($_GET["sessions-popup2"])){sessions_popup();exit;}
if(isset($_GET["sessions-kill"])){sessions_kill();exit;}
if(isset($_GET["lock-file-js"])){lock_conf();exit;}

if(isset($_GET["new-group-js"])){group_new();exit;}
if(isset($_GET["groups-js"])){groups_js();exit;}
if(isset($_GET["group-popup"])){groups_popup();exit;}
if(isset($_GET["groups-list"])){groups_list();exit;}
if(isset($_GET["group-delete"])){group_delete();exit;}
if(isset($_POST["group-delete"])){group_delete_perform();exit;}
if(isset($_POST["group-new"])){group_new_perform();exit;}
if(isset($_GET["group-users-js"])){group_users_js();exit;}
if(isset($_GET["group-users-popup"])){group_users_popup();exit;}
if(isset($_GET["group-users-list"])){group_users_list();exit;}
if(isset($_GET["link-group-js"])){group_users_link_js();exit;}
if(isset($_GET["group-users-link-popup"])){group_users_link_popup();exit;}
if(isset($_POST["group-users-link"])){group_users_link_perform();exit;}
if(isset($_GET["public-key-group-enable"])){public_key_group_enable();exit;}

if(isset($_POST["chgpasswd"])){user_passwd_change();exit;}
if(isset($_GET["public-key-password-js"])){user_passwd_js();exit;}
if(isset($_GET["public-key-password-popup"])){user_passwd_popup();exit;}
if(isset($_GET["new-user-js"])){new_user_js();exit;}
if(isset($_GET["new-user-popup"])){new_user_popup();exit;}
if(isset($_POST["unlink-group"])){group_unlink_perform();exit;}
if(isset($_GET["unlink-group"])){group_unlink_js();exit;}
if(isset($_POST["useradd"])){new_user_perform();exit;}

if(isset($_GET["limit-access-js"])){limit_access_js();exit;}
if(isset($_GET["limit-access-popup"])){limit_access_popup();exit;}
if(isset($_GET["limit-access-table"])){limit_access_table();exit;}
if(isset($_GET["limit-access-new-js"])){limit_access_new_js();exit;}
if(isset($_GET["limit-access-new-popup"])){limit_access_new_popup();exit;}
if(isset($_GET["limit-access-delete"])){limit_access_delete();exit;}

if(isset($_GET["limit-countries-js"])){limit_countries_js();exit;}
if(isset($_GET["limit-countries-popup"])){limit_countries_popup();exit;}
if(isset($_GET["limit-countries-table"])){limit_countries_table();exit;}
if(isset($_GET["limit-countries-deny-all"])){limit_countries_deny_all();exit;}
if(isset($_GET["limit-countries-allow-all"])){limit_countries_allow_all();exit;}
if(isset($_GET["limit-access-country"])){limit_countries_single();exit;}


if(isset($_POST["username"])){limit_access_new_save();exit;}
if(isset($_GET["banner-js"])){banner_js();exit;}
if(isset($_GET["banner-popup"])){banner_popup();exit;}
if(isset($_POST["Banner"])){banner_save();exit;}
if(isset($_GET["public-key-js"])){public_key_js();exit;}
if(isset($_GET["public-key-popup"])){public_key_popup();exit;}
if(isset($_GET["public-keys-userlist"])){public_key_userlist();exit;}
if(isset($_POST["passphrase"])){public_key_user_save();exit;}
if(isset($_GET["public-key-user-js"])){public_key_user_js();exit;}
if(isset($_GET["public-key-user-popup"])){public_key_user_popup();exit;}
if(isset($_GET["public-key-user-form"])){public_key_user_form();exit;}
if(isset($_GET["public-key-user-enable"])){public_key_user_enable();exit;}
if(isset($_GET["public-key-user-download"])){public_key_user_download();exit;}

if(isset($_GET["AuthorizedKeys-create-js"])){AuthorizedKeys_create_js();exit;}
if(isset($_GET["AuthorizedKeys-create-popup"])){AuthorizedKeys_create_popup();exit;}
if(isset($_GET["AuthorizedKeys-create-popup2"])){AuthorizedKeys_create_popup2();exit;}
if(isset($_POST["KeyUsername"])){AuthorizedKeys_create_save();exit;}
if(isset($_GET["AuthorizedKeys-create-results"])){AuthorizedKeys_create_results();exit;}


if(isset($_GET["AuthorizedKeys-js"])){AuthorizedKeys_js();exit;}
if(isset($_GET["AuthorizedKeys-popup"])){AuthorizedKeys_popup();exit;}
if(isset($_GET["AuthorizedKeys-table"])){AuthorizedKeys_table();exit;}
if(isset($_GET["AuthorizedKeys-add-js"])){AuthorizedKeys_add_js();exit;}
if(isset($_GET["AuthorizedKeys-add-popup"])){AuthorizedKeys_add_popup();exit;}
if(isset($_GET["AuthorizedKeys-add-download"])){AuthorizedKeys_add_download();exit;}
if(isset($_GET["AuthorizedKeys-add-delete"])){AuthorizedKeys_add_delete();exit;}
if(isset($_POST["AuthorizedKeys-add-delete"])){AuthorizedKeys_add_delete_perform();exit;}

if(isset($_GET["AuthorizedKeys-del-js"])){AuthorizedKeys_del();exit;}
if(isset($_GET["config-file-js"])){config_file_js();exit;}
if(isset($_GET["config-file-popup"])){config_file_popup();exit;}
if(isset($_POST["configfile"])){config_file_save();exit;}
if(isset($_POST["zkey"])){AuthorizedKeys_add_save();exit;}
if(isset($_GET["settings-js"])){main_setting_js();exit;}
if(isset($_GET["settings-popup"])){main_setting_popup();exit;}

page();

function group_new():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_prompt("{add_group}","{group name}",ico_add_group,$page,"group-new","LoadAjax('public-keys-groups','$page?groups-list=yes');");
    return true;
}
function group_new_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $group=$_POST["group-new"];
    $groupenc=urlencode($group);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?group-add=$groupenc");
    $fname=PROGRESS_DIR."/groupadd.$group";
    $val=trim(@file_get_contents($fname));
    if($val<>null){echo $val;return false;}
    admin_tracks("Add new system Group $group");
    return true;
}



function SSHDIptables():bool{
    $sock=new sockets();
    $sock->SET_INFO("SSHDIptables",$_POST["SSHDIptables"]);
    $sock->REST_API("/reload");
    return admin_tracks("Save SSH Firewall protection to {$_POST["SSHDIptables"]}");
}

function user_passwd_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $user=$_GET["public-key-password-js"];
    $userenc=urlencode($user);
    $tpl->js_dialog8("$user {password}","$page?public-key-password-popup=$userenc");
    return true;
}
function user_passwd_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $user=$_GET["public-key-password-popup"];
    $js="LoadAjax('public-keys-userlist','$page?public-keys-userlist=yes');";
    $form[]=$tpl->field_hidden("chgpasswd",$user);
    $form[]=$tpl->field_password("chgpasswdStr","{password}",null,true);
    echo $tpl->form_outside("$user {password}",$form,null,"{update}","$js;dialogInstance8.close();","AsSystemAdministrator");
    return true;
}
function user_passwd_change():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $user=$tpl->CLEAN_BAD_XSS($_POST["chgpasswd"]);
    $password=$_POST["chgpasswdStr"];
    $userEnc=base64_encode(serialize(array($user,$password)));
    admin_tracks("Change a system user password of [$user]");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?system-user-pass=$userEnc");
    return true;
}
function new_user_js():bool{
    $users=new usersMenus();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $groupenc=null;
    if(isset($_GET["group"])){
        $groupenc=urlencode($_GET["group"]);
    }
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();exit();}
    $tpl->js_dialog8("{add_user}","$page?new-user-popup=yes&group=$groupenc");
    return true;
}
function new_user_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $groups=explode("\n",file_get_contents("/etc/group"));
    $zGroups[null]="{none}";
    $groupdef=null;
    $BadUsers=BadUsers();
    if(isset($_GET["group"])){
        $groupdef=$_GET["group"];
    }

    foreach ($groups as $line){
        if(preg_match("#^(.+?):#",$line,$re)){
            $group=$re[1];
            if(isset($BadUsers[$group])){continue;}
            $zGroups[$group]=$group;
        }
    }

    $js="Loadjs('$page?refresh-group-js=yes');";
    $form[]=$tpl->field_text("useradd","{username}",null,true);
    if($groupdef==null) {
        $form[] = $tpl->field_array_hash($zGroups, "group", "{group}");
    }else{
        $form[]=$tpl->field_hidden("group",$groupdef);

    }
    $form[]=$tpl->field_password("userpass","{password}",null,true);
    echo $tpl->form_outside(null,$form,null,"{create}","$js;dialogInstance8.close();","AsSystemAdministrator");
    return true;
}

function new_user_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $user=$tpl->CLEAN_BAD_XSS($_POST["useradd"]);
    $password=$_POST["userpass"];
    $userEnc=base64_encode(serialize(array($user,$password)));
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?system-user-add=$userEnc");
    $fname=PROGRESS_DIR."/useradd-$user";




    if(trim(@file_get_contents($fname))=="FALSE"){
        admin_tracks("Creating a system user named [$user] failed");
        echo "$user FAILED\n";
        echo @file_get_contents(PROGRESS_DIR."/useradd-$user.log");
        return false;
    }
    admin_tracks("Creating a system user named [$user] success");
    return true;
}

function page():bool{
	$page=CurrentPageName();
    $tpl=new template_admin();
    $OPENSSH_VER=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENSSH_VER");
    $html=$tpl->page_header("{APP_OPENSSH} v$OPENSSH_VER",ico_terminal,
        "{OPENSSH_EXPLAIN}","$page?tabs=yes","sshd","progress-sshd-restart",
        false,"table-loader-sshd-service");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_OPENSSH}",$html);
        echo $tpl->build_firewall();
        return true;
    }

	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function public_key_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return false;}
	return $tpl->js_dialog1("{APP_OPENSSH} >> SSH Keys", "$page?public-key-popup=yes");
}
function groups_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return false;}
    $tpl->js_dialog1("{system} >> {groups2}", "$page?group-popup=yes");
    return true;
}
function reload_compile():bool{
    admin_tracks("Asking to compile OpenSSH parameters and reload");
    $sock=new sockets();
    $sock->REST_API("/ssh/reload");
    return true;
}
function group_users_js():bool{
    $gpname=$_GET["group-users-js"];
    $gpenc=urlencode($gpname);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return false;}
    $tpl->js_dialog2("{system} >> {group} $gpname", "$page?group-users-popup=$gpenc",650);
    return true;
}
function group_users_popup():bool{
    $gpname=$_GET["group-users-popup"];
    $gpenc=urlencode($gpname);
    $page=CurrentPageName();
    $html="
    <input type='hidden' id='group-users-list-name' value='$gpname'>
	<div id='group-users-list'></div>
	<script>
		LoadAjax('group-users-list','$page?group-users-list=$gpenc');
	</script>		
			
	";

    echo $html;
    return true;
}
function group_users_link_js():bool{
    $gpname=$_GET["link-group-js"];
    $gpenc=urlencode($gpname);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return false;}
    $tpl->js_dialog3("{system} >> {group} $gpname {link_user}", "$page?group-users-link-popup=$gpenc",550);
    return true;
}
function group_users_link_popup():bool{
    $gpname=$_GET["group-users-link-popup"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $MAIN=array();
    if(!$users->AsSystemAdministrator){die();}

    $users=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?unixLocalUsers=yes")));
    ksort($users);
    foreach ($users as $uid){
        $MAIN[$uid]=$uid;
    }

    $jsafter="Loadjs('$page?refresh-group-js=yes');dialogInstance3.close();";
    $form[]=$tpl->field_hidden("group-users-link",$gpname);
    $form[]=$tpl->field_array_hash($MAIN,"uid","{member}",null);
    echo $tpl->form_outside("{link_member} $gpname", $form,"","{link}",$jsafter,"AsSystemAdministrator");
    return true;
}
function group_users_link_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $gpname=$_POST["group-users-link"];
    $uid=$_POST["uid"];
    if($uid==null){return false;}
    $array=urlencode(base64_encode(serialize(array($gpname,$uid))));
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?group-adduser=$array");
    $fname=PROGRESS_DIR."/linkuser.$gpname.$uid";
    if(is_file($fname)){
        $tpl->post_error(@file_get_contents($fname));
        admin_tracks("failed to link system user $uid to group $gpname");
        return false;
    }
    admin_tracks("Success to Link system user $uid to group $gpname");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return true;
}
function group_unlink_js():bool{
    $group=$_GET["group"];
    $user=$_GET["unlink-group"];
    $md=$_GET["md"];
    $tpl=new template_admin();
    $tpl->js_confirm_delete("$user/$group","unlink-group","$user:$group","$('#$md').delete();");
    return true;
}
function group_unlink_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $pattern=explode(":",$_POST["unlink-group"]);
    $uid=$pattern[0];
    $gpname=$pattern[1];
    if($uid==null){return false;}
    $array=urlencode(base64_encode(serialize(array($gpname,$uid))));
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?group-deluser=$array");
    admin_tracks("Unlink system user $uid from system group $gpname");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return true;
}
function group_refresh_js():bool{
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $f[]="if(document.getElementById('group-users-list')){";
    $f[]="\tif(document.getElementById('group-users-list-name')){";
    $f[]="\t\tvar group=document.getElementById('group-users-list-name').value;";
    $f[]="\t\tLoadAjax('group-users-list','$page?group-users-list='+group);";
    $f[]="\t}";
    $f[]="}";
    $f[]="if(document.getElementById('public-keys-userlist')){";
    $f[]="\tLoadAjax('public-keys-userlist','$page?public-keys-userlist=yes');";
    $f[]="}";
    $f[]="if(document.getElementById('public-keys-groups')){";
    $f[]="\tLoadAjax('public-keys-groups','$page?groups-list=yes');";
    $f[]="}";
    $f[]="if(document.getElementById('openssh-status-config')){";
    $f[]="\tLoadAjax('openssh-status-config','$page?openssh-status-config=yes');";
    $f[]="}";

    $f[]="";

    echo @implode("\n",$f);
    return true;

}
function group_users_list():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $group=$_GET["group-users-list"];
    $groupenc=urlencode($group);
    if(!function_exists("posix_getgrnam")){
        echo $tpl->div_error("posix_getgrnam no such function!!");
        return true;
    }

    $userinfo = posix_getgrnam($group);

    if(!$userinfo){
        $userinfo=array();
    }

    if(!isset($userinfo["members"])){$userinfo["members"]=array();}

    $add="Loadjs('$page?link-group-js=$groupenc')";


    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='".ico_add_user."'></i> {link_user} </label>";

    $addN="Loadjs('$page?new-user-js=&group=$groupenc')";
    $html[]="<label class=\"btn btn btn-warning\" OnClick=\"$addN\"><i class='".ico_add_user."'></i> {add_user} </label>";
    $html[]="</div>";

    $html[]="<table id='table-$group-groups' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true style='width:1%'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[]="<th data-sortable=false style='width:1%'></th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $icons=ico_member;
    $TRCLASS=null;
    $BadUsers=BadUsers();
    foreach ($userinfo["members"] as $users){
//        if(isset($BadUsers[$group])){continue;}
        if($group==null){continue;}


        $aclid=md5($users.$group);
        $patternenc=urlencode($users);
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $delete=$tpl->icon_unlink("Loadjs('$page?unlink-group=$patternenc&group=$groupenc&md=$aclid')","AsSystemAdministrator");

        if(isset($BadUsers[$users])){
            $delete=$tpl->icon_unlink();
        }

        $html[]="<tr class='$TRCLASS' id='$aclid'>";
        $html[]="<td class=\"center\"><i class='$icons'></i></td>";
        $html[]="<td>". $tpl->td_href($users,"{click_to_edit}","Loadjs('$page?public-key-user-js=$patternenc')")."</td>";
        $html[]="<td>$delete</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$group-groups').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}

function config_file_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return;}
	$tpl->js_dialog1("{APP_OPENSSH} >> {config_file}", "$page?config-file-popup=yes");
	
}
function config_file_save():bool{
	
	$data=url_decode_special_tool($_POST["configfile"]);
	@file_put_contents(PROGRESS_DIR."/sshd.config", $data);
    return admin_tracks("Modified the OpenSSH configuration file entirely");
}

function config_file_popup(){
	$tpl=new template_admin();
	$sock=new sockets();
	$sock->getFrameWork("sshd.php?config-file=yes");
	$data=@file_get_contents(PROGRESS_DIR."/sshd.config");
	$form[]=$tpl->field_textareacode("configfile", null, $data);
	echo $tpl->form_outside("{config_file}", @implode("", $form),"{display_generated_configuration_file_ssh}","{apply}","","AsSystemAdministrator");
	
}


function AuthorizedKeys_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
    $function="blur";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }
	if(!$users->AsSystemAdministrator){echo "alert('No privileges');";return false;}
	return $tpl->js_dialog1("{APP_OPENSSH} >> {AuthorizedKeys}", "$page?AuthorizedKeys-popup=yes&function=$function");
}
function AuthorizedKeys_create_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $function="blur";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }

    if(!$users->AsSystemAdministrator){echo "alert('No privileges');";return false;}
    return $tpl->js_dialog2("{APP_OPENSSH} >> {generate_new_key}", "$page?AuthorizedKeys-create-popup=yes&function=$function");
}

function public_key_user_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$user=$_GET["public-key-user-js"];
	if(!$users->AsSystemAdministrator){echo "alert('No privileges');";return false;}
	$userenc=urlencode($user);
	$tpl->js_dialog2("{APP_OPENSSH} >> {certificate} >> $user", "$page?public-key-user-popup=$userenc");	
    return true;
}
function AuthorizedKeys_add_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
    $function="";
    $username="";$usernameEnc="";$usernameText="";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }
    if(isset($_GET["username"])){
        $username=$_GET["username"];
        $usernameEnc="&username=".urlencode($username);
        $usernameText=" >> $username";
    }
	$zmd5=$_GET["zmd5"];
	$new_key=" >> {new_key}";
	if(!$users->AsSystemAdministrator){echo "alert('No privileges');";return false;}
	if($zmd5<>null){$new_key=null;}
	return $tpl->js_dialog2("{APP_OPENSSH} >> {AuthorizedKeys}$new_key$usernameText", "$page?AuthorizedKeys-add-popup=yes&zmd5=$zmd5&function=$function$usernameEnc");

}

function AuthorizedKeys_add_popup():bool{
	$zmd5=$_GET["zmd5"];
	$zkey=null;
    $function="blur";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }
	$page=CurrentPageName();
	$tpl=new template_admin();
	$btname="{add}";
    $limitfeatures="";
    $limitaddr="";
    $username="";
    $usernameenc="";
    if(isset($_GET["username"])){
        $username=$_GET["username"];
        $usernameenc=urlencode($username);
    }

    $title="";
	if($zmd5<>null){
        $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
		$ligne=$q->mysqli_fetch_array("SELECT zkey,limitaddr,limitfeatures,email,zippackage,username FROM sshd_authorizedkeys WHERE `zmd5`='$zmd5'");
		$zkey=$ligne["zkey"];
		$btname="{apply}";
        $limitaddr=$ligne["limitaddr"];
        $limitfeatures=$ligne["limitfeatures"];
	}
	if(strlen($ligne["email"])>3){
        $title=$ligne["email"];
    }
    $Download=false;
    if(strlen($ligne["zippackage"])>20){
        $Download=true;
    }
    if(strlen($ligne["username"])>20){
        $usernameenc=urlencode($ligne["username"]);
    }

	$form[]=$tpl->field_textarea("zkey","ssh-rsa",$zkey,"100%","120px");
    $form[]=$tpl->field_textarea("limitaddr","{limit_by_subnet}",$limitaddr,"100%","120px");
    $tb=explode(",",$limitfeatures);
    $CUR=array();
    foreach ($tb as $line){
        $CUR[$line]=true;
    }

    $Features=explode(",","no-port-forwarding,no-agent-forwarding,no-X11-forwarding,no-pty,no-user-rc");
    foreach ($Features as $AvFeatures){
        $value=0;
        if($CUR[$AvFeatures]){
            $value=1;
        }
        $form[]=$tpl->field_checkbox("FEATURE-$AvFeatures",$AvFeatures,$value);

    }

    $jsrestart="Loadjs('$page?reload-compile=yes');";
	$jsAfter="dialogInstance2.close();LoadAjax('AuthorizedKeys-div','$page?AuthorizedKeys-table=yes');$jsrestart;$function();";

    if($Download) {
        $topbuttons[] = array("document.location.href='/$page?AuthorizedKeys-add-download=$zmd5';", ico_download, "{download}","AsSystemAdministrator");
    }
    $topbuttons[]=array("Loadjs('$page?AuthorizedKeys-add-delete=$zmd5&username=$usernameenc&function=$function')",ico_trash,"{delete}","AsSystemAdministrator");

    $html[]=$tpl->table_buttons($topbuttons);
    $html[]=$tpl->form_outside($title, @implode("\n", $form),null,$btname,$jsAfter,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
	return true;
}
function AuthorizedKeys_add_delete():bool{
    $zmd5=$_GET["AuthorizedKeys-add-delete"];
    $username=$_GET["username"];
    $function=$_GET["function"];
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $ligne=$q->mysqli_fetch_array("SELECT email FROM sshd_authorizedkeys WHERE `zmd5`='$zmd5'");
    $eMail=$ligne["email"];
    $tpl=new template_admin();

    $id=md5($username);
    $userenc=urlencode($username);
    $js[]="LoadAjax('user-start-$id','fw.system.users.php?user-popup=$userenc&function=$function')";
    $js[]="dialogInstance2.close();";
    if(strlen($function)>3){
        $js[]="$function()";
    }
   return $tpl->js_confirm_delete("$eMail/$username","AuthorizedKeys-add-delete",$zmd5,@implode(";",$js));
}
function AuthorizedKeys_add_delete_perform():bool{
    $zmd5=$_POST["AuthorizedKeys-add-delete"];
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $ligne=$q->mysqli_fetch_array("SELECT email FROM sshd_authorizedkeys WHERE `zmd5`='$zmd5'");
    $eMail=$ligne["email"];
    $q->QUERY_SQL("DELETE FROM sshd_authorizedkeys WHERE `zmd5`='$zmd5'");
    if(!$q->ok){
        echo $q->mysql_error;
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks("Delete a SSH Key for $eMail");
}
function AuthorizedKeys_add_download():bool{
    $zmd5=$_GET["AuthorizedKeys-add-download"];
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $ligne=$q->mysqli_fetch_array("SELECT email,zippackage FROM sshd_authorizedkeys WHERE `zmd5`='$zmd5'");
    $eMail=$ligne["email"];
    $zippackage=base64_decode($ligne["zippackage"]);

    header('Content-type: application/zip');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"authorizedkeys-$eMail.zip\"");
    header("Pragma: public");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé

    header("Content-Length: ".strlen($zippackage));
    ob_clean();
    flush();
    echo $zippackage;
    return admin_tracks("Downloaded Public SSH Key for $eMail");

}
function AuthorizedKeys_add_save():bool{
	$tpl=new template_admin();
    $tpl->CLEAN_POST();

    $zkey=trim(url_decode_special_tool($_POST["zkey"]));
	if(!preg_match("#^ssh-rsa\s+(.+?)\s+(.+?)$#", $zkey,$re)){
		echo "jserror:ssh-rsa incorrect!\n";
		return false;
	}

    $Features=array();
    foreach ($_POST as $key=>$value){
        if (!preg_match("#FEATURE-(.+)#",$key,$re)){
            continue;
        }
        if($value==0){
            continue;
        }
        $Features[]=$re[1];
    }
    $limitfeatures=@implode(",",$Features);
    $limitaddr=$_POST["limitaddr"];


	$md5=md5($zkey);
	$zkey=mysql_escape_string2($zkey);
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
	$q->QUERY_SQL("DELETE FROM sshd_authorizedkeys WHERE `zmd5`='$md5'");
	$q->QUERY_SQL("INSERT OR IGNORE INTO sshd_authorizedkeys (`zmd5`,`zkey`,`limitaddr`,`limitfeatures`) VALUES ('$md5','$zkey','$limitaddr','$limitfeatures')");
	if(!$q->ok){echo $q->mysql_error_html(true);return false;}
	@unlink(PROGRESS_DIR."/sshd.config");
    admin_tracks_post("Add a new Authorized Key with md5=$md5 in OpenSSH service");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return true;
}
function AuthorizedKeys_del():bool{
	$zmd5=$_GET["AuthorizedKeys-del-js"];
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
	$tpl=new template_admin();
	$q->QUERY_SQL("DELETE FROM sshd_authorizedkeys WHERE `zmd5`='$zmd5'");
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."')";return false;}
	echo "$('#$zmd5').remove();\n";
    admin_tracks("Remove Authorized Key with md5=$zmd5 from the OpenSSH service");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return true;
	
}
function group_delete():bool{
    $tpl=new template_admin();
    $group=$_GET["group-delete"];
    $md=$_GET["md"];
    $tpl->js_confirm_delete($group,"group-delete",$group,"$('#$md').remove()");
    return true;
}
function group_delete_perform():bool{
    $group=$_POST["group-delete"];
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?group-delete=$group");
    $fname=PROGRESS_DIR."/groupdel.$group";
    $val=trim(@file_get_contents($fname));
    if($val<>null){echo $val;return false;}
    admin_tracks("Remove system Group $group");
    return true;
}
function groups_popup():bool{
    $page=CurrentPageName();
    $html="
	<div id='public-keys-groups'></div>
	<script>
		LoadAjax('public-keys-groups','$page?groups-list=yes');
	</script>		
			
	";

    echo $html;
    return true;
}
function public_key_popup():bool{
	$page=CurrentPageName();
	$html="
	
	<div id='public-keys-userlist'></div>
	<script>
		LoadAjax('public-keys-userlist','$page?public-keys-userlist=yes');
	</script>		
			
	";
	
	echo $html;
    return true;
	
}
function AuthorizedKeys_create_popup():bool{
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }
    $page=CurrentPageName();
    echo "<div id='AuthorizedKeysCreatePopup'></div>
    <script>LoadAjax('AuthorizedKeysCreatePopup','$page?AuthorizedKeys-create-popup2=yes&function=$function')</script>";
    return true;
}
function AuthorizedKeys_create_popup2():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $form[]=$tpl->field_text("KeyUsername","{username}",null,true);
    $function="blur";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }

    echo $tpl->form_outside("",$form,"{generate_new_key_explain}","{create}",
        "LoadAjax('AuthorizedKeysCreatePopup','$page?AuthorizedKeys-create-results=yes&function=$function');$function();");

    return true;
}
function AuthorizedKeys_create_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $username=$_POST["KeyUsername"];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/genkey/$username"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    return admin_tracks("Created a SSH Key for $username");

}
function AuthorizedKeys_create_results():bool{
    $tpl=new template_admin();
    $fname="ressources/logs/web/Authorized.zip";
    if(!is_file($fname)){
        echo $tpl->div_error("Authorized package not found");
        return false;
    }
    $size=filesize($fname);
    $size=FormatBytes($size/1024);
    $date=date("Y-m-d H:i:s",filemtime($fname));

    $filedown="
		<div style='margin:15px' class='center'>
		<a href='$fname'>
		<img src='img/file-compressed-128.png' alt='' class='img-rounded'>
		</a><br>
		<a href='$fname'><small>Authorized.zip ($size)</small></a><br>
		<a href='$fname'><small>$date</small></a>
		</div>";

    echo $filedown;
    return true;

}
function AuthorizedKeys_table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $function=$_GET["function"];

	$import="Loadjs('$page?AuthorizedKeys-add-js=yes&function=$function');";
    $add="Loadjs('$page?AuthorizedKeys-create-js=yes&function=$function');";

    $topbuttons[] = array($import, ico_plus, "{import_key}");
    $topbuttons[] = array($add, ico_plus, "{generate_new_key}");

    $buttons=base64_encode($tpl->th_buttons($topbuttons));

	$html[]="<table id='table-AuthorizedKeys' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true style='width:1%'></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{AuthorizedKeys}</th>";
	$html[]="<th data-sortable=false style='width:1%'></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $sql="SELECT * FROM sshd_authorizedkeys";
    if(isset($_GET["search"])){
        if(strlen($_GET["search"]) > 1){
            $search="*".$_GET["search"]."*";
            $search=str_replace("**","*",$search);
            $search=str_replace("**","*",$search);
            $search=str_replace("*","%",$search);
            $sql="SELECT * FROM sshd_authorizedkeys WHERE zkey LIKE '$search'";
        }
    }
	$results=$q->QUERY_SQL($sql);
    $TRCLASS=null;
	foreach ($results as $index=>$ligne){
        $aclid=$ligne["zmd5"];
		$delete=$tpl->icon_delete("Loadjs('$page?AuthorizedKeys-del-js=$aclid')","AsSystemAdministrator");
		$zkey=substr($ligne["zkey"],0,80)."...";
        $username_text="";
        $username=$ligne["username"];
        if(strlen($username)>1){
            $username_text="<div style='text-align:right;padding-right:44px;margin-top: 4px'><i class='".ico_member."'></i>&nbsp;<strong>$username</strong></div>";
        }
		$html[]="<tr class='$TRCLASS' id='$aclid'>";
		$html[]="<td class=\"center\" id='$index'><i class='fa fa-key'></i></td>";
		$html[]="<td>". $tpl->td_href($zkey,"{click_to_edit}","Loadjs('$page?AuthorizedKeys-add-js=yes&zmd5=$aclid')")."$username_text</td>";
		$html[]="<td>$delete</td>";
		$html[]="</tr>";
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	document.getElementById('ssh-authorized-keys-buttons').innerHTML = base64_decode('$buttons');
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-AuthorizedKeys').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	return true;
}
function BadUsers():array{
    $badUsers["nogroup"]=true;
	$badUsers["systemd-journal"]=true;
	$badUsers["input"]=true;
	$badUsers["kvm"]=true;
	$badUsers["render"]=true;
	$badUsers["crontab"]=true;
    $badUsers["ssl-cert"]=true;
	$badUsers["postdrop"]=true;
	$badUsers["mlocate"]=true;
	$badUsers["sambashare"]=true;
	$badUsers["winbindd_priv"]=true;
	$badUsers["nvram"]=true;
	$badUsers["quaggavty"]=true;
	$badUsers["rdma"]=true;
	$badUsers["netdev"]=true;
    $badUsers["adm"]=true;
    $badUsers["disk"]=true;
    $badUsers["tty"]=true;
    $badUsers["kmem"]=true;
	$badUsers["dialout"]=true;
	$badUsers["fax"]=true;
	$badUsers["voice"]=true;
	$badUsers["cdrom"]=true;
	$badUsers["floppy"]=true;
	$badUsers["tape"]=true;
	$badUsers["sudo"]=true;
	$badUsers["audio"]=true;
	$badUsers["dip"]=true;
    $badUsers["shadow"]=true;
	$badUsers["utmp"]=true;
	$badUsers["video"]=true;
	$badUsers["sasl"]=true;
	$badUsers["plugdev"]=true;
    $badUsers["ArticaStats"]=true;
    $badUsers["root"]=true;
    $badUsers["apt-mirror"]=true;
    $badUsers["avahi"]=true;
    $badUsers["bin"]=true;
    $badUsers["clamav"]=true;
    $badUsers["cyrus"]=true;
    $badUsers["daemon"]=true;
    $badUsers["davfs2"]=true;
    $badUsers["debian-spamd"]=true;
    $badUsers["debian-transmission"]=true;
    $badUsers["dnscatz"]=true;
    $badUsers["freerad"]=true;
    $badUsers["ftp"]=true;
    $badUsers["games"]=true;
    $badUsers["glances"]=true;
    $badUsers["gnats"]=true;
    $badUsers["irc"]=true;
    $badUsers["kibana"]=true;
    $badUsers["list"]=true;
    $badUsers["lp"]=true;
    $badUsers["mail"]=true;
    $badUsers["man"]=true;
    $badUsers["memcache"]=true;
    $badUsers["mosquitto"]=true;
    $badUsers["msmtp"]=true;
    $badUsers["munin"]=true;
    $badUsers["mysql"]=true;
    $badUsers["news"]=true;
    $badUsers["nobody"]=true;
    $badUsers["ntp"]=true;
    $badUsers["opendkim"]=true;
    $badUsers["openldap"]=true;
    $badUsers["policyd-spf"]=true;
    $badUsers["postfix"]=true;
    $badUsers["prads"]=true;
    $badUsers["privoxy"]=true;
    $badUsers["proftpd"]=true;
    $badUsers["proxy"]=true;
    $badUsers["quagga"]=true;
    $badUsers["redis"]=true;
    $badUsers["redsocks"]=true;
    $badUsers["smokeping"]=true;
    $badUsers["squid"]=true;
    $badUsers["sshd"]=true;
    $badUsers["statd"]=true;
    $badUsers["stunnel4"]=true;
    $badUsers["sync"]=true;
    $badUsers["sys"]=true;
    $badUsers["systemd-coredump"]=true;
    $badUsers["systemd-network"]=true;
    $badUsers["systemd-resolve"]=true;
    $badUsers["systemd-timesync"]=true;
    $badUsers["unbound"]=true;
    $badUsers["uucp"]=true;
    $badUsers["vde2-net"]=true;
    $badUsers["vnstat"]=true;
    $badUsers["wazuh"]=true;
    $badUsers["www-data"]=true;
    $badUsers["ziproxy"]=true;
    $badUsers["Debian-ow"]=true;
    $badUsers["Debian-snmp"]=true;
    $badUsers["_apt"]=true;
    $badUsers["_rpc"]=true;
    $badUsers["src"]=true;
    return $badUsers;
}
function groups_list():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    if(!function_exists("posix_getgrnam")){
        echo $tpl->div_error("posix_getgrnam no such function!!");
        return true;
    }

    $add="Loadjs('$page?new-group-js=yes')";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='".ico_add_group."'></i> {add_group} </label>";
    $addN="Loadjs('$page?new-user-js=yes')";
    $html[]="<label class=\"btn btn btn-warning\" OnClick=\"$addN\"><i class='".ico_add_user."'></i> {add_user} </label>";


    $html[]="</div>";


    $html[]="<table id='table-sshd-groups' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true style='width:1%'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{groups}</th>";
    $html[]="<th data-sortable=false style='width:1%'></th>";
    $html[]="<th data-sortable=false style='width:1%'></th>";
    $html[]="<th data-sortable=false style='width:1%'></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $icons="fa-solid fa-people-group";
    $groups=explode("\n",file_get_contents("/etc/group"));

    $BadUsers=BadUsers();

    $TRCLASS=null;
    $sshd=new openssh();
    $ALR=array();
    $AllowGroups=$sshd->AllowGroups;
    foreach ($AllowGroups as $gpname){
        $ALR[$gpname]=1;
    }

    $locked_group["ssh"]=true;
    $locked_group["backup"]=true;
    $locked_group["operator"]=true;
    $locked_group["staff"]=true;
    $locked_group["users"]=true;




    foreach ($groups as $line){
        if(!preg_match("#^(.+?):#",$line,$re)){continue;}
        $group=trim($re[1]);
        VERBOSE("[$group]",__LINE__);
        if(isset($BadUsers[$group])){continue;}
        if($group==null){continue;}
        $userinfo=posix_getgrnam($group);
        if(!isset($userinfo["members"])){$userinfo["members"]=array();}
        $enabled=0;
        if(isset($ALR[$group])){
            $enabled=1;
        }
        $aclid=md5($group);
        $patternenc=urlencode($group);
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $check=$tpl->icon_check($enabled,"Loadjs('$page?public-key-group-enable=$patternenc&md=$aclid')",null,"AsSystemAdministrator");

        $delete=$tpl->icon_delete("Loadjs('$page?group-delete=$patternenc&md=$aclid')","AsSystemAdministrator");

        if(isset($locked_group[$group])){
            $delete=$tpl->icon_delete(null);
        }
        $CountOFUsers=count($userinfo["members"]);
        $html[]="<tr class='$TRCLASS' id='$aclid'>";
        $html[]="<td class=\"center\"><i class='$icons'></i></td>";
        $html[]="<td>". $tpl->td_href($group,"{click_to_edit}","Loadjs('$page?group-users-js=$patternenc')")."</td>";
        $html[]="<td style='width:1%'>$CountOFUsers</td>";
        $html[]="<td style='width:1%'>$check</td>";
        $html[]="<td style='width:1%'>$delete</td>";
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
	$(document).ready(function() { $('#table-sshd-groups').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function sessions_popup_start():bool{
    $page=CurrentPageName();
    echo "<div id='sessions-popup'></div>
<script>LoadAjax('sessions-popup','$page?sessions-popup2=yes')</script>";
    return true;

}
function sessions_kill():bool{
    $pid=intval($_GET["sessions-kill"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/kill/$pid");
    echo "$('#id$pid').remove();";
    return admin_tracks("Kill OpenSSH session $pid");
}
function sessions_popup(){
    $page=CurrentPageName();
    $t=time();
    $tpl=new template_admin();
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true style='width:1%'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
    $html[]="<th data-sortable=false style='width:1%'>Pid</th>";
    $html[]="<th data-sortable=false style='width:1%'>{ipaddr}</th>";
    $html[]="<th data-sortable=false style='width:1%'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/connected");
    $json=json_decode($data);
    $Array=$json->Info->Users;
    $TRCLASS=null;
    foreach ($Array as $main){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='id{$main->Pid}'>";
        $html[]="<td class=\"center\"><i class='fa fa-duotone fa-user'></i></td>";
        $html[]="<td>$main->User</td>";
        $html[]="<td>$main->Pid</td>";
        $html[]="<td>$main->IPAddr</td>";
        $kill=$tpl->icon_unlink("Loadjs('$page?sessions-kill=$main->Pid')");
        $html[]="<td>$kill</td>";
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
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function public_key_userlist(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new template_admin();

    $add="Loadjs('$page?new-user-js=yes')";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='".ico_add_user."'></i> {add_user} </label>";
    $html[]="</div>";
	

	
	$html[]="<table id='table-sshd-limitaccess' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true style='width:1%'></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
	$html[]="<th data-sortable=false style='width:1%'>SSH Key</th>";
    $html[]="<th data-sortable=false style='width:1%'></th>";
    $html[]="<th data-sortable=false style='width:1%'></th>";
    $html[]="<th data-sortable=false style='width:1%'></th>";
	$html[]="<th data-sortable=false style='width:1%'></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$users=unserialize(base64_decode($sock->getFrameWork("cmd.php?unixLocalUsers=yes")));
	ksort($users);
    $badUsers=BadUsers();

	
	$unavailable=$tpl->_ENGINE_parse_body("{unavailable}");
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
	$TRCLASS=null;
	foreach ($users as $uid){
	    if(isset($badUsers[$uid])){continue;}
		$ligne=$q->mysqli_fetch_array("SELECT enabled,slength FROM sshd_privkeys WHERE `username`='$uid'");
		$slength=intval($ligne["slength"]);
		$class=null;
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$aclid=md5($uid);
        $down=null;
		$patternenc=urlencode($uid);
		$check=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?public-key-user-enable=$patternenc&md=$aclid')",null,"AsSystemAdministrator");
		$delete=$tpl->icon_delete("Loadjs('$page?limit-access-delete=$patternenc&md=$aclid')","AsSystemAdministrator");
		if($slength==0){
			$status="<span class='label'>$unavailable</span>";
			$check=$tpl->icon_nothing();
			$delete=$tpl->icon_nothing();
		}else{
			$status="<span class='label label-primary'>OK</span>";
            $down=$tpl->icon_download("document.location.href='/$page?public-key-user-download=$patternenc'","AsSystemAdministrator");
		}
        $chgpassword=$tpl->icon_password("Loadjs('$page?public-key-password-js=$patternenc')","AsSystemAdministrator");
		$html[]="<tr class='$TRCLASS' id='$aclid'>";
		$html[]="<td class=\"center\"><i class='{$class}fa fa-duotone fa-user'></i></td>";
		$html[]="<td>". $tpl->td_href("{$uid}","{click_to_edit}","Loadjs('$page?public-key-user-js=$patternenc')")."</td>";
		$html[]="<td>$status</td>";
        $html[]="<td>$chgpassword</td>";
        $html[]="<td>$down</td>";
		$html[]="<td>$check</td>";
		$html[]="<td>$delete</td>";
		$html[]="</tr>";
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='7'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-sshd-limitaccess').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}
function public_key_user_enable():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
	$uid=$_GET["public-key-user-enable"];
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM sshd_privkeys WHERE `username`='$uid'");
	if($ligne["enabled"]==0){$enabled=1;}else{$enabled=0;}
	$q->QUERY_SQL("UPDATE sshd_privkeys SET `enabled`='$enabled' WHERE `username`='$uid'");
    admin_tracks("turn $uid SSH Public Key as $enabled");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return true;
}
function public_key_group_enable():bool{
    $sshd=new openssh();
    $curgrou=$_GET["public-key-group-enable"];
    $grps=$sshd->AllowGroups;
    foreach ($grps as $grp){
        $aiv[$grp]=$grp;
    }
    if(isset($aiv[$curgrou])){
        admin_tracks("Disable $curgrou from the Allowed SSH group");
        unset($aiv[$curgrou]);
    }else{
        admin_tracks("Enable $curgrou from the Allowed SSH group");
        $aiv[$curgrou]=$curgrou;
    }
    $sshd->AllowGroups=array();

    foreach ($aiv as $group=>$none){
        $group=trim($group);
        if($group==null){continue;}
        $sshd->AllowGroups[]=$group;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHAllowGroups",serialize($sshd->AllowGroups));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHAllowGroupsForGo",implode(",",$sshd->AllowGroups));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    echo "LoadAjax('openssh-status-config','fw.sshd.php?openssh-status-config=yes');";


    return true;
}
function public_key_user_download():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $uid=$_GET["public-key-user-download"];
    $ligne=$q->mysqli_fetch_array("SELECT publickey FROM sshd_privkeys WHERE `username`='$uid'");
    $privatekey=$ligne["publickey"];
    $privatekey=str_replace('\\n',"\n",$privatekey);
    $fsize=strlen($privatekey);
    admin_tracks("Download the SSH Key for $uid");
    $fname="$uid.pem";
    header('Content-type: application/x-pem-file');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$fname\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    echo $privatekey;
    return admin_tracks("Downloaded OpenSSH Public Key for $uid");
}
function public_key_user_save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
	$uid=$q->sqlite_escape_string2($_POST["uid"]);
	$passphrase=$q->sqlite_escape_string2($_POST["passphrase"]);
	$q->QUERY_SQL("DELETE FROM sshd_privkeys WHERE `username`='$uid'");
	$q->QUERY_SQL("INSERT INTO sshd_privkeys (username,enabled,passphrase,slength)  VALUES ('$uid','{$_POST["enabled"]}','$passphrase',1)");
	
	if(!$q->ok){echo $q->mysql_error_html(true);return false;}
	@unlink(PROGRESS_DIR."/sshd.config");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks("Created SSHD Private Keys for $uid");

}
function public_key_user_popup(){
	$uid=$_GET["public-key-user-popup"];
	$page=CurrentPageName();
	$uidencode=urlencode($uid);
	
	echo "<div id='public-key-user-div'></div>
	<script>
		LoadAjax('public-key-user-div','$page?public-key-user-form=$uidencode');
	</script>
	";
}
function AuthorizedKeys_popup():bool{
	$page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div id='ssh-authorized-keys-buttons' style='margin:10px'></div>";
    echo $tpl->search_block($page,"","","","&AuthorizedKeys-table=yes");
    return true;
}
function public_key_user_form(){
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
	$page=CurrentPageName();
	$tpl=new template_admin();
	$btname=null;
	
	$uid=$_GET["public-key-user-form"];
	$uidencode=urlencode($uid);
	$ligne=$q->mysqli_fetch_array("SELECT * FROM sshd_privkeys WHERE `username`='$uid'");
	if(intval($ligne["slength"])==0){
		$ligne["enabled"]=1;
		$btname="{build_now}";
	}
	$form[]=$tpl->field_info("uid", "{username}", "$uid");
	$form[]=$tpl->field_checkbox("enabled", "{enabled}", $ligne["enabled"]);
	$slength=$ligne["slength"];
	if($slength>0){
		$form[]=$tpl->field_textarea("privatekey","ssh-rsa",$ligne["privatekey"],"100%","80px");
		$form[]=$tpl->field_button("{certificate}", "{download}","document.location.href='/$page?public-key-user-download=$uidencode'");
		$btname="{apply}";
	}
	$form[]=$tpl->field_password2("passphrase", "{use_a_passphrase}", $ligne["passphrase"]);
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/sshd.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/sshd.log";
	$ARRAY["CMD"]="sshd.php?gen-keys=$uidencode";
	$ARRAY["TITLE"]="{build_now}";
	$ARRAY["AFTER"]="LoadAjax('public-key-user-div','$page?public-key-user-form=$uidencode');LoadAjax('public-keys-userlist','$page?public-keys-userlist=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsAfter="Loadjs('fw.progress.php?content=$prgress&mainid=public-keys-generates')";

	$html="<div id='public-keys-generates'></div><p>&nbsp;</p>".	
	$tpl->form_outside("{certificate} {username} $uid", @implode("\n", $form),null,$btname,$jsAfter,"AsSystemAdministrator");
	echo $html;
}
function HostKeyAlgorithms_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{HostKeyAlgorithms}", "$page?HostKeyAlgorithms-popup=yes");
}
function PubkeyAcceptedAlgorithms_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{PubkeyAcceptedAlgorithms}", "$page?PubkeyAcceptedAlgorithms-popup=yes");
}
function banner_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	return $tpl->js_dialog1("{banner}", "$page?banner-popup=yes");
}
function banner_popup(){
	$tpl=new template_admin();
	$SSHDBanner=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDBanner");
	if(strlen($SSHDBanner)<5){
		$SSHDBanner="|--------------------------------------------------------------------------------------|\n| This system is for the use of authorized users only.                                 |\n| Individuals using this computer system without authority, or in                      |\n| excess of their authority, are subject to having all of their                        |\n| activities on this system monitored and recorded by system personnel.                |\n|                                                                                      |\n|                                                                                      |\n| In the course of monitoring individuals improperly using this                        |\n| system, or in the course of system maintenance, the activities                       |\n| of authorized users may also be monitored.                                           |\n|                                                                                      |\n|                                                                                      |\n| Anyone using this system expressly consents to such monitoring                       |\n| and is advised that if such monitoring reveals possible                              |\n| evidence of criminal activity, system personnel may provide the                      |\n| evidence of such monitoring to law enforcement officials.                            |\n|--------------------------------------------------------------------------------------|";
	}
	$form=$tpl->field_textareacode("Banner", "", $SSHDBanner);
	$jsafter="dialogInstance.close();";
	$html=$tpl->form_outside("", $form,null,"{apply}",$jsafter,"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
	
}
function banner_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile($_POST["Banner"],"SSHDBanner");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks("Modified the OpenSSH banner content");
}

function limit_countries_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $PHP_GEOIP_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHP_GEOIP_INSTALLED"));
    $EnableGeoipUpdate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));

    if($PHP_GEOIP_INSTALLED==0) {
        if (!extension_loaded("maxminddb")) {
            $tpl->js_error("{APP_PHP_GEOIP2_MISSING}");
            return;
        }
    }
    if($EnableGeoipUpdate==0){$tpl->js_error("{GeoIPUpdate_not_installed}");return;}

    $tpl->js_dialog1("{limit_countries}", "$page?limit-countries-popup=yes");

}
function limit_countries_popup(){
    $page=CurrentPageName();
    echo "<div id='limit-countries-table'></div><script>LoadAjax('limit-countries-table','$page?limit-countries-table=yes');</script>";
}
function limit_countries_deny_all():bool{
    $page=CurrentPageName();
    include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
    $GEOIPCOUNTRIES=GEO_IP_COUNTRIES_LIST();
    $SSHDDenyCountries=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDDenyCountries")));

    foreach ($GEOIPCOUNTRIES as $CountryCode=>$Country){

        $SSHDDenyCountries[$CountryCode]=true;
    }
    $scount=count($SSHDDenyCountries);
    $SSHDDenyCountries_enc=base64_encode(serialize($SSHDDenyCountries));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDDenyCountries",$SSHDDenyCountries_enc);
    header("content-type: application/x-javascript");
    echo "LoadAjax('limit-countries-table','$page?limit-countries-table=yes');\n";
    echo "document.getElementById('dashboard-ssh-countries').innerHTML='$scount';";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks("Denied All OpenSSH countries");
}
function limit_countries_single():bool{

    $CU=$_GET["limit-access-country"];
    $SSHDDenyCountries=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDDenyCountries")));

    if(isset($SSHDDenyCountries[$CU])){unset($SSHDDenyCountries[$CU]);}else{
        $SSHDDenyCountries[$CU]=true;
    }

    $scount=count($SSHDDenyCountries);
    $SSHDDenyCountries_enc=base64_encode(serialize($SSHDDenyCountries));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDDenyCountries",$SSHDDenyCountries_enc);
    header("content-type: application/x-javascript");
    echo "document.getElementById('dashboard-ssh-countries').innerHTML='$scount';";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/geoconfig");
    return admin_tracks("Added denied country $CU to the OpenSSH blacklist");
}
function limit_countries_allow_all():bool{
    $page=CurrentPageName();
    $SSHDDenyCountries_enc=base64_encode(serialize(array()));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDDenyCountries",$SSHDDenyCountries_enc);
    header("content-type: application/x-javascript");
    echo "LoadAjax('limit-countries-table','$page?limit-countries-table=yes');\n";
    echo "document.getElementById('dashboard-ssh-countries').innerHTML='0';";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/geoconfig");
    return admin_tracks("Allowed all countries from the OpenSSH service");
}
function limit_countries_table(){
    include_once(dirname(__FILE__)."/ressources/class.geoip-db.inc");
    $page               =   CurrentPageName();
    $GEOIPCOUNTRIES     = GEO_IP_COUNTRIES_LIST();
    $deny_all           = "Loadjs('$page?limit-countries-deny-all=yes');";
    $allow_all          = "Loadjs('$page?limit-countries-allow-all=yes');";
    $t                  = time();
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $SSHDDenyCountries  = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDDenyCountries")));

    $html[]="<div class='ibox-content'>
	<div class=\"btn-group\" data-toggle=\"buttons\">
    	<label class=\"btn btn btn-danger\" OnClick=\"$deny_all\"><i class='far fa-check-double'></i> {deny_all} </label>
        <label class=\"btn btn btn-primary\" OnClick=\"$allow_all\"><i class='far fa-check-double'></i> {allow_all} </label>
     </div>";

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true style='width:1%'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{countries}</th>";
    $html[]="<th data-sortable=false style='width:1%'>{deny}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    $TRCLASS=null;
    foreach ($GEOIPCOUNTRIES as $CountryCode=>$Country){
        $class=null;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $aclid=md5($CountryCode);
        $Enabled=0;
        if(isset($SSHDDenyCountries[$CountryCode])){
            $Enabled=1;
        }
        $html[]="<tr class='$TRCLASS' id='$aclid'>";
        $html[]="<td class=\"center\"><i class=\"far fa-globe-europe\"></i></td>";
        $html[]="<td><strong>{$Country}</strong></td>";
        $html[]="<td>". $tpl->icon_check($Enabled,"Loadjs('$page?limit-access-country=$CountryCode&md=$aclid')")."</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function limit_access_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{limit_access}", "$page?limit-access-popup=yes");
}
function sessions_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{sessions}", "$page?sessions-popup=yes",600);
}
function limit_access_new_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog2("{new_member}", "$page?limit-access-new-popup=yes");	
	}
function limit_access_delete():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	header("content-type: application/x-javascript");
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
	$q->QUERY_SQL("DELETE FROM sshd_allowusers WHERE pattern='{$_GET["limit-access-delete"]}'");
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return false;}
	echo "$('#{$_GET["md"]}').remove();\n";
	echo "LoadAjax('table-loader-sshd-service','$page?tabs=yes');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks("Delete access rule from the OpenSSH server");
}
function limit_access_popup(){
	$page=CurrentPageName();
	
	echo "<div id='limit-access-tablediv'></div>
	<script>
			LoadAjax('limit-access-tablediv','$page?limit-access-table=yes');
	</script>		
	";
}
function limit_access_new_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	
	$jsEnd="dialogInstance2.close();LoadAjax('limit-access-tablediv','$page?limit-access-table=yes');LoadAjax('table-loader-sshd-service','$page?tabs=yes');";
	
	$form[]=$tpl->field_text("username", "{user2}", null);
	$form[]=$tpl->field_text("domain", "{domain}", null);
	echo $tpl->form_outside("{limit_access}", @implode("\n", $form),
			"{sshd_AllowUsers_explain}","{add}",$jsEnd,"AsSystemAdministrator");
}
function limit_access_new_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $username   = $_POST["username"];
    $domain     = $_POST["domain"];
    if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#",$username)){
        $tpl->js_error("User name cannot be an ip address, use domain for that and set username with only *");
        return false;
    }


	if($username==null){$username="*";}
	if($domain==null){$domain="*";}
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
	$pattern=$q->sqlite_escape_string2("$username@$domain");

	$q->QUERY_SQL("INSERT OR IGNORE INTO sshd_allowusers (pattern) VALUES ('$pattern')");
	if(!$q->ok){echo $q->mysql_error;return false;}
	@unlink(PROGRESS_DIR."/sshd.config");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks_post("Created access rule from the OpenSSH server");
}
function limit_access_table(){
	$tpl=new template_admin();
	$page=CurrentPageName();

    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
	$add="Loadjs('$page?limit-access-new-js=yes');";
	
	$html[]="<div class='ibox-content'>
	<div class=\"btn-group\" data-toggle=\"buttons\">
    	<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_member} </label>
       
     </div>";
	
	$html[]="<table id='table-sshd-limitaccess' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true style='width:1%'></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{members}</th>";
	$html[]="<th data-sortable=false style='width:1%'></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	
	$results=$q->QUERY_SQL("SELECT * FROM sshd_allowusers ORDER BY pattern");
    $TRCLASS=null;
	
	
	foreach ($results as $index=>$ligne){
	
		$class=null;
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$aclid=md5($ligne["pattern"]);
		$patternenc=urlencode($ligne["pattern"]);
		$html[]="<tr class='$TRCLASS' id='$aclid'>";
		$html[]="<td class=\"center\"><i class='{$class}fa fa-desktop'></i></td>";
		$html[]="<td>{$ligne["pattern"]}</td>";
		$html[]="<td>". $tpl->icon_delete("Loadjs('$page?limit-access-delete=$patternenc&md=$aclid')")."</td>";
		$html[]="</tr>";
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-sshd-limitaccess').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}
function main_setting_js():bool{
    //settings-js=yes&section=general
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $section=$_GET["section"];
    $tpl->js_dialog("SSH {parameters}","$page?settings-popup=$section");
    return true;
}
function reputation_js():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $tpl->js_dialog("SSH {use_reput_service}","$page?reputation-popup=yes");
    return true;
}
function private_host_key_js():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $tpl->js_dialog("{private_host_key}","$page?private-host-key-tab=yes");
    return true;
}
function private_host_key_tab():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $array["{path}"]="$page?private-host-key-path-start=yes";
    $array["{content}"]="$page?private-host-key-popup=yes";
    echo $tpl->tabs_default($array);
    return true;
}
function private_host_key_start():bool{
    $page=CurrentPageName();
    echo "<div id='private-host-key-path' style='margin-top:10px'></div>";
    echo "<script>LoadAjaxSilent('private-host-key-path','$page?private-host-key-path=yes');</script>";
    return true;
}
function private_host_key_path():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $Paths["ssh_host_rsa_key"]="/etc/ssh/ssh_host_rsa_key";
    $Paths["ssh_host_ecdsa_key"]="/etc/ssh/ssh_host_ecdsa_key";
    $Paths["ssh_host_ed25519_key"]="/etc/ssh/ssh_host_ed25519_key";
    $SSHDHotstKey=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDHostKey");
    VERBOSE("SSHDHostKey=$SSHDHotstKey",__LINE__);
    if(strlen($SSHDHotstKey)<3){
        $SSHDHotstKey="ssh_host_rsa_key";
    }
    if(!isset($Paths[$SSHDHotstKey])){
        $SSHDHotstKey="ssh_host_rsa_key";
    }


    foreach ($Paths as $key=>$value) {
        if ($key == $SSHDHotstKey) {
            $tpl->table_form_field_js("");
            $tpl->table_form_field_text($key, "<span style='text-transform:none'>$value</span>",ico_certificate);
            continue;
        }
        $tpl->table_form_field_js("Loadjs('$page?private-host-key-path-save=$key');","AsSystemAdministrator");
        $tpl->table_form_field_bool("$key",0,ico_disabled);
    }
    echo $tpl->table_form_compile();
    return true;
}
function private_host_key_save():bool{
    $page=CurrentPageName();
    $Paths["ssh_host_rsa_key"]="/etc/ssh/ssh_host_rsa_key";
    $Paths["ssh_host_ecdsa_key"]="/etc/ssh/ssh_host_ecdsa_key";
    $Paths["ssh_host_ed25519_key"]="/etc/ssh/ssh_host_ed25519_key";

    $key=$_GET["private-host-key-path-save"];
    $newPath=$Paths["ssh_host_rsa_key"];

    VERBOSE("SSHDHostKey:$key==$newPath",__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDHostKey",$key);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('private-host-key-path','$page?private-host-key-path=yes');\n";
    echo "LoadAjaxSilent('openssh-status-config','$page?openssh-status-config=yes');\n";
    return admin_tracks("Set Hosts Key to $key");
}
function private_host_key_popup():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/private/host/key"));
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error(json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    $data=$json->PrivateHostKey;
    $form[]=$tpl->field_textareacode("PrivateHostKey", "", $data);
    echo $tpl->form_outside("",$form,"","","" ,"AsSystemAdministrator",false,true);;
    return true;
}
function disable_sshd_js():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $tpl->js_dialog("SSH {parameters}","$page?disable-popup=yes");
    return true;
}
function reputation_popup():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $results=$q->QUERY_SQL("SELECT * FROM rbl_reputations WHERE enabled=1");
    $HASH=array();
    if(count($results)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{no_reput_rules}"));
        return true;
    }

    foreach($results as $index=>$ligne) {
        $ID = $ligne["ID"];
        $rulename = $ligne["rulename"];
        $HASH[$ID]=$rulename;
    }
    $SSHDRBL= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDRBL"));
    $SSHDRBLWHITE= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDRBLWHITE"));
    $form[]=$tpl->field_array_hash($HASH,"SSHDRBL","{deny_access}",$SSHDRBL);
    $form[]=$tpl->field_array_hash($HASH,"SSHDRBLWHITE","{allow_access}",$SSHDRBLWHITE);
    echo $tpl->form_outside("",$form,"","{apply}",
        "BootstrapDialog1.close();LoadAjax('openssh-status-config','$page?openssh-status-config=yes');","AsSystemAdministrator");
    return true;
}
function reputation_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDRBL",$_POST["SSHDRBL"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDRBLWHITE",$_POST["SSHDRBLWHITE"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/geoconfig");
    return admin_tracks_post("Save OpenSSH SSH reputation rule");
}

function disable_sshd_popup():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $DisableSSHConfig       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSSHConfig"));


    $form[]=$tpl->field_checkbox("DisableSSHConfig","{DenyHaproxyConf}",$DisableSSHConfig,false);

    $jsrestart=js_tiny();
    echo $tpl->form_outside(null, @implode("\n", $form),"{DisableSSHConfig}","{apply}",
        "LoadAjax('openssh-status-config','$page?openssh-status-config=yes');$jsrestart",
        "AsSystemAdministrator");
    return true;
}
function disable_sshd_save():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableSSHConfig",$_POST["DisableSSHConfig"]);

    if(intval($_POST["DisableSSHConfig"])==0) {
        return admin_tracks("Disable Artica OpenSSH configuration capability !");
    }
    return admin_tracks("Enable Artica OpenSSH configuration capability");
}
function main_setting_popup():bool{
    $section=$_GET["settings-popup"];

    if($section=="interface"){
        return main_interface();
    }


    if($section=="general"){
        return main_general();
    }
    if($section=="auth"){
        return main_auth();
    }
    if($section=="limits"){
        return main_limits();
    }
    if($section=="protocols"){
        return main_protocol();
    }
    if($section=="firewall"){
        return main_firewall();
    }

    return true;
}
function main_interface():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $SSHDInterface          = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDInterface");
    $SSHDListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDListenPort"));
    if($SSHDListenPort==0){$SSHDListenPort=22;}
    $form[]=$tpl->field_interfaces_choose("SSHDInterface", "{listen_interface}", $SSHDInterface);
    $form[]=$tpl->field_numeric("SSHDListenPort","{listen_port}",$SSHDListenPort);
    $jsrestart=main_reload();
    echo $tpl->form_outside(null, @implode("\n", $form),null,"{apply}",
        "BootstrapDialog1.close();LoadAjax('openssh-status-config','$page?openssh-status-config=yes');$jsrestart",
        "AsSystemAdministrator");
    return true;
}
function SSHDInterface_Save():bool{
    $tpl                    = new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDInterface",$_POST["SSHDInterface"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDListenPort",$_POST["SSHDListenPort"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure-restart");
    return admin_tracks("Save OpenSSH interface settings");
}
function main_general():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $sshd                   = new openssh();
    $SSHDNotifyConnected    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDNotifyConnected"));
    $FAIL2BAN               = false;
    $FAIL2BAN_INSTALLED     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FAIL2BAN_INSTALLED"));
    $EnableFail2Ban         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFail2Ban"));


    if($FAIL2BAN_INSTALLED==1){
        if($EnableFail2Ban==1){$FAIL2BAN=true;}
    }

    $Loglevels=array(
        "QUIET"=>"QUIET", "FATAL"=>"FATAL", "ERROR"=>"ERROR", "INFO"=>"INFO", "VERBOSE"=>"VERBOSE",
        "DEBUG1"=>"DEBUG1", "DEBUG2"=>"DEBUG2","DEBUG3"=>"DEBUG3"
    );




    $form[]=$tpl->field_checkbox("Banner","{UseBanner}",$sshd->main_array["Banner"],false);
    $form[]=$tpl->field_text("MaxStartups","{MaxStartups}",$sshd->main_array["MaxStartups"]);
    $form[]=$tpl->field_section("{events}");
    if(!$FAIL2BAN){
        $form[]=$tpl->field_array_hash($Loglevels, "LogLevel", "{log_level}", $sshd->main_array["LogLevel"]);
    }
    $form[]=$tpl->field_checkbox("SSHDNotifyConnected","{write_event_on_success}",$SSHDNotifyConnected,false,"{SSHDNotifyConnected}");

    $jsrestart=main_reload();
    echo $tpl->form_outside(null, @implode("\n", $form),null,"{apply}",
        "BootstrapDialog1.close();LoadAjax('openssh-status-config','$page?openssh-status-config=yes');$jsrestart",
        "AsSystemAdministrator");
    return true;
}
function main_auth():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();

    $SSHGoogle2FA           = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHGoogle2FA"));
    $SSHDUsePAM             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDUsePAM"));
    $PasswordAuthenticationUsers = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PasswordAuthenticationUsers"));
    $sshd                   = new openssh();

    if($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PAM_GOOGLE_AUTHENTICATOR_INSTALLED")==0){$SSHGoogle2FA=0;}


    $SSHGoogle2FAEnabled=true;
    if($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PAM_GOOGLE_AUTHENTICATOR_INSTALLED")==1){
        $SSHGoogle2FAEnabled=false;
    }

    $form[]=$tpl->field_checkbox("SSHGoogle2FA","{PAM_GOOGLE_AUTHENTICATOR}",$SSHGoogle2FA,false,null,$SSHGoogle2FAEnabled);

    $form[]=$tpl->field_checkbox("HostbasedAuthentication","{HostbasedAuthentication}",$sshd->main_array["HostbasedAuthentication"],false,"");
    $form[]=$tpl->field_checkbox("PermitRootLogin","{PermitRootLogin}",$sshd->main_array["PermitRootLogin"],false,"{PermitRootLogin_text}");




    $form[]=$tpl->field_checkbox("SSHDUsePAM","{UsePAM}",$SSHDUsePAM,false,"{UsePAM_TEXT}");
    $form[]=$tpl->field_checkbox("ChallengeResponseAuthentication","{ChallengeResponseAuthentication}",$sshd->main_array["ChallengeResponseAuthentication"],false,"{ChallengeResponseAuthentication_text}");

    $form[]=$tpl->field_checkbox("PubkeyAuthentication","{PubkeyAuthentication}",$sshd->main_array["PubkeyAuthentication"],false,"{PubkeyAuthentication_text}");
    $form[]=$tpl->field_section("{PasswordAuthentication}");
    $form[]=$tpl->field_checkbox("PasswordAuthentication","{enable}",$sshd->main_array["PasswordAuthentication"],false,"{PasswordAuthentication_text}");
    $form[]=$tpl->field_text("PasswordAuthenticationUsers","{exceptipaddresses} ({ifdisabled})",$PasswordAuthenticationUsers,false,"{PasswordAuthentication_text}");

    $jsrestart="Loadjs('$page?reload-compile=yes');";
    echo $tpl->form_outside(null, @implode("\n", $form),null,"{apply}",
        "BootstrapDialog1.close();LoadAjax('openssh-status-config','$page?openssh-status-config=yes');$jsrestart","AsSystemAdministrator");
    return true;
}
function main_limits():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $sshd                   = new openssh();

    $form[]=$tpl->field_checkbox("X11Forwarding","X11 {wccp2_forwarding_method}",$sshd->main_array["X11Forwarding"]);
    $form[]=$tpl->field_checkbox("PermitTTY","{allow} Terminal",$sshd->main_array["PermitTTY"]);


    $form[]=$tpl->field_checkbox("IgnoreRhosts","IgnoreRhosts",$sshd->main_array["IgnoreRhosts"]);


    $form[]=$tpl->field_numeric("ClientAliveInterval","{ClientAliveInterval} ({seconds})",$sshd->main_array["ClientAliveInterval"]);
    $form[]=$tpl->field_numeric("ClientAliveCountMax","{ClientAliveCountMax} ({attempts})",$sshd->main_array["ClientAliveCountMax"]);



    $form[]=$tpl->field_numeric("LoginGraceTime","{LoginGraceTime} ({seconds})",$sshd->main_array["LoginGraceTime"],"{LoginGraceTime_text}");
    $form[]=$tpl->field_numeric("MaxSessions","{MaxSessions}",$sshd->main_array["MaxSessions"],"{MaxSessions_text}");
    $form[]=$tpl->field_numeric("MaxAuthTries","{MaxAuthTries}",$sshd->main_array["MaxAuthTries"],"{MaxAuthTries_text}");
    $form[]=$tpl->field_checkbox("StrictModes","{StrictModes}",$sshd->main_array["StrictModes"],false,"{StrictModes_text}");

    $jsrestart=main_reload();
    echo $tpl->form_outside(null, @implode("\n", $form),null,"{apply}",
        "BootstrapDialog1.close();LoadAjax('openssh-status-config','$page?openssh-status-config=yes');
        $jsrestart","AsSystemAdministrator");
    return true;
}
function main_firewall():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $SSHDIptables           = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDIptables"));

    $form[]=$tpl->field_checkbox("SSHDIptables","{firewall_protection}",$SSHDIptables);
    echo $tpl->form_outside(null,$form,"{sshd_firewall_explain}","{apply}",
        "BootstrapDialog1.close();LoadAjax('openssh-status-config','$page?openssh-status-config=yes');",
        "AsSystemAdministrator");
    return true;
}
function main_protocol():bool{

    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $sshd                   = new openssh();

    $SSHDProtocolz["2"]="SSH2";
    $SSHDProtocolz["1,2"]="SSH1, SSH2";
    $SSHDProtocol           = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDProtocol"));
    $SSHDCiphers            = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDCiphers"));
    $SSHDCiphersEnable      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDCiphersEnable"));

    if($SSHDCiphers==null){$SSHDCiphers="aes256-ctr,aes128-cbc,3des-cbc,aes192-cbc,aes256-cbc";}
    if($SSHDProtocol==null){$SSHDProtocol=2;}


    $form[]=$tpl->field_section("{protocols}");
    $form[]=$tpl->field_array_hash($SSHDProtocolz, "SSHDProtocol", "{protocol}", $SSHDProtocol);
    $form[]=$tpl->field_checkbox("PermitTunnel","{PermitTunnel}",$sshd->main_array["PermitTunnel"],false,"{PermitTunnel_text}");
    $form[]=$tpl->field_checkbox("AllowTcpForwarding","{AllowTcpForwarding}",$sshd->main_array["AllowTcpForwarding"]);
    $form[]=$tpl->field_text("KexAlgorithms","{KexAlgorithms}",$sshd->main_array["KexAlgorithms"]);
    $form[]=$tpl->field_checkbox("UseDNS","{UseDNS}",$sshd->main_array["UseDNS"],false,"{UseDNS_sshd_text}");
    $form[]=$tpl->field_section("{certificate}");
    $form[]=$tpl->field_checkbox("SSHDCiphersEnable","{ssl_ciphers} {enable}",$SSHDCiphersEnable,"SSHDCiphers");
    $form[]=$tpl->field_text("SSHDCiphers","{ssl_ciphers}",$SSHDCiphers);

    $jsrestart=main_reload();
    echo $tpl->form_outside(null, @implode("\n", $form),null,"{apply}",
        "BootstrapDialog1.close();LoadAjax('openssh-status-config','$page?openssh-status-config=yes');$jsrestart;",
        "AsSystemAdministrator");
    return true;
}
function main_reload():string{
    $page                   = CurrentPageName();
    return "Loadjs('$page?reload-compile=yes');\n".js_tiny();
}
function main_restart():string{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $tpl->CLUSTER_CLI=True;

    return $tpl->framework_buildjs("/ssh/restart",
        "sshd.progress","sshd.log","progress-sshd-restart",
        "LoadAjax('table-loader-sshd-service','$page?tabs=yes');");
}
function reconfigure_js():bool{
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    $tpl=new template_admin();
    $tpl->js_ok("{reconfigure}");
    return admin_tracks("Execute OpenSSH service reconfiguration");
}
function status():bool{
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork('cmd.php?openssh-ini-status=yes');
	$tpl                    = new template_admin();
	$page                   = CurrentPageName();
    $FAIL2BAN               = false;
	$ini                    = new Bs_IniHandler(PROGRESS_DIR."/sshd.status");
    $SSHGoogle2FA           = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHGoogle2FA"));
	$EnableFail2Ban         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFail2Ban"));
	$FAIL2BAN_INSTALLED     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FAIL2BAN_INSTALLED"));
    $SSHDCiphers            = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDCiphers"));
    $SSHDProtocol           = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDProtocol"));

    if($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PAM_GOOGLE_AUTHENTICATOR_INSTALLED")==0){$SSHGoogle2FA=0;}
    if($SSHDCiphers==null){$SSHDCiphers="aes256-ctr,aes128-cbc,3des-cbc,aes192-cbc,aes256-cbc";}
    if($SSHDProtocol==null){$SSHDProtocol=2;}

	$sshd=new openssh();

	if($FAIL2BAN_INSTALLED==1){
		if($EnableFail2Ban==1){$FAIL2BAN=true;}
	}

   // $jsrestart_shell=$tpl->framework_buildjs("/sshweb/restart","shellinabox.restart","shellinabox.log","progress-sshd-restart",        "LoadAjax('table-loader-sshd-service','$page?tabs=yes');");

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ssh2fa.restart";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/ssh2fa.log";
    $ARRAY["CMD"]="sshd.php?ssh2fa=yes";
    $ARRAY["TITLE"]="{generate_a_new_code}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-sshd-service','$page?tabs=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $fa2new_js="Loadjs('fw.progress.php?content=$prgress&mainid=progress-sshd-restart')";
    $sock=new sockets();



	
	$html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:350px;vertical-align: top'>";
    $html[]="<div class='center'>";
    $html[]="<div id='OpenSSHStatus'></div>";
    /*
    $html[]="<div style='margin-top:10px'>";
    /$data=$sock->REST_API("/sshweb/status");
    $json=json_decode($data);
    $ini                    = new Bs_IniHandler();
    $ini->loadString($json->Info);
    $html[]=$tpl->SERVICE_STATUS($ini, "APP_SHELLINABOX",$jsrestart_shell);
    $html[]="</div>";
*/
    if($SSHGoogle2FA==1){
        $uri=null;
        if(is_file(ARTICA_ROOT."/img/pam_google_authenticator_qrcode.png")){
            $uri="/img/pam_google_authenticator_qrcode.png";
        }
        if($uri==null) {
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("sshd.php?pam_google_authenticator=yes");
            $dstfile = PROGRESS_DIR . "/pam_google_authenticator.auth";
            $fdate = explode("\n", @file_get_contents($dstfile));
            @unlink($dstfile);
            $uri = null;
            foreach ($fdate as $line) {
                if (preg_match("#\.google\.com\/(.+?)$#", $line, $re)) {
                    $uri = "https://www.google.com/$re[1]";
                    break;
                }
            }

        }

        if($uri<>null){
            $button2FA=$tpl->button_autnonome("{generate_a_new_code}", $fa2new_js, "fas fa-sync-alt");
            $button2FA="<div style='margin-top:30px'>$button2FA</div>";
            $html[]="<div style='margin-top:10px'>";
            $html[]="<div style='vertical-align:top;width:335px'>
			<div class='widget navy-bg p-lg text-center' style='min-height:240px;margin-top:2px'>
			<H3 class='font-bold no-margins' style='padding-bottom:10px;padding-top:10px'>Google Authenticator</H2>
			<img src='$uri' alt=''>
			$button2FA
			</div>
			</div>";
            $html[]="";
            $html[]="</div>";
        }
    }

    $html[]="</div>";
    $html[]="</td>";
    $html[]="<td valign='top'><div id='openssh-status-config'></div>";
	

    $hostkey=null;
		
	
	if(is_array($sshd->HostKey)){
	    foreach ($sshd->HostKey as $num=>$line){
			$hostkey=$hostkey."<div><code>$line</code>&nbsp;</div>";
		}
	}
    $Interval=$tpl->RefreshInterval_js("OpenSSHStatus",$page,"OpenSSHStatus=yes",3);
	$html[]="</td></tr></table>";
    $html[]="<script>";
    $html[]=js_tiny();
    $html[]="LoadAjax('openssh-status-config','$page?openssh-status-config=yes');";
    $html[]=$Interval;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function js_tiny(){
    $page=CurrentPageName();
    return "Loadjs('$page?js-tiny=yes');";
}
function OPenSSHStatus(){
    $sock=new sockets();
    $tpl=new template_admin();
    $jsrestart=main_restart();
    $data=$sock->REST_API("/ssh/astatus");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        $APP_OPENSSH=$tpl->widget_rouge("API ERROR",json_last_error_msg());

    }else {
        if (!$json->Status) {
            $APP_OPENSSH = $tpl->widget_rouge("API ERROR", $json->Error);
        } else {
            $ssh_status = new Bs_IniHandler();
            $ssh_status->loadString($json->Info);
            $APP_OPENSSH = $tpl->SERVICE_STATUS($ssh_status, "APP_OPENSSH", $jsrestart);
        }
    }
    $html[]=$tpl->_ENGINE_parse_body($APP_OPENSSH);
    $html[]=$tpl->_ENGINE_parse_body(widget_connected());
    echo @implode("\n",$html);
}
function lock_conf(){
    $lock=intval($_GET["lock-file-js"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/conf/lock/$lock");
    js_tiny_build();
    $page=CurrentPageName();
    echo "LoadAjax('openssh-status-config','$page?openssh-status-config=yes');";
}

function js_tiny_build(){
    $tpl=new template_admin();
    $DisableSSHConfig       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSSHConfig"));
    $page=CurrentPageName();
    $topbuttons=array();

    if($DisableSSHConfig==0) {
        $topbuttons[] = array("Loadjs('$page?public-key-js=yes')", ico_certificate, "{certificates}");
        $topbuttons[] = array("Loadjs('$page?limit-countries-js=yes')", "far fa-globe-europe", "{deny_countries}");
        $topbuttons[] = array("Loadjs('$page?AuthorizedKeys-js=yes')", "fas fa-key", "{AuthorizedKeys}");
        $topbuttons[] = array("Loadjs('$page?config-file-js=yes')", "fa fa-file-code", "{config_file}");
        $topbuttons[] = array("Loadjs('$page?reconfigure=yes')", ico_refresh, "{reconfigure}");
    }
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/conf/islock"));
    if(property_exists($data,"Locked")){
        if (!$data->Locked) {
            $topbuttons[] = array("Loadjs('$page?lock-file-js=1')", ico_unlock, "{lock}");
        } else {
            $topbuttons[] = array("Loadjs('$page?lock-file-js=0')", ico_lock, "{unlock}");
        }
    }



    $OPENSSH_VER=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENSSH_VER");
    $TINY_ARRAY["TITLE"]="{APP_OPENSSH} v$OPENSSH_VER";
    $TINY_ARRAY["ICO"]=ico_terminal;
    $TINY_ARRAY["EXPL"]="{OPENSSH_EXPLAIN}";
    $TINY_ARRAY["URL"]="sshd";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    header("content-type: application/x-javascript");
    echo $jstiny;
}

function widget_connected():string{

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/connected"));
    if(!$json->Status){return "";}
    if(!is_array($json->Info->Users)) {return "";}
    $Count = count($json->Info->Users);

    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    if($Count==0){
        return $tpl->widget_grey("{sessions}",0);
    }

    $btn[0]["name"] = "{view2}: {sessions}";
    $btn[0]["icon"] = ico_users;
    $btn[0]["js"] = "Loadjs('$page?sessions-js=yes')";
    return $tpl->widget_jaune("{sessions}",$Count,$btn);
}

function SSHDInterfaceToText($Port):string{
    $SSHDInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDInterface");
    if($SSHDInterface==null){return "*:$Port";}
    $SSHDInterface=str_replace(" ",",",$SSHDInterface);
    $tb=explode(",",$SSHDInterface);
    $text=array();
    foreach ($tb as $iface){
        if($iface=="lo"){
            continue;
        }
        $text[]="$iface:$Port";
    }
    return @implode(", ",$text);
}
function status_config():bool{
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork('cmd.php?openssh-ini-status=yes');
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $FAIL2BAN               = false;
    $ini                    = new Bs_IniHandler(PROGRESS_DIR."/sshd.status");
    $SSHGoogle2FA           = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHGoogle2FA"));
    $EnableFail2Ban         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFail2Ban"));
    $FAIL2BAN_INSTALLED     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FAIL2BAN_INSTALLED"));
    $SSHDNotifyConnected    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDNotifyConnected"));

    $SSHDCiphers            = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDCiphers"));
    $SSHDProtocol           = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDProtocol"));
    $SSHDCiphersEnable      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDCiphersEnable"));
    $SSHDUsePAM             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDUsePAM"));
    $DisableSSHConfig       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSSHConfig"));
    $SSHDIptables           = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDIptables"));
    $SSHDRBL= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDRBL"));

    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/conf/islock"));
    if(property_exists($data,"Locked")) {
        if ($data->Locked) {
            echo $tpl->div_warning("{locked}||{configuration_islocked}");
        }
    }



    if($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PAM_GOOGLE_AUTHENTICATOR_INSTALLED")==0){$SSHGoogle2FA=0;}
    if($SSHDCiphers==null){$SSHDCiphers="aes256-ctr,aes128-cbc,3des-cbc,aes192-cbc,aes256-cbc";}
    if($SSHDProtocol==null){$SSHDProtocol=2;}

    $sshd=new openssh();

    if($FAIL2BAN_INSTALLED==1){
        if($EnableFail2Ban==1){$FAIL2BAN=true;}
    }
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ssh2fa.restart";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/ssh2fa.log";
    $ARRAY["CMD"]="sshd.php?ssh2fa=yes";
    $ARRAY["TITLE"]="{generate_a_new_code}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-sshd-service','$page?tabs=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $fa2new_js="Loadjs('fw.progress.php?content=$prgress&mainid=progress-sshd-restart')";

    $hostkey=null;


    if(is_array($sshd->HostKey)){
        foreach ($sshd->HostKey as $num=>$line){
            $hostkey=$hostkey."<div><code>$line</code>&nbsp;</div>";
        }
    }

    $Loglevels=array(
        "QUIET"=>"QUIET", "FATAL"=>"FATAL", "ERROR"=>"ERROR", "INFO"=>"INFO", "VERBOSE"=>"VERBOSE",
        "DEBUG1"=>"DEBUG1", "DEBUG2"=>"DEBUG2","DEBUG3"=>"DEBUG3"


    );

    $SSHDProtocolz["2"]="SSH2";
    $SSHDProtocolz["1,2"]="SSH1, SSH2";


    //Loadjs('$page?banner-js=yes');
    $SSHDDenyCountries=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDDenyCountries")));
    if(!$SSHDDenyCountries){$SSHDDenyCountries=array();}
    $SSHDBanner=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDBanner");
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $sshd_allowusers=$q->COUNT_ROWS("sshd_allowusers")." {items}";
    $sshd_allowips=$q->COUNT_ROWS("sshd_allowips")." {items}";
    $sshd_countries="<span id='dashboard-ssh-countries'>".count($SSHDDenyCountries)."</span> {items}";

    $rules=array();
    $tpl->table_form_section("{general_settings}");
    $tpl->table_form_field_js("Loadjs('$page?disable-js=yes')");
    $tpl->table_form_field_bool("{DenyHaproxyConf}",$DisableSSHConfig,ico_nic);
    if($DisableSSHConfig==1){
        echo $tpl->_ENGINE_parse_body( $tpl->table_form_compile());
        return true;
    }
    $tpl->table_form_field_js("Loadjs('$page?reputation-js=yes')");
    if($SSHDRBL==0){
        $tpl->table_form_field_bool("{use_reput_service}",0,ico_shield);
    }else{
        $qFW=new lib_sqlite("/home/artica/SQLITE/firewall.db");
        $ligneFw=$qFW->mysqli_fetch_array("SELECT rulename,enabled FROM rbl_reputations WHERE ID='$SSHDRBL'");
        if(!isset($ligneFw["enabled"])){$ligneFw["enabled"]=0;}
        if($ligneFw["enabled"]==1) { $rules[] = $ligneFw["rulename"]; }
        $SSHDRBLWHITE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDRBLWHITE"));

        if($SSHDRBLWHITE>0){
            $ligneFw=$qFW->mysqli_fetch_array("SELECT rulename,enabled FROM rbl_reputations WHERE ID='$SSHDRBLWHITE'");
            if(!isset($ligneFw["enabled"])){$ligneFw["enabled"]=0;}
            if($ligneFw["enabled"]==1) {$rules[] = $ligneFw["rulename"];}
        }
        if(count($rules)>0){
            $tpl->table_form_field_text("{use_reput_service}",
                @implode(" / ",$rules),ico_shield);
        }else {
           $tpl->table_form_field_bool("{use_reput_service}",0,ico_shield);
        }
    }
    $SSHDListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDListenPort"));
    if($SSHDListenPort==0){$SSHDListenPort=22;}
    $SSHDInterface=SSHDInterfaceToText($SSHDListenPort);
    if(!isset($sshd->main_array["Banner"])){$sshd->main_array["Banner"]=0;}
    $tpl->table_form_field_js("Loadjs('$page?settings-js=yes&section=firewall')");
    $tpl->table_form_field_bool("{firewall_protection}",$SSHDIptables,ico_shield);
    $tpl->table_form_field_js("Loadjs('$page?settings-js=yes&section=interface')");
    $tpl->table_form_field_text("{listen_interface}",$SSHDInterface,ico_nic);
    $tpl->table_form_field_js("Loadjs('$page?settings-js=yes&section=general')");
    $tpl->table_form_field_text("{MaxStartups}",$sshd->main_array["MaxStartups"],ico_configure);
    $tpl->table_form_field_bool("{UseBanner}",$sshd->main_array["Banner"],ico_proto);

    $tpl->table_form_field_js("Loadjs('$page?banner-js=yes')");
    $tpl->table_form_field_text("{banner}",strlen($SSHDBanner)." bytes",ico_configure);


    $tpl->table_form_section("{authentication}");
    $tpl->table_form_field_js("Loadjs('$page?settings-js=yes&section=auth')");
    $SSHGoogle2FAEnabled=true;
    if($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PAM_GOOGLE_AUTHENTICATOR_INSTALLED")==1){
        $SSHGoogle2FAEnabled=false;
    }

    $tpl->table_form_field_bool("{PAM_GOOGLE_AUTHENTICATOR}",$SSHGoogle2FA,ico_lock);
    $tpl->table_form_field_bool("{HostbasedAuthentication}",$sshd->main_array["HostbasedAuthentication"],ico_lock);

    $tpl->table_form_field_bool("{UsePAM}",$SSHDUsePAM,ico_lock);
    $tpl->table_form_field_bool("{ChallengeResponseAuthentication}",
        $sshd->main_array["ChallengeResponseAuthentication"],ico_lock);


    $PasswordAuthenticationUsers = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PasswordAuthenticationUsers"));
    if( $sshd->main_array["PasswordAuthentication"]==0){
        if (strlen($PasswordAuthenticationUsers)>0){
            $tpl->table_form_field_text("{PasswordAuthentication}","{inactive2}, {exceptipaddresses} $PasswordAuthenticationUsers");
        }else{
            $tpl->table_form_field_bool("{PasswordAuthentication}",
                $sshd->main_array["PasswordAuthentication"],ico_lock);
        }

    }else{
        $tpl->table_form_field_bool("{PasswordAuthentication}",
            $sshd->main_array["PasswordAuthentication"],ico_lock);
    }



    $tpl->table_form_field_bool("{PubkeyAuthentication}",
        $sshd->main_array["PubkeyAuthentication"],ico_certificate);


    $tpl->table_form_field_bool("{PermitRootLogin}",$sshd->main_array["PermitRootLogin"],ico_member);

    $tpl->table_form_field_js("Loadjs('$page?groups-js=yes');");
    $AllowGroups=trim(@implode(",",$sshd->AllowGroups));
    if($AllowGroups==null){$AllowGroups="{none}";}
    $tpl->table_form_field_text("{AllowOnlyGroups}", $AllowGroups,ico_group);
    $tpl->table_form_field_js("Loadjs('$page?limit-access-js=yes');");
    $tpl->table_form_field_text("{limit_access}", $sshd_allowusers, ico_firewall);
    $tpl->table_form_field_js("Loadjs('$page?limit-countries-js=yes');");
    $tpl->table_form_field_text("{deny_countries}", $sshd_countries, ico_country);
    $tpl->table_form_field_js("Loadjs('$page?AuthorizedKeys-js=yes')");
    $tpl->table_form_field_text("{AuthorizedKeysFile}", $sshd->main_array["AuthorizedKeysFile"],ico_folder);

    $HostKeyAlgorithms=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HostKeyAlgorithms");
    $tpl->table_form_field_js("Loadjs('$page?HostKeyAlgorithms-js=yes')");
    if(strlen($HostKeyAlgorithms)==0){
        $tpl->table_form_field_bool("{HostKeyAlgorithms} ({defaults})",0 ,ico_certificate);
    }else{
        $tpl->table_form_field_text("{HostKeyAlgorithms}", $HostKeyAlgorithms, ico_certificate);
    }
    $HostKeyAlgorithms=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PubkeyAcceptedAlgorithms");
    $tpl->table_form_field_js("Loadjs('$page?PubkeyAcceptedAlgorithms-js=yes')");
    if(strlen($HostKeyAlgorithms)==0){
        $tpl->table_form_field_bool("{PubkeyAcceptedAlgorithms} ({defaults})",0 ,ico_certificate);
    }else{
        $tpl->table_form_field_text("{PubkeyAcceptedAlgorithms}", $HostKeyAlgorithms, ico_certificate);
    }

    $Paths["ssh_host_rsa_key"]="/etc/ssh/ssh_host_rsa_key";
    $Paths["ssh_host_ecdsa_key"]="/etc/ssh/ssh_host_ecdsa_key";
    $Paths["ssh_host_ed25519_key"]="/etc/ssh/ssh_host_ed25519_key";

    $SSHDHotstKey=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDHostKey");
    VERBOSE("SSHDHostKey=$SSHDHotstKey",__LINE__);
    if(strlen($SSHDHotstKey)<3){
        $SSHDHotstKey="ssh_host_rsa_key";
    }
    if(!isset($Paths[$SSHDHotstKey])){
        $SSHDHotstKey="ssh_host_rsa_key";
    }

    $tpl->table_form_field_js("Loadjs('$page?private-host-key-js=yes')");
    $tpl->table_form_field_text("{private_host_key}", $Paths[$SSHDHotstKey],ico_certificate);

    $tpl->table_form_section("{limits}");
    $tpl->table_form_field_js("Loadjs('$page?settings-js=yes&section=limits')");

    $tpl->table_form_field_bool("{wccp2_forwarding_method}",$sshd->main_array["X11Forwarding"],ico_params);


    $tpl->table_form_field_bool("{allow} Terminal (TTY)",$sshd->main_array["PermitTTY"],ico_params);
    $tpl->table_form_field_bool("IgnoreRhosts",$sshd->main_array["IgnoreRhosts"],ico_params);
    $tpl->table_form_field_bool("{StrictModes}",$sshd->main_array["StrictModes"],ico_params);
    $tpl->table_form_field_text("{ClientAliveInterval}",
        $sshd->main_array["ClientAliveInterval"]." {seconds}",ico_timeout);

    $tpl->table_form_field_text("{LoginGraceTime}",
        $sshd->main_array["LoginGraceTime"]." {seconds}",ico_timeout);

    $tpl->table_form_field_text("{ClientAliveCountMax}",
        $sshd->main_array["ClientAliveCountMax"]." {attempts}",ico_timeout);

    $tpl->table_form_field_text("{MaxAuthTries}",
        $sshd->main_array["MaxAuthTries"],ico_timeout);

    $tpl->table_form_field_text("{MaxSessions}",
        $sshd->main_array["MaxSessions"],ico_timeout);

    $tpl->table_form_section("{protocols}/{certificate}");
    $tpl->table_form_field_js("Loadjs('$page?settings-js=yes&section=protocols')");

    $tpl->table_form_field_bool("{PermitTunnel}",
        $sshd->main_array["PermitTunnel"],ico_params);

    $tpl->table_form_field_bool("{AllowTcpForwarding}",
        $sshd->main_array["AllowTcpForwarding"],ico_exchange);

    $tpl->table_form_field_bool("{UseDNS}",
        $sshd->main_array["UseDNS"],ico_server);

    $tpl->table_form_field_bool("{ssl_ciphers}",
        $SSHDCiphersEnable,ico_certificate);

    if($SSHDCiphersEnable==1) {
        $tpl->table_form_field_text("{ssl_ciphers}",
            $SSHDCiphers, ico_certificate);
    }
    echo $tpl->_ENGINE_parse_body( $tpl->table_form_compile());
    return true;
}
function save_config():bool{
	$tpl=new template_admin();
    $tpl->CLEAN_POST();

    if(isset($_POST["SSHDInterface"])) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDInterface", $_POST["SSHDInterface"]);
        unset($_POST["SSHDInterface"]);
    }

    if(isset($_POST["SSHDNotifyConnected"])) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDNotifyConnected", $_POST["SSHDNotifyConnected"]);
        unset($_POST["SSHDNotifyConnected"]);
    }
    if(isset($_POST["SSHOnlyShellInaBox"])) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHOnlyShellInaBox", $_POST["SSHOnlyShellInaBox"]);
        unset($_POST["SSHOnlyShellInaBox"]);
    }

    if(isset($_POST["SSHDUsePAM"])) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDUsePAM", $_POST["SSHDUsePAM"]);
        unset($_POST["SSHDUsePAM"]);
    }

    if(isset($_POST["SSHDProtocol"])) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDProtocol", $_POST["SSHDProtocol"]);
        unset($_POST["SSHDProtocol"]);
    }

    if(isset($_POST["SSHDCiphers"])) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDCiphers", $_POST["SSHDCiphers"]);
        unset($_POST["SSHDCiphers"]);
    }

    if(isset($_POST["SSHDCiphersEnable"])) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDCiphersEnable", $_POST["SSHDCiphersEnable"]);
        unset($_POST["SSHDCiphersEnable"]);
    }
    if(isset($_POST["SSHGoogle2FA"])) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHGoogle2FA", $_POST["SSHGoogle2FA"]);
        unset($_POST["SSHGoogle2FA"]);
    }
    if(isset($_POST["PasswordAuthenticationUsers"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PasswordAuthenticationUsers", $_POST["PasswordAuthenticationUsers"]);
        unset($_POST["PasswordAuthenticationUsers"]);

    }

	$sshd=new openssh();
	foreach ($_POST as $num=>$val){
		$sshd->main_array[$num]=$val;
	}
	
	$sshd->SaveInterface();
	@unlink(PROGRESS_DIR."/sshd.config");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return true;
}
function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{status}"]="$page?status=yes";
    $array["{clients}"]="fw.sshd.clients.php";
    $EnableSSHProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSSHProxy"));
    if($EnableSSHProxy==1){
        $array["{APP_SSHPROXY}"]="fw.sshproxy.php?table=yes";
        $array["{forward_rules}"]="fw.sshproxy.php?forward-start=yes";
    }

	$array["{events}"]="fw.sshd.events.php";
	echo $tpl->tabs_default($array);
	return true;
}
function HostKeyAlgorithms_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div id='HostKeyAlgorithms-table'></div>";
    echo "<script>LoadAjaxSilent('HostKeyAlgorithms-table','$page?HostKeyAlgorithms-table=yes');</script>";
    return true;
}
function PubkeyAcceptedAlgorithms_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div id='PubkeyAcceptedAlgorithms-table'></div>";
    echo "<script>LoadAjaxSilent('PubkeyAcceptedAlgorithms-table','$page?PubkeyAcceptedAlgorithms-table=yes');</script>";
    return true;
}
function PubkeyAcceptedAlgorithms_save():bool{
    $page=CurrentPageName();
    $enc=trim($_GET["PubkeyAcceptedAlgorithms-save"]);
    $PubkeyAcceptedAlgorithms=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PubkeyAcceptedAlgorithms");
    if($PubkeyAcceptedAlgorithms==""){
        $PubkeyAcceptedAlgorithms="ssh-ed25519,ecdsa-sha2-nistp256,ecdsa-sha2-nistp384,ecdsa-sha2-nistp521,rsa-sha2-256,rsa-sha2-512";
    }
    $tb=explode(",",$PubkeyAcceptedAlgorithms);
    foreach ($tb as $val){
        $CURRENT[$val]=true;
    }
    if(isset($CURRENT[$enc])){
        unset($CURRENT[$enc]);
    }else{
        $CURRENT[$enc]=true;
    }
    $f=array();
    foreach ($CURRENT as $key=>$val){
        $f[]=$key;
    }
    header("content-type: application/x-javascript");

    if(count($f)==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PubkeyAcceptedAlgorithms", "");
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
        echo "LoadAjaxSilent('PubkeyAcceptedAlgorithms-table','$page?PubkeyAcceptedAlgorithms-table=yes');\n";
        echo "LoadAjaxSilent('openssh-status-config','$page?openssh-status-config=yes');\n";
        return admin_tracks("Set OpenSSH Pubkey Accepted Algorithms to defaults");
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PubkeyAcceptedAlgorithms", @implode(",",$f));
    echo "LoadAjaxSilent('PubkeyAcceptedAlgorithms-table','$page?PubkeyAcceptedAlgorithms-table=yes');\n";
    echo "LoadAjaxSilent('openssh-status-config','$page?openssh-status-config=yes');\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks("Set OpenSSH Pubkey Accepted Algorithms to ".@implode(",",$f));
}
function HostKeyAlgorithms_save():bool{
    $page=CurrentPageName();
    $enc=trim($_GET["HostKeyAlgorithms-save"]);
    $HostKeyAlgorithms=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HostKeyAlgorithms");
    if($HostKeyAlgorithms==""){
        $HostKeyAlgorithms="ssh-ed25519,ecdsa-sha2-nistp256,ecdsa-sha2-nistp384,ecdsa-sha2-nistp521,rsa-sha2-512,rsa-sha2-256";
    }
    $tb=explode(",",$HostKeyAlgorithms);
    foreach ($tb as $val){
        $CURRENT[$val]=true;
    }
    if(isset($CURRENT[$enc])){
        unset($CURRENT[$enc]);
    }else{
        $CURRENT[$enc]=true;
    }
    $f=array();
    foreach ($CURRENT as $key=>$val){
        $f[]=$key;
    }

    header("content-type: application/x-javascript");


    if(count($f)==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HostKeyAlgorithms", "");
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
        echo "LoadAjaxSilent('HostKeyAlgorithms-table','$page?HostKeyAlgorithms-table=yes');\n";
        echo "LoadAjaxSilent('openssh-status-config','$page?openssh-status-config=yes');\n";
        return admin_tracks("Set OpenSSH HostKey Algorithms to defaults");
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HostKeyAlgorithms", @implode(",",$f));
    echo "LoadAjaxSilent('HostKeyAlgorithms-table','$page?HostKeyAlgorithms-table=yes');\n";
    echo "LoadAjaxSilent('openssh-status-config','$page?openssh-status-config=yes');\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks("Set OpenSSH HostKey Algorithms to ".@implode(",",$f));
}
function HostKeyAlgorithms_table():bool{
    $page=CurrentPageName();
    $HostKeyAlgorithms=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HostKeyAlgorithms");
    $tpl=new template_admin();
    $Def="ssh-ed25519,ecdsa-sha2-nistp256,ecdsa-sha2-nistp384,ecdsa-sha2-nistp521,rsa-sha2-512,rsa-sha2-256,ssh-rsa,ssh-dss";

    if($HostKeyAlgorithms==""){
        $HostKeyAlgorithms="ssh-ed25519,ecdsa-sha2-nistp256,ecdsa-sha2-nistp384,ecdsa-sha2-nistp521,rsa-sha2-512,rsa-sha2-256";
    }
    $tb=explode(",",$Def);
    foreach ($tb as $val){
        $DEFAULTS[$val]=true;
    }
    $tb=explode(",",$HostKeyAlgorithms);
    foreach ($tb as $val){
        $CURRENT[$val]=true;
    }
    ksort($DEFAULTS);
    foreach ($DEFAULTS as $key=>$noting){

        $keyEnc=urlencode($key);
        $tpl->table_form_field_js("Loadjs('$page?HostKeyAlgorithms-save=$keyEnc');");
        if(isset($CURRENT[$key])){
            $tpl->table_form_field_bool($key,1,ico_check);
        }else{
            $tpl->table_form_field_bool($key,0,ico_disabled);
        }

    }
    echo $tpl->table_form_compile();
    return true;

}
function PubkeyAcceptedAlgorithms_table():bool{
    $page=CurrentPageName();
    $PubkeyAcceptedAlgorithms=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PubkeyAcceptedAlgorithms");
    $tpl=new template_admin();
    $Def="ssh-ed25519,ssh-ed25519-cert-v01@openssh.com,ecdsa-sha2-nistp256,ecdsa-sha2-nistp384,ecdsa-sha2-nistp521,ecdsa-sha2-nistp256-cert-v01@openssh.com,ssh-rsa,rsa-sha2-256,rsa-sha2-512,ssh-rsa-cert-v01@openssh.com,ssh-dss,ssh-dss-cert-v01@openssh.com";

    if($PubkeyAcceptedAlgorithms==""){
        $PubkeyAcceptedAlgorithms="ssh-ed25519,ecdsa-sha2-nistp256,ecdsa-sha2-nistp384,ecdsa-sha2-nistp521,rsa-sha2-256,rsa-sha2-512";
    }
    $tb=explode(",",$Def);
    foreach ($tb as $val){
        $DEFAULTS[$val]=true;
    }
    $tb=explode(",",$PubkeyAcceptedAlgorithms);
    foreach ($tb as $val){
        $CURRENT[$val]=true;
    }
    ksort($DEFAULTS);
    foreach ($DEFAULTS as $key=>$noting){

        $keyEnc=urlencode($key);
        $tpl->table_form_field_js("Loadjs('$page?PubkeyAcceptedAlgorithms-save=$keyEnc');");
        if(isset($CURRENT[$key])){
            $tpl->table_form_field_bool($key,1,ico_check);
        }else{
            $tpl->table_form_field_bool($key,0,ico_disabled);
        }

    }
    echo $tpl->table_form_compile();
    return true;

}