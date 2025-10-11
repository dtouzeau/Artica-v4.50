<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
if(isset($_GET["popup"])){popup();exit;}
start();

function start(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
	if(!$users->AsProxyMonitor){
		$tpl->js_no_privileges();
		exit();
	}
	$tpl->js_microdaliog_danger("{nginx_reconfiguration_needed}","$page?popup=yes");
	
	
}
function reconfigure($divid):string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("nginx.php?upgrade1=yes","nginx.reconfigure.progress",
    "nginx.reconfigure.progress.txt",$divid,"dialogInstance.close();");


}


function popup(){
	$tpl=new template_admin();
	$divid="div".time();
	$reconfigure=reconfigure($divid);
    $bt=$tpl->button_autnonome("{start_upgrade_procedure}",
        "$reconfigure", ico_play,"AsWebMaster",350,"btn-primary");

    $html[]="<div id='$divid'></div>";
	$html[]= $tpl->div_explain("{nginx_reconfiguration_needed_explain}");
    $html[]="<div style='margin:50px'><center>$bt</center></div>";
	echo $tpl->_ENGINE_parse_body($html);
}