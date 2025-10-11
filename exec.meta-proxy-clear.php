<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$GLOBALS["LogFileDeamonLogDir"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogFileDeamonLogDir");
if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid/realtime-events";}
if(is_file("/usr/local/ArticaStats/bin/postgres")){
	$GLOBALS["LogFileDeamonLogDir"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogFileDeamonLogPostGresDir");
	if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid-postgres/realtime-events";}
}



$dir_handle = @opendir("{$GLOBALS["LogFileDeamonLogDir"]}");

if(!$dir_handle){
	exit();
}

while ($file = readdir($dir_handle)) {
	  if($file=='.'){continue;}
	  if($file=='..'){continue;}
	  if(is_dir("{$GLOBALS["LogFileDeamonLogDir"]}/$file")){continue;}
	  
	  echo "Remove $file\n";
	  @unlink("{$GLOBALS["LogFileDeamonLogDir"]}/$file");
		
		
}