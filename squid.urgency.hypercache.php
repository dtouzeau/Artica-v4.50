<?php
	if(isset($_GET["verbose"])){
			$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
			$GLOBALS["debug"]=true;ini_set('display_errors', 1);
			ini_set('error_reporting', E_ALL);
			ini_set('error_prepend_string',null);
			ini_set('error_append_string',null);
	}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	$sock=new sockets();
	
	if(!$user->AsSquidAdministrator){
			$tpl=new templates();
			header("content-type: application/x-javascript");
			echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
			die("DIE " .__FILE__." Line: ".__LINE__);
	}	
	
	
	if(isset($_GET["justbutton"])){justbutton_js();exit;}
	if(isset($_GET["popup-justbutton"])){justbutton();exit;}	
	justbutton_js();
	
	
function justbutton_js(){
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{hypercache_in_emergency_mode}");
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	echo "YahooWin3('700','$page?popup-justbutton=yes','$title');";
}
	
function justbutton(){
	$user=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	
	if(!$user->AsSquidAdministrator){echo FATAL_ERROR_SHOW_128('{ERROR_NO_PRIVS}');return;}
	echo $tpl->_ENGINE_parse_body("
			<center style='margin:20px' id='SQUID_URGENCY_FORM_ADM'>
			".button("{disable_emergency_mode}","Loadjs('squid.urgency.hypercache.progress.php')",32)."
			</center>");
}	

