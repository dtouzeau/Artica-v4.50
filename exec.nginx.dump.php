<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["REPLIC_CONF"]=false;
$GLOBALS["NO_RELOAD"]=false;
$GLOBALS["pidStampReload"]="/etc/artica-postfix/pids/".basename(__FILE__).".Stamp.reload.time";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--replic-conf#",implode(" ",$argv),$re)){$GLOBALS["REPLIC_CONF"]=true;}
if(preg_match("#--no-reload#",implode(" ",$argv),$re)){$GLOBALS["NO_RELOAD"]=true;}




$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');

if($argv[1]=="--dump"){dump_tables();exit;}
if($argv[1]=="--restore"){restore_tables($argv[2]);exit;}

function build_progress($text,$pourc){
	$filename=basename(__FILE__);
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/nginx-dump.progress";
	echo "[{$pourc}%] $filename: $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){sleep(1);}


}

function dump_tables(){
	
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".dump_tables.".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	
	@file_put_contents($pidfile, getmypid());
	$q=new mysql_squid_builder();
	$mysqldump=$unix->find_program("mysqldump");
	$gzip=$unix->find_program("gzip");
	build_progress("Dump MySQL",30);
	$tables["nginx_pools_list"]=true;
	$tables["nginx_pools"]=true;
	$tables["nginx_caches"]=true;
	$tables["reverse_sources"]=true;
	$tables["reverse_dirs"]=true;
	$tables["nginx_aliases"]=true;
	$tables["authenticator_authlnk"]=true;
	$tables["authenticator_sourceslnk"]=true;
	$tables["authenticator_rules"]=true;
	$tables["nginx_replace_folder"]=true;
	$tables["nginx_replace"]=true;
	$tables["nginx_replace_www"]=true;
	$tables["nginx_exploits"]=true;
	$tables["nginx_exploits_items"]=true;
	$tables["reverse_mailauth"]=true;
	
	while (list ($key, $value) = each ($tables) ){
		$tdump[]=$key;
		
	}
	
	build_progress("Dump MySQL",50);
	$mysqldump_prefix="$mysqldump $q->MYSQL_CMDLINES --skip-add-locks --insert-ignore --quote-names --verbose --force $q->database";
	system("$mysqldump_prefix ".@implode(" ", $tdump)." | $gzip >/usr/share/artica-postfix/ressources/logs/web/nginx.dump.gz");
	build_progress("Dump MySQL {success}",100);
}

function restore_tables($filename){
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".restore_tables.".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	$filepath=dirname(__FILE__)."/ressources/conf/upload/$filename";
	if(!is_file($filename)){
		build_progress("{failed} $filename no such file",110);
		return;
	}
	
	$tmpf=$unix->FILE_TEMP();
	build_progress("{uncompress} $filename",10);
	if(!$unix->uncompress($filename, $tmpf)){
		build_progress("{uncompress} $filename {failed}",110);
		return;
	}
	@unlink($filename);
	build_progress("{importing} $tmpf",50);
	$mysql=$unix->find_program("mysql");
	$q=new mysql_squid_builder();
	$cmd="$mysql $q->MYSQL_CMDLINES -f squidlogs < $tmpf";
	system($cmd);
	build_progress("{done}",100);
	@unlink($tmpf);
}


