<?php
include_once(dirname(__FILE__)."/class.tcpip.inc");
class tcp_outgoing_interface{
	private $nicerror=null;
	
	function __construct(){
		if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){$unix=new unix();$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();}
		if(!isset($GLOBALS["NETWORK_ALL_NICS"])){$unix=new unix();$GLOBALS["NETWORK_ALL_NICS"]=$unix->NETWORK_ALL_INTERFACES();}
		
	}
	
	function build(){
		$conf[]=$this->tcp_outgoing_address_byacl();
		$conf[]=$this->tcp_outgoing_address_new();
        $conf[]="";
		@file_put_contents("/etc/squid3/tcp_outgoing_address.conf", @implode("\n", $conf));
	}
	private function tcp_outgoing_address_byacl(){
		$acls=new squid_acls_groups();
		$tcp_outgoing_address=$acls->buildacls_bytype("tcp_outgoing_address");
		$conf[]="# ------- ACLS --------";
		$conf[]="# tcp_outgoing_address ".count($tcp_outgoing_address)." rule(s) [".__LINE__."]";

		if(isset($GLOBALS["tcp_outgoing_address_errors"])){
			if(count($GLOBALS["tcp_outgoing_address_errors"])>0) {
                foreach ($GLOBALS["tcp_outgoing_address_errors"] as $index => $line) {
                    $conf[] = "# $line";
                }
            }
		}
		
		if(count($tcp_outgoing_address)>0){
            foreach ($tcp_outgoing_address as $index=>$line){
                $conf[]="tcp_outgoing_address $line";
            }
		}else{
			$conf[]="#[ACLS]: ACL Engine tcp_outgoing_address No rules..\n";
		}
		return @implode("\n", $conf);
	}
	private function tcp_outgoing_address_new(){
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$unix=new unix();
        $zInterfaces=array();
        $IPs=array();
		$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
        foreach ($NETWORK_ALL_INTERFACES as $interface=>$ARR){
            $ipaddr=$ARR["IPADDR"];
            $IPs[$ipaddr]=$interface;
            $zInterfaces[$interface]=$ipaddr;
        }


		$results=$q->QUERY_SQL("SELECT * FROM squid_outgoingaddr_acls WHERE enabled=1 ORDER BY zorder");
		$squid_acls_groups=new squid_acls_groups();
		$conf=array();
		foreach ($results as $index=>$ligne){
			$rulename=$ligne["rulename"];
			$aclid=$ligne["aclid"];
			$eth=$ligne["eth"];
            $interface=$ligne["interface"];
            $ipaddr=null;
            if($GLOBALS["VERBOSE"]){
                echo "**** GOT ID:$aclid Interface:$eth\n";
            }
            if(strlen($interface)>2){
                $ipaddr=$zInterfaces[$interface];
            }

            if(is_null($ipaddr)){
                if (isset($IPs[$eth])) {
                    $ipaddr = $eth;
                    echo "**** GOT ID:$aclid Interface:$eth ipaddr:$ipaddr\n";
                }
                if(is_null($ipaddr)) {
                    if(isset($NETWORK_ALL_INTERFACES[$eth])) {
                        $ipaddr = $NETWORK_ALL_INTERFACES[$eth]["IPADDR"];
                    }
                }
            }

			if($ipaddr=="0.0.0.0"){
				$conf[]="#[ACLS][$aclid]: [$interface] OR [$eth] no ip (0.0.0.0) on system, aborting L.".__LINE__."\n";
				continue;
			}
			if($ipaddr==null){
				$conf[]="#[ACLS][$aclid}: [$interface] OR [$eth] no ip (Null) on system, aborting L.".__LINE__."\n";
				continue;
			}			

			$acls_array=$squid_acls_groups->buildacls_bytype_items($aclid,false,"outgoingaddr_sqacllinks");
            $conf[]="#\t$aclid] rule:$rulename ". count($acls_array). " items";
			if(count($acls_array)==0){$acls_array[]="all";}
			$FINAL=@implode(" ", $acls_array);
			$FINAL=trim(str_replace("!!", "!", $FINAL));
			$conf[]="tcp_outgoing_address $ipaddr $FINAL";
			
		}
		return @implode("\n", $conf);
	}

}