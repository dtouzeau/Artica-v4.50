<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

clean_xss_deep();
xgen();



function xgen(){
	$tpl=new template_admin();
	$f[]="                	<ul class='nav nav-third-level'>";

    $f[] = $tpl->LeftMenu(array(
        "PAGE" => "fw.nginx.sla.php",
        "ICO" => "fas fa-arrow-alt-to-right",
        "TEXT" => "Frontends"
    ));


    $f[] = $tpl->LeftMenu(
        array(
            "PAGE" => "fw.nginx.backends.sla.php",
            "ICO" => "fas fa-arrow-alt-from-left",
            "TEXT" => "Backends")
    );

    $f[] = $tpl->LeftMenu(array(
        "PAGE" => "fw.nginx.slas.events.php",
        "ICO" => ico_eye,
        "TEXT" => "{nginx_sla_events_title}"
    ));

    $f[] = $tpl->LeftMenu(
         array(
             "PAGE" => "fw.nginx.backends.sla.statistics.php",
             "ICO" => ico_statistics,
             "TEXT" => "{statistics} (backends)")
        );


	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}