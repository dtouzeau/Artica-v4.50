<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["VERBOSE_MASTER"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;
$GLOBALS["VERBOSE_MASTER"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once("ressources/class.sockets.inc");
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	
	
	
	
	build();
	
function build(){
	$sock=new sockets();
	$unix=new unix();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$HugePages=$sock->GET_INFO("HugePages");
	$KernelShmmax=$sock->GET_INFO("KernelShmmax");
	$meminfo=MemInfo();
	$HUGEPAGESIZE=intval($meminfo["HUGEPAGESIZE"]);
	$HUGEPAGESIZEBytes=$HUGEPAGESIZE;
	
	if(!is_numeric($HugePages)){$HugePages=0;}
	if(!is_numeric($KernelShmmax)){$KernelShmmax=0;}
	if($HugePages>0){
		$HugePagesB=$HugePages*1024;
		$HugePagesB=$HugePagesB*1024;
		
		
		$HugePagesF=$HugePagesB/$HUGEPAGESIZEBytes;

		
		
		echo "HUGEPAGESIZE = $HUGEPAGESIZE ($HUGEPAGESIZEBytes bytes) ". FormatBytes($HUGEPAGESIZEBytes/1024)."\n";
		echo "HugePages = $HugePages Mb ($HugePagesB bytes)\n";
		echo "HugePages Final = $HugePagesF ". FormatBytes($HugePagesF/1024)." \n";
		$unix->sysctl("vm.nr_hugepages", $HugePagesF);
		shell_exec("$sysctl -w vm.nr_hugepages=$HugePagesF");
		for($i=0;$i<10;$i++){
			shell_exec("$echo $HugePagesF > /proc/sys/vm/nr_hugepages");
			sleep(1);
		}
		
		
		
	}
	

	// sysctl
	
}	
	
	
	
function MemInfo(){
	
	
	$f=file("/proc/meminfo");
	foreach ($f as $num=>$ligne){
		if(!preg_match("#(.*?):\s+([0-9]+)\s+#", $ligne,$re)){continue;}
		$TotalKbytes=$re[2];
		$TotalBytes=$TotalKbytes*1024;
		$key=strtoupper($re[1]);
		$array[$key]=$TotalBytes;
	}	
	return $array;
} 