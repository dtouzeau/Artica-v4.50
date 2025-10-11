<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}	
	
	
	
tabs();



function tabs(){
	$array["members"]='{members}';
	$array["websites"]='{websites}';
	$page=CurrentPageName();
	$font="style='font-size:14px;'";
	foreach ($array as $num=>$ligne){
		$html[]= "<li $font><a href=\"squid.mysar.$num.php\"><span>$ligne</span></a></li>\n";
	}
	
	echo build_artica_tabs($html, "mysar_tabs");
}




