#!/bin/sh

# vf https://3proxy.ru/download/devel/
# make -f Makefile.Linux
# make -f Makefile.Linux install


rm -rf /root/3proxy-builder

/usr/bin/install -m 755 -d /root/3proxy-builder/var/run/3proxy
/usr/bin/install -m 755 -d /root/3proxy-builder/bin
/usr/bin/install -m 755 -d /root/3proxy-builder/usr/local/3proxy
/usr/bin/install -m 755 -d /root/3proxy-builder/usr/local/3proxy/conf
/usr/bin/install -m 755 -d /root/3proxy-builder/usr/local/3proxy/logs
/usr/bin/install -m 755 -d /root/3proxy-builder/usr/local/3proxy/count
/usr/bin/install -m 755 -d /root/3proxy-builder/usr/local/3proxy/libexec
/usr/bin/install -m 755 -s /bin/3proxy /bin/ftppr /bin/mycrypt /bin/pop3p /bin/proxy /bin/socks /bin/tcppm /bin/udppm /root/3proxy-builder/bin

strip -s /root/3proxy-builder/bin/*

cp -rfvd /usr/local/3proxy/* /root/3proxy-builder/usr/local/3proxy/

VERSION=`/bin/3proxy -h 2>&1|grep "proxy server"|cut -d" " -f5`

cd /root/3proxy-builder
TGZ="/root/3proxy-$VERSION.debian12.tar.gz"
tar -czvf $TGZ *

echo "$TGZ done"
 

