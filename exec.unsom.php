<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__).  "/ressources/smtp/class.smtp.loader.inc");
include_once(dirname(__FILE__).'/ressources/class.mime.parser.inc');
include_once(dirname(__FILE__).'/ressources/class.rfc822.addresses.inc');
$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

unsom();

function unsom(){
	
	
	//$curl=new ccurl("https://blacklist.tnetworks.com.tr/usom-ip-list-output.txt");
	
//	if(!$curl->GetFile("/root/usom-ip-list-output.txt")){die("Failed to download\n");}
	
	$f=explode("\n",@file_get_contents("/home/dtouzeau/Bureau/usom.txt"));
	
	foreach ($f as $line){
		
		
		if(!preg_match("#dns_line_valid:.+?(.+?)\s+ip#", $line,$re)){continue;}
		$re[1]=trim($re[1]);
		$re[1]=str_replace("#", "", $re[1]);
		echo $re[1]."\n";
		
	}
	
	
	
	
	
	
	
}
