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
    $HTTP_X_ARTICA_SUBFOLDER="/";
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }

    $f[] = $tpl->LeftMenu(array("PAGE" => "{$HTTP_X_ARTICA_SUBFOLDER}fw.system.hd.php",
        "ICO" => "fa-hdd", "TEXT" => "{partitions}"));
    $f[] = $tpl->LeftMenu(array("PAGE" => "{$HTTP_X_ARTICA_SUBFOLDER}fw.system.metrics.io.php",
        "ICO" => "fas fa-chart-line", "TEXT" => "{io_metrics_title}"));
    $f[] = $tpl->LeftMenu(array("PAGE" => "{$HTTP_X_ARTICA_SUBFOLDER}fw.treesize.php",
        "ICO" => "fas fa-chart-pie", "TEXT" => "{disk_usage}"));



	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}