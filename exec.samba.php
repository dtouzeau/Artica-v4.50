<?php
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.lvm.org.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=='--build'){build();exit();}
if($argv[1]=='--fix-etc-hosts'){fixEtcHosts();exit();}





if(!ifMustBeExecuted()){exit();}



if(isset($argv[1])){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". md5($argv[1]).".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		writelogs(basename(__FILE__).":Already executed PID: $pid since {$timepid}Mn {$argv[1]}.. aborting the process",basename(__FILE__),__FILE__,__LINE__);exit();}
	@file_put_contents($pidfile, getmypid());
}


if($argv[1]=='--users'){CountDeUsers();exit();}
if($argv[1]=='--sslbridge'){sslbridgre();exit();}
if($argv[1]=='--ads-destroy'){ads_destroy();exit();}
if($argv[1]=='--ads'){activedirectory();kinit();exit();}
if($argv[1]=='--ping-ads'){activedirectory_ping();exit();}
if($argv[1]=='--logon-scripts'){LogonScripts();exit();}
if($argv[1]=='--login-script-user'){LogonScriptsUser($argv[2]);exit();}



if($argv[1]=='--administrator'){administrator_update();exit();}
if($argv[1]=='--loglevel'){set_log_level($argv[2]);exit();}
if($argv[1]=='--quotas-recheck'){quotasrecheck();exit();}
if($argv[1]=='--quotas-recheck'){quotasrecheck();exit();}
if($argv[1]=='--ldap-groups'){ldap_groups();exit();}
if($argv[1]=='--testjoin'){test_join();exit();}
if($argv[1]=='--recycles'){recycles();exit();}
if($argv[1]=='--trash-restore'){recycles_restore();exit();}
if($argv[1]=='--trash-delete'){recycles_delete();exit();}
if($argv[1]=='--trash-scan'){ScanTrashs();exit();}
if($argv[1]=='--check-privs'){recycles_privileges($argv[2],$argv[3]);exit();}
if($argv[1]=='--smbstatus'){smbstatus_injector();exit();}
if($argv[1]=='--smbpasswd'){smbpasswd();exit();}
if($argv[1]=='--join'){JOIN_ACTIVEDIRECTORY();exit();}





if($argv[1]=='--help'){help_output();exit();}

function help_output(){
	echo "--users....................: Save users number in cache\n";
	echo "--fix-etc-hosts............: Fix hostname in /etc/hosts\n";
	echo "--ads-destroy..............: Destroy Active directory connection\n";
	echo "--ads......................: Create Active directory connection\n";
	echo "--ping-ads.................: refresh Active Directory connection\n";
	echo "--logon-scripts............: Perform logon scripts installation\n";
	echo "--administrator............: update administrator informations\n";
	echo "--loglevel.................: Set log level (1-10)\n";
	echo "--quotas-recheck...........: re-check filesystem quotas\n";
	echo "--ldap-groups..............: re-check groups LDAP population\n";
	echo "--login-script-user [uid]..: Create or Delete Logon script for [uid] user defined\n";
	
}

function ifMustBeExecuted(){
	$IsHTTPAppliance=false;
	$user=new settings_inc();
	if($user->SQUID_APPLIANCE){$IsHTTPAppliance=true;}
	if($user->KASPERSKY_WEB_APPLIANCE){$IsHTTPAppliance=true;}	
	if($user->WEBSTATS_APPLIANCE){$IsHTTPAppliance=true;}
	if($user->PROXYTINY_APPLIANCE){$IsHTTPAppliance=true;}		
	if(!$IsHTTPAppliance){return true;}
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}	
	if($EnableKerbAuth==0){return false;}
	return false;
}


$unix=new unix();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pid=@file_get_contents($pidfile);
if($unix->process_exists($pid,basename(__FILE__))){
	writelogs(basename(__FILE__).":Already executed PID: $pid.. aborting the process",basename(__FILE__),__FILE__,__LINE__);
	exit();
}

@file_put_contents($pidfile, getmypid());


if($argv[1]=='--home'){CheckHomeFor($argv[2],null);exit();}
if($argv[1]=='--homes'){ParseHomeDirectories();exit();}

if($argv[1]=='--reconfigure'){reconfigure();exit();}
if($argv[1]=='--samba-audit'){SambaAudit();exit();}

if($argv[1]=='--check-dirs'){CheckExistentDirectories();exit();}



if($argv[1]=='--disable-profiles'){DisableProfiles();exit();}
if($argv[1]=='--enable-profiles'){EnableProfiles();	exit();}

if($argv[1]=='--fix-lmhost'){fix_lmhosts();exit();}
if($argv[1]=='--fix-HideUnwriteableFiles'){fix_hide_unwriteable_files();exit();}
if($argv[1]=='--usb-mount'){usb_mount($argv[2],$argv[3]);exit;}
if($argv[1]=='--usb-umount'){usb_umount($argv[2],$argv[3]);exit;}
if($argv[1]=='--smbtree'){smbtree();exit;}



$users=new usersMenus();
if(!$users->SAMBA_INSTALLED){echo "Samba is not installed\n";exit();}

FixsambaDomainName();

function FixsambaDomainName(){
	$smb=new samba();
	$workgroup=$smb->main_array["global"]["workgroup"];
	$smb->CleanAllDomains($workgroup);
	}
	
function ldap_groups(){
	$gp=new groups();
	$gp->EditSambaGroups();
	$gp->SambaGroupsBuild();

}	

function LoadConfs(){
$sock=new sockets();
$ArticaSambaAutomAskCreation=$sock->GET_INFO("ArticaSambaAutomAskCreation");
$HomeDirectoriesMask=$sock->GET_INFO("HomeDirectoriesMask");
$SharedFoldersDefaultMask=$sock->GET_INFO("SharedFoldersDefaultMask");
if(!is_numeric($ArticaSambaAutomAskCreation)){$ArticaSambaAutomAskCreation=1;}
if(!is_numeric($HomeDirectoriesMask)){$HomeDirectoriesMask=0775;}
if(!is_numeric($SharedFoldersDefaultMask)){$SharedFoldersDefaultMask=0755;}
$GLOBALS["HomeDirectoriesMask"]=$HomeDirectoriesMask;
$GLOBALS["ArticaSambaAutomAskCreation"]=$ArticaSambaAutomAskCreation;
$GLOBALS["SharedFoldersDefaultMask"]=$SharedFoldersDefaultMask;
}


function ParseHomeDirectories(){
	$sock=new sockets();
	if(!isset($GLOBALS["profile_path"])){
		$profile_path=$sock->GET_INFO('SambaProfilePath');
		$GLOBALS["profile_path"]=$profile_path;
	}
	if(!isset($GLOBALS["HomeDirectoriesMask"])){LoadConfs();}
	
		
	$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
	
	
		if(!isset($GLOBALS["HomeDirectoriesMask"])){LoadConfs();}
		$ldap=new clladp();
		$profile_path=null;
		$attr=array("homeDirectory","uid","dn");
		$pattern="(&(objectclass=sambaSamAccount)(uid=*))";
		$sock=new sockets();
		
		if(trim($profile_path)==null){$profile_path="/home/export/profile";}	
		$sock=new sockets();
		$SambaRoamingEnabled=$sock->GET_INFO('SambaRoamingEnabled');
		if($SambaRoamingEnabled==1){EnableProfiles();}else{DisableProfiles();}			
		
		$sr =@ldap_search($ldap->ldap_connection,"dc=organizations,".$ldap->suffix,$pattern,$attr);
		$hash=ldap_get_entries($ldap->ldap_connection,$sr);
		$sock=new sockets();
		for($i=0;$i<$hash["count"];$i++){
			$dn=$hash[$i]["dn"];
			$uid=$hash[$i]["uid"][0];
			$homeDirectory=$hash[$i][strtolower("homeDirectory")][0];
			writelogs("loading: {$hash[$i]["uid"][0]}",__FUNCTION__,__FILE__,__LINE__);
			if(preg_match("#ou=users,dc=samba,dc=organizations#",$dn)){writelogs("$uid:No a standard user...SKIP",__FUNCTION__,__FILE__,__LINE__);continue;}
			if($uid==null){writelogs("uid is null, SKIP ",__FUNCTION__,__FILE__,__LINE__);continue;}
			if($uid=="nobody"){writelogs("uid is nobody, SKIP ",__FUNCTION__,__FILE__,__LINE__);continue;}
			if($uid=="root"){writelogs("uid is root, SKIP ",__FUNCTION__,__FILE__,__LINE__);continue;}
			if(substr($uid,strlen($uid)-1,1)=='$'){writelogs("$uid:This is a computer, SKIP ",__FUNCTION__,__FILE__,__LINE__);continue;}
			writelogs("-> CheckHomeFor($uid,$homeDirectory)",__FUNCTION__,__FILE__,__LINE__);
			CheckHomeFor($uid,$homeDirectory);
			}
		}

        $EnableSambaActiveDirectory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSambaActiveDirectory"));

        if($EnableSambaActiveDirectory==1){
			$q=new mysql();
			$sql="SELECT uid FROM getent_users ORDER BY uid";
			$q=new mysql();
			writelogs("$sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);		
			$results=$q->QUERY_SQL($sql,"artica_backup");
			$NumTot=mysqli_num_rows($results);
			squid_admin_mysql(2, "$NumTot users to check", __FUNCTION__, __FILE__, __LINE__, "samba");
			while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
				$uid=$ligne["uid"];
				$uidOrg=$ligne["uid"];
				if(preg_match("#(.+?)\/(.+)#", $uid,$re)){$uid=$re[1];}
				$homeDirectory="/home/$uid";
				if(!is_dir($homeDirectory)){
					@mkdir($homeDirectory,0755,true);
					squid_admin_mysql(2, "$homeDirectory has been created for $uid", __FUNCTION__, __FILE__, __LINE__, "samba");
				}
				if($GLOBALS["ArticaSambaAutomAskCreation"]==1){
					if(isset($GLOBALS["HomeDirectoriesMask"])){
						@chmod($homeDirectory,$GLOBALS["HomeDirectoriesMask"]);
					}
				}
				@chown($homeDirectory,"$uidOrg");
	
				
			}
			
		}
		
		
		
		
		
function CheckHomeFor($uid,$homeDirectory=null){
	if(!isset($GLOBALS["HomeDirectoriesMask"])){LoadConfs();}
	$ct=new user($uid);
	if($homeDirectory==null){$homeDirectory=$ct->homeDirectory;}
	
	echo "Starting......: ".date("H:i:s")." Home $uid checking home: $homeDirectory\n";
	if(!isset($GLOBALS["profile_path"])){
		$sock=new sockets();
		$profile_path=$sock->GET_INFO('SambaProfilePath');
		$GLOBALS["profile_path"]=$profile_path;
	}
	if($ct->ou==null){writelogs("$uid: OU=NULL, No a standard user...SKIP",__FUNCTION__,__FILE__,__LINE__);return;}
	$ou=$ct->ou;
	$uid=strtolower($uid);
    $newdir=getStorageEnabled($ou);
    if(!is_null($newdir)){
        $newdir=trim($newdir);
    }

	if($newdir<>null){
		$newdir="$newdir/$uid";
		writelogs("LVM: [$ou]:: storage=$newdir;homeDirectory=$homeDirectory",__FUNCTION__,__FILE__,__LINE__);
		if($newdir<>$homeDirectory){
			writelogs("$uid:: change $homeDirectory to $newdir",__FUNCTION__,__FILE__,__LINE__);
			$ct->homeDirectory=$newdir;
			$ct->edit_system();
			$homeDirectory=$newdir;
		}
	}
	
if($homeDirectory==null){
	$homeDirectory="/home/$uid";
	writelogs("$uid:: change $homeDirectory",__FUNCTION__,__FILE__,__LINE__);
	$ct->homeDirectory=$homeDirectory;
	$ct->edit_system();
	}
	
	if($GLOBALS["profile_path"]<>null){
		$export="$profile_path/$uid";
		writelogs("Checking export:$export",__FUNCTION__,__FILE__,__LINE__);
		@mkdir($export);
		@chmod($export,0775);
		@chown($export,$uid);
	}
	
	
	writelogs("Checking home:$homeDirectory",__FUNCTION__,__FILE__,__LINE__);

	@mkdir($homeDirectory,0755,true);
	if($GLOBALS["ArticaSambaAutomAskCreation"]==1){
		@chmod($homeDirectory,$GLOBALS["HomeDirectoriesMask"]);
	}
	@chown($homeDirectory,$uid);
	
	if($ct->WebDavUser==1){
		$unix=new unix();
		$find=$unix->find_program("find");
		$apacheuser=$unix->APACHE_GROUPWARE_ACCOUNT();
		if(preg_match("#(.+?):#",$apacheuser,$re)){$apacheuser=$re[1];}
		$internet_folder="$homeDirectory/Internet Folder";
		if(!is_dir($internet_folder)){@mkdir($internet_folder,$GLOBALS["SharedFoldersDefaultMask"],true);}else{
		@chmod($internet_folder,$GLOBALS["SharedFoldersDefaultMask"]);
		}
		$internet_folder=$unix->shellEscapeChars($internet_folder);
		echo "Starting......: ".date("H:i:s")." Home $uid checking home: $internet_folder\n";
		writelogs("Checking $ct->uid:$apacheuser :$internet_folder",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("/bin/chown -R $ct->uid:$apacheuser $internet_folder >/dev/null 2>&1 &");
		shell_exec("$find $internet_folder -type d -exec chmod {$GLOBALS["SharedFoldersDefaultMask"]} {} \; >/dev/null 2>&1 &");
	}
	
	
	
}

function getStorageEnabled($ou):string{
    if(!isset($GLOBALS["LVM_$ou"])){$GLOBALS["LVM_$ou"]=null;}
    if(!isset($GLOBALS["LVM_{$ou}_subdir"])){$GLOBALS["LVM_{$ou}_subdir"]=null;}
    $OuBackupStorageSubDir="";
    $storage_enabled="";

    if($GLOBALS["LVM_$ou"]==null){
            $lvm=new lvm_org($ou);
            if($lvm->storage_enabled<>null){
                writelogs("Checking $ou:$lvm->storage_enabled subdir=$lvm->OuBackupStorageSubDir",__FUNCTION__,__FILE__,__LINE__);
                $GLOBALS["LVM_$ou"]="$lvm->storage_enabled";
                $GLOBALS["LVM_{$ou}_subdir"]=$lvm->OuBackupStorageSubDir;
            }
        }

    if(!is_null($GLOBALS["LVM_$ou"])) {
        $storage_enabled = trim($GLOBALS["LVM_$ou"]);
    }
    if(!is_null($GLOBALS["LVM_{$ou}_subdir"])) {
        $OuBackupStorageSubDir = trim($GLOBALS["LVM_{$ou}_subdir"]);
    }
	if($storage_enabled==null){return "";}

    if(!isset($GLOBALS[$storage_enabled])){
        $GLOBALS[$storage_enabled]=null;
    }

	if($GLOBALS[$storage_enabled]<>null){
		$storage_mounted=$GLOBALS[$storage_enabled];
	 }else{	
		$sock=new sockets();
		$storage_mounted=trim(base64_decode($sock->getFrameWork("cmd.php?get-mounted-path=".base64_encode($storage_enabled))));
		if($storage_mounted<>null){$storage_mounted="$storage_mounted/$OuBackupStorageSubDir";}
		$GLOBALS[$storage_enabled]=$storage_mounted;
	 }
	 
	 return $storage_mounted;
	 
}

		
function DisableProfiles(){
	$ldap=new clladp();
	$pattern="(&(objectclass=sambaSamAccount)(sambaProfilePath=*))";
	$attr=array("sambaProfilePath","uid","dn");
	$sr =@ldap_search($ldap->ldap_connection,$ldap->suffix,$pattern,$attr);
	$hash=ldap_get_entries($ldap->ldap_connection,$sr);
	for($i=0;$i<$hash["count"];$i++){
		$uid=$hash[$i][strtolower("uid")][0];
		$dn=$hash[$i][strtolower("dn")];
		$sambaProfilePath=$hash[$i][strtolower("sambaProfilePath")][0];
		$upd["sambaProfilePath"]=$sambaProfilePath;
		$ldap->Ldap_del_mod($dn,$upd);
		}
}
function EnableProfiles(){
		$ldap=new clladp();
		$sock=new sockets();
		$smb=new samba();
		$upd=array();
		$SambaAdminServerDefined=$sock->GET_INFO("SambaAdminServerDefined");
		
		$SAMBA_HOSTNAME=$smb->main_array["global"]["netbios name"];
		$SAMBA_IP=gethostbyname($SAMBA_HOSTNAME);
		if(trim($SAMBA_IP)==null){$SAMBA_IP=$SAMBA_HOSTNAME;}
		if(trim($SAMBA_IP)=="127.0.0.1"){$SAMBA_IP=$SAMBA_HOSTNAME;}
		if(trim($SAMBA_IP)=="127.0.1.1"){$SAMBA_IP=$SAMBA_HOSTNAME;}
		if(trim($SAMBA_IP)=="127.0.0.2"){$SAMBA_IP=$SAMBA_HOSTNAME;}		
		if($SambaAdminServerDefined<>null){$SAMBA_IP=$SambaAdminServerDefined;}
		$profile_path=$sock->GET_INFO('SambaProfilePath');
		if(trim($profile_path)==null){$profile_path="/home/export/profile";}
		$profile_base=basename($profile_path);	
		
		$attr=array("dn","uid","SambaProfilePath");
		$pattern="(&(objectclass=sambaSamAccount)(uid=*))";	
		$sr =@ldap_search($ldap->ldap_connection,$ldap->suffix,$pattern,$attr);
		$hash=ldap_get_entries($ldap->ldap_connection,$sr);
		for($i=0;$i<$hash["count"];$i++){
			$uid=$hash[$i]["uid"][0];
			$SambaProfilePath=$hash[$i][strtolower("SambaProfilePath")][0];
			
			if(strpos($uid,'$')>0){continue;}
			$dn=$hash[$i]["dn"];
			
			if(preg_match("#127\.0\.#",$SambaProfilePath)){
				echo "$SambaProfilePath no match change it\n";
				$upd["SambaProfilePath"][0]='\\\\' .$SAMBA_IP. '\\'.$profile_base.'\\' . $uid;
				$ldap->Ldap_modify($dn,$upd);
			}
			if(!is_dir("$profile_path/$uid")){@mkdir("$profile_path/$uid");}
			@chmod("$profile_path/$uid",0755);
			shell_exec("/bin/chown $uid $profile_path/$uid");
			
		}	
		
}

function build(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		writelogs(basename(__FILE__).":Already executed PID: $pid since {$timepid}Mn.. aborting the process",
		basename(__FILE__),__FILE__,__LINE__);
		exit();
	}
		
		
	@file_put_contents($pidfile, getmypid());
	
	reconfigure();
	
}

function CheckFilesAndDirectories(){
	$unix=new unix();
	if(is_file("/var/lib/samba/usershares/data")){@unlink("/var/lib/samba/usershares/data");}
	if(!is_dir("/var/lib/samba/usershares/data")){@mkdir("/var/lib/samba/usershares/data",0644,true);}
	@chmod("/var/lib/samba/usershares/data",1644);
	shell_exec("/bin/chown -R root:root /var/lib/samba/usershares");	
	$setfacl_bin=$unix->find_program("setfacl");
	if(is_file($setfacl_bin)){shell_exec("$setfacl_bin -b /tmp 2>&1");}	
}

function smbpasswd(){
    $unix=new unix();
	$smbpasswd=$unix->find_program("smbpasswd");
	if(!is_file($smbpasswd)){
		squid_admin_mysql(2, "smbpasswd no such binary",__FUNCTION__,__FILE__,__LINE__, "samba");
		return;
	}
	$ldap=new clladp();
	$ldap_passwd=$ldap->ldap_password;
	
	exec("$smbpasswd -w \"$ldap_passwd\" 2>&1",$results);
	squid_admin_mysql(2, "Samba ldap password updated\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__, "samba");
}


function reconfigure(){
	if($GLOBALS["VERBOSE"]){writelogs("starting reconfigure()",__FUNCTION__,__FILE__,__LINE__);}
	$unix=new unix();
	$sock=new sockets();
	if($GLOBALS["VERBOSE"]){writelogs("->clladp()",__FUNCTION__,__FILE__,__LINE__);}
	$ldap=new clladp();
	$smbpasswd=$unix->find_program("smbpasswd");
	if($GLOBALS["VERBOSE"]){writelogs("smbpasswd=$smbpasswd -->samba()",__FUNCTION__,__FILE__,__LINE__);}
	$samba=new samba();
	$net=$unix->LOCATE_NET_BIN_PATH();	
	$ldap_passwd=$ldap->ldap_password;
	$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
	$EnableSambaRemoteLDAP=$sock->GET_INFO("EnableSambaRemoteLDAP");
	
	if($EnableSambaRemoteLDAP==1){
		$SambaRemoteLDAPInfos=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaRemoteLDAPInfos")));
		$ldap_passwd=$SambaRemoteLDAPInfos["user_dn_password"];
	}
	

	
	if($EnableSambaActiveDirectory==1){activedirectory();}
	CheckFilesAndDirectories();
	FixsambaDomainName();
	echo "Starting......: ".date("H:i:s")." Samba building main configuration...\n";
	@file_put_contents("/etc/samba/smb.conf",$samba->BuildConfig());
	echo "Starting......: ".date("H:i:s")." Samba $smbpasswd -w ****\n";
	shell_exec("$smbpasswd -w \"$ldap_passwd\"");
	system("/usr/sbin/artica-phpfpm-service -nsswitch");
	SambaAudit();
	fixEtcHosts();
	
	$master_password=$samba->GetAdminPassword("administrator");
	$SambaEnableEditPosixExtension=$sock->GET_INFO("SambaEnableEditPosixExtension");
	if($SambaEnableEditPosixExtension==1){
		$cmd="$net idmap secret {$samba->main_array["global"]["workgroup"]} \"$ldap_passwd\" >/dev/null 2>&1 &";
		shell_exec($cmd);
		$cmd="$net idmap secret alloc \"$ldap_passwd\" >/dev/null 2>&1 &";
		shell_exec($cmd);
	}
	
	if($EnableSambaActiveDirectory==1){kinit();}
	shell_exec("/usr/sbin/artica-phpfpm-service -build-pam");
	 
	
	$unix->THREAD_COMMAND_SET(LOCATE_PHP5_BIN2()." ".__FILE__." --check-dirs");
	$unix->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --samba-reconfigure");
	reload();
	
	
	}
	
function reload(){
	$unix=new unix();
	
	$smbcontrol=$unix->find_program("smbcontrol");
	if(is_file($smbcontrol)){
		shell_exec("$smbcontrol smbd reload-config");
		shell_exec("$smbcontrol winbindd reload-config");
		shell_exec("$smbcontrol nmbd reload-config");
		return;
	}
	
	
	$pidof=$unix->find_program("pidof");
	$smbd=$unix->find_program("smbd");
	$winbindd=$unix->find_program("winbindd");
	$kill=$unix->find_program("kill");
	exec("$pidof $smbd 2>&1",$results);
	echo "Starting......: ".date("H:i:s")." samba reloading smbd:$smbd...\n";
	$tbl=explode(" ",@implode(" ",$results));

    foreach ($tbl as $index=>$pid){
		$pid=trim($pid);
		if(!is_numeric($pid)){continue;}
		if($pid<10){continue;}
		echo "Starting......: ".date("H:i:s")." samba reloading smbd pid: $pid\n";
		unix_system_HUP($pid);
	}
	$results=array();
	exec("$pidof winbindd 2>&1",$results);
	echo "Starting......: ".date("H:i:s")." samba reloading winbindd:$smbd...\n";
	$tbl=explode(" ",@implode(" ",$results));
    foreach ($tbl as $index=>$pid){
		$pid=trim($pid);
		if(!is_numeric($pid)){continue;}
		if($pid<10){continue;}
		echo "Starting......: ".date("H:i:s")." samba reloading winbindd pid: $pid\n";
		unix_system_HUP($pid);
	}	
	
}
	
function fixEtcHosts(){}




function ads_destroy(){
	$unix=new unix();
	$net=$unix->LOCATE_NET_BIN_PATH();
	$kdestroy=$unix->find_program("kdestroy");
	$sock=new sockets();
	$config=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaAdInfos")));
	$adminpassword=$config["PASSWORD"];
	$adminpassword=$unix->shellEscapeChars($adminpassword);
	$cmd="$net ads leave -U {$config["ADADMIN"]}%$adminpassword 2>&1";
	echo "Starting......: ".date("H:i:s")." Samba remove connection\n";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	exec("$cmd",$results);
	foreach ($results as $index=>$line){if(trim($line)==null){continue;}echo "Starting......: ".date("H:i:s")." Samba $results\n";}
	unset($results);	
	if($GLOBALS["VERBOSE"]){echo $kdestroy."\n";}
	exec("$kdestroy 2>&1",$results);
	foreach ($results as $index=>$line){if(trim($line)==null){continue;}echo "Starting......: ".date("H:i:s")." Samba $results\n";}
}
	
	
function kinit(){
	$function=__FUNCTION__;
	if(isset($GLOBALS["KINIT_RUN"])){
		echo "Starting......: ".date("H:i:s")." $function, already executed..\n";
		return;}
	$GLOBALS["KINIT_RUN"]=true;
	
	$unix=new unix();
	$kinit=$unix->find_program("kinit");
	$echo=$unix->find_program("echo");
	$net=$unix->LOCATE_NET_BIN_PATH();
	$hostname=$unix->find_program("hostname");
	$sock=new sockets();
	$config=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaAdInfos")));
	$domain=strtoupper($config["ADDOMAIN"]);
	$domain_lower=strtolower($config["ADDOMAIN"]);
	$cachefile="/etc/artica-postfix/NetADSInfo.cache";
	$CyrusToAD=$sock->GET_INFO("CyrusToAD");
	if(!is_numeric($CyrusToAD)){$CyrusToAD=0;}
	$ADSERVER_IP=$config["ADSERVER_IP"];
	@unlink("/etc/artica-postfix/NetADSInfo.cache");
	
	$ad_server=strtolower($config["ADSERVER"]);
	$kinitpassword=$config["PASSWORD"];
	$kinitpassword=$unix->shellEscapeChars($kinitpassword);
	
	if($kinit<>null){	
		echo "Starting......: ".date("H:i:s")." $function, $kinit {$config["ADADMIN"]}@$domain...\n";
		shell_exec("$echo $kinitpassword|$kinit {$config["ADADMIN"]}@$domain");
	}
	
	
	exec($hostname,$results);
	$servername=trim(@implode(" ",$results));
	echo "Starting......: ".date("H:i:s")." $function, using server name has $servername.$domain_lower\n";
	shell_exec("/usr/share/artica-postfix/bin/artica-install --change-hostname $servername.$domain_lower");
	echo "Starting......: ".date("H:i:s")." $function, connecting to $ad_server.$domain_lower\n";
	@unlink($cachefile);
	
	$NetADSINFOS=$unix->SAMBA_GetNetAdsInfos();
	$KDC_SERVER=$NetADSINFOS["KDC server"];
	$adminpassword=$config["PASSWORD"];

	echo "Starting......: ".date("H:i:s")." $function, setauthuser -> \"{$config["ADADMIN"]}\"\n";
	exec("$net setauthuser -U {$config["ADADMIN"]}%$kinitpassword 2>&1",$results);
	
	echo "Starting......: ".date("H:i:s")." $function, checking winbindd daemon...\n";
	shell_exec("/etc/init.d/artica-postfix start winbindd");
	echo "Starting......: ".date("H:i:s")." $function, KDC:  \"$KDC_SERVER\"\n";
	
	
	$adminpassword=$unix->shellEscapeChars($adminpassword);
	
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__." --join");
	
	
	if($KDC_SERVER==null){
		$cmd="$net ads join -W $ad_server.$domain_lower -S $ad_server -U {$config["ADADMIN"]}%$adminpassword 2>&1";
		if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
		
		exec("$cmd",$results);
		
		foreach ($results as $index=>$line){
			writelogs("ads join [{$config["ADADMIN"]}]: $line",__FUNCTION__,__FILE__,__LINE__);
			
			if(preg_match("#DNS update failed#",$line)){
				echo "Starting......: ".date("H:i:s")." ADS Join FAILED with command line \"$cmd\"\n";
			}
			
			if(preg_match("#The network name cannot be found#",$line)){
				echo "Starting......: ".date("H:i:s")." ADS Join $ad_server.$domain_lower failed, unable to resolve it\n";
				if($ADSERVER_IP<>null){
					if(!$GLOBALS["CHANGE_ETC_HOSTS_AD"]){
						$line=base64_encode("$ADSERVER_IP\t$ad_server.$domain_lower\t$ad_server");
						$sock->getFrameWork("cmd.php?etc-hosts-add=$line");
						$GLOBALS["CHANGE_ETC_HOSTS_AD"]=true;
						echo "Starting......: ".date("H:i:s")." ADS Join add $ad_server.$domain_lower $ADSERVER_IP in hosts file done, restart\n";
						kinit();
						return;
					}
				}
			}
			
			echo "Starting......: ".date("H:i:s")." $function, ADS Join $ad_server.$domain_lower ($line)\n";
		}
	}else{
		echo "Starting......: ".date("H:i:s")." $function, ADS Already joined to \"$KDC_SERVER\"\n";
	}
	
	
	
	if($CyrusToAD==1){
		echo "Starting......: ".date("H:i:s")." $function, Activate PAM for Cyrus sasl\n";
		EnablePamd();
	}else{
		echo "Starting......: ".date("H:i:s")." $function, Disable PAM for Cyrus sasl\n";
		DisablePamd();
	}
	echo "Starting......: ".date("H:i:s")." $function, DONE\n";
}

function activedirectory(){
	include_once(dirname(__FILE__)."/ressources/class.kdc.inc");
	$sock=new sockets();
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}	
	if($EnableKerbAuth==1){
		$verbosed=null;
		if($GLOBALS["VERBOSE"]){$verbosed=" --verbose";}
		$unix=new unix();
		$cmd=$unix->LOCATE_PHP5_BIN()." ". dirname(__FILE__)."/exec.kerbauth.php --build$verbosed";
		echo "Enable Kerberos authentification is enabled, executing kerberos auth\n";
		shell_exec($cmd);
	
	}
	$config=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaAdInfos")));
	$kdc=new kdc();
	$kdc->suffix_domain=$config["ADDOMAIN"];
	$kdc->netbios_servername=$config["ADSERVER"];
	$kdc->administrator=$config["ADADMIN"];
	$kdc->wintype=$config["WINDOWS_SERVER_TYPE"];
	$kdc->build();	
	
	
	
	
}

function activedirectory_ping(){
	$sock=new sockets();
	$unix=new unix();
	$filetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
	if(!is_numeric($EnableSambaActiveDirectory)){return;}
	if($EnableSambaActiveDirectory<>1){return;}
	$ping_dc=false;
	$time=$unix->file_time_min($filetime);
	if($time<120){
		if(!$GLOBALS["VERBOSE"]){return;}
		echo "$filetime ({$time}Mn)\n";
	}
	
	$kinit=$unix->find_program("kinit");
	$echo=$unix->find_program("echo");
	$net=$unix->LOCATE_NET_BIN_PATH();
	$wbinfo=$unix->find_program("wbinfo");
	$config=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaAdInfos")));
	$domain=strtoupper($config["ADDOMAIN"]);
	$domain_lower=strtolower($config["ADDOMAIN"]);

	$ADSERVER_IP=$config["ADSERVER_IP"];	
	$ad_server=strtolower($config["ADSERVER"]);
	$kinitpassword=$config["PASSWORD"];
	$kinitpassword=$unix->shellEscapeChars($kinitpassword);
	
	$clock_explain="The clock on you system (Linux/UNIX) is too far off from the correct time.\nYour machine needs to be within 5 minutes of the Kerberos servers in order to get any tickets.\nYou will need to run ntp, or a similar service to keep your clock within the five minute window";
	
	
	$cmd="$echo $kinitpassword|$kinit {$config["ADADMIN"]}@$domain 2>&1";
	echo "$cmd\n";
	exec("$cmd",$kinit_results);
    foreach ($kinit_results as $num=>$ligne){
		if(preg_match("#Clock skew too great while getting initial credentials#", $ligne)){$unix->send_email_events("Active Directory connection clock issue", "kinit program claim\n$ligne\n$clock_explain", "system");}
		if(preg_match("#Client not found in Kerberos database while getting initial credentials#", $ligne)){$unix->send_email_events("Active Directory authentification issue", "kinit program claim\n$ligne\n", "system");}
		if($GLOBALS["VERBOSE"]){echo "kinit: $ligne\n";}
	}	
	

	exec("$wbinfo --ping-dc 2>&1",$ping_dc_results);

    foreach ($ping_dc_results as $num=>$ligne){
		if($GLOBALS["VERBOSE"]){echo "ping-dc: $ligne\n";}
		if(preg_match("#succeeded#", $ligne)){$ping_dc=true;}
	}
	
	@unlink($filetime);
	@file_put_contents($filetime, time());
	
	
}


function CheckExistentDirectories(){
	$change=false;
	$sock=new sockets();
	$ArticaMetaEnabled=$sock->GET_INFO("ArticaMetaEnabled");
	if($ArticaMetaEnabled==null){$ArticaMetaEnabled=0;}
	$SharedFoldersDefaultMask=$sock->GET_INFO("SharedFoldersDefaultMask");
	$ArticaSambaAutomAskCreation=$sock->GET_INFO("ArticaSambaAutomAskCreation");
	if(!is_numeric($ArticaSambaAutomAskCreation)){$ArticaSambaAutomAskCreation=1;}
	if(!is_numeric($SharedFoldersDefaultMask)){$SharedFoldersDefaultMask=0755;}
	if($ArticaSambaAutomAskCreation==0){$ArticaSambaAutomAskCreation=0600;}
	$ini=new Bs_IniHandler("/etc/artica-postfix/settings/Daemons/SambaSMBConf");
	if(is_array($ini->_params)){
        foreach ($ini->_params as $index=>$array){
			if($index=="print$"){continue;}
			if($index=="printers"){continue;}
			if($index=="homes"){continue;}
			if($index=="global"){continue;}
			if($array["path"]==null){continue;}
			if(is_link($array["path"])){continue;}
			if(is_dir($array["path"])){continue;}else{if($ArticaMetaEnabled==1){@mkdir($array["path"],$SharedFoldersDefaultMask,true);continue;}}
			unset($ini->_params[$index]);
			$change=true;
			continue;
		}
	}
	
	$ini->saveFile("/etc/artica-postfix/settings/Daemons/SambaSMBConf");
	
}


function SambaAudit(){
	if($GLOBALS["VERBOSE"]){writelogs("starting SambaAudit()",__FUNCTION__,__FILE__,__LINE__);}
	$sock=new sockets();
	$EnableSambaXapian=$sock->GET_INFO("EnableSambaXapian");
	$EnableScannedOnly=$sock->GET_INFO('EnableScannedOnly');
	if(!is_numeric($EnableSambaXapian)){$EnableSambaXapian=0;}
	if(!is_numeric($EnableScannedOnly)){$EnableScannedOnly=0;}

	$users=new usersMenus();
	if(!$users->XAPIAN_PHP_INSTALLED){$EnableSambaXapian=0;}
	if(!$users->SCANNED_ONLY_INSTALLED){$EnableScannedOnly=0;}
	$EnableSambaXapian=0; //désactivée, utilise une autre méthode...
	$sambaZ=new samba();
	$write=false;
	
	foreach ($sambaZ->main_array as $num=>$ligne){
		if($num<>"homes"){if($ligne["path"]==null){continue;}}
		if($num=="profiles"){continue;}
		if($num=="printers"){continue;}
		if($num=="print$"){continue;}
		if($num=="netlogon"){continue;}
		$vfs_objects=$ligne["vfs object"];
        $ini=new Bs_IniHandler();
		
		if($EnableSambaXapian==1){
			if(!IsVfsExists($vfs_objects,"full_audit")){
				$ini->_params[$num]["vfs object"]=$ini->_params[$num]["vfs object"]." full_audit";
				$ini->_params[$num]["vfs object"]=VFSClean($ini->_params[$num]["vfs object"]);
				$ini->_params[$num]["full_audit:prefix"]="%u|%I|%m|%S|%P";
				$ini->_params[$num]["full_audit:success"]="rename unlink pwrite write";
				$ini->_params[$num]["full_audit:failure"]="none";
				$ini->_params[$num]["full_audit:facility"]="LOCAL7";
				$ini->_params[$num]["full_audit:priority"]="NOTICE";				
				$write=true;
			}
		}else{
			if(IsVfsExists($vfs_objects,"full_audit")){
				$ini->_params[$num]["vfs object"]=str_replace("full_audit","",$ini->_params[$num]["vfs object"]);
				$ini->_params[$num]["vfs object"]=VFSClean($ini->_params[$num]["vfs object"]);
				unset($ini->_params[$num]["full_audit:prefix"]);
				unset($ini->_params[$num]["full_audit:success"]);
				unset($ini->_params[$num]["full_audit:failure"]);
				unset($ini->_params[$num]["full_audit:facility"]);
				unset($ini->_params[$num]["full_audit:priority"]);
				$write=true;
			}
		}
		
		if($EnableScannedOnly==0){
			if(IsVfsExists($vfs_objects,"scannedonly")){
				$ini->_params[$num]["vfs object"]=str_replace("scannedonly","",$ini->_params[$num]["vfs object"]);
				$ini->_params[$num]["vfs object"]=VFSClean($ini->_params[$num]["vfs object"]);
				$write=true;
			}
		}		
}
	
if($write){$sambaZ->SaveToLdap(true);}
	
	
	
	
}

function IsVfsExists($line,$module){
	$tbl=explode(" ",$line);
	if(!is_array($tbl)){return false;}
	foreach ($tbl as $num=>$ligne){
		if(strtolower(trim($ligne))==$module){return true;}
	}
	return false;
}
function VFSClean($line){
	$tbl=explode(" ",$line);
	if(!is_array($tbl)){return false;}
	foreach ($tbl as $num=>$ligne){
		if($ligne==null){continue;}
		$r[]=$ligne;
	}

	if(!is_array($r)){return null;}
	return implode(" ",$r);
	
}


function LogonScriptsUser($uid){
	$sql="SELECT script_code FROM logon_scriptsusers WHERE uid='$uid'";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	writelogs("checking /home/netlogon security settings",__FUNCTION__,__FILE__,__LINE__);
	@mkdir("/home/netlogon");
	@chmod("/home/netlogon",0755);	
 	if(is_file("/home/netlogon/artica-$uid.bat")){@unlink("/home/netlogon/artica-$uid.bat");}
 	$q=new mysql();
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	if($ligne["script_code"]==null){return false;}
	$script=base64_decode($ligne["script_code"]);
	$script=str_replace("\n","\r\n",$script);
	$script=$script."\r\n";
	writelogs("Saving /home/netlogon/artica-$uid.bat",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/home/netlogon/artica-$uid.bat",$script);
	$scriptName="artica-$uid.bat";
	$u=new user($uid);
	if($u->dn==null){return;}
	if($u->NotASambaUser){squid_admin_mysql(2, "$uid is not a Samba user",__FUNCTION__,__FILE__,__LINE__,"samba");return;}
	writelogs("edit $uid for $script script name",__FUNCTION__,__FILE__,__LINE__);
	if($GLOBALS["VERBOSE"]){echo "DEBUG:: $uid -> $script\n";}
	$u->Samba_edit_LogonScript($scriptName);	
	squid_admin_mysql(2, "logon script $scriptName for $uid updated.",__FUNCTION__,__FILE__,__LINE__,"samba");
	return true;
}


function LogonScripts(){
	
	
	$sql="SELECT * FROM logon_scripts";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	writelogs("checking /home/netlogon security settings",__FUNCTION__,__FILE__,__LINE__);
	@mkdir("/home/netlogon");
	@chmod("/home/netlogon",0755);
	LogonScripts_remove();
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if($GLOBALS["VERBOSE"]){echo "DEBUG:: ".mysqli_num_rows($results)." items\n";}
	
	if(!$q->ok){
		writelogs("mysql failed \"SELECT * FROM logon_scripts\" in artica_backup database",__FUNCTION__,__FILE__,__LINE__);
		writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	$count=0;
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$count++;
		$gpid=$ligne["gpid"];
		$script=$ligne["script_code"];
		if($GLOBALS["VERBOSE"]){echo "DEBUG:: Script group id: $gpid\n";}
		if($gpid==null){writelogs("gpid is null, skip",__FUNCTION__,__FILE__,__LINE__);continue;}
		if($script==null){writelogs("script contains no data, skip",__FUNCTION__,__FILE__,__LINE__);continue;}
		$script=base64_decode($script);
		$script=str_replace("\n","\r\n",$script);
		$script=$script."\r\n";
		writelogs("Saving /home/netlogon/artica-$gpid.bat",__FUNCTION__,__FILE__,__LINE__);
		@file_put_contents("/home/netlogon/artica-$gpid.bat",$script);
		LogonScripts_updateusers($gpid);		
	}
	
	squid_admin_mysql(2, "$count logon scripts updated.",__FUNCTION__,__FILE__,__LINE__,"samba");
	
	
}

function LogonScripts_updateusers($gpid){
	$gp=new groups($gpid);
	if(!is_array($gp->members_array)){
		writelogs("Group $gpid did not store users.",__FUNCTION__,__FILE__,__LINE__);
		return null;
	}
	$script="artica-$gpid.bat";
	$members_array=$gp->members_array;
	if($GLOBALS["VERBOSE"]){echo "DEBUG:: GROUP $gpid ". count($members_array) ." Members\n";}
    foreach ($members_array as $uid=>$ligne){
		if(is_file("/home/netlogon/artica-$uid.bat")){
			if($GLOBALS["VERBOSE"]){echo "DEBUG:: /home/netlogon/artica-$uid.bat exists, skip\n";}
			continue;
		}	
		
	
		if($GLOBALS["VERBOSE"]){echo "DEBUG:: GROUP $gpid:: Updating uid $uid\n";}
		$u=new user($uid);
		
		if($u->dn==null){continue;}
		if($u->NotASambaUser){writelogs("$uid is not a Samba user",__FUNCTION__,__FILE__,__LINE__);continue;}
		writelogs("edit $uid for $script script name",__FUNCTION__,__FILE__,__LINE__);
		if($GLOBALS["VERBOSE"]){echo "DEBUG:: $uid -> $script\n";}
		$u->Samba_edit_LogonScript($script);
			
		
	}
	
}




function LogonScripts_remove(){
	$dir_handle = @opendir("/home/netlogon");
	if(!$dir_handle){
		return array();
	}
	$count=0;	
	while ($file = readdir($dir_handle)) {
		  if($file=='.'){continue;}
		  if($file=='..'){continue;}
		  if(!is_file("/home/netlogon/$file")){continue;}
		  if(preg_match("#artica-[0-9]+#",$file)){
		  	if($GLOBALS["VERBOSE"]){echo "removing /home/netlogon/$file\n";}
		  	@unlink("/home/netlogon/$file");
		  }
		  continue;
		}
}


function CountDeUsers(){
	$ldap=new clladp();
	$arr=$ldap->hash_users_ou(null);
	@file_put_contents("/etc/artica-postfix/UsersNumber",count($arr));
}

function fix_lmhosts(){
	$smb=new samba();
	$smb->main_array["global"]["name resolve order"]=null;
	$smb->SaveToLdap();
	
}

function fix_hide_unwriteable_files(){
	
	$smb=new samba();
    foreach ($smb->main_array as $key=>$array){
	    foreach ($array as $valuename=>$value){
			if($valuename=="hide_unwriteable_files"){
				echo "Found $key,$valuename\n";
				$mod=true;
				unset($smb->main_array[$key][$valuename]);
				$smb->main_array[$key]["hide unwriteable files"]=$value;
			}
		}
	}
	
	if($mod==true){$smb->SaveToLdap();}
	
	
}


function usb_mount($uuid,$user){
	$usb=new usb($uuid);
	$unix=new unix();
	writelogs("Mounting $uuid ($usb->TYPE) from $user",__FUNCTION__,__FILE__,__LINE__);
	$path="/media/$uuid";
	$mount=new mount();
	if($mount->ismounted($path)){exit(0);}
	$mount_bin=$unix->find_program("mount");
	$blkid_bin=$unix->find_program("blkid");
	
	writelogs("mount:$mount_bin blkid:$blkid_bin",__FUNCTION__,__FILE__,__LINE__);
	
	if($mount==null){exit(1);}
	if($blkid_bin==null){exit(1);}
	if($usb->TYPE==null){
		exec("$blkid_bin -s UUID -s TYPE 2>&1",$results);
		writelogs("$blkid_bin -s UUID -s TYPE 2>&1 ".count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
		foreach ($results as $key=>$line){
		if(preg_match('#UUID="'.$uuid.'"\s+TYPE="(.+?)"#i',$line,$re)){$type=$re[1];}
		}
	}
	
	if($type==null){
		writelogs("Unable to find type...",__FUNCTION__,__FILE__,__LINE__);
		exit(1);
	}
	
	unset($results);
	
	if(!is_dir($path)){
		if(!@mkdir($path,0755,true)){
			writelogs("create dir \"$path\" permission denied  ",__FUNCTION__,__FILE__,__LINE__);
			exit(1);
		}
	}
	
	$cmd="$mount_bin -t $type $usb->path $path 2>&1";
	writelogs("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	if(!$mount->ismounted($path)){
		writelogs("Mounting $uuid failed",__FUNCTION__,__FILE__,__LINE__);
		foreach ($results as $key=>$line){writelogs("$line",__FUNCTION__,__FILE__,__LINE__);}
		exit(1);
	}
	exit(0);
	
}

function usb_umount($uuid,$user){
	$usb=new usb($uuid);
	$unix=new unix();
	writelogs("Umount $uuid ($usb->TYPE) from $user",__FUNCTION__,__FILE__,__LINE__);
	$path="/media/$uuid";
	$mount=new mount();
	if(!$mount->ismounted($path)){exit(0);}
	$umount_bin=$unix->find_program("umount");
	exec("$umount_bin -l $path 2>&1",$results);
	foreach ($results as $key=>$line){writelogs("$line",__FUNCTION__,__FILE__,__LINE__);}
	if(!$mount->ismounted($path)){exit(0);}
	exit(1);
	
}

function sslbridgre(){
	if(!is_file("/usr/share/artica-postfix/sslbridge/index.php")){return;}
	$unix=new unix();
	$ligghtpd=$unix->LIGHTTPD_USER();
	shell_exec("/bin/chown -R $ligghtpd:$ligghtpd /usr/share/artica-postfix/sslbridge");
	shell_exec("/bin/chmod -R 755 /usr/share/artica-postfix/sslbridge");
	$f[]="<?php";
	$f[]="define(\"LOCALHOST\", \"localhost\");";
	$f[]="define(\"SYSTEMDIR\", '/tmp');";
	$f[]="// define(\"DEBUG\", true);";
	$f[]="?>";
	@file_put_contents("/usr/share/artica-postfix/sslbridge/config.php",@implode("\n",$f));
	
	
}
function EnablePamd(){
$f[]="# PAM configuration file for Cyrus IMAP service";
$f[]="# \$Id: imap.pam 5 2005-03-12 23:19:45Z sven $";
$f[]="#";
$f[]="# If you want to use Cyrus in a setup where users don't have";
$f[]="# accounts on the local machine, you'll need to make sure";
$f[]="# you use something like pam_permit for account checking.";
$f[]="#";
$f[]="# Remember that SASL (and therefore Cyrus) accesses PAM"; 
$f[]="# modules through saslauthd, and that SASL can only deal with";
$f[]="# plaintext passwords if PAM is used.";
$f[]="#";
$f[]="auth     sufficient pam_krb5.so no_user_check validate";
$f[]="account  sufficient pam_permit.so";
@file_put_contents("/etc/pam.d/imap",@implode("\n",$f));
@file_put_contents("/etc/pam.d/smtp",@implode("\n",$f));


}

function DisablePamd(){
	
$f[]="# PAM configuration file for Cyrus IMAP service";
$f[]="# \$Id: imap.pam 5 2005-03-12 23:19:45Z sven $";
$f[]="#";
$f[]="# If you want to use Cyrus in a setup where users don't have";
$f[]="# accounts on the local machine, you'll need to make sure";
$f[]="# you use something like pam_permit for account checking.";
$f[]="#";
$f[]="# Remember that SASL (and therefore Cyrus) accesses PAM"; 
$f[]="# modules through saslauthd, and that SASL can only deal with";
$f[]="# plaintext passwords if PAM is used.";
$f[]="#";
$f[]="@include common-auth";
$f[]="@include common-account";
@file_put_contents("/etc/pam.d/imap",@implode("\n",$f));
@unlink("/etc/pam.d/smtp");
}


function smbtree(){
	$unix=new unix();
	$timefile="/etc/artica-postfix/smbtree.cache";
	$smbtree=$unix->find_program("smbtree");
	if(!is_file($smbtree)){return;}
	$time=file_time_min($timefile);
	if($time>5){
		exec("$smbtree -N 2>&1",$results);
		@file_put_contents($timefile,serialize($results));
	}
	$results=unserialize(@file_get_contents($timefile));
	
	$final=array();
	foreach ($results as $index=>$ligne){
		$ligne=trim($ligne);
		if($GLOBALS["VERBOSE"]){echo "check \"$ligne\"\n";}
		if(preg_match("#^([A-Za-z0-9\_\-]+)$#",$ligne,$re)){
				if($GLOBALS["VERBOSE"]){echo "Found DOMAIN {$re[1]}\n";}
				$DOMAIN=$re[1];
				continue;
			}

		$tr=explode('\\',$ligne);
		if(count($tr)>0){
			unset($tr[0]);
			unset($tr[1]);
			if(count($tr)>1){
				$final[$DOMAIN][$tr[2]]["IP"]=nmblookup($tr[2],null);
				$final[$DOMAIN][$tr[2]]["SHARES"][]=$tr[3];
			}
		
		}
	}
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/smbtree.array",serialize($final));
	shell_exec("/bin/chmod 777 /usr/share/artica-postfix/ressources/logs/smbtree.array");
	
}

function nmblookup($hostname,$ip){
	if(!isset($GLOBALS["nmblookup"])){
		$unix=new unix();
		$GLOBALS["nmblookup"]=$unix->find_program("nmblookup");
	}
	if(trim($hostname)==null){return $ip;}
	if(isset($GLOBALS["NMBLOOKUP-INFOS"][$hostname])){return $GLOBALS["NMBLOOKUP-INFOS"][$hostname];}
	
	$hostname=str_replace('$','',$hostname);
	if($GLOBALS["nmblookup"]==null){
		$unix=new unix();
		$GLOBALS["nmblookup"]=$unix->find_program("nmblookup");
	}
	
	if($GLOBALS["nmblookup"]==null){
		if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> Could not found binary\n";}
		return $ip;
	}
	if(preg_match("#([0-9]+)\.([0-9]+).([0-9]+)\.([0-9]+)#",$hostname)){
		if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> hostname match IP string, aborting\n";}
		return $ip;
	}
	
	if(preg_match("#([0-9]+)\.([0-9]+).([0-9]+)\.([0-9]+)#",$ip,$re)){
		$broadcast="{$re[1]}.{$re[2]}.{$re[3]}.255";
	}else{
		if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> $ip not match for broadcast addr\n";}
		$cmd="{$GLOBALS["nmblookup"]} $hostname 2>&1";
		if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> $cmd\n";}
		exec($cmd,$results);
	}
	
	if(count($results)==0){
		if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> broadcast=$broadcast\n";}
		$cmd="{$GLOBALS["nmblookup"]} -B $broadcast $hostname";
		if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> $cmd\n";}
		exec($cmd,$results);
	}
	
	$hostname_pattern=str_replace(".","\.",$hostname);
	
	foreach ($results as $num=>$ligne){
		if(preg_match("#Got a positive name query response from\s+([0-9\.]+)#",$ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> {$re[1]}\n";}
			$GLOBALS["NMBLOOKUP-INFOS"][$hostname]=$re[1];
			return $re[1];
		}
		
		if(preg_match("#([0-9]+)\.([0-9]+).([0-9]+)\.([0-9]+).+?$hostname_pattern#",$ligne,$re)){
		if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> {$re[1]}.{$re[2]}.{$re[3]}.{$re[4]}\n";}
			$GLOBALS["NMBLOOKUP-INFOS"][$hostname]="{$re[1]}.{$re[2]}.{$re[3]}.{$re[4]}";
			return "{$re[1]}.{$re[2]}.{$re[3]}.{$re[4]}";
		}
		
		
	}
	if($GLOBALS["VERBOSE"]){echo " nmblookup:: --> NO MATCH\n";}
	$GLOBALS["NMBLOOKUP-INFOS"][$hostname]=$ip;
	return $ip;
}

function administrator_update(){
	$samba=new samba();
	$admin_password=$samba->GetAdminPassword("administrator");
	if($admin_password==null){echo "No password set\n";return;}
	$samba->EditAdministrator("administrator",$admin_password);
	echo "updating administrator done...\n";
}

function set_log_level($level){
	$samba=new samba();
	$samba->main_array["global"]["log level"]=$level;
	$samba->SaveToLdap();
	
}	

function quotasrecheck(){
	$unix=new unix();
	$quotaoff=$unix->find_program("quotaoff");
	$quotaon=$unix->find_program("quotaon");
	$quotacheck=$unix->find_program("quotacheck");
	if(is_file($quotacheck)){
		if($GLOBALS["VERBOSE"]){echo " quotacheck:: --> no such file\n";}
		return;
	}
	
	shell_exec("$quotaoff -a");
	shell_exec("$quotacheck -vagum");
	shell_exec("$quotaon -a");
	
}

function test_join(){
	$sock=new sockets();
	$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
	if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
	if($EnableSambaActiveDirectory==0){return;}
	
	
	$unix=new unix();
	$net=$unix->LOCATE_NET_BIN_PATH();
	exec("$net ads testjoin 2>&1",$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#Join is OK#", $ligne)){return;}
		
	}
	
	$adsjoinerror=@implode("\n", $results);
	$results=array();
	
	$config=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaAdInfos")));
	$ad_server=strtolower($config["ADSERVER"]);
	$domain_lower=strtolower($config["ADDOMAIN"]);
	$adminpassword=$config["PASSWORD"];
	$adminpassword=$unix->shellEscapeChars($adminpassword);
	$cmd="$net ads join -W $ad_server.$domain_lower -S $ad_server -U {$config["ADADMIN"]}%$adminpassword 2>&1";
	exec($cmd,$results1);
	$cmd="net join -U {$config["ADADMIN"]}%$adminpassword -S $ad_server 2>&1";
	exec($cmd,$results2);
	$unix->send_email_events("Join to [$ad_server] Active Directory Domain failed", "NET claim:".@implode("\n", $results)."
	Artica reconnect the system to the Active Directory report:\n".@implode("\n", $results1)."\n".@implode("\n", $results2), "system");
	reload();
	
	
}

function recycles(){
	$smb=new samba();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	$unix=new unix();
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		$unix->send_email_events("Virtual trashs: Instance PID $pid already running, task is canceled", "A maintenance task pid $pid is already running... This task pid ".getmypid()." is aborted", "samba");
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$recycles=$smb->LOAD_RECYCLES_BIN();
	
	
	
	if(count($recycles)==0){return;}
	$unix=new unix();
    foreach ($recycles as $directory=>$none){
		$ShareName=$directory;
		$path=$smb->main_array[$directory]["path"].'/.RecycleBin$';
		if($path==null){continue;}
		echo "recycles:: Parsing $directory -> $path\n ";
		if(!is_dir($path)){continue;}
		$finalDirectories=$unix->dirdir($path);
        foreach ($finalDirectories as $DirUid=>$none){
			$uid=basename($DirUid);
			echo "recycles:: Parsing recycles for $uid -> $DirUid\n ";
			Recycles_inject($DirUid,$uid,$ShareName);
		}
		
		
		
		
	}
	//DirRecursiveFiles
	
	
	
}

function Recycles_inject($path,$uid,$ShareName){
	$unix=new unix();
	$q=new mysql();
	$arrays=$unix->DirRecursiveFiles($path);
	$prefix="INSERT IGNORE INTO samba_recycle_bin_list (path,uid,sharename,filesize) VALUES";
    foreach ($arrays as $userpath){
		$size=@filesize($userpath);
		$userpath=addslashes($userpath);
		$path=addslashes($path);
		$uid=addslashes($uid);
		$sql[]="('$userpath','$uid','$ShareName','$size')";
		if(count($sql)>500){
				$finalsql="$prefix ".@implode(",", $sql);
				$sql=array();
				$q->QUERY_SQL($finalsql,"artica_backup");
				if(!$q->ok){echo $q->mysql_error;echo "\n$finalsql\n";}
		}
			
		}
		
		
	if(count($sql)>0){
		if($GLOBALS["VERBOSE"]){echo "Inserting ".count($sql)." events\n";}
		$finalsql="$prefix ".@implode(",", $sql);$sql=array();
		$q->QUERY_SQL($finalsql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;echo "\n$finalsql\n";}
	}
	
}

function recycles_delete(){
	$unix=new unix();
	$sql="SELECT * FROM samba_recycle_bin_list WHERE delete=1";
	$q=new mysql();
	$unix=new unix();
	$c=0;
	$mv=$unix->find_program("mv");
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$path=$ligne["path"];
		$path_org=$path;
		
		$sql="DELETE FROM samba_recycle_bin_list WHERE path='$path_org'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			$unix->send_email_events("Virtual trashs: mysql error $q->mysql_error", "File $path \ncannot be deleted from trash", "samba");
			continue;
		}
		if(is_file($path_org)){		
			@unlink($path_org);
			$c++;
		}

	} 	
	
	if($c>0){
		$unix->send_email_events("Virtual trashs: $c file(s) deleted", "$c File(s) has been automatically deleted from virtual trash.\n", "samba");
	}
	
}

function recycles_privileges($SourcePath,$uid){
	$unix=new unix();
	$dir=dirname($SourcePath);
	if(strpos($dir, "RecycleBin$/$uid/")==0){return;}
	$DestPos=strpos($SourcePath,"/.RecycleBin$");
	$finalDestination=substr($SourcePath, 0,$DestPos);
	
	
	$suffix=substr($dir, 0,strpos($dir, "RecycleBin$/$uid/")+strlen("RecycleBin$/$uid/"));
	
	$dir=substr($dir, strpos($dir, "RecycleBin$/$uid/")+strlen("RecycleBin$/$uid/"),strlen($dir));
	
	echo $dir."\n";
	echo "suffix:$suffix\n";
	echo "Destination:$finalDestination\n";
	$tr=explode("/",$dir);

    foreach ($tr as $directory){
		$dirs[]=$directory;
		$sourcedir="$suffix/". @implode("/", $dirs);
		$sourcedir=str_replace('//', "/", $sourcedir);
		$actualPerms = file_perms($sourcedir,true);
		$stat=stat($sourcedir);
		$uid=$stat["uid"];
		$gid=$stat["gid"];
		
		
		if($GLOBALS["VERBOSE"]){echo "recycles_privileges():: $sourcedir -> $actualPerms ($uid $gid)\n";}
		$FinalDirectoryDestination="$finalDestination/". @implode("/", $dirs);
		$FinalDirectoryDestination=str_replace('//', "/", $FinalDirectoryDestination);
		if(!is_dir($FinalDirectoryDestination)){
			@mkdir($FinalDirectoryDestination,$actualPerms,true);
			shell_exec("/bin/chmod $actualPerms ".$unix->shellEscapeChars($FinalDirectoryDestination));
			shell_exec("/bin/chown $uid:$gid ".$unix->shellEscapeChars($FinalDirectoryDestination));
		}
	}
}

function file_perms($file, $octal = false){
    if(!file_exists($file)) return false;
    $perms = fileperms($file);
    $cut = $octal ? 2 : 3;
    return substr(decoct($perms), $cut);
}


function recycles_restore(){
	$sql="SELECT * FROM samba_recycle_bin_list WHERE restore=1";
	$q=new mysql();
	$unix=new unix();
	$mv=$unix->find_program("mv");
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$path=$ligne["path"];
		$path_org=$path;
		echo "$path\n";
		$uid=$ligne["uid"];
		$pathToRestore=str_replace("/.RecycleBin$/$uid", "", $path);
		$pathToRestoreorg=$pathToRestore;
		if(!is_file($path)){
			echo "FAILED $path no such file\n";
			$sql="DELETE FROM samba_recycle_bin_list WHERE path='$path_org'";
			$q->QUERY_SQL($sql,"artica_backup");
			continue;
		}
		
		
		$path=$unix->shellEscapeChars($path);
		$dirname=dirname($pathToRestore);
		$pathToRestore=$unix->shellEscapeChars($pathToRestore);
		
		
		echo "restore to \"$dirname\"\n";
		recycles_privileges($path_org,$uid);
		if(!is_dir($dirname)){
			echo "FAILED ! $dirname no such directory\n";
			$sql="UPDATE samba_recycle_bin_list SET restore=0 WHERE path='$path_org'";
			$q->QUERY_SQL($sql,"artica_backup");			
			continue;
		}
		
		$cmd="$mv -b $path $pathToRestore";
		$ras=shell_exec($cmd);
		if(!is_file($pathToRestoreorg)){
			echo "FAILED ! mv $path $pathToRestore $ras\n";
			$sql="UPDATE samba_recycle_bin_list SET restore=0 WHERE path='$path_org'";
			$q->QUERY_SQL($sql,"artica_backup");
			continue;
		}else{
			$sql="DELETE FROM samba_recycle_bin_list WHERE path='$path_org'";
			$q->QUERY_SQL($sql,"artica_backup");	
		}
		
	}
	
}

function ScanTrashs(){
	$unix=new unix();
	$ScanTrashPeriod=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ScanTrashTime");
	$ScanTrashTTL=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ScanTrashTTL");
	if(system_is_overloaded(basename(__FILE__))){$unix->send_email_events("Scanning virtual trashs aborted (system is overloaded)", "The task was stopped and will restarted in $ScanTrashPeriod minutes", "samba");return;}
	if(!is_numeric($ScanTrashPeriod)){$ScanTrashPeriod=450;}
	if(!is_numeric($ScanTrashTTL)){$ScanTrashTTL=7;}	
	if($ScanTrashPeriod<30){$ScanTrashPeriod=30;}
	if($ScanTrashTTL<1){$ScanTrashTTL=1;}
	$filetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($unix->file_time_min($filetime)<$ScanTrashPeriod){return;}
	@unlink($filetime);
	@file_put_contents($filetime, time());
	$sql="UPDATE samba_recycle_bin_list SET delete=1 WHERE zDate<DATE_SUB(NOW(), INTERVAL $ScanTrashTTL DAY)";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){$unix->send_email_events("Virtual trashs: unable to set files TTL, mysql error", "Artica cannot update the mysql table\n$sql\n", "samba");}
	recycles_delete();
	recycles();	
}

function smbstatus_injector(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		writelogs("$pid already exists in memory, aborting",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE smbstatus_users","artica_events");
	$smbstatus=$unix->find_program("smbstatus");
	if(!is_file($smbstatus)){return;}
	exec("$smbstatus -p 2>&1",$results);

	$prefix="INSERT IGNORE INTO smbstatus_users ( `pid`,`username`,`usersgroup`,`computer`,`ip_addr`) VALUES ";
	foreach ($results as $index=>$line){
		if(trim($line)==null){continue;}
		if(!preg_match("#([0-9]+)\s+(.+?)\s+(.+?)\s+\s+(.+?)\s+\((.+?)\)#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "[P]:'$line' -> no match #([0-9]+)\s+(.+?)\s+(.+?)\s+\s+(.+?)\s+\((.+?)\)#\n";}
			
			continue;}
		$sql[]="('{$re[1]}','{$re[2]}','{$re[3]}','{$re[4]}','{$re[5]}')";		
		if(count($sql)>500){
			$injectsql=$prefix.@implode(",", $sql);
			$sql=array();
			$q->QUERY_SQL($injectsql,"artica_events");
		}	
	}
	
	if(count($sql)>0){
		$injectsql=$prefix.@implode(",", $sql);
		$sql=array();
		$q->QUERY_SQL($injectsql,"artica_events");
	}		
	
	$results=array();
	exec("$smbstatus -S 2>&1",$results);
	
	foreach ($results as $index=>$line){
		if(trim($line)==null){continue;}
		if(!preg_match("#^(.+?)\s+([0-9]+)\s+(.+?)\s+(.+)$#", $line,$re)){if($GLOBALS["VERBOSE"]){echo "[S]:'$line' -> no match ^(.+?)\s+([0-9]+)\s+(.+?)\s+(.+)$\n";}continue;}
			$share=addslashes($re[1]);
			$pid=$re[2];
			$time=strtotime($re[4]);
			$date=date('Y-m-d H:i:s',$time);
			if($GLOBALS["VERBOSE"]){echo "SHARE='$share' {$re[4]} = $date pid=$pid\n ";}
			
			$q->QUERY_SQL("UPDATE smbstatus_users SET `sharename`='$share',`zDate`='$date' WHERE `pid`='$pid'","artica_events");
			if(!$q->ok){if($GLOBALS["VERBOSE"]){echo "$q->mysql_error\n";}}
			
	}	
	
	$results=array();
	exec("$smbstatus -L 2>&1",$results);
		
	$q->QUERY_SQL("TRUNCATE TABLE smbstatus_users_dirs","artica_events");
	$prefix="INSERT IGNORE INTO smbstatus_users_dirs (`zmd5`, `pid`,`directory`,`filepath`,`zDate`) VALUES ";
	foreach ($results as $index=>$line){
	if(trim($line)==null){continue;}
	if(!preg_match("#^([0-9]+)\s+[0-9]+\s+[A-Z\_]+\s+[a-z0-9]+\s+[A-Z\_\+]+\s+[A-Z\_\+]+\s+(.+?)\s+\s+(.+?)\s+\s+(.+?)$#", $line,$re)){if($GLOBALS["VERBOSE"]){echo "$line -> no match\n";}continue;}
		$pid=$re[1];
		$dir=addslashes($re[2]);
		$path=addslashes($re[3]);
		$time=strtotime($re[4]);
		$date=date('Y-m-d H:i:s',$time);
		$md5=md5("$pid$dir$path$date");
		if($GLOBALS["VERBOSE"]){echo "$pid -> $dir\n";}
		
		$sql[]="('$md5','$pid','$dir','$path','$date')";		
		if(count($sql)>500){
			$injectsql=$prefix.@implode(",", $sql);
			$sql=array();
			$q->QUERY_SQL($injectsql,"artica_events");
		}			
	}
	
	if(count($sql)>0){
		$injectsql=$prefix.@implode(",", $sql);
		$sql=array();
		$q->QUERY_SQL($injectsql,"artica_events");
	}		

}

function winbindfix(){
	
	
}
function msktutil_version(){
	$unix=new unix();
	$msktutil=$unix->find_program("msktutil");
	$t=exec("$msktutil --version 2>&1");
	if(preg_match("#msktutil version\s+([0-9\.]+)#", $t,$re)){
		$tr=explode(".", $re[1]);
		return $tr[1];
	}
}
function run_msktutils(){
	kinit();
	$unix=new unix();
	$sock=new sockets();
	if(is_file("/usr/sbin/msktutil")){@chmod("/usr/sbin/msktutil",0755);}
	$msktutil=$unix->find_program("msktutil");
	$function=__FUNCTION__;
	
	
	
	if(!is_file($msktutil)){
		if(is_file("/home/artica/mskutils.tar.gz.old")){
			echo "Starting......: ".date("H:i:s")." $function, uncompress /home/artica/mskutils.tar.gz.old\n";
			shell_exec("tar xf /home/artica/mskutils.tar.gz.old -C /");
		}
	}
	
	$msktutil=$unix->find_program("msktutil");
	if(!is_file($msktutil)){	
		echo "Starting......: ".date("H:i:s")." $function, msktutil not installed, you should use it..\n";
		return;
	}
	
	$array=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaAdInfos")));
	
	$domainUp=strtoupper($array["ADDOMAIN"]);
	$domain_lower=strtolower($array["ADDOMAIN"]);
	$adminpassword=$array["PASSWORD"];
	$adminpassword=$unix->shellEscapeChars($adminpassword);
	$adminname=$array["ADADMIN"];
	$ad_server=$array["ADSERVER"];
	$workgroup=$array["WORKGROUP"];
	$ipaddr=trim($array["ADSERVER_IP"]);	
	
	
	if(!isset($array["COMPUTER_BRANCH"])){$array["COMPUTER_BRANCH"]="CN=Computers";}
	$myFullHostname=$unix->hostname_g();
	$myNetBiosName=$unix->hostname_simple();
	
	$hostname=strtolower(trim($array["ADSERVER"])).".".strtolower(trim($array["ADDOMAIN"]));
	if(!isset($array["WINDOWS_SERVER_TYPE"])){$array["WINDOWS_SERVER_TYPE"]="WIN_2003";}
	echo "Starting......: ".date("H:i:s")." $function, computers branch `{$array["COMPUTER_BRANCH"]}`\n";
	echo "Starting......: ".date("H:i:s")." $function, my full hostname `$myFullHostname`\n";
	echo "Starting......: ".date("H:i:s")." $function, my netbios name `$myNetBiosName`\n";
	echo "Starting......: ".date("H:i:s")." $function, Active Directory hostname `$hostname` ($ipaddr)\n";
	$kdestroy=$unix->find_program("kdestroy");

	$domain_controller=$hostname;
	if($ipaddr<>null){$domain_controller=$ipaddr;}

	$enctypes=null;
	if( $array["WINDOWS_SERVER_TYPE"]=="WIN_2008AES"){
		$enctypes=" --enctypes 28";
	}
	$msktutil_version=msktutil_version();
	echo "Starting......: ".date("H:i:s")." $function, msktutil version 0.$msktutil_version\n";

	$f[]="$msktutil -c -b \"{$array["COMPUTER_BRANCH"]}\"";
	$f[]="-s HTTP/$myFullHostname -h $myFullHostname -k /etc/krb5.keytab";
	$f[]="--computer-name $myNetBiosName --upn HTTP/$myFullHostname --server $domain_controller $enctypes";
	$f[]="--verbose";
	if($msktutil_version==4){
		//$f[]="--user-creds-only";
	}


	$cmdline=@implode(" ", $f);
	echo "Starting......: ".date("H:i:s")." $function,`$cmdline`\n";
	exec("$cmdline 2>&1",$results);
    foreach ($results as $a){if(trim($a)==null){continue;}echo "Starting......: ".date("H:i:s")." $function, $a Line:".__LINE__."\n";}

	if($msktutil_version==4){
		$cmdline="$msktutil --auto-update --verbose --computer-name $myNetBiosName --server $domain_controller";
		exec("$cmdline 2>&1",$results);
        foreach ($results as $a){if(trim($a)==null){continue;}echo "Starting......: ".date("H:i:s")." $function, $a Line:".__LINE__."\n";}
	}
		


}



function JOIN_ACTIVEDIRECTORY(){
	$unix=new unix();	
	$function=__FUNCTION__;
	$user=new usersMenus();
	$netbin=$unix->LOCATE_NET_BIN_PATH();
	
	if(!is_file($netbin)){echo "Starting......: ".date("H:i:s")."  $function, net, no such binary\n";return;}
	if(!$user->WINBINDD_INSTALLED){echo "Starting......: ".date("H:i:s")."  $function, Samba, no such software\n";return;}
	$NetADSINFOS=$unix->SAMBA_GetNetAdsInfos();
	$KDC_SERVER=$NetADSINFOS["KDC server"];
	$sock=new sockets();
	$array=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaAdInfos")));
	run_msktutils();

$domainUp=strtoupper($array["ADDOMAIN"]);
$domain_lower=strtolower($array["ADDOMAIN"]);
$adminpassword=$array["PASSWORD"];
$adminpassword=$unix->shellEscapeChars($adminpassword);
$adminname=$array["ADADMIN"];
$ad_server=$array["ADSERVER"];
$workgroup=$array["WORKGROUP"];
$ipaddr=trim($array["ADSERVER_IP"]);

if($GLOBALS["VERBOSE"]){echo "$function, Using Password: $adminpassword";}

if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Trying to relink this server with Active Directory $ad_server.$domain_lower server", basename(__FILE__));}
echo "Starting......: ".date("H:i:s")."  Samba, [$adminname]: Kdc server ads : $KDC_SERVER\n";

if($KDC_SERVER==null){
		$cmd="$netbin ads join -W $ad_server.$domain_lower -S $ad_server -U $adminname%$adminpassword 2>&1";
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")."  $function, $cmd\n";}
		exec("$cmd",$results);
		foreach ($results as $index=>$line){echo "Starting......: ".date("H:i:s")."  $function, ads join [$adminname]: $line\n";}	
		$NetADSINFOS=$unix->SAMBA_GetNetAdsInfos();
		$KDC_SERVER=$NetADSINFOS["KDC server"];
	}
	
	if($KDC_SERVER==null){
		echo "Starting......: ".date("H:i:s")."  Samba, [$adminname]: unable to join the domain $domain_lower\n";
		
	}
	
	

	
echo "Starting......: ".date("H:i:s")."  Samba, [$adminname]: setauthuser..\n";
$cmd="$netbin setauthuser -U $adminname%$adminpassword";	
if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")."  $function, $cmd\n";}
shell_exec($cmd);	

if($ipaddr==null){
	$JOINEDRES=false;
	echo "Starting......: ".date("H:i:s")."  Samba, [$adminname 0]: join for $workgroup (without IP addr)\n";	
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")."  $function,[$adminname 0]: $cmd\n";}
	$cmd="$netbin join -U $adminname%$adminpassword $workgroup 2>&1";
	exec($cmd,$A1);
    foreach ($A1 as $index=>$line){
		if(preg_match("#Joined#", $line)){
			echo "Starting......: ".date("H:i:s")."  Samba, [$adminname 0]: join for $workgroup (without IP addr) success\n";
			$JOINEDRES=true;
			break;
		}
		if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Starting......: ".date("H:i:s")."  Samba, $line", basename(__FILE__));}
	}
	
	if(!$JOINEDRES){
		echo "Starting......: ".date("H:i:s")."  Samba, [$adminname 0]: join as netrpc.. (without IP addr)\n";	
		$cmd="$netbin rpc join -U $adminname%$adminpassword $workgroup 2>&1";
		exec($cmd,$A2);
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")."  $function, $cmd\n";}
        foreach ($A2 as $index=>$line){
			if(preg_match("#Joined#", $line)){
				echo "Starting......: ".date("H:i:s")."  Samba, [$adminname 0]: join for $workgroup (without IP addr) success\n";
				$JOINEDRES=true;
				break;
			}
			if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Starting......: ".date("H:i:s")."  Samba, $line", basename(__FILE__));}	
		}
	}
	
}

if($ipaddr<>null){
	if(!$GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")."  $function, [$adminname 1]: ads '$netbin ads join -I $ipaddr -U $adminname%**** $workgroup'\n";}
	//$cmd="$netbin ads join -S $ad_server.$domain_lower -I $ipaddr -U $adminname%$adminpassword 2>&1";
	$cmd="$netbin ads join -I $ipaddr -U $adminname%$adminpassword $workgroup 2>&1";
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")."  $function,[$adminname 1]: $cmd\n";}
	exec($cmd,$BIGRES2);
    foreach ($BIGRES2 as $index=>$line){
		if(preg_match("#Failed to join#i", $line)){
			echo "Starting......: ".date("H:i:s")."  $function, [$adminname 1]: ads join failed ($line), using pure IP\n";
			if(!$GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")."  $function, [$adminname 1]: '$netbin ads join -I $ipaddr -U $adminname%*** $workgroup'\n";}
			
			
			$cmd="$netbin ads join -I $ipaddr -U $adminname%$adminpassword $workgroup 2>&1";
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")."  $function, $cmd\n";}
			$BIGRES1=array();
			exec($cmd,$BIGRES1);
            foreach ($BIGRES1 as $index=>$line){
				echo "Starting......: ".date("H:i:s")."  $function, [$adminname 2] $line\n";
				if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Starting......: ".date("H:i:s")."  $function, $line", basename(__FILE__));}
			}
			
			break;
		}
		echo "Starting......: ".date("H:i:s")."  Samba,[$adminname 1] $line\n";
		if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Starting......: ".date("H:i:s")."  $function, $line", basename(__FILE__));}
	}
	
	
	/*echo "Starting......: ".date("H:i:s")."  Samba, [$adminname]: join with  IP Adrr:$ipaddr..\n";	
	$cmd="$netbin join -U $adminname%$adminpassword -I $ipaddr";
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")."  Samba, $cmd\n";}
	shell_exec($cmd);*/

}

	if($KDC_SERVER==null){$NetADSINFOS=$unix->SAMBA_GetNetAdsInfos();$KDC_SERVER=$NetADSINFOS["KDC server"];}
	if($KDC_SERVER==null){echo "Starting......: ".date("H:i:s")."  Samba, [$adminname]: unable to join the domain $domain_lower\n";}	

	echo "Starting......: ".date("H:i:s")."  Samba, [$adminname]: Kdc server ads : $KDC_SERVER\n";
	
	unset($results);
	$cmd="$netbin ads keytab create -P -U $adminname%$adminpassword 2>&1";
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")."  Samba, $cmd\n";}
	exec("$cmd",$results);
}





?>