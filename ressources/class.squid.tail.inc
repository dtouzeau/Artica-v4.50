<?php
include_once(dirname(__FILE__)."/class.tcpip.inc");

class squid_tail{
	public $error=null;
	public $ToRemove=false;
	public $GLOBAL_QUEUE=array();
	public $GLOBAL_PAGEKEEPER=array();
	public $GLOBAL_YOUTUBE=array();
	public $GLOBAL_RTTSIZE=array();
	public $GLOBAL_SQUIDUSERS=array();
	public $GLOBAL_SEARCHWORDS=array();
	function __construct(){
		if(!isset($GLOBALS["USERSDB"])){
				$this->LOADUSERDB();}
			else{
				$ttltime=@filemtime("/etc/squid3/usersMacs.db");
				if($GLOBALS["USERSDBT"]<>$ttltime){
					$this->LOADUSERDB();
				}
		}
		
		if(!isset($GLOBALS["EnableRemoteStatisticsAppliance"])){
			$GLOBALS["EnableRemoteStatisticsAppliance"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteStatisticsAppliance");
			if(!is_numeric($GLOBALS["EnableRemoteStatisticsAppliance"])){$GLOBALS["EnableRemoteStatisticsAppliance"]=0;}
		}
		if(!isset($GLOBALS["DisableArticaProxyStatistics"])){$GLOBALS["DisableArticaProxyStatistics"]=0;}
		$GLOBALS["TCP_CLASS"]=new IP();
		if(!isset($GLOBALS["RTIME"])){$GLOBALS["RTIME"]=array();}
		
	}
	
	
	private function LOADUSERDB(){
		$GLOBALS["USERSDBT"]=@filemtime("/etc/squid3/usersMacs.db");
		$usersMacsDB=@file_get_contents("/etc/squid3/usersMacs.db");
		$GLOBALS["USERSDB"]=@unserialize($usersMacsDB);
	}
	
	private function InjectSize($user,$ip,$mac,$UserAgent,$size,$xtime=0){
		if($GLOBALS["EnableRemoteStatisticsAppliance"]==1){return;}

				
	}
	
	
	public function parse_tail($buffer,$xtime=0){
		$PROTOS="PROPPATCH|MKCOL|MOVE|UNLOCK|DELETE|HTML|TEXT|PROPFIND|GET|POST|CONNECT|PUT|LOCK|NONE|HEAD|OPTIONS";
			$this->ToRemove=false;
			if($GLOBALS["EnableRemoteStatisticsAppliance"]==1){
				$this->error="EnableRemoteStatisticsAppliance =1";
				return;}
			
			$buffer=trim($buffer);
			if(preg_match("#GET cache_object#",$buffer)){$this->events("[SQUID]: cache_object abort".__LINE__);return true;}
			if(preg_match("#^.+?squid\[[0-9]+\]:#", $buffer,$re)){$buffer=str_replace($re[0], "",$buffer);}
			if(strpos($buffer, "HTTP/0.0")>0){
				if(!preg_match("#($PROTOS)\s+http.*?:#",$buffer)){
					$this->events("[SQUID]: HTTP/0.0 detected in \"$buffer\" -> abort in line:".__LINE__);
					return true;
				}
			}
			
			
			if(strpos($buffer, "TCP_DENIED:")>0){
				//$this->events("$buffer -> ABORT in line:".__LINE__);
				$this->error="TCP_DENIED";$this->ToRemove=true;return;
			}
				
			if(strpos($buffer, "RELEASE -1")>0){$this->error="RELEASE";$this->ToRemove=true;return;}
			if(strpos($buffer, "RELEASE 0")>0){$this->error="RELEASE";$this->ToRemove=true;return;}
			if(strpos($buffer, "SWAPOUT 0")>0){$this->error="SWAPOUT";$this->ToRemove=true;return;}
			if(strpos($buffer, "SWAPIN 0")>0){$this->error="SWAPOUT";$this->ToRemove=true;return;}
		
				
			if(isset($GLOBALS["SQUID_OLD_STYLE"])){
				if(preg_match("#^([0-9\.]+)\s+([0-9]+)\s+(.*?)\s+([A-Z\_]+)\/([0-9]+)\s+([0-9]+)\s+([A-Z]+)\s+(.*?)\s+(.*?)\s+([A-Z\_]+)\/(.*?)#", $buffer,$re)){
					$time=$re[1];
					$ip=$re[2];
					$SquidCode=$re[4];
					$Fdate=date("Y-m-d H:i:s",$time);
					$xtime=strtotime($Fdate);
					$cached=0;
					$size=$re[6];
					$uri=$re[8];
					$code_error=$re[5];
					$user=$re[9];
					if($this->CACHEDORNOT($SquidCode)){$cached=1;}
					if($ip=="127.0.0.1"){$this->events("$buffer -> ABORT in line:".__LINE__);$this->error="IP = 127.0.0.1  - $uri line:".__LINE__;$this->ToRemove=true;return;}
					$this->Builsql($ip,$user,$uri,$code_error,$size,$xtime,$cached,null,$xtime);
					return;
				}
			
				
			}
				
			if(preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9]+):([0-9]+):([0-9]+)\s+#", $buffer,$re)){
					$Fdate="{$re[1]} {$re[2]} {$re[3]}:{$re[4]}:{$re[5]}";
					$Ftime=strtotime($Fdate);
					$Fday=date("Y-m-d H:i:s",$Ftime);
					if($Fday<>"1970-01-01 01:00:00"){$xtime=$Ftime;}else{$this->events("Unable to determine date from $Fdate \"$buffer\"");}
				}				
				
			
			if(preg_match('#MAC:(.+?)\s+(.+?)\s+.+?\s+(.*?)\s+\[.+?:(.+?)\s+.+?\]\s+"([a-z]+:|'.$PROTOS.')\s+(.+?)\s+.+?"\s+([0-9]+)\s+([0-9]+)\s+([A-Z_]+)#',$buffer,$re)){
				    $cached=0;
				    $mac=trim($re[1]);
					$ip=trim($re[2]);
					$user=$re[3];
					$time=date('H:i:s');
					if($xtime>0){$time=date('H:i:s',$xtime);}
					$uri=$re[6];
					$code_error=$re[7];
					$size=$re[8];
					$SquidCode=$re[9];
					if($ip=="127.0.0.1"){
						$this->events("$buffer -> ABORT in line:".__LINE__);
						$this->error="IP = 127.0.0.1  - $uri line:".__LINE__;
						$this->ToRemove=true;
						return;}
					
					if($ip=="::"){
						$this->events("$buffer -> ABORT in line:".__LINE__);
						$this->error="IP = $ip - $uri line:".__LINE__;
						$this->ToRemove=true;
						return;
					}
					if($this->CACHEDORNOT($SquidCode)){$cached=1;}
					if($mac=="00:00:00:00:00:00"){$mac==null;}
					
					if(preg_match('#UserAgent:"(.+?)"#', $buffer,$re)){$UserAgent=$re[1];}
					if(preg_match('#Forwarded:"(.*?)"#', $buffer,$re)){$Forwarded=trim($re[1]);}
					if($Forwarded=="-"){$Forwarded=null;}
					if($Forwarded=="0.0.0.0"){$Forwarded=null;}
					if($Forwarded=="255.255.255.255"){$Forwarded=null;}
					if(strlen($Forwarded)>4){
						if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $Forwarded)){$ip=$Forwarded;$mac=null;}
					}	
					
					$this->Builsql($ip,$user,$uri,$code_error,$size,$time,$cached,$mac,$xtime);								
					$this->InjectUserAuthDb($user,$ip,$mac,$UserAgent,$xtime);
					
					return true;
						
			}	

			
			
			if(preg_match('#(.+?)\s+.+?\s+(.*?)\s+\[.+?:(.+?)\s+.+?\]\s+"(rer:|'.$PROTOS.')\s+(.+?)\s+.+?"\s+([0-9]+)\s+([0-9]+)\s+([A-Z_]+)#',$buffer,$re)){
				    $cached=0;
					$ip=$re[1];
					$user=$re[2];
					if($user=="-"){if(isset($re[3])){$user=$re[3];}}
					$size=$re[7];
					$time=date('H:i:s');
					if($xtime>0){$time=date('H:i:s',$xtime);}
					$SquidCode=$re[8];
					if($ip=="127.0.0.1"){
						$this->error="IP = 127.0.0.1 - $uri line:".__LINE__;
						$this->events("$buffer -> ABORT in line:".__LINE__);
						$this->ToRemove=true;
						return;}
						
					if($ip=="::"){
						$this->error="IP = $ip - $uri line:".__LINE__;
						$this->events("$buffer -> ABORT in line:".__LINE__);
						$this->ToRemove=true;
						return;
					}						
						
					if($this->CACHEDORNOT($SquidCode)){$cached=1;}
					
					if(preg_match('#UserAgent:"(.+?)"#', $buffer,$re)){$UserAgent=$re[1];}
					if(preg_match('#Forwarded:"(.*?)"#', $buffer,$re)){$Forwarded=trim($re[1]);}
					if($Forwarded=="-"){$Forwarded=null;}
					if($Forwarded=="0.0.0.0"){$Forwarded=null;}
					if($Forwarded=="255.255.255.255"){$Forwarded=null;}
					if(strlen($Forwarded)>4){
						if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $Forwarded)){$ip=$Forwarded;}
					}	
					$this->Builsql($ip,$user,$uri,$code_error,$size,$time,$cached,null,$xtime);
					$this->InjectUserAuthDb($user,$ip,$mac,$UserAgent,$xtime);
					
					return true;
						
			}

			if(preg_match('#MAC:(.+?)\s+(.+?)\s+.+?\s+(.*?)\s+\[.+?:(.+?)\s+.+?\]\s+"(rer:|'.$PROTOS.')\s+(.+?)$#',$buffer,$re)){
					$cached=0;
					$ip=$re[1];
					$user=$re[2];
					if($user=="-"){if(isset($re[3])){$user=$re[3];}}
					$time=date('H:i:s');
					if($xtime>0){$time=date('H:i:s',$xtime);}
					
					if($ip=="127.0.0.1"){
						$this->error="IP = 127.0.0.1 - $uri line:".__LINE__;
						$this->events("$buffer -> ABORT in line:".__LINE__);
						$this->ToRemove=true;
						return;}
						
					if($ip=="::"){
						$this->error="IP = $ip - $uri line:".__LINE__;
						$this->events("$buffer -> ABORT in line:".__LINE__);
						$this->ToRemove=true;
						return;
					}						
					$size=0;
					if(preg_match('#UserAgent:"(.+?)"#', $buffer,$re)){$UserAgent=$re[1];}
					if(preg_match('#Forwarded:"(.*?)"#', $buffer,$re)){$Forwarded=trim($re[1]);}
					if($Forwarded=="-"){$Forwarded=null;}
					if($Forwarded=="0.0.0.0"){$Forwarded=null;}
					if($Forwarded=="255.255.255.255"){$Forwarded=null;}
					if(strlen($Forwarded)>4){
						if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $Forwarded)){$ip=$Forwarded;}
					}	
					$this->Builsql($ip,$user,$uri,$code_error,$size,$time,$cached,null,$xtime);
					$this->InjectUserAuthDb($user,$ip,$mac,$UserAgent,$xtime);
					return true;				
			}
			
			
			if(preg_match('#MAC:(.+?)\s+(.+?)\s+.+?\s+(.*?)\s+\[.+?:(.+?)\s+.+?\]\s+".+?('.$PROTOS.')\s+(.+?)\s+HTTP.*?"\s+([0-9]+)\s+([0-9]+)\s+([A-Z_]+)#',$buffer,$re)){
				$cached=0;
				$mac=trim($re[1]);
				$ip=trim($re[2]);
				$user=$re[3];
				$time=date('H:i:s');
				if($xtime>0){$time=date('H:i:s',$xtime);}
				$uri=$re[6];
				$code_error=$re[7];
				$size=$re[8];
				$SquidCode=$re[9];
				
				if($ip=="127.0.0.1"){
					$this->error="IP = 127.0.0.1 - $uri line:".__LINE__;
					$this->events("$buffer -> ABORT in line:".__LINE__);
					$this->ToRemove=true;
					return;}
				
				if($ip=="::"){
					$this->error="IP = $ip - $uri line:".__LINE__;
					$this->events("$buffer -> ABORT in line:".__LINE__);
					$this->ToRemove=true;
					return;
				}				
				
				if($this->CACHEDORNOT($SquidCode)){$cached=1;}
				if(preg_match('#UserAgent:"(.+?)"#', $buffer,$re)){$UserAgent=$re[1];}
				if(preg_match('#Forwarded:"(.*?)"#', $buffer,$re)){$Forwarded=trim($re[1]);}
				if($Forwarded=="-"){$Forwarded=null;}
				if($Forwarded=="0.0.0.0"){$Forwarded=null;}
				if($Forwarded=="255.255.255.255"){$Forwarded=null;}
				if(strlen($Forwarded)>4){if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $Forwarded)){$ip=$Forwarded;}}
				$this->Builsql($ip,$user,$uri,$code_error,$size,$time,$cached,null,$xtime);
				$this->InjectUserAuthDb($user,$ip,$mac,$UserAgent,$xtime);
				return true;
			}
			
			$this->error="Unable to detect $buffer in line:".__LINE__." class ".__CLASS__;
			if(isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]->events("NOT PARSED --start--$buffer--end--","/var/log/squid.NotParsed.log");}
			$this->ToRemove=true;
			
			$this->events("[SQUID]:v1.1 - Not filtered: $buffer line:".__LINE__);
			return false;
	
	}

public function CACHEDORNOT($SquidCode){
	
                switch ($SquidCode) {

                               case "TCP_HIT":

                               case "TCP_REFRESH_UNMODIFIED":

                               case "TCP_REFRESH_HIT":

                               case "TCP_REFRESH_FAIL_HIT":

                               case "TCP_REFRESH_MISS":

                               case "TCP_IMS_HIT":

                               case "TCP_MEM_HIT":

                               case "TCP_DENIED":
                               	
                               case "TCP_IMS_MISS":

                               case "TCP_OFFLINE_HIT":

                               case "TCP_STALE_HIT":

                               case "TCP_ASYNC_HIT":

                               case "UDP_HIT":

                               case "UDP_DENIED":

                               case "UDP_INVALID":

                                               return TRUE;

                                               break;

                               default:

                                               return FALSE;

                                               break;

                }

}	
	private function InjectUserAgent($userAgent){
		if($GLOBALS["EnableRemoteStatisticsAppliance"]==1){return;}
		if($GLOBALS["DisableArticaProxyStatistics"]==1){return;}
		$md5=md5($userAgent);
		if(is_file("/var/log/artica-postfix/squid-userAgent/$md5.sql")){return;}
		@file_put_contents("/var/log/artica-postfix/squid-userAgent/$md5.sql", $userAgent);
	}

	
	private function InjectUserAuthDb($user,$ip,$mac,$userAgent){
		return;
		if($GLOBALS["EnableRemoteStatisticsAppliance"]==1){return;}
		if($GLOBALS["DisableArticaProxyStatistics"]==1){return;}
		if($ip=="127.0.0.1"){return;}
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		if($mac==null){$mac=$this->GetMacFromIP($ip);}
		if($user=="-"){$user=null;}
		$md5=md5("$user$ip$mac$userAgent");
		if(is_file("/var/log/artica-postfix/squid-users/$md5.sql")){return;}
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#", $ip)){$computername=$this->GetComputerName($ip);}else{$computername=$ip;}
		
		$array=array(
			"USER"=>$user,
			"IP"=>$ip,
			"MAC"=>$mac,
			"USERAGENT"=>$userAgent,
			"HOSTNAME"=>$computername,
			"MD5"=>$md5
		
		);
		$this->GLOBAL_SQUIDUSERS[]=$array;
		
		
		
	}
	
	public function errTostring($code){
		$squid_error["100"]="Continue";
		$squid_error["101"]="Switching Protocols";
		$squid_error["102"]="Processing";
		$squid_error["200"]="Pass";
		$squid_error["201"]="Created";
		$squid_error["202"]="Accepted";
		$squid_error["203"]="Non-Authoritative Information";
		$squid_error["204"]="No Content";
		$squid_error["205"]="Reset Content";
		$squid_error["206"]="Partial Content";
		$squid_error["207"]="Multi Status";
		$squid_error["300"]="Multiple Choices";
		$squid_error["301"]="Moved Permanently";
		$squid_error["302"]="Moved Temporarily";
		$squid_error["303"]="See Other";
		$squid_error["304"]="Not Modified";
		$squid_error["305"]="Use Proxy";
		$squid_error["307"]="Temporary Redirect";
		$squid_error["400"]="Bad Request";
		$squid_error["401"]="Unauthorized";
		$squid_error["402"]="Payment Required";
		$squid_error["403"]="Forbidden";
		$squid_error["404"]="Not Found";
		$squid_error["405"]="Method Not Allowed";
		$squid_error["406"]="Not Acceptable";
		$squid_error["407"]="Proxy Authentication Required";
		$squid_error["408"]="Request Timeout";
		$squid_error["409"]="Conflict";
		$squid_error["410"]="Gone";
		$squid_error["411"]="Length Required";
		$squid_error["412"]="Precondition Failed";
		$squid_error["413"]="Request Entity Too Large";
		$squid_error["414"]="Request URI Too Large";
		$squid_error["415"]="Unsupported Media Type";
		$squid_error["416"]="Request Range Not Satisfiable";
		$squid_error["417"]="Expectation Failed";
		$squid_error["424"]="Locked";
		$squid_error["424"]="Failed Dependency";
		$squid_error["433"]="Unprocessable Entity";
		$squid_error["500"]="Internal Server Error";
		$squid_error["501"]="Not Implemented";
		$squid_error["502"]="Bad Gateway";
		$squid_error["503"]="Service Unavailable";
		$squid_error["504"]="Gateway Timeout";
		$squid_error["505"]="HTTP Version Not Supported";
		$squid_error["507"]="Insufficient Storage";
		$squid_error["600"]="Squid header parsing error";
		$squid_error["000"]="Unknown";
		if(!isset($squid_error[$code])){return null;}
		return $squid_error[$code];
	}


	public function Builsql($CLIENT,$username=null,$uri,$code_error,$size=0,$time,$cached,$mac=null,$xtime=0){
			
			
			$hostname=null;
			if($GLOBALS["EnableRemoteStatisticsAppliance"]==1){
				$this->events("EnableRemoteStatisticsAppliance: RETURN!!");
				return;}
				if($GLOBALS["DisableArticaProxyStatistics"]==1){$this->events("DisableArticaProxyStatistics: RETURN!!");
				return;
			}
			$searchWords=array();
	
	if($mac=="00:00:00:00:00:00"){$mac=null;}
	
	$URLAR=parse_url($uri);
	if(isset($URLAR["host"])){$sitename=$URLAR["host"];}
	$TCPIP=new IP();
	if(!$TCPIP->isValid($CLIENT)){
		$hostname=$CLIENT;
	}
	
	
	
	if($sitename==null){if(preg_match("#^(?:[^/]+://)?([^/:]+)#",$uri,$re)){$sitename=$re[1];}}
	if(preg_match("#^www\.(.*)#", $sitename,$rz)){$sitename=$rz[1];}
	
	if(preg_match("#google\.[a-z\.]+\/search.+?q=(.+?)&#i",$uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#yahoo\..*?p=(.*?)&#i",$uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}	
	if(preg_match("#bing\..*?search\?q=(.*?)&#i",$uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#baidu\..*?\/s\?wd=(.*?)&#i",$uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#yandsearch\?text=(.*?)&#",$uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#amazon.*?field-keywords=(.*?)&#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;		}
	if(preg_match("#ebay.*?_nkw=(.*?)&#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#wikipedia\..*?search=(.*?)&#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#duckduckgo.*?q=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#ask\..*?q=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#clusty\..*?query=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#mahalo\..*?q=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#youtube\..*?query=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#deezer.*?\/search\/(.*?)($|&|\/)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#priceminister.+?\/s\/(.*?)($|&|\/)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	if(preg_match("#leboncoin.+?q=(.*?)($|&|\/)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
	
	if(!isset($searchWords["WORDS"])){
		if(preg_match("#\..*?\?(q|queryString|query|search|recherche|find|searchstring|keywords)=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[2]);$searchWords["SITENAME"]=$sitename;}
	}
	if(isset($searchWords["WORDS"])){
		if(trim($searchWords["WORDS"]=="query")){unset($searchWords["WORDS"]);}
		if(is_numeric($searchWords["WORDS"])){return;}
		$searchWords["WORDS"]=str_replace('"', "", $searchWords["WORDS"]);
		$searchWords["WORDS"]=str_replace("'", "", $searchWords["WORDS"]);
	}
	
	if($sitename==null){
		$this->events($uri." => Unable to extract domain name from ");
		$this->youtube_events(__FUNCTION__."::".__LINE__." Unable to extract domain name from `$uri`",__LINE__);
		return;
	}	
	
	if(preg_match("#^(.+?):[0-9]+#", $sitename,$ri)){$sitename=$ri[1];}
	$Country=$this->GetCountry($sitename);
	$site_IP=$this->GetIpFromName($sitename);
	
	if(preg_match("#^www\.(.+)#",$sitename,$ri)){$sitename=$ri[1];}


	
	$TYPE=$this->errTostring($code_error);
	$REASON=$TYPE;
	$CLIENT=trim($CLIENT);
	$date=date('Y-m-d')." ". $time;
	if($xtime>0){$date=date('Y-m-d H:i:s',$xtime);}
	$dateDir=date("Y-m-d-h");
	if($size==null){$size=0;}
	
	$zMD5=md5("$uri$date$CLIENT$username$TYPE$Country$site_IP");
	//$hostname=$this->GetComputerName($CLIENT);
	
	if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $sitename)){$sitename=gethostbyaddr($sitename);}	
	if(!is_dir("/var/log/artica-postfix/pagepeeker")){@mkdir("/var/log/artica-postfix/pagepeeker",600,true);}
	if(!is_dir("/var/log/artica-postfix/dansguardian-stats2/$dateDir")){@mkdir("/var/log/artica-postfix/dansguardian-stats2/$dateDir",600,true);}
	if($mac<>null){if(!$this->IsPhysicalAddress($mac)){$mac=null;}}
	
		
	if(trim($mac)<>null){
		if(!isset($GLOBALS["USERSDB"]["MACS"][$mac]["UID"])){$GLOBALS["USERSDB"]["MACS"][$mac]["UID"]=null;}
		if($GLOBALS["USERSDB"]["MACS"][$mac]["UID"]<>null){
			$this->events("Builsql(): client:$CLIENT: MAC:`$mac` user:`$username`-> {$GLOBALS["USERSDB"]["MACS"][$mac]["UID"]} (".__LINE__.")");
		}
		
		if((trim($username)==null) OR (trim($username)=="-")){
			$username=trim($GLOBALS["USERSDB"]["MACS"][$mac]["UID"]);
		}
	}
	
	
	if(strpos(" $uri", "youtube")>0){
		$VIDEOID=$this->GetYoutubeID($uri);
		if($VIDEOID<>null){
			$this->youtube_events("YOUTUBE:: $date: $CLIENT $username $mac [$VIDEOID]",__LINE__);
			if($hostname==null){$hostname=$this->GetComputerName($CLIENT);}
			$youtube=array(
					"VIDEOID"=>$VIDEOID,
					"clientip"=>$CLIENT,
					"username"=>$username,
					"time"=>$date,
					"mac"=>$mac,
					"hostname"=>$hostname
			);
			
			$CountNextYoutube=count($this->GLOBAL_YOUTUBE)+1;
			$this->youtube_events("YOUTUBE:: Youtube $CountNextYoutube Added in Array",__LINE__);
			$this->GLOBAL_YOUTUBE[]=$youtube;
			
		}
	}

	$uri=addslashes($uri);
	$Country=addslashes($Country);
	$username=addslashes($username);
	if(is_numeric($username)){$this->events_error("Username is a numeric!\nP:$CLIENT, hostname:$hostname, USER:$username, URI:$uri, REASON:$REASON, SIZE:$size, TIME:$xtime, CACHED:$cached");}
	
	$this->GLOBAL_PAGEKEEPER[$sitename]=$sitename;


	
	if($mac=="00:00:00:00:00:00"){$mac=null;}
	if(strpos($mac, ":")==0){$mac=null;}
	if(trim($username)=="-"){$username=null;}
	
	
	if(isset($GLOBALS["SIMULATE"])){
		if($GLOBALS["SIMULATE"]){
			echo "IP:$CLIENT, hostname:$hostname, USER:$username, URI:$uri, REASON:$REASON, SIZE:$size, TIME:$xtime, CACHED:$cached\n";
			unset($this->GLOBAL_PAGEKEEPER);
			unset($this->GLOBAL_YOUTUBE);
			return;
		}
	}
	
	$FINAL_ARRAY["sitename"]=$sitename;
	$FINAL_ARRAY["uri"]=$uri;
	$FINAL_ARRAY["TYPE"]=$TYPE;
	$FINAL_ARRAY["REASON"]=$REASON;
	$FINAL_ARRAY["CLIENT"]=$CLIENT;
	$FINAL_ARRAY["date"]=$date;
	$FINAL_ARRAY["zMD5"]=$zMD5;
	$FINAL_ARRAY["site_IP"]=$site_IP;
	$FINAL_ARRAY["Country"]=$Country;
	$FINAL_ARRAY["size"]=$size;
	$FINAL_ARRAY["username"]=$username;
	$FINAL_ARRAY["cached"]=$cached;
	$FINAL_ARRAY["mac"]=$mac;
	$FINAL_ARRAY["hostname"]=$hostname;
	$FINAL_ARRAY["xtime"]=$xtime;
	
	
	

	if(isset($searchWords["WORDS"])){
		if(preg_match("#gstatic\.[a-z\.]+$#",$searchWords["SITENAME"])){if(preg_match("#^tbn:.*?#", $searchWords["WORDS"])){unset($searchWords["WORDS"]);}}
	}
	
	if(isset($searchWords["WORDS"])){
		$searchWords["ipaddr"]=$CLIENT;
		if($xtime>0){$searchWords["date"]=date("Y-m-d H:i:s",$xtime);}else{$searchWords["date"]=date("Y-m-d H:i:s");}
		$searchWords["uid"]=$username;
		$searchWords["mac"]=$mac;
		$searchWords["hostname"]=$hostname;
		$this->GLOBAL_SEARCHWORDS[]=$searchWords;
	}
	
	
	$this->GLOBAL_QUEUE[]=$FINAL_ARRAY;
	if(count($GLOBALS["RTIME"])>500){unset($GLOBALS["RTIME"]);}
	$GLOBALS["RTIME"][]=array($sitename,$uri,$TYPE,$REASON,$CLIENT,$date,$zMD5,$site_IP,$Country,$size,$username,$mac);
	@file_put_contents("/etc/artica-postfix/squid-realtime.cache",base64_encode(serialize($GLOBALS["RTIME"])));
	
	
  
}

private function youtube_events($text,$line=0){
	$this->events($text);
	$common="/var/log/artica-postfix/youtube.scanner.log";
	$size=@filesize($common);
	if($size>100000){@unlink($common);}
	$pid=getmypid();
	$date=date("Y-m-d H:i:s");
	$h = @fopen($common, 'a');
	$sline="[$pid] $text";
	$line="$date [class.squid.tail.inc/$pid] $text [Line:$line]\n";
	@fwrite($h,$line);
	@fclose($h);

}

public function IsPhysicalAddress($address){
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

public function GetIpFromName($sitename){
	if(isset($GLOBALS["IPs"][$sitename])){return $GLOBALS["IPs"][$sitename];}
	$GLOBALS["IPs"][$sitename]=trim(gethostbyname($sitename));
	return $GLOBALS["IPs"][$sitename];
}

private function GetYoutubeID($url){
	$Youtuberegex[]="youtube.*?stream.*?&v=(.+?)&fmt";
	$Youtuberegex[]="youtube.*?ptracking.*?&video_id=(.+?)$";
	$Youtuberegex[]="youtube.*?user_watch.*?&video_id=(.+?)&";
	$Youtuberegex[]="youtube.*?watchtime.*?&docid=(.+?)&";
	$Youtuberegex[]="youtube\..*?\/watch\?v=(.+?)\&";	
	while (list ($index, $regex) = each ($Youtuberegex) ){
		if(preg_match("#$regex#", $url,$re)){
			$youtubeid=$re[1];
			if(strpos($youtubeid, "&")>0){
				$tt=explode("&",$youtubeid);
				$this->events("YOUTUBE: $youtubeid = " .$tt[0]);
				$youtubeid=$tt[0];
			}
			return $youtubeid;
		}
	}
	
}

public function GetCountry($sitename){
		$Country=null;
		
		if(!is_file("/usr/share/GeoIP/GeoIPCity.dat")){
			if(is_file("/usr/share/GeoIP/GeoLiteCity.dat")){
				@mkdir("/usr/share/GeoIP",0755,true);
				system("/bin/ln -s /usr/share/GeoIP/GeoLiteCity.dat /usr/share/GeoIP/GeoIPCity.dat >/dev/null 2>&1");
			}else{
				return null;
			}
		}		
		
		
		$site_IP=$this->GetIpFromName($sitename);
		if(!isset($_GET["IPs"])){$_GET["IPs"]=array();}
		if(!isset($_GET["COUNTRIES"])){$_GET["COUNTRIES"]=array();}
		if(count($_GET["IPs"])>5000){unset($_GET["IPs"]);}
		if(count($_GET["COUNTRIES"])>5000){unset($_GET["COUNTRIES"]);}
		if(isset($GLOBALS["COUNTRIES"][$site_IP])){return $GLOBALS["COUNTRIES"][$site_IP];}
		if(!function_exists("geoip_record_by_name")){$GLOBALS["COUNTRIES"][$site_IP]=null;return null;}
		if($site_IP==null){$site_IP=$sitename;}

		
		
		
		$record = @geoip_record_by_name($site_IP);
		
		if ($record) {
			$GLOBALS["COUNTRIES"][$site_IP]=$record["country_name"];;
		}else{
			$GLOBALS["COUNTRIES"][$site_IP]=null;
		}
		
	return $GLOBALS["COUNTRIES"][$site_IP];
	
}

	private function GetMacFromIP($ipaddr){
		return null;
		$ipaddr=trim($ipaddr);
		$ttl=date('YmdH');
		if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
		if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}
		
		if(!isset($GLOBALS["SBIN_ARP"])){$unix=new unix();$GLOBALS["SBIN_ARP"]=$unix->find_program("arp");}
		if(!isset($GLOBALS["SBIN_ARPING"])){$unix=new unix();$GLOBALS["SBIN_ARPING"]=$unix->find_program("arping");}
		
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
		if(!isset($GLOBALS["SBIN_PING"])){$unix=new unix();$GLOBALS["SBIN_PING"]=$unix->find_program("ping");}
		if(!isset($GLOBALS["SBIN_NOHUP"])){$unix=new unix();$GLOBALS["SBIN_NOHUP"]=$unix->find_program("nohup");}
		
		
		
		
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
		$this->events("$ipaddr not found (".__LINE__.")");
		if(!isset($GLOBALS["PINGEDHOSTS"][$ipaddr])){
			shell_exec("{$GLOBALS["SBIN_NOHUP"]} {$GLOBALS["SBIN_PING"]} $ipaddr -c 3 >/dev/null 2>&1 &");
			$GLOBALS["PINGEDHOSTS"][$ipaddr]=true;
		}
		
		$GLOBALS["CACHEARP"][$ttl][$ipaddr]=null;
			
		
	}
	

	
	
	private function __IsPhysicalAddress($address){
	
		$address=strtoupper(trim($address));
		if($address=="UNKNOWN"){return null;}
		if($address=="00:00:00:00:00:00"){return false;}
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
	
	
	
	
	public function ArrayToMysql($FINAL_ARRAY,$time=0){
		if(!isset($GLOBALS["TCP_CLASS"])){$GLOBALS["TCP_CLASS"]=new IP();}
		if(!isset($GLOBALS["CLASS_SQUID_TAIL"])){$GLOBALS["CLASS_SQUID_TAIL"]=new squid_tail();}
		if(!isset($GLOBALS["Q"])){$GLOBALS["Q"]=new mysql_squid_builder();}
		

		if(!is_numeric($time)){$time=time();}
		if($time==0){$time=time();}
		$tablehour="squidhour_". date("YmdH",$time);
	
		if(is_array($FINAL_ARRAY)){
			if(!isset($FINAL_ARRAY["xtime"])){$FINAL_ARRAY["xtime"]=0;}
			if($FINAL_ARRAY["xtime"]>0){$tablehour="squidhour_". date("YmdH",$FINAL_ARRAY["xtime"]);}
		}
	
		if(!is_array($FINAL_ARRAY)){
			$this->events("ArrayToMysql():: not an array() !!!");
			return null;
		}
		$zMD5=md5(serialize($FINAL_ARRAY));
	
	
	
		while (list ($key, $val) = each ($FINAL_ARRAY) ){
			
			$val=str_replace("'", "`", $val);
			$val=stripslashes($val);
			$FINAL_ARRAY[$key]=mysql_escape_string2($val);
		}
		
	
		$sitename=$FINAL_ARRAY["sitename"];
		$uri=$FINAL_ARRAY["uri"];
		$TYPE=$FINAL_ARRAY["TYPE"];
		$REASON=$FINAL_ARRAY["REASON"];
		$CLIENT=$FINAL_ARRAY["CLIENT"];
		$date=$FINAL_ARRAY["date"];
		$site_IP=$FINAL_ARRAY["site_IP"];
		$Country=$this->GetCountry($sitename);
		$size=$FINAL_ARRAY["size"];
		$username=$FINAL_ARRAY["username"];
		$username_ORG=$FINAL_ARRAY["username"];
		$cached=$FINAL_ARRAY["cached"];
		$mac=$FINAL_ARRAY["mac"];
		$hostname=$FINAL_ARRAY["hostname"];
		
		
	
		if(!$this->__IsPhysicalAddress($mac)){$mac=null;}
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		if($mac==null){$mac=$this->GetMacFromIP($CLIENT);}
		if($username=="-"){$username=null;}
	
		if($mac<>null){if($username==null){$username=$GLOBALS["Q"]->UID_FROM_MAC($mac);}}
		if($CLIENT<>null){if($username==null){$username=$GLOBALS["Q"]->UID_FROM_IP($CLIENT);}}
		if($hostname==null){
			if($GLOBALS["TCP_CLASS"]->isValid($CLIENT)){
				$hostname=$GLOBALS["CLASS_SQUID_TAIL"]->GetComputerName($CLIENT);
			}
		}
		
		
		if(strlen($username)<3){$username=null;}
		if($CLIENT<>null){if($username==null){$username=$GLOBALS["Q"]->UID_FROM_IP($CLIENT);}}
	
	
		if(strlen($hostname)<3){
			$this->events("Fatal: bad hostname `$hostname` client=`$CLIENT`");
			$hostname=null;
			$hostname=$this->GetComputerName($CLIENT);
			$this->events("Fatal: New hostname `$hostname` client=`$CLIENT`");
				
		}
		if(strlen($hostname)<3){$hostname=null;}
		
		$username=$this->CACHE_CLIENT($CLIENT,$mac,$hostname,$username);

		
		if(is_numeric($username)){
			$this->events_error("ArrayToMysql(): username ($username_ORG) is a numeric-> $username");
		}
		
		if($GLOBALS['FORCE']){
			echo "$date $sitename {$CLIENT}[$hostname]} $username\n";
		}
		
		$sql="('$sitename','$uri','$TYPE','$REASON','$CLIENT','$date','$zMD5','$site_IP','$Country','$size','$username','$cached','$mac','$hostname')";
		return array($tablehour,$sql);
	}	

	private function CACHE_CLIENT($CLIENT,$mac=null,$hostname=null,$username=null){
		if(!$GLOBALS["TCP_CLASS"]->isValid($CLIENT)){$CLIENT=null;}
		if(!$GLOBALS["TCP_CLASS"]->IsvalidMAC($mac)){$mac=null;}
		if($username==null){if($CLIENT==null){if($mac==null){if($hostname<>null){return;}}}}
		
		if($username<>null){
			if($CLIENT<>null){$GLOBALS["USERSCACHE"][$CLIENT]=$username;}
			if($mac<>null){$GLOBALS["USERSCACHE"][$mac]=$username;}
			if($hostname<>null){$GLOBALS["USERSCACHE"][$hostname]=$username;}
			return $username;
		}
		if($username==null){
			
			if($mac<>null){
				if(isset($GLOBALS["USERSCACHE"][$mac])){
					if($GLOBALS["USERSCACHE"][$mac]<>null){$username=$GLOBALS["USERSCACHE"][$mac];}
					return $username;
				}
			}
			
			if($hostname<>null){
				if(isset($GLOBALS["USERSCACHE"][$hostname])){
					if($GLOBALS["USERSCACHE"][$hostname]<>null){$username=$GLOBALS["USERSCACHE"][$hostname];}
					return $username;
				}
			}
			
			
			if($CLIENT<>null){
				if(isset($GLOBALS["USERSCACHE"][$CLIENT])){
					if($GLOBALS["USERSCACHE"][$CLIENT]<>null){$username=$GLOBALS["USERSCACHE"][$CLIENT];}
					return $username;
				}
			}
			
		}
		
	}

	private function events($text){
			$pid=@getmypid();
			$date=@date("H:i:s");
			$logFile="/var/log/artica-postfix/auth-tail.debug";
			$size=@filesize($logFile);
			if($size>1000000){@unlink($logFile);}
			$f = @fopen($logFile, 'a');
			@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
			@fclose($f);	
	}
	
	public function events_error($text){
		$pid=@getmypid();
		$date=@date("H:i:s");
		
		if(isset($GLOBALS["BUFFER_ANALYZED"])){
			if(strlen($GLOBALS["BUFFER_ANALYZED"])>5){
				$text=$text."Buffer\n************************{$GLOBALS["BUFFER_ANALYZED"]}\n";
			}
		}
		

		if(isset($GLOBALS["BUFFER_FILE_ANALYZED"])){
			if(strlen($GLOBALS["BUFFER_FILE_ANALYZED"])>5){
				$text=$text."File\n************************{$GLOBALS["BUFFER_FILE_ANALYZED"]}\n";
			}
		}		
		
		
		
		$logFile="/var/log/artica-postfix/squid-parse-error.debug";
		$size=@filesize($logFile);
		if($size>9000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
		@fclose($f);		
		
	}
			
	public function GetComputerName($ip){
		if(!$GLOBALS["TCP_CLASS"]->isIPAddress($ip)){return;}
		$this->LoadResolveCache();
		if(isset($GLOBALS["resvip"][$ip])){
			if(strlen($GLOBALS["resvip"][$ip])>3){
					return $GLOBALS["resvip"][$ip];
			}
		}
		
		$name=gethostbyaddr($ip);
		$GLOBALS["resvip"][$ip]=$name;
		$this->SaveResolveCache();
		return $name;
	}
	
	private function LoadResolveCache(){
		if(isset($GLOBALS["resvip"])){return;}
		$unix=new unix();
		$fname="/etc/squid3/ipcaches.db";
		if($unix->file_time_min($fname)>15){$GLOBALS["resvip"]=array();}
		$GLOBALS["resvip"]=unserialize(@file_get_contents($fname));
		
	}
	
	private function SaveResolveCache(){
		$fname="/etc/squid3/ipcaches.db";
		@file_put_contents($fname, serialize($GLOBALS["resvip"]));
	}
	
	
	private function GeoIPavailable(){
		if(!function_exists("geoip_db_filename")){
			$this->events('geoip_db_filename not available...');
			return;
		
		}
	
		$cst = array(
		             'GEOIP_COUNTRY_EDITION' => GEOIP_COUNTRY_EDITION,
		             'GEOIP_REGION_EDITION_REV0' => GEOIP_REGION_EDITION_REV0,
		             'GEOIP_CITY_EDITION_REV0' => GEOIP_CITY_EDITION_REV0,
		             'GEOIP_ORG_EDITION' => GEOIP_ORG_EDITION,
		             'GEOIP_ISP_EDITION' => GEOIP_ISP_EDITION,
		             'GEOIP_CITY_EDITION_REV1' => GEOIP_CITY_EDITION_REV1,
		             'GEOIP_REGION_EDITION_REV1' => GEOIP_REGION_EDITION_REV1,
		             'GEOIP_PROXY_EDITION' => GEOIP_PROXY_EDITION,
		             'GEOIP_ASNUM_EDITION' => GEOIP_ASNUM_EDITION,
		             'GEOIP_NETSPEED_EDITION' => GEOIP_NETSPEED_EDITION,
		             'GEOIP_DOMAIN_EDITION' => GEOIP_DOMAIN_EDITION,
		             );
		
		foreach ($cst as $k=>$v) {
		    $this->events($k.': '.geoip_db_filename($v).'  '.(geoip_db_avail($v) ? 'Available':''));
		}	
	}		
	
}

