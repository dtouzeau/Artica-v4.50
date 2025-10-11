<?php
/*
apt-get install libtalloc2
mkdir /etc/samba
mkdir /var/log/samba/
mkdir /var/run/samba
touch /etc/printcap
*/
//http://www.samba.org/samba/ftp/stable/samba-3.6.6.tar.gz

include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

$unix=new unix();

$GLOBALS["SHOW_COMPILE_ONLY"]=false;
$GLOBALS["NO_COMPILE"]=false;
$GLOBALS["REPOS"]=false;
if($argv[1]=='--compile'){$GLOBALS["SHOW_COMPILE_ONLY"]=true;}
if(preg_match("#--no-compile#", @implode(" ", $argv))){$GLOBALS["NO_COMPILE"]=true;}
if(preg_match("#--verbose#", @implode(" ", $argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--repos#", @implode(" ", $argv))){$GLOBALS["REPOS"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}


if($argv[1]=="--factorize"){factorize($argv[2]);exit;}
if($argv[1]=="--serialize"){serialize_tests();exit;}
if($argv[1]=="--latests"){latests();exit;}
if($argv[1]=="--latest"){echo "Latest:". latests()."\n";exit;}
if($argv[1]=="--create-package"){create_package();exit;}
if($argv[1]=="--parse-install"){parse_install($argv[2]);exit;}



$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");

//http://ftp.samba.org/pub/samba/stable/


$dirsrc="gluster-0.0.0";


if(!$GLOBALS["NO_COMPILE"]){
	$v=latests();
	if(preg_match("#gluster-(.+?)#", $v,$re)){$dirsrc=$re[1];}
	squid_admin_mysql(1, "Downloading lastest file $v, working directory $dirsrc ...",__FUNCTION__,__FILE__,__LINE__);
}

if(!$GLOBALS["FORCE"]){
	if(is_file("/root/$v")){if($GLOBALS["REPOS"]){echo "No updates...\n";die("DIE " .__FILE__." Line: ".__LINE__);}}
}

if(is_dir("/root/gluster-builder")){shell_exec("$rm -rf /root/gluster-builder");}




	create_package();
	@mkdir("/root/samba-builder/etc/init.d",0755,true);
	if(is_file("$SOURCE_DIRECTORY2/packaging/LSB/samba.sh")){
		shell_exec("/bin/cp $SOURCE_DIRECTORY2/packaging/LSB/samba.sh /root/samba-builder/etc/init.d/samba");
		@chmod("/root/samba-builder/etc/init.d/samba", 0755);
	}else{
		echo "$SOURCE_DIRECTORY2/packaging/LSB/samba.sh no such file";
	}



	$version=SAMBA_VERSION();
	
	
	
	
	if(is_file("/root/ftp-password")){
		echo "Uploading sambac-$Architecture-$version.tar.gz Done...\n";
		echo "/root/samba-builder/sambac-$Architecture-$version.tar.gz is now ready to be uploaded\n";
		shell_exec("curl -T /root/samba-builder/sambac-$Architecture-$version.tar.gz ftp://www.articatech.net/download/ --user ".@file_get_contents("/root/ftp-password"));
		if(is_file("/root/rebuild-artica")){shell_exec("$wget \"".@file_get_contents("/root/rebuild-artica")."\" -O /tmp/rebuild.html");}
		
	}	

function GLUSTER_VERSION(){
	$unix=new unix();
	$glusterfsd=$unix->find_program("glusterfsd");
	exec("$glusterfsd -V 2>&1",$results);
	if(preg_match("#glusterfs\s+([0-9\.]+)\s+built#i", @implode("", $results),$re)){return $re[1];}
	}




function create_package(){
	$Architecture=Architecture();
	if($Architecture==64){$Architecture="x64";}
	if($Architecture==32){$Architecture="i386";}
	$WORKDIR="/root/gluster-builder";
	$version=GLUSTER_VERSION();
	@mkdir("$WORKDIR/sbin",0755,true);
	@mkdir("$WORKDIR/usr/sbin",0755,true);
	@mkdir("$WORKDIR/usr/lib/glusterfs",0755,true);
	@mkdir("$WORKDIR/etc/glusterfs",0755,true);
	$f[]="/etc/glusterfs/glusterd.vol";
	$f[]="/sbin/mount.glusterfs";
	$f[]="/usr/sbin/gluster";
	$f[]="/usr/sbin/glusterfs";
	$f[]="/usr/sbin/glusterd";
	$f[]="/usr/sbin/glusterfsd";
	$f[]="/etc/glusterfs/glusterd.vol";
	$f[]="/usr/lib/libglusterfs.la";
	$f[]="/usr/lib/libglusterfs.so";
	$f[]="/usr/lib/libglusterfs.so.0";
	$f[]="/usr/lib/libglusterfs.so.0.0.0";
	$f[]="/usr/lib/libgfxdr.a";
	$f[]="/usr/lib/libgfxdr.la";
	$f[]="/usr/lib/libgfxdr.so";
	$f[]="/usr/lib/libgfxdr.so.0";
	$f[]="/usr/lib/libgfxdr.so.0.0.0";
	$f[]="/usr/lib/libgfrpc.la";
	$f[]="/usr/lib/libgfrpc.a";
	$f[]="/usr/lib/libgfrpc.so";
	$f[]="/usr/lib/libgfrpc.so.0";
	$f[]="/usr/lib/libgfrpc.so.0.0.0";
	$f[]="/usr/lib/libglusterfs.la";
	$f[]="/usr/lib/libglusterfs.so";
	$f[]="/usr/lib/libglusterfs.so.0 ";
	$f[]="/usr/lib/libglusterfs.so.0.0.0";
	

	foreach ($f as $num=>$ligne){
		if(!is_file($ligne)){echo "$ligne no such file\n";continue;}
		$dir=dirname($ligne);
		echo "Installing $ligne in $WORKDIR$dir/\n";
		if(!is_dir("$WORKDIR$dir")){@mkdir("$WORKDIR$dir",0755,true);}
		shell_exec("/bin/cp -fd $ligne $WORKDIR$dir/");
		
	}
	
	shell_exec("/bin/cp -rfd /usr/lib/glusterfs/* $WORKDIR/usr/lib/glusterfs/");
	echo "Creating package done....\n";
	echo "Building package Arch:$Architecture Version:$version\n";
	echo "Going to $WORKDIR\n";
	@chdir("$WORKDIR");
	echo "Compressing glusterfsc-$Architecture-$version.tar.gz\n";
	if(is_file("/root/glusterfsc-$Architecture-$version.tar.gz")){@unlink("/root/glusterfsc-$Architecture-$version.tar.gz");}
	shell_exec("tar -czf /root/glusterfsc-$Architecture-$version.tar.gz *");
	echo "Compressing /root/glusterfsc-$Architecture-$version.tar.gz Done...\n";	
}


	

function parse_install($filename){
	if(!is_file($filename)){echo "$filename no such file\n";return;}
	$f=file($filename);
	
	foreach ($f as $num=>$ligne){
		if(preg_match("#Installing (.+?)\s+as\s+(.+)#", $ligne,$re)){
			$re[1]=str_replace("///", "/", $re[2]);
			$target[]=$re[1];
			continue;
		}
		
		if(preg_match("#install\s+-c\s+.+?\/(.+?)\s+(.+)#", $ligne,$re)){
			$filename=$re[2]."/".basename($re[1]);
			$filename=str_replace("///", "/", $filename);
			$filename=str_replace("//", "/", $filename);
			$target[]=trim($filename);
		}
		
	}
	
	while (list ($num, $ligne) = each ($target) ){
		$dir=dirname($ligne);
		$mkdirs[trim($dir)]=true;
		$files[trim($ligne)]=true;
		
	}
	while (list ($num, $ligne) = each ($mkdirs) ){
		$tt="/root/samba-builder/$num";
		$tt=str_replace("//", "/", $tt);
		echo "@mkdir('$tt',0755,true);\n";
	}

	foreach ($files as $num=>$ligne){
			echo "\$f[]=\"$num\";\n";
	}	
	
}

function Architecture(){
	$unix=new unix();
	$uname=$unix->find_program("uname");
	exec("$uname -m 2>&1",$results);
	foreach ($results as $num=>$val){
		if(preg_match("#i[0-9]86#", $val)){return 32;}
		if(preg_match("#x86_64#", $val)){return 64;}
	}
}


function latests(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	shell_exec("$wget http://ftp.samba.org/pub/samba/stable/ -O /tmp/index.html");
	$f=explode("\n",@file_get_contents("/tmp/index.html"));
	foreach ($f as $index=>$line){
		if(preg_match("#<a href=\"samba-(.+?)\.tar\.gz#", $line,$re)){
			$ve=$re[1];
			
			if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)#", $ve,$ri)){
				if(strlen($ri[2])==1){$ri[2]="{$ri[2]}0";}
				if(strlen($ri[3])==1){$ri[3]="{$ri[3]}0";}
				$ve="{$ri[1]}.{$ri[2]}.{$ri[3]}";
				
			}
			
			
			$ve=str_replace(".", "", $ve);
			$ve=str_replace("-", "", $ve);
			
			
			$file="samba-{$re[1]}.tar.gz";
			$versions[$ve]=$file;
		if($GLOBALS["VERBOSE"]){echo "$ve -> $file ({$ri[1]}.{$ri[2]}.{$ri[3]})\n";}
		}
	}
	
	krsort($versions);
	while (list ($num, $filename) = each ($versions)){
		$vv[]=$filename;
	}
	
	echo "Found latest file version: `{$vv[0]}`\n";
	return $vv[0];
}





function factorize($path){
	$f=explode("\n",@file_get_contents($path));
    foreach ($f as $val){
		$newarray[$val]=$val;
		
	}
	while (list ($num, $val) = each ($newarray)){
		echo "$val\n";
	}
	
}










