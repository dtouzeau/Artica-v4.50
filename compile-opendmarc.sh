#!/bin/sh


mkdir -p /root/opendmarc-builder/usr/local/lib
mkdir -p /root/opendmarc-builder/usr/local/sbin

cp -rfd /usr/local/lib/libopendmarc* /root/opendmarc-builder/usr/local/lib/

strip -s /usr/local/sbin/opendmarc-expire 
strip -s /usr/local/sbin/opendmarc-import 
strip -s /usr/local/sbin/opendmarc-importstats 
strip -s /usr/local/sbin/opendmarc-params 
strip -s /usr/local/sbin/opendmarc-reports
strip -s /usr/local/sbin/opendmarc 
strip -s /usr/local/sbin/opendmarc-check


cp -fd /usr/local/sbin/opendmarc-expire /root/opendmarc-builder/usr/local/sbin/
cp -fd /usr/local/sbin/opendmarc-import /root/opendmarc-builder/usr/local/sbin/
cp -fd /usr/local/sbin/opendmarc-importstats /root/opendmarc-builder/usr/local/sbin/
cp -fd /usr/local/sbin/opendmarc-params /root/opendmarc-builder/usr/local/sbin/
cp -fd /usr/local/sbin/opendmarc-reports /root/opendmarc-builder/usr/local/sbin/
cp -fd /usr/local/sbin/opendmarc /root/opendmarc-builder/usr/local/sbin/
cp -fd /usr/local/sbin/opendmarc-check /root/opendmarc-builder/usr/local/sbin/

cd /root/opendmarc-builder
rm /root/opendmarc-builder.tar.gz || true
tar -czf /root/opendmarc-builder.tar.gz *
echo "/root/opendmarc-builder.tar.gz done"
