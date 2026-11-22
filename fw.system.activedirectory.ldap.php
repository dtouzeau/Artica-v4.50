<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__) . "/ressources/class.ActiveDirectory.inc");
include_once(dirname(__FILE__)."/ressources/class.ActiveDirectoryRootDSE.inc");
include_once(dirname(__FILE__)."/ressources/class.activedirectory.license.inc");
include_once(dirname(__FILE__)."/ressources/class.activedirectory.meta.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_POST["connection"])){connection_save();exit;}
if(isset($_GET["connection-js"])){connection_js();exit;}
if(isset($_GET["connection-password-js"])){connection_password_js();exit;}
if(isset($_GET["connection-password-popup"])){connection_password_popup();exit;}
if(isset($_GET["connection-step2-js"])){connection_step2_js();exit;}
if(isset($_GET["connection-step2-popup"])){connection_step2_popup();exit;}
if(isset($_POST["connection-password"])){connection_password_save();exit;}
if(isset($_GET["connection-popup"])){connection_popup();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["remove"])){remove_js();exit;}
if(isset($_GET["bySpopup"])){bySpopup();exit;}
if(isset($_GET["simulate-js"])){simulate_js();exit;}
if(isset($_GET["simulate-popup"])){simulate_popup();exit;}
if(isset($_POST["simusername"])){simulate_save();exit;}
if(isset($_GET["simulate-results"])){simulate_results();exit;}
if(isset($_GET["simulate-perform"])){simulate_results();exit;}
if(isset($_GET["uninstall-js"])){uninstall_js();exit;}
if(isset($_GET["export-js"])){export_js();exit;}
if(isset($_GET["export-popup"])){export_popup();exit;}
if(isset($_POST["export"])){export();exit;}
if(isset($_GET["ADUserCanConnect"])){ADUserCanConnect_save();exit;}

page();


function bySpopup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog("{active_directory_ldap_connections}","$page",1024);
}
function simulate_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{simulate_authentication}","$page?simulate-popup=yes",850);
}
function uninstall_js():bool{
    if(ad_meta_refuse_js()){return false;}
    header("content-type: application/x-javascript");
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/ActiveDirectoryFeature.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/ActiveDirectoryFeature.log";
    $ARRAY["CMD"]="/activedirectory/uninstall";
    $ARRAY["TITLE"]="{uninstall} Active Directory";
    $ARRAY["AFTER"]="document.location.href='/index'";
    $prgress=base64_encode(serialize($ARRAY));
    $jsRestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-adldap-restart')";
    echo $jsRestart;
    return true;
}

function simulate_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<div id='simulate-results'></div>";
    $form[]=$tpl->field_text("simusername","{username}",$_SESSION["SIMULATE-USERNAME"],true);
    $form[]=$tpl->field_password("simpassword","{password}",$_SESSION["SIMULATE-PASSWORD"],true);
    $html[]=$tpl->form_outside("{simulate_authentication}",$form,"{simulate_authentication_explain}","{apply}","LoadAjax('simulate-results','$page?simulate-perform=yes')");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function simulate_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_SESSION["SIMULATE-USERNAME"]=$_POST["simusername"];
    $_SESSION["SIMULATE-PASSWORD"]=$_POST["simpassword"];
}

function export_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{import_export}","$page?export-popup=yes");
    return true;
}
function export_popup():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $ActiveDirectoryConnections=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections");
    $KerbAuthInfos=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos");
    $FULL["ActiveDirectoryConnections"]=$ActiveDirectoryConnections;
    $FULL["KerbAuthInfos"]=$KerbAuthInfos;
    $data=base64_encode(serialize($FULL));
    $jsrestart="LoadAjax('table-ldap-connect','$page?popup=yes');";
    $form[]=$tpl->field_textarea("export", "{connections}", $data,"664px");
    echo $tpl->form_outside("{import_export}", @implode("\n", $form),null,"{apply}",$jsrestart);
    return true;
}

function export():bool{
    $tpl        = new template_admin();
    $meta=ad_meta_refuse();
    if($meta!==""){echo $meta;return false;}
    $tpl->CLEAN_POST();
    $data=unserializeb64($_POST["export"]);
    if(!isset($data["KerbAuthInfos"])){
        $tpl->post_error("{corrupted_parameters}");
        return false;
    }
    if(!isset($data["ActiveDirectoryConnections"])){
        $tpl->post_error("{corrupted_parameters}");
        return false;
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryConnections",$data["ActiveDirectoryConnections"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ldapconns/reset");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthInfos",$data["KerbAuthInfos"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
    return true;
}

function ifRights($users){

    // A group that only carries "Artica administrator" opens a real session:
    // usersMenus() grants every right from it. _TranslateRights() alone does
    // not, so without this test the simulation refused an account the login accepts.
    if($users->AsArticaAdministrator){
        $GLOBALS["Ifrights"][]="As Artica Administrator = <strong>True</strong>";
        return true;
    }
    $GLOBALS["Ifrights"][]="As Artica Administrator = False";

    if($users->AsSystemAdministrator){
        $GLOBALS["Ifrights"][]="As System Administrator = <strong>True</strong>";
        return true;
    }
    $GLOBALS["Ifrights"][]="As System Administrator = False";

    if($users->AsFirewallManager){
        $GLOBALS["Ifrights"][]="As firewall manager = <strong>True</strong>";
        return true;}

    $GLOBALS["Ifrights"][]="As firewall manager = False";
    if($users->AsVPNManager){
        $GLOBALS["Ifrights"][]="As VPN Manager = <strong>True</strong>";
        return true;}
    $GLOBALS["Ifrights"][]="As VPN manager = False";

    if($users->AsSquidAdministrator){
        $GLOBALS["Ifrights"][]="As Proxy Manager = <strong>True</strong>";
        return true;

    }
    $GLOBALS["Ifrights"][]="As Proxy Manager = False";

    if($users->AsProxyMonitor){
        $GLOBALS["Ifrights"][]="As Proxy Monitor = <strong>True</strong>";
        return true;}
    $GLOBALS["Ifrights"][]="As Proxy Monitor = False";
    if($users->AsMessagingOrg ){
        $GLOBALS["Ifrights"][]="As Messaging manager = <strong>True</strong>";
        return true;}
    $GLOBALS["Ifrights"][]="As Messaging manager = False";
    if($users->AsPostfixAdministrator ){
        $GLOBALS["Ifrights"][]="As SMTP manager = <strong>True</strong>";
        return true;
    }


    if($users->AsDnsAdministrator ){
        $GLOBALS["Ifrights"][]="As DNS manager = <strong>True</strong>";
        return true;
    }

    $GLOBALS["Ifrights"][]="As DNS manager = <strong>False</strong>";

    if($users->AsDnsMonitor ){
        $GLOBALS["Ifrights"][]="As DNS Monitor = <strong>True</strong>";
        return true;
    }
    $GLOBALS["Ifrights"][]="As DNS Administrator = False";
    $GLOBALS["Ifrights"][]="<strong>No privileges!!</strong>";
    return false;
}

function simulate_results(){
    include_once(dirname(__FILE__).'/ressources/class.user.inc');
    include_once(dirname(__FILE__).'/ressources/class.privileges.inc');
    include_once(dirname(__FILE__).'/ressources/class.artica-logon.inc');
    $tpl=new template_admin();
    $login=new artica_logon();
    $login->Simulate=true;
    $username=$_SESSION["SIMULATE-USERNAME"];
    $MAINstyle="style='width:100%;margin-bottom: 15px;height:450px;overflow:auto'";
    if(!$login->CheckCreds($_SESSION["SIMULATE-USERNAME"],$_SESSION["SIMULATE-PASSWORD"])) {
        $html[] = "<div $MAINstyle class='center'>" . $tpl->widget_rouge($_SESSION["SIMULATE-USERNAME"], "{failed}");

        foreach ($login->VERBOSE as $line) {
            $HR = false;
            $style = null;
            if (strpos("   $line", "-HR-") > 0) {
                $line = str_replace("-HR-", "", $line);
                $HR = true;
            }
            if ($HR) {
                $style = "style='border-top:2px solid #CCCCCC;width:100%;margin-top:8px'";
            }
            if (strlen($line) > 0) {
                $line = "<code>$line</code>";
            }
            $html[] = "<div $style>$line</div>\n";

        }

        $html[]="</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    if(is_numeric($login->ACTIVE_DIRECTORY_INDEX)){
        $username="AD:$login->ACTIVE_DIRECTORY_INDEX:$login->ACTIVE_DIRECTORY_DN:user=$login->username";

    }

    $privs=new privileges($username,null,0,true);
    $privileges_array=$privs->privs;
    if(!is_array($privileges_array)){$privileges_array=array();}
    $GLOBALS["Ifrights"][]="privileges_array == ".count($privs->privs);
    $users = new usersMenus();
    if(count($privileges_array)>0) {
        $users->_TranslateRights($privileges_array, true);
    }

    if(!ifRights($users)){
        $html[] = "<div $MAINstyle class='center'>" . $tpl->widget_rouge($_SESSION["SIMULATE-USERNAME"], "{iar}");

    }else{
        $html[]="<div $MAINstyle class='center'>".$tpl->widget_vert($_SESSION["SIMULATE-USERNAME"],"{success}");
    }

    foreach ($login->VERBOSE as $line) {
        $HR = false;
        $style = null;
        if (strpos("   $line", "-HR-") > 0) {
            $line = str_replace("-HR-", "", $line);
            $HR = true;
        }
        if ($HR) {
            $style = "style='border-top:2px solid #CCCCCC;width:100%;margin-top:8px'";
        }
        if (strlen($line) > 0) {
            $line = "<code>$line</code>";
        }
        $html[] = "<div $style>$line</div>\n";

    }

    foreach ($privs->VERBOSE as $line) {
        $HR = false;
        $style = null;
        if (strpos("   $line", "-HR-") > 0) {
            $line = str_replace("-HR-", "", $line);
            $HR = true;
        }
        if ($HR) {
            $style = "style='border-top:2px solid #CCCCCC;width:100%;margin-top:8px'";
        }
        if (strlen($line) > 0) {
            $line = "<code>$line</code>";
        }
        $html[] = "<div $style>$line</div>\n";

    }

    foreach ($GLOBALS["Ifrights"] as $line) {
        $HR = false;
        $style = null;
        if (strpos("   $line", "-HR-") > 0) {
            $line = str_replace("-HR-", "", $line);
            $HR = true;
        }
        if ($HR) {
            $style = "style='border-top:2px solid #CCCCCC;width:100%;margin-top:8px'";
        }
        if (strlen($line) > 0) {
            $line = "<code>$line</code>";
        }
        $html[] = "<div $style>$line</div>\n";

    }

    $html[]="</div>";echo $tpl->_ENGINE_parse_body($html);
    return true;


}


function page(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();

    $html= $tpl->page_header("{adl_title}",
        "fab fa-windows","{adl_explain}",
        "$page?popup=yes","ad-ldap","progress-adldap-restart",false,"active_directory_ldap_connections");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{adl_title}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);


}

function ADUserCanConnect_save():bool{
    // Ouvre la console a des comptes AD : droit verifie cote serveur (l'interrupteur
    // est cadenasse sans droit, mais la route repondait a toute session).
    $tplp=new template_admin();
    if(!$tplp->IsSecurity("AsSystemAdministrator")){header("content-type: application/x-javascript");return $tplp->js_error("{ERROR_NO_PRIVS}");}
    $ID=intval($_GET["ADUserCanConnect"]);
    // Ce jeton est ecrit dans l'identifiant DOM interroge par le JavaScript
    // renvoye: il partait brut. C'est toujours un md5.
    $md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
    CheckHaCluster();
    VERBOSE("Check $ID",__LINE__);
    if($ID==0) {
        $array = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
        if(!is_array($array)){$array=array();}

        $ADUserCanConnect = intval($array["ADUserCanConnect"] ?? 0);
        if ($ADUserCanConnect == 1) {
            $array["ADUserCanConnect"] = 0;
        } else {
            $array["ADUserCanConnect"] = 1;
        }
        $KerbAuthInfos = base64_encode(serialize($array));
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthInfos", $KerbAuthInfos);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");

        header("content-type: application/x-javascript");
        $html = base64_encode(td_UserADCanConnect($array, $ID, $md));
        $Id = "ADUserCanConnect$md";
        echo "if ( !document.getElementById('$Id') ){alert('$Id not found');}\n";

        echo "if (document.getElementById('$Id') ){\n";
        echo "document.getElementById('$Id').innerHTML=base64_decode('$html')\n";
        echo "}";
        return admin_tracks("Save User Can Connect AD={$array["ADUserCanConnect"]} on Default Active Directory connection");

    }

    if(ad_meta_refuse_js()){return false;}
    $RealKey=$ID-1;
    $connexion=$RealKey;
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));

    if(!is_array($ActiveDirectoryConnections)){
        $ActiveDirectoryConnections[0]=DefaultConnection();
    }

    foreach ($ActiveDirectoryConnections as $index => $Farray) {
        // "connection" est facultative: elle etait lue sans garde.
        if(!is_array($Farray) || !isset($Farray["connection"])){continue;}
        $Cconnection=$Farray["connection"];
        VERBOSE("$Cconnection($index) == $ID ?",__LINE__);
        if ($Cconnection == $ID) {
            VERBOSE("Found index $index",__LINE__);
            $RealKey=$index;
        }
    }


    if(!isset($ActiveDirectoryConnections[$RealKey]) || !is_array($ActiveDirectoryConnections[$RealKey])){
        header("content-type: application/x-javascript");
        echo "//connection $RealKey not found";
        return false;
    }
    $array=$ActiveDirectoryConnections[$RealKey];
    $ADUserCanConnect=intval($array["ADUserCanConnect"] ?? 0);
    if($ADUserCanConnect==1){
        $array["ADUserCanConnect"]=0;
    }else{
        $array["ADUserCanConnect"]=1;
    }
    if (isset($array["connection"])) {
        $connexion = intval($array["connection"]);
    }

    $array["connection"]=$connexion;
    $ActiveDirectoryConnections[$RealKey]=$array;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryConnections",serialize($ActiveDirectoryConnections));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ldapconns/reset");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");

    header("content-type: application/x-javascript");
    $html=base64_encode(td_UserADCanConnect($array,$connexion,$md));
    $Id="ADUserCanConnect$md";
    echo "if (document.getElementById('$Id') ){\n";
    echo "document.getElementById('$Id').innerHTML=base64_decode('$html')\n";
    echo "}";

    return admin_tracks("Save User Can Connect AD={$array["ADUserCanConnect"]} on Active Directory connection #$RealKey");
}

function connection_password_popup():bool{
    if(intval($_GET["connection-password-popup"])!=0 && ad_meta_managed()){echo ad_meta_banner();return false;}
    $page=CurrentPageName();
    $ID=intval($_GET["connection-password-popup"]);
    $tpl=new template_admin();
    $btname="{apply}";
    $jsafter=null;
    $title="";
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));

    if($ID==0){
        $array = $ActiveDirectoryConnections[$ID] ?? DefaultConnection();
        $jsafter="LoadAjaxSilent('active_directory_ldap_connections','$page?table=yes');";

    }
    if($ID==9999999999999){
        $tpl=new template_admin();
        echo $tpl->div_error("$ID ??");
        return false;
    }

    if($ID<9999999999999){
        if($ID>0){
            $array=$ActiveDirectoryConnections[$ID];
            $jsafter="LoadAjaxSilent('active_directory_ldap_connections','$page?table=yes');";
        }
    }

    if(!is_array($array)){$array=array();}
    if(!isset($array["LDAP_DN"])){$array["LDAP_DN"]="";}
    if(!isset($array["LDAP_PASSWORD"])){$array["LDAP_PASSWORD"]="";}
    $hasPassword=(strlen((string)$array["LDAP_PASSWORD"])>0);

    // Le mot de passe enregistre etait rendu EN CLAIR dans l'attribut value du
    // champ: il suffisait d'afficher la source de la page pour le lire. Le
    // champ part desormais vide; le laisser vide conserve le mot de passe en
    // place (voir connection_password_save()).
    $form[]=$tpl->field_hidden("connection-password",$ID);
    $form[]=$tpl->field_email("LDAP_DN", "{username}","{$array["LDAP_DN"]}",true);
    // 5e argument de field_password2() = explication affichee sous le champ.
    $form[]=$tpl->field_password2("LDAP_PASSWORD", "{password}", "", !$hasPassword,
        $hasPassword?"{password_leave_blank_to_keep}":"{must_type_password_for_connection}");

    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $ClusterNotReplicateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterNotReplicateAD"));
    $LOCK=false;
    if($PowerDNSEnableClusterSlave==1){
        $LOCK=true;
    }
    if($ClusterNotReplicateAD==1){
        $LOCK=false;
    }
    echo $tpl->form_outside("", @implode("\n", $form),null,$btname,"dialogInstance5.close();$jsafter","AsSystemAdministrator",true,$LOCK);
    return true;

}

function connection_password_js():bool{
    if(intval($_GET["connection-password-js"])!=0 && ad_meta_refuse_js()){return false;}
    $page=CurrentPageName();
    $users=new usersMenus();
    $tpl=new template_admin();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return false;}
    $ID=intval($_GET["connection-password-js"]);
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    if($ID==0){
        $array = $ActiveDirectoryConnections[$ID] ?? DefaultConnection();
        $title="{default}: {$array["LDAP_SERVER"]} / {$array["LDAP_DN"]}";
        return $tpl->js_dialog5($title, "$page?connection-password-popup=$ID");
    }

    if($ID==9999999999999){
        // js_dialog5 comme les autres branches de cette fonction, et NON js_dialog2:
        // le formulaire de connexion qui contient ce bouton est lui-meme ouvert en
        // js_dialog2 (connection_js()). Reutiliser le meme niveau d'empilement
        // remplacait la boite parente au lieu de s'ouvrir par-dessus -- le
        // formulaire entier disparaissait au clic. Le formulaire rendu par
        // connection_password_popup() se ferme d'ailleurs sur dialogInstance5,
        // donc 2 etait doublement faux.
        $title=$tpl->_ENGINE_parse_body("{new_connection}");
        return $tpl->js_dialog5($title, "$page?connection-password-popup=$ID");
    }
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    $array=$ActiveDirectoryConnections[$ID];
    $title="{$array["LDAP_SERVER"]} / {$array["LDAP_DN"]}";
    return $tpl->js_dialog5($title, "$page?connection-password-popup=$ID");
}

/**
 * Etape 2 de la creation d'une connexion: identifiants et suffixe.
 *
 * L'identifiant vient de la session, pose par connection_save() -- voir le
 * commentaire la-bas sur l'impossibilite de le renvoyer dans le corps de la
 * reponse.
 */
function connection_step2_js():bool{
    if(ad_meta_refuse_js()){return false;}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return false;}

    $ID=intval(isset($_SESSION["ADLDAP_NEW_ID"])?$_SESSION["ADLDAP_NEW_ID"]:-1);
    // Consomme une seule fois: l'identifiant est ensuite porte par l'URL de la
    // boite. Le laisser en session permettrait de rouvrir l'etape 2 plus tard
    // sur un index devenu faux -- adldap_reindex() renumerote a chaque
    // suppression.
    unset($_SESSION["ADLDAP_NEW_ID"]);
    if($ID<0){
        // Rien a enchainer: la creation n'a pas abouti. On ne montre pas une
        // boite vide.
        return $tpl->js_error("{new_connection}");
    }
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    if(!is_array($ActiveDirectoryConnections)){$ActiveDirectoryConnections=array();}
    $srv=(string)(isset($ActiveDirectoryConnections[$ID]["LDAP_SERVER"])?$ActiveDirectoryConnections[$ID]["LDAP_SERVER"]:"");
    $title=trim("[$ID]: $srv");

    // js_dialog6: distinct de l'etape 1 (js_dialog2, qui vient de se fermer) ET
    // du parcours de suffixe (js_dialog5) que cette boite ouvre elle-meme.
    // Reutiliser un niveau deja pris remplace la boite au lieu de l'empiler.
    return $tpl->js_dialog6($title,"$page?connection-step2-popup=$ID",650);
}

function connection_step2_popup():bool{
    if(ad_meta_managed()){echo ad_meta_banner();return false;}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");return false;}

    $ID=intval($_GET["connection-step2-popup"]);
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    if(!is_array($ActiveDirectoryConnections)){$ActiveDirectoryConnections=array();}
    $array=(isset($ActiveDirectoryConnections[$ID]) && is_array($ActiveDirectoryConnections[$ID]))
        ?$ActiveDirectoryConnections[$ID]
        :array();

    // UN SEUL $tpl pour tout le formulaire. fw.browse-suffix.php retrouve les
    // champs par md5("LDAP_DN".$suffixid) et lit ainsi les identifiants SAISIS,
    // avant meme leur enregistrement -- c'est ce qui rend le parcours utilisable
    // des cette etape. Deux instances = deux suffixid = parcours aveugle.
    //
    // Le formulaire est traite par connection_password_save(), qui fusionne
    // toutes les cles postees dans l'entree: LDAP_SUFFIX est donc enregistre au
    // meme titre que le compte.
    $form[]=$tpl->field_hidden("connection-password",$ID);
    $form[]=$tpl->field_section("{credentials}");
    $form[]=$tpl->field_email("LDAP_DN","{username}",(string)(isset($array["LDAP_DN"])?$array["LDAP_DN"]:""),true);
    $form[]=$tpl->field_password2("LDAP_PASSWORD","{password}","",true,"{must_type_password_for_connection}");
    $form[]=$tpl->field_section("{suffix}");
    $form[]=$tpl->field_ad_suffix_ad_conns("LDAP_SUFFIX",$ID,"{ldap_suffix}",
        (string)(isset($array["LDAP_SUFFIX"])?$array["LDAP_SUFFIX"]:""));

    echo $tpl->form_outside("",@implode("\n",$form),null,"{apply}",
        "dialogInstance6.close();LoadAjaxSilent('active_directory_ldap_connections','$page?table=yes');",
        "AsSystemAdministrator",true);
    return true;
}

function connection_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["connection-js"]);
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));

    if($ID==0){
        $array = $ActiveDirectoryConnections[$ID] ?? DefaultConnection();
        $title="{default}: {$array["LDAP_SERVER"]} / {$array["LDAP_DN"]}";
        $tpl->js_dialog2($title, "$page?connection-popup=$ID");
        return;

    }
    if($ID==9999999999999){
        if(ad_meta_refuse_js()){return;}
        $title=$tpl->_ENGINE_parse_body("{new_connection}");
        $tpl->js_dialog2($title, "$page?connection-popup=$ID");
        return;
    }
    //
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    $array=$ActiveDirectoryConnections[$ID];
    $title="{$array["LDAP_SERVER"]} / {$array["LDAP_DN"]}";
    $tpl->js_dialog2($title, "$page?connection-popup=$ID");


}
/**
 * Reindexe la liste des connexions apres une suppression.
 *
 * C'EST LE CORRECTIF DECISIF. unset() laisse un TROU dans les cles, et le
 * tableau re-serialise cesse alors d'etre une liste PHP. Cote daemon,
 * gophp.Unserialize() rend un []interface{} pour des cles 0..n-1 contigues mais
 * un map[string]interface{} des qu'il manque un index -- et
 * LdapConnections.Connections() ne teste QUE le premier type. Le reglage
 * restait donc syntaxiquement valide (la console continuait de l'afficher) mais
 * le daemon n'y voyait plus AUCUNE connexion: reconstruction AD du cluster,
 * KVNO et les trois autres appelants s'arretaient en silence.
 *
 * Supprimer la derniere entree ne cassait rien; supprimer n'importe quelle
 * autre cassait tout -- d'ou le caractere intermittent du symptome.
 *
 * La cle "connection" est realignee sur le nouvel index pour rester coherente
 * avec ce que le tableau et les gestionnaires de bascule utilisent.
 */
function adldap_reindex($ActiveDirectoryConnections):array{
    if(!is_array($ActiveDirectoryConnections)){return array();}
    $out=array();
    foreach($ActiveDirectoryConnections as $index=>$ligne){
        if(!is_numeric($index)){continue;}
        if(!is_array($ligne)){continue;}
        $out[]=$ligne;
    }
    foreach($out as $newIndex=>$ligne){
        $out[$newIndex]["connection"]=$newIndex;
    }
    return $out;
}

/** Enregistre la liste des connexions, toujours reindexee. */
function adldap_save_connections($ActiveDirectoryConnections):array{
    $ActiveDirectoryConnections=adldap_reindex($ActiveDirectoryConnections);
    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(serialize($ActiveDirectoryConnections),"ActiveDirectoryConnections");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ldapconns/reset");
    return $ActiveDirectoryConnections;
}

/**
 * Connexion par defaut (index 0 = jonction locale, KerbAuthInfos) : sa copie en tete de
 * ActiveDirectoryConnections n'est reecrite que hors verrou Meta. Sous verrou, la liste
 * appartient a la politique Meta (le demon refuse son ecriture, 423) et l'import Meta garde
 * cette ligne en tete : seule KerbAuthInfos (deja enregistree par l'appelant) change.
 */
function adldap_save_default_row($ActiveDirectoryConnections,$array):void{
    if(function_exists("ad_meta_managed") && ad_meta_managed()){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ldapconns/reset");
        return;
    }
    $ActiveDirectoryConnections[0]=$array;
    adldap_save_connections($ActiveDirectoryConnections);
}

/** Vrai si la ligne porte l'identite (serveur|suffixe) de la jonction locale (KerbAuthInfos). */
function adldap_is_join_row($ligne):bool{
    if(!is_array($ligne)){return false;}
    $k=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($k)){return false;}
    $ks=trim(isset($k["LDAP_SERVER"])?(string)$k["LDAP_SERVER"]:"");
    if($ks==""){$ks=trim(isset($k["fullhosname"])?(string)$k["fullhosname"]:"");}
    if($ks==""){return false;}
    $ls=trim(isset($ligne["LDAP_SERVER"])?(string)$ligne["LDAP_SERVER"]:"");
    if($ls==""){$ls=trim(isset($ligne["fullhosname"])?(string)$ligne["fullhosname"]:"");}
    $kx=trim(isset($k["LDAP_SUFFIX"])?(string)$k["LDAP_SUFFIX"]:"");
    $lx=trim(isset($ligne["LDAP_SUFFIX"])?(string)$ligne["LDAP_SUFFIX"]:"");
    return strtolower($ks)==strtolower($ls) && strtolower($kx)==strtolower($lx);
}

function remove_js():bool{
    if(ad_meta_refuse_js()){return false;}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["remove"]);
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    // unserialize() rend false quand le reglage est vide: le foreach qui suivait
    // etait alors FATAL sous PHP 8.
    if(!is_array($ActiveDirectoryConnections)){$ActiveDirectoryConnections=array();}

    // La suppression se fait UNIQUEMENT par index de tableau.
    //
    // Elle cherchait auparavant d'abord une entree dont le champ "connection"
    // egalait $_GET["connection"], et supprimait la PREMIERE trouvee. Or ce
    // champ n'est pas unique: connection_popup() rend
    // field_info("connection", …, $ID), field_info() POSTE sa valeur, et $ID
    // vaut 9999999999999 a la creation -- valeur stockee telle quelle. Toutes
    // les connexions creees portaient donc le meme "connection", et supprimer
    // la troisieme supprimait la deuxieme.
    if(!isset($ActiveDirectoryConnections[$ID]) || !is_array($ActiveDirectoryConnections[$ID])){
        header("content-type: application/x-javascript");
        echo $tpl->js_error("{connection} $ID: {not_found}");
        return false;
    }

    $ligne=$ActiveDirectoryConnections[$ID];
    $server=(string)($ligne["LDAP_SERVER"] ?? "");
    unset($ActiveDirectoryConnections[$ID]);
    adldap_save_connections($ActiveDirectoryConnections);

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
    if($Enablehacluster==1) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
    }
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');\n";
    echo "LoadAjaxSilent('active_directory_ldap_connections','$page?table=yes');\n";
    admin_tracks("Remove Active Directory connection #$ID ($server)");
    return true;
}
function popup():bool{
    $page=CurrentPageName();
    $html="<div id='active_directory_ldap_connections'></div><script>LoadAjaxSilent('active_directory_ldap_connections','$page?table=yes');</script>";
    echo $html;
    return true;
}

function CheckHaCluster():bool{
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    if($Enablehacluster==0){return  false;}
    if(ad_meta_managed()){return false;}
    $haClusterAD=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
    $KerberosUsername=$haClusterAD["KerberosUsername"];
    $KerberosPassword=$haClusterAD["KerberosPassword"];
    $kerberosActiveDirectoryHost=$haClusterAD["kerberosActiveDirectoryHost"];
    $kerberosActiveDirectory2Host=$haClusterAD["kerberosActiveDirectory2Host"];
    $kerberosActiveDirectorySuffix=trim($haClusterAD["kerberosActiveDirectorySuffix"]);


    $array["LDAP_DN"]=$KerberosUsername;
    $array["LDAP_SUFFIX"]=$kerberosActiveDirectorySuffix;
    $array["LDAP_SERVER"]=$kerberosActiveDirectoryHost;
    $array["LDAP_SERVER2"]=$kerberosActiveDirectory2Host;
    $array["LDAP_PASSWORD"]=$KerberosPassword;


    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));

    if(!isset($ActiveDirectoryConnections[0]["LDAP_SERVER"])){
        $ActiveDirectoryConnections[0]=$array;
        $datas=serialize($ActiveDirectoryConnections);
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile($datas, "ActiveDirectoryConnections");
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/ldapconns/reset");
    }
    return true;
}

function td_UserADCanConnect($array=array(),$index=0,$md5=null):string{
    // Acces des comptes AD a la console : un vrai interrupteur (role=switch). Avant,
    // un badge rouge "deny" cliquable, qui faisait passer l'etat normal pour une alerte.
    $page=CurrentPageName();
    $tpl=new template_admin();
    $index=intval($index);
    $md5=preg_replace('/[^a-f0-9]/','',(string)$md5);
    $on=intval(isset($array["ADUserCanConnect"])?$array["ADUserCanConnect"]:0)==1;
    $locked=(($index!=0 && ad_meta_managed()) || !$tpl->IsSecurity("AsSystemAdministrator"));
    $js=$locked?"blur()":"Loadjs('$page?ADUserCanConnect=$index&md=$md5')";
    $h="<div class='adl-sw-w'><button type='button' role='switch' class='adl-sw' aria-checked='".($on?"true":"false")."'"
        .($locked?" disabled":"")." aria-label=\"{adl_col_console}\" OnClick=\"$js\"><span class='adl-sw-tr' aria-hidden='true'></span>"
        ."<span>".($on?"{adl_allowed}":"{adl_not_allowed}")."</span></button>"
        ."<span class='adl-sw-sub'>".($on?"{adl_allowed_sub}":"{adl_denied_sub}")."</span></div>";
    return $tpl->_ENGINE_parse_body($h);
}

// ---------------------------------------------------------------------------
// Connexions LDAP Active Directory (maquette stitch_active_directory_ldap_redesign,
// 2026-11-11). Un modele (adl_model) teste chaque connexion UNE fois ; une
// fonction par widget. Les usages decrits dans les cartes sont ceux du demon :
// authentification silencieuse du proxy (squid/silent_auth*), ACL (squidacls),
// filtrage web (ufdbguardd/UsersList), recherche (ConsoleSearch), console (ADUserCanConnect).
// ---------------------------------------------------------------------------
function adl_h($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,"UTF-8");}

// Etat d'une connexion : ok | fail | nosuffix | mismatch, + message et suffixe lu.
function adl_test(array &$ligne,bool $isKerberos,&$ActiveDirectoryConnections=null,$index=null):array{
    $r=array("status"=>"fail","error"=>"","rootdse"=>"");
    // Le message d'echec est GLOBAL : sans remise a zero, une connexion heritait
    // de l'erreur de la precedente.
    unset($GLOBALS["LDAP_CONNECTION_FAILED"]);
    $suffix=isset($ligne["LDAP_SUFFIX"])?trim((string)$ligne["LDAP_SUFFIX"]):"";
    if(strlen($suffix)<3){
        $r["status"]="nosuffix";
    }else{
        $ad=new ActiveDirectory();
        $r["status"]=$ad->test_ldap_connection($suffix)?"ok":"fail";
        if(isset($GLOBALS["LDAP_CONNECTION_FAILED"])){$r["error"]=trim(strip_tags((string)$GLOBALS["LDAP_CONNECTION_FAILED"]));}
    }
    if($isKerberos){return $r;}
    // Comme avant : sans erreur, on lit le RootDSE ; il complete un suffixe absent
    // et detecte un suffixe declare different de celui du serveur.
    if($r["error"]==""){
        $server=(string)$ligne["LDAP_SERVER"];
        if(isset($ligne["ADNETIPADDR"]) && $ligne["ADNETIPADDR"]<>""){$server=$ligne["ADNETIPADDR"];}
        $dse=new ad_rootdse($server,$ligne["LDAP_PORT"],$ligne["LDAP_DN"],$ligne["LDAP_PASSWORD"],$ligne["LDAP_SSL"]);
        $RootDSE=$dse->RootDSE();
        if(!$dse->ok){
            $r["status"]="fail";
            $r["error"]=trim(strip_tags((string)$dse->mysql_error));
        }
        if($RootDSE<>null){
            if($suffix=="" && !ad_meta_managed() && is_array($ActiveDirectoryConnections)){
                $ligne["LDAP_SUFFIX"]=$RootDSE; $suffix=$RootDSE;
                $ActiveDirectoryConnections[$index]=$ligne;
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryConnections",serialize($ActiveDirectoryConnections));
                $GLOBALS["CLASS_SOCKETS"]->REST_API("/ldapconns/reset");
                $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
            }
            if(strtolower($suffix)<>strtolower(trim((string)$RootDSE))){
                $r["status"]="mismatch";
                $r["rootdse"]=(string)$RootDSE;
            }
        }
    }
    return $r;
}

function adl_model():array{
    $page=CurrentPageName();
    $active=new ActiveDirectory();
    CheckHaCluster();
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $EnableKerbAuth=intval($sock->GET_INFO("EnableKerbAuth"));
    $Lock=intval($sock->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient=intval($sock->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$Lock=1;}
    if($Lock==1){$EnableKerbAuth=1;}
    $m=array("rows"=>array(),"kerberos"=>($EnableKerbAuth==1),"cluster"=>($HaClusterClient==1),
        "meta"=>ad_meta_managed(),"license"=>$sock->CORP_LICENSE(),
        "slave"=>(intval($sock->GET_INFO("PowerDNSEnableClusterSlave"))==1));

    if($EnableKerbAuth==1){
        $array=$sock->unserializeb64($sock->GET_INFO("KerbAuthInfos"));
        if(!is_array($array)){$array=array();}
        foreach(array("ADUserCanConnect"=>0,"LDAP_SERVER"=>"","fullhosname"=>"","LDAP_DN"=>"","LDAP_SUFFIX"=>"","LDAP_SSL"=>0,"LDAP_PORT"=>null) as $k=>$v){
            if(!isset($array[$k])){$array[$k]=$v;}
        }
        if($array["LDAP_SERVER"]=="" && strlen($array["fullhosname"])>3){$array["LDAP_SERVER"]=$array["fullhosname"];}
        if(!isset($array["LDAP_PASSWORD"])){$array["LDAP_PASSWORD"]=isset($array["WINDOWS_SERVER_PASS"])?$array["WINDOWS_SERVER_PASS"]:"";}
        if($array["LDAP_DN"]==null){$array["LDAP_DN"]=$active->ldap_dn_user;}
        if($array["LDAP_SUFFIX"]==null){$array["LDAP_SUFFIX"]=$active->suffix;}
        if($array["LDAP_SERVER"]==null){$array["LDAP_SERVER"]=$active->ldap_host;}
        if($array["LDAP_PORT"]==null){$array["LDAP_PORT"]=$active->ldap_port;}
        if($array["LDAP_PASSWORD"]==null){$array["LDAP_PASSWORD"]=$active->ldap_password;}
        if($array["LDAP_SSL"]==null){$array["LDAP_SSL"]=$active->ldap_ssl;}
        if(preg_match("#^(.+?)@(.+?)@$#",trim((string)$array["LDAP_DN"]),$re)){$array["LDAP_DN"]="$re[1]@$re[2]";}
        if(strlen((string)$array["LDAP_SERVER"])>3){
            $t=adl_test($array,true);
            $m["rows"][]=adl_row_model($array,0,true,$t,
                ($m["license"]?"Loadjs('$page?connection-js=0')":"blur();"),
                "Loadjs('$page?connection-password-js=0')",false);
        }
    }

    $ActiveDirectoryConnections=unserialize($sock->GET_INFO("ActiveDirectoryConnections"));
    if(!is_array($ActiveDirectoryConnections)){$ActiveDirectoryConnections=array();}
    foreach($ActiveDirectoryConnections as $index=>$ligne){
        if(!is_numeric($index) || !is_array($ligne)){continue;}
        // Ligne 0 = copie de la jonction (affichee depuis KerbAuthInfos ci-dessus). Sous verrou
        // Meta, une ligne 0 qui n'est pas la jonction est une connexion de la politique : montree.
        $metaRow0=($HaClusterClient==0 && $EnableKerbAuth==1 && $index==0 && $m["meta"] && !adldap_is_join_row($ligne));
        if($HaClusterClient==0 && $EnableKerbAuth==1 && $index==0 && !$metaRow0){continue;}
        foreach(array("LDAP_PORT"=>389,"LDAP_SSL"=>0,"ADNETIPADDR"=>"","LDAP_DN"=>"","LDAP_PASSWORD"=>"","LDAP_SUFFIX"=>null) as $k=>$v){
            if(!isset($ligne[$k])){$ligne[$k]=$v;}
        }
        if(!isset($ligne["LDAP_SERVER"]) && $ligne["ADNETIPADDR"]<>""){$ligne["LDAP_SERVER"]=$ligne["ADNETIPADDR"];}
        if(!isset($ligne["LDAP_SERVER"])){continue;}
        $t=adl_test($ligne,false,$ActiveDirectoryConnections,$index);
        $isDefault=($index==0);
        $m["rows"][]=adl_row_model($ligne,$index,$isDefault,$t,
            (($m["license"] && !$metaRow0)?"Loadjs('$page?connection-js=$index')":"blur();"),
            ($m["meta"]?"blur();":"Loadjs('$page?connection-password-js=$index')"),
            // Noeud de cluster : le bandeau annonce la lecture seule, pas de corbeille.
            (!$isDefault && !$m["meta"] && !$m["cluster"]));
    }
    $m["total"]=count($m["rows"]); $m["ok"]=0; $m["bad"]=0; $m["console"]=0;
    foreach($m["rows"] as $r){
        if($r["status"]=="ok"){$m["ok"]++;}else{$m["bad"]++;}
        if($r["console"]){$m["console"]++;}
    }
    return $m;
}

function adl_row_model(array $l,$key,bool $isDefault,array $t,string $js,string $jsPwd,bool $canDelete):array{
    $server=(string)$l["LDAP_SERVER"];
    if(isset($l["ADNETIPADDR"]) && $l["ADNETIPADDR"]<>""){$server=$l["ADNETIPADDR"];}
    $ssl=intval($l["LDAP_SSL"])==1;
    return array("key"=>intval($key),"server"=>$server,"ssl"=>$ssl,"port"=>($ssl?636:intval($l["LDAP_PORT"])),
        "dn"=>(string)$l["LDAP_DN"],"suffix"=>trim((string)$l["LDAP_SUFFIX"]),"default"=>$isDefault,
        "status"=>$t["status"],"error"=>$t["error"],"rootdse"=>$t["rootdse"],
        "console"=>intval(isset($l["ADUserCanConnect"])?$l["ADUserCanConnect"]:0)==1,"raw"=>$l,
        "js"=>$js,"jsPwd"=>$jsPwd,"canDelete"=>$canDelete,"md5"=>md5($server.$key.microtime(true).rand()));
}

function adl_hero():string{
    $steps=array(array("{adl_step1_t}","{adl_step1}"),array("{adl_step2_t}","{adl_step2}"),array("{adl_step3_t}","{adl_step3}"));
    $s="";
    foreach($steps as $i=>$st){$s.="<li><span class='adl-num'>".($i+1)."</span><span><strong>{$st[0]}</strong> &mdash; {$st[1]}</span></li>";}
    return "<section class='adl-hero'><div class='adl-hero-main'><span class='adl-hero-ico'><i class='fa-brands fa-windows' aria-hidden='true'></i></span>"
        ."<div><h2 class='adl-hero-t'>{adl_hero_title}</h2><p class='adl-hero-p'>{adl_hero_text}</p></div></div>"
        ."<div class='adl-how'><div class='adl-how-t'><i class='fa-solid fa-route' aria-hidden='true'></i>{adl_how}</div><ol>$s</ol></div></section>";
}

function adl_unlocks():string{
    $cards=array(
        array("fa-solid fa-shield-halved","adl-t1","{adl_u1_t}","{adl_u1}"),
        array("fa-solid fa-filter","adl-t2","{adl_u2_t}","{adl_u2}"),
        array("fa-solid fa-right-to-bracket","adl-t3","{adl_u3_t}","{adl_u3}"),
        array("fa-solid fa-magnifying-glass","adl-t4","{adl_u4_t}","{adl_u4}"));
    $h="<section class='adl-unlocks'>";
    foreach($cards as $c){
        $h.="<div class='adl-u'><div class='adl-u-h'><span class='adl-u-ico {$c[1]}'><i class='{$c[0]}' aria-hidden='true'></i></span><span class='adl-u-t'>{$c[2]}</span></div><p class='adl-u-p'>{$c[3]}</p></div>";
    }
    return $h."</section>";
}

function adl_health(array $m,$tpl):string{
    $verdict="";
    if($m["total"]==0){
        $verdict="<div class='adl-verdict adl-v-off'><i class='fa-solid fa-circle-info' aria-hidden='true'></i><span>{adl_v_none}</span></div>";
    }elseif($m["bad"]==0){
        $verdict="<div class='adl-verdict adl-v-ok'><i class='fa-solid fa-circle-check' aria-hidden='true'></i><span>{adl_v_ok}</span></div>";
    }else{
        $first=null;
        foreach($m["rows"] as $r){ if($r["status"]<>"ok"){$first=$r;break;} }
        $txt=$m["bad"]==1?"{adl_v_bad1}":str_replace("%s",$m["bad"],$tpl->_ENGINE_parse_body("{adl_v_badn}"));
        $sub=adl_h($first["server"]);
        if($first["error"]<>""){$sub.=": ".adl_h($first["error"]);}
        $verdict="<div class='adl-verdict adl-v-bad'><span class='adl-v-ico'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i></span>"
            ."<span><strong>$txt</strong><span class='adl-v-sub' title=\"$sub\">$sub</span></span></div>";
    }
    $of=str_replace("%s",$m["total"],$tpl->_ENGINE_parse_body("{adl_of_conns}"));
    return "<section class='adl-health'>"
        ."<div class='adl-fig'><span class='adl-fig-l'>{adl_h_conns}</span><span class='adl-fig-v'>{$m["total"]}</span></div>"
        ."<div class='adl-fig'><span class='adl-fig-l'>{adl_h_ok}</span><span class='adl-pill adl-ok'><i class='fa-solid fa-circle-check' aria-hidden='true'></i>{$m["ok"]}</span></div>"
        ."<div class='adl-fig'><span class='adl-fig-l'>{adl_h_fail}</span><span class='adl-pill ".($m["bad"]>0?"adl-err":"adl-off")."'><i class='fa-solid fa-circle-xmark' aria-hidden='true'></i>{$m["bad"]}</span></div>"
        ."<div class='adl-fig'><span class='adl-fig-l'>{adl_h_console}</span><span class='adl-fig-v'>{$m["console"]} <small>$of</small></span></div>"
        .$verdict."</section>";
}

function adl_banners(array $m,$tpl):string{
    $h=ad_meta_banner($tpl).ad_license_banner();
    $b="";
    if($m["cluster"]){
        $b.="<div class='adl-banner adl-b-off'><i class='fa-solid fa-circle-nodes' aria-hidden='true'></i><span>{adl_cluster}</span></div>";
    }elseif(!$m["license"] && !$m["meta"]){
        $b.="<div class='adl-banner adl-b-warn'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i><span>{adl_lic}</span></div>";
    }
    return $h.$tpl->_ENGINE_parse_body($b);
}

function adl_status(array $r,$tpl):string{
    switch($r["status"]){
        case "ok":
            return "<span class='adl-pill adl-ok'><i class='fa-solid fa-circle-check' aria-hidden='true'></i>{adl_st_ok}</span><div class='adl-st-sub'><i class='fa-solid fa-check' aria-hidden='true'></i>{adl_st_ok_sub}</div>";
        case "nosuffix":
            return "<span class='adl-pill adl-warn'><i class='fa-solid fa-circle-exclamation' aria-hidden='true'></i>{adl_st_nosuffix}</span>";
        case "mismatch":
            $sub=str_replace(array("%a","%b"),array(adl_h($r["suffix"]),adl_h($r["rootdse"])),$tpl->_ENGINE_parse_body("{adl_mismatch_sub}"));
            return "<span class='adl-pill adl-warn'><i class='fa-solid fa-circle-exclamation' aria-hidden='true'></i>{adl_st_mismatch}</span><div class='adl-st-sub adl-mono' title=\"".strip_tags($sub)."\">$sub</div>";
    }
    $err=$r["error"]<>""?"<div class='adl-st-sub adl-st-err adl-mono' title=\"".adl_h($r["error"])."\"><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i>".adl_h($r["error"])."</div>":"";
    return "<span class='adl-pill adl-err'><i class='fa-solid fa-circle-xmark' aria-hidden='true'></i>{adl_st_fail}</span>$err";
}

function adl_row(array $r,$tpl):string{
    $page=CurrentPageName();
    $srv=adl_h($r["server"]);
    $tags="";
    if($r["default"]){$tags.="<span class='adl-tag adl-tag-def'>{adl_default}</span>";}
    if($r["ssl"]){$tags.="<span class='adl-tag'><i class='fa-solid fa-lock' aria-hidden='true'></i>LDAPS</span>";}
    $meta=array();
    if($r["suffix"]<>""){$meta[]="{adl_suffix} <span class='adl-mono'>".adl_h($r["suffix"])."</span>";}
    $meta[]="{adl_port} <span class='adl-mono'>".$r["port"]."</span>";
    $h="<tr".($r["status"]=="ok"?"":" class='adl-row-bad'").">";
    $h.="<td><div class='adl-conn'><span class='adl-ico".($r["status"]=="fail"?" adl-ico-off":"")."'><i class='fa-brands fa-windows' aria-hidden='true'></i></span><div class='adl-conn-t'>";
    $h.="<a class='adl-srv adl-mono' href='javascript:blur();' OnClick=\"{$r["js"]}\" title='{click_to_edit}'>$srv</a>";
    if($tags<>""){$h.="<div class='adl-tags'>$tags</div>";}
    $h.="<div class='adl-meta'>".@implode(" &middot; ",$meta)."</div></div></div></td>";
    $dn=$r["dn"]<>""?"<span class='adl-acct adl-mono'>".adl_h($r["dn"])."</span>":"<span class='adl-none'>&mdash;</span>";
    $h.="<td><div class='adl-acct-w'>$dn<button type='button' class='adl-ib' aria-label=\"{adl_change_pwd}\" title=\"{adl_change_pwd}\" OnClick=\"{$r["jsPwd"]}\"><i class='fa-solid fa-key' aria-hidden='true'></i></button></div></td>";
    $h.="<td>".adl_status($r,$tpl)."</td>";
    $h.="<td><span id='ADUserCanConnect{$r["md5"]}'>".td_UserADCanConnect($r["raw"],$r["key"],$r["md5"])."</span></td>";
    $acts="<button type='button' class='adl-ib' aria-label=\"{adl_edit} $srv\" title=\"{adl_edit}\" OnClick=\"{$r["js"]}\"><i class='fa-solid fa-pen' aria-hidden='true'></i></button>";
    if($r["canDelete"]){
        $acts.="<button type='button' class='adl-ib adl-ib-del' aria-label=\"{delete} $srv\" title=\"{delete}\" OnClick=\"Loadjs('$page?remove={$r["key"]}')\"><i class='fa-solid fa-trash-can' aria-hidden='true'></i></button>";
    }elseif($r["default"]){
        $acts.="<span class='adl-protected'>{adl_protected}</span>";
    }
    $h.="<td class='adl-acts'>$acts</td></tr>";
    return $h;
}

function adl_table(array $m,$tpl):string{
    $page=CurrentPageName();
    $h="<section class='adl-card'><div class='adl-card-h'><div class='adl-card-t'>{adl_tbl_title} <span class='adl-count'>{$m["total"]}</span></div>";
    if($m["total"]>0){
        $h.="<div class='adl-legend'><span><span class='adl-dot adl-dot-ok'></span>{$m["ok"]} {adl_active}</span>";
        if($m["bad"]>0){$h.="<span><span class='adl-dot adl-dot-err'></span>{$m["bad"]} {adl_failing}</span>";}
        $h.="</div>";
    }
    $h.="</div>";
    if($m["total"]==0){
        $btn=($m["license"] && !$m["meta"] && !$m["cluster"])
            ?$tpl->button_inline("{new_connection}","Loadjs('$page?connection-js=9999999999999')","fa-solid fa-plus","AllowAddGroup",0,"abt-p"):"";
        return $h."<div class='adl-empty'><i class='fa-brands fa-windows' aria-hidden='true'></i><div class='adl-empty-t'>{adl_empty_t}</div><div class='adl-empty-p'>{adl_empty}</div>$btn</div></section>";
    }
    $h.="<div class='adl-scroll'><table class='adl-tbl'><thead><tr><th>{adl_col_conn}</th><th>{adl_col_account}</th><th>{adl_col_status}</th>"
        ."<th>{adl_col_console} <i class='fa-solid fa-circle-info adl-th-i' title=\"{adl_col_console_help}\" aria-label=\"{adl_col_console_help}\"></i></th><th class='adl-th-r'>{adl_col_actions}</th></tr></thead><tbody id='adldap-rows'>";
    foreach($m["rows"] as $r){$h.=adl_row($r,$tpl);}
    $h.="</tbody></table></div>";
    $show=str_replace("%s",$m["total"],$tpl->_ENGINE_parse_body("{adl_showing}"));
    $h.="<div class='adl-card-f'><span>$show</span>";
    if($m["kerberos"]){
        $h.="<span class='adl-kerb'><span class='adl-kerb-on'><i class='fa-solid fa-key' aria-hidden='true'></i>{adl_kerb_on}</span>"
            ."<a href='javascript:blur();' OnClick=\"LoadAjax('MainContent','fw.system.kerberos.php')\">{adl_kerb_link} <i class='fa-solid fa-arrow-right' aria-hidden='true'></i></a></span>";
    }
    $h.="<span class='awc-pg' id='adldap-pager'></span></div></section>";
    return $h;
}

function adl_tip():string{
    return "<p class='adl-tip'><i class='fa-solid fa-circle-info' aria-hidden='true'></i><span><strong>{adl_tip_t}:</strong> {adl_tip}</span></p>";
}

function adl_style():string{
    if(isset($GLOBALS["ADL_STYLE"])){return "";}
    $GLOBALS["ADL_STYLE"]=true;
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488","accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette()); if(is_array($b)){$ac=array_merge($ac,$b);}
    }
    $fill=function_exists("abt_fill")?abt_fill($ac["accent"]):$ac["accent_dark"];
    $c=array("<style>");
    $c[]=".adl{display:flex;flex-direction:column;gap:16px;margin-top:10px;color:#0f172a;font-size:14px}";
    $c[]=".adl *{box-sizing:border-box}";
    $c[]=".adl-mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,'Liberation Mono',monospace}";
    // Accroche
    $c[]=".adl-hero{display:grid;grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:24px;align-items:center;padding:28px;border:1px solid #e2e8f0;border-radius:16px;background:linear-gradient(90deg,{$ac["accent_soft"]},#ffffff 70%)}";
    $c[]=".adl-hero-main{display:flex;gap:18px;align-items:flex-start}";
    $c[]=".adl-hero-ico{flex:0 0 56px;width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:26px;background:#e0f2fe;color:#0369a1}";
    $c[]=".adl-hero-t{font-size:22px;font-weight:700;line-height:1.3;color:#0f172a;margin:0 0 8px 0}";
    $c[]=".adl-hero-p{font-size:14px;line-height:1.6;color:#475569;margin:0}";
    $c[]=".adl-how{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px}";
    $c[]=".adl-how-t{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:{$ac["accent_dark"]};margin-bottom:10px}";
    $c[]=".adl-how ol{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px}";
    $c[]=".adl-how li{display:flex;gap:10px;align-items:flex-start;font-size:13px;color:#475569;line-height:1.45}";
    $c[]=".adl-how li strong{color:#0f172a;font-weight:600}";
    $c[]=".adl-num{flex:0 0 24px;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:$fill;color:#fff;font-size:13px;font-weight:700}";
    // Cartes
    $c[]=".adl-unlocks{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}";
    $c[]=".adl-u{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,.05)}";
    $c[]=".adl-u-h{display:flex;align-items:center;gap:12px;margin-bottom:8px}";
    $c[]=".adl-u-ico{flex:0 0 36px;width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px}";
    $c[]=".adl-t1,.adl-t4{background:#e0f2fe;color:#0369a1}";
    $c[]=".adl-t2,.adl-t3{background:{$ac["accent_soft"]};color:{$ac["accent_dark"]}}";
    $c[]=".adl-u-t{font-size:15px;font-weight:600;color:#0f172a;line-height:1.3}";
    $c[]=".adl-u-p{font-size:13px;color:#475569;line-height:1.5;margin:0}";
    // Sante
    $c[]=".adl-health{display:flex;align-items:center;gap:0;padding:14px 8px;background:#fff;border:1px solid #e2e8f0;border-radius:12px}";
    $c[]=".adl-fig{display:flex;flex-direction:column;gap:6px;padding:0 20px;border-right:1px solid #e2e8f0}";
    $c[]=".adl-fig-l{font-size:13px;color:#64748b}";
    $c[]=".adl-fig-v{font-size:24px;font-weight:700;color:#0f172a;line-height:1.1}";
    $c[]=".adl-fig-v small{font-size:13px;font-weight:400;color:#64748b}";
    $c[]=".adl-verdict{flex:1 1 auto;display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:0 16px;min-width:0;font-size:14px}";
    $c[]=".adl-verdict>span{display:flex;flex-direction:column;min-width:0}";
    $c[]=".adl-v-ok{color:#166534}.adl-v-off{color:#64748b}.adl-v-bad{color:#92400e}";
    $c[]=".adl-v-ico{flex:0 0 36px;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#fef3c7;color:#b45309}";
    $c[]=".adl-v-sub{font-size:13px;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:380px}";
    // Pastilles
    $c[]=".adl-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:13px;font-weight:600;white-space:nowrap;width:max-content}";
    $c[]=".adl-ok{background:#dcfce7;color:#166534}.adl-err{background:#fee2e2;color:#b91c1c}.adl-warn{background:#fef3c7;color:#92400e}.adl-off{background:#f1f5f9;color:#64748b}";
    // Bandeaux
    $c[]=".adl-banner{display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;font-size:14px}";
    $c[]=".adl-b-warn{background:#fef3c7;border:1px solid #fcd34d;color:#92400e}";
    $c[]=".adl-b-off{background:#f1f5f9;border:1px solid #e2e8f0;color:#334155}";
    // Carte-table
    $c[]=".adl-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden}";
    $c[]=".adl-card-h{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 20px;border-bottom:1px solid #e2e8f0}";
    $c[]=".adl-card-t{font-size:16px;font-weight:600;color:#0f172a;display:flex;align-items:center;gap:10px}";
    $c[]=".adl-count{padding:1px 10px;border-radius:999px;background:#f1f5f9;color:#334155;font-size:13px;font-weight:600}";
    $c[]=".adl-legend{display:flex;gap:16px;font-size:13px;color:#475569}";
    $c[]=".adl-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:6px;vertical-align:middle}";
    $c[]=".adl-dot-ok{background:#16a34a}.adl-dot-err{background:#dc2626}";
    $c[]=".adl-scroll{overflow-x:auto}";
    $c[]=".adl-tbl{width:100%;border-collapse:collapse}";
    $c[]=".adl-tbl th{padding:12px 20px;font-size:13px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#475569;background:#f8fafc;text-align:left;border-bottom:1px solid #e2e8f0;white-space:nowrap}";
    $c[]=".adl-th-r{text-align:right!important}";
    $c[]=".adl-th-i{color:#94a3b8;margin-left:4px;cursor:help}";
    $c[]=".adl-tbl td{padding:16px 20px;vertical-align:top;border-top:1px solid #f1f5f9}";
    $c[]=".adl-tbl tbody tr:first-child td{border-top:0}";
    $c[]=".adl-row-bad{background:#fffbfa}";
    $c[]=".adl-conn{display:flex;gap:14px;align-items:flex-start;min-width:0}";
    $c[]=".adl-ico{flex:0 0 40px;width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;background:#e0f2fe;color:#0369a1}";
    $c[]=".adl-ico-off{background:#f1f5f9;color:#94a3b8}";
    $c[]=".adl-conn-t{min-width:0}";
    $c[]=".adl-srv{font-size:15px;font-weight:600;color:#0f172a!important;text-decoration:none!important;word-break:break-all}";
    $c[]=".adl-srv:hover{color:{$ac["accent_dark"]}!important}";
    $c[]=".adl-tags{display:flex;gap:6px;margin-top:6px}";
    $c[]=".adl-tag{display:inline-flex;align-items:center;gap:5px;padding:1px 8px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;color:#334155;font-size:13px;font-weight:500}";
    $c[]=".adl-tag-def{background:#e0f2fe;border-color:#bae6fd;color:#0369a1}";
    $c[]=".adl-meta{margin-top:6px;font-size:13px;color:#64748b;line-height:1.5;word-break:break-word}";
    $c[]=".adl-acct-w{display:flex;align-items:center;gap:8px}";
    $c[]=".adl-acct{padding:4px 10px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;font-size:13px;color:#0f172a;word-break:break-all}";
    $c[]=".adl-none{color:#94a3b8}";
    $c[]=".adl-ib{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border:0;border-radius:8px;background:transparent;color:#64748b;cursor:pointer}";
    $c[]=".adl-ib:hover{background:#f1f5f9;color:#0f172a}";
    $c[]=".adl-ib-del:hover{background:#fee2e2;color:#b91c1c}";
    $c[]=".adl-ib:focus-visible,.adl-srv:focus-visible,.adl-card-f a:focus-visible{outline:2px solid {$ac["accent_dark"]};outline-offset:2px}";
    $c[]=".adl-st-sub{margin-top:6px;font-size:13px;color:#166534;display:flex;align-items:center;gap:6px;max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
    $c[]=".adl-st-err{color:#b91c1c}";
    $c[]=".adl-pill+.adl-st-sub.adl-mono{color:#92400e}";
    $c[]=".adl-pill+.adl-st-sub.adl-st-err{color:#b91c1c}";
    $c[]=".adl-acts{text-align:right;white-space:nowrap}";
    $c[]=".adl-protected{display:inline-block;margin-left:8px;font-size:13px;font-style:italic;color:#94a3b8}";
    // Interrupteur d'acces console (td_UserADCanConnect)
    $c[]=".adl-sw-w{display:flex;flex-direction:column;gap:4px}";
    $c[]=".adl-sw{display:inline-flex;align-items:center;gap:10px;border:0;background:transparent;padding:0;cursor:pointer;font-size:14px;font-weight:600;color:#64748b}";
    $c[]=".adl-sw-tr{position:relative;width:40px;height:22px;border-radius:11px;background:#cbd5e1;transition:background-color .15s ease;flex:0 0 40px}";
    $c[]=".adl-sw-tr::after{content:'';position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.25);transition:transform .15s ease}";
    $c[]=".adl-sw[aria-checked=true]{color:{$ac["accent_dark"]}}";
    $c[]=".adl-sw[aria-checked=true] .adl-sw-tr{background:$fill}";
    $c[]=".adl-sw[aria-checked=true] .adl-sw-tr::after{transform:translateX(18px)}";
    $c[]=".adl-sw[disabled]{cursor:not-allowed;opacity:.6}";
    $c[]=".adl-sw:focus-visible{outline:2px solid {$ac["accent_dark"]};outline-offset:3px;border-radius:12px}";
    $c[]=".adl-sw-sub{font-size:13px;color:#64748b}";
    $c[]=".adl-card-f{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:14px 20px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:13px;color:#64748b}";
    $c[]=".adl-kerb{display:flex;align-items:center;gap:14px}";
    $c[]=".adl-kerb-on{display:inline-flex;align-items:center;gap:6px;color:#166534;font-weight:600}";
    $c[]=".adl-card-f a{color:{$ac["accent_dark"]};font-weight:600;text-decoration:none}";
    $c[]=".adl-empty{display:flex;flex-direction:column;align-items:center;gap:8px;padding:40px 20px;text-align:center}";
    $c[]=".adl-empty>i,.adl-empty>svg{font-size:40px;color:#94a3b8}";
    $c[]=".adl-empty-t{font-size:16px;font-weight:600;color:#0f172a}";
    $c[]=".adl-empty-p{font-size:14px;color:#64748b;margin-bottom:8px}";
    $c[]=".adl-tip{display:flex;align-items:flex-start;gap:10px;margin:0;font-size:14px;color:#475569}";
    $c[]=".adl-tip>i,.adl-tip>svg{color:{$ac["accent_dark"]};margin-top:3px}";
    $c[]="@media (max-width:1200px){.adl-hero{grid-template-columns:minmax(0,1fr)}.adl-unlocks{grid-template-columns:repeat(2,minmax(0,1fr))}.adl-health{flex-wrap:wrap;row-gap:12px}.adl-verdict{justify-content:flex-start;flex-basis:100%}}";
    $c[]="@media (prefers-reduced-motion:reduce){.adl *{transition:none!important}}";
    $c[]="</style>";
    return @implode("",$c);
}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $m=adl_model();

    // Boutons d'en-tete : memes actions et memes conditions qu'avant, icones distinctes.
    $btn=array();
    if(!$m["cluster"]){
        $btn[]="<div class=\"btn-group\" data-toggle=\"buttons\" id='active-directory-barr'>";
        if(!$m["meta"]){
            if(!$m["license"]){
                $btn[]=$tpl->button_label_table("{new_connection}","blur()","fa-plus","AllowAddGroup",true);
            }elseif(!$m["slave"]){
                $btn[]=$tpl->button_label_table("{new_connection}","Loadjs('$page?connection-js=9999999999999')","fa-plus","AllowAddGroup");
            }
        }
        $btn[]=$tpl->button_label_table("{refresh}","LoadAjaxSilent('active_directory_ldap_connections','$page?table=yes');","fa-solid fa-rotate","AllowAddGroup");
        $btn[]=$tpl->button_label_table("{simulate_authentication}","Loadjs('$page?simulate-js=yes');","fa-solid fa-user-check","AllowAddGroup");
        if(!$m["slave"] && !$m["meta"]){
            $btn[]=$tpl->button_label_table("{import_export}","Loadjs('$page?export-js=yes');","fa-solid fa-file-import","AllowAddGroup");
            if(!$m["license"]){
                $btn[]=$tpl->button_label_table("{remove_feature}","Loadjs('$page?uninstall-js=yes');","fa-solid fa-trash-can","AllowAddGroup");
            }
        }
        $btn[]="</div>";
    }

    $html=array();
    $html[]=adl_hero();
    $html[]=adl_unlocks();
    $html[]=adl_health($m,$tpl);
    $body=$tpl->_ENGINE_parse_body(@implode("\n",$html));
    $body.=adl_banners($m,$tpl);
    $body.=$tpl->_ENGINE_parse_body(adl_table($m,$tpl).adl_tip());

    $TINY_ARRAY["TITLE"]="{adl_title}";
    $TINY_ARRAY["ICO"]="fab fa-windows";
    $TINY_ARRAY["EXPL"]="{adl_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $script="<script>\n$jstiny\nNoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."\n</script>";

    echo awc_table_style().adl_style()."<div class='adl'>$body</div>".$script.awc_pager_js(50,"adldap-rows","adldap-pager");
    return true;
}

function CleanArray($ActiveDirectoryConnections):array{
    $NewArray=array();
    $c=0;
    foreach($ActiveDirectoryConnections as $index=>$main){
        if(!is_numeric($index)){continue;}
        $NewArray[$index]=$main;
        $c++;
    }
    if($c==0){
     $NewArray2[]=DefaultConnection();
     return $NewArray2;
    }
    return $NewArray;

}
function connection_password_save():bool{
    $tpl    = new template_admin();
    $tpl->CLEAN_POST();
    $ID     = intval($_POST["connection-password"]);
    // index 0 (Kerberos/NTLM, KerbAuthInfos) reste local ; seules les connexions > 0 sont tenues
    if($ID!=0){$meta=ad_meta_refuse();if($meta!==""){echo $meta;return false;}}
    unset($_POST["connection-password"]);
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    $ActiveDirectoryConnectionsTemp=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections");
    if(strlen($ActiveDirectoryConnectionsTemp)<4) {
        $ActiveDirectoryConnectionsTemp=serialize(array());
    }
    writelogs("Save Active Directory credentials ID:$ID",__FUNCTION__,__FILE__
        ,__LINE__);
    $ActiveDirectoryConnections=CleanArray(unserialize($ActiveDirectoryConnectionsTemp));
    if(!is_array($ActiveDirectoryConnections)){$ActiveDirectoryConnections=array();}
    if(isset($_POST["LDAP_DN"])) {
        $_POST["WINDOWS_SERVER_ADMIN"] = $_POST["LDAP_DN"];
    }
    // Champ laisse vide = "ne pas changer": on retire la cle du POST pour
    // qu'elle n'ecrase pas la valeur enregistree lors de la fusion.
    if(isset($_POST["LDAP_PASSWORD"]) && $_POST["LDAP_PASSWORD"]===""){
        unset($_POST["LDAP_PASSWORD"]);
    }
    if(isset($_POST["LDAP_DN"]) && trim((string)$_POST["LDAP_DN"])===""){
        unset($_POST["LDAP_DN"]);
        unset($_POST["WINDOWS_SERVER_ADMIN"]);
    }
    if($ID==0) {
        if ($Enablehacluster == 1) {
            $haClusterAD = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));

            if (isset($_POST["LDAP_DN"])) {
                $haClusterAD["KerberosUsername"] = $_POST["LDAP_DN"];
            }
            if (isset($_POST["LDAP_PASSWORD"])) {
                $haClusterAD["KerberosPassword"] = $_POST["LDAP_PASSWORD"];
            }
            $datas = base64_encode(serialize($haClusterAD));
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterAD", $datas);
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
        }
        $array = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
        foreach ($_POST as $key => $val) {
            $array[$key] = $val;
        }
        $datas = base64_encode(serialize($array));
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile($datas, "KerbAuthInfos");
        adldap_save_default_row($ActiveDirectoryConnections,$array);
        if ($Enablehacluster == 1) {
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
        }
        return admin_tracks_post("Default Active Directory credentials #$ID");
    }

    if($ID<1){$ID=1;}
    writelogs("Save Active Directory credentials ID:$ID",__FUNCTION__,__FILE__,__LINE__);

    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    if(!is_array($ActiveDirectoryConnections)){$ActiveDirectoryConnections=array();}
    // La connexion existante sert de BASE: la fusion ne doit ajouter que ce que
    // le formulaire a reellement envoye.
    $array=(isset($ActiveDirectoryConnections[$ID]) && is_array($ActiveDirectoryConnections[$ID]))
        ?$ActiveDirectoryConnections[$ID]
        :array();

    foreach ($_POST as $key=>$val){
        $array[$key]=$val;
    }

    $ActiveDirectoryConnections[$ID]=$array;
    adldap_save_connections($ActiveDirectoryConnections);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    if($Enablehacluster==1) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
    }

    return admin_tracks_post("Active Directory credentials #$ID");

}
function connection_save():bool{
    $tpl    = new template_admin();
    $tpl->CLEAN_POST();
    $ID     = intval($_POST["connection"]);
    if($ID!=0){$meta=ad_meta_refuse();if($meta!==""){echo $meta;return false;}}
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    $ActiveDirectoryConnectionsTemp=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections");
    if(strlen($ActiveDirectoryConnectionsTemp)<4) {
        $ActiveDirectoryConnectionsTemp=serialize(array());
    }
    if(!isset($_POST["LDAP_SUFFIX"])){
        $_POST["LDAP_SUFFIX"]="";
    }

    $LDAP_SUFFIX=$_POST["LDAP_SUFFIX"];
    if(strlen($LDAP_SUFFIX)<3){
        if(isset($_POST["LDAP_DN"])) {
            $Pattern["username"]=$_POST["LDAP_DN"];
            $Pattern["password"]=$_POST["LDAP_PASSWORD"];
            $Pattern["hostname"]=$_POST["LDAP_SERVER"];
            $Pattern["ssl"]=intval($_POST["LDAP_SSL"]);;
            $Token=urlencode(base64_encode(serialize($Pattern)));
            $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/activedirectory/finddse/$Token"));
            if($json->Status) {
                foreach ($json->Info as $suffix) {
                    if(preg_match("#^DC#i",$suffix)){
                        $LDAP_SUFFIX=$suffix;
                        $_POST["LDAP_SUFFIX"]=$suffix;
                    }
                }
            }
        }

    }


    writelogs("Save Active Directory Connection ID:$ID",__FUNCTION__,__FILE__
            ,__LINE__);
    $ActiveDirectoryConnections=CleanArray(unserialize($ActiveDirectoryConnectionsTemp));
    if(!is_array($ActiveDirectoryConnections)){$ActiveDirectoryConnections=array();}

    // Ceinture et bretelles: le formulaire de connexion ne poste plus ni
    // LDAP_DN ni LDAP_PASSWORD (voir adldap_credentials_field()), mais si une
    // ancienne page ou un appel direct en envoyait un VIDE, il ne doit pas
    // effacer le compte enregistre. Seule connection_password_save() ecrit ces
    // deux cles.
    if(isset($_POST["LDAP_PASSWORD"]) && trim((string)$_POST["LDAP_PASSWORD"])===""){
        unset($_POST["LDAP_PASSWORD"]);
    }
    if(isset($_POST["LDAP_DN"]) && trim((string)$_POST["LDAP_DN"])===""){
        unset($_POST["LDAP_DN"]);
    }

    if(isset($_POST["LDAP_DN"])) {
        $_POST["WINDOWS_SERVER_ADMIN"] = $_POST["LDAP_DN"];
    }

    // Le champ "connection" du formulaire ne sert qu'a TRANSPORTER l'identifiant
    // de la connexion a enregistrer ($ID, lu juste au-dessus). Il etait ensuite
    // recopie tel quel dans l'enregistrement par la boucle de fusion: a la
    // creation, $ID vaut 9999999999999, si bien que TOUTES les connexions
    // creees portaient cette meme valeur. remove_js() cherchait alors par ce
    // champ et supprimait la mauvaise entree. La cle est retiree du POST; elle
    // est reattribuee depuis l'index reel par adldap_reindex().
    unset($_POST["connection"]);

    if($ID==0){
        if($Enablehacluster==1){
            $haClusterAD=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
            $haClusterAD["kerberosActiveDirectoryHost"]=$_POST["LDAP_SERVER"];
            $haClusterAD["kerberosActiveDirectory2Host"]=$_POST["LDAP_SERVER2"];
            if(isset($_POST["LDAP_DN"])) {
                $haClusterAD["KerberosUsername"] = $_POST["LDAP_DN"];
            }
            if(isset($_POST["LDAP_PASSWORD"])) {
                $haClusterAD["KerberosPassword"] = $_POST["LDAP_PASSWORD"];
            }
            $haClusterAD["kerberosActiveDirectorySuffix"]=$LDAP_SUFFIX;
            $haClusterAD["KerberosLDAPS"]=$_POST["LDAP_SSL"];
            $datas=base64_encode(serialize($haClusterAD));
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaClusterAD",$datas);
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
        }

        $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
        // unserializeb64() rend false sur un reglage vide: la fusion repartait
        // alors du neant et effacait tout KerbAuthInfos.
        if(!is_array($array)){$array=array();}
        foreach ($_POST as $key=>$val){$array[$key]=$val;}
        $datas=base64_encode(serialize($array));
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile($datas, "KerbAuthInfos");
        adldap_save_default_row($ActiveDirectoryConnections,$array);
        $_POST["LDAP_PASSWORD"]="****";
        if($Enablehacluster==1) {
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
        }
        return admin_tracks_post("Update Default Active Directory Connection");
    }

    if($ID==9999999999999){
        // Le cas "liste vide" ecrivait a l'index 1, laissant l'index 0 absent:
        // les cles n'etaient donc pas contigues des la premiere creation.
        $ActiveDirectoryConnections[]=$_POST;
        $saved=adldap_save_connections($ActiveDirectoryConnections);
        // L'etape 2 a besoin de l'identifiant REELLEMENT attribue. Il ne peut
        // pas transiter par le corps de la reponse: un handler derriere
        // form_outside doit n'emettre RIEN en cas de succes, toute reponse de
        // plus de trois caracteres etant rendue comme une banniere d'erreur.
        // Il passe donc par la session. adldap_reindex() conserve l'ordre
        // d'insertion, la nouvelle entree est donc la derniere.
        $_SESSION["ADLDAP_NEW_ID"]=count($saved)-1;
        $_POST["LDAP_PASSWORD"]="****";
        if($Enablehacluster==1) {
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
        }
        return admin_tracks_post("Create a new Active Directory Connection #$ID");
    }

    // Cette branche decalait l'index d'un cran ("$ID=$ID-1", puis un plancher a
    // 1): editer la connexion #2 ecrivait donc dans l'entree #1 et laissait la
    // #2 inchangee. connection_popup() lit pourtant [$ID] et
    // connection_password_save() ecrit dans [$ID] -- les deux chemins de
    // sauvegarde visaient des entrees differentes pour la meme connexion. Le
    // decalage est supprime.
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    if(!is_array($ActiveDirectoryConnections)){$ActiveDirectoryConnections=array();}

    // $array n'etait JAMAIS initialise ici: la fusion partait donc du vide et
    // l'entree enregistree etait remplacee par les seuls champs du formulaire.
    // Tout ce que le formulaire n'affiche pas -- au premier rang desquels
    // LDAP_DN et LDAP_PASSWORD -- disparaissait a chaque enregistrement.
    $array=(isset($ActiveDirectoryConnections[$ID]) && is_array($ActiveDirectoryConnections[$ID]))
        ?$ActiveDirectoryConnections[$ID]
        :array();

    foreach ($_POST as $key=>$val){$array[$key]=$val;}

    $ActiveDirectoryConnections[$ID]=$array;
    adldap_save_connections($ActiveDirectoryConnections);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    if($Enablehacluster==1) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/notify/all");
    }
    $_POST["LDAP_PASSWORD"]="****";
    return admin_tracks_post("Save Active Directory Connection #$ID");

}

function DefaultConnection():array{
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));

    if($Enablehacluster==1){
        $haClusterAD=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterAD"));
        $KerberosUsername=$haClusterAD["KerberosUsername"];
        $KerberosPassword=$haClusterAD["KerberosPassword"];
        $kerberosActiveDirectoryHost=$haClusterAD["kerberosActiveDirectoryHost"];
        $kerberosActiveDirectorySuffix=trim($haClusterAD["kerberosActiveDirectorySuffix"]);
        $KerberosLDAPS=intval($haClusterAD["KerberosLDAPS"]);
        if(!isset($haClusterAD["kerberosActiveDirectory2Host"])){
            $haClusterAD["kerberosActiveDirectory2Host"]="";
        }
        $ldap_port=389;
        if($KerberosLDAPS==1){
            $ldap_port=636;
        }
        $array["LDAP_DN"]=$KerberosUsername;
        $array["LDAP_SUFFIX"]=$kerberosActiveDirectorySuffix;
        $array["LDAP_SERVER"]=$kerberosActiveDirectoryHost;
        $array["LDAP_PORT"]=$ldap_port;
        $array["LDAP_PASSWORD"]=$KerberosPassword;
        $array["LDAP_SSL"]=$KerberosLDAPS;
        $array["LDAP_SERVER2"]=$haClusterAD["kerberosActiveDirectory2Host"];
        return $array;

    }
    $active=new ActiveDirectory();
    $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}
    if(!isset($array["LDAP_SERVER2"])){$array["LDAP_SERVER2"]=null;}
    if(!isset($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
    if(!isset($array["LDAP_DN"])){$array["LDAP_DN"]=null;}
    if(!isset($array["LDAP_PASSWORD"])){$array["LDAP_PASSWORD"]=null;}
    if(!isset($array["LDAP_DN"])){$array["LDAP_DN"]=$array["WINDOWS_SERVER_ADMIN"];}
    if(!isset($array["ADNETIPADDR"])){$array["ADNETIPADDR"]=null;}
    if($array["LDAP_PASSWORD"]==null){
        if(isset($array["WINDOWS_SERVER_PASS"])) {
            $array["LDAP_PASSWORD"] = $array["WINDOWS_SERVER_PASS"];
        }
    }

    if($array["ADNETIPADDR"]==null){$array["ADNETIPADDR"]=$active->ldap_ipaddr;}
    if($array["LDAP_DN"]==null){$array["LDAP_DN"]=$active->ldap_dn_user;}
    if($array["LDAP_SUFFIX"]==null){$array["LDAP_SUFFIX"]=$active->suffix;}
    if($array["LDAP_SERVER"]==null){$array["LDAP_SERVER"]=$active->ldap_host;}
    if($array["LDAP_PORT"]==null){$array["LDAP_PORT"]=$active->ldap_port;}
    if($array["LDAP_PASSWORD"]==null){$array["LDAP_PASSWORD"]=$active->ldap_password;}
    if($array["LDAP_SSL"]==null){$array["LDAP_SSL"]=$active->ldap_ssl;}
    if($array["LDAP_SERVER"]==null){$array["LDAP_SERVER"]=$array["fullhosname"];}
    $array["connection"]=0;
    return $array;
}

function connection_locked():bool{
    $ID=intval($_GET["connection-popup"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    $array=$ActiveDirectoryConnections[$ID];

    $array=ArrayIsset($array);
    $ipaddr="";
    if(strlen($array["ADNETIPADDR"])>3){
        $ipaddr="<br><small>{$array["ADNETIPADDR"]}</small>";
    }
    echo ad_meta_banner($tpl);
    $tpl->table_form_field_text("{connection}",$ID,ico_params);
    $tpl->table_form_field_text("{hostname}","{$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]}$ipaddr",ico_microsoft);
    if(strlen($array["LDAP_SERVER2"])>3) {
        $tpl->table_form_field_text("{FQDNDC2}", "{$array["LDAP_SERVER2"]}:{$array["LDAP_PORT"]}", ico_microsoft);
    }
    $tpl->table_form_field_bool("{enable_ssl}",$array["LDAP_SSL"],ico_ssl);
    $tpl->table_form_field_text("{username}",$array["LDAP_DN"],ico_admin);
    $tpl->table_form_field_text("{ldap_suffix}",$array["LDAP_SUFFIX"],ico_database);
    echo $tpl->table_form_compile();
    return true;
}

function ArrayIsset($array):array{
    if(!isset($array["LDAP_SERVER2"])){$array["LDAP_SERVER2"]="";}
    if(!isset($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
    if(!isset($array["LDAP_DN"])){$array["LDAP_DN"]="";}
    if(!isset($array["LDAP_PASSWORD"])){$array["LDAP_PASSWORD"]="";}
    if(!isset($array["ADNETIPADDR"])){$array["ADNETIPADDR"]="";}
    if(!isset($array["LDAP_SSL"])){$array["LDAP_SSL"]="0";}
    if(!isset($array["ADUserCanConnect"])){$array["ADUserCanConnect"]="0";}
    if(!isset($array["LDAP_SERVER"])){$array["LDAP_SERVER"]="";}
    if(!isset($array["LDAP_SUFFIX"])){$array["LDAP_SUFFIX"]="";}
    if(preg_match("#^(.+?)@(.+?)@$#",trim($array["LDAP_DN"]),$re)){$array["LDAP_DN"]="$re[1]@$re[2]";}
    if(intval($array["LDAP_PORT"])==0){$array["LDAP_PORT"]=389;}
    return $array;
}

/**
 * Masque un compte de service Active Directory pour l'affichage.
 *
 * On garde de quoi RECONNAITRE le compte sans le divulguer: les deux premiers
 * caracteres de l'identifiant et, quand il y en a un, le domaine ou le suffixe.
 *   administrateur@articatech.int  ->  ad************@articatech.int
 *   CN=svc-proxy,DC=corp,DC=int    ->  CN=sv*******,DC=corp,DC=int
 */
function adldap_mask_account($dn):string{
    $dn=trim((string)$dn);
    if($dn===""){return "";}

    // Forme UPN: on ne masque que la partie locale.
    if(preg_match('#^(.+)@(.+)$#',$dn,$re)){
        return adldap_mask_word($re[1])."@".htmlspecialchars($re[2]);
    }
    // Forme DN: on ne masque que la valeur du premier composant.
    if(preg_match('#^([A-Za-z]+=)([^,]+)(,.*)$#',$dn,$re)){
        return htmlspecialchars($re[1]).adldap_mask_word($re[2]).htmlspecialchars($re[3]);
    }
    return adldap_mask_word($dn);
}

/** Garde les deux premiers caracteres d'un mot, remplace le reste par des points. */
function adldap_mask_word($w):string{
    $w=(string)$w;
    $len=function_exists("mb_strlen")?mb_strlen($w,"UTF-8"):strlen($w);
    if($len<=2){return str_repeat("&bull;",3);}
    $head=function_exists("mb_substr")?mb_substr($w,0,2,"UTF-8"):substr($w,0,2);
    return htmlspecialchars($head).str_repeat("&bull;",min($len-2,12));
}

/**
 * La ligne "compte de service" du formulaire de connexion: etat du compte,
 * identifiant masque, et bouton de modification. Elle ne poste AUCUN champ,
 * donc enregistrer le formulaire ne peut pas alterer les identifiants.
 */
function adldap_credentials_field($array,$ID,$page,$tpl):string{
    $dn=(string)($array["LDAP_DN"] ?? "");
    $hasPassword=(strlen((string)($array["LDAP_PASSWORD"] ?? ""))>0);
    $js="Loadjs('$page?connection-password-js=$ID')";

    // Connexion pas encore creee: son ID est la sentinelle 9999999999999 et
    // ActiveDirectoryConnections n'a aucune entree a cet index. La boite des
    // identifiants ecrit precisement dans cette entree -- elle n'a donc rien ou
    // ecrire, et connection_password_popup() repondait "9999999999999 ??".
    // On n'offre pas un bouton dont le seul resultat possible est une erreur:
    // les identifiants se definissent apres l'ajout, quand la connexion porte un
    // vrai identifiant. Le formulaire n'a de toute facon qu'une action ici, {add}.
    if($ID==9999999999999){
        $value="<span class='awc-badge' style='background:#865300'>{not_configured}</span>";
        return $tpl->field_info("credentials","{credentials}",$value);
    }

    if($dn==="" && !$hasPassword){
        $value="<span class='awc-badge' style='background:#865300'>{not_configured}</span>";
        return $tpl->field_text_info_button("{credentials}",$value,"{define}",$js);
    }

    $badge=$hasPassword
        ?"<span class='awc-badge awc-badge-on'>{password} &check;</span>"
        :"<span class='awc-badge' style='background:#865300'>{password} {not_configured}</span>";

    $value="<span style='font-family:monospace'>".adldap_mask_account($dn)."</span>&nbsp;&nbsp;$badge";
    return $tpl->field_text_info_button("{credentials}",$value,"{modify}",$js);
}

function connection_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $ClusterNotReplicateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterNotReplicateAD"));
    $HaClusterClient = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    $LOCK=false;
    if($PowerDNSEnableClusterSlave==1 OR $HaClusterClient==1){
        $LOCK=true;
    }
    if($ClusterNotReplicateAD==1){
        $LOCK=false;
    }



    if($LOCK || (ad_meta_managed() && intval($_GET["connection-popup"])!=0)) {
        return connection_locked();
    }
    $ID=intval($_GET["connection-popup"]);
    $btname="{apply}";
    $jsafter=null;
    $title="";
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));

    if($ID==0){
        $array = $ActiveDirectoryConnections[$ID] ?? DefaultConnection();
        $title="{default}: {$array["LDAP_SERVER"]} / {$array["LDAP_DN"]}";
        $jsafter="LoadAjaxSilent('active_directory_ldap_connections','$page?table=yes');";

    }
    if($ID==9999999999999){
        // Etape 1 d'un parcours en deux temps. On ne demande ici que ce qui ne
        // depend d'aucun compte de service: ni les identifiants, ni le suffixe,
        // dont le parcours interroge l'annuaire et exige donc un compte. Le
        // bouton enregistre, puis enchaine sur l'etape 2 qui, elle, connait
        // l'identifiant reellement attribue a la connexion.
        $btname="{next}";
        $jsafter="dialogInstance2.close();"
            ."LoadAjaxSilent('active_directory_ldap_connections','$page?table=yes');"
            ."Loadjs('$page?connection-step2-js=yes');";
        $title="{new_connection}";
    }

    if($ID<9999999999999){
        if($ID>0){
            $array=$ActiveDirectoryConnections[$ID];
            $title="[$ID]: {$array["LDAP_SERVER"]} / {$array["LDAP_DN"]}";
            $jsafter="LoadAjaxSilent('active_directory_ldap_connections','$page?table=yes');";
        }
    }

    $array=ArrayIsset($array);

    $form[]=$tpl->field_info("connection", "{connection} ID",$ID);
    $form[]=$tpl->field_text("LDAP_SERVER", "{hostname}","{$array["LDAP_SERVER"]}",true);
    $form[]=$tpl->field_text("ADNETIPADDR", "{ipaddr} ({optional})","{$array["ADNETIPADDR"]}");
    $form[]=$tpl->field_text("LDAP_SERVER2", "{FQDNDC2}", $array["LDAP_SERVER2"],false,"{ad_quick_1}");
    $form[]=$tpl->field_numeric("LDAP_PORT", "{ldap_port}","{$array["LDAP_PORT"]}");
    $form[]=$tpl->field_checkbox("LDAP_SSL", "{enable_ssl} (port 636)","{$array["LDAP_SSL"]}");
    // Sur une connexion neuve ces deux sections sont reportees a l'etape 2:
    // les identifiants n'ont pas encore d'entree ou etre ecrits, et le parcours
    // du suffixe ne peut pas interroger l'annuaire sans eux.
    $isNew=($ID==9999999999999);

    // Le compte de service n'est JAMAIS rendu dans ce formulaire, ni en clair
    // ni sous forme de champ vide.
    //
    // Auparavant, tant que le mot de passe n'etait pas renseigne, la section
    // affichait un champ "{username}" et un champ "{password}" VIDES. Enregistrer
    // le formulaire -- meme sans y toucher, par exemple pour changer un port --
    // envoyait donc deux chaines vides qui ECRASAIENT le compte enregistre.
    // C'est exactement ce qui detruisait la connexion par defaut (index 0),
    // dont le mot de passe est vide dans ActiveDirectoryConnections alors que
    // les identifiants reels vivent dans KerbAuthInfos.
    //
    // La section est desormais en LECTURE SEULE et ne poste rien: la
    // modification passe par la boite dediee, qui est la seule a ecrire ces
    // deux cles.
    if(!$isNew){
        $form[]=$tpl->field_section("{credentials}");
        $form[]=adldap_credentials_field($array,$ID,$page,$tpl);
    }

    $form[]=$tpl->field_checkbox("ADUserCanConnect","{ADUserCanConnect}",intval($array["ADUserCanConnect"]),false,"{ADUserCanConnect_explain}");
    if(!$isNew){
        $form[]=$tpl->field_section("{suffix}");
        $form[]=$tpl->field_ad_suffix_ad_conns("LDAP_SUFFIX",$ID, "{ldap_suffix}","{$array["LDAP_SUFFIX"]}");
    }


    echo $tpl->form_outside($title, @implode("\n", $form),null,$btname,$jsafter,"AsSystemAdministrator",true,$LOCK);

    return true;




}