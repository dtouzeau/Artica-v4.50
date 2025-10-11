<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
function APP_ARTICAPCAP_PID(){
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/articapsniffer.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("[articapsniffer]");
}


//===========================================================================================================
function APP_ARTICAPCAP():string{
    $serviceinit="/etc/init.d/articapcap";
    $status_file=PROGRESS_DIR."/articapcap.status";
    $ArticaPSnifferDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferDaemon"));

    $l[]="[APP_ARTICAPCAP]";
    $l[]="service_name=APP_ARTICAPCAP";
    $l[]="service_cmd=/etc/init.d/articapcap";
    $l[]="master_version=".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_ARTICAPCAP_VERSION");
    $l[]="service_disabled=$ArticaPSnifferDaemon";
    $l[]="pid_path=/var/run/articapsniffer.pid";
    $l[]="watchdog_features=1";
    $l[]="family=network";


    if($ArticaPSnifferDaemon==0){
        if(is_file($serviceinit)){
            squid_admin_mysql(0, "Uninstalling Artica PCAP (not enabled)", null,__FILE__,__LINE__);
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.articapcap.php --uninstall");

        }
        @file_put_contents($status_file,@implode("\n",$l));
        return @implode("\n",$l);
    }

    if(!is_file("/etc/init.d/articapcap")){
        squid_admin_mysql(0, "Artica PCAP is not installed [action=install]", null,__FILE__,__LINE__);
        $GLOBALS["CLASS_UNIX"]->framework_exec("exec.articapcap.php --install");
        @file_put_contents($status_file,@implode("\n",$l));
       return @implode("\n",$l);
    }


	$master_pid=APP_ARTICAPCAP_PID();

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "Artica PCAP {stopped} [{action}={start}]", null,__FILE__,__LINE__);
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.articapcap.php --start");
		}
		$l[]="";
        @file_put_contents($status_file,@implode("\n",$l));
        return @implode("\n",$l);

	}

	$l[]=GetMemoriesOf($master_pid,"APP_ARTICAPCAP");
    @file_put_contents($status_file,@implode("\n",$l));
    return @implode("\n",$l);

}

//======================================================================================================================
?>