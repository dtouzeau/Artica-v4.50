#!/bin/sh
# https://sourceforge.net/projects/c-icap/files/c-icap/0.5.x/c_icap-0.5.5.tar.gz/download#
# wget https://sourceforge.net/projects/c-icap/files/c-icap-modules/0.5.x/c_icap_modules-0.5.3.tar.gz/download# -O c_icap_modules-0.5.3.tar.gz
# tar -xf 
# ./configure --enable-static --prefix=/usr --includedir="\${prefix}/include" --enable-large-files --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/c-icap" --with-memcached --with-brotli --with-bzlib --with-zlib
# Modules:
# ./configure --enable-static --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --libexecdir="\${prefix}/lib/c-icap" --with-clamav --with-bdb
mkdir -p /root/c-icap-builder/usr/bin
mkdir -p /root/c-icap-builder/etc
mkdir -p /root/c-icap-builder/usr/lib/c_icap
mkdir -p /root/c-icap-builder/usr/share/c_icap


strip -s /usr/bin/c-icap-client             
strip -s /usr/bin/c-icap-mkbdb              
strip -s /usr/bin/c-icap-stretch
strip -s /usr/bin/c-icap
strip -s /usr/bin/c-icap-config 
strip -s /usr/bin/c-icap-libicapapi-config
                 
cp -fd /usr/bin/c-icap-client  /root/c-icap-builder/usr/bin/              
cp -fd /usr/bin/c-icap-mkbdb /root/c-icap-builder/usr/bin/                
cp -fd /usr/bin/c-icap-stretch /root/c-icap-builder/usr/bin/   
cp -fd /usr/bin/c-icap /root/c-icap-builder/usr/bin/   
cp -fd /usr/bin/c-icap-config  /root/c-icap-builder/usr/bin/   
cp -fd /usr/bin/c-icap-libicapapi-config /root/c-icap-builder/usr/bin/   

cp -fd /etc/c-icap.conf /root/c-icap-builder/etc/
cp -fd /etc/c-icap.magic /root/c-icap-builder/etc/

cp -fd /usr/lib/libicapapi.la /root/c-icap-builder/usr/lib/	
cp -fd /usr/lib/libicapapi.so /root/c-icap-builder/usr/lib/	
cp -fd /usr/lib/libicapapi.so.5 /root/c-icap-builder/usr/lib/  
cp -fd /usr/lib/libicapapi.so.5.0.5 /root/c-icap-builder/usr/lib/

cp -rfd /usr/lib/c_icap/* /root/c-icap-builder/usr/lib/c_icap/
cp -rfd /usr/share/c_icap/* /root/c-icap-builder/usr/share/c_icap/

cd /root/c-icap-builder
tar -czvf /root/c-icap-builder.tar.gz *
echo "/root/c-icap-builder.tar.gz done"
cd /root


