<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}

js();

function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();exit();}
	$tpl->js_dialog6("{APP_POSTGRES} {restart}", "$page?popup=yes",650);
}
function popup(){
    $tpl=new template_admin();

    $jsrestart=$tpl->framework_buildjs(
        "/postgresql/restart",
        "postgres.progress",
        "postgres.log",
        "progress-postgresql-restart",
        "dialogInstance6.close();LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');"
    );
	
	
	echo "<div id='progress-postgresql-restart' style='margin-top:20px'></div>
	<script>$jsrestart</script>		
	";
}