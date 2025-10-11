<?php
if(isset($_POST["none"])){die();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

$users=new usersMenus();
$tpl=new template_admin();
if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();exit();}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["js2"])){js2();exit;}

js();

function js():bool{
    $users=new usersMenus();
    $tpl=new template_admin();
    if(!$users->AsSystemAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog_confirm_action("{error_systemd_reboot}","none","none","Loadjs('$page?js2=yes')");

    return true;
}

function js2():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    admin_tracks("Confirmed uninstalling systemd package...");
	$tpl->js_dialog6("{system}::{error_systemd_installed} ", "$page?popup=yes",650);
	return true;
}


function popup():bool{
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/systemd.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/systemd.log";
	$ARRAY["CMD"]="aptget.php?apt-get-systemd=yes";
	$ARRAY["TITLE"]="{system} {upgrade} systemd";
	$ARRAY["AFTER"]="dialogInstance6.close();LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');Loadjs('fw.system.restart.php');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-php7-systemd')";
	$html="<div id='progress-php7-systemd'></div><script>$jsrestart</script>";
	echo $html;
	return true;
}