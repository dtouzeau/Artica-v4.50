<?php
if(function_exists("posix_getuid")){
    if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}
    }
}
include_once(dirname(__FILE__)."/class.resolv.conf.inc");

class networking{
	public $array_conf;
	public $array_TCP;
	public $array_local_aliases;
	public $ldap_error;
	public $arrayNameServers;
	public $hostname;
	
	public $tcp_addr="0.0.0.0";
	public $wireless=false;
	public $netmask="0.0.0.0";
	public $gateway="0.0.0.0";
	public $linkup=false;
	public $mac_addr=null;
	
	function __construct(){
			include_once(dirname(__FILE__) . '/class.tcpip.inc');
			if(isset($GLOBALS["__CLASS-networking"])){
			foreach (get_object_vars($GLOBALS["__CLASS-networking"]) as $key => $value){$this->$key = $value;}
			if($GLOBALS["DEBUG_MEM"]){
				if(function_exists("events")){events("Memory: class.system.network.inc -> networking class() -> ".round(((memory_get_usage()/1024)/1000),2) ." line:".__LINE__);}
				}
			return null;
			}		
		
		
		$this->LoadNameServers();
		$this->LoadIPs();
		$GLOBALS["__CLASS-networking"]=$this;

	}

    public function InterfaceToIPv4($nicname):string{
        $Nets=$this->Local_interfaces(false,true);
        return strval($Nets[$nicname]);
    }
  public function NETWORK_ALL_INTERFACES():array{
        if(!$GLOBALS["AS_ROOT"]){
  		    $sock=new sockets();
  		    $INTERFACES=unserialize(base64_decode($sock->getFrameWork("cmd.php?TCP_NICS_STATUS_ARRAY=yes")));
        }else{
  		    $unix=new unix();
  		    $INTERFACES=$unix->NETWORK_ALL_INTERFACES();
  	    }
        if(!is_array($INTERFACES)){return array();}
        return $INTERFACES;
    }
	
  public function Local_interfaces($filter=false,$returnips=false):array{
        if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
        VERBOSE("Local_interfaces()",__LINE__);
        $results = array();
        $INTERFACEZ=array();

      $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interfaces");

      $json=json_decode($data);
      if (json_last_error()> JSON_ERROR_NONE) {
          VERBOSE("json status == FALSE",__LINE__);
          return array();
      }

      $EnableIwConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIwConfig"));

      foreach($json->interfaces as $ligne) {
          $Interface = $ligne->Name;
          if(function_exists("VERBOSE")){
              VERBOSE("Interface $Interface",__LINE__);
          }
          $dev_name = $Interface;
          if($dev_name=="lo"){continue;}
          if($filter){
              if(preg_match("#^sit#",trim($dev_name))){continue;}
              if(preg_match("#^lxc#",trim($dev_name))){continue;}
              if(preg_match("#^lo#",trim($dev_name))){continue;}
              if(preg_match("#^ham[0-9]+#",trim($dev_name))){continue;}
              if($EnableIwConfig==0){ if(preg_match("#^wlan[0-9]+#", $dev_name)){continue;} }
          }

          if (!is_array($ligne->Addresses)) {
              $ligne->Addresses = array();
              $ligne->Addresses[0]="0.0.0.0";
          }else{
              foreach ($ligne->Addresses as $index=>$address) {
                  if(preg_match("#^(.+?)\/#",$address,$m)){
                      $ligne->Addresses[$index]=$m[1];
                  }
              }
          }
          $Addresse=$ligne->Addresses[0];
          if(preg_match("#^(.+?)\/#",$Addresse,$m)){
              $Addresse=$m[1];
          }
          $INTERFACEZ[$Interface]=$ligne->Addresses[0];
          $results[trim($dev_name)] = $Addresse;
      }

      $keepalived_enable = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE");
      $keepalived_enable_slave = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE");
      if (intval($keepalived_enable) == 1 || intval($keepalived_enable_slave) == 1) {
          $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
          $sql = "SELECT * FROM `keepalived_primary_nodes` WHERE enable='1'";
          $results_sql = $q->QUERY_SQL($sql);
          if (!$q->ok) {
              echo $q->mysql_error_html();
          }
          foreach ($results_sql as $index => $ligne) {
              $sql_vip = "SELECT * FROM keepalived_virtual_interfaces WHERE primary_node_id='{$ligne["ID"]}' AND enable='1' ORDER BY ID";
              $results_vips = $q->QUERY_SQL($sql_vip);
              if (!$q->ok) {
                  echo $q->mysql_error_html();
              }
              foreach ($results_vips as $index => $vips) {
                  $virtual_ip=$vips["virtual_ip"];
                  $INTERFACEZ[$vips["label"]]=$virtual_ip;
                  $results[$vips["label"]] = trim("Failover Floating IP $virtual_ip");
              }
          }
      }
    
    if($returnips){return $INTERFACEZ;}
    return $results;
  }	

	

	function ALL_IPS_GET_ARRAY(){
        if(isset($GLOBALS["ALL_IPS_GET_ARRAY"])){
            if(count($GLOBALS["ALL_IPS_GET_ARRAY"])>0){
                return $GLOBALS["ALL_IPS_GET_ARRAY"];
            }
        }
        $f=explode("\n",@file_get_contents("/proc/net/fib_trie"));
        $array=array();
        $IPADDR=null;
        foreach ($f as $line){
            if(preg_match("#\/[0-9]+\s+host LOCAL#",$line,$re)){
                if($IPADDR==null){continue;}
                $array[$IPADDR]=true;
            }
            if(!preg_match("#\|--\s+([0-9\.]+)#",$line,$re)){continue;}
            $IPADDR=$re[1];
            if($IPADDR=="127.0.0.0"){$IPADDR=null;continue;}
            if($IPADDR=="0.0.0.0"){$IPADDR=null;continue;}
        }
        $GLOBALS["ALL_IPS_GET_ARRAY"]=array();
        foreach ($array as $ipaddr=>$none){
            $GLOBALS["ALL_IPS_GET_ARRAY"][$ipaddr]=$ipaddr;
            $this->array_TCP[$ipaddr]=$ipaddr;
        }
        return $GLOBALS["ALL_IPS_GET_ARRAY"];

	}
	
	function LoadIPs(){
		include_once(dirname(__FILE__) . '/class.sockets.inc');
		if(!class_exists("usersMenus")){include_once(dirname(__FILE__) . '/class.users.menus.inc');}
		if(isset($GLOBALS["CLASS_SOCKETS"])){$sock=$GLOBALS["CLASS_SOCKETS"];}else{$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}
		$users=new usersMenus();
		
		
		if($GLOBALS["AS_ROOT"]){
			if(class_exists('unix')){
				$unix=new unix();
				$this->hostname=$unix->FULL_HOSTNAME();
			}
		}else{
			$this->hostname=php_uname("n");
		}
		
		
		$this->ALL_IPS_GET_ARRAY();
		
	}

	public function checkIP($ip){
		$cIP = ip2long($ip);
		$fIP = long2ip($cIP);
		if($fIP=="0.0.0.0"){return false;}
		return true;
		}	
		
		private function DNS_LIST(){
		
			$f=explode("\n",@file_get_contents("/etc/resolv.conf"));
		
			foreach ( $f as $index=>$line ){
				$line=trim($line);
				if($line==null){continue;}
				if(preg_match("#nameserver\s+(.+)#", $line,$re)){
					if(trim($re[1])=="127.0.0.1"){continue;}
					$DNS[]=$re[1];
				}
		
			}
		
			return $DNS;
		
		}
	
	
		
	
	function LoadNameServers(){
		include_once(dirname(__FILE__) . '/class.sockets.inc');
		if(isset($GLOBALS["CLASS_SOCKETS"])){$sock=$GLOBALS["CLASS_SOCKETS"];}else{$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}
		$dnslist=$this->DNS_LIST();
		$ip=new IP();
		if(is_array($dnslist)){
			foreach ($dnslist as $val){
				$val=trim(strtolower($val));
				if($val<>null){
					if($GLOBALS["VERBOSE"]){if(function_exists("VERBOSE")){VERBOSE("LoadNameServers: $val", __LINE__);}}
					if($ip->isValid($val)){
						$arr[$val]=trim($val);
					}
					
				}}
			}
			
			if(!isset($arr)){return;}			
		    if(is_array($arr)){
		        foreach ($arr as $val){
					if($ip->isValid($val)){
						$this->arrayNameServers[]=$val;
					}
				}
		   	}
		}
    private function NicStatusOld($InterfaceName):bool{

         $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/nicstatus/$InterfaceName"));
         $nicinfos=$data->Info;

        $tbl=explode(";",$nicinfos);
        $this->tcp_addr=$tbl[0];
        $this->mac_addr=$tbl[1];
        $this->netmask=$tbl[2];
        $this->gateway=$tbl[4];
        if(trim($tbl[5])=='yes'){$this->wireless=true;}else{$this->wireless=false;}
        return true;
    }
    private function NicStatus($InterfaceName):bool{
        if(isset($GLOBALS["CLASS_SOCKETS"])){
            $sock=$GLOBALS["CLASS_SOCKETS"];}
        else{
            $GLOBALS["CLASS_SOCKETS"]=new sockets();
            $sock=$GLOBALS["CLASS_SOCKETS"];
        }

        $content=$sock->REST_API("/system/network/interface/info/$InterfaceName");
        $json=json_decode($content);
        if (json_last_error()> JSON_ERROR_NONE) {
            writelogs("/system/network/interface/info/$InterfaceName Error:".json_last_error_msg(),__FUNCTION__,__FILE__,__LINE__);
            return $this->NicStatusOld($InterfaceName);

        }
        if(!$json->Status){
            writelogs("/system/network/interface/info/$InterfaceName Error:FALSE",__FUNCTION__,__FILE__,__LINE__);
            return $this->NicStatusOld($InterfaceName);
        }
        $this->tcp_addr=$json->IpAddr;
        $this->mac_addr=$json->MacAddr;
        $this->netmask=$json->NetMask;
        $this->gateway=$json->Gateway;
        $this->wireless=$json->Wireless;
        if($this->tcp_addr=="127.0.0.1"){
            $this->tcp_addr="0.0.0.0";
        }

        return true;
    }
	
	function ifconfig($nic){
		include_once(dirname(__FILE__) . '/class.sockets.inc');

        $this->NicStatus($nic);
		$ipBin="/bin/ip";
		if(!is_file($ipBin)){
			$ipBin="/sbin/ip";
		}
		
		exec("$ipBin link show $nic 2>&1",$results);
		foreach ($results as $index=>$line){
			if(preg_match("#LOWER_UP#", $line)){
				$this->linkup=true;
			}
		}
		
		if($this->netmask=="0.0.0.0"){
			$results=array();
			exec("$ipBin addr show $nic 2>&1",$results);
			foreach ($results as $index=>$line){
				if(preg_match("#inet\s+([0-9\.]+)\/([0-9]+)\s+#", $line,$re)){
					$net=$re[1]."/".$re[2];
					$results2=array();
					exec("/usr/share/artica-postfix/bin/ipcalc $net 2>&1");
					foreach ($results2 as $line){
						if(preg_match("#Netmask:\s+([0-9\.]+)#", $line,$re)){$this->netmask=$re[1];break;}
						
					}
					break;
				}
			}
		}
		
		

			
		
		}
    private function GetNicInfosAsRoot($nic){
			if(isset($GLOBALS["GetNicInfosAsRoot$nic"])){
				if($GLOBALS["VERBOSE"]){echo "*** GetNicInfosAsRoot($nic) *** \n{$GLOBALS["GetNicInfosAsRoot$nic"]}\n";}
				return $GLOBALS["GetNicInfosAsRoot$nic"];
			}
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
			$unix=new unix();
			
			$MAIN=$unix->NETWORK_ALL_INTERFACES();
			$f[]="BOOTPROTO=";
			$f[]="METHOD=debian";
			$f[]="DEVICE=$nic";
			$f[]="MAC={$MAIN[$nic]["MAC"]}";
			$datas=trim(@implode("\n",$f));	
			if($GLOBALS["VERBOSE"]){echo "*** GetNicInfosAsRoot($nic) *** \n$datas\n";}
			$GLOBALS["GetNicInfosAsRoot$nic"]=$datas;
			return $datas;
		}
    function NicInfosFromAPI($nic):string{

        $sock=new sockets();
        $data=$sock->REST_API("/system/network/interface/info/$nic");
        $json=json_decode($data);
        if (json_last_error()> JSON_ERROR_NONE) {
            return "";
        }
        if(!$json->Status){
            return "";
        }
        $f[]="BOOTPROTO=";
        $f[]="METHOD=debian";
        $f[]="DEVICE=$nic";
        $f[]="MAC=$json->MacAddr";
        return @implode("\n",$f);
    }
	function GetNicInfos($nic):array{

			VERBOSE("*** GetNicInfos($nic) ***",__LINE__);
			if($nic==null){return false;}
			include_once(dirname(__FILE__) . '/class.sockets.inc');

			if(!isset($GLOBALS["NIC_INFOS_$nic"])){
				if($GLOBALS["AS_ROOT"]){$GLOBALS["NIC_INFOS_$nic"]=$this->GetNicInfosAsRoot($nic);}else{$GLOBALS["NIC_INFOS_$nic"]=$this->NicInfosFromAPI($nic);}
			}
			$datas=$GLOBALS["NIC_INFOS_$nic"];
            VERBOSE($datas,__LINE__);

			
			$tbl=explode("\n",$datas);
			if(!is_array($tbl)){return array();}
			$array=array();
			foreach ($tbl as $ligne){
				$ligne=str_replace("'","",$ligne);
			    if(preg_match("#route add -net\s+([0-9\.]+)\s+netmask\s+([0-9\.]+)\s+gw\s+([0-9\.]+)#",$ligne,$re)){$array["ROUTES"][$re[1]]=array("NETMASK"=>$re[2],"GATEWAY"=>$re[3]);continue;}
			    if(preg_match("#route add -net\s+([0-9\.]+)\s+netmask\s+([0-9\.]+)\s+dev\s+(.+)#",$ligne,$re)){$array["ROUTES"][$re[1]]=array("NETMASK"=>$re[2],"DEV"=>$re[3]);continue;}
			    if(preg_match("#route add default gw ([0-9\.]+)#",$ligne,$re)){$array["ROUTES"]["default"]=array("GATEWAY"=>$re[1]);continue;}
			    if(preg_match('#([a-zA-Z0-9\-\_]+)=(.+)#',$ligne,$reg)){$array[$reg[1]]=$reg[2];}
				
			}
			
		return $this->GetInfosReturnDefault($array,$nic);
		}
	function GetInfosReturnDefault($array,$nic=null):array{
		$default_addr=null;
		$default_netmask=null;
		$default_gateway=null;
		$bradcast_default=null;
        if(!is_array($this->arrayNameServers)){
            $this->arrayNameServers=array();
        }

		if(count($this->arrayNameServers)==0){$this->LoadNameServers();}
		if($nic<>null) {
            include_once(dirname(__FILE__) . '/class.sockets.inc');
            if (!isset($GLOBALS["CLASS_SOCKETS"])) {
                $GLOBALS["CLASS_SOCKETS"] = new sockets();
            }
            if (!isset($GLOBALS["NIC_STATUS_$nic"])) {
                $data = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/nicstatus/$nic"));
                $GLOBALS["NIC_STATUS_$nic"] = $data->Info;

            }

			$nicinfos=$GLOBALS["NIC_STATUS_$nic"];
			$tbl=explode(";",$nicinfos);
			$default_addr=$tbl[0];
			if(isset($tbl[2])){$default_netmask=$tbl[2];}
			if(isset($tbl[4])){$default_gateway=$tbl[4];}
			if(isset($tbl[3])){$bradcast_default=$tbl[3];}
		}
				
		if(trim($default_addr)==null){$default_addr="0.0.0.0";}
		if(trim($default_netmask)==null){$default_netmask="0.0.0.0";}
		if(trim($default_gateway)==null){$default_gateway="0.0.0.0";}
		
		if($bradcast_default==null){
			if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#",$default_addr,$re)){
				$bradcast_default="{$re[1]}.{$re[2]}.{$re[3]}.255";
			}
		}
		
		if(!isset($array["ONBOOT"])){$array["ONBOOT"]=null;}
		if(!isset($array["METRIC"])){$array["METRIC"]=null;}
		if(!isset($array["NEEDHOSTNAME"])){$array["NEEDHOSTNAME"]=null;}
		if(!isset($array["DHCP_CLIENT"])){$array["DHCP_CLIENT"]=null;}
		if(!isset($array["PEERDNS"])){$array["PEERDNS"]=null;}
		if(!isset($array["PEERYP"])){$array["PEERYP"]=null;}
		if(!isset($array["PEERNTPD"])){$array["PEERNTPD"]=null;}
		if(!isset($array["GATEWAY"])){$array["GATEWAY"]=null;}
		if(!isset($array["BROADCAST"])){$array["BROADCAST"]=null;}
		if(!isset($array["IPADDR"])){$array["IPADDR"]=null;}
		if(!isset($array["NETMASK"])){$array["NETMASK"]=null;}
		if(!isset($array["BOOTPROTO"])){$array["BOOTPROTO"]=null;}
		if(!isset($array["ipv6"])){$array["ipv6"]=null;}
		
		
		
		
		if($array["ONBOOT"]==null){$array["ONBOOT"]="yes";}
		if($array["METRIC"]==null){$array["METRIC"]="10";}
		if($array["NEEDHOSTNAME"]==null){$array["NEEDHOSTNAME"]="yes";}	
		if($array["DHCP_CLIENT"]==null){$array["DHCP_CLIENT"]="dhclient";}	
		if($array["PEERDNS"]==null){$array["PEERDNS"]="yes";}	
		if($array["PEERYP"]==null){$array["PEERYP"]="no";}	
		if($array["PEERNTPD"]==null){$array["PEERNTPD"]="no";}		
		if($array["GATEWAY"]==null){$array["GATEWAY"]=$default_gateway;}
		if($array["IPADDR"]==null){$array["IPADDR"]=$default_addr;}
		if($array["NETMASK"]==null){$array["NETMASK"]=$default_netmask;}
		if($array["BOOTPROTO"]==null){$array["BOOTPROTO"]="static";}
		if($array["BROADCAST"]==null){$array["BROADCAST"]=$bradcast_default;}
		
		if(preg_match("#(.+?)\/([0-9]+)#",$array["IPADDR"],$re)){
			$array["IPADDR"]=$re[1];
			if($re[2]==24){$array["NETMASK"]="255.255.255.0";}
		}
		if(isset($array["dns-nameservers"])){
			if($array["dns-nameservers"]<>null){
				$t=explode(" ",$array["dns-nameservers"]);
				$array["NAMESERVERS"]=$t;
			}else{
				$array["NAMESERVERS"]=$this->arrayNameServers;;
			}
		}
		return $array;		
		
	}

}
class MACAddress {
    private $_macPrefix = '00-';
    private $_macORGPrefix = '00-50-56-';
    private $_macAddress;
    private $_macChars;
    public $separator="-";
    public $MAC1;
    public $MAC2;
    public $MAC3;
    public $MAC4;
    public $MAC5;
    public $MAC6;

     public function _generateMAC(){
     	$this->MAC1=str_replace("-",$this->_macPrefix,$this->_macPrefix);
        $this->_macChars = md5(uniqid(mt_rand(), TRUE));
        $this->MAC2=substr($this->_macChars, 0,2);
        $this->MAC3=substr($this->_macChars, 8,2);
        $this->MAC4=substr($this->_macChars, 12,2);
        $this->MAC5=substr($this->_macChars, 16,2);
        $this->MAC6=substr($this->_macChars, 20,2);
            
        
        $this->_macAddress = substr($this->_macChars, 0,2) . '-';
        $this->_macAddress .= substr($this->_macChars, 8,2) . '-';
        $this->_macAddress .= substr($this->_macChars, 12,2) . '-';
        $this->_macAddress .= substr($this->_macChars, 16,2) . '-';
        $this->_macAddress .= substr($this->_macChars, 20,2);
        return "$this->MAC1$this->MAC2$this->separator$this->MAC3$this->separator$this->MAC4$this->separator$this->MAC5$this->separator$this->MAC6";
     }

     public function _generateMAC2(){
     	$this->_macORGPrefix=str_replace("-",$this->separator,$this->_macORGPrefix);
        $this->_macChars = md5(uniqid(mt_rand(), TRUE));
        $this->MAC4=substr($this->_macChars, 0,2);
        $this->MAC5=substr($this->_macChars, 8,2);
        $this->MAC6=substr($this->_macChars, 12,2);
        $this->_macAddress = "$this->_macORGPrefix$this->MAC4$this->separator$this->MAC5$this->separator$this->MAC6";
        return $this->_macAddress;
     }
} 
class networkscanner{
	var $networklist=array();
	var $DefaultNetworkList=array();
	var $Networks_disabled=array();
	
	
	function __construct(){
        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");


        $ligne=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM networks_infos");
        if($ligne["tcount"]>0){
            $results=$q->QUERY_SQL("SELECT * FROM networks_infos");
            foreach ($results as $index=>$ligne){
                $this->networklist[]=$ligne["ipaddr"];
            }
            $results=$q->QUERY_SQL("SELECT * FROM networks_infos WHERE enabled=0");
            foreach ($results as $index=>$ligne){
                $this->Networks_disabled[$ligne["ipaddr"]]=true;
            }
            return;
        }


		$tbl=explode("\n",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetworkScannerMasks"));
		$disabled=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetworkScannerMasksDisabled"));
		
        foreach ($tbl as $maks){
			$maks=trim($maks);
			if(strlen($maks)<6){continue;}
			$netinfos=base64_encode("Network $maks");
            $this->networklist[]=$maks;
			$q->QUERY_SQL("INSERT INTO networks_infos (ipaddr,netinfos) VALUES ('$maks','$netinfos')");
		}
		

	$tbl=explode("\n",$disabled);	
	if(is_array($tbl)){
        foreach ($tbl as $maks){
			if(trim($maks)==null){continue;}
            $netinfos=base64_encode("Network $maks");
            $q->QUERY_SQL("INSERT INTO networks_infos (ipaddr,netinfos,enabled) VALUES ('$maks','$netinfos',0)");
			$this->Networks_disabled[$maks]=true;
		}
	}
		
		if(count($this->networklist)==0){$this->builddefault();}
		
	}

	
	function save(){
		if(is_array($this->networklist)){
			reset($this->networklist);
			foreach ($this->networklist as $maks){
				$maks=trim($maks);
				if(strlen($maks)<6){continue;}
				$arr[trim($maks)]=trim($maks);
				}
			}
		
		$xt=array();
		if(is_array($arr)){
		    foreach ($arr as $net){
				$xt[]=$net;
			}
		}
		
		$sock=new sockets();
		$sock->SaveConfigFile( @implode("\n", $xt),"NetworkScannerMasks");
		$sock->DeleteCache();
		
	}
	
	function builddefault(){
        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
		$net=new networking();
		$cip=new IP();
		foreach ($net->array_TCP as $ip){
			if(preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$ip,$re)){
				$ip_start="{$re[1]}.{$re[2]}.{$re[3]}.0";
				$ip_end="{$re[1]}.{$re[2]}.{$re[3]}.255";
				$cdir=$cip->ip2cidr($ip_start,$ip_end);
				if(trim($cdir)<>null){
                    $netinfos=base64_encode("Network $cdir");
                    $q->QUERY_SQL("INSERT INTO networks_infos (ipaddr,netinfos) VALUES ('$cdir','$netinfos')");
					$this->DefaultNetworkList[trim($cdir)]=true;
					$this->networklist[]=$cdir;
				}
			}
			
		}
		
		$this->save();
		
	}
	
	
		
	
}


?>