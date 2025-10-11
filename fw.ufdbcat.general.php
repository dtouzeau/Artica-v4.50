<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["UfdbCatThreads"])){save();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_UFDBCAT}: {general_settings}</h1>
	</div>

	</div>



	<div class='row'><div id='progress-ufdbcat-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-squid-service'></div>

	</div>
	</div>



	<script>
	LoadAjax('table-loader-squid-service','$page?table=yes');

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
	$users=new usersMenus();
	$ufdbCatLocalInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbCatLocalInterface");
	$UfdbcatEnableArticaDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbcatEnableArticaDB"));
	$UfdbcatEnableUToulouse=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbcatEnableUToulouse"));
	$Threads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatThreads"));
	
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdbcat.restart.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ufdbcat.restart.log";
	$ARRAY["CMD"]="ufdbguard.php?ufdbcat-restart-progress=yes";
	$ARRAY["TITLE"]="{restarting_service}";
	$ARRAY["AFTER"]="LoadAjax('table-loader-squid-service','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-ufdbcat-restart');";
	
	$security="AsSquidAdministrator";
	

	$form[]=$tpl->field_numeric("UfdbCatThreads", "Threads",$Threads,false);
	if($users->CORP_LICENSE){$form[]=$tpl->field_checkbox("UfdbcatEnableArticaDB","{artica_databases}",$UfdbcatEnableArticaDB,false);}
	$form[]=$tpl->field_checkbox("UfdbcatEnableUToulouse","{free_databases}",$UfdbcatEnableUToulouse,false);
	$html[]=$tpl->form_outside("{general_settings}", @implode("\n", $form),null,"{apply}",$jsrestart,$security);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function save(){
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new template_admin();
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$_POST["UfdbcatEnableArticaDB"]=0;}
	$tpl->SAVE_POSTs();
}
