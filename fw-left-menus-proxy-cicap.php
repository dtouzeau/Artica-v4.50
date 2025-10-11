<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){die("DIE " .__FILE__." Line: ".__LINE__);}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

clean_xss_deep();
xgen();



function xgen(){
	$pagename=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$f[]="                	<ul class='nav nav-third-level'>";
	
	
	
	
	if($users->AsProxyMonitor){
		
		$f[]="                			<li id='left-menu'>";
		$f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.clamav.white.php');\">
														<i class=\"fa fa-thumbs-up\"></i> <span class=\"nav-label\">{whitelist}</span> </a>";
		$f[]="							</li>";		
		
		
		$f[]="                			<li id='left-menu'>";
		$f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.c-icap.updates.php');\">
														<i class=\"".ico_download."\"></i> <span class=\"nav-label\">{updates}</span> </a>";
		$f[]="							</li>";
		
		$f[]="                			<li id='left-menu'>";
		$f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.c-icap.events.php');\">
														<i class=\"fa fa-eye\"></i> <span class=\"nav-label\">{events}</span> </a>";
		$f[]="							</li>";
		
	}
	
	if($users->AsDansGuardianAdministrator){
		$f[]="                			<li id='left-menu'>";
		$f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.c-icap.detections.php');\">
														<i class=\"fa fa-eye\"></i> <span class=\"nav-label\">{detections}</span> </a>";
		$f[]="							</li>";		

	}

	
	
	

	
	
	
	$f[]="					</ul>";

	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}