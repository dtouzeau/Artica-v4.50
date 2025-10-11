<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


$users=new usersMenus();
$tpl=new template_admin();
if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();exit();}
if(isset($_GET["popup"])){popup();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $SnapShotRestaured=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotRestaured"));
    $FILENAME=$SnapShotRestaured["FILENAME"];
	$tpl->js_dialog6("{snapshot}::$FILENAME", "$page?popup=yes",650);
}

function popup(){
	$t=time();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SnapShotRestaured=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotRestaured"));
    $text=$tpl->_ENGINE_parse_body("{snapshot_restored_info}");
    $text=str_replace("%s","<strong>{$SnapShotRestaured["FILENAME"]}</strong>",$text);
	$html[]="<p style='font-size:16px'>$text</p>";
    foreach ($SnapShotRestaured["EVENTS"] as $line){
        $html[]="<div><code>$line</code></div>";

    }


	echo $tpl->_ENGINE_parse_body($html);
}