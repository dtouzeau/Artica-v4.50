<?php
class TranslateRights{
	private $priv;
	private $uid;
	public $FINAL_PRIVS;
	private $CurrentPageName;
	function __construct($priv=null,$uid=null){
		if(function_exists("CurrentPageName")){$this->CurrentPageName=CurrentPageName();}
		$this->priv=$priv;
		$this->uid=$uid;
	}
	public static function GetPrivsArray(){
			$f['AsVirtualBoxManager']=true;
			$f['AsSquidAdministrator']=true;
			$f['AsJoomlaWebMaster']=true;
			$f['AseMailCampaignsAdmin']=true;
			$f['AsInventoryAdmin']=true;
			$f['AsOrgAdmin']=true;
			$f['AsMessagingOrg']=true;
			$f['AsDansGuardianGroupRule']=true;
			$f['AsOrgPostfixAdministrator']=true;
			$f['AllowManageOwnComputers']=true;
			$f['AsOrgStorageAdministrator']=true;
			$f['AsMailManAdministrator']=true;
			$f['AllowXapianDownload']=true;
			$f['AllowChangeMailBoxRules']=true;
			$f['AllowDansGuardianBanned']=true;
			$f['AllowOpenVPN']=true;
			$f['AllowEmailing']=true;
			$f['AsQuarantineAdministrator']=true;
			$f['AsDnsAdministrator']=true;
			$f['AsSambaAdministrator']=true;
			$f['AllowEditOuSecurity']=true;
			$f['AllowChangeAntiSpamSettings']=true;
			$f['AllowViewStatistics']=true;
			$f['AllowChangeUserKas']=true;
			$f['AllowSenderCanonical']=true;
			$f['AsMailBoxAdministrator']=true;
			$f['AllowFetchMails']=true;
			$f['AllowChangeDomains']=true;
			$f['AllowEditAliases']=true;
			$f['AllowAddGroup']=true;
			$f['AsSystemAdministrator']=true;
			$f['AllowChangeKav']=true;
			$f['AllowChangeUserPassword']=true;
			$f['AllowChangeKas']=true;
			$f['AllowAddUsers']=true;
			$f['AllowEditAsWbl']=true;
			$f['AsArticaAdministrator']=true;
			$f['AsWebMaster']=true;
			$f['RestrictNabToGroups']=true;
			$f['AsDansGuardianAdministrator']=true;
            $f['AsDatabaseAdministrator']=true;
			$f['AsSquidPersonalCategories']=true;
            $f['AsDockerAdmin']=true;
            $f['AsDockerReverse']=true;
            $f['AsCategories']=true;

			
			$f['AsWebFilterRepository']=true;
			$f['AsWebStatisticsAdministrator']=true;
			$f['AllowUserMaillog']=true;
			$f['AsHotSpotManager']=true;
			$f['AsFirewallManager']=true;
			$f['AsVPNManager']=true;
			
			$f['AsOwnMailBoxBackup']=true;
			$f['AsOrgDNSAdmin']=true;
			$f['ASDCHPAdmin']=true;
			$f['AsCertifsManager']=true;
			$f['AsAnAdministratorGeneric']=true;
			$f['AsPostfixAdministrator']=true;
			$f['OverWriteRestrictedDomains']=true;
			$f['AsProxyMonitor']=true;
			$f["AsSystemWebMaster"]=true;
			$f["AsArticaMetaAdmin"]=true;
			return $f;		
	}
	
	private function Allrights(){
		$f=$this->GetPrivsArray();
		foreach ($f as $num=>$ligne){
			$this->FINAL_PRIVS[$num]=true;
		}
	}
	
	function IsGlobalAdmin($priv=null,$log=false){
		if(!isset($GLOBALS["posix_getuid"])){$GLOBALS["posix_getuid"]=1000;if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}}
		if($GLOBALS["posix_getuid"]==0){$this->uid=-100;}
		if($this->uid==-100){
			$this->FINAL_PRIVS["AsArticaAdministrator"]=true;
			$this->FINAL_PRIVS["AsQuarantineAdministrator"]=true;
			$this->FINAL_PRIVS["AsVirtualBoxManager"]=true;
            $this->FINAL_PRIVS["AsDatabaseAdministrator"]=true;
            $this->FINAL_PRIVS["AsDockerAdmin"]=true;
            $this->FINAL_PRIVS["AsDockerReverse"]=true;
			$this->Allrights();
			if($log){writelogs("It is a global administrator",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			return true;
		}
		if($priv==null){$priv=$this->priv;}
		if($priv["AsSystemAdministrator"]=="yes"){return true;}
		if($priv["AsPostfixAdministrator"]=="yes"){return true;}
		if($priv["AsArticaAdministrator"]=="yes"){return true;}
		if($priv["AsSquidAdministrator"]=="yes"){return true;}
		if($priv["AsSambaAdministrator"]=="yes"){return true;}
		if($priv["AsVirtualBoxManager"]=="yes"){return true;}
        if($priv["AsDatabaseAdministrator"]=="yes"){return true;}
        if($priv["AsDockerAdmin"]=="yes"){return true;}
        if($priv["AsDockerReverse"]=="yes"){return true;}

        return false;
	}	
	
	function ParseRights(){
		if(isset($_SESSION["UID_KEY"])){
			$privsCache=$GLOBALS["CLASS_SOCKETS"]->GET_INFO($_SESSION["UID_KEY"]);

			if(strlen($privsCache)>10){
				VERBOSE("{$_SESSION["UID_KEY"]} HIT", __LINE__);
				$array=unserialize(base64_decode($privsCache));
				$this->FINAL_PRIVS=$array;
				$this->priv=$array;
				return;
			}
		}


		$log=false;
		$priv=$this->priv;
		if(!isset($GLOBALS["posix_getuid"])){$GLOBALS["posix_getuid"]=1000;if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}}
		if(isset($_SESSION["uid"])){if(function_exists("VERBOSE")){VERBOSE("[{$_SESSION["uid"]}]",__LINE__);}}

		if($GLOBALS["posix_getuid"]==0){
		    $this->FINAL_PRIVS["AsCertifsManager"]=true;
            $this->FINAL_PRIVS["AsMailBoxAdministrator"]=true;
            $this->FINAL_PRIVS["AsArticaAdministrator"]=true;
            $this->FINAL_PRIVS["AsSystemAdministrator"]=true;
            $this->FINAL_PRIVS["AsSquidPersonalCategories"]=true;
            $this->FINAL_PRIVS["AsFirewallManager"]=true;
            $this->FINAL_PRIVS["AsProxyMonitor"]=true;
            $this->FINAL_PRIVS["AsVPNManager"]=true;
            $this->FINAL_PRIVS["AsDockerAdmin"]=true;
            $this->FINAL_PRIVS["AsDockerReverse"]=true;
            $this->Allrights();
            $this->priv=$this->FINAL_PRIVS;
            return true;
        }

		if(!isset($_SESSION["uid"])){
            if(function_exists("VERBOSE")){VERBOSE("Unknown [{\$_SESSION[\"uid\"]}]",__LINE__);}
            $this->priv=array();
            return true;
        }

		if($_SESSION["uid"]==-100){
            $this->FINAL_PRIVS["AsDatabaseAdministrator"]=true;
			$this->FINAL_PRIVS["AsArticaAdministrator"]=true;
			$this->FINAL_PRIVS["AllowEditOuSecurity"]=true;
			$this->FINAL_PRIVS["AsSquidAdministrator"]=true;
			$this->FINAL_PRIVS["AsSystemAdministrator"]=true;
			$this->FINAL_PRIVS["AsMessagingOrg"]=true;
			$this->FINAL_PRIVS["AsHotSpotManager"]=true;
			$this->FINAL_PRIVS["AsFirewallManager"]=true;
			$this->FINAL_PRIVS["AsProxyMonitor"]=true;
			$this->FINAL_PRIVS["AsSquidPersonalCategories"]=true;
			$this->FINAL_PRIVS["AsVPNManager"]=true;
			$this->FINAL_PRIVS["AsCertifsManager"]=true;
            $this->FINAL_PRIVS["AsDockerAdmin"]=true;
            $this->FINAL_PRIVS["AsDockerReverse"]=true;
			$this->Allrights();
			$this->priv=$this->FINAL_PRIVS;
			return true;
		}
		
		if(!is_array($priv)){
                if ($GLOBALS["posix_getuid"] <> 0) {
                    if ($GLOBALS["VERBOSE"]) {echo "[{$_SESSION["uid"]}]::No privileges set\n";}
                    writelogs("[{$_SESSION["uid"]}]::No privileges set", __CLASS__ . '/' . __FUNCTION__, __FILE__, __LINE__);
                }
                return true;
		}

		$this->FINAL_PRIVS["AsArticaAdministrator"]=false;
		$this->FINAL_PRIVS["AsSystemAdministrator"]=false;
		if(isset($GLOBALS["DEBUG_PRIVS"])){if($GLOBALS["DEBUG_PRIVS"]){$log=true;}}
		
		if($GLOBALS["VERBOSE"]){echo "[{$_SESSION["uid"]}]::Receive array of ". count($priv)." privileges\n";}
		
		if($log){writelogs("logon: Receive array of ". count($priv)." privileges",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		if($log){
		    foreach ($priv as $num=>$ligne){

                    writelogs("logon: $num = $ligne",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
            }

		
		if($GLOBALS["VERBOSE"]){echo "[{$_SESSION["uid"]}]::this->GetPrivsArray()\n";}
		$GetPrivsArray=$this->GetPrivsArray();
		foreach ($GetPrivsArray as $num=>$ligne){
			if(!isset($priv[$num])){$priv[$num]="no";}
			
		}
		
		reset($GetPrivsArray);
        foreach ($GetPrivsArray as $num=>$ligne){
			$this->FINAL_PRIVS[$num]=false;
			if($priv[$num]=='yes'){$this->FINAL_PRIVS[$num]=true;}
		}
		if(isset($priv["PasswdPolicy"])){
			if($priv["PasswdPolicy"]<>null){$this->FINAL_PRIVS["PasswdPolicy"]=unserialize(base64_decode($priv["PasswdPolicy"]));}
		}
		
		if($log){
			if(is_array($priv)){
                foreach ($priv as $key=>$val){
					if($val=="yes"){
						if($GLOBALS["VERBOSE"]){echo "[{$_SESSION["uid"]}]::$key = $val\n";}
						writelogs("$key=$val",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					}
				}}
		}
		
		
		if($this->FINAL_PRIVS["AllowEditOuSecurity"]==true){
			if($log){writelogs("[FINAL_PRIVS]:{$_SESSION["uid"]}: AllowEditOuSecurity: TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$this->FINAL_PRIVS["AllowManageOwnComputers"]=true;
			$this->FINAL_PRIVS["AsDansGuardianGroupRule"]=true;
			$this->FINAL_PRIVS["AseMailCampaignsAdmin"]=true;
			$this->FINAL_PRIVS["AllowChangeAntiSpamSettings"]=true;
			$this->FINAL_PRIVS["AllowChangeUserPassword"]=true;
			$this->FINAL_PRIVS["AllowFetchMails"]=true;
			$this->FINAL_PRIVS["AllowChangeUserKas"]=true;
			$this->FINAL_PRIVS["AllowChangeMailBoxRules"]=true;
			$this->FINAL_PRIVS["AllowSenderCanonical"]=true;
			$this->FINAL_PRIVS["AllowOpenVPN"]=true;
			$this->FINAL_PRIVS["AllowDansGuardianBanned"]=true;
			$this->FINAL_PRIVS["AllowXapianDownload"]=true;
			$this->FINAL_PRIVS["AllowEditAliases"]=true;
				
		}
		
		
		
		
		if($this->IsGlobalAdmin($priv)==true){
            $this->FINAL_PRIVS["AsDockerAdmin"]=true;
            $this->FINAL_PRIVS["AsDockerReverse"]=true;
			$this->FINAL_PRIVS["AllowChangeDomains"]=true;
			$this->FINAL_PRIVS["AllowAddGroup"]=true;
			$this->FINAL_PRIVS["AllowChangeOrg"]=true;
			$this->FINAL_PRIVS["AsPostfixAdministrator"]=true;
			$this->FINAL_PRIVS["AllowChangeKav"]=true;
			$this->FINAL_PRIVS["AllowChangeKas"]=true;
			$this->FINAL_PRIVS["AllowEditOuSecurity"]=true;
			$this->FINAL_PRIVS["AllowAddUsers"]=true;
			$this->FINAL_PRIVS["AsSquidAdministrator"]=true;
			$this->FINAL_PRIVS["AsSambaAdministrator"]=true;
			$this->FINAL_PRIVS["AsDnsAdministrator"]=true;
			$this->FINAL_PRIVS["AsQuarantineAdministrator"]=true;
			$this->FINAL_PRIVS["AllowDansGuardianBanned"]=true;
			$this->FINAL_PRIVS["AllowChangeAntiSpamSettings"]=true;
			$this->FINAL_PRIVS["AllowViewStatistics"]=true;
			$this->FINAL_PRIVS["AllowSenderCanonical"]=true;
			$this->FINAL_PRIVS["AsOrgStorageAdministrator"]=true;
			$this->FINAL_PRIVS["AllowManageOwnComputers"]=true;
			$this->FINAL_PRIVS["AsDansGuardianGroupRule"]=true;
			$this->FINAL_PRIVS["AsMessagingOrg"]=true;
			$this->FINAL_PRIVS["AsHotSpotManager"]=true;
				
		}
		
		if($this->FINAL_PRIVS["AsArticaAdministrator"]==true){
			$this->Allrights();
		}
			
		if($this->FINAL_PRIVS["AsSquidAdministrator"]){
			if($GLOBALS["VERBOSE"]){echo "[{$_SESSION["uid"]}]::AsSquidAdministrator: TRUE\n";}
			if($log){writelogs("[FINAL_PRIVS]:{$_SESSION["uid"]}: AsSquidAdministrator: TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$this->FINAL_PRIVS["AsAnAdministratorGeneric"]=true;
			$this->FINAL_PRIVS["AsWebFilterRepository"]=true;
			$this->FINAL_PRIVS["AsDansGuardianGroupRule"]=true;
			$this->FINAL_PRIVS["AsDansGuardianAdministrator"]=true;
			$this->FINAL_PRIVS["AsWebStatisticsAdministrator"]=true;
			$this->FINAL_PRIVS["AsHotSpotManager"]=true;
			$this->FINAL_PRIVS["AsProxyMonitor"]=true;
			$this->FINAL_PRIVS["AsSquidPersonalCategories"]=true;
			
		}
			
		if($this->FINAL_PRIVS["AsDansGuardianAdministrator"]){
			$this->FINAL_PRIVS["AsDansGuardianGroupRule"]=true;
			$this->FINAL_PRIVS["AsWebStatisticsAdministrator"]=true;
			$this->FINAL_PRIVS["AsHotSpotManager"]=true;
			$this->FINAL_PRIVS["AsSquidPersonalCategories"]=true;
		}
			
		if($this->FINAL_PRIVS["AsPostfixAdministrator"]){
			$this->FINAL_PRIVS["AllowUserMaillog"]=true;
			$this->FINAL_PRIVS["AsAnAdministratorGeneric"]=true;
			$this->FINAL_PRIVS["OverWriteRestrictedDomains"]=true;
		}
		
		if($this->FINAL_PRIVS["AsArticaMetaAdmin"]){
			$this->FINAL_PRIVS["AsDansGuardianAdministrator"]=true;
			$this->FINAL_PRIVS["AsSquidPersonalCategories"]=true;
		
		}
		
			
		if($this->FINAL_PRIVS["AsSystemAdministrator"]){
			$this->FINAL_PRIVS["AllowUserMaillog"]=true;
			$this->FINAL_PRIVS["AsAnAdministratorGeneric"]=true;
			$this->FINAL_PRIVS["AsSambaAdministrator"]=true;
			$this->FINAL_PRIVS["AsDnsAdministrator"]=true;
			$this->FINAL_PRIVS["AsVirtualBoxManager"]=true;
			$this->FINAL_PRIVS["OverWriteRestrictedDomains"]=true;
			$this->FINAL_PRIVS["AsWebMaster"]=true;
			$this->FINAL_PRIVS["AllowEditAliases"]=true;
			$this->FINAL_PRIVS["AsSystemWebMaster"]=true;
            $this->FINAL_PRIVS["AsDatabaseAdministrator"]=true;
		}

		if($this->FINAL_PRIVS["AsMessagingOrg"]){
			$this->FINAL_PRIVS["AllowSenderCanonical"]=true;
			$this->FINAL_PRIVS["AllowAddUsers"]=true;
			$this->FINAL_PRIVS["AllowAddGroup"]=true;
			$this->FINAL_PRIVS["AllowChangeDomains"]=true;
			$this->FINAL_PRIVS["AllowEditAliases"]=true;
		}
		
		if($this->FINAL_PRIVS["AllowAddUsers"]){
			$this->FINAL_PRIVS["AllowChangeUserPassword"]=true;
		}
		
		if($this->FINAL_PRIVS["AllowChangeDomains"]==true){
			$this->FINAL_PRIVS["AllowSenderCanonical"]=true;
		}
		
		if($this->FINAL_PRIVS["AllowEditOuSecurity"]==true){
			$this->FINAL_PRIVS["AsOrgAdmin"]=true;
			$this->FINAL_PRIVS["AllowAddGroup"]=true;
			$this->FINAL_PRIVS["AsJoomlaWebMaster"]=true;
			$this->FINAL_PRIVS["AllowChangeAntiSpamSettings"]=true;
			$this->FINAL_PRIVS["AllowChangeUserPassword"]=true;
			$this->FINAL_PRIVS["AllowFetchMails"]=true;
			$this->FINAL_PRIVS["AllowChangeUserKas"]=true;
			$this->FINAL_PRIVS["AllowChangeMailBoxRules"]=true;
			$this->FINAL_PRIVS["AllowSenderCanonical"]=true;
			$this->FINAL_PRIVS["AllowOpenVPN"]=true;
			$this->FINAL_PRIVS["AllowDansGuardianBanned"]=true;
			$this->FINAL_PRIVS["AllowXapianDownload"]=true;
			$this->FINAL_PRIVS["AllowManageOwnComputers"]=true;
			$this->FINAL_PRIVS["AllowEditAliases"]=true;
		}
		if($this->FINAL_PRIVS["AsDnsAdministrator"]==true){
			$this->FINAL_PRIVS["AsOrgAdmin"]=true;
			$this->FINAL_PRIVS["AllowAddGroup"]=true;
			$this->FINAL_PRIVS["AsOrgDNSAdmin"]=true;
			$this->FINAL_PRIVS["AllowChangeDomains"]=true;
		
		}
		
		if($this->FINAL_PRIVS["AsOrgDNSAdmin"]){
			$this->FINAL_PRIVS["AllowEditAliases"]=true;
		}

		if($this->FINAL_PRIVS["AsOrgAdmin"]==true){
			$this->FINAL_PRIVS["AllowSenderCanonical"]=true;
			$this->FINAL_PRIVS["AllowAddUsers"]=true;
			$this->FINAL_PRIVS["AllowAddGroup"]=true;
			$this->FINAL_PRIVS["AsJoomlaWebMaster"]=true;
			$this->FINAL_PRIVS["AllowChangeAntiSpamSettings"]=true;
			$this->FINAL_PRIVS["AllowChangeUserPassword"]=true;
			$this->FINAL_PRIVS["AllowFetchMails"]=true;
			$this->FINAL_PRIVS["AllowChangeUserKas"]=true;
			$this->FINAL_PRIVS["AllowChangeMailBoxRules"]=true;
			$this->FINAL_PRIVS["AllowOpenVPN"]=true;
			$this->FINAL_PRIVS["AllowDansGuardianBanned"]=true;
			$this->FINAL_PRIVS["AllowXapianDownload"]=true;
			$this->FINAL_PRIVS["AllowManageOwnComputers"]=true;
			$this->FINAL_PRIVS["AllowEditAliases"]=true;
			$this->FINAL_PRIVS["AsOrgDNSAdmin"]=true;
		}
		
		if($this->FINAL_PRIVS["AsMessagingOrg"]){
			$this->FINAL_PRIVS["AllowChangeDomains"]=true;
		}
		
		if($this->FINAL_PRIVS["AsQuarantineAdministrator"]==true){$this->FINAL_PRIVS["AsMessagingOrg"]=true;$this->FINAL_PRIVS["AllowUserMaillog"]=true;}
		
		if($this->FINAL_PRIVS["AsInventoryAdmin"]){
			$this->FINAL_PRIVS["AllowManageOwnComputers"]=true;
		}
		
		if($this->FINAL_PRIVS["AsWebStatisticsAdministrator"]){
			$this->FINAL_PRIVS["AsAnAdministratorGeneric"]=true;
		}

		
		$this->priv=$this->FINAL_PRIVS;
        return true;
	}
}