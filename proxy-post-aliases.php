<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.tcpip.inc');
include_once('ressources/class.hosts.inc');
$tpl=new templates();
$q=new mysql_squid_builder();

if(!isset($_GET["username"])){die("DIE " .__FILE__." Line: ".__LINE__);}
if(!isset($_GET["macs"])){die("DIE " .__FILE__." Line: ".__LINE__);}


$ipclass=new IP();

$macs=base64_decode($_GET["macs"]);
$Username=trim($_GET["username"]);
$Username=str_replace(chr(0), null, $Username);
$splode=explode("|",$macs);
echo count($splode)." items\r\n";
while (list ($index, $MAC) = each ($splode) ){
		$MAC=strtolower($MAC);
		$MAC=trim($MAC);
		$MAC=str_replace(chr(0), null, $MAC);
		$MAC=str_replace("-", ":", $MAC);
		$MAC=str_replace("\r", "", $MAC);
		$MAC=str_replace("\n", "", $MAC);
		$MAC=str_replace("\r\n", "", $MAC);
	if(!$ipclass->IsvalidMAC($MAC)){
		echo "\"$MAC\" -> Invalid! {$GLOBALS["MacError"]}<br>\r\n";
		continue;}
		echo "$Username:$MAC OK\r\n";
		$hosts=new hosts($MAC);
		$hosts->proxyalias=$Username;
		$hosts->Save();
}


