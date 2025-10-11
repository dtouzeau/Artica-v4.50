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

if($argv[1]=="--remove"){remove($argv[2]);exit();}


build();


function build(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	$unix=new unix();
	if($unix->process_exists($pid)){
		writelogs("Already process exists pid $pid",__FUNCTION__,__FILE__,__LINE__);
		echo "Already process exists pid $pid\n";
		return;
	}
	
	
	$mysqld=$unix->find_program("mysqld");
	if(!is_file($mysqld)){return;}
	
	@file_put_contents($pidfile,getmypid());
	
	
	$q=new mysql();
	$sql="SELECT * FROM loop_disks ORDER BY `size` DESC";
	
	$results=$q->QUERY_SQL($sql,"artica_backup");	
	if(!$q->ok){
		echo "Starting......: ".date("H:i:s")." Loop disks $q->mysql_error\n";
		return false;
	}
	
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$path=trim($ligne["path"]);
		if(is_dir($path)){
			$oldpath=$path;
			$path=$path."/".time().".disk";
			$sql="UPDATE loop_disks SET path='$path' WHERE `path`='$oldpath'";
			$q->QUERY_SQL($sql,'artica_backup');
		}
		$size=$ligne["size"];
		$maxfds=$ligne["maxfds"];
		$label=$ligne["disk_name"];
		writelogs("check $path ($size)",__FUNCTION__,__FILE__,__LINE__);
		if(!stat_system($path)){
			writelogs("buil_dd $path ($size)",__FUNCTION__,__FILE__,__LINE__);
			if(!build_dd($path,$size)){continue;}
		}
		$GetLoops=GetLoops();
		if(!stat_system($path)){
			writelogs("$path no such file",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
		if($GetLoops[$path]==null){
			writelogs("$path no such loop",__FUNCTION__,__FILE__,__LINE__);
			if(!build_loop($path)){writelogs("`$path` unable to create loop",__FUNCTION__,__FILE__,__LINE__);continue;}
			writelogs("Re-check the loop list...",__FUNCTION__,__FILE__,__LINE__);
			$GetLoops=GetLoops();
			if($GetLoops[$path]==null){writelogs("$path no such loop",__FUNCTION__,__FILE__,__LINE__);continue;}
		}
		
		writelogs("$path loop={$GetLoops[$path]}",__FUNCTION__,__FILE__,__LINE__);
		$sql="UPDATE loop_disks SET loop_dev='{$GetLoops[$path]}' WHERE `path`='$path'";
		
		$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){echo "$q->mysql_error\n";continue;}
		$dev=$GetLoops[$path];
		echo "Starting......: ".date("H:i:s")." $path is $dev\n";	
		if(!ifFileSystem($dev)){if(!mke2fs($dev,$label,$maxfds)){continue;}}
		$uuid=Getuuid($dev);
		echo "Starting......: ".date("H:i:s")." $dev uuid=$uuid\n";
		if($uuid==null){continue;}

		
		
		$autofs=new autofs();
		$autofs->uuid=$uuid;
		$autofs->by_uuid_addmedia($ligne["disk_name"],"auto");
		$nohup=$unix->find_program("nohup");
		shell_exec("$nohup /etc/init.d/artica-postfix autofs restart >/dev/null 2>&1 &");
	}	
	
	
}

function GetLastLo(){
	exec("{$GLOBALS["losetup"]} -f 2>&1",$results);
	return trim(@implode("",$results));
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
	$cmd="$dd if=/dev/zero of=$path bs=1024 count=$size 2>&1";
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
function Getuuid($dev){
	$debug=$GLOBALS["VERBOSE"];
	$unix=new unix();
	$tune2fs=$unix->find_program("tune2fs");
	$cmd="$tune2fs -l $dev 2>&1";
	exec($cmd,$results);
	$array=array();	
	if($debug){echo "Getuuid($dev) -> $cmd ". count($results)." rows\n";}	
	foreach ($results as $num=>$line){
		if(preg_match("#UUID:\s+(.+)#i",$line,$re)){
		if($debug){echo "Getuuid($dev) -> ". trim($re[1])."\n";}	
		return trim($re[1]);
		}
	}
		
}
?>