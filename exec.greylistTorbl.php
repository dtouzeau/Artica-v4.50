<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
$GLOBALS["TITLENAME"]="DNS daemon for DNSBLs";
$GLOBALS["OUTPUT"]=true;

if($argv[1]=="--blacklist"){ blacklist();exit;}
if($argv[1]=="--whitelist"){ whitelist();exit;}
if($argv[1]=="--import-blacklist"){ import_blacklist();exit;}
if($argv[1]=="--import-whitelist"){ import_whitelist();exit;}


function blacklist(){
	$ipClass=new IP();
	$pg=new postgres_sql();
	@unlink("/home/postfix/rbldns/export/spammerlist");
	@mkdir("/home/postfix/rbldns/export",0755,true);
	$tmpf = @fopen("/home/postfix/rbldns/export/spammerlist", "w");
	$d=0;
	$c=0;
	$sql="SELECT zdate,pattern,description FROM miltergreylist_acls WHERE method='blacklist' AND type='addr'";
	$results=$pg->QUERY_SQL($sql);
	$max=pg_num_rows($results);
	while ($ligne = pg_fetch_assoc($results)) {
		
		$ipaddr=trim($ligne["pattern"]);
		if($ipaddr==null){continue;}
		$c++;
		$d++;
		if($d>1000){
			$prc=round(($c/$max)*100,2);
			echo "{$prc}%\n";
			$d=0;
		}
		
		if(!$ipClass->isIPAddressOrRange($ipaddr)){continue;}
		fwrite($tmpf, "$ipaddr|||{$ligne["description"]}|||{$ligne["zdate"]}\n");

	}
	
	fclose($tmpf);
	echo "Done\n";
	
}
function whitelist(){
	$ipClass=new IP();
	$pg=new postgres_sql();
	@unlink("/home/postfix/rbldns/export/whitelist");
	@mkdir("/home/postfix/rbldns/export",0755,true);
	$tmpf = @fopen("/home/postfix/rbldns/export/whitelist", "w");
	$d=0;
	$c=0;
	$sql="SELECT zdate,pattern,description FROM miltergreylist_acls WHERE method='whitelist' AND type='addr'";
	$results=$pg->QUERY_SQL($sql);
	$max=pg_num_rows($results);
	while ($ligne = pg_fetch_assoc($results)) {

		$ipaddr=trim($ligne["pattern"]);
		if($ipaddr==null){continue;}
		$c++;
		$d++;
		if($d>1000){
			$prc=round(($c/$max)*100,2);
			echo "{$prc}%\n";
			$d=0;
		}

		if(!$ipClass->isIPAddressOrRange($ipaddr)){continue;}
		fwrite($tmpf, "$ipaddr|||{$ligne["description"]}|||{$ligne["zdate"]}\n");

	}

	fclose($tmpf);
	echo "Done\n";

}

function import_blacklist(){
	$ipClass=new IP();
	$pg=new postgres_sql();
	$pg->SMTP_TABLES();
	
	echo "OPEN /root/spammerlist\n";
	$handle = @fopen("/root/spammerlist", "r");
	
	if(!$handle){
		
		echo "Unable to open /root/spammerlist\n";
		return;
	}
	
	$prefix="INSERT INTO rbl_blacklists (ipaddr,description,zDate) VALUES ";
	
	$c=0;
	$f=array();
	echo "START LOOPING\n";
	while (!feof($handle)) {
		$value=trim(fgets($handle));
		if($value==null){
			if($GLOBALS["VERBOSE"]){echo "NULL\n";}
			continue;}
		$TT=explode("|||",$value);
		$ipaddr=$TT[0];
		$Description=$TT[1];
		$date=$TT[2];
		$f[]="('$ipaddr','$Description','$date')";
		$c++;
		if($GLOBALS["VERBOSE"]){echo "('$ipaddr','$Description','$date')\n";}
		if(count($f)>1000){
			echo "$c\n";
			$pg->QUERY_SQL($prefix.@implode(",", $f)." ON CONFLICT DO NOTHING");
			if(!$pg->ok){echo $pg->mysql_error."\n";return;}
			$f=array();
		}
		
	
	}
	if(count($f)>0){
	
		$pg->QUERY_SQL($prefix.@implode(",", $f)." ON CONFLICT DO NOTHING");
		if(!$pg->ok){echo $pg->mysql_error."\n";return;}
		$f=array();
	}
	fclose($handle);
	echo "Done\n";
	
}

function import_whitelist(){
	$ipClass=new IP();
	$pg=new postgres_sql();
	$pg->SMTP_TABLES();
	
	echo "OPEN /root/whitelist\n";
	$handle = @fopen("/root/whitelist", "r");
	
	if(!$handle){
	
		echo "Unable to open /root/whitelist\n";
		return;
	}
	
	$prefix="INSERT INTO rbl_whitelists (ipaddr,description,zDate) VALUES ";
	
	$c=0;
	$f=array();
	echo "START LOOPING\n";
	while (!feof($handle)) {
		$value=trim(fgets($handle));
		if($value==null){
			if($GLOBALS["VERBOSE"]){echo "NULL\n";}
			continue;}
			$TT=explode("|||",$value);
			$ipaddr=$TT[0];
			$Description=$TT[1];
			$date=$TT[2];
			$f[]="('$ipaddr','$Description','$date')";
			$c++;
			if($GLOBALS["VERBOSE"]){echo "('$ipaddr','$Description','$date')\n";}
			if(count($f)>1000){
				echo "$c\n";
				$pg->QUERY_SQL($prefix.@implode(",", $f)." ON CONFLICT DO NOTHING");
				if(!$pg->ok){echo $pg->mysql_error."\n";return;}
				$f=array();
			}
	
	
	}
	if(count($f)>0){
	
		$pg->QUERY_SQL($prefix.@implode(",", $f)." ON CONFLICT DO NOTHING");
		if(!$pg->ok){echo $pg->mysql_error."\n";return;}
		$f=array();
	}
	fclose($handle);
	echo "Done\n";
}

