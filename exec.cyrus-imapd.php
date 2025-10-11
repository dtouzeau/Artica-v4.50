<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["TITLENAME"]="Cyrus IMAP Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG_CHOWN"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--build"){BuildConfig();exit;}



function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$unix->CreateUnixUser("postfix","postfix");
	$php=$unix->LOCATE_PHP5_BIN();
	if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Stopping service\n";}
	stop(true);
	if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Stopping saslauthd service\n";}
	system("$php /usr/share/artica-postfix/exec.saslauthd.php --stop");
	sleep(1);
	if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Starting service\n";}
	start(true);
	if($GLOBALS["OUTPUT"]){echo "Restarting....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Starting saslauthd\n";}
	system("$php /usr/share/artica-postfix/exec.saslauthd.php --start");
}

function reload($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			
			if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	CheckPermissions();
	BuildConfig();
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Not running, start it...\n";}
		start(true);
		return;
	}
	
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} cyrus-imapd running since {$time}mn\n";}
	$kill=$unix->find_program("kill");
	unix_system_HUP($pid);
	
	$unix->CreateUnixUser("postfix","postfix");
	
	$lmtpsocket="/var/spool/postfix/var/run/cyrus/socket/lmtp";
	for($i=1;$i<5;$i++){
		if($unix->is_socket($lmtpsocket)){
			if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Waiting socket success..\n";}
			$unix->chown_func("postfix","postfix","/var/spool/postfix/var/run");
			$unix->chown_func("postfix","postfix","$lmtpsocket");
			break;
		}
			
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." {$GLOBALS["TITLENAME"]} Waiting socket $i/5\n";}
		sleep(1);
	}
	
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->CYRUS_DAEMON_BIN_PATH();
	$zarafaBin=$unix->find_program("zarafa-server");
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, arpd not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	if($unix->process_exists($unix->get_pid_from_file("/etc/artica-postfix/artica-backup.pid"))){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} A backup task currently is in use\n";}
		return;
	}	
	
	$pid=PID_NUM();
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	
	if(is_file("/etc/artica-postfix/stop.cyrus.imapd")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} LOCKED !\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Remove /etc/artica-postfix/stop.cyrus.imapd !\n";}
		return;
	}
	if(is_file("/etc/artica-postfix/cyrus-stop")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} LOCKED !\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Remove /etc/artica-postfix/cyrus-stop !\n";}
		return;
	}	
	
	$unix->CreateUnixUser("postfix","postfix");
	$php5=$unix->LOCATE_PHP5_BIN();
	$EnableCyrusImap=intval($sock->GET_INFO("EnableCyrusImap"));

	$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
	$DisableIMAPVerif=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableIMAPVerif"));
	
	if($DisableIMAPVerif==0){
		if(is_file("$zarafaBin")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Zarafa is installed, aborting\n";}
			stop(true);
			return;
		}	
	}
	
	if($EnableCyrusImap==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableCyrusImap)\n";}
		return;
	}
	
	if($DisableMessaging==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see DisableMessaging)\n";}
		return;
	}	
	
	if(!is_file('/usr/bin/cyradm')){
		$cyradm=$unix->CYRADM_PATH();
		if(is_file($cyradm)){shell_exec("/bin/ln -s $cyradm /usr/bin/cyradm");}
	}
	
	if(is_file('/usr/share/artica-postfix/exec.imapd.conf.php')){
		shell_exec("$php5 /usr/share/artica-postfix/exec.imapd.conf.php >/dev/null 2>&1");
	}

	
	if(!is_file('/etc/artica-postfix/cyrus.check.time')){
		shell_exec("/usr/share/artica-postfix/bin/artica-install --cyrus-rights >/dev/null 2>&1");
	}
	
	
	shell_exec("$php5 /usr/share/artica-postfix/exec.check-cyrus-account.php --check-adms");
	

	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Check permissions\n";}
	CheckPermissions();
	BuildConfig();
	
	$params[]="$nohup $Masterbin";
	$params[]="-M /etc/cyrus.conf";
	$params[]="-C /etc/imapd.conf";
	$params[]="-p /var/run/cyrmaster.pid -d >/dev/null 2>&1 &";
	
	$cmd=@implode(" ", $params);
	shell_exec($cmd);
	
	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		
		$lmtpsocket="/var/spool/postfix/var/run/cyrus/socket/lmtp";
		for($i=1;$i<5;$i++){
			if($unix->is_socket($lmtpsocket)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Waiting socket success..\n";}
				$unix->chown_func("postfix","postfix","/var/spool/postfix/var/run");
				$unix->chown_func("postfix","postfix","$lmtpsocket");
				break;
			}
			
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Waiting socket $i/5\n";}
			sleep(1);
		}
		
		
		
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}





function BuildConfig(){
	$unix=new unix();
	$sock=new sockets();
	$php=$unix->LOCATE_PHP5_BIN();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Checking cyrusadm ad\n";}
	shell_exec("$php /usr/share/artica-postfix/exec.cyrus.php --cyrusadm-ad");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Checking DB CONFIG\n";}
	shell_exec("$php /usr/share/artica-postfix/exec.cyrus.php --DB_CONFIG");
	$impadconf=explode("\n",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("impadconf"));
	
	while (list ($index, $ligne) = each ($impadconf) ){
		if(!preg_match("#(.+?):(.+)#", $ligne,$re)){continue;}
		$IMAPD_GET_ARTICA[trim($re[1])]=trim($re[2]);
	}
	
	
	
	
	$CyrusAdmPlus=trim(@file_get_contents('/etc/artica-postfix/CyrusAdmPlus'));
	$maxmessagesize=$IMAPD_GET_ARTICA['maxmessagesize'];
	$autocreateinboxfolders=$IMAPD_GET_ARTICA['autocreateinboxfolders'];
	$quotawarn=$IMAPD_GET_ARTICA['quotawarn'];
	$allowallsubscribe=$IMAPD_GET_ARTICA['allowallsubscribe'];
	$duplicatesuppression=$IMAPD_GET_ARTICA['duplicatesuppression'];
	$popminpoll=$IMAPD_GET_ARTICA['popminpoll'];
	$createonpost=$IMAPD_GET_ARTICA['createonpost'];
	$allowanonymouslogin=$IMAPD_GET_ARTICA['allowanonymouslogin'];
	$partition_default=trim($sock->GET_INFO('CyrusPartitionDefault'));
	if($partition_default==null){$partition_default='/var/spool/cyrus/mail';}
	
	if(!is_numeric($popminpoll)){$popminpoll=0;}
	if(!is_numeric($maxmessagesize)){$maxmessagesize=0;}
	if($autocreateinboxfolders==null){$autocreateinboxfolders='sent|drafts|spam|templates';}
	if(!is_numeric($quotawarn)){$quotawarn=90;}
	if(!is_numeric($allowallsubscribe)){$allowallsubscribe=1;}
	if(!is_numeric($duplicatesuppression)){$duplicatesuppression=0;}
	if(!is_numeric($allowanonymouslogin)){$allowanonymouslogin=0;}
	if(!is_numeric($createonpost)){$createonpost=1;}

	
	
	
	$EnableMechCramMD5=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMechCramMD5"));
	$EnableMechDigestMD5=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMechDigestMD5"));
	
	$EnableMechLogin=$sock->GET_INFO("EnableMechLogin");
	$EnableMechPlain=$sock->GET_INFO("EnableMechPlain");
	if(!is_numeric($EnableMechLogin)){$EnableMechLogin=1;}
	if(!is_numeric($EnableMechPlain)){$EnableMechPlain=1;}

	$sasl_mech_listZ=array();
	if($EnableMechLogin==1){$sasl_mech_listZ[]='LOGIN';}
	if($EnableMechPlain==1){$sasl_mech_listZ[]='PLAIN';}
	if($EnableMechDigestMD5==1){$sasl_mech_listZ[]='DIGEST-MD5';}
	if($EnableMechCramMD5==1){$sasl_mech_listZ[]="CRAM-MD5";}
	
	if(count($sasl_mech_listZ)==0){
		$sasl_mech_listZ[]='LOGIN';
		$sasl_mech_listZ[]='PLAIN';
	}
	
	$sasl_mech_list=@implode(" ", $sasl_mech_listZ);
	
	$EnableCyrusMasterCluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCyrusMasterCluster"));
	$CyrusImapDisableCluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CyrusImapDisableCluster"));
	$EnableCyrusReplicaCluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCyrusReplicaCluster"));
	$cyrus_id=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cyrus_id"));
	$CyrusClusterPort=intval($sock->GET_INFO('CyrusClusterPort'));
	$CyrusReplicaClusterPort=intval($sock->GET_INFO('CyrusReplicaClusterPort'));
	$CyrusEnableBackendMurder=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CyrusEnableBackendMurder"));
	$CyrusEnableImapMurderedFrontEnd=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CyrusEnableImapMurderedFrontEnd"));
	$EnableVirtualDomainsInMailBoxes=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVirtualDomainsInMailBoxes"));
	$chown=$unix->find_program("chown");
	$configdirectory='/var/lib/cyrus';
	$srvtab='/var/lib/cyrus/srvtab';
	$POSTFIX_QUEUE_DIRECTORY=$unix->POSTCONF_GET("queue_directory");
	
	$echo=$unix->find_program("echo");
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} sasl_mech_list...........: $sasl_mech_list\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Partition Default........: $partition_default\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Postfix Queue directory..: $POSTFIX_QUEUE_DIRECTORY\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableVirtualDomainsInMailBoxes: $EnableVirtualDomainsInMailBoxes\n";}
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableCyrusMasterCluster.: $EnableCyrusMasterCluster\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableCyrusReplicaCluster: $EnableCyrusReplicaCluster\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} CyrusClusterPort.........: $CyrusClusterPort\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} CyrusEnableBackendMurder.: $CyrusEnableBackendMurder\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} CyrusEnableImapMurderedFrontEnd.: $CyrusEnableImapMurderedFrontEnd\n";}
	
	
	
	
if($EnableCyrusMasterCluster==1){
	CHANGE_SERVICES_IP("csync",$CyrusClusterPort);

}
if($EnableCyrusReplicaCluster==1){
	CHANGE_SERVICES_IP("csync",$CyrusReplicaClusterPort);
}
$ldap=new clladp();
$LDAP_PASSWORD=$ldap->ldap_password;
$LDAP_SUFFIX=$ldap->suffix;
$HOSTNAME=$unix->hostname_g();
$HOSTNAMES=explode(".",$HOSTNAME);
unset($HOSTNAMES[0]);
$DOMAIN=@implode(".", $HOSTNAMES);

if($CyrusEnableBackendMurder==1){
	$SS[]="configdirectory: /var/lib/cyrus-murder";
	$SS[]="partition-default:$partition_default";
	$SS[]="ldap_uri: ldap://$ldap->ldap_host:$ldap->ldap_port";
	$SS[]="sasl_mech_list: $sasl_mech_list";
	$SS[]="admins: murder";
	@file_put_contents("/etc/imap-murder.conf", @implode("\n", $SS));
	@mkdir("/var/lib/cyrus-murder/socket",0755,true);
	@mkdir("/var/lib/cyrus-murder/db",0755,true);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/imap-murder.conf done\n";}
	shell_exec("$chown -R cyrus:mail /var/lib/cyrus-murder");
	shell_exec("$echo \"$LDAP_PASSWORD\"|/usr/sbin/saslpasswd2 -c murder");
}

$partition_default_root=dirname($partition_default);

$f=array();
$f[]="configdirectory: $configdirectory";
$f[]="defaultpartition: default";
$f[]="partition-default: $partition_default";
$f[]="partition-news: /var/spool/cyrus/news";
$f[]="srvtab: $srvtab";
$f[]="newsspool: $partition_default_root/news";
$f[]="sievedir: $partition_default_root/sieve";
$f[]="idlesocket: /var/run/cyrus/socket/idle";
$f[]="notifysocket: /var/run/cyrus/socket/notify";
$f[]="lmtpsocket: $POSTFIX_QUEUE_DIRECTORY/var/run/cyrus/socket/lmtp";
$f[]="sasl_saslauthd_path:/var/run/saslauthd/mux";
$f[]="altnamespace: no";
$f[]="unixhierarchysep: yes";
$f[]="lmtp_downcase_rcpt: yes";
$f[]="umask: 077";
$f[]="sieveusehomedir: false";
$f[]="hashimapspool: true";
$f[]="allowplaintext: yes";
$f[]="sasl_pwcheck_method: saslauthd";
$f[]="sasl_auto_transition: no";
$f[]="sasl_minimum_layer: 0";
$f[]="ldap_member_base:dc=organizations,$LDAP_SUFFIX";
$f[]="idlemethod: poll";
$f[]="syslog_prefix: cyrus";
$f[]="servername: $HOSTNAME";


if($CyrusEnableBackendMurder==1){
	$f[]="mupdate_server: $HOSTNAME";
	$f[]="mupdate_port: 3905";
	$f[]="mupdate_authname: murder";
	$f[]="mupdate_username: murder";
	$f[]="mupdate_password: $LDAP_PASSWORD";
	$f[]="proxyservers: murder";
}

if($CyrusEnableImapMurderedFrontEnd==1){
	$ini=new Bs_IniHandler();
	$ini->loadFile("/etc/artica-postfix/settings/Daemons/CyrusMurderBackendServer");
	$MURDER_BACKEND=$ini->_params["MURDER_BACKEND"]["servername"];
	$backend_server_name=str_replace(".", "_", $MURDER_BACKEND);
	
	$f[]="mupdate_server: $MURDER_BACKEND";
	$f[]="mupdate_port: 3905";
	$f[]="mupdate_authname: murder";
	$f[]="mupdate_username: murder";
	$f[]="mupdate_password: $LDAP_PASSWORD";
	$f[]="#mupdate_config: standard";
	$f[]="allowusermoves: true";
	$f[]="{$backend_server_name}_authname: murder";
	$f[]="{$backend_server_name}_password: $LDAP_PASSWORD";
	$f[]="proxy_authname: murder";
}

if($EnableCyrusMasterCluster==1){
	$ini=new Bs_IniHandler();
	$ini->loadFile("/etc/artica-postfix/settings/Daemons/CyrusClusterReplicaInfos");
	$CyrusClusterID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CyrusClusterID"));
	if($CyrusClusterID==0){$CyrusClusterID=1;}
	$f[]="sync_host: {$ini->_params["REPLICA"]["servername"]}";
	$f[]="sync_authname: cyrus";
	$f[]="sync_password: {$ini->_params["REPLICA"]["password"]}";
	$f[]="guid_mode: sha1";
	$f[]="sync_log: yes";
	$f[]="sync_repeat_interval: 60";
	$f[]="sync_batch_size: 1000";
	$f[]="sync_machineid: $CyrusClusterID";
}

$cur_email[]="cyrus";
$cur_email[]="cyrus@$DOMAIN";

if($EnableVirtualDomainsInMailBoxes==1){
	$ldap=new clladp();
	if($ldap->ldapFailed){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed to connect to LDAP server, checking cache\n";}
		$cur_email=unserialize(@file_get_contents("/etc/artica-postfix/CYRUS_ADMINS_CACHE"));
		
	}else{
		$domains=$ldap->hash_get_all_local_domains();
		while (list ($domain, $cyrusadm) = each ($domains) ){
			$ldap->CyrusAdminOtherCreate("cyrus@$domain",null,true);
			$cur_email[]="cyrus@$domain";
		}
		@file_put_contents("/etc/artica-postfix/CYRUS_ADMINS_CACHE", serialize($cur_email));
	}
}


if($CyrusAdmPlus<>null){$cur_email[]=$CyrusAdmPlus; }

while (list ($index, $cyrusadm) = each ($cur_email) ){$cyrusadm=trim($cyrusadm);if($cyrusadm==null){continue;}$_cyrusadms[$cyrusadm]=$cyrusadm;}
while (list ($cyrusadm, $xcyrusadm) = each ($_cyrusadms) ){
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Cyrus Admin..............: `$cyrusadm`\n";}
	$_cyrusadms2[]=$cyrusadm;}

if($EnableVirtualDomainsInMailBoxes==1){
	$f[]="virtdomains: userid";
	$f[]="defaultdomain: localhost.localdomain";
}else{
	$f[]="virtdomains: no";
}

$f[]="sasl_mech_list: $sasl_mech_list";

if($CyrusEnableBackendMurder==1){$cur_email[]="murder";}
if($CyrusEnableImapMurderedFrontEnd==1){ $cur_email[]="murder";}
$cur_emails=@implode(" ", $_cyrusadms2);
$f[]="admins: $cur_emails";
$f[]="username_tolower: 1";
$f[]="ldap_uri: ldap://$ldap->ldap_host:$ldap->ldap_port";
$f[]="";
$f[]="";
$f[]="autocreatequota: 0";
$f[]="popminpoll: $popminpoll";
$f[]="maxmessagesize: $maxmessagesize";
$f[]="autocreateinboxfolders: $autocreateinboxfolders";
$f[]="quotawarn: $quotawarn";
$f[]="allowallsubscribe: $allowallsubscribe";
$f[]="duplicatesuppression: $duplicatesuppression";
$f[]="allowanonymouslogin: $allowanonymouslogin";
$f[]="createonpost: $createonpost";
$f[]="sieve_maxscriptsize: 1024";
$f[]="";
if(is_file("/etc/ssl/certs/cyrus.pem")){
	$f[]="tls_cert_file: /etc/ssl/certs/cyrus.pem";
	$f[]="tls_key_file: /etc/ssl/certs/cyrus.pem";
	$f[]="tls_imap_cert_file: /etc/ssl/certs/cyrus.pem";
	$f[]="tls_imap_key_file: /etc/ssl/certs/cyrus.pem";
	$f[]="tls_pop3_cert_file: /etc/ssl/certs/cyrus.pem";
	$f[]="tls_pop3_key_file: /etc/ssl/certs/cyrus.pem";
	$f[]="tls_lmtp_cert_file: /etc/ssl/certs/cyrus.pem";
	$f[]="tls_lmtp_key_file: /etc/ssl/certs/cyrus.pem";
	$f[]="sieve_tls_key_file: /etc/ssl/certs/cyrus.pem";
	$f[]="tls_sieve_cert_file: /etc/ssl/certs/cyrus.pem";
	$f[]="tls_sieve_key_file: /etc/ssl/certs/cyrus.pem";
	$f[]="tls_ca_file: /etc/ssl/certs/cyrus.pem";
	$f[]="tls_ca_path: /etc/ssl/certs";
	$f[]="tls_session_timeout: 1440";
	$f[]="tls_cipher_list: TLSv1+HIGH:!aNULL:@STRENGTH";
}
$f[]="tls_require_cert: false";
$f[]="tls_imap_require_cert: false";
$f[]="tls_pop3_require_cert: false";
$f[]="tls_lmtp_require_cert: false";
$f[]="tls_sieve_require_cert: false";

if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/imapd.conf DONE\n";}
@file_put_contents("/etc/imapd.conf", @implode("\n", $f));
WRITE_CYRUS_CONF();	
	
}

function CheckPermissions(){
	$unix=new unix();
	$unix->SystemCreateUser("mail","mail");
	$unix->SystemCreateUser("postfix","mail");
	$unix->SystemCreateUser("cyrus","cyrus");
	$POSTFIX_QUEUE_DIRECTORY=$unix->POSTCONF_GET("queue_directory");
	
	$dirs[]="/var/lib/cyrus";
	$dirs[]="/var/lib/cyrus/db";
	$dirs[]="/var/lib/cyrus/socket";
	$dirs[]="/var/lib/cyrus/proc";
	$dirs[]="/var/run/cyrus/socket";
	$dirs[]="/var/spool/postfix/var/run/cyrus/socket";
	$ln=$unix->find_program("ln");
	$dirs[]="$POSTFIX_QUEUE_DIRECTORY/var/run/cyrus";

    foreach ($dirs as $num=>$directory){
		if(!is_dir($directory)){ @mkdir($directory,0755,true); }
		$unix->chmod_func(0755, "$directory");
		$unix->chown_func("cyrus", "mail","$directory");
	}
	
	$unix->chown_func("cyrus", "cyrus","/var/lib/cyrus");
	if(!is_file("/var/lib/cyrus/user_deny.db")){
		@touch("/var/lib/cyrus/user_deny.db");
		$unix->chown_func("cyrus", "mail","/var/lib/cyrus/user_deny.db");
	}
	
	
	
	

	
}


function CHANGE_SERVICES_IP($servicename=null,$port=0){
if($port==0){return;}

$f=explode("\n",@file_get_contents("/etc/services"));
foreach ($f as $index=>$ligne){
	if(!preg_match("#^$servicename\s+([0-9]+)#", $ligne,$re)){continue;}
	if($re[1]==$port){return;}
	$f[$index]="$servicename\t$port/tcp";
	@file_put_contents("/etc/services", @implode("\n", $f));
	return;
}

$f[]="$servicename\t$port/tcp";
@file_put_contents("/etc/services", @implode("\n", $f));
}


function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		stop_sync_client();
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		stop_sync_client();
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		stop_sync_client();
		return;
	}

}

function stop_sync_client(){
	$unix=new unix();
	if(!is_file($unix->CYRUS_SYNC_CLIENT_BIN_PATH())){return;}
	$pid=PID_NUM();
	
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} - sync_client - service already stopped...\n";}
		return;
	}
	$pid=sync_client_pid();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} - sync_client - service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=sync_client_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} - sync_client - Service Waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	
	$pid=sync_client_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} - sync_client - Service success...\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} - sync_client - service Shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=sync_client_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} - sync_client - service Waiting pid:$pid $i/5...\n";}
		sleep(1);
	}
	
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} - sync_client - service Failed...\n";}
		return;
	}
	
}

function sync_client_pid(){
	$unix=new unix();
	return $unix->PIDOF($unix->CYRUS_SYNC_CLIENT_BIN_PATH());
}

function PID_NUM(){
	$unix=new unix();
	$pidpath=$unix->CYRUS_PID_PATH();
	$pid=$unix->get_pid_from_file($pidpath);
	if(!$unix->process_exists($pid)){
    	return $unix->PIDOF($unix->CYRUS_DAEMON_BIN_PATH());
	}
	return $pid;

	
}

function LOCATE_CYRUS_SQUATTER(){
if(is_file('/usr/sbin/squatter')){ return '/usr/sbin/squatter';}
if(is_file('/usr/lib/cyrus/bin/squatter')){ return '/usr/lib/cyrus/bin/squatter';}
if(is_file('/usr/lib/cyrus-imapd/squatter')){ return '/usr/lib/cyrus-imapd/squatter';}
$unix=new unix();
return $unix->find_program("squatter");
}

function ctl_cyrusdb_path(){
if(is_file('/usr/lib/cyrus-imapd/ctl_cyrusdb')){return '/usr/lib/cyrus-imapd/ctl_cyrusdb';}
if(is_file('/usr/sbin/ctl_cyrusdb')){return '/usr/sbin/ctl_cyrusdb';}
if(is_file('/usr/lib/cyrus/bin/ctl_cyrusdb')){return '/usr/lib/cyrus/bin/ctl_cyrusdb';}
if(is_file('/usr/lib/cyrus/ctl_cyrusdb')){return '/usr/lib/cyrus/ctl_cyrusdb';}
$unix=new unix();
return $unix->find_program("ctl_cyrusdb");
}
//#############################################################################
function cyr_expire_path(){
if(is_file('/usr/sbin/cyr_expire')){return '/usr/sbin/cyr_expire';}
if(is_file('/usr/lib/cyrus-imapd/cyr_expire')){return '/usr/lib/cyrus-imapd/cyr_expire';}
if(is_file('/usr/lib/cyrus/bin/cyr_expire')){return '/usr/lib/cyrus/bin/cyr_expire';}
$unix=new unix();
return $unix->find_program("cyr_expire");
}
//##############################################################################
function tls_prune_path(){
if(is_file('/usr/sbin/tls_prune')){return '/usr/sbin/tls_prune';}
if(is_file('/usr/lib/cyrus-imapd/tls_prune')){return '/usr/lib/cyrus-imapd/tls_prune';}
if(is_file('/usr/lib/cyrus/bin/tls_prune')){return '/usr/lib/cyrus/bin/tls_prune';}
$unix=new unix();
return $unix->find_program("tls_prune");
}
//##############################################################################
function CYRUS_POP3D_BIN_PATH(){
if(is_file('/usr/lib/cyrus/bin/pop3d')){return '/usr/lib/cyrus/bin/pop3d';}
if(is_file('/usr/sbin/pop3d')){return '/usr/sbin/pop3d';}
if(is_file('/opt/artica/cyrus/bin/pop3d')){return '/opt/artica/cyrus/bin/pop3d';}
if(is_file('/usr/lib/cyrus-imapd/pop3d')){return '/usr/lib/cyrus-imapd/pop3d';}
$unix=new unix();
return $unix->find_program("pop3d");
}
//#############################################################################
function LOCATE_ctl_mboxlist(){
if(is_file('/usr/sbin/ctl_mboxlist')){return '/usr/sbin/ctl_mboxlist';}
if(is_file('/usr/lib/cyrus/bin/ctl_mboxlist')){return '/usr/lib/cyrus/bin/ctl_mboxlist';}
if(is_file('/usr/lib/cyrus-imapd/ctl_mboxlist')){return '/usr/lib/cyrus-imapd/ctl_mboxlist';}
$unix=new unix();
return $unix->find_program("ctl_mboxlist");
}
function CYRUS_SYNC_CLIENT_BIN_PATH(){
if(is_file('/usr/lib/cyrus-imapd/sync_client')){return '/usr/lib/cyrus-imapd/sync_client';}
if(is_file('/usr/lib/cyrus/bin/sync_client')){return '/usr/lib/cyrus/bin/sync_client';}
if(is_file('/usr/sbin/sync_client')){return '/usr/sbin/sync_client';}
$unix=new unix();
return $unix->find_program("sync_client");
}
//#############################################################################
function sieved_path(){
	if(is_file('/usr/lib/cyrus-imapd/sieved')){return '/usr/lib/cyrus-imapd/ctl_cyrusdb';}
	if(is_file('/usr/sbin/sieved')){return '/usr/sbin/sieved';}
	if(is_file('/usr/lib/cyrus/bin/timsieved')){return '/usr/lib/cyrus/bin/timsieved';}
	if(is_file('/usr/lib/cyrus/bin/sieved')){return '/usr/lib/cyrus/bin/sieved';}
	if(is_file('/usr/lib/cyrus/timsieved')){return '/usr/lib/cyrus/timsieved';}
	$unix=new unix();
	return $unix->find_program("timsieved");
}
//#############################################################################
function notify_path(){
if(is_file('/usr/lib/cyrus-imapd/notifyd')){return '/usr/lib/cyrus-imapd/notifyd';}
if(is_file('/usr/lib/cyrus/bin/notifyd')){return '/usr/lib/cyrus/bin/notifyd';}
if(is_file('/usr/sbin/notifyd')){return '/usr/sbin/notifyd';}
if(is_file('/usr/lib/cyrus/notifyd')){return '/usr/lib/cyrus/notifyd';}
$unix=new unix();
return $unix->find_program("notifyd");
}
//#############################################################################
function CYRUS_SYNC_SERVER_BIN_PATH(){
if(is_file('/usr/lib/cyrus-imapd/sync_server')){return '/usr/lib/cyrus-imapd/sync_server';}
if(is_file('/usr/lib/cyrus/bin/sync_server')){return '/usr/lib/cyrus/bin/sync_server';}
if(is_file('/usr/sbin/sync_server')){return '/usr/sbin/sync_server';}
$unix=new unix();
return $unix->find_program("sync_server");
}
//#############################################################################
function LOCATE_CYRUS_IPURGE(){

if(is_file('/usr/sbin/ipurge')){return '/usr/sbin/ipurge';}
if(is_file('/usr/lib/cyrus/bin/ipurge')){return '/usr/lib/cyrus/bin/ipurge';}
if(is_file('/usr/lib/cyrus-imapd/ipurge')){return '/usr/lib/cyrus-imapd/ipurge';}
$unix=new unix();
return $unix->find_program("ipurge");
}
//#########################################################################################



function WRITE_CYRUS_CONF(){
$sock=new sockets();
$unix=new unix();
$cyrus=new cyrus_conf();
$users=new usersMenus();
$provide_uuid=null;
$CyrusEnableSquatter=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CyrusEnableSquatter"));
$CyrusSquatterRindex=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CyrusSquatterRindex"));
$CyrusSquatterRindexEveryDay=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CyrusSquatterRindexEveryDay"));

$nice_path=$unix->EXEC_NICE();
$EnableVirtualDomainsInMailBoxes=intval($sock->GET_INFO('EnableVirtualDomainsInMailBoxes'));
$CyrusEnableBackendMurder=intval($sock->GET_INFO('CyrusEnableBackendMurder'));
$CyrusEnableImapMurderedFrontEnd=intval($sock->GET_INFO('CyrusEnableImapMurderedFrontEnd'));
$CyrusClusterID=intval($sock->GET_INFO('CyrusClusterID'));
$EnableCyrusReplicaCluster=intval($sock->GET_INFO('EnableCyrusReplicaCluster'));
$CyrusEnableiPurge=intval($sock->GET_INFO('CyrusEnableiPurge'));
$ListenAddress=$cyrus->main_array["CYRUS"]['ListenAddress'];
$DisableIMAPVerif=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableIMAPVerif"));
$EnableCyrusMasterCluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCyrusMasterCluster"));
$CyrusiPurgeSent=$sock->GET_INFO("CyrusEnableSquatter");
$CyrusiPurgeJunk=$sock->GET_INFO("CyrusiPurgeJunk");
$CyrusiPurgeTrash=$sock->GET_INFO("CyrusiPurgeTrash");
$SieveListenIp=trim($sock->GET_INFO('SieveListenIp'));
$CyrusLMTPListen=$sock->GET_INFO('CyrusLMTPListen');

if(!is_file('/etc/artica-postfix/settings/Daemons/CyrusClusterReplicaInfos') ){ $EnableCyrusMasterCluster=0;}

if($CyrusEnableImapMurderedFrontEnd==1){ 
	if($EnableCyrusReplicaCluster==1){ $EnableCyrusReplicaCluster=0; } 
	if(!is_file('/etc/artica-postfix/settings/Daemons/CyrusMurderBackendServer') ){$CyrusEnableImapMurderedFrontEnd=0; }
}


$CyrusEnableLMTPUnix=$sock->GET_INFO('CyrusEnableLMTPUnix');
if(!is_numeric($CyrusEnableLMTPUnix)){$CyrusEnableLMTPUnix=1;}

if(!is_numeric($cyrus->main_array["CYRUS"]["service_lmtp_listen"])){$cyrus->main_array["CYRUS"]["service_lmtp_listen"]=2005;}
if(!is_numeric($cyrus->main_array["CYRUS"]["service_lmtp_enabed"])){$cyrus->main_array["CYRUS"]["service_lmtp_enabed"]=1;}


if($cyrus->main_array["CYRUS"]["service_lmtp_enabed"]==1){
	if($ListenAddress<>null){$CyrusLMTPListen="$CyrusLMTPListen:{$cyrus->main_array["CYRUS"]["service_lmtp_listen"]}";}else{
		$CyrusLMTPListen="127.0.0.1:{$cyrus->main_array["CYRUS"]["service_lmtp_listen"]}";
	}
}



if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} LMTP Unix.....................: $CyrusEnableLMTPUnix / $CyrusLMTPListen\n";}

if($CyrusSquatterRindex==0){$CyrusSquatterRindex=120;}
if(!is_numeric($CyrusiPurgeSent)){$CyrusiPurgeSent=1;}
if(!is_numeric($CyrusiPurgeJunk)){$CyrusiPurgeJunk=1;}
if(!is_numeric($CyrusiPurgeTrash)){$CyrusiPurgeTrash=1;}


$Zarafa_installed=$users->ZARAFA_INSTALLED;
$CyrusLMTPListenPattern=null;

if(!is_file(LOCATE_CYRUS_SQUATTER() ) ){ $CyrusEnableSquatter=0;}


if(preg_match("#(.+?):([0-9]+)#", $CyrusLMTPListen)){
	$CyrusLMTPListenPattern=$CyrusLMTPListen;
	
}else{
	$CyrusLMTPListenPattern="127.0.0.1:2005";
}



if(!is_file('/etc/artica-postfix/settings/Daemons/ArticaImapIniConf')){
	$fcf[]='[CYRUS]';
	$fcf[]='service_imap_enabed=1';
	$fcf[]='service_imap_listen=imap';
	$fcf[]='service_imapssl_enabed=0';
	$fcf[]='service_imapssl_listen=imaps';
	$fcf[]='service_pop3_enabed=1';
	$fcf[]='service_pop3_listen=pop3';
	$fcf[]='service_pop3ssl_enabed=0';
	$fcf[]='service_pop3ssl_listen=pop3s';
	$fcf[]='service_nntpd_enabed=0';
	$fcf[]='service_nntpd_listen=nntp';
	$fcf[]='service_nntpds_enabed=0';
	$fcf[]='service_nntpds_listen=nntps';
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaImapIniConf", @implode("\n", $fcf));
	$cyrus=new cyrus_conf();
}


$service_imap_maxchild=intval($cyrus->main_array["CYRUS"]['service_imap_maxchild']);
$service_imapssl_enabed=intval($cyrus->main_array["CYRUS"]['service_imapssl_enabed']);
$service_pop3_enabed=intval($cyrus->main_array["CYRUS"]['service_pop3_enabed']);
$service_nntpd_enabed=intval($cyrus->main_array["CYRUS"]['service_nntpd_enabed']);
$service_pop3ssl_enabed=intval($cyrus->main_array["CYRUS"]['service_pop3ssl_enabed']);
$service_nntpds_enabed=intval($cyrus->main_array["CYRUS"]['service_nntpds_enabed']);

$service_imapssl_listen=$cyrus->main_array["CYRUS"]['service_imapssl_listen'];
$service_imapssl_maxchild=intval($cyrus->main_array["CYRUS"]['service_imapssl_maxchild']);


$service_pop3_listen=$cyrus->main_array['CYRUS']['service_pop3_listen'];
$service_pop3_maxchild=intval($cyrus->main_array["CYRUS"]['service_pop3_maxchild']);

$service_pop3ssl_listen=$cyrus->main_array['CYRUS']['service_pop3ssl_listen'];
$service_pop3ssl_maxchild=intval($cyrus->main_array["CYRUS"]['service_pop3ssl_maxchild']);

$service_nntpd_listen=$cyrus->main_array['CYRUS']['service_nntpd_listen'];
$service_nntpds_listen=$cyrus->main_array['CYRUS']['service_nntpds_listen'];


if($service_imap_maxchild==0){$service_imap_maxchild=500;}
if($service_imapssl_maxchild==0){$service_imapssl_maxchild=500;}
if($service_pop3_maxchild==0){$service_pop3_maxchild=150;}
if($service_pop3ssl_maxchild==0){$service_pop3ssl_maxchild=150;}

if($service_imapssl_listen==null){$service_imapssl_listen="imaps";}
if($service_pop3_listen==null){$service_pop3_listen="pop3";}
if($service_pop3ssl_listen==null){$service_pop3ssl_listen="pop3s";}

if($service_nntpd_listen==null){$service_nntpd_listen="nntp";}
if($service_nntpds_listen==null){$service_nntpds_listen="nntps";}

if($SieveListenIp<>null){
	if($SieveListenIp<>'127.0.0.1'){
			$SieveListenIp="$SieveListenIp:sieve";
	}else{$SieveListenIp='';}
}



@mkdir('/var/lib/cyrus/srvtab',0755,true);
@mkdir('/var/lib/cyrus/db',0755,true);
@mkdir('/var/spool/cyrus/mail',0755,true);
@mkdir('/var/spool/cyrus/news',0755,true);
@mkdir('/var/run/cyrus/socket',0755,true);
@mkdir('/var/lib/cyrus/socket',0755,true);
@mkdir('/var/lib/cyrus/proc',0755,true);
@mkdir('/var/run/cyrus/socket',0755,true);


shell_exec('/bin/chmod 750 /var/run/cyrus');
shell_exec('/bin/chmod -R 755 /var/lib/cyrus');
shell_exec('/bin/chown -R cyrus:mail /var/lib/cyrus >/dev/null 2>&1');
shell_exec('/bin/chown -R cyrus:mail /var/run/cyrus >/dev/null 2>&1');


$ctl_cyrusdb=ctl_cyrusdb_path();
$cyr_expire=cyr_expire_path();
$tls_prune=tls_prune_path();
$basepath=dirname(CYRUS_POP3D_BIN_PATH());

$imapd='imapd';
$pop3d='pop3d';
$nntpd='nntpd';
$lmtpd='lmtpd';

if(strlen($ListenAddress)>2) {$ListenAddress=$ListenAddress.':';}

if($CyrusEnableImapMurderedFrontEnd==1){
	$imapd='proxyd';
	$pop3d='pop3proxyd';
	$lmtpd='lmtpproxyd';
}

if($CyrusClusterID>0){
	$provide_uuid=" provide_uuid=$CyrusClusterID";
}



$MAIN[]='';
$MAIN[]='START {';
$MAIN[]="	recover		cmd=\"$ctl_cyrusdb -r\"";
$MAIN[]="	delprune	cmd=\"$cyr_expire -E 3\"";
$MAIN[]="	tlsprune	cmd=\"$tls_prune\"";
if($CyrusEnableBackendMurder==1){
	$MAIN[]='	mupdatepush     cmd="'.LOCATE_ctl_mboxlist().' -m"';
}

if($EnableCyrusMasterCluster==1){
	if(is_file(CYRUS_SYNC_CLIENT_BIN_PATH() ) ){
	$MAIN[]='syncclient       cmd="'+CYRUS_SYNC_CLIENT_BIN_PATH()+' -r"';
	}
}


if($DisableIMAPVerif==1){ $Zarafa_installed=false; }
$MAIN[]='}';
$MAIN[]='';
$MAIN[]='SERVICES {';

$listen='imap';
if($Zarafa_installed){$listen='1143';}
$MAIN[]="\timap		cmd=\"$basepath/imapd -U 30\" listen=\"$ListenAddress$listen\" prefork=0 maxchild=$service_imap_maxchild";

if($service_imapssl_enabed==1){
	if($Zarafa_installed){$service_imapssl_listen='1993';}
	$MAIN[]="\timaps		cmd=\"$basepath/imapd -s -U 30\" listen=\"$ListenAddress$service_imapssl_listen\" prefork=0 maxchild=$service_imapssl_maxchild $provide_uuid";
}

if($service_pop3_enabed==1){
	if($Zarafa_installed){$service_pop3_listen='1110';}
	$MAIN[]="\tpop3		cmd=\"$basepath/pop3d -U 30\" listen=\"$ListenAddress$service_pop3_listen\" prefork=0 maxchild=$service_pop3_maxchild";
}

if($service_pop3ssl_enabed==1){
	if($Zarafa_installed){$service_pop3ssl_listen='1995';}
	$MAIN[]="\tpop3s		cmd=\"$basepath/pop3d -s -U 30\" listen=\"$ListenAddress$service_pop3ssl_listen\" prefork=0 maxchild=$service_pop3ssl_maxchild";
}
if($service_nntpd_enabed==1){
	$MAIN[]="\tnntp		cmd=\"$basepath/nntpd+' -U 30\" listen=\"$ListenAddress$service_nntpd_listen\" prefork=0 maxchild=100 $provide_uuid";
}

if($service_nntpds_enabed==1){
	$MAIN[]="\tnntps		cmd=\"$basepath/nntpd -s -U 30\" listen=\"$ListenAddress$service_nntpds_listen\" prefork=0 maxchild=100 $provide_uuid";
}

if($CyrusEnableLMTPUnix==1){
$MAIN[]="\tlmtpunix	cmd=\"$basepath/lmtpd\" listen=\"/var/spool/postfix/var/run/cyrus/socket/lmtp\" prefork=0 maxchild=20 $provide_uuid";
}
if($CyrusLMTPListenPattern<>null){
	$MAIN[]="\tlmtp	cmd=\"$basepath/lmtpd\" listen=\"$CyrusLMTPListenPattern\" prefork=0 maxchild=20 $provide_uuid";
}


if(is_file( sieved_path() ) ){
	$MAIN[]='	sieve		cmd="'.sieved_path().'" listen="localhost:sieve" prefork=0 maxchild=100';
	if(strlen($SieveListenIp)>0){$MAIN[]='  	sieveremote		cmd="'.sieved_path().'" listen="'.$SieveListenIp.'" prefork=0 maxchild=100'; }
}

if(is_file( notify_path() ) ){
	$MAIN[]='	notify		cmd="'.notify_path().'" listen="/var/run/cyrus/socket/notify" proto="udp" prefork=1';
}

if($CyrusEnableBackendMurder==1){
	$MAIN[]="     mupdate         cmd=\"$basepath/mupdate -m -C /etc/imap-murder.conf\" listen=3905 prefork=1";
}

if(CyrusEnableImapMurderedFrontEnd==1){
	$MAIN[]='     mupdate         cmd="mupdate" listen=3905 prefork=1';
}

if(EnableCyrusReplicaCluster==1){
	$MAIN[]='     syncserver       cmd="'.CYRUS_SYNC_SERVER_BIN_PATH().'" listen="csync"';
}

$MAIN[]='}';
$MAIN[]='';
$MAIN[]='EVENTS {';
$MAIN[]='	checkpoint	cmd="' . $ctl_cyrusdb . ' -c" period=30';
$MAIN[]='	delprune	cmd="' . $cyr_expire  . ' -E 3" at=0401';
$MAIN[]='	tlsprune	cmd="' . $tls_prune   . '" at=0401';

$LOCATE_CYRUS_SQUATTER=LOCATE_CYRUS_SQUATTER();


if($CyrusEnableSquatter==1){
	if($CyrusSquatterRindexEveryDay==0){
			$MAIN[]="	squatter      cmd=\"$nice_path $LOCATE_CYRUS_SQUATTER -s -r user\" period=$CyrusSquatterRindex";
	}
	
	if($CyrusSquatterRindexEveryDay==1){
		$CyrusSquatterRindexUseScheduleTime=$sock->GET_INFO("CyrusSquatterRindexUseScheduleTime");
		if(preg_match("#([0-9]+):([0-9]+)#", $CyrusSquatterRindexUseScheduleTime)){
			$MAIN[]="\tsquatter      cmd=\"$nice_path $LOCATE_CYRUS_SQUATTER -s -r user\" at=$CyrusSquatterRindexUseScheduleTime";
		}
	}

}


if($CyrusEnableiPurge==1){
	$LOCATE_CYRUS_IPURGE=LOCATE_CYRUS_IPURGE();
	$CyrusiPurgeDays=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CyrusiPurgeDays"));
	$CyrusiPurgeTime=$sock->GET_INFO("CyrusiPurgeTime");
	if($CyrusiPurgeTrash==1){$MAIN[]="	purgetrash        cmd=\"$LOCATE_CYRUS_IPURGE -f -d $CyrusiPurgeDays */Trash\" at=$CyrusiPurgeTime";}
	if($CyrusiPurgeJunk==1){$MAIN[]="	purgejunk         cmd=\"$LOCATE_CYRUS_IPURGE -f -d $CyrusiPurgeDays */Junk\" at=$CyrusiPurgeTime";}
	if($CyrusiPurgeSent==1){$MAIN[]="	purgesent         cmd=\"$LOCATE_CYRUS_IPURGE -f -d $CyrusiPurgeDays */Sent\" at=$CyrusiPurgeTime";}
}



$MAIN[]='';
$MAIN[]='}';
$MAIN[]='';

@file_put_contents("/etc/cyrus.conf", @implode("\n", $MAIN));

}
//##############################################################################

//#############################################################################
?>