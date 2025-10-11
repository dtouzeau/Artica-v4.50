<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


$users=new usersMenus();
$tpl=new template_admin();
if(isset($_GET["popup"])){popup();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog6("{categorized_websites}", "$page?popup=yes",650);
}

function popup(){
	$t=time();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $CATEGORIZED_WEBSITES=$GLOBALS["CLASS_SOCKETS"]->CATEGORIZED_WEBSITES();
    $OFFICIALS_CATZT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OFFICIALS_CATZT"));
    $categorized_websites=$tpl->FormatNumber($CATEGORIZED_WEBSITES);
    $sCURVER=$tpl->StringToFonts($categorized_websites,"fa-2x");
    $html[]="<H2 style='margin-bottom:30px'>$sCURVER</H2>";
    if($OFFICIALS_CATZT>0){
        $html[]="<p style='font-size:12px'>{release}:".$tpl->time_to_date($OFFICIALS_CATZT,true)."</p>";
    }

    $html[]=$tpl->div_explain("$categorized_websites||<p style='font-size:16px;margin-top:20px'>{explain_categories_db}</p>");
	echo $tpl->_ENGINE_parse_body($html);
}