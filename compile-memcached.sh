#!/bin/sh

# https://memcached.org/downloads
# ./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-silent-rules --libdir=\${prefix}/lib/x86_64-linux-gnu --libexecdir=\${prefix}/lib/x86_64-linux-gnu --disable-maintainer-mode --disable-dependency-tracking --enable-sasl


strip -s /usr/bin/memcached

VERSION=`/usr/bin/memcached -V|cut -s -d' ' -f2`

mkdir -p /root/compile-memcached/usr/bin
cp -fd /usr/bin/memcached  /root/compile-memcached/usr/bin/

cd /root/compile-memcached
rm -f /root/memcached-compiled-*|| true
tar -czvf /root/memcached-compiled-$VERSION.tar.gz *
echo "/root/memcached-compiled-$VERSION.tar.gz done"


