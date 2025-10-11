<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.langages.inc');
include_once('ressources/class.sockets.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.privileges.inc');
include_once('ressources/class.ChecksPassword.inc');
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");



$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die("DIE " .__FILE__." Line: ".__LINE__);
}


$data=explode("\n",@file_get_contents("{$GLOBALS["BASEDIR"]}/CALAMARIS"));


foreach ($data as $index=>$line){
	if(preg_match('#<img.*?src="(.+?)"#', $line,$re)){
		$img=$re[1];
		$newimg="ressources/interface-cache/{$img}";
		$data[$index]=str_replace($img, $newimg, $line);
	}
	
}








echo "<div class=calamaris
	style='height:10000px;overflow:auto'

><div style='width:98%' class=form>".@implode("\n", $data)."</div></div>";