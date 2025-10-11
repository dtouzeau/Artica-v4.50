<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	
if(isset($_GET["popup"])){popup();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{enable_disable_proxy_service}");
	$html="YahooWin('990','$page?popup=yes','$title')";
	echo $html;

}

function popup(){
	$tpl=new templates();
	$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
	
	if($SQUIDEnable==1){
		$title="{disable_proxy_service}";
		$js="Loadjs('squid.disable.progress.php')";
	}else{
		$title="{enable_proxy_service}";
		$js="Loadjs('squid.enable.progress.php')";		
	}
	
	$html="<div style='font-size:26px'>$title</div>
	<div style='font-size:20px;margin-bottom:50px'>{enable_squid_service_explain2}</div>
	<div style='width:98%' class=form>
		<center style='margin:30px'>". button($title, $js,"35")."</center>
	</div>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

