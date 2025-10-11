<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.openvpn.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . '/ressources/class.openvpn.certificate.inc');

$GLOBALS["server-conf"]=false;
$GLOBALS["IPTABLES_ETH"]=null;
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(is_array($argv)){
		if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
		if(preg_match("#--wait#",implode(" ",$argv))){$GLOBALS["WAIT"]=true;}
if($GLOBALS["VERBOSE"]){echo "Debug mode TRUE for {$argv[1]}\n";}
$users=new usersMenus();
if($users->KASPERSKY_WEB_APPLIANCE){exit();}

$openvpn=new openvpn();


if(isset($openvpn->main_array["GLOBAL"]["IPTABLES_ETH"])){$GLOBALS["IPTABLES_ETH"]=$openvpn->main_array["GLOBAL"]["IPTABLES_ETH"];}

if($argv[1]=='--server-conf'){
		$GLOBALS["server-conf"]=true;
		writelogs("Starting......: ".date("H:i:s")." OpenVPN {building_settings}...","main",__FILE__,__LINE__);
		exit();
}

if($argv[1]=="--genstats"){GenStats();exit();}
if($argv[1]=="--stats"){BuildStats();exit();}
if($argv[1]=="--cert"){dumpcert();exit();}
if($argv[1]=="--iptables-server"){BuildIpTablesServer();exit();}
if($argv[1]=="--iptables-delete"){iptables_delete_rules();exit();}
if($argv[1]=="--server-stop"){StopServer();exit();}
if($argv[1]=="--argvs"){LoadArgvs()."\n";exit();}
if($argv[1]=="--auth-rules"){authentication_rules()."\n";exit();}
if($argv[1]=="--client-connect"){client_connect($argv)."\n";die(0);}


writelogs("Starting......: ".date("H:i:s")." OpenVPN Unable to understand this command-line (" .implode(" ",$argv).")","main",__FILE__,__LINE__);	
	
	

function BuildIpTablesServer(){
	if($GLOBALS["WAIT"]){sleep(5);}
	iptables_delete_rules();
	$IPTABLES_ETH=$GLOBALS["IPTABLES_ETH"];

	if($IPTABLES_ETH==null){echo "Starting......: ".date("H:i:s")." OpenVPN no prerouting set (IPTABLES_ETH)\n";return false;}
	
	$unix=new unix();
	$iptables=$unix->find_program("iptables");	
	if(!is_file($iptables)){echo "Starting......: ".date("H:i:s")." OpenVPN iptables, no such binary\n";return false;}
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." OpenVPN: hook the $IPTABLES_ETH nic\n";}
	shell_exec2("$iptables -A INPUT -i tun0 -j ACCEPT -m comment --comment \"ArticaOpenVPN\"");
	shell_exec2("$iptables -A FORWARD -i tun0 -j ACCEPT -m comment --comment \"ArticaOpenVPN\"");
	shell_exec2("$iptables -A OUTPUT -o tun0 -j ACCEPT -m comment --comment \"ArticaOpenVPN\"");
	shell_exec2("$iptables -t nat -A POSTROUTING -o $IPTABLES_ETH -j MASQUERADE -m comment --comment \"ArticaOpenVPN\"");

	shell_exec2("$iptables -A INPUT -i $IPTABLES_ETH -j ACCEPT -m comment --comment \"ArticaOpenVPN\"");
	shell_exec2("$iptables -A FORWARD -i $IPTABLES_ETH -j ACCEPT -m comment --comment \"ArticaOpenVPN\"");
	shell_exec2("$iptables -A OUTPUT -o $IPTABLES_ETH -j ACCEPT -m comment --comment \"ArticaOpenVPN\"");
	shell_exec2("$iptables -t nat -A POSTROUTING -o tun0 -j MASQUERADE -m comment --comment \"ArticaOpenVPN\"");
	echo "Starting......: ".date("H:i:s")." OpenVPN prerouting success from tun0 -> $IPTABLES_ETH...\n";
	
}

function shell_exec2($cmd){
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." OpenVPN: executing \"$cmd\"\n";}
	shell_exec($cmd);
	
}


function StopServer(){
	$unix=new unix();
    $openvpn=$unix->find_program("openvpn");
    $brctl=$unix->find_program("brctl");
    $ifconfig=$unix->find_program("ifconfig");
    $ip_tools=$unix->find_program("ip");	
    $pgrep=$unix->find_program("pgrep");
    $kill=$unix->find_program("kill");
	$ini=new Bs_IniHandler();
    $sock=new sockets();
    $ini->loadString($sock->GET_INFO("ArticaOpenVPNSettings"));
    $BRIDGE_ETH=$ini->_params["GLOBAL"]["BRIDGE_ETH"]; 
    $ENABLE_BRIDGE_MODE=$ini->_params["GLOBAL"]["ENABLE_BRIDGE_MODE"]; 
    if(!is_numeric($ENABLE_BRIDGE_MODE)){$ENABLE_BRIDGE_MODE=0;}
    
    echo "Stopping OpenVPN......................: Mode Bridge = $ENABLE_BRIDGE_MODE\n";
    if($ENABLE_BRIDGE_MODE==1){
	    echo "Stopping OpenVPN......................: Stopping Server bridged on=$BRIDGE_ETH\n";   
		if(preg_match("#(.+?):([0-9]+)#",$BRIDGE_ETH,$re)){$original_eth=$re[1];}
		if($original_eth<>null){
			$array_ip=BuildBridgeServer_eth_infos($BRIDGE_ETH);
			echo "Stopping OpenVPN......................: checking bridges and $original_eth\n";
			$array=GetBridgeExists("br0");
		
			if(is_array($array)){
				echo "Stopping OpenVPN......................: Bridge br0 exists\n";
				system("$ifconfig br0 down");
				foreach ($array as $num=>$ligne){
					echo "Stopping OpenVPN......................: remove $ligne from br0\n";
					system("brctl delif br0 $ligne");
				}
				
				echo "Stopping OpenVPN......................: remove br0\n";
				system("brctl delbr br0");
				system("$ifconfig $original_eth down");
				}
				
				echo "Stopping OpenVPN......................: rebuild $original_eth settings\n";
				system("$ifconfig $original_eth up");
				if(GetIpaddrOf($original_eth)==null){
					if(preg_match("#^(.+?)\.([0-9]+)$#",$array_ip["IPADDR"],$re)){$eth_broadcast="broadcast {$re[1]}.255";}
					system("$ifconfig $original_eth {$array_ip["IPADDR"]} netmask {$array_ip["NETMASK"]} $eth_broadcast");
				}
				system("$ip_tools route add default via {$array_ip["GATEWAY"]} dev $original_eth  proto static");
		}
	
    }
	
	
	echo "Stopping OpenVPN......................: Find ghost processes\n";
	exec("$pgrep -l -f \"$openvpn --port.+?--dev\" 2>&1",$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#^([0-9]+)\s+#", $ligne,$re)){
			if($unix->process_exists($re[1])){
				echo "Stopping OpenVPN......................: {$re[1]} PID\n"; 
				unix_system_kill_force($re[1]);
				
			}
		}
		
	}
	
	iptables_delete_rules();

}



function iptables_delete_rules(){
$unix=new unix();
$iptables_save=$unix->find_program("iptables-save");
$iptables_restore=$unix->find_program("iptables-restore");	
shell_exec("$iptables_save > /etc/artica-postfix/iptables.conf");
$data=file_get_contents("/etc/artica-postfix/iptables.conf");
$datas=explode("\n",$data);
$pattern="#.+?ArticaOpenVPN#";	
$count=0;
foreach ($datas as $num=>$ligne){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$count++;continue;}
		$conf=$conf . $ligne."\n";
		}

file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
shell_exec("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
echo "Starting......: ".date("H:i:s")." OpenVPN cleaning iptables $count rules\n";

}

function iptables_delete_client_rules($ID=0){
$unix=new unix();
$iptables_save=$unix->find_program("iptables-save");
$iptables_restore=$unix->find_program("iptables-restore");		
echo "Starting......: ".date("H:i:s")." OpenVPN cleaning iptables rules for ID $ID\n";
$conf=null;
shell_exec("$iptables_save > /etc/artica-postfix/iptables.conf");
$data=file_get_contents("/etc/artica-postfix/iptables.conf");
$datas=explode("\n",$data);
if($ID==0){
	$pattern="#.+?ArticaVPNClient_[0-9]+#";
}else{
	$pattern='#.+?ArticaVPNClient_'.$ID.'"#';
}	
$count=0;
foreach ($datas as $num=>$ligne){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$count++;continue;}
		$conf=$conf . $ligne."\n";
		}

file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
shell_exec("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
echo "Starting......: ".date("H:i:s")." OpenVPN cleaning iptables $count rules\n";	
}




function dumpcert(){
	$sock=new sockets();
	$certificate=$sock->GET_INFO("OpenVPNCertificate");
	
	if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." OpenVPN certificate '$certificate'\n";}
	$cert=new openvpn_certificate($certificate);
	$certificates=$cert->build();
	echo " * * * $certificates * * *\n";
} 


function LoadArgvs(){
	$unix=new unix();
	$openvpn=$unix->find_program("openvpn");
	exec("$openvpn --help 2>&1",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#^\-\-(.+?)[\s\:]+#", $line,$re)){
			$GLOBALS["OPENVPNPARAMS"][$re[1]]=1;
		}
	}
	
	if($GLOBALS["VERBOSE"]){
		print_r($GLOBALS["OPENVPNPARAMS"]);
	}
}


function debian_version(){
	if(!is_file("/etc/debian_version")){return;}
	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	$Major=$re[1];
	if(!is_numeric($Major)){return 0;}
	return $Major;


}
function fw_transfert($interface,$sitename){
	if(!is_dir("/etc/openvpn/fw")){@mkdir("/etc/openvpn/fw");}
	$unix=new unix();
	$MARKLOG="-m comment --comment \"openvpn_$sitename\"";
	
	$iptables=$unix->find_program("iptables");
	$f[]="$iptables -t nat -I POSTROUTING -s %s -o $interface {$MARKLOG} -j MASQUERADE";
	$f[]="$iptables -I FORWARD -i $interface -o -d %s -m state --state RELATED,ESTABLISHED {$MARKLOG} -j ACCEPT";
	$f[]="$iptables -I FORWARD -s %s -o $interface -m state --state RELATED,ESTABLISHED {$MARKLOG} -j ACCEPT";
	$f[]="$iptables -I FORWARD -i $interface -d %s -m state --state RELATED,ESTABLISHED {$MARKLOG} -j ACCEPT";
	$f[]="$iptables -I FORWARD -s %s {$MARKLOG} -j ACCEPT";
	$f[]="$iptables -I INPUT -s %s {$MARKLOG} -j ACCEPT";
	$f[]="$iptables -I OUTPUT -s %s {$MARKLOG} -j ACCEPT";
	@file_put_contents("/etc/openvpn/fw/$sitename.add", @implode("\n", $f)."\n");
	$f=array();
	
	$f[]="$iptables -t nat -D POSTROUTING -s %s -o $interface {$MARKLOG} -j MASQUERADE";
	$f[]="$iptables -D FORWARD -i $interface -o -d %s -m state --state RELATED,ESTABLISHED {$MARKLOG} -j ACCEPT";
	$f[]="$iptables -D FORWARD -s %s -o eth0 -m state --state RELATED,ESTABLISHED {$MARKLOG} -j ACCEPT";
	$f[]="$iptables -D FORWARD -i $interface -d %s -m state --state RELATED,ESTABLISHED {$MARKLOG} -j ACCEPT";
	$f[]="$iptables -D FORWARD -s %s {$MARKLOG} -j ACCEPT";
	$f[]="$iptables -D INPUT -s %s {$MARKLOG} -j ACCEPT";
	$f[]="$iptables -D OUTPUT -s %s {$MARKLOG} -j ACCEPT";
	@file_put_contents("/etc/openvpn/fw/$sitename.del", @implode("\n", $f)."\n");
	
}

function GenStats(){
	
	$unix=new unix();
	$pidtime="/etc/artica-postfix/pids/exec.openvpn.php.GenStats.time";
    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
    if($DisablePostGres==1){
        return;
    }
	
	if(!$GLOBALS["VERBOSE"]){if($unix->file_time_min($pidtime)<10){return;}}
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
	$now=date("Y-m-d H:i:s",strtotime("-24 hour"));
	$q=new postgres_sql();
	$sql="select zdate,avg(bytesin) as bytesin, avg(bytesout) as bytesout, avg(nclients) as nclients
	from (select to_timestamp(floor((extract('epoch' from zdate) / 600 )) * 600) 
	AT TIME ZONE 'UTC' as zdate,bytesin,bytesout,nclients from openvpn_stats where zdate >'$now') as t GROUP BY zdate order by zdate";
	
	
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	
	
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=@pg_fetch_assoc($results)){
	$min=$ligne["zdate"];
	$bytesin=$ligne["bytesin"];
	$bytesout=$ligne["bytesout"];
	$nclients=$ligne["nclients"];
	
	echo "$min: nclients=$nclients,bytesin=$bytesin,bytesout=$bytesout\n";
	
	
	$ydata[$min]=round(($bytesin/1024),2);
	$ydataL[$min]=round(($bytesout/1024),2);
	$ydataM[$min]=round($nclients);
	
	}
	
	

	if(count($ydata)>1){
		if($GLOBALS["VERBOSE"]){echo "-> /etc/artica-postfix/settings/Daemons/OpenVPNStatsBytesIn\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenVPNStatsBytesIn", serialize($ydata));
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenVPNStatsBytesOut", serialize($ydataL));
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenVPNStatsnClients", serialize($ydataM));
		@chmod("/etc/artica-postfix/settings/Daemons/OpenVPNStatsBytesIn",0755);
		@chmod("/etc/artica-postfix/settings/Daemons/OpenVPNStatsBytesOut",0755);
		@chmod("/etc/artica-postfix/settings/Daemons/OpenVPNStatsnClients",0755);
	}
	
	
}

function BuildStats(){
	$unix=new unix();
	$pidtime="/etc/artica-postfix/pids/exec.openvpn.php.stats.time";
	
	if(!$GLOBALS["VERBOSE"]){if($unix->file_time_min($pidtime)<5){return;}}
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	
	include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
	$ncat=$unix->find_program("ncat");
	$echo=$unix->find_program("echo");
	$tmpfile=$unix->FILE_TEMP();
	$q=new postgres_sql();
	$cmd="$echo \"load-stats\"|$ncat -U /var/run/openvpn.sock >$tmpfile 2>&1";
	shell_exec($cmd);
	
	$f=explode("\n",@file_get_contents($tmpfile));
	@unlink($tmpfile);
	$bytesout2=0;
	$bytesin2=0;
	$nclients=0;
	foreach ($f as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#SUCCESS:\s+nclients=([0-9]+),bytesin=([0-9]+),bytesout=([0-9]+)#i",$line,$re)){continue;}
		$nclients=intval($re[1]);
		$bytesin2=intval($re[2]);
		$bytesout2=intval($re[3]);
		break;
	}
	
	if($bytesout2==0){return;}
	if($nclients==0){return;}
	
	$bytesin1=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNBytesIn1"));
	$bytesout1=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenVPNBytesOut1"));
	if($bytesout1>$bytesout2){$bytesout1=0;}
	if($bytesin1>$bytesin2){$bytesin1=0;}
	
	
	
	
	$bytesin=$bytesin2-$bytesin1;
	$bytesout=$bytesout2-$bytesout1;
	if($bytesin==0){return;}
	if($bytesout==0){return;}
	if($nclients==0){return;}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenVPNBytesIn1", $bytesin2);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenVPNBytesOut1", $bytesout2);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenVPNCNXNUmber", $nclients);
	
	echo "nclients=$nclients,bytesin=$bytesin,bytesout=$bytesout\n";
	
	
	$q->VPN_TABLES();
	$zdate=date("Y-m-d H:i:s");
	$sql="INSERT INTO openvpn_stats (zdate,nclients,bytesin,bytesout) VALUES ('$zdate','$nclients','$bytesin','$bytesout')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
	
	
	
}
?>