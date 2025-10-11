<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.milter.greylist.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.fetchmail.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");

$GLOBALS["deflog_start"]="Starting......: ".date("H:i:s")." [INIT]: Milter Greylist Daemon";
$GLOBALS["deflog_sstop"]="Stopping......: ".date("H:i:s")." [INIT]: Milter Greylist Daemon";
$GLOBALS["ROOT"]=true;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",@implode(" ", $argv))){$GLOBALS["FORCE"]=true;}
$GLOBALS["WHOPROCESS"]="daemon";

if(!is_file("/root/ftp-hostname")){
	echo "/root/ftp-hostname No such file...\n";
	exit();
}


$unix=new unix();



if(is_file("/etc/artica-postfix/spamassassin-rules1.cf")){
	@unlink("/etc/artica-postfix/spamassassin-rules1.gz");
	if(!$unix->compress("/etc/artica-postfix/spamassassin-rules1.cf", "/etc/artica-postfix/spamassassin-rules1.gz")){exit();}
	$MAIN["SPAMASS_1"]["TIME"]=time();
	$MAIN["SPAMASS_1"]["MD5"]=md5_file("/etc/artica-postfix/spamassassin-rules1.gz");

}
if(is_file("/etc/artica-postfix/spamassassin-rules3.cf")){
	@unlink("/etc/artica-postfix/spamassassin-rules3.gz");
	if(!$unix->compress("/etc/artica-postfix/spamassassin-rules3.cf", "/etc/artica-postfix/spamassassin-rules3.gz")){exit();}
	$MAIN["SPAMASS_2"]["TIME"]=time();
	$MAIN["SPAMASS_2"]["MD5"]=md5_file("/etc/artica-postfix/spamassassin-rules3.gz");
}

if(is_file("/etc/artica-postfix/spamassassin-rules4.cf")){
	@unlink("/etc/artica-postfix/spamassassin-rules4.gz");
	if(!$unix->compress("/etc/artica-postfix/spamassassin-rules4.cf", "/etc/artica-postfix/spamassassin-rules4.gz")){exit();}
	$MAIN["SPAMASS_3"]["TIME"]=time();
	$MAIN["SPAMASS_3"]["MD5"]=md5_file("/etc/artica-postfix/spamassassin-rules4.gz");
}

@unlink("/root/blacklist-database.txt");

$pg=new postgres_sql();
$results=$pg->QUERY_SQL("SELECT * FROM miltergreylist_acls");
if(!$pg->ok){echo $pg->mysql_error."\n";exit();}
$c=0;
while ($ligne = pg_fetch_assoc($results)) {$c++;}

echo "***************** $c items *****************\n";
$MAIN["PGSQL"]["COUNT"]=$c;
@unlink("/root/blacklist-database.txt");
@unlink("/root/blacklist-database.gz");
echo "***************** $c DUMP *****************\n";

$q=new postgres_sql();
$q->QUERY_SQL("DROP TABLE miltergreylist_artica");
$q->QUERY_SQL("create table miltergreylist_artica as select * from miltergreylist_acls");
$cmd="/usr/local/ArticaStats/bin/pg_dump --file=/root/blacklist-database.txt --data-only --format=custom --table=miltergreylist_artica -h /var/run/ArticaStats -U ArticaStats proxydb";
echo $cmd."\n";
system($cmd);
echo "***************** Compress *****************\n";
$unix->compress("/root/blacklist-database.txt", "/root/blacklist-database.gz");
@unlink("/root/blacklist-database.txt");
echo "***************** Saving index *****************\n";

$MAIN["PGSQL"]["TIME"]=time();
$MAIN["PGSQL"]["MD5"]=md5_file("/root/blacklist-database.gz");
@file_put_contents("/root/blacklist-database.txt", serialize($MAIN));

$ftp_serv=@file_get_contents("/root/ftp-hostname");
$ftp_passw=@file_get_contents("/root/ftp-password");
$curl=$unix->find_program("curl");
$ftp_passw=$unix->shellEscapeChars($ftp_passw);
echo "\n ************** FTP WWWW **************\n";
echo "Push to ftp://mirror.articatech.net/www.artica.fr/WebfilterDBS/\n";
$cmdline="$curl -T /root/blacklist-database.gz ftp://mirror.articatech.net/www.artica.fr/WebfilterDBS/ --user $ftp_passw\n";
echo $cmdline."\n";
shell_exec("$curl -T /root/blacklist-database.txt ftp://mirror.articatech.net/www.artica.fr/WebfilterDBS/ --user $ftp_passw");
shell_exec("$curl -T /root/blacklist-database.gz ftp://mirror.articatech.net/www.artica.fr/WebfilterDBS/ --user $ftp_passw");
echo "*****************************************************\n";

if(is_file("/etc/artica-postfix/spamassassin-rules1.gz")){
	shell_exec("$curl -T /etc/artica-postfix/spamassassin-rules1.gz ftp://mirror.articatech.net/www.artica.fr/WebfilterDBS/ --user $ftp_passw");
}
if(is_file("/etc/artica-postfix/spamassassin-rules3.gz")){
	shell_exec("$curl -T /etc/artica-postfix/spamassassin-rules3.gz ftp://mirror.articatech.net/www.artica.fr/WebfilterDBS/ --user $ftp_passw");
}
if(is_file("/etc/artica-postfix/spamassassin-rules4.gz")){
	shell_exec("$curl -T /etc/artica-postfix/spamassassin-rules4.gz ftp://mirror.articatech.net/www.artica.fr/WebfilterDBS/ --user $ftp_passw");
}
echo "*****************************************************\n";
	
	




$q=new mysql();
$sql="SELECT description,pattern FROM miltergreylist_acls WHERE method='blacklist' AND type='domain'";
$results=$pg->QUERY_SQL($sql);
while ($ligne = pg_fetch_assoc($results)) {
	$domain=$ligne["pattern"];
	if(preg_match("#regex:\s+#", $domain)){continue;}
	$miltergreylist_acls[trim(strtolower($domain))]=$ligne["description"];
}

if(count($miltergreylist_acls)>0){
	while (list ($domain, $description) = each ($miltergreylist_acls) ){
			$type="reject";
			$method="connect";
			$instance="master";
			$domain=str_replace(".", "\.", $domain);
			$zmd5=md5("$type$method$domain$instance");
			$zDate=date("Y-m-d H:i:s");
			$description=pg_escape_bytea($description);
			$domain=pg_escape_bytea($domain);
		
		$sql="INSERT INTO `milterregex_acls`
		(`zmd5`,`zDate`,`instance`,`method`,`type`,`pattern`,`description`,`enabled`,`reverse`,`extended`) VALUES ('$zmd5','$zDate','$instance','$method','$type','$domain','$description',1,0,0);";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return;}

	}
}




$sql="SELECT * FROM milterregex_acls WHERE (`instance` = 'master') AND enabled=1";
$results=$q->QUERY_SQL($sql,"artica_backup");
if(!$q->ok){return;}
$MAIN=array();
$MAIN["PATTERN"]["TIME"]=time();

while ($ligne = mysqli_fetch_assoc($results)) {
	$MAIN["DATAS"][]=$ligne;

}
@file_put_contents("/root/milter-regex-database.txt", serialize($MAIN));
@file_put_contents("/root/milter-regex-DB.txt", serialize($MAIN["DATAS"]));
@unlink("/root/milter-regex-database.gz");
if(!$unix->compress("/root/milter-regex-database.txt", "/root/milter-regex-database.gz")){exit();}
echo "\n ************** FTP WWWW **************\n";
echo "Push to ftp://mirror.articatech.net/www.artica.fr/WebfilterDBS/\n";
shell_exec("$curl -T /root/milter-regex-database.gz ftp://mirror.articatech.net/www.artica.fr/WebfilterDBS/ --user $ftp_passw");
echo "*****************************************************\n";


