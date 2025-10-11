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
	$tpl->js_dialog6("{system}::{installing} ", "$page?popup=yes",650);
}


function popup(){
	$t=time();
    $tpl=new template_admin();
    $js3="if(document.getElementById('ad-right-status')){LoadAjax('ad-right-status','fw.proxy.ad.status.php?kerberos-status=yes');}";

    $jsrestart=$tpl->framework_buildjs("services.php?verifpackages=yes",
        "verifpackages.progress","verifpackages.log","progress-php7-$t","dialogInstance6.close();LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');$js3;"
    );
	$html="<div id='progress-php7-$t'></div><script>$jsrestart</script>";
	echo $html;
}