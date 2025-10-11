<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once(dirname(__FILE__).'/ressources/class.blackboxes.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');	
	include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');	
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	
if($argv[1]=="--ping"){ping($argv[2]);exit;}


function ping($hostid){
	$mefile=basename(__FILE__);
	$GLOBALS["CLASS_UNIX"]=new unix();
	$GLOBALS["CLASS_UNIX"]->events("$mefile:: blackboxes($hostid)","/var/log/stats-appliance.log");
	$black=new blackboxes($hostid);

	
	$ssluri=$black->ssluri."/nodes.listener.php";
	$nossluri=$black->sslnouri."/nodes.listener.php";
	if($GLOBALS["VERBOSE"]){echo "Try $ssluri\n";}
	$GLOBALS["CLASS_UNIX"]->events("$mefile:: $ssluri","/var/log/stats-appliance.log");
	$curl=new ccurl($ssluri);
	$curl->parms["PING-ORDERS"]=true;
	if($GLOBALS["VERBOSE"]){$curl->parms["VERBOSE"]=true;}
	$curl->noproxyload=true;
	if($GLOBALS["VERBOSE"]){echo "Sending PING-ORDERS\n";}
	if(!$curl->get()){
		$ssluri=$nossluri;
		$GLOBALS["CLASS_UNIX"]->events("$mefile:: $ssluri","/var/log/stats-appliance.log");
		if($GLOBALS["VERBOSE"]){echo "error `$ssluri` $curl->error, trying http\n";}
		
		$curl=new ccurl($nossluri);
		$curl->noproxyload=true;
		$curl->parms["PING-ORDERS"]=true;
		if(!$curl->get()){
			squid_admin_mysql(1, "$mefile:: Failed to send ping to $black->hostname with Error:`$curl->error`", __FUNCTION__, __FILE__, __LINE__, "communicate");
			return;
		}		
	}
	
	if($GLOBALS["VERBOSE"]){echo $curl->data;}
	
	if(preg_match("#SUCCESS<#s", $curl->data)){
		$GLOBALS["CLASS_UNIX"]->events("Success to send ping to $black->hostname","/var/log/stats-appliance.log");
		squid_admin_mysql(1, "$mefile:: Success to send ping to $black->hostname", __FUNCTION__, __FILE__, __LINE__, "communicate");
	}

	
}