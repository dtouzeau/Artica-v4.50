#!/bin/sh

# git clone https://github.com/rsyslog/rsyslog.git

# apt-get install libestr-dev libfastjson-dev liblognorm-dev librelp-dev librdkafka-dev libmongoc-dev libczmq-dev libgcrypt20-dev libbson-dev        
#./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-option-checking --disable-silent-rules --libdir=\${prefix}/lib/x86_64-linux-gnu --runstatedir=/run --disable-maintainer-mode --disable-dependency-tracking --enable-imptcp --enable-imjournal --enable-omjournal --enable-kmsg --enable-mysql --enable-pgsql --enable-ommongodb --enable-elasticsearch --enable-imkafka --enable-omkafka --enable-mail --enable-imfile --enable-imfile-tests --enable-impstats --enable-klog --enable-gssapi-krb5 --enable-gnutls --enable-openssl --enable-relp --enable-snmp --enable-pmaixforwardedfrom --enable-pmciscoios --enable-pmcisconames --enable-pmlastmsg --enable-pmnormalize --enable-pmsnare --enable-omstdout --enable-omprog --enable-omuxsock --enable-mmanon --enable-mmnormalize --enable-mmjsonparse --enable-mmutf8fix --enable-mmpstrucdata --enable-mmsequence --enable-mmfields --enable-mmrm1stspace --enable-mmkubernetes --enable-imczmq --enable-omczmq --enable-omhiredis --enable-fmhash --disable-libgcrypt --enable-testbench --enable-extended-tests --enable-imdiag --disable-generate-man-pages --enable-omhttpfs --enable-fmhttp --enable-omhttp --disable-liblogging-stdlog --without-valgrind-testbench --disable-libsystemd --disable-journal-tests --disable-imjournal --enable-mmcount --disable-gnutls --disable-imjournal
         
#./autogen.sh  --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-option-checking --disable-silent-rules --libdir=\${prefix}/lib/x86_64-linux-gnu --runstatedir=/run --disable-maintainer-mode --disable-dependency-tracking --enable-imptcp --enable-imjournal --enable-omjournal --enable-kmsg --enable-mysql --enable-pgsql --enable-ommongodb --enable-elasticsearch --enable-imkafka --enable-omkafka --enable-mail --enable-imfile --enable-imfile-tests --enable-impstats --enable-klog --enable-gssapi-krb5 --enable-gnutls --enable-openssl --enable-relp --enable-snmp --enable-pmaixforwardedfrom --enable-pmciscoios --enable-pmcisconames --enable-pmlastmsg --enable-pmnormalize --enable-pmsnare --enable-omstdout --enable-omprog --enable-omuxsock --enable-mmanon --enable-mmnormalize --enable-mmjsonparse --enable-mmutf8fix --enable-mmpstrucdata --enable-mmsequence --enable-mmfields --enable-mmrm1stspace --enable-mmkubernetes --enable-imczmq --enable-omczmq --enable-omhiredis --enable-fmhash --disable-libgcrypt --enable-testbench --enable-extended-tests --enable-imdiag --disable-generate-man-pages --enable-omhttpfs --enable-fmhttp --enable-omhttp --disable-liblogging-stdlog --without-valgrind-testbench --disable-libsystemd --disable-journal-tests --disable-imjournal --enable-mmcount --disable-gnutls --disable-imjournal

        
        
#liblorng
#wget http://download.rsyslog.com/librelp/librelp-1.2.14.tar.gz
#./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-option-checking --disable-silent-rules --libdir=\${prefix}/lib/x86_64-linux-gnu --runstatedir=/run --disable-maintainer-mode --disable-dependency-tracking --enable-tls --enable-tls-openssl --disable-valgrind --disable-debug
        
        
#wget http://www.liblognorm.com/files/download/liblognorm-2.0.6.tar.gz
#./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-option-checking --disable-silent-rules --libdir=\${prefix}/lib/x86_64-linux-gnu --runstatedir=/run --disable-maintainer-mode --disable-dependency-tracking --enable-docs --enable-compile-warnings=yes --disable-docs


        
        
VERSION=`rsyslogd -v|grep compiled|cut -s -d' ' -f3`        

rm -rf /root/syslod-compile >/dev/null 2>&1        
mkdir -p /root/syslod-compile/usr/lib/x86_64-linux-gnu/rsyslog        
mkdir -p /root/syslod-compile/usr/sbin

cp -rfvd /usr/lib/x86_64-linux-gnu/rsyslog/* /root/syslod-compile/usr/lib/x86_64-linux-gnu/rsyslog/
cp -fd  /usr/sbin/rsyslogd /root/syslod-compile/usr/sbin/      

cd /root/syslod-compile
tar -czvf /root/syslod-compile-$VERSION.tar.gz *
echo "/root/syslod-compile-$VERSION.tar.gz done."

