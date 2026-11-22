<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

// Repartiteur(s) mort(s) retire(s): rule_settings()/rule_save() ne sont
// definies NI dans ce fichier NI ailleurs, et aucun code du depot ne produit
// le parametre qui les declenchait -- ni cette page, ni une autre. Les URL
// repondaient donc un fatal « Call to undefined function ». Vestige d'un
// squelette de page recopie; les pages qui utilisent VRAIMENT ?rule-popup=
// ou ?rule-settings= definissent leur propre handler.
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_POST["PostfixActiveDirectory"])){Save();exit;}
if(isset($_GET["saslauthd-config"])){parameters_flat();exit;}
if(isset($_GET["saslauthd-status"])){saslauthd_status();exit;}
if(isset($_GET["saslauthd-restart"])){saslauthd_restart();exit;}
if(isset($_GET["params-js"])){parameters_js();exit;}
if(isset($_GET["params-popup"])){parameters_popup();exit;}



page();

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{parameters}"]="$page?parameters=yes";
    echo $tpl->tabs_default($array);
}

function page(){
	$tpl=new template_admin();
    $page=CurrentPageName();
    $mtitle=$tpl->_ENGINE_parse_body("{mynetworks_title}");
    $active_directory_postfix_auth=$tpl->_ENGINE_parse_body("{active_directory_postfix_auth}");
    $active_directory_postfix_auth=str_replace("{mynetworks_title}",$mtitle,$active_directory_postfix_auth);
    $html=$tpl->page_header("{active_directory_authentication}",
        "fab fa-windows",$active_directory_postfix_auth,"$page?tabs=yes",
        "postfix-activedirectory","postfix-ad-progress",false,"postfix-ad-div");

if(isset($_GET["main-page"])){
	$tpl=new template_admin(null,$html);
	echo $tpl->build_firewall();
	return;

}
	
	echo $tpl->_ENGINE_parse_body($html);

}

function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $sock=new sockets();
    $instanceid=intval($_POST["instanceid"]);
    $sock->REST_API_POSTFIX("/postfix/sasl/$instanceid");
}

function parameters(){
    $page=CurrentPageName();
    // Mise en page en flex: la cellule de gauche etait figee a 340px alors que la
    // tuile de service qu'elle recoit en mesure 335 plus ses marges.
    $html[]="<div style='display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start;margin:10px;width:90%'>";
    $html[]="<div style='flex:0 0 auto'><div id='saslauthd-status'></div></div>";
    $html[]="<div style='flex:1 1 420px;min-width:0'><div id='saslauthd-config'></div></div>";
    $html[]="</div>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('saslauthd-config','$page?saslauthd-config=yes');";
    $html[]="</script>";
    echo @implode("\n",$html);
}

function saslauthd_status(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ini=new Bs_IniHandler();
    // Données riches (mémoire/uptime) rafraîchies par le cron du sous-système de statut.
    $ini->loadFile(PROGRESS_DIR."/APP_SASLAUTHD.status");

    // État "running" lu EN DIRECT depuis le démon Go (postfix/saslauthd) — il fait autorité.
    // Plus aucun passage par framework/saslauthd.php.
    $st=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/saslauthd/status"),true);
    if(isset($ini->_params["SASLAUTHD"]) && isset($st["status"])){
        $ini->_params["SASLAUTHD"]["running"]=($st["status"]=="running")?1:0;
    }

    // Bouton "redémarrer" → appel direct au démon Go (/postfix/saslauthd/restart).
    $restart_js="Loadjs('$page?saslauthd-restart=yes')";

    echo $tpl->SERVICE_STATUS($ini, "SASLAUTHD",$restart_js);

}

function saslauthd_restart(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    // Redémarrage direct via le démon Go (asynchrone côté démon : go Restart()).
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/saslauthd/restart");
    echo "LoadAjaxSilent('saslauthd-status','$page?saslauthd-status=yes');";
}

function parameters_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{authenticate_trough_activedirectory}","$page?params-popup=yes");
}

function parameters_flat(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $PostfixActiveDirectory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixActiveDirectory"));
    $PostfixEnableSubmission=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixEnableSubmission"));
    $PostfixActiveDirectoryCNX=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixActiveDirectoryCNX"));
    $restart=$tpl->framework_buildjs("postfix2.php?sasl=yes","SMTP_SASL_PROGRESS","SMTP_SASL_LOG",
        "postfix-ad-progress",null,null,null,"AsPostfixAdministrator");


    $EnableMechCramMD5=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMechCramMD5");
    $EnableMechDigestMD5=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMechDigestMD5");
    $EnableMechLogin=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMechLogin");
    $EnableMechPlain=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMechPlain");
    if(!is_numeric($EnableMechCramMD5)){$EnableMechCramMD5=0;}
    if(!is_numeric($EnableMechDigestMD5)){$EnableMechDigestMD5=0;}
    if(!is_numeric($EnableMechLogin)){$EnableMechLogin=1;}
    if(!is_numeric($EnableMechPlain)){$EnableMechPlain=1;}

    $tpl->table_form_field_js("Loadjs('$page?params-js=yes')","AsPostfixAdministrator");
    if($PostfixActiveDirectory==0){
        $tpl->table_form_field_bool("{EnableWindowsAuthentication}",$PostfixActiveDirectory,ico_user);

    }else{

        $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADLicenseRemoved"))==1){$ActiveDirectoryConnections=array();}
        if(!is_array($ActiveDirectoryConnections)){$ActiveDirectoryConnections=array();}

        foreach ($ActiveDirectoryConnections as $index=>$ligne) {
            if (!is_numeric($index)) {continue;}
            if(!isset($ligne["LDAP_SERVER"])){continue;}
            if (!isset($ligne["LDAP_PORT"])) {$ligne["LDAP_PORT"] = 389;}
            if (!isset($ligne["LDAP_SSL"])) {$ligne["LDAP_SSL"] = 0;}
            if($ligne["LDAP_SSL"]==1){$ligne["LDAP_PORT"]=636;}
            $hash[$index]="{$ligne["LDAP_SERVER"]}:{$ligne["LDAP_PORT"]}";
        }

        $tpl->table_form_field_bool("{EnableWindowsAuthentication}",$PostfixActiveDirectory,ico_user);
        $tpl->table_form_field_text("{active_directory_connection}",$hash[$PostfixActiveDirectoryCNX],ico_microsoft);
        $tpl->table_form_field_bool("{PostfixEnableSubmission}",$PostfixEnableSubmission,ico_interface);
        $f=array();
        if ($EnableMechPlain==1){
            $f[]="Plain";
        }
        if ($EnableMechLogin==1){
            $f[]="login";
        }
        if ($EnableMechCramMD5==1){
            $f[]="Cram-MD5";
        }
        if ($EnableMechDigestMD5==1){
            $f[]="Digest-MD5";
        }
        $tpl->table_form_field_text("{auth_mechanism}",@implode(", ",$f),ico_lock);
    }

    $html[]=$tpl->table_form_compile();
    $html[]="<script>";
    $html[]="LoadAjaxSilent('saslauthd-status','$page?saslauthd-status=yes');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);

}
function parameters_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$PostfixActiveDirectory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixActiveDirectory"));
    $PostfixEnableSubmission=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixEnableSubmission"));
    $PostfixActiveDirectoryCNX=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixActiveDirectoryCNX"));
    $restart=$tpl->framework_buildjs("postfix2.php?sasl=yes","SMTP_SASL_PROGRESS","SMTP_SASL_LOG",
        "postfix-ad-progress","LoadAjaxSilent('saslauthd-config','$page?saslauthd-config=yes');",null,null,"AsPostfixAdministrator");

    $restart="dialogInstance2.close();LoadAjaxSilent('saslauthd-config','$page?saslauthd-config=yes');$restart;";


    $EnableMechCramMD5=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMechCramMD5");
    $EnableMechDigestMD5=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMechDigestMD5");
    $EnableMechLogin=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMechLogin");
    $EnableMechPlain=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMechPlain");
    if(!is_numeric($EnableMechCramMD5)){$EnableMechCramMD5=0;}
    if(!is_numeric($EnableMechDigestMD5)){$EnableMechDigestMD5=0;}
    if(!is_numeric($EnableMechLogin)){$EnableMechLogin=1;}
    if(!is_numeric($EnableMechPlain)){$EnableMechPlain=1;}




    $form[]=$tpl->field_checkbox("PostfixActiveDirectory",
        "{EnableWindowsAuthentication}",$PostfixActiveDirectory,"PostfixActiveDirectoryCNX","{sasl_intro}");

    $form[]=$tpl->field_activedirectory_cnxs("PostfixActiveDirectoryCNX",
        "{active_directory_connection}",$PostfixActiveDirectoryCNX);

    $form[]=$tpl->field_checkbox("PostfixEnableSubmission",
        "{PostfixEnableSubmission}",$PostfixEnableSubmission,false,'{PostfixEnableSubmission_text}');


    $form[]=$tpl->field_section("{auth_mechanism}");
    $form[]=$tpl->field_checkbox("EnableMechPlain","Plain",$EnableMechPlain);
    $form[]=$tpl->field_checkbox("EnableMechLogin","login",$EnableMechLogin);
    $form[]=$tpl->field_checkbox("EnableMechCramMD5","cram-md5",$EnableMechCramMD5);
    $form[]=$tpl->field_checkbox("EnableMechDigestMD5","digest-md5",$EnableMechDigestMD5);


    $html[]=$tpl->form_outside(null,$form,null,"{apply}",$restart,"AsPostfixAdministrator");

    echo $tpl->_ENGINE_parse_body($html);


}
