<?php
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$postfix_package="postfixp-2.12-20140321-x64-tar.gz";

shell_exec("clear");

$unix=new unix();
$ini=new Bs_IniHandler();

$curl=new ccurl("http://www.artica.fr/auto.update.php");
$curl->NoHTTP_POST=true;
if(!$curl->get()){
	echo "Unable to retreive repository list!!\nPlease check your network first\nType Enter Key\n";
	$answer=trim(strtolower(fgets(STDIN)));
	exit();
}

$ini->loadString($curl->data);

$Version=$ini->_params["NEXT"]["zarafa-debian70-x64"];

if($Version==null){
	echo "Unable to Parse repository!!\nPlease check your network first\nType Enter Key\n";
	$answer=trim(strtolower(fgets(STDIN)));
	exit();	
}

if(!is_file($unix->find_program("zarafa-server"))){
	$tar=$unix->find_program("tar");
	echo "Welcome on Zarafa Internet server installed\n";
	echo "This section will install a full mail server on your system.\n";
	echo "It include Zarafa v$Version\n";
	echo "\n";
	echo "	- Only Debian 7 64Bits supported -\n";
	echo "\n";
	echo "Type Enter Key to continue\n";
	$answer=trim(strtolower(fgets(STDIN)));
}

if(!is_file($unix->POSTFIX_MASTER_BIN_PATH())){
	$uri="http://www.artica.fr/download/postfix-debian7/$postfix_package";
	echo "Installing Postfix MTA...\n";
	echo "Downloading Postfix MTA package...\n";
	echo "$uri\n";
	$curl=new ccurl($uri);
	if(!$curl->GetFile("/tmp/$postfix_package")){
		echo "Unable to retreive Postfix package\nPlease check your network first\nType Enter Key\n";
		$answer=trim(strtolower(fgets(STDIN)));
		exit();
	}
	echo "Extracting Postfix MTA package...\n";
	shell_exec("$tar -xhf /tmp/$postfix_package -C /");
	@unlink("/tmp/$postfix_package");
}

if(!is_file($unix->POSTFIX_MASTER_BIN_PATH())){
	echo "Unable to retreive Postfix package\nPlease check your network first\nType Enter Key\n";
	$answer=trim(strtolower(fgets(STDIN)));
	exit();	
}

if(!is_file($unix->find_program("zarafa-server"))){
	echo "Installing Zarafa MDA $Version...\n";
	$zarafa_package="zarafa-debian70-x64-$Version.tar.gz";
	$curl=new ccurl("http://www.artica.fr/download/$zarafa_package");
	if(!$curl->GetFile("/tmp/$zarafa_package")){
		echo "Unable to retreive Zarafa package\nPlease check your network first\nType Enter Key\n";
		$answer=trim(strtolower(fgets(STDIN)));
		exit();
	}
	echo "Extracting Zarafa package...\n";
	shell_exec("$tar -xhf /tmp/$zarafa_package -C /");
	@unlink("/tmp/$zarafa_package");
	
}

if(!is_file($unix->find_program("zarafa-server"))){
	echo "Unable to retreive Zarafa package\nPlease check your network first\nType Enter Key\n";
	$answer=trim(strtolower(fgets(STDIN)));
	exit();
}


echo "Configuring your system...\n";
@file_put_contents("/etc/artica-postfix/ZARAFA_APPLIANCE", time());
$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ZarafaDedicateMySQLServer", 1);
shell_exec("/usr/bin/php /usr/share/artica-postfix/exec.status.php --process1 --force --verbose");
$php=$unix->LOCATE_PHP5_BIN();
shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php --build --force");
echo "Restarting Zarafa Database\n";
shell_exec("/etc/init.d/zarafa-db restart");
echo "Restarting Zarafa...\n";
shell_exec("/etc/init.d/zarafa-server restart");
shell_exec("/etc/init.d/zarafa-gateway restart");
$unix->framework_exec("/usr/sbin/artica-phpfpm-service -restart-artica-status");



