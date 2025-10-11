#!/bin/sh


echo "Building ipset..."
rm -rf /root/ipset || true
git clone git://git.netfilter.org/ipset.git /root/ipset
cd /root/ipset
./autogen.sh
./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-silent-rules --libdir=\${prefix}/lib/x86_64-linux-gnu --libexecdir=\${prefix}/lib/x86_64-linux-gnu --disable-dependency-tracking --sbindir=/sbin --libdir=/lib/x86_64-linux-gnu --with-kmod=no --enable-settype-modules --disable-silent-rules
make || exit 1
make install || exit 1
strip -s /sbin/ipset


echo "Building iprange..."

rm -rf /root/iprange || true
git clone https://github.com/firehol/iprange.git /root/iprange


cd  /root/iprange || exit 1
./autogen.sh || exit 1
./configure --prefix=/usr CFLAGS="-O2" --disable-man || exit 1
make clean
make || exit 1
make install || exit 1
strip -s /usr/bin/iprange

echo "Building firehol..."

rm -rf /root/firehol || true
rm -rf /usr/lib/x86_64-linux-gnu/firehol

git clone https://github.com/firehol/firehol.git /root/firehol
cd /root/firehol
./autogen.sh || exit 1
./configure --prefix=/usr --sysconfdir=/etc --localstatedir=/var --disable-man --disable-doc || exit 1

./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-silent-rules --libdir=\${prefix}/lib/x86_64-linux-gnu --libexecdir=\${prefix}/lib/x86_64-linux-gnu --runstatedir=/run  --disable-man --disable-doc|| exit 1
make clean
make || exit 1
make install || exit 1
rm -rf /root/firehol || true

rm -rf /root/firehol-builder
mkdir -p /root/firehol-builder/sbin
mkdir -p /root/firehol-builder/usr/bin
mkdir -p /root/firehol-builder/usr/sbin
mkdir -p /root/firehol-builder/lib/x86_64-linux-gnu
mkdir -p /root/firehol-builder/usr/lib/x86_64-linux-gnu/firehol

cp -fd /usr/bin/iprange /root/firehol-builder/usr/bin/
cp -fd /sbin/ipset /root/firehol-builder/sbin/
cp -fd /lib/x86_64-linux-gnu/libipset.a  /root/firehol-builder/lib/x86_64-linux-gnu/
cp -fd /lib/x86_64-linux-gnu/libipset.la  /root/firehol-builder/lib/x86_64-linux-gnu/
cp -fd /lib/x86_64-linux-gnu/libipset.so /root/firehol-builder/lib/x86_64-linux-gnu/	
cp -fd /lib/x86_64-linux-gnu/libipset.so.13 /root/firehol-builder/lib/x86_64-linux-gnu/  
cp -fd /lib/x86_64-linux-gnu/libipset.so.13.1.0 /root/firehol-builder/lib/x86_64-linux-gnu/
cp -rfd /usr/lib/x86_64-linux-gnu/firehol/* /root/firehol-builder/usr/lib/x86_64-linux-gnu/firehol/

cp -fd /usr/sbin/firehol /root/firehol-builder/usr/sbin/
cp -fd /usr/sbin/fireqos /root/firehol-builder/usr/sbin/
cp -fd /usr/sbin/link-balancer /root/firehol-builder/usr/sbin/
cp -fd /usr/sbin/update-ipsets /root/firehol-builder/usr/sbin/
cp -fd /usr/sbin/vnetbuild /root/firehol-builder/usr/sbin/

cd /root/firehol-builder
tar -czf /root/firehol-xxx.tar.gz *
cd /root
rm -rf /root/firehol-builder

echo "/root/firehol-xxx.tar.gz"











