<?php
include_once(dirname(__FILE__)."/class.acls.backend.inc");

class squid_acls_privileges{
	private $EnableKerbAuth=0;
	private $LockActiveDirectoryToKerberos=0;
	private $Groupsid=array();
	
	public function __construct(){
		
		$sock=new sockets();
		$this->EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        $this->LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$this->LockActiveDirectoryToKerberos=1;}
        if($this->LockActiveDirectoryToKerberos==1){$this->EnableKerbAuth=1;}
		
	}
	
	
	
	public function build(){
		if($GLOBALS["VERBOSE"]){unset($_SESSION["SQUID_DELEGATE_ACLS"]);}
		
		
		if(!isset($_SESSION["SQUID_DELEGATE_ACLS"])){
			if($this->EnableKerbAuth==1){$this->search_in_ad();}
			if(count($this->Groupsid)==0){
				$this->search_in_ldap();
			}
			
			$_SESSION["SQUID_DELEGATE_ACLS"]=$this->Groupsid;
		}
		if(count($_SESSION["SQUID_DELEGATE_ACLS"])==0){
			if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Array = 0<br></span>";}
			return;
		}
		
		if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>Array = ".count($_SESSION["SQUID_DELEGATE_ACLS"])."<br></span>";}
		
		$tr[]="<table style='width:100%'>";
		reset($_SESSION["SQUID_DELEGATE_ACLS"]);
		while (list ($gid, $groupname) = each ($_SESSION["SQUID_DELEGATE_ACLS"]) ){
			$q=new mysql_squid_builder();
			$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID=$gid"));
			$GroupName=$ligne["GroupName"];
			$tr[]="<tr>";
			$tr[]="<td style='width:16px'><img src='img/arrow-right-16.png'></td>";
			$tr[]="<td style='font-size:16px'>{manage}: <a href=\"javascript:blur();\" OnClick=\"javascript:GotoAclsDeletegate($gid)\" style='text-decoration:underline;font-weight:bold'>$GroupName</td>";
			$tr[]="</tr>";
		}
		$tr[]="</table>";
		return @implode("\n", $tr);
		
	}
	
	
	private function search_member(){
		
		// acls.db is API-only — delegated-ACL gpids for this uid via the acls backend.
		$gpids=acls_api_get("/proxy/acls/sqlite/aclsdynamic/gpids?type=2&pattern=".urlencode($_SESSION["uid"]));
		if(!is_array($gpids)){return;}
		foreach ($gpids as $gid){$gid=intval($gid); if($gid>0){$this->Groupsid[$gid]=$gid;}}
		
	}
	
	
	private function search_in_ad(){
		
		$groups=$this->ActiveDirectoryGroupsOfMember();
		if(count($groups)==0){return;}
		
		// acls.db is API-only — delegated-ACL gpids per AD group DN via the acls backend.
		while (list ($dn, $groupname) = each ($groups) ){
			$gpids=acls_api_get("/proxy/acls/sqlite/aclsdynamic/gpids?type=1&pattern=".urlencode($dn));
			if(!is_array($gpids)){continue;}
			foreach ($gpids as $gid){$gid=intval($gid); if($gid>0){$this->Groupsid[$gid]=$gid;}}
		}
	}
	
	private function search_in_ldap(){
		include_once(dirname(__FILE__)."/class.user.inc");
		$ct=new user($_SESSION["uid"]);
		$groups=$ct->Groups_list();
		if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>LDAP - Groups_list() = ".count($groups)."<br></span>";}
		
		if(count($groups)==0){return;}
		// acls.db is API-only — delegated-ACL gpids per LDAP group via the acls backend.
		while (list ($guid, $groupname) = each ($groups) ){

			if($GLOBALS["VERBOSE"]){echo "<span style='color:blue'>MySQL loop = $guid<br></span>";}
			$gpids=acls_api_get("/proxy/acls/sqlite/aclsdynamic/gpids?type=3&pattern=".urlencode($guid));
			if(!is_array($gpids)){continue;}
			foreach ($gpids as $gid){$gid=intval($gid); if($gid>0){$this->Groupsid[$gid]=$gid;}}
		}
	}
	
	
	private function ActiveDirectoryGroupsOfMember(){
		include_once(dirname(__FILE__)."/class.external.ad.inc");
		$ad=new external_ad_search();
		$array=$ad->find_DN($_SESSION["uid"]);
		$DN=$array[0]["dn"];
		return $ad->GroupsOfMember($DN);
		
	}
	
}