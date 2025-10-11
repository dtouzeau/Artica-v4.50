<?php

$_GET["filelogs"]="/var/log/artica-postfix/iptables.debug";
$_GET["filetime"]="/etc/artica-postfix/croned.1/".basename(__FILE__).".time";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.iptables-chains.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.baseunix.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
iptables_delete_all();


function iptables_delete_all(){
$RESTORE=false;
$unix=new unix();
$iptables_save=$unix->find_program("iptables-save");
$iptables_restore=$unix->find_program("iptables-restore");
events("Exporting datas iptables-save > /etc/artica-postfix/iptables.conf");
system("$iptables_save > /etc/artica-postfix/iptables.conf");
$data=file_get_contents("/etc/artica-postfix/iptables.conf");
$datas=explode("\n",$data);
$pattern="#.+?(ArticaInstantPostfix|ArticaPersoRules|ArticaInstantNginx|SpamHaus)#";	
foreach ($datas as $num=>$ligne){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$RESTORE=true;continue;}
		events("skip rule $ligne from deletion");
		$conf=$conf . $ligne."\n";
		}

if($RESTORE){
	events("restoring datas iptables-restore < /etc/artica-postfix/iptables.new.conf");
	file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
}

parsequeue();

}
function progress($pourc,$text){
	if($GLOBALS["VERBOSE"]){echo "$pourc% $text\n";}
	$file="/usr/share/artica-postfix/ressources/logs/compile.iptables.progress";
	$ini=new Bs_IniHandler();
	$ini->set("PROGRESS","pourc",$pourc);
	$ini->set("PROGRESS","text",$text);
	$ini->saveFile($file);
	chmod($file,0777);
	}



function events($text){
	$pid=getmypid();
	$date=date('Y-m-d H:i:s');
	$logFile=$_GET["filelogs"];
	$size=filesize($logFile);
	if($size>1000000){unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$date [$pid] $text\n");
	@fclose($f);	
}

	
function parsequeue(){

	foreach (glob("/var/log/artica-postfix/smtp-hack/*.hack") as $filename) {
		@unlink($filename);
	}
	
}
?>