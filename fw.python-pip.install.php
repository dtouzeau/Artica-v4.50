<?php
// Service Pack 9 2026-10-10
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
	// Pentest 2026-11-04 : package etait recopie sans encodage dans la page (XSS).
	// Le titre de modale est rendu en HTML apres decodage des entites : on y retire les caracteres de balisage.
	$packageTitle=preg_replace('/[<>&\\\\]/','',(string)($_GET["package"] ?? ""));
	$packageUrl=req_url($_GET["package"] ?? "");
	$tpl->js_dialog6("{system}::{APP_PYTHON_PIP} {install} $packageTitle", "$page?popup=yes&package=$packageUrl$function",650);
}

function popup(){
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
	if(isset($_GET["function"])){$function=";{$_GET["function"]}()";}
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/python.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/python.progress.log";
	// A pip requirement (name, [extras], version specifier): ASCII only, no space, no shell
	// metacharacter except < > (framework/python.php quotes it with escapeshellarg).
	$package=trim((string)($_GET["package"] ?? ""));
	if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._\-\[\],<>=!~]{0,127}$/',$package)){$package="";}
	if($package===""){return fw_progress_bad_value("package");}
	$ARRAY["CMD"]="python.php?pip-install=".urlencode($package);
	// Pentest 2026-11-04 : package etait recopie sans encodage dans la page (XSS).
	$ARRAY["TITLE"]="{system} {APP_PYTHON_PIP} ".req_html($package);
	$ARRAY["AFTER"]="dialogInstance6.close();LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');$function";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-php7-restart')";
	$html="<div id='progress-php7-restart'></div><script>$jsrestart</script>";
	echo $html;
}