<?php
include_once(dirname(__FILE__).'/class.mysql.inc');

class auditd{
	
	
	function __construct(){
		
		
	}
	
	function KeyAudited($path){
		
		$tbl=explode('/',$path);
		
		$q=new mysql();
		for($i=1;$i<count($tbl);$i++){
			if($tbl[$i]==null){continue;}
			$pp=$pp."/".$tbl[$i];
			$sql="SELECT `key` FROM `auditd_dir` WHERE dir='$pp'";
			$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
			writelogs($sql,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);		
			if(strlen($ligne["key"])>0){return $ligne["key"];}
		}
		return null;
	}
	
	function GetPath($key){
		$q=new mysql();
		$sql="SELECT `dir` FROM `auditd_dir` WHERE `key`='$key';";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		return $ligne["dir"];
	}
	
	function parseConfig(){
		$sock=new sockets();
		$datas=explode("\n",base64_decode($sock->getFrameWork("cmd.php?auditd-config=yes")));
		foreach ($datas as $num=>$ligne){
			if(preg_match("#(.+?)=(.+)#",$ligne,$re)){
				$ARRAY[trim($re[1])]=trim($re[2]);
			}
		}
		
		return $ARRAY;
		
	}
	
	
	function ENTRIES_NUMBER($key){
		$q=new mysql();
		$sql="SELECT COUNT(ID) as tcount FROM auditd_files WHERE `key_path`='$key'";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		return $ligne["tcount"];
	}
	
}
?>