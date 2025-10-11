<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


$users=new usersMenus();
$tpl=new template_admin();
if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();exit();}
if(isset($_GET["popup"])){popup();exit;}
js();


function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog6("{reconfigure} ", "$page?popup=yes",650);
}

function popup(){
    $t=time();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.disable.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.disable.progress.log";
    $ARRAY["CMD"]="squid2.php?restart-upgrade=yes";
    $ARRAY["TITLE"]="{upgrading}";
    $ARRAY["AFTER"]="dialogInstance6.close();LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-php7-$t')";

    $html[]="<div id='progress-php7-$t'></div>";
    $html[]=$tpl->div_error("{NEED_RESTART_SQUID}");
    $html[]="<div style='text-align: center;margin-top:20px;'>";
    $html[]=$tpl->button_autnonome("{perform_upgrade}",$jsrestart,"fas fa-sync-alt","AsProxyMonitor");

    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
}