#!/bin/sh

#apt-get instal libdb5.3-dev libldap2-dev libxml2-dev libsasl2-dev libbz2-dev libz-dev
CFLAGS="-g -O2 -fPIE -fstack-protector -DNUMTHREADS=256 --param=ssp-buffer-size=4 -Wformat -Werror=format-security -Wall"
CXXFLAGS="-g -O2 -fPIE -DNUMTHREADS=256 -fstack-protector --param=ssp-buffer-size=4 -Wformat -Werror=format-security"
CPPFLAGS="-D_FORTIFY_SOURCE=2" 
LDFLAGS="-fPIE -pie -Wl,-z,relro -Wl,-z,now"

#./configure --prefix=/usr --build=x86_64-linux-gnu --includedir=${prefix}/include --mandir=${prefix}/share/man --infodir=${prefix}/share/info --localstatedir=/var --libexecdir=${prefix}/lib/squid3 --disable-maintainer-mode --disable-dependency-tracking --srcdir=. --datadir=/usr/share/squid3 --sysconfdir=/etc/squid3 --with-pidfile=/var/run/squid/squid.pid --with-logdir=/var/log/squid --enable-gnuregex --enable-removal-policy=heap --enable-follow-x-forwarded-for --disable-cache-digests --enable-http-violations --enable-removal-policies=lru,heap --enable-arp-acl --enable-truncate --with-large-files --with-pthreads --enable-esi --enable-storeio=aufs,diskd,ufs,rock --enable-x-accelerator-vary --with-dl --enable-linux-netfilter --with-netfilter-conntrack --enable-wccpv2 --enable-eui --enable-auth --enable-auth-basic --enable-snmp --enable-icmp --enable-auth-digest --enable-log-daemon-helpers --enable-url-rewrite-helpers --enable-auth-ntlm --with-default-user=squid --enable-icap-client --disable-cache-digests --enable-poll --enable-epoll --enable-async-io=128 --enable-zph-qos --enable-delay-pools --enable-http-violations --enable-url-maps --enable-ecap --enable-ssl --with-openssl --enable-ssl-crtd --enable-xmalloc-statistics --enable-ident-lookups --with-filedescriptors=65536 --with-aufs-threads=128 --disable-arch-native

rm -rf /root/squid-packager

mkdir -p /root/squid-packager/usr/lib
mkdir -p /root/squid-packager/lib/squid3
mkdir -p /root/squid-packager/etc/squid3/
mkdir -p /root/squid-packager/usr/share/squid3
mkdir -p /root/squid-packager/usr/bin
mkdir -p /root/squid-packager/usr/sbin

strip -s /usr/bin/squidclient
strip -s /usr/bin/purge
strip -s /usr/sbin/squid

cp -fd /usr/lib/libecap.a  /root/squid-packager/usr/lib/
cp -fd /usr/lib/libecap.la  /root/squid-packager/usr/lib/
cp -fd /usr/lib/libecap.so  /root/squid-packager/usr/lib/
cp -fd /usr/lib/libecap.so.3 /root/squid-packager/usr/lib/ 
cp -fd /usr/lib/libecap.so.3.0.0 /root/squid-packager/usr/lib/
cp -rfd /lib/squid3/* /root/squid-packager/lib/squid3/
cp -rfd /etc/squid3/* /root/squid-packager/etc/squid3/
cp -rfd /usr/share/squid3/* /root/squid-packager/usr/share/squid3/
cp -fd /usr/bin/squidclient /root/squid-packager/usr/bin/
cp -fd /usr/bin/purge /root/squid-packager/usr/bin/
cp -fd /usr/sbin/squid /root/squid-packager/usr/sbin/

cd /root/squid-packager

echo "Compressing  /root/squid-packager ->  /root/squid-package.tar.gz"

tar -czf /root/squid-package.tar.gz *

echo "Done...."
exit 0


