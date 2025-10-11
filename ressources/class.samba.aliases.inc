<?php
include_once(dirname(__FILE__)."/class.mysql.inc");;
include_once(dirname(__FILE__)."/class.samba.inc");
include_once(dirname(__FILE__)."/class.samba.virtuals.folders.inc");
class samba_aliases{
	Public $hostname="";
	Public $ou="";
	Public $workgroup="";
	Public $RootDir="";
	Public $ipaddr=null;
	Public $main_array=array();
	Public $SAMBA_VERSION=0;
	Public $upTo36=0;
	Public $upTo357=0;
	Public $ASROOT=false;
	
	public function __construct($hostname=null){
		if($hostname<>null){
			$this->hostname=$hostname;
			$this->load();
		
		}
		
	}
	
	private function load(){
        return false;
        // A VOIR
		$users=new usersMenus();
		$this->SAMBA_VERSION=$users->SAMBA_VERSION;
		$sql="SELECT * FROM samba_hosts WHERE hostname='$this->hostname'";
		$q=new mysql();
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$this->ou=$ligne["ou"];
		$this->workgroup=$ligne["workgroup"];
		$this->RootDir=$ligne["RootDir"];
		$this->ipaddr=$ligne["ipaddr"];
		$this->main_array=unserialize(base64_decode($ligne["global_settings"]));
		
		$version=$users->SAMBA_VERSION;
		if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)#",$version,$re)){$major=intval($re[1]);$minor=intval($re[2]);$build=intval($re[3]);}	
		if($major>2){if($minor>4){if($build>6){$this->upTo357=1;}}}
	
	
		if(preg_match("#^([0-9]+)\.([0-9]+)#", $version,$re)){
			$major=intval($re[1]);
			$minor=intval($re[2]);
			if($major>=3){if($minor>=6){$this->upTo36=1;$this->upTo357=1;}}
	}		
		
		$this->buildGlobalDefault();
		if($GLOBALS["VERBOSE"]){writelogs("Starting......: ".date("H:i:s")." Samba [$this->hostname] v.$users->SAMBA_VERSION loading settings done...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	}
	
	private function buildGlobalDefault(){
		if(!isset($this->main_array["global"]["strict_allocate"])){$this->main_array["global"]["strict_allocate"]=0;}
		if(!isset($this->main_array["global"]["strict_locking"])){$this->main_array["global"]["strict_locking"]=1;}
		if(!isset($this->main_array["global"]["sync_always"])){$this->main_array["global"]["sync_always"]=0;}
		if(!isset($this->main_array["global"]["getwd_cache"])){$this->main_array["global"]["getwd_cache"]=1;}
		if(!isset($this->main_array["global"]["smb2_protocol"])){$this->main_array["global"]["smb2_protocol"]=1;}
		
		
		
		if(!is_numeric($this->main_array["global"]["strict_allocate"])){$this->main_array["global"]["strict_allocate"]=0;}
		if(!is_numeric($this->main_array["global"]["strict_locking"])){$this->main_array["global"]["strict_locking"]=0;}
		if(!is_numeric($this->main_array["global"]["sync_always"])){$this->main_array["global"]["sync_always"]=0;}
		if(!is_numeric($this->main_array["global"]["getwd_cache"])){$this->main_array["global"]["getwd_cache"]=1;}
		if(!is_numeric($this->main_array["global"]["smb2_protocol"])){$this->main_array["global"]["smb2_protocol"]=1;}
		
		
		if($this->upTo36==0){$this->main_array["global"]["smb2_protocol"]=0;}
		
	}
	
	public function SaveGlobals(){
		$sql="SELECT hostname FROM samba_hosts WHERE hostname='$this->hostname'";
		$q=new mysql();
		if(!$q->ok){if($GLOBALS["VERBOSE"]){writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return;}}
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		
		$global_settings=base64_encode(serialize($this->main_array));
		$global_settings=addslashes($global_settings);
		if($ligne["hostname"]==null){
			$sql="INSERT IGNORE INTO samba_hosts (hostname,global_settings) VALUES ('$this->hostname','$global_settings')";
		}else{
			$sql="UPDATE samba_hosts SET global_settings='$global_settings' WHERE hostname='$this->hostname'";
		}		
		
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?restart-samba=yes");
	}
	
	
	private function CheckListenIP(){

		if($this->ipaddr==null){return;}
		echo "Starting......: ".date("H:i:s")." Samba [$this->hostname]: checks network settings for $this->ipaddr\n";
		$unix=new unix();
		$ips=$unix->ifconfig_all_ips();
		$q=new mysql();
		if(isset($ips[$this->ipaddr])){echo "Starting......: ".date("H:i:s")." Samba [$this->hostname]: checks network settings OK\n";return;}
		$sql="SELECT * FROM nics_virtuals WHERE ipaddr='$this->ipaddr'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
		if($ligne["ipv6"]==1){return;}	
		if($ligne["ID"]==0){echo "Starting......: ".date("H:i:s")." Samba [$this->hostname]: unable to stat infos for '$this->ipaddr'\n";return;}
		echo "Starting......: ".date("H:i:s")." Samba [$this->hostname]: Apply network configuration for {$ligne["nic"]}:{$ligne["ID"]}\n";
		$ifconfig=$unix->find_program("ifconfig");
		$route=$unix->find_program("route");		
		exec("$ifconfig {$ligne["nic"]}:{$ligne["ID"]} $this->ipaddr 2>&1",$results);
		exec("$route add -host $this->ipaddr dev {$ligne["nic"]}:{$ligne["ID"]} 2>&1",$results);	
		foreach ($results as $num=>$ligne){
			echo "Starting......: ".date("H:i:s")." Samba [$this->hostname]:$ligne\n";
		}	
	}
	
	
	public function build(){
		
		
		$this->CheckListenIP();
		
		
		$sock=new sockets();
		$profile_path=$sock->GET_INFO('SambaProfilePath');
		if(trim($profile_path)==null){$profile_path="/home/export/profile";}
		$hostname=$this->hostname;
		if(strlen($hostname)>19){$hostname=substr($hostname, 0,19);}
		$f[]="[global]";
		$f[]="\tnetbios name = $hostname";
		$f[]="\tworkgroup = $this->workgroup";
		if($this->upTo357==0){
			if($this->main_array["global"]["strict_allocate"]==1){$f[]="\tstrict allocate = Yes";}else{$f[]="\tstrict allocate = No";}
		}
		if($this->main_array["global"]["strict_locking"]==1){$f[]="\tstrict locking = Auto";}else{$f[]="\tstrict locking = No";}
		if($this->main_array["global"]["sync_always"]==1){$f[]="\tsync always = Yes";}else{$f[]="\tsync always = No";}
		if($this->main_array["global"]["getwd_cache"]==1){$f[]="\tgetwd cache = Yes";}else{$f[]="\tgetwd cache = No";}
		if($this->main_array["global"]["smb2_protocol"]==1){$f[]="\tmax protocol = SMB2";}else{$f[]="\tmax protocol = NT1";}
		
		
		
		
		
		if($this->ipaddr<>null){
			$f[]="\tinterfaces = $this->ipaddr";
			$f[]="\tsocket address = $this->ipaddr";
			$f[]="\tbind interfaces only = yes";
		}
		
		
		if($this->ou<>null){
				$ldap=new clladp();
				$ldap_admin_dn="cn={$ldap->ldap_admin},{$ldap->suffix}";
				$suffix= $ldap->suffix;
				$ldap_group_suffix="dc=organizations";
				$ldap_user_suffix="ou=$this->ou,dc=organizations";
				$ldap_machine_suffix="ou=Computer,dc=samba,dc=organizations";
				$idmap_ldap_base_dn="ou=idmap,dc=samba,dc=organizations,{$ldap->suffix}";
				$usessl="off";
				$ldap_uri="ldap://{$ldap->ldap_host}:{$ldap->ldap_port}";				
				$f[]="\tldap delete dn = no";
				$f[]="\tpassdb backend = ldapsam:$ldap_uri";
				$f[]="\tadd machine script = /usr/share/artica-postfix/bin/artica-install --samba-add-computer \"%u\"";				
				$f[]="\tldap admin dn = $ldap_admin_dn";
				$f[]="\tldap suffix = $suffix";
				$f[]="\tldap group suffix = $ldap_group_suffix";
				$f[]="\tldap user suffix =  $ldap_user_suffix";			
				$f[]="\tldap machine suffix = $ldap_machine_suffix";
				$f[]="\tldap idmap suffix = $idmap_ldap_base_dn";
				$f[]="\tldap delete dn = yes";
				$f[]="\tencrypt passwords = true";
				$f[]="\tldap ssl  = off";	
			
			
		}
		
		$f[]="";
		$f[]="[homes]";
		$f[]="\tcomment = Personnal Folder on $this->hostname";
		$f[]="\tbrowseable = No";
		$f[]="\twriteable = Yes\n";

		$f[]="[netlogon]";
		$f[]="\tpath = /home/netlogon/";
		$f[]="\twritable = No";
		$f[]="\tbrowseable = No";
		$f[]="\twrite list = root";		
		
		$f[]="[profiles]";
		$f[]="\tpath = $profile_path";
		$f[]="\toplocks = false";
		$f[]="\tlevel2 oplocks = false";
		$f[]="\tcsc policy = disable";
		$f[]="\tbrowseable = No";
		$f[]="\twriteable = Yes";
		$f[]="\tread only = No";
		$f[]="\tprofile acls = Yes";
		$f[]="\tcreate mask = 0700";
		$f[]="\tdirectory mask = 0700";
		$f[]="\tcomment = Roaming user profile storage";
		$f[]="\thide files=/Desktop.ini/Thumbs.db/lost+found";
		$f[]="";
		$f[]="[profiles.V2]";
	    $f[]="\tcopy = profiles";
	    $f[]="\tbrowseable = No";
	    $f[]="\tcsc policy = disable";	        		
	    $f[]="";
		$f[]="[profile]";
	    $f[]="\tcopy = profiles";
	    $f[]="\tbrowseable = No";			
		
		$q=new mysql();
		$sql="SELECT ID FROM samba_hosts_share WHERE hostname='$this->hostname'";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){if($GLOBALS["VERBOSE"]){writelogs("Starting......: ".date("H:i:s")." Samba [$this->hostname] $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}
		if($GLOBALS["VERBOSE"]){writelogs("Starting......: ".date("H:i:s")." Samba [$this->hostname] ". mysqli_num_rows($results)." rows",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
			
			if($GLOBALS["VERBOSE"]){writelogs("Starting......: ".date("H:i:s")." Samba [$this->hostname] loading folder [{$ligne["ID"]}]",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$ss=new samba_virtuals_folders($ligne["ID"]);
			$f[]=$ss->compile();	
			
		}
		
		
		
		$f[]="";
		if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." Samba [$this->hostname] build configuration done\n";}
		@file_put_contents("/etc/samba/virtual/smb.conf.$this->hostname", @implode("\n", $f));
		if($this->ipaddr<>null){@file_put_contents("/etc/samba/virtual/smb.conf.$this->ipaddr", @implode("\n", $f));}
	}
	
	public function Edit(){
		$q=new mysql();
		$q->BuildTables();
	
		
		$sql="SELECT hostname FROM samba_hosts WHERE hostname='{$this->hostname}'";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
		
		$sql="INSERT INTO samba_hosts (
			hostname,
			RootDir,
			workgroup,
			ou,
			ipaddr) VALUES (
			'{$this->hostname}',
			'{$this->RootDir}',
			'{$this->workgroup}',
			'{$this->ou}',
			'{$this->ipaddr}'
			);";	
		
		if($ligne["hostname"]<>null){
			$sql="UPDATE samba_hosts SET 
				RootDir='$this->RootDir',
				workgroup='{$this->workgroup}',
				ou='{$this->ou}',
				ipaddr='$this->ipaddr'
				WHERE hostname='{$this->hostname}'";
				
				
		}
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return false;}
		$sock=new sockets();
		$sock->getFrameWork('cmd.php?samba-save-config=yes');
		return true;
	
	}		
		
		
	
	
}





