#!/bin/sh

cd /root

if [ -d /root/aide ]
then
  rm -rf /root/aide
fi

git clone https://github.com/aide/aide.git
cd /root/aide

if [ ! -f  /usr/include/e2p/e2p.h ]
then
	apt-get install libext2fs-dev
fi

if [ ! -f /usr/include/libaudit.h ]
then
	apt-get install libaudit-dev
fi

if [ ! -f /usr/include/sys/acl.h ]
then
	apt-get install libacl1-dev
fi

if [ ! -f /root/aide/configure ]
then
	chmod 0755 /root/aide/autogen.sh
	./autogen.sh
fi

if [ ! -f /root/aide/Makefile ]
then
	./configure --prefix=/usr --sysconfdir=/var/lib/aide/please-dont-call-aide-without-parameters --with-config_file=/dev/null --with-zlib --with-xattr --with-posix-acl --with-e2fsattrs  --with-audit
fi
if [ ! -f /root/aide/aide ]
then
	make
fi
if [ ! -f /root/aide/aide ]
then
	echo "Compilation failed"
	exit 0
fi

make install
strip -s /usr/bin/aide
VERSION=`/usr/bin/aide -h 2>&1|grep -E "Aide\s+[0-9\.]+"|cut -d ' ' -f2`

mkdir -p /root/aide-compile/usr/bin
mkdir -p /root/aide-compile/usr/share/man/man1
mkdir -p /root/aide-compile/usr/share/man/man5

cp -fd /usr/bin/aide /root/aide-compile/usr/bin/
cp -fd /usr/share/man/man5/aide.conf.5 /root/aide-compile/usr/share/man/man5/
cp -fd /usr/share/man/man1/aide.1 /root/aide-compile/usr/share/man/man1/
cd /root/aide-compile
tar -czvf /root/aide-$VERSION.tar.gz *
cd /root
rm -rf /root/aide-compile

echo "root/aide-$VERSION.tar.gz done..."