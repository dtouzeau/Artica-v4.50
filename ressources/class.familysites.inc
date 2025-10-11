<?php
include_once(dirname(__FILE__)."/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/class.memcached.inc");
class familysite{
	
	private $memcached;
	private $fam;
	function __construct(){
		$GLOBALS["TCP_CLASS"]=new IP();
		$this->memcached=new lib_memcached();
		$this->fam=new squid_familysite();
	}
	
	public function GetComputerName($ip){
		if(!$GLOBALS["TCP_CLASS"]->isIPAddress($ip)){return;}
		$mem=new lib_memcached();
		$name=trim($mem->getKey("GetComputerName:$ip"));
		if($name<>null){return $name;}
		$name=gethostbyaddr($ip);
		$mem->saveKey("GetComputerName:$ip", $name,900);
		return $name;
	}

	private function SaveResolveCache(){
		$fname="/etc/squid3/ipcaches.db";
		@file_put_contents($fname, serialize($GLOBALS["resvip"]));
	}	
	private function LoadResolveCache(){
		if(isset($GLOBALS["resvip"])){return;}
		$unix=new unix();
		$fname="/etc/squid3/ipcaches.db";
		if($unix->file_time_min($fname)>15){$GLOBALS["resvip"]=array();}
		$GLOBALS["resvip"]=unserialize(@file_get_contents($fname));
	
	}	
	
	public function GetFamilySites($sitename){
		$result=$this->memcached->getKey("family:$sitename");
		if(trim($result)<>null){return $result;}
		$result=$this->fam->GetFamilySites($sitename);
		$this->memcached->saveKey("family:$sitename", $result);
		return $result;
	}
	
	private function getRegisteredDomain($signingDomain) {
		if(!isset($GLOBALS["tldTree"])){
			$GLOBALS["tldTree"]=unserialize(@file_get_contents(dirname(__FILE__)."/tldree.db"));
		}
	
		$signingDomainParts = explode('.', $signingDomain);
	
		$result = $this->findRegisteredDomain($signingDomainParts, $GLOBALS["tldTree"]);
	
		if ($result===NULL || $result=="") {
			// this is an invalid domain name
			return NULL;
		}
	
		// assure there is at least 1 TLD in the stripped signing domain
		if (!strpos($result, '.')) {
			$cnt = count($signingDomainParts);
			if ($cnt==1 || $signingDomainParts[$cnt-2]=="") return NULL;
			return $signingDomainParts[$cnt-2].'.'.$signingDomainParts[$cnt-1];
		}
		return $result;
	}


	// recursive helper method
	private function findRegisteredDomain($remainingSigningDomainParts, &$treeNode) {
	
		$sub = array_pop($remainingSigningDomainParts);
	
		$result = NULL;
		if (isset($treeNode['!'])) {
			return '#';
		} else if (is_array($treeNode) && array_key_exists($sub, $treeNode)) {
			$result = $this->findRegisteredDomain($remainingSigningDomainParts, $treeNode[$sub]);
		} else if (is_array($treeNode) && array_key_exists('*', $treeNode)) {
			$result = $this->findRegisteredDomain($remainingSigningDomainParts, $treeNode['*']);
		} else {
			return $sub;
		}
	
		// this is a hack 'cause PHP interpretes '' as NULL
		if ($result == '#') {
			return $sub;
		} else if (strlen($result)>0) {
			return $result.'.'.$sub;
		}
		return NULL;
	}	
	

	
	
}