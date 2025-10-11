<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
$GLOBALS["CLASS_SOCKETS"]       = new sockets();
$users                          = new usersMenus();
$tpl                            = new template_admin();
if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();exit();}
if(isset($_GET["popup"])){popup();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog6("{APP_RDPPROXY}::{installing} ", "$page?popup=yes",650);
}


function popup(){
	$t=time();
    $tpl=new template_admin();
    $jsrestart=$tpl->framework_buildjs("rdpproxy.php?upgrade=yes",
        "squid.rdpproxy.upgrade",
        "squid.rdpproxy.upgrade.log","progress-php7-$t",
        "dialogInstance6.close();LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');");
	$html="<div id='progress-php7-$t'></div><script>$jsrestart</script>";
	echo $html;
}