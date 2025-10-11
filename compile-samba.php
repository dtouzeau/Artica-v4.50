<?php
/*
 * configre:
 * ./configure  --with-fhs  --enable-shared  --enable-static  --disable-pie  --prefix=/usr  --sysconfdir=/etc  --libdir=/usr/lib  --with-privatedir=/etc/samba  --with-piddir=/var/run/samba  --localstatedir=/var  --with-rootsbindir=/sbin  --with-pammodulesdir=/lib/security  --with-pam  --with-syslog  --with-utmp  --with-readline  --with-pam_smbpass  --with-libsmbclient  --with-winbind  --with-cluster-support  --with-shared-modules=idmap_rid,idmap_ad  --with-automount  --with-ldap  --with-ads  --with-dnsupdate  --with-smbmount  --with-cifsmount  --with-acl-support  --with-dnsupdate  --with-syslog  --with-quotas  --with-automount
 * 
 * 
apt-get install libtalloc2
mkdir /etc/samba
mkdir /var/log/samba/
mkdir /var/run/samba
touch /etc/printcap
*/
//http://www.samba.org/samba/ftp/stable/samba-3.6.6.tar.gz
// http://samba.org/samba/ftp/stable/samba-3.6.25.tar.gz

// **** XAPIAN ***
/* 
wget http://oligarchy.co.uk/xapian/1.2.16/xapian-core-1.2.16.tar.xz
tar -xhf xapian-core-1.2.16.tar.xz
cd xapian-core-1.2.16
./configure CFLAGS=-O2 CXXFLAGS=-O2 --prefix=/usr --sysconfdir=/etc --disable-dependency-tracking
make && make install
cd /root
wget http://oligarchy.co.uk/xapian/1.2.16/xapian-omega-1.2.16.tar.xz
tar -xhf xapian-omega-1.2.16.tar.xz
cd xapian-omega-1.2.16
./configure CFLAGS=-O2 CXXFLAGS=-O2 --prefix=/usr --sysconfdir=/etc --mandir=/usr/share/man --disable-dependency-tracking
make && make install
cd /root
wget http://oligarchy.co.uk/xapian/1.2.16/xapian-bindings-1.2.16.tar.xz
tar -xhf xapian-bindings-1.2.16.tar.xz
cd xapian-bindings-1.2.16
./configure CFLAGS=-O2 CXXFLAGS=-O2 --prefix=/usr --sysconfdir=/etc --disable-dependency-tracking --with-php PHP_CONFIG=/usr/bin/php-config5
make && make install
cd /root



//samba 4
 * libncurses5-dev libfam-dev
git clone git://git.samba.org/samba.git /usr/src/samba4/
/usr/src/samba4
./configure --with-regedit --download --enable-fhs  --prefix=/usr --sysconfdir=/etc --localstatedir=/var --with-piddir=/var/run/samba --with-privatedir=/etc/samba --libdir=/usr/lib
make 
 */


// ./configure --prefix=/usr  --sysconfdir=/etc  --libdir=/usr/lib  --with-privatedir=/etc/samba  --with-piddir=/var/run/samba  --localstatedir=/var  --with-rootsbindir=/sbin  --with-pammodulesdir=/lib/security  --with-pam  --with-syslog  --with-utmp  --with-readline  --with-pam_smbpass  --with-libsmbclient  --with-winbind  --with-cluster-support  --with-shared-modules=idmap_rid,idmap_ad  --with-automount  --with-ldap  --with-ads  --with-dnsupdate  --with-smbmount  --with-cifsmount  --with-acl-support  --with-dnsupdate  --with-syslog  --with-quotas  --with-automount --enable-shared  --enable-static  --disable-pie --without-ad-dc --without-systemd --disable-cups


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
if($argv[1]=="--parse"){parsepackage($argv[2]);exit;}
if($argv[1]=="--package"){package4();exit;}



$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");

//http://ftp.samba.org/pub/samba/stable/


$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($pid)){die("DIE " .__FILE__." Line: ".__LINE__);}


$dirsrc="samba-0.0.0";
$Architecture=Architecture();

if(!$GLOBALS["NO_COMPILE"]){
	$v="samba-3.6.25.tar.gz";
	if(preg_match("#samba-(.+?)#", $v,$re)){$dirsrc=$re[1];}
	squid_admin_mysql(1, "Downloading lastest file samba-3.6.25.tar.gz, working directory $dirsrc ...",__FUNCTION__,__FILE__,__LINE__);
}

if(!$GLOBALS["FORCE"]){
	if(is_file("/root/samba-3.6.25.tar.gz")){if($GLOBALS["REPOS"]){echo "No updates...\n";die("DIE " .__FILE__." Line: ".__LINE__);}}
}

if(is_dir("/root/samba-builder")){shell_exec("$rm -rf /root/samba-builder");}
chdir("/root");
if(!$GLOBALS["NO_COMPILE"]){
	if(is_dir("/root/$dirsrc")){shell_exec("/bin/rm -rf /root/$dirsrc");}
	@mkdir("/root/$dirsrc");
	if(!is_file("/root/$v")){
		echo "Downloading $v ...\n";
		shell_exec("$wget http://ftp.samba.org/pub/samba/stable/samba-3.6.25.tar.gz");
		if(!is_file("/root/samba-3.6.25.tar.gz")){echo "Downloading failed...\n";die("DIE " .__FILE__." Line: ".__LINE__);}
	}
	
	shell_exec("$tar -xhf /root/$v -C /root/$dirsrc/");
	chdir("/root/$dirsrc");
	if(!is_file("/root/$dirsrc/configure")){
		echo "/root/$dirsrc/configure no such file\n";
		$dirs=$unix->dirdir("/root/$dirsrc");
		while (list ($num, $ligne) = each ($dirs) ){if(!is_file("$ligne/source3/configure")){echo "$ligne/source3/configure no such file\n";}else{
			chdir("$ligne");echo "Change to dir $ligne/source3\n";
			$SOURCE_DIRECTORY=$ligne."/source3";
			$SOURCESOURCE_DIRECTORY=$ligne;
			break;}}
	}
	
}

$SOURCE_DIRECTORY2=dirname($SOURCE_DIRECTORY);
echo "Source directory: $SOURCE_DIRECTORY ($SOURCE_DIRECTORY2)\n";
shell_exec("/usr/share/artica-postfix/bin/artica-make APP_CTDB");

chdir($SOURCE_DIRECTORY);
if(is_file("$SOURCE_DIRECTORY/autogen.sh")){
	echo "Executing autogen.sh\n";
	exec("./autogen.sh",$results);
	foreach ($results as $num=>$ligne){
		echo "autogen.sh::".$ligne."\n";
	}
	
}else{
	echo "$SOURCE_DIRECTORY/autogen.sh no such file\n";
}

$cmds[]='./configure';
$cmds[]=' --with-fhs';
$cmds[]=' --enable-shared';
$cmds[]=' --enable-static';
$cmds[]=' --disable-pie';
$cmds[]=' --prefix=/usr';
$cmds[]=' --sysconfdir=/etc';
$cmds[]=' --libdir=/usr/lib';
$cmds[]=' --with-privatedir=/etc/samba';
$cmds[]=' --with-piddir=/var/run/samba';
$cmds[]=' --localstatedir=/var';
$cmds[]=' --with-rootsbindir=/sbin';
$cmds[]=' --with-pammodulesdir=/lib/security';
$cmds[]=' --with-pam';
$cmds[]=' --with-syslog';
$cmds[]=' --with-utmp';
$cmds[]=' --with-readline';
$cmds[]=' --with-pam_smbpass';
$cmds[]=' --with-libsmbclient';
$cmds[]=' --with-winbind';
if(is_file("/usr/include/ctdb.h")){
	$cmds[]=" --with-cluster-support";
}
$cmds[]=' --with-shared-modules=idmap_rid,idmap_ad';
$cmds[]=' --with-automount';
$cmds[]=' --with-ldap';
$cmds[]=' --with-ads';
$cmds[]=' --with-dnsupdate';
$cmds[]=' --with-smbmount';
$cmds[]=' --with-cifsmount';
$cmds[]=' --with-acl-support';
$cmds[]=' --with-dnsupdate';
$cmds[]=' --with-syslog';
$cmds[]=' --with-quotas';
$cmds[]=' --with-automount'; 





$configure=@implode(" ", $cmds);

if($GLOBALS["SHOW_COMPILE_ONLY"]){echo $configure."\n";die("DIE " .__FILE__." Line: ".__LINE__);}

echo "Executing `$configure`\n";


if(!$GLOBALS["NO_COMPILE"]){
	
	echo "configuring...\n";
	shell_exec($configure);
	echo "make...\n";
	shell_exec("make");
	echo "make install...\n";
	echo "Make install\n";
	shell_exec("make install");
}

create_package();


function SAMBA_VERSION(){
	
	$unix=new unix();
	$winbind=$unix->find_program("winbindd");
	exec("$winbind -V 2>&1",$results);
	if(preg_match("#Version\s+([0-9\.]+)#i", @implode("", $results),$re)){
		return $re[1];
	}
	
	
}

function DebianVersion(){

	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];

}


function parsepackage($filepath){
	
	$f=explode("\n",@file_get_contents($filepath));
	
	foreach ($f as $line){
		
		if(preg_match("#symlink\s+(.*?)\s+\(->\s+(.+?)\)#", $line,$re)){
			$source=$re[1];
			$dest=$re[2];
			echo "SYM -> $source -> $dest\n";
			$dir=dirname($source);
			$filedest="$dir/$dest";
			$directory["/root/samba-builder4{$dir}"]="/root/samba-builder4{$dir}";
			$CMDS[]="cp -fd $filedest /root/samba-builder4{$dir}/";
			continue;
		}
		
		
		if(preg_match("#installing.*?\s+as\s+(.+?)$#", $line,$re)){
			$re[1]=trim($re[1]);
			$dir=dirname($re[1]);
			
			$directory["/root/samba-builder4{$dir}"]="/root/samba-builder4{$dir}";
			
			if(preg_match("#\/bin\/#", $re[1])){
				$CMDS[]="strip -s {$re[1]}";
			}
			$CMDS[]="cp -fd {$re[1]} /root/samba-builder4{$dir}/";
			
			
		}
	}
	
	foreach ($directory as $line){
		
		echo "mkdir('$line',0755,true);\n";
		
	}
	foreach ($CMDS as $line){
	
		echo "shell_exec('$line');\n";
	
	}	
}


function create_package(){
	$Architecture=Architecture();
	$unix=new unix();
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	$DebianVersion=DebianVersion();
	if($DebianVersion==6){$DebianVersion=null;}else{$DebianVersion="-debian{$DebianVersion}";}

	@mkdir('/root/samba-builder/usr/sbin',0755,true);
	@mkdir('/root/samba-builder/usr/bin',0755,true);
	@mkdir('/root/samba-builder/usr/lib/samba',0755,true);
	@mkdir('/root/samba-builder/usr/lib/samba/vfs',0755,true);
	@mkdir('/root/samba-builder/usr/lib/samba/idmap',0755,true);
	@mkdir('/root/samba-builder/usr/lib/samba/charset',0755,true);
	@mkdir('/root/samba-builder/usr/lib/samba/auth',0755,true);
	@mkdir('/root/samba-builder/lib/security',0755,true);
	@mkdir('/root/samba-builder/usr/include',0755,true);
	@mkdir('/root/samba-builder/usr/lib',0755,true);
	@mkdir('/root/samba-builder/lib',0755,true);
	@mkdir('/root/samba-builder/usr/include',0755,true);
	@mkdir('/root/samba-builder/etc/ctdb/events.d',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/de/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/ar/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/cs/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/da/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/es/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/fi/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/fr/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/hu/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/it/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/ja/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/ko/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/nb/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/nl/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/pl/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/pt_BR/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/ru/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/sv/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/zh_CN/LC_MESSAGES',0755,true);
	@mkdir('/root/samba-builder/usr/share/locale/zh_TW/LC_MESSAGES',0755,true);	
	@mkdir('/root/samba-builder/usr/bin',0755,true);
	@mkdir('/root/samba-builder/usr/lib',0755,true);
	@mkdir('/root/samba-builder/usr/lib/php5/20090626+lfs',0755,true);
	@mkdir('/root/samba-builder/usr/lib/xapian-omega',0755,true);
	@mkdir('/root/samba-builder/usr/share/omega',0755,true);
	@mkdir('/root/samba-builder/usr/include/xapian',0755,true);	
	$f[]="/usr/sbin/smbd";
	$f[]="/usr/sbin/nmbd";
	$f[]="/usr/sbin/swat";
	$f[]="/usr/sbin/winbindd";
	$f[]="/usr/sbin/msktutil";
	$f[]="/usr/bin/wbinfo";
	$f[]="/usr/bin/smbclient";
	$f[]="/usr/bin/net";
	$f[]="/usr/bin/smbspool";
	$f[]="/usr/bin/testparm";
	$f[]="/usr/bin/smbstatus";
	$f[]="/usr/bin/smbget";
	$f[]="/usr/bin/smbta-util";
	$f[]="/usr/bin/smbcontrol";
	$f[]="/usr/bin/smbtree";
	$f[]="/usr/bin/tdbbackup";
	$f[]="/usr/bin/nmblookup";
	$f[]="/usr/bin/pdbedit";
	$f[]="/usr/bin/tdbdump";
	$f[]="/usr/bin/tdbrestore";
	$f[]="/usr/bin/tdbtool";
	$f[]="/usr/bin/smbpasswd";
	$f[]="/usr/bin/rpcclient";
	$f[]="/usr/bin/smbcacls";
	$f[]="/usr/bin/profiles";
	$f[]="/usr/bin/ntlm_auth";
	$f[]="/usr/bin/sharesec";
	$f[]="/usr/bin/smbcquotas";
	$f[]="/usr/bin/eventlogadm";
	$f[]="/usr/lib/samba/lowcase.dat";
	$f[]="/usr/lib/samba/upcase.dat";
	$f[]="/usr/lib/samba/valid.dat";
	$f[]="/usr/lib/samba/vfs/recycle.so";
	$f[]="/usr/lib/samba/vfs/audit.so";
	$f[]="/usr/lib/samba/vfs/extd_audit.so";
	$f[]="/usr/lib/samba/vfs/full_audit.so";
	$f[]="/usr/lib/samba/vfs/netatalk.so";
	$f[]="/usr/lib/samba/vfs/fake_perms.so";
	$f[]="/usr/lib/samba/vfs/default_quota.so";
	$f[]="/usr/lib/samba/vfs/readonly.so";
	$f[]="/usr/lib/samba/vfs/cap.so";
	$f[]="/usr/lib/samba/vfs/expand_msdfs.so";
	$f[]="/usr/lib/samba/vfs/shadow_copy.so";
	$f[]="/usr/lib/samba/vfs/shadow_copy2.so";
	$f[]="/usr/lib/samba/vfs/xattr_tdb.so";
	$f[]="/usr/lib/samba/vfs/catia.so";
	$f[]="/usr/lib/samba/vfs/streams_xattr.so";
	$f[]="/usr/lib/samba/vfs/streams_depot.so";
	$f[]="/usr/lib/samba/vfs/readahead.so";
	$f[]="/usr/lib/samba/vfs/fileid.so";
	$f[]="/usr/lib/samba/vfs/preopen.so";
	$f[]="/usr/lib/samba/vfs/syncops.so";
	$f[]="/usr/lib/samba/vfs/acl_xattr.so";
	$f[]="/usr/lib/samba/vfs/acl_tdb.so";
	$f[]="/usr/lib/samba/vfs/smb_traffic_analyzer.so";
	$f[]="/usr/lib/samba/vfs/dirsort.so";
	$f[]="/usr/lib/samba/vfs/scannedonly.so";
	$f[]="/usr/lib/samba/vfs/crossrename.so";
	$f[]="/usr/lib/samba/vfs/linux_xfs_sgid.so";
	$f[]="/usr/lib/samba/vfs/time_audit.so";
	$f[]="/usr/lib/samba/idmap/rid.so";
	$f[]="/usr/lib/samba/idmap/autorid.so";
	$f[]="/usr/lib/samba/idmap/ad.so";
	$f[]="/usr/lib/samba/charset/CP850.so";
	$f[]="/usr/lib/samba/charset/CP437.so";
	$f[]="/usr/lib/samba/auth/script.so";
	$f[]="/usr/lib/samba/de.msg";
	$f[]="/usr/lib/samba/en.msg";
	$f[]="/usr/lib/samba/fi.msg";
	$f[]="/usr/lib/samba/fr.msg";
	$f[]="/usr/lib/samba/it.msg";
	$f[]="/usr/lib/samba/ja.msg";
	$f[]="/usr/lib/samba/nl.msg";
	$f[]="/usr/lib/samba/pl.msg";
	$f[]="/usr/lib/samba/ru.msg";
	$f[]="/usr/lib/samba/tr.msg";
	$f[]="/lib/security/pam_smbpass.so";
	$f[]="/lib/security/pam_winbind.so";
	$f[]="/usr/lib/libtalloc.so.2.0.5";
	$f[]="/usr/lib/libtalloc.a";
	$f[]="/usr/include/talloc.h";
	$f[]="/usr/lib/libtdb.so.1.2.9";
	$f[]="/usr/lib/libtdb.a";
	$f[]="/usr/include/tdb.h";
	$f[]="/usr/lib/libwbclient.so.0";
	$f[]="/usr/lib/libwbclient.a";
	$f[]="/usr/include/wbclient.h";
	$f[]="/usr/lib/libnetapi.so.0";
	$f[]="/usr/lib/libnetapi.a";
	$f[]="/usr/include/netapi.h";
	$f[]="/usr/lib/libsmbclient.so.0";
	$f[]="/usr/lib/libsmbclient.a";
	$f[]="/usr/include/libsmbclient.h";
	$f[]="/usr/lib/libsmbsharemodes.so.0";
	$f[]="/usr/lib/libsmbsharemodes.a";
	$f[]="/usr/include/smb_share_modes.h";
	$f[]="/usr/share/locale/de/LC_MESSAGES/net.mo";
	$f[]="/usr/share/locale/ar/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/cs/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/da/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/de/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/es/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/fi/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/fr/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/hu/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/it/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/ja/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/ko/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/nb/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/nl/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/pl/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/pt_BR/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/ru/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/sv/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/zh_CN/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/zh_TW/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/lib/libnetapi.a";
	$f[]="/usr/lib/libnetapi.so.0";
	$f[]="/usr/lib/libsmbclient.a";
	$f[]="/usr/lib/libsmbclient.so.0";
	$f[]="/usr/lib/libsmbsharemodes.a";
	$f[]="/usr/lib/libsmbsharemodes.so.0";
	$f[]="/usr/lib/libtalloc.a";
	$f[]="/usr/lib/libtalloc.so.2.0.5";
	$f[]="/usr/lib/libtalloc.so.2";
	$f[]="/usr/lib/libtdb.a";
	$f[]="/usr/lib/libtdb.so.1.2.9";
	$f[]="/usr/lib/libtdb.so.1";
	$f[]="/usr/lib/libcups.so.2";
	$f[]="/usr/lib/libavahi-client.so.3";
	$f[]="/usr/lib/libavahi-client.so.3.2.7";
	$f[]="/usr/lib/libwbclient.so.0";
	$f[]="/lib/libnss_winbind.so";
	$f[]="/lib/libnss_wins.so";
	$f[]="/usr/bin/ctdb";
	$f[]="/usr/bin/smnotify";
	$f[]="/usr/bin/ping_pong";
	$f[]="/usr/bin/ctdb_diagnostics";
	$f[]="/usr/bin/onnode";
	$f[]="/usr/include/ctdb.h";
	$f[]="/usr/include/ctdb_private.h";
	$f[]="/usr/sbin/ctdbd";	
	$f[]="/usr/share/locale/cs/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/da/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/de/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/eo/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/es/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/fi/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/fr/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/ga/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/gl/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/hu/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/id/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/is/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/it/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/ja/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/ko/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/lv/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/nb/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/nl/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/pl/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/pt/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/ro/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/ru/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/sk/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/sl/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/sv/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/th/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/tr/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/uk/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/vi/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/wa/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/zh_TW/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/zh_CN/LC_MESSAGES/popt.mo";
	$f[]="/usr/lib/libpopt.la";
	$f[]="/usr/lib/libpopt.so.0.0.0";
	$f[]="/usr/lib/libpopt.so.0";
	$f[]="/usr/lib/libpopt.so";
	$f[]="/usr/include/popt.h";
	$f[]="/usr/lib/libxapian.la";
	$f[]="/usr/lib/libxapian.so";
	$f[]="/usr/lib/libxapian.a";
	$f[]="/usr/lib/libxapian.so.22.5.0 ";
	$f[]="/usr/lib/libxapian.so.22";
	$f[]="/usr/lib/libxapian.so.22.6.3";
	$f[]="/usr/lib/xapian-omega/bin/omega";
	$f[]="/usr/bin/quartzcheck";
	$f[]="/usr/bin/quartzcheck";
	$f[]="/usr/bin/quartzcompact";
	$f[]="/usr/bin/quartzcompact";
	$f[]="/usr/bin/quartzdump";
	$f[]="/usr/bin/xapian-check";
	$f[]="/usr/bin/xapian-compact";
	$f[]="/usr/bin/xapian-inspect";
	$f[]="/usr/bin/xapian-progsrv";
	$f[]="/usr/bin/xapian-tcpsrv";
	$f[]="/usr/bin/copydatabase";
	$f[]="/usr/bin/delve";
	$f[]="/usr/bin/quest";
	$f[]="/usr/bin/simpleexpand";
	$f[]="/usr/bin/simpleindex";
	$f[]="/usr/bin/simplesearch";
	$f[]="/usr/bin/xapian-config";
	$f[]="/usr/include/xapian.h";
	$f[]="/usr/share/php5/xapian.php";
	$f[]="/usr/lib/php5/20090626+lfs/xapian.so";
	$f[]="/usr/lib/php5/20090626/xapian.so";
	$f[]="/usr/lib/php5/20090626+lfs/xapian.la";
	$f[]="/usr/lib/php5/20090626/xapian.la";
	$f[]="/usr/bin/xapian-check";
	$f[]="/usr/bin/xapian-compact";
	$f[]="/usr/bin/xapian-inspect";
	$f[]="/usr/bin/xapian-replicate";
	$f[]="/usr/bin/xapian-replicate-server";
	$f[]="/usr/bin/xapian-chert-update";
	$f[]="/usr/bin/xapian-progsrv";
	$f[]="/usr/bin/xapian-tcpsrv";
	$f[]="/usr/bin/dbi2omega ";
	$f[]="/usr/bin/htdig2omega ";
	$f[]="/usr/bin/mbox2omega";
	$f[]="/usr/bin/omindex";
	$f[]="/usr/bin/scriptindex";	
	$f[]="/usr/bindbi2omega";
	$f[]="/usr/binhtdig2omega"; 
	$f[]="/usr/binmbox2omega";
	
	foreach ($f as $num=>$ligne){
		if(!is_file($ligne)){echo "$ligne no such file\n";continue;}
		$dir=dirname($ligne);
		echo "Installing $ligne in /root/samba-builder$dir/\n";
		if(!is_dir("/root/samba-builder$dir")){@mkdir("/root/samba-builder$dir",0755,true);}
		shell_exec("/bin/cp -fd $ligne /root/samba-builder$dir/");
		
	}
	
	shell_exec("/bin/cp -rfd /usr/lib/samba/* /root/samba-builder/usr/lib/samba/");
	shell_exec("/bin/cp -rfd /etc/ctdb/* /root/samba-builder/etc/ctdb/");
	shell_exec("/bin/cp -rfd /usr/lib/xapian-omega/* /root/samba-builder/usr/lib/xapian-omega/");
	shell_exec("/bin/cp -rfd /usr/share/omega/* /root/samba-builder/usr/share/omega/");	

	echo "Creating package done....\n";
	
	if(is_dir("/root/3/samba-3.6.25")){$SOURCESOURCE_DIRECTORY="/root/3/samba-3.6.25";}
	

	if(is_file("$SOURCESOURCE_DIRECTORY/nsswitch/libnss_wins.so")){
		echo "Copy $SOURCESOURCE_DIRECTORY/nsswitch/libnss_wins.so\n";
		@copy("$SOURCESOURCE_DIRECTORY/nsswitch/libnss_wins.so", "/lib/libnss_wins.so");
	
	}
	if(is_file("$SOURCESOURCE_DIRECTORY/nsswitch/libnss_winbind.so")){
	echo "Copy $SOURCESOURCE_DIRECTORY/nsswitch/libnss_winbind.so\n";
	@copy("$SOURCESOURCE_DIRECTORY/nsswitch/libnss_winbind.so", "/lib/libnss_winbind.so");
	
	}
	
	
	if($Architecture==64){$Architecture="x64";}
	if($Architecture==32){$Architecture="i386";}
	
	
	
	@mkdir("/root/samba-builder/etc/init.d",0755,true);
			if(is_file("$SOURCESOURCE_DIRECTORY/packaging/LSB/samba.sh")){
			shell_exec("/bin/cp $SOURCESOURCE_DIRECTORY/packaging/LSB/samba.sh /root/samba-builder/etc/init.d/samba");
			@chmod("/root/samba-builder/etc/init.d/samba", 0755);
	}else{
		echo "$SOURCESOURCE_DIRECTORY/packaging/LSB/samba.sh no such file";
	}
	$version=SAMBA_VERSION();
	echo "Building package Arch:$Architecture Version:$version  $DebianVersion\n";
	
	@chdir("/root/samba-builder");
	if(is_file("/root/samba-builder/sambac$DebianVersion-$Architecture-$version.tar.gz")){@unlink("/root/samba-builder/sambac-$Architecture-$version.tar.gz");}
	echo "Compressing sambac$DebianVersion-$Architecture-$version.tar.gz\n";
	shell_exec("$tar -czf sambac$DebianVersion-$Architecture-$version.tar.gz *");
	echo "Compressing /root/samba-builder/sambac$DebianVersion-$Architecture-$version.tar.gz Done...\n";
	if(is_file("/root/ftp-password")){
	echo "Uploading /root/samba-builder/sambac$DebianVersion-$Architecture-$version.tar.gz Done...\n";
	echo "/root/samba-builder/sambac-$Architecture$DebianVersion-$version.tar.gz is now ready to be uploaded\n";
	if($DebianVersion==null){
		shell_exec("curl -T /root/samba-builder/sambac$DebianVersion-$Architecture-$version.tar.gz ftp://www.articatech.net/download/ --user ".@file_get_contents("/root/ftp-password"));
		if(is_file("/root/rebuild-artica")){shell_exec("$wget \"".@file_get_contents("/root/rebuild-artica")."\" -O /tmp/rebuild.html");}
	}
	
	
	}	
	
	
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
				if($ri[1]>3){continue;}
				if(strlen($ri[2])==1){$ri[2]="0{$ri[2]}";}
				if(strlen($ri[3])==1){$ri[3]="0{$ri[3]}";}
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


function package4(){
	mkdir('/root/samba-builder4/usr/lib/samba',0755,true);
	mkdir('/root/samba-builder4/usr/lib/pkgconfig',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party',0755,true);
	mkdir('/root/samba-builder4/usr/bin',0755,true);
	mkdir('/root/samba-builder4/usr/share/perl5/Parse',0755,true);
	mkdir('/root/samba-builder4/usr/share/perl5/Parse/Pidl',0755,true);
	mkdir('/root/samba-builder4/usr/share/perl5/Parse/Pidl/Wireshark',0755,true);
	mkdir('/root/samba-builder4/usr/share/perl5/Parse/Pidl/Samba4',0755,true);
	mkdir('/root/samba-builder4/usr/share/perl5/Parse/Pidl/Samba4/COM',0755,true);
	mkdir('/root/samba-builder4/usr/share/perl5/Parse/Pidl/Samba4/NDR',0755,true);
	mkdir('/root/samba-builder4/usr/share/perl5/Parse/Pidl/Samba3',0755,true);
	mkdir('/root/samba-builder4/usr/share/perl5/Parse/Yapp',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/kcc',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/provision',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/samba3',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/subunit',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/blackbox',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dcerpc',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dns_forwarder_helpers',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/kcc',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/samba_tool',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/web_server',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc',0755,true);
	mkdir('/root/samba-builder4/usr/include/samba-4.0',0755,true);
	mkdir('/root/samba-builder4/usr/include/samba-4.0/samba',0755,true);
	mkdir('/root/samba-builder4/usr/include/samba-4.0/gen_ndr',0755,true);
	mkdir('/root/samba-builder4/usr/include/samba-4.0/util',0755,true);
	mkdir('/root/samba-builder4/usr/include/samba-4.0/ndr',0755,true);
	mkdir('/root/samba-builder4/usr/include/samba-4.0/core',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/iso8601',0755,true);
	mkdir('/root/samba-builder4/usr/lib',0755,true);
	mkdir('/root/samba-builder4/usr/lib/samba/ldb',0755,true);
	mkdir('/root/samba-builder4/lib/security',0755,true);
	mkdir('/root/samba-builder4/usr/sbin',0755,true);
	mkdir('/root/samba-builder4/usr/lib/samba/auth',0755,true);
	mkdir('/root/samba-builder4/usr/lib/samba/vfs',0755,true);
	mkdir('/root/samba-builder4/usr/lib/samba/idmap',0755,true);
	mkdir('/root/samba-builder4/usr/lib/samba/nss_info',0755,true);
	mkdir('/root/samba-builder4/usr/share/man/man1',0755,true);
	mkdir('/root/samba-builder4/usr/share/man/man3',0755,true);
	
	
	shell_exec('cp -fd /usr/lib/samba/libreplace-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/pkgconfig/samba-hostconfig.pc /root/samba-builder4/usr/lib/pkgconfig/');
	shell_exec('cp -fd /usr/lib/pkgconfig/dcerpc_samr.pc /root/samba-builder4/usr/lib/pkgconfig/');
	shell_exec('cp -fd /usr/lib/pkgconfig/dcerpc.pc /root/samba-builder4/usr/lib/pkgconfig/');
	shell_exec('cp -fd /usr/lib/pkgconfig/samdb.pc /root/samba-builder4/usr/lib/pkgconfig/');
	shell_exec('cp -fd /usr/lib/pkgconfig/samba-credentials.pc /root/samba-builder4/usr/lib/pkgconfig/');
	shell_exec('cp -fd /usr/lib/pkgconfig/wbclient.pc /root/samba-builder4/usr/lib/pkgconfig/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/');
	shell_exec('cp -fd /usr/lib/pkgconfig/samba-util.pc /root/samba-builder4/usr/lib/pkgconfig/');
	shell_exec('cp -fd /usr/lib/pkgconfig/ndr_krb5pac.pc /root/samba-builder4/usr/lib/pkgconfig/');
	shell_exec('cp -fd /usr/lib/pkgconfig/ndr_standard.pc /root/samba-builder4/usr/lib/pkgconfig/');
	shell_exec('cp -fd /usr/lib/pkgconfig/ndr_nbt.pc /root/samba-builder4/usr/lib/pkgconfig/');
	shell_exec('cp -fd /usr/lib/pkgconfig/ndr.pc /root/samba-builder4/usr/lib/pkgconfig/');
	shell_exec('cp -fd /usr/lib/pkgconfig/samba-policy.pc /root/samba-builder4/usr/lib/pkgconfig/');
	shell_exec('strip -s /usr/bin/pidl');
	shell_exec('cp -fd /usr/bin/pidl /root/samba-builder4/usr/bin/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl.pm /root/samba-builder4/usr/share/perl5/Parse/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Samba4.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/CUtil.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Expr.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Wireshark/Conformance.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/Wireshark/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Wireshark/NDR.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/Wireshark/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/ODL.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Dump.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Util.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Samba4/Header.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/Samba4/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Samba4/COM/Header.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/Samba4/COM/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Samba4/COM/Proxy.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/Samba4/COM/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Samba4/COM/Stub.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/Samba4/COM/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Samba4/TDR.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/Samba4/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Samba4/NDR/Server.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/Samba4/NDR/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Samba4/NDR/Client.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/Samba4/NDR/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Samba4/NDR/Parser.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/Samba4/NDR/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Samba4/Python.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/Samba4/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Samba4/Template.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/Samba4/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/IDL.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Typelist.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Samba3/ClientNDR.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/Samba3/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Samba3/ServerNDR.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/Samba3/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/Compat.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Pidl/NDR.pm /root/samba-builder4/usr/share/perl5/Parse/Pidl/');
	shell_exec('cp -fd /usr/share/perl5/Parse/Yapp/Driver.pm /root/samba-builder4/usr/share/perl5/Parse/Yapp/');
	shell_exec('cp -fd /usr/lib/pkgconfig/netapi.pc /root/samba-builder4/usr/lib/pkgconfig/');
	shell_exec('cp -fd /usr/lib/pkgconfig/smbclient.pc /root/samba-builder4/usr/lib/pkgconfig/');
	shell_exec('strip -s /usr/bin/findsmb');
	shell_exec('cp -fd /usr/bin/findsmb /root/samba-builder4/usr/bin/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/_tdb_text.py /root/samba-builder4/usr/lib/python2.7/dist-packages/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/_ldb_text.py /root/samba-builder4/usr/lib/python2.7/dist-packages/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/common.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dbchecker.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/descriptor.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/drs_utils.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/getopt.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/hostconfig.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/idmap.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/join.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/kcc/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/kcc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/kcc/debug.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/kcc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/kcc/graph.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/kcc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/kcc/graph_utils.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/kcc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/kcc/kcc_utils.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/kcc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/kcc/ldif_import_export.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/kcc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/ms_display_specifiers.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/ms_schema.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/ndr.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/common.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/dbcheck.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/delegation.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/dns.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/domain.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/drs.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/dsacl.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/fsmo.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/gpo.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/group.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/ldapcmp.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/main.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/nettime.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/ntacl.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/processes.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/rodc.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/sites.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/spn.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/testparm.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netcmd/user.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/netcmd/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/ntacls.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/provision/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/provision/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/provision/backend.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/provision/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/provision/common.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/provision/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/provision/sambadns.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/provision/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/remove_dc.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/samba3/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/samba3/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/samdb.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/schema.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/sd_utils.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/sites.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/subnets.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/subunit/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/subunit/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/subunit/run.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/subunit/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tdb_util.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/auth.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/blackbox/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/blackbox/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/blackbox/ndrdump.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/blackbox/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/blackbox/samba_dnsupdate.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/blackbox/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/blackbox/samba_tool_drs.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/blackbox/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/common.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/core.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/credentials.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dcerpc/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dcerpc/array.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dcerpc/bare.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dcerpc/dnsserver.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dcerpc/integer.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dcerpc/misc.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dcerpc/raw_protocol.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dcerpc/registry.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dcerpc/rpc_talloc.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dcerpc/rpcecho.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dcerpc/sam.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dcerpc/srvsvc.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dcerpc/string.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dcerpc/testrpc.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dcerpc/unix.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dns.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dns_forwarder.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dns_forwarder_helpers/server.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/dns_forwarder_helpers/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dns_tkey.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/docs.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/dsdb.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/gensec.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/get_opt.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/hostconfig.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/kcc/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/kcc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/kcc/graph.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/kcc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/kcc/graph_utils.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/kcc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/kcc/kcc_utils.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/kcc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/kcc/ldif_import_export.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/kcc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/libsmb_samba_internal.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/messaging.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/netcmd.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/ntacls.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/param.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/policy.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/posixacl.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/provision.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/registry.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/samba3.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/samba3sam.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/samba_tool/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/samba_tool/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/samba_tool/base.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/samba_tool/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/samba_tool/fsmo.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/samba_tool/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/samba_tool/gpo.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/samba_tool/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/samba_tool/group.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/samba_tool/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/samba_tool/join.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/samba_tool/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/samba_tool/ntacl.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/samba_tool/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/samba_tool/processes.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/samba_tool/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/samba_tool/rodc.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/samba_tool/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/samba_tool/sites.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/samba_tool/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/samba_tool/timecmd.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/samba_tool/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/samba_tool/user.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/samba_tool/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/samba_tool/user_check_password_script.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/samba_tool/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/samdb.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/security.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/source.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/strings.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/subunitrun.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/unicodenames.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/upgrade.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/upgradeprovision.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/upgradeprovisionneeddc.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/tests/xattr.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/tests/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/upgrade.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/upgradehelpers.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/web_server/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/web_server/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/xattr.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/include/samba-4.0/param.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/samba/version.h /root/samba-builder4/usr/include/samba-4.0/samba/');
	shell_exec('cp -fd /usr/include/samba-4.0/charset.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/share.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/ndr_samr_c.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/dcerpc.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/samba/session.h /root/samba-builder4/usr/include/samba-4.0/samba/');
	shell_exec('cp -fd /usr/include/samba-4.0/credentials.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/wbclient.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/ldb_wrap.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/debug.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/attr.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/byteorder.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/data_blob.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/memory.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/safe_string.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/time.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/talloc_stack.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/xfile.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/string_wrappers.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/idtree.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/idtree_random.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/blocking.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/signal.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/substitute.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/fault.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/genrand.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/tevent_ntstatus.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/tevent_unix.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util/tevent_werror.h /root/samba-builder4/usr/include/samba-4.0/util/');
	shell_exec('cp -fd /usr/include/samba-4.0/util_ldb.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/tdr.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/tsocket.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/tsocket_internal.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/auth.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/server_id.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/security.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/ndr_dcerpc.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/dcerpc.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/ndr/ndr_dcerpc.h /root/samba-builder4/usr/include/samba-4.0/ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/ndr_drsuapi.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/drsuapi.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/ndr/ndr_drsuapi.h /root/samba-builder4/usr/include/samba-4.0/ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/ndr_drsblobs.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/drsblobs.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/ndr/ndr_drsblobs.h /root/samba-builder4/usr/include/samba-4.0/ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/krb5pac.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/ndr_krb5pac.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/ndr/ndr_krb5pac.h /root/samba-builder4/usr/include/samba-4.0/ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/samr.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/ndr_samr.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/lsa.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/netlogon.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/atsvc.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/ndr_atsvc.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/ndr_svcctl.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/svcctl.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/smb2_lease_struct.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/nbt.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/ndr_nbt.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/ndr/ndr_nbt.h /root/samba-builder4/usr/include/samba-4.0/ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/ndr_svcctl_c.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/ndr/ndr_svcctl.h /root/samba-builder4/usr/include/samba-4.0/ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/misc.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/gen_ndr/ndr_misc.h /root/samba-builder4/usr/include/samba-4.0/gen_ndr/');
	shell_exec('cp -fd /usr/include/samba-4.0/ndr.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/rpc_common.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/core/error.h /root/samba-builder4/usr/include/samba-4.0/core/');
	shell_exec('cp -fd /usr/include/samba-4.0/core/ntstatus.h /root/samba-builder4/usr/include/samba-4.0/core/');
	shell_exec('cp -fd /usr/include/samba-4.0/core/doserr.h /root/samba-builder4/usr/include/samba-4.0/core/');
	shell_exec('cp -fd /usr/include/samba-4.0/core/werror.h /root/samba-builder4/usr/include/samba-4.0/core/');
	shell_exec('cp -fd /usr/include/samba-4.0/core/hresult.h /root/samba-builder4/usr/include/samba-4.0/core/');
	shell_exec('cp -fd /usr/include/samba-4.0/domain_credentials.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/policy.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/netapi.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/passdb.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/machine_sid.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/lookup_sid.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/smbldap.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/smb_ldap.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/smbconf.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/include/samba-4.0/libsmbclient.h /root/samba-builder4/usr/include/samba-4.0/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/tevent.py /root/samba-builder4/usr/lib/python2.7/dist-packages/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/iso8601/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/iso8601/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/iso8601/iso8601.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/iso8601/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/iso8601/test_iso8601.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/iso8601/');
	shell_exec('strip -s /usr/bin/smbtar');
	shell_exec('cp -fd /usr/bin/smbtar /root/samba-builder4/usr/bin/');
	shell_exec('cp -fd /usr/lib/samba/libinterfaces-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/libsamba-util.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsamba-util.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsamba-util.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/samba/libtime-basic-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libutil-setid-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libsamba-debug-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libtalloc.so.2.1.8 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libtalloc.so.2.1.8 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libsocket-blocking-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libgenrand-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libsys-rw-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libiov-buf-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libtevent.so.0.9.29 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libtevent.so.0.9.29 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libpytalloc-util.so.2.1.8 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libpytalloc-util.so.2.1.8 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/talloc.so /root/samba-builder4/usr/lib/python2.7/dist-packages/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/_tevent.so /root/samba-builder4/usr/lib/python2.7/dist-packages/');
	shell_exec('cp -fd /usr/lib/samba/libaddns-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/libndr.so.0.0.8 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libndr.so.0.0.8 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libndr.so.0.0.8 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsamba-errors.so.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsamba-errors.so.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/samba/libgssapi-samba4.so.2.0.0 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libgssapi-samba4.so.2.0.0 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libkrb5-samba4.so.26.0.0 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libkrb5-samba4.so.26.0.0 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libheimbase-samba4.so.1.0.0 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libheimbase-samba4.so.1.0.0 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libroken-samba4.so.19.0.1 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libroken-samba4.so.19.0.1 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libcom_err-samba4.so.0.25 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libcom_err-samba4.so.0.25 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libasn1-samba4.so.8.0.0 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libasn1-samba4.so.8.0.0 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libhx509-samba4.so.5.0.0 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libhx509-samba4.so.5.0.0 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libhcrypto-samba4.so.5.0.1 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libhcrypto-samba4.so.5.0.1 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libwind-samba4.so.0.0.0 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libwind-samba4.so.0.0.0 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libtdb.so.1.3.10 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libtdb.so.1.3.10 /root/samba-builder4/usr/lib/samba/');
	shell_exec('strip -s /usr/bin/tdbrestore');
	shell_exec('cp -fd /usr/bin/tdbrestore /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/tdbdump');
	shell_exec('cp -fd /usr/bin/tdbdump /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/tdbbackup');
	shell_exec('cp -fd /usr/bin/tdbbackup /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/tdbtool');
	shell_exec('cp -fd /usr/bin/tdbtool /root/samba-builder4/usr/bin/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/tdb.so /root/samba-builder4/usr/lib/python2.7/dist-packages/');
	shell_exec('cp -fd /usr/lib/samba/libpyldb-util.so.1.1.27 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libpyldb-util.so.1.1.27 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libldb.so.1.1.27 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libldb.so.1.1.27 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/ldb.so /root/samba-builder4/usr/lib/python2.7/dist-packages/');
	shell_exec('cp -fd /usr/lib/samba/ldb/paged_results.so /root/samba-builder4/usr/lib/samba/ldb/');
	shell_exec('cp -fd /usr/lib/samba/ldb/asq.so /root/samba-builder4/usr/lib/samba/ldb/');
	shell_exec('cp -fd /usr/lib/samba/ldb/server_sort.so /root/samba-builder4/usr/lib/samba/ldb/');
	shell_exec('cp -fd /usr/lib/samba/ldb/paged_searches.so /root/samba-builder4/usr/lib/samba/ldb/');
	shell_exec('cp -fd /usr/lib/samba/ldb/rdn_name.so /root/samba-builder4/usr/lib/samba/ldb/');
	shell_exec('cp -fd /usr/lib/samba/ldb/sample.so /root/samba-builder4/usr/lib/samba/ldb/');
	shell_exec('cp -fd /usr/lib/samba/ldb/skel.so /root/samba-builder4/usr/lib/samba/ldb/');
	shell_exec('cp -fd /usr/lib/samba/ldb/tdb.so /root/samba-builder4/usr/lib/samba/ldb/');
	shell_exec('strip -s /usr/bin/ldbadd');
	shell_exec('cp -fd /usr/bin/ldbadd /root/samba-builder4/usr/bin/');
	shell_exec('cp -fd /usr/lib/samba/libldb-cmdline-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('strip -s /usr/bin/ldbsearch');
	shell_exec('cp -fd /usr/bin/ldbsearch /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/ldbdel');
	shell_exec('cp -fd /usr/bin/ldbdel /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/ldbmodify');
	shell_exec('cp -fd /usr/bin/ldbmodify /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/ldbedit');
	shell_exec('cp -fd /usr/bin/ldbedit /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/ldbrename');
	shell_exec('cp -fd /usr/bin/ldbrename /root/samba-builder4/usr/bin/');
	shell_exec('cp -fd /usr/lib/samba/libserver-role-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/libsamba-hostconfig.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsamba-hostconfig.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsamba-hostconfig.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/samba/libsamba-python-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libMESSAGING-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libndr-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libndr-samba-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/libndr-standard.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libndr-standard.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libndr-standard.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libndr-nbt.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libndr-nbt.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libndr-nbt.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/samba/libsamba-security-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/libndr-krb5pac.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libndr-krb5pac.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libndr-krb5pac.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/samba/libasn1util-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libmessages-util-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libtalloc-report-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/libdcerpc.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libdcerpc.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libdcerpc.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/samba/libcli-nbt-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/libtevent-util.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libtevent-util.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libtevent-util.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/samba/libsamba-sockets-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libevents-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libsmbclient-raw-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/libsamba-credentials.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsamba-credentials.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsamba-credentials.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/samba/libsamdb-common-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libflag-mapping-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libcli-ldap-common-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libcliauth-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libutil-tdb-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libkrb5samba-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libdbwrap-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libtdb-wrap-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libauthkrb5-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libauth-sam-reply-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libldbsamba-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libcli-smb-common-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libsmb-transport-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libgensec-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/libwbclient.so.0.13 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libwbclient.so.0.13 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libwbclient.so.0.13 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/samba/libwinbind-client-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libsamba-modules-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/libsamdb.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsamdb.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsamdb.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libdcerpc-binding.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libdcerpc-binding.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libdcerpc-binding.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/samba/libhttp-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libmessages-dgm-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libmsghdr-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libserver-id-db-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/libsmbconf.so.0 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsmbconf.so.0 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/samba/libCHARSET3-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libsamba3-util-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libsmbregistry-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libutil-reg-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libsmbd-shim-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libsamba-cluster-support-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libcli-cldap-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libcli-ldap-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libnetif-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libdcerpc-samba-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libcluster-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/_glue.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/param.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/samba/libshares-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('strip -s /usr/bin/ndrdump');
	shell_exec('cp -fd /usr/bin/ndrdump /root/samba-builder4/usr/bin/');
	shell_exec('cp -fd /usr/lib/samba/libdcerpc-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/libdcerpc-samr.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libdcerpc-samr.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libdcerpc-samr.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/base.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/srvsvc.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/echo.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/dns.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/auth.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/krb5pac.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/winreg.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/misc.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/initshutdown.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/epmapper.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/mgmt.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/atsvc.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/nbt.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/samr.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/svcctl.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/lsa.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/wkssvc.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/dfs.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/dcerpc.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/unixinfo.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/irpc.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/server_id.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/winbind.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/netlogon.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/idmap.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/drsuapi.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/security.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/drsblobs.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/dnsp.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/xattr.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/idmap.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/dnsserver.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dcerpc/smb_acl.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/dcerpc/');
	shell_exec('cp -fd /usr/lib/samba/libdsdb-module-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libdsdb-garbage-collect-tombstones-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dsdb.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/samba/libsamba-net-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libsmbpasswdparser-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/net.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/gensec.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/samba/libauth4-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libLIBWBCLIENT-OLD-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libauth-unix-token-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/auth.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/credentials.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/samba/libcmdline-credentials-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/libnss_winbind.so.2 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libnss_winbind.so.2 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libnss_wins.so.2 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libnss_wins.so.2 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /lib/security/pam_winbind.so /root/samba-builder4/lib/security/');
	shell_exec('cp -fd /usr/lib/winbind_krb5_locator.so /root/samba-builder4/usr/lib/');
	shell_exec('strip -s /usr/bin/wbinfo');
	shell_exec('cp -fd /usr/bin/wbinfo /root/samba-builder4/usr/bin/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/_ldb.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/samba/ldb/ldbsamba_extensions.so /root/samba-builder4/usr/lib/samba/ldb/');
	shell_exec('cp -fd /usr/lib/samba/ldb/ildap.so /root/samba-builder4/usr/lib/samba/ldb/');
	shell_exec('cp -fd /usr/lib/samba/libregistry-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('strip -s /usr/bin/regdiff');
	shell_exec('cp -fd /usr/bin/regdiff /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/regpatch');
	shell_exec('cp -fd /usr/bin/regpatch /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/regshell');
	shell_exec('cp -fd /usr/bin/regshell /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/regtree');
	shell_exec('cp -fd /usr/bin/regtree /root/samba-builder4/usr/bin/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/registry.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/messaging.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/samba/libtorture-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/com.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/dsdb_dns.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('strip -s /usr/bin/oLschema2ldif');
	shell_exec('cp -fd /usr/bin/oLschema2ldif /root/samba-builder4/usr/bin/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/xattr_native.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/samba/libposix-eadb-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/posix_eadb.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/xattr_tdb.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/samba/libxattr-tdb-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('strip -s /usr/bin/smbtorture');
	shell_exec('cp -fd /usr/bin/smbtorture /root/samba-builder4/usr/bin/');
	shell_exec('cp -fd /usr/lib/libnetapi.so.0 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libnetapi.so.0 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/samba/libauth-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libads-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/liblibcli-lsa3-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/libsmbldap.so.0 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsmbldap.so.0 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/samba/liblibsmb-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libgse-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libsecrets3-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libutil-cmdline-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libmsrpc3-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/libsamba-passdb.so.0.25.0 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsamba-passdb.so.0.25.0 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsamba-passdb.so.0.25.0 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/samba/libsmbldaphelper-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/liblibcli-netlogon3-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libtrusts-util-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libnet-keytab-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/libsmbclient.so.0.2.3 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsmbclient.so.0.2.3 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsmbclient.so.0.2.3 /root/samba-builder4/usr/lib/');
	shell_exec('strip -s /usr/bin/gentest');
	shell_exec('cp -fd /usr/bin/gentest /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/masktest');
	shell_exec('cp -fd /usr/bin/masktest /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/locktest');
	shell_exec('cp -fd /usr/bin/locktest /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/cifsdd');
	shell_exec('cp -fd /usr/bin/cifsdd /root/samba-builder4/usr/bin/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/smb.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/security.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/netbios.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/libsamba-policy.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsamba-policy.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/libsamba-policy.so.0.0.1 /root/samba-builder4/usr/lib/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/policy.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/');
	shell_exec('cp -fd /usr/lib/samba/libnpa-tstream-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libkdc-samba4.so.2.0.0 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libkdc-samba4.so.2.0.0 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libhdb-samba4.so.11.0.2 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libhdb-samba4.so.11.0.2 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libheimntlm-samba4.so.1.0.1 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libheimntlm-samba4.so.1.0.1 /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libgpo-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libpopt-samba3-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libsmbd-conn-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libsmbd-base-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libprinting-migrate-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libcli-spoolss-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/sbin/smbd /root/samba-builder4/usr/sbin/');
	shell_exec('cp -fd /usr/sbin/nmbd /root/samba-builder4/usr/sbin/');
	shell_exec('cp -fd /usr/sbin/winbindd /root/samba-builder4/usr/sbin/');
	shell_exec('cp -fd /usr/lib/samba/libidmap-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/libnss-info-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('strip -s /usr/bin/rpcclient');
	shell_exec('cp -fd /usr/bin/rpcclient /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/smbclient');
	shell_exec('cp -fd /usr/bin/smbclient /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/net');
	shell_exec('cp -fd /usr/bin/net /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/profiles');
	shell_exec('cp -fd /usr/bin/profiles /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/smbspool');
	shell_exec('cp -fd /usr/bin/smbspool /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/testparm');
	shell_exec('cp -fd /usr/bin/testparm /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/smbstatus');
	shell_exec('cp -fd /usr/bin/smbstatus /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/smbcontrol');
	shell_exec('cp -fd /usr/bin/smbcontrol /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/smbtree');
	shell_exec('cp -fd /usr/bin/smbtree /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/smbpasswd');
	shell_exec('cp -fd /usr/bin/smbpasswd /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/pdbedit');
	shell_exec('cp -fd /usr/bin/pdbedit /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/smbget');
	shell_exec('cp -fd /usr/bin/smbget /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/nmblookup');
	shell_exec('cp -fd /usr/bin/nmblookup /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/smbcacls');
	shell_exec('cp -fd /usr/bin/smbcacls /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/smbcquotas');
	shell_exec('cp -fd /usr/bin/smbcquotas /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/eventlogadm');
	shell_exec('cp -fd /usr/bin/eventlogadm /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/sharesec');
	shell_exec('cp -fd /usr/bin/sharesec /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/ntlm_auth');
	shell_exec('cp -fd /usr/bin/ntlm_auth /root/samba-builder4/usr/bin/');
	shell_exec('strip -s /usr/bin/dbwrap_tool');
	shell_exec('cp -fd /usr/bin/dbwrap_tool /root/samba-builder4/usr/bin/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/samba3/smbd.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/samba3/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/samba3/libsmb_samba_internal.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/samba3/');
	shell_exec('cp -fd /usr/lib/samba/auth/script.so /root/samba-builder4/usr/lib/samba/auth/');
	shell_exec('cp -fd /usr/lib/samba/libnon-posix-acls-samba4.so /root/samba-builder4/usr/lib/samba/');
	shell_exec('cp -fd /usr/lib/samba/vfs/audit.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/extd_audit.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/full_audit.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/fake_perms.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/recycle.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/netatalk.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/fruit.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/default_quota.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/readonly.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/cap.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/expand_msdfs.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/shadow_copy.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/shadow_copy2.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/xattr_tdb.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/catia.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/streams_xattr.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/streams_depot.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/commit.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/readahead.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/fileid.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/aio_fork.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/aio_pthread.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/preopen.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/syncops.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/acl_xattr.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/acl_tdb.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/dirsort.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/crossrename.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/linux_xfs_sgid.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/time_audit.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/media_harmony.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/unityed_media.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/btrfs.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/shell_snap.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/worm.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/snapper.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/samba/vfs/offline.so /root/samba-builder4/usr/lib/samba/vfs/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/samba3/param.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/samba3/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/samba3/passdb.so /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/samba3/');
	shell_exec('cp -fd /usr/lib/samba/idmap/ad.so /root/samba-builder4/usr/lib/samba/idmap/');
	shell_exec('cp -fd /usr/lib/samba/idmap/rfc2307.so /root/samba-builder4/usr/lib/samba/idmap/');
	shell_exec('cp -fd /usr/lib/samba/idmap/rid.so /root/samba-builder4/usr/lib/samba/idmap/');
	shell_exec('cp -fd /usr/lib/samba/idmap/tdb2.so /root/samba-builder4/usr/lib/samba/idmap/');
	shell_exec('cp -fd /usr/lib/samba/idmap/hash.so /root/samba-builder4/usr/lib/samba/idmap/');
	shell_exec('cp -fd /usr/lib/samba/idmap/autorid.so /root/samba-builder4/usr/lib/samba/idmap/');
	shell_exec('cp -fd /usr/lib/samba/nss_info/hash.so /root/samba-builder4/usr/lib/samba/nss_info/');
	shell_exec('cp -fd /usr/lib/samba/nss_info/rfc2307.so /root/samba-builder4/usr/lib/samba/nss_info/');
	shell_exec('cp -fd /usr/lib/samba/nss_info/sfu20.so /root/samba-builder4/usr/lib/samba/nss_info/');
	shell_exec('cp -fd /usr/lib/samba/nss_info/sfu.so /root/samba-builder4/usr/lib/samba/nss_info/');
	shell_exec('cp -fd /usr/lib/samba/idmap/script.so /root/samba-builder4/usr/lib/samba/idmap/');
	shell_exec('cp -fd /usr/share/man/man1/pidl.1p /root/samba-builder4/usr/share/man/man1/');
	shell_exec('cp -fd /usr/share/man/man3/Parse::Pidl::Dump.3pm /root/samba-builder4/usr/share/man/man3/');
	shell_exec('cp -fd /usr/share/man/man3/Parse::Pidl::Wireshark::Conformance.3pm /root/samba-builder4/usr/share/man/man3/');
	shell_exec('cp -fd /usr/share/man/man3/Parse::Pidl::Util.3pm /root/samba-builder4/usr/share/man/man3/');
	shell_exec('cp -fd /usr/share/man/man3/Parse::Pidl::NDR.3pm /root/samba-builder4/usr/share/man/man3/');
	shell_exec('cp -fd /usr/share/man/man3/Parse::Pidl::Wireshark::NDR.3pm /root/samba-builder4/usr/share/man/man3/');
	
	$Architecture=Architecture();
	if($Architecture==64){$Architecture="x64";}
	if($Architecture==32){$Architecture="i386";}
	$DebianVersion=DebianVersion();
	if($DebianVersion==6){$DebianVersion=null;}else{$DebianVersion="-debian{$DebianVersion}";}
	$version=SAMBA_VERSION();
	$tar="/bin/tar";
	echo "Building package Arch:$Architecture Version:$version  $DebianVersion\n";
	
	@chdir("/root/samba-builder4");
	if(is_file("/root/samba-builder4/sambac$DebianVersion-$Architecture-$version.tar.gz")){@unlink("/root/samba-builder/sambac-$Architecture-$version.tar.gz");}
	echo "Compressing sambac$DebianVersion-$Architecture-$version.tar.gz\n";
	shell_exec("$tar -czf sambac$DebianVersion-$Architecture-$version.tar.gz *");
	echo "Compressing /root/samba-builder4/sambac$DebianVersion-$Architecture-$version.tar.gz Done...\n";
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










