<?php
include_once(dirname(__FILE__) . "/class.templates.inc");
include_once(dirname(__FILE__) . "/class.ldap.inc");
include_once(dirname(__FILE__) . "/class.ini.inc");
include_once(dirname(__FILE__) . "/class.groups.inc");
include_once(dirname(__FILE__) . "/class.user.inc");


class autofs{
	var $uuid=null;
	var $hash_by_dn=array();
	
	function __construct($uuid=null,$Noldap=false){
		if(!$Noldap){$this->CreateBranchs();}
		$this->uuid=$uuid;
		
	}
	
	private function CreateBranchs(){
	if(!function_exists("ldap_connect")){return array();}
	$EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
	if($EnableOpenLDAP==0){return false;}
	$ldap=new clladp();
	if(!$ldap->ldap_perform_connection()){
		return false;
	}
	$mustrestart=false;
	if(!$ldap->ExistsDN($ldap->suffix)){
		echo "<H3>Something strange... &laquo;$ldap->suffix&raquo; does not exists in LDAP database...</H3>";
		return;
	}
	
	
	$dn="ou=mounts,$ldap->suffix";
	$upd=array();
		if(!$ldap->ExistsDN($dn)){
			$upd["objectClass"][]="top";
			$upd["objectClass"][]="organizationalUnit";
			$upd["ou"][]="mounts";
			$ldap->ldap_add($dn,$upd);
		}else{
			if($GLOBALS["VERBOSE"]){echo "$dn OK\n";}
			
		}
		unset($upd);


		$dn="ou=auto.master,ou=mounts,$ldap->suffix";
		if(!$ldap->ExistsDN($dn)){
			$upd["objectClass"][]="top";
			$upd["objectClass"][]="automountMap";
			$upd["ou"][]="auto.master";
			$ldap->ldap_add($dn,$upd);
			$mustrestart=true;
			$upd=array();
		}else{
			if($GLOBALS["VERBOSE"]){echo "$dn OK\n";}
		}
		
		
		$dn="cn=/automounts,ou=auto.master,ou=mounts,$ldap->suffix";
		if(!$ldap->ExistsDN($dn)){
			$upd["objectClass"][]="top";
			$upd["objectClass"][]="automount";
			$upd["cn"][]="/automounts";
			$upd["automountInformation"][]="ldap:$ldap->ldap_host:ou=auto.automounts,ou=mounts,$ldap->suffix -g";
			$ldap->ldap_add($dn,$upd);
			$mustrestart=true;
			$upd=array();
		}else{
			if($GLOBALS["VERBOSE"]){echo "$dn OK\n";}
		}

		$dn="ou=auto.automounts,ou=mounts,$ldap->suffix";
		if(!$ldap->ExistsDN($dn)){
			$upd["objectClass"][]="top";
			$upd["objectClass"][]="automountMap";
			$upd["ou"][]="auto.automounts";
			if(!$ldap->ldap_add($dn,$upd)){
				echo __FILE__."\n".__LINE__. "\n$ldap->ldap_last_error\n";
			}else{
				$mustrestart=true;
			}
			$upd=array();
		}else{
			if($GLOBALS["VERBOSE"]){echo "$dn OK\n";}
		}

		if($mustrestart){
			$sock=new sockets();
			$sock->getFrameWork("cmd.php?autofs-restart=yes");
		}
		
	}
	
	public function nfs_add($folder,$server,$mount_point){
		$ldap=new clladp();
		$dn="cn=$mount_point,ou=auto.automounts,ou=mounts,$ldap->suffix";
		$pattern="-fstype=nfs,rw,nfs,hard,intr,nodev,nosuid $server:$folder";
		if(!$ldap->ExistsDN($dn)){
						$upd["objectClass"][]="top";
						$upd["objectClass"][]="automount";
						$upd["cn"][]="$mount_point";
						$upd["automountInformation"][]=$pattern;
						//"-fstype=nfs,rw,soft,sync,tcp 192.168.1.154:/home/papa";
						if(!$ldap->ldap_add($dn,$upd)){
								echo __FILE__."\n".__LINE__. "\n$ldap->ldap_last_error\n";
								return false;
							}
						return true;	
						
					}
				$upd["automountInformation"][]=$pattern;
				if(!$ldap->Ldap_modify($dn,$upd)){
								echo __FILE__."\n".__LINE__. "\n$ldap->ldap_last_error\n";
								return false;
							}
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?autofs-reload=yes");							
						return true;				
		
	}
	
	public function by_uuid_addmedia($folder,$type){
			if($folder==null){return null;}	
			if($this->uuid==null){echo "UUid=null!\n";}
			$ldap=new clladp();
			if($type=="vfat"){
				$pattern="-fstype=vfat,fmask=0666,dmask=0755  UUID=\"$this->uuid\"";
			}
			
			if($type=="ext4"){
				$pattern="-fstype=ext4,noatime,commit=60,grpid UUID=\"$this->uuid\"";
			}

			if($type=="ext3"){
				$pattern="-fstype=ext3,rw UUID=\"$this->uuid\"";
			}	
			if($type=="ntfs"){
				$pattern="-fstype=ntfs,ro UUID=\"$this->uuid\"";
			}
			
			if($type=="auto"){
				$pattern="-fstype=auto,noatime,grpid,rw,sync,user_xattr,quota,acl,usrquota UUID=\"$this->uuid\"";
			}			

	
			 
			if($pattern==null){
				$tpl=new templates();
				echo $tpl->_ENGINE_parse_body('{error_understand_fs} '.$type);
				return false;
			}
			if($GLOBALS["VERBOSE"]){echo "autofs: creating/updating folder $folder\n";}
			$upd=array();
			$dn="cn=$folder,ou=auto.automounts,ou=mounts,$ldap->suffix";
			if(!$ldap->ExistsDN($dn)){
				$upd["objectClass"][]="top";
				$upd["objectClass"][]="automount";
				$upd["cn"][]="$folder";
				$upd["automountInformation"][]=$pattern;
				//"-fstype=nfs,rw,soft,sync,tcp 192.168.1.154:/home/papa";
				if(!$ldap->ldap_add($dn,$upd)){echo __FILE__."\n".__LINE__. "\n$ldap->ldap_last_error\n";return false;}
				$sock=new sockets();
				$sock->getFrameWork("cmd.php?autofs-reload=yes");					
				return true;	
				
			}
		$upd["automountInformation"][]=$pattern;
		if(!$ldap->Ldap_modify($dn,$upd)){echo __FILE__."\n".__LINE__. "\n$ldap->ldap_last_error\n";return false;}
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?autofs-reload=yes");					
		return true;		
				
		
	}
	
	public function nfs_delete($mount){
		$ldap=new clladp();
		$dn="cn=$mount,ou=auto.automounts,ou=mounts,$ldap->suffix";
		$ldap->ldap_delete($dn);
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?autofs-reload=yes");		
	}
	
	public function by_uuid_removemedia($folder,$type){
		$ldap=new clladp();
		$pattern="(&(ObjectClass=automount)(cn=$folder))";
		$attr=array("automountInformation","cn","dn");
		$suffix="ou=mounts,$ldap->suffix";
		$sr =@ldap_search($ldap->ldap_connection,$suffix,$pattern,$attr);
		$hash=ldap_get_entries($ldap->ldap_connection,$sr);
		if($hash["count"]>0){
			
			for($i=0;$i<$hash["count"];$i++){
				$automountinformation=$hash[$i]["automountinformation"][0];
				if(preg_match("#UUID.+?$this->uuid\"#",$automountinformation)){
					if(!$ldap->ldap_delete($hash[$i]["dn"])){
						echo "Error:".__FILE__."\nLine:".__LINE__."\n".$hash[$i]["dn"]."\n".$ldap->ldap_last_error;
						return ;
					}
					
				}
			}
		}
		
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?autofs-reload=yes");
	}
	
	public function list_nfs(){
		$ldap=new clladp();
		$pattern="(&(ObjectClass=automount)(automountInformation=*))";
		$attr=array("automountInformation","cn");
		$suffix="ou=mounts,$ldap->suffix";
		$sr =@ldap_search($ldap->ldap_connection,$suffix,$pattern,$attr);
		$hash=ldap_get_entries($ldap->ldap_connection,$sr);
		//print_r($hash);
		//automountinformation
		if($hash["count"]>0){
			for($i=0;$i<$hash["count"];$i++){
				$automountinformation=$hash[$i]["automountinformation"][0];				
				if(preg_match("#fstype=nfs.+?\s+(.+)#",$automountinformation,$re)){
					$arr[$hash[$i]["cn"][0]]=$re[1];
				}
			}
		}
		
		if(!is_array($arr)){return array();}
		return $arr;
	}	
	
	public function list_byuuid($uuid){
		$ldap=new clladp();
		$pattern="(&(ObjectClass=automount)(automountInformation=*))";
		$attr=array("automountInformation","cn");
		$suffix="ou=mounts,$ldap->suffix";
		
		$sr =@ldap_search($ldap->ldap_connection,$suffix,$pattern,$attr);
		$hash=ldap_get_entries($ldap->ldap_connection,$sr);
		//automountinformation
		if($hash["count"]>0){
			for($i=0;$i<$hash["count"];$i++){
				$automountinformation=$hash[$i]["automountinformation"][0];
				
				if(preg_match("#UUID.+?$uuid\"#",$automountinformation)){
					$arr[$hash[$i]["cn"][0]]=$hash[$i]["automountinformation"][0];
				}
			}
		}
		
		
	if(!is_array($arr)){return array();}
	return $arr;	
		
	}
	
	
	public function AutofsSharedDir($gpid){
		$gp=new groups($gpid);
		$users=$gp->members_array;
		writelogs("$gpid group contains ".count($users)." rows",__CLASS__.'/'.__FUNCTION__,__FILE__);
		$SharedFolders=$gp->SharedFolders_list;
		$ldap=new clladp();
		
		if(!is_array($SharedFolders)){return null;}
        foreach ($SharedFolders as $num=>$local_directory){
			$this->AutofsSharedUsers($users,$local_directory);
			}
		
	}
	
	public function AutofsDeleteDir($gpid,$folder){
		if($gpid==null){return null;}
		$gp=new groups($gpid);
		$ou=$gp->ou;
		if($ou==null){return null;}
		$ldap=new clladp();
		$folder=basename($folder);
		$dn="ou=$ou,dc=organizations,$ldap->suffix";
		
		
		writelogs("Delete in $dn for cn=$folder",__CLASS__.'/'.__FUNCTION__,__FILE__);
		
		$filter="(&(ObjectClass=automount)(cn=$folder))";
		$attrs=array("dn");
		$sr =@ldap_search($ldap->ldap_connection,$dn,$filter,$attrs);
		$hash=ldap_get_entries($ldap->ldap_connection,$sr);
		for($i=0;$i<$hash["count"];$i++){
				writelogs("Found {$hash[$i]["dn"]}",__CLASS__.'/'.__FUNCTION__,__FILE__);
				if($hash[$i]["dn"]<>null){
					$ldap->ldap_delete($hash[$i]["dn"]);
				}
				
			}
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?autofs-restart=yes");			
	}	
	
	private function AutofsSharedUsers($users,$path){
		if(!is_array($users)){
			writelogs("users() is not an array",__CLASS__.'/'.__FUNCTION__,__FILE__);
			return null;
			}
		if($path==null){
			writelogs("Path value is null",__CLASS__.'/'.__FUNCTION__,__FILE__);
			return null;
			}
		reset($users);
        foreach ($users as $uid=>$null){
			$u=new user($uid);
			$u->add_SharedFolder($path);
			
		}
		
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?autofs-restart=yes");		
		
	}
	
	public function automounts_Browse():array{
        $array=array();
        $q=new lib_sqlite("/home/artica/SQLITE/autofs.db");
        $sql="SELECT *  FROM autofs ORDER BY mountpoint";
        $results=$q->QUERY_SQL($sql);
        foreach ($results as $index=>$ligne){
            $id=$ligne["mountpoint"];
            $automountInformation=base64_decode($ligne["automountInformation"]);
            $array[$id]=$this->parseCommand($automountInformation);

        }
		return $array;
		
	}
	
	public function parseCommand($line,$dn=null):array{
		$array["DN"]=$dn;
		$array["AUTOFS"]=$line;

        if(preg_match("#^sftp:(.+)#",$line,$re)){
            $array=unserialize(base64_decode($re[1]));
            if(!is_array($array)){$array=array();}
            $proto="ftp";
            if($array["FTPSSL"]==1){
                $proto="ftps";
            }
            $array["FS"]="ftp";
            $array["SRC"]="{ftp_directory}:{$array["HOSTNAME"]}";
            $array["BROWSER_URI"]="$proto://{$array["HOSTNAME"]}";
            return $array;
        }


		if(preg_match("#:ftp\\\\:\/\/(.+?)\\\\:(.+?)\\\\@(.+?)\/(.*)#",$line,$re)){
			$array["FS"]="ftp";
            $proto="ftp";
			$array["SRC"]="{ftp_directory}:$re[3]";
			$array["BROWSER_URI"]="ftp://$re[3]";
            $array["HOSTNAME"]=$re[3];
            $array["USERNAME"]=$re[1];
            $array["PASSWORD"]=$re[2];
            $array["DIRECTORY"]=$re[4];
            $array["FTPSSL"]=0;
            $array["USERNAME"]=str_replace("%40","@",$array["USERNAME"]);
            $array["PASSWORD"]=str_replace("%40","@",$array["PASSWORD"]);
            $array["USERNAME"]=str_replace("%24","$",$array["USERNAME"]);
            $array["PASSWORD"]=str_replace("%24","$",$array["PASSWORD"]);
			return $array;
		}
		
		if(preg_match("#:ftp:\/\/(.+?)@(.+?)\/(.*)#",$line,$re)){
			$array["FS"]="ftp";
            $array["FTPSSL"]=0;
			$array["SRC"]="{ftp_directory}:$re[2]";
			$array["BROWSER_URI"]="ftp://$re[2]";
            $array["HOSTNAME"]=$re[2];
            $array["USERNAME"]=null;
            $array["PASSWORD"]=null;
            $array["DIRECTORY"]=$re[3];
			return $array;
		}

		
		
		if(preg_match("#ext3.+?:(.+?)$#",$line,$re)){
			$array["FS"]="ext3";
			$array["SRC"]="{$re[1]}";
			$array["BROWSER_URI"]="{$re[1]}";
			return $array;
		}
		
		if(preg_match("#ext2.+?:(.+?)$#",$line,$re)){
			$array["FS"]="ext2";
			$array["SRC"]="{$re[1]}";
			$array["BROWSER_URI"]="{$re[1]}";
			return $array;
		}

		if(preg_match("#ext4.+?:(.+?)$#",$line,$re)){
			$array["FS"]="ext4";
			$array["SRC"]="{$re[1]}";
			$array["BROWSER_URI"]="{$re[1]}";
			return $array;
		}

		if(preg_match("#vfat.+?:(.+?)$#",$line,$re)){
			$array["FS"]="vfat";
			$array["SRC"]="{$re[1]}";
			$array["BROWSER_URI"]="{$re[1]}";
			return $array;
		}

		if(preg_match("#ntfs-3g.+?:(.+?)$#",$line,$re)){
			$array["FS"]="ntfs";
			$array["SRC"]="{$re[1]}";
			$array["BROWSER_URI"]="{$re[1]}";
			return $array;
		}
		if(preg_match('#auto.+?:UUID="(.+?)"#',$line,$re)){
			$array["FS"]="auto";
			$array["SRC"]="{device}:{$re[1]}";
			return $array;
		}

		if(preg_match("#-fstype=auto.+?:(.+?)$#",$line,$re)){
			$array["FS"]="auto";
			$array["SRC"]="{$re[1]}";
			return $array;
		}			
		
		if(preg_match("#:(http|https)\\\\:\/\/(.+?)\/#",$line,$re)){
			$array["FS"]="webdav";
			$array["SRC"]="{$re[1]}://{$re[2]}";
			$array["BROWSER_URI"]="{$re[1]}://{$re[2]}";
			return $array;
		}		
		
		if(preg_match("#:ftp\\\\:\/\/(.+?)\/(.*)#",$line,$re)){
			$array["FS"]="ftp";
			$array["SRC"]="{ftp_directory}:$re[1]";
			$array["BROWSER_URI"]="ftp://$re[1]";
            $array["HOSTNAME"]=$re[1];
            $array["USERNAME"]=null;
            $array["PASSWORD"]=null;
            $array["DIRECTORY"]=$re[2];
			return $array;
		}

        if(preg_match("#:sshfs:(.+)#",$line,$re)){
            $PRIVKEY=null;
            $PUBLICKEY=null;
            $AUTHKEY=null;
            $MAIN=unserialize(base64_decode($re[1]));
            $PATH=$MAIN["SSH_REMOTE_PATH"];
            if(isset($MAIN["PRIVKEY"])){$PRIVKEY=$MAIN["PRIVKEY"];}
            if(isset($MAIN["PUBLICKEY"])){$PUBLICKEY=$MAIN["PUBLICKEY"];}
            if(isset($MAIN["AUTHKEY"])){$AUTHKEY=$MAIN["AUTHKEY"];}
            $array["FS"]="ssh";
            $array["SRC"]=$MAIN["SSH_SERVER"].":".$MAIN["SSH_PORT"];
            $array["PRIVKEY"]=$PRIVKEY;
            $array["PUBLICKEY"]=$PUBLICKEY;
            $array["AUTHKEY"]=$AUTHKEY;
            $array["BROWSER_URI"]="sshfs://{$array["SRC"]}:$PATH";
            foreach ($MAIN as $key=>$value){
                $array[$key]=$value;
            }

            return $array;
        }
		
		
		if(preg_match("#-fstype=([a-z0-9]+)#",$line,$re)){
			$array["FS"]=$re[1];
		}
		
		if(preg_match('#UUID="(.+?)"$#',$line,$re)){
			$array["SRC"]="{device}:$re[1]";
		}
		
		
		if(preg_match('#([A-Za-z\.\-_0-9\$]+):\/(.+)#',$line,$re)){
			$array["SRC"]="{$re[1]}:/{$re[2]}";
		}
			if(preg_match('#(.+?):\/$#',$line,$re)){
			$array["SRC"]="{$re[1]}:/";
		}		
		
		if(preg_match('#\/\/(.+?)\/(.+)#',$line,$re)){
			$array["SRC"]="//{$re[1]}/{$re[2]}";
			$array["BROWSER_URI"]="file://///{$re[1]}/{$re[2]}";
		}
		
		return $array;
	}
	
}

?>