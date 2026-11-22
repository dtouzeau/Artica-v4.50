<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.openssh.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
// awc_table_style()/awc_pager_js(): feuille de style et pagination partagees des
// tableaux d'entites. L'include DOIT rester INCONDITIONNEL et au premier niveau:
// place dans un if(!class_exists("sockets")){...} il ne serait jamais evalue --
// class.template-admin.inc charge deja class.sockets.inc -- et la page mourrait
// sur un « Call to undefined function awc_table_style() » au moment du rendu.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
// Verrou de la politique Meta « SSH » (sshd) : reglages tenus en lecture seule, cles autorisees Meta
// (metaid) non modifiables. Chaque enregistrement concerne refuse cote serveur (sshd_meta_*).
include_once(dirname(__FILE__)."/ressources/class.sshd.meta.inc");
// Pentest 2026-11-04 : zmd5, cles d'algorithmes, pays, section et chemin de cle etaient recopies sans encodage dans la page (XSS).
foreach (array("zmd5","AuthorizedKeys-add-delete","AuthorizedKeys-add-download","AuthorizedKeys-del-js") as $xk){if(isset($_GET[$xk])){$_GET[$xk]=preg_replace('#[^A-Za-z0-9]#','',(string)$_GET[$xk]);}}
foreach (array("PubkeyAcceptedAlgorithms-save","HostKeyAlgorithms-save") as $xk){if(isset($_GET[$xk])){$_GET[$xk]=preg_replace('#[^A-Za-z0-9@._+-]#','',(string)$_GET[$xk]);}}
foreach (array("limit-access-country","private-host-key-path-save","section","settings-popup") as $xk){if(isset($_GET[$xk])){$_GET[$xk]=preg_replace('#[^A-Za-z0-9_-]#','',(string)$_GET[$xk]);}}

if(isset($_POST["PrintMotd"])){UseWelcome_save();exit;}
if(isset($_GET["UseWelcome-js"])){UseWelcome_js();exit;}
if(isset($_GET["UseWelcome-popup"])){UseWelcome_popup();exit;}
if(isset($_GET["MaxStartups-js"])){MaxStartups_js();exit;}
if(isset($_GET["MaxStartups-popup"])){MaxStartups_popup();exit;}
if(isset($_POST["MaxStartups"])){MaxStartups_save();exit;}
if(isset($_GET["gssapi-js"])){gssapi_js();exit;}
if(isset($_GET["sshmtls-js"])){sshmtls_js();exit;}
if(isset($_GET["sshmtls-popup"])){sshmtls_popup();exit;}
if(isset($_POST["SSHDMTLSCompat"])){sshmtls_save("compat");exit;}
if(isset($_POST["SSHDMTLSStrict"])){sshmtls_save("strict");exit;}
if(isset($_GET["gssapi-popup"])){gssapi_popup();exit;}
if(isset($_POST["OpenSSHEnableGSSAPI"])){gssapi_save();exit;}
if(isset($_GET["UseBanner-js"])){UseBanner_js();exit;}
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
if(isset($_GET["sshd-sessions"])){sshd_sessions_host();exit;}
if(isset($_GET["sshd-kill-js"])){sshd_kill_js();exit;}
if(isset($_POST["sshd-kill"])){sshd_kill_save();exit;}
if(isset($_GET["sshd-qr"])){sshd_qr_show();exit;}
if(isset($_GET["sshd-qr-hide"])){sshd_qr_hide();exit;}
// Formulaires de reglages (general, auth, limits, protocols) : marqueur cache, car un reglage tenu par une
// politique Meta est poste sous un autre nom et ne peut plus servir d'aiguillage.
if(isset($_POST["sshd-settings-form"])){save_config();exit;}
if(isset($_POST["SSHOnlyShellInaBox"])){save_config();exit;}
if(isset($_POST["StrictModes"])){save_config();exit;}
if(isset($_POST["UsePAM"])){save_config();exit;}
if(isset($_POST["PermitRootLogin"])){save_config();exit;}
if(isset($_POST["PermitTunnel"])){save_config();exit;}
if(isset($_GET["sessions-js"])){sessions_js();exit;}
if(isset($_GET["sessions-popup"])){sessions_popup_start();exit;}
if(isset($_GET["sessions-popup2"])){sessions_popup();exit;}
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
if(isset($_GET["AuthorizedKeys-create-download"])){AuthorizedKeys_create_download();exit;}


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
if(isset($_POST["zkey"])){AuthorizedKeys_add_save();exit;}
if(isset($_GET["settings-js"])){main_setting_js();exit;}
if(isset($_GET["settings-popup"])){main_setting_popup();exit;}

page();

/**
 * Check the {Status,Info,Error} envelope returned by articarest.
 * @param string $raw      raw REST_API() answer
 * @param string $fallback error message when the daemon gives none
 * @return array array("ok"=>bool,"error"=>string,"json"=>object|null)
 */
function api_result($raw,$fallback):array{
    $json=json_decode($raw);
    if(!is_object($json)){
        return array("ok"=>false,"error"=>"$fallback: no answer from the API","json"=>null);
    }
    if(!isset($json->Status) or !$json->Status){
        $error="";
        if(isset($json->Error)){$error=trim(strval($json->Error));}
        if(strlen($error)==0){$error=$fallback;}
        return array("ok"=>false,"error"=>$error,"json"=>$json);
    }
    return array("ok"=>true,"error"=>null,"json"=>$json);
}
/*
 * Comptes et groupes systeme : routes POST JSON du demon (audit 2026-11-17). Le corps n'est jamais
 * journalise, contrairement a l'URL des anciennes routes GET (mot de passe en base64 dans le journal).
 */
/** REST: POST /system/group/create {"name"} -> {"Status","Gid"} */
function api_group_create($group):array{
    return api_result($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/group/create",array("name"=>(string)$group)),
        "Unable to create the system group $group");
}
/** REST: POST /system/group/delete {"name"} (the daemon purges its sudo rules and SSHAllowGroups) */
function api_group_delete($group):array{
    return api_result($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/group/delete",array("name"=>(string)$group)),
        "Unable to remove the system group $group");
}
/** REST: POST /system/users/membership {"username","group","action":"add"} (the daemon reconfigures sshd) */
function api_group_adduser($group,$user):array{
    return api_result($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/users/membership",
        array("username"=>(string)$user,"group"=>(string)$group,"action"=>"add")),
        "Unable to link the system user $user to the group $group");
}
/** REST: POST /system/users/membership {"username","group","action":"remove"} */
function api_group_deluser($group,$user):array{
    return api_result($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/users/membership",
        array("username"=>(string)$user,"group"=>(string)$group,"action"=>"remove")),
        "Unable to unlink the system user $user from the group $group");
}
/**
 * REST: POST /system/users/add (JSON). autogroup=true: the daemon creates the same-name group itself
 * and uses its GID; home "" = default home. The password is hashed by the daemon (8..128 chars).
 */
function api_user_add($user,$password,$shell="/bin/bash"):array{
    $params=array("username"=>(string)$user,"password"=>(string)$password,"gecos"=>"OpenSSH user",
        "home"=>"","shell"=>$shell,"gid"=>0,"autogroup"=>true,"allow_root"=>false);
    return api_result($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/users/add",$params),
        "Unable to create the system user $user");
}
/*
 * Politique Meta "Utilisateurs systeme" : comptes et groupes tenus par Artica Meta en lecture seule.
 * Source : GET /system/users/list ("meta" par compte/groupe), lu une fois par requete. Les handlers
 * refusent un element Meta avant d'appeler le demon ; un 423 du demon (element Meta, application Meta
 * en cours) est affiche tel quel.
 */
function sshdsu_meta_maps():array{
    if(isset($GLOBALS["SSHDSU_META"])){return $GLOBALS["SSHDSU_META"];}
    $maps=array("users"=>array(),"groups"=>array());
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/list"));
    if(is_object($data) && !empty($data->Status) && isset($data->UsersAndGroups) && is_object($data->UsersAndGroups)){
        $ug=$data->UsersAndGroups;
        if(isset($ug->users) && is_array($ug->users)){
            foreach($ug->users as $u){if(!empty($u->meta)){$maps["users"][(string)$u->name]=true;}}
        }
        if(isset($ug->groups) && is_array($ug->groups)){
            foreach($ug->groups as $g){if(!empty($g->meta)){$maps["groups"][(string)$g->name]=true;}}
        }
    }
    $GLOBALS["SSHDSU_META"]=$maps;
    return $maps;
}
function sshdsu_user_is_meta($name):bool{
    $m=sshdsu_meta_maps();
    return isset($m["users"][(string)$name]);
}
function sshdsu_group_is_meta($name):bool{
    $m=sshdsu_meta_maps();
    return isset($m["groups"][(string)$name]);
}
function sshdsu_meta_badge():string{
    return "<span class='ak-tag ak-info' title=\"{systemusers_meta_locked}\"><i class='fa-solid fa-lock'></i>&nbsp;Artica Meta</span>";
}
/**
 * Local Unix accounts, name-keyed. REST: GET /system/users/simple
 * Replaces the dead framework handler cmd.php?unixLocalUsers=yes.
 * @return array array(username=>username) sorted by name
 */
function api_local_users():array{
    $MAIN=array();
    $results=api_result($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/simple"),"Unable to list local users");
    if(!$results["ok"]){
        VERBOSE("api_local_users: {$results["error"]}",__LINE__);
        return $MAIN;
    }
    $json=$results["json"];
    if(!isset($json->Users) or !is_array($json->Users)){return $MAIN;}
    foreach ($json->Users as $user){
        if(!isset($user->name)){continue;}
        $name=trim($user->name);
        if(strlen($name)==0){continue;}
        $MAIN[$name]=$name;
    }
    ksort($MAIN);
    return $MAIN;
}
function group_new():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_prompt("{add_group}","{group name}",ico_add_group,$page,"group-new","LoadAjax('public-keys-groups','$page?groups-list=yes');");
    return true;
}
function group_new_perform():bool{
    $tpl=new template_admin();
    if(!sshd_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $tpl->CLEAN_POST_XSS();
    // Regle du demon : ^[a-z_][a-z0-9_-]{0,31}$ (minuscules, espaces -> tirets).
    $group=preg_replace('/\s+/',"-",strtolower(trim((string)$_POST["group-new"])));
    if(!preg_match('/^[a-z_][a-z0-9_-]{0,31}$/',$group)){
        echo $tpl->post_error("{systemusers_group_name_rule}");
        return false;
    }
    $results=api_group_create($group);
    if(!$results["ok"]){
        admin_tracks("Add new system Group $group failed: {$results["error"]}");
        echo $tpl->post_error($results["error"]);
        return false;
    }
    admin_tracks("Add new system Group $group");
    return true;
}



function SSHDIptables():bool{
    if(!sshd_is_admin()){echo (new template_admin())->post_error("{ERROR_NO_PRIVS2}");return false;}
    if(sshd_meta_key_held("SSHDIptables")){return sshd_meta_refuse_post();}
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
    // Pentest 2026-11-04 : public-key-password-js etait recopie sans encodage dans la page (XSS).
    $userT=req_html(req_html($user));
    $tpl->js_dialog8("$userT {password}","$page?public-key-password-popup=$userenc");
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
    if(!sshd_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    // Lu AVANT CLEAN_POST, qui le rogne ; meme decodage du formulaire que CLEAN_POST.
    $password=(isset($_POST["chgpasswdStr"]) && !is_array($_POST["chgpasswdStr"]))?(string)$_POST["chgpasswdStr"]:"";
    if(function_exists("url_decode_special_tool")){$password=url_decode_special_tool($password);}
    // Regle du demon : 8 a 128 caracteres, une seule ligne.
    $plen=strlen($password);
    if($plen<8 || $plen>128 || preg_match('/[\r\n]/',$password)){
        echo $tpl->post_error("{systemusers_password_rule}");
        return false;
    }
    $tpl->CLEAN_POST();
    $user=$tpl->CLEAN_BAD_XSS($_POST["chgpasswd"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/system/user/chpasswd",
        array("user"=>$user,"password"=>$password)
    ));
    if(is_object($json) && !empty($json->success)){
        admin_tracks("Changed system user password of [$user]");
    } else {
        $err=is_object($json)?($json->error ?? 'unknown error'):'connection error';
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($err)));
        return false;
    }
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
    if(!sshd_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    // Lu AVANT CLEAN_POST, qui le rogne ; meme decodage du formulaire que CLEAN_POST.
    $password=(isset($_POST["userpass"]) && !is_array($_POST["userpass"]))?(string)$_POST["userpass"]:"";
    if(function_exists("url_decode_special_tool")){$password=url_decode_special_tool($password);}
    // Regle du demon : 8 a 128 caracteres, une seule ligne.
    $plen=strlen($password);
    if($plen<8 || $plen>128 || preg_match('/[\r\n]/',$password)){
        echo $tpl->post_error("{systemusers_password_rule}");
        return false;
    }
    $tpl->CLEAN_POST();
    $user=$tpl->CLEAN_BAD_XSS($_POST["useradd"]);
    $group="";
    if(isset($_POST["group"])){$group=trim((string)$_POST["group"]);}
    // Groupe tenu par Artica Meta : refuse AVANT de creer le compte.
    if(strlen($group)>0 && sshdsu_group_is_meta($group)){
        echo $tpl->post_error("{systemusers_meta_locked}");
        return false;
    }

    $results=api_user_add($user,$password);
    if(!$results["ok"]){
        admin_tracks("Creating a system user named [$user] failed: {$results["error"]}");
        echo $tpl->post_error($results["error"]);
        return false;
    }
    admin_tracks("Creating a system user named [$user] success");

    // The form offers a group: link the new account to it as a secondary group,
    // which is what OpenSSH AllowGroups matches on.
    if(strlen($group)>0){
        $results=api_group_adduser($group,$user);
        if(!$results["ok"]){
            echo $tpl->post_error($results["error"]);
            return false;
        }
        admin_tracks("Link system user $user to group $group");
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/users/build/sudoers",new stdClass());
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
    // Pentest 2026-11-04 : group-users-js etait recopie sans encodage dans la page (XSS).
    $gpnameT=req_html(req_html($gpname));
    $tpl->js_dialog2("{system} >> {group} $gpnameT", "$page?group-users-popup=$gpenc",650);
    return true;
}
function group_users_popup():bool{
    // Pentest 2026-11-04 : group-users-popup etait recopie sans encodage dans la page (XSS).
    $gpenc=urlencode($_GET["group-users-popup"]);
    $gpname=req_html($_GET["group-users-popup"]);
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
    // Pentest 2026-11-04 : link-group-js etait recopie sans encodage dans la page (XSS).
    $gpnameT=req_html(req_html($gpname));
    $tpl->js_dialog3("{system} >> {group} $gpnameT {link_user}", "$page?group-users-link-popup=$gpenc",550);
    return true;
}
function group_users_link_popup():bool{
    $gpname=$_GET["group-users-link-popup"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $MAIN=array();
    if(!$users->AsSystemAdministrator){die();}
    if(sshdsu_group_is_meta($gpname)){
        echo $tpl->div_error("{systemusers_meta_locked}");
        return false;
    }

    $MAIN=api_local_users();
    // Comptes tenus par Artica Meta : leur appartenance ne se change pas ici.
    foreach(array_keys($MAIN) as $uname){
        if(sshdsu_user_is_meta($uname)){unset($MAIN[$uname]);}
    }

    $jsafter="Loadjs('$page?refresh-group-js=yes');dialogInstance3.close();";
    $form[]=$tpl->field_hidden("group-users-link",$gpname);
    $form[]=$tpl->field_array_hash($MAIN,"uid","{member}",null);
    echo $tpl->form_outside("{link_member} $gpname", $form,"","{link}",$jsafter,"AsSystemAdministrator");
    return true;
}
function group_users_link_perform():bool{
    $tpl=new template_admin();
    if(!sshd_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $tpl->CLEAN_POST_XSS();
    $gpname=$_POST["group-users-link"];
    $uid=$_POST["uid"];
    if($uid==null){return false;}
    if(sshdsu_group_is_meta($gpname) || sshdsu_user_is_meta($uid)){
        echo $tpl->post_error("{systemusers_meta_locked}");
        return false;
    }
    $results=api_group_adduser($gpname,$uid);
    if(!$results["ok"]){
        admin_tracks("failed to link system user $uid to group $gpname: {$results["error"]}");
        echo $tpl->post_error($results["error"]);
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
    // Pentest 2026-11-04 : unlink-group et group etaient recopies sans encodage dans la page (XSS).
    $tpl->js_confirm_delete(req_html("$user/$group"),"unlink-group","$user:$group","$('#$md').delete();");
    return true;
}
function group_unlink_perform():bool{
    $tpl=new template_admin();
    if(!sshd_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $tpl->CLEAN_POST_XSS();
    $pattern=explode(":",$_POST["unlink-group"]);
    $uid=$pattern[0];
    $gpname=$pattern[1] ?? "";
    if($uid==null){return false;}
    if(sshdsu_group_is_meta($gpname) || sshdsu_user_is_meta($uid)){
        echo $tpl->post_error("{systemusers_meta_locked}");
        return false;
    }
    $results=api_group_deluser($gpname,$uid);
    if(!$results["ok"]){
        admin_tracks("Unlink system user $uid from system group $gpname failed: {$results["error"]}");
        echo $tpl->post_error($results["error"]);
        return false;
    }
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
    $group=isset($_GET["group-users-list"])?(string)$_GET["group-users-list"]:"";
    $groupenc=rawurlencode($group);
    if(!function_exists("posix_getgrnam")){
        echo $tpl->div_error("posix_getgrnam no such function!!");
        return true;
    }
    if($group==""){
        echo $tpl->div_error("{error}");
        return true;
    }

    $userinfo = posix_getgrnam($group);
    if(!$userinfo){$userinfo=array();}
    if(!isset($userinfo["members"])){$userinfo["members"]=array();}
    if(!is_array($userinfo["members"])){$userinfo["members"]=array();}

    $BadUsers=BadUsers();
    $groupMeta=sshdsu_group_is_meta($group);
    $rows=array();
    foreach ($userinfo["members"] as $users){
        $aclid=md5($users.$group);
        $patternenc=rawurlencode($users);
        $userMeta=sshdsu_user_is_meta($users);
        $delete=$tpl->icon_unlink("Loadjs('$page?unlink-group=$patternenc&group=$groupenc&md=$aclid')","AsSystemAdministrator");
        if(isset($BadUsers[$users])){
            $delete=$tpl->icon_unlink();
        }
        // Artica Meta : appartenance en lecture seule.
        if($groupMeta || $userMeta){$delete=sshdsu_meta_badge();}
        $rows[]="<tr id='$aclid'>"
            ."<td class='center' style='width:1%'><span class='awc-ico'><i class='".ico_member."'></i></span></td>"
            ."<td><span class='awc-name'>"
                .$tpl->td_href(htmlspecialchars((string)$users),"{click_to_edit}","Loadjs('$page?public-key-user-js=$patternenc')")
                .($userMeta?" ".sshdsu_meta_badge():"")
            ."</span></td>"
            ."<td class='center' style='width:1%'><span class='awc-acts'>$delete</span></td>"
            ."</tr>";
    }
    $c=count($rows);

    $html=array();
    if($groupMeta){
        // Groupe tenu par Artica Meta : ni rattachement ni nouveau membre.
        $html[]="<div class='ak-banner ak-info' role='status' style='margin:10px 0'><i class='fa-solid fa-lock'></i>"
            ."<span class='ak-grow'>{systemusers_meta_banner}</span></div>";
    }else{
        $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
        $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?link-group-js=$groupenc')\"><i class='".ico_add_user."'></i> {link_user} </label>";
        $html[]="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?new-user-js=&group=$groupenc')\"><i class='".ico_add_user."'></i> {add_user} </label>";
        $html[]="</div>";
    }

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead><tr>";
    $html[]="<th class='center' style='width:1%'>&nbsp;</th>";
    $html[]="<th>{members}</th>";
    $html[]="<th class='center' style='width:1%'>&nbsp;</th>";
    $html[]="</tr></thead><tbody id='sshd-gpusers-rows'>";
    if($c==0){
        $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    // L'identifiant du <table> etait "table-$group-groups" -- le nom du groupe
    // vient de l'URL et repartait tel quel dans un id HTML ET dans le selecteur
    // jQuery $('#table-...'): une espace ou une apostrophe dans un nom de groupe
    // suffisait a rendre le selecteur invalide. Le <tfoot> ne portait de toute
    // facon qu'un <ul class='pagination'> jamais alimente.
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c {items}</span>";
    $html[]="<span class='awc-pg' id='sshd-gpusers-pager'></span>";
    $html[]="</div></div></div>";

    // ICON_SCRIPTS_COMPILE(): la version instance ne draine pas
    // $GLOBALS["TPL_ICON_SCRIPTS"]. Script hors de _ENGINE_parse_body().
    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"sshd-gpusers-rows","sshd-gpusers-pager");
    return true;

}

function config_file_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return false;}
	return $tpl->js_dialog1("{APP_OPENSSH} >> {config_file}", "$page?config-file-popup=yes");
}
/**
 * Read-only viewer of the live sshd_config. No apply button on purpose:
 * sshd_config is generated by the daemon (openssh/Configurator.go), so a
 * hand-edit would be overwritten on the next reconfigure. Settings are changed
 * through the parameters dialogs, not here.
 */
function config_file_popup(){
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){die();}
	// REST: GET /ssh/conf/file -> Info = base64 of the live /etc/ssh/sshd_config
	$results=api_result($GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/conf/file"),
		"Unable to read the OpenSSH configuration file");
	if(!$results["ok"]){
		echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($results["error"])));
		return;
	}
	$json=$results["json"];
	$data="";
	if(isset($json->Info)){$data=base64_decode($json->Info);}
	$t=time();

	// CodeMirror assets loaded inline so the dialog works whatever the parent
	// page included; the browser dedupes by URL. The nginx mode is the closest
	// fit for sshd_config (bare directives + '#' comments) and is the mode
	// fw.nginx.conf.php already uses for its config viewer.
	$h[]="<link rel='stylesheet' href='angular/plugins/codemirror/codemirror.css'>";
	$h[]="<script src='angular/js/plugins/codemirror/codemirror.js'></script>";
	$h[]="<script src='angular/js/plugins/codemirror/mode/nginx/nginx.js'></script>";
	$h[]="<style>
		.sshd-conf-banner { padding:10px 14px; border-radius:8px; margin-bottom:10px; font-size:13px; line-height:1.4;
			background:#f0f4f8; color:#676a6c; border:1px solid #e2e8f0; }
		.sshd-conf-banner .path {  font-weight:700; }
		.sshd-conf-viewer .CodeMirror { height:440px !important; font-size:12px; border:1px solid #e2e8f0;
			border-radius:8px; box-sizing:border-box; }
	</style>";
	$h[]="<div class='sshd-conf-banner'><i class='fas fa-eye'></i> "
		."{openssh_config_file_readonly_text}<br><span class='path'>/etc/ssh/sshd_config</span></div>";
	$h[]="<div class='sshd-conf-viewer'><textarea id='sshd-conf-area-$t'>".htmlspecialchars($data)."</textarea></div>";
	$h[]="<script>
	NoSpinner();
	(function(){
		var area=document.getElementById('sshd-conf-area-$t');
		if(!area){ return; }
		var editor=CodeMirror.fromTextArea(area,{
			mode:'nginx', lineNumbers:true, readOnly:true, lineWrapping:false, indentUnit:4
		});
		// The dialog mounts the canvas asynchronously: the first paint can be
		// 0x0, so refresh once the container is actually visible.
		setTimeout(function(){ editor.refresh(); },30);
	})();
	</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n",$h));
}




function AuthorizedKeys_add_delete_perform():bool{
    $tpl=new template_admin();
    if(!sshd_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $zmd5=preg_replace('#[^A-Za-z0-9]#','',(string)$_POST["AuthorizedKeys-add-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    if(sshd_meta_key_is_meta($q,$zmd5)){return sshd_meta_refuse_post("{sshd_meta_row_locked}");}
    $rows=$q->QUERY_SQL_PARAMS("SELECT email FROM sshd_authorizedkeys WHERE `zmd5`=? LIMIT 1",array($zmd5));
    $eMail="";
    if(is_array($rows) && count($rows)>0){$first=reset($rows);$eMail=(string)($first["email"] ?? "");}
    $q->QUERY_SQL_PARAMS("DELETE FROM sshd_authorizedkeys WHERE `zmd5`=?",array($zmd5));
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks("Delete a SSH Key for $eMail");
}
// Types de clés qu'OpenSSH 10 sait encore vérifier — miroir exact de
// SSHDKeys.IsValidAuthorizedKey côté daemon. ssh-dss est volontairement absent:
// DSA a été retiré d'OpenSSH, enregistrer une telle clé produirait une ligne
// que sshd ignore, donc un accès qu'on croit accordé et qui ne l'est pas.
//
// Cette page n'acceptait que ssh-rsa. Une clé ED25519 — le défaut de
// ssh-keygen depuis des années — était donc rejetée, alors que la
// configuration générée annonce ssh-ed25519 en tête de
// PubkeyAcceptedAlgorithms. Il ne restait à l'administrateur qu'à écrire sa
// clé à la main dans /etc/ssh/authorized_keys, hors de toute gestion, d'où
// elle disparaissait à la régénération suivante (incident 10.8.0.9).
// Valide la cle et, si elle l'est, la remplace par sa forme propre (celle qu'on enregistre).
function AuthorizedKeys_is_valid(string &$zkey):bool{
    $clean=AuthorizedKeys_normalize($zkey);
    if($clean===""){return false;}
    $zkey=$clean;
    return true;
}
// Remet en une ligne propre une cle collee depuis un terminal, un courriel ou un
// traitement de texte, "" si ce n'est pas une cle publique. Vu le 2026-11-19 : une
// cle ED25519 valide copiee depuis un terminal etait refusee sans autre explication
// (caractere invisible ajoute par la copie). Le type et le bloc base64 ne gardent
// donc que leurs caracteres ASCII imprimables (espace de largeur nulle, BOM,
// cesure conditionnelle... arrivent a moitie decodes par url_decode_special_tool,
// qui change %EF en « ï » : on ne peut pas les reconnaitre, seulement les jeter),
// CR et tabulations sont des espaces, et un bloc coupe en plusieurs lignes est
// recolle. Le bloc est verifie en le decodant jusqu'au dernier octet, ce qui refuse
// aussi un bloc tronque qui « ressemblait » a du base64 (avant, un morceau de cle
// passait et donnait un acces qui ne marchait pas). Le commentaire reste tel quel.
function AuthorizedKeys_normalize(string $zkey):string{
    // type annonce => type ecrit dans le bloc
    static $types=array("ssh-rsa"=>"ssh-rsa","ssh-ed25519"=>"ssh-ed25519",
        "ecdsa-sha2-nistp256"=>"ecdsa-sha2-nistp256","ecdsa-sha2-nistp384"=>"ecdsa-sha2-nistp384",
        "ecdsa-sha2-nistp521"=>"ecdsa-sha2-nistp521",
        "sk-ssh-ed25519@openssh.com"=>"sk-ssh-ed25519@openssh.com",
        "sk-ecdsa-sha2-nistp256@openssh.com"=>"sk-ecdsa-sha2-nistp256@openssh.com",
        "rsa-sha2-256"=>"ssh-rsa","rsa-sha2-512"=>"ssh-rsa");
    // Le formulaire envoie escape(encodeURIComponent()) et url_decode_special_tool ne
    // connait qu'une liste de sequences : %0D, %E2%80%8B... restent tels quels. Une cle
    // n'a pas de « % » : ce qui en reste est un encodage a defaire.
    if(strpos($zkey,"%")!==false){$zkey=rawurldecode($zkey);}
    // Espaces Unicode (insecable, fine...) en espace ; octets, pas de /u : la chaine
    // peut ne plus etre de l'UTF-8 valide apres le double decodage.
    $zkey=str_replace(array("\xC2\xA0","\xE2\x80\xAF","\xE3\x80\x80"),' ',$zkey);
    $fields=preg_split('#[\s]+#',trim($zkey),-1,PREG_SPLIT_NO_EMPTY);
    if(!is_array($fields)){return "";}
    $ascii=array();
    foreach($fields as $field){$ascii[]=preg_replace('/[^\x21-\x7E]/','',$field);}
    foreach($ascii as $i=>$field){
        if(!isset($types[$field])){continue;}
        $blob="";
        for($j=$i+1;$j<count($ascii) && $j<=$i+64;$j++){
            if($ascii[$j]===""){continue;} // un caractere invisible isole
            if(preg_match('#^[A-Za-z0-9+/]+={0,3}$#',$ascii[$j])!==1){return "";}
            $blob.=$ascii[$j];
            if(AuthorizedKeys_blob_is($blob,$types[$field])){
                $line=array_slice($ascii,0,$i); // options (from=, no-pty...) : ASCII
                $line[]=$field;
                $line[]=$blob;
                $comment=trim(implode(" ",array_slice($fields,$j+1)));
                if($comment!==""){$line[]=$comment;}
                return implode(" ",$line);
            }
        }
        return "";
    }
    return "";
}
// Le bloc d'une cle OpenSSH est une suite de chaines (uint32 longueur + octets) : le
// nom du type puis un nombre fixe de champs selon le type. Il doit etre consomme
// exactement, sinon c'est un bloc tronque ou un bloc auquel on a colle autre chose.
function AuthorizedKeys_blob_is(string $blob,string $type):bool{
    static $parts=array("ssh-ed25519"=>2,"ssh-rsa"=>3,"ecdsa-sha2-nistp256"=>3,"ecdsa-sha2-nistp384"=>3,
        "ecdsa-sha2-nistp521"=>3,"sk-ssh-ed25519@openssh.com"=>3,"sk-ecdsa-sha2-nistp256@openssh.com"=>4);
    $raw=base64_decode($blob,true);
    if($raw===false || !isset($parts[$type])){return false;}
    $off=0;
    $len=strlen($raw);
    for($p=0;$p<$parts[$type];$p++){
        if($off+4>$len){return false;}
        $n=unpack("N",substr($raw,$off,4));
        if(!is_array($n) || $n[1]<1 || $off+4+$n[1]>$len){return false;}
        if($p===0 && substr($raw,4,$n[1])!==$type){return false;}
        $off+=4+$n[1];
    }
    return $off===$len;
}
function group_delete():bool{
    $tpl=new template_admin();
    $group=$_GET["group-delete"];
    $md=$_GET["md"];
    // Pentest 2026-11-04 : group-delete etait recopie sans encodage dans la page (XSS).
    $tpl->js_confirm_delete(req_html($group),"group-delete",$group,"$('#$md').remove()");
    return true;
}
function group_delete_perform():bool{
    $tpl=new template_admin();
    if(!sshd_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $group=trim((string)$_POST["group-delete"]);
    if(sshdsu_group_is_meta($group)){
        echo $tpl->post_error("{systemusers_meta_locked}");
        return false;
    }
    $results=api_group_delete($group);
    if(!$results["ok"]){
        admin_tracks("Remove system Group $group failed: {$results["error"]}");
        echo $tpl->post_error($results["error"]);
        return false;
    }
    admin_tracks("Remove system Group $group");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
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

    $groups=explode("\n",(string)@file_get_contents("/etc/group"));
    $BadUsers=BadUsers();
    $sshd=new openssh();
    $ALR=array();
    $AllowGroups=$sshd->AllowGroups;
    if(!is_array($AllowGroups)){$AllowGroups=array();}
    foreach ($AllowGroups as $gpname){$ALR[$gpname]=1;}

    $locked_group=array("ssh"=>true,"backup"=>true,"operator"=>true,"staff"=>true,"users"=>true);

    $rows=array();
    foreach ($groups as $line){
        if(!preg_match("#^(.+?):#",$line,$re)){continue;}
        $group=trim($re[1]);
        VERBOSE("[$group]",__LINE__);
        if(isset($BadUsers[$group])){continue;}
        if($group==""){continue;}
        $userinfo=posix_getgrnam($group);
        if(!is_array($userinfo)){$userinfo=array();}
        if(!isset($userinfo["members"])||!is_array($userinfo["members"])){$userinfo["members"]=array();}
        $enabled=isset($ALR[$group])?1:0;
        $aclid=md5($group);
        $patternenc=rawurlencode($group);

        $groupMeta=sshdsu_group_is_meta($group);
        $check=$tpl->icon_check($enabled,"Loadjs('$page?public-key-group-enable=$patternenc&md=$aclid')",null,"AsSystemAdministrator");
        $delete=$tpl->icon_delete("Loadjs('$page?group-delete=$patternenc&md=$aclid')","AsSystemAdministrator");
        if(isset($locked_group[$group])){
            $delete=$tpl->icon_delete(null);
        }
        if($groupMeta){
            // Groupe tenu par Artica Meta : acces SSH et suppression en lecture seule.
            $check=$tpl->icon_check($enabled,null);
            $delete=sshdsu_meta_badge();
        }

        $rows[]="<tr id='$aclid'>"
            ."<td class='center' style='width:1%'><span class='awc-ico'><i class='fa-solid fa-people-group'></i></span></td>"
            ."<td><span class='awc-name'>"
                .$tpl->td_href(htmlspecialchars($group),"{click_to_edit}","Loadjs('$page?group-users-js=$patternenc')")
            ."</span></td>"
            ."<td class='center' style='width:1%'><span class='awc-seen'>".count($userinfo["members"])."</span></td>"
            ."<td class='center' style='width:1%'>$check</td>"
            ."<td class='center' style='width:1%'><span class='awc-acts'>$delete</span></td>"
            ."</tr>";
    }
    $c=count($rows);

    $html=array();
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?new-group-js=yes')\"><i class='".ico_add_group."'></i> {add_group} </label>";
    $html[]="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?new-user-js=yes')\"><i class='".ico_add_user."'></i> {add_user} </label>";
    $html[]="</div>";

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead><tr>";
    $html[]="<th class='center' style='width:1%'>&nbsp;</th>";
    $html[]="<th>{groups}</th>";
    // Le nombre de membres n'avait pas d'en-tete: trois colonnes de la ligne
    // repondaient a un <th> vide.
    $html[]="<th class='center' style='width:1%' nowrap>{members}</th>";
    $html[]="<th class='center' style='width:1%' nowrap>{enable}</th>";
    $html[]="<th class='center' style='width:1%'>&nbsp;</th>";
    $html[]="</tr></thead><tbody id='sshd-groups-rows'>";
    if($c==0){
        $html[]="<tr><td colspan='5'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c {items}</span>";
    $html[]="<span class='awc-pg' id='sshd-groups-pager'></span>";
    $html[]="</div></div></div>";

    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"sshd-groups-rows","sshd-groups-pager");
    return true;

}
function sessions_popup_start():bool{
    $page=CurrentPageName();
    echo "<div id='sessions-popup'></div>
<script>LoadAjax('sessions-popup','$page?sessions-popup2=yes')</script>";
    return true;

}
function sessions_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/connected"));
    // "$json->Info->Users" etait deroule sans la moindre verification: une
    // reponse absente ou d'une autre forme faisait un foreach sur null.
    $Array=array();
    if(is_object($json) && isset($json->Info) && is_object($json->Info) && isset($json->Info->Users)){
        if(is_array($json->Info->Users)){$Array=$json->Info->Users;}
    }

    $rows=array();
    foreach ($Array as $main){
        if(!is_object($main)){continue;}
        $pid=intval($main->Pid ?? 0);
        $kill=$tpl->icon_unlink("Loadjs('$page?sshd-kill-js=$pid')","AsSystemAdministrator");
        $rows[]="<tr id='id$pid'>"
            ."<td class='center' style='width:1%'><span class='awc-ico'><i class='fa fa-duotone fa-user'></i></span></td>"
            ."<td><span class='awc-name'>".htmlspecialchars((string)($main->User ?? ""))."</span></td>"
            ."<td class='center' style='width:1%'><span class='awc-mono'>$pid</span></td>"
            ."<td style='width:1%' nowrap><span class='awc-mono'>".htmlspecialchars((string)($main->IPAddr ?? ""))."</span></td>"
            ."<td class='center' style='width:1%'><span class='awc-acts'>$kill</span></td>"
            ."</tr>";
    }
    $c=count($rows);

    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead><tr>";
    $html[]="<th class='center' style='width:1%'>&nbsp;</th>";
    $html[]="<th>{members}</th>";
    $html[]="<th class='center' style='width:1%'>Pid</th>";
    $html[]="<th style='width:1%' nowrap>{ipaddr}</th>";
    $html[]="<th class='center' style='width:1%'>&nbsp;</th>";
    $html[]="</tr></thead><tbody id='sshd-sessions-rows'>";
    if($c==0){
        $html[]="<tr><td colspan='5'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c {items}</span>";
    $html[]="<span class='awc-pg' id='sshd-sessions-pager'></span>";
    $html[]="</div></div></div>";

    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"sshd-sessions-rows","sshd-sessions-pager");

}
// ---- SSH key files (maquette stitch_artica_ssh_key_manager_redesign, 2026-11-19) -------------
// One RSA 4096 login key pair per local account, generated by articarest
// (GET /ssh/privkey/generate/{user}, SSHDKeys.GenUserPrivKey) and kept in sshd.db sshd_privkeys.
// WARNING: the column names of sshd_privkeys are INVERTED and every consumer relies on it:
// "privatekey" holds the PUBLIC line (ssh-rsa AAAA… user, appended to authorized_keys when
// enabled=1) and "publickey" holds the PRIVATE PEM (the downloaded file). Never "fix" them.
// Accounts: GET /system/users/simple (the set listed) + GET /system/users/list (home, shell,
// meta, locked). Left out of the mock-up because invented or not backed: nothing beyond the
// data above (no date, fingerprint, expiry); the "Component preview" bar; the IP 192.168.1.10
// (the example command uses the address the console is reached on).
function public_key_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!sshd_is_admin()){$tpl->js_no_privileges();return false;}
	return $tpl->js_dialog1("{sshk_title}", "$page?public-key-popup=yes",1040);
}

function public_key_popup():bool{
	$page=CurrentPageName();
	if(!sshd_is_admin()){return false;}
	echo "<div id='public-keys-userlist'></div><script>LoadAjax('public-keys-userlist','$page?public-keys-userlist=yes');</script>";
	return true;
}

// Accounts of /system/users/list, name-keyed: home, shell, meta, locked.
function sshk_accounts_info():array{
	$out=array();
	$data=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/list"));
	if(!is_object($data) || empty($data->Status) || !isset($data->UsersAndGroups->users) || !is_array($data->UsersAndGroups->users)){return $out;}
	foreach($data->UsersAndGroups->users as $u){
		if(!is_object($u) || !isset($u->name)){continue;}
		$out[(string)$u->name]=array("home"=>(string)($u->home ?? ""),"shell"=>(string)($u->shell ?? ""),
			"meta"=>!empty($u->meta),"locked"=>!empty($u->locked));
	}
	return $out;
}

// Key rows of sshd_privkeys, name-keyed: has (a key exists), enabled, pub (the PUBLIC line).
function sshk_keys():array{
	$out=array();
	$q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
	$rows=$q->QUERY_SQL("SELECT username,enabled,slength,privatekey FROM sshd_privkeys");
	if(!is_array($rows)){return $out;}
	foreach($rows as $r){
		$pub=trim(str_replace('\\n',"\n",(string)($r["privatekey"] ?? "")));
		$out[(string)$r["username"]]=array("has"=>intval($r["slength"] ?? 0)>1 && strlen($pub)>=20,
			"enabled"=>intval($r["enabled"] ?? 0)==1,"pub"=>$pub);
	}
	return $out;
}

function sshk_no_shell($shell):bool{
	$b=basename(trim((string)$shell));
	return $b==="nologin" || $b==="false";
}

// The model of the window: every listed account with its key state, and the counts.
function sshk_model():array{
	$M=array("rows"=>array(),"n"=>0,"keys"=>0,"allowed"=>0);
	$info=sshk_accounts_info();
	$keys=sshk_keys();
	$bad=BadUsers();
	foreach(api_local_users() as $name){
		if(isset($bad[$name])){continue;}
		$i=isset($info[$name])?$info[$name]:array("home"=>"","shell"=>"","meta"=>false,"locked"=>false);
		$k=isset($keys[$name])?$keys[$name]:array("has"=>false,"enabled"=>false,"pub"=>"");
		$state=!$k["has"]?"none":($k["enabled"]?"ready":"blocked");
		$M["rows"][]=array("name"=>$name,"home"=>$i["home"],"shell"=>$i["shell"],"meta"=>$i["meta"],"locked"=>$i["locked"],
			"state"=>$state,"pub"=>$k["pub"]);
		$M["n"]++;
		if($k["has"]){$M["keys"]++;}
		if($state==="ready"){$M["allowed"]++;}
	}
	return $M;
}

function sshk_tr($token):string{
	$tpl=new template_admin();
	$t=$tpl->_ENGINE_parse_body("{".$token."}");
	$args=func_get_args(); array_shift($args);
	foreach($args as $a){ $p=strpos($t,"%s"); if($p===false){break;} $t=substr_replace($t,(string)$a,$p,2); }
	return $t;
}

function sshk_esc($v):string{
	return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}

// Widget: the window (banner, toolbar, table or empty state).
function public_key_userlist(){
	$tpl=new template_admin();
	if(!sshd_is_admin()){echo $tpl->_ENGINE_parse_body($tpl->div_error("{sshk_no_privs}"));return;}
	$M=sshk_model();
	$h=array();
	$h[]="<div class='sshk'>";
	$h[]=sshk_banner();
	$h[]=sshk_toolbar($M);
	$h[]=$M["n"]==0?sshk_empty():sshk_table($M);
	$h[]="</div>";
	echo sshk_style().awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$h))
		."<script>NoSpinner();".sshk_js()."</script>".awc_pager_js(25,"sshk-rows","sshk-pager");
}

function sshk_banner():string{
	return "<section class='sshk-banner'><h3>{sshk_banner_title}</h3><p>{sshk_banner_text}</p>"
		."<div class='ak-chips'><span class='ak-tag ak-info'><i class='fas fa-check'></i>{sshk_chip_rsa}</span>"
		."<span class='ak-tag ak-info'><i class='fas fa-lock'></i>{sshk_chip_one}</span>"
		."<span class='ak-tag ak-info'><i class='fas fa-rotate-left'></i>{sshk_chip_cut}</span></div>"
		."<ol class='sshk-steps'><li><span>1</span>{sshk_step1}</li><li><span>2</span>{sshk_step2}</li>"
		."<li><span>3</span>{sshk_step3} <em>{sshk_step3_note}</em></li></ol></section>";
}

function sshk_toolbar(array $M):string{
	$page=CurrentPageName();
	$sum=sshk_tr("sshk_summary","<b>".$M["n"]."</b>","<b>".$M["keys"]."</b>","<b>".$M["allowed"]."</b>");
	return "<div class='sshk-bar'><div class='sshk-sum'><i class='fas fa-users'></i><span>$sum</span></div>"
		."<button type='button' class='ak-btn ak-btn-primary' title=\"{sshk_add_user_help}\" OnClick=\"Loadjs('$page?new-user-js=yes')\"><i class='fas fa-user-plus'></i>{add_user}</button></div>";
}

function sshk_empty():string{
	$page=CurrentPageName();
	return "<div class='ak-card ak-empty-c sshk-empty'><i class='fas fa-users'></i><b>{sshk_empty_title}</b><span>{sshk_empty_text}</span>"
		."<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?new-user-js=yes')\"><i class='fas fa-user-plus'></i>{add_user}</button></div>";
}

function sshk_table(array $M):string{
	$h=array();
	$h[]="<div class='ak-card sshk-card'><table class='sshk-tbl'><thead><tr>";
	$h[]="<th>{sshk_col_account}</th><th>{sshk_col_key}</th><th>{sshk_col_allow}</th><th class='sshk-end'>{sshk_col_actions}</th>";
	$h[]="</tr></thead><tbody id='sshk-rows'>";
	foreach($M["rows"] as $r){ $h[]=sshk_row($r); }
	$h[]="</tbody></table>";
	$h[]="<div class='sshk-foot'><span>".sshk_tr("sshk_showing",$M["n"])."</span><span class='awc-pg' id='sshk-pager'></span></div></div>";
	return @implode("",$h);
}

function sshk_state_chip($state):string{
	if($state==="ready"){ return "<span class='ak-pill ak-ok'><i class='fas fa-circle-check'></i>{sshk_state_ready}</span>"; }
	if($state==="blocked"){ return "<span class='ak-pill ak-warn'><i class='fas fa-ban'></i>{sshk_state_blocked}</span>"; }
	return "<span class='ak-pill ak-off'><i class='fas fa-circle-minus'></i>{sshk_state_none}</span>";
}

// The "Allow sign-in with this key" switch: a key must exist first.
function sshk_switch(array $r):string{
	$page=CurrentPageName();
	$enc=rawurlencode($r["name"]);
	$aria="aria-label=\"".sshk_tr("sshk_allow_aria",sshk_esc($r["name"]))."\"";
	if($r["state"]==="none"){
		return "<button type='button' role='switch' class='ak-sw' aria-checked='false' disabled title=\"{sshk_switch_nokey}\" $aria></button>";
	}
	$on=$r["state"]==="ready"?"true":"false";
	return "<button type='button' role='switch' class='ak-sw' aria-checked='$on' $aria OnClick=\"this.disabled=true;Loadjs('$page?public-key-user-enable=$enc');\"></button>";
}

function sshk_row(array $r):string{
	$page=CurrentPageName();
	$enc=rawurlencode($r["name"]);
	$name=sshk_esc($r["name"]);
	$badges="";
	if($r["meta"]){ $badges.="<span class='ak-tag ak-off' title=\"{sshk_meta_help}\"><i class='fas fa-lock'></i>{sshk_meta}</span>"; }
	if($r["locked"]){ $badges.="<span class='ak-tag ak-off' title=\"{sshk_locked_help}\"><i class='fas fa-user-lock'></i>{sshk_locked}</span>"; }
	$sub=array();
	if($r["home"]!==""){ $sub[]=sshk_tr("sshk_home",sshk_esc($r["home"])); }
	if($r["shell"]!==""){ $sub[]=sshk_tr("sshk_shell",sshk_esc($r["shell"])); }
	$noshell=sshk_no_shell($r["shell"])&&$r["state"]!=="none"?"<div class='sshk-warn'><i class='fas fa-triangle-exclamation'></i>{sshk_noshell}</div>":"";
	$details="Loadjs('$page?public-key-user-js=$enc')";
	if($r["state"]==="none"){
		$main="<button type='button' class='ak-btn ak-btn-primary ak-btn-sm' OnClick=\"$details\"><i class='fas fa-key'></i>{sshk_create}</button>";
	}else{
		$main="<a class='ak-btn ak-btn-sm' href='/$page?public-key-user-download=$enc'><i class='fas fa-download'></i>{sshk_download}</a>";
	}
	$pwd=$r["meta"]
		?"<button type='button' class='ak-btn ak-btn-ghost ak-btn-sm' disabled title=\"{sshk_meta_help}\"><i class='fas fa-lock'></i>{sshk_change_password}</button>"
		:"<button type='button' class='ak-btn ak-btn-ghost ak-btn-sm' OnClick=\"Loadjs('$page?public-key-password-js=$enc')\"><i class='fas fa-lock'></i>{sshk_change_password}</button>";
	return "<tr><td><div class='sshk-id'><span class='ak-ico ak-ico-sm ak-off'><i class='fas fa-user'></i></span><div>"
		."<div class='sshk-name'><a href=\"javascript:blur();\" OnClick=\"$details\">$name</a>$badges</div>"
		."<div class='sshk-sub'>".@implode(" · ",$sub)."</div>$noshell</div></div></td>"
		."<td>".sshk_state_chip($r["state"])."</td><td>".sshk_switch($r)."</td>"
		."<td class='sshk-end'><div class='sshk-acts'>$main$pwd<button type='button' class='ak-link' OnClick=\"$details\">{sshk_details}</button></div></td></tr>";
}

// GET (Loadjs): flips "Allow sign-in" then refreshes the open views.
function public_key_user_enable():bool{
	$tpl=new template_admin();
	if(!sshd_is_admin()){$tpl->js_no_privileges();return false;}
	$page=CurrentPageName();
	$uid=strval($_GET["public-key-user-enable"]);
	$q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
	// Pentest 2026-11-04 : public-key-user-enable etait concatene dans la requete.
	$ligne=$q->QUERY_SQL_PARAMS("SELECT enabled,slength FROM sshd_privkeys WHERE `username`=?",array($uid));
	$ligne=(is_array($ligne) && isset($ligne[0]))?$ligne[0]:array();
	if(intval($ligne["slength"] ?? 0)<=1){$tpl->js_error("{sshk_switch_nokey}");return false;}
	$enabled=intval($ligne["enabled"] ?? 0)==0?1:0;
	$q->QUERY_SQL_PARAMS("UPDATE sshd_privkeys SET `enabled`=? WHERE `username`=?",array($enabled,$uid));
	if(!$q->ok){$tpl->js_error($q->mysql_error);return false;}
	admin_tracks(($enabled==1?"Allow":"Block")." sign-in with the SSH key of $uid");
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
	header("content-type: application/x-javascript");
	echo sshk_refresh_js($uid);
	return true;
}

function sshk_refresh_js($uid):string{
	$page=CurrentPageName();
	$enc=rawurlencode($uid);
	return "if(document.getElementById('public-keys-userlist')){LoadAjaxSilent('public-keys-userlist','$page?public-keys-userlist=yes');}"
		."if(document.getElementById('public-key-user-div')){LoadAjaxSilent('public-key-user-div','$page?public-key-user-form=$enc');}";
}

// Downloads the key file (PRIVATE key, stored in the "publickey" column — see the warning above).
function public_key_user_download():bool{
	if(!sshd_is_admin()){http_response_code(403);echo "Forbidden";return false;}
	$q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
	$uid=strval($_GET["public-key-user-download"]);
	// Pentest 2026-11-04 : public-key-user-download etait concatene dans la requete.
	$ligne=$q->QUERY_SQL_PARAMS("SELECT publickey FROM sshd_privkeys WHERE `username`=?",array($uid));
	$ligne=(is_array($ligne) && isset($ligne[0]))?$ligne[0]:array();
	$privatekey=str_replace('\\n',"\n",(string)($ligne["publickey"] ?? ""));
	if(strlen(trim($privatekey))<20){http_response_code(404);echo "No key file for this account";return false;}
	$fname=preg_replace('/[^A-Za-z0-9._-]/','_',$uid).".pem";
	header('Content-type: application/x-pem-file');
	header("Content-Disposition: attachment; filename=\"$fname\"");
	header("Cache-Control: no-store");
	header("X-Content-Type-Options: nosniff");
	header("Content-Length: ".strlen($privatekey));
	if(ob_get_level()>0){ob_clean();}
	echo $privatekey;
	return admin_tracks("Downloaded the SSH key file of $uid");
}

function public_key_user_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!sshd_is_admin()){$tpl->js_no_privileges();return false;}
	$user=strval($_GET["public-key-user-js"]);
	// Pentest 2026-11-04 : public-key-user-js etait recopie sans encodage dans la page (XSS).
	$userT=req_html(req_html($user));
	$tpl->js_dialog2("{sshk_title}: $userT", "$page?public-key-user-popup=".urlencode($user),760);
	return true;
}

function public_key_user_popup(){
	if(!sshd_is_admin()){return;}
	$page=CurrentPageName();
	$uidencode=urlencode(strval($_GET["public-key-user-popup"]));
	echo "<div id='public-key-user-div'></div><script>LoadAjax('public-key-user-div','$page?public-key-user-form=$uidencode');</script>";
}

// The per-account window: header, then the invitation (no key) or the four cards.
function public_key_user_form(){
	$tpl=new template_admin();
	if(!sshd_is_admin() || !fw_progress_page_priv("AsSystemAdministrator")){echo $tpl->_ENGINE_parse_body($tpl->div_error("{sshk_no_privs}"));return false;}   // task link signed for this session: built only for whom may run it
	$uid=strval($_GET["public-key-user-form"]);
	$r=null;
	foreach(sshk_model()["rows"] as $row){ if($row["name"]===$uid){$r=$row;break;} }
	if($r===null){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{sshk_unknown_account}")); return false; }
	$h=array();
	$h[]="<div class='sshk sshk-detail'>";
	$h[]="<div class='sshk-dh'><h3>".sshk_tr("sshk_detail_title",sshk_esc($uid))."</h3><p>".sshk_tr("sshk_detail_text","<b>".sshk_esc($uid)."</b>")."</p></div>";
	$h[]="<div id='sshk-progress'></div>";
	if($r["state"]==="none"){
		$h[]=sshk_detail_nokey($r);
	}else{
		$h[]=sshk_detail_state($r);
		$h[]=sshk_detail_file($r);
		$h[]=sshk_detail_lock($r);
		$h[]=sshk_detail_replace($r);
	}
	$h[]="</div>";
	echo sshk_style().$tpl->_ENGINE_parse_body(@implode("\n",$h))."<script>NoSpinner();".sshk_js()."</script>";
	return true;
}

// Generation through articarest with progress; $confirm = text shown before (Replace key).
function sshk_generate_js($uid,$confirm=null):string{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$enc=rawurlencode($uid);
	return $tpl->framework_buildjs("/ssh/privkey/generate/$enc","sshd.progress","sshd.log","sshk-progress",
		sshk_refresh_js($uid),null,$confirm,"AsSystemAdministrator");
}

function sshk_detail_nokey(array $r):string{
	$js=sshk_generate_js($r["name"]);
	return "<div class='ak-card ak-empty-c sshk-invite'><i class='fas fa-key'></i><b>".sshk_tr("sshk_nokey_title",sshk_esc($r["name"]))."</b>"
		."<span>{sshk_nokey_text}</span>".sshk_noshell_note($r)
		."<button type='button' class='ak-btn ak-btn-primary' OnClick=\"this.disabled=true;$js\"><i class='fas fa-key'></i>{sshk_create}</button></div>";
}

function sshk_noshell_note(array $r):string{
	return sshk_no_shell($r["shell"])?"<div class='ak-banner ak-warn sshk-note'><i class='fas fa-triangle-exclamation'></i><div class='ak-banner-m'>{sshk_noshell_long}</div></div>":"";
}

function sshk_detail_state(array $r):string{
	return "<div class='ak-card sshk-state'><div><div class='sshk-state-l'><span>{sshk_current_state}</span>".sshk_state_chip($r["state"])."</div>"
		."<p>{sshk_switch_help}</p>".sshk_noshell_note($r)."</div>"
		."<label class='sshk-sw-l'><span>{sshk_allow_short}</span>".sshk_switch($r)."</label></div>";
}

function sshk_detail_file(array $r):string{
	$page=CurrentPageName();
	$enc=rawurlencode($r["name"]);
	$host=preg_replace('/[^A-Za-z0-9.\-:\[\]]/','',preg_replace('/:\d+$/','',(string)($_SERVER["HTTP_HOST"] ?? "")));
	if($host===""){ $host="<appliance>"; }
	$port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDListenPort"));
	$file=preg_replace('/[^A-Za-z0-9._-]/','_',$r["name"]).".pem";
	$cmd="ssh -i $file ".($port>0&&$port!=22?"-p $port ":"").$r["name"]."@$host";
	return "<div class='ak-card sshk-sec'><div class='sshk-sec-h'><div><b>{sshk_file_title}</b><span>{sshk_file_sub}</span></div>"
		."<a class='ak-btn ak-btn-primary' href='/$page?public-key-user-download=$enc'><i class='fas fa-download'></i>{sshk_download}</a></div>"
		."<div class='ak-banner ak-warn'><i class='fas fa-triangle-exclamation'></i><div class='ak-banner-m'><b>{sshk_secret_title}</b> {sshk_secret_text}</div></div>"
		."<div class='sshk-k'>{sshk_example}</div>"
		."<div class='sshk-cmd'><code id='sshk-cmd'>".sshk_esc($cmd)."</code><button type='button' class='sshk-copy' aria-label=\"{sshk_copy_command}\" OnClick=\"SshkCopy('sshk-cmd',this)\"><i class='fas fa-copy'></i>{sshk_copy}</button></div>"
		."</div>";
}

function sshk_detail_lock(array $r):string{
	return "<div class='ak-card sshk-sec'><div class='sshk-sec-h'><div><b>{sshk_lock_title}</b><span>{sshk_lock_sub}</span></div></div>"
		."<div class='sshk-pub'><code id='sshk-pub' class='sshk-pub-c'>".sshk_esc($r["pub"])."</code>"
		."<button type='button' class='ak-link' OnClick=\"var c=document.getElementById('sshk-pub');c.classList.toggle('sshk-open');this.textContent=c.classList.contains('sshk-open')?this.getAttribute('data-l'):this.getAttribute('data-s');\" data-s=\"{sshk_show_all}\" data-l=\"{sshk_show_less}\">{sshk_show_all}</button>"
		."<button type='button' class='ak-btn ak-btn-sm' aria-label=\"{sshk_copy_key}\" OnClick=\"SshkCopy('sshk-pub',this)\"><i class='fas fa-copy'></i>{sshk_copy}</button></div>"
		."<p class='sshk-help'><i class='fas fa-circle-info'></i>{sshk_lock_help}</p></div>";
}

function sshk_detail_replace(array $r):string{
	$tpl=new template_admin();
	$confirm=sshk_tr("sshk_replace_confirm",sshk_esc($r["name"]));
	$js=sshk_generate_js($r["name"],$confirm);
	return "<div class='ak-card sshk-sec sshk-danger'><div class='sshk-sec-h'><div><b>{sshk_replace_title}</b><span class='sshk-help'>{sshk_replace_text}</span></div>"
		."<button type='button' class='ak-btn sshk-btn-red' OnClick=\"$js\"><i class='fas fa-rotate'></i>{sshk_replace}</button></div></div>";
}

function sshk_js():string{
	$ok=json_encode(sshk_plain("{sshk_copied}"));
	return "window.SshkCopy=function(id,btn){var el=document.getElementById(id);if(!el){return;}var t=el.textContent;"
		."var done=function(){var o=btn.innerHTML;btn.innerHTML=$ok;setTimeout(function(){btn.innerHTML=o;},1500);};"
		."if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(t).then(done,function(){});return;}"
		."var a=document.createElement('textarea');a.value=t;a.style.position='fixed';a.style.opacity='0';document.body.appendChild(a);a.select();"
		."try{document.execCommand('copy');done();}catch(e){}document.body.removeChild(a);};";
}

function sshk_plain($t):string{
	$tpl=new template_admin();
	return html_entity_decode($tpl->_ENGINE_parse_body($t),ENT_QUOTES,"UTF-8");
}

function sshk_style():string{
	if(isset($GLOBALS["SSHK_STYLE"])){return "";}
	$GLOBALS["SSHK_STYLE"]=true;
	$A="#0d9488";
	if(function_exists("brand_ui_accents") && function_exists("brand_palette")){ $a=brand_ui_accents(brand_palette()); if(!empty($a["accent"])){$A=$a["accent"];} }
	return "<style>
.sshk{color:#0f172a;font-size:14px;padding:8px 2px}
.sshk *{box-sizing:border-box}
.sshk p{margin:0}
.sshk-banner{background:#eff6ff;border:1px solid #dbeafe;border-radius:12px;padding:18px 20px;margin-bottom:18px}
.sshk-banner h3{font-size:16px;font-weight:600;margin:0 0 6px;color:#0f172a}
.sshk-banner p{font-size:14px;color:#334155;line-height:1.55;margin-bottom:12px}
.sshk-steps{list-style:none;margin:14px 0 0;padding:12px 0 0;border-top:1px solid #dbeafe;display:flex;flex-wrap:wrap;gap:8px 22px;font-size:13px;color:#1e3a8a;font-weight:500}
.sshk-steps li{display:flex;align-items:center;gap:8px}
.sshk-steps li>span{width:22px;height:22px;border-radius:50%;background:#1d4ed8;color:#fff;font-size:13px;font-weight:700;display:inline-flex;align-items:center;justify-content:center}
.sshk-steps em{font-style:normal;color:#475569;font-weight:400}
.sshk-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px}
.sshk-sum{display:flex;align-items:center;gap:8px;font-size:14px;color:#475569}
.sshk-sum b{color:#0f172a}
.sshk-card{overflow:hidden}
.sshk-tbl{width:100%;border-collapse:collapse}
.sshk-tbl th{font-size:13px;font-weight:600;color:#475569;text-align:left;padding:12px 16px;background:#f8fafc;border-bottom:1px solid #e2e8f0}
.sshk-tbl td{padding:12px 16px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.sshk-tbl tr:last-child td{border-bottom:0}
.sshk-tbl .sshk-end{text-align:right}
.sshk-id{display:flex;align-items:center;gap:12px}
.sshk-name{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.sshk-name a{font-size:15px;font-weight:600;color:#0f172a;text-decoration:none}
.sshk-name a:hover{color:$A;text-decoration:underline}
.sshk-sub{font-size:13px;color:#64748b;margin-top:2px}
.sshk-warn{font-size:13px;color:#865300;margin-top:4px;display:flex;align-items:center;gap:6px}
.sshk-acts{display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:wrap}
.sshk-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;border-top:1px solid #f1f5f9;font-size:13px;color:#64748b}
.sshk-empty{padding:40px 20px}
.sshk-empty span,.sshk-invite span{color:#64748b;font-size:14px;max-width:460px}
.sshk-detail{padding-top:2px}
.sshk-dh{margin-bottom:14px}
.sshk-dh h3{font-size:16px;font-weight:600;margin:0 0 4px}
.sshk-dh p{font-size:13px;color:#64748b}
.sshk-invite{padding:36px 24px;gap:10px}
.sshk-invite>i,.sshk-invite>svg{color:#1d4ed8}
.sshk-state{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 18px;margin-bottom:14px;background:#f8fafc}
.sshk-state-l{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:500;margin-bottom:6px}
.sshk-state p{font-size:13px;color:#64748b;line-height:1.5}
.sshk-sw-l{display:flex;align-items:center;gap:10px;font-weight:600;font-size:14px;margin:0;white-space:nowrap}
.sshk-sec{padding:18px 20px;margin-bottom:14px}
.sshk-sec-h{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:12px}
.sshk-sec-h b{display:block;font-size:15px;font-weight:600}
.sshk-sec-h span{display:block;font-size:13px;color:#64748b;margin-top:2px}
.sshk-k{font-size:13px;font-weight:500;margin:14px 0 6px}
.sshk-cmd{display:flex;align-items:center;gap:10px;background:#0f172a;border-radius:8px;padding:10px 12px}
.sshk-cmd code{flex:1;min-width:0;color:#e2e8f0;background:none;border:0;padding:0;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;overflow-x:auto;white-space:nowrap}
.sshk-copy{display:inline-flex;align-items:center;gap:6px;border:1px solid #334155;background:#1e293b;color:#e2e8f0;border-radius:6px;padding:4px 10px;font-size:13px;cursor:pointer}
.sshk-copy:hover{background:#334155}
.sshk-pub{display:flex;align-items:center;gap:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px}
.sshk-pub-c{flex:1;min-width:0;background:none;border:0;padding:0;color:#0f172a;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sshk-pub-c.sshk-open{white-space:normal;word-break:break-all}
.sshk-help{font-size:13px;color:#64748b;line-height:1.5;margin-top:10px!important;display:flex;gap:6px;align-items:flex-start}
.sshk-danger{border-color:#fecaca}
.sshk .sshk-btn-red{border-color:#fecaca;color:#ba1a1a!important;background:#fff;white-space:nowrap}
.sshk .sshk-btn-red:hover{background:#fef2f2}
.sshk-note{margin-top:10px;text-align:left}
.sshk .ak-btn:focus-visible,.sshk .ak-sw:focus-visible,.sshk-copy:focus-visible,.sshk .ak-link:focus-visible{outline:2px solid $A;outline-offset:2px}
@media(max-width:1200px){.sshk-state{flex-direction:column;align-items:flex-start}}
</style>";
}
function public_key_group_enable():bool{
    $tpl=new template_admin();
    if(!sshd_is_admin()){$tpl->js_no_privileges();return false;}
    $sshd=new openssh();
    $curgrou=trim((string)$_GET["public-key-group-enable"]);
    if($curgrou===""){$tpl->js_error("Unknown system group");return false;}
    if(sshdsu_group_is_meta($curgrou)){$tpl->js_error("{systemusers_meta_locked}");return false;}
    $aiv=array();
    foreach ($sshd->AllowGroups as $grp){
        $aiv[trim((string)$grp)]=true;
    }
    $enabled=!isset($aiv[$curgrou]);
    $text=($enabled?"Enable":"Disable")." $curgrou from the Allowed SSH group";
    // Le demon ecrit SSHAllowGroups et SSHAllowGroupsForGo ensemble puis reconstruit sshd
    // (423 quand Artica Meta tient ces cles).
    $results=api_result($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/group/ssh",
        array("name"=>$curgrou,"enabled"=>$enabled)),"Unable to change the SSH access of the group $curgrou");
    if(!$results["ok"]){
        admin_tracks("$text failed: {$results["error"]}");
        $tpl->js_error(htmlspecialchars($results["error"],ENT_QUOTES|ENT_SUBSTITUTE,"ISO-8859-1"));
        return false;
    }
    admin_tracks($text);
    header("content-type: application/x-javascript");
    echo "LoadAjax('openssh-status-config','fw.sshd.php?openssh-status-config=yes');";
    return true;
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
	if(sshd_meta_key_held("SSHDBanner")){
		// Texte tenu par une politique Meta « SSH » : montre, jamais modifiable ici.
		echo sshd_meta_banner($tpl)."<pre style='white-space:pre-wrap;max-height:420px;overflow:auto'>".sshd_meta_h($SSHDBanner)."</pre>";
		return;
	}
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
    if(!sshd_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    if(sshd_meta_key_held("SSHDBanner")){return sshd_meta_refuse_post();}
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
    if(sshd_meta_key_held("SSHDDenyCountries")){echo sshd_meta_banner();}
    echo "<div id='limit-countries-table'></div><script>LoadAjax('limit-countries-table','$page?limit-countries-table=yes');</script>";
}
function limit_countries_deny_all():bool{
    $page=CurrentPageName();
    if(!sshd_is_admin()){return (new template_admin())->js_no_privileges();}
    if(sshd_meta_key_held("SSHDDenyCountries")){return sshd_meta_refuse_js();}
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
    if(!sshd_is_admin()){return (new template_admin())->js_no_privileges();}
    if(sshd_meta_key_held("SSHDDenyCountries")){return sshd_meta_refuse_js();}

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
    if(!sshd_is_admin()){return (new template_admin())->js_no_privileges();}
    if(sshd_meta_key_held("SSHDDenyCountries")){return sshd_meta_refuse_js();}
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
    $page               = CurrentPageName();
    $tpl                = new template_admin();
    $GEOIPCOUNTRIES     = GEO_IP_COUNTRIES_LIST();
    if(!is_array($GEOIPCOUNTRIES)){$GEOIPCOUNTRIES=array();}
    // unserialize() renvoie false sur une valeur absente ou corrompue.
    $SSHDDenyCountries  = unserialize(base64_decode((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDDenyCountries")));
    if(!is_array($SSHDDenyCountries)){$SSHDDenyCountries=array();}

    $rows=array();
    foreach ($GEOIPCOUNTRIES as $CountryCode=>$Country){
        $aclid=md5($CountryCode);
        $Enabled=isset($SSHDDenyCountries[$CountryCode])?1:0;
        $cc=preg_replace('/[^A-Za-z0-9_-]/','',(string)$CountryCode);
        $rows[]="<tr id='$aclid'>"
            ."<td class='center' style='width:1%'><span class='awc-ico'><i class='far fa-globe-europe'></i></span></td>"
            ."<td><span class='awc-name'>".htmlspecialchars((string)$Country)."</span>"
                ."<div class='awc-meta awc-mono'>".htmlspecialchars($cc)."</div></td>"
            ."<td class='center' style='width:1%'>"
                .$tpl->icon_check($Enabled,"Loadjs('$page?limit-access-country=$cc&md=$aclid')",null,"AsSystemAdministrator")."</td>"
            ."</tr>";
    }
    $c=count($rows);

    $html=array();
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-bottom:10px'>";
    $html[]="<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('$page?limit-countries-deny-all=yes');\"><i class='far fa-check-double'></i> {deny_all} </label>";
    $html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?limit-countries-allow-all=yes');\"><i class='far fa-check-double'></i> {allow_all} </label>";
    // Le <div class='ibox-content'> ouvert ici n'etait JAMAIS ferme.
    $html[]="</div>";

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead><tr>";
    $html[]="<th class='center' style='width:1%'>&nbsp;</th>";
    $html[]="<th>{countries}</th>";
    $html[]="<th class='center' style='width:1%' nowrap>{deny}</th>";
    $html[]="</tr></thead><tbody id='sshd-countries-rows'>";
    if($c==0){
        $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c {items}</span>";
    $html[]="<span class='awc-pg' id='sshd-countries-pager'></span>";
    $html[]="</div></div></div>";

    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js(50,"sshd-countries-rows","sshd-countries-pager");
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
	$q->QUERY_SQL_PARAMS("DELETE FROM sshd_allowusers WHERE pattern=?",array((string)$_GET["limit-access-delete"]));
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return false;}
	// md is a row id echoed into a jQuery selector: whitelist it.
	$md=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["md"] ?? ""));
	echo "$('#$md').remove();\n";
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
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks_post("Created access rule from the OpenSSH server");
}
function limit_access_table(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	$q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
	$results=$q->QUERY_SQL("SELECT * FROM sshd_allowusers ORDER BY pattern");
	if(!is_array($results)){$results=array();}

	$rows=array();
	foreach ($results as $ligne){
		$pattern=(string)($ligne["pattern"] ?? "");
		$aclid=md5($pattern);
		$patternenc=rawurlencode($pattern);
		$rows[]="<tr id='$aclid'>"
			."<td class='center' style='width:1%'><span class='awc-ico'><i class='fa fa-desktop'></i></span></td>"
			."<td><span class='awc-name awc-mono'>".htmlspecialchars($pattern)."</span></td>"
			."<td class='center' style='width:1%'><span class='awc-acts'>"
				.$tpl->icon_delete("Loadjs('$page?limit-access-delete=$patternenc&md=$aclid')","AsSystemAdministrator")
			."</span></td>"
			."</tr>";
	}
	$c=count($rows);

	$html=array();
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-bottom:10px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?limit-access-new-js=yes');\"><i class='fa fa-plus'></i> {new_member} </label>";
	// Le <div class='ibox-content'> ouvert ici n'etait JAMAIS ferme.
	$html[]="</div>";

	// Cet identifiant "table-sshd-limitaccess" etait aussi celui de la table de
	// public_key_userlist().
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
	$html[]="<thead><tr>";
	$html[]="<th class='center' style='width:1%'>&nbsp;</th>";
	$html[]="<th>{members}</th>";
	$html[]="<th class='center' style='width:1%'>&nbsp;</th>";
	$html[]="</tr></thead><tbody id='sshd-limit-rows'>";
	if($c==0){
		$html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='sshd-limit-pager'></span>";
	$html[]="</div></div></div>";

	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.$script
		.awc_pager_js(50,"sshd-limit-rows","sshd-limit-pager");

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
    echo $tpl->table_form_compile(2);
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
    $form[]=sshd_meta_fld($tpl,"field_numeric","SSHDListenPort","{listen_port}",$SSHDListenPort);
    $jsrestart=main_reload();
    echo $tpl->form_outside(null, @implode("\n", $form),null,"{apply}",
        "BootstrapDialog1.close();LoadAjax('openssh-status-config','$page?openssh-status-config=yes');$jsrestart",
        "AsSystemAdministrator");
    return true;
}
function SSHDInterface_Save():bool{
    $tpl                    = new template_admin();
    if(!sshd_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $tpl->CLEAN_POST();
    // Port tenu par une politique Meta « SSH » : montre en lecture seule (autre nom), jamais enregistre ici.
    if(isset($_POST["SSHDListenPort"]) && sshd_meta_key_held("SSHDListenPort")){return sshd_meta_refuse_post();}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDInterface",$_POST["SSHDInterface"]);
    if(isset($_POST["SSHDListenPort"])){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHDListenPort",$_POST["SSHDListenPort"]);}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure-restart");
    return admin_tracks("Save OpenSSH interface settings");
}
function UseWelcome_popup():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $sshd                   = new openssh();
    $PrintMotd              = $sshd->main_array["PrintMotd"];


    if(sshd_meta_key_held("PrintMotd")){
        echo sshd_meta_banner($tpl).$tpl->_ENGINE_parse_body($tpl->div_explain("{welcome_banner}||{welcome_banner_explain}")
            ."<p><strong>".(intval($PrintMotd)==1?"{enabled}":"{disabled}")."</strong> &mdash; <i class='fa-solid fa-lock'></i>&nbsp;{sshd_meta_locked}</p>");
        return true;
    }
    $html[]=$tpl->BigCircleCheckbox("PrintMotd","{welcome_banner}","{welcome_banner_explain}",$PrintMotd,"dialogInstance1.close();LoadAjax('openssh-status-config','$page?openssh-status-config=yes');","AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function UseWelcome_save():bool{
    $tpl                    = new template_admin();
    if(!sshd_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    if(sshd_meta_key_held("PrintMotd")){return sshd_meta_refuse_raw();}
    $tpl->CLEAN_POST();
    $PrintMotd              = intval($_POST["PrintMotd"]);
    $sshd                   = new openssh();
    $sshd->main_array["PrintMotd"]=$PrintMotd;
    if(!$sshd->SaveInterface()){echo $sshd->LastError();return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/restart");
    return admin_tracks("Save OpenSSH Welcome message to $PrintMotd");
}
function MaxStartups_popup():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $sshd                   = new openssh();
    $MaxStartups            = $sshd->main_array["MaxStartups"];

    $tb=explode(":",$MaxStartups);
    if(sshd_meta_key_held("MaxStartups")){
        echo sshd_meta_banner($tpl).$tpl->_ENGINE_parse_body($tpl->div_explain("{MaxStartups}||{OPenSSHMAxStartupExplain}")
            ."<p><strong>".sshd_meta_h($MaxStartups)."</strong> &mdash; <i class='fa-solid fa-lock'></i>&nbsp;{sshd_meta_locked}</p>");
        return true;
    }

    $form[]=$tpl->field_numeric("MaxStartups","{OPenSSHMAxStartupExplain1}",$tb[0]);
    $form[]=$tpl->field_numeric("rate","{rate}",$tb[1]);
    $form[]=$tpl->field_numeric("full","{full2}",$tb[2]);
    $jsrestart=main_reload();
    echo $tpl->form_outside("{OPenSSHMAxStartupExplain}", @implode("\n", $form),null,"{apply}",
        "BootstrapDialog1.close();LoadAjax('openssh-status-config','$page?openssh-status-config=yes');$jsrestart",
        "AsSystemAdministrator");
    return true;
}
function MaxStartups_save():bool{
    $tpl                    = new template_admin();
    if(!sshd_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    // Le formulaire « general » (settings-popup=general) poste aussi MaxStartups, sans rate/full,
    // avec LogLevel et SSHDNotifyConnected : il passe par save_config() (valeur telle quelle).
    if(!isset($_POST["rate"]) && !isset($_POST["full"])){return save_config();}
    if(sshd_meta_key_held("MaxStartups")){return sshd_meta_refuse_post();}
    $tpl->CLEAN_POST();
    $MaxStartups            = $_POST["MaxStartups"].":".$_POST["rate"].":".$_POST["full"];
    $sshd                   = new openssh();
    $sshd->main_array["MaxStartups"]=$MaxStartups;
    if(!$sshd->SaveInterface()){echo $sshd->LastError();return false;}
    return admin_tracks("Save OpenSSH MaxStartups settings");
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



    $form[]=$tpl->field_hidden("sshd-settings-form","general");
    $form[]=sshd_meta_fld($tpl,"field_text","MaxStartups","{MaxStartups}",$sshd->main_array["MaxStartups"]);
    $form[]=$tpl->field_section("{events}");
    if(!$FAIL2BAN){
        $form[]=sshd_meta_fld_hash($tpl,$Loglevels, "LogLevel", "{log_level}", $sshd->main_array["LogLevel"]);
    }
    $form[]=sshd_meta_fld($tpl,"field_checkbox","SSHDNotifyConnected","{write_event_on_success}",$SSHDNotifyConnected,false,"{SSHDNotifyConnected}");

    $jsrestart=main_reload();
    echo $tpl->form_outside(null, @implode("\n", $form),null,"{apply}",
        "BootstrapDialog1.close();LoadAjax('openssh-status-config','$page?openssh-status-config=yes');$jsrestart",
        "AsSystemAdministrator");
    return true;
}

// ---- SSH-mTLS : ecoute loopback de sshd pour le tunnel ssh-mtls ----
// Le daemon pose 127.0.0.1:885 (tunnel) en plus des interfaces (compatibilite) ou seule
// (strict), recharge sshd et VERIFIE l'ecoute : sans reponse, il remet les
// anciennes valeurs. Strict implique la compatibilite.

/** L'etat lu sur /ssh/mtls/state, ou null. */
function sshd_mtls_state(){
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/mtls/state"));
    if(!is_object($json) || empty($json->Status) || !isset($json->data) || !is_object($json->data)){return null;}
    return $json->data;
}

function sshd_mtls_value():string{
    $st=sshd_mtls_state();
    if($st===null){return sshd_chip("off","{error}","fas fa-circle-xmark");}
    if(empty($st->compat)){return sshd_chip("off","{sshd_off}","fas fa-minus");}
    if(empty($st->listening)){return sshd_chip("warn","{sshd_mtls_not_listening885}","fas fa-triangle-exclamation");}
    if(!empty($st->strict)){return sshd_chip("on","{sshd_mtls_strict}","fas fa-lock");}
    return sshd_chip("on","{sshd_mtls_compat}","fas fa-check");
}

function sshmtls_js():bool{
    if(!sshd_is_admin()){$tpl=new template_admin();return $tpl->js_no_privileges();}
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("SSH-mTLS","$page?sshmtls-popup=yes",700);
}

function sshmtls_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $st=sshd_mtls_state();
    if($st===null){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error_backend}"));
        return false;
    }
    // Apres chaque bascule : erreur = l'interrupteur revient en place (sans
    // nouveau POST), puis la boite et la carte sont relues (strict entraine la
    // compatibilite, et inversement l'arret de la compatibilite arrete strict).
    $after="if(String(resp).indexOf('jserror:')===0){ctrl.input.checked=!ctrl.input.checked;"
        ."swal({title:'Oops...',text:'<strong>'+String(resp).substring(8)+'</strong>',html:true,type:'error'});}"
        ."LoadAjaxSilent('sshmtls-popup-body','$page?sshmtls-popup=yes&inner=yes');"
        ."if(document.getElementById('openssh-status-config')){LoadAjaxSilent('openssh-status-config','$page?openssh-status-config=yes');}";
    $html=$tpl->BigCircleCheckbox("SSHDMTLSCompat","{sshd_mtls_compat}","{sshd_mtls_compat_explain885}",empty($st->compat)?0:1,$after)
        .$tpl->BigCircleCheckbox("SSHDMTLSStrict","{sshd_mtls_strict}","{sshd_mtls_strict_info885}",empty($st->strict)?0:1,$after);
    if(isset($_GET["inner"])){echo $html;return true;}
    echo "<div id='sshmtls-popup-body'>$html</div>";
    return true;
}

function sshmtls_save(string $which):bool{
    $tpl=new template_admin();
    if(!sshd_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $st=sshd_mtls_state();
    if($st===null){echo $tpl->post_error("{error_backend}");return false;}
    $compat=!empty($st->compat);
    $strict=!empty($st->strict);
    if($which=="compat"){
        $compat=intval($_POST["SSHDMTLSCompat"])==1;
        if(!$compat){$strict=false;}
    }else{
        $strict=intval($_POST["SSHDMTLSStrict"])==1;
        if($strict){$compat=true;}
    }
    $raw=(string)$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/ssh/mtls/settings",array("compat"=>$compat,"strict"=>$strict));
    $json=json_decode($raw);
    if(!is_object($json)){echo $tpl->post_error("{error_backend}");return false;}
    if(empty($json->Status)){
        echo $tpl->post_error(isset($json->Error) && trim((string)$json->Error)<>""?(string)$json->Error:"{error_backend}");
        return false;
    }
    return admin_tracks("OpenSSH: SSH-mTLS compatibility=".intval($compat)." strict=".intval($strict));
}

function gssapi_popup():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $OpenSSHEnableGSSAPI    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenSSHEnableGSSAPI"));
    if(sshd_meta_key_held("OpenSSHEnableGSSAPI")){
        echo sshd_meta_banner($tpl).$tpl->_ENGINE_parse_body($tpl->div_explain("GSSAPI||{GSSAPI_EXPLAIN}")
            ."<p><strong>".($OpenSSHEnableGSSAPI==1?"{enabled}":"{disabled}")."</strong> &mdash; <i class='fa-solid fa-lock'></i>&nbsp;{sshd_meta_locked}</p>");
        return true;
    }

    $html[]=$tpl->BigCircleCheckbox("OpenSSHEnableGSSAPI",
        "GSSAPI",
        "{GSSAPI_EXPLAIN}",$OpenSSHEnableGSSAPI,
        "dialogInstance2.close();LoadAjax('openssh-status-config','$page?openssh-status-config=yes');");

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function gssapi_save():bool{
    if(!sshd_is_admin()){echo (new template_admin())->post_error("{ERROR_NO_PRIVS2}");return false;}
    if(sshd_meta_key_held("OpenSSHEnableGSSAPI")){return sshd_meta_refuse_raw();}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenSSHEnableGSSAPI", intval($_POST["OpenSSHEnableGSSAPI"]));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks("OpenSSH: Save GSSAPI to {$_POST["OpenSSHEnableGSSAPI"]}");
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
    $form[]=$tpl->field_hidden("sshd-settings-form","auth");
    $form[]=sshd_meta_fld($tpl,"field_checkbox","HostbasedAuthentication","{HostbasedAuthentication}",$sshd->main_array["HostbasedAuthentication"],false,"");
    $form[]=sshd_meta_fld($tpl,"field_checkbox","PermitRootLogin","{PermitRootLogin}",$sshd->main_array["PermitRootLogin"],false,"{PermitRootLogin_text}");




    $form[]=sshd_meta_fld($tpl,"field_checkbox","SSHDUsePAM","{UsePAM}",$SSHDUsePAM,false,"{UsePAM_TEXT}");
    $form[]=sshd_meta_fld($tpl,"field_checkbox","ChallengeResponseAuthentication","{ChallengeResponseAuthentication}",$sshd->main_array["ChallengeResponseAuthentication"],false,"{ChallengeResponseAuthentication_text}");

    $form[]=sshd_meta_fld($tpl,"field_checkbox","PubkeyAuthentication","{PubkeyAuthentication}",$sshd->main_array["PubkeyAuthentication"],false,"{PubkeyAuthentication_text}");
    $form[]=$tpl->field_section("{PasswordAuthentication}");
    $form[]=sshd_meta_fld($tpl,"field_checkbox","PasswordAuthentication","{enable}",$sshd->main_array["PasswordAuthentication"],false,"{PasswordAuthentication_text}");
    $form[]=sshd_meta_fld($tpl,"field_text","PasswordAuthenticationUsers","{exceptipaddresses} ({ifdisabled})",$PasswordAuthenticationUsers,false,"{PasswordAuthentication_text}");

    $jsrestart="Loadjs('$page?reload-compile=yes');";
    echo $tpl->form_outside(null, @implode("\n", $form),null,"{apply}",
        "BootstrapDialog1.close();LoadAjax('openssh-status-config','$page?openssh-status-config=yes');$jsrestart","AsSystemAdministrator");
    return true;
}
function main_limits():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $sshd                   = new openssh();

    $form[]=$tpl->field_hidden("sshd-settings-form","limits");
    $form[]=sshd_meta_fld($tpl,"field_checkbox","X11Forwarding","X11 {wccp2_forwarding_method}",$sshd->main_array["X11Forwarding"]);
    $form[]=sshd_meta_fld($tpl,"field_checkbox","PermitTTY","{allow} Terminal",$sshd->main_array["PermitTTY"]);


    $form[]=sshd_meta_fld($tpl,"field_checkbox","IgnoreRhosts","IgnoreRhosts",$sshd->main_array["IgnoreRhosts"]);


    $form[]=sshd_meta_fld($tpl,"field_numeric","ClientAliveInterval","{ClientAliveInterval} ({seconds})",$sshd->main_array["ClientAliveInterval"]);
    $form[]=sshd_meta_fld($tpl,"field_numeric","ClientAliveCountMax","{ClientAliveCountMax} ({attempts})",$sshd->main_array["ClientAliveCountMax"]);



    $form[]=sshd_meta_fld($tpl,"field_numeric","LoginGraceTime","{LoginGraceTime} ({seconds})",$sshd->main_array["LoginGraceTime"],"{LoginGraceTime_text}");
    $form[]=sshd_meta_fld($tpl,"field_numeric","MaxSessions","{MaxSessions}",$sshd->main_array["MaxSessions"],"{MaxSessions_text}");
    $form[]=sshd_meta_fld($tpl,"field_numeric","MaxAuthTries","{MaxAuthTries}",$sshd->main_array["MaxAuthTries"],"{MaxAuthTries_text}");
    $form[]=sshd_meta_fld($tpl,"field_checkbox","StrictModes","{StrictModes}",$sshd->main_array["StrictModes"],false,"{StrictModes_text}");

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
    if(sshd_meta_key_held("SSHDIptables")){
        echo sshd_meta_banner($tpl).$tpl->_ENGINE_parse_body($tpl->div_explain("{firewall_protection}||{sshd_firewall_explain}")
            ."<p><strong>".($SSHDIptables==1?"{enabled}":"{disabled}")."</strong> &mdash; <i class='fa-solid fa-lock'></i>&nbsp;{sshd_meta_locked}</p>");
        return true;
    }

    $form[]=sshd_meta_fld($tpl,"field_checkbox","SSHDIptables","{firewall_protection}",$SSHDIptables);
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

    $SSHDCiphers=sshd_ciphers_default($SSHDCiphers);
    if($SSHDProtocol==null){$SSHDProtocol=2;}


    $form[]=$tpl->field_hidden("sshd-settings-form","protocols");
    $form[]=$tpl->field_section("{protocols}");
    $form[]=$tpl->field_array_hash($SSHDProtocolz, "SSHDProtocol", "{protocol}", $SSHDProtocol);
    $form[]=sshd_meta_fld($tpl,"field_checkbox","PermitTunnel","{PermitTunnel}",$sshd->main_array["PermitTunnel"],false,"{PermitTunnel_text}");
    $form[]=sshd_meta_fld($tpl,"field_checkbox","AllowTcpForwarding","{AllowTcpForwarding}",$sshd->main_array["AllowTcpForwarding"]);
    $form[]=sshd_meta_fld($tpl,"field_text","KexAlgorithms","{KexAlgorithms}",$sshd->main_array["KexAlgorithms"]);
    $form[]=sshd_meta_fld($tpl,"field_checkbox","UseDNS","{UseDNS}",$sshd->main_array["UseDNS"],false,"{UseDNS_sshd_text}");
    $form[]=$tpl->field_section("{certificate}");
    $form[]=sshd_meta_fld($tpl,"field_checkbox","SSHDCiphersEnable","{ssl_ciphers} {enable}",$SSHDCiphersEnable,sshd_meta_key_held("SSHDCiphers")?false:"SSHDCiphers");
    $form[]=sshd_meta_fld($tpl,"field_text","SSHDCiphers","{ssl_ciphers}",$SSHDCiphers);

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
// Onglet Status (maquette stitch_openssh_appliance_dashboard_redesign, 2026-11-01).
// Colonne gauche : la tuile de service partagee (SERVICE_STATUS) et la carte
// Google Authenticator. Colonne droite : bandeaux, exposition, quatre cartes de
// reglages, sessions. Un widget = une fonction, un modele unique sshd_model().
function status():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $m=sshd_model();
    // La tuile partagee porte sa propre CSS : elle ne traverse pas _ENGINE_parse_body().
    $open="<div class='sshd'><div class='sshd-layout'><aside class='sshd-side'><div id='OpenSSHStatus'>";
    $html=array();
    $html[]="</div>".sshd_card_2fa($m)."</aside>";
    $html[]="<div class='sshd-main'>";
    $html[]="<div id='openssh-status-config'>".sshd_config_view_html($m)."</div>";
    $html[]="<div id='sshd-sessions-host'>".sshd_card_sessions($m)."</div>";
    $html[]="</div></div></div>";
    echo sshd_style().$open.sshd_service_tile().$tpl->_ENGINE_parse_body(@implode("\n",$html)).sshd_js($page);
    return true;
}

// ?openssh-status-config=yes : les modales existantes rechargent cette zone.
function status_config():bool{
    $tpl=new template_admin();
    echo sshd_style().$tpl->_ENGINE_parse_body(sshd_config_view_html(sshd_model()));
    return true;
}

// ?sshd-sessions=yes : rafraichissement de la carte des sessions.
function sshd_sessions_host():bool{
    $tpl=new template_admin();
    $m=array("admin"=>sshd_is_admin());
    echo sshd_style().$tpl->_ENGINE_parse_body(sshd_card_sessions($m));
    return true;
}

function sshd_is_admin():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemAdministrator;
}

function sshd_get($key):string{
    return trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO($key));
}

function sshd_esc($s):string{
    $s=htmlspecialchars((string)$s,ENT_QUOTES);
    return str_replace(array("{","}"),array("&#123;","&#125;"),$s);
}

function sshd_model():array{
    $m=array();
    $m["admin"]=sshd_is_admin();
    $m["unmanaged"]=intval(sshd_get("DisableSSHConfig"))==1;

    $m["locked"]=false;
    $lock=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/conf/islock"));
    if(is_object($lock) && property_exists($lock,"Locked")){$m["locked"]=(bool)$lock->Locked;}

    $sshd=new openssh();
    $m["cfg_ok"]=$sshd->ok();
    $m["cfg_err"]=(string)$sshd->LastError();
    $c=$sshd->main_array;
    if(!is_array($c)){$c=array();}
    $m["c"]=$c;
    $m["groups"]=is_array($sshd->AllowGroups)?$sshd->AllowGroups:array();

    $m["pam_2fa"]=intval(sshd_get("PAM_GOOGLE_AUTHENTICATOR_INSTALLED"))==1;
    $m["2fa"]=$m["pam_2fa"] && intval(sshd_get("SSHGoogle2FA"))==1;
    $m["gssapi"]=intval(sshd_get("OpenSSHEnableGSSAPI"));
    $m["usepam"]=intval(sshd_get("SSHDUsePAM"));
    $m["iptables"]=intval(sshd_get("SSHDIptables"));
    $m["rbl"]=sshd_reputation_rules();

    $port=intval(sshd_get("SSHDListenPort"));
    if($port==0){$port=22;}
    $m["port"]=$port;
    $m["ifaces"]=sshd_listen_list($port);

    $m["pwd_except"]=array();
    $exc=sshd_get("PasswordAuthenticationUsers");
    if($exc<>""){
        foreach(preg_split('/[\s,;]+/',$exc) as $e){ if(trim($e)<>""){$m["pwd_except"][]=trim($e);} }
    }

    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $m["allowusers"]=intval($q->COUNT_ROWS("sshd_allowusers"));
    $deny=unserialize(base64_decode(sshd_get("SSHDDenyCountries")));
    $m["countries"]=is_array($deny)?count($deny):0;
    $m["banner_len"]=strlen((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHDBanner"));

    $m["hka"]=sshd_get("HostKeyAlgorithms");
    $m["pka"]=sshd_get("PubkeyAcceptedAlgorithms");
    $paths=array("ssh_host_rsa_key"=>"/etc/ssh/ssh_host_rsa_key",
        "ssh_host_ecdsa_key"=>"/etc/ssh/ssh_host_ecdsa_key",
        "ssh_host_ed25519_key"=>"/etc/ssh/ssh_host_ed25519_key");
    $hk=sshd_get("SSHDHostKey");
    if(!isset($paths[$hk])){$hk="ssh_host_rsa_key";}
    $m["hostkey"]=$paths[$hk];

    $m["ciphers_on"]=intval(sshd_get("SSHDCiphersEnable"))==1;
    $m["ciphers"]=sshd_ciphers_default(sshd_get("SSHDCiphers"));
    return $m;
}

// Noms des regles de reputation actives (liste noire puis liste blanche).
function sshd_reputation_rules():array{
    $rules=array();
    $ids=array(intval(sshd_get("SSHDRBL")));
    if($ids[0]==0){return $rules;}
    $white=intval(sshd_get("SSHDRBLWHITE"));
    if($white>0){$ids[]=$white;}
    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    foreach($ids as $id){
        $l=$q->mysqli_fetch_array("SELECT rulename,enabled FROM rbl_reputations WHERE ID=$id");
        if(!is_array($l) || intval($l["enabled"] ?? 0)<>1){continue;}
        $rules[]=(string)$l["rulename"];
    }
    return $rules;
}

function sshd_listen_list($port):array{
    $out=array();
    $raw=str_replace(" ",",",sshd_get("SSHDInterface"));
    foreach(explode(",",$raw) as $iface){
        $iface=trim($iface);
        if($iface=="" || $iface=="lo"){continue;}
        $out[]="$iface:$port";
    }
    return $out;
}

function sshd_cfg($m,$key):string{
    return isset($m["c"][$key])?trim((string)$m["c"][$key]):"";
}

// ---- Widgets elementaires ----
function sshd_chip($kind,$text,$icon=""):string{
    $ico=$icon<>""?"<span class='sshd-ico'><i class='$icon'></i></span>":"";
    $tone=($kind=="on")?"ok":$kind;
    return "<span class='ak-pill ak-$tone'>$ico$text</span>";
}

function sshd_chip_bool($v):string{
    if(intval($v)==1){return sshd_chip("on","{sshd_on}","fas fa-check");}
    return sshd_chip("off","{sshd_off}","fas fa-minus");
}

// Reglage qui ouvre SSH au reseau : "Allowed" en ambre, "Denied" en vert.
function sshd_chip_risk($v):string{
    if(intval($v)==1){return sshd_chip("warn","{sshd_allowed}","fas fa-triangle-exclamation");}
    return sshd_chip("on","{sshd_denied}","fas fa-check");
}

function sshd_tags($items,$mono=true):string{
    $cls=$mono?"sshd-tag ak-mono":"sshd-tag";
    $h=array();
    foreach($items as $i){ $h[]="<span class='$cls'>".sshd_esc($i)."</span>"; }
    return "<span class='sshd-tags'>".@implode("",$h)."</span>";
}

function sshd_count_items($n,$id=""):string{
    $span=$id<>""?"<span id='$id'>".intval($n)."</span>":intval($n);
    return "<span class='sshd-num'>$span {sshd_items}</span>";
}

function sshd_duration($sec):string{
    $sec=intval($sec);
    if($sec>=3600 && $sec%3600==0){return ($sec/3600)." h";}
    if($sec>=60 && $sec%60==0){return ($sec/60)." min";}
    if($sec>60){return floor($sec/60)." min ".($sec%60)." s";}
    return "$sec s";
}

function sshd_bytes($n):string{
    $n=intval($n);
    if($n<1024){return "$n B";}
    return round($n/1024,1)." KB";
}

function sshd_card_open($icon,$title,$sub,$extra=""):string{
    return "<section class='ak-card sshd-card$extra'><div class='sshd-head'><span class='sshd-head-ico'><i class='$icon'></i></span>"
        ."<h3 class='sshd-title'>$title</h3><span class='sshd-sub'>$sub</span></div><div class='sshd-body'>";
}

function sshd_card_close():string{
    return "</div></section>";
}

// $keys : reglages du catalogue Meta montres par la ligne. Un reglage tenu par une politique Meta « SSH »
// ajoute la pastille « Artica Meta » ; la ligne n'ouvre plus de modale quand tous ses reglages sont tenus.
function sshd_row($m,$label,$value,$js="",$keys=array()):string{
    if(count($keys)>0 && sshd_meta_any_held($keys)){
        $value.=" <span class='ak-tag ak-info' title=\"{sshd_meta_locked}\"><i class='fa-solid fa-lock'></i>&nbsp;Artica Meta</span>";
        if(sshd_meta_all_held($keys)){$js="";}
    }
    if($js<>"" && $m["admin"]){
        return "<div class='sshd-row sshd-click' role='button' tabindex='0' aria-label=\"{sshd_edit}: $label\" OnClick=\"$js\" "
            ."onkeydown=\"if(event.key==='Enter'||event.key===' '){this.click();return false;}\">"
            ."<span class='sshd-label'>$label</span><span class='sshd-value'>$value</span>"
            ."<span class='sshd-pen' aria-hidden='true'><i class='fas fa-pen'></i></span></div>";
    }
    return "<div class='sshd-row'><span class='sshd-label'>$label</span><span class='sshd-value'>$value</span></div>";
}

// ---- Colonne gauche ----
function sshd_service_tile():string{
    $tpl=new template_admin();
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/astatus"));
    if(!is_object($json)){
        return $tpl->widget_rouge("API ERROR",json_last_error_msg());
    }
    if(!property_exists($json,"Status") || !$json->Status){
        return $tpl->widget_rouge("API ERROR",(string)($json->Error ?? ""));
    }
    $ini=new Bs_IniHandler();
    $ini->loadString((string)$json->Info);
    return $tpl->SERVICE_STATUS($ini,"APP_OPENSSH",main_restart());
}

function sshd_card_2fa($m):string{
    if(!$m["2fa"]){return "";}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $new=$tpl->framework_buildjs("/ssh/2fa/qrcode","ssh2fa.restart","ssh2fa.log","progress-sshd-restart",
        "LoadAjax('table-loader-sshd-service','$page?tabs=yes');",null,"{sshd_confirm_2fa_new}","AsSystemAdministrator");
    $h=array();
    $h[]=sshd_card_open("fas fa-key","Google Authenticator","{sshd_2fa_sub}"," sshd-card-2fa");
    $h[]="<div class='sshd-qr' id='sshd-qr-box'>".sshd_qr_placeholder($m)."</div>";
    if($m["admin"]){
        $h[]="<div class='sshd-qr-actions'><button type='button' class='ak-btn' OnClick=\"$new\">"
            ."<span class='sshd-ico'><i class='fas fa-arrows-rotate'></i></span>{generate_a_new_code}</button></div>";
    }
    $h[]="<p class='sshd-note'>{sshd_2fa_warn}</p>";
    $h[]=sshd_card_close();
    return @implode("",$h);
}

// Le QR code est le secret TOTP : il n'est jamais dans la page tant qu'on ne le demande pas.
function sshd_qr_placeholder($m):string{
    $page=CurrentPageName();
    $btn="";
    if($m["admin"]){
        $btn="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"LoadAjaxSilent('sshd-qr-box','$page?sshd-qr=yes');\">"
            ."<span class='sshd-ico'><i class='fas fa-eye'></i></span>{sshd_show_qr}</button>";
    }
    return "<div class='sshd-qr-veil' aria-hidden='true'></div><div class='sshd-qr-over'>$btn</div>";
}

// ?sshd-qr=yes
function sshd_qr_show():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!sshd_is_admin()){echo $tpl->_ENGINE_parse_body("<p class='sshd-note'>{ERROR_NO_PRIVS2}</p>");return false;}
    $png=(string)$GLOBALS["CLASS_SOCKETS"]->REST_API_BINARY("/ssh/2fa/qrcode.png");
    if(strlen($png)==0){
        echo $tpl->_ENGINE_parse_body("<p class='sshd-note sshd-note-err'>{sshd_qr_missing}</p>");
        return false;
    }
    $hide="LoadAjaxSilent('sshd-qr-box','$page?sshd-qr-hide=yes');";
    echo $tpl->_ENGINE_parse_body("<img class='sshd-qr-img' src='data:image/png;base64,".base64_encode($png)."' alt=\"{sshd_qr_alt}\">"
        ."<div class='sshd-qr-actions'><button type='button' class='ak-btn' OnClick=\"$hide\">"
        ."<span class='sshd-ico'><i class='fas fa-eye-slash'></i></span>{sshd_hide_qr}</button></div>");
    return admin_tracks("Displayed the OpenSSH two-factor QR code");
}

// ?sshd-qr-hide=yes
function sshd_qr_hide():bool{
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(sshd_qr_placeholder(array("admin"=>sshd_is_admin())));
    return true;
}

// ---- Colonne droite : zone rechargee par les modales ----
function sshd_config_view_html($m):string{
    $h=array();
    $h[]=sshd_banner_locked($m);
    $h[]=sshd_banner_unmanaged($m);
    // Politique Meta « SSH » : bandeau deja traduit (le nom des politiques remplace %s apres traduction).
    $h[]=sshd_meta_banner();
    if(!$m["cfg_ok"]){
        $h[]=sshd_banner_cfg_error($m);
        return @implode("",$h);
    }
    // Deux colonnes explicites : une grille CSS laissait un trou sous la carte courte.
    $dim=$m["unmanaged"]?" sshd-dim":"";
    $h[]="<div class='sshd-grid$dim'><div class='sshd-col'>";
    $h[]=sshd_card_exposure($m);
    $h[]=sshd_card_auth($m);
    $h[]="</div><div class='sshd-col'>";
    $h[]=sshd_card_general($m);
    $h[]=sshd_card_limits($m);
    $h[]=sshd_card_protocols($m);
    $h[]="</div></div>";
    return @implode("",$h);
}

function sshd_banner($kind,$icon,$title,$text,$btn=""):string{
    return "<div class='ak-banner ak-$kind sshd-banner' role='status'><span class='sshd-banner-ico'><i class='$icon'></i></span>"
        ."<div class='ak-banner-m'><strong>$title</strong> <span class='sshd-banner-sep'>&mdash;</span> $text</div>$btn</div>";
}

function sshd_banner_locked($m):string{
    if(!$m["locked"]){return "";}
    $page=CurrentPageName();
    $btn="";
    if($m["admin"]){
        $btn="<button type='button' class='ak-btn' OnClick=\"Loadjs('$page?lock-file-js=0');\">"
            ."<span class='sshd-ico'><i class='fas fa-lock-open'></i></span>{sshd_unlock}</button>";
    }
    return sshd_banner("info","fas fa-lock","{sshd_locked}","{configuration_islocked}",$btn);
}

function sshd_banner_unmanaged($m):string{
    if(!$m["unmanaged"]){return "";}
    $page=CurrentPageName();
    $btn="";
    if($m["admin"]){
        $btn="<button type='button' class='ak-btn' OnClick=\"Loadjs('$page?disable-js=yes');\">{sshd_change}</button>";
    }
    return sshd_banner("warn","fas fa-triangle-exclamation","{DenyHaproxyConf}","{sshd_unmanaged_text}",$btn);
}

function sshd_banner_cfg_error($m):string{
    return sshd_banner("err","fas fa-circle-xmark","{sshd_cfg_error}",sshd_esc($m["cfg_err"]));
}

function sshd_card_exposure($m):string{
    $page=CurrentPageName();
    $h=array();
    $h[]=sshd_card_open("fas fa-eye","{sshd_exposure}","{sshd_exposure_sub}"," sshd-card-exposure");
    $h[]=sshd_row($m,"{PermitRootLogin}",sshd_chip_risk(sshd_cfg($m,"PermitRootLogin")),"Loadjs('$page?settings-js=yes&section=auth')",array("PermitRootLogin"));
    $h[]=sshd_row($m,"{PasswordAuthentication}",sshd_password_value($m),"Loadjs('$page?settings-js=yes&section=auth')",array("PasswordAuthentication","PasswordAuthenticationUsers"));
    if($m["iptables"]==1){
        $fw=sshd_chip("on","{sshd_on}","fas fa-check");
    }else{
        $fw=sshd_chip("warn","{sshd_off}","fas fa-triangle-exclamation");
    }
    $h[]=sshd_row($m,"{firewall_protection}",$fw,"Loadjs('$page?settings-js=yes&section=firewall')",array("SSHDIptables"));
    $h[]=sshd_row($m,"{use_reput_service}",sshd_reputation_value($m),"Loadjs('$page?reputation-js=yes')");
    if($m["pam_2fa"]){
        $h[]=sshd_row($m,"{PAM_GOOGLE_AUTHENTICATOR}",sshd_chip_bool($m["2fa"]?1:0),"Loadjs('$page?settings-js=yes&section=auth')");
    }
    $h[]=sshd_row($m,"{listen_interface}",sshd_listen_value($m),"Loadjs('$page?settings-js=yes&section=interface')",array("SSHDListenPort","SSHDInterface"));
    $h[]=sshd_row($m,"SSH-mTLS",sshd_mtls_value(),"Loadjs('$page?sshmtls-js=yes')");
    $h[]=sshd_card_close();
    return @implode("",$h);
}

function sshd_password_value($m):string{
    if(intval(sshd_cfg($m,"PasswordAuthentication"))==1){return sshd_chip_risk(1);}
    $n=count($m["pwd_except"]);
    if($n==0){return sshd_chip_risk(0);}
    return "<span title=\"".sshd_esc(@implode(", ",$m["pwd_except"]))."\">"
        .sshd_chip("on","{sshd_denied_except} $n","fas fa-check")."</span>";
}

function sshd_reputation_value($m):string{
    if(count($m["rbl"])==0){return sshd_chip("off","{sshd_off}","fas fa-minus");}
    $txt=sshd_esc(@implode(" / ",$m["rbl"]));
    return "<span class='ak-pill ak-ok sshd-chip-long' title=\"$txt\"><span class='sshd-ico'><i class='fas fa-check'></i></span>"
        ."<span class='sshd-ell'>$txt</span></span>";
}

function sshd_listen_value($m):string{
    if(count($m["ifaces"])==0){return "<span class='sshd-num'>{sshd_all_interfaces} :{$m["port"]}</span>";}
    return sshd_tags($m["ifaces"]);
}

function sshd_maxstartups_value($m):string{
    $raw=sshd_cfg($m,"MaxStartups");
    $p=explode(":",$raw);
    if(count($p)<>3){return "<span class='sshd-num'>".sshd_esc($raw)."</span>";}
    return "<span class='sshd-num sshd-wrap'>".intval($p[0])." {sshd_ms_free}, {sshd_ms_drop} ".intval($p[1])."&nbsp;%, {sshd_ms_refuse} ".intval($p[2])."</span>";
}

function sshd_card_general($m):string{
    $page=CurrentPageName();
    $h=array();
    $h[]=sshd_card_open("fas fa-sliders","{sshd_general}","{sshd_general_sub}");
    $h[]=sshd_row($m,"{DenyHaproxyConf}",sshd_chip_bool($m["unmanaged"]?1:0),"Loadjs('$page?disable-js=yes')");
    $h[]=sshd_row($m,"{MaxStartups}",sshd_maxstartups_value($m),"Loadjs('$page?MaxStartups-js=yes')",array("MaxStartups"));
    $h[]=sshd_row($m,"{UseBanner}",sshd_chip_bool(sshd_cfg($m,"Banner")),"Loadjs('$page?UseBanner-js=yes')",array("Banner"));
    $h[]=sshd_row($m,"{welcome_banner}",sshd_chip_bool(sshd_cfg($m,"PrintMotd")),"Loadjs('$page?UseWelcome-js=yes')",array("PrintMotd"));
    $ban=$m["banner_len"]>0?"<span class='sshd-num'>".sshd_bytes($m["banner_len"])."</span>":sshd_chip("off","{sshd_empty}");
    $h[]=sshd_row($m,"{sshd_banner_text}",$ban,"Loadjs('$page?banner-js=yes')",array("SSHDBanner"));
    $h[]=sshd_card_close();
    return @implode("",$h);
}

function sshd_algos_value($v):string{
    if($v==""){return sshd_chip("off","{defaults}");}
    return sshd_tags(preg_split('/[\s,]+/',$v,-1,PREG_SPLIT_NO_EMPTY));
}

function sshd_card_auth($m):string{
    $page=CurrentPageName();
    $auth="Loadjs('$page?settings-js=yes&section=auth')";
    $h=array();
    $h[]=sshd_card_open("fas fa-user-lock","{authentication}","{sshd_auth_sub}");
    $h[]=sshd_row($m,"{HostbasedAuthentication}",sshd_chip_bool(sshd_cfg($m,"HostbasedAuthentication")),$auth,array("HostbasedAuthentication"));
    $h[]=sshd_row($m,"GSSApi",sshd_chip_bool($m["gssapi"]),"Loadjs('$page?gssapi-js=yes')",array("OpenSSHEnableGSSAPI"));
    $h[]=sshd_row($m,"{UsePAM}",sshd_chip_bool($m["usepam"]),$auth,array("SSHDUsePAM"));
    $h[]=sshd_row($m,"{ChallengeResponseAuthentication}",sshd_chip_bool(sshd_cfg($m,"ChallengeResponseAuthentication")),$auth,array("ChallengeResponseAuthentication"));
    $h[]=sshd_row($m,"{PubkeyAuthentication}",sshd_chip_bool(sshd_cfg($m,"PubkeyAuthentication")),$auth,array("PubkeyAuthentication"));
    $groups=count($m["groups"])>0?sshd_tags($m["groups"],false):sshd_chip("off","{sshd_none}");
    $h[]=sshd_row($m,"{AllowOnlyGroups}",$groups,"Loadjs('$page?groups-js=yes')");
    $h[]=sshd_row($m,"{limit_access}",sshd_count_items($m["allowusers"]),"Loadjs('$page?limit-access-js=yes')");
    $h[]=sshd_row($m,"{deny_countries}",sshd_count_items($m["countries"],"dashboard-ssh-countries"),"Loadjs('$page?limit-countries-js=yes')",array("SSHDDenyCountries"));
    $h[]=sshd_row($m,"{AuthorizedKeysFile}",sshd_tags(array(sshd_cfg($m,"AuthorizedKeysFile"))),"Loadjs('$page?AuthorizedKeys-js=yes')");
    $h[]=sshd_row($m,"{HostKeyAlgorithms}",sshd_algos_value($m["hka"]),"Loadjs('$page?HostKeyAlgorithms-js=yes')",array("HostKeyAlgorithms"));
    $h[]=sshd_row($m,"{PubkeyAcceptedAlgorithms}",sshd_algos_value($m["pka"]),"Loadjs('$page?PubkeyAcceptedAlgorithms-js=yes')",array("PubkeyAcceptedAlgorithms"));
    $hk="<span class='sshd-tag ak-mono' title=\"".sshd_esc($m["hostkey"])."\">".sshd_esc(basename($m["hostkey"]))."</span>";
    $h[]=sshd_row($m,"{private_host_key}",$hk,"Loadjs('$page?private-host-key-js=yes')");
    $h[]=sshd_card_close();
    return @implode("",$h);
}

function sshd_card_limits($m):string{
    $page=CurrentPageName();
    $js="Loadjs('$page?settings-js=yes&section=limits')";
    $alive=intval(sshd_cfg($m,"ClientAliveInterval"));
    $grace=intval(sshd_cfg($m,"LoginGraceTime"));
    $h=array();
    $h[]=sshd_card_open("fas fa-gauge","{sshd_limits}","{sshd_limits_sub}");
    $h[]=sshd_row($m,"{sshd_x11}",sshd_chip_bool(sshd_cfg($m,"X11Forwarding")),$js,array("X11Forwarding"));
    $h[]=sshd_row($m,"{sshd_tty}",sshd_chip_bool(sshd_cfg($m,"PermitTTY")),$js,array("PermitTTY"));
    $h[]=sshd_row($m,"IgnoreRhosts",sshd_chip_bool(sshd_cfg($m,"IgnoreRhosts")),$js,array("IgnoreRhosts"));
    $h[]=sshd_row($m,"{StrictModes}",sshd_chip_bool(sshd_cfg($m,"StrictModes")),$js,array("StrictModes"));
    $v=$alive>0?"<span class='sshd-num'>".sshd_duration($alive)."</span>":sshd_chip("off","{sshd_off}","fas fa-minus");
    $h[]=sshd_row($m,"{sshd_alive_interval}",$v,$js,array("ClientAliveInterval"));
    $v=$grace>0?"<span class='sshd-num'>".sshd_duration($grace)."</span>":sshd_chip("off","{sshd_no_limit}");
    $h[]=sshd_row($m,"{LoginGraceTime}",$v,$js,array("LoginGraceTime"));
    $h[]=sshd_row($m,"{sshd_alive_max}","<span class='sshd-num'>".intval(sshd_cfg($m,"ClientAliveCountMax"))." {attempts}</span>",$js,array("ClientAliveCountMax"));
    $h[]=sshd_row($m,"{sshd_max_auth}","<span class='sshd-num'>".intval(sshd_cfg($m,"MaxAuthTries"))."</span>",$js,array("MaxAuthTries"));
    $h[]=sshd_row($m,"{MaxSessions}","<span class='sshd-num'>".intval(sshd_cfg($m,"MaxSessions"))."</span>",$js,array("MaxSessions"));
    $h[]=sshd_card_close();
    return @implode("",$h);
}

function sshd_card_protocols($m):string{
    $page=CurrentPageName();
    $js="Loadjs('$page?settings-js=yes&section=protocols')";
    $h=array();
    $h[]=sshd_card_open("fas fa-shield-halved","{sshd_protocols}","{sshd_protocols_sub}");
    $h[]=sshd_row($m,"{PermitTunnel}",sshd_chip_bool(sshd_cfg($m,"PermitTunnel")),$js,array("PermitTunnel"));
    $h[]=sshd_row($m,"{AllowTcpForwarding}",sshd_chip_bool(sshd_cfg($m,"AllowTcpForwarding")),$js,array("AllowTcpForwarding"));
    $h[]=sshd_row($m,"{UseDNS}",sshd_chip_bool(sshd_cfg($m,"UseDNS")),$js,array("UseDNS"));
    $ciphers=$m["ciphers_on"]?sshd_algos_value($m["ciphers"]):sshd_chip("off","{defaults}");
    $h[]=sshd_row($m,"{sshd_ciphers}",$ciphers,$js,array("SSHDCiphersEnable","SSHDCiphers"));
    $h[]=sshd_card_close();
    return @implode("",$h);
}

// ---- Sessions ----
function sshd_sessions_list():array{
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/connected"));
    $out=array();
    if(!is_object($json) || !isset($json->Info) || !is_object($json->Info)){return $out;}
    if(!isset($json->Info->Users) || !is_array($json->Info->Users)){return $out;}
    foreach($json->Info->Users as $u){
        if(!is_object($u)){continue;}
        $out[]=array("user"=>(string)($u->User ?? ""),"ip"=>(string)($u->IPAddr ?? ""),
            "pid"=>intval($u->Pid ?? 0),"time"=>intval($u->Time ?? 0));
    }
    return $out;
}

function sshd_since($ts):string{
    $ts=intval($ts);
    if($ts<=0){return "<span class='ak-muted'>&ndash;</span>";}
    $d=max(0,time()-$ts);
    if($d<60){$t="{sshd_just_now}";}
    elseif($d<3600){$t=floor($d/60)." {sshd_min_ago}";}
    elseif($d<86400){$t=floor($d/3600)." {sshd_h_ago}";}
    else{$t=floor($d/86400)." {sshd_d_ago}";}
    return "<span title='".date("Y-m-d H:i",$ts)."'>$t</span>";
}

function sshd_card_sessions($m):string{
    $page=CurrentPageName();
    $list=sshd_sessions_list();
    $n=count($list);
    $h=array();
    $h[]="<section class='ak-card sshd-card sshd-card-sessions'><div class='sshd-head'><span class='sshd-head-ico'><i class='fas fa-users'></i></span>"
        ."<h3 class='sshd-title'>{sshd_sessions}</h3><span class='ak-count'>$n</span></div>";
    if($n==0){
        $h[]="<div class='sshd-empty'><span class='sshd-ico'><i class='fas fa-user-slash'></i></span>{sshd_no_session}</div></section>";
        return @implode("",$h);
    }
    $h[]="<div class='sshd-scroll'><table class='sshd-tbl'><thead><tr><th>{sshd_user}</th><th>{ipaddr}</th>"
        ."<th>{sshd_since}</th><th class='sshd-r'>PID</th><th class='sshd-r'><span class='sshd-sr'>{sshd_action}</span></th></tr></thead><tbody>";
    $shown=0;
    foreach($list as $s){
        if($shown>=5){break;}
        $shown++;
        $user=sshd_esc($s["user"]);
        $ip=sshd_esc($s["ip"]);
        $act="";
        if($m["admin"] && $s["pid"]>0){
            $act="<button type='button' class='ak-act ak-danger' aria-label=\"{sshd_disconnect}: $user ($ip)\" title=\"{sshd_disconnect}\" "
                ."OnClick=\"Loadjs('$page?sshd-kill-js={$s["pid"]}');\"><i class='fas fa-link-slash'></i></button>";
        }
        $h[]="<tr><td><span class='ak-dot ak-ok sshd-dot' aria-hidden='true'></span><strong>$user</strong></td><td class='ak-mono'>$ip</td>"
            ."<td>".sshd_since($s["time"])."</td><td class='sshd-r ak-mono'>{$s["pid"]}</td><td class='sshd-r'>$act</td></tr>";
    }
    $h[]="</tbody></table></div>";
    $h[]="<div class='sshd-foot'><span>{sshd_showing} $shown / $n</span>"
        ."<button type='button' class='ak-link' OnClick=\"Loadjs('$page?sessions-js=yes');\">{sshd_view_all} ($n)"
        ."<span class='sshd-ico'><i class='fas fa-arrow-right'></i></span></button></div>";
    $h[]="</section>";
    return @implode("",$h);
}

// ?sshd-kill-js=<pid> : confirmation, puis POST sshd-kill.
function sshd_kill_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!sshd_is_admin()){return $tpl->js_no_privileges();}
    $pid=intval($_GET["sshd-kill-js"]);
    $after="if(document.getElementById('id$pid')){\$('#id$pid').remove();}"
        ."if(document.getElementById('sshd-sessions-host')){LoadAjaxSilent('sshd-sessions-host','$page?sshd-sessions=yes');}";
    return $tpl->js_dialog_confirm_action("{sshd_confirm_kill}","sshd-kill",$pid,$after);
}

function sshd_kill_save():bool{
    $tpl=new template_admin();
    if(!sshd_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $pid=intval($_POST["sshd-kill"]);
    if($pid<=0){echo $tpl->post_error("PID");return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/kill/$pid");
    return admin_tracks("Kill OpenSSH session $pid");
}

function sshd_style():string{
    if(isset($GLOBALS["SSHD_STYLE"])){return "";}
    $GLOBALS["SSHD_STYLE"]=true;
    $accent="#10b981";$dark="#0d9488";$soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.sshd{color:#111827;margin-top:16px}
.sshd *{box-sizing:border-box}
.sshd-ico{display:inline-flex;font-size:12px}
.sshd-layout{display:grid;grid-template-columns:340px minmax(0,1fr);gap:20px;align-items:start}
.sshd-side{display:flex;flex-direction:column;gap:16px;min-width:0}
.sshd-main{display:flex;flex-direction:column;gap:16px;min-width:0}
.sshd-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(420px,1fr));gap:16px;align-items:start}
.sshd-col{display:flex;flex-direction:column;gap:16px;min-width:0}
.sshd-dim .sshd-card{opacity:.6}
.sshd-card{padding:16px 20px}
.sshd-head{display:flex;align-items:center;gap:10px;padding-bottom:12px;border-bottom:1px solid #f1f5f9}
.sshd-head-ico{width:30px;height:30px;border-radius:8px;background:$soft;color:$dark;display:inline-flex;align-items:center;justify-content:center;font-size:14px;flex:0 0 auto}
.sshd-title{margin:0;font-size:16px;font-weight:600;line-height:20px;color:#111827;text-transform:none}
.sshd-sub{margin-left:auto;font-size:13px;color:#6b7280;text-align:right}
.sshd-card-2fa .sshd-head{flex-wrap:wrap}
.sshd-card-2fa .sshd-sub{flex-basis:100%;margin:0 0 0 40px;text-align:left}
.sshd-row{display:flex;align-items:center;gap:10px;min-height:44px;padding:6px 0;border-top:1px solid #f1f5f9}
.sshd-body>.sshd-row:first-child{border-top:0}
.sshd-click{cursor:pointer;margin:0 -10px;padding-left:10px;padding-right:10px;border-radius:6px}
.sshd-click:hover{background:#f8fafc}
.sshd-click:focus-visible{outline:2px solid $dark;outline-offset:2px}
.sshd-label{flex:1 1 auto;min-width:96px;font-size:14px;color:#374151}
.sshd-value{display:flex;justify-content:flex-end;align-items:center;flex:0 1 auto;min-width:0;max-width:72%;font-size:14px;font-weight:500;color:#111827;text-align:right}
.sshd-pen{flex:0 0 14px;color:#9ca3af;font-size:12px;opacity:0;transition:opacity .15s}
.sshd-click:hover .sshd-pen,.sshd-click:focus-visible .sshd-pen{opacity:1}
.sshd-num{white-space:nowrap}
.sshd-wrap{white-space:normal}
.sshd-chip-long{min-width:0;max-width:100%}
.sshd-ell{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.sshd-tags{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:4px;min-width:0}
.sshd-tag{display:inline-block;max-width:100%;padding:2px 8px;border:1px solid #e5e7eb;border-radius:6px;background:#f9fafb;font-size:13px;font-weight:400;color:#1f2937;overflow-wrap:anywhere;text-align:left}
.sshd-banner{min-height:56px;margin-bottom:12px}
.sshd-banner-ico{display:inline-flex;font-size:16px}
.sshd-banner-sep{opacity:.6}
.sshd-qr{position:relative;margin-top:14px;min-height:200px;border-radius:8px;background:#f3f4f6;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:12px;overflow:hidden}
.sshd-qr-veil{position:absolute;inset:18px 60px;background:repeating-conic-gradient(#9ca3af 0 25%,#e5e7eb 0 50%) 0 0/22px 22px;filter:blur(5px);opacity:.55;border-radius:6px}
.sshd-qr-over{position:relative;display:flex;flex-direction:column;gap:8px}
.sshd-qr-img{max-width:100%;height:auto;background:#fff;padding:6px;border-radius:6px}
.sshd-qr-actions{display:flex;justify-content:center;margin-top:12px}
.sshd-note{margin:12px 0 0;font-size:13px;color:#6b7280;line-height:18px}
.sshd-note-err{color:#b91c1c}
.sshd-empty{display:flex;align-items:center;gap:8px;padding:22px 0 8px;font-size:14px;color:#6b7280}
.sshd-scroll{overflow-x:auto}
.sshd-tbl{width:100%;border-collapse:collapse;margin-top:4px}
.sshd-tbl th{font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#6b7280;text-align:left;padding:10px 8px;border-bottom:1px solid #e5e7eb;white-space:nowrap}
.sshd-tbl td{font-size:14px;color:#111827;padding:10px 8px;border-bottom:1px solid #f1f5f9;white-space:nowrap}
.sshd-tbl .sshd-r{text-align:right}
.sshd-dot{margin-right:8px;vertical-align:middle}
.sshd-sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}
.sshd-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;padding-top:12px;font-size:13px;color:#6b7280}
@media (max-width:1200px){.sshd-layout{grid-template-columns:1fr}}
@media (prefers-reduced-motion:reduce){.sshd-pen{transition:none}}
</style>";
}

function sshd_js($page):string{
    return "<script>
function SshdBusy(){
    if(document.querySelector('.modal.in,.modal.show')){return true;}
    var sw=document.querySelector('.sweet-alert');
    return !!(sw && sw.offsetParent!==null);
}
if(window.SshdTimer){clearInterval(window.SshdTimer);}
window.SshdTimer=setInterval(function(){
    if(!document.getElementById('sshd-sessions-host')){clearInterval(window.SshdTimer);window.SshdTimer=null;return;}
    if(SshdBusy()){return;}
    LoadAjaxSilent('OpenSSHStatus','$page?OpenSSHStatus=yes');
    LoadAjaxSilent('sshd-sessions-host','$page?sshd-sessions=yes');
},10000);
".js_tiny()."
NoSpinner();
</script>";
}
// Fin de l'onglet Status

function js_tiny(){
    $page=CurrentPageName();
    return "Loadjs('$page?js-tiny=yes');";
}
function OPenSSHStatus(){
    echo sshd_service_tile();
}
function lock_conf(){
    header("content-type: application/x-javascript");
    if(!sshd_is_admin()){
        $tpl=new template_admin();
        echo "swal({title:'OpenSSH',text:".json_encode($tpl->javascript_parse_text("{ERROR_NO_PRIVS2}")).",type:'error'});";
        return;
    }
    $lock=intval($_GET["lock-file-js"]);
    admin_tracks("Set the OpenSSH configuration lock to $lock");
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
    $data=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/conf/islock"));
    if(is_object($data) && property_exists($data,"Locked")){
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

function save_config():bool{
	$tpl=new template_admin();
    if(!sshd_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $tpl->CLEAN_POST();
    // Marqueur du formulaire et champs en lecture seule (reglages tenus par une politique Meta « SSH ») :
    // jamais ecrits dans OpenSSHDArray. Un reglage tenu poste sous son vrai nom est un contournement : refus.
    unset($_POST["sshd-settings-form"]);
    $_POST=sshd_meta_strip_ro($_POST);
    $held=sshd_meta_posted_held($_POST);
    if(count($held)>0){return sshd_meta_refuse_post();}

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
	
	if(!$sshd->SaveInterface()){echo $sshd->LastError();return false;}

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return true;
}
function MaxStartups_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{MaxStartups}","$page?MaxStartups-popup=yes",650);
}
function UseWelcome_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{welcome_banner}","$page?UseWelcome-popup=yes",650);
}

function gssapi_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("GSSApi","$page?gssapi-popup=yes",650);
}

function UseBanner_js():bool{
    if(!sshd_is_admin()){$tpl=new template_admin();return $tpl->js_no_privileges();}
    if(sshd_meta_key_held("Banner")){return sshd_meta_refuse_js();}
    $sshd=new openssh();
    $page=CurrentPageName();
    if($sshd->main_array["Banner"]==0){
        $sshd->main_array["Banner"]=1;
    }else{
        $sshd->main_array["Banner"]=0;
    }
    header("content-type: application/x-javascript");
    if(!$sshd->SaveInterface()){
        // This responder is executed as JavaScript — a plain text error would
        // be eval'd and throw, leaving the toggle silently stuck.
        echo "swal({title:'OpenSSH',text:".json_encode($sshd->LastError()).",type:'error'});";
        return false;
    }
    echo "LoadAjax('openssh-status-config','$page?openssh-status-config=yes')";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks("Set the OpenSSH banner to {$sshd->main_array["Banner"]}");
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
    if(sshd_meta_key_held("HostKeyAlgorithms")){echo sshd_meta_banner($tpl);}
    echo "<div id='HostKeyAlgorithms-table'></div>";
    echo "<script>LoadAjaxSilent('HostKeyAlgorithms-table','$page?HostKeyAlgorithms-table=yes');</script>";
    return true;
}
function PubkeyAcceptedAlgorithms_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(sshd_meta_key_held("PubkeyAcceptedAlgorithms")){echo sshd_meta_banner($tpl);}
    echo "<div id='PubkeyAcceptedAlgorithms-table'></div>";
    echo "<script>LoadAjaxSilent('PubkeyAcceptedAlgorithms-table','$page?PubkeyAcceptedAlgorithms-table=yes');</script>";
    return true;
}
function PubkeyAcceptedAlgorithms_save():bool{
    $page=CurrentPageName();
    if(!sshd_is_admin()){return (new template_admin())->js_no_privileges();}
    if(sshd_meta_key_held("PubkeyAcceptedAlgorithms")){return sshd_meta_refuse_js();}
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
    if(!sshd_is_admin()){return (new template_admin())->js_no_privileges();}
    if(sshd_meta_key_held("HostKeyAlgorithms")){return sshd_meta_refuse_js();}
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
    echo $tpl->table_form_compile(2);
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
    echo $tpl->table_form_compile(2);
    return true;

}

// Meme defaut que OpenSSHConf.DefaultCiphers (articarest) : AEAD puis CTR, ni CBC ni 3DES
// (ANSSI-BP-028 R79). L'ancien defaut, enregistre tel quel par la page, vaut « defaut ».
function sshd_ciphers_default($SSHDCiphers){
    $legacy="aes256-ctr,aes128-cbc,3des-cbc,aes192-cbc,aes256-cbc";
    $SSHDCiphers=trim(strval($SSHDCiphers));
    if($SSHDCiphers=="" || $SSHDCiphers==$legacy){
        return "chacha20-poly1305@openssh.com,aes256-gcm@openssh.com,aes128-gcm@openssh.com,aes256-ctr,aes192-ctr,aes128-ctr";
    }
    return $SSHDCiphers;
}

/* ============================================================================================
 * Cles autorisees (modale « Authorized Keys ») — maquette Stitch du 2026-11-19
 * (stitch_ssh_authorized_keys_modal_redesign). Donnees : table sshd_authorizedkeys de sshd.db
 * (zkey, username, email, limitaddr, limitfeatures, zippackage, enabled, metaid). Le type, l'empreinte
 * SHA256 et le commentaire sont calcules depuis la cle elle-meme ; aucune date d'usage n'existe.
 * ============================================================================================ */

// Acces base : hors de la tranche des widgets (le harnais de rendu les remplace).
function sshak_db_rows(string $search):array{
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $search=trim($search);
    if(strlen($search)>1){
        $like=str_replace("*","%","%$search%");
        while(strpos($like,"%%")!==false){$like=str_replace("%%","%",$like);}
        $rows=$q->QUERY_SQL_PARAMS("SELECT * FROM sshd_authorizedkeys WHERE zkey LIKE ? OR username LIKE ? OR email LIKE ? ORDER BY username,zmd5",array($like,$like,$like));
    }else{
        $rows=$q->QUERY_SQL("SELECT * FROM sshd_authorizedkeys ORDER BY username,zmd5");
    }
    return is_array($rows)?$rows:array();
}
function sshak_db_row(string $zmd5):array{
    if($zmd5===""){return array();}
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    $rows=$q->QUERY_SQL_PARAMS("SELECT * FROM sshd_authorizedkeys WHERE zmd5=? LIMIT 1",array($zmd5));
    if(!is_array($rows) || count($rows)==0){return array();}
    $first=reset($rows);
    return is_array($first)?$first:array();
}
function sshak_accounts():array{
    return api_local_users();
}
function sshak_zip_path():string{
    return dirname(__FILE__)."/ressources/logs/web/Authorized.zip";
}

// ---- SSHAK widgets begin ----
// Traduit un jeton AVANT toute substitution de %s (un str_replace sur "{jeton}" ne trouverait rien).
function sshak_t(string $token):string{
    if(!isset($GLOBALS["SSHAK_TPL"])){$GLOBALS["SSHAK_TPL"]=new template_admin();}
    return $GLOBALS["SSHAK_TPL"]->_ENGINE_parse_body("{".$token."}");
}
function sshak_fn():string{
    $f=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    return $f===""?"blur":$f;
}
function sshak_h($v):string{
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}
// Type, empreinte SHA256 (comme ssh-keygen -lf) et commentaire, tires de la cle.
function sshak_parse_key(string $zkey):array{
    $types=array("ssh-ed25519"=>"ed25519","ssh-rsa"=>"rsa","rsa-sha2-256"=>"rsa","rsa-sha2-512"=>"rsa",
        "ecdsa-sha2-nistp256"=>"ecdsa","ecdsa-sha2-nistp384"=>"ecdsa","ecdsa-sha2-nistp521"=>"ecdsa",
        "sk-ssh-ed25519@openssh.com"=>"sk","sk-ecdsa-sha2-nistp256@openssh.com"=>"sk","ssh-dss"=>"dsa");
    $out=array("kind"=>"unknown","fp"=>"","comment"=>"");
    $f=preg_split('/\s+/',trim($zkey),-1,PREG_SPLIT_NO_EMPTY);
    if(!is_array($f)){return $out;}
    foreach($f as $i=>$v){
        if(!isset($types[$v])){continue;}
        $out["kind"]=$types[$v];
        $blob=isset($f[$i+1])?$f[$i+1]:"";
        $raw=base64_decode($blob,true);
        if($raw!==false && strlen($raw)>0){
            $out["fp"]="SHA256:".rtrim(base64_encode(hash("sha256",$raw,true)),"=");
        }
        $out["comment"]=trim(implode(" ",array_slice($f,$i+2)));
        return $out;
    }
    return $out;
}
function sshak_nets(string $limitaddr):array{
    $nets=array();
    foreach(preg_split('/[\r\n,]+/',$limitaddr) as $l){
        $l=trim($l);
        if($l!==""){$nets[]=$l;}
    }
    return $nets;
}
function sshak_opts(string $limitfeatures):array{
    $known=array("no-port-forwarding","no-agent-forwarding","no-X11-forwarding","no-pty","no-user-rc");
    $cur=array();
    foreach(explode(",",$limitfeatures) as $o){
        $o=trim($o);
        if(in_array($o,$known,true)){$cur[]=$o;}
    }
    return $cur;
}
// Modele unique : une ligne de base -> ce que montrent la liste et ses compteurs.
function sshak_item(array $r):array{
    $k=sshak_parse_key((string)($r["zkey"] ?? ""));
    $nets=sshak_nets((string)($r["limitaddr"] ?? ""));
    $opts=sshak_opts((string)($r["limitfeatures"] ?? ""));
    return array(
        "id"=>preg_replace('/[^a-f0-9]/','',strtolower((string)($r["zmd5"] ?? ""))),
        "kind"=>$k["kind"],"fp"=>$k["fp"],"comment"=>$k["comment"],
        "account"=>trim((string)($r["username"] ?? "")),
        "nets"=>$nets,"opts"=>$opts,
        "generated"=>strlen((string)($r["zippackage"] ?? ""))>20,
        "meta"=>sshd_meta_row($r),
        "enabled"=>!isset($r["enabled"]) || intval($r["enabled"])==1,
    );
}
function sshak_model(string $search):array{
    $all=array();
    foreach(sshak_db_rows("") as $r){$all[]=sshak_item($r);}
    $shown=$all;
    if(strlen(trim($search))>1){
        $shown=array();
        foreach(sshak_db_rows($search) as $r){$shown[]=sshak_item($r);}
    }
    $restricted=0;$open=0;
    foreach($all as $it){
        if(count($it["nets"])==0 && count($it["opts"])==0){$open++;}else{$restricted++;}
    }
    return array("all"=>$all,"shown"=>$shown,"total"=>count($all),"restricted"=>$restricted,"open"=>$open,
        "search"=>trim($search),"meta_rows"=>count(array_filter($all,function($i){return $i["meta"];})));
}

function sshak_style():string{
    if(isset($GLOBALS["SSHAK_CSS"])){return "";}
    $GLOBALS["SSHAK_CSS"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$dark="#047857";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
    }
    return "<style>
.sshak{color:#111d23;font-size:14px}
.sshak *{box-sizing:border-box}
.sshak-hero{display:flex;gap:16px;padding:18px 22px;border:1px solid #d7e4e0;border-left:4px solid $accent;border-radius:8px;background:$soft;margin-bottom:14px}
.sshak-hero-ico{flex:0 0 40px;width:40px;height:40px;border-radius:50%;background:#fff;color:$dark;display:flex;align-items:center;justify-content:center;font-size:18px}
.sshak-hero-t{font-size:17px;font-weight:600;line-height:24px;margin:0 0 4px}
.sshak-hero-p{font-size:13px;line-height:20px;color:#3d4947;margin:0 0 10px}
.sshak-chips{display:flex;flex-wrap:wrap;gap:8px}
.sshak-chip{display:inline-flex;align-items:center;gap:7px;padding:4px 12px;border-radius:999px;background:#fff;border:1px solid #c9d3d1;font-size:13px;color:#111d23}
.sshak-chip svg,.sshak-chip i{color:$dark}
.sshak-tools{display:grid;grid-template-columns:auto auto 1fr;gap:6px 10px;align-items:start;margin:4px 0 6px}
.sshak-tools .ak-search{grid-column:3;justify-self:end;width:300px}
.sshak-hint{font-size:13px;color:#6d7a77;line-height:18px;max-width:260px}
.sshak-count{grid-column:3;justify-self:end;font-size:13px;color:#3d4947;text-align:right}
.sshak-count b{color:#111d23}
.sshak-tbl{width:100%;border-collapse:separate;border-spacing:0 8px;table-layout:fixed}
.sshak-tbl td{background:#fff;border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;padding:12px 12px;vertical-align:middle}
.sshak-tbl td:first-child{border-left:1px solid #e5e7eb;border-radius:8px 0 0 8px;width:96px}
.sshak-tbl td:last-child{border-right:1px solid #e5e7eb;border-radius:0 8px 8px 0;width:96px;text-align:right}
.sshak-tbl td.sshak-c-acc{width:200px}
.sshak-tbl td.sshak-c-res{width:230px}
.sshak-tbl tr.sshak-r{cursor:pointer}
.sshak-tbl tr.sshak-r:hover td{background:#f9fafb}
.sshak-tbl tr.sshak-r:focus-visible{outline:none}
.sshak-tbl tr.sshak-r:focus-visible td{box-shadow:inset 0 0 0 2px $accent}
.sshak-tbl tr.sshak-off td{background:#fbfbfb}
.sshak-type{display:inline-flex;flex-direction:column;align-items:center;gap:3px;width:76px;padding:6px 4px;border-radius:6px;background:#f3f4f6;font-size:13px;font-weight:600;letter-spacing:.02em;color:#263238;text-align:center;line-height:16px}
.sshak-type.sshak-bad{background:#ffdad6;color:#ba1a1a}
.sshak-id{min-width:0}
.sshak-cmt{font-size:14px;font-weight:600;line-height:20px;word-break:break-word}
.sshak-cmt-none{font-weight:400;font-style:italic;color:#6d7a77}
.sshak-fp{display:flex;align-items:center;gap:6px;margin-top:3px;min-width:0}
.sshak-fp span{font-family:monospace;font-size:13px;color:#6d7a77;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sshak-copy{border:0;background:transparent;color:#6d7a77;padding:2px 4px;border-radius:4px;cursor:pointer;font-size:13px}
.sshak-copy:hover{background:#e3f0f8;color:#111d23}
.sshak-copy:focus-visible,.sshak-act:focus-visible{outline:2px solid $accent;outline-offset:1px}
.sshak-badges{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px}
.sshak-acc{font-size:14px;display:flex;align-items:center;gap:6px;font-weight:600}
.sshak-acc-s{font-size:13px;color:#6d7a77;margin-top:2px;line-height:18px}
.sshak-res{display:flex;flex-wrap:wrap;gap:6px}
.sshak-res .ak-tag{font-size:13px}
.sshak-acts{display:inline-flex;gap:6px}
.sshak-act{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#3d4947;cursor:pointer;font-size:14px}
.sshak-act:hover{background:#e3f0f8;color:#111d23}
.sshak-act.sshak-del:hover{background:#ffdad6;color:#ba1a1a;border-color:#f3b4ad}
.sshak-act.sshak-lock{background:#e6eeed;color:#005447;border-color:#bfd4d1;cursor:help}
.sshak-empty{text-align:center;padding:36px 20px;background:#fff;border:1px dashed #c9d3d1;border-radius:8px;margin-top:8px}
.sshak-empty-ico{width:64px;height:64px;border-radius:50%;background:$soft;color:$dark;display:inline-flex;align-items:center;justify-content:center;font-size:26px;margin-bottom:10px}
.sshak-empty-t{font-size:16px;font-weight:600;margin-bottom:6px}
.sshak-empty-p{font-size:13px;color:#3d4947;line-height:20px;margin-bottom:14px}
.sshak-empty .ak-actions{justify-content:center}
.sshak-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;border-top:1px solid #e5e7eb;margin-top:6px;padding-top:10px;font-size:13px;color:#3d4947}
.sshak-pg{display:flex;gap:4px}
.sshak-pg a{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border:1px solid #d1d5db;border-radius:4px;background:#fff;color:#111d23;text-decoration:none;font-size:13px;font-weight:600}
.sshak-pg a.awc-pg-cur{background:$dark;border-color:$dark;color:#fff}
.sshak-pg a.awc-pg-off{color:#bcc9c6;cursor:default}
.sshak-prev{display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:6px;background:#f3f4f6;font-size:13px;margin-top:2px}
.sshak-prev.sshak-prev-bad{background:#ffdad6;color:#ba1a1a}
.sshak-prev .sshak-type{width:auto;flex-direction:row;padding:3px 8px;background:#fff}
.sshak-note{font-size:13px;color:#6d7a77;line-height:19px}
.sshak-lbl{display:block;font-size:13px;font-weight:600;color:#111d23;margin:10px 0 6px}
.sshak-ta{display:block;width:100%;border:1px solid #c9d3d1;border-radius:6px;padding:8px 10px;font-family:monospace;font-size:13px;line-height:19px;resize:vertical;background:#fff}
.sshak-ta:focus{outline:2px solid $accent;outline-offset:0;border-color:$accent}
.sshak-gen-ok{display:flex;gap:14px;align-items:center;padding:16px;border:1px solid #d7e4e0;border-radius:8px;background:$soft;margin-bottom:12px}
.sshak-gen-ok .sshak-hero-ico{flex:0 0 44px;width:44px;height:44px;font-size:20px}
.sshak-gen-file{font-size:13px;color:#3d4947;margin-top:3px}
.sshak-gen-list{list-style:none;margin:10px 0 0;padding:0;font-size:13px;line-height:24px}
.sshak-gen-list span{font-family:monospace;font-weight:600}
@media (max-width:940px){.sshak-tbl td.sshak-c-res{width:170px}.sshak-tbl td.sshak-c-acc{width:160px}}
@media (prefers-reduced-motion:no-preference){.sshak-tbl td{transition:background .15s}}
</style>";
}

function sshak_js(string $page,string $function):string{
    return "<script>
function SshdAkReload(){var e=document.getElementById('sshak-search');var s=e?e.value:'';LoadAjaxSilent('sshak-list','$page?AuthorizedKeys-table=yes&function=$function&search='+encodeURIComponent(s));}
function SshdAkSearch(){if(window.SshdAkT){clearTimeout(window.SshdAkT);}window.SshdAkT=setTimeout(SshdAkReload,400);}
function SshdAkClear(){var e=document.getElementById('sshak-search');if(e){e.value='';}SshdAkReload();}
function SshdAkRow(ev,js){var t=ev.target;while(t&&t.tagName!=='TR'){if(t.getAttribute&&t.getAttribute('data-noedit')){return;}t=t.parentNode;}eval(js);}
function SshdAkKey(ev,js){if(ev.key==='Enter'||ev.key===' '){ev.preventDefault();eval(js);}}
function SshdAkCopy(btn,txt){
 var ok=function(){var o=btn.innerHTML;btn.innerHTML='<span class=\"fa-solid fa-check\"></span>';setTimeout(function(){btn.innerHTML=o;},1200);};
 if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(txt).then(ok,function(){SshdAkCopyOld(txt);ok();});return;}
 SshdAkCopyOld(txt);ok();}
function SshdAkCopyOld(txt){var t=document.createElement('textarea');t.value=txt;t.style.position='fixed';t.style.opacity='0';document.body.appendChild(t);t.select();try{document.execCommand('copy');}catch(e){}document.body.removeChild(t);}
</script>";
}

function sshak_hero():string{
    $h=array();
    $h[]="<div class='sshak-hero'>";
    $h[]="<span class='sshak-hero-ico' aria-hidden='true'><i class='fa-solid fa-key'></i></span>";
    $h[]="<div><h3 class='sshak-hero-t'>{sshak_hero_title}</h3><p class='sshak-hero-p'>{sshak_hero_text}</p>";
    $h[]="<div class='sshak-chips'>";
    $h[]="<span class='sshak-chip'><i class='fa-solid fa-eye-slash' aria-hidden='true'></i>{sshak_chip_public}</span>";
    $h[]="<span class='sshak-chip'><i class='fa-solid fa-user' aria-hidden='true'></i>{sshak_chip_account}</span>";
    $h[]="<span class='sshak-chip'><i class='fa-solid fa-network-wired' aria-hidden='true'></i>{sshak_chip_networks}</span>";
    $h[]="</div></div></div>";
    return @implode("",$h);
}
function sshak_toolbar(string $page,string $function):string{
    $h=array();
    $h[]="<div class='sshak-tools'>";
    $h[]="<button type='button' class='ak-btn ak-btn-primary ak-btn-lg' OnClick=\"Loadjs('$page?AuthorizedKeys-add-js=yes&function=$function');\"><i class='fa-solid fa-file-import' aria-hidden='true'></i>&nbsp;{sshak_import}</button>";
    $h[]="<button type='button' class='ak-btn ak-btn-lg' OnClick=\"Loadjs('$page?AuthorizedKeys-create-js=yes&function=$function');\"><i class='fa-solid fa-wand-magic-sparkles' aria-hidden='true'></i>&nbsp;{sshak_generate}</button>";
    $h[]="<label class='ak-search'><i class='fa-solid fa-magnifying-glass' aria-hidden='true'></i><input type='search' id='sshak-search' placeholder=\"{sshak_search_ph}\" aria-label=\"{sshak_search_ph}\" OnInput='SshdAkSearch()' autocomplete='off'></label>";
    $h[]="<div class='sshak-hint'>{sshak_import_hint}</div>";
    $h[]="<div class='sshak-hint'>{sshak_generate_hint}</div>";
    $h[]="<div class='sshak-count' id='sshak-count'></div>";
    $h[]="</div>";
    return @implode("",$h);
}
// « 3 keys — 2 restricted · 1 open to any network », sur toutes les cles (pas seulement le filtre).
function sshak_counter(array $m):string{
    if($m["total"]==0){return "";}
    $s="<b>{$m["total"]}</b> ".($m["total"]==1?"{sshak_n_key}":"{sshak_n_keys}");
    $s.=" — <b>{$m["restricted"]}</b> {sshak_n_restricted}";
    if($m["open"]>0){$s.=" · <b>{$m["open"]}</b> {sshak_n_open}";}
    return $s;
}
function sshak_type_badge(array $it):string{
    $labels=array("ed25519"=>"ED25519","rsa"=>"RSA","ecdsa"=>"ECDSA","sk"=>"{sshak_type_sk}","dsa"=>"DSA","unknown"=>"?");
    $ico=$it["kind"]=="sk"?"fa-solid fa-microchip":"fa-solid fa-key";
    $cls="sshak-type";$title="";
    if($it["kind"]=="dsa" || $it["kind"]=="unknown"){$cls.=" sshak-bad";$title=" title=\"{sshak_dsa_ignored}\"";$ico="fa-solid fa-triangle-exclamation";}
    return "<span class='$cls'$title><i class='$ico' aria-hidden='true'></i>{$labels[$it["kind"]]}</span>";
}
function sshak_identity(array $it):string{
    $h=array();
    $h[]="<div class='sshak-id'>";
    if($it["comment"]===""){
        $h[]="<div class='sshak-cmt sshak-cmt-none'>{sshak_no_comment}</div>";
    }else{
        $h[]="<div class='sshak-cmt'>".sshak_h($it["comment"])."</div>";
    }
    if($it["fp"]!==""){
        $fp=sshak_h($it["fp"]);
        $h[]="<div class='sshak-fp'><span title='$fp'>$fp</span><button type='button' class='sshak-copy' data-noedit='1' aria-label=\"{sshak_copy_fp}\" title=\"{sshak_copy_fp}\" OnClick=\"SshdAkCopy(this,'$fp')\"><i class='fa-regular fa-copy' aria-hidden='true'></i></button></div>";
    }
    $b=array();
    if($it["meta"]){$b[]="<span class='ak-tag ak-info' title=\"{sshd_meta_row_locked}\"><i class='fa-solid fa-building-shield' aria-hidden='true'></i>Artica Meta</span>";}
    if($it["generated"]){$b[]="<span class='ak-tag ak-accent'><i class='fa-solid fa-file-zipper' aria-hidden='true'></i>{sshak_generated}</span>";}
    if(!$it["enabled"]){$b[]="<span class='ak-tag ak-off' title=\"{sshak_suspended_help}\"><i class='fa-solid fa-pause' aria-hidden='true'></i>{sshak_suspended}</span>";}
    if(count($b)>0){$h[]="<div class='sshak-badges'>".@implode("",$b)."</div>";}
    $h[]="</div>";
    return @implode("",$h);
}
function sshak_account_cell(array $it):string{
    $acc=$it["account"];
    if($acc===""){
        return "<div class='sshak-acc'><i class='fa-solid fa-user' aria-hidden='true'></i>—</div><div class='sshak-acc-s'>{sshak_dest_unset}</div>";
    }
    $sub=$acc=="root"?"{sshak_dest_root}":"{sshak_dest_user}";
    return "<div class='sshak-acc'><i class='fa-solid fa-user' aria-hidden='true'></i><span style='font-family:monospace'>".sshak_h($acc)."</span></div><div class='sshak-acc-s'>$sub</div>";
}
function sshak_restrictions(array $it):string{
    $nets=count($it["nets"]);$opts=count($it["opts"]);
    if($nets==0 && $opts==0){
        return "<div class='sshak-res'><span class='ak-tag ak-warn' title=\"{sshak_any_nolimit_help}\"><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i>{sshak_any_nolimit}</span></div>";
    }
    $h=array();
    if($nets>0){
        $list=sshak_h(implode(", ",$it["nets"]));
        $label=sshak_t($nets==1?"sshak_from_one_network":"sshak_from_networks");
        $h[]="<span class='ak-tag ak-off' title='$list'><i class='fa-solid fa-network-wired' aria-hidden='true'></i>".str_replace("%s",$nets,$label)."</span>";
    }else{
        $h[]="<span class='ak-tag ak-off' title=\"{sshak_any_network_help}\"><i class='fa-solid fa-globe' aria-hidden='true'></i>{sshak_any_network}</span>";
    }
    if($opts>0){
        $label=sshak_t($opts==1?"sshak_one_limit":"sshak_limits");
        $h[]="<span class='ak-tag ak-off' title='".sshak_h(implode(", ",$it["opts"]))."'><i class='fa-solid fa-shield-halved' aria-hidden='true'></i>".str_replace("%s",$opts,$label)."</span>";
    }
    return "<div class='sshak-res'>".@implode("",$h)."</div>";
}
function sshak_actions(array $it,string $page,string $function):string{
    $id=$it["id"];
    if($it["meta"]){
        return "<span class='sshak-acts' data-noedit='1'><span class='sshak-act sshak-lock' role='img' aria-label=\"{sshd_meta_row_locked}\" title=\"{sshd_meta_row_locked}\"><i class='fa-solid fa-lock' aria-hidden='true'></i></span></span>";
    }
    $h=array();
    if($it["generated"]){
        $h[]="<button type='button' class='sshak-act' data-noedit='1' aria-label=\"{sshak_download}\" title=\"{sshak_download}\" OnClick=\"document.location.href='/$page?AuthorizedKeys-add-download=$id';\"><i class='fa-solid fa-download' aria-hidden='true'></i></button>";
    }
    $h[]="<button type='button' class='sshak-act sshak-del' data-noedit='1' aria-label=\"{sshak_delete}\" title=\"{sshak_delete}\" OnClick=\"Loadjs('$page?AuthorizedKeys-del-js=$id&function=$function');\"><i class='fa-solid fa-trash-can' aria-hidden='true'></i></button>";
    return "<span class='sshak-acts' data-noedit='1'>".@implode("",$h)."</span>";
}
function sshak_row(array $it,string $page,string $function):string{
    $js="Loadjs('$page?AuthorizedKeys-add-js=yes&zmd5={$it["id"]}&function=$function');";
    $cls="sshak-r".($it["enabled"]?"":" sshak-off");
    return "<tr class='$cls' id='sshak-{$it["id"]}' tabindex='0' OnClick=\"SshdAkRow(event,&quot;$js&quot;)\" OnKeyDown=\"SshdAkKey(event,&quot;$js&quot;)\">"
        ."<td>".sshak_type_badge($it)."</td>"
        ."<td>".sshak_identity($it)."</td>"
        ."<td class='sshak-c-acc'>".sshak_account_cell($it)."</td>"
        ."<td class='sshak-c-res'>".sshak_restrictions($it)."</td>"
        ."<td>".sshak_actions($it,$page,$function)."</td></tr>";
}
function sshak_empty(string $page,string $function):string{
    return "<div class='sshak-empty'><span class='sshak-empty-ico' aria-hidden='true'><i class='fa-solid fa-key'></i></span>"
        ."<div class='sshak-empty-t'>{sshak_empty_title}</div><div class='sshak-empty-p'>{sshak_empty_text}</div>"
        ."<div class='ak-actions'><button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?AuthorizedKeys-add-js=yes&function=$function');\"><i class='fa-solid fa-file-import' aria-hidden='true'></i>&nbsp;{sshak_import}</button>"
        ."<button type='button' class='ak-btn' OnClick=\"Loadjs('$page?AuthorizedKeys-create-js=yes&function=$function');\"><i class='fa-solid fa-wand-magic-sparkles' aria-hidden='true'></i>&nbsp;{sshak_generate}</button></div></div>";
}
function sshak_nomatch(string $search):string{
    return "<div class='sshak-empty'><span class='sshak-empty-ico' aria-hidden='true'><i class='fa-solid fa-magnifying-glass'></i></span>"
        ."<div class='sshak-empty-t'>{sshak_nomatch} « ".sshak_h($search)." »</div>"
        ."<div class='ak-actions'><button type='button' class='ak-link' OnClick='SshdAkClear()'>{sshak_clear_search}</button></div></div>";
}

// Modale principale : accroche, bandeau Meta, barre d'outils, puis la liste rechargeable.
function AuthorizedKeys_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=sshak_fn();
    if(!sshd_is_admin()){echo $tpl->div_error("{ERROR_NO_PRIVS2}");return false;}
    $m=sshak_model("");
    $meta="";
    if(sshd_meta_keys_held() || $m["meta_rows"]>0){$meta=sshd_meta_banner($tpl,true,"{sshd_meta_keys_explain}");}
    $html=array();
    $html[]="<div class='sshak'>";
    $html[]=sshak_hero();
    $html[]="</div>";
    $html2=array();
    $html2[]="<div class='sshak'>".sshak_toolbar($page,$function);
    $html2[]="<div id='sshak-list'></div></div>";
    echo sshak_style().sshak_js($page,$function)
        .$tpl->_ENGINE_parse_body(@implode("",$html))
        .$meta
        .$tpl->_ENGINE_parse_body(@implode("",$html2))
        ."<script>SshdAkReload();</script>";
    return true;
}
// Liste rechargee seule (recherche, apres ajout/suppression).
function AuthorizedKeys_table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=sshak_fn();
    if(!sshd_is_admin()){echo $tpl->div_error("{ERROR_NO_PRIVS2}");return false;}
    $m=sshak_model((string)($_GET["search"] ?? ""));
    $html=array();
    if($m["total"]==0){
        $html[]=sshak_empty($page,$function);
    }elseif(count($m["shown"])==0){
        $html[]=sshak_nomatch($m["search"]);
    }else{
        $html[]="<table class='sshak-tbl'><tbody id='sshak-rows'>";
        foreach($m["shown"] as $it){$html[]=sshak_row($it,$page,$function);}
        $html[]="</tbody></table>";
    }
    $html[]="<div class='sshak-foot'><span><i class='fa-solid fa-circle-info' aria-hidden='true'></i>&nbsp;{sshak_footer}</span>";
    if(count($m["shown"])>0){
        $html[]="<span style='display:flex;align-items:center;gap:10px'><span>{showing} ".count($m["shown"])." / {$m["total"]}</span><span class='sshak-pg' id='sshak-pager'></span></span>";
    }
    $html[]="</div>";
    // Le compteur est rendu ici (meme passe de traduction que la liste) puis deplace dans la barre d'outils.
    $html[]="<div id='sshak-count-src' hidden>".sshak_counter($m)."</div>";
    echo sshak_style().$tpl->_ENGINE_parse_body(@implode("",$html))
        ."<script>NoSpinner();(function(){var s=document.getElementById('sshak-count-src'),c=document.getElementById('sshak-count');if(s&&c){c.innerHTML=s.innerHTML;}if(s){s.parentNode.removeChild(s);}})();</script>"
        .awc_pager_js(25,"sshak-rows","sshak-pager");
    return true;
}

// Fenetre « Import key » / edition d'une cle.
function AuthorizedKeys_add_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=sshak_fn();
    $zmd5=preg_replace('/[^a-f0-9]/','',strtolower((string)($_GET["zmd5"] ?? "")));
    if(!sshd_is_admin()){echo $tpl->div_error("{ERROR_NO_PRIVS2}");return false;}
    $ligne=sshak_db_row($zmd5);
    $isNew=count($ligne)==0;
    if(!$isNew && sshd_meta_row($ligne)){
        echo sshak_meta_readonly($tpl,$ligne);
        return true;
    }
    $usernameGet=trim((string)($_GET["username"] ?? ""));
    $zkey=(string)($ligne["zkey"] ?? "");
    $account=$isNew?$usernameGet:trim((string)($ligne["username"] ?? ""));
    $limitaddr=(string)($ligne["limitaddr"] ?? "");
    $cur=$isNew?array("no-port-forwarding","no-agent-forwarding","no-X11-forwarding"):sshak_opts((string)($ligne["limitfeatures"] ?? ""));

    $accounts=array(""=>"{sshak_account_auto}","root"=>"{sshak_account_root}");
    foreach(sshak_accounts() as $u){if($u!=="root"){$accounts[$u]=$u;}}
    if($account!=="" && !isset($accounts[$account])){$accounts[$account]=$account;}

    $form=array();
    $form[]=$tpl->field_hidden("AkOrig",$zmd5);
    $form[]=sshak_textarea($tpl,"zkey","{sshak_pubkey}",$zkey,4,"ssh-ed25519 AAAAC3Nza... jdoe@laptop");
    $form[]="<tr><td colspan='2'><div class='sshak-note'>{sshak_pubkey_help}</div><div id='sshak-prev' class='sshak-prev' style='display:none' role='status' aria-live='polite'></div></td></tr>";
    $form[]=$tpl->field_array_hash($accounts,"AkAccount","{sshak_account}",$account,false,"{sshak_account_help}",null,false);
    $form[]=sshak_textarea($tpl,"limitaddr","{sshak_networks}",$limitaddr,3,"192.168.1.0/24");
    $form[]="<tr><td colspan='2'><div class='sshak-note'>{sshak_networks_help}</div></td></tr>";
    $form[]=$tpl->field_section("{sshak_restrict_title}","{sshak_restrict_explain}",false,"fa-solid fa-shield-halved");
    $optlabels=array("no-port-forwarding"=>"pf","no-agent-forwarding"=>"af","no-X11-forwarding"=>"x11","no-pty"=>"pty","no-user-rc"=>"rc");
    foreach($optlabels as $opt=>$t){
        $form[]=$tpl->field_checkbox("FEATURE-$opt","{sshak_opt_$t}",in_array($opt,$cur,true)?1:0,false,"$opt — {sshak_opt_{$t}_help}");
    }
    $form[]="<tr><td colspan='2'><div class='sshak-note'><i class='fa-solid fa-circle-info' aria-hidden='true'></i>&nbsp;{sshak_footer}</div></td></tr>";

    $jsAfter="dialogInstance2.close();if(typeof SshdAkReload==='function'){SshdAkReload();}Loadjs('$page?reload-compile=yes');$function();";
    $html=array();
    if(!$isNew){
        $btns=array();
        if(strlen((string)($ligne["zippackage"] ?? ""))>20){
            $btns[]=array("document.location.href='/$page?AuthorizedKeys-add-download=$zmd5';",ico_download,"{sshak_download}","AsSystemAdministrator");
        }
        $btns[]=array("Loadjs('$page?AuthorizedKeys-add-delete=$zmd5&username=".urlencode($account)."&function=$function')",ico_trash,"{sshak_delete}","AsSystemAdministrator");
        $html[]=$tpl->table_buttons($btns);
        $html[]=$tpl->div_explain("{sshak_edit_title}||{sshak_edit_explain}");
    }else{
        $html[]=$tpl->div_explain("{sshak_import_title}||{sshak_import_explain}");
    }
    $html[]=$tpl->form_outside("",@implode("\n",$form),"",$isNew?"{sshak_add}":"{sshak_apply}",$jsAfter,"AsSystemAdministrator");
    $zid=md5("zkey".$tpl->suffixid);
    $msg=array("bad"=>$tpl->javascript_parse_text("{sshak_preview_invalid}"),"priv"=>$tpl->javascript_parse_text("{sshak_privkey_warn}"),
        "sk"=>$tpl->javascript_parse_text("{sshak_type_sk}"),"nc"=>$tpl->javascript_parse_text("{sshak_no_comment}"));
    echo sshak_style().$tpl->_ENGINE_parse_body($html).sshak_preview_js($zid,$msg);
    return true;
}
// Textarea toujours visible : field_textarea() masque un champ vide derriere « No data: Click here ».
// On l'appelle pour enregistrer le champ dans le collecteur de form_outside (meme id), puis on rend le notre.
function sshak_textarea($tpl,string $name,string $label,string $value,int $rows,string $placeholder):string{
    $tpl->field_textarea($name,$label,$value);
    $id=md5($name.$tpl->suffixid);
    return "<tr><td colspan='2'><label for='$id' class='sshak-lbl'>$label</label>"
        ."<textarea id='$id' name='$id' rows='$rows' class='sshak-ta' placeholder=\"".sshak_h($placeholder)."\" spellcheck='false'>".sshak_h($value)."</textarea></td></tr>";
}
// Apercu en direct : type, empreinte (SubtleCrypto si dispo) et commentaire de la cle collee.
function sshak_preview_js(string $zid,array $msg):string{
    $m=json_encode($msg);
    return "<script>(function(){
var M=$m,ta=document.getElementById('$zid'),box=document.getElementById('sshak-prev');if(!ta||!box){return;}
var T={'ssh-ed25519':'ED25519','ssh-rsa':'RSA','rsa-sha2-256':'RSA','rsa-sha2-512':'RSA','ecdsa-sha2-nistp256':'ECDSA','ecdsa-sha2-nistp384':'ECDSA','ecdsa-sha2-nistp521':'ECDSA','sk-ssh-ed25519@openssh.com':M.sk,'sk-ecdsa-sha2-nistp256@openssh.com':M.sk};
function esc(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML;}
function show(){var v=ta.value.trim();if(v===''){box.style.display='none';return;}box.style.display='flex';
 if(v.indexOf('PRIVATE KEY')>=0){box.className='sshak-prev sshak-prev-bad';box.innerHTML='<span class=\"fa-solid fa-triangle-exclamation\"></span> '+esc(M.priv);return;}
 var f=v.split(/\\s+/),i,t=null,b='',c='';for(i=0;i<f.length;i++){if(T[f[i]]){t=T[f[i]];b=f[i+1]||'';c=f.slice(i+2).join(' ');break;}}
 if(!t||!/^[A-Za-z0-9+\\/]+={0,3}$/.test(b)){box.className='sshak-prev sshak-prev-bad';box.innerHTML=esc(M.bad);return;}
 box.className='sshak-prev';var head='<span class=\"sshak-type\">'+esc(t)+'</span><span style=\"font-weight:600\">'+(c?esc(c):'<i>'+esc(M.nc)+'</i>')+'</span>';
 box.innerHTML=head+'<span id=\"sshak-prev-fp\" style=\"font-family:monospace;color:#6d7a77\"></span>';
 if(window.crypto&&crypto.subtle&&window.isSecureContext){try{var raw=atob(b),u=new Uint8Array(raw.length);for(i=0;i<raw.length;i++){u[i]=raw.charCodeAt(i);}
  crypto.subtle.digest('SHA-256',u).then(function(h){var s='',a=new Uint8Array(h);for(var j=0;j<a.length;j++){s+=String.fromCharCode(a[j]);}var e=document.getElementById('sshak-prev-fp');if(e){e.textContent='SHA256:'+btoa(s).replace(/=+$/,'');}});}catch(e){}}
}
ta.addEventListener('input',show);show();})();</script>";
}
// Cle posee par Artica Meta : lecture seule, ni enregistrement ni suppression.
function sshak_meta_readonly($tpl,array $ligne):string{
    $it=sshak_item($ligne);
    $h=array();
    $h[]="<div class='sshak'><div class='ak-card ak-card-pad'>";
    $h[]="<div style='display:flex;gap:14px;align-items:center;margin-bottom:12px'>".sshak_type_badge($it).sshak_identity($it)."</div>";
    $h[]="<pre style='white-space:pre-wrap;word-break:break-all;font-size:13px'>".sshak_h((string)($ligne["zkey"] ?? ""))."</pre>";
    $h[]="<p><strong>{sshak_account}</strong>: ".($it["account"]===""?"—":sshak_h($it["account"]))."</p>";
    $h[]="<p><strong>{sshak_networks}</strong>: ".(count($it["nets"])==0?"{sshak_any_network}":sshak_h(implode(", ",$it["nets"])))."</p>";
    $h[]="<p><strong>{sshak_restrict_title}</strong>: ".(count($it["opts"])==0?"—":sshak_h(implode(", ",$it["opts"])))."</p>";
    $h[]="</div></div>";
    return sshak_style().sshd_meta_banner($tpl,true,"{sshd_meta_keys_explain}").$tpl->_ENGINE_parse_body(@implode("",$h));
}

// Fenetre « Generate a new key » (formulaire, puis resultat dans le meme hote).
function AuthorizedKeys_create_popup2():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=sshak_fn();
    $form=array();
    $form[]=$tpl->field_text("KeyUsername","{sshak_gen_user}","",true,"{sshak_gen_user_help}");
    $html=array();
    $html[]=$tpl->div_explain("{sshak_generate}||{sshak_gen_explain}");
    $html[]=$tpl->form_outside("",@implode("\n",$form),"","{sshak_create}",
        "LoadAjax('AuthorizedKeysCreatePopup','$page?AuthorizedKeys-create-results=yes&function=$function');if(typeof SshdAkReload==='function'){SshdAkReload();}$function();","AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function AuthorizedKeys_create_results():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $fname=sshak_zip_path();
    if(!is_file($fname)){
        echo $tpl->div_error("{sshak_gen_missing}");
        return false;
    }
    $size=FormatBytes(filesize($fname)/1024);
    $date=$tpl->time_to_date(filemtime($fname),true);
    $h=array();
    $h[]="<div class='sshak'>";
    $h[]="<div class='sshak-gen-ok'><span class='sshak-hero-ico' aria-hidden='true'><i class='fa-solid fa-circle-check'></i></span>";
    $h[]="<div style='flex:1'><div class='sshak-hero-t'>{sshak_gen_ready}</div><div class='sshak-gen-file'><span style='font-family:monospace'>Authorized.zip</span> · $size · $date</div></div>";
    $h[]="<button type='button' class='ak-btn ak-btn-primary ak-btn-lg' OnClick=\"document.location.href='/$page?AuthorizedKeys-create-download=yes';\"><i class='fa-solid fa-download' aria-hidden='true'></i>&nbsp;{sshak_gen_download}</button></div>";
    $h[]="<div class='ak-banner ak-warn' role='alert'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i><span class='ak-banner-m'>{sshak_gen_warn}</span></div>";
    $h[]="<div class='ak-card ak-card-pad' style='margin-top:12px'><div class='ak-sec-t'>{sshak_gen_contents}</div><ul class='sshak-gen-list'>";
    $h[]="<li><i class='fa-solid fa-lock' aria-hidden='true'></i>&nbsp;<span>id_rsa.key</span> — {sshak_gen_priv}</li>";
    $h[]="<li><i class='fa-solid fa-key' aria-hidden='true'></i>&nbsp;<span>id_rsa.pub</span> — {sshak_gen_pub}</li>";
    $h[]="<li><i class='fa-solid fa-file-lines' aria-hidden='true'></i>&nbsp;<span>id_rsa.txt</span> — {sshak_gen_line}</li>";
    $h[]="</ul></div></div>";
    echo sshak_style().$tpl->_ENGINE_parse_body(@implode("",$h));
    return true;
}
// ---- SSHAK widgets end ----

function AuthorizedKeys_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!sshd_is_admin()){return $tpl->js_no_privileges();}
    $function=sshak_fn();
    return $tpl->js_dialog1("{APP_OPENSSH} >> {AuthorizedKeys}","$page?AuthorizedKeys-popup=yes&function=$function",980);
}
function AuthorizedKeys_create_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!sshd_is_admin()){return $tpl->js_no_privileges();}
    $function=sshak_fn();
    return $tpl->js_dialog2("{APP_OPENSSH} >> {sshak_generate}","$page?AuthorizedKeys-create-popup=yes&function=$function",720);
}
function AuthorizedKeys_create_popup():bool{
    $page=CurrentPageName();
    $function=sshak_fn();
    echo "<div id='AuthorizedKeysCreatePopup'></div>
    <script>LoadAjax('AuthorizedKeysCreatePopup','$page?AuthorizedKeys-create-popup2=yes&function=$function')</script>";
    return true;
}
function AuthorizedKeys_add_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!sshd_is_admin()){return $tpl->js_no_privileges();}
    $function=sshak_fn();
    $zmd5=preg_replace('/[^a-f0-9]/','',strtolower((string)($_GET["zmd5"] ?? "")));
    $usernameEnc="";$usernameText="";
    if(isset($_GET["username"])){
        $usernameEnc="&username=".urlencode((string)$_GET["username"]);
        // Pentest 2026-11-04 : username etait recopie sans encodage dans la page (XSS).
        $usernameText=" >> ".req_html(req_html((string)$_GET["username"]));
    }
    $title=$zmd5===""?"{APP_OPENSSH} >> {sshak_import}$usernameText":"{APP_OPENSSH} >> {AuthorizedKeys}$usernameText";
    return $tpl->js_dialog2($title,"$page?AuthorizedKeys-add-popup=yes&zmd5=$zmd5&function=$function$usernameEnc",760);
}
// Confirmation de suppression depuis la fiche (contexte fw.system.users.php compris).
function AuthorizedKeys_add_delete():bool{
    $tpl=new template_admin();
    if(!sshd_is_admin()){return $tpl->js_no_privileges();}
    $zmd5=(string)$_GET["AuthorizedKeys-add-delete"];
    $username=(string)($_GET["username"] ?? "");
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    if(sshd_meta_key_is_meta($q,$zmd5)){return sshd_meta_refuse_js("{sshd_meta_row_locked}");}
    $id=md5($username);
    $userenc=urlencode($username);
    $js=array();
    $js[]="if(document.getElementById('user-start-$id')){LoadAjax('user-start-$id','fw.system.users.php?user-popup=$userenc&function=$function');}";
    $js[]="dialogInstance2.close();";
    $js[]="if(typeof SshdAkReload==='function'){SshdAkReload();}";
    if(strlen($function)>3){$js[]="$function();";}
    return $tpl->js_confirm_delete(sshak_delete_text(sshak_db_row($zmd5)),"AuthorizedKeys-add-delete",$zmd5,@implode("",$js),true);
}
// Confirmation de suppression depuis la liste (remplace l'ancien effacement immediat par GET).
function AuthorizedKeys_del():bool{
    $tpl=new template_admin();
    if(!sshd_is_admin()){return $tpl->js_no_privileges();}
    $zmd5=(string)$_GET["AuthorizedKeys-del-js"];
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    if(sshd_meta_key_is_meta($q,$zmd5)){return sshd_meta_refuse_js("{sshd_meta_row_locked}");}
    $after="if(typeof SshdAkReload==='function'){SshdAkReload();}";
    if(strlen($function)>3){$after.="$function();";}
    return $tpl->js_confirm_delete(sshak_delete_text(sshak_db_row($zmd5)),"AuthorizedKeys-add-delete",$zmd5,$after,true);
}
function sshak_delete_text(array $ligne):string{
    $it=sshak_item($ligne);
    $who=$it["comment"]!==""?sshak_h($it["comment"]):"{sshak_this_key}";
    $acc=$it["account"]!==""?sshak_h($it["account"]):"root";
    $txt=str_replace(array("%s1","%s2"),array("<strong>$who</strong>","<strong>$acc</strong>"),sshak_t("sshak_del_text"));
    return "<strong style='font-size:16px'>{sshak_del_title}</strong><br>$txt";
}
function AuthorizedKeys_add_download():bool{
    if(!sshd_is_admin()){header("HTTP/1.1 403 Forbidden");return false;}
    $zmd5=preg_replace('/[^a-f0-9]/','',strtolower((string)$_GET["AuthorizedKeys-add-download"]));
    $ligne=sshak_db_row($zmd5);
    $zippackage=base64_decode((string)($ligne["zippackage"] ?? ""));
    if($zippackage===false || strlen($zippackage)==0){header("HTTP/1.1 404 Not Found");return false;}
    $eMail=preg_replace('/[^A-Za-z0-9@._-]/','',(string)($ligne["email"] ?? ""));
    header('Content-type: application/zip');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"authorizedkeys-$eMail.zip\"");
    header("Pragma: public");
    header("Cache-Control: no-store, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    header("Content-Length: ".strlen($zippackage));
    if(ob_get_level()>0){ob_clean();}
    flush();
    echo $zippackage;
    return admin_tracks("Downloaded Public SSH Key for $eMail");
}
// Le zip genere est servi par la console (droit verifie), plus par un lien direct vers le fichier.
function AuthorizedKeys_create_download():bool{
    if(!sshd_is_admin()){header("HTTP/1.1 403 Forbidden");return false;}
    $fname=sshak_zip_path();
    if(!is_file($fname)){header("HTTP/1.1 404 Not Found");return false;}
    header('Content-type: application/zip');
    header("Content-Disposition: attachment; filename=\"Authorized.zip\"");
    header("Cache-Control: no-store, must-revalidate");
    header("Content-Length: ".filesize($fname));
    if(ob_get_level()>0){ob_clean();}
    flush();
    readfile($fname);
    return admin_tracks("Downloaded the generated SSH key package");
}
function AuthorizedKeys_create_save():bool{
    $tpl=new template_admin();
    if(!sshd_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $tpl->CLEAN_POST();
    $username=trim((string)$_POST["KeyUsername"]);
    if(!preg_match('/^[A-Za-z0-9_][A-Za-z0-9_.-]{0,31}(@[A-Za-z0-9.-]{1,253})?$/',$username)){
        echo $tpl->post_error("{sshak_gen_user_invalid}");
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/genkey/".rawurlencode($username)));
    if(!is_object($json)){echo $tpl->post_error("{sshak_daemon_unreachable}");return false;}
    if(empty($json->Status)){
        echo $tpl->post_error((string)($json->Error ?? "error"));
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks("Created a SSH Key for $username");
}
function sshak_valid_net(string $l):bool{
    $p=explode("/",$l,2);
    if(filter_var($p[0],FILTER_VALIDATE_IP)===false){return false;}
    if(count($p)==1){return true;}
    if(!ctype_digit($p[1])){return false;}
    $max=strpos($p[0],":")!==false?128:32;
    return intval($p[1])<=$max;
}
// Enregistrement : met a jour la ligne existante (compte, adresse e-mail et paquet genere conserves) au lieu
// de la supprimer et la recreer ; si le texte de la cle change, l'ancienne ligne est retiree.
function AuthorizedKeys_add_save():bool{
    $tpl=new template_admin();
    if(!sshd_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $tpl->CLEAN_POST();
    $zkey=trim(url_decode_special_tool($_POST["zkey"]));
    if(strpos($zkey,"PRIVATE KEY")!==false){echo $tpl->post_error("{sshak_privkey_warn}");return false;}
    if(!AuthorizedKeys_is_valid($zkey)){echo $tpl->post_error("{sshak_key_invalid}");return false;}
    $orig=preg_replace('/[^a-f0-9]/','',strtolower((string)($_POST["AkOrig"] ?? "")));
    $account=trim((string)($_POST["AkAccount"] ?? ""));
    if($account!=="" && !preg_match('/^[A-Za-z0-9_][A-Za-z0-9_.-]{0,31}$/',$account)){
        echo $tpl->post_error("{sshak_account_invalid}");return false;
    }
    $nets=array();
    foreach(sshak_nets((string)($_POST["limitaddr"] ?? "")) as $n){
        if(!sshak_valid_net($n)){echo $tpl->post_error(str_replace("%s",$n,sshak_t("sshak_networks_invalid")));return false;}
        $nets[]=$n;
    }
    $limitaddr=implode("\n",$nets);
    $Features=array();
    foreach ($_POST as $key=>$value){
        if (!preg_match("#^FEATURE-(.+)#",$key,$re)){continue;}
        if(intval($value)==0){continue;}
        $Features[]=$re[1];
    }
    $limitfeatures=@implode(",",sshak_opts(implode(",",$Features)));
    $md5=md5($zkey);
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    // Une cle posee par une politique Meta « SSH » (meme ligne ou meme materiel) n'est ni remplacee ni doublee.
    if(sshd_meta_key_is_meta($q,$md5) || sshd_meta_key_material_is_meta($q,$zkey) || ($orig!=="" && sshd_meta_key_is_meta($q,$orig))){
        return sshd_meta_refuse_post("{sshd_meta_row_locked}");
    }
    if($orig!=="" && $orig!==$md5){
        $q->QUERY_SQL_PARAMS("DELETE FROM sshd_authorizedkeys WHERE `zmd5`=?",array($orig));
    }
    if(count(sshak_db_row($md5))>0){
        $q->QUERY_SQL_PARAMS("UPDATE sshd_authorizedkeys SET `zkey`=?,`limitaddr`=?,`limitfeatures`=?,`username`=? WHERE `zmd5`=?",
            array($zkey,$limitaddr,$limitfeatures,$account,$md5));
    }else{
        $q->QUERY_SQL_PARAMS("INSERT INTO sshd_authorizedkeys (`zmd5`,`zkey`,`limitaddr`,`limitfeatures`,`username`) VALUES (?,?,?,?,?)",
            array($md5,$zkey,$limitaddr,$limitfeatures,$account));
    }
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    admin_tracks_post("Save the Authorized Key md5=$md5 (account ".($account===""?"automatic":$account).") in OpenSSH service");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return true;
}
