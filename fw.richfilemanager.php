<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["RichFileManagerListenPort"])){Save();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_RICHFILEMANAGER}</h1>
	<p>{APP_RICHFILEMANAGER_ABOUT}</p>
	</div>

	</div>



	<div class='row'><div id='progress-richfilemanager-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-richfilemanager'></div>

	</div>
	</div>



	<script>
	LoadAjax('table-loader-richfilemanager','$page?table=yes');

	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function table(){

	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	$RichFileManagerListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RichFileManagerListenPort"));
	$RichFileManagerAuthent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RichFileManagerAuthent"));
	if($RichFileManagerListenPort==0){$RichFileManagerListenPort=5000;}

	$NetDataHash[3600]="1 {hour} {memory}: 14.4MB";
	$NetDataHash[21600]="6 {hours} {memory}: 86MB";
	$NetDataHash[86400]="1 {day} {memory}: 345MB";
	$NetDataHash[172800]="2 {days} {memory}: 690MB";
	$NetDataHash[432000]="5 {days} {memory}: 1.7GB";
	
	$MAIN_URI="https://{$_SERVER["SERVER_ADDR"]}:".$RichFileManagerListenPort."/";
	
	$jsafter="LoadAjaxSilent('top-barr','fw-top-bar.php');";
	
	$form[]=$tpl->field_numeric("RichFileManagerListenPort","{listen_port}",$RichFileManagerListenPort);
	$form[]=$tpl->field_checkbox("RichFileManagerAuthent","{enable_authentication} (Manager)",$RichFileManagerAuthent);
	$form[]=$tpl->field_url("{web_interface}",$MAIN_URI);

	
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/RichFileManager.install.prg";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/RichFileManager.install.log";
	$ARRAY["CMD"]="richfilemanager.php?install=yes";
	$ARRAY["TITLE"]="{reconfigure}";
	$ARRAY["AFTER"]="LoadAjax('table-loader-richfilemanager','$page?table=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-richfilemanager-restart')";
	
	$html=$tpl->form_outside("{main_parameters}", @implode("\n", $form),null,"{apply}",$jsrestart,"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	
	
	$sock=new sockets();
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, url_decode_special_tool($value));
	}
	
	
	
	
}