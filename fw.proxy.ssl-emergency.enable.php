<?php

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
if(isset($_GET["popup"])){popup();exit;}
start();

function start():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
	if(!$users->AsProxyMonitor){
		$tpl->js_no_privileges();
		exit();
	}
	
	$tpl->js_microdaliog_danger("{enable_emergency_mode} (SSL)","$page?popup=yes");
	return true;
	
}
function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$t=time();
	$js[]="Loadjs('fw.icon.top.php')";
	$js[]="dialogInstance.close()";
	$js[]="LoadAjax('table-acls-ssl-status','fw.proxy.ssl.status.php?table=yes')";
    $js[]="LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');";

    $jsrestart=$tpl->framework_buildjs("/proxy/ssl/emergency/on",
        "squid.ssl.emergency.progress","squid.ssl.emergency.log","proxy-$t",@implode(";", $js));
	
	$html= "<div id='proxy-$t'></div>
	<div class='center' style='margin-top:10px'>". $tpl->button_autnonome("{enable_emergency_mode} (SSL)", $jsrestart, ico_emergency)."</div>	
			
			
	";
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}