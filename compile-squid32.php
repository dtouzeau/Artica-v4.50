<?php
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');

unset($argv[0]);
$unix=new unix();
$php5=$unix->LOCATE_PHP5_BIN();
shell_exec("$php5 ".dirname(__FILE__)."/compile-squid33.php ".@implode(" ",$argv));
die("DIE " .__FILE__." Line: ".__LINE__);



$GLOBALS["SHOW_COMPILE_ONLY"]=false;
$GLOBALS["NO_COMPILE"]=false;
$GLOBALS["REPOS"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if($argv[1]=='--compile'){$GLOBALS["SHOW_COMPILE_ONLY"]=true;}
if(preg_match("#--no-compile#", @implode(" ", $argv))){$GLOBALS["NO_COMPILE"]=true;}
if(preg_match("#--verbose#", @implode(" ", $argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--repos#", @implode(" ", $argv))){$GLOBALS["REPOS"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--cross-packages"){crossroads_package();exit;}
if($argv[1]=="--factorize"){factorize($argv[2]);exit;}
if($argv[1]=="--serialize"){serialize_tests();exit;}
if($argv[1]=="--latests"){latests();exit;}
if($argv[1]=="--error-txt"){error_txt();exit;}
if($argv[1]=="--c-icap"){package_c_icap();exit;}
if($argv[1]=="--ufdb"){package_ufdbguard();exit;}
if($argv[1]=="--msmtp"){package_msmtp();exit;}

if($argv[1]=="--ecapclam"){ecap_clamav();exit;}
if($argv[1]=="--package"){create_package();exit;}
if($argv[1]=="--c-icap-remove"){die("DIE " .__FILE__." Line: ".__LINE__);exit;}



$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");

//http://www.squid-cache.org/Versions/v3/3.2/squid-3.2.0.13.tar.gz


$dirsrc="squid-3.2.0.16";
$Architecture=Architecture();

if(!$GLOBALS["NO_COMPILE"]){$v=latests();
	if(preg_match("#squid-(.+?)-#", $v,$re)){$dirsrc=$re[1];}
	squid_admin_mysql(2, "Downloading lastest file $v, working directory $dirsrc ...",__FUNCTION__,__FILE__,__LINE__);
}

if(!$GLOBALS["FORCE"]){
	if(is_file("/root/$v")){if($GLOBALS["REPOS"]){echo "No updates...\n";die("DIE " .__FILE__." Line: ".__LINE__);}}
}

if(is_dir("/root/squid-builder")){shell_exec("$rm -rf /root/squid-builder");}
chdir("/root");
if(!$GLOBALS["NO_COMPILE"]){
	
	if(is_dir("/root/$dirsrc")){shell_exec("/bin/rm -rf /root/$dirsrc");}
	@mkdir("/root/$dirsrc");
	if(!is_file("/root/$v")){
		squid_admin_mysql(2, "Detected new version $v", __FUNCTION__, __FILE__, __LINE__, "software");
		echo "Downloading $v ...\n";
		shell_exec("$wget http://www.squid-cache.org/Versions/v3/3.2/$v");
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

$cmds[]="--prefix=/usr";
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
$cmds[]="--with-large-files";
$cmds[]="--with-pthreads";
$cmds[]="--enable-esi"; 
$cmds[]="--enable-storeio=aufs,diskd,ufs,rock"; 
$cmds[]="--enable-x-accelerator-vary";
$cmds[]="--with-dl";
$cmds[]="--enable-linux-netfilter"; 
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
$cmds[]="--enable-async-io";
$cmds[]="--enable-delay-pools";
$cmds[]="--enable-http-violations";
$cmds[]="--enable-url-maps";
//$cmds[]="--enable-ecap";
$cmds[]="--enable-ssl"; 
$cmds[]="--enable-ssl-crtd";
$cmds[]="CFLAGS=\"-O3 -pipe -fomit-frame-pointer -funroll-loops -ffast-math -fno-exceptions\""; 

//CPPFLAGS="-I../libltdl"



$configure="./configure ". @implode(" ", $cmds);
if($GLOBALS["VERBOSE"]){echo "\n\n$configure\n\n";}

if($GLOBALS["SHOW_COMPILE_ONLY"]){echo $configure."\n";die("DIE " .__FILE__." Line: ".__LINE__);}
if(!$GLOBALS["NO_COMPILE"]){
	
	echo "configuring...\n";
	shell_exec($configure);
	echo "make...\n";
	if($GLOBALS["VERBOSE"]){system("make");}
	if(!$GLOBALS["VERBOSE"]){shell_exec("make");}
	squid_admin_mysql(2, "{installing} the new squid-cache $v version", __FUNCTION__, __FILE__, __LINE__, "software");
	echo "make install...\n";
	
	$unix=new unix();
	$squid3=$unix->find_program("squid3");
	if(is_file($squid3)){@unlink($squid3);}
	echo "Removing squid last install\n";
	remove_squid();
	echo "Make install\n";
	if($GLOBALS["VERBOSE"]){system("make install");}
	if(!$GLOBALS["VERBOSE"]){shell_exec("make install");}	
	
}
if(!is_file("/usr/sbin/squid")){
	squid_admin_mysql(2, "{installing} the new squid-cache $v failed", __FUNCTION__, __FILE__, __LINE__, "software");
	echo "Failed\n";}
	

shell_exec("/bin/chown -R squid:squid /usr/share/squid3");


create_package($t);	
	


function create_package($t){
$unix=new unix();	
$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");
$Architecture=Architecture();
$version=squid_version();

shell_exec("wget http://www.articatech.net/download/anthony-icons.tar.gz -O /tmp/anthony-icons.tar.gz");
@mkdir("/usr/share/squid3/icons",0755,true);
shell_exec("tar -xhf /tmp/anthony-icons.tar.gz -C /usr/share/squid3/icons/");
shell_exec("/bin/chown -R squid:squid /usr/share/squid3/icons/");

mkdir("/root/squid-builder/usr/share/squid3",0755,true);
mkdir("/root/squid-builder/etc/squid3",0755,true);
mkdir("/root/squid-builder/lib/squid3",0755,true);
mkdir("/root/squid-builder/usr/sbin",0755,true);
mkdir("/root/squid-builder/usr/bin",0755,true);
mkdir("/root/squid-builder/usr/share/squid-langpack",0755,true);

shell_exec("$cp -rf /usr/share/squid3/* /root/squid-builder/usr/share/squid3/");
shell_exec("$cp -rf /etc/squid3/* /root/squid-builder/etc/squid3/");
shell_exec("$cp -rf /lib/squid3/* /root/squid-builder/lib/squid3/");
shell_exec("$cp -rf /usr/share/squid-langpack/* /root/squid-builder/usr/share/squid-langpack/");
shell_exec("$cp -rf /usr/sbin/squid /root/squid-builder/usr/sbin/squid");
shell_exec("$cp -rf /usr/bin/purge /root/squid-builder/usr/bin/purge");
shell_exec("$cp -rf /usr/bin/squidclient /root/squid-builder/usr/bin/squidclient");
shell_exec("$cp -rf /usr/bin/mysar /root/squid-builder/usr/bin/mysar");
echo "Compile SARG....\n";
compile_sarg();

if($Architecture==64){$Architecture="x64";}
if($Architecture==32){$Architecture="i386";}
echo "Compile Arch $Architecture v:$version\n";
chdir("/root/squid-builder");

$version=squid_version();
echo "Compressing....\n";
shell_exec("$tar -czf squid32-$Architecture-$version.tar.gz *");
squid_admin_mysql(2, "/root/squid-builder/squid32-$Architecture-$version.tar.gz  ready...",__FUNCTION__,__FILE__,__LINE__);
if(is_file("/root/ftp-password")){
	echo "/root/squid-builder/squid32-$Architecture-$version.tar.gz is now ready to be uploaded\n";
	shell_exec("curl -T /root/squid-builder/squid32-$Architecture-$version.tar.gz ftp://www.articatech.net/download/ --user ".@file_get_contents("/root/ftp-password"));
	squid_admin_mysql(2, "Uploading squid32-$Architecture-$version.tar.gz done.",__FUNCTION__,__FILE__,__LINE__);
	if(is_file("/root/rebuild-artica")){shell_exec("$wget \"".@file_get_contents("/root/rebuild-artica")."\" -O /tmp/rebuild.html");}
	
}	

shell_exec("/etc/init.d/artica-postfix restart squid-cache");	
$took=$unix->distanceOfTimeInWords($t,time(),true);
squid_admin_mysql(2, "{installing} the new squid-cache $version success {took}:$took", __FUNCTION__, __FILE__, __LINE__, "software");
}

function compile_sarg(){

mkdir("/root/squid-builder/usr/bin",0755,true);
mkdir("/root/squid-builder/usr/share/locale",0755,true);

	
$f[]="/usr/bin/sarg";
$f[]="/usr/share/locale/bg/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/ca/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/cs/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/de/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/el/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/es/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/fr/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/hu/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/id/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/it/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/ja/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/lv/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/nl/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/pl/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/pt/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/ro/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/ru/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/sk/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/sr/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/tr/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/zh_CN/LC_MESSAGES/sarg.mo";
$f[]="/usr/share/locale/uk/LC_MESSAGES/sarg.mo";
$f[]="/usr/etc/sarg.conf";
$f[]="/usr/etc/user_limit_block";
$f[]="/usr/etc/exclude_codes";
$f[]="usr/sbin/sniproxy";

	foreach ($f as $num=>$ligne){
		if(!is_file($ligne)){echo "$ligne no such file\n";continue;}
		$dir=dirname($ligne);
		echo "Installing $ligne in /root/squid-builder$dir/\n";
		if(!is_dir("/root/squid-builder$dir")){@mkdir("/root/squid-builder$dir",0755,true);}
		shell_exec("/bin/cp -fd $ligne /root/squid-builder$dir/");
		
	}

$f=array();
$f[]="/usr/share/sarg/fonts";
$f[]="/usr/share/sarg/images";

foreach ($f as $num=>$dir){
	if(!is_dir("/root/squid-builder$dir")){@mkdir("/root/squid-builder$dir",0755,true);}
	echo "Installing $dir/* in /root/squid-builder$dir/\n";
	shell_exec("/bin/cp -rfdv $dir/* /root/squid-builder$dir/");
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
	
	$curl=new ccurl("http://www.squid-cache.org/Versions/v3/3.2/");
	if(!$curl->GetFile("/tmp/index.html")){
		echo "$curl->error\n";
		return 0;
	}
	$f=explode("\n",@file_get_contents("/tmp/index.html"));
	foreach ($f as $index=>$line){
		if(preg_match("#<a href=\"squid-(.+?)\.tar\.gz#", $line,$re)){
			$ve=$re[1];
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





function remove_squid(){
$bins[]="/usr/sbin/squid3";
$bins[]="/usr/sbin/squid";
$bins[]="/usr/share/man/man8/squid3.8.gz";
$bins[]="/usr/sbin/squid";
$bins[]="/usr/bin/purge";
$bins[]="/usr/bin/squidclient";

while (list ($num, $filename) = each ($bins)){
	if(is_file($filename)){
		echo "Remove $filename\n";
		@unlink($filename);
	}
	
}

$dirs[]="/etc/squid3";
$dirs[]="/lib/squid3"; 
$dirs[]="/usr/lib/squid3"; 
$dirs[]="/lib64/squid3"; 
$dirs[]="/usr/lib64/squid3"; 
$dirs[]="/usr/share/squid3"; 

while (list ($num, $filename) = each ($dirs)){
	if(is_dir($filename)){
		echo "Remove $filename\n";
		shell_exec("/bin/rm -rf $filename");
	}
	
}
	
	
}

function package_msmtp_version(){
	exec("/root//root/msmtp-compiled/usr/bin/msmtp --version 2>&1",$results);
	foreach ($results as $num=>$line){
		if(preg_match("msmtp version.*?([0-9\.]+)#", $line,$re)){return $re[1];}
	}


}

function package_msmtp(){
	$base="/root/msmtp-compiled";
	shell_exec("/bin/rm -rf /root/ufdbGuard-compiled");
	
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
	echo "/root/msmtp-$Architecture-$version.tar.gz done";
	
}




function package_ufdbguard(){
	
shell_exec("/bin/rm -rf /root/ufdbGuard-compiled");
	
$f[]="/usr/bin/ufdbguardd";
$f[]="/usr/bin/ufdbgclient";
$f[]="/usr/bin/ufdb-pstack";
$f[]="/usr/bin/ufdbConvertDB";
$f[]="/usr/bin/ufdbGenTable";
$f[]="/usr/bin/ufdbAnalyse";
$f[]="/usr/bin/ufdbhttpd";
$f[]="/usr/bin/ufdbUpdate";
$f[]="/etc/init.d/ufdb";	
$base="/root/ufdbGuard-compiled";
foreach ($f as $num=>$filename){
	$dirname=dirname($filename);
	if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
	shell_exec("/bin/cp -f $filename $base/$dirname/");
	
}

$Architecture=Architecture();
$version=ufdbguardVersion();
chdir("/root/ufdbGuard-compiled");
shell_exec("tar -czf ufdbGuard-$Architecture-$version.tar.gz *");
shell_exec("/bin/cp ufdbGuard-$Architecture-$version.tar.gz /root/");
echo "/root/ufdbGuard-$Architecture-$version.tar.gz done";

	
}

function ufdbguardVersion(){
	exec("/root/ufdbGuard-compiled/usr/bin/ufdbguardd -v 2>&1",$results);
	foreach ($results as $num=>$line){
		if(preg_match("#ufdbguardd.*?([0-9\.]+)#", $line,$re)){return $re[1];}
	}
	
	
}



function c_cicap_remove(){
}


function package_c_icap(){
$f=c_icap_array();

$base="/root/c-icap-export";
shell_exec("/bin/rm -rf $base");
@mkdir($base);
foreach ($f as $num=>$filename){
	$dirname=dirname($filename);
	if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
	if(is_file($filename)){
		echo "Copy $filename into $base$dirname\n";
		shell_exec("/bin/cp -f $filename $base$dirname/");
	}
	
}
$C_ICAP_VERSION=C_ICAP_VERSION();
$Architecture=Architecture();
echo "C-icap version $C_ICAP_VERSION ($Architecture)\n";
mkdir("/root/c-icap/usr/share/c_icap",0755,true);
mkdir("/root/c-icap/usr/include/c_icap",0755,true);
shell_exec("/bin/cp -rf /usr/share/c_icap/* /root/c-icap/usr/share/c_icap/");
shell_exec("/bin/cp -rf /usr/include/c_icap/* /root/c-icap/usr/include/c_icap/");
//error while loading shared libraries: libbz2.so.1.0
shell_exec("/bin/cp /lib/libbz2.so.1.0.4 /usr/lib/c_icap/");

chdir($base);
@unlink("/root/c-icap-$C_ICAP_VERSION-$Architecture.tar.gz");
shell_exec("/bin/tar -czf /root/c-icap-$C_ICAP_VERSION-$Architecture.tar.gz *");
echo "/root/c-icap-$C_ICAP_VERSION-$Architecture.tar.gz\n";
}

function C_ICAP_VERSION(){
	
	$results=exec("/usr/bin/c-icap-config --version");
	preg_match("#([0-9\.]+)#", $results,$re);
	return $re[1];
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


