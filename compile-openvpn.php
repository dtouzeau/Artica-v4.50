<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
echo " *********************** 1\n";
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');

echo " *********************** 2\n";

if($argv[1]=='--compile'){CompileOpenVPN();die("DIE " .__FILE__." Line: ".__LINE__);}
if($argv[1]=='--package'){PackageOpenVPN();die("DIE " .__FILE__." Line: ".__LINE__);}



echo "????\n";

/* 
wget https://github.com/lz4/lz4/archive/refs/tags/v1.9.4.tar.gz
tar -xf v1.9.4.tar.gz
make 
make install PREFIX=/usr LIBDIR=/usr/lib/x86_64-linux-gnu CC=x86_64-linux-gnu-gcc
usr/lib/x86_64-linux-gnu/l

git clone https://github.com/greendev5/openvpn-python.git 
cd openvpn-python
./autogen.sh
./configure
make && make install
 *
 *
 */

function CompileOpenVPN(){
	$unix=new unix();
	$git=$unix->find_program("git");
	if(!is_file("$git")){echo "git no such binary\n";return;}
	if(is_dir("/root/openvpn")){system("rm -rf /root/openvpn");}
	system("cd /root");
	@chdir("/root");
	if(is_dir("/root/openvpn")){system("rm -rf /root/openvpn");}
	system("$git clone https://github.com/OpenVPN/openvpn.git");
	system("cd /root/openvpn");
	@chdir("/root/openvpn");
	system("autoreconf -i -v -f");
	system("./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-silent-rules --libexecdir=\${prefix}/lib/openvpn  --host=x86_64-linux-gnu --build=x86_64-linux-gnu --prefix=/usr --mandir=\${prefix}/share/man --enable-iproute2 --with-plugindir=\${prefix}/lib/openvpn --includedir=\${prefix}/include/openvpn --enable-x509-alt-username --with-special-build=\"Artica Edition\"");
	system("make");
	system("make install");
	PackageOpenVPN();
}
function DebianVersion(){
	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];

}
function PackageOpenVPN(){
	echo "Build OpenVPN package\n";
	$debian=DebianVersion();
	$unix=new unix();	
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	$strip=$unix->find_program("strip");
	$Architecture=xArchitecture();
	chdir("/root");
	if(is_dir("/root/openvpn-builder")){system("$rm -rf /root/openvpn-builder");}
	@mkdir("/root/openvpn-builder",0755,true);
	@mkdir("/root/openvpn-builder/usr/lib/openvpn",0755,true);
	@mkdir("/root/openvpn-builder/usr/sbin",0755,true);
	@mkdir("/root/openvpn-builder/usr/lib/x86_64-linux-gnu",0755,true);
	@mkdir("/root/openvpn-builder/lib/x86_64-linux-gnu",0755,true);
	@mkdir("/root/openvpn-builder/usr/local/lib",0755,true);


    @mkdir("/root/openvpn-builder/usr/local/lib/python2.7/dist-packages/mysql",0755,true);
    @mkdir("/root/openvpn-builder/usr/local/lib/python2.7/dist-packages/mysql_connector_python-8.0.23.dist-info",0755,true);
    @mkdir("/root/openvpn-builder/usr/local/lib/python2.7/dist-packages/mysqlx",0755,true);
    @mkdir("/root/openvpn-builder/usr/include",0755,true);
    @mkdir("/root/openvpn-builder/usr/bin",0755,true);
    @mkdir("/root/openvpn-builder/usr/share/man/man1",0755,true);

	system("$strip -s /usr/sbin/openvpn");
	
	system("cp -rfd /usr/lib/openvpn/* /root/openvpn-builder/usr/lib/openvpn/");
	system("cp -rfd /usr/sbin/openvpn /root/openvpn-builder/usr/sbin/openvpn");

    system("cp -rfd /usr/local/lib/python2.7/dist-packages/mysql/* /root/openvpn-builder/usr/local/lib/python2.7/dist-packages/mysql/");
    system("cp -rfd /usr/local/lib/python2.7/dist-packages/mysql_connector_python-8.0.23.dist-info/* /root/openvpn-builder/usr/local/lib/python2.7/dist-packages/mysql_connector_python-8.0.23.dist-info/");
    system("cp -rfd /usr/local/lib/python2.7/dist-packages/mysqlx/* /root/openvpn-builder/usr/local/lib/python2.7/dist-packages/mysqlx/");

    system("cp -rfd /usr/include/lz4* /root/openvpn-builder/usr/include/");
    system("cp -rfd /usr/bin/lz4* /root/openvpn-builder/usr/bin/");
    system("cp -rfd /usr/share/man/man1/lz4* /root/openvpn-builder/usr/share/man/man1");
    system("cp -fd /usr/lib/x86_64-linux-gnu/liblz4* /root/openvpn-builder/usr/lib/x86_64-linux-gnu/");


	if($debian<9){
		system("cp -fd /usr/lib/x86_64-linux-gnu/liblz4.a /root/openvpn-builder/usr/lib/x86_64-linux-gnu/");
		system("cp -fd /usr/lib/x86_64-linux-gnu/liblz4.so  /root/openvpn-builder/usr/lib/x86_64-linux-gnu/");
		system("cp -fd /usr/lib/x86_64-linux-gnu/liblz4.so.1  /root/openvpn-builder/usr/lib/x86_64-linux-gnu/");
		system("cp -fd /usr/lib/x86_64-linux-gnu/liblz4.so.1.7.5  /root/openvpn-builder/usr/lib/x86_64-linux-gnu/");
	}
	system("cp -fd /usr/local/lib/openvpn-python.la /root/openvpn-builder/usr/local/lib/");
	system("cp -fd /usr/local/lib/openvpn-python.so /root/openvpn-builder/usr/local/lib/");
	
	if($debian<9){
		system("cp -fd /usr/lib/x86_64-linux-gnu/libpython2.7.a /root/openvpn-builder/usr/lib/x86_64-linux-gnu/");   
		system("cp -fd /usr/lib/x86_64-linux-gnu/libpython2.7.so.1 /root/openvpn-builder/usr/lib/x86_64-linux-gnu/");
		system("cp -fd /usr/lib/x86_64-linux-gnu/libpython2.7.so /root/openvpn-builder/usr/lib/x86_64-linux-gnu/");  
		system("cp -fd /usr/lib/x86_64-linux-gnu/libpython2.7.so.1.0 /root/openvpn-builder/usr/lib/x86_64-linux-gnu/");
		if(is_file("/lib/x86_64-linux-gnu/liblzo2.so.2")){echo "Copy: /lib/x86_64-linux-gnu/liblzo2.so.2\n";system("cp -fd /lib/x86_64-linux-gnu/liblzo2.so.2 /root/openvpn-builder/lib/x86_64-linux-gnu/");}	
		if(is_file("/lib/x86_64-linux-gnu/liblzo2.so.2.0.0")){	system("cp -fd /lib/x86_64-linux-gnu/liblzo2.so.2.0.0 /root/openvpn-builder/lib/x86_64-linux-gnu/");}
	}
	system("cd /root/openvpn-builder/");
	@chdir("/root/openvpn-builder/");
	system("pwd");
	$version=xopenvpn_version();
	$version=str_replace("_git", "", $version);
	$Architecture=xArchitecture();
	
	$filename="/root/openvpn-debian$debian-$Architecture-$version.tar.gz";
	if(is_file($filename)){@unlink($filename);}
	echo "Compressing $filename\n";
	system("$tar czf $filename *");

}


function xopenvpn_version(){
	$unix=new unix();
	if(isset($GLOBALS["openvpn_version"])){return $GLOBALS["openvpn_version"];}
	$bin_path=$unix->find_program("openvpn");
	exec("$bin_path --version 2>&1",$results);
	
	foreach ($results as $index=>$line){
		if(preg_match("#OpenVPN\s+([0-9]+)\.([0-9]+)([a-z0-9\_\-\.]+)\s+#",$line,$re)){
			$GLOBALS["openvpn_version"]=$re[1].".{$re[2]}{$re[3]}";
			return $GLOBALS["openvpn_version"];
		}
	}
	
}


function xArchitecture(){
	$unix=new unix();
	$uname=$unix->find_program("uname");
	exec("$uname -m 2>&1",$results);
	foreach ($results as $num=>$val){
		if(preg_match("#i[0-9]86#", $val)){return 32;}
		if(preg_match("#x86_64#", $val)){return 64;}
	}
}
?>