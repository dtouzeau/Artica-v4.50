<?php
if(!defined("alse")){define("alse", "alse");}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$GLOBALS["AS_ROOT"]=false;
if(!isset($GLOBALS["KAV4PROXY_NOSESSION"])){$GLOBALS["KAV4PROXY_NOSESSION"]=false;}
if(!isset($GLOBALS["DEBUG_MEM"])){$GLOBALS["DEBUG_MEM"]=false;}
if(!isset($GLOBALS["UFDBPAGE"])){$GLOBALS["UFDBPAGE"]=false;}
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__) .'/logs.inc');
if(function_exists("debug_mem")){debug_mem();}

class usersMenus {
	public $AS_KIMSUFFI=false;
    public $AsDockerWeb=false;
    public $AsWebSecurity=false;
    public $AsWebMonitor=false;
	public $TreePage=array("domains.edit.group.php"=>true,"samba.index.php"=>true);
	public $ArchStruct=0;
	public $menus;
	public $main_left_menus=array();
	public $menusright;
	public $WANPROXY=FALSE;
	public $toplinks;
	public $priv;
	public $MAIN_CONSOLE_PORT=9000;
	public $EnablePerUserRemoteAddressBook=0;
	public $dhcp_installed=false;
	public $PRIV_VERBOSE=array();
    public $AsJoomlaWebMaster=false;
	public $AllowChangeDomains=false;
	public $AsSystemAdministrator=false;
	public $AsArticaMetaAdmin=false;
	public $AsDansGuardianAdministrator=false;
    public $AsDatabaseAdministrator=false;
    public $NGINX_SERVICES=array();
    public $SIMPLE_ACLS=array();
	public $AsSquidPersonalCategories=false;
	public $AsWebFilterRepository=false;
	public $AsOrgStorageAdministrator=false;
	public $AllowXapianDownload=false;
	public $AllowAddGroup=false;
	public $AllowUserMaillog=false;
	public $AllowChangeKav=false;
	public $AllowChangeKas=false;
	public $AllowEditAsWbl=false;
	public $AllowEditOuSecurity=false;
	public $RestrictNabToGroups=false;
	public $AsArticaAdministrator=false;
	public $AsSambaAdministrator=false;
	public $AsMailManAdministrator=false;
	public $AsPostfixAdministrator=false;
	public $AsSquidAdministrator=false;
    public $AsDockerAdmin=false;
    public $AsHamrpAdmin=false;
    public $AsCategories=false;
	public $APP_NETDATA_INSTALLED=false;
	public $AsMailBoxAdministrator=false;
	public $AsDansGuardianGroupRule=false;
	public $AsWebStatisticsAdministrator=false;
	public $AsWebMaster=false;
	public $AsSystemWebMaster=false;
	public $AsDnsAdministrator=false;
    public $AsDockerReverse=false;
	public $AsQuarantineAdministrator=false;
	public $AllowFetchMails=false;
	public $AllowSenderCanonical=false;
	public $AllowChangeUserPassword=false;
	public $AllowDansGuardianBanned=false;
	public $AllowChangeUserKas=false;
	public $AllowEditAliases=false;
	public $AllowEmailing=false;
	public $AllowOpenVPN=false;
	public $AsAnAdministratorGeneric=false;
	public $CGROUPS_INSTALLED=false;
	public $AllowAddUsers=false;
	public $AllowViewStatistics=false;
	public $AllowChangeAntiSpamSettings=false;
	public $AsOrgAdmin=false;
	public $AsProxyMonitor=false;
	public $AsInventoryAdmin=false;
	public $AseMailCampaignsAdmin=false;
	public $NETATALK_INSTALLED=false;
	public $SAMBA_APPLIANCE=false;
	public $MPM_ITK_MODULE=false;
	public $APACHE_MOD_BW=false;
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
    public $template_user_addr;
	public $PasswdPolicy=array();
	public $disks_size=null;
	public $cpumhz=null;
	public $preload_installed=false;
	public $smbmount_installed=false;
	public $collectd_installed=false;
	public $EnableVirtualDomainsInMailBoxes=false;
	public $MEMCACHED_INSTALLED=false;
	public $CNTLM_INSTALLED=false;
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
	public $MainarticakEy;
	public $APACHE_INSTALLED=false;
	public $cyrus_sync_installed=false;
	public $AllowManageOwnComputers=false;
	public $POLICYD_WEIGHT_PORT=12525;
	public $CRYPTSETUP_INSTALLED=false;
	public $ZABBIX_INSTALLED=true;
	public $HAMACHI_INSTALLED=true;
	public $phpldapadmin_installed=false;
	public $phpmyadmin_installed=false;
	public $HOSTAPD_INSTALLED=false;

	public $POSTMULTI=false;
	public $VIRTUALBOX_HOST=false;
	public $APP_VBOXADDINTION_INSTALLED=false;
	public $MHONARC_VERSION="";
	public $FETCHMAIL_STATUS="";
	public $kavmilter_installed=false;
	public $APACHE_MOD_AUTHNZ_LDAP=false;
	public $OverWriteRestrictedDomains=false;
	public $AsHotSpotManager=false;
	public $AsFirewallManager=false;

	public $AsVPNManager=false;
	public $AsOrgDNSAdmin=false;
	public $ASDCHPAdmin=false;
	public $AsCertifsManager=false;
	public $PACTESTER_INSTALLED=false;
	public $CONNTRACK_INSTALLED=false;
	public $APP_SQUID27_INSTALLED=false;

	public $VSFTPD_INSTALLED=false;
	public $PHPFPM_INSTALLED=false;
	public $WIFIDOG_INSTALLED=false;
	/* This licence must be used Only with Artica-for postfix */
	public $ChartLicence="D1XFWGMEW9L.HSK5T4Q79KLYCK07EK";
	/*you cannot use it without any authorizations of maani.us.
	this licence is Copyright 2004-2007, maani.us
	For more information, see
	http://www.maani.us/xml_charts/index.php?menu=License
	*/

	public $STATS_APPLIANCE=null;
	public $WEBSTATS_APPLIANCE=null;

	public $KASPERSKY_WEB_APPLIANCE=false;
	public $APP_ULOGD_INSTALLED=false;
	public $APP_ZIP_INSTALLED=false;
	public $APP_OPENVPN_PYTHON=false;
	public $APP_DAVSERVER=false;
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
	public $mysql_installed=false;
	public $mailgraph_installed=false;
	public $mailgraph_virus_database;
	public $mailgraph_postfix_database;
	public $queuegraph_installed=false;
	public $LOCATE_AUTHLOG_PATH="";
	public $QUOTA_INSTALLED=false;
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
	public $NGINX_INSTALLED=false;
	public $SQUID_BIN_PATH="";
	public $VMWARE_HOST=false;
	public $HAPROXY_INSTALLED=false;
	public $DANSGUARDIAN_INSTALLED=false;
	public $AMAVIS_INSTALLED=false;
	public $AMAVISD_VERSION=null;
	public $DANSGUARDIAN_VERSION=null;
	public $NETBALANCE_INSTALLED=false;
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


	public $NTPD_INSTALLED;
	public $NTPDEnabled;
	public $MailFromdEnabled;
	public $IPTABLES_INSTALLED;
	public $ApacheArticaEnabled;
	public $SAMBA_INSTALLED;

	public $SAMBA_VERSION;
	public $GeoIPDat_Path;
	public $POSTFIX_LDAP_COMPLIANCE=false;
	public $MAILFROMD_SOCKET="";
	public $CLAMAV_INSTALLED=false;
	public $CLAMD_INSTALLED=false;
	public $CLAMSCAN_INSTALLED=false;

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
	public $APP_CHILLI_INSTALLED=false;
	public $RDPPROXY_INSTALLED=false;
	public $netbiosname;
	public $MIMEDEFANG_SOCKET="";
	public $MimeDefangEnabled;
	public $GRAPHDEFANG_INSTALLED=false;
	public $MHONARC_INSTALLED=alse;
	public $SQUID_VERSION;
	public $SQUID_LDAP_AUTH;
	public $SQUID_NTLM_AUTH;
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
	public $STRONGSWAN_INSTALLED=false;
	public $spamassassin_conf_path="/etc/spamassassin/local.cf";
	public $SMARTMONTOOLS_INSTALLED=false;
	public $XAPIAN_PHP_INSTALLED=false;
	public $INSTANSEARCH_ENABLE=false;
	public $JOOMLA_INSTALLED=false;
	public $DSTAT_INSTALLED=false;
	public $SUGARCRM_INSTALLED=false;
	public $GNUPLOT_PNG=false;
	public $IOSTAT_INSTALLED=false;
	public $IPAUDIT_INSTALLED=false;
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
	public $autofs_installed=false;

	public $NFS_SERVER_INSTALLED=false;
	public $LVM_INSTALLED=false;
	public $MYSQL_NDB_MGMD_INSTALLED=false;
	public $CPU_NUMBER=0;

	public $winexe_installed=false;

	public $ASSP_INSTALLED=false;
	public $MEM_HIGER_1G=true;
	public $LMB_LUNDIMATIN_INSTALLED=false;

	public $TCP_ARRAY=array();
	public $POWER_DNS_INSTALLED=false;
	public $mysqld_version=0;
	public $GLUSTER_INSTALLED=false;
	public $SLAPD_CONF_PATH;
	public $cyrus_squatter_exists=false;
	public $cyrus_ipurge_exists=false;
	public $SCANNED_ONLY_INSTALLED=false;
	public $GROUPOFFICE_INSTALLED=false;
	public $POSTFIX_MULTI=false;
	public $AsOrgPostfixAdministrator=false;
	public $AsMessagingOrg=false;
    public $MEM_TOTAL_INSTALLEE=0;
	public $KASPERSKY_SMTP_APPLIANCE=false;
	public $POSTFILTER_INSTALLED=false;
	public $VMWARE_TOOLS_INSTALLED=false;

	public $ZARAFA_INSTALLED=false;
	public $SQUIDGUARD_INSTALLED=false;
	public $SQUIDGUARD_BIN_PATH="";
	public $RSYNC_INSTALLED=false;
	public $ZARAFA_APPLIANCE=false;
	public $mysqld_datadir;

	public $MAIL_DKIM_VERSION=null;
	public $HDPARM_INSTALLED=false;
	public $APACHE_MODE_WEBDAV=false;


	public $OCS_LNX_AGENT_INSTALLED=false;
	public $POSTFIX_PCRE_COMPLIANCE=false;
	public $APP_AUDITD_INSTALLED=false;
	public $DKFILTER_INSTALLED=false;
	public $OPENDKIM_INSTALLED=false;
	public $DB_RECOVER_INSTALLED=false;
	public $APP_UFDBGUARD_INSTALLED=false;
	public $ufdbgclient_path=null;
	public $roundcube_subscriptions_option=false;
	public $KINIT_INSTALLED=false;
	public $DKIMPROXY_INSTALLED=false;
	public $MILTER_DKIM_INSTALLED=false;
	public $DROPBOX_INSTALLED=false;
	public $VIRTUALBOX_INSTALLED=false;
	public $AsVirtualBoxManager=false;
	public $TFTPD_INSTALLED=false;
	public $offlineimap_installed=false;
	public $THINSTATION_INSTALLED=false;
	public $OPENSSH_VER=0;
	public $cyrus_syncserver_installed=false;
	public $PEAR_PACKAGES=array();

	public $IPSEC_INSTALLED=false;
	public $PPTPD_INSTALLED=false;
	public $PPTP_INSTALLED=false;
	public $SQUID_ARP_ACL_ENABLED=true;

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
	public $APACHE_DIR_SITES_ENABLED='';
    public $ISCSI_INSTALLED=false;
	public $ISCSI_CLIENT_INSTALLED=false;
	public $ldapClass;

	public $AS_VPS_CLIENT=false;
	public $AS_DEBIAN_FAMILY=false;
	public $VLAN_INSTALLED=false;

	public $LOSETUP_INSTALLED;
	public $SNORT_INSTALLED=false;



	public $OPENVPN_APPLIANCE=false;
	public $SIEVE_PORT=0;
	public $Z_PUSH_INSTALLED=false;


	public $IPTABLES_ACCOUNTING_EXISTS=false;

	public $POWERADMIN_INSTALLED=false;
	public $SYNCTHING_INSTALLED=false;
	public $NicRoleArray=array();
	public $PDNSSEC_INSTALLED=false;
	public $DRUPAL7_INSTALLED=false;


	public $MSKTUTIL_INSTALLED=false;
	public $WINBINDD_INSTALLED=false;

	public $JOOMLA17_INSTALLED=false;

	public $FROM_ISO=false;

	public $WORDPRESS_INSTALLED=false;
	public $APACHE_MOD_INFO=false;
	public $APACHE_MOD_STATUS=false;
	public $MYCOSI_APPLIANCE=false;
	public $LOAD_BALANCE_APPLIANCE=false;
	public $ARPD_INSTALLED=false;
	public $SQUID_APPLIANCE=false;
	public $PROXYTINY_APPLIANCE=false;
	public $APP_KHSE_INSTALLED=false;
	public $MYSQLD_MULTI_INSTALLED=false;
	public $MONIT_INSTALLED=false;
	public $CONCRETE5_INSTALLED=false;
	public $ECAPAV_INSTALLED=false;
	public $UPDATE_UTILITYV2_INSTALLED=false;
	public $IPBAN_INSTALLED=false;

	public $ZARAFA_WEBAPP_INSTALLED=false;
	public $ZARAFA_WEBACCESS_MOBILE_INSTALLED=false;
	public $EJABBERD_INSTALLED=false;
	public $PYMSNT_INSTALLED=false;
	public $PYICQT_INSTALLED=false;
	public $SSHFS_INSTALLED=false;
	public $BTRFS_INSTALLED=false;
	public $APP_ARKEIA_INSTALLED=false;
	public $SAMBA_CLUSTER_SUPPORT=false;
	public $HAPRROXY_APPLIANCE=false;
	public $SMTP_APPLIANCE=false;
	public $FULL_APPLIANCE=false;
	public $FROM_SETUP=false;
	public $KLMS_WEB_INSTALLED=false;
	public $INNODB_FILE_PER_TABLE_INSTALL=false;
	public $LDAP_BACKMETA=false;
	public $LDAP_BACKLDAP=false;
	public $LDAP_RWM=false;
	public $LDAP_BACKRELAY=false;
	public $CYRUS_APPLIANCE=false;
	public $VPS_OPENVZ=false;
	public $ZARAFA_SEARCH_INSTALLED=false;
	public $OWNCLOUD_INSTALLED=false;
	public $LIGHT_INSTALL=false;
	public $CORP_LICENSE=false;
	public $AsOwnMailBoxBackup=false;
	public $SURICATA_INSTALLED=false;
	public $ZARAFA_INDEXER_INSTALLED=false;
	public $APP_TOMCAT6_INSTALLED=false;
	public $APP_JAVA_JDK_INSTALLED=false;
	public $WSGATE_INSTALLED=false;
	public $URLSNARF_INSTALLED=false;
	public $ETTERCAP_INSTALLED=false;
	public $PERL_AUTHNTLM=false;
	public $APACHE_MOD_PYTHON=false;
	public $APP_PYAUTHENNTLM=false;
	public $APP_ZARAFADB_INSTALLED=false;
	public $FREERADIUS_INSTALLED=false;

	public $APACHE_MOD_SUPHP=false;
	public $APP_FILEZ_WEB=false;
	public $NTPDATE=false;
	public $APP_FTP_PROXY=false;
	public $UCARP_INSTALLED=false;
	public $SQUID_REVERSE_APPLIANCE=false;
	public $ISCSID_INSTALLED=false;
	public $VDESWITCH_INSTALLED=false;
	public $GATEWAY_APPLIANCE=false;
	public $WEBSECURITY_APPLIANCE=false;
	public $APP_EBTABLES_INSTALLED=false;

	public $WORDPRESS_APPLIANCE=false;
	public $WEBSECURIZE=false;
	public $BAMSIGHT=false;
	public $LANWANSAT=false;
	public $SENSORS_INSTALLED=false;
	public $SS5_INSTALLED=false;
	public $FIREHOL=false;
	public $XEN_HOST=false;
	public $HYPERV_HOST=false;
	public $APP_NGINX_CONSOLE=false;


	private function PostfixDiscover(){
		$PostfixPostMulti=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixPostMulti"));
		if($PostfixPostMulti==1){$this->POSTMULTI=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVMilterInstalled"))==1){$this->CLAMAV_MILTER_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangInstalled"))==1){$this->MIMEDEFANG_INSTALLED=true;}

	}

	function __construct($priv=null,$nobuild=0,$hostname=null){
		$this->PRIV_VERBOSE=array();
		$mypage=$this->CurrentPageName();

		$_GLOBAL=array();

		if(!isset($GLOBALS["posix_getuid"])){$GLOBALS["posix_getuid"]=1000;if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}}

		include_once(dirname(__FILE__)."/class.sockets.inc");
		$settings=dirname(__FILE__).'/settings.inc';
		if(is_file($settings)){@include($settings);$this->GLOBAL_CONFIG=$_GLOBAL;}

        if(is_file("/etc/docker-method.conf")){
            $dockerType=trim(@file_get_contents("/etc/docker-method.conf"));
            if($dockerType=="webadm"){
                $this->AsDockerWeb=true;
            }else{
                $this->AsDockerWeb=false;
            }
        }


		$PostFixInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
		if($PostFixInstalled==1){$this->POSTFIX_INSTALLED=true;$this->PostfixDiscover();}
        $this->MAIN_CONSOLE_PORT=9000;
		$this->LOCATE_AUTHLOG_PATH="/var/log/auth.log";

		if(isset($_GLOBAL["cyrus_ldap_admin_password"])){$this->cyrus_ldap_admin_password=$_GLOBAL["cyrus_ldap_admin_password"];}
		if(isset($_GLOBAL["AutomaticConfig"])){$this->AutomaticConfig=$_GLOBAL["AutomaticConfig"];}
		if(isset($_GLOBAL["imapsync_installed"])){$this->imapsync_installed=$_GLOBAL["imapsync_installed"];}
		if(isset($_GLOBAL["fetchmail_installed"])){$this->fetchmail_installed=$_GLOBAL["fetchmail_installed"];}
		if(isset($_GLOBAL["cyrus_imapd_installed"])){$this->cyrus_imapd_installed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cyrus_imapd_installed"));}
		if(isset($_GLOBAL["cyrus_lmtp_path"])){$this->cyrus_lmtp_path=$_GLOBAL["cyrus_lmtp_path"];}
		if(isset($_GLOBAL["cyr_partition_default"])){$this->cyr_partition_default=$_GLOBAL["cyr_partition_default"];}
		if(isset($_GLOBAL["cyr_config_directory"])){$this->cyr_config_directory=$_GLOBAL["cyr_config_directory"];}
		if(isset($_GLOBAL["rrdtool_installed"])){$this->rrdtool_installed=$_GLOBAL["rrdtool_installed"];}
		if(isset($_GLOBAL["procmail_installed"])){$this->procmail_installed=$_GLOBAL["procmail_installed"];}
		if(isset($_GLOBAL["mysql_installed"])){$this->mysql_installed=$_GLOBAL["mysql_installed"];}
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

		if(isset($_GLOBAL["MAILMAN_INSTALLED"])){$this->MAILMAN_INSTALLED=$_GLOBAL["MAILMAN_INSTALLED"];;}

		if(isset($_GLOBAL["fetchmail_daemon_logs"])){$this->fetchmail_daemon_logs=$_GLOBAL["fetchmail_daemon_logs"];;}

		if(isset($_GLOBAL["ARTICA_FILTER_STATUS"])){$this->ARTICA_FILTER_STATUS=$_GLOBAL["ARTICA_FILTER_STATUS"];;}
		if(isset($_GLOBAL["ARTICA_VERSION"])){$this->ARTICA_VERSION=$_GLOBAL["ARTICA_VERSION"];;}
		if(isset($_GLOBAL["hotwayd_installed"])){$this->hotwayd_installed=$_GLOBAL["hotwayd_installed"];;}

		if(isset($_GLOBAL["EMAILRELAY_STATUS"])){$this->EMAILRELAY_STATUS=$_GLOBAL["EMAILRELAY_STATUS"];;}
		if(isset($_GLOBAL["EMAILRELAY_INSTALLED"])){$this->EMAILRELAY_INSTALLED=$_GLOBAL["EMAILRELAY_INSTALLED"];;}

		$this->ARTICA_ROOT_PATH="/usr/share/artica-postfix";

		if(is_file("/etc/artica-postfix/settings/Daemons/NGINX_INSTALLED")){
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NGINX_INSTALLED"))==1){
				$this->NGINX_INSTALLED=true;
			}
		}



		if(is_file("/etc/artica-postfix/settings/Daemons/SyncThingInstalled")){
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyncThingInstalled"))==1){
				$this->SYNCTHING_INSTALLED=true;
			}
		}
		if(is_file("/etc/artica-postfix/settings/Daemons/DropBoxInstalled")){
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DropBoxInstalled"))==1){
				$this->DROPBOX_INSTALLED=true;
			}
		}
		if(is_file("/etc/artica-postfix/settings/Daemons/CNTLMInstalled")){
			$CNTLMInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CNTLMInstalled"));
			if($CNTLMInstalled==1){$this->CNTLM_INSTALLED=true;}
		}
		if(is_file("/etc/artica-postfix/settings/Daemons/SQUID_INSTALLED")){
			$SQUID_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED"));
			if($SQUID_INSTALLED==1){
				$this->SQUID_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
				$this->SQUID_INSTALLED=true;
				if(is_file("/etc/artica-postfix/settings/Daemons/SQUID_LDAP_AUTH")){$this->SQUID_LDAP_AUTH=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_LDAP_AUTH");}
			}
		}
		if(is_file("/etc/artica-postfix/settings/Daemons/C_ICAP_INSTALLED")){
			$C_ICAP_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("C_ICAP_INSTALLED"));
			if($C_ICAP_INSTALLED==1){
				$this->C_ICAP_INSTALLED=true;
				if(is_file("/etc/artica-postfix/settings/Daemons/C_ICAP_DNSBL")){
					$C_ICAP_DNSBL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("C_ICAP_DNSBL"));
					if($C_ICAP_DNSBL==1){$this->C_ICAP_DNSBL=true;}
				}
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

		if(is_file("/etc/artica-postfix/settings/Daemons/AsHaProxyAppliance")){
			$AsHaProxyAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AsHaProxyAppliance"));
			if($AsHaProxyAppliance==1){$this->HAPRROXY_APPLIANCE=true;}

		}
		if(is_file("/etc/artica-postfix/settings/Daemons/FromSetup")){
			$FromSetup=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FromSetup"));
			if($FromSetup==1){$this->FROM_SETUP=true;}

		}


		if(isset($_GLOBAL["SQUID_CACHMGR"])){$this->SQUID_CACHMGR=$_GLOBAL["SQUID_CACHMGR"];;}
		if(isset($_GLOBAL["APACHE_DIR_SITES_ENABLED"])){$this->APACHE_DIR_SITES_ENABLED=$_GLOBAL["APACHE_DIR_SITES_ENABLED"];;}

		if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){$this->WEBSECURITY_APPLIANCE=true;}
		if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){$this->STATS_APPLIANCE=true;}
		if(!$this->STATS_APPLIANCE){
			$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
			if($EnableWebProxyStatsAppliance==1){$this->STATS_APPLIANCE=true;}
		}

		if(isset($_GLOBAL["AS_KIMSUFFI"])){$this->AS_KIMSUFFI=$_GLOBAL["AS_KIMSUFFI"];}
		if(isset($_GLOBAL["MYSQLD_MULTI_INSTALLED"])){$this->MYSQLD_MULTI_INSTALLED=$_GLOBAL["MYSQLD_MULTI_INSTALLED"];}
		if(isset($_GLOBAL["ECAPAV_INSTALLED"])){$this->ECAPAV_INSTALLED=$_GLOBAL["ECAPAV_INSTALLED"];}
		if(isset($_GLOBAL["UPDATE_UTILITYV2_INSTALLED"])){$this->UPDATE_UTILITYV2_INSTALLED=$_GLOBAL["UPDATE_UTILITYV2_INSTALLED"];}
		if(isset($_GLOBAL["WEBSTATS_APPLIANCE"])){$this->WEBSTATS_APPLIANCE=$_GLOBAL["WEBSTATS_APPLIANCE"];;}
		if(isset($_GLOBAL["QUOTA_INSTALLED"])){$this->QUOTA_INSTALLED=$_GLOBAL["QUOTA_INSTALLED"];;}
		if(isset($_GLOBAL["ZARAFA_WEBAPP_INSTALLED"])){$this->ZARAFA_WEBAPP_INSTALLED=$_GLOBAL["ZARAFA_WEBAPP_INSTALLED"];;}
		if(isset($_GLOBAL["ZARAFA_WEBACCESS_MOBILE_INSTALLED"])){$this->ZARAFA_WEBACCESS_MOBILE_INSTALLED=$_GLOBAL["ZARAFA_WEBACCESS_MOBILE_INSTALLED"];;}
		if(isset($_GLOBAL["EJABBERD_INSTALLED"])){$this->EJABBERD_INSTALLED=$_GLOBAL["EJABBERD_INSTALLED"];;}
		if(isset($_GLOBAL["PYMSNT_INSTALLED"])){$this->PYMSNT_INSTALLED=$_GLOBAL["PYMSNT_INSTALLED"];;}
		if(isset($_GLOBAL["PYICQT_INSTALLED"])){$this->PYICQT_INSTALLED=$_GLOBAL["PYICQT_INSTALLED"];;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaProxyInstalled"))==1){$this->HAPROXY_INSTALLED=true;}else{$this->HAPROXY_INSTALLED=false;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_INSTALLED"))==1){$this->HAPROXY_INSTALLED=true;}
		if(isset($_GLOBAL["SSHFS_INSTALLED"])){$this->SSHFS_INSTALLED=$_GLOBAL["SSHFS_INSTALLED"];;}
		if(isset($_GLOBAL["BTRFS_INSTALLED"])){$this->BTRFS_INSTALLED=$_GLOBAL["BTRFS_INSTALLED"];;}
		if(isset($_GLOBAL["APP_ARKEIA_INSTALLED"])){$this->APP_ARKEIA_INSTALLED=$_GLOBAL["APP_ARKEIA_INSTALLED"];;}
		if(isset($_GLOBAL["SAMBA_CLUSTER_SUPPORT"])){$this->SAMBA_CLUSTER_SUPPORT=$_GLOBAL["SAMBA_CLUSTER_SUPPORT"];}






		if(isset($_GLOBAL["FULL_APPLIANCE"])){$this->FULL_APPLIANCE=$_GLOBAL["FULL_APPLIANCE"];}
		if(isset($_GLOBAL["INNODB_FILE_PER_TABLE_INSTALL"])){$this->INNODB_FILE_PER_TABLE_INSTALL=$_GLOBAL["INNODB_FILE_PER_TABLE_INSTALL"];}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENVPN_APPLIANCE"))==1){$this->OPENVPN_APPLIANCE=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENVPN_INSTALLED"))==1){$this->OPENVPN_INSTALLED=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("STRONGSWAN_INSTALLED"))==1){$this->STRONGSWAN_INSTALLED=True;}
		if(isset($_GLOBAL["CYRUS_APPLIANCE"])){$this->CYRUS_APPLIANCE=$_GLOBAL["CYRUS_APPLIANCE"];}
		if(isset($_GLOBAL["VPS_OPENVZ"])){$this->VPS_OPENVZ=$_GLOBAL["VPS_OPENVZ"];}
		if(isset($_GLOBAL["ZARAFA_SEARCH_INSTALLED"])){$this->ZARAFA_SEARCH_INSTALLED=$_GLOBAL["ZARAFA_SEARCH_INSTALLED"];}
		if(isset($_GLOBAL["ZARAFA_INDEXER_INSTALLED"])){$this->ZARAFA_INDEXER_INSTALLED=$_GLOBAL["ZARAFA_INDEXER_INSTALLED"];}
		if(isset($_GLOBAL["APP_TOMCAT6_INSTALLED"])){$this->APP_TOMCAT6_INSTALLED=$_GLOBAL["APP_TOMCAT6_INSTALLED"];}
		if(isset($_GLOBAL["OWNCLOUD_INSTALLED"])){$this->OWNCLOUD_INSTALLED=$_GLOBAL["OWNCLOUD_INSTALLED"];}
		if(isset($_GLOBAL["LIGHT_INSTALL"])){$this->LIGHT_INSTALL=$_GLOBAL["LIGHT_INSTALL"];}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SURICATA_INSTALLED"))==1){$this->SURICATA_INSTALLED=True;}
		if(isset($_GLOBAL["WSGATE_INSTALLED"])){$this->WSGATE_INSTALLED=$_GLOBAL["WSGATE_INSTALLED"];}
		if(isset($_GLOBAL["URLSNARF_INSTALLED"])){$this->URLSNARF_INSTALLED=$_GLOBAL["URLSNARF_INSTALLED"];}
		if(isset($_GLOBAL["ETTERCAP_INSTALLED"])){$this->ETTERCAP_INSTALLED=$_GLOBAL["ETTERCAP_INSTALLED"];}
		if(isset($_GLOBAL["PERL_AUTHNTLM"])){$this->PERL_AUTHNTLM=$_GLOBAL["PERL_AUTHNTLM"];}


		if(isset($_GLOBAL["APP_PYAUTHENNTLM"])){$this->APP_PYAUTHENNTLM=$_GLOBAL["APP_PYAUTHENNTLM"];}
		if(isset($_GLOBAL["APP_ZARAFADB_INSTALLED"])){$this->APP_ZARAFADB_INSTALLED=$_GLOBAL["APP_ZARAFADB_INSTALLED"];}

		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FILEZ_WEB"))==1){$this->APP_FILEZ_WEB=true;}else{$this->APP_FILEZ_WEB=false;}







		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FTP_PROXY"))==1){$this->APP_FTP_PROXY=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDInstalled"))==1){$this->NTPDATE=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ISCSID_INSTALLED"))==1){$this->ISCSID_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VDESWITCH_INSTALLED"))==1){$this->VDESWITCH_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_EBTABLES_INSTALLED"))==1){$this->APP_EBTABLES_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SQUID27_INSTALLED"))==1){$this->APP_SQUID27_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WORDPRESS_APPLIANCE"))==1){$this->WORDPRESS_APPLIANCE=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WIFIDOG_INSTALLED"))==1){$this->WIFIDOG_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NETBALANCE_INSTALLED"))==1){$this->NETBALANCE_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHPFPM_INSTALLED"))==1){$this->PHPFPM_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_CONSOLE"))==1){$this->APP_NGINX_CONSOLE=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetDataInstalled"))==1){$this->APP_NETDATA_INSTALLED=true;}




		$this->netbiosname=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("fixed_hostname");
		$this->hostname=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("fixed_hostname");
		$this->fqdn=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("fqdn_hostname");
		$this->mysql_password=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password"));
		$this->mysql_admin=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin"));
		if(is_file("/etc/artica-postfix/settings/Mysql/mysql_server")) {
			$this->mysql_server = trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server"));
		}
		$this->mysql_enabled=1;
		if(isset($_SESSION["uid"])){$this->uid=$_SESSION["uid"];}
		if(isset($_SESSION["groupid"])){$this->groupid=$_SESSION["groupid"];}





		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PUREFTP_INSTALLED"))==1){$this->PUREFTP_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MILTERGREYLIST_INSTALLED"))==1){$this->MILTERGREYLIST_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamScanInstalled"))==1){$this->CLAMSCAN_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SENSORS_INSTALLED"))==1){$this->SENSORS_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SS5Installed"))==1){$this->SS5_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SAMBA_INSTALLED"))==1){$this->SAMBA_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_INSTALLED"))==1){$this->IPTABLES_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SENSORS_INSTALLED"))==1){$this->SENSORS_INSTALLED=true;}


		if(isset($_GLOBAL["ARTICA_FILTER_QUEUE_PATH"])){$this->ARTICA_FILTER_QUEUE_PATH=$_GLOBAL["ARTICA_FILTER_QUEUE_PATH"];}
		if(isset($_GLOBAL["ARTICA_FILTER_MAXSUBQUEUE"])){$this->ARTICA_FILTER_MAXSUBQUEUE=$_GLOBAL["ARTICA_FILTER_MAXSUBQUEUE"];}

		if(isset($_GLOBAL["GeoIPDat_Path"])){$this->GeoIPDat_Path=$_GLOBAL["GeoIPDat_Path"];}

		if($this->ChartLicence==null){$this->ChartLicence='D1XFWGMEW9L.HSK5T4Q79KLYCK07EK';}

		$this->LOCAL_SID=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LOCAL_SID"));

		if(isset($_GLOBAL["POSTFIX_LDAP_COMPLIANCE"])){$this->POSTFIX_LDAP_COMPLIANCE=$_GLOBAL["POSTFIX_LDAP_COMPLIANCE"];}





		if(is_file("/etc/artica-postfix/settings/Daemons/LIGHTTPD_INSTALLED")){if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LIGHTTPD_INSTALLED"))==1){$this->lighttpd_installed=true;}}
		if(is_file("/etc/artica-postfix/settings/Daemons/NTPDInstalled")){if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDInstalled"))==1){$this->NTPD_INSTALLED=true;}}



		if(isset($_GLOBAL["phpcgi"])){$this->phpcgi=$_GLOBAL["phpcgi"];}
		if(isset($_GLOBAL["razor_config"])){$this->razor_config=$_GLOBAL["razor_config"];}
		if(isset($_GLOBAL["razor_installed"])){$this->razor_installed=$_GLOBAL["razor_installed"];}
		if(isset($_GLOBAL["pyzor_installed"])){$this->pyzor_installed=$_GLOBAL["pyzor_installed"];}
		if(isset($_GLOBAL["spfmilter_installed"])){$this->spfmilter_installed=$_GLOBAL["spfmilter_installed"];}



		if(isset($_GLOBAL["GRAPHDEFANG_INSTALLED"])){$this->GRAPHDEFANG_INSTALLED=$_GLOBAL["GRAPHDEFANG_INSTALLED"];}
		if(isset($_GLOBAL["MHONARC_INSTALLED"])){$this->MHONARC_INSTALLED=$_GLOBAL["MHONARC_INSTALLED"];}
		if(isset($_GLOBAL["MHONARC_VERSION"])){$this->MHONARC_VERSION=$_GLOBAL["MHONARC_VERSION"];}
		if(isset($_GLOBAL["spamassassin_installed"])){$this->spamassassin_installed=$_GLOBAL["spamassassin_installed"];}
		if(isset($_GLOBAL["spamassassin_version"])){$this->spamassassin_version=$_GLOBAL["spamassassin_version"];}



		if(isset($_GLOBAL["stunnel4_installed"])){$this->stunnel4_installed=$_GLOBAL["stunnel4_installed"];}
		if(isset($_GLOBAL["DKIMFILTER_INSTALLED"])){$this->DKIMFILTER_INSTALLED=$_GLOBAL["DKIMFILTER_INSTALLED"];}
		if(isset($_GLOBAL["DKIMFILTER_SOCKET"])){$this->DKIMFILTER_SOCKET=$_GLOBAL["DKIMFILTER_SOCKET"];}


		if(isset($_GLOBAL["usb_list"])){$this->usb_list=$_GLOBAL["usb_list"];}

		$this->nmap_installed=false;
		$nmap_installed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NMAPInstalled"));
		if($nmap_installed==1){
			$this->nmap_installed=true;
			$this->NMAP_PATH=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NMAPPath");
			$this->nmap_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NMAPVersion");
		}


		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WANPROXY_INSTALLED"))==1){$this->WANPROXY=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MONIT_INSTALLED"))==1){$this->MONIT_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UCARP_INSTALLED"))==1){$this->UCARP_INSTALLED=true;}

		if(isset($_GLOBAL["fetchmail_configured"])){$this->fetchmail_configured=$_GLOBAL["fetchmail_configured"];}
		if(isset($_GLOBAL["KAV4SAMBA_INSTALLED"])){$this->KAV4SAMBA_INSTALLED=$_GLOBAL["KAV4SAMBA_INSTALLED"];}
		if(isset($_GLOBAL["KAV4SAMBA_VFS"])){$this->KAV4SAMBA_VFS=$_GLOBAL["KAV4SAMBA_VFS"];}
		if(isset($_GLOBAL["SAMBA_MYSQL_AUDIT"])){$this->SAMBA_MYSQL_AUDIT=$_GLOBAL["SAMBA_MYSQL_AUDIT"];}
		if(isset($_GLOBAL["awstats_www_path"])){$this->awstats_www_path=$_GLOBAL["awstats_www_path"];}

		if(isset($_GLOBAL["LIGHTTPD_LDAP_AUTH"])){$this->LIGHTTPD_LDAP_AUTH=$_GLOBAL["LIGHTTPD_LDAP_AUTH"];}
		$this->SIEVE_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SIEVE_PORT"));
		if(isset($_GLOBAL["Z_PUSH_INSTALLED"])){$this->Z_PUSH_INSTALLED=$_GLOBAL["Z_PUSH_INSTALLED"];}



		if(isset($_GLOBAL["SQUID_APPLIANCE"])){$this->SQUID_APPLIANCE=$_GLOBAL["SQUID_APPLIANCE"];}
		if(isset($_GLOBAL["PROXYTINY_APPLIANCE"])){$this->PROXYTINY_APPLIANCE=$_GLOBAL["PROXYTINY_APPLIANCE"];}
		if(isset($_GLOBAL["NETATALK_INSTALLED"])){$this->NETATALK_INSTALLED=$_GLOBAL["NETATALK_INSTALLED"];}
		if(isset($_GLOBAL["QUOTA_INSTALLED"])){$this->QUOTA_INSTALLED=$_GLOBAL["QUOTA_INSTALLED"];}


		if(isset($_GLOBAL["IPBAN_INSTALLED"])){$this->IPBAN_INSTALLED=$_GLOBAL["IPBAN_INSTALLED"];}
		if(isset($_GLOBAL["SMTP_APPLIANCE"])){$this->SMTP_APPLIANCE=$_GLOBAL["SMTP_APPLIANCE"];}
		if(isset($_GLOBAL["LDAP_BACKMETA"])){$this->LDAP_BACKMETA=$_GLOBAL["LDAP_BACKMETA"];}
		if(isset($_GLOBAL["LDAP_BACKLDAP"])){$this->LDAP_BACKLDAP=$_GLOBAL["LDAP_BACKLDAP"];}
		if(isset($_GLOBAL["LDAP_BACKRELAY"])){$this->LDAP_BACKLDAP=$_GLOBAL["LDAP_BACKRELAY"];}
		if(isset($_GLOBAL["LDAP_RWM"])){$this->LDAP_RWM=$_GLOBAL["LDAP_RWM"];}

		if(is_file("/etc/artica-postfix/FROM_ISO")){$this->FROM_ISO=true;}
		if(isset($_GLOBAL["APP_JAVA_JDK_INSTALLED"])){$this->APP_JAVA_JDK_INSTALLED=$_GLOBAL["APP_JAVA_JDK_INSTALLED"];}
		if(isset($_GLOBAL["APP_CHILLI_INSTALLED"])){$this->APP_CHILLI_INSTALLED=$_GLOBAL["APP_CHILLI_INSTALLED"];}


		$RDPProxyInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyInstalled"));
		if($RDPProxyInstalled==1){$this->RDPPROXY_INSTALLED=true;}
		if(isset($_GLOBAL["PACTESTER_INSTALLED"])){$this->PACTESTER_INSTALLED=$_GLOBAL["PACTESTER_INSTALLED"];}
		if(isset($_GLOBAL["CONNTRACK_INSTALLED"])){$this->CONNTRACK_INSTALLED=$_GLOBAL["CONNTRACK_INSTALLED"];}


		if(isset($_GLOBAL["BAMSIGHT"])){$this->BAMSIGHT=$_GLOBAL["BAMSIGHT"];}

		if(isset($_GLOBAL["LANWANSAT"])){$this->LANWANSAT=$_GLOBAL["LANWANSAT"];}
		if(isset($_GLOBAL["FIREHOL"])){$this->FIREHOL=$_GLOBAL["FIREHOL"];}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XEN_HOST"))==1){$this->XEN_HOST=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HYPERV_HOST"))==1){$this->HYPERV_HOST=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WEBSECURIZE"))==1){$this->WEBSECURIZE=true;}

		if(is_file("/etc/artica-postfix/MYCOSI_APPLIANCE")){$this->MYCOSI_APPLIANCE=True;}
		if(is_file("/etc/artica-postfix/SQUID_APPLIANCE")){$this->SQUID_APPLIANCE=True;}
		if(is_file("/etc/artica-postfix/LOAD_BALANCE_APPLIANCE")){$this->LOAD_BALANCE_APPLIANCE=True;}
		if(is_file("/etc/artica-postfix/SAMBA_APPLIANCE")){$this->SAMBA_APPLIANCE=True;}
		if(is_file("/etc/artica-postfix/GATEWAY_APPLIANCE")){$this->GATEWAY_APPLIANCE=True;}


		if(isset($_GLOBAL["LIGHT_INSTALL"])){$this->LIGHT_INSTALL=$_GLOBAL["LIGHT_INSTALL"];}
		// Are you sure to do that !!!, breaking the license turn you to a pirate...

		if(isset($_GLOBAL["bindrrd_installed"])){$this->bindrrd_installed=$_GLOBAL["bindrrd_installed"];}
		if(isset($_GLOBAL["fdm_installed"])){$this->fdm_installed=$_GLOBAL["fdm_installed"];}
		if(isset($_GLOBAL["fdm_cache"])){$this->fdm_cache=$_GLOBAL["fdm_cache"];}
		if(isset($_GLOBAL["p3scan_installed"])){$this->p3scan_installed=$_GLOBAL["p3scan_installed"];}
		if(isset($_GLOBAL["syslogng_installed"])){$this->syslogng_installed=$_GLOBAL["syslogng_installed"];}
		if(isset($_GLOBAL["AsDebianSystem"])){$this->AsDebianSystem=$_GLOBAL["AsDebianSystem"];}
		if(trim($this->hostname)==null){$this->hostname=$this->fqdn;}
		if(isset($_GLOBAL["cpumhz"])){$this->cpumhz=$_GLOBAL["cpumhz"];}
		if(isset($_GLOBAL["preload_installed"])){$this->preload_installed=$_GLOBAL["preload_installed"];}
		if(isset($_GLOBAL["POSTFIX_MAILDROP_PATH"])){$this->POSTFIX_MAILDROP_PATH=$_GLOBAL["POSTFIX_MAILDROP_PATH"];}
		if(isset($_GLOBAL["smbmount_installed"])){$this->smbmount_installed=$_GLOBAL["smbmount_installed"];}
		if(isset($_GLOBAL["PUREFTP_USERS"])){$this->PUREFTP_USERS=$_GLOBAL["PUREFTP_USERS"];}
		if(isset($_GLOBAL["roundcube_version"])){$this->roundcube_version=$_GLOBAL["roundcube_version"];}

		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("openldap_installed"))==1){$this->openldap_installed=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVirtualDomainsInMailBoxes"))==1){$this->EnableVirtualDomainsInMailBoxes=true;}
		if(isset($_GLOBAL["collectd_installed"])){$this->collectd_installed=$_GLOBAL["collectd_installed"];}
		if(isset($_GLOBAL["COLLECTD_RRD"])){$this->COLLECTD_RRD=$_GLOBAL["COLLECTD_RRD"];}

		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MILTER_SPY_INSTALLED"))==1){$this->MILTER_SPY_INSTALLED=true;}
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
		if(isset($_GLOBAL["GNARWL_INSTALLED"])){$this->GNARWL_INSTALLED=$_GLOBAL["GNARWL_INSTALLED"];}


		if(isset($_GLOBAL["PFLOGSUMM_INSTALLED"])){$this->PFLOGSUMM_INSTALLED=$_GLOBAL["PFLOGSUMM_INSTALLED"];}
		if(isset($_GLOBAL["maillog_path"])){$this->maillog_path=$_GLOBAL["maillog_path"];}
		if(isset($_GLOBAL["spamassassin_ipcountry"])){$this->spamassassin_ipcountry=$_GLOBAL["spamassassin_ipcountry"];}
		if(isset($_GLOBAL["spamassassin_conf_path"])){$this->spamassassin_conf_path=$_GLOBAL["spamassassin_conf_path"];}
		if(isset($_GLOBAL["CUPS_INSTALLED"])){$this->CUPS_INSTALLED=$_GLOBAL["CUPS_INSTALLED"];}


		$this->SMARTMONTOOLS_INSTALLED=false;
		if(is_file("/etc/artica-postfix/settings/Daemons/SMARTDiskInstalled")){
			$SMARTMONTOOLS_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SMARTDiskInstalled"));
			if($SMARTMONTOOLS_INSTALLED==1){$this->SMARTMONTOOLS_INSTALLED=true;}
		}



		if(isset($_GLOBAL["SARG_INSTALLED"])){$this->SARG_INSTALLED=$_GLOBAL["SARG_INSTALLED"];}
		if(is_file("/etc/artica-postfix/settings/Daemons/XAPIAN_PHP_INSTALLED")){if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XAPIAN_PHP_INSTALLED"))==1){$this->XAPIAN_PHP_INSTALLED=True;}}


		if(isset($_GLOBAL["APACHE_GROUPWARE_INSTALLED"])){$this->APACHE_GROUPWARE_INSTALLED=$_GLOBAL["APACHE_GROUPWARE_INSTALLED"];}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DSTAT_INSTALLED"))==1){$this->DSTAT_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GNUPLOT_PNG"))==1){$this->GNUPLOT_PNG=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IOSTAT_INSTALLED"))==1){$this->IOSTAT_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPAUDIT_INSTALLED"))==1){$this->IPAUDIT_INSTALLED=true;}



		if(isset($_GLOBAL["POSTFIX_QUEUE"])){$this->POSTFIX_QUEUE=$_GLOBAL["POSTFIX_QUEUE"];}
		$this->PHP_BIN_PATH=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHP_BIN_PATH");
		$this->EXEC_NICE=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EXEC_NICE");
		if(isset($_GLOBAL["spamassassin_bin_path"])){$this->spamassassin_bin_path=$_GLOBAL["spamassassin_bin_path"];}
		$this->LinuxDistriCode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LinuxDistributionCodeName"));




		if(isset($_GLOBAL["smbclient_installed"])){$this->smbclient_installed=$_GLOBAL["smbclient_installed"];}
		if(isset($_GLOBAL["AMAVISD_VERSION"])){$this->AMAVISD_VERSION=$_GLOBAL["AMAVISD_VERSION"];}
		if(isset($_GLOBAL["cyrus_murder_installed"])){$this->cyrus_murder_installed=$_GLOBAL["cyrus_murder_installed"];}
		if(isset($_GLOBAL["cyrus_sync_installed"])){$this->cyrus_sync_installed=$_GLOBAL["cyrus_sync_installed"];}
		if(isset($_GLOBAL["FETCHMAIL_VERSION"])){$this->FETCHMAIL_VERSION=$_GLOBAL["FETCHMAIL_VERSION"];}
		if(isset($_GLOBAL["cyrus_sync_client_path"])){$this->cyrus_sync_client_path=$_GLOBAL["cyrus_sync_client_path"];}
		if(isset($_GLOBAL["ctl_mboxlist"])){$this->ctl_mboxlist=$_GLOBAL["ctl_mboxlist"];}
		if(isset($_GLOBAL["CURL_PATH"])){$this->CURL_PATH=$_GLOBAL["CURL_PATH"];}
		if(isset($_GLOBAL["DANSGUARDIAN_VERSION"])){$this->DANSGUARDIAN_VERSION=$_GLOBAL["DANSGUARDIAN_VERSION"];}
		if(isset($_GLOBAL["nmap_path"])){$this->NMAP_PATH=$_GLOBAL["nmap_path"];}
		if(isset($_GLOBAL["msmtp_installed"])){$this->msmtp_installed=$_GLOBAL["msmtp_installed"];}
		if(isset($_GLOBAL["nmap_version"])){$this->nmap_version=$_GLOBAL["nmap_version"];}



		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APACHE_INSTALLED"))==1){$this->APACHE_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AutoFSInstalled"))==1){$this->autofs_installed=true;}



		if($this->APACHE_INSTALLED){
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("mpm_itk_module"))==1){$this->MPM_ITK_MODULE=true;}
			if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("mod_dav"))==1){$this->APACHE_MODE_WEBDAV=true;}
		}

		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NFS_SERVER_INTSALLED"))==1){$this->NFS_SERVER_INTSALLED=true;}
		if(isset($_GLOBAL["LVM_INSTALLED"])){$this->LVM_INSTALLED=$_GLOBAL["LVM_INSTALLED"];}
		if(isset($_GLOBAL["MYSQL_NDB_MGMD_INSTALLED"])){$this->MYSQL_NDB_MGMD_INSTALLED=$_GLOBAL["MYSQL_NDB_MGMD_INSTALLED"];}
		if(isset($_GLOBAL["winexe_installed"])){$this->winexe_installed=$_GLOBAL["winexe_installed"];}
		if(isset($_GLOBAL["ASSP_INSTALLED"])){$this->ASSP_INSTALLED=$_GLOBAL["ASSP_INSTALLED"];}
		if(isset($_GLOBAL["POLICYD_WEIGHT_PORT"])){$this->POLICYD_WEIGHT_PORT=$_GLOBAL["POLICYD_WEIGHT_PORT"];}
		if(isset($_GLOBAL["ASSP_INSTALLED"])){$this->ASSP_INSTALLED=$_GLOBAL["ASSP_INSTALLED"];}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MEM_HIGER_1G"))==1){$this->MEM_HIGER_1G=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSInstalled"))==1){$this->POWER_DNS_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerAdminInstalled"))==1){$this->POWERADMIN_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSSecInstalled"))==1){$this->PDNSSEC_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonInstalled"))==1){$this->CLAMD_INSTALLED=true;}

		$this->mysqld_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("mysqld_version");
		$this->SLAPD_CONF_PATH="/etc/ldap/slapd.conf";
		if(isset($_GLOBAL["cyrus_squatter_exists"])){$this->cyrus_squatter_exists=$_GLOBAL["cyrus_squatter_exists"];}
		if(isset($_GLOBAL["SCANNED_ONLY_INSTALLED"])){$this->SCANNED_ONLY_INSTALLED=$_GLOBAL["SCANNED_ONLY_INSTALLED"];}
		if(isset($_GLOBAL["CRYPTSETUP_INSTALLED"])){$this->CRYPTSETUP_INSTALLED=$_GLOBAL["CRYPTSETUP_INSTALLED"];}
		if(isset($_GLOBAL["ZABBIX_INSTALLED"])){$this->ZABBIX_INSTALLED=$_GLOBAL["ZABBIX_INSTALLED"];}

		if(isset($_GLOBAL["cyrus_ipurge_exists"])){$this->cyrus_ipurge_exists=$_GLOBAL["cyrus_ipurge_exists"];}
		$this->POSTFIX_MULTI=$this->postfixmutli();
		if(isset($_GLOBAL["GLUSTER_INSTALLED"])){$this->GLUSTER_INSTALLED=$_GLOBAL["GLUSTER_INSTALLED"];}

		$this->MEM_TOTAL_INSTALLEE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MEM_TOTAL_INSTALLEE"));

		if(isset($_GLOBAL["POSTFILTER_INSTALLED"])){$this->POSTFILTER_INSTALLED=$_GLOBAL["POSTFILTER_INSTALLED"];}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_HOST"))==1){$this->VMWARE_HOST=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_TOOLS_INSTALLED"))==1){$this->VMWARE_TOOLS_INSTALLED=true;}
		if(isset($_GLOBAL["phpldapadmin_installed"])){$this->phpldapadmin_installed=$_GLOBAL["phpldapadmin_installed"];}
		if(isset($_GLOBAL["phpmyadmin_installed"])){$this->phpmyadmin_installed=$_GLOBAL["phpmyadmin_installed"];}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FREERADIUS_INSTALLED"))==1){$this->FREERADIUS_INSTALLED=true;}
		$this->SQUID_BIN_PATH=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_BIN_PATH");
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOSTAPD_INSTALLED"))==1){$this->HOSTAPD_INSTALLED=true;}
		if(is_file("/etc/artica-postfix/settings/Daemons/WPA_SUPPLIANT_INSTALLED")){if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WPA_SUPPLIANT_INSTALLED"))==1){$this->WPA_SUPPLIANT_INSTALLED=true;}}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARPD_INSTALLED"))==1){$this->ARPD_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MEMCACHED_INSTALLED"))==1){$this->MEMCACHED_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAMACHI_INSTALLED"))==1){$this->HAMACHI_INSTALLED=true;}



		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KASPERSKY_WEB_APPLIANCE"))==1){$this->KASPERSKY_WEB_APPLIANCE=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_ULOGD_INSTALLED"))==1){$this->APP_ULOGD_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_ZIP_INSTALLED"))==1){$this->APP_ZIP_INSTALLED=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_OPENVPN_PYTHON"))==1){$this->APP_OPENVPN_PYTHON=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DAVSERVER"))==1){$this->APP_DAVSERVER=true;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RSYNC_INSTALLED"))==1){$this->RSYNC_INSTALLED=true;}

		$this->ArchStruct=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArchStruct"));

		if(is_file("/etc/artica-postfix/ZARAFA_APPLIANCE")){$this->ZARAFA_APPLIANCE=True;}
		if(isset($_GLOBAL["mysqld_datadir"])){$this->mysqld_datadir=$_GLOBAL["mysqld_datadir"];}

		if(isset($_GLOBAL["MAIL_DKIM_VERSION"])){$this->MAIL_DKIM_VERSION=trim($_GLOBAL["MAIL_DKIM_VERSION"]);}
		if(isset($_GLOBAL["HDPARM_INSTALLED"])){$this->HDPARM_INSTALLED=trim($_GLOBAL["HDPARM_INSTALLED"]);}


		//error detected ?
		$this->hostname=str_replace('.(none)','',$this->hostname);
		$this->fqdn=str_replace('.(none)','',$this->fqdn);

		if(isset($_GLOBAL["OCS_LNX_AGENT_INSTALLED"])){$this->OCS_LNX_AGENT_INSTALLED=$_GLOBAL["OCS_LNX_AGENT_INSTALLED"];}
		if(isset($_GLOBAL["POSTFIX_PCRE_COMPLIANCE"])){$this->POSTFIX_PCRE_COMPLIANCE=$_GLOBAL["POSTFIX_PCRE_COMPLIANCE"];};
		if(isset($_GLOBAL["APP_AUDITD_INSTALLED"])){$this->APP_AUDITD_INSTALLED=$_GLOBAL["APP_AUDITD_INSTALLED"];}
		if(isset($_GLOBAL["DKFILTER_INSTALLED"])){$this->DKFILTER_INSTALLED=$_GLOBAL["DKFILTER_INSTALLED"];}
		if(isset($_GLOBAL["OPENDKIM_INSTALLED"])){$this->OPENDKIM_INSTALLED=$_GLOBAL["OPENDKIM_INSTALLED"];}
		if(isset($_GLOBAL["DB_RECOVER_INSTALLED"])){$this->DB_RECOVER_INSTALLED=$_GLOBAL["DB_RECOVER_INSTALLED"];}




		$this->APP_UFDBGUARD_INSTALLED=false;
		$APP_UFDBGUARD_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_UFDBGUARD_INSTALLED"));
		if($APP_UFDBGUARD_INSTALLED==1){
			$this->APP_UFDBGUARD_INSTALLED=true;
			$this->ufdbgclient_path=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbgclient_path");
		}

		if(is_file("/etc/artica-postfix/settings/Daemons/DHCPDInstalled")){
			$DHCPDInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDInstalled"));
			if($DHCPDInstalled==1){$this->dhcp_installed=true;}
		}





		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KINIT_INSTALLED"))==1){$this->KINIT_INSTALLED=True;}
		if(isset($_GLOBAL["DKIMPROXY_INSTALLED"])){$this->DKIMPROXY_INSTALLED=$_GLOBAL["DKIMPROXY_INSTALLED"];}
		if(isset($_GLOBAL["MILTER_DKIM_INSTALLED"])){$this->MILTER_DKIM_INSTALLED=$_GLOBAL["MILTER_DKIM_INSTALLED"];}
		if(isset($_GLOBAL["VIRTUALBOX_INSTALLED"])){$this->VIRTUALBOX_INSTALLED=$_GLOBAL["VIRTUALBOX_INSTALLED"];}

		if(isset($_GLOBAL["THINSTATION_INSTALLED"])){$this->THINSTATION_INSTALLED=$_GLOBAL["THINSTATION_INSTALLED"];}
		$this->OPENSSH_VER=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENSSH_VER");
		if(isset($_GLOBAL["cyrus_syncserver_installed"])){$this->cyrus_syncserver_installed=$_GLOBAL["cyrus_syncserver_installed"];}



		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PPTPD_INSTALLED"))==1){$this->PPTPD_INSTALLED=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PPTP_INSTALLED"))==1){$this->PPTP_INSTALLED=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPSEC_INSTALLED"))==1){$this->IPSEC_INSTALLED=True;}


		$this->tc_version=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TC_VERSION"));
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TC_INSTALLED"))==1){$this->qos_tools_installed=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LDAP_SYNCPROV"))==1){$this->LDAP_SYNCPROV=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TFTPD_INSTALLED"))==1){$this->TFTPD_INSTALLED=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_VBOXADDINTION_INSTALLED"))==1){$this->APP_VBOXADDINTION_INSTALLED=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VIRTUALBOX_HOST"))==1){$this->VIRTUALBOX_HOST=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SABNZBDPLUS_INSTALLED"))==1){$this->APP_SABNZBDPLUS_INSTALLED;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTSCREEN_INSTALLED"))==1){$this->POSTSCREEN_INSTALLED=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPSET_INSTALLED"))==1){$this->IPSET_INSTALLED=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KASPERSKY_UPDATE_UTILITY_INSTALLED"))==1){$this->KASPERSKY_UPDATE_UTILITY_INSTALLED=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ASPELL_INSTALLED"))==1){$this->ASPELL_INSTALLED=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"))==1){$this->MUNIN_CLIENT_INSTALLED;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AS_VPS_CLIENT"))==1){$this->AS_VPS_CLIENT=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("offlineimap_installed"))==1){$this->offlineimap_installed=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VLAN_INSTALLED"))==1){$this->VLAN_INSTALLED=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LOSETUP_INSTALLED"))==1){$this->LOSETUP_INSTALLED=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ISCSI_INSTALLED"))==1){$this->ISCSI_INSTALLED=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WORDPRESS_INSTALLED"))==1){$this->WORDPRESS_INSTALLED=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WINBINDD_INSTALLED"))==1){$this->WINBINDD_INSTALLED=True;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_ACCOUNTING_EXISTS"))==1){$this->IPTABLES_ACCOUNTING_EXISTS=True;}




		$MSKTUTIL_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MSKTUTIL_INSTALLED"));
		if($MSKTUTIL_INSTALLED==1){$this->MSKTUTIL_INSTALLED=True;}

		$CGROUPS_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CGROUPS_INSTALLED"));
		if($CGROUPS_INSTALLED==1){
			$this->CGROUPS_INSTALLED=true;
		}else{
			$this->CGROUPS_INSTALLED=false;
		}




		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ISCSI_CLIENT_INSTALLED"))==1){$this->ISCSI_CLIENT_INSTALLED=True;}
		if($this->LinuxDistriCode=="DEBIAN"){$this->AS_DEBIAN_FAMILY=true;}
		if($this->LinuxDistriCode=="UBUNTU"){$this->AS_DEBIAN_FAMILY=true;}

		if(isset($_GLOBAL["deduplication_installed"])){$this->deduplication_installed=$_GLOBAL["deduplication_installed"];}

		if(!isset($GLOBALS["CLASS_SOCKETS"])){$sock=new sockets();$GLOBALS["CLASS_SOCKETS"]=$sock;}
		if(!is_object($GLOBALS["CLASS_SOCKETS"])){$sock=new sockets();$GLOBALS["CLASS_SOCKETS"]=$sock;}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
		$ArticaMetaEnabled=trim($sock->GET_INFO("ArticaMetaEnabled"));
		if($ArticaMetaEnabled==null){$sock->SET_INFO("ArticaMetaEnabled","0");}
		if($ArticaMetaEnabled==1){$this->ARTICA_META_ENABLED=true;}

		if($this->SQUID_INSTALLED){
			if(!isset($GLOBALS["SQUID_ICAP_ENABLED"])){
				$GLOBALS["SQUID_ICAP_ENABLED"]=false;
				$IsIcapClient=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("IsIcapClient");
				if($IsIcapClient==null){
					$IsIcapClient=trim($sock->getFrameWork("squid.php?IsIcapClient=yes"));
					if($IsIcapClient==null){$IsIcapClient="FALSE";}
					$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IsIcapClient", $IsIcapClient);
				}
				if($IsIcapClient=='TRUE'){$GLOBALS["SQUID_ICAP_ENABLED"]=true; }
			}


		}

		if(isset($GLOBALS["SQUID_ICAP_ENABLED"])){$this->SQUID_ICAP_ENABLED=$GLOBALS["SQUID_ICAP_ENABLED"]; }




		$EnableMacAddressFilter=$sock->GET_INFO("EnableMacAddressFilter");

		$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");


		if(!is_numeric($EnableMacAddressFilter)){$EnableMacAddressFilter=1;}

		if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}



		if($this->SQUID_ARP_ACL_ENABLED){if($EnableMacAddressFilter==0){$this->SQUID_ARP_ACL_ENABLED=false;}}

		$pear=explode(";",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PEAR_PACKAGES"));
		if(is_array($pear)){
			foreach ($pear as $pp){
				if(trim($pp)==null){continue;}
				$this->PEAR_PACKAGES[$pp]=true;
			}
		}

		if(!$this->APACHE_MOD_PYTHON){$this->APP_PYAUTHENNTLM=false;}
        if(is_file("/etc/artica-postfix/CPU_NUMBER")) {
            $this->CPU_NUMBER = intval(@file_get_contents("/etc/artica-postfix/CPU_NUMBER"));
        }


		if($this->OPENVPN_APPLIANCE){
			$this->SQUID_INSTALLED=false;
			$this->SAMBA_INSTALLED=false;
			$this->POSTFIX_INSTALLED=false;
			$this->cyrus_imapd_installed=false;
			$this->ZARAFA_INSTALLED=false;
			$this->APP_AUDITD_INSTALLED=false;

			$this->nmap_installed=false;
		}

		if($this->PROXYTINY_APPLIANCE){
			$this->SQUID_APPLIANCE=true;
		}


		$this->SetTimeZone();
		$this->SessionExpired();
		$this->aghjkyrtij();

        if(!isset($GLOBALS["DEBUG_PRIVS"])){$GLOBALS["DEBUG_PRIVS"]=false;}
		$AsArticaAdministrator=false;

		//Parse privileges
		if(!$GLOBALS["AS_ROOT"]){
			if($GLOBALS["DEBUG_PRIVS"]){echo "<hr><code style='color:black'> _ParsePrivieleges()" .__CLASS__.'/'.__FUNCTION__."/".basename(__FILE__)."/".__LINE__."</code><br>\n";}
			$priv=$this->_ParsePrivieleges();
			if($GLOBALS["DEBUG_PRIVS"]){echo "<hr><code style='color:black'> _TranslateRights(priv)" .__CLASS__.'/'.__FUNCTION__."/".basename(__FILE__)."/".__LINE__."</code><br>\n";}
			$this->priv=$this->_TranslateRights($priv);
		}else{
			$this->AsSystemAdministrator=true;
			$this->AsInventoryAdmin=true;
			$this->AsAnAdministratorGeneric=true;
			$this->AsPostfixAdministrator=true;
			$this->AsSambaAdministrator=true;
			$this->AsWebMaster=true;
            $this->AsWebSecurity=true;
			$this->AsSquidAdministrator=true;
			$this->AsArticaMetaAdmin=true;
			$this->AsDansGuardianAdministrator=true;
			$this->AsSquidPersonalCategories=true;
			$this->AsArticaAdministrator=true;
			$this->AsDebianSystem=true;
			$this->AsMailBoxAdministrator=true;
            $this->AsDatabaseAdministrator=true;
			if($this->WEBSECURIZE){$GLOBALS["CHANGE_TEMPLATE"]="Websecurize";};
			if($this->BAMSIGHT){$GLOBALS["CHANGE_TEMPLATE"]="BamSight";};
			if($this->LANWANSAT){$GLOBALS["CHANGE_TEMPLATE"]="LanWanSAT";};
		}

		if(isset($_SESSION["uid"])){
			if($_SESSION["uid"]==-100){$AsArticaAdministrator=true;}
			if($this->AsArticaAdministrator==true){$AsArticaAdministrator=true;}
		}
		if($AsArticaAdministrator){$this->AllRights();}
		if($this->AsSystemAdministrator){$this->SystemAdmin();}
		if($this->AsSquidAdministrator){$this->ProxyAdmin();}
		if($this->AsDnsAdministrator){$this->AsAnAdministratorGeneric=true;}
        if($this->AsDatabaseAdministrator){$this->AsAnAdministratorGeneric=true;}
        if($this->AsWebMaster){
            $this->AsAnAdministratorGeneric=true;
            $this->AsWebSecurity=true;
            $this->AsWebMonitor=true;
        }
        if($this->AsWebSecurity){
            $this->AsWebMonitor=true;
            $this->AsAnAdministratorGeneric=true;}
        if($this->AsWebMonitor){
            $this->AsAnAdministratorGeneric=true;
        }

		$this->CORP_LICENSE=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
		if($this->CORP_LICENSE){VERBOSE("License!: OK",__LINE__);}
	}


	public function isVPNAdminOnly(){
        if($this->AsDatabaseAdministrator==true){return false;}
		if($this->AsSystemAdministrator==true){return false;}
		if($this->AsInventoryAdmin==true){return false;}
		if($this->AsAnAdministratorGeneric==true){return false;}
		if($this->AsPostfixAdministrator==true){return false;}
		if($this->AsSambaAdministrator==true){return false;}
		if($this->AsWebMaster==true){return false;}
		if($this->AsSquidAdministrator==true){return false;}
		if($this->AsArticaMetaAdmin==true){return false;}
		if($this->AsDansGuardianAdministrator==true){return false;}
		if($this->AsArticaAdministrator==true){return false;}
		if($this->AsHotSpotManager==true){return false;}
		if($this->AsProxyMonitor==true){return false;}
		if($this->AllowViewStatistics==true){return false;}
		if($this->ASDCHPAdmin==true){return false;}
		if($this->AsCertifsManager==true){return false;}
		if($this->AllowAddGroup==true){return false;}
		if($this->AllowAddUsers==true){return false;}
		if($this->AllowChangeUserPassword==true){return false;}
		if($this->AsDnsAdministrator==true){return false;}
		if($this->AsWebStatisticsAdministrator==true){return false;}
		if($this->AsPostfixAdministrator==true){return false;}
		if($this->AsMailBoxAdministrator==true){return false;}
		if($this->AsOrgAdmin==true){return false;}
		if($this->AsMailManAdministrator==true){return false;}
		if($this->AsMessagingOrg==true){return false;}
		if($this->AsOrgDNSAdmin==true){return false;}
		if($this->AsVirtualBoxManager==true){return false;}
		if($this->AsOrgPostfixAdministrator==true){return false;}
		if($this->AsQuarantineAdministrator==true){return false;}
        if($this->AsWebSecurity=true){return false;}
        if($this->AsWebMonitor=true){return false;}

		if($this->AsVPNManager==true){return true;}
        return false;

	}

	public function isFireWallAdminOnly():bool{
        if($this->AsDatabaseAdministrator==true){return false;}
		if($this->AsSystemAdministrator==true){return false;}
		if($this->AsInventoryAdmin==true){return false;}
		if($this->AsAnAdministratorGeneric==true){return false;}
		if($this->AsPostfixAdministrator==true){return false;}
		if($this->AsSambaAdministrator==true){return false;}
		if($this->AsWebMaster==true){return false;}
		if($this->AsSquidAdministrator==true){return false;}
		if($this->AsArticaMetaAdmin==true){return false;}
		if($this->AsDansGuardianAdministrator==true){return false;}
		if($this->AsArticaAdministrator==true){return false;}
		if($this->AsHotSpotManager==true){return false;}
		if($this->AsProxyMonitor==true){return false;}
		if($this->AllowViewStatistics==true){return false;}
		if($this->ASDCHPAdmin==true){return false;}
		if($this->AsCertifsManager==true){return false;}
		if($this->AllowAddGroup==true){return false;}
		if($this->AllowAddUsers==true){return false;}
		if($this->AllowChangeUserPassword==true){return false;}
		if($this->AsDnsAdministrator==true){return false;}
		if($this->AsWebStatisticsAdministrator==true){return false;}
		if($this->AsPostfixAdministrator==true){return false;}
		if($this->AsMailBoxAdministrator==true){return false;}
		if($this->AsOrgAdmin==true){return false;}
		if($this->AsMailManAdministrator==true){return false;}
		if($this->AsMessagingOrg==true){return false;}
		if($this->AsOrgDNSAdmin==true){return false;}
		if($this->AsVirtualBoxManager==true){return false;}
		if($this->AsOrgPostfixAdministrator==true){return false;}
		if($this->AsQuarantineAdministrator==true){return false;}
		if($this->AsVPNManager==true){return false;}
        if($this->AsWebSecurity=true){return false;}
        if($this->AsWebMonitor=true){return false;}
		if($this->AsFirewallManager==true){return true;}
        return false;
	}

	public function isProxyMonitorOnly(){
        if($this->AsDatabaseAdministrator==true){return false;}
		if($this->AsSystemAdministrator==true){return false;}
		if($this->AsInventoryAdmin==true){return false;}
		if($this->AsAnAdministratorGeneric==true){return false;}
		if($this->AsPostfixAdministrator==true){return false;}
		if($this->AsSambaAdministrator==true){return false;}
		if($this->AsWebMaster==true){return false;}
		if($this->AsArticaMetaAdmin==true){return false;}
		if($this->AsDansGuardianAdministrator==true){return false;}
		if($this->AsArticaAdministrator==true){return false;}
		if($this->AsHotSpotManager==true){return false;}
		if($this->AllowViewStatistics==true){return false;}
		if($this->ASDCHPAdmin==true){return false;}
		if($this->AsCertifsManager==true){return false;}
		if($this->AllowAddGroup==true){return false;}
		if($this->AllowAddUsers==true){return false;}
		if($this->AllowChangeUserPassword==true){return false;}
		if($this->AsDnsAdministrator==true){return false;}
		if($this->AsWebStatisticsAdministrator==true){return false;}
		if($this->AsPostfixAdministrator==true){return false;}
		if($this->AsMailBoxAdministrator==true){return false;}
		if($this->AsOrgAdmin==true){return false;}
		if($this->AsMailManAdministrator==true){return false;}
		if($this->AsMessagingOrg==true){return false;}
		if($this->AsOrgDNSAdmin==true){return false;}
		if($this->AsVirtualBoxManager==true){return false;}
		if($this->AsOrgPostfixAdministrator==true){return false;}
		if($this->AsQuarantineAdministrator==true){return false;}
		if($this->AsVPNManager==true){return false;}
		if($this->AsSquidAdministrator==true){return false;}
        if($this->AsWebSecurity=true){return false;}
        if($this->AsWebMonitor=true){return false;}
		if($this->AsProxyMonitor==true){return true;}

	}

	public function isWebStatisticsAdministratorOnly(){
        if($this->AsDatabaseAdministrator==true){return false;}
		if($this->AsSystemAdministrator==true){return false;}
		if($this->AsInventoryAdmin==true){return false;}
		if($this->AsAnAdministratorGeneric==true){return false;}
		if($this->AsPostfixAdministrator==true){return false;}
		if($this->AsSambaAdministrator==true){return false;}
		if($this->AsWebMaster==true){return false;}
		if($this->AsArticaMetaAdmin==true){return false;}
		if($this->AsDansGuardianAdministrator==true){return false;}
		if($this->AsArticaAdministrator==true){return false;}
		if($this->AsHotSpotManager==true){return false;}
		if($this->AllowViewStatistics==true){return false;}
		if($this->ASDCHPAdmin==true){return false;}
		if($this->AsCertifsManager==true){return false;}
		if($this->AllowAddGroup==true){return false;}
		if($this->AllowAddUsers==true){return false;}
		if($this->AllowChangeUserPassword==true){return false;}
		if($this->AsDnsAdministrator==true){return false;}
		if($this->AsPostfixAdministrator==true){return false;}
		if($this->AsMailBoxAdministrator==true){return false;}
		if($this->AsOrgAdmin==true){return false;}
		if($this->AsMailManAdministrator==true){return false;}
		if($this->AsMessagingOrg==true){return false;}
		if($this->AsOrgDNSAdmin==true){return false;}
		if($this->AsVirtualBoxManager==true){return false;}
		if($this->AsOrgPostfixAdministrator==true){return false;}
		if($this->AsQuarantineAdministrator==true){return false;}
		if($this->AsVPNManager==true){return false;}
		if($this->AsSquidAdministrator==true){return false;}
		if($this->AsProxyMonitor==true){return false;}
		if($this->AsWebStatisticsAdministrator==true){return true;}

	}
	public function isCertifManagerOnly(){
        if($this->AsDatabaseAdministrator==true){return false;}
		if($this->AsSystemAdministrator==true){return false;}
		if($this->AsInventoryAdmin==true){return false;}
		if($this->AsAnAdministratorGeneric==true){return false;}
		if($this->AsPostfixAdministrator==true){return false;}
		if($this->AsSambaAdministrator==true){return false;}
		if($this->AsWebMaster==true){return false;}
		if($this->AsArticaMetaAdmin==true){return false;}
		if($this->AsDansGuardianAdministrator==true){return false;}
		if($this->AsArticaAdministrator==true){return false;}
		if($this->AsHotSpotManager==true){return false;}
		if($this->AllowViewStatistics==true){return false;}
		if($this->ASDCHPAdmin==true){return false;}
		if($this->AllowAddGroup==true){return false;}
		if($this->AllowAddUsers==true){return false;}
		if($this->AllowChangeUserPassword==true){return false;}
		if($this->AsDnsAdministrator==true){return false;}
		if($this->AsWebStatisticsAdministrator==true){return false;}
		if($this->AsPostfixAdministrator==true){return false;}
		if($this->AsMailBoxAdministrator==true){return false;}
		if($this->AsOrgAdmin==true){return false;}
		if($this->AsMailManAdministrator==true){return false;}
		if($this->AsMessagingOrg==true){return false;}
		if($this->AsOrgDNSAdmin==true){return false;}
		if($this->AsVirtualBoxManager==true){return false;}
		if($this->AsOrgPostfixAdministrator==true){return false;}
		if($this->AsQuarantineAdministrator==true){return false;}
		if($this->AsVPNManager==true){return false;}
		if($this->AsSquidAdministrator==true){return false;}
		if($this->AsProxyMonitor==true){return false;}

		if($this->AsCertifsManager==true){return true;}

	}

	public function isProxyManagerOnly(){
        if($this->AsDatabaseAdministrator==true){return false;}
		if($this->AsSystemAdministrator==true){return false;}
		if($this->AsInventoryAdmin==true){return false;}
		if($this->AsAnAdministratorGeneric==true){return false;}
		if($this->AsPostfixAdministrator==true){return false;}
		if($this->AsSambaAdministrator==true){return false;}
		if($this->AsWebMaster==true){return false;}
		if($this->AsArticaMetaAdmin==true){return false;}
		if($this->AsDansGuardianAdministrator==true){return false;}
		if($this->AsArticaAdministrator==true){return false;}
		if($this->AsHotSpotManager==true){return false;}
		if($this->AllowViewStatistics==true){return false;}
		if($this->ASDCHPAdmin==true){return false;}
		if($this->AsCertifsManager==true){return false;}
		if($this->AllowAddGroup==true){return false;}
		if($this->AllowAddUsers==true){return false;}
		if($this->AllowChangeUserPassword==true){return false;}
		if($this->AsDnsAdministrator==true){return false;}
		if($this->AsWebStatisticsAdministrator==true){return false;}
		if($this->AsPostfixAdministrator==true){return false;}
		if($this->AsMailBoxAdministrator==true){return false;}
		if($this->AsOrgAdmin==true){return false;}
		if($this->AsMailManAdministrator==true){return false;}
		if($this->AsMessagingOrg==true){return false;}
		if($this->AsOrgDNSAdmin==true){return false;}
		if($this->AsVirtualBoxManager==true){return false;}
		if($this->AsOrgPostfixAdministrator==true){return false;}
		if($this->AsQuarantineAdministrator==true){return false;}
		if($this->AsVPNManager==true){return false;}
		if($this->AsSquidAdministrator==true){return true;}
		if($this->AsProxyMonitor==true){return true;}
	}


	private function SystemAdmin(){
		$this->AsAnAdministratorGeneric=true;
		$this->AsDebianSystem=true;
		$this->AsFirewallManager=true;
		$this->AllowAddGroup=true;
		$this->AllowAddUsers=true;
		$this->AllowChangeUserPassword=true;
		$this->AsDnsAdministrator=true;
		$this->ASDCHPAdmin=true;
		$this->AsCertifsManager=true;
		$this->AllowEditOuSecurity=true;
		$this->AsOrgAdmin=true;
		$this->AsOrgDNSAdmin=true;
		$this->AsVPNManager=true;
		$this->AsMailBoxAdministrator=true;
	}

    public function isCategorizeAdmin(){
        $PostfixEnable=intval($GLOBALS['CLASS_SOCKETS']->GET_INFO("EnablePostfix"));
        if($PostfixEnable==1){return false;}
        if($this->AsSystemAdministrator){return true;}
        if($this->AsSquidPersonalCategories){return true;}
        if($this->AsDansGuardianAdministrator){return true;}
    }
    public function IsDBAdmin(){
        if($this->AsSystemAdministrator){return true;}
        if($this->AsDatabaseAdministrator){return true;}
        return false;
    }

	private function ProxyAdmin(){
		$this->AsAnAdministratorGeneric=true;
		$this->AsWebMaster=true;
		$this->AsDansGuardianAdministrator=true;
		$this->AsSquidPersonalCategories=true;
		$this->AsHotSpotManager=true;
		$this->AsProxyMonitor=true;
		$this->AllowViewStatistics=true;
		$this->AsWebStatisticsAdministrator=true;

	}

	private function AllRights(){
        $this->AsDockerAdmin=true;
		$this->AsCertifsManager=true;
		$this->AsSquidPersonalCategories=true;
		$this->AsAnAdministratorGeneric=true;
		$this->AsSystemAdministrator=true;
		$this->AsInventoryAdmin=true;
		$this->AsPostfixAdministrator=true;
		$this->AsSambaAdministrator=true;
		$this->AsWebMaster=true;
		$this->AsSquidAdministrator=true;
		$this->AsArticaMetaAdmin=true;
		$this->AsArticaAdministrator=true;
		$this->AsWebFilterRepository=true;
		$this->AsMailBoxAdministrator=true;
		$this->AsMailManAdministrator=true;
		$this->AsMessagingOrg=true;
		$this->AllowChangeDomains=true;
		$this->AsVirtualBoxManager=true;
		$this->AsOrgPostfixAdministrator=true;
		$this->AsQuarantineAdministrator=true;
		$this->OverWriteRestrictedDomains=true;
		$this->AsOrgStorageAdministrator=true;
		$this->AsSystemWebMaster=true;
		$this->SystemAdmin();
		$this->ProxyAdmin();

	}

	private function postfixmutli(){
		if(!$this->POSTMULTI){return false;}
		$vers=explode(".",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION"));
		if($vers[0]>=2){if($vers[1]>=5){return true;}}
	}

	function SessionExpired(){}


	private function SetTimeZone(){
		if(!function_exists("getLocalTimezone")){return;}
		if(isset($GLOBALS["TIMEZONES"])){
			@date_default_timezone_set($GLOBALS["TIMEZONES"]);
			return;
		}

		if(isset($_SESSION["TIMEZONES"])){
			@date_default_timezone_set($_SESSION["TIMEZONES"]);
			$GLOBALS["TIMEZONES"]=$_SESSION["TIMEZONES"];
			return;

		}

		if(isset($GLOBALS["GET_TIME_ZONE"])){
			$GLOBALS["TIMEZONES"]=$GLOBALS["GET_TIME_ZONE"];
			$_SESSION["TIMEZONES"]=$GLOBALS["GET_TIME_ZONE"];
			@date_default_timezone_set($GLOBALS["TIMEZONES"]);
			return;
		}

		$sock=new sockets();
		$timezones=$sock->GET_INFO("timezones");
		if($timezones==null){$timezones="Europe/Dublin";}
		$GLOBALS["TIMEZONES"]=$timezones;
		$_SESSION["TIMEZONES"]=$timezones;
		@date_default_timezone_set($GLOBALS["TIMEZONES"]);
	}


	private function CurrentPageName(){$phpPage=basename($_SERVER["SCRIPT_FILENAME"]);return $phpPage;}


	function LoadModulesEnabled(){
		$debugThis=false;
		if(!isset($GLOBALS["DEBUG_TEMPLATE"])){$GLOBALS["DEBUG_TEMPLATE"]=false;}
		if($GLOBALS["DEBUG_TEMPLATE"]){$debugThis=true;}
		$CurrentPageName=CurrentPageName();
		if($GLOBALS["posix_getuid"]<>0){
			if($GLOBALS["VERBOSE"]){
				$CurrentPageName="logon.php";
			}
		}
		if($this->LoadModulesEnabledParsed){return ;}

		if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
		if(!class_exists("sqlgrey")){include(dirname(__FILE__).'/class.sqlgrey.inc');}
		if(!class_exists("milter_greylist")){include(dirname(__FILE__) . '/class.milter.greylist.inc');}
		include_once(dirname(__FILE__).'/class.artica.inc');
		if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}:includes done..; function ".__FUNCTION__." in " . __FILE__. " line ".__LINE__);}

		if($debugThis){error_log("[{$_SESSION["uid"]}]::$CurrentPageName: includes done ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		$artica=new artica_general($this->ldapClass);
		if($debugThis){error_log("[{$_SESSION["uid"]}]::$CurrentPageName: artica class ok ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}

		if($debugThis){writelogs($CurrentPageName.": sqlgrey($this->hostname,true)".__FUNCTION__.'/'.__LINE__);}
		$sqlg=new sqlgrey($this->hostname,true);
		if($sock->GET_INFO("kavmilterEnable")==1){$this->KAVMILTER_ENABLED=1;}else{$this->KAVMILTER_ENABLED=0;}
		if($sock->GET_INFO("InstantSearchEnable")==1){$this->INSTANSEARCH_ENABLE=true;}
		$this->MilterGreyListEnabled=$sock->GET_INFO("MilterGreyListEnabled");
		$this->AmavisFilterEnabled=$sock->GET_INFO('EnableAmavisDaemon');
		$this->KasxFilterEnabled=$artica->KasxFilterEnabled;
		$this->SQLGreyEnabled=$sqlg->SqlGreyEnabled;
		$this->ApacheArticaEnabled=$artica->ApacheArticaEnabled;
		if($this->ApacheArticaEnabled==null){$this->ApacheArticaEnabled=0;}
		$this->ClamavMilterEnabled=$artica->ClamavMilterEnabled;
		$this->SpamAssMilterEnabled=$artica->SpamAssMilterEnabled;
		$this->spfmilterEnabled=$artica->spfmilterEnabled;
		$this->MimeDefangEnabled=$artica->MimeDefangEnabled;
		$this->DkimFilterEnabled=$artica->DkimFilterEnabled;
		$this->EnableFetchmail=$artica->EnableFetchmail;
		$this->EnableFDMFetch=$artica->EnableFDMFetch;
		$this->P3ScanEnabled=$artica->P3ScanEnabled;
		$this->RoundCubeHTTPEngineEnabled=$artica->RoundCubeHTTPEngineEnabled;
		$this->sTunnel4enabled=$artica->sTunnel4enabled;
		$this->MailArchiverEnabled=$sock->GET_INFO("MailArchiverEnabled");
		$this->EnableMilterBogom=$artica->EnableMilterBogom;
		$this->EnableMysqlFeatures=$artica->EnableMysqlFeatures;
		$this->EnableMilterSpyDaemon=$artica->EnableMilterSpyDaemon;
		$this->EnableAmavisDaemon=$artica->EnableAmavisDaemon;
		$this->EnableAmavisBackup=$artica->EnableAmavisBackup;
		$this->KAVMILTER_ENABLED=$artica->kavmilterEnable;
		$this->EnableRemoteAddressBook=$artica->EnableRemoteAddressBook;
		$this->EnablePerUserRemoteAddressBook=$artica->EnablePerUserRemoteAddressBook;


		//writelogs("KasxFilterEnabled={$hash["KasxFilterEnabled"]},ApacheArticaEnabled=$this->ApacheArticaEnabled ",__CLASS__.'/'.__FUNCTION__,__FILE__);
		$this->EnableAmavisDaemon==0;

		if($this->SMTP_LEVEL==null){$this->SMTP_LEVEL=0;}
		if($GLOBALS["DEBUG_TEMPLATE"]){error_log("[{$_SESSION["uid"]}]::DEBUG::{$GLOBALS["CURRENT_PAGE"]}: DONE; function ".__FUNCTION__." in " . __FILE__. " line ".__LINE__);}
		$this->LoadModulesEnabledParsed=true;
	}

	public function EnablePDNS(){
		$sock=new sockets();
		$EnablePDNS=$sock->GET_INFO("EnablePDNS");
		if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSInstalled"))==0){return 0;}
		if($this->EnableDNSMASQ()==1){return 0;}


		if($this->APP_CHILLI_INSTALLED){
			$EnableChilli=$sock->GET_INFO("EnableChilli");
			if(!is_numeric($EnableChilli)){$EnableChilli=0;}
			if($EnableChilli==1){return 0;}
		}

		return $EnablePDNS;
	}
	public function EnableDNSMASQ(){
		$DNSMASQInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSMASQInstalled"));
		if($DNSMASQInstalled==0){return 0;}
		$EnableDNSMASQ=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSMASQ"));
		return $EnableDNSMASQ;
	}






	private function VERBOSE_PRIVS($text,$line=0){
		$this->PRIV_VERBOSE[]="$text [$line]";
	}


	function _TranslateRights($priv,$log=false):array{

		if(!class_exists("TranslateRights")){
			include_once(dirname(__FILE__)."/class.translate.rights.inc");
		}
        if(!isset($GLOBALS["_TranslateRights"])){$GLOBALS["_TranslateRights"]=array();}

        if(isset( $_SESSION["HAMRP_PRIVS"])){
            if(count( $_SESSION["HAMRP_PRIVS"])>0){
                $this->AsHamrpAdmin=true;
            }
        }

		$Rights=TranslateRights::GetPrivsArray();
		foreach ($Rights as $name=>$none){
			$this->$name=false;
		}
		$this->VERBOSE_PRIVS("_TranslateRights: Count of privs = ".count($priv),__LINE__);

		if(is_array($priv)){
			if(count($priv)>0){

                if(isset($priv["NGINX_SERVICES"])){
                    $this->NGINX_SERVICES=$priv["NGINX_SERVICES"];
                    unset($priv["NGINX_SERVICES"]);
                }
                if(isset($priv["SIMPLE_ACLS"])){
                    $this->SIMPLE_ACLS=$priv["SIMPLE_ACLS"];
                    unset($priv["SIMPLE_ACLS"]);
                }
				foreach ($priv as $num=>$ligne){
				$this->VERBOSE_PRIVS("$num == [[$ligne]]",__LINE__);
					$GLOBALS["_TranslateRights"][$num]=$ligne;
					if(trim(strtolower($ligne))=="yes"){
						$this->$num=true;
						$this->VERBOSE_PRIVS("<strong>this->$num == TRUE</strong>",__LINE__);

					}

				}
				if($this->AsSystemAdministrator){$this->SystemAdmin();}
				if($this->AsSquidAdministrator){$this->ProxyAdmin();}
				if($this->AsDockerAdmin){$this->AsAnAdministratorGeneric=true;}
                if($this->AsDockerReverse){$this->AsAnAdministratorGeneric=true;}
				return $GLOBALS["_TranslateRights"];
			}
		}


		if(isset($GLOBALS["_TranslateRights"])){
            $TranslateRights=$GLOBALS["_TranslateRights"];

            if(isset($TranslateRights["NginxPrivs"])){
                $tb=explode(",",$TranslateRights["NginxPrivs"]);
                foreach ($tb as $ServiceID){
                    if(!is_numeric($ServiceID)){continue;}
                    $this->NGINX_SERVICES[$ServiceID]=true;
                }
                unset($TranslateRights["NginxPrivs"]);
            }
            if(isset($TranslateRights["SimpleAclsPrivs"])){
                $tb=explode(",",$TranslateRights["SimpleAclsPrivs"]);
                foreach ($tb as $ServiceID){
                    if(!is_numeric($ServiceID)){continue;}
                    $this->SIMPLE_ACLS[$ServiceID]=true;
                }
                unset($TranslateRights["SimpleAclsPrivs"]);
            }






			foreach ($TranslateRights as $num=>$ligne){
				$value=false;
				if(!is_bool($ligne)){
					if(trim(strtolower($ligne))=="yes"){$value=true;}
				}else{
					$value=$ligne;
				}

				if($GLOBALS["DEBUG_PRIVS"]){echo "_TranslateRights(".__LINE__.") $num = $ligne<br>\n";}
				$this->$num=$value;
			}
			if($this->AsSystemAdministrator){$this->SystemAdmin();}
			if($this->AsSquidAdministrator){$this->ProxyAdmin();}
			if($this->AsDnsAdministrator){$this->AsAnAdministratorGeneric=true;}
            if($this->AsDockerAdmin){$this->AsAnAdministratorGeneric=true;}
            if($this->AsDockerReverse){$this->AsAnAdministratorGeneric=true;}
            return $GLOBALS["_TranslateRights"];
		}




		$cr=new TranslateRights($priv,$this->uid);
		$cr->ParseRights();
		$TTR=$cr->FINAL_PRIVS;
		if(!is_array($TTR)){return array();}
		$GLOBALS["_TranslateRights"]=$TTR;
		foreach ($GLOBALS["_TranslateRights"] as $num=>$ligne){
			$value=false;
			if(!is_bool($ligne)){
				if(trim(strtolower($ligne))=="yes"){$value=true;}
			}else{
				$value=$ligne;
			}
			if($GLOBALS['DEBUG_PRIVS']){echo "_TranslateRights(".__LINE__.") this->$num=$ligne/$value\n";}
			$this->$num=$value;
		}
		if($this->AsSystemAdministrator){$this->SystemAdmin();}
		if($this->AsSquidAdministrator){$this->ProxyAdmin();}
		if($this->AsDnsAdministrator){$this->AsAnAdministratorGeneric=true;}
        if($this->AsDockerAdmin){$this->AsAnAdministratorGeneric=true;}
        if($this->AsDockerReverse){$this->AsAnAdministratorGeneric=true;}

		return $GLOBALS["_TranslateRights"];

	}
	private function aghjkyrtij():bool{
		if(!isset($GLOBALS["DEBUGLIC"])){$GLOBALS["DEBUGLIC"]=false;}
		$objzrtg=base64_decode("Q09SUF9MSUNFTlNF");
		$this->$objzrtg=false;
		$TVF_WORKPATH=base64_decode("L2V0Yy9hcnRpY2EtcG9zdGZpeC9zZXR0aW5ncy9EYWVtb25zL0NPUlBfTElDRU5TRQ==");
		if(!is_file($TVF_WORKPATH)){
			$this->$objzrtg=false;
			return false;
		}
		$CONTENT=strtoupper(trim(@file_get_contents($TVF_WORKPATH)));
		$CONTENT=base64_decode($CONTENT);

		if($CONTENT=="TRUE"){
			if($GLOBALS["DEBUGLIC"]){echo "TVF_WORKPATH RETURN TRUE\n";}
			$this->$objzrtg=true;
			if($GLOBALS["DEBUGLIC"]){echo "TVF_WORKPATH={$this->$objzrtg}\n";}
			return true;
		}
		if($GLOBALS["DEBUGLIC"]){echo "TVF_WORKPATH={$this->$objzrtg} --> FALSE\n";}
        return false;

	}

	function IfIsAnuser($log=false){

		if($this->AsMessagingOrg){return false;}
		if($this->AseMailCampaignsAdmin){return false;}


		if($this->AsSystemAdministrator){
			if($log){writelogs("[{$_SESSION["uid"]}]:logon:  AsSystemAdministrator=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
			return false;}

		if($this->AsArticaMetaAdmin){
			return false;
		}

		if($this->AsVirtualBoxManager){
			if($log){writelogs("[{$_SESSION["uid"]}]:logon:  AsVirtualBoxManager=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
			return false;}


		if($this->AsMailBoxAdministrator){
			if($log){writelogs("[{$_SESSION["uid"]}]:logon:  AsMailBoxAdministrator=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
			return false;}
		if($this->AsPostfixAdministrator){
			if($log){writelogs("[{$_SESSION["uid"]}]:logon:  AsPostfixAdministrator=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
			return false;}

		if($this->AsSquidAdministrator){
			if($log){writelogs("[{$_SESSION["uid"]}]:logon:  AsSquidAdministrator=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
			return false;}

		if($this->AllowChangeKav){
			if($log){writelogs("[{$_SESSION["uid"]}]:logon:  AllowChangeKav=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
			return false;}
		if($this->AllowChangeKas){
			if($log){writelogs("[{$_SESSION["uid"]}]:logon:  AllowChangeKas=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
			return false;}
		if($this->AllowAddUsers){
			if($log){writelogs("[{$_SESSION["uid"]}]: AllowAddUsers=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
			return false;}
		if($this->AsOrgAdmin){
			if($log){writelogs("[{$_SESSION["uid"]}]: AsOrgAdmin=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
			return false;}
		if($this->AllowViewStatistics){
			if($log){writelogs("[{$_SESSION["uid"]}]: AllowViewStatistics=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
			return false;}
		if($this->AllowEditOuSecurity){
			if($log){writelogs("[{$_SESSION["uid"]}]: AllowEditOuSecurity=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
			return false;}
		if($this->AsSambaAdministrator){
			if($log){writelogs("[{$_SESSION["uid"]}]: AsSambaAdministrator=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
			return false;}
		if($this->AsDnsAdministrator){
			if($log){writelogs("[{$_SESSION["uid"]}]: AsDnsAdministrator=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
			return false;}
		if($this->AsQuarantineAdministrator){
			if($log){writelogs("[{$_SESSION["uid"]}]: AsQuarantineAdministrator=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
			return false;}
		if($this->AsOrgStorageAdministrator){
			if($log){writelogs("[{$_SESSION["uid"]}]: AsOrgStorageAdministrator=TRUE",__CLASS__.'/'.__FUNCTION__,__FILE__);}
			return false;}

		if($log){writelogs("[{$_SESSION["uid"]}]:logon:  None privileges found",__CLASS__.'/'.__FUNCTION__,__FILE__);}
		return true;
	}

	private function VERBOSE($trace=array(),$text=null,$sline=0){

		$t			= array();
		$suffix		= null;
		if(count($trace)>0){
			foreach ($trace as $main){
				$file=$main["file"];
				$function=$main["function"];
				$line=intval($main["line"]);
				$t[]="Called by $file $function() in line $line";
			}
		}

		if(count($t)>0){
			$suffix=@implode(", ",$t);
		}

		if(function_exists("VERBOSE")) {
			VERBOSE($text . " $suffix", $sline);
		}

		writelogs($text." $suffix",__CLASS__,__FILE__,$sline);
	}

	function _ParsePrivieleges($content=null){
		$uid			= null;
		$trace			= @debug_backtrace();
		if($GLOBALS["DEBUG_PRIVS"]){$this->VERBOSE($trace,$content);}
		if(!isset($GLOBALS["posix_getuid"])){$GLOBALS["posix_getuid"]=1000;if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}}

		$posix_getuid=$GLOBALS["posix_getuid"];

		if(isset($_SESSION["uid"])){$uid=$_SESSION["uid"];}
		if($uid==null){if($posix_getuid==0){$uid="-100";}}

		include_once(dirname(__FILE__)."/class.privileges.inc");
		$privs=new privileges($uid);
		$array=$privs->privs;
		if(isset($array["AsFirewallManager"])){
			if(trim(strtolower($array["AsFirewallManager"]))=="yes"){$this->AsFirewallManager=true;}
		}
        if(isset($array["AsDatabaseAdministrator"])){
            if(trim(strtolower($array["AsDatabaseAdministrator"]))=="yes"){$this->AsDatabaseAdministrator=true;}
        }

        if(isset($array["NGINX_SERVICES"])){
            $this->NGINX_SERVICES=$array["NGINX_SERVICES"];
            unset($array["NGINX_SERVICES"]);
        }
        if(isset($array["SIMPLE_ACLS"])){
            $this->SIMPLE_ACLS=$array["SIMPLE_ACLS"];
            unset($array["SIMPLE_ACLS"]);
        }
        if(isset($array["NginxPrivs"])){
            $tb=explode(",",$array["NginxPrivs"]);
            foreach ($tb as $ServiceID){
                if(!is_numeric($ServiceID)){continue;}
                $this->NGINX_SERVICES[$ServiceID]=true;
            }
            unset($array["NginxPrivs"]);
        }
        if(isset($TranslateRights["SimpleAclsPrivs"])){
            $tb=explode(",",$TranslateRights["SimpleAclsPrivs"]);
            foreach ($tb as $ServiceID){
                if(!is_numeric($ServiceID)){continue;}
                $this->SIMPLE_ACLS[$ServiceID]=true;
            }
            unset($TranslateRights["SimpleAclsPrivs"]);
        }

        foreach ($array as $num=>$ligne){
			if(trim(strtolower($ligne))=="yes"){
                $this->$num=true;
                if($GLOBALS["DEBUG_PRIVS"]){
                    $this->VERBOSE(array(),"this->$num == TRUE",__LINE__);
                }
            }
		}
		return $privs->privs;

	}

	function _BuildPrivileges($hash){
		$conf			= null;
		if(!is_array($hash)){return "DEFAULT";}
		foreach ($hash as $num=>$ligne){
			$conf=$conf ."[$num]=\"$ligne\"\n";

		}

		return $conf;

	}

	function pageDie(){
		echo "<h1 style='color:red'>Access denied, please check your privileges</h1>";
		exit;


	}


	function isTreeReadable($priv=null){
		if(posix_getuid()==0){$this->uid=-100;}
		if($this->uid==-100){return true;}
		if($priv==null){$priv=$this->priv;}
		if($priv["AsSystemAdministrator"]=="yes"){return true;}
		if($priv["AsPostfixAdministrator"]=="yes"){return true;}
		if($priv["AsArticaAdministrator"]=="yes"){return true;}
		if($priv["AsSystemAdministrator"]=="yes"){return true;}
		if($priv["AsSambaAdministrator"]=="yes"){return true;}
		if($priv["AsVirtualBoxManager"]=="yes"){return true;}
		if($priv["AsArticaMetaAdmin"]=="yes"){return true;}


		if($priv["AllowChangeKas"]=="yes"){return true;}
		if($priv["AllowChangeKav"]=="yes"){return true;}
		if($priv["AllowAddUsers"]=="yes"){return true;}
		if($priv["AllowAddGroup"]=="yes"){return true;}
		return false;
	}

	function IsGlobalAdmin($priv=null,$log=false){
		if(!isset($GLOBALS["posix_getuid"])){$GLOBALS["posix_getuid"]=1000;if(function_exists("posix_getuid")){$GLOBALS["posix_getuid"]=posix_getuid();}}
		if($GLOBALS["posix_getuid"]==0){$this->uid=-100;}
		if($this->uid==-100){
			$this->AsArticaAdministrator=true;
			$this->AsQuarantineAdministrator=true;
			$this->AsVirtualBoxManager=true;
			$this->AsArticaMetaAdmin=true;
			if($log){writelogs("It is a global administrator",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			return true;
		}
		if($priv==null){$priv=$this->priv;}
		if($priv["AsSystemAdministrator"]=="yes"){return true;}
		if($priv["AsPostfixAdministrator"]=="yes"){return true;}
		if($priv["AsArticaAdministrator"]=="yes"){return true;}
		if($priv["AsSquidAdministrator"]=="yes"){return true;}
		if($priv["AsSambaAdministrator"]=="yes"){return true;}
		if($priv["AsVirtualBoxManager"]=="yes"){return true;}
		if($priv["AsArticaMetaAdmin"]=="yes"){return true;}


		return false;
	}

	function IsSuperAdmin(){
		if($this->AsArticaAdministrator){return true;}
		if($this->AsPostfixAdministrator){return true;}
		if($this->AsSquidAdministrator){return true;}
		if($this->AsSambaAdministrator){return true;}
		if($this->AsDansGuardianAdministrator){return true;}
		if($this->AsMailBoxAdministrator){return true;}
		return false;
	}


	function array_monitorix($type="day"){
		include_once(dirname(__FILE__)."/class.page.builder.inc");
		$pges=new pagebuilder();
		return $pges->monitorix_array($type);
	}

}


function url_decode_special($path){
	include_once(dirname(__FILE__)."/class.html.tools.inc");
	return url_decode_special_tool($path);
}

function GetRights_aliases() {
	$usersprivs = new usersMenus();
	if ($usersprivs->AsAnAdministratorGeneric) {return 1;}
	$GLOBALS["ERRORS_PRIVS"][]="{AsAnAdministratorGeneric}: {no}";
	if ($usersprivs->AllowEditOuSecurity) {return 1;}
	$GLOBALS["ERRORS_PRIVS"][]="{AllowEditOuSecurity}: {no}";
	if ($usersprivs->AsMessagingOrg) {return 1;}
	$GLOBALS["ERRORS_PRIVS"][]="{AsMessagingOrg}: {no}";
	if ($usersprivs->AllowEditAliases) {return 1;}
	$GLOBALS["ERRORS_PRIVS"][]="{AllowEditAliases}: {no}";
	if($usersprivs->AsInventoryAdmin){return 1;}
	$GLOBALS["ERRORS_PRIVS"][]="{AsInventoryAdmin}: {no}";
	if($usersprivs->AsOrgDNSAdmin){return 1;}
	$GLOBALS["ERRORS_PRIVS"][]="{AsOrgDNSAdmin}: {no}";
	if($usersprivs->ASDCHPAdmin){return 1;}
	$GLOBALS["ERRORS_PRIVS"][]="{ASDCHPAdmin}: {no}";
	return 0;
}