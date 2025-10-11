#!/bin/sh 
# apt-get install libcap-ng-dev
# git clone https://github.com/linux-audit/audit-userspace.git
# cd audit-userspace
# ./autogen.sh
# ./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-option-checking --disable-silent-rules --libdir=\${prefix}/lib/x86_64-linux-gnu --runstatedir=/run --disable-maintainer-mode --disable-dependency-tracking --sbindir=/sbin --libdir=/lib/x86_64-linux-gnu --enable-shared=audit --enable-gssapi-krb5 --with-apparmor --with-libwrap --with-libcap-ng --without-python --without-python3 --with-arm --with-aarch64 --disable-systemd

# mkdir -p /root/auditd-compile/lib/x86_64-linux-gnu
# mkdir -p /root/auditd-compile/usr/sbin
# mkdir -p /root/auditd-compile/etc/audit

VERSION=3.1.1

cp -rfv /lib/x86_64-linux-gnu/libaudit* /root/auditd-compile/lib/x86_64-linux-gnu/
cp -rfv /lib/x86_64-linux-gnu/libauparse* /root/auditd-compile/lib/x86_64-linux-gnu/
cp -rfv /etc/audit/* /root/auditd-compile/etc/audit/

cp -f /sbin/audisp-af_unix /root/auditd-compile/usr/sbin/
cp -f /sbin/audisp-remote /root/auditd-compile/usr/sbin/
cp -f /sbin/audisp-syslog /root/auditd-compile/usr/sbin/
cp -f /sbin/audispd-zos-remote /root/auditd-compile/usr/sbin/
cp -f /sbin/auditd /root/auditd-compile/usr/sbin/
cp -f /sbin/auditctl /root/auditd-compile/usr/sbin/
cp -f /sbin/aureport /root/auditd-compile/usr/sbin/
cp -f /sbin/ausearch /root/auditd-compile/usr/sbin/
cp -f /sbin/autrace /root/auditd-compile/usr/sbin/

cd /root/auditd-compile 
tar -czf /root/auditd-$VERSION.tar.gz *

