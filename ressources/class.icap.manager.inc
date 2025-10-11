<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

class icap_manager{
	private $fp;
	public $errno;
	public $errstr;
	public $ok=false;
	public $TimeOutsec=3;
	public $errstr_plus=null;
	public $URL_SENDED;
	public $ListenAddress=null;
	public $errlog=array();
	function __construct(){
		
		
	}
	
	
	private function connect(){
		$this->fp = false;
		$this->ok=true;
		$this->errstr_plus=null;
        $unix=new unix();
		$EnableSecurityAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSecurityAppliance"));
        $this->CICAPListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPListenInterface"));
        if($this->CICAPListenInterface==null){$this->CICAPListenInterface="lo";}
        $this->ListenAddress= $unix->InterfaceToIPv4($this->CICAPListenInterface);
		if($this->ListenAddress==null){$this->ListenAddress="127.0.0.1";}
		if($EnableSecurityAppliance==1){
			$this->ListenAddress=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecurityApplianceIPaddr");
		}

		if($GLOBALS["VERBOSE"]){echo "Connect {$this->ListenAddress}:1345\n";}
		
		
		$this->fp = @stream_socket_client("tcp://$this->ListenAddress:1345",$this->errno, $this->errstr,$this->TimeOutsec, STREAM_CLIENT_CONNECT);
		
		
		if(!$this->fp){
			$this->errlog[]="tcp://$this->ListenAddress:1345 - failed";
			$this->ok=false;
			return false;
		}
		
		if (!is_resource($this->fp)) {
			$this->errlog[]="tcp://$this->ListenAddress:1345 - failed not a resource";
			$this->ok=false;
			return false;
		
		}
		
		$this->errlog[]="tcp://$this->ListenAddress:1345 - Connect Success";
		if($GLOBALS["VERBOSE"]){echo "success to connect $this->ListenAddress:1345\n";}
		return true;
	}
	
	private function GET_INFO($key){
		$value=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("$key"));
		return $value;
	
	}

	
	
	public function makeQuery($query="info?view=text",$rawZ=false){
		if(!$this->fp){
			if(!$this->connect()){
				$this->errlog[]="tcp://$this->ListenAddress:1345 - connect() failed";
				return false;
			}
		}
		$this->errno=0;
		$out = "OPTIONS icap://$this->ListenAddress:1345/info ICAP/1.0\r\n\r\n";
		if($GLOBALS["VERBOSE"]){echo "Send $out\n";}
	
		stream_set_timeout($this->fp,$this->TimeOutsec);
		fwrite($this->fp, $out);
		$info = stream_get_meta_data($this->fp);
		
		if ($info['timed_out']) {
			$this->errno=1000;
			$this->errstr="Timed Out";
			@socket_close($this->fp);
			@fclose($this->fp);
			$this->errlog[]="tcp://$this->ListenAddress:1345 - Timed OUT";
			return false;
		}
		
		stream_set_blocking($this->fp, TRUE);
		stream_set_timeout($this->fp,$this->TimeOutsec);
		
		 while ((!feof($this->fp)) && (!$info['timed_out'])) { 
		 	$lineZ=trim(fgets($this->fp, 2048));
		 	if($GLOBALS["VERBOSE"]){echo "RECEIVE: --> \"$lineZ\"\n";}
		 	$raw[]=$lineZ;
		 	if(trim($lineZ)=="ICAP/1.0 200 OK"){
		 		if($GLOBALS["VERBOSE"]){echo "SUCCESS - EXIT TRUE<br>\n";}
		 		$this->errlog[]="tcp://$this->ListenAddress:1345 - 200 OK";
		 		@socket_close($this->fp);
		 		@fclose($this->fp);
		 		return true;
		 	}
			$info = stream_get_meta_data($this->fp);
		}
		
		
		if ($info['timed_out']) {
			if($GLOBALS["VERBOSE"]){echo "TIMEDOUT: -->\n";}
			$this->errno=1000;
			$this->errstr="Timed Out";
			$this->errlog[]="tcp://$this->ListenAddress:1345 - Timed OUT";
			$this->ok=false;
			@socket_close($this->fp);
			@fclose($this->fp);
			return false;
		}
		
		$this->errlog[]="tcp://$this->ListenAddress:1345 - SOCKCLOSE";
		if($GLOBALS["VERBOSE"]){echo "SOCK CLOSE: -->\n";}
		@socket_close($this->fp);
		@fclose($this->fp);
		
		if(!preg_match("#ICAP.*?200\s+OK#i", $raw[0])){
			$this->errlog[]="Cannot get data. Server answered: $raw[0] {$this->errstr_plus}";
			if($GLOBALS["VERBOSE"]){echo "Failed \"{$raw[0]}\"\n";}
			$this->errno=1;
			$this->errstr="Cannot get data. Server answered: $raw[0] {$this->errstr_plus}";
			$this->ok=false;
			return false;
		}
		$this->ok=true;
		$this->errlog[]="Success";
		if($GLOBALS["VERBOSE"]){echo "Success ".count($raw)." line(s)\n";}
		return true;
	}
	
	
	public function active_requests(){
		$f=$this->makeQuery("active_requests",true);
		if(!$this->ok){return;}
		while (list ($mkey, $ligne) = each ($f) ){
		
			if(preg_match("#Connection:\s+(.+)#", $ligne,$re)){
				$CON=$re[1];
				if($CON=="close"){continue;}
				$H["CON"][$re[1]]=true;
				continue;
			}
		
			if(preg_match("#remote:\s+([0-9\.]+):#", $ligne,$re)){
				$H[$CON]["IPS"][$re[1]]=true;
				$H["connections"][$CON]["IPS"]=$re[1];
				$H["IPS"][$re[1]]=true;
				$H["ALL"][$re[1]]=true;
				continue;
			}
		
			if(preg_match("#username\s+(.+)#", $ligne,$re)){
				$H[$CON]["USERS"][$re[1]]=true;
				$H["connections"][$CON]["USERS"]=$re[1];
				$H["USERS"][$re[1]]=true;
				$H["ALL"][$re[1]]=true;
				continue;
			}
			
			if(preg_match("#uri\s+(.+)#",$ligne,$matches)){
				$H["connections"][$CON]["uri"]=$matches[1];
				$H[$CON]["uri"]=$matches[1];
			}
			
			if(preg_match('/out.offset \d+, out.size (\d+)/',$ligne,$matches)){
				$H["connections"][$CON]["bytes"]=$matches[1];
				$H[$CON]["bytes"]=$matches[1];
			}
			if(preg_match('/start \d+\.\d+ \((\d+).\d+ seconds ago\)/',$ligne,$matches)){
				$H["connections"][$CON]["seconds"]=$matches[1];
				$H[$CON]["seconds"]=$matches[1];
			}
		
		}
		
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/active_requests.inc", serialize($H));
		@chmod("/usr/share/artica-postfix/ressources/logs/active_requests.inc", 0755);
		return $H;
		
	}

	
	
	
	
	
	private function squid_get_internal_addrport(){
		$SquidMgrListenPort=trim($this->GET_INFO("SquidMgrListenPort"));
		if( !is_numeric($SquidMgrListenPort) OR ($SquidMgrListenPort==0) ){
			$SquidBinIpaddr=$this->GET_INFO("SquidBinIpaddr");
			if($SquidBinIpaddr==null){$SquidBinIpaddr="127.0.0.1";}
			$http_port=$this->squid_get_alternate_port();
	
			if(preg_match("#(.+?):([0-9]+)#", $http_port,$re)){
				$SquidBinIpaddr=$re[1];
				if($SquidBinIpaddr=="0.0.0.0"){$SquidBinIpaddr="127.0.0.1";}
				$http_port=$re[2];
			}
	
		}else{
			$SquidBinIpaddr="127.0.0.1";
			$http_port=$SquidMgrListenPort;
		}
	
		return array($SquidBinIpaddr,$http_port);
	}	
	
	
}