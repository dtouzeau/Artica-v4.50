<?php


function GET_INFO($key){
	if(!file_exists("/etc/artica-postfix/settings/Daemons/$key")){return null;}
	$data=file_get_contents("/etc/artica-postfix/settings/Daemons/$key");
	$data=trim($data);
	return $data;
	}

class mysql_cron{
	var $database;
	var $bd;
	
	
function __construct(){
	$this->bd=$this->mysqlOpen();
}
	
function mysqlOpen(){
		if(!is_file(dirname(__FILE__) . '/settings.inc')){die("DIE " .__FILE__." Line: ".__LINE__);}
		include(dirname(__FILE__) . '/settings.inc');
		$mysql_server=$_GLOBAL["mysql_server"];
		$mysql_admin=$_GLOBAL["mysql_admin"];
		$mysql_password=$_GLOBAL["mysql_password"];
		$mysql_port=$_GLOBAL["mysql_port"];	
		
		if(($mysql_server=="localhost") OR ($mysql_server=="127.0.0.1")){
			$bd=@mysqli_connect($mysql_server,$mysql_admin,$mysql_password,null,$mysql_port,"/var/run/mysqld/mysqld.sock");
		}else{
			$bd=@mysqli_connect($mysql_server,$mysql_admin,$mysql_password,null,$mysql_port);
		}		
		
		if(!$bd){
    		$des=mysqli_error();
    		die("Unable to connect to mysql server $des");
		}
		
		return $bd;	
	
	
}	

function QUERY_SQL($sql){
		$database=$this->database;
		$ok=@mysqli_select_db($this->bd,$database);
    	if (!$ok){
    		$errnum=mysqli_error();
    		$des=mysqli_error();
    		echo "Error Myslq Number ($errnum) ($des) ".__FUNCTION__.'/'.__FILE__."\n";
    		return null;
    
    	}	

    $results=mysqli_query($this->bd,$sql);
	
		if(mysqli_error()){
				$errnum=mysqli_error();
				$des=mysqli_error();
				echo "Error Myslq Number ($errnum) ($des) ".__FUNCTION__.'/'.__FILE__."\n";
				
		}	
	return $results;
}	
}