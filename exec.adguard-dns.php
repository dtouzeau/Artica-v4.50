<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.catz.inc');
die();
$md5=md5_file("/root/AdguardDNS.txt");
$curl=new ccurl("https://raw.githubusercontent.com/r-a-y/mobile-hosts/master/AdguardDNS.txt");
if(!$curl->GetFile("/root/AdguardDNS.txt")){echo "Failed to download....\n";}

//$md52=md5_file("/root/AdguardDNS.txt");

$f=explode("\n",@file_get_contents("/root/AdguardDNS.txt"));

$q=new mysql_squid_builder();
$catz=new mysql_catz();
$catz->NoCache();
$category=$catz->GET_CATEGORIES("auchan.fr");
if($category==0){echo "Fatal, category not available\n";}

$c=0;

foreach ($f as $line){
	$line=trim($line);
	if(!preg_match("#0\.0\.0\.0\s+(.+)#", $line,$re)){continue;}
	$domain=trim($re[1]);
	$familysite=$q->GetFamilySites($domain);
	if($familysite<>$domain){continue;}
	$category=$catz->GET_CATEGORIES($familysite);
	if($category>0){continue;}
	$q->free_categorizeSave($domain,5);
	$c++;
	echo "$c: $domain\n";
	
}

?>