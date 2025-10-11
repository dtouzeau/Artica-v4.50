<?php
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$GLOBALS["ROOT-DIR"]="/root/powerdns-compile";
$unix=new unix();

$GLOBALS["SHOW_COMPILE_ONLY"]=false;
$GLOBALS["NO_COMPILE"]=false;
$GLOBALS["REPOS"]=false;
if($argv[1]=='--compile'){$GLOBALS["SHOW_COMPILE_ONLY"]=true;}
if(preg_match("#--no-compile#", @implode(" ", $argv))){$GLOBALS["NO_COMPILE"]=true;}
if(preg_match("#--verbose#", @implode(" ", $argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--repos#", @implode(" ", $argv))){$GLOBALS["REPOS"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}


/* 
 * apt-get install bison ragel 	liblua5.3-dev libsodium-dev libsnmp-dev libpcap0.8-dev libmariadbclient-dev-compat libkrb5-dev libboost-dev libboost-serialization-dev libboost-program-options-dev libyaml-cpp-dev
 * https://downloads.powerdns.com/releases/pdns-4.1.3.tar.bz2
./configure --prefix=/usr --enable-verbose-logging --sysconfdir=/etc/powerdns --mandir=/usr/share/man --infodir=/usr/share/info --libdir=/usr/lib --libexecdir=/usr/lib --with-modules=gmysql --with-dynmodules=pipe  --without-sqlite3 --enable-tools


wget https://downloads.powerdns.com/releases/pdns-recursor-3.6.2.tar.bz2
https://downloads.powerdns.com/releases/pdns-recursor-4.1.8.tar.bz2
./configure --prefix=/usr --sysconfdir=/etc/powerdns --mandir=/usr/share/man --infodir=/usr/share/info --libdir=/usr/lib --libexecdir=/usr/lib --with-lua=lua5.3 
./configure --prefix=/usr --sysconfdir=/etc/powerdns --mandir=/usr/share/man --infodir=/usr/share/info --libdir=/usr/lib --libexecdir=/usr/lib --with-lua=lua5.3

ou git clone https://github.com/PowerDNS/pdns.git
cd pdns 

cd /pdns/pdns/recursordist
autoreconf -vi

DSC:
https://www.dns-oarc.net/dsc/download
wget https://www.dns-oarc.net/files/dsc/dsc-2.1.1.tar.gz
wget https://www.dns-oarc.net/files/dsc/dsc-2.6.1.tar.gz
https://www.dns-oarc.net/files/dsc/dsc-2.7.0.tar.gz
./configure --prefix=/usr --bindir=/usr/local/bin --sbindir=/usr/local/sbin --with-data-dir=/home/artica/dsc --sysconfdir=/etc --mandir=/usr/share/man --infodir=/usr/share/info --libdir=/usr/lib --libexecdir=/usr/lib --with-pid-file=/var/run/dsc.pid


*/
if($argv[1]=="--version"){echo PDNS_VERSION();exit;}
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


$dirsrc="pdns-0.0.0";
$GLOBALS["ROOT-DIR"]="/root/pdns-builder";


if(is_dir($GLOBALS["ROOT-DIR"])){shell_exec("$rm -rf {$GLOBALS["ROOT-DIR"]}");}
create_package();


function PDNS_VERSION():string{
	$unix=new unix();
	$pdns_recursor=$unix->find_program("pdns_recursor");
	exec("$pdns_recursor --version 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#version:\s+([0-9\.]+)#i", @implode("", $results),$re)){return $re[1];}
		if(preg_match("#PowerDNS Recursor\s+([0-9\.]+)#i", @implode("", $results),$re)){return $re[1];}
	}
    return "";
}




function create_package(){
	$Architecture=Architecture();
	if($Architecture==64){$Architecture="x64";}
	if($Architecture==32){$Architecture="i386";}
	$WORKDIR=$GLOBALS["ROOT-DIR"];
	
	@mkdir("$WORKDIR/sbin",0755,true);
	@mkdir("$WORKDIR/usr/sbin",0755,true);
	@mkdir("$WORKDIR/usr/lib/powerdns",0755,true);
	@mkdir("$WORKDIR/usr/local/bin",0755,true);
	@mkdir("$WORKDIR/usr/local/libexec/dsc",0755,true);

	
	if(is_dir("/lib/powerdns")){
		shell_exec("/bin/cp -rfd /lib/powerdns/* $WORKDIR/usr/lib/powerdns/");
	}
	
	if(is_dir("/usr/local/libexec/dsc")){
		shell_exec("/bin/cp -rfd /usr/local/libexec/dsc/* $WORKDIR/usr/local/libexec/dsc/");
	}
	
	
	$fdir[]="/usr/lib/powerdns";
	$fdir[]="/lib/powerdns";
	$fdir[]="/etc/powerdns";
	$fdir[]="/usr/share/poweradmin";
	$fdir[]="/usr/share/doc/pdns";
	$fdir[]="/usr/lib/powerdns";
	while (list ($num, $ligne) = each ($fdir) ){
		@mkdir("$WORKDIR$ligne",0755,true);
		echo "Installing $ligne in $WORKDIR$ligne/\n";
		shell_exec("/bin/cp -rfd $ligne/* $WORKDIR$ligne/");
	}
	
	if(!is_file("/usr/sbin/pdns_recursor")){
		if(is_file("/usr/local/sbin/pdns_recursor")){
			@copy("/usr/local/sbin/pdns_recursor", "/usr/sbin/pdns_recursor");
			@chmod("/usr/sbin/pdns_recursor",0755);
		}
	}
	if(!is_file("/usr/bin/rec_control")){
		if(is_file("/usr/local/sbin/pdns_recursor")){
			@copy("/usr/local/bin/rec_control", "/usr/bin/rec_control");
			@chmod("/usr/bin/rec_control",0755);
		}
	}
	
	
		
	$f[]="strip -s /usr/local/bin/dsc";
	$f[]="strip -s /usr/sbin/pdns_recursor";
	$f[]="strip -s /usr/sbin/pdns_server";
	
	$f[]="/usr/local/bin/dsc";
	$f[]="/usr/sbin/pdns_recursor";
	$f[]="/usr/sbin/pdns_server";
	$f[]="/usr/bin/pdnssec";
	$f[]="/usr/bin/dnsreplay";
	$f[]="/usr/bin/pdns_control";
	$f[]="/usr/bin/rec_control";
	$f[]="/etc/init.d/pdns-recursor";
	$f[]="/usr/bin/zone2sql";
	$f[]="/usr/bin/zone2ldap";
	$f[]="/usr/bin/zone2json";
	$f[]="/etc/init.d/pdns";
	$f[]="/usr/share/man/man8/pdns_control.8"; 
	$f[]="/usr/share/man/man8/pdnssec.8";  
	$f[]="/usr/share/man/man8/pdns_server.8";
	$f[]="/usr/share/man/man1/pdns_recursor.1";
	$f[]="/usr/share/man/man1/rec_control.1";
	

	foreach ($f as $num=>$ligne){
		if(!is_file($ligne)){echo "$ligne no such file\n";continue;}
		if(preg_match("#(\/bin|\/sbin)#", $ligne)){
			system("strip -s $ligne");
		}
		
		
		$dir=dirname($ligne);
		echo "Installing $ligne in $WORKDIR$dir/\n";
		if(!is_dir("$WORKDIR$dir")){@mkdir("$WORKDIR$dir",0755,true);}
		shell_exec("/bin/cp -fd $ligne $WORKDIR$dir/");
		
	}
	
	$version=PDNS_VERSION();
	echo "Creating package done....\n";
	echo "Building package Arch:$Architecture Version:$version\n";
	echo "Going to $WORKDIR\n";
	@chdir("$WORKDIR");
	$debianv=DebianVersion();
	
	if($debianv>6){
		$debianv="-debian$debianv";
	}
	
	$TARGET_TGZ="/root/pdnsc-$Architecture$debianv-$version.tar.gz";
	
	
	
	
	echo "Compressing $TARGET_TGZ\n";
	if(is_file($TARGET_TGZ)){@unlink($TARGET_TGZ);}
	shell_exec("tar -czf $TARGET_TGZ *");
	echo "Compressing $TARGET_TGZ Done...\n";	
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


function DebianVersion(){

	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];

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










