<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
$GLOBALS["CLASS_SOCKETS"]       = new sockets();
$users                          = new usersMenus();
$tpl                            = new template_admin();
if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();exit();}
js();


// L'ancien framework lancait "exec.rdpproxy.php --upgrade", un script qui
// n'existe plus : la mise a jour de ReDemPtion passe par le paquet APP_RDPPROXY
// du miroir (aupdate/Installv2), comme tout autre logiciel.
function js(){
	echo "Loadjs('fw.system.upgrade-software.php?product=APP_RDPPROXY');";
}
