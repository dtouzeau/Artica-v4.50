<?php
if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 




if(isset($_GET["popup"])){popup();exit;}
js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$restart=null;
	if($_GET["restart"]=="yes"){$restart="&restart=yes"; }
	$title=$tpl->javascript_parse_text("{reconfigure_proxy_service} {failed}");
	echo "YahooWin6('800','$page?popup=yes','$title')";


}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$size=@filesize("/usr/share/artica-postfix/ressources/logs/web/squid-config-failed.tar.gz");
	
	$html="<div class=explain style='font-size:18px'>{squid_bungled_explain}</div>
		<center style='margin:20px'>
			<a href='ressources/logs/web/squid-config-failed.tar.gz' style='font-size:18px;color:black'><img src='img/gz-128.png'>
			<hr>
			<span style='font-size:18px;color:black'>squid-config-failed.tar.gz ". FormatBytes($size/1024)."
			<hr>
			</a>
		</center>
				
		";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}