<?php
$GLOBALS["MAXTTL"]=15;
$GLOBALS["FORCE"]=false;
$GLOBALS["PROGRESS"]=false;
include(dirname(__FILE__).'/ressources/class.qos.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');

disconnect();

function disconnect(){
	
	$unix=new unix();
	$sock=new sockets();
	progress("Unmout Stastistics Appliance",20);
	$php=$unix->LOCATE_PHP5_BIN();
	
	$sock->SET_INFO("EnableSquidRemoteMySQL",0);
	$sock->SET_INFO("EnableRemoteSyslogStatsAppliance",0);
	$sock->SET_INFO("RemoteStatisticsApplianceSettings",base64_encode(serialize(array())));
	$sock->SET_INFO("WizardStatsAppliance",base64_encode(serialize(array())));
	$sock->SET_INFO("UseRemoteUfdbguardService",0);
	
	progress("{reconfiguring} Proxy service",30);
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	progress("{reconfiguring} Web filtering service",40);
	system("$php /usr/share/artica-postfix/exec.squidguard.php --build --force");
	progress("{restarting} Service status",50);
	system("/etc/init.d/artica-status restart --force");
	progress("{reconfiguring} {tasks}",60);
	system("$php /usr/share/artica-postfix/exec.schedules.php");
	progress("{done}",100);
	
}



function progress($text,$prc){
	
	if($GLOBALS["OUTPUT"]){echo "[{$prc}%] $text\n";}
	sleep(1);
	$file=PROGRESS_DIR."/squid.stats-appliance.disconnect.php.progress";
	$ARRAY["TEXT"]=$text;
	$ARRAY["POURC"]=$prc;
	@file_put_contents($file, serialize($ARRAY));
	@chmod($file,0755);
}
