#!/usr/bin/php
<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";exit();}}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["SLEEP"]=0;
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["MONIT"]=false;
$GLOBALS["PAUSE"]=false;
$GLOBALS["TINYPAUSE"]=false;
$GLOBALS["SERVICE_NAME"]="Artica Web console";

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--monit#",implode(" ",$argv),$re)){$GLOBALS["MONIT"]=true;}
if(preg_match("#--pause#",implode(" ",$argv),$re)){$GLOBALS["PAUSE"]=true;}
if(preg_match("#--tinypause#",implode(" ",$argv),$re)){$GLOBALS["TINYPAUSE"]=true;}
if(preg_match("#--sleep=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SLEEP"]=$re[1];}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.apache.certificate.php');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.webconsole.params.inc');

	$GLOBALS["ARGVS"]=implode(" ",$argv);
	init_syslog("script started with {$GLOBALS["ARGVS"]}");
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
	if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;nginx_reload();exit();}
	if($argv[1]=="--status"){$GLOBALS["OUTPUT"]=true;exit();}
	if($argv[1]=="--phpmyadmin"){$GLOBALS["OUTPUT"]=true;PHP_MYADMIN();exit();}
	if($argv[1]=="--error500"){$GLOBALS["OUTPUT"]=true;islighttpd_error_500();exit();}
	if($argv[1]=="--phpcgi"){$GLOBALS["OUTPUT"]=true;phpcgi();exit();}
	if($argv[1]=="--apache-build"){$GLOBALS["OUTPUT"]=true;apache_config();exit();}
	if($argv[1]=="--php-snmp"){$GLOBALS["OUTPUT"]=true;php_snmp();exit();}
	if($argv[1]=="--nginx-reload"){$GLOBALS["OUTPUT"]=true;nginx_reload();exit();}
    if($argv[1]=="--nginx-monit"){exit();}

	if($argv[1]=="--fpm-start"){nginx_fpm_start();exit();}
	if($argv[1]=="--fpm-stop"){$GLOBALS["OUTPUT"]=true;nginx_fpm_stop();exit();}
	if($argv[1]=="--fpm-reload"){$GLOBALS["OUTPUT"]=true;nginx_fpm_reload();exit();}
    if($argv[1]=="--system-reload"){$GLOBALS["OUTPUT"]=true;nginx_reload();exit();}




function init_syslog($text){
	if(!function_exists("openlog")){return;}
	openlog("WebConsole", LOG_PID , LOG_SYSLOG);
	if(!function_exists("syslog")){ return;}
	syslog(true, $text);
	if(function_exists("closelog")){closelog();}
}


function restart($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}

	if($GLOBALS["TINYPAUSE"]){usleep(1000);}
	if($GLOBALS["PAUSE"]){sleep(5);}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("need_to_reboot_webconsole", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("STOP_APP_NGINX_CONSOLE_WARN",0);
    @file_put_contents($pidfile, getmypid());
	stop();
    stop();
    stop();
	start();


}



function nginx_reload():bool{
    shell_exec("/usr/sbin/artica-phpfpm-service -reload-webconsole");
    return true;
}
function patchHardDrives(){
	$unix=new unix();


	$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();

	@mkdir("/var/run/lighttpd",0755,true);
	@mkdir("/var/log/lighttpd",0755,true);

	@chown("/var/run/lighttpd",$APACHE_SRC_ACCOUNT);
	@chown("/var/log/lighttpd",$APACHE_SRC_ACCOUNT);
	@chgrp("/var/run/lighttpd", $APACHE_SRC_GROUP);
	@chgrp("/var/log/lighttpd", $APACHE_SRC_GROUP);


	exec("/sbin/blkid 2>&1",$results);

	foreach ($results as $num=>$val){
		$val=trim($val);
		if(preg_match("#^(.+?):.*?UUID=\"(.+?)\"\s+#", $val,$re)){
			$UUIDS[$re[2]]=$re[1];
		}

	}


	$f=explode("\n",@file_get_contents("/etc/fstab"));
	foreach ( $f as $num=>$val ){
		if(preg_match("#(.+?)\s+(.+?)\s+ext4\s+(.+?)\s+([0-9]+)\s+([0-9]+)#", $val,$re)){

			$dev=$re[1];

			if(preg_match("#UUID=(.+)#", $dev,$rz)){
				if(!isset($UUIDS[$rz[1]])){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} EXT4 $dev ( Unknown )\n";}
					continue;}
				$dev=$UUIDS[$rz[1]];
			}


			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} EXT4 $dev changed to dir_index,journal_data_writeback\n";}
			shell_exec("/sbin/tune2fs -o journal_data_writeback $dev");
			shell_exec("/sbin/tune2fs -O dir_index $dev");
		}
	}

}


function apache_kill_ipcs(){
	$GLOBALS["CLASS_UNIX"]=new unix();
	$ipcs=$GLOBALS["CLASS_UNIX"]->find_program("ipcs");
	$ipcrm=$GLOBALS["CLASS_UNIX"]->find_program("ipcrm");
	$APACHE_SRC_ACCOUNT=$GLOBALS["CLASS_UNIX"]->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$GLOBALS["CLASS_UNIX"]->APACHE_SRC_GROUP();
	$ipcsT=array();


if(!is_file($ipcs)){
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ipcs, no such binary !!!\n";}
	return;
}
$cmd="$ipcs -s 2>&1";
if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ipcs on $APACHE_SRC_ACCOUNT\n";}
exec("$cmd",$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#[a-z0-9]+\s+([0-9]+)\s+$APACHE_SRC_ACCOUNT#", $ligne,$re)){$ipcsT[$re[1]]=true;}
	}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} kill ". count($ipcsT)." semaphores created by $APACHE_SRC_ACCOUNT...\n";}

	foreach ($ipcsT as $id=>$none){
		shell_exec("$ipcrm sem $id");
	}
}


function fuser_port($port){
	$unix=new unix();
	$PIDS=$unix->PIDOF_BY_PORT($port);
	if(count($PIDS)==0){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} 0 PID listens $port...\n";}

		return;}
	foreach ($PIDS as $pid=>$b){
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} killing PID $pid that listens $port\n";}
			unix_system_kill_force($pid);
		}
	}
}

function nginx_stop($aspid=false){

	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	nginx_fpm_stop();
	$GLOBALS["SERVICE_NAME"]="Artica Web console (Main)";
	$pid=nginx_pid();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} already stopped...\n";}
		return;
	}

	$pid=nginx_pid();
	if($GLOBALS["MONIT"]){
		@file_put_contents("/var/run/artica-webconsole.pid",$pid);
		return true;
	}


    system("/usr/local/ArticaWebConsole/sbin/artica-webconsole -c /etc/artica-postfix/webconsole.conf -s stop");


	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
	for($i=0;$i<5;$i++){
		$pid=nginx_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
        system("/usr/local/ArticaWebConsole/sbin/artica-webconsole -c /etc/artica-postfix/webconsole.conf -s stop");
		sleep(1);
	}

	$pid=nginx_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return true;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=nginx_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
        unix_system_kill_force($pid);
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}else{
		echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";
	}
}

function stop(){
	nginx_stop();
}






function ToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}

function remove_service($INITD_PATH){
    if(!is_file($INITD_PATH)){return;}
    system("$INITD_PATH stop");

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");

    }

    if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}



function nginx_config_version():string{


    exec("/usr/local/ArticaWebConsole/sbin/artica-webconsole -v 2>&1",$results);
    foreach ($results as $line){
        if(preg_match("#version:.*?\/([0-9\.]+)#",$line,$re)){
            return $re[1];
        }
    }
    return "0.0.0";
}











function nginx_fpm_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/artica-phpfpm.pid");
	if(!$unix->process_exists($pid)){ return $unix->PIDOF("/usr/local/ArticaWebConsole/sbin/artica-phpfpm");}
	return $pid;
}


function build_progress_fpm_reload($text,$pourc){
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Nginx[".__LINE__."](".basename(__FILE__).")[{$pourc}%] $text\n";}
    $cachefile=PROGRESS_DIR."/fpm.reload.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);

}

function nginx_fpm_reload():bool{
    system("/usr/sbin/artica-phpfpm-service -reload-artica-php -debug");
    return true;
}

function nginx_fpm_stop():bool{
    $binfile=CopyForPhp();
    system("$binfile -stop-artica-php -debug");
    return true;
}
function nginx_fpm_start():bool{
    $unix=new unix();
    $pids=$unix->PIDOF_PATTERN_ALL("articarest -start-artica-php");
    if(count($pids)>0){
        foreach ($pids as $pid){
            $TimExec=$unix->PROCCESS_TIME_MIN($pid);
            echo "Already Running PID $pid since $TimExec mn\n";
            if($TimExec>5){
                echo "Killing $pid\n";
                $unix->KILL_PROCESS($pid,9);
            }
        }

    }
    if(!is_file("/usr/sbin/artica-phpfpm-service")){
        @copy("/usr/share/artica-postfix/bin/articarest","/usr/sbin/artica-phpfpm-service");
        chmod("/usr/sbin/artica-phpfpm-service",0755);
    }else{
        $md51=md5_file("/usr/share/artica-postfix/bin/articarest");
        $md52=md5_file("/usr/sbin/artica-phpfpm-service");
        if($md51<>$md52){
            @unlink("/usr/sbin/artica-phpfpm-service");
            @copy("/usr/share/artica-postfix/bin/articarest","/usr/sbin/artica-phpfpm-service");
            chmod("/usr/sbin/artica-phpfpm-service",0755);
        }
    }


   	system("/usr/sbin/artica-phpfpm-service -start-artica-php -debug");
	return true;
}
function CopyForPhp():string{
    $Dst="/usr/share/artica-postfix/bin/artica-php";
    if(is_file($Dst)){
        @unlink($Dst);
    }
    @copy("/usr/share/artica-postfix/bin/articarest",$Dst);
    @chmod($Dst,0755);
    return $Dst;
}

function nginx_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/artica-webconsole.pid");
	if(!$unix->process_exists($pid)){ return $unix->PIDOF("/usr/local/ArticaWebConsole/sbin/artica-webconsole");}
	return $pid;

}

function nginx_syslog($text){
	if(!function_exists("openlog")){return;}
	openlog("Artica-WebConsole", LOG_PID , LOG_SYSLOG);
	if(!function_exists("syslog")){ return;}
	syslog(true, $text);
	if(function_exists("closelog")){closelog();}

}


function nginx_start():bool{
    system("/usr/sbin/artica-phpfpm-service -start-webconsole -debug");
    return true;
}

function start():bool{
	$unix=new unix();
	if(is_file("/etc/artica-postfix/FROM_ISO")){
		if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){return false;}
	}

	return nginx_start();

}
//##############################################################################
function apache_troubleshoot(){}
function debian_version(){
	if(!is_file("/etc/debian_version")){return;}
	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	$Major=$re[1];
	if(!is_numeric($Major)){return;}

	return $Major;

}





function apache_config(){}




function apache_htpassword($clearTextPassword){
	$password = crypt($clearTextPassword, base64_encode($clearTextPassword));
	return $password;
}


function apache_webdav(){}
function apache_firewall(){}
function buildConfig(){}
function PHP_MYADMIN(){
	$sock=new sockets();
	$phpmyadminAllowNoPassword=$sock->GET_INFO("phpmyadminAllowNoPassword");
	if(!is_numeric($phpmyadminAllowNoPassword)){$phpmyadminAllowNoPassword=0;}
	if(!is_file('/usr/share/phpmyadmin/index.php')){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PhpMyAdmin: /usr/share/phpmyadmin/index.php no such file\n";}
		return;
	}
	include_once(dirname(__FILE__)."/ressources/class.mysql.inc");

	if(is_file('/etc/artica-postfix/phpmyadmin_config.txt')){$phpmyadmin_config_add=@file_get_contents('/etc/artica-postfix/phpmyadmin_config.txt');}
	@mkdir("/usr/share/phpmyadmin/config",0755,true);


	$database_password=@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password");
	if($database_password=='!nil'){$database_password=null;}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PhpMyAdmin: AllowNoPassword=$phpmyadminAllowNoPassword\n";}
	//$phpmyadminAllowNoPassword

	$q=new mysql();
	$f[]='<?php';
	$f[]='/* Servers configuration */';
	$f[]='$i = 0;';
	$f[]='';
	$f[]='/* Server: Artica Mysql [1] */';
	$f[]='$i++;';
	$f[]='$cfg["Servers"][$i]["verbose"] = "Artica Mysql";';
	$f[]='$cfg["Servers"][$i]["host"] = "'.$q->mysql_server.'";';
	$f[]='$cfg["Servers"][$i]["port"] = '.$q->mysql_port.';';
	$f[]='$cfg["Servers"][$i]["socket"] = "'.$q->SocketName.'";';
	$f[]='$cfg["Servers"][$i]["connect_type"] = "tcp";';
	$f[]='$cfg["Servers"][$i]["extension"] = "mysql";';
	$f[]='$cfg["Servers"][$i]["auth_type"] = "cookie";';
	$f[]='$cfg["Servers"][$i]["user"] = "'.$q->mysql_admin.'";';
	$f[]='$cfg["Servers"][$i]["password"] = "'.$q->mysql_password.'";';
	if($phpmyadminAllowNoPassword==1){  $f[]='$cfg["Servers"][$i]["AllowNoPassword"] = True;';}
	if(is_file('/opt/squidsql/bin/mysqld')){
		$f[]='$i++;';
		$f[]='$cfg["Servers"][$i]["verbose"] = "Squid Mysql";';
		$f[]='$cfg["Servers"][$i]["socket"] = "/var/run/mysqld/squid-db.sock";';
		$f[]='$cfg["Servers"][$i]["connect_type"] = "socket";';
		$f[]='$cfg["Servers"][$i]["extension"] = "mysql";';
		$f[]='$cfg["Servers"][$i]["auth_type"] = "cookie";';
		$f[]='$cfg["Servers"][$i]["user"] = "root";';
		$f[]='$cfg["Servers"][$i]["password"] = "";';
		$f[]='$cfg["Servers"][$i]["AllowNoPassword"] = True;';
	}



	$f[]='';
	if($phpmyadmin_config_add<>null){$f[]=$phpmyadmin_config_add;}
	$f[]='/* End of servers configuration */';
	$f[]='';
	$f[]='$cfg["blowfish_secret"] = "4bf112360c9db0.66618545";';
	$f[]='$cfg["DefaultLang"] = "en-utf-8";';
	$f[]='$cfg["ServerDefault"] = 1;';
	$f[]='$cfg["UploadDir"] = "";';
	$f[]='$cfg["SaveDir"] = "";';
	$f[]='?>';
	@file_put_contents('/usr/share/phpmyadmin/config.inc.php',@implode("\n", $f));

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PhpMyAdmin: Success writing phpmyadmin configuration\n";}
	IF(is_dir('/usr/share/phpmyadmin/setup')){shell_exec('/bin/rm -rf /usr/share/phpmyadmin/setup');}
	IF(is_dir('/usr/share/phpmyadmin/config')){shell_exec('/bin/rm -rf /usr/share/phpmyadmin/config');}

}
function islighttpd_error_500(){
	$sock=new sockets();
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}

	@file_put_contents($pidfile, getmypid());

	$curl=$unix->find_program("curl");
	if(!is_file($curl)){return;}
	$LighttpdArticaListenIP=$sock->GET_INFO('LighttpdArticaListenIP');
	$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
	$ArticaHttpUseSSL=$sock->GET_INFO("ArticaHttpUseSSL");
	if(!is_numeric($ArticaHttpUseSSL)){$ArticaHttpUseSSL=1;}
	if(!is_numeric($ArticaHttpsPort)){$ArticaHttpsPort="9000";}
	$EnableArticaFrontEndToNGninx=$sock->GET_INFO("EnableArticaFrontEndToNGninx");
	if(!is_numeric($EnableArticaFrontEndToNGninx)){$EnableArticaFrontEndToNGninx=0;}
	$proto="http";
	if($ArticaHttpUseSSL==1){$proto="https";}

	if($LighttpdArticaListenIP<>null){
		$IPS=$unix->NETWORK_ALL_INTERFACES(true);
		if(!isset($IPS[$LighttpdArticaListenIP])){$LighttpdArticaListenIP=null;}
	}


	if(strlen($LighttpdArticaListenIP)>3){
		$ips[$LighttpdArticaListenIP]=true;
		$uri="$proto://$LighttpdArticaListenIP:$ArticaHttpsPort/logon.php";
	}else{
		$ips=$unix->NETWORK_ALL_INTERFACES(true);
		unset($ips["127.0.0.1"]);
	}

	foreach ($ips as $ipaddr=>$line){
		$f=array();
		$results=array();
		$uri="$proto://$ipaddr:$ArticaHttpsPort/logon.php";
		$f[]="$curl -I --connect-timeout 5";
		$f[]="--insecure";
		$f[]="--interface $ipaddr";
		$f[]="--url $uri 2>&1";
		$cmdline=@implode(" ", $f);
		if($GLOBALS['VERBOSE']){echo "$cmdline\n";}
		exec(@implode(" ", $f),$results);
		if($GLOBALS['VERBOSE']){echo count($results)." rows\n";}

		if(DetectError($results,"Artica Web Interface")){if($EnableArticaFrontEndToNGninx==1){shell_exec("/etc/init.d/nginx restart");}else{restart(true);}}


	}

	$results=array();
	if($GLOBALS['VERBOSE']){echo "done\n";}

}
function FrmToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}
function DetectError($results,$type){
	foreach ($results as $a=>$b){
		if($GLOBALS["VERBOSE"]){echo "$a \"$b\"\n";}
		if(preg_match("#HTTP.+?200 OK#", $b)){
			if($GLOBALS['VERBOSE']){echo "$type: 200 OK Nothing to do...\n";}
			return false;
		}

		IF(preg_match("#HTTP.*?502 Bad Gateway#", $b)){
			$GLOBALS["DetectError"]="$b";
			if($GLOBALS['VERBOSE']){echo "$b detected\n";}
			squid_admin_mysql(2, "$type: $b detected ",__FUNCTION__,__FILE__,__LINE__);
			return true;
		}

		IF(preg_match("#HTTP.*?500.*?Error#", $b)){
			$GLOBALS["DetectError"]="$b";
			if($GLOBALS['VERBOSE']){echo "$b detected\n";}
			squid_admin_mysql(2, "$type: $b detected",__FUNCTION__,__FILE__,__LINE__);
			return true;
		}

		IF(preg_match("#HTTP.*?500.*?Internal#", $b)){
			$GLOBALS["DetectError"]="$b";
			if($GLOBALS['VERBOSE']){echo "$b detected\n";}
			squid_admin_mysql(2, "$type: $b detected",__FUNCTION__,__FILE__,__LINE__);
			return true;
		}

		IF(preg_match("#HTTP.*?503.*?Service Not Available#i", $b)){
			$GLOBALS["DetectError"]="$b";
			if($GLOBALS['VERBOSE']){echo "$b detected\n";}
			squid_admin_mysql(2, "$type: $b detected",__FUNCTION__,__FILE__,__LINE__);
			return true;
		}


	}


}

function phpcgi(){
	$unix=new unix();
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();

	$pids=$unix->PIDOF_PATTERN_ALL($phpcgi);
	if(count($pids)==0){return;}
	$c=0;
	foreach ($pids as $pid=>$ligne){
		$time=$unix->PROCESS_TTL($pid);

		if($time>1640){
			$c++;
			$unix->KILL_PROCESS($pid,9);
		}
	}
}
function php_snmp_progress($text,$pourc){
	if(!$GLOBALS["PROGRESS"]){return;}
	echo $text."\n";
	$cachefile=PROGRESS_DIR."/php-snmp.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);
}
function php_snmp(){
	$unix=new unix();
	php_snmp_progress("{checking_php_snmp}",20);
	$unix->DEBIAN_INSTALL_PACKAGE("php5-snmp");
	$nohup=$unix->find_program("nohup");

	if(is_file("/usr/lib/php5/20090626/snmp.so")){
		php_snmp_progress("{checking_php_snmp} {success}",100);
		sleep(5);
		system("$nohup /etc/init.d/artica-webconsole restart >/dev/null 2>&1 &");
		system("$nohup /etc/init.d/artica-status restart >/dev/null 2>&1 &");
	}


	if(is_file("/usr/lib/php5/20100525/snmp.so")){
		php_snmp_progress("{checking_php_snmp} {success}",100);
		sleep(5);
		system("$nohup /etc/init.d/artica-webconsole restart >/dev/null 2>&1 &");
		system("$nohup /etc/init.d/artica-status restart >/dev/null 2>&1 &");
	}

	php_snmp_progress("{checking_php_snmp} {failed}",110);
}


