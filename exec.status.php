<?php
if (!defined("ARTICA_ROOT")) {define('ARTICA_ROOT', "/usr/share/artica-postfix");}
define("OUTCLOSE", ">/dev/null 2>&1 &");
ini_set('error_reporting', E_ALL);
if (function_exists("posix_getuid")) {if (posix_getuid() <> 0) {die("Cannot be used in web server mode\n\n");}}
$GLOBALS["YESCGROUP"]=true;
$GLOBALS["JSON"] = false;
$GLOBALS["FORCE"] = false;
$GLOBALS["EXECUTED_AS_ROOT"] = true;
$GLOBALS["RUN_AS_DAEMON"] = false;
$GLOBALS["VERBOSE"] = false;
$GLOBALS["AS_ROOT"] = true;
$GLOBALS["DISABLE_WATCHDOG"] = false;
$GLOBALS["BASE_ROOT"] = dirname(__FILE__);
$GLOBALS["NOSTATUSTIME"] = false;
$GLOBALS["COMMANDLINE"] = implode(" ", $argv);
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"] = new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
register_shutdown_function('shutdown');
CheckGLOBALS();
if (!defined("PHPART")) {
    $cmdprf = "{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} {$GLOBALS["BASE_ROOT"]}";
    define("PHPART", $cmdprf);
}
if ($GLOBALS["VERBOSE"]) {
    echo "START [" . __LINE__ . "]\n";
}
if ($GLOBALS["VERBOSE"]) {echo "LoadIncludes();\n";}
LoadIncludes();
if ($GLOBALS["VERBOSE"]) {echo "LoadIncludes() DONE;\n";}
$GLOBALS["CLASS_UNIX"] = new unix();

if (is_file("/etc/artica-postfix/FROM_ISO")) {
    if ($GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/FROM_ISO") < 1) {
        exit();
    }
}
if (is_file("/var/log/artica-status.log")) {
    $size=@filesize("/var/log/artica-status.log");
    if($size>100000000){@unlink("/var/log/artica-status.log");}
}
if ($GLOBALS["VERBOSE"]) { echo "DEBUG MODE ENABLED\n"; }
if ($GLOBALS["VERBOSE"]) {echo "command line: {$GLOBALS["COMMANDLINE"]}\n"; }
$GLOBALS["TOTAL_MEMORY_MB"] = $GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();


if (isset($argv[1])) {
    if (strlen($argv[1]) > 0) {
        events("parsing command line " . @implode(";", $argv), "MAIN", __LINE__);
        CheckCallable();
    }

    $MDIR = dirname(__FILE__);
    $RDIR = "$MDIR/ressources";
    $mem = round(((memory_get_usage() / 1024) / 1000), 2);
    events("{$mem}MB after declarations", "MAIN", __LINE__);

    if (strlen($argv[1]) > 2) {
        events("parsing command line {$argv[1]}", "MAIN", __LINE__);
    }
    if ($argv[1] == "--nice") {
        echo "{$GLOBALS["NICE"]}\n";
        exit;
    }
    if ($argv[1] == "--reboot") {
        echo reboot();
        exit;
    }
    if ($argv[1] == "--zabbix") {
        exit;
    }
    if ($argv[1] == "--webconsole") {
        exit;
    }

    if ($argv[1] == "--statscom") {
        exit;
    }

    if ($argv[1] == "--urbackup-server") {
        echo APP_URBACKUP();
        exit;
    }

    if ($argv[1] == "--categories-cache") {
        echo CIESCACHE_STATUS();
        exit;
    }
    if ($argv[1] == "--all") {
        if(!is_dir("/etc/artica-postfix/pids.3")){@mkdir("/etc/artica-postfix/pids.3",0755,true);}
        $pidfile="/etc/artica-postfix/pids.3/".basename(__FILE__).".all.pid";
        $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
        if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){ die(); }
        @file_put_contents($pidfile,getmypid());

        $GLOBALS["YESCGROUP"] = true;
        include_once("$MDIR/framework/class.unix.inc");
        if (function_exists("xcgroups")) {
            xcgroups();
        }
        events("-> launch_all_status_cmdline()", "MAIN", __LINE__);
        $GLOBALS["NOSTATUSTIME"] = true;
        Scheduler();
        launch_all_status_cmdline();
        if ($GLOBALS["VERBOSE"]) {
            echo "DONE\n\n\n";
        }
        exit();
    }

    if ($argv[1] == "--quagga") {
        echo OSPF_STATUS() . "\n" . ZEBRA_STATUS();
        exit;
    }
    if ($argv[1] == "--vasd") {
        echo VASD_STATUS();
        exit;
    }


    if ($argv[1] == "--free") {
        echo getmem();
        exit;
    }
    if ($argv[1] == "--procs") {
        $PROCESSES_CLASS = new processes_php();
        $PROCESSES_CLASS->MemoryInstances();
        exit();
    }
    if ($argv[1] == "--ss5") {
        echo "\n" . ss5();
        exit;
    }
    if($argv[1]=="--hypercache"){
        HypercacheStatus();
        exit;
    }

    if ($argv[1] == "--squid") {
        echo squid_master_status();
        echo "\n";
        exit;
    }


    if ($argv[1] == "--watchdog-me") {
        include_once("$RDIR/class.status.watchdog.me.inc");
        watchdog_me();
        exit;
    }

    if ($argv[1] == "--manticore") {
        include_once("$RDIR/class.status.manticore.inc");
        try {
            echo MANTICORE_STATUS()."\n";
        } catch (Exception $e) {
            if($GLOBALS["VERBOSE"]){
                echo "Fatal while running function MANTICORE_STATUS ($e) L.".__LINE__."\n";
            }
            ToSyslog("Fatal while running function MANTICORE_STATUS ($e)");
        }
    }


    if ($argv[1] == "--ad-rest") {
        exit;
    }
    if ($argv[1] == "--prads") {
        echo prads() . "\n";
        exit;
    }
    if ($argv[1] == "--klnagent") {
        echo klnagent() . "\n";
        exit;
    }
    if ($argv[1] == "--splunk") {
        echo splunk_forwarder() . "\n";
        exit;
    }
    if ($argv[1] == "--dnscache") {
        exit;
    }
    if ($argv[1] == "--wsus") {
        echo WSUS_HTTP() . "\n";
        exit;
    }
    if ($argv[1] == "--defaults") {
        $GLOBALS["VERBOSE"] = true;
        Build_default_values();
        exit;
    }
    if ($argv[1] == "--netdata") {
        echo netdata();
        exit;
    }
    if ($argv[1] == "--fsm") {
        echo APP_ARTICAFSMON();
        exit;
    }

    if ($argv[1] == "--sealion") {
        echo sealion_agent();
        exit;
    }
    if ($argv[1] == "--freshclam") {
        exit;
    }

    if($argv[1]=="--apt-mirror"){
        echo APT_MIRROR();APT_MIRROR_WEB();
        exit;
    }

    if ($argv[1] == "--c-icap") {
        exit;
    }

    if ($argv[1] == "--wifi") {
        echo wpa_supplicant();
        exit;
    }
    if ($argv[1] == "--fetchmail") {
        echo fetchmail();
        exit;
    }
    if ($argv[1] == "--milter-greylist") {
        echo milter_greylist() . "\n";
        exit;
    }
    if ($argv[1] == "--framework") {
        echo framework();
        exit;
    }
    if ($argv[1] == "--glances") {
        echo glances();
        exit;
    }
    if ($argv[1] == "--dnsfilterd") {
        include_once("$RDIR/class.status.dnsfilterd.inc");
        echo dnsfilterd_status();
        exit;
    }


    
    if ($argv[1] == "--smokeping") {
        include_once("$RDIR/class.status.smokeping.inc");
        echo smokeping();
        exit;
    }

    if ($argv[1] == "--patchs-backup") {
        exit;
    }
    if($argv[1]=="--hotspot-web"){
        include_once("$RDIR/class.status.hotspot.inc");
        echo HOTSPOT_STATUS();
        exit;
    }


    if($argv[1]=="--wazhu-client"){
        include_once("$RDIR/class.status.wazhu.agent.inc");
        echo APP_WHAZU_AGENT();
        exit;
    }

    if ($argv[1] == "--pdns") {
        $results = array();
        $array = pdns_increment_func(array());
        foreach ($array as $num => $func) {
            try {
                if ($GLOBALS["VERBOSE"]) {
                    echo "***** $func *****\n";
                }
                $results[] = call_user_func($func);
            } catch (Exception $e) {
                ToSyslog("Fatal while running function $func ($e)");
            }
        }
        echo @implode("\n", $results);
        @file_put_contents("$RDIR/logs/all.pdns.status", @implode("\n", $results));
        exit();

    }

    if ($argv[1] == "--dsc") {
        echo pdns_stats();
        exit;
    }
    if ($argv[1] == "--cyrus-imap") {
        echo cyrus_imap();
        exit;
    }
    if ($argv[1] == "--greensql") {
        include_once("$RDIR/class.status.greensql.inc");
        echo greensql_status();
        exit;
    }
    if ($argv[1] == "--mysql") {
        if($GLOBALS["VERBOSE"]){
            echo "Running MySQL status....\n";
            echo "Includes: $RDIR/class.status.mysql.inc\n";
        }
        include_once("$RDIR/class.status.mysql.inc");
        try{
            echo "\n" . mysql_server() . "\n" . mysql_mgmt() . "\n" . mysql_replica();
        } catch (Exception $e) {
            echo $e->getMessage()."\n";
        }
        exit;
    }

    if ($argv[1] == "--saslauthd") {
        echo "\n" . saslauthd();
        exit;
    }
    if ($argv[1] == "--sysloger") {
        echo "\n" . syslogger();
        exit;
    }
    if ($argv[1] == "--xmail") {
        XMail();
        exim4();
        exit;
    }
    if ($argv[1] == "--bwm-ng") {

        exit;
    }

    if ($argv[1] == "--ntopng") {
        echo ntopng() . "\n" . bandwidthd() . "\n" . "\n";
        exit;
    }
    if ($argv[1] == "--load-stats") {
        $GLOBALS["VERBOSE"] = true;
        load_stats();
        exit;
    }
    if ($argv[1] == "--vsftpd") {
        echo vsftpd();
        exit;
    }
    if ($argv[1] == "--unifi") {
        echo unifi_mongodb() . "\n" . unifi();
        exit;
    }
    if ($argv[1] == "--transmission-daemon") {
        echo transmission_daemon() . "\n";
        exit;
    }

    if ($argv[1] == "--fail2ban") {
        include_once(dirname(__FILE__) . '/ressources/class.status.fail2ban.inc');
        echo fail2ban() . "\n";
        exit;
    }
    if ($argv[1] == "--unbound") {
        include_once(dirname(__FILE__) . '/ressources/class.status.unbound.inc');
        echo unbound() . "\n";
        echo pdns_stats() . "\n" . dnscrypt_proxy() . "\n";
        exit;
    }
    if ($argv[1] == "--strongswan") {
        echo ipsec() . "\n";
        exit;
    }
    if ($argv[1] == "--strongswan-vici") {
        echo ipsec_vici() . "\n";
        exit;
    }
    if ($argv[1] == "--strongswan-vici-parser") {
        echo ipsec_vici_parser() . "\n";
        exit;
    }
    //KEEPALIVED
    if ($argv[1] == "--keepalived") {
        echo keepalived() . "\n";
        exit;
    }

    //END KEEPALIVED
    if ($argv[1] == "--squid-tail-size") {
        echo SQUID_TAIL_SIZE() . "\n" . SQUID_TAIL_SIZE_HELPERS() . "\n";
        exit;
    }
    if ($argv[1] == "--udev") {
        echo udevd_daemon_s() . "\n";
        exit;
    }

    if ($argv[1] == "--dockerd") {
        include_once(dirname(__FILE__) . "/ressources/class.status.dockerd.inc");
        echo "\n" . dockerd();
        exit;
    }
    if ($argv[1] == "--rustdesk") {
        include_once(dirname(__FILE__) . "/ressources/class.status.rustdesk.inc");
        echo "\n" . rustdesk();
        exit;
    }

    if ($argv[1] == "--rsyslog") {
        exit;
    }

    if ($argv[1] == "--lighttpd") {
        exit;
    }

    if ($argv[1] == "--clamav") {
        exit;
    }






    if ($argv[1] == "--slapd-version") {
        $GLOBALS["VERBOSE"]=true;
        echo APP_SLAPD_VERSION()."\n";
        exit;
    }
    if ($argv[1] == "--dwagent") {
        include_once('/usr/share/artica-postfix/ressources/class.status.dwagent.inc');
        echo DWAGENT_STATUS();
        exit;
    }




    if ($argv[1] == "--postfix") {
        exit;
    }
    if ($argv[1] == "--postfix-logger") {
        echo "\n" . postfix_logger();
        exit;
    }
    if ($argv[1] == "--mailman") {
        echo "\n" . mailman();
        exit;
    }



    if ($argv[1] == "--cups") {
        echo "\n" . cups();
        exit;
    }

    if ($argv[1] == "--gdm") {
        echo "\n" . gdm();
        exit;
    }


    if ($argv[1] == "--filebeat") {
        include_once(dirname(__FILE__) . "/ressources/class.status.filebeat.inc");
        echo "\n" . _filebeat();
        exit;
    }

    if ($argv[1] == "--hamachi") {
        echo "\n" . hamachi();
        exit;
    }
    if ($argv[1] == "--artica-notifier") {
        echo "\n" . artica_notifier();
        exit;
    }

    if ($argv[1] == "--dhcpd-relay") {
        echo "\n" . dhcp_relay();
        exit;
    }
    if ($argv[1] == "--dhcpd") {
        exit;
    }
    if ($argv[1] == "--pure-ftpd") {
        echo "\n" . pure_ftpd();
        exit;
    }

    if ($argv[1] == "--policydw") {
        echo "\n" . policyd_weight();
        exit;
    }
    if ($argv[1] == "--ocsweb") {
        echo "\n";
        exit;
    }
    if ($argv[1] == "--ocsagent") {
        echo "\n" . ocs_agent();
        exit;
    }
    if ($argv[1] == "--openssh") {
        exit;
    }
    if ($argv[1] == "--sshportal") {
        echo "\n" . sshportal();
        exit;
    }

    if ($argv[1] == "--gluster") {
        echo "\n" . gluster();
        exit;
    }

    if ($argv[1] == "--auditd") {
        echo "\n" . auditd();
        exit;
    }
    if ($argv[1] == "--squidguard-http") {
        echo "\n" . ufdbguard_http();
        exit;
    }
    if ($argv[1] == "--opendkim") {
        echo "\n" . opendkim();
        exit;
    }
    if ($argv[1] == "--ufdbguard-http") {
        echo "\n" . ufdbguard_http();
        exit;
    }
    if ($argv[1] == "--ufdbguardd") {
        $ufdbguardd = ufdbguardd();
        echo $ufdbguardd . "\n";
        $ufdbguard_http = ufdbguard_http();
        echo $ufdbguard_http . "\n";
        $ufdbguardd_tail = ufdbguardd_tail();
        echo $ufdbguardd_tail . "\n";
        exit;
    }
    if ($argv[1] == "--ufdb-tail") {
        echo "\n" . ufdbguardd_tail();
        exit;
    }
    if ($argv[1] == "--squidguard-tail") {
        exit;
    }
    if ($argv[1] == "--dkim-milter") {
        echo "\n" . milter_dkim();
        exit;
    }
    if ($argv[1] == "--dropbox") {
        echo "\n" . dropbox();
        exit;
    }
    if ($argv[1] == "--artica-policy") {exit;}

    if ($argv[1] == "--tftpd") {
        echo "\n" . tftpd();
        exit;
    }
    if ($argv[1] == "--vdi") {
        exit;
    }
    if ($argv[1] == "--artica-background") {
        echo "\n";
        exit;
    }
    if ($argv[1] == "--pptpd") {
        echo "\n" . pptpd();
        exit;
    }
    if ($argv[1] == "--pptpd-clients") {
        echo "\n" . pptp_clients();
        exit;
    }
    if ($argv[1] == "--lsm") {
        echo "\n" . lsm();
        exit;
    }


    if ($argv[1] == "--squidclamav-tail") {
        echo "\n";
        exit;
    }



    if ($argv[1] == "--ddclient") {
        echo "\n" . ddclient();
        exit;
    }
    if ($argv[1] == "--cluebringer") {
        echo "\n" . cluebringer();
        exit;
    }

    if ($argv[1] == "--freewebs") {
        echo "\n" . pure_ftpd() . "\n" . php_fpm() . "\n" . nginx() ;
        exit;
    }
    if ($argv[1] == "--openvpn") {
        echo openvpnserver() . "\n";
        exit;
    }
    if ($argv[1] == "--vboxguest") {
        echo "\n" . vboxguest();
        exit;
    }
    if ($argv[1] == "--sabnzbdplus") {
        echo "\n" . sabnzbdplus();
        exit;
    }
    if ($argv[1] == "--openvpn-clients") {
        echo "\n" . OpenVPNClientsStatus();
        exit;
    }
    if ($argv[1] == "--stunnel") {
        echo "\n" . stunnel();
        exit;
    }
    if ($argv[1] == "--smbd") {
        echo "\n" . smbd();
        exit;
    }

    if ($argv[1] == "--munin") {
        echo "\n" . munin();
        exit;
    }

    if ($argv[1] == "--greyhole") {
        echo "\n" . greyhole();
        exit;
    }
    if ($argv[1] == "--amavis-watchdog") {
        echo "\n";
        exit;
    }
    if ($argv[1] == "--iscsi") {
        echo "\n" . iscsi();
        exit;
    }
    if ($argv[1] == "--watchdog-service") {
        echo "\n" . WATCHDOG($argv[2], $argv[3]);
        exit;
    }
    if ($argv[1] == "--smartd") {
        echo "\n" . smartd();
        exit;
    }
    if ($argv[1] == "--watchdog-me") {
        include_once(dirname(__FILE__) . "/ressources/class.status.watchdog.me.inc");
        watchdog_me();
        exit();
    }
    if ($argv[1] == "--auth-tail") {exit;}

    if ($argv[1] == "--xload") {
        exit;
    }
    if ($argv[1] == "--greyhole-watchdog") {
        greyhole_watchdog();
        exit;
    }
    if ($argv[1] == "--cgroups") {
        echo cgroups();
        exit;
    }
    if ($argv[1] == "--ufdb-ver") {
        echo _ufdbguardd_version();
        exit;
    }

    if ($argv[1] == "--exec-nice") {
        $GLOBALS["VERBOSE"] = true;
        echo "\"{$GLOBALS["CLASS_UNIX"]->EXEC_NICE()}\"\n";
        exit();
    }

    if ($argv[1] == "--ps-mem") {
        ps_mem();
        exit();
    }
    if ($argv[1] == "--arpd") {
        echo arpd();
        exit();
    }
    if ($argv[1] == "--netatalk") {
        echo netatalk();
        exit();
    }
    if ($argv[1] == "--network") {
        ifconfig_network();
        exit();
    }
    if ($argv[1] == "--avahi-daemon") {
        echo avahi_daemon();
        exit();
    }
    if ($argv[1] == "--time-capsule") {
        echo avahi_daemon();
        echo "\n";
        echo netatalk();
        exit();
    }
    if ($argv[1] == "--rrd") {
        exit();
    }


    if ($argv[1] == "--ejabberd") {
        echo ejabberd() . "\n";
        echo pymsnt();
        exit();
    }
    if ($argv[1] == "--lighttpd-all") {
        echo framework();
        exit();
    }
    if ($argv[1] == "--arkeia") {
        echo arkwsd() . "\n";
        echo arkeiad();
        exit();
    }
    if ($argv[1] == "--haproxy") {
        echo haproxy();
        exit();
    }
    if ($argv[1] == "--adagent") {
        echo adagent();
        exit();
    }
    if ($argv[1] == "--privoxy") {
        echo privoxy();
        exit();
    }
    if ($argv[1] == "--mailman") {
        echo mailman();
        exit();
    }
    if ($argv[1] == "--mimedefang") {
        echo mimedefang() . "\n";
        exit();
    }
    if ($argv[1] == "--articadb") {
        articadb();
        exit();
    }
    if ($argv[1] == "--maillog") {
        maillog_watchdog();
        exit();
    }
    if ($argv[1] == "--freeradius") {
        echo freeradius();
        exit();
    }
    if ($argv[1] == "--php-pfm") {
        echo php_fpm();
        exit();
    }



    if ($argv[1] == "--ftp-proxy") {
        echo ftp_proxy() . "\n";
        exit();
    }
    if ($argv[1] == "--rsync-debian-mirror") {
        echo rsync_debian_mirror() . "\n";
        exit();
    }
    if ($argv[1] == "--cntlm") {
        echo cntlm() . "\n";
        echo cntlm_parent() . "\n";
        exit();
    }
    if ($argv[1] == "--roundcube-db") {
        echo roundcube_db() . "\n";
        exit();
    }
    if ($argv[1] == "--vde-uniq") {
        echo vde_uniq($argv[2]);
        exit();
    }
    if ($argv[1] == "--vde-all") {
        echo vde_all();
        exit();
    }
    if ($argv[1] == "--ufdb") {
        $ufdbguardd = ufdbguardd();
        echo $ufdbguardd . "\n";
        $ufdbguard_http = ufdbguard_http();
        echo $ufdbguard_http . "\n";
        $ufdbguardd_tail = ufdbguardd_tail();
        echo $ufdbguardd_tail . "\n";
        exit;
    }
    if ($argv[1] == "--ufdb-tail") {
        echo ufdbguardd_tail();
        exit;
    }


    if ($argv[1] == "--ucarp") {
        echo ucarp();
        exit();
    }
    if ($argv[1] == "--squid-db") {
        exit();
    }
    if ($argv[1] == "--sarg") {
        exit();
    }

    if ($argv[1] == "--squid-nat") {
        echo squid_nat();
        exit();
    }
    if ($argv[1] == "--ntlm-monitor") {
        echo ntlm_monitor();
        exit();
    }
    if ($argv[1] == "--ziproxy") {
        echo ziproxy();
        exit();
    }

    if ($argv[1] == "--milter-regex") {
        echo milter_regex();
        exit();
    }


    if ($argv[1] == "--hypercachestoreid") {
        echo "\n";
        hypercache_logger();
        exit();
    }

    if ($argv[1] == "--tailscale") {
        echo TAILSCALE_STATUS();
        exit();
    }



    if ($argv[1] == "--hypercache-proxy") {
        echo HyperCacheServer();
        exit;
    }
    if ($argv[1] == "--process1") {
        Build_default_values();
        exit;
    }
    if ($argv[1] == "--wanproxy") {
        echo wanproxy();
        echo "\n";
        exit;
    }
    if ($argv[1] == "--philesight") {
        philesight();
        exit();
    }
    if ($argv[1] == "--squid-transparent") {
        echo iptables_transparent();
        exit();
    }
    if ($argv[1] == "--itcharter") {
        echo itcharter();
        exit;
    }
    if ($argv[1] == "--itchart") {
        echo itcharter();
        exit;
    }
    if ($argv[1] == "--ulogd") {
        echo ulogd();
        exit();
    }
    if ($argv[1] == "--xapian") {
        echo xapian_web();
        exit();
    }

    if ($argv[1] == "--elasticsearch") {
        echo elasticsearch() . "\n" . squid_logger();
        exit();
    }
    if ($argv[1] == "--all-postfix") {
        $results = array();
        $array = postfix_increment_func(array());
        foreach ($array as $func) {
            try {
                if ($GLOBALS["VERBOSE"]) {
                    echo "***** $func *****\n";
                }
                $results[] = call_user_func($func);
            } catch (Exception $e) {
                ToSyslog("Fatal while running function $func ($e)");
            }
        }
        @file_put_contents("/usr/share/artica-postfix/ressources/logs/all.postifx.status", @implode("\n", $results));
        exit();
    }


    if ($argv[1] == "--videocache") {

        $conf[] = videocache();
        $conf[] = videocache_scheduler();
        $conf[] = videocache_clients();
        echo @implode("\n", $conf);
        exit();
    }


    if ($argv[1] == "--functions") {
        $arr = get_defined_functions();
        print_r($arr);
        exit();
    }


    if ($argv[1] == "--all-squid") {
        if(system_is_overloaded(basename(__FILE__))){die();}
        $GLOBALS["CLASS_UNIX"] = new unix();
        $processes = $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN_ALL(basename(__FILE__) . ".*?{$argv[1]}");
        events(count($processes) . " Running  " . @implode(";", $processes), "{$argv[1]}", __LINE__);


        if (count($processes) > 2) {
            foreach ($processes as $num => $pid) {
                events("Killing pid $pid  ", "MAIN", __LINE__);
                $GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid, 9);
            }
            $processes = $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN_ALL(basename(__FILE__) . ".*?{$argv[1]}");
            events(count($processes) . " Running  " . @implode(";", $processes), "{$argv[1]}", __LINE__);
        }

        if (count($processes) > 0) {
            events("ALL_SQUID: Processes already exists, aborting", "{$argv[1]}", __LINE__);
            exit();
        }

        $cachefile = "/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS";
        $GLOBALS["DISABLE_WATCHDOG"] = true;
        $TimeFile = $cachefile;

        include_once(dirname(__FILE__) . '/ressources/class.status.squid.inc');

       $func=array("ntlm_auth_path","squid_master_status","proxypac","privoxy","squidguardweb","ufdbguardd","articadb","ftp_proxy","cntlm","cntlm_parent","ucarp","squid_nat","ziproxy","killstrangeprocesses","iptables_transparent");


        if (function_exists("WSUS_HTTP")) { $func[] = WSUS_HTTP(); }
        foreach ($func as $function){
            if(is_null($function)){continue;}
            if(!function_exists($function)){continue;}

            try {
                $conf[] = call_user_func($function);
            } catch (Exception $e) {
                events("Fatal while running function $function ($e)", __FUNCTION__, __LINE__);
                _statussquid("Fatal while running function $function ($e)");
            }
            if(system_is_overloaded(basename(__FILE__))){
                squid_admin_mysql(1, "{OVERLOADED_SYSTEM}, aborting the task after call $function()...", ps_report(), __FILE__, __LINE__);
                exit();
            }
        }

        if (is_file($cachefile)) {
            @unlink($cachefile);
        }
        @file_put_contents($cachefile, @implode("\n", $conf));
        @chmod($cachefile, 0755);
        echo @implode("\n", $conf);
        exit();

    }
    if ($argv[1] == "--amavis-full") {
        exit();
    }
    if ($argv[1] == "--verbose") {
        unset($argv[1]);
    }
    if ($GLOBALS["VERBOSE"]) {
        echo "cannot understand {$argv[1]} assume perhaps it is a function\n";
    }
}
if (isset($argv[1])) {
    if (strlen($argv[1]) > 0) {
        write_syslog("Unable to understand {$argv[1]}", basename(__FILE__));
        exit();
    }
}


if ($GLOBALS["DisableArticaStatusService"] == 1) {
    if (systemMaxOverloaded()) {
        events("OVERLOADED !! aborting", "MAIN", __LINE__);
        exit();
    }
    events("-> launch_all_status()", "MAIN", __LINE__);
    launch_all_status();
    exit();
}



$pidfile = "/etc/artica-postfix/" . basename(__FILE__) . ".pid";
$pid = @file_get_contents($pidfile);

if ($GLOBALS["CLASS_UNIX"]->process_exists($pid, (basename(__FILE__)))) {
    print "Starting......: " . date("H:i:s") . " artica-status Already executed PID $pid...\n";
    exit();
}
$nofork = false;
$mem = round(((memory_get_usage() / 1024) / 1000), 2);
events("{$mem}MB artica-status System Memory: {$GLOBALS["TOTAL_MEMORY_MB"]}MB", "MAIN", __LINE__);
print "Starting......: " . date("H:i:s") . " artica-status system memory: {$GLOBALS["TOTAL_MEMORY_MB"]}MB\n";
if (!function_exists("pcntl_fork")) {
    $nofork = true;
}
if ($GLOBALS["TOTAL_MEMORY_MB"] < 400) {
    $nofork = true;
}
if ($GLOBALS["DisableArticaStatusService"] == 1) {
    $nofork = true;
}

if ($nofork) {
    if (systemMaxOverloaded()) {
        events("OVERLOADED !! aborting", "MAIN", __LINE__);
        exit();
    }
    print "Starting......: " . date("H:i:s") . " artica-status pcntl_fork module not loaded !\n";
    $pidfile = "/etc/artica-postfix/" . basename(__FILE__) . ".pid";
    if(!function_exists("posix_getpid")){
        $childpid=getmypid();
    }else{
        $childpid = posix_getpid();
    }

    events("{$mem}MB artica-status Memory NO fork.... pid=$childpid", "MAIN", __LINE__);
    @file_put_contents($pidfile, $childpid);

    $timefile = "/etc/artica-postfix/" . basename(__FILE__) . ".time";
    if (file_time_min($timefile) > 1) {
        @unlink($timefile);
        events("{$mem}MB artica-status Memory NO fork.... -> launch_all_status()", "MAIN", __LINE__);
        launch_all_status();
        @file_put_contents($timefile, time());
    }
    events("{$mem}MB artica-status Memory NO fork.... -> die()", "MAIN", __LINE__);
    $nohup = $GLOBALS["CLASS_UNIX"]->find_program("nohup");
    print "Starting......: " . date("H:i:s") . " artica-status building parse-orders..\n";
    shell_exec2(trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.parse-orders.php >/dev/null 2>&1 &"));

    exit();


}


if (function_exists("pcntl_signal")) {
    pcntl_signal(SIGTERM, 'sig_handler');
    pcntl_signal(SIGINT, 'sig_handler');
    pcntl_signal(SIGCHLD, 'sig_handler');
    pcntl_signal(SIGHUP, 'sig_handler');
}


set_time_limit(0);
ob_implicit_flush();
declare(ticks=1);


$stop_server = false;
$reload = false;
$pid = pcntl_fork();
if ($pid == -1) {
    die("Starting......: " . date("H:i:s") . " artica-status fork() call asploded!\n");
} else if ($pid) {
    // we are the parent
    print "Starting......: " . date("H:i:s") . " artica-status fork()ed successfully.\n";
    exit();
}

$pidfile = "/etc/artica-postfix/" . basename(__FILE__) . ".pid";
if(function_exists("posix_getpid")){$childpid = posix_getpid();}else{$childpid=getmypid();}

@file_put_contents($pidfile, $childpid);
events("Starting PID $childpid", "MAIN", __LINE__);
if (is_file("/var/log/artica-status.log")) {
    @unlink("/var/log/artica-status.log");
}

$renice_bin = $GLOBALS["CLASS_UNIX"]->find_program("renice");
events("$renice_bin 19 $childpid", "MAIN", __LINE__);
shell_exec2("$renice_bin 19 $childpid &");
$GLOBALS["RUN_AS_DAEMON"] = true;
$GLOBALS["SHUTDOWN_COUNT"] = 0;
events("Memory: " . round(((memory_get_usage() / 1024) / 1000), 2) . " before start service" . __LINE__);
$count = 0;
$TTL = 0;
$PP = 0;
CheckCallable();

$PROCESSES_CLASS = new processes_php();
$FIRST_RUN = FALSE;
while ($stop_server == false) {
    $count++;
    $TTL++;

    if(function_exists("posix_getpid")){$childpid = posix_getpid();}else{$childpid=getmypid();}
    $seconds = $count * 5;

    if (is_file("/tmp/postgressql-restore.sh")) {
        shell_exec("/tmp/postgressql-restore.sh >/tmp/postgressql-restore.sh.results 2>&1 &");
    }

    if (is_file("/tmp/dhtest.sh")) {
        shell_exec("/tmp/dhtest.sh");
        @unlink("/tmp/dhtest.sh");
    }

    $mem = round(((memory_get_usage() / 1024) / 1000), 2);

    $f_daemon_time="/etc/artica-postfix/cron.1/exec.status.daemon.time";
    $f_global_stat="/usr/share/artica-postfix/ressources/logs/global.status.ini";

    if (!is_file($f_global_stat)) {
        if (is_file($f_daemon_time)){@unlink($f_daemon_time);}
    }

    if (is_file("/etc/artica-postfix/ARTICA_STATUS_RUN")) {
        ToSyslog("RUN STATUS MANUALLY");
        @unlink("/etc/artica-postfix/ARTICA_STATUS_RUN");
        if (is_file($f_daemon_time)){@unlink($f_daemon_time);}
    }

    $timefile = $GLOBALS["CLASS_UNIX"]->file_time_min($f_daemon_time);

    if (is_file("/etc/artica-postfix/ARTICA_STATUS_RELOAD")) {
        ToSyslog("Reloading settings and libraries...");
        Reload();
    }

    if (!is_file($f_global_stat)) {
        events("global.status.ini does not exists  -> Launch all status...", __FUNCTION__, __LINE__);
        try {
            launch_all_status(true);
        } catch (Exception $e) {
            writelogs("Fatal while running function launch_all_status $e", __FUNCTION__, __FILE__, __LINE__);
        }
        continue;
    }


    if ($timefile >= 3) {
        events("***** LAUNCH ! *******", __FUNCTION__, __LINE__);
        if (is_file($f_daemon_time)){@unlink($f_daemon_time);}
        @file_put_contents($f_daemon_time, time());
        try {
            launch_all_status(true);
        } catch (Exception $e) {
            writelogs("Fatal while running function launch_all_status $e", __FUNCTION__, __FILE__, __LINE__);
        }
        try {
            $PROCESSES_CLASS->ParseLocalQueue();
        } catch (Exception $e) {
            ToSyslog("Fatal while running function ParseLocalQueue $e");
        }

        continue;
    }


    sleep(5);
    $TTLSeconds = $TTL + 5;

    try {
        $PROCESSES_CLASS->ParseLocalQueue();
    } catch (Exception $e) {
        ToSyslog("Fatal while running function ParseLocalQueue $e");
    }

    if (is_file("/etc/artica-postfix/ARTICA_STATUS_RELOAD")) {
        ToSyslog("Reloading settings and libraries...");
        Reload();
    }

}
write_syslog("Shutdown after $TTLSeconds seconds. stop_server=$stop_server", __FILE__);
events("!!! STOPPED DAEMON....die()...", "MAIN", __LINE__);


function sig_handler($signo)
{
    global $stop_server;
    global $reload;
    switch ($signo) {
        case SIGTERM:
        {
            $GLOBALS["SHUTDOWN_COUNT"] = $GLOBALS["SHUTDOWN_COUNT"] + 1;
            if ($GLOBALS["SHUTDOWN_COUNT"] > 3) {
                $stop_server = true;
            }
            events("Memory: " . round(((memory_get_usage() / 1024) / 1000), 2) . " Asked to shutdown {$GLOBALS["SHUTDOWN_COUNT"]}/3", __FUNCTION__, __LINE__);
            break;
        }

        case 1:
        {
            $reload = true;

        }

        default:
        {
            if ($signo <> 17) {
                events("Receive sig_handler $signo", __FUNCTION__, __LINE__);
            }
        }
    }
}


function LoadIncludes(){

    $Frameworks[]="class.unix.inc";
    $Frameworks[]="frame.class.inc";
    $Frameworks[]="class.settings.inc";


    foreach ($Frameworks as $ressource){
        $fname=$GLOBALS["BASE_ROOT"] . "/framework/$ressource";
        if ($GLOBALS["VERBOSE"]) {echo "LoadIncludes(); -> $fname\n";}
        include_once($fname);
    }

    if ($GLOBALS["VERBOSE"]) {echo "LoadIncludes(); -> Start loop\n";}
    $RESSOURCES=array("class.mysql.inc","class.system.network.inc","class.os.system.inc","mysql.status.inc","class.status.manticore.inc",
        "class.status.schedules.php","class.process.inc","class.status.unifi.inc","class.status.irqbalance.inc",
        "class.status.statistics.inc","class.status.defaults.inc","class.status.cgroups.inc","class.status.bandwidthd.inc",
        "class.status.haproxy.inc","class.status.adagent.inc","class.status.privoxy.inc","class.status.sealion.inc",
        "class.status.watchdog.me.inc","class.status.splunk.inc","class.status.elasticsearch.inc","class.status.prads.inc",
        "class.status.videocache.inc","class.status.squid.inc","class.status.postfix.inc","class.status.pdns.inc","class.status.defaults.inc","class.status.xapian.inc","class.status.elasticsearch.inc","class.status.wordpress.inc","class.status.dwagent.inc","class.status.quagga.inc",
        "class.status.saslauthd.inc","class.status.wanproxy.inc","class.status.fsm.inc","class.status.haexchange.inc","class.status.wazhu.agent.inc","class.status.hotspot.inc","class.status.vasd.inc");

    foreach ($RESSOURCES as $ressource){
        $fname=$GLOBALS["BASE_ROOT"] . "/ressources/$ressource";
        if ($GLOBALS["VERBOSE"]) {echo "LoadIncludes(); -> $fname\n";}
        include_once($fname);
    }

    $mem = round(((memory_get_usage() / 1024) / 1000), 2);
    $GLOBALS["CLASS_USERS"] = new settings_inc();
    events("{$mem}MB", __FUNCTION__, __LINE__);
}


function squid_relatime_events($text)
{
    if (trim($text) == null) {
        return;
    }

    $pid = @getmypid();
    $date = @date("H:i:s");
    $logFile = "/var/log/squid/logfile_daemon.debug";

    $size = @filesize($logFile);
    if ($size > 1000000) {
        @unlink($logFile);
    }
    $f = @fopen($logFile, "a");
    if ($GLOBALS["VERBOSE"]) {
        echo "$date:[" . basename(__FILE__) . "] $pid `$text`\n";
    }
    @fwrite($f, "$date:[" . basename(__FILE__) . "] $pid `$text`\n");
    @fclose($f);

}


function Reload(){

    @unlink("/etc/artica-postfix/ARTICA_STATUS_RELOAD");
    $mem = ((memory_get_usage() / 1024) / 1000);

    unset($GLOBALS["CLASS_SOCKETS"]);
    unset($GLOBALS["CLASS_USERS"]);
    unset($GLOBALS["CLASS_UNIX"]);
    unset($GLOBALS["TIME_CLASS"]);
    unset($GLOBALS["GetVersionOf"]);
    unset($GLOBALS["ArticaWatchDogList"]);

    $mem2 = ((memory_get_usage() / 1024) / 1000);
    $free = $mem - $mem2;
    ToSyslog("Reloading {$free}Mb Free...");
    CheckCallable();


}

function ToSyslog($text){
    if ($GLOBALS["VERBOSE"]) {echo $text . "\n";}
    if (!function_exists("syslog")) {return;}
    $LOG_SEV = LOG_INFO;
    openlog("artica-status", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
}


function Scheduler(){
    include_once('/usr/share/artica-postfix/ressources/class.status.scheduler.inc');


    if (function_exists("SecuriteInfo")) {
        SecuriteInfo();
    }
    if (class_exists("status_scheduler")) {
        $sch = new status_scheduler();
        if ($sch) {
            return null;
        }
    }
}


function CleanCloudCatz()
{


    $durations[60] = "7 * * * *";
    $durations[120] = "7 0,2,4,6,8,10,12,14,16,18,20,22 * * *";
    $durations[240] = "7 0,4,8,12,16,22 * * *";
    $durations[480] = "7 0,8,16 * * *";
    $durations[720] = "7 0,12 * * *";
    $durations[960] = "7 0,16 * * *";
    $durations[1440] = "7 0 * * *";
    $durations[2880] = "7 0 */2 * *";
    $durations[5760] = "7 0 */4 * *";
    $durations[10080] = "7 0 * * 0";
    $durations[43200] = "7 0 1 * *";


    $ServerRunSince = $GLOBALS["CLASS_UNIX"]->ServerRunSince();
    if ($ServerRunSince < 5) {
        return;
    }

    $UfdbCatsUpload = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUpload"));
    if (!$GLOBALS["CLASS_USERS"]->CORP_LICENSE) {
        $UfdbCatsUpload = 0;
    }
    if ($UfdbCatsUpload == 1) {
        $UfdbCatsUploadFTPSchedule = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPSchedule"));


        if (!is_file("/etc/cron.d/artica-ufdbcat-upload")) {
            $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("artica-ufdbcat-upload", $durations[$UfdbCatsUploadFTPSchedule], "exec.upload.categories.php");
            shell_exec("/etc/init.d/cron reload");
        }

    } else {
        if (is_file("/etc/cron.d/artica-ufdbcat-upload")) {
            @unlink("/etc/cron.d/artica-ufdbcat-upload");
            shell_exec("/etc/init.d/cron reload");
        }

    }
}
function xdcloudlogs($text = null)
{
    $logFile = "/var/log/cleancloud.log";
    $time = date("Y-m-d H:i:s");
    $PID = getmypid();
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile));
    }
    if (is_file($logFile)) {
        $size = filesize($logFile);
        if ($size > 1000000) {
            unlink($logFile);
        }
    }
    $logFile = str_replace("//", "/", $logFile);
    $f = @fopen($logFile, 'a');
    @fwrite($f, "$time [$PID]:exec.status.php:: $text\n");
    @fclose($f);
}

function MemorySync()
{

    $filecacheInodes = "/etc/artica-postfix/cron.1/InodeSync.time";
    $filetime = $GLOBALS["CLASS_UNIX"]->file_time_min($filecacheInodes);

    if ($filetime > 30) {
        if (is_file($filecacheInodes)) {
            @unlink($filecacheInodes);
        }
        $DISK_INODES = $GLOBALS["CLASS_UNIX"]->DISK_INODES();
        foreach ($DISK_INODES as $num => $ligne) {
            if(!is_array($ligne)){
                continue;
            }
            $POURC = $ligne["POURC"];
            if ($POURC > 90) {
                squid_admin_mysql(1, "Alertes too many files on partition $num {$POURC}% used",
                    "Please remove some files on this partition", __FILE__, __LINE__);

                squid_admin_mysql(1, "Alertes too many files on partition $num {$POURC}% used",
                    "Please remove some files on this partition", __FILE__, __LINE__);

                @file_put_contents($filecacheInodes, time());

            }
        }
    }


    $TOTAL_MEM_POURCENT_USED = $GLOBALS["CLASS_UNIX"]->TOTAL_MEM_POURCENT_USED();
    $GLOBALS["CLASS_UNIX"]->ToSyslog("Memory use {$TOTAL_MEM_POURCENT_USED}%");
    $filecache_80 = "/etc/artica-postfix/cron.1/MemorySync80.time";
    $filecache_90 = "/etc/artica-postfix/cron.1/MemorySync90.time";
    $filecache_100 = "/etc/artica-postfix/cron.1/MemorySync99.time";

    if ($TOTAL_MEM_POURCENT_USED > 80) {
        if ($TOTAL_MEM_POURCENT_USED < 90) {
            $filetime = $GLOBALS["CLASS_UNIX"]->file_time_min($filecache_80);
            if ($filetime > 15) {
                @unlink($filecache_80);
                @file_put_contents($filecache_80, time());
                squid_admin_mysql(1, "System memory exceed {$TOTAL_MEM_POURCENT_USED}%",
                    "Timeout {$filetime}Mn\nYou will find here a snapshot of current tasks\n" . $GLOBALS["CLASS_UNIX"]->ps_mem_report(), __FILE__, __LINE__);
            }
        }
    }

    if ($TOTAL_MEM_POURCENT_USED > 89) {
        if ($TOTAL_MEM_POURCENT_USED < 97) {
            $filetime = $GLOBALS["CLASS_UNIX"]->file_time_min($filecache_90);
            if ($filetime > 10) {
                @unlink($filecache_90);
                @file_put_contents($filecache_90, time());
                squid_admin_mysql(1, "System memory exceed {$TOTAL_MEM_POURCENT_USED}%",
                    "Timeout {$filetime}Mn\nYou will find here a snapshot of current tasks\n" . $GLOBALS["CLASS_UNIX"]->ps_mem_report(), __FILE__, __LINE__);
            }
        }
    }

    if ($TOTAL_MEM_POURCENT_USED > 97) {
        $filetime = $GLOBALS["CLASS_UNIX"]->file_time_min($filecache_100);
        if ($filetime > 10) {
            @unlink($filecache_100);
            @file_put_contents($filecache_100, time());
            squid_admin_mysql(0, "System memory exceed {$TOTAL_MEM_POURCENT_USED}% (action {$filetime}Mn/20mn)",
                "Timeout {$filetime}Mn\nYou will find here a snapshot of current tasks\n" . $GLOBALS["CLASS_UNIX"]->ps_mem_report(), __FILE__, __LINE__);
        }
    }


}

function SwapWatchdog()
{
    $reboot = false;
    $DisableSWAPP = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSWAPP");
    if (!is_numeric($DisableSWAPP)) {
        $DisableSWAPP = 0;
    }
    if ($DisableSWAPP == 1) {
        return;
    }
    $notif = null;

    mkdir_test("/etc/artica-postfix/cron.1", 0755, true);
    $filecache = "/etc/artica-postfix/cron.1/SwapOffOn.time";
    $filecache20 = "/etc/artica-postfix/cron.1/SwapOffOn20.time";
    $filecache50 = "/etc/artica-postfix/cron.1/SwapOffOn50.time";
    $filecache100 = "/etc/artica-postfix/cron.1/SwapOffOn50.time";

    $Data=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SwapOffOn");
    if(strlen($Data)<10){
        return;
    }
    $SwapOffOn=unserialize($Data);
    if(!$SwapOffOn){
        return;
    }


    if (!isset($SwapOffOn["SwapEnabled"])) {
        $SwapOffOn["SwapEnabled"] = 1;
    }
    if (!isset($SwapOffOn["SwapMaxPourc"])) {
        $SwapOffOn["SwapMaxPourc"] = 20;
    }
    if (!isset($SwapOffOn["SwapMaxMB"])) {
        $SwapOffOn["SwapMaxMB"] = 0;
    }
    if (!isset($SwapOffOn["SwapTimeOut"])) {
        $SwapOffOn["SwapTimeOut"] = 60;
    }


    if (!is_numeric($SwapOffOn["SwapEnabled"])) {
        $SwapOffOn["SwapEnabled"] = 1;
    }
    if (!is_numeric($SwapOffOn["SwapMaxPourc"])) {
        $SwapOffOn["SwapMaxPourc"] = 20;
    }
    if (!is_numeric($SwapOffOn["SwapMaxMB"])) {
        $SwapOffOn["SwapMaxMB"] = 0;
    }
    if (!is_numeric($SwapOffOn["SwapTimeOut"])) {
        $SwapOffOn["SwapTimeOut"] = 60;
    }

    include_once(dirname(__FILE__) . "/ressources/class.main_cf.inc");
    $sys = new systeminfos();
    if ($sys->swap_used == 0) {
        return;
    }
    if ($sys->swap_total == 0) {
        return;
    }
    if ($sys->swap_used == $sys->swap_total) {
        return;
    }

    events("$sys->swap_used/$sys->swap_total ", __FUNCTION__, __LINE__);
    $pourc = round(($sys->swap_used / $sys->swap_total) * 100);

    $notif = $notif . "$sys->swap_used/$sys->swap_total\n";

    events("{$sys->swap_used}MB used ($pourc%)", __FUNCTION__, __LINE__);


    if ($pourc > 20) {
        if ($pourc < 50) {
            $filetime = $GLOBALS["CLASS_UNIX"]->file_time_min($filecache20);
            if ($filetime > 30) {
                @unlink($filecache20);
                @file_put_contents($filecache20, time());

                squid_admin_mysql(1, "[INFO]: System swap exceed {$pourc}%",
                    "Time {$filetime}Mn\nYou will find here a snapshot of current tasks\n" . $GLOBALS["CLASS_UNIX"]->ps_mem_report(), __FILE__, __LINE__);
            }
        }
    }

    if ($pourc > 50) {
        if ($pourc < 70) {
            $filetime = $GLOBALS["CLASS_UNIX"]->file_time_min($filecache50);
            if ($filetime > 15) {
                @unlink($filecache50);
                @file_put_contents($filecache50, time());

                squid_admin_mysql(1, "[WARNING]: System swap exceed {$pourc}%",
                    "Time {$filetime}Mn\nYou will find here a snapshot of current tasks\n" . $GLOBALS["CLASS_UNIX"]->ps_mem_report(), __FILE__, __LINE__);
            }
        }
    }
    if ($pourc > 70) {
        $filetime = $GLOBALS["CLASS_UNIX"]->file_time_min($filecache100);
        if ($filetime > 10) {
            @unlink($filecache100);
            @file_put_contents($filecache100, time());
            squid_admin_mysql(0, "[ALERT!!]: System swap exceed {$pourc}%",
                "Time {$filetime}Mn\nYou will find here a snapshot of current tasks\n" . $GLOBALS["CLASS_UNIX"]->ps_mem_report(), __FILE__, __LINE__);
        }

    }

    if ($SwapOffOn["SwapEnabled"] == 0) {
        return;
    }
    $filetime = $GLOBALS["CLASS_UNIX"]->file_time_min($filecache);
    if ($filetime < $SwapOffOn["SwapTimeOut"]) {
        events("{$filetime}Mn need to wait {$SwapOffOn["SwapTimeOut"]}mn", __FUNCTION__, __LINE__);
        return;
    }

    if ($SwapOffOn["SwapMaxMB"] > 0) {
        if ($sys->swap_used > $SwapOffOn["SwapMaxMB"]) {
            $execeed_text = $SwapOffOn["SwapMaxMB"] . "MB";
            $reboot = true;
        }
    }
    if ($SwapOffOn["SwapMaxMB"] == 0) {
        if ($pourc > 3) {
            if ($pourc > $SwapOffOn["SwapMaxPourc"]) {
                $execeed_text = $SwapOffOn["SwapMaxPourc"] . "%";
                $reboot = true;
            }
        }
    }
    @unlink($filecache);
    @file_put_contents($filecache, time());
    if (!$reboot) {
        return;
    }

    $swapoff = $GLOBALS["CLASS_UNIX"]->find_program("swapoff");
    $swapon = $GLOBALS["CLASS_UNIX"]->find_program("swapon");

    if (!is_file($swapoff)) {
        events("swapoff no such file", __FUNCTION__, __LINE__);
        shell_exec2("sync; echo \"3\" > /proc/sys/vm/drop_caches >/dev/null 2>&1");
        return;
    }
    if (!is_file($swapon)) {
        events("swapon no such file", __FUNCTION__, __LINE__);
        shell_exec2("sync; echo \"3\" > /proc/sys/vm/drop_caches >/dev/null 2>&1");
        return;
    }


    $time = time();
    if (function_exists("WriteToSyslogMail")) {
        WriteToSyslogMail("SwapWatchdog:: Starting to purge the swap file because it execeed rules", basename(__FILE__));
    }
    $cmd = "$swapoff -a 2>&1";

    $results = array();
    $results[] = $cmd;
    events("running $cmd", __FUNCTION__, __LINE__);
    exec($cmd, $results);

    $cmd = "$swapon -a 2>&1";

    $results[] = $cmd;
    events("running $cmd", __FUNCTION__, __LINE__);
    exec($cmd, $results);

    $text = @implode("\n", $results);
    $time_duration = distanceOfTimeInWords($time, time());
    events("results: $time_duration\n $text", __FUNCTION__, __LINE__);

    $notif = $notif . "\nMemory swap purge $execeed_text ($time_duration)\n$text";
    $notif = $notif . "\n" . $GLOBALS["CLASS_UNIX"]->ps_mem_report();

    squid_admin_mysql(1, "Memory swap purge $execeed_text", "(Execution time: $time_duration)", __FILE__, __LINE__);
    $GLOBALS["CLASS_UNIX"]->send_email_events("Memory swap purge $execeed_text (task time execuction: $time_duration)", $text, "system");

    $sqdbin = $GLOBALS["CLASS_UNIX"]->find_program("squid");
    if (!is_file($sqdbin)) {
        $sqdbin = $GLOBALS["CLASS_UNIX"]->find_program("squid3");
    }
    if (is_file($sqdbin)) {
        $php5 = $GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
        $nohup = $GLOBALS["CLASS_UNIX"]->find_program("nohup");
        if (function_exists("debug_backtrace")) {
            $trace = debug_backtrace();
            if (isset($trace[1])) {
                $sourcefunction = $trace[1]["function"];
                $sourceline = $trace[1]["line"];
                $executed = "Executed by $sourcefunction() line $sourceline\nusing argv:{$GLOBALS["ARGVS"]}\n";
            }
        }
        squid_admin_mysql(1, "Asking to reload proxy service after purging the Swap file", "$executed\n$notif", __FILE__, __LINE__);
        if (function_exists("WriteToSyslogMail")) {
            WriteToSyslogMail("SwapWatchdog:: reloading Squid after purging the Swap file", basename(__FILE__));
        }
        shell_exec2("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --reload-squid --bywatchdog >/dev/null 2>&1 &");
    }


}

function CleanLogs()
{

    if (!isset($GLOBALS["CLASS_UNIX"])) {
        $GLOBALS["CLASS_UNIX"] = new unix();
    }

    $df = $GLOBALS["CLASS_UNIX"]->find_program("df");
    $rm = $GLOBALS["CLASS_UNIX"]->find_program("rm");
    $php5 = $GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
    $nohup = $GLOBALS["CLASS_UNIX"]->find_program("nohup");
    $chmod = $GLOBALS["CLASS_UNIX"]->find_program("chmod");
    if (!isset($GLOBALS["CLASS_SOCKETS"])) {
        $GLOBALS["CLASS_SOCKETS"] = new sockets();
    }


    exec("$df -i /usr/share/artica-postfix 2>&1", $results);
    $INODESARTICA = 0;
    foreach ($results as $num => $line) {
        if (preg_match("#.*?\s+[0-9]+\s+[0-9]+\s+[0-9]+\s+([0-9]+)%\s+\/usr\/share\/artica-postfix#", $line, $re)) {
            $INODESARTICA = $re[1];
        }
    }
    if ($INODESARTICA > 95) {
        shell_exec2("$rm -rf /usr/share/artica-postfix/ressources/logs/web/*.html");
        shell_exec2("$rm -rf /usr/share/artica-postfix/ressources/logs/web/*.log");
        shell_exec2("$rm -rf /usr/share/artica-postfix/ressources/logs/web/*.cache");
        shell_exec2("$rm -rf /usr/share/artica-postfix/ressources/logs/jGrowl/*");
        shell_exec2("$rm -rf /usr/share/artica-postfix/ressources/conf/*");

    }

    if (!is_dir("/etc/artica-postfix/settings/Daemons")) {
        mkdir_test("/etc/artica-postfix/settings/Daemons", true);
    }
    @chmod("/etc/artica-postfix/settings/Daemons", 0755);
    shell_exec2("$chmod 0755 /etc/artica-postfix/settings/Daemons/* >/dev/null 2>&1");

    if (is_file("/var/log/php.log")) {
        $size = $GLOBALS["CLASS_UNIX"]->file_size("/var/log/php.log");
        $size = intval(round(($size / 1024)) / 1000);
        if ($size > 150) {
            @unlink("/var/log/php.log");
            @file_put_contents("/var/log/php.log", "#");
            @chmod("/var/log/php.log", 0777);
        }
    }


    $MirrorEnableDebian = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorEnableDebian");
    if (!is_numeric($MirrorEnableDebian)) {
        $MirrorEnableDebian = 0;
    }
    if ($MirrorEnableDebian == 1) {
        $TIME = $GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.debian.mirror.php.debian_size.time");
        if ($TIME > 30) {
            shell_exec2("$nohup $php5 /usr/share/artica-postfix/exec.debian.mirror.php --debian-size >/dev/null 2>&1 &");
        }

        $MirrorDebianEachMn = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorDebianEachMn");

        $MirrorDebianMaxExecTime = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorDebianMaxExecTime");
        if ($MirrorDebianMaxExecTime > 0) {
            shell_exec2("$nohup $php5 /usr/share/artica-postfix/exec.debian.mirror.php --kill >/dev/null 2>&1 &");
        }

        if (!is_numeric($MirrorDebianEachMn)) {
            $MirrorDebianEachMn = 2880;
        }
        $pidtime = "/etc/artica-postfix/pids/DEBIAN_MIRROR_EXECUTION.TIME";
        $TIME = $GLOBALS["CLASS_UNIX"]->file_time_min($pidtime);
        if ($TIME > $MirrorDebianEachMn) {
            shell_exec2("$nohup $php5 /usr/share/artica-postfix/exec.debian.mirror.php --start-exec >/dev/null 2>&1 &");
        }
    }

}
function launch_all_status_squid($conf=array()){

    $squid_functions = squid_increment_func(array());
    $c = 0;
    $max = count($squid_functions);
    foreach ($squid_functions as $func) {
        $mem = round(((memory_get_usage() / 1024) / 1000), 2);
        if (!function_exists($func)) {
            events("Squid functions: $func() No such function", __FUNCTION__, __LINE__);
            continue;
        }
        events("Squid functions: Running $c/$max function [$func()] {$mem}MB", __FUNCTION__, __LINE__);
        _statussquid("Launch $func(): {$mem}MB in memory");
        $c++;
        try {
            $results = call_user_func($func);
        } catch (Exception $e) {
            events("Fatal while running function $func ($e)", __FUNCTION__, __LINE__);
            _statussquid("Fatal while running function $func ($e)");
        }

        if (trim((string) $results) <> null) {
            $conf[] = $results;
        }

    }
    return $conf;
}

function launch_all_status_cmdline()
{
    if (!isset($GLOBALS["CLASS_UNIX"])) {
        $GLOBALS["CLASS_UNIX"] = new unix();
    }
    if ($GLOBALS["VERBOSE"]) {
        echo "launch_all_status_cmdline()\n";
    }
    $pids = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
    $CacheFileTime = "/usr/share/artica-postfix/ressources/logs/global.status.ini";
    $GLOBALS["CLASS_UNIX"] = new unix();
    $pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file($pids);
    if ($GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
        return;
    }
    @file_put_contents($pids, getmypid());
    $time = $GLOBALS["CLASS_UNIX"]->file_time_min($CacheFileTime);
    if (!$GLOBALS["VERBOSE"]) {
        if ($time < 2) {
            events("{$time}mn, need at least 2mn", __FUNCTION__, __LINE__);
            return;
        }
    }
    if(is_file($CacheFileTime)) {
        @unlink($CacheFileTime);
    }
    @file_put_contents($CacheFileTime, "\n");
    events("-> launch_all_status()", __FUNCTION__, __LINE__);
    $GLOBALS["CLASS_UNIX"]->framework_exec("exec.verif.packages.php --checks &");
    launch_all_status();
}


function killstrangeprocesses()
{
    $pids=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN_ALL("start-stop-daemon");
    foreach ($pids as $pid){
        $time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
        if($time==0){continue;}
        if($pid>1){
            squid_admin_mysql(0, "Killing!! start-stop-daemon process pid $pid, running since {$time}mn", null, __FILE__, __LINE__);
            $GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid,9);
        }

    }

    if (!$GLOBALS["DISABLE_WATCHDOG"]) {
        $net = $GLOBALS["CLASS_UNIX"]->find_program("net");
        if (is_file($net)) {
            $pids = $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN_ALL($net);
            foreach ($pids as $pid => $ligne) {
                $ptime = $GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
                if ($ptime > 4) {
                    squid_admin_mysql(1, "Killing process ID:$pid $net Running over 5mn ({$ptime}mn)", null, __FILE__, __LINE__);
                    $GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid, 9);
                }
            }
        }

        $msktutil = $GLOBALS["CLASS_UNIX"]->find_program("msktutil");
        if (is_file($msktutil)) {
            $pids = $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN_ALL($msktutil);
            foreach ($pids as $pid => $ligne) {
                $ptime = $GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
                if ($ptime > 4) {
                    squid_admin_mysql(1, "Killing process ID:$pid $msktutil Running over 5mn ({$ptime}mn)", null, __FILE__, __LINE__);
                    $GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid, 9);
                }
            }
        }
    }


}


function launch_all_status($force = false){
    $conf = array();
    $squid_functions = array();
    $results = null;
    $CacheFileTime = "/usr/share/artica-postfix/ressources/logs/global.status.ini";

    if (!is_file("/etc/artica-postfix/settings/Daemons/EnableipV6")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableipV6", 0);
    }

    if (is_file("/etc/artica-postfix/STATS_APPLIANCE")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("AsStatisticsAppliance", 1);
    } else {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("AsStatisticsAppliance", 0);
    }
    if (is_file("/etc/artica-postfix/HAPRROXY_APPLIANCE")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("AsHaProxyAppliance", 1);
    } else {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("AsHaProxyAppliance", 0);
    }

    if (is_file("/etc/artica-postfix/FROM_SETUP")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FromSetup", 1);
    } else {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FromSetup", 0);
    }

    if (is_file("/usr/bin/hamachi")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HAMACHI_INSTALLED", 1);
    } else {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HAMACHI_INSTALLED", 0);
    }
    if (is_file("/etc/init.d/clamav-milter")) {
        remove_service("/etc/init.d/clamav-milter");
    }
    if (!isset($GLOBALS["CLASS_UNIX"])) {
        $GLOBALS["CLASS_SOCKETS"] = new sockets();
        $GLOBALS["CLASS_USERS"] = new settings_inc();
        $GLOBALS["CLASS_UNIX"] = new unix();
    }
    $GLOBALS["NICE"] = $GLOBALS["CLASS_UNIX"]->EXEC_NICE();
    mkdir_test("/usr/share/artica-postfix/ressources/logs", 0755, true);
    if (!is_file("/usr/share/artica-postfix/ressources/logs/php.log")) {
        @touch("/usr/share/artica-postfix/ressources/logs/php.log");
    }
    ChecksRoutes();
    $trace = debug_backtrace();
    if (isset($trace[1])) {
        $called = " called by " . basename($trace[1]["file"]) . " {$trace[1]["function"]}() line {$trace[1]["line"]}";
        //events("$called", __FUNCTION__, __LINE__);
    }

    CheckCallable();
    if (!system_is_overloaded()) {

        if (!is_file("/usr/share/artica-postfix/ressources/logs/global.versions.conf")) {
            events("-> artica-install --write-version", __FUNCTION__, __LINE__);

        } else {
            $filetime = file_time_min("/usr/share/artica-postfix/ressources/logs/global.versions.conf");
            events("global.versions.conf={$filetime}mn ", __FUNCTION__, __LINE__);
            if ($filetime > 60) {
                events("global.versions.conf \"$filetime\"mn", __FUNCTION__, __LINE__);
                @unlink("/usr/share/artica-postfix/ressources/logs/global.versions.conf");

            }
        }
    }
    if(is_file($GLOBALS["MY-POINTER"])) {
        @unlink($GLOBALS["MY-POINTER"]);
    }
    @file_put_contents($GLOBALS["MY-POINTER"], time());
    $authtailftime = "/etc/artica-postfix/pids/auth-tail.time";
    $timefile = $GLOBALS["CLASS_UNIX"]->file_time_min($authtailftime);
    events("/etc/artica-postfix/pids/auth-tail.time -> {$timefile}Mn", __FUNCTION__, __LINE__);
    if ($timefile > 15) {
        if (is_file($timefile)) {
            @unlink($timefile);
        }
        @file_put_contents($authtailftime, time());
        $cmd = trim("{$GLOBALS["nohup"]} /etc/init.d/artica-postfix restart auth-logger >/dev/null 2>&1 &");
        events($cmd);
        shell_exec2($cmd);
    }


    if (is_file("/home/artica/SQLITE/bugzilla.db")) {
        $TimeF = "/etc/artica-postfix/pids/exec.bugzilla.php.get_bugs.time";
        $timefile = $GLOBALS["CLASS_UNIX"]->file_time_min($TimeF);
        if ($timefile > 60) {
            $cmd = trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.bugzilla.php --get-bugs --schedule >/dev/null 2>&1 &");
            events($cmd);
            shell_exec2($cmd);
        }
    }



    if(is_file($CacheFileTime)) {
        @unlink($CacheFileTime);
    }
    @file_put_contents($CacheFileTime, time());

    $GLOBALS["RESTART_FRAMEWORK_440"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RESTART_FRAMEWORK_440"));
    if($GLOBALS["RESTART_FRAMEWORK_440"]==1){
        squid_admin_mysql(1,"Restarting Framework for 4.40 release..",null,__FILE__,__LINE__);
        shell_exec("/etc/init.d/artica-phpfpm restart");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RESTART_FRAMEWORK_440",time());
    }





    events("**************** START ALL STATUS ****************");
    $GLOBALS["CLASS_UNIX"]->chmod_func(0755, "/etc/artica-postfix/settings/Daemons/*");


    $functions = array("Default_values", "gam_server",  "glances", "elasticsearch",
        "squid_logger",  "load_stats", "watchdog_me_load",  "bandwidthd", "unifi_mongodb", "unifi",
        "prads", "Popuplate_cron", "squid_dashboard_statistics",
        "APP_WHAZU_AGENT","APP_ARTICAPCAP",
        "philesight", "cron",  "disks_monitor",    "netdata","TAILSCALE_STATUS","VASD_STATUS","ZEBRA_STATUS","OSPF_STATUS","APP_URBACKUP","rustdesk","MANTICORE_STATUS",
        "CleanLogs",   "wpa_supplicant","sqlite_dbs",
        "fetchmail", "milter_greylist", "irqbalance", "ulogd",
        "framework", "pdns_server", "pdns_recursor", "cyrus_imap",  "saslauthd", "syslogger",   "clamscan",  "spamassassin_milter", "spamassassin",   "ksrn", "DWAGENT_STATUS","CIESCACHE_STATUS",
        "mailman", "rpcbind",  "ntlm_auth_path", "scanned_only", "roundcube", "cups",
        "gdm",  "hamachi",  "artica_notifier", "pure_ftpd",
        "ocs_agent",  "wanproxy","go_exec_update" ,"sshportal", "gluster", "auditd", "milter_dkim", "dropbox", "killstrangeprocesses", "klnagent","dockerd",
         "tftpd",  "bandwith", "lsm", "Build_default_values",
        "pptpd", "pptp_clients", "ddclient", "cluebringer", "proftpd_status", "splunk",
         "openvpn", "vboxguest", "sabnzbdplus", "MemorySync",  "SwapWatchdog", "mosquitto","APP_ARTICAFSMON",        "OpenVPNClientsStatus", "stunnel", "avahi_daemon", "CheckCurl", "NetAdsWatchdog", "munin",  "greyhole",
        "iscsi", "netatalk", "smartd",   "greyhole_watchdog", "tomcat",
        "cgroups",  "arpd", "ps_mem", "ipsec", "openvpn", "ifconfig_network",
        "udevd_daemon", "ejabberd", "pymsnt", "arkwsd", "arkeiad", "haproxy", "hacluster", "privoxy", "ad_rest", "CleanLogs", "checksyslog", "freeradius", "maillog_watchdog", "arp_spoof","go_squid_auth","HOTSPOT_STATUS",
        "php_fpm", "CleanCloudCatz",   "Scheduler", "exim4", "ntopng",   "XMail", "conntrackd", "iptables", "wordpress",
         "vde_all", "sealion_agent", "syncthing", "killstrangeprocesses","keepalived");

    ToSyslog("launch_all_status(): " . count($functions));

    if ($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MYSQL_INSTALLED") == 1) {
        if ($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMySQL") == 1) {
            $functions[] = "mysql_server";
            $functions[] = "mysql_mgmt";
            $functions[] = "mysql_replica";
        }
    }



    if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFail2Ban")) == 1) {
        $functions[]="fail2ban";
    }


    if (is_file("/etc/init.d/unbound")) {
        $functions[]="unbound";
        $functions[]="dnscrypt_proxy";
    }






    if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGreenSQL") == 1)) {
        include_once(dirname(__FILE__) . '/ressources/class.status.greensql.inc');
        greensql_status();
    }

    if (is_file("/opt/dnsfilterd/bin/dnsfilterd")) {
        include_once(dirname(__FILE__) . '/ressources/class.status.dnsfilterd.inc');
        dnsfilterd_status();
    }
    if (is_file("/etc/init.d/filebeat")) {
        include_once(dirname(__FILE__) . '/ressources/class.status.filebeat.inc');
        _filebeat();
    }



    $postfix_functions = array();
    $POSTFIX_INSTALLED = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
    if ($POSTFIX_INSTALLED == 1) {
        $EnablePostfix = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
        if ($EnablePostfix == 1) {
            include_once('/usr/share/artica-postfix/ressources/class.status.postfix.inc');
            $postfix_functions = postfix_increment_func(array());
        }
    }


    $PDNSInstalled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSInstalled"));
    if ($PDNSInstalled == 1) {
        include_once('/usr/share/artica-postfix/ressources/class.status.pdns.inc');
        $functions = pdns_increment_func($functions);
    }


    $LOAD_SQUID_LIB = false;
    $AsCategoriesAppliance = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AsCategoriesAppliance"));

    if ($AsCategoriesAppliance == 1) { $LOAD_SQUID_LIB = true; }

    if (is_file("/etc/init.d/ufdbcat")) {
        include_once('/usr/share/artica-postfix/ressources/class.status.statistics.appliance.inc');
        $functions = incr_stats_apps_func($functions);
    } else {
        if (is_file("/etc/init.d/ufdb")) {
            include_once('/usr/share/artica-postfix/ressources/class.status.statistics.appliance.inc');
            $functions = incr_stats_apps_func($functions);
        }
    }





    if (is_file("/etc/init.d/xapian-web")) {
        include_once('/usr/share/artica-postfix/ressources/class.status.xapian.inc');
        $functions[] = "xapian_web";
    }


    include_once('/usr/share/artica-postfix/ressources/class.status.smokeping.inc');
    $functions[] = "smokeping";







    ToSyslog("launch_all_status(): " . count($functions));
    $stats = new status_hardware();
    if ($stats) {
        unset($stats);
    }
    $data1 = $GLOBALS["TIME_CLASS"];
    $data2 = time();
    $difference = ($data2 - $data1);
    $min = round($difference / 60);
    if ($min > 9) {
        events("reloading classes...", __FUNCTION__, __LINE__);
        $GLOBALS["TIME_CLASS"] = time();
        $GLOBALS["CLASS_SOCKETS"] = new sockets();
        $GLOBALS["CLASS_USERS"] = new settings_inc();
        $GLOBALS["CLASS_UNIX"] = new unix();
    }
    $AllFunctionCount = count($functions);
    events("running $AllFunctionCount functions ", __FUNCTION__, __LINE__);
    if ($force) {
        events("running function in FORCE MODE !", __FUNCTION__, __LINE__);
    }
    $max = count($functions);
    $c = 0;
    $TEX = time();
    foreach ($functions as $func) {
        $c++;
        $mem = round(((memory_get_usage() / 1024) / 1000), 2);
        if ($GLOBALS["VERBOSE"]) {
            echo "*****\n$func $c/$max\n*****\n";
        }
        if (!function_exists($func)) {
            continue;
        }
        events("Running $c/$max $func() function {$mem}MB", __FUNCTION__, __LINE__);

        if (is_file("/etc/artica-postfix/ARTICA_STATUS_RELOAD")) {
            ToSyslog("Reloading settings and libraries...");
            Reload();
        }

        if (!$force) {
            if (system_is_overloaded(basename(__FILE__))) {
                events("System is overloaded: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting", __FUNCTION__, __LINE__);
                ToSyslog("System is overloaded: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting");
                greyhole_watchdog();
                break;
            }
        }


        try {
            if ($GLOBALS["VERBOSE"]) {
                echo "***** $c/$max $func *****\n";
            }
            if(is_array($func)){
                ToSyslog("FATAL! array send in func". serialize($func));
                continue;
            }

            $results = call_user_func($func);
            $GLOBALS["LAST_FUNCTION_USED"] = "$func()";
        } catch (Exception $e) {
            ToSyslog("Fatal while running function $func ($e)");
        }

        if (is_array($results)) {
            foreach ($results as $key => $line) {
                $conf[] = "$key => $line";
            }
        } else {
            if (trim((string) $results) <> null) {
                $conf[] = $results;
            }
        }

    }
    if ($GLOBALS["VERBOSE"]) {
        events("Postfix functions: " . count($postfix_functions) . " functions", __FUNCTION__, __LINE__);
    }
    if (count($postfix_functions) > 0) {
        $c = 0;
        $max = count($postfix_functions);
        foreach ($postfix_functions as $num => $func) {
            $c++;
            $mem = round(((memory_get_usage() / 1024) / 1000), 2);
            if ($GLOBALS["VERBOSE"]) {
                echo "*****\npostfix_functions $func $c/$max\n*****\n";
                events("Postfix functions: Running $c/$max $func() function {$mem}MB", __FUNCTION__, __LINE__);
            }
            if (!function_exists($func)) {
                continue;
            }

            if (!$force) {
                if (system_is_overloaded(basename(__FILE__))) {
                    events("System is overloaded: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting", __FUNCTION__, __LINE__);
                    ToSyslog("System is overloaded: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting");
                    greyhole_watchdog();
                    break;
                }
            }


            try {
                $results = call_user_func($func);
            } catch (Exception $e) {
                ToSyslog("Fatal while running function $func ($e)");
            }

            if (trim($results) <> null) {
                $conf[] = $results;
            }

        }
    }

    if ($LOAD_SQUID_LIB) {
        $res='/usr/share/artica-postfix/ressources/class.status.squid.inc';
        include_once($res);
        launch_all_status_squid($conf);
    }


    $p = new processes_php();
    $p->MemoryInstances();
    $p = null;

    $TOOK = $GLOBALS["CLASS_UNIX"]->distanceOfTimeInWords($TEX, time());
    $mem = round(((memory_get_usage() / 1024) / 1000), 2);
    $percent_free = $GLOBALS["CLASS_UNIX"]->GetMemFreePourc();
    ToSyslog("Executed " . count($functions) . " functions in $TOOK MemFree {$percent_free}% Used memory: {$mem}MB");

    if(is_file("/usr/share/artica-postfix/ressources/logs/global.status.ini")) {
        @unlink("/usr/share/artica-postfix/ressources/logs/global.status.ini");
    }
    file_put_contents("/usr/share/artica-postfix/ressources/logs/global.status.ini", @implode("\n", $conf));
    @chmod("/usr/share/artica-postfix/ressources/logs/global.status.ini", 0777);
    @file_put_contents("/etc/artica-postfix/cache.global.status", @implode("\n", $conf));

    $sock = new sockets();
    $WizardSavedSettingsSend = $sock->GET_INFO("WizardSavedSettingsSend");
    if (!is_numeric($WizardSavedSettingsSend)) {
        $WizardSavedSettingsSend = 0;
    }
    if ($WizardSavedSettingsSend == 0) {
        //$cmd = trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.web-community-filter.php --register >/dev/null 2>&1 &");
        //shell_exec2($cmd);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/register/server");
    }

    if (!is_file("/usr/share/artica-postfix/ressources/settings.inc")) {
        $GLOBALS["CLASS_UNIX"]->Process1(true);
    }

    if (is_dir("/opt/artica-agent/usr/share/artica-agent/ressources")) {
        events("writing /opt/artica-agent/usr/share/artica-agent/ressources/status.ini", __FUNCTION__, __LINE__);
        @file_put_contents("/opt/artica-agent/usr/share/artica-agent/ressources/status.ini", @implode("\n", $conf));
    }


    if (system_is_overloaded(__FILE__)) {
        ToSyslog("{OVERLOADED_SYSTEM} {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} {$GLOBALS["SYSTEM_INTERNAL_MEMM"]}MB Memory free");
        return;
    }



    if(is_file("/etc/init.d/smbd")) {
        $cmd = trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} " . __FILE__ . " --samba >/usr/share/artica-postfix/ressources/logs/web/samba.status 2>&1 &");
        shell_exec2($cmd);
    }


    $GLOBALS["CLASS_UNIX"]->BLKID_ALL();
    events("*****  FINISH $TOOK ****", __FUNCTION__, __LINE__);
    events("********************************************************************", __FUNCTION__, __LINE__);
    if ($GLOBALS["VERBOSE"]) {
        echo " *****  FINISH **** \n\n";
    }


}

// ========================================================================================================
function sqlite_dbs():bool{

    if(!is_file("/etc/artica-postfix/UPGRADE_SQLITE_440")){
        squid_admin_mysql(1,"Upgrading local SQLite databases...",null,__FILE__,__LINE__);
        $GLOBALS["CLASS_UNIX"]->framework_execute("exec.convert-to-sqlite.php --force");
    }

    return true;

}

function OpenVPNClientsStatus()
{
    $q = new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    $l = array();
    if (is_file("/usr/share/artica-postfix/ressources/logs/openvpn-clients.status")) {
        @unlink("/usr/share/artica-postfix/ressources/logs/openvpn-clients.status");
    }
    if (!$q->TABLE_EXISTS("vpnclient")) {
        return null;
    }

    $sql = "SELECT ID,connexion_name FROM vpnclient WHERE `connexion_type`=2 AND `enabled`=1";
    $results = $q->QUERY_SQL($sql);

    if (!$q->ok) {
        events($q->mysql_error, __FUNCTION__, __FILE__, __LINE__);
        return;
    }


    foreach ($results as $index => $ligne) {
        $id = $ligne["ID"];
        events("Checking VPN client N.$id", __FUNCTION__, __FILE__, __LINE__);
        $l[] = "[{$ligne["connexion_name"]}]";
        $l[] = "service_name={$ligne["connexion_name"]}";
        $l[] = "service_cmd=openvpn";
        $l[] = "master_version=" . GetVersionOf("openvpn");
        $l[] = "service_disabled=1";
        $l[] = "family=vpn";
        $l[] = "watchdog_features=1";
        $master_pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file("/etc/artica-postfix/openvpn/clients/$id/pid");

        if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            WATCHDOG("APP_OPENVPN {$ligne["connexion_name"]}", "openvpn");
            $l[] = "running=0\ninstalled=1";
            $l[] = "";
        } else {
            $l[] = "running=1";
            $l[] = GetMemoriesOf($master_pid,$ligne["connexion_name"]);
            $l[] = "";
        }

    }
    if (is_array($l)) {
        $final = implode("\n", $l);
    }
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/openvpn-clients.status", $final);
    return $final;

}

function maillog_watchdog(){
    if (!isset($GLOBALS["CLASS_USERS"])) {
        CheckCallable();
    }
    $POSTFIX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
    if($POSTFIX_INSTALLED==0){
        return;
    }
    $PostfixEnable=intval($GLOBALS['CLASS_SOCKETS']->GET_INFO("EnablePostfix"));
    if($PostfixEnable==0){
        return;
    }

    $EnableStopPostfix = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStopPostfix"));
    if ($EnableStopPostfix == 1) {
        return;
    }
    $maillog_path = $GLOBALS["CLASS_USERS"]->maillog_path;
    if ($GLOBALS["VERBOSE"]) { echo "maillog_path --> ??? filesize(`$maillog_path`)\n"; }
    if(!is_null($maillog_path)) {if (trim($maillog_path) == null) { return; } }

    $maillog_size = @filesize($maillog_path);

    if ($GLOBALS["VERBOSE"]) {
        echo "maillog_path --> $maillog_size bytes\n";
    }
    if ($GLOBALS["VERBOSE"]) {
        echo "$maillog_path: $maillog_size Bytes...\n";
    }
    if ($maillog_size < 50) {
        $GLOBALS["CLASS_UNIX"]->send_email_events("Warning, Log path:$maillog_path Size:$maillog_size bytes.. restarting syslog", "Suspicious size on maillog, restarting system log daemon", "postfix");
        $GLOBALS["CLASS_UNIX"]->RESTART_SYSLOG(true);
    }
    if ($GLOBALS["VERBOSE"]) {
        echo "maillog_watchdog finish --> ???\n";
    }

}





//---------------------------------------------------------------------------------------------------

function glances_pid(){
    $pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/glances/glances.pid");
    if ($GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
        return $pid;
    }
    $Masterbin = $GLOBALS["CLASS_UNIX"]->find_program("glances");
    return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($Masterbin);
}

function glances():string{
    $l[] = "[APP_GLANCES]";
    $l[] = "service_name=APP_GLANCES";
    $l[] = "service_cmd=/etc/init.d/glances";
    $l[] = "master_version=" . $GLOBALS["CLASS_SOCKETS"]->GET_INFO("GLANCES_VERSION");
    $l[] = "family=statistics";
    $l[] = "watchdog_features=1";
    $l[] = "installed=1";

    $EnableGlances=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGlances"));
    if($EnableGlances==1){
        $GLOBALS["CLASS_UNIX"]->framework_exec("exec.glances.php --uninstall");
        squid_admin_mysql(1, "Glances is installed remove it for htop-web migration [action=uninstall]",
            null, __FILE__, __LINE__);

        $GLOBALS["CLASS_UNIX"]->framework_exec("exec.bandwhich.php --install");
        return true;
    }


    if($EnableGlances==0) {
        $l[] = "service_disabled=0";
        $l[] = "running=0";
        if (!is_file("/etc/init.d/glances")) {
            return @implode("\n", $l);
        }
        if (is_file("/etc/init.d/glances")) {
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.glances.php --uninstall");
            squid_admin_mysql(1, "Glances is installed but disabled [action=uninstall]",
                null, __FILE__, __LINE__);
        }
        return @implode("\n", $l);
    }

    $l[] = "service_disabled=1";
    if($EnableGlances==1) {
        $l[] = "running=0";
        if (!is_file("/etc/init.d/glances")) {
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.glances.php --install");
            squid_admin_mysql(1, "Glances is not installed but enabled [action=install]",
                null, __FILE__, __LINE__);
            return @implode("\n", $l);
        }
    }


    $f = explode("\n", @file_get_contents("/etc/default/glances"));
    foreach ($f as $line) {
        $line = trim($line);
        if ($line == null) {
            continue;
        }
        if (preg_match("#^RUN=.*?false#i", $line)) {
            squid_admin_mysql(0, "Glances Daemon installation corrupted [action=uninstall]", "see in /etc/default/glances", __FILE__, __LINE__);
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.glances.php --uninstall");
            return @implode("\n", $l);
        }

    }

    $glances_pid = glances_pid();
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($glances_pid)) {
        if (!$GLOBALS["DISABLE_WATCHDOG"]) {
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.glances.php --restart");
        }
        $l[] = "running=0";
        $l[] = "";
        return implode("\n", $l);
    }


    $l[] = "running=1";
    $l[] = GetMemoriesOf($glances_pid);
    $l[] = "";
    return implode("\n", $l);
}
function squid_watchdog_events($text)
{
    $sourcefunction = null;
    $sourceline = null;
    if (function_exists("debug_backtrace")) {
        $trace = debug_backtrace();
        if (isset($trace[1])) {

            if (isset($trace[1]["function"])) {
                $sourcefunction = $trace[1]["function"];
            }
            if (isset($trace[1]["line"])) {
                $sourceline = $trace[1]["line"];
            }
        }

    }


    $GLOBALS["CLASS_UNIX"]->events($text, "/var/log/squid.watchdog.log", false, $sourcefunction, $sourceline);
}


function WATCHDOG($APP_NAME, $cmd)
{
    if (!is_file(dirname(__FILE__) . "/exec.watchdog.php")) {
        return;
    }
    if ($GLOBALS["DISABLE_WATCHDOG"]) {
        return null;
    }
    if (!isset($GLOBALS["ArticaWatchDogList"][$APP_NAME])) {
        $GLOBALS["ArticaWatchDogList"][$APP_NAME] = 1;
    }
    if ($GLOBALS["ArticaWatchDogList"][$APP_NAME] == null) {
        $GLOBALS["ArticaWatchDogList"][$APP_NAME] = 1;
    }

    if (systemMaxOverloaded()) {
        $array_load = sys_getloadavg();
        $internal_load = $array_load[0];
        $GLOBALS["CLASS_UNIX"]->send_email_events("Artica Watchdog start $APP_NAME is not performed (load $internal_load)", "System is very overloaded ($internal_load) all watchdog tasks are stopped and waiting a better time!", "system");
        return;
    }

    if ($GLOBALS["ArticaWatchDogList"][$APP_NAME] == 1) {

        $cmd = "{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.watchdog.php --start-process \"$APP_NAME\" \"$cmd\" >/dev/null 2>&1 &";
        events("WATCHDOG: running $APP_NAME ($cmd)", basename(__FILE__));
        shell_exec2($cmd);

    }

}



function disks_monitor()
{
    $HardDisksWatchDog = unserialize(@file_get_contents('/etc/artica-postfix/settings/Daemons/HardDisksWatchDog'));
    if (!is_array($HardDisksWatchDog)) {
        return;
    }
    if (count($HardDisksWatchDog) == 0) {
        return;
    }
    include_once(dirname(__FILE__) . "/ressources/class.disk.monitor.inc");
    $monitor = new disk_monitor();
    $monitor->Scan();
}

// ========================================================================================================================================================
function wpa_supplicant()
{

    if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WPA_SUPPLIANT_INSTALLED")) == 0) {
        return null;
    }
    $enabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("WpaSuppliantEnabled");
    if ($enabled == null) {
        $enabled = 1;
    }
    $eth = trim($GLOBALS["CLASS_UNIX"]->GET_WIRELESS_CARD());
    if (trim($eth) == null) {
        $enabled = 0;
    }
    $master_pid = trim(@file_get_contents("/var/run/wpa_supplicant.$eth.pid"));
    $WifiAPEnable = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("WifiAPEnable");
    if ($WifiAPEnable <> 1) {
        $WifiAPEnable = 0;
    }
    if ($WifiAPEnable == 0) {
        $enabled = 0;
    }

    $l[] = "[APP_WPA_SUPPLIANT]";
    $l[] = "service_name=APP_WPA_SUPPLIANT";
    $l[] = "master_version=" . GetVersionOf("wpa_suppliant");
    $l[] = "service_cmd=wifi";
    $l[] = "service_disabled=$enabled";
    $l[] = "family=network";
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);
}

// ========================================================================================================================================================
function arp_spoof()
{
    if (!$GLOBALS["CLASS_USERS"]->ETTERCAP_INSTALLED) {
        return null;
    }
    $enabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArpSpoofEnabled");
    if (!is_numeric($enabled)) {
        $enabled = 0;
    }
    if ($enabled == 0) {
        return;
    }
    shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.arpspoof.php --start >/dev/null 2>&1 &");
}

// ========================================================================================================================================================
function fetchmail_version()
{
    if (isset($GLOBALS["fetchmail_version"])) {
        return $GLOBALS["fetchmail_version"];
    }
    $fetchmail = $GLOBALS["CLASS_UNIX"]->find_program("fetchmail");
    if (!is_file($fetchmail)) {
        return "0.0.0";
    }
    exec("$fetchmail -V 2>&1", $results);

    foreach ($results as $md => $line) {
        if (preg_match("#release\s+([0-9\.]+)#", $line, $re)) {
            $GLOBALS["fetchmail_version"] = $re[1];
            return $re[1];
        }
        if (preg_match("#version\s+([0-9\.]+)#", $line, $re)) {
            $GLOBALS["fetchmail_version"] = $re[1];
            return $re[1];
        }
    }

    return "0.0.0";
}





function fetchmail()
{


    if (!$GLOBALS["CLASS_USERS"]->fetchmail_installed) {
        return null;
    }
    $EnablePostfixMultiInstance = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance");
    $EnableFetchmailScheduler = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFetchmailScheduler");
    $enabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFetchmail");
    if (!is_numeric($enabled)) {
        $enabled = 0;
    }
    if (!is_numeric($EnableFetchmailScheduler)) {
        $EnableFetchmailScheduler = 0;
    }
    if ($EnableFetchmailScheduler == 1) {
        return null;
    }
    $DisableMessaging = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
    if ($DisableMessaging == 1) {
        $enabled = 0;
    }


    if ($EnablePostfixMultiInstance <> 1) {
        if (!is_file("/etc/fetchmailrc")) {
            $enabled = 0;
        }
        $master_pid = trim(@file_get_contents("/var/run/fetchmail.pid"));
        if (preg_match("#^([0-9]+)#", $master_pid, $re)) {
            $master_pid = $re[1];
        }
        $l[] = "[FETCHMAIL]";
        $l[] = "service_name=APP_FETCHMAIL";
        $l[] = "master_version=" . fetchmail_version();
        $l[] = "service_cmd=/etc/init.d/fetchmail";
        $l[] = "service_disabled=$enabled";
        $l[] = "watchdog_features=1";
        $l[] = "family=mailbox";

        if ($enabled == 1) {
            $fetchmail_count_server = fetchmail_count_server();
            if ($GLOBALS["VERBOSE"]) {
                echo "fetchmail_count_server: $fetchmail_count_server\n";
            }

            if ($fetchmail_count_server > 0) {
                if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
                    if (!$GLOBALS["DISABLE_WATCHDOG"]) {
                        shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.initslapd.php --fetchmail >/dev/null 2>&1");
                        shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.fetchmail.php --start >/dev/null 2>&1 &");
                    }
                    $l[] = "running=0\ninstalled=1";
                    $l[] = "";
                    return implode("\n", $l);

                }
            }
        }

        if ($enabled == 0) {
            return implode("\n", $l);
        }
        if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            $l[] = "running=0";
        } else {
            $l[] = "running=1";
            $l[] = GetMemoriesOf($master_pid);
        }
        $l[] = "";
    } else {
        $enabled = 1;
    }

    $master_pid = trim(@file_get_contents("/etc/artica-postfix/exec.fetmaillog.php.pid"));
    $l[] = "[FETCHMAIL_LOGGER]";
    $l[] = "service_name=APP_FETCHMAIL_LOGGER";
    $l[] = "master_version=" . fetchmail_version();
    $l[] = "service_cmd=fetchmail-logger";
    $l[] = "service_disabled=$enabled";
    $l[] = "watchdog_features=1";

    if ($enabled == 1) {
        if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            $fetchmail_count_server = fetchmail_count_server();
            if ($GLOBALS["VERBOSE"]) {
                echo "fetchmail_count_server: $fetchmail_count_server\n";
            }
            if ($fetchmail_count_server > 0) {
                WATCHDOG("APP_FETCHMAIL_LOGGER", "fetchmail-logger");
                $l[] = "running=0\ninstalled=1";
                $l[] = "";
                return implode("\n", $l);
                return;
            } else {
                return implode("\n", $l);
            }
        }
    }

    if ($enabled == 0) {
        return implode("\n", $l);
    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);
}

function fetchmail_count_server()
{
    $f = explode("\n", @file_get_contents("/etc/fetchmailrc"));
    $count = 0;
    foreach ($f as $line) {
        if (preg_match("#^poll\s+(.+)#", $line)) {
            $count = $count + 1;
        }
    }
    return $count;
}

//========================================================================================================
function framework():bool{

    if(is_file("/etc/init.d/artica-framework")){
        squid_admin_mysql(1,"{start_migration} 4.40/4.50 {removing} {APP_FRAMEWORK}","Not a necessary service",__FILE__,__LINE__);
        $GLOBALS["CLASS_UNIX"]->framework_exec("exec.framework.php --migration");
    }
    return true;
}

//================================================================================================

function checksyslog()
{

    $syslogpath = "/var/log/syslog";
    $size = @filesize($syslogpath);
    if ($GLOBALS["VERBOSE"]) {
        echo "$syslogpath -> Size:$size\n";
    }
    if ($size < 5) {
        squid_admin_mysql(1, "{warning} $syslogpath $size Bytes, restarting Syslog",
            "Suspicious system log size, restarting syslog daemon", __FILE__, __LINE__);
        $GLOBALS["CLASS_UNIX"]->RESTART_SYSLOG(true);
    }
}


function philesight()
{
    $pids = array();
    $pgrep = $GLOBALS["CLASS_UNIX"]->find_program("pgrep");
    if ($GLOBALS["VERBOSE"]) {
        echo __FUNCTION__ . "/" . __LINE__ . "\n";
    }
    exec("$pgrep -l -f \"ruby.*?philesight\" 2>&1", $results);
    foreach ($results as $num => $line) {
        if (preg_match("#pgrep#", $line)) {
            continue;
        }
        if (!preg_match("#^([0-9]+)\s+#", $line, $re)) {
            if ($GLOBALS["VERBOSE"]) {
                echo "No match.. <$line>\n";
            }
        }
        if ($GLOBALS["VERBOSE"]) {
            echo "match..$line\n";
        }
        $pids[$re[1]] = true;

    }

    if (count($pids) == 0) {
        return;
    }
    foreach ($pids as $pid => $line) {
        $time = $GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
        if ($GLOBALS["VERBOSE"]) {
            echo "$pid -> {$time}mn\n";
        }
        if ($time > 30) {
            squid_admin_mysql(1, "Killing philesight process $pid {running} {since} {$time}mn", null, __FILE__, __LINE__);
            unix_system_kill_force($pid);

        }
    }
}

function ucarp_version()
{
    if (isset($GLOBALS["ucarp_version"])) {
        return $GLOBALS["ucarp_version"];
    }
    $ucarp = $GLOBALS["CLASS_UNIX"]->find_program("ucarp");
    exec("$ucarp --help 2>&1", $results);
    foreach ($results as $line) {
        $line = trim($line);
        if ($line == null) {
            continue;
        }
        if (preg_match("#^ucarp\s+([0-9\.]+)\s+#", $line, $re)) {
            $GLOBALS["ucarp_version"] = $re[1];
            return $GLOBALS["ucarp_version"];
        }
    }

    return 0;

}

function ucarp()
{
    if ($GLOBALS["VERBOSE"]) {
        echo " ********************************** UCARP ******************\n";
    }
    $ucarp = $GLOBALS["CLASS_UNIX"]->find_program("ucarp");
    if (!is_file($ucarp)) {
        if ($GLOBALS["VERBOSE"]) {
            echo "No such binary\n";
        }

        return;
    }
    $enabled = 1;
    $HEAD = "UCARP_SLAVE";
    if (!is_file("/usr/share/ucarp/ETH_LIST")) {
        if ($GLOBALS["VERBOSE"]) {
            echo " */usr/share/ucarp/ETH_LIST no such file\n";
        }
        return;
    }
    if (is_file("/usr/share/ucarp/Master")) {
        $HEAD = "UCARP_MASTER";
    }

    $ETHS = unserialize(@file_get_contents("/usr/share/ucarp/ETH_LIST"));
    foreach ($ETHS as $Interface => $ucarpcmdLINE) {
        $PID = $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$ucarp.*?--interface=$Interface");
        if ($GLOBALS["CLASS_UNIX"]->process_exists($PID)) {
            $l[] = "[$HEAD]";
            $l[] = "service_name=$HEAD";
            $l[] = "master_version=" . ucarp_version();
            $l[] = "service_cmd=/etc/init.d/artica-failover";
            $l[] = "service_disabled=1";
            $l[] = "watchdog_features=1";
            $l[] = "running=1";
            $l[] = GetMemoriesOf($PID);
            $l[] = "";
            return implode("\n", $l);

        }

    }

    $l[] = "[$HEAD]";
    $l[] = "service_name=$HEAD";
    $l[] = "master_version=" . ucarp_version();
    $l[] = "service_cmd=/etc/init.d/artica-failover";
    $l[] = "service_disabled=1";
    $l[] = "watchdog_features=1";
    $l[] = "running=0";
    $l[] = "";
    return implode("\n", $l);

}

function ChecksRoutes()
{
    $CacheFileTime = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".time";
    $globalStatusIniTime = $GLOBALS["CLASS_UNIX"]->file_time_min($CacheFileTime);
    if ($globalStatusIniTime < 1) {
        return;
    }

    @unlink($CacheFileTime);
    @file_put_contents($CacheFileTime, time());

    $ip = $GLOBALS["CLASS_UNIX"]->find_program("ip");
    exec("$ip route 2>&1", $results);
    $c = 0;
    foreach ($results as $num => $ligne) {
        $ligne = trim($ligne);
        if ($ligne == null) {
            continue;
        }
        $c++;
    }

    if ($c > 0) {
        return;
    }
    events_syslog("kernel: [  Artica-Net] Start Network [artica-ifup] (" . basename(__FILE__) . "/" . __LINE__ . ")");
    shell_exec2("/usr/sbin/artica-phpfpm-service -restart-network --script=" . basename(__FILE__) . "/" . __FUNCTION__);
    squid_admin_mysql(2, "No route defined", "I can't see routes in\nip route\n" . @implode("\n", $results) . "\nNetwork will be reconfigured", __FUNCTION__, __FILE__, __LINE__, "network", 0);

}


//========================================================================================================================================================
function cyrus_imap()
{

    if (intval("/etc/artica-postfix/settings/Daemons/APP_CYRUS_INSTALLED") == 0) {
        return null;
    }
    $pid_path = $GLOBALS["CLASS_UNIX"]->LOCATE_CYRUS_PIDPATH();
    $master_pid = trim(@file_get_contents($pid_path));
    $enabled = 1;
    $EnableCyrusImap = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCyrusImap");
    if (!is_numeric($EnableCyrusImap)) {
        $EnableCyrusImap = 1;
    }
    $DisableMessaging = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
    if ($EnableCyrusImap == 0) {
        $enabled = 0;
    }
    if ($DisableMessaging == 1) {
        $enabled = 0;
    }

    $l[] = "[CYRUSIMAP]";
    $l[] = "service_name=APP_CYRUS";
    $l[] = "master_version=" . $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CYRUS_VERSION");
    $l[] = "service_cmd=/etc/init.d/cyrus-imapd";
    $l[] = "service_disabled=1";
    $l[] = "pid_path=$pid_path";
    $l[] = "watchdog_features=1";
    $l[] = "family=mailbox";
    $l[] = "service_disabled=$enabled";
    if ($enabled == 0) {
        return implode("\n", $l);
        return;
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $cmd = trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.cyrus-imapd.php --start >/dev/null 2>&1 &");
        shell_exec2($cmd);
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);

    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    if (is_file("/var/run/saslauthd/mux")) {
        @chmod("/var/run/saslauthd/mux", 0777);
    }


    $timefile = "/etc/artica-postfix/croned.1/exec.cyrus.php.DirectorySize.time";
    $filetim = $GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
    if ($filetim > 240) {
        shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.cyrus.php --DirectorySize >/dev/null 2>&1 &");
    }

    return implode("\n", $l);


}

function Dump2json($initext):string{
    if(!$GLOBALS["JSON"]){return $initext;}
    $ini=new Bs_IniHandler();
    $ini->loadString($initext);
    return strval(json_encode($ini->_params));

}

function cyrus_imap_pid()
{
    $pidpath = $GLOBALS["CLASS_UNIX"]->CYRUS_PID_PATH();
    $pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidpath);
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
        return $GLOBALS["CLASS_UNIX"]->PIDOF($GLOBALS["CLASS_UNIX"]->CYRUS_DAEMON_BIN_PATH());
    }
    return $pid;


}

//========================================================================================================================
function syslogger_pid()
{
    $pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file("/etc/artica-postfix/exec.syslog.php.pid");
    if ($GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
        return $pid;
    }
    return $GLOBALS["CLASS_UNIX"]->PIDOF("/usr/sbin/syslog-tail");

}

function syslogger()
{
    if (!is_file("/usr/share/artica-postfix/exec.syslog.php")) {
        return;
    }
    CheckCallable();
    $pid_path = "/etc/artica-postfix/exec.syslog.php.pid";
    $master_pid = syslogger_pid();
    if (is_file("/etc/init.d/syslog")) {
        @chmod("/etc/init.d/syslog", 0755);
    }

    $l[] = "[APP_SYSLOGER]";
    $l[] = "service_name=APP_SYSLOGER";
    $l[] = "master_version=" . trim(@file_get_contents(dirname(__FILE__) . "/VERSION"));
    $l[] = "service_cmd=/etc/init.d/artica-syslog";
    $l[] = "service_disabled=1";
    $l[] = "watchdog_features=1";
    $l[] = "family=system";
    $l[] = "pid_path=$pid_path";


    $size = $GLOBALS["CLASS_UNIX"]->file_size("/usr/share/artica-postfix/ressources/logs/php.log");
    if ($size > 104857600) {
        @unlink("/usr/share/artica-postfix/ressources/logs/php.log");
    }

    if (!$GLOBALS["DISABLE_WATCHDOG"]) {
        if (is_file("/etc/artica-postfix/settings/Daemons/NET_PINGABLE")) {
            if (!is_dir("/etc/artica-postfix/cron.ping")) {
                @mkdir("/etc/artica-postfix/cron.ping", 0755, true);
            }
            $NET_PINGABLE = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NET_PINGABLE"));
            if (is_array($NET_PINGABLE)) {
                foreach ($NET_PINGABLE as $net => $interval) {
                    if (intval($interval) < 5) {
                        $interval = 15;
                    }
                    $filetime = "/etc/artica-postfix/cron.ping/" . md5($net);
                    if ($GLOBALS["CLASS_UNIX"]->file_time_min($filetime) < $interval) {
                        continue;
                    }
                    shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.nmapscan.php --pingeable \"$net\"");
                }
            }
        }
    }


    $SquidPerformance = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
    if ($SquidPerformance > 2) {
        return null;
    }


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        shell_exec2("/etc/init.d/artica-syslog restart");
        $l[] = "";
        return implode("\n", $l);
        events("done", __FUNCTION__, __LINE__);
        return null;
    }


    if (!is_file("/var/log/artica-postfix/syslogger.debug")) {
        events("restart sysloger", __FUNCTION__, __LINE__);
        $GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/artica-syslog restart");
    }


    $timelog = $GLOBALS["CLASS_UNIX"]->file_time_min("/var/log/artica-postfix/syslogger.debug");
    events("/var/log/artica-postfix/syslogger.debug = $timelog minutes TTL", __FUNCTION__, __LINE__);

    $l[] = "running=1";
    if ($GLOBALS ["VERBOSE"]) {
        echo "GetMemoriesOf -> $master_pid\n";
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";

    if (!$GLOBALS["DISABLE_WATCHDOG"]) {
        $time = file_time_min("/var/log/artica-postfix/syslogger.debug");
        //writelogs("LOG TIME: $time",__FUNCTION__,__FILE__,__LINE__);
        if ($time > 5) {
            $GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("/etc/init.d/artica-syslog restart");
        }
    }

    return implode("\n", $l);

}

//=========================================================================================================
function iptables_version()
{
    if (isset($GLOBALS["iptables_version"])) {
        return $GLOBALS["iptables_version"];
    }
    $bin_path = $GLOBALS["CLASS_UNIX"]->find_program("iptables");
    exec("$bin_path -V 2>&1", $results);
    foreach ($results as $pid => $line) {
        if (preg_match("#iptables v([0-9\.]+)#", $line, $re)) {
            $GLOBALS["iptables_version"] = $re[1];
            return $GLOBALS["iptables_version"];
        }
    }
}

//============================================================================================
//=============================================================================================
function clamscan()
{
    $bin_path = $GLOBALS["CLASS_UNIX"]->find_program("clamscan");
    if ($bin_path == null) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClamScanInstalled", 0);
        return null;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClamScanInstalled", 1);
    $master_pid = 1;


    $l[] = "[CLAMSCAN]";
    $l[] = "service_name=APP_CLAMSCAN";
    $l[] = "master_version=" . GetVersionOf("clamav");
    $l[] = "service_cmd=";

    $l[] = "family=system";
    $l[] = "pid_path=";
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $l[] = "service_disabled=0";
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = "service_disabled=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";

    return implode("\n", $l);

}



function YaraRules(){
    $EnableClamavYaraRules = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavYaraRules"));
    if ($EnableClamavYaraRules == 1) {
        if (is_file("/etc/artica-postfix/EnableClamavYaraRulesRemove")) {
            @unlink("/etc/artica-postfix/EnableClamavYaraRulesRemove");
        }
        return;
    }
    $hash = $GLOBALS["CLASS_UNIX"]->DirFiles("/var/lib/clamav", "\.yar$");
    events(count($hash) . " Yara rules in /var/lib/clamav", __FUNCTION__, __LINE__);
    if (count($hash) > 1) {
        $time = $GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN("/etc/artica-postfix/EnableClamavYaraRulesRemove");
        events(count($hash) . " Yara rules Timeout:{$time}Mn/5mn", __FUNCTION__, __LINE__);
        if ($time > 5) {
            @unlink("/etc/artica-postfix/EnableClamavYaraRulesRemove");
            @file_put_contents("/etc/artica-postfix/EnableClamavYaraRulesRemove", time());
            squid_admin_mysql(0, "Yara rules are disabled but " . count($hash) . " scripts still stored [action=remove]", null, __FILE__, __LINE__);
            $cmd = trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.yararules.update.php --remove >/dev/null 2>&1 &");
            events(count($hash) . " $cmd", __FUNCTION__, __LINE__);
            shell_exec2($cmd);
        }
    }

}

//====================================================================================


//========================================================================================================================================================

function NetAdsWatchdog()
{

    $GLOBALS["PHP5"] = LOCATE_PHP5_BIN2();
    $EnableSambaActiveDirectory = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSambaActiveDirectory");
    if (!is_numeric($EnableSambaActiveDirectory)) {
        return;
    }
    if ($EnableSambaActiveDirectory <> 1) {
        return;
    }
    $net = $GLOBALS["CLASS_UNIX"]->LOCATE_NET_BIN_PATH();
    if (!is_file($net)) {
        return;
    }
    exec("$net ads info 2>&1", $results);
    foreach ($results as $index => $line) {
        if (preg_match("#^(.+?):(.+)#", trim($line), $re)) {
            events($line, __FUNCTION__, __LINE__);
            $array[trim($re[1])] = trim($re[2]);
        }
    }

    $log = @implode("\n", $results);
    unset($results);
    if ($array["KDC server"] == null) {
        exec("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.samba.php --build 2>&1", $results);

        $text = "Watchdog Daemon has detected an unlinked AD connection.:
		$log
		This is the result of re-connect operation:
		" . @implode("\n", $results);

        $GLOBALS["CLASS_UNIX"]->send_email_events(
            "Connection to Active Directory Failed (Action reconnect)",
            $text,
            "system"

        );
    }

}

//========================================================================================================================================================

function ipsec_init()
{
    if (is_file("/etc/init.d/ipsec")) {
        return "/etc/init.d/ipsec";
    }
}

function ipsec_pid_path()
{
    if (is_file("/var/run/charon.pid")) {
        return "/var/run/charon.pid";
    }
}

function ipsec_binpath():string{
    if (is_file("/usr/lib/ipsec/charon")) {
        return "/usr/lib/ipsec/charon";
    }
    return "";
}

function ipsec_vici_binpath():string{
    if (is_file("/usr/share/artica-postfix/strongswan-vici.py")) {
        return "/usr/share/artica-postfix/strongswan-vici.py";
    }
    return "";
}

function ipsec_vici_stats_binpath():string{
    if (is_file("/usr/share/artica-postfix/bin/strongswan-vici-stats.py")) {
        return "/usr/share/artica-postfix/bin/strongswan-vici-stats.py";
    }
    return "";
}

//KEEPALIVED
function keepalived_binpath(){
    if (is_file("/usr/sbin/keepalived")) {
        return "/usr/sbin/keepalived";
    }
}

function keepalived_pid_path():string{
    if (is_file("/var/run/keepalived/keepalived.pid")) {
        return "/var/run/keepalived/keepalived.pid";
    }

    return "/var/run/keepalived/keepalived.pid";
}

function keepalived_init()
{
    if (is_file("/etc/init.d/keepalived")) {
        return "/etc/init.d/keepalived";
    }
}

function keepalived()
{
    $bin_path = keepalived_binpath();
    if (!is_file((string) $bin_path)) {
        return;
    }
    $APP_KEEPALIVED_ENABLE = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE");
    if (!is_numeric($APP_KEEPALIVED_ENABLE)) {
        $APP_KEEPALIVED_ENABLE = 0;
    }
    if ($APP_KEEPALIVED_ENABLE == 0) {
        $APP_KEEPALIVED_ENABLE = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_ENABLE_SLAVE");
        if (!is_numeric($APP_KEEPALIVED_ENABLE)) {
            $APP_KEEPALIVED_ENABLE = 0;
        }
    }
    $pid_path = keepalived_pid_path();
    $master_pid = trim(@file_get_contents($pid_path));
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
    }

    $l[] = "[APP_KEEPALIVED]";
    $l[] = "service_name=APP_KEEPALIVED";
    $l[] = "master_version=0.00";
    $l[] = "service_cmd=";
    $l[] = "service_disabled=$APP_KEEPALIVED_ENABLE";
    $l[] = "pid_path=$pid_path";
    $l[] = "family=system";
    $l[] = "watchdog_features=1";

    $l[] = "";

    if ($APP_KEEPALIVED_ENABLE == 0) {
        if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            $init = keepalived_init();
            if (!is_null($init)) {
                if (is_file($init))
                    $GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("$init stop");
            }
        }
    }

    if ($APP_KEEPALIVED_ENABLE == 0) {
        $l[] = "";
        return implode("\n", $l);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $l[] = "";
        return implode("\n", $l);
        return;
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";


    return implode("\n", $l);
}


//END KEEPALIVED

function iptables()
{
}

function openvpnserver()
{
    //if(!$GLOBALS["CLASS_USERS"]->IPSEC_INSTALLED){return;}
    $bin_path = $GLOBALS["CLASS_UNIX"]->find_program("openvpn");
    if (!is_file((string) $bin_path)) {
        return;
    }
    $EnableOPENVPN = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenVPNServer");
    if (!is_numeric($EnableOPENVPN)) {
        $EnableOPENVPN = 0;
    }
    $pid_path = '/var/run/openvpn/openvpn-server.pid';
    $master_pid = trim(@file_get_contents($pid_path));
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
    }

    $l[] = "[APP_OPENVPN]";
    $l[] = "service_name=APP_OPENVPN";
    $l[] = "master_version=0.00";
    $l[] = "service_cmd=";
    $l[] = "service_disabled=$EnableOPENVPN";
    $l[] = "pid_path=$pid_path";
    $l[] = "family=system";
    $l[] = "watchdog_features=1";

    $l[] = "";

    if ($EnableOPENVPN == 0) {
        if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            $init = '/etc/init.d/openvpn-server';
            if (is_file($init))
                $GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("$init stop");
        }
    }

    if ($EnableOPENVPN == 0) {
        $l[] = "";
        return implode("\n", $l);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";


    return implode("\n", $l);
}

function ipsec_vici()
{
    //if(!$GLOBALS["CLASS_USERS"]->IPSEC_INSTALLED){return;}
    $bin_path = ipsec_vici_binpath();
    if (!is_file((string) $bin_path)) {
        return;
    }
    $EnableIPSEC = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStrongswanServer");
    if (!is_numeric($EnableIPSEC)) {
        $EnableIPSEC = 0;
    }
    $pid_path = '/var/run/strongswan-stats.pid';
    $master_pid = trim(@file_get_contents($pid_path));
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
    }

    $l[] = "[APP_STRONGSWAN_VICI]";
    $l[] = "service_name=APP_STRONGSWAN_VICI";
    $l[] = "master_version=0.00";
    $l[] = "service_cmd=";
    $l[] = "service_disabled=$EnableIPSEC";
    $l[] = "pid_path=$pid_path";
    $l[] = "family=system";
    $l[] = "watchdog_features=1";

    $l[] = "";

    if ($EnableIPSEC == 0) {
        if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            $init = '/etc/init.d/ipsec-stats';
            if (is_file($init))
                $GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("$init stop");
        }
    }

    if ($EnableIPSEC == 0) {
        $l[] = "";
        return implode("\n", $l);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";


    return implode("\n", $l);
}

function ipsec_vici_parser()
{
    //if(!$GLOBALS["CLASS_USERS"]->IPSEC_INSTALLED){return;}
    $bin_path = ipsec_vici_stats_binpath();
    if (!is_file((string) $bin_path)) {
        return;
    }
    $EnableIPSEC = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStrongswanServer");
    if (!is_numeric($EnableIPSEC)) {
        $EnableIPSEC = 0;
    }
    $pid_path = '/var/run/strongswan-vici-stats.pid';
    $master_pid = trim(@file_get_contents($pid_path));
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
    }

    $l[] = "[APP_STRONGSWAN_VICI_PARSER]";
    $l[] = "service_name=APP_STRONGSWAN_VICI_PARSER";
    $l[] = "master_version=0.00";
    $l[] = "service_cmd=";
    $l[] = "service_disabled=$EnableIPSEC";
    $l[] = "pid_path=$pid_path";
    $l[] = "family=system";
    $l[] = "watchdog_features=1";

    $l[] = "";

    if ($EnableIPSEC == 0) {
        if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            $init = '/etc/init.d/ipsec-stats';
            if (is_file($init))
                $GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("$init stop");
        }
    }

    if ($EnableIPSEC == 0) {
        $l[] = "";
        return implode("\n", $l);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {

        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";


    return implode("\n", $l);
}

function ipsec()
{
    //if(!$GLOBALS["CLASS_USERS"]->IPSEC_INSTALLED){return;}
    $bin_path = ipsec_binpath();
    if (!is_file((string) $bin_path)) {
        return;
    }
    $EnableIPSEC = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStrongswanServer");
    if (!is_numeric($EnableIPSEC)) {
        $EnableIPSEC = 0;
    }
    $pid_path = ipsec_pid_path();
    $master_pid = trim(@file_get_contents($pid_path));
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
    }

    $l[] = "[APP_STRONGSWAN]";
    $l[] = "service_name=APP_STRONGSWAN";
    $l[] = "master_version=0.00";
    $l[] = "service_cmd=";
    $l[] = "service_disabled=$EnableIPSEC";
    $l[] = "pid_path=$pid_path";
    $l[] = "family=system";
    $l[] = "watchdog_features=1";

    $l[] = "";

    if ($EnableIPSEC == 0) {
        if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            $init = ipsec_init();
            if (is_file($init))
                $GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET("$init stop");
        }
    }

    if ($EnableIPSEC == 0) {
        $l[] = "";
        return implode("\n", $l);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
       $l[] = "";
        return implode("\n", $l);
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";


    return implode("\n", $l);
}

function c_icap_master_enabled()
{
    $cicapbin = $GLOBALS["CLASS_UNIX"]->find_program("c-icap");

    if (!is_file($cicapbin)) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("C_ICAP_INSTALLED", 0);
        return 0;
    }


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("C_ICAP_INSTALLED", 1);
    if (is_file("/usr/lib/c_icap/dnsbl_tables.so")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("C_ICAP_DNSBL", 1);
    } else {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("C_ICAP_DNSBL", 0);
    }


    $SQUIDEnable = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
    $SquidDisableAllFilters = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableAllFilters");
    if (!is_numeric($SQUIDEnable)) {
        $SQUIDEnable = 1;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDEnable", 1);
    }


    $CicapEnabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled");
    $UnlockWebStats = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnlockWebStats");
    if (!is_numeric($UnlockWebStats)) {
        $UnlockWebStats = 0;
    }


    if (is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")) {
        $EnableStatisticsCICAPService = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatisticsCICAPService");
        if (!is_numeric($EnableStatisticsCICAPService)) {
            $EnableStatisticsCICAPService = 1;
        }
        $CicapEnabled = 1;
        if ($EnableStatisticsCICAPService == 0) {
            $CicapEnabled = 0;
        }
    }

    if ($SQUIDEnable == 0) {
        $CicapEnabled = 0;
    }
    if (!is_numeric($CicapEnabled)) {
        $CicapEnabled = 0;
    }
    if (!is_numeric($SquidDisableAllFilters)) {
        $SquidDisableAllFilters = 0;
    }

    if ($GLOBALS["CLASS_USERS"]->APP_KHSE_INSTALLED) {
        $KavMetascannerEnable = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("KavMetascannerEnable");
        if (!is_numeric($KavMetascannerEnable)) {
            $KavMetascannerEnable = 0;
        }
        if ($KavMetascannerEnable == 1) {
            $CicapEnabled = 1;
        }
    }

    if ($SquidDisableAllFilters == 1) {
        $CicapEnabled = 0;
    }

    if (!$GLOBALS["CLASS_USERS"]->MEM_HIGER_1G) {
        if ($GLOBALS["VERBOSE"]) {
            echo "MEM_HIGER_1G !!! FALSE\n";
        }
        if ($CicapEnabled == 1) {
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CicapEnabled", 0);
        }
        $CicapEnabled = 0;
    }

    return $CicapEnabled;
}



function iscsi_pid()
{
    $pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/iscsid.pid");
    if ($GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
        return $pid;
    }
    $Masterbin = $GLOBALS["CLASS_UNIX"]->find_program("iscsid");
    return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);

}

function iscsi2_version()
{
    if (isset($GLOBALS[__FUNCTION__])) {
        if ($GLOBALS["VERBOSE"]) {
            echo "iscsi2_version:: " . __FUNCTION__ . " -> {$GLOBALS[__FUNCTION__]}\n";
        }
        return $GLOBALS[__FUNCTION__];
    }
    $iscsiadm = $GLOBALS["CLASS_UNIX"]->find_program("iscsiadm");
    if ($GLOBALS["VERBOSE"]) {
        echo "iscsiadm -> $iscsiadm\n";
    }
    exec("$iscsiadm -V 2>&1", $results);
    foreach ($results as $line) {

        if (preg_match("#version\s+([0-9\.\-]+)#i", $line, $re)) {
            if ($GLOBALS["VERBOSE"]) {
                echo "iscsiadm -> {$re[1]}\n";
            }
            $GLOBALS[__FUNCTION__] = $re[1];
            if ($GLOBALS["VERBOSE"]) {
                echo "iscsiadm -> {$GLOBALS[__FUNCTION__]}\n";
            }
            return $GLOBALS[__FUNCTION__];
        }
        if ($GLOBALS["VERBOSE"]) {
            echo "iscsi_version: $line no MATCH\n";
        }
    }

}

function iscsi()
{

    $version = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ISCSI_VERSION"));
    if ($version == null) {
        $version = iscsi2_version();
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ISCSI_VERSION", $version);
    }
    if (!is_file("/etc/init.d/iscsitarget")) {
        return;
    }
    $master_pid = iscsi_pid();
    $l[] = "[APP_IETD]";
    $l[] = "service_name=APP_IETD";
    $l[] = "master_version=" . $GLOBALS["CLASS_SOCKETS"]->GET_INFO("ISCSI_VERSION");
    $l[] = "service_cmd=iscsi";
    $l[] = "service_disabled=1";
    $l[] = "pid_path=/var/run/iscsid.pid";
    $l[] = "watchdog_features=1";
    $l[] = "family=system";


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if (!$GLOBALS["DISABLE_WATCHDOG"]) {
            squid_admin_mysql(0, "{APP_IETD} stopped [{action}={start}]", null, __FILE__, __LINE__);
            system("/etc/init.d/iscsitarget start");
        }
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";

    $pidtime = "/etc/artica-postfix/pids/exec.iscsi.php.checknodes.time";
    $time_file = $GLOBALS["CLASS_UNIX"]->file_time_min($pidtime);
    if ($time_file > 3) {
        $prefixcmd = "{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]}";
        shell_exec2("$prefixcmd /usr/share/artica-postfix/exec.iscsi.php --checknodes >/dev/null 2>&1 &");
    }
    return implode("\n", $l);


}

function smartd_version()
{
    if (isset($GLOBALS["smartd_version"])) {
        return $GLOBALS["smartd_version"];
    }
    $bin_path = $GLOBALS["CLASS_UNIX"]->find_program("smartd");
    exec("$bin_path -V 2>&1", $results);
    if (preg_match("#release\s+([0-9\.]+)#", @implode("", $results), $re)) {
        $GLOBALS["smartd_version"] = $re[1];
        return $re[1];
    }
}

function smartd()
{
    if ($GLOBALS["CLASS_USERS"]->VMWARE_HOST) {
        return;
    }
    if ($GLOBALS["CLASS_USERS"]->VIRTUALBOX_HOST) {
        return;
    }
    if ($GLOBALS["CLASS_USERS"]->XEN_HOST) {
        return;
    }
    if ($GLOBALS["CLASS_USERS"]->HYPERV_HOST) {
        return;
    }

    $bin_path = $GLOBALS["CLASS_UNIX"]->find_program("smartd");
    if (!is_file((string) $bin_path)) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SMARTDiskInstalled", 0);
        return;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SMARTDiskInstalled", 1);


    if (!is_file("/etc/artica-postfix/settings/Daemons/EnableSMARTDisk")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSMARTDisk", 0);
    }
    $EnableSMARTDisk = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSMARTDisk"));

    $l[] = "[SMARTD]";
    $l[] = "service_name=APP_SMARTMONTOOLS";
    $l[] = "master_version=" . smartd_version();
    $l[] = "service_cmd=/etc/init.d/smartd";
    $l[] = "service_disabled=$EnableSMARTDisk";
    $l[] = "pid_path=none";
    $l[] = "watchdog_features=1";
    $l[] = "family=system";
    if ($EnableSMARTDisk == 0) {
        $l[] = "";
        return implode("\n", $l);
    }
    $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if (!$GLOBALS["DISABLE_WATCHDOG"]) {
            $cmd = trim("/etc/init.d/smartd start >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }

        $l[] = "";
        return implode("\n", $l);

    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";

    return implode("\n", $l);

}


//========================================================================================================================================================

function milter_dkim()
{


    if (!$GLOBALS["CLASS_USERS"]->MILTER_DKIM_INSTALLED) {
        return;
    }
    $EnableDKFilter = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDkimMilter");
    if (!is_numeric($EnableDKFilter)) {
        $EnableDKFilter = 0;
    }
    $DisconnectDKFilter = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisconnectDKFilter");
    if (!is_numeric($DisconnectDKFilter)) {
        $DisconnectDKFilter = 0;
    }


    $pid_path = "/var/run/dkim-milter/dkim-milter.pid";
    $master_pid = trim(@file_get_contents($pid_path));


    $l[] = "[APP_MILTER_DKIM]";
    $l[] = "service_name=APP_MILTER_DKIM";
    $l[] = "master_version=" . GetVersionOf("milterdkim");
    $l[] = "service_cmd=dkim-milter";
    $l[] = "service_disabled=$EnableDKFilter";
    $l[] = "pid_path=$pid_path";
    $l[] = "watchdog_features=1";
    $l[] = "family=postfix";

    if ($EnableDKFilter == 0) {
        $l[] = "";
        return implode("\n", $l);
    }


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $binpath = $GLOBALS["CLASS_UNIX"]->find_program("dkim-filter");
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if ($DisconnectDKFilter == 0) {
            WATCHDOG("APP_MILTER_DKIM", "dkim-milter");
        }
        $l[] = "";
        return implode("\n", $l);
        return;
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";

    return implode("\n", $l);

}

//========================================================================================================================================================

function dropbox()
{

    if (!is_file('/root/.dropbox-dist/dropbox')) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DropBoxInstalled", 0);
        $l[] = "";
        $l[] = "[APP_DROPBOX]";
        $l[] = "service_name=APP_DROPBOX";
        $l[] = "installed=0";
        $l[] = "service_disabled=0";
        $l[] = "";
        return @implode("\n", $l);
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DropBoxInstalled", 1);
    $EnableDropBox = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDropBox");
    if ($EnableDropBox == null) {
        $EnableDropBox = 0;
    }


    $pid_path = "/root/.dropbox/dropbox.pid";
    $master_pid = trim(@file_get_contents($pid_path));

    $l[] = "";
    $l[] = "[APP_DROPBOX]";
    $l[] = "service_name=APP_DROPBOX";
    $l[] = "master_version=" . GetVersionOf("dropbox");
    $l[] = "service_cmd=dropbox";
    $l[] = "service_disabled=$EnableDropBox";
    $l[] = "pid_path=$pid_path";
    $l[] = "watchdog_features=1";
    $l[] = "family=storage";

    if ($EnableDropBox == 0) {
        $l[] = "";
        return implode("\n", $l);
    }


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_DROPBOX", "dropbox");
        $l[] = "";
        return implode("\n", $l);
        return;
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";

    return implode("\n", $l);

}

//========================================================================================================================================================
function arkeiad_version()
{
    if (isset($GLOBALS[__FUNCTION__])) {
        return $GLOBALS[__FUNCTION__];
    }
    $line = exec("/opt/arkeia/bin/arktrans --version 2>&1");
    if (preg_match("#Backup\s+([0-9\.]+)#", $line, $re)) {
        $GLOBALS[__FUNCTION__] = $re[1];
        return $GLOBALS[__FUNCTION__];
    }
}

//========================================================================================================================================================

//========================================================================================================================================================

function arkeiad()
{
    if (!$GLOBALS["CLASS_USERS"]->APP_ARKEIA_INSTALLED) {
        return;
    }
    $EnableArkeia = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArkeia");
    if ($EnableArkeia == null) {
        $EnableArkeia = 0;
    }


    $pid_path = "/opt/arkeia/arkeiad/arkeiad.pid";
    $master_pid = trim(@file_get_contents($pid_path));


    $l[] = "[APP_ARKEIAD]";
    $l[] = "service_name=APP_ARKEIAD";
    $l[] = "master_version=" . arkeiad_version();
    $l[] = "service_cmd=arkeia";
    $l[] = "service_disabled=$EnableArkeia";
    $l[] = "pid_path=$pid_path";
    $l[] = "watchdog_features=1";
    $l[] = "family=storage";

    if ($EnableArkeia == 0) {
        $l[] = "";
        return implode("\n", $l);
    }


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_ARKEIAD", "arkeia");
        $l[] = "";
        return implode("\n", $l);
        return;
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";

    return implode("\n", $l);

}

//========================================================================================================================================================





function vde_all()
{
    $f = array();
    $files = $GLOBALS["CLASS_UNIX"]->DirFiles("/etc/init.d", "virtualswitch");
    foreach ($files as $num => $ligne) {
        if (preg_match("#virtualswitch-(.+)#", $ligne, $re)) {
            $f[] = vde_uniq($re[1]) . "\n";
        }
    }

    return @implode("\n", $f);
}

function vde_uniq($switch)
{

    $bin = $GLOBALS["CLASS_UNIX"]->find_program("vde_switch");
    if (!is_file($bin)) {
        return;
    }

    $switch_init = "/etc/init.d/virtualswitch-$switch";
    if (!is_file($switch_init)) {
        return;
    }
    $switch_pid = "/var/run/switch-$switch.pid";
    $VirtualSwitchEnabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("VirtualSwitchEnabled{$switch}");
    if (!is_numeric($VirtualSwitchEnabled)) {
        $VirtualSwitchEnabled = 1;
    }

    $master_pid = @file_get_contents($switch_pid);


    $l[] = "[VDE_$switch]";
    $l[] = "service_name=virtual_switch";
    $l[] = "service_cmd=$switch_init";
    $l[] = "master_version=" . vde_version();
    $l[] = "service_disabled=$VirtualSwitchEnabled";
    $l[] = "pid_path=$switch_pid";
    $l[] = "watchdog_features=1";
    $l[] = "family=network";
    if ($VirtualSwitchEnabled == 0) {
        if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            $cmd = trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.vde.php --stop-switch $switch >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }
        return implode("\n", $l) . vde_hook_uniq($switch);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if (!$GLOBALS["DISABLE_WATCHDOG"]) {
            $cmd = trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.vde.php --start-switch $switch >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }
        $l[] = "";
        return implode("\n", $l) . vde_hook_uniq($switch);
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";

    return implode("\n", $l) . vde_hook_uniq($switch);


}

function vde_hook_uniq($switch)
{

    $bin = $GLOBALS["CLASS_UNIX"]->find_program("vde_switch");
    if (!is_file($bin)) {
        return;
    }

    $switch_init = "/etc/init.d/virtualhook-$switch";
    if (!is_file($switch_init)) {
        return;
    }
    $switch_pid = "/var/run/switch{$switch}p.pid";
    $VirtualSwitchEnabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("VirtualSwitchEnabled{$switch}");
    if (!is_numeric($VirtualSwitchEnabled)) {
        $VirtualSwitchEnabled = 1;
    }

    $master_pid = @file_get_contents($switch_pid);


    $l[] = "[VDHOOK_$switch]";
    $l[] = "service_name=virtual_hook";
    $l[] = "service_cmd=$switch_init";
    $l[] = "master_version=" . vde_version();
    $l[] = "service_disabled=$VirtualSwitchEnabled";
    $l[] = "pid_path=$switch_pid";
    $l[] = "watchdog_features=1";
    $l[] = "family=network";
    if ($VirtualSwitchEnabled == 0) {
        if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            $cmd = trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.vde.php --pcapplug-stop $switch >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }
        return implode("\n", $l);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if (!$GLOBALS["DISABLE_WATCHDOG"]) {
            $cmd = trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.vde.php --pcapplug-start $switch >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }
        $l[] = "";
        return implode("\n", $l);
        return;
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";

    return implode("\n", $l);


}

function vde_version()
{
    if (isset($GLOBALS["vde_version"])) {
        return $GLOBALS["vde_version"];
    }
    $bin = $GLOBALS["CLASS_UNIX"]->find_program("vde_switch");
    if (!is_file($bin)) {
        if ($GLOBALS['VERBOSE']) {
            echo "vde_switch -> no such file\n";
        }
        return;
    }
    exec("$bin -v 2>&1", $array);
    foreach ($array as $pid => $line) {
        if (preg_match("#VDE\s+([0-9\.]+)#i", $line, $re)) {
            $GLOBALS["vde_version"] = $re[1];
            return $GLOBALS["vde_version"];
        }
        if ($GLOBALS['VERBOSE']) {
            echo "vde_switch(),  \"$line\", not found \n";
        }
    }
}

//========================================================================================================================================================


function php_fpm_version()
{
    if (isset($GLOBALS[__FUNCTION__])) {
        return $GLOBALS[__FUNCTION__];
    }
    $bin = $GLOBALS["CLASS_UNIX"]->APACHE_LOCATE_PHP_FPM();
    if (!is_file($bin)) {
        if ($GLOBALS['VERBOSE']) {
            echo "APACHE_LOCATE_PHP_FPM -> no such file\n";
        }
        return;
    }
    $array = array();
    if (is_file("/etc/artica-postfix/phpfpm_version.db")) {
        $array = unserialize(@file_get_contents("/etc/artica-postfix/phpfpm_version.db"));
    }
    $binMD5 = md5_file($bin);
    if ($binMD5 <> $array["binMD5"]) {
        $array["binMD5"] = $binMD5;
        exec("$bin -v 2>&1", $array);
        foreach ($array as $pid => $line) {
            if (preg_match("#^PHP\s+([0-9\.\-]+)#i", $line, $re)) {
                $GLOBALS[__FUNCTION__] = $re[1];
                $array["binversion"] = $re[1];
                syslog_status("php5-FPM: v{$array["binversion"]} - $binMD5", "artica-status");
                @file_put_contents("/etc/artica-postfix/phpfpm_version.db", serialize($array));
                return $re[1];
            }
            if ($GLOBALS['VERBOSE']) {
                echo "php_fpm_version(), $line, not found \n";
            }
        }
    }

    $GLOBALS[__FUNCTION__] = $array["binversion"];
    return $GLOBALS[__FUNCTION__];

}

function spwanfcgi_version()
{
    if (isset($GLOBALS[__FUNCTION__])) {
        return $GLOBALS[__FUNCTION__];
    }
    $bin = $GLOBALS["CLASS_UNIX"]->find_program("spawn-fcgi");
    if (!is_file($bin)) {
        if ($GLOBALS['VERBOSE']) {
            echo "spwanfcgi_version -> no such file\n";
        }
        return;
    }
    exec("$bin -h 2>&1", $array);
    foreach ($array as $pid => $line) {
        if (preg_match("#spawn-fcgi v([0-9\.\-]+)#i", $line, $re)) {
            $GLOBALS[__FUNCTION__] = $re[1];
            return $re[1];
        }
        if ($GLOBALS['VERBOSE']) {
            echo "spwanfcgi_version(), $line, not found \n";
        }
    }
}

function FPM_PID()
{
    $pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/php5-fpm.pid');
    if ($GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
        return $pid;
    }
    $bin = $GLOBALS["CLASS_UNIX"]->APACHE_LOCATE_PHP_FPM();
    return $GLOBALS["CLASS_UNIX"]->PIDOF($bin);
}

//===============================================================================================
function php_fpm()
{

    $bin = $GLOBALS["CLASS_UNIX"]->APACHE_LOCATE_PHP_FPM();
    if (!is_file($bin)) {
        if (!is_file("/etc/debian_version")) {
            return;
        }

        $StampFile = "/etc/artica-postfix/pids/php_fpm.install.time";
        $TimeFile = $GLOBALS["CLASS_UNIX"]->file_time_min($StampFile);
        if ($TimeFile > 1440) {
            @unlink($StampFile);
            @file_put_contents($StampFile, time());
            syslog_status("php5-FPM: Not installed , installing php5-fpm Time:{$TimeFile}Mn", "artica-status");
            $cmd = trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.apt-get.php --phpfpm-daemon >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }
        return;
    }

    $master_pid = FPM_PID();
    $EnablePHPFPM = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPFPM");

    $EnableArticaApachePHPFPM = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaApachePHPFPM");
    if (!is_numeric($EnableArticaApachePHPFPM)) {
        $EnableArticaApachePHPFPM = 0;
    }

    $EnablePHPFPMFreeWeb = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPFPMFreeWeb");
    if (!is_numeric($EnablePHPFPMFreeWeb)) {
        $EnablePHPFPMFreeWeb = 0;
    }

    $EnablePHPFPMFrameWork = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPFPMFrameWork");
    if (!is_numeric($EnablePHPFPMFrameWork)) {
        $EnablePHPFPMFrameWork = 0;
    }


    if ($EnableArticaApachePHPFPM == 1) {
        $EnablePHPFPM = 1;
    }
    if ($EnablePHPFPMFreeWeb == 1) {
        $EnablePHPFPM = 1;
    }
    if ($EnablePHPFPMFrameWork == 1) {
        $EnablePHPFPM = 1;
    }
    if ($EnableArticaApachePHPFPM == 1) {
        $EnablePHPFPM = 1;
    }
    if (!is_numeric($EnablePHPFPM)) {
        $EnablePHPFPM = 0;
    }
    if (is_file("/etc/artica-postfix/WORDPRESS_APPLIANCE")) {
        $EnablePHPFPM = 1;
        $EnablePHPFPMFreeWeb = 1;
    }


    $l[] = "[APP_PHPFPM]";
    $l[] = "service_name=APP_PHPFPM";
    $l[] = "master_version=" . php_fpm_version();
    $l[] = "service_disabled=$EnablePHPFPM";
    $l[] = "pid_path=/var/run/php5-fpm.pid";
    $l[] = "service_cmd=/etc/init.d/php5-fpm";
    $l[] = "watchdog_features=1";
    $l[] = "family=network";

    if (is_file("/etc/monit/conf.d/phpfpm.monitrc")) {
        @unlink("/etc/monit/conf.d/phpfpm.monitrc");
        $GLOBALS["CLASS_UNIX"]->MONIT_RELOAD();
    }

    if ($EnablePHPFPM == 0) {
        $l[] = "";
        return implode("\n", $l);
        return;
    }


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($bin);
    }
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if (!$GLOBALS["DISABLE_WATCHDOG"]) {
            syslog_status("php5-FPM: Not running starting php5-fpm", "artica-status");
            $cmd = trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.php-fpm.php --start >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";

    if ($EnableArticaApachePHPFPM == 1) {
        if (!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/php-fpm.sock")) {
            syslog_status("/var/run/php-fpm.sock: no such file, restarting php5-FPM", "artica-status");
            $cmd = trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.php-fpm.php --restart >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }
    }

    if ($EnablePHPFPMFreeWeb == 1) {
        if (!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/php-fpm-apache2.sock")) {
            syslog_status("/var/run/php-fpm-apache2.sock: no such file, restarting php5-FPM", "artica-status");
            $cmd = trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.php-fpm.php --restart >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }
    }


    return implode("\n", $l);
}

//========================================================================================================================================================
function syslog_status($text)
{
    $file = "artica-status";
    if (!function_exists('syslog')) {
        return null;
    }
    openlog($file, LOG_PID | LOG_PERROR, LOG_LOCAL0);
    syslog(LOG_INFO, $text);
    closelog();
}

//========================================================================================================================================================


//========================================================================================================================================================


function arkwsd()
{
    if (!$GLOBALS["CLASS_USERS"]->APP_ARKEIA_INSTALLED) {
        return;
    }
    $EnableArkeia = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArkeia");
    if ($EnableArkeia == null) {
        $EnableArkeia = 0;
    }


    $pid_path = "/opt/arkeia/arkeiad/arkwsd.pid";
    $master_pid = trim(@file_get_contents($pid_path));


    $l[] = "[APP_ARKWSD]";
    $l[] = "service_name=APP_ARKWSD";
    $l[] = "master_version=" . arkeiad_version();
    $l[] = "service_cmd=arkeia";
    $l[] = "service_disabled=$EnableArkeia";
    $l[] = "pid_path=$pid_path";
    $l[] = "watchdog_features=1";
    $l[] = "family=storage";

    if ($EnableArkeia == 0) {
        $l[] = "";
        return implode("\n", $l);
    }


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_ARKWSD", "arkeia");
        $l[] = "";
        return implode("\n", $l);
        return;
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";

    return implode("\n", $l);

}

//========================================================================================================================================================

function cron_pid()
{
    $pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/crond.pid");
    if ($GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
        return $pid;
    }

    $cron = $GLOBALS["CLASS_UNIX"]->find_program("cron");
    return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($cron);
}

function cron()
{

    $master_pid = cron_pid();
    if (!is_dir("/var/spool/cron")) {
        @mkdir("/var/spool/cron", 0755, true);
    }

    $l[] = "[APP_CRON]";
    $l[] = "service_name=APP_CRON";
    $l[] = "master_version=1.0";
    $l[] = "service_cmd=/etc/init.d/cron";
    $l[] = "service_disabled=1";

    $l[] = "watchdog_features=1";
    $l[] = "family=system";


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if (!$GLOBALS["DISABLE_WATCHDOG"]) {
            ToSyslog("Cron is not started -> run it");
            shell_exec2("/etc/init.d/cron start");
        }
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";

    return implode("\n", $l);

}

//========================================================================================================================================================


function pptpd()
{
    if (!$GLOBALS["CLASS_USERS"]->PPTPD_INSTALLED) {
        return;
    }
    $EnablePPTPDVPN = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePPTPDVPN");
    if ($EnablePPTPDVPN == null) {
        $EnablePPTPDVPN = 0;
    }
    $pid_path = "/var/run/pptpd.pid";
    $master_pid = trim(@file_get_contents($pid_path));

    $l[] = "[APP_PPTPD]";
    $l[] = "service_name=APP_PPTPD";
    $l[] = "master_version=" . GetVersionOf("pptpd");
    $l[] = "service_cmd=pptpd";
    $l[] = "service_disabled=$EnablePPTPDVPN";
    $l[] = "pid_path=$pid_path";
    $l[] = "watchdog_features=1";
    $l[] = "family=network";
    if ($EnablePPTPDVPN == 0) {
        $l[] = "";
        return implode("\n", $l);
    }
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_PPTPD", "pptpd");
        $l[] = "";
        return implode("\n", $l);
        return;
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);

}

function ddclient()
{
    $binpath = $GLOBALS["CLASS_UNIX"]->find_program("ddclient");
    if (!is_file($binpath)) {
        return null;
    }
    $EnableDDClient = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDDClient");
    if ($EnableDDClient == null) {
        $EnableDDClient = 0;
    }
    $pid_path = "/var/run/ddclient.pid";
    $master_pid = trim(@file_get_contents($pid_path));

    $l[] = "[APP_DDCLIENT]";
    $l[] = "service_name=APP_DDCLIENT";
    $l[] = "master_version=" . GetVersionOf("ddclient");
    $l[] = "service_cmd=apt-mirror";
    $l[] = "service_disabled=$EnableDDClient";
    $l[] = "pid_path=$pid_path";
    $l[] = "watchdog_features=1";
    $l[] = "family=network";
    if ($EnableDDClient == 0) {
        $l[] = "";
        return implode("\n", $l);
    }
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_DDCLIENT", "ddclient");
        $l[] = "";
        return implode("\n", $l);

    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);

}

function cluebringer()
{

}

//========================================================================================================================================================
function sabnzbdplus()
{
    $binary = $GLOBALS["CLASS_UNIX"]->find_program("sabnzbdplus");
    if (!is_file($binary)) {
        if ($GLOBALS["VERBOSE"]) {
            echo __FUNCTION__ . " APP_SABNZBDPLUS_INSTALLED = FALSE\n";
        }
        return;
    }
    $EnableSabnZbdPlus = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSabnZbdPlus");
    if ($EnableSabnZbdPlus == null) {
        $EnableSabnZbdPlus = 0;
    }

    if ($GLOBALS["VERBOSE"]) {
        echo __FUNCTION__ . " EnableSabnZbdPlus = $EnableSabnZbdPlus\n";
    }
    if (is_file("/usr/share/sabnzbdplus/SABnzbd.py")) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("SABnzbd.py");
        $binary = "SABnzbd.py";
    } else {
        $binary = $GLOBALS["CLASS_UNIX"]->find_program("sabnzbdplus");
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($binary);
    }

    $l[] = "[APP_SABNZBDPLUS]";
    $l[] = "service_name=APP_SABNZBDPLUS";
    $l[] = "master_version=" . GetVersionOf("sabnzbdplus");
    $l[] = "service_cmd=sabnzbdplus";
    $l[] = "service_disabled=$EnableSabnZbdPlus";
    $l[] = "pid_path=pidof $binary";
    $l[] = "watchdog_features=1";
    $l[] = "family=samba";
    if ($EnableSabnZbdPlus == 0) {
        $l[] = "";
        return implode("\n", $l);
    }
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_SABNZBDPLUS", "sabnzbdplus");
        $l[] = "";
        return implode("\n", $l);

    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);
}

//========================================================================================================
function exim4():bool{
    $bin = $GLOBALS["CLASS_UNIX"]->find_program("exim4");
    if (!is_file($bin)) {return true;}


    $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($bin);


    if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $cmd = "{$GLOBALS["KILLBIN"]} -9 $master_pid >/dev/null 2>&1";
        echo " *****  *****  KILLING EXIM **** **** $cmd\n";
        shell_exec2($cmd);
    }
    return true;
}

//========================================================================================================================================================
//========================================================================================================================================================
function conntrackd_version()
{
    if (isset($GLOBALS["conntrackd_version"])) {
        return $GLOBALS["conntrackd_version"];
    }
    $bin_path = $GLOBALS["CLASS_UNIX"]->find_program("conntrackd");
    exec("$bin_path -v 2>&1", $results);
    foreach ($results as $pid => $line) {
        if (preg_match("#v([0-9\.]+)#", $line, $re)) {
            $GLOBALS["conntrackd_version"] = $re[1];
            return $GLOBALS["conntrackd_version"];
        }
    }
}

//========================================================================================================================================================
function conntrackd()
{
    if (!is_file("/etc/init.d/artica-postfix")) {
        return;
    }

    $bin = $GLOBALS["CLASS_UNIX"]->find_program("conntrackd");
    $EnableConntrackd = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableConntrackd");
    if (!is_numeric($EnableConntrackd)) {
        $EnableConntrackd = 0;
    }
    $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($bin, true);
    if ($EnableConntrackd == 0) {
        if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            shell_exec2("/etc/init.d/conntrackd stop");
        }
    }

    $l[] = "[APP_CONNTRACKD]";
    $l[] = "service_name=APP_CONNTRACKD";
    $l[] = "master_version=" . conntrackd_version();;
    $l[] = "service_disabled=$EnableConntrackd";
    $l[] = "watchdog_features=1";
    $l[] = "installed=1";
    $l[] = "family=system";
    $l[] = "service_cmd=/etc/init.d/conntrackd";
    if ($EnableConntrackd == 0) {
        if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            $cmd = "{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}/etc/init.d/conntrackd stop >/dev/null 2>&1 &";
            events("$cmd", __FUNCTION__, __LINE__);
            shell_exec2($cmd);

        }
        $l[] = "";
        return implode("\n", $l);
        return;
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if (!$GLOBALS["DISABLE_WATCHDOG"]) {
            $cmd = trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.conntrackd.php --start >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }
        return implode("\n", $l);
    } else {
        if ($EnableConntrackd == 0) {
            shell_exec2("{$GLOBALS["KILLBIN"]} -9 $master_pid >/dev/null 2>&1");
        }
    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);
}

//========================================================================================================================================================




function syncthing()
{
    if (!is_file("/etc/init.d/artica-postfix")) {
        return;
    }
    $bin = $GLOBALS["CLASS_UNIX"]->find_program("syncthing");
    if (!is_file($bin)) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SyncThingInstalled", 0);
        if ($GLOBALS["VERBOSE"]) {
            echo __FUNCTION__ . " SYNCTHING_INSTALLED = FALSE\n";
        }
        $l[] = "[APP_SYNCTHING]";
        $l[] = "service_name=APP_SYNCTHING";
        $l[] = "installed=0";
        $l[] = "service_disabled=0";
        return @implode("\n", $l);
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SyncThingInstalled", 0);
    $EnableSyncThing = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSyncThing"));

    $master_pid = syncthing_pid();
    if ($EnableSyncThing == 0) {
        if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            shell_exec2("/etc/init.d/syncthing stop");
        }
    }

    $l[] = "[APP_SYNCTHING]";
    $l[] = "service_name=APP_SYNCTHING";
    $l[] = "master_version=" . syncthing_version();;
    $l[] = "service_disabled=$EnableSyncThing";
    $l[] = "watchdog_features=1";
    $l[] = "installed=1";
    $l[] = "family=system";
    $l[] = "service_cmd=/etc/init.d/syncthing";
    if ($EnableSyncThing == 0) {
        if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            $cmd = "{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}/etc/init.d/syncthing stop >/dev/null 2>&1 &";
            events("$cmd", __FUNCTION__, __LINE__);
            shell_exec2($cmd);

        }
        $l[] = "";
        return implode("\n", $l);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if (!$GLOBALS["DISABLE_WATCHDOG"]) {
            $cmd = trim(PHPART."/exec.syncthing.php --start >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }
        return implode("\n", $l);
    } else {
        if ($EnableSyncThing == 0) {
            shell_exec2("{$GLOBALS["KILLBIN"]} -9 $master_pid >/dev/null 2>&1");
        }
    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);
}

//============================================================================================

function arpd():string{
    $bin = $GLOBALS["CLASS_UNIX"]->find_program("arpd");
    $EnableArpDaemon = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArpDaemon"));

    if ($EnableArpDaemon == 0) {
        if (is_file("/etc/init.d/arpd")) {
            squid_admin_mysql(1, "{uninstalling}} {APP_ARPD} ({disabled})", null, __FILE__, __LINE__);
            shell_exec(PHPART . "/exec.arpd.php --uninstall >/dev/null 2>&1 &");
            return "";
        }
        return "";
    } else {
        if (!is_file("/etc/init.d/arpd")) {
            squid_admin_mysql(1, "{installing} {APP_ARPD} ({enabled})", null, __FILE__, __LINE__);
            shell_exec(PHPART . "/exec.arpd.php --install >/dev/null 2>&1 &");
            return "";
        }
    }
    $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($bin, true);


    $l[] = "[APP_ARPD]";
    $l[] = "service_name=APP_ARPD";
    $l[] = "master_version=No";
    $l[] = "service_disabled=$EnableArpDaemon";
    $l[] = "watchdog_features=1";
    $l[] = "family=system";


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if (!$GLOBALS["DISABLE_WATCHDOG"]) {
            squid_admin_mysql(1, "Starting {APP_ARPD} (not running)", null, __FILE__, __LINE__);
            $cmd = trim(PHPART."/exec.arpd.php --start >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);
}

//===========================================================================================
//
function netatalk_version()
{
    if (isset($GLOBALS[__FUNCTION__])) {
        return $GLOBALS[__FUNCTION__];
    }
    $bin = $GLOBALS["CLASS_UNIX"]->find_program("afpd");
    exec("$bin -V 2>&1", $results);
    foreach ($results as $num => $line) {
        if (preg_match("#afpd\s+([0-9\.]+)#", $line, $re)) {
            $GLOBALS[__FUNCTION__] = $re[1];
            return $re[1];
        }
    }
    return null;
}

//========================================================================================================================================================
function avahi_daemon_version()
{
    if (isset($GLOBALS[__FUNCTION__])) {
        return $GLOBALS[__FUNCTION__];
    }
    $bin = $GLOBALS["CLASS_UNIX"]->find_program("avahi-daemon");
    exec("$bin -V 2>&1", $results);
    foreach ($results as $num => $line) {
        if (preg_match("#avahi-daemon\s+([0-9\.]+)#", $line, $re)) {
            $GLOBALS[__FUNCTION__] = $re[1];
            return $re[1];
        }
    }
    return null;
}

//========================================================================================================================================================

function udevd_daemon_version()
{
    if (isset($GLOBALS[__FUNCTION__])) {
        return $GLOBALS[__FUNCTION__];
    }
    $bin = $GLOBALS["CLASS_UNIX"]->find_program("udevd");
    if (is_file("/lib/systemd/systemd-udevd")) {
        $bin = "/lib/systemd/systemd-udevd";
    }
    exec("$bin --version 2>&1", $results);
    foreach ($results as $num => $line) {
        if (preg_match("#^([0-9\.]+)#", $line, $re)) {
            $GLOBALS[__FUNCTION__] = $re[1];
            return $re[1];
        }
    }

    return null;

}


function netatalk()
{
    if (!$GLOBALS["CLASS_USERS"]->NETATALK_INSTALLED) {
        $l[] = "[APP_NETATALK]";
        $l[] = "service_name=APP_NETATALK";
        $l[] = "installed=0";
        $l[] = "service_disabled=0";
        if ($GLOBALS["VERBOSE"]) {
            echo __FUNCTION__ . " NETATALK_INSTALLED = FALSE\n";
        }
        return @implode("\n", $l);
    }
    $bin = $GLOBALS["CLASS_UNIX"]->find_program("afpd");
    $NetatalkEnabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetatalkEnabled");
    if (!is_numeric($NetatalkEnabled)) {
        $NetatalkEnabled = 1;
    }
    $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($bin, true);
    if ($NetatalkEnabled == 0) {
        if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            shell_exec2("/etc/init.d/netatalk stop");
        }
    }

    $l[] = "[APP_NETATALK]";
    $l[] = "service_name=APP_NETATALK";
    $l[] = "master_version=" . netatalk_version();
    $l[] = "service_disabled=$NetatalkEnabled";
    $l[] = "watchdog_features=1";
    $l[] = "family=system";
    $l[] = "installed=1";
    if ($NetatalkEnabled == 0) {
        if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            $cmd = "{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}/etc/init.d/artica-postfix stop netatalk >/dev/null 2>&1 &";
            events("$cmd", __FUNCTION__, __LINE__);
            shell_exec2($cmd);

        }
        $l[] = "running=0";
        $l[] = "";
        return implode("\n", $l);

    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_NETATALK", "netatalk");
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);
}

//========================================================================================================================================================
function avahi_daemon()
{

    $bin = $GLOBALS["CLASS_UNIX"]->find_program("avahi-daemon");
    $NetatalkEnabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetatalkEnabled");
    if (!is_numeric($NetatalkEnabled)) {
        $NetatalkEnabled = 1;
    }
    $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($bin, true);
    if ($NetatalkEnabled == 0) {
        if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            shell_exec2("/etc/init.d/netatalk stop");
        }
    }
    if (!$GLOBALS["CLASS_USERS"]->NETATALK_INSTALLED) {
        $NetatalkEnabled = 0;
    }

    $l[] = "[APP_AVAHI]";
    $l[] = "service_name=APP_AVAHI";
    $l[] = "master_version=" . avahi_daemon_version();
    $l[] = "service_disabled=$NetatalkEnabled";
    $l[] = "watchdog_features=1";
    $l[] = "family=system";
    if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if ($NetatalkEnabled == 0) {

            $kill = $GLOBALS["CLASS_UNIX"]->find_program("kill");
            if ($GLOBALS["VERBOSE"]) {
                echo "avahi_daemon:: Killing PID $master_pid\n";
            }
            $GLOBALS["CLASS_UNIX"]->KILL_PROCESS($master_pid, 9);

        }
    }

    if ($NetatalkEnabled == 0) {
        $l[] = "";
        return implode("\n", $l);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_AVAHI", "netatalk");
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);
}

//========================================================================================================================================================
function udevd_daemon_s()
{

    $bin = $GLOBALS["CLASS_UNIX"]->find_program("udevd");
    if (is_file("/lib/systemd/systemd-udevd")) {
        $bin = "/lib/systemd/systemd-udevd";
    }

    $INSTALLED = true;
    if (!is_file($bin)) {
        $INSTALLED = false;
    }
    if (!is_file("/etc/init.d/udev")) {
        $INSTALLED = false;
    }

    if (!$INSTALLED) {
        $l[] = "[APP_UDEVD]";
        $l[] = "service_name=APP_UDEVD";
        $l[] = "installed=0";
        $l[] = "service_disabled=0";
        implode("\n", $l);
    }
    $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($bin, true);


    $l[] = "[APP_UDEVD]";
    $l[] = "service_name=APP_UDEVD";
    $l[] = "master_version=" . udevd_daemon_version();
    $l[] = "service_disabled=1";
    $l[] = "watchdog_features=1";
    $l[] = "family=system";
    $l[] = "installed=1";

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);
}

//==========================================================================================================================



function klnagent_pid()
{
    $pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/klnagent.pid");
    if ($GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
        return $pid;
    }
    return $GLOBALS["CLASS_UNIX"]->PIDOF("/opt/kaspersky/klnagent64/sbin/klnagent");

}

function klnagent()
{


    if (!is_file("/etc/init.d/klnagent64")) {
        if (is_file("/etc/cron.d/klnagent")) {
            @unlink("/etc/cron.d/klnagent");
            system("/etc/init.d/cron reload");
        }
        if (is_file("/etc/monit/conf.d/APP_KLNAGENT.monitrc")) {
            @unlink("/etc/monit/conf.d/APP_KLNAGENT.monitrc");
            shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
        }
        return;
    }
    $master_pid = klnagent_pid();
    $KLNAGENT_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("KLNAGENT_VERSION");

    $l[] = "[APP_KLNAGENT]";
    $l[] = "service_name=APP_KLNAGENT";
    $l[] = "master_version=$KLNAGENT_VERSION";
    $l[] = "service_disabled=1";
    $l[] = "watchdog_features=1";
    $l[] = "family=system";
    $l[] = "installed=1";

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if ($GLOBALS["CLASS_UNIX"]->ServerRunSince() > 3) {
            if (!$GLOBALS["DISABLE_WATCHDOG"]) {
                $nohup = $GLOBALS["CLASS_UNIX"]->find_program("nohup");
                squid_admin_mysql(0, "Kaspersky Network Agent not running [{action}={start}]", null, __FILE__, __LINE__);
                shell_exec2("$nohup /etc/init.d/klnagent64 start >/dev/null 2>&1 &");
            }
        }
        $l[] = "";
        return implode("\n", $l);
    }

    if (!is_file("/etc/cron.d/klnagent")) {
        $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("klnagent", "0,3,6,9,12,15,18,21,24,27,30,33,36,39,42,45,48,51,54,57 * * * *", "exec.klnagent.php --klnagchk");
        system("/etc/init.d/cron reload");
    }


    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid,"APP_KLNAGENT");
    $l[] = "";
    return implode("\n", $l);
}

//===================================================================================================
//===================================================================================================
function nscd_version($bin)
{
    if (isset($GLOBALS[__FUNCTION__])) {
        return $GLOBALS[__FUNCTION__];
    }
    exec("$bin -V 2>&1", $results);
    foreach ($results as $num => $line) {
        if (preg_match("#nscd.+?([0-9\.]+)#", $line, $re)) {
            $GLOBALS[__FUNCTION__] = $re[1];
            return $re[1];
        }
    }

}

//===================================================================================================

function stunnel()
{

    if (!$GLOBALS["CLASS_USERS"]->stunnel4_installed) {
        if ($GLOBALS["VERBOSE"]) {
            echo __FUNCTION__ . " stunnel4_installed = FALSE\n";
        }
        $l[] = "[STUNNEL]";
        $l[] = "service_name=APP_STUNNEL";
        $l[] = "installed=0";
        $l[] = "service_disabled=0";
        return implode("\n", $l);

    }
    $sTunnel4enabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("sTunnel4enabled");
    if ($sTunnel4enabled == null) {
        $sTunnel4enabled = 0;
    }


    $binary = $GLOBALS["CLASS_UNIX"]->LOCATE_STUNNEL();
    $master_pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/stunnel/stunnel4.pid");

    if ($GLOBALS["VERBOSE"]) {
        echo "binary............: $binary\n";
    }
    if ($GLOBALS["VERBOSE"]) {
        echo "PID...............: $master_pid\n";
    }

    $l[] = "[STUNNEL]";
    $l[] = "service_name=APP_STUNNEL";
    $l[] = "master_version=" . stunnel_version();
    $l[] = "service_cmd=" . $GLOBALS["CLASS_UNIX"]->LOCATE_STUNNEL_INIT();
    $l[] = "service_disabled=$sTunnel4enabled";
    $l[] = "pid_path=pidof $binary";
    $l[] = "watchdog_features=1";
    $l[] = "family=system";
    $l[] = "installed=1";
    if ($sTunnel4enabled == 0) {
        $l[] = "";
        return implode("\n", $l);
    }
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if (!$GLOBALS["DISABLE_WATCHDOG"]) {
            shell_exec2("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.stunnel.php --start &");
        }

        $l[] = "running=0";
        return implode("\n", $l);
        return;
    }
    $l[] = GetMemoriesOf($master_pid,"APP_STUNNEL");
    $l[] = "running=1";
    return implode("\n", $l);
}


function stunnel_version()
{
    if (isset($GLOBALS["stunnel_version"])) {
        return $GLOBALS["stunnel_version"];
    }

    $stunnel = $GLOBALS["CLASS_UNIX"]->LOCATE_STUNNEL();
    exec("$stunnel -version 2>&1", $f);
    foreach ($f as $pid => $line) {
        if (preg_match("#stunnel\s+([0-9\.]+)#", $line, $re)) {
            $GLOBALS["stunnel_version"] = $re[1];
            return $re[1];
        }
    }

}

//========================================================================================================================================================


function pptp_clients()
{
    if (!$GLOBALS["CLASS_USERS"]->PPTP_INSTALLED) {
        if ($GLOBALS["VERBOSE"]) {
            echo __FUNCTION__ . " PPTP_INSTALLED = FALSE\n";
        }
        return;
    }
    $version = GetVersionOf("pptpd");

    $array = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PPTPVpnClients"));
    if (!is_array($array)) {
        if ($GLOBALS["VERBOSE"]) {
            echo __FUNCTION__ . " not an array PPTPVpnClients\n";
        }
        return;
    }
    if (count($array) == 0) {
        if ($GLOBALS["VERBOSE"]) {
            echo __FUNCTION__ . " PPTPVpnClients\n";
        }
        return;
    }
    $reload = false;
    foreach ($array as $connexionname => $PPTPDConfig) {
        if ($GLOBALS["VERBOSE"]) {
            echo __FUNCTION__ . " $connexionname...:{$PPTPDConfig["ENABLED"]}\n";
        }
        if ($PPTPDConfig["ENABLED"] <> 1) {
            continue;
        }
        $arrayPIDS = pptp_client_is_active($connexionname);
        $l[] = "[PPTPDCLIENT_$connexionname]";
        $l[] = "service_name=$connexionname";
        $l[] = "master_version=$version";
        $l[] = "service_cmd=pptpd-clients";
        $l[] = "service_disabled=1";
        $l[] = "pid_path=";
        $l[] = "watchdog_features=1";
        $l[] = "family=network";

        if (!is_array($arrayPIDS)) {
            $reload = true;
        } else {
            $l[] = GetMemoriesOf($arrayPIDS[0],"$connexionname");
            $l[] = "";
        }
    }

    $l[] = "";
    if (!$GLOBALS["DISABLE_WATCHDOG"]) {
        if ($reload) {
            $cmd = "{$GLOBALS["PHP5"]} " . dirname(__FILE__) . "/exec.pptpd.php --clients-start &";
            events("START PPTP Clients -> $cmd", __FUNCTION__, __LINE__);
            $GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET($cmd);
        }
    }

    return implode("\n", $l);

}

//===============================================================================================
function pptp_client_is_active($connexionname)
{
    if ($GLOBALS["PGREP"] == null) {
        $GLOBALS["PGREP"] = $GLOBALS["CLASS_UNIX"]->find_program("pgrep");
    }

    $cmd = "{$GLOBALS["PGREP"]} -l -f \"pptp.+?call $connexionname\" 2>&1";
    if ($GLOBALS["VERBOSE"]) {
        echo __FUNCTION__ . " ->$cmd\n";
    }
    exec($cmd, $results);

    foreach ($results as $num => $line) {
        if (preg_match("#^([0-9]+).+?pptp#", $line, $re)) {
            if ($GLOBALS["VERBOSE"]) {
                echo __FUNCTION__ . " ->PID: {$re[1]}\n";
            }
            if ($GLOBALS["CLASS_UNIX"]->PID_IS_CHROOTED($re[1])) {
                continue;
            }
            $arr[] = $re[1];
        } else {
            if ($GLOBALS["VERBOSE"]) {
                echo __FUNCTION__ . " NO MATCH \"$line\"\n";
            }
        }

    }

    return $arr;


}


function tftpd()
{
    if (!is_file("/etc/artica-postfix/settings/Daemons/TFTPD_INSTALLED")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TFTPD_INSTALLED", 0);
    }
    if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TFTPD_INSTALLED")) == 0) {
        return;
    }

    if (!$GLOBALS["CLASS_USERS"]->TFTPD_INSTALLED) {
        return;
    }
    $EnableTFTPD = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTFTPD");
    if ($EnableTFTPD == null) {
        $EnableTFTPD = 1;
    }
    $bin = $GLOBALS["CLASS_UNIX"]->find_program("inetd");
    if (!is_file($bin)) {
        $bin = $GLOBALS["CLASS_UNIX"]->find_program("xinetd");
        if (is_file("/var/run/xinetd.pid")) {
            $master_pid = trim(@file_get_contents("/var/run/xinetd.pid"));
        }
    }
    if (!is_numeric($master_pid)) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($bin);
    }


    $l[] = "[APP_TFTPD]";
    $l[] = "service_name=APP_TFTPD";
    $l[] = "master_version=" . GetVersionOf("tftpd");
    $l[] = "service_cmd=tftpd";
    $l[] = "service_disabled=$EnableTFTPD";

    $l[] = "watchdog_features=0";
    $l[] = "family=storage";
    if ($EnableTFTPD == 0) {
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = GetMemoriesOf($master_pid,"APP_TFTPD");
    $l[] = "";

    return implode("\n", $l);

}


//=============================================================================================================
function postfix_multi_status():string{
    $calc=false;
    if(!isset($GLOBALS["MULTI-INSTANCES-LIST"])){$GLOBALS["MULTI-INSTANCES-LIST"]=array();}
    if (!is_array($GLOBALS["MULTI-INSTANCES-LIST"])) {
        $calc = true;
    }
    if ($GLOBALS["MULTI-INSTANCES-TIME"] == null) {
        $cacl = true;
    }
    if (calc_time_min($GLOBALS["MULTI-INSTANCES-TIME"]) > 5) {
        $cacl = true;
    }
    if ($GLOBALS["VERBOSE"]) {
        echo "GetVersionOf(postfix) line:" . __LINE__ . "\n";
    }
    $version = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");

    if ($GLOBALS["VERBOSE"]) {
        echo "calc=\"$cacl\" postfix v$version\n";
    }

    if ($calc) {
        if ($GLOBALS["VERBOSE"]) {
            echo "POSTFIX_MULTI_INSTANCES_LIST() line:" . __LINE__ . "\n";
        }
        $GLOBALS["MULTI-INSTANCES-LIST"] = $GLOBALS["CLASS_UNIX"]->POSTFIX_MULTI_INSTANCES_LIST();
        $GLOBALS["MULTI-INSTANCES-TIME"] = time();
    }
    if (is_array($GLOBALS["MULTI-INSTANCES-LIST"])) {
        foreach ($GLOBALS["MULTI-INSTANCES-LIST"] as $num => $instance) {
            if ($instance == null) {
                continue;
            }
            $l[] = "[POSTFIX-MULTI-$instance]";
            $l[] = "service_name=$instance";
            $l[] = "master_version=" . GetVersionOf("postfix");
            $l[] = "service_cmd=postfix-multi";
            $l[] = "service_disabled=1";
            $l[] = "remove_cmd=--postfix-remove";
            $l[] = "family=postfix";
            $master_pid = $GLOBALS["CLASS_UNIX"]->POSTFIX_MULTI_PID($instance);
            if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
                $l[] = "";
                return implode("\n", $l);
            }

            $l[] = GetMemoriesOf($master_pid,$instance);
            $l[] = "";
        }
    }
    if (is_array($l)) {
        return implode("\n", $l);
    }
    return "";
}





function lsm_version()
{
    if (isset($GLOBALS["LSM_VERSION"])) {
        return $GLOBALS["LSM_VERSION"];
    }
    exec("/usr/share/artica-postfix/bin/foolsm --version 2>&1", $results);
    foreach ($results as $line) {
        if (preg_match("#foolsm version ([0-9\.]+)#", $line, $re)) {
            $GLOBALS["LSM_VERSION"] = $re[1];
            return $re[1];
        }

    }

}

function lsm_pid()
{
    $pidfile = "/var/run/foolsm.pid";
    $pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
    if ($GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
        return $pid;
    }
    return $GLOBALS["CLASS_UNIX"]->PIDOF("/usr/share/artica-postfix/bin/foolsm");

}

function lsm()
{
    if (!is_file("/usr/share/artica-postfix/bin/foolsm")) {
        return null;
    }
    if (!is_file("/etc/init.d/foolsm")) {
        return null;
    }
    $l[] = "[LinkStatusMonitor]";
    $l[] = "service_name=LinkStatusMonitor";
    $l[] = "master_version=" . lsm_version();
    $l[] = "service_cmd=/etc/init.d/foolsm";
    $l[] = "service_disabled=1";
    $l[] = "pid_path=/var/run/foolsm.pid";
    $l[] = "watchdog_features=1";
    $l[] = "family=system";
    $l[] = "installed=1";

    $master_pid = lsm_pid();


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if (!$GLOBALS["DISABLE_WATCHDOG"]) {
            if ($GLOBALS["CLASS_UNIX"]->ServerRunSince() > 3) {
                squid_admin_mysql(0, "Link Status Monitor stopped [ {action} = {restart} ]", null, __FILE__, __LINE__);
            }
            $cmd = trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.lsm.php --restart >/dev/null 2>&1");
            shell_exec2($cmd);

        }
        $l[] = "running=0";
        $l[] = "installed=1";
        return implode("\n", $l);

    }

    $l[] = GetMemoriesOf($master_pid,"LinkStatusMonitor");
    $l[] = "";

    return implode("\n", $l);


}

//========================================================================================================================================================
function mailman()
{
    if (!$GLOBALS["CLASS_USERS"]->MAILMAN_INSTALLED) {
        return null;
    }
    $enabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("MailManEnabled");
    if ($enabled == null) {
        $enabled = 0;
    }
    $pid_path = trim(GetVersionOf("mailman-pid"));
    $master_pid = trim(@file_get_contents($pid_path));

    $l[] = "[MAILMAN]";
    $l[] = "service_name=APP_MAILMAN";
    $l[] = "master_version=" . GetVersionOf("mailman");
    $l[] = "service_cmd=mailman";
    $l[] = "service_disabled=$enabled";
    $l[] = "family=postfix";
    $l[] = "pid_path=$pid_path";
    //$l[]="remove_cmd=--milter-grelist-remove";
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid,"APP_MAILMAN");
    $l[] = "";

    return implode("\n", $l);

}

//========================================================================================================================================================


function rsync_debian_mirror()
{
    if (!is_file("/etc/init.d/debian-artmirror")) {
        return;
    }
    $rsync = $GLOBALS["CLASS_UNIX"]->find_program("rsync");

    if (!is_file($rsync)) {
        if ($GLOBALS["VERBOSE"]) {
            echo __FUNCTION__ . " not installed\n";
        }
        return null;
    }
    $enabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorEnableDebian");
    if ($enabled == null) {
        $enabled = 0;
    }

    $MirrorDebianDir = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("MirrorDebianDir");
    if ($MirrorDebianDir == null) {
        $MirrorDebianDir = "/home/mirrors/Debian";
    }


    $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("rsync.+?$MirrorDebianDir");

    $l[] = "[APP_RSYNC_DEBIAN]";
    $l[] = "service_name=APP_RSYNC_DEBIAN";
    $l[] = "master_version=" . $GLOBALS["CLASS_SOCKETS"]->GET_INFO("RSYNC_VERSION");
    $l[] = "service_disabled=$enabled";
    $l[] = "remove_cmd=--kas3-remove";
    $l[] = "service_cmd=/etc/init.d/debian-artmirror";
    $l[] = "family=proxy";
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid,"APP_RSYNC_DEBIAN");
    $l[] = "";
    return implode("\n", $l);
}

//=================================================================================================



function scanned_only()
{
    if (!is_file("/etc/init.d/samba")) {
        return;
    }
    if (!$GLOBALS["CLASS_USERS"]->SAMBA_INSTALLED) {
        if ($GLOBALS["VERBOSE"]) {
            echo __FUNCTION__ . " not installed\n";
        }
        return null;
    }
    if (!is_file("/etc/artica-postfix/settings/Daemons/EnableScannedOnly")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableScannedOnly", 0);
    }
    $enabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableScannedOnly");
    $binpath = $GLOBALS["CLASS_UNIX"]->find_program('scannedonlyd_clamav');
    if (strlen($binpath) < strlen("scannedonlyd_clamav")) {
        if ($GLOBALS["VERBOSE"]) {
            echo __FUNCTION__ . " not installed\n";
        }
        return null;
    }


    if (!is_numeric($enabled)) {
        $enabled = 1;
    }

    $pid_path = "/var/run/scannedonly.pid";
    $master_pid = trim(@file_get_contents($pid_path));

    $l[] = "[SAMBA_SCANNEDONLY]";
    $l[] = "service_name=APP_SCANNED_ONLY";
    $l[] = "master_version=unknown";
    $l[] = "service_cmd=samba";
    $l[] = "service_disabled=$enabled";
    $l[] = "pid_path=$pid_path";
    //$l[]="remove_cmd=--samba-remove";
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
    }


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid,"APP_SCANNED_ONLY");
    $l[] = "";
    return implode("\n", $l);
}


function cups()
{


    if (!$GLOBALS["CLASS_USERS"]->CUPS_INSTALLED) {
        if ($GLOBALS["VERBOSE"]) {
            echo __FUNCTION__ . " not installed\n";
        }
        return null;
    }
    $enabled = 1;
    if ($enabled == null) {
        $enabled = 0;
    }
    $pid_path = "/var/run/cups/cupsd.pid";
    $master_pid = trim(@file_get_contents($pid_path));

    $l[] = "[CUPS]";
    $l[] = "service_name=APP_CUPS";
    $l[] = "master_version=" . GetVersionOf("cups");
    $l[] = "service_cmd=cups";
    $l[] = "service_disabled=$enabled";
    $l[] = "pid_path=$pid_path";
    $l[] = "family=storage";
    //$l[]="remove_cmd=--samba-remove";
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid,"APP_CUPS");
    $l[] = "";
    return implode("\n", $l);
}

//========================================================================================================================================================
function ocs_agent()
{
    if (!$GLOBALS["CLASS_USERS"]->OCS_LNX_AGENT_INSTALLED) {
        return null;
    }
    $OCSNGEnabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOCSAgent");
    if ($OCSNGEnabled == null) {
        $OCSNGEnabled = 1;
    }
    $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("/usr/local/bin/ocsinventory-agent");


    $l[] = "[APP_OCSI_LINUX_CLIENT]";
    $l[] = "service_name=APP_OCSI_LINUX_CLIENT";
    $l[] = "master_version=" . GetVersionOf("ocsagent");
    $l[] = "service_cmd=ocsagent";
    $l[] = "service_disabled=$OCSNGEnabled";
    $l[] = "family=computers";
    $l[] = "watchdog_features=1";
    //$l[]="remove_cmd=--samba-remove";

    if ($OCSNGEnabled == 0) {
        return implode("\n", $l);
    }


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_OCSI_LINUX_CLIENT", "ocsagent");
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);

    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid,"APP_OCSI_LINUX_CLIENT");
    $l[] = "";
    return implode("\n", $l);
}

//========================================================================================================================================================
function ulogd_pid()
{

    $pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/ulogd.pid");
    if ($GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
        return $pid;
    }
    $Masterbin = "/usr/local/sbin/ulogd";
    return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);
}

function ulogd_version()
{
    if (isset($GLOBALS["ULOGDVERSION"])) {
        return $GLOBALS["ULOGDVERSION"];
    }
    exec("/usr/local/sbin/ulogd -V 2>&1", $results);
    foreach ($results as $line) {

        if (preg_match("#ulogd Version\s+([0-9\.]+)#", $line, $re)) {
            $GLOBALS["ULOGDVERSION"] = $re[1];
            return $GLOBALS["ULOGDVERSION"];
        }
    }
    return null;

}

function ulogd()
{
    if (!is_file("/etc/init.d/ulogd")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UlogdEnabled", 0);
        return null;
    }


    $bin_path = "/usr/local/sbin/ulogd";
    if ($bin_path == null) {
        return null;
    }
    $pid_path = "/var/run/ulogd.pid";
    $master_pid = ulogd_pid();


    if (!is_file("/etc/artica-postfix/settings/Daemons/UlogdEnabled")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UlogdEnabled", 0);
    }

    $UlogdEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UlogdEnabled"));
    $FireHolEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
    if ($FireHolEnable == 0) {
        $UlogdEnabled = 0;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_ULOGD_INSTALLED", 1);


    $l[] = "[APP_ULOGD]";
    $l[] = "service_name=APP_ULOGD";
    $l[] = "master_version=" . ulogd_version();
    $l[] = "service_cmd=/etc/init.d/ulogd";
    $l[] = "service_disabled=$UlogdEnabled";
    $l[] = "pid_path=$pid_path";
    $l[] = "family=network";
    $l[] = "watchdog_features=1";

    if ($UlogdEnabled == 0) {
        if (is_file("/etc/init.d/ulogd")) {
            squid_admin_mysql(0, "Uninstall FireWall logger service", "If you need, this service, re-install it on System/Firewall/FireWall logger service", __FILE__, __LINE__);
            shell_exec2(trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ulogd.php --uninstall >/dev/null 2>&1 &"));
        }

        return implode("\n", $l);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if ($GLOBALS["CLASS_UNIX"]->ServerRunSince() > 3) {
            squid_admin_mysql(0, "FireWall logger service is not running [{action}={start}]", null, __FILE__, __LINE__);
        }
        shell_exec2("/etc/init.d/ulogd start >/dev/null 2>&1 &");
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);

    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid,"APP_ULOGD");
    $l[] = "";
    return implode("\n", $l);
}


//==========================================================================================
function sshportal_pid()
{
    $pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/sshportal.pid");
    if ($GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
        return $pid;
    }
    $Masterbin = $GLOBALS["CLASS_UNIX"]->find_program("sshportal");
    return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($Masterbin);
}

function sshportal()
{

    $EnableSSHPortal = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSSHPortal"));
    $MasterVersion = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SSHPORTAL_VERSION");

    if ($EnableSSHPortal == 0) {
        if (!is_file("/etc/init.d/sshportal")) {
            return null;
        }
        squid_admin_mysql(0, "Uninstall {APP_SSHPORTAL} (feature disabled)", null, __FILE__, __LINE__);
        shell_exec2(trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.sshportal.php --uninstall >/dev/null 2>&1 &"));
        return null;
    }
    if ($EnableSSHPortal == 1) {
        if (!is_file("/etc/init.d/sshportal")) {
            squid_admin_mysql(1, "Install {APP_SSHPORTAL} (feature enabled)", null, __FILE__, __LINE__);
            shell_exec2(trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.sshportal.php --install >/dev/null 2>&1 &"));
            return null;
        }
    }
    $master_pid = sshportal_pid();
    $l[] = "[APP_SSHPORTAL]";
    $l[] = "service_name=APP_SSHPORTAL";
    $l[] = "master_version=$MasterVersion";
    $l[] = "service_cmd=/etc/init.d/sshportal";
    $l[] = "service_disabled=$EnableSSHPortal";
    $l[] = "family=network";
    $l[] = "watchdog_features=1";


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        squid_admin_mysql(1, "{APP_SSHPORTAL} server is not running [{action}={start}]", null, __FILE__, __LINE__);
        shell_exec2("/etc/init.d/sshportal start >/dev/null 2>&1 &");
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);

    }

    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid,"APP_SSHPORTAL");
    $l[] = "";
    return implode("\n", $l);
}
//=====================================================================================================

function go_exec_update():bool{
    $prebin=prebin();
    $ARROOT = ARTICA_ROOT;
    $goserver_src   = "$ARROOT/bin/go-shield/exec/go-exec";
    $monit_file     = "/etc/monit/conf.d/go-exec.monitrc";
    $goserver_dst   = "/bin/go-exec";



    if(!is_file($goserver_src)){return false;}
    if(!is_file($goserver_dst)){
        squid_admin_mysql(1,"{installing} Go Exec service",null,__FILE__,__LINE__);
        shell_exec("$prebin/exec.go.exec.php >/dev/null 2>&1 &");
        return true;
    }

    if(!is_file($monit_file)){
        squid_admin_mysql(1,"{installing} Go Exec Monitor configuration",null,__FILE__,__LINE__);
        shell_exec("$prebin/exec.go.exec.php --monit >/dev/null 2>&1 &");
        return true;
    }


    $goserver_src_md5 = md5_file($goserver_src);
    $goserver_dst_md5 = md5_file($goserver_dst);
    if($goserver_src_md5==$goserver_dst_md5){return true;}
    squid_admin_mysql(1,"Trying update the Go Exec service",null,__FILE__,__LINE__);
    shell_exec("$prebin/exec.go.exec.php --update >/dev/null 2>&1 &");
    return true;
}
function prebin():string{
    return "{$GLOBALS["nohup"]} {$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix";
}







//========================================================================================================================================================
function gdm()
{


    $gdm_path = $GLOBALS["CLASS_UNIX"]->find_program('gdm');
    if ($gdm_path == null) {
        return;
    }
    $pid_path = "/var/run/gdm.pid";
    $master_pid = trim(@file_get_contents($pid_path));

    $l[] = "[GDM]";
    $l[] = "service_name=APP_GDM";
    $l[] = "master_version=" . GetVersionOf("gdm");
    //$l[]="service_cmd=apache-groupware";
    $l[] = "service_disabled=1";
    $l[] = "pid_path=$pid_path";
    $l[] = "family=system";
    //$l[]="remove_cmd=--samba-remove";
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid,"APP_GDM");
    $l[] = "";
    return implode("\n", $l);
}

//=======================================================================================================

function XMail()
{
    $binpath = "/var/lib/xmail/bin/XMail";
    if ($binpath == null) {
        return;
    }

    $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        return;
    }
    shell_exec2("{$GLOBALS["KILLBIN"]} -9 $master_pid");
}

//=======================================================================================================

function ntopng_pid()
{

    $masterbin = $GLOBALS["CLASS_UNIX"]->find_program("ntopng");
    $pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/ntopng/ntopng.pid');
    if ($GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
        return $pid;
    }
    return $GLOBALS["CLASS_UNIX"]->PIDOF($masterbin);
}




function ntopng()
{
    if (is_file("/usr/local/bin/ntopng")) {
        @chmod("/usr/local/bin/ntopng", 0755);
    }
    if (!is_file("/etc/init.d/ntopng")) {
        if (is_file("/etc/init.d/pf_ring")) {
            system("/etc/init.d/pf_ring stop");
            system("/usr/sbin/update-rc.d -f pf_ring remove");
            @unlink("/etc/init.d/pf_ring");
        }
        return;
    }
    $masterbin = $GLOBALS["CLASS_UNIX"]->find_program("ntopng");
    if (!is_file($masterbin)) {
        return;
    }
    $enabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablentopng"));

    $SquidPerformance = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
    $EnableIntelCeleron = intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
    if ($SquidPerformance > 2) {
        $Enablentopng = 0;
    }
    if ($EnableIntelCeleron == 1) {
        $Enablentopng = 0;
    }

    $l[] = "[APP_NTOPNG]";
    $l[] = "service_name=APP_NTOPNG";
    $l[] = "master_version=" . trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTOPNG_VERSION"));
    $l[] = "service_cmd=/etc/init.d/ntopng";
    $l[] = "service_disabled=$enabled";
    $l[] = "family=proxy";
    $l[] = "watchdog_features=1";

    if ($enabled == 0) {
        $master_pid = ntopng_pid();
        if ($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
            ToSyslog("Stopping ntopng pid $master_pid, service disabled");
            shell_exec2("/usr/sbin/artica-phpfpm-service -uninstall-ntopng");
        }
        return implode("\n", $l);
    }

    $master_pid = ntopng_pid();

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if (!$GLOBALS["DISABLE_WATCHDOG"]) {

            shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ntopng start >/dev/null 2>&1 &");

        }
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);
    }

    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid,"APP_NTOPNG");
    $l[] = "";
    $CacheFile = "/etc/artica-postfix/settings/Daemons/NTOPNgSize";
    if (!is_file($CacheFile)) {
        shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ntopng.php --clean  >/dev/null 2>&1 &");
        return implode("\n", $l);
    }

    $time_file = $GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.ntopng.php.cleanstorage.time");
    if ($time_file > 1880) {
        shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ntopng.php --clean  >/dev/null 2>&1 &");
    }
    return implode("\n", $l);
}

function freeradius_pid()
{

    $pidfile = "/var/run/freeradius/freeradius.pid";

    $pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
        $freeradius = $GLOBALS["CLASS_UNIX"]->find_program("freeradius");
        $pid = $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($freeradius);
    }
    return $pid;
}

function freeradius()
{

    if (!is_file("/etc/init.d/freeradius")) {
        return;
    }

    $enabled = 1;
    $pid_path = "/var/run/freeradius/freeradius.pid";
    $master_pid = freeradius_pid();

    $EnableFreeRadius = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFreeRadius"));


    $l[] = "[APP_FREERADIUS]";
    $l[] = "service_name=APP_FREERADIUS";
    $l[] = "master_version=" . $GLOBALS["CLASS_SOCKETS"]->GET_INFO("FREERADIUS_VERSION");
    $l[] = "service_cmd=/etc/init.d/freeradius";
    $l[] = "service_disabled=$enabled";
    $l[] = "pid_path=$pid_path";
    $l[] = "watchdog_features=1";
    $l[] = "family=system";

    if ($EnableFreeRadius == 0) {
        return implode("\n", $l);
    }


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $nohup = $GLOBALS["CLASS_UNIX"]->find_program("nohup");
        shell_exec2("$nohup /etc/init.d/freeradius start >/dev/null 2>&1 &");
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);
    }

    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);


}


//=======================================================================================================================
function hamachi_version()
{
    if (isset($GLOBALS[__FUNCTION__])) {
        return $GLOBALS[__FUNCTION__];
    }
    exec("/usr/bin/hamachi 2>&1", $results);
    foreach ($results as $num => $ligne) {
        if (preg_match("#version.+?([0-9\.]+)#", $ligne, $re)) {
            $GLOBALS[__FUNCTION__] = $re[1];
            return $GLOBALS[__FUNCTION__];
        }
    }
}

//================================================================================================

function hamachi():string{
    if (!is_file("/opt/logmein-hamachi/bin/hamachid")) {
        return "";
    }
    $enabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableHamachi");
    if (!is_numeric($enabled)) {
        $enabled = 1;
    }
    $pid_path = "/var/run/logmein-hamachi/hamachid.pid";
    $master_pid = trim(@file_get_contents($pid_path));
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF("/opt/logmein-hamachi/bin/hamachid");
    }

    $l[] = "[APP_AMACHI]";
    $l[] = "service_name=APP_AMACHI";
    $l[] = "master_version=" . hamachi_version();
    $l[] = "service_cmd=amachi";
    $l[] = "family=network";
    $l[] = "service_disabled=$enabled";
    $l[] = "pid_path=$pid_path";

    if ($enabled == 0) {
        return implode("\n", $l);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_HAMACHI", "hamachi");
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);
    }


    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);
}

//================================================================================================
function ejabberd_version():string{
    if (isset($GLOBALS[__FUNCTION__])) {
        return strval($GLOBALS[__FUNCTION__]);
    }
    $binpath = $GLOBALS["CLASS_UNIX"]->find_program("ejabberdctl");
    exec("$binpath status 2>&1", $results);
    foreach ($results as $num => $ligne) {
        if (preg_match("#ejabberd\s+([0-9\.]+)\s+#", $ligne, $re)) {
            $GLOBALS[__FUNCTION__] = $re[1];
            return $GLOBALS[__FUNCTION__];
        }
    }

    return "0.0";
}

function ejabberd_bin():string{

    if (is_file("/usr/lib/erlang/erts-5.8/bin/beam")) {
        return "/usr/lib/erlang/erts-5.8/bin/beam";
    }
return "";
}

//================================================================================================
function ejabberd():string
{
    if (!$GLOBALS["CLASS_USERS"]->EJABBERD_INSTALLED) {
        return "";
    }

    $enabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ejabberdEnabled"));
    $pid_path = "/var/run/ejabberd/ejabberd.pid";
    $master_pid = trim(@file_get_contents($pid_path));
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $binpath = ejabberd_bin();
        if ($binpath <> null) {
            $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
        }
    }
    $version = ejabberd_version();
    @file_put_contents("/etc/artica-postfix/ejabberd_version", $version);
    $l[] = "[APP_EJABBERD]";
    $l[] = "service_name=APP_EJABBERD";
    $l[] = "master_version=$version";
    $l[] = "service_cmd=ejabberd";
    $l[] = "family=network";
    $l[] = "service_disabled=$enabled";
    $l[] = "pid_path=$pid_path";

    if ($enabled == 0) {
        return implode("\n", $l);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_EJABBERD", "ejabberd");
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);
    }


    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);


}

//================================================================================================
function pymsnt_pgrep():string{
    $pgrep = $GLOBALS["CLASS_UNIX"]->find_program("pgrep");
    exec("$pgrep -l -f \"/usr/share/pymsnt/PyMSNt.py\" 2>&1", $results);
    foreach ($results as $ligne) {
        if (preg_match("#pgrep#", $ligne)) {
            continue;
        }
        if (preg_match("#^([0-9]+)#", $ligne, $re)) {
            return $re[1];
        }
    }

    return "";

}

//================================================================================================
function pymsnt_version():string
{
    if (isset($GLOBALS[__FUNCTION__])) {
        return strval($GLOBALS[__FUNCTION__]);
    }
    $binpath = "/usr/share/pymsnt/src/legacy/glue.py";
    if (!is_file($binpath)) {
        return "0.0";
    }
    $results = file($binpath);

    foreach ($results as $num => $ligne) {
        if (preg_match("#version.*?=.*?([0-9\.]+)#", $ligne, $re)) {
            $GLOBALS[__FUNCTION__] = $re[1];
            return $GLOBALS[__FUNCTION__];
        }
    }
    return "0.0";
}

//================================================================================================
function pymsnt():string
{
    if (!$GLOBALS["CLASS_USERS"]->EJABBERD_INSTALLED) {
        return "";
    }
    if (!$GLOBALS["CLASS_USERS"]->PYMSNT_INSTALLED) {
        return "";
    }

    $enabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ejabberdEnabled"));

    $pid_path = "/var/run/pymsnt/pymsnt.pid";
    $master_pid = trim(@file_get_contents($pid_path));
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $master_pid = pymsnt_pgrep();
    }


    $version = pymsnt_version();
    @file_put_contents("/etc/artica-postfix/pymsnt_version", $version);
    $l[] = "[APP_PYMSNT]";
    $l[] = "service_name=APP_PYMSNT";
    $l[] = "master_version=$version";
    $l[] = "service_cmd=pymsnt";
    $l[] = "family=network";
    $l[] = "service_disabled=$enabled";
    $l[] = "pid_path=$pid_path";

    if ($enabled == 0) {
        return implode("\n", $l);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_PYMSNT", "pymsnt");
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);
    }


    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);


}

//================================================================================================


function artica_notifier():string{

    $binpath = $GLOBALS["CLASS_UNIX"]->find_program('emailrelay');
    if ($binpath == null) {
        return "";
    }


    $l[] = "[APP_ARTICA_NOTIFIER]";
    $l[] = "service_name=APP_ARTICA_NOTIFIER";
    $l[] = "service_cmd=artica-notifier";
    $l[] = "master_version=" . GetVersionOf("emailrelay");

    if (!is_file("/etc/artica-postfix/smtpnotif.conf")) {
        $l[] = "service_disabled=0";
        return implode("\n", $l);
    }

    $ini = new Bs_IniHandler("/etc/artica-postfix/smtpnotif.conf");
    if ($ini->_params["SMTP"]["enabled"] <> 1) {
        $l[] = "service_disabled=0";
        return implode("\n", $l);

    }

    $l[] = "service_disabled=1";
    $pid_path = "/var/run/artica-notifier.pid";
    $master_pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
    $l[] = "service_cmd=artica-notifier";
    $l[] = "service_disabled=1";
    $l[] = "family=system";
    $l[] = "pid_path=$pid_path";
    $l[] = "watchdog_features=1";

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_ARTICA_NOTIFIER", "artica-notifier");
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);

    }

    //$l[]="remove_cmd=--zarafa-remove";
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);
    }
    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);
}

//================================================================================================
function python():bool{

    if (!is_file("/usr/lib/python2.7/dist-packages/_ldap.so")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PythonLDAPInstalled", 0);

    } else {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PythonLDAPInstalled", 1);
    }
    return true;
}





function greyhole()
{

    if (!$GLOBALS["CLASS_USERS"]->GREYHOLE_INSTALLED) {
        if ($GLOBALS["VERBOSE"]) {
            echo "GREYHOLE_INSTALLED FALSE\n";
        }
        return;
    }

    $EnableGreyhole = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGreyhole");
    if (!is_numeric($EnableGreyhole)) {
        $EnableGreyhole = 1;
    }

    $binpath = $GLOBALS["CLASS_UNIX"]->find_program('greyhole');
    if ($binpath == null) {
        if ($GLOBALS["VERBOSE"]) {
            echo "automount no such binary.\n";
        }
        return;
    }
    if (is_file("/var/run/greyhole.pid")) {
        $pid_path = "/var/run/greyhole.pid";
    }


    if ($pid_path <> null) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
    }
    if (!is_numeric($master_pid)) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($binpath);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
    }

    if (!is_file("/etc/greyhole.conf")) {
        $EnableGreyhole = 0;
    }

    $l[] = "[APP_GREYHOLE]";
    $l[] = "service_name=APP_GREYHOLE";
    $l[] = "service_cmd=greyhole";
    $l[] = "master_version=" . GetVersionOf("greyhole");
    $l[] = "service_disabled=$EnableGreyhole";
    $l[] = "family=network";
    $l[] = "watchdog_features=1";
    if ($EnableGreyhole == 0) {
        return implode("\n", $l);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_GREYHOLE", "greyhole");
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);
        return;
    }

    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);

}


function greyhole_watchdog()
{

    $greyhole = $GLOBALS["CLASS_UNIX"]->find_program('greyhole');
    $pgrep = $GLOBALS["CLASS_UNIX"]->find_program('pgrep');
    if (!is_file($greyhole)) {
        events("greyhole is not installed", __FUNCTION__, __LINE__);
        return;
    }
    $kill = $GLOBALS["CLASS_UNIX"]->find_program('kill');
    events("$pgrep -l -f \"$greyhole --fsck\" 2>&1", __FUNCTION__, __LINE__);
    exec("$pgrep -l -f \"$greyhole --fsck\"", $results);
    if (count($results) == 0) {
        return;
    }
    foreach ($results as $key => $value) {
        events("$value", __FUNCTION__, __LINE__);
        if (!preg_match("#^([0-9]+)\s+#", $value, $re)) {
            continue;
        }
        $pid = $re[1];
        if ($GLOBALS["CLASS_UNIX"]->PID_IS_CHROOTED($pid)) {
            continue;
        }
        $time = $GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
        events("Found pid $pid, $time minutes", __FUNCTION__, __LINE__);
        if (!is_file("/etc/greyhole.conf")) {
            events("/etc/greyhole.conf no such file, kill process", __FUNCTION__, __LINE__);
            $GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid, 9);

            continue;
        }
        if ($time > 120) {
            events("killing PID $pid", __FUNCTION__, __LINE__);
            $GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid, 9);
            $GLOBALS["CLASS_UNIX"]->send_email_events("greyhole process $pid was killed after {$time}Mn execution",
                "It reach max execution time : 120Mn ", "system"
            );
        }

    }
}
function vsftpd_pid()
{


    $Masterbin = $GLOBALS["CLASS_UNIX"]->find_program("vsftpd");
    $pid = $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("^vsftpd$");
    if ($GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
        return $pid;
    }
    return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($Masterbin);

}


function vsftpd()
{
    $vsftpd = $GLOBALS["CLASS_UNIX"]->find_program("vsftpd");
    if (!is_file($vsftpd)) {
        return;
    }
    $enabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVSFTPDDaemon");
    if (!is_numeric($enabled)) {
        $enabled = 0;
    }

    $master_pid = vsftpd_pid();
    $l[] = "[APP_VSFTPD]";
    $l[] = "service_name=APP_VSFTPD";
    $l[] = "master_version=2.3.5";
    $l[] = "service_cmd=/etc/init.d/vsftpd";
    $l[] = "service_disabled=$enabled";
    $l[] = "family=system";
    $l[] = "watchdog_features=1";

    if ($enabled == 0) {
        return implode("\n", $l);
    }

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if (!$GLOBALS["DISABLE_WATCHDOG"]) {
            vsftpd_admin_mysql(0, "Starting VSFTPD service [not running]", null, __FILE__, __LINE__);
            $cmd = trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.vsftpd.php --start");
            shell_exec2($cmd);
        }
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);

    } else {
        if ($enabled == 0) {
            vsftpd_admin_mysql(0, "Stopping VSFTPD service EnableVSFTPDDaemon = 0", null, __FILE__, __LINE__);
            shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/vsftpd stop >/dev/null 2>&1 &");
        }
    }


    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);
}


function remove_service($INITD_PATH)
{
    if (!is_file($INITD_PATH)) {
        return;
    }
    if(function_exists("debug_backtrace")) {
        $strace = debug_backtrace();
        foreach ($strace as $index => $trace) {
            $called[] = " called by " . basename($trace["file"]) . " {$trace["function"]}() line {$trace["line"]}";

        }
    }
    squid_admin_mysql(1, "Removing service $INITD_PATH", @implode("\n",$called), __FILE__, __LINE__);
    system("$INITD_PATH stop");

    if (is_file('/usr/sbin/update-rc.d')) {
        shell_exec("/usr/sbin/update-rc.d -f " . basename($INITD_PATH) . " remove >/dev/null 2>&1");
    }

    if (is_file('/sbin/chkconfig')) {
        shell_exec("/sbin/chkconfig --del " . basename($INITD_PATH) . " >/dev/null 2>&1");

    }

    if (is_file($INITD_PATH)) {
        @unlink($INITD_PATH);
    }

    if (is_file($INITD_PATH)) {
        squid_admin_mysql(0, "Unable to remove $INITD_PATH", null, __FILE__, __LINE__);
    }
}










function munin()
{
    $GLOBALS["NICE"] = $GLOBALS["CLASS_UNIX"]->EXEC_NICE();
    if (!is_file("/etc/init.d/munin-node")) {
        return null;
    }
    $bin_path = $GLOBALS["CLASS_UNIX"]->find_program("munin-node");
    $EnableMunin = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
    $pid_path = "/var/run/munin/munin-node.pid";

    $l[] = "[APP_MUNIN]";
    $l[] = "service_name=APP_MUNIN";
    $l[] = "service_cmd=munin";
    $l[] = "master_version=" . $GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_VERSION");
    $l[] = "service_disabled=$EnableMunin";
    $l[] = "family=network";
    $l[] = "watchdog_features=1";


    $master_pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);

    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($bin_path);
    }


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {

        if ($GLOBALS["CLASS_UNIX"]->ServerRunSince() > 3) {
            squid_admin_mysql(0, "{APP_MUNIN} not running [{action}={start}]", null, __FILE__, __LINE__);
            $cmd = trim("{$GLOBALS["NICE"]} /etc/init.d/munin-node start >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);
    }

    $Filetime = "/etc/artica-postfix/pids/munin-graph-cron.time";

    if ($GLOBALS["CLASS_UNIX"]->file_time_min($Filetime) > 4) {
        $su = $GLOBALS["CLASS_UNIX"]->find_program("su");
        @unlink($Filetime);
        @file_put_contents($Filetime, time());
        system("$su - munin --shell=/bin/bash -c \"{$GLOBALS["NICE"]}/usr/share/munin/munin-graph --cron\"");

    }

    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);

}


function vboxguest()
{
    if (!$GLOBALS["CLASS_USERS"]->APP_VBOXADDINTION_INSTALLED) {
        return;
    }
    $bin_path = $GLOBALS["CLASS_UNIX"]->find_program("VBoxService");
    if (!is_file((string) $bin_path)) {
        return;
    }
    $pid_path = $GLOBALS["CLASS_UNIX"]->LOCATE_VBOX_ADDITIONS_PID();

    $l[] = "[APP_VBOXADDITIONS]";
    $l[] = "service_name=APP_VBOXADDITIONS";
    $l[] = "service_cmd=vboxguest";
    $l[] = "master_version=" . GetVersionOf("vboxguest");
    $l[] = "service_disabled=1";
    $l[] = "pid_path=$pid_path";
    //$l[]="remove_cmd=--pureftpd-remove";
    $l[] = "family=system";
    $l[] = "watchdog_features=1";


    $master_pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
    }


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_VBOXADDITIONS", "vboxguest");
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);
        return;
    }

    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);


}

//========================================================================================================================================================


function pure_ftpd()
{


    if (!$GLOBALS["CLASS_USERS"]->PUREFTP_INSTALLED) {
        return;
    }

    $PureFtpdEnabled = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("PureFtpdEnabled");
    if ($PureFtpdEnabled == null) {
        $PureFtpdEnabled = 0;
    }
    $pid_path = $GLOBALS["CLASS_UNIX"]->LOCATE_PURE_FTPD_PID_PATH();
    $bin_path = $GLOBALS["CLASS_UNIX"]->find_program("pure-ftpd");

    $l[] = "[PUREFTPD]";
    $l[] = "service_name=APP_PUREFTPD";
    $l[] = "service_cmd=ftp";
    $l[] = "master_version=" . GetVersionOf("pure-ftpd");
    $l[] = "service_disabled=$PureFtpdEnabled";
    $l[] = "remove_cmd=--pureftpd-remove";
    $l[] = "family=storage";
    $l[] = "watchdog_features=1";
    if ($PureFtpdEnabled == 0) {
        return implode("\n", $l);
    }

    $master_pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
    $l[] = "watchdog_features=1";
    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        $master_pid = $GLOBALS["CLASS_UNIX"]->PIDOF($bin_path);
    }


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_PUREFTPD", "ftp");
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);
    }

    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);

}
function gluster_directories_number()
{

    $f = file("/etc/artica-cluster/glusterfs-server.vol");
    $c = 0;
    foreach ($f as $index => $line) {
        if (preg_match("#option directory\s+(.+)#", $line)) {
            $c++;
        }
    }
    return $c;
}


function gluster()
{
    if (!$GLOBALS["CLASS_USERS"]->GLUSTER_INSTALLED) {
        return null;
    }
    $EnableGluster = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGluster"));
    if (!is_numeric($EnableGluster)) {
        $EnableGluster = 0;
    }

    $l[] = "[GLUSTER]";
    $l[] = "service_name=APP_GLUSTER";
    $l[] = "service_cmd=gluster";
    $l[] = "family=storage";
    $l[] = "master_version=" . GetVersionOf("gluster");
    $l[] = "service_disabled=$EnableGluster";
    $l[] = "watchdog_features=1";
    //$l[]="remove_cmd=--pureftpd-remove";

    if ($EnableGluster == 0) {
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);

    }

    $pid_path = "/var/run/glusterd.pid";
    $master_pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_GLUSTER", "gluster");
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);

    }

    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);

}

//========================================================================================================================================================


function auditd()
{


    if (!$GLOBALS["CLASS_USERS"]->APP_AUDITD_INSTALLED) {
        return null;
    }


    $EnableAuditd = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAuditd");
    if ($EnableAuditd == null) {
        $EnableAuditd = 1;
    }


    $l[] = "[APP_AUDITD]";
    $l[] = "service_name=APP_AUDITD";
    $l[] = "service_cmd=auditd";
    $l[] = "master_version=" . GetVersionOf("auditd");
    $l[] = "service_disabled=$EnableAuditd";
    $l[] = "watchdog_features=1";
    $l[] = "family=system";


    if ($EnableAuditd == 0) {
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);

    }

    $pid_path = "/var/run/auditd.pid";
    $master_pid = $GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        WATCHDOG("APP_AUDITD", "auditd");
        $l[] = "running=0\ninstalled=1";
        $l[] = "";
        return implode("\n", $l);

    }

    $l[] = "running=1";
    $l[] = GetMemoriesOf($master_pid);
    $l[] = "";
    return implode("\n", $l);

}

//=======================================================================================================


function GetMemoriesOf($pid,$APP_NAME=null):string{
    return $GLOBALS["CLASS_UNIX"]->GetMemoriesOfStatus($pid,$APP_NAME);
}

function CheckGLOBALS(): bool
{

    if (preg_match("#--verbose#", $GLOBALS["COMMANDLINE"])) {
        $GLOBALS["DEBUG"] = true;
        $GLOBALS["VERBOSE"] = true;
        ini_set('display_errors', 1);
        ini_set('error_reporting', E_ALL);
        ini_set('error_prepend_string', null);
        ini_set('error_append_string', null);
    }
    $GLOBALS["NMAP_INSTALLED"] = false;
    $GLOBALS["SCHEDULE_ID"] = 0;
    $GLOBALS["STARTED_BY_CRON"] = false;
    if (preg_match("#schedule-id=([0-9]+)#", $GLOBALS["COMMANDLINE"], $re)) {
        $GLOBALS["SCHEDULE_ID"] = $re[1];
    }
    if (preg_match("#--json#", $GLOBALS["COMMANDLINE"], $re)) {
        $GLOBALS["JSON"]=true;
    }



    if (preg_match("#--startcron#", $GLOBALS["COMMANDLINE"], $re)) {
        $GLOBALS["STARTED_BY_CRON"] = true;
    }
    if (preg_match("#--nowachdog#", $GLOBALS["COMMANDLINE"])) {
        $GLOBALS["DISABLE_WATCHDOG"] = true;
    }
    if (preg_match("#--force#", $GLOBALS["COMMANDLINE"])) {
        $GLOBALS["FORCE"] = true;
    }


    $GLOBALS["CLASS_UNIX"] = new unix();
    $GLOBALS["MY-POINTER"] = "/etc/artica-postfix/pids/" . basename(__FILE__) . ".pointer";
    $GLOBALS["PHP5"] = $GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
    $GLOBALS["NICE"] = $GLOBALS["CLASS_UNIX"]->EXEC_NICE();
    $GLOBALS["nohup"] = $GLOBALS["CLASS_UNIX"]->find_program("nohup");
    $GLOBALS["pgrep"] = $GLOBALS["CLASS_UNIX"]->find_program("pgrep");
    $GLOBALS["CHMOD"] = $GLOBALS["CLASS_UNIX"]->find_program("chmod");
    $GLOBALS["CHOWN"] = $GLOBALS["CLASS_UNIX"]->find_program("chown");
    $GLOBALS["KILLBIN"] = $GLOBALS["CLASS_UNIX"]->find_program("kill");
    $GLOBALS["RMBIN"] = $GLOBALS["CLASS_UNIX"]->find_program("rm");
    $GLOBALS["SYNCBIN"] = $GLOBALS["CLASS_UNIX"]->find_program("sync");
    $GLOBALS["ECHOBIN"] = $GLOBALS["CLASS_UNIX"]->find_program("echo");
    $GLOBALS["NMAPBIN"] = $GLOBALS["CLASS_UNIX"]->find_program("nmap");
    $GLOBALS["UPTIMEBIN"] = $GLOBALS["CLASS_UNIX"]->find_program("uptime");
    $GLOBALS["TAILBIN"] = $GLOBALS["CLASS_UNIX"]->find_program("tail");
    $GLOBALS["DisableArticaStatusService"] = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableArticaStatusService"));
    if (is_file($GLOBALS["NMAPBIN"])) {
        $GLOBALS["NMAP_INSTALLED"] = true;
    }
    $GLOBALS["KILL"] = $GLOBALS["KILLBIN"];

    if (!is_file("/etc/artica-postfix/settings/Daemons/ArticaWatchDogList")) {
        @touch("/etc/artica-postfix/settings/Daemons/ArticaWatchDogList");
    }
    $GLOBALS["ArticaWatchDogList"] = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWatchDogList"));

    $GoExec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Exec_Enable"));
    if ($GoExec==0){ system("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.go.exec.php"); }
    return true;
}

function CheckCallable():bool{

    if (!isset($GLOBALS["CLASS_UNIX"])) {
        $GLOBALS["CLASS_UNIX"] = new unix();
    }

    $Callables[]="/ressources/class.os.system.tools.inc";
    $Callables[]="/ressources/class.postgres.inc";
    $Callables[]="/framework/class.status.hardware.inc";
    $Callables[]="/ressources/class.status.ftp-proxy.inc";
    $Callables[]="/ressources/class.status.firehol.inc";
    $Callables[]="/ressources/class.status.dockerd.inc";
    $Callables[]="/ressources/class.status.rustdesk.inc";
    $Callables[]="/ressources/class.status.defaults.inc";
    $Callables[]="/ressources/class.status.bandwidthd.inc";
    $Callables[]="/ressources/class.status.haproxy.inc";
    $Callables[]="/ressources/class.status.adagent.inc";
    $Callables[]="/ressources/class.status.privoxy.inc";
    $Callables[]="/ressources/class.status.sealion.inc";
    $Callables[]="/ressources/class.status.openvpn.inc";
    $Callables[]="/ressources/class.status.mosquitto.inc";
    $Callables[]="/ressources/class.status.watchdog.me.inc";
    $Callables[]="/ressources/class.status.splunk.inc";
    $Callables[]="/ressources/class.status.tailscale.inc";
    $Callables[]="/ressources/class.status.urbackup.inc";
    $Callables[]="/ressources/class.status.categories-cache.inc";
    $Callables[]="/ressources/class.status.wanproxy.inc";
    $Callables[]="/ressources/class.status.apt-mirror.inc";
    $Callables[]="/ressources/class.status.fsm.inc";
    $Callables[]="/ressources/class.status.haexchange.inc";
    $Callables[]="/ressources/class.status.wazhu.agent.inc";
    $GLOBALS["CLASS_SOCKETS"]=new sockets();

    if ($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MYSQL_INSTALLED") == 1) {if ($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMySQL") == 1) { $Callables[]="/ressources/class.status.mysql.inc"; } }
    if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFail2Ban")) == 1) { $Callables[]='/ressources/class.status.fail2ban.inc'; }

    if (is_file("/etc/init.d/unbound")) {
        $Callables[]='/ressources/class.status.unbound.inc';
    }




    foreach ($Callables as $root){
        include_once(dirname(__FILE__).$root);
    }



    if (!isset($GLOBALS["CLASS_USERS"])) {
        $GLOBALS["CLASS_USERS"] = new settings_inc();
    }
    if (!isset($GLOBALS["CLASS_SOCKETS"])) {
        $GLOBALS["CLASS_SOCKETS"] = new sockets();
    }


    $methodVariable = array($GLOBALS["CLASS_UNIX"], 'GetVersionOf');
    if (!is_callable($methodVariable, true, $callable_name)) {
        ToSyslog("Loading unix class");
        $GLOBALS["CLASS_UNIX"] = new unix();
    }

    $methodVariable = array($GLOBALS["CLASS_UNIX"], 'find_program');
    if (!is_callable($methodVariable, true, $callable_name)) {
        events("Loading unix class");
        $GLOBALS["CLASS_UNIX"] = new unix();
    }
    $methodVariable = array($GLOBALS["CLASS_SOCKETS"], 'GET_INFO');
    if (!is_callable($methodVariable, true, $callable_name)) {
        ToSyslog("Loading socket class");
        $GLOBALS["CLASS_SOCKETS"] = new sockets();
    }


    $methodVariable = array($GLOBALS["CLASS_USERS"], '_ParsePrivieleges');
    if (!is_callable($methodVariable, true, $callable_name)) {
        ToSyslog("Loading usersMenus class");
        $GLOBALS["CLASS_USERS"] = new settings_inc();
    }


    $GLOBALS["OS_SYSTEM"] = new os_system();
    $GLOBALS["MEMORY_INSTALLED"] = $GLOBALS["OS_SYSTEM"]->memory();

    $GLOBALS["TIME_CLASS"] = time();
    $GLOBALS["ArticaWatchDogList"] = array();
    if (is_file("/etc/artica-postfix/settings/Daemons/ArticaWatchDogList")) {
        $GLOBALS["ArticaWatchDogList"] = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWatchDogList"));
    }

    if (!is_dir("/var/log/artica-postfix/rotate_events")) {
        mkdir_test("/var/log/artica-postfix/rotate_events");
    }
    if (!is_dir("/etc/artica-postfix/settings/Mysql")) {
        mkdir_test("/etc/artica-postfix/settings/Mysql");
    }

    return true;

}


function getmem(): string
{
    include_once(dirname(__FILE__) . "/ressources/class.os.system.tools.inc");
    $os = new os_system();
    $GLOBALS["MEMORY_INSTALLED"] = $os->memory();
    $os = null;
    print_r($GLOBALS["MEMORY_INSTALLED"]);
    return "";
}

function CheckCurl()
{
    $results = array();
    $pidof = $GLOBALS["CLASS_UNIX"]->find_program("pidof");
    if ($pidof == null) {
        events("pidof no such file", __FUNCTION__, __LINE__);
        return;
    }
    $curl = $GLOBALS["CLASS_UNIX"]->find_program("curl");
    if ($curl == null) {
        events("curl binary no such file", __FUNCTION__, __LINE__);
        return;
    }

    exec("$pidof $curl 2>&1", $results);
    if (count($results) == 0) {
        events("no curl instance in memory", __FUNCTION__, __LINE__);
        return;
    }

    foreach ($results as $pid) {
        $pid = trim($pid);
        if (!is_numeric($pid)) {
            continue;
        }
        if ($pid < 5) {
            continue;
        }
        if ($GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
            $time = $GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
            events("$curl: $pid {$time}Mn", __FUNCTION__, __LINE__);
            if ($time > 60) {
                events("$curl: too long time for $pid, kill it", __FUNCTION__, __LINE__);
                $GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid, 9);

            }
        }
    }

}

function GetVersionOf($name)
{
    if (isset($GLOBALS["GetVersionOf"][$name])) {
        return $GLOBALS["GetVersionOf"][$name];
    }
    CheckCallable();
    $GLOBALS["GetVersionOf"][$name] = $GLOBALS["CLASS_UNIX"]->GetVersionOf($name);
    return $GLOBALS["GetVersionOf"][$name];
}

function events($text, $function = null, $line = 0){ ToSyslog("$text $function() L.$line");}
function events_syslog($text = null){ToSyslog("$text");}
function ps_mem()
{
}

function ifconfig_network(){
    $ifconfigs = $GLOBALS["CLASS_UNIX"]->ifconfig_all_ips();
    $DisableWatchDogNetwork = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableWatchDogNetwork"));
    $DisableNetworking=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworking"));

    if($DisableNetworking==1){
        return false;
    }
    if ($DisableWatchDogNetwork == 1) {
        return false;
    }
    unset($ifconfigs["127.0.0.1"]);
    events(count($ifconfigs) . " Ip addresses", __FUNCTION__, __LINE__);
    if (count($ifconfigs) == 0) {
        $timefile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".time";
        $timmin = $GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
        if ($timmin > 10) {
            $ifconfigbin = $GLOBALS["CLASS_UNIX"]->find_program("ifconfig");
            if (is_file($ifconfigbin)) {
                exec("$ifconfigbin -a 2>&1", $ifconfigbinDump);
                $GLOBALS["CLASS_UNIX"]->send_email_events("No Network detected !, rebuild network configuration", "Artica has no detected network the network interface will be rebuilded\nHere it is the Network dump\n" . @implode("\n", $ifconfigbinDump) . "\nIf you did not want this watchdog, do the following command on this console server:\n# echo 1 >/etc/artica-postfix/settings/Daemons/DisableWatchDogNetwork\n# /etc/init.d/artica-status reload", "system");
                @unlink($timefile);
                @file_put_contents($timefile, time());
                @unlink("/etc/artica-postfix/MEM_INTERFACES");
                $cmd = trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.virtuals-ip.php >/dev/null 2>&1 &");
                shell_exec2($cmd);
            }
        }
    }

}

function reboot(){
    squid_admin_mysql(2, "Ask to reboot the system...", "", __FILE__, __LINE__);
    shell_exec2("/usr/sbin/artica-phpfpm-service -reboot");
    return null;
}




function shell_exec_time($cmdlineNophp5, $mintime = 5)
{
    if (!is_numeric($mintime)) {
        $mintime = 5;
    }
    if ($mintime < 5) {
        $mintime = 5;
    }
    $md5 = md5($cmdlineNophp5);
    $timefile = "/etc/artica-postfix/pids/" . basename(__FILE__) . ".$md5.time";
    $TimeExec = $GLOBALS["CLASS_UNIX"]->file_time_min($timefile);
    if ($TimeExec < $mintime) {
        return;
    }
    @unlink($timefile);
    @file_put_contents($timefile, time());
    shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} " . basename(__FILE__) . "/$cmdlineNophp5 >/dev/null 2>&1 &");
}

function shell_exec2($cmdline){
    $cmdline = str_replace("/usr/share/artica-postfix/ressources/exec.", "/usr/share/artica-postfix/exec.", $cmdline);

    if (function_exists("debug_backtrace")) {


        $trace = debug_backtrace();
        if (isset($trace[0])) {
            $T_FUNCTION = $trace[0]["function"];
            $T_LINE = $trace[0]["line"];
            $T_FILE = basename($trace[0]["file"]);
        }


        if (isset($trace[1])) {
            $T_FUNCTION = $trace[1]["function"];
            if (isset($trace[1]["line"])) {
                $T_LINE = $trace[1]["line"];
            }
            if (isset($trace[1]["file"])) {
                $T_FILE = basename($trace[1]["file"]);
            }
        }


    }


    if (!isset($GLOBALS["shell_exec2"])) {
        $GLOBALS["shell_exec2"] = array();
    }
    if (!is_array($GLOBALS["shell_exec2"])) {
        $GLOBALS["shell_exec2"] = array();
    }
    $md5 = md5($cmdline);
    $time = date("YmdHi");
    if (isset($GLOBALS["shell_exec2"][$time][$md5])) {
        if ($GLOBALS["VERBOSE"]) {
            echo "ERROR ALREADY EXECUTED $cmdline\n";
        }
        return false;
    }
    if (count($GLOBALS["shell_exec2"]) > 5) {
        $GLOBALS["shell_exec2"] = array();
    }
    $GLOBALS["shell_exec2"][$time][$md5] = true;
    if(is_file("/etc/init.d/go-exec")){
        $sh=$GLOBALS["CLASS_UNIX"]->sh_command($cmdline);
        $GLOBALS["CLASS_UNIX"]->go_exec($sh);
        return true;
    }

    if (!preg_match("#\/nohup\s+#", $cmdline)) {
        $cmdline = "{$GLOBALS["nohup"]} $cmdline";
    }
    if (!preg_match("#\s+>\/.*?2>\&1#", $cmdline)) {
        if (!preg_match("#\&$#", $cmdline)) {
            $cmdline = "$cmdline >/dev/null 2>&1 &";
        }
    }

    if ($GLOBALS["VERBOSE"]) {
        echo "EXEC $cmdline\n********************************\n";
    }
    if (!$GLOBALS["VERBOSE"]) {
        events("$T_FILE:$T_FUNCTION:$T_LINE:Execute: \"$cmdline\"", __FUNCTION__, __LINE__);
    }
    $sh=$GLOBALS["CLASS_UNIX"]->sh_command($cmdline);
    $GLOBALS["CLASS_UNIX"]->go_exec($sh);

    $GLOBALS["system_is_overloaded"] = false;
    if (function_exists("system_is_overloaded")) {
        $GLOBALS["system_is_overloaded"] = system_is_overloaded(basename(__FILE__));
    }
    return true;
}




function isSquidOldDbExists()
{
    if (!is_file("/etc/init.d/squid-db")) {
        return;
    }
    squid_admin_mysql(1, "Remove old 3.x MySQL database service", "Detected /etc/init.d/squid-db", __FILE__, __LINE__);
    remove_service("/etc/init.d/squid-db");
    if (is_file("/usr/share/artica-postfix/exec.squid-db.php")) {
        @unlink("/usr/share/artica-postfix/exec.squid-db.php");
    }
}

function isSquidRunning()
{

    isSquidOldDbExists();
    $pid = $GLOBALS["CLASS_UNIX"]->PIDOF("/usr/sbin/squid");
    $SQUIDEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));


    if (!$GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
        return;
    }
    if ($SQUIDEnable == 1) {
        return;
    }

    squid_admin_mysql(1, "Proxy service running but currently disabled, kill it", "Detected pid $pid", __FILE__, __LINE__);
    for ($i = 0; $i < 10; $i++) {
        $pid = $GLOBALS["CLASS_UNIX"]->PIDOF("/usr/sbin/squid");
        if (!$GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
            return;
        }
        $GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid, 9);
    }


}


function Default_values(){
    isSquidRunning();

    $nohup = $GLOBALS["CLASS_UNIX"]->find_program("nohup");
    $php = $GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
    $ifconfig=$GLOBALS["CLASS_UNIX"]->find_program("ifconfig");


    if (is_file("/etc/init.d/php7.0-fpm")) {
        squid_admin_mysql(1, "Removing unecessary service php7.0-fpm", null, __FILE__, __LINE__);
        remove_service("/etc/init.d/php7.0-fpm");
    }

    if(strlen($ifconfig)>5){
        $cmd="$ifconfig -a 2>&1";
        exec($cmd,$results);
        @file_put_contents(PROGRESS_DIR."/ifconfig.a.arr",base64_encode(serialize($results)));
    }

    $ziproxy = $GLOBALS["CLASS_UNIX"]->find_program("ziproxy");
    if(!is_null($ziproxy)) {
        $EnableProxyCompressor = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyCompressor"));
        if ($EnableProxyCompressor == 0) {
            if (is_file($ziproxy)) {
                $pid = $GLOBALS["CLASS_UNIX"]->PIDOF($ziproxy);
                if ($GLOBALS["CLASS_UNIX"]->process_exists($pid)) {
                    squid_admin_mysql(0, "Killing bad Process ziproxy (not enabled)",
                        @file_get_contents("/var/run/$pid/cmdline"), __FILE__, __LINE__);
                    $GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid, 9);
                }

                if (is_file("/etc/init.d/ziproxy")) {
                    squid_admin_mysql(0, "Uninstall ziproxy (not enabled)",
                        "/etc/init.d/ziproxy exists ", __FILE__, __LINE__);
                    shell_exec("$nohup $php /usr/share/artica-postfix/exec.zipproxy.php --uninstall >/dev/null 2>&1 &");
                }
            }
        }
    }
    if (is_file(base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2EvLmxpYw=="))) {
        @file_put_contents(base64_decode("L2V0Yy9hcnRpY2EtcG9zdGZpeC9zZXR0aW5ncy9EYWVtb25zL01haW5TZXJ2ZXJUeXBleg=="), 1);
    } else {
        @file_put_contents(base64_decode("L2V0Yy9hcnRpY2EtcG9zdGZpeC9zZXR0aW5ncy9EYWVtb25zL01haW5TZXJ2ZXJUeXBleg=="), 0);
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("fixed_hostname", $GLOBALS["CLASS_UNIX"]->hostname_simple());
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("fqdn_hostname", $GLOBALS["CLASS_UNIX"]->hostname_g());
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("netbiosname", $GLOBALS["CLASS_UNIX"]->hostname_simple());

    if (!is_file("/etc/artica-postfix/settings/Daemons/NetDataListenPort")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NetDataListenPort", 19999);
    }
    if (!is_file("/etc/artica-postfix/settings/Daemons/php5MemoryLimit")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("php5MemoryLimit", 1024);
        @chmod("/etc/artica-postfix/settings/Daemons/php5MemoryLimit", 0755);
    }

    if (!is_file("/etc/artica-postfix/settings/Daemons/WindowsUpdateUseLocalProxy")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WindowsUpdateUseLocalProxy", 1);
        @chmod("/etc/artica-postfix/settings/Daemons/WindowsUpdateUseLocalProxy", 0755);
    }
    if (!is_dir("/etc/artica-postfix/ldap_settings")) {
        @mkdir("/etc/artica-postfix/ldap_settings", 0755, true);
    }
    if (!is_file("/etc/artica-postfix/settings/Daemons/UnlockWebStats")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnlockWebStats", 0);
    }
    if (!is_file("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableIntelCeleron", 0);
    }
    if (!is_file("/etc/artica-postfix/ldap_settings/port")) {
        @file_put_contents("/etc/artica-postfix/ldap_settings/port", 389);
    }
    if (!is_file("/etc/artica-postfix/settings/Daemons/SquidDisableAllFilters")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidDisableAllFilters", 0);
    }
    if (!is_file("/etc/artica-postfix/settings/Daemons/CicapEnabled")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CicapEnabled", 0);
    }
    if (!is_file("/etc/artica-postfix/settings/Daemons/ArticaLogDir")) {
        @touch("/etc/artica-postfix/settings/Daemons/ArticaLogDir");
    }
    if (!is_file("/etc/cron.d/DirectoriesMonitor")) {
        $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("DirectoriesMonitor", "30 4 * * *", "exec.philesight.php --directories");
    }
    if (!is_file("/etc/artica-postfix/settings/Daemons/DisksBenchs")) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisksBenchs", 6);
        @chmod("/etc/artica-postfix/settings/Daemons/DisksBenchs", 0755);
    }
    if (!is_file("/etc/artica-postfix/settings/Daemons/SessionPathInMemory")) {
        $memoire = $GLOBALS["CLASS_UNIX"]->MEM_TOTAL_INSTALLEE();
        $memoire = round($memoire / 1024);
        if ($memoire > 512) {
            $SessionPathInMemory = 50;
        }
        if ($memoire > 699) {
            $SessionPathInMemory = 90;
        }
        if ($memoire > 999) {
            $SessionPathInMemory = 128;
        }
        if ($memoire > 1499) {
            $SessionPathInMemory = 256;
        }
        if ($memoire > 1999) {
            $SessionPathInMemory = 320;
        }
        if ($memoire > 2599) {
            $SessionPathInMemory = 512;
        }
        if ($memoire > 4999) {
            $SessionPathInMemory = 728;
        }
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SessionPathInMemory", $SessionPathInMemory);
        @chmod("/etc/artica-postfix/settings/Daemons/SessionPathInMemory", 0755);
    }


}






function Popuplate_cron():bool{

    if(!is_dir("/etc/cron.d")){@mkdir("/etc/cron.d",0755,true);}


    $EnableSquidQuotasBandwidth = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidQuotasBandwidth"));
    $SQUIDEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $ASQUID = true;
    $squidbin = $GLOBALS["CLASS_UNIX"]->LOCATE_SQUID_BIN();
    if (!is_file($squidbin)) {$ASQUID = false;$SQUIDEnable=0;}

    if ($SQUIDEnable == 0) {
        $ASQUID = false;
    }

    $CRON_RELOAD = false;


    if ($EnableSquidQuotasBandwidth == 1) {
        $SquidQuotaBandwidthRefresh = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidQuotaBandwidthRefresh"));
        $SquidQuotaBandwidthRefresh_array[15] = "0,15,30,45 * * * *";
        $SquidQuotaBandwidthRefresh_array[30] = "0,30 * * * *";
        $SquidQuotaBandwidthRefresh_array[60] = "0 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * *";
        $SquidQuotaBandwidthRefresh_array[120] = "0 2,4,6,8,10,12,14,16,18,20,22 * * *";
        $FileMD5 = @md5_file("/etc/cron.d/squid-squidbandquot");
        Popuplate_cron_make("squid-squidbandquot", $SquidQuotaBandwidthRefresh_array[$SquidQuotaBandwidthRefresh], "exec.quotaband.php");
        $FileMD52 = @md5_file("/etc/cron.d/squid-squidbandquot");
        if ($FileMD5 <> $FileMD52) {
            $CRON_RELOAD = true;
        }
    } else {
        if (is_file("/etc/cron.d/squid-squidbandquot")) {
            @unlink("/etc/cron.d/squid-squidbandquot");
            $CRON_RELOAD = true;
        }

    }


    if (!is_file("/etc/cron.d/PostgreSQL-failed")) {
        Popuplate_cron_make("PostgreSQL-failed", "0,5,10,15,20,25,30,35,40,45,50,55 * * * *", "exec.PostgreSQL-failed.php");
        $CRON_RELOAD = true;
    }

    if (!is_file("/etc/cron.d/PostgreSQL-count")) {
        Popuplate_cron_make("PostgreSQL-count", "0,30 * * * *", "exec.PostgreSQL-failed.php --count-files");
        $CRON_RELOAD = true;
    }


    if (is_file("/etc/cron.d/artica-rxtx-stats")) {
        @unlink("/etc/cron.d/artica-rxtx-stats");
        shell_exec("/etc/ini.d/cron restart");
    }




    if(is_file("/etc/init.d/squid")) {
        if (!is_file("/etc/cron.d/access-parser-logs")) {
            Popuplate_cron_make("access-parser-logs", "0 * * * *", "exec.parse.hourly.php");
            $CRON_RELOAD = true;
        }
    }


    if (is_file("/etc/cron.d/apache-parser-logs")) {
        @unlink("/etc/cron.d/apache-parser-logs");
        $CRON_RELOAD = true;
    }


    if (is_file("/etc/cron.d/artica-dnsperf")) {
        @unlink("/etc/cron.d/artica-dnsperf");
        $CRON_RELOAD = true;
    }

    if (is_file("/etc/cron.d/artica-dnsperf2")) {
        @unlink("/etc/cron.d/artica-dnsperf2");
        $CRON_RELOAD = true;
    }

    if (!is_file("/etc/cron.d/postgres-monthly")) {
        Popuplate_cron_make("postgres-monthly", "5 4 1,5,10,15,20,25,30 * *", "exec.postgres.clean.php");
        $CRON_RELOAD = true;
    }


    if (!is_file("/etc/cron.d/artica-clean-logs")) {
        Popuplate_cron_make("artica-clean-logs", "30 0,4,6,12 * * *", "exec.clean.logs.php --clean-tmp1");
        $CRON_RELOAD = true;
    }

    if (!is_file("/etc/cron.d/artica-clean-tmp")) {
        Popuplate_cron_make("artica-clean-logs", "30 1,6,20,23 * * *", "exec.clean.logs.php --clean-logs");
        $CRON_RELOAD = true;
    }
    if (!is_file("/etc/cron.d/artica-clean-syslog")) {
        Popuplate_cron_make("artica-clean-syslog", "30 5 * * *", "exec.clean.logs.php --varlog");
        $CRON_RELOAD = true;
    }


    if (!is_file("/etc/cron.d/artica-clean-RTTSize")) {
        if(is_file("/etc/init.d/squid")) {
            Popuplate_cron_make("artica-clean-RTTSize", "30 4 * * *", "exec.clean.logs.php --rttsize");
            $CRON_RELOAD = true;
        }
    }

    if (!is_file("/etc/cron.d/artica-interface-size")) {
        Popuplate_cron_make("artica-interface-size", "0,15,30,45 * * * *", "exec.squid.interface-size.php");
        $CRON_RELOAD = true;
    }
    if (!is_file("/etc/cron.d/artica-interface-hour")) {
        Popuplate_cron_make("artica-interface-hour", "25 * * * *", "exec.squid.interface-size.php --flux-hour");
        $CRON_RELOAD = true;
    }


    if (is_file("/usr/share/artica-postfix/exec.mpstat.php")) {
        @unlink("/usr/share/artica-postfix/exec.mpstat.php");
    }

    if (is_file("/etc/cron.d/artica-sys-alert")) {
        @unlink("/etc/cron.d/artica-sys-alert");
        $CRON_RELOAD = true;
    }


    if (is_file("/etc/cron.d/artica-usb-scan")) {
        @unlink("/etc/cron.d/artica-usb-scan");
    }


    if (!is_file("/etc/cron.d/artica-nightly")) {
        Popuplate_cron_make("artica-nightly", "0,30 * * * *", "exec.nightly.php");
        $CRON_RELOAD = true;
    }


    if (!is_file("/etc/cron.d/artica-process1")) {
        $CRON = array();
        $nice = $GLOBALS["CLASS_UNIX"]->EXEC_NICE();
        $php = $GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
        $PATH = "PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
        $CRON[] = "PATH=$PATH";
        $CRON[] = "MAILTO=\"\"";
        $CRON[] = "0 0,2,6,8,10,14,16,18,20 * * *\troot\t$nice $php /usr/share/artica-postfix/exec.status.php --process1 >/dev/null 2>&1";
        $CRON[] = "\n";
        file_put_contents("/etc/cron.d/artica-process1", @implode("\n", $CRON));
        chmod("/etc/cron.d/artica-process1", 0640);
        chown("/etc/cron.d/artica-process1", "root");
        $CRON_RELOAD = true;
        $CRON = array();
    }


    if ($CRON_RELOAD) {
        shell_exec2("/etc/init.d/cron reload");
    }
    return true;
}

function Popuplate_cron_make($cronfile, $schedule, $phpprocess)
{

    $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make($cronfile, $schedule, $phpprocess);

}

function shutdown()
{
    $error = error_get_last();
    $lastfunc = null;
    $type = null;
    $message = null;
    if (!isset($error["file"])) {
        $error["file"] = basename(__FILE__);
    }
    if (isset($error["type"])) {
        $type = trim($error["type"]);
    }
    if (isset($error["message"])) {
        $message = trim($error["message"]);
    }
    if ($message == null) {
        return;
    }
    $file = $error["file"];
    if (isset($GLOBALS["LAST_FUNCTION_USED"])) {
        $lastfunc = $GLOBALS["LAST_FUNCTION_USED"];
    }
    if (function_exists("openlog")) {
        openlog("artica-status", LOG_PID, LOG_SYSLOG);
    }
    if (function_exists("syslog")) {
        syslog(true, "$file: Last function: `$lastfunc` Fatal, stopped with error $type $message");
    }
    if (function_exists("closelog")) {
        closelog();
    }

}

function mkdir_test($dir, $bit = 0755, $continue = true)
{
    if (is_dir($dir)) {
        return;
    }
    @mkdir($dir, $bit, $continue);
}


?>