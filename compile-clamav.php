<?php

/*
 * git clone https://github.com/vrtadmin/clamav-devel
 * ./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-silent-rules --libexecdir=\${prefix}/lib/clamav --disable-maintainer-mode --disable-dependency-tracking --with-dbdir=/var/lib/clamav --sysconfdir=/etc/clamav --disable-milter --enable-dns-fix --with-gnu-ld
 * 
 */
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.compile_squid.inc');

$unix=new unix();

$GLOBALS["SHOW_COMPILE_ONLY"]=false;
$GLOBALS["NO_COMPILE"]=false;
$GLOBALS["REPOS"]=false;
$GLOBALS["ROOT-DIR"]="/root/securities-plugins";
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
if($argv[1]=="--clamav"){clamav_only($argv[2]);exit;}
if($argv[1]=="--package"){create_package();exit;}



$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");





create_package();


function CLAMAV_VERSION(){
	$unix=new unix();
	$proftpd=$unix->find_program("clamav-config");
	exec("$proftpd --version 2>&1",$results);
	if(preg_match("#([0-9\.]+)#i", @implode("", $results),$re)){return $re[1];}
}

function clamav_only(){
	$Architecture=Architecture();
	if($Architecture==64){$Architecture="64";}
	if($Architecture==32){$Architecture="32";}
	$WORKDIR=$GLOBALS["ROOT-DIR"];
	if(is_dir($WORKDIR)){shell_exec("rm -rf {$WORKDIR}");}
	@mkdir($WORKDIR,0755,true);
	@mkdir("$WORKDIR/sbin",0755,true);
	@mkdir("$WORKDIR/usr/sbin",0755,true);
	@mkdir("$WORKDIR/usr/bin",0755,true);
	@mkdir("$WORKDIR/usr/lib",0755,true);
	@mkdir("$WORKDIR/usr/include",0755,true);
	
	$version=CLAMAV_VERSION();
	
	$f=clamav_files();
	
	$f[]="/usr/lib/libclamunrar.so.7.1.1";
	$f[]="/usr/lib/libclamunrar_iface.so.7.1.1";
	$f[]="/usr/lib/libclamav.so.7.1.1";
	$f[]="/usr/lib/libclamunrar.so.7";
	$f[]="/usr/lib/libclamunrar_iface.so.7";
	$f[]="/usr/lib/libclamav.so.7";
	
	$f[]="/usr/lib/libclamunrar.so";
	$f[]="/usr/lib/libclamunrar_iface.so";
	$f[]="/usr/lib/libclamav.so";
	
	$f[]="/usr/lib/libclamunrar.la";
	$f[]="/usr/lib/libclamav.la";
	$f[]="/usr/lib/libclamunrar_iface.la";

	$f[]="/usr/include/clamav.h";

	$f[]="/usr/sbin/clamd";
	$f[]="/usr/sbin/clamav-milter";
	
	$f[]="/usr/bin/clamdscan";
	$f[]="/usr/bin/freshclam";
	$f[]="/usr/bin/sigtool";
	$f[]="/usr/bin/clamconf";
	$f[]="/usr/bin/clamscan";
	$f[]="/usr/bin/clambc";
	$f[]="/usr/bin/clamsubmit";
	$f[]="/usr/bin/clamav-config";
	
	foreach ($f as $num=>$ligne){
		if(is_dir($ligne)){
			echo "$WORKDIR$ligne Creating directory\n";continue;
			@mkdir("$WORKDIR$ligne",0755,true);
			continue;
		}
		if(!is_file($ligne)){echo "$ligne no such file\n";continue;}
		$dir=dirname($ligne);
		echo "Installing $ligne in $WORKDIR$dir/\n";
		if(!is_dir("$WORKDIR$dir")){@mkdir("$WORKDIR$dir",0755,true);}
		shell_exec("/bin/cp -fd $ligne $WORKDIR$dir/");
	
	}
	
	
	echo "Creating package done....\n";
	echo "Building package Arch:$Architecture Version:$version\n";
		echo "Going to $WORKDIR\n";
		@chdir("$WORKDIR");
		$targtefile="clamav-debian7-$Architecture-$version.tar.gz";
		echo "Compressing $targtefile\n";
		if(is_file("/root/$targtefile")){@unlink("/root/$targtefile");}
		shell_exec("tar -czf /root/$targtefile *");
		echo "Compressing /root/$targtefile Done...\n";
	
}


function create_package(){
	$Architecture=Architecture();
	if($Architecture==64){$Architecture="64";}
	if($Architecture==32){$Architecture="32";}
	$WORKDIR=$GLOBALS["ROOT-DIR"];
	$version=CLAMAV_VERSION();
	$unix=new unix();
	$strip=$unix->find_program("strip");
	$cp=$unix->find_program("cp");
	
	if(is_dir($WORKDIR)){system("rm -rf $WORKDIR");}
	@mkdir("$WORKDIR/sbin",0755,true);
	@mkdir("$WORKDIR/usr/sbin",0755,true);
	@mkdir("$WORKDIR/usr/bin",0755,true);
	@mkdir("$WORKDIR/usr/lib",0755,true);


	
	$BINARIES[]="/usr/bin/clamscan";
	$BINARIES[]="/usr/sbin/clamd";
	$BINARIES[]="/usr/bin/clamdscan";
	$BINARIES[]="/usr/bin/freshclam";
	$BINARIES[]="/usr/bin/sigtool";
	$BINARIES[]="/usr/bin/clamconf";
	$BINARIES[]="/usr/bin/clambc";
	$BINARIES[]="/usr/bin/clamav-config";
	$BINARIES[]="/usr/bin/clamdtop";
	$BINARIES[]="/usr/bin/freshclam";
	
	
	
	foreach ($BINARIES as $zbinary){
		system("$strip -s $zbinary");
		echo "echo $zbinary -> $WORKDIR$zbinary\n";
		system("$cp -fd $zbinary $WORKDIR$zbinary");
		
	}
	
	$LIBRARIES[]="/usr/lib/libclamav.la";
	$LIBRARIES[]="/usr/lib/libclamav.so.7";
	$LIBRARIES[]="/usr/lib/libclamunrar_iface.la";
	$LIBRARIES[]="/usr/lib/libclamunrar_iface.so.7";
	$LIBRARIES[]="/usr/lib/libclamunrar.la";
	$LIBRARIES[]="/usr/lib/libclamunrar.so.7";
	$LIBRARIES[]="/usr/lib/libclamav.so";
	$LIBRARIES[]="/usr/lib/libclamav.so.7.1.1";
	$LIBRARIES[]="/usr/lib/libclamunrar_iface.so";
	$LIBRARIES[]="/usr/lib/libclamunrar_iface.so.7.1.1";
	$LIBRARIES[]="/usr/lib/libclamunrar.so";
	$LIBRARIES[]="/usr/lib/libclamunrar.so.7.1.1";
	$LIBRARIES[]="/usr/lib/libmspack.a";
	$LIBRARIES[]="/usr/lib/libmspack.la";
	$LIBRARIES[]="/usr/lib/libmspack.so";
	$LIBRARIES[]="/usr/lib/libmspack.so.0";
	$LIBRARIES[]="/usr/lib/libmspack.so.0.1.0";
	
	foreach ($LIBRARIES as $zfile){
		echo "echo $zfile -> $WORKDIR".dirname($zfile)."/\n";
		system("$cp -fd $zfile $WORKDIR".dirname($zfile)."/");
	}
	

	echo "Creating package done....\n";
	echo "Building package Arch:$Architecture Version:$version\n";
	echo "Going to $WORKDIR\n";
	@chdir("$WORKDIR");
	$targtefile="clamav-$Architecture-$version.tar.gz";
	echo "Compressing $targtefile\n";
	if(is_file("/root/$targtefile")){@unlink("/root/$targtefile");}
	shell_exec("tar -czf /root/$targtefile *");
	echo "Compressing /root/$targtefile Done...\n";	
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








function factorize($path){
	$f=explode("\n",@file_get_contents($path));
    foreach ($f as $val){
		$newarray[$val]=$val;
		
	}
	while (list ($num, $val) = each ($newarray)){
		echo "$val\n";
	}
	
}










