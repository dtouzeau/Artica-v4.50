<?php
	include_once(dirname(__FILE__).'/class.ldap.inc');
	include_once(dirname(__FILE__).'/class.templates.inc');
	include_once(dirname(__FILE__).'/class.mysql.inc');
	
class gluster_samba{
	var $clients=array();
	var $PARAMS_CLIENTS=array();
	var $STATUS_CLIENTS=array();
	var $CLUSTERED_DIRECTORIES=array();
	
	public function __construct(){
		$this->GetDirectoryList();
		$this->GetClustersClientsList();
	}
	
	
	private function GetDirectoryList(){
		$sql="SELECT cluster_path FROM gluster_paths";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		$CountDeDir=mysqli_num_rows($results);
		if($GLOBALS["EXECUTED_AS_ROOT"]){echo "Starting......: ".date("H:i:s")." Gluster Daemon $CountDeDir clustered directories from MySQL database\n";}
		
		while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
			if($ligne["cluster_path"]==null){continue;}
			if(!is_dir($ligne["cluster_path"])){
				if($GLOBALS["EXECUTED_AS_ROOT"]){echo "Starting......: ".date("H:i:s")." Gluster Daemon creating new directory {$ligne["cluster_path"]}\n";}
				@mkdir($ligne["cluster_path"],0755,true);
				if(!is_dir($ligne["cluster_path"])){
					if($GLOBALS["EXECUTED_AS_ROOT"]){echo "Starting......: ".date("H:i:s")." Gluster Daemon creating new directory {$ligne["cluster_path"]} permission denied\n";}
					continue;
				}
			}
			$this->CLUSTERED_DIRECTORIES[]=$ligne["cluster_path"];
		}
		
	}
	private function GetClustersClientsList(){
		$sql="SELECT client_ip FROM glusters_clients WHERE client_notified=1";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
			if($ligne["client_ip"]==null){continue;}
			
			$this->clients[]=$ligne["client_ip"];
		}
	}
	
	private function BuildVolumes(){
		if(!is_array($this->CLUSTERED_DIRECTORIES)){
			if($GLOBALS["EXECUTED_AS_ROOT"]){echo "Starting......: ".date("H:i:s")." Gluster Daemon CLUSTERED_DIRECTORIES is not an array (failed)\n";}
			
			return null;}
		
		reset($this->CLUSTERED_DIRECTORIES);
		if($GLOBALS["EXECUTED_AS_ROOT"]){echo "Starting......: ".date("H:i:s")." Gluster Daemon ". count($this->CLUSTERED_DIRECTORIES)." clustered directories\n";}
		
		while (list ($index, $path) = each ($this->CLUSTERED_DIRECTORIES) ){
			$f[]="volume posix-$index";
			$f[]="\ttype storage/posix";
			$f[]="\toption directory $path";
			$f[]="end-volume";
			$f[]="";
		}
		
		reset($this->CLUSTERED_DIRECTORIES);
		while (list ($index, $path) = each ($this->CLUSTERED_DIRECTORIES) ){
			$f[]="volume locks-$index";
			$f[]="\ttype features/locks";
			$f[]="\tsubvolumes posix-$index";
			$f[]="end-volume";
			$f[]="";
		}
		
		reset($this->CLUSTERED_DIRECTORIES);
		while (list ($index, $path) = each ($this->CLUSTERED_DIRECTORIES) ){
			$bricks[]="brick-$index";
			$bricks_auth[]="\toption auth.addr.brick-$index.allow *";
			$bricks_sql[]="INSERT INTO gluster_clients_brick (brickname,source) VALUES('brick-$index','$path')";
			$f[]="volume brick-$index";
			$f[]="\ttype performance/io-threads";
			$f[]="\toption thread-count 8";
			$f[]="\tsubvolumes locks-$index";
			$f[]="end-volume";
			$f[]="";
		}
		$this->bricksql($bricks_sql);
		
		reset($this->CLUSTERED_DIRECTORIES);
		$f[]="volume server";
		$f[]="\ttype protocol/server";
		$f[]="\toption transport-type tcp";
		$f[]="\tsubvolumes ". @implode(" ",$bricks);
		$f[]=@implode("\n",$bricks_auth);
		$f[]="end-volume";
		$f[]="";
		
		
		return @implode("\n",$f);
				
		
	}
	
	private function bricksql($array){
		$q=new mysql();
		$q->QUERY_SQL("TRUNCATE TABLE `gluster_clients_brick`","artica_backup");
		while (list ($index, $sql) = each ($array) ){
			$q->QUERY_SQL($sql,"artica_backup");
		}
		
	}
	
	public function build(){
		$volumes=$this->BuildVolumes();
		return $volumes;
	}
	
	
}


class gluster_volume{
	var $ID=0;
	var $volume_name="";
	var $volume_type="";
	var $state="";
	
	function gluster_volume($volid=0){
		
		if($volid>0){
			$this->ID=$volid;
			$this->load();
		}
		
	}
	
	
	private function load(){
		$q=new mysql();
		$sql="SELECT * FROM glusters_volumes WHERE ID='$this->ID'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));			
		$this->volume_name=$ligne["volume_name"];
		$this->volume_type=$ligne["volume_type"];
		$this->state=$ligne["state"];
	}
	
	
	
}




class gluster_client{
	var $master_array=array();
	var $directories=array();
	var $hostname=null;
	var $client_ip=null;
	var $uuid=null;
	var $state=null;
	var $ID;
	function gluster_client($hostname=null){
		if($hostname<>null){
			$this->hostname=$hostname;
			$this->load();
		}
		
		
		
	}
	
	
	private function load(){
		$q=new mysql();
		$sql="SELECT * FROM glusters_clients WHERE hostname='$this->hostname'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));		
		if($ligne["hostname"]==null){
			if($GLOBALS["VERBOSE"]){echo "$this->hostname have no entry\n";}	
			$sql="SELECT * FROM glusters_clients WHERE client_ip='$this->hostname'";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			if($ligne["client_ip"]==null){
				if($GLOBALS["VERBOSE"]){echo "$this->hostname have no entry (by ip)\n";}	
				return;
			}
		}
		$this->state=$ligne["state"];
		$this->uuid=$ligne["uuid"];
		$this->hostname=$ligne["hostname"];
		$this->client_ip=$ligne["client_ip"];
		$this->ID=$ligne["ID"];
		
	}
	
	public function add_client(){
		$sql="INSERT IGNORE INTO glusters_clients (hostname,client_ip,uuid) VALUES('$this->hostname','$this->client_ip','$this->uuid')";
		$q=new mysql();
		$q->CheckTables_gluster();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return false;}
		return true;
	}
	
	public function edit_client(){
		$q=new mysql();
		$this->state=addslashes($this->state);
		$sql="UPDATE glusters_clients SET uuid='$this->uuid',`state`='$this->state' WHERE hostname='$this->hostname'";
		if($GLOBALS["VERBOSE"]){echo "$this->hostname: $sql\n";}
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return false;}
		return true;
	}
	
	public function remove(){
		$q=new mysql();
		$q->QUERY_SQL("DELETE FROM glusters_clients WHERE ID=$this->ID","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return false;}
		return true;		
	}
	
	
	
	
	public function implode_bricks(){
		$VOLS=null;
		$sql="SELECT * FROM glusters_servers";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$serverip=$ligne["server_ip"];
			writelogs("parameters={$ligne["parameters"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$array=unserialize(base64_decode($ligne["parameters"]));
			if(!is_array($array["PATHS"])){
				writelogs("NO PATHS for $serverip ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				continue;
			}
			while (list ($brickname, $path) = each ($array["PATHS"]) ){
				if(trim($path)==null){continue;}
				writelogs("$path -> $serverip:$brickname",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$VOLS[$path][]=array("BRICKNAME"=>$brickname,"SERVER"=>$serverip);
			}
			
		}
		
		return $VOLS;
	}
	
	function buildconf(){
		$vols=$this->implode_bricks();
		if($GLOBALS["VERBOSE"]){print_r($vols);}
		shell_exec("/bin/rm /etc/artica-cluster/glusterfs-client/* >/dev/null 2>&1");
		if(!is_array($vols)){return null;}
		$path_count=0;
		while (list ($path, $array_infos) = each ($vols) ){
			$path_count=$path_count+1;
			writelogs("Check servers for $path ($path_count)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			unset($subvolumes);
			$count=0;
			while (list ($index, $infos) = each ($array_infos) ){
				$count=$count+1;
				$subvolumes[]="remote{$count}";
				$f[]="#LOCAL_PATH:$path";
				$f[]="#replicate{$path_count} -> {$infos["SERVER"]}:{$infos["BRICKNAME"]}";
				$f[]="volume remote{$count}";
				$f[]="\ttype protocol/client";
				$f[]="\toption transport-type tcp";
 				$f[]="\toption remote-host {$infos["SERVER"]}";
 				$f[]="\toption remote-subvolume {$infos["BRICKNAME"]}";
				$f[]="end-volume";
				$f[]="";
				
				
			}

			
			/*	$f[]="volume iocache";
			$f[]="\ttype performance/io-cache";
			$f[]="\toption cache-size `echo $(( $(grep 'MemTotal' /proc/meminfo | sed 's/[^0-9]//g') / 5120 ))`MB";
			$f[]="\toption cache-timeout 1";
			$f[]="\tsubvolumes readahead";
			$f[]="end-volume";	
			$f[]="";
			
			$f[]="volume readahead";
			$f[]="\ttype performance/read-ahead";
			$f[]="\toption page-count 2";
			$f[]="\toption page-size 1MB";
			$f[]="\tsubvolumes replicate{$path_count}";
			$f[]="end-volume";			
			$f[]="";
			*/
			
			$f[]="volume replicate{$path_count}";
  			$f[]="\ttype cluster/replicate";
  			$f[]="\tsubvolumes ". implode(" ",$subvolumes);
			$f[]="end-volume";
			$f[]="";			
			
			$f[]="volume writebehind";
			$f[]="\ttype performance/write-behind";
			$f[]="\toption window-size 1MB";
			$f[]="\tsubvolumes replicate{$path_count}";
			$f[]="end-volume";
			$f[]="";
			$f[]="volume cache";
			$f[]="\ttype performance/io-cache";
			$f[]="\toption cache-size 512MB";
			$f[]="\tsubvolumes writebehind";
			$f[]="end-volume";	
			$f[]="";
			@mkdir("/etc/artica-cluster/glusterfs-client",null,true);
			@file_put_contents("/etc/artica-cluster/glusterfs-client/$path_count.vol",@implode("\n",$f));
			echo "Starting......: ".date("H:i:s")." Gluster clients $path_count.vol configuration done..\n";
			unset($f);
			
		}
		
		
		
	}
	
	function volToPath($path){
		$f=explode("\n",@file_get_contents($path));
		foreach ( $f as $index=>$line ){
			if(preg_match("#LOCAL_PATH:(.+)#",$line,$re)){
				return trim($re[1]);
			}
		}
	}
	function ismounted($path,$volume=null){
		$unix=new unix();
		$umount=$unix->find_program("umount");
		$pathString=string_to_regex($path);
		$f=explode("\n",@file_get_contents("/proc/mounts"));
		foreach ( $f as $index=>$line ){
			if(preg_match("#([0-9]+)\.vol\s+$pathString\s+fuse\.glusterfs#",$line,$re)){
				$finded_volume=$re[1];
				if(is_numeric($volume)){
					if($volume<>$finded_volume){
						if($GLOBALS["VERBOSE"]){echo "Mounted path $path with volume $finded_volume did not match specified volume: $volume\n";}
						$GLOBALS["GLUSTERS_EV"][]="Mounted path $path with volume $finded_volume did not match specified volume: $volume";
						shell_exec("$umount -l $path");
						return false;
					}
				}else{
					if($GLOBALS["VERBOSE"]){echo "specified volume `$volume` for $path is not an integer...\n";}
				}
				
				if($GLOBALS["VERBOSE"]){echo "ismounted():: Found volume:$finded_volume line $line OK \"#$pathString\s+fuse\.glusterfs#\"\n";}
				return true;
			}
			
		}
		
	}
	
	function CheckPath($path){
		if(!is_dir($path)){
			@mkdir($path,0755,true);
			return true;
		}
		
		$array=glob("$path/*");
		if(count($array)>0){
			$GLOBALS["GLUSTERS_EV"][]="$path is not empty ! aborting";
			writelogs("$path is not empty ! aborting",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
		
		return true;
	}
	
	function mount($path,$volfile){
		$unix=new unix();
		$mount=$unix->find_program("mount");
		exec("$mount -t glusterfs $volfile $path",$results);
		foreach ($results as $index=>$line){
			if(trim($line)==null){continue;}
			echo "Starting......: ".date("H:i:s")." Gluster clients $line\n";
		}
	}
	
	function get_mounted(){
		$array=array();
		$f=explode("\n",@file_get_contents("/proc/mounts"));
		foreach ( $f as $index=>$line ){
			if(preg_match("#^(.+?)\s+.+?\s+fuse\.glusterfs#",$line,$re)){
				$array[]=$re[1];
			}
		}
		return $array;
	}
	
}



?>