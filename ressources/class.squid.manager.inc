<?php
include_once(dirname(__FILE__)."/class.memcached.inc");

class cache_manager{
	private $fp;
	private $ForceAddr=null;
	public $errno;
	public $errstr;
	public $ok=false;
	public $TimeOutsec=3;
    public $Out=false;
	public $errstr_plus=null;
	public $URL_SENDED;
    private $CacheTime=0;
	function __construct($forceAddr=null){
		if($forceAddr<>null){$this->ForceAddr=$forceAddr;}
		
	}

	private function connect(){
		$this->fp = false;
		$this->ok=true;
		$this->errstr_plus=null;
        if ($GLOBALS["VERBOSE"]) {$this->Out=true;}


		if($this->ForceAddr==null) {
            $AP = $this->squid_get_internal_addrport();
            $addr = "{$AP[0]}:{$AP[1]}";
        }else{
            $addr=$this->ForceAddr;
        }
        if ($this->Out) {echo "Connect $addr\n";}
		$this->fp = @stream_socket_client("tcp://$addr",$this->errno,
            $this->errstr,$this->TimeOutsec, STREAM_CLIENT_CONNECT);
		
		
		if(!$this->fp){
            if($this->Out){
                echo "Connection failed $this->errstr (Timeout=$this->TimeOutsec)\n";
            }
			$this->ok=false;
			return false;
		}
		
		if (!is_resource($this->fp)) {
            if($this->Out){
                echo "Connection failed Not a ressource (Timeout=$this->TimeOutsec)\n";
            }
			$this->ok=false;
			return false;
		
		}
		
		if($this->Out){echo "success to connect {$AP[0]}:{$AP[1]}\n";}
		return true;
	}
	
	private function GET_INFO($key){
		$value=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("$key"));
		return $value;
	
	}
	private function squid_get_alternate_port(){
		$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
		foreach ($f as $num=>$ligne){
			if(preg_match("#(transparent|tproxy|intercept)#i", trim($ligne))){continue;}
			if(preg_match("#http_port\s+([0-9]+)$#", trim($ligne),$re)){return $re[1];}
			if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)$#", trim($ligne),$re)){return "{$re[1]}:{$re[2]}";}
				
			if(preg_match("#http_port\s+([0-9]+)\s+#", trim($ligne),$re)){return $re[1];}
			if(preg_match("#http_port\s+([0-9\.]+):([0-9]+)\s+#", trim($ligne),$re)){return "{$re[1]}:{$re[2]}";}
		}
	
	}

    private function time_min($firstTime){
        $data1 = $firstTime;
        $data2 = time();
        $difference = ($data2 - $data1);
        $results=intval(round($difference/60));
        if($results<0){$results=1;}
        return $results;

    }

	private function makeQueryLimit($key,$maxtime,$memcache){

        $key="makeQuery{$key}";
        $time=intval($memcache->getKey($key));
        $this->CacheTime=$time;
        if($time==0){return false;}
        $diff=$this->time_min($time);
        $this->CacheTime="$time ({$diff}mn";
        if($diff>$maxtime){return false;}
        return true;
    }

    private function makeQueryGetCache($query,$memcache):string{
        $CacheData=$memcache->getKey("makeQuery_{$query}");
        if($memcache->MemCachedFound){
            if($GLOBALS["VERBOSE"]){echo "makeQuery(): HIT makeQuery_{$query}\n";}
            if(strlen($CacheData)>20){
                $this->ok=true;
                return $CacheData;
            }
        }
        return "";
    }

    private function makeQuerySetCache($query,$data,$memcache):bool{
        $memcache->saveKey("makeQuery_{$query}",$data,120);
        return true;
    }

    private function VERBOSE($text,$line) {
            if(!function_exists("VERBOSE")){return false;}
            VERBOSE($text,$line);
            return true;
    }

    private function tosyslog($text):bool{
        if(!function_exists("syslog")){return false;}
        openlog("SquidClient", LOG_PID , LOG_SYSLOG);
        syslog(LOG_INFO, "$text");
        closelog();
        return true;
    }
	
	public function makeQuery($query,$rawZ=false,$xlog=null){
        $this->fp=false;
		if(!isset($GLOBALS["makeQueryForce"])){$GLOBALS["makeQueryForce"]=false;}
        $this->tosyslog("Connecting: $query");
        $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/squidclient/$query");
        $this->ok=true;
        $this->VERBOSE($data,__LINE__);
        if($rawZ){return explode("\n",$data);}
        return $data;
	}

	
	private function file_time_min($path){
		$last_modified=0;
	
		if(is_dir($path)){return 10000;}
		if(!is_file($path)){return 100000;}
			
		$data1 = filemtime($path);
		$data2 = time();
		$difference = ($data2 - $data1);
		$results=intval(round($difference/60));
		if($results<0){$results=1;}
		return $results;
	}
	
	
	public function active_requests():array{
		$H=array();
		$f=$this->makeQuery("active_requests",true);
		if(!$this->ok){return $H;}


		foreach ($f as $ligne){

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
		@unlink("/usr/share/artica-postfix/ressources/logs/active_requests.inc");
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/active_requests.inc", serialize($H));
		@chmod("/usr/share/artica-postfix/ressources/logs/active_requests.inc", 0755);
		return $H;
		
	}

	
	
	
	
	
	public function squid_get_internal_addrport(){
		$SquidMgrListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
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