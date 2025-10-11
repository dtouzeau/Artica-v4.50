<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["NOPROGRESS"]=false;
$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);

//$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');
if($argv[1]=="--wizard"){$GLOBALS["NOPROGRESS"]=true;}


checkcaches();



function build_progress($text,$pourc){
	if($GLOBALS["NOPROGRESS"]){return;}
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents(PROGRESS_DIR."/squid.rock.progress", serialize($array));
	@chmod(PROGRESS_DIR."/squid.rock.progress",0755);
	sleep(1);

}


function disable_rock(){
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$fname="/etc/squid3/caches.conf";
	if(!is_file($fname)){$fname="/etc/squid3/squid.conf";}
	$f=explode("\n",@file_get_contents($fname));
	$removed=false;
	
	build_progress("{remove_configuration}",50);
	
	foreach ($f as $num=>$val){
		if(preg_match("#cache_dir\s+rock\s+#",$val,$re)){$removed=true;echo "Remove: $val\n";continue;}
		$newconf[]=$val;
	}
	
	$SquidRockPath=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockPath"));
	$cache_directory=$SquidRockPath."/rock";
	
	if($cache_directory<>null){
		if(is_dir($cache_directory)){shell_exec("$rm -rf $cache_directory");}
	}
	
	if($removed){
		@file_put_contents("/etc/squid3/caches.conf", @implode("\n", $newconf));
		build_progress("{reload_proxy}",90);
		system("/etc/init.d/proxy reload --force --script=".basename(__FILE__));
	}
	
}

function checkcaches(){
	$unix=new unix();
	
	$php=$unix->LOCATE_PHP5_BIN();
	$caches=new SquidCacheCenter();
	$lineRock=$caches->build_rock();
	if(!$caches->IsCacheRock){
		disable_rock();
		build_progress("{success}",100);
		return;
	}
	
	
	$cache_size=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockSize"));
	$cache_directory=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockPath"));
	$cache_directory=$cache_directory."/rock";
	build_progress("{checking} Rock {$cache_size}M",10);
	build_progress("{checking_current_configuration}",15);
	
	shell_exec("$php /usr/share/artica-postfix/exec.squid.php.storedir.php --force >/dev/null 2>&1");
	$cachefile="/etc/artica-postfix/settings/Daemons/squid_get_cache_infos.db";
	$MAIN_CACHES=unserialize(@file_get_contents($cachefile));
	
	build_progress("{build_new_cache}",50);

	@mkdir($cache_directory,0755,true);
	@chown($cache_directory,"squid");
	@chgrp($cache_directory, "squid");
	$filetmp=$unix->FILE_TEMP()."conf";
	$f=array();
	$f[]="cache_effective_user squid";
	$f[]="pid_filename	/var/run/squid-temp.pid";
	$f[]="http_port 65478";
	$f[]=$lineRock;
	$f[]="";
	
	@file_put_contents("$filetmp", @implode("\n", $f));
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	echo "$squidbin -f $filetmp -z\n";
	shell_exec("$squidbin -f $filetmp -z");
	@unlink($filetmp);
	build_progress("{reconfiguring_proxy_service}",80);
	
	$caches->build();
	if(isset($MAIN_CACHES[$cache_directory])){
		build_progress("{reloading_proxy_service}",90);
		system("/etc/init.d/squid reload");
	}else{
		build_progress("{restarting_proxy_service}",85);
		system("/etc/init.d/squid restart");
		build_progress("{restarting_proxy_service}",90);
		shell_exec("$php /usr/share/artica-postfix/exec.squid.php.storedir.php --force >/dev/null 2>&1");
	}
	build_progress("{done}",100);
	
}
