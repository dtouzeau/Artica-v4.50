<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}

js();

function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){$tpl->js_no_privileges();return;}
	$tpl->js_dialog6("{listen_ports} {reconfigure}", "$page?popup=yes");
}

function popup(){
	$id=md5(time().microtime(true));
    $tpl=new template_admin();
	$html[]="<div id='$id'></div>";

    $jsrestart=$tpl->framework_buildjs("/proxy/general/nohup/restart",
        "squid.articarest.nohup","squid.articarest.nohup.log",$id,"LoadAjaxSilent('top-barr','fw-top-bar.php');");

	$html[]="<script>";
	$html[]="$jsrestart";
	$html[]="</script>";
	echo @implode("\n", $html);
}