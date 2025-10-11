<?php


class ClassUsersMacs extends Threaded{

	private $query;
	private $MyThread;
	private $SquidParameters=array();
	private $Whitelist=array();
	private $NET_IPADDRS=array();
	private $fam;
	private $catzz;
	private $USERNAME=null;
	private $IPADDR1=null;
	private $MACADDR=null;
	private $IPADDR2=null;
	private $CHANNEL=0;
	private $FULL_URL=null;
	private $WEBSITE_SNI=null;
	private $whitelist=array();
	private $memcached_notfound=false;
	private $memcachedFound=false;
	private $DEBUG=false;
	
	public function __construct($url,$debug){
		
		if($debug){echo "[".__LINE__."]: __construct(...\n";}
		$this->DEBUG=$debug;
		if($debug){echo "[".__LINE__."]: __construct($url)...\n";}
		$this->query = $url;
		if($debug){echo "[".__LINE__."]: FillParameters(...\n";}
		$this->FillParameters();
		if($debug){echo "[".__LINE__."]: load_global_whitelists(...\n";}
		$this->load_global_whitelists();
		
	}
	
	
	private function FillParameters(){
		
		$this->MyThread=Thread::getCurrentThreadId();
		$this->fam=new familysite();
		$this->catzz=new mysql_catz();
		
		$SquidHelperParams=$this->getMemory("SquidHelperParams");
		if($this->memcachedFound){
			if(is_array($SquidHelperParams)){
				//$this->SquidParameters=$SquidHelperParams;
				return;
			}
		}
		
		$this->SquidParameters["UfdbgclientSockTimeOut"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbgclientSockTimeOut"));
		$this->SquidParameters["UseCloudArticaCategories"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseCloudArticaCategories"));
		$this->SquidParameters["CloudArticaCategoriesOutgoing"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CloudArticaCategoriesOutgoing"));
		$this->SquidParameters["CategoryItemsInMemory"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHotCacheSize"));
		$this->SquidParameters["SquidDebugMainHelper"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDebugMainHelper"));
		
		if($this->SquidParameters["UfdbgclientSockTimeOut"]==0){$this->SquidParameters["UfdbgclientSockTimeOut"]=2;}
		if($this->SquidParameters["CategoryItemsInMemory"]==0){ $this->SquidParameters["CategoryItemsInMemory"]=5000;}


		$this->SquidParameters["SquidDebugMainHelper"]=1;
		$this->WLOG("Done..." .count($this->SquidParameters)." parameters",__FUNCTION__);
		
	}
	public function run(){
		$this->USERNAME=null;
		$this->IPADDR1=null;
		$this->MACADDR=null;
		$this->IPADDR2=null;
		$this->CHANNEL=0;
		$this->FULL_URL=null;
		$this->WEBSITE_SNI=null;
		$this->ParseLine();
		
		echo "$this->CHANNEL OK\n";
		
		
	}
	
	
	
	private function ParseLine(){
		
		$re=preg_split("/[\s]+/", $this->query);
		$this->CHANNEL=$re[0];
		$this->USERNAME=trim($re[1]);
		$this->IPADDR1=trim($re[2]);
		$this->MACADDR=strtolower(trim($re[3]));
		$this->IPADDR2=trim($re[4]);
		
		if($this->USERNAME=="-"){$this->USERNAME=null;}
		if($this->IPADDR2=="-"){$this->IPADDR2=null;}
		if($this->MACADDR=="00:00:00:00:00:00"){$this->MACADDR=null;}
		if($this->MACADDR=="-"){$this->MACADDR=null;}
		if($this->IPADDR2<>null){if($this->IPADDR2<>$this->IPADDR1){$this->IPADDR1=$this->IPADDR2;}}
		
		$this->WEBSITE=trim($re[5]);
		$this->WEBSITE_SNI=trim($re[6]);
		if(isset($re[7])){$this->FULL_URL=$re[7];}
		
		if($this->FULL_URL=="-"){$this->FULL_URL=null;}
		if($this->WEBSITE_SNI=="-"){$this->WEBSITE_SNI=null;}
		if($this->WEBSITE_SNI<>null){$this->WEBSITE=$this->WEBSITE_SNI;}
		
		$this->WLOG("Channel: $this->CHANNEL Mac:$this->MACADDR  $this->WEBSITE",__FUNCTION__,true);
		
	}
	
	private function getMemory($key){

		if($this->DEBUG){echo "[".__LINE__."]: getMemory($key)\n";}
		if(!class_exists("Memcached")){
			if($this->DEBUG){echo "[".__LINE__."]: Memcached No such class\n";}
			$this->WLOG("Memcached No such class!");
			$this->memcached_notfound=true;
			$this->memcachedFound=false;
			return;
		}
		
		$this->memcached_notfound=false;
		if($this->DEBUG){echo "[".__LINE__."]: Memcached -> Build class\n";}
		$Memcached = new Memcached();
		if($this->DEBUG){echo "[".__LINE__."]: Memcached -> addServer()\n";}
		$Memcached->addServer('/var/run/memcached.sock', 0);
		if($this->DEBUG){echo "[".__LINE__."]: Memcached -> get($key)\n";}
		
		$value=$Memcached->get($key);
		if($this->DEBUG){echo "[".__LINE__."]: Memcached -> getResultCode()\n";}
		
		$resultCode=$Memcached->getResultCode();
		$Memcached=null;
		if($this->DEBUG){echo "[".__LINE__."]: Memcached -> getResultCode($resultCode)\n";}
		
		if ($resultCode==16) {
			if($this->DEBUG){echo "[".__LINE__."]: Memcached -> NOT FOUND\n";}
			$this->memcached_notfound=true;
			return null;
		}
		if($this->DEBUG){echo "[".__LINE__."]: Memcached -> FOUND\n";}
		$this->memcachedFound=true;
		return $value;
	}
	
	private function setMemory($key,$value){
		
		if(!class_exists("Memcached")){
			$this->WLOG("Memcached No such class!");
			$this->memcached_notfound=true;
			return;
		} 
		$Memcached = new Memcached();
		$Memcached->addServer('/var/run/memcached.sock', 0);
		$Memcached->set($key,$value);
	}
	
	private function load_global_whitelists(){





		
		$cached=$this->getMemory("SquidHelperWhiteLists");
		
		
		if($this->memcachedFound){
			if($this->DEBUG){echo "[".__LINE__."]: Memcached -> memcachedFound -> populate \n";}
			if(is_array($cached)){
				//$this->whitelist=$cached;
				return;
			}
		}

		$this->whitelist["highcharts.com"]=true;
		$this->whitelist["amazonaws.com"]=true;
		$this->whitelist["orange.fr"]=true;
		$this->whitelist["salesforce.com"]=true;
		$this->whitelist["force.com"]=true;
		$this->whitelist["akamaihd.net"]=true;
		$this->whitelist["nvidia.com"]=true;
		$this->whitelist["wistia.net"]=true;
		$this->whitelist["ebay.com"]=true;
		$this->whitelist["jqueryscript.net"]=true;
		$this->whitelist["microsoft.com"]=true;
        $this->whitelist["cloudfront.net"]=true;

	
		$f=explode("\n",@file_get_contents("/etc/squid3/acls_whitelist.dstdomain.conf"));
		foreach ($f as $line){
			$line=trim($line);
			if(strlen($line)<4){continue;}
			$line=str_replace("^","",$line);
			if(substr($line,0,1)=="."){$line=substr($line, 1,strlen($line));}
			$line=trim($line);
			$this->whitelist[$line]=true;
		}

		$this->setMemory("SquidHelperWhiteLists", $this->whitelist);
		$this->WLOG(count($this->whitelist)." Whitelisted websites...");
	
	}
	
	
	private function WLOG($text=null,$function=null,$debug=false){
		
		if($debug){
			if($this->SquidParameters["SquidDebugMainHelper"]==0){return;}
		}
		
		$handle= @fopen("/var/log/squid/MacToUid.log", 'a');
		$function_text="-";
		
			
			
		
		$date=@date("Y-m-d H:i:s"). substr(microtime(), 1, 9);
		if (is_file("/var/log/squid/MacToUid.log")) {
			$size=@filesize("/var/log/squid/MacToUid.log");
			if($size>1000000){
				@fclose($handle);
				unlink("/var/log/squid/MacToUid.log");
				$handle = @fopen("/var/log/squid/MacToUid.log", 'a');
			}
			 
			 
		}
	
		if($function<>null){$function_text=$function;}
		@fwrite($handle, "$date [{$this->MyThread}]: $function_text $text\n");
		@fclose($handle);
	}
}


/*
 * 00 = MEMCACHED_SUCCESS
01 = MEMCACHED_FAILURE
02 = MEMCACHED_HOST_LOOKUP_FAILURE // getaddrinfo() and getnameinfo() only
03 = MEMCACHED_CONNECTION_FAILURE
04 = MEMCACHED_CONNECTION_BIND_FAILURE // DEPRECATED see MEMCACHED_HOST_LOOKUP_FAILURE
05 = MEMCACHED_WRITE_FAILURE
06 = MEMCACHED_READ_FAILURE
07 = MEMCACHED_UNKNOWN_READ_FAILURE
08 = MEMCACHED_PROTOCOL_ERROR
09 = MEMCACHED_CLIENT_ERROR
10 = MEMCACHED_SERVER_ERROR // Server returns "SERVER_ERROR"
11 = MEMCACHED_ERROR // Server returns "ERROR"
12 = MEMCACHED_DATA_EXISTS
13 = MEMCACHED_DATA_DOES_NOT_EXIST
14 = MEMCACHED_NOTSTORED
15 = MEMCACHED_STORED
16 = MEMCACHED_NOTFOUND
17 = MEMCACHED_MEMORY_ALLOCATION_FAILURE
18 = MEMCACHED_PARTIAL_READ
19 = MEMCACHED_SOME_ERRORS
20 = MEMCACHED_NO_SERVERS
21 = MEMCACHED_END
22 = MEMCACHED_DELETED
23 = MEMCACHED_VALUE
24 = MEMCACHED_STAT
25 = MEMCACHED_ITEM
26 = MEMCACHED_ERRNO
27 = MEMCACHED_FAIL_UNIX_SOCKET // DEPRECATED
28 = MEMCACHED_NOT_SUPPORTED
29 = MEMCACHED_NO_KEY_PROVIDED  Deprecated. Use MEMCACHED_BAD_KEY_PROVIDED! 
30 = MEMCACHED_FETCH_NOTFINISHED
31 = MEMCACHED_TIMEOUT
32 = MEMCACHED_BUFFERED
33 = MEMCACHED_BAD_KEY_PROVIDED
34 = MEMCACHED_INVALID_HOST_PROTOCOL
35 = MEMCACHED_SERVER_MARKED_DEAD
36 = MEMCACHED_UNKNOWN_STAT_KEY
37 = MEMCACHED_E2BIG
38 = MEMCACHED_INVALID_ARGUMENTS
39 = MEMCACHED_KEY_TOO_BIG
40 = MEMCACHED_AUTH_PROBLEM
41 = MEMCACHED_AUTH_FAILURE
42 = MEMCACHED_AUTH_CONTINUE
43 = MEMCACHED_PARSE_ERROR
44 = MEMCACHED_PARSE_USER_ERROR
45 = MEMCACHED_DEPRECATED
46 = MEMCACHED_IN_PROGRESS
47 = MEMCACHED_SERVER_TEMPORARILY_DISABLED
48 = MEMCACHED_SERVER_MEMORY_ALLOCATION_FAILURE
49 = MEMCACHED_MAXIMUM_RETURN /* Always add new error code before 
11 = MEMCACHED_CONNECTION_SOCKET_CREATE_FAILURE = MEMCACHED_ERROR
 */