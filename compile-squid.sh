#!/bin/sh

VERSION="5.1"
URI="http://www.squid-cache.org/Versions/v5/squid-$VERSION.tar.gz"
TARBALL="/root/squid-$VERSION.tar.gz"
SRCDIR="/root/squid-$VERSION"
CURVERSION=`/usr/sbin/squid --version| cut -s -d' ' -f4|grep -E "[0-9\.]+"`

echo "Current version is $CURVERSION"
WORKDIR="/root/squid-builder-$CURVERSION"


cd /root
if [ ! -d $SRCDIR ]; then
  if [ ! -f $TARBALL ]; then
    echo "Downloading $VERSION"
    wget $URI -O $TARBALL
  fi

  if [ ! -f $TARBALL ]; then
    echo "FAILED!"
    exit 0
  fi

  tar xf $TARBALL
  if [ ! -d $SRCDIR ]; then
    echo "Extracting $TARBALL [FAILED]"
    exit 0
  fi
fi

mkdir $SRCDIR/src/icmp/tests || true
mkdir $SRCDIR/tools/squidclient/tests || true
mkdir $SRCDIR/tools/tests || true
echo "Patching negotiate_kerberos_auth.cc"
echo "in /root/squid-4.10/src/client_side.cc ConnStateData::checkLogging -> add return at the start function."
echo "run php /usr/share/artica-postfix/compile-squid35.php --patch $SRCDIR"
echo "A patcher pour retirer les groupes /auth/negotiate/kerberos/negotiate_kerberos_auth.cc"
echo " ------------------------------------------------------"
echo "Do a vi $SRCDIR/auth/negotiate/kerberos/negotiate_kerberos_auth.cc"
echo "Remove any entry of"
echo "ag = get_ad_groups((char *)&ad_groups,context, pac);"
echo "fprintf(stdout, \"OK token=%s user=%s %s\n\", \"AA==\", rfc_user, ag?ag:\"group=\");"
echo "char ad_groups[MAX_PAC_GROUP_SIZE];"
echo "char *ag=NULL;"
exit 0

#./configure --prefix=/usr --build=x86_64-linux-gnu --includedir=/include --mandir=/share/man --infodir=/share/info --localstatedir=/var --libexecdir=/lib/squid3 --disable-maintainer-mode --disable-dependency-tracking --datadir=/usr/share/squid3 --sysconfdir=/etc/squid3 --enable-gnuregex --enable-removal-policy=heap --enable-follow-x-forwarded-for --disable-cache-digests --enable-http-violations --enable-removal-policies=lru,heap --enable-arp-acl --enable-truncate --with-large-files --with-pthreads --enable-esi --enable-storeio=aufs,diskd,ufs,rock --enable-x-accelerator-vary --with-dl --enable-linux-netfilter --with-netfilter-conntrack --enable-wccpv2 --enable-eui --enable-auth --enable-auth-basic --enable-snmp --enable-icmp --enable-auth-digest --enable-log-daemon-helpers --enable-url-rewrite-helpers --enable-auth-ntlm --with-default-user=squid --enable-icap-client --disable-cache-digests --enable-poll --enable-epoll --enable-async-io=128 --enable-zph-qos --enable-delay-pools --enable-http-violations --enable-url-maps --enable-ecap --enable-ssl --with-openssl --enable-ssl-crtd --enable-xmalloc-statistics --enable-ident-lookups --with-filedescriptors=65536 --with-aufs-threads=128 --disable-arch-native --with-logdir=/var/log/squid --with-pidfile=/var/run/squid/squid.pid --with-swapdir=/var/cache/squid

# git clone https://github.com/yvoinov/squid-ecap-gzip.git
# cd squid-ecap-gzip
# chmod 0755 configure
# ./configure 'CXXFLAGS=-m64' 'LDFLAGS=-L/usr/lib'

rm -rf $WORKDIR || true

rm -f /etc/squid3/squid.conf
rm -f /etc/squid3/squid.conf.default
rm -f /etc/squid3/squid.conf.documented
rm -f /etc/squid3/mime.conf



mkdir -p $WORKDIR/etc/squid3
mkdir -p $WORKDIR/lib/squid3
mkdir -p $WORKDIR/usr/bin
mkdir -p $WORKDIR/usr/sbin
mkdir -p $WORKDIR/usr/lib
mkdir -p $WORKDIR/usr/share/squid3
mkdir -p $WORKDIR/usr/share/squid3/errors/templates
mkdir -p $WORKDIR/usr/share/squid3/errors
mkdir -p $WORKDIR/usr/share/squid3/icons/silk
mkdir -p $WORKDIR/usr/share/squid3/icons

strip -s /usr/bin/squidclient
strip -s /usr/bin/purge
strip -s /usr/sbin/squid
cp -fvd /usr/lib/libecap.* $WORKDIR/usr/lib/
cp -fvd /usr/local/lib/ecap_adapter_* $WORKDIR/usr/lib/
cp -rfvd /etc/squid3/* $WORKDIR/etc/squid3/
cp -rfvd /lib/squid3/* $WORKDIR/lib/squid3/
cp -fvd /usr/bin/squidclient $WORKDIR/usr/bin/squidclient
cp -fvd /usr/bin/purge $WORKDIR/usr/bin/purge
cp -fvd /usr/sbin/squid $WORKDIR/usr/sbin/squid
cp -rfvd /usr/share/squid3/* $WORKDIR/usr/share/squid3/




rm -f /root/xsquid-$CURVERSION.tar.gz
cd $WORKDIR
tar -czvf /root/xsquid-builder-$CURVERSION.tar.gz *
echo "/root/xsquid-builder-$CURVERSION.tar.gz done"
