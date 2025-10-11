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
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.opendlap.certificates.inc');


if($GLOBALS["VERBOSE"]){echo "Starting analyze command lines\n";$GLOBALS["OUTPUT"]=true;}
if(isset($argv[1])){
    if($argv[1]=="syslog-deb"){exit();}
    if($argv[1]=="--ldap-client"){$GLOBALS["OUTPUT"]=true;ldap_client();exit();}
    if($argv[1]=="--getty"){$GLOBALS["OUTPUT"]=true;getty();exit();}


    if($argv[1]=="--dnsmasq"){exit();}
    if($argv[1]=="--nscd"){nscd_init_debian();exit();}
    if($argv[1]=="--start"){exit;}
    if($argv[1]=="--stop"){exit;}
    if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart_ldap();exit;}
    if($argv[1]=="--notify-monit-stop"){notify_monit_stop();exit;}
    if($argv[1]=="--mailarchive-perl"){mailarchive_perl();exit;}
    if($argv[1]=="--freeradius"){exit;}
    if($argv[1]=="--restart-www"){die();}

    if($argv[1]=="--ftp-proxy"){ftpproxy();exit;}
    if($argv[1]=="--framework"){exit;}
    if($argv[1]=="--phppfm"){phppfm();exit;}
    if($argv[1]=="--phppfm-fix"){phppfm_fix();exit;}
    if($argv[1]=="--phppfm-restart-back"){phppfm_restartback();exit;}
    if($argv[1]=="--mysql"){mysqlInit();exit;}
    if($argv[1]=="--ubuntu"){CleanUbuntu();exit;}
    if($argv[1]=="--roundcube"){roundcube_http();exit;}
    if($argv[1]=="--fetchmail"){fetchmail();exit;}
    if($argv[1]=="--milter-greylist"){exit();}
    if($argv[1]=="--vde-switch"){vde_switch();exit;}
    if($argv[1]=="--vnstat"){exit();}
    if($argv[1]=="--artica-syslog"){die();}
    if($argv[1]=="--artica-fpm"){exit;}
    if($argv[1]=="--freshclam"){clamav_freshclam();exit;}
    if($argv[1]=="--webservices"){webservices();exit;}
    if($argv[1]=="--squid-db"){$GLOBALS["OUTPUT"]=true;exit;}
    if($argv[1]=="--bandwidthd"){$GLOBALS["OUTPUT"]=true;bandwidthd();exit;}
    if($argv[1]=="--clamav-milter"){$GLOBALS["OUTPUT"]=true;clamav_milter();exit;}
    if($argv[1]=="--ldap-monit"){$GLOBALS["OUTPUT"]=true;build_monit();exit;}
    if($argv[1]=="--squid-stats-central"){$GLOBALS["OUTPUT"]=true;exit();}
    if($argv[1]=="--wifidog"){$GLOBALS["OUTPUT"]=true;exit;}

    if($argv[1]=="--syncthing"){$GLOBALS["OUTPUT"]=true;syncthing();exit;}
    if($argv[1]=="--hypercache-web"){exit();}
    if($argv[1]=="--not-shutdown"){$GLOBALS["OUTPUT"]=true;not_shutdown();exit;}
    if($argv[1]=="--postgres"){system("/usr/sbin/artica-phpfpm-service -install-postgresql");exit;}
    if($argv[1]=="--squid-tail"){$GLOBALS["OUTPUT"]=true;exit;}
    if($argv[1]=="--hypercache-tail"){$GLOBALS["OUTPUT"]=true;hypercache_tail();exit;}
    if($argv[1]=="--ldap-client"){$GLOBALS["OUTPUT"]=true;ldap_client();exit;}
    if($argv[1]=="--policyd-weight"){$GLOBALS["OUTPUT"]=true;policyd_weight();exit;}
    if($argv[1]=="--transmission-daemon"){exit;}
        if($argv[1]=="--rest-on"){$GLOBALS["OUTPUT"]=true;enable_rest_api();exit;}
    if($argv[1]=="--rest-off"){$GLOBALS["OUTPUT"]=true;disable_rest_api();exit;}
    if($argv[1]=="--remove-postgres"){system("/usr/sbin/artica-phpfpm-service -uninstall-postgresql");exit;}
    if($argv[1]=="--install-postgres"){system("/usr/sbin/artica-phpfpm-service -install-postgresql");exit;}
}
$unix=new unix();
if($GLOBALS["VERBOSE"]){echo "Open unix class\n";}

$PID_FILE="/etc/artica-postfix/pids/".basename(__FILE__);
$PID_TIME="/etc/artica-postfix/pids/".basename(__FILE__).".time";

$timeF=$unix->file_time_min($PID_TIME);
if(!$GLOBALS["FORCE"]){
    if($timeF<3){
        echo "slapd: [INFO] Executed since {$timeF}Mn die (use --force to bypass)..\n";
        exit();
    }
}

@unlink($PID_TIME);
@file_put_contents($PID_TIME, time());
$pid=$unix->get_pid_from_file($PID_FILE);
$php=$unix->LOCATE_PHP5_BIN();
if($unix->process_exists($pid)){
    $timepid=$unix->PROCCESS_TIME_MIN($pid,120);
    echo "slapd: [INFO] Already executed pid $pid since {$timepid}mn\n";
    exit();
}

@file_put_contents($PID_FILE, getmypid());
$GLOBALS["OUTPUT"]=true;
@mkdir("/home/artica/SQLITE",0755,true);
shell_exec("$php /usr/share/artica-postfix/exec.convert-to-sqlite.php");


$functions=array("upgrades","artica_monitor","bandwidthd","hypercache_tail","vsftpd","irqbalance","artica_firewall","artica_fw_hotspot",
    "specialreboot","buildscript","mysqlInit","remove_nested_services","netdiscover",
    "conntrackd","monit","nscd_init_debian","wsgate_init_debian","buildscriptLoopDisk",
    "ifup","ftpproxy","webservices","phppfm","cicap",
    "CleanUbuntu","UpstartJob","debian_mirror","artica_categories","roundcube_http","fetchmail","vde_switch","squid_db","clamav_freshclam","postgres",
    "artica_iso","syncthing","getty","proftpd",
    "not_shutdown","cgconfig","cgredconfig","clamdscan","policyd_weight");

$countDeFunc=count($functions);
$c=0;
foreach ($functions as $func){
    $c++;
    $prc=($c/$countDeFunc)*100;
    $prc=round($prc);
    echo "\n";
    echo "{$prc}%: [INFO] Building $func() init script function\n";
    if(!function_exists($func)){continue;}

    try {
        $results=call_user_func($func);
    }catch (Exception $e) {
        echo "[!!!]: ERROR while running function $func ($e)\n";
    }

}


echo "100%: [INFO] success terminated\n";

function artica_categories(){

    if(is_file("/etc/init.d/categories-db")){
        $unix=new unix();
        $php=$unix->LOCATE_PHP5_BIN();
        $nohup=$unix->find_program("nohup");
        shell_exec("$nohup $php /usr/share/artica-postfix/exec.uninstall.catzdb.php >/dev/null 2>&1 &");
    }

}

function upgrades(){
    $unix=new unix();
    $binpath=$unix->find_program("nmap");
    if(!is_file($binpath)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NMAPInstalled", 0);

    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NMAPInstalled", 1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NMAPPath", $binpath);
        exec("$binpath -V 2>&1",$results);
        foreach ($results as $ligne){
            if(preg_match("#Nmap version\s+([0-9\.]+)#i", $ligne,$re)){
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NMAPVersion", $re[1]);
                break;
            }
        }

    }

    if(!is_file("/usr/lib/python2.7/dist-packages/_ldap.so")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PythonLDAPInstalled", 0);

    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PythonLDAPInstalled", 1);
    }


}


function clamdscan(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    if(is_file("/etc/cron.d/clamscan-daily")){return;}

    $f[]="MAILTO=\"\"";
    $f[]="30 3 * * * root $php /usr/share/artica-postfix/exec.clamscan.php >/dev/null 2>&1";
    $f[]="";
    @file_put_contents("/etc/cron.d/clamscan-daily", @implode("\n", $f));
    @chmod("/etc/cron.d/clamscan-daily",0644);
    shell_exec("/etc/init.d/cron reload");
}

function notify_monit_stop(){
    $unix=new unix();
    $pid=GET_PID();

    if($unix->process_exists($pid)){
        squid_admin_mysql(1,"[OPENLDAP]: Watchdog try to stop OpenLDAP that is running pid $pid",null,__FILE__,__LINE__);
        return;
    }

    squid_admin_mysql(0,"[OPENLDAP]: Watchdog notify that OpenLDAP that is not running",null,__FILE__,__LINE__);

}

function restart_ldap_progress($text,$pourc){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";

    $trace=debug_backtrace();
    $array["TRACE"]=$trace;

    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/openldap.progress", serialize($array));
    @chmod("/usr/share/artica-postfix/ressources/logs/web/openldap.progress",0755);

}

function enable_rest_api(){
    restart_ldap_progress("REST API {enabled}",10);
    $EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
    if($EnableOpenLDAP==0){disable_rest_api();return;}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableOpenLDAPRestFul", 1);
    $OpenLDAPRestFulApi=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPRestFulApi"));
    echo "OpenLDAPRestFulApi = $OpenLDAPRestFulApi\n";

    if(strlen($OpenLDAPRestFulApi)<32){
        $kzy=random_str(32);
        echo "OpenLDAPRestFulApi = $kzy\n";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("OpenLDAPRestFulApi",$kzy);
    }else{
        echo "OpenLDAPRestFulApi = $OpenLDAPRestFulApi\n";
    }
    restart_ldap_progress("REST API {enabled} {success}",100);
}
function disable_rest_api(){
    restart_ldap_progress("REST API {disable}",10);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableOpenLDAPRestFul", 0);
    restart_ldap_progress("REST API {disablee} {success}",100);
}



function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'){
    $pieces = array();
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $pieces []= $keyspace[random_int(0, $max)];
    }
    return implode('', $pieces);
}






function restart_ldap(){
    $unix=new unix();
    $MYPID_FILE="/etc/artica-postfix/pids/restart_ldap.pid";
    $pid=$unix->get_pid_from_file($MYPID_FILE);

    if(!is_file("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableOpenLDAP", 1);
        @chmod("/etc/artica-postfix/settings/Daemons/EnableOpenLDAP",0755);
    }


    $EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
    $mypid=getmypid();
    if($unix->process_exists($pid,basename(__FILE__))){
        echo "slapd: [INFO] Artica task already running pid $pid, my pid is $mypid\n";
        restart_ldap_progress("{failed}  [".__LINE__."]",110);
        exit();
    }



    if($GLOBALS["MONIT"]){
        $unix->ToSyslog("Monit (Watchdog) Ask to restart OpenLDAP service...",false,true);
        squid_admin_mysql(0, "Monit (Watchdog) Ask to restart OpenLDAP service...", null,__FILE__,__LINE__);
    }

    if(!$GLOBALS["FORCE"]){
        $lastexecution=$unix->file_time_min($MYPID_FILE);
        if($lastexecution==0){
            $unix->ToSyslog("Restarting the OpenLDAP by `{$GLOBALS["BY_FRAMEWORK"]}` aborted this command must be executed minimal each 1mn",false,"slapd");
            echo "slapd: [INFO] this command must be executed minimal each 1mn\n";
            exit();
        }
    }
    @unlink($MYPID_FILE);
    restart_ldap_progress("{build_init_script}",5);
    $INITD_PATH=$unix->SLAPD_INITD_PATH();
    echo "Script: $INITD_PATH\n";
    buildscript();
    if(!is_file($INITD_PATH)){
        restart_ldap_progress("{build_init_script} {failed}",110);
        return;
    }

    @file_put_contents($MYPID_FILE, getmypid());
    $unix->ToSyslog("Restarting the OpenLDAP daemon by `{$GLOBALS["BY_FRAMEWORK"]}`",false,basename(__FILE__));
    restart_ldap_progress("{stopping_service}",10);

    stop_ldap(true);
    if($EnableOpenLDAP==1){
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
        restart_ldap_progress("{starting_service}",40);
        start_ldap(true);
    }else{
        restart_ldap_progress("{stopping_service} {success}",100);
    }
}


function ldap_client(){
    $EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
    $f[]="# Saved by Artica on ".date("Y-m-d H:i:s");
    if($EnableOpenLDAP==0){
        $f[]="TLS_REQCERT never";
        $f[]="";
        @file_put_contents("/etc/ldap.conf", @implode("\n", $f));
        @file_put_contents("/etc/ldap/ldap.conf", @implode("\n", $f));
        @file_put_contents("/etc/openldap/ldap.conf", @implode("\n", $f));
        return;
    }
    $ldap=new clladp();
    $server=$ldap->ldap_host;
    $port=$ldap->ldap_port;
    $admin="cn=$ldap->ldap_admin,$ldap->suffix";
    $password=$ldap->ldap_password;
    $ldapi="ldapi://". urlencode("/var/run/slapd/slapd.sock");

    $f[]="host $server";
    $f[]="port $port";
    $f[]="uri $ldapi";
    $f[]="ldap_version 3";
    $f[]="TLS_REQCERT never";
    $f[]="binddn $admin";
    $f[]="rootbinddn $admin";
    $f[]="bindpw $password";
    $f[]="bind_policy soft";
    $f[]="scope sub";
    $f[]="base $ldap->suffix";
    $f[]="pam_password clear";
    $f[]="pam_lookup_policy yes";
    $f[]="pam_filter objectclass=posixAccount";
    $f[]="pam_login_attribute uid";
    $f[]="nss_reconnect_maxconntries 5";
    $f[]="idle_timelimit 3600";
    $f[]="nss_base_group $ldap->suffix?sub";
    $f[]="nss_base_passwd $ldap->suffix?sub";
    $f[]="nss_base_shadow $ldap->suffix?sub";


    $initgroups_ignoreusers=nss_initgroups_ignoreusers();
    if($initgroups_ignoreusers<>null){$f[]="nss_initgroups_ignoreusers $initgroups_ignoreusers";}
    $f[]="";
    $f[]="";


    @file_put_contents("/etc/ldap.conf", @implode("\n", $f));
    @file_put_contents("/etc/ldap/ldap.conf", @implode("\n", $f));
    @file_put_contents("/etc/openldap/ldap.conf", @implode("\n", $f));

    @file_put_contents("/etc/ldap.secret", "$password");

    echo "slapd: [INFO] slapd `/etc/ldap.secret` done\n";
    echo "slapd: [INFO] slapd `/etc/ldap.conf` done\n";

}
function nss_initgroups_ignoreusers(){
    $f=explode("\n",'/etc/passwd');
    $t=array();
    foreach ($f as $ipaddr){
        if(!preg_match("#^(.+?):#", $ipaddr,$re)){continue;}
        $t[]=$re[1];
    }
    if(count($t)>0){
        return @implode(",", $t);
    }
    return null;
}
//##############################################################################



function start_ldap($aspid=false){
    $sock=new sockets();
    $ldaps=array();
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $SLAPD_SERVICESSSL=null;
    $OpenLDAPLogLevelCmdline=null;

    if(!$GLOBALS["FORCE"]){
        $pid=$unix->get_pid_from_file('/etc/artica-postfix/pids/exec.backup.artica.php.restore.pid');
        echo "slapd: [INFO] old Artica pid: $pid\n";
        if($unix->process_exists($pid)){
            $pidtime=$unix->PROCCESS_TIME_MIN($pid);
            if($pidtime<15){
                echo "slapd: [INFO] Artica restore task already running pid $pid since {$pidtime}mn\n";
                restart_ldap_progress("{success}",100);
                return false;
            }
        }
    }


    $MYPID_FILE="/etc/artica-postfix/pids/start_ldap.pid";
    if(!$aspid){
        echo "slapd: [INFO] get_pid_from_file($MYPID_FILE)\n";
        $pid=$unix->get_pid_from_file($MYPID_FILE);
        if($unix->process_exists($pid,basename(__FILE__))){
            $pidtime=$unix->PROCCESS_TIME_MIN($pid);
            $unix->ToSyslog("Artica task already running pid $pid since {$pidtime}mn",false,basename(__FILE__));
            echo "slapd: [INFO] Artica task already running pid $pid since {$pidtime}mn\n";
            if($pidtime>10){
                echo "slapd: [INFO] Killing this Artica task...\n";
                unix_system_kill_force($pid);
            }else{
                exit();
            }
        }


        $MYPID_FILE_TIME=$unix->file_time_min($MYPID_FILE);
        if(!$GLOBALS["FORCE"]){
            if($MYPID_FILE_TIME<1){
                echo "slapd: [INFO] Task must be executed only each 1mn (use --force to by pass)\n";
                exit();
            }
        }

        @unlink($MYPID_FILE);
        @file_put_contents($MYPID_FILE, getmypid());
    }
    $squidbin=$unix->LOCATE_SQUID_BIN();
    if(is_file($squidbin)){
        $SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
        if($SquidPerformance>2){
            echo "slapd: [INFO] Server is set in lower performance, aborting\n";
            return false;
        }

        $EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
        if($EnableIntelCeleron==1){
            echo "slapd: [INFO] Server is set in Celeron support aborting\n";
            restart_ldap_progress("{success}",100);
            return false;
        }
    }
    $EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
    if($EnableOpenLDAP==0){
        echo "slapd: [INFO] Server disabled ( see EnableOpenLDAP )\n";
        restart_ldap_progress("{success}",100);
        return true;
    }

    $slapd=$unix->find_program("slapd");
    $SLAPD_PID_FILE=$unix->SLAPD_PID_PATH();

    $pid=GET_PID();
    if($unix->process_exists($pid)){
        $pidtime=$unix->PROCCESS_TIME_MIN($pid);
        restart_ldap_progress("{success}",100);
        echo "slapd: [INFO] slapd already running pid $pid since {$pidtime}mn\n";
        @file_put_contents($SLAPD_PID_FILE, $pid);
        return true;
    }

    ldap_client();
    echo "slapd: [INFO] slapd loading required values...\n";
    if(!is_file($slapd)){if(is_file('/usr/lib/openldap/slapd')){$slapd='/usr/lib/openldap/slapd';}}
    $OpenLDAPLogLevel=$sock->GET_INFO("OpenLDAPLogLevel");
    $EnableipV6=$sock->GET_INFO("EnableipV6");
    if(!is_numeric($EnableipV6)){$EnableipV6=0;}

    if(!is_dir("/var/lib/ldap")){@mkdir("/var/lib/ldap",0755,true);}
    if(!is_dir("/var/run/slapd")){@mkdir("/var/run/slapd",0755,true);}
    if(!is_numeric($OpenLDAPLogLevel)){$OpenLDAPLogLevel=0;}
    if($OpenLDAPLogLevel<>0){$OpenLDAPLogLevelCmdline=" -d $OpenLDAPLogLevel";}

    $ifconfig=$unix->find_program("ifconfig");
    echo "slapd: [INFO] start looback address...\n";
    shell_exec("$ifconfig lo 127.0.0.1 netmask 255.255.255.0 up >/dev/null 2>&1");



    $ldap[]="ldapi://". urlencode("/var/run/slapd/slapd.sock");
    $ldap[]="ldap://127.0.0.1:389/";

    $LdapListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LdapListenInterface"));
    $OpenLDAPEnableSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPEnableSSL"));
    $OpenLDAPCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenLDAPCertificate"));

    if($LdapListenInterface==null){$LdapListenInterface="lo";}



    if($LdapListenInterface<>"lo"){
        $ipaddr=$unix->InterfaceToIPv4($LdapListenInterface);
        if($ipaddr<>null){
            if($OpenLDAPEnableSSL==1) {
                if ($OpenLDAPCertificate <> null) {
                    $ldaps[] = "ldaps://$ipaddr/";
                }
            }
            $ldap[]="ldap://$ipaddr:389/";
        }
    }


    if(count($ldaps)>0){$SLAPD_SERVICESSSL=" ".@implode(" ", $ldaps);}

    $SLAPD_SERVICES=@implode(" ", $ldap).$SLAPD_SERVICESSSL;
    $DB_RECOVER_BIN=$unix->LOCATE_DB_RECOVER();
    $DB_ARCHIVE_BIN=$unix->LOCATE_DB_ARCHIVE();
    $LDAP_SCHEMA_PATH=$unix->LDAP_SCHEMA_PATH();
    $SLAPD_CONF=$unix->SLAPD_CONF_PATH();
    $php5=$unix->LOCATE_PHP5_BIN();
    $pidofbin=$unix->find_program("pidof");
    $ulimit=$unix->find_program("ulimit");
    $nohup=$unix->find_program("nohup");


    echo "slapd: [INFO] slapd `$slapd`\n";
    echo "slapd: [INFO] db_recover `$DB_RECOVER_BIN`\n";
    echo "slapd: [INFO] db_archive `$DB_ARCHIVE_BIN`\n";
    echo "slapd: [INFO] config `$SLAPD_CONF`\n";
    echo "slapd: [INFO] pid `$SLAPD_PID_FILE`\n";
    echo "slapd: [INFO] services `$SLAPD_SERVICES`\n";
    echo "slapd: [INFO] pidof `$pidofbin`\n";
    if($EnableipV6==0){
        echo "slapd: [INFO] ipv4 only...\n";
        $v4=" -4";
    }



    if($GLOBALS["VERBOSE"]){echo "-> ARRAY;\n";}

    $shemas[]="core.schema";
    $shemas[]="cosine.schema";
    $shemas[]="mod_vhost_ldap.schema";
    $shemas[]="nis.schema";
    $shemas[]="inetorgperson.schema";
    $shemas[]="evolutionperson.schema";
    $shemas[]="postfix.schema";
    $shemas[]="dhcp.schema";
    $shemas[]="samba.schema";
    $shemas[]="ISPEnv.schema";
    $shemas[]="mozilla-thunderbird.schema";
    $shemas[]="officeperson.schema";
    $shemas[]="pureftpd.schema";
    $shemas[]="joomla.schema";
    $shemas[]="autofs.schema";
    $shemas[]="dnsdomain2.schema";
    $shemas[]="zarafa.schema";
    restart_ldap_progress("{starting_service}",50);


    foreach ($shemas as $file){
        if(is_file("/usr/share/artica-postfix/bin/install/$file")){
            if(is_file("$LDAP_SCHEMA_PATH/$file")){@unlink("$LDAP_SCHEMA_PATH/$file");}
            @copy("/usr/share/artica-postfix/bin/install/$file", "$LDAP_SCHEMA_PATH/$file");
            echo "slapd: [INFO] installing `$file` schema\n";
            $unix->chmod_func(0777,"$LDAP_SCHEMA_PATH/$file");
        }
    }



    if(file_exists($ulimit)){
        shell_exec("$ulimit -HSd unlimited");
    }

    restart_ldap_progress("{starting_service}",60);
    if(is_dir("/usr/share/phpldapadmin/config")){
        $phpldapadmin="$php5 /usr/share/artica-postfix/exec.phpldapadmin.php --build >/dev/null 2>&1";
        echo "slapd: [INFO] please wait, configuring PHPLdapAdminservice... \n";
        shell_exec($phpldapadmin);
    }

    echo "slapd: [INFO] please wait, configuring the daemon...\n";

    if($unix->MEM_TOTAL_INSTALLEE()<624288){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SlapdThreads", 2);
    }
    restart_ldap_progress("{starting_service}",70);
    shell_exec("$php5 /usr/share/artica-postfix/exec.slapd.conf.php");
    echo "slapd: [INFO] please wait, building the start script...\n";
    restart_ldap_progress("{starting_service}",71);

    restart_ldap_progress("{starting_service}",72);

    $unix->ToSyslog("Launching the OpenLDAP daemon ",false,basename(__FILE__));
    echo "slapd: [INFO] please wait, Launching the daemon...\n";

    if(!$unix->NETWORK_INTERFACE_OK("lo")){
        $ifconfig=$unix->find_program("ifconfig");
        shell_exec("$ifconfig lo 127.0.0.1 netmask 255.255.255.0 up >/dev/null 2>&1");
    }
    restart_ldap_progress("{starting_service}",73);
    

    restart_ldap_progress("{starting_service}",80);
    $unix->ToSyslog("Starting new instance of OpenLDAP server (Artica)",false,"slapd");

    $cdmline="$slapd$v4 -h \"$SLAPD_SERVICES\" -f $SLAPD_CONF -u root -g root -l local4$OpenLDAPLogLevelCmdline";
    if(is_file("/var/run/slapd/slapd.sock")){@unlink("/var/run/slapd/slapd.sock");}

    $tmpfile=$unix->sh_command($cdmline);
    restart_ldap_progress("{starting_service} $tmpfile",81);
    $unix->go_exec($tmpfile);
    restart_ldap_progress("{starting_service} {please_wait}",82);
    sleep(1);

    for($i=0;$i<5;$i++){
        $pid=GET_PID();
        if($unix->process_exists($pid)){
            echo "slapd: [INFO] slapd success Running pid $pid\n";
            restart_ldap_progress("{success}",100);
            return true;
        }


        echo "slapd: [INFO] please wait, waiting service to start...\n";
        sleep(1);

    }
    restart_ldap_progress("{failed} [".__LINE__."]",110);
    $unix->ToSyslog("Failed to start new instance of OpenLDAP server (Artica)",false,"slapd");
    echo "slapd: [ERR ] Failed to start the service with `$cdmline`\n";
    return false;
}

function xsyslog($text){
    echo $text."\n";
    $unix=new unix();
    $unix->ToSyslog($text. ' (Artica)',false,"slapd");
}


function GET_PID(){
    $unix=new unix();

    $SLAPD_PID_FILE=$unix->SLAPD_PID_PATH();
    $pid=$unix->get_pid_from_file($SLAPD_PID_FILE);
    if($unix->process_exists($pid)){return intval($pid);}
    $slapd=$unix->find_program("slapd");
    return intval($unix->PIDOF($slapd));
}

function stop_ldap($aspid=false){


    if($GLOBALS["MONIT"]){
        xsyslog("Not accept a stop order from MONIT process");
        return false;
    }

    $unix=new unix();
    $slapd=$unix->find_program("slapd");
    $MYPID_FILE="/etc/artica-postfix/pids/stop_ldap.pid";


    if(!$aspid){
        $pid=$unix->get_pid_from_file($MYPID_FILE);
        if($unix->process_exists($pid,basename(__FILE__))){
            $pidtime=$unix->PROCCESS_TIME_MIN($pid);
            echo "slapd: [INFO] Artica task already running pid $pid since {$pidtime}mn\n";
            if($pidtime>10){
                echo "slapd: [INFO] Killing this Artica task...\n";
                unix_system_kill_force($pid);
            }else{exit();}
        }

        @unlink($MYPID_FILE);
        @file_put_contents($MYPID_FILE, getmypid());
    }






    $pid=GET_PID();
    if($unix->process_exists($pid)){
        $timeDaemon=$unix->PROCESS_TTL($pid);
        $unix->ToSyslog("Stopping OpenLDAP service (Artica) running since {$timeDaemon}Mn",false,"slapd");
        echo "slapd: [INFO] slapd shutdown ldap server PID:$pid...\n";
        unix_system_kill($pid);
    }


    for($i=0;$i<10;$i++){
        $pid=GET_PID();
        if($pid==0){break;}
        restart_ldap_progress("{stopping_service} stop PID:$pid",20);
        if($unix->process_exists($pid)){echo "slapd: [INFO] slapd waiting the server to stop PID:$pid...\n";sleep(1);continue;}
        $pid=$unix->PIDOF($slapd);
        if($unix->process_exists($pid)){echo "slapd: [INFO] slapd waiting the server to stop PID:$pid...\n";sleep(1);continue;}

    }

    $pid=GET_PID();
    if($unix->process_exists($pid)){
        echo "slapd: [INFO] slapd PID:$pid still exists, kill it...\n";
        unix_system_kill_force($pid);
    }

    $pid=GET_PID();
    if($unix->process_exists($pid)){
        echo "slapd: [INFO] slapd PID:$pid still exists, start the force kill procedure...\n";
    }

    restart_ldap_progress("{stopping_service} Checking $slapd",25);
    $pid=GET_PID();
    if($unix->process_exists($pid)){
        echo "slapd: [INFO] slapd PID:$pid still exists, kill it...\n";
        unix_system_kill_force($pid);
        sleep(1);
    }

    $pid=GET_PID();
    if($unix->process_exists($pid)) {
        $unix->ToSyslog("Failed to stop OpenLDAP service (Artica)", false, "slapd");
        return false;
    }

    $unix->ToSyslog("Stopping OpenLDAP service with success (Artica) ",false,"slapd");
    restart_ldap_progress("{stopping_service} {success}",30);
    echo "slapd: [INFO] slapd stopped, success...\n";
    return true;
}

function artica_iso(){
    $INITD_PATH="/etc/init.d/artica-iso";
    if(!is_file($INITD_PATH)){return;}
    if(!is_file("/etc/artica-postfix/artica-as-rebooted")){return;}

    echo "artica-iso: [INFO] Removing startup $INITD_PATH script...\n";

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
        @unlink($INITD_PATH);
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." of >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");
        @unlink($INITD_PATH);
    }
}




function not_shutdown(){

    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/notify-start-stop";
    $php5script="exec.notify-start-stop.php";
    $daemonbinLog="Notify Start/Stop Dameon";



    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         ".basename($INITD_PATH);
    $f[]="# Required-Start:    \$local_fs \$syslog";
    $f[]="# Required-Stop:     \$local_fs \$syslog";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: $daemonbinLog";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";

    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="    ;;";
    $f[]="";

    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";


    echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }




}



function vsftpd(){
    if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}
    $INITD_PATH="/etc/init.d/vsftpd";
    if(!is_file($INITD_PATH)){return;}
    system("$INITD_PATH stop");

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");
    }
    @unlink($INITD_PATH);


}

function remove_nested_services(){

    $f[]="/etc/init.d/bind9";
    $f[]="/etc/init.d/exim4";
    $f[]="/etc/init.d/nscd";
    $f[]="/etc/init.d/artica-hotspot";

    foreach ($f as $init){
        if(!is_file($init)){continue;}
        echo "Bad services: [INFO] Remove $init\n";
        shell_exec("$init stop");
        if(is_file('/usr/sbin/update-rc.d')){
            shell_exec("/usr/sbin/update-rc.d -f " .basename($init)." remove >/dev/null 2>&1");
            @unlink($init);
        }

        if(is_file('/sbin/chkconfig')){
            shell_exec("/sbin/chkconfig --del " .basename($init)." >/dev/null 2>&1");
            @unlink($init);
        }

    }

}




function roundcube_http(){}

function fetchmail(){
    if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}
    $unix=new unix();
    $fetchmail=$unix->find_program("fetchmail");
    if(!is_file($fetchmail)){return;}
    $php=$unix->LOCATE_PHP5_BIN();
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          php5-fcgi";
    $f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$time";
    $f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog ";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: PHP5 CGI Daemon";
    $f[]="# chkconfig: 2345 11 89";
    $f[]="# description: PHP5 CGI Daemon";
    $f[]="### END INIT INFO";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="   $php /usr/share/artica-postfix/exec.fetchmail.php --start \$2 \$3";
    $f[]="	 exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="   $php /usr/share/artica-postfix/exec.fetchmail.php --stop \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="   $php /usr/share/artica-postfix/exec.fetchmail.php --restart \$2 \$3";
    $f[]="	 exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reload)";
    $f[]="   $php /usr/share/artica-postfix/exec.fetchmail.php --reload \$2 \$3";
    $f[]="	 exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";

    $INITD_PATH="/etc/init.d/fetchmail";
    echo "fetchmail: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }
}





function netdiscover(){
    if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/netdiscover";
    if(!is_file($INITD_PATH)){return;}

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." off >/dev/null 2>&1");
    }

    @unlink($INITD_PATH);
}

function conntrackd(){
    return null;
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/conntrackd";
    $daemonbinLog=basename($INITD_PATH);
    $php5script="exec.conntrackd.php";
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         conntrackd";
    $f[]="# Required-Start:    \$local_fs \$syslog \$network";
    $f[]="# Required-Stop:     \$local_fs \$syslog \$network";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: Connection Tracker Daemon";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";


    echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }
}
function ifup(){
    $INITD_PATH="/etc/init.d/artica-ifup";
    if(is_file($INITD_PATH)){return;}
    $f[]="#!/bin/sh -e";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          artica-ifup";
    $f[]="# Required-Start:    \$local_fs";
    $f[]="# Required-Stop:     \$local_fs";
    $f[]="# Should-Start:      ";
    $f[]="# Should-Stop:       ";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: start and stop the network";
    $f[]="# Description:       Artica ifup service";
    $f[]="### END INIT INFO";
    $f[]="export LC_ALL=C";
    $f[]="";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]=" ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|}\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0";
    $f[]="";

    echo "artica-ifup: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }
}



function debian_mirror(){
    return null;
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $daemonbin=$unix->find_program("rsync");
    $daemonbinLog=basename($daemonbin);
    $INITD_PATH="/etc/init.d/debian-artmirror";
    $php5script="exec.debian.mirror.php";
    if(!is_file($daemonbin)){return;}


    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         debian-artmirror";
    $f[]="# Required-Start:    \$local_fs \$syslog \$network";
    $f[]="# Required-Stop:     \$local_fs \$syslog \$network";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: Artica Debian Mirror builder";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";


    echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }

    return null;
}


function ftpproxy(){
    $INITD_PATH="/etc/init.d/ftp-proxy";

    if(is_file($INITD_PATH)){

        if(is_file('/usr/sbin/update-rc.d')){
            shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
        }

        if(is_file('/sbin/chkconfig')){
            shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");

        }


        @unlink($INITD_PATH);

    }
    return null;

}

function webservices(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/artica-webservices";
    $daemonbinLog="Web services";


    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         artica-webservices";
    $f[]="# Required-Start:    \$local_fs \$syslog \$network";
    $f[]="# Required-Stop:     \$local_fs \$syslog \$network";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: $daemonbinLog";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="    $php /usr/share/artica-postfix/exec.php-fpm.php --start --script \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/exec.lighttpd.php --fpm-start --script \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/exec.squidguard-http.php --start --script \$2 \$3 || true";
    $f[]="    /etc/init.d/artica-status reload --script \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/exec.php-fpm.php --stop --script \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/exec.lighttpd.php --fpm-stop --script \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/exec.squidguard-http.php --stop --script \$2 \$3 || true";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";

    $f[]="    $php /usr/share/artica-postfix/exec.php-fpm.php --restart --script \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/exec.php5-fcgi.php --restart --script \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/exec.lighttpd.php --restart --script \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/exec.squidguard-http.php --restart --script \$2 \$3 || true";
    $f[]="    /etc/init.d/artica-status reload --script \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";


    echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }



}






function phppfm_fix(){
    $unix=new unix();
    $pidF="/etc/artica-postfix/pids/".__FUNCTION__.".pid";
    $pid=$unix->get_pid_from_file($pidF);
    if($unix->process_exists($pid,basename(__FILE__))){return;}
    @file_put_contents($pidF, getmypid());
    phppfm();
    shell_exec("/etc/init.d/php5-fpm start");
    $nohup=$unix->find_program("nohup");
    shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");

}
function phppfm_restartback(){

    if(!isPhpFpmPatched()){
        InitSlapdToSyslog("phppfm_restartback():: /etc/init.d/php5-fpm not patched..");
        phppfm();
        $unix=new unix();
        $nohup=$unix->find_program("nohup");
        InitSlapdToSyslog("phppfm_restartback():: Restarting PHP5-FPM");
        shell_exec("/etc/init.d/php5-fpm restart");

    }
}
function isPhpFpmPatched(){
    $f=explode("\n",@file_get_contents("/etc/init.d/php5-fpm"));
    foreach ( $f as $index=>$line ){
        if(preg_match("#exec\.php-fpm\.php#", $line)){return true;}

    }
    return false;
}



function InitSlapdToSyslog($text){

    $LOG_SEV=LOG_INFO;
    if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
    if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
    if(function_exists("closelog")){closelog();}
}

function LIGHTTPD_INITD(){
    $f[]="/etc/init.d/lighttpd";
    $f[]="/usr/local/etc/rc.d/lighttpd";
    $f[]="/etc/rc.d/lighttpd";
    foreach ($f as $pid=>$line){
        if(is_file($line)){return $line;}
    }
}
//##############################################################################
function lighttpd(){
    $LIGHTTPD_INITD=LIGHTTPD_INITD();
    if(!is_file($LIGHTTPD_INITD)){return;}
    $INITD_PATH=$LIGHTTPD_INITD;
    $daemonbinLog="Disabled service";
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         lighttpd";
    $f[]="# Required-Start:    \$local_fs \$syslog";
    $f[]="# Required-Stop:     \$local_fs \$syslog";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: $daemonbinLog";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="   exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";

    echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));

    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
        shell_exec("/usr/sbin/update-rc.d -f ".basename($INITD_PATH)." remove");

    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }




}


function phppfm(){

    if(is_file("/etc/artica-postfix/FROM_ISO")){if(!is_file("/etc/artica-postfix/artica-iso-setup-launched")){exit();}}
    return;
    $unix=new unix();
    if(is_file("/etc/artica-postfix/FROM_ISO")){
        $daemon_path="/usr/sbin/php5-fpm";
        $php=$GLOBALS["PHP5_BIN_PATH"];
    }else{
        $php=$unix->LOCATE_PHP5_BIN();
        $daemon_path=$unix->APACHE_LOCATE_PHP_FPM();
    }


    $INITD_PATH="/etc/init.d/php5-fpm";
    $php5script="exec.php-fpm.php";
    $daemonbinLog="PHP5 FastCGI Process Manager Daemon";


    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         php5-fpm";
    $f[]="# Required-Start:    \$local_fs \$syslog";
    $f[]="# Required-Stop:     \$local_fs \$syslog";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: $daemonbinLog";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start --script \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop --script \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --restart --script \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";


    echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }


}





















function hypercache_tail(){remove_service("/etc/init.d/hypercache-tail");}





function monit(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/monit";
    $php5script="exec.monit.php";
    $daemonbinLog="Monitor Daemon";
    $monitbin=$unix->find_program("monit");


    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         artica-monit";
    $f[]="# Required-Start:    \$local_fs \$syslog";
    $f[]="# Required-Stop:     \$local_fs \$syslog";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: $daemonbinLog";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";

    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
    $f[]="    ;;";
    $f[]=" reload)";
    $f[]="    $monitbin -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reconfigure)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
    $f[]="    $monitbin -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart|reload|reconfigure} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";


    echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }


}

















function CleanUbuntu(){
    $unix=new unix();
    if(is_file("/etc/default/whoopsie")){
        echo "Ubuntu: [INFO] Disabling whoopsie\n";
        @file_put_contents("/etc/default/whoopsie","[General]\nreport_crashes=false\n");
        shell_exec("/usr/bin/killall whoopsie");
        shell_exec("/etc/init.d/whoopsie stop");
        if(is_file('/usr/sbin/update-rc.d')){
            shell_exec("/usr/sbin/update-rc.d -f whoopsie remove >/dev/null 2>&1");
        }
    }
    if(is_file("/usr/sbin/console-kit-daemon")){
        echo "Ubuntu: [INFO] Disabling console-kit-daemon\n";
        shell_exec("/bin/mv /usr/sbin/console-kit-daemon /usr/sbin/console-kit-daemon.bkup");
        shell_exec("/bin/cp /bin/true /usr/sbin/console-kit-daemon");

    }

    if(is_file("/usr/sbin/bluetoothd")){
        echo "Ubuntu: [INFO] Disabling bluetoothd\n";
        shell_exec("/usr/bin/killall bluetoothd");
        shell_exec("/etc/init.d/bluetooth stop");
        if(is_file('/usr/sbin/update-rc.d')){
            shell_exec("/usr/sbin/update-rc.d -f bluetooth remove >/dev/null 2>&1");
        }

    }

    if(is_file("/etc/default/avahi-daemon")){
        echo "Ubuntu: [INFO] Disabling avahi dameon\n";
        if($unix->LINUX_CODE_NAME()=="UBUNTU"){
            @file_put_contents("/etc/default/avahi-daemon","AVAHI_DAEMON_START = 0\nAVAHI_DAEMON_DETECT_LOCAL=1\n");
        }
        if(is_file("/etc/init.d/avahi-daemon")){
            shell_exec("/etc/init.d/avahi-daemon stop");
            if(is_file('/usr/sbin/update-rc.d')){
                shell_exec("/usr/sbin/update-rc.d -f avahi-daemon remove >/dev/null 2>&1");
                shell_exec("kill -9 `pidof avahi-daemon` >/dev/null 2>&1");
            }
        }
    }

}



function mailarchive_perl(){
    if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}

    $INITD_PATH="/etc/init.d/mailarchive-perl";
    if(!is_file($INITD_PATH)){return;}

    echo "mailarchive-perl: [INFO] DELETE $INITD_PATH\n";


    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");
    }

    @unlink($INITD_PATH);

}





function vde_switch(){
    return;
    $unix=new unix();
    $Masterbin=$unix->find_program("vde_pcapplug");
    if(!is_file($Masterbin)){return;}
    $php=$unix->LOCATE_PHP5_BIN();
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          vde-switch";
    $f[]="# Required-Start:    \$all";
    $f[]="# Required-Stop:     \$local_fs";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: vde-switch";
    $f[]="# chkconfig: 2345 11 89";
    $f[]="# description: vde-switch";
    $f[]="### END INIT INFO";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="    $php /usr/share/artica-postfix/exec.vde.php --start \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/exec.vde.php --stop \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/exec.vde.php --restart \$2 \$3";
    $f[]="    ;;";
    $f[]=" reconfigure)";
    $f[]="    $php /usr/share/artica-postfix/exec.vde.php --reconfigure \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";

    $INITD_PATH="/etc/init.d/vde_switch";
    echo "mailarchive-perl: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);@file_put_contents($INITD_PATH, @implode("\n", $f));

    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }

}




function buildscriptLoopDisk(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();


    $phpscr=dirname(__FILE__)."/exec.loopdisks.php";
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          Artica-loopdisk";
    $f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$network \$time";
    $f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$network";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: Calls spamassassin to allow filtering out";
    $f[]="# chkconfig: 2345 11 89";
    $f[]="# description: reconfigure loop disks after reboot";
    $f[]="### END INIT INFO";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="    $php $phpscr \$2 \$3";
    $f[]="	  /etc/init.d/autofs reload";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";

    $INITD_PATH="/etc/init.d/artica-loopd";
    echo "artica-oopd: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);@file_put_contents($INITD_PATH, @implode("\n", $f));

    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }
}

function specialreboot(){
    if(!is_dir("/etc/rc6.d")){return;}
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          StopWatchdog";
    $f[]="# Required-Start:    \$local_fs";
    $f[]="# Required-Stop:     \$local_fs";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: Stop Artica Watchdogs";
    $f[]="# chkconfig: 56 11 89";
    $f[]="# description: Stop Artica Watchdogs";
    $f[]="### END INIT INFO";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="	 exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="	 echo \"Stopping all Artica watchdogs...\"";
    $f[]="   /etc/init.d/monit stop";
    $f[]="   /etc/init.d/artica-status stop";
    $f[]="	 echo \"Stopping all Artica watchdogs done\"";
    $f[]="	 exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="	 exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart} {ldap|} (+ 'debug' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";

    if(is_file("/etc/rc6.d/K00StopWatchdog")){@unlink("/etc/rc6.d/K00StopWatchdog");}
    $INITD_PATH="/etc/init.d/StopWatchdog";
    @file_put_contents("/etc/init.d/StopWatchdog", @implode("\n", $f));
    @chmod("/etc/init.d/StopWatchdog",0755);
    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults 1 >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }

}








function nscd_init_debian(){
    $unix=new unix();
    $sock=new sockets();
    $servicebin=$unix->find_program("update-rc.d");
    $users=new usersMenus();
    if(!is_file("/etc/init.d/nscd")){return;}
    if(!is_file($servicebin)){return;}
    $php=$unix->LOCATE_PHP5_BIN();
    if(!is_file($servicebin)){return;}
    $EnableNSCD=$sock->GET_INFO("EnableNSCD");
    if(!is_numeric($EnableNSCD)){$EnableNSCD=0;}
    $nscdbin=$unix->find_program("nscd");
    echo "nscd: [INFO] ncsd enabled = `$EnableNSCD`\n";
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          nscd";
    $f[]="# Required-Start:    \$remote_fs \$syslog";
    $f[]="# Required-Stop:     \$remote_fs \$syslog";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: Starts the Name Service Cache Daemon";
    $f[]="### END INIT INFO";
    $f[]="";
    $f[]="#";
    $f[]="# nscd:		Starts the Name Service Cache Daemon";
    $f[]="#";
    $f[]="# description:  This is a daemon which handles passwd and group lookups";
    $f[]="#		for running programs and caches the results for the next";
    $f[]="#		query.  You should start this daemon only if you use";
    $f[]="#		slow Services like NIS or NIS+";
    $f[]="";
    $f[]="PATH=\"/sbin:/usr/sbin:/bin:/usr/bin\"";
    $f[]="NAME=\"nscd\"";
    $f[]="DESC=\"Name Service Cache Daemon\"";
    $f[]="DAEMON=\"$nscdbin\"";
    $f[]="PIDFILE=\"/var/run/nscd/nscd.pid\"";
    $f[]="";
    $f[]="# Sanity checks.";
    $f[]="umask 022";
    $f[]="[ -f /etc/nscd.conf ] || exit 0";
    $f[]="[ -x \"\$DAEMON\" ] || exit 0";
    $f[]="[ -d /var/run/nscd ] || mkdir -p /var/run/nscd";
    $f[]=". /lib/lsb/init-functions";
    $f[]="";
    $f[]="start_nscd()";
    $f[]="{";
    $f[]="ENABLED=$EnableNSCD";
    $f[]="	if [ \$ENABLED -eq 0 ]";
    $f[]="	then";
    $f[]="		return 1";
    $f[]="	fi";
    $f[]="	log_daemon_msg \"Starting \$DESC\" \"\$NAME\"";
    $f[]="	# Return";
    $f[]="	#   0 if daemon has been started or was already running";
    $f[]="	#   2 if daemon could not be started";
    $f[]="	start-stop-daemon --start --quiet --pidfile \"\$PIDFILE\" --exec \"\$DAEMON\" --test > /dev/null || return 0";
    $f[]="	start-stop-daemon --start --quiet --pidfile \"\$PIDFILE\" --exec \"\$DAEMON\" || return 2";
    $f[]="}";
    $f[]="";
    $f[]="stop_nscd()";
    $f[]="{";

    $f[]="	# Return";
    $f[]="	#   0 if daemon has been stopped";
    $f[]="	#   1 if daemon was already stopped";
    $f[]="	#   2 if daemon could not be stopped";
    $f[]="";
    $f[]="	# we try to stop using nscd --shutdown, that fails also if nscd is not present.";
    $f[]="	# in that case, fallback to \"good old methods\"";
    $f[]="	RETVAL=0";
    $f[]="	if ! \$DAEMON --shutdown; then";
    $f[]="		start-stop-daemon --stop --quiet --pidfile \"\$PIDFILE\" --name \"\$NAME\" --test > /dev/null";
    $f[]="		RETVAL=\"\$?\"";
    $f[]="		[ \"\$?\" -ne 0  -a  \"\$?\" -ne 1 ] && return 2";
    $f[]="	fi";
    $f[]="";
    $f[]="	# Wait for children to finish too";
    $f[]="	start-stop-daemon --stop --quiet --oknodo --retry=0/30/KILL/5 --exec \"\$DAEMON\" > /dev/null";
    $f[]="	[ \"\$?\" -ne 0  -a  \"\$?\" -ne 1 ] && return 2";
    $f[]="	rm -f \"\$PIDFILE\"";
    $f[]="	return \"\$RETVAL\"";
    $f[]="}";
    $f[]="";
    $f[]="status()";
    $f[]="{";
    $f[]="	# Return";
    $f[]="	#   0 if daemon is stopped";
    $f[]="	#   1 if daemon is running";
    $f[]="	start-stop-daemon --start --quiet --pidfile \"\$PIDFILE\" --exec \"\$DAEMON\" --test > /dev/null || return 1";
    $f[]="	return 0";
    $f[]="}";
    $f[]="";
    $f[]="case \"\$1\" in";
    $f[]="start)";
    $f[]="	start_nscd";
    $f[]="	case \"\$?\" in";
    $f[]="		0) log_end_msg 0 ; exit 0 ;;";
    $f[]="		1) log_warning_msg \" (already running).\" ; exit 0 ;;";
    $f[]="		*) log_end_msg 1 ; exit 1 ;;";
    $f[]="	esac";
    $f[]="	;;";
    $f[]="stop)";
    $f[]="	log_daemon_msg \"Stopping \$DESC\" \"\$NAME\"";
    $f[]="	stop_nscd";
    $f[]="	case \"\$?\" in";
    $f[]="		0) log_end_msg 0 ; exit 0 ;;";
    $f[]="		1) log_warning_msg \" (not running).\" ; exit 0 ;;";
    $f[]="		*) log_end_msg 1 ; exit 1 ;;";
    $f[]="	esac";
    $f[]="	;;";
    $f[]="restart|force-reload)";
    $f[]="	log_daemon_msg \"Restarting \$DESC\" \"\$NAME\"";
    $f[]="	for table in passwd group hosts ; do";
    $f[]="		\$DAEMON --invalidate \$table";
    $f[]="	done";
    $f[]="	stop_nscd";
    $f[]="	case \"\$?\" in";
    $f[]="	0|1)";
    $f[]="		start_nscd";
    $f[]="		case \"\$?\" in";
    $f[]="			0) log_end_msg 0 ; exit 0 ;;";
    $f[]="			1) log_failure_msg \" (failed -- old process is still running).\" ; exit 1 ;;";
    $f[]="			*) log_failure_msg \" (failed to start).\" ; exit 1 ;;";
    $f[]="		esac";
    $f[]="		;;";
    $f[]="	*)";
    $f[]="		log_failure_msg \" (failed to stop).\"";
    $f[]="		exit 1";
    $f[]="		;;";
    $f[]="	esac";
    $f[]="	;;";
    $f[]="status)";
    $f[]="	log_daemon_msg \"Status of \$DESC service: \"";
    $f[]="	status";
    $f[]="	case \"\$?\" in";
    $f[]="		0) log_failure_msg \"not running.\" ; exit 3 ;;";
    $f[]="		1) log_success_msg \"running.\" ; exit 0 ;;";
    $f[]="	esac";
    $f[]="	;;";
    $f[]="*)";
    $f[]="	echo \"Usage: /etc/init.d/\$NAME {start|stop|force-reload|restart|status}\" >&2";
    $f[]="	exit 1";
    $f[]="	;;";
    $f[]="esac";
    @unlink("/etc/init.d/nscd");
    @file_put_contents("/etc/init.d/nscd", @implode("\n", $f));
    @chmod("/etc/init.d/nscd",0755);
    echo "nscd: [INFO] nscd path `/etc/init.d/nscd` done\n";
}








function LOCATE_SQUID_BIN(){
    $unix=new unix();
    if(isset($GLOBALS["UNIX_LOCATE_SQUID_BIN"])){return $GLOBALS["UNIX_LOCATE_SQUID_BIN"];}
    $GLOBALS["UNIX_LOCATE_SQUID_BIN"]=$unix->find_program("squid3");
    if(!is_file($GLOBALS["UNIX_LOCATE_SQUID_BIN"])){$GLOBALS["UNIX_LOCATE_SQUID_BIN"]=$unix->find_program("squid");}
    return $GLOBALS["UNIX_LOCATE_SQUID_BIN"];

}




function squid_db()
{
}







function policyd_weight(){
    if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}
    return;
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $f[]="#! /bin/sh";
    $f[]="#";
    $f[]="# policyd-weight	start/stop the policyd-weight deamon for postfix";
    $f[]="#               	(priority should be smaller than that of postfix)";
    $f[]="#";
    $f[]="# Author:		(c) 2012 Werner Detter <werner@aloah-from-hell.de>";
    $f[]="#";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides: policyd-weight";
    $f[]="# Required-Start: \$local_fs \$network \$remote_fs \$syslog";
    $f[]="# Required-Stop: \$local_fs \$network \$remote_fs \$syslog";
    $f[]="# Default-Start:  2 3 4 5";
    $f[]="# Default-Stop: 0 1 6";
    $f[]="# Short-Description: start and stop the policyd-weight daemon";
    $f[]="# Description: Perl policy daemon for the Postfix MTA";
    $f[]="### END INIT INFO";
    $f[]="";
    $f[]="set -e";
    $f[]="";
    $f[]="PATH=/sbin:/bin:/usr/sbin:/usr/bin";
    $f[]="DAEMON=/usr/share/artica-postfix/bin/policyd-weight";
    $f[]="NAME=policyd-weight";
    $f[]="DESC=\"policyd-weight\"";
    $f[]="";
    $f[]="PIDFILE=/var/run/\$NAME.pid";
    $f[]="SCRIPTNAME=/etc/init.d/\$NAME";
    $f[]="DAEMON_OPTS=\"-f /etc/policyd-weight.conf\"";
    $f[]="";
    $f[]="# Gracefully exit if the package has been removed.";
    $f[]="test -x \$DAEMON || exit 0";
    $f[]="";
    $f[]=". /lib/init/vars.sh";
    $f[]=". /lib/lsb/init-functions";
    $f[]="ret=0";
    $f[]="";
    $f[]="case \"\$1\" in";
    $f[]="  start)";

    $f[]="if [ ! -f \"/etc/artica-postfix/settings/Daemons/EnablePolicydWeight\" ]; then";
    $f[]="\tlog_daemon_msg \"Starting \$DESC (Not enabled!)\" \"\$NAME\"";
    $f[]="\texit 0";
    $f[]="fi";

    $f[]="EnablePolicydWeight=`cat /etc/artica-postfix/settings/Daemons/EnablePolicydWeight`";

    $f[]="if [ \$EnablePolicydWeight -eq 0 ]; then";
    $f[]="\tlog_daemon_msg \"Starting \$DESC (Not enabled!)\" \"\$NAME\"";
    $f[]="\tlog_daemon_msg \"DONE.....\" \"\$NAME\"";
    $f[]="\texit 0";
    $f[]="fi";



    $f[]="\tlog_daemon_msg \"Starting \$DESC (EnablePolicydWeight=\$EnablePolicydWeight)\" \"\$NAME\"";
    $f[]="if [ ! -f \"/etc/artica-postfix/settings/Daemons/PolicydWeightConfig\" ]; then";
    $f[]="\t$php /usr/share/artica-postfix/exec.postfix.maincf.php --policyd-reconfigure";
    $f[]="fi";
    $f[]="	/bin/rm -rf /var/run/policyd-weight >/dev/null 2>&1 || true";
    $f[]="	/bin/rm -rf /tmp/.policyd-weight >/dev/null 2>&1 || true";
    $f[]="	mkdir -p /var/run/policyd-weight >/dev/null 2>&1 || true";
    $f[]="	mkdir -p /tmp/.policyd-weight >/dev/null 2>&1 || true";
    $f[]="	/bin/chown postfix:postfix /var/run/policyd-weight >/dev/null 2>&1 || true";
    $f[]="	/bin/chmod 770 /var/run/policyd-weight >/dev/null 2>&1 || true";
    $f[]="	/bin/chown postfix:postfix /tmp/.policyd-weight >/dev/null 2>&1 || true";
    $f[]="	/bin/cp -f /etc/artica-postfix/settings/Daemons/PolicydWeightConfig /etc/policyd-weight.conf || true";
    $f[]="        if start-stop-daemon --start --oknodo --quiet --pidfile \$PIDFILE --name \$NAME --exec \$DAEMON start -- \$DAEMON_OPTS";
    $f[]="        then";
    $f[]="            log_end_msg 0";
    $f[]="        else";
    $f[]="            ret=\$?";
    $f[]="            log_end_msg 1";
    $f[]="        fi";
    $f[]="        ;;";
    $f[]="  stop)";
    $f[]="	log_daemon_msg \"Stopping \$DESC (incl. cache)\" \"\$NAME\"";
    $f[]="	if \$DAEMON -k && start-stop-daemon --stop --quiet --oknodo --pidfile \$PIDFILE && rm -f \$PIDFILE";
    $f[]="	then";
    $f[]="		log_end_msg 0";
    $f[]="	else";
    $f[]="		ret=\$?";
    $f[]="		log_end_msg 1";
    $f[]="	fi";
    $f[]="	;;";
    $f[]="  dstop)";
    $f[]="	log_daemon_msg \"Stopping \$DESC (without cache)\" \"\$NAME\"";
    $f[]="	if start-stop-daemon --stop --quiet --oknodo --pidfile \$PIDFILE && rm -f \$PIDFILE";
    $f[]="	then";
    $f[]="		log_end_msg 0";
    $f[]="	else	";
    $f[]="		ret=\$?";
    $f[]="		log_end_msg 1";
    $f[]="	fi";
    $f[]="	;;";
    $f[]="  reload|force-reload)";
    $f[]="		  /bin/cp -f /etc/artica-postfix/settings/Daemons/PolicydWeightConfig /etc/policyd-weight.conf || true";
    $f[]="        log_daemon_msg \"Reloading \$DESC configuration files\" \"\$NAME\"";
    $f[]="        if \$DAEMON \$DAEMON_OPTS reload > /dev/null 2>&1";
    $f[]="        then";
    $f[]="                log_end_msg 0";
    $f[]="        else";
    $f[]="                log_end_msg 1";
    $f[]="                ret=\$?";
    $f[]="        fi";
    $f[]="        ;;";
    $f[]="restart)";
    $f[]="	log_daemon_msg \"Restarting \$DESC configuration (incl. cache)\" \"\$NAME\"";
    $f[]="	mkdir -p /var/run/policyd-weight >/dev/null 2>&1 || true";
    $f[]="	mkdir -p /tmp/.policyd-weight >/dev/null 2>&1 || true";
    $f[]="	/bin/rm -rf /var/run/policyd-weight/* >/dev/null 2>&1 || true";
    $f[]="	/bin/rm -rf /tmp/.policyd-weight/* >/dev/null 2>&1 || true";
    $f[]="	/bin/chown postfix:postfix /var/run/policyd-weight >/dev/null 2>&1 || true";
    $f[]="	/bin/chmod 770 /var/run/policyd-weight >/dev/null 2>&1 || true";
    $f[]="	/bin/chown postfix:postfix /tmp/.policyd-weight >/dev/null 2>&1 || true";
    $f[]="	/bin/cp -f /etc/artica-postfix/settings/Daemons/PolicydWeightConfig /etc/policyd-weight.conf || true";
    $f[]="	if \$DAEMON -k && start-stop-daemon --stop --quiet --oknodo --pidfile \$PIDFILE && rm -f \$PIDFILE && start-stop-daemon --start --oknodo --quiet --pidfile \$PIDFILE --name \$NAME --exec \$DAEMON start -- \$DAEMON_OPTS";
    $f[]="	then	";
    $f[]="		log_end_msg 0";
    $f[]="        else";
    $f[]="        	ret=\$?";
    $f[]="        	log_end_msg 1";
    $f[]="        fi";
    $f[]="	;;";
    $f[]="	drestart)";
    $f[]="        log_daemon_msg \"Restarting \$DESC configuration (without cache)\" \"\$NAME\"";
    $f[]="        if \$DAEMON \$DAEMON_OPTS restart > /dev/null 2>&1";
    $f[]="        then";
    $f[]="                log_end_msg 0";
    $f[]="        else";
    $f[]="                ret=\$?";
    $f[]="                log_end_msg 1";
    $f[]="        fi";
    $f[]="        ;;";
    $f[]=" status)";
    $f[]="	;;";
    $f[]="  *)";
    $f[]="	N=/etc/init.d/\$NAME";
    $f[]="	echo \"Usage: \$N {start|stop|dstop|reload|force-reload|restart|drestart}\" >&2";
    $f[]="	exit 1";
    $f[]="	;;";
    $f[]="esac";
    $f[]="";
    $f[]="exit \$ret";


    @file_put_contents("/etc/init.d/policyd-weight", @implode("\n", $f));
    @chmod("/etc/init.d/policyd-weight",0755);





    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec('/usr/sbin/update-rc.d -f policyd-weight >/dev/null 2>&1');

    }

    if(is_file('/sbin/chkconfig')){
        shell_exec('/sbin/chkconfig --add policyd-weight >/dev/null 2>&1');
        shell_exec('/sbin/chkconfig --level 2345 policyd-weight on >/dev/null 2>&1');
    }
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Policyd-weight success...\n";}


}








function getty(){
    $f=array();
    $unix=new unix();
    $chattr=$unix->find_program("chattr");
    $main=explode("\n",@file_get_contents("/etc/inittab"));
    foreach ($main as $line){
        if(preg_match("#\/(logon\.sh|artica-logon)#",$line)){
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already done\n";}
            system("$chattr +i /etc/inittab");
            return;
        }
    }
    $main=array();
    if(!is_file("/etc/inittab.bak")){@copy("/etc/inittab","/etc/inittab.bak");}
    system("$chattr -i /etc/inittab");

    $f[]="# /etc/inittab: init(8) configuration.";
    $f[]="# Override by Artica on ". date("Y-m-d H:i:s");
    $f[]="# \$Id: inittab,v 1.91 2002/01/25 13:35:21 miquels Exp \$";
    $f[]="";
    $f[]="id:2:initdefault:";
    $f[]="si::sysinit:/etc/init.d/rcS";
    $f[]="~~:S:wait:/sbin/sulogin --force";
    $f[]="";
    $f[]="";
    $f[]="l0:0:wait:/etc/init.d/rc 0";
    $f[]="l1:1:wait:/etc/init.d/rc 1";
    $f[]="l2:2:wait:/etc/init.d/rc 2";
    $f[]="l3:3:wait:/etc/init.d/rc 3";
    $f[]="l4:4:wait:/etc/init.d/rc 4";
    $f[]="l5:5:wait:/etc/init.d/rc 5";
    $f[]="l6:6:wait:/etc/init.d/rc 6";
    $f[]="z6:6:respawn:/sbin/sulogin --force";
    $f[]="ca:12345:ctrlaltdel:/sbin/shutdown -t1 -a -r now";
    $f[]="pf::powerwait:/etc/init.d/powerfail start";
    $f[]="pn::powerfailnow:/etc/init.d/powerfail now";
    $f[]="po::powerokwait:/etc/init.d/powerfail stop";
    $f[]="";
    $f[]="1:2345:respawn:/sbin/getty -i -n -l /usr/share/artica-postfix/logon.sh 38400 tty1";
    $f[]="2:23:respawn:/sbin/getty 38400 tty2";
    $f[]="3:23:respawn:/sbin/getty 38400 tty3";
    $f[]="4:23:respawn:/sbin/getty 38400 tty4";
    $f[]="5:23:respawn:/sbin/getty 38400 tty5";
    $f[]="6:23:respawn:/sbin/getty 38400 tty6";
    $f[]="";

    @file_put_contents("/etc/inittab", @implode("\n", $f)."\n");

    $main=explode("\n",@file_get_contents("/etc/inittab"));
    foreach ($main as $line){
        if(preg_match("#\/(logon\.sh|artica-logon)#",$line)){
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: inittab success\n";}
            system("$chattr +i /etc/inittab");
            return;
        }
    }

}

function bandwidthd(){
    return;
    if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/bandwidthd";
    $php5script="exec.bandwithd.php";
    $daemonbinLog="Bandwidthd Daemon";



    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         bandwidthd";
    $f[]="# Required-Start:    \$local_fs \$syslog";
    $f[]="# Required-Stop:     \$local_fs \$syslog";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: $daemonbinLog";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";

    $f[]="";
    $f[]=". /lib/lsb/init-functions";
    $f[]="";




    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reconfigure)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reload)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";


    echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }

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

