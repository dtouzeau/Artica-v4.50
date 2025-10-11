<?php

$GLOBALS["FORCE"]=false;
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["posix_getuid"]=0;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.autofs.inc');
include_once(dirname(__FILE__) . '/ressources/logs.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
$unix=new unix();
$GLOBALS["losetup"]=$unix->find_program("losetup");

if($argv[1]=="--checks"){Checks();exit();}
if($argv[1]=="--build"){build();exit();}
if($argv[1]=="--delete-container"){delete($argv[2]);exit();}
if($argv[1]=="--patch"){patch_grub_default($argv[2]);exit();}


build();


function delete($ID){
	
	$q=new mysql();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM users_containers WHERE `container_id`='$ID'","artica_backup"));
	$directory=trim($ligne["directory"]);
	$ID=$ligne["container_id"];
	$ContainerFullPath=$directory."/$ID.disk";
	$mountpoint="/media/artica_containers/membersdisks/disk$ID";
	$unix=new unix();
	$umount=$unix->find_program("umount");
	shell_exec("$umount -l $mountpoint");
	@unlink($ContainerFullPath);
	$q->QUERY_SQL("DELETE FROM users_containers WHERE `container_id`='$ID'","artica_backup");
	build();
}

function Checks($nopid=false){
	$unix=new unix();
	
	if(system_is_overloaded(basename(__FILE__))){exit();}
	
	if(!$nopid){
		$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($GLOBALS["VERBOSE"]){echo "Time file: $timefile\n";}
		if($unix->process_exists($pid)){echo "Starting......: ".date("H:i:s")." Already process exists pid $pid\n";return;}
		$time=$unix->file_time_min($timefile);
		if($time<15){return;}
		@unlink($timefile);
		@file_put_contents($timefile, time());
	}
	$ARRAY=array();
	$CHECKS=array();
	$CHECKS2=array();
	if($GLOBALS["VERBOSE"]){echo "Checks mounted containers...\n";}
	
	
	$ls=$unix->find_program("ls");
	
	$q=new mysql();
	$sql="SELECT * FROM users_containers WHERE created=1 AND onerror=0";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$directory=trim($ligne["directory"]);
		$ID=$ligne["container_id"];
		$ContainerFullPath=$directory."/$ID.disk";
		$MountedPath="/media/artica_containers/membersdisks/disk$ID";
		if(system_is_overloaded(basename(__FILE__))){exit();}
		if($GLOBALS["VERBOSE"]){echo __LINE__."] Chock: $MountedPath\n";}
		
		if(!is_file($ContainerFullPath)){
			$q->QUERY_SQL("UPDATE users_containers SET `created`='0' WHERE container_id=$ID","artica_backup");
			continue;
		}
		
		shell_exec("$ls $MountedPath/*");
		
	
	}
	
	
	
	$f=explode("\n",@file_get_contents("/proc/mounts"));
	foreach ($f as $num=>$ligne){
		if(!preg_match("#^(.+)?\s+\/media\/artica_containers\/membersdisks\/disk([0-9]+)#", $ligne,$re)){continue;}
		$mounted=$re[1];
		$ID=$re[2];
		if($GLOBALS["VERBOSE"]){echo __LINE__."] ID: $ID mounted on `$mounted` ->DF_SATUS_K($mounted) \n";}
		$DF_SATUS=$unix->DF_SATUS_K($mounted);
		if($GLOBALS["VERBOSE"]){
			while (list ($num, $ligne) = each ($DF_SATUS) ){
				echo __LINE__."] ID: $ID Key `$num` => \"$ligne\"\n";
			}
			reset($DF_SATUS);
		}
		$ARRAY[$ID]["MOUNTED"]=$mounted;
		$ARRAY[$ID]["STATUS"]=$DF_SATUS;
		$ARRAY[$ID]["TIME"]=time();
		$CHECKS[$ID]=true;
		if($GLOBALS["VERBOSE"]){echo "*****************\n";}
	}
	
	$q=new mysql();
	$sql="SELECT * FROM users_containers WHERE created=1 AND onerror=0";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$count=mysqli_num_rows($results);
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	
	$mkfs_ext4=$unix->find_program("mkfs.ext4");
	$typ="ext4";
	if(!is_file($mkfs_ext4)){$typ="ext3";}
	
	if(!$q->FIELD_EXISTS("users_containers","status","artica_backup")){
		$sql="ALTER TABLE `users_containers` ADD `status` TEXT";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$directory=trim($ligne["directory"]);
		$ID=$ligne["container_id"];
		$ContainerFullPath=$directory."/$ID.disk";
		
		if(!isset($ARRAY[$ID])){
			if($GLOBALS["VERBOSE"]){__LINE__."] ID: $ID `No data...`\n";}
			continue;
		}
		
		if(count($ARRAY[$ID])==0){
			if($GLOBALS["VERBOSE"]){__LINE__."] ID: $ID `No data...`\n";}
			continue;
		}
		
		if($GLOBALS["VERBOSE"]){echo "ID: $ID `$ContainerFullPath`\n";}
		$CHECKS2[$ID]=$ContainerFullPath;
		$status=mysql_escape_string2(base64_encode(serialize($ARRAY[$ID])));
		$q->QUERY_SQL("UPDATE users_containers SET `status`='$status' WHERE container_id=$ID","artica_backup");
	}

		
		
	
	
		
		
}


function build(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	$unix=new unix();
	if($unix->process_exists($pid)){echo "Starting......: ".date("H:i:s")." Already process exists pid $pid\n";return;}
	
	@file_put_contents($pidfile,getmypid());
	$php=$unix->LOCATE_PHP5_BIN();
	if(system_is_overloaded()){$unix->THREAD_COMMAND_SET("$php ".__FILE__." --build");return;}
	
	patch_grub_default();
	$sock=new sockets();
	$q=new mysql();
	$sql="SELECT * FROM users_containers WHERE created=0 AND onerror=0";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$count=mysqli_num_rows($results);
	if(!$q->ok){echo "Starting......: ".date("H:i:s")." users_containers $q->mysql_error\n";return;}
	
	
	echo "Starting......: ".date("H:i:s")." $count containers to build\n";
	if($count>0){
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$directory=trim($ligne["directory"]);
		$ID=$ligne["container_id"];
		if($directory==null){users_containers_error($ID,"No specified main directory...");continue;}
		
		$directory_size_avai=$unix->DIRECTORY_FREEM($directory);
		if($directory_size_avai==0){users_containers_error($ID,"no space left on specified directory");continue;}
		
		@mkdir($directory,0755,true);
		if(!is_dir($directory)){users_containers_error($ID,"Permission denied on specified directory");continue;}
		
		$ContainerFullPath=$directory."/$ID.disk";
		$size=$ligne["container_size"];
		
		if($size>$directory_size_avai){users_containers_error($ID,"{$size}MB will exceed space on main storage");continue;			}
		
		$label="{$ID}_disk";
		echo "Starting......: ".date("H:i:s")." Verify $ContainerFullPath with a size of {$size}MB\n";
		
		
		if(!stat_system($ContainerFullPath)){
			echo "Starting......: ".date("H:i:s")." buil_dd $ContainerFullPath {$size}MB\n";
			if(!build_dd($ContainerFullPath,$size)){
				users_containers_error($ID,"Unable to build the virtual disk (ERR.".__LINE__.")");
				continue;
			}
		}
		
		$GetLoops=GetLoops();
		if(!stat_system($ContainerFullPath)){
			users_containers_error($ID,"Unable to build the virtual disk (ERR.".__LINE__.")");
			continue;
		}
		
		if($GetLoops[$ContainerFullPath]==null){
			echo "Starting......: ".date("H:i:s")." $ContainerFullPath no such loop\n";
			
			if(!build_loop($ContainerFullPath)){
				echo "`$ContainerFullPath` unable to create loop\n";
				echo "Starting......: ".date("H:i:s")." Re-check the loop list...\n";
				$GetLoops=GetLoops();
				if($GetLoops[$ContainerFullPath]==null){
					users_containers_error($ID,"Loop error (ERR.".__LINE__.")");
					continue;
				}
			}
		}
		
	
		echo "Starting......: ".date("H:i:s")." $ContainerFullPath loop={$GetLoops[$ContainerFullPath]}\n";
		$sql="UPDATE users_containers SET loop_dev='{$GetLoops[$ContainerFullPath]}' WHERE `container_id`='$ID'";
		$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){echo "$q->mysql_error\n";continue;}
		
		$dev=$GetLoops[$ContainerFullPath];
		echo "Starting......: ".date("H:i:s")." $ContainerFullPath is $dev\n";	
		if(!ifFileSystem($dev)){
			if(!mke2fs($dev,$label)){
				users_containers_error($ID,"mke2fs error (ERR.".__LINE__.")");
				continue;
			}
		}
		
		
		$uuid=Getuuid($dev);
		echo "Starting......: ".date("H:i:s")." $dev uuid=$uuid\n";
		$q->QUERY_SQL("UPDATE users_containers SET uuid='$uuid' WHERE `container_id`='$ID'",'artica_backup');
		if($uuid==null){continue;}
		$q->QUERY_SQL("UPDATE users_containers SET created='1' WHERE `container_id`='$ID'",'artica_backup');
	}
	}
	
	@mkdir("/media/artica_containers/membersdisks",0755,true);
	$q=new mysql();
	$sql="SELECT * FROM users_containers WHERE created=1 AND onerror=0";
	$results=$q->QUERY_SQL($sql,"artica_backup");

	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$directory=trim($ligne["directory"]);
		$mkfs_ext4=$unix->find_program("mkfs.ext4");
		$typ="ext4";
		if(!is_file($mkfs_ext4)){$typ="ext3";}
		
		$ID=$ligne["container_id"];
		$ContainerFullPath=$directory."/$ID.disk";
		
		if(!is_file($ContainerFullPath)){
			echo "Starting......: ".date("H:i:s")." $ContainerFullPath no such file\n";
			
		}
		
		$autofs[]="disk$ID\t-fstype=$typ,loop\t:$ContainerFullPath";
		
	}
	echo "Starting......: ".date("H:i:s")." Saving /etc/auto.members\n";
	@file_put_contents("/etc/auto.members", implode("\n", $autofs)."\n");
	@unlink("/etc/init.d/artica-containers");
	shell_exec("/etc/init.d/autofs reload");
	
	$unix=new unix();

	if(is_file("/etc/init.d/iscsitarget")){
		$unix->THREAD_COMMAND_SET("/etc/init.d/iscsitarget restart");
	}
	Checks(true);
	
}



function GetLastLo(){
	exec("{$GLOBALS["losetup"]} -f 2>&1",$results);
	return trim(@implode("",$results));
}

function users_containers_error($ID,$error){
	$q=new mysql();
	$error=mysql_escape_string2($error);
	$q->QUERY_SQL("UPDATE users_containers SET onerror=1, onerrortext='$error' WHERE container_id='$ID'",'artica_backup');
	
}



function mke2fs($dev,$label,$maxfds=0){
	$debug=$GLOBALS["VERBOSE"];
	$label_cmd=null;
	$maxfds_cmd=null;
	$label=strtolower(str_replace(" ", "_", $label));
	$label=trim(substr($label, 0,16));
	$unix=new unix();
	$mkfs_ext4=$unix->find_program("mkfs.ext4");
	if($maxfds>0){
		$maxfds_cmd=" -I 128 -N $maxfds";
	}
		
	if(!is_file($mkfs_ext4)){$mkfs_ext4=$unix->find_program("mkfs.ext3");}
	
	if(!$unix->IsExt4()){
		$mkfs_ext4=$unix->find_program("mkfs.ext3");
	}	
	if($label<>null){$label_cmd=" -L $label";}
	echo "Starting......: ".date("H:i:s")." $dev formatting...\n";		
	$cmd="$mkfs_ext4 $label_cmd$maxfds_cmd -q $dev 2>&1";
	exec($cmd,$results);
	if($debug){echo "mke2fs($dev) -> $cmd ". count($results)." rows\n";}	
	if($debug){foreach ($results as $num=>$line){echo "mke2fs() -> $line\n";}}
	if(ifFileSystem($dev)){return true;}
}

function build_dd($path,$size){
	$dir=dirname($path);
	if(!is_dir($dir)){
		writelogs("$dir no such directory, create it",__FUNCTION__,__FILE__,__LINE__);
		@mkdir(dirname($path),644,true);
		
	}
	
	if(!is_dir($dir)){
		writelogs("$dir no such directory",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	
	
	
	$unix=new unix();
	$dd=$unix->find_program("dd");
	$size=$size*1024;
	$NICE=$unix->EXEC_NICE();
	$cmd=trim("$NICE $dd if=/dev/zero of=$path bs=1024 count=$size 2>&1");
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	echo "build_dd() $cmd ". count($results)." rows\n";
	foreach ($results as $num=>$ligne){echo "build_dd() $ligne\n";}
	if(!stat_system($path)){echo "build_dd() $path no such block\n";return false;}
	if(build_loop($path)){return true;}
	}
	
function build_loop($path){
	$loop_free=GetLastLo();
	$cmd="{$GLOBALS["losetup"]} $loop_free $path 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	if($GLOBALS["VERBOSE"]){echo "build_loop() $cmd ". count($results)." rows\n";}
	foreach ($results as $num=>$ligne){echo "build_loop() $ligne\n";}
	$GetLoops=GetLoops();
	if($GetLoops[$path]<>null){
		if($GLOBALS["VERBOSE"]){echo "build_loop() done {$GetLoops[$path]}\n";}
		return true;	
	}	
	return false;
}

function GetLoops(){
	$cmd="{$GLOBALS["losetup"]} -a 2>&1";
	exec($cmd,$results);	
	foreach ($results as $num=>$ligne){
		if(preg_match("#^(.+?):.+?\((.+?)\)#",$ligne,$re)){
			$array[trim($re[2])]=trim($re[1]);
		}
	}	
	return $array;
	
}

function remove($path){
	$unix=new unix();
	$umount=$unix->find_program("umount");
	$sql="SELECT * FROM loop_disks WHERE `path`='$path'";
	$q=new mysql();
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$disk_name=$ligne["disk_name"];
	$loop_dev=$ligne["loop_dev"];
	$GetLoops=GetLoops();
	$dev=$GetLoops[$path];
	if($dev==null){$dev=$loop_dev;}
	$uuid=Getuuid($dev);
	if($dev<>null){
		echo "Starting......: ".date("H:i:s")." $dev umounting...\n";
		exec("$umount -l $dev 2>&1",$results);
		exec("$umount -l $dev 2>&1",$results);
		exec("$umount -l $dev 2>&1",$results);
		foreach ($results as $num=>$ligne){echo "Starting......: ".date("H:i:s")." $dev $ligne\n";}
		
	}
	
	
	$results=array();
	if($uuid<>null){
		echo "Starting......: ".date("H:i:s")." $dev disconnect $uuid...$disk_name\n";
		$autofs=new autofs();
		$autofs->uuid=$uuid;
		$autofs->by_uuid_removemedia($disk_name,"auto");		
	}
	
	if($dev<>null){
		echo "Starting......: ".date("H:i:s")." dev:`$dev` remove media\n";
		$cmd="{$GLOBALS["losetup"]} -d $dev 2>&1";
		exec($cmd,$results);	
		foreach ($results as $num=>$ligne){echo "Starting......: ".date("H:i:s")." $dev $ligne\n";}	
		if(is_file($path)){
			echo "Starting......: ".date("H:i:s")." $dev remove file\n";
			shell_exec("/bin/rm -f $path");
		}
	}
	echo "Starting......: ".date("H:i:s")." $dev remove entry in database\n";
	$sql="DELETE FROM loop_disks WHERE `path`='$path'";
	$q->QUERY_SQL($sql,"artica_backup");
	echo "Starting......: ".date("H:i:s")." $dev removed\n";
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup /etc/init.d/autofs restart >/dev/null 2>&1 &");
	
}


function ifFileSystem($dev){
		$debug=$GLOBALS["VERBOSE"];
		$unix=new unix();
		$tune2fs=$unix->find_program("tune2fs");
		$cmd="$tune2fs -l $dev 2>&1";
		exec($cmd,$results);
		$array=array();	
		if($debug){echo "ifFileSystem($dev) -> $cmd ". count($results)." rows\n";}	
		foreach ($results as $num=>$line){
			if(preg_match("#Filesystem magic number:\s+(.+)#i",$line,$re)){
				if($debug){echo "ifFileSystem($dev) ->  Filesystem magic number = {$re[1]}\n";}
				return true;
			}
			
		}
		if($debug){echo "ifFileSystem($dev) FALSE\n";}
		return false;
		
	}




function stat_system($path){
	$unix=new unix();
	$stat=$unix->find_program("stat");
	if($GLOBALS["VERBOSE"]){echo "stat -f $path -c %b 2>&1\n";}
	exec("$stat -f $path -c %b 2>&1",$results);
	$line=trim(@implode("",$results));
	if(preg_match("#^[0-9]+#",$line,$results)){return true;}
	return false;
}
function Getuuid($dev):string{
	$debug=$GLOBALS["VERBOSE"];
	$unix=new unix();
	$tune2fs=$unix->find_program("tune2fs");
	$cmd="$tune2fs -l $dev 2>&1";
	exec($cmd,$results);
	if($debug){echo "Getuuid($dev) -> $cmd ". count($results)." rows\n";}
	foreach ($results as $num=>$line){
		if(preg_match("#UUID:\s+(.+)#i",$line,$re)){
		if($debug){echo "Getuuid($dev) -> ". trim($re[1])."\n";}	
		return strval(trim($re[1]));
		}
	}
	return "";
}

function patch_grub_default():bool{
	if(!is_file("/etc/default/grub")){return false;}
	$f=explode("\n",@file_get_contents("/etc/default/grub"));
	foreach ($f as $index=>$line){
		if(preg_match("#^GRUB_CMDLINE_LINUX_DEFAULT.*?drm_kms_helper.*?max_loop=256#", $line)){return true;}
		if(preg_match('#^GRUB_CMDLINE_LINUX_DEFAULT="(.*?)"#',$line,$re)){
			$f[$index]="GRUB_CMDLINE_LINUX_DEFAULT=\"{$re[1]} drm_kms_helper.poll=0 max_loop=256\"";
			$update=true;
		}
	}
	if($update){
		@file_put_contents("/etc/default/grub", @implode("\n", $f));
	}
	
	
	return true;
	
}


?>