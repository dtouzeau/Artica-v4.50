<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);


if($argv[1]=="--confirm"){startx();exit;}


function build_progress($prc,$text):bool{

    if(!$GLOBALS["BYCONSOLE"]){
        system("echo $prc | /usr/bin/dialog --gauge \"$text\" 10 70 0");
        return true;
    }

    $unix=new unix();
    $unix->framework_progress($prc,$text,"system.reset.progress");
    return true;
}

function startx():bool{

    build_progress(10,"Reset Interface settings...");
    ApacheReset();
    build_progress(20,"Removing softwares...");

    $f[]="/usr/sbin/artica-phpfpm-service -uninstall-redsocks";
    $f[]="exec.DnsCryptProxy.php --uninstall";
    $f[]="exec.doh.php --uninstall";
    $f[]="exec.haproxy.php --uninstall";
    $f[]="exec.kibana.php --uninstall";
    $f[]="exec.klnagent.php --uninstall";
    $f[]="/usr/sbin/artica-phpfpm-service -uninstall-ntopng";
    $f[]="/usr/sbin/artica-phpfpm-service -uninstall-ntp";
    $f[]="exec.richfilemanager.php --uninstall";
    $f[]="exec.postgres.php --remove-database";
    $f[]="/usr/sbin/artica-phpfpm-service -reconfigure-syslog";

    $SERVICES["/etc/init.d/rbldnsd"]="/usr/sbin/artica-phpfpm-service -uninstall-dnsbl";
    $SERVICES["/etc/init.d/glances"]="exec.glances.php --uninstall";
    $SERVICES["/etc/init.d/proxy-pac"]="/usr/sbin/artica-phpfpm-service -uninstall-proxypac";
    $SERVICES["/etc/init.d/ufdb"]="/usr/sbin/artica-phpfpm-service -uninstall-ufdb";
    squid_admin_mysql(0,"Reseting all parameters!",null,__FILE__,__LINE__);
    $SERVICES["/etc/init.d/postfix"]="exec.postfix-install.php --uninstall";
    $SERVICES["/etc/init.d/squid-logger"]="exec.squid-logger.php --uninstall";
    $SERVICES["/etc/init.d/squid"]="/usr/sbin/artica-phpfpm-service -uninstall-proxy";
    $SERVICES["/etc/init.d/nginx"]="/usr/sbin/artica-phpfpm-service -nginx-uninstall";
    $SERVICES["/etc/init.d/dnsfilterd"]="exec.dnsfilterd.php --uninstall";
    $SERVICES["/etc/init.d/c-icap"]="/usr/sbin/artica-phpfpm-service -uninstall-cicap";
    $SERVICES["/etc/init.d/clamav-daemon"]="/usr/sbin/artica-phpfpm-service -uninstall-clamd";
    $SERVICES["/etc/init.d/web-error-page"]="/usr/sbin/artica-phpfpm-service -uninstall-weberror";
    $SERVICES["/etc/init.d/go-shield-server"]="exec.go.shield.server.php --disable";
    $SERVICES["/etc/init.d/proftpd"]="exec.proftpd.install.php --uninstall";
    $SERVICES["/etc/init.d/wazuh-agent"]="exec.wazhu.client.php --uninstall";
    $SERVICES["/etc/init.d/splunk"]="/usr/sbin/artica-phpfpm-service -uninstall-splunk-uf";
    $SERVICES["/etc/init.d/fail2ban"]="exec.fail2ban.php --uninstall";
    $SERVICES["/etc/init.d/suricata"]="/usr/sbin/artica-phpfpm-service -uninstall-ids";
    $SERVICES["/etc/init.d/snmpd"]="/usr/sbin/artica-phpfpm-service -uninstall-snmpd";
    $SERVICES["/etc/init.d/redis-server"]="/usr/sbin/artica-phpfpm-service -uninstall-redis";
    $SERVICES["/etc/init.d/privoxy"]="exec.privoxy.php --remove";
    $SERVICES["/etc/init.d/pdns"]="exec.pdns_server.install.php --remove";
    $SERVICES["/etc/init.d/pdns_recursor"]="exec.pdns_server.install.php --uninstall-recursor";
    $SERVICES["/etc/init.d/isc-dhcp-server"]="/usr/sbin/artica-phpfpm-service -uninstall-dhcpd";
    $SERVICES["/etc/init.d/mysql"]="exec.mysql.start.php --uninstall";
    $i=20;
    foreach ($SERVICES as $initd=>$script){
        if(is_file($initd)){
            $i++;
            if($i>70){$i=70;}
            build_progress($i,"Removing $initd...");
            shell_exec("/usr/bin/php /usr/share/artica-postfix/$script");
        }
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSyslogLogSink",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActAsASyslogServer",0);

    foreach ($f as $cmdline){
        $i++;
        if($i>70){$i=70;}
        build_progress($i,"Removing softwares...");
        shell_exec("/usr/bin/php /usr/share/artica-postfix/$cmdline >/dev/null 2>&1");
    }

    $dbs[]="acls.db";
    $dbs[]="certificates.db";
    $dbs[]="etc_hosts.db";
    $dbs[]="hypercache.db";
    $dbs[]="link_balancer.db";
    $dbs[]="nmapping.db";
    $dbs[]="postqueue.db";
    $dbs[]="radius.db";
    $dbs[]="sshd.db";
    $dbs[]="sys_schedules.db";
    $dbs[]="webfilter.db";
    $dbs[]="admins.db";
    $dbs[]="clusters_events.db";
    $dbs[]="firewall.db";
    $dbs[]="mgr_client_list.db";
    $dbs[]="ntp.db";
    $dbs[]="privileges.db";
    $dbs[]="rdpproxy.db";
    $dbs[]="ssl_db.db";
    $dbs[]="syslogrules.db";
    $dbs[]="wordpress.db";
    $dbs[]="antivirus.db";
    $dbs[]="dhcpd.db";
    $dbs[]="fping.db";
    $dbs[]="imapbox.db";
    $dbs[]="mgr_client_list_stats.db";
    $dbs[]="openvpn.db";
    $dbs[]="proxy.db";
    $dbs[]="rpz.db";
    $dbs[]="strongswan.db";
    $dbs[]="system_events.db";
    $dbs[]="caches.db";
    $dbs[]="dns.db";
    $dbs[]="ftpusers.db";
    $dbs[]="nginx.db";
    $dbs[]="postfix.db";
    $dbs[]="proxy_search.db";
    $dbs[]="siege.db";
    $dbs[]="suricata.db";
    $dbs[]="unbound.db";
    $dbs[]="categories.caches.db";
    $dbs[]="haproxy.db";
    $dbs[]="ipinfo.db";
    $dbs[]="postfix_events.db";
    $dbs[]="python-packages.db";
    $dbs[]="spamassassin.db";
    $dbs[]="sys.db";
    foreach ($dbs as $dbfile){
        if(!is_file("/home/artica/SQLITE/$dbfile")){continue;}
        build_progress($i,"Removing configuration $dbfile");
        @unlink("/home/artica/SQLITE/$dbfile");
        $i++;
        if($i>70){$i=70;}
    }

    system("echo 1 >/etc/artica-postfix/settings/Daemons/SYSTEMID_CREATED");
    system("echo 1 >/etc/artica-postfix/settings/Daemons/ArticaHttpUseSSL");

    if(is_file("/var/log/artica-wizard.log")){
        @unlink("/var/log/artica-wizard.log");
    }

    build_progress(70,"Rebuilding database");
    shell_exec("/usr/bin/php /usr/share/artica-postfix/exec.convert-to-sqlite.php >/dev/null 2>&1");
    if(!$GLOBALS["BYCONSOLE"]) {
        build_progress(75, "Restarting Web console");
        system("/etc/init.d/artica-webconsole restart --force >/dev/null 2>&1");
        build_progress(80, "Restarting Web console");

    }



    $unix=new unix();
    $rm=$unix->find_program("rm");
    $DirsDel[]="/home/artica/rrd";
    foreach ($DirsDel as $dir){
        if(!is_dir($dir)){continue;}
        shell_exec("$rm -rf $dir/*");
    }

    build_progress(95,"Reconfiguring networks");
    shell_exec("/usr/bin/php /usr/share/artica-postfix/exec.virtuals-ip.php --build >/dev/null 2>&1");
    build_progress(100,"Done");
    return true;
}




function ApacheReset(){


    echo "Remove Security settings\n";

    shell_exec("/usr/bin/php /usr/share/artica-postfix/exec.firewall.php --build");
    shell_exec("/usr/bin/php /usr/share/artica-postfix/exec.firewall.php --remove");

    $f[]="LighttpdArticaListenIP";
    $f[]="EnableArticaWebLogging";
    $f[]="LighttpdArticaCertificateName";

    foreach ($f as $filename){
        @unlink("/etc/artica-postfix/settings/Daemons/$filename");
        @touch("/etc/artica-postfix/settings/Daemons/$filename");

    }


    @unlink("/etc/ssl/certs/apache/server.crt");
    @unlink("/etc/ssl/certs/apache/server.key");



    echo "Done\n";
}