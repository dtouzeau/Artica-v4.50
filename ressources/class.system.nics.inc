<?php
	include_once(dirname(__FILE__).'/class.mysql.inc');
	include_once(dirname(__FILE__).'/class.sockets.inc');
	include_once(dirname(__FILE__).'/class.resolv.conf.inc');
	include_once(dirname(__FILE__).'/class.tcpip.inc');
	include_once(dirname(__FILE__).'/class.system.network.inc');

	if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
class system_nic{
	public $eth="";
	public $NETMASK="";
	public $GATEWAY="";
	public $BROADCAST="";
	public $IPADDR="";
	public $DNS1="";
	public $DNS2="";
	public $dhcp=0;
	public $ROUTES=array();
	public $UseSPAN=0;
    public $denydhcp=0;
	private $UseSPANORG=0;
	public $NICNAME=null;
	public $NETWORK="";
	public $unconfigured=true;
	public $enabled=0;
	public $NoReboot=false;
	public $EnableipV6=0;
	public $ipv6=0;
	public $BOOTPROTO=null;
    public $mirror=0;
	public $ipv6mask=0;
	public $ipv6addr=null;
	public $ipv6gw=null;
	public $metric=0;
	public $mtu=1500;
    public $txqueuelen=1000;
	public $ucarp_enabled=0;
	public $ucarp_master=0;
	public $ucarp_vid=3;
	public $ucarp_vip='';
	public $ucarp_advskew=1;
	public $ucarp_advbase=1;
	public $Bridged=0;	
	public $BridgedTo=null;
	public $defaultroute=0;
	public $DNS_1=null;
	public $DNS_2=null;
	public $netzone=null;
	public $SourceBasedRouting=0;
	public $isFW=0;
	public $isFWAcceptNet=1;
	public $isFWAcceptArtica=1;
	public $isFWLogBlocked=0;
	public $QOSMAX=100;
	public $FireQOS=0;
	public $InputSpeed=0;
	public $OutputSpeed=0;
	public $QOS=1;
	public $mysql_error;
	public $firewall_policy     = "accept";
	public $firewall_ping       = "accept";
	public $proxyarp            = 0;
	public $DenyCountries       = 0;
	public $firewall_behavior=0;
	public $firewall_masquerade=0;
	public $firewall_artica=0;
    public $xtIpv4options=0;
	public $RPFilter=0;
	public $LogMartians=0;
	public $AcceptSourceRoute=1;
	public $forwarding=0;
	public $MCForwarding=0;
	public $AcceptRedirects=1;
	public $SendRedirects=1;
	public $ModemType=null;
	public $SpeedUnit="kbit";
    public $mirrorgateway="";
	public $LinkedSwitchInterface=null;
	public $NoInternetCheck=0;
	public $SysCtlEnable=0;
	public $checksum_offloading=0;
	public $watchdog=0;
	private $nocache=false;
    public $udhcpd=0;
    public $parprouted=0;
    public $parprouted_cf=array();
    public $udhcpd_conf=array();
    public $ospf_enable=1;
    public $AntiDDOS=0;
    public $macvlan=0;
    public $ipvlan=0;
    public $physical=null;
    private $sumcontrol=null;
    public $NotSaved=false;
    public $MACADDR="";
    public $virtualbridge=0;
    public $ShoreWallServices=array();

	function __construct($eth=null,$nocache=false){
		if(!isset($GLOBALS["AS_ROOT"])){
			$GLOBALS["AS_ROOT"]=false;
			if(function_exists("posix_getuid")){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
		}
		
		$this->nocache=$nocache;
		if($GLOBALS["AS_ROOT"]){if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}}
		$sock=new sockets();
		$this->EnableipV6=intval($sock->GET_INFO("EnableipV6"));

		if($eth<>null){
			$this->eth=$eth;
			$this->loadEthConfig();
		}
		$this->CheckUnconfigured();
    }

    public function RemoveInterface($eth):bool{
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/macvlan/delete/$eth");
        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $q->QUERY_SQL("DELETE FROM nics WHERE Interface='$eth'");
        if(!$q->ok){return false;}
        return true;
    }
    public function macvlan_create($physical=null,$vethType=null):bool{
        if($physical==null){
            $this->mysql_error="No physical Interface defined";
            return false;
        }
        // Get the number of Interfaces to create vethx
        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $Count=1;
        $results=$q->QUERY_SQL("SELECT Interface FROM nics");
        foreach ($results as $index=>$ligne){
            $Count++;
        }

        $VERBOSED[]="Count OF rows = $Count\n";
        $Count++;

        $VERBOSED[]="New Interface = veth$Count\n";

        if($vethType=="macvlan"){
            $Interface="veth$Count";
            $sql="INSERT INTO nics (Interface,macvlan,ipvlan,physical,NICNAME,netzone,enabled,AUTO,`BRIDGE_PORTS`,`BRIDGE_STP`,`BRIDGE_MAXWAIT`,`DNS_SEARCH`,`VLAN_ROW_DEVICE`,`MTU`,`IPADDR`,`NETMASK`,`BOOTPROTO`,`GATEWAY`,`BROADCAST`,`DNS1`,`DNS2`,`ipv6addr`,`metric`,`defaultroute`,`NETWORK`,`ipv6gw`)
        VALUES('$Interface','1','0','$physical','macvlan $Interface','',1,'$Interface',0,0,0,'localhost','$Interface',1500,'0.0.0.0','0.0.0.0','static','0.0.0.0','255.255.255.255','','','',99,0,'','')";
        }
        if($vethType=="ipvlan"){
            $Interface="ipvlan{$Count}";
            $sql="INSERT INTO nics (Interface,macvlan,ipvlan,physical,NICNAME,netzone,enabled,AUTO,`BRIDGE_PORTS`,`BRIDGE_STP`,`BRIDGE_MAXWAIT`,`DNS_SEARCH`,`VLAN_ROW_DEVICE`,`MTU`,`IPADDR`,`NETMASK`,`BOOTPROTO`,`GATEWAY`,`BROADCAST`,`DNS1`,`DNS2`,`ipv6addr`,`metric`,`defaultroute`,`NETWORK`,`ipv6gw`)
        VALUES('$Interface','0','1','$physical','ipvlan $Interface','',1,'$Interface',0,0,0,'localhost','$Interface',1500,'0.0.0.0','0.0.0.0','static','0.0.0.0','255.255.255.255','','','',99,0,'','')";
        }

        $q->QUERY_SQL($sql);
        if(!$q->ok){
            $this->mysql_error=@implode("\n",$VERBOSED) ."\n".$q->mysql_error;
            return false;
        }
        $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/macvlan/build/$Interface"));

        if(!$data->Status){
            $this->mysql_error=$data->Error;
            return false;
        }
        return true;
    }
	public function IsConfigured():bool{
		$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
		$sql="SELECT `Interface` FROM `nics` WHERE `Interface`='$this->eth'";
		$ligne=$q->mysqli_fetch_array($sql);
        if(!isset($ligne["Interface"])){return false;}
		if($ligne["Interface"]<>null){return true;}
		return false;
	}
	private function loadEthVirtual(){
		$LOGPR=__CLASS__."/".__FUNCTION__;
		if(preg_match("#^virt([0-9]+)#", $this->eth,$re)){$ID=$re[1];}
		$sql="SELECT * FROM `nics_switch` WHERE `ID`='$ID'";
		if($GLOBALS["VERBOSE"]){echo "$LOGPR: $sql<br>\n";}
		$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db"); 
		//foreach ($results as $index=>$ligne){
		//$results=$q->QUERY_SQL($sql);$ligne=$results[0];
		
		$results=$q->QUERY_SQL($sql);$ligne=$results[0];
		
		if(!$q->ok){echo "$q->mysql_error\n";}
		
		if(!isset($ligne["DNS1"])){$ligne["DNS1"]=null;}
		if(!isset($ligne["DNS2"])){$ligne["DNS2"]=null;}
		if(!isset($ligne["NETWORK"])){$ligne["NETWORK"]=null;}
		if(!isset($ligne["ipv6mask"])){$ligne["ipv6mask"]=null;}
		if(!isset($ligne["ipv6addr"])){$ligne["ipv6addr"]=null;}
		if(!isset($ligne["ipv6gw"])){$ligne["ipv6gw"]=null;}
		if(!isset($ligne["UseSPAN"])){$ligne["UseSPAN"]=0;}
        if(!isset($ligne["watchdog"])){$ligne["watchdog"]=0;}
        if(!isset($ligne["ospf_enable"])){$ligne["ospf_enable"]=1;}
        if(!isset($ligne["virtualbridge"])){$ligne["virtualbridge"]=1;}



		if($ligne["netzone"]==null){$ligne["netzone"]=$this->eth;}
		$this->LinkedSwitchInterface=$ligne["nic"];
		$this->firewall_policy=$ligne["firewall_policy"];
		$this->netzone=$ligne["netzone"];
		$this->ipv6=$ligne["ipv6"];
		$this->NETMASK=$ligne["netmask"];
		$this->GATEWAY=$ligne["gateway"];
		$this->IPADDR=$ligne["ipaddr"];
		$this->DNS1=trim($ligne["DNS1"]);
		$this->DNS2=trim($ligne["DNS2"]);
        $this->virtualbridge=intval($ligne["virtualbridge"]);
		$this->NETWORK=$ligne["cdir"];
		$this->ipv6mask=$ligne["ipv6mask"];
		$this->ipv6addr=$ligne["ipv6addr"];
		$this->ipv6gw=$ligne["ipv6gw"];
		$this->metric=$ligne["metric"];
		$this->NICNAME=$ligne["NICNAME"];
		$this->watchdog=intval($ligne["watchdog"]);
		$this->ospf_enable=intval($ligne["ospf_enable"]);
        $this->AntiDDOS=intval($ligne["AntiDDOS"]);
        $this->denydhcp=intval($ligne["denydhcp"]);
        $this->xtIpv4options=intval($ligne["xtIpv4options"]);
		
		$this->UseSPAN=intval($ligne["UseSPAN"]);
		$this->enabled=intval($ligne["enabled"]);
		$this->isFW=$ligne["isFW"];
		$this->isFWAcceptNet=$ligne["isFWAcceptNet"];
		$this->isFWAcceptArtica=$ligne["isFWAcceptArtica"];
		$this->isFWLogBlocked=$ligne["isFWLogBlocked"];
		$this->firewall_behavior=$ligne["firewall_behavior"];
		$this->firewall_masquerade=$ligne["firewall_masquerade"];
		$this->firewall_artica=$ligne["firewall_artica"];
		$this->DenyCountries=$ligne["DenyCountries"];
		$this->NoInternetCheck=intval($ligne["NoInternetCheck"]);
		$this->ShoreWallServices=unserialize(base64_decode($ligne["ShoreWallServices"]));
		if($this->NICNAME==null){$this->NICNAME="Interface $this->eth";}
		if($this->NETWORK==null){$this->NETWORK=$this->GetCDIRNetwork($this->IPADDR,$this->NETMASK);}
		if($GLOBALS["VERBOSE"]){echo "$LOGPR: NETWORK:$this->NETWORK<br>\n";}
	}
	private function loadEthConfig(){

		if(!$this->nocache){
            VERBOSE("LOAD CACHE...",__LINE__);
			$results=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ETHCONFIG-$this->eth"));
		}
		
		$LOGPR=__CLASS__."/".__FUNCTION__;
		VERBOSE("Loading $this->eth",__LINE__);
		if(preg_match("#^virt#", $this->eth)){
			$this->loadEthVirtual();
			$this->enabled=1;
			return;
		}
        if(preg_match("#^br[0-9]+#",$this->eth)){
            $ip=new networking();
            $ligne=$ip->GetNicInfos($this->eth);
            if(!isset($ligne["ROUTES"])){$ligne["ROUTES"]=null;}
            $this->ROUTES=$ligne["ROUTES"];
            if(($ligne["BOOTPROTO"]=='dhcp') OR ($ligne["BOOTPROTO"]==null)){$this->dhcp=1;}
            $this->NETMASK=$ligne["NETMASK"];
            $this->GATEWAY=$ligne["GATEWAY"];
            $this->BROADCAST=$ligne["BROADCAST"];
            $this->IPADDR=$ligne["IPADDR"];
            $this->netzone="{bridge}";
            $this->NICNAME=$this->eth;
            $this->MACADDR=$ligne["MAC"];
            return;
        }

        $AsSWitchInterface=false;
        $RequiredInterface=$this->eth;
        if(preg_match("#^switch(.+)#",$this->eth,$re)){
            $RequiredInterface=$re[1];
            $AsSWitchInterface=true;
        }

		if(!isset($results[0]["virtualbridge"])){
			$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
            $sql="SELECT * FROM `nics` WHERE `Interface`='$RequiredInterface'";
            if(function_exists("VERBOSE")) {VERBOSE("MISS nic infos from `$RequiredInterface` `$sql`",__LINE__);}
            $ligne=$q->mysqli_fetch_array($sql);
            if(isset($ligne["Interface"])) {
                $results[0] = $ligne;
                $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(serialize($results), "ETHCONFIG-$RequiredInterface");
            }
            if(!isset($ligne["Interface"])) {
                if(function_exists("VERBOSE")) {VERBOSE("`$RequiredInterface` NOT CONFIGURED",__LINE__);}
                $this->NotSaved=true;
            }

		}else{
            $ligne=$results[0];
            if(function_exists("VERBOSE")) {VERBOSE("$RequiredInterface: HIT FROM MEMORY [{$ligne["IPADDR"]}]", __LINE__);}
		}
		$this->dhcp=0;
        if(!isset($ligne["Interface"])){$ligne["Interface"]=null;}
		if($ligne["Interface"]==null){
			$ip=new networking();
			$ligne=$ip->GetNicInfos($this->eth);
			if(!isset($ligne["ROUTES"])){$ligne["ROUTES"]=null;}
			$this->ROUTES=$ligne["ROUTES"];
			if(($ligne["BOOTPROTO"]=='dhcp') OR ($ligne["BOOTPROTO"]==null)){$this->dhcp=1;}
            $this->MACADDR=$ligne["MAC"];
			$ligne["enabled"]=1;
		
		}else{
			$this->dhcp=$ligne["dhcp"];
			$this->ROUTES=unserialize($ligne["routes"]);
		}
        if(!isset($ligne["mirror"])){$ligne["mirror"]=0;}
        if(!isset($ligne["mirrorgateway"])){$ligne["mirrorgateway"]="";}
        if(!isset($ligne["udhcpd"])){$ligne["udhcpd"]=0;}
        if(!isset($ligne["udhcpd_conf"])){$ligne["udhcpd_conf"]=null;}
        if(!isset($ligne["macvlan"])){$ligne["macvlan"]=null;}
        if(!isset($ligne["physical"])){$ligne["physical"]=null;}
		if(!isset($ligne["DNS1"])){$ligne["DNS1"]=null;}
		if(!isset($ligne["DNS2"])){$ligne["DNS2"]=null;}
		if(!isset($ligne["NETWORK"])){$ligne["NETWORK"]=null;}
		if(!isset($ligne["ipv6mask"])){$ligne["ipv6mask"]=null;}
		if(!isset($ligne["ipv6addr"])){$ligne["ipv6addr"]=null;}
		if(!isset($ligne["ipv6gw"])){$ligne["ipv6gw"]=null;}
		if(!isset($ligne["RPFilter"])){$ligne["RPFilter"]=0;}
		if(!isset($ligne["LogMartians"])){$ligne["LogMartians"]=0;}
		if(!isset($ligne["AcceptSourceRoute"])){$ligne["AcceptSourceRoute"]=1;}
		if(!isset($ligne["forwarding"])){$ligne["forwarding"]=0;}
		if(!isset($ligne["MCForwarding"])){$ligne["MCForwarding"]=0;}
		if(!isset($ligne["AcceptRedirects"])){$ligne["AcceptRedirects"]=1;}
		if(!isset($ligne["SendRedirects"])){$ligne["SendRedirects"]=1;}
		if(!isset($ligne["SysCtlEnable"])){$ligne["SysCtlEnable"]=0;}
		if(!isset($ligne["checksum_offloading"])){$ligne["checksum_offloading"]=0;}
		if(!isset($ligne["netzone"])){$ligne["netzone"]=null;}
		if(!isset($ligne["metric"])){$ligne["metric"]=0;}
		if(!isset($ligne["UseSPAN"])){$ligne["UseSPAN"]=0;}
        if(!isset($ligne["AntiDDOS"])){$ligne["AntiDDOS"]=0;}
		if(!isset($ligne["ucarp-enable"])){$ligne["ucarp-enable"]=0;}
		if(!isset($ligne["ucarp-master"])){$ligne["ucarp-master"]=0;}
		if(!isset($ligne["QOS"])){$ligne["QOS"]=0;}
		if(!isset($ligne["QOSMAX"])){$ligne["QOSMAX"]=0;}
		if(!isset($ligne["DenyCountries"])){$ligne["DenyCountries"]=null;}
		if(!isset($ligne["firewall_masquerade"])){$ligne["firewall_masquerade"]=0;}
		if(!isset($ligne["BridgedTo"])){$ligne["BridgedTo"]="";}
		if(!isset($ligne["NICNAME"])){$ligne["NICNAME"]="";}
		if(!isset($ligne["isFW"])){$ligne["isFW"]=0;}
		if(!isset($ligne["MTU"])){$ligne["MTU"]="";}
        if(!isset($ligne["proxyarp"])){$ligne["MTU"]="";}
        if(!isset($ligne["watchdog"])){$ligne["watchdog"]=0;}
        if(!isset($ligne["proxyarp"])){$ligne["proxyarp"]=0;}
        if(!isset($ligne["metric"])){$ligne["metric"]=0;}
        if(!isset($ligne["parprouted"])){$ligne["parprouted"]=0;}
        if(!isset($ligne["parprouted_cf"])){$ligne["parprouted_cf"]="YTowOnt9";}
        if(!isset($ligne["udhcpd_conf"])){$ligne["udhcpd_conf"]="YTowOnt9";}
        if(!isset($ligne["ospf_enable"])){$ligne["ospf_enable"]=1;}
        if(!isset($ligne["sumcontrol"])){$ligne["sumcontrol"]="";}
        if(!isset($ligne["txqueuelen"])){$ligne["txqueuelen"]="1000";}
        if(!isset($ligne["xtIpv4options"])){$ligne["xtIpv4options"]=0;}
        if(!isset($ligne["DNS1"])){$ligne["DNS1"]="";}
        if(!isset($ligne["DNS2"])){$ligne["DNS2"]="";}
        if(!isset($ligne["denydhcp"])){$ligne["denydhcp"]=0;}
        if(!isset($ligne["ipvlan"])){$ligne["ipvlan"]=0;}
        if(!isset($ligne["virtualbridge"])){
            VERBOSE("virtualbridge --> UNSET",__CLASS__."/".__LINE__);
            $ligne["virtualbridge"]=0;
        }
		
		if($ligne["netzone"]==null){$ligne["netzone"]=$RequiredInterface;}
		if(!isset($ligne["NoInternetCheck"])){$ligne["NoInternetCheck"]=0;}
		$this->udhcpd=intval($ligne["udhcpd"]);
        $this->macvlan=intval($ligne["macvlan"]);
        $this->ipvlan=intval($ligne["ipvlan"]);
        $this->physical=trim((string) $ligne["physical"]);
        $this->parprouted=intval($ligne["parprouted"]);
        $this->udhcpd_conf=unserialize(base64_decode($ligne["udhcpd_conf"]));
        $this->parprouted_cf=unserialize(base64_decode($ligne["parprouted_cf"]));
		$this->forwarding=$ligne["forwarding"];
        $this->virtualbridge=intval($ligne["virtualbridge"]);
		$this->AcceptSourceRoute=$ligne["AcceptSourceRoute"];
		$this->LogMartians=$ligne["LogMartians"];
		$this->RPFilter=$ligne["RPFilter"];
		$this->netzone=$ligne["netzone"];
		$this->ipv6=$ligne["ipv6"];
		$this->NETMASK=$ligne["NETMASK"];
		$this->GATEWAY=$ligne["GATEWAY"];
		$this->BROADCAST=$ligne["BROADCAST"];
		$this->IPADDR=$ligne["IPADDR"];
		$this->DNS1=trim($ligne["DNS1"]);
		$this->DNS2=trim($ligne["DNS2"]);
		$this->NETWORK=$ligne["NETWORK"];
		$this->enabled=intval($ligne["enabled"]);
		$this->ipv6mask=$ligne["ipv6mask"];
		$this->ipv6addr=$ligne["ipv6addr"];
		$this->ipv6gw=$ligne["ipv6gw"];
		$this->metric=$ligne["metric"];
        $this->xtIpv4options=intval($ligne["xtIpv4options"]);
		$this->UseSPAN=intval($ligne["UseSPAN"]);
		$this->UseSPANORG=intval($ligne["UseSPAN"]);
        $this->AntiDDOS=intval($ligne["AntiDDOS"]);
		$this->ucarp_enabled=$ligne["ucarp-enable"];
		$this->ucarp_master=$ligne["ucarp-master"];
		$this->QOS=intval($ligne["QOS"]);
		$this->QOSMAX=intval($ligne["QOSMAX"]);
		$this->DenyCountries=intval($ligne["DenyCountries"]);
		$this->firewall_masquerade=intval($ligne["firewall_masquerade"]);
		$this->NoInternetCheck=intval($ligne["NoInternetCheck"]);
		$this->MCForwarding=intval($ligne["MCForwarding"]);
		$this->AcceptRedirects=intval($ligne["AcceptRedirects"]);
		$this->SendRedirects=intval($ligne["SendRedirects"]);
		$this->SysCtlEnable=intval($ligne["SysCtlEnable"]);
		$this->checksum_offloading=intval($ligne["checksum_offloading"]);
		$this->BOOTPROTO=trim($ligne["BOOTPROTO"]);
		$this->proxyarp=intval($ligne["proxyarp"]);
		$this->ospf_enable=intval($ligne["ospf_enable"]);
        $this->sumcontrol=$ligne["sumcontrol"];
        $this->denydhcp=intval($ligne["denydhcp"]);
        $this->mirror=intval($ligne["mirror"]);
        $this->mirrorgateway=trim($ligne["mirrorgateway"]);
        $this->txqueuelen=intval($ligne["txqueuelen"]);
		
		if(isset($ligne["ucarp-vid"])){$this->ucarp_vid=$ligne["ucarp-vid"];}
		if(isset($ligne["ucarp-vip"])){$this->ucarp_vip=$ligne["ucarp-vip"];}
		if(isset($ligne["ucarp-advskew"])){$this->ucarp_advskew=$ligne["ucarp-advskew"];}
		if(isset($ligne["ucarp-advbase"])){$this->ucarp_advbase=$ligne["ucarp-advbase"];}
		if(isset($ligne["Bridged"])){$this->Bridged=$ligne["Bridged"];}
		if(isset($ligne["firewall_policy"])){$this->firewall_policy=$ligne["firewall_policy"];}
        if(isset($ligne["firewall_ping"])){$this->firewall_ping=$ligne["firewall_ping"];}
		if(isset($ligne["firewall_behavior"])){$this->firewall_behavior=$ligne["firewall_behavior"];;}
		if(isset($ligne["firewall_artica"])){$this->firewall_artica=$ligne["firewall_artica"];;}
		
		if(isset($ligne["FireQOS"])){$this->FireQOS=$ligne["FireQOS"];;}
		if(isset($ligne["InputSpeed"])){$this->InputSpeed=$ligne["InputSpeed"];;}
		if(isset($ligne["OutputSpeed"])){$this->OutputSpeed=$ligne["OutputSpeed"];;}
		if(isset($ligne["ModemType"])){$this->ModemType=$ligne["ModemType"];;}
		if(isset($ligne["SpeedUnit"])){$this->SpeedUnit=$ligne["SpeedUnit"];;}
		if(isset($ligne["defaultroute"])){$this->defaultroute=$ligne["defaultroute"];}
		if(isset($ligne["watchdog"])){$this->watchdog=intval($ligne["watchdog"]);}
        if(isset($ligne["isFWAcceptNet"])){$this->isFWAcceptNet=$ligne["isFWAcceptNet"];}
        if(isset($ligne["isFWLogBlocked"])){$this->isFWLogBlocked=$ligne["isFWLogBlocked"];}
        if(!isset($ligne["SourceBasedRouting"])){$ligne["SourceBasedRouting"]=0;}

        $this->BridgedTo=$ligne["BridgedTo"];
		$this->NICNAME=$ligne["NICNAME"];
		$this->isFW=$ligne["isFW"];
        $this->SourceBasedRouting=intval($ligne["SourceBasedRouting"]);


		VERBOSE("$this->eth mtu={$ligne["MTU"]}",__LINE__);
		if(isset($ligne["MTU"])){ $this->mtu=intval($ligne["MTU"]); }
		if(!is_numeric($this->ucarp_enabled)){$this->ucarp_enabled=0;}
		if(!is_numeric($this->ucarp_vid)){$this->ucarp_vid=0;}
		if(!is_numeric($this->ucarp_advbase)){$this->ucarp_advbase=0;}
		if(!is_numeric($this->mtu)){$this->mtu=1500;}
		if($this->ucarp_vid==0){$this->ucarp_vid=3;}
		if($this->ucarp_advbase==0){$this->ucarp_advbase=2;}
		if(isset($ligne["ShoreWallServices"])){ $this->ShoreWallServices=unserialize(base64_decode($ligne["ShoreWallServices"])); }
		if(!is_numeric($this->isFWAcceptNet)){$this->isFWAcceptNet=1;}
		if($this->metric==0){$this->metric=$this->lastmetric();}
		if($this->mtu==0){$this->mtu=1500;}
		if($this->firewall_policy==null){$this->firewall_policy="accept";}
        if($this->firewall_ping==null){$this->firewall_ping="accept";}
        if($this->txqueuelen<100){$this->txqueuelen=1000;}
		
		if($this->ipv6addr==null){
			$ip=new IP();
			$this->ipv6addr=$ip->IPv4To6($this->IPADDR);
		}
		
		if($this->ipv6gw==null){
			$ip=new IP();
			$this->ipv6gw=$ip->IPv4To6($this->GATEWAY);
		}		
		
		if($this->DNS1==null){
			$resolv=new resolv_conf();
			$this->DNS1=$resolv->MainArray["DNS1"];
			$this->DNS2=$resolv->MainArray["DNS2"];
		}
		if($this->EnableipV6==0){
				$this->ipv6=0;

		}
		
		if($this->ipv6==0){
				$this->ipv6addr=null;
				$this->ipv6mask=0;
				$this->ipv6gw=null;			
		}
		
		if($this->BROADCAST=="0.0.0.255"){
			$t=explode(".", $this->IPADDR);
			$this->BROADCAST="$t[0].$t[1].$t[2].255";
		}
		
		if($this->NICNAME==null){$this->NICNAME="Interface $RequiredInterface";}
		VERBOSE("$LOGPR: $this->NICNAME NETWORK:$this->NETWORK",__LINE__);
        if($this->eth=="tailscale0"){$this->NICNAME="Tailscale VPN Interface";}
        if($this->IPADDR=="127.0.0.1"){
            $this->IPADDR="0.0.0.0";
        }
        if($AsSWitchInterface){
            $this->virtualbridge=0;
            $this->netzone="{virtual_switch}";
            $this->NICNAME="<i class='".ico_arrow_right."'></i> $RequiredInterface";
        }
		
		
	}
	private function CheckUnconfigured($called=null){
		
		if($called==null){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
				}
			}
		}
		
		
		$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
		$sql="SELECT COUNT(Interface) as tcount FROM `nics` WHERE enabled=1";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){
			$this->writelogs("Fatal $q->mysql_error $called",__FUNCTION__,__LINE__);
			return false;
		}
		$ligne=$results[0];
		if($ligne["tcount"]>0){$this->unconfigured=false;}

        $sql="CREATE TABLE IF NOT EXISTS `bond_interfaces` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`nic` TEXT NOT NULL,`members` TEXT NOT NULL,`primary_member` TEXT,`mode` INTEGER NOT NULL,`miimon` INTEGER NOT NULL DEFAULT 100, `downdelay` INTEGER NOT NULL DEFAULT 200,`updelay` INTEGER NOT NULL DEFAULT 200, `xmit_hash_policy` TEXT,`lacp_rate` INTEGER)";
        $q->QUERY_SQL($sql);
        if(!$q->TABLE_EXISTS("nics")){
            $this->writelogs("Fatal table nics doesn't exists Aborting function/$called",__FUNCTION__,__LINE__);
            return false;
        }
        return true;
	}
	private function writelogs($text=null,$function=null,$line=0):bool{
		$file_source="/usr/share/artica-postfix/ressources/logs/web/mysql.debug";
	if(!is_dir('/usr/share/artica-postfix/ressources/logs/web')){@mkdir('/usr/share/artica-postfix/ressources/logs/web',0755,true);}
		if(!is_numeric($line)){$line=0;}
		if(function_exists("writelogs")){writelogs("$text (L.$line)","$function",__FILE__,$line);}
		if(!$GLOBALS["VERBOSE"]){return false;}
		$logFile=$file_source;
		if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
		if (is_file($logFile)) {
			$size=filesize($logFile);
			if($size>1000000){unlink($logFile);}
		}
	
		$f = @fopen($logFile, 'a');
		$date=date("Y-m-d H:i:s");
		@fwrite($f, "$date:[$function()][{$_SERVER['REMOTE_ADDR']}]:: $text (L.$line)\n");
		@fclose($f);
        return true;
	}
	private function lastmetric():int{
		$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db"); 
		$hash=array();
		$f=array();

		
		
		$sql="SELECT metric as tcount FROM `nics` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
        $ligne=$q->mysqli_fetch_array($sql);
        if(!isset($ligne["metric"])){$ligne["metric"]=0;}
		$hash[$ligne["metric"]]=$ligne["metric"];
		
		$sql="SELECT metric as tcount FROM `nics_vlan` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
        $ligne=$q->mysqli_fetch_array($sql);
        if(isset($ligne["metric"])) {
            $hash[$ligne["metric"]] = $ligne["metric"];
        }

		$sql="SELECT metric as tcount FROM `nics_virtuals` WHERE enabled=1 ORDER BY metric DESC LIMIT 0,1";
        $ligne=$q->mysqli_fetch_array($sql);
        if(isset($ligne["metric"])) {
            $hash[$ligne["metric"]] = $ligne["metric"];
        }

        if(!is_array($hash)){ return 1;}
        if(count($hash)==0){ return 1;}
    	@krsort($hash);
        foreach ($hash as $a=>$b) {
             $f[] = $b;
        }
		if(!is_numeric($f[0])){$f[0]=1;}
		return $f[0]+1;
		
	}
	public function CleanIPAddr($addr):string{
		$addr=str_replace("___.___.___.___", "", $addr);
		if($addr==null){return "";}
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $addr)){
			$pr=explode(".",$addr);
			foreach ($pr as $index=>$number){$pr[$index]=intval($number);}
			$addr=@implode(".", $pr);
		}		
		return $addr;
		
	}
	public function set_hostname($hostname){

        $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/nohup/hostname/$hostname");

		
	}
	function ToSyslog($text,$error=false){
		if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
		if(function_exists("openlog")){openlog("monit", LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}	
	public function SaveNic():bool{
		
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ETHCONFIG-$this->eth",serialize(array()));
		$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
		$routes=addslashes(serialize($this->ROUTES));
		
		if($this->defaultroute==1){
			$q->QUERY_SQL("UPDATE `nics` SET defaultroute=0");
		}
        if( !is_array($this->udhcpd_conf) OR empty($this->udhcpd_conf) ){
            $this->udhcpd_conf=array();
        }
        if( !is_array($this->parprouted_cf) OR empty($this->parprouted_cf) ){
            $this->parprouted_cf=array();
        }
        $udhcpd_conf=base64_encode(serialize($this->udhcpd_conf));
        $parprouted_cf=base64_encode(serialize($this->parprouted_cf));
		

		if(trim($this->BOOTPROTO==null)){$this->BOOTPROTO="static";}
		$this->eth=trim($this->eth);
		$this->eth=str_replace("\r\n", "", $this->eth);
		$this->eth=str_replace("\r", "", $this->eth);
		$this->eth=str_replace("\n", "", $this->eth);		
		if($this->NICNAME==null){$this->NICNAME="Interface $this->eth";}
		if($this->mtu==0){$this->mtu=1500;}
		if(!is_numeric($this->isFWAcceptNet)){$this->isFWAcceptNet=1;}
		$this->writelogs("$this->eth: ospf_enable: $this->ospf_enable",__FUNCTION__,__FILE__,__LINE__);
		
		$sql_add="
		INSERT INTO `nics` (`Interface`,`AUTO`,`BRIDGE_PORTS`,`BRIDGE_STP`,`BRIDGE_MAXWAIT`,`DNS_SEARCH`,`VLAN_ROW_DEVICE`,`BOOTPROTO`,`IPADDR`,`NETMASK`,`GATEWAY`,`BROADCAST`,`DNS1`,`DNS2`,
		`dhcp`,`ROUTES`,`NETWORK`,`enabled`,`ipv6`,`ipv6mask`,`ipv6gw`,`ipv6addr`,`metric`,`Bridged`,
		`BridgedTo`,`defaultroute`,`NICNAME`,`netzone`,`isFW`,`isFWAcceptNet`,`isFWLogBlocked`,`MTU`,`QOSMAX`,`QOS`,`firewall_policy`,
		`firewall_behavior`,`firewall_masquerade`,`watchdog`,`firewall_ping`,`proxyarp`,`udhcpd`,`udhcpd_conf`,parprouted_cf,parprouted,ospf_enable,AntiDDOS,mirror,mirrorgateway)
		VALUES('$this->eth','$this->eth','0','0','0','localhost','$this->eth','$this->BOOTPROTO','$this->IPADDR','$this->NETMASK','$this->GATEWAY','$this->BROADCAST','$this->DNS_1',
		'$this->DNS_2','$this->dhcp','$routes','$this->NETWORK',1,'$this->ipv6','$this->ipv6mask',
		'$this->ipv6gw','$this->ipv6addr','$this->metric','$this->Bridged','$this->BridgedTo',
		'$this->defaultroute','$this->NICNAME','$this->netzone','$this->isFW','$this->isFWAcceptNet',
		'$this->isFWLogBlocked','$this->mtu','$this->QOSMAX','$this->QOS','$this->firewall_policy','$this->firewall_behavior','$this->firewall_masquerade','$this->watchdog','$this->firewall_ping',
		'$this->proxyarp','$this->udhcpd','$udhcpd_conf','$this->parprouted','$parprouted_cf','$this->ospf_enable',
		       '$this->AntiDDOS',$this->mirror,'$this->mirrorgateway')";
		if($this->ucarp_vid==0){$this->ucarp_vid=3;}
		if($this->ucarp_advbase==0){$this->ucarp_advbase=2;}
		if($this->metric==0){$this->metric=$this->lastmetric();exit;}
		if(!is_numeric($this->ucarp_master)){$this->ucarp_master=0;}
		if(!is_numeric($this->ucarp_enabled)){$this->ucarp_enabled=0;}
		$this->eth=trim($this->eth);
		if(preg_match("#(.+?)\s+#", $this->eth,$re)){$this->eth=$re[1];}
		
		$this->IPADDR=$this->CleanIPAddr($this->IPADDR);
		$this->NETMASK=$this->CleanIPAddr($this->NETMASK);
		$this->GATEWAY=$this->CleanIPAddr($this->GATEWAY);
		$this->BROADCAST=$this->CleanIPAddr($this->BROADCAST);
		$this->NETWORK=$this->CleanIPAddr($this->NETWORK);
		$this->DNS1=$this->CleanIPAddr($this->DNS1);
		$this->DNS2=$this->CleanIPAddr($this->DNS2);
		$this->ucarp_vip=$this->CleanIPAddr($this->ucarp_vip);
		
		
		if($this->enabled==1){
			if($this->UseSPAN==0){
				$ip=new IP();
				if(!$ip->isIPAddress($this->IPADDR)){
					echo __CLASS__."/".__FUNCTION__."/".__LINE__.": \"$this->IPADDR\" not a valid IP address\n";
					return false;
				}
			}
		}
		
		if($this->netzone==null){$this->netzone="$this->eth";}
		if($this->UseSPANORG<>$this->UseSPAN) {
            writelogs("UseSPAN = $this->UseSPAN", __FUNCTION__, __FILE__, __LINE__);
            $q->QUERY_SQL("UPDATE `nics` SET UseSPAN=$this->UseSPAN WHERE `Interface`='$this->eth'");
            admin_tracks("Modify Interface $this->eth SPAN port from $this->UseSPANORG to $this->UseSPAN");
        }
		
		$sql_upd="UPDATE `nics` SET 
		`NICNAME`='".mysql_escape_string2($this->NICNAME)."',
		`netzone`='$this->netzone',
		`IPADDR`='$this->IPADDR',
		`NETMASK`='$this->NETMASK',
		`GATEWAY`='$this->GATEWAY',
		`BROADCAST`='$this->BROADCAST',
		`NETWORK`='$this->NETWORK',
		`UseSPAN`='$this->UseSPAN',
		`DNS1`='$this->DNS1',
		`DNS2`='$this->DNS2',
		`dhcp`='$this->dhcp',
		`enabled`='$this->enabled',
		`ipv6`='$this->ipv6',
		`ipv6mask`='$this->ipv6mask',
		`ipv6gw`='$this->ipv6gw',
		`ipv6addr`='$this->ipv6addr',
		`metric`='$this->metric',
		`ROUTES`='$routes',
		`ucarp-enable`='$this->ucarp_enabled',
		`ucarp-master`='$this->ucarp_master',
		`ucarp-vid`='$this->ucarp_vid',
		`ucarp-vip`='$this->ucarp_vip',
		`ucarp-advskew`='$this->ucarp_advskew',
		`ucarp-advbase`='$this->ucarp_advbase',
		`Bridged`='$this->Bridged',
		`BridgedTo`='$this->BridgedTo',
		`defaultroute`='$this->defaultroute',
		`denydhcp`='$this->denydhcp',
		`watchdog`='".$this->watchdog."',
		`isFW`='$this->isFW',
		`isFWAcceptNet`='$this->isFWAcceptNet',
		`isFWAcceptArtica`='$this->isFWAcceptArtica',
		`isFWLogBlocked`='$this->isFWLogBlocked',
		`MTU`='$this->mtu',
		`SourceBasedRouting`='$this->SourceBasedRouting',
		`QOSMAX`='$this->QOSMAX',
		`QOS`='$this->QOS',
		`firewall_policy`='$this->firewall_policy',
		`firewall_ping`='$this->firewall_ping',
		`firewall_behavior`='$this->firewall_behavior',
		`firewall_masquerade`='$this->firewall_masquerade',
		`DenyCountries`='$this->DenyCountries',
		`firewall_artica`='$this->firewall_artica',
		`FireQOS`='$this->FireQOS',
		`InputSpeed`='$this->InputSpeed',
		`OutputSpeed`='$this->OutputSpeed',
		`ModemType`='$this->ModemType',
		`SpeedUnit`='$this->SpeedUnit',
		`NoInternetCheck`='$this->NoInternetCheck',
		`RPFilter`=$this->RPFilter,
		`LogMartians`=$this->LogMartians,
		`AcceptSourceRoute`=$this->AcceptSourceRoute,
		`forwarding`=$this->forwarding,
		`MCForwarding`=$this->MCForwarding,
		`AcceptRedirects`=$this->AcceptRedirects,
		`SendRedirects`=$this->SendRedirects,
		`SysCtlEnable`='$this->SysCtlEnable',
		`proxyarp`='$this->proxyarp',
		`checksum_offloading`='$this->checksum_offloading',
		`ROUTES`='$routes',
		`udhcpd`='$this->udhcpd',
		`udhcpd_conf`='$udhcpd_conf',
		`parprouted_cf`='$parprouted_cf',
		`parprouted`='$this->parprouted',
		`ospf_enable`='$this->ospf_enable',
		`AntiDDOS`='$this->AntiDDOS',
		`mirror`='$this->mirror',
		`txqueuelen`='$this->txqueuelen',
		`xtIpv4options`=$this->xtIpv4options,
		`mirrorgateway`='$this->mirrorgateway'
		 WHERE `Interface`='$this->eth'";
		$sumcontrol=md5($sql_upd);

		if($sumcontrol==$this->sumcontrol){
            writelogs("save nic infos : $this->eth Control Sum is the same, aborting $sumcontrol---> $this->sumcontrol",__CLASS__."/".__FUNCTION__,__FILE__);
            if(preg_match("#^veth#",$this->eth)){
                $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/macvlan/build/$this->eth"));
                writelogs("--> /system/network/macvlan/build/$this->eth",__CLASS__."/".__FUNCTION__,__FILE__);
                if(!$data->Status){
                    $this->mysql_error=$data->Error;
                    return false;
                }
            }
            return true;
        }

		writelogs("save nic infos : NIC: MTU=$this->mtu; ipv6=$this->ipv6; `$this->eth`;$this->IPADDR;$this->NETMASK;$this->GATEWAY;$this->dhcp;$this->BROADCAST",__CLASS__."/".__FUNCTION__,__FILE__);
		if(function_exists("admin_tracks")){admin_tracks("Modified network parameters of $this->eth interface");}
		
	
		$sql="SELECT Interface FROM `nics` WHERE `Interface`='$this->eth'";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){
			$this->mysql_error=$q->mysql_error;
			echo $q->mysql_error."\n\nLine: ".__LINE__;
			return false;
		}
		
		$ligne=$results[0];
		$sql=$sql_add;
		
		if($ligne["Interface"]<>null){
			$sql=$sql_upd;
		}
		$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			writelogs("FATAL: $q->mysql_error",__FUNCTION__,__FILE__);
			$this->mysql_error=$q->mysql_error;
			echo $q->mysql_error."\n\n$q->db_path\n\n$sql command:\n\n$sql\n\n<hr>".__FUNCTION__." In Line ".__LINE__;
			return false;
		}

        if(preg_match("#^veth#",$this->eth)){
            $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/macvlan/build/$this->eth"));
            if(!$data->Status){
                $this->mysql_error=$data->Error;
                return false;
            }
        }
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/reset/cache");
        $q->QUERY_SQL("UPDATE nics SET sumcontrol='$sumcontrol' WHERE `Interface`='$this->eth'");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("network.php?crc32=yes");
		return true;
		
		
	}
	public function NicToOther($nic){
		if($this->IsBridged($nic)){
			$sql="SELECT Bridged,BridgedTo FROM `nics` WHERE Interface='$nic'";
			$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db"); 
			$results=$q->QUERY_SQL($sql);$ligne=$results[0];
			return $ligne["BridgedTo"];
		}
		return $nic;
	}
	public function IsBridged($eth){
		$key=__CLASS__.__FUNCTION__.$eth;
		if(isset($GLOBALS[$key])){return $GLOBALS[$key];}
		if($GLOBALS["AS_ROOT"]){
			
			
			if(!is_file($GLOBALS["brctlbin"])){
				if($GLOBALS["AS_ROOT"]){
					echo "Starting......: ".date("H:i:s")." $eth brctl no such binary\n";
				}
				$GLOBALS[$key]=false;
				return false;
			}
		}
		$eth=trim($eth);
		if(preg_match("#^br[0-9]+#", $eth)){$GLOBALS[$key]=false;return false;}
		$sql="SELECT Bridged,BridgedTo FROM `nics` WHERE Interface='$eth'";
		$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db"); 
		$results=$q->QUERY_SQL($sql);$ligne=$results[0];
		if(!is_numeric($ligne["Bridged"])){
			if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." $eth Not enabled [NULL] ({$ligne["Bridged"]} to bridge\n";}
			$GLOBALS[$key]=false;return false;}
		if($ligne["Bridged"]==0){
			if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." $eth Not enabled [0] to bridge\n";}
			$GLOBALS[$key]=false;return FALSE;}
		if($ligne["BridgedTo"]==null){
			if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." $eth Not bridged to any interface\n";}
			$GLOBALS[$key]=false;return false;}
		if(!preg_match("#^br([0-9]+)#", $ligne["BridgedTo"],$re)){
			if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." $eth -> {$ligne["BridgedTo"]} incorrect\n";}
			$GLOBALS[$key]=false;return false;}
		$BridgeID=$re[1];
		
		if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." $eth Bridged To {$ligne["BridgedTo"]}\n";}
		$sql="SELECT ipaddr FROM `nics_bridge` WHERE ID='$BridgeID'";
		$results=$q->QUERY_SQL($sql);$ligne=$results[0];
		if($ligne["ipaddr"]==null){$GLOBALS[$key]=false;return false;}
		$ip=new IP();
		if(!$ip->isValid($ligne["ipaddr"])){$GLOBALS[$key]=false;return false;}
		$GLOBALS[$key]=true;
		return true;
	}
	public function LoadTools(){
		if(!isset($GLOBALS["PROC_NET_DEV"])){$this->LoadProcNetDev();}
		if(!class_exists("unix")){return;}
			
		if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		if(!isset($GLOBALS["sysctl"])){$GLOBALS["sysctl"]=$GLOBALS["CLASS_UNIX"]->find_program("sysctl");}
		if(!isset($GLOBALS["ifconfig"])){$GLOBALS["ifconfig"]=$GLOBALS["CLASS_UNIX"]->find_program("ifconfig");}
		if(!isset($GLOBALS["routebin"])){$GLOBALS["routebin"]=$GLOBALS["CLASS_UNIX"]->find_program("route");}
		if(!isset($GLOBALS["ipbin"])){$GLOBALS["ipbin"]=$GLOBALS["CLASS_UNIX"]->find_program("ip");}
		if(!isset($GLOBALS["vconfigbin"])){$GLOBALS["vconfigbin"]=$GLOBALS["CLASS_UNIX"]->find_program("vconfig");}
		if(!isset($GLOBALS["moprobebin"])){$GLOBALS["moprobebin"]=$GLOBALS["CLASS_UNIX"]->find_program("modprobe");}
		if(!isset($GLOBALS["brctlbin"])){$GLOBALS["brctlbin"]=$GLOBALS["CLASS_UNIX"]->find_program("brctl");}
		if(!isset($GLOBALS["dhclientbin"])){$GLOBALS["dhclientbin"]=$GLOBALS["CLASS_UNIX"]->find_program("dhclient");}
		if(!isset($GLOBALS["tunctlbin"])){$GLOBALS["tunctlbin"]=$GLOBALS["CLASS_UNIX"]->find_program("tunctl");}
		if(!isset($GLOBALS["sleepbin"])){$GLOBALS["sleepbin"]=$GLOBALS["CLASS_UNIX"]->find_program("sleep");}
		if(!isset($GLOBALS["echobin"])){$GLOBALS["echobin"]=$GLOBALS["CLASS_UNIX"]->find_program("echo");}
		if(!isset($GLOBALS["rmbin"])){$GLOBALS["rmbin"]=$GLOBALS["CLASS_UNIX"]->find_program("rm");}
		if(!isset($GLOBALS["touchbin"])){$GLOBALS["touchbin"]=$GLOBALS["CLASS_UNIX"]->find_program("touch");}
		if(!isset($GLOBALS["dhclientbin"])){$GLOBALS["dhclientbin"]=$GLOBALS["CLASS_UNIX"]->find_program("dhclient");}
		if(!isset($GLOBALS["PHP5BIN"])){$GLOBALS["PHP5BIN"]=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();}
		if(!isset($GLOBALS["MIITOOLBIN"])){$GLOBALS["MIITOOLBIN"]=$GLOBALS["CLASS_UNIX"]->find_program("mii-tool");}
		
		
		
	}
	private function LoadProcNetDev(){
		$unix=new unix();
		if(!isset($GLOBALS["dhclientbin"])){$GLOBALS["dhclientbin"]=$unix->find_program("dhclient");}
		if(function_exists("build_progress")){build_progress(50, "LoadProcNetDev() open /proc/net/dev");}
		$datas=explode("\n",@file_get_contents("/proc/net/dev"));
		if(function_exists("build_progress")){build_progress(50, "LoadProcNetDev() parsing /proc/net/dev");}
		foreach ( $datas as $num=>$line ){
			if(preg_match("#^(.+?):#",$line,$re)){
				$re[1]=trim($re[1]);
				if($GLOBALS["FIRST_TIME"]){
					if($re[1]<>"lo"){
						if(function_exists("build_progress")){build_progress(50, "LoadProcNetDev() ->root_build_debian_config_dhcpd($re[1])");}
						$this->root_build_debian_config_dhcpd($re[1]);}
				}
				$GLOBALS["SCRIPTS_TOP"][]="# [class/LoadProcNetDev/".__LINE__."] Found Network Interface <{$re[1]}>";
				$GLOBALS["PROC_NET_DEV"][$re[1]]=true;
			}
		}
		if(function_exists("build_progress")){build_progress(50, "LoadProcNetDev() ->DONE");}
		$GLOBALS["SCRIPTS_TOP"][]="# [".__LINE__."] Found ". count($GLOBALS["PROC_NET_DEV"])." Network Interface(s)";
	}
	public function BuildBridges_getlinked($bridge=null):array{
        if($bridge==null){return array();}
		$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db"); 
		$f=array();
		$sql="SELECT Interface FROM `nics` WHERE Bridged=1 AND BridgedTo='$bridge'";
		$results = $q->QUERY_SQL($sql);
        foreach ($results as $index=>$ligne){
			$f[]=$ligne["Interface"];
		}
		
		return $f;
		
		
	}
	public function GetCDIRNetwork($ipaddr,$network):string{

        if($network=="255.255.255.0"){
            $re=explode(".",$ipaddr);
            return "$re[0].$re[1].$re[2].0/24";
        }

		exec("/usr/share/artica-postfix/bin/ipcalc $ipaddr/$network 2>&1",$results);
		foreach ($results as $line){
			if(preg_match("#Network:\s+([0-9\.]+)\/([0-9]+)#i", $line,$re)){
				if($GLOBALS["VERBOSE"]){echo "$ipaddr/$network  = `$re[1]/$re[2]`\n";}
				return "$re[1]/$re[2]";}
		}
		
		$re=explode(".",$ipaddr);
		return "$re[0].$re[1].$re[2].0/$network";
			
	}
	function IfBridgeExists($br){
		$this->LoadTools();
		if(isset($GLOBALS["CLASS_SOCKETS"])){$sock=$GLOBALS["CLASS_SOCKETS"];}else{$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}
		exec("{$GLOBALS["brctlbin"]} showstp $br 2>&1",$results);
		if($GLOBALS["VERBOSE"]){echo count($results)." lines for {$GLOBALS["brctlbin"]} showstp $br\n";}
	
		foreach ($results as $index=>$line){
			if(preg_match("#^([a-zA-Z0-9]+)#",$line,$re)){
				if($GLOBALS["VERBOSE"]){echo "Found Bridge {$re[1]}\n";}
				if($re[1]=="$br"){return true;}
			}
		}
	
		return false;
	}	

}

class NetStatus{
        public $InterfaceRunning=false;
        public $IPADDR=null;
        public $NETMASK=null;
        public $BROADCAST=null;
        public $MacAddr=null;
        public $GATEWAY=null;

    public function __construct($Interface=null){
        if($Interface<>null){
            $this->ParseInterface($Interface);
        }
    }



    private function ParseInterface($Interface):bool{
        $IpaddrSatus=2;
        $IpaddrInfo=17;

        if(is_file("/sys/class/net/$Interface/address")) {
            $this->MacAddr = trim(@file_get_contents("/sys/class/net/$Interface/address"));
        }

        $this->InterfaceRunning=false;
        $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interface/info/$Interface"));


        if(!$data->Status){
            return false;
        }



        $State=$data->State; //
        if(preg_match("#up|broadcast|multicast|running#",$State)){
            $this->InterfaceRunning=true;
        }
        $this->IPADDR=$data->IpAddr;
        $this->NETMASK=$data->NetMask;
        $this->BROADCAST=$data->Broadcast;
        $this->GATEWAY = $data->Gateway;
        $this->MacAddr=$data->MacAddr;
        if($this->IPADDR=="127.0.0.1"){
            $this->IPADDR="0.0.0.0";
        }
        return true;
    }


}

?>