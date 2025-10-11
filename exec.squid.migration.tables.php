<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	include_once('ressources/class.mysql.dump.inc');
	include_once('ressources/class.squid.inc');
	
	
	
function doexport(){
	
	
	$q=new mysql_squid_builder();
	$q->BD_CONNECT();
	$t=$_GET["t"];
	$squidlogs["webfilters_sqacls"]=true;
	$squidlogs["webfilters_sqaclaccess"]=true;
	$squidlogs["webfilters_sqgroups"]=true;
	$squidlogs["webfilters_sqacllinks"]=true;
	$squidlogs["webfilters_sqitems"]=true;
	$squidlogs["webfilters_sqtimes_rules"]=true;
	$squidlogs["webfilters_blkwhlts"]=true;
	$squidlogs["webfilters_usersasks"]=true;
	$squidlogs["webfilters_quotas"]=true;
	$squidlogs["webfilter_avwhitedoms"]=true;
	$squidlogs["webfilter_aclsdynamic"]=true;
	$squidlogs["squidtpls"]=true;

	
	$dir=dirname(__FILE__)."/ressources/logs/web/acls.gz";
	$databases["squidlogs"]=$squidlogs;
	if(is_file($dir)){@unlink($dir);}
	$dump=new phpMyDumper("squidlogs",$q->mysqli_connection,"$dir",true,$squidlogs);
	$dump->doDump();	
	
	
}
