<?php

$GLOBALS["PROGRESS"]=false;
$GLOBALS["UPDATE_GRUB"]=false;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if($argv[1]=="--optimize"){optimize();exit();}
if($argv[1]=="--apparmor"){apparmor();exit();}
if($argv[1]=="--rc-local"){rc_local();exit();}


function optimize(){
	$unix					= new unix();
	$GLOBALS["PROGRESS"]	= true;
	$GLOBALS["UPDATE_GRUB"]	= true;
	$php					= $unix->LOCATE_PHP5_BIN();
	$EnableSystemOptimize	= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSystemOptimize"));
	
	if($EnableSystemOptimize==1){
		build_progress("{enable_system_optimization}: ON",10);
		EnableScheduler();
		rc_local();
		$ARRAY=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kernel_values")));
		$ARRAY["swappiness"]=0;
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("kernel_values", serialize($ARRAY));
		build_progress("Build Kernel values....",35);
		system("$php /usr/share/artica-postfix/exec.sysctl.php --restart");
		build_progress("Optimize system disk partitions",50);
		system("$php /usr/share/artica-postfix/exec.patch.fstab.php");
		build_progress("{done}",100);
		
		if(!is_file("/etc/modprobe.d/performance.conf")){
			$f[]="blacklist battery";
			$f[]="blacklist psmouse";
			$f[]="blacklist btusb";
			$f[]="blacklist joydev";
			$f[]="blacklist bluetooth";
			$f[]="blacklist rfcomm";
			$f[]="blacklist bnep";
			$f[]="blacklist uvcvideo";
			$f[]="blacklist videodev";
			$f[]="blacklist v4l_compat_ioctl32";
			$f[]="blacklist lp";
			$f[]="blacklist msdos";
			$f[]="blacklist parport";
			$f[]="blacklist btrfs";
			$f[]="blacklist ufs";
			$f[]="blacklist qnx4";
			$f[]="blacklist hfsplus";
			$f[]="blacklist hfs";
			$f[]="blacklist minix";
			$f[]="blacklist jfs";
			$f[]="blacklist xfs";
			$f[]="blacklist reiserfs";
			$f[]="blacklist ext2";
			$f[]="blacklist zlib_deflate";
			$f[]="blacklist libcrc32c";
			@file_put_contents("/etc/modprobe.d/performance.conf", @implode("\n", $f));
			$update_initramfs=$unix->find_program("update-initramfs");
			shell_exec("$update_initramfs -u -k all");
		}
		
	}else{
		build_progress("{enable_system_optimization}: OFF",10);
		DisableScheduler();
		$ARRAY=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("kernel_values")));
		$ARRAY["swappiness"]=60;
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("kernel_values", serialize($ARRAY));
		build_progress("Build Kernel values....",35);
		system("$php /usr/share/artica-postfix/exec.sysctl.php --restart");
		build_progress("Optimize system disk partitions",50);
		system("$php /usr/share/artica-postfix/exec.patch.fstab.php");
		if(is_file("/etc/modprobe.d/performance.conf")){
			@unlink("/etc/modprobe.d/performance.conf");
			$update_initramfs=$unix->find_program("update-initramfs");
			shell_exec("$update_initramfs -u -k all");
		}
		

		
		
		build_progress("{done}",100);
		
	}
	
}

function rc_local(){
	echo "Starting......: ".date("H:i:s")." Optimize, Scanning HARD drives\n";
	$unix=new unix();
	$dirs=$unix->dirdir("/sys/class/scsi_generic");
	$echobin=$unix->find_program("echo");
	
	$f[]="#!/bin/sh -e";

	foreach ($dirs as $num=>$directory){
		$file="$directory/device/timeout";
		$basename=basename($directory);
		if(!is_file($file)){
			echo "Starting......: ".date("H:i:s")." Optimize, SKIP $file\n";
			continue;}
		echo "Starting......: ".date("H:i:s")." Optimize,$basename\n";
		$f[]="$echobin 180 >$file || true";
		shell_exec("$echobin 180 >$file");
		
		
	}
	
	$dirs=$unix->dirdir("/sys/block");
	foreach ($dirs as $num=>$directory){
		$file="$directory/device/timeout";
		$basename=basename($directory);
		if(!is_file($file)){
			echo "Starting......: ".date("H:i:s")." Optimize, SKIP $file\n";
			continue;}
		echo "Starting......: ".date("H:i:s")." Optimize,$basename\n";
		$f[]="$echobin 180 >$file || true";
		shell_exec("$echobin 180 >$file");
	}
	
	$f[]="";
	$f[]="exit 0";
	$f[]="";
	@file_put_contents("/etc/rc.local", @implode("\n", $f));
	@chmod("/etc/rc.local",0755);
	
}






function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/system.optimize.progress";
	if(!$GLOBALS["PROGRESS"]){return;}
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);
}
function build_progress_appr($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/system.apparmor.progress";
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function apparmor():bool{
	build_progress_appr("Checking GRUB_CMDLINE_LINUX_DEFAULT",15);
	$res=check_apparmor("GRUB_CMDLINE_LINUX_DEFAULT");
	build_progress_appr("Checking GRUB_CMDLINE_LINUX",30);
	$res1=check_apparmor("GRUB_CMDLINE_LINUX");
	if($res==false AND $res1==false){
		build_progress_appr("Checking nothing to do...",100);
		return true;
	}

	build_progress_appr("Update GRUB....",50);
	if(is_file("/usr/sbin/grub-mkconfig")){system("/usr/sbin/grub-mkconfig -o /boot/grub/grub.cfg");}
	build_progress_appr("Update GRUB....",60);
	if(is_file("/usr/sbin/update-grub2")){system("/usr/sbin/update-grub2");}
	build_progress_appr("Update GRUB....",70);
	if(is_file("/usr/sbin/update-grub")){system("/usr/sbin/update-grub");}
	build_progress_appr("Update GRUB....",80);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NEEDRESTART",1);
	build_progress_appr("{done}....",100);
	return true;
}

function check_apparmor($mainKey):bool{
	$EnableAppArmor		= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAppArmor"));
	$apparmor			= " apparmor=$EnableAppArmor";
	$grb=explode("\n",@file_get_contents("/etc/default/grub"));
	foreach ($grb as $num=>$line){
		if(preg_match("#^$mainKey=(.+)#",$line,$se)){
			if(preg_match("#apparmor=([0-9]+)#",$line,$re)){
				$current=intval($re[1]);
				if($current==$EnableAppArmor){return false;}
				$grb[$num]=str_replace("apparmor=$current","apparmor=$EnableAppArmor",$grb[$num]);
				@file_put_contents("/etc/default/grub", @implode("\n", $grb));
				return true;
			}

			$seline=$se[1];
			$seline=str_replace('"',"",$seline);
			$seline=str_replace("'","",$seline);
			$seline=trim($seline);
			$grb[$num]="$mainKey=\"$seline{$apparmor}\"";
			@file_put_contents("/etc/default/grub", @implode("\n", $grb));
			return true;
		}
	}
	return false;
}





function DisableScheduler(){

	$unix=new unix();

	$echo=$unix->find_program("echo");
	$array=$unix->dirdir("/sys/block");

	build_progress("Optimize kernel to LOOP",15);

	while (list ($num, $directory) = each ($array) ){
		if(is_file("$directory/queue/scheduler")){
			build_progress("Optimize ".basename($directory),20);
			echo "Starting......: ".date("H:i:s")." Optimize, turn scheduler to noop and deadline on ". basename($directory)."\n";
			shell_exec("$echo \"noop deadline [cfq]\" >$directory/queue/scheduler");
		}

	}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("XEN_HOST",$unix->IS_CITRIXXEN_HOST());
	$update=false;
	$GRUB_DISABLE_OS_PROBER=false;
	$GRUB_GFXMODE=false;
	$NO_GRAPHIQUE=false;
	if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XEN_HOST"))==1){$NO_GRAPHIQUE=true;}
	$DisableNetworking=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));

	$ifnames=" net.ifnames=0 biosdevname=0";
	if($DisableNetworking==1){$ifnames=null;}

	if(is_file("/etc/default/grub")){
		$grb=explode("\n",@file_get_contents("/etc/default/grub"));
		while (list ($num, $line) = each ($grb) ){
			if(preg_match("#^GRUB_CMDLINE_LINUX_DEFAULT#",$line)){
				if(strpos($line, "noop")==0){
					build_progress("GRUB_CMDLINE_LINUX_DEFAULT",25);
					echo "Starting......: ".date("H:i:s")." Optimize, Grub N1\n";
					$grb[$num]="GRUB_CMDLINE_LINUX_DEFAULT=\"quiet{$ifnames}\"";
					$update=true;
				}
				continue;
			}
			if(preg_match("#^GRUB_CMDLINE_LINUX#",$line)){
				if(strpos($line, "noop")==0){
					build_progress("GRUB_CMDLINE_LINUX",25);
					echo "Starting......: ".date("H:i:s")." Optimize, Grub N2\n";
					$grb[$num]="GRUB_CMDLINE_LINUX=\"{$ifnames}\"";
					$update=true;
				}
				continue;
			}

			if(preg_match("#^GRUB_DISABLE_OS_PROBER#",$line)){
				$GRUB_DISABLE_OS_PROBER=true;
				if(strpos($line, "true")==0){
					$grb[$num]="GRUB_DISABLE_OS_PROBER=true";
					$update=true;
				}
				continue;
			}

			if(!$NO_GRAPHIQUE){
				if(preg_match("#^GRUB_GFXMODE#",$line)){
					$GRUB_GFXMODE=true;
					if(strpos($line, "800")==0){
						$grb[$num]="GRUB_GFXMODE=800x600,640x480";
						$update=true;
					}
					continue;
				}
			}

		}


		if(!$GRUB_DISABLE_OS_PROBER){
			$grb[]="GRUB_DISABLE_OS_PROBER=true\n";
			$update=true;
		}
		if(!$NO_GRAPHIQUE){
			if(!$GRUB_GFXMODE){
				$grb[]="GRUB_GFXMODE=800x600\n";
				$update=true;
			}
		}

		if($GLOBALS["UPDATE_GRUB"]){$update=true;}

		if($update){
			build_progress("Update GRUB....",30);
			echo "Starting......: ".date("H:i:s")." Optimize, Grub N2\n";
			@file_put_contents("/etc/default/grub", @implode("\n", $grb));
			if(is_file("/usr/sbin/grub-mkconfig")){system("/usr/sbin/grub-mkconfig -o /boot/grub/grub.cfg");}
			if(is_file("/usr/sbin/update-grub")){system("/usr/sbin/update-grub");}

		}

	}

}

?>
