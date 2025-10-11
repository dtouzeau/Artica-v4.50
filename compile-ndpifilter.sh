#!/bin/sh



# wget https://github.com/vel21ripn/nDPI/archive/flow_info.zip -O /root/flow_info.zip
# unzip flow_info.zip
# cd /root/nDPI-flow_info && ./autogen.sh && ./configure --prefix=/usr/local/ndpi --sysconfdir=/etc/ndpi && make && make install
# cd /root/nDPI-flow_info/ndpi-netfilter && make && make modules_install

VIRTUAL_VERSION="2.8.5"
CURRENT_KERNEL=`uname -r`

cp /root/nDPI-flow_info/ndpi-netfilter/src/xt_ndpi.ko /lib/modules/$CURRENT_KERNEL/extra/

if [ ! -f /lib/modules/$CURRENT_KERNEL/extra/xt_ndpi.ko ];then
  echo "/lib/modules/$CURRENT_KERNEL/extra/xt_ndpi.ko no such file"
  exit 1
fi

strip --strip-debug /lib/modules/4.19.0-8-amd64/extra/xt_ndpi.ko
strip --strip-debug /lib/modules/4.19.0-10-amd64/extra/xt_ndpi.ko
strip --strip-debug /lib/modules/4.19.0-12-amd64/extra/xt_ndpi.ko
strip --strip-debug /lib/modules/4.19.0-13-amd64/extra/xt_ndpi.ko
strip --strip-debug /lib/modules/4.19.0-17-amd64/extra/xt_ndpi.ko
strip --strip-debug /lib/modules/4.19.0-24-amd64/extra/xt_ndpi.ko
strip --strip-debug /lib/modules/4.19.0-25-amd64/extra/xt_ndpi.ko
strip --strip-debug /root/nDPI-flow_info/ndpi-netfilter/ipt/libxt_ndpi.so

mkdir -p /root/ndpi-netfilter/usr/local/lib
mkdir -p /root/ndpi-netfilter/lib/modules/4.19.0-12-amd64/extra
mkdir -p /root/ndpi-netfilter/lib/modules/4.19.0-13-amd64/extra
mkdir -p /root/ndpi-netfilter/lib/modules/4.19.0-8-amd64/extra
mkdir -p /root/ndpi-netfilter/lib/modules/4.19.0-9-amd64/extra
mkdir -p /root/ndpi-netfilter/lib/modules/4.19.0-10-amd64/extra
mkdir -p /root/ndpi-netfilter/lib/modules/4.19.0-17-amd64/extra
mkdir -p /root/ndpi-netfilter/lib/modules/4.19.0-24-amd64/extra
mkdir -p /root/ndpi-netfilter/lib/modules/4.19.0-25-amd64/extra
mkdir -p /root/ndpi-netfilter/usr/lib/x86_64-linux-gnu/xtables
mkdir -p /root/ndpi-netfilter/lib/modules/4.19.0-25-amd64/extra/
mkdir -p /root/ndpi-netfilter/usr/share/nDPI
mkdir -p /root/ndpi-netfilter/usr/sbin
mkdir -p /root/ndpi-netfilter/usr/local/bin
mkdir -p /root/ndpi-builder/usr/share/nDPI


cp /root/nDPI-flow_info/ndpi-netfilter/ipt/libxt_ndpi.so /root/ndpi-netfilter/usr/lib/x86_64-linux-gnu/xtables/
cp /usr/local/bin/ndpi_network_list_compile /root/ndpi-netfilter/usr/local/bin/
cp /usr/local/bin/ndpiReader /root/ndpi-netfilter/usr/local/bin/
cp /usr/local/lib/libndpi.a /root/ndpi-netfilter/usr/local/lib/

echo $VIRTUAL_VERSION > /root/ndpi-builder/usr/share/nDPI/VERSION


cp -rfd /usr/local/ndpi/bin/* /root/ndpi-netfilter/usr/sbin/
cp -rfd /usr/local/ndpi/lib/* /root/ndpi-netfilter/usr/local/lib/
cp -rfd /lib/modules/4.19.0-17-amd64/extra/* /root/ndpi-netfilter/lib/modules/4.19.0-13-amd64/extra/
cp -rfd /lib/modules/4.19.0-13-amd64/extra/* /root/ndpi-netfilter/lib/modules/4.19.0-13-amd64/extra/
cp -rfd /lib/modules/4.19.0-12-amd64/extra/* /root/ndpi-netfilter/lib/modules/4.19.0-12-amd64/extra/
cp -rfd /lib/modules/4.19.0-8-amd64/extra/* /root/ndpi-netfilter/lib/modules/4.19.0-8-amd64/extra/
cp -rfd /lib/modules/4.19.0-9-amd64/extra/* /root/ndpi-netfilter/lib/modules/4.19.0-9-amd64/extra/
cp -rfd /lib/modules/4.19.0-10-amd64/extra/* /root/ndpi-netfilter/lib/modules/4.19.0-10-amd64/extra/
cp -rfd /lib/modules/4.19.0-24-amd64/extra/* /root/ndpi-netfilter/lib/modules/4.19.0-24-amd64/extra/
cp -rfd /lib/modules/4.19.0-25-amd64/extra/* /root/ndpi-netfilter/lib/modules/4.19.0-25-amd64/extra/
cp -rfd /lib/modules/$CURRENT_KERNEL/extra/* /root/ndpi-netfilter/lib/modules/$CURRENT_KERNEL/extra/
cd /root/ndpi-netfilter
tar -czf /root/ndpi-netfilter-$VIRTUAL_VERSION.tar.gz *
echo "/root/ndpi-netfilter-$VIRTUAL_VERSION.tar.gz done"
