<?php
include_once(dirname(__FILE__).'/class.sockets.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');

class drdb{
	var $ipaddr;
	var $hostname;
	var $disk;
	var $sslport;
	var $username;
	var $password;
	var $IsMe=0;
	
	function __construct($nodeip=null){
		
		if($nodeip<>null){$this->ipaddr=$nodeip;$this->loadmysqldatas();}

	}
	
	
	private function loadmysqldatas(){
		$sql="SELECT * FROM clusters WHERE ipaddr='$this->ipaddr'";
		$q=new mysql();
		$this->checkTables();
		if(!$q->ok){if($GLOBALS["VERBOSE"]){writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}return;}
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$this->hostname=$ligne["hostname"];
		$this->disk=$ligne["disk"];
		$this->sslport=$ligne["sslport"];
		$this->username=$ligne["username"];
		$this->password=$ligne["password"];
		$this->IsMe=$ligne["IsMe"];
	}
	
	
	public function SaveNode(){
		$q=new mysql();
		$sql="SELECT * FROM clusters WHERE ipaddr='$this->ipaddr'";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		if($ligne["ipaddr"]<>null){
			$sql="UPDATE clusters SET hostname='$this->hostname',
			`disk`=$this->disk,
			`sslport`=$this->sslport,
			`username`='$this->username',
			`password`='$this->password',
			`IsMe`=$this->IsMe
			WHERE `ipaddr`='$this->ipaddr'";
		}else{
			$sql="INSERT IGNORE INTO clusters (ipaddr,hostname,disk,sslport,username,password,IsMe)
			VALUES('$this->ipaddr','$this->hostname','$this->disk','$this->sslport','$this->username','$this->password','$this->IsMe')";
			
		}
		
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		
		
		
	}
	
	private function checkTables(){
		if(isset($GLOBALS[__CLASS__."/".__FUNCTION__])){return;}
		$q=new mysql();
		if(!$q->TABLE_EXISTS('clusters','artica_backup')){		
			$sql="CREATE TABLE `artica_backup`.`clusters` (
			`ipaddr` VARCHAR( 60 ) NOT NULL PRIMARY KEY,
			`hostname` VARCHAR( 128 ) NOT NULL ,
			`disk` VARCHAR( 128 ) NOT NULL ,
			`zDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`sslport` INT( 5 ) NOT NULL ,
			`username` VARCHAR( 128 ) NOT NULL ,
			`password` VARCHAR( 128 ) NOT NULL ,
			`IsMe` TINYINT( 1 ) NOT NULL ,
			INDEX ( `zDate`,`hostname`,`disk`,`IsMe` )
			)";
			$q->QUERY_SQL($sql,'artica_backup');
			if(!$q->ok){writelogs("Fatal: $q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		} 		
		$GLOBALS[__CLASS__."/".__FUNCTION__]=true;
		
		
	}
	
	
	
}