#!/bin/sh

# apt-get install texinfo
# git clone git://git.code.sf.net/p/msmtp/code msmtp
# cd msmtp
# autoreconf -i
# ./configure --prefix=/usr --bindir=/usr/bin --sbindir=/usr/bin

rm -rf /root/msmtp-builder
mkdir -p /root/msmtp-builder/usr/share/gettext/po
mkdir -p /root/msmtp-builder/usr/bin
mkdir -p /root/msmtp-builder/usr/share/artica-postfix/bin

cp -rvd /usr/share/gettext/po/remove-potcdate.sin /usr/share/gettext/po/usr/share/gettext/po/
cp -rvd /usr/share/gettext/po/Makefile.in.in /root/msmtp-builder/usr/share/gettext/po/
cp -rvd /usr/share/gettext/po/remove-potcdate.sin /root/msmtp-builder/usr/share/gettext/po/
cp -rvd /usr/share/gettext/po/quot.sed boldquot.sed /root/msmtp-builder/usr/share/gettext/po/
cp -rvd /usr/share/gettext/po/en@quot.header /root/msmtp-builder/usr/share/gettext/po/
cp -rvd /usr/share/gettext/po/en@boldquot.header /root/msmtp-builder/usr/share/gettext/po/
cp -rvd /usr/share/gettext/po/insert-header.sin /root/msmtp-builder/usr/share/gettext/po/
cp -rvd /usr/share/gettext/po/Rules-quot /root/msmtp-builder/usr/share/gettext/po/
cp -rvd /usr/share/gettext/po/Makevars.template /root/msmtp-builder/usr/share/gettext/po/


cp -fd /usr/bin/msmtp /root/msmtp-builder/usr/bin/
cp -fd /usr/bin/msmtp /root/msmtp-builder/usr/share/artica-postfix/bin/
strip -s /root/msmtp-builder/usr/bin/msmtp
strip -s /root/msmtp-builder/usr/bin/msmtp

VERSION=`msmtp --version| cut -s -d' ' -f3|grep -E "[0-9]+\.[0-9]+\.[0-9]+"`
FILEPATH="/root/msmtp-$VERSION.tar.gz"
cd /root/msmtp-builder
tar czf $FILEPATH *
echo $FILEPATH
