#!/usr/bin/php -q
<?php

$line=@implode("|", $argv);
$fd = fopen("php://stdin", "r");
$buffer = ""; 
ToSyslog("Open logger");
while (!feof($fd)) {
	
	$buffer= fread($fd, 1024);
	if(trim($buffer)<>null){
		send_to_mysql(trim($buffer));
	}
}

fclose($fd);

ToSyslog("Closing logger");
function ToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}


function send_to_mysql($buffer){
	
	
	$dbpath="/home/artica/postfix/milter-greylist/logger/".date("YmdH").".miltergreylist.db";
	if(!berekley_db_create($dbpath)){return;}
	
	$db_con = @dba_open($dbpath, "w","db4");
	if(!$db_con){
		ToSyslog("send_to_mysql:: $dbpath failed connect");
		@dba_close($db_con);
		return false;
			
	}
	
	
	$results=explode(",",$buffer);
	
	$md5=md5($buffer.time());
	$instance=$results[0];
	$publicip=$results[1];
	$mailfrom=mysql_escape_string2($results[2]);
	$rcpt=mysql_escape_string2($results[3]);
	$failed=$results[6];
	$Country=$results[7];
	$HOUR=date('H');
	$date=date("Y-m-d H:i:s");
	$tablename="mgreyh_".date("YmdH");
	$mailfromZ=explode("@",$mailfrom);
	
	$rcptZ=explode("@",$rcpt);
	$prefix="INSERT IGNORE INTO $tablename (`zmd5`,`ztime`,`zhour`,`mailfrom`,`instancename`,`mailto`,`domainfrom`,`domainto`,`senderhost`,`failed`) VALUES ";
	$suffix="('$md5','$date','$HOUR','$mailfrom','$instance','$rcpt','{$mailfromZ[1]}','{$rcptZ[1]}','$publicip','$failed')";
	
	$md5=md5($suffix);
	dba_replace($md5,$suffix,$db_con);
	@dba_close($db_con);
	return;

	
}

function mysql_escape_string2($line){

	$search=array("\\","\0","\n","\r","\x1a","'",'"');
	$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
	return str_replace($search,$replace,$line);
}

function berekley_db_create($db_path){
	if(is_file($db_path)){return true;}
	$db_desttmp = @dba_open($db_path, "c","db4");
	@dba_close($db_desttmp);
	if(is_file($db_path)){return true;}
	ToSyslog("berekley_db_create:: Failed Creating $db_path database");
}

function events($text){
	if(trim($text)==null){return;}

	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile="/var/log/milter-greylist.RTT.debug";

	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$date:[".basename(__FILE__)."] $pid `$text`\n";}
	@fwrite($f, "$date:[".basename(__FILE__)."] $pid `$text`\n");
	@fclose($f);

}

function SEND_MYSQL($sql){

	$bd=@mysqli_connect("localhost","root",null,null,0,"/var/run/mysqld/mysqld.sock");
	if(!$bd){
		$des=@mysqli_error();
		$errnum=@mysqli_errno();
		ToSyslog("MySQL error: $errnum $des");
		return;
	}
	$ok=@mysqli_select_db($bd,"postfixlog");
	if(!$ok){
		$des=@mysqli_error();
		$errnum=@mysqli_errno();
		ToSyslog("MySQL error: $errnum $des");
		@mysqli_close($bd);
		return;
	}
	$results=@mysqli_query($bd,$sql);
	if(!$results){
		$des=@mysqli_error();
		$errnum=@mysqli_errno();
		ToSyslog("MySQL error: $errnum $des");
	}

	@mysqli_close($bd);

}
?>