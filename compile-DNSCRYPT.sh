#!/bin/sh

# vf https://github.com/jedisct1/dnscrypt-proxy/releases/tag/2.0.18

# cp root/dnscrypt-proxy-2.0.18/linux-x86_64/dnscrypt-proxy /usr/sbin/dnscrypt-proxy

# export PATH=$PATH:/usr/local/go/bin
# git clone https://github.com/m13253/dns-over-https.git
# cd dns-over-https/
# mkdir /root/gopath
# export GOPATH=/root/gopath
# make
# make install

rm -rf /root/dnscrypt-proxy-builder||true
mkdir -p /root/dnscrypt-proxy-builder/usr/sbin
mkdir -p /root/dnscrypt-proxy-builder/usr/bin
chmod 0755 /usr/sbin/dnscrypt-proxy
strip -s "/usr/local/bin/doh-client"
strip -s "/usr/local/bin/doh-server"

cp -fd /usr/sbin/dnscrypt-proxy /root/dnscrypt-proxy-builder/usr/sbin/
cp -fd /usr/local/bin/doh-client /root/dnscrypt-proxy-builder/usr/bin/
cp -fd /usr/local/bin/doh-server /root/dnscrypt-proxy-builder/usr/bin/
cp -fd /usr/sbin/doh /root/dnscrypt-proxy-builder/usr/sbin/

VERSION="2.0.18"

cd /root/dnscrypt-proxy-builder

tar czvf /root/dnscrypt-proxy-$VERSION.tar.gz *
echo "/root/dnscrypt-proxy-$VERSION.tar.gz done"
rm -rf /root/dnscrypt-proxy-builder


