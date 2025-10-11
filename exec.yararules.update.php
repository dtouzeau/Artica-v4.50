<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["PROGRESS"]=true;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if($argv[1]=="--remove"){YaraRules_remove();exit;}
xstart();

function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/clamav.yararules.progress";
	if(!$GLOBALS["PROGRESS"]){return;}
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);

}

function xstart(){
    $NoInternetAccess=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoInternetAccess"));
    if($NoInternetAccess==1){return true;}
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/exec.yararules.update.php.xstart.pid";
	$pidTime="/etc/artica-postfix/pids/exec.yararules.update.php.xstart.time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		echo "Already executed...\n";
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	
	$pidTimeINT=$unix->file_time_min($pidTime);
	if(!$GLOBALS["FORCE"]){
		if($pidTimeINT<1440){
			echo "To short time to execute the process $pidTime = {$pidTimeINT}Mn < 1440\n";
			return;
		}
	}
	
	build_progress("{yararules} {update}",15);
	@file_put_contents($pidTime, time());
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableClamavYaraRules")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableClamavYaraRules", 0);}
	$EnableClamavYaraRules=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavYaraRules"));
	$EnableClamavYaraRules=0;
	$sock=new sockets();
	$CORP_LICENSE=$sock->CORP_LICENSE();
	if(!$CORP_LICENSE){
		build_progress("{yararules} {license_error}",50);
		$EnableClamavYaraRules=0;}

	if($EnableClamavYaraRules==0){
		build_progress("{yararules} {remove}",55);
		YaraRules_remove();
		build_progress("{yararules} {success}",100);
		return;
	}
	

	$TEMPDIR=$unix->TEMP_DIR();
	$curl=new ccurl("http://articatech.net/webfilters-databases/yara-rules.txt");
	
	if(!$curl->GetFile("$TEMPDIR/yara-rules.txt")){
		echo $curl->error."\n";
		squid_admin_mysql(0, "Unable to retreive Yara Rules index file yara-rules.txt", $curl->error,__FILE__,__LINE__);
		build_progress("{yararules} {failed}",110);
		@unlink("$TEMPDIR/yara-rules.txt");
		return;
	}
	
	build_progress("{yararules} {updating}",60);
	$MAIN_ARRAY=unserialize(base64_decode(@file_get_contents("$TEMPDIR/yara-rules.txt")));
	$CURRENT_ARRAY=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamavYaraRulesDB")));
	
	
	if(!is_array($MAIN_ARRAY)){
		squid_admin_mysql(0, "Corrupted(1) Yara rules index file", null,__FILE__,__LINE__);
		build_progress("{yararules} {failed}",110);
		@unlink("$TEMPDIR/yara-rules.txt");
		return;
	}
	if(!isset($MAIN_ARRAY["TIME"])){
		squid_admin_mysql(0, "Corrupted(2) Yara rules index file", null,__FILE__,__LINE__);
		build_progress("{yararules} {failed}",110);
		@unlink("$TEMPDIR/yara-rules.txt");
		return;		
	}		
	
	
	@unlink("$TEMPDIR/yara-rules.txt");
	$TargetDir="/var/lib/clamav";
	$TempDir=$unix->TEMP_DIR();
	$TGZFILE="yara-rules.tar.gz";
	$TARGETFILE="$TempDir/yara-rules.tar.gz";
	
	@mkdir($TargetDir,0755,true);
	
	$time=$MAIN_ARRAY["TIME"];
	$MD5GZ=$MAIN_ARRAY["MD5"];
	@unlink("$TEMPDIR/yara-rules.txt");

	$oldtime=$CURRENT_ARRAY["TIME"];
	if($oldtime==$time){
		echo "$oldtime==$time up-to-date\n";
		return;
	}

	build_progress("{yararules} {downloading}",80);
	echo "Downloading yara-rules.tar.gz\n";
		$curl=new ccurl("http://articatech.net/webfilters-databases/yara-rules.tar.gz");
		if(!$curl->GetFile($TARGETFILE)){
			echo "Downloading yara-rules.tar.gz - FAILED -\n";
			squid_admin_mysql(0, "Unable to retreive Yara rules database file", $curl->error,__FILE__,__LINE__);
			@unlink($TARGETFILE);
			return;
		}
		
		
		echo "Checking MD5 $TARGETFILE - INFO -\n";
		$zmd5=md5_file($TARGETFILE);
		
		echo "Checking MD5 $TARGETFILE - INFO - --> $zmd5\n";
		
		if($zmd5<>$MD5GZ){
			echo "Checking MD5 $TARGETFILE - FAILED - --> $zmd5 require $MD5GZ\n";
			squid_admin_mysql(0, "Corrupted Yara database file $TGZFILE $zmd5<>$MD5GZ", $curl->error,__FILE__,__LINE__);
			@unlink("$TEMPDIR/$TGZFILE");
			return false;
			
		}
		
		$tar=$unix->find_program("tar");
		$find=$unix->find_program("find");
		$cmd="$tar xf $TARGETFILE -C $TargetDir/";
		echo $cmd."\n";
		shell_exec($cmd);
		@unlink($TARGETFILE);
		
		shell_exec("/usr/bin/find $TargetDir -type f -name \"*.yar\" >$TEMPDIR/yarafind.txt");
	
		$f=explode("\n",@file_get_contents("$TEMPDIR/yarafind.txt"));
		
		
		$c=0;
		foreach ($f as $index=>$filename){
			$filename=trim($filename);
			if($filename==null){continue;}
			$basename=basename($filename);
			$size=@filesize($filename);
			$DBARRAY[$basename]=$size;
			
		}
	
	RemoveBadPatterns();
	build_progress("{yararules} {reloading}",90);
	system("/etc/init.d/clamav-daemon reload-database");
	squid_admin_mysql(2, count($DBARRAY)." Yara rules updated",null,__FILE__,__LINE__);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClamavYaraRulesDB", base64_encode(serialize($MAIN_ARRAY)));
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClamavYaraFiles", base64_encode(serialize($DBARRAY)));
	build_progress("{yararules} {success}",100);
}
function YaraRules_remove(){
	$unix=new unix();
	$find=$unix->find_program("find");
	$tmpfile=$unix->FILE_TEMP();
	shell_exec("$find /var/lib/clamav -type f -name \"*.yar\" >$tmpfile");
	
	$f=explode("\n",@file_get_contents($tmpfile));
	@unlink($tmpfile);
	
	$c=0;
	foreach ($f as $index=>$filename){
		$filename=trim($filename);
		if($filename==null){continue;}
		if(!is_file($filename)){
			echo "$filename, no such file\n";
			continue;}
		@unlink($filename);
		$c++;
	
	}
	if($c>0){
		build_progress("{yararules} {removed}: $c {rules}",70);
		squid_admin_mysql(0, "$c Yara rules was removed ( antivirus rate was reduced)", "$c Yara rules was removed.\nBecause it was disabled or  an issue on licence was detected\n","SecuriteInfo",998767);
		build_progress("{yararules} {reloading}",70);
		system("/etc/init.d/clamav-daemon reload-database");
	}
	
	@unlink("/etc/artica-postfix/settings/Daemons/ClamavYaraRulesDB");
	@unlink("/etc/artica-postfix/settings/Daemons/ClamavYaraFiles");
	

}
?>