<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.openvpn.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');

$GLOBALS["server-conf"]=false;
$GLOBALS["IPTABLES_ETH"]=null;

xrun();


function xrun(){

	$unix=new unix();
	build_progress("{disable_service}",15);
	$vpn=new openvpn();
	$vpn->main_array["GLOBAL"]["ENABLE_SERVER"]=0;
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableOpenVPNServer",0);
	$vpn->Save(true);
	build_progress("{building_configuration}",50);
	$php=$unix->LOCATE_PHP5_BIN();
	$RELOAD_CRON=False;
	
	build_progress("{stopping_service}",60);
	remove_service("/etc/init.d/openvpn-server");
	build_progress("{removing_startup_scripts}",70);
	
	if(is_file("/etc/cron.d/artica-openvpn-sess2mn")){
		@unlink("/etc/cron.d/artica-openvpn-sess2mn");
		$RELOAD_CRON=true;
	}
	if(is_file("/etc/cron.d/openvpn-status-mn")){
		@unlink("/etc/cron.d/openvpn-status-mn");
		$RELOAD_CRON=true;
	}

	if($RELOAD_CRON){
		shell_exec("/etc/init.d/cron reload");
	}

    shell_exec("/usr/sbin/artica-phpfpm-service -uninstall-openvpn");
	

	
	if(is_file("/etc/monit/conf.d/APP_OPENVPN.monitrc")){
		@unlink("/etc/monit/conf.d/APP_OPENVPN.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	}

	build_progress("{restarting_artica_status}",90);
	system("/etc/init.d/artica-status restart --force");
	

	
	
	build_progress("{done}",100);
	
}
function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/openvpn.enable.progress";
	echo "[{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){sleep(1);}
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");

	}

	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}


?>