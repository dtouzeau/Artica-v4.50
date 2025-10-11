<?php

/*
 * 
 * ./configure --enable-static --prefix=/usr --includedir="\${prefix}/include" --enable-large-files --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/c-icap"
 * ./configure --enable-static --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/c-icap" --with-clamav
 */
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.compile_squid.inc');

$unix=new unix();

$GLOBALS["SHOW_COMPILE_ONLY"]=false;
$GLOBALS["NO_COMPILE"]=false;
$GLOBALS["REPOS"]=false;
$GLOBALS["ROOT-DIR"]="/root/c-icap-builder";
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
if($argv[1]=="--clamav"){clamav_only($argv[2]);exit;}
if($argv[1]=="--package"){create_package();exit;}



$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");





create_package();


function CICAP_VERSION(){
	$unix=new unix();
	$proftpd=$unix->find_program("c-icap");
	exec("$proftpd -V 2>&1",$results);
	if(preg_match("#([0-9\.]+)#i", @implode("", $results),$re)){return $re[1];}
}



function create_package(){
	$Architecture=Architecture();
	if($Architecture==64){$Architecture="64";}
	if($Architecture==32){$Architecture="32";}
	$WORKDIR=$GLOBALS["ROOT-DIR"];
	$version=CICAP_VERSION();
	$unix=new unix();
	$strip=$unix->find_program("strip");
	$cp=$unix->find_program("cp");
	$mkdir=$unix->find_program("mkdir");
	
	$f[]="rm -rf $WORKDIR";
	$f[]="$mkdir -p $WORKDIR";
	$f[]="$strip -s /usr/bin/c-icap";
	$f[]="$strip -s /usr/bin/c-icap-stretch";
	$f[]="$strip -s /usr/bin/c-icap-libicapapi-config";
	$f[]="$strip -s /usr/bin/c-icap-config";
	$f[]="$strip -s /usr/bin/c-icap-client";
	$f[]="$strip -s /usr/bin/c-icap-mkbdb";
	$f[]="$strip -s /usr/bin/c-icap-mods-sguardDB";
	$f[]="$mkdir -p $WORKDIR/etc";
	$f[]="$mkdir -p $WORKDIR/usr/bin";
	$f[]="$mkdir -p $WORKDIR/usr/include/c_icap";
	$f[]="$mkdir -p $WORKDIR/usr/lib/c_icap";
	$f[]="$mkdir -p $WORKDIR/usr/lib";
	$f[]="$mkdir -p $WORKDIR/etc";
	$f[]="$mkdir -p $WORKDIR/usr/bin";
	$f[]="$mkdir -p $WORKDIR/usr/share";
	$f[]="$mkdir -p $WORKDIR/usr/share/c_icap";
	$f[]="$mkdir -p $WORKDIR/usr/share/c_icap/templates";
	$f[]="$mkdir -p $WORKDIR/usr/share/c_icap/templates/virus_scan";
	$f[]="$mkdir -p $WORKDIR/usr/share/c_icap/templates/virus_scan/en";
	$f[]="$mkdir -p $WORKDIR/usr/share/c_icap/templates/srv_content_filtering";
	$f[]="$mkdir -p $WORKDIR/usr/share/c_icap/templates/srv_content_filtering/en";
	$f[]="$mkdir -p $WORKDIR/usr/share/c_icap/templates/srv_url_check/en";
	$f[]="$mkdir -p $WORKDIR/usr/lib/c_icap";
	
	
	$f[]="$cp -fvd /etc/c-icap.conf.default $WORKDIR/etc/c-icap.conf.default";
	$f[]="$cp -fvd /etc/c-icap.magic.default $WORKDIR/etc/c-icap.magic.default";
	$f[]="$cp -fvd /etc/c-icap.magic $WORKDIR/etc/c-icap.magic";
	$f[]="$cp -fvd /etc/c-icap.conf $WORKDIR/etc/c-icap.conf";
	$f[]="$cp -fvd /usr/bin/c-icap $WORKDIR/usr/bin/c-icap";
	$f[]="$cp -fvd /usr/bin/c-icap-stretch $WORKDIR/usr/bin/c-icap-stretch";
	$f[]="$cp -fvd /usr/bin/c-icap-libicapapi-config $WORKDIR/usr/bin/c-icap-libicapapi-config";
	$f[]="$cp -fvd /usr/bin/c-icap-config $WORKDIR/usr/bin/c-icap-config";
	$f[]="$cp -fvd /usr/bin/c-icap-client $WORKDIR/usr/bin/c-icap-client";
	$f[]="$cp -fvd /usr/bin/c-icap-mkbdb $WORKDIR/usr/bin/c-icap-mkbdb";
	$f[]="$cp -fvd /usr/include/c_icap/hash.h $WORKDIR/usr/include/c_icap/hash.h";
	$f[]="$cp -fvd /usr/include/c_icap/acl.h $WORKDIR/usr/include/c_icap/acl.h";
	$f[]="$cp -fvd /usr/include/c_icap/util.h $WORKDIR/usr/include/c_icap/util.h";
	$f[]="$cp -fvd /usr/include/c_icap/log.h $WORKDIR/usr/include/c_icap/log.h";
	$f[]="$cp -fvd /usr/include/c_icap/mem.h $WORKDIR/usr/include/c_icap/mem.h";
	$f[]="$cp -fvd /usr/include/c_icap/service.h $WORKDIR/usr/include/c_icap/service.h";
	$f[]="$cp -fvd /usr/include/c_icap/cfg_param.h $WORKDIR/usr/include/c_icap/cfg_param.h";
	$f[]="$cp -fvd /usr/include/c_icap/c-icap-conf.h $WORKDIR/usr/include/c_icap/c-icap-conf.h";
	$f[]="$cp -fvd /usr/include/c_icap/proc_threads_queues.h $WORKDIR/usr/include/c_icap/proc_threads_queues.h";
	$f[]="$cp -fvd /usr/include/c_icap/ci_threads.h $WORKDIR/usr/include/c_icap/ci_threads.h";
	$f[]="$cp -fvd /usr/include/c_icap/request.h $WORKDIR/usr/include/c_icap/request.h";
	$f[]="$cp -fvd /usr/include/c_icap/lookup_table.h $WORKDIR/usr/include/c_icap/lookup_table.h";
	$f[]="$cp -fvd /usr/include/c_icap/module.h $WORKDIR/usr/include/c_icap/module.h";
	$f[]="$cp -fvd /usr/include/c_icap/types_ops.h $WORKDIR/usr/include/c_icap/types_ops.h";
	$f[]="$cp -fvd /usr/include/c_icap/txtTemplate.h $WORKDIR/usr/include/c_icap/txtTemplate.h";
	$f[]="$cp -fvd /usr/include/c_icap/commands.h $WORKDIR/usr/include/c_icap/commands.h";
	$f[]="$cp -fvd /usr/include/c_icap/body.h $WORKDIR/usr/include/c_icap/body.h";
	$f[]="$cp -fvd /usr/include/c_icap/net_io.h $WORKDIR/usr/include/c_icap/net_io.h";
	$f[]="$cp -fvd /usr/include/c_icap/array.h $WORKDIR/usr/include/c_icap/array.h";
	$f[]="$cp -fvd /usr/include/c_icap/header.h $WORKDIR/usr/include/c_icap/header.h";
	$f[]="$cp -fvd /usr/include/c_icap/proc_mutex.h $WORKDIR/usr/include/c_icap/proc_mutex.h";
	$f[]="$cp -fvd /usr/include/c_icap/shared_mem.h $WORKDIR/usr/include/c_icap/shared_mem.h";
	$f[]="$cp -fvd /usr/include/c_icap/access.h $WORKDIR/usr/include/c_icap/access.h";
	$f[]="$cp -fvd /usr/include/c_icap/txt_format.h $WORKDIR/usr/include/c_icap/txt_format.h";
	$f[]="$cp -fvd /usr/include/c_icap/filetype.h $WORKDIR/usr/include/c_icap/filetype.h";
	$f[]="$cp -fvd /usr/include/c_icap/cache.h $WORKDIR/usr/include/c_icap/cache.h";
	$f[]="$cp -fvd /usr/include/c_icap/net_io_ssl.h $WORKDIR/usr/include/c_icap/net_io_ssl.h";
	$f[]="$cp -fvd /usr/include/c_icap/registry.h $WORKDIR/usr/include/c_icap/registry.h";
	$f[]="$cp -fvd /usr/include/c_icap/ci_regex.h $WORKDIR/usr/include/c_icap/ci_regex.h";
	$f[]="$cp -fvd /usr/include/c_icap/stats.h $WORKDIR/usr/include/c_icap/stats.h";
	$f[]="$cp -fvd /usr/include/c_icap/simple_api.h $WORKDIR/usr/include/c_icap/simple_api.h";
	$f[]="$cp -fvd /usr/include/c_icap/dlib.h $WORKDIR/usr/include/c_icap/dlib.h";
	$f[]="$cp -fvd /usr/include/c_icap/port.h $WORKDIR/usr/include/c_icap/port.h";
	$f[]="$cp -fvd /usr/include/c_icap/debug.h $WORKDIR/usr/include/c_icap/debug.h";
	$f[]="$cp -fvd /usr/include/c_icap/md5.h $WORKDIR/usr/include/c_icap/md5.h";
	$f[]="$cp -fvd /usr/include/c_icap/c-icap.h $WORKDIR/usr/include/c_icap/c-icap.h";
	$f[]="$cp -fvd /usr/lib/c_icap/shared_cache.a $WORKDIR/usr/lib/c_icap/shared_cache.a";
	$f[]="$cp -fvd /usr/lib/c_icap/dnsbl_tables.so $WORKDIR/usr/lib/c_icap/dnsbl_tables.so";
	$f[]="$cp -fvd /usr/lib/c_icap/dnsbl_tables.a $WORKDIR/usr/lib/c_icap/dnsbl_tables.a";
	$f[]="$cp -fvd /usr/lib/c_icap/srv_echo.a $WORKDIR/usr/lib/c_icap/srv_echo.a";
	$f[]="$cp -fvd /usr/lib/c_icap/bdb_tables.so $WORKDIR/usr/lib/c_icap/bdb_tables.so";
	$f[]="$cp -fvd /usr/lib/c_icap/bdb_tables.la $WORKDIR/usr/lib/c_icap/bdb_tables.la";
	$f[]="$cp -fvd /usr/lib/c_icap/sys_logger.a $WORKDIR/usr/lib/c_icap/sys_logger.a";
	$f[]="$cp -fvd /usr/lib/c_icap/sys_logger.so $WORKDIR/usr/lib/c_icap/sys_logger.so";
	$f[]="$cp -fvd /usr/lib/c_icap/sys_logger.la $WORKDIR/usr/lib/c_icap/sys_logger.la";
	$f[]="$cp -fvd /usr/lib/c_icap/ldap_module.la $WORKDIR/usr/lib/c_icap/ldap_module.la";
	$f[]="$cp -fvd /usr/lib/c_icap/srv_ex206.la $WORKDIR/usr/lib/c_icap/srv_ex206.la";
	$f[]="$cp -fvd /usr/lib/c_icap/srv_ex206.a $WORKDIR/usr/lib/c_icap/srv_ex206.a";
	$f[]="$cp -fvd /usr/lib/c_icap/ldap_module.so $WORKDIR/usr/lib/c_icap/ldap_module.so";
	$f[]="$cp -fvd /usr/lib/c_icap/srv_ex206.so $WORKDIR/usr/lib/c_icap/srv_ex206.so";
	$f[]="$cp -fvd /usr/lib/c_icap/srv_echo.la $WORKDIR/usr/lib/c_icap/srv_echo.la";
	$f[]="$cp -fvd /usr/lib/c_icap/shared_cache.so $WORKDIR/usr/lib/c_icap/shared_cache.so";
	$f[]="$cp -fvd /usr/lib/c_icap/dnsbl_tables.la $WORKDIR/usr/lib/c_icap/dnsbl_tables.la";
	$f[]="$cp -fvd /usr/lib/c_icap/bdb_tables.a $WORKDIR/usr/lib/c_icap/bdb_tables.a";
	$f[]="$cp -fvd /usr/lib/c_icap/ldap_module.a $WORKDIR/usr/lib/c_icap/ldap_module.a";
	$f[]="$cp -fvd /usr/lib/c_icap/srv_echo.so $WORKDIR/usr/lib/c_icap/srv_echo.so";
	$f[]="$cp -fvd /usr/lib/c_icap/shared_cache.la $WORKDIR/usr/lib/c_icap/shared_cache.la";
	$f[]="$cp -fvd /usr/lib/libicapapi.la $WORKDIR/usr/lib/libicapapi.la";
	$f[]="$cp -fvd /usr/lib/libicapapi.so.5.0.2 $WORKDIR/usr/lib/libicapapi.so.5.0.2";
	$f[]="$cp -fvd /usr/lib/libicapapi.so.5 $WORKDIR/usr/lib/libicapapi.so.5";
	$f[]="$cp -fvd /usr/lib/libicapapi.so $WORKDIR/usr/lib/libicapapi.so";
	$f[]="$cp -fvd /etc/virus_scan.conf $WORKDIR/etc/virus_scan.conf";
	$f[]="$cp -fvd /etc/clamav_mod.conf.default $WORKDIR/etc/clamav_mod.conf.default";
	$f[]="$cp -fvd /etc/clamav_mod.conf $WORKDIR/etc/clamav_mod.conf";
	$f[]="$cp -fvd /etc/clamd_mod.conf.default $WORKDIR/etc/clamd_mod.conf.default";
	$f[]="$cp -fvd /etc/srv_url_check.conf.default $WORKDIR/etc/srv_url_check.conf.default";
	$f[]="$cp -fvd /etc/virus_scan.conf.default $WORKDIR/etc/virus_scan.conf.default";
	$f[]="$cp -fvd /etc/srv_content_filtering.conf.default $WORKDIR/etc/srv_content_filtering.conf.default";
	$f[]="$cp -fvd /etc/srv_url_check.conf $WORKDIR/etc/srv_url_check.conf";
	$f[]="$cp -fvd /etc/clamd_mod.conf $WORKDIR/etc/clamd_mod.conf";
	$f[]="$cp -fvd /usr/bin/c-icap-mods-sguardDB $WORKDIR/usr/bin/c-icap-mods-sguardDB";
	$f[]="$cp -fvd /usr/share/c_icap $WORKDIR/usr/share/c_icap";
	$f[]="$cp -fvd /usr/share/c_icap/templates $WORKDIR/usr/share/c_icap/templates";
	$f[]="$cp -fvd /usr/share/c_icap/templates/virus_scan $WORKDIR/usr/share/c_icap/templates/virus_scan";
	$f[]="$cp -fvd /usr/share/c_icap/templates/virus_scan/en $WORKDIR/usr/share/c_icap/templates/virus_scan/en";
	$f[]="$cp -fvd /usr/share/c_icap/templates/virus_scan/en/VIR_MODE_PROGRESS $WORKDIR/usr/share/c_icap/templates/virus_scan/en/VIR_MODE_PROGRESS";
	$f[]="$cp -fvd /usr/share/c_icap/templates/virus_scan/en/VIR_MODE_VIRUS_FOUND $WORKDIR/usr/share/c_icap/templates/virus_scan/en/VIR_MODE_VIRUS_FOUND";
	$f[]="$cp -fvd /usr/share/c_icap/templates/virus_scan/en/VIRUS_FOUND $WORKDIR/usr/share/c_icap/templates/virus_scan/en/VIRUS_FOUND";
	$f[]="$cp -fvd /usr/share/c_icap/templates/virus_scan/en/VIR_MODE_HEAD $WORKDIR/usr/share/c_icap/templates/virus_scan/en/VIR_MODE_HEAD";
	$f[]="$cp -fvd /usr/share/c_icap/templates/virus_scan/en/VIR_MODE_TAIL $WORKDIR/usr/share/c_icap/templates/virus_scan/en/VIR_MODE_TAIL";
	$f[]="$cp -fvd /usr/share/c_icap/templates/srv_content_filtering $WORKDIR/usr/share/c_icap/templates/srv_content_filtering";
	$f[]="$cp -fvd /usr/share/c_icap/templates/srv_content_filtering/en $WORKDIR/usr/share/c_icap/templates/srv_content_filtering/en";
	$f[]="$cp -fvd /usr/share/c_icap/templates/srv_content_filtering/en/BLOCK $WORKDIR/usr/share/c_icap/templates/srv_content_filtering/en/BLOCK";
	$f[]="$cp -fvd /usr/share/c_icap/templates/srv_url_check $WORKDIR/usr/share/c_icap/templates/srv_url_check";
	$f[]="$cp -fvd /usr/share/c_icap/templates/srv_url_check/en $WORKDIR/usr/share/c_icap/templates/srv_url_check/en";
	$f[]="$cp -fvd /usr/share/c_icap/templates/srv_url_check/en/DENY $WORKDIR/usr/share/c_icap/templates/srv_url_check/en/DENY";
	$f[]="$cp -fvd /usr/lib/c_icap/clamav_mod.so $WORKDIR/usr/lib/c_icap/clamav_mod.so";
	$f[]="$cp -fvd /usr/lib/c_icap/srv_content_filtering.so $WORKDIR/usr/lib/c_icap/srv_content_filtering.so";
	$f[]="$cp -fvd /usr/lib/c_icap/virus_scan.so $WORKDIR/usr/lib/c_icap/virus_scan.so";
	$f[]="$cp -fvd /usr/lib/c_icap/srv_url_check.so $WORKDIR/usr/lib/c_icap/srv_url_check.so";
	$f[]="$cp -fvd /usr/lib/c_icap/srv_url_check.a $WORKDIR/usr/lib/c_icap/srv_url_check.a";
	$f[]="$cp -fvd /usr/lib/c_icap/virus_scan.a $WORKDIR/usr/lib/c_icap/virus_scan.a";
	$f[]="$cp -fvd /usr/lib/c_icap/srv_content_filtering.la $WORKDIR/usr/lib/c_icap/srv_content_filtering.la";
	$f[]="$cp -fvd /usr/lib/c_icap/srv_url_check.la $WORKDIR/usr/lib/c_icap/srv_url_check.la";
	$f[]="$cp -fvd /usr/lib/c_icap/clamd_mod.a $WORKDIR/usr/lib/c_icap/clamd_mod.a";
	$f[]="$cp -fvd /usr/lib/c_icap/clamd_mod.la $WORKDIR/usr/lib/c_icap/clamd_mod.la";
	$f[]="$cp -fvd /usr/lib/c_icap/clamav_mod.la $WORKDIR/usr/lib/c_icap/clamav_mod.la";
	$f[]="$cp -fvd /usr/lib/c_icap/srv_content_filtering.a $WORKDIR/usr/lib/c_icap/srv_content_filtering.a";
	$f[]="$cp -fvd /usr/lib/c_icap/clamav_mod.a $WORKDIR/usr/lib/c_icap/clamav_mod.a";
	$f[]="$cp -fvd /usr/lib/c_icap/clamd_mod.so $WORKDIR/usr/lib/c_icap/clamd_mod.so";
	$f[]="$cp -fvd /usr/lib/c_icap/virus_scan.la $WORKDIR/usr/lib/c_icap/virus_scan.la";
	
	foreach ($f as $line){
		echo "$line\n";
		system($line);
		
	}

	echo "Creating package done....\n";
	echo "Building package Arch:$Architecture Version:$version\n";
	echo "Going to $WORKDIR\n";
	@chdir("$WORKDIR");
	$targtefile="cicap-$Architecture-$version.tar.gz";
	echo "Compressing $targtefile\n";
	if(is_file("/root/$targtefile")){@unlink("/root/$targtefile");}
	shell_exec("tar -czf /root/$targtefile *");
	echo "Compressing /root/$targtefile Done...\n";	
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










