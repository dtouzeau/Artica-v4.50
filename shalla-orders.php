<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
$GLOBALS["CLIENT_META_IP"]=$IPADDR;
$GLOBALS["HOSTS_PATH"]="/usr/share/artica-postfix/ressources/conf/meta/hosts";

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');


if(isset($_POST["COMMUNITY_POST_VISITED"])){IMPORT_COMMUNITY_POST_VISITED();die("DIE " .__FILE__." Line: ".__LINE__);}




function IMPORT_COMMUNITY_POST_VISITED(){
	
	$q=new mysql_meta();
	$array=unserialize(base64_decode($_POST["COMMUNITY_POST_VISITED"]));
	
	$prefix="INSERT IGNORE INTO dansguardian_community_nocat(zmd5,uuid,sitename,HitsNumber,familysite) VALUES ".@implode(",",$array);
	$q->QUERY_SQL($prefix);
	if(!$q->ok){
		$q->squid_admin_mysql(0,"MySQL error",$q->mysql_error,__FILE__,__LINE__);
		return;
	}
	
	echo "<ANSWER>OK</ANSWER>";
	
}