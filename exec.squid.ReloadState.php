<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
$GLOBALS["VERBOSE"]=false;
$GLOBALS["makeQueryForce"]=false;
$GLOBALS["FORCE"]=false;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");



xstart();



function xstart(){
	$cacheTime="/etc/artica-postfix/pids/".basename(__FILE__);
	$unix=new unix();
	
	$TimeExec=$unix->file_time_min($cacheTime);
	if($TimeExec<5){
		squid_admin_mysql(0, "Unable to Reloading network after monitoring IP state UP ( require at least 5mn)", null,__FILE__,__LINE__);
		exit();
	}
	
	@unlink($cacheTime);
	@file_put_contents($cacheTime, time());
	

	squid_admin_mysql(0, "Reloading network Interfaces after monitoring IP state UP", null,__FILE__,__LINE__);
	system("/usr/sbin/artica-phpfpm-service -restart-network");
	
	if(is_file("/etc/init.d/unbound")){
		squid_admin_mysql(0, "Restarting DNS Proxy after monitoring IP state UP", null,__FILE__,__LINE__);
		system('/usr/sbin/artica-phpfpm-service -restart-unbound');
	}
	
	if(is_file("/etc/init.d/pdns")){
		squid_admin_mysql(0, "Restarting DNS service after monitoring IP state UP", null,__FILE__,__LINE__);
		system('/etc/init.d/pdns restart');
	
	}

	$squidbin=$unix->LOCATE_SQUID_BIN();
	
	if(is_file($squidbin)){
		if(is_file("/etc/init.d/squid")){
			squid_admin_mysql(0, "Reloading Proxy after monitoring IP state UP", null,__FILE__,__LINE__);
            system("/usr/sbin/artica-phpfpm-service -reload-proxy");
		}
	}
	
	
	if(is_file("/etc/init.d/artica-webconsole")){
		squid_admin_mysql(0, "Restarting Web administration console after monitoring IP state UP", null,__FILE__,__LINE__);
		system('/etc/init.d/artica-webconsole restart');
	
	}	
	if(is_file("/etc/init.d/suricata")){
		squid_admin_mysql(0, "Restarting Intrusion detection system after monitoring IP state UP", null,__FILE__,__LINE__);
		system('/etc/init.d/suricata restart');
	
	}	
	if(is_file("/etc/init.d/hypercache-service")){system("/etc/init.d/hypercache-service restart");}
	
}