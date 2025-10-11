<?php
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}




function postfix_increment_func($array){
	if(!isset($GLOBALS["CLASS_SOCKETS"])){ if(function_exists("LoadIncludes")){LoadIncludes();}else{return $array;} }
	$postconf=$GLOBALS["CLASS_UNIX"]->find_program("postconf");
	if(!is_file($postconf)){return $array;}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/DisableMessaging")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableMessaging", 0);
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableStopPostfix")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableStopPostfix", 0);
	}
	
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	$EnableStopPostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix"));
	if($DisableMessaging==1){$EnableStopPostfix=1;}
	if($DisableMessaging==1){return $array;}
	if($EnableStopPostfix==1){return $array;}
	$postmulti=$GLOBALS["CLASS_UNIX"]->find_program("postmulti");
	if(is_file($postmulti)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PostfixPostMulti", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PostfixPostMulti", 0);
	}
	
	

	$array[]="postfix_logger";
	$array[]="opendkim";
	$array[]="milter_greylist";
	$array[]="milter_greylist_updates";
	$array[]="postfix_schedules";
	$array[]="policyd_weight";
	$array[]="milter_regex";
	$array[]="mimedefang";
	$array[]="cleanClode";
	$array[]="PostfixClusterServer";
	$array[]="PostfixClusterClient";
	return $array;
}

function postfix_version(){

	return $GLOBALS["CLASS_UNIX"]->POSTCONF_GET("mail_version");
}


function posfix_pid():int{
	$postfix_path=$GLOBALS["CLASS_UNIX"]->find_program("postfix");
	$master_pid=$GLOBALS["CLASS_UNIX"]->POSTFIX_PID();
	if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){return $master_pid;}
	exec("$postfix_path status 2>&1",$status_results);

	foreach ($status_results as $line){
		if(preg_match("#PID:.+?([0-9]+)#", $line,$re)){
			$GLOBALS["DEBUG_LOGS"][]="postfix status: $line";
			return intval($re[1]);
		}
	}
	
	$master_bin_path=$GLOBALS["CLASS_UNIX"]->POSTFIX_MASTER_BIN_PATH();
	return $GLOBALS["CLASS_UNIX"]->PIDOF($master_bin_path,true);
}

function postfix():string{
	return "";

}



function postfix_schedules():bool{
	$ASPOST=true;
	$CRON_RELOAD=false;
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableMalwarePatrolBody")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableMalwarePatrolBody", 1); }
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	$EnableStopPostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix"));
	$EnableMalwarePatrolBody=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMalwarePatrolBody"));
	$MilterGreylistExternalDBSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreylistExternalDBSchedule"));
	if($DisableMessaging==1){$ASPOST=false;}
	if($EnableStopPostfix==1){$ASPOST=false;}
	
	
	
	
	if($EnableMalwarePatrolBody==0){
		if(is_file("/etc/cron.d/postfix-malware-patrol")){
			@unlink("/etc/cron.d/postfix-malware-patrol");
			$CRON_RELOAD=true;
			
		}
	}else{
		
		if(!is_file("/etc/cron.d/postfix-malware-patrol")){
			if($MilterGreylistExternalDBSchedule==0){$MilterGreylistExternalDBSchedule=8;}
			$schedules[1]="0 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22 * * *";
			$schedules[2]="0 2,4,6,8,10,12,14,16,18,20,22 * * *";
			$schedules[4]="0 4,8,12,16,20 * * *";
			$schedules[8]="0 8,16 * * *";
			$schedules[24]="0 1 * * *";
			$schedule=$schedules[$MilterGreylistExternalDBSchedule];
			$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("postfix-malware-patrol",$schedule,"exec.milter-greylist.update.php --malware-patrol");
			$CRON_RELOAD=true;
		}
	}
	
	
	
	if($ASPOST){

		if(!is_file("/etc/cron.d/artica-rotate-postfix")){
            $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("artica-rotate-postfix","2 0 * * *","exec.logrotate.postfix.php");
			$CRON_RELOAD=true;
		}
		
		if(is_file("/etc/cron.d/artica-logcheck-postfix")){@unlink("/etc/cron.d/artica-logcheck-postfix");}
		if(is_file("/etc/cron.d/compresscl-postfix")){@unlink("/etc/cron.d/compresscl-postfix");}
		if(is_file("/etc/cron.d/artica-postgres-postfix")){@unlink("/etc/cron.d/artica-postgres-postfix");}	
		
	}else{

		if(is_file("/etc/cron.d/artica-postgres-postfix")){
			@unlink("/etc/cron.d/artica-postgres-postfix");
			$CRON_RELOAD=true;
		}
		if(is_file("/etc/cron.d/artica-logcheck-postfix")){
			@unlink("/etc/cron.d/artica-logcheck-postfix");
			$CRON_RELOAD=true;
		}		
		if(is_file("/etc/cron.d/artica-rotate-postfix")){
			@unlink("/etc/cron.d/artica-rotate-postfix");
			$CRON_RELOAD=true;
		}
		if(is_file("/etc/cron.d/artica-compresscl-postfix")){
			@unlink("/etc/cron.d/artica-compresscl-postfix");
			$CRON_RELOAD=true;
		}		
	}
	
	if($CRON_RELOAD){shell_exec("/etc/init.d/cron reload");}
	return true;
}

function milter_greylist_updates():bool{
	$EnableBandwithCalculation=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMilterGreylistExternalDB"));
	$BandwithCalculationSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreylistExternalDBSchedule"));

	if($EnableBandwithCalculation==0){
		if(is_file("/etc/cron.d/artica-miltergreylist")){
			@unlink("/etc/cron.d/artica-miltergreylist");
			shell_exec("/etc/init.d/cron reload");
			return false;
		}
	}

	if($BandwithCalculationSchedule==0){$BandwithCalculationSchedule=8;}
	$schedules[1]="0 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22 * * *";
	$schedules[2]="0 2,4,6,8,10,12,14,16,18,20,22 * * *";
	$schedules[4]="0 4,8,12,16,20 * * *";
	$schedules[8]="0 8,16 * * *";
	$schedules[24]="0 1 * * *";
	$schedule=$schedules[$BandwithCalculationSchedule];

	
	
	$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("artica-miltergreylist",$schedule,"exec.milter-greylist.update.php");
	shell_exec("/etc/init.d/cron reload");
	return true;
}

//==============================================================================================================
function policyd_weight():string{
	if(!is_file("/usr/share/artica-postfix/bin/artica-install")){return false;}
	$EnablePolicydWeight=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePolicydWeight"));
	if($EnablePolicydWeight==null){$EnablePolicydWeight=0;}

	$l[]="[POLICYD_WEIGHT]";
	$l[]="service_name=APP_POLICYD_WEIGHT";
	$l[]="service_cmd=policydw";
	$l[]="family=postfix";
	$l[]="master_version=".GetVersionOf("policydw");
	$l[]="service_disabled=$EnablePolicydWeight";
	$l[]="watchdog_features=1";
	//$l[]="remove_cmd=--pureftpd-remove";

	if($EnablePolicydWeight==0){
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
	}

	$pid_path=$GLOBALS["CLASS_UNIX"]->POLICYD_WEIGHT_GET("PIDFILE");
	
	events("PID Path: $pid_path",__FUNCTION__,__LINE__);
	
	
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	events("master_pid: $master_pid",__FUNCTION__,__LINE__);

	$l[]="watchdog_features=1";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("policyd-weight \(");
		events("PIDOF_PATTERN: $master_pid",__FUNCTION__,__LINE__);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		events("Not running: $master_pid",__FUNCTION__,__LINE__);
		
		if(!is_file("/etc/init.d/policyd-weight")){
			shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --policyd-weight");
			
		}
		
		shell_exec2("/etc/init.d/policyd-weight start");
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
		
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"APP_POLICYD_WEIGHT");
	$l[]="";
	return implode("\n",$l);

}
//==============================================================================================================



function _milter_greylist_enabled(){


    if(!is_file("/etc/artica-postfix/settings/Daemons/EnablePostfix")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePostfix", 0);
    }

    $EnablePostfix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix");
    if($EnablePostfix==0){return 0;}

	if(!is_file("/etc/artica-postfix/settings/Daemons/MilterGreyListEnabled")){
	    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("MilterGreyListEnabled", 0);
	}
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableMilterGreylistExternalDB")) {$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableMilterGreylistExternalDB", 0);
	}

	$bin=$GLOBALS["CLASS_UNIX"]->find_program("milter-greylist");
	if(!is_file($bin)){return 0;}
	

	$EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));
	$enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListEnabled"));


	if($EnablePostfixMultiInstance==1){$enabled=0;}
	if($GLOBALS["VERBOSE"]){echo "DEBUG: EnablePostfixMultiInstance: $EnablePostfixMultiInstance\n";}
	if($GLOBALS["VERBOSE"]){echo "DEBUG: enabled: $enabled\n";}
	
	return $enabled;
}







function milter_greylist(){
    $enabled=_milter_greylist_enabled();
    if(!is_file("/etc/init.d/milter-greylist")){
        if($enabled==0){return false;}
        squid_admin_mysql(0,"Milter-greylist is enabled but uninstalled [action=install]",null,__FILE__,__LINE__);
        shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.milter-greylist.install.php --install 2>&1 &");
        return false;
    }

	$EnableStopPostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix"));
	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	$EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));

    $l[]="[MILTER_GREYLIST]";
    $l[]="service_name=APP_MILTERGREYLIST";
    $l[]="master_version=".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MILTER_GREYLIST_VERSION");
    $l[]="service_cmd=/etc/init.d/milter-greylist";

	if($enabled==1){
        $l[]="service_disabled=0";
        return implode("\n",$l);}
	if($DisableMessaging==1){
        $l[]="service_disabled=0";
        return implode("\n",$l);}
	if($EnableStopPostfix==1){
        $l[]="service_disabled=0";
        return implode("\n",$l);}

	$pid_path="/var/run/milter-greylist/milter-greylist.pid";
	if($GLOBALS["VERBOSE"]){echo "DEBUG: pid path: $pid_path\n";}

	if($pid_path==null){
		$pid_path=$GLOBALS["CLASS_UNIX"]->LOCATE_MILTER_GREYLIST_PID();
		if($GLOBALS["VERBOSE"]){echo "DEBUG: ->LOCATE_MILTER_GREYLIST_PID()= pid path: $pid_path\n";}
	}

	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	
	if($GLOBALS["VERBOSE"]){echo "DEBUG: ->LOCATE_MILTER_GREYLIST_PID()= master pid: $master_pid\n";}

	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="remove_cmd=--milter-grelist-remove";
	$l[]="family=postfix";
	
	
	


	$dirname=dirname(__FILE__);
	if($EnablePostfixMultiInstance==1){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.milter-greylist.php --startall >/dev/null 2>&1 &");
		return false;
	}

	if($enabled==0){return implode("\n",$l);}
	

	

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $dirname/exec.milter-greylist.php --start-single >/dev/null 2>&1 &");
		}
		$l[]="running=0";
		$l[]="installed=1\n";
		return implode("\n",$l);

	}
	
	$TimeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".database.time";
	$TimeExec=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($TimeFile);
	if($TimeExec>60){
		@unlink($TimeFile);
		@file_put_contents($TimeFile, time());
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.milter-greylist.php --database >/dev/null 2>&1 &");
	}
	
	@chown("/var/run/milter-greylist/milter-greylist.sock","postfix");
	@chgrp("/var/run/milter-greylist/milter-greylist.sock", "postfix");
	@chmod("/var/run/milter-greylist/milter-greylist.sock", 0777);
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"APP_MILTERGREYLIST");
	$l[]="";

	return implode("\n",$l);

}



function milter_regex_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/milter-regex.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('milter-regex');
	return $GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
}

function milter_regex(){
	
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program('milter-regex');
	if($binpath==null){if($GLOBALS["VERBOSE"]){echo "postconf no such binary.\n";}return false;}
	if(!is_file("/etc/init.d/milter-regex")){return false;}
	
	$EnableMilterRegex=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMilterRegex");
	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MILTER_REGEX_VERSION");
	
	
	if(!is_numeric($EnableMilterRegex)){$EnableMilterRegex=1;}

	$l[]="[milter_regex]";
	$l[]="service_name=milter_regex";
	$l[]="service_cmd=/etc/init.d/milter-regex";
	$l[]="master_version=$version";
	$l[]="service_disabled=$EnableMilterRegex";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	if($EnableMilterRegex==0){return implode("\n",$l);}
	$master_pid=milter_regex_pid();
	ToSyslog("Scanning Postfix Milter-regex watchdog PID: $master_pid");
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} /etc/init.d/milter-regex start >/dev/null 2>&1 &");

		}
	}
	
	@chmod("/var/run/milter-regex/milter-regex.sock",0755);
	@chown("/var/run/milter-regex/milter-regex.sock","postfix");
	@chgrp("/var/run/milter-regex/milter-regex.sock","postfix");
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"milter_regex");
	$l[]="";
	return implode("\n",$l);

}






function postfix_logger_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/etc/artica-postfix/exec.maillog.php.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("/exec.maillog.php");
}

//========================================================================================================================================================
function postfix_logger(){
	
	$l[]="[ARTICA_MYSQMAIL]";
	$l[]="service_name=APP_ARTICA_MYSQMAIL";
	$l[]="installed=1";
	$l[]="family=postfix";
	$l[]="watchdog_features=1";
	
	if(is_file("/etc/artica-postfix/DO_NOT_DETECT_POSTFIX")){
		$l[]="service_disabled=0";
		$l[]="DO_NOT_DETECT_POSTFIX=1";
		return @implode("\n", $l);
	}
	$ActAsSMTPGatewayStatistics=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActAsSMTPGatewayStatistics");
	if(!is_numeric($ActAsSMTPGatewayStatistics)){$ActAsSMTPGatewayStatistics=0;}
	if($ActAsSMTPGatewayStatistics==0){
		$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("postconf");
		if($bin_path==null){
			$l[]="service_disabled=0";
			$l[]="postconf=No such file!";
			return @implode("\n", $l);
		}
	}
	
	$master_pid=postfix_logger_pid();
	ToSyslog("Scanning Postfix mail.log watchdog PID: $master_pid");

	$l[]="[ARTICA_MYSQMAIL]";
	$l[]="service_name=APP_ARTICA_MYSQMAIL";
	$l[]="master_version=".trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
	$l[]="service_cmd=/etc/init.d/postfix-logger";
	$l[]="service_disabled=1";
	$l[]="pid_path=/etc/artica-postfix/exec.maillog.php.pid";
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "Fatal, Postfix-logger did {stopped} [{action}={start}]", null,__FILE__,__LINE__);
			shell_exec("{$GLOBALS["nohup"]} /etc/init.d/postfix-logger start >/dev/null 2>&1 &");
			$l[]="running=0\ninstalled=1";$l[]="";
			return implode("\n",$l);
		}
	}
	
	if(!is_file("/etc/monit/conf.d/ARTICA_MYSQMAIL.monitrc")){
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
		
	}

	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	


	return implode("\n",$l);

}
//========================================================================================================================================================
function opendkim(){

	$EnableDKFilter=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDKFilter");
	if(!is_numeric($EnableDKFilter)){$EnableDKFilter=0;}
	$DisconnectDKFilter=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisconnectDKFilter");
	if(!is_numeric($DisconnectDKFilter)){$DisconnectDKFilter=0;}
    $nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");



	$pid_path="/var/run/opendkim/opendkim.pid";
	$master_pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);


    if($GLOBALS["VERBOSE"]){echo  "$pid_path --- > $master_pid\n";}

	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$EnableDKFilter=0;}

	$l[]="[APP_OPENDKIM]";
	$l[]="service_name=APP_OPENDKIM";
	$l[]="master_version=".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENDKIM_VERSION");
	$l[]="service_cmd=/etc/init.d/opendkim";
	$l[]="service_disabled=$EnableDKFilter";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";

    if(!is_file("/etc/init.d/opendkim")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDKFilter",0);
        return @implode("\n",$l);
    }

	

	if($EnableDKFilter==0){$l[]="";return implode("\n",$l);}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($DisconnectDKFilter==0){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/opendkim start >/dev/null 2>&1 &");
					
			}
		}
		$l[]="";
		return implode("\n",$l);
		
	}

	if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/opendkim/opendkim.sock")){
		shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/opendkim restart >/dev/null 2>&1 &");
	}

	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);

}
//========================================================================================================================================================


function spamassassin_version(){
	if(isset($GLOBALS["spamassassin_version"])){return $GLOBALS["spamassassin_version"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("spamd");
	if(is_file("/usr/local/bin/spamd")){$bin="/usr/local/bin/spamd";}
	exec("$bin -h 2>&1",$results);
	foreach ($results as $num=>$line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#SpamAssassin Server version\s+([0-9\.]+)#",$line,$re)){continue;}
		$GLOBALS["spamassassin_version"]=$re[1];
	}

	return $GLOBALS["spamassassin_version"];

}
//========================================================================================================================================================
function valvulad_version($binary){
	if(isset($GLOBALS["valvulad_version"])){return $GLOBALS["valvulad_version"];}
	exec("$binary --version 2>&1",$results);
	foreach ($results as $num=>$line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^([0-9\.]+)#",$line,$re)){continue;}
		$GLOBALS["valvulad_version"]=$re[1];
		break;
	}
	
	return $GLOBALS["valvulad_version"];
	
	
}






function sa_update_directory(){
    $UpdateDir=null;
	$saupdate=$GLOBALS["CLASS_UNIX"]->find_program("sa-update");
	if(is_file("/usr/local/bin/sa-update")){$saupdate="/usr/local/bin/sa-update";}
	
	$f=explode("\n",@file_get_contents($saupdate));
	foreach ($f as $num=>$ligne){
		if(preg_match("#VERSION.*?=>.*?([0-9\.]+)#", $ligne,$re)){
			$UpdateDir="/var/lib/spamassassin/{$re[1]}";
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SpamAssassinUpdateDir", $UpdateDir);
			return $UpdateDir;
			break;
		}
		
	}
    return $UpdateDir;
	
}


//========================================================================================================================================================
function mimedefang():string{
	$WORKDIR=base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2E=");
	$WORKFILE=base64_decode('LmxpYw==');
	$WORKPATH="$WORKDIR/$WORKFILE";
	$FUNCTION=__FUNCTION__."()";
	if(!is_file("/etc/init.d/mimedefang")){
		return "";
    }
	$l[]="[APP_MIMEDEFANG]";
	$l[]="service_name=APP_MIMEDEFANG";
	$MasterBin=$GLOBALS["CLASS_UNIX"]->find_program("mimedefang");
	if(is_file("/usr/local/bin/mimedefang")){$MasterBin="/usr/local/bin/mimedefang";}

	
	if(!is_file($MasterBin)){
		$l[]="running=0\ninstalled=0";
		events_watchdog_postfix("$FUNCTION Master binary not found [".__LINE__."]");
		return @implode("\n", $l);
	}
	
	
	
	$MimeDefangEnabled=intval(@file_get_contents('/etc/artica-postfix/settings/Daemons/MimeDefangEnabled'));
	
	$CONTENT=strtoupper(@file_get_contents($WORKPATH));
	if($GLOBALS["VERBOSE"]){echo "DEBUG: $WORKPATH --> $CONTENT\n";}
	if($CONTENT<>"TRUE"){
		if($MimeDefangEnabled==1){
		    squid_admin_mysql(0,"License Error, disable SMTP Policies service ",null,__FILE__,__LINE__);
			shell_exec("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mimedefang.php --uninstall >/dev/null 2>&1");
			return false;
		}
	}



	

	if($GLOBALS["VERBOSE"]){echo "DEBUG: MimeDefangEnabled..: $MimeDefangEnabled\n";}
	$pid_path="/var/spool/MIMEDefang/mimedefang.pid";
	if($GLOBALS["VERBOSE"]){echo "DEBUG: pid path....: $pid_path\n";}
	$master_pid=trim(@file_get_contents($pid_path));
	if($GLOBALS["VERBOSE"]){echo "DEBUG: master pid..: $master_pid\n";}

	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	if($DisableMessaging==1){$MimeDefangEnabled=0;}


	$l[]="master_version=".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangVersion");
	$l[]="service_cmd=/etc/init.d/mimedefang";
	$l[]="service_disabled=$MimeDefangEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";
	
	if(!is_file("/etc/init.d/mimedefang")){
		shell_exec("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mimedefang.php --init");
		
	}
	
	


	if($MimeDefangEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			squid_admin_mysql(0, "Stopping MimeDefang service...", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/mimedefang stop");
		}
		events_watchdog_postfix("$FUNCTION MimeDefangEnabled:$MimeDefangEnabled [".__LINE__."]");
		return implode("\n",$l)."\n".mimedefangmx();
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		squid_admin_mysql(0, "Starting MimeDefang service (not running)..", null,__FILE__,__LINE__);
		$l[]="running=0";
		$l[]="installed=1\n";
		events_watchdog_postfix("$FUNCTION Starting MimeDefang service (not running).. !!!");
		return implode("\n",$l)."\n".mimedefangmx();
		
	}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->is_socket('/var/spool/MIMEDefang/mimedefang.sock')){
		squid_admin_mysql(0, "mimedefang.sock No such socket [ {action} = {restart} ]", null,__FILE__,__LINE__);
		shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/mimedefang restart");
	}
    sa_update();
	
	
	$MimeDefangArchiver=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangArchiver"));
	$MilterGreyListEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListEnabled"));
	$MimeDefangAutoWhiteList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangAutoWhiteList"));
    $MimeDefangSpamAssassin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangSpamAssassin"));
	
	events_watchdog_postfix("$FUNCTION MimeDefangArchiver:$MimeDefangArchiver [".__LINE__."]");
	
	if($MimeDefangArchiver==1){
		$pidtime="/etc/artica-postfix/pids/exec.mimedefang.backup.php.start.time";
		$execTime=$GLOBALS["CLASS_UNIX"]->file_time_min($pidtime);
		events_watchdog_postfix("$FUNCTION $pidtime {$execTime}mn/5 [".__LINE__."]");
		
		if($execTime>5){
			events_watchdog_postfix("RUNNING /usr/share/artica-postfix/exec.mimedefang.backup.php [".__LINE__."]");
			shell_exec("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mimedefang.backup.php >/dev/null 2>&1 &");
		}
	}
	
	if($MimeDefangSpamAssassin==1){

		$pidtime="/etc/artica-postfix/pids/exec.mimedefang.quarantine.php.start.time";
		$execTime=$GLOBALS["CLASS_UNIX"]->file_time_min($pidtime);
		if($execTime>5){
			shell_exec("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.mimedefang.quarantine.php >/dev/null 2>&1 &");
		}
		
		
		$pidtime="/etc/artica-postfix/pids/exec.spamassassin.update.artica.time";
		$execTime=$GLOBALS["CLASS_UNIX"]->file_time_min($pidtime);
		if($execTime>15){
			shell_exec("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.spamassassin.update.artica.php >/dev/null 2>&1 &");
		}
		
	}
	
	
	if($MilterGreyListEnabled==1){
		if($MimeDefangAutoWhiteList==1){
			$pidtime="/etc/artica-postfix/pids/exec.milter-greylist.php.SingleInstance_whitelist.pid";
			$execTime=$GLOBALS["CLASS_UNIX"]->file_time_min($pidtime);
			if($execTime>10){
				shell_exec("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.milter-greylist.php --AutoWhiteList >/dev/null 2>&1 &");
			}
		}
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l)."\n".mimedefangmx();

}
//=======================================================================================================


function sa_update(){
    $MimeDefangSpamAssassin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangSpamAssassin"));

    if($MimeDefangSpamAssassin==0){
        if(is_file("/etc/cron.d/sa-update")){
            @unlink("/etc/cron.d/sa-update");
            system("/etc/init.d/cron reload");
        }
        return false;
    }

    $SpamAssassinUpdateDir=sa_update_directory();
    if($SpamAssassinUpdateDir<>null){
        if(!is_dir($SpamAssassinUpdateDir)){
            squid_admin_mysql(0, "Spamassassin $SpamAssassinUpdateDir empty, running sa-update",null,__FILE__,__LINE__);
            shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.spamassassin.php --sa-update >/dev/null 2>&1 &");
        }
    }

    if(!is_file("/etc/cron.d/sa-update")){
        $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("sa-update","10 4 * * *","exec.spamassassin.php --sa-update");
    }

return true;

}

function mimedefangmx(){
	
	$pid_path="/var/spool/MIMEDefang/mimedefang-multiplexor.pid";
	if($GLOBALS["VERBOSE"]){echo "DEBUG: pid path....: $pid_path\n";}
	$master_pid=trim(@file_get_contents($pid_path));
	if($GLOBALS["VERBOSE"]){echo "DEBUG: master pid..: $master_pid\n";}

	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("mimedefang-multiplexor");
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){

        $master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			@file_put_contents($pid_path, $master_pid);
		}
	}



	$MimeDefangEnabled=intval(@file_get_contents('/etc/artica-postfix/settings/Daemons/MimeDefangEnabled'));
	$l[]="[APP_MIMEDEFANGX]";
	$l[]="service_name=APP_MIMEDEFANGX";
	$l[]="master_version=".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangVersion");
	$l[]="service_cmd=/etc/init.d/mimedefang";
	$l[]="service_disabled=$MimeDefangEnabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=postfix";



	if($MimeDefangEnabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/mimedefang stop");}
		return implode("\n",$l);
	}

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/mimedefang start");
        $l[] = "running=0";
        $l[] = "installed=1\n";
        return implode("\n", $l);
    }
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	
	if(!is_dir("/home/artica/SQLITE/MIMEDEFANG_STATS")){@mkdir("/home/artica/SQLITE/MIMEDEFANG_STATS",0755,true);}
	if(!is_dir("/home/artica/MIMEDEFANG/MYSQL_FAILED")){@mkdir("/home/artica/MIMEDEFANG/MYSQL_FAILED",0755,true);}
	
	shell_exec("chown -R postfix:postfix /home/artica/SQLITE/MIMEDEFANG_STATS");
	shell_exec("chown -R postfix:postfix /home/artica/MIMEDEFANG");
	shell_exec("{$GLOBALS["nohup"]} /usr/local/bin/md-mx-ctrl -s /var/spool/MIMEDefang/mimedefang-multiplexor.sock msgs >/etc/artica-postfix/settings/Daemons/MIMEDEFANG_MSGS 2>&1 &");
	shell_exec("{$GLOBALS["nohup"]} /usr/local/bin/md-mx-ctrl -s /var/spool/MIMEDefang/mimedefang-multiplexor.sock status >/etc/artica-postfix/settings/Daemons/MIMEDEFANG_WORKERS 2>&1 &");
	

	return implode("\n",$l);

}
//========================================================================================================================================================
function cleanClode():bool{
	
	$f[]="/usr/share/artica-postfix/exec.mailarchiver.php";
	$f[]="/usr/share/artica-postfix/miniadmin.postfix.archive.php";
	$f[]="/usr/share/artica-postfix/postfix.archiver.php";
	$f[]="/usr/share/artica-postfix/postfix.backup.fly.php";
	$f[]="/usr/share/artica-postfix/bin/milter_archiver.pl";


	foreach ($f as $filename){
		if(is_file($filename)){@unlink($filename);}
	}
    return true;
	
}



function PostfixClusterClient(){
	$PowerDNSEnableClusterSlave=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
	if($PowerDNSEnableClusterSlave==0){
		if(is_file("/etc/cron.d/PowerDNSClusterClient")){ @unlink("/etc/cron.d/PowerDNSClusterClient"); UNIX_RESTART_CRON(); }
		return false;
	}



	$TimeFile=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/settings/Daemons/PowerDNSClusterClientMD5");
	if($TimeFile<1800){return false;}
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.pdns.php --import-cluster >/dev/null 2>&1 &");
	return true;
}

function PostfixClusterServer(){
	$PowerDNSEnableClusterMaster=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
	if($PowerDNSEnableClusterMaster==0){
		if(is_file("/etc/cron.d/PowerDNSClusterServer")){ @unlink("/etc/cron.d/PowerDNSClusterServer"); UNIX_RESTART_CRON(); }
		return false;
	}

	if(!is_file("/etc/cron.d/PowerDNSClusterServer")){
		$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("PowerDNSClusterServer","0,5,10,15,20,25,30,35,40,45,50,55 * * * *","exec.pdns.php --checksum-cluster");
		UNIX_RESTART_CRON();
	}

	$TimeFile=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/pdns.checksum_cluster.time");
	if($TimeFile<5){return false;}
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.pdns.php --checksum-cluster >/dev/null 2>&1 &");
	return true;
}

function events_watchdog_postfix($text):bool{
	if($GLOBALS["VERBOSE"]){echo "$text\n";}
	if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix";}
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	$filename=basename(__FILE__);
	$logFile="{$GLOBALS["ARTICALOGDIR"]}/postfix-logger.debug";
    if(is_file($logFile)) {
        $size = filesize($logFile);
        if ($size > 5000000) {
            unlink($logFile);
        }
    }
	error_log("[{$GLOBALS["MYPID"]}]: $filename $text");
	events($text);
    return true;
}