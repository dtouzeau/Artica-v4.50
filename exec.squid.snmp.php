<?php
$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');


function install(){
	
	
	
	
}



run();




function run(){
	if(!extension_loaded('snmp')){install();exit;}
	if(!class_exists("SNMP")){exit;}

  $session = new SNMP(SNMP::VERSION_1, "127.0.0.1:3401", "public");
  $session->valueretrieval = SNMP_VALUE_PLAIN;
  $ifDescr = $session->walk(".1.3.6.1.4.1.3495.1.3", TRUE);
  $session->valueretrieval = SNMP_VALUE_LIBRARY;
  $ifType = $session->walk(".1.3.6.1.4.1.3495.1.3", TRUE);

  $ifCpu=$session->walk(".1.3.6.1.4.1.3495.1.3.1.5.0", TRUE);

    $ifRequest=$session->walk(".1.3.6.1.4.1.3495.1.3.2.1.1.0",true);
    $cacheClients=$session->walk(".1.3.6.1.4.1.3495.1.3.2.1.15.0",true);



print_r($ifRequest);
echo $ifRequest["iso.3.6.1.4.1.3495.1.3.2.1.1.0"]."\n";
  
  
}
  
?>
