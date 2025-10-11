<?php
if(!isset($GLOBALS["PROGRESS"])){$GLOBALS["PROGRESS"]=false;}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/class.tcpip.inc");
include_once(dirname(__FILE__)."/externals/Net/DNS2.inc");

class resolv_conf{
	var $MainArray=array();
	var $arrayNameServers=array();
	var $lockServ1=0;
	var $output=false;
	var $mysql_error=null;
    var $UseTCP=0;
	var $ok=true;
	
	function __construct($nobuild=false){

	    if(!$nobuild) {
            $this->buildConf();
        }
		
	}
	private function buildConf(){
		$sock=new sockets();
		if($GLOBALS["PROGRESS"]){echo "82%] Loading resolvConf\n";}
		$this->MainArray=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolvConf")));
		
		if(!is_array($this->MainArray)){
			if($this->output){echo "---] resolvConf: Not an array, build default...\n";}
			$this->buildDefault();
		}
		$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
        $this->UseTCP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ResolvConfUseTCP"));
		if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
		if(!isset($this->MainArray["TIMEOUT"])){$this->MainArray["TIMEOUT"]=2;}
		if(!isset($this->MainArray["ATTEMPTS"])){$this->MainArray["ATTEMPTS"]=2;}
		if(!isset($this->MainArray["USEROTATION"])){$this->MainArray["USEROTATION"]=0;}
		if(!isset($this->MainArray["DOMAINS1"])){$this->MainArray["DOMAINS1"]=$this->SearchPrinciPalDomain();}
        if(!isset($this->MainArray["DontUseLocalDns"])){$this->MainArray["DontUseLocalDns"]=0;}
		
		if(!is_numeric($this->MainArray["TIMEOUT"])){$this->MainArray["TIMEOUT"]=2;}
		if(!is_numeric($this->MainArray["ATTEMPTS"])){$this->MainArray["ATTEMPTS"]=2;}
		if(!is_numeric($this->MainArray["USEROTATION"])){$this->MainArray["USEROTATION"]=0;}
		if($this->MainArray["DOMAINS1"]==null){$this->MainArray["DOMAINS1"]=$this->SearchPrinciPalDomain();}
		if(!$this->isValidDomain($this->MainArray["DOMAINS1"])){$this->MainArray["DOMAINS1"]=null;}
        if(!is_null($this->MainArray["DOMAINS1"])){
            $this->MainArray["DOMAINS1"]=trim(strtolower($this->MainArray["DOMAINS1"]));
        }

		if($EnableKerbAuth==1){
			$UseADAsNameServer=$sock->GET_INFO("UseADAsNameServer");
			if(!is_numeric($UseADAsNameServer)){$UseADAsNameServer=0;}
			if($UseADAsNameServer==1){
				$array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
                if(!is_array($array)){$array=array();}
				if(preg_match("#[0-9\.]+#", $array["ADNETIPADDR"])){
					$this->MainArray["DNS1"]=$array["ADNETIPADDR"];
					$this->lockServ1=1;
				}
				
			}
		}
		if($GLOBALS["PROGRESS"]){echo "89%] Loading DONE...\n";}
		VERBOSE("DONE",__LINE__);
		
		
		
	}
	
	private function buildDefault(){
		if($GLOBALS["PROGRESS"]){echo "82%] Loading LoadNameServers()\n";}
		$this->LoadNameServers();
		if(isset($this->arrayNameServers[0])){
			$this->MainArray["DNS1"]=$this->arrayNameServers[0];
		}
		if(isset($this->arrayNameServers[1])){
			$this->MainArray["DNS2"]=$this->arrayNameServers[1];
		}
		if(isset($this->arrayNameServers[2])){
			$this->MainArray["DNS3"]=$this->arrayNameServers[2];
		}
		
		if(!isset($this->MainArray["DNS3"])){$this->MainArray["DNS3"]=null;}
		if(!isset($this->MainArray["DNS2"])){$this->MainArray["DNS2"]=null;}
		if(!isset($this->MainArray["DNS1"])){$this->MainArray["DNS1"]=null;}

        if($this->MainArray["DNS1"]=="0.0.0.0"){$this->MainArray["DNS1"]=null;}
        if($this->MainArray["DNS2"]=="0.0.0.0"){$this->MainArray["DNS2"]=null;}
        if($this->MainArray["DNS3"]=="0.0.0.0"){$this->MainArray["DNS3"]=null;}

		$ip=new IP();
		if(!$ip->isValid($this->MainArray["DNS1"])){unset($this->MainArray["DNS1"]);}
		if(!$ip->isValid($this->MainArray["DNS2"])){unset($this->MainArray["DNS2"]);}
		if(!$ip->isValid($this->MainArray["DNS3"])){unset($this->MainArray["DNS3"]);}

        if(isset($this->MainArray["DNS1"])) {
            if (!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $this->MainArray["DNS1"])) {
                unset($this->MainArray["DNS1"]);
            }
        }

        if(isset($this->MainArray["DNS2"])) {
            if (!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $this->MainArray["DNS2"])) {
                unset($this->MainArray["DNS2"]);
            }
        }

		if(isset($this->MainArray["DNS3"])) {
            if (!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $this->MainArray["DNS3"])) {
                unset($this->MainArray["DNS3"]);
            }
        }
		
		
		
	}

	public function resolvTypeA($dns1=null,$dns2=null,$hostname=null):string{
        try {
            $ns=array();
            if($dns1<>null){$ns[]=$dns1;}
            if($dns2<>null){$ns[]=$dns2;}
            $r = new Net_DNS2_Resolver(array('nameservers' => $ns, "timeout" => 1));
            $result = $r->query($hostname, 'A');
            return $result->answer[0]->address;

        } catch (Net_DNS2_Exception $e) {
            $this->mysql_error="resolvTypeA() failed: ".$e->getMessage();
        }
        return "";
    }

    public function gethostbyaddr($ipaddr){
        $key="gethostbyaddr:$ipaddr";
        if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $ipaddr)){
            return $ipaddr;
        }

        if(isset($GLOBALS["gethostbyaddr"][$ipaddr])){
            return $GLOBALS["gethostbyaddr"][$ipaddr];
        }

        if (class_exists("lib_memcached")){
            $memCached=new lib_memcached();
            $data=$memCached->getKey($key);
            if($memCached->MemCachedFound){
                $GLOBALS["gethostbyaddr"][$ipaddr]=$data;
                return $data;
            }
        }

        $this->mysql_error=null;
        if(count($this->MainArray)==0){
            $this->MainArray=unserialize(
                base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolvConf")));
        }
        if($this->MainArray["DNS1"]<>null){$tt[]=$this->MainArray["DNS1"];}
        if($this->MainArray["DNS2"]<>null){$tt[]=$this->MainArray["DNS2"];}
        if(isset($this->MainArray["DNS3"])) {
            if ($this->MainArray["DNS3"] <> null) {
                $tt[] = $this->MainArray["DNS3"];
            }
        }

        try {
            $r = new Net_DNS2_Resolver(array('nameservers' => $tt, "timeout" => 2));
            $result = $r->query($ipaddr, 'PTR');
            $data=$result->answer[0]->ptrdname;
            if (class_exists("lib_memcached")){
                $memCached=new lib_memcached();
                $memCached->saveKey($key,$data,2600);
            }
            $GLOBALS["gethostbyaddr"][$ipaddr]=$data;
            return $data;

        } catch (Net_DNS2_Exception $e) {
            $GLOBALS["gethostbyaddr"][$ipaddr]=$ipaddr;
            $this->mysql_error="gethostbyaddr() failed: ".$e->getMessage();
            if (class_exists("lib_memcached")){
                $memCached=new lib_memcached();
                $memCached->saveKey($key,$ipaddr,2600);
            }

        }

        return $ipaddr;

    }

	
	public function isValidDomain($domain){
        if(is_null($domain)){return false;}
		$domain=trim($domain);
		if(strpos($domain, " ")>0){return false;}
		if(strpos($domain, "[")>0){return false;}
		if(strpos($domain, ".")==0){return false;}
		return true;
	}
	
	private function SearchPrinciPalDomain(){
		exec("/bin/hostname -A 2>&1",$results );
		$ff=@implode("", $results);
		if(preg_match("#([\s+\(\)\[\]]+)#", $ff)){
			$ff="";
		}
		if(is_null($ff)){$ff="";}
		$pos=strpos($ff, ".");
		
		
		if(!$this->isValidDomain($ff)){return "localhost.local";}
		return trim(substr($ff, $pos,strlen($ff)));
		
	}
	
	public function save(){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("resolvConf",base64_encode(serialize($this->MainArray)));
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/nohup/resolvapply");
	}

	private function progress($text,$prc){
	    if(function_exists("build_progress_resolvconf")){
            build_progress_resolvconf($text,$prc);
        }
    }

    private function Syslog($text){
        openlog("rsyslog.conf", LOG_PID , LOG_SYSLOG);
        syslog(LOG_INFO, $text);
        closelog();
    }
	
	public function build():string{

        if(function_exists("debug_backtrace")) {
            $trace = debug_backtrace();

            if (isset($trace[0])) {
                $file = basename($trace[0]["file"]);
                $function = $trace[0]["function"];
                $line = $trace[0]["line"];
            }

            if (isset($trace[1])) {
                $file = basename($trace[1]["file"]);
                $function = $trace[1]["function"];
                $line = $trace[1]["line"];
            }
            $this->Syslog("ALERT! Build() by $file,$function,$line");
        }

		$rotation=null;
        $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
        $DoNotUseLocalDNSCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DoNotUseLocalDNSCache"));

        $f[]="# HaClusterClient = $HaClusterClient";
        $f[]="# DoNotUseLocalDNSCache = $DoNotUseLocalDNSCache";
        if($DoNotUseLocalDNSCache==0){
            $this->UseTCP=1;
        }

        $this->progress("{building}",10);
        $HaClusterUseLBAsDNS=0;

        if($HaClusterClient==1) {
            if($HaClusterGBConfig["DOMAINS1"]<>null){$this->MainArray["DOMAINS1"]=$HaClusterGBConfig["DOMAINS1"];}
            if($HaClusterGBConfig["DOMAINS2"]<>null){$this->MainArray["DOMAINS2"]=$HaClusterGBConfig["DOMAINS2"];}
            $HaClusterUseLBAsDNS = intval($HaClusterGBConfig["HaClusterUseLBAsDNS"]);
        }

		if($this->MainArray["DOMAINS1"]<>null){$tt[]=$this->MainArray["DOMAINS1"];}
		if($this->MainArray["DOMAINS2"]<>null){$tt[]=$this->MainArray["DOMAINS2"];}
		if($this->MainArray["DOMAINS3"]<>null){$tt[]=$this->MainArray["DOMAINS3"];}
		if($this->MainArray["DOMAINS1"]<>null){$f[]="domain {$this->MainArray["DOMAINS1"]}";}


        if ($HaClusterClient == 1) {
            if ($HaClusterUseLBAsDNS == 1) {
                $DoNotUseLocalDNSCache=1;
            }
        }
		
		if(count($tt)>0){
			$f[]="search ".@implode(" ", $tt);
		}

        if($this->UseTCP==1){
            $f[] = "options use-vc";
        }
		
		if($DoNotUseLocalDNSCache==1) {
            if ($this->MainArray["USEROTATION"] == 1) {
                $rotation = " rotate";
            }
            $f[] = "options attempts:{$this->MainArray["ATTEMPTS"]} timeout:{$this->MainArray["TIMEOUT"]}$rotation";
        }
		
		if($this->MainArray["DNS1"]=="___.___.___.___"){$this->MainArray["DNS1"]=null;}
		if($this->MainArray["DNS2"]=="___.___.___.___"){$this->MainArray["DNS2"]=null;}
		if($this->MainArray["DNS3"]=="___.___.___.___"){$this->MainArray["DNS3"]=null;}
        if($DoNotUseLocalDNSCache==1) {
            if ($HaClusterClient == 1) {
                $HaClusterUseLBAsDNS = intval($HaClusterGBConfig["HaClusterUseLBAsDNS"]);
                if ($HaClusterUseLBAsDNS == 1) {
                    $HaClusterIP = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterIP");
                    if (preg_match("#http(s|):\/\/(.+?):[0-9]+#", $HaClusterIP, $re)) {
                        $HaClusterIP = $re[2];
                    }

                    $f[] = "nameserver $HaClusterIP";
                }
            }
        }

        if($DoNotUseLocalDNSCache==1) {
            if ($HaClusterClient == 1) {
                if (trim($HaClusterGBConfig["DNS1"]) <> null) {
                    $this->MainArray["DNS1"] = $HaClusterGBConfig["DNS1"];
                }
                if (trim($HaClusterGBConfig["DNS2"]) <> null) {
                    $this->MainArray["DNS2"] = $HaClusterGBConfig["DNS2"];
                }
            }
        }
        if($DoNotUseLocalDNSCache==1) {
            if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled")) == 1 || intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist")) == 1) {

                if ($this->MainArray["DontUseLocalDns"] == 0) {
                    if ($GLOBALS["VERBOSE"]) {
                        echo "UnboundEnabled -> nameserver 127.0.0.1\n";
                    }
                    $f[] = "nameserver 127.0.0.1";
                }

            }
        }
        if($DoNotUseLocalDNSCache==0) {
            $f[] = "nameserver 127.0.0.55";
            $libmem=new lib_memcached();
            $libmem->Delkey("GOOD_DNS_SERVERS");
            return @implode("\n", $f)."\n";
        }


        $this->progress("{building}",20);

        if(!isset($this->MainArray["DNS3"])){$this->MainArray["DNS3"]=null;}
		if($this->MainArray["DNS1"]<>null){$f[]="nameserver {$this->MainArray["DNS1"]}";}
		if($this->MainArray["DNS2"]<>null){$f[]="nameserver {$this->MainArray["DNS2"]}";}
		if($this->MainArray["DNS3"]<>null){$f[]="nameserver {$this->MainArray["DNS3"]}";}
		if($this->output){echo "---] Configuration builded successfully\n";}
        $libmem=new lib_memcached();
        $libmem->Delkey("GOOD_DNS_SERVERS");
		return @implode("\n", $f)."\n";

	}
	

	
	
	
	private function DNS_LIST(){
		
		$f=explode("\n",@file_get_contents("/etc/resolv.conf"));
		
		foreach ( $f as $index=>$line ){
			$line=trim($line);
			if($line==null){continue;}
			if(preg_match("#nameserver\s+(.+)#", $line,$re)){
				if(trim($re[1])=="127.0.0.1"){continue;}
				$DNS[]=$re[1];
			}
		
		}
		
		return @implode(";", $DNS);
		
	}
	
	
	private function LoadNameServers():bool{
		if(!class_exists("IP")){include_once(dirname(__FILE__)."/class.tcpip.inc");}
		include_once(dirname(__FILE__) . '/class.sockets.inc');
		$dns=$this->DNS_LIST();
        $this->arrayNameServers=array();

		$dnslist=explode(";",$dns);
		$ip=new IP();
		if(is_array($dnslist)){
		    foreach ($dnslist as $val){
				$val=trim(strtolower($val));
				if($val<>null){
					if($ip->isValid($val)){
						$arr[$val]=trim($val);
					}
					
				}}
			}
			
			if(!isset($arr)){return false;}
		    if(!is_array($arr)){ return false;}


            foreach ($arr as $num=>$val){
                if($ip->isValid($val)){
						$this->arrayNameServers[]=$val;
					}
            }

        return true;

    }
	

	
}

