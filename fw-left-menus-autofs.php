<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$tpl=new template_admin();
if(!$tpl->xPrivs()){die("DIE " .__FILE__." Line: ".__LINE__);}


clean_xss_deep();
xgen();

function xgen():bool{
	$users=new usersMenus();
	$pagename=CurrentPageName();
	$tpl=new template_admin();
	$f[]="                	<ul class='nav nav-third-level'>";

    if($users->AsDebianSystem) {
        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.system.autofs.events.php", "ICO" => ico_eye,
            "TEXT" => "{events}"));
    }
	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
    return true;
}