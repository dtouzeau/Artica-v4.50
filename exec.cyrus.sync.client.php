<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(!Build_pid_func(__FILE__,"MAIN")){
	writelogs(basename(__FILE__).":Already executed.. aborting the process",basename(__FILE__),__FILE__,__LINE__);
	exit();
}
$_GET["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/sync_client.log";
if($argv[1]=='--silent'){events("Silent specified");$_GET["SILENT"]=true;}
$users=new usersMenus();
if(!is_file($users->ctl_mboxlist)){
	events("Unable to stat ctl_mailboxlist");
	exit();
}

if(!is_file($users->cyrus_sync_client_path)){
	events("Unable to stat sync_client");
	exit();
}
$unix=new unix();
$tempdir=$unix->TEMP_DIR();
events("Exporting mailbox list");
system("su - cyrus -c \"$users->ctl_mboxlist -d\" >$tempdir/ctl_mboxlist");

$datas=file_get_contents("$tempdir/ctl_mboxlist");
$tbl=explode("\n",$datas);
if(!is_array($tbl)){
	events("Fatal ERROR");
	exit();
}

foreach ($tbl as $num=>$ligne){
	if(!preg_match("#^user\..+?\s+[0-9]+\s+default(.+?)\s+#",$ligne,$re)){continue;}
	$arr[trim($re[1])]=true;
}

if(!is_array($arr)){
	events("No mailboxes here");
	exit();
}

if(is_file($_GET["LOG_FILE"])){chmod($_GET["LOG_FILE"],0755);}

$count=count($arr);
$ct=0;
events("$count mailboxes here");
if(is_file($_GET["LOG_FILE"])){chmod($_GET["LOG_FILE"],0755);}

foreach ($arr as $num=>$ligne){
	$ct=$ct+1;
	events("$ct/$count sync \"$num\"");
	system($users->cyrus_sync_client_path." -u $num");
	if(is_file($_GET["LOG_FILE"])){chmod($_GET["LOG_FILE"],0755);}
	
}
if(is_file($_GET["LOG_FILE"])){chmod($_GET["LOG_FILE"],0755);}
events("done");
exit();

function events($text){
			$pid=getmypid();
		if(!$_GET["SILENT"]){echo "$date [$pid]: $text\n";}
		$date=date('Y-m-d H:i:s');
		$logFile=$_GET["LOG_FILE"];
		$size=@filesize($logFile);
		if($size>5000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$towrite="$date ".basename(__FILE__)."[$pid]: $text\n";
		if($_GET["DEBUG"]){echo $towrite;}
		@fwrite($f, $towrite);
		@fclose($f);	
		
		}

?>