#!/usr/bin/php
<?php

if(is_file("/etc/artica-postfix/FROM_ISO")){$GLOBALS["PHP5_BIN_PATH"]="/usr/bin/php5";}
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["MONIT"]=false;
$GLOBALS["BY_FRAMEWORK"]=null;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--monit#",implode(" ",$argv))){$GLOBALS["MONIT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--framework=(.+?)$#",implode(" ",$argv),$re)){$GLOBALS["BY_FRAMEWORK"]=$re[1];}
if($GLOBALS["VERBOSE"]){echo "Starting in verbose mode\n";}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.opendlap.certificates.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");


xstart();


function Debuglogs($text){
	echo $text."\n";
}

function xstart(){
	$modulepath="/usr/lib/ldap";
	$SCHEMA_PATH="/etc/ldap/schema";
	$unix=new unix();
	$EnableRemoteAddressBook=0;
	$EnablePerUserRemoteAddressBook=0;
	$NoLDAPBackMonitor=0;
	$LockLdapConfig=0;
	
	$LockLdapConfig=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockLdapConfig"));
	
	$EnableRemoteAddressBook=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRemoteAddressBook"));
	$EnablePerUserRemoteAddressBook=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePerUserRemoteAddressBook"));
    $OpenLDAPLogLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPLogLevel"));
	$EnableLDAPSyncProv=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProv"));
	$EnableLDAPSyncProvClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLDAPSyncProvClient"));
	$NoLDAPBackMonitor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoLDAPBackMonitor"));
	$SlapdThreads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SlapdThreads"));
	$EnableOpenLdapProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLdapProxy"));
	$LdapDBCachesize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapDBCachesize"));
    $OpenLDAPEnableSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPEnableSSL"));
    $OpenLDAPCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPCertificate"));
	$php=$unix->LOCATE_PHP5_BIN();
	$addgroup=$unix->find_program("addgroup");
	if($LdapDBCachesize<500){$LdapDBCachesize=1000;}
    $LdapListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapListenInterface"));
    if($LdapListenInterface==null){$LdapListenInterface="lo";}

    $f=array();
    $f[]="# Replication enabled: $EnableLDAPSyncProv";
    $f[]="# Listen interface: $LdapListenInterface";
	
	if($LockLdapConfig==1){
		Debuglogs('slapd: [INFO] server configuration locked...');
		Debuglogs('slapd: [INFO] server set LockLdapConfig to 0 if you want artica to modify configuration');
		exit;
	}
	
	if(is_file('/etc/artica-postfix/no-ldap-change')){
		Debuglogs('slapd: [INFO] server skip auto-change ldap configuration...');
		exit;
	}
	
	
	$unix->CreateUnixUser("openldap","openldap","OpenLDAP User");
	@mkdir("/var/lib/ldap",0755,true);
	@mkdir("/var/run/slapd",0755,true);
	
	SET_DB_CONFIG();
	
	if(is_dir('/etc/openldap/slapd.d')){
		Debuglogs('slapd: [INFO] removing content of /etc/openldap/slapd.d');
		system('/bin/rm -rf /etc/openldap/slapd.d');
	}
	
	if(is_dir('/etc/ldap/slapd.d')){
		Debuglogs('slapd: [INFO] removing content of /etc/ldap/slapd.d');
		system('/bin/rm -rf /etc/ldap/slapd.d');
	}
	
	if($EnableOpenLdapProxy==1){
		system("$php /usr/share/artica-postfix/exec.ldap.php --proxy");
		if(is_file('/etc/artica-postfix/proxy.slpad.conf')){
			$OpenLDAPProxyContent=@file_get_contents('/etc/artica-postfix/proxy.slpad.conf');
		}else{
			$EnableOpenLdapProxy=0;		
		}
	}

    $LDAPSyncProvID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvID"));
    if($LDAPSyncProvID==0){$LDAPSyncProvID=1;}
    if($LDAPSyncProvID>999){$LDAPSyncProvID=999;}
	$SyncProvUserDN=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyncProvUserDN"));
	$LDAPSyncProvClientServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientServer"));
	$LDAPSyncProvClientSearchBase=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientSearchBase"));
	$LDAPSyncProvClientBindDN=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientBindDN"));
	$LDAPSyncProvClientBindPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAPSyncProvClientBindPassword"));
	
	if($EnableLDAPSyncProvClient==1){
		if($LDAPSyncProvClientServer==null){$EnableLDAPSyncProvClient=0;}
		if($LDAPSyncProvClientSearchBase==null){$EnableLDAPSyncProvClient=0;}
		if($LDAPSyncProvClientBindDN==null){$EnableLDAPSyncProvClient=0;}
	}
	if(strlen($LDAPSyncProvID)==1){

    }
    if(strlen($LDAPSyncProvID)==2){

    }


	system("$addgroup nvram >/dev/null 2>&1");
	Debuglogs('slapd: [INFO] writing new configuration...');
	
	
	$artica_admin=trim(@file_get_contents("/etc/artica-postfix/ldap_settings/admin"));
	$artica_password=trim(@file_get_contents("/etc/artica-postfix/ldap_settings/password"));
	$ldap_suffix=trim(@file_get_contents("/etc/artica-postfix/ldap_settings/suffix"));
	$artica_password_cmd=$unix->shellEscapeChars($artica_password);
	
	if(is_file('/usr/bin/smbpasswd')){
		Debuglogs('slapd: [INFO] set password in secret.tdb');
		system('/usr/bin/smbpasswd -w '.$artica_password_cmd);
	}
	
	
	if($ldap_suffix==null){
		$ldap_suffix='dc=my-domain,dc=com';
		@file_put_contents("/etc/artica-postfix/ldap_settings/suffix", $ldap_suffix);
	}
	
	
	if($artica_password==null){
		$artica_password='secret';
		@file_put_contents("/etc/artica-postfix/ldap_settings/password", $artica_password);
	}
	
	if($artica_admin==null){
		$artica_admin='Manager';
		@file_put_contents("/etc/artica-postfix/ldap_settings/admin", $artica_admin);
	}
	 
	
	Debuglogs('SAVE_SLAPD_CONF() set permission for openldap');
	system('/bin/chown -R openldap /var/lib/ldap');
	system('/bin/chown -R openldap /var/run/slapd');
	system("/bin/chown -R openldap $SCHEMA_PATH");
	



	if($SlapdThreads>1){ $f[]="threads $SlapdThreads";}
	$f[]='pidfile         /var/run/slapd/slapd.pid';
	
	$f[]='';
	$f[]='#Artica schemas added';
	if(is_file('$SCHEMA_PATH/rfc2307bis.schema')){
		system('/bin/mv $SCHEMA_PATH/rfc2307bis.schema $SCHEMA_PATH/rfc2307bis.schema.mv');
	}
	
	$schemas[]='core.schema';
	$schemas[]='cosine.schema';
	$schemas[]='mod_vhost_ldap.schema';
	$schemas[]='nis.schema';
	$schemas[]='inetorgperson.schema';
	$schemas[]='evolutionperson.schema';
	$schemas[]='postfix.schema';
	$schemas[]='dhcp.schema';
	$schemas[]='samba.schema';
	$schemas[]='ISPEnv.schema';
	$schemas[]='mozilla-thunderbird.schema';
	$schemas[]='officeperson.schema';
	$schemas[]='pureftpd.schema';
	$schemas[]='joomla.schema';
	$schemas[]='autofs.schema';
	$schemas[]='dnsdomain2.schema';
	$schemas[]='zarafa.schema';
	
	
	foreach ($schemas as $xchem){
		if(is_file("$SCHEMA_PATH/$xchem")){
			$f[]="include         $SCHEMA_PATH/$xchem";
		}else{
			$f[]="# Missing $SCHEMA_PATH/$xchem";
		}
	}
	

	
	$f[]='';
	
	$dyngroup=false;
	$back_hdb="$modulepath/back_hdb.la";
	$dynlist_path="$modulepath/dynlist.la";
	$back_monitor="$modulepath/back_monitor.so";
	$syncprov="$modulepath/syncprov.so";
	
	
	if($NoLDAPBackMonitor==1){$back_monitor='';}
    if($EnableLDAPSyncProv==1) {
        if (is_file($syncprov)) {
            if ($SyncProvUserDN == null) {
                $f[]="# SyncProvUserDN is null.(aborting)";
                $EnableLDAPSyncProv = 0;
            }
        }
    }
	if(is_file($dynlist_path)){$dyngroup=true;}
	
	
	if( $dyngroup){
		if(is_file("$SCHEMA_PATH/dyngroup.schema")){
			$f[]="include         $SCHEMA_PATH/dyngroup.schema";
		}
	}
	
	
	
	$f[]='';
	$f[]='argsfile        /var/run/slapd/slapd.args';
	
	if($OpenLDAPLogLevel<>0){
		$f[]="loglevel       $OpenLDAPLogLevel";
	}else{
		$f[]='loglevel        0';
	}
	
	if(is_file($back_monitor)){$f[]='moduleload'.chr(9).'back_monitor';}
	if(is_file($back_hdb)){$f[]='moduleload'.chr(9).'back_hdb';}

    if($EnableLDAPSyncProv==1){$f[]='# Loading modules for replication';}
	if($EnableLDAPSyncProv==1){$f[]='moduleload'.chr(9).'syncprov';}
	if($EnableLDAPSyncProv==1){$f[]='# ******* LDAP Proxy Specific modules;';}
	if($EnableLDAPSyncProv==1){$f[]='moduleload'.chr(9).'back_meta';}
	if($EnableLDAPSyncProv==1){$f[]='moduleload'.chr(9).'back_ldap';}
	if($EnableLDAPSyncProv==1){$f[]='moduleload'.chr(9).'rwm';}
	if($EnableLDAPSyncProv==1){$f[]='backend'.chr(9).'hdb';}
	 
	$f[]='sizelimit 500';
	$f[]='tool-threads 1';
	if($EnableOpenLdapProxy==1){
		$f[]='# ******* LDAP Proxy parameters';
		$f[]=$OpenLDAPProxyContent;
		$f[]='';
	}
	
	$f[]='# ******* Main Database parameters';
	
	if(is_file($back_monitor)){$f[]='database'.chr(9).'monitor';}
	if(is_file($back_hdb)){$f[]='database'.chr(9).'hdb';}
	if(!is_file($back_hdb)){$f[]='database'.chr(9).'bdb';}
	$f[]="suffix            \"$ldap_suffix\"";
	$f[]="rootdn            \"cn=$artica_admin,$ldap_suffix\"";
	$f[]="rootpw            $artica_password";
	$f[]='';
	$f[]='directory         /var/lib/ldap';
	$f[]="cachesize         $LdapDBCachesize";
	$f[]='dbconfig          set_lk_max_objects 1500';
	$f[]='dbconfig          set_lk_max_locks 1500';
	$f[]='dbconfig          set_lk_max_lockers 1500';
    $f[]='';
    $f[]="# Using SSL = $OpenLDAPEnableSSL, with certificate '$OpenLDAPCertificate'";
	if($OpenLDAPEnableSSL==1){
        if($OpenLDAPCertificate<>null){
            $openldap_ssl=new openldap_ssl($OpenLDAPCertificate);
            $openldap_ssl->build();
            $f[]="TLSCertificateKeyFile {$openldap_ssl->private_key_path}";
            $f[]="TLSCertificateFile {$openldap_ssl->certificate_path}";
        }
    }

	$f[]="";
	$f[]='index objectClass                       eq,pres';
	$f[]='index ou,cn,mail,surname,givenname      eq,pres,sub';
	$f[]='index uniqueMember,mailAlias,associatedDomain,ComputerIP,ComputerMacAddress    eq,pres';
	$f[]='index uidNumber,gidNumber,memberUid,uid eq,pres';
	$f[]='index entryUUID,entryCSN                eq';
	$f[]='index aRecord            pres,eq';
	if(is_file("$SCHEMA_PATH/dhcp.schema")){
		$f[]='index dhcpHWAddress                     eq';
		$f[]='index dhcpClassData                     eq';
	}
	$f[]='';
	$f[]='lastmod         on';
	$f[]='checkpoint      512 30';
	//$f[]='secure tls=0';
	$f[]='';
	
	
	
	// ******** SyncProv server mode ***********
	if($EnableLDAPSyncProv==1){
		$f[]='overlay syncprov';
		$f[]='syncprov-checkpoint 100 10';
		$f[]="serverID\t$LDAPSyncProvID";
		$f[]='';
	}
	
	if($EnableLDAPSyncProvClient==1){
		$f[]='';
		$f[]='';
		$f[]='syncrepl rid=001';
		$f[]="   provider=ldap://$LDAPSyncProvClientServer";
		$f[]='   type=refreshAndPersist';
		$f[]='   retry="5 10 300 +"';
		$f[]="   searchbase=\"$LDAPSyncProvClientSearchBase\"";
		$f[]='   bindmethod=simple';
		// $f[]='   starttls=critical';
		$f[]="	binddn=\"$LDAPSyncProvClientBindDN\"";
		$f[]="	credentials=\"$LDAPSyncProvClientBindPassword\"";
		//$f[]="	updateref       ldap://$LDAPSyncProvClientServer";
		
		$f[]='';
		$f[]='';
	}
	
	$f[]='access to dn.base="'.$ldap_suffix.'"';
	$f[]=' by * read';
	$f[]='';
	if($EnableLDAPSyncProv==1){
		$f[]='access to dn.base="cn=Subschema"';
		$f[]=' by dn="'.$SyncProvUserDN.'" write';
		$f[]='';
	}
	
	$f[]='access to attrs=userPassword,sambaNTPassword,sambaLMPassword,sambaPwdLastSet,shadowLastChange,gecos,sambaPWDMustChange,MailboxSecurityParameters';
	$f[]=' by peername.ip=127.0.0.1 write';
	if($EnableLDAPSyncProv==1){$f[]=' by dn="'.$SyncProvUserDN.'" read';}
	$f[]=' by anonymous auth';
	$f[]=' by self write';
	$f[]=' by * none';
	$f[]='';
	
	if($EnableRemoteAddressBook==1){
		Debuglogs('slapd: [INFO] Enable Remote Address Book ...');
		$f[]='access to dn.regex="(cn=.*,)?ou=users,ou=.+?,dc=organizations,'.$ldap_suffix.'"';
		$f[]=' by anonymous read';
		$f[]=' by * none';
		$f[]='';
		
		$f[]='access to dn.regex="(cn=.*,)?ou=groups,ou=.+?,dc=organizations,'.$ldap_suffix.'"';
		$f[]=' by anonymous read';
		$f[]=' by * none';
	}
	
	
	$f[]='access to dn.subtree="ou=mounts,'.$ldap_suffix.'"';
	$f[]=' by peername.ip=127.0.0.1 write';
	$f[]=' by users write';
	$f[]=' by anonymous read';
	$f[]=' by * none';
	$f[]='';
	
	
	$f[]='access to dn.subtree="'.$ldap_suffix.'"';
	$f[]=' by peername.ip=127.0.0.1 write';
	$f[]=' by self write';
	$f[]=' by users write ';
	$f[]=' by anonymous auth';
	if($EnableLDAPSyncProv==1){$f[]=' by dn="'.$SyncProvUserDN.'" read';}
	$f[]=' by * none';
	$f[]='';
	
	
	
	$f[]='access to attrs=userPassword,shadowLastChange';
	$f[]=' by anonymous auth';
	$f[]=' by self write';
	$f[]=' by peername.ip=127.0.0.1 write';
	if($EnableLDAPSyncProv==1){$f[]=' by dn="'.$SyncProvUserDN.'" read';}
	$f[]=' by * none';
	$f[]='';
	
	$LdapAclsPlus=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapAclsPlus"));
	
	if($EnablePerUserRemoteAddressBook==1){
		if($LdapAclsPlus<>null){$f[]="$LdapAclsPlus";}
	
	}
	
	
	
	
	
	
	$f[]='';
	$f[]='password-hash {CLEARTEXT}';
	$f[]='monitoring off';
/*	
	if($OpenLDAPDisableSSL==0){
		$f[]='';
		$f[]='TLSCACertificateFile /etc/ssl/certs/openldap/ca.crt';
		$f[]='TLSCertificateFile /etc/ssl/certs/openldap/ldap.crt';
		$f[]='TLSCertificateKeyFile /etc/ssl/certs/openldap/ldap.key';
		$f[]='TLSVerifyClient never';
	
	}
	
*/
	$f[]='';
	
	@file_put_contents("/etc/ldap/slapd.conf", @implode("\n", $f));
	
	
}

function SET_DB_CONFIG(){
	if(is_file('/etc/artica-postfix/no-ldap-change')){return;}
	@mkdir('/var/lib/ldap',0755,true);
	$LdapDBSetCachesize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapDBSetCachesize"));
	if($LdapDBSetCachesize==0){$LdapDBSetCachesize=5120000;}
	$f[]="set_lk_max_objects 1500";
	$f[]="set_lk_max_locks 1500";
	$f[]="set_lk_max_lockers 1500";
	$f[]="set_flags DB_LOG_AUTOREMOVE";
	$f[]="set_cachesize 0 $LdapDBSetCachesize 1";
	DebugLogs('slapd: [INFO] server writing DB_CONFIG');
	@file_put_contents("/var/lib/ldap/DB_CONFIG", @implode("\n", $f));
}
//##############################################################################
