#!/bin/sh

# ./configure -with-srv-conf-file=/etc/dhcp3/dhcpd.conf --with-srv-pid-file=/var/run/dhcpd.pid --with-srv6-lease-file=/var/lib/dhcp3/dhcpd6.leases --with-srv-lease-file=/var/lib/dhcp3/dhcpd.leases  --prefix=/usr --with-cli-lease-file=/var/lib/dhcp/dhclient.leases --enable-log-pid --enable-paranoia --enable-binary-leases CFLAGS="-g -O2 -D_PATH_DHCLIENT_SCRIPT='\"/sbin/dhclient-script\"' -D_PATH_DHCLIENT_CONF='\"/etc/dhcp/dhclient.conf\"' -D_PATH_DHCLIENT_DB='\"/var/lib/dhcp/dhclient.leases\"' -D_PATH_DHCLIENT6_DB='\"/var/lib/dhcp/dhclient6.leases\"' -DNSUPDATE"

# cd source-of-dhcpd
# git clone https://github.com/parsley42/omcmd.git
# ln -s /root/dhcp-4.4.1/bind/include/isc /usr/include/isc
# ln -s /root/dhcp-4.4.1/bind/include/dns /usr/include/dns
# ln -s /root/dhcp-4.4.1/bind/include/dst /usr/include/dst

rm -rf /root/dhcpd-builder
mkdir -p /root/dhcpd-builder/usr/bin
mkdir -p /root/dhcpd-builder/usr/sbin
mkdir -p /root/dhcpd-builder/usr/lib

strip -s /usr/sbin/dhcrelay
strip -s /usr/sbin/dhcpd
strip -s /usr/bin/omshell
strip -s /usr/sbin/dhclient


cp -fd /usr/lib/libdhcp.a /root/dhcpd-builder/usr/lib/
cp -fd /usr/lib/libdhcpctl.a /root/dhcpd-builder/usr/lib/
cp -fd /usr/lib/libomapi.a /root/dhcpd-builder/usr/lib/

cp -fd /usr/bin/omcmd /root/dhcpd-builder/usr/bin/
cp -fd /usr/sbin/dhcrelay /root/dhcpd-builder/usr/sbin/
cp -fd /usr/sbin/dhcpd /root/dhcpd-builder/usr/sbin/
cp -fd /usr/bin/omshell /root/dhcpd-builder/usr/bin/
cp -fd /usr/sbin/dhclient /root/dhcpd-builder/usr/sbin/


VERSION=`dhcpd --version 2>&1`
echo "Version: $VERSION"
FILEPATH="/root/$VERSION.tar.gz"
rm -f $FILEPATH
cd /root/dhcpd-builder
tar czf $FILEPATH *
echo $FILEPATH
