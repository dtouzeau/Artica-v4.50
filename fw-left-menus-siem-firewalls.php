<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

clean_xss_deep();
xgen();



function xgen(){
	$tpl=new template_admin();

    $f[]="                	<ul class='nav nav-third-level'>";

    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.firewall.events.php", "ICO" => ico_eye,
        "TEXT" => "{events}"));

    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.statistics.firewall.denied.php",
        "ICO" => "fas fa-ban",
        "TEXT" => "{blocked_packets}"));

    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.firewall.sources.php",
        "ICO" => "fas fa-network-wired",
        "TEXT" => "{firewall_sources}"));

    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.firewall.rules.php",
        "ICO" => "fas fa-filter",
        "TEXT" => "{firewall_rules}"));

    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.firewall.alerts.php",
        "ICO" => "fas fa-bell",
        "TEXT" => "{firewall_alerts}"));
	
	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}