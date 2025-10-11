<?php
if (!defined('CURLOPTTYPE_OBJECTPOINT')){define('CURLOPTTYPE_OBJECTPOINT', 10000);}
if (!defined('CURLOPT_NOPROXY')){define('CURLOPT_NOPROXY', CURLOPTTYPE_OBJECTPOINT + 177);}
function elasticsearch_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/elasticsearch/elasticsearch.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    $pid=$GLOBALS["CLASS_UNIX"]->PIDOF("/usr/share/elasticsearch/jdk/bin/java");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
        @file_put_contents("/var/run/elasticsearch/elasticsearch.pid",$pid);
        return $pid;
    }
}
//========================================================================================================================================================
function elasticsearch(){
    $prfix="{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix";
    $EnableElasticSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableElasticSearch"));
    if(!is_file("/etc/init.d/elasticsearch")){
        if($EnableElasticSearch==1){
            squid_admin_mysql(0, "RealTime Statistics database daemon not installed [action=install]",
                null,__FILE__,__LINE__);
            $cmd=trim("$prfix/exec.elastic.search.php --install  >/dev/null 2>&1 &");
            shell_exec2($cmd);
            return null;
        }
        return null;
    }

    if($EnableElasticSearch==0){
        if(is_file("/etc/init.d/elasticsearch")){
            squid_admin_mysql(0, "RealTime Statistics database daemon installed [action=uninstall]",
                null,__FILE__,__LINE__);
            $cmd=trim("$prfix/exec.elastic.search.php --uninstall  >/dev/null 2>&1 &");
            shell_exec2($cmd);
            return null;
        }
        return null;
    }

	$version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTICSEARCH_VERSION");
	$l[]="[APP_ELASTICSEARCH]";
	$l[]="service_name=APP_ELASTICSEARCH";
	$l[]="service_cmd=/etc/init.d/elasticsearch";
	$l[]="master_version=$version";
	$l[]="service_disabled=1";
	$l[]="pid_path=/var/run/elasticsearch/elasticsearch.pid";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	$master_pid=elasticsearch_pid();
	$ServerRunSince=$GLOBALS["CLASS_UNIX"]->ServerRunSince();
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($ServerRunSince>3){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				squid_admin_mysql(0, "RealTime Statistics database daemon {stopped} [{action}={start}]", null,__FILE__,__LINE__);
				$cmd=trim("{$GLOBALS["NICE"]} /etc/init.d/elasticsearch restart  >/dev/null 2>&1 &");
				shell_exec2($cmd);
			}
			$l[]="";			return implode("\n",$l);
			return;
		}
	}
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	

	
	return @implode("\n", $l);
}

function squid_logger(){

	if(!is_file("/etc/init.d/squid-logger")){return;}
	$ARTICA_VERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");


	$l[]="";
	$l[]="[APP_ARTICALOGGER]";
	$l[]="service_name=APP_ARTICALOGGER";
	$l[]="master_version=$ARTICA_VERSION";
	$l[]="service_cmd=/etc/init.d/squid-logger";
	$l[]="service_disabled=1";
	$l[]="family=squid";
	$l[]="watchdog_features=1";
	$l[]="installed=1";

	$master_pid=squid_logger_pid();
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		squid_watchdog_events("[squid_tail]: $master_pid Not running !!!");
		if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>1){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				squid_admin_mysql(1, "Proxy events listener {stopped} [{action}={start}]", null,__FILE__,__LINE__);
				shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squid-logger start >/dev/null 2>&1 &");
			}
		}
	$l[]="running=0\ninstalled=1";
	$l[]="";
	return implode("\n",$l);

	}

	$l[]=GetMemoriesOf($master_pid);
	$l[]="";
	return implode("\n",$l);return;


}
function squid_logger_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/squid-tail.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN("squid-logger.py");

}

?>