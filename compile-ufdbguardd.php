<?php

/*./configure --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --with-ufdb-dbhome=/home/ufdbcat --with-ufdb-user=squid --with-ufdb-config=/etc/ufdbcat --with-ufdb-logdir=/var/log/ufdbcat --without-unix-sockets --with-ufdb-bindir=/opt/ufdbcat/bin
 * 
 * Debian 9 en attendant la compatibilité openssl 1.1 
 * ./configure --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --with-ufdb-dbhome=/var/lib/squidguard --with-ufdb-user=squid --with-ufdb-config=/etc/ufdbguard --with-ufdb-logdir=/var/log/ufdbguard --without-unix-sockets  --with-ssl-lib=/usr/openssl-old/lib --with-ssl-inc=/usr/openssl-old/include/openssl LDFLAGS="-L/usr/openssl-old/lib -ldl -pthread -lpthread"
 * 
 * 
 * 
 * 
 */

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



$GLOBALS["ROOT-DIR"]="/root/ufdbguard-builder";


if(is_dir($GLOBALS["ROOT-DIR"])){shell_exec("$rm -rf {$GLOBALS["ROOT-DIR"]}");}
create_package();


function UFDBGUARD_VERSION(){
	exec("/usr/bin/ufdbguardd -v 2>&1",$results);
		foreach ($results as $num=>$line){
			if(preg_match("#ufdbguardd.*?([0-9\.]+)#", $line,$re)){return $re[1];}
		}
}




function create_package(){
	$Architecture=Architecture();
	if($Architecture==64){$Architecture="x64";}
	if($Architecture==32){$Architecture="i386";}
	$WORKDIR=$GLOBALS["ROOT-DIR"];
	
	
	$f[]="ufdbAnalyse";
	$f[]="ufdb_analyse_users";
	$f[]="ufdbgclient";
	$f[]="ufdbguardd ";
	$f[]="ufdb-pstack";
	$f[]="ufdb_top_urls";
	$f[]="ufdbUpdate";
	$f[]="ufdb_analyse_urls";
	$f[]="ufdbConvertDB";
	$f[]="ufdbGenTable";
	$f[]="ufdbhttpd";
	$f[]="ufdbsignal";
	$f[]="ufdb_top_users";
	
	@mkdir("$WORKDIR/usr/bin",0755,true);
	
	foreach ($f as $bin){
		shell_exec("strip -s /usr/bin/$bin");
		if(is_file("/opt/ufdbcat/bin/$bin")){
			shell_exec("strip -s /opt/ufdbcat/bin/$bin");
		}
		shell_exec("cp -fd /usr/bin/$bin $WORKDIR/usr/bin/$bin");
	}
	$f=array();
	@mkdir("$WORKDIR/var/lib/squidguard/security",0755,true);
	@mkdir("$WORKDIR/home/ufdbcat/security",0755,true);
	@mkdir("$WORKDIR/opt/ufdbcat/bin",0755,true);
	
	@mkdir("$WORKDIR/etc/artica-postfix",0755,true);
	@mkdir("$WORKDIR/etc/artica-postfix/settings/Daemons",0755,true);
	
	@file_put_contents("$WORKDIR/etc/artica-postfix/UFDBCAT_INSTALLED",time());
	@file_put_contents("$WORKDIR/etc/artica-postfix/settings/Daemons/UfdbcatOnlyTCP",1);
	
	if(is_file("/opt/ufdbcat/bin/ufdbcatdd")){@unlink("/opt/ufdbcat/bin/ufdbcatdd");}
	@copy("/opt/ufdbcat/bin/ufdbguardd", "/opt/ufdbcat/bin/ufdbcatdd");
	shell_exec("cp -fd /var/lib/squidguard/security/cacerts $WORKDIR/var/lib/squidguard/security/cacerts");
	shell_exec("cp -fd /home/ufdbcat/security/cacerts $WORKDIR/home/ufdbcat/security/cacerts");
	
	
	@chmod("/opt/ufdbcat/bin/ufdbcatdd",0755);

	$f[]="/opt/ufdbcat/bin/ufdbcatdd";
	$f[]="/opt/ufdbcat/bin/ufdbGenTable";
	
	
	$version=UFDBGUARD_VERSION();

	foreach ($f as $num=>$ligne){
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
	$tgzname="ufdbguardd-$Architecture-$version.tar.gz";
	echo "Compressing $tgzname\n";
	if(is_file("/root/$tgzname")){@unlink("/root/$tgzname");}
	shell_exec("tar -czf /root/$tgzname *");
	echo "Compressing /root/$tgzname Done...\n";	
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










