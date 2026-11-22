<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}

js();

function js(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{update_webfiltering_artica_databases}","$page?popup=yes",650);

}

function popup(){
    $tpl=new template_admin();
    // 8e parametre: le privilege exige. Sans lui, framework_buildjs() engendrait
    // le declencheur de mise a jour pour TOUTE session authentifiee -- masquer
    // le bouton d'appel dans fw.dns.filterd.rules.php ne protege rien, l'URL de
    // cette page repondant directement. Avec, la fonction renvoie a la place le
    // rappel "privileges insuffisants" de fw.progress.php.
    $jsrestart=$tpl->framework_buildjs("/category/ufdb/update",
        "artica-webfilterdb.progress","artica-webfilterdb.log","webfdbupdt",
        "dialogInstance2.close();","dialogInstance2.close();",null,"AsDnsAdministrator");

    $html=array();
    $html[]="<div id='webfdbupdt'></div>";
    // Le <script> est concatene APRES l'analyse: framework_buildjs() rend du
    // JavaScript plein d'accolades, et la regex #{(.+?)}# de _ENGINE_parse_body
    // lit chacune comme un {token}.
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html))."<script>$jsrestart</script>";

}



