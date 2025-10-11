#!/bin/bash
INPUT=/tmp/menu.sh.$$
OUTPUT=/tmp/output.sh.$$
trap "rm -f $OUTPUT >/dev/null 2>&1; rm -f $INPUT >/dev/null 2>&1; exit" SIGHUP SIGINT SIGTERM
DIALOG=${DIALOG=dialog}
# Using now the 4.40.0000

if [ ! -f /usr/bin/dialog ]
then
  apt-get install -f dialog
fi

if [ ! -f /usr/bin/dialog ]
then
  echo "Dialog not found, aborting..."
  exit 0
fi
if [ ! -f /root/.InstallAnswerYes ]
then
  /usr/bin/dialog --title "Artica Installation" --yesno "This operation will transform definitively this server to an Artica appliance\nAre you sure to perform this operation\nPress 'Yes' to continue, or 'No' to exit" 0 0
  return_value=$?
  case $return_value in
    1)
    echo "Aborting"
    exit
    return;;
  esac
  touch /root/.InstallAnswerYes
fi

if [ ! -f /root/.APT_GET_UPDATE.TXT ]
then
  echo 50| dialog --title "Updating repository" --gauge "Please wait..." 6 80
  apt-get update >/root/.APT_GET_UPDATE.TXT
  echo 100| dialog --title "Updating repository DONE" --gauge "Please wait..." 6 80
fi




PACKAGES=("php7.3-common" "php7.3-dev" "php7.3-dba" "php7.3-mysql" "php7.3-pgsql" "php7.3-xml" "php7.3-imap" "php7.3-fpm" "php7.3-cli" "php7.3-xmlrpc" "php7.3-ldap" "php7.3-snmp" "php7.3-gd" "php7.3-pspell" "php7.3-mbstring" "php7.3-curl" "php7.3-sqlite3" "php-geoip" "php-uploadprogress" "php-uuid" "php-pear" "php-redis" "php-msgpack" "php-igbinary" "php-memcached" "libhdb9-heimdal"  "curl" "sqlite3" "winbind" "targetcli-fb" "libzmq5" "net-tools" "prads" "argus-client" "argus-server" "wireless-tools" "iw" "wpasupplicant" "proftpd-basic" "proftpd-mod-ldap" "proftpd-mod-mysql" "proftpd-mod-sqlite" "dhcpd-pools" "glances" "uuid-dev" "libmnl-dev" "apt-transport-https" "mariadb-client" "mariadb-server" "poppler-utils" "antiword" "catdoc" "fusesmb" "cabextract" "xmlstarlet" "trash-cli" "hashdeep" "aria2" "libxau6" "libonig5" "libhiredis0.14" "ipset" "libreswan" "python3-bottle" "python3-memcache" "python-ply" "python-yaml" "python-mysqldb" "python-psycopg2" "python-talloc" "python-bottle" "python-pycurl" "python-pymongo" "python-lxml" "python-dev" "python" "python-apt" "python-ldap" "python-openssl" "python-requests" "python-ipaddress" "python-bcrypt" "python-cryptography" "python-daemon" "python-dateutil" "python-cherrypy3" "python-memcache" "namebench" "python-dnspython" "python-simplejson" "python-docutils" "python-configobj" "python-oauthlib" "python-migrate" "python-markupsafe" "python-sqlalchemy" "python-cffi" "python-phpserialize" "python-six" "python-click" "python-idna" "python-psutil" "python-netaddr" "python-apt-common" "python-chardet" "python-debian" "python-debianbts" "python-fpconst" "python-minimal" "python-itsdangerous" "python-jinja2" "python-soappy" "libsnmp30" "python-flask" "python-lockfile" "python-flask-sqlalchemy" "python-flaskext.wtf" "python-webdav" "python-requests-oauthlib" "python-virtualenv" "python-netifaces" "python-iniparse" "python-setuptools" "python-socks" "virtualenv-clone" "python-werkzeug" "python-wtforms" "python-pyasn1" "python-redis" "python-pycparser" "python-enum34" "python-httplib2" "python-graphy" "python2.7-minimal" "kbd" "libdumbnet1" "liblz4-dev" "liblz4-1" "libpacparser1" "libnss3" "libjansson4"  "libcrypt-ssleay-perl"  "libprotobuf-c1" "libfstrm0" "libnuma-dev" "saidar" "dialog" "privoxy" "libpq5" "tcpdump" "arp-scan" "rsync" "cgroup-bin" "ipcalc" "wakeonlan" "syslinux" "ntp" "expect" "siege" "ethtool" "lm-sensors" "autofs" "open-iscsi" "samba" "samba-dsdb-modules" "libhyperscan5" "libjudydebian1" "smartmontools" "redis-server" "libcap2-bin" "libdb-dev" "libunix-syslog-perl" "libsendmail-pmilter-perl" "libmail-imapclient-perl" "libsnappy1v5" "libavcodec58" "libavformat58" "libswscale5" "libc++1" "libc++abi1" "apache2-utils" "strace" "ebtables" "whois" "iotop" "lshw" "acl" "socat" "pst-utils"  "mlocate" "libmaxminddb0" "mmdb-bin" "libenchant1c2a" "pv" "arj" "bridge-utils" "build-essential" "memcached" "byacc" "cifs-utils" "curlftpfs" "davfs2" "discover" "rsyslog" "rsyslog-gnutls" "dnsutils" "flex" "freeradius-krb5" "freeradius-ldap" "freeradius-mysql" "ftp-proxy" "g++" "gcc" "htop" "ifenslave" "iptables-dev" "iputils-arping" "isc-dhcp-client" "krb5-user" "krb5-config" "krb5-kdc" "mingetty" "getdns-utils" "fping" "lighttpd" "locales" "locales-all" "util-linux-locales" "lsof" "make" "ntpdate" "openssh-client" "openssh-server" "openssl" "msmtp" "bandwidthd" "rdate" "rrdtool" "sasl2-bin" "scons" "slapd" "sshfs" "tcsh" "telnet" "ucarp" "unzip" "vde2" "vnstat" "vnstati" "munin" "munin-node" "munin-plugins-extra" "libcdb-dev" "libxslt1-dev" "libluajit-5.1-2" "apt-file" "hdparm" "conntrack" "conntrackd" "attr" "quota" "libnetfilter-conntrack3" "netdiscover" "redsocks" "postfix-policyd-spf-python" "libnet-netmask-perl" "libnet-ip-perl" "librrds-perl" "libio-stringy-perl" "libmime-tools-perl" "libnet-server-perl" "libnet-ldap-perl" "libnet-dns-perl" "libnet-dns-resolver-programmable-perl" "libconvert-asn1-perl" "libconvert-uulib-perl" "libcache-cache-perl" "libnet-cidr-lite-perl" "libencode-detect-perl" "libtext-csv-xs-perl" "libnet-patricia-perl" "razor" "libmime-encwords-perl" "spamassassin" "libberkeleydb-perl" "libxml-namespacesupport-perl" "libxml-sax-perl" "libxml-sax-writer-perl" "libxml-filter-buffertext-perl" "libclass-dbi-pg-perl" "libmail-dkim-perl" "libdigest-sha-perl" "libmail-spf-perl" "libnetaddr-ip-perl" "libsys-hostname-long-perl" "libarchive-zip-perl" "libconvert-tnef-perl" "libgssapi-perl" "libfile-tail-perl" "libical3" "libsasl2-dev" "libmilter-dev" "stunnel4" "libspf2-dev" "mailgraph" "mhonarc" "opendkim" "opendkim-tools" "offlineimap" "libdkim-dev" "altermime" "pax" "re2c" "procmail" "liblzo2-2" "ruby" "dh-autoreconf" "libpcap-dev" "libmagic-dev" "libgd3" "python-pip" "snmp" "snmpd" "ripmime" "spfquery" "libspf2-2" "liblzo2-dev" "libdbus-1-dev" "libnetfilter-conntrack-dev" "less" "libtinfo5" "libacl1" "libalgorithm-diff-perl" "libgd-graph-perl" "libalgorithm-diff-xs-perl" "libalgorithm-merge-perl" "libapache2-mod-bw" "libaprutil1" "libaprutil1-dbd-sqlite3" "libaprutil1-ldap" "libapt-inst2.0" "libapt-pkg-perl" "libapt-pkg5.0" "libasprintf0v5" "libpam0g-dev" "libattr1" "libaudit1" "libavahi-client3" "libavahi-common-data" "libavahi-common3" "libavahi-core7" "libbind9-161" "libblkid1" "libbsd-resource-perl" "libbsd0" "libbz2-1.0" "libbz2-dev" "libc-bin" "libc-client2007e" "libc-dev-bin" "libc6-dev" "libc6" "libcairo2" "libcdio18" "libclass-isa-perl" "libcomerr2" "libconfuse-common" "libcurl4" "libcwidget3v5" "libdaemon0" "libdatrie1" "libdb5.3" "libdbi-perl" "libdbi1" "libdbus-1-3" "libdevel-symdump-perl" "libdevmapper-event1.02.1" "libdevmapper1.02.1" "libdiscover2" "libdns1104" "libdpkg-perl" "libdrm-intel1" "libdrm-dev" "libdrm-nouveau2" "libdrm-radeon1" "libdrm2" "libedit2" "libencode-locale-perl" "libept1.5.0" "libev4" "libevent-2.1-6" "libexpat1" "libfam0" "libffi-dev" "libffi6" "libdbd-pg-perl" "libfile-fcntllock-perl" "libfile-listing-perl" "libfont-afm-perl" "libfreetype6" "libgc1c2" "libgcc1" "libgcrypt20-dev" "libgcrypt20" "libgdbm6" "libgeoip1" "libgeoip-dev" "libcurl4-openssl-dev" "libgif7" "libglib2.0-0" "libglib2.0-data" "libgmp10" "libgnutls28-dev" "libgnutls-openssl27" "libgomp1" "libgpg-error-dev" "libgpg-error0" "libgpgme11" "libgpm2" "libgraph-perl" "libgssapi-krb5-2" "libgssglue1" "libgssrpc4" "libheap-perl" "libice6" "libidn11-dev" "libidn11" "libisc1100" "libisccc161" "libisccfg163" "libitm1" "libjpeg62-turbo-dev" "libjpeg62-turbo" "libk5crypto3" "libkadm5clnt-mit11" "libkadm5srv-mit11" "libkeyutils1" "libklibc" "libkmod2" "libkrb5support0" "libldap-2.4-2" "liblocale-gettext-perl" "liblockfile-bin" "liblockfile1" "libltdl-dev" "libltdl7" "liblwp-mediatypes-perl" "liblwp-protocol-https-perl" "liblwres161" "liblzma5" "libmagic1" "libmailtools-perl" "libmcrypt4" "libmount1" "libmpc3" "libmpfr6" "libncurses5" "libncursesw5" "libneon27-gnutls" "libnet-daemon-perl" "libnet-http-perl" "libnetfilter-queue-dev" "libnetfilter-queue1" "libnet1" "libnewt0.52" "libnfnetlink0" "libnfsidmap2" "libnids1.21" "libntlm0" "libodbc1" "libp11-kit-dev" "libp11-kit0" "libpam-modules-bin" "libpam-runtime" "libpam-winbind" "libpam0g" "libpango1.0-0" "libpci3" "libpciaccess0" "libpcre3" "libpipeline1" "libpixman-1-0" "libpopt0" "libprocps7" "libpth20" "libpython2.7" "libpython2.7-stdlib" "libqdbm14" "libquadmath0" "libreadline-dev" "libreadline5" "libreadline7" "librrd8" "librtmp-dev" "librtmp1" "libruby2.5" "libsasl2-2" "libselinux1" "libsemanage-common" "libsemanage1" "libsepol1-dev" "libsepol1" "libsigc++-2.0-0v5" "libslang2" "bind9utils" "libpcap0.8-dev" "liblua5.3-dev" "lua-socket" "libsnmp-dev" "libsodium-dev" "libboost-context1.67.0" "libboost-system1.67.0" "libboost-thread1.67.0" "libboost-program-options1.67.0" "libh2o-evloop-dev" "libsm6" "libsocket-perl" "libsqlite3-0" "libss2" "libssh2-1-dev" "libssh2-1" "libssl-doc" "libssl1.1" "libstdc++-8-dev" "libstdc++6" "libsysfs2" "libtasn1-6" "libtdb1" "libcache-memcached-perl" "libdbd-sqlite3-perl" "liberror-perl" "libthai-data" "libthai0" "libtimedate-perl" "libtinfo-dev" "libtirpc3" "libtokyocabinet9" "libudev1" "liburi-perl" "libusb-0.1-4" "libusb-1.0-0" "libustr-1.0-1" "libuuid1" "libv4lconvert0" "libvde0" "libvdeplug2" "libverto-libev1" "libverto1" "libwbclient0" "libwrap0" "libwww-perl" "libwww-robotrules-perl" "libx11-6" "libx11-data" "libxapian30" "libxaw7" "libxcb-render0" "libxcb-shm0" "libxcb1" "libxcomposite1" "libxdamage1" "libxdmcp6" "libxext6" "libxfixes3" "libxft2" "libxkbfile1" "libxml2-dev" "libxml2" "libxmu6" "libxmuu1" "libxpm4" "libxrandr2" "libxrender1" "libxt6" "libyaml-0-2" "lib32gcc1" "lib32ncurses6" "lib32stdc++6" "lib32z1" "libaio1" "libapr1" "libattr1-dev" "libauthen-sasl-perl" "libc6-i386" "libcap2" "libcdio-dev" "libconfuse2" "libcrypt-openssl-random-perl" "libcups2" "libdbd-mysql-perl" "libfuse-dev" "libfuse2" "libgeo-ip-perl" "libgsasl7" "libiodbc2" "libkrb5-3" "libkrb5-dev" "libldap2-dev" "libmcrypt-dev" "libmhash2" "libnss-ldap" "libnss-mdns" "libpam-krb5" "libpam-ldap" "libpam-modules" "libpcrecpp0v5" "libperl-dev" "librrdp-perl" "libsasl2-modules" "libsasl2-modules-gssapi-mit" "libsasl2-modules-ldap" "libselinux1-dev" "libssl-dev" "libtevent0" "libtalloc2" "libusb-dev" "libv4l-0" "libwrap0-dev" "libgsasl7-dev" "libblkid-dev" "libcap-dev" "libtevent-dev" "httrack" "vlan" "libpcre3-dev" "wget" "udev" "usbutils" "runit-init" )

TARGETS_INIT=( "exim4" "dnsmasq" "smartmontools" "lm-sensors" "nscd" "iscsid" "rsync" "ftp-proxy" "conntrackd" "vnstat" "redis-server" "winbind" "autofs" "isc-dhcp-server" "irqbalance" "transmission-daemon" "mimedefang" "open-iscsi" "clamav-daemon" "clamav-freshclam" "smbd" "freeradius" "proftpd" "opendkim" "cyrus-imapd" "postfix" "ziproxy" "x11-common" "nmbd" "clamav-freshclam" "spamassassin" "spamass-milter" "spamassassin" "ntp" "nscd" "nfs-common" "stunnel4" "mysql" ,"privoxy","brightness","redsocks" "prads" "pads" "mailgraph" "pdns-recursor" "quota" "quotarpc" "samba" "tor" "l7filter" "firehol" "fail2ban" "samba-ad-dc" "rpcbind" "avahi-daemon" "unbound"  "squid" "squid3" "open-vm-tools" "filebeat","dbus","lvm2", "lvm2-lvmpolld" ,"krb5-kdc" ,"privoxy" ,"redsocks","snmpd" )

LAST_INIT=("privoxy" "elasticsearch" "kibana" "lighttpd" "redsocks" "apache2" "apache-htcacheclean" "arpd" "bandwidthd" "collectd" "php5-fcgi" "php7.3-fpm" "lighttpd")
TARGETS_FREEZE=( "slapd" "samba" "winbind" "samba-common" "squid" "squid3" "squid3-common" "postfix" "exim4-base" "exim4-daemon-light" "exim4" "xmail" "python-unbound" "python3-unbound" "unbound")


  /usr/bin/dialog --title "light Installation Mode" --yesno "Do you want to install Artica in light mode ?\n Light mode will install only necessaries to make artica run\nArtica will install other packages later depends on what you need to install inside the web console.\nThis operation is recommended on a VPS server.\n\nIf you want to install 'light package' Press 'Yes', or 'No' to continue in 'normal mode'" 0 0
  return_value=$?
  case $return_value in
    0)
    touch /tmp/.lightpackage
    ;;

    1)
      rm -f /tmp/.lightpackage
    ;;
  esac

if [ -f /tmp/.lightpackage ]
then
 PACKAGES=("php7.3-common" "php7.3-dev" "php7.3-dba" "php7.3-mysql" "php7.3-pgsql" "php7.3-xml" "php7.3-imap" "php7.3-fpm" "php7.3-cli" "php7.3-xmlrpc" "php7.3-ldap" "php7.3-snmp" "php7.3-gd" "php7.3-pspell" "php7.3-mbstring" "php7.3-curl" "php7.3-sqlite3" "php-geoip" "php-uploadprogress" "php-uuid" "php-pear" "php-redis" "php-msgpack" "php-igbinary" "php-memcached" "libhdb9-heimdal" "curl" "sqlite3" "net-tools" "glances" "uuid-dev" "apt-transport-https" "ipset" "python3-bottle" "python3-memcache" "python-ply" "python-yaml" "python-mysqldb" "python-psycopg2" "python-talloc" "python-bottle" "python-pycurl" "python-pymongo" "python-lxml" "python-dev" "python" "python-apt" "python-ldap" "python-openssl" "python-requests" "python-ipaddress" "python-bcrypt" "python-cryptography" "python-daemon" "python-dateutil" "python-cherrypy3" "python-memcache" "namebench" "python-dnspython" "python-simplejson" "python-docutils" "python-configobj" "python-oauthlib" "python-migrate" "python-markupsafe" "python-sqlalchemy" "python-cffi" "python-phpserialize" "python-six" "python-click" "python-idna" "python-psutil" "python-netaddr" "python-apt-common" "python-chardet" "python-debian" "python-debianbts" "python-fpconst" "python-minimal" "python-itsdangerous" "python-jinja2" "python-soappy" "libsnmp30" "python-flask" "python-lockfile" "python-flask-sqlalchemy" "python-flaskext.wtf" "python-webdav" "python-requests-oauthlib" "python-virtualenv" "python-netifaces" "python-iniparse" "python-setuptools" "python-socks" "virtualenv-clone" "python-werkzeug" "python-wtforms" "python-pyasn1" "python-redis" "python-pycparser" "python-enum34" "python-httplib2" "python-graphy" "python2.7-minimal"  "tcpdump" "arp-scan" "rsync" "cgroup-bin" "ipcalc" "ntp" "expect" "ethtool" "autofs" "redis-server" "libcap2-bin" "libunix-syslog-perl" "strace" "whois" "iotop" "lshw" "acl" "socat" "mlocate" "libmaxminddb0" "mmdb-bin" "memcached" "curlftpfs" "davfs2" "discover" "rsyslog" "rsyslog-gnutls" "dnsutils" "htop" "ifenslave" "iputils-arping"  "mingetty" "getdns-utils" "fping" "lighttpd" "locales" "locales-all" "util-linux-locales" "lsof" "ntpdate" "openssh-client" "openssh-server" "openssl" "msmtp" "bandwidthd" "rdate" "rrdtool" "sasl2-bin" "scons" "slapd" "sshfs" "tcsh" "telnet" "ucarp" "unzip" "vde2" "vnstat" "vnstati" "munin" "munin-node" "munin-plugins-extra"  "apt-file" "hdparm" "conntrack" "conntrackd" "attr" "quota" "libnetfilter-conntrack3" "netdiscover"  "libnet-netmask-perl" "libnet-ip-perl" "librrds-perl" "libio-stringy-perl" "libmime-tools-perl" "libnet-server-perl" "libnet-ldap-perl" "libnet-dns-perl" "libnet-dns-resolver-programmable-perl" "libconvert-asn1-perl" "libconvert-uulib-perl" "libcache-cache-perl" "libnet-cidr-lite-perl" "libencode-detect-perl" "libtext-csv-xs-perl" "libnet-patricia-perl" "razor" "libmime-encwords-perl" "libberkeleydb-perl" "libxml-namespacesupport-perl" "libxml-sax-perl" "libxml-sax-writer-perl" "libxml-filter-buffertext-perl" "libclass-dbi-pg-perl" "libmail-dkim-perl" "libdigest-sha-perl" "libmail-spf-perl" "libnetaddr-ip-perl" "libsys-hostname-long-perl" "libarchive-zip-perl" "libconvert-tnef-perl" "libgssapi-perl" "libfile-tail-perl" "libical3" "stunnel4"  "liblzo2-2" "ruby" "dh-autoreconf" "libgd3" "python-pip" "snmp" "snmpd" "less" "libacl1" "libalgorithm-diff-perl" "libgd-graph-perl" "libalgorithm-diff-xs-perl" "libalgorithm-merge-perl" "libapache2-mod-bw" "libaprutil1" "libaprutil1-dbd-sqlite3" "libaprutil1-ldap" "libapt-inst2.0" "libapt-pkg-perl" "libapt-pkg5.0" "libasprintf0v5" "libattr1"  "libblkid1" "libbsd-resource-perl" "libbsd0" "libbz2-1.0"  "libcairo2" "libcdio18" "libclass-isa-perl" "libcomerr2" "libconfuse-common" "libcurl4" "libcwidget3v5" "libdaemon0" "libdatrie1" "libdb5.3" "libdbi-perl" "libdbi1" "libdbus-1-3" "libdevel-symdump-perl" "libdevmapper-event1.02.1" "libdevmapper1.02.1" "libdiscover2" "libdns1104" "libdpkg-perl" "libdrm-intel1" "libedit2" "libencode-locale-perl" "libept1.5.0" "libev4" "libevent-2.1-6" "libexpat1" "libfam0" "libffi6" "libdbd-pg-perl" "libfile-fcntllock-perl" "libfile-listing-perl" "libfont-afm-perl" "libfreetype6" "libgc1c2" "libgcc1" "libgcrypt20" "libgdbm6" "libgeoip1" "libgif7" "libglib2.0-0" "libglib2.0-data" "libgmp10" "libgnutls-openssl27" "libgomp1" "libgpg-error0" "libgpgme11" "libgpm2" "libgraph-perl"  "libgssglue1" "libgssrpc4" "libheap-perl" "libice6" "libisccfg163" "libitm1" "libjpeg62-turbo" "libk5crypto3" "libkadm5clnt-mit11" "libkadm5srv-mit11" "libkeyutils1" "libklibc" "libkmod2"  "liblocale-gettext-perl" "liblockfile-bin" "liblockfile1" "libltdl7" "liblwp-mediatypes-perl" "liblwp-protocol-https-perl" "liblwres161" "liblzma5" "libmagic1" "libmailtools-perl" "libmcrypt4" "libmount1" "libmpc3" "libmpfr6" "libncurses5" "libncursesw5" "libneon27-gnutls" "libnet-daemon-perl" "libnet-http-perl"  "libnetfilter-queue1" "libnet1" "libnewt0.52" "libnfnetlink0" "libnfsidmap2" "libnids1.21" "libntlm0" "libodbc1"  "libp11-kit0" "libpam-modules-bin" "libpam-runtime" "libpam0g" "libpango1.0-0" "libpci3" "libpciaccess0" "libpcre3" "libpipeline1" "libpixman-1-0" "libpopt0" "libprocps7" "libpth20" "libpython2.7" "libpython2.7-stdlib" "libqdbm14" "libquadmath0" "librrd8"  "librtmp1" "libruby2.5" "libsasl2-2" "libselinux1" "libsemanage-common" "libsemanage1" "libslang2" "bind9utils" "lua-socket" "libboost-context1.67.0" "libboost-system1.67.0" "libboost-thread1.67.0" "libboost-program-options1.67.0" "libsm6" "libsocket-perl" "libsqlite3-0" "libss2"  "libssh2-1" "libssl-doc" "libssl1.1" "libstdc++6" "libsysfs2" "libtasn1-6" "libtdb1" "libcache-memcached-perl" "libdbd-sqlite3-perl" "liberror-perl" "libthai-data" "libthai0" "libtimedate-perl"  "libtirpc3" "libtokyocabinet9" "libudev1" "liburi-perl" "libusb-0.1-4" "libusb-1.0-0" "libustr-1.0-1" "libuuid1" "libv4lconvert0" "libvde0" "libvdeplug2" "libverto-libev1" "libverto1" "libwbclient0" "libwrap0" "libwww-perl" "libwww-robotrules-perl" "libx11-6" "libx11-data" "libxapian30" "libxaw7" "libxcb-render0" "libxcb-shm0" "libxcb1" "libxcomposite1" "libxdamage1" "libxdmcp6" "libxext6" "libxfixes3" "libxft2" "libxkbfile1"  "libxml2" "libxmu6" "libxmuu1" "libxpm4" "libxrandr2" "libxrender1" "libxt6" "libyaml-0-2" "lib32gcc1" "lib32ncurses6" "lib32stdc++6" "lib32z1" "libaio1" "libapr1" "libauthen-sasl-perl" "libc6-i386" "libcap2" "libconfuse2" "libcrypt-openssl-random-perl" "libcups2" "libdbd-mysql-perl"  "libfuse2" "libgeo-ip-perl" "libgsasl7" "libiodbc2" "libnss-ldap" "libnss-mdns" "libpam-modules" "libpcrecpp0v5"  "librrdp-perl" "libsasl2-modules" "libsasl2-modules-gssapi-mit" "libsasl2-modules-ldap"  "libtevent0" "libtalloc2" "libv4l-0" "usbutils" "python3-bottle" "firmware-realtek" "iucode-tool" "intel-microcode" "runit-init" )

 fi


let NumberOfPackages=${#PACKAGES[@]}



if [ ! -f /tmp/.debpackages ]
then

  /usr/bin/dialog --title "Artica Installation" --yesno "$NumberOfPackages packages will be installed from Debian Repository\nAre you ready to perform this operation ?\n\nPress 'Yes' to continue, or 'No' to exit" 0 0
  return_value=$?
  case $return_value in
    1)
    echo "Aborting"
    exit
    return;;
  esac

  let FinalAdd=0
  let Number=0
  Perc=1
  for pkg in "${PACKAGES[@]}"
  do
      let Number+=1
      let FinalAdd+=1
      if (( Number > 10 ));
      then
          Perc=1
          if [ ! -f /usr/bin/php ]
          then
            /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Installation failed for php"  0 0
            exit 0
          fi
      fi
      if (( Number > 20 ));
      then
          Perc=3
      fi
      if (( Number > 25 ));
      then
          Perc=4
      fi

      if (( Number > 30 ));
      then
          Perc=5
      fi
      if (( Number > 35 ));
      then
          Perc=6
      fi
      if (( Number > 49 ));
      then
          Perc=7
      fi
      if (( Number > 55 ));
      then
          Perc=8
      fi
      if (( Number > 59 ));
      then
          Perc=9
      fi
      if (( Number > 65 ));
      then
          Perc=10
      fi
      if (( Number > 79 ));
      then
          Perc=11
      fi
      if (( Number > 85 ));
      then
          Perc=12
      fi
      if (( Number > 90 ));
      then
          Perc=13
      fi
      if (( Number > 95 ));
      then
          Perc=14
      fi
      if (( Number > 100 ));
      then
          Perc=15
      fi
      if (( Number > 110 ));
      then
          Perc=16
      fi
      if (( Number > 115 ));
      then
          Perc=17
      fi
      if (( Number > 120 ));
      then
          Perc=18
      fi
      if (( Number > 125 ));
      then
          Perc=19
      fi
      if (( Number > 130 ));
      then
          Perc=20
      fi
      if (( Number > 139 ));
      then
          Perc=21
      fi
      if (( Number > 150 ));
      then
          Perc=23
      fi
      if (( Number > 160 ));
      then
          Perc=24
      fi
      if (( Number > 170 ));
      then
          Perc=25
      fi
      if (( Number > 175 ));
      then
          Perc=26
      fi
      if (( Number > 180 ));
      then
          Perc=27
      fi
      if (( Number > 185 ));
      then
          Perc=28
      fi
      if (( Number > 190 ));
      then
          Perc=29
      fi
      if (( Number > 195 ));
      then
          Perc=30
      fi
      if (( Number > 200 ));
      then
          Perc=31
      fi
      if (( Number > 209 ));
      then
          Perc=32
      fi
      if (( Number > 210 ));
      then
          Perc=33
      fi
      if (( Number > 220 ));
      then
          Perc=34
      fi
      if (( Number > 230 ));
      then
          Perc=35
      fi
      if (( Number > 235 ));
      then
          Perc=36
      fi
      if (( Number > 240 ));
      then
          Perc=37
      fi
      if (( Number > 245 ));
      then
          Perc=38
      fi
      if (( Number > 250 ));
      then
          Perc=39
      fi
      if (( Number > 260 ));
      then
          Perc=40
      fi
      if (( Number > 265 ));
      then
          Perc=41
      fi
      if (( Number > 270 ));
      then
          Perc=42
      fi
      if (( Number > 280 ));
      then
          Perc=43
      fi
      if (( Number > 285 ));
      then
          Perc=44
      fi
      if (( Number > 290 ));
      then
          Perc=45
      fi
      if (( Number > 295 ));
      then
          Perc=46
      fi
      if (( Number > 300 ));
      then
          Perc=47
      fi
      if (( Number > 310 ));
      then
          Perc=48
      fi
      if (( Number > 320 ));
      then
          Perc=49
      fi
      if (( Number > 325 ));
      then
          Perc=50
      fi
      if (( Number > 340 ));
      then
          Perc=51
      fi
      if (( Number > 341 ));
      then
          Perc=52
      fi
      if (( Number > 345 ));
      then
          Perc=53
      fi
      if (( Number > 350 ));
      then
          Perc=54
      fi
      if (( Number > 360 ));
      then
          Perc=55
      fi
      if (( Number > 370 ));
      then
          Perc=56
      fi
      if (( Number > 380 ));
      then
          Perc=57
      fi
      if (( Number > 385 ));
      then
          Perc=58
      fi
      if (( Number > 390 ));
      then
          Perc=59
      fi
      if (( Number > 395 ));
      then
          Perc=61
      fi
      if (( Number > 400 ));
      then
          Perc=62
      fi
      if (( Number > 410 ));
      then
          Perc=63
      fi
      if (( Number > 420 ));
      then
          Perc=64
      fi
      if (( Number > 430 ));
      then
          Perc=65
      fi
      if (( Number > 440 ));
      then
          Perc=66
      fi
      if (( Number > 450 ));
      then
          Perc=70
      fi
      if (( Number > 455 ));
      then
          Perc=71
      fi
      if (( Number > 460 ));
      then
          Perc=72
      fi
      if (( Number > 465 ));
      then
          Perc=73
      fi
      if (( Number > 470 ));
      then
          Perc=73
      fi
      if (( Number > 475 ));
      then
          Perc=74
      fi
      if (( Number > 480 ));
      then
          Perc=75
      fi
      if (( Number > 485 ));
      then
          Perc=76
      fi
      if (( Number > 490 ));
      then
          Perc=77
      fi
      if (( Number > 500 ));
      then
          Perc=78
      fi
      if (( Number > 505 ));
      then
          Perc=79
      fi
      if (( Number > 510 ));
      then
          Perc=80
      fi
      if (( Number > 520 ));
      then
          Perc=81
      fi
      if (( Number > 525 ));
      then
          Perc=82
      fi
      if (( Number > 530 ));
      then
          Perc=83
      fi
      if (( Number > 535 ));
      then
          Perc=84
      fi
      if (( Number > 550 ));
      then
          Perc=85
      fi
      if (( Number > 555 ));
      then
          Perc=86
      fi
      if (( Number > 560 ));
      then
          Perc=87
      fi
      if (( Number > 565 ));
      then
          Perc=88
      fi
      if (( Number > 570 ));
      then
          Perc=89
      fi
      if (( Number > 580 ));
      then
          Perc=90
      fi
      if (( Number > 600 ));
      then
          Perc=93
      fi
      if (( Number > 610 ));
      then
          Perc=95
      fi
      if (( Number > 620 ));
      then
          Perc=96
      fi
      if (( Number > 620 ));
      then
          Perc=96
      fi
      if (( Number > 630 ));
      then
          Perc=98
      fi
      echo $Perc| dialog --title "INSTALLING BASE DEBIAN PACKAGES" --gauge "Installing $pkg $FinalAdd/$NumberOfPackages" 6 80
      DEBIAN_FRONTEND=noninteractive apt-get -o Dpkg::Options::='--force-confnew' -fuy install $pkg >>/root/INSTALL.TXT 2>&1

  done
    echo $Perc| dialog --title "INSTALLING BASE DEBIAN PACKAGES" --gauge "Installing FIREHOL" 6 80
    DEBIAN_FRONTEND=noninteractive apt-get -o Dpkg::Options::='--force-confnew' -fuy install firehol fireqos >>/root/INSTALL.TXT 2>&1
    echo 100| dialog --title "INSTALLING BASE DEBIAN PACKAGES" --gauge "DONE..." 6 80
    touch /tmp/.debpackages
  fi




len=${#TARGETS_INIT[@]}
Number=0
for path in "${TARGETS_INIT[@]}"
do
	((Number++))
	if [ -f "/etc/init.d/$path" ]
	then
		((percent++))
		if (( percent > 99 )); then
			percent=99
		fi
		echo $percent| dialog --title "DISABLING SERVICE" --gauge "Please wait, uninstalling /etc/init.d/$path ($Number/$len)" 6 80
		/etc/init.d/$path stop >>/var/log/artica-iso.log 2>&1 || true
		update-rc.d $path remove >>/var/log/artica-iso.log 2>&1 || true
		systemcl disable $path  >>/var/log/artica-iso.log 2>&1 || true
		rm -f /etc/init.d/$path||true
	fi
done
echo 100| dialog --title "DISABLING SERVICE" --gauge "DONE..." 6 80

if [ ! -f /root/.SYSTEMD_SYSVINIT.TXT ]
then
  echo 10| dialog --title "MIGRATING SYSTEMD TO SYSVINIT" --gauge "Please wait..." 6 80
  apt-get remove -y --purge libpam-systemd >/root/.SYSTEMD_SYSVINIT.TXT 2>&1
  echo 20| dialog --title "MIGRATING SYSTEMD TO SYSVINIT" --gauge "Please wait..." 6 80
  apt-get remove -y --purge libnss-systemd >>/root/.SYSTEMD_SYSVINIT.TXT 2>&1
  echo 30| dialog --title "MIGRATING SYSTEMD TO SYSVINIT" --gauge "Please wait..." 6 80
  echo 50| dialog --title "MIGRATING SYSTEMD TO SYSVINIT" --gauge "Please wait..." 6 80
  apt-get remove -y --purge systemd-sysv >>/root/.SYSTEMD_SYSVINIT.TXT 2>&1
  echo 60| dialog --title "MIGRATING SYSTEMD TO SYSVINIT" --gauge "Please wait..." 6 80
  DEBIAN_FRONTEND=noninteractive apt-get -o Dpkg::Options::='--force-confnew' -fuy install sysvinit-core sysvinit-utils >>/root/.SYSTEMD_SYSVINIT.TXT 2>&1
  echo 100| dialog --title "MIGRATING SYSTEMD TO SYSVINIT" --gauge "DONE..." 6 80
fi


if [ ! -f /tmp/.freezpckg ]
then
  for path in "${TARGETS_FREEZE[@]}"
  do
    ((Number++))
      if (( percent > 99 )); then
        percent=99
      fi
    echo $percent| dialog --title "FREEZE SERVICES" --gauge "Please wait, freeze $path" 6 80
    /bin/echo "$path hold" | /usr/bin/dpkg --set-selections >/dev/null 2>&1|| true
    ((percent++))
  done
  echo 100| dialog --title "FREEZE SERVICES" --gauge "DONE..." 6 80
  touch /tmp/.freezpckg
  sleep 1
fi

 if [ ! -f /usr/bin/php ]
  then
    /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Installation failed for php\nPlease restart this script"  0 0
    rm -f /tmp/.debpackages >/dev/null 2>&1
    rm -f /tmp/.freezpckg >/dev/null 2>&1
    exit 0
  fi

if [ -f /tmp/.lightpackage ]
then
   echo 70| dialog --title "Downloading light package script" --gauge "Downloading light package script" 6 80
   curl http://articatech.net/minimal-package.php >/root/minimal.sh
   chmod 0755 /root/minimal.sh
   mkdir -p /etc/artica-postfix
   /root/minimal.sh
   exit 0
fi

if [ ! -f /etc/artica-postfix/FULL_SETUP_EXECUTED ]
then
    if [ ! -f /tmp/.lightpackage ]
    then
      if [ ! -f /home/artica/tmp/package.tar.gz ]
      then
          mkdir -p /home/artica/tmp >/dev/null 2>&1
          if [ ! -f /home/artica/tmp/exec.downloader.php ]
          then
            echo 10| dialog --title "INSTALLING ARTICA" --gauge "Downloading master package" 6 80
            wget http://articatech.net/download/downloader.tgz -O /tmp/downloader.tgz >/dev/null
            tar -xf /tmp/downloader.tgz -C /home/artica/tmp/
            rm -f /tmp/downloader.tgz

          fi

          if [ ! -f /home/artica/tmp/exec.downloader.php ]
          then
            /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Installation failed\nUnable to download downloader"  0 0
            exit 0
          fi
          echo 20| dialog --title "INSTALLING ARTICA" --gauge "Retreive index file" 6 80
          /usr/bin/php /home/artica/tmp/exec.downloader.php --index >/dev/null 2>&1

          if [ ! -f /home/artica/tmp/exec.downloader.php ]
          then
            /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Installation failed\nUnable to download index file"  0 0
            exit 0
          fi
          echo 30| dialog --title "INSTALLING ARTICA" --gauge "Building download script" 6 80
          /usr/bin/php /home/artica/tmp/exec.downloader.php --build >/dev/null 2>&1

          if [ ! -f /tmp/downloader.sh ]
          then
            /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Installation failed\nUnable to build the downloader script"  0 0
            exit 0
          fi
          chmod 0755 /tmp/downloader.sh
          /tmp/downloader.sh
          if [ ! -f /home/artica/tmp/package.splited ]
          then
            /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Installation failed\nUnable to download splited packages"  0 0
            exit 0
          fi
           echo 40| dialog --title "INSTALLING ARTICA" --gauge "Recover the Artica package" 6 80
          /usr/bin/php /home/artica/tmp/exec.downloader.php --recover >/dev/null 2>&1
          if [ ! -f /home/artica/tmp/package.tar.gz ]
          then
            /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Installation failed\nUnable to recover splited packages"  0 0
            exit 0
          fi
      fi

  mkdir /home/TempSystem
  mkdir -p /etc/artica-postfix >/dev/null 2>&1
  if [ ! -f /etc/artica-postfix/PACKAGE_EXTRACTED ]
  then
    (pv -n /home/artica/tmp/package.tar.gz | /bin/tar xzf - -C /home/TempSystem/ ) 2>&1 | dialog --title "INSTALLING ARTICA" --gauge "Extracting Artica Base package..." 6 80
    touch /etc/artica-postfix/PACKAGE_EXTRACTED
  fi

  rm -rf /home/TempSystem/lib/modules
  rm -rf /home/TempSystem/usr/lib/x86_64-linux-gnu/xtables

  TARGETS_DIRS=( "/usr/lib" "/lib" "/usr/local/lib" "/var/lib/elasticsearch" "/var/lib/fail2ban" "/var/lib/kibana" "/var/lib/netdata" "/usr/lib/x86_64-linux-gnu","/lib/x86_64-linux-gnu" "/etc" "/opt" "/usr/local/ArticaStats"  "/usr/local/ArticaWebConsole" "/usr/local/3proxy" "/usr/local/share" "/var/cache" "/var/log" "/var/lib" "/var/log" "/var/milter-greylist" "/var/opt" "/var/run" "/var/spool","/usr/local/modsecurity" "/usr/local/modsecurity/lib" "/var/opt" "/opt/kaspersky" "/usr/share/artica-postfix")
  TARGETS_USR=( "RichFilemanager"  "artica-postfix"  "elasticsearch"  "greensql-console"  "lintian"  "netdata"  "php" "phpipam" "suricata" "wsusoffline" "aclocal" "doc" "filebeat" "kibana" "nDPI" "nmap" "php-composer" "pyshared" "update-ipsets"  "xapian-core" "nginx")
  TARGETS_BINS=( "/usr/local/sbin" "/usr/local/bin" "/bin" "/sbin" "/usr/bin" "/usr/sbin" "/usr/libexec" )
    percent=10
  if [ ! -f /etc/artica-postfix/PACKAGES_INSTALLED ]
  then
      len=${#TARGETS_BINS[@]}
      for path in "${TARGETS_BINS[@]}"
      do
        mkdir -p $path || true
        chmod 0755 $path || true
        ((Number++))
        echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Installing $path $Number/$len" 6 80
        echo "* * * BINARY /home/TempSystem$path TO $path/" >> /var/log/artica-rsync.log
        echo "rsync -qra /home/TempSystem$path/* $path/" >> /var/log/artica-rsync.log
        rsync -qra /home/TempSystem$path/* $path/ >>/var/log/artica-rsync.log 2>&1
        ((percent++))
      done
      Number=0
      len=${#TARGETS_DIRS[@]}
      for path in "${TARGETS_DIRS[@]}"
      do
        mkdir -p $path || true
        chmod 0755 $path || true
        ((Number++))
        echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Installing $path $Number/$len" 6 80
        echo "rsync -qra /home/TempSystem$path/* $path/" >> /var/log/artica-rsync.log
        rsync -qra /home/TempSystem$path/* $path/ >>/var/log/artica-rsync.log 2>&1
        ((percent++))

      done



      Number=0
      len=${#TARGETS_DIRS[@]}
      for path in "${TARGETS_USR[@]}"
      do
        mkdir -p /usr/share/$path || true
        chmod 0755 /usr/share/$path || true
        ((Number++))
        echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Installing /usr/share/$path $Number/$len" 6 80
        echo "rsync -qra /home/TempSystem/usr/share/$path/* /usr/share/$path/" >>/var/log/artica-rsync.log
        rsync -qra /home/TempSystem/usr/share/$path/* /usr/share/$path/ >>/var/log/artica-rsync.log 2>&1
        ((percent++))
      done

      if [ ! -f /usr/sbin/squid ]
      then
        /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Unable to find squid binary!"  0 0
        sleep 5
        exit 0
      fi

      ((percent++))
    fi
  fi
fi

if [ -f /home/TempSystem/usr/share/artica-postfix/ressources/class.sockets.inc ]
then
  ((percent++))
  echo $percent| dialog --title "INSTALLING ARTICA BASE Package" --gauge "Please wait...." 6 80
  cp rfd /home/TempSystem/usr/share/artica-postfix/* /usr/share/artica-postfix/ >/dev/null 2>&1 ||true
  ((percent++))
  echo $percent| dialog --title "Apply chmod/chown" --gauge "Please wait...." 6 80
  chmod -R 0755 /usr/share/artica-postfix >/dev/null 2>&1 ||true
  chown -R www-data /usr/share/artica-postfix >/dev/null 2>&1 ||true
fi

if [ -d /home/TempSystem ]
then
      ((percent++))
      echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, removing source directory" 6 80
      rm -rf /home/TempSystem
fi

if [ ! -f /usr/share/artica-postfix/logon.sh ]
then
	/usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Unable to find Artica LOGON binary!"  0 0
	exit 0
fi

if [ ! -f /usr/share/artica-postfix/ressources/class.sockets.inc ]
then
	/usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Corrupted installation! (class.sockets.inc)"  0 0
	exit 0
fi

chmod 0755 /usr/share/artica-postfix/logon.sh

if [ -f /etc/php5/cli/conf.d/ming.ini ]
then
  /bin/rm -f /etc/php5/cli/conf.d/ming.ini
fi
mkdir -p /home/artica/tmp >>/var/log/artica-iso.log 2>&1 || true
mkdir -p /var/run/slapd >>/var/log/artica-iso.log 2>&1 || true

((percent++))
echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Configure the framework..." 6 80
/usr/bin/php  /usr/share/artica-postfix/exec.apt-get.php --remove-systemd >/dev/null 2>&1 &


/usr/bin/wget http://articatech.net/download/webconsole.tar.gz -O /tmp/webconsole.tar.gz >/var/log/artica-iso.log 2>&1 || true

/usr/bin/tar xf /tmp/webconsole.tar.gz -C / >/var/log/artica-iso.log 2>&1 || true

mkdir -p /usr/local/ArticaWebConsole/sbin >/var/log/artica-iso.log 2>&1 || true
if [ -f /usr/sbin/php-fpm7.0 ]
then
  cp /usr/sbin/php-fpm7.0 /usr/local/ArticaWebConsole/sbin/artica-phpfpm >/var/log/artica-iso.log 2>&1 || true
fi
if [ -f /usr/sbin/php-fpm7.1 ]
then
  cp /usr/sbin/php-fpm7.1 /usr/local/ArticaWebConsole/sbin/artica-phpfpm >/var/log/artica-iso.log 2>&1 || true
fi
if [ -f /usr/sbin/php-fpm7.2 ]
then
  cp /usr/sbin/php-fpm7.2 /usr/local/ArticaWebConsole/sbin/artica-phpfpm >/var/log/artica-iso.log 2>&1 || true
fi
if [ -f /usr/sbin/php-fpm7.3 ]
then
  cp /usr/sbin/php-fpm7.3 /usr/local/ArticaWebConsole/sbin/artica-phpfpm >/var/log/artica-iso.log 2>&1 || true
fi
if [ -f /usr/sbin/php-fpm7.4 ]
then
  cp /usr/sbin/php-fpm7.4 /usr/local/ArticaWebConsole/sbin/artica-phpfpm >/var/log/artica-iso.log 2>&1 || true
fi

chmod 0755 /usr/local/ArticaWebConsole/sbin/artica-phpfpm >/var/log/artica-iso.log 2>&1 || true

((percent++))
echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Freeze some services.." 6 80
/usr/sbin/groupadd winbindd_priv >>/var/log/artica-iso.log 2>&1 || true
killall apache2 >>/var/log/artica-iso.log 2>&1 || true
echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Uninstalling exim" 6 80
/usr/bin/apt-get --purge --yes --force-yes --remove exim4* >>/var/log/artica-iso.log 2>&1 || true
killall php5-fpm >>/var/log/artica-iso.log 2>&1 || true
killall php-fpm >>/var/log/artica-iso.log 2>&1 || true



if [ ! -f /bin/login.old ]
then
 ((percent++))
  echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Installing Artica Menu console" 6 80
  /bin/mv /bin/login /bin/login.old || true
  /bin/ln -s /usr/share/artica-postfix/logon.sh /bin/login || true
  dpkg-divert --divert /bin/login.old /bin/login >>/var/log/artica-iso.log 2>&1|| true
  /bin/chmod 777 /bin/login || true
  /bin/chmod 777 /usr/share/artica-postfix/logon.sh || true
fi

if [ ! -f /etc/artica-postfix/artica-iso-setup-launched ]
then
  /bin/rm -f /etc/artica-postfix/FROM_ISO  >>/var/log/artica-iso.log 2>&1 || true
  /bin/rm -f /usr/share/artica-postfix/bin/artica-iso >>/var/log/artica-iso.log 2>&1 || true
  /bin/touch /etc/artica-postfix/FROM_ISO
  /bin/rm -f /etc/artica-postfix/ARTICA_ISO2.lock >>/var/log/artica-iso.log 2>&1 || true
  /bin/rm -f /etc/cron.d/artica-boot-first >>/var/log/artica-iso.log 2>&1 || true

  ((percent++))
  echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Apply permissions" 6 80
  /bin/chown -R www-data:www-data /usr/share/artica-postfix >>/var/log/artica-iso.log 2>&1
  ((percent++))
  echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Patching Filesystem " 6 80
  /bin/touch /etc/artica-postfix/artica-iso-setup-launched || true
  /bin/echo "secret" >/etc/ldap.secret || true
  /bin/touch /etc/artica-postfix/artica-iso-first-reboot || true
  /bin/mkdir -p /etc/artica-postfix/settings/Daemons || true
  /bin/chmod -R 0755 /etc/artica-postfix/settings/Daemons || true
  echo "1" >/etc/artica-postfix/settings/Daemons/ArticaHttpUseSSL 2>&1 || true
  ((percent++))
  echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Configuring PHP system" 6 80
  /usr/share/artica-postfix/bin/articarest -phpini -debug >>/var/log/artica-iso.log 2>&1|| true
  ((percent++))
  echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, installing default services" 6 80
  /usr/bin/php /usr/share/artica-postfix/exec.initslapd.php >>/var/log/artica-iso.log 2>&1|| true
  ((percent++))
  echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, tunning filesystem" 6 80
  echo "Tuning file system" >>/var/log/artica-iso.log 2>&1 || true
  /usr/bin/php /usr/share/artica-postfix/exec.patch.fstab.php >>/var/log/artica-iso.log 2>&1 || true
  percent=80
  ((percent++))
  echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Deleting floppy" 6 80
  echo "blacklist floppy" >>/var/log/artica-iso.log 2>&1 || true
  echo 'blacklist floppy' >/etc/modprobe.d/floppy-blacklist.conf 2>&1 || true
  ((percent++))
  echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Patching Grub for Debian 10" 6 80
  echo "/usr/share/artica-postfix/bin/articarest -grub-debian5" >>/var/log/artica-iso.log 2>&1 || true
  /usr/share/artica-postfix/bin/articarest -grub-debian5 >>/var/log/artica-iso.log 2>&1 || true
  ((percent++))
fi
if [ ! -f /etc/artica-postfix/FULL_SETUP_EXECUTED ]
then
    echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Installing framework" 6 80
    echo "/usr/bin/php /usr/share/artica-postfix/exec.framework.php --install" >>/var/log/artica-iso.log 2>&1 || true
    /usr/bin/php /usr/share/artica-postfix/exec.framework.php --install >>/var/log/artica-iso.log 2>&1 || true
    ((percent++))
    echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Installing VnStatD" 6 80
    echo "/usr/bin/php /usr/share/artica-postfix/exec.vnstat.php --install" >>/var/log/artica-iso.log 2>&1 || true
    /usr/bin/php /usr/share/artica-postfix/exec.vnstat.php --install >>/var/log/artica-iso.log 2>&1 || true
    ((percent++))
    echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Installing DNS CACHE SERVICE" 6 80
    /usr/bin/php /usr/share/artica-postfix/exec.unbound.php --install >>/var/log/artica-iso.log 2>&1 || true
    /usr/bin/php /usr/share/artica-postfix/exec.wizard.resolv.conf.php >>/var/log/artica-iso.log 2>&1 || true
    /usr/bin/php /usr/share/artica-postfix/exec.pam.php --build >>/var/log/artica-iso.log 2>&1 || true
    ((percent++))
    echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Reconfiguring PHP-FPM" 6 80
    echo "/usr/bin/php /usr/share/artica-postfix/exec.artica-php-fpm.php --install" >>/var/log/artica-iso.log 2>&1 || true
    /usr/bin/php /usr/share/artica-postfix/exec.artica-php-fpm.php --install >>/var/log/artica-iso.log 2>&1 || true
    ((percent++))
    echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Reconfiguring MONIT" 6 80
    echo "/usr/bin/php /usr/share/artica-postfix/exec.monit.php --build" >>/var/log/artica-iso.log 2>&1 || true
    rm -rf /etc/monit/monitrc >>/var/log/artica-iso.log 2>&1 || true
    /usr/bin/php /usr/share/artica-postfix/exec.monit.php --build >>/var/log/artica-iso.log 2>&1 || true
    ((percent++))
    echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, installing TailON" 6 80
    echo "/usr/bin/php /usr/share/artica-postfix/exec.tailon.php --install" >>/var/log/artica-iso.log 2>&1 || true
    ((percent++))
    echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Update debian sources list" 6 80
    php /usr/share/artica-postfix/exec.apt-get.php --sources-list >>/var/log/artica-iso.log 2>&1 || true
    ((percent++))
    echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, installing MEMCACHED" 6 80
    echo "Reconfiguring MEMCACHED" >>/var/log/artica-iso.log 2>&1 || true
    /usr/share/artica-postfix/bin/articarest -install-memcached >>/var/log/artica-iso.log 2>&1 ||
    ((percent++))
    echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Installing Deep packet inspection" 6 80
    /usr/bin/php /usr/share/artica-postfix/exec.firehol.php --enable-ndpi >>/var/log/artica-iso.log 2>&1|| true
    ((percent++))
    echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Checking and Testing VMWare Tools" 6 80
    /usr/bin/php /usr/share/artica-postfix/exec.openVMTools.php --autoinstall >>/var/log/artica-iso.log 2>&1|| true
    ((percent++))
    echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, checking network" 6 80
    php /usr/share/artica-postfix/exec.syslog-engine.php --rsylogd >>/var/log/artica-iso.log 2>&1 || true
    ((percent++))

        ((percent++))
    echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Uninstall systemd" 6 80
    php /usr/share/artica-postfix/exec.verif.packages.php >>/var/log/artica-iso.log 2>&1 || true

    echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Uninstall systemd" 6 80
    php /usr/share/artica-postfix/exec.apt-get.php --remove-systemd >>/var/log/artica-iso.log 2>&1 || true

    ((percent++))
    len=${#LAST_INIT[@]}
    Number=0
    for path in "${LAST_INIT[@]}"
    do
      ((Number++))
      if [ -f "/etc/init.d/$path" ]
      then
        ((percent++))
        if (( percent > 95 )); then
          percent=95
        fi
        echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, uninstalling /etc/init.d/$path ($Number/$len)" 6 80
        /etc/init.d/$path stop >>/var/log/artica-iso.log 2>&1 || true
        update-rc.d $path remove >>/var/log/artica-iso.log 2>&1 || true
        rm -f /etc/init.d/$path||true
      fi
    done

    percent=95
    ((percent++))
    if [ ! -f /etc/artica-postfix/SETUP_INIT_RAMFS ]
    then
      echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Update INIT RamFS" 6 80
      if [ -x /usr/sbin/update-initramfs -a -e /etc/initramfs-tools/initramfs.conf ] ; then
        echo "update-initramfs" >>/var/log/artica-iso.log 2>&1 || true
        update-initramfs -u >>/etc/artica-postfix/SETUP_INIT_RAMFS 2>&1
      fi
    fi

    echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, get the latest version" 6 80
    /usr/bin/php /usr/share/artica-postfix/exec.nightly.php --force >/dev/null 2>&1
    rm -f /home/artica/tmp/package.tar.gz
    touch /etc/artica-postfix/FULL_SETUP_EXECUTED
fi
((percent++))
 echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Patching tables 1/2" 6 80
/usr/bin/php  /usr/share/artica-postfix/exec.convert-to-sqlite.php >/dev/null 2>&1
((percent++))
echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Patching tables 2/2" 6 80
/usr/bin/php  /usr/share/artica-postfix/exec.convert-to-sqlite.php >/dev/null 2>&1

((percent++))
echo $percent| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Build Network wizard" 6 80
/usr/bin/php  /usr/share/artica-postfix/exec.menu.interface.php --wizard eth0
/tmp/wizard_eth0.sh

echo 99| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Build Network..." 6 80
/usr/bin/php  /usr/share/artica-postfix/exec.virtuals-ip.php --build >/dev/null 2>&1
echo 100| dialog --title "INSTALLING ARTICA" --gauge "Please wait, Build Network DONE..." 6 80
sleep 1
/usr/bin/dialog --title "\Zb\Z1REBOOTING" --colors --infobox "\Zb\Z1You need to reboot the system\nbefore save your networks interface parameters\nbecause your ensxx will return back to ehtxx" 0 0

exit 0

