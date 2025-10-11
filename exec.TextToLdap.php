<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["NO_COMPILE_POSTFIX"]=true;

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;$GLOBALS["RESTART"]=true;}


scan_connections();

function scan_connections(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/". basename(__FILE__).".".__FUNCTION__.".time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Artica Task Already running PID $pid since {$time}mn\n";}
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	
	
	if(system_is_overloaded()){
		squid_admin_mysql(2, "{OVERLOADED_SYSTEM}, aborting task",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	
	$q=new mysql();
	$sql="SELECT * FROM texttoldap";
	
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(mysqli_num_rows($results)==0){return;}
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		if(!connect($ligne)){continue;}
		import($ligne);
		$ID=$ligne["ID"];
	}
	
	
	
}

function import($ligne){
	$unix=new unix();
	$Tmpdir=$unix->TEMP_DIR();
	
	$connection=$ligne["connection"];
	$username=$ligne["username"];
	$password=$ligne["password"];
	$folder=$ligne["folder"];
	$filename=$ligne["filename"];
	$ID=$ligne["ID"];
	$folder=str_replace("\\", "/", $folder);
	if(strpos($folder, "/")>0){
		$FF=explode("/",$folder);
		$SharedDir=$FF[0];
		unset($FF[0]);
		$folder=@implode("/", $FF);
	}
	
	$mountpoint="$Tmpdir/{$ligne["ID"]}";	
	
	if($folder<>null){
		$targetdir="$mountpoint/$folder";
	}else{
		$targetdir=$mountpoint;
	}
	
	
	if(!is_file("$targetdir/$filename")){
		squid_admin_mysql(2, "$connection: $targetdir/$filename, no such file",__FUNCTION__,__FILE__,__LINE__,"import",$GLOBALS["SCHEDULE_ID"]);
		$mount=new mount();
		$mount->umount($mountpoint);
		return false;
	}
	
	if(is_file("$targetdir/$filename.scanned")){
		$SCANNED=unserialize(@file_get_contents("$targetdir/$filename.scanned"));
	}
	
	$md5file=md5_file("$targetdir/$filename");
	if($md5file==$ligne["lastmd5"]){return true;}
	
	$handle = @fopen("$targetdir/$filename", "r");
	
	if (!$handle) {
		squid_admin_mysql(2, "$connection: $targetdir/$filename, fatal error",__FUNCTION__,__FILE__,__LINE__,"import",$GLOBALS["SCHEDULE_ID"]);
		return;
	}
	
	
	$c=0;
	
	$ldap=new clladp();
	$OUS=$ldap->hash_get_ou(true);
	$defaultgroup=$ligne["ldapgroup"];
	
	if($defaultgroup>0){
		$gp=new groups($defaultgroup);
		$DefaultOu=$gp->ou;
		$defaultGroupName=$gp->groupName;
		if($GLOBALS["VERBOSE"]){echo "Default group: $defaultgroup/ $gp->groupName/$DefaultOu\n";}
	}
	$t1=time();
	$c=0;
	$d=0;
	while (!feof($handle)){
		$line =trim(fgets($handle, 1024));
		$line=str_replace("\"", "", $line);
		if($line==null){continue;}
		if(strpos($line, ";")==0){continue;}
		$SCANMD=md5($line);
		$tr=explode(";",$line);
		$account=$tr[0];
		$password=$tr[1];
		$email=$tr[2];
		$groupname=$tr[3];
		$organization=$tr[4];
		if($organization=="organization"){continue;}
		if($account=="account"){continue;}
		$Telephon=$tr[5];
		$mobile=$tr[6];
		$d++;
		if(isset($SCANNED[$SCANMD])){continue;}
		
		if($GLOBALS["VERBOSE"]){echo "--------------- $d ----------------- $SCANMD\n";}
		if($organization==null){if($defaultgroup==0){continue;}}
		if($organization==null){ if($DefaultOu<>null){ $organization=$DefaultOu; } }
		if($groupname == null) { $groupname=$defaultGroupName;}
		if($groupname == null){
			if($GLOBALS["VERBOSE"]){echo "$organization NO GroupName !!\n";}
			continue;
		}
		
		if(!isset($OUS[$organization])){
			if(!$ldap->AddOrganization($organization)){
				squid_admin_mysql(2, "fatal error failed to create entry $organization",__FUNCTION__,__FILE__,__LINE__,"import",$GLOBALS["SCHEDULE_ID"]);
				return;
			}
			$OUS=$ldap->hash_get_ou(true);
		}
		
		if(!isset($GLOBALS["GROUPS"][$organization])){
			$GLOBALS["GROUPS"][$organization]=$ldap->hash_groups($organization);
		}
		
		if(!isset($GLOBALS["GROUPS"][$organization][$groupname])){
			$gp=new groups();
			$gp->ou=$organization;
			$gp->groupName=$groupname;
			if(!$gp->add_new_group($groupname,$organization)){
				squid_admin_mysql(2, "fatal error failed to create entry $groupname/$organization",__FUNCTION__,__FILE__,__LINE__,"import",$GLOBALS["SCHEDULE_ID"]);
				return;
			}
			$GLOBALS["GROUPS"][$organization]=$ldap->hash_groups($organization);
				
		}
		
		if(!isset($GLOBALS["GROUPS"][$organization][$groupname]["gid"])){
			if($GLOBALS["VERBOSE"]){echo "$groupname/$organization NO GID!!\n";}
			continue;
		}
		
		$gid=$GLOBALS["GROUPS"][$organization][$groupname]["gid"];
		
		

		if($password==null){$password=$account;}
		$UPDATE=FALSE;
		$user=new user($account);
		if(!is_numeric($user->uidNumber)){$UPDATE=true;}
		
		
		if($email<>null){ if($user->mail<>$email){ 
			if($GLOBALS["VERBOSE"]){echo "mail $email\n";}
			$user->mail=$email;$UPDATE=true; } }
		if($password<>null){ if($user->password<>$password){ 
			if($GLOBALS["VERBOSE"]){echo "password $password\n";}
			$user->password=$password;$UPDATE=true; } }
		if($Telephon<>null){ if($user->telephoneNumber<>$Telephon){ 
			if($GLOBALS["VERBOSE"]){echo "telephoneNumber $Telephon\n";}
			$user->telephoneNumber=$Telephon;$UPDATE=true; } }
		if($mobile<>null){ if($user->mobile<>$mobile){ 
			if($GLOBALS["VERBOSE"]){echo "mobile $mobile\n";}
			$user->mobile=$mobile; $UPDATE=true;}
		}
		
		if($user->ou <> $organization){ 
			if($GLOBALS["VERBOSE"]){echo "ou $organization\n";}
			$user->ou=$organization;$UPDATE=true;}
	
			
		$user->group_id=$gid;
			
			
		if($UPDATE){
			$c++;
			if(!$user->SaveUser()){continue;}
			
			
		}
		$SCANNED[$SCANMD]=time();
		@file_put_contents("$targetdir/$filename.scanned", serialize($SCANNED));
		if($d>500){
			if(system_is_overloaded()){
				squid_admin_mysql(2, "$connection: {OVERLOADED_SYSTEM}, aborting task",__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
			$distance=$unix->distanceOfTimeInWords($t1,time(),true);
			squid_admin_mysql(2, "$connection: $c lines processed in $distance",__FUNCTION__,__FILE__,__LINE__);
			$d=0;
		}
	
	}
	
	
	$date=date("Y-m-d H:i:s");
	$q=new mysql();
	$q->QUERY_SQL("UPDATE `texttoldap` SET `lastmd5`='$md5file',`lastscan`='$date' WHERE ID='$ID'","artica_backup");
	$distance=$unix->distanceOfTimeInWords($t1,time(),true);
	squid_admin_mysql(2, "$connection: $c lines processed in $distance",__FUNCTION__,__FILE__,__LINE__);
	$umount=$unix->find_program("umount");
	shell_exec("$umount -l $mountpoint");
	return true;
}


function connect($ligne){
	
	$unix=new unix();
	$Tmpdir=$unix->TEMP_DIR();
	
	
	$connection=$ligne["connection"];
	$server=$ligne["hostname"];
	$username=$ligne["username"];
	$password=$ligne["password"];
	$folder=$ligne["folder"];
	$folder=str_replace("\\", "/", $folder);
	if(strpos($folder, "/")>0){
		$FF=explode("/",$folder);
		$SharedDir=$FF[0];
		unset($FF[0]);
		$folder=@implode("/", $FF);
	}
	
	$mountpoint="$Tmpdir/{$ligne["ID"]}";
	
	$mount=new mount();
	@mkdir($mountpoint,0755,true);
	if(!$mount->smb_mount($mountpoint, $server, $username, $password, $SharedDir)){
		squid_admin_mysql(2, "$connection: Unable to connect to smb://$server/$folder",__FUNCTION__,__FILE__,__LINE__,"import",$GLOBALS["SCHEDULE_ID"]);
		return false;
	}
	
	return true;
	
}
