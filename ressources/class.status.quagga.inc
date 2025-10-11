<?php

function OSPF_PID():int{
    $pidfile=OSPF_PID_PATH;
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    $ospfd=$GLOBALS["CLASS_UNIX"]->find_program("ospfd");
    $pid=$GLOBALS["CLASS_UNIX"]->PIDOF($ospfd);
    return intval($pid);
}
function ZEBRA_PID():int{
    $pidfile=ZEBRA_PID_PATH;
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return intval($pid);}
    $ospfd=$GLOBALS["CLASS_UNIX"]->find_program("zebra");
    $pid=$GLOBALS["CLASS_UNIX"]->PIDOF($ospfd);
    return intval($pid);
}

function ZEBRA_STATUS():string{
    $Enable         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOSPFD"));
    $rphp           = "{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]}";
    $root           =  $GLOBALS["BASE_ROOT"];
    $initd          = "/etc/init.d/zebra";
    $statusfile     = PROGRESS_DIR."/zebra.status";
    $CODE_NAME      = "APP_ZEBRA";
    $CODE_NAME_TEXT = "{".$CODE_NAME."}";
    $scrfile        = "exec.quagga.php";

    if(!is_file($initd)){
        if($Enable==1){
            squid_admin_mysql(0, "$CODE_NAME_TEXT seems enabled but install corrupted [action=install]","$initd missing",__FILE__,__LINE__);
            $GLOBALS["CLASS_UNIX"]->framework_exec("$scrfile --install");
            @file_put_contents($statusfile,"");
            return "";
        }
        @file_put_contents($statusfile,"");
        return "";
    }
    if(is_file($initd)){
        if($Enable==0){
            squid_admin_mysql(0, "$CODE_NAME_TEXT seems installed but Should be disabled [action=uninstall]","$initd exists",__FILE__,__LINE__);
            $GLOBALS["CLASS_UNIX"]->framework_exec("$scrfile --uninstall");
            @file_put_contents($statusfile,"");
            return "";
        }
    }

    $l[]="[$CODE_NAME]";
    $l[]="service_name=$CODE_NAME";
    $l[]="service_cmd=$initd";
    $l[]="family=proxy";
    $l[]="watchdog_features=1";
    $l[]="service_disabled=$Enable";

    if($Enable==0){
        $l[]="running=0\ninstalled=0";
        $l[]="";
        squid_admin_mysql(0, "$CODE_NAME_TEXT seems not enabled but install exists [action=uninstall]",null,__FILE__,__LINE__);
        $GLOBALS["CLASS_UNIX"]->framework_exec("$scrfile --uninstall");
        @file_put_contents($statusfile,@implode("\n",$l));
        return implode("\n",$l);
    }
    $master_pid=ZEBRA_PID();
    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){squid_admin_mysql(0, "{warning} $CODE_NAME_TEXT {service_stopped} [{action}={start}]", null,__FILE__,__LINE__);}
            $GLOBALS["CLASS_UNIX"]->framework_exec("$scrfile --start");
        }

        $l[]="master_pid=$master_pid";
        $l[]="running=0";
        $l[]="installed=1";
        $l[]="";
        @file_put_contents($statusfile,@implode("\n",$l));
        return implode("\n",$l);

    }

    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid);
    $l[]="";
    @file_put_contents($statusfile,@implode("\n",$l));
    return implode("\n",$l);
}

function OSPF_STATUS():string{
    $Enable         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOSPFD"));
    $rphp           = "{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]}";
    $root           =  $GLOBALS["BASE_ROOT"];
    $initd          = "/etc/init.d/ospfd";
    $statusfile     = PROGRESS_DIR."/ospfd.status";
    $CODE_NAME      = "APP_OSPF";
    $CODE_NAME_TEXT = "{".$CODE_NAME."}";
    $scrfile        = "exec.quagga.php";

    if(!is_file($initd)){
        if($Enable==1){
            squid_admin_mysql(0, "$CODE_NAME_TEXT seems enabled but install corrupted [action=install]","$initd missing",__FILE__,__LINE__);
            shell_exec("$rphp $root/$scrfile --install 2>&1 &");
            @file_put_contents($statusfile,"");
            return "";
        }
        @file_put_contents($statusfile,"");
        return "";
    }


	$l[]="[$CODE_NAME]";
	$l[]="service_name=$CODE_NAME";
	$l[]="service_cmd=$initd";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";
    $l[]="service_disabled=$Enable";

	if($Enable==0){
		$l[]="running=0\ninstalled=0";
		$l[]="";
        squid_admin_mysql(0, "$CODE_NAME_TEXT seems not enabled but install exists [action=uninstall]",null,__FILE__,__LINE__);
        shell_exec("$rphp $root/$scrfile --uninstall 2>&1 &");
        @file_put_contents($statusfile,@implode("\n",$l));
		return implode("\n",$l);
	}
	$master_pid=OSPF_PID();
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){squid_admin_mysql(0, "{warning} $CODE_NAME_TEXT {service_stopped} [{action}={start}]", null,__FILE__,__LINE__);}
			$cmd=trim("$rphp $root/$scrfile --start");
			shell_exec2($cmd);
		}

		$l[]="master_pid=$master_pid";
		$l[]="running=0";
		$l[]="installed=1";
		$l[]="";
        @file_put_contents($statusfile,@implode("\n",$l));
		return implode("\n",$l);

	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
    @file_put_contents($statusfile,@implode("\n",$l));
	return implode("\n",$l);


}

