<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
$GLOBALS["CLIENT_META_IP"]=$IPADDR;
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}

	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.blackboxes.inc');
	include_once('ressources/class.mysql.squid.builder.php');		
	include_once('ressources/class.mysql.dump.inc');
	include_once('ressources/class.mysql.syslogs.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');



if(isset($_REQUEST["FILENAME"])){stats_appliance_receive();exit;}


function stats_appliance_receive(){
	$hostname=$_POST["HOSTNAME"];
	$sock=new sockets();
	$FILENAME=$_POST["FILENAME"];
	$SIZE=$_POST["SIZE"];
	$MD5FILE=$_POST["MD5FILE"];
	$UUID=null;
	if(isset($_POST["UUID"])){$UUID=trim($_POST["UUID"]); }
	if($UUID==null){die("NO UUID!???");}
	
	
	$content_dir=dirname(__FILE__)."/ressources/conf/upload/StatsApplianceLogs/$UUID";
	
	@mkdir($content_dir,0755,true);
	if(!is_dir($content_dir)){
		echo "$content_dir: Error Unable create directory..\n";
		echo "\n\n<RESULTS>FAILED</RESULTS>\n\n";
	}
	
	foreach ($_FILES as $key=>$arrayF){
		$type_file = $arrayF['type'];
		$name_file = $arrayF['name'];
		$tmp_file  = $arrayF['tmp_name'];
	
		
	
		$target_file="$content_dir/$name_file";
		if(file_exists( $target_file)){@unlink( $target_file);}
		if( !move_uploaded_file($tmp_file, $target_file) ){
			print_r($arrayF);
			writelogs_stats("Error Unable to Move File : $target_file FROM {$GLOBALS["CLIENT_META_IP"]}",__FUNCTION__,__FILE__,__LINE__);
			echo "$content_dir: Error Unable to Move File : $target_file\n";
			echo "\n\n<RESULTS>FAILED</RESULTS>\n\n";
			return;
		}
		
		$size=@filesize($target_file);
		logsize($UUID,$GLOBALS["CLIENT_META_IP"],$hostname,$name_file,$size);
	}
	
	
	
	$md=md5_file($target_file);
	if($md<>$MD5FILE){
		writelogs_stats("Error $target_file: Signature differ... FROM {$GLOBALS["CLIENT_META_IP"]}",__FUNCTION__,__FILE__,__LINE__);
		echo "$target_file: Signature differ...\n";
		echo "\n\n<RESULTS>FAILED</RESULTS>\n\n";
		return;
	}
	
	echo "\n\n<RESULTS>SUCCESS</RESULTS>\n\n";
	$sock->getFrameWork("squid.php?scan-proxy-logs=yes&uuid=$UUID");
	
	
	
	

}
function logsize($uuid,$ipaddr,$hostname,$file,$size){
	$time=time();
	$key=md5("$uuid$size$file$time$ipaddr");
	writelogs_stats("$uuid,$ipaddr,$hostname,$file,$size",__FUNCTION__,__FILE__,__LINE__);
	$DatabasePath="/usr/share/artica-postfix/ressources/conf/STATSAPPUPLD_". date("Ymdi").".db";
	if(!berekley_db_create($DatabasePath)){
		writelogs_meta("Fatal: Creating $DatabasePath",__FUNCTION__,__FILE__,__LINE__);
		return;}
		$db_con = @dba_open($DatabasePath, "c","db4");
		$ARRAY["UUID"]=$uuid;
		$ARRAY["IPADDR"]=$ipaddr;
		$ARRAY["HOSTNAME"]=$hostname;
		$ARRAY["SIZE"]=$size;
		$ARRAY["FILE"]=$file;
		$ARRAY["TIME"]=time();
		dba_replace($key,base64_encode(serialize($ARRAY)),$db_con);
		@dba_close($db_con);
}



function berekley_db_create($db_path){
	if(is_file($db_path)){return true;}
	$db_desttmp = @dba_open($db_path, "c","db4");
	@dba_close($db_desttmp);
	if(!is_file($db_path)){return false;}
	return true;

}


