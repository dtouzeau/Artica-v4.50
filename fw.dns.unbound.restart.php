<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
js();


function js(){
	$tpl=new template_admin();
	$users=new usersMenus();
	$page=CurrentPageName();
	if(!$users->AsDnsAdministrator){$tpl->js_no_privileges();return;}
	$tpl->js_dialog1("modal:{restart_service}", "$page?popup=yes");
	
	
}

function popup():bool{
	$tpl=new template_admin();
	
	$html[]="<div id='unbound-dedicated-progress'></div>";

    $jsRestart=$tpl->framework_buildjs("/unbound/restart",
        "unbound.restart.progress","unbound.restart.log",
        "unbound-dedicated-progress","LoadAjaxSilent('unbound-status','fw.dns.unbound.php?unbound-status=yes')");


    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));

    if($EnableDNSDist==1) {
        $jsRestart = $tpl->framework_buildjs("/dnsfw/service/php/restart",
            "dnsdist.restart", "dnsdist.restart.log",
            "unbound-dedicated-progress",
            "LoadAjaxSilent('unbound-status','fw.dns.unbound.php?unbound-status=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');dialogInstance1.close();"
        );
    }
    $html[]="<script>$jsRestart</script>";
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}