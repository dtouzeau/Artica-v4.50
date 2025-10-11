<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.pdns.inc');

$GLOBALS["SHOWKEYS"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if(preg_match("#--showkeys#",implode(" ",$argv))){$GLOBALS["SHOWKEYS"]=true;}


if($argv[1]=="--import"){import($argv[2],$argv[3]);exit;}



function import($fileenc,$domain){
	$domain=trim($domain);
	if(preg_match("#--#", $domain)){$domain=null;}
	
	$q=new mysql();
	$q->BuildTables();
	$filename=base64_decode($fileenc);
	if(!is_file($filename)){
		echo "$filename, no such file...\n";
		return;
	}
	echo "$filename, Open file\n";
	$f=file($filename);
	
	$F=0;
	$S=0;
	foreach ( $f as $index=>$line ){
		$line=trim($line);
		$line=str_replace("\r", "", $line);$line=str_replace("\n", "", $line);$line=str_replace("\r\n", "", $line);
		if($line==null){continue;}
		
		if(substr($line, 0,1)=="#"){continue;}
		
		$posDieze=strpos($line, "#");
		if($posDieze>0){
			if($posDieze<5){continue;}
		}
		$Obs=substr($line, $posDieze,strlen($line));
		$line=str_replace($Obs, "", $line);
		$Obs=trim(utf8_encode($Obs));
		if(strlen($Obs)>2){$Obs=trim(str_replace("#", "", $Obs));}
		if(!preg_match("#^([0-9\.]+)\s+(.+?)$#", $line,$re)){continue;}
		
		$IP=trim($re[1]);
		$domainname=$domain;
		$hostname=trim(strtolower($re[2]));
		
		if(strpos($hostname, " ")>0){
			$tze=explode(" ",$hostname);
			while (list ($a, $b) = each ($tze) ){if(trim($b)==null){continue;}$tzf[]=$b;}
			if(strlen($tzf[0])>2){$hostname=$tzf[0];}
		}
		
		$netbiosname=$hostname;

		if(strpos($hostname, ".")>0){
			$tb=explode(".",$hostname);
			$netbiosname=$tb[0];
			unset($tb[0]);
			$domainname=trim(@implode(".", $tb));
			
		}
		
		$host=$netbiosname.".".$domainname;
		$pdns=new pdns($domainname);
		
		if(!$pdns->EditIPName($netbiosname, $IP, "A",null,$Obs)){
			echo "Item [{$re[1]}] - `{$host}` ($Obs) failed\n";
			$F++;
		}else{
			echo "Item [{$re[1]}] - `{$host}` ($Obs) Success\n";
			$S++;
		}
		
	}
	
	echo "Success: $S item(s), Failed: $F item(s)\n";
	
}