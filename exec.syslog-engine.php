<?php
$GLOBALS["LEGALLOGS"]=false;
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
if(preg_match("#--legal-logs#",implode(" ",$argv),$re)){$GLOBALS["LEGALLOGS"]=true;}

if(function_exists("posix_getuid")){
    if(posix_getuid()<>0){
        die("Cannot be used in web server mode\n\n");
    }
}

$BASEDIR="/usr/share/artica-postfix";
include_once($BASEDIR . '/ressources/class.users.menus.inc');
include_once($BASEDIR. '/ressources/class.iptables-chains.inc');
include_once($BASEDIR . '/ressources/class.mysql.haproxy.builder.php');
include_once($BASEDIR . "/ressources/class.mysql.squid.builder.php");
include_once($BASEDIR. "/ressources/class.mysql.builder.inc");
include_once($BASEDIR . "/ressources/class.mysql.syslogs.inc");

if(preg_match("#--norestart#",implode(" ",$argv))){$GLOBALS["NORESTART"]=true;}
if(preg_match("#--syslogmini#",implode(" ",$argv))){$GLOBALS["SYSLOGMINI"]=true;}
if(preg_match("#--restart#",implode(" ",$argv))){$GLOBALS["RESTART"]=true;}

if(isset($argv[1])){
    if($argv[1]=="--start"){service_start();exit;}
    if($argv[1]=="--stop"){service_stop();exit;}
    if($argv[1]=="--restart-service"){service_restart();exit;}
    if($argv[1]=="--task"){rsyslog_tasks();exit;}
    if($argv[1]=="--enable-ssl"){enable_ssl();exit;}
    if($argv[1]=="--disable-ssl"){disable_ssl();exit;}
    if($argv[1]=="--client-certificate"){syslog_client_certificate();exit;}
    if($argv[1]=='--client-template'){client_template_ssl();exit;}
    if($argv[1]=='--log-sink-params'){logsink_params();exit;}


}

$unix=new unix();
$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." instances:".count($pids)."\n";}
if(count($pids)>3){
	echo "Starting......: ".date("H:i:s")." Too many instances ". count($pids)." starting squid, kill them!\n";
	$mypid=getmypid();
	foreach ($pids as $pid=>$ligne){
		if($pid==$mypid){continue;}
		echo "Starting......: ".date("H:i:s")." killing $pid\n";
		unix_system_kill_force($pid);
	}

}

$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
if(count($pids)>3){
	echo "Starting......: ".date("H:i:s")." Too many instances ". count($pids)." dying\n";
	exit();
}

if($GLOBALS["VERBOSE"]){echo __LINE__." TRUE\n";}


if(is_file("/etc/artica-postfix/FROM_ISO")){
	if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){
		if($GLOBALS["VERBOSE"]){echo __LINE__." FALSE\n";}
		return;
	}
}
if(isset($argv[1])){
    if($argv[1]=='--stats'){$GLOBALS["VERBOSE"]=true;die();}
    if($argv[1]=='--seeker'){$GLOBALS["VERBOSE"]=true;die();}
    if($argv[1]=='--updtev'){udfbguard_update_events();exit();}
    if($argv[1]=='--rsylogd'){rsyslog_check_includes();exit();}
    if($argv[1]=='--sysev'){sysev();exit();}
    if($argv[1]=='--admin-evs'){scan_queue();exit();}
    if($argv[1]=='--loadavg'){exit();}
    if($argv[1]=='--buildconf'){rsyslog_check_includes();exit;}
    if($argv[1]=='--restart'){rsyslog_reconfigure();exit;}
    if($argv[1]=='--reconfigure'){rsyslog_reconfigure();exit;}
    if($argv[1]=='--enable-ssl'){enable_ssl();exit;}
    if($argv[1]=='--disable-ssl'){disable_ssl();exit;}
    if($argv[1]=='--restart-syslog'){restart_syslog();exit();}
    if($argv[1]=='--build-server'){build_server_mode();exit();}
    if($argv[1]=='--build-client'){build_client_mode();exit();}
    if($argv[1]=='--load-stats'){load_stats();exit();}
    if($argv[1]=='--install'){install();exit();}
    if($argv[1]=='--uninstall'){uninstall();exit();}


    if($argv[1]=='--auth-logs'){
        $GLOBALS["YESCGROUP"]=true;
        xcgroups();

        if(system_is_overloaded(__FILE__)){
            return true;
        }

        $TimeFile="/etc/artica-postfix/pids/exec.syslog-engine.auth.time";
        $unix=new unix();
        $TimExec=$unix->file_time_min($TimeFile);
        if($TimExec<5){exit();}
        if(is_file($TimeFile)){@unlink($TimeFile);}
        @file_put_contents($TimeFile, time());
        $functions=array("scan_queue");

        foreach ($functions as $function){
            if(!function_exists($function)){continue;}
            try {
                call_user_func($function);
                if(system_is_overloaded(__FILE__)){
                    $ps_report="";
                    if( function_exists("ps_report")){
                        $ps_report=ps_report();
                    }
                    squid_admin_mysql(1,"{OVERLOADED_SYSTEM} after calling $function()",
                        $ps_report,__FILE__,__LINE__);
                    exit(0);
                }
            } catch (Exception $e) {
                squid_admin_mysql(0,"Error calling function $function()",
                    $e->getMessage(),null,__FILE__,__LINE__);
            }

        }
    }
    if($argv[1]=='--authfw'){exit();}
    if($argv[1]=='--sessions'){system_admin_events();exit();}
    if($argv[1]=='--scan'){scan_queue(true);exit();}
    if($argv[1]=='--;'){scan_queue(true);exit();}
    if($argv[1]=='--squid-tasks'){exit();}
    if($argv[1]=='--squidsys'){exit();}
    if($argv[1]=='--localx'){build_localx_servers();exit();}
    if($argv[1]=='--all-daemons'){build_all_daemons();exit;}
    }


if(!$GLOBALS["FORCE"]){if(system_is_overloaded(basename(__FILE__))){exit();}}
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
$unix=new unix();
$pid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($pid,basename(__FILE__))){
	ssh_events("Already PID $pid exists, aborting" , "MAIN", __FILE__, __LINE__);
	exit();
}

if(!$GLOBALS["VERBOSE"]){
	$time=$unix->file_time_min($timefile);
	if($time<5){exit();}
}

@file_put_contents($pidfile, getmypid());
@unlink($timefile);
@file_put_contents($timefile, time());
if(system_is_overloaded(basename(__FILE__))){exit();}


if(system_is_overloaded(basename(__FILE__))){
    squid_admin_mysql(2, "{OVERLOADED_SYSTEM}: {$GLOBALS["SYSTEM_INTERNAL_LOAD"]}, aborting",ps_report(),__FILE__,__LINE__);exit();
}

exit();


function enable_ssl_progress($prc,$text):bool{
    $unix=new unix();
    echo "$text\n";
    $unix->framework_progress($prc,$text,"syslog.ssl.progress");
    return true;
}
function disable_ssl():bool{
    enable_ssl_progress(50,"{reconfigure}...");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RsyslogTCPUseSSL",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RsyslogCertificates",base64_encode(serialize(array())));
    enable_ssl_progress(70,"{reconfigure}...");
    rsyslog_check_includes();
    enable_ssl_progress(100,"{restarting} {done}...");
    shell_exec("/etc/init.d/rsyslog restart");
    return true;
}


function rsyslog_certificate_request($autority=false):string{
    $unix=new unix();
    $hostname=$unix->hostname_g();
    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
    if(!isset($LicenseInfos["COMPANY"])){$LicenseInfos["COMPANY"]=null;}
    if(!isset($LicenseInfos["EMAIL"])){$LicenseInfos["EMAIL"]=null;}
    if(!isset($WizardSavedSettings["company_name"])){$WizardSavedSettings["company_name"]=null;}
    if(!isset($WizardSavedSettings["mail"])){$WizardSavedSettings["mail"]=null;}

    if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
    if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
    if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]="Log Sink";}
    if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]="root@localhost.localdomain";}
    $f[]="organization = \"{$LicenseInfos["COMPANY"]}\"";
    $f[]="unit = \"sleeping dept.\"";
    $f[]="locality =";
    $f[]="state = \"Attiki\"";
    $f[]="country = US";
    $f[]="cn = \"$hostname\"";
    $f[]="uid = \"$hostname\"";
    $tb=explode(".",$hostname);
    foreach ($tb as $line){
        $f[]="dc = \"$line\"";
    }
    echo "Using $hostname; {$LicenseInfos["EMAIL"]}; {$LicenseInfos["COMPANY"]};\n";

    $f[]="expiration_days = 5475";
    $f[]="dns_name = \"$hostname\"";
    $f[]="email = \"{$LicenseInfos["EMAIL"]}\"";
    $f[]="# Challenge password used in certificate requests";
    $f[]="# challenge_password = 123456";
    $f[]="# Password when encrypting a private key";
    $f[]="#password = secret";
    $f[]="# An URL that has CRLs (certificate revocation lists)";
    $f[]="# available. Needed in CA certificates.";
    $f[]="#crl_dist_points = \"https://www.getcrl.crl/getcrl/\"";
    $f[]="serial = 007";

    if($autority) {
        $f[]="ca";
    }
    $f[]="#signing_key";
    $f[]="#cert_signing_key";
    $f[]="#encryption_key";
    $f[]="#crl_signing_key";
    $f[]="#key_agreement";
    $f[]="#data_encipherment";
    $f[]="#non_repudiation";
    $f[]="";
    return @implode("\n",$f);
}


function client_template_ssl():bool{
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $TEMPDIR=$unix->TEMP_DIR()."/syslog-ssl";
    $RsyslogCertificatesConfig=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogCertificatesConfig");
    $RsyslogCertificatesPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogCertificatesPassword");
    @mkdir($TEMPDIR,0755,true);
    $final_config_crypted="$TEMPDIR/data.conf.crypt";
    $final_config_decrypted="$TEMPDIR/data.conf";
    $rmcdline="$rm -rf $TEMPDIR";
    echo "Client Template...\n";
    enable_ssl_progress(20,"Building configuration...");
    @file_put_contents($final_config_crypted,base64_decode($RsyslogCertificatesConfig));
    exec("/usr/share/artica-postfix/bin/artica-rotate -decrypt-file \"$final_config_crypted\" -passphrase \"$RsyslogCertificatesPassword\" 2>&1",$results);

    if(!is_file($final_config_decrypted)){
        echo "$final_config_decrypted no such file\n";
        enable_ssl_progress(110,"Building configuration {failed}");
        foreach ($results as $line){
            echo "Results: $line\n";
        }
        shell_exec($rmcdline);
        return false;
    }

    $data=base64_decode(@file_get_contents($final_config_decrypted));
    $FINAL=unserialize($data);

    $server_hostname=$FINAL["server_hostname"];
    $RsyslogTCPPort=$FINAL["server_port"];
    $main_ca_data=base64_encode($FINAL["CA"]);
    $ca_key=base64_encode($FINAL["KEY"]);
    $ca_cert=base64_encode($FINAL["CERT"]);
    $client_hostname=$FINAL["client_hostname"];

    $q          = new lib_sqlite("/home/artica/SQLITE/syslogrules.db");



    $fields[]="server";
    $fields[]="port";
    $fields[]="ssl";
    $fields[]="proto";
    $fields[]="certificate";
    $fields[]="public_key";
    $fields[]="ca_key";
    if($client_hostname<>null){
        $fields[]="myhostname";
    }
    $fields[]="logtype";



    $values[]="'$server_hostname'";
    $values[]="'$RsyslogTCPPort'";
    $values[]="1";
    $values[]="'tcp'";
    $values[]="'$ca_cert'";
    $values[]="'$ca_key'";
    $values[]="'$main_ca_data'";
    if($client_hostname<>null){
        $values[]="'$client_hostname'";
    }
    $values[]="'all'";
    enable_ssl_progress(50,"Building configuration...");
    $q->QUERY_SQL("INSERT INTO rules (".@implode(",",$fields).") VALUES (".@implode(",",$values).")");
    if(!$q->ok){
        echo $q->mysql_error."\n";
        enable_ssl_progress(110,"SQL Failed");
        shell_exec($rmcdline);
        return false;
    }


    enable_ssl_progress(100,"Building configuration {success}");
    return true;
}

function syslog_client_certificate():bool{
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $CONFIG=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LOGSINKWIZARD"));
    $hostname=$CONFIG["client_hostname"];
    enable_ssl_progress(20,"Building certificate for $hostname");
    $TEMPDIR=$unix->TEMP_DIR()."/syslog-ssl";
    @mkdir($TEMPDIR,0755,true);
    $certtool=$unix->find_program("certtool");
    $ca_key="$TEMPDIR/ca-key.pem";
    $main_ca="$TEMPDIR/ca.pem";
    $ca_req="$TEMPDIR/ca-request.pem";
    $ca_cfg="$TEMPDIR/cert.cfg";
    $ca_cert="$TEMPDIR/certificate.pem";
    $rmcdline="$rm -rf $TEMPDIR";

    $password=$CONFIG["client_password"];
    $server_hostname=$CONFIG["server_hostname"];
    $final_config="$TEMPDIR/$hostname.config";
    $RsyslogCertificates=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogCertificates")));



    $main_ca_data=$RsyslogCertificates["CA"];
    echo $main_ca_data;
    if(strlen($main_ca_data)<50){
        enable_ssl_progress(110,"CA no data!");
        shell_exec($rmcdline);
        return false;
    }
    enable_ssl_progress(30,"Building certificate for $hostname");
    @file_put_contents($main_ca,$main_ca_data);

    exec("$certtool --generate-privkey --outfile \"$ca_key\" --bits 2048 2>&1",$results);
    if(!is_file($ca_key)){
        echo "$ca_key no such file\n";
        enable_ssl_progress(110,"Building certificate for $hostname {failed}");
        foreach ($results as $line){
            echo "Results: $line\n";
        }
        shell_exec($rmcdline);
        return false;
    }

    enable_ssl_progress(30,"Generate certificate requests for $hostname");
    @file_put_contents($ca_cfg,rsyslog_certificate_request());

    $results=array();
    exec("$certtool --generate-request --load-privkey $ca_key --template \"$ca_cfg\" --outfile $ca_req  2>&1",$results);

    if(!is_file($ca_req)){
        echo "$ca_req no such file\n";
        enable_ssl_progress(110,"Requests for $hostname {failed}");
        foreach ($results as $line){
            echo "Results: $line\n";
        }
        shell_exec($rmcdline);
        return false;
    }
    enable_ssl_progress(50,"Generate certificate for $hostname");

    exec("$certtool --generate-certificate --load-request $ca_req --outfile $ca_cert --load-ca-certificate $main_ca --template \"$ca_cfg\" --load-ca-privkey $ca_key 2>&1",$results);

    if(!is_file($ca_cert)){
        echo "$ca_cert no such file\n";
        enable_ssl_progress(110,"Generate certificate for $hostname {failed}");
        foreach ($results as $line){
            echo "Results: $line\n";
        }
        shell_exec($rmcdline);
        return false;
    }

    $RsyslogTCPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RsyslogTCPPort"));
    $FINAL["server_hostname"]=$server_hostname;
    $FINAL["server_port"]=$RsyslogTCPPort;
    $FINAL["CA"]=$main_ca_data;
    $FINAL["KEY"]=@file_get_contents($ca_key);
    $FINAL["CERT"]=@file_get_contents($ca_cert);
    $FINAL["client_hostname"]=$hostname;



    $FINAL_DATA=base64_encode(serialize($FINAL));
    @file_put_contents($final_config,$FINAL_DATA);
    $password=$unix->shellEscapeChars($password);
    echo "Crypting $final_config\n";
    $final_config_crypted="$final_config.crypt";
    exec("/usr/share/artica-postfix/bin/artica-rotate -crypt-file \"$final_config\" -passphrase \"$password\" 2>&1",$results);
    echo "$final_config_crypted no such file";
    if(!is_file($final_config_crypted)){
        echo "$final_config Crypt failed\n";
        enable_ssl_progress(110,"Crypt {failed}");
        foreach ($results as $line){
            echo "Results: $line\n";
        }
        shell_exec($rmcdline);
        return false;

    }

    $CONFIG["FINAL_CONF"]=base64_encode(@file_get_contents($final_config_crypted));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LOGSINKWIZARD",serialize($CONFIG));
    shell_exec($rmcdline);
    enable_ssl_progress(100,"Generate certificate for $hostname {success}");
    return true;

}

function enable_ssl():bool{
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $hostname=$unix->hostname_g();
    enable_ssl_progress(20,"Building certificate for $hostname");
    $TEMPDIR=$unix->TEMP_DIR()."/syslog-ssl";
    @mkdir($TEMPDIR,0755,true);
    $certtool=$unix->find_program("certtool");
    $ca_key="$TEMPDIR/ca-key.pem";
    $main_ca="$TEMPDIR/ca.pem";
    $ca_req="$TEMPDIR/ca-request.pem";
    $ca_cfg="$TEMPDIR/cert.cfg";
    $ca_cert="$TEMPDIR/certificate.pem";
    $rmcdline="$rm -rf $TEMPDIR";

    exec("$certtool --generate-privkey --outfile \"$ca_key\" --bits 2048 2>&1",$results);
    if(!is_file($ca_key)){
        echo "$ca_key no such file\n";
        enable_ssl_progress(110,"Building certificate for $hostname {failed}");
        foreach ($results as $line){
            echo "Results: $line\n";
        }
        shell_exec($rmcdline);
        return false;
    }

    enable_ssl_progress(30,"Generate certificate requests for $hostname");
    @file_put_contents($ca_cfg,rsyslog_certificate_request(true));

    $results=array();
    exec("$certtool --generate-self-signed  --template \"$ca_cfg\"  --load-privkey $ca_key --outfile $main_ca  2>&1",$results);

    if(!is_file($main_ca)){
        echo "$main_ca no such file\n";
        enable_ssl_progress(110,"CA for $hostname {failed}");
        foreach ($results as $line){
            echo "Results: $line\n";
        }
        shell_exec($rmcdline);
        return false;
    }



    @file_put_contents($ca_cfg,rsyslog_certificate_request());
    exec("$certtool --generate-request --template \"$ca_cfg\" --load-privkey \"$ca_key\" --outfile \"$ca_req\" 2>&1",$results);

    if(!is_file($ca_req)){
        echo "$ca_key no such file\n";
        enable_ssl_progress(110,"certificate requests for $hostname {failed}");
        foreach ($results as $line){
            echo "Results: $line\n";
        }

        shell_exec($rmcdline);
        return false;
    }

    enable_ssl_progress(50,"Generate certificates for $hostname");
    $results=array();
    $cmdline="$certtool --generate-certificate --load-request \"$ca_req\" --outfile \"$ca_cert\" --load-ca-certificate \"$main_ca\" --template \"$ca_cfg\" --load-ca-privkey \"$ca_key\" 2>&1";
    echo "$cmdline\n";
    exec($cmdline,$results);

    if(!is_file($ca_cert)){
        echo "$ca_cert no such file\n";
        enable_ssl_progress(110,"certificate for $hostname {failed}");
        foreach ($results as $line){
            echo "Results: $line\n";
        }

        shell_exec($rmcdline);
        return false;
    }

    enable_ssl_progress(60,"Saving Certificates...");

    $RsyslogCertificates["CA"]=@file_get_contents($main_ca);
    $RsyslogCertificates["PRIVKEY"]=@file_get_contents($ca_key);
    $RsyslogCertificates["CERT"]=@file_get_contents($ca_cert);

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RsyslogCertificates",base64_encode(serialize($RsyslogCertificates)));

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RsyslogTCPUseSSL",1);

    enable_ssl_progress(70,"{reconfigure}...");
    rsyslog_check_includes();
    enable_ssl_progress(100,"{restarting} {done}...");
    shell_exec($rmcdline);
    shell_exec("/etc/init.d/rsyslog restart");



return true;

}



function build_all_daemons_progress($prc=0,$text=null):bool{
    $unix=new unix();
    echo "($prc%) $text\n";
    $unix->framework_progress($prc,$text,"syslog.daemons.progress");
    return true;
}

function crc_config():string{
    $crc[]=crc32_file("/etc/rsyslog.conf");
    $workdir="/etc/rsyslog.d";
    $dir_handle = scandir($workdir, SCANDIR_SORT_DESCENDING);
    foreach ($dir_handle as $file){
        if ($file == '.') {continue;}
        if ($file == '..') {continue;}
        if (!is_file("$workdir/$file")) {continue;}
        $crc[] = crc32_file("$workdir/$file");
    }
    return md5(@implode("",$crc));

}

function build_all_daemons():bool{
    $unix=new unix();
    $crc32=crc_config();
    $cmds[]="exec.squid.disable.php --syslog";
    $cmds[]="exec.c-icap.install.php --syslog-sandbox";
    $cmds[]="exec.keepalived.php --syslog";
    $cmds[]="exec.apt-mirror.php --syslog";
    $cmds[]="exec.suricata.php --syslog";
    $cmds[]="exec.unbound.php --syslog";
    $cmds[]="exec.nginx.php --syslog";
    $cmds[]="exec.rustdesk.php --syslog";
    $cmds[]="exec.trackadmin.php --syslog";
    $cmds[]="exec.postgres.php --syslog";
    $cmds[]="exec.postfix.maincf.php --syslog";
    $cmds[]="exec.apt-mirror.php --syslog";
    $cmds[]="exec.artica-milter.php --syslog";
    $cmds[]="exec.nginx-letsencrypt.php --syslog";
    $cmds[]="exec.compile.categories.php --syslog";
    $cmds[]="exec.c-icap.php --syslog";
    $cmds[]="exec.go.shield.server.php --syslog";
    $cmds[]="exec.hotspot-service.php --syslog";
    $cmds[]="exec.hotspot-service.php --syslog";
    $cmds[]="exec.init-tail-cache.php --syslog";



    $EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));
    if($EnablePostfixMultiInstance==1){
        $cmds[]="exec.postfix-multi.php --all-syslog";
    }

    $tot=count($cmds);
    build_all_daemons_progress(10,"rsyslog_check_includes");
    rsyslog_check_includes();

    $i=10;
    $php=$unix->LOCATE_PHP5_BIN();
    foreach ($cmds as $cmd){
        $i++;
        $prc=$i/$tot;
        $prc=round($prc*100);
        if($prc>90){$prc=90;}
        build_all_daemons_progress($prc,"$cmd");
        shell_exec("$php ".ARTICA_ROOT."/$cmd");
    }
    $crc322=crc_config();
    if($crc32==$crc322){
        build_all_daemons_progress(100,"{success}");
        return true;
    }

    build_all_daemons_progress(92,"{checking_configuration}");
    if(!CheckConfig()){
        build_all_daemons_progress(92,"{revert_config}");
        RestoreConfig();
        build_all_daemons_progress(110,"{checking_configuration} {failed}");
        return false;
    }

    build_all_daemons_progress(96,"{stopping}");
    service_stop();
    build_all_daemons_progress(98,"{starting}");
    if(!service_start(true)){
        build_all_daemons_progress(110,"{starting} {failed}");
        return false;
    }
    BackupConfig();
    build_all_daemons_progress(100,"{starting} {success}");
    return true;
}
function CheckConfig():bool{

    echo "Verify configurations...\n";
    $unix=new unix();
    $rsyslod=$unix->find_program("rsyslogd");
    exec("$rsyslod -N1 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if(preg_match("#rsyslogd:\s+error\s+#",$line)){
            echo "Fatal error $line\n";
            if(preg_match("#error during parsing file\s+(.+?),#",$line,$re)){
                if(is_file($re[1])){
                    $c=0;
                    $f=explode("\n",@file_get_contents($re[1]));
                    foreach ($f as $xline){
                        $c++;
                        echo "$c: $xline\n";
                    }
                }
            }
            return false;
        }
    }
    return true;
}
function RestoreConfig():bool{
    $unix=new unix();
    $tar=$unix->find_program("tar");

    $destdir="/home/artica";
    $destFile="$destdir/rsyslogd.backup.tar.gz";
    if(!is_file($destFile)){
        echo "$destFile no such file\n";
        return false;}
    shell_exec("$tar xf $destFile -C /");
    return true;

}
function BackupConfig():bool{
    $unix=new unix();
    $cp=$unix->find_program("cp");
    $cd=$unix->find_program("cd");
    $rm=$unix->find_program("rm");
    $tar=$unix->find_program("tar");
    $destdir="/home/artica";
    $destFile="$destdir/rsyslogd.backup.tar.gz";
    if(!is_dir($destdir)){@mkdir($destdir,0755,true);}

    $tempdir=$unix->TEMP_DIR()."/syslog_backup";
    if(is_dir($tempdir)){ shell_exec("$rm -rf $tempdir");}
    @mkdir("$tempdir/etc");
    @mkdir("$tempdir/etc/rsyslog.d");
    if(!is_dir($tempdir)){return false;}
    @copy("/etc/rsyslog.conf","$tempdir/etc/rsyslog.conf");
    shell_exec("$cp -f /etc/rsyslog.d/* $tempdir/etc/rsyslog.d/");
    shell_exec("$cd $tempdir");
    if(!is_file($destFile)){@unlink($destFile);}
    @chdir($tempdir);
    shell_exec("$tar czf $destFile *");
    chdir("/root");
    shell_exec("$rm -rf $tempdir");
    return true;
}
function sysev():bool{

	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
        writelogs("Already running pid $pid",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }

	scan_queue();
    return true;
}

function build_progress($pourc,$text):bool{
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"syslog.install.progress");
    return true;
}
function build_progress_restart($pourc,$text):bool{
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"syslog.restart.progress");
    return true;
}
function install():bool{
	$unix=new unix();
	build_progress(10,"{installing}");
	$sock=new sockets();
	$sock->SET_INFO("ActAsASyslogServer",1);
	if($GLOBALS["LEGALLOGS"]){
		if($unix->CORP_LICENSE()) {
			$sock->SET_INFO("LegallogServer", 1);
		}
	}


	build_progress(50,"{configuring}");
	rsyslog_check_includes();
	build_progress(90,"{restarting}");
	restart_syslog();
	build_progress(100,"{installing} {success}");
    return true;

}
function uninstall():bool{
	build_progress(10,"{uninstalling}");
	$sock=new sockets();
	$sock->SET_INFO("ActAsASyslogServer",0);
	$sock->SET_INFO("LegallogServer",0);
	build_progress(50,"{configuring}");
	rsyslog_check_includes();
	build_progress(90,"{restarting}");
	restart_syslog();
	build_progress(100,"{uninstalling} {success}");
    return true;
}

function build_server_mode():bool{
	echo "Starting......: ".date("H:i:s")." syslog rsyslog mode\n";
	rsyslog_check_includes();
    return true;
}
function rsyslog_tasks():bool{
    $unix=new unix();
    $unix->Popuplate_cron_make("syslog-task","*/2 * * * *",basename(__FILE__)." --stats");
    return true;
}
function rsyslog_reconfigure():bool{
	rsyslog_check_includes();
	$unix=new unix();$unix->RESTART_SYSLOG(true);
    return true;
}

function rsyslog_check_includes():bool{
    system("/usr/sbin/artica-phpfpm-service -reconfigure-syslog");
    return true;
}
function _out_syslog($text):bool{
    if(!function_exists("openlog")){return false;}
    openlog("syslog", LOG_PID , LOG_SYSLOG);
    $text="[Artica]: $text";
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}
function build_client_mode():bool{
	$sock=new sockets();
	$ActAsASyslogClient=$sock->GET_INFO("ActAsASyslogClient");
	$ActAsASyslogSMTPClient=$sock->GET_INFO("ActAsASyslogSMTPClient");
	if(!is_numeric($ActAsASyslogSMTPClient)){$ActAsASyslogSMTPClient=0;}
	if(!is_numeric($ActAsASyslogClient)){$ActAsASyslogClient=0;}
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
	if($EnableRemoteSyslogStatsAppliance==1){$ActAsASyslogClient=1;}

	if(($ActAsASyslogClient==0) OR ($ActAsASyslogSMTPClient==0)){
		echo "Starting......: ".date("H:i:s")." syslog client parameters not defined, aborting tasks\n";
	}

	if(is_file("/etc/default/syslogd")){
		echo "Starting......: ".date("H:i:s")." syslog client old syslog mode\n";
		build_client_mode_debian();
		shell_exec("/etc/init.d/auth-tail restart");
		return true;
	}

	if(is_dir("/etc/rsyslog.d")){
		echo "Starting......: ".date("H:i:s")." syslog client rsyslog mode\n";
		build_client_mode_ubuntu();

	}
    return true;
}
function build_client_mode_ubuntu():bool{

	$sock=new sockets();
	$ActAsASyslogClient=$sock->GET_INFO("ActAsASyslogClient");
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	$ActAsASyslogSMTPClient=$sock->GET_INFO("ActAsASyslogSMTPClient");
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
	if(!is_numeric($ActAsASyslogClient)){$ActAsASyslogClient=0;}
	if(!is_numeric($ActAsASyslogSMTPClient)){$ActAsASyslogSMTPClient=0;}
	if($EnableRemoteSyslogStatsAppliance==1){$ActAsASyslogClient=1;}
    $s=array();


	$serversList=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActAsASyslogClientServersList")));
	@unlink("/etc/rsyslog.d/artica-client.conf");
	$g[]="";

	if($EnableRemoteSyslogStatsAppliance==1){
		$RemoteStatisticsApplianceSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteStatisticsApplianceSettings")));
		if(isset($RemoteStatisticsApplianceSettings["SERVER"])){
			$s[]="authpriv.info\t@{$RemoteStatisticsApplianceSettings["SERVER"]}";
		}

		$RemoteSyslogAppliance=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteSyslogAppliance")));
		if(isset($RemoteSyslogAppliance["SERVER"])){
			$s[]="authpriv.info\t@{$RemoteSyslogAppliance["SERVER"]}";
		}
	}

	if($ActAsASyslogClient==1){
		if(count($serversList)>0){
            foreach ($serversList as $num=>$server){
				if($server==null){continue;}
				if(preg_match("#(.+?):([0-9]+)#",$server,$re)){$server=$re[1];}
				echo "Starting......: ".date("H:i:s")." $num) syslog client $server (forced to 514 port)\n";
				$s[]="*.*\t@$server";
			}
		}
	}

	if($ActAsASyslogSMTPClient==1){
		$serversList=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActAsASyslogClientSMTPList")));
		if(count($serversList)>0){
            foreach ($serversList as $num=>$server){
				if($server==null){continue;}
				if(preg_match("#(.+?):([0-9]+)#",$server,$re)){$server=$re[1];}
				echo "Starting......: ".date("H:i:s")." $num) syslog mail.* client $server (forced to 514 port)\n";
				$s[]="mail.*\t@$server";
			}
		}
	}


	$g[]="";

	if(is_array($s)){
		$final=@implode("\n",$s)."\n".@implode("\n",$g);
	}else{
		$final=@implode("\n",$g);
	}

	@file_put_contents("/etc/rsyslog.d/artica-client.conf",$final);
	echo "Starting......: ".date("H:i:s")." syslog client /etc/rsyslog.d/artica-client.conf done\n";

	@unlink("/etc/rsyslog.d/artica-authpriv.conf");
	if(!ParseDirAuthpriv("/etc/rsyslog.d")){
		@file_put_contents("/etc/rsyslog.d/artica-authpriv.conf","authpriv.*			/var/log/auth.log");
	}

	restart_syslog();
    return true;
}
function ParseDirAuthpriv($dirname):bool{
	foreach (glob("$dirname/*") as $filename) {
		if(CheckAuthAuthpriv($filename)){return true;}
	}
	return false;

}
function CheckAuthAuthpriv($filename):bool{
	$f=explode("\n", @file_get_contents($filename));
    foreach ($f as $line){
		if(preg_match("#authpriv#", $line)){echo "Starting......: ".date("H:i:s")." syslog client $filename has Authpriv\n";return true;}
	}
	return false;
}
function build_localx_servers():bool{

	for($i=0;$i<8;$i++){
		@unlink("/etc/rsyslog.d/artica-server-local$i.conf");

	}


	$datas=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyslogLocals")));
    foreach ($datas as $local=>$path){
		if(!preg_match("#\.[a-z]+$#", $path)){
			$path=$path."/$local.log";
		}
		@file_put_contents("/etc/rsyslog.d/artica-server-$local.conf", "$local.*\t-$path\n");
	}
	rsyslog_check_includes();
	restart_syslog();
    return true;
}
function build_client_mode_debian(){

	$sock=new sockets();
	$ActAsASyslogServer=$sock->GET_INFO("ActAsASyslogServer");
	$ActAsASyslogClient=$sock->GET_INFO("ActAsASyslogClient");
	$ActAsASyslogSMTPClient=$sock->GET_INFO("ActAsASyslogSMTPClient");

	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
	if(!is_numeric($ActAsASyslogClient)){$ActAsASyslogClient=0;}
	if(!is_numeric($ActAsASyslogSMTPClient)){$ActAsASyslogSMTPClient=0;}
	if($EnableRemoteSyslogStatsAppliance==1){$ActAsASyslogClient=1;}


	$serversList=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActAsASyslogClientServersList")));
	$f=explode("\n",@file_get_contents("/etc/syslog.conf"));
    foreach ($f as $num=>$line){
		if(preg_match("#\.+?\.\.+?\s+@#",$line,$re)){
			$f[$num]=null;
			echo "Starting......: ".date("H:i:s")." syslog client removing $line\n";
		}
	}

	reset($f);
    foreach ($f as $num=>$line){
		if(trim($line)==null){continue;}
		$g[]=$line;
	}
	$g[]="";

	if($EnableRemoteSyslogStatsAppliance==1){
		$RemoteStatisticsApplianceSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteStatisticsApplianceSettings")));
		if(isset($RemoteStatisticsApplianceSettings["SERVER"])){
			$s[]="authpriv.info\t@{$RemoteStatisticsApplianceSettings["SERVER"]}";
		}

		$RemoteSyslogAppliance=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteSyslogAppliance")));
		if(isset($RemoteSyslogAppliance["SERVER"])){
			$s[]="authpriv.info\t@{$RemoteSyslogAppliance["SERVER"]}";
		}
	}

	if($ActAsASyslogClient==1){
		if(count($serversList)>0){
            foreach ($serversList as $num=>$server){
				if($server==null){continue;}
				if(preg_match("#(.+?):([0-9]+)#",$server,$re)){$server=$re[1];}
				echo "Starting......: ".date("H:i:s")." syslog client ($num):$server (forced to 514 port)\n";
				$s[]="*.*\t@$server";
			}
		}
	}

	if($ActAsASyslogSMTPClient==1){
		$serversList=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActAsASyslogClientSMTPList")));
		if(count($serversList)>0){
            foreach ($serversList as $num=>$server){
				if($server==null){continue;}
				if(preg_match("#(.+?):([0-9]+)#",$server,$re)){$server=$re[1];}
				echo "Starting......: ".date("H:i:s")." syslog mail.* client $server (forced to 514 port)\n";
				$s[]="mail.*\t@$server";
			}
		}
	}




	$g[]="";

	if(is_array($s)){
		$final=@implode("\n",$s)."\n".@implode("\n",$g);
	}else{
		$final=@implode("\n",$g);
	}

	@file_put_contents("/etc/syslog.conf",$final);
	echo "Starting......: ".date("H:i:s")." syslog client /etc/syslog.conf done\n";
	restart_syslog();


}

function rsyslog_pid():int{
	$GLOBALS["CLASS_UNIX"]=new unix();
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/rsyslogd.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$rsyslogd=$GLOBALS["CLASS_UNIX"]->find_program("rsyslogd");
	return $GLOBALS["CLASS_UNIX"]->PIDOF($rsyslogd);

}
function _out($text):bool{
    echo date("H:i:s")." [INIT]: Syslog Daemon: $text\n";
    return true;
}
function service_restart($aspid=false):bool{
    return true;
}
function service_stop($aspid=false){
    $unix=new unix();
    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            _out("service Already Artica task running PID $pid since {$time}mn");
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=rsyslog_pid();

    if(!$unix->process_exists($pid)){
        _out("Service already stopped...");
        return true;
    }
    $pid=rsyslog_pid();
    _out("Shutdown pid $pid...");
    unix_system_kill($pid);
    for($i=0;$i<5;$i++){
        $pid=rsyslog_pid();
        if(!$unix->process_exists($pid)){break;}
        _out("Waiting to stop pid:$pid $i/5...");
        sleep(1);
    }

    $pid=rsyslog_pid();
    if(!$unix->process_exists($pid)){
        _out("Stopping success...");
        return true;
    }

    _out("Shutdown - force - pid $pid...");
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=rsyslog_pid();
        if(!$unix->process_exists($pid)){break;}
        _out("Waiting to stop pid:$pid $i/5...");
        sleep(1);
    }

    if(!$unix->process_exists($pid)){
        _out("Service success to be stopped");
        return true;
    }

    _out("Stopping service failed...");
    return false;

}

function service_start($aspid=false):bool{
    $unix=new unix();
    $Masterbin="/usr/sbin/rsyslogd";
    if(!is_file($Masterbin)){
        _out("$Masterbin not installed");
        return false;
    }

    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            _out("Already Artica task running PID $pid since {$time}mn");
            return true;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=rsyslog_pid();

    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        _out("Service already started $pid since {$timepid}Mn...");
        return true;
    }
    $LogSyncMoveDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSyncMoveDir"));
    $EnableSyslogLogSink=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSyslogLogSink"));
    if($EnableSyslogLogSink==1){
        if($LogSyncMoveDir<>null){
            echo "A Moved operation is currently in use....\n";
            $unix->framework_exec("exec.backup.logsink.php --move-directory");
            return false;
        }
    }

    rsyslog_check_includes();
    build_service();

    $cmd="/usr/sbin/rsyslogd -n -i /var/run/rsyslogd.pid";
    $unix->go_exec($cmd);

    for($i=1;$i<5;$i++){
        _out("Waiting $i/5");
        sleep(1);
        $pid=rsyslog_pid();
        if($unix->process_exists($pid)){break;}
    }

    $pid=rsyslog_pid();
    if($unix->process_exists($pid)) {
        _out("Success PID $pid");
        return true;
    }

    _out("Failed");
    _out("$cmd");
    return false;

}

function build_service():bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/rsyslog";
    $php5script=basename(__FILE__);
    $daemonbinLog="Rsyslog service";
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         rsyslog";
    $f[]="# Required-Start:    \$local_fs";
    $f[]="# Required-Stop:     \$local_fs";
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
    $f[]="    $php /usr/share/artica-postfix/$php5script --restart-service \$2 \$3";
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
    return true;
}
function restart_syslog():bool{
	if($GLOBALS["NORESTART"]){return true;}
	echo "Starting......: ".date("H:i:s")." syslog restart daemon\n";
	$unix=new unix();
	$sysloginit=$unix->LOCATE_SYSLOG_INITD();
	if(!is_file($sysloginit)){echo "Starting......: ".date("H:i:s")." syslog init.d/*? no such file\n";
		build_progress_restart(110,"init.d no such file");
		return false;
    }

    if(is_file("/etc/rsyslog.d/redis-server.conf")){
        $unix->framework_exec("/usr/sbin/artica-phpfpm-service -restart-redis");
    }

	build_progress_restart(10,"{configuring}");
	rsyslog_check_includes();
    build_progress_restart(20,"{configuring}");
	$pid1=rsyslog_pid();
	build_progress_restart(30,"{restarting} PID:$pid1");
	exec("$sysloginit restart 2>&1",$results);
	foreach ($results as $line){
		if(trim($line)==null){continue;}
		echo "Starting......: ".date("H:i:s")." syslog $line\n";
	}
	$pid2=rsyslog_pid();

	if($pid1==$pid2){
		build_progress_restart(100,"{restarting} PID:$pid1 {success} {no_change} detected $pid2");
        return true;
	}
	if(is_file("/etc/init.d/artica-syslog")) {
		build_progress_restart(30,"{restarting} artica-syslog");
		shell_exec("/etc/init.d/artica-syslog restart");
	}



	$squidbin=$unix->LOCATE_SQUID_BIN();
	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	if($SQUIDEnable==1) {
		if (is_file($squidbin)) {
			build_progress_restart(40,"{reloading} {APP_SQUID}");
			squid_admin_mysql(2, "{reloading_proxy_service} (" . __FUNCTION__ . ")", null, __FILE__, __LINE__);
			shell_exec("/etc/init.d/squid reload --script=" . basename(__FILE__) . " >/dev/null 2>&1");
		}
	}

	$EnablePostfix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix");
	if($EnablePostfix==1) {
		$postfix = $unix->find_program("postfix");
		if (is_file($postfix)) {
			build_progress_restart(50,"{reloading} {APP_POSTFIX}");
			shell_exec("$postfix reload >/dev/null 2>&1");
		}
	}
	build_progress_restart(100,"{restarting} {success}");
	return true;

}


// {$GLOBALS["ARTICALOGDIR"]}/system_failover_events
function udfbguard_update_events($nopid=false){}
function load_stats(){}

function ssh_events($text,$function,$file,$line){
	writelogs($text,$function,$file,$line);
	$pid=@getmypid();
	$filename=basename(__FILE__);
	$date=@date("H:i:s");
	$logFile="{$GLOBALS["ARTICALOGDIR"]}/auth-tail.debug";
	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
	@fclose($f);
	$logFile="{$GLOBALS["ARTICALOGDIR"]}/syslogger.debug";
	if(!isset($GLOBALS["CLASS_UNIX"])){
		include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
		$GLOBALS["CLASS_UNIX"]=new unix();
	}
	$GLOBALS["CLASS_UNIX"]->events("$filename $text",$logFile);
}
function iptables_delete_all(){
	$unix=new unix();
	$iptables_restore=$unix->find_program("iptables-restore");
	$iptables_save=$unix->find_program("iptables-save");
	events("Exporting datas iptables-save > /etc/artica-postfix/iptables.conf");
	system("$iptables_save > /etc/artica-postfix/iptables.conf");
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$conf=null;
	$pattern="#.+?ArticaInstantSSH#";
	foreach ($datas as $num=>$ligne){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){continue;}
		events("skip rule $ligne from deletion");
		$conf=$conf . $ligne."\n";
	}

	events("restoring datas $iptables_restore < /etc/artica-postfix/iptables.new.conf");
	file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");


}
function events($text){
	$pid=@getmypid();
	$filename=basename(__FILE__);
	$date=@date("H:i:s");
	$logFile="{$GLOBALS["ARTICALOGDIR"]}/auth-tail.debug";
	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$pid ".basename(__FILE__)." $text\n");
	@fclose($f);
	$logFile="{$GLOBALS["ARTICALOGDIR"]}/syslogger.debug";
	if(!isset($GLOBALS["CLASS_UNIX"])){include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}$GLOBALS["CLASS_UNIX"]=new unix();}
	$GLOBALS["CLASS_UNIX"]->events("$filename $text",$logFile);
}

function GetComputerName($ip){
	if($GLOBALS["resvip"][$ip]<>null){return $GLOBALS["resvip"][$ip];}
	$name=gethostbyaddr($ip);
	$GLOBALS["resvip"]=$name;
	return $name;
}

function GetMacFromIP($ipaddr){
	$ipaddr=trim($ipaddr);
	$ttl=date('YmdH');
	if(count($GLOBALS["CACHEARP"])>3){unset($GLOBALS["CACHEARP"]);}
	if(isset($GLOBALS["CACHEARP"][$ttl][$ipaddr])){return $GLOBALS["CACHEARP"][$ttl][$ipaddr];}

	if(!isset($GLOBALS["SBIN_ARP"])){$unix=new unix();$GLOBALS["SBIN_ARP"]=$unix->find_program("arp");}
	if(strlen($GLOBALS["SBIN_ARP"])<4){return "";}

	if(!isset($GLOBALS["SBIN_PING"])){$unix=new unix();$GLOBALS["SBIN_PING"]=$unix->find_program("ping");}
	if(!isset($GLOBALS["SBIN_NOHUP"])){$unix=new unix();$GLOBALS["SBIN_NOHUP"]=$unix->find_program("nohup");}

	$cmd="{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1";
	events($cmd);
	exec("{$GLOBALS["SBIN_ARP"]} -n \"$ipaddr\" 2>&1",$results);
	foreach ($results as $num=>$line){
		if(preg_match("#^[0-9\.]+\s+.+?\s+([0-9a-z\:]+)#", $line,$re)){
			if($re[1]=="no"){continue;}
			$GLOBALS["CACHEARP"][$ttl][$ipaddr]=$re[1];
			return $GLOBALS["CACHEARP"][$ttl][$ipaddr];
		}

	}
	events("$ipaddr not found (".__LINE__.")");
	if(!isset($GLOBALS["PINGEDHOSTS"][$ipaddr])){
		shell_exec("{$GLOBALS["SBIN_NOHUP"]} {$GLOBALS["SBIN_PING"]} $ipaddr -c 3 >/dev/null 2>&1 &");
		$GLOBALS["PINGEDHOSTS"][$ipaddr]=true;
	}

return "";
}
function WriteMyLogs($text,$function,$file,$line){
	$mem=round(((memory_get_usage()/1024)/1000),2);
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	writelogs($text,$function,__FILE__,$line);
	$logFile="{$GLOBALS["ARTICALOGDIR"]}/".basename(__FILE__).".log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>9000000){unlink($logFile);}
	}
	$date=date('m-d H:i:s');
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n";}
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}][{$mem}MB][Task:{$GLOBALS["SCHEDULE_ID"]}]: [$function::$line] $text\n");
	@fclose($f);
}
?>