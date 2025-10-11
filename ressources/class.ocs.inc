<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
include_once(dirname(__FILE__).'/class.templates.inc');
include_once(dirname(__FILE__).'/class.ldap.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__) . '/class.computers.inc');

	class ocs{
		var $mac=null;
		var $HARDWARE_ID=0;
		var $ComputerName;
		var $ComputerOS;
		var $ComputerIP;
		var $PROLOG_FREQ=0;
		var $dhcpfixed=0;
		function __construct($mac=null){
			if($mac=="unknown"){$mac=null;}
			$this->PATCHS_TABLES();
			if($mac<>null){
				$this->mac=strtoupper(trim($mac));
				$this->Loadinfos();
			}
		}
		
		private function Loadinfos(){
			if($this->mac==null){return;}
			$this->mac=strtoupper($this->mac);
			$sql="SELECT HARDWARE_ID FROM networks WHERE UPPER(MACADDR)='$this->mac' ORDER BY `IPMASK` DESC";
			
			$q=new mysql();
			if(!$q->DATABASE_EXISTS("ocsweb")){
				$sock=new sockets();
				writelogs("-> services.php?mysql-ocs=yes",__CLASS__."/".__FILE__,__FILE__,__LINE__);
				$sock->getFrameWork("services.php?mysql-ocs=yes");
				return;
			}
			
			
			
			$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'ocsweb'));
			if(!$q->ok){
				writelogs("$q->mysql_error",__CLASS__."/".__FILE__,__FILE__,__LINE__);
				if(strpos($q->mysql_error,"doesn't exist")>0){
					$sock=new sockets();
					writelogs("-> services.php?mysql-ocs=yes",__CLASS__."/".__FILE__,__FILE__,__LINE__);
					$sock->getFrameWork("services.php?mysql-ocs=yes");
					return;
				}
			}
			if($ligne["HARDWARE_ID"]>0){
				$this->HARDWARE_ID=$ligne["HARDWARE_ID"];
				
			}else{
				writelogs("$sql -> NULL",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			}
			
			$this->dhcpfixed=0;
			$ligneMAC=mysqli_fetch_array($q->QUERY_SQL("SELECT mac FROM dhcpd_fixed WHERE mac='$this->ComputerMacAddress'","artica_backup"));
			if($ligneMAC["mac"]<>null){$this->dhcpfixed=1;}
			
		}
		
		public function LoadParams(){
			if($this->HARDWARE_ID==0){return null;}
			$q=new mysql();
			$sql="SELECT * FROM hardware WHERE ID='$this->HARDWARE_ID'";
			$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'ocsweb'));
			$this->ComputerName=$ligne["NAME"];
			$this->ComputerOS=$ligne["OSNAME"];
			$this->ComputerIP=$ligne["IPADDR"];
			
			$sql="SELECT * FROM networks WHERE HARDWARE_ID='$this->HARDWARE_ID'";
			$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'ocsweb'));
			$this->mac=$ligne["MACADDR"];
			
		}
		
		public function UPDATE_IPADDR($ipaddr){
			if($ipaddr=="0.0.0.0"){return;}
			if($this->HARDWARE_ID==0){return;}
			$q=new mysql();
			$q->QUERY_SQL("UPDATE hardware SET `IPADDR`='$ipaddr' WHERE `ID`='$this->HARDWARE_ID'",'ocsweb');
		}
		
		public function ADD_HARDWARE($ipaddr,$mac){
			$q=new mysql();
			$newdate=date("Y-m-d H:i:s");
			if($mac=="00:00:00:00:00:00"){return true;}
			$mac=strtoupper($mac);
			$ligne=@mysqli_fetch_array($q->QUERY_SQL("SELECT ID,HARDWARE_ID FROM networks WHERE UPPER(MACADDR)='$mac' LIMIT 0,1",'ocsweb'));
			$id_net=$ligne["ID"];
			$HARDWARE_ID=$ligne["HARDWARE_ID"];
			
			if(intval($id_net)>0){
				$q->QUERY_SQL("UPDATE networks SET `IPADDRESS`='$ipaddr' WHERE `ID`='$id_net'",'ocsweb');
				if(!$q->ok){return false;}
				$ligne=@mysqli_fetch_array($q->QUERY_SQL("SELECT `IPADDR`,`NAME` FROM hardware WHERE ID=$HARDWARE_ID LIMIT 0,1",'ocsweb'));
				if($ligne["IPADDR"]==null){
					$NAME=gethostbyaddr($ipaddr);
					$sql="INSERT IGNORE INTO hardware(`IPADDR`,`NAME`,`LASTDATE`) VALUES ('$ipaddr','$NAME','$newdate')";
					$q->QUERY_SQL($sql,'ocsweb');
					$ID=$q->last_id;
					$q->QUERY_SQL("UPDATE networks SET `HARDWARE_ID`=$ID WHERE `ID`='$id_net'",'ocsweb');
					return $q->ok;
				}else{
					$q->QUERY_SQL("UPDATE hardware SET `IPADDR`='$ipaddr',`LASTDATE`='$newdate' WHERE `ID`='$HARDWARE_ID'",'ocsweb');
					
				}
				return $q->ok;
			}
			
			$NAME=gethostbyaddr($ipaddr);
			$sql="INSERT IGNORE INTO hardware(`IPADDR`,`NAME`,`LASTDATE`) VALUES ('$ipaddr','$NAME','$newdate')";
			$q->QUERY_SQL($sql,'ocsweb');
			$ID=$q->last_id;
			
			
			
			$sql="INSERT IGNORE INTO networks(`IPADDRESS`,`MACADDR`,`HARDWARE_ID`) VALUES ('$ipaddr','$mac','$ID')";
			$q->QUERY_SQL($sql,'ocsweb');
			if(!$q->ok){return false;}
			
			
			return $q->ok;
		}
		
		
		private function COMPUTER_ID_FROM_IP_INTERNAL($IPADDR){
			$q=new mysql();
			$sql="SELECT ID FROM hardware WHERE IPADDR='$IPADDR'";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"ocsweb"));
			if(!is_numeric($ligne["ID"])){$ligne["ID"]=0;}	
			return $ligne["ID"];
		}
		
		public function DeleteComputer(){
			if($this->mac==null){
				echo "NO MAC defined!";
				return false;
			}
			$q=new mysql();
			$this->mac=str_replace("-", ":", $this->mac);
			$this->mac=strtolower($this->mac);
			$MAC_UPPER=strtoupper($this->mac);
			$sql="SELECT ID,HARDWARE_ID FROM networks WHERE UPPER(MACADDR)='$MAC_UPPER'";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"ocsweb"));
			$ID=$ligne["ID"];
			$HARDWARE_ID=$ligne["HARDWARE_ID"];
			$q->QUERY_SQL("DELETE FROM hardware WHERE ID='$HARDWARE_ID'","ocsweb");
			if(!$q->ok){echo $q->mysql_error;return false;}
			$q->QUERY_SQL("DELETE FROM networks WHERE ID='$ID'","ocsweb");
			if(!$q->ok){echo $q->mysql_error;return false;}
			$computer=new computers();
			$uid=$computer->ComputerIDFromMAC($this->mac);
			if($uid<>null){
				$computer=new computers($uid);
				if(!$computer->RemoveHost()){return false;}
			}
			


			

			
		}
		
		public function UpdateDirect(){
			if($this->HARDWARE_ID==0){echo "HARDWARE_ID = 0 ";return false;}
	
			$q=new mysql();
			$q->QUERY_SQL("UPDATE hardware SET `IPADDR`='$this->ComputerIP',`NAME`='$this->ComputerName' 
					WHERE `ID`='$this->HARDWARE_ID'",'ocsweb');
			if(!$q->ok){echo $q->mysql_error;return false;}
			if($this->mac<>null){
				$MAC_UPPER=strtoupper($this->mac);
				$sql="SELECT ID FROM networks WHERE UPPER(MACADDR)='$MAC_UPPER'";
				$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"ocsweb"));
				if($ligne["ID"]>0){
					$NET_ID=$ligne["ID"];
					$q->QUERY_SQL("UPDATE networks SET IPADDRESS='$this->ComputerIP' WHERE ID='$NET_ID'",'ocsweb');
					if(!$q->ok){echo $q->mysql_error;return false;}
				}else{
					$sql="INSERT INTO networks (HARDWARE_ID,IPADDRESS,MACADDR) VALUES('$this->HARDWARE_ID','$this->ComputerIP','$MAC_UPPER')";
					$q->QUERY_SQL($sql,"ocsweb");
					if(!$q->ok){echo $q->mysql_error;return false;}
				}
				
			}
			
			return true;
			
		}
		
		
		public function EditComputer(){
			if($this->ComputerName==null){return;}
			if($this->mac==null){return;}
			$this->mac=str_replace("-", ":", $this->mac);
			$this->mac=strtolower($this->mac);
			$MAC=strtolower($this->mac);
			$NAME=strtolower($this->ComputerName);
			$IPADDR=$this->ComputerIP;
			$MAC=str_replace("-", ":", $MAC);
			
			$ComputerOS_edit=null;$ComputerOS_add_field=null;$ComputerOS_add_datd=null;
			$IPADDR_SQL=null;
			$date=date('Y-m-d H:i:s');
			$q=new mysql();
			$MAC_UPPER=strtoupper($this->mac);
			$sql="SELECT ID FROM networks WHERE UPPER(MACADDR)='$MAC_UPPER'";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"ocsweb"));
			if(!$q->ok){if($GLOBALS["VERBOSE"]){writelogs($q->mysql_error,__CLASS__."/".__FILE__,__FILE__,__LINE__);}return false;}	
			if(!is_numeric($ligne["ID"])){$ligne["ID"]=0;}
			
			if($this->ComputerOS<>null){
				$this->ComputerOS=addslashes($this->ComputerOS);
				$ComputerOS_add_field=",OSNAME";
				$ComputerOS_add_data=",'$this->ComputerOS'";
				$ComputerOS_edit=",OSNAME='$this->ComputerOS'";
			}
			
			if($ligne["ID"]==0){
				$HARDWARE_ID=$this->COMPUTER_ID_FROM_IP_INTERNAL($IPADDR);
				if($HARDWARE_ID==0){
					$sql="INSERT INTO hardware (NAME,IPADDR,LASTDATE$ComputerOS_add_field) VALUES('$NAME','$IPADDR','$date'$ComputerOS_add_data)";
					$q->QUERY_SQL($sql,"ocsweb");
					if(!$q->ok){if($GLOBALS["VERBOSE"]){writelogs($q->mysql_error,__CLASS__."/".__FILE__,__FILE__,__LINE__);}return false;}
					$HARDWARE_ID=$this->COMPUTER_ID_FROM_IP_INTERNAL($IPADDR);
					if($HARDWARE_ID==0){return false;}
				}
				
				$sql="INSERT INTO networks (HARDWARE_ID,IPADDRESS,MACADDR) VALUES('$HARDWARE_ID','$IPADDR','$MAC')";
				$q->QUERY_SQL($sql,"ocsweb");
				if(!$q->ok){if($GLOBALS["VERBOSE"]){writelogs($q->mysql_error,__CLASS__."/".__FILE__,__FILE__,__LINE__);}return false;}
			}else{
				$NET_ID=$ligne["ID"];
				$sql="SELECT HARDWARE_ID FROM networks WHERE ID='{$ligne["ID"]}'";
				$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"ocsweb"));
				$HARDWARE_ID=$ligne["HARDWARE_ID"];
				
				if($IPADDR<>null){
					$sql="UPDATE networks SET IPADDRESS='$IPADDR' WHERE ID='$NET_ID'";
					$q->QUERY_SQL($sql,"ocsweb");
					if(!$q->ok){if($GLOBALS["VERBOSE"]){writelogs($q->mysql_error,__CLASS__."/".__FILE__,__FILE__,__LINE__);}return false;}
				}
				if($IPADDR<>null){$IPADDR_SQL=",IPADDR='$IPADDR'";}
				$sql="UPDATE hardware SET NAME='$NAME',LASTDATE='$date'$IPADDR_SQL$ComputerOS_edit WHERE ID='$HARDWARE_ID'";
				$q->QUERY_SQL($sql,"ocsweb");
				if(!$q->ok){if($GLOBALS["VERBOSE"]){writelogs($q->mysql_error,__CLASS__."/".__FILE__,__FILE__,__LINE__);}return false;}			
			}
			

			
		
		}
		

		

		
		

		


		

		

		

		
		function COMPUTER_SEARCH_QUERY($pattern=null,$nolimit=0){
				$pattern=str_replace("*","%",$pattern);
				$pattern=$pattern."%";
				$pattern=str_replace("%%","%",$pattern);
				$osc=new ocs();
				
				if($nolimit==0){$LIMIT="LIMIT 0,50";}
				
				return "SELECT hardware.LASTCOME,hardware.OSNAME,hardware.PROCESSORT,
				networks.MACADDR,networks.IPADDRESS,networks.HARDWARE_ID,hardware.ID,hardware.NAME,hardware.IPADDR,hardware.IPSRC FROM networks,hardware WHERE 1 AND
				(networks.HARDWARE_ID=hardware.ID AND hardware.NAME LIKE '$pattern') 
				OR (networks.HARDWARE_ID=hardware.ID AND hardware.IPADDR LIKE '$pattern')
				OR (networks.HARDWARE_ID=hardware.ID AND hardware.IPSRC LIKE '$pattern')
				ORDER BY hardware.LASTCOME DESC
				$LIMIT
				";			
			
		}
		
		FUNCTION GET_HARDWARE_ID_FROM_MAC($MAC){
			$MAC=strtoupper(trim($MAC));
			if($_SESSION[__FUNCTION__][$MAC]<>null){return $_SESSION[__FUNCTION__][$MAC];}
			$sql="SELECT HARDWARE_ID FROM networks WHERE UPPER(MACADDR)='$MAC'";
			$q=new mysql();
			$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'ocsweb'));
			if(!$q->ok){writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			if($ligne["HARDWARE_ID"]>0){
				$_SESSION[__FUNCTION__][$MAC]=$ligne["HARDWARE_ID"];
			}
			return $ligne["HARDWARE_ID"];
		}
		
		FUNCTION GET_UID_FROM_HARDWARE_ID($ID){
			$sql="SELECT UPPER(MACADDR) as MACADDR FROM networks WHERE HARDWARE_ID=$ID";
			if($_SESSION[__FUNCTION__][$ID]<>null){return $_SESSION[__FUNCTION__][$ID];}
			
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"ocsweb");
			
			if(!$q->ok){
				writelogs("$sql $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			}
			
			$cmp=new computers();
			while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
				if(trim($ligne["MACADDR"])==null){
					writelogs("MACADDR -> NULL",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					writelogs("$sql $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					continue;
				}
				$uid=trim($cmp->ComputerIDFromMAC($ligne["MACADDR"]));
				if($uid<>null){
					$_SESSION[__FUNCTION__][$ID]=$uid;
					writelogs("{$ligne["MACADDR"]} -> $uid",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					return $uid;}
				writelogs("{$ligne["MACADDR"]} not in LDAP",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			}
			
		}
		
		
		FUNCTION GET_AGENTVER($MAC){
			
			$HARDWARE_ID=$this->GET_HARDWARE_ID_FROM_MAC($MAC);
			if($HARDWARE_ID<1){return null;}
			if($_SESSION[__FUNCTION__][$MAC]<>null){return $_SESSION[__FUNCTION__][$MAC];}
			$sql="SELECT USERAGENT FROM hardware WHERE ID=$HARDWARE_ID";
			$q=new mysql();
			$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'ocsweb'));
			if(!$q->ok){writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			if(trim($ligne["USERAGENT"])<>null){$_SESSION[__FUNCTION__][$MAC]=$ligne["USERAGENT"];}
			return $ligne["USERAGENT"];
			
		}
		
		private FUNCTION PATCHS_TABLES(){
			$q=new mysql();
			if(!$q->FIELD_EXISTS("networks", "meta", "ocsweb")){
				$q->QUERY_SQL("ALTER TABLE `networks` ADD `meta` SMALLINT(1) NOT NULL DEFAULT 0,ADD INDEX(`meta`)","ocsweb");
			}
			
		}
		
		FUNCTION REMOTE_AGENT_INSTALL_PROGRESS($uid){
			$sql="SELECT ID,PROGRESS,pourcent FROM deploy_tasks WHERE computer_id='$uid' AND TASK_TYPE='OCS_AGENT'";
			$q=new mysql();
			$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
			return array($ligne["ID"],$ligne["pourcent"],$ligne["PROGRESS"]);
		}
		
		/*
		 * return tableau HARDWARE_ID=>1 d'un fileid spécifié.
		 */
		
		function PACKAGE_HASH_AFFECTED_COMPUTERS($FILEID){
			if(isset($_SESSION["PACKAGE_HASH_AFFECTED_COMPUTERS"])){return $_SESSION["PACKAGE_HASH_AFFECTED_COMPUTERS"];}
			$ID=$this->PACKAGE_ID_FROM_FILEID($FILEID);
			$sql="SELECT HARDWARE_ID FROM devices WHERE IVALUE=$ID AND NAME='DOWNLOAD'";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"ocsweb");
			while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
				$_SESSION["PACKAGE_HASH_AFFECTED_COMPUTERS"][$ligne["HARDWARE_ID"]]=1;
				
			}
			
			return $_SESSION["PACKAGE_HASH_AFFECTED_COMPUTERS"];
			
		}
		
		function PACKAGE_IS_AFFECTED($FILEID,$HARDWARE_ID){
			$ID=$this->PACKAGE_ID_FROM_FILEID($FILEID);
			$sql="SELECT NAME FROM devices WHERE HARDWARE_ID='$HARDWARE_ID' AND IVALUE='$ID' AND NAME='DOWNLOAD'";
			$q=new mysql();
			$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'ocsweb'));
			if($ligne["NAME"]=='DOWNLOAD'){return true;}
		}
		
		function PACKAGE_AFFECT($FILEID,$HARDWARE_ID){
			if($this->PACKAGE_IS_AFFECTED($FILEID,$HARDWARE_ID)){return;}
			$ID=$this->PACKAGE_ID_FROM_FILEID($FILEID);
			$sql="INSERT INTO devices (NAME,HARDWARE_ID,IVALUE) VALUES('DOWNLOAD',$HARDWARE_ID,$ID)";
			$q=new mysql();
			$q->QUERY_SQL($sql,"ocsweb");
			writelogs($sql,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			if(!$q->ok){echo __FUNCTION__.":".$q->mysql_error;return;}
			$_SESSION["PACKAGE_HASH_AFFECTED_COMPUTERS"][$HARDWARE_ID]=1;
			
		}
		function PACKAGE_DESAFFECT($FILEID,$HARDWARE_ID){
			if(!$this->PACKAGE_IS_AFFECTED($FILEID,$HARDWARE_ID)){return;}
			$ID=$this->PACKAGE_ID_FROM_FILEID($FILEID);
			$sql="DELETE FROM devices WHERE HARDWARE_ID='$HARDWARE_ID' AND IVALUE='$ID' AND NAME='DOWNLOAD'";
			$q=new mysql();
			$q->QUERY_SQL($sql,"ocsweb");
			if(!$q->ok){echo __FUNCTION__.":".$q->mysql_error;return;}
			unset($_SESSION["PACKAGE_HASH_AFFECTED_COMPUTERS"][$HARDWARE_ID]);
			
		}
		
		function PACKAGE_DOWNLOAD_INFOS_HASH($FILEID){
			$sql="SELECT * FROM download_enable WHERE FILEID='$FILEID'";
			$q=new mysql();
			$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'ocsweb'));
			return $ligne;
		}
		
	function PACKAGE_AFFECT_COUNT_COMPUTERS($FILEID){
		$ID=$this->PACKAGE_ID_FROM_FILEID($FILEID);
		$sql="SELECT COUNT(HARDWARE_ID) as tcount FROM devices WHERE IVALUE='$ID' AND NAME='DOWNLOAD'";
		$q=new mysql();
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'ocsweb'));
		writelogs($sql."={$ligne["tcount"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		return $ligne["tcount"];
	}

	function PACKAGE_UPDATE_ALL_RESSOURCES(){
			$sock=new sockets();
			$OCSCertInfos=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OCSCertInfos")));
			$servername=$OCSCertInfos["OCSCertServerName"];
			$domainname=$OCSCertInfos["OCSCertDomainName"];
			$OCSWebPort=$sock->GET_INFO("OCSWebPort");
			$OCSWebPortSSL=$sock->GET_INFO("OCSWebPortSSL");
			if($OCSWebPort==null){$OCSWebPort=9080;}
			if($OCSWebPortSSL==null){$OCSWebPortSSL=$OCSWebPort+50;}	
			if($servername==null){return;}
			if($domainname<>null){
				$servername=$servername.".$domainname";
			}
			
			$sql="UPDATE download_enable SET INFO_LOC='$servername:$OCSWebPortSSL/download',PACK_LOC='$servername:$OCSWebPort/download'";
			$q=new mysql();
			$q->QUERY_SQL($sql,"ocsweb");
			if(!$q->ok){echo __CLASS__.'/'.__FUNCTION__."\n".$q->mysql_error;}
		
	}
	
	function SSL_SERVER_NAME(){
			$sock=new sockets();
			$OCSCertInfos=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OCSCertInfos")));
			$servername=$OCSCertInfos["OCSCertServerName"];
			$domainname=$OCSCertInfos["OCSCertDomainName"];
			$users=new usersMenus();
			if($servername==null){$servername=$users->hostname;}
			if($domainname<>null){
				if(preg_match("#^(.+?)\.#",$servername,$re)){$servername=$re[1];}
				$servername=$servername.".$domainname";
			}
			
			return $servername;
		
	}
	
	function INJECT_COMPUTER_TOLDAP($MACADDR){
		$q=new mysql();	
		$MACADDR=trim($MACADDR);
		if($MACADDR==null){
		$GLOBALS["INJECT_COMPUTER_TOLDAP"][]="INJECT_COMPUTER_TOLDAP:: MAC ADDRESS IS NULL";return ;}
		
		if($GLOBALS["MEM"][md5(__CLASS__.__FUNCTION__)][md5($MACADDR)]){return true;}
		$sql="SELECT HARDWARE_ID FROM networks WHERE MACADDR='$MACADDR'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"ocsweb"));
		
		if($ligne["HARDWARE_ID"]<1){
			$GLOBALS["INJECT_COMPUTER_TOLDAP"][]="INJECT_COMPUTER_TOLDAP:: MACADDR <1";
			echo "{corrupted_database}";
			return false;
		}
		$HARDWARE_ID=$ligne["HARDWARE_ID"];
		$sql="SELECT * FROM hardware WHERE ID='{$HARDWARE_ID}'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"ocsweb"));
		$cp=new computers();
		$cp->ComputerIP=$ligne["IPSRC"];
		$cp->ComputerMacAddress=$MACADDR;
		$cp->ComputerRealName=$ligne["NAME"];
		if($ligne["OSNAME"]<>null){$cp->ComputerOS=$ligne["OSNAME"];}
		$cp->ComputerCPU=$ligne["PROCESSORT"];
		$cp->uid=$ligne["NAME"];
		
		
		
		writelogs("Adding computer MAC:$MACADDR IPSRC:{$ligne["IPSRC"]} NAME:{$ligne["NAME"]} in LDAP",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		
		$sql="SELECT *  FROM bios WHERE HARDWARE_ID='$HARDWARE_ID'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"ocsweb"));
		$cp->ComputerMachineType=trim("{$ligne["SMODEL"]} {$ligne["SMANUFACTURER"]}");
		
		
		$val=$cp->Add();
		$GLOBALS["INJECT_COMPUTER_TOLDAP"][]="INJECT_COMPUTER_TOLDAP:: ->Add=$val";
		if($val){$GLOBALS["MEM"][md5(__CLASS__.__FUNCTION__)][md5($MACADDR)]=true;}
		return $val;		
	}
	
	function GET_OCSSERVER_URI(){
		$sock=new sockets();
		$users=new usersMenus();
		$ocswebservername=$sock->GET_INFO("ocswebservername");
		$OCSImportToLdap=$sock->GET_INFO("OCSImportToLdap");
		$UseFusionInventoryAgents=$sock->GET_INFO("UseFusionInventoryAgents");
		if($UseFusionInventoryAgents==null){$UseFusionInventoryAgents=1;}
		if($OCSImportToLdap==null){$OCSImportToLdap=60;}

	
		$OCSWebPort=$sock->GET_INFO("OCSWebPort");
		$OCSWebPortSSL=$sock->GET_INFO("OCSWebPortSSL");
		if($OCSWebPort==null){$OCSWebPort=9080;}
		if($OCSWebPortSSL==null){$OCSWebPortSSL=$OCSWebPort+50;}
		if($ocswebservername==null){$ocswebservername=$users->hostname;}
		if($UseFusionInventoryAgents==1){
			$http_suffix="https";
			$OCSWebPortLink=$OCSWebPortSSL;
			}else{
				$http_suffix="http";
				$OCSWebPortLink=$OCSWebPort;
			}
		$final="$http_suffix://$ocswebservername:$OCSWebPortLink/ocsinventory";
		$final=str_replace("http://http:", "http:", $final);
		$final=str_replace("https://https:", "https:", $final);
	}
	
	function COUNT_HARDWARE_SOFTWARES($PUBLISHER=null,$NAME=null){
	$NAME=trim($NAME);
	$md5=md5("$PUBLISHER$NAME");
	
	
	if($_SESSION["COUNT_HARDWARE_SOFTWARES"][$md5]>0){return $_SESSION["COUNT_HARDWARE_SOFTWARES"][$md5];}
	
	$q=new mysql();
	$PUBLISHER=$q->mysql_real_escape_string2($PUBLISHER);
	$NAME=$q->mysql_real_escape_string2($NAME);
	$sql="SELECT PUBLISHER,NAME,HARDWARE_ID FROM softwares 
	WHERE PUBLISHER='$PUBLISHER' AND NAME='$NAME' GROUP BY PUBLISHER,NAME,HARDWARE_ID";
	if($NAME==null){
		$sql="SELECT PUBLISHER,HARDWARE_ID FROM softwares WHERE PUBLISHER='$PUBLISHER' GROUP BY PUBLISHER,HARDWARE_ID";
	}
	
	
	$results=$q->QUERY_SQL($sql,"ocsweb");
	if(!$q->ok){
		writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
	}
	$num=@mysqli_num_rows($results);
	$_SESSION["COUNT_HARDWARE_SOFTWARES"][$md5]=$num;
	return $num;
}	

}

?>