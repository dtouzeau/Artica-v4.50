#!/bin/sh
# Apt-get install libpcre3 libpcre3-dev liblmdb-dev libsqlite3-dev libdb5.3-dev libcdb-dev postgresql-server-dev-11
#/bin/mv /usr/sbin/sendmail /usr/sbin/sendmail.OFF >/dev/null
#/bin/mv /usr/bin/newaliases /usr/bin/newaliases.OFF >/dev/null
#/bin/mv /usr/bin/mailq /usr/bin/mailq.OFF >/dev/null
#/bin/chmod 755 /usr/sbin/sendmail.OFF /usr/bin/newaliases.OFF /usr/bin/mailq.OFF >/dev/null

#  /usr/bin/make makefiles CCARGS="-DDEBIAN -DHAS_PCRE -DHAS_LDAP -DUSE_LDAP_SASL -DHAS_SQLITE -DMYORIGIN_FROM_FILE  -DHAS_CDB -DHAS_LMDB -DHAS_MYSQL -I/usr/include/mysql -I/usr/include/postgresql -DHAS_PGSQL -I`pg_config --includedir` -DHAS_SQLITE -I/usr/include -DHAS_SSL -I/usr/include/openssl -DUSE_SASL_AUTH -I/usr/include/sasl -DUSE_CYRUS_SASL -DUSE_TLS" DEBUG="" AUXLIBS="-lssl -lcrypto -lsasl2 -lpthread -L$(pwd)/debian" OPT="-O2" AUXLIBS_CDB="-lcdb -L../../lib -L. -lpostfix-util" AUXLIBS_LDAP="-lldap -llber -L../../lib -L. -lpostfix-util -lpostfix-global" AUXLIBS_LMDB="-llmdb -L../../lib -L. -lpostfix-util" AUXLIBS_MYSQL="-lmysqlclient -L../../lib -L. -lpostfix-util -lpostfix-global" AUXLIBS_PCRE="-lpcre -L../../lib -L. -lpostfix-util" AUXLIBS_PGSQL="-lpq -L../../lib -L. -lpostfix-util -lpostfix-global" AUXLIBS_SQLITE="-lsqlite3 -L../../lib -L. -lpostfix-util -lpostfix-global -lpthread" shared=yes pie=yes dynamicmaps=yes daemon_directory=/usr/libexec/postfix shlibs_directory=/usr/libexec/postfix

rm -rf /root/postfix-builder
mkdir -p /root/postfix-builder/etc/postfix
mkdir -p /root/postfix-builder/usr/libexec/postfix
mkdir -p /root/postfix-builder/usr/sbin
mkdir -p /root/postfix-builder/usr/bin
mkdir -p /root/postfix-builder/usr/lib/postfix

cp -rfvd /etc/postfix/* /root/postfix-builder/etc/postfix/
cp -rfvd /usr/libexec/postfix/* /root/postfix-builder/usr/libexec/postfix/
cp -rfvd /usr/lib/postfix/* /root/postfix-builder/usr/lib/postfix/

strip -s  /usr/sbin/postalias
strip -s  /usr/sbin/postcat
strip -s  /usr/sbin/postconf
strip -s  /usr/sbin/postfix
strip -s  /usr/sbin/postkick
strip -s  /usr/sbin/postlock
strip -s  /usr/sbin/postlog
strip -s  /usr/sbin/postmap
strip -s  /usr/sbin/postmulti
strip -s  /usr/sbin/postsuper
strip -s  /usr/sbin/postdrop
strip -s  /usr/sbin/postqueue
strip -s  /usr/sbin/sendmail
strip -s  /usr/bin/newaliases
strip -s  /usr/bin/mailq
strip -s /usr/libexec/postfix/*

cp -fvd /usr/sbin/postalias /root/postfix-builder/usr/sbin/
cp -fvd /usr/sbin/postcat /root/postfix-builder/usr/sbin/
cp -fvd /usr/sbin/postconf /root/postfix-builder/usr/sbin/
cp -fvd /usr/sbin/postfix /root/postfix-builder/usr/sbin/
cp -fvd /usr/sbin/postkick /root/postfix-builder/usr/sbin/
cp -fvd /usr/sbin/postlock /root/postfix-builder/usr/sbin/
cp -fvd /usr/sbin/postlog /root/postfix-builder/usr/sbin/
cp -fvd /usr/sbin/postmap /root/postfix-builder/usr/sbin/
cp -fvd /usr/sbin/postmulti /root/postfix-builder/usr/sbin/
cp -fvd /usr/sbin/postsuper /root/postfix-builder/usr/sbin/
cp -fvd /usr/sbin/postdrop /root/postfix-builder/usr/sbin/
cp -fvd /usr/sbin/postqueue /root/postfix-builder/usr/sbin/
cp -fvd /usr/sbin/sendmail /root/postfix-builder/usr/sbin/
cp -fvd /usr/bin/newaliases /root/postfix-builder/usr/bin/
cp -fvd /usr/bin/mailq /root/postfix-builder/usr/bin/

VERSION=`postconf -d | grep -E "mail_version\s+=" | cut -s -d' ' -f3`
FILEPATH="/root/postfix-compiled-$VERSION.tar.gz"
rm -f FILEPATH || true
cd /root/postfix-builder
echo "Compressing Postfix v$VERSION"
tar czf $FILEPATH *
echo $FILEPATH





