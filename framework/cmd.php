<?php
//SP139
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");

$f=array();
foreach ($_GET as $num=>$line){
	if($GLOBALS["VERBOSE"]){echo "$num=$line\n";}
	$f[]="$num=$line";
}
writelogs_framework("Get query " .@implode(",",$f),"main()",__FILE__,__LINE__);
if(isset($_GET["Dir-Files"])){Dir_Files();exit;}
if(isset($_GET["repair-settings"])){repair_settings_inc();exit;}
if(isset($_GET["squid-rebuild-reconfigure"])){squid_rebuild_reconfigure();exit;}
if(isset($_GET["clamav-status"])){clamav_status();exit;}
if(isset($_GET["hypercachestoreid-ini-status"])){HYPERCACHE_STOREID_INI_STATUS();exit;} 
if(isset($_GET["hypercacheweb-ini-status"])){HYPERCACHE_WEB_INI_STATUS();exit;}
if(isset($_GET["ufdbcat-ini-status"])){UFDBCAT_INI_STATUS();exit;}
if(isset($_GET["CleanCache"])){CleanCache();exit;}
if(isset($_GET["GetLangagueFile"])){LOAD_LANGUAGE_FILE();exit;}

if(isset($_GET["SaveSMTPNotifications"])){SaveSMTPNotifications();exit;}
if(isset($_GET["SmtpNotificationConfig"])){SmtpNotificationConfig();exit;}
if(isset($_GET["refresh-frontend"])){Refresh_frontend();exit;}
if(isset($_GET["find-program"])){find_sock_program();exit;}
if(isset($_GET["syslog-query"])){SYSLOG_QUERY();exit;}
if(isset($_GET["aptcheck"])){aptcheck();exit;}
if(isset($_GET["SetServerTime"])){SetServerTime();exit;}
if(isset($_GET["CompileSSHDRules"])){CompileSSHDRules();exit;}
if(isset($_GET["ou-ldap-import-execute"])){LDAP_IMPORT_EXEC();exit;}
if(isset($_GET["sys-sync-paquages"])){SysSyncPaquages();exit;}
if(isset($_GET["GetTotalMemMB"])){GetTotalMemMB();exit;}
if(isset($_GET["process-ttl"])){process_timeexec();exit;}
if(isset($_GET["myisamchk"])){myisamchk();exit;}
if(isset($_GET["filesize"])){_filesize();exit;}
if(isset($_GET["chmod"])){_chmod();exit;}
if(isset($_GET["readfile"])){_readfile();exit;}
if(isset($_GET["TCP_NICS_STATUS_ARRAY"])){TCP_NICS_STATUS_ARRAY();exit;}
if(isset($_GET["LaunchRemoteInstall"])){LaunchRemoteInstall();exit;}
if(isset($_GET["restart-web-server"])){RestartWebServer();exit;}
if(isset($_GET["restart-artica-status"])){RestartArticaStatus();exit;}
if(isset($_GET["RestartVnStat"])){RestartVnStat();exit;}
if(isset($_GET["restart-ufdb"])){restart_ufdbguard();exit;}
if(isset($_GET["iptables-nginx-compile"])){iptables_nginx_compile();exit;}
if(isset($_GET["iptables-save"])){iptables_save();exit;}

if(isset($_GET["wake-on-lan"])){WakeOnLan();exit;}

if(isset($_GET["net-ads-leave"])){net_ads_leave();exit;}
if(isset($_GET["process1-force"])){process1_force();exit;}


if(isset($_GET["rdpproxy-ini-status"])){RDP_INI_STATUS();exit;}
if(isset($_GET["syncthing-ini-status"])){SYNCTHING_INI_STATUS();exit;}
if(isset($_GET["right-status"])){right_status();exit;}

if(isset($_GET["RestartApacheGroupwareForce"])){RestartApacheGroupwareForce();exit;}
if(isset($_GET["RestartApacheGroupwareNoForce"])){RestartApacheGroupwareNoForce();exit;}


//snort

if(isset($_GET["snort-networks"])){snort_networks();exit;}
if(isset($_GET["restart-snort"])){restart_snort();exit;}
if(isset($_GET["snort-status"])){snort_status();exit;}
if(isset($_GET["awstats-perform"])){awstats_perform();exit;}
if(isset($_GET["VIPTrackRun"])){VIPTrackRun();exit;}

if(isset($_GET["sabnzbdplus-ini-status"])){sabnzbdplus_src_status();exit;}
if(isset($_GET["sabnzbdplus-restart"])){sabnzbdplus_restart();exit;}


if(isset($_GET["start-install-app"])){SETUP_CENTER_LAUNCH();exit;}

if(isset($_GET["ChangeMysqlLocalRoot"])){ChangeMysqlLocalRoot();exit;}
if(isset($_GET["ChangeMysqlLocalRoot2"])){ChangeMysqlLocalRoot2();exit;}
if(isset($_GET["ChangeMysqlDir"])){ChangeMysqlDir();exit;}



if(isset($_GET["change-mysql-params"])){ChangeMysqlParams();exit;}
if(isset($_GET["mysql-myd-file"])){mysql_myd_file();exit;}
if(isset($_GET["mysql-check"])){mysql_check();exit;}

if(isset($_GET["viewlogs"])){viewlogs();exit;}
if(isset($_GET["LdapdbStat"])){LdapdbStat();exit;}
if(isset($_GET["LdapdbSize"])){LdapdbSize();exit;}
if(isset($_GET["ldap-restart"])){ldap_restart();exit;}
if(isset($_GET["buildFrontEnd"])){buildFrontEnd();exit;}
if(isset($_GET["cpualarm"])){cpualarm();exit;}

if(isset($_GET["TaskLastManager"])){TaskLastManager();exit;}
if(isset($_GET["TaskLastManagerTime"])){TaskLastManagerTime();exit;}

if(isset($_GET["kill-pid-number"])){process_kill();exit;}
if(isset($_GET["kill-pid-single"])){process_kill_single();exit;}
if(isset($_GET["start-service-name"])){StartServiceCMD();exit;}
if(isset($_GET["stop-service-name"])){StopServiceCMD();exit;}
if(isset($_GET["START-STOP-SERVICES"])){START_STOP_SERVICES();exit;}

if(isset($_GET["monit-restart"])){MONIT_RESTART();exit;}
if(isset($_GET["restart-http-engine"])){LIGHTTPD_RESTART();exit;}
if(isset($_GET["fcron-restart"])){FCRON_RESTART();exit;}
if(isset($_GET["restart-artica-maillog"])){ARTICA_MAILLOG_RESTART();exit;}
if(isset($_GET["notifier-restart"])){EMAILRELAY_RESTART();exit;}

if(isset($_GET["cdir-calc"])){IP_CALC_CDIR();exit;}
if(isset($_GET["ip-get-default-getway"])){getDefaultGateway();exit;}
if(isset($_GET["ip-get-default-dns"])){GetMyDNSServers();exit;}
if(isset($_GET["ip-del-route"])){IP_DEL_ROUTE();exit;}
if(isset($_GET["ip-build-routes"])){IP_ROUTES();exit;}

if(isset($_GET["DeleteAllIpTablesRules"])){IpTables_delete_all_rules();exit;}
if(isset($_GET["WhiteListResolvMX"])){IpTables_WhiteListResolvMX();exit;}


if(isset($_GET["unix-groups"])){unix_groups();exit;}
if(isset($_GET["ping"])){ping();exit;}


//autofs



//resolv
if(isset($_GET["copyresolv"])){copyresolv();exit;}


if(isset($_GET["greyhole-ini-status"])){GREYHOLE_STATUS();exit;}
if(isset($_GET["greyhole-restart"])){GREYHOLE_RESTART();exit;}
if(isset($_GET["greyhole-daily-fck"])){GREYHOLE_DAILY_FCK();exit;}


if(isset($_GET["ProcessExists"])){ProcessExists();exit;}
if(isset($_GET["ProcessInfo"])){ProcessInfo();exit;}


if(isset($_GET["compile-proxy"])){PROXY_SAVE();exit;}
if(isset($_GET["sarg-config"])){SARG_SAVE();exit;}
if(isset($_GET["sarg-run"])){SARG_EXEC();exit;}
if(isset($_GET["sarg-passwords"])){SARG_PASSWORDS();exit;}


//syslog
if(isset($_GET["syslog-master-mode"])){syslog_master_mode();exit;}
if(isset($_GET["syslog-client-mode"])){syslog_client_mode();exit;}
if(isset($_GET["IsUDPport"])){IsUDPport();exit;}

//PDNS
if(isset($_GET["pdns-restart"])){POWERDNS_RESTART();exit;}


//DNSMASQ
if(isset($_GET["LoaddnsmasqConf"])){DNSMASQ_LOAD_CONF();exit;}



//iscsi

if(isset($_GET["restart-iscsi"])){iscsi_restart();exit;}
if(isset($_GET["iscsi-status"])){iscsi_status();exit;}
if(isset($_GET["reload-iscsi"])){iscsi_reload();exit;}
if(isset($_GET["iscsi-client"])){iscsi_client();exit;}





//UpdateUtility

if(isset($_GET["UpdateUtilitySource"])){UpdateUtilitySource();exit;}

//stunnel
if(isset($_GET["stunnel-ini-status"])){STUNNEL_INI_STATUS();exit;}
if(isset($_GET["stunnel-restart"])){STUNNEL_RESTART();exit;}



if(isset($_GET["hamachi-net"])){hamachi_net();exit;}
if(isset($_GET["hamachi-status"])){hamachi_status();exit;}
if(isset($_GET["hamachi-sessions"])){hamachi_sessions();exit;}
if(isset($_GET["hamachi-ip"])){hamachi_currentIP();exit;}
if(isset($_GET["hamachi-restart"])){hamachi_restart();exit;}
if(isset($_GET["hamachi-delete-net"])){hamachi_delete_network();exit;}



if(isset($_GET["pptpd-ini-status"])){pptpd_status();exit;}
if(isset($_GET["pptpd-clients-ini-status"])){pptpd_clients_status();exit;}
if(isset($_GET["pptpd-chap"])){pptpd_chap();exit;}
if(isset($_GET["pptpd-restart"])){pptpd_restart();exit;}
if(isset($_GET["pptpd-ifconfig"])){pptpd_ifconfig();exit;}

if(isset($_GET["mbx-migr-add-file"])){mailbox_migration_import_file();exit;}
if(isset($_GET["mbx-migr-reload-members"])){mailbox_migration_start_members();exit;}



if(isset($_GET["ocsweb-restart"])){OCSWEB_RESTART();exit;}
if(isset($_GET["ocsweb-status"])){OCSWEB_STATUS();exit;}
if(isset($_GET["ocs-generate-certificate"])){OCSWEB_CERTIFICATE();exit();}
if(isset($_GET["ocs-get-csr"])){OCSWEB_CERTIFICATE_CSR();exit;}
if(isset($_GET["ocs-generate-final-certificate"])){OCSWEB_FINAL_CERTIFICATE();exit;}
if(isset($_GET["ocs-package-infos"])){OCSWEB_PACKAGE_INFOS();exit;}
if(isset($_GET["ocs-package-cp"])){OCSWEB_PACKAGE_COPY();exit;}
if(isset($_GET["ocs-package-cpinfo"])){OCSWEB_PACKAGE_CREATE_INFO();exit;}
if(isset($_GET["ocs-package-delete"])){OCSWEB_PACKAGE_DELETE();exit;}
if(isset($_GET["ocs-package-frag"])){OCSWEB_PACKAGE_FRAGS();exit;}
if(isset($_GET["ocs-agent-zip-packages"])){OCSWEB_GET_AGENT_PACKAGE_FILENAME();exit;}
if(isset($_GET["ocsagntlnx-status"])){OCSAGENT_STATUS();exit;}
if(isset($_GET["ocsagntlnx-restart"])){OCSAGENT_RESTART();exit;}
if(isset($_GET["ocsInventoryagntWinVer"])){InventoryAgentsWindowsVersions();exit;}
if(isset($_GET["UpdateFusionInventory"])){OCSAGENT_UPDATE_FUSION_INVENTORY();exit;}
if(isset($_GET["winexe-ver"])){WINEXE_VERSION();exit;}
if(isset($_GET["moveOcsAgentPackage"])){OCSWEB_MOVE_INVENTORY_WIN_PACKAGE();exit;}
if(isset($_GET["ocs-web-events"])){OCSWEB_WEB_EVENTS();exit;}
if(isset($_GET["ocs-web-errors"])){OCSWEB_WEB_ERRORS();exit;}
if(isset($_GET["ocs-service-events"])){OCSWEB_SERV_EVENTS();exit;}
if(isset($_GET["sysctl-value"])){KERNEL_SYSCTL_VALUE();exit;}
if(isset($_GET["sysctl-setvalue"])){KERNEL_SYSCTL_SET_VALUE();exit;}

if(isset($_GET["keymap-list"])){KEYBOARD_KEY_MAP();exit;}

//artica-meta
if(isset($_GET["artica-meta-register"])){artica_meta_register();exit;}
if(isset($_GET["artica-meta-join"])){artica_meta_join();exit;}
if(isset($_GET["artica-meta-unjoin"])){artica_meta_unjoin();exit;}
if(isset($_GET["artica-meta-push"])){artica_meta_push();exit;}
if(isset($_GET["artica-meta-user"])){artica_meta_user();exit;}
if(isset($_GET["artica-meta-export-dns"])){artica_meta_user_export_dns();exit;}
if(isset($_GET["artica-meta-awstats"])){artica_meta_export_awstats();exit;}
if(isset($_GET["artica-meta-computer"])){artica_meta_computer();exit;}
if(isset($_GET["artica-meta-fetchmail-rules"])){artica_meta_fetchmail_rules();exit;}
if(isset($_GET["artica-meta-ovpn"])){artica_meta_ovpn();exit;}
if(isset($_GET["artica-meta-openvpn-sites"])){artica_meta_export_openvpn_sites();exit;}
//organizations

if(isset($_GET["move-ldap-ou"])){ORGANISATION_RENAME();exit;}

//iptables
if(isset($_GET["iptables-bridge-rules"])){IPTABLES_CHAINES_BRIDGE_RULES();exit;}
if(isset($_GET["iptables-rotator"])){IPTABLES_CHAINES_ROTATOR();exit;}
if(isset($_GET["iptables-rotator-show"])){IPTABLES_CHAINES_ROTATOR_SHOW();exit;}



//apt-mirror


//qos
if(isset($_GET["qos-iptables"])){qos_iptables();exit;}
if(isset($_GET["qos-compile"])){qos_compile();exit;}



//ddclient
if(isset($_GET["ddclient"])){DDCLIENT_RESTART();exit;}


//audtitd
if(isset($_GET["auditd-rebuild"])){AUDITD_REBUILD();exit;}
if(isset($_GET["auditd-ini-status"])){AUDITD_STATUS();exit;}
if(isset($_GET["auditd-config"])){AUDITD_CONFIG();exit;}
if(isset($_GET["auditd-apply"])){AUDITD_SAVE_CONFIG();exit;}
if(isset($_GET["auditd-force"])){AUDITD_FORCE();exit;}




//saslauthd
if(isset($_GET["saslauthd-restart"])){saslauthd_restart();exit;}


//openDKIM
if(isset($_GET["opendkim-restart"])){OPENDKIM_RESTART();exit;}
if(isset($_GET["opendkim-status"])){opendkim_status();exit;}

if(isset($_GET["opendkim-show-keys"])){OPENDKIM_SHOW_KEYS();exit;}
if(isset($_GET["opendkim-build-keys"])){OPENDKIM_BUILD_KEYS();exit;}



if(isset($_GET["opendkim-show-tests-keys"])){OPENDKIM_SHOW_TESTS_KEYS();exit;}


//milter-dkim
if(isset($_GET["milter-dkim-restart"])){MILTERDKIM_RESTART();exit;}
if(isset($_GET["milterdkim-show-tests-keys"])){MILTERDKIM_SHOW_TESTS_KEYS();exit;}
if(isset($_GET["milterdkim-show-keys"])){MILTERDKIM_SHOW_KEYS();exit;}
if(isset($_GET["milterdkim-whitelistdomains"])){MILTERDKIM_WHITELIST_DOMAINS();exit;}

//thincient

if(isset($_GET["thinclients-rebuild"])){THINCLIENT_REBUILD();exit;}
if(isset($_GET["thinclients-rebuild-cd"])){THINCLIENT_REBUILD_CD();exit;}


if(isset($_GET["milter-greylist-ini-status"])){MILTER_GREYLIST_INI_STATUS();exit;}
if(isset($_GET["milter-greylist-reconfigure"])){milter_greylist_reconfigure();exit;}
if(isset($_GET["milter-greylist-multi-status"])){milter_greylist_multi_status();exit;}
if(isset($_GET["move_uploaded_file"])){move_uploaded_file_framework();exit;}

if(isset($_GET["sslfingerprint"])){sslfingerprint();exit;}

if(isset($_GET["kasversion"])){kasversion();exit;}
if(isset($_GET["kaspersky-status"])){kaspersky_status();exit;}
if(isset($_GET["UpdateUtility-pattern-date"])){UpdateUtilityPatternDate();exit;}



// 
if(isset($_GET["RestartRetranslator"])){retranslator_restart();exit;}
if(isset($_GET["RetranslatorSitesList"])){retranslator_sites_lists();exit;}
if(isset($_GET["RetranslatorEvents"])){retranslator_events();exit;}
if(isset($_GET["retranslator-status"])){retranslator_status();exit;}
if(isset($_GET["retranslator-execute"])){retranslator_execute();exit;}
if(isset($_GET["retranslator-dbsize"])){retranslator_dbsize();exit;}
if(isset($_GET["retranslator-tmp-dbsize"])){retranslator_tmp_dbsize();exit;}

if(isset($_GET["Global-Applications-Status"])){Global_Applications_Status();exit;}
if(isset($_GET["status-forced"])){Global_Applications_Status();exit;}
if(isset($_GET["system-unique-id"])){GetUniqueID();exit;}
if(isset($_GET["system-debian-kernel"])){system_debian_kernel();exit;}
if(isset($_GET["system-debian-upgrade-kernel"])){system_debian_kernel_upgrade();exit;}
if(isset($_GET["system-reboot-force"])){system_reboot_force();exit;}
//clamav
if(isset($_GET["update-clamav"])){ClamavUpdate();exit;}
if(isset($_GET["clamd-restart"])){clamd_restart();exit;}
if(isset($_GET["clamav-av-pattern-status"])){clamd_pattern_status();exit;}
if(isset($_GET["clamd-reload"])){clamd_reload();exit;}



//reports
if(isset($_GET["pdf-quarantine-cron"])){reports_build_quarantine_cron();exit;}
if(isset($_GET["pdf-quarantine-send"])){reports_build_quarantine_send();exit;}

//pure-ftpd

if(isset($_GET["pure-ftpd-status"])){pureftpd_status();exit;}
if(isset($_GET["pure-ftpd-restart"])){pureftpd_restart();exit;}
if(isset($_GET["pure-ftpd-users"])){pureftpd_users();exit;}



//NFS
if(isset($_GET["reload-nfs"])){NFS_RELOAD();exit;}


//amavis restart
if(isset($_GET["amavis-restart"])){RestartAmavis();exit;}
if(isset($_GET["amavis-get-events"])){amavis_get_events();exit;}
if(isset($_GET["amavis-configuration-file"])){amavis_get_config();exit;}
if(isset($_GET["amavis-get-status"])){amavis_get_status();exit;}
if(isset($_GET["amavis-template-load"])){amavis_get_template();exit;}
if(isset($_GET["amavis-template-save"])){amavis_save_template();exit;}
if(isset($_GET["amavis-template-help"])){amavis_template_help();exit;}
if(isset($_GET["amavis-watchdog"])){amavis_watchdog();exit;}




//rsync
if(isset($_GET["RestartRsyncServer"])){RestartRsyncServer();exit;}
if(isset($_GET["rsyncd-conf"])){rsync_load_config();exit;}
if(isset($_GET["rsync-save-conf"])){rsync_save_conf();exit;}




//Install/Uninstall
if(isset($_GET["organization-delete"])){organization_delete();exit;}
if(isset($_GET["uninstall-app"])){application_uninstall();exit;}
if(isset($_GET["AppliCenterGetDebugInfos"])){application_debug_infos();exit;}
if(isset($_GET["services-install"])){application_service_install();exit;}



//fetchmail
if(isset($_GET["restart-fetchmail"])){RestartFetchmail();exit;}
if(isset($_GET["fetchmail-status"])){fetchmail_status();exit;}
if(isset($_GET["fetchmail-logs"])){fetchmail_logs();exit;}


//Ad importation
if(isset($_GET["ad-import-schedule"])){AD_IMPORT_SCHEDULE();exit;}
if(isset($_GET["ad-import-remove-schedule"])){AD_REMOVE_SCHEDULE();exit;}
if(isset($_GET["ad-import-perform"])){AD_PERFORM();exit;}

if(isset($_GET["ou-ldap-import-schedules"])){LDAP_IMPORT_SCHEDULE();exit;}



//exec.hamachi.php
if(isset($_GET["list-nics"])){TCP_LIST_NICS();exit;}
if(isset($_GET["virtuals-ip-reconfigure"])){writelogs_framework("TCP_VIRTUALS()",__FUNCTION__,__FILE__,__LINE__);TCP_VIRTUALS();exit;}
if(isset($_GET["vlan-ip-reconfigure"])){TCP_VLANS();exit;}



if(isset($_GET["QueryArticaLogs"])){artica_update_query_fileslogs();exit;}
if(isset($_GET["ReadArticaLogs"])){artica_update_query_logs();exit;}

if(isset($_GET["repair-artica-ldap-branch"])){RepairArticaLdapBranch();exit;}

//certitifcate
if(isset($_GET["ChangeSSLCertificate"])){ChangeSSLCertificate();exit;}
if(isset($_GET["postfix-certificate"])){postfix_certificate();exit;}
if(isset($_GET["certificate-viewinfos"])){certificate_infos();exit;}
if(isset($_GET["postfix-perso-settings"])){postfix_perso_settings();exit;}
if(isset($_GET["postfix-smtpd-restrictions"])){postfix_smtpd_restrictions();exit;}
if(isset($_GET["postfix-mem-disk-status"])){postfix_mem_disk_status();exit;}
if(isset($_GET["postscreen"])){postscreen();exit;}
if(isset($_GET["postfix-throttle"])){postfix_throttle();exit;}
if(isset($_GET["postfix-freeze"])){postfix_freeze();exit;}
if(isset($_GET["postfix-ssl"])){postfix_single_ssl();exit;}
if(isset($_GET["postfix-sasl-mech"])){postfix_single_sasl_mech();exit;}
if(isset($_GET["postfix-postfinger"])){postfix_postfinger();exit;}
if(isset($_GET["postfix-iptables-compile"])){postfix_iptables_compile();exit;}
if(isset($_GET["postfix-body-checks"])){postfix_body_checks();exit;}
if(isset($_GET["postfix-smtp-sender-restrictions"])){postfix_smtp_senders_restrictions();exit;}
if(isset($_GET["maillog-query"])){maillog_query();exit;}
if(isset($_GET["postfix-whitelisted-global"])){postfix_whitelisted_global();exit;}
if(isset($_GET["postfinder"])){postfinder();exit;}
if(isset($_GET["postfix-multi-configure-hostname"])){postfix_multi_configure_hostname();exit;}
//cluebringer


if(isset($_GET["cluebringer-restart"])){cluebringer_restart();exit;}
if(isset($_GET["cluebringer-ini-status"])){cluebringer_status();exit;}
if(isset($_GET["cluebringer-passwords"])){cluebringer_passwords();exit;}



//postmulti

if(isset($_GET["postfix-multi-status"])){postfix_multi_status();exit;}
if(isset($_GET["postfix-multi-reconfigure"])){postfix_multi_reconfigure();exit;}
if(isset($_GET["postfix-multi-sasl"])){postfix_multi_ssl();exit;}
if(isset($_GET["postfix-multi-settings"])){postfix_multi_settings();exit;}
if(isset($_GET["postfix-multi-mastercf"])){postfix_multi_mastercf();exit;}




if(isset($_GET["postfix-multi-perform-reload"])){postfix_multi_perform_reload();exit;}
if(isset($_GET["postfix-multi-perform-restart"])){postfix_multi_perform_restart();exit;}
if(isset($_GET["postfix-multi-perform-flush"])){postfix_multi_perform_flush();exit;}
if(isset($_GET["postfix-multi-reconfigure-all"])){postfix_multi_reconfigure_all();exit;}
if(isset($_GET["postfix-multi-perform-reconfigure"])){postfix_multi_perform_reconfigure();exit;}
if(isset($_GET["restart-postfix-single"])){postfix_restart_single();exit;}
if(isset($_GET["restart-postfix-single-now"])){postfix_restart_single_now();exit;}

//virtualbox
if(isset($_GET["virtualbox-list-vms"])){virtualbox_list_vms();exit;}
if(isset($_GET["virtualbox-ini-status"])){virtualbox_status();exit;}
if(isset($_GET["virtualbox-ini-all-status"])){virtualbox_all_status();exit;}
if(isset($_GET["virtualbox-showvminfo"])){virtualbox_showvminfo();exit;}
if(isset($_GET["virtualbox-showcpustats"])){virtualbox_showcpustats();exit;} //$_GET["virtual-machine"]
if(isset($_GET["virtualbox-clonehd"])){virtualbox_clonehd();exit;}
if(isset($_GET["virtualbox-stop"])){virtualbox_stop();exit;}
if(isset($_GET["virtualbox-start"])){virtualbox_start();exit;}
if(isset($_GET["virtualbox-snapshot"])){virtualbox_snapshot();exit;}
if(isset($_GET["install-vdi"])){virtualbox_install();exit;}
if(isset($_GET["virtualbox-nats"])){virtualbox_nats();exit;}
if(isset($_GET["virtualbox-nat-del"])){virtualbox_nat_del();exit;}
if(isset($_GET["virtualbox-nats-rebuild"])){virtualbox_nat_rebuild();exit;}
if(isset($_GET["virtualbox-guestmemoryballoon"])){virtualbox_guestmemoryballoon();exit;}
if(isset($_GET["virtualbox-set-params"])){virtualbox_set_params();exit;}
if(isset($_GET["VboxPid"])){VboxPid();exit;}




if(isset($_GET["dkim-check-presence-key"])){dkim_check_presence_key();exit;}
if(isset($_GET["dkim-amavis-build-key"])){dkim_amavis_build_key();exit;}
if(isset($_GET["dkim-amavis-show-keys"])){dkim_amavis_show_keys();}
if(isset($_GET["dkim-amavis-tests-keys"])){dkim_amavis_tests_keys();}

//safeBox
if(isset($_GET["SafeBoxUser"])){safe_box_set_user();exit;}
if(isset($_GET["mount-safebox"])){safebox_mount();exit;}
if(isset($_GET["umount-safebox"])){safebox_umount();exit;}
if(isset($_GET["safebox-logs"])){safebox_logs();exit;}
if(isset($_GET["check-safebox"])){safebox_check();exit;}

//ntpd

if(isset($_GET["ntpd-events"])){ntpd_events();exit;}

//zabix
if(isset($_GET["zabbix-restart"])){zabbix_restart();exit;}


//cyrus


if(isset($_GET["mailbox-delete"])){cyrus_mailboxdelete();exit;}
if(isset($_GET["DelMbx"])){delete_mailbox();exit;}
if(isset($_GET["cyrus-check-cyr-accounts"])){cyrus_check_cyraccounts();exit;}
if(isset($_GET["cyrus-reconfigure"])){cyrus_reconfigure();exit;}
if(isset($_GET["cyrus-get-partition-default"])){cyrus_paritition_default_path();exit;}
if(isset($_GET["cyrus-MoveDefaultToCurrentDir"])){cyrus_move_default_dir_to_currentdir();exit;}
if(isset($_GET["cyrus-SaveNewDir"])){cyrus_move_newdir();exit;}
if(isset($_GET["cyrus-rebuild-all-mailboxes"])){cyrus_rebuild_all_mailboxes();exit;}
if(isset($_GET["cyrus-imap-status"])){cyrus_imap_status();exit;}
if(isset($_GET["cyrus-change-password"])){cyrus_imap_change_password();}
if(isset($_GET["cyrus-empty-mailbox"])){cyrus_empty_mailbox();exit;}
if(isset($_GET["cyrus-to-ad"])){cyrus_activedirectory();exit;}
if(isset($_GET["cyrus-to-ad-events"])){cyrus_activedirectory_events();exit;}
if(isset($_GET["cyrus-sync-to-ad"])){cyrus_sync_to_ad();exit;}
if(isset($_GET["cyrus-db-config"])){cyrus_db_config();exit;}



if(isset($_GET["emailing-import-contacts"])){emailing_import_contacts();exit;}
if(isset($_GET["emailing-database-migrate-perform"])){emailing_database_migrate_export();exit;}
if(isset($_GET["emailing-builder-linker"])){emailing_builder_linker();exit;}
if(isset($_GET["emailing-builder-linker-simple"])){emailing_builder_linker_simple();exit;}
if(isset($_GET["emailing-build-emailrelays"])){emailing_build_emailrelays();exit;}
if(isset($_GET["emailrelay-ou-status"])){emailing_emailrelays_status_ou();exit;}
if(isset($_GET["emailing-make-unique-table"])){emailing_database_make_unique();exit;}



if(isset($_GET["emailing-remove-emailrelays"])){emailing_emailrelays_remove();exit;}

//restore

if(isset($_GET["cyr-restore"])){cyrus_restore_mount_dir();exit;}
if(isset($_GET["cyr-restore-container"])){cyr_restore_container();;exit;}
if(isset($_GET["cyr-restore-mailbox"])){cyr_restore_mailbox();;exit;}


//WIFI

if(isset($_GET["wifi-ini-status"])){WIFI_INI_STATUS();exit;}
if(isset($_GET["wifi-eth-status"])){WIFI_ETH_STATUS();exit;}
if(isset($_GET["wifi-eth-client-check"])){WIFI_ETH_CHECK();exit;}



//openssh
if(isset($_GET["openssh-ini-status"])){SSHD_INI_STATUS();exit;}
if(isset($_GET["openssh-config"])){SSHD_GET_CONF();exit;}
if(isset($_GET["sshd-restart"])){SSHD_RESTART();exit;}

if(isset($_GET["ssh-keygen-fingerprint"])){SSHD_KEY_FINGERPRINT();exit;}
if(isset($_GET["ssh-keygen-download"])){SSHD_KEY_DOWNLOAD_PUB();exit;}
if(isset($_GET["sshd-authorized-keys"])){SSHD_KEY_UPLOAD_PUB();exit;}

//SQUID

if(isset($_GET["squid-status"])){SQUID_STATUS();exit;}
if(isset($_GET["squid-reload"])){SQUID_RELOAD();exit;}
if(isset($_GET["squid-ini-status"])){SQUID_INI_STATUS();exit;}
if(isset($_GET["cntlm-ini-status"])){CNTLM_INI_STATUS();exit;}



if(isset($_GET["ufdb-ini-status"])){UFDB_INI_STATUS();exit;}
if(isset($_GET["cicap-ini-status"])){CICAP_INI_STATUS();exit;}
if(isset($_GET["monit-ini-status"])){MONIT_INI_STATUS();exit;}
if(isset($_GET["sarg-ini-status"])){SARG_INI_STATUS();exit;}



if(isset($_GET["squid-restart"])){SQUID_RESTART();exit;}
if(isset($_GET["squid-restart-now"])){SQUID_RESTART_NOW();exit;}
if(isset($_GET["force-restart-squidonly"])){SQUID_RESTART_ONLY();exit;}
if(isset($_GET["squid-build-caches"])){SQUID_CACHES();exit;}
if(isset($_GET["squid-build-caches-output"])){SQUID_CACHESOUTPUT();exit;}

if(isset($_GET["squid-task-caches"])){SQUID_TASK_CACHE();exit;}
if(isset($_GET["squid-templates"])){SQUID_TEMPLATES();exit;}

if(isset($_GET["squid-reconstruct-caches"])){SQUID_CACHES_RECONSTRUCT();exit;}




if(isset($_GET["Sarg-Scan"])){SQUID_SARG_SCAN();exit;}
if(isset($_GET["squid-GetOrginalSquidConf"])){squid_originalconf();exit;}
if(isset($_GET["MalwarePatrol"])){MalwarePatrol();exit;}
if(isset($_GET["MalwarePatrol-list"])){MalwarePatrol_list();exit;}


if(isset($_GET["force-upgrade-squid"])){SQUID_FORCE_UPGRADE();exit;}
if(isset($_GET["squid-cache-infos"])){SQUID_CACHE_INFOS();exit;}

if(isset($_GET["proxy-pac-build"])){SQUID_PROXY_PAC_REBUILD();exit;}
if(isset($_GET["proxy-pac-show"])){SQUID_PROXY_PAC_SHOW();exit;}
if(isset($_GET["squid-conf-view"])){SQUID_CONF_EXPORT();exit;}




if(isset($_GET["reload-squidguard"])){SQUIDGUARD_RELOAD();exit;}
if(isset($_GET["squidguard-db-status"])){squidGuardDatabaseStatus();exit;}
if(isset($_GET["squidguard-db-maint"])){squidGuardDatabaseMaintenance();exit;}
if(isset($_GET["squidguard-db-maint-now"])){squidGuardDatabaseMaintenanceNow();exit;}



if(isset($_GET["squidguard-status"])){squidGuardStatus();exit;}
if(isset($_GET["squidguard-tests"])){squidguardTests();exit;}
if(isset($_GET["reload-squidguardWEB"])){SQUIDGUARD_WEB_RELOAD();exit;}
if(isset($_GET["philesize-img"])){philesizeIMG();exit;}
if(isset($_GET["philesize-img-path"])){philesizeIMGPath();exit;}

//samba
if(isset($_GET["smblient"])){samba_smbclient();exit;}
if(isset($_GET["smb-logon-scripts"])){samba_logon_scripts();exit;}
if(isset($_GET["samba-events-list"])){samba_events_lists();exit;}
if(isset($_GET["samba-move-logs"])){samba_move_logs();exit;}
if(isset($_GET["samba-delete-logs"])){samba_delete_logs();exit;}
if(isset($_GET["winbindd-stop"])){winbindd_stop();exit;}
if(isset($_GET["samba-server-role"])){samba_server_role();exit;}
if(isset($_GET["samba-reconfigure"])){samba_reconfigure();exit;}





if(isset($_GET["add-acl-group"])){samba_add_acl_group();exit;}
if(isset($_GET["add-acl-user"])){samba_add_acl_user();exit;}
if(isset($_GET["change-acl-user"])){samba_change_acl_user();exit;}
if(isset($_GET["change-acl-group"])){samba_change_acl_group();exit;}
if(isset($_GET["delete-acl-group"])){samba_delete_acl_group();exit;}
if(isset($_GET["delete-acl-user"])){samba_delete_acl_user();exit;}
if(isset($_GET["change-acl-items"])){samba_change_acl_items();exit;}
if(isset($_GET["wbinfo-domain"])){samba_wbinfo_domain();exit;}
if(isset($_GET["net-ads-info"])){net_ads_info();exit;}




//postfix
if(isset($_GET["postfixQueues"])){postfixQueues();exit;}
if(isset($_GET["getMainCF"])){postfix_read_main();exit;}
if(isset($_GET["postfix-tail"])){postfix_tail();exit;}
if(isset($_GET["postfix-hash-tables"])){postfix_hash_tables();exit;}
if(isset($_GET["postfix-transport-maps"])){postfix_hash_transport_maps();exit;}
if(isset($_GET["postfix-hash-senderdependent"])){postfix_hash_senderdependent();exit;}
if(isset($_GET["postfix-hash-aliases"])){postfix_hash_aliases();exit;}
if(isset($_GET["postfix-hash-r-canonical"])){postfix_hash_recipient_canonical();exit;}
if(isset($_GET["postfix-bcc-tables"])){postfix_hash_bcc();exit;}
if(isset($_GET["postfix-relayhost"])){postfix_relayhost();exit;}
if(isset($_GET["postfix-smtp-sasl"])){postfix_sasl();exit;}
if(isset($_GET["postfix-multi-transport-maps"])){postfix_multi_transport_maps();exit;}

if(isset($_GET["rbl-check"])){rbl_check();exit;}
if(isset($_GET["my-rbl-check"])){my_rbl_check();exit;}



if(isset($_GET["postfix-others-values"])){postfix_others_values();exit;}

if(isset($_GET["postfix-interfaces"])){postfix_interfaces();exit;}
if(isset($_GET["postfix-networks"])){postfix_single_mynetworks();exit;}
if(isset($_GET["postfix-luser-relay"])){postfix_luser_relay();exit;}
if(isset($_GET["postqueue-master-list"])){postfix_postqueue_master();exit;}
if(isset($_GET["postsuper-r-master"])){postfix_postqueue_reprocess_msgid();exit;}
if(isset($_GET["smtp-domains-import"])){postfix_import_domains_ou();exit;}
if(isset($_GET["smtp-import-events"])){postfix_import_domains_ou_events();exit;}
if(isset($_GET["get-main-cf"])){postfix_get_main_cf();exit;}





if(isset($_GET["ChangeLDPSSET"])){ChangeLDPSSET();exit;}
if(isset($_GET["ASSPOriginalConf"])){ASSPOriginalConf();exit;}
if(isset($_GET["SetupCenter"])){SetupCenter();exit;}
if(isset($_GET["restart-assp"])){RestartASSPService();exit;}
if(isset($_GET["reload-assp"])){ReloadASSPService();exit;}
if(isset($_GET["restart-mailgraph"])){RestartMailGraphService();exit;}
if(isset($_GET["restart-mysql"])){RestartMysqlDaemon();exit;}


if(isset($_GET["restart-openvpn-server"])){RestartOpenVPNServer();exit;}
if(isset($_GET["openvpn-rebuild-certificate"])){openvpn_rebuild_certificates();exit;}
if(isset($_GET["OpenVPNServerSessions"])){openvpn_sesssions();exit;}
if(isset($_GET["openvpn-client-sesssions"])){openvpn_client_sesssions();exit;}
if(isset($_GET["openvpn-server-schedule"])){openvpn_server_exec_schedule();exit;}
if(isset($_GET["openvpn-status"])){openvpn_server_status();exit;}
if(isset($_GET["openvpn-clients-status"])){openvpn_clients_status();exit;}

if(isset($_GET["restart-strongswan-server"])){RestartstrongSwanServer();exit;}



if(isset($_GET["read-log"])){read_log();exit;}
//assp
if(isset($_GET["rsync-reconfigure"])){rsync_reconfigure();exit;}


//mailman
if(isset($_GET["syncro-mailman"])){MailManSync();exit;}
if(isset($_GET["restart-mailman"])){RestartMailManService();exit;}
if(isset($_GET["MailMan-List"])){MailManList();exit;}
if(isset($_GET["mailman-delete"])){MailManDelete();exit;}
if(isset($_GET["MailManSaveGlobalSettings"])){MailManSync();exit;}

//DHCPD
if(isset($_GET["restart-dhcpd"])){RestartDHCPDService();exit;}
if(isset($_GET["apply-dhcpd"])){RestartDHCPDService();exit;}
if(isset($_GET["apply-bind"])){ApplyBINDService();exit;}
if(isset($_GET["dhcpd-status"])){dhcp_status();exit;}



if(isset($_GET["MySqlPerf"])){MySqlPerf();exit;}
if(isset($_GET["mysql-audit"])){MysqlAudit();exit;}
if(isset($_GET["RestartDaemon"])){RestartDaemon();exit;}
if(isset($_GET["restart-apache-no-timeout"])){RestartApacheNow();exit;}

//network
if(isset($_GET["SaveNic"])){Reconfigure_nic();exit;}
if(isset($_GET["nic-add-route"])){Reconfigure_routes();exit;}


if(isset($_GET["dnslist"])){DNS_LIST();exit;}
if(isset($_GET["hostToIp"])){hostToIp();exit;}



//WIFI
if(isset($_GET["iwlist"])){iwlist();exit;}
if(isset($_GET["start-wifi"])){start_wifi();exit;}

//imapSYnc

if(isset($_GET["imapsync-events"])){imapsync_events();exit;}
if(isset($_GET["imapsync-cron"])){imapsync_cron();exit;}
if(isset($_GET["imapsync-run"])){imapsync_run();exit;}
if(isset($_GET["imapsync-stop"])){imapsync_stop();exit;}
if(isset($_GET["imapsync-show"])){imapsync_show();exit;}

//gluster
if(isset($_GET["gluster-remounts"])){GLUSTER_REMOUNT();exit;}
if(isset($_GET["gluster-mounts"])){GLUSTER_MOUNT();exit;}
if(isset($_GET["gluster-update-clients"])){GLUSTER_UPDATE_CLIENTS();exit;}
if(isset($_GET["gluster-restart"])){GLUSTER_RESTART();exit;}
if(isset($_GET["gluster-delete-clients"])){GLUSTER_DELETE_CLIENTS();exit;}
if(isset($_GET["gluster-notify-clients"])){GLUSTER_NOTIFY_CLIENTS();exit;}
if(isset($_GET["glfs-is-mounted"])){GLUSTER_IS_MOUNTED();exit;}

if(isset($_GET["lessfs"])){LESSFS_RESTART();exit;}
if(isset($_GET["lessfs-mounts"])){LESSFS_MOUNTS();exit;}
if(isset($_GET["lessfs-restart"])){LESSFS_RESTART_SERVICE();exit;}


	
//cyrus
if(isset($_GET["cyrus-backup-now"])){CyrusBackupNow();exit;}
if(isset($_GET["restart-cyrus"])){RestartCyrusImapDaemon();exit;}
if(isset($_GET["reload-cyrus"])){ReloadCyrus();exit;}
if(isset($_GET["reconfigure-cyrus"])){ReconfigureCyrusImapDaemon();exit;} // --reconfigure-cyrus
if(isset($_GET["reconfigure-cyrus-debug"])){ReconfigureCyrusImapDaemonDebug();exit;} // --reconfigure-cyrus
if(isset($_GET["restart-cyrus-debug"])){rRestartCyrusImapDaemonDebug();exit;} // --reconfigure-cyrus
if(isset($_GET["repair-mailbox"])){CyrusRepairMailbox();exit;}
if(isset($_GET["cyr-restore-computer"])){cyr_restore_computer();exit;}

//backup
if(isset($_GET["backup-sql-test"])){backup_sql_tests();exit;}
if(isset($_GET["backup-build-cron"])){backup_build_cron();exit;}
if(isset($_GET["backup-task-run"])){backup_task_run();exit;}


if(isset($_GET["backuppc-ini-status"])){BACKUPPPC_INI_STATUS();exit;}
if(isset($_GET["backuppc-affect"])){backuppc_affect();exit;}
if(isset($_GET["backuppc-comp"])){backuppc_load_computer_config();exit;}
if(isset($_GET["backuppc-save-computer"])){backuppc_save_computer_config();exit;}
if(isset($_GET["restart-backuppc"])){backuppc_restart();exit;}
if(isset($_GET["backuppc-computer-infos"])){backuppc_computer_infos();exit;}


//apache
if(isset($_GET["restart-groupware-server"])){RestartGroupwareWebServer();exit;}
if(isset($_GET["philesight-perform"])){philesight_perform();exit;}

//postfix

if(isset($_GET["SaveMaincf"])){SaveMaincf();exit;}
if(isset($_GET["sasl-finger"])){SASL_FINGER();exit;}
if(isset($_GET["pluginviewer"])){SASL_pluginviewer();exit;}


if(isset($_GET["reconfigure-postfix"])){postfix_reconfigure();exit;}
if(isset($_GET["postfix-stat"])){postfix_stat();exit;}
if(isset($_GET["postfix-multi-queues"])){postfix_multi_queues();exit;}
if(isset($_GET["postfix-mutli-stat"])){postfix_multi_stat();exit;}
if(isset($_GET["postfix-multi-configure-ou"])){postfix_multi_configure();exit;}
if(isset($_GET["postfix-multi-disable"])){postfix_multi_disable();exit;}
if(isset($_GET["postfix-restricted-users"])){postfix_restricted_users();exit;}
if(isset($_GET["postfix-multi-postqueue"])){postfix_multi_postqueue();exit;}
if(isset($_GET["postfix-multi-cfdb"])){postfix_multi_cfdb();exit;}




if(isset($_GET["smtp-hack-reconfigure"])){smtp_hack_reconfigure();exit;}
if(isset($_GET["cups-delete-printer"])){cups_delete_printer();exit;}


//samba
if(isset($_GET["samba-save-config"])){samba_save_config();exit;}
if(isset($_GET["samba-build-homes"])){samba_build_homes();exit;}
if(isset($_GET["restart-samba"])){samba_restart();exit;}
if(isset($_GET["restart-samba-now"])){samba_restart_now();exit;}
if(isset($_GET["Debugpdbedit"])){samba_pdbedit_debug();exit;}
if(isset($_GET["pdbedit"])){samba_pdbedit();exit;}
if(isset($_GET["pdbedit-group"])){samba_pdbedit_group();exit;}
if(isset($_GET["samba-status"])){samba_status();exit;}
if(isset($_GET["samba-shares-list"])){samba_shares_list();exit;}
if(isset($_GET["samba-synchronize"])){samba_synchronize();exit;}
if(isset($_GET["samba-change-sid"])){samba_change_sid();exit;}
if(isset($_GET["samba-original-conf"])){samba_original_config();exit;}
if(isset($_GET["GetLocalSid"])){GET_LOCAL_SID();exit;}

if(isset($_GET["smbpass"])){samba_password();exit;}
if(isset($_GET["home-single-user"])){samba_build_home_single();exit;}

//dropbox
if(isset($_GET["dropbox-status"])){dropbox_status();exit;}
if(isset($_GET["dropbox-service-status"])){dropbox_service_status();exit;}
if(isset($_GET["dropbox-service-uri"])){dropbox_service_uri();exit;}
if(isset($_GET["dropbox-service-dump"])){dropbox_files_status();exit;}


//

//squid;
if(isset($_GET["squidnewbee"])){squid_config();exit;}
if(isset($_GET["cicap-reconfigure"])){cicap_reconfigure();exit;}
if(isset($_GET["cicap-reload"])){cicap_reload();exit;}
if(isset($_GET["cicap-restart"])){cicap_restart();exit;}
if(isset($_GET["MalwarePatrolDatabasesCount"])){MalwarePatrolDatabasesCount();exit;}

if(isset($_GET["artica-filter-reload"])){ReloadArticaFilter();exit;}
if(isset($_GET["artica-policy-restart"])){RestartArticaPolicy();exit;}
if(isset($_GET["artica-policy-reload"])){ReloadArticaPolicy();exit;}



if(isset($_GET["dirdir"])){dirdir();exit;}
if(isset($_GET["dirdirEncoded"])){dirdir_Encoded();exit;}


if(isset($_GET["du-dir-size"])){du_dir_size();exit;}



if(isset($_GET["view-file-logs"])){ViewArticaLogs();exit;}
if(isset($_GET["ExecuteImportationFrom"])){ExecuteImportationFrom();exit;}
if(isset($_GET["squid-reconfigure"])){RestartSquid();exit;}

if(isset($_GET["EnableEmergingThreats"])){EnableEmergingThreats();exit;}
if(isset($_GET["EnableEmergingThreatsBuild"])){EnableEmergingThreatsBuild();exit;}

//apache-groupware
if(isset($_GET["reload-apache-groupware"])){ReloadApacheGroupWare();exit;}
if(isset($_GET["build-vhosts"])){BuildVhosts();exit;}
if(isset($_GET["vhost-delete"])){DeleteVHosts();exit;}
if(isset($_GET["install-joomla"])){JOOMLA_INSTALL();exit;}

if(isset($_GET["replicate-performances-config"])){ReplicatePerformancesConfig();exit;}
if(isset($_GET["reload-dansguardian"])){reload_dansguardian();exit;}
if(isset($_GET["reload-ufdbguard"])){reload_ufdbguard();exit;}
if(isset($_GET["ufdbguard-recompile-missing-dbs"])){ufdbguard_compile_missing_dbs();exit;}
if(isset($_GET["ufdbguard-recompile-dbs"])){ufdbguard_compile_all_dbs();exit;}
if(isset($_GET["ufdbguard-compile-schedule"])){ufdbguard_compile_schedule();exit;}




if(isset($_GET["ufdbguard-compilator-events"])){ufdbguard_compilator_events();exit;}



if(isset($_GET["dansguardian-template"])){dansguardian_template();exit;}
if(isset($_GET["dansguardian-get-template"])){dansguardian_get_template();exit;}


if(isset($_GET["searchww-cat"])){dansguardian_search_categories();exit;}
if(isset($_GET["export-community-categories"])){dansguardian_community_categories();exit;}
if(isset($_GET["create-user-folder"])){directory_create_user();exit;}
if(isset($_GET["delete-user-folder"])){directory_delete_user();exit;}



//disks
if(isset($_GET["disks-list"])){disks_list();exit;}
if(isset($_GET["disks-inodes"])){disks_inodes();exit;}
if(isset($_GET["lvm-lvs"])){lvs_scan();exit;}
if(isset($_GET["sfdisk-dump"])){sfdisk_dump();exit;}
if(isset($_GET["mkfs"])){mkfs();exit;}
if(isset($_GET["parted-print"])){parted_print();exit;}
if(isset($_GET["format-disk-unix"])){format_disk_unix();exit;}
if(isset($_GET["lvs-mapper"])){LVM_LVS_DEV_MAPPER();exit;}
if(isset($_GET["check-dev"])){DEV_CHECK();}

if(isset($_GET["fstablist"])){fstab_list();exit;}
if(isset($_GET["path-acls"])){acls_infos();exit;}
if(isset($_GET["chmod-access"])){chmod_access();exit;}
if(isset($_GET["acls-status"])){acls_status();exit;}
if(isset($_GET["acls-apply"])){acls_apply();exit;}
if(isset($_GET["acls-delete"])){acls_delete();exit;}
if(isset($_GET["acls-rebuild"])){acls_rebuild();exit;}



if(isset($_GET["IsDir"])){IsDir();exit;}
if(isset($_GET["hdparm-infos"])){hdparm_infos();exit;}
if(isset($_GET["disk-change-label"])){disks_change_label();exit;}
if(isset($_GET["disk-get-label"])){disks_get_label();exit;}
if(isset($_GET["udevinfos"])){udevinfos();exit;}



// cmd.php?fstab-acl=yes&acl=$acl&dev=$dev
if(isset($_GET["fstab-acl"])){fstab_acl();exit;}
if(isset($_GET["fstab-quota"])){fstab_quota();exit;}
if(isset($_GET["fstab-remove"])){fstab_del();exit;}
if(isset($_GET["DiskInfos"])){DiskInfos();exit;}
if(isset($_GET["fstab-get-mount-point"])){fstab_get_mount_point();exit;}
if(isset($_GET["get-mounted-path"])){disk_get_mounted_point();exit;}
if(isset($_GET["fdisk-build-big-partitions"])){disk_format_big_partition();exit;}
if(isset($_GET["chown"])){directory_chown();exit;}
if(isset($_GET["quotastats"])){quotastats();exit;}
if(isset($_GET["repquota"])){repquota();exit;}
if(isset($_GET["setquota"])){setquota();exit;}
if(isset($_GET["quotas-recheck"])){quotasrecheck();exit;}





if(isset($_GET["umount-disk"])){umount_disk();exit;}
if(isset($_GET["lvremove"])){LVM_REMOVE();exit;}
if(isset($_GET["fdiskl"])){fdisk_list();exit;}
if(isset($_GET["lvmdiskscan"])){lvmdiskscan();exit;}
if(isset($_GET["pvscan"])){pvscan();exit;}
if(isset($_GET["vgs-info"])){LVM_VGS_INFO();exit;}
if(isset($_GET["vg-disks"])){LVM_VG_DISKS();exit;}
if(isset($_GET["lvdisplay"])){LVM_LV_DISPLAY();exit;}



if(isset($_GET["lvm-unlink-disk"])){LVM_UNLINK_DISK();exit;}
if(isset($_GET["lvm-link-disk"])){LVM_LINK_DISK();exit;}
if(isset($_GET["vgcreate-dev"])){LVM_CREATE_GROUP();exit;}
if(isset($_GET["DirectorySize"])){disk_directory_size();exit;}
if(isset($_GET["filemd5"])){FILE_MD5();exit;}
if(isset($_GET["read-file"])){ReadFromfile();exit;}



if(isset($_GET["lvs-all"])){LVM_lVS_INFO_ALL();exit;}
if(isset($_GET["lv-resize-add"])){LVM_LV_ADDSIZE();exit;}
if(isset($_GET["lv-resize-red"])){LVM_LV_DELSIZE();exit;}
if(isset($_GET["disk-ismounted"])){disk_ismounted();exit;}
if(isset($_GET["disks-quotas-list"])){disks_quotas_list();exit;}
if(isset($_GET["dfmoinshdev"])){disks_dfmoinshdev();exit;}



if(isset($_GET["filesize"])){z_file_size();exit;}
if(isset($_GET["filetype"])){file_type();exit;}
if(isset($_GET["mime-type"])){mime_type();exit;}

if(isset($_GET["sync-remote-smtp-artica"])){postfix_sync_artica();exit;}

//etc/hosts
if(isset($_GET["etc-hosts-open"])){etc_hosts_open();exit;}
if(isset($_GET["etc-hosts-add"])){etc_hosts_add();exit;}
if(isset($_GET["etc-hosts-del"])){etc_hosts_del();exit;}
if(isset($_GET["etc-hosts-del-by-values"])){etc_hosts_del_by_values();exit;}



if(isset($_GET["full-hostname"])){hostname_full();exit;}

//computers
if(isset($_GET["nmap-scan"])){nmap_scan();exit;}


//users UNix
if(isset($_GET["unixLocalUsers"])){PASSWD_USERS();exit;}


//tcp
if(isset($_GET["ifconfig-interfaces"])){ifconfig_interfaces();exit;}
if(isset($_GET["ifconfig-all"])){ifconfig_all();exit;}
if(isset($_GET["ifconfig-all-ips"])){ifconfig_all_ips();exit;}
if(isset($_GET["resolv-conf"])){resolv_conf();exit;}
if(isset($_GET["myos"])){MyOs();exit;}
if(isset($_GET["lspci"])){lspci();exit;}
if(isset($_GET["freemem"])){freemem();exit;}
if(isset($_GET["dfmoinsh"])){dfmoinsh();exit;}
if(isset($_GET["printenv"])){printenv();exit;}
if(isset($_GET["GenerateCert"])){GenerateCert();exit;}
if(isset($_GET["all-status"])){GLOBAL_STATUS();exit;}
if(isset($_GET["procstat"])){procstat();exit;}
if(isset($_GET["nic-infos"])){TCP_NIC_INFOS();exit;}



if(isset($_GET["ip-to-mac"])){ip_to_mac();exit;}
if(isset($_GET["arp-ip"])){arp_and_ip();exit;}
if(isset($_GET["hostToMac"])){hostToMac();exit;}
if(isset($_GET["browse-computers-import-list"])){import_computer_from_list();exit;}




if(isset($_GET["refresh-status"])){RefreshStatus();exit;}

if(isset($_GET["SpamassassinReload"])){reloadSpamAssassin();exit;}
if(isset($_GET["SpamAssassin-Reload"])){reloadSpamAssassin();exit;}
if(isset($_GET["spamass-check"])){spamassassin_check();exit;}
if(isset($_GET["spamass-trust-nets"])){spamassassin_trust_networks();exit;}
if(isset($_GET["SpamAssDBVer"])){SpamAssDBVer();exit;}
if(isset($_GET["spamass-build"])){spamassassin_rebuild();exit;}





if(isset($_GET["SetupIndexFile"])){SetupIndexFile();exit;}
if(isset($_GET["install-web-services"])){InstallWebServices();exit;}
if(isset($_GET["install-web-service-unique"])){InstallWebServiceUnique();exit;}
if(isset($_GET["ForceRefreshLeft"])){ForceRefreshLeft();exit;}
if(isset($_GET["ForceRefreshRight"])){ForceRefreshRight();exit;}
if(isset($_GET["perform-autoupdate"])){artica_update();exit;}


if(isset($_GET["ComputerRemoteRessources"])){ComputerRemoteRessources();exit;}
if(isset($_GET["DumpPostfixQueue"])){DumpPostfixQueue();exit;}
if(isset($_GET["smtp-whitelist"])){SMTP_WHITELIST();exit;}
if(isset($_GET["LaunchNetworkScanner"])){LaunchNetworkScanner();exit;}
if(isset($_GET["idofUser"])){idofUser();exit;}
if(isset($_GET["php-rewrite"])){rewrite_php();exit;}

if(isset($_GET["B64-dirdir"])){dirdirBase64();exit;}
if(isset($_GET["Dir-Files"])){Dir_Files();exit;}
if(isset($_GET["Dir-Directories"])){Dir_Directories();exit;}

if(isset($_GET["filestat"])){filestat();exit;}
if(isset($_GET["create-folder"])){folder_create();exit;}
if(isset($_GET["folder-remove"])){folder_delete();exit;}
if(isset($_GET["file-content"])){file_content();exit;}
if(isset($_GET["file-remove"])){file_remove();exit;}

//CLUSTERS
if(isset($_GET["notify-clusters"])){CLUSTER_NOTIFY();exit;}
if(isset($_GET["cluster-restart-notify"])){CLUSTER_CLIENT_RESTART_NOTIFY();exit;}
if(isset($_GET["cluster-delete"])){CLUSTER_DELETE();exit;}
if(isset($_GET["cluster-add"])){CLUSTER_ADD();exit;}

//computers
if(isset($_GET["computers-import-nets"])){COMPUTERS_IMPORT_ARTICA();exit;}
if(isset($_GET["smbclientL"])){smbclientL();exit;}

//paths 
if(isset($_GET["SendmailPath"])){SendmailPath();exit;}
if(isset($_GET["release-quarantine"])){release_quarantine();exit;}

//policyd-weight
if(isset($_GET["PolicydWeightReplicConF"])){Restart_Policyd_Weight();exit;}

//dansguardian
if(isset($_GET["dansguardian-update"])){dansguardian_update();exit;}
if(isset($_GET["shalla-update-now"])){shalla_update();exit;}

if(isset($_GET["uri"])) {
	$uri = $_GET["uri"];

	switch ($uri) {
		case "GlobalApplicationsStatus":
			GlobalApplicationsStatus();
			break;
		case "artica_version":
			artica_version();
			break;
		case "daemons_status":
			daemons_status();
			break;
		case "pid":
			echo "<articadatascgi>" . getmypid() . "</articadatascgi>";
			break;
		case "myhostname";
			myhostname();
			break;

		default:

			break;
	}
}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);

function SMTP_WHITELIST(){
	
	NOHUP_EXEC( LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.opendkim.php --whitelist");
	NOHUP_EXEC( LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.spamassassin.php --whitelist");
	NOHUP_EXEC( LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.iptables.php --compile");
}

function repair_settings_inc(){
	
	@unlink("/usr/share/artica-postfix/ressources/settings.bak"); 
	@unlink("/usr/share/artica-postfix/ressources/settings.inc"); 
	
}

function _filesize(){
	$size=filesize(base64_decode($_GET["filesize"]));
	echo "<articadatascgi>$size</articadatascgi>";
}
function _chmod(){
	$filepath=base64_decode($_GET["chmod"]);
	$unix=new unix();
	$num=base64_decode($_GET["num"]);
	$chmod=$unix->find_program("chmod");
	$cmd="$chmod $num \"$filepath\" 2>&1";
	if(!is_file("$filepath")){$results[]="No such file...\n";}
	if(is_file($filepath)){
		$results[]=$cmd;
		exec("$chmod $num \"$filepath\"",$results);
	}
	echo "<articadatascgi>".base64_encode(@implode("\n", $results))."</articadatascgi>";	
}

function artica_update_query_fileslogs(){
	$unix=new unix();
	$array=$unix->DirFiles("{$GLOBALS["ARTICALOGDIR"]}");
	echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";	
}
function artica_update_query_logs(){
	$_GET["file"]=str_replace("../","",$_GET["file"]);
	$array=explode("\n",@file_get_contents("{$GLOBALS["ARTICALOGDIR"]}/{$_GET["file"]}"));
	echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";	
}
function _readfile(){
	$filrepath=base64_decode($_GET["readfile"]);
	echo "<articadatascgi>".base64_encode(@file_get_contents($filrepath))."</articadatascgi>";
}


function nmap_scan(){
	 $unix=new unix();
	 $computer=$unix->shellEscapeChars($_GET["nmap-scan"]);
	 writelogs_framework("Scan the computer:{$_GET["nmap-scan"]}=$computer",__FUNCTION__,__FILE__,__LINE__);
	 $cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.nmapscan.php $computer";
   	 writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	 exec($cmd,$results);
	 echo "<articadatascgi>". @implode("\n",$results)."</articadatascgi>";		
}


function acls_infos(){
	$unix=new unix();
	$path=base64_decode($_GET["path-acls"]);
	$array=array();
	if(!is_dir($path)){
		$array[0]="NO_SUCH_DIR";
		writelogs_framework("$path -> NO_SUCH_DIR",__FUNCTION__,__FILE__,__LINE__);
		echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
		return true;
	}
	
	if(isset($_GET["justdirectoryTests"])){return true;}
	
	$getfacl=$unix->find_program("getfacl");
	if($getfacl==null){return false;}
	exec("$getfacl --tabular \"$path\" 2>&1",$results);
	
	foreach ($results as $line){
		
		if(preg_match("#USER\s+(.+?)\s+(.*)#",$line,$re)){
			$array["OWNER"]=array("NAME"=>$re[1],"RIGHTS"=>$re[2],"DEFAULT"=>true);
			continue;
		}
		
		if(preg_match("#GROUP\s+(.+?)\s+(.*)#",$line,$re)){
			$array["GROUP"]=array("NAME"=>$re[1],"RIGHTS"=>$re[2],"DEFAULT"=>true);
			continue;
		}	

		if(preg_match("#other\s+(.+?)\s+(.*)#",$line,$re)){
			$array["other"]=array("NAME"=>$re[1],"RIGHTS"=>$re[2]);
			continue;
		}			

		if(preg_match("#user\s+(.+?)\s+\s+(.*)#",$line,$re)){
			$array["users"][]=array("NAME"=>$re[1],"RIGHTS"=>$re[2]);
			continue;
		}
		
		if(preg_match("#group\s+(.+?)\s+\s+(.*)#",$line,$re)){
			$array["groups"][]=array("NAME"=>$re[1],"RIGHTS"=>$re[2]);
			continue;
		}		

		
	}
	
	echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
	return true;
}

function IsDir():bool{
	$_GET["IsDir"]=base64_decode($_GET["IsDir"]);
	if(is_dir($_GET["IsDir"])){
		echo "<articadatascgi>".base64_encode("TRUE")."</articadatascgi>";
	}
	return true;
}


function z_file_size():bool{
	$res="";
	$unix=new unix();
	$_GET["filesize"]=$unix->shellEscapeChars($_GET["filesize"]);
	exec($unix->find_program("stat")." {$_GET["filesize"]} ",$results);
	foreach ($results as $num=>$line){
		if(preg_match("#Size:\s+([0-9]+)\s+Blocks#",$line,$re)){
			$res=$re[1];break;
		}
	}
	echo "<articadatascgi>$res</articadatascgi>";	
	return true;
}

function file_type(){
$unix=new unix();
$filetype=base64_decode($_GET["filetype"]);
	exec($unix->find_program("file")." \"$filetype\" ",$results);	
foreach ($results as $num=>$line){
		if(preg_match("#.+?:\s+(.+?)$#",$line,$re)){
			$res=$re[1];break;
		}
	}
	echo "<articadatascgi>".base64_encode($res)."</articadatascgi>";	
}

function mime_type(){
$unix=new unix();
$filetype=base64_decode($_GET["mime-type"]);
	exec($unix->find_program("file")." -i -b \"$filetype\" ",$results);	
foreach ($results as $num=>$line){
		if(preg_match("#.+?;.+?$#",$line,$re)){
			$res=$line;break;
		}
	}
	echo "<articadatascgi>".base64_encode($res)."</articadatascgi>";	
}	


function COMPUTERS_IMPORT_ARTICA(){
	NOHUP_EXEC( LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.import-networks.php");
	
}
function ReloadCyrus(){
	@chmod("/usr/share/artica-postfix/bin/artica-install",0755);
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --reload-cyrus");
}

function import_computer_from_list(){
	writelogs_framework("STARTING" ,__FUNCTION__,__FILE__,__LINE__);
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/ocs.import.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/ocs.import.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.computer.scan.php --import-list >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function format_disk_unix(){
	$logs=md5($_GET["format-disk-unix"]);
	@unlink("/usr/share/artica-postfix/ressources/logs/$logs.format");
	@chmod("/usr/share/artica-postfix/bin/artica-install",0755);
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --format-disk-unix {$_GET["format-disk-unix"]} --verbose >/usr/share/artica-postfix/ressources/logs/$logs.format 2>&1");
}
function read_log(){
	if(!is_file("/usr/share/artica-postfix/ressources/logs/{$_GET["read-log"]}")){
		writelogs_framework("unable to stat /usr/share/artica-postfix/ressources/logs/{$_GET["read-log"]}");
		return;
	}
echo "<articadatascgi>". @file_get_contents("/usr/share/artica-postfix/ressources/logs/{$_GET["read-log"]}")."</articadatascgi>";	
}


function StartServiceCMD(){
	$cmd=$_GET["start-service-name"];
	$cmdline="/etc/init.d/artica-postfix start $cmd 2>&1";
	writelogs_framework($cmdline,__FUNCTION__,__FILE__,__LINE__);
	exec("/etc/init.d/artica-postfix start $cmd",$results);
	$datas=base64_encode(serialize($results));
	echo "<articadatascgi>$datas</articadatascgi>";	
}
function StopServiceCMD(){
	$cmd=$_GET["stop-service-name"];
	$cmdline="/etc/init.d/artica-postfix stop $cmd 2>&1";
	writelogs_framework($cmdline,__FUNCTION__,__FILE__,__LINE__);
	exec($cmdline,$results);
	$datas=base64_encode(serialize($results));
	echo "<articadatascgi>$datas</articadatascgi>";	
}

function DEV_CHECK():bool{
	$dev=$_GET["check-dev"];
	if(is_link($dev)){
		$link=readlink($dev);
		$dev=str_replace("../mapper","/dev/mapper",$link);
		echo "<articadatascgi>$dev</articadatascgi>";
		return true;
	}
	echo "<articadatascgi>$dev</articadatascgi>";
	return true;

}

function SQUID_STATUS(){
	$cachefile=PROGRESS_DIR."/squid.status";
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --squid --nowachdog >$cachefile";
	@chmod($cachefile,0755);
	writelogs_framework($cmd);
	shell_exec($cmd);
	echo "<articadatascgi>". @file_get_contents($cachefile)."</articadatascgi>";
}
function SARG_INI_STATUS(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --sarg --nowachdog";
	exec($cmd,$results);
	writelogs_framework($cmd." ->".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";
}

function RDP_INI_STATUS(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --rdpproxy --nowachdog";
	exec($cmd,$results);
	writelogs_framework($cmd." ->".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";
}
function SYNCTHING_INI_STATUS(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --syncthing --nowachdog";
	exec($cmd,$results);
	writelogs_framework($cmd." ->".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";	
	
}
function UFDBCAT_INI_STATUS(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --ufdbcat --nowachdog";
	exec($cmd,$results);
	writelogs_framework($cmd." ->".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";
}
function HYPERCACHE_WEB_INI_STATUS(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --hypercacheweb --nowachdog";
	exec($cmd,$results);
	writelogs_framework($cmd." ->".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";
}
function HYPERCACHE_STOREID_INI_STATUS(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --hypercachestoreid --nowachdog >/usr/share/artica-postfix/ressources/logs/web/hypercache.status";
	exec($cmd,$results);
	writelogs_framework($cmd." ->".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";
}


function CNTLM_INI_STATUS(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --cntlm --nowachdog";
	exec($cmd,$results);
	writelogs_framework($cmd." ->".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";
}

function SQUID_INI_STATUS():bool{
	$unix=new unix();
	return $unix->framework_exec("exec.status.php --all-squid --nowachdog");
}
function UFDB_INI_STATUS(){
	SQUID_INI_STATUS();
}

function CICAP_INI_STATUS(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --c-icap --nowachdog";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";	
	
}
function MONIT_INI_STATUS(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --monit --nowachdog";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";

}
function SQUID_RELOAD(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid_reload.txt");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/squid_reload.txt",0755);
	squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
	$cmd="$nohup /etc/init.d/squid reload --script=cmd.php >/usr/share/artica-postfix/ressources/logs/web/squid_reload.txt 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$cmd");
	
}

function SSHD_INI_STATUS(){
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --openssh --nowachdog >/usr/share/artica-postfix/ressources/logs/web/sshd.status 2>&1");
	echo "<articadatascgi>". base64_encode(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/sshd.status"))."</articadatascgi>";
}

function STUNNEL_INI_STATUS(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --stunnel --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";
}

function STUNNEL_RESTART(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.stunnel.php --restart >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$cmd");
}



function BACKUPPPC_INI_STATUS(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --backuppc --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";
}


function MILTER_GREYLIST_INI_STATUS(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --milter-greylist --nowachdog >/usr/share/artica-postfix/ressources/logs/web/greylist.status 2>&1";
	shell_exec($cmd);
	
	
}

function WIFI_INI_STATUS(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --wifi --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";	
}


function SQUID_FORCE_UPGRADE(){
	NOHUP_EXEC( "/usr/share/artica-postfix/bin/artica-make APP_SQUID --reconfigure");
}

function artica_update(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	@chmod("/usr/share/artica-postfix/bin/artica-update",0755);
	$cmd=trim("$nohup /usr/share/artica-postfix/bin/artica-update --update --force >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function SQUID_SARG_SCAN(){
	$unix=new unix();
	$sarg=$unix->find_program("sarg");
	if(!is_file($sarg)){return null;}
	@chmod("/usr/share/artica-postfix/bin/artica-install",0755);
	exec("/usr/share/artica-postfix/bin/artica-install --sarg-scan",$results);
	$datas=base64_encode(serialize($results));
	echo "<articadatascgi>$datas</articadatascgi>";
	
}

function disk_ismounted(){
	$unix= new unix();
	$dev=$_GET["dev"];
	if(is_link($dev)){
		$link=@readlink($dev);
		$dev2=str_replace("../mapper","/dev/mapper",$link);
	}
	writelogs_framework("$dev OR $dev2 ",__FUNCTION__,__FILE__,__LINE__);
	if(!$unix->DISK_MOUNTED($dev)){
		if($dev2<>null){
			if($unix->DISK_MOUNTED($dev2)){
				echo "<articadatascgi>TRUE</articadatascgi>";
				return ;
			}
		}
	}else{
		echo "<articadatascgi>TRUE</articadatascgi>";
		return;
	}
	
	echo "<articadatascgi>FALSE</articadatascgi>";
	
}


function rsync_reconfigure(){
	NOHUP_EXEC( LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.rsync-lvm.php");
}

function DeleteVHosts(){
	$unix=new unix();
	$tmp=$unix->FILE_TEMP();
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.www.install.php remove {$_GET["vhost-delete"]} --verbose >$tmp 2>&1");
	echo "<articadatascgi>". @file_get_contents($tmp)."</articadatascgi>";
	@unlink($tmp);
	
}



function fstab_del(){
	$dev=$_GET["dev"];
	$unix=new unix();
	$unix->DelFSTab($dev);
}

function fstab_get_mount_point(){
	$dev=$_GET["dev"];
	$unix=new unix();
	$datas=$unix->GetFSTabMountPoint($dev);
	writelogs_framework(count($datas)." mounts points",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode(serialize($datas));
	echo "</articadatascgi>";	
}

function DiskInfos(){
	
	$dev=$_GET["DiskInfos"];
	$unix=new unix();
	exec($unix->find_program("df")." -h $dev",$results);
foreach ($results as $num=>$line){
		if(preg_match("#(.+?)\s+([0-9-A-Z,\.]+)\s+([0-9-A-Z,\.]+)\s+([0-9-A-Z,\.]+)\s+([0-9,\.]+)%\s+(.+)$#i",$line,$re)){
			if($re[6]=="/dev"){continue;}
			$array["SIZE"]=$re[2];
			$array["USED"]=$re[3];
			$array["FREE"]=$re[4];
			$array["POURC"]=$re[5];
			$array["MOUNTED"]=$re[6];
			echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
			break;
		}
	}	
	
}

function fstab_list(){
	$datas=explode("\n",@file_get_contents("/etc/fstab"));
	echo "<articadatascgi>".base64_encode(serialize($datas))."</articadatascgi>";
}

function ViewArticaLogs(){
	$datas=@file_get_contents("{$GLOBALS["ARTICALOGDIR"]}/{$_GET["view-file-logs"]}");
	echo "<articadatascgi>$datas</articadatascgi>";
	}
	
function ExecuteImportationFrom(){
	$path=$_GET["ExecuteImportationFrom"];
	NOHUP_EXEC( LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.cyrus-restore.php \"$path\"");
}
function LaunchNetworkScanner(){
		$unix=new unix();
		$nohup=$unix->find_program("nohup");
		$php5=LOCATE_PHP5_BIN2();
		$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.nmapscan.php  --scan-nets --verbose >/dev/null 2>&1 &");
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);			
}

function CLUSTER_NOTIFY(){
	$server=$_GET["notify-clusters"];
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.gluster.php --notify-client $server");
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.gluster.php");
}
function CLUSTER_CLIENT_RESTART_NOTIFY(){
	NOHUP_EXEC( LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.gluster.php --cluster-restart-notify");
}

function RoundCube_restart(){
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.initslapd.php --roundcube");
	$nohup=$unix->find_program("nohup");	
	shell_exec("$nohup /etc/init.d/roundcube restart >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/monit restart >/dev/null 2>&1 &");
	
} 
function ReloadArticaFilter(){
	$instance_id=intval($_GET["instance-id"]);
	$unix=new unix();
	$unix->framework_exec("exec.postfix.maincf.php --artica-filter --reload --instance-id=$instance_id");
}

function postfix_perso_settings(){
	$instance_id=intval($_GET["instance-id"]);
	$unix=new unix();
	$unix->framework_exec("exec.postfix.maincf.php --perso-settings --reload --instance-id=$instance_id");
}

function postfix_smtpd_restrictions(){
	$instance_id=intval($_GET["instance-id"]);
	$unix=new unix();
	// /usr/sbin/artica-phpfpm-service -smtpd-restrictions -instanceid {$GLOBALS["POSTFIX_INSTANCE_ID"]}
	$unix->framework_exec("exec.postfix.maincf.php --smtpd-restrictions --reload --instance-id=$instance_id");
}

function Reconfigure_nic(){
	// depreciated
}

function Reconfigure_routes(){
	//depreciated
}

function postfix_sync_artica(){
	NOHUP_EXEC( LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.smtp.export.users.php --sync");
	NOHUP_EXEC( LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.maincf.php --reconfigure");
}






function CLUSTER_DELETE(){
	$server=$_GET["cluster-delete"];
	@unlink("/etc/artica-cluster/clusters-$server");
	NOHUP_EXEC( LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.gluster.php --notify-all-clients");
	
}
function CLUSTER_ADD(){
	$server=$_GET["cluster-add"];
	@file_put_contents("/etc/artica-cluster/clusters-$server","#");
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.gluster.php --notify-all-clients");
	
}


function SmtpNotificationConfigRead(){
	$unix=new unix();
	$datas=trim(@file_get_contents("/etc/artica-postfix/smtpnotif.conf"));
	echo "<articadatascgi>$datas</articadatascgi>";
}

function safebox_mount(){
	if(is_file("{$GLOBALS["ARTICALOGDIR"]}/safebox.{$_GET["uid"]}.debug")){@unlink("{$GLOBALS["ARTICALOGDIR"]}/safebox.{$_GET["uid"]}.debug");}
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.safebox.php --init {$_GET["uid"]}");
}
function safebox_umount(){
	if(is_file("{$GLOBALS["ARTICALOGDIR"]}/safebox.{$_GET["uid"]}.debug")){@unlink("{$GLOBALS["ARTICALOGDIR"]}/safebox.{$_GET["uid"]}.debug");}
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.safebox.php --umount {$_GET["uid"]}");
}
function safebox_check(){
	if(is_file("{$GLOBALS["ARTICALOGDIR"]}/safebox.{$_GET["uid"]}.debug")){@unlink("{$GLOBALS["ARTICALOGDIR"]}/safebox.{$_GET["uid"]}.debug");}
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.safebox.php --fsck {$_GET["uid"]}");	
}

function safe_box_set_user(){
	if($_GET["uid"]==null){writelogs_framework("no user set",__FUNCTION__,__FILE__,__LINE__);}
	if(is_file("{$GLOBALS["ARTICALOGDIR"]}/safebox.{$_GET["uid"]}.debug")){@unlink("{$GLOBALS["ARTICALOGDIR"]}/safebox.{$_GET["uid"]}.debug");}
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.safebox.php --init {$_GET["uid"]}");
}
function safebox_logs(){
	$uid=$_GET["uid"];
	if(!is_file("{$GLOBALS["ARTICALOGDIR"]}/safebox.$uid.debug")){
	writelogs_framework("unable to stat {$GLOBALS["ARTICALOGDIR"]}/safebox.$uid.debug",__FUNCTION__,__FILE__,__LINE__);
	}
	$f=@file_get_contents("{$GLOBALS["ARTICALOGDIR"]}/safebox.$uid.debug");
	$datas=explode("\n",$f);
	writelogs_framework(count($datas)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($datas))."</articadatascgi>";
}


function CyrusBackupNow(){
	@chmod("/usr/share/artica-postfix/bin/artica-backup",0755);
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-backup --single-cyrus \"{$_GET["cyrus-backup-now"]}\"");
}




function RepairArticaLdapBranch(){
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-backup --repair-artica-branch");
}



function cyrus_mailboxdelete(){
	@chmod("/usr/share/artica-postfix/bin/artica-install",0755);
	exec("/usr/share/artica-postfix/bin/artica-install --mailbox-delete {$_GET["mailbox-delete"]}",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";		
}

function cyrus_check_cyraccounts(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=LOCATE_PHP5_BIN2();
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.cyrus-imapd.php --reload >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function cyrus_reconfigure(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=LOCATE_PHP5_BIN2();
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.cyrus-imapd.php --restart >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	}
function cyrus_paritition_default_path(){
	$unix=new unix();
	
	echo "<articadatascgi>". base64_encode($unix->IMAPD_GET("partition-default"))."</articadatascgi>";
	
}

function CyrusRepairMailBox(){
	$uid=$_GET["repair-mailbox"];
	$unix=new unix();
	$php5=LOCATE_PHP5_BIN2();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.cyrus-repair-mailbox.php $uid >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}


function InstallWebServices(){
	NOHUP_EXEC( LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.www.install.php");
}

function InstallWebServiceUnique(){
	
	$unix=new unix();
	$php5=LOCATE_PHP5_BIN2();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.www.install.php --single-install \"{$_GET["install-web-service-unique"]}\" >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}


function MailManSync(){
	NOHUP_EXEC( LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.mailman.php");
}

function RefreshStatus(){
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/launch.status.task","#");
}

function ForceRefreshLeft(){
	$unix=new unix();
	$php5=LOCATE_PHP5_BIN2();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.admin.status.postfix.flow.php --services >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}


function Global_Applications_Status(){
}
	
function artica_version(){
	$datas=@file_get_contents("/usr/share/artica-postfix/VERSION");
	if(trim($datas)==null){$datas="0.00";}
	echo "<articadatascgi>$datas</articadatascgi>";
	  
}
function daemons_status(){

      if(!is_file('/usr/share/artica-postfix/ressources/logs/global.status.ini')){ 
            sys_exec('/etc/init.d/artica-status reload');
         }

      if(is_file('/usr/share/artica-postfix/ressources/logs/global.status.ini')){ 
            $datas=@file_get_contents("/usr/share/artica-postfix/ressources/logs/global.status.ini");
            echo "<articadatascgi>$datas</articadatascgi>";
            return ;
        }       
}

function myhostname():bool{
	$unix=new unix();
	$datas=$unix->hostname_g();
	sys_events(basename(__FILE__)."::{$_SERVER['REMOTE_ADDR']}:: myhostname ($datas)");
	 echo "<articadatascgi>$datas</articadatascgi>";
	 return true;
}

function SmtpNotificationConfig(){
	@copy("/etc/artica-postfix/settings/Daemons/SmtpNotificationConfig","/etc/artica-postfix/smtpnotif.conf");
}
function SaveMaincf(){

	$php=LOCATE_PHP5_BIN2();
	shell_exec("/etc/init.d/artica-postfix start daemon &");
	EXEC_INTERNAL("$php /usr/share/artica-postfix/exec.postfix.maincf.php --reconfigure");	
}


function EXEC_INTERNAL($zcommands){
	if(!isset($GLOBALS["NOHUPBIN"])){
	 	$unix=new unix();
	 	$nohup=$unix->find_program("nohup");
	 	$GLOBALS["NOHUPBIN"]=$nohup;
	}else{
		$nohup=$GLOBALS["NOHUPBIN"];
	}
	 $cmd=trim("$nohup $zcommands >/dev/null 2>&1 &");
	 writelogs_framework("EXEC: \"$cmd\""); 	
	 shell_exec($cmd);
	
}


	


function SaveSMTPNotifications(){
	$filename=$_GET["SaveSMTPNotifications"];
	$source="/usr/share/artica-postfix/ressources/conf/$filename";
	$destfile="{$GLOBALS["ARTICALOGDIR"]}/squid_admin_notifs/$filename"	;
	@mkdir("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_notifs",0755,true);
	@copy($source, $destfile);
	@unlink($source);
	
}

function LaunchRemoteInstall(){
	$php=LOCATE_PHP5_BIN2();
	NOHUP_EXEC("$php /usr/share/artica-postfix/exec.remote-install.php");
}
function RestartWebServer(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.initslapd.php --webservices >/dev/null 2>&1 &");
	shell_exec($cmd);
	$cmd=trim("$nohup /etc/init.d/artica-webservices restart >/dev/null 2>&1 &");	
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function RestartArticaStatus(){
	$unix=new unix();
	
	$unix->THREAD_COMMAND_SET("/etc/init.d/artica-status reload");
	$unix->THREAD_COMMAND_SET("/etc/init.d/monit restart");
}
function RestartVnStat(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup /etc/init.d/artica-postfix restart vnstat >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function RestartApacheGroupwareForce(){
	shell_exec('/etc/init.d/artica-postfix restart apache-groupware');
}

function RestartApacheGroupwareNoForce(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup /etc/init.d/artica-postfix restart apache-groupware >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function RestartMailManService(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart mailman");	
}
function samba_restart(){
	NOHUP_EXEC("/etc/init.d/samba restart");	
}

function samba_restart_now(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup /etc/init.d/samba restart >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function samba_synchronize(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.samba.synchronize.php");
}

function samba_save_config(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	@file_put_contents("/var/log/samba/log.smbd", "\n");
	$cmd=trim("$nohup ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.samba.php --build >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}




function samba_original_config(){
	$datas=@file_get_contents("/etc/samba/smb.conf");
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";	
}




function samba_build_homes(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.samba.php --homes");
}
function samba_build_home_single(){
	$uid=base64_decode($_GET["home-single-user"]);
	if($uid==null){return;}
	@mkdir("/home/$uid",0755,true);
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.samba.php --home \"$uid\"");
}
function process_timeexec(){
	$unix=new unix();
	echo "<articadatascgi>". $unix->PROCESS_TTL($_GET["pid"])."</articadatascgi>";	
	
}


function samba_change_sid(){
	$unix=new unix();
	$sid=$_GET["samba-change-sid"];
	shell_exec($unix->LOCATE_NET_BIN_PATH()." setlocalsid $sid");
	if(!is_file("/etc/init.d/artica-process1")){return;}
	shell_exec("/etc/init.d/artica-process1 start");
}
function samba_password(){
	$password=base64_decode($_GET["smbpass"]);
	$file="/usr/share/artica-postfix/bin/install/smbldaptools/smbencrypt";
	$unix=new unix();
	$tmp=$unix->FILE_TEMP();
	$cmd="$file \"$password\" >$tmp 2>&1";
	shell_exec($cmd);
	$results=explode("\n",@file_get_contents($tmp));
	@unlink($tmp);
	writelogs_framework("SambaLoadpasswd ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	
	
	echo "<articadatascgi>". base64_encode(implode(" ",$results))."</articadatascgi>";
	
}

function samba_status(){
	
	if(is_file("/usr/share/artica-postfix/ressources/logs/web/samba.status")){
		$datas= @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/samba.status");
	if(strlen($datas)>50){
echo "<articadatascgi>$datas</articadatascgi>";	
return;

}
	}
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --samba --nowachdog",$results);
	$datas=implode("\n",$results);
	echo "<articadatascgi>$datas</articadatascgi>";	
	
}
function dropbox_status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --dropbox --nowachdog",$results);
	$datas=implode("\n",$results);
	echo "<articadatascgi>$datas</articadatascgi>";	
	}
	
function dropbox_service_status(){
	exec("/usr/share/artica-postfix/bin/install/dropbox/dropbox.py status",$results);
	$datas=trim(implode(" ",$results));
	echo "<articadatascgi>$datas</articadatascgi>";	
	}	
function dropbox_service_uri(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.dropbox.php --uri";
	exec("$cmd",$results);
	$datas=trim(implode(" ",$results));
	writelogs_framework("$cmd -> $datas",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>$datas</articadatascgi>";	
	}		
	
function dropbox_files_status(){
	exec("/usr/share/artica-postfix/bin/install/dropbox/DropBoxValues.py",$results);
	foreach ($results as $line){
		if(preg_match("#(.+?)\s+=\s+(.+)#",$line,$re)){
			$array[$re[1]]=$re[2];
		}
	}
	
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";	
}	


function samba_shares_list(){
	$ini=new iniFrameWork("/etc/samba/smb.conf");
	foreach ($ini->_params as $num=>$array){
		if(trim($array["path"])==null){continue;}
		if(!is_dir(trim($array["path"]))){continue;}
			$results[]=$array["path"];
	}
	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}



function samba_pdbedit(){
	$user=$_GET["pdbedit"];
	$unix=new unix();
	$cmd=$unix->find_program("pdbedit")." -Lv $user -s /etc/samba/smb.conf";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function samba_pdbedit_group(){
	$administrator_pwd=base64_decode($_GET["password"]);
	$group=$_GET["pdbedit-group"];
	
	
	$unix=new unix();
	$net=$unix->find_program("net");
	$cmd="net rpc group MEMBERS \"$group\" -U administrator%$administrator_pwd 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	$results=array();
	exec($cmd,$results);
	$AR["MEMBERS"]=$results;
	$cmd="net groupmap list -U administrator%$administrator_pwd 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	$results=array();
	exec($cmd,$results);
	foreach ($results as $line){
		if(strpos(" $line","$group")>0){$AR["MAP"][]=$line;}
	}
	echo "<articadatascgi>". base64_encode(serialize($AR))."</articadatascgi>";
}

function samba_pdbedit_debug(){
	$user=$_GET["Debugpdbedit"];
	$unix=new unix();
	$cmd=$unix->find_program("pdbedit")." -Lv -d 10 $user -s /etc/samba/smb.conf";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}



function arp_and_ip(){
	$computer_name=$_GET["arp-ip"];
	if($computer_name==null){return;}
	$unix=new unix();
	$ip=$unix->HostToIp($computer_name);
	writelogs_framework("gethostbyname -> $computer_name = $ip",__FUNCTION__,__FILE__,__LINE__);
	if($ip==$computer_name){return null;}
	if($ip==null){return null;}
	$arp=$unix->IpToMac($ip);
	echo "<articadatascgi>".  base64_encode(serialize(array($ip,$arp)))."</articadatascgi>";	
	}
	
function ip_to_mac(){
	$ip=$_GET["ip-to-mac"];
	$unix=new unix();
	$arp=$unix->IpToMac($ip);
	echo "<articadatascgi>$arp</articadatascgi>";	
	
}


function RestartGroupwareWebServer(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart apache");
	NOHUP_EXEC("/etc/init.d/artica-postfix restart apache-groupware");
}

function ReloadApacheGroupWare(){
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --reload-apache-groupware");
}


function RestartASSPService(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart assp");	
}
function ReloadASSPService(){
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --reload-assp");	
}
function rewrite_php(){
	
	$cmd=trim(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.initslapd.php --fetchmail --force");
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --php-include");	
	
}

function RestartDHCPDService(){
	NOHUP_EXEC("/etc/init.d/isc-dhcp-server restart");		
}


function RestartMailGraphService(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart mailgraph");		
}
function RestartDaemon(){
	$unix=new unix();
	$unix->THREAD_COMMAND_SET("/etc/init.d/artica-status restart");
	$unix->THREAD_COMMAND_SET("/etc/init.d/postfix-logger restart");
	$unix->THREAD_COMMAND_SET("/etc/init.d/artica-syslog restart");
	$unix->THREAD_COMMAND_SET("/etc/init.d/artica-postfix restart artica-exec");
	$unix->THREAD_COMMAND_SET("/etc/init.d/artica-postfix restart artica-back");

	
}
function RestartFetchmail(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup /etc/init.d/fetchmail restart >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
}

function SQUIDGUARD_RELOAD(){
	$unix=new unix();
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.squidguard.php --build --reload --force");
	NOHUP_EXEC("/etc/init.d/artica-status reload");
	NOHUP_EXEC("/etc/init.d/monit restart");
	if(isset($_GET["restart"])){
		$unix->THREAD_COMMAND_SET("/etc/init.d/ufdb restart");
	}
}
function SQUIDGUARD_WEB_RELOAD(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	if(is_file("/etc/init.d/ufdb-http")){
		$cmd=trim("$nohup /etc/init.d/ufdb-http restart >/dev/null 2>&1 &");
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
		shell_exec($cmd);
	}
	

	
}



function RestartSquid(){
	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.squid.php --build");return;}
	NOHUP_EXEC("/etc/init.d/artica-postfix restart squid");
}
function RestartArticaPolicy(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart artica-policy");
}
function ReloadArticaPolicy(){
	NOHUP_EXEC("/usr/share/bin/artica-install --reload-artica-policy");
}


function RestartMysqlDaemon(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.mysql.build.php \"".$unix->MYSQL_MYCNF_PATH()."\" >/dev/null 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
	$nohup=$unix->find_program("nohup");
	squid_admin_mysql(1,"Restarting MySQL service...", null,__FILE__,__LINE__);
	$cmd=trim("$nohup /etc/init.d/mysql restart --force --framework=".__FILE__." >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
	
}

function RestartOpenVPNServer(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart openvpn");
}

function RestartstrongSwanServer(){
	NOHUP_EXEC("/etc/init.d/ipsec restart");
}

function RestartCyrusImapDaemon(){
	NOHUP_EXEC("/etc/init.d/cyrus-imapd restart");
}

function rRestartCyrusImapDaemonDebug(){
	exec("/etc/init.d/cyrus-imapd restart --verbose",$results);
	$a=serialize($results);
	echo "<articadatascgi>". base64_encode($a)."</articadatascgi>";		
}

function ReconfigureCyrusImapDaemon(){
	if(isset($_GET["force"])){$force=" --force";}
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus$force");
	NOHUP_EXEC("/etc/init.d/postfix restart-single");
}
function ReconfigureCyrusImapDaemonDebug(){
	exec("/usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus --force --verbose",$results);
	$a=serialize($results);
	echo "<articadatascgi>". base64_encode($a)."</articadatascgi>";		
}

function reload_dansguardian(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.squidguard.php --build");
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --reload-dansguardian");
	}
	
function reload_ufdbguard(){
	$unix=new unix();
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.squidguard.php --build");
}

function restart_ufdbguard(){
	NOHUP_EXEC("/etc/init.d/ufdb restart");
}
	
function delete_mailbox(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.cyrus.php --delete-mailbox {$_GET["DelMbx"]}");
}

function umount_disk(){
	$mount=$_GET["umount-disk"];
	$unix=new unix();
	writelogs_framework("umount $mount",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($unix->find_program("umount")." -l \"$mount\"");
}

function fdisk_list(){
	$unix=new unix();
	$array=array();
	exec($unix->find_program("fdisk")." -l",$results);
	if(!is_array($results)){return null;}

	foreach ($results as $path){
		if(preg_match("#Disk\s+(.+?):\s+([0-9\.]+)\s+([A-Za-z]+),#",$path,$re)){
			$array[trim($re[1])]=trim($re[2]." ".$re[3]);
		}
	}
	writelogs_framework(count($array)." disks found",__FUNCTION__,__FILE__,__LINE__);
	$a=serialize($array);
	echo "<articadatascgi>". base64_encode($a)."</articadatascgi>";	
}

function lvmdiskscan(){
	$unix=new unix();
	exec($unix->find_program("lvmdiskscan")." -l",$results);
	if(!is_array($results)){return null;}	
	///dev/sda2                [      148.95 GB] LVM physical volume
	foreach ($results as $num=>$path){
		if(preg_match("#(.+?)\s+\[(.+?)\]\s+#",$path,$re)){
			$array[trim($re[1])]=trim($re[2]);
		}else{
			
		}
	}
writelogs_framework(count($array)." disks found",__FUNCTION__,__FILE__,__LINE__);
	$a=serialize($array);
	echo "<articadatascgi>". base64_encode($a)."</articadatascgi>";			
}

function pvscan(){
$unix=new unix();
	exec($unix->find_program("pvscan")." -u",$results);	
if(!is_array($results)){return null;}
foreach ($results as $path){
		if(preg_match("#PV\s+(.+?)\s+with\s+UUID\s+(.+?)\s+VG\s+(.+?)\s+lvm[0-9]\s+\[([0-9,\.]+)\s+([A-Z]+).+?([0-9,\.]+)\s+([A-Z]+)#",$path,$re)){
			$array[trim($re[1])]=array("VG"=>trim($re[3]),"SIZE"=>trim($re[4])." ".trim($re[5]),"UUID"=>trim($re[2]),"FREE"=>trim($re[6])." ".trim($re[7]));
		}
	}
writelogs_framework(count($array)." disks found",__FUNCTION__,__FILE__,__LINE__);
	$a=serialize($array);
	echo "<articadatascgi>". base64_encode($a)."</articadatascgi>";			
	
	
}

function LVM_VG_DISKS(){
	$unix=new unix();
	exec($unix->find_program("pvdisplay")." -c",$results);	
	if(!is_array($results)){return null;}
	foreach ($results as $line){
		$tb=explode(":",$line);
		
		$size=round(($tb[2]/2048)/1000);
		$array[$tb[1]][]=array($tb[0],$size);
		
	}
	
	$a=serialize($array);
	echo "<articadatascgi>". base64_encode($a)."</articadatascgi>";		
}


function LVM_LV_DISPLAY(){
	$dev=trim($_GET["lvdisplay"]);
	$unix=new unix();
	$vgdisplay=$unix->find_program("lvdisplay");
	$cmd="$vgdisplay -v -m $dev 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec("$cmd",$results);
	echo "<articadatascgi>". @implode("\n",$results)."</articadatascgi>";
}

function LVM_UNLINK_DISK(){
	$groupname=$_GET["groupname"];
	$dev=$_GET["dev"];
	$unix=new unix();
	$cmd=$unix->find_program("vgreduce")." $groupname $dev";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	  
	
}
function LVM_LINK_DISK(){
	$groupname=$_GET["groupname"];
	$dev=$_GET["dev"];
	$unix=new unix();
	$tmpstr=$unix->FILE_TEMP();
	$cmd=$unix->find_program("vgextend")." $groupname $dev";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$cmd >$tmpstr 2>&1");
	$results=explode("\n",@file_get_contents($tmpstr));
	$results[]="$cmd";
	$results[]="$dev -> $groupname";	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	  
	
}

function LVM_CREATE_GROUP(){
	$groupname=$_GET["groupname"];
	$dev=$_GET["dev"];
	exec("/usr/share/artica-postfix/bin/artica-install --vgcreate-dev $dev $groupname",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}



function ChangeMysqlLocalRoot(){
	
	$q=new unix();
	$nohup=$q->find_program("nohup");
	$password=$_GET["password"];
	if(isset($_GET["encoded"])){$password=base64_decode($password);}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/ChangeMysqlLocalRoot","{scheduled}\nPlease wait few times...");
	@chmod("/usr/share/artica-postfix/ressources/logs/ChangeMysqlLocalRoot",0755);
	$q=new unix();
	$_GET["password"]=$q->shellEscapeChars($_GET["password"]);
	$cmd="$nohup /usr/share/artica-postfix/bin/artica-install --change-mysqlroot --inline \"{$_GET["ChangeMysqlLocalRoot"]}\" \"$password\" --verbose >>/usr/share/artica-postfix/ressources/logs/ChangeMysqlLocalRoot 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec(trim($cmd));	
	
}

function ChangeMysqlLocalRoot2(){
	$q=new unix();
	$nohup=$q->find_program("nohup");
	if($_GET["password"]==null){echo $results[]="No Password set";}
	if($_GET["username"]==null){echo $results[]="No Username set";}
	$_GET["password"]=$q->shellEscapeChars(base64_decode($_GET["password"]));
	$_GET["username"]=$q->shellEscapeChars(base64_decode($_GET["username"]));
	$tplfile=PROGRESS_DIR."/ChangeMysqlLocalRoot2.log";
	@file_put_contents($tplfile, "{waiting}....");
	@chmod($tplfile,777);
	$cmd="$nohup /usr/share/artica-postfix/bin/artica-install --change-mysqlroot --inline \"{$_GET["username"]}\" \"{$_GET["password"]}\" >$tplfile 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec(trim($cmd));
	
	
}

function ChangeMysqlDir(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	if(is_file($nohup)){$nohup="$nohup ";}
	shell_exec("$nohup/usr/share/artica-postfix/bin/artica-install --change-mysqldir >/dev/null 2>&1 &");
}

function ChangeSSLCertificate(){
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --change-certificate");
	}


function viewlogs(){
	$file=$_GET["viewlogs"];
	$datas=@shell_exec("tail -n 100 {$GLOBALS["ARTICALOGDIR"]}/$file");
	echo "<articadatascgi>$datas</articadatascgi>";
}
function LdapdbStat(){
	$unix=new unix();
	$dbstat=$unix->LOCATE_DB_STAT();
	$ldap_datas=$unix->PATH_LDAP_DIRECTORY_DATA();
	error_log($ldap_datas);
	$head=$unix->LOCATE_HEAD();
	if($dbstat==null){return null;}
	$cmd="$dbstat -h $ldap_datas -m | $head -n 11";
	
	error_log($cmd);
	$results=shell_exec($cmd);
	echo "<articadatascgi>$results</articadatascgi>";
} 
function LdapdbSize(){
	$unix=new unix();
	$du=$unix->LOCATE_DU();
	$ldap_datas=$unix->PATH_LDAP_DIRECTORY_DATA();
	if($du==null){return null;}
	$results=trim(shell_exec("$du -h $ldap_datas"));
	echo "<articadatascgi>$results</articadatascgi>";
}

function du_dir_size(){
	
	if(!function_exists("system_is_overloaded")){
		include_once("/usr/share/artica-postfix/ressources/class.os.system.inc");
	}
	if(function_exists("system_is_overloaded")){
		if(system_is_overloaded()){
			echo "<articadatascgi>0</articadatascgi>";
			return 0;}
	}
	
	
	$path=$_GET["path"];
	$unix= new unix();
	$du=$unix->find_program("du");
	if(!is_dir($path)){echo "<articadatascgi>0</articadatascgi>";return;}
	exec("$du -m -s $path",$results);
	if(preg_match("#^([0-9]+)#",@implode("",$results),$re)){echo "<articadatascgi>{$re[1]}</articadatascgi>";return;}
	
}

function ldap_restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$init=$unix->SLAPD_INITD_PATH();
	$stamp="/etc/artica-postfix/socket.ldap.start";
	if($unix->file_time_min($stamp)<3){
		writelogs_framework("Stamp, says to wait 3mn before",__FUNCTION__,__FILE__,__LINE__);
		return;}
	@unlink($stamp);
	shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.initslapd.php");
	$cmd="$nohup $init start >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents($stamp, time());
	shell_exec($cmd);
	}


function cpualarm(){
$cpu=shell_exec("/usr/share/artica-postfix/bin/cpu-alarm.pl");	
echo "<articadatascgi>$cpu</articadatascgi>";
}


function TaskLastManager(){
	$datas=shell_exec("/bin/ps -w axo pid,pcpu,pmem,time,args --sort -pcpu,-pmem|/usr/bin/head --lines=30");	
	echo "<articadatascgi>$datas</articadatascgi>";
}
function TaskLastManagerTime(){
	$unix=new unix();
	$time=$unix->PROCESS_TTL_TEXT($_GET["TaskLastManagerTime"]);
	//writelogs_framework("{$_GET["TaskLastManagerTime"]} = $time",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>$time</articadatascgi>";
}

function postfixQueues(){
	$p=new postfix_system();
	$datas=serialize($p->getQueuesNumber());
	echo "<articadatascgi>".serialize($p->getQueuesNumber())."</articadatascgi>";
}

function postfix_read_main(){
	echo "<articadatascgi>".@file_get_contents("/etc/postfix/main.cf")."</articadatascgi>";
}
function postfix_reconfigure(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.maincf.php --reconfigure");
}
function postfix_restart_single(){
	NOHUP_EXEC("/etc/init.d/postfix restart-single");
}
function postfix_restart_single_now(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	if(is_file($nohup)){$nohup="$nohup ";}
	shell_exec("$nohup /etc/init.d/postfix restart-single >/dev/null 2>&1 &");
}

function postfix_restricted_users(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.maincf.php --restricted");
}

function postfix_tail(){
	if(isset($_GET["filter"])){$filter=" \"{$_GET["filter"]}\"";}
	exec("/usr/share/artica-postfix/bin/artica-install --mail-tail$filter",$results);
	//writelogs_framework(count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}

function postfix_multi_configure(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix-multi.php --org {$_GET["postfix-multi-configure-ou"]}");
}
function postfix_multi_disable(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix-multi.php --removes");
}
function zabbix_restart():bool{
	NOHUP_EXEC("/etc/init.d/artica-postfix restart zabbix");
	return true;
}

function postfix_multi_stat(){
	$instance=$_GET["postfix-mutli-stat"];
	$unix=new unix();
	$queue_directory=$unix->POSTCONF_MULTI_GET($instance,"queue_directory");
	$pidpath="$queue_directory/pid/master.pid";
	$pid=trim(@file_get_contents($pidpath));
	$path="/proc/$pid/exe";
	writelogs_framework("POSTFIX_MULTI_PID::queudir:$queue_directory [$instance] proc:$path  pid:($pidpath) $pid",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	
	
	$version=$unix->POSTFIX_VERSION();
	if($version==null){
		$array[0]=-2;
		$array[1]=$version;
		$array[2]=$pid;
		$array[3]=$path;
		echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
		return ;
	}
	
	
	if(is_file($path)){
		
		$array[0]=1;
		$array[1]=$version;
		$array[2]=$pid;
		$array[3]=$path;
	}else{
		$pid=null;
		$array[0]=0;
		$array[1]=$version;
		$array[2]=null;
		$array[3]=$path;
	}
echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";	
}



function postfix_stat(){
	$unix=new unix();
	$pid=$unix->POSTFIX_PID();
	$path="/proc/$pid/exe";
	writelogs_framework("POSTFIX_PID->$pid",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	
	
	$version=$unix->POSTFIX_VERSION();
	if($version==null){
		$array[0]=-2;
		$array[1]=$version;
		$array[2]=$pid;
		$array[3]=$path;
		echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
		return ;
	}
	
	
	if(is_file($path)){
		$array[0]=1;
		$array[1]=$version;
		$array[2]=$pid;
		$array[3]=$path;
	}else{
		$pid=null;
		$array[0]=0;
		$array[1]=$version;
		$array[2]=null;
		$array[3]=$path;
	}
echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";

	
}

function ChangeLDPSSET(){
	$unix=new unix();
	$password=base64_decode($_GET["password"]);
	$password=$unix->shellEscapeChars($password);
	
	$vals=shell_exec("/usr/share/artica-postfix/bin/artica-install --change-ldap-settings {$_GET["ldap_server"]} {$_GET["ldap_port"]} {$_GET["suffix"]} {$_GET["username"]} $password {$_GET["change_ldap_server_settings"]}");
	echo "<articadatascgi>$vals</articadatascgi>";
}
function ASSPOriginalConf(){
	echo "<articadatascgi>".@file_get_contents("/usr/share/assp/assp.cfg")."</articadatascgi>";
}
function SetupCenter(){
NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.admin.status.postfix.flow.php --setup");
NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --write-versions");	
}
function BuildVhosts(){
NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.www.install.php");
NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.www.webdav.php --users");		
}


function MySqlPerf(){
	$unix=new unix();
	$tmpdir=$unix->TEMP_DIR();
	$cmd="mysql -p{$_GET["pass"]} -u {$_GET["username"]} -T -P {$_GET["port"]} -h {$_GET["host"]} -e \"SELECT benchmark(100000000,1+2);\" -vvv >$tmpdir/mysqlperfs.txt 2>&1";
	shell_exec($cmd);
	
	
	$tbl=explode("\n",@file_get_contents("$tmpdir/mysqlperfs.txt"));
	foreach ($tbl as $num=>$ligne){
		if(preg_match('#row in set\s+\(([0-9\.]+)#',$ligne,$re)){
			$time=trim($re[1]);
		}
	}
	
	echo "<articadatascgi>$time</articadatascgi>";
	@unlink("$tmpdir/mysqlperfs.txt");
	
	
}

function MysqlAudit(){
	$cmd="/usr/share/artica-postfix/bin/mysqltuner.pl --skipsize --noinfo --nogood --nocolor --pass {$_GET["pass"]} --user {$_GET["username"]} --port {$_GET["port"]} --host {$_GET["host"]} --forcemem {$_GET["server_memory"]} --forceswap {$_GET["server_swap"]} 2>&1";
	exec($cmd,$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#Performance Metrics#",$ligne)){$start=true;}
		if(!$start){continue;}
		$f[]=$ligne;
		
	}
	
	
	echo "<articadatascgi>". implode("\n",$f)."</articadatascgi>";
}
function RestartApacheNow(){
	error_log("[{$_SESSION["uid"]}]::restarting apache");
	NOHUP_EXEC("/etc/init.d/artica-postfix restart apache &");
	error_log("[{$_SESSION["uid"]}]::restarting apache done");
}
function reloadSpamAssassin(){
NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --reload-spamassassin");
}


function dirdir(){
	$path=$_GET["dirdir"];
	$unix=new unix();
	$array=$unix->dirdir($path);
	writelogs_framework("$path=".count($array)." directories",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". serialize($array)."</articadatascgi>";
}
function dirdir_Encoded(){
	$path=$_GET["dirdirEncoded"];
	$unix=new unix();
	$array=$unix->dirdir($path);
	writelogs_framework("$path=".count($array)." directories",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}

function directory_delete_user(){
	$path=base64_decode($_GET["delete-user-folder"]);
	$uid=base64_decode($_GET["uid"]);
	if($uid==null){return;}
	if($path==null){return;}
	if($path=="/"){return;}	
	$dir_uid=posix_getpwuid(fileowner($path));
	$dir_uid_name=$dir_uid["name"];
	writelogs_framework("Delete folder '$path' for $uid against $dir_uid_name",__FUNCTION__,__FILE__,__LINE__);
	if($dir_uid_name<>$uid){
		echo "<articadatascgi>{ERROR_NO_PRIVS}</articadatascgi>;";
		return;
		
	}
	if(is_dir($path)){
		$path=shellEscapeChars($path);
		writelogs_framework("Delete folder '$path' finally",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("/bin/rm -rf $path");
	}
	
	//@mkdir($path,0666,true);
	//shell_exec("/bin/chown $uid $path");
}

function shellEscapeChars($path){
		$unix=new unix();
		return $unix->shellEscapeChars($path);	
}

function DefaultDirMask(){
	$SharedFoldersDefaultMask=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SharedFoldersDefaultMask"));
	if(!is_numeric($SharedFoldersDefaultMask)){$SharedFoldersDefaultMask="0755";}
	return $SharedFoldersDefaultMask;
}


function directory_create_user(){
	$SharedFoldersDefaultMask=DefaultDirMask();
	$path=base64_decode($_GET["create-user-folder"]);
	$uid=base64_decode($_GET["uid"]);
	if($uid==null){return;}
	if($path==null){return;}
	if($path=="/"){return;}
	writelogs_framework("Create new folder '$path' for $uid",__FUNCTION__,__FILE__,__LINE__);
	@mkdir($path,$SharedFoldersDefaultMask,true);
	shell_exec("/bin/chown $uid $path");
	@chmod($path,$SharedFoldersDefaultMask);
}

function dirdirEncoded(){
	$path=$_GET["dirdir-encoded"];
	$unix=new unix();
	$array=$unix->dirdir($path);
	writelogs_framework("$path=".count($array)." directories",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". serialize($array)."</articadatascgi>";
}

function Dir_Files(){
	$queryregex=null;
	if(isset($_GET["queryregex"])){
		if($_GET["queryregex"]<>null){
			$queryregex=base64_decode($_GET["queryregex"]);
		}
	}
	$path=base64_decode($_GET["Dir-Files"]);
	$path=utf8_encode($path);
	writelogs_framework("$path ($queryregex)",__FUNCTION__,__FILE__,__LINE__);
	$unix=new unix();
	$array=$unix->DirFiles($path,$queryregex);
	writelogs_framework("$path=".count($array)." files",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}

function Dir_Directories(){
	$path=base64_decode($_GET["Dir-Directories"]);
	writelogs_framework("$path",__FUNCTION__,__FILE__,__LINE__);
	$unix=new unix();
	$array=$unix->dirdir($path);
	writelogs_framework("$path=".count($array)." files",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}

function filestat(){
	$path=base64_decode($_GET["filestat"]);
	$path=utf8_encode($path);
	if(isset($_GET["filename"])){
		$filename=base64_decode($_GET["filename"]);
		$path=$path."/$filename";
	}
	
	$unix=new unix();
	$array=$unix->alt_stat($path);
	if(!is_array($array)){writelogs_framework("ERROR stat -> $path",__FUNCTION__,__FILE__,__LINE__);}
	
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";	
}

function folder_create(){
$path=utf8_decode(base64_decode($_GET["create-folder"]));
$perms=base64_decode($_GET["perms"]);
$unix=new unix();
writelogs_framework("path=$path (".base64_decode($_GET["perms"]).")",__FUNCTION__,__FILE__,__LINE__);
if(is_dir($path)){
	echo "<articadatascgi>". base64_encode($path." -> {already_exists}")."</articadatascgi>";
	exit;
	
}


	if(!mkdir(utf8_encode($path),0666,true)){
		writelogs_framework("Fatal ERROR while creating folder $path (".base64_decode($_GET["perms"]).")",__FUNCTION__,__FILE__,__LINE__);
		echo "<articadatascgi>". base64_encode($path." -> {failed}")."</articadatascgi>";
		exit;
	}
	
	if($perms<>null){
		$cmd=$unix->find_program("chown")." ".base64_decode($_GET["perms"])." \"$path\"";
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
		}
	
}

function folder_delete(){
$path=base64_decode($_GET["folder-remove"]);
$path=utf8_encode($path);
$unix=new unix();
if($_GET["emergency"]<>"yes"){
	if($unix->IsProtectedDirectory($path)){
		echo "<articadatascgi>". base64_encode($path." -> {failed} {protected}")."</articadatascgi>";
		exit;
	}
}
writelogs_framework("path=$path",__FUNCTION__,__FILE__,__LINE__);
if(!is_dir($path)){
	writelogs_framework("$path no such directory",__FUNCTION__,__FILE__,__LINE__);
	return;
}
$cmd=$unix->find_program("rm")." -rf \"$path\"";
writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
shell_exec($cmd);
}



function dirdirBase64(){
	$path=base64_decode($_GET["B64-dirdir"]);
	$unix=new unix();
	$path=utf8_encode($path);
	if(!is_dir($path)){
		writelogs_framework("path `$path` no such directory",__FUNCTION__,__FILE__,__LINE__);
		echo "<articadatascgi>". base64_encode(serialize(array()))."</articadatascgi>";
		return;
	}
	
	$array=$unix->dirdir($path);
	writelogs_framework("path=$path (".count($array)." elements)",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}

function ReplicatePerformancesConfig(){
	copy("/etc/artica-postfix/settings/Daemons/ArticaPerformancesSettings","/etc/artica-postfix/performances.conf");
}

function SetupIndexFile(){
 
 	$unix=new unix();
 	$tmpf=$unix->FILE_TEMP();
 	if(is_file("/usr/share/artica-postfix/ressources/index.ini")){@unlink("/usr/share/artica-postfix/ressources/index.ini");}
 	shell_exec("/usr/share/artica-postfix/bin/artica-update --index --verbose >$tmpf 2>&1");
    $datas=@file_get_contents($tmpf);
    @unlink($tmpf);  
    
    $cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.admin.status.postfix.flow.php --setup-center";
    error_log("[{$_SESSION["uid"]}]::framework:: $cmd");
	shell_exec(LOCATE_PHP5_BIN2().' /usr/share/artica-postfix/exec.admin.status.postfix.flow.php --setup-center');	
	echo "<articadatascgi>$datas</articadatascgi>";
	
	
}


function ComputerRemoteRessources(){
	$unix=new unix();
	$tmpstr=$unix->FILE_TEMP();
	$cmd="/usr/share/artica-postfix/bin/artica-install --remote-ressources \"{$_GET["ComputerRemoteRessources"]}\" \"{$_GET["username"]}\" \"{$_GET["password"]}\" >$tmpstr 2>&1";
	error_log("[{$_SESSION["uid"]}]::framework:: $cmd");
	shell_exec($cmd);
	echo "<articadatascgi>".@file_get_contents($tmpstr)."</articadatascgi>";
	@unlink($tmpstr);	
}

function DumpPostfixQueue(){
	$queue=$_GET["DumpPostfixQueue"];
	error_log("[{$_SESSION["uid"]}]::framework:: DumpPostfixQueue() -> $queue");
	$postfix=new postfix_system();
	echo "<articadatascgi>".$postfix->READ_QUEUE($queue)."</articadatascgi>";
	
	
}
function idofUser(){
	$unix=new unix();
	exec($unix->find_program('id')." {$_GET["idofUser"]}",$return);
	if(preg_match("#uid=([0-9]+)\({$_GET["idofUser"]}\)#",$return[0],$re)){
		echo "<articadatascgi>{$re[1]}</articadatascgi>";
	}
}

function MailManList(){
	$cmd="/usr/lib/mailman/bin/list_lists -a";
	exec($cmd,$array);
	foreach ($array as $num=>$ligne){
		
		if(preg_match("#([a-zA-Z0-9-_\.]+)\s+-\s+\[#",$ligne,$re)){
			$rr[]=strtolower($re[1]);
		}
		
	}	
	
 echo "<articadatascgi>". serialize($rr)."</articadatascgi>";	
	
}
function MailManDelete(){
	$list=$_GET["mailman-delete"];
	shell_exec("/bin/touch /var/lib/mailman/data/aliases");
	exec("/usr/lib/mailman/bin/rmlist -a $list",$re);
	if(is_array($re)){
		echo "<articadatascgi>". serialize($re)."</articadatascgi>";
	}
}

function philesizeIMG(){
	$t=time();
	$unix=new unix();
	$tmpf=$unix->FILE_TEMP();
	$path=$_GET["philesize-img"];
	$img=md5($path);
	$path=str_replace("//","/",$path);
	if(substr($path,strlen($path)-1,1)=='/'){$path=substr($path,0,strlen($path)-1);}
	if($path==null){$path="/";}
	chdir("/usr/share/artica-postfix/bin");
	$cmd="./philesight --db /opt/artica/philesight/database.db --path $path --draw /usr/share/artica-postfix/ressources/logs/$img.png";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec("$cmd 2>&1",$res);
	echo "<articadatascgi><img src='ressources/logs/$img.png?$t'></articadatascgi>";
	writelogs_framework("ressources/logs/$img.png=>\"$res\" (". @filesize("/usr/share/artica-postfix/ressources/logs/$img.png")." bytes)",__FUNCTION__,__FILE__,__LINE__);
	
	
}
function philesizeIMGPath(){
	
	$unix=new unix();
	$tmpf=$unix->FILE_TEMP();
	$path=$_GET["philesize-img-path"];
	$img=md5($path);
	$path=str_replace("//","/",$path);
	if(substr($path,strlen($path)-1,1)=='/'){$path=substr($path,0,strlen($path)-1);}
	if($path==null){$path="/";}
	chdir("/usr/share/artica-postfix/bin");
	$cmd="/usr/share/artica-postfix/bin/philesight --db /opt/artica/philesight/database.db --path $path --draw /usr/share/artica-postfix/ressources/logs/$img.png";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	@mkdir("/usr/share/artica-postfix/user-backup/ressources/logs");
	@copy("/usr/share/artica-postfix/ressources/logs/$img.png","/usr/share/artica-postfix/user-backup/ressources/logs/$img.png");
	@chmod("/usr/share/artica-postfix/user-backup/ressources/logs/$img.png",0755);
	shell_exec("/usr/share/artica-postfix/bin/philesight --db /opt/artica/philesight/database.db --path $path --draw /usr/share/artica-postfix/ressources/logs/$img.png >$tmpf 2>&1");
	echo "<articadatascgi>ressources/logs/$img.png</articadatascgi>";
	$res=@file_get_contents($tmpf);
	@unlink($tmpf);
	writelogs_framework("ressources/logs/$img.png=>\"$res\" (". @filesize("/usr/share/artica-postfix/ressources/logs/$img.png")." bytes)",__FUNCTION__,__FILE__,__LINE__);
	
	
}

function kaspersky_status(){
	exec("/usr/share/artica-postfix/bin/artica-install --kaspersky-status",$results);
	$text=trim(implode("\n",$results));
	echo "<articadatascgi>". base64_encode($text)."</articadatascgi>";
	
}



function squid_originalconf(){
	echo "<articadatascgi>". base64_encode(@file_get_contents("/etc/squid3/squid.conf"))."</articadatascgi>";
}



function  philesight_perform(){

}

function disks_list(){
	$unix=new unix();
	$array=$unix->DISK_LIST();
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}
function disks_inodes(){
	$unix=new unix();
	$array=$unix->DISK_INODES();
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}


function disks_change_label(){
	$dev=$_GET["disk-change-label"];
	$name=$_GET["name"];
	exec("/usr/share/artica-postfix/bin/artica-install --disk-change-label $dev $name --verbose",$array);
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}

function disks_get_label(){
	$dev=$_GET["disk-get-label"];
	$unix=new unix();
	$label=$unix->DiskLabel($dev);
	echo "<articadatascgi>". base64_encode($label)."</articadatascgi>";
	
}

function disk_get_mounted_point(){
	$dev=base64_decode($_GET["get-mounted-path"]);
	$unix=new unix();
	echo "<articadatascgi>". base64_encode($unix->MOUNTED_PATH($dev))."</articadatascgi>";
}




function lvs_scan(){
	$results=array();
	$VolumeGroupName=$_GET["lvm-lvs"];
	$unix=new unix();
	exec($unix->find_program("lvs")." --noheadings --aligned --separator \";\" --units g $VolumeGroupName",$returns);
	foreach ($returns as $ligne){
		if(!preg_match("#(.+?);(.+?);(.+?);(.+?)G#",$ligne,$re)){continue;}
		$array[trim($re[1])]=str_replace(",",".",trim($re[4]));
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	}

function sfdisk_dump(){
	$dev=$_GET["sfdisk-dump"];
	$unix=new unix();
	exec($unix->find_program("sfdisk")." -d $dev",$returns);	
	echo "<articadatascgi>". base64_encode(serialize($returns))."</articadatascgi>";
	}
function mkfs(){
	$dev=$_GET["mkfs"];
	if($dev==null){return null;}
	$unix=new unix();
	$ext=$unix->BetterFS();
	exec($unix->find_program("mkfs")." -T $ext $dev",$returns);	
	echo "<articadatascgi>". base64_encode(serialize($returns))."</articadatascgi>";	
}

function parted_print(){
	$dev=$_GET["parted-print"];
	$unix=new unix();
	if($dev==null){return;}
	exec($unix->find_program("parted")." $dev -s unit GB print",$returns);
	echo "<articadatascgi>". base64_encode(serialize($returns))."</articadatascgi>";		
}

function LVM_LVS_DEV_MAPPER(){
	$dev=$_GET["lvs-mapper"];
	$mapper=@readlink($dev);
	$mapper=str_replace("../mapper","/dev/mapper",$mapper);
	echo "<articadatascgi>$mapper</articadatascgi>";
}
function LVM_VGS_INFO(){
	$vg=$_GET["vgs-info"];
	$unix=new unix();
	exec($unix->find_program("vgs")." $vg",$returns);
	$pattern="$vg\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)(.+?)\s+([0-9,\.A-Z]+)\s+([0-9,\.A-Z]+)";
	writelogs_framework("$vg:: PATTERN=\"$pattern\"",__FUNCTION__,__FILE__,__LINE__);
	foreach ($returns as $ligne){
		if(preg_match("#$pattern#",$ligne,$re)){
			$array[$vg]=array("SIZE"=>$re[5],"FREE"=>$re[6]);
			echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
			break;
		}else{
			writelogs_framework("$vg:: FAILED=\"$ligne\"",__FUNCTION__,__FILE__,__LINE__);
		}
	}	
	
}


function LVM_lVS_INFO_ALL(){
$unix=new unix();
	exec($unix->find_program("lvs"),$returns);
	$pattern="(.+?)\s+(.+?)\s+(.+?)\s+([0-9,\.A-Z]+)";
	writelogs_framework("PATTERN=\"$pattern\"",__FUNCTION__,__FILE__,__LINE__);
	
	foreach ($returns as $ligne){
		if(preg_match("#$pattern#",trim($ligne),$re)){
			$array[trim($re[1])]=array("SIZE"=>$re[4],"GROUPE"=>$re[2]);
		}else{
			writelogs_framework("FAILED=\"$ligne\"",__FUNCTION__,__FILE__,__LINE__);
		}
	}

	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}

function LVM_LV_ADDSIZE(){
	$mapper=$_GET["lv-resize-add"];
	$size=$_GET["size"];
	$unit=$_GET["unit"];
	$results=array();
	$unix=new unix();
	
	$cmd0=$unix->find_program("lvextend")." -L $size$unit $mapper";
	$cmd1=$unix->find_program("umount")." -f $mapper";
	$cmd2=$unix->find_program("resize2fs")." -f $mapper";
	$cmd3=$unix->find_program("mount")." $mapper";
	
	writelogs_framework("$cmd0",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd0,$results0);
	
	writelogs_framework("$cmd1",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd1,$results1);
	
	writelogs_framework("$cmd2",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd2,$results2);

	writelogs_framework("$cmd3",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd3,$results3);	
	
	
	if(is_array($results0)){$results=$results+$results0;}
	if(is_array($results1)){$results=$results+$results1;}
	if(is_array($results2)){$results=$results+$results2;}
	if(is_array($results3)){$results=$results+$results3;}
	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	

}
function LVM_LV_DELSIZE(){
	$mapper=$_GET["lv-resize-red"];
	$size=$_GET["size"];
	$unit=$_GET["unit"];
	$results=array();
	$unix=new unix();
	
	
	$cmd0=$unix->find_program("lvreduce")." -y -f -L$size$unit $mapper";
	$cmd1=$unix->find_program("umount")." -f $mapper";
	$cmd2=$unix->find_program("resize2fs")." -f -p $mapper $size$unit";
	$cmd3=$unix->find_program("mount")." $mapper";
	
	writelogs_framework("$cmd0",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd0,$results0);
	
	writelogs_framework("$cmd2",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd2,$results2);	
	
	writelogs_framework("$cmd1",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd1,$results1);
	

	writelogs_framework("$cmd3",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd3,$results3);	
	
	
	if(is_array($results0)){$results=$results+$results0;}
	if(is_array($results1)){$results=$results+$results1;}
	if(is_array($results2)){$results=$results+$results2;}
	if(is_array($results3)){$results=$results+$results3;}
	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	

}



function LVM_REMOVE(){
	
	// dmsetup info -c
	$dev=$_GET["lvremove"];
	$unix=new unix();
	$cmd=$unix->find_program("lvremove")." -f $dev 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function SASL_FINGER(){
	$unix=new unix();
	$saslfinger=$unix->find_program("saslfinger");
	if(!is_file($saslfinger)){
		echo "<articadatascgi>". base64_encode(serialize(array("unable to stat saslfinger")))."</articadatascgi>";
		return;
	}	
	
	exec("$saslfinger -s",$returns);
	echo "<articadatascgi>". base64_encode(serialize($returns))."</articadatascgi>";
}

function SASL_pluginviewer(){
	$unix=new unix();
	$saslfinger=$unix->find_program("pluginviewer");
	if(!is_file($saslfinger)){
		echo "<articadatascgi>". base64_encode(serialize(array("unable to stat pluginviewer")))."</articadatascgi>";
		return;
	}	
	
	exec("$saslfinger -c 2>&1",$returns);
	echo "<articadatascgi>". base64_encode(serialize($returns))."</articadatascgi>";	
	
}

function cups_delete_printer(){
	$unix=new unix();
	$printer=$_GET["cups-delete-printer"];
	$printer=urlencode($printer);
	$lpadmin=$unix->find_program("lpadmin");
	if(!is_file($lpadmin)){
			echo "<articadatascgi>". base64_encode(serialize(array("unable to stat lpadmin")))."</articadatascgi>";
			return;
		}
	
	exec("$lpadmin -x $printer",$returns);
	echo "<articadatascgi>". base64_encode(serialize($returns))."</articadatascgi>";	
}


function samba_reconfigure(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");	
	$cmd=$nohup." /usr/share/artica-postfix/bin/artica-install --samba-reconfigure >/dev/null 2>&1 &";
	NOHUP_EXEC(trim($cmd));
	
}

function squid_config(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");	
	$cmd="$nohup /etc/init.d/artica-postfix restart squid-cache >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}






function etc_hosts_open(){
	$datas=explode("\n",@file_get_contents("/etc/hosts"));
	foreach ($datas as $num=>$ligne){
		if(trim($ligne)==null){continue;}
		$newf[]=$ligne;
	}	
	$newf[]="\n";
	$datz=serialize($newf);
	echo "<articadatascgi>". base64_encode($datz)."</articadatascgi>";	
}
function etc_hosts_add(){
}
 
function etc_hosts_del(){
}

function etc_hosts_del_by_values(){}

function file_content(){
	$datas=@file_get_contents(base64_decode($_GET["file-content"]));
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";	
}
function file_remove(){
	$f=base64_decode($_GET["file-remove"]);
	if(!is_file($f)){return;}
	@unlink($f);
	
}

function samba_smbclient(){
	$ini=new iniFrameWork("/etc/samba/smb.conf");
	$unix=new unix();
	$creds=unserialize(base64_decode($_GET["creds"]));
	$comp=$_GET["computer"];
	$cmd=$unix->find_program("smbclient")." -N -U {$creds[0]}%{$creds[1]} -L //$comp -g";
	exec($cmd,$results);
	if(is_array($results)){
		foreach ($results as $num=>$ligne){
			if(preg_match("#Disk\|(.+?)\|#",$ligne,$re)){
				$folder=$re[1];
				$array[$folder]=$ini->_params[$folder]["path"];
			}
		}
	}
	unset($array[$creds[0]]);
	if(!is_array($array)){$array=array();}
	writelogs_framework($cmd." =".count($array)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
	
}

function postfix_certificate(){
	$cmd='/usr/share/artica-postfix/exec.postfix.default-certificate.php --verbose';
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function certificate_infos(){
	$unix=new unix();
	$openssl=$unix->find_program("openssl");
	$l=$unix->FILE_TEMP();
	$f[]="/etc/ssl/certs/cyrus.pem";
	$f[]="/etc/ssl/certs/openldap/cert.pem";
	$f[]="/opt/artica/ssl/certs/lighttpd.pem";

	foreach ($f as $num=>$path){
		if(is_file($path)){
			$cmd="$openssl x509 -in $path -text -noout >$l 2>&1";
			break;
		}
	}
	
	if($cmd<>null){
		shell_exec($cmd);
		$datas=explode("\n",@file_get_contents($l));
		writelogs_framework($cmd." =".count($datas)." rows",__FUNCTION__,__FILE__,__LINE__);
		@unlink($l);
	}
	echo "<articadatascgi>". base64_encode(serialize($datas))."</articadatascgi>";
}

function process_kill_single(){
	if(!is_numeric($_GET["kill-pid-single"])){return;}
	if($_GET["kill-pid-single"]==null){return;}
	if($_GET["kill-pid-single"]<2){return;}	
	$unix=new unix();
	$cmd=$unix->find_program("kill")." -9 {$_GET["kill-pid-single"]}";
	writelogs_framework("kill PID process {$_GET["kill-pid-single"]}",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	sleep(1);	
}

function process_kill(){
	if(!is_numeric($_GET["kill-pid-number"])){return;}
	if($_GET["kill-pid-number"]==null){return;}
	if($_GET["kill-pid-number"]<2){return;}
	process_kill_perform($_GET["kill-pid-number"]);
}

function process_kill_perform($pid){
		if($pid==null){return;}
		if($pid<2){return;}
		$unix=new unix();
		$array=$unix->PROCESS_STATUS($pid);
		if(!$array){return null;}
		if($array[0]="Z"){
			writelogs_framework("Zombie detected PPID:{$array[1]}",__FUNCTION__,__FILE__,__LINE__);
			process_kill_perform($array[1]);			
		}
		$cmd=$unix->find_program("kill")." -9 {$pid}";
		writelogs_framework("kill PID process $pid",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
}

function TCP_LIST_NICS(){
	$datas=explode("\n",@file_get_contents("/proc/net/dev"));
	foreach ( $datas as $num=>$line ){
		if(preg_match("#^(.+?):#",$line,$re)){
			if(trim($re[1])=="lo"){continue;}
			if(preg_match("#pan[0-9]+#",$re[1])){continue;}
			if(preg_match("#tun[0-9]+#",$re[1])){continue;}
			if(preg_match("#vboxnet[0-9]+#",$re[1])){continue;}
			if(preg_match("#wmaster[0-9]+#",$re[1])){continue;}
			$re[1]=trim($re[1]);
			writelogs_framework("found '{$re[1]}'",__FUNCTION__,__FILE__,__LINE__);
			$array[]=trim($re[1]);
		}
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}


function TCP_NICS_STATUS_ARRAY(){
	$unix=new unix();
	$ALLARRAY=$unix->NETWORK_ALL_INTERFACES();	
	writelogs_framework(" TCP_NICS_STATUS_ARRAY: ".count($ALLARRAY)." elements ",__FUNCTION__,__FILE__,__LINE__);
	$sortie=base64_encode(serialize($ALLARRAY));
	echo "<articadatascgi>$sortie</articadatascgi>";
}


function TCP_NIC_INFOS(){
	$unix=new unix();
	$Interface=trim($_GET["nic-infos"]);
	$MAIN=$unix->NETWORK_ALL_INTERFACES();
	$f[]="BOOTPROTO=";
	$f[]="METHOD=debian";
	$f[]="DEVICE=$Interface";
	$f[]="MAC={$MAIN[$Interface]["MAC"]}";
	$datas=trim(@implode("\n",$f));
	echo "<articadatascgi>$datas</articadatascgi>";	
}



function samba_logon_scripts(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.samba.php --logon-scripts >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec(trim($cmd));	
}



function TCP_VIRTUALS(){
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/exec.virtuals-ip.php.html";
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/reconfigure-newtork.progress";
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",0777);
	@file_put_contents($GLOBALS["LOGSFILES"], "\n");
	writelogs_framework("initialize",__FUNCTION__,__FILE__,__LINE__);
	$unix=new unix();
	if(isset($_GET["stay"])){
		if($_GET["stay"]<>"no"){
			$php5=$unix->LOCATE_PHP5_BIN();
			writelogs_framework("initialize",__FUNCTION__,__FILE__,__LINE__);
			@unlink("/etc/artica-postfix/MEM_INTERFACES");
			shell_exec("$php5 /usr/share/artica-postfix/exec.virtuals-ip.php >{$GLOBALS["LOGSFILES"]} 2>&1");
			@chmod("/usr/share/artica-postfix/ressources/logs/web/exec.virtuals-ip.php.html",0777);
			return;
		}
	}
	
	
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	
	$restart=null;
	if(isset($_GET["restart"])){$restart=" --restart";}
	$nohup=$unix->find_program("nohup");
	writelogs_framework("nohup:$nohup",__FUNCTION__,__FILE__,__LINE__);
	$php5=$unix->LOCATE_PHP5_BIN();
	@unlink("/etc/artica-postfix/MEM_INTERFACES");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.virtuals-ip.php{$restart} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	@chmod($GLOBALS["LOGSFILES"],0777);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec(trim($cmd));
	


	
}

function TCP_VLANS(){
	
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=LOCATE_PHP5_BIN2();
	@unlink("/etc/artica-postfix/MEM_INTERFACES");

	
	if(isset($_GET["stay"])){
		@unlink("/etc/artica-postfix/MEM_INTERFACES");
		shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.virtuals-ip.php --vlans");
		return;
	}
	@unlink("/etc/artica-postfix/MEM_INTERFACES");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.virtuals-ip.php --vlans >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}




function MalwarePatrol(){
	@chmod("/usr/share/artica-postfix/bin/artica-update",0755);
	if(!is_file("/etc/squid3/malwares.acl")){@file_put_contents("/etc/squid3/malwares.acl","#");}

	}
	
function MalwarePatrol_list(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$pattern=trim(base64_decode($_GET["pattern"]));
	
	if($pattern==null){
		$cmd="$tail -n 200 /etc/squid3/malwares.acl 2>&1";
	}else{
		$pattern=str_replace("*",".*?",$pattern);
		$cmd="$grep --binary-files=text -i -E '$pattern' /etc/squid3/malwares.acl 2>&1";
	}
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	
}
	
function MalwarePatrolDatabasesCount(){
	$datas=explode("\n",@file_get_contents("/etc/squid3/malwares.acl"));
	$count=0;
	foreach ( $datas as $num=>$line ){
		if(trim($line)==null){continue;}
		if(substr($line,0,1)=="#"){continue;}
		$count=$count+1;
	}
	echo "<articadatascgi>$count</articadatascgi>";
	
}

function postfix_multi_queues(){
	$instance=$_GET["postfix-multi-queues"];
	$unix=new unix();
	$queue_directory=trim($unix->POSTCONF_MULTI_GET($instance,"queue_directory"));
	$queues=array("active","bounce","corrupt","defer","deferred","flush","hold","incoming");

	foreach ($queues as $queuename){
		$array["$queuename"]=$unix->dir_count_files_recursive("$queue_directory/$queuename");
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}


function postfix_multi_postqueue(){
	$instance=$_GET["postfix-multi-postqueue"];
	if($instance==null){$instance="MASTER";}
	$array=unserialize(@file_get_contents("{$GLOBALS["ARTICALOGDIR"]}/postqueue.$instance"));
	echo "<articadatascgi>". base64_encode($array["COUNT"])."</articadatascgi>";
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.watchdog.postfix.queue.php");
	
}
function postfix_multi_cfdb(){
	$unix=new unix();
	$postconf=$unix->find_program("postconf");
	if(!is_file($postconf)){return null;}
	$timeFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$Time=$unix->file_time_min($timeFile);
	if($Time<2){
		writelogs_framework("{$Time}mn, need to wait 2mn,aborting",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	
	$hostname=trim($_GET["postfix-multi-cfdb"]);
	if($hostname=="master"){
		NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.hashtables.php --aliases");
		NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.hashtables.php --transport");
		return;
	}
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"$hostname\"");
}


function system_reboot_force():bool{
	$unix=new unix();
	$echo=$unix->find_program("echo");	
	system("$echo s > /proc/sysrq-trigger");
	system("$echo u > /proc/sysrq-trigger");
	system("$echo b > /proc/sysrq-trigger");
	return true;
}



function postfix_smtp_senders_restrictions(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$hostname=trim($_GET["postfix-smtp-sender-restrictions"]);
	if($hostname=="master"){
		shell_exec(trim("$nohup $php5 /share/artica-postfix/exec.postfix.maincf.php --smtp-sender-restrictions >/dev/null 2>&1 &"));
		return;
	}
	shell_exec(trim("$nohup $php5 /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"$hostname\" >/dev/null 2>&1 &"));	
}




function postfix_postqueue_reprocess_msgid(){
	$unix=new unix();
	$postsuper=$unix->find_program("postsuper");
	$logpath=PROGRESS_DIR."/postcat-{$_GET["postsuper-r-master"]}.log";
	$cmd="$postsuper -r {$_GET["postsuper-r-master"]} -v >$logpath 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@chmod($logpath,0777);
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.watchdog.postfix.queue.php");	
}




function postfix_postqueue_master(){
	$instance=$_GET["postfix-multi-postqueue"];
	if($instance==null){$instance="MASTER";}
	writelogs_framework("OPEN:: {$GLOBALS["ARTICALOGDIR"]}/postqueue.$instance",__FUNCTION__,__LINE__);
	echo "<articadatascgi>". base64_encode(@file_get_contents("{$GLOBALS["ARTICALOGDIR"]}/postqueue.$instance"))."</articadatascgi>";
	
}







function START_STOP_SERVICES(){
	$md5=$_GET["APP"].$_GET["action"].$_GET["cmd"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/$md5.log","...");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/$md5.log",0777);
	
	if(is_file($_GET["cmd"])){
		$cmd=trim("$nohup {$_GET["cmd"]} {$_GET["action"]} >>/usr/share/artica-postfix/ressources/logs/web/$md5.log 2>&1 &");
		writelogs_framework("$cmd",__FUNCTION__,__LINE__);
		shell_exec($cmd);
		return;
	}
	
	$cmd=trim("$nohup /etc/init.d/artica-postfix {$_GET["action"]} {$_GET["cmd"]} >>/usr/share/artica-postfix/ressources/logs/web/$md5.log 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__LINE__);
	shell_exec($cmd);
	
	
}


function retranslator_execute(){
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-update --retranslator");
}
function retranslator_dbsize(){
	$unix=new unix();
	$cmd=$unix->find_program("du")." -h -s /var/db/kav/databases 2>&1";
	exec($cmd,$results);
	$text=trim(implode(" ",$results));
	if(preg_match("#^([0-9\.\,A-Z]+)#",$text,$re)){
		$dbsize=$re[1];
	}
	
	
	echo "<articadatascgi>". base64_encode($dbsize)."</articadatascgi>";
}
function retranslator_tmp_dbsize(){
	$unix=new unix();
	$tmp=$unix->TEMP_DIR();
	$array=$unix->getDirectories($tmp);
	foreach ($array as $num=>$ligne){
		if(preg_match("#(.+?)\/temporaryFolder\/bases\/av#",$ligne,$re)){
			$folder=$re[1];
		}
	}
	if(is_dir($folder)){
		$cmd=$unix->find_program("du")." -h -s $folder 2>&1";
		exec($cmd,$results);
		$text=trim(implode(" ",$results));
		if(preg_match("#^([0-9\.\,A-Z]+)#",$text,$re)){
			$dbsize=$re[1];
		}
	}else{
		$dbsize="0M";
	}
	
echo "<articadatascgi>". base64_encode($dbsize)."</articadatascgi>";
}






function retranslator_restart(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart retranslator");
}
function retranslator_sites_lists(){
	$cmd="/usr/share/artica-postfix/bin/retranslator.bin -s -c /etc/kretranslator/retranslator.conf 2>&1";
	exec($cmd,$results);
	writelogs_framework(count($results)." lines [$cmd]",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function retranslator_events(){
	$unix=new unix();
	$cmd=$unix->find_program("tail").' -n 100 /var/log/kretranslator/retranslator.log 2>&1';
	exec($cmd,$results);
	writelogs_framework(count($results)." lines [$cmd]",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function retranslator_status(){
	$cmd="/usr/share/artica-postfix/bin/artica-install --retranslator-status 2>&1";
	exec($cmd,$results);
	writelogs_framework(count($results)." lines [$cmd]",__FUNCTION__,__FILE__,__LINE__);
	$datas=implode("\n",$results);
	
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";	
}

function hamachi_net(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.hamachi.php");
//if(isset($_GET["hamachi-net"])){hamachi_net();exit;} 
}

function hamachi_status(){
	exec("/usr/share/artica-postfix/bin/artica-install --hamachi-status",$rr);
	$ini=new iniFrameWork();
	$ini->loadString(implode("\n",$rr));
	echo "<articadatascgi>". base64_encode(serialize($ini->_params))."</articadatascgi>";
}
function hamachi_sessions(){
	$unix=new unix();
	$session=array();
	exec($unix->find_program("hamachi")." list",$l);
	foreach ($l as $num=>$ligne){
		if(preg_match("#You have no networks#", $ligne)){break;}
		if(preg_match("#\[(.+?)\]#",$ligne,$re)){$net=$re[1];continue;}
		if(preg_match("#([0-9\.]+)#",$ligne,$re)){
			$session[$net][]=$re[1];
		}
	}
	echo "<articadatascgi>". base64_encode(serialize($session))."</articadatascgi>";
}

function hamachi_currentIP(){
	$unix=new unix();
	$cmd=$unix->find_program("hamachi")." 2>&1";
	exec($cmd,$datas);
	
	foreach ($datas as $num=>$ligne){
		if(preg_match("#address.+?([0-9\.]+)\s+#",$ligne,$re)){
			echo "<articadatascgi>". $re[1]."</articadatascgi>";
			break;
		}
	}
	
}

function hamachi_restart(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart hamachi");
	
}

function POWERDNS_RESTART(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.pdns_server.php --restart >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/pdns-recursor restart >/dev/null 2>&1 &");
}

function hamachi_delete_network(){
	$unix=new unix();
	$_GET["hamachi-delete-net"]=base64_decode($_GET["hamachi-delete-net"]);
	exec($unix->find_program("hamachi")." -c /etc/hamachi leave {$_GET["hamachi-delete-net"]}",$l);
	exec($unix->find_program("hamachi")." -c /etc/hamachi delete {$_GET["hamachi-delete-net"]}",$l);
}




function SendmailPath(){
	$unix=new unix();
	echo "<articadatascgi>". base64_encode($unix->LOCATE_SENDMAIL_PATH())."</articadatascgi>";
}


function kasmilter_license(){
	$unix=new unix();
	$tmpstr=$unix->FILE_TEMP();	
	$cmd="/usr/local/ap-mailfilter3/bin/licensemanager -c /usr/local/ap-mailfilter3/etc/keepup2date.conf";
	exec("$cmd -s >$tmpstr 2>&1");
	$results=explode("\n",@file_get_contents($tmpstr));
	@unlink($tmpstr);
	$results[]="--------------------------------------------------------------";
	$results[]=basename($cmd);
	$results[]="--------------------------------------------------------------";
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";	
}

function kavmilter_license(){
	$unix=new unix();
	$tmpstr=$unix->FILE_TEMP();	
	if(is_file("/opt/kav/5.6/kavmilter/bin/licensemanager")){
		$cmd="/opt/kav/5.6/kavmilter/bin/licensemanager";
		
	}
	
	if(is_file("/opt/kaspersky/kav4lms/bin/kav4lms-licensemanager")){
		$cmd="/opt/kaspersky/kav4lms/bin/kav4lms-licensemanager";
	}
	exec("$cmd -s >$tmpstr 2>&1");
	$results=explode("\n",@file_get_contents($tmpstr));
	@unlink($tmpstr);
	$results[]="--------------------------------------------------------------";
	$results[]=basename($cmd);
	$results[]="--------------------------------------------------------------";
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";
	
}




function kasversion(){
	exec("/usr/share/artica-postfix/bin/artica-install --kas3-version",$results);
	preg_match("#([0-9\.]+);([0-9]+);([0-9]+)#",implode("",$results),$re);
	$array["version"]=$re[1];
	$f=$re[2];
	$d=substr($f,0,2);
	$m=substr($f,2,2);
	$y=substr($f,4,4);
	
	$f=$re[3];
	$H=substr($f,0,2);
	$M=substr($f,2,2);
	$array["pattern"]="$y-$m-$d $H:$M:00";
	$unix=new unix();
	unset($results);
	$cmd2=$unix->find_program("du"). " -h -s /usr/local/ap-mailfilter3/cfdata/bases/";
	exec($cmd2,$results);
	$f=trim(implode(" ",$results));
	$f=str_replace(",",".",$f);
	preg_match("#([0-9\.A-Z]+)\s+#",$f,$re);
	$size=$re[1];
	$array["size"]=$size;
	
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}

function release_quarantine(){
	$array=unserialize(base64_decode($_GET["release-quarantine"]));
	$unix=new unix();
	$tmpfileConf=$unix->FILE_TEMP();
	
	$msmtp[]= "syslog on";
	$msmtp[]="from {$array["from"]}";
	$msmtp[]="protocol smtp";
	$msmtp[]="host 127.0.0.1";
	$msmtp[]="port 33559";
	@file_put_contents($tmpfileConf,implode("\n",$msmtp));
	if(is_file("/usr/share/artica-postfix/bin/artica-msmtp")){$msmtp_cmd="/usr/share/artica-postfix/bin/artica-msmtp";}
	if(is_file($unix->find_program("msmtp"))){$msmtp_cmd=$unix->find_program("msmtp");}
	$logfile=$unix->FILE_TEMP().".log";
	chmod($tmpfileConf,0600);
	$cmd="$msmtp_cmd --tls-certcheck=off --timeout=10 --file=$tmpfileConf --syslog=on  --logfile=$logfile -- {$array["to"]} <{$array["file"]}";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$data=explode("\n",@file_get_contents($logfile));
	writelogs_framework(implode("\n",$data),__FUNCTION__,__FILE__,__LINE__);
	@unlink($logfile);
	@unlink($tmpfileConf);
	echo "<articadatascgi>". base64_encode(serialize($data))."</articadatascgi>";
}

if(isset($_GET["uninstall-app"])){application_uninstall();exit;}

function application_uninstall(){
	$cmdline=base64_decode($_GET["uninstall-app"]);
	$app=$_GET["app"];
	$unix=new unix();
	@unlink("/usr/share/artica-postfix/ressources/install/$app.ini");
	@unlink("/usr/share/artica-postfix/ressources/install/$app.dbg");
	$tmpstr="/usr/share/artica-postfix/ressources/logs/UNINSTALL_$app";
	
	@file_put_contents($tmpstr,"Scheduled.....");
	@chmod($tmpstr,0755);
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install $cmdline >>$tmpstr 2>&1");
	$results=explode("\n",@file_get_contents($tmpstr));
	@unlink($tmpstr);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function application_debug_infos(){
	$appli=$_GET["AppliCenterGetDebugInfos"];
	$results=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/install/$appli.dbg"));
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	
}
function application_service_install(){
	$cmdline=base64_decode($_GET["services-install"]);
	writelogs_framework("launch $cmdline !!!",__FUNCTION__,__FILE__,__LINE__);
	NOHUP_EXEC("/usr/share/artica-postfix/bin/setup-ubuntu $cmdline");
}
function Restart_Policyd_Weight(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="#!/bin/sh";
	$f[]="$php /usr/share/artica-postfix/exec.initslapd.php --policyd-weight";
	$f[]="/etc/init.d/policyd-weight stop";
	$f[]="/etc/init.d/policyd-weight start";
	$f[]="";
	@file_put_contents("/usr/sbin/policyd-weight-restart", @implode("\n", $f));
	@chmod("/usr/sbin/policyd-weight-restart",0755);
	NOHUP_EXEC("/usr/sbin/policyd-weight-restart");	
}

function dansguardian_update(){
	@chmod("/usr/share/artica-postfix/bin/artica-update",0755);
	$cmd="/usr/share/artica-postfix/bin/artica-update --dansguardian --verbose";
	file_put_contents("/usr/share/artica-postfix/ressources/logs/DANSUPDATE","{waiting}...\n\n\n");
	@chmod("/usr/share/artica-postfix/ressources/logs/DANSUPDATE",0775);
	NOHUP_EXEC("$cmd >>/usr/share/artica-postfix/ressources/logs/DANSUPDATE");	
	}



function ifconfig_interfaces(){
	$unix=new unix();
	$cmd=$unix->find_program("ifconfig")." -s";
	exec($cmd,$results);
	foreach ($results as $index=>$line){
		if(preg_match("#^(.+?)\s+[0-9]+#",$line,$re)){
			$array[trim($re[1])]=trim($re[1]);
		}
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}

function ifconfig_interfaces_all(){
	$unix=new unix();
	$cmd=$unix->find_program("ifconfig")." -s";
	exec($cmd,$results);
	foreach ($results as $index=>$line){
		if(preg_match("#^(.+?)\s+[0-9]+#",$line,$re)){
			$array[trim($re[1])]=trim($re[1]);
		}
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";

}


function ifconfig_all():bool{
	$unix=new unix();
	$cmd=$unix->find_program("ifconfig")." -a 2>&1";
	exec($cmd,$results);
	@file_put_contents(PROGRESS_DIR."/ifconfig.a.arr",base64_encode(serialize($results)));
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	return true;
}
function ifconfig_all_ips(){
	$unix=new unix();
	$data=$unix->ifconfig_all_ips();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ifconfig_all_ips", serialize($data));
	echo "<articadatascgi>". base64_encode(serialize($data))."</articadatascgi>";
	
}


function organization_delete(){
	
	$ou=base64_decode($_GET["organization-delete"]);
	$deletmailboxes=$_GET["delete-mailboxes"];
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.delete-ou.php $ou $deletmailboxes");
}

function fetchmail_status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --fetchmail --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function fetchmail_logs(){
	$unix=new unix();
	$search=trim(base64_decode($_GET["search"]));
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$sourcefile="/var/log/fetchmail.log";
	$rp=25;
	if(is_numeric($_GET["rp"])){$rp=intval($_GET["rp"]);}
	if($search==null){
		$cmd="$tail -n $rp $sourcefile 2>&1";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
		exec($cmd,$results);
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return;
	}
	
	$search=$unix->StringToGrep($search);
	
	
	exec("$grep --binary-files=text -i -E '$search' /var/log/fetchmail.log|$tail -n $rp",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	
}

function AD_IMPORT_SCHEDULE(){
	$ou=base64_decode($_GET["ou"]);
	$schedule=base64_decode($_GET["schedule"]);
	@mkdir("/etc/artica-postfix/ad-import");
	$f="$schedule ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.ad-import-ou.php $ou"; 
	$file="/etc/artica-postfix/ad-import/import-ad-".md5($ou);
	@file_put_contents($file,$f);
	NOHUP_EXEC("/etc/init.d/artica-postfix restart daemon");

}

function LDAP_IMPORT_SCHEDULE(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.domains.ldap.import.php --schedules >/dev/null 2>&1");
	shell_exec($cmd);
}

function LDAP_IMPORT_EXEC(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.domains.ldap.import.php --import {$_GET["ID"]} >/dev/null 2>&1");
	shell_exec($cmd);	
}


function AD_REMOVE_SCHEDULE(){
	$ou=base64_decode($_GET["ou"]);
	$file="/etc/artica-postfix/ad-import/import-ad-".md5($ou);
	writelogs_framework("Remove $file");
	@unlink($file);
	NOHUP_EXEC("/etc/init.d/artica-postfix restart daemon");
}

function AD_PERFORM(){
$ou=base64_decode($_GET["ou"]);

$file="/usr/share/artica-postfix/ressources/logs/web/ad-$ou.log";
@file_put_contents($file,"{scheduled}...");
@chmod($file,777);
NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.ad-import-ou.php $ou");
}

function backup_sql_tests(){
	writelogs_framework("Testing backup id {$_GET["backup-sql-test"]}",__FUNCTION__,__FILE__,__LINE__);
	exec(LOCATE_PHP5_BIN2() ." /usr/share/artica-postfix/exec.backup.php {$_GET["backup-sql-test"]} --only-test --verbose",$results);
	
	writelogs_framework(count($results)." line",__FUNCTION__,__FILE__,__LINE__);
	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function backup_task_run(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.backup.php {$_GET["backup-task-run"]}");
}


function backup_build_cron(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.backup.php --cron");
}
function GlobalApplicationsStatus(){
	$unix=new unix();
	$mainfile="/usr/share/artica-postfix/ressources/logs/global.versions.conf";
	$mainstatus="/usr/share/artica-postfix/ressources/logs/global.status.ini";
	if(!is_file($mainfile)){
		shell_exec("/usr/share/artica-postfix/bin/artica-install -versions > /usr/share/artica-postfix/ressources/logs/global.versions.conf 2>&1");
	}
	if(!is_file($mainstatus)){
            shell_exec('/usr/share/artica-postfix/bin/artica-install --status > /usr/share/artica-postfix/ressources/logs/global.status.ini 2>&1');
	}
	
	$datas=@file_get_contents($mainstatus)."\n".@file_get_contents($mainfile);
	
	if($unix->file_time_min($mainstatus)>0){
		@unlink($mainfile);
		@unlink($mainstatus);
		NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install -versions >/usr/share/artica-postfix/ressources/logs/global.versions.conf");
		NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --status >/usr/share/artica-postfix/ressources/logs/global.status.ini");
	}
	NOHUP_EXEC("/bin/chmod 755 /usr/share/artica-postfix/ressources/logs/global.*");
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";
}
function resolv_conf(){
	$datas=explode("\n",@file_get_contents("/etc/resolv.conf"));
	echo "<articadatascgi>". base64_encode(serialize($datas))."</articadatascgi>";
	}
	
function GetMyDNSServers(){
	$datas=explode("\n",@file_get_contents("/etc/resolv.conf"));
	writelogs_framework("resolv.conf - > ". count($datas)." rows",__FUNCTION__,__FILE__,__LINE__);
	foreach ($datas as $line){
	if(preg_match("#nameserver\s+(.+)#",$line,$re)){
		writelogs_framework("found {$re[1]}",__FUNCTION__,__FILE__,__LINE__);
		$ip=trim($re[1]);
		if($ip==null){continue;}
		if($ip=="127.0.0.1"){continue;}
		$array[]=$ip;
		} 
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}	
	
function MyOs(){
	exec("/usr/share/artica-postfix/bin/artica-install --myos 2>&1",$results);
	writelogs_framework(trim(implode("",$results)),__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". trim(implode("",$results))."</articadatascgi>";
}
function lspci(){
	$unix=new unix();
	$lspci=$unix->find_program("lspci");
	exec("$lspci 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function freemem(){
	$unix=new unix();
	$prog=$unix->find_program("free");
	exec("$prog -m -o 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}
function dfmoinsh(){
	$unix=new unix();
	$prog=$unix->find_program("df");
	exec("$prog -h 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}
function printenv(){
	$unix=new unix();
	$prog=$unix->find_program("printenv");
	exec("$prog 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}
function GenerateCert(){
	$path=$_GET["path"];
	exec("/usr/share/artica-postfix/bin/artica-install --gen-cert $path",$results);
	echo "<articadatascgi>". trim(implode(" ",$results))."</articadatascgi>";
}
function GLOBAL_STATUS(){
exec("/usr/share/artica-postfix/bin/artica-install --all-status",$results);	
echo "<articadatascgi>". base64_encode((implode("\n",$results)))."</articadatascgi>";
}





function MONIT_RESTART(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart monit");
}

function LIGHTTPD_RESTART(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart apache");
}

function FCRON_RESTART(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart cron");
}
function NFS_RELOAD(){
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --nfs-reload");
}



function sabnzbdplus_restart(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart sabnzbdplus");
}

function EMAILRELAY_RESTART(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart artica-notifier");
}

function DNS_LIST(){
	
	$f=explode("\n",@file_get_contents("/etc/resolv.conf"));
	
	foreach ( $f as $index=>$line ){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#nameserver\s+(.+)#", $line,$re)){
			$DNS[]=$re[1];
		}
		
	}
	echo "<articadatascgi>". implode(";",$DNS)."</articadatascgi>";
}

FUNCTION procstat(){
	exec("/usr/share/artica-postfix/bin/procstat {$_GET["procstat"]}",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#(.+?):(.+)#",$line,$re)){
			$array[trim($re[1])]=trim($re[2]);
		}
	}
	
	if($array["start_time"]<>null){
		if(preg_match("#\(([0-9]+)#",$array["start_time"],$re)){
			$mins=$re[1]/60;
			$text="{$mins}mn";
			if($mins>60){
				$h=round($mins/60,2);
				if(preg_match("#(.+?)\.(.+)#",$h,$re)){
					if(strlen($re[2])==1){$re[2]="{$re[2]}0";}
					$text="{$re[1]}h {$re[2]}mn";
				}else{
					$text="{$h}h";
				}
			}
		}
	}
	
	$array["since"]=$text;
	
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";	
	
}

function imapsync_events(){
	$f="/usr/share/artica-postfix/ressources/logs/imapsync.{$_GET["imapsync-events"]}.logs";
	if(is_file($f)){
		exec("tail -n 300 $f",$datas);
	}else{
		writelogs_framework("unable to stat imapsync.{$_GET["imapsync-events"]}.logs",__FUNCTION__,__FILE__);
		exit;
	}
	writelogs_framework(basename($f).": ".count($datas)." rows",__FUNCTION__,__FILE__);
	echo "<articadatascgi>". base64_encode(serialize($datas))."</articadatascgi>";	
}

function imapsync_cron(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.mailsync.php --cron");
}
function imapsync_run(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.mailsync.php --sync {$_GET["imapsync-run"]}");
}
function imapsync_stop(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.mailsync.php --stop {$_GET["imapsync-stop"]}");
}

function imapsync_show(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.mailsync.php --sync {$_GET["imapsync-show"]} --verbose",$results);
	$datas=@implode("\n",$results);
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";
}


function cyrus_restore_mount_dir(){
	$taskid=$_GET["cyr-restore"];
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.backup.php --mount $taskid",$results);
	writelogs_framework(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.backup.php --mount $taskid",__FUNCTION__,__FILE__);
	$datas=trim(implode("",$results));
	writelogs_framework(strlen($datas)." bytes",__FUNCTION__,__FILE__);
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";	
	
}

function cyr_restore_computer(){
	$taskid=$_GET["cyr-restore-computer"];
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.backup.php --mount --id=$taskid --dir={$_GET["dir"]}",$results);
	$datas=trim(implode("",$results));
	writelogs_framework($datas,__FUNCTION__,__FILE__);
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";		
	// cyr-restore-computer
	
}
//cyr-restore-container
function cyr_restore_container(){
	$taskid=$_GET["cyr-restore-container"];
	$_GET["dir"]=base64_decode($_GET["dir"]);
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.backup.php --mount --id=$taskid --dir={$_GET["dir"]} --list",$results);
	$datas=trim(implode("",$results));
	writelogs_framework($datas,__FUNCTION__,__FILE__);
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";			
}
function cyr_restore_mailbox(){
	$datas=$_GET["cyr-restore-mailbox"];
	writelogs_framework($datas,__FUNCTION__,__FILE__);
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.backup.php --restore-mbx $datas");
}
function disk_format_big_partition(){
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/system.partition.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/system.partition.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	
	@touch($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	
	
	@chmod($GLOBALS["LOGSFILES"], 0777);
	@chmod($GLOBALS["CACHEFILE"], 0777);
	$nofstab=null;
	if(isset($_GET["nofstab"])){$nofstab=" --nofstab ";}
	
	$cmd=LOCATE_PHP5_BIN2() ." /usr/share/artica-postfix/exec.system.build-partition.php --full \"{$_GET["dev"]}\" \"{$_GET["label"]}\" \"{$_GET["fs_type"]}\"{$nofstab} >{$GLOBALS["LOGSFILES"]} 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__);			
	NOHUP_EXEC($cmd);
}
function RestartRsyncServer(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart rsync");
}

function rsync_load_config(){
	$datas=@file_get_contents("/etc/rsync/rsyncd.conf");
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";		
}
function rsync_save_conf(){
	$datas=base64_decode($_GET["rsync-save-conf"]);
	@file_put_contents("/etc/rsync/rsyncd.conf",$datas);
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --reload-rsync");
}
function ARTICA_MAILLOG_RESTART(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup /etc/init.d/postfix-logger restart >/dev/null 2>&1 &");
}
function disk_directory_size(){
	$dir=base64_decode($_GET["DirectorySize"]);
	$unix=new unix();
	exec($unix->find_program("du")." -h -s $dir 2>&1",$results);
	$r=implode("",$results);
	if(preg_match("#^(.+?)\s+#",$r,$re)){
		echo "<articadatascgi>". $re[1]."</articadatascgi>";		
	}
}

function cyrus_move_default_dir_to_currentdir(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.cyrus-restore.php --move-default-current");
}
function  cyrus_move_newdir(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.cyrus-restore.php --move-new-dir {$_GET["cyrus-SaveNewDir"]}";
	NOHUP_EXEC("$cmd");
	shell_exec($cmd);
}
function cyrus_rebuild_all_mailboxes(){
	$f="/usr/share/artica-postfix/ressources/logs/web/". md5($_GET["cyrus-rebuild-all-mailboxes"])."-mailboxes-rebuilded.log";
	@unlink("$f");
	@file_put_contents($f,"");
	@chmod($f,0755);
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.cyrus-restore.php --rebuildmailboxes {$_GET["cyrus-rebuild-all-mailboxes"]}");
	
}

function cyrus_imap_status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --cyrus-imap --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
}

function cyrus_activedirectory(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.cyrus.php --kinit --reload");
}
function cyrus_activedirectory_events(){
	echo "<articadatascgi>". base64_encode(@file_get_contents("{$GLOBALS["ARTICALOGDIR"]}/kinit.log"))."</articadatascgi>";	
	
}

function cyrus_imap_change_password(){
	$password=base64_decode($_GET["cyrus-change-password"]);
	NOHUP_EXEC("/etc/init.d/artica-process1 start");
	NOHUP_EXEC("/etc/init.d/cyrus-imapd restart");
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.maincf.php --mailbox-transport");
}


function postfix_hash_tables(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.hashtables.php");
}
function postfix_hash_transport_maps(){
	if(isset($_GET["hostname"])){$hostname=$_GET["hostname"];}
	if($hostname==null){$hostname="master";}
	if($hostname=="master"){
		NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.hashtables.php --transport --reload");
		NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.maincf.php --smtp-sender-restrictions");
		NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.opendkim.php --keyTable");
		NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.cluebringer.php --internal-domains");
		return;
	}

	
}



function postfix_multi_transport_maps(){
	$hostname=$_GET["hostname"];
	
	if($hostname=="master"){
		NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.hashtables.php --transport --reload");
		return;
	}
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"$hostname\"");
}


function postfix_hash_senderdependent(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.hashtables.php --smtp-passwords");
}
function postfix_hash_recipient_canonical(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.hashtables.php --recipient-canonical");
}



function postfix_hash_aliases(){
	$unix=new unix();
	$instance_id=intval($_GET["instance-id"]);
	$unix->framework_exec("exec.postfix.hashtables.php --aliases --instance-id=$instance_id");


}
function postfix_hash_bcc(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.hashtables.php --bcc");
}

function postfix_relayhost(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.hashtables.php --relayhost >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function postfix_sasl(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.maincf.php --smtp-sasl >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function postfix_others_values(){
	$unix=new unix();
	$instance_id=intval($_GET["instance-id"]);
	$unix->framework_execute("exec.postfix.maincf.php --others-values --instance-id=$instance_id","postfix.othervalues.progress.log","postfix.othervalues.progress.log");
}


function postfix_interfaces(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();		
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.postfix.maincf.php --interfaces >/dev/null 2>&1 &");
}

function CleanCache(){
	shell_exec("/bin/rm -f /usr/share/artica-postfix/ressources/logs/web/cache/* &");
	shell_exec("/bin/rm -f /usr/share/artica-postfix/ressources/logs/web/*.cache");
	shell_exec("/bin/rm -f /usr/share/artica-postfix/ressources/logs/web/icons-*");
	shell_exec("/bin/rm -f /usr/share/artica-postfix/ressources/logs/web/tooltips-*");
}




function RestartAmavis(){
	NOHUP_EXEC("/etc/init.d/amavis reload");
}


function fstab_acl(){
	$acl_enabled=$_GET["acl"];
	$dev=base64_decode($_GET["dev"]);
	writelogs_framework("$dev= enable acl=$acl_enabled",__FUNCTION__,__FILE__);
	$unix=new unix();
	$unix->FSTAB_ACL($dev,$acl_enabled);
	}
function fstab_quota(){
	$quota_enabled=$_GET["quota"];
	$dev=base64_decode($_GET["dev"]);
	writelogs_framework("$dev= enable quota=$quota_enabled",__FUNCTION__,__FILE__);
	$unix=new unix();
	$unix->FSTAB_QUOTA($dev,$quota_enabled);
	}	
	
function samba_add_acl_group(){
	$group=base64_decode($_GET["group"]);
	$path=base64_decode($_GET["path"]);
	$unix=new unix();
	$setfacl=$unix->find_program("setfacl");
	if($setfacl==null){
		$results[]="Unable to stat setfacl";
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return ;	
	}
	$cmd="$setfacl -m group:\"$group\":r \"$path\" 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__);
	exec($cmd,$results);
	samba_change_acl_items(1);
	if(is_array($results)){
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	}
	
}
function samba_add_acl_user(){
	$user=base64_decode($_GET["username"]);
	$path=base64_decode($_GET["path"]);
	$unix=new unix();
	$setfacl=$unix->find_program("setfacl");
	if($setfacl==null){
		$results[]="Unable to stat setfacl";
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return ;	
	}
	$cmd="$setfacl -m u:\"$user\":r \"$path\" 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__);
	exec($cmd,$results);
	samba_change_acl_items(1);
	if(is_array($results)){
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	}	
}

function samba_delete_acl_group(){
	$group=base64_decode($_GET["group"]);
	$path=base64_decode($_GET["path"]);
	$unix=new unix();
	$setfacl=$unix->find_program("setfacl");
	if($setfacl==null){
		$results[]="Unable to stat setfacl";
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return ;	
	}
	$cmd="$setfacl -x group:\"$group\" \"$path\" 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__);
	exec($cmd,$results);
	samba_change_acl_items(1);
	if(is_array($results)){
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	}	
	
}
function samba_delete_acl_user(){
	$user=base64_decode($_GET["username"]);
	$path=base64_decode($_GET["path"]);
	$unix=new unix();
	$setfacl=$unix->find_program("setfacl");
	if($setfacl==null){
		$results[]="Unable to stat setfacl";
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return ;	
	}
	$cmd="$setfacl -x u:\"$user\" \"$path\" 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__);
	exec($cmd,$results);
	samba_change_acl_items(1);
	if(is_array($results)){
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	}	
}

function samba_change_acl_group(){
	$group=base64_decode($_GET["group"]);
	$path=base64_decode($_GET["path"]);
	$unix=new unix();
	$setfacl=$unix->find_program("setfacl");
	if($setfacl==null){
		$results[]="Unable to stat setfacl";
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return ;	
	}
	if($_GET["chmod"]==null){$_GET["chmod"]='---';}
	$cmd="$setfacl -m group:\"$group\":{$_GET["chmod"]} \"$path\" 2>&1";
	
	if($group=="GROUP"){
		$cmd="$setfacl -m g::{$_GET["chmod"]} \"$path\" 2>&1";
	}
	if($group=="OTHER"){
		$cmd="$setfacl -m o::{$_GET["chmod"]} \"$path\" 2>&1";
	}	
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__);
	exec($cmd,$results);
	samba_change_acl_items(1);
	if(is_array($results)){
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	}	
	
}

function samba_change_acl_items($noecho=0){
$path=base64_decode($_GET["path"]);
$unix=new unix();
	$setfacl=$unix->find_program("setfacl");
	$getfacl=$unix->find_program("getfacl");
	
	if($setfacl==null){
		$results[]="Unable to stat setfacl";
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return ;	
	}
	
	if($_GET["default"]==1){
		$cmd="$getfacl --access \"$path\" | $setfacl -d -M- \"$path\"";
		writelogs_framework("$cmd",__FUNCTION__,__FILE__);
		NOHUP_EXEC($cmd);
		
	}	
	
	if($_GET["recursive"]==1){
		$cmd="$getfacl --access \"$path\" | $setfacl -R -M- \"$path\"";
		writelogs_framework("$cmd",__FUNCTION__,__FILE__);
		NOHUP_EXEC($cmd);
	}

	if($noecho==1){return;}


}

function samba_change_acl_user(){
	$username=base64_decode($_GET["username"]);
	$path=base64_decode($_GET["path"]);
	$unix=new unix();
	$setfacl=$unix->find_program("setfacl");
	if($setfacl==null){
		$results[]="Unable to stat setfacl";
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		return ;	
	}
	if($_GET["chmod"]==null){$_GET["chmod"]='---';}
	$cmd="$setfacl -m u:\"$username\":{$_GET["chmod"]} \"$path\" 2>&1";
	
	if($username=="OWNER"){
		$cmd="$setfacl -m u::{$_GET["chmod"]} \"$path\" 2>&1";
	}
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__);
	exec($cmd,$results);
	samba_change_acl_items(1);
	if(is_array($results)){
		echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	}		
}
	


function dansguardian_template(){
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-install --dansguardian-template");
}
function dansguardian_get_template(){
	echo "<articadatascgi>".@file_get_contents("/usr/share/artica-postfix/bin/install/dansguardian/template.html")."</articadatascgi>";
}

function dansguardian_categories(){
$unix=new unix();
	
	
}	

function find_sock_program(){
	$unix=new unix();
	echo "<articadatascgi>".  base64_encode($unix->find_program($_GET["find-program"]))."</articadatascgi>";	
}

function squidGuardDatabaseStatus(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.squidguard.php --db-status-www",$ri);
	echo "<articadatascgi>".  base64_encode(implode("",$ri))."</articadatascgi>";
}
function squidGuardStatus(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.squidguard.php --status",$ri);
	echo "<articadatascgi>".  base64_encode(implode("\n",$ri))."</articadatascgi>";	
}



function squidguardTests(){
	$uri=base64_decode($_GET["uri"]);
	$client=base64_decode($_GET["client"]);	
	$unix=new unix();
	$squidGuard=$unix->find_program("squidGuard");
	$echo=$unix->find_program("echo");
	$cmd="$echo \"$uri $client/- - GET\" | $squidGuard -c /etc/squid/squidGuard.conf -d 2>&1";
	exec($cmd,$results);
	$results[]=$cmd;
	echo "<articadatascgi>".  base64_encode(serialize($results))."</articadatascgi>";	
	
	
}


function SQUID_CACHE_INFOS(){
	$cache_file="/usr/share/artica-postfix/ressources/logs/web/squid.caches.infos";
	if(is_file($cache_file)){
		$time=file_time_min($cache_file);
		$datas=@file_get_contents($cache_file);
		writelogs_framework("$cache_file time:$time bytes:". strlen($datas),__FUNCTION__,__FILE__,__LINE__);
		if(strlen($datas)>20){
			if($time<10){echo "<articadatascgi>".  base64_encode($datas)."</articadatascgi>";return;}
		}
	}
	
	$unix=new unix();
	$array=$unix->squid_get_cache_infos();
	$serialized=serialize($array);
	@file_put_contents($cache_file,$serialized);
	chmod($cache_file,0777);
	echo "<articadatascgi>".  base64_encode($serialized)."</articadatascgi>";	
}


function cicap_reconfigure(){
	$unix=new unix();
	$unix->CICAP_SERVICE_EVENTS("Building ICAP service", __FILE__,__LINE__);
	if(isset($_GET["tenir"])){

		shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.c-icap.php --build");
		return;
	}
	
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.c-icap.php --build");
}

function cicap_reload(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart cicap");
}

function cicap_restart(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart cicap");
}


function SQUID_RESTART_NOW(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid.caches.infos");
	$cmd="/etc/init.d/artica-postfix restart squid-cache";
	$EnableWebProxyStatsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){$EnableWebProxyStatsAppliance=1;}
	if($EnableWebProxyStatsAppliance==1){
		$cmd=trim("$php5 /usr/share/artica-postfix/exec.squid.php --notify-clients-proxy");
	}
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function SQUID_RESTART(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	
	$cmd="$nohup /etc/init.d/artica-postfix restart squid-cache >/dev/null 2>&1 &";
	
	$EnableWebProxyStatsAppliance=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){$EnableWebProxyStatsAppliance=1;}
	if($EnableWebProxyStatsAppliance==1){
		$php5=LOCATE_PHP5_BIN2();
		$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --notify-clients-proxy >/dev/null 2>&1 &");
	}
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function SQUID_CACHES(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=LOCATE_PHP5_BIN2();
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid.caches.infos");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid.rebuild.infos");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.php --caches >/usr/share/artica-postfix/ressources/logs/web/squid.rebuild.infos 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$cmd");
}
function SQUID_CACHESOUTPUT(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=LOCATE_PHP5_BIN2();
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid.caches.infos");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid.rebuild.infos");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.php --caches --output >/usr/share/artica-postfix/ressources/logs/web/squid.rebuild.infos 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$cmd");
}

function SQUID_CACHES_RECONSTRUCT(){
	@unlink("/usr/share/artica-postfix/ressources/logs/web/squid.caches.infos");
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.squid.php --caches-reconstruct");
}


function iwlist(){
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.wifi.detect.cards.php --iwlist");
}

function start_wifi(){
	shell_exec("/etc/init.d/artica-postfix start wifi");
}

function WIFI_ETH_STATUS(){
	$unix=new unix();
	$eth=$unix->GET_WIRELESS_CARD();
	if($eth==null){
		writelogs_framework("NO eth card found",__FUNCTION__,__FILE__,__LINE__);
		return null;
	}
	$wpa_cli=$unix->find_program("wpa_cli");
	if($wpa_cli==null){
		writelogs_framework("NO wpa_cli found",__FUNCTION__,__FILE__,__LINE__);
		return null;
	}
	exec("$wpa_cli -p/var/run/wpa_supplicant status -i{$eth}",$results);
	writelogs_framework(count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	$conf="[IF]\neth=$eth\n".implode("\n",$results);
	echo "<articadatascgi>".  base64_encode($conf)."</articadatascgi>";	
}
function WIFI_ETH_CHECK(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.wifi.detect.cards.php --checkap",$r);
	echo "<articadatascgi>".  base64_encode(implode("\n",$r))."</articadatascgi>";	
}



function hostname_full(){
	$unix=new unix();
	$host=$unix->hostname_g();
	echo "<articadatascgi>$host</articadatascgi>";	
	
}

function clamav_status(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.status.php --clamav --nowachdog >/usr/share/artica-postfix/ressources/logs/web/clamav.status");
	
	
}


function GetUniqueID(){
	$unix=new unix();
	$uuid=$unix->GetUniqueID();
	echo "<articadatascgi>". base64_encode($uuid)."</articadatascgi>";	
	
}

function shalla_update(){
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-update --filter-plus --force");
}


function dansguardian_community_categories(){
	$GLOBALS["SquidPerformance"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($GLOBALS["SquidPerformance"]>2){return;}
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.web-community-filter.php --export");
}
function samba_wbinfo_domain(){
	$WORKGROUP=base64_decode($_GET["wbinfo-domain"]);
	$unix=new unix();
	$wbinfo=$unix->find_program("wbinfo");
	exec("$wbinfo -D $WORKGROUP 2>&1",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#(.+?):(.+)#",$line,$re)){
			$array[trim($re[1])]=trim($re[2]);
		}
		
	}
	
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}

function openvpn_rebuild_certificates(){
	shell_exec("/bin/rm /etc/artica-postfix/openvpn/keys/*");
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	
	$cmd=trim("$nohup /usr/share/artica-postfix/bin/artica-install --openvpn-build-certificate && /etc/init.d/artica-postfix restart openvpn");	
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function openvpn_server_exec_schedule(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.openvpn.php --schedule");
}
function openvpn_sesssions(){
	$array=explode("\n",@file_get_contents("/var/log/openvpn/openvpn-status.log"));
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}
function openvpn_client_sesssions(){
	$array=explode("\n",@file_get_contents("/etc/artica-postfix/openvpn/clients/{$_GET["openvpn-client-sesssions"]}/openvpn-status.log"));
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}
function postfix_get_main_cf(){
	if($_GET["hostname"]<>null){
		if(is_file("/etc/postfix-{$_GET["hostname"]}/main.cf")){
			writelogs_framework("/etc/postfix-{$_GET["hostname"]}/main.cf",__FUNCTION__,__FILE__,__LINE__);
			$array=explode("\n",file_get_contents("/etc/postfix-{$_GET["hostname"]}/main.cf"));
			echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";	
			return;
		}
		
	}
	writelogs_framework("/etc/postfix/main.cf",__FUNCTION__,__FILE__,__LINE__);
	$array=explode("\n", @file_get_contents("/etc/postfix/main.cf"));
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";	
}

function postfix_multi_status(){
	$hostname=$_GET["postfix-multi-status"];
	writelogs_framework("Statusof \"$hostname\"",__FUNCTION__,__FILE__,__LINE__);
	$pidfile="/var/spool/postfix-$hostname/pid/master.pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	
	writelogs_framework("Statusof \"$hostname\" $pidfile=$pid",__FUNCTION__,__FILE__,__LINE__);
	
	if($unix->process_exists($pid)){
		$array["PID"]=$pid;
	}
	
	if(!is_array($array)){return null;}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";	
	
	
}

	
function ProcessExists(){
	$pid=$_GET["PID"];
	$unix=new unix();
	if($unix->process_exists($pid)){
		echo "<articadatascgi>TRUE</articadatascgi>";
	}
	
}
function ProcessInfo(){
	$pid=$_GET["ProcessInfo"];
	$unix=new unix();
	$ARRAY=array();
	if($unix->process_exists($pid)){
		$ARRAY["PROCESS_TIME"]=$unix->PROCESS_TTL_TEXT($pid);
		$ARRAY["PROCESS_MIN"]=$unix->PROCCESS_TIME_MIN($pid);
		
	}
	echo "<articadatascgi>". base64_encode(serialize($ARRAY))."</articadatascgi>";
}

function postfix_multi_reconfigure(){
	$hostname=$_GET["postfix-multi-reconfigure"];
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"$hostname\"");	
}

function postfix_multi_ssl(){
	$hostname=$_GET["postfix-multi-sasl"];
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix-multi.php --instance-ssl \"$hostname\"");	
}
function postfix_multi_settings(){
	$hostname=$_GET["postfix-multi-settings"];
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix-multi.php --instance-settings \"$hostname\"");	
}
function postfix_multi_mastercf(){
	$hostname=$_GET["postfix-multi-mastercf"];
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix-multi.php --instance-mastercf \"$hostname\"");	
}
function postfix_multi_reconfigure_all(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix-multi.php");
}

function SQUID_PROXY_PAC_REBUILD(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2() ." /usr/share/artica-postfix/exec.proxy.pac.php --write");
}
function SQUID_PROXY_PAC_SHOW(){
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.proxy.pac.php --write");
	$datas=@file_get_contents("/usr/share/proxy.pac/proxy.pac");
	writelogs_framework("proxy.pac: ". strlen($datas)." bytes",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";	
}

function SQUID_CONF_EXPORT(){
	$datas=@file_get_contents("/etc/squid3/squid.conf");
	$datas=$datas."\n".
	 @file_get_contents("/etc/squid3/GlobalAccessManager_auth.conf")
	.@file_get_contents("/etc/squid3/ssl.conf")
	.@file_get_contents("/etc/squid3/url_rewrite_access.conf")
	.@file_get_contents("/etc/squid3/GlobalAccessManager_auth.conf")
	.@file_get_contents("/etc/squid3/GlobalAccessManager_deny_cache.conf")
	.@file_get_contents("/etc/squid3/GlobalAccessManager_deny.conf");
	
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";
}




function postfix_multi_perform_reload(){
	$unix=new unix();
	$hostname=$_GET["postfix-multi-perform-reload"];
	$postmulti=$unix->find_program("postmulti");
	shell_exec("$postmulti -i postfix-$hostname -p reload");
	}
function postfix_multi_perform_restart(){
	$unix=new unix();
	$hostname=$_GET["postfix-multi-perform-restart"];
	$postmulti=$unix->find_program("postmulti");
	shell_exec("$postmulti -i postfix-$hostname -p stop");
	writelogs_framework("$postmulti -i postfix-$hostname -p stop",__FUNCTION__,__FILE__,__LINE__);
	$cmdline=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix-multi.php --instance-start $hostname";
	//shell_exec("$postmulti -i postfix-$hostname -p start");
	writelogs_framework($cmdline,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmdline);
	}
function postfix_multi_perform_flush(){
	$unix=new unix();
	$hostname=$_GET["postfix-multi-perform-flush"];
	$postmulti=$unix->find_program("postmulti");
	shell_exec("$postmulti -i postfix-$hostname -p flush");
	}
function postfix_multi_perform_reconfigure(){
	}
	


	

function samba_events_lists(){
	foreach (glob("/var/log/samba/log.*") as $filename) {
		$file=basename($filename);
		$size=@filesize($filename)/1024;
		$array[$file]=$size;
		
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";	
}

function postfix_single_mynetworks(){
	$unix=new unix();
	$instance_id=intval($_GET["instance-id"]);
	$unix->framework_exec("exec.postfix.maincf.php --networks --instance-id=$instance_id");


	if(is_file("/etc/init.d/opendkim")) {
		$unix->framework_exec("exec.opendkim.php --restart");
	}
	if(is_file("/etc/init.d/milter-greylist")) {
		NOHUP_EXEC("/etc/init.d/milter-greylist restart");
	}
}


function MILTERDKIM_WHITELIST_DOMAINS(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.dkim-milter.php --whitelist-domains");
}


function postfix_luser_relay(){
	$unix=new unix();
	$instance_id=intval($_GET["instance-id"]);
	$unix->framework_exec("exec.postfix.maincf.php --luser-relay --reload --instance-id=$instance_id");
}

function samba_move_logs(){
	$filename=base64_decode($_GET["samba-move-logs"]);
	if(!is_file("/var/log/samba/$filename")){return null;}
	chdir("/var/log/samba");
	$unix=new unix();
	$zip=$unix->find_program("zip");
	$tar=$unix->find_program("tar");
	
	$target_filename="/usr/share/artica-postfix/ressources/logs/$filename.tar";
	
	$cmd="tar -cf $target_filename $filename";
	
	if($zip<>null){
		$target_filename="/usr/share/artica-postfix/ressources/logs/$filename.zip";
		$cmd="$zip $target_filename $filename";
	}
	if(is_file($target_filename)){@unlink($target_filename);}
	exec($cmd,$results);
	writelogs_framework("$cmd\n".@implode("\n",$results),__FUNCTION__,__FILE__,__LINE__);
	
	if(!file_exists($target_filename)){return null;}
	echo "<articadatascgi>". base64_encode(basename($target_filename))."</articadatascgi>";	
	}
	
function samba_delete_logs(){
	$filename=base64_decode($_GET["samba-delete-logs"]);
	writelogs_framework("try to delete /var/log/samba/$filename",__FUNCTION__,__FILE__,__LINE__);
	
	if(!is_file("/var/log/samba/$filename")){return null;}	
	@unlink("/var/log/samba/$filename");
}

function milter_greylist_reconfigure(){
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	if(isset($_GET["hostname"])){
		if($_GET["hostname"]<>"master"){
			$cmdp=" --hostname={$_GET["hostname"]} --ou=\"{$_GET["ou"]}\"";
			$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.php$cmdp --who=WebInterface >/dev/null 2>&1 &";
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			shell_exec($cmd);
			return;
		}
	}

	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.php --reload-single >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function milter_greylist_multi_status(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.milter-greylist.php --status --hostname={$_GET["hostname"]} --ou={$_GET["ou"]}";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
}

function amavis_get_events(){
	$maillog=$_GET["maillog"];
	$unix=new unix();
	$gep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$cmd="$tail -n 3000 $maillog|$gep amavis 2>&1";
	exec($cmd,$results);
	writelogs_framework("$cmd (". count($results).")" ,__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}

function amavis_get_config(){
	echo "<articadatascgi>". base64_encode(@file_get_contents("/usr/local/etc/amavisd.conf"))."</articadatascgi>";
}

function amavis_get_status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --amavis-full --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
}
function opendkim_status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --opendkim --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}
function amavis_watchdog(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.status.php --amavis-watchdog --verbose >/tmp/amavis.txt";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function amavis_get_template(){
	$tplname=$_GET["amavis-template-load"];
	writelogs_framework("loading \"$tplname\"",__FUNCTION__,__FILE__,__LINE__);
	if(is_file("/usr/local/etc/amavis/$tplname.txt")){
		writelogs_framework("loading /usr/local/etc/amavis/$tplname.txt",__FUNCTION__,__FILE__,__LINE__);
		$datas=@file_get_contents("/usr/local/etc/amavis/$tplname.txt");
	}
		
	if(trim($datas)==null){	
		writelogs_framework("loading /usr/share/artica-postfix/bin/install/amavis/$tplname.txt",__FUNCTION__,__FILE__,__LINE__);
		$datas=@file_get_contents("/usr/share/artica-postfix/bin/install/amavis/$tplname.txt");
	}
	
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";
}

function amavis_template_help(){
	writelogs_framework("loading /usr/share/artica-postfix/bin/install/amavis/README.customize",__FUNCTION__,__FILE__,__LINE__);
	$datas=@file_get_contents("/usr/share/artica-postfix/bin/install/amavis/README.customize");
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";
}

function amavis_save_template(){
	$tplname=$_GET["amavis-template-save"];
	copy("/etc/artica-postfix/settings/Daemons/amavis-template-$tplname","/usr/local/etc/amavis/$tplname.txt");
}


function move_uploaded_file_framework(){
	$src=base64_decode($_GET["src"]);
	$dest_path=base64_decode($_GET["move_uploaded_file"]);
	if(!is_file($src)){echo "<articadatascgi>$src source file, no such file or directory</articadatascgi>";exit;}
	if(!is_dir($dest_path)){echo "<articadatascgi>$dest_path destination path,no such file or directory</articadatascgi>";exit;}
	$filename=basename($src);
	writelogs_framework("/bin/mv $src $dest_path/$filename" ,__FUNCTION__,__FILE__,__LINE__);
	
	shell_exec("/bin/mv $src $dest_path/$filename");
}
function sslfingerprint(){
	$ip=$_GET["ip"];
	$port=$_GET["port"];
	$unix=new unix();
	$openssl=$unix->find_program("openssl");
	$cmd="$openssl s_client -connect  $ip:$port -showcerts | $openssl x509 -fingerprint -noout -md5 2>&1";
	writelogs_framework("$cmd" ,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	foreach ($results as $index=>$line){
		writelogs_framework("$line" ,__FUNCTION__,__FILE__,__LINE__);
		if(preg_match("#MD5 Fingerprint=(.+)#",$line,$re)){
			echo "<articadatascgi>". base64_encode(trim($re[1]))."</articadatascgi>";
			return ;
		}
	}
}
function emailing_import_contacts(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.emailing-import.php");
}
function emailing_database_migrate_export(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.emailing-import.php --import-id {$_GET["emailing-database-migrate-perform"]}");
}

function emailing_database_make_unique(){
	$id=$_GET["ID"];
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.emailing-import.php --make-unique $id";
	NOHUP_EXEC($cmd);
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	}

function dkim_check_presence_key(){
	$file="/etc/amavis/dkim/{$_GET["dkim-check-presence-key"]}.key";
	if(is_file($file)){echo "<articadatascgi>". base64_encode("TRUE")."</articadatascgi>";exit;}
	writelogs_framework("UNABLE TO STAT $file" ,__FUNCTION__,__FILE__,__LINE__);
}
function dkim_amavis_build_key(){
	@mkdir("/etc/amavis/dkim",0666,true);
	$key="/etc/amavis/dkim/{$_GET["dkim-amavis-build-key"]}.key";
	if(is_file($key)){@unlink($key);}
	@chown("/usr/share/artica-postfix/bin/install/amavis/check-external-users.conf","root");
	
	$cmd="/usr/local/sbin/amavisd -c /usr/local/etc/amavisd.conf genrsa $key";
	writelogs_framework("$cmd" ,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
	}
	
function dkim_amavis_show_keys(){
	
	$cmd="/usr/local/sbin/amavisd -c /usr/local/etc/amavisd.conf showkeys";
	writelogs_framework("$cmd" ,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	foreach ($results as $index=>$line){
		if(preg_match("#;\s+key.+?,\s+domain\s+(.+?),\s+\/etc\/amavis#",$line,$re)){$domain=$re[1];continue;}
		$ri[$domain][]=$line;
		}
		
	echo "<articadatascgi>". base64_encode(serialize($ri))."</articadatascgi>";

}


function dkim_amavis_tests_keys(){
	$cmd="/usr/local/sbin/amavisd -c /usr/local/etc/amavisd.conf testkeys 2>&1";
	writelogs_framework("$cmd" ,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);

	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}

function hdparm_infos(){
$unix=new unix();
$hdparm=$unix->find_program("hdparm");
exec("$hdparm -I {$_GET["hdparm-infos"]} 2>&1",$results);
echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function spamassassin_check(){
	$unix=new unix();
	$bin=$unix->find_program("spamassassin");
	exec("$bin --lint -D 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}

function spamassassin_trust_networks(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.spamassassin.php --trusted");	
}

function spamassassin_rebuild(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.spamassassin.php --build");	
}



function emailing_builder_linker(){
	$ou=$_GET["emailing-builder-linker"];
	writelogs_framework("exec.emailing.php --build-queues $ou " ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.emailing.php --build-queues $ou &");
}

function emailing_builder_linker_simple(){
	$ou=base64_decode($_GET["ou"]);
	writelogs_framework("exec.emailing.php --build-single-queue {$_GET["ID"]} $ou" ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.emailing.php --build-single-queue {$_GET["ID"]} $ou &");
	
}


function emailing_build_emailrelays(){
	writelogs_framework("exec.emailrelay.php --emailrelays-emailing" ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.emailrelay.php --emailrelays-emailing &");
}

function system_debian_kernel(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.apt-cache.kernel.php --detect");
}

function system_debian_kernel_upgrade(){
	$pkg=$_GET["system-debian-upgrade-kernel"];
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.apt-cache.kernel.php --install $pkg");
}

function reports_build_quarantine_cron(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.quarantine.reports.php --build-cron-users");
	
}
function reports_build_quarantine_send(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.quarantine.reports.php ----user {$_GET["pdf-quarantine-send"]}");
	
}



function emailing_emailrelays_status_ou(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.emailrelay.php --emailing-ou-status {$_GET["ou"]}";
	writelogs_framework("$cmd" ,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	$datas=@implode("\n",$results);
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";	
	}
	
function emailing_emailrelays_remove(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.emailrelay.php --emailing-remove {$_GET["emailing-remove-emailrelays"]}";
	writelogs_framework("$cmd" ,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	}
function cyrus_empty_mailbox(){
	$unix=new unix();
	$ipurge=$unix->LOCATE_CYRUS_IPURGE();
	if($ipurge==null){echo "<articadatascgi>". base64_encode("Could not locate ipurge")."</articadatascgi>";return;}
	$user=$_GET["uid"];
	if($user==null){echo "<articadatascgi>". base64_encode("No user set")."</articadatascgi>";return;}
	
	if(trim($_GET["size_of_message"])<>null){$params[]="-m{$_GET["size_of_message"]}";}
	if(trim($_GET["age_of_message"])<>null){$params[]="-d{$_GET["age_of_message"]}";}	
	if($_GET["submailbox"]<>null){$submailbox="/{$_GET["submailbox"]}";}
	$params[]="user/$user$submailbox";
	$cmd="su cyrus -c \"$ipurge -f ".@implode(" ",$params)." 2>&1\"";
	writelogs_framework("$cmd" ,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	
	if($_GET["by"]==-100){$_GET["by"]="Super Administrator";}
	
	$finale=trim(implode("",$results));
	if($finale==null){$results[]="Executed...";}
	$unix->send_email_events("Messages task deletion on mailbox $user$submailbox by {{$_GET["by"]} executed",@implode("\n",$results),"mailbox");
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
	
}

function smtp_hack_reconfigure(){
	shell_exec("/bin/touch {$GLOBALS["ARTICALOGDIR"]}/smtp-hack-reconfigure");
}



function pureftpd_status(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --pure-ftpd --nowachdog";
	writelogs_framework("$cmd" ,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	$datas=@implode("\n",$results);
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";	
}
function directory_chown(){
	$path=shellEscapeChars(utf8_encode(base64_decode($_GET["chown"])));
	$uid=base64_decode($_GET["uid"]);
	
	if(preg_match("^\/home\/#", $path)){
		if(!is_dir($path)){@mkdir($path,0755,true);}
	}
	
	$unix=new unix();
	$cmd=$unix->find_program("chown")." $uid $path 2>&1";
		
	exec($cmd,$results);
	writelogs_framework("\n$cmd\n". @implode("\n",$results) ,__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}

function smbclientL(){
	$ip=$_GET["smbclientL"];
	$user=base64_decode($_GET["user"]);
	$password=base64_decode($_GET["password"]);
	$unix=new unix();
	$smbclient=$unix->find_program("smbclient");
	if($smbclient==null){
		writelogs_framework("UNable to find smbclient" ,__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	$f[]=$smbclient;$f[]="-L";$f[]=$ip;	
	if($password==null){$f[]="-N";}else{$f[]="-U $user%$password";}
	$cmd=@implode(" ",$f);
	exec($cmd,$results);
	writelogs_framework("\n$cmd\n". @implode("\n",$results) ,__FUNCTION__,__FILE__,__LINE__);
	foreach ($results as $index=>$line){
		if(preg_match("#session setup failed: (.+)#",$line,$re)){
			echo "<articadatascgi>". base64_encode($re[1])."</articadatascgi>";
			return;
		}
		if(preg_match("#(.+?)\s+(Printer|IPC|Disk)(.*)#",$line,$re)){
			$array[trim($re[1])]=array("TYPE"=>trim($re[2]),"INFOS"=>trim($re[3]));
		}else{
			writelogs_framework("$line NO MATCH",__FUNCTION__,__FILE__,__LINE__);	
		}
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}


function backuppc_load_computer_config(){
	
	$unix=new unix();
	$path=$unix->BACKUPPC_locate_config_path();
	if(!is_dir($path)){return;}
	$file="$path/{$_GET["backuppc-comp"]}.pl";
	if(!is_file($file)){return null;}
	writelogs_framework("Open $file",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>". base64_encode(@file_get_contents($file))."</articadatascgi>";
}

function backuppc_save_computer_config(){
	$unix=new unix();
	$path=$unix->BACKUPPC_locate_config_path();
	if(!is_dir($path)){return;}	
	$file="$path/{$_GET["backuppc-save-computer"]}.pl";
	$file2="/usr/share/artica-postfix/ressources/logs/{$_GET["backuppc-save-computer"]}.pl";
	@copy($file2,$file);
	@chown($file,"backuppc");
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.backup-pc.php --affect");
	shell_exec("/etc/init.d/backuppc reload");
	}
	
function backuppc_affect(){
	$unix=new unix();
	$path=$unix->BACKUPPC_locate_config_path();
	if(!is_dir($path)){return;}	
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.backup-pc.php --affect");
}

function backuppc_restart(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart backuppc");
}

function backuppc_computer_infos(){
	$unix=new unix();
	$uid=$_GET["backuppc-computer-infos"];
	$TopDir=$unix->BACKUPPC_GET_CONFIG_INFOS("TopDir");
	
	$datas=@file_get_contents("$TopDir/log/status.pl");
	writelogs_framework("$uid: Open TopDir $TopDir/log/status.pl ". strlen($datas)." bytes lenght",__FUNCTION__,__FILE__,__LINE__);
	$pattern='#"'.$uid.'".*?=>.*?\{(.+?)\}#is';
	$array=array();
	if(preg_match($pattern,$datas,$re)){
		writelogs_framework("$uid: found $pattern",__FUNCTION__,__FILE__,__LINE__);
		$f=@explode("\n",$re[1]);
		foreach ($f as $num=>$ligne){
			if(preg_match('#"(.+?)".*=>(.*)#',$ligne,$re)){
				$re[2]=str_replace(",","",trim($re[2]));
				$re[2]=str_replace("'","",$re[2]);
				$re[2]=str_replace('"',"",$re[2]);
				$array[$re[1]]=$re[2];
			}else{
				writelogs_framework("$uid: Not found $ligne",__FUNCTION__,__FILE__,__LINE__);
			}
		}
		
		
		
	}else{
		writelogs_framework("$uid: Not found $pattern",__FUNCTION__,__FILE__,__LINE__);
	}
	
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}

function kav4fs_infos(){
	
	exec("/opt/kaspersky/kav4fs/bin/kav4fs-control --app-info 2>&1",$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#(.+?)[:=]+(.+)#",$ligne,$re)){
			$array[trim($re[1])]=trim($re[2]);
		}else{
			writelogs_framework("$ligne No match",__FUNCTION__,__FILE__,__LINE__);
		}
		
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}

function kav4fs_status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --kav4fs --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}

function pptpd_status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --pptpd --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
}

function snort_status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --snort --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";		
}




function iscsi_status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --iscsi --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";		
}



function GREYHOLE_STATUS(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --greyhole --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
}

function sabnzbdplus_src_status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --sabnzbdplus --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
}

function openvpn_server_status(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --openvpn --nowachdog >/usr/share/artica-postfix/ressources/logs/web/openvpn.status 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	echo "<articadatascgi>". base64_encode(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/openvpn.status"))."</articadatascgi>";		
}
function openvpn_clients_status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --openvpn-clients --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
}

function dhcp_status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --dhcpd --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";		
}


function pptpd_clients_status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --pptpd-clients --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";		
}
function pptpd_chap(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.pptpd.php --chap");
		
}

function pptpd_restart(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart pptpd");
}

function AUDITD_STATUS(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --auditd --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
}







function kav4fs_install_key(){
	$license_file=base64_decode($_GET["kaf4fs-install-key"]);
	exec("/opt/kaspersky/kav4fs/bin/kav4fs-control --validate-key $license_file 2>&1",$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#error#i",$ligne,$re)){
			echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
			return;
		}
	}
	
}


function IP_CALC_CDIR(){
	$pattern=base64_decode($_GET["cdir-calc"]);
	@chmod("/usr/share/artica-postfix/bin/ipcalc",0755);
	exec("/usr/share/artica-postfix/bin/ipcalc \"$pattern\" 2>&1",$results);	
	foreach ($results as $num=>$ligne){
		if(preg_match("#Network:\s+\s+([0-9\.\/]+)\s+[0-9]+#",$ligne,$re)){
			echo "<articadatascgi>". base64_encode(trim($re[1]))."</articadatascgi>";
			return;
		}
	}
	
}




function UpdateUtilityPatternDatePath(){
	$unix=new unix();
	$base="/var/db/kaspersky/databases/Updates/index";
	if(is_file("$base/u0607g.xml")){return "$base/u0607g.xml";}	
	if(is_file("$base/master.xml")){return "$base/master.xml";}
	if(is_file("$base/masterv2.xml")){return "$base/master.xml";}
	if(is_file("$base/rt60.xml")){return "$base/rt60.xml";}
	return "$base/master.xml";
}


function UpdateUtilityPatternDate(){
	$unix=new unix();
	
	$base=$_GET["path"];
	if($base==null){
		$base=UpdateUtilityPatternDatePath();
	}
	writelogs_framework("Found $base",__FUNCTION__,__FILE__,__LINE__);
	if(!is_file($base)){
		writelogs_framework("$base no such file",__FUNCTION__,__FILE__,__LINE__);
		return;}
	$f=explode("\n",@file_get_contents($base));
	$reg='#UpdateDate="([0-9]+)\s+([0-9]+)"#';
	
	foreach ($f as $num=>$ligne){
		if(preg_match($reg,$ligne,$re)){
			writelogs_framework("Found {$re[1]} {$re[2]}",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>". base64_encode(trim($re[1]).";".trim($re[2]))."</articadatascgi>";
			return;
		}
	}	
	writelogs_framework("Not found",__FUNCTION__,__FILE__,__LINE__);
}

function OCSWEB_RESTART(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart ocsweb");
}
function OCSWEB_STATUS(){
exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --ocsweb --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
}
function OCSAGENT_STATUS(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --ocsagent --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
}
function OCSAGENT_RESTART(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart ocsagent");
}


function OCSWEB_CERTIFICATE(){
	$send=false;
	$UseSelfSignedCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseSelfSignedCertificate");
	if($UseSelfSignedCertificate==null){$UseSelfSignedCertificate=1;}
	
	if($UseSelfSignedCertificate==0){
		shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.ocsweb.php --certificate");
	}
	if($UseSelfSignedCertificate==1){
		exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.ocsweb.php --certificate-self 2>&1",$results);
		foreach ($results as $num=>$ligne){
			if(preg_match("#error#",$ligne)){$send=true;$err[]=$ligne;}
			writelogs_framework("OCS-PACKAGES:: --certificate-self: $ligne",__FUNCTION__,__FILE__,__LINE__);
		}
		
		if($send){
			echo "<articadatascgi>". base64_encode(@implode("\n",$err))."</articadatascgi>";	
		}
		
		NOHUP_EXEC("/etc/init.d/artica-postfix restart ocsweb");
	}
}

function OCSWEB_CERTIFICATE_CSR(){
	if(!is_file("/etc/ocs/cert/server.csr")){return null;}
	echo "<articadatascgi>". base64_encode(@file_get_contents("/etc/ocs/cert/server.csr"))."</articadatascgi>";
}
function OCSWEB_FINAL_CERTIFICATE(){
	$path=base64_decode($_GET["path"]);
	if(!is_file($path)){return null;}
	shell_exec("/bin/cp $path /etc/artica-postfix/settings/Daemons/OCSServerDotCrt");
	shell_exec("/bin/cp $path /etc/ocs/cert/server.crt");
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.ocsweb.php --final-cert");
	NOHUP_EXEC("/etc/init.d/artica-postfix restart ocsweb");
}

function OCSWEB_PACKAGE_INFOS(){
	$FILEID=$_GET["ocs-package-infos"];
	$filepath="/var/lib/ocsinventory-reports/download/$FILEID/info";
	$content=@file_get_contents($filepath);
	if(preg_match('#<DOWNLOAD\s+ID="(.*?)"\s+PRI="(.*?)"\s+ACT="(.*?)"\s+DIGEST="(.*?)"\s+PROTO="(.*?)"\s+FRAGS="(.*?)"\s+DIGEST_ALGO="(.*?)"\s+DIGEST_ENCODE="(.*?)"\s+PATH="(.*?)"\s+NAME="(.*?)"\s+COMMAND="(.*?)"\s+NOTIFY_USER="(.*?)"\s+NOTIFY_TEXT="(.*?)"\s+NOTIFY_COUNTDOWN="(.*?)"\s+NOTIFY_CAN_ABORT="(.*?)"\s+NOTIFY_CAN_DELAY="(.*?)"\s+NEED_DONE_ACTION="(.*?)"\s+NEED_DONE_ACTION_TEXT="(.*?)"#'
	,$content,$re)){
		$array["PRI"]=$re[2];
		$array["ACT"]=$re[3];
		$array["DIGEST"]=$re[4];
		$array["PROTO"]=$re[5];
		$array["FRAGS"]=$re[6];
		$array["DIGEST_ALGO"]=$re[7];
		$array["DIGEST_ENCODE"]=$re[8];
		$array["PATH"]=$re[9];
		$array["NAME"]=$re[10];
		$array["COMMAND"]=$re[11];
		$array["NOTIFY_USER"]=$re[12];
		$array["NOTIFY_TEXT"]=$re[13];
		$array["NOTIFY_COUNTDOWN"]=$re[14];
		$array["NOTIFY_CAN_ABORT"]=$re[15];
		$array["NOTIFY_CAN_DELAY"]=$re[16];
		$array["NEED_DONE_ACTION"]=$re[17];
		$array["NEED_DONE_ACTION_TEXT"]=$re[18];
		echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	}
}

function FILE_MD5(){
	echo "<articadatascgi>".md5_file(base64_decode($_GET["filemd5"]))."</articadatascgi>";
	
}

function OCSWEB_PACKAGE_COPY(){
	$sourcefile=base64_decode($_GET["filesource"]);
	$FILEID=$_GET["FILEID"];
	$document_root="/var/lib/ocsinventory-reports";
	@mkdir("$document_root/download/$FILEID",0666,true);
	shell_exec("/bin/cp $sourcefile $document_root/download/$FILEID/$FILEID");
}

function OCSWEB_PACKAGE_CREATE_INFO(){
	$unix=new unix();
	$userwww=$unix->APACHE_GROUPWARE_ACCOUNT();
	$FILEID=$_GET["FILEID"];
	$sourcefile="/usr/share/artica-postfix/ressources/logs/$FILEID.info";
	writelogs_framework("OCS-PACKAGES:: SOURCE=\"$sourcefile\"",__FUNCTION__,__FILE__,__LINE__);
	$document_root="/var/lib/ocsinventory-reports";
	@mkdir("$document_root/download/$FILEID",0755,true);
	@chown("$document_root/download/$FILEID",$userwww);
	$finale_file="$document_root/download/$FILEID/info";
	writelogs_framework("OCS-PACKAGES::$sourcefile file:$finale_file",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("/bin/cp $sourcefile $finale_file");
	@chown("$finale_file",$userwww);
	@unlink($sourcefile);
	writelogs_framework("OCS-PACKAGES:: chown:$userwww",__FUNCTION__,__FILE__,__LINE__);
	exec("/bin/chown -R $userwww $document_root/download/$FILEID",$results);
	foreach ($results as $num=>$ligne){
		writelogs_framework("OCS-PACKAGES:: chown: $ligne",__FUNCTION__,__FILE__,__LINE__);
	}
	
}


FUNCTION OCSWEB_PACKAGE_FRAGS(){
		$sourcefile=base64_decode($_GET["filesource"]);
		$unix=new unix();
		$userwww=$unix->APACHE_GROUPWARE_ACCOUNT();
		if(trim($sourcefile)==null){
			writelogs_framework("OCS-PACKAGES:: base64_decode({$_GET["filesource"]})=\"NULL\" aborting",__FUNCTION__,__FILE__,__LINE__);
			return;
		}		
		
		$FILEID=$_GET["FILEID"];
		if(trim($FILEID)==null){
			writelogs_framework("OCS-PACKAGES:: FILEID=\"NULL\" aborting",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		$nbfrags=$_GET["nbfrags"];
		
		if(trim($nbfrags)==null){
			writelogs_framework("OCS-PACKAGES:: nbfrags=\"NULL\" aborting",__FUNCTION__,__FILE__,__LINE__);
			return;
		}		

		$TMP=$unix->FILE_TEMP();
		shell_exec("/bin/cp $sourcefile $TMP");
		$document_root="/var/lib/ocsinventory-reports";
		writelogs_framework("OCS-PACKAGES:: nbfrags=\"$nbfrags\"",__FUNCTION__,__FILE__,__LINE__);
		writelogs_framework("OCS-PACKAGES:: SOURCE=\"$sourcefile\"",__FUNCTION__,__FILE__,__LINE__);
		writelogs_framework("OCS-PACKAGES:: DEST=\"$TMP\"",__FUNCTION__,__FILE__,__LINE__);
		@mkdir("$document_root/download/$FILEID",0755,true);
		@chmod("$document_root/download/$FILEID",0755);
		@chown("$document_root/download/$FILEID",$userwww);
		
		$fname = $TMP;
		if( $size = @filesize( $fname )) {
			writelogs_framework("OCS-PACKAGES:: SIZE=\"$size\"",__FUNCTION__,__FILE__,__LINE__);
			$handle = fopen ( $fname, "rb");
			
			$read = 0;
			for( $i=1; $i<$nbfrags; $i++ ) {
				$contents = fread ($handle, $size / $nbfrags );
				$read += strlen( $contents );
				writelogs_framework("OCS-PACKAGES:: OPEN=\"$document_root/download/$FILEID/$FILEID-$i\"",__FUNCTION__,__FILE__,__LINE__);
				$handfrag = fopen( "$document_root/download/$FILEID/$FILEID-$i", "w+b" );
				fwrite( $handfrag, $contents );
				fclose( $handfrag );
				@chown("$document_root/download/$FILEID/$FILEID-$i",$userwww);
			}	
			
			$contents = fread ($handle, $size - $read);
			$read += strlen( $contents );
			$handfrag = fopen( "$document_root/download/$FILEID/$FILEID-$i", "w+b" );
			fwrite( $handfrag, $contents );
			fclose( $handfrag );
			fclose ($handle);
			@chown("$document_root/download/$FILEID/$FILEID-$i",$userwww);
			unlink($TMP);
		}
		
	exec("/bin/chown -R $userwww $document_root/download/$FILEID",$results);
	foreach ($results as $num=>$ligne){
		writelogs_framework("OCS-PACKAGES:: chown: $ligne",__FUNCTION__,__FILE__,__LINE__);
	}		
		
}




function OCSWEB_PACKAGE_DELETE(){
	writelogs_framework("OCS-PACKAGES:: ID={$_GET["FILEID"]}",__FUNCTION__,__FILE__,__LINE__);
	$FILEID=$_GET["FILEID"];
	if(trim($FILEID)==null){return;}
	if(strpos($FILEID,"/")>0){return;}
	$FILEID=str_replace("..","",$FILEID);
	$document_root="/var/lib/ocsinventory-reports";
	writelogs_framework("OCS-PACKAGES:: /bin/rm -rf $document_root/$FILEID",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("/bin/rm -rf $document_root/$FILEID");
}
function OCSWEB_GET_AGENT_PACKAGE_FILENAME(){
	$document_root="/var/lib/ocsinventory-reports";
	foreach (glob("$document_root/OCSNG_WINDOWS_AGENT-*") as $filename) {
		$file=basename($filename);
		$size=@filesize($filename)/1024;
		$array[$file]=$size;
		
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}


function SQUID_REFRESH_PANEL_STATUS(){
	$GLOBALS["SQUID_REFRESH_PANEL_STATUS"]="/usr/share/artica-postfix/ressources/logs/web/restart.squid";
	@unlink("/usr/share/artica-postfix/ressources/logs/web/restart.squid");
	@touch("/usr/share/artica-postfix/ressources/logs/web/restart.squid");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/restart.squid",0777);
	
	@unlink("/usr/share/artica-postfix/ressources/logs/squid.restart.progress");
	touch("/usr/share/artica-postfix/ressources/logs/squid.restart.progress");
	@chmod("/usr/share/artica-postfix/ressources/logs/squid.restart.progress",0777);
	
	@unlink("/usr/share/artica-postfix/ressources/logs/squid.restart.progress2");
	touch("/usr/share/artica-postfix/ressources/logs/squid.restart.progress2");
	@chmod("/usr/share/artica-postfix/ressources/logs/squid.restart.progress2",0777);
	
}

function SQUID_RESTART_ONLY(){
	$unix=new unix();
	$nohup=null;
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup")." ";
	$force=null;
	
	SQUID_REFRESH_PANEL_STATUS();
	
	if(isset($_GET["force"])){$force=" --force";}
	
	writelogs_framework("Force = `$force`",__FUNCTION__,__FILE__,__LINE__);
	writelogs_framework("ApplyConfToo = `{$_GET["ApplyConfToo"]}`",__FUNCTION__,__FILE__,__LINE__);
	writelogs_framework("SQUID_REFRESH_PANEL_STATUS = `{$_GET["SQUID_REFRESH_PANEL_STATUS"]}`",__FUNCTION__,__FILE__,__LINE__);
	
	if(isset($_GET["ApplyConfToo"])){
		if(is_file("/etc/init.d/artica-memcache")){
			$cmd="$nohup /etc/init.d/artica-memcache >/dev/null 2>&1";
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			shell_exec($nohup.$cmd);
		}
		
		$cmd="$nohup /usr/sbin/artica-phpfpm-service -start-ufdb >> {$GLOBALS["SQUID_REFRESH_PANEL_STATUS"]} 2>&1 &";
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
		
		
		$cmd="$nohup $php /usr/share/artica-postfix/exec.squid.watchdog.php --restart --reconfigure --framework  {$force} >> {$GLOBALS["SQUID_REFRESH_PANEL_STATUS"]} 2>&1 &";
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		@file_put_contents($GLOBALS["SQUID_REFRESH_PANEL_STATUS"], $cmd."\n");
		shell_exec($cmd);
		return;
	}
	
	$f[]="#!/bin/sh";
	$f[]="/etc/init.d/squid restart$force --framework >> {$GLOBALS["SQUID_REFRESH_PANEL_STATUS"]} 2>&1";
	$f[]="/etc/init.d/dnsmasq restart$force --framework >> {$GLOBALS["SQUID_REFRESH_PANEL_STATUS"]} 2>&1";
	@file_put_contents("/tmp/squid.restart.sh", @implode("\n", $f));
	@chmod("/tmp/squid.restart.sh",0755);
	writelogs_framework("$nohup /tmp/squid.restart.sh >/dev/null 2>&1 &",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$nohup /tmp/squid.restart.sh >/dev/null 2>&1 &");
}

function LOAD_LANGUAGE_FILE(){
	writelogs_framework("Loading language pack {$_GET["GetLangagueFile"]}",__FUNCTION__,__FILE__,__LINE__);
	$path="/usr/share/artica-postfix/ressources/language/{$_GET["GetLangagueFile"]}.db";
	if(!is_file($path)){
		writelogs_framework("$path no such file",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$file=base64_encode(@file_get_contents("/usr/share/artica-postfix/ressources/language/{$_GET["GetLangagueFile"]}.db"));
	echo "<articadatascgi>$file</articadatascgi>";
}

FUNCTION InventoryAgentsWindowsVersions(){
	foreach (glob("/opt/artica/install/sources/fusioninventory/fusioninventory-agent_windows*.exe") as $filename) {
		$file=basename($filename);
		if(preg_match('#fusioninventory-agent_windows-i386-([0-9+\-\.]+)\.exe#i',$file,$r)){
          			if(strpos($r[1],'.')>0){
          				$key=$r[1];
          				$key=str_replace('.','',$key);
          				$key=str_replace('-','',$key);
          				$arr[$key]=$r[1];}
					}
          		
          		if(is_array($arr)){
          			ksort($arr);
					foreach ($arr as $num=>$val){$v[]=$val;}
          		}
          		
          		echo "<articadatascgi>{$v[count($v)-1]}</articadatascgi>";
	}
}

function OCSAGENT_UPDATE_FUSION_INVENTORY(){
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-make APP_OCSI_FUSIONCLIENT");
}

function WINEXE_VERSION(){
	$unix=new unix();
	$winexe=$unix->find_program("winexe");
	if($winexe==null){return;}
	exec("$winexe -V",$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#Version\s+(.+)#",$ligne)){
			echo "<articadatascgi>$ligne</articadatascgi>";
			return;
		}
	}
}

function hostToMac(){
	$unix=new unix();
	$newip=$unix->HostToIp($_GET["hostToMac"]);
	if($newip==null){$newip=$_GET["ip"];}
	if($newip==null){return;}
	echo "<articadatascgi>" .$unix->IpToMac($newip)."</articadatascgi>";
}
function hostToIp(){
	$unix=new unix();
	echo "<articadatascgi>" .$unix->HostToIp($_GET["hostToIp"])."</articadatascgi>";
}
function OCSWEB_MOVE_INVENTORY_WIN_PACKAGE(){
	$ver=$_GET["moveOcsAgentPackage"];
	@mkdir("/usr/share/artica-postfix/computers/ressources/logs/web",0777,true);
	shell_exec("/bin/cp /opt/artica/install/sources/fusioninventory/fusioninventory-agent_windows-i386-$ver.exe /usr/share/artica-postfix/computers/ressources/logs/web/fusioninventory-agent_windows-i386-$ver.exe");
	@chmod("/usr/share/artica-postfix/computers/ressources/logs/web/fusioninventory-agent_windows-i386-$ver.exe",0664);
}

function SYSLOG_QUERY(){
	
	$preprend=$_GET["prepend"];
	
	$pattern=trim(base64_decode($_GET["syslog-query"]));
	if($pattern=="yes"){$pattern=null;}
	$pattern=str_replace("  "," ",$pattern);
	$pattern=str_replace(" ","\s+",$pattern);
	$pattern=str_replace(".","\.",$pattern);
	$pattern=str_replace("*",".+?",$pattern);
	$pattern=str_replace("/","\/",$pattern);
	$syslogpath=$_GET["syslog-path"];
	$maxrows=0;
	if($syslogpath==null){
		
		exec("/usr/share/artica-postfix/bin/artica-install --whereis-syslog",$results);
		foreach ($results as $num=>$ligne){
			if(preg_match('#SYSLOG:"(.+?)"#',$ligne,$re)){
				$syslogpath=$re[1];
				break;
				writelogs_framework("artica-install --whereis-syslog $syslogpath" ,__FUNCTION__,__FILE__,__LINE__);
			}else{
				writelogs_framework("$ligne no match" ,__FUNCTION__,__FILE__,__LINE__);
			}
		}
		
		
	}
	$unix=new unix();
	$grepbin=$unix->find_program("grep");
	$tail = $unix->find_program("tail");
	if($tail==null){return;}
	if(isset($_GET["prefix"])){
		if(trim($_GET["prefix"])<>null){
			if(strpos($_GET["prefix"], ",")>0){$_GET["prefix"]="(".str_replace(",", "|", $_GET["prefix"]).")";}
			$_GET["prefix"]=str_replace("*",".*?",$_GET["prefix"]);
			$pattern="{$_GET["prefix"]}.*?\[[0-9]+\].*?$pattern";
		}
	}
	
	if($preprend<>null){
		$grep="$grepbin '$preprend'";
		if(strpos($preprend, ",")>0){$grep="$grepbin -E '(".str_replace(",", "|", $preprend).")'";}
	}
	
	writelogs_framework("Pattern \"$pattern\"" ,__FUNCTION__,__FILE__,__LINE__);
	if(isset($_GET["rp"])){$maxrows=$_GET["rp"];}
	if($maxrows==0){$maxrows=500;}
	
	
	if(strlen($pattern)>1){
		if(($preprend<>null) && (strlen($preprend)>3)){
			$preprend="'".$preprend."'";
			if(strpos($preprend, ",")>0){$preprend=" -E '(".str_replace(",", "|", $preprend).")'";}
			$grep="$grepbin $preprend|$grepbin -i -E '$pattern'";}
			else{
				$grep="$grepbin -i -E '$pattern'";
			}
	}
	
	unset($results);
	$l=$unix->FILE_TEMP();
	
	if($grep<>null){
		$cmd="$tail -n 5000 $syslogpath|$grep|$tail -n $maxrows 2>&1";
	}else{
		$cmd="$tail -n $maxrows $syslogpath 2>&1";
	}
	
	
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	if(count($results)<3){
		$maxrows=$maxrows+2000;
		if($grep<>null){
			$cmd="$tail -n 5000 $syslogpath|$grep |$tail -n $maxrows 2>&1";
		}else{
			$cmd="$tail -n $maxrows $syslogpath 2>&1";
		}
		writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
		exec($cmd,$results);	
	}
	
	if(count($results)<3){
		$maxrows=$maxrows+5000;
		if($grep<>null){
			$cmd="$grep $syslogpath|$tail -n $maxrows 2>&1";
		}else{
			$cmd="$tail -n $maxrows $syslogpath 2>&1";
		}
		writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
		exec($cmd,$results);	
	}	
	
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/syslog.query", @implode("\n", $results));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/syslog.query", 0755);
}

function SSHD_GET_CONF(){
	$unix=new unix();
	$config=$unix->LOCATE_SSHD_CONFIG_PATH();
	writelogs_framework("config=$config" ,__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>" .base64_encode(@file_get_contents($config))."</articadatascgi>";
}
function SSHD_RESTART(){
	$newfile="/etc/artica-postfix/settings/Daemons/OpenSSHDConfig";
	if(is_file($newfile)){
		$unix=new unix();
		$orginial=$unix->LOCATE_SSHD_CONFIG_PATH();
		if(is_file($orginial)){
			shell_exec("/bin/cp $newfile $orginial");
		}
	}
	
	if(is_file("/etc/init.d/ssh")){
		NOHUP_EXEC("/etc/init.d/ssh restart");
		return;
	}
	if(is_file("/etc/init.d/sshd")){
		NOHUP_EXEC("/etc/init.d/sshd restart");
		return;
	}

}

function GLUSTER_REMOUNT(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.gluster.php --remount");
}
function GLUSTER_UPDATE_CLIENTS(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.gluster.php --update-all-clients");
}
function GLUSTER_RESTART(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart gluster");
}
function GLUSTER_DELETE_CLIENTS(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.gluster.php --delete-clients");
}
function GLUSTER_NOTIFY_CLIENTS(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.gluster.php --notify-all-clients");
}
function GLUSTER_MOUNT(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.gluster.php --mount");
}


function GLUSTER_IS_MOUNTED(){
	$path=base64_decode($_GET["glfs-is-mounted"]);
	$unix=new unix();
	if($unix->GLFS_ismounted($path)){echo "<articadatascgi>1</articadatascgi>";return;}
}
function PASSWD_USERS(){
	$passwd=@file_get_contents("/etc/passwd");
	writelogs_framework("/etc/passwd ". strlen($passwd)." bytes" ,__FUNCTION__,__FILE__,__LINE__);
	$f=explode("\n",$passwd);
	writelogs_framework("/etc/passwd ". count($f)." lines" ,__FUNCTION__,__FILE__,__LINE__);
	foreach ($f as $ligne){
		if(preg_match("#^(.+?):#",$ligne,$re)){
			$array[$re[1]]=$re[1];
		}
	}
	writelogs_framework("/etc/passwd ". count($array)." rows" ,__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>" .base64_encode(serialize($array))."</articadatascgi>";
	
}





function SSHD_KEY_FINGERPRINT(){
	$uid=$_GET["uid"];
	$unix=new unix();
	$path=base64_decode($_GET["ssh-keygen-fingerprint"]);
	echo "<articadatascgi>" .base64_encode($unix->SSHD_GET_FINGERPRINT("$path/id_rsa.pub"))."</articadatascgi>";
}

function SSHD_KEY_DOWNLOAD_PUB(){
	$path=base64_decode($_GET["ssh-keygen-download"]);
	shell_exec("/bin/cp $path/id_rsa.pub /usr/share/artica-postfix/ressources/logs/web/id_rsa.pub");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/id_rsa.pub",0777);
	
}
function SSHD_KEY_UPLOAD_PUB(){
	$uploaded_file=base64_decode($_GET["rsa"]);
	$homedirectory=base64_decode($_GET["home"]);
	$uid=$_GET["uid"];
	writelogs_framework("Uploaded file: $uploaded_file" ,__FUNCTION__,__FILE__,__LINE__);
	writelogs_framework("homedirectory: $homedirectory" ,__FUNCTION__,__FILE__,__LINE__);
	writelogs_framework("uid: $uid" ,__FUNCTION__,__FILE__,__LINE__);
	
	if(!is_file($uploaded_file)){
		echo "<articadatascgi>" .base64_encode("$uploaded_file no such file")."</articadatascgi>";
		exit;
	}
	$unix=new unix();
	$fingerprint=$unix->SSHD_GET_FINGERPRINT($uploaded_file);
	if($fingerprint==null){
		echo "<articadatascgi>" .base64_encode("{fingerprint} {corrupted}")."</articadatascgi>";
		exit;
	}
	$cat=$unix->find_program("cat");
	$uploaded_file=$unix->shellEscapeChars($uploaded_file);
	@mkdir($homedirectory,0755,true);
	$cmd="$cat $uploaded_file >>$homedirectory/authorized_keys";
	writelogs_framework("$cmd" ,__FUNCTION__,__FILE__,__LINE__);
	exec("$cat $uploaded_file >>$homedirectory/authorized_keys",$results);
	if(is_file("$homedirectory/authorized_keys")){
		echo "<articadatascgi>" .base64_encode("{success}")."</articadatascgi>";
		shell_exec("/bin/chmod 755 $homedirectory");
		shell_exec("/bin/chmod 700 $homedirectory/.ssh");
		shell_exec("/bin/chmod 600 $homedirectory/.ssh/*");
		shell_exec("/bin/chown -R $uid:$uid $homedirectory");	
		return;	
		
	}
	$logs=@implode("<br>",$results);
	
	shell_exec("/bin/chmod 755 $homedirectory");
	shell_exec("/bin/chmod 700 $homedirectory/.ssh");
	shell_exec("/bin/chmod 600 $homedirectory/.ssh/*");
	shell_exec("/bin/chown -R $uid:$uid $homedirectory");	
	@chmod("/etc/sudoers", 0440);
	
	echo "<articadatascgi>" .base64_encode("{failed}<br>$logs")."</articadatascgi>";
}
function JOOMLA_INSTALL(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.joomla.install.php");
}

function OCSWEB_WEB_EVENTS(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	exec("$tail -n 350 /var/log/ocsinventory-server/apache-access.log 2>&1",$results);
	echo "<articadatascgi>" .base64_encode(serialize($results))."</articadatascgi>";
}
function OCSWEB_WEB_ERRORS(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	exec("$tail -n 350 /var/log/ocsinventory-server/apache-error.log 2>&1",$results);
	echo "<articadatascgi>" .base64_encode(serialize($results))."</articadatascgi>";
}
function OCSWEB_SERV_EVENTS(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	exec("$tail -n 350 /var/log/ocsinventory-server/activity.log 2>&1",$results);
	echo "<articadatascgi>" .base64_encode(serialize($results))."</articadatascgi>";
}
function GET_LOCAL_SID(){
	$unix=new unix();
	echo "<articadatascgi>" .$unix->GET_LOCAL_SID()."</articadatascgi>";
}
function AUDITD_REBUILD(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.auditd.php --build");
}

function AUDITD_SAVE_CONFIG(){
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.auditd.php --conf");
}

function AUDITD_CONFIG(){
	$datas=base64_encode(@file_get_contents("/etc/audit/auditd.conf"));
	writelogs_framework("/etc/audit/auditd.conf= ". strlen($datas)." bytes",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>$datas</articadatascgi>";
}


function AUDITD_FORCE(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.auditd.php --import --force");
}
function ReadFromfile(){
	$datas=@file_get_contents(base64_decode($_GET["read-file"]));
	writelogs_framework("". strlen($datas)." bytes",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>" .base64_encode($datas)."</articadatascgi>";
}
function postfix_import_domains_ou(){
	$ou_decoded=base64_decode($_GET["ou"]);
	$file="{$GLOBALS["ARTICALOGDIR"]}/domains.import.$ou_decoded.log";
	if(is_file($file)){@unlink($file);}
	writelogs_framework("Scheduling \"{$_GET["file"]}\ \"{$_GET["ou"]}\"",__FUNCTION__,__FILE__,__LINE__);
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.import.smtp.domains.php \"{$_GET["file"]}\" \"{$_GET["ou"]}\"");
	
}

function postfix_import_domains_ou_events(){
	$ou=base64_decode($_GET["ou"]);
	$file="{$GLOBALS["ARTICALOGDIR"]}/domains.import.$ou.log";
	if(!is_file($file)){
		writelogs_framework("$file no such file",__FUNCTION__,__FILE__,__LINE__);
	}
	echo "<articadatascgi>" .@file_get_contents($file)."</articadatascgi>";
}

function iptables_save(){
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	$cmd="$iptables_save 2>&1";
	exec($cmd,$results);
	writelogs_framework(count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
	$socks=new sockets();
	$socks->SET_INFO("IPTABLES_SAVE_DUMP",serialize($results));
}

function IPTABLES_CHAINES_BRIDGE_RULES(){
	if(!is_numeric($_GET["iptables-bridge-rules"])){return;}
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");

	
	$_GET["iptables-bridge-rules"]=trim($_GET["iptables-bridge-rules"]);
	$cmd="$iptables_save 2>&1";
	exec($cmd,$results);
	writelogs_framework(count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	$pattern="#.+?ArticaBridgesVirtual:{$_GET["iptables-bridge-rules"]}#";	
	$count=0;
	foreach ($results as $num=>$ligne){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$r[]=$ligne;}	
	}
	echo "<articadatascgi>" .base64_encode(serialize($r))."</articadatascgi>";
	
	
}

function IPTABLES_CHAINES_ROTATOR(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.ip-rotator.php --build");
}

function IPTABLES_CHAINES_ROTATOR_SHOW(){
	$unix=new unix();
	$iptables_save=$unix->find_program("iptables-save");
	$grep=	$unix->find_program("grep");
	$cmd="$iptables_save|$grep ArticaIpRotator 2>&1";
	exec($cmd,$results);
	writelogs_framework($cmd." -> ".count($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>" .base64_encode(serialize($results))."</articadatascgi>";
}

function OPENDKIM_RESTART(){
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.initslapd.php --opendkim");
	NOHUP_EXEC("/etc/init.d/opendkim restart");
}
function MILTERDKIM_RESTART(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart dkim-milter");
}


function OPENDKIM_SHOW_KEYS(){
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.opendkim.php --buildKeyView");
	echo "<articadatascgi>" .@file_get_contents("/etc/mail/dkim.domains.key")."</articadatascgi>";
	}
	
function OPENDKIM_BUILD_KEYS(){
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.opendkim.php --keyTable");
}
function MILTERDKIM_SHOW_KEYS(){
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.dkim-milter.php --buildKeyView");
	echo "<articadatascgi>" .@file_get_contents("/etc/mail/dkim.domains.key")."</articadatascgi>";
	}	
function OPENDKIM_SHOW_TESTS_KEYS(){
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.opendkim.php --TESTKeyView");
	echo "<articadatascgi>" .@file_get_contents("/etc/mail/dkim.domains.tests.key")."</articadatascgi>";
	}
function MILTERDKIM_SHOW_TESTS_KEYS(){
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.dkim-milter.php --TESTKeyView");
	echo "<articadatascgi>" .@file_get_contents("/etc/mail/dkim.domains.tests.key")."</articadatascgi>";
	}	
	

	
	
function squidGuardDatabaseMaintenance(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.c-icap.php --maint-schedule");
}
function squidGuardDatabaseMaintenanceNow(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.c-icap.php --db-maintenance");
}
function SETUP_CENTER_LAUNCH(){
	$app=$_GET["start-install-app"];
	writelogs_framework("$app to install",__FUNCTION__,__FILE__,__LINE__);
	if(trim($app)==null){return;}
	$unix=new unix();
	$cmd="/usr/share/artica-postfix/bin/artica-install --install-status $app";
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	foreach ($results as $num=>$ligne){
		writelogs_framework("$ligne",__FUNCTION__,__FILE__,__LINE__);
	}
	$tmpfile="/usr/share/artica-postfix/ressources/install/$app.dbg";
	
	//if(is_file($tmpfile)){if($unix->file_time_min($tmpfile)<1){return;}}

	
	@file_put_contents("Scheduled","/usr/share/artica-postfix/ressources/install/$app.dbg");
	shell_exec("/bin/chmod 777 /usr/share/artica-postfix/ressources/install/$app.dbg");
	
	writelogs_framework("Schedule /usr/share/artica-postfix/bin/artica-make $app >$tmpfile 2>&1",__FUNCTION__,__FILE__,__LINE__);
	NOHUP_EXEC("/usr/share/artica-postfix/bin/artica-make $app >$tmpfile 2>&1");
	}

function KERNEL_SYSCTL_VALUE(){
	$key=base64_decode($_GET["key"]);
	$unix=new unix();
	$sysctl=$unix->find_program("sysctl");
	exec("$sysctl -n $key",$results);
	echo "<articadatascgi>" .trim(@implode(" ",$results))."</articadatascgi>";
	
	
}

function KERNEL_SYSCTL_SET_VALUE(){
	$key=base64_decode($_GET["key"]);
	$unix=new unix();
	$sysctl=$unix->find_program("sysctl");
	$value=$_GET["sysctl-setvalue"];
	$cmd="$sysctl -w $key=$value 2>&1";

	exec($cmd,$results);
	writelogs_framework("$cmd <".@implode(" ",$results).">",__FUNCTION__,__FILE__,__LINE__);
	if(isset($_GET["write"])){
		$unix->sysctl("$key",$value);
	}
	
}

function SQUID_TASK_CACHE(){
	echo "<articadatascgi>" .trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheTask"))."</articadatascgi>";
}


function SQUID_TEMPLATES(){
	squid_admin_mysql(1, "Launch Templates builder", null,__FILE__,__LINE__);
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.squid.php --templates --force");
}

function virtualbox_status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --vboxwebsrv --nowachdog",$results);
	writelogs_framework(count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";	
}
function virtualbox_all_status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --vdi --nowachdog",$results);
	writelogs_framework(count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";	
}

function virtualbox_list_vms(){
	$unix=new unix();
	$array=array();
	$manage=$unix->find_program("VBoxManage");
	if($manage==null){
		writelogs_framework("VBoxManage no such tool",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	exec("$manage list -l vms",$results);
	foreach ($results as $ligne){
		if(preg_match("#(.+?):(.+)#",$ligne,$re)){
			$key=trim($re[1]);
			$data=trim($re[2]);
			if($key=="Name"){
				if(!preg_match("#\(UUID:\s+#",$data)){$VirtualBoxName=$data;}
			}
		
		
		if($VirtualBoxName<>null){
			if(strtoupper($key)=="NAME"){
				if($array[$VirtualBoxName]["NAME"]<>null){
					
					if(!$GLOBALS["VBXSNAPS"][$data]){
						$array[$VirtualBoxName]["SNAPS"][]=$data;
						$GLOBALS["VBXSNAPS"][$data]=true;
						continue;
					}
					continue;
				}
			}
			$array[$VirtualBoxName][strtoupper($key)]=$data;
		}
	}
}

	echo "<articadatascgi>" .base64_encode(serialize($array))."</articadatascgi>";
	
}
function virtualbox_showvminfo(){
	$unix=new unix();
	$array=array();
	$manage=$unix->find_program("VBoxManage");
	if($manage==null){
		writelogs_framework("VBoxManage no such tool",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	$uuid=base64_decode($_GET["uuid"]);
	
	exec("$manage showvminfo $uuid",$results);
	foreach ($results as $ligne){
		if(preg_match("#(.+?):(.+)#",$ligne,$re)){
			$key=trim($re[1]);
			$data=trim($re[2]);
			
		}
		
	if(strtoupper($key)=="NAME"){	
		if($array["NAME"]<>null){
			if(!$GLOBALS["VBXSNAPS"][$data]){
				$array["SNAPS"][]=$data;
				$GLOBALS["VBXSNAPS"][$data]=true;
				continue;
				}			
			continue;}
	}
	
	$array[strtoupper($key)]=$data;
	}
	exec("$manage list hdds",$results2);
	foreach ($results2 as $line){
		if(preg_match("#(.+?):(.+)#",$line,$re)){
			$key=trim($re[1]);
			$data=trim($re[2]);
			
		}
		
		if($key=="UUID"){$UUID=$data;continue;}
		if($key=="Location"){$filename=$data;continue;}
		if($key=="Usage"){
			if(preg_match("#UUID:\s+$uuid#",$data)){
				$array["HDS"][$UUID]=$filename;
			}
		}
	}
	
	

	echo "<articadatascgi>" .base64_encode(serialize($array))."</articadatascgi>";
	
}


function virtualbox_clonehd(){
	$unix=new unix();
	$array=unserialize(base64_decode($_GET["virtualbox-clonehd"]));
	
	$manage=$unix->find_program("VBoxManage");
	if($manage==null){
		writelogs_framework("VBoxManage no such tool",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$NAME=$array["NAME"];
	$NAME=str_replace(" ","-",$NAME);
	$uuid=$array["uuid"];
	$filename=$array["filename"];
	$type=$array["type"];
	$format=$array["format"];
	writelogs_framework("\"uuid\"=>$uuid,\"filename\"=>$filename,\"type\"=>$type,\"format\"=>$format",__FUNCTION__,__FILE__,__LINE__);
	if($uuid==null){return;}
	if(!is_file($filename)){return;}
	$basename=basename($filename);
	$dirname=dirname($filename);
	
	if(strpos($basename,"}")==0){
		if(preg_match("#(.+?)\.(.+?)$#",$basename,$re)){
		$newfile=$re[1]."-".time().".".$re[2];
		}else{
			$newfile=$NAME."-".time().".vdi";
		}
	}else{
		$newfile=$NAME."-".time().".vdi";
	}
	
	if($format<>null){$add[]="--format $format";}
	if($type<>null){$add[]="--type $type";}
	
	$add[]="--remember";
	$cmd="$manage clonehd $uuid $dirname/$newfile ".@implode(" ",$add);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". @implode("\n",$results)."</articadatascgi>";	
	
	
}

function virtualbox_showcpustats(){
	
	$unix=new unix();
	$computer_name=base64_decode($_GET["virtual-machine"]);
	$array=array();
	$manage=$unix->find_program("VBoxManage");
	if($manage==null){
		writelogs_framework("VBoxManage no such tool",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$cmd="$manage metrics query $computer_name";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec("$manage metrics query $computer_name 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#\s+CPU\/Load\/User\s+(.+)#",$line,$re)){
			$cpu_load_user=str_replace("%","",$re[1]);
			$cpu_load_user=str_replace(" ","",$cpu_load_user);
			$array["CPU_LOAD_USER_TABLE"]=explode(",",$cpu_load_user);	
		}
		if(preg_match("#\s+CPU\/Load\/Kernel\s+(.+)#",$line,$re)){
			$cpu_load_user=str_replace("%","",$re[1]);
			$cpu_load_user=str_replace(" ","",$cpu_load_user);
			$array["CPU_LOAD_KERNEL_TABLE"]=explode(",",$cpu_load_user);	
		}		
		
		if(preg_match("#\s+CPU\/Load\/Kernel:avg\s+([0-9\.]+)#",$line,$re)){
			$array["CPU_LOAD_KERNEL"]=$re[1];
		}
		if(preg_match("#\s+CPU\/Load\/User:avg\s+([0-9\.]+)#",$line,$re)){
			$array["CPU_LOAD_USER"]=$re[1];
		}
		if(preg_match("#\s+RAM\/Usage\/Used:avg\s+([0-9\.]+)#",$line,$re)){
			$array["RAM_USAGE"]=$re[1];
		}
		if(preg_match("#RAM/Usage/Used\s+(.+)#",$line,$re)){
			$cpu_load_user=str_replace(" kB","",$re[1]);
			$cpu_load_user=str_replace(" ","",$cpu_load_user);
			$array["CPU_LOAD_MEMORY_TABLE"]=explode(",",$cpu_load_user);	
		}		
		
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";	
}


	
function KEYBOARD_KEY_MAP(){
	$unix=new unix();
	exec($unix->find_program("find") ." /usr/share/keymaps/i386",$results);
	foreach ($results as $ligne){
		$line=str_replace("/usr/share/keymaps/i386/","",$ligne);
		if(preg_match("#.+?\/([A-Za-z0-9\-\_]+)\.kmap\.gz$#",$line,$re)){
			$array[$re[1]]=$re[1];
		}
		
	}
	
	ksort($array);
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";	
}

function THINCLIENT_REBUILD(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.thinclient.php --workstations");
}

function THINCLIENT_REBUILD_CD(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.thinclient.php");
}


function virtualbox_stop(){
	$uuid=$_GET["virtualbox-stop"];
	$unix=new unix();
	$tmp=$unix->TEMP_DIR();
	$array=array();
	$manage=$unix->find_program("VBoxManage");
	if($manage==null){
		writelogs_framework("VBoxManage no such tool",__FUNCTION__,__FILE__,__LINE__);
		return;
	}

	$cmd="$manage controlvm $uuid poweroff >$tmp/$uuid-stop 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	sleep(5);
	echo "<articadatascgi>". @file_get_contents("$tmp/$uuid-stop")."</articadatascgi>";
	
}



function virtualbox_start(){
	$uuid=$_GET["virtualbox-start"];
	$unix=new unix();
	$array=array();
	
	$tmp=$unix->TEMP_DIR();
	$manage=$unix->find_program("VBoxManage");
	if($manage==null){
		writelogs_framework("VBoxManage no such tool",__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	
	$VBoxHeadless=$unix->LOCATE_VBoxHeadless();
	if(is_file($VBoxHeadless)){
		$cmd="$VBoxHeadless --startvm $uuid --vrdp on >$tmp/$uuid-start 2>&1 &";
	}else{
		$cmd="$manage startvm $uuid --type headless >$tmp/$uuid-start 2>&1 &";
	}
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	sleep(5);
	echo "<articadatascgi>". @file_get_contents("$tmp/$uuid-start")."</articadatascgi>";
}
function virtualbox_snapshot(){
	$uuid=$_GET["virtualbox-snapshot"];
	$unix=new unix();
	$tmpdir=$unix->TEMP_DIR();
	$array=array();
	$manage=$unix->find_program("VBoxManage");
	if($manage==null){
		writelogs_framework("VBoxManage no such tool",__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	
	$time=time();
	$date=date("Y-m-d H:i:s");
	$cmd="$manage snapshot $uuid take $time --description \"saved on $date\" >$tmpdir/$uuid-stop 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	sleep(5);
	echo "<articadatascgi>". @file_get_contents("$tmpdir/$uuid-start")."</articadatascgi>";
}
function virtualbox_guestmemoryballoon(){
	$uuid=$_GET["virtualbox-guestmemoryballoon"];
	$unix=new unix();
	$array=array();
	$VBoxManage=$unix->find_program("VBoxManage");
	if($VBoxManage==null){
		writelogs_framework("VBoxManage no such tool",__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	$mem=$_GET["mem"];
	$results[]="Change settings on Opened Virtual machine:";
	$cmd="$VBoxManage controlvm $uuid guestmemoryballoon $mem 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	
	$results[]="";
	$results[]="Save settings on Virtual machine:";
	$cmd="$VBoxManage modifyvm $uuid --guestmemoryballoon $mem 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);	
	$results=VirtualBoxCleanArrayPub($results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}


function virtualbox_set_params(){
$unix=new unix();
	$array=array();
	$VBoxManage=$unix->find_program("VBoxManage");
	if($VBoxManage==null){
		writelogs_framework("VBoxManage no such tool",__FUNCTION__,__FILE__,__LINE__);
		return;
	}		
	$uuid=$_GET["virtualbox-set-params"];
	$cmd="$VBoxManage modifyvm $uuid --{$_GET["key"]} {$_GET["value"]} 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);	
	$results=VirtualBoxCleanArrayPub($results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
}


function virtualbox_nats(){
	$unix=new unix();
	$filetmp=$unix->FILE_TEMP();
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.virtualbox.php --nat-ports >$filetmp 2>&1");
	echo "<articadatascgi>". base64_encode(@file_get_contents($filetmp))."</articadatascgi>";
	@unlink($filetmp);
}
function virtualbox_nat_rebuild(){
	$unix=new unix();
	$filetmp=$unix->FILE_TEMP();
	$uuid=$_GET["virtualbox-nats-rebuild"];
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.virtualbox.php --nat-rebuild $uuid >$filetmp 2>&1");
	echo "<articadatascgi>". base64_encode(@file_get_contents($filetmp))."</articadatascgi>";
	@unlink($filetmp);
}



function virtualbox_nat_del(){
	$unix=new unix();
	$VBoxManage=$unix->find_program("VBoxManage");	
	if(strlen($VBoxManage)<4){return;}
	
	$vboxid=$_GET["uuid"];
	$localport=$_GET["localport"];
	$vboxport=$_GET["vboxport"];
	$cmd="$VBoxManage setextradata $vboxid \"VBoxInternal/Devices/pcnet/0/LUN#0/Config/ArticaNat{$localport}To{$vboxport}/HostPort\"";
	exec($cmd,$results);
	$cmd="$VBoxManage setextradata $vboxid \"VBoxInternal/Devices/pcnet/0/LUN#0/Config/ArticaNat{$localport}To{$vboxport}/GuestPort\" 2>&1";
	exec($cmd,$results);
	$cmd="$VBoxManage setextradata $vboxid \"VBoxInternal/Devices/pcnet/0/LUN#0/Config/ArticaNat{$localport}To{$vboxport}/Protocol\" 2>&1";
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",VirtualBoxCleanArrayPub($results)))."</articadatascgi>";
}

function VirtualBoxCleanArrayPub($array){
	if(!is_array($array)){return;}
	foreach ($array as $index=>$line){
		if(strpos($line,"VirtualBox Command Line Management")>0){continue;}
		if(strpos($line,"Oracle Corporation")>0){continue;}
		if(strpos($line,"rights reserved.")>0){continue;}
		if(trim($line)==null){continue;}
		if(preg_match("#Context:\s+#",$line)){continue;}
		if(preg_match("#Details:\s+#",$line)){continue;}
		$returned[]=$line;
	}
	
	return $returned;
}

function virtualbox_install(){
	@unlink("/usr/share/artica-postfix/ressources/logs/vdi-install.dbg");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/vdi-install.dbg","{scheduled}");
	@chmod("/usr/share/artica-postfix/ressources/logs/vdi-install.dbg",777);
	NOHUP_EXEC("/usr/share/artica-postfix/bin/setup-ubuntu --check-virtualbox >>/usr/share/artica-postfix/ressources/logs/vdi-install.dbg 2>&1");
	
}

function getDefaultGateway(){
	$unix=new unix();
	$ipbin=$unix->find_program("ip");
	if($ipbin==null){return;}
	exec("$ipbin route 2>&1",$results);
	foreach ($results as $index=>$ligne){
		if(preg_match("#default via\s+(.+?)\s+dev#",$ligne,$re)){
			echo "<articadatascgi>{$re[1]}</articadatascgi>";
			return;
		}
	}
	
}



function pptpd_ifconfig(){
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	writelogs_framework("$ifconfig -a 2>&1",__FUNCTION__,__FILE__,__LINE__);
	exec("$ifconfig -a 2>&1",$results);
	$array=array();
	foreach ($results as $ligne){
		if(preg_match("#ppp([0-9]+).+?Point-to-Point#",$ligne,$re)){$ppp="ppp$re[1]";continue;}
		if(preg_match("#inet addr:([0-9\.]+)\s+P-t-P:([0-9\.]+)\s+Mask:([0-9\.]+)#",$ligne,$re)){
			writelogs_framework("$ppp {$re[1]} ,{$re[2]},{$re[3]} ",__FUNCTION__,__FILE__,__LINE__);
			$array[$ppp]=array(
				"INET"=>$re[1],
				"REMOTE"=>$re[2],"MASK"=>$re[3]
			);
			continue;
			
		}
		
		
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}

function mailbox_migration_import_file(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.mailbox.migration.php --files");
	
}
function mailbox_migration_start_members(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.mailbox.migration.php --accounts");
}


function SARG_SAVE(){
}
function SARG_EXEC(){
}
function SARG_PASSWORDS(){

}

function DDCLIENT_RESTART(){
	NOHUP_EXEC("/etc/init.d/artica-postfix restart ddclient");
}

function cluebringer_restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup /etc/init.d/artica-postfix restart cluebringer >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function cluebringer_status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --cluebringer --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}
function cluebringer_passwords(){
	NOHUP_EXEC(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.cluebringer.php --passwords");
}

function qos_iptables(){
	//qos-iptables
	$datas=@file_get_contents("/etc/artica-postfix/qos.cmds");
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";	
}
function qos_compile(){
	//qos-compile
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup ". LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.qos.php --build >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}


function clamd_reload(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup /etc/init.d/clamav-daemon reload >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec(trim($cmd));	
}
	
	
function pureftpd_restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup /etc/init.d/artica-postfix restart ftp >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec(trim($cmd));		
	}	
function ORGANISATION_RENAME(){
	$newname=base64_decode($_GET["to"]);
	$oldname=base64_decode($_GET["from"]);
	$cmd= LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.ldap.move-orgs.php \"$newname\" \"$oldname\" 2>&1";
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
	}


function artica_meta_register(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");	
	$cmd="$nohup ". LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.artica.meta.php --register >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function artica_meta_join(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");	
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.artica.meta.php --join >/dev/null";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}
function artica_meta_unjoin(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");	
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.artica.meta.php --unjoin >/dev/null 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}
function artica_meta_push(){
	$key=$_GET["artica-meta-push"];
	@mkdir("/etc/artica-postfix/artica-meta-queue-socks",666,true);
	$file="/etc/artica-postfix/artica-meta-queue-socks/".md5($key).".sock";
	@file_put_contents($file,$key);
	}

function artica_meta_user(){
	$uid=$_GET["artica-meta-user"];
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.artica.meta.users.php --user \"$uid\" >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function artica_meta_computer(){
	$uid=$_GET["artica-meta-computer"];
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.artica.meta.users.php --computer \"$uid\" >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}
function artica_meta_fetchmail_rules(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.artica.meta.users.php --export-fetchmail-rules >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function artica_meta_ovpn(){
	$uid=$_GET["uid"];
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.artica.meta.users.php --ovpn \"$uid\"";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	NOHUP_EXEC($cmd);	
}

function VboxPid(){
	$unix=new unix();
	$pid=$unix->PIDOF_PATTERN($_GET["VboxPid"]);
	if($pid>0){
		$array["PID"]=$pid;
		$array["INFOS"]="[APP_VIRTUALBOX]\n".$unix->GetSingleMemoryOf($pid);
		
		
		
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}


function artica_meta_user_export_dns(){
	$ArticaMetaEnabled=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaMetaEnabled"));
	if($ArticaMetaEnabled<>1){return;}
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.artica.meta.users.php --export-all-dns >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function artica_meta_export_awstats(){
	$ArticaMetaEnabled=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaMetaEnabled"));
	if($ArticaMetaEnabled<>1){return;}
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.artica.meta.users.php --export-awstats >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
	
}
function artica_meta_export_openvpn_sites(){
	$ArticaMetaEnabled=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaMetaEnabled"));
	if($ArticaMetaEnabled<>1){return;}
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.artica.meta.users.php --export-openvpn-sites >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
	
}


function GetTotalMemMB(){
	$unix=new unix();
	echo "<articadatascgi>".$unix->TOTAL_MEMORY_MB()."</articadatascgi>";
}



//artica-meta-export-dns





function pureftpd_users(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");	
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.pureftpd.php >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}
function postfix_mem_disk_status(){
	$hostname=$_GET["postfix-mem-disk-status"];
	if($hostname=="master"){
		$directory="/var/spool/postfix";
	}else{
		$directory="/var/spool/postfix-$hostname";
	}
	
	$unix=new unix();
	$mem=$unix->MOUNTED_TMPFS_MEM($directory);
	$TOTAL_MEMORY_MB=$unix->TOTAL_MEMORY_MB();
	$TOTAL_MEMORY_MB_FREE=$unix->TOTAL_MEMORY_MB_USED();
	$array=array("MOUTED"=>$mem,"TOTAL_MEMORY_MB"=>$TOTAL_MEMORY_MB,"TOTAL_MEMORY_MB_FREE"=>$TOTAL_MEMORY_MB_FREE);
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}

function awstats_perform(){
	
}

function WakeOnLan(){
	$wol=new WakeOnLanClass(null);
	$wol->wake(base64_decode($_GET["wake-on-lan"]));
	echo "<articadatascgi>". base64_encode(@implode("\n",$wol->error))."</articadatascgi>";
	
}

function UpdateUtilitySource(){
	echo "<articadatascgi>". @file_get_contents("/opt/kaspersky/UpdateUtility/updater.ini")."</articadatascgi>";
}

function postscreen(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");	
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.maincf.php --postscreen \"{$_GET["hostname"]}\">/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
	
}

function postfix_single_ssl(){
		$unix=new unix();
		$nohup=$unix->find_program("nohup");
		$php5=$unix->find_program("php5");
		$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --ssl >/dev/null 2>&1 &");
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
}

function postfix_single_sasl_mech(){
		$unix=new unix();
		$nohup=$unix->find_program("nohup");
		$php5=$unix->find_program("php5");
		
		$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/SMTP_SASL_PROGRESS";
		$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/SMTP_SASL_PROGRESS.txt";
		@unlink($GLOBALS["CACHEFILE"]);
		@unlink($GLOBALS["LOGSFILES"]);
		@touch($GLOBALS["CACHEFILE"]);
		@touch($GLOBALS["LOGSFILES"]);
		@chmod($GLOBALS["CACHEFILE"],0777);
		@chmod($GLOBALS["LOGSFILES"],0777);
		
		$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --smtp-sasl >{$GLOBALS["LOGSFILES"]} 2>&1 &");
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
}

function postfix_postfinger(){
	exec("/usr/share/artica-postfix/bin/postfinger --nowarn 2>&1",$resuts);
	echo "<articadatascgi>". base64_encode(serialize($resuts))."</articadatascgi>";
}

function postfix_throttle(){
	$instance=$_GET["instance"];
	if($instance=="master"){
		$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.maincf.php --ssl";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		NOHUP_EXEC($cmd);
		
		$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.hashtables.php --transport";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		NOHUP_EXEC($cmd);		
		return;
	}
	
	
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"$instance\"";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	NOHUP_EXEC($cmd);			
}



function postfix_freeze(){

	$instance_id=intval($_GET["instance-id"]);
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/postqueue";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/postqueue.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);


	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.maincf.php --freeze --instance-id=$instance_id";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	NOHUP_EXEC($cmd);

}

function LESSFS_RESTART(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=$nohup.LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.lessfs.php >/dev/null 2>&1 &";
	if(isset($_GET["mount"])){
		$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.lessfs.php --restart";
	}
	
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);			
	
}

function LESSFS_MOUNTS(){
	$unix=new unix();
	$array=$unix->LESSFS_ARRAY();
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	}
	
	
function LESSFS_RESTART_SERVICE(){
	unlink("/usr/share/artica-postfix/ressources/logs/web/LESS_FS_RESTART");
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/LESS_FS_RESTART","scheduled\nPlease Wait....");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/LESS_FS_RESTART",0777);
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.lessfs.php >>/usr/share/artica-postfix/ressources/logs/web/LESS_FS_RESTART 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	NOHUP_EXEC($cmd);		
}




function EnableEmergingThreats(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.emerging.threats.php";
	NOHUP_EXEC($cmd);	
	
}
function EnableEmergingThreatsBuild(){
	if(is_file("/usr/share/artica-postfix/ressources/logs/EnableEmergingThreatsBuild.db")){
		writelogs_framework("ressources/logs/EnableEmergingThreatsBuild.db Alreay exists.",__FUNCTION__,__FILE__);
		@chmod("/usr/share/artica-postfix/ressources/logs/EnableEmergingThreatsBuild.db",0777);
		echo "<articadatascgi>Done</articadatascgi>";
		return;
	}
	$unix=new unix();;
	$ipset=$unix->find_program("ipset");
	if(!is_file("$ipset")){
		echo "<articadatascgi>Fatal ipset, no such file</articadatascgi>";
		return;
	}
	writelogs_framework("$ipset -L botccnet >/etc/artica-postfix/botccnet.list",__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$ipset -L botccnet >/etc/artica-postfix/botccnet.list");
    $tr=explode("\n",@file_get_contents("/etc/artica-postfix/botccnet.list"));
    $conf=array();
	foreach ($tr as $num=>$ligne){
    	if(trim($ligne)==null){continue;}
    	if(preg_match("#(.+?):#",$ligne)){continue;}
    	$conf["THREADS"][]=$ligne;
    }
    
    shell_exec("$ipset --list botcc >/etc/artica-postfix/ccnet.list");
	$tr=explode("\n",@file_get_contents("/etc/artica-postfix/ccnet.list"));
    $conf=array();
	foreach ($tr as $num=>$ligne){
    	if(trim($ligne)==null){continue;}
    	if(preg_match("#(.+?):#",$ligne)){continue;}
    	$conf["THREADS"][]=$ligne;
    }    
    
    $conf["COUNT"]=count($conf["THREADS"]);
    writelogs_framework("Writing ressources/logs/EnableEmergingThreatsBuild.db done.",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/EnableEmergingThreatsBuild.db",serialize($conf));
	@chmod("/usr/share/artica-postfix/ressources/logs/EnableEmergingThreatsBuild.db",0777);
	echo "<articadatascgi>". count($conf["THREADS"]). "</articadatascgi>";
}

function aptcheck(){
	echo "<articadatascgi>". @file_get_contents("/etc/artica-postfix/apt.upgrade.cache") ."</articadatascgi>";
}

function ping(){
	$ip=trim($_GET["ip"]);

	if(!preg_match("#^([0-9\.]+)$#",$ip)){
		echo "<articadatascgi>FALSE</articadatascgi>";
		return false;
	}

	$unix=new unix();
	
	$tmp=$unix->TEMP_DIR();
		if(trim($ip)==null){return false;}
		$ftmp="$tmp/". md5(__FILE__);
		exec("/bin/ping -q -c 1 -s 16 -W1 -Q 0x02 $ip >$ftmp 2>&1");
		$results=explode("\n",@file_get_contents($ftmp) );
		@unlink($ftmp);
		if(!is_array($results)){return false;}
		foreach ($results as $line){
			if(preg_match("#[0-9]+\s+[a-zA-Z]+\s+[a-zA-Z]+,\s+([0-9]+)\s+received#",$line,$re)){
				if($re[1]>0){
					$ping_check=true;
				}else{
					$ping_check=false;
				}
			}
		}
	if ($ping_check){echo "<articadatascgi>TRUE</articadatascgi>";return;}
	echo "<articadatascgi>FALSE</articadatascgi>";
	return true;
}

function net_ads_info(){
	
	if($_GET["reconnect"]=="yes"){shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.samba.php --ads");}
	
	
	$cachefile="/etc/artica-postfix/NetADSInfo.cache";
	$cachefilesize=filesize($cachefile);
	writelogs_framework("$cachefile $cachefilesize",__FUNCTION__,__FILE__,__LINE__);
	
	
	if(is_file("/etc/artica-postfix/NetADSInfo.cache")){
		$filetime=file_time_min($cachefile);
		if($filetime<30){
			writelogs_framework("$cachefile {$filetime}Mn",__FUNCTION__,__FILE__,__LINE__);
			$results=explode("\n",@file_get_contents($cachefile));
			}
	}
	
	writelogs_framework("results= ".count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	if(!is_array($results)){
		$unix=new unix();
		$net=$unix->LOCATE_NET_BIN_PATH();
		if(!is_file($net)){$unix->send_email_events("Unable to locate net binary !!","","system");return;}
	 	writelogs_framework("$net ads info 2>&1",__FUNCTION__,__FILE__,__LINE__);
		exec("$net ads info 2>&1",$results);
		@file_put_contents($cachefile,@implode("\n",$results));
	}
		
	foreach ($results as $index=>$line){
		if(preg_match("#^(.+?):(.+)#",trim($line),$re)){
			writelogs_framework(trim($re[1])."=".trim($re[2]),__FUNCTION__,__FILE__,__LINE__);
			$array[trim($re[1])]=trim($re[2]);
		}
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}

function net_ads_leave(){
	$cachefile="/etc/artica-postfix/NetADSInfo.cache";
	@unlink($cachefile);
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.samba.php --ads-destroy 2>&1";
	exec($cmd,$results);
	echo "<articadatascgi>". @implode("\n",$results)."</articadatascgi>";	
}

function process1_force(){
	$unix=new unix();
	$unix->Process1(true);
}
function saslauthd_restart(){
	$unix=new unix();
	$unix->THREAD_COMMAND_SET("/etc/init.d/artica-postfix restart saslauthd");
}
function cyrus_sync_to_ad(){
	$unix=new unix();
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.cyrus-restore.php --ad-sync --force";
	$unix->THREAD_COMMAND_SET($cmd);
}

function right_status(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim($nohup." ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.admin.status.postfix.flow.php --status-right >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
}



function postfix_iptables_compile(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.postfix.iptables.php --compile");	
	$unix->THREAD_COMMAND_SET($cmd);
	
}
function iptables_nginx_compile(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.postfix.iptables.php --nginx >/dev/null 2>&1 &");
	shell_exec($cmd);

}



function clamd_pattern_status(){
	$cmd="/usr/share/artica-postfix/bin/artica-install --avpattern-status 2>&1";
	exec($cmd,$results);
	echo "<articadatascgi>". @implode("\n",$results)."</articadatascgi>";		
}


	
function SpamAssDBVer(){
	$path="/usr/share/artica-postfix/ressources/logs/sa.update.dbg";
	if(!is_file($path)){echo "<articadatascgi>00000</articadatascgi>";return "00000";}
	$f=explode("\n",@file_get_contents($path));
	foreach ( $f as $index=>$line ){if(preg_match("#metadata version.+?([0-9]+)#",$line,$re)){$ptemp=$re[1];break;}}
	echo "<articadatascgi>$ptemp</articadatascgi>";
}

function samba_server_role(){
	$unix=new unix();
	$testparm=$unix->find_program("testparm");
	if(!is_file($testparm)){
		writelogs_framework("testparm no such file ",__FUNCTION__,__FILE__,__LINE__);
		return null;
	}
	exec("$testparm -l -s /etc/samba/smb.conf 2>&1",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#Server role:\s+([A-Z\_]+)#",$line,$re)){
			echo "<articadatascgi>{$re[1]}</articadatascgi>";
			return;
		}
	}
	
}



function maillog_query(){
	$unix=new unix();
	$head=$unix->find_program("head");
	$grep=$unix->find_program("grep");
	$cat=$unix->find_program("cat");
	$tail=$unix->find_program("tail");
	$pattern=$_GET["maillog-query"];
	$path=$_GET["maillog-path"];
	if(strpos($pattern,"*")){
		$pattern=str_replace("*",'.*?',$pattern);
		$e=" -E ";
	}
	
	if($pattern<>null){
		$cmd="$cat $path|$grep$e \"$pattern\"|$head -n 300";
	}else{
		$cmd="$tail -n 300 $path";
	}
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec("$cmd 2>&1",$results);
	echo "<articadatascgi>". @implode("\n",$results)."</articadatascgi>";
}

function rbl_check(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.my-rbl.check.php --query {$_GET["rbl-check"]} 2>&1";
	exec($cmd,$results);
	echo "<articadatascgi>". trim(@implode(" ",$results))."</articadatascgi>";		
}

function my_rbl_check(){
	$et=" &";
	$verbose=" --verbose";
	if(isset($_GET["force"])){
		$force=" --force";
		$et=null;
		$verbose=null;
	}
	
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.my-rbl.check.php --query {$_GET["rbl-check"]}$force";
	$cmd=$cmd." --checks$verbose 2>&1$et";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$cmd");
}

function ChangeMysqlParams(){
	$basePath="/etc/artica-postfix/settings/Mysql";
	$arrayMysqlinfos=unserialize(base64_decode($_GET["change-mysql-params"]));
	$user=$arrayMysqlinfos["USER"];
	$password=trim($arrayMysqlinfos["PASSWORD"]);
	$server=$arrayMysqlinfos["SERVER"];
	writelogs_framework("Change mysql parameters to $user:$password@$server",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("$basePath/database_admin",$user);
	if($password==null){@unlink("$basePath/database_password");}else{@file_put_contents("$basePath/database_password",$password);}
	@file_put_contents("$basePath/mysql_server",$server);
	shell_exec("/usr/bin/php /usr/share/artica-postfix/exec.status.php --process1 --force ".time());
	$unix=new unix();
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.www.install.php";	
	$unix->THREAD_COMMAND_SET("/etc/init.d/roundcube restart");	
	$unix->THREAD_COMMAND_SET($cmd);
}

function VIPTrackRun(){
	$unix=new unix();
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.vip.php --reports";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	$unix->THREAD_COMMAND_SET($cmd);	
}


function postfix_whitelisted_global(){
	$unix=new unix();
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.spamassassin.php --whitelist";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	$unix->THREAD_COMMAND_SET($cmd);
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.milter-greylist.php";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	$unix->THREAD_COMMAND_SET($cmd);

	
	
}
function cyrus_db_config(){
	$unix=new unix();
	$cmd="/usr/share/artica-postfix/bin/artica-install --cyrus-db_config";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	$unix->THREAD_COMMAND_SET($cmd);
}

function winbindd_stop(){
	$unix=new unix();
	$cmd="/etc/init.d/artica-postfix stop winbindd";
	$unix->THREAD_COMMAND_SET($cmd);	
}

function myisamchk(){
	$db=$_GET["database"];
	$table=$_GET["table"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	exec("$nohup $php5 /usr/share/artica-postfix/exec.myisamchk.php $db $table >/dev/null 2>&1 &");
	return;
	
}

function mysql_myd_file(){
	$db=$_GET["database"];
	$table=$_GET["table"];
	$unix=new unix();
	$MYSQL_DATADIR=$unix->MYSQL_DATADIR();
	if(!is_file("$MYSQL_DATADIR/$db/$table.MDY")){
		echo "<articadatascgi>NO</articadatascgi>";
		return;
	}else{
		echo "<articadatascgi>YES</articadatascgi>";
	}
	
}

function mysql_check(){
	$db=$_GET["database"];
	$table=$_GET["table"];	
	$instance_id=$_GET["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.mysql.build.php --mysqlcheck $db $table $instance_id";	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	$unix=new unix();
	$unix->THREAD_COMMAND_SET($cmd);		
}

function SetServerTime(){
	$time=$_GET["SetServerTime"];
	$unix=new unix();
	$bin_date=$unix->find_program("date");
	$cmd="$bin_date $time 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". @implode("\n",$results)."</articadatascgi>";
}

function syslog_master_mode(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim($nohup." ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.syslog-engine.php --build-server >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function syslog_client_mode(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim($nohup." ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.syslog-engine.php --build-client >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}



function GREYHOLE_RESTART(){
	$unix=new unix();
	$cmd="/etc/init.d/artica-postfix restart greyhole";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	$unix->THREAD_COMMAND_SET($cmd);		
}

function GREYHOLE_DAILY_FCK(){
	$unix=new unix();
	$cmd="/usr/bin/greyhole --fsck --if-conf-changed --dont-walk-graveyard > /dev/null";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	$unix->THREAD_COMMAND_SET($cmd);		
}



function IsUDPport(){
	$unix=new unix();
	$host=$_GET["host"];
	$port=$_GET["port"];
	$nc=$unix->find_program("nc");
	writelogs_framework("nc= `$nc`",__FUNCTION__,__FILE__,__LINE__);
	if(!is_file($nc)){
		writelogs_framework("nc , no such binary",__FUNCTION__,__FILE__,__LINE__);
		echo "<articadatascgi>UNKNOWN</articadatascgi>";
		return;
	}
	$cmd="$nc -zuv $host $port 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	foreach ($results as $index=>$line){
		if(preg_match("#refused#",$line)){
			echo "<articadatascgi>FAILED</articadatascgi>";
			return;
		}
		
		if(preg_match("#open#",$line)){
			echo "<articadatascgi>OK</articadatascgi>";
			return;
		}		
		
	}
	
	echo "<articadatascgi>UNKNOWN</articadatascgi>";
	
	
}
function copyresolv(){
	$unix=new unix();
	$cp=$unix->find_program("cp");
	$chmod=$unix->find_program("chmod");
	$chown=$unix->find_program("chown");
	$copyresolv=$_GET["copyresolv"];
	if(is_file($copyresolv)){
		writelogs_framework("$copyresolv  -> /etc/resolv.conf",__FUNCTION__,__FILE__,__LINE__);
		shell_exec("$cp -f $copyresolv /etc/resolv.conf");
		shell_exec("$cp -f $copyresolv /etc/dnsmasq.resolv.conf");
		shell_exec("$chmod 0644 /etc/resolv.conf");
		shell_exec("$chown root:root /etc/resolv.conf");
	}else{
		writelogs_framework("$copyresolv no such file",__FUNCTION__,__FILE__,__LINE__);
	}
}




function postfinder(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim($nohup." ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.finder.php >/dev/null 2>&1 &");
	shell_exec($cmd);
}

function IP_DEL_ROUTE(){
	@unlink("/etc/artica-postfix/MEM_INTERFACES");
	$cmd=trim(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.virtuals-ip.php --routes-del {$_GET["ip-del-route"]}");
	shell_exec($cmd);
}
function IP_ROUTES(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	@unlink("/etc/artica-postfix/MEM_INTERFACES");
	$cmd=trim($nohup." ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.virtuals-ip.php --routes >/dev/null 2>&1 &");
	shell_exec($cmd);
	$cmd=trim($nohup." ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.virtuals-ip.php --main-routes >/dev/null 2>&1 &");
	shell_exec($cmd);	
}

function disks_quotas_list(){
	$unix=new unix();
	echo "<articadatascgi>". base64_encode(serialize($unix->GET_QUOTA_MOUNTED()))."</articadatascgi>";
}

function quotastats(){
	$unix=new unix();
	$quotastats=$unix->find_program("quotastats");
	if(!is_file($quotastats)){return;}
	exec("$quotastats 2>&1",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#(.+?):(.+)#",$line,$re)){
			$array[trim($re[1])]=trim($re[2]);
		}
	}
	
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}

function repquota(){
	$mount=$_GET["mount"];
	$unix=new unix();
	$repquota=$unix->find_program("repquota");	
	if(!is_file($repquota)){writelogs_framework("repquota no such file",__FUNCTION__,__FILE__,__LINE__);return;}
	$array=array();
	exec("$repquota \"$mount\" 2>&1",$results);
	writelogs_framework("$repquota $mount 2>&1 = ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	foreach ($results as $index=>$line){
		if(preg_match("#Block grace time.+?\s+([0-9]+)days;\s+Inode grace time.+?\s+([0-9]+)days#",$line,$re)){
			$array["GRACES"]["Block"]=$re[1];$array["GRACES"]["Inode"]=$re[2];continue;}
		if(preg_match("#^(.+?)\s+(.+?)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+(.*?)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)#",$line,$re)){
			$uid=$re[1];
			$array["USERS"]["user:$uid"]=array(
				"STATUS"=>$re[2],
				"BLOCK_USED"=>$re[3],
				"BLOCK_SOFT"=>$re[4],
				"BLOCK_HARD"=>$re[5],
				"BLOCK_GRACE"=>$re[6],
				
				"FILE_USED"=>$re[7],
				"FILE_SOFT"=>$re[8],
				"FILE_HARD"=>$re[9],
				"FILE_GRACE"=>$re[10],			
				
			
			);
			
			continue;
		}
		
		if(preg_match("#^(.+?)\s+(.+?)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+(.*?)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+(.*)#",$line,$re)){
			$uid=$re[1];
			$array["USERS"][$uid]=array(
				"STATUS"=>$re[2],
				"BLOCK_USED"=>$re[3],
				"BLOCK_SOFT"=>$re[4],
				"BLOCK_HARD"=>$re[5],
				"BLOCK_GRACE"=>$re[6],
				
				"FILE_USED"=>$re[7],
				"FILE_SOFT"=>$re[8],
				"FILE_HARD"=>$re[9],
				"FILE_GRACE"=>$re[10],			
				
			
			);
			
			continue;
		}		
		
	}
	
	exec("$repquota -g \"$mount\" 2>&1",$results);
	writelogs_framework("$repquota $mount 2>&1 = ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	foreach ($results as $index=>$line){
		if(preg_match("#Block grace time.+?\s+([0-9]+)days;\s+Inode grace time.+?\s+([0-9]+)days#",$line,$re)){
			$array["GRACES"]["Block"]=$re[1];$array["GRACES"]["Inode"]=$re[2];continue;}
		if(preg_match("#^(.+?)\s+([\-\+]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+(.*?)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)#",$line,$re)){
			$uid=$re[1];
			$array["USERS"]["group:$uid"]=array(
				"STATUS"=>$re[2],
				"BLOCK_USED"=>$re[3],
				"BLOCK_SOFT"=>$re[4],
				"BLOCK_HARD"=>$re[5],
				"BLOCK_GRACE"=>$re[6],
				
				"FILE_USED"=>$re[7],
				"FILE_SOFT"=>$re[8],
				"FILE_HARD"=>$re[9],
				"FILE_GRACE"=>$re[10],			
				
			
			);
			
			continue;
		}
		
		if(preg_match("#^(.+?)\s+(.+?)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+(.*?)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+(.*)#",$line,$re)){
			$uid=$re[1];
			$array["USERS"][$uid]=array(
				"STATUS"=>$re[2],
				"BLOCK_USED"=>$re[3],
				"BLOCK_SOFT"=>$re[4],
				"BLOCK_HARD"=>$re[5],
				"BLOCK_GRACE"=>$re[6],
				
				"FILE_USED"=>$re[7],
				"FILE_SOFT"=>$re[8],
				"FILE_HARD"=>$re[9],
				"FILE_GRACE"=>$re[10],			
				
			
			);
			
			continue;
		}		
		
	}	
	
	
	
	
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}

function setquota(){
	
	writelogs_framework("Set quota for \"{$_GET["u"]}\" {$_GET["b"]} {$_GET["bh"]} {$_GET["f"]} {$_GET["fh"]}",__FUNCTION__,__FILE__,__LINE__);	
	
	if(!preg_match("#(.+?):(.+)#",$_GET["u"],$re)){
		writelogs_framework("Unable to preg_match \"{$_GET["u"]}\"",__FUNCTION__,__FILE__,__LINE__);	
		return;}
	$mount=$_GET["mount"];
	$unix=new unix();
	$results2=array();
	$setquota=$unix->find_program("setquota");		
	if(!is_file($setquota)){return;}
	if($re[1]=="user"){$prefix=" -u {$re[2]}";}
	if($re[1]=="group"){$prefix=" -g {$re[2]}";}
	

	$cmd="$setquota $prefix {$_GET["b"]} {$_GET["bh"]} {$_GET["f"]} {$_GET["fh"]} $mount 2>&1";
	
	exec($cmd,$results);
	writelogs_framework("$cmd = ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	
	$cmd="$setquota $prefix -T {$_GET["bg"]} {$_GET["fg"]} \"$mount\" 2>&1";
	
	
	exec($cmd,$results2);
	writelogs_framework("$cmd = ". count($results2)." rows",__FUNCTION__,__FILE__,__LINE__);

	$quotaon=$unix->find_program("quotaon");
	$quotaoff=$unix->find_program("quotaoff");
	$nohup=$unix->find_program("nohup");

	shell_exec(trim("$nohup $quotaoff \"$mount\" && $quotaon \"$mount\" >/dev/null 2>&1 &"));
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	
		
}

function quotasrecheck(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim($nohup." ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.samba.php --quotas-recheck >/dev/null 2>&1 &");
	shell_exec($cmd);	
}
function IpTables_delete_all_rules(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim($nohup." ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.iptables.php --delete-all-iptables >/dev/null 2>&1 &");
	shell_exec($cmd);		
}

function IpTables_WhiteListResolvMX(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim($nohup." ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.iptables.php --transfert-white >/dev/null 2>&1 &");
	shell_exec($cmd);	
}


function DNSMASQ_LOAD_CONF(){
	$datas=@file_get_contents("/etc/dnsmasq.conf");
	echo "<articadatascgi>". base64_encode($datas)."</articadatascgi>";
}






function iscsi_restart(){
	$unix=new unix();
	$unix->THREAD_COMMAND_SET("/etc/init.d/artica-postfix restart iscsi");	
}
function iscsi_reload(){iscsi_restart();}
function iscsi_client(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim($nohup." ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.iscsi.php --clients >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);		
}


function udevinfos(){
	$dev=$_GET["dev"];
	$unix=new unix();
	$udevinfo=$unix->find_program("udevinfo");
	$udevadm=$unix->find_program("udevadm");
	if(is_file($udevinfo)){$cmd="$udevinfo -q all -n $dev 2>&1";}
	if(is_file($udevadm)){$cmd="udevadm info --query=all --path=`/sbin/udevadm info --query=path --name=$dev` 2>&1";}
	exec($cmd,$results);
	writelogs_framework("$cmd = ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	$array=array();
	foreach ($results as $index=>$line){
		if(preg_match("#E:\s+(.+?)=(.+)#",$line,$re)){
			$array[trim($re[1])]=trim($re[2]);
		}
	}	
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}
function disks_dfmoinshdev(){
	$dev=$_GET["dfmoinshdev"];
	$unix=new unix();
	$df=$unix->find_program("df");	
	$cmd="$df -h $dev 2>&1";
	exec($cmd,$results);
	writelogs_framework("$cmd = ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>". @implode("\n",$results)."</articadatascgi>";
}


function unix_groups(){
	$results=explode("\n",@file_get_contents("/etc/group"));
	$gp=array();
	foreach ($results as $index=>$line){
		if(preg_match("#(.+?):.+?:([0-9]+):(.*?)#",$line,$re)){
			$gp[$re[2]]["NAME"]=$re[1];
			if($re[3]<>null){
				$gp[$re[2]]["MEMBERS"][]=$re[3];
			}
		}else{
			writelogs_framework("$line no match #(.+?):x:([0-9]+):(.*?)#",__FUNCTION__,__FILE__,__LINE__);
		}
		
	}
	
	echo "<articadatascgi>". base64_encode(serialize($gp))."</articadatascgi>";
}

function chmod_access(){
	$unix=new unix();
	$stat=$unix->find_program("stat");
	$_GET["chmod-access"]=$unix->shellEscapeChars($_GET["chmod-access"]);
	$cmd="$stat {$_GET["chmod-access"]} 2>&1";
	exec($cmd,$results);
	writelogs_framework("$cmd = ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	foreach ($results as $index=>$line){
		if(preg_match("#Access:.+?\(([0-9]+)\/(.+?)\)\s+Uid:\s+\(.+?\/(.+?)\)\s+Gid:\s+\(.+?\/(.+?)\)#i",$line,$re)){
			writelogs_framework("$line MATCH",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>". base64_encode(serialize($re))."</articadatascgi>";
		}
	}
	
}

function acls_status(){
	$unix=new unix();
	$stat=$unix->find_program("stat");	
	$getfacl=$unix->find_program("getfacl");	
	$dir=$unix->shellEscapeChars($_GET["acls-status"]);
	$cmd="$stat $dir 2>&1";
	exec($cmd,$results);
	writelogs_framework("$cmd = ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	if(is_file($getfacl)){
	$cmd="$getfacl $dir 2>&1";
	$results[]="";
	$results[]="#HR#";
	exec($cmd,$results);	
	writelogs_framework("$cmd = ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	}
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	
}

function acls_apply(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim($nohup. " ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.acls.php --acls-single {$_GET["acls-apply"]} >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function acls_delete(){
	$unix=new unix();
	$setfacl=$unix->find_program("setfacl");	
	$dir=$unix->shellEscapeChars($_GET["acls-delete"]);	
	$cmd="$setfacl -b $dir 2>&1";
	exec("$cmd",$events);	
}
function acls_rebuild(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim($nohup. " ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.acls.php --acls");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function ufdbguard_compile_missing_dbs(){
	@file_put_contents("/etc/artica-postfix/ufdbguard.compile.missing.alldbs","#");
		
	
}
function ufdbguard_compile_all_dbs(){
	
	@file_put_contents("/etc/artica-postfix/ufdbguard.compile.alldbs","#");
	
}
function ufdbguard_compile_schedule(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=$nohup." ".LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.squidguard.php --ufdbguard-schedule >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function ufdbguard_compilator_events(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$cmd="$tail -n 300 {$GLOBALS["ARTICALOGDIR"]}/ufdbguard-compilator.debug";
	exec($cmd,$results);
	writelogs_framework("$cmd=".count($results),__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}

function snort_networks(){
	$cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.snort.php --networks";
	shell_exec($cmd);
}

function restart_snort(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim($nohup." /etc/init.d/artica-postfix restart snort >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);

	$cmd=trim($nohup." /etc/init.d/artica-postfix restart fcron >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	
	
}

function WriteToSyslog($text,$file,$error=false){
	$file=basename($file);
	if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
	if(function_exists("openlog")){openlog($file, LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
	}
	
function NOHUP_EXEC($cmdline){
	$cmdline=str_replace(">/dev/null 2>&1 &", "", $cmdline);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmdfinal="$nohup $cmdline >/dev/null 2>&1 &";
	writelogs_framework("$cmdfinal",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmdfinal);
	
}




?>
