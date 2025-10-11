<?php

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/externals/Net/DNS2.inc");

$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);


$array["Google"]="8.8.8.8";
$array["OpenDNS"]="208.67.222.222";
$array["85.185.105.231 Iran, Islamic Republic of"]="85.185.105.231";
$array["logger.lu-visp.net"]="81.92.226.181";
$array["ns-cache0.oleane.net"]="194.2.0.20";
$array["cache0101.ns.eu.uu.net"]="194.98.65.165";
$array["Verizon"]="4.2.2.1";




$ipClass=new IP();

foreach ($array as $ISP=>$dnsA){
	echo "Checks DNS $dnsA\n";
	if(!$ipClass->isIPAddress($dnsA)){continue;}
	if($GLOBALS["VERBOSE"]){echo "$dnsA\n";}
	$t['start'] = microtime(true);
	
	echo "Checking $ISP\n";
	
	$rs = new Net_DNS2_Resolver(array('nameservers' => array($dnsA)));
	$rs->timeout = 5;
	
	try {
		$result = $rs->query("www.artica.fr", "A");
		} catch(Net_DNS2_Exception $e) {
			echo "" . $e->getMessage() . "\n";
			continue;
		}
		
		if(count($result->answer)==0){continue;}
		
		$F[]="\$array[\"$ISP\"]=\"$dnsA\";";
		
		foreach($result->answer as $record){
			echo "Name: {$record->name}, type: {$record->type}, address: {$record->address} TTL: {$record->ttl}\n";


		}
		
		
	}
	
	echo @implode("\n", $F)."\n";