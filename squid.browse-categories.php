<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');

$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}

if(isset($_GET["categories"])){page();exit;}
js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text(utf8_encode("{categories}"));
	$t=time();

$html="YahooWinBrowse('850','$page?categories=yes&callback={$_GET["callback"]}','$title');";
echo $html;
}


function page(){
	
	
	echo "<div id='BrowsCatz' style='margin-left:15px'></div>
	<script>
		LoadAjax('BrowsCatz','dansguardian2.databases.php?categories=yes&select=yes&callback={$_GET["callback"]}');
	</script>	
			
	";
	
}



