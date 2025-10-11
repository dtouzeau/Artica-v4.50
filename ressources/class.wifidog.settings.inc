<?php

include_once(dirname(__FILE__)."/class.wifidog.tools.inc");
include_once(dirname(__FILE__)."/class.wifidog.templates.inc");
include_once(dirname(__FILE__)."/class.wifidog.rules.inc");
include_once(dirname(__FILE__)."/class.tcpip.inc");

class wifidog_settings{

	
	function __construct(){

		
		
	}
	
	
	public function GET_INFO($key){
		$sock=new sockets();
		return $sock->GET_INFO($key);
		
	}
	
	
	public function SET_INFO($key,$value){
        $sock=new sockets();
        $sock->SET_INFO($key,$value);
	}

	
	
}