<?php
$GLOBALS["FORCE"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--convert"){convert($argv[2]);}
if($argv[1]=="--fstab"){Checkfstab($argv[2]);}



function convert($dev){
	$unix=new unix();
	
	$mddev=md5($dev);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$mddev.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){
		$TimeF=$unix->file_time_min($pidfile);
		$unix->send_email_events("Failed Report btrfs conversion on $dev", "A process PID:$pid already running since {$TimeF}Mn", "filesystem");
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	
	$converter=$unix->find_program("btrfs-convert");
	$umount=$unix->find_program("umount");
	$mount=$unix->find_program("mount");
	$btrfs=$unix->find_program("btrfs");
	if(!is_file($converter)){$GLOBALS["EVTS"][]=__LINE__."::$dev btrfs-convert no such binary";return;}
	$BLKID_INFOS=$unix->BLKID_INFOS($dev);
	
	$FS_TYPE=$BLKID_INFOS["TYPE"];
	$GLOBALS["EVTS"][]=__LINE__."::$dev filesystem type=$FS_TYPE";
	
	
	if($FS_TYPE==null){$GLOBALS["EVTS"][]="$dev wrong filesystem type...";return;}
	if($FS_TYPE=="btrfs"){
		$GLOBALS["EVTS"][]="$dev already converted";
		$GLOBALS["EVTS"][]=__LINE__."::$dev Checking fstab";
		Checkfstab($dev);
		$GLOBALS["EVTS"][]=__LINE__."::$dev mounting";
		exec("$mount \"$dev\" 2>&1",$results);
		$GLOBALS["EVTS"][]=@implode("\n", $results);$results=array();
		$GLOBALS["EVTS"][]=__LINE__."::$dev done";
		$unix->send_email_events("Success Report btrfs conversion on $dev", @implode("\n", $GLOBALS["EVTS"]), "filesystem");
		return;
	}
	
	$GLOBALS["EVTS"][]=__LINE__."::$dev Unmounting filesystem";
	exec("$umount \"$dev\" 2>&1",$results);
	$GLOBALS["EVTS"][]=@implode("\n", $results);$results=array();
	$GLOBALS["EVTS"][]=__LINE__."::$dev Running converter";
	exec("$converter \"$dev\" 2>&1",$results);
	$GLOBALS["EVTS"][]=@implode("\n", $results);$results=array();
	$GLOBALS["EVTS"][]=__LINE__."::$dev scanning converter ($btrfs device scan)";
	exec("$btrfs device scan \"$dev\" 2>&1",$results);
	$GLOBALS["EVTS"][]=@implode("\n", $results);$results=array();
	
	$BLKID_INFOS=$unix->BLKID_INFOS($dev);
	$FS_TYPE=$BLKID_INFOS["TYPE"];
	
	
	if($FS_TYPE==null){
		for($i=0;$i<10;$i++){
			$GLOBALS["EVTS"][]=__LINE__."::$dev sleeping 2s..\n";
			sleep(2);
			$BLKID_INFOS=$unix->BLKID_INFOS($dev);
			$FS_TYPE=$BLKID_INFOS["TYPE"];
			if($FS_TYPE<>null){break;}
		}
		
		
	}
	
	
	
	if($FS_TYPE<>"btrfs"){
		$GLOBALS["EVTS"][]=__LINE__."::$dev failed converting to btrfs current=`$FS_TYPE`, aborting";
		$GLOBALS["EVTS"][]=__LINE__."::$dev mounting";
		exec("$mount \"$dev\" 2>&1",$results);
		$GLOBALS["EVTS"][]=@implode("\n", $results);$results=array();
		$GLOBALS["EVTS"][]=__LINE__."::$dev aborting";
		$unix->send_email_events("Failed Report btrfs conversion on $dev", @implode("\n", $GLOBALS["EVTS"]), "filesystem");
		return;
	}
	
	
	$GLOBALS["EVTS"][]=__LINE__."::$dev Checking fstab";
	Checkfstab($dev);
	$GLOBALS["EVTS"][]=__LINE__."::$dev mounting";
	exec("$mount \"$dev\" 2>&1",$results);
	$GLOBALS["EVTS"][]=@implode("\n", $results);$results=array();
	$GLOBALS["EVTS"][]=__LINE__."::$dev done";
	$unix->send_email_events("Success Report btrfs conversion on $dev", @implode("\n", $GLOBALS["EVTS"]), "filesystem");
	
	
}

function Checkfstab($dev){
	$devRgx=str_replace("/", "\/", $dev);
	$devRgx=str_replace(".", "\.", $devRgx);
	$f=file("/etc/fstab");
	$change=false;
	foreach ($f as $num=>$ligne){
		if(preg_match("#$devRgx\s+(.+?)\s+([a-z0-9]+)\s+#", $ligne,$re)){
			$GLOBALS["EVTS"][]=__LINE__."::Found $dev must mounted on {$re[1]} FS:{$re[2]}";
			$f[$num]="$dev\t{$re[1]}\tbtrfs\trw,relatime  0    1";
			$change=true;
		}
		                 
	}
	
	
	if($change){
		reset($f);
		$GLOBALS["EVTS"][]=__LINE__."::$dev Cleaning fstab";
		foreach ($f as $num=>$ligne){
			$ligne=trim(str_replace("\r", "", $ligne));
			$ligne=trim(str_replace("\r\n", "", $ligne));
			$ligne=trim(str_replace("\n", "", $ligne));
			if(trim($ligne)==null){continue;}
			$t[]=$ligne;
		}
		
		$GLOBALS["EVTS"][]=__LINE__."::$dev Wrinting fstab";
		@file_put_contents("/etc/fstab", @implode("\n", $t)."\n");
		$GLOBALS["EVTS"][]=__LINE__."::$dev Writing fstab done";
	}
	
}


