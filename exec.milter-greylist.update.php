
<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",@implode(" ", $argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

$EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
if($EnablePostfix==0){echo "Postfix messaging is disabled, Aborting...\n";exit();}


include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.milter.greylist.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.fetchmail.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');

if($argv[1]=="--regex"){update_milter_regex();exit();}
if($argv[1]=="--postfix"){compile_postfix();exit();}
if($argv[1]=="--malware-patrol"){malware_patrol();exit();}
if($argv[1]=="--nixspam"){die();exit();}

run();

function run(){
	update_milter_greylist();
	update_milter_regex();
	malware_patrol();
	
}

function compile_postfix(){
	$unix=new unix();
	$main=new maincf_multi("master","master");
	$check_client_access=$main->check_client_access();
	$postfix=$unix->find_program("postfix");
	shell_exec("$postfix stop");
	shell_exec("$postfix start");
	
}





function malware_patrol(){
	$unix=new unix();
	$cacheTemp="/etc/artica-postfix/pids/postfix.malware_patrol.time";
	
	if($unix->file_time_min($cacheTemp)<10){
		echo "Please, restart later (10mn)\n";
		return ;
	}
	@unlink($cacheTemp);
	@file_put_contents($cacheTemp, time());
	$MalwarePatrolPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MalwarePatrolPassword");
	if($MalwarePatrolPassword==null){return;}
	
	$tmpfile=$unix->FILE_TEMP();
	$curl=new ccurl("https://lists.malwarepatrol.net/cgi/getfile?receipt={$MalwarePatrolPassword}&product=8&list=postfix");
	if(!$curl->GetFile($tmpfile)){
		squid_admin_mysql(0, "Unable to download malwarepatrol database", $curl->error,__FILE__,__LINE__);
		return;
	}
	
	$md5_src=md5_file("/etc/postfix/malwarepatrol.db");
	$newmd5=md5_file($tmpfile);
	if($md5_src==$newmd5){
		@unlink($tmpfile);
		return;
	}
	$php=$unix->LOCATE_PHP5_BIN();
	@unlink("/etc/postfix/malwarepatrol.db");
	@copy($tmpfile, "/etc/postfix/malwarepatrol.db");
	shell_exec("/usr/sbin/artica-phpfpm-service -smtpd-restrictions");
}


function update_milter_greylist(){
	

	$EnableMilterGreylistExternalDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMilterGreylistExternalDB"));
	$unix=new unix();
	if(!$unix->CORP_LICENSE()){$EnableMilterGreylistExternalDB=0;}
	if($EnableMilterGreylistExternalDB==0){
		$pg=new postgres_sql();
		if($pg->TABLE_EXISTS("miltergreylist_artica")){$pg->QUERY_SQL("TRUNCATE TABLE miltergreylist_artica");}
		return;
	}
	
	$cacheTemp="/etc/artica-postfix/pids/postfix.update_milter_greylist.time";
	$ztime=$unix->file_time_min($cacheTemp);
	if($ztime<720){
		echo "Please, restart later (720mn) - current = {$ztime}mn\n";
		return ;
	}
	@unlink($cacheTemp);
	@file_put_contents($cacheTemp, time());
	
	$q=new postgres_sql();
	$q->SMTP_TABLES();
	
	$mirror="http://mirror.articatech.net/webfilters-databases";
	echo "Downloading $mirror/blacklist-database.txt\n";
	$curl=new ccurl("$mirror/blacklist-database.txt");
	$curl->NoHTTP_POST=true;

$temppath=$unix->TEMP_DIR();

if(!$curl->GetFile("$temppath/milter-greylist-database.txt")){
		squid_admin_mysql(0, "Unable to get Milter-greylist index file", $curl->error);
		return;;

}

if(!is_file("$temppath/milter-greylist-database.txt")){
	squid_admin_mysql(0, "Unable to get Milter-greylist index file (no such file)", $curl->error);
		return;;	
}

$data=@file_get_contents("$temppath/milter-greylist-database.txt");
$MAIN=unserialize($data);
@unlink("$temppath/milter-greylist-database.txt");

if(!isset($MAIN["PGSQL"])){
	echo "PGSQL Not found, incompatible version...\n";
	return;
}

$TIME=$MAIN["PGSQL"]["TIME"];
$MD5=$MAIN["PGSQL"]["MD5"];
$sock=new sockets();
$MyTime=$sock->GET_INFO("MilterGreyListPatternTime");
$CountSource=$MAIN["PGSQL"]["COUNT"];
if($MD5==null){
	echo "PGSQL Not found, incompatible version...\n";
	return;
}
if($TIME==$MyTime){
	echo "Antispam database: $TIME==$MyTime No new update\n";
	return;;
}
echo "Antispam database: Downloading $mirror/blacklist-database.txt\n";	
$curl=new ccurl("$mirror/blacklist-database.gz");
$curl->NoHTTP_POST=true;

if(!$curl->GetFile("$temppath/blacklist-database.gz")){
	squid_admin_mysql(0, "Unable to get blacklist-database.gz", $curl->error,__FILE__,__LINE__);
	return;;

}
$size=@filesize("$temppath/blacklist-database.gz");
$size=$size/1024;
$size=round($size/1024,2);
$md5f=md5_file("$temppath/blacklist-database.gz");
echo "Downloaded $temppath/blacklist-database.gz MD5=$md5f require:$MD5 {$size}MB\n";

if($md5f<>$MD5){
	@unlink("$temppath/blacklist-database.gz");
	squid_admin_mysql(0, "Unable to get blacklist-database.gz (corrupted)", $curl->error,__FILE__,__LINE__);
	return;;
	
}	
echo "Antispam database: uncompress in $temppath/blacklist-database.dump\n";
if(!$unix->uncompress("$temppath/blacklist-database.gz", "$temppath/blacklist-database.dump")){
	@unlink("$temppath/blacklist-database.gz");
	squid_admin_mysql(0, "Unable to extract blacklist-database.gz (corrupted)", null,__FILE__,__LINE__);
	return;;		
}


$size=@filesize("$temppath/blacklist-database.dump");
$SIZEKB=$size/1024;
$SIZEMB=$SIZEKB/1024;
@unlink("$temppath/blacklist-database.gz");
@unlink("/etc/mail/milter-greylist-database.conf");

$pg=new postgres_sql();
$pg->QUERY_SQL("TRUNCATE TABLE miltergreylist_artica");
echo "Restore $size{bytes} ". round($SIZEKB)."KB ". round($SIZEMB)."MB $temppath/blacklist-database.dump\n";
$cmd="/usr/local/ArticaStats/bin/pg_restore -v --data-only --dbname=proxydb --format=custom --table=miltergreylist_artica -h /var/run/ArticaStats -U ArticaStats  $temppath/blacklist-database.dump";
echo $cmd."\n";
system($cmd);

	$results=$pg->QUERY_SQL("SELECT * FROM miltergreylist_artica");
	if(!$pg->ok){echo $pg->mysql_error."\n";exit();}
	$c=0;
	while ($ligne = pg_fetch_assoc($results)) {$c++;}
	echo "Source count: $CountSource, New Count = $c\n";
	
	if($c==0){
		if(is_file("/home/artica/postfix/blacklist-database.dump")){
			squid_admin_mysql(1, "Failed updating Milter-greylist database [action=restore]", null,__FILE__,__LINE__);
			$cmd="/usr/local/ArticaStats/bin/pg_restore -v --data-only --dbname=proxydb --format=custom --table=miltergreylist_artica -h /var/run/ArticaStats -U ArticaStats /home/artica/postfix/blacklist-database.dump";
			echo $cmd."\n";
			system($cmd);
		}else{
			squid_admin_mysql(1, "Failed updating Milter-greylist database [action=none]", null,__FILE__,__LINE__);
		}
	return;
	}
	if(is_file("/home/artica/postfix/blacklist-database.dump")){@unlink("/home/artica/postfix/blacklist-database.dump");}
	@copy("$temppath/blacklist-database.dump", "/home/artica/postfix/blacklist-database.dump");
	@unlink("$temppath/blacklist-database.dump");
	squid_admin_mysql(1, "Success updating new Milter-greylist database version $TIME $CountSource items restored $c", null,__FILE__,__LINE__);
	$sock->SET_INFO("MilterGreyListPatternTime", $TIME);
	$sock->SET_INFO("MilterGreyListPatternCount", $c);

	$main=new maincf_multi("master","master");
	$check_client_access=$main->check_client_access();
	$postfix=$unix->find_program("postfix");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$postfix stop");
	shell_exec("$postfix start");

squid_admin_mysql(1, "Restarting Milter-greylist service", null,__FILE__,__LINE__);
shell_exec("/etc/init.d/milter-greylist restart");

}

function update_milter_regex(){
	$unix=new unix();
	$mirror="http://mirror.articatech.net/webfilters-databases";
	if($GLOBALS["VERBOSE"]){echo "Downloading $mirror/milter-regex-database.gz\n";}
	$curl=new ccurl("$mirror/milter-regex-database.gz");
	$curl->NoHTTP_POST=true;
	
	$temppath=$unix->TEMP_DIR();
	
	if(!$curl->GetFile("$temppath/milter-regex-database.gz")){
		squid_admin_mysql(0, "Unable to get Milter-Regex database file", $curl->error);
		return;
	
	}
	
	if(!is_file("$temppath/milter-regex-database.gz")){
		squid_admin_mysql(0, "Unable to get Milter-Regex database file (no such file)", $curl->error);
		return;
	}

	if(!$unix->uncompress("$temppath/milter-regex-database.gz", "$temppath/milter-regex-database.sql")){
		@unlink("$temppath/milter-regex-database.gz");
		squid_admin_mysql(0, "Unable to extract milter-regex-database.gz (corrupted)", null,__FILE__,__LINE__);
		return;
	}
	
	@unlink("$temppath/milter-regex-database.gz");
	
	$MAIN=unserialize(@file_get_contents("$temppath/milter-regex-database.sql"));
	if(!is_array($MAIN)){
		@unlink("$temppath/milter-regex-database.sql");
		squid_admin_mysql(0, "Unable to understand milter-regex-database (Array corrupted)", null,__FILE__,__LINE__);
		return;
	}
	
	$Time=intval($MAIN["PATTERN"]["TIME"]);
	if($Time==0){
		@unlink("$temppath/milter-regex-database.sql");
		squid_admin_mysql(0, "Unable to understand milter-regex-database (Time corrupted)", null,__FILE__,__LINE__);
		return;
	}
	$sock=new sockets();
	$MyTime=$sock->GET_INFO("MilterRegexPatternTime");
	if($MyTime==$Time){return;}
	$q=new mysql();
	$RULES=$q->COUNT_ROWS("milterregex_acls", "artica_backup");
	@unlink("$temppath/milter-regex-database.sql");

    foreach ($MAIN["DATAS"] as $num=>$ligne){
    	foreach ($ligne as $a=>$b){
			$ligne[$a]=mysql_escape_string2($b);
		}
	
		$description=$ligne["description"];
		$pattern=$ligne["pattern"];
		$method=$ligne["method"];
		$zmd5=$ligne["zmd5"];
		$instance=$ligne["instance"];
		$method=$ligne["method"];
		$type=$ligne["type"];
		$enabled=$ligne["enabled"];
		$reverse=$ligne["reverse"];
		$extended=$ligne["extended"];
		$zDate=$ligne["zDate"];
	
		$sql="INSERT IGNORE INTO `milterregex_acls`
		(`zmd5`,`zDate`,`instance`,`method`,`type`,`pattern`,`description`,`enabled`,`reverse`,`extended`) VALUES
		('$zmd5','$zDate','$instance','$method','$type','$pattern','$description',$enabled,$reverse,$extended);";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return;}
	}	
	
	$sock->SET_INFO("MilterRegexPatternTime", $MAIN["PATTERN"]["TIME"]);
	$RULES2=$q->COUNT_ROWS("milterregex_acls", "artica_backup");
	$SUM=$RULES2-$RULES;
	if($SUM>0){
		squid_admin_mysql(1, "Restarting Milter-regex service", null,__FILE__,__LINE__);
		shell_exec("/etc/init.d/milter-regex restart");
		squid_admin_mysql(2, "$SUM rules updated for Milter-regex ACls", null,__FILE__,__LINE__);
	}
	
	$chown=$unix->find_program("chown");
	shell_exec("$chown postfix:postfix /var/run/milter-greylist/milter-greylist.sock >/dev/null 2>&1");
	
}





?>