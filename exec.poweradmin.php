<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";exit();}}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["MONIT"]=false;
$GLOBALS["PAUSE"]=false;
$GLOBALS["SERVICE_NAME"]="Artica Web service";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--monit#",implode(" ",$argv),$re)){$GLOBALS["MONIT"]=true;}
if(preg_match("#--pause#",implode(" ",$argv),$re)){$GLOBALS["PAUSE"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.apache.certificate.php');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');

	$GLOBALS["ARGVS"]=implode(" ",$argv);
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;apache_stop();exit();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;apache_start();exit();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
	if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
	if($argv[1]=="--status"){$GLOBALS["OUTPUT"]=true;status();exit();}
	if($argv[1]=="--apache-build"){$GLOBALS["OUTPUT"]=true;apache_config();exit();}



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
	
	if($GLOBALS["PAUSE"]){sleep(5);}
	
	@file_put_contents($pidfile, getmypid());
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Stopping service\n";}
	apache_stop(true);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Reconfiguring service\n";}
	apache_config();
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Starting service\n";}
	apache_start(true);	
}	

function reload(){
	$unix=new unix();
	$sock=new sockets();
	$apache2ctl=$unix->LOCATE_APACHE_CTL();
	apache_config();
	$cmd="$apache2ctl -f /etc/poweradmin/httpd.conf -k restart";
	shell_exec($cmd);
	
	
}


function apache_stop(){
	$GLOBALS["SERVICE_NAME"]="Artica Apache service";
	$unix=new unix();
	$pid=apache_pid();
	$sock=new sockets();
	$ArticaHttpsPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerAdminListenPort"));
	if($ArticaHttpsPort==0){$ArticaHttpsPort=9393;}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} already stopped...\n";}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} testing $ArticaHttpsPort port...\n";}
		fuser_port($ArticaHttpsPort);
		apache_kill_ipcs();
		return;
	}
	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	$apache2ctl=$unix->LOCATE_APACHE_CTL();


	ToSyslog("ARTICA_STOP:: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
	shell_exec("$apache2ctl -f /etc/poweradmin/httpd.conf -k stop");
	for($i=0;$i<5;$i++){
		$pid=apache_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=apache_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=apache_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	

	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} testing $ArticaHttpsPort port...\n";}
	fuser_port($ArticaHttpsPort);
	apache_kill_ipcs();

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
	}
}


function patchHardDrives(){
	$unix=new unix();
	
	$unix->CreateUnixUser("poweradmin","poweradmin","PowerAdmin HTTP user");
	$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
	
	@mkdir("/var/run/poweradmin",0755,true);
	@mkdir("/var/log/poweradmin",0755,true);
	@mkdir("/etc/poweradmin",0755,true);
	
	@mkdir("/usr/share/poweradmin/sessions",0755,true);
	
	@chown("/var/run/poweradmin","poweradmin");
	@chown("/var/log/poweradmin","poweradmin");
	@chown("/etc/poweradmin","poweradmin");
	@chgrp("/var/run/poweradmin", "poweradmin");
	@chgrp("/var/log/poweradmin", "poweradmin");
	@chown("/etc/poweradmin","poweradmin");
	
	@chgrp("/usr/share/poweradmin/sessions", "poweradmin");
	@chown("/usr/share/poweradmin/sessions","poweradmin");
	
}


function apache_kill_ipcs(){
	$GLOBALS["CLASS_UNIX"]=new unix();
	$ipcs=$GLOBALS["CLASS_UNIX"]->find_program("ipcs");
	$ipcrm=$GLOBALS["CLASS_UNIX"]->find_program("ipcrm");
	$ipcsT=array();
	
	
if(!is_file($ipcs)){
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ipcs, no such binary !!!\n";}
	return;
}
$cmd="$ipcs -s 2>&1";
if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ipcs on poweradmin\n";}
exec("$cmd",$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#[a-z0-9]+\s+([0-9]+)\s+poweradmin#", $ligne,$re)){$ipcsT[$re[1]]=true;}
	}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} kill ". count($ipcsT)." semaphores created by $APACHE_SRC_ACCOUNT...\n";}
	
	while (list ($id, $ligne) = each ($ipcsT) ){
		shell_exec("$ipcrm sem $id");
	}
}


function fuser_port($port){
	$unix=new unix();
	$kill=$unix->find_program("kill");
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
	


function killallphpcgi(){
	
	$unix=new unix();
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
	$kill=$unix->find_program("kill");
	$array=$unix->PIDOF_PATTERN_ALL($phpcgi);
	$userp=LIGHTTPD_GET_USER();
	
	if(preg_match("#^(.+?):#", $userp,$re)){$user=strtolower(trim($re[1]));}
	
	if(count($array)==0){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} No ghost processes...\n";}
		return;
	}
	$c=0;
	foreach ($array as $pid=>$line){
		$username=trim(strtolower($unix->PROCESS_GET_USER($pid)));
		if($username==null){continue;}
		if($username<>$user){continue;}
		$c++;
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Stopping ghots processes $pid\n";}
		unix_system_kill_force($pid);
	}
	
	if($c==0){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} No ghost processes...\n";}
	}
	
}

function status(){
	$unix=new unix();
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=$unix->get_pid_from_file($pidfile);
	$nohup=$unix->find_program("nohup");
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	if(!$GLOBALS["VERBOSE"]){
		$timeExec=$unix->file_time_min($pidtime);
		if($timeExec<15){return;}
	}
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	@file_put_contents($pidfile, getmypid());	
	
	$pid=LIGHTTPD_PID();
	$unix=new unix();
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} running $pid since {$timepid}Mn...\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} stopped...\n";}
		start();
		return;
	}
	$MAIN_PID=$pid;
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
	$kill=$unix->find_program("kill");
	$array=$unix->PIDOF_PATTERN_ALL($phpcgi);
	if(count($array)==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} no php-cgi processes...\n";}
		ToSyslog("{$GLOBALS["SERVICE_NAME"]} no php-cgi processes restarting PHP-FPM");
		shell_exec("$nohup /etc/init.d/php5-fpm restart >/dev/null 2>&1 &");
		return;
	}
	foreach ($array as $pid=>$line){
		$username=$unix->PROCESS_GET_USER($pid);
		if($username==null){continue;}
		if($username<>"root"){continue;}
		$time=$unix->PROCCESS_TIME_MIN($pid);
		$arrayPIDS[$pid]=$time;
		$ppid=$unix->PPID_OF($pid);
		if($time>20){
			if($ppid<>$MAIN_PID){
				if($GLOBALS["VERBOSE"]){echo "killing $pid {$time}mn ppid:$ppid/$MAIN_PID\n";}
				unix_system_kill_force($pid);
			}
		}
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ".count($arrayPIDS)." php-cgi processes...\n";}
	
}

function ToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}



function isModule($modulename){
	$LOAD_MODULES=LOAD_MODULES();
	$modulename=trim(strtolower($modulename));
	if(isset($LOAD_MODULES[$modulename])){return true;}
	$libdir=LIGHTTPD_MODULES_PATH();
	if(is_file("$libdir/$modulename.so")){return true;}
	return false;
}
//##############################################################################   
function LOAD_MODULES(){
	
	if(isset($GLOBALS["LIGHTTPDMODS"])){return $GLOBALS["LIGHTTPDMODS"];}
	$unix=new unix();
	$lighttpd=$unix->find_program("lighttpd");
	if(!is_file($lighttpd)){return;}
	exec("$lighttpd -V 2>&1",$results);
	foreach ($results as $pid=>$line){
		if(preg_match('#\+\s+(.+?)\s+support#',$line,$re)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Available module.....: \"{$re[1]}\"\n";}
			$re[1]=trim(strtolower($re[1]));
			$GLOBALS["LIGHTTPDMODS"][$re[1]]=true;
			continue;
		}
			
	}
}	


function apache_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file('/var/run/poweradmin/apache.pid');
	if($unix->process_exists($pid)){return $pid;}
	$apache2ctl=$unix->LOCATE_APACHE_CTL();
	return $unix->PIDOF_PATTERN($apache2ctl." -f /etc/poweradmin/httpd.conf");
}


function apache_start(){
	$unix=new unix();
	$GLOBALS["SERVICE_NAME"]="PowerAdmin Apache service";
	$apachebin=$unix->LOCATE_APACHE_BIN_PATH();
	
	
	
	$pid=apache_pid();
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} already started $pid since {$timepid}Mn...\n";}
		return;
	}
	
	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$rm=$unix->find_program("rm");
	$apache2ctl=$unix->LOCATE_APACHE_CTL();
	patchHardDrives();
	apache_config();
	
	$ArticaHttpsPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerAdminListenPort"));
	if($ArticaHttpsPort==0){$ArticaHttpsPort=9393;}
	
	$unix->KILL_PROCESSES_BY_PORT($ArticaHttpsPort);
	
	$cmd="$apache2ctl -f /etc/poweradmin/httpd.conf -k start";
	shell_exec($cmd);
	
	
	
	
	for($i=0;$i<6;$i++){
		$pid=apache_pid();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting $i/6...\n";}
		sleep(1);
	}
	
	
	$pid=apache_pid();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Success service started pid:$pid...\n";}
			
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmd\n";}
		apache_troubleshoot();
		
	}	

}

function apache_troubleshoot(){
	
	$f=explode("\n",@file_get_contents("/var/log/poweradmin/apache-error.log"));
	
	foreach ( $f as $index=>$line ){
		
		if(preg_match("#SSL Library Error#", $line)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} SSL certificate error, remove all certificates\n";}
			@unlink("/etc/ssl/certs/apache/server.crt");
			@unlink("/etc/ssl/certs/apache/server.key");
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} you should restart the service now...\n";}
			return;
		}
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $line...\n";}
	}
	
	
}





function apache_config(){
	$sock=new sockets();
	$unix=new unix();
	$APACHE_SRC_ACCOUNT="poweradmin";
	$APACHE_SRC_GROUP="poweradmin";
	$APACHE_MODULES_PATH=$unix->APACHE_MODULES_PATH();
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	$ArticaHttpsPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerAdminListenPort"));
	$ListenIP=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerAdminListenAddr");
	if($ArticaHttpsPort==0){$ArticaHttpsPort=9393;}
	$php5SessionGCMaxlifeTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("php5SessionGCMaxlifeTime"));
	
	$php=$unix->LOCATE_PHP5_BIN();
	if($php5SessionGCMaxlifeTime==0){$php5SessionGCMaxlifeTime=3600;}
	$PowerAdminCertificateName=trim($sock->GET_INFO("PowerAdminCertificateName"));
	$apache_LOCATE_MIME_TYPES=$unix->apache_LOCATE_MIME_TYPES();
	

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Run as $APACHE_SRC_ACCOUNT:$APACHE_SRC_GROUP\n";}
	$f[]="LockFile /var/run/poweradmin/accept.lock";
	$f[]="PidFile /var/run/poweradmin/apache.pid";
	$f[]="DocumentRoot /usr/share/poweradmin";
	
	
	$open_basedir[]="/usr/share/poweradmin";
	$open_basedir[]="/var/log";
	$open_basedir[]="/var/run/mysqld";
	$open_basedir[]="/usr/share/php";
	$open_basedir[]="/usr/share/php5";
	$open_basedir[]="/var/lib/php5";
	$open_basedir[]="/var/run";


	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Listen Port: $ArticaHttpsPort\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Listen IP: $ListenIP\n";}
	

	if($ListenIP<>null){
		$unix=new unix();
		$IPS=$unix->NETWORK_ALL_INTERFACES(true);
		if(!isset($IPS[$ListenIP])){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ERROR! Listen IP: $ListenIP -> FALSE !!\n";}
			$ListenIP=null;
		}
	}
	
	
	if($ListenIP==null){$ListenIP="*";}
	
	
	if($ListenIP<>null){
		$ArticaHttpsPort="$ListenIP:$ArticaHttpsPort";
	}
	
	$f[]="Listen $ArticaHttpsPort";

	
	
	$MaxClients=20;
	$MinSpareServers=2;
	$MaxSpareServers=3;
	
	
	if($SquidPerformance>2){
		$MinSpareServers=1;
		$MaxSpareServers=2;
		$MaxClients=10;
	}
	
		
	$f[]="<IfModule mpm_prefork_module>";
	$f[]="\tStartServers 1";
	$f[]="\tMinSpareServers $MinSpareServers";
	$f[]="\tMaxSpareServers $MaxSpareServers";
	$f[]="\tMaxClients $MaxClients";
	$f[]="\tServerLimit $MaxClients";
	$f[]="\tMaxRequestsPerChild 100";
	$f[]="</IfModule>";
	$f[]="<IfModule mpm_worker_module>";
	$f[]="\tMinSpareThreads      25";
	$f[]="\tMaxSpareThreads      75 ";
	$f[]="\tThreadLimit          64";
	$f[]="\tThreadsPerChild      25";
	$f[]="</IfModule>";
	$f[]="<IfModule mpm_event_module>";
	$f[]="\tMinSpareThreads      25";
	$f[]="\tMaxSpareThreads      75 ";
	$f[]="\tThreadLimit          64";
	$f[]="\tThreadsPerChild      25";
	$f[]="</IfModule>";
	$f[]="AccessFileName .htaccess";
	$f[]="<Files ~ \"^\.ht\">";
	//$f[]="\tOrder allow,deny";
	//$f[]="\tDeny from all";
	//$f[]="\tSatisfy all";
	$f[]="</Files>";
	$f[]="DefaultType text/plain";
	$f[]="HostnameLookups Off";
	$f[]="User				   $APACHE_SRC_ACCOUNT";
	$f[]="Group				   $APACHE_SRC_GROUP";
	$f[]="Timeout              300";
	$f[]="KeepAlive            Off";
	$f[]="KeepAliveTimeout     15";
	$f[]="StartServers         1";
	$f[]="MaxClients           $MaxClients";
	$f[]="MinSpareServers      2";
	$f[]="MaxSpareServers      3";
	$f[]="MaxRequestsPerChild  100";
	$f[]="MaxKeepAliveRequests 100";
	$ServerName=$unix->hostname_g();
	if($ServerName==null){$ServerName="localhost.localdomain";}
	$f[]="AcceptMutex flock";
	$f[]="";
	$f[]="<IfModule mod_ssl.c>";
	$f[]="\tSSLRandomSeed connect builtin";
	$f[]="\tSSLRandomSeed connect file:/dev/urandom 256";
	$f[]="\tSSLRandomSeed startup file:/dev/urandom  256";
	$f[]="\tSSLPassPhraseDialog  builtin";
	$f[]="\tSSLSessionCache        shmcb:/var/run/poweradmin/ssl_scache-artica(512000)";
	$f[]="\tSSLSessionCacheTimeout  300";
	$f[]="\tSSLSessionCacheTimeout  300";
	$f[]="\tSSLCipherSuite HIGH:MEDIUM:!ADH";
	$f[]="\tSSLProtocol all -SSLv2";
	$f[]="</IfModule>";
	$f[]="";
	$f[]="\tAddType application/x-x509-ca-cert .crt";
	$f[]="\tAddType application/x-pkcs7-crl    .crl";
	

	$f[]="<IfModule mod_mime.c>";
	$f[]="\tTypesConfig /etc/mime.types";
	$f[]="\tAddType application/x-compress .Z";
	$f[]="\tAddType application/x-gzip .gz .tgz";
	$f[]="\tAddType application/x-bzip2 .bz2";
	$f[]="\tAddType application/x-httpd-php .php .phtml";
	$f[]="\tAddType application/x-httpd-php-source .phps";
	$f[]="\tAddType application/octet-stream .acl";
	$f[]="\tAddLanguage ca .ca";
	$f[]="\tAddLanguage cs .cz .cs";
	$f[]="\tAddLanguage da .dk";
	$f[]="\tAddLanguage de .de";
	$f[]="\tAddLanguage el .el";
	$f[]="\tAddLanguage en .en";
	$f[]="\tAddLanguage eo .eo";
	$f[]="\tRemoveType  es";
	$f[]="\tAddLanguage es .es";
	$f[]="\tAddLanguage et .et";
	$f[]="\tAddLanguage fr .fr";
	$f[]="\tAddLanguage he .he";
	$f[]="\tAddLanguage hr .hr";
	$f[]="\tAddLanguage it .it";
	$f[]="\tAddLanguage ja .ja";
	$f[]="\tAddLanguage ko .ko";
	$f[]="\tAddLanguage ltz .ltz";
	$f[]="\tAddLanguage nl .nl";
	$f[]="\tAddLanguage nn .nn";
	$f[]="\tAddLanguage no .no";
	$f[]="\tAddLanguage pl .po";
	$f[]="\tAddLanguage pt .pt";
	$f[]="\tAddLanguage pt-BR .pt-br";
	$f[]="\tAddLanguage ru .ru";
	$f[]="\tAddLanguage sv .sv";
	$f[]="\tRemoveType  tr";
	$f[]="\tAddLanguage tr .tr";
	$f[]="\tAddLanguage zh-CN .zh-cn";
	$f[]="\tAddLanguage zh-TW .zh-tw";
	$f[]="\tAddCharset us-ascii    .ascii .us-ascii";
	$f[]="\tAddCharset ISO-8859-1  .iso8859-1  .latin1";
	$f[]="\tAddCharset ISO-8859-2  .iso8859-2  .latin2 .cen";
	$f[]="\tAddCharset ISO-8859-3  .iso8859-3  .latin3";
	$f[]="\tAddCharset ISO-8859-4  .iso8859-4  .latin4";
	$f[]="\tAddCharset ISO-8859-5  .iso8859-5  .cyr .iso-ru";
	$f[]="\tAddCharset ISO-8859-6  .iso8859-6  .arb .arabic";
	$f[]="\tAddCharset ISO-8859-7  .iso8859-7  .grk .greek";
	$f[]="\tAddCharset ISO-8859-8  .iso8859-8  .heb .hebrew";
	$f[]="\tAddCharset ISO-8859-9  .iso8859-9  .latin5 .trk";
	$f[]="\tAddCharset ISO-8859-10  .iso8859-10  .latin6";
	$f[]="\tAddCharset ISO-8859-13  .iso8859-13";
	$f[]="\tAddCharset ISO-8859-14  .iso8859-14  .latin8";
	$f[]="\tAddCharset ISO-8859-15  .iso8859-15  .latin9";
	$f[]="\tAddCharset ISO-8859-16  .iso8859-16  .latin10";
	$f[]="\tAddCharset ISO-2022-JP .iso2022-jp .jis";
	$f[]="\tAddCharset ISO-2022-KR .iso2022-kr .kis";
	$f[]="\tAddCharset ISO-2022-CN .iso2022-cn .cis";
	$f[]="\tAddCharset Big5        .Big5       .big5 .b5";
	$f[]="\tAddCharset cn-Big5     .cn-big5";
	$f[]="\t# For russian, more than one charset is used (depends on client, mostly):";
	$f[]="\tAddCharset WINDOWS-1251 .cp-1251   .win-1251";
	$f[]="\tAddCharset CP866       .cp866";
	$f[]="\tAddCharset KOI8      .koi8";
	$f[]="\tAddCharset KOI8-E      .koi8-e";
	$f[]="\tAddCharset KOI8-r      .koi8-r .koi8-ru";
	$f[]="\tAddCharset KOI8-U      .koi8-u";
	$f[]="\tAddCharset KOI8-ru     .koi8-uk .ua";
	$f[]="\tAddCharset ISO-10646-UCS-2 .ucs2";
	$f[]="\tAddCharset ISO-10646-UCS-4 .ucs4";
	$f[]="\tAddCharset UTF-7       .utf7";
	$f[]="\tAddCharset UTF-8       .utf8";
	$f[]="\tAddCharset UTF-16      .utf16";
	$f[]="\tAddCharset UTF-16BE    .utf16be";
	$f[]="\tAddCharset UTF-16LE    .utf16le";
	$f[]="\tAddCharset UTF-32      .utf32";
	$f[]="\tAddCharset UTF-32BE    .utf32be";
	$f[]="\tAddCharset UTF-32LE    .utf32le";
	$f[]="\tAddCharset euc-cn      .euc-cn";
	$f[]="\tAddCharset euc-gb      .euc-gb";
	$f[]="\tAddCharset euc-jp      .euc-jp";
	$f[]="\tAddCharset euc-kr      .euc-kr";
	$f[]="\tAddCharset EUC-TW      .euc-tw";
	$f[]="\tAddCharset gb2312      .gb2312 .gb";
	$f[]="\tAddCharset iso-10646-ucs-2 .ucs-2 .iso-10646-ucs-2";
	$f[]="\tAddCharset iso-10646-ucs-4 .ucs-4 .iso-10646-ucs-4";
	$f[]="\tAddCharset shift_jis   .shift_jis .sjis";
	$f[]="\tAddType text/html .shtml";
	$f[]="\tAddOutputFilter INCLUDES .shtml";
	$f[]="</IfModule>";
	
	$f[]="";
	$f[]="<VirtualHost $ArticaHttpsPort>";	
	$f[]="\tServerName $ServerName";
	$f[]="\tAddType application/x-httpd-php .php";
	$f[]="\tphp_value post_max_size 50M";
	$f[]="\tphp_value upload_max_filesize 50M";
	$f[]="\tphp_value session.gc_maxlifetime {$php5SessionGCMaxlifeTime}";
	$f[]="\tphp_value session.gc_divisor 1";
	$f[]="\tphp_value session.gc_probabilit 1";
	$f[]="\tphp_value session.cookie_lifetime 0";
	$f[]="\tphp_value error_log \"/var/log/poweradmin/php.log\"";
	$f[]="\tphp_value session.save_path \"/usr/share/poweradmin/sessions\"";
		
	$mknod=$unix->find_program("mknod");
	$f[]="\tSSLEngine on";
	if($PowerAdminCertificateName==null){
		$f[]="\tSSLCertificateFile \"/etc/ssl/certs/apache/server.crt\"";
		$f[]="\tSSLCertificateKeyFile \"/etc/ssl/certs/apache/server.key\"";
		if(!is_file("/etc/ssl/certs/apache/server.crt")){shell_exec("/usr/share/artica-postfix/bin/artica-install --apache-ssl-cert");}
	}else{
		$cert=new apache_certificate($PowerAdminCertificateName);
		$f[]=$cert->build();
	}
	$f[]="\tSSLVerifyClient none";
	$f[]="\tServerSignature Off";
	shell_exec("$mknod /dev/random c 1 9 >/dev/null 2>&1");

	
		
	$f[]="<IfModule mod_fcgid.c>";
	$f[]="	PHP_Fix_Pathinfo_Enable 1";
	$f[]="</IfModule>";
	
	$f[]="<IfModule mod_php5.c>";
	$f[]="    <FilesMatch \"\.ph(p3?|tml)$\">";
	$f[]="	SetHandler application/x-httpd-php";
	$f[]="    </FilesMatch>";
	$f[]="    <FilesMatch \"\.phps$\">";
	$f[]="	SetHandler application/x-httpd-php-source";
	$f[]="    </FilesMatch>";
	$f[]="    <IfModule mod_userdir.c>";
	$f[]="        <Directory /home/*/public_html>";
	$f[]="            php_admin_value engine Off";
	$f[]="        </Directory>";
	$f[]="    </IfModule>";
	$f[]="</IfModule>";	

	
	$f[]="<Directory \"/usr/share/poweradmin\">";
	$f[]="\tDirectoryIndex index.php";
	$f[]="\tSSLOptions +StdEnvVars";
	$f[]="\tOptions Indexes FollowSymLinks";
	$f[]="\tAllowOverride Options";
	$f[]="</Directory>";	
	$f[]="</VirtualHost>";
	

	$f[]="Loglevel crit";
	$f[]="ErrorLog /var/log/poweradmin/http-error.log";
	$f[]="LogFormat \"%a %l %u %t \\\"%r\\\" %<s %b\" common";
	$f[]="CustomLog /dev/null common";
	
	$array["php5_module"]="libphp5.so";
	
	
	$array["actions_module"]="mod_actions.so";
	$array["expires_module"]="mod_expires.so";
	$array["rewrite_module"]="mod_rewrite.so";
	$array["dir_module"]="mod_dir.so";
	$array["mime_module"]="mod_mime.so";
	$array["alias_module"]="mod_alias.so";
	$array["auth_basic_module"]="mod_auth_basic.so";
	$array["authn_file_module"]="mod_authn_file.so";	
	$array["autoindex_module"]="mod_autoindex.so";
	$array["negotiation_module"]="mod_negotiation.so";
	$array["ssl_module"]="mod_ssl.so";
	$array["headers_module"]="mod_headers.so";
	


	
	if(is_dir("/etc/apache2")){
		if(!is_file("/etc/apache2/mime.types")){
			if($apache_LOCATE_MIME_TYPES<>"/etc/apache2/mime.types"){
				@copy($apache_LOCATE_MIME_TYPES, "/etc/apache2/mime.types");
			}
		}
		
	}
	
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Mime types path.......: $apache_LOCATE_MIME_TYPES\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Modules path..........: $APACHE_MODULES_PATH\n";}
	
	while (list ($module, $lib) = each ($array) ){
		
		if(is_file("$APACHE_MODULES_PATH/$lib")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} include module \"$module\"\n";}
			$f[]="LoadModule $module $APACHE_MODULES_PATH/$lib";
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} skip module \"$module\"\n";}
		}
	
	}

	

	@file_put_contents("/etc/poweradmin/httpd.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} /etc/poweradmin/httpd.conf done\n";}
	
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


