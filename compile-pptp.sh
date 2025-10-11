#!/bin/sh
# see https://sourceforge.net/projects/pptpclient/files/pptp/
# wget "https://downloads.sourceforge.net/project/pptpclient/pptp/pptp-1.10.0/pptp-1.10.0.tar.gz?r=https%3A%2F%2Fsourceforge.net%2Fprojects%2Fpptpclient%2Ffiles%2Fpptp%2Fpptp-1.10.0%2Fpptp-1.10.0.tar.gz%2Fdownload&ts=1604311389" -O pptp-1.10.0.tar.gz

# cd /root//pptp-1.10.0
# make
# make install

strip -s /usr/sbin/pptp
strip -s /usr/sbin/pptpsetup

mkdir -p /root/pptp-builder/usr/sbin
mkdir -p /root/pptp-builder/etc/ppp
mkdir -p /root/pptp-builder/usr/share/man/man8

cp -fd /usr/sbin/pptp /root/pptp-builder/usr/sbin/
cp -fd /usr/sbin/pptpsetup /root/pptp-builder/usr/sbin/
cp -fd /usr/share/man/man8/pptp.8 /root/pptp-builder/usr/share/man/man8/
cp -fd /usr/share/man/man8/pptpsetup.8 /root/pptp-builder/usr/share/man/man8/
cp -fd /etc/ppp/options.pptp /root/pptp-builder/etc/ppp/

cd /root/pptp-builder

tar -czvf /root/pptp-builder.1.10.0.tar.gz *
echo "/root/pptp-builder.1.10.0.tar.gz done."

