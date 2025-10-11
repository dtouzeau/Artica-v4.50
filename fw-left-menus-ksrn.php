<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){die("DIE " .__FILE__." Line: ".__LINE__);}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

clean_xss_deep();
xgen();



function xgen(){
	$tpl=new template_admin();


    $EnableSquidMicroHotSpot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
    $EnableCategoriesCache = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCategoriesCache"));
    $KSRNEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNEnable"));
    $EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
    $MacToUidPHP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MacToUidPHP"));
    $EnableITChart=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableITChart"));
    $EnableGoShieldServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"));
    $EnableGoShieldConnector=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Connector_Enable"));
    $DisplayGoShields=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisplayGoShields"));

    $f[]="                	<ul class='nav nav-third-level'>";

    $f[] = $tpl->LeftMenu(array("PAGE" => "fw.ksrn.threats.php", "ICO" => ico_eye,
        "TEXT" => "{DETECTED_THREATS}"));


	
	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}