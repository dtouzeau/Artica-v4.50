<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");

$GLOBALS["DEBUG"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["LOGFILE"]="/usr/share/artica-postfix/ressources/logs/vmtools.debug";
if(preg_match("#--verbose#",implode(" ",$argv))){
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}

	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$unix=new unix();
	if($unix->process_exists($pid,basename(__FILE__))){events("PID: $pid Already exists....");exit();}



function install(){
   // blkid -t LABEL="Citrix VM Tools";



}



function installapt(){
	@unlink($GLOBALS["LOGFILE"]);
	$unix=new unix();
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$php=$unix->LOCATE_PHP5_BIN();
	echo "Please wait...\n";
	build_progress("{update_debian_repository}",5);
	$aptget=$unix->find_program("apt-get");
	build_progress("{updating_repository}",15);
	
	echo "Please wait, running apt-get install\n";
	$cmd="DEBIAN_FRONTEND=noninteractive $aptget update";
	system($cmd);
	
	build_progress("{INSTALL_VMWARE_TOOLS}",50);
	@unlink("/var/log/artica-apget.log");
	
	$unix->DEBIAN_INSTALL_PACKAGE("open-vm-tools");
	
	echo @file_get_contents("/var/log/artica-apget.log");
	
	
	if(!is_file("/usr/bin/vmware-toolbox-cmd")){
		build_progress("{INSTALL_VMWARE_TOOLS} {failed_to_install}",110);
		return;
	}
	
	build_progress("{INSTALL_VMWARE_TOOLS} {success}",60);
	build_progress("{removing_caches}",70);
	system("/etc/init.d/artica-status restart --force");
	build_progress("{removing_caches}",80);
	system("$php /usr/share/artica-postfix/exec.status.php --process1");
	build_progress("{removing_caches}",90);
	$unix->REMOVE_INTERFACE_CACHE();
	monit();
	build_progress("{success}",100);
}
function vmtools_pid(){
	if(is_file("/var/run/vmware-guestd.pid")){return "/var/run/vmware-guestd.pid";}
	if(is_file("/var/run/vmtoolsd.pid")){return "/var/run/vmtoolsd.pid";}

}

function vmtools_init(){

	if(is_file("/etc/init.d/vmware-tools")){return "/etc/init.d/vmware-tools";}
	if(is_file("/etc/init.d/open-vm-tools")){return "/etc/init.d/open-vm-tools";}
}





function monit(){
    $unix=new unix();
	$vmtools_init=vmtools_init();
	$f[]="check process APP_VMTOOLS with pidfile ". vmtools_pid();
	$f[]="start program = \"$vmtools_init start\"";
	$f[]="stop program = \"$vmtools_init stop\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	@file_put_contents("/etc/monit/conf.d/APP_VMTOOLS.monitrc", @implode("\n", $f));
    $unix->reload_monit();
	
}



function installbycd(){
	@unlink($GLOBALS["LOGFILE"]);
	$unix=new unix();
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
//SP139
	build_progress(5, "Mounting media...");
	
	if(!is_media_mounted()){
		events("Mount the CD-ROM on /media/cdrom0...");
		exec("$mount /media/cdrom0 2>&1",$results);
		foreach ($results as $line){events("$line");}
	}
	
	if(!is_media_mounted()){
		build_progress(110, "Failed to Mount the CD-ROM");
		events("Failed to Mount the CD-ROM on /media/cdrom0...");
		return;
	}
	
	
	$SourceFile=LatestVmSourcePackage("/media/cdrom0");
	build_progress(10, "$SourceFile");
	
	
	if($SourceFile==null){
		build_progress(110, "Failed to find VMWare Tools Source");
		events("Failed to find VMWare Tools Source package File on /media/cdrom0");
		shell_exec("$umount -l /media/cdrom0 &");
		return;	
	}
	installbyPath($SourceFile);
	shell_exec("$umount -l /media/cdrom0 &");
	
	
}

function installbyPath($SourceFile){
	
	$unix=new unix();
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");	
	if(!is_file($SourceFile)){
		build_progress(110, "$SourceFile no such file");
		events("Failed $SourceFile no such file...");
		return;
	}
	
	build_progress(15, "Extracting ".basename($SourceFile));
	events("Extract ". basename($SourceFile)." Source package...");
	if(is_dir("/root/VMwareArticaInstall")){recursive_remove_directory("/root/VMwareArticaInstall");}
	@mkdir("/root/VMwareArticaInstall",0640,true);
	shell_exec("$tar -xhf $SourceFile -C /root/VMwareArticaInstall/");
	events("Extract ". basename($SourceFile)." Source package done");
	build_progress(20, "Extracting ".basename($SourceFile)." success");
	
	if(!is_file("/root/VMwareArticaInstall/vmware-tools-distrib/vmware-install.pl")){
		build_progress(110, "vmware-install.pl no such file");
		events("Failed /root/VMwareArticaInstall/vmware-tools-distrib/vmware-install.pl no such file");
		recursive_remove_directory("/root/VMwareArticaInstall");
		return;
	}
	
	build_progress(25, "Execute the setup...");
	events("Launch setup vmware-install.pl");
	if(!is_dir("/root/VMwareArticaInstall/vmware-tools-distrib")){
		events("Failed /root/VMwareArticaInstall/vmware-tools-distrib no such directory");
		build_progress(110, "vmware-tools-distrib no such directory");
		return;
	}
	
	chdir("/root/VMwareArticaInstall/vmware-tools-distrib");
	events("Installing VMWare Tools....");
	$results=array();
	exec("./vmware-install.pl --default 2>&1",$results);
	foreach ($results as $line){events("$line");}
	build_progress(50, "Removing package");
	recursive_remove_directory("/root/VMwareArticaInstall");
	
	if(file_exists("/etc/init.d/vmware-tools")){
		build_progress(55, "Starting VMWare Tools service");
		events("Starting VMWare Tools service");
		$results=array();
		exec("/etc/init.d/vmware-tools start",$results);
		foreach ($results as $line){events("$line");}
		
	}
	
	
	
	if(file_exists("/usr/bin/vmware-toolbox-cmd")){
		
	events("VMWare Tools installed");
		$results=array();
		exec("/usr/bin/vmware-toolbox-cmd -v 2>&1",$results);
		foreach ($results as $line){events("$line");}
		
	}

	if(is_dir("/root/VMwareArticaInstall")){recursive_remove_directory("/root/VMwareArticaInstall");}
	build_progress(80, "Indexing softwares database");
	events("Indexing softwares database");
	shell_exec("/usr/bin/php /usr/share/artica-postfix/exec.status.php --process1 --force --verbose ".time());
	
}

function LatestVmSourcePackage($path){
	echo "Scanning $path\n";
	foreach (glob("$path/*.gz") as $filename) {
		echo "Checks $filename\n";
		if(preg_match("#VMwareTools(.+?)\.tar\.gz#", $filename)){return $filename;}
	}
	
	
}


function is_media_mounted(){
	
		$mount=new mount();
		return $mount->ismounted("/media/cdrom0");
}




function build_progress($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/vmware.install.progress";
	if(is_numeric($text)){
		$array["POURC"]=$text;
		$array["TEXT"]=$pourc;
	}else{
		$array["POURC"]=$pourc;
		$array["TEXT"]=$text;
	}
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}







function events($text){
		$pid=@getmypid();
		$date=@date("H:i:s");
		
		echo "$date [$pid]:".basename(__FILE__).": $text\n";
		$size=@filesize($GLOBALS["LOGFILE"]);
		if($size>1000000){@unlink($GLOBALS["LOGFILE"]);}
		$f = @fopen($GLOBALS["LOGFILE"], 'a');
		@fwrite($f, "$date [$pid]:".basename(__FILE__).": $text\n");
		@fclose($f);
		@chmod($GLOBALS["LOGFILE"], 0777);	
		}

