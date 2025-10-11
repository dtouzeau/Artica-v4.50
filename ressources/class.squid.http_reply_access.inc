<?php

class http_reply_access{
	
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
			@file_put_contents("/etc/squid3/http_reply_access.conf", @implode("\n", $this->acls_array));
			return;
		}
		
		$this->access_rules();
	    @file_put_contents("/etc/squid3/http_reply_access.conf", @implode("\n", $this->acls_array));
		
	}
	
	private function access_rules(){
		if($this->SQUIDACLsEnabled==0){
			$this->acls_array[]="# --------- ACLs Features disabled [".__LINE__."]\n";
			return;
		}
		
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$results=$q->QUERY_SQL("SELECT * FROM http_reply_access WHERE enabled=1 ORDER BY zorder");
		$squid_acls_groups=new squid_acls_groups();

		foreach ($results as $index=>$ligne){
			$allow=$ligne["allow"];
            $rulename=$ligne["rulename"];
			$aclid=$ligne["ID"];
			$acls_array=$squid_acls_groups->buildacls_bytype_items($aclid,false,"http_reply_access_links");
            $this->acls_array[]=@implode("\n",$squid_acls_groups->HLOGS);
			$this->acls_array[]="#\t$aclid] $rulename ". count($acls_array). " items";
			if(count($acls_array)==0){continue;}
			$final[0]="allow";
            $final[1]="deny";
            $this->acls_array[]="http_reply_access {$final[$allow]} ".@implode(" ", $acls_array);

		}


        $this->acls_array[]="";
		
	}
	
	
}