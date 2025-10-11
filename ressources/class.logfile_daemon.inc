<?php
// P 125
class logfile_daemon{
	public $CODE_DEST=array();
	
	public function __construct(){
		$this->CODE_DEST["HIER_NONE"]="failed";
		$this->CODE_DEST["HIER_DIRECT"]="Direct";
		$this->CODE_DEST["ANY_OLD_PARENT"]="Parent";
		$this->CODE_DEST["NONE"]="Direct";
		$this->CODE_DEST["DIRECT"]="Direct";
		$this->CODE_DEST["SIBLING_HIT"]="Parent";
		$this->CODE_DEST["PARENT_HIT"]="Parent";
		$this->CODE_DEST["DEFAULT_PARENT"]="Parent";
		$this->CODE_DEST["SINGLE_PARENT"]="Parent";
		$this->CODE_DEST["FIRST_UP_PARENT"]="Parent";
		$this->CODE_DEST["NO_PARENT_DIRECT"]="Direct";
		$this->CODE_DEST["FIRST_PARENT_MISS"]="Parent";
		$this->CODE_DEST["CLOSEST_PARENT_MISS"]="Parent";
		$this->CODE_DEST["CLOSEST_PARENT"]="Parent";
		$this->CODE_DEST["CLOSEST_DIRECT"]="Direct";
		$this->CODE_DEST["NO_DIRECT_FAIL "]="Firewall issue";
		$this->CODE_DEST["SOURCE_FASTEST"]="Direct";
		$this->CODE_DEST["ROUNDROBIN_PARENT"]="Parent";
		$this->CODE_DEST["CACHE_DIGEST_HIT"]="Parent";
		$this->CODE_DEST["CD_PARENT_HIT"]="Parent";
		$this->CODE_DEST["CD_SIBLING_HIT"]="Parent";
		$this->CODE_DEST["NO_CACHE_DIGEST_DIRECT"]="Direct";
		$this->CODE_DEST["CARP"]="Direct";
		$this->CODE_DEST["PINNED"]="Direct";
		$this->CODE_DEST["ORIGINAL_DST"]="Direct MIT";
		$this->CODE_DEST["ANY_PARENT"]="Parent";
		
	}
	
	public function GetYoutubeID($url){
		$Youtuberegex[]="youtube.*?stream.*?&v=(.+?)&fmt";
		$Youtuberegex[]="youtube.*?ptracking.*?&video_id=(.+?)$";
		$Youtuberegex[]="youtube.*?user_watch.*?&video_id=(.+?)&";
		$Youtuberegex[]="youtube.com.*?set_awesome.*?&video_id=(.+?)&";
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
	
	private function events($text){
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/squid/logfile_daemon.debug";
	
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$pid `$text`\n");
		@fclose($f);
	}
	
		
	public function SquidCodeToText($code){
		$code=str_replace("TAG_NONE_ABORTED","Splice aborted",$code);
		$code=str_replace("TAG_NONE_TIMEDOUT","Timed Out",$code);
		$code=str_replace("TAG_NONE","Spliced",$code);
		$code=str_replace("TCP_REFRESH_UNMODIFIED","Not Modified",$code);
		$code=str_replace("TCP_HIT","Cached in disk",$code);
		$code=str_replace("TCP_MISS_ABORTED","Not cached aborted",$code);
		$code=str_replace("TCP_MISS","Not cached",$code);
		$code=str_replace("TCP_CLIENT_REFRESH_MISS","Not cached",$code);
		$code=str_replace("TCP_MEM_HIT","Cached in memory",$code);
        $code=str_replace("TCP_REDIRECT", "Redirect",$code);
		return $code;
	}
	
	public function SearchWords($uri){
		$sitename=null;
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
			$searchWords["WORDS"]=str_replace('"', "`", $searchWords["WORDS"]);
			$searchWords["WORDS"]=str_replace("'", "`", $searchWords["WORDS"]);
		}
		
		if(isset($searchWords["WORDS"])){
			if(preg_match("#gstatic\.[a-z\.]+$#",$searchWords["SITENAME"])){if(preg_match("#^tbn:.*?#", $searchWords["WORDS"])){unset($searchWords["WORDS"]);}}
		}
		
		if(!isset($searchWords["WORDS"])){return null;}
		return $searchWords;
		
		
	}
	
	public function parseURL($URL,$PROTO,$CATEGORIES,$nolink=false){
		include_once(dirname(__FILE__)."/class.squid.familysites.inc");
		include_once(dirname(__FILE__)."/class.mysql.catz.inc");
		include_once(dirname(__FILE__)."/class.tcpip.inc");
		$scheme=null;
		$hostname=null;
		$path=null;
		$prefix=null;
        $URL=trim(strtolower($URL));
		$parse=parse_url($URL);
        if(!isset($parse["host"])){
            if(preg_match("#^(http|https|ftp|ftps):\/\/(.*?)(\/|$)#",$URL,$re)){
                $parse["host"]=$re[2];
            }else{
                $parse["host"]=$URL;
            }
        }


		$PROTO=trim($PROTO);
		if($PROTO=="CONNECT"){$PROTO="SSL";$scheme="https";$parse["scheme"]="https";}
		if($PROTO=="SSL"){$PROTO="SSL";$scheme="https";$parse["scheme"]="https";}
		$IP=new IP();$catz=new mysql_catz();
		$zcat=new squid_familysite();
		$SOURCE_URL=$URL;
		$GET_URL=$SOURCE_URL;
		$port=null;
		$query=null;
		$infos=null;
		
		if(!isset($parse["scheme"])){
			if($PROTO=="SSL"){
				$GET_URL="https://$SOURCE_URL";
				$parse["scheme"]="https";
			}
		}else{
			if(!preg_match("#^(http|ftp)#", $SOURCE_URL)){$SOURCE_URL="https://$SOURCE_URL";$parse["scheme"]="https";}
			$GET_URL=$SOURCE_URL;
		}
		
		if($scheme==null){if(isset($parse["scheme"])){$scheme=$parse["scheme"]; }}
		if(isset($parse["host"])){$hostname=$parse["host"];}

        if(function_exists("VERBOSE")){
            VERBOSE("$URL hostname=[$hostname]",__LINE__);
        }

		if(preg_match("#(.+?):([0-9]+)#", $hostname,$re)){
			$hostname=$re[1];
			$port=$re[2];
		}
		if($IP->isValid($hostname)){
			$parse["query"]=null;
			$parse["path"]=null;
				
			$TT=explode(".",$hostname);
			$net=$TT[0].".".$TT[1].".".$TT[2];
			$infos="&nbsp;(<a href=\"http://www.tcpiputils.com/browse/ip-address/$hostname\" style='text-decoration:underline;color:black' target=_new>TCP Utils</a>&nbsp;|&nbsp<a href=\"https://db-ip.com/all/$net\" style='text-decoration:underline;color:%color%' target=_new>Subnet</a>)";
		}
		

		$familysite=$zcat->GetFamilySites($hostname);
		$familysite=str_replace("'", "`", $familysite);
        if(!is_null($scheme)){$scheme=trim($scheme);}
		if(is_null($scheme)){$scheme="https";}
		
			$URL="$scheme://";
            $category=0;
			if($prefix<>null){$URL=$URL."$prefix.";}
			$URL=$URL."$familysite";
			if($port<>null){$URL=$URL.":$port";}
			if($CATEGORIES){
				$category=$catz->GET_CATEGORIES($hostname);
			}
			$tpl=new template_admin();
			$link=$tpl->icon_link(1,"s_PopUpFull('$GET_URL','1024','900');");
			

			
			return array(
			    "GET_URL"=>$GET_URL,"HOSTNAME"=>$hostname,
				"URL"=>$URL,
				"FAMILYSITE"=>$familysite,
				"CATEGORY"=>$category,"INFOS"=>$infos,"LINK"=>$link
					);
		
			
			
			
		
		
		
	}
	
	
	public function codeToString($code,$return_array=false){
		if(!$return_array){if($code==null){return "";}}
		$squid_error["000"]="";
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
		$squid_error["407"]="Authentication Required";
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
		$squid_error["444"]="No response";
		$squid_error["433"]="Unprocessable Entity";
		$squid_error["500"]="Internal Server Error";
		$squid_error["501"]="Not Implemented";
		$squid_error["502"]="Bad Gateway";
		$squid_error["503"]="Service Unavailable";
		$squid_error["504"]="Gateway Timeout";
		$squid_error["505"]="HTTP Version Not Supported";
		$squid_error["507"]="Insufficient Storage";
		$squid_error["600"]="Squid header parsing error";
        $squid_error["499"]="Client closed request";
        if($return_array){return $squid_error;}
		return $squid_error[$code];
	}
	
	
	
	
	public function CACHEDORNOT($SquidCode){
		if(preg_match("#(.+?):(.+)#", $SquidCode,$re)){
			$SquidCode=$re[1];
		}
		$SquidCode=strtoupper($SquidCode);
		
		
		$HITS["TCP_ASYNC_HIT"]=true;
		$HITS["TCP_DENIED"]=true;
		$HITS["TCP_HIT"]=true;
		$HITS["TCP_IMS_HIT"]=true;
		$HITS["TCP_MEM_HIT"]=true;
		$HITS["TCP_OFFLINE_HIT"]=true;
		$HITS["TCP_MISS_ABORTED"]=true;
		$HITS["TCP_OFFLINE_HIT"]=true;
		$HITS["TCP_REDIRECT"]=true;
		$HITS["TCP_REFRESH_FAIL_HIT"]=true;
		$HITS["TCP_REFRESH_HIT"]=true;
		$HITS["TCP_REFRESH_MISS"]=true;
		$HITS["TCP_REFRESH_UNMODIFIED"]=true;
		$HITS["TAG_NONE"]=true;
		$HITS["TCP_STALE_HIT"]=true;
		$HITS["UDP_HIT"]=true;
		$HITS["UDP_DENIED"]=true;
		$HITS["UDP_INVALID"]=true;
		
		if(isset($HITS[$SquidCode])){return 1;}
		if(preg_match("#_HIT$#", $SquidCode)){return 1;}
		
		switch ($SquidCode) {
	
			case "TCP_HIT":
	
			case "TCP_REFRESH_UNMODIFIED":
				
			case "TCP_REFRESH_HIT":
	
			case "TCP_REFRESH_FAIL_HIT":
	
			case "TCP_REFRESH_MISS":
	
			case "TCP_IMS_HIT":
	
			case "TCP_MEM_HIT":
				
			case "TCP_HIT_ABORTED":
	
			case "TCP_IMS_MISS":
	
			case "TCP_OFFLINE_HIT":
	
			case "TCP_STALE_HIT":
	
			case "TCP_ASYNC_HIT":
	
			case "UDP_HIT":
	
			case "UDP_DENIED":
	
			case "UDP_INVALID":
	
				return 1;
	
				break;
	
			default:
	
				return 0;
	
				break;
	
		}
	
	}
	
}