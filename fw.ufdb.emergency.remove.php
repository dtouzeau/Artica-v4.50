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
	$tpl->js_microdaliog_danger("{disable_emergency_mode}","$page?popup=yes");
	
	
}
function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$js[]="Loadjs('fw.icon.top.php')";
	$js[]="dialogInstance.close()";
	$js[]="LoadAjax('table-ufdbstatus','fw.ufdb.status.php?table=yes');";
    $js[]="LoadAjax('table-loader-ufdbrules-service','fw.ufdb.rules.php?table=yes');";
	$SquidUFDBUrgencyLastEvents=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUFDBUrgencyLastEvents");

    $jsrestart=$tpl->framework_buildjs("/ufdbclient/emergency/off",
    "ufdb.urgency.disable.progress","ufdb.urgency.disable.progress.txt","proxy-emergency-remove",@implode(";", $js));
	
	$html[]= "<div id='proxy-emergency-remove'></div>";
	if($SquidUFDBUrgencyLastEvents<>null){
        $html[]= $tpl->div_error("{last_error}:<br>$SquidUFDBUrgencyLastEvents");
	}


    $html[]= "<div  style='margin-top:10px' class='center'>". $tpl->button_autnonome("{disable_emergency_mode}", $jsrestart, "fa fa-bell-slash")."</div>";
			

	echo $tpl->_ENGINE_parse_body($html);
}