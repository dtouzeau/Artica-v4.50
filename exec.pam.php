<?php

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.lvm.org.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.groups.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--ldap"){ldap_conf();exit;}



function build(){
	$sock=new sockets();	
	$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
	$KerbAuthDisableNsswitch=$sock->GET_INFO("KerbAuthDisableNsswitch");
	$nsswitchEnableLdap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nsswitchEnableLdap"));
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if(!is_numeric($KerbAuthDisableNsswitch)){$KerbAuthDisableNsswitch=0;}		
	$EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
	$WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/nsswitchEnableWinbind")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("nsswitchEnableWinbind", 0);}
	$nsswitchEnableWinbind=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nsswitchEnableWinbind"));
	
	
	$unix=new unix();
	$winbindd=$unix->find_program("winbindd");
	if(is_file($winbindd)){
		if($EnableKerbAuth==1){
			if($WindowsActiveDirectoryKerberos==0){
			$EnableSambaActiveDirectory=1;
			}
		}
	}
	
	if($KerbAuthDisableNsswitch==1){$EnableSambaActiveDirectory=0;}
	
	if($EnableIntelCeleron==1){
		$EnableSambaActiveDirectory=0;
		$nsswitchEnableLdap=0;
	}
	if($nsswitchEnableWinbind==0){$EnableSambaActiveDirectory=0;}
	
	if($EnableSambaActiveDirectory==0){echo "Starting......: ".date("H:i:s")." pam.d, ActiveDirectory is disabled\n";}else{echo "Starting......: ".date("H:i:s")." pam.d, ActiveDirectory is Enabled\n";}

	
	
	
	$f[]="@include common-auth";
	$f[]="@include common-account";
	$f[]="@include common-session";
	
	@file_put_contents("/etc/pam.d/samba", @implode("\n", $f));
	echo "Starting......: ".date("H:i:s")." pam.d, \"/etc/pam.d/samba\" done\n";
	unset($f);

	
	
	
	
	
	if(is_file("/etc/pam.d/common-account")){
		 if($EnableSambaActiveDirectory==1){ 
		 	if(SearchLibrarySecurity("pam_winbind.so")){$f[]="account sufficient       pam_winbind.so";}
		 }
	

		 if($nsswitchEnableLdap==1){ 
			if($EnableOpenLDAP){$f[]="account sufficient pam_ldap.so";}
		 	$f[]="account required   pam_unix.so try_first_pass";
		 }else{
		 	$f[]="account	[success=1 new_authtok_reqd=done default=ignore]	pam_unix.so";
		 	$f[]="account	requisite			pam_deny.so";
		 	$f[]="account	required			pam_permit.so";
		 }
		 $f[]="";
		 @file_put_contents("/etc/pam.d/common-account", @implode("\n", $f)); 
		 echo "Starting......: ".date("H:i:s")." pam.d, \"/etc/pam.d/common-account\" done\n";
		 unset($f);
		}
//------------------------------------------------------------------------------------------------------------------- 
	 if(is_file("/etc/pam.d/common-auth")){
		  if($EnableSambaActiveDirectory==1){ 
		  	if(SearchLibrarySecurity("pam_winbind.so")){$f[]="auth sufficient pam_winbind.so";}
		  } 
		  	
		if($nsswitchEnableLdap==1){
			if($EnableOpenLDAP){$f[]="auth sufficient pam_ldap.so";}
		 }
		 $f[]="auth	requisite	pam_unix.so nullok_secure try_first_pass";		 
		 
		 if($nsswitchEnableLdap==0){
		 	
		  $f[]="auth	[success=1 default=ignore]	pam_unix.so nullok_secure";
		  $f[]="auth	requisite			pam_deny.so";
		  $f[]="auth	required			pam_permit.so";
		  if(SearchLibrarySecurity("pam_cap.so")){$f[]="auth	optional			pam_cap.so";}
		 }
		 if($EnableSambaActiveDirectory==1){
			 if(SearchLibrarySecurity("pam_smbpass.so")){
			 	$f[]="auth	optional	pam_smbpass.so migrate";
			 }
		 }


		 $f[]="";
		 @file_put_contents("/etc/pam.d/common-auth", @implode("\n", $f)); 
		 echo "Starting......: ".date("H:i:s")." pam.d, \"/etc/pam.d/common-auth\" done\n";
		 unset($f); 
	 }
	 //------------------------------------------------------------------------------------------------------------------- 
 
	 $f[]="#%PAM-1.0";
	 $f[]="";
	 $f[]="#@include common-auth";
	 $f[]="#@include common-account";
	 $f[]="auth    sufficient      pam_unix.so ";
	 $f[]="auth    required        pam_unix.so";
	 $f[]="session required pam_permit.so";
	 $f[]="session required pam_limits.so";
	 $f[]="";
	 @file_put_contents("/etc/pam.d/sudo", @implode("\n", $f)); 
	 echo "Starting......: ".date("H:i:s")." pam.d, \"/etc/pam.d/sudo\" done\n";
	 unset($f); 
	 //-------------------------------------------------------------------------------------------------------------------

	if(is_file("/etc/pam.d/common-password")){

		
		echo "Starting......: ".date("H:i:s")." pam.d,nsswitchEnableLdap=$nsswitchEnableLdap\n";
		
		 $f[]="#";
		 $f[]="# /etc/pam.d/common-password - password-related modules common to all services";
		 if($EnableSambaActiveDirectory==1){ $f[]="password        [success=1 default=ignore]      pam_winbind.so use_authtok try_first_pass";}
		 if($nsswitchEnableLdap==1){
		 	if($EnableOpenLDAP==1){
				$f[]="password\tsufficient\tpam_ldap.so";
		 		$f[]="password\trequisite\tpam_unix.so nullok obscure md5 try_first_pass";
		 	}
		 }else{
		 	$f[]="password	[success=1 default=ignore]	pam_unix.so obscure sha512";
		 	$f[]="password	requisite			pam_deny.so";
		 	$f[]="password	required			pam_permit.so";
		 	
			
		 	
		 }
		 
		 if($EnableSambaActiveDirectory==1){
			  if(SearchLibrarySecurity("pam_smbpass.so")){
				$f[]="password   optional   pam_smbpass.so nullok use_authtok use_first_pass";
			  }
		 }
		 $f[]="";
		 @file_put_contents("/etc/pam.d/common-password", @implode("\n", $f)); 
		 echo "Starting......: ".date("H:i:s")." pam.d, \"/etc/pam.d/common-password\" done\n";
		 unset($f); 
	}
	//-------------------------------------------------------------------------------------------------------------------
	if(is_file("/etc/pam.d/common-session")){
		$f[]="session	required			pam_unix.so ";
		if($EnableSambaActiveDirectory==1){
			if(SearchLibrarySecurity("pam_krb5.so")){$f[]="session	optional			pam_krb5.so minimum_uid=1000";}
			if(SearchLibrarySecurity("pam_winbind.so")){$f[]="session	optional			pam_winbind.so ";}
		}
		
		if($nsswitchEnableLdap==1){
			if($EnableOpenLDAP==1){$f[]="session	optional			pam_ldap.so";}
		}else{
			$f[]="session	[default=1]			pam_permit.so";
			$f[]="session	requisite			pam_deny.so";
			$f[]="session	required			pam_permit.so";
			$f[]="session	required	pam_unix.so";
			if(SearchLibrarySecurity("pam_ck_connector.so")){$f[]="session	optional			pam_ck_connector.so nox11";}
			
		}
		$f[]="";
		 @file_put_contents("/etc/pam.d/common-session", @implode("\n", $f)); 
		 echo "Starting......: ".date("H:i:s")." pam.d, \"/etc/pam.d/common-session\" done\n";
		 unset($f); 
	}	
	//-------------------------------------------------------------------------------------------------------------------	
	
	if(is_file("/etc/pam.d/common-session-noninteractive")){
		$f[]="session	[default=1]			pam_permit.so";
		$f[]="session	requisite			pam_deny.so";
		$f[]="session	required			pam_permit.so";
		$f[]="session	required			pam_unix.so";
		if($nsswitchEnableLdap==1){$f[]="session	optional			pam_ldap.so ";}
		if($EnableSambaActiveDirectory==1){ $f[]="session	optional			pam_winbind.so";}
		
		$f[]="";
		@file_put_contents("/etc/pam.d/common-session-noninteractive", @implode("\n", $f));
		echo "Starting......: ".date("H:i:s")." pam.d, \"/etc/pam.d/common-session\" done\n";
		unset($f);
		
	}
	//-------------------------------------------------------------------------------------------------------------------	
	
	if(is_file("/etc/pam.d/system-auth-ac")){
		$f[]="#%PAM-1.0";
		$f[]="# This file is auto-generated.";
		$f[]="# User changes will be destroyed the next time authconfig is run.";
		$f[]="auth        required      pam_env.so";
		$f[]="auth        sufficient    pam_unix.so nullok try_first_pass";
		$f[]="auth        requisite     pam_succeed_if.so uid >= 500 quiet";
		if($nsswitchEnableLdap==1){if($EnableOpenLDAP==1){$f[]="auth        sufficient    pam_ldap.so use_first_pass";}}
		if($EnableSambaActiveDirectory==1){ $f[]="auth        sufficient    pam_winbind.so use_first_pass";}
		$f[]="auth        required      pam_deny.so";
		$f[]="";
		$f[]="account     required      pam_unix.so";
		$f[]="account     sufficient    pam_succeed_if.so uid < 500 quiet";
		if($nsswitchEnableLdap==1){if($EnableOpenLDAP==1){$f[]="account     sufficient    pam_ldap.so use_first_pass";}}
		if($EnableSambaActiveDirectory==1){ $f[]="account     sufficient    pam_winbind.so use_first_pass";}
		$f[]="account     required      pam_permit.so";
		$f[]="";
		$f[]="password    requisite     pam_cracklib.so try_first_pass retry=3";
		$f[]="password    sufficient    pam_unix.so md5 shadow nullok try_first_pass use_authtok";
		if($nsswitchEnableLdap==1){if($EnableOpenLDAP==1){$f[]="password    sufficient    pam_ldap.so use_first_pass";}}
		if($EnableSambaActiveDirectory==1){ $f[]="password    sufficient    pam_winbind.so use_first_pass";}
		$f[]="password    required      pam_deny.so";
		$f[]="";
		$f[]="session     optional      pam_keyinit.so revoke";
		$f[]="session     required      pam_limits.so";
		$f[]="session     [success=1 default=ignore] pam_succeed_if.so service in crond quiet use_uid";
		if($nsswitchEnableLdap==1){if($EnableOpenLDAP==1){$f[]="session     optional      pam_ldap.so use_first_pass";}}
		if($EnableSambaActiveDirectory==1){ $f[]="session     optional      pam_winbind.so use_first_pass";}
		if(SearchLibrarySecurity("pam_mkhomedir.so")){ $f[]="session     required      pam_mkhomedir.so skel=/etc/skel/ umask=0022";}
		$f[]="session     required      pam_unix.so";
		$f[]="";	
		 @file_put_contents("/etc/pam.d/system-auth-ac", @implode("\n", $f)); 
		 echo "Starting......: ".date("H:i:s")." pam.d, \"/etc/pam.d/system-auth-ac\" done\n";
		 unset($f); 	
	}
	
	@unlink("/etc/libnss-ldap.conf");
	@unlink("/etc/ldap.secret");
	@unlink("/etc/libnss-ldap.secret");
	
	if($nsswitchEnableLdap==1){
		if($EnableOpenLDAP==1){
			ldap_conf(true);
		}
	}
	

}
function ifispam_mkhomedir(){
	if(is_file("/lib/x86_64-linux-gnu/security/pam_mkhomedir.so")){return true;}
	if(is_file("/lib/security/pam_mkhomedir.so")){return true;}
	if(is_file("/lib/i386-linux-gnu/security/pam_mkhomedir.so")){return true;}
	echo "Starting......: ".date("H:i:s")." pam.d, pam_mkhomedir.so no such file\n";
	return false;
	
}

function SearchLibrarySecurity($filename){
	if(is_file("/lib64/security/$filename")){return true;}
	if(is_file("/lib/security/$filename")){return true;}
	if(is_file("/lib/x86_64-linux-gnu/security/$filename")){return true;}
	if(is_file("/lib/security/$filename")){return true;}
	if(is_file("/lib/i386-linux-gnu/security/$filename")){return true;}	
	echo "Starting......: ".date("H:i:s")." pam.d $filename, no such library\n";
	
}
function ldap_conf($aspid=false){
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$MYPID_FILE="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$aspid){
		$pid=$unix->get_pid_from_file($MYPID_FILE);
		if($unix->process_exists($pid,basename(__FILE__))){
			$pidtime=$unix->PROCCESS_TIME_MIN($pid);
			echo "slapd: [INFO] Artica task already running pid $pid since {$pidtime}mn\n";
			if($pidtime>10){
				echo "slapd: [INFO] Killing this Artica task...\n";
				unix_system_kill_force($pid);
			}
			else{
				exit();
			}
		}


	}
	@unlink($MYPID_FILE);
	@file_put_contents($MYPID_FILE, getmypid());
	
	
	$EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
	if($EnableOpenLDAP==0){
		@unlink("/etc/libnss-ldap.conf");
		@unlink("/etc/ldap.secret");
		@unlink("/etc/libnss-ldap.secret");
		return;
	}

	$ASLOCAL=false;
	$admin=@file_get_contents("/etc/artica-postfix/ldap_settings/admin");
	$password=@file_get_contents("/etc/artica-postfix/ldap_settings/password");
	$port=@file_get_contents("/etc/artica-postfix/ldap_settings/port");
	$server=@file_get_contents("/etc/artica-postfix/ldap_settings/server");
	$suffix=@file_get_contents("/etc/artica-postfix/ldap_settings/suffix");
	$chmod=$unix->find_program("chmod");
	if($server==null){$server="127.0.0.1";}
	if($server=="localhost"){$server="127.0.0.1";}
	if(!is_numeric($port)){$port=389;}
	$binddn="cn=$admin,$suffix";
	echo "Starting......: ".date("H:i:s")." pam.d LDAP slapd set system to $server:$port/$suffix\n";
	echo "Starting......: ".date("H:i:s")." pam.d LDAP slapd set root DN $binddn\n";
	
	$ARRAY=$unix->ldap_GET_CONFS();
	@file_put_contents("/usr/share/artica-postfix/ressources/local_ldap.php", "<?php \$GLOBALS[\"MAIN_LOCAL_LDAP_SETTINGS\"]=\"".base64_encode(serialize($ARRAY))."\";?>");
	@chmod("/usr/share/artica-postfix/ressources/local_ldap.php",0755);


	if($server<>"127.0.0.1"){
		$fp = @fsockopen($server, $port, $errno, $errstr, 2);
		if(!$fp){
			xsyslog("$errno $errstr Return to local ldap server");
			echo "Starting......: ".date("H:i:s")." pam.d LDAP $errno $errstr\n";
			echo "Starting......: ".date("H:i:s")." pam.d LDAP Return to local ldap server\n";
			$server="127.0.0.1";
			$port=389;
			$binddn=$ARRAY["DN"];
			$password=$ARRAY["PWD"];
			$suffix=$ARRAY["SUFFIX"];
			echo "Starting......: ".date("H:i:s")." pam.d LDAP set system to $server:$port/$suffix\n";
			echo "Starting......: ".date("H:i:s")." pam.d LDAP set root DN $binddn\n";
		}
	}
	
	$ldap_uri="ldap://$server:$port/";
	if($server=="127.0.0.1"){
		$ASLOCAL=true;
		$ldap_uri="ldapi://". urlencode("/var/run/slapd/slapd.sock");
	}

	if(!$ASLOCAL){
		$f[]="host $server";
		$f[]="port $port";
	}
	$f[]="uri $ldap_uri";
	$f[]="ldap_version 3";
	$f[]="binddn $binddn";
	$f[]="rootbinddn $binddn";
	$f[]="bindpw $password";
	$f[]="bind_policy soft";
	$f[]="scope sub";
	$f[]="base $suffix";
	$f[]="pam_password clear";
	$f[]="pam_lookup_policy yes";
	$f[]="pam_filter objectclass=posixAccount";
	$f[]="pam_login_attribute uid";
	$f[]="nss_reconnect_maxconntries 5";
	$f[]="idle_timelimit 3600";
	$f[]="nss_base_group $suffix?sub";
	$f[]="nss_base_passwd $suffix?sub";
	$f[]="nss_base_shadow $suffix?sub";
//	$f[]="debug 255";
	$f[]="";

	@file_put_contents("/etc/ldap.secret", "$password");
	@file_put_contents("/etc/libnss-ldap.secret", $password);
	@chmod("/etc/libnss-ldap.secret", 0600);
	
	shell_exec("$chmod 0600 /etc/ldap.secret >/dev/null 2>&1");
	echo "Starting......: ".date("H:i:s")." pam.d LDAP /etc/ldap.secret, success...\n";
	@file_put_contents("/etc/pam_ldap.conf", @implode("\n", $f));
	@file_put_contents("/etc/nss_ldap.conf", @implode("\n", $f));

	echo "Starting......: ".date("H:i:s")." pam.d LDAP /etc/pam_ldap.conf, success...\n";
	echo "Starting......: ".date("H:i:s")." pam.d LDAP /etc/nss_ldap.conf, success...\n";

	if(is_dir('/usr/share/libnss-ldap')){
		@file_put_contents("/usr/share/libnss-ldap/ldap.conf", @implode("\n", $f));
		echo "Starting......: ".date("H:i:s")." pam.d LDAP /usr/share/libnss-ldap/ldap.conf, success...\n";
	}
	if(is_dir('/etc/openldap')){
		@file_put_contents("/etc/openldap/ldap.conf", @implode("\n", $f));
		echo "Starting......: ".date("H:i:s")." pam.d LDAP /etc/openldap/ldap.conf, success...\n";
	}

	
	echo "Starting......: ".date("H:i:s")." pam.d LDAP Suffix....: $suffix\n";
			$f[]="## Your LDAP server. Must be resolvable without using LDAP.";
			$f[]="# Multiple hosts may be specified, each separated by a ";
			$f[]="# space. How long nss_ldap takes to failover depends on";
			$f[]="# whether your LDAP client library supports configurable";
			$f[]="# network or connect timeouts (see bind_timelimit).";
			if(!$ASLOCAL){
				$f[]="host $server";
				$f[]="port $port";
			}
			$f[]="base $suffix";
			$f[]="";
			$f[]="# Another way to specify your LDAP server is to provide an";
			$f[]="#uri ldap://127.0.0.1/";
			$f[]="# Unix Domain Sockets to connect to a local LDAP Server.";
			$f[]="#uri ldap://127.0.0.1/";
			$f[]="#uri ldaps://127.0.0.1/   ";
			$f[]="uri $ldap_uri";
			$f[]="# Note: %2f encodes the '/' used as directory separator";
			$f[]="";
			$f[]="# The LDAP version to use (defaults to 3";
			$f[]="# if supported by client library)";
			$f[]="ldap_version 3";
			$f[]="";
			$f[]="# The distinguished name to bind to the server with.";
			$f[]="# Optional: default is to bind anonymously.";
			$f[]="# Please do not put double quotes around it as they";
			$f[]="# would be included literally.";
			$f[]="binddn $binddn";
			$f[]="";
			$f[]="# The credentials to bind with. ";
			$f[]="# Optional: default is no credential.";
			$f[]="bindpw $password";
			$f[]="";
			$f[]="# The distinguished name to bind to the server with";
			$f[]="# if the effective user ID is root. Password is";
			$f[]="# stored in /etc/libnss-ldap.secret (mode 600)";
			$f[]="# Use 'echo -n \"mypassword\" > /etc/libnss-ldap.secret' instead";
			$f[]="# of an editor to create the file.";
			$f[]="rootbinddn $binddn";
			$f[]="";

			
			$f[]="";
			$f[]="# The search scope.";
			$f[]="scope sub";
			$f[]="#scope one";
			$f[]="#scope base";
			$f[]="";
			$f[]="# Search timelimit";
			$f[]="#timelimit 30";
			$f[]="";
			$f[]="# Bind/connect timelimit";
			$f[]="#bind_timelimit 30";
			$f[]="";
			$f[]="# Reconnect policy:";
			$f[]="#  hard_open: reconnect to DSA with exponential backoff if";
			$f[]="#             opening connection failed";
			$f[]="#  hard_init: reconnect to DSA with exponential backoff if";
			$f[]="#             initializing connection failed";
			$f[]="#  hard:      alias for hard_open";
			$f[]="#  soft:      return immediately on server failure";
			$f[]="#bind_policy hard";
			$f[]="";
			$f[]="# Connection policy:";
			$f[]="#  persist:   DSA connections are kept open (default)";
			$f[]="#  oneshot:   DSA connections destroyed after request";
			$f[]="#nss_connect_policy persist";
			$f[]="";
			$f[]="# Idle timelimit; client will close connections";
			$f[]="# (nss_ldap only) if the server has not been contacted";
			$f[]="# for the number of seconds specified below.";
			$f[]="#idle_timelimit 3600";
			$f[]="";
			$f[]="# Use paged rseults";
			$f[]="#nss_paged_results yes";
			$f[]="";
			$f[]="# Pagesize: when paged results enable, used to set the";
			$f[]="# pagesize to a custom value";
			$f[]="#pagesize 1000";
			$f[]="";
			$f[]="# Filter to AND with uid=%s";
			$f[]="pam_filter objectclass=posixAccount";
			$f[]="";
			$f[]="# The user ID attribute (defaults to uid)";
			$f[]="pam_login_attribute uid";
			$f[]="";
			$f[]="# Search the root DSE for the password policy (works";
			$f[]="# with Netscape Directory Server)";
			$f[]="#pam_lookup_policy yes";
			$f[]="";
			$f[]="# Check the 'host' attribute for access control";
			$f[]="# Default is no; if set to yes, and user has no";
			$f[]="# value for the host attribute, and pam_ldap is";
			$f[]="# configured for account management (authorization)";
			$f[]="# then the user will not be allowed to login.";
			$f[]="#pam_check_host_attr yes";
			$f[]="";
			$f[]="# Check the 'authorizedService' attribute for access";
			$f[]="# control";
			$f[]="# Default is no; if set to yes, and the user has no";
			$f[]="# value for the authorizedService attribute, and";
			$f[]="# pam_ldap is configured for account management";
			$f[]="# (authorization) then the user will not be allowed";
			$f[]="# to login.";
			$f[]="#pam_check_service_attr yes";
			$f[]="";
			$f[]="# Group to enforce membership of";
			$f[]="#pam_groupdn cn=PAM,ou=Groups,dc=padl,dc=com";
			$f[]="";
			$f[]="# Group member attribute";
			$f[]="#pam_member_attribute uniquemember";
			$f[]="";
			$f[]="# Specify a minium or maximum UID number allowed";
			$f[]="pam_min_uid 1";
			$f[]="#pam_max_uid 0";
			$f[]="";
			$f[]="# Template login attribute, default template user";
			$f[]="# (can be overriden by value of former attribute";
			$f[]="# in user's entry)";
			$f[]="#pam_login_attribute userPrincipalName";
			$f[]="pam_template_login_attribute uid";
			$f[]="#pam_template_login nobody";
			$f[]="";
			$f[]="# HEADS UP: the pam_crypt, pam_nds_passwd,";
			$f[]="# and pam_ad_passwd options are no";
			$f[]="# longer supported.";
			$f[]="#";
			$f[]="# Do not hash the password at all; presume";
			$f[]="# the directory server will do it, if";
			$f[]="# necessary. This is the default.";
			$f[]="#pam_password clear";
			$f[]="";
			$f[]="# Hash password locally; required for University of";
			$f[]="# Michigan LDAP server, and works with Netscape";
			$f[]="# Directory Server if you're using the UNIX-Crypt";
			$f[]="# hash mechanism and not using the NT Synchronization";
			$f[]="# service. ";
			$f[]="#pam_password crypt";
			$f[]="";
			$f[]="# Remove old password first, then update in";
			$f[]="# cleartext. Necessary for use with Novell";
			$f[]="# Directory Services (NDS)";
			$f[]="#pam_password nds";
			$f[]="";
			$f[]="# RACF is an alias for the above. For use with";
			$f[]="# IBM RACF";
			$f[]="#pam_password racf";
			$f[]="";
			$f[]="# Update Active Directory password, by";
			$f[]="# creating Unicode password and updating";
			$f[]="# unicodePwd attribute.";
			$f[]="#pam_password ad";
			$f[]="";
			$f[]="# Use the OpenLDAP password change";
			$f[]="# extended operation to update the password.";
			$f[]="#pam_password exop";
			$f[]="";
			$f[]="# Redirect users to a URL or somesuch on password";
			$f[]="# changes.";
			$f[]="#pam_password_prohibit_message Please visit http://internal to change your password.";
			$f[]="";
			$f[]="# Use backlinks for answering initgroups()";
			$f[]="#nss_initgroups backlink";
			$f[]="";
			$f[]="# Enable support for RFC2307bis (distinguished names in group";
			$f[]="# members)";
			$f[]="#nss_schema rfc2307bis";
			$f[]="";
			$f[]="# RFC2307bis naming contexts";
			$f[]="# Syntax:";
			$f[]="# nss_base_XXX		base?scope?filter";
			$f[]="# where scope is {base,one,sub}";
			$f[]="# and filter is a filter to be &'d with the";
			$f[]="# default filter.";
			$f[]="# You can omit the suffix eg:";
			$f[]="# nss_base_passwd	ou=People,";
			$f[]="# to append the default base DN but this";
			$f[]="# may incur a small performance impact.";
			$f[]="#nss_base_passwd	ou=People,dc=padl,dc=com?one";
			$f[]="#nss_base_shadow	ou=People,dc=padl,dc=com?one";
			$f[]="#nss_base_group		ou=Group,dc=padl,dc=com?one";
			$f[]="#nss_base_hosts		ou=Hosts,dc=padl,dc=com?one";
			$f[]="#nss_base_services	ou=Services,dc=padl,dc=com?one";
			$f[]="#nss_base_networks	ou=Networks,dc=padl,dc=com?one";
			$f[]="#nss_base_protocols	ou=Protocols,dc=padl,dc=com?one";
			$f[]="#nss_base_rpc		ou=Rpc,dc=padl,dc=com?one";
			$f[]="#nss_base_ethers	ou=Ethers,dc=padl,dc=com?one";
			$f[]="#nss_base_netmasks	ou=Networks,dc=padl,dc=com?ne";
			$f[]="#nss_base_bootparams	ou=Ethers,dc=padl,dc=com?one";
			$f[]="#nss_base_aliases	ou=Aliases,dc=padl,dc=com?one";
			$f[]="#nss_base_netgroup	ou=Netgroup,dc=padl,dc=com?one";
			$f[]="";
			$f[]="# attribute/objectclass mapping";
			$f[]="# Syntax:";
			$f[]="#nss_map_attribute	rfc2307attribute	mapped_attribute";
			$f[]="#nss_map_objectclass	rfc2307objectclass	mapped_objectclass";
			$f[]="";
			$f[]="# configure --enable-nds is no longer supported.";
			$f[]="# NDS mappings";
			$f[]="#nss_map_attribute uniqueMember member";
			$f[]="";
			$f[]="# Services for UNIX 3.5 mappings";
			$f[]="#nss_map_objectclass posixAccount User";
			$f[]="#nss_map_objectclass shadowAccount User";
			$f[]="#nss_map_attribute uid msSFU30Name";
			$f[]="#nss_map_attribute uniqueMember msSFU30PosixMember";
			$f[]="#nss_map_attribute userPassword msSFU30Password";
			$f[]="#nss_map_attribute homeDirectory msSFU30HomeDirectory";
			$f[]="#nss_map_attribute homeDirectory msSFUHomeDirectory";
			$f[]="#nss_map_objectclass posixGroup Group";
			$f[]="#pam_login_attribute msSFU30Name";
			$f[]="#pam_filter objectclass=User";
			$f[]="#pam_password ad";
			$f[]="";
			$f[]="# configure --enable-mssfu-schema is no longer supported.";
			$f[]="# Services for UNIX 2.0 mappings";
			$f[]="#nss_map_objectclass posixAccount User";
			$f[]="#nss_map_objectclass shadowAccount user";
			$f[]="#nss_map_attribute uid msSFUName";
			$f[]="#nss_map_attribute uniqueMember posixMember";
			$f[]="#nss_map_attribute userPassword msSFUPassword";
			$f[]="#nss_map_attribute homeDirectory msSFUHomeDirectory";
			$f[]="#nss_map_attribute shadowLastChange pwdLastSet";
			$f[]="#nss_map_objectclass posixGroup Group";
			$f[]="#nss_map_attribute cn msSFUName";
			$f[]="#pam_login_attribute msSFUName";
			$f[]="#pam_filter objectclass=User";
			$f[]="#pam_password ad";
			$f[]="";
			$f[]="# RFC 2307 (AD) mappings";
			$f[]="#nss_map_objectclass posixAccount user";
			$f[]="#nss_map_objectclass shadowAccount user";
			$f[]="#nss_map_attribute uid sAMAccountName";
			$f[]="#nss_map_attribute homeDirectory unixHomeDirectory";
			$f[]="#nss_map_attribute shadowLastChange pwdLastSet";
			$f[]="#nss_map_objectclass posixGroup group";
			$f[]="#nss_map_attribute uniqueMember member";
			$f[]="#pam_login_attribute sAMAccountName";
			$f[]="#pam_filter objectclass=User";
			$f[]="#pam_password ad";
			$f[]="";
			$f[]="# configure --enable-authpassword is no longer supported";
			$f[]="# AuthPassword mappings";
			$f[]="#nss_map_attribute userPassword authPassword";
			$f[]="";
			$f[]="# AIX SecureWay mappings";
			$f[]="#nss_map_objectclass posixAccount aixAccount";
			$f[]="#nss_base_passwd ou=aixaccount,?one";
			$f[]="#nss_map_attribute uid userName";
			$f[]="#nss_map_attribute gidNumber gid";
			$f[]="#nss_map_attribute uidNumber uid";
			$f[]="#nss_map_attribute userPassword passwordChar";
			$f[]="#nss_map_objectclass posixGroup aixAccessGroup";
			$f[]="#nss_base_group ou=aixgroup,?one";
			$f[]="#nss_map_attribute cn groupName";
			$f[]="#nss_map_attribute uniqueMember member";
			$f[]="#pam_login_attribute userName";
			$f[]="#pam_filter objectclass=aixAccount";
			$f[]="#pam_password clear";
			$f[]="";
			$f[]="# For pre-RFC2307bis automount schema";
			$f[]="#nss_map_objectclass automountMap nisMap";
			$f[]="#nss_map_attribute automountMapName nisMapName";
			$f[]="#nss_map_objectclass automount nisObject";
			$f[]="#nss_map_attribute automountKey cn";
			$f[]="#nss_map_attribute automountInformation nisMapEntry";
			$f[]="";
			$f[]="# Netscape SDK LDAPS";
			$f[]="#ssl on";
			$f[]="";
			$f[]="# Netscape SDK SSL options";
			$f[]="#sslpath /etc/ssl/certs";
			$f[]="";
			$f[]="# OpenLDAP SSL mechanism";
			$f[]="# start_tls mechanism uses the normal LDAP port, LDAPS typically 636";
			$f[]="#ssl start_tls";
			$f[]="#ssl on";
			$f[]="";
			$f[]="# OpenLDAP SSL options";
			$f[]="# Require and verify server certificate (yes/no)";
			$f[]="# Default is to use libldap's default behavior, which can be configured in";
			$f[]="# /etc/openldap/ldap.conf using the TLS_REQCERT setting.  The default for";
			$f[]="# OpenLDAP 2.0 and earlier is \"no\", for 2.1 and later is \"yes\".";
			$f[]="#tls_checkpeer yes";
			$f[]="";
			$f[]="# CA certificates for server certificate verification";
			$f[]="# At least one of these are required if tls_checkpeer is \"yes\"";
			$f[]="#tls_cacertfile /etc/ssl/ca.cert";
			$f[]="#tls_cacertdir /etc/ssl/certs";
			$f[]="";
			$f[]="# Seed the PRNG if /dev/urandom is not provided";
			$f[]="#tls_randfile /var/run/egd-pool";
			$f[]="";
			$f[]="# SSL cipher suite";
			$f[]="# See man ciphers for syntax";
			$f[]="#tls_ciphers TLSv1";
			$f[]="";
			$f[]="# Client certificate and key";
			$f[]="# Use these, if your server requires client authentication.";
			$f[]="#tls_cert";
			$f[]="#tls_key";
			$f[]="";
			$f[]="# Disable SASL security layers. This is needed for AD.";
			$f[]="#sasl_secprops maxssf=0";
			$f[]="";
			$f[]="# Override the default Kerberos ticket cache location.";
			$f[]="#krb5_ccname FILE:/etc/.ldapcache";
			$f[]="";
			@file_put_contents("/etc/libnss-ldap.conf", @implode("\n", $f));
			echo "Starting......: ".date("H:i:s")." pam.d /etc/libnss-ldap.conf done\n";	

}

