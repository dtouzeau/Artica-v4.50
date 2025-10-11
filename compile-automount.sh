#!/bin/sh
VERSION="5.1.8"
# apt-get install libtirpc-dev
# url https://mirrors.edge.kernel.org/pub/linux/daemons/autofs/v5/autofs-5.1.8.tar.xz

# wget https://mirrors.edge.kernel.org/pub/linux/daemons/autofs/v5/autofs-5.1.8.tar.xz

# ./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-option-checking --disable-silent-rules --libdir=\${prefix}/lib/x86_64-linux-gnu --disable-maintainer-mode --disable-dependency-tracking --disable-mount-locking --enable-force-shutdown --enable-ignore-busy --enable-sloppy-mount --mandir=/usr/share/man --with-confdir=/etc/default --with-mapdir=/etc --with-fifodir=/var/run --with-flagdir=/var/run --with-hesiod --with-sasl --with-libtirpc


rm -rf /root/autofs-compile

mkdir -p /root/autofs-compile/usr/lib/x86_64-linux-gnu/autofs
mkdir -p /root/autofs-compile/usr/sbin

cp -rfv --preserve=all /usr/lib/x86_64-linux-gnu/autofs/* /root/autofs-compile/usr/lib/x86_64-linux-gnu/autofs/

cp /usr/lib/x86_64-linux-gnu/libautofs.so /root/autofs-compile/usr/lib/x86_64-linux-gnu/
cp /usr/sbin/automount /root/autofs-compile/usr/sbin/

cd /root/autofs-compile
tar -czvf /root/autofs-$VERSION.tar.gz *
echo "/root/autofs-$VERSION.tar.gz DONE"