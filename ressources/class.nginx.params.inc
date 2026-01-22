<?php

class socksngix{
    public $serviceid=0;
	public $found=false;
	private $localid=0;
	
	public function __construct($serverid){
		$this->serviceid=intval($serverid);
	}


    public function GetServiceName():string{
        $ligne=$this->GetCache();
        if(!isset($ligne["servicename"])){
            return "Unknown";
        }
        return strval($ligne["servicename"]);
    }
    public function GetCache():array{
        if(isset($GLOBALS["/site/$this->serviceid"])){
            return $GLOBALS["/site/$this->serviceid"];
        }
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/site/$this->serviceid"),true);
        if(isset($json["config"])){
            $GLOBALS["/site/$this->serviceid"]=$json["config"];
            return $json["config"];
        }
        return array();
    }

    private function isHarmpID():bool{
        if(!isset($_SESSION["HARMPID"])){
            return false;
        }
        if(intval($_SESSION["HARMPID"])==0){
            return false;
        }

        return true;
    }

    private function NginxGetDB():string{
        if(!$this->isHarmpID()){
            return "/home/artica/SQLITE/nginx.db";
        }
        $Gpid=$_SESSION["HARMPID"];
        return "/home/artica/SQLITE/nginx.$Gpid.db";
    }

    public function GetType():int{

        $q=new lib_sqlite($this->NginxGetDB());
        $ligne=$q->mysqli_fetch_array("SELECT type FROM nginx_services WHERE ID=$this->serviceid");
        return intval($ligne["type"]);
    }
	
	public function GET_INFO($key):string{
		
		$this->localid=0;
		$this->found=false;
		if($key==null){return "";}
		$q=new lib_sqlite($this->NginxGetDB());
		$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `service_parameters` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`serviceid` INTEGER,`zkey` text,`zvalue` text)");
		$q->QUERY_SQL("CREATE INDEX IF NOT EXISTS KeyService ON service_parameters (serviceid,zkey)");
		$ligne=$q->mysqli_fetch_array("SELECT ID,zvalue FROM `service_parameters` WHERE `zkey`='$key' AND serviceid=$this->serviceid");
		
		if(!$q->ok){
			writelogs("FATAL! $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		}

        if(!isset($ligne["ID"])){
            $ligne["ID"]=0;
        }
		
		if(intval($ligne["ID"])>0){
			$this->found=true;$this->localid=intval($ligne["ID"]);
			return trim($ligne["zvalue"]);
		}
		return "";
	}
	
	public function OnOff($value){
		if(!is_numeric($value)){return $value;}
		if($value==1){return "on";}
		return "off";
		
		
	}

    public function SET_MULTI($vals=array()):bool{
        $q=new lib_sqlite($this->NginxGetDB());
        foreach($vals as $key=>$value){
            $this->GET_INFO($key);
            if($this->localid>0){
                $q->QUERY_SQL("UPDATE `service_parameters` SET `zvalue`='$value' WHERE ID=$this->localid");
                if(!$q->ok){echo $q->mysql_error;return false;}
                continue;
            }
            $q->QUERY_SQL("INSERT INTO `service_parameters` (serviceid,zkey,zvalue) VALUES('$this->serviceid','$key','$value')");
            if(!$q->ok){echo $q->mysql_error;return false;}
        }
        $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($this->serviceid);
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/flush/$this->serviceid");
        return true;
    }

	public function SET_INFO($key,$value):bool{
		
		if($key==null){return false;}
		$this->GET_INFO($key);
		$q=new lib_sqlite($this->NginxGetDB());
        $value=$q->sqlite_escape_string2($value);
		if($this->localid>0){
			$q->QUERY_SQL("UPDATE `service_parameters` SET `zvalue`='$value' WHERE ID=$this->localid");
			if(!$q->ok){echo $q->mysql_error;return false;}
            $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($this->serviceid);
            $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/flush/$this->serviceid");
			return true;
		}
		$q->QUERY_SQL("INSERT INTO `service_parameters` (serviceid,zkey,zvalue) VALUES('$this->serviceid','$key','$value')");
		if(!$q->ok){echo $q->mysql_error;return false;}
        $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($this->serviceid);
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/flush/$this->serviceid");
        return true;
	}
	
	
}