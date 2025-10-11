<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["INTERFACES"])){Save();exit;}
if(isset($_GET["status"])){status();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTOPNG_VERSION");
	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_NTOPNG} v$version</h1>
	<p>{enable_ntopng_text}</p>
	</div>

	</div>



	<div class='row'><div id='progress-ntopng-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-ntopng'></div>

	</div>
	</div>



	<script>
	LoadAjax('table-loader-ntopng','$page?table=yes');

	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function table(){

	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/restart-ntopng.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/restart-ntopng.progress.log";
	$ARRAY["CMD"]="ntopng.php?restart=yes";
	$ARRAY["TITLE"]="{restarting}";
	$ARRAY["AFTER"]="LoadAjax('table-loader-ntopng','$page?table=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-ntopng-restart')";
	
	$arrayConf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ntopng")));
	if(!is_numeric($arrayConf["HTTP_PORT"])){$arrayConf["HTTP_PORT"]=3000;}
	if(!is_numeric($arrayConf["ENABLE_LOGIN"])){$arrayConf["ENABLE_LOGIN"]=0;}
	if(!is_numeric($arrayConf["ENABLE_SSL"])){$arrayConf["ENABLE_SSL"]=0;}
	if(!is_numeric($arrayConf["MAX_DAYS"])){$arrayConf["MAX_DAYS"]=30;}
	if(!is_numeric($arrayConf["MAX_SIZE"])){$arrayConf["MAX_SIZE"]=5000;}
	if(!isset($arrayConf["INTERFACE"])){$arrayConf["INTERFACE"]=null;}
	if(!isset($arrayConf["INTERFACES"])){$arrayConf["INTERFACES"]="eth0";}
	
	
	$form[]=$tpl->field_interfaces_choose("INTERFACES", "{monitor_interfaces}", $arrayConf["INTERFACES"]);
	$form[]=$tpl->field_section("{http_engine}");
	$form[]=$tpl->field_checkbox("ENABLE_LOGIN","{enable_login}",$arrayConf["ENABLE_LOGIN"]);
	$settings=$tpl->form_outside("{main_parameters}", @implode("\n", $form),null,"{apply}",$jsrestart,"AsSystemAdministrator");
	
	$html="<table style='width:100%'>
	<tr>
		<td style='width:240px'><div id='ntop-services-status'></div>
		<td style='width:78%'>$settings</td>
	</tr>
	</table>

	
	<script>LoadAjaxTiny('ntop-services-status','$page?status=yes');</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function Save(){
	
	$arrayConf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ntopng")));
	
	foreach ($_POST as $key=>$value){
		$arrayConf[$key]=url_decode_special_tool($value);
	}
	
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($arrayConf)),"ntopng");
}

function status(){
	$sock=new sockets();
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock->getFrameWork("ntopng.php?status=yes");
	$ini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/web/ntopng.status");

	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/restart-ntopng.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/restart-ntopng.progress.log";
	$ARRAY["CMD"]="ntopng.php?restart=yes";
	$ARRAY["TITLE"]="{restarting}";
	$ARRAY["AFTER"]="LoadAjax('table-loader-ntopng','$page?table=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-ntopng-restart')";
	
	
	$html[]=$tpl->SERVICE_STATUS($ini, "APP_NTOPNG",$jsrestart);
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/restart-ntopng.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/restart-ntopng.progress.log";
	$ARRAY["CMD"]="ntopng.php?redis-restart=yes";
	$ARRAY["TITLE"]="{restarting}";
	$ARRAY["AFTER"]="LoadAjax('table-loader-ntopng','$page?table=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-ntopng-restart')";
	
	$html[]=$tpl->SERVICE_STATUS($ini, "APP_REDIS_SERVER",$jsrestart);
	
	echo $tpl->_ENGINE_parse_body(@implode("<p>&nbsp;</p>", $html));
}

