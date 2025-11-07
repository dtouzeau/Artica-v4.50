<?php
if(!isset($GLOBALS["DEBUG_MEMCACHE"])){$GLOBALS["DEBUG_MEMCACHE"]=false;}
include_once(dirname(__FILE__)."/class.valkey.inc");
class lib_memcached{
	
	public $MemCachedFound=false;
	private $Memcached;
	public $mysql_error;
	public $ok=false;
	private $memcached_exists=true;
	
	function __construct(){

    }
	public function allKeys(){
		return $this->getAllKeys();
	}
    function getAllKeys(): array{
        $this->ok=true;
        return ValkeyClient::listKeys('*');
    }
    public function getKey($key): string{
      return ValkeyClient::getKey($key);
    }
    public function getSetting($Key):string{
        $this->ok=true;
	    return ValkeyClient::getSetting($Key);
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
		return ValkeyClient::Statistics();
	}
	
	public function SetSetting($key,$value):bool{
        $this->ok=true;
        if(is_null($value)){
            $value="";
        }
        return ValkeyClient::setSetting($key,$value, 172800);

	}
	
	public function Delkey($key):bool{
        $this->ok=true;
        return ValkeyClient::delKey($key);
	}

    private function ToSyslog($text){
        $LOG_SEV=LOG_INFO;
        if(function_exists("openlog")){openlog("memcached", LOG_PID , LOG_SYSLOG);}
        if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
        if(function_exists("closelog")){closelog();}
    }


    public function saveKey($key,$object,$ttl=0):bool{
        $this->ok=true;
        return ValkeyClient::saveKey($key,$object,$ttl);
   }
}