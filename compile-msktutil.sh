#!/usr/bin/env bash
# apt-get install libsasl2-dev
# git clone https://github.com/msktutil/msktutil.git
# cd msktutil
# ./autogen.sh
# ./configure --prefix=/usr


strip -s /usr/sbin/msktutil
mkdir -p /root/msktutil-builder/usr/sbin

cp -fd /usr/sbin/msktutil /root/msktutil-builder/usr/sbin/
VERSION=`/usr/sbin/msktutil -v | cut -d' ' -f3`
cd /root/msktutil-builder
tar -czf /root/msktutil-$VERSION.tar.gz *
echo "/root/msktutil-$VERSION.tar.gz done..."

