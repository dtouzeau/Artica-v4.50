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
	$package=$_GET["pkg"];
	$packageenc=urlencode($package);
	$tpl->js_dialog6("{system}::{installing} ", "$page?popup=yes&pkg=$packageenc",650);
}


function popup(){
	$package=$_GET["pkg"];
	$t=time();

    $js3="if(document.getElementById('ad-right-status')){LoadAjax('ad-right-status','fw.proxy.ad.status.php?kerberos-status=yes');}";


	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/php7install.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/php7install.log";
	$ARRAY["CMD"]="services.php?apt-get-install=$package";
	$ARRAY["TITLE"]="{system} {installing} $package";
	$ARRAY["AFTER"]="dialogInstance6.close();LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');$js3;";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-php7-$t')";

	$html="<div id='progress-php7-$t'></div><script>$jsrestart</script>";
	echo $html;
}