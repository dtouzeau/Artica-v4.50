<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();


if(isset($_GET["popup"])){popup();exit;}


js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $tpl->js_dialog4("{nginx_need_restart}","$page?popup=yes",550);
}

function popup(){
    $tpl=new template_admin();
    $html[]="<div id='nginx-need-restart'></div>";
    $html[]="<script>";
    $service_restart=$tpl->framework_buildjs("nginx:/reverse-proxy/restarthup",
        "nginx.restart.progress","nginx.restart.progress.txt",
        "nginx-need-restart","dialogInstance4.close();");
    $html[]=$service_restart;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}