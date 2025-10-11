<?php

if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 




if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["build-js"])){buildjs();exit;}
if(isset($_POST["Filllogs"])){Filllogs();exit;}
js();

function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{$_GET["MAC"]} - {$_GET["ipaddr"]}");
	echo "
	YahooWinBrowseHide();
	RTMMail('800','$page?popup=yes&MAC=".urlencode($_GET["MAC"])."&ipaddr=".urlencode($_GET["ipaddr"])."','$title');";


}
function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$restart=null;
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$title=$tpl->_ENGINE_parse_body("{$_GET["MAC"]} - {$_GET["ipaddr"]}");

	$html="
	<center id='title-$t' style='font-size:30px;margin-bottom:20px'>$title</center>
	<textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:98%;height:846px;border:5px solid #8E8E8E;
	overflow:auto;font-size:20px !important' id='text-$t'>".@file_get_contents(PROGRESS_DIR."/nmap_single_progress.results")."</textarea>
	";
	echo $html;
}