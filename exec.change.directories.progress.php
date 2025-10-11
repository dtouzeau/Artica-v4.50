<?php
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.auth.tail.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.tail.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

Start();

function build_progress($text,$pourc){

	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/change.directories.progress";


	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}


function start(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		events("Already PID $pid exists, aborting...");
		build_progress("Already PID $pid exists, aborting",110);
		return;
	}
	$configfile="/usr/share/artica-postfix/ressources/conf/upload/ChangeDirs";
	build_progress("{checking}",5);
	
	$f["/var/log/squid"]=true;
	$f["/home/artica/categories_databases"]=true;
	$f["/home/logs-backup"]=true;;
	
	if(!is_file($configfile)){
		echo "$configfile no such file\n";
		build_progress("No configuration set...",110);
	}
	
	$data=unserialize(@file_get_contents($configfile));
	if(!is_array($data)){
		echo "$configfile no such array\n";
		build_progress("No configuration set...",110);
	}
	
	
	
	$GLOBALS["RESTART_SQUID"]=false;
	
	sleep(2);
	build_progress("Checking /var/log/squid",10);
	if(!ChangePath("/var/log/squid",trim($data["/var/log/squid"]))){
		build_progress("Checking /var/log/squid {failed}",110);
		return;
	}

	sleep(2);
	build_progress("Checking /home/artica/categories_databases",15);
	if(!ChangePath("/home/artica/categories_databases",trim($data["/home/artica/categories_databases"]))){
		build_progress("Checking /home/artica/categories_databases {failed}",110);
		return;
	}	
	
	sleep(2);
	$tdir="/home/logs-backup";
	build_progress("Checking $tdir",15);
	if(!ChangePath($tdir,trim($data[$tdir]))){
		build_progress("Checking $tdir {failed}",110);
		return;
	}	
	sleep(2);
	$tdir="/home/c-icap/blacklists";
	build_progress("Checking $tdir",20);
	if(!ChangePath($tdir,trim($data[$tdir]))){
		build_progress("Checking $tdir {failed}",110);
		return;
	}

	sleep(2);
	$tdir="/var/log/artica-postfix";
	build_progress("Checking $tdir",25);
	if(!ChangePath($tdir,trim($data[$tdir]))){
		build_progress("Checking $tdir {failed}",110);
		return;
	}	
	
	if($GLOBALS["RESTART_SQUID"]){
		build_progress("Restart Squid-cache",90);
		shell_exec("/etc/init.d/squid restart");
	}
	
	build_progress("{success}",100);
	
}


function ChangePath($orgpath,$newpath){
	if($orgpath==null){return true;}
	if($newpath==null){return true;}
	$unix=new unix();
	
	$cp=$unix->find_program("cp");
	$rm=$unix->find_program("rm");
	$sync=$unix->find_program("sync");
	if($orgpath==$newpath){return;}
	

	if(is_link($newpath)){
			$newnewpath=@readlink($newpath);
			echo "$orgpath -> $newpath ( is symbolic link to $newnewpath, - change $orgpath to $newnewpath )\n";
			MakeSymbolic($orgpath,$newnewpath);
			return true;
	}
	if(!is_dir($newpath)){
		echo "$orgpath -> $newpath Creating directory $newpath\n";
		@mkdir($newpath);
	}
	
	
	
	// ------------------------------------------------------------------
	if(is_link($orgpath)){
		$checkpath=@readlink($orgpath);
		if($checkpath==$newpath){
			echo "$orgpath -> $checkpath == $newpath ( skip )\n";
			return true;
		}
		
		@mkdir("$newpath",0755,true);
		
		
		echo "$orgpath -> $checkpath == $newpath ( change )\n";
		echo "$checkpath copy to $newpath ( cp )\n";
		
		if(!CopyToFrom($checkpath,$newpath)){return false;}
		
		echo "Remove directory $checkpath ( rm )\n";
		$cmd="$rm -rf $checkpath";
		echo "[".__LINE__."] * * * $cmd * * *\n";
		shell_exec($cmd);
		shell_exec($cmd);
		
		$cmd=$sync;
		echo "[".__LINE__."] * * * $cmd * * *\n";
		system($cmd);
		
		echo "Remove link from $orgpath\n";
		@unlink($orgpath);
		if(!MakeSymbolic($orgpath,$newpath)){return false;}
		if($orgpath=="/var/log/squid"){$GLOBALS["RESTART_SQUID"]=true;}
		return true;
		
	}
	// ------------------------------------------------------------------

	if(!is_dir($orgpath)){
		echo "$orgpath No such directory..(aborting)\n";
		return true;
	}
	
	
	@mkdir($newpath,0755,true);
	echo "$orgpath copy to $newpath ( cp )\n";
	if(!CopyToFrom($orgpath,$newpath)){return false;}
	
	echo "Remove directory $orgpath\n";
	$cmd="$rm -rf $orgpath";
	echo "[".__LINE__."] * * * $cmd * * *\n";
	system($cmd);
	system($cmd);
	
	$cmd=$sync;
	echo "[".__LINE__."] * * * $cmd * * *\n";
	system($cmd);
	
	if(!MakeSymbolic($orgpath,$newpath)){return false;}
	if($orgpath=="/var/log/squid"){$GLOBALS["RESTART_SQUID"]=true;}
	
	return true;
}

function MakeSymbolic($orgdir,$destdir){
	echo "Make symbolic link from $destdir to $orgdir\n";
	if(is_link($orgdir)){@unlink($orgdir);}
	
	
	$orgdir=trim($orgdir);
	$destdir=trim($destdir);
	
	if($orgdir==null){echo "MakeSymbolic: Orginal dir is null\n";return false;}
	if($destdir==null){echo "MakeSymbolic: Destdir dir is null\n";return false;}
	$unix=new unix();
	$ln=$unix->find_program("ln");
	exec("$ln -sf $destdir $orgdir 2>&1",$results);
	foreach ($results as $a=>$b){
		echo "MakeSymbolic: \"$b\"\n";
	}
	
	$checkpath=@readlink($orgdir);
	if($checkpath<>$destdir){
		echo "Make symbolic link from $orgdir -> $checkpath <> $destdir\n";
		return false;
	}
	echo "Make symbolic link Success $orgdir is now $checkpath\n";
	return true;
	
}

function CopyToFrom($orgdir,$destdir){
	$orgdir=trim($orgdir);
	$destdir=trim($destdir);
	
	
	if($orgdir==null){
		echo "CopyToFrom: Orginal dir is null\n";
		return false;}
	if($destdir==null){echo "CopyToFrom: Destdir dir is null\n";
		return false;}
	$unix=new unix();
	
	$cp=$unix->find_program("cp");
	$orgdir=$unix->shellEscapeChars($orgdir);
	$destdir=$unix->shellEscapeChars($destdir);
	exec("$cp -rfp $orgdir/* $destdir/ 2>&1",$results);
	foreach ($results as $a=>$b){ if(preg_match("#(No space|failed to|error|missing)#i", $b)){ echo $b; return false; } }
	return true;

	
}



