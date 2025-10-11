<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
if(isset($_GET["popup"])){popup();exit;}
start();


function start(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $users      = new usersMenus();
    if(!$users->AsProxyMonitor){
        $tpl->js_no_privileges();
        exit();
    }

    $tpl->js_dialog6("{proxy_is_not_configured_ldap}","$page?popup=yes",650);
}

function popup(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $t          = time();

    $jsrestart=$tpl->framework_buildjs("/proxy/nohup/reconfigure","squid.articarest.nohup","squid.articarest.log",$t,"dialogInstance6.close();");

    $html[]=$tpl->div_error("{proxy_is_not_configured_ldap_explain}");
    $html[]="<div id='$t'></div>";

//<i class="fas fa-download"></i>
    $html[]="<div class='center' style='margin-top:20px'>".$tpl->button_autnonome("{reconfigure_auth_method}",$jsrestart,"fas fa-download","AsProxyMonitor",450)."</div>";

    echo $tpl->_ENGINE_parse_body($html);

}