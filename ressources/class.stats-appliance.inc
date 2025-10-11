<?php


class stats_appliance{
	private $EnableSquidRemoteMySQL=0;
	private $remote_server=null;
	private $remote_server_ssl=0;
	private $remote_server_port=0;
	
	function __construct(){
		$this->EnableSquidRemoteMySQL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidRemoteMySQL"));
		$squidRemostatisticsServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("squidRemostatisticsServer");
		$WizardStatsAppliance=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardStatsAppliance")));
		if(isset($WizardStatsAppliance["SERVER"])){
				if($WizardStatsAppliance["SERVER"]<>null){ 
					$this->remote_server=$WizardStatsAppliance["SERVER"];
					$this->remote_server_ssl=intval($WizardStatsAppliance["SSL"]);
					$this->remote_server_port=intval($WizardStatsAppliance["PORT"]);
					if($this->remote_server_port==0){$this->remote_server_port=9000;}
					
				} 
		}
		if($this->remote_server==null){
			if($this->EnableSquidRemoteMySQL==1){
				$this->remote_server==$squidRemostatisticsServer;
			}
		}
		
		
	}
	
	public function IsConnected(){
		
		if($this->EnableSquidRemoteMySQL==1){return true;}
		if($this->remote_server<>null){return true;}
	}
	public function build_upload_uri(){
		$PROTO="http";
		if(!is_numeric($this->remote_server_port)){$this->remote_server_port=9000;}
		if($this->remote_server_port==0){$this->remote_server_port=9000;}
		
		if($this->remote_server_ssl==1){$PROTO="https";}
		return "$PROTO://$this->remote_server:{$this->remote_server_port}/artica.stats.receiver.php";
	}
	
	
}