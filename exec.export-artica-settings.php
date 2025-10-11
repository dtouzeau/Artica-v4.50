<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once('ressources/class.activedirectory.inc');
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}	
export();
	
function export(){
	$workdir=dirname(__FILE__)."/ressources/squid-export";
	if(!is_dir($workdir)){@mkdir($workdir,0777,true);}
if (!$handle = opendir("/etc/artica-postfix/settings/Daemons")) {@mkdir("/etc/artica-postfix/settings/Daemons",0755,true);exit();}
	while (false !== ($filename = readdir($handle))) {
				if($filename=="."){continue;}
				if($filename==".."){continue;}
				$targetFile="/etc/artica-postfix/settings/Daemons/$filename";
				if($GLOBALS["VERBOSE"]){echo "Exporting $filename\n";}
				$array[$filename]=@file_get_contents($targetFile);
				
	}
	
	if($GLOBALS["VERBOSE"]){echo count($array)." items....\n";}
	
	$finalitems=base64_encode(serialize($array));
	$unix=new unix();
	$tmpf=$unix->FILE_TEMP();
	@file_put_contents($tmpf, $finalitems);
	if($GLOBALS["VERBOSE"]){echo "compressing in $workdir/settingsHD.gz\n";}
	if(!$unix->compress($tmpf, "$workdir/settingsHD.gz")){@unlink("$workdir/settingsHD.gz");}
	@chmod( "$workdir/settingsHD.gz",0777);
	@unlink($tmpf);	
	if($GLOBALS["VERBOSE"]){echo "compressing in $workdir/settingsHD.gz done\n";}
	
}	