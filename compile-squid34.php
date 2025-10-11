<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/ressources/class.compile_squid.inc');

/*
 *
 * 
WIFIDOG

git clone https://github.com/wifidog/wifidog-gateway.git
cd /root/wifidog-gateway
./autogen.sh
make
make install

// *************** SARG 
 * 
 * git clone git://git.code.sf.net/p/sarg/code sarg-code
 * autoreconf -fi
 * ./configure --prefix=/usr --with-gd --with-iconv

unset($argv[0]);
$unix=new unix();
$php5=$unix->LOCATE_PHP5_BIN();
shell_exec("$php5 ".dirname(__FILE__)."/compile-squid34.php ".@implode(" ",$argv));
die("DIE " .__FILE__." Line: ".__LINE__);
// *************** C-ICAP 
/* CICAP
 * 
 * wget "http://downloads.sourceforge.net/project/c-icap/c-icap/0.3.x/c_icap-0.3.4.tar.gz?r=http%3A%2F%2Fc-icap.sourceforge.net%2Fdownload.html&ts=1409512420&use_mirror=heanet" -O c_icap-0.3.4.tar.gz 
 * 
 * ./configure --enable-static --prefix=/usr --includedir="\${prefix}/include" --enable-large-files --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/c-icap"
 * MODULES
 * wget "http://downloads.sourceforge.net/project/c-icap/c-icap-modules/0.3.x/c_icap_modules-0.3.2.tar.gz?r=http%3A%2F%2Fc-icap.sourceforge.net%2Fdownload.html&ts=1409512549&use_mirror=heanet" -O c_icap_modules-0.3.2.tar.gz 
 * 
 * ./configure --enable-static --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/c-icap" --with-clamav
 * 
 Contrack tools
 
git clone git://git.netfilter.org/libnfnetlink
cd /root/libnfnetlink
./autogen.sh
./configure --prefix=/usr
make && make install
cd /root
git clone git://git.netfilter.org/libmnl
cd /root/libmnl/ 
./autogen.sh
./configure --prefix=/usr
make && make install
cd /root/
git clone git://git.netfilter.org/libnetfilter_conntrack
cd /root/libnetfilter_conntrack
./autogen.sh
./configure --prefix=/usr
make && make install
cd /root/
wget http://www.netfilter.org/projects/libnetfilter_cttimeout/files/libnetfilter_cttimeout-1.0.0.tar.bz2
tar -xhf libnetfilter_cttimeout-1.0.0.tar.bz2
cd /root/libnetfilter_cttimeout-1.0.0
./configure --prefix=/usr
make && make install
cd /root/
wget http://www.netfilter.org/projects/libnetfilter_cthelper/files/libnetfilter_cthelper-1.0.0.tar.bz2
tar -xhf libnetfilter_cthelper-1.0.0.tar.bz2
cd /root/libnetfilter_cthelper-1.0.0
./configure --prefix=/usr
make && make install
cd /root/
wget http://www.netfilter.org/projects/libnetfilter_queue/files/libnetfilter_queue-1.0.2.tar.bz2
tar -xhf libnetfilter_queue-1.0.2.tar.bz2
cd /root/libnetfilter_queue-1.0.2
./configure --prefix=/usr
make && make install
cd /root/

cd /root/
 wget http://www.netfilter.org/projects/conntrack-tools/files/conntrack-tools-1.4.2.tar.bz2
 tar -xhf conntrack-tools-1.4.2.tar.bz2
 
  
wget http://ftp.de.debian.org/debian/pool/main/s/squid/squid_2.7.STABLE9.orig.tar.gz
tar -xhf squid_2.7.STABLE9.orig.tar.gz 
cd squid-2.7.STABLE9/
./configure --prefix=/usr --exec_prefix=/usr --bindir=/usr/sbin --sbindir=/usr/sbin --libexecdir=/usr/lib/squid27 --sysconfdir=/etc/squid27 --localstatedir=/var/spool/squid27 --datadir=/usr/share/squid27 --with-pthreads --enable-async-io --enable-storeio=ufs,aufs,coss,diskd,null --enable-ssl --enable-linux-netfilter --enable-arp-acl --enable-epoll --enable-removal-policies=lru,heap --enable-snmp --enable-delay-pools --enable-htcp --disable-cache-digests --enable-referer-log --enable-useragent-log --enable-auth="basic,digest,ntlm,negotiate" --enable-negotiate-auth-helpers=squid_kerb_auth --enable-carp --enable-follow-x-forwarded-for --with-large-files --with-maxfd=65536 --build x86_64-linux-gnu --program-suffix=27

./configure --prefix=/usr --exec_prefix=/usr --bindir=/usr/sbin --sbindir=/usr/sbin --libexecdir=/usr/lib/squid27 --sysconfdir=/etc/streamcache --localstatedir=/var/spool/squid27 --datadir=/usr/share/squid27 --with-pthreads --enable-async-io --enable-storeio=ufs,aufs,coss,diskd,null --enable-ssl --enable-linux-netfilter --enable-arp-acl --enable-epoll --enable-removal-policies=lru,heap --enable-snmp --enable-delay-pools --enable-htcp --disable-cache-digests --enable-referer-log --enable-useragent-log --enable-auth="basic,digest,ntlm,negotiate" --enable-negotiate-auth-helpers=squid_kerb_auth --enable-carp --enable-follow-x-forwarded-for --with-large-files --with-maxfd=65536 --build x86_64-linux-gnu --program-suffix=cache --program-prefix=stream
 
 
 * ---------------------------- C- ICAP
 * 
 *  ./configure --enable-static --prefix=/usr --includedir="\${prefix}/include" --enable-large-files --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/c-icap"
  fpsystem(cmd);
 
 MODULES : 
 
 ./configure --enable-static --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/c-icap" --with-clamav  
 
 
 */



$unix=new unix();
$GLOBALS["SHOW_COMPILE_ONLY"]=false;
$GLOBALS["NO_COMPILE"]=false;
$GLOBALS["REPOS"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if($argv[1]=='--compile'){$GLOBALS["SHOW_COMPILE_ONLY"]=true;$GLOBALS["NO_COMPILE"]=true;}
if(preg_match("#--no-compile#", @implode(" ", $argv))){$GLOBALS["NO_COMPILE"]=true;}
if(preg_match("#--verbose#", @implode(" ", $argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--repos#", @implode(" ", $argv))){$GLOBALS["REPOS"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--cross-packages"){crossroads_package();exit;}
if($argv[1]=="--factorize"){factorize($argv[2]);exit;}
if($argv[1]=="--serialize"){serialize_tests();exit;}
if($argv[1]=="--latests"){latests();exit;}
if($argv[1]=="--ufdb"){package_ufdbguard();exit;}
if($argv[1]=="--ecapclam"){ecap_clamav();exit;}
if($argv[1]=="--package"){create_package_squid();exit;}
if($argv[1]=="--c-icap-remove"){die("DIE " .__FILE__." Line: ".__LINE__);exit;}
if($argv[1]=="--msmtp"){package_msmtp();exit;}

if($argv[1]=="--nginx"){package_nginx();exit;}
if($argv[1]=="--nginx-compile"){nginx_compile();exit;}


if($argv[1]=="--dnsmasqver"){dnsmasq_lastver();exit;}
if($argv[1]=="--dnsmasq"){dnsmasq_compile();exit;}
if($argv[1]=="--langs"){ParseLangs();exit;}
if($argv[1]=="--ufdbcat"){compile_ufdbcat();exit;}



$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");
$dirsrc="squid-3.4.0.0";
$Architecture=Architecture();

if(!$GLOBALS["NO_COMPILE"]){
	
	$v=latests();
	if(preg_match("#squid-(.+?)-#", $v,$re)){$dirsrc=$re[1];}
	squid_admin_mysql(2, "Downloading lastest file $v, working directory $dirsrc ...",__FUNCTION__,__FILE__,__LINE__);
}

//$v="squid-3.4.6.tar.gz";

if(!$GLOBALS["FORCE"]){
	if(is_file("/root/$v")){if($GLOBALS["REPOS"]){echo "No updates...\n";die("DIE " .__FILE__." Line: ".__LINE__);}}
}

if(is_dir("/root/squid-builder")){
	echo "****** Removing /root/squid-builder ****** \n";
	shell_exec("$rm -rf /root/squid-builder");}
chdir("/root");
if(!$GLOBALS["NO_COMPILE"]){
	
	if(!is_file("/usr/include/libecap/common/libecap.h")){
		shell_exec("$wget http://www.measurement-factory.com/tmp/ecap/libecap-0.2.0.tar.gz -O /root/libecap-0.2.0.tar.gz");
		shell_exec("$tar -xhf /root/libecap-0.2.0.tar.gz -C /root/");
		chdir("/root/libecap-0.2.0");
		shell_exec("./configure --prefix=/usr");
		shell_exec("make");
		shell_exec("make install");
		if(!is_file("/usr/include/libecap/common/libecap.h")){
			echo "libecap.h failed\n";
			die("DIE " .__FILE__." Line: ".__LINE__);
		}
	}
	
	if(is_dir("/root/$dirsrc")){
		echo "****** Removing /root/$dirsrc ****** \n";
		shell_exec("/bin/rm -rf /root/$dirsrc");
		sleep(1);
	}
	@mkdir("/root/$dirsrc");
	if(!is_file("/root/$v")){
		squid_admin_mysql(2, "Detected new version $v", __FUNCTION__, __FILE__, __LINE__, "software");
		echo "Downloading $v ...\n";
		shell_exec("$wget http://www.squid-cache.org/Versions/v3/3.4/$v");
		if(!is_file("/root/$v")){
			squid_admin_mysql(2, "Downloading failed", __FUNCTION__, __FILE__, __LINE__, "software");
			echo "Downloading failed...\n";die("DIE " .__FILE__." Line: ".__LINE__);}
	}
	
	shell_exec("$tar -xhf /root/$v -C /root/$dirsrc/");
	chdir("/root/$dirsrc");
	if(!is_file("/root/$dirsrc/configure")){
		echo "/root/$dirsrc/configure no such file\n";
		$dirs=$unix->dirdir("/root/$dirsrc");
		while (list ($num, $ligne) = each ($dirs) ){if(!is_file("$ligne/configure")){echo "$ligne/configure no such file\n";}else{
			chdir("$ligne");
			echo "[OK]: Change to dir $ligne\n";
			$SOURCE_DIRECTORY=$ligne;
			break;}}
	}
	
	
	
	
}
$Architecture=Architecture();
$CFLAGS[]="#!/bin/sh";
$CFLAGS[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
$CFLAGS[]="CFLAGS=\"-g -O2 -fPIE -fstack-protector -DNUMTHREADS=128 --param=ssp-buffer-size=4 -Wformat -Werror=format-security -Wall\"";
$CFLAGS[]="CXXFLAGS=\"-g -O2 -fPIE -fstack-protector --param=ssp-buffer-size=4 -Wformat -Werror=format-security\"";
$CFLAGS[]="CPPFLAGS=\"-D_FORTIFY_SOURCE=2\" LDFLAGS=\"-fPIE -pie -Wl,-z,relro -Wl,-z,now\"";
$CFLAGS[]="echo \$CFLAGS";
$CFLAGS[]="echo \$CXXFLAGS";
$CFLAGS[]="echo \$CPPFLAGS";
$CFLAGS[]="";
@file_put_contents("/tmp/flags.sh", @implode("\n", $CFLAGS));
$CFLAGS=array();
@chmod("/tmp/flags.sh",0755);
system("/tmp/flags.sh");


$cmds[]="--prefix=/usr";
if($Architecture==64){
	$cmds[]="--build=x86_64-linux-gnu";
}
$cmds[]="--includedir=\${prefix}/include";
$cmds[]="--mandir=\${prefix}/share/man";
$cmds[]="--infodir=\${prefix}/share/info";
$cmds[]="--localstatedir=/var";
$cmds[]="--libexecdir=\${prefix}/lib/squid3";
$cmds[]="--disable-maintainer-mode";
$cmds[]="--disable-dependency-tracking";
$cmds[]="--srcdir=.";
$cmds[]="--datadir=/usr/share/squid3"; 
$cmds[]="--sysconfdir=/etc/squid3";
$cmds[]="--enable-gnuregex";
$cmds[]="--enable-removal-policy=heap"; 
$cmds[]="--enable-follow-x-forwarded-for"; 
$cmds[]="--disable-cache-digests"; 
$cmds[]="--enable-http-violations"; 
$cmds[]="--enable-removal-policies=lru,heap"; 
$cmds[]="--enable-arp-acl";
$cmds[]="--enable-truncate";
$cmds[]="--with-large-files";
$cmds[]="--with-pthreads";
$cmds[]="--enable-esi"; 
$cmds[]="--enable-storeio=aufs,diskd,ufs,rock"; 
$cmds[]="--enable-x-accelerator-vary";
$cmds[]="--with-dl";
$cmds[]="--enable-linux-netfilter"; 
$cmds[]="--with-netfilter-conntrack";
$cmds[]="--enable-wccpv2"; 
$cmds[]="--enable-eui"; 
$cmds[]="--enable-auth";
$cmds[]="--enable-auth-basic"; 
$cmds[]="--enable-snmp";
$cmds[]="--enable-icmp"; 
$cmds[]="--enable-auth-digest"; 
$cmds[]="--enable-log-daemon-helpers";
$cmds[]="--enable-url-rewrite-helpers";
$cmds[]="--enable-auth-ntlm";
$cmds[]="--with-default-user=squid";
$cmds[]="--enable-icap-client"; 
$cmds[]="--disable-cache-digests"; 
$cmds[]="--enable-poll";
$cmds[]="--enable-epoll";
$cmds[]="--enable-async-io=128";
$cmds[]="--enable-zph-qos";
$cmds[]="--enable-delay-pools";
$cmds[]="--enable-http-violations";
$cmds[]="--enable-url-maps";
$cmds[]="--enable-ecap";
$cmds[]="--enable-ssl"; 
$cmds[]="--enable-ssl-crtd";
$cmds[]="--enable-xmalloc-statistics";
$cmds[]="--with-filedescriptors=32768";
$cmds[]="--enable-ident-lookups";
//$cmds[]="--disable-ipv6";
$cmds[]="--disable-arch-native";

//CPPFLAGS="-I../libltdl"



$configure="./configure ". @implode(" ", $cmds);
if($GLOBALS["VERBOSE"]){echo "\n\n$configure\n\n";}

if($GLOBALS["SHOW_COMPILE_ONLY"]){echo $configure."\n";die("DIE " .__FILE__." Line: ".__LINE__);}
if(!$GLOBALS["NO_COMPILE"]){
	
	echo "configuring... ( logs in /root/squid-$v-configure.log )\n";
	shell_exec("$configure >/root/squid-$v-configure.log 2>&1");
	shell_exec("echo \"**************************************\" >>/root/squid-$v-configure.log 2>&1");
	shell_exec("echo \"\" >>/root/squid-$v-configure.log 2>&1");
	echo "Compiling...\n";
	if($GLOBALS["VERBOSE"]){system("make");}
	if(!$GLOBALS["VERBOSE"]){shell_exec("make >>/root/squid-$v-configure.log 2>&1");}
	squid_admin_mysql(2, "{installing} the new squid-cache $v version", __FUNCTION__, __FILE__, __LINE__, "software");
	
	
	$unix=new unix();
	$squid3=$unix->find_program("squid3");
	if(is_file($squid3)){@unlink($squid3);}
	echo "Removing squid last install\n";
	remove_squid();
	echo "Make install\n";
	shell_exec("echo \"**************************************\" >>/root/squid-$v-configure.log 2>&1");
	shell_exec("echo \"\" >>/root/squid-$v-configure.log 2>&1");
	if($GLOBALS["VERBOSE"]){system("make install");}
	if(!$GLOBALS["VERBOSE"]){shell_exec("make install >>/root/squid-$v-configure.log 2>&1");}	
	
}
if(!is_file("/usr/sbin/squid")){
	squid_admin_mysql(2, "{installing} the new squid-cache $v failed", __FUNCTION__, __FILE__, __LINE__, "software");
	echo "Failed\n";}
	

if(!$GLOBALS["NO_COMPILE"]){shell_exec("/bin/rm -rf /usr/share/squid3/errors/templates/*");}
shell_exec("/bin/chown -R squid:squid /usr/share/squid3");
echo "Compiling ufdbcat....\n";
compile_ufdbcat();
echo "Creating package....\n";
create_package_squid($t);	
	
function DebianVersion(){
	
	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];	
	
}

function dnsmasq_compile(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	

$dnsmasq_lastver=dnsmasq_lastver();
if($dnsmasq_lastver==null){echo "No version...\n";return;}	
if(!is_file("/usr/include/dbus-1.0/dbus/dbus.h")){shell_exec("apt-get install -y libdbus-1-dev");}
if(!is_file("/usr/include/libnetfilter_conntrack/libnetfilter_conntrack.h")){shell_exec("apt-get install -y libnetfilter-conntrack-dev");}

echo "downloading $dnsmasq_lastver\n";
if(!is_file("/root/$dnsmasq_lastver")){
	shell_exec("$wget http://www.thekelleys.org.uk/dnsmasq/$dnsmasq_lastver -O /root/$dnsmasq_lastver");
}
$f[]="make install-i18n";
$f[]="PREFIX=/usr"; 
$f[]="CFLAGS=\"-g -O2 -fstack-protector -DNO_IPV6 --param=ssp-buffer-size=4 -Wformat -Werror=format-security -D_FORTIFY_SOURCE=2 -Wall -W\"";
$f[]="LDFLAGS=\"-Wl,-z,relro\""; 
$f[]="COPTS=\" -DHAVE_DBUS -DHAVE_CONNTRACK\" CC=gcc";

$dir=str_replace(".tar.gz", "", $dnsmasq_lastver);
if(is_dir("/root/$dir")){shell_exec("$rm -rf /root/$dir");}
shell_exec("$tar -xhf /root/$dnsmasq_lastver -C /root/");

if(!is_dir("/root/$dir")){
	echo "/root/$dir no such dir\n";
	return;
}


@chdir("/root/$dir");
$cmd=@implode(" ", $f);
echo "$cmd\n";
shell_exec($cmd);
@chmod("/usr/sbin/dnsmasq",0755);

	
}

function dnsmasq_lastver(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	shell_exec("$wget http://www.thekelleys.org.uk/dnsmasq/ -O /root/dnsmasq.html");
	$f=explode("\n",@file_get_contents("/root/dnsmasq.html"));
	foreach ($f as $num=>$ligne){
		if(!preg_match("#href=.*?dnsmasq-([0-9\.]+).tar.gz#",$ligne,$re));
		$binver=str_replace(".", "", trim($re[1]));
		$R[$binver]="dnsmasq-{$re[1]}.tar.gz";
			
		
		
		
	}
	krsort($R);
	while (list ($num, $ligne) = each ($R) ){
		return $ligne;
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

function squid_version(){
	exec("/usr/sbin/squid -v 2>&1",$results);
	foreach ($results as $num=>$val){
		if(preg_match("#Squid Cache: Version\s+(.+)#", $val,$re)){
			return trim($re[1]);
		}
	}
	
}

function latests(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	echo "Downloading latests index file Versions/v3/3.4/\n";
	@unlink("/tmp/index.html");
	shell_exec("$wget http://www.squid-cache.org/Versions/v3/3.4/ -O /tmp/index.html");
	
	$CURMAIN=0;
	$f=explode("\n",@file_get_contents("/tmp/index.html"));
	foreach ($f as $index=>$line){
		if(preg_match("#<a href=\"squid-(.+?)\.tar\.gz#", $line,$re)){
			$ve=$re[1];
			
			if(preg_match("#^([0-9\.]+)-#", $ve,$ri)){
				$MAIN_VERSION=str_replace(".", "", $ri[1]);
			}else{
				$MAIN_VERSION=str_replace(".", "", $ve);
			}
			
			if($MAIN_VERSION>=$CURMAIN){
				$CURMAIN=$MAIN_VERSION;
			}else{
				continue;
			}
			
			if($GLOBALS["VERBOSE"]){echo  "$ve = MAIN=$MAIN_VERSION\n";}
			$STT=explode(".", $ve);
			$CountDeSTT=count($STT);
			if($CountDeSTT<4){$ve="{$ve}.00";}
			$veOrg=$ve;
			$ve=str_replace(".", "", $ve);
			$ve=str_replace("-", "", $ve);
			if($GLOBALS["VERBOSE"]){echo "Add version $veOrg -> `$ve`\n";}
			$file="squid-{$re[1]}.tar.gz";
			$versions[$ve]=$file;
		if($GLOBALS["VERBOSE"]){echo "$ve -> $file $CountDeSTT points\n";}
		}else{
			
		}
		
	}
	
	krsort($versions);
	while (list ($num, $filename) = each ($versions)){
		$vv[]=$filename;
	}
	
	echo "Found latest file version: `{$vv[0]}`\n";
	return $vv[0];
}


function crossroads_package(){
$Architecture=Architecture();	
if($Architecture==64){$Architecture="x64";}
if($Architecture==32){$Architecture="i386";}
$unix=new unix();
$tar=$unix->find_program("tar");
$f[]="/usr/sbin/xrctl";
$f[]="/usr/share/man/man1/xr.1";
$f[]="/usr/share/man/man1/xrctl.1";
$f[]="/usr/share/man/man5/xrctl.xml.5";
$f[]="/usr/sbin/xr";
@mkdir("/root/crossroads",0755,true);
foreach ($f as $num=>$file){
	$dir=dirname($file);
	@mkdir("/root/crossroads$dir",0755,true);
	@copy($file, "/root/crossroads$file");

}
	chdir("/root/crossroads");
	shell_exec("$tar -czf crossroads-$Architecture.tar.gz *");

	
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

function serialize_tests(){
	$array["zdate"]=date("Y-m-d H:i:s");
	$array["text"]="this is the text";
	$array["function"]="this is the function";
	$array["file"]="this is the process";
	$array["line"]="this is the line";
	$array["category"]="this is the category";
	$serialize=serialize($array);
	echo $serialize;
	
}






function c_cicap_remove(){
	die("DIE " .__FILE__." Line: ".__LINE__);

}





function ecap_clamav(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");	
	chdir("/root");
	shell_exec("$rm -rf /root/libecap-0.2.0 >/dev/null 2>&1");
	@unlink("/root/libecap-0.2.0.tar.gz");
	echo "Download libecap-0.2.0.tar.gz\n";
	shell_exec("wget http://www.measurement-factory.com/tmp/ecap/libecap-0.2.0.tar.gz");
	echo "extracting libecap-0.2.0.tar.gz\n";
	shell_exec("$tar -xhf libecap-0.2.0.tar.gz");
	if(!is_dir("/root/libecap-0.2.0")){echo "Failed\n";return;}
	chdir("/root/libecap-0.2.0");
	echo "Configuring....\n";
	shell_exec("./configure --prefix=/usr --includedir=\"\${prefix}/include\" --mandir=\"\${prefix}/share/man\" --infodir=\"\${prefix}/share/info\" --sysconfdir=/etc --localstatedir=/var --libexecdir=\"\${prefix}/lib\"");
	if(!is_file("/root/libecap-0.2.0/Makefile")){echo "Failed\n";return;}
	echo "Make....\n";
	shell_exec("make");
	shell_exec("make install");
	mkdir("/root/ecapav/usr/include/libecap/common",0755,true);
	mkdir("/root/ecapav/usr/include/libecap/adapter",0755,true);
	mkdir("/root/ecapav/usr/include/libecap/host",0755,true);
	mkdir("/root/ecapav/usr/lib",0755,true);
	mkdir("/root/ecapav/usr/libexec/squid",0755,true);
	
	shell_exec("$cp -a /usr/include/libecap/common/* /root/ecapav/usr/include/libecap/common/");
	shell_exec("$cp -a /usr/include/libecap/adapter/* /root/ecapav/usr/include/libecap/adapter/");
	shell_exec("$cp -a /usr/include/libecap/host/* /root/ecapav/usr/include/libecap/host/");
	shell_exec("$cp -a /usr/lib/libecap.so.2.0.0 /root/ecapav/usr/lib/libecap.so.2.0.0");
	shell_exec("$cp -a /usr/lib/libecap.so.2 /root/ecapav/usr/lib/libecap.so.2");
	shell_exec("$cp -a /usr/lib/libecap.so /root/ecapav/usr/lib/libecap.so");
	shell_exec("$cp -a /usr/lib/libecap.la /root/ecapav/usr/lib/libecap.la");
	
	
	chdir("/root");
	echo "Download squid-ecap-av-1.0.3.tar.bz2\n";
	@unlink("/root/squid-ecap-av-1.0.3.tar.bz2");
	shell_exec("wget http://www.articatech.net/download/squid-ecap-av-1.0.3.tar.bz2");
	echo "extracting squid-ecap-av-1.0.3.tar.bz2\n";
	shell_exec("$tar -xhf squid-ecap-av-1.0.3.tar.bz2");
	if(!is_dir("/root/squid-ecap-av-1.0.3")){echo "Failed\n";return;}
	chdir("/root/squid-ecap-av-1.0.3");
	echo "cmake\n";
	shell_exec("cmake -DCMAKE_INSTALL_PREFIX=/usr");
	echo "Make....\n";
	shell_exec("make");
	echo "Make install\n";
	shell_exec("make install");
	shell_exec("$cp -a /usr/libexec/squid/ecap_adapter_av.so /root/ecapav/usr/libexec/squid/ecap_adapter_av.so");
	
}
function package_msmtp_version(){
	exec("/root/msmtp-compiled/usr/bin/msmtp --version 2>&1",$results);
	foreach ($results as $num=>$line){
		if(preg_match("#msmtp version.*?([0-9\.]+)#", $line,$re)){return $re[1];}
	}


}

function package_msmtp(){
	$base="/root/msmtp-compiled";
	shell_exec("/bin/rm -rf $base");
	
	/* git clone git://git.code.sf.net/p/msmtp/code msmtp
	 *./configure  --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/gsasl"  --disable-dependency-tracking
	 */
	

	$f[]="/usr/share/info/msmtp.info";
	$f[]="/usr/bin/msmtp";
	$f[]="/usr/share/gettext/po";

	foreach ($f as $num=>$filename){

		if(is_dir($filename)){
			@mkdir("$base/$filename",0755,true);
			shell_exec("/bin/cp -rf $filename/ $base/$filename/");
			continue;
		}

		$dirname=dirname($filename);
		if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
		shell_exec("/bin/cp -f $filename $base/$dirname/");

	}

	$Architecture=Architecture();
	$version=package_msmtp_version();
	chdir($base);
	shell_exec("tar -czf msmtp-$Architecture-$version.tar.gz *");
	shell_exec("/bin/cp msmtp-$Architecture-$version.tar.gz /root/");
	echo "/root/msmtp-$Architecture-$version.tar.gz done\n";

}




function package_freerdp(){
/* uri: https://github.com/FreeRDP/FreeRDP/zipball/master
apt-get install libpcsclite-dev libasound2-dev libxtst-dev
cmake -DCMAKE_BUILD_TYPE=Debug -DWITH_DEBUG_CERTIFICATE=ON -DWITH_DEBUG_CHANNELS=ON WITH_DEBUG_CLIPRDR=ON -DWITH_DEBUG_DVC=ON -DWITH_DEBUG_GDI=ON -DWITH_DEBUG_KBD=ON -DWITH_DEBUG_LICENSE=ON -DWITH_DEBUG_NEGO=ON -DWITH_DEBUG_NLA=ON -DWITH_DEBUG_NTLM=ON -DWITH_DEBUG_ORDERS=ON -DWITH_DEBUG_RAIL=ON -DWITH_DEBUG_RDP=ON -DWITH_DEBUG_REDIR=ON -DWITH_DEBUG_RFX=ON -DWITH_DEBUG_SCARD=ON -DWITH_DEBUG_SVC=ON -DWITH_DEBUG_TRANSPORT=ON -DWITH_DEBUG_TSG=ON -DWITH_DEBUG_WND=ON -DWITH_DEBUG_X11=ON -DWITH_DEBUG_X11_CLIPRDR=ON -DWITH_DEBUG_X11_LOCAL_MOVESIZE=ON -DWITH_DEBUG_XV=ON -DWITH_FFMPEG=OFF -DWITH_JPEG=ON -DWITH_MANPAGES=ON -DWITH_PCSC=ON -DWITH_PROFILER=ON -DWITH_PULSEAUDIO=ON -DWITH_SERVER=ON -DWITH_SSE2=ON -DWITH_SSE2_TARGET=ON -DWITH_THIRD_PARTY=ON -DWITH_ALSA=ON -DWITH_XTEST=ON .
make
make install
*/
	
	$f["/usr/local/include/winpr/crt.h"]=true;
	$f["/usr/local/include/winpr/stream.h"]=true;
	$f["/usr/local/include/winpr/winhttp.h"]=true;
	$f["/usr/local/include/winpr/print.h"]=true;
	$f["/usr/local/include/winpr/nt.h"]=true;
	$f["/usr/local/include/winpr/interlocked.h"]=true;
	$f["/usr/local/include/winpr/spec.h"]=true;
	$f["/usr/local/include/winpr/sam.h"]=true;
	$f["/usr/local/include/winpr/ndr.h"]=true;
	$f["/usr/local/include/winpr/timezone.h"]=true;
	$f["/usr/local/include/winpr/winsock.h"]=true;
	$f["/usr/local/include/winpr/cmdline.h"]=true;
	$f["/usr/local/include/winpr/pipe.h"]=true;
	$f["/usr/local/include/winpr/security.h"]=true;
	$f["/usr/local/include/winpr/io.h"]=true;
	$f["/usr/local/include/winpr/wtsapi.h"]=true;
	$f["/usr/local/include/winpr/handle.h"]=true;
	$f["/usr/local/include/winpr/input.h"]=true;
	$f["/usr/local/include/winpr/rpc.h"]=true;
	$f["/usr/local/include/winpr/collections.h"]=true;
	$f["/usr/local/include/winpr/platform.h"]=true;
	$f["/usr/local/include/winpr/pool.h"]=true;
	$f["/usr/local/include/winpr/error.h"]=true;
	$f["/usr/local/include/winpr/windows.h"]=true;
	$f["/usr/local/include/winpr/memory.h"]=true;
	$f["/usr/local/include/winpr/file.h"]=true;
	$f["/usr/local/include/winpr/heap.h"]=true;
	$f["/usr/local/include/winpr/asn1.h"]=true;
	$f["/usr/local/include/winpr/schannel.h"]=true;
	$f["/usr/local/include/winpr/config.h"]=true;
	$f["/usr/local/include/winpr/winpr.h"]=true;
	$f["/usr/local/include/winpr/wlog.h"]=true;
	$f["/usr/local/include/winpr/sspi.h"]=true;
	$f["/usr/local/include/winpr/sysinfo.h"]=true;
	$f["/usr/local/include/winpr/string.h"]=true;
	$f["/usr/local/include/winpr/wtypes.h"]=true;
	$f["/usr/local/include/winpr/midl.h"]=true;
	$f["/usr/local/include/winpr/tchar.h"]=true;
	$f["/usr/local/include/winpr/thread.h"]=true;
	$f["/usr/local/include/winpr/path.h"]=true;
	$f["/usr/local/include/winpr/bcrypt.h"]=true;
	$f["/usr/local/include/winpr/endian.h"]=true;
	$f["/usr/local/include/winpr/crypto.h"]=true;
	$f["/usr/local/include/winpr/ntlm.h"]=true;
	$f["/usr/local/include/winpr/registry.h"]=true;
	$f["/usr/local/include/winpr/dsparse.h"]=true;
	$f["/usr/local/include/winpr/synch.h"]=true;
	$f["/usr/local/include/winpr/credentials.h"]=true;
	$f["/usr/local/include/winpr/sspicli.h"]=true;
	$f["/usr/local/include/winpr/environment.h"]=true;
	$f["/usr/local/include/winpr/library.h"]=true;
	$f["/usr/local/include/winpr/credui.h"]=true;
	$f["/usr/local/lib/libwinpr-timezone.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-timezone.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-timezone.so"]=true;
	$f["/usr/local/lib/libwinpr-timezone.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sspi.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sspi.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-sspi.so"]=true;
	$f["/usr/local/lib/libwinpr-sspi.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-interlocked.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-interlocked.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-interlocked.so"]=true;
	$f["/usr/local/lib/libwinpr-interlocked.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-synch.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-synch.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-synch.so"]=true;
	$f["/usr/local/lib/libwinpr-synch.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-input.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-input.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-input.so"]=true;
	$f["/usr/local/lib/libwinpr-input.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-handle.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-handle.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-handle.so"]=true;
	$f["/usr/local/lib/libwinpr-handle.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-dsparse.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-dsparse.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-dsparse.so"]=true;
	$f["/usr/local/lib/libwinpr-dsparse.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-rpc.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-rpc.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-rpc.so"]=true;
	$f["/usr/local/lib/libwinpr-rpc.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-credentials.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-credentials.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-credentials.so"]=true;
	$f["/usr/local/lib/libwinpr-credentials.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-environment.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-environment.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-environment.so"]=true;
	$f["/usr/local/lib/libwinpr-environment.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-pipe.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-pipe.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-pipe.so"]=true;
	$f["/usr/local/lib/libwinpr-pipe.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-thread.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-thread.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-thread.so"]=true;
	$f["/usr/local/lib/libwinpr-thread.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-error.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-error.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-error.so"]=true;
	$f["/usr/local/lib/libwinpr-error.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sysinfo.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sysinfo.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-sysinfo.so"]=true;
	$f["/usr/local/lib/libwinpr-sysinfo.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-crypto.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-crypto.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-crypto.so"]=true;
	$f["/usr/local/lib/libwinpr-crypto.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-pool.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-pool.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-pool.so"]=true;
	$f["/usr/local/lib/libwinpr-pool.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-crt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-crt.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-crt.so"]=true;
	$f["/usr/local/lib/libwinpr-crt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-asn1.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-asn1.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-asn1.so"]=true;
	$f["/usr/local/lib/libwinpr-asn1.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-file.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-file.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-file.so"]=true;
	$f["/usr/local/lib/libwinpr-file.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-wtsapi.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-wtsapi.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-wtsapi.so"]=true;
	$f["/usr/local/lib/libwinpr-wtsapi.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-io.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-io.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-io.so"]=true;
	$f["/usr/local/lib/libwinpr-io.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-winhttp.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-winhttp.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-winhttp.so"]=true;
	$f["/usr/local/lib/libwinpr-winhttp.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-path.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-path.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-path.so"]=true;
	$f["/usr/local/lib/libwinpr-path.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-heap.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-heap.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-heap.so"]=true;
	$f["/usr/local/lib/libwinpr-heap.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-library.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-library.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-library.so"]=true;
	$f["/usr/local/lib/libwinpr-library.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-com.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-com.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-com.so"]=true;
	$f["/usr/local/lib/libwinpr-com.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-credui.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-credui.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-credui.so"]=true;
	$f["/usr/local/lib/libwinpr-credui.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-registry.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-registry.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-registry.so"]=true;
	$f["/usr/local/lib/libwinpr-registry.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-winsock.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-winsock.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-winsock.so"]=true;
	$f["/usr/local/lib/libwinpr-winsock.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-bcrypt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-bcrypt.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-bcrypt.so"]=true;
	$f["/usr/local/lib/libwinpr-bcrypt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-utils.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-utils.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-utils.so"]=true;
	$f["/usr/local/lib/libwinpr-utils.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-nt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-nt.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-nt.so"]=true;
	$f["/usr/local/lib/libwinpr-nt.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sspicli.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-sspicli.so.0.1"]=true;
	$f["/usr/local/lib/libwinpr-sspicli.so"]=true;
	$f["/usr/local/lib/libwinpr-sspicli.so.0.1.0"]=true;
	$f["/usr/local/lib/libwinpr-makecert-tool.a"]=true;
	$f["/usr/local/include/freerdp/types.h"]=true;
	$f["/usr/local/include/freerdp/api.h"]=true;
	$f["/usr/local/include/freerdp/peer.h"]=true;
	$f["/usr/local/include/freerdp/dvc.h"]=true;
	$f["/usr/local/include/freerdp/svc.h"]=true;
	$f["/usr/local/include/freerdp/rail.h"]=true;
	$f["/usr/local/include/freerdp/secondary.h"]=true;
	$f["/usr/local/include/freerdp/window.h"]=true;
	$f["/usr/local/include/freerdp/primitives.h"]=true;
	$f["/usr/local/include/freerdp/primary.h"]=true;
	$f["/usr/local/include/freerdp/pointer.h"]=true;
	$f["/usr/local/include/freerdp/input.h"]=true;
	$f["/usr/local/include/freerdp/extension.h"]=true;
	$f["/usr/local/include/freerdp/error.h"]=true;
	$f["/usr/local/include/freerdp/message.h"]=true;
	$f["/usr/local/include/freerdp/scancode.h"]=true;
	$f["/usr/local/include/freerdp/settings.h"]=true;
	$f["/usr/local/include/freerdp/altsec.h"]=true;
	$f["/usr/local/include/freerdp/constants.h"]=true;
	$f["/usr/local/include/freerdp/client.h"]=true;
	$f["/usr/local/include/freerdp/addin.h"]=true;
	$f["/usr/local/include/freerdp/event.h"]=true;
	$f["/usr/local/include/freerdp/listener.h"]=true;
	$f["/usr/local/include/freerdp/graphics.h"]=true;
	$f["/usr/local/include/freerdp/freerdp.h"]=true;
	$f["/usr/local/include/freerdp/update.h"]=true;
	$f["/usr/local/include/freerdp/cache"]=true;
	$f["/usr/local/include/freerdp/cache/offscreen.h"]=true;
	$f["/usr/local/include/freerdp/cache/nine_grid.h"]=true;
	$f["/usr/local/include/freerdp/cache/bitmap.h"]=true;
	$f["/usr/local/include/freerdp/cache/pointer.h"]=true;
	$f["/usr/local/include/freerdp/cache/brush.h"]=true;
	$f["/usr/local/include/freerdp/cache/cache.h"]=true;
	$f["/usr/local/include/freerdp/cache/glyph.h"]=true;
	$f["/usr/local/include/freerdp/cache/palette.h"]=true;
	$f["/usr/local/include/freerdp/codec"]=true;
	$f["/usr/local/include/freerdp/codec/rfx.h"]=true;
	$f["/usr/local/include/freerdp/codec/nsc.h"]=true;
	$f["/usr/local/include/freerdp/codec/bitmap.h"]=true;
	$f["/usr/local/include/freerdp/codec/mppc_enc.h"]=true;
	$f["/usr/local/include/freerdp/codec/dsp.h"]=true;
	$f["/usr/local/include/freerdp/codec/jpeg.h"]=true;
	$f["/usr/local/include/freerdp/codec/mppc_dec.h"]=true;
	$f["/usr/local/include/freerdp/codec/color.h"]=true;
	$f["/usr/local/include/freerdp/codec/audio.h"]=true;
	$f["/usr/local/include/freerdp/crypto"]=true;
	$f["/usr/local/include/freerdp/crypto/per.h"]=true;
	$f["/usr/local/include/freerdp/crypto/der.h"]=true;
	$f["/usr/local/include/freerdp/crypto/certificate.h"]=true;
	$f["/usr/local/include/freerdp/crypto/ber.h"]=true;
	$f["/usr/local/include/freerdp/crypto/er.h"]=true;
	$f["/usr/local/include/freerdp/crypto/crypto.h"]=true;
	$f["/usr/local/include/freerdp/crypto/tls.h"]=true;
	$f["/usr/local/include/freerdp/gdi"]=true;
	$f["/usr/local/include/freerdp/gdi/region.h"]=true;
	$f["/usr/local/include/freerdp/gdi/16bpp.h"]=true;
	$f["/usr/local/include/freerdp/gdi/line.h"]=true;
	$f["/usr/local/include/freerdp/gdi/bitmap.h"]=true;
	$f["/usr/local/include/freerdp/gdi/drawing.h"]=true;
	$f["/usr/local/include/freerdp/gdi/32bpp.h"]=true;
	$f["/usr/local/include/freerdp/gdi/shape.h"]=true;
	$f["/usr/local/include/freerdp/gdi/pen.h"]=true;
	$f["/usr/local/include/freerdp/gdi/dc.h"]=true;
	$f["/usr/local/include/freerdp/gdi/8bpp.h"]=true;
	$f["/usr/local/include/freerdp/gdi/brush.h"]=true;
	$f["/usr/local/include/freerdp/gdi/gdi.h"]=true;
	$f["/usr/local/include/freerdp/gdi/clipping.h"]=true;
	$f["/usr/local/include/freerdp/gdi/palette.h"]=true;
	$f["/usr/local/include/freerdp/locale"]=true;
	$f["/usr/local/include/freerdp/locale/timezone.h"]=true;
	$f["/usr/local/include/freerdp/locale/keyboard.h"]=true;
	$f["/usr/local/include/freerdp/locale/locale.h"]=true;
	$f["/usr/local/include/freerdp/rail"]=true;
	$f["/usr/local/include/freerdp/rail/rail.h"]=true;
	$f["/usr/local/include/freerdp/rail/window.h"]=true;
	$f["/usr/local/include/freerdp/rail/icon.h"]=true;
	$f["/usr/local/include/freerdp/rail/window_list.h"]=true;
	$f["/usr/local/include/freerdp/utils"]=true;
	$f["/usr/local/include/freerdp/utils/tcp.h"]=true;
	$f["/usr/local/include/freerdp/utils/uds.h"]=true;
	$f["/usr/local/include/freerdp/utils/bitmap.h"]=true;
	$f["/usr/local/include/freerdp/utils/signal.h"]=true;
	$f["/usr/local/include/freerdp/utils/pcap.h"]=true;
	$f["/usr/local/include/freerdp/utils/rail.h"]=true;
	$f["/usr/local/include/freerdp/utils/time.h"]=true;
	$f["/usr/local/include/freerdp/utils/svc_plugin.h"]=true;
	$f["/usr/local/include/freerdp/utils/list.h"]=true;
	$f["/usr/local/include/freerdp/utils/passphrase.h"]=true;
	$f["/usr/local/include/freerdp/utils/stopwatch.h"]=true;
	$f["/usr/local/include/freerdp/utils/msusb.h"]=true;
	$f["/usr/local/include/freerdp/utils/debug.h"]=true;
	$f["/usr/local/include/freerdp/utils/profiler.h"]=true;
	$f["/usr/local/include/freerdp/utils/event.h"]=true;
	$f["/usr/local/include/freerdp/client"]=true;
	$f["/usr/local/include/freerdp/client/tsmf.h"]=true;
	$f["/usr/local/include/freerdp/client/cliprdr.h"]=true;
	$f["/usr/local/include/freerdp/client/cmdline.h"]=true;
	$f["/usr/local/include/freerdp/client/channels.h"]=true;
	$f["/usr/local/include/freerdp/client/drdynvc.h"]=true;
	$f["/usr/local/include/freerdp/client/rdpei.h"]=true;
	$f["/usr/local/include/freerdp/client/file.h"]=true;
	$f["/usr/local/include/freerdp/client/disp.h"]=true;
	$f["/usr/local/include/freerdp/server"]=true;
	$f["/usr/local/include/freerdp/server/cliprdr.h"]=true;
	$f["/usr/local/include/freerdp/server/rdpsnd.h"]=true;
	$f["/usr/local/include/freerdp/server/audin.h"]=true;
	$f["/usr/local/include/freerdp/server/channels.h"]=true;
	$f["/usr/local/include/freerdp/server/drdynvc.h"]=true;
	$f["/usr/local/include/freerdp/server/rdpdr.h"]=true;
	$f["/usr/local/include/freerdp/channels"]=true;
	$f["/usr/local/include/freerdp/channels/rdpsnd.h"]=true;
	$f["/usr/local/include/freerdp/channels/channels.h"]=true;
	$f["/usr/local/include/freerdp/channels/rdpdr.h"]=true;
	$f["/usr/local/include/freerdp/channels/wtsvc.h"]=true;
	$f["/usr/local/lib/libfreerdp-utils.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-utils.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-utils.so"]=true;
	$f["/usr/local/lib/libfreerdp-utils.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-common.so.1.1.0-beta1"]=true;
	$f["/usr/local/lib/libfreerdp-common.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-common.so"]=true;
	$f["/usr/local/lib/libfreerdp-common.so.1.1.0-beta1"]=true;
	$f["/usr/local/lib/libfreerdp-gdi.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-gdi.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-gdi.so"]=true;
	$f["/usr/local/lib/libfreerdp-gdi.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-rail.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-rail.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-rail.so"]=true;
	$f["/usr/local/lib/libfreerdp-rail.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-cache.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-cache.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-cache.so"]=true;
	$f["/usr/local/lib/libfreerdp-cache.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-codec.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-codec.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-codec.so"]=true;
	$f["/usr/local/lib/libfreerdp-codec.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-crypto.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-crypto.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-crypto.so"]=true;
	$f["/usr/local/lib/libfreerdp-crypto.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-locale.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-locale.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-locale.so"]=true;
	$f["/usr/local/lib/libfreerdp-locale.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-primitives.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-primitives.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-primitives.so"]=true;
	$f["/usr/local/lib/libfreerdp-primitives.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-core.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-core.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-core.so"]=true;
	$f["/usr/local/lib/libfreerdp-core.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-client.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-client.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-client.so"]=true;
	$f["/usr/local/lib/libfreerdp-client.so.1.1.0"]=true;
	$f["/usr/local/lib/libxfreerdp-client.so.1.1.0"]=true;
	$f["/usr/local/lib/libxfreerdp-client.so.1.1"]=true;
	$f["/usr/local/lib/libxfreerdp-client.so"]=true;
	$f["/usr/local/lib/libxfreerdp-client.so.1.1.0"]=true;
	$f["/usr/local/bin/xfreerdp"]=true;
	$f["/usr/local/bin/xfreerdp"]=true;
	$f["/usr/local/lib/libfreerdp-server.so.1.1.0"]=true;
	$f["/usr/local/lib/libfreerdp-server.so.1.1"]=true;
	$f["/usr/local/lib/libfreerdp-server.so"]=true;
	$f["/usr/local/lib/libfreerdp-server.so.1.1.0"]=true;
	$f["/usr/local/lib/libxfreerdp-server.so.1.1.0"]=true;
	$f["/usr/local/lib/libxfreerdp-server.so.1.1"]=true;
	$f["/usr/local/lib/libxfreerdp-server.so"]=true;
	$f["/usr/local/lib/libxfreerdp-server.so.1.1.0"]=true;
	$f["/usr/local/bin/xfreerdp-server"]=true;
	$f["/usr/local/bin/xfreerdp-server"]=true;	
	$f["/etc/ld.so.conf.d/freerdp.conf"]=true;	
	
}

function nginx_compile(){
	$f[]="./configure";
	$f[]="--with-luajit ";
	$f[]="--sbin-path=/usr/sbin/nginx ";
	$f[]="--prefix=/usr/share/nginx ";
	$f[]="--conf-path=/etc/nginx/nginx.conf ";
	$f[]="--error-log-path=/var/log/nginx/error.log ";
	$f[]="--http-client-body-temp-path=/var/lib/nginx/body ";
	$f[]="--http-fastcgi-temp-path=/var/lib/nginx/fastcgi ";
	$f[]="--http-log-path=/var/log/nginx/access.log ";
	$f[]="--http-proxy-temp-path=/var/lib/nginx/proxy ";
	$f[]="--http-scgi-temp-path=/var/lib/nginx/scgi ";
	$f[]="--http-uwsgi-temp-path=/var/lib/nginx/uwsgi ";
	$f[]="--lock-path=/var/lock/nginx.lock ";
	$f[]="--pid-path=/var/run/nginx.pid";
	$f[]="--with-pcre-jit";
	$f[]="--with-debug";
	$f[]="--with-http_addition_module ";
	$f[]="--with-http_dav_module ";
	$f[]="--with-http_geoip_module ";
	$f[]="--with-http_gzip_static_module ";
	$f[]="--with-http_image_filter_module ";
	$f[]="--with-http_realip_module ";
	$f[]="--with-http_stub_status_module ";
	$f[]="--with-http_ssl_module ";
	$f[]="--with-http_sub_module";
	$f[]="--with-http_xslt_module";
	$f[]="--with-ipv6";
	$f[]="--with-mail";
	$f[]="--with-mail_ssl_module";
	$f[]="--with-http_realip_module";
	$f[]="--with-http_addition_module";
	$f[]="--with-http_xslt_module";
	$f[]="--with-http_image_filter_module";
	$f[]="--with-http_geoip_module";
	$f[]="--with-http_sub_module";
	$f[]="--with-http_dav_module";
	$f[]="--with-http_flv_module";
	$f[]="--with-http_gzip_static_module";
	$f[]="--with-http_random_index_module";
	$f[]="--with-http_secure_link_module";
	$f[]="--with-http_degradation_module";
	$f[]="--with-http_stub_status_module";
	
	echo @implode(" ", $f);
	
}

function package_nginx(){
	/*
	 * 
	 * 
	 * http://openresty.org/#Download
cd /root
wget http://openresty.org/download/ngx_openresty-1.2.8.6.tar.gz
tar -xhf ngx_openresty-1.2.8.6.tar.gz
cd ngx_openresty-1.2.7.8
git config --global http.proxy http://192.168.1.245:3140

cd ngx_openresty-1.2.8.6/bundle
git clone git://github.com/yaoweibin/ngx_http_substitutions_filter_module.git
mv ngx_http_substitutions_filter_module ngx_http_substitutions_filter_module-1.0

git clone https://github.com/kvspb/nginx-auth-ldap.git  
mv nginx-auth-ldap ngx_http_auth_ldap_module-1.0

// ************************************************** MARCHE PLUS avec la 1.7.10
cd ngx_openresty-1.2.8.6/bundle
git clone  https://github.com/yaoweibin/nginx_tcp_proxy_module.git 
cd ...
patch -p1 < bundle/nginx_tcp_proxy_module/tcp.patch
// *************************************************************************************




// ************************************************** MARCHE PLUS avec la 1.7.10
git clone https://github.com/yaoweibin/nginx_syslog_patch.git
mv nginx_syslog_patch ngx_nginx_syslog_patch-1.0
cd ngx_nginx_syslog_patch-1.0
patch -p1 < syslog-1.7.0.patch
répondre par : 
/root/ngx_openresty-1.7.2.1/bundle/nginx-1.7.2/src/core/ngx_log.c
/root/ngx_openresty-1.7.2.1/bundle/nginx-1.7.2/src/core/ngx_log.h
/root/ngx_openresty-1.7.2.1/bundle/nginx-1.7.2/src/http/modules/ngx_http_log_module.c
[nginx_syslog_patch=>'ngx_nginx_syslog_patch'],
// *************************************************************************************


 *****
dans configure
[http_substitutions_filter_module=>'ngx_http_substitutions_filter_module'],
[http_auth_ldap_module=>'ngx_http_auth_ldap_module'],
*****
* Inutile de rajouter l'option du module dans la ligne de commande.


	 */
	$base="/root/nginx-compiled";
	shell_exec("/bin/rm -rf $base");
	shell_exec("/bin/rm -rf /etc/nginx/sites-enabled/*");
	$f[]="/usr/share/nginx";
	$f[]="/usr/sbin/nginx";
	$f[]="/etc/nginx";
	$f[]="/var/lib/nginx";
	$f[]="/usr/lib/libxslt.a";
	$f[]="/usr/lib/libxslt.la";
	$f[]="/usr/lib/libxslt.so";
	$f[]="/usr/lib/libxslt.so.1";
	$f[]="/usr/lib/libxslt.so.1.1.26";
	$f[]="/usr/lib32/libxslt.so.1";
	$f[]="/usr/lib32/libxslt.so.1.1.26";
	$f[]="/usr/lib/mod_security2.so";
	$f[]="/usr/lib/mod_security2.la";
	$f[]="/usr/lib/mod_security2.a";
	$f[]="/usr/lib/standalone.so";
	$f[]="/usr/lib/standalone.la";
	$f[]="/usr/lib/standalone.a";

	$Debian=DebianVersion();
	$Architecture=Architecture();
	$version=package_nginx_version();	
	
	foreach ($f as $num=>$filename){
	
		if(is_dir($filename)){
			@mkdir("$base/$filename",0755,true);
			echo "/bin/cp -rf $filename/* $base$filename/\n";
			shell_exec("/bin/cp -rf $filename/* $base$filename/");
			continue;
		}
	
		
		if(is_file($filename)){
			$dirname=dirname($filename);
			if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
			echo "/bin/cp -f $filename $base$dirname/\n";
			shell_exec("/bin/cp -f $filename $base/$dirname/");
		}
	
	}

	chdir($base);
	if(is_file("$base/nginx-$Architecture-$version.tar.gz")){
		@unlink("$base/nginx-$Architecture-$version.tar.gz");
	}
	
	$filename="nginx-debian{$Debian}-$Architecture-$version.tar.gz";
	shell_exec("/bin/rm -rf $base/etc/nginx/sites-enabled/*");
	shell_exec("tar -czf $filename *");
	shell_exec("/bin/cp $filename /root/");
	echo "/root/$filename done\n";	
	
}
function package_nginx_version(){
	
	$unix=new unix();
	$nginx=$unix->find_program("nginx");
	if(!is_file($nginx)){return;}
	$php5=$unix->LOCATE_PHP5_BIN();
	exec("$nginx -V 2>&1",$results);
	
	foreach ($results as $key=>$value){
		if(preg_match("#nginx version: .*?\/([0-9\.]+)#", $value,$re)){return $re[1];}
		if(preg_match("#TLS SNI support enabled#", $value,$re)){$ARRAY["DEF"]["TLS"]=true;continue;}
	}	
}



function ParseLangs_content($filename){
	$title=null;
	$body=null;
$data=@file_get_contents($filename);
$strlen=strlen($data);

if($strlen==0){
	$filename="/root/squid-lang/templates/".basename($filename);
	$data=@file_get_contents("/root/squid-lang/templates/".basename($filename));
	$strlen=strlen($data);
}

if(preg_match("#<title>(.+?)</title>#is", $data,$re)){$title=$re[1];}

if(!preg_match("#<body.*?>(.+?)</body>#is", $data,$re)){
	echo "!!!! BODY IN  $filename\n";
	}
$body=$re[1];
if($title==null){$title=basename($filename);}

if($body==null){echo "!!!! $filename - $strlen bytes\n";}
return array("TITLE"=>$title,"BODY"=>$body);

}

function ParseLangs(){
	
$unix=new unix();
$dirs=$unix->dirdir("/root/squid-lang");
while (list ($dirpath, $value) = each ($dirs) ){
	$lang=basename($dirpath);
	$files=$unix->DirFiles($dirpath);
	while (list ($filename, $value) = each ($files) ){
		if(strpos($filename, ".")>0){continue;}
		$array[$lang][$filename]=ParseLangs_content("$dirpath/$filename");
	}
	
	@file_put_contents("/usr/share/artica-postfix/ressources/databases/squid.default.templates.db", serialize($array));
}




}


function compile_ufdbcat(){
	$unix=new unix();
	$uri="http://www.articatech.net/download/ufdbGuard-1.31.tar.gz";
	$curl=new ccurl("http://www.articatech.net/download/ufdbGuard-1.31.tar.gz");
	echo "Downloading $uri\n";
	$tempdir=$unix->TEMP_DIR()."/ufdb";
	$tempfile="$tempdir/ufdbGuard-1.31.tar.gz";
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	if(is_dir($tempdir)){shell_exec("$rm -rf $tempdir");}
	@mkdir($tempdir,0755,true);
	
	if(is_file($tempfile)){@unlink($tempfile);}
	if(!$curl->GetFile($tempfile)){
		echo "Fatal, unable to download $uri\n";
		squid_admin_mysql(0, "Fatal, unable to download $uri",@implode("\n",$curl->errors),__FILE__,__LINE__);
		return;
	}
	
	if(is_dir("$tempdir/ufdbcompile")){
		shell_exec("$rm -rf $tempdir/ufdbcompile");
	}
	
	echo "Uncompressing $tempdir/ufdbGuard-1.31.tar.gz to $tempdir/\n";
	shell_exec("$tar -xhf $tempdir/ufdbGuard-1.31.tar.gz -C $tempdir/");
	$dirs=$unix->dirdir($tempdir);
	while (list ($directory, $value) = each ($dirs) ){
		echo "Found directory $directory\n";
		if(is_file("$directory/src/mtserver/ufdbguardd.c")){
			$WORKDIR="$directory";
			break;
		}
	}
	
	if(!is_dir($WORKDIR)){
		echo "Fatal, unable to download $uri\n";
		squid_admin_mysql(0, "Fatal, unable to locate working directory",__FILE__,__LINE__);
		if(is_dir($tempdir)){shell_exec("$rm -rf $tempdir");}
		return;
	}
	echo "Patching mtserver/ufdbguardd.c\n";
	$C=explode("\n",@file_get_contents("$directory/src/mtserver/ufdbguardd.c"));
	while (list ($index, $line) = each ($C) ){
		
		if(strpos($line, "/tmp/ufdbguardd-")>0){
			echo "Patching mtserver/ufdbguardd.c line $index\n";
			$C[$index]=str_replace("/tmp/ufdbguardd-", "/var/run/ufdbcat-", $line);
		}
		
	}
	@file_put_contents("$directory/src/mtserver/ufdbguardd.c", @implode("\n", $C));
	
	chdir($WORKDIR);
	if(is_dir("/opt/ufdbcat")){shell_exec("$rm -rf /opt/ufdbcat");}
	
	echo "Configure\n";
	
	$f[]="./configure";
	$f[]="--prefix=/opt/ufdbcat"; 
	$f[]="--includedir=\"\\\${prefix}/include\"";
	$f[]="--mandir=\"\\\${prefix}/share/man\""; 
	$f[]="--infodir=\"\\\${prefix}/share/info\"";
	$f[]="--sysconfdir=/etc/ufdbcat";
	$f[]="--localstatedir=/opt/ufdbcat"; 
	$f[]="--with-ufdb-logdir=/var/log/ufdbcat"; 
	$f[]="--with-ufdb-dbhome=/home/ufdbcat"; 
	$f[]="--with-ufdb-user=root"; 
	$f[]="--with-ufdb-config=/etc/ufdbcat";
	$f[]="--with-ufdb-logdir=/var/log/ufdbcat";
	$f[]="--with-ufdb-config=/etc/ufdbcat";
	$f[]="--with-ufdb-piddir=/var/run/ufdbcat";
	$cmd=@implode(" ", $f);
	system($cmd);
	echo "Make\n";
	system("make");
	echo "Install\n";
	system("make install");
	if(!is_file("/opt/ufdbcat/bin/ufdbguardd")){
		echo "Fatal, unable to compile ufdbcat\n";
		squid_admin_mysql(0, "Fatal, unable to compile ufdbcat",__FILE__,__LINE__);
		if(is_dir($tempdir)){shell_exec("$rm -rf $tempdir");}
		return;
	}
	
	@copy("/opt/ufdbcat/bin/ufdbguardd", "/opt/ufdbcat/bin/ufdbcatdd");
	@unlink("/opt/ufdbcat/bin/ufdbguardd");
	@chmod("/opt/ufdbcat/bin/ufdbcatdd",0755);
	$ufdbcatVersion=ufdbcatVersion();
	$Architecture=Architecture();
	$DebianVersion=DebianVersion();
	$base="/root/ufdbcat-compile";
	if(is_dir($base)){
		shell_exec("$rm -rf $base");
	}
	@mkdir("$base/opt/ufdbcat",0755,true);
	shell_exec("$cp -rfp /opt/ufdbcat/* $base/opt/ufdbcat/");
	$filename="ufdbcat-debian{$DebianVersion}-$Architecture-$ufdbcatVersion.tar.gz";
	chdir($base);
	@unlink("/root/$filename");
	shell_exec("/bin/tar -czf /root/$filename *");
	echo "/root/$filename done\n\n";
	
}

function ufdbcatVersion(){
	exec("/opt/ufdbcat/bin/ufdbcatdd -v 2>&1",$results);
	foreach ($results as $num=>$line){
		if(preg_match("#ufdbguardd.*?([0-9\.]+)#", $line,$re)){return $re[1];}
	}


}