<?php

class SocksWeb{
	public $found=false;
	private $localid=0;
	
	public function __construct(){

	}
	
	public function GET_INFO($key){
		
		$this->localid=0;
		$this->found=false;
		if($key==null){return;}
		$q=new lib_sqlite("/home/artica/SQLITE/webconsole.db");
        $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `webconsole` (
            `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
            `master_key` TEXT UNIQUE,
            `value` TEXT)");


		$ligne=$q->mysqli_fetch_array("SELECT ID,`value` FROM `webconsole` WHERE `master_key`='$key'");
		
		if(!$q->ok){
			writelogs("FATAL! $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		}
        if(!isset($ligne["ID"])){
            $ligne["ID"]=0;
        }
		
		if(intval($ligne["ID"])>0){
			$this->found=true;
			$this->localid=intval($ligne["ID"]);
			return trim($ligne["value"]);
		}
		return null;
			
		
	}

	public function GetNumberOfParams(){
        $q=new lib_sqlite("/home/artica/SQLITE/webconsole.db");
        return intval($q->COUNT_ROWS("webconsole"));
    }

	public function ReplicParams(){
        include_once(dirname(__FILE__)."/class.sockets.inc");
        $KEYS[] = "ArticaHttpsPort";
        $KEYS[] = "LighttpdArticaListenInterface";
        $KEYS[] = "ArticaHttpUseSSL";
        $KEYS[] = "LighttpdArticaCertificateName";
        $KEYS[] = "LighttpdArticaDisableSSLv2";
        $KEYS[] = "SSLCipherSuite";
        $KEYS[] = "EnableArticaWebLogging";
        $sock   = new sockets();

        foreach ($KEYS as $masterkey){
            $value=$sock->GET_INFO($masterkey);
            $this->SET_INFO($masterkey,$value);

        }
    }

	
	public function SET_INFO($key,$value){
		
		if($key==null){return;}
		$this->GET_INFO($key);
		$q=new lib_sqlite("/home/artica/SQLITE/webconsole.db");
		if($this->localid>0){
			$value=sqlite_escape_string2($value);
			writelogs("Writing UPDATE value $key=$value keyid:$this->localid");
			$q->QUERY_SQL("UPDATE `webconsole` SET `value`='$value' WHERE ID=$this->localid");
			if(!$q->ok){echo $q->mysql_error;}
			return;
		}
		writelogs("Writing INSERT value $key=$value keyid:$this->localid");
		$q->QUERY_SQL("INSERT INTO `webconsole` (`master_key`,`value`) VALUES('$key','$value')");
		if(!$q->ok){echo $q->mysql_error;}
		
	}
	
	
}