<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}
js();

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog1("{APP_TRANSPARENT_ROUTING}","$page?popup=yes",650);
}

function popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div id='wizard-transparent-method'>";
    $html[]="<h2 style='margin-bottom:30px'></h2>";
    $html[]=$tpl->div_wizard("{APP_TRANSPARENT_ROUTING}||{proxy_transparent_wizard}");

    $sinstall=$tpl->framework_buildjs("/proxy/wizard/transparent",
        "squid.transparent.progress","squid.transparent.log",
        "wizard-transparent-method","dialogInstance1.close();");

    $installImages=$tpl->button_autnonome("{next}",$sinstall,ico_wizard,"AsSquidAdministrator",400,"btn-primary");
    $html[]="<div style='text-align:right;margin-right:10px'>$installImages</div></div>";
   

    echo $tpl->_ENGINE_parse_body($html);
}
