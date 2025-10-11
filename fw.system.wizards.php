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
    $hostname=$_SERVER["SERVER_NAME"];
    $html[]="<div class=\"wrapper wrapper-content animated fadeInRight\">";

    $html[]=$tpl->widget_wizard("{simple_proxy_and_webfiltering}",
        "{simple_proxy_and_webfiltering_short}","{simple_proxy_and_webfiltering_explain}","wizards.php?create-simple-proxy=yes&domain=$hostname");


    $html[]=$tpl->widget_wizard("{minimalist_gateway}","{minimalist_gateway_short}","{minimalist_gateway_explain}","wizards.php?create-gateway=yes&domain=$hostname");


    $html[]=$tpl->widget_wizard("{FULL_WAF}","{FULL_WAF2}","{FULL_WAF3}","wizards.php?create-waf=yes&domain=$hostname");

     $html[] = $tpl->widget_wizard("{cache_and_bandwidth}", "{cache_and_bandwidth2}", "{cache_and_bandwidth_explain}", "wizards.php?create-cache=yes&domain=$hostname",true);

    $html[]=$tpl->widget_wizard("{APP_DNSFILTERD}","{APP_DNSFILTERD_WIZARD}","{APP_DNSFILTERD_EXPLAIN}","wizards.php?create-dns-filter=yes&domain=$hostname");
    $html[]=$tpl->widget_wizard("{APP_STRONGSWAN}","{APP_STRONGSWAN_WIZARD}","{APP_STRONGSWAN_WIZARD_EXPLAIN}","wizards.php?create-ipsec=yes&domain=$hostname");

    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);



}