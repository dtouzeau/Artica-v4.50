<?php

class externhelper{
	function __construct(){
	 if($GLOBALS["DEBUG_LEVEL"]>0){$GLOBALS["F"] = @fopen("/var/log/squid/external-acl.log", 'a');}
	 $GLOBALS["STARTIME"]=time();	
		
	}
	
	function parseURL($url){
		$uri=null;
		if($GLOBALS["DEBUG_LEVEL"]>1){$this->WLOG("Analyze $url");}
		$md5=md5($url);
		if(preg_match("#(http|ftp|https|ftps):\/\/(.*)#i", $url,$re)){
			$uri=$re[1]."://".$re[2];
			if($GLOBALS["DEBUG_LEVEL"]>1){$this->WLOG("found uri $uri");}
			$url=trim(str_replace($uri, "", $url));
			if($GLOBALS["DEBUG_LEVEL"]>1){$this->WLOG("Analyze $url");}
			
		}
		if($uri==null){
			if(preg_match("#([a-z0-9\.]+):([0-9]+)$#i", $url,$re)){
				$uri="http://".$re[1].":".$re[2];
				if($GLOBALS["DEBUG_LEVEL"]>1){$this->WLOG("found uri $uri");}
				$url=trim(str_replace($re[1].":".$re[2], "", $url));
				if($GLOBALS["DEBUG_LEVEL"]>1){$this->WLOG("Analyze \"$url\"");}
			}
		}
		if($uri<>null){
			$URLAR=parse_url($uri);
			if(isset($URLAR["host"])){$rhost=$URLAR["host"];}
		}
		
		
		
		
		if(isset($GLOBALS["CACHE_URI"][$md5])){return $GLOBALS["CACHE_URI"][$md5];}
		$tr=explode(" ", $url);
		if($GLOBALS["DEBUG_LEVEL"]>1){
			while (list ($index, $line) = each ($tr)){
				$this->WLOG("tr[$index] = $line");	
			}
		}
		
		
		//max auth=4
		if(count($tr)==4){
			if($GLOBALS["DEBUG_LEVEL"]>1){$this->WLOG("count --> 4");}
			$login=$tr[0];
			$ipaddr=$tr[1];
			$mac=$tr[2];
			$forwarded=$tr[3];
			if(isset($tr[4])){$uri=$tr[4];}
			if($mac=="00:00:00:00:00:00"){$mac=null;}
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $forwarded)){$ipaddr=$forwarded;}
			if($mac==null){$mac=$this->GetMacFromIP($ipaddr);}
			
			if($mac=="00:00:00:00:00:00"){$mac=null;}
			$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=$login;
			$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$ipaddr;
			$GLOBALS["CACHE_URI"][$md5]["MAC"]=$mac;
			$GLOBALS["CACHE_URI"][$md5]["HOST"]=$this->GetComputerName($ipaddr);
			$GLOBALS["CACHE_URI"][$md5]["URI"]=$uri;
			$GLOBALS["CACHE_URI"][$md5]["RHOST"]=$rhost;
			$GLOBALS["CACHE_URI"][$md5]["DDATE"]=time();
			return $GLOBALS["CACHE_URI"][$md5];
		}
		
		
		
		if(count($tr)==3){
			if($GLOBALS["DEBUG_LEVEL"]>1){$this->WLOG("count --> 3");}
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $tr[0])){
				//ip en premier donc mac=ok, pas de login
				$login=null;	
				$ipaddr=$tr[0];
				$mac=$tr[1];
				$forwarded=$tr[2];
				if(isset($tr[3])){$uri=$tr[3];}	
			}else{
				//login en premier donc mac=bad
				$login=$tr[0];
				$ipaddr=$tr[1];
				
				$forwarded=$tr[2];
				if(isset($tr[3])){$uri=$tr[3];}	
			}
			if($mac=="00:00:00:00:00:00"){$mac=null;}
			if(preg_match("#[0-9]+\[0-9]+\.[0-9]+\.[0-9]+#", $forwarded)){$ipaddr=$forwarded;}
			if($mac==null){$mac=$this->GetMacFromIP($ipaddr);}
			if($mac=="00:00:00:00:00:00"){$mac=null;}
			$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=$login;
			$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$ipaddr;
			$GLOBALS["CACHE_URI"][$md5]["MAC"]=$mac;
			$GLOBALS["CACHE_URI"][$md5]["HOST"]=$this->GetComputerName($ipaddr);
			$GLOBALS["CACHE_URI"][$md5]["URI"]=$uri;	
			$GLOBALS["CACHE_URI"][$md5]["RHOST"]=$rhost;	
			$GLOBALS["CACHE_URI"][$md5]["DDATE"]=time();	
			return $GLOBALS["CACHE_URI"][$md5];		
			
		}
		
		
		
		if(count($tr)==2){
			if($GLOBALS["DEBUG_LEVEL"]>1){$this->WLOG("count --> 2");}
			//pas de login et pas de MAC;
			$login=null;	
			$ipaddr=$tr[0];
			$mac=null;
			$forwarded=$tr[1];
			if(isset($tr[2])){$uri=$tr[2];}	
			if(preg_match("#[0-9]+\[0-9]+\.[0-9]+\.[0-9]+#", $forwarded)){$ipaddr=$forwarded;}
			
		}
		if($mac==null){$mac=$this->GetMacFromIP($ipaddr);}
		else{		
			if($mac=="00:00:00:00:00:00"){$mac=null;$mac=$this->GetMacFromIP($ipaddr);}
		}
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		$GLOBALS["CACHE_URI"][$md5]["LOGIN"]=$login;
		$GLOBALS["CACHE_URI"][$md5]["IPADDR"]=$ipaddr;
		$GLOBALS["CACHE_URI"][$md5]["MAC"]=$mac;
		$GLOBALS["CACHE_URI"][$md5]["HOST"]=$this->GetComputerName($ipaddr);
		$GLOBALS["CACHE_URI"][$md5]["URI"]=$uri;	
		$GLOBALS["CACHE_URI"][$md5]["RHOST"]=$rhost;
		$GLOBALS["CACHE_URI"][$md5]["DDATE"]=time();
		return $GLOBALS["CACHE_URI"][$md5];
		
		
	}
	private function GetComputerName($ip){
			$time=time("Ymh");
			if(count($GLOBALS["resvip"])>5){unset($GLOBALS["resvip"]);}
			if(isset($GLOBALS["resvip"][$time][$ip])){return $GLOBALS["resvip"][$time][$ip];}
			$name=@gethostbyaddr($ip);
			$GLOBALS["resvip"][$time][$ip]=$name;
			return $name;
			}

	private function GetMacFromIP($ipaddr){
		
		if(!isset($GLOBALS["EnableMacAddressFilter"])){
			$sock=new sockets();
			$GLOBALS["EnableMacAddressFilter"]=$sock->GET_INFO("EnableMacAddressFilter");
			if(!is_numeric($GLOBALS["EnableMacAddressFilter"])){$GLOBALS["EnableMacAddressFilter"]=1;}
		
		}
		
		if($GLOBALS["EnableMacAddressFilter"]==0){return;}
		
		$ipaddr=trim($ipaddr);
		$ttl=date('YmdH');
		if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
		if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}
		if(!isset($GLOBALS["SBIN_ARP"])){$GLOBALS["SBIN_ARP"]=$this->find_program("arp");}
		if(!isset($GLOBALS["SBIN_ARPING"])){$GLOBALS["SBIN_ARPING"]=$this->find_program("arping");}
			
		if(strlen($GLOBALS["SBIN_ARPING"])>3){
			$cmd="{$GLOBALS["SBIN_ARPING"]} $ipaddr -c 1 -r 2>&1";
			exec($cmd,$results);
			foreach ($results as $num=>$line){
				if(preg_match("#^([0-9a-zA-Z\:]+)#", $line,$re)){
					if($this->IsPhysicalAddress($re[1])){
						$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
						return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
					}
				}
			}
		}
			
			
		$results=array();
				
		if(strlen($GLOBALS["SBIN_ARP"])<4){return;}
		if(!isset($GLOBALS["SBIN_PING"])){$GLOBALS["SBIN_PING"]=$this->find_program("ping");}
		if(!isset($GLOBALS["SBIN_NOHUP"])){$GLOBALS["SBIN_NOHUP"]=$this->find_program("nohup");}
			
		$cmd="{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1";
		$this->events($cmd);
		exec($cmd,$results);
		foreach ($results as $num=>$line){
				if(preg_match("#^[0-9\.]+\s+.+?\s+([0-9a-z\:]+)#", $line,$re)){
					if($re[1]=="no"){continue;}
					if($this->IsPhysicalAddress($re[1])){
						$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
						return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
					}
				}
				
			}
			
			if(!isset($GLOBALS["PINGEDHOSTS"][$ipaddr])){
				shell_exec("{$GLOBALS["SBIN_NOHUP"]} {$GLOBALS["SBIN_PING"]} $ipaddr -c 3 >/dev/null 2>&1 &");
				$GLOBALS["PINGEDHOSTS"][$ipaddr]=true;
			}
				
			
		}
		
		private function IsPhysicalAddress($address){
			$address=strtoupper(trim($address));
			if($address=="UNKNOWN"){return null;}
			$address=str_replace(":","-",$address);
			If(strlen($address) > 18){return false;}
			If($address == ""){return false;}
			If(!preg_match("#^[0-9A-Z]+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)+(\-[0-9A-Z]+)$#i",$address)){
		
				return false;
			}
			$Array=explode("-",$address);
			If(strlen($Array[0]) != 2){return false;}
			If(strlen($Array[1]) != 2){return false;}
			If(strlen($Array[2]) != 2){return false;}
			If(strlen($Array[3]) != 2){return false;}
			If(strlen($Array[4]) != 2){return false;}
			If(strlen($Array[5]) != 2){return false;}
		
			return true;
		}		
		

	public function WLOG($text=null){
		$trace=@debug_backtrace();
		if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
		$date=@date("Y-m-d H:i:s");
	   	if (is_file("/var/log/squid/external-acl.log")) { 
	   		$size=@filesize("/var/log/squid/external-acl.log");
	   		if($size>1000000){
	   			@fclose($GLOBALS["F"]);
	   			unlink("/var/log/squid/external-acl.log");
	   			$GLOBALS["F"] = @fopen("/var/log/squid/external-acl.log", 'a');
	   		}
	   		
	   		
	   	}
		
		
		@fwrite($GLOBALS["F"], "$date [{$GLOBALS["PID"]}]: $text $called\n");
	}

	private function find_program($strProgram) {
		  $key=md5($strProgram);
		  if(isset($GLOBALS["find_program"][$key])){return $GLOBALS["find_program"][$key];}
		  $value=trim($this->internal_find_program($strProgram));
		  $GLOBALS["find_program"][$key]=$value;
	      return $value;
	}
	private function internal_find_program($strProgram){
		  global $addpaths;	
		  $arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin', 
		  '/usr/local/sbin',
		  '/usr/kerberos/bin',
		  
		  );
		  
		  if (function_exists("is_executable")) {
		    foreach($arrPath as $strPath) {
		      $strProgrammpath = $strPath . "/" . $strProgram;
		      if (is_executable($strProgrammpath)) {
		      	  return $strProgrammpath;
		      }
		    }
		  } else {
		   	return strpos($strProgram, '.exe');
		  }
		}
		
	public function shutdown(){
		$distanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
		$distanceInMinutes = round($distanceInSeconds / 60);
		$this->WLOG("v1.0: die after ({$distanceInSeconds}s/about {$distanceInMinutes}mn)");
		if(isset($GLOBALS["F"])){@fclose($GLOBALS["F"]);}		
		
	}
	
}