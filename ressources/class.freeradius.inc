<?php

class freeradius{

	
	
	
	
	public function GroupSave($groupname,$id=0){
		$q=new mysql();
		$groupname=$this->mysql_escape_string2($groupname);
		if($id>0){
			$sql="UPDATE radgroupcheck SET groupname='$groupname' WHERE id=$id";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo "Line: ".__LINE__."\n".$q->mysql_error;return;}
		
			$sql="UPDATE radusergroup SET groupname='$groupname' WHERE gpid=$id";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo "Line: ".__LINE__."\n".$q->mysql_error;return;}
		
			$sql="UPDATE radgroupreply SET groupname='$groupname' WHERE gpid=$id";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo "Line: ".__LINE__."\n".$q->mysql_error;return;}
		
		
			return;
		}
		
		$sql="INSERT IGNORE INTO radgroupcheck  (`groupname`, `attribute`,`op`, `value`)
		VALUES ('$groupname', 'Auth-Type',':=', 'Accept');";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Line: ".__LINE__."\n".$q->mysql_error;}
		
		$gpid=$q->last_id;
	}
	
	public function GroupCheckValue($gpid,$item){
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT `value` FROM radgroupcheck WHERE id={$gpid} AND `attribute`='$item'","artica_backup"));
		return $ligne["value"];
		
	}
	
	private function mysql_escape_string2($line){
		
		$search=array("\\","\0","\n","\r","\x1a","'",'"');
		$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
		return str_replace($search,$replace,$line);
		
	if(function_exists("mysql_real_escape_string")){
		return mysql_real_escape_string($line);
	}
	return mysql_escape_string2($line);

}
	
	
	public function GroupDelete($gpid=0){
		$q=new mysql();
		$q->QUERY_SQL("DELETE FROM radgroupcheck WHERE id={$gpid}","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$q->QUERY_SQL("DELETE FROM radgroupreply WHERE gpid={$gpid}","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$q->QUERY_SQL("UPDATE radusergroup SET groupname='',gpid=0 WHERE gpid={$gpid}","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	public function MemberDelete($userid=0){
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT username FROM radcheck WHERE id={$userid}","artica_backup"));
		$UserName=$ligne["UserName"];
		$UserName=$this->mysql_escape_string2($UserName);
		$q->QUERY_SQL("DELETE FROM radcheck WHERE id={$userid}","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$q->QUERY_SQL("DELETE FROM radusergroup WHERE username='$UserName'","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	
	public function MemberSave($UserName,$Password=null,$id=0,$gpid=0){
		$Password=$this->mysql_escape_string2($Password);
		$UserName=$this->mysql_escape_string2($UserName);
		$sql="INSERT IGNORE INTO radcheck (`username`, `attribute`, `value`) VALUES ('$UserName', 'Cleartext-Password', '$Password');";
		if($id>0){
			$sql="UPDATE radcheck SET `username`='$UserName',
			`value`='$Password',
			`attribute`='Cleartext-Password' WHERE id=$id";
		}
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$sql="DELETE FROM radusergroup WHERE `username`='$UserName'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		if($gpid>0){
			$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT groupname FROM radgroupcheck WHERE id={$gpid}","artica_backup"));
			$ligne["groupname"]=$this->mysql_escape_string2($ligne["groupname"]);
			$sql="insert into radusergroup (username, groupname, priority,gpid)
			VALUES ('$UserName', '{$ligne["groupname"]}', 1,$gpid);";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error;return;}
			}		
	}
	
	
	
}