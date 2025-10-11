<?php
include_once(dirname(__FILE__)."/class.user.inc");

class dynamic_acls_auth{
	private $uid=null;
	private $groupsofuser=array();
	private $ADenabled=0;
	private $verbose=false;
	private $groupsofusers;

	
	public function __construct($userid=null){
		$this->uid=$userid;
		if(isset($_GET["VERBOSE"])){$this->verbose=true;}
		if(isset($_GET["verbose"])){$this->verbose=true;}

        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

        $sql="CREATE TABLE IF NOT EXISTS `webfilter_aclsdynamic_rights` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`gpid` INTEGER,
			`type` INTEGER,
			`pattern` TEXT  )";
        $q->QUERY_SQL($sql);
		
		$this->writelogs("$userid",__FUNCTION__,__LINE__);
		
		if($this->uid<>null){
			$this->writelogs("->LoadAllItems()",__FUNCTION__,__LINE__);
			$ldap=new clladp();
			$this->ADenabled=$ldap->IsKerbAuth();
			$this->LoadAllItems();
		}else{
			$this->writelogs("User is null",__FUNCTION__,__LINE__);
		}
		
		
	}
	
	public function SQUID_DYNAMIC_ACLS(){
		$this->writelogs("->LoadAllItems()",__FUNCTION__,__LINE__);
		return $this->LoadAllItems();
	}
	
	private function writelogs($text,$function,$line){
		if(!$this->verbose){return;}
		echo "$text ($function),$line<br>\n";
		writelogs("$text",__CLASS__."/".$function,__FILE__,$line);
	}
	
	private function LoadGroupOfUsers(){
		
		if(isset($_SESSION["dynamic_acls_auth"]["LoadGroupOfUsers"])){
			$this->writelogs("_SESSION: $this->uid = Groups -> ".count($_SESSION["dynamic_acls_auth"]["LoadGroupOfUsers"]),__FUNCTION__,__LINE__);
			$this->groupsofuser=$_SESSION["dynamic_acls_auth"]["LoadGroupOfUsers"];
			return;
		}
		
		$ldap=new clladp();
		$LOG_PREFIX=__CLASS__."/".__FUNCTION__;
		$ADenabled=$ldap->IsKerbAuth();
		if($this->ADenabled==0){return;}
		$ad=new external_ad_search();
		
		if(!class_exists("user")){include_once(dirname(__FILE__)."/class.user.inc");}
		$ct=new user($this->uid);
		$this->writelogs("$this->uid::GroupsOfMember($ct->dn)",__FUNCTION__,__LINE__);
		$this->groupsofuser=$ad->GroupsOfMember($ct->dn);
		$_SESSION["dynamic_acls_auth"]["LoadGroupOfUsers"]=$this->groupsofuser;
		
		if($GLOBALS["VERBOSE"]){
			echo "<H3>$ct->dn</H3>\n";
			$groupsofusers=$this->groupsofusers;
			while (list ($a, $b) = each ($groupsofusers) ){echo "<li>{$LOG_PREFIX} Member of group: groupsofusers[<strong>$a</strong>]</li>\n";}
			reset($groupsofusers);
		}
		
	}
	
	private function CheckUserINAD($DN){
		$LOG_PREFIX=__CLASS__."/".__FUNCTION__;
		if($this->ADenabled==0){
			if($GLOBALS["VERBOSE"]){echo "<span style='color:#d32d2d'>{$LOG_PREFIX}: FAILED:squid_load_dynamic_acls::AD not enabled</span><br>\n";}
			return false;}
		$this->LoadGroupOfUsers();
		if(!isset($this->groupsofuser[$DN])){
			$this->writelogs("->$DN not in array of ".count($this->groupsofuser)." elements",__FUNCTION__,__LINE__);
			return false;}
		
		$this->writelogs("->$DN {$this->groupsofuser[$DN]}:TRUE",__FUNCTION__,__LINE__);
		return true;
	}
		
	private function GetFromSession(){
		if(!isset($_SESSION["SQUID_DYNAMIC_ACLS"])){return false;}
		if(!is_array($_SESSION["SQUID_DYNAMIC_ACLS"])){return false;}
		if(!isset($_SESSION["dynamic_acls_auth"])){return false;}
		if(count($_SESSION["SQUID_DYNAMIC_ACLS"])>0){return true;}
		
		if(count($_SESSION["dynamic_acls_auth"]["LoadGroupOfUsers"])==0){return false;}
		return true;
	}
	
	
	private function webfilters_sqitems_sql(){
		if(isset($GLOBALS["webfilters_sqitems_sql"])){return $GLOBALS["webfilters_sqitems_sql"];}
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");

		$sql="SELECT webfilter_aclsdynamic_rights.pattern,webfilter_aclsdynamic_rights.type,webfilters_sqgroups.ID
		FROM webfilter_aclsdynamic_rights, webfilters_sqgroups
		WHERE webfilter_aclsdynamic_rights.gpid = webfilters_sqgroups.ID
		AND webfilters_sqgroups.GroupType = 'dynamic_acls'
		AND webfilters_sqgroups.enabled =1";
		
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){
			VERBOSE("dynamic_acls_auth: $q->mysql_error", __LINE__);
			$this->writelogs("$q->mysql_error",__FUNCTION__,__LINE__);
			echo $q->mysql_error_html();
			return;
		}
		$CountDerules=count($results);
		$this->writelogs("$sql",__FUNCTION__,__LINE__);
		$this->writelogs("$CountDerules rules...",__FUNCTION__,__LINE__);
		$GLOBALS["webfilters_sqitems_sql"]=$results;
		return $GLOBALS["webfilters_sqitems_sql"];
		
		
	}
	
	public function CountDeRules(){
		$results=$this->webfilters_sqitems_sql();
		return mysqli_num_rows($results);
		
	}
	
	
	private function LoadAllItems(){
		if($this->GetFromSession()){
			$this->writelogs("User: $this->uid GetFromSession() --> TRUE",__FUNCTION__,__LINE__);
			return $_SESSION["SQUID_DYNAMIC_ACLS"];
		}
		
		
		$this->writelogs("User: $this->uid",__FUNCTION__,__LINE__);
		if($this->uid==null){
			$this->writelogs("User: $this->uid is null ->return ",__FUNCTION__,__LINE__);
			return;}
		

		
		$results=$this->webfilters_sqitems_sql();
		$CountDerules=count($results);
		$this->writelogs("$CountDerules rules...",__FUNCTION__,__LINE__);
		if($CountDerules==0){return;}
		
		$this->LoadGroupOfUsers();
		$_SESSION["SQUID_DYNAMIC_ACLS"]=array();
		
		foreach ($results as $index=>$ligne){
			$this->writelogs("Pattern:{$ligne["pattern"]}",__FUNCTION__,__LINE__);
			
			if($this->uid==-100){
				$_SESSION["SQUID_DYNAMIC_ACLS"][$ligne["ID"]]=true;
				continue;
				
			}
			
			
			if($ligne["type"]==1){
				if(!$this->CheckUserINAD($ligne["pattern"])){
					$this->writelogs("->CheckUserINAD({$ligne["pattern"]}) -> FALSE",__FUNCTION__,__LINE__);
					continue;
				}
				$this->writelogs("->CheckUserINAD({$ligne["pattern"]}) -> TRUE",__FUNCTION__,__LINE__);
				$_SESSION["SQUID_DYNAMIC_ACLS"][$ligne["ID"]]=true;
				continue;
				
			}
			
			if($ligne["type"]==2){
				if(trim(strtolower($ligne["pattern"])) == trim(strtolower($this->uid))){
					$_SESSION["SQUID_DYNAMIC_ACLS"][$ligne["ID"]]=true;
					continue;
				}
				continue;
			}
			
			if($ligne["type"]==3){
				$ct=new user();
				$groups=$ct->GetGroups($this->uid,1);
				if(isset($groups[$ligne["pattern"]])){
					$_SESSION["SQUID_DYNAMIC_ACLS"][$ligne["ID"]]=true;
					continue;
				}
				
				continue;
				
			}
			
			
			
			if(preg_match("#^AD:[0-9]+:(.+)#", $ligne["pattern"],$re)){
				$DNGroup=base64_decode($re[1]);
				
				if(!$this->CheckUserINAD($DNGroup)){
					$this->writelogs("->CheckUserINAD($DNGroup) -> FALSE",__FUNCTION__,__LINE__);
					continue;
				}
				$this->writelogs("->CheckUserINAD($DNGroup) -> TRUE",__FUNCTION__,__LINE__);
				$_SESSION["SQUID_DYNAMIC_ACLS"][$ligne["ID"]]=true;
				continue;
			}
		
		}
	
	
	return $_SESSION["SQUID_DYNAMIC_ACLS"];
	}
	
	
}