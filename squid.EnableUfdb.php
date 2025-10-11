<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}

if(isset($_POST["Disable"])){Disable();exit;}


js();


function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	$t=time();
	$html="
			
	function xRefreshDisable$t(){
			Loadjs('ufdbguard.enable.progress.php');
	}
	
		var xEnable$t= function (obj) {
			var res=obj.responseText;
			if (res.length>3){alert(res);}
			setTimeout(\"xRefreshDisable$t()\",2000);
			
		}				
	
	
		
		function Enable$t(){
			
			var XHR = new XHRConnection();
		    XHR.appendData('Disable','yes');
		   	XHR.sendAndLoad('$page', 'POST',xEnable$t);			
		
		}
			
		Enable$t();	
	";
	echo $html;
}

function Disable(){
	$sock=new sockets();
	$sock->SET_INFO("EnableUfdbGuard",1);
	$sock->SET_INFO("EnableUfdbGuard2",1);
}

