#!/bin/sh 
SURI_VER="suricata-7.0.8"
apt-get install linux-headers-$(uname -r) git build-essential bison flex git libnspr4-dev libjansson-dev libnss3-dev cmake ragel libyaml-dev libcap-ng-dev libmagic-dev libnl-genl-3-dev

TARGETMOD="/root/suricata-builder/lib/modules"
# https://github.com/thom311/libnl.git
# wget https://github.com/thom311/libnl/releases/download/libnl3_11_0/libnl-3.11.0.tar.gz
# cd libnl-3.11.0
# ./configure --prefix=/usr && make && make install

rm -rf /root/PF_RING
rm -f /usr/local/lib/libpfring*
git clone https://github.com/ntop/PF_RING.git /root/PF_RING
cd "/root/PF_RING/kernel" || exit 0
make && make install
insmod ./pf_ring.ko
cd "/root/PF_RING/userland" || exit 0
make && make install

rm -rf /root/suricata-builder

mkdir -p /root/suricata-builder/usr/local/lib/libnl
mkdir -p /root/suricata-builder/lib/x86_64-linux-gnu
mkdir -p $TARGETMOD/4.9.0-4-amd64/kernel/net/pf_ring/
mkdir -p $TARGETMOD/4.9.0-8-amd64/kernel/net/pf_ring/
mkdir -p $TARGETMOD/4.9.0-9-amd64/kernel/net/pf_ring/
mkdir -p $TARGETMOD/4.19.0-20-amd64/kernel/net/pf_ring/
mkdir -p $TARGETMOD/4.19.0-21-amd64/kernel/net/pf_ring/
mkdir -p $TARGETMOD/4.19.0-24-amd64/kernel/net/pf_ring/
mkdir -p $TARGETMOD/$(uname -r)/kernel/net/pf_ring
mkdir -p /root/suricata-builder/usr/lib/python2.7/site-packages
mkdir -p /root/suricata-builder/usr/bin
mkdir -p /root/suricata-builder/usr/share/suricata/rules
mkdir -p /root/suricata-builder/etc/suricata/

# cp -rfvd /usr/lib/libnl/* /root/suricata-builder/usr/local/lib/libnl/
# cp -fvd /usr/lib/libnl-* /root/suricata-builder/usr/local/lib/
cp -fvd /usr/local/lib/libpcap* /root/suricata-builder/usr/local/lib/

cp -fd /lib/modules/4.9.0-4-amd64/kernel/net/pf_ring/pf_ring.ko $TARGETMOD/4.9.0-4-amd64/kernel/net/pf_ring/
cp -fd /lib/modules/4.9.0-8-amd64/kernel/net/pf_ring/pf_ring.ko $TARGETMOD/4.9.0-8-amd64/kernel/net/pf_ring/
cp -fd /lib/modules/4.9.0-9-amd64/kernel/net/pf_ring/pf_ring.ko $TARGETMOD/4.9.0-9-amd64/kernel/net/pf_ring/
cp -fd /lib/modules/4.19.0-20-amd64/kernel/net/pf_ring/pf_ring.ko $TARGETMOD/4.19.0-20-amd64/kernel/net/pf_ring/
cp -fd /lib/modules/4.19.0-21-amd64/kernel/net/pf_ring/pf_ring.ko $TARGETMOD/4.19.0-21-amd64/kernel/net/pf_ring/
cp -fd /lib/modules/$(uname -r)/kernel/net/pf_ring/pf_ring.ko $TARGETMOD/$(uname -r)/kernel/net/pf_ring/

rm -f /root/$SURI_VER.tar.gz || true
rm -rf /root/$SURI_VER || true

wget https://www.openinfosecfoundation.org/download/$SURI_VER.tar.gz -O /root/$SURI_VER.tar.gz
tar -xf /root/$SURI_VER.tar.gz -C /root/
cd /root/$SURI_VER/ || exit 1
CFLAGS="-D_GNU_SOURCE" LIBS="-lrt"  ./configure --enable-python --enable-pfring --enable-geoip --disable-gccmarch-native --with-libjansson --prefix=/usr/ --sysconfdir=/etc --localstatedir=/var --with-libpfring-includes=/usr/local/pfring/include/ --with-libpfring-libraries=/usr/local/lib/ --with-libpcap-includes=/usr/local/pfring/include/ --with-libpcap-libraries=/usr/local/lib/ --with-libnss-libraries=/usr/lib --with-libnss-includes=/usr/include/nss/ --with-libnspr-libraries=/usr/lib --with-libnspr-includes=/usr/include/nspr --with-libgeoip-includes=/usr/include --with-libgeoip-libraries=/usr/lib/x86_64-linux-gnu/
make
make install-full

strip -s /usr/bin/suricata
cp -rfvd /usr/lib/python2.7/site-packages/suricata* /root/suricata-builder/usr/lib/python2.7/site-packages/
cp -rfd /usr/bin/suricatasc /root/suricata-builder/usr/bin/
cp -rfd /usr/bin/suricatactl /root/suricata-builder/usr/bin/
cp -rfd /usr/bin/suricata-update /root/suricata-builder/usr/bin/
cp -rfd /usr/bin/suricata /root/suricata-builder/usr/bin/
cp -fvd /usr/lib/libhtp*  /root/suricata-builder/usr/lib/
cp -rfd /usr/share/suricata/* /root/suricata-builder/usr/share/suricata/
cp -rfd /etc/suricata/* /root/suricata-builder/etc/suricata/
rm -f /root/suricata-builder/etc/suricata/suricata.yaml

cp -fd /usr/local/lib/libpfring*  /root/suricata-builder/lib/x86_64-linux-gnu/
cp /usr/local/lib/libpfring.so.8.5.0 /root/suricata-builder/lib/x86_64-linux-gnu/libpfring.so.1

cd /root/suricata-builder || exit 1
version=$(/usr/bin/suricata -V | grep -oP '(?<=Suricata version )[\d.]+')

tar -czvf /root/suricata-builder-$version.tar.gz *
echo "/root/suricata-builder-$version.tar.gz done"