<?php

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__).'/ressources/class.identity.inc');

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["GOLD"])){SAVE();exit;}
if(isset($_GET["reset-js"])){reset_js();exit;}
if(isset($_POST["reset"])){reset_perform();exit;}

js();

function js(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

    $tpl->js_dialog4("{gold_license}","$page?popup=yes");
}

function reset_js(){
    $tpl        = new template_admin();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/artica.license.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/artica_license.txt";
    $ARRAY["CMD"]="/register/license";
    $ARRAY["TITLE"]="{artica_license}";
    $ARRAY["AFTER"]="dialogInstance4.close();LoadAjax('table-loader-license-service','fw.license.php?table=yes');";

    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=gold');";
    $tpl->js_confirm_execute("{reset} Gold License ?","reset","yes","dialogInstance4.close();$jsrestart");

}
function reset_perform(){
    admin_tracks("The Gold License as been removed.. back to community mode");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_ARTICA_LIC_GOLD","");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("artica.php?LkdPTEQ=yes");

}

function popup(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $time       = time();
    $APP_ARTICA_LIC_GOLD=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_ARTICA_LIC_GOLD");
    if($APP_ARTICA_LIC_GOLD==null){
        if(is_file(LkdPTEQ)){
            $APP_ARTICA_LIC_GOLD=trim(@file_get_contents(LkdPTEQ));
        }
    }


    $form[]=$tpl->field_text("GOLD","{gold_license}",$APP_ARTICA_LIC_GOLD,true);

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/artica.license.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/artica_license.txt";
    $ARRAY["CMD"]="/register/license";
    $ARRAY["TITLE"]="{artica_license}";
    $ARRAY["AFTER"]="dialogInstance4.close();LoadAjax('table-loader-license-service','fw.license.php?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=gold$time');";
    $html[]="<div id='gold$time'></div>";

    if($APP_ARTICA_LIC_GOLD<>null){
        $tpl->form_add_button("{reset}", "Loadjs('$page?reset-js=yes')");
    }

    $html[]=$tpl->form_outside("{license}",$form,"{gold_license_explain}","{apply}",$jsrestart,"AsSystemAdministrator");

echo $tpl->_ENGINE_parse_body($html);
}

function SAVE(){
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    admin_tracks("Saving an Artica Gold License {$_POST["GOLD"]}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_ARTICA_LIC_GOLD",$_POST["GOLD"]);
}