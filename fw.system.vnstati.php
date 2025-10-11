<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["refresh"])){refresh();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog3("{$_GET["interface"]}: {statistics}", "$page?start={$_GET["interface"]}",700);
	
}

function start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $interface=$_GET["start"];
    $cmdr[]="vnstat-$interface-resume.png";
    $cmdr[]="vnstat-$interface-hourly.png";
    $cmdr[]="vnstat-$interface-daily.png";
    $cmdr[]="vnstat-$interface-monthly.png";
    $cmdr[]="vnstat-$interface-top.png";
    $t=time();
    $html=array();
    $html[]="<div class='center' style='margin:-20px'><div id='t$t'></div>";
$t=time();
    foreach ($cmdr as $cmd) {
        $id=str_replace(".png","",$cmd);
        $html[]="<div style='margin:5px'><img src='img/squid/$id.png?$t' id='$id'></div>";
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/vnstat/stats/$interface");

    $html[]="</div>";
    $html[]="<script>";
    $html[]=$tpl->RefreshInterval_Loadjs("t$t",$page,"refresh=$interface",20);
    $html[]="</script>";
    echo @implode("\n", $html);
    return true;
}
function refresh():bool{
    $interface=$_GET["refresh"];
    $cmdr[]="vnstat-$interface-resume.png";
    $cmdr[]="vnstat-$interface-hourly.png";
    $cmdr[]="vnstat-$interface-daily.png";
    $cmdr[]="vnstat-$interface-monthly.png";
    $cmdr[]="vnstat-$interface-top.png";
    $html=array();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/vnstat/stats/$interface");
    foreach ($cmdr as $cmd) {
        $id=str_replace(".png","",$cmd);
        $html[]="var img = document.getElementById('$id');";
        $html[]="if (img) {";
        $html[]="\tvar currentSrc = img.src;";
        $html[]="\timg.src = currentSrc.split('?')[0] + '?v=' + new Date().getTime();";
        $html[]="}";
    }
    header("content-type: application/x-javascript");
    echo @implode("\n", $html);
    return true;
}

