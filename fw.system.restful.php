<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["SystemRESTFulAPIKey"])){Save();exit;}
if(isset($_GET["glances-status"])){status();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	
	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GLANCES_VERSION");
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	$users=new usersMenus();

	$html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{SYSTEM_RESTFULL}</h1><p>{SYSTEM_RESTFULL_EXPLAIN}</p></div>
	</div>
	<div class='row'>
	<div id='progress-glances-restart'></div>
	";



	$html[]="
</div><div class='row'><div class='ibox-content'>
";

	$html[]="


	<div id='table-loader-glances-pages'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/system-restful');
	LoadAjax('table-loader-glances-pages','$page?table=yes');
	</script>";

	if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	
	$SystemRESTFulAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemRESTFulAPIKey"));
	
	
	$form[]=$tpl->field_text("SystemRESTFulAPIKey", "{API_KEY}", $SystemRESTFulAPIKey);
	
	$myform=$tpl->form_outside("RESTful API KEY", $form,null,"{apply}",null,"AsSystemAdministrator");
	
//restart_service_each
	$html="<table style='width:100%'>
	<tr>
	<td	style='vertical-align:top;width:90%'>$myform</td>
	</tr>
	</table>
	<script></script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function status(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	
	$sock=new sockets();
	$sock->getFrameWork("glances.php?status=yes");
	$bsini=new Bs_IniHandler(PROGRESS_DIR."/glances.status");
	$tpl=new template_admin();
	$page=CurrentPageName();
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/restart-glances.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/restart-glances.progress.log";
	$ARRAY["CMD"]="glances.php?restart=yes";
	$ARRAY["TITLE"]="{APP_GLANCES} {restarting_service}";
	$ARRAY["AFTER"]="LoadAjaxSilent('glances-status','$page?glances-status=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-glances-restart')";
	echo $tpl->SERVICE_STATUS($bsini, "APP_GLANCES",$jsRestart);
}

function Save(){
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();

}
