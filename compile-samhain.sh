#!/bin/sh
VERSION="4.4.2"
cd /root

if [  -f /root/samhain-$VERSION.tar.gz ]
then
	rm -f /root/samhain-$VERSION.tar.gz
fi


if [ -d /root/samhain-$VERSION ]
then
	rm -rf /root/samhain-$VERSION
fi

echo "Downloading samhain-current.tar.gz"

wget https://www.la-samhna.de/samhain/samhain-current.tar.gz
tar xf samhain-current.tar.gz


if [ ! -f /root/samhain-$VERSION.tar.gz ]
then
	echo "/root/samhain-$VERSION.tar.gz, no such file"
	exit 0
fi


tar xf samhain-$VERSION.tar.gz


if [ ! -d /root/samhain-$VERSION ]
then
	echo "/root/samhain-$VERSION no such directory"
	exit 0
fi

rm -f samhain-current.tar.gz
rm -f samhain-$VERSION.tar.gz

if [ -f /usr/sbin/samhain ]
then
	rm /usr/sbin/samhain
fi



cd samhain-$VERSION
./configure --prefix=/usr --mandir=\${prefix}/share/man --with-config-file=/etc/samhain/samhainrc --enable-static --disable-ipv6 --with-state-dir=/var/lib/samhain --enable-dnmalloc -disable-asm --enable-network=no  --enable-login-watch --enable-mounts-check 	--enable-logfile-monitor --enable-process-check --enable-port-check --enable-suidcheck --with-pid-file=/var/run/samhain/samhain.pid --with-log-file=/var/log/samhain/samhain.log
make && make install

strip -s /usr/sbin/samhain

mkdir -p /root/samhain-builder/etc/samhain
mkdir -p /root/samhain-builder/var/run/samhain
mkdir -p /root/samhain-builder/var/log/samhain
mkdir -p /root/samhain-builder/var/lib/samhain
mkdir -p /root/samhain-builder/usr/sbin/

cp -d /usr/sbin/samhain /root/samhain-builder/usr/sbin/


cd /root/samhain-builder
tar -czvf /root/samhain-$VERSION.tar.gz *





