<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_SESSION["uid"])){header("content-type: application/x-javascript");echo "document.location.href='logoff.php'";exit();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}
if(isset($_POST["reboot"])){reboot();exit;}
if(isset($_POST["reset"])){zreset();exit;}
if(isset($_GET["reset"])){reset_js();exit;}


xstart();

function reset_js(){
	$tpl=new template_admin();
	$tpl->js_confirm_execute("{reset_system}", "reset", "yes","document.location.href='logoff.php'");
	
}

function xstart(){
	
	$tpl=new template_admin();
	$tpl->js_confirm_execute("{reboot_system_explain}", "reboot", "yes","document.location.href='logoff.php'");
	
	
}

function reboot(){
	$sock=new sockets();
	$sock->REST_API("/system/reboot");
}


function zreset():bool{
	$sock=new sockets();
	$sock->REST_API("/system/force-reboot");
    return true;
}