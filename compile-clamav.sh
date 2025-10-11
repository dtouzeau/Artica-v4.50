#!/bin/bash

#git clone https://github.com/vrtadmin/clamav-devel
#./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=\${prefix}/include --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --disable-silent-rules --libexecdir=\${prefix}/lib/clamav --disable-maintainer-mode --disable-dependency-tracking --with-dbdir=/var/lib/clamav --sysconfdir=/etc/clamav --disable-milter --enable-dns-fix --with-libjson --with-system-libmspack --with-libcurl=/usr --with-gnu-ld "CFLAGS=-g -O2 -fstack-protector-strong -Wformat -Werror=format-security -Wall -D_FILE_OFFSET_BITS=64" "CPPFLAGS=-Wdate-time -D_FORTIFY_SOURCE=2" "CXXFLAGS=-g -O2 -fstack-protector-strong -Wformat -Werror=format-security -Wall -D_FILE_OFFSET_BITS=64" "LDFLAGS=-Wl,-z,relro -Wl,-z,now -Wl,--as-needed"


URL="https://www.clamav.net/downloads/production/clamav-1.4.2.linux.x86_64.deb"
version=`echo "$URL" | awk -F'clamav-' '{print $2}' | awk -F'.linux' '{print $1}'`
SRCDIR="/root/clamav-$version"
rm -rf $SRCDIR
finalTarBall="/root/clamav-$version.tar.gz"
echo "Using working directory $SRCDIR"
mkdir -p $SRCDIR

wget "$URL" -O $SRCDIR/clamav.deb
cd "$SRCDIR" || exit
ar -x clamav.deb
rm -f clamav.deb  control.tar.gz  debian-binary
tar -xf data.tar.gz
rm -f data.tar.gz
mv usr/local/* usr/
rm -rf usr/share/doc
rm -rf usr/share/man
strip -s usr/lib/libclamav_rust.a
rmdir usr/local
tar -cvf "$finalTarBall" *
echo "$finalTarBall done"

exit 0



