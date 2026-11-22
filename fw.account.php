<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
    $GLOBALS["DEBUG_PRIVS"]=true;
}
if(isset($_GET["form-change-pass-sqlite-popup"])){change_password_slite_popup();exit;}
if(isset($_GET["form-change-pass-sqlite"])){change_password_slite_js();exit;}
if(isset($_POST["formchangepasssqlite"])){change_password_slite_perform();exit;}
if(isset($_POST["change_admin"])){if(!account_is_manager()){account_refuse_post("change_admin");exit;}change_manager_perform();exit;}
if(isset($_GET["apilink-js"])){API_LINK_JS();exit;}
if(isset($_GET["apilink-popup"])){API_LINK_POPUP();exit;}
if(isset($_POST["apilink"])){API_LINK_SAVE();exit;}
if(isset($_GET["table"])){privileges();exit;}
if(isset($_POST["userfont"])){lang();exit;}
if(isset($_POST["lang2"])){change_language_perform();exit;}
if(isset($_GET["change-language-js"])){change_language_js();exit;}
if(isset($_GET["change-language-popup"])){change_language_form();exit;}
if(isset($_GET["after-language"])){change_language_after();exit;}
if(isset($_GET["change-manager-popup"])){if(!account_is_manager()){account_refuse_popup();exit;}change_manager_popup();exit;}
if(isset($_GET["change-manager-js"])){if(!account_is_manager()){account_refuse_js();exit;}change_manager_js();exit;}
if(isset($_GET["client-certificate"])){client_certificate();exit;}
if(isset($_GET["pfx-manager"])){client_certificate_download();exit;}
if(isset($_GET["manager-password-js"])){if(!account_is_manager()){account_refuse_js();exit;}manager_password_js();exit;}
if(isset($_GET["manager-password-popup"])){if(!account_is_manager()){account_refuse_popup();exit;}manager_password_popup();exit;}
if(isset($_POST["MANAGERPASS"])){if(!account_is_manager()){account_refuse_post("MANAGERPASS");exit;}manager_password_save();exit;}
if(isset($_GET["form-js"])){form_js();exit;}
if(isset($_GET["form-popup"])){form_popup();exit;}
page();

// Les identifiants du super-administrateur (Manager) ne se changent que depuis la
// session du Manager lui-meme (uid -100), exactement ce que l'interface affichait
// deja (page : `if($_SESSION["uid"]==-100)`). Jusqu'au 2026-10-07 les points
// d'entree ne le verifiaient pas : n'importe quel compte connecte -- un
// administrateur DNS delegue par exemple -- pouvait POSTer change_admin ou
// MANAGERPASS et prendre le compte Manager.
function account_is_manager():bool{
    return isset($_SESSION["uid"]) && (string)$_SESSION["uid"]==="-100";
}
function account_refuse_track($what){
    $uid=isset($_SESSION["uid"])?preg_replace('/[^A-Za-z0-9_.@\-]/','',(string)$_SESSION["uid"]):"-";
    $msg="Web console: $what on the super-administrator account refused for $uid (not the Manager session)";
    error_log("Artica account guard: $msg");
    if(function_exists("admin_tracks")){@admin_tracks($msg);}
}
function account_refuse_post($field){
    account_refuse_track("POST $field");
    http_response_code(403);
    $tpl=new template_admin();
    echo $tpl->post_error("{ERROR_NO_PRIVS2}");
}
function account_refuse_js(){
    account_refuse_track("dialog");
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $tpl->js_error("{ERROR_NO_PRIVS2}");
}
function account_refuse_popup(){
    account_refuse_track("form");
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS2}"));
}

function API_LINK_JS():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	return $tpl->js_dialog1("Auth Link", "$page?apilink-popup=yes");
}
function change_manager_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{SuperAdmin}", "$page?change-manager-popup=yes");
}
function change_language_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{language}", "$page?change-language-popup=yes");
}
function change_password_slite_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{password}", "$page?form-change-pass-sqlite-popup=yes",550);
}
function change_password_slite_popup():bool{
    $tpl=new template_admin();

    $form[]=$tpl->field_password2("formchangepasssqlite", "{password}", null,true);
    $html[]=$tpl->form_outside("", $form,null,"{apply}","dialogInstance1.close();");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function change_password_slite_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=0;
    if(strlen($_POST["formchangepasssqlite"])<3){
        echo $tpl->post_error("Passwords must be at least 3 characters long");
        return false;
    }
    $passmd5=md5(trim($_POST["formchangepasssqlite"]));
    if(isset($_SESSION["SQLITE_ID"])) {
        if ($_SESSION["SQLITE_ID"] > 0) {
            $ID = intval($_SESSION["SQLITE_ID"]);
        }
    }
    if($ID==0){
        echo $tpl->post_error("Wrong username");
        return false;
    }
    $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/localadmins/users/$ID/password",array("passmd5"=>$passmd5))),true);
    if(!is_array($resp) || empty($resp["success"])){
        $err=is_array($resp)?strval($resp["error"] ?? "unknown error"):"daemon unreachable";
        echo $tpl->post_error($err);
        return false;
    }
    return admin_tracks("Change user password");

}
function manager_password_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ldap=new clladp();
    $change_admin=$ldap->ldap_admin;
    $change_password=trim($_POST["MANAGERPASS"]);
    if($change_password==$ldap->ldap_password){
        return admin_tracks("Super-admin credentials posted without changes");

    }
    $password_len=strlen($change_password);
    if($password_len< 3){
        return admin_tracks("Super-admin credentials posted without changes ( $password_len < 3 )");
    }
    // ChangeLDPSSET est ecrit par le daemon (RestSystemManagerPassword) a partir des
    // deux seules valeurs utiles ; la page y ajoutait tout le $_POST.
    $data=urlencode(base64_encode("$change_admin|||$change_password"));

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/password/manager/$data"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    return admin_tracks("SuperAdmin credentials as been changed $change_admin with a password of $password_len characters");

}
function change_manager_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ldap=new clladp();
    $change_admin=trim($_POST["change_admin"]);
    $change_password=trim($_POST["change_password"]);
    if(strpos("\"",$change_password)>0){
        echo $tpl->post_error("{double_quotes_not_supported}");
        return false;
    }

    if(trim(strtolower($change_admin))==strtolower($ldap->ldap_admin)){
        if($change_password==$ldap->ldap_password){
            admin_tracks("Super-admin credentials posted without changes");
            echo $tpl->post_error("{no_change}");
            return false;
        }
    }
    $password_len=strlen($change_password);
    // ChangeLDPSSET est ecrit par le daemon (RestSystemManagerPassword).


    $data=urlencode(base64_encode("{$_POST["change_admin"]}|||{$_POST["change_password"]}"));


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/password/manager/$data"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    


    return admin_tracks("SuperAdmin credentials as been changed from $ldap->ldap_admin to $change_admin with a password of $password_len characters");
}


function change_manager_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ldap=new clladp();

   

    $f[]="dialogInstance1.close();LoadAjax('table-loader','$page?table=yes');";

    $field[]=$tpl->field_text("change_admin","{SuperAdmin}",$ldap->ldap_admin,true);
    $field[]=$tpl->field_password2("change_password", "{password}", $ldap->ldap_password,true);
    $ht[]="<div id='ch-super-admin-progress'></div>";
    $ht[]=$tpl->form_outside("{SuperAdmin}", @implode("\n", $field),"{SuperAdminChangeExplain}","{apply}",@implode(";",$f));
    echo $tpl->_ENGINE_parse_body($ht);
    return true;

}

function API_LINK_POPUP():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API("/localadmins/apis?userid=".urlencode(strval($_SESSION["uid"])))),true);
	$ID=0;$zmd5=null;
	if(is_array($resp) && !empty($resp["success"]) && is_array($resp["data"] ?? null)){
		$ID=intval($resp["data"]["id"] ?? 0);
		$zmd5=strval($resp["data"]["zmd5"] ?? "");
	}
	$button="{create_auth_link}";
	$HTTP_HOST=$_SERVER["HTTP_HOST"];
    $form=array();



	if($ID>0){
		$LINK="https://$HTTP_HOST/auth/$zmd5";
		$form[]=$tpl->field_text("nothing", "{link}", $LINK);
		$button="{update_auth_link}";
		
	}
	$tpl->field_hidden("apilink", "$ID");
	echo $tpl->form_outside("Auth Link", $form,"{authlink_explain}",$button,"Loadjs('$page?apilink-js=yes')");
    return true;
}

function API_LINK_SAVE(){
	$FULLCONTENT=base64_encode(serialize($_SESSION));
	$uid=strval($_SESSION["uid"]);
	// L'identifiant du lien n'est PAS celui que poste le navigateur : le daemon met a
	// jour par ID sans verifier le proprietaire (UPDATE ... WHERE ID=?), un compte
	// pouvait donc ecraser le lien d'un autre. On relit celui du compte de la session.
	$ID=0;
	$cur=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API("/localadmins/apis?userid=".urlencode($uid))),true);
	if(is_array($cur) && !empty($cur["success"]) && is_array($cur["data"] ?? null)){
		$ID=intval($cur["data"]["id"] ?? 0);
	}

	$resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/localadmins/apis",
		array("id"=>$ID,"userid"=>$uid,"content"=>$FULLCONTENT))),true);
	if(!is_array($resp) || empty($resp["success"])){
		echo is_array($resp)?strval($resp["error"] ?? "unknown error"):"daemon unreachable";
	}
	
	
	
	
}

function old_page(){
    $page=CurrentPageName();
    $username=$_SESSION["uid"];
    if($username==-100){ $ldap=new clladp(); $username="$ldap->ldap_admin/{administrator}"; }
    if(isset($_SESSION["ACTIVE_DIRECTORY_INFO"])){ $username=$_SESSION["ACTIVE_DIRECTORY_INFO"]["displayName"][0]; }
    $html="
<div class=\"row border-bottom white-bg dashboard-header\">	<div class=\"col-sm-12\"><h1 class=ng-binding>$username</h1></div></div><div class='row'><div id='progress-myaccount-restart'></div><div class='ibox-content'><div id='table-loader'></div></div></div><script>$.address.state('/');$.address.value('/account');LoadAjax('table-loader','$page?table=yes');</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin($username,$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);



}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!method_exists($tpl,"page_header")) {old_page();return true;}

    $username=$_SESSION["uid"];
    if($username==-100){ $ldap=new clladp(); $username="$ldap->ldap_admin/{administrator}"; }

    $html=$tpl->page_header("$username","fad fa-user-tie","{myaccount_text}",
        "$page?table=yes","account","progress-myaccount-restart",false,"table-loader");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin($username,$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return false;
}



function change_language_form(){
    $tpl            = new template_admin();
    $page           = CurrentPageName();
    $htmltools_inc  = new htmltools_inc();
    $lang           = $htmltools_inc->LanguageArray();
    $lang_selected=$_COOKIE["artica-language"];
    $FixedLanguage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FixedLanguage"));
    $lock=0;
    if($FixedLanguage<>null){
        $lang_selected=$FixedLanguage;
        $lock=1;
    }
    $field[]=$tpl->field_array_hash($lang, "lang2", "{mylanguage}", $lang_selected);


    $users=new usersMenus();

    if($users->AsSystemAdministrator){
        $field[]= $tpl->field_section(null,"{fix_language_explain}");
        $field[]=$tpl->field_checkbox("LockLang","{fix_language_perference}",$lock);

    }
    $ht[]=$tpl->form_outside("{mylanguage}", @implode("\n", $field),null,"{apply}","Loadjs('$page?after-language=yes')",null,false,false,"far fa-language");
    echo $tpl->_ENGINE_parse_body($ht);
}

function change_language_perform(){
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $_SESSION["detected_lang"]=$_POST["lang2"];
    // FixedLanguage est un reglage GLOBAL (langue imposee a toute la console) : administrateur
    // systeme seulement, comme la case du formulaire. La langue personnelle (session, cookie)
    // reste modifiable par tout compte.
    $users=new usersMenus();
    if(isset($_POST["LockLang"]) && !$users->AsSystemAdministrator){
        admin_tracks("Refused: lock the Web console language (not a system administrator)");
        unset($_POST["LockLang"]);
    }
    if(isset($_POST["LockLang"])){
        $fix=intval($_POST["LockLang"]);
        if($fix==0){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FixedLanguage","");
        }else{
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FixedLanguage",$_POST["lang2"]);
        }
    }


    admin_tracks("Change Web console language to {$_POST["lang2"]}");
    setcookie("artica-language", $_POST["lang2"], time()+31536000);
}
function change_language_after(){
    $xtime=strtotime( '+30 days' );
    $page=CurrentPageName();
    $language=$_SESSION["detected_lang"];
    $f[]="Delete_Cookie('artica-language', '/', '');";
    $f[]="Set_Cookie('artica-language', '$language', '$xtime', '/', '', '');";
    $f[]="dialogInstance1.close();";
    $f[]="LoadAjax('table-loader','$page?table=yes');";
    $f[]="var uri=document.getElementById('fw-left-menus-uri').value;";
    $f[]="LoadAjaxSilent('left-barr',uri);";
    $f[]="LoadAjaxSilent('top-barr','fw-top-bar.php');";
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
}
function form_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog3("{parameters}", "$page?form-popup=yes");
}
function manager_password_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog3("{SuperAdmin}: {password}", "$page?manager-password-popup=yes",500);
}
function manager_password_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $form[]=$tpl->field_password2("MANAGERPASS","{password}","");
    echo $tpl->form_outside(null, $form,null,"{apply}","dialogInstance3.close();");
    return true;
}

function form_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $HTMLTITLE=null;
    if(!isset($_COOKIE["userfont"])){$_COOKIE["userfont"]="lato";}
    if(!isset($_COOKIE["StandardDropDown"])){$_COOKIE["StandardDropDown"]=0;}
    $userfont=$_COOKIE["userfont"];
    $StandardDropDown=intval($_COOKIE["StandardDropDown"]);
    if(isset($_COOKIE["StandardDropDown"])){$StandardDropDown=intval($_COOKIE["StandardDropDown"]);}
    if(isset($_COOKIE["HTMLTITLE"])){$HTMLTITLE=$_COOKIE["HTMLTITLE"];}
    if($HTMLTITLE==null){$HTMLTITLE="%s (%v)";}

    if($userfont==null){$userfont="lato";}
    $fonts["ailerons"]="Ailerons";
    $fonts["bariol"]="Bariol";
    $fonts["lato"]="Lato";
    $fonts["Roboto"]="Roboto";
    $fonts["standard"]="Standard";

    if($_SESSION["uid"]==-100){
        $ldap=new clladp();
        $field[]=$tpl->field_none_bt("change_admin","{SuperAdmin}",$ldap->ldap_admin,"{change}","Loadjs('$page?change-manager-js=yes')");
        $field[]=$tpl->field_password("change_password", "{password}", $ldap->ldap_password,false,null,true);

    }
    $field[]=$tpl->field_none_bt("lang","{mylanguage}",$_COOKIE["artica-language"],"{change}","Loadjs('$page?change-language-js=yes')");

    $field[]=$tpl->field_array_hash($fonts, "userfont", "{font_family}", $userfont);
    $field[]=$tpl->field_checkbox("StandardDropDown","{standard_dropdown}",$StandardDropDown);
    $field[]=$tpl->field_text("HTMLTITLE","{title_pages}",$HTMLTITLE);
    $zooms=array();
    foreach(console_zoom_levels() as $z){$zooms[$z]=$z==0?"{automatic}":"$z %";}
    $field[]=$tpl->field_array_hash($zooms,"ConsoleZoom","{console_zoom}",console_zoom_current(),false,"{console_zoom_explain}",null,false);

    $tpl->form_add_button("Auth Link", "Loadjs('$page?apilink-js=yes')");
    // ?after-form=yes n'etait aiguille nulle part : la fenetre restait ouverte et la
    // page n'etait pas rafraichie apres l'enregistrement.
    echo $tpl->form_outside(null, @implode("\n", $field),null,"{apply}",
        "dialogInstance3.close();if(window.ConsoleZoomApply){ConsoleZoomApply();}LoadAjaxSilent('table-loader','$page?table=yes');");
    return true;
}

/**
 * Page "Mon compte" (maquette stitch_artica_account_dashboard) : identite et session,
 * apparence, certificat client du Manager, droits du compte et groupes.
 * acc_collect() rassemble les donnees (logique d'origine conservee) ; chaque carte
 * est rendue par sa propre fonction ; CSS et JS concatenes HORS du moteur.
 */
function privileges():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    include_once(dirname(__FILE__)."/ressources/class.translate.rights.inc");
    if(isset($_SESSION["CLASS_TRANSLATE_RIGHTS"]["FINAL_PRIVS"][$_SESSION["uid"]])){
        unset($_SESSION["CLASS_TRANSLATE_RIGHTS"]["FINAL_PRIVS"][$_SESSION["uid"]]);
    }
    $d=acc_collect();

    $html=array();
    $html[]="<div class='acc-wrap ak'>";
    $html[]=acc_card_identity($d);
    $html[]="<div class='acc-grid2".($d["cert"]?"":" acc-grid1")."'>";
    $html[]=acc_card_appearance();
    if($d["cert"]){$html[]=acc_card_certificate_shell();}
    $html[]="</div>";
    $html[]=acc_rights_section($d);
    if(!$d["manager"]){$html[]=acc_card_groups($d);}
    $html[]="</div>";

    $TINY_ARRAY["TITLE"]="{myaccount}";
    $TINY_ARRAY["ICO"]="fad fa-user-tie";
    $TINY_ARRAY["EXPL"]="{acc_page_explain}";
    $TINY_ARRAY["BUTTONS"]=null;
    $script="<script>";
    if($d["cert"]){$script.="LoadAjaxSilent('client-certificate','$page?client-certificate=yes');";}
    $script.="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";

    echo acc_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
    return true;
}

/** Rassemble identite, session, droits et groupes du compte connecte. */
function acc_collect():array{
    $tpl=new template_admin();
    $uid=(string)$_SESSION["uid"];
    $d=array("manager"=>$uid==="-100","name"=>$uid,"type"=>"{acc_type_ldap}","password_js"=>"",
        "privs"=>array(),"web"=>array(),"acls"=>array(),"siem"=>array(),"docker"=>array(),
        "groups"=>array(),"groups_title"=>"{my_groups}","groups_error"=>"","proxy_rules"=>array(),
        "session"=>"","session_warn"=>false,"cert"=>false,"count"=>0);
    $page=CurrentPageName();
    $sqliteId=isset($_SESSION["SQLITE_ID"])?intval($_SESSION["SQLITE_ID"]):0;

    $ldap=new clladp();
    $IsKerbAuth=$ldap->IsKerbAuth();
    $ActiveDirectoryIndex="Nan";
    if(isset($_SESSION["ACTIVE_DIRECTORY_INDEX"]) && is_numeric($_SESSION["ACTIVE_DIRECTORY_INDEX"])){
        $ActiveDirectoryIndex=$_SESSION["ACTIVE_DIRECTORY_INDEX"];
        $IsKerbAuth=true;
    }

    // Identite, type de compte et moyen de changer le mot de passe.
    if($d["manager"]){
        $d["name"]=$ldap->ldap_admin;
        $d["type"]="{acc_type_manager}";
        $d["password_js"]="Loadjs('$page?manager-password-js=yes')";
        $d["cert"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaClientAuth"))==1;
    }elseif($sqliteId>0){
        $d["type"]="{acc_type_local}";
        $d["password_js"]="Loadjs('$page?form-change-pass-sqlite=yes')";
    }elseif($IsKerbAuth){
        $d["type"]="{acc_type_ad}";
    }

    // Duree de session restante.
    $life=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SessionCookieLifetime"));
    if($life>0){
        $final=intval($_SESSION["SESSARTSTART"] ?? time())+($life*60);
        $left=$final>time()?$tpl->time_diff_min($final):0;
        $d["session"]=str_replace("%s","<strong>$left</strong>",$tpl->_ENGINE_parse_body("{you_have_x_minutes}"));
        $d["session_warn"]=$left<10;
    }

    $users=new usersMenus();
    $cr=new TranslateRights(null,$_SESSION["uid"]);
    $r=$cr->GetPrivsArray();

    // Listes de perimetre : services web, regles d'acces, tenants SIEM, droits Docker.
    foreach($users->NGINX_SERVICES as $ServicesID=>$none){
        if(!is_numeric($ServicesID)){continue;}
        $name=get_nginx_servicename($ServicesID);
        if(strlen($name)>3){$d["web"][]=$name;}
    }
    foreach($users->SIMPLE_ACLS as $aclid=>$none){
        $row=acls_api_get("/proxy/simple-acls/".intval($aclid));
        if(!is_array($row) || !isset($row["aclName"])){continue;}
        $d["acls"][intval($aclid)]=(string)$row["aclName"];
    }
    if(property_exists($users,"SIEM_TENANTS") && is_array($users->SIEM_TENANTS) && count($users->SIEM_TENANTS)>0
        && intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaSiem"))==1){
        $names=array();
        if(!function_exists("siem_tenants_available")){
            @include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
        }
        if(function_exists("siem_tenants_available")){
            $avail=siem_tenants_available();
            if(is_array($avail)){$names=$avail;}
        }
        foreach($users->SIEM_TENANTS as $tenant_id=>$none){
            $tenant_id=trim((string)$tenant_id);
            if($tenant_id===""){continue;}
            $d["siem"][]=(isset($names[$tenant_id]) && $names[$tenant_id]!==$tenant_id)
                ? $names[$tenant_id]." (".$tenant_id.")" : $tenant_id;
        }
    }
    include_once(dirname(__FILE__)."/ressources/class.docker.manager.inc");
    $dockerPerms=docker_perms();
    if(count($dockerPerms)>0){
        if($dockerPerms===array("DOCKER_ADMIN")){
            $d["docker"][]=array("{docker_full_access}",docker_perms_origin());
        }else{
            foreach(docker_priv_table() as $row){
                if(!in_array($row["perm"],$dockerPerms,true)){continue;}
                $d["docker"][]=array($row["family"]." : ".$row["label"],$row["perm"]);
            }
        }
    }

    // Privileges oui/non.
    if($users->AsHamrpAdmin){$d["privs"][]="{APP_HAMRP}";}
    foreach($r as $key=>$val){
        if(strpos($key,"AsDocker")===0 && $key!=="AsDockerAdmin" && $key!=="AsDockerReverse"){continue;}
        if($users->$key){$d["privs"][]="{{$key}}";}
    }
    $d["count"]=count($d["privs"])+count($d["web"])+count($d["acls"])+count($d["siem"])+count($d["docker"]);

    if($d["manager"]){return $d;}

    // Groupes : Active Directory, annuaire LDAP local, groupes d'administrateurs locaux.
    if($IsKerbAuth){
        $d["groups_title"]="{my_microsoft_groups}";
        include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
        $ad=new external_ad_search(null,$ActiveDirectoryIndex);
        $groups=$ad->GroupsOfMember($_SESSION["ACTIVE_DIRECTORY_DN"] ?? "");
        if(is_array($groups)){
            foreach($groups as $dn=>$name){$d["groups"][]=array((string)$name,(string)$dn);}
        }
    }else{
        $ct=new user($_SESSION["uid"]);
        $groups=$ct->GetGroups($_SESSION["uid"]);
        if(is_array($groups)){
            foreach($groups as $name=>$gid){$d["groups"][]=array((string)$name,"");}
        }
    }
    if($sqliteId>0){
        $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API("/localadmins/users/$sqliteId/groups")),true);
        if(!is_array($resp) || empty($resp["success"])){
            $d["groups_error"]=is_array($resp)?strval($resp["error"] ?? "unknown error"):"{unable_to_connect_to_the_service}";
        }else{
            foreach((is_array($resp["data"] ?? null)?$resp["data"]:array()) as $ligne){
                $g=strval($ligne["groupname"] ?? "");
                $d["groups"][]=array($g==""?"{unknown}":$g,"");
            }
        }
    }

    // Regles proxy dynamiques heritees de la session.
    if(isset($_SESSION["SQUID_DYNAMIC_ACLS"]) && is_array($_SESSION["SQUID_DYNAMIC_ACLS"]) && count($_SESSION["SQUID_DYNAMIC_ACLS"])>0){
        $q=new mysql_squid_builder();
        foreach($_SESSION["SQUID_DYNAMIC_ACLS"] as $gpid=>$val){
            $ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID=".intval($gpid)));
            if(is_array($ligne)){$d["proxy_rules"][]=(string)$ligne["GroupName"];}
        }
    }
    return $d;
}

/** Initiales d'un nom de compte (2 caracteres, multi-octets). */
function acc_initials($name):string{
    $name=trim(preg_replace('#[^\p{L}\p{N} ._-]#u','',(string)$name));
    if($name===""){return "?";}
    $parts=preg_split('#[ ._-]+#',$name);
    if(count($parts)>1 && $parts[1]!==""){
        return mb_strtoupper(mb_substr($parts[0],0,1).mb_substr($parts[1],0,1));
    }
    return mb_strtoupper(mb_substr($name,0,2));
}

/** Carte d'identite : avatar, nom, type de compte, session, mot de passe. */
function acc_card_identity($d):string{
    $h="<section class='ak-card ak-card-pad acc-card acc-identity'>";
    $h.="<span class='acc-avatar' aria-hidden='true'>".htmlspecialchars(acc_initials($d["name"]))."</span>";
    $h.="<div class='acc-identity-body'><div class='acc-name-row'><h2>".htmlspecialchars($d["name"])."</h2>";
    $h.="<span class='ak-pill ak-ok'>".$d["type"]."</span></div>";
    $cls=$d["session_warn"]?"acc-session acc-session-warn":"acc-session";
    $txt=$d["session"]<>""?"{sessionid_label} : ".$d["session"]:"{acc_session_unlimited}";
    $h.="<div class='$cls'><span class='acc-session-ico'><i class='far fa-clock'></i></span>$txt</div></div>";
    if($d["password_js"]<>""){
        $h.="<button type='button' class='ak-btn ak-btn-lg ak-btn-primary' onclick=\"{$d["password_js"]}\"><i class='fas fa-key'></i>{acc_change_password}</button>";
    }else{
        $h.="<p class='acc-note'>{acc_password_directory}</p>";
    }
    $h.="</section>";
    return $h;
}

/** Carte Apparence : langue, police, listes deroulantes, titre des onglets. */
function acc_card_appearance():string{
    $page=CurrentPageName();
    $font=$_COOKIE["userfont"] ?? "lato";
    $fonts=array("ailerons"=>"Ailerons","bariol"=>"Bariol","lato"=>"Lato","Roboto"=>"Roboto","standard"=>"Standard");
    $fontName=$fonts[$font] ?? "Lato";
    $dd=intval($_COOKIE["StandardDropDown"] ?? 0)==1;
    $title=trim((string)($_COOKIE["HTMLTITLE"] ?? ""));
    if($title===""){$title="%s (%v)";}
    $lang=trim((string)($_COOKIE["artica-language"] ?? ""));

    $h="<section class='ak-card ak-card-pad acc-card'>";
    $h.="<div class='acc-card-head'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-palette'></i></span><h3>{skin}</h3>";
    $h.="<button type='button' class='ak-btn' onclick=\"Loadjs('$page?form-js=yes')\"><i class='fas fa-sliders'></i>{modify}</button></div>";
    $h.="<dl class='acc-rows'>";
    if($lang<>""){$h.="<dt>{mylanguage}</dt><dd><span class='ak-tag ak-off'>".htmlspecialchars(strtoupper($lang))."</span></dd>";}
    $h.="<dt>{font_family}</dt><dd><span class='ak-tag ak-off'>".htmlspecialchars($fontName)."</span></dd>";
    $h.="<dt>{console_zoom}</dt><dd><span class='ak-tag ak-off'>".(console_zoom_current()==0?"{automatic}":console_zoom_current()." %")."</span></dd>";
    $h.="<dt>{standard_dropdown}</dt><dd>".($dd?"<span class='ak-pill ak-ok'><span class='acc-uc'>{yes}</span></span>":"<span class='ak-pill ak-off'><span class='acc-uc'>{no}</span></span>")."</dd>";
    $h.="</dl>";
    $h.="<div class='acc-field'><span class='ak-kicker'>{title_pages}</span>";
    $h.="<div class='acc-tabpreview'><i class='far fa-window-maximize'></i><span>".htmlspecialchars($title)."</span></div>";
    $h.="<span class='ak-help'>{acc_title_help}</span></div>";
    $h.="</section>";
    return $h;
}

/** Enveloppe de la carte Certificat client ; son contenu vient de ?client-certificate=yes. */
function acc_card_certificate_shell():string{
    $h="<section class='ak-card ak-card-pad acc-card'>";
    $h.="<div class='acc-card-head'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-file-certificate'></i></span><h3>{acc_client_certificate}</h3></div>";
    $h.="<p class='acc-explain'>{acc_client_certificate_explain}</p>";
    $h.="<div id='client-certificate'></div></section>";
    return $h;
}

/** Section des droits : privileges puis listes de perimetre, chacune dans sa carte. */
function acc_rights_section($d):string{
    $h="<div class='acc-section'><h2>{acc_rights_title}</h2><p>{acc_rights_explain}</p></div>";
    $h.="<section class='ak-card ak-card-pad acc-card'>";
    $h.="<div class='acc-card-head'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-user-shield'></i></span><h3>{my_privileges}</h3></div>";
    if(count($d["privs"])==0){
        $cls=$d["count"]==0?"ak-banner ak-warn acc-empty-warn":"ak-empty";
        $h.="<div class='$cls'><i class='fas fa-lock'></i> {ERROR_NO_PRIVS2}</div>";
    }else{
        $h.="<ul class='acc-chips acc-chips-3'>";
        foreach($d["privs"] as $p){$h.="<li class='acc-right'><span class='acc-check'><i class='fas fa-check'></i></span><span>$p</span></li>";}
        $h.="</ul>";
    }
    $h.="</section>";

    $cards=array();
    if(count($d["web"])>0){
        $items=array();
        foreach($d["web"] as $n){$items[]="<li class='acc-right'><span class='acc-li-ico'><i class='fas fa-globe'></i></span><span>".htmlspecialchars($n)."</span></li>";}
        $cards[]=acc_list_card("fas fa-globe","{myWebServices}","{myWebServices_text}",$items);
    }
    if(count($d["acls"])>0){
        $items=array();
        foreach($d["acls"] as $id=>$n){
            $items[]="<li><a class='acc-right acc-right-link' href='javascript:blur();' onclick=\"Loadjs('fw.proxy.rules.php?rule-settings=$id')\"><span class='acc-li-ico'><i class='fas fa-shield-halved'></i></span><span>".htmlspecialchars($n)."</span><span class='acc-chev'><i class='fas fa-chevron-right'></i></span></a></li>";
        }
        $cards[]=acc_list_card("fas fa-shield-halved","{access_rules}","",$items);
    }
    if(count($d["siem"])>0){
        $items=array();
        foreach($d["siem"] as $n){$items[]="<li class='acc-right'><span class='acc-li-ico'><i class='fas fa-building'></i></span><span>".htmlspecialchars($n)."</span></li>";}
        $cards[]=acc_list_card("fas fa-building","{siem_tenants}","{siem_tenants_account_text}",$items);
    }
    if(count($d["docker"])>0){
        $items=array();
        foreach($d["docker"] as $row){
            $items[]="<li class='acc-right'><span class='acc-li-ico'><i class='fab fa-docker'></i></span><span>$row[0]</span><span class='acc-code'>".htmlspecialchars((string)$row[1])."</span></li>";
        }
        $cards[]=acc_list_card("fab fa-docker","{docker_my_privileges}","{docker_my_privileges_text}",$items);
    }
    if(count($cards)>0){$h.="<div class='acc-grid2'>".@implode("",$cards)."</div>";}
    return $h;
}

/** Une carte de liste de perimetre (services, regles, tenants, Docker). */
function acc_list_card($ico,$title,$explain,$items):string{
    $h="<section class='ak-card ak-card-pad acc-card'>";
    $h.="<div class='acc-card-head'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='$ico'></i></span><h3>$title</h3></div>";
    if($explain<>""){$h.="<p class='acc-explain'>$explain</p>";}
    $h.="<ul class='acc-chips'>".@implode("",$items)."</ul></section>";
    return $h;
}

/** Carte des groupes du compte (AD avec DN, LDAP, administrateurs locaux) et regles proxy. */
function acc_card_groups($d):string{
    $h="<section class='ak-card ak-card-pad acc-card'>";
    $h.="<div class='acc-card-head'><span class='ak-ico ak-ico-sm ak-ico-sq'><i class='fas fa-users'></i></span><h3>".$d["groups_title"]."</h3></div>";
    if($d["groups_error"]<>""){
        $h.="<div class='ak-banner ak-warn acc-empty-warn'><i class='fas fa-triangle-exclamation'></i> ".htmlspecialchars($d["groups_error"])."</div>";
    }elseif(count($d["groups"])==0){
        $h.="<div class='ak-empty'>{ERROR_NO_GROUP_ACCOUNT}</div>";
    }else{
        $h.="<ul class='acc-chips acc-chips-2'>";
        foreach($d["groups"] as $g){
            $h.="<li class='acc-right'><span class='acc-li-ico'><i class='fas fa-users'></i></span><span class='acc-group'><span>".htmlspecialchars($g[0])."</span>";
            if($g[1]<>""){$h.="<span class='acc-dn' title=\"".htmlspecialchars($g[1])."\">".htmlspecialchars($g[1])."</span>";}
            $h.="</span></li>";
        }
        $h.="</ul>";
    }
    if(count($d["proxy_rules"])>0){
        $h.="<span class='ak-kicker acc-lbl-gap'>{dynamic_acls_newbee}</span><ul class='acc-chips acc-chips-2'>";
        foreach($d["proxy_rules"] as $n){$h.="<li class='acc-right'><span class='acc-check'><i class='fas fa-check'></i></span><span>".htmlspecialchars($n)."</span></li>";}
        $h.="</ul>";
    }
    $h.="</section>";
    return $h;
}

/** CSS du kit de la page (une fois par requete). */
function acc_style():string{
    if(isset($GLOBALS["ACC_STYLE_DONE"])){return "";}
    $GLOBALS["ACC_STYLE_DONE"]=true;
    $soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    // Seules les regles propres a la page : le reste vient du kit ak- (angular.css.php).
    return "<style>
.acc-wrap{margin-top:20px}
.acc-card{margin-bottom:24px}
.acc-identity{display:flex;align-items:center;gap:24px}
.acc-avatar{width:72px;height:72px;border-radius:50%;background:$soft;color:#00504a;display:inline-flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;flex:0 0 auto}
.acc-identity-body{flex:1 1 auto;min-width:0}
.acc-name-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.acc-name-row h2{font-size:24px;font-weight:600;margin:0;color:#111d23}
.acc-session{display:flex;align-items:center;gap:8px;margin-top:8px;font-size:14px;color:#3d4947}
.acc-session strong{color:#111d23;font-variant-numeric:tabular-nums}
.acc-session-warn,.acc-session-warn strong{color:#865300}
.acc-session-ico{color:#6d7a77;display:inline-flex}
.acc-note{font-size:13px;color:#3d4947;max-width:280px;margin:0;text-align:right}
.acc-grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 24px;align-items:stretch}
.acc-grid1{grid-template-columns:1fr}
.acc-card-head{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.acc-card-head h3{font-size:17px;font-weight:600;margin:0;color:#111d23;flex:1 1 auto}
.acc-explain{font-size:14px;color:#3d4947;margin:-4px 0 16px 0;line-height:1.5}
.acc-right-link:focus-visible{outline:2px solid #006a62;outline-offset:2px}
.acc-uc{display:inline-block}
.acc-uc::first-letter{text-transform:uppercase}
.acc-rows{display:grid;grid-template-columns:1fr auto;gap:12px 16px;align-items:center;margin:0 0 16px 0;font-size:14px}
.acc-rows dt{font-weight:400;color:#111d23}
.acc-rows dd{margin:0;text-align:right}
.acc-field{display:flex;flex-direction:column;gap:6px;border-top:1px solid #f1f5f9;padding-top:14px}
.acc-lbl-gap{display:block;margin:20px 0 10px 0}
.acc-tabpreview{display:flex;align-items:center;gap:10px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;font-size:14px;color:#111d23}
.acc-tabpreview i{color:#6d7a77}
.acc-section{margin:8px 0 16px 0}
.acc-section h2{font-size:18px;font-weight:600;margin:0;color:#111d23}
.acc-section p{font-size:14px;color:#3d4947;margin:4px 0 0 0}
.acc-chips{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:1fr;gap:10px}
.acc-chips-2{grid-template-columns:repeat(2,minmax(0,1fr))}
.acc-chips-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.acc-right{display:flex;align-items:center;gap:10px;min-height:42px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;font-size:14px;color:#111d23;line-height:1.35}
.acc-right-link{text-decoration:none;color:#111d23;transition:background .15s}
.acc-right-link:hover{background:#e3f0f8;color:#111d23}
.acc-check{width:22px;height:22px;border-radius:50%;background:$soft;color:#006a62;display:inline-flex;align-items:center;justify-content:center;font-size:11px;flex:0 0 auto}
.acc-li-ico{width:22px;display:inline-flex;justify-content:center;color:#006a62;font-size:14px;flex:0 0 auto}
.acc-chev{margin-left:auto;color:#6d7a77;font-size:11px}
.acc-code{margin-left:auto;font-size:13px;color:#6d7a77;letter-spacing:.03em;white-space:nowrap}
.acc-group{display:flex;flex-direction:column;min-width:0}
.acc-dn{font-size:13px;color:#6d7a77;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.acc-empty-warn{font-weight:600}
.acc-cert-state{margin-bottom:16px;font-weight:600}
.acc-cert-actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
.acc-cert-renew{margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9;font-size:13px;color:#3d4947;display:flex;gap:10px;align-items:center;flex-wrap:wrap}
@media(max-width:1100px){.acc-grid2,.acc-chips-2,.acc-chips-3{grid-template-columns:1fr}}
</style>";
}

function lang(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $userfont=$_POST["userfont"];
    $StandardDropDown=$_POST["StandardDropDown"];
    $HTMLTITLE=$_POST["HTMLTITLE"];
    $HTMLTITLE=str_replace("'", "`", $HTMLTITLE);


    setcookie("userfont", $userfont, time()+172800);
    setcookie("StandardDropDown", $StandardDropDown, time()+172800);
    setcookie("HTMLTITLE", $HTMLTITLE, time()+172800);
    // Zoom de la console : liste fermee (la valeur finit dans du CSS), un an, tout le site.
    if(isset($_POST["ConsoleZoom"])){
        $zoom=intval($_POST["ConsoleZoom"]);
        if(!in_array($zoom,console_zoom_levels(),true)){
            echo $tpl->post_error("{console_zoom}: $zoom");
            return;
        }
        setcookie("ConsoleZoom", (string)$zoom, time()+31536000, "/");
    }

}
/**
 * Contenu de la carte "Certificat client" du Manager (charge dans #client-certificate).
 * L'ancien bouton "Afficher le certificat" lancait la MEME action que "Creer" et
 * regenerait donc le certificat en service : il est retire, aucune vue n'existe.
 * Recreer un certificat invalide celui deja installe : une confirmation le dit.
 */
function client_certificate(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    if($_SESSION["uid"]<>-100){return false;}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaClientAuth"))==0){return false;}
    $js=$tpl->framework_buildjs("/webconsole/client-cert/manager",
        "manager-certificate.progress","manager-certificate.log",
        "progress-myaccount-restart",
        "LoadAjaxSilent('client-certificate','$page?client-certificate=yes');");
    $has=strlen(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdManagerClientAuth")))>20;
    $html=array();
    if(!$has){
        $html[]="<div class='ak-banner ak-off acc-cert-state'><i class='far fa-circle'></i> {acc_cert_none}</div>";
        $html[]="<div class='acc-cert-actions'><button type='button' class='ak-btn' onclick=\"$js\"><i class='fas fa-plus'></i>{create_certificate}</button></div>";
        echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
        return true;
    }
    $downloaded=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdManagerCertDown"))==1;
    // Confirmation integree a framework_buildjs : recreer invalide le certificat installe.
    $jsRenew=$tpl->framework_buildjs("/webconsole/client-cert/manager",
        "manager-certificate.progress","manager-certificate.log",
        "progress-myaccount-restart",
        "LoadAjaxSilent('client-certificate','$page?client-certificate=yes');",null,"{acc_cert_renew_confirm}");
    $html[]="<div class='ak-banner ak-ok acc-cert-state'><i class='fas fa-circle-check'></i> {acc_cert_ready}";
    $html[]=$downloaded?"<span class='ak-pill ak-ok' style='margin-left:auto'>{acc_cert_downloaded}</span>":"<span class='ak-pill ak-warn' style='margin-left:auto'>{acc_cert_not_downloaded}</span>";
    $html[]="</div>";
    $html[]="<div class='acc-cert-actions'><button type='button' class='ak-btn' onclick=\"window.location.href='$page?pfx-manager=yes'\"><i class='fas fa-download'></i>{acc_cert_download}</button></div>";
    $html[]="<div class='acc-cert-renew'><button type='button' class='ak-link' onclick=\"$jsRenew\"><i class='fas fa-rotate'></i>{acc_cert_renew}</button><span>{acc_cert_renew_warn}</span></div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}
function client_certificate_download(){
    if($_SESSION["uid"]<>"-100"){die();}
    $LighttpdManagerClientAuth=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdManagerClientAuth"));
    $fsize=strlen($LighttpdManagerClientAuth);
    if($fsize==0){die();}
    $content_type=" application/x-pkcs12";
    $tfilename=php_uname("n")."-artica-webconsole.pfx";
    header('Content-type: '.$content_type);
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$tfilename\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    admin_tracks("Downloaded Web console Client PFX Certificate");
    echo $LighttpdManagerClientAuth;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LighttpdManagerCertDown",1);


}
function get_nginx_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_services/ID/$ID");
    $data=json_decode($resp,true);
    if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){
        return "Unknown";
    }
    $row=$data["rows"][0];
    return isset($row["servicename"]) ? strval($row["servicename"]) : "Unknown";
}