<?php
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}

if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.templates.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.ini.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.squid.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::framework/class.unix.inc\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::frame.class.inc\n";}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');

tests_port($argv[1]);

function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.testssl.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);


}

function tests_port($port_id){
	$unix=new unix();
	$squid=new squidbee();
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM proxy_ports WHERE ID=$port_id");
	$squid_certificates=new squid_certificate();
	$ssl_bump_line=$squid_certificates->BuildSquidCertificate($ligne["sslcertificate"]);
	$randport=rand(63500,65535);
	
	
	build_progress("{testing_ssl_certificate} ID:$port_id {$ligne["sslcertificate"]}",15);
	
	$t=time();
	$pid_filename="/var/run/squid/$t.pid";
	$cache_log="/var/log/squid/cache.$t.log";
	$f[]="coredump_dir	/var/squid/cache";
	$f[]="cache_log	/var/log/squid/cache.log";
	$f[]="pid_filename	/var/run/squid/squid.pid";
	$f[]="cache_effective_user squid";
	$f[]="http_port 127.0.0.1:$randport ssl-bump {$ssl_bump_line}";
	echo " ***********************************************************************\n";
	echo "Using HTTPS port ssl-bump $ssl_bump_line Certificate {$ligne["sslcertificate"]}\n";
	echo " ***********************************************************************\n";
	$f[]="";
	$tmpfile=$unix->FILE_TEMP();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	build_progress("{testing_ssl_certificate} {$ligne["sslcertificate"]}",20);
	
	echo "Conf  : $tmpfile\n";
	echo "Binary: $squidbin\n";
	echo "\n";
	echo @implode("\n", $GLOBALS["BuildSquidCertificate"]);
	echo "\n";
	@file_put_contents($tmpfile, @implode("\n", $f));
	exec("$squidbin -f $tmpfile -k check 2>&1",$results);
	@unlink($tmpfile);
	
	foreach ($results as $num=>$line){
		echo "Check  : $line\n";
		if(preg_match("#FATAL: No valid signing SSL#", $line)){
			build_progress("{testing_ssl_certificate_failed} {$ligne["sslcertificate"]}",110);
			$sql="UPDATE proxy_ports SET sslcertificate='' WHERE ID='$port_id'";
			$q->QUERY_SQL($sql);
			return;
		}
		
	}
	build_progress("{testing_ssl_certificate} {success}",100);
	
	
	// FATAL: No valid signing SSL certificate
	
	
}
