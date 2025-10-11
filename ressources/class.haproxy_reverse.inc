<?php


class haproxy_reverse{
	
	private $servicename=null;
	private $backends=array();
	
	
	function __construct($servicename){
		$this->servicename=$servicename;
		
	}
	
	public function build(){
		$backends=$this->buildBackends();
		$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
		$sql="SELECT *  FROM `haproxy_backends` WHERE servicename='$this->servicename' and enabled=1 ORDER BY bweight";
		$results = $q->QUERY_SQL($sql);
		$acl_num=0;
		$f[]="\tdefault_backend {$this->servicename}_default";
		$f[]="\thttp-request add-header X-Forwarded-For %[src]";
		foreach ($results as $index=>$ligne){
			$sitename=$ligne["sitename"];
			if($sitename=="*"){continue;}
			$acl_num++;
			$listen_ip=$ligne["listen_ip"];
			$listen_port=$ligne["listen_port"];
			$bweight=$ligne["bweight"];
			$HostHeader=null;
			$MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
			if(isset($MainConfig["HostHeader"])){if($MainConfig["HostHeader"]<>null){$HostHeader=$MainConfig["HostHeader"];}}
			if($HostHeader==$sitename){$HostHeader=null;}
			$f[]="#\t$sitename redirect to $listen_ip:$listen_port";
			
			
			if(substr($sitename, 0,1)=="."){
				$domain=substr($sitename, 1,strlen($sitename));
				$f[]="\tacl site$acl_num hdr_end(host) $domain";
				if($HostHeader<>null){
					$WEBSITES["HostH$acl_num"]=$sitename;
					$WEBSITES["site$acl_num"]=$HostHeader;
					$f[]="\thttp-request set-header Host $HostHeader if site$acl_num";}
				$WEBSITES["site$acl_num"]=$sitename;
				continue;
			}
			
			$f[]="\tacl site$acl_num hdr(host) $sitename";
			if($HostHeader<>null){
				$f[]="\tacl Host$acl_num hdr(host) $HostHeader";
				$WEBSITES["Host$acl_num"]=$sitename;
				$f[]="\thttp-request set-header Host $HostHeader if site$acl_num";
			}
				$WEBSITES["site$acl_num"]=$sitename;
		}
		
		$acl_num=0;
		while (list ($acl, $sitename) = each ($WEBSITES)){
			if(!isset($this->backends[$sitename])){
				$f[]="#\tno backends group for $sitename";
				continue;
			}
			$backendname=$this->backends[$sitename];
			$f[]="\tuse_backend {$this->servicename}_$backendname if $acl";
		}
		$f[]="";
		$f[]=$backends;
		return @implode("\n", $f);
	}
	
	private function buildBackends(){
		$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
		$sql="SELECT *  FROM `haproxy_backends` 
		WHERE servicename='$this->servicename' and enabled=1 ORDER BY bweight";
		$results = $q->QUERY_SQL($sql);
		$c=0;
		foreach ($results as $index=>$ligne){
			$c++;
			$ID=$ligne["ID"];
			$listen_ip=$ligne["listen_ip"];
			$listen_port=$ligne["listen_port"];
			$servername="{$this->servicename}_backendid{$ID}";
			$addr="$listen_ip:$listen_port";
			$bweight=$ligne["bweight"];
			$sitename=$ligne["sitename"];
			if($sitename=="*"){continue;}
			$check_params=null;
			$MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
			if(!is_numeric($MainConfig["inter"])){$MainConfig["inter"]=60000;}
			if(!is_numeric($MainConfig["fall"])){$MainConfig["fall"]=3;}
			if(!is_numeric($MainConfig["rise"])){$MainConfig["rise"]=2;}
			if(!is_numeric($MainConfig["maxconn"])){$MainConfig["maxconn"]=10000;}
			if( ($MainConfig["inter"]>0) && ($MainConfig["fall"]>0) && ($MainConfig["rise"]>0) ){
				$check_params=" inter {$MainConfig["inter"]} rise {$MainConfig["rise"]} fall {$MainConfig["fall"]}";
			}
			$MAIN[$sitename][]="server $servername $addr weight $bweight{$check_params}".$this->outgoing_address($ligne["localInterface"]);
			
		}
		
		$backendCount=0;
		while (list ($sitename, $array) = each ($MAIN)){
			
			$backendCount++;
			$this->backends[$sitename]="reverse_backend{$backendCount}";
			$f[]="";
			$f[]="backend {$this->servicename}_reverse_backend{$backendCount}";
			foreach ($array as $line){
				$f[]="\t$line";
			}
			
		}
		
		$f[]=$this->build_default();
		
		return @implode("\n", $f);
		
	}
	
	private function build_default(){
		$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
		$MAIN=array();
		$f[]="";
		$sql="SELECT *  FROM `haproxy_backends`
		WHERE servicename='$this->servicename' and enabled=1 AND sitename='*' ORDER BY bweight";
		$results = $q->QUERY_SQL($sql);
		$c=0;
		foreach ($results as $index=>$ligne){
			$MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
			if(!is_numeric($MainConfig["inter"])){$MainConfig["inter"]=60000;}
			if(!is_numeric($MainConfig["fall"])){$MainConfig["fall"]=3;}
			if(!is_numeric($MainConfig["rise"])){$MainConfig["rise"]=2;}
			if(!is_numeric($MainConfig["maxconn"])){$MainConfig["maxconn"]=10000;}
			if( ($MainConfig["inter"]>0) && ($MainConfig["fall"]>0) && ($MainConfig["rise"]>0) ){
				$check_params=" inter {$MainConfig["inter"]} rise {$MainConfig["rise"]} fall {$MainConfig["fall"]}";
			}
			$listen_ip=$ligne["listen_ip"];
			$listen_port=$ligne["listen_port"];
			$servername="{$this->servicename}_default$c";
			$addr="$listen_ip:$listen_port";
			$bweight=$ligne["bweight"];
			$sitename=$ligne["sitename"];
			$MAIN[]="server $servername $addr weight $bweight{$check_params}".$this->outgoing_address($ligne["localInterface"]);
			
		}
		$f[]="#\tDefaults backends = ".count($MAIN);
		
		
		if(count($MAIN)==0){
			$f[]="backend {$this->servicename}_default";
			$f[]="\tmode http";
			$f[]="\thttp-request deny";
			return @implode("\n",$f);
		}
		
		$f[]="backend {$this->servicename}_default";
		foreach ($MAIN as $line){
			$f[]="\t$line";
		}
		
		return @implode("\n",$f);
	}
	
	private function outgoing_address($localInterface=null){
		if($localInterface==null){return;}
	
		if(!class_exists("unix")){
			return " source [of $localInterface] usesrc clientip";
		}
	
		if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){
		$unix=new unix();
		$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();
		}
	
		$IPADDR=$GLOBALS["NETWORK_ALL_INTERFACES"][$localInterface]["IPADDR"];
		if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $localInterface = $IPADDR\n";}
		if($IPADDR==null){return;}
		if($IPADDR=="0.0.0.0"){return;}
		if($IPADDR=="127.0.0.1"){return;}
		return " source $IPADDR";
	}
	
}