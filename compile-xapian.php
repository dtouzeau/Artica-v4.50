<?php

/*
 * 
 * wget http://oligarchy.co.uk/xapian/1.4.4/xapian-core-1.4.4.tar.xz
 * ./configure --prefix=/usr --bindir="\${prefix}/bin" --sbindir="\${prefix}/sbin" --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib"
 * 
 * Binding
 * ./configure --with-php7 --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib"
 * 
 * 
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
$GLOBALS["ROOT-DIR"]="/root/xapian-builder";
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


function _VERSION(){
	$unix=new unix();
	$binary=$unix->find_program("xapian-config");
	exec("$binary --version 2>&1",$results);
	if(preg_match("#xapian-core\s+([0-9\.]+)#i", @implode("", $results),$re)){return $re[1];}
}



function create_package(){
	if($GLOBALS["ROOT-DIR"]==null){echo "Root dir is null!\n";die("DIE " .__FILE__." Line: ".__LINE__);}
	$Architecture=Architecture();
	if($Architecture==64){$Architecture="64";}
	if($Architecture==32){$Architecture="32";}
	$WORKDIR=$GLOBALS["ROOT-DIR"];
	$version=_VERSION();
	$unix=new unix();
	$strip=$unix->find_program("strip");
	$cp=$unix->find_program("cp");
	$mkdir=$unix->find_program("mkdir");
	if(is_dir("{$GLOBALS["ROOT-DIR"]}")){system("rm -rf {$GLOBALS["ROOT-DIR"]}");}
	
	
	$f[]="$mkdir -p {$GLOBALS["ROOT-DIR"]}";
	$f[]="$strip -s /usr/bin/xapian-delve";
	$f[]="$strip -s /usr/bin/quest";
	$f[]="$strip -s /usr/bin/simpleexpand";
	$f[]="$strip -s /usr/bin/xapian-tcpsrv";
	$f[]="$strip -s /usr/bin/simpleindex";
	$f[]="$strip -s /usr/bin/copydatabase";
	$f[]="$strip -s /usr/bin/xapian-config";
	$f[]="$strip -s /usr/bin/xapian-progsrv";
	$f[]="$strip -s /usr/bin/xapian-replicate";
	$f[]="$strip -s /usr/bin/xapian-compact";
	$f[]="$strip -s /usr/bin/xapian-metadata";
	$f[]="$strip -s /usr/bin/simplesearch";
	$f[]="$strip -s /usr/bin/xapian-check";
	$f[]="$strip -s /usr/bin/xapian-replicate-server";
	
	$f[]="$strip -s /usr/bin/omindex";  
	$f[]="$strip -s /usr/bin/omindex-list";  
	$f[]="$strip -s /usr/bin/omshell";
	$f[]="$strip -s /usr/lib/xapian-omega/bin/mhtml2html";
	$f[]="$strip -s /usr/lib/xapian-omega/bin/omega";
	$f[]="$strip -s /usr/lib/xapian-omega/bin/outlookmsg2html";
	$f[]="$strip -s /usr/lib/xapian-omega/bin/rfc822tohtml";
	$f[]="$strip -s /usr/lib/xapian-omega/bin/vcard2text";
	
	$f[]="$mkdir -p {$GLOBALS["ROOT-DIR"]}/usr/bin";
	$f[]="$mkdir -p {$GLOBALS["ROOT-DIR"]}/usr/include";
	$f[]="$mkdir -p {$GLOBALS["ROOT-DIR"]}/usr/include/xapian";
	$f[]="$mkdir -p {$GLOBALS["ROOT-DIR"]}/usr/share/aclocal";
	$f[]="$mkdir -p {$GLOBALS["ROOT-DIR"]}/usr/share/xapian-core/stopwords";
	$f[]="$mkdir -p {$GLOBALS["ROOT-DIR"]}/usr/lib/cmake/xapian";
	$f[]="$mkdir -p {$GLOBALS["ROOT-DIR"]}/usr/lib/pkgconfig";
	$f[]="$mkdir -p {$GLOBALS["ROOT-DIR"]}/usr/lib/xapian-omega/bin";
	$f[]="$cp -fvd /usr/bin/omindex {$GLOBALS["ROOT-DIR"]}/usr/bin/";
	$f[]="$cp -fvd /usr/bin/omindex-list {$GLOBALS["ROOT-DIR"]}/usr/bin/";
	$f[]="$cp -fvd /usr/bin/omshell {$GLOBALS["ROOT-DIR"]}/usr/bin/";
	$f[]="$cp -fvd /usr/bin/xapian-delve {$GLOBALS["ROOT-DIR"]}/usr/bin/xapian-delve";
	$f[]="$cp -fvd /usr/bin/quest {$GLOBALS["ROOT-DIR"]}/usr/bin/quest";
	$f[]="$cp -fvd /usr/bin/simpleexpand {$GLOBALS["ROOT-DIR"]}/usr/bin/simpleexpand";
	$f[]="$cp -fvd /usr/bin/xapian-tcpsrv {$GLOBALS["ROOT-DIR"]}/usr/bin/xapian-tcpsrv";
	$f[]="$cp -fvd /usr/bin/simpleindex {$GLOBALS["ROOT-DIR"]}/usr/bin/simpleindex";
	$f[]="$cp -fvd /usr/bin/copydatabase {$GLOBALS["ROOT-DIR"]}/usr/bin/copydatabase";
	$f[]="$cp -fvd /usr/bin/xapian-config {$GLOBALS["ROOT-DIR"]}/usr/bin/xapian-config";
	$f[]="$cp -fvd /usr/bin/xapian-progsrv {$GLOBALS["ROOT-DIR"]}/usr/bin/xapian-progsrv";
	$f[]="$cp -fvd /usr/bin/xapian-replicate {$GLOBALS["ROOT-DIR"]}/usr/bin/xapian-replicate";
	$f[]="$cp -fvd /usr/bin/xapian-compact {$GLOBALS["ROOT-DIR"]}/usr/bin/xapian-compact";
	$f[]="$cp -fvd /usr/bin/xapian-metadata {$GLOBALS["ROOT-DIR"]}/usr/bin/xapian-metadata";
	$f[]="$cp -fvd /usr/bin/simplesearch {$GLOBALS["ROOT-DIR"]}/usr/bin/simplesearch";
	$f[]="$cp -fvd /usr/bin/xapian-check {$GLOBALS["ROOT-DIR"]}/usr/bin/xapian-check";
	$f[]="$cp -fvd /usr/bin/xapian-replicate-server {$GLOBALS["ROOT-DIR"]}/usr/bin/xapian-replicate-server";
	$f[]="$cp -rfvd /usr/lib/xapian-omega/* {$GLOBALS["ROOT-DIR"]}/usr/lib/xapian-omega/";
	$f[]="$cp -rfvd /usr/include/* {$GLOBALS["ROOT-DIR"]}/usr/include/";
	$f[]="$cp -rfvd /usr/share/xapian-core/* {$GLOBALS["ROOT-DIR"]}/usr/share/xapian-core/";
	$f[]="$cp -fvd /usr/share/aclocal/xapian.m4 {$GLOBALS["ROOT-DIR"]}/usr/share/aclocal/xapian.m4";
	$f[]="$cp -fvd /usr/lib/libxapian.so.30 {$GLOBALS["ROOT-DIR"]}/usr/lib/libxapian.so.30";
	$f[]="$cp -fvd /usr/lib/cmake/xapian/xapian-config.cmake {$GLOBALS["ROOT-DIR"]}/usr/lib/cmake/xapian/xapian-config.cmake";
	$f[]="$cp -fvd /usr/lib/cmake/xapian/xapian-config-version.cmake {$GLOBALS["ROOT-DIR"]}/usr/lib/cmake/xapian/xapian-config-version.cmake";
	$f[]="$cp -fvd /usr/lib/pkgconfig/xapian-core.pc {$GLOBALS["ROOT-DIR"]}/usr/lib/pkgconfig/xapian-core.pc";
	$f[]="$cp -fvd /usr/lib/libxapian.so {$GLOBALS["ROOT-DIR"]}/usr/lib/libxapian.so";
	$f[]="$cp -fvd /usr/lib/libxapian.la {$GLOBALS["ROOT-DIR"]}/usr/lib/libxapian.la";
	$f[]="$cp -fvd /usr/lib/libxapian.so.30.3.1 {$GLOBALS["ROOT-DIR"]}/usr/lib/libxapian.so.30.3.1";


	foreach ($f as $line){
		echo "$line\n";
		system($line);
		
	}

	echo "Creating package done....\n";
	echo "Building package Arch:$Architecture Version:$version\n";
	echo "Going to $WORKDIR\n";
	@chdir("$WORKDIR");
	$targtefile="xapian-$Architecture-$version.tar.gz";
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










