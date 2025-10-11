<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");

$unix=new unix();
if(is_file("/etc/artica-postfix/FROM_ISO")){
	if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){return;}
}

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}

if($argv[1]=="--detect"){detectCards();exit();}
if($argv[1]=="--iwlist"){iwlist($argv[2]);exit();}
if($argv[1]=="--ap"){ConnectToAccessPoint();exit();}
if($argv[1]=="--checkap"){CheckConnection();exit();}



function detectCards(){
	$unix=new unix();
	
	if($unix->file_time_get(basename(__FILE__))<5){return;}
	@mkdir("/etc/artica-postfix/pids",0755,true);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		echo "detectCards: [INFO] Already running...\n";
		exit();
	}
	
	@file_put_contents($pidfile, getmypid());	
	$unix->file_time_set(basename(__FILE__));
	$detect=false;
	$sock=new sockets();
	$lspci=$unix->find_program("lspci");
	$iwlist=$unix->find_program("iwlist");
	if($iwlist==null){
		if($GLOBALS["VERBOSE"]){echo "Unable to stat iwlist\n";}
		$sock->SET_INFO("WifiCardOk",0);
		exit;
	}
	
	exec("$lspci -mm",$results);
	foreach ($results as $num=>$ligne){
			if(preg_match('#[0-9\:\.]+\s+".+?"\s+".+?"\s+"(.+?)"#',$ligne,$re)){				
				if(SupportedCards($re[1])){$detect=true;}
			}
	}
	
	
	if($detect){
		echo "Starting......: ".date("H:i:s")." WIFI Network Card detected\n";
		$sock=new sockets();
		$sock->SET_INFO("WifiCardOk",1);
		exit;
	}
	$sock->SET_INFO("WifiCardOk",0);
}



function SupportedCards($pattern){
	
	$array[]="PRO/Wireless 4965 AG or AGN [Kedron] Network Connection";
	$array[]="BCM4312 802.11b/g";
	$array[]="RTL8187SE Wireless LAN Controller";
	$array[]="DWL-520+ 22Mbps PCI Wireless Adapter";
	$array[]="AirPlus G DWL-G510 Wireless Network Adapter (Rev.C)";
	$array[]="RT2561/RT61 rev B 802.11g";
	
	foreach ($array as $num=>$ligne){
		if(strtolower($ligne)==trim(strtolower($pattern))){
			echo "Starting......: ".date("H:i:s")." WIFI $pattern\n";
			return true;
		
		}
	}
	
	if($GLOBALS["VERBOSE"]){
		echo "Starting......: ".date("H:i:s")." WIFI \"$pattern\" (NOT supported)\n";
	}
}

function build_progress_iwlist($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/iwlist.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function iwlist($eth=null){
	$unix=new unix();
	
	@mkdir("/etc/artica-postfix/pids",0755,true);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		build_progress_iwlist(110, "Already running");
		echo "iwlist: [INFO] Already running...\n";
		exit();
	}
	
	@file_put_contents($pidfile, getmypid());	
	
	if($eth==null){
		$eth=$unix->GET_WIRELESS_CARD();
	}
	
	if($eth==null){ 
		if($GLOBALS["VERBOSE"]){echo "Unable to get nic name...\n";}
		return;
	}
	build_progress_iwlist(25, "{scanning} $eth");
	
	$iwlist=$unix->find_program("iwlist");
	$iwgetid=$unix->find_program("iwgetid");
	$ifconfig=$unix->find_program("ifconfig");
	shell_exec("$ifconfig $eth up");
	exec("$iwgetid $eth -s -r",$ares);
	$SELECTED_POINT=trim(@implode("",$ares));
	
	
	$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/iwlist.scan"));
    if(!is_array($array)){$array=array();}
	exec("$iwlist $eth scan 2>&1",$results);
	$prc=25;
	foreach ($results as $num=>$ligne){
		
		if(preg_match("#Network is down#",$ligne)){ break; }
		
		if(preg_match("#Cell\s+([0-9]+).+?Address:\s+(.+)#",$ligne,$re)){
			build_progress_iwlist($prc++, "{scanning} {$re[2]}");
			$mac=$re[2];
			$index=$re[1];
			$array[$mac]["MAC"]=$mac;
			$array[$mac]["RATES"]=null;
			echo "Index:$index '$mac'\n";
			continue;
		}
		
		if(preg_match("#Quality[=:]([0-9\.]+)\/([0-9\.]+)#",$ligne,$re)){
			$purc=$re[1]/$re[2];
			$purc=$purc*100;
			$array[$mac]["QUALITY"]=round($purc,1);
			continue;
		}
		if(preg_match("#ESSID:[='\"](.*?)['\"]#",$ligne,$re)){
			build_progress_iwlist($prc++, "{scanning} {$re[1]}");
			if(trim($re[1])==null){$re[1]=$array[$mac]["MAC"];}
			echo "ESSID '{$re[1]}'\n";
			$array[$mac]["ESSID"]=trim($re[1]);
			continue;
		}
		
		if(preg_match("#Encryption key:([a-zA-z]+)#",$ligne,$re)){
			$re[1]=strtolower(trim($re[1]));
			$array[$mac]["KEY"]=false;
			if($re[1]=="on"){$array[$mac]["KEY"]=true;}
			continue;
		}
		if(preg_match("#Bit Rates:(.+)#",$ligne,$re)){
			$array[$mac]["RATES"][$re[1]]=true;
			 continue;
		}
		
		echo "Skipped '$ligne'\n";
		 
		
	}
	
	
	build_progress_iwlist(100, "{scanning} {success}");
	if($GLOBALS["VERBOSE"]){print_r($array);}
	@unlink("/usr/share/artica-postfix/ressources/logs/iwlist.scan");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/iwlist.scan",@serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/iwlist.scan",0775);
}

function ConnectToAccessPoint(){
	$sock=new sockets();
	$unix=new unix();
	$WifiAPEnable=$sock->GET_INFO("WifiAPEnable");
	if($WifiAPEnable<>1){return null;}
	
	
	wpa_removenetworks();
	$array=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WifiAccessPoint")));
    if(!is_array($array)){$array=array();}
	if(is_array($array)){
		while (list ($ssid, $array2) = each ($array) ){
			if($array2["ENABLED"]==1){
				$CONFIG=$array2;
				$ESSID=$ssid;
				break;	
			}
		}
	}	
	if($ESSID==null){return;}
	echo "Starting......: ".date("H:i:s")." WIFI Access Point: \"$ESSID\"\n";
	$password=$CONFIG["ESSID_PASSWORD"];
	$COUNTRY=$CONFIG["COUNTRY"];
	if($COUNTRY==null){$COUNTRY="US";}
	$eth=$unix->GET_WIRELESS_CARD();
	$wpa_cli=$unix->find_program("wpa_cli");
	$ifconfig=$unix->find_program("ifconfig");
	$dhclient=$unix->find_program("dhclient");
	$route=$unix->find_program("route");
	$nohup=$unix->find_program("nohup");
	
	if($GLOBALS["VERBOSE"]){
		echo "using NIC:$eth\n";
		echo "using wpa_cli:$wpa_cli\n";
		echo "using ifconfig:$ifconfig\n";
		echo "using dhclient:$dhclient\n";
		echo "using route:$route\n";
		echo "using nohup:$nohup\n";
		
	}
	
	exec("$wpa_cli -p/var/run/wpa_supplicant -i{$eth} remove_network 0",$results);
	$echo=implode("",$results);
	if(trim($echo)<>null){$r[]="add_network: $echo";}
	unset($results);	
	
	exec("$wpa_cli -p/var/run/wpa_supplicant -i{$eth} add_network 0",$results);
	$echo=implode("",$results);
	if(trim($echo)<>null){$r[]="add_network: $echo";}
	unset($results);
	
	exec("$wpa_cli -p/var/run/wpa_supplicant -i{$eth} set_network 0 ssid \\\"\"$ESSID\"\\\"",$results);
	$echo=implode("",$results);
	if(trim($echo)<>null){$r[]="ssid: $echo";}
	unset($results);
	
	exec("$wpa_cli -p/var/run/wpa_supplicant -i{$eth} set_network 0 key_mgmt WPA-PSK",$results);
	$echo=implode("",$results);
	if(trim($echo)<>null){$r[]="key_mgmt: $echo";}
	unset($results);
	
	exec("$wpa_cli -p/var/run/wpa_supplicant -i{$eth} set_network 0 pairwise TKIP",$results);
	$echo=implode("",$results);
	if(trim($echo)<>null){$r[]="pairwise: $echo";}
	unset($results);
	
	exec("$wpa_cli -p/var/run/wpa_supplicant -i{$eth} set_network 0 group TKIP",$results);
	$echo=implode("",$results);
	if(trim($echo)<>null){$r[]="group: $echo";}
	unset($results);
	
	exec("$wpa_cli -p/var/run/wpa_supplicant -i{$eth} set_network 0 proto WPA",$results);	
	$echo=implode("",$results);
	if(trim($echo)<>null){$r[]="WPA: $echo";}
	unset($results);
	
	if($GLOBALS["VERBOSE"]){echo "$wpa_cli -p/var/run/wpa_supplicant -i{$eth} set_network 0 psk \\\"\"$password\"\\\""."\n";}
	exec("$wpa_cli -p/var/run/wpa_supplicant -i{$eth} set_network 0 psk \\\"\"$password\"\\\"",$results);
	$echo=implode("",$results);
	if(trim($echo)<>null){$r[]="psk (password): $echo";}	
	unset($results);
	
	exec("$wpa_cli -p/var/run/wpa_supplicant -i{$eth} enable_network 0",$results);	
	$echo=implode("",$results);
	if(trim($echo)<>null){$r[]="enable_network: $echo";}
	unset($results);	
	
	exec("$wpa_cli -p/var/run/wpa_supplicant save_config",$results);	
	$echo=implode("",$results);
	if(trim($echo)<>null){$r[]="save_config: $echo";}
	unset($results);

	exec("$wpa_cli -p/var/run/wpa_supplicant -i{$eth} reconnect",$results);	
	$echo=implode("",$results);
	if(trim($echo)<>null){$r[]="reconnect: $echo";}
	unset($results);
	foreach ($r as $a){
		echo "Starting......: ".date("H:i:s")." WIFI Access Point: $a\n";
	}
	
	system("$ifconfig $eth up");
	
	if($CONFIG["UseDhcp"]==1){
		echo "Starting......: ".date("H:i:s")." WIFI Access Point using DHCP\n";
		system("$nohup $dhclient $eth >/dev/null 2>&1 &");
		return;
	}
	
	if(!preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)#",$CONFIG["ip_address"],$re)){
		echo "Starting......: ".date("H:i:s")." WIFI Access Point bad IP address format\n";
		return;
	}	
	$sub="{$re[1]}.{$re[2]}.{$re[3]}.0";
	
	system("$ifconfig $eth {$CONFIG["ip_address"]} netmask {$CONFIG["mask"]}");
	system("$route add default gw {$CONFIG["gateway"]} $eth");
}

function wpa_removenetworks(){
	$unix=new unix();
	$wpa_cli=$unix->find_program("wpa_cli");
	$cmd="$wpa_cli -p/var/run/wpa_supplicant list_networks";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	if($GLOBALS["VERBOSE"]){echo count($results)." lines\n";}
	foreach ($results as $index=>$line){
		if($GLOBALS["VERBOSE"]){echo "$line\n";}
		if(preg_match("#^([0-9]+)\s+#",$line,$re)){
			echo "Starting......: ".date("H:i:s")." WIFI Access Point remove {$re[1]} Access Point\n";
			shell_exec("$wpa_cli -p/var/run/wpa_supplicant remove_network {$re[1]}");
		}
	}
	unset($results);
	exec("$wpa_cli -p/var/run/wpa_supplicant save_config",$results);
	$echo=implode("",$results);
	if(trim($echo)<>null){if($GLOBALS["VERBOSE"]){echo "$echo\n";}}
}

function CheckConnection(){
	$sock=new sockets();
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	$array=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WifiAccessPoint")));
    if(!is_array($array)){$array=array();}
	$WifiAPEnable=$sock->GET_INFO("WifiAPEnable");
	$eth=trim($unix->GET_WIRELESS_CARD());
	if(!is_array($array)){return false;}	
	if($eth==null){return false;}	
	if($WifiAPEnable<>1){return false;}
	$wpa_cli=$unix->find_program("wpa_cli");
	$ifconfig=$unix->find_program("ifconfig");
	
	exec("$wpa_cli -p/var/run/wpa_supplicant status -i{$eth}",$results);
	$conf="[IF]\n".implode("\n",$results);
	
	
	$ini=new Bs_IniHandler();
	$ini->loadString($conf);
	$ip=$ini->_params["IF"]["ip_address"];
	$status=$ini->_params["IF"]["wpa_state"];
	writelogs_framework("$eth: state= $status",__FUNCTION__,__FILE__,__LINE__);
	switch ($status) {
		
		case "SCANNING":
			writelogs("$eth: up interface...",__FUNCTION__,__FILE__,__LINE__);
			shell_exec("$ifconfig $eth up");break;
		
		case "COMPLETED":return;break;
		
		case "INACTIVE":
			writelogs_framework("$eth: -> ConnectToAccessPoint()",__FUNCTION__,__FILE__,__LINE__);
			ConnectToAccessPoint();
			return;break;
		default:ConnectToAccessPoint();
			writelogs_framework("$eth: -> ConnectToAccessPoint()",__FUNCTION__,__FILE__,__LINE__);
			return;
			break;
		
	}	
}
	
	



?>