<?php
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.postgres.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
$GLOBALS["UPDATE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["HOTSPOT"]=false;
$GLOBALS["NORELOAD"]=false;
if(is_array($argv)){if(preg_match("#--no-reload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}}
if(is_array($argv)){if(preg_match("#--hotspot#",implode(" ",$argv))){$GLOBALS["HOTSPOT"]=true;}}
if(is_array($argv)){if(preg_match("#--update#",implode(" ",$argv))){$GLOBALS["UPDATE"]=true;}}
if(is_array($argv)){if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}}
if(is_array($argv)){if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["UPDATE"]=true;$GLOBALS["PROGRESS"]=true;}}



// La traduction MAC/IP -> alias (ex-build(), --build et appel sans argument)
// est portee en Go : articarest hostnets.BuildMacTranslation, route
// /hostnets/aliases/rebuild. Ne reste ici que les sessions hotspot (MySQL),
// lancees par exec.hotspot.build.php.
if($argv[1]=='--hotspot'){build_hotspot();exit;}
exit;

function build_hotspot(){
	
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squidbin)){return;}
	$rm=$unix->find_program("rm");
	$HotSpotDirectory="/home/artica/UsersMac/Hotspots";
	if(is_dir($HotSpotDirectory)){
		shell_exec("$rm -rf $HotSpotDirectory");
	}
	
	
	@mkdir($HotSpotDirectory,0755,true);
	@chmod($HotSpotDirectory, 0755);
	@chown($HotSpotDirectory, "squid");
	@chgrp($HotSpotDirectory, "squid");

	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("hotspot_sessions")){return;}
	
	$sql="SELECT uid,MAC,ipaddr FROM hotspot_sessions WHERE LENGTH(uid)>1";
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysqli_fetch_assoc($results)) {
		
		$f=array();
		$f[]=$ligne["uid"];
		$f[]="hotspot";
		$f[]=null;
		@file_put_contents("$HotSpotDirectory/{$ligne["MAC"]}", @implode("|", $f));
		@chown("$HotSpotDirectory/{$ligne["MAC"]}", "squid");
		@chgrp("$HotSpotDirectory/{$ligne["MAC"]}", "squid");
		
		$f=array();
		$f[]=$ligne["uid"];
		$f[]="hotspot";
		$f[]=null;
		@file_put_contents("$HotSpotDirectory/{$ligne["ipaddr"]}", @implode("|", $f));
		@chown("$HotSpotDirectory/{$ligne["ipaddr"]}", "squid");
		@chgrp("$HotSpotDirectory/{$ligne["ipaddr"]}", "squid");
		
	}
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if( is_file($squidbin)){system("/usr/sbin/artica-phpfpm-service -reload-proxy");}
	
}
