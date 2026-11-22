<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.features.inc");

if(isset($_GET["popup"])){popup();exit;}

js();

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog("{wizards}","$page?popup=yes");
}


function popup(){
    $tpl=new template_admin();
    // The profiles uninstall services: system administrators only (fw.progress.php
    // also checks the PRIV of each task before starting it).
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS2}"));
        return;
    }
    // articarest wizards module (ex framework/wizards.php + exec.wizards.php).
    $html[]="<div class=\"wrapper wrapper-content animated fadeInRight\">";
    $html[]=$tpl->widget_wizard("{simple_proxy_and_webfiltering}",
        "{simple_proxy_and_webfiltering_short}","{simple_proxy_and_webfiltering_explain}","/system/wizards/simple-proxy");
    $html[]=$tpl->widget_wizard("{minimalist_gateway}","{minimalist_gateway_short}","{minimalist_gateway_explain}","/system/wizards/gateway");
    $html[]=$tpl->widget_wizard("{FULL_WAF}","{FULL_WAF2}","{FULL_WAF3}","/system/wizards/waf");
    $html[]=$tpl->widget_wizard("{cache_and_bandwidth}", "{cache_and_bandwidth2}", "{cache_and_bandwidth_explain}", "/system/wizards/cache",true);
    $html[]=$tpl->widget_wizard("{APP_STRONGSWAN}","{APP_STRONGSWAN_WIZARD}","{APP_STRONGSWAN_WIZARD_EXPLAIN}","/system/wizards/ipsec");

    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);



}