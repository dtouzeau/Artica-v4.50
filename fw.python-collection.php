<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


$users=new usersMenus();
$tpl=new template_admin();
if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();exit();}
if(isset($_GET["popup"])){popup();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$function=null;
	if(isset($_GET["function"])){$function="&function={$_GET["function"]}";}
	$tpl->js_dialog6("{system}::Python {building_collection}", "$page?popup=yes$function",650);
}

function popup(){
	$t=time();
	$function=null;
	if(isset($_GET["function"])){$function=";{$_GET["function"]}()";}
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/python.collection.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/python.collection.progress.log";
	$ARRAY["CMD"]="python.php?collection=yes";
	$ARRAY["TITLE"]="Python";
	$ARRAY["AFTER"]="dialogInstance6.close();LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');$function";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-$t-restart')";
	$html="<div id='progress-$t-restart'></div><script>$jsrestart</script>";
	echo $html;
}