<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["RELOAD"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;

include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');

$GLOBALS["SYSDIRS"]["/var/cache/apt/archives"]=true;
$GLOBALS["SYSDIRS"]["/var/cache/man"]=true;
$GLOBALS["SYSDIRS"]["/var/cache/apt"]=true;
$GLOBALS["SYSDIRS"]["/var/cache/debconf"]=true;
$GLOBALS["SYSDIRS"]["/var/backups"]=true;
$GLOBALS["SYSDIRS"]["/usr/etc"]=true;
$GLOBALS["SYSDIRS"]["/usr/images"]=true;
$GLOBALS["SYSDIRS"]["/usr/include"]=true;
$GLOBALS["SYSDIRS"]["/usr/local"]=true;
$GLOBALS["SYSDIRS"]["/usr/share/man"]=true;
$GLOBALS["SYSDIRS"]["/usr/share/locale"]=true;
$GLOBALS["SYSDIRS"]["/usr/share/fonts"]=true;
$GLOBALS["SYSDIRS"]["/usr/share/perl5"]=true;
$GLOBALS["SYSDIRS"]["/usr/share/terminfo"]=true;
$GLOBALS["SYSDIRS"]["/usr/src"]=true;
$GLOBALS["SYSDIRS"]["/usr/share/squid-langpack"]=true;

$GLOBALS["SYSDIRS"]["/var/lib/dpkg"]=true;
$GLOBALS["SYSDIRS"]["/var/lib/ufdbartica"]=true;
$GLOBALS["SYSDIRS"]["/var/lib/squidguard"]=true;
$GLOBALS["SYSDIRS"]["/var/lib/ftpunivtlse1fr"]=true;
$GLOBALS["SYSDIRS"]["/var/lib/mlocate"]=true;
$GLOBALS["SYSDIRS"]["/var/lib/GeoIP"]=true;
$GLOBALS["SYSDIRS"]["/var/lib/clamav"]=true;
$GLOBALS["SYSDIRS"]["/var/lib/apt"]=true;
if($argv[1]=="--totalsize"){GetTotalSize();}
if($argv[1]=="--move"){move();}



function move(){
	
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	
	$filecache="/etc/artica-postfix/settings/Daemons/SystemTotalSize";
	$sock=new sockets();
	$DirectoryFSPath=$sock->GET_INFO("DirectoryFSPath");
	if($DirectoryFSPath==null){return;}
	@mkdir($DirectoryFSPath,0755,true);

    $dir="";
	$f=explode("/",$DirectoryFSPath);
    foreach ($f as $subdir){
		if($subdir==null){continue;}
		$dir=$dir."/$subdir";
		@chmod($dir,0755);
	}
	
	$SystemTotalSize=@file_get_contents($filecache);
	if($SystemTotalSize==0){GetTotalSize(true);$SystemTotalSize=@file_get_contents($filecache);}
	if($SystemTotalSize==0){
		echo "Move FS: SystemTotalSize = 0!!! \n";
		return;
	}
	
	$SystemTotalSize=$SystemTotalSize/1024;
	$SystemTotalSize=$SystemTotalSize/1024;
	$SystemTotalSize=round($SystemTotalSize);
	$cp=$unix->find_program("cp");
	$mv=$unix->find_program("mv");
	$ln=$unix->find_program("ln");
	$rm=$unix->find_program("rm");
	$available=$unix->DIRECTORY_FREEM($DirectoryFSPath);
	$after=$available-$SystemTotalSize;
	echo "Move directory: $DirectoryFSPath available:{$available}M system: {$SystemTotalSize}M after {$after}M\n";
	if($after<5){
		echo "Move directory: $DirectoryFSPath no space left\n";
	}
	
	if(is_file("/opt/articatech/bin/articadb")){
		$php=$unix->LOCATE_PHP5_BIN();
		echo "move /opt/articatech -> $DirectoryFSPath/opt/articatech\n";
		shell_exec("$php /usr/share/artica-postfix/exec.catz-db.php --changemysqldir \"$DirectoryFSPath/opt/articatech\"");
		echo "move /opt/squidsql/data -> $DirectoryFSPath/opt/squidsql/data\n";
		shell_exec("$php /usr/share/artica-postfix/exec.squid-db.php --changemysqldir \"$DirectoryFSPath/opt/squidsql/data\"");
	}
	
	
	reset($GLOBALS["SYSDIRS"]);
	$Max=count($GLOBALS["SYSDIRS"]);
	$c=1;
    foreach ($GLOBALS["SYSDIRS"] as $directory=>$val){
		echo "[$c/$Max] **** $directory ***\n";
		$nextdirectory="$DirectoryFSPath/$directory";
		$nextdirectory=str_replace("//", "/", $nextdirectory);
		
		
		
		if(is_link($directory)){
			$linkPath=@readlink($directory);
			echo "[INF]: $directory is a link of \"$linkPath\"\n";
			if($linkPath==$nextdirectory){
				echo "[OK]: $directory already moved\n";
				continue;
			}
			$directory=$linkPath;
			
		}
		if(!is_dir($directory)){
			echo "[INF]: $directory no such directory\n";
			if(is_dir($nextdirectory)){
				echo "Link $nextdirectory -> $directory\n";
				$cmd="$ln -sf $nextdirectory $directory";
				echo "$cmd\n";
				shell_exec($cmd);
			}
			continue;
		}
		$nextdirectory_dest=dirname($nextdirectory);
		
		@mkdir($nextdirectory,0644,true);
		echo "Copy $directory/* -> $nextdirectory\n";
		$cmd="$cp -rf $directory/* $nextdirectory/";
		echo "$cmd\n";
		system($cmd);
		echo "Remove $directory\n";
		recursive_remove_directory($directory);
		echo "Link $nextdirectory -> $directory\n";
		$cmd="$ln -sf $nextdirectory $directory";
		echo "$cmd\n";
		shell_exec($cmd);
		if(!is_link($directory)){
			echo "Link $nextdirectory -> $directory Failed, return back\n";
			system("$cp -rf  $nextdirectory/* $directory/");
			continue;
		}
		
		$DirExploded=explode("/",$nextdirectory);
        foreach ($DirExploded as $subdir){if($subdir==null){continue;}$dir=$dir."/$subdir";@chmod($dir,0755);}
		
		
	}


	
}




function GetTotalSize($aspid=false){
	
	$filecache="/etc/artica-postfix/settings/Daemons/SystemTotalSize";
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	
	
	$time=$unix->file_time_min($filecache);
	if(!$GLOBALS["FORCE"]){
		if($time<1440){return;}
	}
	
	$sync=$unix->find_program("sync");
	shell_exec($sync);
	$fullsize=0;
	reset($GLOBALS["SYSDIRS"]);
	$GLOBALS["SYSDIRS"]["/opt/articatech"]=true;
	$GLOBALS["SYSDIRS"]["/opt/squidsql/data"]=true;

    foreach ($GLOBALS["SYSDIRS"] as $directory=>$val){
		if(is_link($directory)){$directory=readlink($directory);}
		if(!is_dir($directory)){continue;}
		$size=$unix->DIRSIZE_BYTES($directory);
		$fullsize=$fullsize+$size;
	}
	@unlink($filecache);
	@file_put_contents($filecache, $fullsize);
	
	
}




  
