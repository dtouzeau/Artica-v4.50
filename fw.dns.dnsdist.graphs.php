<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["period"])){period();exit;}
if(isset($_GET["tabs"])){tabs();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $APP_DNSDIST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
    $html=$tpl->page_header("{APP_DNSDIST}&nbsp;&raquo;&nbsp;{requests}",ico_eye,
        "{APP_DNSDIST_EXPLAIN2}","$page?tabs=yes","dnsfw-requests","progress-dnsdist-restart",
        false,"dnsfw-graphs-loader");
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}

	echo $tpl->_ENGINE_parse_body($html);

}
function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $array["{this_hour}"]="$page?period=hourly";
    $array["{today}"]="$page?period=day";
    $array["{yesterday}"]="$page?period=yesterday";
    $array["{this_week}"]="$page?period=week";
    $array["{this_month}"]="$page?period=month";
    $array["{this_year}"]="$page?period=year";
    echo $tpl->tabs_default($array);
    return true;
}

function period():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    $period=$_GET["period"];
    $pic="img/squid/dnsfw-$period.flat.png";
    $pic2="img/squid/dnsfwOut-$period.flat.png";
    $pic3="img/squid/dnsfwHits-$period.flat.png";
    $t=time();
    echo "<div style='margin-top:10px'><img src='$pic?t=$t'></div>";
    echo "<div style='margin-top:10px'><img src='$pic2?t=$t'></div>";
    echo "<div style='margin-top:10px'><img src='$pic3?t=$t'></div>";
    return true;
}




