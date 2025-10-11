<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

function privoxy_pid(){
	$pid_path="/var/run/privoxy.pid";
	if(!is_file($pid_path)){
		$binpath=$GLOBALS["CLASS_UNIX"]->find_program("privoxy");
		return $GLOBALS["CLASS_UNIX"]->PIDOF($binpath); 
	}
	$pids=explode("\n",@file_get_contents($pid_path));
	while (list ($index, $line) = each ($pids)){
		$line=str_replace("\r", "", $line);
		$line=str_replace("\n", "", $line);
		if(!is_numeric(trim($line))){continue;}
		if($GLOBALS["VERBOSE"]){echo "$pid_path = $line\n";}
		if($GLOBALS["CLASS_UNIX"]->process_exists($line)){
			$PPID=$GLOBALS["CLASS_UNIX"]->PPID_OF($line);
			if($GLOBALS["VERBOSE"]){echo "$line ->running PPID:$PPID\n";}
			return $PPID;
		}
	}
	
}
function privoxy_version($bin){
	if(isset($GLOBALS["privoxy_version"])){return $GLOBALS["privoxy_version"];}
	exec("$bin --version 2>&1",$results);
	foreach ($results as $num=>$line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^Privoxy version\s+([0-9a-z\.]+)#",$line,$re)){continue;}
		$GLOBALS["privoxy_version"]=$re[1];
	}

	return $GLOBALS["privoxy_version"];

}

function privoxy(){
    $prfix="{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix";
    $enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PrivoxyEnabled"));

	if(!is_file("/etc/init.d/privoxy")){
	    if($enabled==1){
            squid_admin_mysql(0,"Install privoxy service [service was enabled]",null,__FILE__,__LINE__);
            $cmd=trim("$prfix/exec.privoxy.php --install >/dev/null 2>&1 &");
            shell_exec2($cmd);
            return null;
        }
	    return null;
	}

	$l[]="[APP_PRIVOXY]";
	$l[]="service_name=APP_PRIVOXY";
	
	$PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_PRIVOXY_VERSION");

	$pid_path="/var/run/privoxy.pid";

	if($enabled==0){
	    squid_admin_mysql(0,"Uninstall privoxy service [service was disabled]",null,__FILE__,__LINE__);
        $cmd=trim("$prfix/exec.privoxy.php --remove >/dev/null 2>&1 &");
        shell_exec2($cmd);
        return null;
    }
	
	$l[]="master_version=$PrivoxyVersion";
	$l[]="service_cmd=/etc/init.d/privoxy";
	$l[]="service_disabled=$enabled";
	$l[]="pid_path=$pid_path";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	
	if(!is_file("/etc/init.d/privoxy")){
		$l[]="";
		return implode("\n",$l);
	}
	$PPID=privoxy_pid();
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($PPID)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "{APP_PRIVOXY} service {stopped} [{action}={start}]", null,__FILE__,__LINE__);
			$cmd=trim("$prfix/exec.privoxy.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);

	}
	
	
	$l[]=GetMemoriesOf($PPID);
	$l[]="";
	

	return implode("\n",$l);return;
}

