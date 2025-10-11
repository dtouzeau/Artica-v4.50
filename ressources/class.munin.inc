<?php


class munin_plugins{
	private $SQUIDEnable=0;
	private $EnableMunin=0;
	private $EnableDHCPServer=0;
	private $EnableHaProxy=0;
	private $UnboundEnabled=0;
	
	public function __construct(){
		$this->SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
		$this->EnableMunin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
		$this->EnableDHCPServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
		$this->EnableHaProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHaProxy"));
		$this->UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
	}
	private function unbound(){
		$RESTART=false;
		$srcsr="/usr/share/artica-postfix/bin/install/munin/unbound_munin_";
		if(!is_file($srcsr)){return;}
		$unix=new unix();
		$ln=$unix->find_program("ln");
		$chmod=$unix->find_program("chmod");
		system("$chmod +x $srcsr");
		
		$f[]="/etc/munin/plugins/unbound_munin_by_class";
		$f[]="/etc/munin/plugins/unbound_munin_by_flags";
		$f[]="/etc/munin/plugins/unbound_munin_by_opcode";
		$f[]="/etc/munin/plugins/unbound_munin_by_rcode";
		$f[]="/etc/munin/plugins/unbound_munin_by_type";
		$f[]="/etc/munin/plugins/unbound_munin_histogram";
		$f[]="/etc/munin/plugins/unbound_munin_hits";
		$f[]="/etc/munin/plugins/unbound_munin_memory";
		$f[]="/etc/munin/plugins/unbound_munin_queue";
		
		if($this->UnboundEnabled==0){
			foreach ($f as $path){
				if(is_file($path)){@unlink($path);$RESTART=true;}
			}
		}
		
		if($this->UnboundEnabled==1){
			foreach ($f as $path){
				if(!is_file($path)){
					shell_exec("$ln -s $srcsr $path");
					$RESTART=true;
				}
			}
			
		}
	
		
		if($RESTART){system("/etc/init.d/munin-node restart");}
		
		
	}
	
	
	public function build(){
		if($this->EnableMunin==0){return;}
		$unix=new unix();
		$RESTARTT=false;
		$install=array();
		$remove=array();
		$this->unbound();
		$install[]="uptime";
		$install[]="tcp";
		
		$remove[]="ntp_kernel_err";
		$remove[]="ntp_kernel_pll_freq";
		$remove[]="ntp_kernel_pll_off";
		$remove[]="ntp_offset";
		
		if($this->SQUIDEnable==1){
			$install[]="squid_cache";
			$install[]="squid_objectsize";
			$install[]="squid_requests";
			$install[]="squid_traffic";
		}else{
			$remove[]="squid_cache";
			$remove[]="squid_objectsize";
			$remove[]="squid_requests";
			$remove[]="squid_traffic";
			
		}
		$POSTFIX=false;
		$POSTFIX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
		$EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
		if($POSTFIX_INSTALLED==1){if($EnablePostfix==1){$POSTFIX=true;}}
		if($POSTFIX){
			$install[]="postfix_mailqueue";
			$install[]="postfix_mailstats";  
			$install[]="postfix_mailvolume";
		}else{
			$remove[]="postfix_mailqueue";
			$remove[]="postfix_mailstats";  
			$remove[]="postfix_mailvolume";
		}
		
		
		$ln=$unix->find_program("ln");
		
		$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
		foreach ($NETWORK_ALL_INTERFACES as $eth=>$all){
			if($eth=="lo"){continue;}
			if(is_file("/etc/munin/plugins/if_{$eth}")){continue;}
			$RESTARTT=true;
			system("$ln -s /usr/share/munin/plugins/if_ /etc/munin/plugins/if_{$eth}");
		}
		
		
		if($this->EnableDHCPServer==1){
			$install[]="dhcpd3";
		}else{
			$remove[]="dhcpd3";
		}
		if($this->EnableHaProxy==1){
			$install[]="haproxy_";
		}else{
			$remove[]="haproxy_";
		}		

		foreach ($install as $filename){
			if(!is_file("/usr/share/munin/plugins/$filename")){continue;}
			if(is_file("/etc/munin/plugins/$filename")){continue;}
			echo "Installing munin plugin $filename\n";
			system("$ln -s /usr/share/munin/plugins/$filename /etc/munin/plugins/$filename");
			$RESTARTT=true;
		}
		foreach ($remove as $filename){
			if(is_file("/etc/munin/plugins/$filename")){
				echo "Remove munin plugin $filename\n";
				@unlink("/etc/munin/plugins/$filename");
				$RESTARTT=true;
			}
			}
		
		
		if($RESTARTT){system("/etc/init.d/munin-node restart");}
		
	}
	
	
}