<?php
include_once(dirname(__FILE__)."/class.squid.acls.inc");
include_once(dirname(__FILE__)."/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/class.http_access_defaults.inc");

class squid_access_manager{
	private $acls;
    private $isSquid5=false;
	private $SquidUrgency=0;
	private $SquidVersion="";
	
	public function __construct(){
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}

        $this->SquidVersion=$this->GET_SQUID_VERSION();
        if(preg_match("#^[567]\.#",$this->SquidVersion)){$this->isSquid5=true;}
		
		
		if(!is_file("/etc/artica-postfix/settings/Daemons/AllowWindowsUpdates")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AllowWindowsUpdates", 1);}
		if(is_file("/etc/artica-postfix/settings/Daemons/SquidUrgency")){
			$this->SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
		}
		$this->acls=new squid_acls();
		$this->acls->Build_Acls();
	
	}
    private function GET_SQUID_VERSION():string{
        if(isset($GLOBALS["GET_SQUID_VERSION"])){return $GLOBALS["GET_SQUID_VERSION"];}
        exec("/usr/sbin/squid -v 2>&1",$results);
        foreach ($results as $line){
            if(preg_match("#Squid Cache: Version\s+([0-9\.]+)#",$line,$re)){
                $GLOBALS["GET_SQUID_VERSION"]=$re[1];
                return $GLOBALS["GET_SQUID_VERSION"];
            }
        }
        $GLOBALS["GET_SQUID_VERSION"]=strval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion"));
        return $GLOBALS["GET_SQUID_VERSION"];
    }
	
	
	function build_all():bool{
		$this->deny_global();
		$this->allow_auth();
		$this->url_rewrite_access();
		return true;
	}


	
	

	
	
	function deny_global():bool{
		$f=array();
		$f[]="acl DnsIPfake dst 127.0.0.3";
		$f[]="http_access deny DnsIPfake all";
		$f[]="";
		
		
		if($this->SquidUrgency){
			$f[]="#Emergency mode !!! ";
			@file_put_contents("/etc/squid3/GlobalAccessManager_deny.conf", @implode("\n",$f));
			return true;
		}
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$this->acl_extensions();
		$head1=null;
		$sql="SELECT *  FROM `global_whitelist` WHERE enabled=1 and deny_global=1";
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";}
		$f[]="# ". date("Y-md-H:i:s");
		$f[]="# Deny auth ".count($results)." rules";
		
		foreach ($results as $index=>$ligne){
			$acls_name="Group{$ligne["groupid"]}";
			if(!$this->acls->availableGroups[$ligne["groupid"]]){
				$f[]="# [$index] Group{$ligne["groupid"]} unavailable For rule {$ligne["groupid"]}";
				continue;}
			$f[]="http_access deny $acls_name all";
				
		}
		
		
		$sql="SELECT *  FROM `global_whitelist` WHERE enabled=1 and deny_ext=1";
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";}
		$ext=0;
		foreach ($results as $index=>$ligne){
			$acls_name="Group{$ligne["groupid"]}";
			if(!$this->acls->availableGroups[$ligne["groupid"]]){continue;}
			$ext++;
			$f[]="http_access deny $acls_name BlockDangerousFiles all";
		
		}		

		
		if($ext>0){$head1="acl BlockDangerousFiles urlpath_regex -i \"/etc/squid3/dangerous_extensions.conf\"\n"; }
		
		
		@file_put_contents("/etc/squid3/GlobalAccessManager_deny.conf", $head1.@implode("\n",$f). "\n");
        return true;
	}
	
	
	function acl_extensions(){
		$cachefile="/etc/squid3/dangerous_extensions.conf";
		$extz[]="exe";
		$extz[]="pif";
		$extz[]="application";
		$extz[]="gadget";
		$extz[]="msi";
		$extz[]="msu";
		$extz[]="msp";
		$extz[]="com";
		$extz[]="scr";
		$extz[]="hta";
		$extz[]="cpl";
		$extz[]="msc";
		$extz[]="bat";
		$extz[]="cmd";
		$extz[]="vb";
		$extz[]="vbs";
		$extz[]="vbe";
		$extz[]="jse";
		$extz[]="ws";
		$extz[]="wsf";
		$extz[]="wsc";
		$extz[]="wsh";
		$extz[]="ps1";
		$extz[]="ps1xml";
		$extz[]="ps2";
		$extz[]="ps2xml";
		$extz[]="psc1";
		$extz[]="psc2";
		$extz[]="msh";
		$extz[]="msh1";
		$extz[]="msh2";
		$extz[]="mshxml";
		$extz[]="msh2xml";
		$extz[]="scf";
		$extz[]="lnk";
		$extz[]="inf";
		$extz[]="reg";

		foreach ($extz as $ext){
			$f[]="\.$ext(\?.*)?$";
		}
		$f[]="";
		@file_put_contents("$cachefile", @implode("\n", $f));
		
	}
	
	function deny_ufdb():bool{
		$cachefile="/etc/squid3/GlobalAccessManager_url_rewrite.conf";
        if(!is_file($cachefile)){@touch($cachefile);}
    	return true;
	}
	

	
	private function url_rewrite_access():bool{
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $results = $q->QUERY_SQL("SELECT * FROM squid_url_rewrite_acls WHERE enabled=1 ORDER BY zorder");
        $anotates=array();
        $HEADERS=array();
        $squid_acls_groups = new squid_acls_groups();
        $array_access["url_rewrite_access_deny"] = "url_rewrite_access deny";
        $array_access["url_rewrite_access_allow"] = "url_rewrite_access allow";
        $conf[] = "#\tACLS " . count($results) . " Policies";

        foreach ($results as $ligne) {
            $aclport = $ligne["aclport"];
            $PortGroup = null;
            $ztype = $ligne["ztype"];
            $rulename = $ligne["rulename"];
            if ($aclport > 0) { $PortGroup = " MyPort1"; }
            $aclid = $ligne["ID"];
            $annotate_transaction=null;

            $acls_array = $squid_acls_groups->buildacls_bytype_items($aclid, false, "squid_url_rewrite_link");
            $conf[] = "#\t$aclid] $rulename " . count($acls_array) . " items";
            if (count($acls_array) == 0) {continue;}

            if($this->isSquid5) {
                $annotate_transaction = " AnnotateFinalUFDB$aclid";
                $anotates[] = "acl AnnotateFinalUFDB$aclid annotate_transaction webfilterpolicy=$aclid";
            }


            $HEADERS[]="# ENABLED_RULE=$aclid";
            $conf[] = "$array_access[$ztype]$PortGroup " . @implode(" ", $acls_array)." $annotate_transaction";
        }
        $conf[]="url_rewrite_access allow all";
        $FINAL[]=@implode("\n",$HEADERS);
        $FINAL[]=@implode("\n",$anotates);
        $FINAL[]=@implode("\n",$conf);
        @file_put_contents("/etc/squid3/url_rewrite_access_admin.conf", @implode("\n", $FINAL)."\n");
		return true;
	
	}




	function allow_auth(){



        $f=array();
		
		if($this->SquidUrgency==1){
			@file_put_contents("/etc/squid3/GlobalAccessManager_auth.conf", "# Emergency mode!\n");
			return;
		}
		
		$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
		$sql="SELECT *  FROM `global_whitelist` WHERE enabled=1 and deny_auth=1";
		$results = $q->QUERY_SQL($sql);
		
		if(count($results)>0){
			foreach ($results as $index=>$ligne){
				$acls_name="Group{$ligne["groupid"]}";
				if(!$this->acls->availableGroups[$ligne["groupid"]]){continue;}
				$f[]="http_access allow $acls_name";
				
			}
		
		}
		
		@file_put_contents("/etc/squid3/GlobalAccessManager_auth.conf", @implode("\n",$f). "\n");
		
	}
	

	
}