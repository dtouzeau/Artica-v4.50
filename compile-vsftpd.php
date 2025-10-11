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
$GLOBALS["WORKDIR"]="/root/haproxy-builder";
$GLOBALS["MAINURI"]="http://haproxy.1wt.eu/download/1.5/src/devel/";
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


$unix=new unix();
$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");
$dirsrc="vsftpd-3";
$GLOBALS["VERSION"]="3.0.2";
$GLOBALS["MAINURI"]="https://security.appspot.com/downloads/vsftpd-3.0.2.tar.gz";
$GLOBALS["WORKDIR"]="/root/$dirsrc";
$GLOBALS["SRCNAME"]=basename($GLOBALS["MAINURI"]);


$Architecture=Architecture();

if(is_dir("/root/$dirsrc")){shell_exec("$rm -rf {$GLOBALS["WORKDIR"]}");}
chdir("/root");
if(!$GLOBALS["NO_COMPILE"]){
	
	if(is_dir("/root/$dirsrc")){shell_exec("/bin/rm -rf /root/$dirsrc");}
	@mkdir("/root/$dirsrc");
	if(!is_file("/root/{$GLOBALS["SRCNAME"]}")){
		squid_admin_mysql(2, "Detected new version {$GLOBALS["VERSION"]}", __FUNCTION__, __FILE__, __LINE__, "software");
		echo "Downloading {$GLOBALS["SRCNAME"]} ...\n";
		shell_exec("$wget {$GLOBALS["MAINURI"]}");
		if(!is_file("/root/{$GLOBALS["SRCNAME"]}")){
			squid_admin_mysql(2, "Downloading failed", __FUNCTION__, __FILE__, __LINE__, "software");
			echo "Downloading failed...\n";die("DIE " .__FILE__." Line: ".__LINE__);}
	}
	
	shell_exec("$tar -xhf /root/{$GLOBALS["SRCNAME"]} -C {$GLOBALS["WORKDIR"]}");
	chdir($GLOBALS["WORKDIR"]);
	if(!is_file("/root/$dirsrc/Makefile")){
		echo "/root/$dirsrc/Makefile no such file\n";
		$dirs=$unix->dirdir("/root/$dirsrc");
		while (list ($num, $ligne) = each ($dirs) ){if(!is_file("$ligne/Makefile")){echo "$ligne/Makefile no such file\n";}else{
			chdir("$ligne");
			echo "[OK]: Change to dir $ligne\n";
			$SOURCE_DIRECTORY=$ligne;
			break;}}
	}
	
}



if(!$GLOBALS["NO_COMPILE"]){
	
	$make_params="CFLAGS=\"-g -O2 -fPIE -fstack-protector --param=ssp-buffer-size=4 -Wformat -Werror=format-security -D_FORTIFY_SOURCE=2\" LIBS=\"-lwrap -lpam -lssl -lcrypto -lcap\" LINK=\"\"";
	
	echo "make...\n";
	if($GLOBALS["VERBOSE"]){system("make $make_params");}
	if(!$GLOBALS["VERBOSE"]){shell_exec("make $make_params");}
	echo "make install...\n";
	
	$unix=new unix();
	$vsftpd=$unix->find_program("vsftpd");
	if(is_file($vsftpd)){@unlink("$vsftpd");}
	@mkdir("/usr/local/sbin",0755,true);
	@mkdir("/usr/local/man/man5",0755,true);
	
	
	echo "Make install\n";
	if($GLOBALS["VERBOSE"]){system("make install");}
	if(!$GLOBALS["VERBOSE"]){shell_exec("make install");}	
	
}
if(!is_file("/usr/local/sbin/vsftpd")){squid_admin_mysql(2, "{installing} the new vsftpd {$GLOBALS["VERSION"]} failed", __FUNCTION__, __FILE__, __LINE__, "software"); echo "Failed\n";}
create_package();	
	


function create_package(){
$unix=new unix();	
$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");
$Architecture=Architecture();
$version=haproxy_version();
$debian_version=DebianVersion();

mkdir("{$GLOBALS["WORKDIR"]}/usr/local/sbin",0755,true);
$f[]="/usr/local/sbin/vsftpd";
$f[]="/usr/local/man/man8/vsftpd.8";
$f[]="/usr/man/man8/vsftpd.8";
$f[]="/usr/share/man/man5/vsftpd.conf.5";
$f[]="/usr/share/man/man8/vsftpd.8";


foreach ($f as $num=>$ligne){
	if(is_dir($ligne)){
		shell_exec("/bin/cp -rfd $ligne/* {$GLOBALS["WORKDIR"]}/$ligne/");
		continue;
	}

	if(!is_file($ligne)){echo "$ligne no such file\n";continue;}
	$dir=dirname($ligne);
	echo "Installing $ligne in {$GLOBALS["WORKDIR"]}$dir/\n";
	if(!is_dir("{$GLOBALS["WORKDIR"]}$dir")){@mkdir("{$GLOBALS["WORKDIR"]}$dir",0755,true);}
	shell_exec("/bin/cp -fd $ligne {$GLOBALS["WORKDIR"]}$dir/");

}

if($Architecture==64){$Architecture="x64";}
if($Architecture==32){$Architecture="i386";}
echo "Compile Arch $Architecture v:{$GLOBALS["VERSION"]} Debian $debian_version\n";
chdir("{$GLOBALS["WORKDIR"]}");

$packagename="vsftpd-$Architecture-debian{$debian_version}-{$GLOBALS["VERSION"]}.tar.gz";

echo "Compressing....{$GLOBALS["WORKDIR"]}/$packagename\n";
shell_exec("$tar -czf $packagename *");
shell_exec("$cp {$GLOBALS["WORKDIR"]}/$packagename /root/");
squid_admin_mysql(2, "{$GLOBALS["WORKDIR"]}/$packagename  ready...",__FUNCTION__,__FILE__,__LINE__);
}


function DebianVersion(){
	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];

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

function haproxy_version(){
	exec("/usr/local/sbin/haproxy -v 2>&1",$results);
	foreach ($results as $num=>$val){
        if(preg_match("#^(HA-Proxy|HAProxy)\s+version\s+([0-9\.]+)#", $val,$re)){
            return trim($re[2]);
        }
	}
	
}

function latests(){
	$unix=new unix();
	$time=time();
	$curl=new ccurl("{$GLOBALS["MAINURI"]}");
	if(!$curl->GetFile("/tmp/index-$time.html")){
		echo "$curl->error\n";
		return 0;
	}
	$f=explode("\n",@file_get_contents("/tmp/index-$time.html"));
	foreach ($f as $index=>$line){
		if(preg_match("#<a href=\"haproxy-(.+?)\.tar\.gz#", $line,$re)){
			$ve=$re[1];
			$ve=str_replace("-dev", "", $ve);
			$STT=explode(".", $ve);
			$CountDeSTT=count($STT);
			$veOrg=$ve;
			$ve=str_replace(".", "", $ve);
			$ve=str_replace("-", "", $ve);
			if($GLOBALS["VERBOSE"]){echo "Add version $veOrg -> `$ve`\n";}
			$file="haproxy-{$re[1]}.tar.gz";
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




function factorize($path){
	$f=explode("\n",@file_get_contents($path));
    foreach ($f as $val){
		$newarray[$val]=$val;
		
	}
	while (list ($num, $val) = each ($newarray)){
		echo "$val\n";
	}
	
}

