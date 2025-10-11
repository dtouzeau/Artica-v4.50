#!/bin/sh

# apt-get install libmilter-dev libdkim-dev libspf2-2 libgeoip-dev libcurl4-openssl-dev libspf2-dev
# http://lcamtuf.coredump.cx/p0f3/releases/p0f-3.09b.tgz
# tar -xf p0f-3.09b.tgz
# cd p0f-3.09b 
# ./build.sh

# cp -fp /root/p0f-3.09b/p0f /usr/sbin/p0f
# mkdir -p /usr/src/p0f
# cp -fpr /root/p0f-3.09b/* /usr/src/p0f/
#./configure --enable-postfix --enable-spamassassin --with-thread-safe-resolver --with-libGeoIP=/usr/lib/x86_64-linux-gnu --enable-mx --enable-dnsrbl --with-p0f-src=/usr/src/p0f --with-libspf2=/usr/lib CFLAGS="-L/usr/lib/libmilter -L/lib -L/usr/lib -L/usr/local/lib"

strip -s /usr/local/bin/milter-greylist
mkdir -p /root/milter-greylist-builder/usr/sbin
mkdir -p /root/milter-greylist-builder/etc/milter-greylist
mkdir -p /root/milter-greylist-builder/var/run/milter-greylist
mkdir -p /root/milter-greylist-builder/var/milter-greylist
cp -fd /usr/local/bin/milter-greylist /root/milter-greylist-builder/usr/sbin/

cd /root/milter-greylist-builder/
tar -czf /root/milter-greylist-4.6.2.tar.gz *
echo "/root/milter-greylist-4.6.2.tar.gz done"
