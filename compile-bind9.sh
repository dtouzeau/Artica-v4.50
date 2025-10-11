#!/usr/bin/env bash

# apt-get install liburcu-dev libuv1-dev python3-sphinx
# wget https://downloads.isc.org/isc/bind9/9.20.0/bind-9.20.0.tar.xz


# ./configure --prefix=/usr/local/ddns-agent --without-openssl --disable-doh  --without-libnghttp2  --disable-geoip --disable-dnsrps

# make && make install

strip -s /usr/local/ddns-agent/sbin/named
mv /usr/local/ddns-agent/sbin/named /usr/local/ddns-agent/sbin/ddns-agent
version=$(/usr/local/ddns-agent/sbin/ddns-agent -v | sed -n 's/^BIND \([0-9.]*\).*/\1/p')

mkdir -p /root/compile-ddns-agent-$version/usr/local/ddns-agent
cp -rf /usr/local/ddns-agent/ /root/compile-ddns-agent-$version/usr/local/ddns-agent/
cd /root/compile-ddns-agent-$version
chmod 0755 /root/compile-ddns-agent-$version/usr/local/ddns-agent/*
tar -czf /root/ddns-agent-$version.tar.gz *
echo "/root/compile-ddns-agent-$version.tar.gz done"