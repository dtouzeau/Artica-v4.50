<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["after-js"])){after_js();exit;}
if(isset($_GET["popup"])){popup();exit;}
js();

function js(){
    $tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		header("content-type: application/x-javascript");
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		exit();
	}
	
	
	$tpl->js_dialog_modal("{please_wait_building_network}", "$page?popup=yes",650);
	
	
}


function popup(){
	$tpl=new template_admin();

	$html[]="<div id='progress-network-restart'></div>";
    $jsrestart=$tpl->framework_buildjs("/system/network/reconfigure-restart",
        "reconfigure-newtork.progress",
        "exec.virtuals-ip.php.html","progress-network-restart",
        "DialogModal.close();LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');","DialogModal.close();"
    ); // apply_network_configuration
	
	$html[]="<script>$jsrestart</script>";
	
	echo @implode("\n", $html);
	
	
}

function after_js(){
	header("content-type: application/x-javascript");
	$h[]="if(document.getElementById('route-dump-fields') ){ RouteDumpFields();}";
	echo @implode("\n", $h);
}