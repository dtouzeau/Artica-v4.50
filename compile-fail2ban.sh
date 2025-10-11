#!/bin/sh

# git clone https://github.com/fail2ban/fail2ban.git
# cd fail2ban
# python setup.py install

mkdir -p /root/fail2ban-compiled/usr/lib/python2.7/dist-packages/fail2ban/
mkdir -p /root/fail2ban-compiled/usr/bin
mkdir -p /root/fail2ban-compiled/var/lib/fail2ban
mkdir -p /root/fail2ban-compiled/etc/fail2ban


cp -rfvd /usr/local/lib/python2.7/dist-packages/fail2ban/* /root/fail2ban-compiled/usr/lib/python2.7/dist-packages/fail2ban/
cp -fd /usr/local/lib/python2.7/dist-packages/fail2ban-*.dev3.egg-info /root/fail2ban-compiled/usr/lib/python2.7/dist-packages/


cp -fd /usr/local/bin/fail2ban-server /root/fail2ban-compiled/usr/bin/
cp -fd /usr/local/bin/fail2ban-testcases /root/fail2ban-compiled/usr/bin/
cp -fd /usr/local/bin/fail2ban-client /root/fail2ban-compiled/usr/bin/
cp -fd /usr/local/bin/fail2ban-regex /root/fail2ban-compiled/usr/bin/

VERSION=`fail2ban-server -V`

cd /root/fail2ban-compiled
tar -czvf /root/fail2ban-$VERSION.tar.gz *
rm -rf /root/fail2ban-compiled
echo "/root/fail2ban-$VERSION.tar.gz done"


