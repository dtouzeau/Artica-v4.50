#!/bin/sh

VERSION="7.70"
if [ ! -d "/root/nmap-$VERSION" ] 
then
	wget https://nmap.org/dist/nmap-$VERSION.tar.bz2 -O /root/nmap-$VERSION.tar.bz2
	tar -xf /root/nmap-$VERSION.tar.bz2 -C /root/
fi
if [ ! -d "/root/nmap-$VERSION" ] 
then
	echo "Unable to stat directory /root/nmap-$VERSION"
	exit 0
fi


cd /root/nmap-$VERSION
if [ ! -f "/root/nmap-$VERSION/Makefile" ] 
then
	./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-silent-rules --libdir=\${prefix}/lib/x86_64-linux-gnu --libexecdir=\${prefix}/lib/x86_64-linux-gnu --disable-maintainer-mode --disable-dependency-tracking --without-liblua --with-liblinear --disable-ipv6 STRIP=/bin/true
fi

make && make install




strip -s /usr/bin/nping
strip -s /usr/bin/ndiff
strip -s /usr/bin/nmap
strip -s /usr/bin/ncat

mkdir -p /root/nmap-builder/usr/bin
mkdir -p /root/nmap-builder/usr/share/nmap
mkdir -p /root/nmap-builder/usr/lib/x86_64-linux-gnu/

cp -fd /usr/bin/nping /root/nmap-builder/usr/bin/
cp -fd /usr/bin/ndiff /root/nmap-builder/usr/bin/
cp -fd /usr/bin/nmap /root/nmap-builder/usr/bin/
cp -fd /usr/bin/ncat /root/nmap-builder/usr/bin/
cp -rfd /usr/share/nmap/* /root/nmap-builder/usr/share/nmap/
cp -rfd /usr/lib/x86_64-linux-gnu/libssh2.* /root/nmap-builder/usr/lib/x86_64-linux-gnu/

cd /root/nmap-builder
tar -czf /root/nmap-builder-$VERSION.tar.gz *
echo "/root/nmap-builder-$VERSION.tar.gz done"



