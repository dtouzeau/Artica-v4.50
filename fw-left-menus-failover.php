<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$tpl=new template_admin();
if(!$tpl->xPrivs()){die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");}
if(isset($_GET["verbose"])){
    $GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string',null);
    ini_set('error_append_string',null);
}

clean_xss_deep();
xgen();



function xgen(){

	$tpl=new template_admin();
	$f[]="                	<ul class='nav nav-third-level'>";
    $f[]=keepalived();
    $f[]=keepalived_slave();
	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}

//KEEPALIVED
function keepalived(): string{
    $tpl=new template_admin();
    $slaveIsenable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));
    if ($slaveIsenable == 1) {
        return "";
    }
    $APP_KEEPALIVED_ENABLE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE"));
    if ($APP_KEEPALIVED_ENABLE == 0) {
        return "";
    }
    $HTTP_X_ARTICA_SUBFOLDER=articaSubFolder();
    $f[] = "<!-- KEEPALIVED START -->";
    $f[] = $tpl->LeftMenu(
        array("PAGE" => "{$HTTP_X_ARTICA_SUBFOLDER}fw.keepalived.parameters.php",
            "ICO" => "fas fa-tachometer-alt", "TEXT" => "{parameters}")
    );

    $f[] = $tpl->LeftMenu(
        array("PAGE" => "{$HTTP_X_ARTICA_SUBFOLDER}fw.keepalived.instances.php",
            "ICO" => "fas fa-network-wired", "TEXT" => "{services}")
    );

    $f[] = $tpl->LeftMenu(
        array("PAGE" => "{$HTTP_X_ARTICA_SUBFOLDER}fw.keepalived.events.php",
            "ICO" => ico_eye, "TEXT" => "{events}")
    );


    return @implode("\n",$f);
}
//END KEEPALIVED

//KEEPALIVED SLAVE
function keepalived_slave(): string{
    $tpl=new template_admin();
    $masterIsenable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE"));
    if ($masterIsenable == 1) {
        return "";
    }
    $APP_KEEPALIVED_ENABLE = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE"));
    if ($APP_KEEPALIVED_ENABLE == 0) {
        return "";
    }
    $HTTP_X_ARTICA_SUBFOLDER=articaSubFolder();
    $f[] = "<!-- KEEPALIVED START -->";
    $f[] = $tpl->LeftMenu(
        array("PAGE" => "{$HTTP_X_ARTICA_SUBFOLDER}fw.keepalived.slave.status.php",
            "ICO" => ico_dashboard, "TEXT" => "{status}"));

    $f[] = $tpl->LeftMenu(
        array("PAGE" => "{$HTTP_X_ARTICA_SUBFOLDER}fw.keepalived.events.php",
            "ICO" => ico_eye, "TEXT" => "{events}"));

    return @implode("\n", $f);
}

//END KEEPALIVED

function articaSubFolder(){
    $HTTP_X_ARTICA_SUBFOLDER="/";
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }
    if(isset($_SERVER["HTTP_X_META_PREFIX"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_META_PREFIX"]."/";
    }
    return $HTTP_X_ARTICA_SUBFOLDER;
}