<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}

js();


function js(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();

    $tpl->js_dialog_confirm("{apply_configuration_ports}","$page?popup=yes");




}


function popup(){
    $tpl=new template_admin();
    $t=time();

    $after[]="if( document.getElementById('proxy-transparent-ports') ){LoadAjax('proxy-transparent-ports','fw.proxy.transparent.php?table=yes');}";

    $after[]="if( document.getElementById('table-connected-proxy-ports') ){LoadAjax('table-connected-proxy-ports','fw.proxy.ports.php?connected-ports-list=yes');}";

    $after[]="DialogConfirm.close()";

//global-ports-center

    $jsrestart=$tpl->framework_buildjs("/proxy/general/nohup/restart",
    "squid.articarest.nohup","squid.articarest.nohup.log","apply-configuration-$t",implode(";",$after));

    $html[]="<div id='apply-configuration-$t'>";
    $html[]=$tpl->div_error("{WARN_OPE_RESTART_SQUID_ASK}");
    $html[]="<div style='margin-top:10px;text-align:right'>".$tpl->button_autnonome("{continue}",$jsrestart,"fas fa-shield-check")."</div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);

}