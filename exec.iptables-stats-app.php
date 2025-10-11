<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["EBTABLES"]=false;
$GLOBALS["OUTPUT"]=true;
$GLOBALS["PROGRESS"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');


if($argv[1]=="--delete"){delete();exit;}
if($argv[1]=="--restart"){restart();exit;}

buildScript();

function restart(){
	
	buildScript();
	system("/etc/init.d/artica-postgres restart");
	if(!is_file("/etc/init.d/iptables-statsapp")){return;}
	system("/etc/init.d/iptables-statsapp restart");
}

function buildScript(){
	
	script_uninstall();
	delete();
	
}








function script_uninstall(){
	
	if(!is_file("/etc/init.d/iptables-statsapp")){return;}
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f iptables-statsapp remove >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --del iptables-statsapp >/dev/null 2>&1");
		
	}
	@unlink("/etc/init.d/iptables-statsapp");
	
}
function delete(){
	$d=0;
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	system("$iptables_save > /etc/artica-postfix/iptables2.conf");
	$data=file_get_contents("/etc/artica-postfix/iptables2.conf");
	$datas=explode("\n",$data);
	$pattern2="#.+?ArticaStatsAppliance#";

	
	foreach ($datas as $num=>$ligne){
		if($ligne==null){continue;}
		if(preg_match($pattern2,$ligne)){$d++;continue;}

		$conf=$conf . $ligne."\n";
	}
	file_put_contents("/etc/artica-postfix/iptables.new2.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new2.conf");
	echo "Starting......: ".date("H:i:s")." Removing $d iptables rule(s) done...\n";

}