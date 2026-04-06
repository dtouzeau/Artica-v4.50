<?php
// Patch License.
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
if(isset($_GET["restart-winbind-tenir"])){restart_winbind_tenir();exit;}
if(isset($_GET["apt-get-install"])){ aptget_install();exit;}
if(isset($_GET["search"])){search_progress();exit;}
if(isset($_GET["restart-webconsole-scheduled"])){restart_webconsole_scheduled();exit;}
if(isset($_GET["netstart-log"])){netstart_log();exit;}
if(isset($_GET["automation-script"])){automation_script();exit;}
if(isset($_GET["nodes-export-tables"])){nodes_export_tables();exit;}
if(isset($_GET["import-ou2"])){import_ou_fromgz();exit;}
if(isset($_GET["syslogger"])){syslogger();exit;}
if(isset($_GET["postfix-single"])){postfix_single();exit;}
if(isset($_GET["nsswitch"])){nsswitch();exit;}
if(isset($_GET["nsswitch-tenir"])){nsswitch_tenir();exit;}
if(isset($_GET["cache-pages"])){cache_pages();exit;}
if(isset($_GET["squidlogs-oldlogs-test-nas"])){squidoldlogs_tests_nas();exit;}
if(isset($_GET["squidlogs-oldlogs-logs-nas"])){squidoldlogs_logs_nas();exit;}
if(isset($_GET["webdav-service"])){webdav_service();exit;}

if(isset($_GET["squidstats-test-nas"])){squidstats_tests_nas();exit;}
if(isset($_GET["execute-debian-mirror-rsync"])){debian_mirror_execute_rsync();exit;}
if(isset($_GET["recompile-postfix"])){recompile_postfix();exit;}
if(isset($_GET["restart-arp-daemon"])){restart_arpd();exit;}
if(isset($_GET["restart-vnstat"])){restart_vnstat();exit;}
if(isset($_GET["restart-winbindd"])){restart_winbindd();exit;}
if(isset($_GET["verifpackages"])){verifpackages();exit;}

if(isset($_GET["process1"])){process1();exit;}
if(isset($_GET["mysql-status"])){mysql_status();exit;}
if(isset($_GET["openldap-status"])){openldap_status();exit;}
if(isset($_GET["fetchmail-monit"])){fetchmail_monit();exit;}
if(isset($_GET["reload-haproxy"])){reload_haproxy();exit;}
if(isset($_GET["reload-adagent"])){reload_adagent();exit;}
if(isset($_GET["ModifyPam"])){ModifyPam();exit;}
if(isset($_GET["system-users"])){system_users();exit;}
if(isset($_GET["delete-system-user"])){system_users_del();exit;}
if(isset($_GET["remove-app"])){remove_application();exit;}
if(isset($_GET["refresh-setup-exe"])){refresh_applications();exit;}
if(isset($_GET["run-scheduled-task"])){run_schedules();exit;}
if(isset($_GET["run-scheduled-task"])){build_schedules();exit;}
if(isset($_GET["restart-arkeia"])){restart_arkeia();exit;}
if(isset($_GET["arkeia-ini-status"])){arkeia_status();exit;}
if(isset($_GET["build-system-tasks"])){build_system_tasks();exit;}
if(isset($_GET["refresh-my-ip"])){public_ip_refresh();exit;}
if(isset($_GET["reload-openldap-tenir"])){reload_openldap_tenir();exit;}
if(isset($_GET["process1-tenir"])){process1_tenir();exit;}
if(isset($_GET["system-defrag"])){system_defrag();exit;}

if(isset($_GET["license-migration"])){license_migration();exit;}
if(isset($_GET["license-register"])){register_license();exit;}
if(isset($_GET["register"])){register_server_www();exit;}
if(isset($_GET["pdns-status"])){pdns_status();exit;}
if(isset($_GET["dnsmasq-status"])){dnsmasq_status();exit;}
if(isset($_GET["dmesg"])){dmesg();exit;}
if(isset($_GET["copyFiles"])){copyFiles();exit;}
if(isset($_GET["DeleteFiles"])){DeleteFiles();exit;}
if(isset($_GET["port-list"])){ports_list();exit;}
if(isset($_GET["CleanCacheMem"])){CleanCacheMem();exit;}
if(isset($_GET["files-descriptors"])){file_descriptors_get();exit;}
if(isset($_GET["ssh-test"])){SSH_TEST_CONNECTION();exit;}
if(isset($_GET["greensql-status"])){greensql_status();exit;}
if(isset($_GET["greensql-reload"])){greensql_reload();exit;}
if(isset($_GET["greensql-logs"])){greensql_logs();exit;}
if(isset($_GET["restart-postfix-all"])){restart_postfix_all();exit;}
if(isset($_GET["restart-apache-groupware"])){restart_apache_groupware();exit;}
if(isset($_GET["restart-artica-status"])){restart_artica_status();exit;}
if(isset($_GET["restart-instant-messaging"])){restart_instant_messaging();exit;}



if(isset($_GET["stop-nscd"])){stop_nscd();exit;}
if(isset($_GET["restart-cron"])){restart_cron();exit;}
if(isset($_GET["restart-dhcpd"])){restart_dhcpd();exit;}
if(isset($_GET["restart-freshclam"])){restart_freshclam();exit;}
if(isset($_GET["restart-ipband"])){restart_ipband();exit;}
if(isset($_GET["restart-framework"])){restart_framework();exit;}
if(isset($_GET["restart-amavis"])){restart_amavis();exit;}
if(isset($_GET["restart-monit"])){restart_monit();exit;}
if(isset($_GET["kill-pid"])){kill_pid();exit;}
if(isset($_GET["php-cgi-array"])){php_cgi_array();exit;}


if(isset($_GET["localx"])){syslog_localx();exit;}
if(isset($_GET["KernelTuning"])){KernelTuning();exit;}
if(isset($_GET["iptables-save"])){iptables_save_query();exit;}
if(isset($_GET["iptables-dump"])){iptables_dump();exit;}
if(isset($_GET["iptables-delete"])){iptables_delete();exit;}



if(isset($_GET["stop-cicap"])){stop_cicap();exit;}
if(isset($_GET["start-cicap"])){start_cicap();exit;}
if(isset($_GET["restart-cicap"])){restart_cicap();exit;}
if(isset($_GET["cicap-events"])){events_cicap();exit;}
if(isset($_GET["admin-events"])){admin_events();exit;}
if(isset($_GET["mysql-ssl-keys"])){mysql_ssl_key();exit;}
if(isset($_GET["mysqld-perso"])){mysqld_perso();exit;}
if(isset($_GET["mysqld-perso-save"])){mysqld_perso_save();exit;}
if(isset($_GET["openemm-status"])){openemm_status();exit;}
if(isset($_GET["restart-openemm"])){openemm_restart();exit;}
if(isset($_GET["kerbauth"])){kerbauth();exit;}
if(isset($_GET["kerbauth-progress"])){kerbauth_progress();exit;}


if(isset($_GET["reload-pure-ftpd"])){pureftpd_reload();exit;}
if(isset($_GET["restart-ftp"])){pureftpd_restart();exit;}
if(isset($_GET["mysql-events"])){mysql_events();exit;}
if(isset($_GET["AdCacheMysql"])){AdCacheMysql();exit;}
if(isset($_GET["change-ldap-suffix"])){change_ldap_suffix();exit;}
if(isset($_GET["mysql-repair-database"])){mysql_repair_database();exit;}



if(isset($_GET["clock"])){GETclock();exit;}
if(isset($_GET["phpldapadmin"])){phpldapadmin();exit;}
if(isset($_GET["ntpd-status"])){ntpd_status();exit;}
if(isset($_GET["artica-patchs"])){artica_patchs();exit;}
if(isset($_GET["patchs-force"])){artica_patchs_force();exit;}
if(isset($_GET["mysql-ocs"])){mysql_ocs();exit;}
if(isset($_GET["pkg-upgrade"])){pkg_upgrade();exit;}
if(isset($_GET["schedule-apps"])){apps_upgrade();exit;}
if(isset($_GET["restart-arpd"])){restart_arpd();exit;}
if(isset($_GET["restart-squid"])){restart_squid();exit;}
if(isset($_GET["time-capsule-status"])){time_capsule_status();exit;}
if(isset($_GET["restart-netatalk"])){restart_netatalk();exit;}
if(isset($_GET["build-iptables"])){build_iptables();exit;}
if(isset($_GET["setquotas"])){setquotas();exit;}
if(isset($_GET["send-email-events"])){send_email_events_frame();exit;}
if(isset($_GET["chmod-rrd"])){chmod_rrd();exit;}
if(isset($_GET["reload-dkim"])){reload_dkim();exit;}
if(isset($_GET["dhcpd-conf"])){dhcpd_conf();exit;}
if(isset($_GET["SessionPathInMemoryInfos"])){SessionPathInMemoryInfos();exit;}
if(isset($_GET["updateutility-local"])){updateutility_local();exit;}
if(isset($_GET["KERNEL_CONFIG"])){KERNEL_CONFIG();exit;}
if(isset($_GET["ARTICA-MAKE"])){ARTICA_MAKE_STATUS();exit;}
if(isset($_GET["service-dropbox-cmds"])){service_dropbox_cmd();exit;}
if(isset($_GET["beancounters"])){beancounters();exit;}
if(isset($_GET["export-etc-artica"])){export_etc_artica();exit;}
if(isset($_GET["folders-security"])){folders_security();exit;}
if(isset($_GET["blackbox-notify"])){blackbox_notify();exit;}
if(isset($_GET["sysev"])){sysev();exit;}
if(isset($_GET["blkid"])){blkid_infos();exit;}
if(isset($_GET["dir-status"])){dir_status();exit;}


foreach ($_GET as $num=>$line){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);


function verifpackages(){
    $unix=new unix();
    $unix->framework_execute("exec.verif.packages.php --force","verifpackages.progress","verifpackages.log");
}



function arkeia_status(){
    exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --arkeia --nowachdog",$results);
    echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}
function openldap_status(){
    $cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --openldap --nowachdog >/usr/share/artica-postfix/ressources/logs/web/openldap.status";
    shell_exec($cmd);
    writelogs_framework("$cmd ",__FUNCTION__,__FILE__,__LINE__);


}


function system_defrag(){
    $unix=new unix();
    $shutdown=$unix->find_program("shutdown");

    exec("$shutdown -rF now 2>&1",$results);
    writelogs_framework("$shutdown -rF now",__FUNCTION__,__FILE__,__LINE__);
    foreach ($results as $num=>$val){
        writelogs_framework("$val",__FUNCTION__,__FILE__,__LINE__);
    }

}






function webdav_service(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.davservice.php";
    shell_exec("$nohup $cmd >/dev/null 2>&1 &");
    writelogs_framework("$cmd ",__FUNCTION__,__FILE__,__LINE__);

}

function ARTICA_MAKE_STATUS(){
    $unix=new unix();
    $pgrep=$unix->find_program("pgrep");
    $master_pid=0;
    exec("$pgrep -l -f \"bin/artica-make\"",$results);
    foreach ($results as $num=>$line){
        if(preg_match("#pgrep#", $line)){continue;}
        if(preg_match("#^([0-9]+)\s+sh\s+#", $line,$re)){continue;}
        if(preg_match("#^([0-9]+)\s+.+?artica-make\s+([A-Z\_0-9]+)#", $line,$re)){
            $pid=$re[1];
            $time=$unix->PROCESS_TTL_TEXT($pid);
            $SOFT=$re[2];
            $array[$SOFT]=$time;
        }
    }
    echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";

}





function dmesg(){
    $unix=new unix();
    $dmesg=$unix->find_program("dmesg");
    exec("$dmesg 2>&1",$results);
    echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";

}
function syslogger(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/artica-syslog restart >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function start_cicap(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("/etc/init.d/artica-postfix start cicap >/dev/null 2>&1");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}
function stop_cicap(){
    $cmd=trim("/etc/init.d/artica-postfix stop cicap >/dev/null 2>&1");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}
function restart_cicap(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/artica-postfix restart cicap >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}
function chmod_rrd(){
    $unix=new unix();
    $chmod=$unix->find_program("chmod");
    $cmd=trim("$chmod 755 /opt/artica/var/rrd/* >/dev/null 2>&1");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function ports_list(){
    $unix=new unix();
    $lsof=$unix->find_program("lsof");
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("head");
    $search=null;
    if($_GET["port-list"]<>null){
        $search=base64_decode($_GET["port-list"]);
        $search=str_replace(".", "\.", $search);
        $search=str_replace("*", ".*?", $search);
        $search="|$grep --binary-files=text -Ei ".escapeshellarg($search);
    }
    $rp_val=intval($_GET["rp"]);
    $tail="|$tail -n $rp_val";
    $cmdline="$lsof -Pnl +M -i4$search$tail 2>&1";


    exec($cmdline,$results);
    writelogs_framework("$cmdline",__FUNCTION__,__FILE__,__LINE__);
    echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function restart_cron(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/artica-postfix restart fcron >/dev/null 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function restart_arpd(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/arpd restart >/dev/null 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}





function ServicesToSyslog($text){

    $LOG_SEV=LOG_INFO;
    if(function_exists("openlog")){openlog("framework-service", LOG_PID , LOG_SYSLOG);}
    if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
    if(function_exists("closelog")){closelog();}
}



function restart_vnstat(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/vnstat restart >/dev/null 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}



function restart_arkeia(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/artica-postfix restart arkeia >/dev/null 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function restart_ipband(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup ".$unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.ipband.php --restart >/dev/null 2>&1");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}



function restart_netatalk(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/artica-postfix restart netatalk >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function restart_freshclam(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    if(is_file("/etc/init.d/clamav-freshclam")){
        $cmd=trim("$nohup /etc/init.d/clamav-freshclam restart >/dev/null 2>&1 &");
        shell_exec($cmd);
    }
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function restart_dhcpd(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/artica-postfix restart dhcp >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function updateutility_local(){
    $d=base64_encode(@file_get_contents("/etc/UpdateUtility/locale.ini"));
    echo "<articadatascgi>$d</articadatascgi>";
}


function pkg_upgrade(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $NICE=$unix->EXEC_NICE();
    $cmd=trim("$NICE $php5 /usr/share/artica-postfix/exec.apt-get.php --pkg-upgrade >/dev/null 2>&1 &");
    shell_exec("$cmd");
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);

}

function apps_upgrade(){
    writelogs_framework("Fatal! Disabled function",__FUNCTION__,__FILE__,__LINE__);
}








function restart_postfix_all(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/postfix restart-heavy >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function restart_apache_groupware(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/artica-postfix restart apache-groupware >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function restart_squid(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/artica-postfix restart squid >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function restart_artica_status(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}
function restart_instant_messaging(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/artica-postfix restart ejabberd >/dev/null 2>&1 &");
    shell_exec($cmd);
    $cmd=trim("$nohup /etc/init.d/artica-postfix restart pymsnt >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}


function stop_nscd(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/nscd stop >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function kerbauth(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup ".$unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.nltm.connect.php >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}


function kerbauth_progress(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.ad.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.ad.progress.log";
    @file_put_contents($GLOBALS["PROGRESS_FILE"], "\n");
    @file_put_contents($GLOBALS["LOGSFILES"], "\n");
    @chmod($GLOBALS["PROGRESS_FILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $cachefile="/usr/share/artica-postfix/ressources/logs/web/AdConnnection.status";
    @unlink($cachefile);
    $cmd=trim("$nohup ".$unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.nltm.connect.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function artica_patchs(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup ".$unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.patchs.php");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}
function artica_patchs_force(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup ".$unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.patchs.php --force");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}



function postfix_single(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/postfix restart-single >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function nsswitch(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("/usr/sbin/artica-phpfpm-service -nsswitch");
    @mkdir("/etc/artica-postfix/pids");
    $timeFile="/etc/artica-postfix/pids/nsswitch.time";

    if($unix->file_time_min($timeFile)>10){
        @unlink($timeFile);
        @file_put_contents($timeFile, time());
        shell_exec($cmd);
        writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    }
}
function nsswitch_tenir(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("/usr/sbin/artica-phpfpm-service -nsswitch");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}
function process1(){
    $unix=new unix();
    $unix->Process1(true);
}

function greensql_reload(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /usr/share/artica-postfix/bin/artica-install --greensql-reload ". time()." >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}



function time_capsule_status(){
    exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --time-capsule --nowachdog",$results);
    echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}



function restart_webconsole_scheduled(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/artica-webconsole restart-paused >/dev/null 2>&1 &");
    shell_exec($cmd);


}


function greensql_logs(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $tail=$unix->find_program("tail");
    $cmd=trim("$tail -n 300 /var/log/greensql.log 2>&1 ");

    exec($cmd,$results);
    writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
    echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function openemm_status(){
    exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --openemm --nowachdog 2>&1",$results);
    echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}

function ntpd_status(){
    exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --ntpd --nowachdog 2>&1",$results);
    echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}
function dnsmasq_status(){
    exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --dnsmasq --nowachdog 2>&1",$results);
    echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}
function pdns_status(){
    exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --pdns --nowachdog 2>&1",$results);
    echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}

function ModifyPam(){
    $cmd=trim("/usr/sbin/artica-phpfpm-service -build-pam");
    shell_exec($cmd);
}

function openemm_restart(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/artica-postfix restart openemm >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function reload_dkim(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup ".$unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.dkim-milter.php --build --reload >/dev/null 2>&1");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function mysql_ocs(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim($unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.mysql.build.php --checks >/dev/null 2>&1");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}



function build_iptables(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim($nohup." ".$unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.iptables.php >/dev/null 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function setquotas(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim($nohup." ".$unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.quotaroot.php --users >/dev/null 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}



function GETclock(){
    $unix=new unix();
    $date=$unix->find_program("date");
    $hwclock=$unix->find_program("hwclock");
    exec("$date +\"%Y-%m-%d;%H:%M:%S\" 2>&1",$results);
    $dateTEXT=@implode("",$results);
    if(is_file($hwclock)){
        exec("$hwclock --show 2>&1",$results2);
        writelogs_framework("$hwclock --show ". count($results2)." rows",__FUNCTION__,__FILE__,__LINE__);
        $hwclockTEXT=@implode("",$results2);
    }else{
        writelogs_framework("hwclock no such binary",__FUNCTION__,__FILE__,__LINE__);
    }
    writelogs_framework("$dateTEXT|$hwclockTEXT",__FUNCTION__,__FILE__,__LINE__);
    $array[0]=$dateTEXT;
    $array[1]=$hwclockTEXT;
    $finale=base64_encode(serialize($array));

    echo "<articadatascgi>$finale</articadatascgi>";

}
function send_email_events_frame(){
    $array=unserialize(base64_decode($_GET["send-email-events"]));
    $unix=new unix();
    $unix->send_email_events($array["SUBJECT"], $array["TEXT"], $array["CONTEXT"]);
}


function SessionPathInMemoryInfos(){
    $unix=new unix();
    $array=$unix->SessionPathInMemoryInfos();
    $finale=base64_encode(serialize($array));
    echo "<articadatascgi>$finale</articadatascgi>";

}
function dhcp3Config(){

    $f[]="/etc/dhcp3/dhcpd.conf";
    $f[]="/etc/dhcpd.conf";
    $f[]="/etc/dhcpd/dhcpd.conf";
    foreach ($f as $index=>$filename){
        if(is_file($filename)){return $filename;}
    }
    return "/etc/dhcp3/dhcpd.conf";

}

function dhcpd_conf(){
    echo "<articadatascgi>". base64_encode(@file_get_contents(dhcp3Config()))."</articadatascgi>";

}
function KERNEL_CONFIG(){
    $unix=new unix();
    $array=$unix->KERNEL_CONFIG();
    writelogs_framework(count($array)." Items",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/KERNEL_CONFIG", serialize($array));
    echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}
function events_cicap(){
    $unix=new unix();
    $syslog=$unix->LOCATE_SYSLOG_PATH();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $cmd="$grep -i \"c-icap:\" $syslog 2>&1|$tail -n 500 >/usr/share/artica-postfix/ressources/logs/web/cicap.events 2>&1";
    shell_exec("$cmd");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function restart_framework(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim($nohup." /etc/init.d/artica-postfix restart framework >/dev/null 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function restart_amavis(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim($nohup." /etc/init.d/amavis restart >/dev/null 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function restart_monit(){
    $unix=new unix();
    $unix->THREAD_COMMAND_SET("/etc/init.d/artica-postfix restart monit");
    writelogs_framework("/etc/init.d/artica-postfix restart monit",__FUNCTION__,__FILE__,__LINE__);

}




function DeleteFiles(){
    $array=unserialize(base64_decode($_GET["DeleteFiles"]));
    if(is_file($array["FileDest"])){@unlink($array["FileDest"]);}
}

function copyFiles(){
    $array=unserialize(base64_decode($_GET["copyFiles"]));
    @copy($array["FROM"], $array["TO"]);
    @chmod($array["TO"], 0775);

}
function kill_pid(){
    $unix=new unix();
    $kill=$unix->find_program("kill");
    $pid=$_GET["kill-pid"];
    if(!is_numeric($pid)){return;}
    if($pid<10){return;}
    unix_system_kill_force($pid);

}




function fetchmail_monit(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim($nohup." ".$unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.fetchmail.php --monit >/dev/null 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function CleanCacheMem(){
    $unix=new unix();
    $sync=$unix->find_program("sync");
    shell_exec($sync);
    @file_put_contents("/proc/sys/vm/drop_caches", "3");
}
function file_descriptors_get(){
    $unix=new unix();
    $fileCache="/usr/share/artica-postfix/ressources/logs/web/files-descriptors";
    $sysctl=$unix->find_program("sysctl");
    $cmd="$sysctl fs.file-nr 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    exec("$cmd",$results);
    if(preg_match("#=\s+([0-9]+)\s+[0-9]+\s+([0-9]+)#", @implode("", $results),$re)){
        $array=array("MINI"=>$re[1],"MAXI"=>$re[2]);
        $FINAL=serialize($array);
        @unlink($fileCache);
        @file_put_contents($fileCache, $FINAL);
        @chmod($fileCache, 0777);
        echo "<articadatascgi>". base64_encode($FINAL)."</articadatascgi>";
    }
}

function php_cgi_array(){
    $unix=new unix();
    $pgrep=$unix->find_program("pgrep");
    $php_cgi=$unix->find_program("php-cgi");

    $cmd="$pgrep -l -f \"$php_cgi\" 2>&1";

    exec("$pgrep -l -f \"$php_cgi\" 2>&1",$results);
    writelogs_framework("$cmd ->".count($results)." line",__FUNCTION__,__FILE__,__LINE__);


    foreach ($results as $num=>$ligne){
        if(preg_match("#\/pgrep#", $ligne)){continue;}
        if(preg_match("#([0-9]+)\s+#", $ligne,$re)){
            $pid=$re[1];
            $PPID=$unix->PPID_OF($pid);
            $rss0=$unix->PROCESS_MEMORY($pid,true);
            $vm0=$unix->PROCESS_CACHE_MEMORY($pid,true);
            $TTL=$unix->PROCESS_TTL_TEXT($pid);
            $PPID2=$unix->PPID_OF($PPID);
            if($PPID2>0){if($PPID2<>$pid){$PPID=$PPID2;}}

            $ARRAY[$PPID][$pid]["RSS"]=$unix->PROCESS_MEMORY($pid,true);
            $ARRAY[$PPID][$pid]["VM"]=$unix->PROCESS_CACHE_MEMORY($pid,true);
            $ARRAY[$PPID][$pid]["TTL"]=$unix->PROCESS_TTL_TEXT($pid,true);
        }

    }

    echo "<articadatascgi>". base64_encode(serialize($ARRAY))."</articadatascgi>";

}



function reload_haproxy(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim($nohup." ".$unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.haproxy.php --reload >/dev/null 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function reload_adagent(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim($nohup." ".$unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.ad-agent.php --reload >/dev/null 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}



function SSH_TEST_CONNECTION(){
    $unix=new unix();
    $uid=$_GET["uid"];
    $hostname=$_GET["ssh-test"];
    $sshbin=$unix->find_program("ssh");

    $tmp=$unix->TEMP_DIR();
    $tt[]="Host $hostname";
    $tt[]="\tStrictHostKeyChecking no";
    $tt[]="\tUserKnownHostsFile=/dev/null";
    @file_put_contents("$tmp/$hostname.$uid", @implode("\n", $tt));
    $cmd="$sshbin $hostname -F $tmp/$hostname.$uid -qq -l $uid -i /home/$uid/.ssh/id_rsa -v -n 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    exec($cmd,$results);
    writelogs_framework(count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/$uid.ssh", @implode("\n", $results));
    @chmod("/usr/share/artica-postfix/ressources/logs/web/$uid.ssh", 0777);
}
function system_users(){


    $f=explode("\n",@file_get_contents("/etc/passwd"));
    foreach ($f as $index=>$line){
        $t=explode(":",$line);
        $array[$t[0]]=array("UID"=>$t[2],"GID"=>$t[3],"DESC"=>$t[4]);
    }
    echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";


}
function system_users_del(){
    $unix=new unix();
    $userdel=$unix->find_program("userdel");
    $cmd="$userdel ".escapeshellarg($_GET["delete-system-user"]);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function refresh_applications(){
}

function remove_application(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.uninstall.php --app ".escapeshellarg($_GET["remove-app"])." >/dev/null 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function blkid_infos(){
    $dev=$_GET["blkid"];
    $unix=new unix();
    $array=$unix->BLKID_INFOS($dev);
    echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}

function dir_status(){
    $dev=$_GET["dir-status"];
    $unix=new unix();
    $array=$unix->DIR_STATUS($dev);
    writelogs_framework("DIR_STATUS -> ".count($array),__FUNCTION__,__FILE__,__LINE__);
    echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";

}



function import_ou_fromgz(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $filename=$_GET["filename"];
    $ou=$_GET["import-ou2"];
    $cmd="$php5 /usr/share/artica-postfix/exec.import-users.php --org \"$ou\" \"$filename\" --verbose 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    exec($cmd,$results);
    echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";

}


function syslog_localx(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.syslog-engine.php --localx >/dev/null 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function KernelTuning(){
    $unix   = new unix();
    $unix->go_exec("/usr/sbin/artica-phpfpm-service -sysctl");

}
function build_schedules(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.schedules.php --run-schedules ".escapeshellarg($_GET["run-scheduled-task"])." >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function run_schedules(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.schedules.php --run-schedules ".escapeshellarg($_GET["run-scheduled-task"])." >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}
function build_system_tasks(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();

    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/tasks.compile.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/tasks.compile.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.schedules.php --output >/usr/share/artica-postfix/ressources/logs/web/tasks.compile.txt 2>&1 &");
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);



}

function register_server_www(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --register >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.schedules.php --output >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --build-schedules >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function license_migration(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();

    $CACHEFILE="/usr/share/artica-postfix/ressources/logs/web/artica.license.progress";
    $LOGSFILES="/usr/share/artica-postfix/ressources/logs/web/artica_license.txt";
    @unlink($CACHEFILE);
    @unlink($LOGSFILES);
    @touch($LOGSFILES);
    @touch($CACHEFILE);
    @chmod($CACHEFILE,0755);
    @chmod($LOGSFILES,0755);

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --register-lic --migration >$LOGSFILES 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function register_license():bool{
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();


    $CACHEFILE="/usr/share/artica-postfix/ressources/logs/artica.license.progress";
    $LOGSFILES="/usr/share/artica-postfix/ressources/logs/web/artica_license.txt";

    @unlink($CACHEFILE);
    @unlink($LOGSFILES);
    @touch($LOGSFILES);
    @touch($CACHEFILE);
    @chmod($CACHEFILE,0755);
    @chmod($LOGSFILES,0755);
    @file_put_contents("/etc/artica-postfix/settings/Daemons/NewLicServer",1);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --register-lic --email >$LOGSFILES 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    return true;
}


function public_ip_refresh(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php5 /usr/share/artica-postfix/exec.my-rbl.check.php --myip --force");
}

function admin_events(){
    $serialize=base64_decode($_GET["admin-events"]);
    $md5=md5($serialize);
    if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/system_admin_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/system_admin_events",0755,true);}
    @file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/system_admin_events/$md5.log", $serialize);
}

function iptables_delete(){
    $index=$_GET["iptables-delete"];
    $unix=new unix();
    $iptables=$unix->find_program("iptables");
    $cmd="$iptables --delete INPUT $index";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

    $cachefile="/etc/artica-postfix/IPTABLES_INPUT";
    @unlink($cachefile);
    shell_exec("$iptables -L --line-numbers -n >$cachefile 2>&1");
}

function iptables_dump(){
    $unix=new unix();
    $iptables=$unix->find_program("iptables");
    $cachefile="/etc/artica-postfix/IPTABLES_INPUT";
    $size=@filesize($cachefile);
    if($size==0){@unlink($cachefile);}
    if(!is_file($cachefile)){
        shell_exec("$iptables -L --line-numbers -n >$cachefile 2>&1");
    }

    $head=$unix->find_program("head");
    $rp=intval($_GET["rp"]);
    $head="$head -n $rp";
    if($_GET["search"]<>null){
        $search=base64_decode($_GET["search"]);
        $grep=$unix->find_program("grep");
        $cmd="$grep --binary-files=text -Ei '$search' $cachefile|$head";
    }else{
        $cmd="$head $cachefile 2>&1";
    }

    exec($cmd,$results);
    writelogs_framework("$cmd ".count($results)." rows - $size bytes -",__FUNCTION__,__FILE__,__LINE__);

    echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";

}

function iptables_save_query(){
    $cachefile="/etc/artica-postfix/iptables-save.tmp";
    $unix=new unix();
    if(is_file($cachefile)){
        $timin=$unix->file_time_min($cachefile);
        if($timin>5){@unlink($cachefile);}
    }
    if(!is_file($cachefile)){
        $iptables_save=$unix->find_program("iptables-save");
        shell_exec("$iptables_save >$cachefile 2>&1");
    }
    $head=$unix->find_program("head");
    $rp=intval($_GET["rp"]);
    $head="$head -n $rp";
    if($_GET["search"]<>null){
        $search=base64_decode($_GET["search"]);
        $grep=$unix->find_program("grep");
        $cmd="$grep --binary-files=text -Ei '$search' $cachefile|$head";
    }else{
        $cmd="$head $cachefile 2>&1";
    }

    exec($cmd,$results);
    writelogs_framework("$cmd ".count($results)." rows",__FUNCTION__,__FILE__,__LINE__);

    echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";


}

function change_ldap_suffix(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();
    $logfile="/usr/share/artica-postfix/ressources/logs/web/change.ldap.suffix.log";
    $cmdline="$nohup $php /usr/share/artica-postfix/exec.ldap.php --change-suffix >$logfile 2>&1 &";
    writelogs_framework("$cmdline",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmdline);

}






function sysev(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $hostid=$_GET["blackbox-notify"];
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php /usr/share/artica-postfix/exec.syslog-engine.php --sysev >/dev/null 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}


function blackbox_notify(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $hostid=$_GET["blackbox-notify"];
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php /usr/share/artica-postfix/exec.blackbox.php --ping $hostid >/dev/null 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function reload_openldap_tenir(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $SLAPD_INITD_PATH=$unix->SLAPD_INITD_PATH();
    $cmd="$php /usr/share/artica-postfix/exec.initslapd.php >/dev/null 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    $cmd="$SLAPD_INITD_PATH restart 2>&1";
    exec($cmd,$results);
    echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}
function service_dropbox_cmd(){
    $servicecmd=$_GET["service-dropbox-cmds"];
    exec("/etc/init.d/artica-postfix $servicecmd dropbox 2>&1",$results);
    echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function beancounters(){
    $unix=new unix();
    echo "<articadatascgi>". base64_encode(serialize(file("/proc/user_beancounters")))."</articadatascgi>";

}
function process1_tenir(){
    writelogs_framework("/usr/bin/php /usr/share/artica-postfix/exec.status.php --process1 --force",__FUNCTION__,__FILE__,__LINE__);
    if(!is_file("/etc/init.d/artica-process1")){return;}
    exec("/etc/init.d/artica-process1 start 2>&1",$results);
    foreach ($results as $num=>$line){
        writelogs_framework($line,__FUNCTION__,__FILE__,__LINE__);
    }
}
function export_etc_artica(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.export-artica-settings.php");

}

function nodes_export_tables(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php /usr/share/artica-postfix/exec.squid.php --export-tables >/dev/null 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}


function folders_security(){
    shell_exec("/usr/sbin/artica-phpfpm-service -permission-watch");
}




function restart_winbind_tenir(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd="$php /usr/share/artica-postfix/exec.winbindd.php --restart --force >/dev/null 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}



function cache_pages(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();

    @chmod("/usr/bin/php /usr/share/artica-postfix/exec.status.php --process1",0755);
    $cmd="$nohup /usr/bin/php /usr/share/artica-postfix/exec.status.php --process1 --force --verbose --".time()." >/dev/null 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

    $cmd="$nohup $php /usr/share/artica-postfix/exec.squid.interface-size.php --force >/dev/null 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}



function squidoldlogs_tests_nas(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd="$php /usr/share/artica-postfix/exec.squid.logs.import.php --test-nas --verbose 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    exec($cmd,$results);
    echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function squidoldlogs_logs_nas(){
    $unix=new unix();
    $f=array();
    $pidfile="/etc/artica-postfix/pids/exec.squid.logs.import.php.analyze_all.pid";
    $pid=$unix->get_pid_from_file($pidfile);
    writelogs_framework("Last PID $pid",__FUNCTION__,__FILE__,__LINE__);
    $f=explode("\n",@file_get_contents("/var/log/squid-import-logs.debug"));
    if($unix->process_exists($pid)){
        writelogs_framework("running PID $pid",__FUNCTION__,__FILE__,__LINE__);
        array_unshift($f,"***** Currently running PID $pid ******");
    }

    writelogs_framework(count($f)." elements",__FUNCTION__,__FILE__,__LINE__);
    echo "<articadatascgi>". base64_encode(serialize($f))."</articadatascgi>";
}

function squidstats_tests_nas(){
    $unix=new unix();

    $php=$unix->LOCATE_PHP5_BIN();
    $cmd="$php /usr/share/artica-postfix/exec.squidlogs.purge.php --test-nas 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    exec($cmd,$results);
    echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function debian_mirror_execute_rsync(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.debian.mirror.php --start-exec-manu >/dev/null 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function recompile_postfix(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    @unlink("/usr/share/artica-postfix/ressources/logs/web/POSTFIX_COMPILES");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --reconfigure >/dev/null 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function restart_winbindd(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    if(is_file("/etc/init.d/winbind")){
        $cmd="$nohup /etc/init.d/winbind restart >/dev/null 2>&1 &";
        shell_exec($cmd);
    }
}


function mysql_repair_database(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/RepairMysql.log", "\n");
    @chmod("/usr/share/artica-postfix/ressources/logs/web/RepairMysql.log",0777);
    shell_exec("$nohup $php /usr/share/artica-postfix/exec.mysql.clean.php --corrupted --verbose >> /usr/share/artica-postfix/ressources/logs/web/RepairMysql.log 2>&1 &");

}


function ToSyslog($text){
    if(!function_exists("syslog")){return;}
    $file=basename(__FILE__);
    $LOG_SEV=LOG_INFO;
    openlog("framework", LOG_PID , LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
}


function netstart_log(){
    $data=@file_get_contents("/var/log/net-start.log");
    echo "<articadatascgi>". base64_encode($data)."</articadatascgi>";
}



function automation_script(){
    $PROGRESS_FILE="/usr/share/artica-postfix/ressources/logs/web/wizard.progress";
    $LOGS="/usr/share/artica-postfix/ressources/logs/wizard.progress.txt";
    @file_put_contents($PROGRESS_FILE, "\n");
    @file_put_contents($LOGS, "\n");
    @chmod($LOGS,0777);
    @chmod($PROGRESS_FILE,0777);
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.wizard-install.php --automation >/usr/share/artica-postfix/ressources/logs/wizard.progress.txt 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}






function search_progress(){


    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/fw.search.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/fw.search.txt";

    @file_put_contents($GLOBALS["PROGRESS_FILE"], "\n");
    @file_put_contents($GLOBALS["LOGSFILES"], "\n");
    @chmod($GLOBALS["PROGRESS_FILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.top.search.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}



function aptget_install(){

    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/php7install.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/php7install.log";

    @file_put_contents($GLOBALS["PROGRESS_FILE"], "\n");
    @file_put_contents($GLOBALS["LOGSFILES"], "\n");
    @chmod($GLOBALS["PROGRESS_FILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.apt-get.php --install ".escapeshellarg($_GET["apt-get-install"])." >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
