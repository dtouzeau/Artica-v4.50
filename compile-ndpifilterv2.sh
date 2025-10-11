#!/bin/sh
# ndpi 2.8.0
# wget https://github.com/vel21ripn/nDPI/archive/flow_info.zip
# apt-get install git autoconf automake autogen libpcap-dev libtool git autoconf automake autogen libpcap-dev libtool libxtables-dev pkg-config linux-headers-$(uname -r)
# unzip flow_info.zip
# cd nDPI-flow_info
# ./autogen.sh 
# make && make install
# cd ndpi-netfilter
# make
# make modules_install

# /usr/lib/modules/4.19.0-27-amd64/extra/xt_ndpi.ko
# /root/VMNDPI/usr/lib/modules/4.19.0-27-amd64/extra/xt_ndpi.ko
# cp /root/VMNDPI/root/nDPI-flow_info/ndpi-netfilter/ipt/libxt_ndpi.so /usr/lib/x86_64-linux-gnu/xtables/libxt_ndpi.so

mkdir -p /root/ndpi-builder/usr/local/lib
mkdir -p /root/ndpi-builder/lib/modules/4.9.0-8-amd64/extra
mkdir -p /root/ndpi-builder/lib/modules/4.9.0-9-amd64/extra
mkdir -p /root/ndpi-builder/usr/lib/x86_64-linux-gnu/xtables
mkdir -p /root/ndpi-builder/usr/share/nDPI

echo "2.8.0" > /root/ndpi-builder/usr/share/nDPI/VERSION


cp -fd /usr/local/lib/libndpi* /root/ndpi-builder/usr/local/lib/
cp -fd /lib/modules/4.9.0-8-amd64/extra/xt_ndpi.ko /root/ndpi-builder/lib/modules/4.9.0-8-amd64/extra/
cp -fd /lib/modules/4.9.0-9-amd64/extra/xt_ndpi.ko /root/ndpi-builder/lib/modules/4.9.0-9-amd64/extra/
cp -fd /usr/lib/x86_64-linux-gnu/xtables/libxt_ndpi.so /root/ndpi-builder/usr/lib/x86_64-linux-gnu/xtables/

cd /root/ndpi-builder
tar -czvf /root/ndpi-builder-2.8.0.tar.gz *
echo "/root/ndpi-builder-2.8.0.tar.gz OK"

