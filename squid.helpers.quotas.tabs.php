<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.ActiveDirectory.inc');
include_once(dirname(__FILE__).'/ressources/class.external.ldap.inc');



$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die("DIE " .__FILE__." Line: ".__LINE__);
}

tabs();

function tabs(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	
	$fontsize=18;
	$array["acls-time"]='{time_quotas}';
	$array["acls-size"]='{size_quotas}';
	$t=time();
	foreach ($array as $num=>$ligne){


		if($num=="acls-time"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.helpers.quotas.time.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;

		}
		if($num=="acls-size"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.helpers.quotas.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		
		}

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}

	$html=build_artica_tabs($html,'main_dansguardian_tabs',1150)."<script>LeftDesign('webfiltering-white-256-opac20.png');</script>";
	echo $html;
}