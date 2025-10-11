<?php
if(!isset($GLOBALS["DEBUG_MEMCACHE"])){$GLOBALS["DEBUG_MEMCACHE"]=false;}
class lib_memcached{
	
	public $MemCachedFound=false;
	private $Memcached;
	public $mysql_error;
	public $ok=false;
	private $memcached_exists=true;
	
	function __construct(){

    }
    /**
     * (PECL memcached &gt;= 2.0.0)<br/>
     * Gets the keys stored on all the servers
     * @link https://php.net/manual/en/memcached.getallkeys.php
     * @return array|false the keys stored on all the servers on success or <b>FALSE</b> on failure.
     */

	public function allKeys(){
		return $this->getAllKeys();
	}
    private function Connect():bool{
        if (!extension_loaded("redis")) {
            $this->memcached_exists=false;
            if(function_exists("VERBOSE")) {
                VERBOSE("Redis not loaded",__LINE__);
            }
            return false;
        }

        try {
            $this->Memcached = new Redis();
            $socketPath = "/run/redis/redis.sock";
            if(!$this->Memcached->connect($socketPath)){
                writelogs("Connection to Redis socket error" ,__FUNCTION__,__FILE__,__LINE__);
            }
            return true;
        } catch (Exception $e) {
            $this->memcached_exists=false;
            if(!isset($GLOBALS["REDIS_ERROR"][$e->getMessage()])) {
                writelogs("Connection to Redis error: " . $e->getMessage(), __FUNCTION__, __FILE__, __LINE__);
                $GLOBALS["REDIS_ERROR"][$e->getMessage()]=true;
            }
            return false;
        }
    }



    function getAllKeys(): array{

        $this->ok=true;

        if(!$this->Connect()){
            $this->ok=false;
            return array();
        }

        $allKeys = $this->Memcached->keys('*');

        if ($this->Memcached) {
            $this->Memcached->close();
        }

        return $allKeys;
    }

    private function ParseResultCode($resultCode):bool{
        if ($resultCode==16) {
            $this->ok=false;
            $this->MemCachedFound=false;
            return true;
        }

        if ($resultCode==47) {

            $this->ok=false;
            $this->MemCachedFound=false;
            if(function_exists("VERBOSE")){
                VERBOSE("Set error $resultCode,".$this->ErrorCodeToString($resultCode),__LINE__);
            }
            return true;
        }

        return false;

    }
	
	public function getKey($key):string{
        if(!$this->Connect()){
            $this->ok=false;
            return "";
        }

		if(!$this->memcached_exists){
		    $this->ok=false;
		    return "";
        }
		$this->ok=true;

        try {
            $tmpval = $this->Memcached->get($key);
        } finally {
            // Ensure that the connection is closed
            if ($this->Memcached) {
                $this->Memcached->close();
            }
        }

        if ($tmpval === false) {
            $this->ok=false;
            $this->MemCachedFound=false;
            $this->mysql_error="$key does not exist";
            $this->saveKey($key,"",);
            return "";
        }
        $this->MemCachedFound=true;
        if(is_array($tmpval)){
            return serialize($tmpval);
        }
		$value=trim($tmpval);
        if($value=="!nil"){
            return "";
        }
		return $value;
	}
	

	public function getSetting($Key):string{
        $this->ok=true;


	    $value=$this->getKey("SET:$Key");
	    if(!$this->MemCachedFound){
           list($result,$val)=$this->GetInfoFile($Key);
            if ($result){
                $this->MemCachedFound=true;
                $val=trim($val);
                if($val=="!nil"){
                    $this->SetSetting("SET:$Key","");
                    return "";
                }
                $this->SetSetting("SET:$Key",$val);
                return $val;
            }
            return "";
        }
	    return trim($value);
		
	}
    private function GetInfoFile($key):array{

        $FileKeyPath="/etc/artica-postfix/settings/Daemons/$key";
        if(!is_file($FileKeyPath)){return array(true,"");}

        if(!is_readable($FileKeyPath)) {
            return array(false,"");
        }

        $fp = fopen($FileKeyPath, "rb");//open file in read mode
        if(!$fp){
            $this->ToSyslog("Fatal!! $FileKeyPath fopen failed");
            return array(false,"");
        }

        $str = stream_get_contents($fp);
        fclose($fp);
        return array(true,trim((string) $str));

    }
	
	public function Statistics():array{
		if(!$this->memcached_exists){
            $this->ok=false;
            $this->mysql_error="Memcached exists! false";
            return array();
        }
        $this->ok=true;
		$data=$this->Memcached->getStats();

		if(!$data){
            $resultCode=$this->Memcached->getResultCode();
            if($this->ParseResultCode($resultCode)){return array();}
            $this->ok=false;
            $this->MemCachedFound=false;
            $this->mysql_error=$this->ErrorCodeToString($resultCode);
            $line=__LINE__;
            $this->ToSyslog("[L.$line]: [Statistics] Code:$resultCode $this->mysql_error");
            return array();
        }
        if(!is_array($data)){
            return array();
        }

		return $data;
	}
	
	public function SetSetting($key,$value):bool{
		if(!$this->memcached_exists){
		    $this->mysql_error="memcached_exists return false;";
		    return false;
		}

        return $this->saveKey("SET:$key", $value);

	}
	
	public function Delkey($key):bool{

        if(!$this->Connect()){
            $this->ok=false;
            return false;
        }

        $this->Memcached->del($key);

        if ($this->Memcached) {
            $this->Memcached->close();
        }
        return true;
	}

    private function ToSyslog($text){
        $LOG_SEV=LOG_INFO;
        if(function_exists("openlog")){openlog("memcached", LOG_PID , LOG_SYSLOG);}
        if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
        if(function_exists("closelog")){closelog();}
    }


    public function saveKey($key,$object,$ttl=0):bool{
        if(!isset($GLOBALS["DEBUG_MEMCACHE"])){$GLOBALS["DEBUG_MEMCACHE"]=false;}
        $this->ok = true;

        if(!$this->Connect()){
            $this->ok=false;
            return false;
        }

        if (!$this->memcached_exists) {
            if ($GLOBALS["DEBUG_MEMCACHE"]) {
                if ($GLOBALS["VERBOSE"]) {
                    echo "Memcached did not exists...\n";
                }
            }
            $this->ok = false;
            return false;
        }

        try {
            if($ttl==0){
                $ttl=172800;
            }


            if(is_array($object)){
                 writelogs("Fatal \"$key\" try to save an Array??",__FUNCTION__,__FILE__,__LINE__);
                 return false;
            }


            if($object=="" || $object==null || strlen($object)==0){
                $this->Delkey($key);
                if ($this->Memcached) {
                    $this->Memcached->close();
                }
                return true;
            }

            $object=trim($object);
            $this->Memcached->set($key, $object, $ttl);
            if ($this->Memcached) {
                $this->Memcached->close();
            }

        } catch (Exception $e) {
            $this->ok = false;
            $this->mysql_error=$e->getMessage();
            writelogs("Fatal $this->mysql_error",__FUNCTION__,__FILE__,__LINE__);
        }

        return true;


    }
	


	
}