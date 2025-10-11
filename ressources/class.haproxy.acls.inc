<?php


class haproxy_acls{
	private $servicename;
	private $acls=array();
	public $acls_logs=array();
	function __construct($servicename){
		
		$this->servicename=$servicename;
		
	}
	
	
	public function buildacls(){
		
		$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
		if($q->COUNT_ROWS("haproxy_acls_rules")==0){return null;}
		
		$f=array();
		$results=$q->QUERY_SQL("SELECT * FROM haproxy_acls_rules WHERE servicename='$this->servicename' AND rule_action>0 ORDER BY zorder");
		if(count($results)==0){return null;}
		
		foreach ($results as $index=>$ligne){
			$rule_action=$ligne["rule_action"];
			$ID=$ligne["ID"];
			$rule_action_data=$ligne["rule_action_data"];
			
			if($rule_action==2){
				$backend_name=$this->backend_name($rule_action_data);
				if($backend_name==null){
					$this->acls_logs[]="# ERROR: Action: $rule_action service: $this->servicename backend_server $rule_action_data did not exists";
					continue;
					
				}
				$access="use-server $backend_name if";
				
			}
			
			
			if($rule_action==1){
				if(!$this->backend_exists(intval($rule_action_data))){
					$this->acls_logs[]="# ERROR: Action: $rule_action service: $this->servicename backend group $rule_action_data did not exists";
					continue;
				}
				$access="use_backend ".$this->backend_groupname($rule_action_data)." if";
			}
			
			
			if($rule_action==3){$access="http-request deny if ";}
			
			
			$acls=$this->buildGroups($ID);
			if($acls==null){
				$this->acls_logs[]="# ERROR: service: $this->servicename buildGroups($ID) report no group";
				continue;}
			$f[]="\t$access $acls";			
		}
		
		if(count($f)==0){return @implode("\n", $this->acls_logs);}
		return @implode("\n",$this->acls)."\n\n".@implode("\n",$f)."\n";
	}
	
	private function backend_name($ID){
		$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
		$sql="SELECT backendname FROM haproxy_backends WHERE servicename='$this->servicename' AND ID='$ID' AND enabled=1";
		$ligne=$q->mysqli_fetch_array($sql);
		return $ligne["backendname"];
		
	}
	
	private function backend_groupname($ID){
		$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
		$ligne=$q->mysqli_fetch_array("SELECT groupname FROM haproxy_backends_groups WHERE servicename='$this->servicename' AND ID='$ID'");
		return $ligne["groupname"];
	}
	
	private function backend_exists($ID){
		$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
		$sql="SELECT ID,groupname FROM haproxy_backends_groups WHERE servicename='$this->servicename' AND ID='$ID'";
		$ligne=$q->mysqli_fetch_array($sql);
		if(intval($ligne["ID"])==0){return false;}
		if($this->backends_gpid_count($ID)==0){return false;}
		return true;
	}
	
	private function backends_gpid_count($gpid){
		$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
		$table="(SELECT haproxy_backends_link.ID as tid,
		haproxy_backends.* FROM haproxy_backends_link,haproxy_backends WHERE
		haproxy_backends.ID=haproxy_backends_link.backendid AND
		haproxy_backends_link.gpid='$gpid' ORDER BY bweight) as t";
	
		$sql="SELECT count(*) as tcount FROM $table";
		$ligne=$q->mysqli_fetch_array($sql);
		return intval($ligne["tcount"]);
		
	}	
	
	function buildGroups($ruleid){
		$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
		$f=array();
		$table="SELECT haproxy_acls_link.groupid,
		haproxy_acls_link.ID as tid,
		haproxy_acls_link.revert,
		haproxy_acls_link.operator,
		haproxy_acls_link.zorder as torder,
		haproxy_acls_groups.* FROM haproxy_acls_link,haproxy_acls_groups
		WHERE haproxy_acls_link.groupid=haproxy_acls_groups.ID AND
		haproxy_acls_link.ruleid=$ruleid AND
		haproxy_acls_groups.enabled=1
		ORDER BY haproxy_acls_link.zorder";
		
		$results = $q->QUERY_SQL($table);
		$this->acls_logs[]="#\tINFO: Rule:$ruleid $this->servicename report ".count($results)." groups";
		
		$c=0;
		foreach ($results as $index=>$ligne){
			$revert=$ligne["revert"];
			$revert_text=null;
			$operator_text=null;
			if($revert==1){$revert_text="!";}
			$operator=$ligne["operator"];
			$grouptype=$ligne["grouptype"];
			$this->acls_logs[]="#\tGroup Type=$grouptype";
			if($grouptype=="hdr(host)"){$grouptype="hdr_sub(host)";}
			
			$items=$this->builditem($ligne["ID"]);
			if($items==0){
				$this->acls_logs[]="# ERROR: Group:{$ligne["ID"]} Rule:$ruleid $this->servicename report no items";
				continue;}
			if($operator==1){$operator_text="|| ";}
			if($c==0){$operator=null;}
			$this->acls[]="\tacl Group{$ligne["ID"]} $grouptype -f /etc/haproxy/acls/Group{$ligne["ID"]}.acl";
			
			$f[]="{$operator_text}{$revert_text}Group{$ligne["ID"]}";
			$c++;
		}
		if(count($f)==0){return null;}
		return @implode(" ", $f);
	}
	
	private function builditem($gpid){
		$q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
		$f=array();
		if(!is_dir("/etc/haproxy/acls")){@mkdir("/etc/haproxy/acls",0755,true);}
		@unlink("/etc/haproxy/acls/Group{$gpid}.acl");
		$sql="SELECT * FROM haproxy_acls_items WHERE groupid='$gpid'";
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){$this->acls_logs[]="# ERROR: Group:{$gpid} $this->servicename MySQL error $q->mysql_error";}
		foreach ($results as $index=>$ligne){
			$pattern=$ligne["pattern"];
			$f[]=$pattern;
		}
		
		if(count($f)==0){return 0;}
		@file_put_contents("/etc/haproxy/acls/Group{$gpid}.acl", @implode("\n", $f)."\n");
		return count($f);
	}
	
}

