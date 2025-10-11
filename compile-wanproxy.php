<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
/*./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-silent-rules --libexecdir=\${prefix}/lib/clamav --disable-maintainer-mode --disable-dependency-tracking --with-dbdir=/var/lib/clamav --sysconfdir=/etc/clamav --enable-milter --enable-dns-fix --with-gnu-ld
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
$GLOBALS["ROOT-DIR"]="/root/wanproxy-compile";
if($argv[1]=='--compile'){$GLOBALS["SHOW_COMPILE_ONLY"]=true;}
if(preg_match("#--no-compile#", @implode(" ", $argv))){$GLOBALS["NO_COMPILE"]=true;}
if(preg_match("#--verbose#", @implode(" ", $argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--repos#", @implode(" ", $argv))){$GLOBALS["REPOS"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}

create_package();


function WANPROXY_VERSION(){
	return trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WANPROXY_VERSION"));
}

function create_package(){
	
	if(!is_file("/root/wanproxy/programs/wanproxy/wanproxy")){
		die("/root/wanproxy/programs/wanproxy/wanproxy no such file\n");
	}
	$Architecture=Architecture();
	if($Architecture==64){$Architecture="64";}
	if($Architecture==32){$Architecture="32";}
	$WORKDIR=$GLOBALS["ROOT-DIR"];
	
	$unix=new unix();
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	$strip=$unix->find_program("strip");
	
	$version=WANPROXY_VERSION();
	@mkdir("$WORKDIR/usr/local/bin",0755,true);
	@mkdir("$WORKDIR/etc/artica-postfix/settings/Daemons",0755,true);
	
	system("$strip -s /root/wanproxy/programs/wanproxy/wanproxy");
	system("$cp -fd /etc/artica-postfix/settings/Daemons/WANPROXY_VERSION $WORKDIR/etc/artica-postfix/settings/Daemons/");
	system("$cp -fd /root/wanproxy/programs/wanproxy/wanproxy $WORKDIR/usr/local/bin/");

	
	echo "Creating package done....\n";
	echo "Building package Arch:$Architecture Version:$version\n";
	echo "Going to $WORKDIR\n";
	@chdir("$WORKDIR");
	$targtefile="wanproxy-$Architecture-$version.tar.gz";
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
?>