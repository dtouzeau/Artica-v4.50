<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}

xgen();

function xgen(){
	$page=CurrentPageName();
	$tpl=new template_admin();


	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{cache_level}</h1>
	<p>{cache_level_explain}</p>
	</div>

	</div>



	<div class='row' style='min-height:1200px'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-cache-level'></div>

	</div>
	</div>



	<script>
	LoadAjax('table-loader-cache-level','fw.proxy.cache.level.php?table=yes');

	</script>";

$tpl=new template_admin("{your_proxy}:{cache_level}",$html);
echo $tpl->build_firewall("choose-proxy=yes");

}