<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["checkdownloads"])){checkdownloads();exit;}


js();

function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog6("{build_support_package}", "$page?popup=yes",580);
}

function popup(){
	
	$tpl=new template_admin();
	$page=CurrentPageName();

    $jsbton= $tpl->framework_buildjs("/system/support/tool/generate",
        "squid.debug.support-tool.progress","squid.debug.support-tool.progress.txt","support-progress-tool",
    "LoadAjax('support-progress-download','$page?checkdownloads=yes');");



	$bton=$tpl->button_autnonome("{build_now}", $jsbton, "fas fa-file-archive");

	
	$html[]="<div id='support-progress-tool'></div>";
	
	$html[]="<div id='support-tool-explain style='margin-top:10px'>
	<div class='alert alert-info'>{build_support_package_explain}</div>
	<center>$bton</center>";
	$html[]="</div>";
	$html[]="<div id='support-progress-download'></div>";
	$html[]="<script>LoadAjax('support-progress-download','$page?checkdownloads=yes');</script>";

	
	echo $tpl->_ENGINE_parse_body($html);
}

function checkdownloads(){
	if(!is_file("ressources/support/support.tar.gz")){return;}
	
		$size=filesize("ressources/support/support.tar.gz");
		$size=FormatBytes($size/1024);
		$date=date("Y-m-d H:i:s",filemtime("ressources/support/support.tar.gz"));
	
		$filedown="
		<center style='margin:15px'>
		<a href='ressources/support/support.tar.gz'>
		<img src='img/file-compressed-128.png' class='img-rounded'>
		</a><br>
		<a href='ressources/support/support.tar.gz'><small>support.tar.gz ($size)</small></a><br>
		<a href='ressources/support/support.tar.gz'><small>$date</small></a>
		</center>
			
		";
	
	echo $filedown;
}