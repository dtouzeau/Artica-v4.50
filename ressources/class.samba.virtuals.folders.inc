<?php
class samba_virtuals_folders{
	Public $ID;
	Public $hostname;
	Public $ou;
	Public $share_name;
	Public $share_path;
	Public $share_params=array();
	Public $RootDir;
	Public $ASROOT=false;

	function __construct($ID=0){
		$this->ID=intval($ID);
		if($ID>0){$this->load();}

	}

	private function load(){
		if(!is_numeric($this->ID)){return false;}
		$sql="SELECT * FROM samba_hosts_share WHERE ID='$this->ID'";
		writelogs("Loading ID:$this->ID parameters",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$q=new mysql();
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		if(!$q->ok){if($GLOBALS["VERBOSE"]){writelogs("Mysql error: $q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}
		$this->hostname=$ligne["hostname"];
		$this->share_name=$ligne["share_name"];
		$this->share_path=$ligne["share_path"];
		$this->share_params=unserialize(base64_decode($ligne["share_params"]));
		$this->Setdefault();
		$sql="SELECT * FROM samba_hosts WHERE hostname='$this->hostname'";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$this->ou=$ligne["ou"];
		$this->RootDir=$ligne["RootDir"];


	}

	public function DeleteShare(){
		$sql="DELETE FROM samba_hosts_share WHERE ID='$this->ID'";
		$q=new mysql();
		if(!$q->QUERY_SQL($sql,"artica_backup")){echo $q->mysql_error;return;}
		$sock=new sockets();
		$sock->getFrameWork('cmd.php?samba-save-config=yes');
	}


	private function Setdefault(){
		$smb=new samba();
		if($this->share_params["nt_acl_support"]==null){$this->share_params["nt_acl_support"]=$smb->main_array["global"]["nt acl support"];}
		if($this->share_params["acl_group_control"]==null){$this->share_params["acl_group_control"]=$smb->main_array["global"]["acl group control"];}
		if($this->share_params["map_acl_inherit"]==null){$this->share_params["map_acl_inherit"]=$smb->main_array["global"]["map acl inherit"];}
		if($this->share_params["inherit_acls"]==null){$this->share_params["inherit_acls"]=$smb->main_array["global"]["inherit acls"];}
		if($this->share_params["dos_filemode"]==null){$this->share_params["dos_filemode"]=$smb->main_array["global"]["dos filemode"];}

		if($this->share_params["create_mask"]==null){$this->share_params["create_mask"]= "0775";}
		if($this->share_params["directory_mask"]==null ){$this->share_params["directory_mask"]= "0777";}
		if($this->share_params["force_create_mode"]==null){$this->share_params["force_create_mode"] = "0775";}
		if($this->share_params["create_mode"]==null){$this->share_params["create_mode"]=$this->share_params["create mask"];}
		reset($this->share_params);
		while (list ($num, $ligne) = each ($this->share_params) ){
			if($ligne=="yes"){$this->share_params[$num]=1;}
			if($ligne=="no"){$this->share_params[$num]=0;}
		}

	}

	function compile(){
		if($GLOBALS["VERBOSE"]){writelogs("Starting......: ".date("H:i:s")." Samba [$this->hostname] compile [{$this->share_name}]",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		reset($this->share_params);
		unset($this->share_params["folder-id"]);
		$maps["nt_acl_support"]="nt acl support";
		$maps["acl_group_control"]="acl group control";
		$maps["map_acl_inherit"]="map acl inherit";
		$maps["inherit_acls"]="inherit acls";
		$maps["dos_filemode"]="dos filemode";
		$maps["create_mask"]="create mask";
		$maps["directory_mask"]="directory mask";
		$maps["force_create_mode"]="force create mode";
		$maps["create_mode"]="create mode";
		$maps["hide_unreadable"]="hide unreadable";
		$maps["hide_unwriteable_files"]="hide unwriteable files";
		$this->share_params["browseable"]=$this->share_params["browsable"];

		$f[]="[$this->share_name]";
		$f[]="\tpath = $this->share_path";

		while (list ($num, $ligne) = each ($this->share_params) ){
			if($ligne==1){$this->share_params[$num]="yes";}
			if($ligne==0){$this->share_params[$num]="no";}
		}

		reset($this->share_params);
		while (list ($num, $ligne) = each ($this->share_params) ){
				
			if($maps[$num]<>null){
				$f[]="\t{$maps[$num]} = $ligne";
				if($GLOBALS["VERBOSE"]){writelogs("Starting......: ".date("H:i:s")." Samba [$this->hostname] {$maps[$num]} = $ligne",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				continue;
			}
			$f[]="\t$num = $ligne";
			if($GLOBALS["VERBOSE"]){writelogs("Starting......: ".date("H:i:s")." Samba [$this->hostname] $num = $ligne",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		}


		$f[]="";

		return @implode("\n", $f);


	}


	function Save(){
		$q=new mysql();
		$this->ID=intval($this->ID);
		$sql="SELECT RootDir FROM samba_hosts WHERE hostname='$this->hostname'";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));

		if($ligne["RootDir"]<>null){
			if(strpos("    ".$this->share_path, $ligne["RootDir"])==0){echo "$this->share_path Not authorized\n";return false;}
		}
		writelogs("Saving ID:$this->ID parameters",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$share_params=base64_encode(serialize($this->share_params));
		$sql="UPDATE samba_hosts_share SET
		share_name='$this->share_name',
		share_params='$share_params',
		share_path='$this->share_path' WHERE ID='$this->ID'";

		if($this->ID==0){
			$sql="SELECT ID FROM samba_hosts_share WHERE hostname='$this->hostname' AND share_name='$this->share_name'";
			$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
			if($ligne["ID"]>0){
				echo "Cannot add this folder\n$this->share_name is already used";
				return false;
			}
				
			$sql="INSERT INTO samba_hosts_share (share_name,share_path,hostname,share_params) VALUES (
			'$this->share_name',
			'$this->share_path',
			'$this->hostname',
			'$share_params'
			)
			";
				
		}

		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\nFile:". basename(__FILE__)."\nLine:".__LINE__;return false;}
		return true;
		$sock=new sockets();
		$sock->getFrameWork('cmd.php?samba-save-config=yes');
	}


}
?>