<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){die("DIE " .__FILE__." Line: ".__LINE__);}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

clean_xss_deep();
xgen();



function xgen(){
	$tpl=new template_admin();
	$f[]="                	<ul class='nav nav-third-level'>";

    $f[] = $tpl->LeftMenu(
         array(
             "PAGE" => "fw.nginx.statistics.status.php",
             "ICO" => ico_dashboard,
             "TEXT" => "{dashboard}")
        );

    $f[] = $tpl->LeftMenu(
        array(
            "PAGE" => "fw.nginx.statistics.fingerprints.php",
            "ICO" => "fas fa-fingerprint",
            "TEXT" => "{fingerprints}")
    );


    $f[] = $tpl->LeftMenu(
        array(
            "PAGE" => "fw.nginx.statistics.bots.php",
            "ICO" => "fas fa-robot",
            "TEXT" => "Botnets")
    );

    $f[] = $tpl->LeftMenu(
        array(
            "PAGE" => "fw.nginx.statistics.hosts.php",
            "ICO" => ico_computer,
            "TEXT" => "{client_source_ip_address}")
    );

	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}