<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.blackboxes.inc');
	include_once('ressources/class.nodes.squid.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	js();
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{configuration_file}");
	
	$html="
		YahooWin4('700','$page?popup=yes&nodeid={$_GET["nodeid"]}','$title');
	";
	echo $html;
	
	
}


function popup(){
	
	$black=new blackboxes($_GET["nodeid"]);
	$datas=$black->etcsquidconf;
	
	
	$html="<textarea style='width:100%;height:450px;overflow:auto;border:1px solid #CCCCCC;font-size:12px;padding:3px'>$datas</textarea>";
	echo $html;
	
}

?>