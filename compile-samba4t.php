<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if($argv[1]=="--parse"){parsepackage($argv[2],$argv[3]);exit;}
if($argv[1]=="--package"){package4();exit;}
if($argv[1]=="--clamav"){package_clamav();exit;}


function package_cicap(){
	
	mkdir('/root/cicapinstall/usr/lib',0755,true);
	mkdir('/root/cicapinstall/usr/bin',0755,true);
	mkdir('/root/cicapinstall/usr/include',0755,true);
	mkdir('/root/cicapinstall/usr/lib/c_icap',0755,true);
	mkdir('/root/cicapinstall/usr/share/man',0755,true);
	shell_exec('cp -fd /usr/lib/libicapapi.so.4.0.4 /root/cicapinstall/usr/lib/');
	shell_exec('cp -fd /usr/lib/libicapapi.so.4 /root/cicapinstall/usr/lib/');
	shell_exec('cp -fd /usr/lib/libicapapi.so /root/cicapinstall/usr/lib/');
	shell_exec('cp -fd /usr/lib/libicapapi.la /root/cicapinstall/usr/lib/');
	
	shell_exec('cp -fd /usr/bin/c-icap /root/cicapinstall/usr/bin/');
	shell_exec('cp -fd /usr/include/c_icap/access.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/body.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/cfg_param.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/c-icap-conf.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/c-icap.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/ci_threads.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/commands.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/debug.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/dlib.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/filetype.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/header.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/log.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/mem.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/module.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/net_io.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/proc_mutex.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/proc_threads_queues.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/request.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/service.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/shared_mem.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/simple_api.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/util.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/lookup_table.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/hash.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/stats.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/acl.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/cache.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/txt_format.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/types_ops.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/txtTemplate.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/array.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/registry.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/md5.h /root/cicapinstall/usr/include/c_icap/');
	shell_exec('cp -fd /usr/include/c_icap/ci_regex.h /root/cicapinstall/usr/include/c_icap/');

	
	shell_exec('cp -fd /usr/bin/c-icap-client /root/cicapinstall/usr/bin/');
	shell_exec('cp -fd /usr/bin/c-icap-stretch /root/cicapinstall/usr/bin/');
	shell_exec('cp -fd /usr/bin/c-icap-mkbdb /root/cicapinstall/usr/bin/');
	shell_exec('cp -fd /usr/lib/c_icap/sys_logger.so /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/sys_logger.la /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/dnsbl_tables.so /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/dnsbl_tables.la /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/shared_cache.so /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/shared_cache.la /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/bdb_tables.so /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/bdb_tables.la /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/ldap_module.so /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/ldap_module.la /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/sys_logger.a /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/dnsbl_tables.a /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/shared_cache.a /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/bdb_tables.a /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/ldap_module.a /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/srv_echo.so /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/srv_echo.la /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/srv_echo.a /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/srv_ex206.so /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/srv_ex206.la /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/lib/c_icap/srv_ex206.a /root/cicapinstall/usr/lib/c_icap/');
	shell_exec('cp -fd /usr/share/man/man8/c-icap.8 /root/cicapinstall/usr/share/man/man8/');
	shell_exec('cp -fd /usr/share/man/man8/c-icap-client.8 /root/cicapinstall/usr/share/man/man8/');
	shell_exec('cp -fd /usr/share/man/man8/c-icap-config.8 /root/cicapinstall/usr/share/man/man8/');
	shell_exec('cp -fd /usr/share/man/man8/c-icap-libicapapi-config.8 /root/cicapinstall/usr/share/man/man8/');
	shell_exec('cp -fd /usr/share/man/man8/c-icap-stretch.8 /root/cicapinstall/usr/share/man/man8/');
	shell_exec('cp -fd /usr/share/man/man8/c-icap-mkbdb.8 /root/cicapinstall/usr/share/man/man8/');
	
}

function package_clamav(){
	$Architecture=Architecture();
	if($Architecture==64){$Architecture="64";}
	if($Architecture==32){$Architecture="32";}
	if(is_dir("/root/clamdinstall")){shell_exec("rm -rf /root/clamdinstall");}
	$version=CLAMAV_VERSION();
	
	$DebianVersion=DebianVersion();
	if($DebianVersion==6){$DebianVersion=null;}else{$DebianVersion="-debian{$DebianVersion}";}
	
	$targtefile="clamav{$DebianVersion}-$Architecture-$version.tar.gz";
	
	
	mkdir('/root/clamdinstall/usr/lib',0755,true);
	mkdir('/root/clamdinstall/usr',0755,true);
	mkdir('/root/clamdinstall/usr/bin',0755,true);
	mkdir('/root/clamdinstall/usr/sbin',0755,true);
	mkdir('/root/clamdinstall/lib/systemd',0755,true);
	mkdir('/root/clamdinstall/usr/share/man',0755,true);
	mkdir('/root/clamdinstall/etc/clamav',0755,true);
	
	shell_exec('cp -fd /usr/lib/libclamunrar.so.7.1.1 /root/clamdinstall/usr/lib/');
	shell_exec('cp -fd /usr/lib/libclamunrar.so.7 /root/clamdinstall/usr/lib/');
	shell_exec('cp -fd /usr/lib/libclamunrar.so /root/clamdinstall/usr/lib/');
	shell_exec('cp -fd /usr/lib/libclamunrar.la /root/clamdinstall/usr/lib/');
	shell_exec('cp -fd /usr/lib/libclamunrar_iface.so.7.1.1 /root/clamdinstall/usr/lib/');
	shell_exec('cp -fd /usr/lib/libclamunrar_iface.so.7 /root/clamdinstall/usr/lib/');
	shell_exec('cp -fd /usr/lib/libclamunrar_iface.so /root/clamdinstall/usr/lib/');
	shell_exec('cp -fd /usr/lib/libclamunrar_iface.la /root/clamdinstall/usr/lib/');
	shell_exec('cp -fd /usr/lib/libclamav.so.7.1.1 /root/clamdinstall/usr/lib/');
	shell_exec('cp -fd /usr/lib/libclamav.so.7 /root/clamdinstall/usr/lib/');
	shell_exec('cp -fd /usr/lib/libclamav.so /root/clamdinstall/usr/lib/');
	shell_exec('cp -fd /usr/lib/libclamav.la /root/clamdinstall/usr/lib/');
	shell_exec('strip -s /usr/bin/clamscan');
	shell_exec('cp -fd /usr/bin/clamscan /root/clamdinstall/usr/bin/');
	shell_exec('strip -s /usr/sbin/clamd');
	shell_exec('cp -fd /usr/sbin/clamd /root/clamdinstall/usr/sbin/');
	shell_exec('cp -fd /lib/systemd/system/clamav-daemon.socket /root/clamdinstall/lib/systemd/system/');
	shell_exec('cp -fd /lib/systemd/system/clamav-daemon.service /root/clamdinstall/lib/systemd/system/');
	shell_exec('strip -s /usr/bin/clamdscan');
	shell_exec('cp -fd /usr/bin/clamdscan /root/clamdinstall/usr/bin/');
	shell_exec('strip -s /usr/bin/freshclam');
	shell_exec('cp -fd /usr/bin/freshclam /root/clamdinstall/usr/bin/');
	shell_exec('strip -s /usr/bin/sigtool');
	shell_exec('cp -fd /usr/bin/sigtool /root/clamdinstall/usr/bin/');
	shell_exec('strip -s /usr/bin/clamconf');
	shell_exec('cp -fd /usr/bin/clamconf /root/clamdinstall/usr/bin/');
	shell_exec('cp -fd /usr/share/man/man1/clamscan.1 /root/clamdinstall/usr/share/man/man1/');
	shell_exec('cp -fd /usr/share/man/man1/freshclam.1 /root/clamdinstall/usr/share/man/man1/');
	shell_exec('cp -fd /usr/share/man/man1/sigtool.1 /root/clamdinstall/usr/share/man/man1/');
	shell_exec('cp -fd /usr/share/man/man1/clamdscan.1 /root/clamdinstall/usr/share/man/man1/');
	shell_exec('cp -fd /usr/share/man/man1/clamconf.1 /root/clamdinstall/usr/share/man/man1/');
	shell_exec('cp -fd /usr/share/man/man1/clamdtop.1 /root/clamdinstall/usr/share/man/man1/');
	shell_exec('cp -fd /usr/share/man/man1/clambc.1 /root/clamdinstall/usr/share/man/man1/');
	shell_exec('cp -fd /usr/share/man/man1/clamsubmit.1 /root/clamdinstall/usr/share/man/man1/');
	shell_exec('cp -fd /usr/share/man/man5/clamd.conf.5 /root/clamdinstall/usr/share/man/man5/');
	shell_exec('cp -fd /usr/share/man/man5/clamav-milter.conf.5 /root/clamdinstall/usr/share/man/man5/');
	shell_exec('cp -fd /usr/share/man/man5/freshclam.conf.5 /root/clamdinstall/usr/share/man/man5/');
	shell_exec('cp -fd /usr/share/man/man8/clamd.8 /root/clamdinstall/usr/share/man/man8/');
	shell_exec('cp -fd /usr/share/man/man8/clamav-milter.8 /root/clamdinstall/usr/share/man/man8/');
	shell_exec('cp -fd /etc/clamav/clamd.conf.sample /root/clamdinstall/etc/clamav/');
	shell_exec('cp -fd /etc/clamav/freshclam.conf.sample /root/clamdinstall/etc/clamav/');
	shell_exec('strip -s /usr/bin/clambc');
	shell_exec('cp -fd /usr/bin/clambc /root/clamdinstall/usr/bin/');
	shell_exec('strip -s /usr/bin/clamsubmit');
	shell_exec('cp -fd /usr/bin/clamsubmit /root/clamdinstall/usr/bin/');
	shell_exec('strip -s /usr/bin/clamav-config');
	shell_exec('cp -fd /usr/bin/clamav-config /root/clamdinstall/usr/bin/');
	
	
	echo "Compressing $targtefile\n";
	if(is_file("/root/$targtefile")){@unlink("/root/$targtefile");}
	system("cd /root/clamdinstall && tar -czf /root/$targtefile *");
	echo "Compressing /root/$targtefile Done...\n";
	
	
}
function CLAMAV_VERSION(){
	$proftpd="/usr/bin/clamav-config";
	exec("$proftpd --version 2>&1",$results);
	if(preg_match("#([0-9\.]+)#i", @implode("", $results),$re)){return $re[1];}
}
function package4(){
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
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes',0755,true);
	mkdir('/root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/iso8601',0755,true);
	mkdir('/root/samba-builder4/usr/lib',0755,true);
	mkdir('/root/samba-builder4/usr/lib/samba/ldb',0755,true);
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
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/dnssec.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/e164.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/edns.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/entropy.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/exception.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/flags.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/hash.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/inet.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/ipv4.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/ipv6.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/message.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/name.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/namedict.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/node.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/opcode.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/query.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rcode.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdata.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdataclass.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdataset.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdatatype.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/AFSDB.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/CERT.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/CNAME.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/DLV.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/DNAME.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/DNSKEY.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/DS.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/GPOS.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/HINFO.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/HIP.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/ISDN.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/LOC.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/MX.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/NS.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/NSEC.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/NSEC3.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/NSEC3PARAM.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/PTR.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/RP.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/RRSIG.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/RT.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/SOA.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/SPF.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/SSHFP.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/TXT.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/X25.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/ANY/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/A.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/AAAA.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/APL.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/DHCID.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/IPSECKEY.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/KX.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/NAPTR.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/NSAP.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/NSAP_PTR.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/PX.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/SRV.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/WKS.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/IN/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/__init__.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/dsbase.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/mxbase.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/nsbase.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/txtbase.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/rdtypes/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/renderer.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/resolver.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/reversename.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/rrset.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/set.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/tokenizer.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/tsig.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/tsigkeyring.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/ttl.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/update.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/version.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/wiredata.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
	shell_exec('cp -fd /usr/lib/python2.7/dist-packages/samba/third_party/dns/zone.py /root/samba-builder4/usr/lib/python2.7/dist-packages/samba/third_party/dns/');
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
	shell_exec('cp -fd /usr/lib/samba/libpopt-samba4.so /root/samba-builder4/usr/lib/samba/');
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
	shell_exec('cp -fd /usr/lib/samba/libz-samba4.so /root/samba-builder4/usr/lib/samba/');
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

function SAMBA_VERSION(){
	$winbind="/usr/sbin/winbindd";
	exec("$winbind -V 2>&1",$results);
	if(preg_match("#Version\s+([0-9\.]+)#i", @implode("", $results),$re)){
		return $re[1];
	}


}
function Architecture(){
	
	$uname="/bin/uname";
	exec("$uname -m 2>&1",$results);
	foreach ($results as $num=>$val){
		if(preg_match("#i[0-9]86#", $val)){return 32;}
		if(preg_match("#x86_64#", $val)){return 64;}
	}
}
function DebianVersion(){

	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];

}

function parsepackage($filepath,$root_builder="/root/samba-builder4"){

	$f=explode("\n",@file_get_contents($filepath));

	foreach ($f as $line){
		
	if(preg_match("#\/install -c (.+?)\s+'(.+?)'#", $line,$re)){
		$filesource=$re[2]."/".$re[1];
		if(is_file($filesource)){
			$dir=$re[2];
			$directory["$root_builder{$dir}"]="$root_builder{$dir}";
			if(preg_match("#(\/bin|\/sbin)#", $filesource)){$CMDS["strip$filesource"]="strip -s $filesource";}
			$CMDS[$filesource]="cp -fd $filesource $root_builder{$dir}/";
			continue;
		}
		
	}
		
	if(preg_match("#\/install -c -m [0-9]+\s+(.+?)'(.+?)'#", $line,$re)){
			$re[1]=trim($re[1]);
			echo "FOUND {$re[1]} -> {$re[2]}\n";
			$dir=dirname($re[2]);
			$directory["$root_builder{$dir}"]="$root_builder{$dir}";
			if(strpos($re[1], " ")>0){
				$dir=$re[2];
				$tt=explode(" ",$re[1]);
				foreach ($tt as $filename){
					$FFM="{$dir}/$filename";
					if(!is_file($FFM)){
						$FFM="{$dir}/".basename($filename);
					}
					if(!is_file($FFM)){continue;}
					if(preg_match("#(\/bin|\/sbin)#", $FFM)){$CMDS["strip$FFM"]="strip -s $FFM";}
					$CMDS[$FFM]="cp -fd $FFM $root_builder{$dir}/";
				}
				continue;
			}
			if(is_dir($re[2])){continue;}
			if(preg_match("#(\/bin|\/sbin)#", $re[2])){$CMDS["strip{$re[2]}"]="strip -s {$re[2]}";}
			$CMDS[$re[2]]="cp -fd {$re[2]} $root_builder{$dir}/";
			continue;
		}
		
		if(preg_match("#cd\s+(.+?)\s+.*?\{ ln -s -f\s+(.+?)\s+(.+?)\s+#", $line,$re)){
			echo "FOUND[".__LINE__."] {$re[1]} -> {$re[2]}\n";
			$dir=$re[1];
			$file1="$dir/{$re[2]}";
			$file2="$dir/{$re[3]}";
			$directory["$root_builder{$dir}"]="$root_builder{$dir}";
			$CMDS[$file1]="cp -fd {$file1} $root_builder{$dir}/";
			$CMDS[$file2]="cp -fd {$file2} $root_builder{$dir}/";
			if(preg_match("#(\/bin|\/sbin)#", $file1)){$CMDS["strip$file1"]="strip -s $file1";}
			if(preg_match("#(\/bin|\/sbin)#", $file2)){$CMDS["strip$file2"]="strip -s $file2";}
			
			if(preg_match("#\|\|.*?rm -f.*?ln -s\s+(.*?)\s+(.*?);#", $line,$re)){
				$file1="$dir/{$re[1]}";
				$file2="$dir/{$re[2]}";				
				$CMDS[$file1]="cp -fd {$file1} $root_builder{$dir}/";
				$CMDS[$file2]="cp -fd {$file2} $root_builder{$dir}/";
				if(preg_match("#(\/bin|\/sbin)#", $file1)){$CMDS["strip$file1"]="strip -s $file1";}
				if(preg_match("#(\/bin|\/sbin)#", $file2)){$CMDS["strip$file2"]="strip -s $file2";}
			}
			
			continue;
					
		}
		
		if(preg_match("#install:.*?-c\s+\.(.+?)\s+(.+)#", $line,$re)){
			echo "FOUND[".__LINE__."] {$re[1]} -> {$re[2]}\n";
			$sourcefile=$re[2];
			$dir=dirname($sourcefile);
			$directory["$root_builder{$dir}"]="$root_builder{$dir}";
			if(is_dir($sourcefile)){continue;}
			if(preg_match("#(\/bin|\/sbin)#", $sourcefile)){$CMDS["strip$sourcefile"]="strip -s $sourcefile";}
			$CMDS[$sourcefile]="cp -fd {$sourcefile} $root_builder{$dir}/";
			continue;
		}

		if(preg_match("#symlink\s+(.*?)\s+\(->\s+(.+?)\)#", $line,$re)){
			$source=$re[1];
			$dest=$re[2];
			echo "SYM -> $source -> $dest\n";
			$dir=dirname($source);
			$filedest="$dir/$dest";
			$directory["$root_builder{$dir}"]="$root_builder{$dir}";
			$CMDS[$filedest]="cp -fd $filedest $root_builder{$dir}/";
			continue;
		}


		if(preg_match("#installing.*?\s+as\s+(.+?)$#", $line,$re)){
			$re[1]=trim($re[1]);
			$dir=dirname($re[1]);
				
			$directory["$root_builder{$dir}"]="$root_builder{$dir}";
				
			if(preg_match("#\/bin\/#", $re[1])){
				$CMDS[]="strip -s {$re[1]}";
			}
			$CMDS[]="cp -fd {$re[1]} $root_builder{$dir}/";
				
				
		}
	}

	foreach ($directory as $line){

		echo "mkdir('$line',0755,true);\n";

	}
	foreach ($CMDS as $line){

		echo "shell_exec('$line');\n";

	}
}