<?php


include_once(dirname(__FILE__)."/class.tcpip.inc");

class squid_meta{
	
	
	public function __construct(){
		
	}
	
	private function chown_object($path){
		@chown($path,"squid");
		@chgrp($path, "squid");
	}
	
	function ToSyslog($text,$error=false){
	
		if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
		if(function_exists("openlog")){openlog("monit", LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}	
	
	
	public function build_acls(){

	}
	
	
	public function http_access(){

		return @implode("\n",$f);
		
	}
	
	
	
}