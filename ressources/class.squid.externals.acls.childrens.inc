<?php
class external_acl_children_class{
	private $SquidUsersNumber=0;
	private $SquidClientParams=array();
	private $EnableipV6=0;
	
	function __construct(){
		$this->SquidUsersNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUsersNumber"));
		$this->SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));
		$this->ntlm_params_defaults();
	}
	
	public function external_acl_children(){
		
		$external_acl_children=$this->SquidClientParams["external_acl_children"];
		if($external_acl_children>5){return $external_acl_children;}
		if($this->SquidUsersNumber==0){return $external_acl_children;}
		if($this->SquidUsersNumber>49){$external_acl_children=20;}
		if($this->SquidUsersNumber>499){$external_acl_children=40;}
		if($this->SquidUsersNumber>1000){$external_acl_children=100;}
		if($this->SquidUsersNumber>1500){$external_acl_children=120;}
		return $external_acl_children;
	}

	public function build($MacProc=0,$NoNegCache=false,$ForceConf=array()){
		$external_acl_concurrency=0;
		$array["CACHE_TIME"]=360;
		$MacProc=$this->external_acl_children();
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];
	
		$negative_ttl=$array["CACHE_TIME"];
		$ttl=$array["CACHE_TIME"];
	
	
	
		if(isset($ForceConf["ttl"])){$ttl=$ForceConf["ttl"];}
		if(isset($ForceConf["negative_ttl"])){$negative_ttl=$ForceConf["ttl"];}
		if(isset($ForceConf["children-startup"])){$external_acl_startup=$ForceConf["children-startup"];}
		if(isset($ForceConf["children-idle"])){$external_acl_idle=$ForceConf["children-idle"];}
		if(isset($ForceConf["concurrency"])){$external_acl_concurrency=intval($ForceConf["concurrency"]);}
		if($external_acl_startup==1){$external_acl_startup=5;}
	
		$f[]="ttl=$ttl";
		if(!$NoNegCache){
			$f[]="negative_ttl=$negative_ttl";
		}else{
			$f[]="negative_ttl=0";
		}
		
		$f[]="children-startup={$external_acl_startup}";
		$f[]="children-idle={$external_acl_idle}";
		if($MacProc>0){
			$f[]="children-max=$MacProc";
		}
		
		if($external_acl_concurrency>0){
			$f[]="concurrency=$external_acl_concurrency";
		}
		
		if($this->EnableipV6==0){$f[]="ipv4";}
		return @implode(" ", $f);
	
	}
	private function ntlm_params_defaults(){
		$this->EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
		if(!isset($this->SquidClientParams["external_acl_children"])){$this->SquidClientParams["external_acl_children"]=5;}
		if(!isset($this->SquidClientParams["authenticate_ttl"])){$this->SquidClientParams["authenticate_ttl"]=14400;}
		if(!isset($this->SquidClientParams["authenticate_ip_ttl"])){$this->SquidClientParams["authenticate_ip_ttl"]=14400;}
		if(!isset($this->SquidClientParams["credentialsttl"])){$this->SquidClientParams["credentialsttl"]=14400;}
		if(!isset($this->SquidClientParams["authenticate_cache_garbage_interval"])){$this->SquidClientParams["authenticate_cache_garbage_interval"]=18000;}
			
		if(!isset($this->SquidClientParams["auth_param_basic_children"])){$this->SquidClientParams["auth_param_basic_children"]=3;}
		if(!isset($this->SquidClientParams["auth_param_basic_startup"])){$this->SquidClientParams["auth_param_basic_startup"]=2;}
		if(!isset($this->SquidClientParams["auth_param_basic_idle"])){$this->SquidClientParams["auth_param_basic_idle"]=1;}
		
		
		if(!isset($this->SquidClientParams["auth_param_ntlm_children"])){$this->SquidClientParams["auth_param_ntlm_children"]=20;}
		if(!isset($this->SquidClientParams["auth_param_ntlm_startup"])){$this->SquidClientParams["auth_param_ntlm_startup"]=1;}
		if(!isset($this->SquidClientParams["auth_param_ntlm_idle"])){$this->SquidClientParams["auth_param_ntlm_idle"]=1;}

		if(!isset($this->SquidClientParams["external_acl_startup"])){$this->SquidClientParams["external_acl_startup"]=20;}
		if(!isset($this->SquidClientParams["external_acl_idle"])){$this->SquidClientParams["external_acl_idle"]=1;}

		if(!is_numeric($this->SquidClientParams["external_acl_startup"])){$this->SquidClientParams["external_acl_startup"]=5;}
		if(!is_numeric($this->SquidClientParams["external_acl_idle"])){$this->SquidClientParams["external_acl_idle"]=1;}		
		if(!is_numeric($this->SquidClientParams["external_acl_children"])){$this->SquidClientParams["external_acl_children"]=5;}

		
		if(!is_numeric($this->SquidClientParams["auth_param_ntlm_children"])){$this->SquidClientParams["auth_param_ntlm_children"]=20;}
		if(!is_numeric($this->SquidClientParams["auth_param_ntlm_startup"])){$this->SquidClientParams["auth_param_ntlm_startup"]=1;}
		if(!is_numeric($this->SquidClientParams["auth_param_ntlm_idle"])){$this->SquidClientParams["auth_param_ntlm_idle"]=1;}
			
		if(!is_numeric($this->SquidClientParams["auth_param_basic_children"])){$this->SquidClientParams["auth_param_basic_children"]=3;}
		if(!is_numeric($this->SquidClientParams["auth_param_basic_startup"])){$this->SquidClientParams["auth_param_basic_startup"]=2;}
		if(!is_numeric($this->SquidClientParams["auth_param_basic_idle"])){$this->SquidClientParams["auth_param_basic_idle"]=1;}
			
		if(intval($this->SquidClientParams["authenticate_cache_garbage_interval"])==0){$this->SquidClientParams["authenticate_cache_garbage_interval"]=18000;}
		if(intval($this->SquidClientParams["authenticate_ttl"])==0){$this->SquidClientParams["authenticate_ttl"]=14400;}
		if(intval($this->SquidClientParams["authenticate_ip_ttl"])==0){$this->SquidClientParams["authenticate_ip_ttl"]=$this->SquidClientParams["authenticate_ttl"];}
			
			
			
		if($this->SquidClientParams["authenticate_ttl"]>$this->SquidClientParams["authenticate_cache_garbage_interval"]){
			$this->SquidClientParams["authenticate_cache_garbage_interval"]=$this->SquidClientParams["authenticate_ttl"];
		}
		if(intval($this->SquidClientParams["credentialsttl"])==0){
			$this->SquidClientParams["credentialsttl"]=$this->SquidClientParams["authenticate_ttl"];
		}
	}
	
}