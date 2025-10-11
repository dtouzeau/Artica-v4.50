<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){die("DIE " .__FILE__." Line: ".__LINE__);}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

clean_xss_deep();
xgen();

function xgen(){
	$users=new usersMenus();
	$tpl=new template_admin();
	$f[]="                	<ul class='nav nav-third-level'>";

    if($users->AsDebianSystem) {
        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.versions.php", "ICO" => "fa-info",
            "TEXT" => "{packages_center}"));
    }
    if($users->AsProxyMonitor) {
        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.global.proxy.php", "ICO" => "fas fa-globe",
            "TEXT" => "{internet_access}"));
    }
    if($users->AsDebianSystem) {
        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.apt.mirror.php", "ICO" => "fab fa-linux",
            "TEXT" => "{APP_APT_MIRROR}"));

     // $f[] = $tpl->LeftMenu(array("PAGE" => "fw.artica.mirror.php", "ICO" => ico_download,"TEXT" => "{repositories} (Artica)"));
    }
	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}