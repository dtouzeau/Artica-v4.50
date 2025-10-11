<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}

xgen();

function xgen(){
$page=CurrentPageName();
$tpl=new template_admin();

$html="
<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{legal_logs}</h1>
	<p>{legal_logs_explain}</p>	
	</div>
</div>
<div class='row'><div id='progress-firehol-restart'></div>
<div class='ibox-content'>
<div id='table-loader'></div>
</div>
</div>
<script>
LoadAjax('table-loader','fw.proxy.rotate.php?main=yes');

</script>";

$tpl=new template_admin("{your_proxy}:{legal_logs} Proxy Management console",$html);
echo $tpl->build_firewall("choose-proxy=yes");

}