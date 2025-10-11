<?php
include_once(dirname(__FILE__)."/class.ldap-extern.inc");

class squid_external_ldap_builder{
	private $EXTERNAL_LDAP_AUTH_PARAMS=array();
	private $SQUID_LDAP_AUTH_PATH=null;
	private $ldap_server;
	private $ldap_port;
	private $userdn;
	private $ldap_password;
	private $ldap_suffix;
	private $ldap_filter_users;
	private $ldap_filter_group;
	private $ldap_user_attribute;
	private $ldap_group_attribute;
	private $ldap_filter_search_group;
	private $ldap_filter_group_attribute;
	private $BasicAuthenticatorEmergency=0;
	private $EnableipV6=0;
	public $MyStandPort;
	
	function __construct(){
		$this->LoadSettings();
		
	}
	
	
	private function squid_ldap_auth_path(){
		if(is_file('/lib/squid3/basic_ldap_auth')){return '/lib/squid3/basic_ldap_auth';}
		if(is_file('/usr/lib/squid3/squid_ldap_auth')){return '/usr/lib/squid3/squid_ldap_auth';}
		if(is_file('/usr/lib64/squid3/squid_ldap_auth')){return '/usr/lib64/squid3/squid_ldap_auth';}
		if(is_file('/lib/squid3/squid_ldap_auth')){return '/lib/squid3/squid_ldap_auth';}
		if(is_file('/lib64/squid3/squid_ldap_auth')){return '/lib64/squid3/squid_ldap_auth';}
		if(is_file('/usr/lib/squid/ldap_auth')){return '/usr/lib/squid/ldap_auth';}
		if(is_file('/usr/lib/squid/squid_ldap_auth')){return '/usr/lib/squid/squid_ldap_auth';}
		if(is_file('/usr/lib64/squid/squid_ldap_auth')){return '/usr/lib64/squid/squid_ldap_auth';}
		if(is_file('/usr/lib64/squid/ldap_auth')){return '/usr/lib64/squid/ldap_auth';}
		if(is_file('/usr/local/lib/squid/ldap_auth')){return '/usr/local/lib/squid/ldap_auth';}
		if(is_file('/usr/local/lib64/squid/ldap_auth')){return '/usr/local/lib64/squid/ldap_auth';}
		if(is_file('/opt/artica/libexec/squid_ldap_auth')){return '/opt/artica/libexec/squid_ldap_auth';} 			
	}
	private function LoadSettings(){
		$sock=new sockets();
		$this->EXTERNAL_LDAP_AUTH_PARAMS=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternalAuth")));
		$this->ldap_server=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
		$this->ldap_port=intval($this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"]);
		$this->userdn=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];
		$this->ldap_password=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"];
		$this->ldap_suffix=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"];
		$this->ldap_filter_users=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_users"];
		$this->ldap_filter_group=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group"];
		$this->ldap_user_attribute=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_user_attribute"];
		$this->ldap_group_attribute=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_group_attribute"];
		$this->ldap_filter_search_group=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_search_group"];
		$this->ldap_filter_group_attribute=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group_attribute"];
		$this->EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
		$this->SQUID_LDAP_AUTH_PATH=$this->squid_ldap_auth_path();
		if(!is_numeric($this->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_children"])){$this->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_children"]=10;}
		if(!is_numeric($this->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_startup"])){$this->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_startup"]=3;}
		if(!is_numeric($this->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_idle"])){$this->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_idle"]=1;}
		if(!is_numeric($this->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_cache"])){$this->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_cache"]=360;}
		$this->BasicAuthenticatorEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BasicAuthenticatorEmergency"));
		
	}	
	
	private function external_acl_childrens(){
		$array["CACHE_TIME"]=360;
		$external_acl_children=$this->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_children"];
		$external_acl_startup=$this->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_startup"];
		$external_acl_idle=$this->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_idle"];
		$negative_ttl=$this->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_cache"];
		$ttl=$this->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_cache"];
		
		if($external_acl_startup==1){$external_acl_startup=round($external_acl_children/2);}
		$f[]="ttl=$ttl";
		$f[]="negative_ttl=$negative_ttl";
		$f[]="children-startup={$external_acl_startup}";
		$f[]="children-idle={$external_acl_idle}";
		$f[]="children-max=$external_acl_children";
		if($this->EnableipV6==0){$f[]="ipv4";}
		return @implode(" ", $f);
	}
	
	public function Build(){
		if($this->BasicAuthenticatorEmergency==1){
			$conf[]= "#--------- LDAP AUTH settings ( ".__CLASS__."/ EMERGENCY / " . __LINE__." !!!!!";
			return @implode("\n", $conf);
		}
		$ldap_host=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
		$ldap_port=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"];
		$ldap_password=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"];
		$ldap_admin=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];
		$suffix=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"];
		$user_filter=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_users"];
		$group_filter=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group"];
		$auth_banner=$this->EXTERNAL_LDAP_AUTH_PARAMS["auth_banner"];
		$SQUID_LDAP_AUTH=$this->SQUID_LDAP_AUTH_PATH;

		$negative_ttl=$this->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_cache"];
		$ttl=$this->EXTERNAL_LDAP_AUTH_PARAMS["external_acl_cache"];
		
		
		
		$helper="/usr/share/artica-postfix/external_acl_squid_extldap.php";
		@chmod($helper,0755);
		
		
		$conf[]= "#--------- LDAP AUTH settings ( ".__CLASS__."/". __LINE__.")";
		$conf[]= "#Authentification mode, building using squid compiled for $ldap_host:$ldap_port";
		$conf[]= "auth_param basic program $SQUID_LDAP_AUTH -b \"$suffix\" -D \"$ldap_admin\" -w \"$ldap_password\" -f \"$user_filter\" -v 3 -h $ldap_host -p $ldap_port";
		$conf[]= "auth_param basic children ".$this->auth_param_basic_children();
		$conf[]= "auth_param basic realm $auth_banner";
		$conf[]= $this->auth_params_basic_options();
		$external_acl_childrens=$this->external_acl_childrens();
		$conf[]= "external_acl_type ldap_group $external_acl_childrens %LOGIN $helper";
		$conf[]= "acl ldapauth proxy_auth REQUIRED";
		$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"]="http_access allow {$this->MyStandPort}ldapauth";
		$conf[]= "";
		return @implode("\n",$conf);
	}

    private function auth_params_basic_options(){


        $SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));

        if(!isset($SquidClientParams["authenticate_cache_garbage_interval"])){$SquidClientParams["authenticate_cache_garbage_interval"]=3600;}
        if(!isset($SquidClientParams["authenticate_ttl"])){$SquidClientParams["authenticate_ttl"]=3600;}
        if(!isset($SquidClientParams["authenticate_ip_ttl"])){$SquidClientParams["authenticate_ip_ttl"]=1;}
        if(!isset($SquidClientParams["credentialsttl"])){$SquidClientParams["credentialsttl"]=7200;}

        if(intval($SquidClientParams["authenticate_cache_garbage_interval"])==0){$SquidClientParams["authenticate_cache_garbage_interval"]=3600;}
        if(intval($SquidClientParams["authenticate_ttl"])==0){$SquidClientParams["authenticate_ttl"]=3600;}
        if(intval($SquidClientParams["authenticate_ip_ttl"])==0){$SquidClientParams["authenticate_ip_ttl"]=1;}

        if($SquidClientParams["authenticate_ttl"]>$SquidClientParams["authenticate_cache_garbage_interval"]){
            $SquidClientParams["authenticate_cache_garbage_interval"]=$SquidClientParams["authenticate_ttl"];
        }

        if(intval($SquidClientParams["credentialsttl"])==0){$SquidClientParams["credentialsttl"]=7200;}

        $conf[]= "auth_param basic credentialsttl {$SquidClientParams["credentialsttl"]} seconds";
        $conf[]= "authenticate_ttl {$SquidClientParams["authenticate_ttl"]} seconds";
        $conf[]= "authenticate_ip_ttl {$SquidClientParams["authenticate_ip_ttl"]} seconds";
        $conf[]= "authenticate_cache_garbage_interval {$SquidClientParams["authenticate_cache_garbage_interval"]} seconds";
        return @implode("\n",$conf);
    }

    private function auth_param_basic_children(){

        if(isset($GLOBALS["auth_param_basic_children"])){return $GLOBALS["auth_param_basic_children"];}

        $SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));

        if(!isset($SquidClientParams["auth_param_basic_children"])){$SquidClientParams["auth_param_basic_children"]=3;}
        if(!isset($SquidClientParams["auth_param_basic_startup"])){$SquidClientParams["auth_param_basic_startup"]=2;}
        if(!isset($SquidClientParams["auth_param_basic_idle"])){$SquidClientParams["auth_param_basic_idle"]=1;}



        $auth_param_basic_children=intval($SquidClientParams["auth_param_basic_children"]);
        $auth_param_basic_startup=intval($SquidClientParams["auth_param_basic_startup"]);
        $auth_param_basic_idle=intval($SquidClientParams["auth_param_basic_idle"]);
        if($auth_param_basic_idle==0){$auth_param_basic_idle=1;}
        if($auth_param_basic_startup==0){$auth_param_basic_startup=2;}
        if($auth_param_basic_children==0){$auth_param_basic_children=3;}

        $auth_param_child="$auth_param_basic_children startup=$auth_param_basic_startup idle=$auth_param_basic_idle";
        $GLOBALS["auth_param_basic_children"]=$auth_param_child;
        return $auth_param_child;

    }
	
	
	
}