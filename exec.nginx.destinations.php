<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["REPLIC_CONF"]=false;
$GLOBALS["pidStampReload"]="/etc/artica-postfix/pids/".basename(__FILE__).".Stamp.reload.time";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--replic-conf#",implode(" ",$argv),$re)){$GLOBALS["REPLIC_CONF"]=true;}




$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');

compile_destination($argv[1]);

function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/nginx-destination.progress";
	echo "[{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){usleep(1000);}


}

function compile_destination($cacheid){
	
	
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".compile_destination.".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	
	
	@file_put_contents($pidfile, getmypid());
	
	if(!is_numeric($cacheid)){
		build_progress("Error Destination ID is not set",110);
		return;
	}
	
	if($cacheid==0){
		build_progress("Error Destination ID is Zero",110);
		return;
	}
	
	$q=new mysql_squid_builder();
	$sql="SELECT servername FROM reverse_www WHERE cache_peer_id=$cacheid";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		build_progress("Error MySQL error",110);
		echo $q->mysql_error;
		return;
	}
	
	
	$count=mysqli_num_rows($results);
	if($count==0){
		echo "$sql\n";
		build_progress("Error no destination for ID $cacheid",110);
		return;
	}
	build_progress("$count Destination(s)",5);
	
	
	
	$c=0;
	$php=$unix->LOCATE_PHP5_BIN();
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$c++;
		$prc=$c/$count;
		$prc=$prc*100;
		if($prc>90){$prc=90;}
		$servername=$ligne["servername"];
		build_progress("{reconfigure} $servername",$prc);
		system("$php /usr/share/artica-postfix/exec.nginx.single.php $servername --no-reload --output --no-buildmain");
	}
	build_progress("{cleaning_old_configs}...",91);
	system("$php /usr/share/artica-postfix/exec.nginx.wizard.php --check-http");
	build_progress("{building_main_settings}",95);
	system("$php /usr/share/artica-postfix/exec.nginx.php --main");
	build_progress("{$ligne["servername"]}: {reloading_reverse_proxy} ",96);
	system("/etc/init.d/nginx reload --force");
	build_progress("{$ligne["servername"]}: {reloading_reverse_proxy}  {done}",100);
}
?>