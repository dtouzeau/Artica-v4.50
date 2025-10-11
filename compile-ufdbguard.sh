#!/bin/sh

# apt-get install libssl1.0-dev
#./configure --prefix=/usr --includedir=${prefix}/include --mandir=${prefix}/share/man --infodir=${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --with-ufdb-dbhome=/home/ufdbcat --with-ufdb-user=squid --with-ufdb-config=/etc/ufdbcat --with-ufdb-logdir=/var/log/ufdbcat --without-unix-sockets --with-ufdb-piddir=/var/run/ufdbcat --with-ufdb-bindir=/opt/ufdbcat/bin -ldl -pthread -l pthread

#./configure --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var  --with-ufdb-piddir=/var/run/ufdbguard --with-ufdb-dbhome=/var/lib/squidguard --with-ufdb-user=squid --with-ufdb-config=/etc/squid3 --with-ufdb-logdir=/var/log/squid --without-unix-sockets


rm -rf /root/ufdbguard
mkdir -p /root/ufdbguard/usr/bin
mkdir -p /root/ufdbguard/usr/sbin
mkdir -p /root/ufdbguard/etc/init.d
mkdir -p /root/ufdbguard/opt/ufdbcat/bin

cp /usr/bin/ufdbguardd /root/ufdbguard/usr/sbin/
cp /usr/bin/ufdbConvertDB /root/ufdbguard/usr/bin/
cp /usr/bin/ufdbGenTable /root/ufdbguard/usr/bin/
cp /usr/bin/ufdbAnalyse /root/ufdbguard/usr/bin/
cp /usr/bin/ufdbhttpd /root/ufdbguard/usr/bin/
cp /etc/init.d/ufdb /root/ufdbguard/etc/init.d/
cp /usr/bin/ufdbgclient /root/ufdbguard/usr/bin/
cp /usr/bin/ufdbUpdate /root/ufdbguard/usr/bin/
cp /usr/bin/ufdb-pstack /root/ufdbguard/usr/bin/
cp /opt/ufdbcat/bin/ufdbcatdd /root/ufdbguard/opt/ufdbcat/bin/ufdbcatdd

cd /root/ufdbguard && tar cvf /root/ufdbguardd.tar.gz *
