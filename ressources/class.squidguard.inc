<?php

include_once(dirname(__FILE__)."/class.dansguardian.inc");
include_once(dirname(__FILE__)."/class.groups.inc");
include_once(dirname(__FILE__)."/class.c-icap-filter.inc");


class squidguard{
	var $categories=array();
	var $rules=array();
	var $groupsofRule=array();
	var $DefaultRules=array();
	var $DestOfRules=array();
	var $CategoriesDefinitions=array();
	var $rulesOK=array();
	var $ldap_server;
	var $ldap_port;
	var $ldap_suffix;
	var $EnableWhiteList=false;
	var $EnableBlackList=false;
	var $SquidGuardIPWeb=null;
	var $EnableUfdbGuard=0;
	var $UfdbGuard_conf=null;
	var $EnableSquidFilterWhiteListing=0;
	function __construct(){
		
		
		writelogs("FATAL class.squidguard.inc is used !!!!!",__FUNCTION__,__FILE__,__LINE__);
		$GLOBALS["guarddb"]="/var/lib/squidguard";
		$this->rules=$this->GetRules();
		$sock=new sockets();
		$this->EnableSquidFilterWhiteListing=$sock->GET_INFO("EnableSquidFilterWhiteListing");
	}
	
	
	function BuildConf(){
		
		
		
		
	}
	
	private function GetRules(){
		
	}
	

	
	
	
	
}

?>