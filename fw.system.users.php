<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
// awc_table_style(): feuille de style partagee des tableaux d'entites
// (cf. fw.statistics.proxies.php, fw.firewall.alerts.php).
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// Pentest 2026-11-04 : les noms d'utilisateur systeme (user-*, member) et groupid-js/uid etaient recopies sans encodage dans la page (XSS).
foreach(array("user-js","user-tab","user-start","user-popup","user-gecos-js","user-gecos-popup","user-shell-js","user-shell-popup","user-password-js","user-password-popup","user-sudo","user-su-js","user-ssh-switch","member") as $k){
    if(isset($_GET[$k])){$_GET[$k]=preg_replace('/[^A-Za-z0-9_.@$-]/','',(string)$_GET[$k]);}
}
foreach(array("groupid-js","uid") as $k){
    if(isset($_GET[$k])){$_GET[$k]=intval($_GET[$k]);}
}
// Audit 2026-11-17 (9) : function, function2, functionSrc et md partent bruts dans du JavaScript
// (OnClick, $jsafter, $('#md').remove()). Meme liste blanche que table().
foreach(array("function","function2","functionSrc") as $k){
    if(isset($_GET[$k])){$_GET[$k]=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET[$k]);}
}
if(isset($_GET["md"])){$_GET["md"]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET["md"]);}
if(isset($_GET["ssh-switch"])){groupid_ssh();exit;}
if(isset($_GET["reconfigure-js"])){reconfigure_all();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["groupid-js"])){groupid_js();exit;}
if(isset($_GET["groupid-start"])){groupid_start();exit;}
if(isset($_GET["groupid-tab"])){groupid_tab();exit;}
if(isset($_GET["groupid-popup"])){groupid_popup();exit;}
if(isset($_GET["groupid-members"])){groupid_members();exit;}
if(isset($_GET["groupid-search"])){groupid_search();exit;}
if(isset($_GET["groupid-buttons"])){groupid_buttons();exit;}
if(isset($_GET["groupid-useradd-js"])){groupid_useradd_js();exit;}
if(isset($_GET["groupid-useradd-popup"])){groupid_useradd_popup();exit;}
if(isset($_POST["groupid-useradd"])){groupid_useradd_save();exit;}
if(isset($_GET["groupid-userunlink-js"])){groupid_userunlink_js();exit;}
if(isset($_GET["groupid-userlink-js"])){groupid_link_js();exit;}
if(isset($_GET["groupid-userlink-popup"])){groupid_link_popup();exit;}
if(isset($_GET["groupid-userlink-search"])){groupid_link_search();exit;}
if(isset($_GET["groupid-userlink-perform"])){groupid_link_perform();exit;}
if(isset($_GET["groupid-delete-js"])){groupid_delete_js();exit;}
if(isset($_POST["groupid-delete"])){groupid_delete_perform();exit;}
if(isset($_GET["newgroup-created"])){groupid_created();exit;}
if(isset($_GET["user-su-js"])){user_su_js();exit;}
if(isset($_GET["AuthorizedKeys-create-js"])){AuthorizedKeys_create_js();exit;}

if(isset($_GET["user-js"])){user_js();exit;}
if(isset($_GET["user-tab"])){user_tab();exit;}
if(isset($_GET["user-start"])){user_start();exit;}
if(isset($_GET["user-popup"])){user_popup();exit;}

if(isset($_GET["user-shell-js"])){user_shell_js();exit;}
if(isset($_GET["user-shell-popup"])){user_shell_popup();exit;}
if(isset($_POST["user-shell"])){user_shell_save();exit;}

if(isset($_GET["user-gecos-js"])){user_gecos_js();exit;}
if(isset($_GET["user-gecos-popup"])){user_gecos_popup();exit;}
if(isset($_POST["user-gecos"])){user_gecos_save();exit;}
if(isset($_GET["user-ssh-switch"])){user_ssh_switch();exit;}

if(isset($_GET["user-password-js"])){user_password_js();exit;}
if(isset($_GET["user-password-popup"])){user_password_popup();exit;}
if(isset($_POST["user-password"])){user_password_save();exit;}
if(isset($_GET["user-delete-js"])){user_delete_js();exit;}
if(isset($_POST["user-delete"])){user_delete_perform();exit;}
if(isset($_GET["user-sudo"])){user_sudo_popup();exit;}
if(isset($_GET["user-sudo-search"])){user_sudo_search();exit;}
if(isset($_GET["user-sudo-buttons"])){user_sudo_buttons();exit;}
if(isset($_GET["user-sudo-id"])){user_sudo_id_js();exit;}
if(isset($_GET["user-sudo-id-popup"])){user_sudo_id_popup();exit;}
if(isset($_POST["user-sudo-id"])){user_sudo_id_save();exit;}
if(isset($_GET["user-sudo-id-del"])){user_sudo_id_del();exit;}






if(isset($_GET["groupid-sudo"])){groupid_sudo();exit;}
if(isset($_GET["groupid-sudo-search"])){groupid_sudo_table();exit;}
if(isset($_GET["groupid-sudo-buttons"])){groupid_sudo_buttons();exit;}
if(isset($_GET["groupid-sudo-id"])){groupid_sudo_id_js();exit;}
if(isset($_GET["groupid-sudo-id-popup"])){groupid_sudo_id_popup();exit;}
if(isset($_GET["groupid-sudo-id-del"])){groupid_sudo_id_del();exit;}
if(isset($_POST["groupid-sudo-id"])){groupid_sudo_id_save();exit;}


if(isset($_GET["new-group-js"])){groupid_create_js();exit;}
if(isset($_GET["new-group-popup"])){groupid_create_popup();exit;}
if(isset($_POST["newgroup"])){groupid_create_perform();exit;}

if(isset($_GET["search"])){table();exit;}

page();

/*
 * Socle commun (audit 2026-11-17). Toute action qui modifie le systeme verifie le droit
 * AsSystemAdministrator COTE SERVEUR : le parametre "AsSystemAdministrator" des widgets ne fait que
 * griser un bouton. Les ecritures passent par des routes POST JSON du demon (le corps n'est jamais
 * journalise, contrairement a l'URL) ; un compte, groupe ou regle tenu par Artica Meta est refuse par
 * le demon (423 managed_by_meta) et affiche en lecture seule ici.
 */
function sysusers_is_admin():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemAdministrator;
}
function sysusers_deny_post():bool{
    $tpl=new template_admin();
    echo $tpl->post_error("{ERROR_NO_PRIVS2}");
    return false;
}
function sysusers_deny_js():bool{
    $tpl=new template_admin();
    $tpl->js_error("{ERROR_NO_PRIVS2}");
    return false;
}
function sysusers_js_fail($error):bool{
    $tpl=new template_admin();
    $tpl->js_error(sysusers_esc($error));
    return false;
}
/** Texte systeme (gecos, home, shell, nom) vers HTML ; les accolades ne deviennent jamais des jetons. */
function sysusers_esc($s):string{
    $s=htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,"ISO-8859-1");
    return str_replace(array("{","}"),array("&#123;","&#125;"),$s);
}
/** POST JSON vers le demon, enveloppe {Status,Error}. Corps vide = objet JSON {}. */
function sysusers_post($endpoint,$payload,$fallback):array{
    if(!is_array($payload) || count($payload)==0){$payload=new stdClass();}
    return api_result($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON($endpoint,$payload),$fallback);
}
/**
 * Mot de passe tel que saisi : lu AVANT CLEAN_POST, qui le rogne (audit 14). Le decodage du formulaire
 * (url_decode_special_tool) reste celui de CLEAN_POST.
 */
function sysusers_raw_password($key):string{
    if(!isset($_POST[$key]) || is_array($_POST[$key])){return "";}
    $v=(string)$_POST[$key];
    if(function_exists("url_decode_special_tool")){$v=url_decode_special_tool($v);}
    return $v;
}
/** Regle du demon : 8 a 128 caracteres, une seule ligne. "" si correct, sinon le jeton d'erreur. */
function sysusers_password_error($password):string{
    $len=strlen($password);
    if($len<8 || $len>128 || preg_match('/[\r\n]/',$password)){return "{systemusers_password_rule}";}
    return "";
}
/** Programme d'une regle sudo : ALL, ou un chemin absolu sans espace , : = \ # " ' (regle du demon). */
function sysusers_sudo_binary_ok($binary):bool{
    if($binary==="ALL"){return true;}
    return preg_match('#^/[^\s,:=\\\\\#"\']+$#',$binary)===1;
}
/** Shells acceptes par le demon ; le shell courant hors liste reste propose et choisi (audit 14). */
function sysusers_shells($current=""):array{
    $list=array("/bin/bash","/bin/sh","/usr/bin/bash","/bin/rbash","/usr/bin/rbash","/bin/dash",
        "/usr/bin/dash","/bin/tcsh","/usr/bin/tcsh","/bin/false","/usr/bin/false","/sbin/nologin",
        "/usr/sbin/nologin");
    $shells=array();
    $current=trim((string)$current);
    if($current!=="" && !in_array($current,$list,true)){$shells[$current]=$current;}
    foreach($list as $s){$shells[$s]=$s;}
    return $shells;
}
/** Groupes autorises a SSH (cle SSHAllowGroups, tableau PHP serialise). */
function sysusers_ssh_groups():array{
    $raw=(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SSHAllowGroups");
    $arr=@unserialize($raw,array("allowed_classes"=>false));
    if(!is_array($arr)){return array();}
    $out=array();
    foreach($arr as $g){
        $g=trim((string)$g);
        if($g===""){continue;}
        $out[$g]=true;
    }
    return $out;
}
/**
 * GET /system/users/list, decode UNE fois par requete (audit 21 : UserInfo, GroupInfo, AllMembers,
 * GroupIDFromName l'appelaient chacun). null en cas d'erreur ; le message est dans SYSUSERS_LIST_ERROR.
 */
function sysusers_list(){
    if(array_key_exists("SYSUSERS_LIST",$GLOBALS)){return $GLOBALS["SYSUSERS_LIST"];}
    $GLOBALS["SYSUSERS_LIST"]=null;
    $GLOBALS["SYSUSERS_LIST_ERROR"]="{error}";
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/users/list"));
    if(!is_object($data)){return null;}
    if(empty($data->Status)){
        if(property_exists($data,"Error") && strlen(trim((string)$data->Error))>0){
            $GLOBALS["SYSUSERS_LIST_ERROR"]=(string)$data->Error;
        }
        return null;
    }
    if(!property_exists($data,"UsersAndGroups") || !is_object($data->UsersAndGroups)){
        $GLOBALS["SYSUSERS_LIST_ERROR"]="{no_data}";
        return null;
    }
    if(!isset($data->UsersAndGroups->users) || !is_array($data->UsersAndGroups->users)){$data->UsersAndGroups->users=array();}
    if(!isset($data->UsersAndGroups->groups) || !is_array($data->UsersAndGroups->groups)){$data->UsersAndGroups->groups=array();}
    $GLOBALS["SYSUSERS_LIST"]=$data;
    return $data;
}
/** Message d'erreur de sysusers_list() pret pour le HTML (un jeton {x} reste un jeton). */
function sysusers_list_error():string{
    $err=(string)($GLOBALS["SYSUSERS_LIST_ERROR"] ?? "{error}");
    if(preg_match('/^\{[A-Za-z0-9_]+\}$/',$err)){return $err;}
    return sysusers_esc($err);
}
function sysusers_users():array{
    $data=sysusers_list();
    if($data===null){return array();}
    return $data->UsersAndGroups->users;
}
function sysusers_groups():array{
    $data=sysusers_list();
    if($data===null){return array();}
    return $data->UsersAndGroups->groups;
}
/** Membres d'un groupe, sans doublon ni entree vide (audit 19). */
function sysusers_group_members($jsClass):array{
    $out=array();
    if(!isset($jsClass->members) || !is_array($jsClass->members)){return $out;}
    foreach($jsClass->members as $m){
        $m=trim((string)$m);
        if($m===""){continue;}
        $out[$m]=$m;
    }
    return array_values($out);
}
/** Verrou de la politique Meta "Utilisateurs systeme" pose sur ce noeud. */
function sysusers_meta_managed():bool{
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemUsersMetaManaged"))==1){return true;}
    $data=sysusers_list();
    return $data!==null && !empty($data->MetaManaged);
}
function sysusers_meta_badge():string{
    return "<span class='ak-tag ak-info' title=\"{systemusers_meta_locked}\"><i class='fa-solid fa-lock'></i>&nbsp;Artica Meta</span>";
}
function sysusers_locked_badge():string{
    return "<span class='ak-tag ak-warn'><i class='fa-solid fa-user-lock'></i>&nbsp;{locked}</span>";
}
function sysusers_meta_banner():string{
    if(!sysusers_meta_managed()){return "";}
    return "<div class='ak-banner ak-info' role='status' style='margin:10px 0'><i class='fa-solid fa-lock'></i>"
        ."<span class='ak-grow'>{systemusers_meta_banner}</span></div>";
}
/** name => true pour les comptes tenus par Artica Meta. */
function sysusers_user_meta_map():array{
    $out=array();
    foreach(sysusers_users() as $u){
        if(!empty($u->meta)){$out[(string)$u->name]=true;}
    }
    return $out;
}
function sysusers_user_by_uid($uid):array{
    foreach(sysusers_users() as $u){
        if(intval($u->uid)==intval($uid)){return UserInfo((string)$u->name);}
    }
    return array();
}
function sysusers_group_is_meta($gid):bool{
    $Info=GroupInfo($gid);
    return !empty($Info["meta"]);
}
function reconfigure_all():bool{
    $tpl=new template_admin();
    if(!sysusers_is_admin()){return sysusers_deny_js();}
    $r=sysusers_post("/system/users/reconfigure",array(),"Unable to apply the system users configuration");
    if(!$r["ok"]){
        admin_tracks("Apply the system users configuration failed: {$r["error"]}");
        return sysusers_js_fail($r["error"]);
    }
    admin_tracks("Apply the system users configuration");
    return $tpl->js_ok("{success}");
}
function groupid_create_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog10("{new_group}", "$page?new-group-popup=yes&function=$function",550);
}
function groupid_create_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $page=CurrentPageName();
    unset($_SESSION["SYSTEM_USERS_GROUP_CREATED"]);
    unset($_SESSION["SYSTEM_USERS_GROUP_CREATED_NAME"]);
    $form[]=$tpl->field_text("newgroup","{groupname}","",true,"{systemusers_group_name_rule}");
    $jsafter[]="dialogInstance10.close();";
    $jsafter[]="$function();";
    $jsafter[]="Loadjs('$page?newgroup-created=yes&function=$function');";
    echo $tpl->form_outside("",$form,"","{add}",@implode(";",$jsafter),"AsSystemAdministrator");
    return true;
}
function groupid_created():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    // Audit 15 : le GID rendu par le demon, pas le nom urlencode recherche ensuite.
    $gid=intval($_SESSION["SYSTEM_USERS_GROUP_CREATED"] ?? 0);
    $name=(string)($_SESSION["SYSTEM_USERS_GROUP_CREATED_NAME"] ?? "");
    unset($_SESSION["SYSTEM_USERS_GROUP_CREATED"]);
    unset($_SESSION["SYSTEM_USERS_GROUP_CREATED_NAME"]);
    if($gid<=0 && $name!==""){$gid=GroupIDFromName($name);}
    if($gid<=0){
        return false;
    }
    header("content-type: application/x-javascript");
    echo "Loadjs('$page?groupid-js=$gid&function=$function');";
    return true;
}


function groupid_create_perform():bool{
    $tpl=new template_admin();
    if(!sysusers_is_admin()){return sysusers_deny_post();}
    $tpl->CLEAN_POST();
    // Regle du demon : ^[a-z_][a-z0-9_-]{0,31}$ (audit 15 : le nom n'etait pas valide).
    $name=strtolower(trim((string)$_POST["newgroup"]));
    $name=preg_replace('/\s+/',"-",$name);
    if(!preg_match('/^[a-z_][a-z0-9_-]{0,31}$/',$name)){
        echo $tpl->post_error("{systemusers_group_name_rule}");
        return false;
    }
    $r=sysusers_post("/system/group/create",array("name"=>$name),"Unable to create the system group $name");
    if(!$r["ok"]){
        admin_tracks("Create the system group $name failed: {$r["error"]}");
        echo $tpl->post_error($r["error"]);
        return false;
    }
    $gid=intval($r["json"]["Gid"] ?? 0);
    $_SESSION["SYSTEM_USERS_GROUP_CREATED"]=$gid;
    $_SESSION["SYSTEM_USERS_GROUP_CREATED_NAME"]=$name;
    return admin_tracks("Create the system group $name (gid $gid)");
}
function user_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=$_GET["user-js"];
    $function=$_GET["function"];
    return $tpl->js_dialog8($id, "$page?user-tab=$id&function=$function");
}
/**
 * Check the {Status,Info,Error} envelope returned by articarest.
 * @param string $raw      raw REST_API*() answer
 * @param string $fallback error message when the daemon gives none
 * @return array array("ok"=>bool,"error"=>string,"json"=>array|null)
 */
function api_result($raw,$fallback):array{
    $json=json_decode($raw,true);
    if(!is_array($json)){
        return array("ok"=>false,"error"=>"$fallback: no answer from the API","json"=>null);
    }
    if(!isset($json["Status"]) or !$json["Status"]){
        $error="";
        if(isset($json["Error"])){$error=trim(strval($json["Error"]));}
        if(strlen($error)==0){$error=$fallback;}
        return array("ok"=>false,"error"=>$error,"json"=>$json);
    }
    return array("ok"=>true,"error"=>null,"json"=>$json);
}
/**
 * Sudo rules of one user/group id. $kind is "users" or "groups".
 * REST: GET /system/sudoers/{kind}/list/{gpid}
 * @return array rows keyed ID/gpid/binary/binmode, as the sudoers.db columns were
 */
function api_sudo_rules($kind,$gpid):array{
    $results=api_result($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sudoers/$kind/list/".intval($gpid)),
        "Unable to list the sudo rules");
    if(!$results["ok"]){
        VERBOSE("api_sudo_rules($kind,$gpid): {$results["error"]}",__LINE__);
        return array();
    }
    $json=$results["json"];
    if(!isset($json["Rules"]) or !is_array($json["Rules"])){return array();}
    return $json["Rules"];
}
/**
 * One sudo rule. REST: GET /system/sudoers/{kind}/rule/{id}
 * @return array|null the row, or null when the id is unknown
 */
function api_sudo_rule($kind,$id){
    $results=api_result($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sudoers/$kind/rule/".intval($id)),
        "Unable to read the sudo rule");
    if(!$results["ok"]){
        VERBOSE("api_sudo_rule($kind,$id): {$results["error"]}",__LINE__);
        return null;
    }
    $json=$results["json"];
    if(!isset($json["Rule"]) or !is_array($json["Rule"])){return null;}
    return $json["Rule"];
}
/**
 * Add ($id=0) or update a sudo rule; the daemon resolves the program path and
 * regenerates /etc/sudoers itself. REST: POST /system/sudoers/{kind}/save
 */
function api_sudo_save($kind,$id,$gpid,$binary,$binmode):array{
    $payload=array("ID"=>intval($id),"gpid"=>intval($gpid),"binary"=>$binary,"binmode"=>intval($binmode));
    return api_result($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/sudoers/$kind/save",$payload),
        "Unable to save the sudo rule");
}
/** REST: DELETE /system/sudoers/{kind}/rule/{id} */
function api_sudo_delete($kind,$id):array{
    return api_result($GLOBALS["CLASS_SOCKETS"]->REST_API_DELETE("/system/sudoers/$kind/rule/".intval($id)),
        "Unable to remove the sudo rule");
}
/**
 * SSH access state of a system account. REST: GET /ssh/allowuser/{username}
 * @return array array("exists"=>bool,"enabled"=>bool)
 */
function api_ssh_allowuser($username):array{
    $results=api_result($GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/allowuser/".urlencode($username)),
        "Unable to read the SSH access of $username");
    if(!$results["ok"]){
        VERBOSE("api_ssh_allowuser($username): {$results["error"]}",__LINE__);
        return array("exists"=>false,"enabled"=>false);
    }
    $json=$results["json"];
    return array("exists"=>!empty($json["Exists"]),"enabled"=>!empty($json["Enabled"]));
}
/**
 * Flip the SSH access of an account. The daemon does the whole
 * read-modify-write and returns the new state, so no direction to compute here.
 * REST: POST /ssh/allowuser/{username}/toggle (body {})
 */
function api_ssh_allowuser_toggle($username):array{
    return sysusers_post("/ssh/allowuser/".urlencode($username)."/toggle",array(),
        "Unable to change the SSH access of $username");
}
/**
 * Public keys of one account. REST: GET /ssh/authorizedkeys/user/{username}
 * @return array rows keyed zmd5/email/username
 */
function api_ssh_authorizedkeys($username):array{
    $results=api_result($GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/authorizedkeys/user/".urlencode($username)),
        "Unable to list the SSH keys of $username");
    if(!$results["ok"]){
        VERBOSE("api_ssh_authorizedkeys($username): {$results["error"]}",__LINE__);
        return array();
    }
    $json=$results["json"];
    if(!isset($json["Keys"]) or !is_array($json["Keys"])){return array();}
    return $json["Keys"];
}
function groupid_delete_js():bool{
    if(!sysusers_is_admin()){return sysusers_deny_js();}
    $id=intval($_GET["groupid-delete-js"]);
    $tpl=new template_admin();
    $md=$_GET["md"];
    $Info=GroupInfo($id);
    return $tpl->js_confirm_delete(sysusers_esc($Info["name"] ?? "gid $id"),"groupid-delete",$id,"$('#$md').remove();if(typeof UsrSysRefresh==='function'){UsrSysRefresh();}");
}
function groupid_delete_perform():bool{
    $tpl=new template_admin();
    if(!sysusers_is_admin()){return sysusers_deny_post();}
    $id=intval($_POST["groupid-delete"]);
    $Info=GroupInfo($id);
    $name=(string)($Info["name"] ?? "");
    $label=$name===""?"gid $id":"$name (gid $id)";
    // Le demon purge les regles sudo et SSHAllowGroups du groupe, puis reconstruit.
    $r=sysusers_post("/system/group/delete",array("gid"=>$id),"Unable to remove the system group $label");
    if(!$r["ok"]){
        admin_tracks("Delete the system group $label failed: {$r["error"]}");
        echo $tpl->post_error($r["error"]);
        return false;
    }
    return admin_tracks("Delete the system group $label");
}
function groupid_js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $id=$_GET["groupid-js"];
    $function=$_GET["function"];
    $ar=GroupInfo($id);
	return $tpl->js_dialog($ar["name"], "$page?groupid-tab=$id&function=$function");
}
function user_sudo_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $user=$_GET["user-sudo"];
    $functionSrc="";
    $function2="";
    if(isset($_GET["functionSrc"])){
        $functionSrc=$_GET["functionSrc"];
    }
    if(isset($_GET["function2"])){
        $function2=$_GET["function2"];
    }
    $UserInfo=UserInfo($user);
    $id=$UserInfo["uid"];

    echo "<div id='buttons-sudoers-$id' style='margin:5px'></div>";
    echo $tpl->search_block($page,"","","","&user-sudo-search=$id&functionSrc=$functionSrc&function2=$function2");
    return true;
}
function groupid_sudo():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["groupid-sudo"]);
    $functionSrc=$_GET["functionSrc"];
    $function2=$_GET["function2"];
    echo "<div id='buttons-sudoers-$id' style='margin:5px'></div>";
    echo $tpl->search_block($page,"","","","&groupid-sudo-search=$id&functionSrc=$functionSrc&function2=$function2");
    return true;
}
/**
 * Motif de recherche des tableaux de cette page.
 * Les quatre listes construisaient "*terme*" puis remplacaient l'asterisque par
 * ".*?" et injectaient le tout TEL QUEL dans preg_match: une parenthese, un
 * crochet ou un slash dans le terme rendait la regex invalide, preg_match
 * renvoyait false et la liste se vidait sans le moindre message.
 * Rend "" quand il n'y a pas de filtre.
 */
function sysusers_search_regex():string{
    $raw=trim((string)($_GET["search"] ?? ""));
    if(strlen($raw)<1){return "";}
    $parts=explode("*",$raw);
    foreach($parts as $i=>$piece){$parts[$i]=preg_quote($piece,"#");}
    return "#".implode(".*?",$parts)."#i";
}

/**
 * Une regle sudo (programme autorise + exigence de mot de passe).
 * Les deux tableaux -- regles de groupe et regles d'utilisateur -- etaient
 * ecrits deux fois a l'identique; ils partagent desormais cette ligne.
 */
function sudo_rule_row($ligne,$delparam,$readonly=false):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $binary=(string)($ligne["binary"] ?? "");
    $ID=intval($ligne["ID"] ?? 0);
    $md=md5(serialize($ligne));
    // Regle Artica Meta : lecture seule (le demon refuse sa suppression, 423).
    if(!empty($ligne["meta"])){
        $delete=sysusers_meta_badge();
    }elseif($readonly){
        $delete="";
    }else{
        $delete=$tpl->icon_delete("Loadjs('$page?$delparam=$ID&md=$md');","AsSystemAdministrator");
    }
    // binmode vaut 0 ou 1; toute autre valeur lisait une cle absente du tableau
    // de correspondance et emettait un avertissement par ligne.
    $nopass=(intval($ligne["binmode"] ?? 0)==1);
    $binmode=$nopass
        ? "<span class='awc-badge' style='background:#865300'>{AllowNoPassword}</span>"
        : "<span class='awc-badge awc-badge-on'>{mandatory}</span>";
    $label=($binary=="*" || $binary=="ALL")?"{all}":sysusers_esc($binary);

    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='".ico_terminal."'></i></span>";
    $h.="<div class='awc-name'>$label</div>";
    $h.="</div></td>";
    $h.="<td class='awc-nowrap'>$binmode</td>";
    $h.="<td><div class='awc-acts'>$delete</div></td>";
    $h.="</tr>";
    return $h;
}

/**
 * Un membre systeme dans une liste de selection ou de composition de groupe.
 * $sub est le sous-titre (gecos ou repertoire personnel), vide cote selection.
 */
function sysusers_member_row($md,$nameHtml,$sub,$action):string{
    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='".ico_member."'></i></span>";
    $h.="<div><div class='awc-name'>$nameHtml</div>";
    if(trim((string)$sub)!==""){$h.="<div class='awc-meta'>".htmlspecialchars((string)$sub)."</div>";}
    $h.="</div></div></td>";
    $h.="<td><div class='awc-acts' style='opacity:1'>$action</div></td>";
    $h.="</tr>";
    return $h;
}

function groupid_sudo_table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["groupid-sudo-search"]);
    $functionSrc=$_GET["functionSrc"];
    $function2=$_GET["function2"];
    $function=$_GET["function"];
    $results=api_sudo_rules("groups",$id);
    $readonly=sysusers_group_is_meta($id);
    $html[]=sysusers_meta_banner();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'>";
    $html[]="<thead><tr>";
    $html[]="<th>{program}</th>";
    $html[]="<th class='awc-nowrap'>{password}</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody id='gsudo-rows'>";

    // preg_quote(): le terme saisi etait injecte TEL QUEL dans la regex, ou une
    // parenthese ou un crochet suffisait a la rendre invalide -- preg_match
    // renvoyait alors false et la liste se vidait sans explication.
    $search=sysusers_search_regex();

    $c=0;
    foreach ($results as $ligne) {
        if($search!=="" && !preg_match($search,serialize($ligne))){continue;}
        $html[]=sudo_rule_row($ligne,"groupid-sudo-id-del",$readonly);
        $c++;
    }
    if($c==0){
        $html[]="<tr><td colspan='3' class='awc-empty'>{no_data}</td></tr>";
    }

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $c</span>"
           ."<span class='awc-pg' id='gsudo-pager'></span></div>";
    $html[]="</div></div>";

    // Le bloc <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n"
           ."LoadAjax('buttons-sudoers-$id','$page?groupid-sudo-buttons=$id&function=$function');\n</script>";

    // awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"gsudo-rows","gsudo-pager");

return true;
}
function groupid_sudo_buttons():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["groupid-sudo-buttons"]);
    $function=$_GET["function"];
    // Groupe tenu par Artica Meta : pas d'ajout de regle.
    if(sysusers_group_is_meta($id)){return true;}
    $topbuttons[] = array("Loadjs('$page?groupid-sudo-id=0&gpid=$id&function=$function');", ico_plus, "{new_rule}");
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}
function user_sudo_buttons():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["user-sudo-buttons"]);
    $function=$_GET["function"];
    // Compte tenu par Artica Meta : pas d'ajout de regle.
    $owner=sysusers_user_by_uid($id);
    if(!empty($owner["meta"])){return true;}
    $topbuttons[] = array("Loadjs('$page?user-sudo-id=0&gpid=$id&function=$function');", ico_plus, "{new_rule}");
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}
function groupid_sudo_id_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["groupid-sudo-id"]);
    $function=$_GET["function"];
    $gpid=intval($_GET["gpid"]);
    $js="Loadjs('$page?groupid-sudo-id-popup=$id&gpid=$gpid&function=$function');";
   return $tpl->js_dialog12("{rule} #$id",$js,550);
}
function user_sudo_id_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["user-sudo-id"]);
    $function=$_GET["function"];
    $gpid=intval($_GET["gpid"]);
    $js="Loadjs('$page?user-sudo-id-popup=$id&gpid=$gpid&function=$function');";
    return $tpl->js_dialog12("{rule} #$id",$js,550);
}
function user_sudo_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["user-sudo-search"]);
    $function=$_GET["function"];
    $function2=$_GET["function2"];
    $functionSrc=$_GET["functionSrc"];
    $results=api_sudo_rules("users",$id);
    $owner=sysusers_user_by_uid($id);
    $readonly=!empty($owner["meta"]);
    $html[]=sysusers_meta_banner();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'>";
    $html[]="<thead><tr>";
    $html[]="<th>{program}</th>";
    $html[]="<th class='awc-nowrap'>{password}</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody id='usudo-rows'>";

    // preg_quote(): le terme saisi etait injecte TEL QUEL dans la regex, ou une
    // parenthese ou un crochet suffisait a la rendre invalide -- preg_match
    // renvoyait alors false et la liste se vidait sans explication.
    $search=sysusers_search_regex();

    $c=0;
    foreach ($results as $ligne) {
        if($search!=="" && !preg_match($search,serialize($ligne))){continue;}
        $html[]=sudo_rule_row($ligne,"user-sudo-id-del",$readonly);
        $c++;
    }
    if($c==0){
        $html[]="<tr><td colspan='3' class='awc-empty'>{no_data}</td></tr>";
    }

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $c</span>"
           ."<span class='awc-pg' id='usudo-pager'></span></div>";
    $html[]="</div></div>";

    // Le bloc <script> est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prendrait chaque accolade JavaScript pour un jeton.
    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n"
           ."LoadAjax('buttons-sudoers-$id','$page?user-sudo-buttons=$id&function=$function');\n</script>";

    // awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"usudo-rows","usudo-pager");
    return true;
}
function groupid_sudo_id_popup():bool{
    $tpl=new template_admin();
    $id=intval($_GET["groupid-sudo-id-popup"]);
    $gpid=intval($_GET["gpid"]);
    $function=$_GET["function"];

    $ligne["binary"]="/bin/ls";
    $ligne["binmode"]=0;
    $btn="{add}";

    if($id>0){
        $rule=api_sudo_rule("groups",$id);
        if(is_array($rule)){
            $ligne=$rule;
            $btn="{apply}";
        }
    }
    $form[]=$tpl->field_hidden("groupid-sudo-id",$id);
    $form[]=$tpl->field_hidden("gpid",$gpid);
    $form[]=$tpl->field_text("binary","{program}",$ligne["binary"],true,"{systemusers_sudo_binary_explain}");
    $form[]=$tpl->field_checkbox("binmode","{AllowNoPassword}",intval($ligne["binmode"]));
    $jsafter[]="dialogInstance12.close()";
    $jsafter[]="$function()";
    echo $tpl->form_outside("",$form,"",$btn,@implode(";",$jsafter),"AsSystemAdministrator");
    return true;
}
function user_sudo_id_popup():bool{
    $tpl=new template_admin();
    $id=intval($_GET["user-sudo-id-popup"]);
    $gpid=intval($_GET["gpid"]);
    $function=$_GET["function"];

    $ligne["binary"]="/bin/ls";
    $ligne["binmode"]=0;
    $btn="{add}";

    if($id>0){
        $rule=api_sudo_rule("users",$id);
        if(is_array($rule)){
            $ligne=$rule;
            $btn="{apply}";
        }
    }
    $form[]=$tpl->field_hidden("user-sudo-id",$id);
    $form[]=$tpl->field_hidden("gpid",$gpid);
    $form[]=$tpl->field_text("binary","{program}",$ligne["binary"],true,"{systemusers_sudo_binary_explain}");
    $form[]=$tpl->field_checkbox("binmode","{AllowNoPassword}",intval($ligne["binmode"]));
    $jsafter[]="dialogInstance12.close()";
    $jsafter[]="$function()";
    echo $tpl->form_outside("",$form,"",$btn,@implode(";",$jsafter),"AsSystemAdministrator");
    return true;
}
function user_sudo_id_save():bool{
    $tpl=new template_admin();
    if(!sysusers_is_admin()){return sysusers_deny_post();}
    $tpl->CLEAN_POST();
    $id=intval($_POST["user-sudo-id"]);
    $gpid=intval($_POST["gpid"]);
    $binary=trim((string)$_POST["binary"]);
    $binmode=intval($_POST["binmode"]);
    if(!sysusers_sudo_binary_ok($binary)){
        echo $tpl->post_error("{systemusers_sudo_binary_explain}");
        return false;
    }
    $owner=sysusers_user_by_uid($gpid);
    $who=isset($owner["name"])?"user {$owner["name"]}":"uid $gpid";
    $verb=$id==0?"Create a new sudo rule":"Update the sudo rule #$id";
    // The daemon validates the program and regenerates /etc/sudoers once the row is stored.
    $results=api_sudo_save("users",$id,$gpid,$binary,$binmode);
    if(!$results["ok"]){
        admin_tracks("$verb $binary for $who failed: {$results["error"]}");
        echo $tpl->post_error($results["error"]);
        return false;
    }
    return admin_tracks("$verb $binary for $who");
}
function groupid_sudo_id_save():bool{
    $tpl=new template_admin();
    if(!sysusers_is_admin()){return sysusers_deny_post();}
    $tpl->CLEAN_POST();
    $id=intval($_POST["groupid-sudo-id"]);
    $gpid=intval($_POST["gpid"]);
    $binary=trim((string)$_POST["binary"]);
    $binmode=intval($_POST["binmode"]);
    if(!sysusers_sudo_binary_ok($binary)){
        echo $tpl->post_error("{systemusers_sudo_binary_explain}");
        return false;
    }
    $Info=GroupInfo($gpid);
    $who=isset($Info["name"])?"group {$Info["name"]}":"gid $gpid";
    $verb=$id==0?"Create a new sudo rule":"Update the sudo rule #$id";
    // The daemon validates the program and regenerates /etc/sudoers once the row is stored.
    $results=api_sudo_save("groups",$id,$gpid,$binary,$binmode);
    if(!$results["ok"]){
        admin_tracks("$verb $binary for $who failed: {$results["error"]}");
        echo $tpl->post_error($results["error"]);
        return false;
    }
    return admin_tracks("$verb $binary for $who");
}
function groupid_sudo_id_del():bool{
    if(!sysusers_is_admin()){return sysusers_deny_js();}
    $id=intval($_GET["groupid-sudo-id-del"]);
    $md=$_GET["md"] ?? "";
    $label="#$id";
    $rule=api_sudo_rule("groups",$id);
    if(is_array($rule)){
        $gpid=intval($rule["gpid"] ?? 0);
        $Info=GroupInfo($gpid);
        $label="#$id (".($rule["binary"] ?? "")." for group ".($Info["name"] ?? "gid $gpid").")";
    }
    $results=api_sudo_delete("groups",$id);
    if(!$results["ok"]){
        admin_tracks("Delete the sudo rule $label failed: {$results["error"]}");
        return sysusers_js_fail($results["error"]);
    }
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    return admin_tracks("Delete the sudo rule $label");
}
function user_sudo_id_del():bool{
    if(!sysusers_is_admin()){return sysusers_deny_js();}
    $id=intval($_GET["user-sudo-id-del"]);
    $md=$_GET["md"] ?? "";
    $label="#$id";
    $rule=api_sudo_rule("users",$id);
    if(is_array($rule)){
        $gpid=intval($rule["gpid"] ?? 0);
        $owner=sysusers_user_by_uid($gpid);
        $label="#$id (".($rule["binary"] ?? "")." for user ".($owner["name"] ?? "uid $gpid").")";
    }
    $results=api_sudo_delete("users",$id);
    if(!$results["ok"]){
        admin_tracks("Delete the sudo rule $label failed: {$results["error"]}");
        return sysusers_js_fail($results["error"]);
    }
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    return admin_tracks("Delete the sudo rule $label");
}
function groupid_useradd_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["groupid-useradd-js"]);
    $function2="";
    $nogroup="";
    $function=$_GET["function"];
    if(isset($_GET["function2"])){
        $function2=$_GET["function2"];
    }
    $GroupName="";


    if(isset($_GET["nogroup"])){
        $nogroup="&nogroup=yes";
    }else {
        if ($id == 0) {
            $GroupName = "Root:";
        }
    }
    if($id>0){
        $ar = GroupInfo($id);
        $GroupName=sysusers_esc($ar["name"] ?? "").":";
    }
    return $tpl->js_dialog10("$GroupName {new_member}", "$page?groupid-useradd-popup=$id&function=$function&function2=$function2$nogroup");
}
function groupid_link_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["groupid-userlink-popup"]);
    $functionSrc=$_GET["functionSrc"];
    $function2=$_GET["function2"];
   echo $tpl->search_block($page,"","","","&groupid-userlink-search=$id&functionSrc=$functionSrc&function2=$function2");
    return true;
}
function groupid_link_perform():bool{
    if(!sysusers_is_admin()){return sysusers_deny_js();}
    $groupid=intval($_GET["groupid-userlink-perform"]);
    $member=(string)$_GET["member"];
    $md=$_GET["md"] ?? "";
    $functionSrc=$_GET["functionSrc"] ?? "";
    $function2=$_GET["function2"] ?? "";
    $Info=GroupInfo($groupid);
    $gname=(string)($Info["name"] ?? "gid $groupid");
    // Le demon reconstruit sshd apres un changement d'appartenance (AllowUsers developpe les groupes, audit 18).
    $r=sysusers_post("/system/users/membership",array("username"=>$member,"gid"=>$groupid,"action"=>"add"),
        "Unable to add the system user $member to the group $gname");
    if(!$r["ok"]){
        admin_tracks("Add the system user $member to the group $gname failed: {$r["error"]}");
        return sysusers_js_fail($r["error"]);
    }
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    if($function2!==""){echo "$function2();\n";}
    if($functionSrc!==""){echo "$functionSrc();\n";}
    return admin_tracks("Add the system user $member to the group $gname");
}

function groupid_link_search():bool{
    $id=intval($_GET["groupid-userlink-search"]);
    $Info=GroupInfo($id);
    $function=$_GET["function"];
    $function2=$_GET["function2"];
    $functionSrc=$_GET["functionSrc"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $Members=is_array($Info["members"] ?? null)?$Info["members"]:array();
    $ALREADY=array();
    foreach ($Members as $member) {
        $ALREADY[$member]=true;
    }
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'>";
    $html[]="<thead><tr>";
    $html[]="<th>{members}</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody id='glink-rows'>";

    $search=sysusers_search_regex();
    $gico=ico_member;
    $AllMembers=AllMembers();
    $MetaUsers=sysusers_user_meta_map();
    $c=0;
    foreach ($AllMembers as $member) {
        if(isset($ALREADY[$member])){continue;}
        // Compte tenu par Artica Meta : son appartenance ne se change pas ici.
        if(isset($MetaUsers[$member])){continue;}
        if($search!=="" && !preg_match($search,$member)){continue;}
        $c++;
        if($c>20){break;}
        $memberenc=urlencode($member);
        $md=md5($member);
        $js="Loadjs('$page?groupid-userlink-perform=$id&member=$memberenc&function=$function&function2=$function2&functionSrc=$functionSrc&md=$md');";
        $bton=$tpl->th_buttons_label("{select}",$js,ico_link,"AsSystemAdministrator","label-primary");
        // Le nom vient du systeme et partait BRUT dans le HTML.
        $html[]=sysusers_member_row($md,htmlspecialchars($member),"",$bton);
    }
    if($c==0){
        $html[]="<tr><td colspan='2' class='awc-empty'>{no_data}</td></tr>";
    }

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $c</span>"
           ."<span class='awc-pg' id='glink-pager'></span></div>";
    $html[]="</div></div>";

    // Le bloc <script> est concatene APRES l'analyse (accolades JavaScript).
    $script="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"glink-rows","glink-pager");
    return true;
}
function groupid_link_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["groupid-userlink-js"]);
    $functionSrc=$_GET["functionSrc"];
    $function2=$_GET["function2"];
    $ar=GroupInfo($id);
    $gname=sysusers_esc($ar["name"] ?? "");
    return $tpl->js_dialog11("$gname: {link_user}", "$page?groupid-userlink-popup=$id&functionSrc=$functionSrc&function2=$function2",550);
}
function groupid_useradd_popup(){
    $tpl=new template_admin();
    $function2="";
    $nogroup=false;
    $id=intval($_GET["groupid-useradd-popup"]);
    $function=$_GET["function"];
    if(isset($_GET["function2"])){
        $function2=$_GET["function2"];
    }
    if(isset($_GET["nogroup"])){
        $nogroup=true;
    }
    if(!$nogroup) {
        if ($id == 0) {
            echo $tpl->div_warning("{warning_group_root_create_user}");
        }
    }
    if($nogroup){
        if(sysusers_list()===null){
            echo $tpl->div_error(sysusers_list_error());
            return false;
        }
        // Groupes principaux acceptes par le demon : "users" (100) ou GID >= 1000, hors groupes Meta.
        // 0 = automatique : le demon cree un groupe du nom du compte (le PHP ne le cree plus, audit 16).
        $GPRS[0]="{automatic}";
        foreach (sysusers_groups() as $jsClass){
            $gid=intval($jsClass->gid);
            if($gid==0){continue;}
            if($gid<1000 && $gid!=100){continue;}
            if(!empty($jsClass->meta)){continue;}
            $GPRS[$gid]=(string)$jsClass->name;
        }
        $form[]=$tpl->field_array_hash($GPRS,"groupid-useradd","nonull:{select_group}","");
        $form[]=$tpl->field_checkbox("asRoot","{group} Root",0);
    }else{
        $form[]=$tpl->field_hidden("groupid-useradd",$id);
        if($id==0){
            $form[]=$tpl->field_hidden("asRoot",1);
        }
    }
    $form[]=$tpl->field_text("username","{username}","",true);
    $form[]=$tpl->field_password2("password","{password}","",true,"{systemusers_password_rule}");
    $form[]=$tpl->field_text("gecos","{description}","");
    $form[]=$tpl->field_browse_directory("home","{homeDirectory}","auto");
    $form[]=$tpl->field_array_hash(sysusers_shells("/bin/bash"),"shell","{shell}","/bin/bash");
    $jsafter[]="dialogInstance10.close()";
    $jsafter[]="$function()";
    if(strlen($function2)>2) {
        $jsafter[] = "$function2()";
    }
    echo $tpl->form_outside("",$form,"","{add}",@implode(";",$jsafter),"AsSystemAdministrator");
}
function groupid_useradd_save():bool{
    $tpl=new template_admin();
    if(!sysusers_is_admin()){return sysusers_deny_post();}
    $password=sysusers_raw_password("password");
    $tpl->CLEAN_POST();
    $gid=intval($_POST["groupid-useradd"]);
    $asRoot=intval($_POST["asRoot"] ?? 0)==1;
    $userName=trim((string)$_POST["username"]);
    $err=sysusers_password_error($password);
    if($err!==""){
        echo $tpl->post_error($err);
        return false;
    }
    $home=trim((string)($_POST["home"] ?? ""));
    if(strtolower($home)=="auto"){$home="";}
    $gecos=trim(str_replace(array("\r","\n"),"",(string)($_POST["gecos"] ?? "")));
    $shell=trim((string)($_POST["shell"] ?? "/bin/bash"));
    if($gid<0){$gid=0;}
    // GID 0 sans "Root" coche : le demon cree lui-meme le groupe du compte (autogroup).
    $autogroup=($gid==0 && !$asRoot);
    $payload=array(
        "username"=>$userName,
        "password"=>$password,
        "gecos"=>$gecos,
        "home"=>$home,
        "shell"=>$shell,
        "gid"=>$autogroup?0:$gid,
        "autogroup"=>$autogroup,
        "allow_root"=>($gid==0 && $asRoot),
    );
    $where=$autogroup?"with its own group":"in group gid $gid";
    $r=sysusers_post("/system/users/add",$payload,"Unable to create the system user $userName");
    if(!$r["ok"]){
        admin_tracks("Create the system user $userName $where failed: {$r["error"]}");
        echo $tpl->post_error($r["error"]);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    sysusers_post("/system/users/build/sudoers",array(),"Unable to build sudoers");
    return admin_tracks("Create the system user $userName $where");
}
function AllMembers():array{
    $MAIN=array();
    foreach (sysusers_groups() as $jsClass) {
        foreach (sysusers_group_members($jsClass) as $member) {
            $MAIN[$member] = $member;
        }
    }
    return $MAIN;
}
function UserInfo($Username):array{
    $data=sysusers_list();
    if($data===null){
        return array();
    }
    foreach ($data->UsersAndGroups->users as $jsClass) {
        if((string)$jsClass->name!==(string)$Username){continue;}
        $gid=intval($jsClass->gid);
        $Array["uid"] = intval($jsClass->uid);
        $Array["gid"] = $gid;
        $Array["name"] = (string)$jsClass->name;
        $Array["gecos"] = (string)($jsClass->gecos ?? "");
        $Array["home"] = (string)($jsClass->home ?? "");
        $Array["shell"] = (string)($jsClass->shell ?? "");
        $Array["type"] = (string)($jsClass->type ?? "");
        $Array["meta"] = !empty($jsClass->meta);
        $Array["locked"] = !empty($jsClass->locked);
        $Primary = GroupInfo($gid);
        $Array["group"] = (string)($Primary["name"] ?? "");
        $Array["memberof"] = isMemberOf($Username,$data->UsersAndGroups);
        return $Array;
    }
    return array();
}
function isMemberOf($Username,$data):array{
    $Array=array();
    $groups=(is_object($data) && isset($data->groups) && is_array($data->groups))?$data->groups:array();
    foreach ($groups as $jsClass) {
        if(in_array((string)$Username,sysusers_group_members($jsClass),true)){
            $Array[intval($jsClass->gid)] = (string)$jsClass->name;
        }
    }
    return $Array;
}

function GroupIDFromName($GroupName):int{
    if(sysusers_list()===null){
        return -1;
    }
    foreach (sysusers_groups() as $jsClass) {
        if(strtolower((string)$GroupName)==strtolower((string)$jsClass->name)){
            return intval($jsClass->gid);
        }
    }
    return -1;
}
function GroupInfo($id):array{
    foreach (sysusers_groups() as $jsClass) {
        if(intval($jsClass->gid)==intval($id)) {
            $Array["gid"] = intval($jsClass->gid);
            $Array["name"] = (string)$jsClass->name;
            // Sans doublon (audit 19 : un compte apparaissait deux fois).
            $Array["members"] = sysusers_group_members($jsClass);
            $Array["meta"] = !empty($jsClass->meta);
            return $Array;
        }
    }
    return array();
}
function groupid_tab():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $id=intval($_GET["groupid-tab"]);
    $function=$_GET["function"];
    $GroupInfo=GroupInfo($id);
    $array[$GroupInfo["name"]]="$page?groupid-start=$id&function=$function";
    $array["{members}"]="$page?groupid-members=$id&function=$function";
    if($id>0) {
        if(!isBanned($GroupInfo["name"])) {
            $SUDO_INSTALLED = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SUDO_INSTALLED"));
            if ($SUDO_INSTALLED == 1) {
                $array["Sudo"] = "$page?groupid-sudo=$id&function=$function";
            }
        }
    }
	echo $tpl->tabs_default($array);
    return true;
}
function user_tab():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $user = $_GET["user-tab"];
    $userenc = urlencode($user);
    $function = $_GET["function"];
    $array[$user] = "$page?user-start=$userenc&function=$function";
    if (!isBanned($user)) {
        $SUDO_INSTALLED = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SUDO_INSTALLED"));
        if ($SUDO_INSTALLED == 1) {
            $array["Sudo"] = "$page?user-sudo=$user&function=$function";
        }
    }

    echo $tpl->tabs_default($array);
    return true;
}
function groupid_start():bool{
    $page=CurrentPageName();
    $id=intval($_GET["groupid-start"]);
    $function=$_GET["function"];
    echo "<div id='groupid-start-$id'></div>";
    echo "<script>LoadAjaxSilent('groupid-start-$id','$page?groupid-popup=$id&function=$function');</script>";
    return true;
}
function user_start():bool{
    $page=CurrentPageName();
    $user=$_GET["user-start"];
    $userenc=urlencode($user);
    $function=$_GET["function"];
    $uid=md5($user);
    echo "<div id='user-start-$uid'></div>";
    echo "<script>LoadAjax('user-start-$uid','$page?user-popup=$userenc&function=$function');</script>";
    return true;
}
function user_gecos_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $user=$_GET["user-gecos-js"];
    $userenc=urlencode($user);
    $function=$_GET["function"];
    return $tpl->js_dialog9("$user: {description}", "$page?user-gecos-popup=$userenc&function=$function",550);
}
function user_shell_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $user=$_GET["user-shell-js"];
    $userenc=urlencode($user);
    $function=$_GET["function"];
    return $tpl->js_dialog9("$user: {shell}", "$page?user-shell-popup=$userenc&function=$function",550);
}
function user_shell_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $user=$_GET["user-shell-popup"];
    $form[]=$tpl->field_hidden("user-shell",$user);
    $Info=UserInfo($user);
    $uid=md5($user);
    $userenc=urlencode($user);
    $function=$_GET["function"];
    // Le shell courant hors liste reste propose et selectionne : "Appliquer" ne le change plus en silence.
    $current=(string)($Info["shell"] ?? "");
    $form[]=$tpl->field_array_hash(sysusers_shells($current),"shellCommand","{shell}",$current);
    $jsafter[]="dialogInstance9.close()";
    $jsafter[]="LoadAjax('user-start-$uid','$page?user-popup=$userenc&function=$function');";
    $jsafter[]="$function()";
    echo $tpl->form_outside("",$form,"","{apply}",@implode(";",$jsafter),"AsSystemAdministrator");
    return true;
}
function user_shell_save():bool{
    $tpl=new template_admin();
    if(!sysusers_is_admin()){return sysusers_deny_post();}
    $tpl->CLEAN_POST();
    $user=(string)$_POST["user-shell"];
    $shell=trim((string)$_POST["shellCommand"]);
    $r=sysusers_post("/system/users/shell",array("username"=>$user,"shell"=>$shell),
        "Unable to change the shell of $user");
    if(!$r["ok"]){
        admin_tracks("Change the shell of the system user $user to $shell failed: {$r["error"]}");
        echo $tpl->post_error($r["error"]);
        return false;
    }
    return admin_tracks("Change the shell of the system user $user to $shell");
}
function user_password_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $user=$_GET["user-password-js"];
    $userenc=urlencode($user);
    $function=$_GET["function"];
    return $tpl->js_dialog9("$user: {password}", "$page?user-password-popup=$userenc&function=$function",550);
}
function user_password_popup():bool{
    $tpl=new template_admin();
    $user=$_GET["user-password-popup"];
    $form[]=$tpl->field_hidden("user-password",$user);
    // Jamais pre-rempli ; 8 caracteres minimum (regle du demon).
    $form[]=$tpl->field_password2("password","{password}","",true,"{systemusers_password_rule}");
    $jsafter[]="dialogInstance9.close()";
    echo $tpl->form_outside("",$form,"","{apply}",@implode(";",$jsafter),"AsSystemAdministrator");
    return true;
}
function user_password_save():bool{
    $tpl=new template_admin();
    if(!sysusers_is_admin()){return sysusers_deny_post();}
    $password=sysusers_raw_password("password");
    $tpl->CLEAN_POST();
    $user=(string)$_POST["user-password"];
    $err=sysusers_password_error($password);
    if($err!==""){
        echo $tpl->post_error($err);
        return false;
    }
    // POST JSON : le mot de passe ne passe plus dans l'URL (journal des requetes du demon, audit 1).
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/system/user/chpasswd",
        array("user"=>$user,"password"=>$password)),true);
    if(!is_array($json) || empty($json["success"])){
        $error="Unable to change the password of $user";
        if(is_array($json)){
            if(!empty($json["error"])){$error=(string)$json["error"];}
            elseif(!empty($json["Error"])){$error=(string)$json["Error"];}
        }
        admin_tracks("Change the password of the system user $user failed: $error");
        echo $tpl->post_error($error);
        return false;
    }
    return admin_tracks("Change the password of the system user $user");
}
function user_gecos_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $page=CurrentPageName();
    $user=$_GET["user-gecos-popup"];
    $userenc=urlencode($user);
    $form[]=$tpl->field_hidden("user-gecos",$user);
    $Info=UserInfo($user);
    $uid=md5($user);

    $form[]=$tpl->field_text("gecos","{description}",$Info["gecos"] ?? "");
    $jsafter[]="dialogInstance9.close()";
    $jsafter[]="LoadAjax('user-start-$uid','$page?user-popup=$userenc&function=$function');";
    $jsafter[]="$function()";
    echo $tpl->form_outside("",$form,"","{apply}",@implode(";",$jsafter),"AsSystemAdministrator");
    return true;
}
function user_gecos_save():bool{
    $tpl=new template_admin();
    if(!sysusers_is_admin()){return sysusers_deny_post();}
    $tpl->CLEAN_POST();
    $user=(string)$_POST["user-gecos"];
    // Une seule ligne : un saut de ligne ajoutait une ligne parasite dans /etc/passwd (audit 17).
    $gecos=trim(str_replace(array("\r","\n"),"",(string)$_POST["gecos"]));
    $r=sysusers_post("/system/users/gecos",array("username"=>$user,"gecos"=>$gecos),
        "Unable to change the description of $user");
    if(!$r["ok"]){
        admin_tracks("Change the description of the system user $user failed: {$r["error"]}");
        echo $tpl->post_error($r["error"]);
        return false;
    }
    return admin_tracks("Change the description of the system user $user");
}
function user_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $user=$_GET["user-popup"];
    $userenc=urlencode($user);
    $function=$_GET["function"];
    $Info=UserInfo($user);
    if(count($Info)==0){
        echo $tpl->div_error("{no_data}");
        return false;
    }
    $meta=!empty($Info["meta"]);
    // Audit 12 : groupe principal lu sous des noms que la boucle des groupes secondaires ne reutilise pas.
    $PrimaryName=(string)$Info["group"];
    $PrimaryGid=intval($Info["gid"]);
    $UserName=$Info["name"];
    $uid=intval($Info["uid"]);

    if($meta){
        echo $tpl->_ENGINE_parse_body("<div class='ak-banner ak-info' role='status' style='margin:10px 0'>"
            ."<i class='fa-solid fa-lock'></i><span class='ak-grow'>{systemusers_meta_banner}</span></div>");
    }
    $type="{".preg_replace('/[^A-Za-z0-9_]/','',$Info["type"])."}";
    if($meta){$type.=" ".sysusers_meta_badge();}
    if(!empty($Info["locked"])){$type.=" ".sysusers_locked_badge();}
    $tpl->table_form_field_text("{type}",$type,ico_infoi);

    // Compte Artica Meta : lecture seule, aucun lien d'edition.
    $tpl->table_form_field_js($meta?"":"Loadjs('$page?user-password-js=$userenc&function=$function')","AsSystemAdministrator");
    $tpl->table_form_field_text("{password}","* * * *",ico_lock);

    $Memberof=array();
    $tt=array();
    foreach ($Info["memberof"] as $mgid => $mname) {
        $Memberof[$mname]=true;
        if($mname=="wheel"){
            continue;
        }
        $link=$tpl->td_href(sysusers_esc($mname),"","Loadjs('$page?groupid-js=".intval($mgid)."&function=$function')");
        $tt[]="<span style='text-transform:none'>$link</span>";
    }
    $tpl->table_form_field_js($meta?"":"Loadjs('$page?user-gecos-js=$userenc&function=$function')","AsSystemAdministrator");
    $tpl->table_form_field_text("{description}",sysusers_esc($Info["gecos"]),ico_infoi);
    $tpl->table_form_field_js("Loadjs('$page?groupid-js=$PrimaryGid&function=$function')","AsSystemAdministrator");
    $tpl->table_form_field_text("{primary_group}","<span style='text-transform:none'>".sysusers_esc($PrimaryName)."</span>",
        ico_groups_finders);

    if($uid>0) {
        $tpl->table_form_field_js($meta?"":"Loadjs('$page?user-su-js=$userenc&function=$function')", "AsSystemAdministrator");
        if (isset($Memberof["wheel"])) {
            $tpl->table_form_field_bool("{allow} <strong>&laquo;{su}&raquo;</strong>", 1, ico_admin);
        } else {
            $tpl->table_form_field_bool("{allow} <strong>&laquo;{su}&raquo;</strong>", 0, ico_admin);
        }
    }

    if(count($tt)>0) {
        $tt_text=@implode(", ",$tt);
        $tpl->table_form_field_js("");
        $tpl->table_form_field_text("{isMemberOf}", "<small>$tt_text</small>",
            ico_groups_settings);
    }
    $tpl->table_form_field_js("");
    $tpl->table_form_field_text("{homeDirectory}","<span style='text-transform:none'>".sysusers_esc($Info["home"])."</span>",ico_directory);

    $tpl->table_form_field_js($meta?"":"Loadjs('$page?user-shell-js=$userenc&function=$function')","AsSystemAdministrator");
    $tpl->table_form_field_text("{shell}","<span style='text-transform:none'>".sysusers_esc($Info["shell"])."</span>",ico_terminal);

    $tpl=user_popup_ssh($tpl,$Info);

    if(!$meta && !isBanned($UserName)){
        $tpl->table_form_button("{remove}","Loadjs('$page?user-delete-js=$uid&name=$userenc&function=$function')","AsSystemAdministrator",ico_trash);
    }

    echo $tpl->table_form_compile();

    return true;
}
function user_su_js():bool{
    $page=CurrentPageName();
    if(!sysusers_is_admin()){return sysusers_deny_js();}
    $user=(string)$_GET["user-su-js"];
    $Info=UserInfo($user);
    if(count($Info)==0){return sysusers_js_fail("Unknown system user $user");}
    $function=$_GET["function"];
    $userenc=urlencode($user);
    $inWheel=in_array("wheel",array_values($Info["memberof"]),true);
    // Le demon cree "wheel" s'il manque et reconstruit sshd apres le changement d'appartenance.
    $action=$inWheel?"remove":"add";
    $text=$inWheel?"Remove the system user $user from the wheel group":"Add the system user $user to the wheel group";
    $r=sysusers_post("/system/users/membership",array("username"=>$user,"group"=>"wheel","action"=>$action),
        "Unable to change the su permission of $user");
    if(!$r["ok"]){
        admin_tracks("$text failed: {$r["error"]}");
        return sysusers_js_fail($r["error"]);
    }
    header("content-type: application/x-javascript");
    $uid=md5($user);
    echo "LoadAjax('user-start-$uid','$page?user-popup=$userenc&function=$function');";
    return admin_tracks($text);
}
function user_ssh_switch():bool{
    if(!sysusers_is_admin()){return sysusers_deny_js();}
    $username=(string)$_GET["user-ssh-switch"];
    $userenc=urlencode($username);
    $function=$_GET["function"];
    $page=CurrentPageName();
    // One atomic call: the daemon inserts the row when missing, else flips
    // enabled, and answers with the resulting state.
    $results=api_ssh_allowuser_toggle($username);
    if(!$results["ok"]){
        admin_tracks("Change the SSH access of the system user $username failed: {$results["error"]}");
        return sysusers_js_fail($results["error"]);
    }
    $enabled=!empty($results["json"]["Enabled"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    header("content-type: application/x-javascript");
    $md=md5($username);
    echo "LoadAjax('user-start-$md','$page?user-popup=$userenc&function=$function');";
    if(!$enabled){
        return admin_tracks("Disable the SSH access of the system user $username");
    }
    return admin_tracks("Enable the SSH access of the system user $username");
}
function user_popup_ssh($tpl,$Info){
    $page=CurrentPageName();
    $function=$_GET["function"];
    if($Info["type"]<>"member") {
        return $tpl;
    }
    if(user_isssh($Info["memberof"])){
        $tpl->table_form_field_js("");
        $tpl->table_form_field_bool("{accept_ssh}",1,ico_terminal);
        VERBOSE("--> memberof",__LINE__);
        return user_popup_sshd_authorizedkeys($tpl,$Info);
    }
    $uid=$Info["uid"];
    $NameEncoded=urlencode($Info["name"]);
    // Compte Artica Meta : son acces SSH est tenu par la politique.
    $tpl->table_form_field_js(!empty($Info["meta"])?"":"Loadjs('$page?user-ssh-switch=$NameEncoded&uid=$uid&function=$function');", "AsSystemAdministrator");
    $allow=api_ssh_allowuser($Info["name"]);
    if(!$allow["exists"]){
        $tpl->table_form_field_bool("{accept_ssh}",0,ico_terminal);
        VERBOSE("--> accept_ssh --> 0",__LINE__);
        return $tpl;
    }
    $tpl->table_form_field_bool("{accept_ssh}",intval($allow["enabled"]),ico_terminal);
// https://192.168.90.53:9000/fw.sshd.php?AuthorizedKeys-create-js=yes&function=ss1753642731&jQueryLjs=yes&_=1753628008726

    return user_popup_sshd_authorizedkeys($tpl,$Info);
}
function AuthorizedKeys_create_js():bool{
    if(!sysusers_is_admin()){return sysusers_deny_js();}
    $function=$_GET["function"];
    $page=CurrentPageName();
    $uid=intval($_GET["AuthorizedKeys-create-js"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/genkeyid/$uid"));
    if(!is_object($json) || empty($json->Status)){
        $error=(is_object($json) && !empty($json->Error))?(string)$json->Error:"Unable to create the SSH key of uid $uid";
        admin_tracks("Create an SSH key for uid $uid failed: $error");
        return sysusers_js_fail($error);
    }
    $user=(string)$json->username;
    $id=md5($user);
    $userenc=urlencode($user);
    header("content-type: application/x-javascript");
    echo "LoadAjax('user-start-$id','$page?user-popup=$userenc&function=$function');\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks("Create an SSH key for the system user $user (uid $uid)");
}
function user_popup_sshd_authorizedkeys($tpl,$Info){
    $uid=$Info["uid"];
    $page=CurrentPageName();
    $function=$_GET["function"];
    $usernameEnc=urlencode($Info["name"]);
    VERBOSE("--> /ssh/authorizedkeys/user/{$Info["name"]}",__LINE__);
    $results=api_ssh_authorizedkeys($Info["name"]);
    foreach ($results as $index=>$ligne) {
        $email=$ligne["email"];
        $zmd5=$ligne["zmd5"];
        $tpl->table_form_field_js("Loadjs('fw.sshd.php?AuthorizedKeys-add-js=yes&zmd5=$zmd5&function=$function&username=$usernameEnc')");
        $tpl->table_form_field_text("{public_ssh_key}",$email,ico_ssl);
    }
    $topbuttons[] = array("Loadjs('$page?AuthorizedKeys-create-js=$uid&function=$function')", ico_plus, "{public_ssh_key}:{generate_new_key}");
    VERBOSE("--> table_form_field_buttons",__LINE__);
    $tpl->table_form_field_buttons($topbuttons);
    return $tpl;
}
function user_delete_js():bool{
    if(!sysusers_is_admin()){return sysusers_deny_js();}
    $tpl=new template_admin();
    $id=intval($_GET["user-delete-js"]);
    $function=$_GET["function"];
    $name=$_GET["name"];
    // Pentest 2026-11-04 : name etait recopie sans encodage dans la page (XSS).
    return $tpl->js_confirm_delete(req_html($name), "user-delete", $id,"dialogInstance8.close();$function();", "AsSystemAdministrator");
}
function user_delete_perform():bool{
    $tpl=new template_admin();
    if(!sysusers_is_admin()){return sysusers_deny_post();}
    $tpl->CLEAN_POST();
    $uid=intval($_POST["user-delete"]);
    $owner=sysusers_user_by_uid($uid);
    $label=isset($owner["name"])?"{$owner["name"]} (uid $uid)":"uid $uid";
    // Le demon garde le repertoire personnel, purge les regles sudo et l'acces SSH du compte, puis reconstruit.
    $r=sysusers_post("/system/user/delete",array("uid"=>$uid),"Unable to remove the system user $label");
    if(!$r["ok"]){
        admin_tracks("Delete the system user $label failed: {$r["error"]}");
        echo $tpl->post_error($r["error"]);
        return false;
    }
    return admin_tracks("Delete the system user $label");
}
function user_isssh($arrayUser):bool{
    $zAllowsUser=sysusers_ssh_groups();
    foreach ($arrayUser as $gid => $GroupName) {
        if(isset($zAllowsUser[$GroupName])){
            return true;
        }
    }
    return false;
}
function groupid_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["groupid-popup"]);
    $function=$_GET["function"];
    $Info=GroupInfo($id);
    if(count($Info)==0){
        echo $tpl->div_error("{no_data}");
        return false;
    }
    $meta=!empty($Info["meta"]);
    $name=$Info["name"];
    // GroupInfo() rend deja les membres sans doublon (audit 19).
    $c=0;
    foreach ($Info["members"] as $member) {
        if(strlen($member)<2){continue;}
        $c++;
    }
    if($meta){
        echo $tpl->_ENGINE_parse_body("<div class='ak-banner ak-info' role='status' style='margin:10px 0'>"
            ."<i class='fa-solid fa-lock'></i><span class='ak-grow'>{systemusers_meta_banner}</span></div>");
    }
    $label="<span style='text-decoration:none'>".sysusers_esc($name)."</span>";
    if($meta){$label.=" ".sysusers_meta_badge();}
    $tpl->table_form_field_text("{group}",$label,ico_group);
    $tpl->table_form_field_text("{members}",$c,ico_users);

    if(!isBanned($name)) {
        $EnableOpenSSH = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenSSH"));
        if ($EnableOpenSSH == 1) {
            $zAllowsGroup = sysusers_ssh_groups();
            if ($id > 0 && !$meta) {
                $tpl->table_form_field_js("Loadjs('$page?ssh-switch=$id&function=$function');", "AsSystemAdministrator");
            }
            if (isset($zAllowsGroup[$name])) {
                $tpl->table_form_field_bool("{accept_ssh}", 1, ico_terminal);
            } else {
                $tpl->table_form_field_bool("{accept_ssh}", 0, ico_terminal);
            }
        }
    }
    echo $tpl->table_form_compile();
    return true;
}
function groupid_ssh():bool{
    $page=CurrentPageName();
    if(!sysusers_is_admin()){return sysusers_deny_js();}
    $id=intval($_GET["ssh-switch"]);
    $Info=GroupInfo($id);
    $groupname=(string)($Info["name"] ?? "");
    // Audit 18 : un GID inconnu ajoutait "" et le GID 0 ajoutait root.
    if($id<=0 || $groupname===""){
        return sysusers_js_fail("Unknown system group (gid $id)");
    }
    $zAllowsGroup=sysusers_ssh_groups();
    $enabled=!isset($zAllowsGroup[$groupname]);
    $text=($enabled?"Allow":"Deny")." the SSH access of the system group $groupname";
    // Le demon met a jour SSHAllowGroups et SSHAllowGroupsForGo ensemble, puis reconstruit sshd.
    $r=sysusers_post("/system/group/ssh",array("name"=>$groupname,"enabled"=>$enabled),
        "Unable to change the SSH access of the group $groupname");
    if(!$r["ok"]){
        admin_tracks("$text failed: {$r["error"]}");
        return sysusers_js_fail($r["error"]);
    }
    $function=$_GET["function"];
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('groupid-start-$id','$page?groupid-popup=$id&function=$function');";
    return admin_tracks($text);
}
function groupid_buttons():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["groupid-buttons"]);
    $function=$_GET["function"];
    $function2=$_GET["function2"];
    // Groupe tenu par Artica Meta : ni nouveau membre ni rattachement.
    if(sysusers_group_is_meta($id)){return true;}
    $topbuttons=array();
    $topbuttons[] = array("Loadjs('$page?groupid-useradd-js=$id&function=$function&function2=$function2');", ico_plus, "{new_member}");
    $topbuttons[] = array("Loadjs('$page?groupid-userlink-js=$id&functionSrc=$function&function2=$function2');", ico_link, "{link_member}");
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}
function groupid_members():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $id=intval($_GET["groupid-members"]);
     echo "<div id='buttons-groupid-$id' style='margin:5px'></div>";
    echo $tpl->search_block($page,"","","","&groupid-search=$id&function=$function&function2=$function");
    return true;
}
function membersGroups():array{
    if(!isset($GLOBALS["MEMBERS"])){
        $GLOBALS["MEMBERS"]=array();
        foreach (sysusers_users() as $jsClass) {
            $name = (string)$jsClass->name;
            $GLOBALS["MEMBERS"][$name]["uid"] = intval($jsClass->uid);
            $GLOBALS["MEMBERS"][$name]["gid"] = intval($jsClass->gid);
            $GLOBALS["MEMBERS"][$name]["gecos"] = (string)($jsClass->gecos ?? "");
            $GLOBALS["MEMBERS"][$name]["home"] = (string)($jsClass->home ?? "");
            $GLOBALS["MEMBERS"][$name]["shell"] = (string)($jsClass->shell ?? "");
            $GLOBALS["MEMBERS"][$name]["meta"] = !empty($jsClass->meta);
            $GLOBALS["MEMBERS"][$name]["locked"] = !empty($jsClass->locked);
        }
    }
    return $GLOBALS["MEMBERS"];
}
function groupid_search():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $function = $_GET["function"];
    $function2 = $_GET["function2"];
    $id = intval($_GET["groupid-search"]);
    $Info = GroupInfo($id);
    $members = is_array($Info["members"] ?? null)?$Info["members"]:array();
    $groupMeta = !empty($Info["meta"]);
    $search=sysusers_search_regex();

    $membersInfo = membersGroups();

    $html[] = sysusers_meta_banner();
    $html[] = "<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[] = "<table class='awc-tbl'>";
    $html[] = "<thead><tr>";
    $html[] = "<th>{members}</th>";
    $html[] = "<th>&nbsp;</th>";
    $html[] = "</tr></thead>";
    $html[] = "<tbody id='gmemb-rows'>";
    $c=0;
    foreach ($members as $member) {
        if(strlen($member)<2){continue;}
        if($search!=="" && !preg_match($search,$member)){continue;}
        // membersGroups() ne connait pas forcement un membre issu d'un autre
        // annuaire: la lecture directe emettait un avertissement et un
        // "Trying to access array offset on null" en PHP 8.
        $info = is_array($membersInfo[$member] ?? null) ? $membersInfo[$member] : array();
        $gecos = (string)($info["gecos"] ?? "");
        $uid = intval($info["uid"] ?? 0);
        if (strlen($gecos) < 2) {$gecos = "Home: ".(string)($info["home"] ?? "");}
        $md=md5("tt".$member);
        $bton="";
        $memberMeta=!empty($info["meta"]);
        if($groupMeta || $memberMeta){
            // Artica Meta : appartenance en lecture seule.
            $bton=sysusers_meta_badge();
        }elseif(isset($info["gid"]) && intval($info["gid"])==$id){
            // Audit 11 : le groupe principal d'un compte ne se retire pas (le demon refuse).
            $bton="<span class='ak-help' title=\"{systemusers_primary_member}\">{primary_group}</span>";
        }elseif($uid>0) {
            $bton = $tpl->icon_unlink("Loadjs('$page?groupid-userunlink-js=$id&function=$function&function2=$function2&member=".urlencode($member)."&md=$md')", "AsSystemAdministrator");
        }
        $memberencoded=urlencode($member);
        $nameHtml=$tpl->td_href(sysusers_esc($member),"","Loadjs('$page?user-js=$memberencoded&function=$function')");
        if(!empty($info["locked"])){$nameHtml.=" ".sysusers_locked_badge();}
        $html[]=sysusers_member_row($md,$nameHtml,$gecos,$bton);
        $c++;
    }
    if($c==0){
        $html[]="<tr><td colspan='2' class='awc-empty'>{no_data}</td></tr>";
    }

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $c</span>"
           ."<span class='awc-pg' id='gmemb-pager'></span></div>";
    $html[]="</div></div>";

    // Le bloc <script> est concatene APRES l'analyse (accolades JavaScript).
    $script="<script>
        LoadAjaxTiny('buttons-groupid-$id','$page?groupid-buttons=$id&function=$function&function2=$function2');
        NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .$script
        .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"gmemb-rows","gmemb-pager");
    return true;

}
function groupid_userunlink_js():bool{
    if(!sysusers_is_admin()){return sysusers_deny_js();}
    $gpid=intval($_GET["groupid-userunlink-js"]);
    $member=(string)$_GET["member"];
    $md=$_GET["md"] ?? "";
    $function2=$_GET["function2"] ?? "";
    $Info=GroupInfo($gpid);
    $gname=(string)($Info["name"] ?? "gid $gpid");
    // Le demon refuse le groupe principal et reconstruit sshd apres le changement.
    $r=sysusers_post("/system/users/membership",array("username"=>$member,"gid"=>$gpid,"action"=>"remove"),
        "Unable to remove the system user $member from the group $gname");
    if(!$r["ok"]){
        admin_tracks("Remove the system user $member from the group $gname failed: {$r["error"]}");
        return sysusers_js_fail($r["error"]);
    }
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    if($function2!==""){echo "$function2();\n";}
    return admin_tracks("Remove the system user $member from the group $gname");
}
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{system_users}",ico_group,"{system_users_explain}","$page?start=yes","system-users","progress-system-users-restart",false,"table-loader-swap");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return true;
	}
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
	
}
function isBanned($GroupName):bool{
    $banned["sudo"]=true;
    $banned["clamav"]=true;
    $banned["dnscatz"]=true;
    $banned["postfix"]=true;
    $banned["crontab"]=true;
    $banned["openldap"]=true;
    $banned["munin"]=true;
    $banned["quaggavty"]=true;
    $banned["quagga"]=true;
    $banned["smokeping"]=true;
    $banned["apt-mirror"]=true;
    $banned["manticore"]=true;
    $banned["redsocks"]=true;
    $banned["mosquitto"]=true;
    $banned["ziproxy"]=true;
    $banned["uucp"]=true;
    $banned["sys"]=true;
    $banned["proxy"]=true;
    $banned["man"]=true;
    $banned["mail"]=true;
    $banned["daemon"]=true;
    $banned["kibana"]=true;
    $banned["avahi"]=true;
    $banned["prads"]=true;
    $banned["netdev"]=true;
    $banned["nogroup"]=true;
    $banned["plugdev"]=true;
    $banned["sasl"]=true;
    $banned["policyd-spf"]=true;
    $banned["davfs2"]=true;
    $banned["vde2-net"]=true;
    $banned["glances"]=true;
    $banned["vnstat"]=true;
    $banned["freerad"]=true;
    $banned["mlocate"]=true;
    $banned["postdrop"]=true;
    $banned["tty"]=true;
    $banned["nvram"]=true;
    $banned["mysql"]=true;
    $banned["ntp"]=true;
    $banned["webunix"]=true;
    $banned["systemd-coredump"]=true;
    $banned["squid"]=true;
    $banned["ArticaStats"]=true;
    $banned["stunnel4"]=true;
    $banned["messagebus"]=true;
    $banned["cdrom"]=true;
    $banned["nobody"]=true;
    $banned["root"]=true;
    $banned["www-data"]=true;
    $banned["systemd-journal"]=true;
    $banned["systemd-timesync"]=true;
    $banned["systemd-network"]=true;
    $banned["systemd-resolve"]=true;
    $banned["ssh"]=true;
    $banned["winbindd_priv"]=true;
    $banned["unbound"]=true;
    $banned["wheel"]=true;
    $banned["redis"]=true;
    if(isset($banned[$GroupName])){
        return true;
    }


    return false;
}
/**
 * Accents de marque (repli sur le teal de la maquette stitch_artica_system_users_manager).
 */
function usr_accents():array{
    $ac=array("accent"=>"#006a62","accent_dark"=>"#00524b",
        "accent_soft"=>"rgba(0,106,98,.10)","accent_line"=>"rgba(0,106,98,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return $ac;
}

/**
 * Styles de la page. Ne JAMAIS faire passer ce retour dans _ENGINE_parse_body().
 * Font Awesome remplace les <i> par des <svg> : la taille se pose sur le conteneur.
 */
function usr_css():string{
    $ac=usr_accents();
    $a=$ac["accent"];
    // Seules les regles propres a la page : cartes, tuiles, pastilles, table, recherche, segments,
    // actions et pagination viennent du kit ak- (angular.css.php).
    return "<style>
.usr{margin-top:10px}
.usr-how,.usr-kpis{margin-bottom:24px}
.usr-how-card{padding:20px 22px}
.usr-how-head{display:flex;align-items:center;gap:12px;margin-bottom:8px}
.usr-how-card h3{font-size:15px;font-weight:600;margin:0;color:var(--ak-text)}
.usr-how-card p{font-size:13px;line-height:1.55;color:#3d4947;margin:0}
.usr-how-card p br:last-child,.usr-how-warn br:last-child,.usr-kpi-note br:last-child{display:none}
.usr-how-warn{margin-top:10px;font-size:13px;color:var(--ak-tfg);background:var(--ak-tbg);border-radius:6px;padding:6px 10px;display:inline-block}
.usr-kpi-val{display:flex;align-items:baseline;gap:10px;margin-top:2px}
.ak-fig-v.usr-acc{color:$a}
.usr-kpi-note{font-size:13px;color:#865300;line-height:1.45}
.usr-bar{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 22px;border-bottom:1px solid var(--ak-border)}
.usr-search{width:420px;max-width:100%}
.usr-tbl tbody tr{transition:background .12s;cursor:pointer}
.usr-tbl tbody tr:hover{background:#f7faf9}
.usr-tbl tbody tr:focus{outline:2px solid $a;outline-offset:-2px}
.usr-ident{display:flex;align-items:center;gap:14px}
.usr-gname{font-size:15px;font-weight:600}
.usr-prot .usr-gname{color:var(--ak-muted);font-weight:500}
.usr-lock{font-size:11px;color:var(--ak-muted);margin-left:6px}
.usr-more{background:transparent;border:1px solid var(--ak-border)}
.usr-none{font-style:italic;color:var(--ak-muted);font-size:13px}
.usr-empty{padding:44px 22px;text-align:center;color:#3d4947;font-size:14px}
.usr-empty span{display:block;font-size:28px;color:#a3adab;margin-bottom:10px}
.usr-empty button{margin-top:10px;border:0;background:transparent;color:$a;font-weight:600;cursor:pointer;font-size:13px}
</style>";
}

/** Une carte explicative : icone, titre, texte, et un avertissement optionnel. */
function usr_how_card($ico,$title,$text,$warn=""):string{
    $h ="<div class='ak-card usr-how-card'>";
    $h.="<div class='usr-how-head'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='$ico'></i></span><h3>$title</h3></div>";
    $h.="<p>$text</p>";
    if($warn<>""){$h.="<div class='usr-how-warn ak-warn'>$warn</div>";}
    $h.="</div>";
    return $h;
}

/** Bandeau des trois usages des comptes systeme. */
function usr_how():string{
    $SUDO_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SUDO_INSTALLED"));
    $warn=$SUDO_INSTALLED==1?"":"{usr_card_sudo_missing}";
    $h="<div class='ak-grid-3 usr-how'>";
    $h.=usr_how_card("fa-solid fa-terminal","{usr_card_ssh_title}","{usr_card_ssh_text}");
    $h.=usr_how_card("fa-solid fa-folder-open","{usr_card_share_title}","{usr_card_share_text}");
    $h.=usr_how_card("fa-solid fa-shield-halved","{usr_card_sudo_title}","{usr_card_sudo_text}",$warn);
    $h.="</div>";
    return $h;
}

/** Une tuile de compteur. */
function usr_kpi($label,$num,$sub,$accent=false):string{
    $cls=$accent?" usr-acc":"";
    return "<div class='ak-fig'><div class='ak-fig-txt'><div class='ak-fig-l'>$label</div>"
        ."<div class='usr-kpi-val'><span class='ak-fig-v$cls'>$num</span><span class='ak-help'>$sub</span></div></div></div>";
}

/** Tuile de l'etat du service OpenSSH. */
function usr_kpi_openssh($EnableOpenSSH):string{
    $h="<div class='ak-fig'><div class='ak-fig-txt'><div class='ak-fig-l'>{usr_kpi_openssh}</div><div class='usr-kpi-val'>";
    if($EnableOpenSSH==1){
        $h.="<span class='ak-pill ak-ok'><span class='ak-dot'></span>{active2}</span></div>";
    }else{
        $h.="<span class='ak-pill ak-warn'><span class='ak-dot'></span>{disabled}</span></div>";
        $h.="<div class='usr-kpi-note'>{usr_openssh_off_explain}</div>";
    }
    return $h."</div></div>";
}

/** Cellule Membres : six pastilles au plus, puis une pastille +N qui ouvre le groupe. */
function usr_members_cell($members,$gid,$page,$function):string{
    $seen=array();
    $out=array();
    $extra=0;
    foreach($members as $member){
        if(strlen($member)<2){continue;}
        if(isset($seen[$member])){continue;}
        $seen[$member]=true;
        if(count($out)>=6){$extra++;continue;}
        $enc=urlencode($member);
        $name=htmlspecialchars($member,ENT_QUOTES);
        $out[]="<a class='ak-chip' href=\"javascript:blur();\" title=\"$name\" "
            ."onclick=\"event.stopPropagation();Loadjs('$page?user-js=$enc&function=$function')\">$name</a>";
    }
    if(count($out)==0){return "<span class='usr-none'>{no_members}</span>";}
    if($extra>0){
        $out[]="<a class='ak-chip usr-more' href=\"javascript:blur();\" "
            ."onclick=\"event.stopPropagation();Loadjs('$page?groupid-js=$gid&function=$function')\">+$extra</a>";
    }
    return "<div class='ak-chips'>".@implode("",$out)."</div>";
}

/** Pastille SSH : actif / inactif, ou desactive quand OpenSSH est coupe globalement. */
function usr_ssh_badge($GroupName,$zAllowsGroup,$EnableOpenSSH):string{
    if($EnableOpenSSH==0){return "<span class='ak-pill ak-off'>{disabled}</span>";}
    if(isset($zAllowsGroup[$GroupName])){return "<span class='ak-pill ak-ok'>{active2}</span>";}
    return "<span class='ak-pill ak-off'>{inactive2}</span>";
}

/** Une ligne de groupe. Toute la ligne ouvre le groupe (clic, Entree, Espace). */
function usr_group_row($jsClass,$md,$zAllowsGroup,$EnableOpenSSH,$function):string{
    $page=CurrentPageName();
    $Name=$jsClass->name;
    $gid=intval($jsClass->gid);
    $banned=isBanned($Name);
    $NameH=htmlspecialchars($Name,ENT_QUOTES);
    $open="Loadjs('$page?groupid-js=$gid&function=$function')";
    $members=sysusers_group_members($jsClass);
    $meta=!empty($jsClass->meta);
    $hay=htmlspecialchars(strtolower($Name." ".@implode(" ",$members)),ENT_QUOTES);
    $prot=$banned?"1":"0";
    $cls=$banned?" class='usr-prot'":"";

    // id conserve: js_confirm_delete fait $('#<md>').remove() sur cette ligne.
    $h ="<tr id='$md'$cls data-prot='$prot' data-q=\"$hay\" tabindex='0' role='button' aria-label=\"$NameH\" "
        ."onclick=\"$open\" onkeydown=\"if(event.target===this&&(event.key==='Enter'||event.key===' ')){event.preventDefault();$open}\">";
    $h.="<td><div class='usr-ident'><span class='ak-ico ak-ico-sm ak-ico-sq".($banned?" ak-off":"")."'><i class='fa-solid fa-users'></i></span><div>";
    $h.="<div class='usr-gname'>$NameH";
    if($banned){$h.="<span class='usr-lock' title=\"{usr_protected_group}\"><i class='fa-solid fa-lock'></i></span>";}
    if($meta){$h.=" ".sysusers_meta_badge();}
    $h.="</div><div class='ak-code'>GID: $gid</div></div></div></td>";
    $h.="<td>".usr_members_cell($members,$gid,$page,$function)."</td>";
    $h.="<td>".usr_ssh_badge($Name,$zAllowsGroup,$EnableOpenSSH)."</td>";
    $h.="<td class='ak-end'><div class='ak-acts'>";
    $h.="<button type='button' class='ak-act' title=\"{open}\" aria-label=\"{open} $NameH\" "
        ."onclick=\"event.stopPropagation();$open\"><i class='fa-solid fa-pen'></i></button>";
    if($meta){
        // Groupe tenu par Artica Meta : pas de suppression locale (le badge est sur le nom).
    }elseif($banned){
        // Les groupes systeme proteges n'exposent pas la suppression.
        $h.="<span class='ak-help ak-nowrap'>{usr_protected_group}</span>";
    }else{
        $h.="<button type='button' class='ak-act ak-danger' title=\"{delete}\" aria-label=\"{delete} $NameH\" "
            ."onclick=\"event.stopPropagation();Loadjs('$page?groupid-delete-js=$gid&md=$md')\"><i class='fa-solid fa-trash-can'></i></button>";
    }
    $h.="</div></td></tr>";
    return $h;
}

/**
 * Filtre, compteur et pagination cote navigateur (une soixantaine de lignes).
 * La recherche et le filtre survivent au rechargement apres une modification :
 * ils sont gardes dans window.UsrSysState.
 */
function usr_js($page):string{
    return "<script>
(function(){
 var st=window.UsrSysState||(window.UsrSysState={q:'',scope:'created',page:1});
 var size=25;
 var tb=document.getElementById('usr-rows'); if(!tb){return;}
 var inp=document.getElementById('usr-q');
 var segC=document.getElementById('usr-seg-created'), segA=document.getElementById('usr-seg-all');
 var empty=document.getElementById('usr-empty'), shown=document.getElementById('usr-shown');
 var pg=document.getElementById('usr-pg');
 inp.value=st.q;
 function match(){
   var out=[], q=st.q.toLowerCase().trim();
   for(var i=0;i<tb.rows.length;i++){
     var r=tb.rows[i];
     var ok=(st.scope==='all'||r.getAttribute('data-prot')!=='1');
     if(ok&&q!==''){ ok=(r.getAttribute('data-q')||'').indexOf(q)>-1; }
     r.style.display='none';
     if(ok){out.push(r);}
   }
   return out;
 }
 function btn(label,p,cur,dis,aria){
   var b=document.createElement('button'); b.type='button'; b.textContent=label;
   if(aria){b.setAttribute('aria-label',aria);}
   if(cur){b.setAttribute('aria-current','page');b.className='cur';}
   if(dis){b.disabled=true;b.className='off';}else{ b.onclick=function(){st.page=p;draw();}; }
   return b;
 }
 function draw(){
   segC.setAttribute('aria-pressed',st.scope==='created'?'true':'false');
   segA.setAttribute('aria-pressed',st.scope==='all'?'true':'false');
   segC.classList.toggle('on',st.scope==='created'); segA.classList.toggle('on',st.scope==='all');
   var rows=match(), n=rows.length, pages=Math.max(1,Math.ceil(n/size));
   if(st.page>pages){st.page=pages;} if(st.page<1){st.page=1;}
   for(var i=(st.page-1)*size;i<Math.min(n,st.page*size);i++){ rows[i].style.display=''; }
   empty.style.display=n===0?'':'none';
   shown.textContent=n;
   pg.innerHTML='';
   if(pages>1){
     pg.appendChild(btn('‹',st.page-1,false,st.page===1,'Previous'));
     for(var p=1;p<=pages;p++){ pg.appendChild(btn(String(p),p,p===st.page,false,null)); }
     pg.appendChild(btn('›',st.page+1,false,st.page===pages,'Next'));
   }
 }
 inp.oninput=function(){ st.q=inp.value; st.page=1; draw(); };
 segC.onclick=function(){ st.scope='created'; st.page=1; draw(); };
 segA.onclick=function(){ st.scope='all'; st.page=1; draw(); };
 window.UsrSysClear=function(){ st.q=''; inp.value=''; st.page=1; draw(); inp.focus(); };
 draw();
})();
</script>";
}

function start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html="<div class='usr ak'>".usr_how()."<div id='usr-host'></div></div>";
    // Fonction de rafraichissement fixe : les fenetres l'appellent apres une modification.
    $js="<script>
function UsrSysRefresh(){ LoadAjaxSilent('usr-host','$page?search=yes&function=UsrSysRefresh'); }
UsrSysRefresh();
</script>";
    echo usr_css().$tpl->_ENGINE_parse_body($html).$js;
    return true;
}

function table():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $topbuttons=array();
    // Whitelist obligatoire: $function part brut dans OnClick / $jsafter (XSS reflechi).
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    if($function==""){$function="UsrSysRefresh";}
    $topbuttons[] = array("Loadjs('$page?groupid-useradd-js=-1&function=$function&nogroup=yes');", ico_add_user, "{create_user}");
    $topbuttons[] = array("Loadjs('$page?new-group-js=yes&function=$function')", ico_add_group, "{new_group}");
    $topbuttons[] = array("Loadjs('$page?reconfigure-js=yes')", ico_retweet, "{usr_apply_system}");

    $EnableOpenSSH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenSSH"));

    $zAllowsGroup=sysusers_ssh_groups();

    $data=sysusers_list();
    if($data===null){
        echo $tpl->div_error(sysusers_list_error());
        return false;
    }

    $rows=array();
    $total=0;
    $created=0;
    $sshGroups=0;
    $groups=sysusers_groups();
	foreach ($groups as $jsClass){
        $total++;
        if(!isBanned($jsClass->name)){$created++;}
        if(isset($zAllowsGroup[$jsClass->name])){$sshGroups++;}
        $md=md5(json_encode($jsClass));
        $rows[]=usr_group_row($jsClass,$md,$zAllowsGroup,$EnableOpenSSH,$function);
    }

    $html=array();
    $html[]=sysusers_meta_banner();
    $html[]="<div class='ak-grid-4 usr-kpis'>";
    $html[]=usr_kpi("{groups2}",$total,"{usr_kpi_groups_sub}");
    $html[]=usr_kpi("{usr_kpi_created}",$created,"{usr_kpi_created_sub}",true);
    $html[]=usr_kpi("{usr_kpi_ssh}",$EnableOpenSSH==1?$sshGroups:"-","{usr_kpi_ssh_sub}");
    $html[]=usr_kpi_openssh($EnableOpenSSH);
    $html[]="</div>";

    $html[]="<div class='ak-card'>";
    $html[]="<div class='usr-bar'>";
    $html[]="<label class='ak-search usr-search'><span><i class='fa-solid fa-magnifying-glass'></i></span>";
    $html[]="<input type='search' id='usr-q' placeholder=\"{usr_search_placeholder}\" aria-label=\"{usr_search_placeholder}\" autocomplete='off'></label>";
    $html[]="<div class='ak-seg' role='group'>";
    $html[]="<button type='button' id='usr-seg-created' class='ak-seg-b on' aria-pressed='true'>{usr_kpi_created} ($created)</button>";
    $html[]="<button type='button' id='usr-seg-all' class='ak-seg-b' aria-pressed='false'>{all_groups} ($total)</button>";
    $html[]="</div></div>";
    $html[]="<table class='ak-tbl usr-tbl'><thead><tr>";
    $html[]="<th scope='col'>{group}</th>";
    $html[]="<th scope='col'>{members}</th>";
    $html[]="<th scope='col'>{usr_card_ssh_title}</th>";
    $html[]="<th scope='col' class='ak-end'>{actions}</th>";
    $html[]="</tr></thead><tbody id='usr-rows'>";
    $html[]=@implode("\n",$rows);
    $html[]="</tbody></table>";
    $html[]="<div class='usr-empty' id='usr-empty' style='display:none'><span><i class='fa-solid fa-users-slash'></i></span>"
        ."{usr_no_match}<br><button type='button' onclick='UsrSysClear()'>{usr_clear_search}</button></div>";
    $html[]="<div class='ak-card-f'><span>{showing} <b id='usr-shown'>0</b> / <b>$total</b> {system_groups}</span>";
    $html[]="<span class='ak-pg' id='usr-pg'></span></div>";
    $html[]="</div>";

    $TINY_ARRAY["TITLE"]="{system_users}";
    $TINY_ARRAY["ICO"]=ico_group;
    $TINY_ARRAY["EXPL"]="{system_users_explain}";
    $TINY_ARRAY["URL"]="system-users";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // NoSpinner() reste obligatoire, sinon le loader ne s'arrete jamais.
	$html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n$jstiny</script>";

    // CSS et JS concatenes AUTOUR du parsing, jamais dedans.
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html)).usr_js($page);
	return true;
}
