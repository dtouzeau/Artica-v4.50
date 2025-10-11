<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

    if(isset($_GET["popup"])){popup();exit;}
    if(isset($_POST["NetworkAdvancedRouting"])){NetworkAdvancedRouting_save();exit;}
js();

function js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{advanced_routing}","$page?popup=yes",590);

}

function popup(){
    $tpl=new template_admin();
    $NetworkAdvancedRouting=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetworkAdvancedRouting"));
    $NetworkAdvancedRoutingHErmetic=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetworkAdvancedRoutingHErmetic"));

    $form[]=$tpl->field_checkbox("NetworkAdvancedRouting","{enable_advanced_routing}",$NetworkAdvancedRouting,true);

    $form[]=$tpl->field_checkbox("NetworkAdvancedRoutingHErmetic","{NetworkAdvancedRoutingHErmetic}",$NetworkAdvancedRoutingHErmetic);

    $html=$tpl->form_outside("{advanced_routing}",@implode("\n",$form),"{NetworkAdvancedRouting_explain}","{apply}",
        "dialogInstance1.close();LoadAjax('table-loader-iprule','fw.network.routing.php?table=yes');","AsSystemAdministrator");

echo $tpl->_ENGINE_parse_body($html);

}

function NetworkAdvancedRouting_save(){

    if(intval($_POST["NetworkAdvancedRouting"])==0){
        $_POST["NetworkAdvancedRoutingHErmetic"]=0;
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NetworkAdvancedRouting",$_POST["NetworkAdvancedRouting"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NetworkAdvancedRoutingHErmetic",$_POST["NetworkAdvancedRoutingHErmetic"]);
}

