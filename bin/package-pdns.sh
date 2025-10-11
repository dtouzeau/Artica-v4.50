#!/bin/sh
rm -f /root/pdns-builder.tar.gz
rm -rf /root/pdns-builder
mkdir -p /root/pdns-builder/usr/sbin
mkdir -p /root/pdns-builder/usr/bin
mkdir -p /root/pdns-builder/usr/lib/powerdns
mkdir -p /root/pdns-builder/usr/local/bin
strip -s /usr/sbin/pdns_recursor
strip -s /usr/sbin/pdns_server
strip -s /usr/bin/rec_control 
strip -s /usr/bin/pdns_control 
strip -s /usr/bin/pdnsutil 
strip -s /usr/bin/zone2sql 
strip -s /usr/bin/zone2json 
strip -s /usr/bin/dnsgram 
strip -s /usr/bin/dnsreplay 
strip -s /usr/bin/dnsscan 
strip -s /usr/bin/dnsscope 
strip -s /usr/bin/dnswasher 
strip -s /usr/bin/dumresp 
strip -s /usr/bin/pdns_notify 
strip -s /usr/bin/nproxy 
strip -s /usr/bin/nsec3dig 
strip -s /usr/bin/saxfr 
strip -s /usr/bin/stubquery 
strip -s /usr/bin/ixplore 
strip -s /usr/bin/sdig 
strip -s /usr/bin/calidns 
strip -s /usr/bin/dnsbulktest 
strip -s /usr/bin/dnstcpbench 
strip -s /usr/bin/zone2ldap

cp -fd /usr/sbin/pdns_recursor /root/pdns-builder/usr/sbin/
cp -fd /usr/sbin/pdns_server /root/pdns-builder/usr/sbin/
cp -fd /usr/bin/rec_control /root/pdns-builder/usr/bin/
cp -fd /usr/bin/pdns_control /root/pdns-builder/usr/bin/
cp -fd /usr/bin/pdnsutil /root/pdns-builder/usr/bin/
cp -fd /usr/bin/zone2sql /root/pdns-builder/usr/bin/
cp -fd /usr/bin/zone2json /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dnsgram /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dnsreplay /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dnsscan /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dnsscope /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dnswasher /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dumresp /root/pdns-builder/usr/bin/
cp -fd /usr/bin/pdns_notify /root/pdns-builder/usr/bin/
cp -fd /usr/bin/nproxy /root/pdns-builder/usr/bin/
cp -fd /usr/bin/nsec3dig /root/pdns-builder/usr/bin/
cp -fd /usr/bin/saxfr /root/pdns-builder/usr/bin/
cp -fd /usr/bin/stubquery /root/pdns-builder/usr/bin/
cp -fd /usr/bin/ixplore /root/pdns-builder/usr/bin/
cp -fd /usr/bin/sdig /root/pdns-builder/usr/bin/
cp -fd /usr/bin/calidns /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dnsbulktest /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dnstcpbench /root/pdns-builder/usr/bin/
cp -fd /usr/bin/zone2ldap /root/pdns-builder/usr/bin/
cp -fd /usr/local/bin/dsc /root/pdns-builder/usr/local/bin/


cp -rfd /usr/lib/powerdns/* /root/pdns-builder/usr/lib/powerdns/
chmod -R 0755 /root/pdns-builder/usr/bin /root/pdns-builder/usr/sbin /root/pdns-builder/usr/local/bin/

cd /root/pdns-builder
tar -czvf /root/pdns-builder.tar.gz *
cd /root

