<?php

class ufdbgquota{
	
	private $IP=null;
	private $userid=null;
	private $URI=null;
	private $sitename=null;
	private $ByCategory=false;
	private $category=null;
	private $CACHEKEY=null;
	public $returned=null;
	function __construct($CACHEKEY=null){
		$this->quotaconfig();
		$this->CACHEKEY=$CACHEKEY;
		if(!isset($GLOBALS["ARTICA_QUOTAS_RULES"])){$GLOBALS["ARTICA_QUOTAS_RULES"]=array();}
		
		if(count($GLOBALS["ARTICA_QUOTAS_RULES"])>0){
			if(!class_exists("mysql_catz")){include_once(dirname(__FILE__)."/class.mysql.catz.inc"); }
			
		}
	}
	
	
	function parse_rules($IP,$userid,$URI,$sitename){
		$this->IP=$IP;
		$this->userid=$userid;
		$this->URI=$URI;
		$this->sitename=$sitename;
		
		$MAINKEY=md5("$IP,$userid,$sitename");
		
		if($GLOBALS["DebugQuota"]){$this->events("$IP,$userid,$sitename -> $MAINKEY");}
		
		
		if(isset($GLOBALS["QUOTAS_MAIN_KEY"][$MAINKEY])){
			$currentSeconds=$this->tool_time_sec($GLOBALS["QUOTAS_MAIN_KEY"][$MAINKEY]["TIME"]);
			
			if($GLOBALS["DebugQuota"]){$this->events("$MAINKEY = {$currentSeconds}s/60s");}
			
			if($currentSeconds<60){
				if($GLOBALS["DebugQuota"]){$this->events("Return from Memory...");}
				if(!$GLOBALS["QUOTAS_MAIN_KEY"][$MAINKEY]["RESULTS"]){return false;}
				$this->returned=$GLOBALS["QUOTAS_MAIN_KEY"][$MAINKEY]["URI"];
				return true;
			}
			
		}
		
		if(count($GLOBALS["QUOTAS_MAIN_KEY"])>10000){$GLOBALS["QUOTAS_MAIN_KEY"]=array();}
		
		
		$ruleidZ1=array();
		if($userid<>null){
			$ruleidZ=$this->IfMatchUID($userid);
			if($GLOBALS["DebugQuota"]){$this->events( "IfMatchUID return ".count($ruleidZ). " rules [".__LINE__."]");}
		}
		
		if(count($ruleidZ)==0){
			$ruleidZ=$this->IfMatchIP($IP);
			if($GLOBALS["DebugQuota"]){$this->events( "IfMatchIP return ".count($ruleidZ). " rules [".__LINE__."]");}
		}
		
		if(count($ruleidZ)==0){
			if($GLOBALS["DebugQuota"]){$this->events( "No match IP $IP, Check AllSystems...[".__LINE__."]");}
			$ruleidZ=$this->ifMatchAllSystems();
		}		
		
		
		if(count($ruleidZ)==0){
			if($GLOBALS["DebugQuota"]){$this->events( "No rule match for all...[".__LINE__."]");}
			$GLOBALS["QUOTAS_MAIN_KEY"][$MAINKEY]["RESULTS"]=false;
			$GLOBALS["QUOTAS_MAIN_KEY"][$MAINKEY]["TIME"]=time();
			return false;
		}
		
		
		$this->category=$this->GetCategory($sitename);
		if($this->category<>null){
			$ruleidZ1=$this->IfMatchCategories($this->category,$ruleidZ);
		}
		
		if($GLOBALS["DebugQuota"]){
			if(count($ruleidZ1)==0){$this->events( "No rule match category  $this->category ...[".__LINE__."]");}
		}
		
		if(count($ruleidZ1)>0){
			$this->ByCategory=true;
			$ruleidZ=$ruleidZ1;
			if($GLOBALS["DebugQuota"]){$this->events( count($ruleidZ1)." Rules match category $this->category...[".__LINE__."]");}
		}
		
		if($this->ifMatchesRules($ruleidZ)){
			$GLOBALS["QUOTAS_MAIN_KEY"][$MAINKEY]["RESULTS"]=true;
			$GLOBALS["QUOTAS_MAIN_KEY"][$MAINKEY]["TIME"]=time();
			$GLOBALS["QUOTAS_MAIN_KEY"][$MAINKEY]["URI"]=$this->returned;
			return true;}
		$GLOBALS["QUOTAS_MAIN_KEY"][$MAINKEY]["RESULTS"]=false;
		$GLOBALS["QUOTAS_MAIN_KEY"][$MAINKEY]["TIME"]=time();
		return false;
		
	}
	
	
	private function CheckbyWeek($quotasize){
		

		if($this->userid<>null){
			if($this->ByCategory){
				$SIZE=intval($GLOBALS["CurrentSizesUsers"]["categories"][$this->category]["WEEK"][$this->userid]);
			}else{
				$SIZE=intval($GLOBALS["CurrentSizesUsers"]["UID"][$this->userid]["WEEK"]);
			}
		}
		
		
		if($GLOBALS["DebugQuota"]){events( "CheckbyWeek: uid:[$this->userid] $SIZE/WEEK [".__LINE__."]");}
		
		if($SIZE>0){
			if($SIZE>$quotasize){
				if($GLOBALS["DebugQuota"]){events( "CheckbyWeek uid:[$this->userid] $SIZE match $quotasize [".__LINE__."]");}
				return true;
			}
			return false;
		}

		if($this->ByCategory){
			$SIZE=intval($GLOBALS["CurrentSizesUsers"]["categories"][$this->category]["WEEK"][$this->IP]);
		}else{
			$SIZE=intval($GLOBALS["CurrentSizesUsers"]["IPADDR"][$this->IP]["WEEK"]);
		}
		
		if($GLOBALS["DebugQuota"]){events( "CheckbyWeek: uid:[$this->IP] $SIZE/day [".__LINE__."]");}

		if($SIZE>0){
			if($SIZE>$quotasize){
				if($GLOBALS["DebugQuota"]){events( "CheckbyWeek uid:[$this->IP] $SIZE match $quotasize [".__LINE__."]");}
				
				return true;
			}
			
		}
			
		
		return false;
	}
	
	
	private function CheckByDay($quotasize){

		if($this->userid<>null){
			if($this->ByCategory){
				$SIZE=intval($GLOBALS["CurrentSizesUsers"]["categories"][$this->category]["DAY"][$this->userid]);
			}else{
				$SIZE=intval($GLOBALS["CurrentSizesUsers"]["UID"][$this->userid]["DAY"]);
			}
		}
		
		
		if($GLOBALS["DebugQuota"]){events( "CheckByDay: uid:[$this->userid] $SIZE/day [".__LINE__."]");}
		
		if($SIZE>0){
			if($SIZE>$quotasize){
				if($GLOBALS["DebugQuota"]){events( "CheckByDay uid:[$this->userid] $SIZE match $quotasize [".__LINE__."]");}
				return true;
			}
			return false;
		}

		if($this->ByCategory){
			$SIZE=intval($GLOBALS["CurrentSizesUsers"]["categories"][$this->category]["DAY"][$this->IP]);
		}else{
			$SIZE=intval($GLOBALS["CurrentSizesUsers"]["IPADDR"][$this->IP]["DAY"]);
		}
		
		if($GLOBALS["DebugQuota"]){events( "CheckByDay: uid:[$this->IP] $SIZE/day [".__LINE__."]");}

		if($SIZE>0){
			if($SIZE>$quotasize){
				if($GLOBALS["DebugQuota"]){events( "CheckByDay uid:[$this->IP] $SIZE match $quotasize [".__LINE__."]");}
				return true;
			}
			
		}
			
		
		return false;
		
	}
	
	private function CheckByHourSpeed($quotasize){
		$SIZE=0;
		
		if($GLOBALS["DebugQuota"]){$this->events( "CheckByHourSpeed: UID:\"$this->userid\" for quota:$quotasize [".__LINE__."]");}
		if($this->userid<>null){
			if($this->ByCategory){
				$SIZE=intval($GLOBALS["CurrentSizesSpeed"]["categories"][$this->category]["HOUR"][$this->userid]);
				if($GLOBALS["DebugQuota"]){$this->events( "CheckByHour $this->userid $this->category SIZE ($SIZE) [".__LINE__."]");}
			}else{
				$SIZE=intval($GLOBALS["CurrentSizesSpeed"]["UID"][$this->userid]["HOUR"]);
			}
		}
		
		if($GLOBALS["DebugQuota"]){$this->events( "CheckByHourSpeed: uid:[$this->userid] $SIZE/$quotasize [".__LINE__."]");}
		
		if($SIZE>0){
			$SIZE_kb=$SIZE/1024;
			$SIZE_mb=$SIZE_kb/1024;
			$quotasize_kb=$quotasize/1024;
			$quotasize_mb=round($quotasize_kb/1024);
			if($GLOBALS["DebugQuota"]){$this->events( "CheckByHourSpeed: uid:[$this->userid] Current size $SIZE/{$SIZE_kb}KB/{$SIZE_mb}MB //-> $quotasize/{$quotasize_kb}kb/{$quotasize_mb}MB [".__LINE__."]");}
			
			if($SIZE>$quotasize){
				if($GLOBALS["DebugQuota"]){$this->events( "CheckByHourSpeed uid:[$this->userid] $SIZE match $quotasize [".__LINE__."]");}
				return true;
			}
			return false;
		}
		
		if($this->ByCategory){
			$SIZE=intval($GLOBALS["CurrentSizesSpeed"]["categories"][$this->category]["HOUR"][$this->IP]);
		}else{
			$SIZE=intval($GLOBALS["CurrentSizesSpeed"]["IPADDR"][$this->IP]["HOUR"]);
		}
		
		
		if($GLOBALS["DebugQuota"]){
			$SIZE_kb=$SIZE/1024;
			$SIZE_mb=$SIZE_kb/1024;
			$quotasize_kb=$quotasize/1024;
			$quotasize_mb=round($quotasize_kb/1024);
			$this->events( "CheckByHourSpeed: IPADDR:[$this->IP] Current size $SIZE/{$SIZE_kb}KB/{$SIZE_mb}MB //-> $quotasize/{$quotasize_kb}kb/{$quotasize_mb}MB [".__LINE__."]");}
		
			if($SIZE>0){
				if($SIZE>$quotasize){
					if($GLOBALS["DebugQuota"]){$this->events( "CheckByHourSpeed IPADDR:[$this->IP] $SIZE match $quotasize [".__LINE__."]");}
					return true;
				}
				return false;
			}		
		
	}
	
	
	private function CheckByHour($quotasize){
		$this->userid=strtolower($this->userid);
		$this->LoadDbSpeed();
		
		if(count($GLOBALS["CurrentSizesSpeed"])>0){
			if($GLOBALS["DebugQuota"]){$this->events( "CheckByHour -> CheckByHourSpeed [".__LINE__."]");}
			return $this->CheckByHourSpeed($quotasize);
		}
		
		if($this->userid<>null){
			if($this->ByCategory){
				$SIZE=intval($GLOBALS["CurrentSizesUsers"]["categories"][$this->category]["HOUR"][$this->userid]);
				if($GLOBALS["DebugQuota"]){$this->events( "CheckByHour $this->userid $this->category SIZE ($SIZE) [".__LINE__."]");}
			}else{
				$SIZE=intval($GLOBALS["CurrentSizesUsers"]["UID"][$this->userid]["HOUR"]);
			}
		}
		
		if($GLOBALS["DebugQuota"]){events( "CheckByHour: uid:[$this->userid] $SIZE/$quotasize [".__LINE__."]");}
		
		if($SIZE>0){
			if($SIZE>$quotasize){
				if($GLOBALS["DebugQuota"]){events( "CheckByHour uid:[$this->userid] $SIZE match $quotasize [".__LINE__."]");}
				return true;
			}
			return false;
		}
		
		if($this->ByCategory){
			$SIZE=intval($GLOBALS["CurrentSizesUsers"]["categories"][$this->category]["HOUR"][$this->IP]);
		}else{
			$SIZE=intval($GLOBALS["CurrentSizesUsers"]["IPADDR"][$this->IP]["HOUR"]);
		}
		
		
		if($GLOBALS["DebugQuota"]){
			$SIZE_kb=$SIZE/1024;
			$SIZE_mb=$SIZE_kb/1024;
			$quotasize_kb=$quotasize/1024;
			$quotasize_mb=round($quotasize_kb/1024);
			$this->events( "CheckByHour: IPADDR:[$this->IP] Current size $SIZE/{$SIZE_kb}KB/{$SIZE_mb}MB //-> $quotasize/{$quotasize_kb}kb/{$quotasize_mb}MB [".__LINE__."]");}
		
		if($SIZE>0){
			if($SIZE>$quotasize){
				if($GLOBALS["DebugQuota"]){events( "CheckByHour IPADDR:[$this->IP] $SIZE match $quotasize [".__LINE__."]");}
				return true;
			}
			return false;
		}
			
		
	}
	
	function QuotaSizeBlock($RULEID){
		$CACHEKEY=$this->CACHEKEY;
		$UseExternalWebPage=$GLOBALS["ARTICA_QUOTAS_RULES"][$RULEID]["UseExternalWebPage"];
		$ExternalWebPage=$GLOBALS["ARTICA_QUOTAS_RULES"][$RULEID]["ExternalWebPage"];
		$GLOBALS["QuotaSizeResults"][$CACHEKEY]["TIME"]=time();
	
		if($UseExternalWebPage==1){
			$GLOBALS["QuotaSizeResults"][$CACHEKEY]["RETURN"]=$ExternalWebPage;
			Output_results($ExternalWebPage,__FUNCTION__,__LINE__);
			return true;
	
		}
		$urlenc=urlencode($this->URI);
		$returned="{$GLOBALS["SquidGuardIPWeb"]}?rule-id=0SquidGuardIPWeb=".
		base64_encode($GLOBALS["SquidGuardIPWeb"])."&clientaddr=$this->IP&clientname=$this->IP&clientuser=$this->userid".
		"&clientgroup=$this->category&targetgroup=quota&url=$urlenc";
		ufdbgevents($this->category,"quota");
		$this->returned=$returned;
		return true;
	
	}
	
	
	private function ifMatchesRules($ruleidZ){
		while (list ($RULEID, $NONE) = each ($ruleidZ) ){
			$quotaPeriod=$GLOBALS["ARTICA_QUOTAS_RULES"][$RULEID]["quotaPeriod"];
			$quotasize=$GLOBALS["ARTICA_QUOTAS_RULES"][$RULEID]["quotasize"]*1024;
			$UseExternalWebPage=$GLOBALS["ARTICA_QUOTAS_RULES"][$RULEID]["UseExternalWebPage"];
			$ExternalWebPage=$GLOBALS["ARTICA_QUOTAS_RULES"][$RULEID]["ExternalWebPage"];
			$quotasize=$quotasize*1024;
			$SIZE=0;
			if($GLOBALS["DebugQuota"]){$this->events( "ifMatchesRules: Check Rule N.$RULEID ByCategory:\"$this->ByCategory\" Rule: $RULEID Max size:$quotasize Period:$quotaPeriod [".__LINE__."]");}
			
			switch ($quotaPeriod) {
				case 1:
					if($this->CheckByHour($quotasize)){ return $this->QuotaSizeBlock($RULEID); }
					break;
				case 2:
					if($this->CheckByDay($quotasize)){ return $this->QuotaSizeBlock($RULEID); }
					break;
				case 2:
					if($this->CheckbyWeek($quotasize)){ return $this->QuotaSizeBlock($RULEID); }
					break;					
				default:
					if($this->CheckbyWeek($quotasize)){ return $this->QuotaSizeBlock($RULEID); }
					break;
			}
			
			
			
		}
	}
	
	
	private function LoadDbSpeed(){
		
		if(isset($GLOBALS["LoadDbSpeed"]["TIME"])){
			$tool_time_sec=$this->tool_time_sec($GLOBALS["LoadDbSpeed"]["TIME"]);
			if($tool_time_sec<45){return;}
		}
		
		if(!isset($GLOBALS["LoadDbSpeed"]["MMTIME"])){
			if(is_file("/usr/share/squid3/SpeedSizesUsers.db")){
				$GLOBALS["LoadDbSpeed"]["TIME"]=0;
				$GLOBALS["LoadDbSpeed"]["MMTIME"]=filemtime("/usr/share/squid3/SpeedSizesUsers.db");
				$GLOBALS["CurrentSizesSpeed"]=unserialize(@file_get_contents("/usr/share/squid3/SpeedSizesUsers.db"));
				return;
			}
		}
		
		if(!is_file("/usr/share/squid3/SpeedSizesUsers.db")){
			if($GLOBALS["DebugQuota"]){$this->events("/usr/share/squid3/SpeedSizesUsers.db no such file");}
			$GLOBALS["LoadDbSpeed"]["TIME"]=0;
			$GLOBALS["LoadDbSpeed"]["MMTIME"]=0;
			$GLOBALS["CurrentSizesSpeed"]=array();
			return;
		}
		
		$memtime=filemtime("/usr/share/squid3/SpeedSizesUsers.db");
		if($memtime==$GLOBALS["LoadDbSpeed"]["MMTIME"]){
			$GLOBALS["LoadDbSpeed"]["TIME"]=0;
			return;
		}
		$GLOBALS["LoadDbSpeed"]["TIME"]=0;
		$GLOBALS["LoadDbSpeed"]["MMTIME"]=$memtime;
		$GLOBALS["CurrentSizesSpeed"]=unserialize(@file_get_contents("/usr/share/squid3/SpeedSizesUsers.db"));
			
	}
	
	
	private function tool_time_sec($last_time){
		$data1 = $last_time;
		$data2 = time();
		$difference = ($data2 - $data1);
		return $difference;
	}
	
	
	private function IfMatchCategories($category,$RULES){
		$key=md5($category.serialize($RULES));
		if(isset($GLOBALS["QuotaSizeIfMatchCategories"][$key])){return $GLOBALS["QuotaSizeIfMatchCategories"][$key];}
		$GLOBALS["QuotaSizeIfMatchCategories"][$key]=array();
		$category=trim(strtolower($category));
		while (list ($RULEID, $NONE) = each ($RULES) ){
			$categories=$GLOBALS["ARTICA_QUOTAS_RULES"][$RULEID]["categories"];
			if(count($categories)==0){continue;}
			while (list ($category_id, $NONE) = each ($categories) ){
				$category_id=trim(strtolower($category_id));
				if($category_id<>$category){
					if($GLOBALS["DebugQuota"]){events( "QuotaSizeIfMatchCategories: $category_id did not match $category [".__LINE__."]");}
					continue;
				}
					
				if($GLOBALS["DebugQuota"]){events( "QuotaSizeIfMatchCategories: $category_id == match $category [".__LINE__."]");}
				$GLOBALS["QuotaSizeIfMatchCategories"][$key][$RULEID]=true;
			}
	
		}
		return $GLOBALS["QuotaSizeIfMatchCategories"][$key];
	
	}	
	
	
	
	function IfMatchIP($ipaddr){
		if(isset($GLOBALS["QuotaSizeIfMatchIP"][$ipaddr])){return $GLOBALS["QuotaSizeIfMatchIP"][$ipaddr];}
		$GLOBALS["QuotaSizeIfMatchIP"][$ipaddr]=array();
		$IDZ=array();
		$ARTICA_QUOTAS_RULES=$GLOBALS["ARTICA_QUOTAS_RULES"];
	
		while (list ($ID, $ARRAY) = each ($ARTICA_QUOTAS_RULES) ){
			$GROUPS=$ARRAY["GROUPS"];
			if(count($GROUPS)==0){continue;}
			while (list ($GroupType, $ARRAY_GROUP) = each ($GROUPS) ){
				if($GroupType<>"FREE"){continue;}
				if($GLOBALS["DebugQuota"]){$this->events( "[$ipaddr]: Rule $ID Group $GroupType [".__LINE__."]");}
				// -------------------------------------------------------------------------------------------------------
					
				while (list ($ItemType, $values) = each ($ARRAY_GROUP) ){
					if($ItemType<>"IP"){continue;}
					while (list ($index, $value) = each ($values) ){
						$value=trim(strtolower($value));
						if($GLOBALS["DebugQuota"]){$this->events( "[$ipaddr]: Match rule $ID![".__LINE__."]");}
						if($value==$ipaddr){$GLOBALS["QuotaSizeIfMatchIP"][$ipaddr][$ID]=true;}
					}
				}
					
				// -------------------------------------------------------------------------------------------------------
					
			}
	
		}
		return $GLOBALS["QuotaSizeIfMatchIP"][$ipaddr];
	}
	
	
	function ifMatchAll(){
		if(isset($GLOBALS["QuotaSizeIfMatchALL"]["ALL"])){
			if($GLOBALS["DebugQuota"]){$this->events( "ifMatchAll return from memory...[".__LINE__."]");}
			return $GLOBALS["QuotaSizeIfMatchALL"]["ALL"];
		}
		$ARTICA_QUOTAS_RULES=$GLOBALS["ARTICA_QUOTAS_RULES"];
		while (list ($ID, $ARRAY) = each ($ARTICA_QUOTAS_RULES) ){
			
			$GROUPS=$ARRAY["GROUPS"];
			$COUNTFGRP=count($GROUPS);
			if($GLOBALS["DebugQuota"]){$this->events( "ifMatchAll RULE: $ID {$COUNTFGRP} Groups");}
			
			if($COUNTFGRP==0){
				if($GLOBALS["DebugQuota"]){$this->events( "ifMatchAll RULE: $ID OK");}
				$GLOBALS["QuotaSizeIfMatchALL"]["ALL"][$ID]=true;
			}
		}
		if(isset($GLOBALS["QuotaSizeIfMatchALL"]["ALL"])){
			return $GLOBALS["QuotaSizeIfMatchALL"]["ALL"];
		}
	}
	
	function ifMatchAllSystems(){
		if(isset($GLOBALS["QuotaSizeIfMatchAllSystems"]["ALL"])){
			if($GLOBALS["DebugQuota"]){$this->events( "ifMatchAllSystems return from memory...[".__LINE__."]");}
			return $GLOBALS["QuotaSizeIfMatchAllSystems"]["ALL"];
		}
		
		$ARTICA_QUOTAS_RULES=$GLOBALS["ARTICA_QUOTAS_RULES"];
		while (list ($ID, $ARRAY) = each ($ARTICA_QUOTAS_RULES) ){
				
			$AllSystems=intval($ARRAY["AllSystems"]);
			if($AllSystems==1){
				$GLOBALS["QuotaSizeIfMatchAllSystems"]["ALL"][$ID]=true;
			}
			
		}
		if(isset($GLOBALS["QuotaSizeIfMatchAllSystems"]["ALL"])){
			return $GLOBALS["QuotaSizeIfMatchAllSystems"]["ALL"];
		}		
		
		
	}
	
	
	private function quotaconfig(){
	
	
		if(isset($GLOBALS["ARTICA_QUOTAS_RULES_CHECK_NO_FILE"])){
			if(tool_time_min($GLOBALS["ARTICA_QUOTAS_RULES_CHECK_NO_FILE"])==0){return;}
		}
	
		if(!is_file("/etc/squid3/artica.quotas.rules.db")){
			$GLOBALS["ARTICA_QUOTAS_RULES_CHECK_NO_FILE"]=time();
			return;
		}
	
	
		if(!isset($GLOBALS["ARTICA_QUOTAS_RULES"])){$GLOBALS["ARTICA_QUOTAS_RULES"]=array();}
		if(isset($GLOBALS["CATZ_MEM"])){if(count($GLOBALS["CATZ_MEM"])>10000){$GLOBALS["CATZ_MEM"]=array();} }
	
		if(!isset($GLOBALS["CurrentSizesUsers"])){
			$GLOBALS["CurrentSizesUsers"]=unserialize(@file_get_contents("/usr/share/squid3/CurrentSizesUsers.db"));
			$GLOBALS["CurrentSizesUsers_CHECK"]=time();
		}
	
		if(isset($GLOBALS["CurrentSizesUsers_CHECK"])){
			if($GLOBALS["CurrentSizesUsers_CHECK"]>0){
				if(tool_time_min($GLOBALS["CurrentSizesUsers_CHECK"])>5){
					$GLOBALS["CurrentSizesUsers"]=unserialize(@file_get_contents("/usr/share/squid3/CurrentSizesUsers.db"));
					$GLOBALS["CurrentSizesUsers_CHECK"]=time();
				}
			}
		}
	
	
	
		if(isset($GLOBALS["ARTICA_QUOTAS_RULES_CHECK"])){
			if($GLOBALS["ARTICA_QUOTAS_RULES_CHECK"]>0){
				if(tool_time_min($GLOBALS["ARTICA_QUOTAS_RULES_CHECK"])>0){
					if(!is_file("/etc/squid3/artica.quotas.rules.db")){
						$GLOBALS["ARTICA_QUOTAS_RULES_CHECK"]=time();
						return;
					}
					$filemtime=filemtime("/etc/squid3/artica.quotas.rules.db");
					if($filemtime==$GLOBALS["ARTICA_QUOTAS_RULES_TIME"]){return;}
				}
			}
		}
		$GLOBALS["QuotaSizeIfMatchUID"]=array();
		$GLOBALS["ARTICA_QUOTAS_RULES"]=array();
		$GLOBALS["QuotaSizeIfMatchIP"]=array();
		$GLOBALS["QuotaSizeIfMatchALL"]=array();
		$GLOBALS["QuotaSizeIfMatchAllSystems"]=array();
		if(is_file("/etc/squid3/artica.quotas.rules.db")){
			$GLOBALS["ARTICA_QUOTAS_RULES_TIME"]=filemtime("/etc/squid3/artica.quotas.rules.db");
			$GLOBALS["ARTICA_QUOTAS_RULES_CHECK"]=time();
			$GLOBALS["ARTICA_QUOTAS_RULES"]=unserialize(@file_get_contents("/etc/squid3/artica.quotas.rules.db"));
		}
	
		if($GLOBALS["DebugQuota"]){$this->events("Quota rules: ".count($GLOBALS["ARTICA_QUOTAS_RULES"])." rules...");}
	}
	
	function IfMatchUID($uid=null){
		if(isset($GLOBALS["QuotaSizeIfMatchUID"][$uid])){return $GLOBALS["QuotaSizeIfMatchUID"][$uid];}
		$GLOBALS["QuotaSizeIfMatchUID"][$uid]=array();
		$IDZ=array();
		if($uid==null){return $IDZ;}
		$ARTICA_QUOTAS_RULES=$GLOBALS["ARTICA_QUOTAS_RULES"];
		$uid=trim(strtolower($uid));
	
		while (list ($ID, $ARRAY) = each ($ARTICA_QUOTAS_RULES) ){
			$GROUPS=$ARRAY["GROUPS"];
			if(count($GROUPS)==0){continue;}
			while (list ($GroupType, $ARRAY_GROUP) = each ($GROUPS) ){
				if($GLOBALS["DebugQuota"]){$this->events( "[$uid]: Rule $ID Group $GroupType [".__LINE__."]");}
					
				// -------------------------------------------------------------------------------------------------------
				if($GroupType=="FREE"){
					while (list ($ItemType, $values) = each ($ARRAY_GROUP) ){
						if($ItemType<>"USER"){continue;}
						while (list ($index, $value) = each ($values) ){
							$value=trim(strtolower($value));
							if($value==$uid){
								if($GLOBALS["DebugQuota"]){$this->events( "QuotaSizeIfMatchUID: [$uid]: Match rule $ID![".__LINE__."]");}
								$GLOBALS["QuotaSizeIfMatchUID"][$uid][$ID]=true;}
						}
					}
				}
				// -------------------------------------------------------------------------------------------------------
				if($GroupType=="AD"){
					if(!class_exists("ActiveDirectory")){include_once(dirname(__FILE__)."/ressources/class.ActiveDirectory.inc");}
					while (list ($NONE,$DN ) = each ($ARRAY_GROUP) ){
							
						if(!isset($GLOBALS["QUOTASIZE"]["AD"][$DN])){
							if($GLOBALS["DebugQuota"]){$this->events( "QuotaSizeIfMatchUID::[$DN]: Not in memory [".__LINE__."]");}
							$GLOBALS["QUOTASIZE"]["AD"][$DN]["TIME"]=0;
						}
							
						if(tool_time_min($GLOBALS["QUOTASIZE"]["AD"][$DN]["TIME"])>14){
							$ad=new ActiveDirectory(0);
							$GLOBALS["QUOTASIZE"]["AD"][$DN]["USERS"]=$ad->dump_users_from_group($DN);
							if($GLOBALS["DebugQuota"]){$this->events( "QuotaSizeIfMatchUID::[$DN]:".count($GLOBALS["QUOTASIZE"]["AD"][$DN]["USERS"])." members [".__LINE__."]");}
							$GLOBALS["QUOTASIZE"]["AD"][$DN]["TIME"]=time();
						}
							
						if($GLOBALS["DebugQuota"]){$this->events( "[$uid]: $DN = ".count($GLOBALS["QUOTASIZE"]["AD"][$DN]["USERS"])." [".__LINE__."]");}
						if(isset($GLOBALS["QUOTASIZE"]["AD"][$DN]["USERS"][$uid])){
							if($GLOBALS["DebugQuota"]){$this->events( "[$uid]: Match rule $ID![".__LINE__."]");}
							$GLOBALS["QuotaSizeIfMatchUID"][$uid][$ID]=true;
						}
					}
						
				}
				// -------------------------------------------------------------------------------------------------------
	
			}
	
		}
		return $GLOBALS["QuotaSizeIfMatchUID"][$uid];
	
	}	
	
	
	function GetCategory($sitename){
		if(!class_exists("mysql_catz")){return null;}
		$time_start=tool_microtime_float();
		if(!isset($GLOBALS["CATZ_MEM"][$sitename])){
			$q=new mysql_catz();
			$GLOBALS["CATZ_MEM"][$sitename]=$q->GET_CATEGORIES($sitename);
			$time_end=tool_microtime_float();
		
		}
		$category=$GLOBALS["CATZ_MEM"][$sitename];
		$tt = round($time_end - $time_start,3);
		if($GLOBALS["DebugQuota"]){$this->events( "Category:$category for $sitename {$tt}ms [[".__LINE__."]");}
	}
	
	
	private function events($text,$line=0){
		if(trim($text)==null){return;}
		$pid=$GLOBALS["MYPID"];
		$date=@date("H:i:s");
		$logFile="/var/log/squid/ufdbgclient.quotas.debug";
		$time_end=tool_microtime_float();
		$tt = round($time_end - $GLOBALS["time_loop_start"],3);
	
		$size=@filesize($logFile);
		if($size>9000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
	
		@fwrite($f, "$date:[".basename(__FILE__)."/{$GLOBALS["UFDBVERS"]} $pid [{$GLOBALS["LOG_DOM"]}]:$text - {$tt}ms $line\n");
		@fclose($f);
	}	
	
	
}
