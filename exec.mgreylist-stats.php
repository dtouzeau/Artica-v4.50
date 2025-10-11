<?php


	openlog($file, LOG_PID , LOG_MAIL);
	if(function_exists("syslog")){ syslog($LOG_SEV, @implode("", $argv));}
	closelog();

receive($argv);
	exit();



function receive($instance,$line,$argvz){
	
	$t=time();
	$md5=md5("$t".@implode("", $argvz));
	$array=array("T"=>$t,"I"=>$instance,"A"=>$line,"C"=>$argvz);
	@file_put_contents("/var/log/milter-greylist/$md5.log", serialize($array));
	
}
?>