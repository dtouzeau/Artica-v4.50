<?php

include_once(dirname(__FILE__).'/class.users.menus.inc');
include_once(dirname(__FILE__).'/class.system.network.inc');
include_once(dirname(__FILE__).'/class.ldap.inc');
include_once(dirname(__FILE__).'/class.ini.inc');
include_once(dirname(__FILE__) .'/class.tcpip.inc');
include_once(dirname(__FILE__) .'/class.mysql.inc');
include_once(dirname(__FILE__) .'/class.dhcpd-sub.inc');
include_once(dirname(__FILE__) .'/class.dhcpd-socks.inc');
include_once(dirname(__FILE__) .'/class.computers.inc');
include_once(dirname(__FILE__) .'/class.mysql.powerdns.inc');

class dhcpd_hosts{
	public $uid;
	public $dn;
	public $dhcpHWAddress;
	public $dhcpStatements;
	public $dds_domain;
	public $hostname;
	public $IP;
	public $MAC;
	
	function __construct($uid=null){
		if(!isset($GLOBALS["ASROOT"])){$GLOBALS["ASROOT"]=false;}
		if($uid==null){
			writelogs("DHCP:: Resquest an uid null, aborting",__CLASS__.'/'.__FUNCTION__,__FILE__);
			return null;
		}
		$this->uid=$uid;	
		$this->load();
		
	}
	
	private function load(){
		if(substr($this->uid,0,1)=='$'){$this->uid=substr($this->uid,1,strlen($this->uid));}
		if(substr($this->uid,strlen($this->uid)-1,1)=='$'){$this->uid=substr($this->uid,0,strlen($this->uid)-1);}
		$ldap=new clladp();
		$this->dn="cn=$this->uid,cn=DhcpConfig,ou=dhcp,$ldap->suffix";
		if(!$ldap->ExistsDN($this->dn)){return null;}
		$hash=$ldap->Ldap_read($this->dn,"(objectclass=*)",array());
		$this->dhcpHWAddress=$hash[0][strtolower("dhcpHWAddress")][0];
		$this->dhcpStatements=$hash[0][strtolower("dhcpStatements")][0];
	
		
	}
	
function DeleteFixed(){
	$ldap=new clladp();
	if(!$ldap->ExistsDN($this->dn)){return true;}
	$tpl=new templates();
	
	include_once(dirname(__FILE__)."/class.mysql.inc");;
	$sql="DELETE FROM dhcpd_fixed WHERE mac='$this->MAC'";
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error." in ".__CLASS__."/".__FUNCTION__." line ".__LINE__;}	
	
	if(!$ldap->ldap_delete($this->dn)){
		echo $tpl->_ENGINE_parse_body("DHCP:: {error} function:".__FUNCTION__."\nfile: ".__FILE__."\nline:".__LINE__."\n");
		echo $ldap->ldap_last_error;
		return false;
	}

	
	return true;
	
}	

	
}


class dhcpd{
	public $ddns_domainname="";
	public $max_lease_time;
	public $array_tcp;
	public $listen_nic;
	public $netmask;
	public $range2;
	public $range1;
	public $DNS_1;
	public $DNS_2;
	public $EnableArticaAsGateway=0;
	public $EnableArticaAsDNSFirst=0;
	public $do_no_verify_range=0;
	public $gateway;
	public $pxe_enable=0;
	public $pxe_server=null;
	public $pxe_file=null;
	public $conf;
	public $routes=array();
	public $ntp_server=null;
	public $OnlySetGateway=0;
	public $subnet;
	public $broadcast=null;
    public $deny_unkown_clients=0;
	public $WINS=null;
	public $noload=0;
	public $authoritative=0;
	public $ping_check=0;
	public $noloadFixed=0;
	public $local_pac_server=null;
	public $browser_portal_page=null;
	public $get_lease_hostnames=0;
	public $service_enabled=0;
    public $EnableVLANs=0;
    public $vlan_id=0;
	
	function __construct($noload=0,$noloadFixed=0,$interface=null){
		if($noload<>0){$this->noload=$noload;return ;}
		$this->noloadFixed=$noloadFixed;
		$sock=new sockets();
		$ini=new Bs_IniHandler();
		$ipClass=new IP();

		
		$this->EnableArticaAsGateway=$sock->GET_INFO("EnableArticaAsGateway");
        $this->EnableVLANs=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVLANs");


		$dhcp_socks=new dhcp_socks($interface);
        if($this->EnableVLANs==1){
            $this->vlan_id=$dhcp_socks->GET("vlan_id");
        }

        $this->ddns_domainname=$dhcp_socks->GET("ddns_domainname");
        $this->max_lease_time=$dhcp_socks->GET("max_lease_time");
        $this->listen_nic=$dhcp_socks->GET("listen_nic");
        $this->netmask=$dhcp_socks->GET("netmask");
        $this->range1=$dhcp_socks->GET("range1");
        $this->range2=$dhcp_socks->GET("range2");
        $this->EnableArticaAsDNSFirst=intval($dhcp_socks->GET("EnableArticaAsDNSFirst"));
		$this->gateway=$dhcp_socks->GET("gateway");
		$this->DNS_1=$dhcp_socks->GET("DNS_1");
		$this->DNS_2=$dhcp_socks->GET("DNS_2");
		$this->pxe_server=$dhcp_socks->GET("pxe_server");
		$this->pxe_file=$dhcp_socks->GET("pxe_file");
		$this->pxe_enable=$dhcp_socks->GET("pxe_enable");
		$this->ntp_server=$dhcp_socks->GET("ntp_server");
		$this->subnet=$dhcp_socks->GET("subnet");
		$this->broadcast=$dhcp_socks->GET("broadcast");
		$this->WINS=$dhcp_socks->GET("WINS");
		$this->authoritative=$dhcp_socks->GET("authoritative");
		$this->local_pac_server=$dhcp_socks->GET("local_pac_server");
		$this->get_lease_hostnames=$dhcp_socks->GET("get_lease_hostnames");
		$this->deny_unkown_clients=intval($dhcp_socks->GET("deny_unkown_clients"));
		$this->browser_portal_page=$dhcp_socks->GET("browser_portal_page");
        $this->do_no_verify_range=intval($dhcp_socks->GET("do_no_verify_range"));
        $this->ping_check=trim($dhcp_socks->GET("ping_check"));
        $this->service_enabled=intval($dhcp_socks->GET("service_enabled"));
		
		if($this->listen_nic==null){$this->GetDefaultNic();}
		
		if(trim($this->subnet)==null){
			if($ipClass->isIPv4($this->gateway)){
				if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)#',trim($this->gateway),$re)){
					$this->subnet="{$re[1]}.{$re[2]}.{$re[3]}.0";
					if(trim($this->range1)==null){$this->range1="{$re[1]}.{$re[2]}.{$re[3]}.50";}
					if(trim($this->range2)==null){$this->range2="{$re[1]}.{$re[2]}.{$re[3]}.254";}
				}
			}
		}
		
		if(!$ipClass->isIPv4($this->range1)){
			if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)#',trim($this->subnet),$re)){
				$this->range1="{$re[1]}.{$re[2]}.{$re[3]}.50";
			}
		}
		if(!$ipClass->isIPv4($this->range2)){
			if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)#',trim($this->subnet),$re)){
				$this->range2="{$re[1]}.{$re[2]}.{$re[3]}.254";
			}			
		}
		

		if(!is_numeric($this->authoritative)){$this->authoritative=0;}
		if(!is_numeric($this->get_lease_hostnames)){$this->get_lease_hostnames=0;}
		if(!is_numeric($this->ping_check)){$this->ping_check=1;}
		
		$this->OnlySetGateway=0;
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [INIT]: DHCP Server server DEBUG: loading OnlySetGateway = [DHCPOnlySetGateway]  $this->OnlySetGateway\n";}
		$ip=new networking();
		$ip->LoadIPs();
		$this->array_tcp=$ip->Local_interfaces();
		if($this->pxe_file==null){$this->pxe_file="/tftpboot/pxelinux.0";}
		if($this->max_lease_time==null){$this->max_lease_time=3600;}
		if($this->netmask==null){$this->netmask="255.255.255.0";}
		
		$this->LoadRoutes();
		$GLOBALS["___"]=$this->RemoteVPN();


	}
	
	function Save($norestart=false,$InterfaceService=null){
		writelogs("Save Config file ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		if($this->noload<>0){return;}
		if(!is_numeric($this->authoritative)){$this->authoritative=0;}
		if(!is_numeric($this->ping_check)){$this->ping_check=1;}
		
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $this->range1)){
			if($this->listen_nic<>null){
				$tcp=new networking();
				$tcp->ifconfig($this->listen_nic);
				$tcp_addr=$tcp->tcp_addr;
				preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$#", $tcp_addr,$ri);
				$this->range1="{$ri[1]}.{$ri[2]}.{$ri[3]}.50";
				$this->range2="{$ri[1]}.{$ri[2]}.{$ri[3]}.253";
				
			}
			
		}
		
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $this->gateway)){
			if($this->listen_nic<>null){
				$tcp=new networking();
				$tcp->ifconfig($this->listen_nic);
				$this->gateway=$tcp->gateway;
			}
		}
		
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $this->DNS_1)){
			if($this->listen_nic<>null){
				$tcp=new networking();
				$tcp->ifconfig($this->listen_nic);
				$this->DNS_1=$tcp->tcp_addr;
			}
		}

        $dhcp_socks=new dhcp_socks($InterfaceService);
        $dhcp_socks->SET("ddns_domainname",$this->ddns_domainname);
        $dhcp_socks->SET("max_lease_time",$this->max_lease_time);
        $dhcp_socks->SET("listen_nic",$this->listen_nic);
        $dhcp_socks->SET("netmask",$this->netmask);
        $dhcp_socks->SET("range1",$this->range1);
        $dhcp_socks->SET("range2",$this->range2);
        $dhcp_socks->SET("gateway",$this->gateway);
        $dhcp_socks->SET("DNS_1",$this->DNS_1);
        $dhcp_socks->SET("DNS_2",$this->DNS_2);
        $dhcp_socks->SET("pxe_server",$this->pxe_server);
        $dhcp_socks->SET("pxe_file",$this->pxe_file);
        $dhcp_socks->SET("pxe_enable",$this->pxe_enable);
        $dhcp_socks->SET("EnableArticaAsDNSFirst",$this->EnableArticaAsDNSFirst);
        $dhcp_socks->SET("ntp_server",$this->ntp_server);
        $dhcp_socks->SET("subnet",$this->subnet);
        $dhcp_socks->SET("broadcast",$this->broadcast);
        $dhcp_socks->SET("WINS",$this->WINS);
        $dhcp_socks->SET("authoritative",$this->authoritative);
        $dhcp_socks->SET("ping_check",$this->ping_check);
        $dhcp_socks->SET("local_pac_server",$this->local_pac_server);
        $dhcp_socks->SET("get_lease_hostnames",$this->get_lease_hostnames);
        $dhcp_socks->SET("do_no_verify_range",$this->do_no_verify_range);
        $dhcp_socks->SET("deny_unkown_clients",$this->deny_unkown_clients);
        $dhcp_socks->SET("browser_portal_page",$this->browser_portal_page);
        $dhcp_socks->SET("service_enabled",$this->service_enabled);
        $dhcp_socks->SET("vlan_id",$this->vlan_id);

        if(!$norestart){
			$GLOBALS["CLASS_SOCKETS"]->getFrameWork('cmd.php?restart-dhcpd=yes');
		}
		
		
		
	}
	




function AddRoute($ip,$netmask,$gateway,$info=null){
	$this->LoadRoutes();
	if(!is_array($this->routes[$ip])){
		$this->routes[$ip]=array($netmask,$gateway,$info);
		$this->SaveRoutes();
		$this->Save();
	}
}

function DelRoute($ip){
	$this->LoadRoutes();
    foreach ($this->routes as $ipr=>$array){
		if($ipr==$ip){
			writelogs("Delete Gateway $ip (".$this->routes[$ip][0].")",__CLASS__.'/'.__FUNCTION__,__FILE__);
			continue;
		}
		$arrayF[]="$ipr;{$array[0]};{$array[1]}";
	}
	
	if(is_array($arrayF)){$conf=implode("\n",$arrayF);}
	$sock=new sockets();
	$sock->SaveConfigFile($conf,"DHCPDRoutesInfos");
}

private function LoadRoutes(){
	
	if(isset($GLOBALS["DHCPD_ROUTES"])){
        foreach ($GLOBALS["DHCPD_ROUTES"] as $num=>$ligne){
			$this->routes[$num]=$ligne;
		}
		return;
	}
	$GLOBALS["DHCPD_ROUTES"]=array();
	$sock=new sockets();
	$datas=explode("\n",$sock->GET_INFO("DHCPDRoutesInfos"));
	foreach ($datas as $num=>$ligne){
		$tbl=explode(";",$ligne);
		if(count($tbl)>1){
			$this->routes[$tbl[0]]=array($tbl[1],$tbl[2]);
			$GLOBALS["DHCPD_ROUTES"][$tbl[0]]=array($tbl[1],$tbl[2]);
		}
		
	}
	
}

private function SaveRoutes(){
	if(is_array($this->routes)){
	reset($this->routes);
	$conf=null;
    foreach ($this->routes as $ip=>$array){
		$ll="$ip;{$array[0]};{$array[1]};{$array[2]}";
		writelogs("$ll",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$arrayF[]="$ip;{$array[0]};{$array[1]};{$array[2]}";
	}
		if(is_array($arrayF)){
			$conf=implode("\n",$arrayF);
		}
	}
	
	
	
	$sock=new sockets();
	$sock->SaveConfigFile($conf,"DHCPDRoutesInfos");
}







	private function GetDefaultNic(){
        $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/listnics"));
        $devs=unserialize(base64_decode($data->nics));

		if(!is_array($devs)){
			if($GLOBALS["VERBOSE"]){echo "WARN!!: \$dev not an array\n";}
			return null;}
			if($GLOBALS["VERBOSE"]){echo "DEBUG:: GetDefaultNic() -> {$devs[0]}\n";}
			$this->listen_nic=$devs[0];
		return $devs[0];
	}
	
	
	function BuildConf(){
        $this->SetFixedAddresses();



		
	}
	
	public function ExplodeMyRange($interface="default"){

	    if($interface<>"default") {
            $dhcp_sock = new dhcp_socks($interface);
            $this->subnet = $dhcp_sock->GET("subnet");
            $this->range1 = $dhcp_sock->GET("range1");
            $this->broadcast = $dhcp_sock->GET("broadcast");
        }

		if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',trim($this->subnet),$re)){return $re;}
		if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',trim($this->range1),$re)){return $re;}
		if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',trim($this->broadcast),$re)){return $re;}
	}
	
	
	public function  IsPhysicalAddress($address){
		$address=strtoupper(trim($address));
		if($address=="UNKNOWN"){return null;}
		
		if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					} catch (Exception $e) {writelogs("IsPhysicalAddress:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}			
		
		$address=str_replace(":","-",$address);
		If(strlen($address) > 18){
			writelogs("`$address` too many caracters, exceed 18 receive ".strlen($address)." $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		If($address == ""){
			writelogs("$address is null !",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		If(!preg_match("#^[0-9A-Z]+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)$#i",$address)){
			writelogs("$address did not match pattern $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		$Array=explode("-",$address);
		If(strlen($Array[0]) != 2){
			writelogs("$address: array 0 did not match 2 $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		If(strlen($Array[1]) != 2){
			writelogs("$address:array 1 did not match 2 $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		If(strlen($Array[2]) != 2){
			writelogs("$address:array 2 did not match 2 $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		If(strlen($Array[3]) != 2){
			writelogs("$address:array 3 did not match 2 $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		If(strlen($Array[4]) != 2){
			writelogs("$address:array 4 did not match 2 $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		If(strlen($Array[5]) != 2){
			writelogs("$address:array 5 did not match 2 $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		
		return true;
	}	
	
	private function validhostname($hostname){
		$hostname=trim($hostname);
		if(strpos(" $hostname", "(")>0){return false;}
		if(strpos(" $hostname", ")")>0){return false;}
		if(strpos(" $hostname", "[")>0){return false;}
		if(strpos(" $hostname", "]")>0){return false;}
		if(strpos(" $hostname", ";")>0){return false;}
		if(strpos(" $hostname", ",")>0){return false;}
		if(strpos(" $hostname", "?")>0){return false;}
		if(strpos(" $hostname", "$")>0){return false;}
		if(strpos(" $hostname", "%")>0){return false;}
		if(strpos(" $hostname", "é")>0){return false;}
		if(strpos(" $hostname", "è")>0){return false;}
		if(strpos(" $hostname", "à")>0){return false;}
		if(strpos(" $hostname", "@")>0){return false;}
		if(strpos(" $hostname", "/")>0){return false;}
		if(strpos(" $hostname", ":")>0){return false;}
		if(strpos(" $hostname", " ")>0){return false;}
		if(strpos(" $hostname", "\\")>0){return false;}
		if(strpos(" $hostname", "*")>0){return false;}
		if(strpos(" $hostname", "+")>0){return false;}
		if(strpos(" $hostname", "=")>0){return false;}
		if(strpos(" $hostname", "°")>0){return false;}
		if(strpos(" $hostname", "&")>0){return false;}
		if(strpos(" $hostname", "'")>0){return false;}
		if(strpos(" $hostname", '"')>0){return false;}
		if(strpos(" $hostname", '|')>0){return false;}
		if(strpos(" $hostname", '`')>0){return false;}
		if(strpos(" $hostname", '^')>0){return false;}
		if(strpos(" $hostname", '}')>0){return false;}
		if(strpos(" $hostname", '{')>0){return false;}
		return true;
	}
	
	
	private function SetFixedAddresses(){
		$sock=new sockets();
		$conf=array();
		$EnableDHCPUseHostnameOnFixed=$sock->GET_INFO("EnableDHCPUseHostnameOnFixed");
		$IncludeDHCPLdapDatabase=intval($sock->GET_INFO("IncludeDHCPLdapDatabase"));
		if($IncludeDHCPLdapDatabase==0){return;}
		
		$ipClass=new IP();
		$sql="SELECT * FROM hostsnet WHERE dhcpfixed=1";
		$q=new postgres_sql();
		$results = $q->QUERY_SQL($sql);
		$ipClass=new IP();
        if($results) {
            $conf[] = "# " . pg_num_rows($results) . " fixed items\n";
        }
		$c=0;
		while ($ligne = pg_fetch_assoc($results)) {
			$c++;
			$nameservers=null;
			if($ligne["gateway"]=="0.0.0.0"){$ligne["gateway"]=null;}
			if($ligne["gateway2"]=="0.0.0.0"){$ligne["gateway2"]=null;}
			if($ligne["dns1"]=="0.0.0.0"){$ligne["dns1"]=null;}
			if($ligne["dns2"]=="0.0.0.0"){$ligne["dns2"]=null;}
			if($ligne["pxe_server"]=="0.0.0.0"){$ligne["pxe_server"]=null;}


			
			
			$dns1=$ligne["dns1"];
			$dns2=$ligne["dns2"];
			$ComputerMacAddress=$ligne["mac"];
			$hostname=$ligne["hostname"];
			$hostname=trim($hostname);
			$hostname=str_replace(" ", "-", $hostname);
			$ip_addr=$ligne["ipaddr"];
			$IP=$ligne["ipaddr"];
			$domainname=$ligne["domainname"];
			if(preg_match("#localhost#",$domainname)){$domainname=null;}
				
			$gateway=$ligne["gateway"];
			$gateway2=$ligne["gateway2"];
			
			
			if(trim($domainname)==null){$domainname=$this->ddns_domainname;}
			if($ip_addr=="127.0.0.1"){$ip_addr=null;}
			if($ip_addr=="0.0.0.0"){$ip_addr=null;}

			if($GLOBALS["VERBOSE"]){echo "$ComputerMacAddress `$hostname` -> $ip_addr\n";}
			if(!$this->validhostname($hostname)){
				$conf[]="#$ComputerMacAddress $ip_addr:[$hostname] Invalid hostname";
				continue;
			}
			$conf[]="# $ComputerMacAddress $ip_addr:[$hostname] domain:$domainname gw:{$ligne["gateway"]}";
			
			$GW=array();
			$NMS=array();

			if($ipClass->isIPv4($ligne["gateway"])){$GW[]=$ligne["gateway"];}
			if($ipClass->isIPv4($ligne["gateway2"])){$GW[]=$ligne["gateway2"];}
			
			
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", trim($hostname))){$conf[]="# Skip $hostname , match ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$\n";continue;}
			
			$IP=trim(str_replace("fixed-address","",$IP));
			

			
			if(isset($DUPHOSTS[$hostname])){
				$conf[]="# $ComputerMacAddress $hostname duplicate ->$hostname-$c";
				$hostname="$hostname-$c";
			}
			$DUPHOSTS[$hostname]=true;
			
			$conf[]="host $hostname{\n";
			$conf[]="\thardware ethernet $ComputerMacAddress;";
			if($EnableDHCPUseHostnameOnFixed==1){
					$hostnameRes=gethostbyaddr($IP);
					if($hostnameRes<>$IP){
						if($ipClass->isIPv4($hostnameRes)){$conf[]="\tfixed-address $hostnameRes;";}
					}else{
						if($ipClass->isIPv4($ip_addr)){$conf[]="\tfixed-address $ip_addr;";}
					}
			}else{
				if($ipClass->isIPv4($ip_addr)){$conf[]="\tfixed-address $ip_addr;";}
			}
				
				
				if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $hostname)){
					$conf[]="\toption host-name \"$hostname\";";
				}
				if($domainname<>null){$conf[]="\toption domain-name \"$domainname\";";}
				
				
				if($ipClass->isIPv4($dns1)){$NMS[]=$dns1;}
				if($ipClass->isIPv4($dns2)){$NMS[]=$dns2;}
				
				if(count($GW)>0){
					$conf[]="\toption routers ".@implode(",", $GW).";";
				}

				if($ligne["time-servers"]<>null){
					$conf[]="\toption time-servers {$ligne["time-servers"]};";
				}	

				if(count($NMS)>0){
					$conf[]="\toption domain-name-servers ".@implode(",", $NMS).";";
				}

				if($ligne["ntp-servers"]<>null){
					$conf[]="\toption ntp-servers {$ligne["ntp-servers"]};";
				}	
				
				if(intval($ligne["pxe_enabled"])==1){
					if($ipClass->isIPv4($ligne["pxe_server"])){
						$conf[]="\tnext-server {$ligne["pxe_server"]};";
						$conf[]="\tfilename \"{$ligne["pxe_file"]}\";";
					}
				}

			
				$conf[]="}\n";
				
		
			}

		@file_put_contents("/etc/dhcpd-reservations.conf",@implode("\n", $conf)."\n");

		
	}
	

	
	
	function GetKey($domain){
		$sock=new sockets();
		$md=md5($domain);
        $conf=null;
		$datas=$sock->GET_INFO("Bind9DnsKey.$md.key");
		if($datas==null){
			$key=$sock->getfile("Bind9Key:$domain");
			$conf=$conf."[KEY]\n";
			$conf=$conf."";
		}
		
		
	}
	
	private function RemoteVPN(){
	    return false;
			if(isset($GLOBALS["DHCPD_RemoteVPN"])){return;}
			$GLOBALS["DHCPD_RemoteVPN"]=true;
			
			if($this->listen_nic==null){$this->GetDefaultNic();}
			$ip=new networking();
			$array=$ip->array_TCP;
			
			$gateway=$array[$this->listen_nic];
			$sql="SELECT IP_START,netmask,sitename  FROM vpnclient WHERE connexion_type=1 ORDER BY sitename DESC";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
				writelogs("find route on vpn=>{$ligne["IP_START"]}=>{$ligne["netmask"]}=>$gateway",__CLASS__.'/'.__FUNCTION__,__FILE__);
				$this->AddRoute($ligne["IP_START"],$ligne["netmask"],$gateway,"VPN ".$ligne["sitename"]);
			}
	}

	
	
	private function shared_networks(){
		$q=new mysql();
		$sql="SELECT sharednet_name FROM dhcpd_sharednets GROUP BY sharednet_name ORDER BY sharednet_name";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(mysqli_num_rows($results)==0){return null;}
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$content=$this->shared_networks_sub($ligne["sharednet_name"]);
			if($content==null){continue;}
			$ligne["sharednet_name"]=trim($ligne["sharednet_name"]);
			if($ligne["sharednet_name"]==null){continue;}
			$ligne["sharednet_name"]=str_replace(" ", "-", $ligne["sharednet_name"]);
			$f[]="shared-network {$ligne["sharednet_name"]} {";
			$f[]=$content;
			$f[]="}\n";
		}
		
		return @implode("\n", $f);		
	}
	
	
	private function shared_networks_sub($mainet){
		if($GLOBALS["VERBOSE"]){echo "->shared_networks_sub() -> $mainet item Line ".__LINE__."\n";}
		$q=new mysql();
		$spacer="    ";
		$sql="SELECT * FROM dhcpd_sharednets WHERE sharednet_name='$mainet'";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(mysqli_num_rows($results)==0){return null;}
		$TcpClass=new IP();
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$dns=array();
			foreach ($ligne as $a=>$b){$ligne[$a]=trim($b);}
			
			
			$subnet=$ligne["subnet"];
			$netmask=$ligne["subnet"];
			$range1=$ligne["range1"];
			$range2=$ligne["range2"];
			
			if(!$TcpClass->isIPAddress($subnet)){$subnet=null;}
			if(!$TcpClass->isIPAddress($netmask)){$netmask=null;}
			if(!$TcpClass->isIPAddress($range1)){$range1=null;}
			if(!$TcpClass->isIPAddress($range2)){$range2=null;}
			if(!$TcpClass->isIPAddress($ligne["domain-name-servers1"])){$ligne["domain-name-servers1"]=null;}
			if(!$TcpClass->isIPAddress($ligne["domain-name-servers2"])){$ligne["domain-name-servers2"]=null;}
			if(!$TcpClass->isIPAddress($ligne["wins-server"])){$ligne["wins-server"]=null;}
			if(!$TcpClass->isIPAddress($ligne["routers"])){$ligne["routers"]=null;}
			if(!$TcpClass->isIPAddress($ligne["routers"])){$ligne["routers"]=null;}
			
			if($subnet==null){continue;}
			if($netmask==null){continue;}
			if($range1==null){continue;}
			if($range2==null){continue;}	
			
			
			
			if(trim($ligne["domain-name-servers1"])<>null){$dns[]=trim($ligne["domain-name-servers1"]);}
			if(trim($ligne["domain-name-servers2"])<>null){$dns[]=trim($ligne["domain-name-servers2"]);}
			
			$f[]="#Scope \"{$ligne["scopename"]}\"";
			if($GLOBALS["VERBOSE"]){echo "->shared_networks_sub() -> [{$ligne["scopename"]}] subnet $subnet netmask $netmask Line ".__LINE__."\n";}
			$f[]="{$spacer}subnet $subnet netmask $netmask {";
			$f[]="{$spacer}{$spacer}range $range1 $range2;";
			if(trim($ligne["subnet-mask"])<>null){$f[]="{$spacer}{$spacer}option subnet-mask {$ligne["subnet-mask"]};";}
			if(trim($ligne["domain-name"])<>null){$f[]="{$spacer}{$spacer}option domain-name \"{$ligne["domain-name"]}\";";}
			if(trim($ligne["routers"])<>null){$f[]="{$spacer}{$spacer}option routers {$ligne["routers"]};";}
			
			if(trim($ligne["option-176"])<>null){
				$ligne["option-176"]=str_replace("\n", "", $ligne["option-176"]);
				$f[]="{$spacer}{$spacer}option option-176 \"{$ligne["option-176"]}\";";
			}
			if(count($dns)>0){$f[]="{$spacer}{$spacer}option domain-name-servers ". @implode(", ", $dns).";";}
			if(trim($ligne["wins-server"])<>null){$f[]="{$spacer}{$spacer}option netbios-name-servers {$ligne["wins-server"]};";}
			if(trim($ligne["tftp-server-name"])<>null){$f[]="{$spacer}{$spacer}option tftp-server-name \"{$ligne["tftp-server-name"]}\";";}
			if(trim($ligne["server-name"])<>null){$f[]="{$spacer}{$spacer}option server-name \"{$ligne["server-name"]}\";";}
			if(trim($ligne["next-server"])<>null){$f[]="{$spacer}{$spacer}option next-server {$ligne["next-server"]};";}
			if(trim($ligne["pxe_filename"])<>null){$f[]="{$spacer}{$spacer}option filename \"{$ligne["pxe_filename"]}\";";}
			if(trim($ligne["pxe_root-path"])<>null){$f[]="{$spacer}{$spacer}option root-path \"{$ligne["pxe_root-path"]}\";";}
			
			$f[]="{$spacer}}\n";
			}	
		
		return @implode("\n",$f);
	}
	
	
	
}

?>