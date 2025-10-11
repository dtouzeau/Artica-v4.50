<?php
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');


$file=$argv[1];
$user_req=trim(strtolower($argv[2]));


$handle = @fopen($file, "r");
if (!$handle) {echo "Failed to open file\n";return;}


$logFile="/home/$user_req.csv";
if(is_file($logFile)){@unlink($logFile);}


$q=new mysql_squid_builder();
$d = @fopen($logFile, 'a');
@fwrite($d, "\"date\";\"url\",\"sitename\",\"size\",\"user\"");

while (!feof($handle)){
	$c++;
	$pattern =trim(fgets($handle, 4096));
	if($pattern==null){continue;}
	if(strpos($pattern,"NONE/400")>0){continue;}
	if(!preg_match("#^([0-9\.]+)\s+([0-9]+)\s+([0-9\.]+)\s+([A-Z\/0-9_]+)\s+([0-9]+)\s+([A-Z]+)\s+(.*?)\s+(.*?)\s+([A-Z]+)#",$pattern,$re)){
		echo "*** `$pattern` ****\n";
		continue;
	}

$time=$re[1];
$xdate=date("Y-m-d H:i:s",$time);
$times=$re[2];
$ipaddr=$re[3];
$Code=$re[4];
$Size=$re[5];
$PROTO=$re[6];
$URI=$re[7];
$user=trim(strtolower($re[8]));
if($user_req<>$user){continue;}
$arrayURI=parse_url($URI);
$hostname=$arrayURI["host"];
$familysite=$q->GetFamilySites($hostname);
@fwrite($d, "\"$xdate\";\"$URI\",\"$familysite\",\"$Size\",\"$user\"\n");

}

@fclose($d);
@fclose($handle);

