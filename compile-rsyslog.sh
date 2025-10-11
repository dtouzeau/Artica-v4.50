#!/bin/sh
# https://sourceforge.net/projects/c-icap/files/c-icap/0.5.x/c_icap-0.5.5.tar.gz/download#
# wget https://sourceforge.net/projects/c-icap/files/c-icap-modules/0.5.x/c_icap_modules-0.5.3.tar.gz/download# -O c_icap_modules-0.5.3.tar.gz
# tar -xf 
  #	./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-option-checking --disable-silent-rules --libdir=\${prefix}/lib/x86_64-linux-gnu --runstatedir=/run --disable-maintainer-mode --disable-dependency-tracking --enable-imptcp --enable-imjournal --enable-omjournal --enable-kmsg --enable-mysql --enable-pgsql --enable-ommongodb --enable-elasticsearch --enable-imkafka --enable-omkafka --enable-mail --enable-imfile --enable-imfile-tests --enable-impstats --enable-klog --enable-gssapi-krb5 --enable-gnutls --enable-openssl --enable-relp --enable-snmp --enable-pmaixforwardedfrom --enable-pmciscoios --enable-pmcisconames --enable-pmlastmsg --enable-pmnormalize --enable-pmsnare --enable-omstdout --enable-omprog --enable-omuxsock --enable-mmanon --enable-mmnormalize --enable-mmjsonparse --enable-mmutf8fix --enable-mmpstrucdata --enable-mmsequence --enable-mmfields --enable-mmrm1stspace --enable-mmkubernetes --enable-imczmq --enable-omczmq --enable-omhiredis --enable-fmhash --disable-libgcrypt --enable-testbench --enable-extended-tests --enable-imdiag --disable-generate-man-pages --disable-fmhttp --disable-liblogging-stdlog --without-valgrind-testbench --with-systemdsystemunitdir=/lib/systemd/system
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


