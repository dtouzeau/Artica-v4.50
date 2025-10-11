<?php
include_once(dirname(__FILE__).'/class.mysql-multi.inc');

class concrete5{
	var $groupware;
	var $ou;
	var $www_dir;
	var $ServerPort;	
	var $servername;
	var $database;
	var $uid;
	var $rebuildb=false;
	var $mysql_password=null;	
	var $mysql_username=null;
	var $mysql_instance_id=0;
	function __construct($servername){
		if($servername<>null){$this->servername=$servername;}
		$this->load();
	}
	
	private function Load(){
			$sql="SELECT * from freeweb WHERE servername='$this->servername'";
			$q=new mysql();
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			$this->groupware=$ligne["groupware"];
			$this->servername=$ligne["servername"];
			$this->ou=$ligne["ou"];
			$this->www_dir=$ligne["www_dir"];
			$this->ServerPort=$ligne["ServerPort"];
			$this->mysql_password=$ligne["mysql_password"];
			$this->mysql_username=$ligne["mysql_username"];	
			$this->mysql_instance_id=$ligne["mysql_instance_id"];		
			if($this->www_dir==null){$this->www_dir="/var/www/$this->servername";}
			writelogs("[$this->servername] CONCRETE5 mysql user=$this->mysql_username",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			
			$this->database="crt5".time();
			if($ligne["mysql_database"]<>null){$this->database=$ligne["mysql_database"];}else{
				$sql="UPDATE freeweb SET mysql_database='{$this->database}' WHERE servername='$this->servername'";
				$q->QUERY_SQL($sql,"artica_backup");
			}
			$this->uid=$ligne["uid"];
			
	}	
	
	public function CheckInstall(){
		$unix=new unix();
		$cp=$unix->find_program("cp");
		$chmod=$unix->find_program("chmod");
		$chown=$unix->find_program("chown");
		if(!$this->checkFiles()){
		    writelogs("[$this->servername] CONCRETE5 Installing core application",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			shell_exec("$cp -rf /usr/share/concrete5/* $this->www_dir/");
		}
		$apacheusername=$unix->APACHE_SRC_ACCOUNT();
		$apachegroup=$unix->APACHE_SRC_GROUP();
		
		writelogs("[$this->servername] Apply permissions ($apacheusername:$apachegroup)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$chmod -R 755 $this->www_dir");
		shell_exec("$chown -R $apacheusername:$apachegroup $this->www_dir");
		
		$this->InstallDB();
		
	}
	
	private function InstallDB(){
		$q=new mysql();
		if($this->mysql_instance_id>0){$q=new mysql_multi($this->mysql_instance_id);}
		if($this->mysql_username==null){$this->mysql_username=$q->mysql_admin;}
		if($this->mysql_password==null){$this->mysql_password=$q->mysql_password;}		
		writelogs("[$this->servername] CONCRETE5 mysql user=$this->mysql_username",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);

		}
	
	
	
	private function checkFiles(){
		$f[]="blocks";
		$f[]="concrete";
		$f[]="config";
		$f[]="controllers";
		$f[]="css";
		$f[]="elements";
		$f[]="files";
		$f[]="helpers";
		$f[]="jobs";
		$f[]="js";
		$f[]="languages";
		$f[]="libraries";
		$f[]="mail";
		$f[]="models";
		$f[]="packages";
		$f[]="page_types";
		$f[]="single_pages";
		$f[]="themes";
		$f[]="tools";
		$f[]="updates";
		
		while (list ($none, $xpath) = each ($f) ){
			$testfile="$this->www_dir/$xpath";
			if(!is_file($testfile)){
				if(!is_dir($testfile)){
					writelogs("[$this->servername] CONCRETE5 $testfile no such file or directory",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					return false;
				}
			}
			
		}
		
		return true;			
		
		
	}
	
	
}