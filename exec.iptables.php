<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.iptables.exec.rules.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');



if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}

if($argv[1]=="--dns"){iprulesDNS();exit;}

$unix=new unix();
$sock=new sockets();
$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
if($unix->process_exists(@file_get_contents($pidfile),basename(__FILE__))){echo "Starting......: ".date("H:i:s")." iptables configurator already executed PID ". @file_get_contents($pidfile)."\n";exit();}
$pid=getmypid();
echo "Starting......: ".date("H:i:s")." iptables configurator running $pid\n";
file_put_contents($pidfile,$pid);
$ip=new iptables_exec();
$ip->buildrules();



function iprulesDNS(){
	$unix=new unix();
	$IPCHAIN="dnsfilter";
	$pidfile="/etc/artica-postfix/".basename(__FILE__).".".__FUNCTION__.".pid";
	if($unix->process_exists(@file_get_contents($pidfile),basename(__FILE__))){echo "Starting......: ".date("H:i:s")." iptables configurator already executed PID ". @file_get_contents($pidfile)."\n";exit();}
	$pid=getmypid();
	file_put_contents($pidfile,$pid);
	
	$sock=new sockets();
	$EnableIptablesDNS=$sock->GET_INFO("EnableIptablesDNS");
	if(!is_numeric($EnableIptablesDNS)){$EnableIptablesDNS=1;}
	
	if($EnableIptablesDNS==0){
		$ip=new iptables_exec();
		if($ip->is_chain_exists($IPCHAIN)){
			shell_exec("{$GLOBALS["iptables"]} -F $IPCHAIN");
			shell_exec("{$GLOBALS["iptables"]} -X $IPCHAIN");
			
		}
		
		return;
	}
	
	$tmpfile=$unix->FILE_TEMP();
	$curl=new ccurl("https://raw.github.com/smurfmonitor/dns-iptables-rules/master/domain-blacklist.txt");
	$curl->NoHTTP_POST=true;
	if($curl->GetFile($tmpfile)){
		$size=@filesize($tmpfile);
		if($size<100){$tmpfile="/usr/share/artica-postfix/bin/install/iptables_defaults.txt";}
		
	}
	
	
	$ip=new iptables_exec();
	if(!$ip->is_chain_exists($IPCHAIN)){
		echo "Adding chain $IPCHAIN\n";
		shell_exec("{$GLOBALS["iptables"]} -N $IPCHAIN");
		shell_exec("{$GLOBALS["iptables"]} -I INPUT -p udp --dport 53 -j $IPCHAIN");
	}else{
		echo "chain $IPCHAIN exists...\n";
	}
	
	shell_exec("{$GLOBALS["iptables"]} -F $IPCHAIN");
	shell_exec("{$GLOBALS["iptables"]} -A $IPCHAIN -j RETURN");
	$f=explode("\n",@file_get_contents($tmpfile));
	
	foreach ($f as $num=>$ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		$ligne=str_replace("INPUT", $IPCHAIN, $ligne);
		$ligne=str_replace("iptables",$GLOBALS["iptables"],$ligne);
		$results=array();
		exec($ligne,$results);
		echo "$ligne\n";
		foreach ($results as $a=>$b){
			echo "$b\n";
		}
		
	}
	
	
	
}
