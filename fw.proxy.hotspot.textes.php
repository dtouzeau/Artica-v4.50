<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

include_once("/usr/share/artica-postfix/ressources/class.wifidog.templates.inc");

$users=new usersMenus();if(!$users->AsSquidAdministrator){$users->pageDie();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["MainTitle"])){Save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}

page();


function page(){
    $tpl=new template_admin();
    $sock=new wifidog_templates();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/articaweb/chown");
    $form[]=$tpl->field_textarea("MainTitle","{title2}",$sock->MainTitle,"100%","50px");
    $form[]=$tpl->field_textarea("LoginTitle","Login {title2}",$sock->LoginTitle,"100%","50px");
    $form[]=$tpl->field_textarea("LabelUsername","{label} {username}",$sock->LabelUsername,"100%","50px");
    $form[]=$tpl->field_textarea("LabelPassword","{label} {password}",$sock->LabelPassword,"100%","50px");
    $form[]=$tpl->field_textarea("WelcomeMessage","{welcome_message}",$sock->WelcomeMessage,"100%","250px");
    $form[]=$tpl->field_textarea("SubWelcome","{welcome_message} (2)",$sock->SubWelcome,"100%","250px");
    $form[]=$tpl->field_textarea("SuccessTitle","{success} {title2}",$sock->SuccessTitle,"100%","250px");
    $form[]=$tpl->field_textarea("SuccessWelcome","{welcome_message} {success}",$sock->SuccessWelcome,"100%","250px");



    $form[]=$tpl->field_section("Active Directory");
    $form[]=$tpl->field_textarea("DomainAccount","{label} {username} (Active Directory)",$sock->DomainAccount,"100%","50px");
    $form[]=$tpl->field_textarea("WelcomeMessageActiveDirectory","{welcome_message} (Active Directory)",$sock->WelcomeMessageActiveDirectory,"100%","250px");

    $form[]=$tpl->field_section("{vouchers_mananger}");
    $form[]=$tpl->field_textarea("LabelVoucher","{label} {username}",$sock->LabelVoucher,"100%","50px");
    $form[]=$tpl->field_textarea("VoucherExplain","{welcome_message}",$sock->VoucherExplain,"100%","250px");
    $form[]=$tpl->field_textarea("VoucherDevice","{lock} {computer}",$sock->VoucherDevice,"100%","250px");



    $form[]=$tpl->field_section("{register}");
    $form[]=$tpl->field_textarea("RegisterTitle","{register} {title2}",$sock->RegisterTitle,"100%","50px");
    $form[]=$tpl->field_textarea("RegisterButton","{register} {button}",$sock->RegisterButton,"100%","50px");
    $form[]=$tpl->field_textarea("REGISTER_MESSAGE_EXPLAIN","{register_explain}",$sock->REGISTER_MESSAGE_EXPLAIN,"100%","250px");
    $form[]=$tpl->field_textarea("REGISTER_MESSAGE_SUCCESS","{smtp_register_message_success}",$sock->REGISTER_MESSAGE_SUCCESS);

    $form[]=$tpl->field_textarea("REGISTER_MESSAGE_TIMEOUT","{register_explain} {timeout}",$sock->REGISTER_MESSAGE_TIMEOUT);




    $form[]=$tpl->field_textarea("LabelEmail","{label} {email}",$sock->LabelEmail,"100%","50px");
    $form[]=$tpl->field_textarea("ErrorInvalidMail","{error_email_invalid}",$sock->ErrorInvalidMail,"100%","50px");
    $form[]=$tpl->field_textarea("REGISTER_SUBJECT","{message}: {subject}",$sock->REGISTER_SUBJECT,"100%","50px");
    $form[]=$tpl->field_textarea("REGISTER_MESSAGE","{message}: {body}",$sock->REGISTER_MESSAGE,"100%","50px");






    $form[]=$tpl->field_section("{button}");
    $form[]=$tpl->field_textarea("LabelConfirm","{label} {confirm}",$sock->LabelConfirm,"100%","50px");
    $form[]=$tpl->field_textarea("ConnectionButton","{connection} {button}",$sock->ConnectionButton,"100%","50px");
    $form[]=$tpl->field_textarea("AcceptButton","{accept} {button}",$sock->AcceptButton,"100%","50px");
    $form[]=$tpl->field_textarea("SubmitButton","{submit} {button}",$sock->SubmitButton,"100%","50px");

    $form[]=$tpl->field_section("{Terms_Conditions}");
    $form[]=$tpl->field_textarea("TERMS_TITLE","{title2}",$sock->TERMS_TITLE,"100%","250px");
    $form[]=$tpl->field_textarea("TERMS_EXPLAIN","{Terms_Conditions_explain}",$sock->TERMS_EXPLAIN,"100%","250px");
    $form[]=$tpl->field_textarea("TERMS_CONDITIONS","{Terms_Conditions}",$sock->TERMS_CONDITIONS,"100%","250px");

    $form[]=$tpl->field_section("{errors}");
    $form[]=$tpl->field_textarea("authentication_failed","{authentication_failed}",$sock->authentication_failed,"100%","50px");
    $form[]=$tpl->field_textarea("ErrorThisAccountExists","{this_account_already_exists}",$sock->ErrorThisAccountExists,"100%","50px");
    $form[]=$tpl->field_textarea("PasswordMismatch","{password_mismatch}",$sock->PasswordMismatch,"100%","50px");
    $form[]=$tpl->field_textarea("SessionExpired","{session_expired}",$sock->SessionExpired,"100%","50px");

    $form[]=$tpl->field_textarea("ArticaSplashHotSpotRedirectText","{ArticaSplashHotSpotRedirectText}",$sock->ArticaSplashHotSpotRedirectText,"100%","250px");





    $form[]=$tpl->field_section("{template} {options}");
    $form[]=$tpl->field_textarea("FORM_HEAD","{header_form}",$sock->FORM_HEAD,"100%","250px");
    $form[]=$tpl->field_textarea("FooterText","{footer_text}",$sock->FooterText,"100%","250px");
    $form[]=$tpl->field_color("DivLeftColor","{font_color} {left} ($sock->DivLeftColor)",$sock->DivLeftColor);
    $form[]=$tpl->field_color("DivLeftBGColor","{background_color} {left} ($sock->DivLeftBGColor)",$sock->DivLeftBGColor);

    $form[]=$tpl->field_color("DivRigthColor","{font_color} {right} ($sock->DivRigthColor)",$sock->DivRigthColor);
    $form[]=$tpl->field_color("DivRigthBGColor","{background_color} {right} ($sock->DivRigthBGColor)",$sock->DivRigthBGColor);
    $form[]=$tpl->field_color("DivRigthButtonColor","{button_color} {right} ($sock->DivRigthButtonColor)",$sock->DivRigthButtonColor);
    $form[]=$tpl->field_color("DivRigthButtonTextColor","{button} {font_color} {right} ($sock->DivRigthButtonTextColor)",$sock->DivRigthButtonTextColor);
    $form[]=$tpl->field_color("DivRigthButtonHoverColor","{button_color} {right} (hover)($sock->DivRigthButtonHoverColor)",$sock->DivRigthButtonHoverColor);
    $form[]=$tpl->field_upload_image("DivBackgroundImage","{background_image}",$sock->DivBackgroundImage);
    $form[]=$tpl->field_checkbox("HotSpotBackToDefaults","{back_to_defaults}");


    $jsrestart = $tpl->framework_buildjs("/proxy/hotspot/install",
        "hotspot-web.progress",
        "hotspot-web.log",
        "progress-hotspot-restart");

    $HotSpotListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenPort"));
    $Host=$_SERVER["SERVER_ADDR"];
    $REMOTE_ADDR=$_SERVER["REMOTE_ADDR"];
    if(strpos($Host,":")>0){
        $tb=explode(":",$Host);
        $Host=$tb[0];
    }

    if($HotSpotListenPort==0){$HotSpotListenPort=8025;}


    echo $tpl->form_outside(null,$form,null,"{apply}",$jsrestart,"AsHotSpotManager",true);

    $uri="s_PopUpFull('http://$Host:$HotSpotListenPort/hotspot.php?info=eyJNQUMiOiIiLCJTUkMiOiIxOTIuMTY4LjEuMjQ4IiwiRU1BSUwiOiIiLCJLRVkiOiIxOTIuMTY4LjEuMjQ4In0=&ip=$REMOTE_ADDR&user=&url=http://www.google.com/',1024,768,'Monitor');";

    $topbuttons[] = array($uri, ico_loupe, "{view}:{portal_page}");
    $topbuttons[] = array($jsrestart, ico_refresh, "{reconfigure}");




    $TINY_ARRAY["TITLE"]="{web_portal_authentication}: {skins}";
    $TINY_ARRAY["ICO"]="fad fa-pencil-alt";
    $TINY_ARRAY["EXPL"]="{hotspot_skin_explain}";
    $TINY_ARRAY["URL"]="hotspot-config";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<script>$jstiny</script>";


}

function Save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $SockTemplate=new wifidog_settings();

    if($_POST["HotSpotBackToDefaults"]==1){
        unset($_POST["HotSpotBackToDefaults"]);
        foreach ($_POST as $key=>$value) {
            $_POST[$key]="";

        }
    }

    foreach ($_POST as $key=>$value) {
        $value=utf8_encode_switch($value);
        $SockTemplate->SET_INFO($key,$value);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
    return admin_tracks("Saving HotSpot skins");
}
function utf8_encode_switch($string):string{
    if(is_null($string)){
        return "";
    }
    if(PHP_MAJOR_VERSION>7) {
        return $string;
    }
    $tpl=new template_admin();
    return $tpl->utf8_encode($string);

}