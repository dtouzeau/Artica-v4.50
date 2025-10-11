<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["patterns"])){patterns();exit;}
if(isset($_POST["ClamavRefreshDaemonMemory"])){save();exit;}
if(isset($_GET["upload-js"])){upload_js();exit;}
if(isset($_GET["upload-popup"])){upload_popup();exit;}
if(isset($_GET["file-uploaded"])){uploaded();exit;}
page();





function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ClamAVDaemonVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonVersion");

    $html=$tpl->page_header("{APP_CLAMAV} $ClamAVDaemonVersion","fab fa-medrt",
        "{APP_CLAMAV_TEXT}","fw.clamav.status.php?patterns=yes","clamavdb","progress-clamav-restart",false,"table-clamav");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: ClamAV Parameters",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
