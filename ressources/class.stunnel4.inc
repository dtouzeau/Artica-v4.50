<?php
include_once(dirname(__FILE__)."/class.ldap.inc");
include_once(dirname(__FILE__)."/class.ini.inc");



class stunnel4{
	var $dn;
	var $main_array=array();
	var $global_conf;
	var $Disclaimer;
	var $ConfBuilded=null;
	
	function __construct(){
		
		$this->LoadSettings();
		
		
	}
	


function LoadSettings(){
		$sock=new sockets();
		$this->global_conf=$sock->GET_INFO("stunnelconfINI");
		$ini=new Bs_IniHandler();
		
		$ini->loadString($this->global_conf);
		$this->main_array=$ini->_params;
		if($GLOBALS["VERBOSE"]){
			while (list ($pid, $line) = each ($this->main_array["postfix_relayhost"]) ){
				echo "[DEBUG]: $pid = $line\n";
			}
			reset($this->main_array);
		}
		
		$relay_host=$this->main_array["postfix_relayhost"]["connect"];
		
		if(preg_match('#(.+?):([0-9]+)#',$relay_host,$h)){
			$this->main_array["postfix_relayhost"]["port"]=$h[2];
			$this->main_array["postfix_relayhost"]["connect"]=$h[1];
		}
		
		
}

function SaveConf(){
	$ini=new Bs_IniHandler();
	
	
	if(!is_numeric($this->main_array["postfix_relayhost"]["port"])){$this->main_array["postfix_relayhost"]["port"]=465;}
	$this->main_array["postfix_relayhost"]["connect"]="{$this->main_array["postfix_relayhost"]["connect"]}:{$this->main_array["postfix_relayhost"]["port"]}";
	unset($this->main_array["postfix_relayhost"]["port"]);
	$this->main_array["postfix_relayhost"]["connect"]=str_replace("::", ":", $this->main_array["postfix_relayhost"]["connect"]);
	
	
	$ini->_params=$this->main_array;
	$conf=$ini->toString();
	$conf=str_replace("[]","",$conf);
	$conf=str_replace("\n\n","\n",$conf);
	$sock=new sockets();	
	$sock->SaveConfigFile($conf,"stunnelconfINI");
	$builder=$this->BuildConf().$conf;
	if($GLOBALS["VERBOSE"]){echo "[DEBUG] ***************** \n".$builder;}
	$this->ConfBuilded=$builder;
	$sock->SaveConfigFile($builder,"stunnelconf");
	return true;

}


	function BuildConf(){
		$conf=$conf ."cert = /etc/stunnel/stunnel.pem\n";
		$conf=$conf ."sslVersion = SSLv3\n";
		$conf=$conf ."setuid = stunnel4\n";
		$conf=$conf ."setgid = stunnel4\n";
		$conf=$conf ."pid =/var/run/stunnel/stunnel4.pid\n";
		$conf=$conf ."socket = l:TCP_NODELAY=1\n";
		$conf=$conf ."socket = r:TCP_NODELAY=1\n";
		$conf=$conf ."client = yes\n";
		$conf=$conf ."foreground=no\n";
		$conf=$conf ."syslog=yes\n";
		$conf=$conf ."debug=6\n";
		$conf=$conf ."service=postfix_relayhost\n";
		$conf=$conf ."\n";	
		return $conf;
		
		

		
		
	}
	
	function SaveToserver(){
		$users=new usersMenus();
		if(!$users->stunnel4_installed){return null;}
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?stunnel-restart=yes");
		//return $sock->getfile('stunnel4save');
		}






}