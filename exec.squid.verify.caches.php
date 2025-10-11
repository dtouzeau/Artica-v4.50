<?php
$GLOBALS["ARGVS"]=implode(" ",$argv);
$GLOBALS["OUTPUT"]=false;
$GLOBALS["BYWIZARD"]=false;
$GLOBALS["RESTART"]=true;
$GLOBALS["PERCENT_PR"]=0;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.refresh_patterns.inc');
if(preg_match("#--bywizard#",implode(" ",$argv))){$GLOBALS["BYWIZARD"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--report#",implode(" ",$argv))){$GLOBALS["REPORT"]=true;}
	if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
	if(preg_match("#--norestart#",implode(" ",$argv))){$GLOBALS["RESTART"]=false;}
    if(preg_match("#--percent-pr=([0-9])+#",implode(" ",$argv),$re)){$GLOBALS["PERCENT_PR"]=$re[1];}
	
}

if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}



verifycaches();

function build_progress($text,$pourc):bool{
	if($GLOBALS["BYWIZARD"]){
        if($pourc<90){$pourc=90;}if($pourc>90){$pourc=90;}
        build_progress_wizard($text,$pourc);
    }
    $unix=new unix();
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $unix->framework_progress($pourc,$text,"squid.caches.progress");

    if($GLOBALS["PERCENT_PR"]>0) {
        $unix->framework_progress($GLOBALS["PERCENT_PR"],"{$pourc}% - $text","wizard.progress");
    }
    return true;
}
function build_progress_wizard($text,$pourc):bool{
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"squid.newcache.center.progress");
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	return true;
}

function verifycaches(){
	$logFile=PROGRESS_DIR."/rebuild-cache.txt";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$unix=new unix();
	$sock=new sockets();
	if($unix->process_exists($pid,basename(__FILE__))){
		ouputz("Already process exists $pid, aborting", __LINE__);
		build_progress("Already process exists $pid, aborting",110);
		exit();
	}
	
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	@unlink($logFile);
	build_progress("Listing caches....",10);
	$cache=new SquidCacheCenter();
	$mv=$unix->find_program("mv");
	$rm=$unix->find_program("rm");
	$php5=$unix->LOCATE_PHP5_BIN();


	
	build_progress("Listing caches....",15);
	$caches=$cache->build();
	$squid_refresh_pattern=new squid_refresh_pattern();
	$squid_refresh_pattern->build();
	$unix->CreateUnixUser("squid","squid");
	@mkdir("/var/run/squid",0755,true);
	@chown("/var/run/squid", "squid");
	@chgrp("/var/run/squid", "squid");
	$http_port=rand(55000, 65000);
	$f=array();
	$f[]="cache_effective_user squid";
	$f[]="pid_filename	/var/run/squid/squid-temp.pid";
	$f[]="http_port 127.0.0.1:$http_port";
	$f[]="include /etc/squid3/caches.conf";
	$f[]="";
    build_progress("Building HyperCache",20);
    $HyperCacheSquid=new HyperCacheSquid();
    $HyperCacheSquid->build();


	$squidconf="/etc/squid3/squid.caches.conf";
	@file_put_contents($squidconf, @implode("\n", $f));
	build_progress("Generating caches {please_wait}",25);
	
	$q=new lib_sqlite("/home/artica/SQLITE/caches.db");
	$sql="SELECT cache_dir FROM squid_caches_center WHERE enabled=1";
	$results = $q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
	    $remove=intval($ligne["remove"]);
	    if($remove==1){continue;}
		$cache_dir=$ligne["cache_dir"];
		$MOUNTED_PART=$unix->DIRPART_OF($cache_dir);
		if(!is_dir($cache_dir)){@mkdir($cache_dir,0755,true);}
		if($MOUNTED_PART==null){continue;}
		@chown($cache_dir, "squid");
		@chgrp($cache_dir, "squid");
	}
	
	
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$su=$unix->find_program("su");

	
	$cmd="$su -c \"$squidbin -f $squidconf -z\" squid";
	ouputz("Building new caches $cmd",__LINE__);
	system($cmd);
	@unlink($squidconf);
	
	build_progress("{stopping} {proxy_service}",20);
	$cmd="/usr/sbin/artica-phpfpm-service -stop-proxy";
	system($cmd);
	build_progress("{starting}  {proxy_service}...",25);
	$cmd="/usr/sbin/artica-phpfpm-service -start-proxy";
	system($cmd);
	
	$RUNNING=false;
	$i=0;
	while (!$RUNNING) {
		build_progress("{starting} {proxy_service} $i...",20);
		$i++;
		$pid=$unix->SQUID_PID();
		if($unix->process_exists($pid)){break;}
		if($i>10){
			build_progress("{starting}  {proxy_service} {failed}...",110);
			return;
		}
		sleep(2);
	}
	
	for($i=0;$i<30;$i++){
		$array=$unix->squid_get_cache_infos();
		if(count($array)>0){break;}
		build_progress("{waiting_proxy_status} $i/29",50);
		echo "Waiting 1s to squid be ready...\n";
		sleep(1);
	}
	
	build_progress("{waiting_proxy_status} $i/29",60);
	system("$php5 /usr/share/artica-postfix/exec.squid.php.storedir.php --force");
	
	$q=new lib_sqlite("/home/artica/SQLITE/caches.db");
	build_progress("{remove_old_caches}",70);
	$sql="SELECT * FROM squid_caches_center WHERE remove=1";
	$results = $q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		$cache_type=$ligne["cache_type"];
		$cache_size=$ligne["cache_size"];
		$cachename=$ligne["cachename"];
		$ID=$ligne["ID"];
		if($cache_type=="Cachenull"){continue;}
		if($cache_type=="tmpfs"){$ligne["cache_dir"]="/home/squid/cache/MemBooster$ID";}
		$Directory=$ligne["cache_dir"];
		if($Directory<>null){
			echo "Remove $Directory\n";
			build_progress("{remove} $Directory",80);
			if(is_dir($Directory)){system("$rm -rf $Directory");}
		}
		$q->QUERY_SQL("DELETE FROM squid_caches_center WHERE ID='$ID'");
	}
	
	
	
	build_progress("{done}",100);
	
	
	
	
}
function squid_watchdog_events($text):bool{
	$unix=new unix();
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
	$unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
    return true;
}

function IfDirIsACache($directory){
	if(is_file("$directory/swap.state")){return true;}
	
	for ($i=0;$i<10;$i++){
		if(is_dir("$directory/0$i/0$i")){return true;}
	}
	
	return false;
}

function clean_old_caches(){

}



function ListCaches(){
	$fname="/etc/squid3/caches.conf";
	if(!is_file($fname)){$fname="/etc/squid3/squid.conf";}
	$f=explode("\n",$fname);
	foreach ($f as $num=>$ligne){
		if(preg_match("#^cache_dir\s+(.+?)\s+(.+?)\s+#", $ligne,$re)){
			if($re[1]=="null"){continue;}
			
			$array[trim($re[2])]=$re[1];
		}
	}
	return $array;
}
function ouputz($text,$line){
	
	
	
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	echo "$text\n";
	$pid=@getmypid();
	$date=@date("H:i:s");

	$logFile=PROGRESS_DIR."/rebuild-cache.txt";
	if($GLOBALS["VERBOSE"]){echo "$text\n";}
	if(is_file($logFile)){
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
	}
	$f = fopen($logFile, 'a');
	fwrite($f, "$date [$pid][$line]: $text\n");
	fclose($f);
	chmod($logFile, 0777);
}


