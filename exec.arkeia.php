<?php
$GLOBALS["FORCE"]=false;$GLOBALS["REINSTALL"]=false;
$GLOBALS["NO_HTTPD_CONF"]=false;
$GLOBALS["NO_HTTPD_RELOAD"]=false;
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--reinstall#",implode(" ",$argv))){$GLOBALS["REINSTALL"]=true;}
	if(preg_match("#--no-httpd-conf#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_CONF"]=true;}
	if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_RELOAD"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["posix_getuid"]=0;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');

if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--users"){ParseUsers();exit;}
if($argv[1]=="--default-storage"){SetDefaultStorage();exit;}





function build(){
	
	$lic[]="ITEM\t{";
	$lic[]="\t\"KEY\"\t\"Z5C2GDANS8189Y\"";
	$lic[]="\t\"SERIAL\"\t\"TE91KNNFYHLDP1\"";
	$lic[]="\t\"ORGANISATION\"\t\"Free Version Edition\"";
	$lic[]="\t\"LICENSE\"\t\"ARK_DISKSTORAGE\"";
	$lic[]="}\n";	
	$lic[]="ITEM\t{";
	$lic[]="\t\"KEY\"\t\"PPKBEZCAVKH9PB\"";
	$lic[]="\t\"SERIAL\"\t\"TL92JXVFRHMDPN\"";
	$lic[]="\t\"ORGANISATION\"\t\"Free Version\"";
	$lic[]="\t\"LICENSE\"\t\"ARK91\"";
	$lic[]="}\n";

	
	$unix=new unix();
	$hostname=$unix->hostname_g();
	if(!is_file("/opt/arkeia/arkeiad/admin.cfg")){
		echo "Starting......: ".date("H:i:s")." Arkeia Network Backup writing admin.cfg for ($hostname)\n";
		@file_put_contents("/opt/arkeia/arkeiad/admin.cfg", $unix->hostname_g());
		
	}
	
	if(!is_file("/opt/arkeia/server/dbase/f3sec/license.lst")){
		echo "Starting......: ".date("H:i:s")." Arkeia Network Backup adding Free version license...\n";
		@file_put_contents("/opt/arkeia/server/dbase/f3sec/license.lst", @implode("\n", $lic));
		
	}
	
	$users=ParseUsers();
	if(!isset($users["root"])){
		if($users["root"]["PASSWORD"]==null){
			echo "Starting......: ".date("H:i:s")." Arkeia Network Backup root as no password, delete it...\n";
			unset($users["root"]);
		}
	}
	

	
	$ldap=new clladp();
	$users[$ldap->ldap_admin]["PASSWORD"]=crypt($ldap->ldap_password,"n3");
	$users[$ldap->ldap_admin]["DENY"]="*";
	$users[$ldap->ldap_admin]["NODE"]="*";
	$users[$ldap->ldap_admin]["EMAIL"]="";
	$users[$ldap->ldap_admin]["ROLE"]="ADMINISTRATOR";
	$users[$ldap->ldap_admin]["NAME"]="$ldap->ldap_admin";
	SetUsers($users);
	echo "Starting......: ".date("H:i:s")." Arkeia Network Backup edit/add $ldap->ldap_admin done..\n";
	
	$arc[]="NODE	\"localhost\"";
	$arc[]="LOGIN	\"$ldap->ldap_admin\"";
	$arc[]="PASSWORD	\"$ldap->ldap_password\"";
	$arc[]="ENCODING	\"1\"";
	$arc[]="LANG	\"EN\"";
	@file_put_contents("/opt/arkeia/arkc/arkc.param", @implode("\n", $arc));
	echo "Starting......: ".date("H:i:s")." Arkeia Network Backup edit arkc.param done..\n";
	
	$akeiad[]="ARKEIADLOGLEVEL\t\"10\"	";
	$akeiad[]="PORT_NUMBER\t\"617\"";
	$akeiad[]="NLP_TIMEOUT\t\"60\"";
	$akeiad[]="RESTART_TIMEOUT\t\"300\"";
	$akeiad[]="DONT_USE_PS\t\"1\"";
	$akeiad[]="";
	@file_put_contents("/opt/arkeia/arkeiad/arkeiad.cfg", @implode("\n", $arc));
	echo "Starting......: ".date("H:i:s")." Arkeia Network Backup edit arkeiad.cfg done..\n";
	
	
	
}

function SetUsers($array){
	foreach ($array as $num=>$line){
		$f[]="ITEM\t{";
		while (list ($a, $b) = each ($line)){
			$f[]="\"$a\"\t\"$b\"";
		}
		$f[]="}\n";
		
	}
	
	@file_put_contents("/opt/arkeia/server/dbase/f3sec/usr.lst", @implode("\n", $f));
	
	
}

function SetDefaultStorage(){
	$array=array();
	exec("/opt/arkeia/bin/arkc -vtl -list 2>&1",$results);
	foreach ($results as $num=>$line){
		if(preg_match("#name=(.*)#", $line,$re)){
			$array[$re[1]]=true;
		}
		
	}
	
	if(count($array)>0){return;}
	echo "Starting......: ".date("H:i:s")." Arkeia Network Backup creating the default storage with 249.5G\n";
	shell_exec("/opt/arkeia/bin/arkc -vtl -create -D name=DefaultStorage capacity=249500 path=/home/arkeia/backup media_server=localhost");
	
	
}

function ParseUsers(){
	
	$f=file("/opt/arkeia/server/dbase/f3sec/usr.lst");
	$c=0;
	foreach ($f as $index=>$line){
		$line=trim($line);
		$line=str_replace("\n", "", $line);
		$line=str_replace("\r", "", $line);
		if(preg_match("#ITEM\s+\{#", $line)){$c++;continue;}
		if(preg_match("#\"(.*)\"\s+\"(.*)\"#", $line,$re)){
			$n[$c][$re[1]]=$re[2];
			continue;
		}
	}
	
	while (list ($num, $line) = each ($n)){
		$t[$line["NAME"]]=$line;
		
	}
	
	
	return $t;
	
}



