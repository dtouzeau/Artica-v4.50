<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["DEBUG"]=false;;
$GLOBALS["FORCE"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");


   
$sock=new sockets();
$unix=new unix();
$chmod=$unix->find_program("chmod");
$NoInternetAccess=intval($sock->GET_INFO("NoInternetAccess"));
if($NoInternetAccess==1){die();}


$LinuxDistributionFullName = $sock->GET_INFO("LinuxDistributionFullName");
if($LinuxDistributionFullName==null){ $LinuxDistributionFullName="Linux default";}
$users=new usersMenus();
shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.dmidecode.php --chassis --force");
$datas=trim(@file_get_contents("/etc/artica-postfix/dmidecode.cache.url"));  
$SYSTEMID=$sock->GET_INFO("SYSTEMID");
$MEMORY_INSTALLED=$unix->TOTAL_MEMORY_MB();
$SystemCpuNumber=$sock->GET_INFO("SystemCpuNumber");
$ARTICA_VERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
$hostname=$users->hostname;
$UsersNumber=@file_get_contents("/etc/artica-postfix/UsersNumber");
if(!is_numeric($UsersNumber)){$UsersNumber=0;}
$uriplus="$SYSTEMID;$MEMORY_INSTALLED;$SystemCpuNumber;$LinuxDistributionFullName;$ARTICA_VERSION;$hostname;$UsersNumber;$datas";
$uriplus=urlencode($uriplus);
$ini=new Bs_IniHandler();
$ini->loadFile("/etc/artica-postfix/artica-update.conf");
$uri=$ini->get("AUTOUPDATE","uri");
if(trim($uri)==null){$uri="http://93.88.245.88/auto.update.php";}
$unix=new unix();
$URIBASE=$unix->MAIN_URI();

$uri=str_replace($URIBASE, "93.88.245.88", $uri);
$localFile='/usr/share/artica-postfix/ressources/index.ini';
$curl=new ccurl("$uri?datas=$uriplus");

$tmpfile="/tmp/artica.".basename(__FILE__).'.tmp';
@unlink("/usr/share/artica-postfix/ressources/logs/INTERNET_FAILED");
	
	


$ini=new Bs_IniHandler();
$ini->loadFile("$tmpfile");
$articaversion=$ini->get("NEXT","artica");
if($GLOBALS["VERBOSE"]){echo "Artica version:$articaversion\n";}
if(preg_match("#^[0-9\.]+#", $articaversion)){
	@copy($tmpfile, "/usr/share/artica-postfix/ressources/index.ini");
	shell_exec("$chmod 777 /usr/share/artica-postfix/ressources/index.ini");
}

	

