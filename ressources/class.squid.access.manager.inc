<?php
include_once(dirname(__FILE__)."/settings.exists.inc"); // artica_setting_exists(): settings are root-only, see the file
include_once(dirname(__FILE__)."/class.squid.acls.inc");
include_once(dirname(__FILE__)."/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/class.http_access_defaults.inc");
include_once(dirname(__FILE__)."/class.acls.backend.inc");

class squid_access_manager{
	private $acls;
    private $isSquid5=false;
	private $SquidUrgency=0;
	private $SquidVersion="";
	
	public function __construct(){
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}

        $this->SquidVersion=$this->GET_SQUID_VERSION();
        if(preg_match("#^[567]\.#",$this->SquidVersion)){$this->isSquid5=true;}
		
		
		if(!artica_setting_exists("AllowWindowsUpdates")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AllowWindowsUpdates", 1);}
		if(artica_setting_exists("SquidUrgency")){
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
        $this->url_rewrite_access();
		return true;
	}

	private function url_rewrite_access():bool{
        // acls.db is API-only — list url-rewrite ACL rules via the acls backend (enabled only, zorder).
        $rw=acls_api_get("/proxy/url-rewrite/acls");
        $results=array();
        if(is_array($rw)){foreach($rw as $r){
            if(!is_array($r)){continue;}
            if(intval($r["enabled"] ?? 0)!=1){continue;}
            $results[]=array("ID"=>intval($r["id"] ?? 0),"aclport"=>intval($r["aclPort"] ?? 0),"ztype"=>(string)($r["zType"] ?? ""),"rulename"=>(string)($r["ruleName"] ?? ""));
        }}
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





	

	
}