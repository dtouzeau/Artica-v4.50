<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(!Build_pid_func(__FILE__,"MAIN")){
	events("Already executed.. aborting the process");
	exit();
}

if($argv[1]=='--date'){echo date("Y-m-d H:i:s")."\n";}

$pid=getmypid();
$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
@mkdir("/var/log/artica-postfix/squid-stats",0666,true);
events("running $pid ");
file_put_contents($pidfile,$pid);
$pipe = fopen("php://stdin", "r");
while(!feof($pipe)){
	$buffer .= fgets($pipe, 4096);
	Parseline($buffer);
	$buffer=null;
}

fclose($pipe);
events("Shutdown...");
exit();



function Parseline($buffer){
$buffer=trim($buffer);
if($buffer==null){return null;}
if(strpos($buffer,"Squid Cache purged")>0){return null;}
if(preg_match("#bidirectional pipe to",$buffer)){return null;}



if(preg_match('#url=(.+?)&source=(.+?)\/.+?&virus=.+?:(.+?)\+FOUND#',$buffer,$re)){
	$uri=$re[1];$ipsrc=$re[2];$virus=$re[3];Builsql($uri,$ipsrc,$virus);return;
}



events("Not filtered: $buffer");

}




function Builsql($uri,$ip,$virus){
	$virus=str_replace("+"," ",$virus);
	$virus=trim($virus);
	$md5=md5(time()."$uri,$ip,$virus");
	$ip=GetComputerName($ip);
	
	$sql="INSERT INTO `antivirus_events` (`zDate`, `TaskName`, `email`, `VirusName`, `InfectedPath`, `ComputerName`, `zmd5`) 
	VALUES (NOW(), 'HTTP Scan', 0, '$virus', '$uri', '$ip', '$md5')";
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){
		events($q->mysql_error);
		events($sql);
		return;
	}
	
	events("Virus $virus found from $uri to $ip");
  
}



function events($text){
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/artica-postfix/squid-tail.debug";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
		@fclose($f);	
		}
		
function GetComputerName($ip){
	if($GLOBALS["resvip"][$ip]<>null){return $GLOBALS["resvip"][$ip];}
	$name=gethostbyaddr($ip);
	$GLOBALS["resvip"]=$name;
	return $name;
	}
		

?>