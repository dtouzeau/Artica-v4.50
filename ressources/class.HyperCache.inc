<?php

class HyperCache{
	
	function __construct(){
		$this->GetConf();
		
	}
	
	public function HyperCacheUriToHostname($url){
		if(strpos($url, "//")==0){return $url;}
		$H=parse_url($url);
		$domain=$H["host"];
		if(preg_match("#(.+?):([0-9]+)#", $domain,$re)){$domain=$re[1];}
		return $domain;
	}
	
	
	public function ChecksOtherRules($urikey,$FileType,$ID){
		
		if($GLOBALS["HYPER_CACHE_VERBOSE_RULES"]){echo "---------] ChecksOtherRules(....,$FileType,$ID) [".__LINE__."]\n";}
		
		if(!is_numeric($ID)){return false;}
		if($ID==0){return false;}
		
		$RULES=$this->HyperCacheRulesMatches($urikey,$ID);
		if($GLOBALS["HYPER_CACHE_VERBOSE_RULES"]){echo "---------] ChecksOtherRules ". count($RULES)." rules [".__LINE__."]\n";}
		if(count($RULES)>0){
			while (list ($ID, $FileTypesArray) = each ($RULES) ){
				if(isset($FileTypesArray[$FileType])){
					if($GLOBALS["HYPER_CACHE_VERBOSE_RULES"]){echo "---------] *OK* ChecksOtherRules $FileType MATCH [".__LINE__."]\n";}
					return true;
				}
			}
			
		}
		if($GLOBALS["HYPER_CACHE_VERBOSE_RULES"]){echo "---------]  - - - $FileType NONE - - - [".__LINE__."]\n";}
		return false;
		
	}
	
	
	public function debug($text,$function,$line){
		
		if(!$GLOBALS["HyperCacheDebug"]){return;}
		events(__CLASS__."/$function:`$text [$line]");
		
	}
	
	
	public function HyperCacheRulesMatches($URI,$allids=0){
		if(!isset($GLOBALS["HyperCacheRules"])){$this->HyperCacheRulesLoad();}
		$MAIN=$GLOBALS["HyperCacheRules"];
		$OtherDomains=array();
		if(count($MAIN)==0){
			$this->debug("HyperCacheRulesMatches NO rules", __FUNCTION__, __LINE__);
			if($GLOBALS["HYPER_CACHE_VERBOSE_RULES"]){echo "---------] HyperCacheRulesMatches NO rules [".__LINE__."]\n";}
			return 0;
		}
		$IDS=array();
		
		while (list ($ID, $ligne) = each ($MAIN) ){
			if($allids>0){ if($allids==$ID){continue;} }
			if($GLOBALS["HYPER_CACHE_VERBOSE_RULES"]){echo "---------] HyperCacheRulesMatches Check RULE ID $ID [".__LINE__."]\n";}
			
			$sitename=$ligne["sitename"];
			$FileTypesArray=unserialize($ligne["FileTypes"]);
			if(trim($ligne["OtherDomains"])<>null){$OtherDomains=unserialize($ligne["OtherDomains"]); }
			if(!is_array($OtherDomains)){$OtherDomains=array();}
			
			if($this->HyperCacheRulesMatchPattern($sitename,$URI)){
				$this->debug("$sitename Matches rule id $ID ", __FUNCTION__, __LINE__);
				if($allids==0){return $ID;}
				if($GLOBALS["HYPER_CACHE_VERBOSE_RULES"]){echo "---------] $ID ++ [".__LINE__."]\n";}
				$IDS[$ID]=$FileTypesArray;
				continue;
			}
			
			if(count($OtherDomains)>0){
				while (list ($domain, $ligne) = each ($OtherDomains) ){
					if($this->HyperCacheRulesMatchPattern($domain,$URI)){
						$this->debug("$sitename Matches rule id $ID ", __FUNCTION__, __LINE__);
						if($allids==0){return $ID;}
						if($GLOBALS["HYPER_CACHE_VERBOSE_RULES"]){echo "---------] $ID ++ [".__LINE__."]\n";}
						$IDS[$ID]=$FileTypesArray;
						continue;
					}
				}
			}
		}
		
		
		if($allids==0){return 0;}
		return $IDS;
	}
	
	public function HyperCacheRulesMatchPattern($pattern,$URI){
		$pattern=trim($pattern);
		if($pattern==null){return false;}
		if(preg_match("#(^|\.)dropbox\.com$#", $pattern)){return true;}
		
		
		if(preg_match("#regex:(.+)#", $pattern,$re)){
			$pattern=$re[1];
			if(preg_match("#$pattern#i", $URI)){
				if($GLOBALS["HYPER_CACHE_VERBOSE_RULES"]){echo "---------] $pattern MATCH [".__LINE__."]\n";}
				return true;
			}
			if($GLOBALS["HYPER_CACHE_VERBOSE_RULES"]){echo "---------] #$pattern# NO MATCH $URI [".__LINE__."]\n";}
			return false;
		}
		
		
		$sitename=$this->string_to_regex($pattern);
		
		if(strpos($URI, "://")>0){
			if(preg_match("#:\/\/(.*?)$sitename(\/|$)#",$URI)){
				if($GLOBALS["HYPER_CACHE_VERBOSE_RULES"]){echo "---------] $pattern MATCH #:\/\/(.*?)$sitename(\/|$)# [".__LINE__."]\n";}
				return true;
			}
		}else{
			if(preg_match("#$sitename#",$URI)){
				if($GLOBALS["HYPER_CACHE_VERBOSE_RULES"]){echo "---------] $pattern MATCH $sitename [".__LINE__."]\n";}
				return true;
			}
		}
		
		
		
		return false;
		
	}
	
	
	private function string_to_regex($pattern){
		if(trim($pattern)==null){return null;}
		$pattern=str_replace("/", "\/", $pattern);
		$pattern=str_replace(".", "\.", $pattern);
		//$pattern=str_replace("-", "\-", $pattern);
		$pattern=str_replace("[", "\[", $pattern);
		$pattern=str_replace("]", "\]", $pattern);
		$pattern=str_replace("(", "\(", $pattern);
		$pattern=str_replace(")", "\)", $pattern);
		$pattern=str_replace("$", "\$", $pattern);
		$pattern=str_replace("?", "\?", $pattern);
		$pattern=str_replace("#", "\#", $pattern);
		$pattern=str_replace("{", "\{", $pattern);
		$pattern=str_replace("}", "\}", $pattern);
		$pattern=str_replace("^", "\^", $pattern);
		$pattern=str_replace("!", "\!", $pattern);
		$pattern=str_replace("+", "\+", $pattern);
		$pattern=str_replace("*", ".*?", $pattern);
		$pattern=str_replace("|", "\|", $pattern);
		return $pattern;
	}
	
	private function HyperCacheRulesLoad(){
		if($GLOBALS["SquidEnforceRules"]==0){return;}
		$dbfile="/usr/share/squid3/HyperCacheRules.db";
		if(!is_file($dbfile)){return;}
	
		$db_con = dba_open($dbfile, "r","db4");
	
		if(!$db_con){
			$this->events("HyperCache: HyperCacheRulesLoad():: FATAL!!!::$dbfile, unable to open");
			return false;
		}
	
		$mainkey=trim(dba_firstkey($db_con));
	
		while($mainkey !=false){
			$array=unserialize(dba_fetch($mainkey,$db_con));
            if(!is_array($array)){$array=array();}
	
			$GLOBALS["HyperCacheRules"][$mainkey]=$array;
			$this->events("HyperCache: HyperCacheRulesLoad():: Loading rule ID $mainkey");
			$mainkey=dba_nextkey($db_con);
	
		}
	
		dba_close($db_con);
	
	}
	
	private function events($text){
		if(trim($text)==null){return;}
		$chown=false;
		$pid=$GLOBALS["MYPID"];
		$date=@date("H:i:s");
		$logFile="/var/log/squid/ufdbgclient.debug";
	
		$size=@filesize($logFile);
		if($size>9000000){@unlink($logFile);$chown=true;}
		$f = @fopen($logFile, 'a');
		if($GLOBALS["OUTPUT"]){echo "$pid `[{$GLOBALS["LOG_DOM"]}]: $text`\n";}
		@fwrite($f, "$date:[".basename(__FILE__)."] $pid $text\n");
		@fclose($f);
	}
	
	private function GetConf(){
		if(!isset($GLOBALS["SquidEnforceRules"])){
			$GLOBALS["SquidEnforceRules"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnforceRules"));
		}
		if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
		
		
	}
	
	
}