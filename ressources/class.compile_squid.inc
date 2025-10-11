<?php


function create_package_hypercache(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	$Architecture=Architecture();
	$debian=DebianVersion();	
	system("$rm -rf /root/hypercache-builder");
	@mkdir("/root/hypercache-builder/etc/hypercache",0755,true);
	@mkdir("/root/hypercache-builder/usr/local/HyperCache",0755,true);
	
	system("$cp -rfd /etc/hypercache/* /root/hypercache-builder/etc/hypercache/");
	system("$cp -rfd /usr/local/HyperCache/* /root/hypercache-builder/usr/local/HyperCache/");
	system("cd /root/hypercache-builder");
	@chdir("/root/hypercache-builder");
	system("$tar -cvf /root/hypercache-builder.tar.gz *");
	
}

function ulogd_version(){
	
	exec("/usr/local/sbin/ulogd -V 2>&1",$results);
	
	foreach ($results as $line){
		
		if(preg_match("#ulogd Version\s+([0-9\.]+)#", $line,$re)){
			return $re[1];
		}
	}
	
}

function create_package_ulogd($ulogdbuilder="/root/ulogd-builder",$nocompile=false){
	
/*
 * 
git clone git://git.netfilter.org/libnfnetlink
cd libnfnetlink
./autogen.sh
./configure
make && make install

git clone git://git.netfilter.org/libmnl
cd libmnl
./autogen.sh
./configure
make && make install

git clone git://git.netfilter.org/libnetfilter_log
cd libnetfilter_log
./autogen.sh
./configure
make && make install

git clone git://git.netfilter.org/libnetfilter_conntrack
cd libnetfilter_conntrack
./autogen.sh
./configure
make && make install

git clone git://git.netfilter.org/libnetfilter_acct
cd libnetfilter_acct
autoreconf -fi
./configure
make && make install

git clone git://git.netfilter.org/ulogd2
cd ulogd2
./autogen.sh
./configure --without-mysql --without-dbi --with-pgsql=/usr/local/ArticaStats --with-pgsql-inc=/usr/local/ArticaStats/include --with-pgsql-lib=/usr/local/ArticaStats/lib LD_LIBRARY_PATH="/lib:/usr/local/lib:/usr/local/ArticaStats/lib:/usr/lib" LDFLAGS="-L/lib -L/usr/local/lib -L/usr/local/ArticaStats/lib -L/usr/lib"
make && make install
 
Debian 8 MINI
git clone git://git.code.sf.net/p/xtables-addons/xtables-addons
cd xtables-addons
./autogen.sh
 
 
 *
 *
 *
 *
 *
 */
	
	
	$unix=new unix();
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");	
	if(!$nocompile){system("$rm -rf $ulogdbuilder");}
	@mkdir("$ulogdbuilder/usr/local/lib/ulogd",0755,true);
	@mkdir("$ulogdbuilder/usr/local/sbin",0755,true);
	
	
	system("$cp -fd /usr/local/lib/libnfnetlink.la	$ulogdbuilder/usr/local/lib/");
	system("$cp -fd /usr/local/lib/libnfnetlink.so	$ulogdbuilder/usr/local/lib/");
	system("$cp -fd /usr/local/lib/libnfnetlink.so.0 $ulogdbuilder/usr/local/lib/");
	system("$cp -fd /usr/local/lib/libnfnetlink.so.0.2.0 $ulogdbuilder/usr/local/lib/");
	
	
	
	system("$cp -fd /usr/local/lib/libmnl.la $ulogdbuilder/usr/local/lib/");
	system("$cp -fd /usr/local/lib/libmnl.so $ulogdbuilder/usr/local/lib/");
	system("$cp -fd /usr/local/lib/libmnl.so.0 $ulogdbuilder/usr/local/lib/");	
	system("$cp -fd /usr/local/lib/libmnl.so.0.2.0 $ulogdbuilder/usr/local/lib/");
	
	system("$cp -fd /usr/local/lib/libnetfilter_log.la $ulogdbuilder/usr/local/lib/");  
	system("$cp -fd /usr/local/lib/libnetfilter_log_libipulog.la $ulogdbuilder/usr/local/lib/");  
	system("$cp -fd /usr/local/lib/libnetfilter_log_libipulog.so $ulogdbuilder/usr/local/lib/");	
	system("$cp -fd /usr/local/lib/libnetfilter_log_libipulog.so.1 $ulogdbuilder/usr/local/lib/");	
	system("$cp -fd /usr/local/lib/libnetfilter_log_libipulog.so.1.0.0 $ulogdbuilder/usr/local/lib/");  
	system("$cp -fd /usr/local/lib/libnetfilter_log.so $ulogdbuilder/usr/local/lib/");	
	system("$cp -fd /usr/local/lib/libnetfilter_log.so.1 $ulogdbuilder/usr/local/lib/");  
	system("$cp -fd /usr/local/lib/libnetfilter_log.so.1.1.0 $ulogdbuilder/usr/local/lib/");

	
	
	system("$cp -fd /usr/local/lib/libnetfilter_conntrack.la $ulogdbuilder/usr/local/lib/");    
	system("$cp -fd /usr/local/lib/libnetfilter_conntrack.so $ulogdbuilder/usr/local/lib/");    
	system("$cp -fd /usr/local/lib/libnetfilter_conntrack.so.3 $ulogdbuilder/usr/local/lib/");  	
	system("$cp -fd /usr/local/lib/libnetfilter_conntrack.so.3.6.0 $ulogdbuilder/usr/local/lib/");  
	
	system("$cp -fd /usr/local/lib/libnetfilter_acct.la $ulogdbuilder/usr/local/lib/");      
	system("$cp -fd /usr/local/lib/libnetfilter_acct.so $ulogdbuilder/usr/local/lib/");      
	system("$cp -fd /usr/local/lib/libnetfilter_acct.so.1 $ulogdbuilder/usr/local/lib/");      
	system("$cp -fd /usr/local/lib/libnetfilter_acct.so.1.0.0 $ulogdbuilder/usr/local/lib/");

	system("$cp -rfd /usr/local/lib/ulogd/* $ulogdbuilder/usr/local/lib/ulogd/");
	system("$cp -fd /usr/local/sbin/ulogd $ulogdbuilder/usr/local/sbin/");
	
	if(is_file("/usr/local/lib/libxt_ACCOUNT_cl.la")){
		system("$cp -fd /usr/local/lib/libxt_ACCOUNT_cl.la $ulogdbuilder/usr/local/lib/");
		system("$cp -fd /usr/local/lib/libxt_ACCOUNT_cl.so $ulogdbuilder/usr/local/lib/");	
		system("$cp -fd /usr/local/lib/libxt_ACCOUNT_cl.so.0 $ulogdbuilder/usr/local/lib/"); 
		system("$cp -fd /usr/local/lib/libxt_ACCOUNT_cl.so.0.0.0 $ulogdbuilder/usr/local/lib/");
	}
	
	if(is_file("/usr/local/sbin/iptaccount")){
		system("$cp -fd /usr/local/sbin/iptaccount $ulogdbuilder/usr/local/sbin/");
		
	}
	
	if(is_dir("/usr/local/libexec/xtables-addons")){
		@mkdir("$ulogdbuilder/usr/local/libexec/xtables-addons",0755,true);
		system("$cp -rfd /usr/local/libexec/xtables-addons/* $ulogdbuilder/usr/local/libexec/xtables-addons/");
	}
	
	if(is_dir("/lib/xtables")){
		@mkdir("$ulogdbuilder/lib/xtables",0755,true);
		system("$cp -rfd /lib/xtables/* $ulogdbuilder/lib/xtables/");
	}
	
	if(is_file("/lib/modules/3.16.0-4-amd64/extra/compat_xtables.ko")){
		@mkdir("$ulogdbuilder/lib/modules/3.16.0-4-amd64/extra",0755,true);
		
	}
	
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/compat_xtables.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_DHCPMAC.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_geoip.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_ipv4options.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_psd.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_CHAOS.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_DNETMAP.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_iface.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_length2.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_quota2.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_condition.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_ECHO.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_IPMARK.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_LOGMARK.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_SYSRQ.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_DELUDE.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_fuzzy.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_ipp2p.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_lscan.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_TARPIT.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	system("$cp -fd /lib/modules/3.16.0-4-amd64/extra/xt_ACCOUNT.ko $ulogdbuilder/lib/modules/3.16.0-4-amd64/extra/");
	
	
	if($nocompile){return;}
	
	$workingdir="$ulogdbuilder";
	$debian=DebianVersion();
	$version=ulogd_version();
	$Architecture=Architecture();
	
	echo "Compile Arch $Architecture v:$version Debian v:$debian on $ulogdbuilder\n";
	chdir("$workingdir");
	system("cd $workingdir");
	
	$TARGET_TGZ="/root/ulogd-debian$debian-$Architecture-$version.tar.gz";
	if(is_file($TARGET_TGZ)){@unlink($TARGET_TGZ);}
	echo "Compressing $TARGET_TGZ....\n";
	shell_exec("$tar -czf $TARGET_TGZ *");
	
	
	

	
}






function create_package_firehol(){
	$unix=new unix();
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	$strip=$unix->find_program("strip");	
	/*
wget https://github.com/firehol/iprange/releases/download/v1.0.3/iprange-1.0.3.tar.gz
tar -xf iprange-1.0.3.tar.gz 
cd iprange-1.0.3/
./autogen.sh
./configure
make
make install
cd /root/

wget http://ipset.netfilter.org/ipset-6.31.tar.bz2
tar -xf ipset-6.31.tar.bz2
cd ipset-6.31
./autogen.sh
./configure --prefix=/ --libdir=/lib/x86_64-linux-gnu/
make
make install
cd /root/

wget https://www.kernel.org/pub/linux/utils/net/bridge-utils/bridge-utils-1.6.tar.gz
tar -xf bridge-utils-1.6.tar.gz 
cd /root/bridge-utils-1.6/
autoconf
./configure --prefix=/usr
make 
make install 


wget https://github.com/firehol/firehol/releases/download/v3.1.3/firehol-3.1.3.tar.gz
tar -xf firehol-3.1.3.tar.gz
cd firehol-3.1.3
./autogen.sh
./configure
make 
make install
cd /root/


 */
	
	
	$buildPath="/root/firehol-builder";
	system("$strip -s /usr/local/bin/iprange");
	system("$strip -s /sbin/ipset");
	system("$strip /usr/sbin/brctl");
	
	if(is_dir($buildPath)){system("$rm -rf $buildPath");}
	
	@mkdir("$buildPath/usr/local/bin",0755,true);
	@mkdir("$buildPath/usr/local/sbin",0755,true);
	@mkdir("$buildPath/usr/local/etc/firehol",0755,true);
	@mkdir("$buildPath/sbin",0755,true);
	@mkdir("$buildPath/usr/sbin",0755,true);
	@mkdir("$buildPath/lib/x86_64-linux-gnu",0755,true);
	$version=_firehol_version();
	
	system("$cp -rfd /usr/local/bin/iprange $buildPath/usr/local/bin/iprange");
	system("$cp -rfd /usr/local/libexec/firehol/$version/* $buildPath/usr/local/sbin/");
	system("$cp -rfd /usr/local/etc/firehol/* $buildPath/usr/local/etc/firehol/");
	
	
	system("$cp -fd /usr/sbin/brctl $buildPath/sbin/bridge");
	system("$cp -fd /usr/sbin/brctl $buildPath/usr/sbin/brctl");
	system("$cp -fd /sbin/ipset $buildPath/sbin/");
	system("$cp -fd /usr/sbin/brctl $buildPath/usr/sbin/brctl");
	system("$cp -fd /lib/x86_64-linux-gnu/libipset.a $buildPath/lib/x86_64-linux-gnu/");
	system("$cp -fd /lib/x86_64-linux-gnu/libipset.la $buildPath/lib/x86_64-linux-gnu/");  
	system("$cp -fd /lib/x86_64-linux-gnu/libipset.so $buildPath/lib/x86_64-linux-gnu/");  
	system("$cp -fd /lib/x86_64-linux-gnu/libipset.so.3 $buildPath/lib/x86_64-linux-gnu/");  
	system("$cp -fd /lib/x86_64-linux-gnu/libipset.so.3.6.0 $buildPath/lib/x86_64-linux-gnu/");
	system("$cp -fd /usr/local/lib/libmnl.la $buildPath/usr/local/lib/");
	system("$cp -fd /usr/local/lib/libmnl.so $buildPath/usr/local/lib/");
	system("$cp -fd /usr/local/lib/libmnl.so.0 $buildPath/usr/local/lib/");
	system("$cp -fd /usr/local/lib/libmnl.so.0.2.0 $buildPath/usr/local/lib/");
	
	
	
	$debian=DebianVersion();
	$version=_firehol_version();
	$Architecture=Architecture();
	
	echo "Compile Arch $Architecture v:$version Debian v:$debian on $buildPath\n";
	chdir("$buildPath");
	system("cd $buildPath");
	
	$TARGET_TGZ="/root/firehol-debian$debian-$Architecture-$version.tar.gz";
	if(is_file($TARGET_TGZ)){@unlink($TARGET_TGZ);}
	echo "Compressing $TARGET_TGZ....\n";
	shell_exec("$tar -czf $TARGET_TGZ *");
	
}

function _firehol_version(){
	$unix=new unix();
	$firehol=$unix->find_program("firehol");
	exec("$firehol 2>&1",$f);
	foreach ($f as $num=>$filename){
		if(preg_match("#FireHOL\s+([0-9\.]+)#", $filename,$re)){
			return $re[1];
			
		}
	}
}

function create_package_netdata($buildPath="/root/netdata-builder",$nocompile=false){
	
	$unix=new unix();
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	
	if(!$nocompile){system("$rm -rf $buildPath");}
	
	@mkdir("$buildPath/var/lib/netdata",0755,true);
	@mkdir("$buildPath/usr/sbin",0755,true);
	@mkdir("$buildPath/usr/libexec/netdata",0755,true);
	@mkdir("$buildPath/etc/netdata",0755,true);
	@mkdir("$buildPath/usr/local/sbin",0755,true);
	@mkdir("$buildPath/usr/share/netdata",0755,true);
	@mkdir("$buildPath/usr/local/etc/netdata",0755,true);
	@mkdir("$buildPath/usr/local/libexec/netdata/plugins.d",0755,true);
	@mkdir("$buildPath/opt/netdata/usr/share/netdata/web",0755,true);
	@mkdir("$buildPath/sbin",0755,true);
	
	system("$cp -rfd /var/lib/netdata/* $buildPath/var/lib/netdata/");
	system("$cp -rfd /usr/share/netdata/* $buildPath/usr/share/netdata/");
	
	
	
	system("$cp -rfd /usr/libexec/netdata/* $buildPath/usr/libexec/netdata/");
	
	system("$cp -rfd /etc/netdata/* $buildPath/etc/netdata/");
	
	
	system("strip -s /usr/sbin/netdata");
	system("$cp /usr/sbin/netdata $buildPath/sbin/netdata");
	
	if(!$nocompile){
		system("cd $buildPath");
		@chdir("$buildPath");
		system("$tar -cvf /root/netdata-builder.tar.gz *");
	}
	
}

function create_package_websecurity(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	$Architecture=Architecture();
	$debian=DebianVersion();
	$version=C_ICAP_VERSION();
	
	$workingdir="/root/websecurity-builder";
	
	$preparrep[]="/usr/lib/c_icap";
	$preparrep[]="/usr/share/c_icap";
	$preparrep[]="/usr/include/c_icap";
	$preparrep[]="/usr/bin";
	$preparrep[]="/usr/include";
	$preparrep[]="/usr/sbin";
	
	shell_exec("/bin/rm -rf $workingdir");
	
	while (list ($num, $directory) = each ($preparrep) ){
		echo "Creating $workingdir$directory\n";
		@mkdir("$workingdir$directory",0755,true);
	}
	
	$Files=clamav_files();
	while (list ($num, $path) = each ($Files) ){
		if(is_file($path)){
			$cmd="$cp -fd $path $workingdir".dirname($path)."/";
			echo "$cmd\n";
			shell_exec($cmd);
		}
		
	}
	$Files=c_icap_files();
	while (list ($num, $path) = each ($Files) ){
		if(is_file($path)){
			$cmd="$cp -fd $path $workingdir".dirname($path)."/";
			echo "$cmd\n";
			shell_exec($cmd);
		}
	}
	
	

	shell_exec("/bin/cp -rfd /usr/share/c_icap/* $workingdir/usr/share/c_icap/");
	shell_exec("/bin/cp -rfd /usr/include/c_icap/* $workingdir/usr/include/c_icap/");
	shell_exec("/bin/cp -rfd /usr/lib/c_icap/* $workingdir/usr/lib/c_icap/");
	

	echo "Compile Arch $Architecture v:$version Debian v:$debian on $workingdir\n";
	chdir("$workingdir");
	system("cd $workingdir");
	
	$TARGET_TGZ="/root/websecurity-$Architecture-debian$debian-$version.tar.gz";
	if(is_file($TARGET_TGZ)){@unlink($TARGET_TGZ);}
	echo "Compressing $TARGET_TGZ....\n";
	shell_exec("$tar -czf $TARGET_TGZ *");
	
	
}

function create_package_squid($t){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	$Architecture=Architecture();
	$version=squid_version();
	$debian=DebianVersion();
		shell_exec("wget http://www.articatech.net/download/anthony-icons.tar.gz -O /tmp/anthony-icons.tar.gz");
	@mkdir("/usr/share/squid3/icons",0755,true);
	shell_exec("tar -xhf /tmp/anthony-icons.tar.gz -C /usr/share/squid3/icons/");
	shell_exec("/bin/chown -R squid:squid /usr/share/squid3/icons/");
	echo "Removing /root/squid-builder\n";
	
	$strip=$unix->find_program("strip");
	system("$strip -s /usr/sbin/squid");
	system("$strip -s /usr/sbin/squid27");
	system("$strip -s /usr/local/HyperCache/sbin/hypercache-service");
	system("$strip -s /usr/bin/c-icap-client");
	system("$strip -s /usr/bin/c-icap-mkbdb");
	system("$strip -s /usr/bin/c-icap-stretch");
	system("$strip -s /usr/bin/c-icap");
	system("$strip -s /usr/local/bin/ntopng");
	system("$strip -s /usr/bin/ncat");
	system("$strip -s /usr/bin/nmap");

	system("$strip -s /usr/sbin/unbound");
	system("$strip -s /usr/sbin/unbound-checkconf");
	system("$strip -s /usr/sbin/unbound-control");
	system("$strip -s /usr/sbin/unbound-host");
	system("$strip -s /usr/sbin/unbound-anchor");
	system("$strip -s /usr/sbin/unbound-control-setup");
	system("$strip -s /usr/local/bin/dsc");
	
	@unlink("/etc/squid3/squid.conf");
	@unlink("/etc/squid3/acls_center.conf");
	@unlink("/etc/squid3/ChildsProxy.conf");
	@unlink("/etc/squid3/external_acls.conf");
	@unlink("/etc/squid3/GlobalAccessManager_auth.conf");
	@unlink("/etc/squid3/GlobalAccessManager_deny.conf");
	@unlink("/etc/squid3/icap.conf");
	@unlink("/etc/squid3/mime.conf");
	@unlink("/etc/squid3/ufdbGuard.conf");
	@unlink("/etc/squid3/wccp.conf");
	@unlink("/etc/squid3/cachemgr.conf");
	@unlink("/etc/squid3/dangerous_extensions.conf");
	@unlink("/etc/squid3/freewebs.conf");
	@unlink("/etc/squid3/GlobalAccessManager_deny_cache.conf");
	@unlink("/etc/squid3/GlobalAccessManager_url_rewrite.conf");
	@unlink("/etc/squid3/listen_ports.conf");
	@unlink("/etc/squid3/refresh_patterns.conf");
	@unlink("/etc/squid3/ssl.conf");
	@unlink("/etc/squid3/url_rewrite_access.conf");
	
	sleep(1);
	shell_exec("/bin/rm -rf /root/squid-builder");
	@unlink("/etc/squid3/squid.conf");
	mkdir("/root/squid-builder/usr/local/bin",0755,true);
	mkdir("/root/squid-builder/usr/share/squid3",0755,true);
	mkdir("/root/squid-builder/usr/lib",0755,true);
	mkdir("/root/squid-builder/usr/lib32",0755,true);
	mkdir("/root/squid-builder/etc/squid3",0755,true);
	mkdir("/root/squid-builder/etc/miniupnpd/netfilter",0755,true);
	mkdir("/root/squid-builder/lib/squid3",0755,true);
	mkdir("/root/squid-builder/usr/sbin",0755,true);
	mkdir("/root/squid-builder/usr/bin",0755,true);
	mkdir("/root/squid-builder/usr/share/squid-langpack",0755,true);
	mkdir("/root/squid-builder/usr/share/sarg/images",0755,true);
	mkdir("/root/squid-builder/usr/etc",0755,true);
	mkdir("/root/squid-builder/usr/share/gettext/po",0755,true);
	mkdir("/root/squid-builder/opt/kaspersky/KasperskyUpdateUtility",0755,true);
	mkdir("/root/squid-builder/usr/share/nmap",0755,true);
	mkdir("/root/squid-builder/usr/share/squid27",0755,true);
	mkdir("/root/squid-builder/usr/share/man/man8",0755,true);
	mkdir("/root/squid-builder/usr/share/perl5",0755,true);
	mkdir("/root/squid-builder/usr/lib/squid27",0755,true);
	mkdir("/root/squid-builder/etc/squid27",0755,true);
	mkdir("/root/squid-builder/etc/streamcache",0755,true);
	mkdir("/root/squid-builder/usr/include/libecap/common",0755,true);
	mkdir("/root/squid-builder/usr/include/libecap/adapter",0755,true);
	mkdir("/root/squid-builder/usr/include/libecap/host",0755,true);
	mkdir("/root/squid-builder/usr/include/jasper",0755,true);
	mkdir("/root/squid-builder/usr/share/man/man1",0755,true);
	mkdir("/root/squid-builder/usr/lib/c_icap",0755,true);
	mkdir("/root/squid-builder/opt/ufdbcat",0755,true);
	mkdir("/root/squid-builder/usr/local/sbin",0755,true);
	mkdir("/root/squid-builder/usr/local/lib",0755,true);
	mkdir("/root/squid-builder/usr/local/share/ntopng",0755,true);
	mkdir("/root/squid-builder/var/lib/ss5/ss5",0755,true);
	@mkdir("/root/squid-builder/usr/lib/openvpn",0755,true);
	@mkdir("/root/squid-builder/usr/sbin",0755,true);
	@mkdir("/root/squid-builder/usr/local/sbin",0755,true);
	@mkdir("/root/squid-builder/usr/local/etc",0755,true);
	@mkdir("/root/squid-builder/lib/modules/3.16.0-4-amd64/kernel/net/pf_ring",0755,true);
	@mkdir("/root/squid-builder/usr/lib/python2.7/dist-packages",0755,true);
	@mkdir("/root/squid-builder/usr/lib/x86_64-linux-gnu",0755,true);
	@mkdir("/root/squid-builder/lib/x86_64-linux-gnu",0755,true);
	
	@mkdir("/root/squid-builder/usr/local/libexec/netdata",0755,true);
	@mkdir("/root/squid-builder/usr/local/share/netdata",0755,true);
	@mkdir("/root/squid-builder/usr/local/var/cache/netdata",0755,true);
	@mkdir("/root/squid-builder/usr/local/sbin",0755,true);
	@mkdir("/root/squid-builder/etc/hypercache",0755,true);
	@mkdir("/root/squid-builder/etc/unbound",0755,true);
	@mkdir("/root/squid-builder/usr/local/HyperCache",0755,true);
	@mkdir("/root/squid-builder/etc/artica-postfix/settings/Daemons",0755,true);
	
	shell_exec("$cp -fd /usr/lib/python2.7/dist-packages/unboundmodule.py /root/squid-builder/usr/lib/python2.7/dist-packages/");
	shell_exec("$cp -fd /usr/lib/libunbound.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libunbound.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libunbound.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libunbound.so.2 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libunbound.so.2.4.2 /root/squid-builder/usr/lib/");

	shell_exec("$cp -fd /usr/sbin/unbound /root/squid-builder/usr/sbin/");
	shell_exec("$cp -fd /usr/sbin/unbound-checkconf /root/squid-builder/usr/sbin/");
	shell_exec("$cp -fd /usr/sbin/unbound-control /root/squid-builder/usr/sbin/");
	shell_exec("$cp -fd /usr/sbin/unbound-host /root/squid-builder/usr/sbin/");
	shell_exec("$cp -fd /usr/sbin/unbound-anchor /root/squid-builder/usr/sbin/");
	shell_exec("$cp -fd /usr/sbin/unbound-control-setup /root/squid-builder/usr/sbin/");
	
	shell_exec("$cp -fd /usr/sbin/msktutil /root/squid-builder/usr/sbin/");
	
	shell_exec("$cp -fd /usr/lib/libevent_core-2.0.so.5 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_extra-2.0.so.5 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_openssl-2.0.so.5 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_pthreads-2.0.so.5 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_core-2.0.so.5.1.9 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_extra-2.0.so.5.1.9 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_openssl-2.0.so.5.1.9 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_pthreads-2.0.so.5.1.9 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_core.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_extra.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_openssl.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_pthreads.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_core.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_extra.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_openssl.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_pthreads.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_core.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_extra.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_openssl.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libevent_pthreads.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/local/bin/dsc /root/squid-builder/usr/local/bin/");
	
	shell_exec("$cp -rfd /etc/hypercache/* /root/squid-builder/etc/hypercache/");
	shell_exec("$cp -rfd /usr/local/HyperCache/* /root/squid-builder/usr/local/HyperCache/");
	shell_exec("$cp -rfd /usr/share/squid3/* /root/squid-builder/usr/share/squid3/");
	shell_exec("$cp -rfd /etc/squid3/* /root/squid-builder/etc/squid3/");
	shell_exec("$cp -rfd /lib/squid3/* /root/squid-builder/lib/squid3/");
	shell_exec("$cp -rfd /usr/share/squid-langpack/* /root/squid-builder/usr/share/squid-langpack/");
	shell_exec("$cp -rfd /usr/sbin/squid /root/squid-builder/usr/sbin/squid");
	shell_exec("$cp -rfd /usr/bin/purge /root/squid-builder/usr/bin/purge");
	shell_exec("$cp -rfd /usr/bin/squidclient /root/squid-builder/usr/bin/squidclient");
	shell_exec("$cp -rfd /usr/sbin/vnstatd /root/squid-builder/usr/sbin/vnstatd");
	shell_exec("$cp -rfd /usr/bin/pactester /root/squid-builder/usr/bin/pactester");
	shell_exec("$cp -rfd /usr/lib/libpacparser.so.1 /root/squid-builder/usr/lib/libpacparser.so.1");
	shell_exec("$cp -rfd /usr/share/sarg/images/* /root/squid-builder/usr/share/sarg/images/");
	shell_exec("$cp -rfd /opt/kaspersky/﻿KasperskyUpdateUtility/* /root/squid-builder/opt/kaspersky/﻿KasperskyUpdateUtility/");
	shell_exec("$cp -rfd /usr/local/libexec/netdata/* /root/squid-builder/usr/local/libexec/netdata/");
	shell_exec("$cp -rfd /usr/local/share/netdata/* /root/squid-builder/usr/local/share/netdata/");
	shell_exec("$cp -rfd /usr/local/var/cache/netdata/* /root/squid-builder/usr/local/var/cache/netdata/");
	
	
	shell_exec("$cp -fd /usr/local/sbin/haproxy /root/squid-builder/usr/local/sbin/haproxy");
	shell_exec("$cp -fd /usr/local/sbin/haproxy-systemd-wrapper /root/squid-builder/usr/local/sbin/haproxy-systemd-wrapper");
	shell_exec("$cp -fd /usr/bin/nping /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/ncat /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/nmapfe /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/zenmap /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/nmap /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap.dtd /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap-mac-prefixes /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap-os-db /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap-payloads /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap-protocols /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap-rpc /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap-service-probes /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap-services /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nmap.xsl /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/share/nmap/nse_main.lua /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/redsocks /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/openvpn /root/squid-builder/usr/bin/openvpn");
	
	shell_exec("$cp -fd /usr/lib/x86_64-linux-gnu/liblz4.so /root/squid-builder/usr/lib/x86_64-linux-gnu/");
	shell_exec("$cp -fd /usr/lib/x86_64-linux-gnu/liblz4.so.1 /root/squid-builder/usr/lib/x86_64-linux-gnu/");
	shell_exec("$cp -fd /usr/lib/x86_64-linux-gnu/liblz4.so.1.7.5 /root/squid-builder/usr/lib/x86_64-linux-gnu/");
	
	
	shell_exec("$cp -fd /usr/local/sbin/privoxy /root/squid-builder/usr/local/sbin/privoxy");

	shell_exec("$cp -rfd /usr/share/nmap/* /root/squid-builder/usr/share/nmap/");
	shell_exec("$cp -rfd /usr/lib/openvpn/* /root/squid-builder/usr/lib/openvpn/");
	shell_exec("$cp -rfd /usr/local/etc/privoxy /root/squid-builder/usr/local/etc/");
	
	shell_exec("$cp -rfd /usr/include/libecap/common/* /root/squid-builder/usr/include/libecap/common/");
	shell_exec("$cp -rfd /usr/include/libecap/adapter/* /root/squid-builder/usr/include/libecap/adapter/");
	shell_exec("$cp -rfd /usr/include/libecap/host/* /root/squid-builder/usr/include/libecap/host/");
	shell_exec("$cp -rfd /usr/include/jasper/* /root/squid-builder/usr/include/jasper/");

	shell_exec("$cp -fd /usr/lib/libecap.so.3.0.0 /root/squid-builder/usr/lib/libecap.so.3.0.0");
	shell_exec("$cp -fd /usr/lib/libecap.so.3 /root/squid-builder/usr/lib/libecap.so.3");
	shell_exec("$cp -fd /usr/lib/libecap.so /root/squid-builder/usr/lib/libecap.so");
	shell_exec("$cp -fd /usr/lib/libecap.la /root/squid-builder/usr/lib/libecap.la");
	
	shell_exec("$cp -fd /usr/lib/ecap_clamav_adapter.so /root/squid-builder/usr/lib/ecap_clamav_adapter.so");
	shell_exec("$cp -fd /usr/lib/ecap_clamav_adapter.la /root/squid-builder/usr/lib/ecap_clamav_adapter.la");
	shell_exec("$cp -fd /usr/lib/ecap_clamav_adapter.a /root/squid-builder/usr/lib/ecap_clamav_adapter.a");
	
	shell_exec("$cp -fd /usr/lib/ecap_adapter_gzip.so /root/squid-builder/usr/lib/ecap_adapter_gzip.so");
	shell_exec("$cp -fd /usr/lib/ecap_adapter_gzip.la /root/squid-builder/usr/lib/ecap_adapter_gzip.la");
	shell_exec("$cp -fd /usr/lib/ecap_adapter_gzip.a /root/squid-builder/usr/lib/ecap_adapter_gzip.a");

	shell_exec("$cp -fd /usr/lib/libnettle.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libnettle.so /root/squid-builder/usr/lib/"); 
	shell_exec("$cp -fd /usr/lib/libnettle.so.6 /root/squid-builder/usr/lib/");  
	shell_exec("$cp -fd /usr/lib/libnettle.so.6.1 /root/squid-builder/usr/lib/");
	
	
	shell_exec("$cp -fd /usr/lib/libgif.so.6.0.1 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libgif.so.6 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libgif.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libgif.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libgif.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /etc/artica-postfix/settings/Daemons/WANPROXY_VERSION /root/squid-builder/etc/artica-postfix/settings/Daemons/");
	shell_exec("$cp -fd /root/wanproxy/programs/wanproxy/wanproxy /root/squid-builder/usr/local/bin/wanproxy");
	shell_exec("$cp -fd /usr/include/gif_lib.h /root/squid-builder/usr/include/");
	shell_exec("$cp -fd /usr/lib/libgif.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libgif.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libgif.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libungif.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libungif.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libungif.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libjasper.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libjasper.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/bin/jasper /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/imgcmp /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/imginfo /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/tmrdemo /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/ziproxylogtool  /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/bin/ziproxy  /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /root/squid-builder/usr/share/man/man1/ziproxy.1 /root/squid-builder/usr/share/man/man1/");
	shell_exec("$cp -fd /root/squid-builder/usr/share/man/man1/ziproxylogtool.1 /root/squid-builder/usr/share/man/man1/");
	shell_exec("$cp -fd /etc/squid3/README_ZIPPROXY /root/squid-builder/etc/squid3/");
	shell_exec("$cp -fd /usr/lib/libecap.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libecap.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libecap.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/ecap_adapter_gzip.la /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/ecap_adapter_gzip.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/ecap_adapter_gzip.a /root/squid-builder/usr/lib/");
	
	//ntop
	shell_exec("$cp -fd /usr/lib/libgio-2.0.la	/root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libgio-2.0.so	/root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libgio-2.0.so.0 /root/squid-builder/usr/lib/"); 
	shell_exec("$cp -fd /usr/lib/libgio-2.0.so.0.2800.7 /root/squid-builder/usr/lib/");
	
	shell_exec("$cp -fd /usr/local/lib/libpcap.a /root/squid-builder/usr/local/lib/");
	shell_exec("$cp -fd /usr/local/lib/libpcap.so  /root/squid-builder/usr/local/lib/");
	shell_exec("$cp -fd /usr/local/lib/libpcap.so.1  /root/squid-builder/usr/local/lib/");  
	shell_exec("$cp -fd /usr/local/lib/libpcap.so.1.7.4  /root/squid-builder/usr/local/lib/");

	
	shell_exec("$cp -fd /usr/bin/upnpc /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/lib/libminiupnpc.so.10 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/lib/libminiupnpc.so /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/sbin/miniupnpd /root/squid-builder/usr/sbin/");
	shell_exec("$cp -fd /etc/miniupnpd/netfilter/iptables_init.sh /root/squid-builder/etc/miniupnpd/netfilter/");
	shell_exec("$cp -fd /etc/miniupnpd/netfilter/iptables_removeall.sh /root/squid-builder/etc/miniupnpd/netfilter/");
	shell_exec("$cp -fd /etc/miniupnpd/netfilter/ip6tables_init.sh /root/squid-builder/etc/miniupnpd/netfilter/");
	shell_exec("$cp -fd /etc/miniupnpd/netfilter/ip6tables_removeall.sh /root/squid-builder/etc/miniupnpd/netfilter/");
	shell_exec("$cp -fd /etc/miniupnpd/miniupnpd.conf /root/squid-builder/etc/miniupnpd/");
	shell_exec("$cp -fd /usr/share/man/man8/miniupnpd.8 /root/squid-builder/usr/share/man/man8/");
	shell_exec("$cp -fd /usr/share/perl5/SquidAnalyzer.pm /root/squid-builder/usr/share/perl5/SquidAnalyzer.pm");
	
	
	
	shell_exec("$cp -rfd /usr/lib/c_icap/* /root/squid-builder/usr/lib/c_icap/");
	shell_exec("$cp -rfd /usr/share/squid27/* /root/squid-builder/usr/share/squid27/");
	shell_exec("$cp -rfd /usr/lib/squid27/* /root/squid-builder/usr/lib/squid27/");
	shell_exec("$cp -rfd /etc/squid27/* /root/squid-builder/etc/squid27/");
	shell_exec("$cp -rfd /etc/streamcache/* /root/squid-builder/etc/streamcache/*");
	shell_exec("$cp -rfd /opt/ufdbcat/* /root/squid-builder/opt/ufdbcat/");
	shell_exec("$cp -rfd /var/lib/ss5/ss5/* /root/squid-builder/var/lib/ss5/ss5/");
	
	shell_exec("$cp -fd /usr/local/lib/libhttpd.a /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/local/lib/libhttpd.so.0.0.0 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/local/lib/libhttpd.so.0 /root/squid-builder/usr/lib/");
	shell_exec("$cp -fd /usr/local/bin/wifidog /root/squid-builder/usr/bin/");
	shell_exec("$cp -fd /usr/local/bin/wdctl /root/squid-builder/usr/bin/");
	
	if(is_file("/lib/x86_64-linux-gnu/liblzo2.so.2")){
		system("cp -fd /lib/x86_64-linux-gnu/liblzo2.so.2 /root/squid-builder/lib/x86_64-linux-gnu/");
		system("cp -fd /lib/x86_64-linux-gnu/liblzo2.so.2.0.0 /root/squid-builder/x86_64-linux-gnu/");
	}

	@file_put_contents("/etc/ld.so.conf.d/cicap.conf", "/usr/lib/c_icap\n");
	
	$f[]="/usr/lib/libssl.so.0.9.8";
	$f[]="/usr/lib/libcrypto.so.0.9.8";
	$f[]="/usr/lib/libboost_program_options.so.1.42.0";
	$f[]="/usr/bin/bwm-ng";
	$f[]="/usr/share/info/msmtp.info";
	$f[]="/etc/ld.so.conf.d/KasperskyUpdateUtility.conf";
	$f[]="/etc/ld.so.conf.d/cicap.conf";
	
	$f[]="/usr/bin/msmtp";
	$f[]="/usr/sbin/dnsmasq";
	$f[]="/usr/share/gettext/po";
	$f[]="/usr/etc/css.tpl";
	$f[]="/usr/etc/exclude_codes";
	$f[]="/usr/bin/sarg";
	$f[]="/usr/sbin/cntlm";
	$f[]="/usr/lib/libmysqlclient.so.18";
	$f[]="/usr/lib/libmysqlclient.so.18.0.0";
	$f[]="/usr/lib/libicapapi.la";
	$f[]="/usr/lib/libicapapi.so";
	$f[]="/usr/lib/libicapapi.so.5";
	$f[]="/usr/lib/libicapapi.so.5.0.2";
	
	$f[]="/usr/bin/c-icap";
	$f[]="/usr/bin/c-icap-client";
	$f[]="/usr/bin/c-icap-config";
	$f[]="/usr/bin/c-icap-libicapapi-config";
	$f[]="/usr/bin/c-icap-mkbdb";
	$f[]="/usr/bin/c-icap-mods-sguardDB";
	$f[]="/usr/bin/c-icap-stretch";
	
	
	$f[]="/usr/bin/ufdbguardd";
	$f[]="/usr/bin/ufdbgclient";
	$f[]="/usr/bin/ufdb-pstack";
	$f[]="/usr/bin/ufdbConvertDB";
	$f[]="/usr/bin/ufdbGenTable";
	$f[]="/usr/bin/ufdbAnalyse";
	$f[]="/usr/bin/ufdbhttpd";
	$f[]="/usr/bin/ufdbUpdate";
	$f[]="/usr/lib32/libglib-2.0.so.0";
	$f[]="/usr/lib32/liblzma.so.5";
	$f[]="/usr/lib32/libpcre.so.3";
	$f[]="/usr/lib32/libQtCLucene.so.4";
	$f[]="/usr/lib32/libQtCLucene.so.4.8.2";
	$f[]="/usr/lib32/libQtCore.so.4.8";
	$f[]="/usr/lib32/libQtXml.so.4";
	$f[]="/usr/lib32/libQtXml.so.4.8.2";
	$f[]="/usr/lib32/libxml2.so.2.8.0";
	$f[]="/usr/lib32/libglib-2.0.so.0.3200.4";
	$f[]="/usr/lib32/liblzma.so.5.0.0";
	$f[]="/usr/lib32/libpcre.so.3.13.1";
	$f[]="/usr/lib32/libQtCLucene.so.4.8";
	$f[]="/usr/lib32/libQtCore.so.4";
	$f[]="/usr/lib32/libQtCore.so.4.8.2";
	$f[]="/usr/lib32/libQtXml.so.4.8";
	$f[]="/usr/lib32/libxml2.so.2";
	$f[]="/usr/sbin/RunCache27";
	$f[]="/usr/sbin/squidclient27";
	$f[]="/usr/sbin/cossdump27";
	$f[]="/usr/sbin/squid27";
	$f[]="/usr/sbin/streamRunCachecache";
	$f[]="/usr/sbin/streamsquidcache";
	$f[]="/usr/local/sbin/vsftpd";
	$f[]="/usr/local/man/man8/vsftpd.8";
	$f[]="/usr/man/man8/vsftpd.8";
	$f[]="/usr/share/man/man5/vsftpd.conf.5";
	$f[]="/usr/share/man/man8/vsftpd.8";
	$f[]="/usr/lib/libsocks.so.0.1.1";
	$f[]="/usr/lib/libsocks.so";
	$f[]="/usr/lib/libsocks.la";
	$f[]="/usr/lib/libsocks.a";
	$f[]="/usr/sbin/sockd";
	$f[]="/usr/share/man/man8/sockd.8";
	$f[]="/usr/share/man/man1/socksify.1";
	$f[]="/usr/share/man/man5/sockd.conf.5";
	$f[]="/usr/share/man/man5/socks.conf.5";
	$f[]="/usr/bin/socksify";
	$f[]="/usr/include/socks.h";
	
	$f[]="/usr/sbin/ss5";
	$f[]="/usr/sbin/ss5srv";
	$f[]="/usr/local/bin/krake";
	$f[]="/usr/bin/squid-analyzer";

	//  mkdir -p /var/spool/squid27/cache && mkdir /var/spool/squid27/logs && chown -R nobody:nobody /var/spool/squid27 && squid27 -z
	// pour démarer squid27 : /usr/sbin/squid27



	//ldconfig -n /usr/lib/conntrack-tools
	
	@unlink("/etc/squid3/squid.conf");
	@unlink("/etc/squid3/acls_center.conf");
	@unlink("/etc/squid3/ChildsProxy.conf");
	@unlink("/etc/squid3/external_acls.conf");
	@unlink("/etc/squid3/GlobalAccessManager_auth.conf");
	@unlink("/etc/squid3/GlobalAccessManager_deny.conf");
	@unlink("/etc/squid3/icap.conf");
	@unlink("/etc/squid3/mime.conf");
	@unlink("/etc/squid3/ufdbGuard.conf");
	@unlink("/etc/squid3/wccp.conf");
	@unlink("/etc/squid3/cachemgr.conf");
	@unlink("/etc/squid3/dangerous_extensions.conf");
	@unlink("/etc/squid3/freewebs.conf");	 
	@unlink("/etc/squid3/GlobalAccessManager_deny_cache.conf");
	@unlink("/etc/squid3/GlobalAccessManager_url_rewrite.conf");
	@unlink("/etc/squid3/listen_ports.conf");
	@unlink("/etc/squid3/refresh_patterns.conf");
	@unlink("/etc/squid3/ssl.conf");
	@unlink("/etc/squid3/url_rewrite_access.conf");

	foreach ($f as $num=>$ligne){
		if(is_dir($ligne)){
			shell_exec("/bin/cp -rfd $ligne/* /root/squid-builder$ligne/");
			continue;
		}

		if(!is_file($ligne)){echo "$ligne no such file\n";continue;}
		$dir=dirname($ligne);
		echo "Installing $ligne in /root/squid-builder$dir/\n";
		if(!is_dir("/root/squid-builder$dir")){@mkdir("/root/squid-builder$dir",0755,true);}
		shell_exec("/bin/cp -fd $ligne /root/squid-builder$dir/");

}
package_redemption();


$CICAP=c_icap_array();
while (list ($num, $filename) = each ($CICAP) ){
	if(is_dir($filename)){
		@mkdir("/root/squid-builder$filename",0755,true);
		shell_exec("/bin/cp -rfd $filename/* /root/squid-builder$filename/");
		continue;
	}
	$dir=dirname($filename);
	echo "Installing $filename in /root/squid-builder$dir/\n";
	if(!is_dir("/root/squid-builder$dir")){@mkdir("/root/squid-builder$dir",0755,true);}
	shell_exec("/bin/cp -fd $filename /root/squid-builder$dir/");
}

proftpd("/root/squid-builder",true);

echo "Debian Version: $debian\n";
echo "Compile SARG....\n";
compile_sarg();

if($debian>6){
$debianv="-debian$debian";
}
if($Architecture==64){$Architecture="x64";}
if($Architecture==32){$Architecture="i386";}
echo "Compile Arch $Architecture v:$version Debian v:$debian \n";
chdir("/root/squid-builder");

$TARGET_TGZ="/root/squid32-$Architecture$debianv-$version.tar.gz";

$version=squid_version();
echo "Compressing $TARGET_TGZ....\n";
shell_exec("$tar -czf $TARGET_TGZ *");

if(!is_file($TARGET_TGZ)){
	echo "$TARGET_TGZ no such file ???\n";
	return;
}

echo "********************************************\n";
echo "$TARGET_TGZ\n";
echo "********************************************\n\n";


squid_admin_mysql(2, "$TARGET_TGZ  ready...",__FUNCTION__,__FILE__,__LINE__);
if(is_file("/root/ftp-password")){
	echo "$TARGET_TGZ is now ready to be uploaded\n";
	shell_exec("curl -T $TARGET_TGZ ftp://articatech.net/download/ --user ".@file_get_contents("/root/ftp-password"));
	squid_admin_mysql(2, "Uploading $TARGET_TGZ done.",__FUNCTION__,__FILE__,__LINE__);
	if(is_file("/root/rebuild-artica")){shell_exec("$wget \"".@file_get_contents("/root/rebuild-artica")."\" -O /tmp/rebuild.html");}

}

	shell_exec("/etc/init.d/artica-postfix restart squid-cache");
$took=$unix->distanceOfTimeInWords($t,time(),true);
squid_admin_mysql(2, "Installing the new squid-cache $version success took:$took", __FUNCTION__, __FILE__, __LINE__, "software");
}

function proftpd($base=null,$nocompress=false){
	// apt-get install libpq-dev
	// ./configure --prefix=/usr --with-includes=/usr/include/postgresql --mandir=/usr/share/man --sysconfdir=/etc/proftpd --localstatedir=/var/run --libexecdir=/usr/lib/proftpd --enable-sendfile --enable-facl --enable-dso --enable-autoshadow --enable-ctrls --with-modules=mod_readme --enable-ipv6  --build x86_64-linux-gnu --with-shared=mod_quotatab:mod_sql:mod_sql_mysql:mod_sql_postgres:mod_quotatab_sql:mod_ldap:mod_quotatab_ldap:mod_ratio:mod_tls:mod_rewrite:mod_radius:mod_wrap:mod_quotatab_file:mod_facl:mod_ctrls_admin:mod_ifsession
	$f[]="/usr/sbin/proftpd";

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


	if($base==null){
		$base="/root/proftpd-build";
	}
	
	

	foreach ($f as $num=>$filename){

		if(is_dir($filename)){
			@mkdir("$base/$filename",0755,true);
			echo "/bin/cp -rfd $filename/* $base$filename/\n";
			shell_exec("/bin/cp -rfd $filename/* $base$filename/");
			continue;
		}


		if(is_file($filename)){
			$dirname=dirname($filename);
			if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
			echo "/bin/cp -rfd $filename $base$dirname/\n";
			shell_exec("/bin/cp -rfd $filename $base/$dirname/");
		}

	}
	@mkdir("$base/usr/lib/proftpd",0755,true);
	shell_exec("/bin/cp -rfd /usr/lib/proftpd/* $base/usr/lib/proftpd/");

	if(!$nocompress){
		$Debian=DebianVersion();
		$Architecture=Architecture();
		$version=proftpd_version();
		chdir($base);
		if(is_file("$base/proftpd-debian{$Debian}-$Architecture-$version.tar.gz")){
			@unlink("$base/proftpd-debian{$Debian}-$Architecture-$version.tar.gz");
		}
	
		$filename="proftpd-debian{$Debian}-$Architecture-$version.tar.gz";
		shell_exec("tar -czf $filename *");
		shell_exec("/bin/cp $filename /root/");
		echo "/root/$filename done\n";
	}


}

function proftpd_version(){
$unix=new unix();
$proftpd=$unix->find_program("proftpd");
	exec("$proftpd -v 2>&1",$results);

	foreach ($results as $num=>$val){
	if(preg_match("#ProFTPD Version\s+([0-9\.]+)#", $val,$re)){
	$GLOBALS["proftpd_version"]=trim($re[1]);
	return $GLOBALS["proftpd_version"];
		}
	}

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
	
	$base="/root/ufdbGuard-compiled";
	@mkdir($base,0755,true);
	@mkdir($base."/opt/ufdbcat",0755,true);
	if($GLOBALS["NOSOCKETS"]){
		@mkdir("$base/etc/artica-postfix",0755,true);
		@touch("$base/etc/artica-postfix/UFDB_NO_SOCKETS");
	
	}
	
	
	foreach ($f as $num=>$filename){
		$dirname=dirname($filename);
		if(!is_dir("$base/$dirname")){@mkdir("$base/$dirname",0755,true);}
		shell_exec("/bin/cp -fd $filename $base/$dirname/");

	}

	shell_exec("/bin/cp -rfd /opt/ufdbcat/* $base/opt/ufdbcat/");
	$Debian=DebianVersion();
	$Architecture=Architecture();
	$version=ufdbguardVersion();
	chdir("/root/ufdbGuard-compiled");
	shell_exec("tar -czf ufdbGuard-debian{$Debian}-$Architecture-$version.tar.gz *");
	shell_exec("/bin/cp ufdbGuard-debian{$Debian}-$Architecture-$version.tar.gz /root/");
	echo "/root/ufdbGuard-debian{$Debian}-$Architecture-$version.tar.gz done";


}

function ufdbguardVersion(){
	exec("/root/ufdbGuard-compiled/usr/bin/ufdbguardd -v 2>&1",$results);
	foreach ($results as $num=>$line){
		if(preg_match("#ufdbguardd.*?([0-9\.]+)#", $line,$re)){return $re[1];}
	}


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

function c_icap_files(){
	$f[]="/usr/lib/libicapapi.la";
	$f[]="/usr/lib/libicapapi.so";
	$f[]="/usr/lib/libicapapi.so.5";
	$f[]="/usr/lib/libicapapi.so.5.0.2";
	$f[]="/usr/bin/c-icap";
	$f[]="/usr/bin/c-icap-client";
	$f[]="/usr/bin/c-icap-config";
	$f[]="/usr/bin/c-icap-libicapapi-config";
	$f[]="/usr/bin/c-icap-mkbdb";
	$f[]="/usr/bin/c-icap-mods-sguardDB";
	$f[]="/usr/bin/c-icap-stretch";
	return $f;
}

function clamav_files(){
	
	$f[]="/usr/lib/libclamunrar.so.7.1.1";
	$f[]="/usr/lib/libclamunrar_iface.so.7.1.1";
	$f[]="/usr/lib/libclamav.so.7.1.1";
	$f[]="/usr/lib/libclamunrar.so.7";
	$f[]="/usr/lib/libclamunrar_iface.so.7";
	$f[]="/usr/lib/libclamav.so.7";
	
	$f[]="/usr/lib/libclamunrar.so";
	$f[]="/usr/lib/libclamunrar_iface.so";
	$f[]="/usr/lib/libclamav.so";
	
	$f[]="/usr/lib/libclamunrar.la";
	$f[]="/usr/lib/libclamav.la";
	$f[]="/usr/lib/libclamunrar_iface.la";
	
	$f[]="/usr/include/clamav.h";
	
	$f[]="/usr/sbin/clamd";
	$f[]="/usr/sbin/clamav-milter";
	
	$f[]="/usr/bin/clamdscan";
	$f[]="/usr/bin/freshclam";
	$f[]="/usr/bin/sigtool";
	$f[]="/usr/bin/clamconf";
	$f[]="/usr/bin/clamscan";
	$f[]="/usr/bin/clambc";
	$f[]="/usr/bin/clamsubmit";
	$f[]="/usr/bin/clamav-config";
	return $f;
}

function compile_sarg(){

	@mkdir("/root/squid-builder/usr/bin",0755,true);
	@mkdir("/root/squid-builder/usr/share/locale",0755,true);


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

function package_redemption(){

	/* apt-get instal boost-build libboost-dev  libboost-test-dev libboost-program-options-dev libssl-dev
	 wget https://github.com/wallix/redemption/archive/master.zip
	mkdir redemption && cp master.zip redemption/
	cd redemption && unzip master.zip
	cd redemption-master
	bjam
	bjam install
	*/


	
	
	$f["/usr/local/share/rdpproxy"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_16.fv1"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b.png"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_24.fv1"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b.bmp"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_10.fv1"]=true;
	$f["/usr/local/share/rdpproxy/ad24b-xxx.bmp"]=true;
	$f["/usr/local/share/rdpproxy/wablogoblue.png"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b-redemption.bmp"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_18.fv1"]=true;
	$f["/usr/local/share/rdpproxy/ad24b.bmp"]=true;
	$f["/usr/local/share/rdpproxy/Philips_PM5544_192.bmp"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_14.fv1"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b-redemption.png"]=true;
	$f["/usr/local/share/rdpproxy/ad160-24b.bmp"]=true;
	$f["/usr/local/share/rdpproxy/Philips_PM5544_640.png"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_13.fv1"]=true;
	$f["/usr/local/share/rdpproxy/ad24b.png"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b.jpg"]=true;
	$f["/usr/local/share/rdpproxy/Philips_PM5544_640.bmp"]=true;
	$f["/usr/local/share/rdpproxy/sans-10.fv1"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_12.fv1"]=true;
	$f["/usr/local/share/rdpproxy/ad8b.png"]=true;
	$f["/usr/local/share/rdpproxy/ad8b.bmp"]=true;
	$f["/usr/local/bin/rdpproxy"]=true;
	$f["/usr/local/bin/redrec"]=true;
	$f["/etc/rdpproxy"]=true;
	$f["/etc/rdpproxy/rsakeys.ini"]=true;
	$f["/etc/rdpproxy/rdpproxy.ini"]=true;
	$f["/etc/rdpproxy/dh1024.pem"]=true;
	$f["/etc/rdpproxy/rdpproxy-cert.pem"]=true;
	$f["/etc/rdpproxy/dh2048.pem"]=true;
	$f["/etc/rdpproxy/rdpproxy-key.pem"]=true;
	$f["/etc/rdpproxy/rdpproxy.crt"]=true;
	$f["/etc/rdpproxy/rdpproxy.key"]=true;
	$f["/etc/rdpproxy/rdpproxy.p12"]=true;
	$f["/usr/local/share/rdpproxy/cursor0.cur"]=true;
	$f["/usr/local/share/rdpproxy/xrdp24b-wallix.bmp"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_11.fv1"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_20.fv1"]=true;
	$f["/usr/local/share/rdpproxy/cursor1.cur"]=true;
	$f["/usr/local/share/rdpproxy/FreeSans.ttf"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_32.fv1"]=true;
	$f["/usr/local/share/rdpproxy/dejavu_48.fv1"]=true;


	$base="/root/squid-builder";
	@mkdir("$base",0755,true);

	while (list ( $filename,$none) = each ($f)){
		if(is_file($filename)){
			$dirname=dirname($filename);
			$newbase="$base/$dirname";
			@mkdir("$newbase",0755,true);
			echo "Copy $filename to $newbase\n";
			shell_exec("/bin/cp $filename $newbase/");
		}

	}
	@mkdir("$base/etc/rdpproxy/tools",0755,true);
	if(is_dir("/root/redemption/redemption-master/tools")){
		shell_exec("/bin/cp -rfv /root/redemption/redemption-master/tools $base/etc/rdpproxy/tools/");
	}
	
	if(is_dir("/root/redemption/tools")){
		shell_exec("/bin/cp -rfv /root/redemption/tools $base/etc/rdpproxy/tools/");
	}
//rdproxy-debian7-64-0.9.353.tar.gz

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
			shell_exec("/bin/cp -fd $filename $base$dirname/");
	}

	}
	$C_ICAP_VERSION=C_ICAP_VERSION();
	$Architecture=Architecture();
	echo "C-icap version $C_ICAP_VERSION ($Architecture)\n";
	mkdir("$base/usr/share/c_icap",0755,true);
	mkdir("$base/usr/include/c_icap",0755,true);
	mkdir("$base/usr/lib/c_icap",0755,true);


	shell_exec("/bin/cp -rfd /usr/share/c_icap/* $base/usr/share/c_icap/");
	shell_exec("/bin/cp -rfd /usr/include/c_icap/* $base/usr/include/c_icap/");
	shell_exec("/bin/cp -fd /lib/libbz2.so.1.0.4 /usr/lib/c_icap/");
	shell_exec("/bin/cp -fd /lib/libbz2.so.1.0.4 /usr/lib/c_icap/");
	shell_exec("/bin/cp -rfd /usr/lib/c_icap/* $base/usr/lib/c_icap/");
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