<?php
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if($argv[1]=="mac"){
	echo "00:1f:3b:b3:a4:3b -> ". strlen("00:1f:3b:b3:a4:3b")."\n";
	die("DIE " .__FILE__." Line: ".__LINE__);
}

if($argv[1]=="--patch-net"){patchnetfilter();die("DIE " .__FILE__." Line: ".__LINE__);}

$unix=new unix();
// netfilter error="http://muhdzamri.blogspot.com/2011/01/usrincludelinuxnetfilteripv4h53-error.html"
$Architecture=Architecture();
$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");
$v="dansguardian-2.12.0.0.tar.gz";
$dirsrc="dansguardian-2.12.0.0";
if($Architecture==64){$Architecture="x64";}
if($Architecture==32){$Architecture="i386";}

if(preg_match("#dansguardian-([0-9\.]+)\.tar#", $v,$re)){$master_version=$re[1];}

if(is_dir("/usr/share/dansguardian")){shell_exec("$rm -rf /usr/share/dansguardian");}
if(is_dir("/etc/dansguardian")){shell_exec("$rm -rf /etc/dansguardian");}
if(is_dir("/root/dansguardian-builder")){shell_exec("$rm -rf /root/dansguardian-builder");}

chdir("/root");
shell_exec("$wget http://dansguardian.org/downloads/2/Alpha/$v");

if(!is_file("/root/$v")){echo "Downloading failed...\n";die("DIE " .__FILE__." Line: ".__LINE__);}
shell_exec("$tar -xhf /root/$v");
chdir("/root/$dirsrc");
$configure="./configure --enable-orig-ip=yes --enable-lfs=yes --enable-clamd=no --enable-icap=yes --with-proxyuser=squid --with-proxygroup=squid --with-piddir=/var/run/dansguardian.pid --with-logdir=/var/log/dansguardian --prefix=/usr --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --enable-trickledm=yes --enable-ntlm=yes";
echo "configuring...\n";
shell_exec($configure);

echo "Patching...\n";
if(!patchnetfilter()){echo "patchnetfilter() Failed\n";die("DIE " .__FILE__." Line: ".__LINE__);}

echo "make...\n";
shell_exec("make");
echo "make install...\n";
shell_exec("make install");

if(!is_file("/usr/sbin/dansguardian")){echo "Failed\n";}

mkdir("/root/dansguardian-builder/usr/share/dansguardian",0755,true);
mkdir("/root/dansguardian-builder/etc/dansguardian",0755,true);
mkdir("/root/dansguardian-builder/usr/sbin",0755,true);

shell_exec("$cp -rf /usr/share/dansguardian/* /root/dansguardian-builder/usr/share/dansguardian/");
shell_exec("$cp -rf /etc/dansguardian/* /root/dansguardian-builder/etc/dansguardian/");
shell_exec("$cp -rf /usr/sbin/dansguardian /root/dansguardian-builder/usr/sbin/dansguardian");

chdir("/root/dansguardian-builder");
shell_exec("$tar -czf dansguardian2-$Architecture-$master_version.tar.gz *");
echo "/root/dansguardian-builder/dansguardian2-$Architecture-$master_version.tar.gz is now ready to be uploaded\n";


function patchnetfilter(){
	$netfilter="/usr/include/linux/netfilter_ipv4.h";
	if (!is_file($netfilter)){
		echo "$netfilter no such file\n";
		return;
	}
	
	$f=explode("\n",@file_get_contents($netfilter));
    foreach ($f as $val){
		if(preg_match("#include <limits\.h>#", $val)){
			echo "patchnetfilter() limits\.h found....\n";
			return true;
		}
		
	}
	
	reset($f);
    foreach ($f as $num=>$val){
		if(preg_match("#include#", $val)){
			echo "patchnetfilter() patching limits.h line $num\n";
			$f[$num]=$f[$num]."\n#include <limits.h>";
			@file_put_contents($netfilter, @implode("\n", $f));
			return true;
		}
		
	}	
	
	return false;
	
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