#/bin/sh

# apt-get install swig protobuf-c-compiler libprotobuf-c-dev libfstrm-dev
#./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --with-ldns=/usr/local/lib --libdir=\${prefix}/lib/x86_64-linux-gnu --libexecdir=\${prefix}/lib/x86_64-linux-gnu  --disable-rpath --with-pidfile=/run/unbound.pid --with-rootkey-file=/var/lib/unbound/root.key --with-libevent --enable-cachedb --with-pythonmodule --with-pyunbound --enable-subnet --enable-dnstap --with-dnstap-socket-path=/run/dnstap.sock --libdir=/usr/lib

# wget https://nlnetlabs.nl/downloads/unbound/unbound-latest.tar.gz
#

rm -rf /root/unbound-builder
mkdir -p /root/unbound-builder/usr/lib/python2.7/dist-packages
mkdir -p /root/unbound-builder/usr/sbin

strip -s /usr/sbin/unbound
strip -s /usr/sbin/unbound-checkconf
strip -s /usr/sbin/unbound-control
strip -s /usr/sbin/unbound-host
strip -s /usr/sbin/unbound-anchor


cp -fd /usr/lib/python2.7/dist-packages/unboundmodule.py /root/unbound-builder/usr/lib/python2.7/dist-packages/
cp -fd /usr/lib/python2.7/dist-packages/_unbound.la /root/unbound-builder/usr/lib/python2.7/dist-packages/	  
cp -fd /usr/lib/python2.7/dist-packages/_unbound.so /root/unbound-builder/usr/lib/python2.7/dist-packages/
cp -fd /usr/lib/python2.7/dist-packages/unbound.py /root/unbound-builder/usr/lib/python2.7/dist-packages/
cp -fd /usr/lib/libunbound.a  /root/unbound-builder/usr/lib/
cp -fd /usr/lib/libunbound.la  /root/unbound-builder/usr/lib/
cp -fd /usr/lib/libunbound.so  /root/unbound-builder/usr/lib/
cp -fd /usr/lib/libunbound.so.8  /root/unbound-builder/usr/lib/
cp -fd /usr/lib/libunbound.so.8.0.3 /root/unbound-builder/usr/lib/

cp -fd /usr/sbin/unbound /root/unbound-builder/usr/sbin/
cp -fd /usr/sbin/unbound-checkconf /root/unbound-builder/usr/sbin/
cp -fd /usr/sbin/unbound-control /root/unbound-builder/usr/sbin/
cp -fd /usr/sbin/unbound-host /root/unbound-builder/usr/sbin/
cp -fd /usr/sbin/unbound-anchor /root/unbound-builder/usr/sbin/
cp -fd /usr/sbin/unbound-control-setup /root/unbound-builder/usr/sbin/

VERSION=`/usr/sbin/unbound -h | grep -E 'Version\s+[0-9\.]+' | cut -s -d' ' -f2`

echo "Building package for $VERSION"

cd /root/unbound-builder
rm -f /root/unbound-$VERSION.tar.gz
tar -czvf /root/unbound-$VERSION.tar.gz *

echo "/root/unbound-$VERSION.tar.gz done"
