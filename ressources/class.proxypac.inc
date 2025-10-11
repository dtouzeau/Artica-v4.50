<?php
class proxypacrules{
	var $ProxyPacDatas=array();
	var $ProxyPacFinal=null;
	function __construct($decodedArray){
		if(is_array($decodedArray)){
			$this->ProxyPacDatas=$decodedArray;
			$this->build();
		}
	}
	
	
	public function build(){
		$isInNet[]="function FindProxyForURL(url, host) {";
		$isInNet[]=$this->InternalAddresses();
		$datas=$this->ProxyPacDatas;
		if(is_array($datas["PROXYS"])){
			while (list ($num, $uri) = each ($datas["PROXYS"])){
				$condition=$this->proxy_list_conditions($num,$datas["CONDITIONS"][$num]);
				$proxiesCondition=$this->conditions_explode_proxies($num,$datas["CONDITIONS"][$num],$uri);
				if($condition<>null){
					$isInNet[]="\t$condition";
					$isInNet[]="\t\treturn \"".implode("; ",$proxiesCondition)."\"";
					$isInNet[]="";
				}
				$proxies[]="PROXY $uri;";
			}
		}
		
		
		if(is_array($datas["localHostOrDomainIs"])){
			while (list ($num, $servername) = each ($datas["localHostOrDomainIs"])){
			if(preg_match("#dnsDomainIs:(.+?)$#",trim($servername),$re)){
					
					if(strpos($re[1], "*")>0){
						$isInNet[]="\tif( shExpMatch(host ,\"{$re[1]}\") ){ return 'DIRECT';}";
						continue;
					}
				
				
					$isInNet[]="\tif(dnsDomainIs(host,\"{$re[1]}\") ){return \"DIRECT\";}";
					continue;
				}
				
				$isInNet[]="\tif(localHostOrDomainIs(host,\"$servername\") ){return \"DIRECT\";}";
			}	
		}
		//patch g_delmas 22/04/2011 (see http://www.articatech.net/forum/viewtopic.php?f=39&t=3429&p=16444#p16444)
		$isInNet[]="\tif(isPlainHostName(host)){return \"DIRECT\";}";
		
		if(is_array($datas["isInNet"])){
			while (list ($num, $array) = each ($datas["isInNet"])){
				if($array[1]==null){$array[1]="255.255.255.0";}
				$isInNet[]="\tif(isInNet(host,\"{$array[0]}\",\"{$array[1]}\")){return \"DIRECT\";}";
			}
		}
		
		if(is_array($proxies)){
			if($ProxyPacRemoveProxyListAtTheEnd<>1){
				$proxiesIsInnet="\treturn \"". implode("; ",$proxies)."\";";
				$proxiesIsInnet=str_replace(";;", ";", $proxiesIsInnet);
				$isInNet[]=$proxiesIsInnet;}
		}
		
		if(count($datas["FINAL_PROXY"]>0)){
			$proxiesIsInnet="\treturn \"". implode("; ",$this->clean_final_proxies($datas["FINAL_PROXY"]))."; DIRECT\";";
			$proxiesIsInnet=str_replace(";;", ";", $proxiesIsInnet);
			$isInNet[]=$proxiesIsInnet;
		}
		
		$isInNet[]="";
		$isInNet[]="}";
		$isInNet[]="";
		$this->ProxyPacFinal=@implode("\n", $isInNet);
		
		
		
	}
	
	private function InternalAddresses(){
		
		$f[]="\tvar resolved_ip = dnsResolve(host);";
		$f[]="";
		$f[]="\tif (isInNet(resolved_ip, \"10.0.0.0\", \"255.0.0.0\") ||";
		$f[]="\t\tisInNet(resolved_ip, \"172.16.0.0\",  \"255.240.0.0\") ||";
		$f[]="\t\tisInNet(resolved_ip, \"192.168.0.0\", \"255.255.0.0\") ||";
		$f[]="\t\tisInNet(resolved_ip, \"127.0.0.0\", \"255.255.255.0\"))";
		$f[]="\t\treturn \"DIRECT\";\n\n";
		return @implode("\n", $f);
		
	}
	
	
	
	private function proxy_list_conditions($num,$array){
	
	if(!is_array($array)){return null;}	
	
	while (list ($index, $condition) = each ($array)){

		if($condition["dnsDomainIs"]<>null){
			if(strpos($condition["dnsDomainIs"], "*")>0){
				$final[]="\tif( shExpMatch(host ,\"{$condition["dnsDomainIs"]}\") ){ return 'DIRECT';}";
				continue;
			}
			
			$final[]="dnsDomainIs(host, \"{$condition["dnsDomainIs"]}\")";
		}
		if($condition["isPlainhost"]<>null){$final[]="isPlainhost name(host)";}		
	}
	
	if(is_array($final)){return "if(".@implode($final," || ").")";}
	
	}
	
	private function conditions_explode_proxies($num,$array,$main_proxy){
		
		$final[]="PROXY $main_proxy";
		if(is_array($array)){
			reset($array);
			while (list ($index, $condition) = each ($array)){
				if($condition["FailOverProxy"]<>null){
				$final[]="PROXY {$condition["FailOverProxy"]}";
			}
		}
		}
		
		return $final;
	}
	
private function clean_final_proxies($array){
	if(!is_array($array)){return array();}
	while (list ($num, $uri) = each ($array)){
		$p[$uri]=$uri;
	}
	while (list ($num, $line) = each ($p)){
		$t[]="PROXY $line";
	}
	return $t;
}	
	
	
	
}