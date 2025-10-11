<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["prefer_direct"])){Save();exit;}
if(isset($_GET["flat"])){flat();exit;}
if(isset($_GET["table-flat"])){table_flat();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["param-js"])){param_js();exit;}
if(isset($_GET["params-popup"])){param_popup();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{parent_proxies} {parameters}",
        "fas fa-cogs","{parent_proxies_status_explain}","$page?table=yes",
        "progress-squid-parent-restart","progress-squid-parent-restart",false,"table-loader-progress-squid-parent-restart");


	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall("{parent_proxies} {parameters}");
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function param_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{general_settings}","$page?params-popup=yes",650);
}
function flat():bool{
    $page=CurrentPageName();
    echo "<div id='proxy-parents-param-flat'></div>";
    echo "<script>LoadAjaxSilent('proxy-parents-param-flat','$page?table-flat=yes');</script>";
    return true;
}
function table_flat():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $prefer_direct=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("prefer_direct"));
    $nonhierarchical_direct=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nonhierarchical_direct"));
    $forwarded_for=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("forwarded_for"));
    if($forwarded_for==null){$forwarded_for="on";}


    $tpl->table_form_field_js("Loadjs('$page?param-js=yes')","AsSquidAdministrator");
    $tpl->table_form_section("{general_settings}");
    $tpl->table_form_field_bool("{prefer_direct}",$prefer_direct,ico_arrow_right);
    $tpl->table_form_field_bool("{nonhierarchical_direct}",$nonhierarchical_direct,ico_arrow_right);
    $arrayParams["on"]="{enabled}";
    $arrayParams["off"]="{disabled}";
    $arrayParams["transparent"]="{transparent}";
    $arrayParams["delete"]="{anonymous}";
    $arrayParams["truncate"]="{hide}";

    $TINY_ARRAY["TITLE"]="{parent_proxies} {parameters}";
    $TINY_ARRAY["ICO"]="fas fa-cogs";
    $TINY_ARRAY["EXPL"]="{parent_proxies_status_explain}";
    $TINY_ARRAY["URL"]="proxy-parents-status";
    $TINY_ARRAY["BUTTONS"]="";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $tpl->table_form_field_text("X-Forwarded-For",$arrayParams[$forwarded_for],ico_params);
    echo $tpl->table_form_compile();
    echo "<script>$jstiny</script>";
    return true;

}

function param_popup(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$prefer_direct=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("prefer_direct"));
	$nonhierarchical_direct=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nonhierarchical_direct"));
	$forwarded_for=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("forwarded_for"));
	if($forwarded_for==null){$forwarded_for="on";}
	$form[]=$tpl->field_checkbox("prefer_direct","{prefer_direct}",$prefer_direct,false,"{squid_prefer_direct}");
	$form[]=$tpl->field_checkbox("nonhierarchical_direct","{nonhierarchical_direct}",$nonhierarchical_direct,false,"{squid_nonhierarchical_direct}");

		
	$arrayParams["on"]="{enabled}";
	$arrayParams["off"]="{disabled}";
	$arrayParams["transparent"]="{transparent}";
	$arrayParams["delete"]="{anonymous}";
	$arrayParams["truncate"]="{hide}";
	$form[]=$tpl->field_array_hash($arrayParams, "forwarded_for", "X-Forwarded-For", $forwarded_for,false,"{x-Forwarded-For_explain}");

	$security="AsSquidAdministrator";

    $jsrestart=$tpl->framework_buildjs("/proxy/parents/compile",
    "squid.access.center.progress","squid.access.center.progress.log","progress-squid-parent-restart");

    $f[]="LoadAjaxSilent('proxy-parents-param-flat','$page?table-flat=yes');";
    $f[]="dialogInstance2.close()";
    $f[]=$jsrestart;
	
	$html[]=$tpl->form_outside("",$form,null,"{apply}",implode("\n",$f),$security);
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
}