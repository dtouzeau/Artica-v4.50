<?php
include_once(dirname(__FILE__)."/class.tcpip.inc");

class squid_childs{
	private $LogsWarninStop=0;
	private $SquidUrgency=0;
	private $FireWallScriptsIps=array();
	private $FireWallMARK="-m comment --comment \"ArticaSquidChilds\"";
	
	function __construct(){
		$sock=new sockets();
		$this->LogsWarninStop=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsWarninStop"));
		$this->SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
	}
	
	
	
	
	public function deny_logs(){
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		$tr=array();
		

		
		$sql="SELECT ID  FROM proxy_ports WHERE Parent=1 AND SquidAsMasterLogChilds=0";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return null;}
		foreach ($results as $index=>$ligne){
			if(!isset($ligne["SquidAsMasterFollowxForward"])){$ligne["SquidAsMasterFollowxForward"]=0;}
			$SquidAsMasterFollowxForward=intval($ligne["SquidAsMasterFollowxForward"]);
			if($SquidAsMasterFollowxForward==0){
				$tr[]="deny_log ChildsProxy{$ligne["ID"]}\n";
				
			}
		}
			
		if(count($tr)>0){return @implode("\n", $tr);}
	}
	
	
	public function final_access(){
		$ipClass=new IP();
		$tr=array();
		$sql="SELECT ID,port FROM proxy_ports WHERE enabled=1 AND Parent=1";
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){return;}
	
		$c=0;
		foreach ($results as $index=>$ligne){
			$tr[]="http_access allow ParentPort{$ligne["ID"]}";
			$c++;
		}
		
		return @implode("\n", $tr);
		
		
		
	}
		
	
	
	
	function build(){
		@unlink("/etc/squid3/ChildsProxy.conf");
		@touch("/etc/squid3/ChildsProxy.conf");
		
		$ipClass=new IP();
		$tr=array();
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");

		
		$sql="SELECT ID,port,SquidAsMasterFollowxForward FROM proxy_ports WHERE enabled=1 AND Parent=1";
		
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){
			echo $q->mysql_error."\n";
			return;}
		
		$c=0;
		
		
		
		foreach ($results as $index=>$ligne){
			if(!isset($ligne["SquidAsMasterFollowxForward"])){$ligne["SquidAsMasterFollowxForward"]=0;}
			$SquidAsMasterFollowxForward=intval($ligne["SquidAsMasterFollowxForward"]);
			echo "Starting......: ".date("H:i:s")." [CHIL]: Port {$ligne["port"]}\n";
			$tr[]=$this->build_clients($ligne["ID"],$ligne["port"]);
			$tr[]="acl ParentPort{$ligne["ID"]} localport {$ligne["port"]}";
			if($SquidAsMasterFollowxForward==1){
			$tr[]="follow_x_forwarded_for allow ChildsProxy{$ligne["ID"]}";
			
			$c++;
			}
			

			
			
		}
		
	
		
		
		if(count($tr)==0){
			echo "Starting......: ".date("H:i:s")." [CHIL]: TR === 0, return...\n";
			
			return;}
		
		
		
		$tr[]=$this->build_all_clients();
		$tr[]="icp_access allow AllChilds";
		
		@file_put_contents("/etc/squid3/ChildsProxy.conf", @implode("\n", $tr));
		
	}
	

	
	
	private function build_all_clients(){
		$tr=array();
		$ipClass=new IP();
		$sql="SELECT portid,ipsrc FROM squid_balancers WHERE enabled=1";
		$q=new mysql();
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return "acl AllChilds src all";}
		
		while ($ligne = mysqli_fetch_assoc($results)) {
			if(trim($ligne["ipsrc"])==null){continue;}
			if(!$ipClass->isValid($ligne["ipsrc"])){continue;}
			$tr[]="acl AllChilds src {$ligne["ipsrc"]}\n";
		}		
		
		if(count($tr)==0){
			$tr[]="acl AllChilds src all\n";
		}
		
		return @implode("\n", $tr);
	}
	
	
	private function build_clients($portid,$localport){
		$MAIN=array();
		$ipClass=new IP();
		$tr=array();
		$sql="SELECT portid,ipsrc FROM squid_balancers WHERE enabled=1 and portid=$portid";
		$q=new mysql();
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return "acl ChildsProxy{$portid} localport $localport";}
		
		while ($ligne = mysqli_fetch_assoc($results)) {
			if(trim($ligne["ipsrc"])==null){continue;}
			if(!$ipClass->isIPAddressOrRange($ligne["ipsrc"])){continue;}
			$tr[]="acl ChildsProxy{$portid} src {$ligne["ipsrc"]}\n";
			$MAIN["ChildsProxy{$portid}"]=true;
			
			
			
		}
		
		if(count($tr)==0){
			$tr[]="acl ChildsProxy{$portid} localport $localport";
			
		}
		
		if(count($MAIN)>0){
			while (list ($Group, $none) = each ($MAIN) ){
				$tr[]="http_access allow $Group";
			}
			
		}
		
		return @implode("\n",$tr);
	
	}	
	
	

	
	
	public function access_log_childs(){
		if($this->LogsWarninStop==1){return;}
		if($this->SquidUrgency==1){return;}
		
		
		$sql="SELECT ID,port FROM proxy_ports WHERE enabled=1 AND Parent=1 AND SquidAsMasterLogChilds=1";
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		
		
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){return null;}
		if(count($results)==0){return null;}
		foreach ($results as $index=>$ligne){
			$conf[]="acl ParentPorts localport {$ligne["port"]}";
			
		}
		
		

		$conf[]="logformat childs %ts.%03tu %6tr %>a %Ss/%03>Hs %<st %rm %ru %[un %Sh/%<a %mt %{X-Forwarded-For}>h";
		$access_log="access_log stdio:/var/log/squid/childs-access.log on-error=drop logformat=childs buffer-size=64KB ParentPorts";
		$conf[]=$access_log;
		return @implode("\n", $conf);
	}	
	
	
	
	
}