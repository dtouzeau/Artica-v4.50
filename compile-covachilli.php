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


$unix=new unix();
$php5=$unix->LOCATE_PHP5_BIN();
$GLOBALS["SHOW_COMPILE_ONLY"]=false;
$GLOBALS["NO_COMPILE"]=false;
$GLOBALS["REPOS"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if($argv[1]=='--compile'){$GLOBALS["SHOW_COMPILE_ONLY"]=true;}
if(preg_match("#--no-compile#", @implode(" ", $argv))){$GLOBALS["NO_COMPILE"]=true;}
if(preg_match("#--verbose#", @implode(" ", $argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--repos#", @implode(" ", $argv))){$GLOBALS["REPOS"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}

$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");

/*
 *  
 *  apt-get install gengetopt
 *  apt-get install libtool libssl-dev libcurl4-openssl-dev gengetopt
 *  dpkg-architecture -qDEB_BUILD_GNU_TYPE [ x86_64-linux-gnu or i486-linux-gnu ]
svn checkout http://dev.coova.org/svn/coova-chilli/
cd coova-chilli
sh bootstrap
i386
./configure --prefix=/usr --mandir=${prefix}/share/man --infodir=${prefix}/share/info   --sysconfdir=/etc --localstatedir=/var --enable-largelimits   --enable-binstatusfile --enable-statusfile --enable-chilliproxy   --enable-chilliradsec --enable-chilliredir --with-openssl --with-curl   --with-poll --enable-dhcpopt --enable-sessgarden --enable-dnslog   --enable-ipwhitelist --enable-redirdnsreq --enable-miniconfig   --enable-libjson --enable-layer3 --enable-proxyvsa --enable-miniportal   --enable-chilliscript --enable-eapol --enable-uamdomainfile   --enable-modules --enable-multiroute --build=i486-linux-gnu
./configure --prefix=/usr --mandir=${prefix}/share/man --infodir=${prefix}/share/info  --sysconfdir=/etc --localstatedir=/var --enable-largelimits --enable-proxyvsa --enable-miniportal --enable-chilliredir --enable-chilliproxy --enable-binstatusfile --enable-chilliscript --enable-chilliradsec --enable-dnslog --enable-layer3 --enable-eapol --enable-uamdomainfile --enable-redirdnsreq --enable-modules --enable-multiroute --enable-extadmvsa --with-openssl --with-poll --enable-gardenaccounting


amd64
./configure --prefix=/usr --mandir=${prefix}/share/man --infodir=${prefix}/share/info   --sysconfdir=/etc --localstatedir=/var --enable-largelimits   --enable-binstatusfile --enable-statusfile --enable-chilliproxy   --enable-chilliradsec --enable-chilliredir --with-openssl --with-curl   --with-poll --enable-dhcpopt --enable-sessgarden --enable-dnslog   --enable-ipwhitelist --enable-redirdnsreq --enable-miniconfig   --enable-libjson --enable-layer3 --enable-proxyvsa --enable-miniportal   --enable-chilliscript --enable-eapol --enable-uamdomainfile   --enable-modules --enable-multiroute --build=x86_64-linux-gnu
make
make install
 */


$Architecture=Architecture();
$version=chilli_version();
$f["/usr/lib/libbstring.a"]=true;
$f["/usr/lib/libbstring.so.0.0.0 "]=true;
$f["/usr/lib/libbstring.so"]=true;
$f["/usr/lib/libbstring.so.0"]=true;
$f["/usr/lib/libjson.so.0.0.0 "]=true;
$f["/usr/lib/libjson.so.0"]=true;
$f["/usr/lib/libjson.so.0.0.0 "]=true;
$f["/usr/lib/libjson.so.0"]=true;
$f["/usr/lib/libjson.a"]=true;
$f["/usr/lib/libchilli.so"]=true;
$f["/usr/lib/libchilli.so.0.0.0 "]=true;
$f["/usr/lib/libchilli.so.0"]=true;
$f["/usr/lib/libchilli.a"]=true;
$f["/usr/lib/coova-chilli/sample.so"]=true;
$f["/usr/lib/coova-chilli/sample.la"]=true;
$f["/usr/lib/coova-chilli/sample.a"]=true;
$f["/usr/sbin/chilli"]=true;
$f["/usr/sbin/chilli_response"]=true;
$f["/usr/sbin/chilli_radconfig"]=true;
$f["/usr/sbin/chilli_opt"]=true;
$f["/usr/sbin/chilli_rtmon"]=true;
$f["/usr/sbin/chilli_query"]=true;
$f["/usr/sbin/chilli_proxy"]=true;
$f["/usr/sbin/chilli_radsec"]=true;
$f["/usr/sbin/chilli_script"]=true;
$f["/usr/sbin/chilli_redir"]=true;
$f["/etc/init.d/chilli"]=true;
$f["/share/man/man1/chilli_query.1"]=true;
$f["/share/man/man1/chilli_response.1"]=true;
$f["/share/man/man1/chilli_radconfig.1"]=true;
$f["/share/man/man1/chilli_opt.1"]=true;
$f["/share/man/man1/chilli_rtmon.1"]=true;
$f["/share/man/man1/chilli_redir.1"]=true;
$f["/share/man/man1/chilli_proxy.1"]=true;
$f["/share/man/man1/chilli_radsec.1"]=true;
$f["/share/man/man1/chilli_script.1"]=true;
$f["/etc/chilli.conf"]=true;
if($Architecture==64){$Architecture="x64";}
if($Architecture==32){$Architecture="i386";}
echo "Compile Arch $Architecture v:$version\n";

$WORKDIR="/root/chilli/chilli-$Architecture-$version";

while (list ($path, $ligne) = each ($f) ){
	$dirname=dirname($path);
	
	@mkdir("$WORKDIR/$dirname",0755,true);
	echo "Copy $path to $WORKDIR/$dirname\n";
	shell_exec("$cp $path $WORKDIR/$path");
	
}
@mkdir("$WORKDIR/etc/chilli",0755,true);
shell_exec("$cp -rf /etc/chilli/* $WORKDIR/etc/chilli/");

chdir($WORKDIR);
shell_exec("$tar -czf /root/chilli-$Architecture-$version.tar.gz *");


function Architecture(){
	$unix=new unix();
	$uname=$unix->find_program("uname");
	exec("$uname -m 2>&1",$results);
	foreach ($results as $num=>$val){
		if(preg_match("#i[0-9]86#", $val)){return 32;}
		if(preg_match("#x86_64#", $val)){return 64;}
	}
}

function chilli_version(){
	exec("/usr/sbin/chilli -V 2>&1",$results);
	foreach ($results as $num=>$val){
		if(preg_match("#coova-chilli\s+([0-9\.]+)#", $val,$re)){
			return trim($re[1]);
		}
	}
	
}

