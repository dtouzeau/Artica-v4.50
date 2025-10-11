<?php
ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.categorize.externals.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");


$curl=new ccurl("https://raw.githubusercontent.com/quidsup/notrack/master/trackers.txt");
if(!$curl->GetFile("/root/trackers2.txt")){
	echo "Download False\n";
}
$f=explode("\n",@file_get_contents("/root/trackers2.txt"));
$q=new mysql_squid_builder();
$catz=new mysql_catz();
$fam=new squid_familysite();
echo "<html><head></head><body><table>";
foreach ($f as $pid=>$line){
	$line=trim($line);
	if($line==null){continue;}
	if(substr($line, 0,1)=="#"){continue;}
	if(strpos($line, "#")>2){
		$tt=explode("#",$line);
		$line=$tt[0];
	}
	$www=trim($line);
	if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
	$familysite=$fam->GetFamilySites($www);
	if($familysite<>$www){
		echo "<tr><td>";
		echo $www."</td><td><strong>SKIPPED</strong></td></tR>\n";
		continue;
	}
	$category=$catz->GET_CATEGORIES($familysite);
	if($category=="publicite"){continue;}
	if($category=="malware"){continue;}
	if($category=="tracker"){continue;}
	if($category=="marketingware"){continue;}
	if($category=="suspicious"){continue;}
	if($category==null){
		echo "<tr><td>";
		echo $www."</td><td><strong>ADDED</strong></td></tR>\n";
		$q->categorize($familysite, "tracker",true);
		continue;
	}
	echo "<tr><td>";
	echo $familysite."</td><td>$category</td></tR>\n";
	
}



$curl=new ccurl("http://winhelp2002.mvps.org/hosts.txt");


if(!$curl->GetFile("/root/winhelp2002.txt")){
	echo "Download False\n";
}


$f=explode("\n",@file_get_contents("/root/winhelp2002.txt"));
$q=new mysql_squid_builder();
$catz=new mysql_catz();
$fam=new squid_familysite();

foreach ($f as $pid=>$line){
	$line=trim($line);
	if($line==null){continue;}
	if(strpos(" $line", "#")>0){continue;}
	if(!preg_match("#^[0-9\.]+\s+(.+)#", $line,$re)){continue;}
	$www=trim($re[1]);
	if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
	$www=trim($www);
	$familysite=$fam->GetFamilySites($www);
	if($www<>$familysite){continue;}
	if(isset($MAIN[$familysite])){continue;}
	$category=$catz->GET_CATEGORIES($familysite);
	if($category=="publicite"){continue;}
	if($category=="malware"){continue;}
	if($category=="tracker"){continue;}
	if($category=="marketingware"){continue;}
	if($category=="suspicious"){continue;}
	if($category=="hacking"){
		$q->QUERY_SQL("DELETE FROM category_hacking WHERE pattern LIKE '%$www'");
		$q->categorize($familysite, "tracker",true);
		continue;
	}
	$MAIN[$familysite]=true;
	echo "<tr><td>";
	echo $familysite."</td><td>$category</td></tR>\n";
	
}
echo "</table></body></html>";
//http://winhelp2002.mvps.org/hosts.txt