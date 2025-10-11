<?php
ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.categorize.externals.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");

xstart();

function xstart(){
	$unix=new unix();
	
	$filetime="/etc/artica-postfix/pids/exec.mvps.php.time";
	
	if($unix->file_time_min($filetime)<240){
		echo "Need 240mn, current is {$filetime}Mn\n";
		return;
	}
	
	@unlink($filetime);
	@file_put_contents($filetime, time());
	$q=new mysql_squid_builder();
	$curl=new ccurl("http://winhelp2002.mvps.org/hosts.txt");

	$targetpath=$unix->FILE_TEMP();
	if(!$curl->GetFile($targetpath)){
		squid_admin_mysql(1, "Unable to download hosts.txt from winhelp2002.mvps.org", null,__FILE__,__LINE__);
		return;
	}
	
	$f=explode("\n",@file_get_contents($targetpath));
	@unlink($targetpath);
	$fam=new familysite();
	
	$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`ads_domains` (
			`servername` VARCHAR(255) PRIMARY KEY,
			`enabled` smallint(1) NOT NULL DEFAULT 1,
			 KEY `enabled`(`enabled`)
		) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$COUNT1=$q->COUNT_ROWS("ads_domains");
	
	$QQR=array();
	while (list ($a,$line) = each ($f) ){
		$line=trim($line);
		if(strpos($line, "localhost")>0){continue;}
		if(substr($line, 0,1)=="#"){continue;}
		$line=str_replace("0.0.0.0 ", "", $line);
		if(strpos($line, "#")>0){$FI=explode("#",$line);$line=$FI[0];}
		if(strpos($line, ".")==0){continue;}
		$line=trim($line);
		$familysite=$fam->GetFamilySites($line);
		if($line==$familysite){$line=".$line";}
		$QQR[]="('$line','1')";
		if(count($QQR)>500){
			$sql="INSERT IGNORE INTO `ads_domains` (`servername`,`enabled`) VALUES ".@implode(",", $QQR);
			$q->QUERY_SQL($sql);
			$QQR=array();
		}
	}
	if(count($QQR)>0){
		$sql="INSERT IGNORE INTO `ads_domains` (`servername`,`enabled`) VALUES ".@implode(",", $QQR);
		$q->QUERY_SQL($sql);
		$QQR=array();
	}
	
	$curl=new ccurl("http://pgl.yoyo.org/adservers/serverlist.php?hostformat=hosts&showintro=0&mimetype=plaintext");
	$targetpath=$unix->FILE_TEMP();
	if(!$curl->GetFile($targetpath)){
		squid_admin_mysql(1, "Unable to download serverlist from yoyo.org", null,__FILE__,__LINE__);
		return;
	}
	$f=explode("\n",@file_get_contents($targetpath));
	@unlink($targetpath);
	
	while (list ($a,$line) = each ($f) ){
		$line=trim($line);
		if(strpos($line, "localhost")>0){continue;}
		if(substr($line, 0,1)=="#"){continue;}
		$line=str_replace("127.0.0.1 ", "", $line);
		if(strpos($line, "#")>0){$FI=explode("#",$line);$line=$FI[0];}
		if(strpos($line, ".")==0){continue;}
		$line=trim($line);
		$QQR[]="('$line','1')";
		$familysite=$fam->GetFamilySites($line);
		if($line==$familysite){$line=".$line";}
		if(count($QQR)>500){
			$sql="INSERT IGNORE INTO `ads_domains` (`servername`,`enabled`) VALUES ".@implode(",", $QQR);
			$q->QUERY_SQL($sql);
			$QQR=array();
		}
	}
	if(count($QQR)>0){
		$sql="INSERT IGNORE INTO `ads_domains` (`servername`,`enabled`) VALUES ".@implode(",", $QQR);
		$q->QUERY_SQL($sql);
		$QQR=array();
	}
	
	$COUNT2=$q->COUNT_ROWS("ads_domains");
	if($COUNT2>$COUNT1){
		$TOTAL=$COUNT2-$COUNT1;
		squid_admin_mysql(1, "$TOTAL ads and tracker added in ACLs", null,__FILE__,__LINE__);
	}
		
}


