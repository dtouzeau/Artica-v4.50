<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";exit();}}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["SERVICE_NAME"]="HyperCache Web service";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');

$WindowsUpdateCaching=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsUpdateCaching"));
if($WindowsUpdateCaching==0){
	echo "WindowsUpdateCaching === $WindowsUpdateCaching ABORT\n";
	exit();}


xstart();
function xstart(){DirectorySize();}

function xFormatBytes($kbytes,$nohtml=false){
	$spacer=null;
	if($kbytes>1048576){
		$value=round($kbytes/1048576, 2);
		if($value>1000){
			$value=round($value/1000, 2);
			return "$value{$spacer}TB";
		}
		return "$value{$spacer}GB";
	}
	elseif ($kbytes>=1024){
		$value=round($kbytes/1024, 2);
		return "$value{$spacer}MB";
	}
	else{
		$value=round($kbytes, 2);
		return "$value{$spacer}KB";
	}
}

function CheckPartitionPercentage(){
	if(!isset($GLOBALS["WindowsUpdateCachingDir"])){
		$GLOBALS["WindowsUpdateCachingDir"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsUpdateCachingDir");
		if($GLOBALS["WindowsUpdateCachingDir"]==null){$GLOBALS["WindowsUpdateCachingDir"]="/home/squid/WindowsUpdate";}
	}
	$dir=$GLOBALS["WindowsUpdateCachingDir"];
	$unix=new unix();
	$partition=$unix->DIRPART_INFO($dir);
	return $partition["POURC"];
	
}

function DirectorySize(){

	if(!isset($GLOBALS["WindowsUpdateCachingDir"])){
		$GLOBALS["WindowsUpdateCachingDir"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsUpdateCachingDir");
		if($GLOBALS["WindowsUpdateCachingDir"]==null){$GLOBALS["WindowsUpdateCachingDir"]="/home/squid/WindowsUpdate";}
	}


	$dir=$GLOBALS["WindowsUpdateCachingDir"];
	$unix=new unix();

	$time=$unix->file_time_min("/usr/share/artica-postfix/ressources/logs/web/WindowsUpdate.state");
	if(!$GLOBALS["FORCE"]){
		if($time<120){return;}
	}
	
	$directories=$unix->dirdir($dir);
	while (list ($num, $ligne) = each ($directories) ){
		$domain=basename($num);
		echo "Checking size of $num/$domain\n";
		$size=$unix->DIRSIZE_KO_nocache("$num/$domain");
		echo " Checking size of $domain = $size\n";
		$DOMAINS[$domain]=$size;
	}

	$size=$unix->DIRSIZE_KO_nocache($dir);
	$partition=$unix->DIRPART_INFO($dir);
	

	$TOT=$partition["TOT"];
	$AIV=$partition["AIV"];
	$percent=($size/$TOT)*100;
	$percent=round($percent,3);
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WindowsUpdatePartitionPercent",round($percent));

	$ARRAY["PERCENTAGE"]=$percent;
	$ARRAY["SIZEKB"]=$size;
	$ARRAY["PART"]=$TOT;
	$ARRAY["AIV"]=$AIV;
	$ARRAY["DOMAINS"]=$DOMAINS;

	@unlink("/usr/share/artica-postfix/ressources/logs/web/WindowsUpdate.state");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/WindowsUpdate.state", serialize($ARRAY));

}