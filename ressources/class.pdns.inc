<?php
include_once(dirname(__FILE__) . '/logs.inc');
include_once(dirname(__FILE__) . '/class.templates.inc');
include_once(dirname(__FILE__) . '/class.ldap.inc');
include_once(dirname(__FILE__) . '/class.mysql.inc');
include_once(dirname(__FILE__) . '/class.dnsmasq.inc');
include_once(dirname(__FILE__) . '/class.tcpip.inc');
class pdns{
	var $domainname=null;
	var $suffix;
	var $last_error;
	var $DisablePowerDnsManagement=0;
	var $PowerDNSDisableLDAP=0;
	var $ttl=null;
	var $prio=null;
	var $articasrv=null;
	var $POWER_DNS_INSTALLED=false;
//------------------------------------------------------------------------------------------------------------------------------	
	function __construct($domainname=null){
		$sock=new sockets();
		$users=new usersMenus();
		$this->POWER_DNS_INSTALLED=$users->POWER_DNS_INSTALLED;
		
		if(!isset($GLOBALS["DisablePowerDnsManagement"])){
			
			$this->DisablePowerDnsManagement=$sock->GET_INFO("DisablePowerDnsManagement");
			if(!is_numeric($this->DisablePowerDnsManagement)){$this->DisablePowerDnsManagement=0;}
			$GLOBALS["DisablePowerDnsManagement"]=$this->DisablePowerDnsManagement;
		}else{
			$this->DisablePowerDnsManagement=$GLOBALS["DisablePowerDnsManagement"];
		}
		
		
		if($domainname<>null){
			$this->domainname=strtolower($domainname);
			$this->BuildBranch();
			
		}
		
	}
	
	function AddDomain($domainname){
		if(class_exists("usersMenus")){
			$LOCAL_MDA=false;
			
			if(!$this->POWER_DNS_INSTALLED){return true;}
		}		
		
		$domainname=trim(strtolower($domainname));
		$q=new mysql();
		if(!$q->DATABASE_EXISTS("powerdns",true)){return;}
		$sql="SELECT id FROM domains WHERE `name`='$domainname'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		if($ligne["id"]>0){return true;}
		$time=date("YmdH");
		$q->QUERY_SQL("INSERT IGNORE INTO domains (name,`type`) VALUE('$domainname','MASTER')","powerdns");
		$nextid=$q->last_id;
		if($nextid>0){
		$q->QUERY_SQL("INSERT IGNORE INTO `records` (`domain_id`, `name`, `type`, `content`, `ttl`, `prio`, `change_date`, `ordername`, `auth`)
				VALUES ($nextid, '$domainname', 'SOA', 'ns1.$domainname hostmaster.$domainname $time 28800 7200 604800 86400', 86400, 0, 1343065389, NULL, NULL)","powerdns");
		}
				
	}
	
	function DelDomain($domainname){
		if(class_exists("usersMenus")){
			if(!$this->POWER_DNS_INSTALLED){return true;}
		}
		
		$domainname=trim(strtolower($domainname));
		$q=new mysql();
		if(!$q->DATABASE_EXISTS("powerdns",true)){return;}
		$sql="SELECT id FROM domains WHERE `name`='$domainname'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		if(!is_numeric($ligne["id"])){$ligne["id"]=0;}
		if($ligne["id"]==0){return true;}
		$q->QUERY_SQL("DELETE FROM records WHERE domain_id={$ligne["id"]}","powerdns");
		if(!$q->ok){return;}
		$q->QUERY_SQL("DELETE FROM domains WHERE id={$ligne["id"]}","powerdns");
		if(!$q->ok){return;}
		return true;
	}
	
//------------------------------------------------------------------------------------------------------------------------------
	private function mysql_checkMasterDefaulZone(){
		if(!$this->POWER_DNS_INSTALLED){return "localhost.localdomain";}
		$q=new mysql();
		$sql="SELECT name FROM domains WHERE id=1";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		$this->domainname=$ligne["name"];
		if($this->domainname==null){
			$this->domainname="localhost.localdomain";
		}
	}
	
//------------------------------------------------------------------------------------------------------------------------------
	private function mysql_checkMasterZone(){
		if(!$this->POWER_DNS_INSTALLED){return 0;}
		if(isset($GLOBALS["mysql_checkMasterZone:$this->domainname"])){return $GLOBALS["mysql_checkMasterZone:$this->domainname"];}
		if($this->domainname==null){$this->mysql_checkMasterDefaulZone();}
		
		$q=new mysql();
		$sql="SELECT id FROM domains WHERE name='$this->domainname'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		if(($ligne["id"]==0) OR !is_numeric($ligne["id"])){
		 	$q->QUERY_SQL("INSERT IGNORE INTO domains (name,`type`) VALUE('$this->domainname','MASTER')","powerdns");
		 	$nextid=$q->last_id;
		 	if(!$q->ok){return;}
		 	$time=date("YmdH");
		 	$q->QUERY_SQL("INSERT INTO `records` (`domain_id`, `name`, `type`, `content`, `ttl`, `prio`, `change_date`, `ordername`, `auth`)
			VALUES ($nextid, '$this->domainname', 'SOA', 'ns1.$this->domainname hostmaster.$this->domainname $time 28800 7200 604800 86400', 86400, 0, 1343065389, NULL, NULL)","powerdns");
		 	
		}else{
			$nextid=$ligne["id"];
			
		}
		$GLOBALS["mysql_checkMasterZone:$this->domainname"]=$nextid;
		return $nextid;
		
	}
	
	private function mysql_checkin_addr_arpa(){
		if(!$this->POWER_DNS_INSTALLED){return 0;}
		$q=new mysql();
		$sql="SELECT id FROM domains WHERE name='in-addr.arpa'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		if(($ligne["id"]==0) OR !is_numeric($ligne["id"])){
		 	$q->QUERY_SQL("INSERT IGNORE INTO domains (name,`type`) VALUE('in-addr.arpa','MASTER')","powerdns");
		 	$nextid=$q->last_id;
		 	if(!$q->ok){return;}
		 	$time=date("YmdH");
		 	$q->QUERY_SQL("INSERT INTO `records` (`domain_id`, `name`, `type`, `content`, `ttl`, `prio`, `change_date`, `ordername`, `auth`)
			VALUES ($nextid, 'in-addr.arpa', 'SOA', 'ns1.in-addr.arpa hostmaster.in-addr.arpa $time 28800 7200 604800 86400', 86400, 0, 1343065389, NULL, NULL)","powerdns");
		 	
		}else{
			$nextid=$ligne["id"];
		}
		
		return $nextid;		
		
		
		
	}
	
	
	

	
//------------------------------------------------------------------------------------------------------------------------------	
	private function GetDNFromMac($macaddress){
		$suffix="ou=dns,$ldap->suffix";
		$sr = @ldap_search($ldap->ldap_connection,$suffix,"(&(objectclass=dNSDomain2)(MacRecord=$macaddress))",array());
		if($sr){
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
			return $hash[0]["dn"];
		}
	}
	
//------------------------------------------------------------------------------------------------------------------------------
	private function DeleteFromMacAddrr($macaddress){
		$ldap=new clladp();
		if($macaddress==null){return null;}
		$suffix="ou=dns,$ldap->suffix";
		
		$sr = @ldap_search($ldap->ldap_connection,$suffix,"(&(objectclass=dNSDomain2)(MacRecord=$macaddress))",array());
		if ($sr) {
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
			writelogs("find mac $macaddress ".count($hash)." entries",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			for($i=0;$i<$hash["count"];$i++){
				if($hash[$i]["dn"]<>null){
					writelogs("Delete {$hash[$i]["dn"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$ldap->ldap_delete($hash[$i]["dn"]);
				}
			}
		}else{
			writelogs("find mac (&(objectclass=dNSDomain2)(MacRecord=$macaddress)) ERROR",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		
		
	}
//------------------------------------------------------------------------------------------------------------------------------	
	private function ArticaMetaReplic(){
		if($this->DisablePowerDnsManagement==1){return;}
		$sock=new sockets();
		if($sock->GET_INFO("ArticaMetaEnabled")==1){
			$sock->getFrameWork("cmd.php?artica-meta-export-dns=yes");
		}		
	}
	
	private function mysql_EditIPName($computername,$IP,$DnsType,$id=0,$obs=null){
		if(!is_numeric($id)){$id=0;}
		if(trim($computername)==null){return false;}
		$tr=array();
		if(preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$computername)){
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]::Cannot put in DNS an ip address as computer name $computername\n";}
			writelogs("Cannot put in DNS an ip address as computer name",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		
		if(strpos($computername, ".")>0){
			$tr=explode(".", $computername);
			$computername=strtolower($tr[0]);
			unset($tr[0]);
			$this->domainname=strtolower(@implode(".", $tr));
		}
				
			
		if(substr($computername,0,1)=='.'){$computername=substr($computername,1,strlen($computername));}
		$computername=str_replace('..','.',$computername);
		$computername=str_replace('$','',$computername);
		$fqdn="$computername.$this->domainname";		
		$fqdn=str_replace('..','.',$fqdn);	
		
		
		
		$domain_id=$this->mysql_checkMasterZone();
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]::`$fqdn`:: domain:`$this->domainname` ID:[$domain_id] \n";}
		if($domain_id<1){
			$this->AddDomain($this->domainname);
			$domain_id=$this->mysql_checkMasterZone();
		}
		
		if($domain_id<1){
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]::`$fqdn`:: Failed unable to find domain_id for `$this->domainname` \n";}
			return false;
		}
		
		if(!is_numeric($this->ttl)){$this->ttl=86400;}
		if(!is_numeric($this->prio)){$this->prio=0;}
		if($this->ttl<500){$this->ttl=86400;}
		$q=new mysql();
		$zdate=time();
		if($id==0){
			$sql="SELECT id FROM records WHERE name='$fqdn' AND `type`='$DnsType'";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"powerdns"));
			$id=$ligne["id"];
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]::`$fqdn`::Type: `$DnsType` id:`$id` \n";}
		}
		
		
		if($id>0){
			$obssql=null;
			if($obs<>null){$obssql=",`explainthis`='".mysql_escape_string2($obs)."'";}
			$sql="UPDATE records SET content='$IP',`change_date`='$zdate',
					`ttl`='$this->ttl',
					`prio`='$this->prio'$obssql WHERE id='$id'";
			$q->QUERY_SQL($sql,"powerdns");
			if(!$q->ok){
				if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]::`$fqdn`:: $q->mysql_error => FAILED \n$sql\n";}
				return false;
			}
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]::`$fqdn`:: CheckARPA($IP,$fqdn)\n";}
			$this->CheckARPA($IP,$fqdn);
			return true;
		}
		if($obs<>null){$obF=",`explainthis`";$obD=",'".mysql_escape_string2($obs)."'";}			$idU=$this->GetHostnameFromIPAddr($IP,$fqdn);
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]::`$fqdn`::$IP => $idU \n";}
			
		if($idU<>null){
			if(!$this->AddAliase($domain_id, $fqdn, $idU,$obs)){
				if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]::`$fqdn`::AddAliase($domain_id, $fqdn, $idU,$obs) => FAILED \n";}
				return false;}
			$this->CheckARPA($IP,$fqdn);
			return true;
		}

		$q->QUERY_SQL("INSERT INTO records 
		(`domain_id`,`name`,`type`,`content`,`ttl`,`prio`,`change_date`,`articasrv`$obF) 
		VALUES($domain_id,'$fqdn','$DnsType','$IP','$this->ttl','$this->prio','$zdate','$this->articasrv'$obD)","powerdns");
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]::`$fqdn`:: $q->mysql_error => FAILED \n";}
			return false;}	
		$this->CheckARPA($IP,$fqdn);
		return true;
		
	}
	
	private function AddAliase($domain_id,$alias,$hostname_src,$obs=null){
		$q=new mysql();
		$sql="SELECT id FROM records WHERE name='$alias' AND content='$hostname_src'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		if($ligne["id"]>0){return true;}
		
		if(!is_numeric($this->ttl)){$this->ttl=86400;}
		if(!is_numeric($this->prio)){$this->prio=0;}
		if($this->ttl<500){$this->ttl=86400;}
		$change_date=date("YmdH");
		if($obs<>null){$obF=",`explainthis`";$obD=",'".mysql_escape_string2($obs)."'";}
		$sql="INSERT IGNORE INTO records (domain_id, name, type, content, ttl, prio, change_date$obF)
		VALUES ($domain_id,'$alias','CNAME','$hostname_src','{$this->ttl}','{$this->prio}','$change_date'$obD)";
		$q->QUERY_SQL($sql,"powerdns");
		if(!$q->ok){return false;}
		return true;
	}
	
	private function CheckARPA($IP,$fqdn){
		$q=new mysql();
		$articasrv_data=null;
		if(!preg_match('#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#',$IP,$re)){return;}
		$arpa_domain_id=$this->mysql_checkin_addr_arpa();
		if($arpa_domain_id==0){return;}
		$arpa="{$re[4]}.{$re[3]}.{$re[2]}.{$re[1]}.in-addr.arpa";
		$sql="SELECT id FROM records WHERE content='$fqdn'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		if(!is_numeric($this->ttl)){$this->ttl=86400;}
		if(!is_numeric($this->prio)){$this->prio=0;}
		if($this->ttl<500){$this->ttl=86400;}		
		
		$zdate=date("YmdH");
		if($ligne["id"]>0){
			$q->QUERY_SQL("UPDATE records SET `name`='$arpa',`change_date`='$zdate',`ttl`='$this->ttl',`prio`='$this->prio' WHERE id='{$ligne["id"]}'","powerdns");
			if(!$q->ok){return false;}
			return true;
		}
		

		$q->QUERY_SQL("INSERT INTO records (`domain_id`,`name`,`type`,`content`,`ttl`,`prio`,`change_date`,`articasrv`) VALUES($arpa_domain_id,'$arpa','PTR','$fqdn','$this->ttl','$this->prio','$zdate','$this->articasrv')","powerdns");
		if(!$q->ok){return false;}		
		return true;
	}
	
	
	private function GetHostnameFromIPAddr($IP,$hostname){
		$q=new mysql();
		$sql="SELECT id,name FROM records WHERE content='$IP'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		$name=$ligne["name"];
		if($name==$hostname){return null;}
		return $name;
	}
	
	
//------------------------------------------------------------------------------------------------------------------------------	
	public function EditIPName($computername,$IP,$DnsType,$macaddress=null,$obs=null){
			$computername=trim(strtolower($computername));
			$IpClass=new IP();
			if(!$IpClass->isIPAddress($IP)){return false;}
			if(trim($computername)==null){return false;}
			$IP=trim($IP);
			$this->domainname=trim(strtolower($this->domainname));
			if(!$this->mysql_EditIPName($computername,$IP,$DnsType,$macaddress,$obs)){
				if($GLOBALS["VERBOSE"]){echo "mysql_EditIPName($computername,$IP,$DnsType...) return false\n";}
				return false;
			}
			if($this->PowerDNSDisableLDAP==1){
				if($GLOBALS["VERBOSE"]){echo "PowerDNSDisableLDAP==1 return True\n";}
				return true;}
			if($this->DisablePowerDnsManagement==1){
				if($GLOBALS["VERBOSE"]){echo "DisablePowerDnsManagement==1 return True\n";}
				return true;}
			if($GLOBALS["VERBOSE"]){echo "EditIPName -> check lDAP...\n";}
			$ldap=new clladp();
			$up=array();
			
			if(preg_match('#[0-9]+\.[0-9]+\.[0-9]+\.#',$computername)){
				writelogs("Cannot put in DNS an ip address as computer name",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
			
			if(strpos($computername, ".")>0){
					$tr=explode(".", $computername);
					$computername=strtolower($tr[0]);
					unset($tr[0]);
					if($this->domainname==null){
						$this->domainname=strtolower(@implode(".", $tr));
					}
				}			
			
			if($this->domainname==null){
				writelogs("Cannot put in DNS a computer that did not have any domain",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}	

			if(substr($computername,0,1)=='.'){$computername=substr($computername,1,strlen($computername));}
			$computername=str_replace('..','.',$computername);
			$computername=str_replace('$','',$computername);
			$fqdn="$computername.$this->domainname";		
			$fqdn=str_replace('..','.',$fqdn);		
			$fqdn=str_replace('$','',$fqdn);
			

			
			$macaddress=trim($macaddress);
			if($macaddress<>null){
				$dn=$this->GetDNFromMac($macaddress);
				if($dn<>null){
					writelogs("$macaddress = \"$dn\"",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$up["associateddomain"]="$fqdn";
					if(!$ldap->Ldap_add_mod($dn,$up)){echo $ldap->ldap_last_error;return false;}
					$this->ArticaMetaReplic();
					return true;
				}
			}
			
			
			
			$dn="dc=$computername,$this->suffix";
			if(!$ldap->ExistsDN($dn)){
				writelogs("Add $dn",__CLASS__.'/'.__FUNCTION__,__FILE__);
				$up["objectClass"][]="top";
				$up["objectClass"][]="dNSDomain2";
				$up["objectClass"][]="domainRelatedObject";
				$up["associatedDomain"][]="$fqdn";
				$up["dc"][0]=$computername;
				if($macaddress<>null){$up["MacRecord"][]=$macaddress;}
				if($DnsType=="A"){$up["arecord"][]=$IP;}
				if(!$ldap->ldap_add($dn,$up)){$this->last_error=$ldap->ldap_last_error; return false;}
			}else{
				if($DnsType=="A"){$up["arecord"][]=$IP;}
				$up["arecord"][]=$IP;
				if(!$ldap->Ldap_modify($dn,$up)){$this->last_error=$ldap->ldap_last_error; return false;}
				
			}

			unset($up);
			if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#",$IP,$re)){
				$arpa1=$re[3].".".$re[2].".". $re[1].".in-addr.arpa";
				$arpa2_dc_ip=$re[4].".".$re[3].".".$re[2]."in-addr.arpa";
				
			}else{
				$this->last_error=" $IP bad value\n";
				return false;
			}
			
			$dn="dc=$arpa1,$this->suffix";
			if(!$ldap->ExistsDN($dn)){
				writelogs("Add $dn",__CLASS__.'/'.__FUNCTION__,__FILE__);
				$up["objectClass"][]="top";
				$up["objectClass"][]="dNSDomain2";
				$up["objectClass"][]="domainRelatedObject";	
				$up["dc"][]="$arpa1";	
				$up["soarecord"][]="ns.$this->domainname me@$this->domainname 1 1800 3600 86400 7200";
				$up["nsrecord"][]="ns.$this->domainname";
				$up["associateddomain"][]="$arpa1";
				if(!$ldap->ldap_add($dn,$up)){$this->last_error=$ldap->ldap_last_error; return false;}
			}
			unset($up);
			$dn="dc={$re[4]},dc=$arpa1,$this->suffix";
		if(!$ldap->ExistsDN($dn)){
				writelogs("Add $dn",__CLASS__.'/'.__FUNCTION__,__FILE__);
				$up["objectClass"][]="top";
				$up["objectClass"][]="dNSDomain2";
				$up["objectClass"][]="domainRelatedObject";	
				//$up["objectClass"][]="ArticaComputerInfos";
				$up["dc"][]="$re[4]";	
				$up["ptrrecord"][]="$fqdn";
				$up["associateddomain"][]="{$re[4]}.$arpa1";
				$up["MacRecord"][]=$macaddress;
				if(!$ldap->ldap_add($dn,$up)){$this->last_error=$ldap->ldap_last_error; return false;}
			}else{
				$up["ptrrecord"]="$fqdn";
				if(!$ldap->Ldap_add_mod($dn,$up)){$this->last_error=$ldap->ldap_last_error; return false;}		
			}
			$this->ArticaMetaReplic();
			return true;
			
		}
		
	private function mysql_GetIP($servername){
		$q=new mysql();
		$sql="SELECT content FROM records WHERE name='$servername'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		return $ligne["content"];
		
	}
		
	public function GetIp($servername){
			
				return $this->mysql_GetIP($servername);
			
			//if($this->DisablePowerDnsManagement==1){return null;}
			$ldap=new clladp();
			$user=new usersMenus();
			$filter="(&(Objectclass=dNSDomain2)(associatedDomain=$servername))";
			$sr = @ldap_search($ldap->ldap_connection,"ou=dns,$ldap->suffix",$filter,array("aRecord"));
			if(!$sr){return null;}
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
			return $hash[0]["arecord"][0];
			
		}
		
	private function mysql_DelHostname($hostname_full){
		$q=new mysql();
		$q->QUERY_SQL("DELETE FROM records WHERE name='$hostname_full'","powerdns");
		$q->QUERY_SQL("DELETE FROM records WHERE content='$hostname_full'","powerdns");
	} 

	public function mysql_delete_record_id($id){
		$q=new mysql();
		$sql="SELECT name FROM records WHERE id='$id'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		$hostname_full=$ligne["name"];		
		$q->QUERY_SQL("DELETE FROM records WHERE id='$id'","powerdns");
		$q->QUERY_SQL("DELETE FROM records WHERE content='$hostname_full'","powerdns");		
		
	}
		
	public function DelHostname($hostname_full){
		return $this->mysql_DelHostname($hostname_full);
		if(trim($hostname_full)==null){return;}
		$dn=$this->GetIp($hostname_full);
		if(strlen($dn)<10){return null;}
		$ldap=new clladp();
		if(!$ldap->ldap_delete($dn,true)){
			echo $ldap->ldap_last_error;
		}
		
	}
	
	
		
	public function GetIpDN($servername){
			return $this->mysql_GetIP($servername);
			$ldap=new clladp();
			$user=new usersMenus();
			$filter="(&(Objectclass=dNSDomain2)(associatedDomain=$servername))";
			$sr = @ldap_search($ldap->ldap_connection,"ou=dns,$ldap->suffix",$filter,array("dn"));
			if(!$sr){return null;}
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
			return $hash[0]["dn"];		
		
	}
	
	private function mysql_IpToHosts($ip){
		$q=new mysql();
		$sql="SELECT name FROM records WHERE content='$ip'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"powerdns"));
		return $ligne["name"];		
		
	} 
	
		
	public function IpToHosts($ip){
		return $this->mysql_IpToHosts($ip);
		if($this->DisablePowerDnsManagement==1){return null;}
		$ldap=new clladp();
		
		$resolved=gethostbyaddr($ip);
		if($resolved<>null){
			if($resolved<>$ip){
				$array[$resolved]=$resolved;
			}
		}
		$filter="(&(Objectclass=dNSDomain2)(aRecord=$ip))";
		$sr = @ldap_search($ldap->ldap_connection,"ou=dns,$ldap->suffix",$filter,array("associatedDomain"));
		if(!$sr){return null;}
		$hash=ldap_get_entries($ldap->ldap_connection,$sr);
		
		
		for($i=0;$i<$hash["count"];$i++){
			for($Z=0;$Z<$hash[$i]["associateddomain"]["count"];$Z++){
				$array[$hash[$i]["associateddomain"][$Z]]=$hash[$i]["associateddomain"][$Z];
			}
			
		}
		
		if(is_array($array)){
			while (list ($index, $host) = each ($array) ){
				$return[]=$host;
			}
		}
		
		return $return;}
	

	
	
}




?>