<?php
include_once(dirname(__FILE__)."/class.mysql.squid.builder.php");
class squid_report{
	private $ID=0;
	private $tablename="TrackMembers";
	var $report="";
	var $userfield="";
	var $userdata="";
	var $categories_hash=array();
	var $sitename_hash=array();
	var $duration=null;
	var $csv=0;
	var $scheduled=0;
	var $csvContentBytes=0;
	var $error;
	
	function __construct($ID){
		if(!is_numeric($ID)){$ID=0;}
		if($ID>0){$this->ID=$ID;$this->load();}
		
		
	}
	
	
	private function load(){
		$q=new mysql_squid_builder();
		$sql="SELECT `ID`,`report`,`userfield`,`userdata`,`categories`,
		`sitename`,`duration`,`scheduled`,`csv`,LENGTH(csvContent) as `csvContentBytes`
		FROM `$this->tablename` WHERE `ID`=$this->ID";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){$this->error=$q->mysql_error;}
		$this->report=utf8_encode($ligne["report"]);
		$this->userfield=$ligne["userfield"];
		$this->userdata=$ligne["userdata"];
		$this->categories_hash=unserialize(base64_decode($ligne["categories"]));
		$this->sitename_hash=unserialize(base64_decode($ligne["sitename"]));
		$this->duration=$ligne["duration"];
		$this->scheduled=$ligne["scheduled"];
		$this->csv=$ligne["csv"];
		$this->csvContentBytes=$ligne["csvContentBytes"];
	}
	
	public function Save(){
		if($this->report==null){$this->report="New report ".time();}
		$this->report=addslashes($this->report);
		if($this->ID==0){
			$sql="INSERT IGNORE INTO $this->tablename (`report`,`userfield`,`userdata`) VALUES ('$this->report','$this->userfield','$this->userdata')";
		}else{
			$sql="UPDATE $this->tablename SET 
			`report`='$this->report',
			`userfield`='$this->userfield',
			`userdata`='$this->userdata',
			`scheduled`='$this->scheduled', 
			`csv`='$this->csv'  
			WHERE ID=$this->ID";
		}
		
		$q=new mysql_squid_builder();
		$q->CheckTables();
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
		
	}
	
	public function explain(){
		$f[]="{compile_statistics} {from} $this->userdata ($this->userfield)";
		if($this->csv==1){$f[]="{create_csv_content}";}
		return @implode("<br>{and} ",$f);
		
	}
	
	public function BuildQuery($sourcetable){
		$userfield=$this->userfield;
		$userdata=$this->userdata;
		if($userfield=="ipaddr"){$userfield="CLIENT";}
		$OP="=";
		if(strpos("  $userdata", "*")>0){
			$OP=" LIKE ";
			$userdata=str_replace("*", "%", $userdata);
		}
		
		$sql="SELECT sitename,SUM(hits) as hits,SUM(QuerySize) as size,DATE_FORMAT(zDate,'%Y-%m-%d') as zDate,$userfield as source
		FROM `$sourcetable` GROUP BY sitename,zDate,source HAVING source$OP'$userdata'";
		//sitename 	ID 	uri 	TYPE 	REASON 	CLIENT 	hostname 	zDate 	zMD5 	uid 	remote_ip 	country 	QuerySize 	hits 	cached 	MAC 	account
		return $sql;
	}
	public function BuildQueryBlock($sourcetable){
		$userfield=$this->userfield;
		$userdata=$this->userdata;
		if($userfield=="ipaddr"){$userfield="client";}
		$OP="=";
		if(strpos("  $userdata", "*")>0){
			$OP=" LIKE ";
			$userdata=str_replace("*", "%", $userdata);
		}
		
		$sql="SELECT website as `sitename`,`category`,`rulename`,`event`,`why`,`explain`,`blocktype`,COUNT(ID) as hits,DATE_FORMAT(zDate,'%Y-%m-%d') as zDate,
		$userfield as source
		FROM `$sourcetable` GROUP BY `sitename`,`category`,`rulename`,`event`,`why`,`explain`,`blocktype`,`zDate`,`source` HAVING `source`$OP'$userdata'";
		
		return $sql;		
	}
	
	public function set_duration($text){
		$text=addslashes($text);
		$q=new mysql_squid_builder();
		$sql="UPDATE $this->tablename SET `duration`='$text' WHERE ID=$this->ID";
		$q->QUERY_SQL($sql);
		
	}
	
	public function loadcsv(){
		$q=new mysql_squid_builder();
		$sql="SELECT csvContent FROM `$this->tablename` WHERE `ID`=$this->ID";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));	
		return $ligne["csvContent"];
		
	}
	 	 	 	
	
	
}
