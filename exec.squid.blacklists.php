<?php
$GLOBALS["working_directory"]="/opt/artica/proxy";
$GLOBALS["WORKDIR_LOCAL"]="/var/lib/ufdbartica";
$GLOBALS["MAILLOG"]=array();
$GLOBALS["CHECKTIME"]=false;
$GLOBALS["NOTIME"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["NOCHECKTIME"]=false;
$GLOBALS["NOLOGS"]=false;
$GLOBALS["NOISO"]=false;
$GLOBALS["NODELETE"]=false;
$GLOBALS["OUTPUT"]=false;

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
$GLOBALS["FULL"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');


$GLOBALS["MYPID"]=getmypid();

$GLOBALS["CMDLINE"]=@implode(" ", $argv);
if(preg_match("#--notime#",implode(" ",$argv))){$GLOBALS["NOTIME"]=true;}
if(preg_match("#--nodelete#",implode(" ",$argv))){$GLOBALS["NODELETE"]=true;}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--checktime#",implode(" ",$argv))){$GLOBALS["CHECKTIME"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--nologs#",implode(" ",$argv))){$GLOBALS["NOLOGS"]=true;}
if(preg_match("#--noiso#",implode(" ",$argv))){$GLOBALS["NOISO"]=true;}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;$GLOBALS["CHECKTIME"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if($GLOBALS["FORCE"]){$GLOBALS["BYCRON"]=true;}
if(isset($argv[1])) {
	if ($argv[1] == "--get-version") {
		exit();
	}
}


$unix=new unix();
$pids=$unix->PIDOF_PATTERN_ALL(__FILE__);
$mypid=getmypid();
echo "[$mypid]:". count($pids)." processe(s) found...\n";
if(count($pids)>1){
	foreach ($pids as $i=>$y){echo "[$mypid]: Found process $i\n";}
	build_progress("[$mypid] Error: Already executed [".__LINE__."]",110);
	exit();
}



Launch_update();



function launch_update(){
	shell_exec("/usr/sbin/artica-phpfpm-service -categories-update");
}

function download_progress( $client, $download_size, $downloaded, $upload_size, $uploaded) {

	if ($download_size === 0) {
		return;
	}

	$percent = floor($downloaded * 100 / $download_size);
	if(!isset($GLOBALS["PERCENT"][$download_size])){$GLOBALS["PERCENT"][$download_size]=0;}
	if($GLOBALS["PERCENT"][$download_size]==$percent){return;}

	$GLOBALS["PERCENT"][$download_size]=$percent;

	build_progress($GLOBALS["PROGRESS_TEXT"]."  {$percent}%",$GLOBALS["PROGRESS_PRC"]);


}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
function updatev2_progress($num,$text){}
function updatev2_progress2($num,$text){}


function updatev2_NAS(){
	$sock=new sockets();
	$unix=new unix();
	$t=time();
	$GLOBALS["TEMP_PATH"]=$unix->TEMP_DIR();
	$mount_point="{$GLOBALS["TEMP_PATH"]}/$t";
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	$ArticaDBNasUpdt=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaDBNasUpdt")));
	include_once(dirname(__FILE__)."/ressources/class.mount.inc");
	$mount=new mount();
	updatev2_progress(30,"{mouting} {$ArticaDBNasUpdt["hostname"]}...");
	$umount=$unix->find_program("umount");
	if(!$mount->smb_mount($mount_point, $ArticaDBNasUpdt["hostname"], $ArticaDBNasUpdt["username"], $ArticaDBNasUpdt["password"], $ArticaDBNasUpdt["folder"])){
		updatev2_progress(100,"{failed} to update Artica categories database trough NAS");
		squid_admin_mysql(1, "Unable to mount on {$ArticaDBNasUpdt["hostname"]}", @implode("\n", $GLOBALS["MOUNT_EVENTS"]));
		return false;
	}

	$filename=$ArticaDBNasUpdt["filename"];
	if(!is_file("$mount_point/$filename")){
		updatev2_progress(100,"{failed} {$ArticaDBNasUpdt["hostname"]}/{$ArticaDBNasUpdt["folder"]}/$filename no such file");
		squid_admin_mysql(1, "{failed} to update Artica categories database trough NAS","{$ArticaDBNasUpdt["hostname"]}/{$ArticaDBNasUpdt["folder"]}/$filename no such file");
		shell_exec("$umount -l $mount_point");
		return false;
	}

	$tar=$unix->find_program("tar");
	updatev2_progress(40,"{installing}...");
	if($GLOBALS["VERBOSE"]){echo "uncompressing $mount_point/$filename\n";}
	if(!is_dir($ArticaDBPath)){	@mkdir($ArticaDBPath,0755,true);}
	updatev2_progress(50,"{stopping_service}...");
	shell_exec("/etc/init.d/artica-postfix stop articadb");
	updatev2_progress(60,"{extracting_package}...");
	shell_exec("$tar -xhf  $mount_point/$filename -C $ArticaDBPath/");
	updatev2_progress(70,"{cleaning}...");
	$sock->SET_INFO("ManualArticaDBPathNAS", "0");
	shell_exec("$umount -l $mount_point");
	updatev2_progress(75,"{starting_service}...");
	if($GLOBALS["VERBOSE"]){echo "starting Articadb\n";}
	shell_exec("/etc/init.d/artica-postfix start articadb");
	updatev2_progress(80,"{checking}");
	$q=new mysql();
	if(!$q->DATABASE_EXISTS("catz")){
		updatev2_progress(85,"Removing old database catz");
		$q->DELETE_DATABASE("catz");
	}

	updatev2_progress(90,"{finish}");
	$took=$unix->distanceOfTimeInWords($t,time());
	$LOCAL_VERSION=@file_get_contents("$ArticaDBPath/VERSION");
	squid_admin_mysql(2, "New Artica Database statistics $LOCAL_VERSION updated took:$took","");
	squid_admin_mysql(2,"New Artica Database statistics $LOCAL_VERSION updated took:$took",null,__FILE__,__LINE__,"ufbd-artica");
	updatev2_progress(100,"{done}");

	if($q->TABLE_EXISTS("catztemp")){$q->QUERY_SQL("DROP TABLE `catztemp`");}
	return true;

}






function events($text){

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[0])){$file=basename($trace[0]["file"]);$function=$trace[0]["function"];$line=$trace[0]["line"];}
		if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];}
	}
	WriteMyLogs($text,$function,basename(__FILE__),$line);

}
function build_progress($text,$pourc){

	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/artica-webfilterdb.progress";
	echo "[{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	if(!is_dir("/usr/share/artica-postfix/ressources/logs/web/cache")){
		@mkdir("/usr/share/artica-postfix/ressources/logs/web/cache",0755,true);
	}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/cache/webfilter-artica.progress", serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){usleep(500);}


}
function ifMustBeExecuted2(){
	$users=new usersMenus();
	$sock=new sockets();
	$update=true;
	$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
	$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
	$EnableDNSFilterd=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFilterd"));
	if(!is_numeric($CategoriesRepositoryEnable)){$CategoriesRepositoryEnable=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}

	if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return true;}


	if($GLOBALS["VERBOSE"]){echo "CategoriesRepositoryEnable..: $CategoriesRepositoryEnable\n";}
	if($GLOBALS["VERBOSE"]){echo "EnableWebProxyStatsAppliance: $EnableWebProxyStatsAppliance\n";}
	if($GLOBALS["VERBOSE"]){echo "SQUID_INSTALLED.............: $users->SQUID_INSTALLED\n";}

	if($EnableDNSFilterd==1){return true;}

	if($EnableWebProxyStatsAppliance==1){return true;}
	if($CategoriesRepositoryEnable==1){return true;}
	if(!$users->SQUID_INSTALLED){$update=false;}
	return $update;
}


function __GetMemory(){
	$mem=round(((memory_get_usage()/1024)/1000),2);
	return $mem;
}


function WriteMyLogs($text,$function,$file,$line){
	$GLOBALS["MAILLOG"][]=$line.") $text";
	$mem=__GetMemory();
	writelogs("Task:{$GLOBALS["SCHEDULE_ID"]}::$text",$function,__FILE__,$line);
	$logFile="/var/log/webfiltering-update.log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>9000000){unlink($logFile);}
	}
	$date=date('m-d H:i:s');
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n";}
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n");
	@fclose($f);
}

?>				