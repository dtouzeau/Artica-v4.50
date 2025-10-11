<?php

if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");

if($argv[1]=="--menu"){menu();exit;}
if($argv[1]=="--disable"){disable();exit;}
if($argv[1]=="--enable"){enable();exit;}

function disable(){
	$unix=new unix();
	$conf[]="[PROXY]";
	$conf[]="ArticaProxyServerEnabled=no\n";
	$php=$unix->LOCATE_PHP5_BIN();
	$sock=new sockets();
	$sock->SaveConfigFile(@implode("\n",$conf),"ArticaProxySettings");

	
}

function enable(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$sock=new sockets();
	
	$ini=new Bs_IniHandler();
	$ini->loadString($sock->GET_INFO("ArticaProxySettings"));
	$ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
	$ArticaProxyServerPort=intval($ini->_params["PROXY"]["ArticaProxyServerPort"]);
	if($ArticaProxyServerPort==0){$ArticaProxyServerPort=3128;}
	
	$conf[]="[PROXY]";
	$conf[]="ArticaProxyServerEnabled=yes";
	$at=null;
	
	if(is_file("/etc/artica-postfix/WIZARD_PROXY_PORT")){
		$ArticaProxyServerPort=@file_get_contents("/etc/artica-postfix/WIZARD_PROXY_PORT");
	}
	
	$ArticaProxyServerUsername=@file_get_contents("/etc/artica-postfix/WIZARD_PROXY_USER");
	$ArticaProxyServerUserPassword=@file_get_contents("/etc/artica-postfix/WIZARD_PROXY_PASS");
	
	if(is_file("/etc/artica-postfix/WIZARD_PROXY_NAME")){
		$ArticaProxyServerName=@file_get_contents("/etc/artica-postfix/WIZARD_PROXY_NAME");
	}

	if(trim($ArticaProxyServerUserPassword)<>null){$p=":{$ArticaProxyServerUserPassword}";}
	if(trim($ArticaProxyServerUsername)<>null){$at="{$ArticaProxyServerUsername}$p@";}
	if(trim($ArticaProxyServerPort)<>null){$port=":{$ArticaProxyServerPort}";}
	
	$uri="http://$at{$ArticaProxyServerName}$port";
	
	$conf[]="ArticaProxyServerName=$ArticaProxyServerName";
	$conf[]="ArticaProxyServerPort=$ArticaProxyServerPort";
	$conf[]="ArticaProxyServerUsername=$ArticaProxyServerUsername";
	$conf[]="ArticaProxyServerUserPassword=$ArticaProxyServerUserPassword";
	$conf[]="ArticaCompiledProxyUri=$uri\n";

	$sock->SaveConfigFile(@implode("\n",$conf),"ArticaProxySettings");
	
	
	$f[]="/etc/artica-postfix/WIZARD_PROXY_NAME";
	$f[]="/etc/artica-postfix/WIZARD_PROXY_PORT";
	$f[]="/etc/artica-postfix/WIZARD_PROXY_AUTH";
	$f[]="/etc/artica-postfix/WIZARD_PROXY_USER";
	$f[]="/etc/artica-postfix/WIZARD_PROXY_PASS";
	
	foreach ($f as $filename){if(is_file($filename)){@unlink($filename);}}

}

function menu(){
	$unix=new unix();
	$HOSTNAME=$unix->hostname_g();
	$DIALOG=$unix->find_program("dialog");
	$php=$unix->LOCATE_PHP5_BIN();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini->loadString($sock->GET_INFO("ArticaProxySettings"));
	$ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
	$ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
	$ArticaProxyServerPort=intval($ini->_params["PROXY"]["ArticaProxyServerPort"]);
	if($ArticaProxyServerPort==0){$ArticaProxyServerPort=3128;}
	$ArticaProxyServerUsername=$ini->_params["PROXY"]["ArticaProxyServerUsername"];
	$ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];
	$ArticaCompiledProxyUri=$ini->_params["PROXY"]["ArticaCompiledProxyUri"];
	
	
	$f[]="#!/bin/bash";
	$f[]="INPUT=/tmp/menu.sh.$$";
	$f[]="OUTPUT=/tmp/output.sh.$$";
	$f[]="trap \"rm -f \$OUTPUT; rm -f \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
	$f[]="DIALOG=\${DIALOG=dialog}";
	
	
	$f[]="\t$DIALOG --title \"Internet connection with a proxy\" --yesno \"Did you want to enable the use of a global proxy ?\\nPress 'Yes' to setup the proxy, or 'No' to disable use a proxy\" 0 0";
	$f[]="\tcase $? in";
	$f[]="\t\t0)";

	$f[]="\t$DIALOG --clear --title \"Proxy name\" --inputbox \"Enter address of the proxy\\nExample: myproxy.local or 192.168.1.2\" 10 68 $ArticaProxyServerName 2> /etc/artica-postfix/WIZARD_PROXY_NAME";
	$f[]="\tif [ $? = 1 -o $? = 255 ]; then";
	$f[]="\t\trm -f /etc/artica-postfix/WIZARD_PROXY_NAME";
	$f[]="\t\texit 0";
	$f[]="fi";
	
	$f[]="\t$DIALOG --clear --title \"Proxy Port\" --inputbox \"Enter address of the proxy port\\nExample: 3128 or 8080\" 10 68 $ArticaProxyServerPort 2> /etc/artica-postfix/WIZARD_PROXY_PORT";
	$f[]="\tif [ $? = 1 -o $? = 255 ]; then";
	$f[]="\t\trm -f /etc/artica-postfix/WIZARD_PROXY_NAME";
	$f[]="\t\trm -f /etc/artica-postfix/WIZARD_PROXY_PORT";
	$f[]="\t\texit 0";
	$f[]="fi";	
	
	$f[]="\t$DIALOG --title \"Authentication\" --yesno \"Did this proxy require credentials\\nPress 'Yes' to set your username and password, or 'No' to disable authentication\" 0 0";
	$f[]="\t\tcase $? in";
	$f[]="\t\t\t0)";
		$f[]="\t\t\t\techo 1 >/etc/artica-postfix/WIZARD_PROXY_AUTH";
		$f[]="\t\t\t\t$DIALOG --clear --title \"Proxy Authentication\" --inputbox \"Enter your account\" 10 68 $ArticaProxyServerUsername 2> /etc/artica-postfix/WIZARD_PROXY_USER";
		$f[]="\t\t\t\tif [ $? = 1 -o $? = 255 ]; then";
		$f[]="\t\t\t\t\trm -f /etc/artica-postfix/WIZARD_PROXY_NAME";
		$f[]="\t\t\t\t\trm -f /etc/artica-postfix/WIZARD_PROXY_PORT";
		$f[]="\t\t\t\t\trm -f /etc/artica-postfix/WIZARD_PROXY_AUTH";
		$f[]="\t\t\t\t\texit 0";
		$f[]="\t\t\tfi";	
		$f[]="\t\t\t\t$DIALOG --clear --title \"Proxy Authentication\" --insecure --passwordbox \"Enter your password\" 10 68 2> /etc/artica-postfix/WIZARD_PROXY_PASS";
		$f[]="\t\t\t\tif [ $? = 1 -o $? = 255 ]; then";
		$f[]="\t\t\t\t\trm -f /etc/artica-postfix/WIZARD_PROXY_NAME";
		$f[]="\t\t\t\t\trm -f /etc/artica-postfix/WIZARD_PROXY_PORT";
		$f[]="\t\t\t\t\trm -f /etc/artica-postfix/WIZARD_PROXY_AUTH";
		$f[]="\t\t\t\t\trm -f /etc/artica-postfix/WIZARD_PROXY_USER";
		$f[]="\t\t\t\t\trm -f /etc/artica-postfix/WIZARD_PROXY_PASS";
		$f[]="\t\t\t\t\texit 0";
		$f[]="\t\t\tfi";		
		$f[]="\t\t;;";
	$f[]="\t1)";
	$f[]="\techo 0 >/etc/artica-postfix/WIZARD_PROXY_AUTH";
	$f[]="\t\t;;";
	$f[]="\tesac";
	
	$f[]="\t$php ".__FILE__." --enable";
	$f[]="\t$php ".dirname(__FILE__)."/exec.menu.updates.php --menu";	
	$f[]="\t/tmp/bash_update_menu.sh";
	$f[]="\t\texit;;";
	$f[]="\t1)";
	$f[]="\t$php ".__FILE__." --disable";
	$f[]="\t$php ".dirname(__FILE__)."/exec.menu.updates.php --menu";
	$f[]="\t/tmp/bash_update_menu.sh";
	$f[]="\t\texit;;";
	$f[]="\t255)";
	$f[]="\techo \"Say ???\"";
	$f[]="\t\texit;;";
	$f[]="\tesac";	
	
	@file_put_contents("/tmp/menu-proxy.sh", @implode("\n", $f));
	
	@chmod("/tmp/menu-proxy.sh",0755);
	
	
}