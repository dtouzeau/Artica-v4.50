<?php

include_once(dirname(__FILE__)."/class.external.ad.inc");
include_once(dirname(__FILE__)."/class.acls.backend.inc");
class squid_acls_quotas_time{
	private $ruleid=0;
	
	
	function __construct($ruleid=null){
		$this->ruleid=$ruleid;
		if(!is_numeric($this->ruleid)){$this->ruleid=0;}
	}
	
	public function IsValid($ruleid=null){
		if($this->locate_binary()==null){
			$this->debugOutput("{warning} locate_binary -> false");
			return false;
		}
		if(!is_numeric($ruleid)){$ruleid=0;}
		if($ruleid==0){$ruleid=$this->ruleid;}
		if($ruleid==0){$this->Output("Rule $ruleid not acceptable");return false;}
		// acls.db is API-only — time-quota rule via the acls backend.
		$ligne=acls_api_get("/proxy/time-quota/acls/".intval($ruleid));
		if(!is_array($ligne)){$ligne=array();}
		$ligne["enabled"]=intval($ligne["enabled"] ?? 0);
		$ligne["QuotaType"]=(string)($ligne["quotaType"] ?? "");
		if($ligne["enabled"]==0){
			$this->Output("Rule $ruleid not enabled");
			return false;
		}
		
		if($ligne["QuotaType"]=="uid"){
            if(!isset($GLOBALS["SQUIDBEE"])){$GLOBALS["SQUIDBEE"]=new squidbee();}
            $squid=$GLOBALS["SQUIDBEE"];
			if(!$squid->is_auth()){
				$this->Output("Rule $ruleid Type: {$ligne["QuotaType"]} but auth is not enabled");
				return false;
			}
		}
		if($this->GetavailableRulesNumber($ruleid)==0){
			$this->Output("Rule $ruleid no quota rules available by GetavailableRulesNumber($ruleid)");
			return false;
		}
		return true;
		
	}
	
	private function debugOutput($text,$function=null,$line=null){
	
		if(!$GLOBALS["AS_ROOT"]){return;}
		if(!$GLOBALS["VERBOSE"]){return;}
		if(!is_numeric($line)){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$function=$trace[1]["function"];
					$line=$trace[1]["line"];
						
				}
			}
		}
	
		echo "DEBUG ACLS:$function::$text [$line]\n";
	}	
	
	private function GetavailableRulesNumber($ruleid){
		// acls.db is API-only — enabled quota sub-rules via the acls backend.
		$results = acls_api_get("/proxy/time-quota/acl-rules?ruleid=".intval($ruleid));
		if(!is_array($results)){$results=array();}
		foreach($results as $ri=>$rr){$results[$ri]["ID"]=intval($rr["id"] ?? 0);}
		if(count($results)==0){
			$this->Output("quota rules for ruleid $ruleid return no data");
			return 0;}
		$c=0;
		foreach ($results as $index=>$ligne){
			$num=$this->GetavailableGroupesNumber($ligne["ID"]);
			$this->Output("Rule {$ligne["ID"]} return $num Groups");
			if($num==0){continue;}
			$c++;
		}
		
		return $c;
		
	}
	
	private function GetavailableGroupesNumber($quotaid){
		// acls.db is API-only — enabled group links via the acls backend.
		$results = acls_api_get("/proxy/time-quota/acl-links?ruleid=".intval($quotaid));
		if(!is_array($results)){$results=array();}
		if(count($results)==0){return 0;}
		return count($results);
		
	}
	
	
	private function locate_binary(){
		if(is_file('/lib/squid3/ext_time_quota_acl')){return '/lib/squid3/ext_time_quota_acl';}
		if(is_file('/usr/lib/squid3/ext_time_quota_acl')){return '/usr/lib/squid3/ext_time_quota_acl';}
		if(is_file('/usr/libexec/ext_time_quota_acl')){return '/usr/libexec/ext_time_quota_acl';}
		if(is_file('/usr/lib64/squid3/ext_time_quota_acl')){return '/usr/lib64/squid3/ext_time_quota_acl';}
		if(is_file('/lib/squid3/ext_time_quota_acl')){return '/lib/squid3/ext_time_quota_acl';}
		if(is_file('/lib64/squid3/ext_time_quota_acl')){return '/lib64/squid3/ext_time_quota_acl';}
		if(is_file('/usr/lib/squid/ext_time_quota_acl')){return '/usr/lib/squid/ext_time_quota_acl';}
		if(is_file('/usr/lib/squid/ext_time_quota_acl')){return '/usr/lib/squid/ext_time_quota_acl';}
		if(is_file('/usr/lib64/squid/ext_time_quota_acl')){return '/usr/lib64/squid/ext_time_quota_acl';}
		if(is_file('/usr/lib64/squid/ext_time_quota_acl')){return '/usr/lib64/squid/ext_time_quota_acl';}
		if(is_file('/usr/local/lib/squid/ext_time_quota_acl')){return '/usr/local/lib/squid/ext_time_quota_acl';}
		if(is_file('/usr/local/lib64/squid/ext_time_quota_acl')){return '/usr/local/lib64/squid/ext_time_quota_acl';}
		if(is_file('/opt/artica/libexec/ext_time_quota_acl')){return '/opt/artica/libexec/ext_time_quota_acl';}
	}
	
	public function build(){
		$binary=$this->locate_binary();
		if(!is_file($binary)){
			echo "Starting......: ".date("H:i:s")." [ACL]: Quotas Time: ext_time_quota_acl, no such binary\n";
			return;}
		// acls.db is API-only — enabled time-quota rules via the acls backend.
		$results = acls_api_get("/proxy/time-quota/acls");
		if(!is_array($results)){$results=array();}
		foreach($results as $ri=>$rr){$results[$ri]["ID"]=intval($rr["id"] ?? 0);$results[$ri]["QuotaType"]=(string)($rr["quotaType"] ?? "");$results[$ri]["TTL"]=intval($rr["ttl"] ?? 60);}
		$line=array();
		if(count($results)==0){
			echo "Starting......: ".date("H:i:s")." [ACL]: Quotas Time: ext_time_quota_acl, no enabled main rules\n";
			return;}
		foreach ($results as $index=>$ligne){
			$ID=$ligne["ID"];
			if(!$this->IsValid($ID)){echo "Starting......: ".date("H:i:s")." [ACL]: Quotas Time: Rule [$ID] invalid\n";continue;}
			$QuotaType["src"]="%SRC";
			$QuotaType["uid"]="%LOGIN";
			$QuotaType["MAC"]="%SRCEUI48";
			$QuotaType["ADMBR"]="%LOGIN";
			$QuotaType["EXT_USER"]="%EXT_USER";
			$QuotaType["EXT_TAG"]="%EXT_TAG";
				
			$this->Output("buildconf($ID,{$ligne["QuotaType"]})");
			$this->buildconf($ID,$ligne["QuotaType"]);
			$QuOtaText=$QuotaType[$ligne["QuotaType"]];
			$line[]="external_acl_type time_quotaex$ID ttl={$ligne["TTL"]} children-max=1 $QuOtaText $binary -b /etc/squid3/time_quota$ID.db /etc/squid3/time_quota$ID.conf";
			$line[]="acl time_quota$ID external time_quotaex$ID";
			
			
			
		}
		if(count($line)==0){return;}
		return @implode("\n", $line);
		
	}
	
	private function buildconf($ruleid,$QuotaType){
		$path="/etc/squid3/time_quota$ruleid.conf";
		$this->Output("Path: `$path`");
		// acls.db is API-only — enabled quota sub-rules via the acls backend.
		$results = acls_api_get("/proxy/time-quota/acl-rules?ruleid=".intval($ruleid));
		if(!is_array($results)){$results=array();}
		foreach($results as $ri=>$rr){$results[$ri]["ID"]=intval($rr["id"] ?? 0);}
		if(count($results)==0){@file_put_contents($path, "\n");return;}
		$c=0;
		$items=array();
		foreach ($results as $index=>$ligne){
			$period="{$ligne["budget"]} / {$ligne["period"]}";
			$items[]=$this->compileGroups($ligne["ID"],$period,$path,$QuotaType);
			
			
		}
		@file_put_contents($path, @implode("\n", $items)."\n");
	}
	
	private function compileGroups($ruleid,$period,$path,$QuotaType){
		@file_put_contents($path, "\n");
		@chmod($path,0755);
		@chown($path,"squid");
		@chgrp($path, "squid");
		// acls.db is API-only — enabled group links via the acls backend.
		$linkIds = acls_api_get("/proxy/time-quota/acl-links?ruleid=".intval($ruleid));
		$results=array();
		if(is_array($linkIds)){foreach($linkIds as $g){$results[]=array("groupid"=>intval($g));}}
		if(count($results)==0){return;}
		$items=array();
		foreach ($results as $index=>$ligne){
			$this->Output("buildItems: `{$ligne["groupid"]}` Period: $period Type:$QuotaType");
			$items[]=$this->buildItems($ligne["groupid"],$period,$QuotaType);
			
		}
		
		return @implode("\n",$items);
	}
	
	private function GetGroupType($gpid){
		// acls.db is API-only — group type via the acls backend.
		$grp=acls_api_get("/proxy/acls/sqlite/group/".intval($gpid));
		return is_array($grp)?(string)($grp["groupType"] ?? ""):"";
	}
	private function GetGroupGroupName($gpid){
		// acls.db is API-only — group name via the acls backend.
		$grp=acls_api_get("/proxy/acls/sqlite/group/".intval($gpid));
		return is_array($grp)?(string)($grp["groupName"] ?? ""):"";
	}	
	
	
	
	
	private function buildItems($gpid,$period,$QuotaType){
		$c=0;
		$f=array();
		$already=array();
		$MASTERDOMAINAL=array();
		
		$ipClass=new IP();
		$GroupType=$this->GetGroupType($gpid);

		
		if($QuotaType=="ADMBR"){
			if($GroupType=="proxy_auth_ads"){
				$GroupName=$this->GetGroupGroupName($gpid);
				
				$ad=new external_ad_search();
				$dn=$ad->GetGroupDN($GroupName);
				$this->Output("buildItems: $GroupName -> ($GroupType) $dn ADMBR/proxy_auth_ads");
				$HashUsersFromGroupDN=$ad->HashUsersFromGroupDN($dn);
				if(count($HashUsersFromGroupDN)==0){return;}
				while (list ($none,$uid) = each ($HashUsersFromGroupDN) ){$this->Output("buildItems: $uid -> $period");$f[]="$uid $period";}
				if(count($f)>0){return @implode("\n",$f);}
				return;
			}
		}
		
		// acls.db is API-only — enabled group items via the acls backend.
		$itemsApi = acls_api_get("/proxy/acls/sqlite/items?gpid=".intval($gpid));
		$results=array();
		if(is_array($itemsApi)){foreach($itemsApi as $it){if(is_array($it) && intval($it["enabled"] ?? 0)==1){$results[]=$it;}}}
		$this->Output("buildItems: items gpid=$gpid -> ($GroupType) ".count($results)." items");
				
		
		foreach ($results as $index=>$ligne){
			$ligne["pattern"]=trim($ligne["pattern"]);
			if($ligne["pattern"]==null){continue;}
			$this->Output("buildItems: `{$ligne["pattern"]}` Group type $GroupType, Rule type:$QuotaType ");
			
			if($QuotaType=="src"){if(!$ipClass->isValid($ligne["pattern"])){continue;}}
			$f[]="{$ligne["pattern"]} $period";
		}	
		
		if(count($f)>0){return @implode("\n",$f);}
	}
	
	private function Output($text){
		if(!$GLOBALS["VERBOSE"]){return;}
		echo "Starting......: ".date("H:i:s")." [ACL]: Quotas Time: $text\n";
	}
	
	
}