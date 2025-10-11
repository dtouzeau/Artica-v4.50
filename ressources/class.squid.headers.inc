<?php

class squid_headers{
	
	private $SquidUrgency=0;
	private $SquidCacheLevel=0;
	private $SQUIDACLsEnabled=0;
	private $SquidCachesProxyEnabled=0;
	private $DisableAnyCache=0;
	private $SquidAddVersion=0;
	private $AllowSquidHSTS=0;
	private $AllowSquidOtherProtocols=0;
	private $acls_array=array();
	
	public function __construct(){
		$this->SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
		$this->SquidCacheLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel"));
		$this->SQUIDACLsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDACLsEnabled"));
		$this->SquidCachesProxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled"));
		$this->DisableAnyCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableAnyCache"));
		if(!is_numeric($this->SquidCacheLevel)){$this->SquidCacheLevel=3;}
		if($this->SquidCachesProxyEnabled==0){$this->SquidCacheLevel=0;}
		if($this->DisableAnyCache==1){$this->SquidCacheLevel=0;}
		$this->SquidAddVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAddVersion"));
		$this->AllowSquidHSTS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidHSTS"));
		$this->AllowSquidOtherProtocols=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowSquidOtherProtocols"));
		
	}
	
	public function build(){
		if($this->SquidUrgency==1){
			$this->acls_array[]="# Proxy is in emergency mode!";
			@file_put_contents("/etc/squid3/acls_headers.conf", @implode("\n", $this->acls_array));
			return;
		}

        $this->acls_array[]="#Normalize Accept-Encoding to support compression via eCAP";
        $this->acls_array[]="request_header_access Accept-Encoding deny all";
        $this->acls_array[]="request_header_replace Accept-Encoding gzip;q=1.0, identity;q=0.5, *;q=0";
        $this->acls_array[]="# Remove User-Agent from Vary";
        $this->acls_array[]="reply_header_access Vary deny all";
        $this->acls_array[]="reply_header_replace Vary Accept-Encoding";
		
		$this->access_rules();
		
		$this->acls_array[]="# --------- CACHES RULES SquidCacheLevel:$this->SquidCacheLevel [".__LINE__."]\n";
		$this->acls_array[]="# --------- ALCS Features Enabled:$this->SQUIDACLsEnabled [".__LINE__."]\n";
		
		if($this->SquidCacheLevel>2){
			$this->acls_array[]="reply_header_access Cache-Control deny all\n";
		}

		if($this->SquidCacheLevel>3){
			$this->acls_array[]="reply_header_access Content-Security-Policy deny all\n";
			$this->acls_array[]="reply_header_access ETag deny all\n";
			
		}
		
		if($this->SquidAddVersion==0){
			$this->acls_array[]="reply_header_access Server deny all";
		}
		
		if($this->AllowSquidOtherProtocols==0){
			$this->acls_array[]="#--------- Wont push the client to use udp 443 or udp 80";
			$this->acls_array[]="reply_header_access Alternate-Protocol deny all";
		
		}
		if($this->AllowSquidHSTS==0){
			$this->acls_array[]="#--------- Wont push the client to use HSTS sent by the web site";
			$this->acls_array[]="reply_header_access Strict-Transport-Security deny all";
            $this->acls_array[]="reply_header_replace Strict-Transport-Security max-age=0; includeSubDomains";

		}
		
		
		@file_put_contents("/etc/squid3/acls_headers.conf", @implode("\n", $this->acls_array));
		
	}
	
	private function access_rules(){
		if($this->SQUIDACLsEnabled==0){
			$this->acls_array[]="# --------- ACLs Features disabled [".__LINE__."]\n";
			return;
		}
		
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$results=$q->QUERY_SQL("SELECT * FROM squid_http_headers_acls WHERE enabled=1 ORDER BY zorder");
		
		
		$squid_acls_groups=new squid_acls_groups();
		$array_access_replace["request_header_replace"]=true;
		$array_access_replace["reply_header_replace"]=true;
		

		
		foreach ($results as $index=>$ligne){
			$aclport=$ligne["aclport"];
			$PortGroup=null;
			$ztype=$ligne["ztype"];
			$headername=$ligne["headername"];
			$headervalue=base64_decode(trim($ligne["headervalue"]));
			$rulename=$ligne["rulename"];
			if($aclport>0){$PortGroup=" MyPort{$aclport}";}
            $array_access_ad["request_header_add"]="request_header_add $headername";
			$array_access_ad["request_header_allow"]="request_header_access $headername allow";
			$array_access_ad["request_header_deny"]="request_header_access $headername deny";
            $array_access_ad["reply_header_add"]="reply_header_add $headername";
			$array_access_ad["reply_header_allow"]="reply_header_access $headername allow";
			$array_access_ad["reply_header_deny"]="reply_header_access $headername deny";
			
			
			$aclid=$ligne["ID"];
			$acls_array=$squid_acls_groups->buildacls_bytype_items($aclid,false,"squid_http_headers_link");
			
			$this->acls_array[]="#\t$aclid] $rulename ". count($acls_array). " items";
			if(count($acls_array)==0){$acls_array[]="all";}
			
			if(isset($array_access_ad[$ztype])){

                if($headername=="X-GoogApps-Allowed-Domains"){
                    $val="";
                    if($headervalue<>null){
                        $val="\"$headervalue\"";
                    }
                    $this->acls_array[]="{$array_access_ad[$ztype]}{$PortGroup} $val ".@implode(" ", $acls_array);

                }
                else {
                    $this->acls_array[]="{$array_access_ad[$ztype]}{$PortGroup} ".@implode(" ", $acls_array);

                }
				continue;
			}
			
			if($ztype=="request_header_replace"){
				$this->acls_array[]="request_header_access $headername deny{$PortGroup} ".@implode(" ", $acls_array);
				if($headervalue<>null){$this->acls_array[]="request_header_replace $headername $headervalue";}
				continue;
			}
			if($ztype=="reply_header_replace"){
				$this->acls_array[]="reply_header_access $headername deny{$PortGroup} ".@implode(" ", $acls_array);
				if($headervalue<>null){$this->acls_array[]="reply_header_replace $headername $headervalue";}
				continue;
			}			
		}
		
		
		
		
	}
	
	
}