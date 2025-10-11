<?php

class dnsfiltersocks{
	private $IDFound=0;
	private $LOGS=array();
	
	public function __construct(){
	
	}
	
	public function GET_INFO($key){
		$this->IDFound=0;
		
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$ligne=$q->mysqli_fetch_array("SELECT `zvalue`,`ID` FROM `DNSFilterSettings` WHERE `zKey`='$key'");
		$this->LOGS[]="SELECT `zvalue`,`ID` FROM `DNSFilterSettings` WHERE `zKey`='$key'";
		if(!$q->ok){$this->LOGS[]=$q->mysql_error;}
		if(isset($ligne["ID"])){
			$this->LOGS[]="$key ID = {$ligne["ID"]}";
			$this->IDFound=intval($ligne["ID"]);
			if($this->IDFound==0){return null;}
			return base64_decode($ligne["zvalue"]);
		}
		$this->LOGS[]="$key ID = None";
		
	}
	
	public function SET_INFO($key,$value){
		$this->LOGS=array();
		$this->GET_INFO($key);
		$value=base64_encode(trim($value));
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$this->LOGS[]="$key IDFound = $this->IDFound";
		if($this->IDFound==0){
			$q->QUERY_SQL("INSERT INTO `DNSFilterSettings` (`zKey`,`zvalue`) VALUES ('$key','$value')");
			if(!$q->ok){echo $q->mysql_error."\n".@implode("\n", $this->LOGS);return false;}
			return true;
		}
		$q->QUERY_SQL("UPDATE `DNSFilterSettings` SET `zvalue`='$value' WHERE ID=$this->IDFound");
		if(!$q->ok){echo $q->mysql_error."\n".@implode("\n", $this->LOGS);return false;}
		return true;
	}
	
}