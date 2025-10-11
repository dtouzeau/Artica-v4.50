<?php

/*./configure CFLAGS="-g -O2 -fstack-protector --param=ssp-buffer-size=4 -Wformat -Werror=format-security" CPPFLAGS="-D_FORTIFY_SOURCE=2" CXXFLAGS="-g -O2 -fstack-protector --param=ssp-buffer-size=4 -Wformat -Werror=format-security" FCFLAGS="-g -O2 -fstack-protector --param=ssp-buffer-size=4" FFLAGS="-g -O2 -fstack-protector --param=ssp-buffer-size=4" GCJFLAGS="-g -O2 -fstack-protector --param=ssp-buffer-size=4" LDFLAGS="-Wl,-z,relro" OBJCFLAGS="-g -O2 -fstack-protector --param=ssp-buffer-size=4 -Wformat -Werror=format-security" OBJCXXFLAGS="-g -O2 -fstack-protector --param=ssp-buffer-size=4 -Wformat -Werror=format-security"  --prefix=/usr --with-includes=/usr/include/postgresql:/usr/include/mysql --mandir=/usr/share/man --sysconfdir=/etc/proftpd --localstatedir=/run --libexecdir=/usr/lib/proftpd --enable-sendfile --enable-facl --enable-dso --enable-autoshadow --enable-ctrls --with-modules=mod_readme --enable-ipv6 --enable-nls --disable-memcache --with-lastlog=/var/log/lastlog --enable-pcre  --build x86_64-linux-gnu --with-shared=mod_unique_id:mod_site_misc:mod_load:mod_ban:mod_quotatab:mod_sql:mod_sql_mysql:mod_sql_postgres:mod_sql_sqlite:mod_sql_odbc:mod_dynmasq:mod_quotatab_sql:mod_ldap:mod_quotatab_ldap:mod_ratio:mod_tls:mod_rewrite:mod_radius:mod_wrap:mod_wrap2:mod_wrap2_file:mod_wrap2_sql:mod_quotatab_file:mod_quotatab_radius:mod_facl:mod_ctrls_admin:mod_copy:mod_deflate:mod_ifversion:mod_tls_memcache:mod_geoip:mod_exec:mod_sftp:mod_sftp_pam:mod_sftp_sql:mod_shaper:mod_sql_passwd:mod_ifsession
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



$GLOBALS["ROOT-DIR"]="/root/proftpd-builder";


if(is_dir($GLOBALS["ROOT-DIR"])){shell_exec("$rm -rf {$GLOBALS["ROOT-DIR"]}");}
create_package();


function PROFTPD_VERSION(){
	$unix=new unix();
	$proftpd=$unix->find_program("proftpd");
	exec("$proftpd -v 2>&1",$results);
	if(preg_match("#ProFTPD Version\s+([0-9\.]+)#i", @implode("", $results),$re)){return $re[1];}
}




function create_package(){
	$Architecture=Architecture();
	if($Architecture==64){$Architecture="x64";}
	if($Architecture==32){$Architecture="i386";}
	$WORKDIR=$GLOBALS["ROOT-DIR"];
	$version=PROFTPD_VERSION();
	@mkdir("$WORKDIR/sbin",0755,true);
	@mkdir("$WORKDIR/usr/sbin",0755,true);

	
	
	$fdir[]="/usr/lib/proftpd";
	$fdir[]="/etc/proftpd";

	while (list ($num, $ligne) = each ($fdir) ){
		@mkdir("$WORKDIR$ligne",0755,true);
		echo "Installing $ligne in $WORKDIR$ligne/\n";
		shell_exec("/bin/cp -rfd $ligne/* $WORKDIR$ligne/");
	}
	
	
	
	
	$f[]="/usr/bin/ftpasswd";
	$f[]="/usr/bin/ftpmail";
	$f[]="/usr/bin/ftpquota";
	$f[]="/usr/bin/ftpcount";
	$f[]="/usr/bin/ftpdctl";
	$f[]="/usr/sbin/ftpscrub";
	$f[]="/usr/sbin/ftpshut";
	$f[]="/usr/bin/ftptop";
	$f[]="/usr/bin/ftpwho";
	$f[]="/usr/bin/prxs";
	
	

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
	echo "Compressing proftpd-$Architecture-$version.tar.gz\n";
	if(is_file("/root/proftpd-$Architecture-$version.tar.gz")){@unlink("/root/proftpd-$Architecture-$version.tar.gz");}
	shell_exec("tar -czf /root/proftpd-$Architecture-$version.tar.gz *");
	echo "Compressing /root/proftpd-$Architecture-$version.tar.gz Done...\n";	
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










