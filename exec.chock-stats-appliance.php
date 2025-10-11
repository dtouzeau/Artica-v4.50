<?php
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.acls.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.ini.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");



xstart();

function xstart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){exit();}
	
	$xtime=$unix->file_time_min($pidTime);
	if($xtime<10){
		echo "Only each 10mn ($xtime mn)\n";
		exit();}
	
	@file_put_contents($pidfile, getmypid());
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	$InfluxUseRemoteIpaddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemoteIpaddr"));
	$InfluxUseRemotePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemotePort"));
	$InfluxUseRemoteArticaPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemoteArticaPort"));
	
	if($InfluxUseRemoteArticaPort==0){$InfluxUseRemoteArticaPort=9000;}

	$myhostname=$unix->hostname_g();
	$cnxlog[]="Trying connection to $InfluxUseRemoteIpaddr:$InfluxUseRemoteArticaPort";
	$curl=new ccurl("https://$InfluxUseRemoteIpaddr:$InfluxUseRemoteArticaPort/artica.meta.listener.php?influx-client-restart=yes",false);
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		$cnxlog=debug_curl($curl->CURL_ALL_INFOS,$cnxlog);
		squid_admin_mysql(1, "Unable to chock the remote statistics appliance (".$curl->error.")", @implode("\n", $cnxlog));
		return;
	}
	if(!preg_match("#>OK<#is", $curl->data,$re)){
		squid_admin_mysql(1, "Unable to chock the remote statistics appliance Protocol error", "Please upgrade your Statistics appliance to the new version");
	}
}




function debug_curl($array,$finalarray){
	foreach ($array as $num=>$val){
		if(is_array($val)){
			$finalarray[]="$num:";
            foreach ($val as $a=>$b){$finalarray[]="\t$a = $b";}
			continue;
			
		}
		$finalarray[]="$num:$val";
		

	}


	return $finalarray;

}