<?php
if(!defined("alse")){define("alse",null);}
class settings_inc {
    public $TreePage=array("domains.edit.group.php"=>true,"samba.index.php"=>true);
	public $ArchStruct=0;
	public $menus;
	public $main_left_menus=array();
	public $menusright;
	public $toplinks;
	public $priv;
	public $MAIN_CONSOLE_PORT=9000;
	public $EnablePerUserRemoteAddressBook=0;
	public $APACHE_INSTALLED=false;
	public $AllowChangeDomains=false;
	public $AsSystemAdministrator=false;
	public $AsOrgStorageAdministrator=false;
	public $AllowXapianDownload=false;
	public $AllowAddGroup=false;
	public $AllowChangeKav=false;
	public $AllowChangeKas=false;
	public $AllowEditAsWbl=false;
	public $AllowEditOuSecurity=false;	
	public $AsArticaAdministrator=false;
	public $AsSambaAdministrator=false;
	public $AsMailManAdministrator=false;
	public $AsPostfixAdministrator=false;
	public $AsSquidAdministrator=false;
	public $AsMailBoxAdministrator=false;
	public $AsDansGuardianGroupRule=false;
	public $AsDnsAdministrator=false;
	public $AsQuarantineAdministrator=false;
	public $AllowFetchMails=false;
	public $AllowSenderCanonical=false;
	public $AllowChangeUserPassword=false;
	public $AllowDansGuardianBanned=false;
	public $AllowChangeUserKas=false;
	public $AllowEditAliases=false;
	public $AllowEmailing=false;
	public $AllowOpenVPN=false;
	public $NETATALK_INSTALLED=false;
	public $AsAnAdministratorGeneric=false;
	public $AllowAddUsers=false;
	public $AllowViewStatistics=false;
	public $AllowChangeAntiSpamSettings=false;
	public $AsOrgAdmin=false;
	public $AsInventoryAdmin=false;
	public $AseMailCampaignsAdmin=false;
	public $uid=null;
	public $groupid=-900;
	public $IptablesEnabled;
	public $KAVMILTER_ENABLED;
	public $lighttpd_installed;
	public $spamassassin_installed=false;
	public $spamassassin_version=0;
	public $fetchmail_configured=false;
	public $LOCAL_SID;
	public $EnableFetchmail=0;
	public $EnableFDMFetch=0;
	public $P3ScanEnabled=0;
	public $RoundCubeHTTPEngineEnabled=0;
	public $roundcube_intversion=0;
	public $roundcube_plugins=null;
	public $template_user_addr;
	public $disks_size=null;
	public $cpumhz=null;
	public $preload_installed=false;
	public $smbmount_installed=false;
	public $collectd_installed=false;
	public $EnableVirtualDomainsInMailBoxes=false;
	public $CLAMD_DATABASE_PATH="";
	public $COLLECTD_RRD=array();
	public $LoadModulesEnabledParsed=false;
	public $dar_installed=false;
	public $GNARWL_INSTALLED=false;
	public $AllowChangeMailBoxRules=false;
	public $cyrus_ldap_admin_password="";
	public $CLAMD_CONF_PATH="";
	public $PFLOGSUMM_INSTALLED=false;
	public $maillog_path;
	public $spamassassin_ipcountry=false;
	public $CUPS_INSTALLED=false;
	
	public $SARG_INSTALLED=false;
	public $OPENGOO_INSTALLED=false;
	public $APACHE_GROUPWARE_INSTALLED=false;
	public $POSTFIX_QUEUE=array();
	public $LinuxDistriCode=null;

	
	public $cyrus_sync_installed=false;
	public $AllowManageOwnComputers=false;
	public $POLICYD_WEIGHT_PORT;
	public $CRYPTSETUP_INSTALLED=false;
	public $ZABBIX_INSTALLED=true;
	public $phpldapadmin_installed=false;
	public $phpmyadmin_installed=false;
	public $HOSTAPD_INSTALLED=false;
	public $VIRTUALBOX_HOST=false;
	public $APP_VBOXADDINTION_INSTALLED=false;
	public $MHONARC_VERSION="";
	public $FETCHMAIL_STATUS="";
	public $kavmilter_installed=false;
	public $APACHE_MOD_AUTHNZ_LDAP=false;
	public $OPENVPN_APPLIANCE=false;
	public $SIEVE_PORT=0;

	public $APP_KHSE_INSTALLED=false;
	public $MEMCACHED_INSTALLED=false;
	public $HAPROXY_INSTALLED=false;
	public $APP_TOMCAT6_INSTALLED=false;
	public $URLSNARF_INSTALLED=false;
	public $APP_ZARAFADB_INSTALLED=false;
	public $FREERADIUS_INSTALLED=false;
	public $SQUID_REVERSE_APPLIANCE=false;
	/* This licence must be used Only with Artica-for postfix */
	public $ChartLicence="D1XFWGMEW9L.HSK5T4Q79KLYCK07EK";
	/*you cannot use it without any authorizations of maani.us.
	this licence is Copyright 2004-2007, maani.us
	For more information, see
	http://www.maani.us/xml_charts/index.php?menu=License
	*/
	
	
	public $KASPERSKY_WEB_APPLIANCE=false;
	public $mysql_password='';
	public $mysql_admin='root';
	public $mysql_server='127.0.0.1';
	public $ARTICA_FILTER_QUEUE_PATH;
	public $ARTICA_FILTER_MAXSUBQUEUE;
	public $POSTFIX_MAILDROP_PATH;
	public $GLOBAL_CONFIG;
	public $AutomaticConfig=false;
	public $fetchmail_installed=false;
	public $cyrus_imapd_installed=false;
	public $cyrus_lmtp_path='/var/run/cyrus/socket/lmtp';
	public $rrdtool_installed;
	public $procmail_installed=false;	
	public $dnsmasq_installed=false;
	
	public $mailgraph_installed=false;
	public $mailgraph_virus_database;
	public $mailgraph_postfix_database;
	public $queuegraph_installed=false;

	public $LOCATE_AUTHLOG_PATH="";
	public $WIFIDOG_INSTALLED=false;
	
	public $roundcube_installed=false;
	public $awstats_installed=false;
	public $mysql_enabled=true;
	public $aveserver_pattern_date="00000000;0000";
	public $POSTIFX_STATUS="-1;0.0.0;";
	public $KAS_STATUS="-1;0.0.0;";
	public $CYRUS_STATUS="-1;0.0.0;";
	public $KAV_STATUS="-1;0.0.0;";
	public $FETHMAIL_STATUS="-1;0.0.0;";
	public $ARTICA_ROOT_PATH;
	public $ARTICA_VERSION;
	public $ARTICA_FILTER_STATUS=false;
	public $EMAILRELAY_STATUS=false;
	public $EMAILRELAY_INSTALLED=false;
	public $BOGOFILTER_INSTALLED=false;
	public $KAV_MILTER_INSTALLED=false;
	public $MAILMAN_INSTALLED=false;
	public $POSTFIX_INSTALLED=false;
	
	
	public $fetchmail_daemon_logs="";
	public $KAVMILTER_PID;
	public $hotwayd_installed;
	public $SQUID_INSTALLED=false;
	public $SQUID_BIN_PATH="";
	public $VMWARE_HOST=false;
	public $DANSGUARDIAN_INSTALLED=false;
	public $AMAVIS_INSTALLED=false;
	public $AMAVISD_VERSION=null;
	public $DANSGUARDIAN_VERSION=null;
	public $PUREFTP_INSTALLED=false;
	public $PUREFTP_USERS=false;
	public $MILTERGREYLIST_INSTALLED=false;
	public $MAILFROMD_INSTALLED=false;
	public $SQUID_PID;	
	public $fqdn;
	public $hostname;
	public $mysql_port;
	public $SMTP_LEVEL;
	public $ArticaFilterEnabled;
	public $AmavisFilterEnabled;
	public $MilterGreyListEnabled;
	public $EnableMilterSpyDaemon=0;
	public $SQLGreyEnabled;
	public $KasxFilterEnabled;
	
	
	
	public $NTPDEnabled;
	public $MailFromdEnabled;
	public $IPTABLES_INSTALLED;
	public $ApacheArticaEnabled;
	public $SAMBA_INSTALLED;
	
	public $SAMBA_VERSION;
	public $GeoIPDat_Path;
	
	public $POSTFIX_LDAP_COMPLIANCE=false;
	public $MAILFROMD_SOCKET="";
	
	
	public $ClamavMilterEnabled=0;
	public $CLAMAV_MILTER_SOCKET;
	public $CLAMAV_MILTER_INSTALLED=false;
	public $SpamAssMilterEnabled=0;
	public $SPAMASS_MILTER_INSTALLED=false;
	public $SPAMASS_MILTER_SOCKET="";
	public $roundcube_folder="";
	public $phpcgi;
	public $razor_installed=false;
	public $pyzor_installed=false;
	public $razor_config="";
	public $spfmilterEnabled=0;
	public $spfmilter_installed=false;
	public $MIMEDEFANG_INSTALLED=false;
	public $netbiosname;
	public $MIMEDEFANG_SOCKET="";
	public $MimeDefangEnabled;
	public $GRAPHDEFANG_INSTALLED=false;
	public $MHONARC_INSTALLED=alse;
	public $SQUID_VERSION;
	
	
	
	public $stunnel4_installed=false;
	public $DkimFilterEnabled=0;
	public $DKIMFILTER_INSTALLED=false;
	public $DKIMFILTER_SOCKET="";
	
	public $nmap_installed=false;
	public $usb_list=array();
	public $roundcube_mysql_sources;
	public $roundcube_web_folder;
	public $KAV4SAMBA_INSTALLED=false;
	public $KAV4SAMBA_VFS="";
	public $SAMBA_MYSQL_AUDIT=false;
	public $awstats_www_path="";
	public $SQUID_ICAP_ENABLED=false;
	public $LIGHTTPD_LDAP_AUTH=false;
	public $bindrrd_installed=false;
	public $fdm_installed=false;
	public $fdm_cache=false;
	public $p3scan_installed=false;
	public $syslogng_installed=false;
	public $AsDebianSystem=false;
	public $sTunnel4enabled=0;
	public $MailArchiverEnabled=0;
	public $roundcube_version="0.0.0";
	public $EnableMilterBogom=0;
	public $openldap_installed=false;
	public $EnableMysqlFeatures=1;
	public $MILTER_SPY_INSTALLED=false;
	public $EnableAmavisDaemon=0;
	public $imapsync_installed=false;
	public $mailsync_installed=false;
	public $ISOQLOG_INSTALLED=false;
	public $zip_installed=false;
	public $EnableAmavisBackup=0;
	public $OPENLDAP_DYNLIST=false;
	public $EnableRemoteAddressBook=0;
	public $SLPAD_LOCKED=false;
	public $ALTERMIME_INSTALLED=false;
	public $POMMO_INSTALLED=false;
	public $C_ICAP_INSTALLED=false;
	public $C_ICAP_DNSBL=false;
	public $MILTERGREYLIST_SOCKET="";
	public $OPENVPN_INSTALLED=false;
	public $spamassassin_conf_path="/etc/spamassassin/local.cf";

	
	public $XAPIAN_PHP_INSTALLED=false;
	public $INSTANSEARCH_ENABLE=false;
	public $JOOMLA_INSTALLED=false;
	public $DSTAT_INSTALLED=false;
	public $SUGARCRM_INSTALLED=false;
	public $GNUPLOT_PNG=false;
	public $IOSTAT_INSTALLED=false;
	public $IOSTAT_ARRAY=array();
	public $PHP_BIN_PATH=null;	
	public $EXEC_NICE=null;
	public $spamassassin_bin_path=null;
	public $cyr_partition_default=null;
	public $cyr_config_directory=null;
	public $smbclient_installed=false;
	public $cyrus_murder_installed=false;
	public $FETCHMAIL_VERSION=null;
	public $cyrus_sync_client_path=null;
	public $ctl_mboxlist=null;
	public $CURL_PATH=null;
	public $NMAP_PATH=null;
	public $msmtp_installed=false;
	public $nmap_version=null;
	
	
	public $NFS_SERVER_INSTALLED=false;
	public $LVM_INSTALLED=false;
	public $MYSQL_NDB_MGMD_INSTALLED=false;
	public $CPU_NUMBER=0;
	
	public $winexe_installed=false;
	
	public $ASSP_INSTALLED=false;
	public $MEM_HIGER_1G=true;
	public $LMB_LUNDIMATIN_INSTALLED=false;
	
	public $TCP_ARRAY=array();
	
	public $mysqld_version=0;
	public $GLUSTER_INSTALLED=false;
	public $SLAPD_CONF_PATH;
	public $cyrus_squatter_exists=false;
	public $cyrus_ipurge_exists=false;
	public $SCANNED_ONLY_INSTALLED=false;
	public $POSTFIX_MULTI=false;
	public $AsOrgPostfixAdministrator=false;
	public $AsMessagingOrg=false;
	public $MEM_TOTAL_INSTALLEE=0;
	public $KASPERSKY_SMTP_APPLIANCE=false;
	public $POSTFILTER_INSTALLED=false;
	public $VMWARE_TOOLS_INSTALLED=false;
	public $SQUIDGUARD_INSTALLED=false;
	public $SQUIDGUARD_BINVER=0;
	public $SQUIDGUARD_BIN_PATH="";
	public $RSYNC_INSTALLED=false;
	public $ZARAFA_APPLIANCE=false;
	
	public $mysqld_datadir;
	
	public $MAIL_DKIM_VERSION=null;
	public $HDPARM_INSTALLED=false;
	
	
	
	public $OCS_LNX_AGENT_INSTALLED=false;
	public $POSTFIX_PCRE_COMPLIANCE=false;
	public $APP_AUDITD_INSTALLED=false;
	public $DKFILTER_INSTALLED=false;
	public $OPENDKIM_INSTALLED=false;
	public $DB_RECOVER_INSTALLED=false;
	public $APP_UFDBGUARD_INSTALLED=false;
	public $ufdbgclient_path=null;
	public $roundcube_subscriptions_option=false;
	public $ADZAPPER_INSTALLED=false;
	public $KINIT_INSTALLED=false;
	public $DKIMPROXY_INSTALLED=false;
	public $MILTER_DKIM_INSTALLED=false;
	public $DROPBOX_INSTALLED=false;
	public $VIRTUALBOX_INSTALLED=false;
	public $AsVirtualBoxManager=false;
	
	public $offlineimap_installed=false;
	public $THINSTATION_INSTALLED=false;
	public $OPENSSH_VER=0;
	public $cyrus_syncserver_installed=false;
	public $APP_SQUIDCLAMAV_INSTALLED=false;
	public $squidclamav_path="";
	public $PEAR_PACKAGES=array();
	public $RACOON_INSTALLED=false;
	public $IPSEC_INSTALLED=false;
	public $PPTPD_INSTALLED=false;
	public $PPTP_INSTALLED=false;
	
	
	
	public $qos_tools_installed=false;
	public $tc_version=0;
	public $LDAP_SYNCPROV=false;
	public $ARTICA_META_ENABLED=false;
	public $PIWIGO_INSTALLED=false;
	public $APP_SABNZBDPLUS_INSTALLED=false;
	public $POSTSCREEN_INSTALLED=false;
	public $KASPERSKY_UPDATE_UTILITY_INSTALLED=false;
	public $deduplication_installed=false;
	
	public $IPSET_INSTALLED=false;
	public $EnableManageUsersTroughActiveDirectory=false;
	public $ActiveDirectoryCredentials;
	public $ASPELL_INSTALLED=false;
	public $MUNIN_CLIENT_INSTALLED=false;
	public $GREYHOLE_INSTALLED=false;
	public $SQUID_CACHMGR=false;
	public $ISCSI_INSTALLED=false;
	public $ISCSI_CLIENT_INSTALLED=false;
	public $ldapClass;
	public $LXC_INSTALLED=false;
	public $AS_VPS_CLIENT=false;
	public $AS_DEBIAN_FAMILY=false;
	public $VLAN_INSTALLED=false;
	public $LOSETUP_INSTALLED=false;

	
	public $Z_PUSH_INSTALLED=false;
    public $IPTABLES_ACCOUNTING_EXISTS=false;
    public $LOAD_BALANCE_APPLIANCE=false;
	public $ARPD_INSTALLED=false;
	public $SQUID_APPLIANCE=false;
	public $PROXYTINY_APPLIANCE=false;
	public $MPM_ITK_MODULE=false;
	public $APACHE_MOD_BW=false;
	public $QUOTA_INSTALLED=false;
	public $MYSQLD_MULTI_INSTALLED=false;
	
	public $IPBAN_INSTALLED=false;
	public $EJABBERD_INSTALLED=false;
	public $PYMSNT_INSTALLED=false;
	public $SSHFS_INSTALLED=false;
	public $APP_ARKEIA_INSTALLED=false;
	public $SAMBA_CLUSTER_SUPPORT=false;
	public $ZARAFA_SEARCH_INSTALLED=false;
	public $LIGHT_INSTALL=false;
	public $CORP_LICENSE=false;
	public $WEBSTATS_APPLIANCE=false;
	public $SURICATA_INSTALLED=false;
	public $ZARAFA_INDEXER_INSTALLED=false;
	public $APP_TOMCAT6_VERSION=null;
	public $APP_JAVA_JDK_INSTALLED=false;
	public $WSGATE_INSTALLED=false;
	public $ETTERCAP_INSTALLED=false;
	public $APACHE_MOD_PYTHON=false;
	public $APP_PYAUTHENNTLM=false;

	public $APP_CHILLI_INSTALLED=false;
	public $ISCSID_INSTALLED=false;
	public $RDPPROXY_INSTALLED=false;
	
	public $SENSORS_INSTALLED=false;

	public $HYPERCACHE_STOREID=false;
	public $XEN_HOST=false;
	public $HYPERV_HOST=false;
	public $WINBINDD_INSTALLED=false;
	
	private function CheckIndexDefaults($_GLOBAL){
		return $_GLOBAL;
		foreach($this as $key => $value) {
			if(!isset($_GLOBAL[$key])){$_GLOBAL[$key]=$value;}
		}
		
		return $_GLOBAL;
		
		
	}
	
	
	function __construct(){
			$_GLOBAL=array();
			if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}
			if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
			
			if($GLOBALS["VERBOSE"]){
					$trace=debug_backtrace();
					if(isset($trace[1])){
						$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";			
						echo "Instanciate userMenus() $called\n";
					}
				}		
		
			
			
			$settings="/usr/share/artica-postfix/ressources/settings.inc";
			if(file_exists($settings)){@include($settings);$this->GLOBAL_CONFIG=$_GLOBAL;$GLOBALS["SETTINGS_FILES"]=$_GLOBAL;}
			if(isset($_GLOBAL["cyrus_ldap_admin_password"])){$this->cyrus_ldap_admin_password=$_GLOBAL["cyrus_ldap_admin_password"];}
			if(isset($_GLOBAL["AutomaticConfig"])){$this->AutomaticConfig=$_GLOBAL["AutomaticConfig"];}
			if(isset($_GLOBAL["imapsync_installed"])){$this->imapsync_installed=$_GLOBAL["imapsync_installed"];}
			if(isset($_GLOBAL["fetchmail_installed"])){$this->fetchmail_installed=$_GLOBAL["fetchmail_installed"];}
			if(isset($_GLOBAL["cyrus_imapd_installed"])){$this->cyrus_imapd_installed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cyrus_imapd_installed"));}
			$this->LOCATE_AUTHLOG_PATH="/var/log/auth.log";
			if(isset($_GLOBAL["cyrus_lmtp_path"])){$this->cyrus_lmtp_path=$_GLOBAL["cyrus_lmtp_path"];}
			if(isset($_GLOBAL["cyr_partition_default"])){$this->cyr_partition_default=$_GLOBAL["cyr_partition_default"];}
			if(isset($_GLOBAL["cyr_config_directory"])){$this->cyr_config_directory=$_GLOBAL["cyr_config_directory"];}
			
			
			if(isset($_GLOBAL["rrdtool_installed"])){$this->rrdtool_installed=$_GLOBAL["rrdtool_installed"];}
			if(isset($_GLOBAL["procmail_installed"])){$this->procmail_installed=$_GLOBAL["procmail_installed"];}
			
			if(isset($_GLOBAL["mailgraph_installed"])){$this->mailgraph_installed=$_GLOBAL["mailgraph_installed"];}
			if(isset($_GLOBAL["mailgraph_virus_database"])){$this->mailgraph_virus_database=$_GLOBAL["mailgraph_virus_database"];}
			if(isset($_GLOBAL["mailgraph_postfix_database"])){$this->mailgraph_postfix_database=$_GLOBAL["mailgraph_postfix_database"];}
			if(isset($_GLOBAL["queuegraph_installed"])){$this->queuegraph_installed=$_GLOBAL["queuegraph_installed"];}

			
			
			
			
			if(isset($_GLOBAL["POSTFIX_STATUS"])){$this->POSTIFX_STATUS=$_GLOBAL["POSTFIX_STATUS"];}
			if(isset($_GLOBAL["KAS_STATUS"])){$this->KAS_STATUS=$_GLOBAL["KAS_STATUS"];}
			if(isset($_GLOBAL["CYRUS_STATUS"])){$this->CYRUS_STATUS=$_GLOBAL["CYRUS_STATUS"];}
			if(isset($_GLOBAL["KAV_STATUS"])){$this->KAV_STATUS=$_GLOBAL["KAV_STATUS"];}
			if(isset($_GLOBAL["FETCHMAIL_STATUS"])){$this->FETCHMAIL_STATUS=$_GLOBAL["FETCHMAIL_STATUS"];}
			if(isset($_GLOBAL["awstats_installed"])){$this->awstats_installed=$_GLOBAL["awstats_installed"];;}
			if(isset($_GLOBAL["BOGOFILTER_INSTALLED"])){$this->BOGOFILTER_INSTALLED=$_GLOBAL["BOGOFILTER_INSTALLED"];;}
			if(isset($_GLOBAL["MAILMAN_INSTALLED"])){$this->MAILMAN_INSTALLED=$_GLOBAL["MAILMAN_INSTALLED"];;}
			if(isset($_GLOBAL["kavmilter_installed"])){$this->KAV_MILTER_INSTALLED=$_GLOBAL["kavmilter_installed"];;}
			if(isset($_GLOBAL["fetchmail_daemon_logs"])){$this->fetchmail_daemon_logs=$_GLOBAL["fetchmail_daemon_logs"];;}
			if(isset($_GLOBAL["ARTICA_ROOT_PATH"])){$this->ARTICA_ROOT_PATH=$_GLOBAL["ARTICA_ROOT_PATH"];;}
			if(isset($_GLOBAL["ARTICA_FILTER_STATUS"])){$this->ARTICA_FILTER_STATUS=$_GLOBAL["ARTICA_FILTER_STATUS"];;}
			if(isset($_GLOBAL["ARTICA_VERSION"])){$this->ARTICA_VERSION=$_GLOBAL["ARTICA_VERSION"];;}
			if(isset($_GLOBAL["hotwayd_installed"])){$this->hotwayd_installed=$_GLOBAL["hotwayd_installed"];;}

            $POSTFIX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
            if($POSTFIX_INSTALLED==1){$this->POSTFIX_INSTALLED=true;}

			if(isset($_GLOBAL["EMAILRELAY_STATUS"])){$this->EMAILRELAY_STATUS=$_GLOBAL["EMAILRELAY_STATUS"];;}
			if(isset($_GLOBAL["EMAILRELAY_INSTALLED"])){$this->EMAILRELAY_INSTALLED=$_GLOBAL["EMAILRELAY_INSTALLED"];;}
			if(isset($_GLOBAL["KAVMILTER_PID"])){$this->KAVMILTER_PID=$_GLOBAL["KAVMILTER_PID"];;}
			

			if(isset($_GLOBAL["SQUID_PID"])){$this->SQUID_PID=$_GLOBAL["SQUID_PID"];;}
			if(isset($_GLOBAL["SQUID_CACHMGR"])){$this->SQUID_CACHMGR=$_GLOBAL["SQUID_CACHMGR"];}
			if(isset($_GLOBAL["QUOTA_INSTALLED"])){$this->QUOTA_INSTALLED=$_GLOBAL["QUOTA_INSTALLED"];;}


			

			if(isset($_GLOBAL["NETATALK_INSTALLED"])){$this->NETATALK_INSTALLED=$_GLOBAL["NETATALK_INSTALLED"];}
			
			if(isset($_GLOBAL["PROXYTINY_APPLIANCE"])){$this->PROXYTINY_APPLIANCE=$_GLOBAL["PROXYTINY_APPLIANCE"];}
			if(isset($_GLOBAL["WSGATE_INSTALLED"])){$this->WSGATE_INSTALLED=$_GLOBAL["WSGATE_INSTALLED"];}
			if(isset($_GLOBAL["URLSNARF_INSTALLED"])){$this->URLSNARF_INSTALLED=$_GLOBAL["URLSNARF_INSTALLED"];}
			if(isset($_GLOBAL["ETTERCAP_INSTALLED"])){$this->ETTERCAP_INSTALLED=$_GLOBAL["ETTERCAP_INSTALLED"];}
			if(isset($_GLOBAL["APP_ZARAFADB_INSTALLED"])){$this->APP_ZARAFADB_INSTALLED=$_GLOBAL["APP_ZARAFADB_INSTALLED"];}
			if(isset($_GLOBAL["APP_CHILLI_INSTALLED"])){$this->APP_CHILLI_INSTALLED=$_GLOBAL["APP_CHILLI_INSTALLED"];}
			if(isset($_GLOBAL["RDPPROXY_INSTALLED"])){$this->RDPPROXY_INSTALLED=$_GLOBAL["RDPPROXY_INSTALLED"];}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFIDOG_INSTALLED"))==1){$this->WIFIDOG_INSTALLED=true;}


			if(is_file("/etc/artica-postfix/MYCOSI_APPLIANCE")){$this->MYCOSI_APPLIANCE=True;}
			if(is_file("/etc/artica-postfix/SQUID_APPLIANCE")){$this->SQUID_APPLIANCE=True;}
			if(is_file("/etc/artica-postfix/LOAD_BALANCE_APPLIANCE")){$this->LOAD_BALANCE_APPLIANCE=True;}
			if(is_file("/etc/artica-postfix/SAMBA_APPLIANCE")){$this->SAMBA_APPLIANCE=True;}
			if(is_file("/etc/artica-postfix/GATEWAY_APPLIANCE")){$this->GATEWAY_APPLIANCE=True;}
			if(is_file("/etc/artica-postfix/ZARAFA_APPLIANCE")){$this->ZARAFA_APPLIANCE=True;}
			
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APACHE_INSTALLED"))==1){$this->APACHE_INSTALLED=true;}
			
			
			if(isset($_GLOBAL["MPM_ITK_MODULE"])){$this->MPM_ITK_MODULE=$_GLOBAL["MPM_ITK_MODULE"];}
			if(isset($_GLOBAL["APACHE_MOD_BW"])){$this->APACHE_MOD_BW=$_GLOBAL["APACHE_MOD_BW"];}
			if(isset($_GLOBAL["MYSQLD_MULTI_INSTALLED"])){$this->MYSQLD_MULTI_INSTALLED=$_GLOBAL["MYSQLD_MULTI_INSTALLED"];}
			if(isset($_GLOBAL["WEBSTATS_APPLIANCE"])){$this->WEBSTATS_APPLIANCE=$_GLOBAL["WEBSTATS_APPLIANCE"];}
			
			
			if(isset($_GLOBAL["DANSGUARDIAN_INSTALLED"])){$this->DANSGUARDIAN_INSTALLED=$_GLOBAL["DANSGUARDIAN_INSTALLED"];}

			if(isset($_SESSION["uid"])){$this->uid=$_SESSION["uid"];}
			if(isset($_SESSION["groupid"])){$this->groupid=$_SESSION["groupid"];}
			
			
			if(isset($_GLOBAL["mysql_port"])){$this->mysql_port=$_GLOBAL["mysql_port"];}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PUREFTP_INSTALLED"))==1){$this->PUREFTP_INSTALLED=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MILTERGREYLIST_INSTALLED"))==1){$this->MILTERGREYLIST_INSTALLED=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SAMBA_INSTALLED"))==1){$this->SAMBA_INSTALLED=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_INSTALLED"))==1){$this->IPTABLES_INSTALLED=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WINBINDD_INSTALLED"))==1){$this->WINBINDD_INSTALLED=True;}
			
			
			if(isset($_GLOBAL["MAILFROMD_INSTALLED"])){$this->MAILFROMD_INSTALLED=$_GLOBAL["MAILFROMD_INSTALLED"];	}	
			if(isset($_GLOBAL["ARTICA_FILTER_QUEUE_PATH"])){$this->ARTICA_FILTER_QUEUE_PATH=$_GLOBAL["ARTICA_FILTER_QUEUE_PATH"];}
			if(isset($_GLOBAL["ARTICA_FILTER_MAXSUBQUEUE"])){$this->ARTICA_FILTER_MAXSUBQUEUE=$_GLOBAL["ARTICA_FILTER_MAXSUBQUEUE"];}
			
			if(isset($_GLOBAL["GeoIPDat_Path"])){$this->GeoIPDat_Path=$_GLOBAL["GeoIPDat_Path"];}
			if(isset($_GLOBAL["lighttpd_installed"])){$this->lighttpd_installed=$_GLOBAL["lighttpd_installed"];}
			if($this->ChartLicence==null){$this->ChartLicence='D1XFWGMEW9L.HSK5T4Q79KLYCK07EK';}

			$this->LOCAL_SID=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LOCAL_SID"));
			if(isset($_GLOBAL["POSTFIX_LDAP_COMPLIANCE"])){$this->POSTFIX_LDAP_COMPLIANCE=$_GLOBAL["POSTFIX_LDAP_COMPLIANCE"];}
			if(isset($_GLOBAL["MAILFROMD_SOCKET"])){$this->MAILFROMD_SOCKET=$_GLOBAL["MAILFROMD_SOCKET"];}
			
			
			if(isset($_GLOBAL["CLAMAV_MILTER_SOCKET"])){$this->CLAMAV_MILTER_SOCKET=$_GLOBAL["CLAMAV_MILTER_SOCKET"];}
			if(isset($_GLOBAL["CLAMAV_MILTER_INSTALLED"])){$this->CLAMAV_MILTER_INSTALLED=$_GLOBAL["CLAMAV_MILTER_INSTALLED"];}
			if(isset($_GLOBAL["SPAMASS_MILTER_INSTALLED"])){$this->SPAMASS_MILTER_INSTALLED=$_GLOBAL["SPAMASS_MILTER_INSTALLED"];}
			if(isset($_GLOBAL["SPAMASS_MILTER_SOCKET"])){$this->SPAMASS_MILTER_SOCKET=$_GLOBAL["SPAMASS_MILTER_SOCKET"];}
			
			if(isset($_GLOBAL["phpcgi"])){$this->phpcgi=$_GLOBAL["phpcgi"];}
			if(isset($_GLOBAL["razor_config"])){$this->razor_config=$_GLOBAL["razor_config"];}
			if(isset($_GLOBAL["razor_installed"])){$this->razor_installed=$_GLOBAL["razor_installed"];}
			if(isset($_GLOBAL["pyzor_installed"])){$this->pyzor_installed=$_GLOBAL["pyzor_installed"];}
			if(isset($_GLOBAL["spfmilter_installed"])){$this->spfmilter_installed=$_GLOBAL["spfmilter_installed"];}
			if(isset($_GLOBAL["netbiosname"])){$this->netbiosname=$_GLOBAL["netbiosname"];}
			if(isset($_GLOBAL["MIMEDEFANG_INSTALLED"])){$this->MIMEDEFANG_INSTALLED=$_GLOBAL["MIMEDEFANG_INSTALLED"];}
			if(isset($_GLOBAL["MIMEDEFANG_SOCKET"])){$this->MIMEDEFANG_SOCKET=$_GLOBAL["MIMEDEFANG_SOCKET"];}
			if(isset($_GLOBAL["GRAPHDEFANG_INSTALLED"])){$this->GRAPHDEFANG_INSTALLED=$_GLOBAL["GRAPHDEFANG_INSTALLED"];}
			if(isset($_GLOBAL["MHONARC_INSTALLED"])){$this->MHONARC_INSTALLED=$_GLOBAL["MHONARC_INSTALLED"];}
			if(isset($_GLOBAL["MHONARC_VERSION"])){$this->MHONARC_VERSION=$_GLOBAL["MHONARC_VERSION"];}
			if(isset($_GLOBAL["spamassassin_installed"])){$this->spamassassin_installed=$_GLOBAL["spamassassin_installed"];}
			if(isset($_GLOBAL["spamassassin_version"])){$this->spamassassin_version=$_GLOBAL["spamassassin_version"];}
			if(isset($_GLOBAL["bogofilter_installed"])){$this->BOGOFILTER_INSTALLED=$_GLOBAL["bogofilter_installed"];}
			
			
			
			if(is_file("/etc/artica-postfix/settings/Daemons/SQUID_INSTALLED")){
				$SQUID_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED"));
				if($SQUID_INSTALLED==1){
					$this->SQUID_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
					$this->SQUID_INSTALLED=true;
				}
			}
			
			if(is_file("/etc/artica-postfix/settings/Daemons/RoundCubeInstalled")){
				$RoundCubeInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RoundCubeInstalled"));	
				if($RoundCubeInstalled==1){
					$this->roundcube_installed=True;
					$this->roundcube_web_folder=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RoundCubeWebFolder");
					$this->roundcube_mysql_sources=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RoundCubeMySQLSources");
					
				}
			}		

			
			
			
			if(isset($_GLOBAL["stunnel4_installed"])){$this->stunnel4_installed=$_GLOBAL["stunnel4_installed"];}
			if(isset($_GLOBAL["DKIMFILTER_INSTALLED"])){$this->DKIMFILTER_INSTALLED=$_GLOBAL["DKIMFILTER_INSTALLED"];}
			if(isset($_GLOBAL["DKIMFILTER_SOCKET"])){$this->DKIMFILTER_SOCKET=$_GLOBAL["DKIMFILTER_SOCKET"];}
			
			
			if(isset($_GLOBAL["usb_list"])){$this->usb_list=$_GLOBAL["usb_list"];}
			$nmap_installed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NMAPInstalled"));
			if($nmap_installed==1){$this->nmap_installed=true;}
			
		
			if(isset($_GLOBAL["fetchmail_configured"])){$this->fetchmail_configured=$_GLOBAL["fetchmail_configured"];}
			if(isset($_GLOBAL["KAV4SAMBA_INSTALLED"])){$this->KAV4SAMBA_INSTALLED=$_GLOBAL["KAV4SAMBA_INSTALLED"];}
			if(isset($_GLOBAL["KAV4SAMBA_VFS"])){$this->KAV4SAMBA_VFS=$_GLOBAL["KAV4SAMBA_VFS"];}
			if(isset($_GLOBAL["SAMBA_MYSQL_AUDIT"])){$this->SAMBA_MYSQL_AUDIT=$_GLOBAL["SAMBA_MYSQL_AUDIT"];}
			if(isset($_GLOBAL["awstats_www_path"])){$this->awstats_www_path=$_GLOBAL["awstats_www_path"];}
			if(isset($_GLOBAL["SQUID_ICAP_ENABLED"])){$this->SQUID_ICAP_ENABLED=$_GLOBAL["SQUID_ICAP_ENABLED"];}
			if(isset($_GLOBAL["LIGHTTPD_LDAP_AUTH"])){$this->LIGHTTPD_LDAP_AUTH=$_GLOBAL["LIGHTTPD_LDAP_AUTH"];}
			
			if(isset($_GLOBAL["QUOTA_INSTALLED"])){$this->QUOTA_INSTALLED=$_GLOBAL["QUOTA_INSTALLED"];}
			
			if(isset($_GLOBAL["IPBAN_INSTALLED"])){$this->IPBAN_INSTALLED=$_GLOBAL["IPBAN_INSTALLED"];}
			if(isset($_GLOBAL["EJABBERD_INSTALLED"])){$this->EJABBERD_INSTALLED=$_GLOBAL["EJABBERD_INSTALLED"];;}
			if(isset($_GLOBAL["PYMSNT_INSTALLED"])){$this->PYMSNT_INSTALLED=$_GLOBAL["PYMSNT_INSTALLED"];;}
			if(isset($_GLOBAL["SSHFS_INSTALLED"])){$this->SSHFS_INSTALLED=$_GLOBAL["SSHFS_INSTALLED"];;}
			if(isset($_GLOBAL["APP_ARKEIA_INSTALLED"])){$this->APP_ARKEIA_INSTALLED=$_GLOBAL["APP_ARKEIA_INSTALLED"];;}
			if(isset($_GLOBAL["ZARAFA_SEARCH_INSTALLED"])){$this->ZARAFA_SEARCH_INSTALLED=$_GLOBAL["ZARAFA_SEARCH_INSTALLED"];;}
			if(isset($_GLOBAL["ZARAFA_INDEXER_INSTALLED"])){$this->ZARAFA_INDEXER_INSTALLED=$_GLOBAL["ZARAFA_INDEXER_INSTALLED"];;}
			
					
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ISCSID_INSTALLED"))==1){$this->ISCSID_INSTALLED=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FREERADIUS_INSTALLED"))==1){$this->FREERADIUS_INSTALLED=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_INSTALLED"))==1){$this->HAPROXY_INSTALLED=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("C_ICAP_INSTALLED"))==1){$this->C_ICAP_INSTALLED=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("C_ICAP_DNSBL"))==1){$this->C_ICAP_DNSBL=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XEN_HOST"))==1){$this->XEN_HOST=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HYPERV_HOST"))==1){$this->HYPERV_HOST=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SENSORS_INSTALLED"))==1){$this->SENSORS_INSTALLED=true;}
			
			
			
			
			if(isset($_GLOBAL["HYPERCACHE_STOREID"])){$this->HYPERCACHE_STOREID=$_GLOBAL["HYPERCACHE_STOREID"];}
			
			
			
	
			
			
			if(isset($_GLOBAL["LIGHT_INSTALL"])){$this->LIGHT_INSTALL=$_GLOBAL["LIGHT_INSTALL"];}
			// Are you sure to do that !!!, breaking the license turn you to a pirate...
			$this->CORP_LICENSE=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
			if(isset($_GLOBAL["bindrrd_installed"])){$this->bindrrd_installed=$_GLOBAL["bindrrd_installed"];}
			if(isset($_GLOBAL["fdm_installed"])){$this->fdm_installed=$_GLOBAL["fdm_installed"];}
			if(isset($_GLOBAL["fdm_cache"])){$this->fdm_cache=$_GLOBAL["fdm_cache"];}
			if(isset($_GLOBAL["p3scan_installed"])){$this->p3scan_installed=$_GLOBAL["p3scan_installed"];}
			if(isset($_GLOBAL["syslogng_installed"])){$this->syslogng_installed=$_GLOBAL["syslogng_installed"];}
			if(isset($_GLOBAL["AsDebianSystem"])){$this->AsDebianSystem=$_GLOBAL["AsDebianSystem"];}
			if(trim((string) $this->hostname)==null){$this->hostname=$this->fqdn;}
			if(isset($_GLOBAL["cpumhz"])){$this->cpumhz=$_GLOBAL["cpumhz"];}
			if(isset($_GLOBAL["preload_installed"])){$this->preload_installed=$_GLOBAL["preload_installed"];}
			if(isset($_GLOBAL["POSTFIX_MAILDROP_PATH"])){$this->POSTFIX_MAILDROP_PATH=$_GLOBAL["POSTFIX_MAILDROP_PATH"];}
			if(isset($_GLOBAL["smbmount_installed"])){$this->smbmount_installed=$_GLOBAL["smbmount_installed"];}
			if(isset($_GLOBAL["PUREFTP_USERS"])){$this->PUREFTP_USERS=$_GLOBAL["PUREFTP_USERS"];}
			if(isset($_GLOBAL["roundcube_version"])){$this->roundcube_version=$_GLOBAL["roundcube_version"];}
			
			if(isset($_GLOBAL["collectd_installed"])){$this->collectd_installed=$_GLOBAL["collectd_installed"];}
			if(isset($_GLOBAL["COLLECTD_RRD"])){$this->COLLECTD_RRD=$_GLOBAL["COLLECTD_RRD"];}
			if(isset($_GLOBAL["EnableVirtualDomainsInMailBoxes"])){$this->EnableVirtualDomainsInMailBoxes=$_GLOBAL["EnableVirtualDomainsInMailBoxes"];}
			
			
			
			if(isset($_GLOBAL["MILTER_SPY_INSTALLED"])){$this->MILTER_SPY_INSTALLED=$_GLOBAL["MILTER_SPY_INSTALLED"];}
			if(isset($_GLOBAL["mailsync_installed"])){$this->mailsync_installed=$_GLOBAL["mailsync_installed"];}
			if(isset($_GLOBAL["ISOQLOG_INSTALLED"])){$this->ISOQLOG_INSTALLED=$_GLOBAL["ISOQLOG_INSTALLED"];}
			if(isset($_GLOBAL["zip_installed"])){$this->zip_installed=$_GLOBAL["zip_installed"];}
			if(isset($_GLOBAL["OPENLDAP_DYNLIST"])){$this->OPENLDAP_DYNLIST=$_GLOBAL["OPENLDAP_DYNLIST"];}
			if(isset($_GLOBAL["SLPAD_LOCKED"])){$this->SLPAD_LOCKED=$_GLOBAL["SLPAD_LOCKED"];}
			
			if(isset($_GLOBAL["ALTERMIME_INSTALLED"])){$this->ALTERMIME_INSTALLED=$_GLOBAL["ALTERMIME_INSTALLED"];}
			if(isset($_GLOBAL["POMMO_INSTALLED"])){$this->POMMO_INSTALLED=$_GLOBAL["POMMO_INSTALLED"];}
			if(isset($_GLOBAL["dar_installed"])){$this->dar_installed=$_GLOBAL["dar_installed"];}
			$this->SAMBA_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAMBA_VERSION");
			
			
			if(isset($_GLOBAL["MILTERGREYLIST_SOCKET"])){$this->MILTERGREYLIST_SOCKET=$_GLOBAL["MILTERGREYLIST_SOCKET"];}
			if(is_file("/etc/artica-postfix/settings/Daemons/OPENVPN_INSTALLED")){
				if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENVPN_INSTALLED"))==1){$this->OPENVPN_INSTALLED=True;}
			}
			if(isset($_GLOBAL["GNARWL_INSTALLED"])){$this->GNARWL_INSTALLED=$_GLOBAL["GNARWL_INSTALLED"];}
			
			
			if(isset($_GLOBAL["PFLOGSUMM_INSTALLED"])){$this->PFLOGSUMM_INSTALLED=$_GLOBAL["PFLOGSUMM_INSTALLED"];}
			if(isset($_GLOBAL["maillog_path"])){$this->maillog_path=$_GLOBAL["maillog_path"];}
			if(isset($_GLOBAL["spamassassin_ipcountry"])){$this->spamassassin_ipcountry=$_GLOBAL["spamassassin_ipcountry"];}
			if(isset($_GLOBAL["spamassassin_conf_path"])){$this->spamassassin_conf_path=$_GLOBAL["spamassassin_conf_path"];}
			if(isset($_GLOBAL["CUPS_INSTALLED"])){$this->CUPS_INSTALLED=$_GLOBAL["CUPS_INSTALLED"];}
			
			
			if(isset($_GLOBAL["SARG_INSTALLED"])){$this->SARG_INSTALLED=$_GLOBAL["SARG_INSTALLED"];}
			
			if(is_file("/etc/artica-postfix/settings/Daemons/XAPIAN_PHP_INSTALLED")){if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XAPIAN_PHP_INSTALLED"))==1){$this->XAPIAN_PHP_INSTALLED=True;}}
			
			
			
			
			
			if(isset($_GLOBAL["APACHE_GROUPWARE_INSTALLED"])){$this->APACHE_GROUPWARE_INSTALLED=$_GLOBAL["APACHE_GROUPWARE_INSTALLED"];}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GNUPLOT_PNG"))==1){$this->GNUPLOT_PNG=true;}
			
			if(isset($_GLOBAL["POSTFIX_QUEUE"])){$this->POSTFIX_QUEUE=$_GLOBAL["POSTFIX_QUEUE"];}
			$this->PHP_BIN_PATH=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHP_BIN_PATH");
			if(isset($_GLOBAL["spamassassin_bin_path"])){$this->spamassassin_bin_path=$_GLOBAL["spamassassin_bin_path"];}
			$this->LinuxDistriCode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LinuxDistributionCodeName"));

			
			if(isset($_GLOBAL["roundcube_intversion"])){$this->roundcube_intversion=$_GLOBAL["roundcube_intversion"];}
			if(isset($_GLOBAL["roundcube_plugins"])){$this->roundcube_plugins=$_GLOBAL["roundcube_plugins"];}
			if(isset($_GLOBAL["smbclient_installed"])){$this->smbclient_installed=$_GLOBAL["smbclient_installed"];}
			if(isset($_GLOBAL["AMAVISD_VERSION"])){$this->AMAVISD_VERSION=$_GLOBAL["AMAVISD_VERSION"];}
			if(isset($_GLOBAL["cyrus_murder_installed"])){$this->cyrus_murder_installed=$_GLOBAL["cyrus_murder_installed"];}
			if(isset($_GLOBAL["cyrus_sync_installed"])){$this->cyrus_sync_installed=$_GLOBAL["cyrus_sync_installed"];}
			if(isset($_GLOBAL["FETCHMAIL_VERSION"])){$this->FETCHMAIL_VERSION=$_GLOBAL["FETCHMAIL_VERSION"];}
			if(isset($_GLOBAL["cyrus_sync_client_path"])){$this->cyrus_sync_client_path=$_GLOBAL["cyrus_sync_client_path"];}
			if(isset($_GLOBAL["ctl_mboxlist"])){$this->ctl_mboxlist=$_GLOBAL["ctl_mboxlist"];}
			if(isset($_GLOBAL["CURL_PATH"])){$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CURL_PATH");}
			
			if(isset($_GLOBAL["nmap_path"])){$this->NMAP_PATH=$_GLOBAL["nmap_path"];}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MSMTP_INSTALLED"))==1){$this->msmtp_installed=true;}
			
			
			
			if(isset($_GLOBAL["LVM_INSTALLED"])){$this->LVM_INSTALLED=$_GLOBAL["LVM_INSTALLED"];}
			if(isset($_GLOBAL["MYSQL_NDB_MGMD_INSTALLED"])){$this->MYSQL_NDB_MGMD_INSTALLED=$_GLOBAL["MYSQL_NDB_MGMD_INSTALLED"];}
			if(isset($_GLOBAL["winexe_installed"])){$this->winexe_installed=$_GLOBAL["winexe_installed"];}
			if(isset($_GLOBAL["ASSP_INSTALLED"])){$this->ASSP_INSTALLED=$_GLOBAL["ASSP_INSTALLED"];}
			
			
			
			
			
			
			$this->mysqld_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("mysqld_version");
			$this->SLAPD_CONF_PATH="/etc/ldap/slapd.conf";
			if(isset($_GLOBAL["cyrus_squatter_exists"])){$this->cyrus_squatter_exists=$_GLOBAL["cyrus_squatter_exists"];}
			if(isset($_GLOBAL["SCANNED_ONLY_INSTALLED"])){$this->SCANNED_ONLY_INSTALLED=$_GLOBAL["SCANNED_ONLY_INSTALLED"];}
			if(isset($_GLOBAL["CRYPTSETUP_INSTALLED"])){$this->CRYPTSETUP_INSTALLED=$_GLOBAL["CRYPTSETUP_INSTALLED"];}
			if(isset($_GLOBAL["ZABBIX_INSTALLED"])){$this->ZABBIX_INSTALLED=$_GLOBAL["ZABBIX_INSTALLED"];}
			
			if(isset($_GLOBAL["cyrus_ipurge_exists"])){$this->cyrus_ipurge_exists=$_GLOBAL["cyrus_ipurge_exists"];}

			if(isset($_GLOBAL["GLUSTER_INSTALLED"])){$this->GLUSTER_INSTALLED=$_GLOBAL["GLUSTER_INSTALLED"];}

			
			$this->MEM_TOTAL_INSTALLEE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MEM_TOTAL_INSTALLEE"));
			
			if(isset($_GLOBAL["POSTFILTER_INSTALLED"])){$this->POSTFILTER_INSTALLED=$_GLOBAL["POSTFILTER_INSTALLED"];}
			
			
			if(isset($_GLOBAL["phpldapadmin_installed"])){$this->phpldapadmin_installed=$_GLOBAL["phpldapadmin_installed"];}
			if(isset($_GLOBAL["phpmyadmin_installed"])){$this->phpmyadmin_installed=$_GLOBAL["phpmyadmin_installed"];}			
			
			
			

			$this->SQUID_BIN_PATH=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_BIN_PATH");
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MEM_HIGER_1G"))==1){$this->MEM_HIGER_1G=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DSTAT_INSTALLED"))==1){$this->DSTAT_INSTALLED=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NFS_SERVER_INTSALLED"))==1){$this->NFS_SERVER_INTSALLED=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_HOST"))==1){$this->VMWARE_HOST=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_TOOLS_INSTALLED"))==1){$this->VMWARE_TOOLS_INSTALLED=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOSTAPD_INSTALLED"))==1){$this->HOSTAPD_INSTALLED=true;}
			if(is_file("/etc/artica-postfix/settings/Daemons/WPA_SUPPLIANT_INSTALLED")){if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WPA_SUPPLIANT_INSTALLED"))==1){$this->WPA_SUPPLIANT_INSTALLED=true;}}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARPD_INSTALLED"))==1){$this->ARPD_INSTALLED=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KASPERSKY_WEB_APPLIANCE"))==1){$this->KASPERSKY_WEB_APPLIANCE=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RSYNC_INSTALLED"))==1){$this->RSYNC_INSTALLED=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("openldap_installed"))==1){$this->openldap_installed=true;}
			
			
			if(isset($_GLOBAL["mysqld_datadir"])){$this->mysqld_datadir=$_GLOBAL["mysqld_datadir"];}
			if(isset($_GLOBAL["MAIL_DKIM_VERSION"])){$this->MAIL_DKIM_VERSION=trim($_GLOBAL["MAIL_DKIM_VERSION"]);}
			if(isset($_GLOBAL["HDPARM_INSTALLED"])){$this->HDPARM_INSTALLED=trim($_GLOBAL["HDPARM_INSTALLED"]);}
			
			
			$this->hostname=str_replace('.(none)','',(string) $this->hostname);
			$this->fqdn=str_replace('.(none)','',(string)  $this->fqdn);
			
			
			

			if(isset($_GLOBAL["OCS_LNX_AGENT_INSTALLED"])){$this->OCS_LNX_AGENT_INSTALLED=$_GLOBAL["OCS_LNX_AGENT_INSTALLED"];}
			if(isset($_GLOBAL["POSTFIX_PCRE_COMPLIANCE"])){$this->POSTFIX_PCRE_COMPLIANCE=$_GLOBAL["POSTFIX_PCRE_COMPLIANCE"];};
			if(isset($_GLOBAL["APP_AUDITD_INSTALLED"])){$this->APP_AUDITD_INSTALLED=$_GLOBAL["APP_AUDITD_INSTALLED"];;};
			if(isset($_GLOBAL["DKFILTER_INSTALLED"])){$this->DKFILTER_INSTALLED=$_GLOBAL["DKFILTER_INSTALLED"];}
			if(isset($_GLOBAL["OPENDKIM_INSTALLED"])){$this->OPENDKIM_INSTALLED=$_GLOBAL["OPENDKIM_INSTALLED"];}
			if(isset($_GLOBAL["DB_RECOVER_INSTALLED"])){$this->DB_RECOVER_INSTALLED=$_GLOBAL["DB_RECOVER_INSTALLED"];}
			
			$APP_UFDBGUARD_INSTALLED=0;
			$this->APP_UFDBGUARD_INSTALLED=false;
			if(is_file("/etc/artica-postfix/settings/Daemons/APP_UFDBGUARD_INSTALLED")){
					$APP_UFDBGUARD_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_UFDBGUARD_INSTALLED"));
			}
			if($APP_UFDBGUARD_INSTALLED==1){
				$this->APP_UFDBGUARD_INSTALLED=true;
				$this->ufdbgclient_path=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbgclient_path");
			}
			
			
			if(isset($_GLOBAL["roundcube_subscriptions_option"])){$this->roundcube_subscriptions_option=$_GLOBAL["roundcube_subscriptions_option"];}
			if(isset($_GLOBAL["ADZAPPER_INSTALLED"])){$this->ADZAPPER_INSTALLED=$_GLOBAL["ADZAPPER_INSTALLED"];}
			
			if(isset($_GLOBAL["DKIMPROXY_INSTALLED"])){$this->DKIMPROXY_INSTALLED=$_GLOBAL["DKIMPROXY_INSTALLED"];}
			if(isset($_GLOBAL["MILTER_DKIM_INSTALLED"])){$this->MILTER_DKIM_INSTALLED=$_GLOBAL["MILTER_DKIM_INSTALLED"];}
			if(isset($_GLOBAL["DROPBOX_INSTALLED"])){$this->DROPBOX_INSTALLED=$_GLOBAL["DROPBOX_INSTALLED"];}
			if(isset($_GLOBAL["VIRTUALBOX_INSTALLED"])){$this->VIRTUALBOX_INSTALLED=$_GLOBAL["VIRTUALBOX_INSTALLED"];}
			
			if(isset($_GLOBAL["THINSTATION_INSTALLED"])){$this->THINSTATION_INSTALLED=$_GLOBAL["THINSTATION_INSTALLED"];}
			if(isset($_GLOBAL["SAMBA_CLUSTER_SUPPORT"])){$this->SAMBA_CLUSTER_SUPPORT=$_GLOBAL["SAMBA_CLUSTER_SUPPORT"];}

			
			if(isset($_GLOBAL["APP_JAVA_JDK_INSTALLED"])){$this->APP_JAVA_JDK_INSTALLED=$_GLOBAL["APP_JAVA_JDK_INSTALLED"];}
			if(isset($_GLOBAL["APACHE_MOD_PYTHON"])){$this->APACHE_MOD_PYTHON=$_GLOBAL["APACHE_MOD_PYTHON"];}
			if(isset($_GLOBAL["APP_PYAUTHENNTLM"])){$this->APP_PYAUTHENNTLM=$_GLOBAL["APP_PYAUTHENNTLM"];}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SURICATA_INSTALLED"))==1){$this->SURICATA_INSTALLED=True;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("offlineimap_installed"))==1){$this->offlineimap_installed=True;}
			
			

			if(isset($_GLOBAL["cyrus_syncserver_installed"])){$this->cyrus_syncserver_installed=$_GLOBAL["cyrus_syncserver_installed"];}
			if(isset($_GLOBAL["APP_SQUIDCLAMAV_INSTALLED"])){$this->APP_SQUIDCLAMAV_INSTALLED=$_GLOBAL["APP_SQUIDCLAMAV_INSTALLED"];}
			if(isset($_GLOBAL["squidclamav_path"])){$this->squidclamav_path=$_GLOBAL["squidclamav_path"];}
			if(isset($_GLOBAL["RACOON_INSTALLED"])){$this->RACOON_INSTALLED=$_GLOBAL["RACOON_INSTALLED"];}
			if(isset($_GLOBAL["IPSEC_INSTALLED"])){$this->IPSEC_INSTALLED=$_GLOBAL["IPSEC_INSTALLED"];}
			if(isset($_GLOBAL["GREYHOLE_INSTALLED"])){$this->GREYHOLE_INSTALLED=$_GLOBAL["GREYHOLE_INSTALLED"];}
			
			
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TC_INSTALLED"))==1){$this->qos_tools_installed=True;}
			$this->tc_version=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TC_VERSION"));
			
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAP_SYNCPROV"))==1){$this->LDAP_SYNCPROV=True;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KINIT_INSTALLED"))==1){$this->KINIT_INSTALLED=True;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_VBOXADDINTION_INSTALLED"))==1){$this->APP_VBOXADDINTION_INSTALLED=True;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VIRTUALBOX_HOST"))==1){$this->VIRTUALBOX_HOST=True;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SABNZBDPLUS_INSTALLED"))==1){$this->APP_SABNZBDPLUS_INSTALLED;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"))==1){$this->MUNIN_CLIENT_INSTALLED;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KASPERSKY_UPDATE_UTILITY_INSTALLED"))==1){$this->KASPERSKY_UPDATE_UTILITY_INSTALLED=True;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ASPELL_INSTALLED"))==1){$this->ASPELL_INSTALLED=True;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPSET_INSTALLED"))==1){$this->IPSET_INSTALLED=True;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTSCREEN_INSTALLED"))==1){$this->POSTSCREEN_INSTALLED=True;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ISCSI_INSTALLED"))==1){$this->ISCSI_INSTALLED=True;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VLAN_INSTALLED"))==1){$this->VLAN_INSTALLED=True;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LOSETUP_INSTALLED"))==1){$this->LOSETUP_INSTALLED=True;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ISCSI_CLIENT_INSTALLED"))==1){$this->ISCSI_CLIENT_INSTALLED=True;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENVPN_APPLIANCE"))==1){$this->OPENVPN_APPLIANCE=True;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AS_VPS_CLIENT"))==1){$this->AS_VPS_CLIENT=True;}
			$this->SIEVE_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SIEVE_PORT"));
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_ACCOUNTING_EXISTS"))==1){$this->IPTABLES_ACCOUNTING_EXISTS=True;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PPTPD_INSTALLED"))==1){$this->PPTPD_INSTALLED=True;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PPTP_INSTALLED"))==1){$this->PPTP_INSTALLED=True;}
			

			if($this->LinuxDistriCode=="DEBIAN"){$this->AS_DEBIAN_FAMILY=true;}
			if($this->LinuxDistriCode=="UBUNTU"){$this->AS_DEBIAN_FAMILY=true;}

			
			if(isset($_GLOBAL["Z_PUSH_INSTALLED"])){$this->Z_PUSH_INSTALLED=$_GLOBAL["Z_PUSH_INSTALLED"];}
			
			

			if(isset($_GLOBAL["APP_TOMCAT6_INSTALLED"])){$this->APP_TOMCAT6_INSTALLED=$_GLOBAL["APP_TOMCAT6_INSTALLED"];}
			if(isset($_GLOBAL["APP_TOMCAT6_VERSION"])){$this->APP_TOMCAT6_VERSION=$_GLOBAL["APP_TOMCAT6_VERSION"];}
			

			
			

				
			
			
			
			if(isset($_GLOBAL["deduplication_installed"])){$this->deduplication_installed=$_GLOBAL["deduplication_installed"];}
			
			
			if(!isset($GLOBALS["CLASS_SOCKETS"])){$sock=new sockets();$GLOBALS["CLASS_SOCKETS"]=$sock;}
			if(!is_object($GLOBALS["CLASS_SOCKETS"])){$sock=new sockets();$GLOBALS["CLASS_SOCKETS"]=$sock;}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
			$ArticaMetaEnabled=trim($sock->GET_INFO("ArticaMetaEnabled"));
			if($ArticaMetaEnabled==null){$sock->SET_INFO("ArticaMetaEnabled","0");}
			if($ArticaMetaEnabled==1){$this->ARTICA_META_ENABLED=true;}
			if($this->PROXYTINY_APPLIANCE){$this->SQUID_APPLIANCE=true;}
			

			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaProxyInstalled"))==1){$this->HAPROXY_INSTALLED=true;}else{$this->HAPROXY_INSTALLED=false;}
			
			
			$pear=explode(";",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PEAR_PACKAGES"));
			if(is_array($pear)){
			    foreach ($pear as $num=>$pp){
					if(trim($pp)==null){continue;}
					$this->PEAR_PACKAGES[$pp]=true;
				}
			}

            if(is_file("/etc/artica-postfix/CPU_NUMBER")) {
                $this->CPU_NUMBER = intval(@file_get_contents("/etc/artica-postfix/CPU_NUMBER"));
            }
			
			
			if($this->OPENVPN_APPLIANCE){
				$this->SQUID_INSTALLED=false;
				$this->SAMBA_INSTALLED=false;
				$this->POSTFIX_INSTALLED=false;
				$this->cyrus_imapd_installed=false;
				
				
				$this->nmap_installed=false;
			}			
			
			if($this->KASPERSKY_WEB_APPLIANCE){
				$this->POSTFIX_INSTALLED=false;
				$this->APP_AUDITD_INSTALLED=false;

				
			}
	        $this->CORP_LICENSE=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();

		}
		


	public function EnablePDNS(){
		
		$EnablePDNS=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS");
		if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
		
		if($this->EnableDNSMASQ()==1){return 0;}
	
	
		if($this->APP_CHILLI_INSTALLED){
			$EnableChilli=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableChilli");
			if(!is_numeric($EnableChilli)){$EnableChilli=0;}
			if($EnableChilli==1){return 0;}
		}
	
		return $EnablePDNS;
	}
	public function EnableDNSMASQ(){
		$DNSMASQInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSMASQInstalled"));
		if($DNSMASQInstalled==0){return 0;}
		$EnableDNSMASQ=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSMASQ");
		if(!is_numeric($EnableDNSMASQ)){$EnableDNSMASQ=0;}
		return $EnableDNSMASQ;
	}	
	
}

?>