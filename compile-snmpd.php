<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');

$unix=new unix();
$GLOBALS["SHOW_COMPILE_ONLY"]=false;
$GLOBALS["NO_COMPILE"]=false;
$GLOBALS["REPOS"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if($argv[1]=='--compile'){$GLOBALS["SHOW_COMPILE_ONLY"]=true;}
if(preg_match("#--no-compile#", @implode(" ", $argv))){$GLOBALS["NO_COMPILE"]=true;}
if(preg_match("#--verbose#", @implode(" ", $argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--repos#", @implode(" ", $argv))){$GLOBALS["REPOS"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}

$users=new usersMenus();

// ./configure --prefix=/usr --enable-embedded-perl --enable-shared


$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");
$dirsrc="snmpd-src";
$Architecture=Architecture();

chdir("/root");
buildpackage();
die("DIE " .__FILE__." Line: ".__LINE__);







function Architecture(){
	$unix=new unix();
	$uname=$unix->find_program("uname");
	exec("$uname -m 2>&1",$results);
	foreach ($results as $num=>$val){
		if(preg_match("#i[0-9]86#", $val)){return 32;}
		if(preg_match("#x86_64#", $val)){return 64;}
	}
}


function buildpackage(){
	
	$version=snmpd_version();
	if($version==null){echo "version is null\n";return;}
	$Architecture=Architecture();
	$f["/usr/include/net-snmp"]=true;
	$f["/usr/share/snmp"]=true;
	$f["/etc/snmp"]=true;
	$f["/usr/lib/libnetsnmp.so.30.0.2"]=true;
	$f["/usr/lib/libnetsnmp.so.30"]=true;
	$f["/usr/lib/libnetsnmp.la"]=true;
	$f["/usr/lib/libnetsnmp.so"]=true;
	$f["/usr/lib/libnetsnmp.a"]=true;
	$f["/usr/lib/libnetsnmpagent.so.30.0.2"]=true;
	$f["/usr/lib/libnetsnmpagent.so.30"]=true;
	$f["/usr/lib/libnetsnmpagent.so"]=true;
	$f["/usr/lib/libnetsnmpagent.la"]=true;
	$f["/usr/lib/libnetsnmpagent.a"]=true;
	$f["/usr/lib/libnetsnmphelpers.so.30.0.2"]=true;
	$f["/usr/lib/libnetsnmphelpers.so.30"]=true;
	$f["/usr/lib/libnetsnmphelpers.so"]=true;
	$f["/usr/lib/libnetsnmphelpers.la"]=true;
	$f["/usr/lib/libnetsnmphelpers.a"]=true;
	$f["/usr/lib/libnetsnmpmibs.so.30.0.2"]=true;
	$f["/usr/lib/libnetsnmpmibs.so.30"]=true;
	$f["/usr/lib/libnetsnmpmibs.so"]=true;
	$f["/usr/lib/libnetsnmpmibs.la"]=true;
	$f["/usr/lib/libnetsnmpmibs.a"]=true;
	$f["/usr/lib/libnetsnmptrapd.so.30.0.2"]=true;
	$f["/usr/lib/libnetsnmptrapd.so.30"]=true;
	$f["/usr/lib/libnetsnmptrapd.so"]=true;
	$f["/usr/lib/libnetsnmptrapd.la"]=true;
	$f["/usr/lib/libnetsnmptrapd.a"]=true;
	$f["/usr/lib/libnetsnmp.so.30.0.2"]=true;
	$f["/usr/lib/libnetsnmp.so.30"]=true;
	$f["/usr/lib/libnetsnmp.so"]=true;
	$f["/usr/lib/libnetsnmp.la"]=true;
	$f["/usr/lib/libnetsnmp.a"]=true;
	$f["/usr/lib/libnetsnmpagent.so.30.0.2"]=true;
	$f["/usr/lib/libnetsnmpagent.so.30"]=true;
	$f["/usr/lib/libnetsnmpagent.so"]=true;
	$f["/usr/lib/libnetsnmpagent.la"]=true;
	$f["/usr/lib/libnetsnmpagent.a"]=true;
	$f["/usr/lib/libnetsnmphelpers.so.30.0.2"]=true;
	$f["/usr/lib/libnetsnmphelpers.so.30"]=true;
	$f["/usr/lib/libnetsnmphelpers.so"]=true;
	$f["/usr/lib/libnetsnmphelpers.la"]=true;
	$f["/usr/lib/libnetsnmphelpers.a"]=true;
	$f["/usr/lib/libnetsnmpmibs.so.30.0.2"]=true;
	$f["/usr/lib/libnetsnmpmibs.so.30"]=true;
	$f["/usr/lib/libnetsnmpmibs.so"]=true;
	$f["/usr/lib/libnetsnmpmibs.la"]=true;
	$f["/usr/lib/libnetsnmpmibs.a"]=true;
	$f["/usr/lib/libnetsnmptrapd.so.30.0.2"]=true;
	$f["/usr/lib/libnetsnmptrapd.so.30"]=true;
	$f["/usr/lib/libnetsnmptrapd.so"]=true;
	$f["/usr/lib/libnetsnmptrapd.la"]=true;
	$f["/usr/lib/libnetsnmptrapd.a"]=true;
	$f["/usr/bin/snmpdelta"]=true;
	$f["/usr/bin/snmpdf"]=true;
	$f["/usr/sbin/snmpd"]=true;
	$f["/usr/sbin/snmptrapd"]=true;
	$f["/usr/bin/snmpget"]=true;
	$f["/usr/bin/snmpgetnext"]=true;
	$f["/usr/bin/snmpset"]=true;
	$f["/usr/bin/snmpwalk"]=true;
	$f["/usr/bin/snmpbulkwalk"]=true;
	$f["/usr/bin/snmptable"]=true;
	$f["/usr/bin/snmptrap"]=true;
	$f["/usr/bin/snmpbulkget"]=true;
	$f["/usr/bin/snmptranslate"]=true;
	$f["/usr/bin/snmpstatus"]=true;
	$f["/usr/bin/snmpdelta"]=true;
	$f["/usr/bin/snmptest"]=true;
	$f["/usr/bin/snmpdf"]=true;
	$f["/usr/bin/agentxtrap"]=true;
	$f["/usr/bin/snmpvacm"]=true;
	$f["/usr/bin/snmpusm"]=true;
	$f["/usr/bin/encode_keychange"]=true;
	$f["/usr/bin/snmpnetstat"]=true;
	$f["/usr/bin/snmpinform"]=true;
	$f["/usr/bin/snmpcheck"]=true;
	$f["/usr/bin/tkmib"]=true;
	$f["/usr/bin/mib2c"]=true;
	$f["/usr/bin/fixproc"]=true;
	$f["/usr/bin/ipf-mod.pl"]=true;
	$f["/usr/bin/snmpconf"]=true;
	$f["/usr/bin/traptoemail"]=true;
	$f["/usr/bin/snmp-bridge-mib"]=true;
	$f["/usr/bin/net-snmp-cert"]=true;
	$f["/usr/bin/mib2c-update"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/SNMP/SNMP.bs"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/SNMP/SNMP.so"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/OID/OID.bs"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/OID/OID.so"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/agent/agent.so"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/agent/agent.bs"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/agent/default_store/default_store.so"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/agent/default_store/default_store.bs"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/default_store/default_store.so"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/default_store/default_store.bs"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/ASN/ASN.so"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/ASN/ASN.bs"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/TrapReceiver/TrapReceiver.bs"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/TrapReceiver/TrapReceiver.so"]=true;
	$f["/usr/local/lib/perl/5.10.1/SNMP.pm"]=true;
	$f["/usr/local/lib/perl/5.10.1/NetSNMP/OID.pm"]=true;
	$f["/usr/local/lib/perl/5.10.1/NetSNMP/TrapReceiver.pm"]=true;
	$f["/usr/local/lib/perl/5.10.1/NetSNMP/ASN.pm"]=true;
	$f["/usr/local/lib/perl/5.10.1/NetSNMP/default_store.pm"]=true;
	$f["/usr/local/lib/perl/5.10.1/NetSNMP/agent.pm"]=true;
	$f["/usr/local/lib/perl/5.10.1/NetSNMP/agent/netsnmp_request_infoPtr.pm"]=true;
	$f["/usr/local/lib/perl/5.10.1/NetSNMP/agent/Support.pm"]=true;
	$f["/usr/local/lib/perl/5.10.1/NetSNMP/agent/default_store.pm"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/SNMP/autosplit.ix"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/OID/autosplit.ix"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/agent/autosplit.ix"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/agent/default_store/autosplit.ix"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/default_store/autosplit.ix"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/ASN/autosplit.ix"]=true;
	$f["/usr/local/lib/perl/5.10.1/auto/NetSNMP/TrapReceiver/autosplit.ix"]=true;
	$f["/usr/local/lib/perl/5.10.1/Bundle/Makefile.subs.pl"]=true;
	$f["/usr/local/man/man3/NetSNMP::agent.3pm"]=true;
	$f["/usr/local/man/man3/NetSNMP::OID.3pm"]=true;
	$f["/usr/local/man/man3/NetSNMP::ASN.3pm"]=true;
	$f["/usr/local/man/man3/NetSNMP::agent::default_store.3pm"]=true;
	$f["/usr/local/man/man3/NetSNMP::default_store.3pm"]=true;
	$f["/usr/local/man/man3/NetSNMP::TrapReceiver.3pm"]=true;
	$f["/usr/local/man/man3/SNMP.3"]=true;
	$f["/usr/local/man/man3/NetSNMP::netsnmp_request_infoPtr.3pm"]=true;

	$root="/root/SNMPD-$version";
	while (list ($filename, $none) = each ($f) ){
		if(is_dir($filename)){
			@mkdir("$root/$filename",0755,true);
			echo "Installing $filename/* in $root/$filename/\n";
			shell_exec("/bin/cp -rfd $filename/* $root/$filename/");
			continue;
		}
		
		
		if(!is_file($filename)){echo "$filename no such file\n";continue;}
		$dir=dirname($filename);
		@mkdir("$root/$dir",0755,true);
		echo "Installing $filename in $root/$dir/\n";
		shell_exec("/bin/cp -fd $filename $root/$dir/");
	
	}
	
	$unix=new unix();
	$tar=$unix->find_program("tar");
	@chdir($root);
	echo "Compressing snmpd-$Architecture-$version.tar.gz\n";
	shell_exec("$tar -czf snmpd-$Architecture-$version.tar.gz *");
	echo "Compressing $root/snmpd-$Architecture-$version.tar.gz Done...\n";	
	
}

function snmpd_version(){
	$unix=new unix();
	$snmpd=$unix->find_program("snmpd");
	exec("$snmpd -v 2>&1",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#NET-SNMP version:.*?([0-9\.]+)#", $line,$re)){return $re[1];}
	
	}
}
