#!/bin/bash
KEEPALIVED_VER="keepalived-2.2.7"
apt-get install libnl-3-dev
apt-get install libnl-genl-3-dev
apt-get install libglib2.0-dev
wget https://www.keepalived.org/software/$KEEPALIVED_VER.tar.gz -O /root/$KEEPALIVED_VER.tar.gz
tar -xf /root/$KEEPALIVED_VER.tar.gz -C /root/
cd /root/$KEEPALIVED_VER/
./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=${prefix}/include --mandir=${prefix}/share/man --infodir=${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-silent-rules --libdir=${prefix}/lib/x86_64-linux-gnu --libexecdir=${prefix}/lib/x86_64-linux-gnu --runstatedir=/run --disable-maintainer-mode  --enable-snmp --enable-sha1 --enable-snmp-rfcv2 --enable-snmp-rfcv3 --enable-dbus --enable-dbus-create-instance --enable-json --enable-bfd --disable-systemd
make
make install
rm -rf /root/keepalived-builder
mkdir -p /root/keepalived-builder/usr/include/libnl3/
mkdir -p /root/keepalived-builder/usr/sbin/
mkdir -p /root/keepalived-builder/usr/bin/
mkdir -p /root/keepalived-builder/usr/share/doc/keepalived/
mkdir -p /root/keepalived-builder/etc/keepalived/
mkdir -p /root/keepalived-builder/etc/sysconfig/

strip -s /usr/sbin/keepalived
strip -s /usr/bin/genhash
cp -rfd /usr/sbin/keepalived /root/keepalived-builder/usr/sbin/
cp -rfd /usr/bin/genhash /root/keepalived-builder/usr/bin/
cp -rfd /usr/include/libnl3/* /root/keepalived-builder/usr/include/libnl3/
cp -rfd /usr/share/doc/keepalived/* /root/keepalived-builder/usr/share/doc/keepalived/
cp -rfd /etc/sysconfig/* /root/keepalived-builder/etc/sysconfig/
cp -rfd /etc/keepalived/* /root/keepalived-builder/etc/keepalived/
cd /root/keepalived-builder
tar -czvf /root/$KEEPALIVED_VER.tar.gz *
echo "/root/keepalived-builder-$KEEPALIVED_VER.tar.gz done"