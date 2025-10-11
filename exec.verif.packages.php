<?php
$GLOBALS["FORCE"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");


if(isset($argv[1])) {
    if ($argv[1] == "systemd-remove") {
        fix_systemd();
        exit;
    }
    if ($argv[1] == "--pin") {
        exit;
    }
    if ($argv[1] == "--checks") {
        CheckPackages_dashboard();
        exit;
    }
    if ($argv[1] == "--php") {
        Check_PHP();
        exit;
    }
}

ExecuteVerification();

function Check_PHP(){
    $MINZ=php_packages(array());
    foreach ($MINZ as $filetest=>$pak){
        if(!is_file($filetest)){
            echo "[".__LINE__."]: Must install \"$pak\"\n";

        }
    }
}

function build_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"verifpackages.progress");
}

function ExecuteVerification(){
    $unix=new unix();

    $apttime="/etc/artica-postfix/VERIF_PKG_TIME";
    $time=$unix->file_time_min($apttime);
    if(!$GLOBALS["FORCE"]) {
        if ($time < 2) {
            die();
        }
    }
    @unlink("/etc/artica-postfix/VERIF_PKG_TIME");
    @file_put_contents("/etc/artica-postfix/VERIF_PKG_TIME", time());
    build_progress(15,"{running} Fix Systemd");
    fix_systemd();
    build_progress(20,"{running}");
    $PKG=CheckPackages();

    if(is_file("/etc/group.lock")){ @unlink("/etc/group.lock"); }
    if(is_file("/etc/gshadow.lock")){ @unlink("/etc/gshadow.lock"); }
    if(is_file("/etc/passwd.lock")){ @unlink("/etc/passwd.lock"); }
    if(is_file("/etc/shadow.lock")){ @unlink("/etc/shadow.lock"); }


    $Count_source=count($PKG);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("VerifPackages",base64_decode(serialize($PKG)));
    $GLOBALS["OUTPUT"]=true;
    $prc=30;
    build_progress(30,"{starting}");
    if($Count_source>0) {
        foreach ($PKG as $package) {
            $prc=$prc+1;
            if($prc>90){$prc=90;}


            build_progress($prc,"{installing} $package");
            echo "[".__LINE__."]: Installing $package...\n";
            if(!$unix->DEBIAN_INSTALL_PACKAGE($package)){
                build_progress(110,"$package: {failed}");
                return false;
            }
            if($package=="mosquito"){
                if(is_file("/etc/init.d/mosquito")){
                    $unix->remove_service("/etc/init.d/mosquito");
                }
            }
            if($package=="udhcpd"){
                if(is_file("/etc/init.d/udhcpd")){
                    $unix->remove_service("/etc/init.d/udhcpd");
                }
            }

            if($package=="dante-server"){
                if(is_file("/etc/init.d/danted")){
                    $unix->remove_service("/etc/init.d/danted");
                }
            }

            if($package=="quagga-ospfd"){
                if(is_file("/etc/init.d/ospfd")){
                    $unix->remove_service("/etc/init.d/ospfd");
                }
            }
        }

    }


    $PKG=CheckPackages();
    $Count_final=count($PKG);
    $reste=$Count_source-$Count_final;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("VerifPackages",base64_encode(serialize($PKG)));

    if(count($PKG)>0){
        build_progress(110,"{checking} {failed}: " .@implode(" ",$PKG) );
    }else{
        build_progress(100,"{checking} {success}: " .@implode(" ",$PKG) );
    }

    $php = $unix->LOCATE_PHP5_BIN();
    if($reste<$Count_source) {
        shell_exec("/usr/share/artica-postfix/bin/articarest -deb-collection");
        system("$php /usr/share/artica-postfix/exec.pip.php --collection");
        shell_exec("/usr/share/artica-postfix/bin/articarest -phpini -debug");
        shell_exec("/etc/init.d/artica-phpfpm restart");
        shell_exec("/etc/init.d/artica-status restart");
    }
    shell_exec("$php /usr/share/artica-postfix/exec.status.php --process1 --force");
    fix_systemd();

}


function CheckPackages_dashboard():bool{
    $PKG=CheckPackages(true);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("VerifPackages",base64_encode(serialize($PKG)));
    return true;
}

function php_packages($MINZ):array{
    if(is_file("/usr/bin/php7.4")) {return $MINZ;}

    if(is_file("/usr/bin/php7.3")) {
        $MINZ["/usr/lib/php/20180731/memcached.so"] = "php7.3-memcached";
        $MINZ["/usr/lib/php/20180731/sqlite3.so"] = "php7.3-sqlite3";
        $MINZ["/usr/lib/php/20180731/ssh2.so"] = "php7.3-ssh2";
        $MINZ["/usr/lib/php/20180731/curl.so"] = "php7.3-curl";
        $MINZ["/usr/lib/php/20180731/mapi.so"] = "php-mapi";
        $MINZ["/usr/lib/php/20180731/zip.so"] = "php7.3-zip";
        $MINZ["/usr/lib/php/20180731/mysqli.so"] = "php7.3-mysql";
        return $MINZ;
    }

    return $MINZ;
}

function CheckPackages($onlycheck=false):array{
    $unix=new unix();

    if(is_file("/etc/artica-postfix/ARTICA_REVERSE_PROXY_APPLIANCE")){
        return array();
    }
    if(is_file("/etc/artica-postfix/ARTICA_SMTP_APPLIANCE")){
       return array();
    }
    system("/usr/share/artica-postfix/bin/articarest -apt-sources");
    $PKG = array();
    $python27="/usr/lib/python2.7/dist-packages";



    $DEBIAN_VERSION = $unix->DEBIAN_VERSION();

    if(is_dir("/usr/lib/x86_64-linux-gnu/perl5/5.28")){
        if(!is_file("/usr/lib/x86_64-linux-gnu/perl5/5.28/Text/CSV_XS.pm")) {
            $PKG[] = "libtext-csv-xs-perl";
        }
    }

    if(!is_file("/usr/bin/certtool")){
        $PKG[]="gnutls-bin";
    }

    if(!is_file("/usr/bin/zip")){
        $PKG[] = "zip";
    }
    if(!is_file("/usr/bin/sqlite3")){
        $PKG[] = "sqlite3";
    }

    if ($DEBIAN_VERSION < 10) {

        if (!is_file("$python27/OpenSSL/__init__.py")) {
            $PKG[] = "python-openssl";
        }

        if (!is_file("/usr/lib/x86_64-linux-gnu/liblmdb.so.0")){
            $PKG[] = "liblmdb0";
        }

        if (!is_file("/usr/lib/x86_64-linux-gnu/perl5/5.24/DBD/SQLite.pm")) {
            $PKG[] = "libdbd-sqlite3-perl";
        }
        if (!is_file("/usr/lib/x86_64-linux-gnu/libavcodec.so.57")) {
            $PKG[] = "libavcodec57";
        }
        if (!is_file("/usr/lib/x86_64-linux-gnu/libavformat.so.57")) {
            $PKG[] = "libavformat57";
        }
        if (!is_file("/usr/lib/x86_64-linux-gnu/libswscale.so.4")) {
            $PKG[] = "libswscale4";
        }
        if (!is_file("/usr/lib/libhs.so.4")) {
            $PKG[] = "libhyperscan4";
        }
        if (!is_file("/usr/lib/x86_64-linux-gnu/perl5/5.24/Encode/Detect.pm")) {
            $PKG[] = "libencode-detect-perl";
        }
        if (!is_file("/usr/lib/x86_64-linux-gnu/perl5/5.24/Net/Patricia.pm")) {
            $PKG[] = "libnet-patricia-perl";
        }
        if (!is_file("/usr/lib/x86_64-linux-gnu/liblua5.3.so.0")) {
            $PKG[] = "liblua5.3-0";
        }

        if (!is_file("/usr/lib/x86_64-linux-gnu/libmaxminddb.so.0")) {
            $PKG[] = "libmaxminddb0";
        }
        if (!is_file("/usr/bin/certbot")) {
            echo "Adding letsencrypt\n";
            $PKG[] = "letsencrypt";
        }
        if (!is_file("/usr/lib/x86_64-linux-gnu/libmaxminddb.so.0")) {
            $PKG[] = "libmaxminddb0";
        }
        if (!is_file("/usr/bin/mmdblookup")) {
            $PKG[] = "mmdb-bin";
        }
        if (!is_file("/usr/lib/php/20151012/mcrypt.so")) {
            $PKG[] = "php7.0-mcrypt";
        }
        if (!is_file("/usr/bin/php-cgi7.0")) {
            $PKG[] = "php7.0-cgi";
        }


    }

    $QEMU_HOST = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("QEMU_HOST"));
    $VMWARE_HOST = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_HOST"));
    $VMWARE_TOOLS_INSTALLED = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_TOOLS_INSTALLED"));

    if ($QEMU_HOST == 1) {
        $qemu_ga = $unix->find_program("qemu-ga");
        if (!is_file($qemu_ga)) {
            $PKG[] = "qemu-guest-agent";
        }
    }

    if ($VMWARE_HOST == 1) {
        if ($VMWARE_TOOLS_INSTALLED == 0) {
            $PKG[] = "open-vm-tools";
        }
    }

    echo "DEBIAN_VERSION=$DEBIAN_VERSION\n";
    if ($DEBIAN_VERSION < 10) {
        $MINZ["$python27/DNS/Base.py"] = "python-dns";
        $MINZ["/usr/sbin/fcgiwrap"]="fcgiwrap";
        $MINZ=php_packages($MINZ);
    }

    if ($DEBIAN_VERSION == 10) {
        // https://nsrc.org/workshops/2011/dakar-gestion-supervision-reseau/raw-attachment/wiki/Agenda/smokeping-vFR.pdf

        $MINZ["/usr/bin/spawn-fcgi"]                = "spawn-fcgi";
        $MINZ["/usr/sbin/smokeping"]                = "smokeping";
        $MINZ["/usr/sbin/aa-complain"]              = "apparmor-utils";
        $MINZ["/usr/sbin/arp-scan"]                 = "arp-scan";
        $MINZ["/usr/bin/lsb_release"]               = "lsb-release";
        $MINZ=php_packages($MINZ);
        $MINZ["/bin/nc.openbsd"]                    = "netcat-openbsd";
        $MINZ["/sbin/mount.cifs"]                   = "cifs-utils";
        $MINZ["/usr/lib/x86_64-linux-gnu/liblua5.2.so.0"]="liblua5.2-0";
        $MINZ["/usr/lib/x86_64-linux-gnu/libfuzzy.so.2"]="libfuzzy2";
        $MINZ["/usr/lib/x86_64-linux-gnu/libluajit-5.1.so.2"]="libluajit-5.1-2";
        $MINZ["/usr/lib/locale/fr_FR.utf8/LC_ADDRESS"]="locales-all";
        $MINZ["/usr/bin/mmdblookup"]="mmdb-bin";
        $MINZ["/usr/lib/x86_64-linux-gnu/libmaxminddb.so.0"]="libmaxminddb0";
        $MINZ["/usr/lib/x86_64-linux-gnu/libh2o-evloop.so"]="libh2o-evloop-dev";
        $MINZ["/usr/lib/x86_64-linux-gnu/libhs.so.5"]="libhyperscan5";
        $MINZ["/usr/lib/x86_64-linux-gnu/samba/ldb/acl.so"]="samba-dsdb-modules";
        $MINZ["/usr/lib/x86_64-linux-gnu/libbrotlicommon.so.1"]="libbrotli1";
        $MINZ["/usr/bin/pactester"]="libpacparser1";
        $MINZ["/sbin/setcap"]="libcap2-bin";
        $MINZ["/sbin/iscsiadm"]="open-iscsi";
        $MINZ["/usr/bin/telnet.netkit"]="telnet";
        $MINZ["/usr/bin/curlftpfs"]="curlftpfs";
        $MINZ["/usr/bin/sshfs"]="sshfs";
        $MINZ["/usr/bin/bdftopcf"]="xfonts-utils";
        $MINZ["/etc/X11/fonts/75dpi/xfonts-75dpi.alias"]="xfonts-75dpi";
        $MINZ["/etc/X11/fonts/misc/xfonts-base.alias"]="xfonts-base";
        $MINZ["/usr/share/fonts/X11/encodings/encodings.dir"]="xfonts-encodings";
        //$MINZ["/usr/bin/mosquitto_sub"]="mosquitto-clients";
        //$MINZ["/usr/sbin/mosquitto"]="mosquitto";
        $MINZ["/usr/bin/kinit"]="krb5-user";
        $MINZ["/usr/bin/msmtp"]="msmtp";
        $MINZ["/usr/bin/rrdtool"]="rrdtool";
        $MINZ["/usr/bin/namebench"]="namebench";
        $MINZ["/usr/bin/krenew"]="kstart";
        $MINZ["/usr/bin/unrar-nonfree"]="unrar";
        $MINZ["/usr/bin/7zr"]="p7zip";

        $EnableFreeRadius=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFreeRadius"));
        if($EnableFreeRadius==1) {
            $MINZ["/usr/bin/radtest"] = "freeradius-utils";
        }

        $MINZ["/usr/share/locale/fr/LC_MESSAGES/util-linux.mo"]="util-linux-locales";
        $MINZ["/usr/lib/x86_64-linux-gnu/libsnmp.so.30"]="libsnmp30";
        $MINZ["/usr/lib/x86_64-linux-gnu/libfontenc.so.1"]="libfontenc1";
        $MINZ["$python27/six.py"]="python-six";
        $MINZ["/usr/lib/locale/zu_ZA/LC_TIME"]="locales-all";

        // RDP PROXY
        $MINZ["/usr/lib/python3/dist-packages/phpserialize.py"]="python3-phpserialize";
        $MINZ["/usr/lib/python3/dist-packages/netaddr/__init__.py"]="python3-netaddr";
        $MINZ["/usr/lib/python3/dist-packages/ldap/__init__.py"]="python3-ldap";
        $MINZ["/usr/lib/python3/dist-packages/DNS/__init__.py"]="python3-dns";
        $MINZ["/usr/lib/python3/dist-packages/geoip2/database.py"]="python3-geoip2";

        $MINZ["$python27/redis/client.py"]="python-redis";
        $MINZ["$python27/chardet/__init__.py"]="python-chardet";
        $MINZ["$python27/DNS/Base.py"]="python-dns";
        $MINZ["$python27/apt/__init__.py"]="python-apt";
        $MINZ["$python27/click/__init__.py"]="python-click";
        $MINZ["$python27/oauthlib/__init__.py"]="python-oauthlib";
        $MINZ["/usr/share/locale/zh_TW/LC_MESSAGES/python-apt.mo"]="python-apt-common";
        $MINZ["$python27/SOAPpy/Client.py"]="python-soappy";
        $MINZ["$python27/requests_oauthlib/__init__.py"]="python-requests-oauthlib";
        $MINZ["$python27/pywebdav/__init__.py"]="python-webdav";
        $MINZ["$python27/flask/__init__.py"]="python-flask";
        $MINZ["$python27/flask_sqlalchemy/__init__.py"]="python-flask-sqlalchemy";
        $MINZ["$python27/jinja2/__init__.py"]="python-jinja2";
        $MINZ["$python27/itsdangerous.py"]="python-itsdangerous";
        $MINZ["$python27/docutils/__init__.py"]="python-docutils";
        $MINZ["$python27/setuptools/__init__.py"]="python-setuptools";
        $MINZ["$python27/flask_wtf/__init__.py"]="python-flaskext.wtf";
        $MINZ["$python27/markupsafe/__init__.py"]="python-markupsafe";
        $MINZ["$python27/migrate/__init__.py"]="python-migrate";
        $MINZ["$python27/sqlalchemy/__init__.py"]="python-sqlalchemy";
        $MINZ["$python27/cffi/__init__.py"]="python-cffi";
        $MINZ["$python27/wtforms/__init__.py"]="python-wtforms";
        $MINZ["$python27/yaml/__init__.py"]="python-yaml";
        $MINZ["$python27/ply/__init__.py"]="python-ply";
        $MINZ["$python27/pycparser/__init__.py"]="python-pycparser";
        $MINZ["$python27/werkzeug/__init__.py"]="python-werkzeug";
        $MINZ["$python27/bottle.py"]="python-bottle";
        $MINZ["$python27/graphy/__init__.py"]="python-graphy";
        $MINZ["$python27/debian/__init__.py"]="python-debian";
        $MINZ["$python27/chardet/__init__.py"]="python-chardet";
        $MINZ["$python27/OpenSSL/__init__.py"]="python-openssl";
        $MINZ["$python27/bcrypt/__init__.py"]="python-bcrypt";
        $MINZ["$python27/cryptography/__init__.py"]="python-cryptography";
        $MINZ["$python27/tdb.x86_64-linux-gnu.so"]="python-tdb";
        $MINZ["$python27/pyroute2/__init__.py"]="python-pyroute2";
        $MINZ["$python27/iptc/__init__.py"]="python-iptables";
        $MINZ["$python27/paramiko/__init__.py"]="python-paramiko";
        $MINZ["$python27/pyotp/__init__.py"]="python-pyotp";
        $MINZ["$python27/geoip2/database.py"]="python-geoip2";

        $MINZ["/usr/lib/python3/dist-packages/bottle.py"]="python3-bottle";
        $MINZ["/lib/firmware/rtlwifi/rtl8188efw.bin"]="firmware-realtek";
        $MINZ["/usr/sbin/iucode-tool"]="iucode-tool";
        $MINZ["/etc/kernel/preinst.d/intel-microcode"]="intel-microcode";
        $MINZ["/usr/sbin/mount.davfs"]="davfs2";
        $MINZ["/usr/bin/pon"]="ppp";
        $MINZ["/usr/lib/x86_64-linux-gnu/liblua5.3.so.0"]="liblua5.3-0";
        $MINZ["/usr/sbin/needrestart"]="needrestart";
        $MINZ["/usr/bin/google-authenticator"]="libpam-google-authenticator";
        $MINZ["/usr/sbin/udhcpd"]="udhcpd";
        $MINZ["/usr/sbin/ospfd"]="quagga-ospfd";
        $MINZ["/usr/sbin/parprouted"]="parprouted";
        $MINZ["/usr/bin/apt-mirror"]="apt-mirror";

        // NetData
        $NetDATAEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetDATAEnabled"));
        if($NetDATAEnabled==1) {
            $MINZ["/usr/lib/x86_64-linux-gnu/libmongoc-1.0.so.0"] = "libmongoc-1.0-0";
            $MINZ["/usr/lib/x86_64-linux-gnu/libJudy.so.1"] = "libjudydebian1";
        }

        foreach ($MINZ as $filetest=>$pak){
            if(!is_file($filetest)){
                echo "[".__LINE__."]: Must install \"$pak\"\n";
                $PKG[] =$pak;
            }
        }



    }
    if ($DEBIAN_VERSION == 10) {
        if (!is_file("/lib/firmware/e100/d101m_ucode.bin")) {
            $PKG[] = "firmware-misc-nonfree";
        }
        if (!is_file("/lib/firmware/iwlwifi-100-5.ucode")) {
            $PKG[] = "firmware-iwlwifi";
        }
        if (!is_file("/usr/lib/x86_64-linux-gnu/libmilter.so.1.0.1")) {
            $PKG[] = "libmilter1.0.1";
        }
        if (!is_file("/usr/lib/x86_64-linux-gnu/libuuid.so")) {
            $PKG[] = "uuid-dev";
        }
        if (!is_file("/usr/lib/x86_64-linux-gnu/libmnl.so.0")) {
            $PKG[] = "libmnl0";
        }
        if (!is_file("/usr/lib/x86_64-linux-gnu/libz.so")) {
            $PKG[] = "zlib1g-dev";
        }
        if (!is_file("/usr/share/perl5/Net/CIDR/Lite.pm")) {
            $PKG[] = "libnet-cidr-lite-perl";
        }
        if (!is_file("/usr/lib/apt/methods/https")) {
            $PKG[] = "apt-transport-https";
        }
        if (!is_file("/usr/bin/schedtool")) {
            $PKG[] = "schedtool";
        }
        if (!is_file("/usr/bin/getdns_query")) {
            $PKG[] = "getdns-utils";
        }
        if (!is_file("/usr/bin/msmtp")) {
            $PKG[] = "msmtp";
        }
        if (!is_file("/usr/bin/zip")) {
            $PKG[] = "zip";
        }

        $PostfixEnable = intval($GLOBALS['CLASS_SOCKETS']->GET_INFO("EnablePostfix"));
        if ($PostfixEnable == 1) {
            $sacompile = $unix->find_program("sa-compile");
            if (!is_file($sacompile)) {
                $PKG[] = "sa-compile";
            }
            if (!is_file("/usr/share/perl5/Razor2/Client/Agent.pm")) {
                $PKG[] = "razor";
            }
        }
    }

    if (count($PKG) == 0) {
        return array();
    }


    return $PKG;

}



function fix_systemd(){
    $MultiPath="/etc/systemd/system/multi-user.target.wants";
    $Sysinit="/etc/systemd/system/sysinit.target.wants";
    $sys="/etc/systemd/system";
    if(!is_file("/usr/bin/systemctl")){return false;}

    $f[]="firehol";
    $f[]="ModemManager";
    $f[]="glances";
    $f[]="mariadb";
    $f[]="nmbd";
    $f[]="postfix";
    $f[]="redis-server";
    $f[]="autofs";
    $f[]="krb5-kdc";
    $f[]="ntp";
    $f[]="pppd-dns";
    $f[]="redsocks";
    $f[]="smartd";
    $f[]="vnstat";
    $f[]="avahi-daemon";
    $f[]="cyrus-imapd";
    $f[]="lighttpd";
    $f[]="mosquitto";
    $f[]="opendkim";
    $f[]="prads";
    $f[]="remote-fs.target";
    $f[]="smbd";
    $f[]="winbind";
    $f[]="conntrackd";
    $f[]="fireqos";
    $f[]="lm-sensors";
    $f[]="munin-node";
    if(is_file("/usr/bin/php7.3")) {
        $f[] = "php7.3-fpm";
    }
    $f[]="privoxy";
    $f[]="rsync";
    $f[]="snmpd";
    $f[]="wpa_supplicant";

    foreach ($f as $package){
        $path="$MultiPath/$package.service";
        if(is_file($path)){
            echo "Removing SystemCTL $package";
            shell_exec("/usr/bin/systemctl disable $package >/dev/null 2>&1");
        }
    }

    $f=array();
    $f[]="open-iscsi";
    $f[]="iscsid";

    foreach ($f as $package){
        $path="$Sysinit/$package.service";
        if(is_file($path)){
            echo "Removing SystemCTL $package";
            shell_exec("/usr/bin/systemctl disable $package >/dev/null 2>&1");
        }
    }

    $f=array();
    $f[]="kibana";
    $f[]="mysql";
    $f[]="mysqld";
    $f[]="redis";
    $f[]="samba-ad-dc";
    $f[]="vnstatd";


    foreach ($f as $package){
        $path="$sys/$package.service";
        if(!is_file($path)){continue;}
        echo "Removing SystemCTL $package";
        shell_exec("/usr/bin/systemctl disable $package >/dev/null 2>&1");

    }

    $f=array();
    $f[]="cryptdisks";
    $f[]="memcached";
    $f[]="munin-node";
    $f[]="krb5-kdc";
    $f[]="pppd-dns";
    $f[]="vgauth";
    $f[]="firehol";
    $f[]="wpa_supplicant@";
    $f[]="open-iscsi";
    $f[]="quotarpc";
    $f[]="umountnfs";
    $f[]="mosquitto";
    $f[]="lighttpd";
    $f[]="ModemManager";
    $f[]="motd";
    $f[]="postfix@";
    $f[]="redis-server@";
    $f[]="smartmontools";
    $f[]="ssh";
    if(is_file("/usr/bin/php7.3")) {
        $f[] = "php7.3-fpm";
    }
    if(is_file("/usr/bin/php7.4")) {
        $f[] = "php7.4-fpm";
    }

    $f[]="snmpd";
    $f[]="conntrackd";
    $f[]="iio-sensor-proxy";
    $f[]="wpa_supplicant";
    $f[]="smbd";
    $f[]="lm-sensors";
    $f[]="spamassassin";
    $f[]="ntp";
    $f[]="smartd";
    $f[]="mailgraph";
    $f[]="opendkim";
    $f[]="redsocks";
    $f[]="freeradius";
    $f[]="privoxy";
    $f[]="iscsid";
    $f[]="wpa_supplicant-nl80211@";
    $f[]="mariadb";
    $f[]="vnstat";
    $f[]="zabbix-agent";
    $f[]="rsync";
    $f[]="prads";
    $f[]="mariadb@";
    $f[]="nmbd";
    $f[]="quota";
    $f[]="winbind";
    $f[]="glances";
    $f[]="fstrim";
    $f[]="postfix";
    $f[]="fireqos";

    foreach ($f as $package){
        if(!is_file("/usr/lib/systemd/$package.service")){continue;}
        echo "Removing SystemCTL $package";
        shell_exec("/usr/bin/systemctl disable $package >/dev/null 2>&1");
    }



return true;

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



?>