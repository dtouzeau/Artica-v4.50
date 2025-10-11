<?php
if(!is_array($argv)){die("No parameters");}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');


$computer=$argv[1];


if(strpos($computer,'$')==0){$uid=$computer.'$';}else{$uid=$computer;}
	$computer=new computers($uid);
	if($computer->ComputerIP=='0.0.0.0'){exit();}
	echo "$computer->ComputerIP\n";
?>