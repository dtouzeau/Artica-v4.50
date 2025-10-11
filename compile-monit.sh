#!/bin/sh

# wget https://mmonit.com/monit/dist/monit-5.26.0.tar.gz
# ./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-silent-rules --libdir=\${prefix}/lib/x86_64-linux-gnu --libexecdir=\${prefix}/lib/x86_64-linux-gnu --disable-maintainer-mode --disable-dependency-tracking --sysconfdir=/etc/monit --without-pam --enable-optimized

strip -s /usr/bin/monit
mkdir -p /root/monit-builder/usr/bin
mkdir -p /root/monit-builder/usr/sbin


cp -fd /usr/bin/monit /root/monit-builder/usr/bin/
cp -fd /usr/sbin/ripole /root/monit-builder/usr/sbin/

VERSION=`monit -V|grep version|cut -s -d' ' -f5`

cd /root/monit-builder
chmod -R 0755 * /root/monit-builder
tar -czf /root/monit-$VERSION.tar.gz *
echo "/root/monit-$VERSION.tar.gz Done..."





