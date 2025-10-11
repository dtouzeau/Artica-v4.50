<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SERV_NAME"]="WAN Proxy compressor";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');  
include_once(dirname(__FILE__).'/ressources/class.squid.acls.groups.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.parents.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.ports.inc');




if($argv[1]=="--build-pp"){$GLOBALS["OUTPUT"]=true;build();exit();}
if($argv[1]=="--build-squid"){$GLOBALS["OUTPUT"]=true;build();exit();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();exit();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--wizard"){$GLOBALS["OUTPUT"]=true;wizard();exit();}
if($argv[1]=="--remove"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
if($argv[1]=="--reconfigure"){$GLOBALS["OUTPUT"]=true;reconfigure();exit();}

if($argv[1]=="--uninstall-socks"){uninstall_danted();exit();}
if($argv[1]=="--install-socks"){install_socks_service();exit();}

if($argv[1]=="--danted-start"){start_danted();exit;}
if($argv[1]=="--danted-stop"){stop_danted();exit;}
if($argv[1]=="--danted-restart"){restart_danted();exit;}
if($argv[1]=="--danted-build"){build_danted();exit;}



function start_danted():bool{
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        echo "Starting......: ".date("H:i:s")." [INIT]: Already task running PID $pid since {$time}mn\n";
        return false;
    }

    $pidfile="/var/run/danted/danted.pid";
    $config_file="/etc/danted.conf";
    $SERV_NAME="Dante Socks5";
    $daemonbin="/usr/sbin/danted";

    if(!is_file($daemonbin)){
        echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME is not installed...\n";
        return false;
    }

    $pid=GET_PID_DANTED();

    if($unix->process_exists($pid)){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME already running pid $pid since {$time}mn\n";
        @file_put_contents($pidfile, $pid);
        return true;
    }

    if(!is_file($config_file)){
        echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME $config_file no such file\n";
        return false;
    }
    build_danted();
    $cmdline="$daemonbin -D -N 5 -f $config_file -p /var/run/danted/danted.pid";

    if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
    $unix->go_exec($cmdline);
    sleep(1);
    for($i=0;$i<10;$i++){
        $pid=GET_PID_DANTED();
        if($unix->process_exists($pid)) {
            echo "Starting......: " . date("H:i:s") . " [INIT]: $SERV_NAME started pid .$pid..\n";
            break;
        }
        echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME wait $i/10\n";
        sleep(1);
    }

    $pid=GET_PID_DANTED();
    if(!$unix->process_exists($pid)){
        echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME failed to start\n";
        echo "Starting......: ".date("H:i:s")." [INIT]: $cmdline\n";
        return false;
    }

    echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME success\n";
    return true;
}
function uninstall_danted(){
    $unix=new unix();
    stop_danted();
    $unix->remove_service("/etc/init.d/danted");

    $files[]="/var/run/danted/danted.pid";
    $files="/etc/danted.conf";
    foreach ($files as $file){
        if(is_file($file)){@unlink($file);}
    }

}

function start(){
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/wanproxy.pid";
	$SERV_NAME=$GLOBALS["SERV_NAME"];
	$pid=$unix->get_pid_from_file($pidfile);
	$sock=new sockets();
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting Task Already running PID $pid since {$time}mn\n";}
		return false;
	}
		
	@file_put_contents($pidfile, getmypid());
	
	$daemonbin="/usr/local/bin/wanproxy";
	if(!is_file($daemonbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME is not installed...\n";}
		return false;
	}	
	
	$pid=GET_PID();
	
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME already running pid $pid since {$time}mn\n";}
		@file_put_contents("/var/run/wanproxy.pid", $pid);
		return true;
	}	
	
	if(!is_file("/etc/wanproxy.conf")){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME /etc/wanproxy.conf no such file\n";}
		return false;
		
	}
	
	$WanProxyDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyDebug"));
	$nohup=$unix->find_program("nohup");
	@mkdir("/var/log/wanproxy",0755,true);
	$TMP="/var/log/wanproxy/wanproxy.log";
	if(is_file($TMP)){@unlink($TMP);}
    $debugcom=null;
    if($WanProxyDebug==1){$debugcom=" -v";}
	$cmdline="$nohup $daemonbin$debugcom -c /etc/wanproxy.conf >$TMP 2>&1 &";
	
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting $SERV_NAME\n";}
	shell_exec("$cmdline");
	sleep(1);
	for($i=0;$i<10;$i++){
		$pid=GET_PID();
		if($unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME started pid .$pid..\n";}break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME wait $i/10\n";}
		sleep(1);
	}	
	sleep(1);
	$pid=GET_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME failed to start\n";}
		$f=explode("\n",@file_get_contents($TMP));
		foreach ($f as $ligne){if(trim($ligne)==null){continue;}if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $ligne\n";}}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmdline\n";}
		return false;
	}else{
		@file_put_contents("/var/run/wanproxy.pid", $pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME success\n";}
		return true;
		
	}

	
}
function build(){
	$WanproxyMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxyMode"));
	if($GLOBALS["OUTPUT"]){echo "Reconfigure...: ".date("H:i:s")." [INIT]: Mode \"$WanproxyMode\"\n";}
	if($WanproxyMode=="parent"){build_parent();return;}
	if($WanproxyMode=="client"){build_client();}	
	
}

function reconfigure():bool{
	build_progress(20, "{reconfiguring}");
	$WanproxyMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxyMode"));
	
	if($GLOBALS["OUTPUT"]){echo "Reconfigure...: ".date("H:i:s")." [INIT]: Mode \"$WanproxyMode\"\n";}

    $md5=md5_file("/etc/wanproxy.conf");
	
	if($WanproxyMode=="parent"){
		if(!build_parent()){
            build_progress(110, "{failed}");
            return false;
        }
	}

	if($WanproxyMode=="client"){
        build_client();
    }

    $md52=md5_file("/etc/wanproxy.conf");
    if($md5==$md52){
        build_progress(100, "{success}");
        return true;
    }

    stop();
    if(!start()){
        build_progress(110, "{starting_service} {failed}");
        return false;
    }
	build_progress(100, "{success}");
    return true;
}

function install(){
	build_progress(50, "{installing}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WanProxyEnabled",1);
	create_init();
	build_progress(90, "{installing}");
	build_monit();
	$WanproxyMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxyMode"));
	if($WanproxyMode=="parent"){build_parent();}
	if($WanproxyMode=="client"){build_client();}
	system("/etc/init.d/wanproxy restart");
	build_progress(100, "{done}");
}

function uninstall(){
    $unix=new unix();
	build_progress(50, "{uninstalling}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WanProxyEnabled",0);
	$unix->remove_service("/etc/init.d/wanproxy");
	@unlink("/etc/wanproxy.conf");
    if(is_dir("/home/squid/wanproxy")){
        $rm=$unix->find_program("rm");
        shell_exec("$rm -rf /home/squid/wanproxy");
    }

	if(is_file("/etc/monit/conf.d/APP_WANPROXY.monitrc")){
		@unlink("/etc/monit/conf.d/APP_WANPROXY.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
	}

    if(is_file("/etc/init.d/redsocks")){
        $unix->go_exec("/usr/sbin/artica-phpfpm-service -uninstall-redsocks");
    }

    build_progress(70, "{uninstalling}");
    uninstall_danted();
	build_progress(100, "{uninstalling} {done}");
}

function build_monit(){

	$f[]="check process APP_WANPROXY with pidfile /var/run/wanproxy.pid";
	$f[]="\tstart program = \"/etc/init.d/wanproxy start --monit\"";
	$f[]="\tstop program = \"/etc/init.d/wanproxy stop --monit\"";

	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_WANPROXY.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_WANPROXY.monitrc")){
		echo "/etc/monit/conf.d/APP_WANPROXY.monitrc failed !!!\n";
	}
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
	
}





function stop_danted():bool{
    $SERV_NAME="Dante Socks5";
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        echo "Stopping......: ".date("H:i:s")." [INIT]: Already task running PID $pid since {$time}mn\n";
        return false;
    }

    $pid=GET_PID_DANTED();

    if(!$unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME already stopped...\n";
        return true;
    }

    $kill=$unix->find_program("kill");
    $time=$unix->PROCCESS_TIME_MIN($pid);
    echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping $SERV_NAME with a ttl of {$time}mn\n";

    echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping $SERV_NAME smoothly...\n";
    $cmd="$kill $pid >/dev/null";
    shell_exec($cmd);

    $pid=GET_PID_DANTED();

    if(!$unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME success...\n";
        return true;
    }


    for($i=0;$i<10;$i++){
        $pid=GET_PID_DANTED();
        if($unix->process_exists($pid)){
            echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME kill pid $pid..\n";
            unix_system_kill_force($pid);
        }else{
            break;
        }
        echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME wait $i/10\n";
        sleep(1);
    }
    $pid=GET_PID_DANTED();

    if(!$unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME success...\n";
        return true;
    }
    echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME Failed...\n";
    return false;
}

function stop(){
	$SERV_NAME=$GLOBALS["SERV_NAME"];
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		echo "Stopping......: ".date("H:i:s")." [INIT]: Already task running PID $pid since {$time}mn\n";
		return;
	}

	$pid=GET_PID();
	
	if(!$unix->process_exists($pid)){
		echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME already stopped...\n";
		return;
	}	
	
	$kill=$unix->find_program("kill");
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping $SERV_NAME with a ttl of {$time}mn\n";}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping $SERV_NAME smoothly...\n";}
	$cmd="$kill $pid >/dev/null";
	shell_exec($cmd);

	$pid=GET_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME success...\n";}
		return;
	}	
	
	
	for($i=0;$i<10;$i++){
		$pid=GET_PID();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME kill pid $pid..\n";}
			unix_system_kill_force($pid);
		}else{
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME wait $i/10\n";}
		sleep(1);
	}	
	$pid=GET_PID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME success...\n";}
		return;
	}	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: $SERV_NAME Failed...\n";}
}

function GET_PID(){
	$unix=new unix();
	$daemonbin="/usr/local/bin/wanproxy";
	return $unix->PIDOF($daemonbin);
	
}



function restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 ".__FILE__." --stop");
	shell_exec("$php5 ".__FILE__." --start");
}

function restart_danted():bool{
    if(!stop_danted()){return false;}
    if(!start_danted()){return false;}
    return true;
}


function build_progress($pourc,$text){
	$cachefile=PROGRESS_DIR."/wanproxy.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "{$pourc}% $text\n";
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}




function build_client(){
    return build_childs();
}





function build_childs(){
	$ID=2;
	$unix=new unix();
	@mkdir("/home/squid/wanproxy",0755,true);
	//$conf[]="create log-mask catch-all";
	//$conf[]="set catch-all.regex \"^/\"";
	//$conf[]="set catch-all.mask INFO";
	//$conf[]="activate catch-all";
	$conf[]="";
	

	$WanproxyParentPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxyParentPort"));
    if($WanproxyParentPort==0){$WanproxyParentPort=8088;}

	$WanProxyMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyMemory"));
	$WanProxyCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyCache"));
	$WanProxyDestAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyDestAddr"));
	$WanproxyDestPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxyDestPort"));
	
	if($WanProxyMemory==0){$WanProxyMemory=128;}
	if($WanProxyCache==0){$WanProxyCache=1;}

	if($WanproxyDestPort==0){$WanproxyDestPort=8088;}
	$WanproxyIpaddr="127.0.0.1";

    $WanProxyClientUseSock  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyClientUseSock"));
    $SQUIDEnable            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0){$WanProxyClientUseSock=1;}

    if($WanProxyClientUseSock==1){
        if(!is_file("/etc/init.d/redsocks")){
            $sh=$unix->sh_command("/usr/sbin/artica-phpfpm-service -install-redsocks");
            $unix->go_exec($sh);
        }else{
            echo "Configuring...: ".date("H:i:s")." [INIT]: Reconfiguring iptables...\n";
            system("/usr/sbin/artica-phpfpm-service -reload-redsocks");
        }
    }else{
        if(is_file("/etc/init.d/redsocks")){
            $sh=$unix->sh_command("/usr/sbin/artica-phpfpm-service -uninstall-redsocks");
            $unix->go_exec($sh);
        }

    }
	
		if($WanProxyMemory>0){
			$cacheAdd=true;
			$conf[]="# A primary in-memory cache of 128MB per peer.";
			$conf[]="# A secondary disk cache of 1GB in the file wanproxy.xcache shared by all peers.";
			$conf[]="create cache memorycache$ID";
			$conf[]="set memorycache$ID.type Memory";
			$conf[]="set memorycache$ID.size 128MB";
			$conf[]="activate memorycache$ID";
			$conf[]="";
		}
	
		if($WanProxyCache>0){
			$cacheAdd=true;
			$conf[]="create cache diskcache{$ID}";
			$conf[]="set diskcache$ID.type Disk";
			$conf[]="set diskcache$ID.size {$WanProxyCache}GB";
			$conf[]="set diskcache$ID.path \"/home/squid/wanproxy/wanproxy$ID.xcache\"";
			$conf[]="activate diskcache{$ID}";
			$conf[]="";
		}
	
		@mkdir("/home/squid/wanproxy",0755,true);
		
		if($cacheAdd){
			$conf[]="create cache cache{$ID}";
			$conf[]="set cache$ID.type Pair";
			if($WanProxyMemory>0){$conf[]="set cache$ID.primary memorycache{$ID}";}
			if($WanProxyCache>0){$conf[]="set cache$ID.secondary diskcache{$ID}";}
			$conf[]="activate cache{$ID}";
			
		}
		$conf[]="";
		$conf[]="# Set up codec instances.";
		$conf[]="create codec codec{$ID}";
		$conf[]="set codec$ID.codec XCodec";
		if($cacheAdd){
			$conf[]="set codec$ID.cache cache{$ID}";
		}
		$conf[]="set codec$ID.compressor zlib";
		$conf[]="set codec$ID.compressor_level 6";
		$conf[]="set codec$ID.track_statistics true";
		$conf[]="activate codec{$ID}";
		$conf[]="";
	
		$conf[]="create interface if{$ID}";
		$conf[]="set if$ID.family IPv4";
		$conf[]="set if$ID.host \"$WanproxyIpaddr\"";
		$conf[]="set if$ID.port \"$WanproxyParentPort\"";
		$conf[]="activate if{$ID}";
		$conf[]="";
	
	
		$conf[]="create peer peer{$ID}";
		$conf[]="set peer$ID.family IPv4";
		$conf[]="set peer$ID.host \"$WanProxyDestAddr\"";
		$conf[]="set peer$ID.port \"$WanproxyDestPort\"";
		$conf[]="activate peer{$ID}";
		$conf[]="";
	
		$conf[]="create proxy proxy{$ID}";
		//$conf[]="set proxy$ID.type TCP-TCP";
		$conf[]="set proxy$ID.interface if{$ID}";
		$conf[]="set proxy$ID.interface_codec None";
		$conf[]="set proxy$ID.peer peer{$ID}";
		$conf[]="set proxy$ID.peer_codec codec{$ID}";
		$conf[]="activate proxy{$ID}";
		$conf[]="";
	
	
	$conf[]="create interface if0";
	$conf[]="set if0.family IPv4";
	$conf[]="set if0.host \"0.0.0.0\"";
	$conf[]="set if0.port \"9900\"";
	$conf[]="activate if0";
	$conf[]="";
	$conf[]="create monitor monitor0";
	$conf[]="set monitor0.interface if0";
	$conf[]="activate monitor0";
	$conf[]="";
	echo "Configuring...: ".date("H:i:s")." [INIT]: /etc/wanproxy.conf done...\n";
	@file_put_contents("/etc/wanproxy.conf", @implode("\n", $conf));

    if($SQUIDEnable==1){
	    build_progress(40, "{reconfiguring} {APP_SQUID}");
        if(!$unix->go_exec("/usr/sbin/artica-phpfpm-service -proxy-parents")){
            system("/usr/sbin/artica-phpfpm-service -proxy-parents");
        }
    }

	return true;
	
}
function install_socks_service(){
    if(!is_file("/usr/sbin/danted")){
        echo "!!! Socks5 Service is not installed on this system !!\n";
        return false;
    }


    create_socks_init();
    $RESTART=build_danted();
    if(!start_danted()){return false;}
    if($RESTART){
        if(!restart_danted()){return false;}
    }

    return true;

}

function build_danted():bool{
    $WanproxyInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxyInterface"));
    $WanProxyParentSockIfOut=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyParentSockIfOut"));
    if($WanProxyParentSockIfOut==null){$WanProxyParentSockIfOut=$WanproxyInterface;}
    if($WanProxyParentSockIfOut==null){$WanProxyParentSockIfOut="eth0";}

    if(!is_dir("/var/run/danted")){
        @mkdir("/var/run/danted",0755,true);

    }

    if(!is_file("/var/run/danted/danted.pid")) {
        @touch("/var/run/danted/danted.pid");
    }
    chmod("/var/run/danted",0755);
    @chown("/var/run/danted","squid");
    chmod("/var/run/danted/danted.pid",0755);
    @chown("/var/run/danted/danted.pid","squid");


    $f[]="logoutput: syslog stdout stderr";
    $f[]="errorlog: syslog";
    $f[]="internal: lo port = 1080";
    $f[]="external: $WanProxyParentSockIfOut";
    $f[]="socksmethod: none";
    $f[]="clientmethod: none";
    $f[]="#socksmethod: username rfc931 none";
    $f[]="#socksmethod: pam";
    $f[]="user.privileged: root";
    $f[]="user.unprivileged: root";
    $f[]="user.libwrap: nobody";
    $f[]="#compatibility: sameport";
    $f[]="#compatibility: reuseaddr";
    $f[]="#extension: bind";
    $f[]="#timeout.negotiate: 30   # on a lan, this should be enough.";
    $f[]="#timeout.io: 0 # or perhaps 86400, for a day.";
    $f[]="#srchost: nodnsunknown nodnsmismatch";
    $f[]="client pass {";
    $f[]="        from: 0.0.0.0/0 to: 0.0.0.0/0";
    $f[]="        log: connect disconnect iooperation";
    $f[]="}";
    $f[]="pass {";
    $f[]="    from: 127.0.0.0/8 to: 0.0.0.0/0";
    $f[]="    protocol: tcp udp";
    $f[]="    log: connect disconnect iooperation";
    $f[]="}";
    $f[]="";
    $f[]="client block {";
    $f[]="        from: 0.0.0.0/0 to: 0.0.0.0/0";
    $f[]="        log: connect disconnect iooperation";
    $f[]="}";
    $f[]="";
    $f[]="#socks block {";
    $f[]="#        from: 0.0.0.0/0 to: lo0";
    $f[]="#        log: connect error";
    $f[]="#}";
    $f[]="";
    $f[]="socks pass {";
    $f[]="        from: 0.0.0.0/0 to: 0.0.0.0/0";
    $f[]="        command: bindreply udpreply";
    $f[]="        log: connect disconnect iooperation";
    $f[]="}";
    $f[]="# route all http connects via an upstream socks server";
    $f[]="#route {";
    $f[]="# from: 10.0.0.0/8 to: 0.0.0.0/0 port = http via: socks.example.net port = socks";
    $f[]="#}";
    $f[]="";
    $md5=md5_file("/etc/danted.conf");
    @file_put_contents("/etc/danted.conf",@implode("\n",$f));
    $md2=md5_file("/etc/danted.conf");
    if($md5==$md2){return false;}
    return true;
}

function GET_PID_DANTED():int{
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/danted/danted.pid");
    if($unix->process_exists($pid)){return $pid;}
    $daemonbin="/usr/sbin/danted";
    return intval($unix->PIDOF($daemonbin));
}

function create_socks_init(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $daemonbin="/usr/sbin/danted";
    $daemonbinLog=basename($daemonbin);
    $INITD_PATH="/etc/init.d/danted";
    $md51=md5_file($INITD_PATH);
    $php5script=basename(__FILE__);

    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         $daemonbinLog";
    $f[]="# Required-Start:    \$local_fs \$syslog \$network";
    $f[]="# Required-Stop:     \$local_fs \$syslog \$network";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: $daemonbinLog";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --danted-start \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --danted-stop \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reload)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --danted-reload $2 \$3";
    $f[]="    ;;";

    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --danted-restart \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";

    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    $md52=md5_file($INITD_PATH);
    if($md52==$md51){return true;}
    echo "Starting......: ".date("H:i:s")." [INIT]: building $INITD_PATH done...\n";

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }
    return true;

}


function build_parent(){
	$unix=new unix();
	$WanproxyInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxyInterface"));
	$WanproxyParentPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxyParentPort"));
	$WanproxySquidPortID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanproxySquidPortID"));
	$WanProxyMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyMemory"));
	$WanProxyCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyCache"));
	if($WanProxyMemory==0){$WanProxyMemory=128;}
	if($WanProxyCache==0){$WanProxyCache=1;}
	if($WanproxyInterface==null) {$WanproxyInterface="eth0";}
    $WanproxyListenIP = $unix->InterfaceToIPv4($WanproxyInterface);
    $WanProxyParentUseSock=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WanProxyParentUseSock"));
    $SQUIDEnable    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0){$WanProxyParentUseSock=1;}

    echo "Configuring...: " . date("H:i:s") . " [INIT]: Use SOCKS Service = $WanProxyParentUseSock\n";
    if($WanProxyParentUseSock==1){
      echo "Configuring...: " . date("H:i:s") . " [INIT]: Installing SOCKS Service\n";
      if(!install_socks_service()){
          return false;
      }
    }

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");

	@mkdir("/home/squid/wanproxy",0755,true);
	
	$conf[]="create log-mask catch-all";
	$conf[]="set catch-all.regex \"^/\"";
	$conf[]="set catch-all.mask INFO";
	$conf[]="activate catch-all";
	$conf[]="";

    //127.0.0.1:1080
    $prxy_port="127.0.0.1";
    $prxy_port=1080;

    if($WanProxyParentUseSock==0) {
        echo "Configuring...: " . date("H:i:s") . " [INIT]: Proxy port = $WanproxySquidPortID\n";
        $ligne = $q->mysqli_fetch_array("SELECT ipaddr,port FROM proxy_ports WHERE ID='$WanproxySquidPortID'");
        $prxy_port = intval($ligne["port"]);
        $rpxy_ip = $ligne["ipaddr"];
    }
	
	echo "Reconfigure...: ".date("H:i:s")." [INIT]: local Proxy listens $rpxy_ip:$prxy_port\n";
	

	if($rpxy_ip==null){$rpxy_ip="127.0.0.1";}
	if($rpxy_ip=="0.0.0.0"){$rpxy_ip="127.0.0.1";}
    echo "Configuring...: ".date("H:i:s")." [INIT]: $WanproxyInterface:$WanproxyListenIP:$WanproxyParentPort --> $rpxy_ip:$prxy_port\n";
		
	
	$ID=1;
	$wanport=0;
	$cacheAdd=false;

	
	$conf[]="# A primary in-memory cache of {$WanProxyMemory}MB per peer.";
	$conf[]="# A secondary disk cache of {$WanProxyCache}GB in the file wanproxy.xcache shared by all peers.";
	
	if($WanProxyMemory>0){
		$conf[]="create cache memorycache{$ID}";
		$conf[]="set memorycache$ID.type Memory";
		$conf[]="set memorycache$ID.size {$WanProxyMemory}MB";
		$conf[]="activate memorycache{$ID}";
		$conf[]="";
		$cacheAdd=true;
	}
	if($WanProxyCache>0){
		$conf[]="create cache diskcache{$ID}";
		$conf[]="set diskcache$ID.type Disk";
		$conf[]="set diskcache$ID.size {$WanProxyCache}GB";
		$conf[]="set diskcache$ID.path \"/home/squid/wanproxy/wanproxyParent.xcache\"";
		$conf[]="activate diskcache{$ID}";
		$conf[]="";
		$cacheAdd=true;
	}
	
	if($cacheAdd){
		$conf[]="create cache cache{$ID}";
		$conf[]="set cache$ID.type Pair";
		if($WanProxyMemory>0){$conf[]="set cache$ID.primary memorycache{$ID}";}
		if($WanProxyCache>0){$conf[]="set cache$ID.secondary diskcache{$ID}";}
		$conf[]="activate cache{$ID}";
	}
	$conf[]="";
	$conf[]="# Set up codec instances.";
	$conf[]="create codec codec{$ID}";
	$conf[]="set codec$ID.codec XCodec";
	if($cacheAdd){$conf[]="set codec$ID.cache cache{$ID}";}
	$conf[]="set codec$ID.compressor zlib";
	$conf[]="set codec$ID.compressor_level 6";
	$conf[]="set codec$ID.track_statistics true";
	$conf[]="activate codec{$ID}";
	$conf[]="";
	
	$conf[]="create interface if{$ID}";
	$conf[]="set if$ID.family IPv4";
	$conf[]="set if$ID.host \"$WanproxyListenIP\"";
	$conf[]="set if$ID.port \"$WanproxyParentPort\"";
	$conf[]="activate if{$ID}";
	$conf[]="";
	
	build_progress(35, "{reconfigure}");
	$conf[]="create peer peer{$ID}";
	$conf[]="set peer$ID.family IPv4";
    if($WanProxyParentUseSock==1) {
        $conf[] = "set peer$ID.host \"127.0.0.1\"";
        $conf[] = "set peer$ID.port \"1080\"";
    }else{
        $conf[] = "set peer$ID.host \"$rpxy_ip\"";
        $conf[] = "set peer$ID.port \"$prxy_port\"";
    }
	
	$conf[]="activate peer{$ID}";
	$conf[]="";
	
	$conf[]="create proxy proxy{$ID}";
    if($WanProxyParentUseSock==0) {
        $conf[] = "set proxy$ID.type TCP-TCP";
    }
	$conf[]="set proxy$ID.interface if{$ID}";
	$conf[]="set proxy$ID.interface_codec codec{$ID}";
	$conf[]="set proxy$ID.peer peer{$ID}";
	$conf[]="set proxy$ID.peer_codec None";
	$conf[]="activate proxy{$ID}";
	$conf[]="";
	
	
	
	$conf[]="create interface if0";
	$conf[]="set if0.family IPv4";
	$conf[]="set if0.host \"127.0.0.1\"";
	$conf[]="set if0.port \"9900\"";
	$conf[]="activate if0";
	$conf[]="";


	$conf[]="create monitor monitor0";
	$conf[]="set monitor0.interface if0";
	$conf[]="activate monitor0";
	$conf[]="";
	
	build_progress(40, "{reconfigure}");
	if($GLOBALS["OUTPUT"]){echo "Reconfigure...: ".date("H:i:s")." [INIT]: /etc/wanproxy.conf\n";}
	@file_put_contents("/etc/wanproxy.conf", @implode("\n", $conf));
	return true;
}	





function create_init(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin="/usr/local/bin/wanproxy";
	$daemonbinLog=basename($daemonbin);
	$INITD_PATH="/etc/init.d/wanproxy";

	$php5script=basename(__FILE__);
	if(!is_file($daemonbin)){
		if($GLOBALS["OUTPUT"]){echo "Reconfigure...: ".date("H:i:s")." [INIT]: $daemonbin no such file!!!\n";}
		return;
	}
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         $daemonbinLog";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network";
	$f[]="# Required-Stop:     \$local_fs \$syslog \$network";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop $2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: building $INITD_PATH done...\n";}
	
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
	
}


?>