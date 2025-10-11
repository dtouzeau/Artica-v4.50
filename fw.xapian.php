<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_TEMPLATE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["XapianSearchInterface"])){save();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{InstantSearch}</h1>
	<p>{InstantSearch_explain}</p>
	</div>

	</div>



	<div class='row'><div id='progress-xapian-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-xapian-service'></div>

	</div>
	</div>



	<script>
	LoadAjax('table-loader-xapian-service','$page?table=yes');

	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$IPClass=new IP();
	$sock=new sockets();

	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:450px;vertical-align:top'>";
	$html[]="<div id='xapian-status'></div>";
	$html[]="</td>";
	$html[]="<td style='width:100%;vertical-align:top;padding-left:20px'>";

	$XapianSearchPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianSearchPort"));
	$XapianSearchInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianSearchInterface"));
	if($XapianSearchPort==0){$XapianSearchPort=5600;}
	$XapianSearchTitle=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianSearchTitle"));
	if($XapianSearchTitle==null){$XapianSearchTitle="Company Search Engine";}
	$XapianSearchField=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianSearchField"));
	if($XapianSearchField==null){$XapianSearchField="Search something...";}
	$XapianSearchText=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianSearchText"));
	if($XapianSearchText==null){$XapianSearchText="Find any document stored in the company's network";}
	$XapianAllowDownloads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianAllowDownloads"));


	$security="AsSystemAdministrator";

	
	$form[]=$tpl->field_interfaces("XapianSearchInterface", "{listen_interface}", $XapianSearchInterface);
	$form[]=$tpl->field_numeric("XapianSearchPort","{listen_port}",$XapianSearchPort);
	$form[]=$tpl->field_section("{search_page}");
	$form[]=$tpl->field_text("XapianSearchTitle", "{page_title}", $XapianSearchTitle);
	$form[]=$tpl->field_text("XapianSearchText", "{subtitle}", $XapianSearchText);
	$form[]=$tpl->field_text("XapianSearchField", "{field_label}", $XapianSearchField);
	$form[]=$tpl->field_checkbox("XapianAllowDownloads", "{allow_download}", $XapianAllowDownloads,false,"{xapian_allow_download_explain}");
	

	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/xapian.install.prg";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/xapian.install.log";
	$ARRAY["CMD"]="xapian.php?restart=yes";
	$ARRAY["TITLE"]="{reconfigure}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-xapian-restart')";


	$html[]=$tpl->form_outside("{main_parameters}", @implode("\n", $form),null,"{apply}",$jsrestart,$security);
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="<script>LoadAjaxTiny('xapian-status','$page?status=yes');</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function status(){
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new template_admin();

	$page=CurrentPageName();
	$datas=$sock->getFrameWork("xapian.php?status=yes");
	$ini=new Bs_IniHandler(PROGRESS_DIR."/xapian.status");
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/xapian.install.prg";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/xapian.install.log";
	$ARRAY["CMD"]="xapian.php?restart=yes";
	$ARRAY["TITLE"]="{restart_service}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-xapian-restart')";
	echo $tpl->SERVICE_STATUS($ini, "APP_XAPIAN_WEB",$jsrestart);
}

function Save(){


	$sock=new sockets();
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, url_decode_special_tool($value));
	}




}