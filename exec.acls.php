<?php

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.acls.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
writelogs("Task::{$GLOBALS["SCHEDULE_ID"]}:: Executed with ".@implode(" ", $argv)." ","MAIN",__FILE__,__LINE__);
if($argv[1]=='--acls'){applyAcls();exit();}
if($argv[1]=='--acls-single'){ApplySingleAcls_cmdline($argv[2]);exit();}


function applyAcls(){
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	if($unix->process_exists(@file_get_contents("$pidfile"))){
		echo "Already process exists\n";
		return;
	}
	
	@file_put_contents($pidfile,getmypid());
	
	$sql="SELECT `directory` FROM acl_directories";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){if($GLOBALS["VERBOSE"]){
		squid_admin_mysql(2, "Fatal,$q->mysql_error",__FUNCTION__, __FILE__, __LINE__, "acls");
		echo $q->mysql_error."\n";return;}}
	
	$count=mysqli_num_rows($results);
	squid_admin_mysql(2, "INFO,acls $count items",__FUNCTION__, __FILE__, __LINE__, "acls");
	echo "Starting......: ".date("H:i:s")." acls $count items\n";
	if($count==0){return;}
	

	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		ApplySingleAcls($ligne["directory"]);
	}
	
	$setfacl_bin=$unix->find_program("setfacl");
	if(is_file($setfacl_bin)){
		shell_exec("$setfacl_bin -b /tmp 2>&1");
	}
	
	
	
	
}

function ApplySingleAcls_cmdline($md5){
	$q=new mysql();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT `directory` FROM acl_directories WHERE `md5`='$md5'","artica_backup"));
	ApplySingleAcls($ligne["directory"]);
}


function ApplySingleAcls($directory){
		if($directory=="/tmp"){
			echo "Starting......: ".date("H:i:s")." acls $directory is denied\n";
			return;
		}
		$unix=new unix();	
		$chmod_bin=$unix->find_program("chmod");
		$setfacl_bin=$unix->find_program("setfacl");	
		
		$recursive=null;
		$chmod=null;
		$q=new mysql();
		$dir=$unix->shellEscapeChars($directory);
		
		
		if(!is_dir($directory)){
			squid_admin_mysql(2, "INFO,acls $directory no such directory delete it from database",__FUNCTION__, __FILE__, __LINE__, "acls");
			echo "Starting......: ".date("H:i:s")." acls $directory no such directory\n";
			$q->QUERY_SQL("DELETE FROM acl_directories WHERE `directory`='$directory'");
			if(!$q->ok){
				squid_admin_mysql(2, "Fatal,$q->mysql_error",__FUNCTION__, __FILE__, __LINE__, "acls");
				echo $q->mysql_error."\n";}
			return;
		}
		
		$acls=new aclsdirs($directory);
		
		echo "Starting......: ".date("H:i:s")." acls \"$dir\" directory\n";
		
		if(!is_numeric($acls->chmod_octal)){$events[]="octal is not a numeric value...";}
		if(is_numeric($acls->chmod_octal)){
			$events[]="octal \"$acls->chmod_octal\"";
			if(chmod_recursive==1){$events[]="Recursive mode";$recursive=" -R ";}
			$chmod=" ".$acls->chmod_octal;
		}
		
		if($chmod<>null){
			$cmd="$chmod_bin$recursive$chmod $dir";
			$events[]="$cmd";
			exec("$chmod_bin$recursive$chmod $dir 2>&1",$events);
		}
		
		if(strlen($setfacl_bin)<3){
			$events[]="ERROR: setfacl no such binary file";
			$events_text=@implode("\n",$events);
			if($GLOBALS["VERBOSE"]){echo $events_text."\n";}			
			$sql="UPDATE acl_directories SET events='".addslashes($events_text)."' WHERE `md5`='$acls->md5'";
			if($GLOBALS["VERBOSE"]){echo $sql."\n";}
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo "$q->mysql_error\n";}
			return;
			
		}
		
		$cmd="$setfacl_bin -b $dir 2>&1";
		$events[]=$cmd;
		exec("$cmd",$events);
		
		if($GLOBALS["VERBOSE"]){
			if(!is_array($acls->acls_array)){echo "acls_array not an Array\n";}
		}
		
		print_r($acls->acls_array);
		$gp=new groups();
		if(is_array($acls->acls_array["GROUPS"])){
			while (list ($groupname, $array) = each ($acls->acls_array["GROUPS"]) ){	
				$perms=array();
				$perms_strings=null;
				$recurs=null;
				if($array["r"]==1){$perms[]="r";}
				if($array["w"]==1){$perms[]="w";}
				if($array["x"]==1){$perms[]="x";}
				$perms_strings=@implode("",$perms);
				if($perms_strings==null){$events[]="No permissions set for $groupname";continue;}
				if($acls->acls_array["recursive"]==1){$recurs="-R ";}
				$gpid=$gp->GroupIDFromGetEnt($groupname);
				$groupname=utf8_encode($groupname);
				
				if($GLOBALS["VERBOSE"]){echo "`$groupname` as gidNumber `$gpid`\n";}
				if(is_numeric($gpid)){
					if($gpid>0){
						$groupname=$gpid;
					}
				}
					
				
				
				$cmd="$setfacl_bin $recurs-m g:\"$groupname\":$perms_strings $dir 2>&1";
				$events[]=$cmd;
				exec("$cmd",$events);
				
				
				if($acls->acls_array["default"]==1){
					$groupname=utf8_encode($groupname);
					$cmd="$setfacl_bin $recurs-m d:g:\"$groupname\":$perms_strings $dir 2>&1";
					$events[]=$cmd;
					exec("$cmd",$events);					
				}
			}	
		
		}else{
			$events[]="Groups: No acls\n";
		}
		
	if(is_array($acls->acls_array["MEMBERS"])){
			while (list ($member, $array) = each ($acls->acls_array["MEMBERS"]) ){	
				$perms=array();
				$perms_strings=null;
				$recurs=null;
				if($array["r"]==1){$perms[]="r";}
				if($array["w"]==1){$perms[]="w";}
				if($array["x"]==1){$perms[]="x";}
				$perms_strings=@implode("",$perms);
				if($perms_strings==null){
					$events[]="No permissions set for $member";
					continue;
				}
				if($acls->acls_array["recursive"]==1){$recurs="-R";}
				$member=utf8_encode($member);
				$cmd="$setfacl_bin $recurs -m u:\"$member\":$perms_strings $dir 2>&1";
				$events[]=$cmd;
				exec("$cmd",$events);
				
				
				if($acls->acls_array["default"]==1){
					$member=utf8_encode($member);
					$cmd="$setfacl_bin $recurs -m d:u:\"$member\":$perms_strings $dir 2>&1";
					$events[]=$cmd;
					exec("$cmd",$events);					
				}
			}	
		
		}else{
			$events[]="Members: No acls\n";
		}
		$events_text=@implode("\n",$events);
		if($GLOBALS["VERBOSE"]){echo $events_text."\n";}
		squid_admin_mysql(2, "INFO,$directory,\n$events_text",__FUNCTION__, __FILE__, __LINE__, "acls");
		$sql="UPDATE acl_directories SET events='".addslashes($events_text)."' WHERE `md5`='$acls->md5'";
		$q->QUERY_SQL($sql,"artica_backup");		
			
	
}