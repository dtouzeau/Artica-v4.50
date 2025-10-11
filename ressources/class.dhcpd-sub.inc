<?php
include_once(dirname(__FILE__)."/class.system.nics.inc");
class dhcpd_sub{
	
	private $nic=null;
	public $EnableDHCPServer=0;
	public $EnableDHCPUseHostnameOnFixed=0;
	public $authoritative=0;
	public $ping_check=1;
	public $get_lease_hostnames=1;
	public $ddns_domainname=null;
	public $max_lease_time=3600;
	public $local_pac_server=null;
	public $subnet=null;
	public $netmask=null;
	public $gateway=null;
	public $DNS_1=null;
	public $DNS_2=null;
	public $WINS=null;
	public $ntp_server=null;
	public $range1=null;
	public $range2=null;
	public $broadcast=null;
	public $deny_unkown_clients=0;
	
	public $route_subnet;
	public $route_gateway;
	public $route_netmask;
	public $route_nic=null;
	
	function __construct($nic=null){
		$this->nic=$nic;
		$this->checkTable();
		if($nic<>null){
			$this->Load();
		}
	}
	
	
	private function Load(){
		$q=new mysql();
		
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM dhcpd_nics WHERE nic='$this->nic'","artica_backup"));
		if($ligne["nic"]==null){return;}
		$this->EnableDHCPServer=$ligne["EnableDHCPUseHostnameOnFixed"];
		$this->EnableDHCPUseHostnameOnFixed=$ligne["EnableDHCPUseHostnameOnFixed"];
		$this->authoritative=$ligne["authoritative"];
		$this->ping_check=$ligne["ping_check"];
		$this->get_lease_hostnames=$ligne["get_lease_hostnames"];
		$this->ddns_domainname=$ligne["ddns_domainname"];
		$this->max_lease_time=$ligne["max_lease_time"];
		$this->local_pac_server=$ligne["local_pac_server"];
		$this->subnet=$ligne["subnet"];
		$this->netmask=$ligne["netmask"];
		$this->gateway=$ligne["gateway"];
		$this->DNS_1=$ligne["DNS_1"];
		$this->DNS_2=$ligne["DNS_2"];
		$this->WINS=$ligne["WINS"];
		$this->ntp_server=$ligne["ntp_server"];
		$this->range1=$ligne["range1"];
		$this->range2=$ligne["range2"];
		$this->broadcast=$ligne["broadcast"];
		$this->deny_unkown_clients=$ligne["deny_unkown_clients"];
	}
	
	
	private function checkTable(){
		$q=new mysql();
		$sql="CREATE TABLE IF NOT EXISTS `dhcpd_nics` (
		`nic` varchar(40) NOT NULL PRIMARY KEY,
		`EnableDHCPServer` smallint(1) not NULL DEFAULT 0 ,
		`EnableDHCPUseHostnameOnFixed` smallint(1) not NULL DEFAULT 0 ,
		`authoritative` smallint(1) not NULL DEFAULT 0 ,
		`ping_check` smallint(1) not NULL DEFAULT 0 ,
		`get_lease_hostnames` smallint(1) not NULL DEFAULT 0 ,
		`ddns_domainname` VARCHAR(128),
		`max_lease_time` smallint(10) not NULL DEFAULT 3600 ,	
		`local_pac_server`  VARCHAR(256),
		`subnet` VARCHAR(128),
		`netmask` VARCHAR(128),
		`gateway` VARCHAR(128),
		`DNS_1` VARCHAR(128),
		`DNS_2` VARCHAR(128),
		`WINS` VARCHAR(128),
		`ntp_server` VARCHAR(128),
		`range1` VARCHAR(128),
		`range2` VARCHAR(128),
		`broadcast` VARCHAR(128),
		`deny_unkown_clients` smallint(1) not NULL DEFAULT 0 
		
		)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return false;}
		
		
		if(!$q->FIELD_EXISTS("dhcpd_nics", "deny_unkown_clients","artica_backup")){
			$q->QUERY_SQL("ALTER IGNORE TABLE `dhcpd_nics` 
			ADD `deny_unkown_clients` SMALLINT(1) NOT NULL DEFAULT 0","artica_backup");
		}
		
		$sql="CREATE TABLE IF NOT EXISTS `dhcpd_routes` (
			`ID` INT(100) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`nic` varchar(40) NOT NULL,
			`subnet` VARCHAR(128),
			`gateway` VARCHAR(128),
			`netmask` VARCHAR(128)
			)  ENGINE = MYISAM;";
		
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return false;}
		
	}
	
	public function build(){
		$DNS=array();
		if($this->DNS_1<>null){
			$DNS[]=$this->DNS_1;
		}
		if($this->DNS_2<>null){
			$DNS[]=$this->DNS_2;
		}		
		
		
		
		$f[]="subnet $this->subnet netmask $this->netmask {";
		$f[]="\tinterface $this->nic;";
		if($this->authoritative==1){
			$f[]="\tauthoritative;";
		}else{
			$f[]="\tnot authoritative;";
		}
		$f[]="\tmax-lease-time $this->max_lease_time;";
		$f[]="\trange $this->range1 $this->range2;";
		$f[]="\toption subnet-mask $this->netmask;";
		$f[]="\toption routers $this->gateway;";
		$f[]="\toption broadcast-address $this->broadcast;";
		if(count($DNS)>0){
			$f[]="\toption domain-name-servers ".@implode(",", $DNS).";";
		}
		
		if(preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->WINS)){
			$f[]="\toption netbios-name-servers $this->WINS;";
		}
		
		if(preg_match("#http.*:\/\/.*#", $this->local_pac_server)){
			$f[]="\toption local-pac-server code 252 = text;";
			$f[]="\toption local-pac-server \"$this->local_pac_server\";";
		}
		
		if($this->ntp_server<>null){
			$f[]="\toption ntp-servers $this->ntp_server;";
				
		}
		
		if($this->ddns_domainname<>null){
			$f[]="\toption domain-name \"$this->ddns_domainname\";";
		}


		
		
		
		
		$f[]="\ton commit {";
		$f[]="\t\tset clip = binary-to-ascii(10, 8, \".\", leased-address);";
		$f[]="\t\tset clhw = binary-to-ascii(16, 8, \":\", substring(hardware, 1, 6));";
		$f[]="\t\tset ClientName = pick-first-value(option fqdn.hostname,option host-name,config-option host-name, noname);";
		$f[]="\t\texecute(\"/usr/share/artica-postfix/exec.dhcpd-leases.php\", \"commit\", clip, clhw, ClientName);";
		$f[]="\t}";
		$f[]="\ton release {";
		$f[]="\t\tset clip = binary-to-ascii(10, 8, \".\", leased-address);";
		$f[]="\t\tset clhw = binary-to-ascii(16, 8, \":\", substring(hardware, 1, 6));";
		$f[]="\t\tset ClientName = pick-first-value(option fqdn.hostname,option host-name,config-option host-name, noname);";
		$f[]="\t\texecute(\"/usr/share/artica-postfix/exec.dhcpd-leases.php\", \"release\", clip, clhw, ClientName);";
		$f[]="\t}";		
		
		$f[]="}";
		
		return @implode("\n", $f);
		
	}
	
	public function AddRoute($subnet,$netmask,$gateway){
		
		$sql="INSERT IGNORE INTO `dhcpd_routes` (`nic`,`subnet`,`netmask`,`gateway`)
		VALUES('$this->nic','$subnet','$netmask','$gateway')";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
		
	}
	
	public function LoadRoute($ID){
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM dhcpd_routes 
				WHERE ID='$ID'","artica_backup"));
		$this->route_subnet=$ligne["subnet"];
		$this->route_gateway=$ligne["gateway"];
		$this->route_netmask=$ligne["netmask"];
		$this->route_nic=$ligne["nic"];
	}
	
	public function DelRoute($ID){
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("DELETE FROM dhcpd_routes WHERE ID='$ID'","artica_backup"));
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	
	public function BuildRoutes(){
		$rtts=array();

		$q=new mysql();
		$results=$q->QUERY_SQL("SELECT * FROM dhcpd_routes","artica_backup");
		if(!$q->ok){	$conf=$conf."# dhcpd_routes = $q->mysql_error\n";}
		$f[]="# dhcpd_routes = ".mysqli_num_rows($results);
		
		$net=new system_nic();
		
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$netmask=$ligne["netmask"];
			$subnet=$ligne["subnet"];
			$gateway=$ligne["gateway"];
			$cdir=$net->GetCDIRNetwork($subnet,$netmask);
			$f[]="#[$this->nic]: $subnet/$netmask = $cdir ( $gateway )";
			if($cdir==null){continue; }
			if(!preg_match("#.+?\/([0-9]+)#", $cdir,$re)){continue;}
			$cdir=$re[1];
			
			
			$subnet2=explode(".",$subnet);
			$gateway2=explode(".",$gateway);
			if($subnet2[3]==0){unset($subnet2[3]);}
			$rtts[]="$cdir, ".@implode(",", $subnet2).", ".@implode(",", $gateway2);
			
			
		}
		
		if(count($rtts)>0){
			$f[]="option classless-routes ".@implode(",\n\t", $rtts).";";
			$f[]="option classless-routes-win ".@implode(",\n\t", $rtts).";";
		}
		
		return @implode("\n", $f);
		
	}
	
	
	
	public function Save(){
		
		$q=new mysql();
		$sql_add="INSERT IGNORE INTO dhcpd_nics (`nic`,`EnableDHCPServer`,`EnableDHCPUseHostnameOnFixed`,
		`authoritative`,`ping_check`,`get_lease_hostnames`,`ddns_domainname`,`local_pac_server`,
		`subnet`,`netmask`,`gateway`,`DNS_1`,`DNS_2`,`WINS`,`ntp_server`,`range1`,
		`range2`,`broadcast`,`deny_unkown_clients`)
				VALUES ('$this->nic','$this->EnableDHCPServer','$this->EnableDHCPUseHostnameOnFixed',
		'$this->authoritative','$this->ping_check','$this->get_lease_hostnames','$this->ddns_domainname','$this->local_pac_server',
		'$this->subnet','$this->netmask','$this->gateway','$this->DNS_1','$this->DNS_2','$this->WINS','$this->ntp_server','$this->range1',
		'$this->range2','$this->broadcast','$this->deny_unkown_clients')";
		
		$sql_edit="UPDATE dhcpd_nics SET 
				`EnableDHCPServer`='$this->EnableDHCPServer',
				`netmask`='$this->netmask',
				`subnet`='$this->subnet',
				`EnableDHCPUseHostnameOnFixed`='$this->EnableDHCPUseHostnameOnFixed',
				`authoritative`='$this->authoritative',
				`ping_check`='$this->ping_check',
				`get_lease_hostnames`='$this->get_lease_hostnames',
				`ddns_domainname`='$this->ddns_domainname',
				`local_pac_server`='$this->local_pac_server',
				`deny_unkown_clients`='$this->deny_unkown_clients',
				`broadcast`='$this->broadcast' WHERE `nic`='$this->nic'";
		
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM dhcpd_nics WHERE nic='$this->nic'","artica_backup"));
		if($ligne["nic"]==null){
			$q->QUERY_SQL($sql_add,"artica_backup");
			if(!$q->ok){echo $q->mysql_error."\n$sql_add";}
			return;
		}
		$q->QUERY_SQL($sql_edit,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n\n$sql_edit";return;}
		
		$sql_edit="UPDATE dhcpd_nics SET `DNS_2`='$this->DNS_2',
		`WINS`='$this->WINS',
		`ntp_server`='$this->ntp_server',
		`range2`='$this->range2',
		`range1`='$this->range1',
		`gateway`='$this->gateway',
		`DNS_1`='$this->DNS_1' WHERE `nic`='$this->nic'";
		$q->QUERY_SQL($sql_edit,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n\n$sql_edit";return;}
		
		
		
	}
	
	
}
