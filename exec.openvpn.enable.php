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
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["server-conf"]=false;
$GLOBALS["IPTABLES_ETH"]=null;
$GLOBALS["FRAMEWORK"]=false;
if(preg_match("#--framework#",@implode("",$argv))){$GLOBALS["FRAMEWORK"]=true;}

if($argv[1]=="--certificate"){rebuild_certificate();exit;}
xrun();

function rebuild_certificate(){
	build_progress("{rebuild}:{certificate}",15);
	$OpenVPNCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNCertificate");
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	echo "Certificate $OpenVPNCertificate:\n";
	system("$php /usr/share/artica-postfix/exec.openssl.php --easyrsa \"$OpenVPNCertificate\"");
	
	build_progress("{building_configuration}",50);
	system("$php /usr/share/artica-postfix/exec.openvpn.php --server-conf");
	
	build_progress("{restarting_service}",70);

	system("/etc/init.d/openvpn-server restart");
	build_progress("{rebuild}:{certificate} {done}",100);
}

function xrun(){

	$unix=new unix();
	build_progress("{enable_service}",15);
	$php=$unix->LOCATE_PHP5_BIN();
	//system("$php /usr/share/artica-postfix/exec.convert-to-sqlite.php --openvpn");
	$vpn=new openvpn();
	$OpenVPNWizard=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNWizard")));
	$vpn->main_array["GLOBAL"]["ENABLE_SERVER"]=1;
	$vpn->main_array["GLOBAL"]["IP_START"]=$OpenVPNWizard["IP_START"];
	$vpn->main_array["GLOBAL"]["NETMASK"]=$OpenVPNWizard["NETMASK"];
	$vpn->main_array["GLOBAL"]["PUBLIC_IP"]=$OpenVPNWizard["PUBLIC_IP"];
	
	build_progress("{$vpn->main_array["GLOBAL"]["IP_START"]}/{$vpn->main_array["GLOBAL"]["NETMASK"]}",20);
	sleep(1);
	build_progress("{$OpenVPNWizard["OpenVPNCertificate"]}/{$vpn->main_array["GLOBAL"]["PUBLIC_IP"]}",25);
	sleep(1);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableOpenVPNServer",1);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenVPNCertificate",$OpenVPNWizard["OpenVPNCertificate"]);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableOPenVPNServerMode", 1);
	$vpn->Save(true);
	build_progress("{building_configuration}",50);

    shell_exec("/usr/sbin/artica-phpfpm-service -install-openvpn");

	shell_exec("/etc/init.d/cron reload");
    authentication_rules();
	
	$q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
	$results=$q->QUERY_SQL("SELECT *, uid FROM openvpn_clients");
	if($q->ok){
		foreach($results as $index=>$ligne) {
			$uid=$ligne["uid"];
			build_progress("{building_certificate}:$uid",62);
			system("$php /usr/share/artica-postfix/exec.openvpn.build-client.php \"$uid\"");
		}
	}
	build_progress("{done}",100);
}

function authentication_rules(){
	$unix=new unix();
	$q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
	$f=array();

	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	if($EnableKerbAuth==1){
		$KerbAuthInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
        
		@file_put_contents("/etc/openvpn/CurrentActiveDirectory",$KerbAuthInfos["ADNETIPADDR"]);
	}

	$sql="SELECT * FROM vpn_auth ORDER BY ID DESC";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	foreach($results as $index=>$ligne) {
		if($ligne["hostname"]==null){$ligne["hostname"]="-";}
		$f[]="{$ligne["ID"]}||{$ligne["type"]}||{$ligne["hostname"]}";
		@file_put_contents("/etc/openvpn/auth.groups.{$ligne["ID"]}", $ligne["params"]);
	}
	@file_put_contents("/etc/openvpn/auth.conf", @implode("\n", $f));
}

function PID_NUM(){

	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/openvpn/openvpn-server.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("openvpn");
	return $unix->PIDOF_PATTERN("$Masterbin --management /var/run/openvpn.sock unix --port.+?--dev");

}




function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/openvpn.enable.progress";
	echo "[{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){sleep(1);}
}
?>