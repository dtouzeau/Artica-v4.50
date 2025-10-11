<?php
if (!isset($GLOBALS["CLASS_SOCKETS"])) {if (!class_exists("sockets")) {include_once "/usr/share/artica-postfix/ressources/class.sockets.inc";}
    $GLOBALS["CLASS_SOCKETS"] = new sockets();}if (function_exists("posix_getuid")) {if (posix_getuid() != 0) {die("Cannot be used in web server mode\n\n");}}
include_once dirname(__FILE__) . '/ressources/class.templates.inc';
include_once dirname(__FILE__) . '/ressources/class.ldap.inc';
include_once dirname(__FILE__) . '/ressources/class.computers.inc';
include_once dirname(__FILE__) . '/ressources/class.system.network.inc';
include_once dirname(__FILE__) . '/ressources/class.ccurl.inc';
include_once dirname(__FILE__) . '/ressources/class.elasticssearch.inc';
include_once dirname(__FILE__) . '/framework/class.unix.inc';
include_once dirname(__FILE__) . '/ressources/class.sockets.inc';
include_once dirname(__FILE__) . "/framework/frame.class.inc";

$GLOBALS["CLASS_SOCKETS"] = new sockets();
$GLOBALS["PROGRESS_FILE"] = PROGRESS_DIR."/system.installsoft.progress";
if (preg_match("#--verbose#", implode(" ", $argv))) {$GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);}
if ($argv[1] == "--install") {
    if(!isset($argv[4])){$argv[4]=null;}
    install($argv[2], $argv[3],$argv[4]);exit;}

function GET_SQUID_VERSION():string{
    exec("/usr/sbin/squid -v 2>&1",$results);
    foreach ($results as $line){
        if(preg_match("#Squid Cache: Version\s+([0-9\.]+)#",$line,$re)){
            return $re[1];
        }
    }
    return strval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion"));
}

function install($product, $key,$localpath=null){
    $isSquid5   = false;
    $isSquid6   = false;
    $isSquid4   = false;
    $RESTART_CONSOLE=false;
    $v4softsRepo=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("v4softsRepo");
	$UPDATES_ARRAY = unserialize(base64_decode($v4softsRepo));
	$el = new elasticsearch();
    $CurrentVer = $el->GetVersion();
    $AR_ROOT=ARTICA_ROOT;
    $SquidVersion=GET_SQUID_VERSION();
    if(preg_match("#^5\.#",$SquidVersion)){$isSquid5=true;}
    if(preg_match("#^6\.#",$SquidVersion)){$isSquid6=true;}
    if(preg_match("#^4\.#",$SquidVersion)){$isSquid4=true;}
    echo "Starting {{$product}} ($key)\n";

    if (!$UPDATES_ARRAY) {
        echo "$v4softsRepo\n";
        build_progress("v4softsRepo no array!", 110);
        return false;
    }



    if (!isset($UPDATES_ARRAY[$product])) {
        build_progress("$product no key!", 110);
        return false;

    }
    $MAIN = $UPDATES_ARRAY[$product][$key];

    foreach ($MAIN as $integ=>$b){
        echo "$integ: $b\n";
    }

    $unix = new unix();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LinuxDistributionFullName","");
    $LINUX_CODE_NAME    = $unix->LINUX_CODE_NAME();
    $LINUX_DISTRIBUTION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("LinuxDistributionFullName");
    $LINUX_VERS         = $unix->LINUX_VERS();
    $LINUX_ARCHITECTURE = $unix->LINUX_ARCHITECTURE();
    $DebianVer          = "debian{$LINUX_VERS[0]}";
    $TMP_DIR            = $unix->TEMP_DIR();
    $DEBIAN_VERSION     = $unix->DEBIAN_VERSION();
    $php                = $unix->LOCATE_PHP5_BIN();
    $MINZ               = array();
    $VERSION            = $MAIN["VERSION"];
    $DISTRI             = $MAIN["DISTRI"];
    $SIZE               = $MAIN["SIZE"];
    $URL                = $MAIN["URI"];
    $MD5                = $MAIN["MD5"];
    $pgrep=$unix->find_program("pgrep");
    $kill=$unix->find_program("kill");
    $aptget             = $unix->find_program("apt-get");
    $ArticaRepoSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoSSL"));
    if ($ArticaRepoSSL==1){
        $URL=str_replace("http://mirror.articatech.com", "https://www.articatech.com",$URL);
    }
    if ($URL == null) {
        build_progress("{{$product}} ($key) no such URL", 110);
        return false;

    }
    if($product=="APP_NGINX") {
        if($DEBIAN_VERSION==10) {
            $MINZ["/usr/lib/x86_64-linux-gnu/liblua5.2.so.0"]   = "liblua5.2-0";
            $MINZ["/usr/lib/x86_64-linux-gnu/liblua5.3.so.0"]   = "liblua5.3-0";
            $MINZ["/usr/lib/x86_64-linux-gnu/libfuzzy.so.2"]    = "libfuzzy2";
        }
    }



    if(count($MINZ)>0){
        foreach ($MINZ as $path=>$package){
            if(!is_file($path)){
                build_progress("{{$product}} {installing} $package", 110);
                $unix->DEBIAN_INSTALL_PACKAGE($package);
            }
        }

        foreach ($MINZ as $path=>$package){
            if(!is_file($path)){
                build_progress("{{$product}} {missing} $package",110);
                return false;
            }
        }
    }


    build_progress("{downloading} $VERSION.tgz...", 10);
    $destfile = $TMP_DIR . "/package.tgz";
    if (is_file($destfile)) {
        echo "Removing old package....\n";
        @unlink($destfile);
    }

    echo "Current system..........: $LINUX_CODE_NAME $LINUX_DISTRIBUTION {$LINUX_VERS[0]}/{$LINUX_VERS[1]} $LINUX_ARCHITECTURE\n";
    echo "Package.................: $VERSION.tgz\n";
    echo "Version.................: $VERSION\n";
    echo "Operating system........: Debian{$DISTRI}/$DebianVer\n";
    echo "Temp dir................: $TMP_DIR\n";
    echo "Size....................: $SIZE bytes\n";
    $DOWNLOAD=True;
    if($localpath<>null) {
        if (is_file($localpath)) {
            $DOWNLOAD = false;
            $destfile=$localpath;
        }
    }
    echo "Destination file........: $destfile\n";

    if($DOWNLOAD) {
        echo "Downloading file...\n";
        $curl = new ccurl($URL);
        $curl->WriteProgress = true;
        $curl->ProgressFunction = "download_progress";
        if (!$curl->GetFile($destfile)) {
            @unlink($destfile);
            build_progress("{downloading} {{$product}} ($key) failed with error $curl->error...", 110);
            return false;
        }
        if(!is_file($destfile)){
            echo "$destfile no such file after a successfull download !\n";
            build_progress("$destfile Strange behavior after downloading file.", 110);
            return false;
        }



    }

    $ftype = 'application/octet-stream';
    $finfo = @finfo_open(FILEINFO_MIME);
    if ($finfo !== FALSE) {
        $fres = @finfo_file($finfo, $destfile);
        if ( ($fres !== FALSE)
            && is_string($fres)
            && (strlen($fres)>0)) {
            $ftype = $fres;
        }
        @finfo_close($finfo);
    }



    $size=@filesize($destfile);
    echo "$destfile :".$size."bytes (".FormatBytes($size/1024).") - $ftype\n";

    $newmd5 = md5_file($destfile);
    if ($newmd5 != $MD5) {
        echo "$newmd5<>$MD5\n";
        @unlink($destfile);
        build_progress("$destfile {{$product}} ($key) MD5 verification failed ...", 110);
        return false;
    }

    build_progress("{stopping} {APP_MONIT} ($key)...", 35);
    shell_exec("/etc/init.d/monit stop");
    build_progress("{stopping} {APP_ARTICA_STATUS} ($key)...", 40);
    shell_exec("/etc/init.d/artica-status stop --force");

    if ($product == "APP_TAILSCALE") {
        if(is_file("/etc/init.d/tailscale")){
            build_progress("{stopping} {{$product}} ($key)...", 40);
            shell_exec("/etc/init.d/tailscale stop");
        }
        if(is_file("/etc/init.d/tailscale-web")){
            build_progress("{stopping} {{$product}} ($key)...", 45);
            shell_exec("/etc/init.d/tailscale-web stop");
        }
    }
    if ($product == "SQUID_AD_RESTFULL") {
        build_progress("{stopping} {{$product}} ($key)...", 45);
        system("/etc/init.d/monit stop >/dev/null 2>&1");
        shell_exec("/etc/init.d/artica-ad-rest stop");
        shell_exec("$kill -9 `$pgrep -f -l articarest`");
        shell_exec("$kill -9 `$pgrep -f -l artica-phpfpm-service`");
    }
    if ($product == "APP_PROFTPD") {
        build_progress("{stopping} {{$product}} ($key)...", 45);
        system("/etc/init.d/monit stop >/dev/null 2>&1");
        if(is_file("/etc/init.d/proftpd")) {
            system("/usr/sbin/artica-phpfpm-service -stop-proftpd");
        }
    }
    if ($product == "APP_LOKI") {
        build_progress("{stopping} {{$product}} ($key)...", 45);
        if(is_file("/etc/init.d/loki")) {
            system("/usr/sbin/artica-phpfpm-service -stop-loki >/dev/null 2>&1");
        }
        build_progress("{stopping} {{$product}} ($key)...", 46);
        if(is_file("/etc/init.d/grafana")) {
            system("/usr/sbin/artica-phpfpm-service -stop-grafana >/dev/null 2>&1");
        }
        build_progress("{stopping} {{$product}} ($key)...", 47);
        if(is_file("/etc/init.d/grafana-agent")) {
            system("/usr/sbin/artica-phpfpm-service -stop-grafana-agent >/dev/null 2>&1");
        }
    }


    if($product=="APP_AUTOFS"){
        build_progress("{uninstalling} {APP_AUTOFS} (APT Debian) ($key)...", 35);
        shell_exec("$aptget remove autofs");
        if(is_file("/etc/init.d/autofs")) {
            build_progress("{stopping} {{$product}} ($key)...", 45);
            shell_exec("/usr/sbin/artica-phpfpm-service -stop-autofs");
        }
    }

    if ($product=="APP_FRONTAIL"){
        build_progress("{stopping} {{$product}} ($key)...", 40);
        shell_exec("/usr/sbin/artica-phpfpm-service -stop-frontail-smtp");
        shell_exec("/usr/sbin/artica-phpfpm-service -stop-frontail-syslog");
    }
    if ($product=="APP_VNSTAT"){
        build_progress("{stopping} {{$product}} ($key)...", 40);
        shell_exec("/usr/sbin/artica-phpfpm-service -stop-vnstat");
    }

    if ($product == "APP_SYSLOGD") {
        build_progress("{stopping} {{$product}} ($key)...", 40);
        shell_exec("/etc/init.d/rsyslog stop");
    }

    if ($product == "APP_FILEBEAT") {
        shell_exec("$php /usr/share/artica-postfix/exec.filebeat.php --setup");
    }

    if($product=="APP_MYSQL"){
        $INSTALLED=false;
        if(is_file("/etc/init.d/mysql")){
            $INSTALLED=true;
            build_progress("{{$product}} ($key) {stopping_service}...", 40);
            system("$php $AR_ROOT/exec.mysql.start.php --stop");
        }
        $aptget=$unix->find_program("apt-get");
        build_progress("{backup_database} {{$product}} ($key) {uninstall}...", 41);
        $packagesNames[]="mariadb-server";
        $packagesNames[]="mariadb-server-10.3";
        $packagesNames[]="mariadb-server-core-10.3";
        $packagesNames[]="mariadb-client";
        $packagesNames[]="mariadb-client-10.3";
        $packagesNames[]="mariadb-client-core-10.3";
        foreach ($packagesNames as $package) {
            build_progress("{{$product}} ($key) {uninstall} $package...", 43);
            $cmd = "DEBIAN_FRONTEND=noninteractive $aptget remove --purge -yqq $package";
            echo "$cmd\n";
            system("$cmd");
        }

        if($INSTALLED){
            if(!is_file("/etc/init.d/mysql")){
                system("$php $AR_ROOT/exec.mysql.start.php --install");
            }
        }

    }

    if($product=="APP_POSTGRES"){
        $rm=$unix->find_program("rm");
        if(is_file("/usr/local/ArticaStats/bin/postgres")) {
            build_progress("{backup_database} {{$product}} ($key)...", 40);
            shell_exec("$php /usr/share/artica-postfix/exec.postgres.php --upgrade-backup");
            system("$php $AR_ROOT/exec.postgres.php --stop");
        }
        $dirs[]="/usr/local/ArticaStats";
        $dirs[]="/home/ArticaStats";
        $dirs[]="/home/ArticaStatsDB";
        foreach ($dirs as $directory){
            if(is_dir($directory)){
                build_progress("{remove} $directory {{$product}} ($key)...", 76);
                system("$rm -rf $directory");
                @mkdir($directory,0755,true);
                @chown($directory,"ArticaStats");
                @chgrp($directory,"ArticaStats");
            }
        }
    }

    if ($product == "APP_ELASTICSEARCH") {
        $ingesDir = "/etc/elasticsearch/ingest_geoip";
        if (is_dir($ingesDir)) {
            system("/usr/share/elasticsearch/bin/elasticsearch-plugin remove --purge ingest-geoip");
		}

        if (is_file("/etc/init.d/elasticsearch")) {

            if (substr($CurrentVer, 0, strlen('6.7')) < '6.7' && substr($VERSION, 0, strlen('7.0')) >= '7.0') {
                //FULL UPGRADE STOP
                build_progress("{do_full_upgrade_stop} {{$product}} ($key)...", 40);
                $el->FullUpgradeStop_Step1();
            }
            if ((substr($CurrentVer, 0, strlen('6.7')) >= '6.7' && substr($VERSION, 0, strlen('7.0')) >= '7.0') || (substr($CurrentVer, 0, strlen('6.7')) < '6.7' && substr($VERSION, 0, strlen('6.7')) == '6.7')) {
                //ROLLING UPGRADE STOP
                build_progress("{do_rolling_upgrade_stop} {{$product}} ($key)...", 40);
                $el->RollingUpgradeStop_Step1();
            }
            build_progress("{stopping} {{$product}} ($key)...", 40);
            @unlink("/etc/monit/conf.d/APP_ELASTICSEARCH.monitrc");
            shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");

            system("/etc/init.d/elasticsearch stop");
        }

        if (is_file("/etc/init.d/kibana")) {
            build_progress("{stopping} {{$product}} ($key)...", 45);
            @unlink("/etc/monit/conf.d/APP_KIBANA.monitrc");
            shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
            build_progress("{stopping} {{$product}} ($key)...", 46);
            system("/etc/init.d/kibana stop");
        }
    }

    build_progress("{installing} {{$product}} ($key)...", 50);
    $tar = $unix->find_program("tar");
    $nohup = $unix->find_program("nohup");
    $php = $unix->LOCATE_PHP5_BIN();
    $rm=$unix->find_program("rm");

    if ($product == "APP_POSTFIX") {
        build_progress("{uninstalling} {{$product}} ($key) APT...", 54);
        shell_exec("$aptget remove postfix --purge -y");
        build_progress("{reconfiguring} {{$product}} ($key)...", 55);
        chdir("/etc/postfix");
        system("cd /etc/postfix");
        build_progress("{backup} {{$product}} ($key)...", 56);
        system("$tar -czf /root/postfix-backup.tar.gz *");
        chdir("/root");
        system("cd /root");
    }

    if ($product == "APP_CROWDSEC") {
        build_progress("{reconfiguring} {{$product}} ($key)...", 55);
        if(is_file("/etc/init.d/crowdsec")){
            build_progress("{stopping} {{$product}} ($key)...", 55);
            shell_exec("$php /usr/share/artica-postfix/exec.crowdsec.php --stop --".time());
        }
    }

    if ($product == "APP_MIMEDEFANG") {
        build_progress("{installing} {{$product}} (libmime-tools-perl)...", 56);
        if (!is_file("/usr/share/perl5/MIME/Tools.pm")) {$unix->DEBIAN_INSTALL_PACKAGE("libmime-tools-perl");} else {echo "libmime-tools-perl OK\n";}
        build_progress("{installing} {{$product}} (libmime-encwords-perl)...", 56);
        if (!is_file("/usr/share/perl5/MIME/EncWords.pm")) {$unix->DEBIAN_INSTALL_PACKAGE("libmime-encwords-perl");} else {echo "libmime-encwords-perl OK\n";}
        build_progress("{installing} {{$product}} (spamassassin)...", 56);
        if (!is_file("/usr/share/perl5/Mail/SpamAssassin.pm")) {$unix->DEBIAN_INSTALL_PACKAGE("spamassassin");} else {echo "spamassassin OK\n";}
        build_progress("{installing} {{$product}} (libhtml-parser-perl)...", 56);
        if (!is_file("/usr/lib/x86_64-linux-gnu/perl5/5.24/HTML/Parser.pm")) {$unix->DEBIAN_INSTALL_PACKAGE("libhtml-parser-perl");} else {echo "libhtml-parser-perl OK\n";}
        build_progress("{installing} {{$product}} (clamav)...", 56);
        if (!is_file("/usr/bin/clamscan")) {$unix->DEBIAN_INSTALL_PACKAGE("clamav");} else {echo "clamav OK\n";}
        build_progress("{installing} {{$product}} (clamav-daemon)...", 56);
        if (!is_file("/usr/bin/clamd")) {$unix->DEBIAN_INSTALL_PACKAGE("clamav-daemon");} else {echo "clamav-daemon OK\n";}
        build_progress("{installing} {{$product}} (ripmime)...", 56);
        if (!is_file("/usr/bin/ripmime")) {$unix->DEBIAN_INSTALL_PACKAGE("ripmime");} else {echo "ripmime OK\n";}
        build_progress("{installing} {{$product}} (spfquery)...", 56);
        if (!is_file("/usr/bin/spftest")) {$unix->DEBIAN_INSTALL_PACKAGE("spfquery");} else {echo "spfquery OK\n";}
    }

    if ($product == "APP_ELASTICSEARCH") {
        system("rm -rf /usr/share/elasticsearch/");
		system("rm -rf /usr/share/kibana/");
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KibanaVersion", $VERSION);
    }
    if($product=="APP_NETDATA") {
        if(is_file("/etc/init.d/netdata")) {
            build_progress("{stopping} {{$product}} ($key)...", 56);
            shell_exec("/etc/init.d/netdata stop");
            $dirs[]="/etc/netdata";
            $dirs[]="/var/lib/netdata";
            $dirs[]="/var/cache/netdata";
            $dirs[]="/var/lib/netdata";
            $dirs[]="/usr/lib/netdata";
            $dirs[]="/usr/share/netdata";
            foreach ($dirs as $directory){
                if(!is_dir($directory)){continue;}
                echo "Removing $directory\n";
                system("rm -rf $directory/*");
            }
            $dirs=array();
        }
    }
    if ($product == "APP_SYSLOGD") {
        build_progress("{starting} {{$product}} ($key)...", 56);
        shell_exec("$php /usr/share/artica-postfix/exec.status.php --process1 --force --".time());
        shell_exec("/etc/init.d/rsyslog start");
    }
    if ($product == "APP_ZABBIX_AGENT") {
        build_progress("{starting} {{$product}} ($key)...", 56);
        shell_exec("$php /usr/share/artica-postfix/exec.zabbix.php --stop --force --".time());
    }
    if($product=="APP_SNMPD"){
        if(is_file("/etc/init.d/snmpd")){
            build_progress("{stopping} {{$product}} ($key)...", 56);
            shell_exec("/etc/init.d/snmpd stop");
        }
    }
    if($product=="APP_RDPPROXY") {
        build_progress("{stopping} {{$product}} ($key)...", 56);
        if (is_file("/etc/init.d/rdpproxy")) {
            shell_exec("/etc/init.d/rdpproxy stop");
        }
        if (is_file("/etc/init.d/rdpproxy-authhook")) {
            shell_exec("/etc/init.d/rdpproxy-authhook stop");
        }
    }
    if($product=="APP_SSHPORTAL") {
        if(is_file("/etc/init.d/sshportal")){
            build_progress("{stopping} {{$product}} ($key)...", 56);
            shell_exec("/etc/init.d/sshportal stop");
        }
    }
    if($product=="APP_C_ICAP") {
        build_progress("{stopping} {{$product}} ($key)...", 56);
        if (is_file("/etc/init.d/c-icap")) {
            shell_exec("/etc/init.d/c-icap stop");
        }
    }
    if($product=="APP_MEMCACHED") {
        build_progress("{reconfiguring} {{$product}} ($key)...", 56);
        if(is_file("/etc/init.d/memcached")){
            shell_exec("/usr/sbin/artica-phpfpm-service -stop-memcached");
        }
    }
    if($product=="APP_DNSDIST" OR $product=="APP_DNSDIST9") {
        if(is_file("/etc/init.d/squid-dns")){
            build_progress("{stopping} {{$product}} ($key)...", 56);
            system("/etc/init.d/squid-dns stop");
        }
        if(is_file("/etc/init.d/dnsdist")){
            build_progress("{stopping} {{$product}} ($key)...", 56);
            system("/etc/init.d/dnsdist stop");
        }
    }
    if($product=="APP_SURICATA") {
        if(is_file("/etc/init.d/suricata")){
            build_progress("{stopping} {{$product}} ($key)...", 56);
            system("/usr/sbin/artica-phpfpm-service -stop-ids");
        }
    }
    build_progress("{uncompressing} {{$product}} ($key)...{success}", 59);

    $tmpdir=$unix->TEMP_DIR()."/$product";
    if(!is_dir($tmpdir)){@mkdir($tmpdir);}


    if(!is_dir($tmpdir)){
        echo "$tmpdir no such directory or permission denied\n";
        build_progress("{installing} {{$product}} ($key)...{failed}", 110);
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        $unix->framework_exec("/usr/sbin/artica-phpfpm-service -start-artica-status");
        return false;
    }


    build_progress("{uncompressing} {{$product}} ($key)..", 59);
    system("$tar xf $destfile -C $tmpdir/");
    build_progress("{uncompressing} {{$product}} ($key)...{success}", 60);
    build_progress("{installing} {{$product}} ($key)...", 65);


    $cp=$unix->find_program("cp");
    $binaries[]="usr/local/bin";
    $binaries[]="usr/local/sbin";
    $binaries[]="usr/local/lib";
    $binaries[]="usr/local/man";
    $binaries[]="usr/local/openvpn";
    $binaries[]="usr/local/share/ntopng";
    $binaries[]="usr/local/share";
    $binaries[]="usr/lib/x86_64-linux-gnu/xtables";
    $binaries[]="usr/lib/x86_64-linux-gnu";
    $binaries[]="usr/lib";
    $binaries[]="usr/sbin";
    $binaries[]="usr/bin";
    $binaries[]="sbin";
    $binaries[]="bin";
    $binaries[]="lib/modules/4.19.0-6-amd64/extra";
    $binaries[]="lib/modules/4.19.0-6-amd64";
    $binaries[]="lib/modules";
    $binaries[]="lib/squid3";
    $binaries[]="lib";
    $binaries[]="usr/share/squid3";
    $binaries[]="etc/squid3";
    $binaries[]="/usr/share/artica-postfix/bin/go-shield/server/bin/";
    $binaries[]="/usr/share/artica-postfix/bin/go-shield/client/external_acl_first/bin/";
    $binaries[]="/usr/share/artica-postfix/bin/go-shield/client/external_acls_ldap/bin/";
    $binaries[]="/usr/share/artica-postfix/bin/go-shield/exec/";
    $binaries[]="/usr/share/artica-postfix/bin/go-shield/fs-watcher/bin/";
    $binaries[]="/usr/share/artica-postfix/bin/";

    foreach ($binaries as $directory){
        $srcdir="$tmpdir/$directory";
        $destdir="/$directory";
        $destdir=str_replace("//","/",$destdir);
        $srcdir=str_replace("//","/",$srcdir);
        if(!is_dir($srcdir)){continue;}
        $srcdir_to_remove=$srcdir;
        build_progress("{installing} {$destdir}...", 66);
        if(!is_dir($destdir)){@mkdir($destdir,0755,true);}
        $destdir=$destdir."/";
        $srcdir=$srcdir."/";
        $destdir=str_replace("//","/",$destdir);
        $srcdir=str_replace("//","/",$srcdir);
        echo "Copy files $srcdir to $destdir\n";
        system("$cp -rfva $srcdir* $destdir");
        echo "Removing $srcdir_to_remove\n";
        shell_exec("$rm -rf $srcdir_to_remove");
    }

    shell_exec("$cp -rfva $tmpdir/* /");
    build_progress("{removing} {$tmpdir}...", 67);
    echo "Removing $tmpdir\n";
    shell_exec("$rm -rf $tmpdir");

    if($product=="APP_NAGIOS_CLIENT"){
        build_progress("{installing} {{$product}}...", 68);
        if(is_file("/etc/init.d/ncpa_listener")) {
            shell_exec("/usr/sbin/artica-phpfpm-service -install-nagios");
            build_progress("{restarting} {{$product}}...", 68);
            shell_exec("/usr/sbin/artica-phpfpm-service -restart-nagios");
        }
    }


    build_progress("ldconfig...", 68);
    $ldconfig = $unix->find_program("ldconfig");
    system("$ldconfig -v >/dev/null 2>&1");

    if($product=="APP_NGINX_CONSOLE"){
       $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UseHttp2",1);
       $GLOBALS["CLASS_SOCKETS"]->SET_INFO("STOP_APP_NGINX_CONSOLE_WARN",1);
       system("$php $AR_ROOT/exec.status.php --process1 --force --verbose>/dev/null 2>&1");
       shell_exec("/etc/init.d/artica-webconsole restart");

    }
    if($product=="APP_FAILOVER_CHECKER") {
        if(is_file("/etc/init.d/keepalived")){
            build_progress("{restarting} {{$product}} ($key)...", 56);
            system("$php $AR_ROOT/exec.keepalived.php --reconfigure");
        }
    }
    if($product=="APP_WAZHU"){
        if(is_file("/etc/init.d/wazuh-agent")){
            build_progress("{restarting} {{$product}} ($key)...", 80);
            system("$php $AR_ROOT/exec.wazhu.client.php --restart");
        }
    }
    if ($product == "APP_ZABBIX_AGENT") {
        if(is_file("/etc/init.d/zabbix-agent")) {
            build_progress("{starting} {{$product}} ($key)...", 56);
            $unix->framework_exec("/usr/sbin/artica-phpfpm-service -stop-artica-status");
            system("$nohup /etc/init.d/monit stop >/dev/null 2>&1 &");
            shell_exec("$php /usr/share/artica-postfix/exec.zabbix.php --stop --force --" . time());
        }
    }
    if ($product == "SQUID_AD_RESTFULL") {
        build_progress("{starting} {{$product}} ($key)...", 56);
        shell_exec("/etc/init.d/artica-ad-rest stop");
        build_progress("{starting} {{$product}} ($key)...", 57);
        shell_exec("$kill -9 `$pgrep -f -l articarest`");
        build_progress("{starting} {{$product}} ($key)...", 58);
        shell_exec("$kill -9 `$pgrep -f -l artica-phpfpm-service`");
        build_progress("{starting} {{$product}} ($key)...", 59);
        system("/etc/init.d/monit start >/dev/null 2>&1");
        build_progress("{starting} {{$product}} ($key)...", 60);
        shell_exec("/etc/init.d/artica-ad-rest restart");
        build_progress("{starting} {{$product}} ($key)...", 61);
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose>/dev/null 2>&1");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product == "APP_LOKI") {
        build_progress("{starting} {{$product}} ($key)...", 56);
        if(is_file("/etc/init.d/loki")) {
            system("/usr/sbin/artica-phpfpm-service -start-loki >/dev/null 2>&1");
        }
        build_progress("{starting} {{$product}} ($key)...", 57);
        if(is_file("/etc/init.d/grafana")) {
            system("/usr/sbin/artica-phpfpm-service -start-grafana >/dev/null 2>&1");
        }
        build_progress("{starting} {{$product}} ($key)...", 58);
        if(is_file("/etc/init.d/grafana-agent")) {
            system("/usr/sbin/artica-phpfpm-service -start-grafana-agent >/dev/null 2>&1");
        }
        system("/usr/sbin/artica-phpfpm-service -process1 >/dev/null 2>&1");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_FRONTAIL"){
        build_progress("{starting} {{$product}} ($key)...", 80);
        shell_exec("/usr/sbin/artica-phpfpm-service -start-frontail-smtp");
        shell_exec("/usr/sbin/artica-phpfpm-service -start-frontail-syslog");
        shell_exec("/usr/sbin/artica-phpfpm-service -process1");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_VNSTAT"){
        build_progress("{starting} {{$product}} ($key)...", 80);
        shell_exec("/usr/sbin/artica-phpfpm-service -start-vnstat");
        shell_exec("/usr/sbin/artica-phpfpm-service -process1");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product == "APP_PROFTPD") {
        if(is_file("/etc/init.d/proftpd")) {
            build_progress("{starting} {{$product}} ($key)...", 80);
            system("/usr/sbin/artica-phpfpm-service -start-proftpd");
        }
        build_progress("{starting} {{$product}} ($key)...", 90);
        system("/usr/sbin/artica-phpfpm-service -process1");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_REDIS_SERVER"){
        build_progress("{restarting} {{$product}} ($key)...", 80);
        system("/usr/sbin/artica-phpfpm-service -restart-redis");
        build_progress("{restarting} {{$product}} ($key)...", 90);
        system("/usr/sbin/artica-phpfpm-service -process1");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_SURICATA"){
        // /usr/local/lib/libpfring
        if(is_file("/etc/init.d/suricata")) {
            build_progress("{restarting} {{$product}} ($key)...", 80);
            system("/usr/sbin/artica-phpfpm-service -restart-ids");
            build_progress("{restarting} {{$product}} ($key)...", 90);

        }
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose>/dev/null 2>&1");
        shell_exec("/usr/sbin/artica-phpfpm-service -start-artica-status");
        build_progress("{success} {{$product}} ($key)...", 100);

        return true;
    }
    if ($product=="APP_GO_SHIELD_SERVER"){
        $src = "/usr/bin/go-shield-server";
        $dst=ARTICA_ROOT."/bin/go-shield/server/bin/go-shield-server";
        $md51=md5_file($src);
        $md52=md5_file($dst);
        echo "Binary file on the Artica Repo: $md52\n";
        echo "Binary file on the binary Repo: $md51\n";
        if($md51==$md52){
            shell_exec("$php $AR_ROOT/exec.monit.php --start");
            shell_exec("/usr/sbin/artica-phpfpm-service -start-artica-status");
            build_progress("{success} {{$product}} ($key) (Same version)...", 100);
            return true;
        }
        $IF_INIT=false;
        if(is_file("/etc/init.d/go-shield-server")){$IF_INIT=true;}
        echo "Updating....\n";
        build_progress("{stopping} {{$product}} ($key)...", 80);
        if($IF_INIT){shell_exec("/usr/sbin/artica-phpfpm-service -stop-go-shield");}
        build_progress("{stopping} {{$product}} ($key)...", 82);
        if($IF_INIT){shell_exec("/usr/sbin/artica-phpfpm-service -stop-go-shield");}
        build_progress("{updating} {{$product}} ($key)...", 85);
        if(!@copy($dst,$src)){
            echo "Copy failed ". error_get_last()["message"] . "\n";
            shell_exec("$php $AR_ROOT/exec.monit.php --start");
            shell_exec("/usr/sbin/artica-phpfpm-service -start-artica-status");
            if($IF_INIT){shell_exec("/usr/sbin/artica-phpfpm-service -start-go-shield");}
            build_progress("{success} {{$product}} ($key) {failed}...", 110);
            return true;
        }
        $md51=md5_file($src);
        if($md51!=$md52){
            shell_exec("$php $AR_ROOT/exec.monit.php --start");
            shell_exec("/usr/sbin/artica-phpfpm-service -start-artica-status");
            if($IF_INIT){shell_exec("/usr/sbin/artica-phpfpm-service -start-go-shield");}
            build_progress("{success} {{$product}} ($key) {failed}...", 110);
            return true;
        }


        @chmod($dst,0755);
        build_progress("{starting} {{$product}} ($key)...", 90);
        if($IF_INIT){shell_exec("$php $AR_ROOT/exec.go.shield.server.php --start");}
        build_progress("{starting} {{$product}} ($key)...", 92);
        shell_exec("$php $AR_ROOT/exec.monit.php --start");
        build_progress("{starting} {{$product}} ($key)...", 95);
        shell_exec("/usr/sbin/artica-phpfpm-service -start-artica-status");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_GO_SHIELD_CONNECTOR"){
        build_progress("{restarting} {{$product}} ($key)...", 80);
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_GO_EXEC"){
        build_progress("{restarting} {{$product}} ($key)...", 80);
        $unix->go_exec("/etc/init.d/go-exec restart");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_GO_FS"){
        build_progress("{restarting} {{$product}} ($key)...", 80);
        $unix->go_exec("/etc/init.d/go-shield-server-fs-watcher restart");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_ADAGENT_CONNECTOR"){
        build_progress("{restarting} {{$product}} ($key)...", 80);
        system("$php /usr/share/artica-postfix/exec.squid.global.access.php --auth");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_GO_AD_GROUP_SEARCH"){
        build_progress("{restarting} {{$product}} ($key)...", 80);
        system("$php /usr/share/artica-postfix/exec.squid.global.access.php --auth");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_GO_HOTSPOT_ENGINE"){
        build_progress("{restarting} {{$product}} ($key)...", 80);
        $unix->go_exec("/etc/init.d/artica-hotspot restart");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_GO_PAC_ENGINE"){
        build_progress("{restarting} {{$product}} ($key)...", 80);
        $unix->go_exec("/usr/sbin/monit-restart-proxy-pac.sh");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_GO_WEBFITLER_ERROR_PAGE_ENGINE"){
        build_progress("{restarting} {{$product}} ($key)...", 80);
        $unix->go_exec("/etc/init.d/web-error-page restart");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_AUTOFS") {
        $AutoFSEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AutoFSEnabled"));
        if($AutoFSEnabled==1) {
            build_progress("{restarting} {APP_AUTOFS} ($key)...", 80);
            shell_exec("/usr/sbin/artica-phpfpm-service -start-autofs");
        }
        build_progress("{restarting} Artica status...", 95);
        system("$php $AR_ROOT/exec.status.php --process1 >/dev/null 2>&1");
        system("/etc/init.d/artica-status restart --force >/dev/null");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product == "APP_SYNO_BACKUP") {
        $depmod = $unix->find_program("depmod");
        $nohup  = $unix->find_program("nohup");
        build_progress("{running} $depmod...", 68);
        system("$nohup $depmod -a >/dev/null 2>&1 &");
    }
    if ($product == "APP_XTABLES"){
        $depmod=$unix->find_program("depmod");
        build_progress("{running} {$depmod}...", 68);
        system("$nohup $depmod -a >/dev/null 2>&1 &");
    }
    if ($product == "APP_TAILSCALE") {
        if(is_file("/etc/init.d/tailscale")){
            build_progress("{starting} {{$product}} ($key)...", 68);
            shell_exec("/etc/init.d/tailscale start");
        }
        if(is_file("/etc/init.d/tailscale-web")){
            build_progress("{starting} {{$product}} ($key)...", 69);
            shell_exec("/etc/init.d/tailscale-web start");
        }
    }
    if ($product=="APP_SSHPORTAL") {
        if(is_file("/etc/init.d/sshportal")){
            build_progress("{starting} {{$product}} ($key)...", 68);
            shell_exec("/etc/init.d/sshportal start");
        }
    }
    if ($product == "APP_ELASTICSEARCH" && substr($VERSION, 0, strlen('6.6')) <= '6.6') {
        system("/usr/share/elasticsearch/bin/elasticsearch-plugin install ingest-geoip --batch");
    }
    if ($product == "APP_ELASTICSEARCH") {
        $EnableElasticSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableElasticSearch"));
        if($EnableElasticSearch==1) {
            build_progress("{installing} {{$product}} ($key)...", 68);
            build_progress("{reconfiguring} {{$product}} ($key)...", 69);
            if (is_file("/etc/init.d/elasticsearch")) {
                system("$php /usr/share/artica-postfix/exec.elastic.search.php --monit");
            }
            build_progress("{reconfiguring} {{$product}} ($key)...", 70);
            if (is_file("/etc/init.d/elasticsearch")) {
                system("$php /usr/share/artica-postfix/exec.elastic.search.php --build");
            }
            if (is_file("/etc/init.d/elasticsearch")) {
                system("/etc/init.d/elasticsearch start");
            }
            build_progress("{reconfiguring} {{$product}} ($key)...", 71);
            if (is_file("/etc/init.d/kibana")) {
                system("$php /usr/share/artica-postfix/exec.kibana.php --monit");
            }
            if (is_file("/etc/init.d/kibana")) {
                system("$php /usr/share/artica-postfix/exec.kibana.php --build");
            }
            if (is_file("/etc/init.d/kibana")) {
                system("$php /usr/share/artica-postfix/exec.kibana.php --create_service");
            }
            build_progress("{reconfiguring} {{$product}} ($key)...", 72);
            if (is_file("/etc/init.d/kibana")) {
                system("/etc/init.d/kibana start");
            }
            if (substr($CurrentVer, 0, strlen('6.7')) < '6.7' && substr($VERSION, 0, strlen('7.0')) >= '7.0') {
                //FULL UPGRADE START
                build_progress("{do_full_upgrade_start} {{$product}} ($key)...", 75);
                $el->FullUpgradeStart_Step1();
            }
            if ((substr($CurrentVer, 0, strlen('6.7')) >= '6.7' && substr($VERSION, 0, strlen('7.0')) >= '7.0') || (substr($CurrentVer, 0, strlen('6.7')) < '6.7' && substr($VERSION, 0, strlen('6.7')) == '6.7')) {
                //ROLLING UPGRADE START
                build_progress("{do_rolling_upgrade_start} {{$product}} ($key)...", 90);
                $el->RollingUpgradeStart_Step1();
            }
        }
        shell_exec("/usr/share/elasticsearch/bin/elasticsearch-keystore upgrade");
        system("$php /usr/share/artica-postfix/exec.squid.interface-size.php --version");
        build_progress("{restarting} Artica status...", 95);
        system("$php $AR_ROOT/exec.status.php --process1 >/dev/null 2>&1");
        system("/etc/init.d/artica-status restart --force >/dev/null");
        build_progress("{success} {{$product}} ($key)...", 100);
        sleep(3);
        $nohup=$unix->find_program("nohup");
        system("$nohup /etc/init.d/artica-phpfpm restart >/dev/null 2>&1");
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        return true;
    }
    if ($product=="APP_PHP_GEOIP2"){
        build_progress("{reconfiguring} {{$product}} ($key)...", 70);
        shell_exec("/usr/sbin/artica-phpfpm-service -phpini -debug");
        build_progress("{reconfiguring} {{$product}} ($key)...", 71);
        if(is_file("/etc/init.d/artica-status")){system("/etc/init.d/artica-status restart");}
        system("$php $AR_ROOT/exec.squid.interface-size.php --version");
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
        system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
        build_progress("{success} {{$product}} ($key)...", 100);
        sleep(3);
        $nohup=$unix->find_program("nohup");
        system("$nohup /etc/init.d/artica-phpfpm restart >/dev/null 2>&1");
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        shell_exec("/etc/init.d/artica-status start");
        return true;

    }
    if ($product == "APP_ZABBIX_AGENT") {
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
        if(is_file("/etc/init.d/zabbix-agent")) {
            build_progress("{restarting} {{$product}} ($key)...", 70);
            shell_exec("$php /usr/share/artica-postfix/exec.zabbix.php --restart --force --" . time());
        }
        system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        build_progress("{success} {{$product}} ($key)...", 100);
    }
    if ($product=="APP_NGINX") {
        build_progress("{reconfiguring} {{$product}} ($key)...", 70);
        shell_exec("$php /usr/share/artica-postfix/exec.nginx.php --dump-modules");
        build_progress("{reconfiguring} {{$product}} ($key)...", 71);
        if(is_file("/etc/init.d/nginx")){system("/etc/init.d/nginx restart");}
        system("/usr/sbin/artica-phpfpm-service -nginx-modules");
        system("$php /usr/share/artica-postfix/exec.squid.interface-size.php --version");
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
        system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_MEMCACHED") {
        build_progress("{reconfiguring} {{$product}} ($key)...", 70);
        if(is_file("/etc/init.d/memcached")){system("/usr/sbin/artica-phpfpm-service -start-memcached");}
        system("$php $AR_ROOT/exec.squid.interface-size.php --version");
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose>/dev/null 2>&1");
        system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_HAPROXY") {
        build_progress("{reconfiguring} {{$product}} ($key)...", 70);
        if(is_file("/etc/init.d/haproxy")){system("/etc/init.d/haproxy restart");}
        build_progress("{reconfiguring} {{$product}} ($key)...", 71);
        if(is_file("/etc/init.d/hacluster")){system("/etc/init.d/hacluster restart");}
        build_progress("{reconfiguring} {{$product}} ($key)...", 72);
        if(is_file("/etc/init.d/parentlb")){system("/usr/sbin/artica-phpfpm-service -restart-parent-lb");}
        build_progress("{reconfiguring} {{$product}} ($key)...", 73);
        system("$php $AR_ROOT/exec.squid.interface-size.php --version");
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
        system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_ADAGENT_LBL") {
        build_progress("{reconfiguring} {{$product}} ($key)...", 70);
        if(is_file("/etc/init.d/ad-agent-lbl")){system("/etc/init.d/ad-agent-lbl restart");}
        system("$php $AR_ROOT/exec.squid.interface-size.php --version");
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
        system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product=="APP_C_ICAP") {
        build_progress("{restarting} {{$product}} ($key)...", 70);
        if(is_file("/usr/lib/c_icap/memcached_cache.so")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("C_ICAP_MEMCACHED", 1);}
        if (is_file("/etc/init.d/c-icap")) {shell_exec("/etc/init.d/c-icap restart");}
    }
    if ($product == "APP_DNSDIST" or $product=="APP_DNSDIST9") {

        build_progress("{reconfiguring} {{$product}} ($key)...", 70);
        if(is_file("/etc/init.d/squid-dns")){
            build_progress("{reconfiguring} {{$product}} ($key)...", 71);
            system("/etc/init.d/squid-dns restart");
        }
        if(is_file("/etc/init.d/dnsdist")){
            build_progress("{reconfiguring} {{$product}} ($key)...", 72);
            system("/etc/init.d/dnsdist restart");
        }

        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
        $unix->framework_exec("/usr/sbin/artica-phpfpm-service -restart-artica-status");
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product == "APP_PDNS"){
        build_progress("{reconfiguring} {{$product}} ($key)...", 70);
        if(is_file("/etc/init.d/squid-dns")){
            build_progress("{reconfiguring} {{$product}} ($key)...", 71);
            system("/etc/init.d/squid-dns restart");
        }
        if(is_file("/etc/init.d/dnsdist")){
            build_progress("{reconfiguring} {{$product}} ($key)...", 72);
            system("/etc/init.d/dnsdist restart");
        }
        if(is_file("/etc/init.d/pdns")) {
            build_progress("{reconfiguring} {{$product}} ($key)...", 73);
            system("/etc/init.d/pdns restart");
        }
        if(is_file("/etc/init.d/pdns_recursor")){
            build_progress("{reconfiguring} {{$product}} ($key)...", 74);
            system("/etc/init.d/pdns_recursor restart");
        }
        build_progress("{reconfiguring} {{$product}} ($key)...", 75);
        system("$php $AR_ROOT/exec.pdns.php --mysql>/dev/null 2>&1");
        build_progress("{reconfiguring} {{$product}} ($key)...", 76);
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
        build_progress("{reconfiguring} {{$product}} ($key)...", 77);
        $unix->framework_exec("/usr/sbin/artica-phpfpm-service -restart-artica-status");
        build_progress("{reconfiguring} {{$product}} ($key)...", 78);
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product == "APP_UFDBGUARDD"){
        build_progress("{reconfiguring} {{$product}} ($key)...", 70);
        if(is_file("/etc/init.d/ufdbcat")){
            system("/etc/init.d/ufdbcat restart");
        }
        build_progress("{reconfiguring} {{$product}} ($key)...", 71);
        if(is_file("/etc/init.d/ufdb")){
            system("/etc/init.d/ufdb restart");
        }
        build_progress("{reconfiguring} {{$product}} ($key)...", 72);
        if(is_file("/etc/init.d/dnsfilterd")){
            system("/etc/init.d/dnsfilterd restart");
        }

        system("$php $AR_ROOT/exec.squid.interface-size.php --version");
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
        system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;

    }
    if ($product == "APP_SQUID6"){
        $Upgrade=false;
        if($isSquid5){$Upgrade=true;}
        if($isSquid4){$Upgrade=true;}
        if($SQUIDEnable==1){
            if($Upgrade){
                return upgrdade_squid($key);
            }
        }
    }
    if ($product == "APP_SQUID_REV4"){
        $Downgrade=false;
        if($isSquid5){$Downgrade=true;}
        if($isSquid6){$Downgrade=true;}

        if($Downgrade && $SQUIDEnable==1){
            return downgrade_squid($key);
        }
    }
    if ($product == "APP_SQUID"){
        if(preg_match("#^4\.#",$SquidVersion)){
            if($SQUIDEnable==1) {
                return upgrdade_squid($key);
            }
        }
        if($isSquid6){
            return downgrade_squid($key);
        }
    }
    if ($product == "APP_SQUID" OR $product=="APP_SQUID_REV4" OR $product=="APP_SQUID6" ) {
        @unlink("/etc/artica-postfix/settings/Daemons/SquidRealVersion");
        @unlink("/etc/artica-postfix/settings/Daemons/SquidVersion");


        if(is_file("/etc/init.d/squid")) {
            build_progress("{reconfiguring} {{$product}} ($key)...", 70);
            if (is_file("/root/squid-good.tgz")) {
                shell_exec("/bin/tar -xf /root/squid-good.tgz -C /etc/squid3/");
            } else {
                system("$php /usr/share/artica-postfix/exec.squid.global.access.php");
            }
            build_progress("{stopping} {{$product}} ($key)...", 75);
            system("/etc/init.d/squid stop");
            build_progress("{stopping} {{$product}} ($key)...", 76);
            system("/etc/init.d/squid stop");
            build_progress("{starting} {{$product}} ($key)...", 77);
            system("/usr/sbin/artica-phpfpm-service -start-proxy");
        }
        system("$php $AR_ROOT/exec.squid.interface-size.php --version");
        system("/usr/sbin/artica-phpfpm-service -process1 >/dev/null 2>&1");
        system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("COMPILE_SQUID_TOKENS",serialize(array()));
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product == "APP_POSTFIX") {
        build_progress("{reconfiguring} {{$product}} ($key)...", 75);
        build_progress("{restore} {{$product}} ($key)...", 76);
        system("$tar -xf /root/postfix-backup.tar.gz -C /etc/postfix/");
        if (is_file("/etc/init.d/postfix")) {
            build_progress("{restarting} {{$product}} ($key)...", 77);
            system("/etc/init.d/postfix stop");
            system("/etc/init.d/postfix start");
        }
        system("$php $AR_ROOT/exec.squid.interface-size.php --version");
        system("/usr/sbin/artica-phpfpm-service -process1 >/dev/null 2>&1");
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
        system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product == "APP_UNBOUND") {
        build_progress("{reconfiguring} {{$product}} ($key)...", 75);
        if (!is_file("/etc/init.d/unbound")) {
            system("$nohup /usr/sbin/artica-phpfpm-service -install-unbound  >/dev/null 2>&1 &");
        } else {
            system("/usr/sbin/artica-phpfpm-service -restart-unbound");
        }
        system("$php $AR_ROOT/exec.squid.interface-size.php --version");
        system("/usr/sbin/artica-phpfpm-service -process1 >/dev/null 2>&1");
        system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }
    if ($product == "APP_SNMPD"){
        build_progress("{reconfiguring} {{$product}} ($key)...", 75);
        if(is_file("/etc/init.d/snmpd")){
            shell_exec("/etc/init.d/snmpd start");
        }
        system("$php $AR_ROOT/exec.squid.interface-size.php --version");
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
        system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        build_progress("{success} {{$product}} ($key)...", 100);
    }
    if ($product == "APP_POSTGRES"){
        build_progress("{reconfiguring} {{$product}} ($key)...", 75);
        system("$php $AR_ROOT/exec.postgres.php --build");
        build_progress("{restarting} {{$product}} ($key)...", 76);
        system("$php $AR_ROOT/exec.postgres.php --restart");
        build_progress("{restoring} {{$product}} ($key)...", 79);
        system("$php $AR_ROOT/exec.postgres.php --upgrade-restore");
        build_progress("{restoring} {{$product}} ($key)...", 80);
        system("$php $AR_ROOT/exec.PostgreSQL-failed.php --pg-size --force");
        build_progress("{restoring} {{$product}} ($key)...", 81);
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
        build_progress("{reconfiguring} {{$product}} ($key)...", 82);
        system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
        build_progress("{reconfiguring} {{$product}} ($key)...", 83);
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        build_progress("{success} {{$product}} ($key)...", 100);
    }
    if ($product == "APP_MYSQL"){
        if(is_file("/etc/init.d/mysql")){
            build_progress("{backup_database} {{$product}} ($key) {restarting}...", 75);
            $unix->framework_exec("exec.mysql.start.php --restart");
            system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
            build_progress("{reconfiguring} {{$product}} ($key)...", 82);
            system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
            build_progress("{reconfiguring} {{$product}} ($key)...", 83);
            system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
            build_progress("{success} {{$product}} ($key)...", 100);
            return true;
        }
        build_progress("{backup_database} {{$product}} ($key) {updating}...", 75);
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
    }
    if ($product == "APP_RDPPROXY"){
        build_progress("{reconfiguring} {{$product}} ($key)...", 75);
        system("/usr/sbin/artica-phpfpm-service -restart-rdpproxy");
        build_progress("{starting} {{$product}} ($key)...", 80);
        if(is_file("/etc/init.d/rdpproxy")){
            shell_exec("/etc/init.d/rdpproxy start");
        }
        build_progress("{starting} {{$product}} ($key)...", 85);
        if(is_file("/etc/init.d/rdpproxy-authhook")){
            shell_exec("/etc/init.d/rdpproxy-authhook start");
        }
        system("$php $AR_ROOT/exec.squid.interface-size.php --version");
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
        system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        build_progress("{success} {{$product}} ($key)...", 100);
    }
    if ($product == "APP_PDNS"){
        if (is_file("/etc/init.d/pdns")) {
            build_progress("{reconfiguring} {{$product}} ($key)...", 75);
            system("/etc/init.d/pdns restart");
        }
        if (is_file("/etc/init.d/pdns-recursor")) {
            build_progress("{reconfiguring} {{$product}} ($key)...", 80);
            system("/etc/init.d/pdns-recursor restart");
        }
        system("$php $AR_ROOT/exec.squid.interface-size.php --version");
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
        system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;

    }
    if ($product == "APP_DHCP"){
        if (is_file("/etc/init.d/isc-dhcp-server")) {
            build_progress("{reconfiguring} {{$product}} ($key)...", 75);
            system("/etc/init.d/isc-dhcp-server restart");
        }
        system("$php /usr/share/artica-postfix/exec.squid.interface-size.php --version");
        system("$php /usr/share/artica-postfix/exec.status.php --process1 >/dev/null 2>&1");
        system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
        system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;

    }
    if ($product == "APP_CROWDSEC") {
        build_progress("{reconfiguring} {{$product}} ($key)...", 80);
        if(is_file("/etc/init.d/crowdsec")){
            build_progress("{starting} {{$product}} ($key)...", 90);
            shell_exec("$php /usr/share/artica-postfix/exec.crowdsec.php --reconfigure --".time());
            shell_exec("$php /usr/share/artica-postfix/exec.crowdsec.php --start --".time());
        }
        build_progress("{scanning} {{$product}} ($key)...", 95);
        system("$php $AR_ROOT/exec.status.php --process1 --force --verbose>/dev/null 2>&1");
        build_progress("{success} {{$product}} ($key)...", 100);
        return true;
    }

    $devnull=">/dev/null 2>&1";
    $SQUIDEnable    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $reconfigure_squid="$php $AR_ROOT/exec.squid.global.access.php --noverifacls --force $devnull";
    $deny_final="$php $AR_ROOT/exec.squid.global.access.php --deny-final $devnull";
    $http_access="$php $AR_ROOT/exec.squid.global.access.php --http-access-conf $devnull";
    $rm=$unix->find_program("rm");

    system("$php $AR_ROOT/exec.squid.interface-size.php --version");
    system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
    system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
    system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");





    build_progress("{success}...", 100);

    if($RESTART_CONSOLE){
        sleep(3);
        $nohup=$unix->find_program("nohup");
        system("$nohup /etc/init.d/artica-phpfpm restart >/dev/null 2>&1");
    }
}

function build_progress($text, $pourc)
{
    echo "[{$pourc}%] $text\n";
    $array["POURC"] = $pourc;
    $array["TEXT"] = $text;
    @file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
    @chmod($GLOBALS["PROGRESS_FILE"], 0755);


}
function download_progress($client, $download_size, $downloaded, $upload_size, $uploaded){
    if(!isset($GLOBALS["PERCENT"])){
        $GLOBALS["PERCENT"][$download_size]=0;
    }
    if ($download_size === 0) {return;}
    $percent = floor($downloaded * 100 / $download_size);
    if ($GLOBALS["PERCENT"][$download_size] == $percent) {return;}
    $GLOBALS["PERCENT"][$download_size] = $percent;
    $percent2 = $percent;
    if ($percent < 15) {$percent2 = 15;}
    if ($percent > 60) {$percent2 = 60;}
    build_progress("{downloading} {$percent}%", $percent2);
}
function downgrade_squid($key){
    $product="APP_SQUID";
    $SquidVersion=GET_SQUID_VERSION();
    $NewVersion=GET_SQUID_VERSION();
    $devnull=">/dev/null 2>&1";
    $AR_ROOT=ARTICA_ROOT;

    $unix=new unix();
    $rm=$unix->find_program("rm");
    $php=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $reconfigure_squid="$php $AR_ROOT/exec.squid.global.access.php --noverifacls --force $devnull";
    $deny_final="$php $AR_ROOT/exec.squid.global.access.php --deny-final $devnull";
    $http_access="$php $AR_ROOT/exec.squid.global.access.php --http-access-conf $devnull";


    squid_admin_mysql(0,"Downgrading from $SquidVersion to proxy version $NewVersion","",
        __FILE__,__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("COMPILE_SQUID_TOKENS",serialize(array()));
    @unlink("/etc/artica-postfix/settings/Daemons/SquidVersion");
    @unlink("/etc/artica-postfix/settings/Daemons/SquidRealVersion");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidVersion",$NewVersion);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidRealVersion",$NewVersion);


    build_progress("{downgrade} to 4.x {{$product}} ($key)...", 70);
    system("$nohup /etc/init.d/artica-status restart --force $devnull");
    build_progress("{downgrade} to 4.x {{$product}} ($key)...", 71);
    system("$php $AR_ROOT/exec.status.php --process1 --force --verbose $devnull 2>&1");
    build_progress("{downgrade} to 4.x {{$product}} ($key)...", 72);
    shell_exec("$rm -f /etc/squid3/*.conf");
    build_progress("{downgrade} to 4.x {{$product}} ($key)...", 73);
    system("$php /usr/share/artica-postfix/exec.squid.php --build --force --noverifacls");
    build_progress("{downgrade} to 4.x {{$product}} ($key)...", 74);
    system("$reconfigure_squid");
    build_progress("{downgrade} to 4.x {{$product}} ($key)...", 75);
    system("$deny_final");
    build_progress("{downgrade} to 4.x {{$product}} ($key)...", 76);
    system($http_access);
    build_progress("{downgrade} to 4.x {{$product}} ($key)...", 77);
    system("$php $AR_ROOT/exec.squid.templates.php --perc");
    build_progress("{stopping_service}", 78);
    system("/usr/sbin/artica-phpfpm-service -stop-proxy");
    build_progress("{starting_service}", 79);
    $unix->go_exec("/usr/sbin/artica-phpfpm-service -start-proxy");
    system("/usr/sbin/artica-phpfpm-service -process1 >/dev/null 2>&1");
    system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("COMPILE_SQUID_TOKENS",serialize(array()));
    build_progress("{success} {{$product}} ($key)...", 100);
    return true;
}

function upgrdade_squid($key):bool{

    $product="APP_SQUID";
    $SquidVersion=GET_SQUID_VERSION();
    $NewVersion=GET_SQUID_VERSION();
    $devnull=">/dev/null 2>&1";
    $AR_ROOT=ARTICA_ROOT;

    $unix=new unix();
    $rm=$unix->find_program("rm");
    $php=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $reconfigure_squid="$php $AR_ROOT/exec.squid.global.access.php --noverifacls --force $devnull";
    $deny_final="$php $AR_ROOT/exec.squid.global.access.php --deny-final $devnull";
    $http_access="$php $AR_ROOT/exec.squid.global.access.php --http-access-conf $devnull";

    squid_admin_mysql(0,"{upgrading} from Proxy version $SquidVersion to version $NewVersion","",
        __FILE__,__LINE__);
    @unlink("/etc/artica-postfix/settings/Daemons/SquidVersion");
    @unlink("/etc/artica-postfix/settings/Daemons/SquidRealVersion");
    shell_exec("/usr/sbin/artica-phpfpm-service -restart-memcached");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidVersion",$NewVersion);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidRealVersion",$NewVersion);
    system("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
    system("$php $AR_ROOT/exec.status.php --process1 --force --verbose >/dev/null 2>&1");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("COMPILE_SQUID_TOKENS",serialize(array()));
    build_progress("{upgrade}  {{$product}} ($key)...", 70);
    shell_exec("$rm -f /etc/squid3/*.conf");
    system("$php /usr/share/artica-postfix/exec.squid.php --build --force --noverifacls");
    build_progress("{upgrade}  {{$product}} ($key)...", 71);
    system($reconfigure_squid);
    build_progress("{upgrade}  {{$product}} ($key)...", 72);
    system("$deny_final");
    build_progress("{upgrade}  {{$product}} ($key)...", 73);
    system($http_access);
    build_progress("{upgrade}  {{$product}} ($key)...", 74);
    system("$php $AR_ROOT/exec.squid.templates.php --perc");
    build_progress("{stopping_service}", 75);
    system("/usr/sbin/artica-phpfpm-service -stop-proxy");
    system("/usr/sbin/artica-phpfpm-service -stop-proxy");
    build_progress("{starting_service}", 76);
    $unix->go_exec("/usr/sbin/artica-phpfpm-service -start-proxy");
    system("$nohup /etc/init.d/monit start >/dev/null 2>&1 &");
    system("/usr/sbin/artica-phpfpm-service -process1 >/dev/null 2>&1");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("COMPILE_SQUID_TOKENS",serialize(array()));
    build_progress("{success} {{$product}} ($key)...", 100);
    return true;
}