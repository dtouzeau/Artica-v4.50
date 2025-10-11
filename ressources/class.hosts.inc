<?php
include_once(dirname(__FILE__) .'/class.tcpip.inc');
include_once(dirname(__FILE__)."/class.postgres.inc");
include_once(dirname(__FILE__)."/class.squid.familysites.inc");
class hosts{
	public $ok=false;
	public $unknown=0;
	public $createdate=null;
	public $hostid=0;
	public $proxyalias=null;
	public $proxyalias_org=null;
	public $fullhostname=null;
	public $mac=null;
    public $uuid=null;
	public $ipaddr=null;
    public $domainname=null;
	public $ipaddrBackup=null;
	public $dhcpfixed=0;
    public $dnsfixed=0;
	public $gateway=null;
	public $gateway2=null;
	public $dns1=null;
	public $dns2=null;
	public $timeserver=null;
	public $ntpserver=null;
	public $localpac=null;
	public $scanreport=null;
	public $mysql_error=null;
	public $domain=null;
	public $updated=null;
	public $hostalias1=null;
	public $hostalias2=null;
	public $hostalias3=null;
	public $hostalias4=null;
	public $hostname=null;
	public $ComputerOS=null;
    public $dhcpiface="";
	public $pxe_enabled=0;
	public $vendor=null;
	public $pxe_server="0.0.0.0";
	public $pxe_file="/pxelinux.0";
	public $hotspotwhite=0;
	
	public function __construct($search){
		if($GLOBALS["VERBOSE"]){echo "$search ??\n";}
		$search=trim($search);
		if($search==null){return;}
		$IpClass=new IP();
        if(strpos($search,"|")>0){
            $tb=explode("|",$search);
            $this->hostname=$tb[0];
            if($IpClass->isValid($tb[1])) {
                $this->ipaddr = $tb[1];
            }
            $this->LoadFromName();
            return;
        }
		
		if($IpClass->IsvalidMAC($search)){
			$this->mac=$search;
			$this->LoadFromMAC();
			return;
		}
		
		if($IpClass->isIPv4($search)){
			$this->ipaddr=$search;
			$this->LoadFromIP();
			return;
		}
		
		
		
		if(preg_match("#^(.+?)\$#", $search,$re)){$search=$re[1];}
		$this->hostname=$search;
		$this->LoadFromName();

	}
	
	private function LoadFromMAC(){
		$q=new postgres_sql();
		$this->ok=true;
		$this->mac=str_replace("-", ":", $this->mac);
		$this->mac=strtolower($this->mac);
		$sql="SELECT * FROM hostsnet WHERE mac='$this->mac'";
		
        if(function_exists("VERBOSE")) {
            VERBOSE($sql, __LINE__);
        }
		$ligne=$q->mysqli_fetch_array($sql);
        if(!$q->ok){
            if(function_exists("VERBOSE")) {
                VERBOSE($q->mysql_error, __LINE__);
            }
        }

        if(!isset($ligne["hostuuid"])){
            $this->PatchTable();
            $ligne["hostuuid"]=null;
        }
        if(function_exists("VERBOSE")) {
            VERBOSE("DNS1: {$ligne["dns1"]}", __LINE__);
        }

		if(!$q->ok){$this->mysql_error=$q->mysql_error;$this->ok=false;}
		$this->ipaddr=$ligne["ipaddr"];
		$this->createdate=$ligne["createdate"];
		$this->hostid=intval($ligne["hostid"]);
		$this->proxyalias=trim($ligne["proxyalias"]);
		$this->proxyalias_org=$this->proxyalias;
		$this->fullhostname=trim($ligne["fullhostname"]);
		$this->hostname=trim($ligne["hostname"]);
		$this->domainname=trim($ligne["domainname"]);
		$this->dhcpfixed=intval($ligne["dhcpfixed"]);
		$this->gateway=$ligne["gateway"];
		$this->gateway2=$ligne["gateway2"];
		$this->dns1=$ligne["dns1"];
		$this->dns2=$ligne["dns2"];
		$this->timeserver=$ligne["timeserver"];
		$this->ntpserver=$ligne["ntpserver"];
		$this->localpac=$ligne["localpac"];
		$this->scanreport=$ligne["scanreport"];
		$this->updated=$ligne["updated"];
		$this->hostalias1=$ligne["hostalias1"];
		$this->hostalias2=$ligne["hostalias2"];
		$this->hostalias3=$ligne["hostalias3"];
		$this->hostalias4=$ligne["hostalias4"];
		$this->ComputerOS=$ligne["computeros"];
        $this->dhcpiface=$ligne["dhcpiface"];
        $this->uuid=trim($ligne["hostuuid"]);
		$this->pxe_enabled=intval($ligne["pxe_enabled"]);
		$this->pxe_server=$ligne["pxe_server"];
		$this->pxe_file=$ligne["pxe_file"];
        $this->dnsfixed=intval($ligne["dnsfixed"]);

		if(!isset($ligne["hotspotwhite"])){
		    $this->PatchTable();
            $ligne["hotspotwhite"]=0;
        }

		$this->hotspotwhite=$ligne["hotspotwhite"];
		$this->vendor=$ligne["vendor"];
		$this->scanreport=$ligne["scanreport"];
		$this->ipaddrBackup=$ligne["ipaddr"];
		if($this->hostname==null){if($this->fullhostname<>null){$this->hostname=$this->fullhostname;}}
		if($this->hostname==null){if($this->proxyalias<>null){$this->hostname=$this->proxyalias;}}
		if($this->hostname==null){$this->hostname="unknown";}
		if($this->pxe_server==null){$this->pxe_server="0.0.0.0";}
		if($this->pxe_file==null){$this->pxe_file="/pxelinux.0";}
        if($this->uuid=null){$this->uuid=$this->gettuid();$this->SaveUuid();}
	}

	private function GetFreeMac(){
        $q=new postgres_sql();
	    while(true){
          $mac=implode(':',str_split(str_pad(base_convert(mt_rand(0,0xffffff),10,16).base_convert(mt_rand(0,0xffffff),10,16),12),2));
            $sql="SELECT mac FROM hostsnet WHERE mac='$mac'";
            $ligne=$q->mysqli_fetch_array($sql);
            if($ligne["mac"]==null){break;}


        }

        return $mac;
    }

    private function gettuid(){
        $uuid = bin2hex( openssl_random_pseudo_bytes(16) );
        for($cnt = 8; $cnt <=23; $cnt+=5) {
            $uuid = substr($uuid, 0, $cnt) . "-" . substr($uuid, $cnt);
        }
        return $uuid;
    }

    private function PatchTable(){
        $q=new postgres_sql();
        if(!$q->FIELD_EXISTS("hostsnet","hotspotwhite")){
            $q->ADD_FIELD("hostsnet", "hotspotwhite", "smallint");
        }



    }


	private function LoadFromIP(){
		$q=new postgres_sql();
		$sql="SELECT mac FROM hostsnet WHERE ipaddr='$this->ipaddr'";
		if($GLOBALS["VERBOSE"]){echo "$sql\n";}
		$ligne=@pg_fetch_array($q->QUERY_SQL($sql));
		
		if($GLOBALS["VERBOSE"]){print_r($ligne);}
		
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo "$q->mysql_error\n";}
			$this->mysql_error=$q->mysql_error;$this->ok=false;}
		$this->mac=$ligne["mac"];
		if($this->mac<>null){$this->LoadFromMAC();}
	}
	private function LoadFromName(){
		$q=new postgres_sql();
		$sql="SELECT mac FROM hostsnet WHERE fullhostname like '$this->hostname%'";
		$ligne=@pg_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){$this->mysql_error=$q->mysql_error;$this->ok=false;}
		$this->mac=$ligne["mac"];
		if($this->mac<>null){$this->LoadFromMAC();}
        if($this->ipaddr<>null){$this->LoadFromIP();}
	}
    private function SaveUuid(){
        $q=new postgres_sql();
        $q->QUERY_SQL("UPDATE hostsnet SET hostuuid='$this->uuid' WHERE mac='$this->mac'");
    }

	public function Save($noreload=false){

	    if($this->mac==null){
            $this->mac=$this->GetFreeMac();
            $this->uuid=$this->gettuid();
            $this->unknown=1;
        }
        if($this->uuid==null){
            $this->uuid=$this->gettuid();
        }
		$this->hostname=strtolower($this->hostname);
		$this->domainname=strtolower($this->domainname);
		$this->fullhostname=strtolower($this->fullhostname);
		if($this->fullhostname=="."){$this->fullhostname=null;}
		


		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#",$this->fullhostname)) {
            if (preg_match("#^(.+?)\.$#", $this->fullhostname, $re)) {
                $this->fullhostname = $re[1];
            }
        }
		
		if($this->fullhostname==null){
			if($this->hostname<>null){
				$this->fullhostname=strtolower($this->hostname);
			}
		}
        if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#",$this->fullhostname)) {
            if ($this->fullhostname <> null) {
                if (strpos($this->fullhostname, ".") > 0) {
                    $tt = explode(".", $this->fullhostname);
                    $this->hostname = $tt[0];
                    unset($tt[0]);
                    $this->domainname = @implode(".", $tt);
                }else{
                    $this->hostname=$this->fullhostname;
                }

            }
        }

		if($this->hostname=="unknown"){
			if($this->fullhostname<>"unknown"){$this->hostname=$this->fullhostname;}
		}
		
		writelogs("fullhostname=$this->fullhostname,hostname=$this->hostname",__FUNCTION__,__FILE__,__LINE__);

        if($this->hostname<>null){
			$this->fullhostname=strtolower($this->hostname);
		}
		if($this->domainname<>null){
            if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#",$this->fullhostname)) {
                $this->fullhostname = strtolower("$this->hostname.$this->domainname");
            }
		}


        $this->hotspotwhite=intval($this->hotspotwhite);
		if($this->pxe_server==null){$this->pxe_server="0.0.0.0";}
		if($this->pxe_file==null){$this->pxe_file="/pxelinux.0";}
		if($this->gateway==null){$this->gateway="0.0.0.0";}
		if($this->gateway2==null){$this->gateway2="0.0.0.0";}
		if($this->dns1==null){$this->dns1="0.0.0.0";}
		if($this->dns2==null){$this->dns2="0.0.0.0";}
		if($this->ipaddr==null){$this->ipaddr="0.0.0.0";}
		if($this->vendor==null){$this->vendor="unknown";}
		if($this->ipaddr=="0.0.0.0"){if($this->ipaddrBackup<>"0.0.0.0"){$this->ipaddr=$this->ipaddrBackup;}}
		
		writelogs("fullhostname=$this->fullhostname,hostname=$this->hostname",__FUNCTION__,__FILE__,__LINE__);
		if($this->fullhostname==null){
			if($this->ipaddr<>"0.0.0.0"){
				$ff=explode(".",$this->ipaddr);
				krsort($ff);
				$this->fullhostname=@implode("-", $ff);
				if($this->domainname<>null){$this->fullhostname=strtolower("$this->hostname.$this->domainname");}
			}
		}
		

		writelogs("fullhostname=$this->fullhostname,hostname=$this->hostname",__FUNCTION__,__FILE__,__LINE__);
		if($this->hostname==null){$this->hostname="unknown";}
		if($this->fullhostname==null){$this->fullhostname="unknown";}
		if($this->ipaddr==null){$this->ipaddr="0.0.0.0";}
		$this->PatchTable();
		$q=new postgres_sql();
        if(!$q->FIELD_EXISTS("hostsnet", "unknown")){$q->ADD_FIELD("hostsnet", "unknown", "smallint");}
		$sqlAdd[]="INSERT INTO hostsnet(createdate,hostid,mac,ipaddr,proxyalias,fullhostname,dhcpfixed,gateway,gateway2,dns1,dns2,timeserver,ntpserver,localpac,scanreport,hostname,domainname,hostalias1,hostalias2,hostalias3,hostalias4,computeros,vendor,pxe_enabled,pxe_server,pxe_file,\"unknown\",hotspotwhite,dnsfixed,hostuuid)";
		$sqlAdd[]=" VALUES (NOW(),'$this->hostid','$this->mac','$this->ipaddr','$this->proxyalias','$this->fullhostname',";
		$sqlAdd[]="'$this->dhcpfixed','$this->gateway','$this->gateway2','$this->dns1','$this->dns2',";
		$sqlAdd[]="'$this->timeserver','$this->ntpserver','$this->localpac','$this->scanreport','$this->hostname','$this->domainname',";
		$sqlAdd[]="'$this->hostalias1',";
		$sqlAdd[]="'$this->hostalias2',";
		$sqlAdd[]="'$this->hostalias3',";
		$sqlAdd[]="'$this->hostalias4',";
		$sqlAdd[]="'$this->ComputerOS',";
		$sqlAdd[]="'$this->vendor',";
		$sqlAdd[]="'$this->pxe_enabled',";
		$sqlAdd[]="'$this->pxe_server',";
		$sqlAdd[]="'$this->pxe_file',";
        $sqlAdd[]="'$this->unknown',";
        $sqlAdd[]="'$this->hotspotwhite',";
        $sqlAdd[]="'$this->dnsfixed',";
        $sqlAdd[]="'$this->uuid'";
		$sqlAdd[]=") ON CONFLICT DO NOTHING";
		
		$sql=@implode("", $sqlAdd);
		
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
			$this->ok=false;$this->mysql_error=$q->mysql_error."\n$sql\n";return false;}
		$today=date("Y-m-d H:i:s");
		$sql="UPDATE hostsnet SET 
		ipaddr='$this->ipaddr',
		proxyalias='$this->proxyalias',
		fullhostname='$this->fullhostname',
		hostname='$this->hostname',
		domainname='$this->domainname',
		dhcpfixed='$this->dhcpfixed',
		gateway='$this->gateway',
		gateway2='$this->gateway2',
		dns1='$this->dns1',
		dns2='$this->dns2',
		timeserver='$this->timeserver',
		ntpserver='$this->ntpserver',
		\"updated\"='$today',
		hostalias1='$this->hostalias1',
		hostalias2='$this->hostalias2',
		hostalias3='$this->hostalias3',
		hostalias4='$this->hostalias4',
		computeros='$this->ComputerOS',
		scanreport='$this->scanreport',
		vendor='$this->vendor',
		pxe_enabled='$this->pxe_enabled',
		pxe_server='$this->pxe_server',
		pxe_file='$this->pxe_file',
		dhcpiface='$this->dhcpiface',
		\"unknown\"='$this->unknown',
		hotspotwhite='$this->hotspotwhite',
        dnsfixed='$this->dnsfixed',
        hostuuid='$this->uuid'
		WHERE mac='$this->mac'";

		$q->QUERY_SQL($sql);
		if(!$q->ok){
            $this->ok=false;
            writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
            writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
            $this->mysql_error=$q->mysql_error;
            return false;
        }
		$sock=new sockets();
		$UnboundInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundInstalled"));
		$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
		if($UnboundInstalled==1){
			if($UnboundEnabled==1){
			    if(!$noreload) {
                    $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/reconfig");
                }
			}
		}
		
		$DHCPDInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDInstalled"));
		$EnableDHCPServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
        $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
        $sock->REST_API("/ipam/update/$this->mac");

        if($EnableDNSDist==1){
            $sock=new sockets();
            $sock->REST_API("/dnsfw/addhost/$this->fullhostname/$this->ipaddr");
        }
        $EnableKEA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
        if($EnableKEA==1){
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/kea/dhcp/reconfigure");
        }


		if($DHCPDInstalled==1){
			if($EnableDHCPServer==1){
			    if(!$noreload) {
                    $sock->REST_API("/dhcpd/service/reconfigure");
                }
			}
		}

        $EnableSquidMicroHotSpot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));

		if($EnableSquidMicroHotSpot==1){
            $memcached=new lib_memcached();
            if($this->hotspotwhite==1){
                $memcached->saveKey("MICROHOTSPOT_WHITE:$this->mac:$this->ipaddr",$this->proxyalias, 60);
            }else{
                $memcached->Delkey("MICROHOTSPOT_WHITE:$this->mac:$this->ipaddr");
            }

        }
        return true;
		
	}
	
	
	
}
function kea_interfaces():array{
    $net=new networking();
    $interfaces=$net->Local_interfaces();
    $MAIN=array();
    foreach ($interfaces as $interface=>$Ipaddress){
        $dhcpd=new dhcpd(0,1,$interface);
        if($dhcpd->service_enabled==0){
            continue;
        }
        $MAIN[$interface]="$dhcpd->subnet/$dhcpd->netmask";
    }
    return $MAIN;
}