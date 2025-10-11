<?php
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

xstart();


function xstart(){
	$unix=new unix();
	
	if($unix->ServerRunSince()<5){
		echo "ServerRunSince --> DIE\n";
		exit();}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		squid_admin_mysql(2, "Already PID $pid {running} {since} {$timepid}mn" , __FUNCTION__, __FILE__, __LINE__, "postfix-stats");
		return;
	}
	
	
	$q=new postgres_sql();
	$sql="SELECT COUNT(*) as tcount FROM smtprefused";
	$ligne=$q->mysqli_fetch_array($sql);
	if(!$q->ok){echo $q->mysql_error."\n";}
	$SMTP_REFUSED["NUMBER"]=$ligne["tcount"];
	$SMTP_REFUSED["DATE"]=time();
	echo "SMTP_REFUSED:{$ligne["tcount"]}\n";
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SMTP_REFUSED", serialize($SMTP_REFUSED));
	
	
}
