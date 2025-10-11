<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--cluster#",implode(" ",$argv))){$GLOBALS["CLUSTER"]=true;}
if(preg_match("#--noexternal-scripts#",implode(" ",$argv))){$GLOBALS["NO_EXTERNAL_SCRIPTS"]=true;}
if(preg_match("#--noverifacls#",implode(" ",$argv))){$GLOBALS["NO_VERIF_ACLS"]=true;}
if(preg_match("#--no-reload#",implode(" ",$argv))){$GLOBALS["NO_RELOAD"]=true;}
if(preg_match("#--firehol#",implode(" ",$argv))){$GLOBALS["FIREHOL"]=true;}

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');


if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--installed"){echo msmtp_installed()."\n";exit;}




function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/tcpmultipath.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}


function msmtp_installed(){
	$f=explode("\n",@file_get_contents("/boot/grub/grub.cfg"));
	
	foreach ($f as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^menuentry.*?\s+([0-9\.]+)\.mptcp#", $line,$re)){continue;}
		if($GLOBALS["VERBOSE"]){echo "stat /lib/modules/{$re[1]}.mptcp/kernel/net/mptcp\n";}
		if(is_dir("/lib/modules/{$re[1]}.mptcp/kernel/net/mptcp")){return $re[1];}
	}
	return null;
	
}


function AlreadyInstalled(){
	if(!is_file("/etc/apt/sources.list.d/mptcp.list")){return false;}
	echo "/etc/apt/sources.list.d/mptcp.list [OK]\n";
	$msmtp_installed=msmtp_installed();
	if($msmtp_installed==null){return false;}
	echo "Kernel $msmtp_installed  [OK]\n";
	return true;
}

function uninstall(){
	
	$unix=new unix();
	$aptget=$unix->find_program("apt-get");
	$dpkg=$unix->find_program("dpkg");
	$grep=$unix->find_program("grep");
	build_progress(50, "{uninstalling}");
	$cmd="PATH=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\" DEBIAN_FRONTEND=noninteractive $aptget -fuy remove linux-mptcp --purge 2>&1";
	$script=array();
	$script[]="#!/bin/sh";
	$script[]="PATH=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\"";
	$script[]="echo PATH=\$PATH";
	$script[]="$cmd";
	$script[]="";
	$tmpfile=$unix->FILE_TEMP().".sh";
	@file_put_contents($tmpfile, @implode("\n", $script));
	@chmod($tmpfile,0755);
	system($tmpfile);
	@unlink($tmpfile);
	
	exec("$dpkg -l |$grep linux- 2>&1",$results);
	$i=50;
	foreach ($results as $line){
		if(!preg_match("#ii\s+(.+?)\.mptcp\s+#", $line,$re)){continue;}
		$i++;
		$package=$re[1].".mptcp";
		build_progress($i, "{uninstalling} $package");
		$cmd="PATH=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\" DEBIAN_FRONTEND=noninteractive $aptget -fuy remove $package --purge 2>&1";
		$script=array();
		$script[]="#!/bin/sh";
		$script[]="PATH=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\"";
		$script[]="echo PATH=\$PATH";
		$script[]="$cmd";
		$script[]="";
		$tmpfile=$unix->FILE_TEMP().".sh";
		@file_put_contents($tmpfile, @implode("\n", $script));
		@chmod($tmpfile,0755);
		system($tmpfile);
		@unlink($tmpfile);
	}
	
	
	
	$msmtp_installed=msmtp_installed();
	if($msmtp_installed<>null){
		build_progress(110, "{uninstalling} {failed}");
		return;
	}
	$sock=new sockets();
	$sock->SET_INFO("APP_MULTIPATH_TCP_INSTALLED", 0);
	$sock->SET_INFO("APP_MULTIPATH_TCP_ENABLED", 0);
	build_progress(100, "{uninstalling} {success}");
	
}

function install(){
	
	$unix=new unix();
	build_progress(10, "{installing}");
	
	if(AlreadyInstalled()){
		$sock=new sockets();
		$sock->SET_INFO("APP_MULTIPATH_TCP_INSTALLED", 1);
		$sock->SET_INFO("APP_MULTIPATH_TCP_ENABLED", 1);
		build_progress(20, "{installing}");
        $unix->framework_exec("/usr/sbin/artica-phpfpm-service -restart-artica-status");
		build_progress(100, "{installing} {success} {already_installed}");
		return;
	}
	
	
	
	@file_put_contents("/etc/apt/sources.list.d/mptcp.list","deb https://dl.bintray.com/cpaasch/deb stretch main\n");
	$aptkey=$unix->find_program("apt-key");
	$aptget=$unix->find_program("apt-get");
	build_progress(20, "{installing}");
	system("$aptkey adv --keyserver hkp://keys.gnupg.net --recv-keys 379CE192D401AB61");
	build_progress(30, "{updating_repository}");
	system("$aptget update -y");
	build_progress(40, "{installing} apt-transport-https");
	
	$cmd="PATH=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\" DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\"  -fuy install apt-transport-https 2>&1";
	$script=array();
	$script[]="#!/bin/sh";
	$script[]="PATH=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\"";
	$script[]="echo PATH=\$PATH";
	$script[]="$cmd";
	$script[]="";
	$tmpfile=$unix->FILE_TEMP().".sh";
	@file_put_contents($tmpfile, @implode("\n", $script));
	@chmod($tmpfile,0755);
	system($tmpfile);
	@unlink($tmpfile);
	
	system("$aptget update -y");
	build_progress(50, "{installing} {APP_MULTIPATH_TCP}");
	
	$cmd="PATH=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\" DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\"  -fuy --allow-unauthenticated install linux-mptcp 2>&1";
	$script=array();
	$script[]="#!/bin/sh";
	$script[]="PATH=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\"";
	$script[]="echo PATH=\$PATH";
	$script[]="$cmd";
	$script[]="";
	$tmpfile=$unix->FILE_TEMP().".sh";
	@file_put_contents($tmpfile, @implode("\n", $script));
	@chmod($tmpfile,0755);
	system($tmpfile);
	@unlink($tmpfile);
	
	$msmtp_installed=msmtp_installed();
	echo "Verison '$msmtp_installed'\n";
	if($msmtp_installed==null){build_progress(110, "{installing} {failed}");return;}
	$sock=new sockets();
	$sock->SET_INFO("APP_MULTIPATH_TCP_INSTALLED", 1);
	$sock->SET_INFO("APP_MULTIPATH_TCP_ENABLED", 1);
	build_progress(100, "{installing} {success}");
	
}