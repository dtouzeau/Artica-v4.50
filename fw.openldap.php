<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["EnableLDAPSyncProv"])){cluster_save();exit;}
if(isset($_POST["SyncProvUserDN"])){cluster_save();exit;}
if(isset($_POST["EnableLDAPSyncProvClient"])){cluster_save();exit;}
if(isset($_GET["cluster"])){cluster();exit;}
if(isset($_GET["cluster2"])){cluster2();exit;}
if(isset($_GET["cluster-flat"])){cluster_flat();exit;}
if(isset($_GET["cluster-general-js"])){cluster_general_js();exit;}
if(isset($_GET["cluster-general-popup"])){cluster_general_popup();exit;}
if(isset($_GET["cluster-server-js"])){cluster_server_js();exit;}
if(isset($_GET["cluster-server-popup"])){cluster_server_popup();exit;}
if(isset($_GET["cluster-client-js"])){cluster_client_js();exit;}
if(isset($_GET["cluster-client-popup"])){cluster_client_popup();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table2"])){table2();exit;}
if(isset($_GET["tab"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["RestartOpenLDAP"])){save();exit;}
if(isset($_POST["LdapListenInterface"])){save();exit;}
if(isset($_POST["EnableMultipleOrganizations"])){save();exit;}
if(isset($_POST["LdapDBCachesize"])){save();exit;}
if(isset($_GET["form-restart-js"])){form_restart_js();exit;}
if(isset($_GET["form-restart-popup"])){form_restart_popup();exit;}

if(isset($_GET["suffix-js"])){suffix_js();exit;}
if(isset($_GET["suffix-popup"])){suffix_popup();exit;}
if(isset($_GET["join-js"])){join_js();exit;}
if(isset($_GET["join-popup"])){join_popup();exit;}
if(isset($_POST["ChangeLDAPSuffixFrom"])){suffix_save();exit;}
if(isset($_POST["REMOTE_ARTICA_SERVER"])){join_save();exit;}
if(isset($_GET["remove-openldap"])){remove_openldap_js();exit;}
if(isset($_POST["remove-openldap"])){remove_openldap_perform();exit;}
if(isset($_GET["flat"])){flat_config();exit;}
if(isset($_GET["form-service-js"])){form_service_js();exit;}
if(isset($_GET["form-service-popup"])){form_service_popup();exit;}
if(isset($_GET["form-service2-js"])){form_service2_js();exit;}
if(isset($_GET["form-service2-popup"])){form_service2_popup();exit;}
if(isset($_GET["form-service3-js"])){form_service3_js();exit;}
if(isset($_GET["form-service3-popup"])){form_service3_popup();exit;}


page();


function form_service_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
   return  $tpl->js_dialog1("{openldap_parameters}","$page?form-service-popup=yes");
}
function form_restart_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog1("{openldap_parameters}","$page?form-restart-popup=yes",850);
}


function form_service2_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog1("{openldap_parameters}","$page?form-service2-popup=yes");
}
function form_service3_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog1("{ldap_configure_bdbd}","$page?form-service3-popup=yes");
}


function remove_openldap_js(){
      $tpl=new template_admin();
    $jsrestart=$tpl->framework_buildjs(
        "/openldap/uninstall",
        "openldap.progress",
        "openldap.log",
        "progress-openldap-restart",
        "document.location.href='/index';"
    );

    $tpl->js_confirm_delete("{APP_OPENLDAP}","remove-openldap","yes",$jsrestart);
}
function remove_openldap_perform(){
    admin_tracks("OpenLDAP server was uninstalled");
}
function join_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{join_a_cluster}","$page?join-popup=yes");
}

function join_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $server=trim($_POST["REMOTE_ARTICA_SERVER"]);
    $port=intval($_POST["REMOTE_ARTICA_SERVER_PORT"]);
    if($port==0){$port=9000;}
    $username=trim($_POST["REMOTE_ARTICA_USERNAME"]);
    if($username==null){$username="Manager";}
    $password=$_POST["REMOTE_ARTICA_PASSWORD"];

    // La cle WizardLdapCluster ne sert plus qu'a re-remplir ce formulaire: elle
    // ne porte PLUS le mot de passe administrateur du master. Il partait ici en
    // clair dans Valkey, et n'y etait efface qu'en cas de SUCCES -- une jointure
    // ratee l'y laissait indefiniment.
    $WizardLdapCluster=serialize(array(
        "REMOTE_ARTICA_SERVER"=>$server,
        "REMOTE_ARTICA_SERVER_PORT"=>$port,
        "REMOTE_ARTICA_USERNAME"=>$username));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WizardLdapCluster",$WizardLdapCluster);

    // Les identifiants voyagent dans le CORPS du POST: le daemon les garde en
    // memoire (usage unique, 5 min) et le GET emis ensuite par framework_buildjs
    // consomme ce ticket. Une URL est journalisee dans articarest.query.log, un
    // corps ne l'est pas.
    $payload=array("server"=>$server,"port"=>$port,"username"=>$username,"password"=>$password);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/openldap/cluster/join",$payload));
    if(!is_object($json) || !isset($json->Status) || !$json->Status){
        $error="{failed}";
        if(is_object($json) && isset($json->Error)){$error=$json->Error;}
        echo $tpl->post_error($error);
        return;
    }
    admin_tracks("Run wizard to join an OpenLDAP Master $server");
}

function join_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $btname="{connect}";
    $title="{connect}";

    $WizardLdapCluster=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardLdapCluster"));
    $REMOTE_ARTICA_SERVER=$WizardLdapCluster["REMOTE_ARTICA_SERVER"];
    $REMOTE_ARTICA_SERVER_PORT=intval($WizardLdapCluster["REMOTE_ARTICA_SERVER_PORT"]);
    $REMOTE_ARTICA_USERNAME=trim($WizardLdapCluster["REMOTE_ARTICA_USERNAME"]);
    $REMOTE_ARTICA_PASSWORD=trim($WizardLdapCluster["REMOTE_ARTICA_PASSWORD"]);
    if($REMOTE_ARTICA_SERVER_PORT==0){$REMOTE_ARTICA_SERVER_PORT=9000;}
    if($REMOTE_ARTICA_USERNAME==null){$REMOTE_ARTICA_USERNAME="Manager";}
    $form[]= $tpl->field_text("REMOTE_ARTICA_SERVER","{REMOTE_ARTICA_SERVER}",$REMOTE_ARTICA_SERVER,true);
    $form[]= $tpl->field_numeric("REMOTE_ARTICA_SERVER_PORT","{REMOTE_ARTICA_SERVER_PORT}",$REMOTE_ARTICA_SERVER_PORT,true);
    $form[]= $tpl->field_text("REMOTE_ARTICA_USERNAME","{REMOTE_ARTICA_USERNAME}",$REMOTE_ARTICA_USERNAME,true);
    $form[]= $tpl->field_password("REMOTE_ARTICA_PASSWORD","{REMOTE_ARTICA_PASSWORD}",$REMOTE_ARTICA_PASSWORD,true);

    $applyjs=$tpl->framework_buildjs(
        "/openldap/cluster/join",
        "openldap.join.prog",
        "openldap.join.log",
        "progress-openldap-restart",
        "LoadAjaxSilent('cluster-parameters','$page?cluster2=yes');"

    );

    $html[]=$tpl->form_outside($title, @implode("\n", $form),"{wizard_com_artica_api}",$btname,
        "dialogInstance2.close();$applyjs",
        "AsSquidAdministrator",true);

    echo $tpl->_ENGINE_parse_body($html);

// wizard_com_artica_api
}

function suffix_js(){
    // Pentest 2026-11-04 : id etait recopie sans encodage dans la page (XSS). C'est un id de DOM.
    $id=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["id"] ?? ""));
    $value=$_GET["value"];
    $valuencode=urlencode($value);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{ldap_suffix}","$page?suffix-popup=$valuencode&id=$id");
}

function suffix_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSystemAdministrator";
    $id=$_GET["id"];
    $value=$_GET["suffix-popup"];

    // Le POST du formulaire arme l'operation cote daemon ; ce GET la lance et la
    // fenetre suit openldap.chsuffix.progress. Le fichier de progression vit
    // desormais dans PROGRESS_DIR (ressources/logs/web), ou notifs.BuildProgress
    // ecrit, et non plus dans ressources/logs.
    $jsrestart=$tpl->framework_buildjs(
        "/openldap/suffix/change",
        "openldap.chsuffix.progress",
        "openldap.chsuffix.progress.txt",
        "progress-openldap-suffix",
        "window.location.href ='logoff.php';");

    $tpl->field_hidden("ChangeLDAPSuffixFrom",$value);
    $html[]="<div id='progress-openldap-suffix'></div>";
    $form[]=$tpl->field_text("ChangeLDAPSuffixTo","{ldap_suffix}",$value,true);
    $html[]=$tpl->form_outside("{modify}: {ldap_suffix}", @implode("\n", $form),
        "{openldap_change_suffix_warning}","{modify}",$jsrestart,$security);

    echo $tpl->_ENGINE_parse_body($html);
}

function suffix_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $to=trim($_POST["ChangeLDAPSuffixTo"]);
    $from=trim($_POST["ChangeLDAPSuffixFrom"]);

    if(strtolower($to) == strtolower($from)){
        echo $tpl->post_error("{no_change}");
        exit;
    }

    // Le daemon valide le suffixe ICI et arme un ticket a usage unique (5 min) :
    // l'operation DETRUIT et reconstruit l'annuaire, une URL seule ne doit pas
    // suffire a la declencher. Valider au POST fait aussi apparaitre un refus
    // dans la modale, et non dans une fenetre de progression qui n'avance pas.
    // Les cles ChangeLDAPSuffixTo/From ne sont plus ecrites : le daemon lit le
    // suffixe courant dans clladp.
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/openldap/suffix/change",array("to"=>$to)));
    if(!is_object($json) || !isset($json->Status) || !$json->Status){
        $error="{failed}";
        if(is_object($json) && isset($json->Error)){$error=$json->Error;}
        echo $tpl->post_error($error);
        exit;
    }
    admin_tracks("Change LDAP Database suffix from $from to $to");
}

function page(){
	$page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{APP_OPENLDAP}",ico_database,"{APP_OPENLDAP_EXPLAIN}",
        "$page?tab=yes","ldap-database","progress-openldap-restart",false,"table-loader-openldap-service");

    $tpl=new template_admin("{APP_OPENLDAP}",$html);

	if(isset($_GET["main-page"])){
		echo $tpl->build_firewall();
		return;
	}
	


	echo $tpl->_ENGINE_parse_body($html);

}
function cluster_save(){
    $tpl=new template_admin();
    // Le bouton est masque sans le privilege, mais la page repond a toute session :
    // le controle se fait ici, cote serveur.
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        echo $tpl->post_error("{ERROR_NO_PRIVS2}");
        return false;
    }
    if(isset($_POST["LDAPSyncProvID"])){
        $id=intval($_POST["LDAPSyncProvID"]);
        if($id<1){$id=1;}
        if($id>999){$id=999;}
        $_POST["LDAPSyncProvID"]=$id;
    }
    $tpl->SAVE_POSTs();
    // Chaque fenetre n'envoie que ses propres champs : le journal decrit ce qui a change.
    if(isset($_POST["EnableLDAPSyncProv"])){
        if(intval($_POST["EnableLDAPSyncProv"])==0){
            admin_tracks("Set LDAP Server Cluster replication to OFF");
        }else{
            admin_tracks("Set LDAP Server Cluster replication to ON (server id {$_POST["LDAPSyncProvID"]})");
        }
    }
    if(isset($_POST["SyncProvUserDN"])){
        admin_tracks("Set LDAP Server Cluster sync user to {$_POST["SyncProvUserDN"]}");
    }
    if(isset($_POST["EnableLDAPSyncProvClient"])){
        admin_tracks("Set LDAP Server Cluster client mode={$_POST["EnableLDAPSyncProvClient"]} master=".(isset($_POST["LDAPSyncProvClientServer"])?$_POST["LDAPSyncProvClientServer"]:""));
    }
    return true;
}
function cluster(){
    $page=CurrentPageName();
    echo "<div id='cluster-parameters'></div>
    <script>LoadAjax('cluster-parameters','$page?cluster2=yes');</script>";
}
function cluster2(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $EnableLDAPSyncProv=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProv"));
    $EnableLDAPSyncProvClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProvClient"));

    $title_add=" ({disabled})";
    if($EnableLDAPSyncProv==1){
        $title_add=" ({master_mode})";
        if($EnableLDAPSyncProvClient==1){$title_add=" ({client_mode})";}
    }

    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px;vertical-align: top'><div id='cluster-status'></div></td>";
    $html[]="<td style='width:99%;vertical-align: top;padding-left:20px'><div id='cluster-flat'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";

    $topbuttons[]=array("Loadjs('$page?join-js=yes');","fas fa-sign-in","{join_a_cluster}");
    $TINY_ARRAY["TITLE"]="{APP_OPENLDAP}:{cluster}$title_add";
    $TINY_ARRAY["ICO"]="fad fa-exchange";
    $TINY_ARRAY["EXPL"]="{APP_OPENLDAP_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>";
    $html[]="LoadAjax('cluster-status','$page?status=yes');";
    $html[]="LoadAjaxSilent('cluster-flat','$page?cluster-flat=yes');";
    $html[]=$headsjs;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}
function cluster_flat(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSystemAdministrator";

    $EnableLDAPSyncProv=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProv"));
    $EnableLDAPSyncProvClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProvClient"));
    $SyncProvUser=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyncProvUserDN"));
    $LDAPSyncProvClientServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientServer"));
    $LDAPSyncProvClientSearchBase=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientSearchBase"));
    $LDAPSyncProvClientBindDN=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientBindDN"));
    $LDAPSyncProvClientBindPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientBindPassword"));
    $LDAPSyncProvID=cluster_sync_id();

    // --- Replication : le service et l'identifiant de ce serveur dans la grappe
    $tpl->table_form_section("{replication}");
    $tpl->table_form_field_js("Loadjs('$page?cluster-general-js=yes')",$security);
    $tpl->table_form_field_bool("{enable_ldap_sync_service}",$EnableLDAPSyncProv,"fad fa-exchange");
    $tpl->table_form_field_text("{uuid}",$LDAPSyncProvID,ico_computer);

    // --- Mode serveur : le compte avec lequel les autres serveurs lisent cet annuaire
    $tpl->table_form_section("{server_mode}");
    if($EnableLDAPSyncProv==1 && $EnableLDAPSyncProvClient==0){
        $LdapListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapListenInterface"));
        if($LdapListenInterface=="" || $LdapListenInterface=="lo"){
            $tpl->table_form_field_warn("{error_server_listen_loopback}");
        }
    }
    $tpl->table_form_field_js("Loadjs('$page?cluster-server-js=yes')",$security);
    if($SyncProvUser==""){$SyncProvUser="{none}";}
    $tpl->table_form_field_text("{sync_user}",$SyncProvUser,ico_admin);

    // --- Mode client : le serveur maitre dont cet annuaire est la copie
    $tpl->table_form_section("{client_mode}");
    $tpl->table_form_field_js("Loadjs('$page?cluster-client-js=yes')",$security);
    $tpl->table_form_field_bool("{enable_client_mode}",$EnableLDAPSyncProvClient,ico_sitemap);
    if($EnableLDAPSyncProvClient==1){
        if($LDAPSyncProvClientServer==""){$LDAPSyncProvClientServer="{none}";}
        if($LDAPSyncProvClientSearchBase==""){$LDAPSyncProvClientSearchBase="{none}";}
        if($LDAPSyncProvClientBindDN==""){$LDAPSyncProvClientBindDN="{none}";}
        // Jamais le mot de passe en clair dans la liste : seulement s'il est defini.
        $pass=strlen($LDAPSyncProvClientBindPassword)>0?"********":"{none}";
        $tpl->table_form_field_text("{ldap_server}",$LDAPSyncProvClientServer,ico_server);
        $tpl->table_form_field_text("{searchbasedn}",$LDAPSyncProvClientSearchBase,ico_database);
        $tpl->table_form_field_text("{ldap_user_dn}",$LDAPSyncProvClientBindDN,ico_admin);
        $tpl->table_form_field_text("{ldap_password}",$pass,ico_lock);
    }

    echo $tpl->table_form_compile(2);
}
function cluster_sync_id():int{
    $id=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvID"));
    if($id==0){$id=1;}
    if($id>999){$id=999;}
    return $id;
}
// Apres chaque enregistrement : fermer la fenetre, redemarrer le service (barre de
// progression du haut de page) puis rafraichir la liste des parametres.
function cluster_jsafter():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsrestart=$tpl->framework_buildjs(
        "/openldap/restart",
        "openldap.progress",
        "openldap.log",
        "progress-openldap-restart",
        "LoadAjaxSilent('cluster-flat','$page?cluster-flat=yes');"
    );
    return "dialogInstance1.close();LoadAjaxSilent('cluster-flat','$page?cluster-flat=yes');$jsrestart";
}
function cluster_general_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{replication}","$page?cluster-general-popup=yes",650);
}
function cluster_server_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{server_mode}","$page?cluster-server-popup=yes",650);
}
function cluster_client_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{client_mode}","$page?cluster-client-popup=yes",650);
}
function cluster_general_popup(){
    $tpl=new template_admin();
    $EnableLDAPSyncProv=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProv"));
    $form[]=$tpl->field_checkbox("EnableLDAPSyncProv","{enable_ldap_sync_service}",$EnableLDAPSyncProv,true);
    $form[]=$tpl->field_numeric("LDAPSyncProvID","{uuid}",cluster_sync_id());
    $html[]=$tpl->div_explain("{replication}||{ldap_cluster_general_explain}");
    $html[]=$tpl->form_outside("", @implode("\n", $form),"","{apply}",cluster_jsafter(),"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
}
function cluster_server_popup(){
    $tpl=new template_admin();
    $SyncProvUser=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyncProvUserDN");
    $form[]=$tpl->field_browse_ldap_user("SyncProvUserDN","{sync_user}",$SyncProvUser);
    $html[]=$tpl->div_explain("{server_mode}||{ldap_cluster_server_explain}");
    $html[]=$tpl->form_outside("", @implode("\n", $form),"","{apply}",cluster_jsafter(),"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
}
function cluster_client_popup(){
    $tpl=new template_admin();
    $EnableLDAPSyncProvClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProvClient"));
    $LDAPSyncProvClientServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientServer");
    $LDAPSyncProvClientSearchBase=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientSearchBase");
    $LDAPSyncProvClientBindDN=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientBindDN");
    $LDAPSyncProvClientBindPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientBindPassword");
    $form[]=$tpl->field_checkbox("EnableLDAPSyncProvClient","{enable_client_mode}",$EnableLDAPSyncProvClient,"LDAPSyncProvClientServer,LDAPSyncProvClientSearchBase,LDAPSyncProvClientBindDN,LDAPSyncProvClientBindPassword");
    $form[]=$tpl->field_text("LDAPSyncProvClientServer","{ldap_server}",$LDAPSyncProvClientServer);
    $form[]=$tpl->field_text("LDAPSyncProvClientSearchBase","{searchbasedn}",$LDAPSyncProvClientSearchBase);
    $form[]=$tpl->field_text("LDAPSyncProvClientBindDN","{ldap_user_dn}",$LDAPSyncProvClientBindDN);
    $form[]=$tpl->field_password("LDAPSyncProvClientBindPassword","{ldap_password}",$LDAPSyncProvClientBindPassword);
    $html[]=$tpl->div_explain("{client_mode}||{ldap_cluster_client_explain}");
    $html[]=$tpl->form_outside("", @implode("\n", $form),"","{apply}",cluster_jsafter(),"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{parameters}"]="$page?table=yes";
    $array["{cluster}"]="$page?cluster=yes";
    echo $tpl->tabs_default($array);

}
function table(){
    $page=CurrentPageName();
    echo "<div id='openldap-parameters'></div>
    <script>LoadAjax('openldap-parameters','$page?table2=yes');</script>";
}

function table2()
{
    $page = CurrentPageName();
    $tpl = new template_admin();

    $jsrestart = $tpl->framework_buildjs(
        "/openldap/restart",
        "openldap.progress",
        "openldap.log",
        "progress-openldap-restart",
        ""
    );


    $jsremove = "Loadjs('$page?remove-openldap=yes');";
    $topbuttons[] = array($jsrestart, ico_refresh, "{restart_service}");
    $topbuttons[] = array($jsremove, ico_trash, "{remove_this_section}");

    $html[] = "<table style='width:100%;margin-top:10px'>";
    $html[] = "<tr>";
    $html[] = "<td style='width:450px;vertical-align:top'>";
    $html[] = "<div id='openldap-status'></div>";
    $html[] = "</td>";
    $html[] = "<td style='width:100%;vertical-align:top;padding-left:20px'>";
    $html[] = "<div id='openldap-flat'></div>";
    $html[] = "</td>";
    $APP_SLAPD_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SLAPD_VERSION");
    if ($APP_SLAPD_VERSION <> null) {
        $APP_SLAPD_VERSION = " v$APP_SLAPD_VERSION";
    }
    $TINY_ARRAY["TITLE"] = "{APP_OPENLDAP}$APP_SLAPD_VERSION";
    $TINY_ARRAY["ICO"] = ico_database;
    $TINY_ARRAY["EXPL"] = "{APP_OPENLDAP_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"] = $tpl->table_buttons($topbuttons);
    $headsjs = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";
    $js = $tpl->RefreshInterval_js("openldap-status", $page, "status=yes");

    $html[] = "</tr>";
    $html[] = "</table>";
    $html[] = "<script>";
    $html[] = $js;
    $html[] = $headsjs;
    $html[] = "LoadAjax('openldap-flat','$page?flat=yes');";
    $html[] = "</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function flat_config(){
    $page = CurrentPageName();
    $tpl = new template_admin();
    $security="AsSystemAdministrator";

    $loglevel_hash=array(
        0=>"{default}",
        256=>"{basic}",
        512=>"stats log entries sent",
        1024=>"Communications",
        16384=>"{synchronization}",
        2048=>"{debug}",
    );
    $CACHE_AGES[0]="{never}";
    $CACHE_AGES[30]="30 {minutes}";
    $CACHE_AGES[60]="1 {hour}";
    $CACHE_AGES[120]="2 {hours}";
    $CACHE_AGES[360]="6 {hours}";
    $CACHE_AGES[720]="12 {hours}";
    $CACHE_AGES[1440]="1 {day}";
    $CACHE_AGES[2880]="2 {days}";
    $CACHE_AGES[4320]="3 {days}";
    $CACHE_AGES[10080]="1 {week}";
    $CACHE_AGES[20160]="2 {weeks}";
    $CACHE_AGES[43200]="1 {month}";


    $RestartOpenLDAP = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RestartOpenLDAP"));
    $RestartLDAPEach = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RestartLDAPEach"));
    $OpenLDAPLogLevel = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPLogLevel"));
    if($OpenLDAPLogLevel==0){$OpenLDAPLogLevel=256;}

    $LockLdapConfig = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockLdapConfig"));
    $LdapAllowAnonymous = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapAllowAnonymous"));
    $LdapListenInterface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapListenInterface"));
    $EnableVirtualDomainsInMailBoxes = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVirtualDomainsInMailBoxes"));
    $EnableMultipleOrganizations = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMultipleOrganizations"));
    $OpenLDAPEnableSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPEnableSSL"));
    $OpenLDAPCertificate = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPCertificate"));
    $sock = new sockets();

    $ldap=new clladp();

    if(strlen($LdapListenInterface)<2){
        $LdapListenInterface="{loopback}";
    }

    if($RestartLDAPEach==0){
        $RestartOpenLDAP=0;
    }
    $EnableOpenLDAPRestFul=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAPRestFul"));
    $OpenLDAPUseMDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPUseMDB"));

    // Un theme par section. Chaque ligne repose son propre table_form_field_js() :
    // le regroupement a separe des lignes qui partageaient un ouvreur par simple
    // voisinage, et switchjs n'est jamais desarme.

    // --- Reseau : ou et comment le service ecoute
    $tpl->table_form_section("{network_settings}");
    $tpl->table_form_field_js("Loadjs('$page?form-service-js=yes')",$security);
    $tpl->table_form_field_text("{listen_interface}", $LdapListenInterface.":389", ico_nic);
    if($OpenLDAPEnableSSL==0){
        $tpl->table_form_field_bool("{use_ssl}",0,ico_ssl);
    }else{
        $tpl->table_form_field_text("{use_ssl}", "{listen_port} 636", ico_ssl);
        $tpl->table_form_field_text("{certificate}", $OpenLDAPCertificate, ico_certificate);
    }

    // --- Annuaire : l'arborescence, son administrateur et son decoupage
    $tpl->table_form_section("{directory}");
    $tpl->table_form_field_js("Loadjs('$page?form-service-js=yes')",$security);
    $tpl->table_form_field_text("{ldap_suffix}",$ldap->suffix,ico_database);
    $tpl->table_form_field_js("Loadjs('fw.account.php?change-manager-js=yes')",$security);
    $tpl->table_form_field_text("{ldap_admin}", $ldap->ldap_admin, ico_admin);
    $tpl->table_form_field_js("Loadjs('$page?form-service2-js=yes')",$security);
    $tpl->table_form_field_bool("{EnableMultipleOrganizations}",$EnableMultipleOrganizations,ico_sitemap);
    $tpl->table_form_field_bool("{multidomains}",$EnableVirtualDomainsInMailBoxes,ico_earth);

    // --- Securite : qui peut interroger, qui peut modifier
    $tpl->table_form_section("{security_settings}");
    $tpl->table_form_field_js("Loadjs('$page?form-service2-js=yes')",$security);
    $tpl->table_form_field_bool("{allowanonymouslogin}",$LdapAllowAnonymous,ico_unknown);
    if($LockLdapConfig==1){
        $tpl->table_form_field_bool("{LockLdapConfig}",1,ico_lock);
    }
    if($EnableOpenLDAPRestFul==1){
        $OpenLDAPRestFulApi=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPRestFulApi"));
        $tpl->table_form_field_text("{API_KEY}",$OpenLDAPRestFulApi);
    }else{
        $tpl->table_form_field_bool("RESTFull API",0,ico_computer_down);
    }

    // --- Service : journalisation, redemarrage planifie, cache
    $tpl->table_form_section("{service_parameters}");
    $tpl->table_form_field_js("Loadjs('$page?form-service-js=yes')",$security);
    $tpl->table_form_field_text("{log_level}", $loglevel_hash[$OpenLDAPLogLevel], ico_bug);
    $tpl->table_form_field_js("Loadjs('$page?form-restart-js=yes')",$security);
    if($RestartOpenLDAP==1) {
        if ($RestartLDAPEach > 0) {
            $tpl->table_form_field_text("{restart_service_each}", $CACHE_AGES[$RestartLDAPEach], ico_clock);
        }
    }else{
        $tpl->table_form_field_bool("{restart_service_each}", 0, ico_clock);
    }
    if($OpenLDAPUseMDB==0) {
        $tpl->table_form_field_js("Loadjs('$page?form-service3-js=yes')",$security);
        $LdapDBSetCachesize=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapDBSetCachesize");
        $LdapDBSCachesize=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapDBCachesize");
        if($LdapDBSetCachesize==null){$LdapDBSetCachesize=5120000;}
        if($LdapDBSCachesize==null){$LdapDBSCachesize=1000;}
        $LdapDBSetCachesizeMo=($LdapDBSetCachesize/1024)/1000;
        $tpl->table_form_field_text("{ldap_cache_size}","$LdapDBSCachesize / $LdapDBSetCachesizeMo MB",ico_caching);
    }


    echo $tpl->table_form_compile(2);
}
function form_restart_popup(){
    $tpl = new template_admin();
    $page=CurrentPageName();
    $CACHE_AGES[0]="{never}";
    $CACHE_AGES[30]="30 {minutes}";
    $CACHE_AGES[60]="1 {hour}";
    $CACHE_AGES[120]="2 {hours}";
    $CACHE_AGES[360]="6 {hours}";
    $CACHE_AGES[720]="12 {hours}";
    $CACHE_AGES[1440]="1 {day}";
    $CACHE_AGES[2880]="2 {days}";
    $CACHE_AGES[4320]="3 {days}";
    $CACHE_AGES[10080]="1 {week}";
    $CACHE_AGES[20160]="2 {weeks}";
    $CACHE_AGES[43200]="1 {month}";

    $security="AsSystemAdministrator";
    $RestartOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RestartOpenLDAP"));
    $RestartLDAPEach=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RestartLDAPEach"));

    $form[]=$tpl->field_checkbox("RestartOpenLDAP","{restart_openldap_periodically}",$RestartOpenLDAP,true,"{restart_openldap_periodically_explain}");
    $form[]=$tpl->field_array_hash($CACHE_AGES, "RestartLDAPEach", "{restart_service_each}", $RestartLDAPEach,true,null);

    echo $tpl->form_outside("", @implode("\n", $form),null,"{apply}","dialogInstance1.close();LoadAjax('openldap-flat','$page?flat=yes');",$security);
}
function form_service_popup(){
    $LdapListenInterface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapListenInterface"));
    $OpenLDAPEnableSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPEnableSSL"));
    $OpenLDAPCertificate = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPCertificate"));
    $OpenLDAPLogLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPLogLevel"));
    if($OpenLDAPLogLevel==0){$OpenLDAPLogLevel=256;}
    $tpl = new template_admin();
    $ldap=new clladp();
    $security="AsSystemAdministrator";

    $loglevel_hash=array(
        0=>"{default}",
        256=>"{basic}",
        512=>"stats log entries sent",
        1024=>"Communications",
        16384=>"{synchronization}",
        2048=>"{debug}",
    );

    $form[] = $tpl->field_interfaces("LdapListenInterface", "nodef:{listen_interface}", $LdapListenInterface);
    $form[]=$tpl->field_array_hash($loglevel_hash, "OpenLDAPLogLevel", "nonull:{log_level}", $OpenLDAPLogLevel,true);


    $form[] = $tpl->field_checkbox("OpenLDAPEnableSSL", "{use_ssl} <small>({listen_port} 636)</small>", $OpenLDAPEnableSSL, false);
    $form[] = $tpl->field_certificate("OpenLDAPCertificate", "{certificate}", $OpenLDAPCertificate);
    $form[] = $tpl->field_text_button("suffix", "{ldap_suffix}", $ldap->suffix, true, null);
    echo $tpl->form_outside("", @implode("\n", $form),null,"{apply}",jsrestart(),$security);
}
function form_service2_popup(){
    $tpl = new template_admin();
    $security="AsSystemAdministrator";





    $LockLdapConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockLdapConfig"));
    $LdapAllowAnonymous=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapAllowAnonymous"));

    $EnableVirtualDomainsInMailBoxes=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVirtualDomainsInMailBoxes"));
    $EnableMultipleOrganizations=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMultipleOrganizations"));



    $form[]=$tpl->field_checkbox("EnableMultipleOrganizations","{EnableMultipleOrganizations}",$EnableMultipleOrganizations,false,"{EnableMultipleOrganizations_explain}");
    $form[]=$tpl->field_checkbox("EnableVirtualDomainsInMailBoxes","{multidomains}",$EnableVirtualDomainsInMailBoxes,false,"{multidomains_explain}");

    $form[]=$tpl->field_checkbox("LdapAllowAnonymous","{allowanonymouslogin}",$LdapAllowAnonymous,false,"{allowanonymouslogin_explain}");

    $form[]=$tpl->field_checkbox("LockLdapConfig","{LockLdapConfig}",$LockLdapConfig,false,"{LockLdapConfig_explain}");

    $EnableOpenLDAPRestFul=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAPRestFul"));
    if($EnableOpenLDAPRestFul==1){
        $OpenLDAPRestFulApi=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPRestFulApi"));
        $form[]=$tpl->field_text("OpenLDAPRestFulApi", "{API_KEY}", $OpenLDAPRestFulApi);
    }

    echo $tpl->form_outside("", @implode("\n", $form),null,"{apply}",jsrestart(),$security);
}
function form_service3_popup(){
    $tpl = new template_admin();
    $security="AsSystemAdministrator";

    $LdapDBSetCachesize=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapDBSetCachesize");
    $LdapDBSCachesize=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapDBCachesize");
    if($LdapDBSetCachesize==null){$LdapDBSetCachesize=5120000;}
    if($LdapDBSCachesize==null){$LdapDBSCachesize=1000;}
    $LdapDBSetCachesizeMo=($LdapDBSetCachesize/1024)/1000;
    $form[] = $tpl->field_numeric("set_cachesize", "{set_cachesize} (MB)", $LdapDBSetCachesizeMo);
    $form[] = $tpl->field_numeric("LdapDBCachesize", "{ldap_cache_size}", $LdapDBSCachesize);
    echo $tpl->form_outside("", @implode("\n", $form),null,"{apply}",jsrestart(),$security);
}

function jsrestart():string{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $jsrestart = $tpl->framework_buildjs(
        "/openldap/restart",
        "openldap.progress",
        "openldap.log",
        "progress-openldap-restart",
        ""
    );
    return "dialogInstance1.close();LoadAjax('openldap-flat','$page?flat=yes');$jsrestart;";
}
function save():bool{
	$tpl=new template_admin();
	$tpl->CLEAN_POST();	
	unset($_POST["suffix"]);
	$tpl->SAVE_POSTs();
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
    return admin_tracks_post("Saving OpenLDAP parameters");
}

function status(){

	$tpl=new template_admin();
	$page=CurrentPageName();

    $data =  $GLOBALS["CLASS_SOCKETS"]->REST_API("/openldap/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error().
            "<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return true;
    }

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);

    $jsrestart=$tpl->framework_buildjs(
        "/openldap/restart",
        "openldap.progress",
        "openldap.log",
        "progress-openldap-restart",
        "LoadAjax('openldap-parameters','$page?table2=yes');"
    );
	
	echo $tpl->SERVICE_STATUS($ini, "APP_OPENLDAP",$jsrestart);

    $html[]="<div class=\"col-lg-13\">";

    $EnableLDAPSyncProv=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProv"));
    $EnableLDAPSyncProvClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProvClient"));
    $SyncProvUser=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyncProvUserDN");
    $LDAPSyncProvClientServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientServer");
    $LDAPSyncProvClientSearchBase=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientSearchBase");
    $LDAPSyncProvClientBindDN=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientBindDN");
    $LDAPSyncProvClientBindPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientBindPassword");
    $EnableLDAPSyncProvClient=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProvClient");





    if($EnableLDAPSyncProv==1){
        $title_add="{master_mode}";
        if($EnableLDAPSyncProvClient==1){
            $title_add="{client_mode}";
            $fp = fsockopen($LDAPSyncProvClientServer,389,$errno,$errstr,2);
            if(!$fp) {
                $title_add=null;
                $html[]=$tpl->widget_style1("red-bg","fad fa-unlink","{connection_error}","$errno $errstr");
            }
        }
        if($title_add<>null){
            $html[]=$tpl->widget_style1("navy-bg","fad fa-exchange","{cluster}","$title_add");
        }
    }

	
	$EnableOpenLDAPRestFul=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAPRestFul"));
	

	
	if($EnableOpenLDAPRestFul==0){
		$html[]=$tpl->widget_style1("gray-bg","fa fa-thumbs-down","RESTful API","{disabled}");
	}else{
		$html[]=$tpl->widget_style1("navy-bg","fa fa-thumbs-up","RESTful API","{active2}");
	}
	
	$html[]="</div>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
