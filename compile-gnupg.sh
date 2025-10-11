#!/bin/sh
# wget https://gnupg.org/ftp/gcrypt/libassuan/libassuan-2.5.1.tar.bz2
# tar -xf libassuan-2.5.1.tar.bz2
# cd libassuan-2.5.1/
# ./configure --prefix=/opt/artica/libs
# make && make install
# cd /root/

# wget https://gnupg.org/ftp/gcrypt/libksba/libksba-1.3.5.tar.bz2
# tar -xf libksba-1.3.5.tar.bz2
# cd libksba-1.3.5
# ./configure --prefix=/opt/artica/libs
# make && make install
# cd /root/

# wget https://gnupg.org/ftp/gcrypt/npth/npth-1.5.tar.bz2
# tar -xf npth-1.5.tar.bz2 
# cd npth-1.5/
# ./configure --prefix=/opt/artica/libs
# make && make install
# cd /root/

# wget https://www.gnupg.org/ftp/gcrypt/gnupg/gnupg-2.2.6.tar.bz2
# tar -xf gnupg-2.2.6.tar.bz2 
# cd gnupg-2.2.6/
# ./autogen.sh 


#./configure --prefix=/usr --with-libassuan-prefix=/opt/artica/libs --with-ksba-prefix=/opt/artica/libs --with-npth-prefix=/opt/artica/libs --disable-sqlite --disable-ldap LDFLAGS="-static"
# make && make install

rm -rf /root/gnupg-compile

mkdir -p /root/gnupg-compile/usr/share/gnupg
mkdir -p /root/gnupg-compile/opt/artica/libs/lib
mkdir -p /root/gnupg-compile/opt/artica/libs/share/info
mkdir -p /root/gnupg-compile/opt/artica/libs/share/aclocal
mkdir -p /root/gnupg-compile/opt/artica/libs/bin
mkdir -p /root/gnupg-compile/opt/artica/libs/include
mkdir -p /root/gnupg-compile/usr/bin
mkdir -p /root/gnupg-compile/usr/sbin
mkdir -p /root/gnupg-compile/usr/libexec


cp -fvd /opt/artica/libs/bin/ksba-config
cp -fvd /opt/artica/libs/bin/npth-config
cp -fvd /opt/artica/libs/include/ksba.h
cp -fvd /opt/artica/libs/include/npth.h
cp -fvd /opt/artica/libs/include/assuan.h
cp -fvd /opt/artica/libs/share/info/ksba.info
cp -fvd /opt/artica/libs/share/info/assuan.info
cp -fvd /opt/artica/libs/share/aclocal/ksba.m4
cp -fvd /opt/artica/libs/share/aclocal/npth.m4
cp -fvd /opt/artica/libs/share/aclocal/libassuan.m4
cp -fvd /opt/artica/libs/lib/libksba.la
cp -fvd /opt/artica/libs/lib/libnpth.so.0.1.1
cp -fvd /opt/artica/libs/lib/libksba.so.8
cp -fvd /opt/artica/libs/lib/libksba.so.8.11.6
cp -fvd /opt/artica/libs/lib/libassuan.la
cp -fvd /opt/artica/libs/lib/libassuan.so.0.8.1
cp -fvd /opt/artica/libs/lib/libnpth.la
cp -fvd /opt/artica/libs/lib/libassuan.so.0
cp -fvd /opt/artica/libs/lib/libnpth.so.0
cp -fvd /opt/artica/libs/lib/libassuan.so
cp -fvd /opt/artica/libs/lib/libksba.so
cp -fvd /opt/artica/libs/lib/libnpth.so

strip -s /opt/artica/libs/bin/libassuan-config
strip -s /opt/artica/libs/bin/ksba-config
strip -s /opt/artica/libs/bin/npth-config

strip -s /usr/bin/gpg
strip -s /usr/bin/gpgv
strip -s /usr/bin/gpgsm
strip -s /usr/bin/gpg-agent
strip -s /usr/bin/dirmngr
strip -s /usr/bin/dirmngr-client
strip -s /usr/bin/gpgconf 
strip -s /usr/bin/gpg-connect-agent 
strip -s /usr/bin/watchgnupg 
strip -s /usr/bin/gpgparsemail 
strip -s /usr/bin/gpgtar

strip -s /usr/sbin/addgnupghome 
strip -s /usr/sbin/applygnupgdefaults
 
strip -s /usr/libexec/gpg-protect-tool
strip -s /usr/libexec/gpg-preset-passphrase
strip -s /usr/libexec/scdaemon
strip -s /usr/libexec/gpg-wks-client 
strip -s /usr/libexec/gpg-check-pattern


cp -rfvd /usr/share/gnupg/* /root/gnupg-compile/usr/share/gnupg/

cp -fvd /opt/artica/libs/bin/libassuan-config /root/gnupg-compile/opt/artica/libs/bin/
cp -fvd /opt/artica/libs/bin/ksba-config /root/gnupg-compile/opt/artica/libs/bin/
cp -fvd /opt/artica/libs/bin/npth-config /root/gnupg-compile/opt/artica/libs/bin/

cp -fvd /usr/bin/gpg /root/gnupg-compile/usr/bin/
cp -fvd /usr/bin/gpgv /root/gnupg-compile/usr/bin/
cp -fvd /usr/bin/gpgsm /root/gnupg-compile/usr/bin/
cp -fvd /usr/bin/gpg-agent /root/gnupg-compile/usr/bin/
cp -fvd /usr/bin/dirmngr /root/gnupg-compile/usr/bin/
cp -fvd /usr/bin/dirmngr-client /root/gnupg-compile/usr/bin/
cp -fvd /usr/bin/gpgconf /root/gnupg-compile/usr/bin/
cp -fvd /usr/bin/gpg-connect-agent /root/gnupg-compile/usr/bin/
cp -fvd /usr/bin/watchgnupg /root/gnupg-compile/usr/bin/
cp -fvd /usr/bin/gpgparsemail /root/gnupg-compile/usr/bin/
cp -fvd /usr/bin/gpgtar /root/gnupg-compile/usr/bin/

cp -fvd /usr/sbin/addgnupghome /root/gnupg-compile/usr/sbin/ 
cp -fvd /usr/sbin/applygnupgdefaults /root/gnupg-compile/usr/sbin/
 
cp -fvd /usr/libexec/gpg-protect-tool /root/gnupg-compile/usr/libexec/
cp -fvd /usr/libexec/gpg-preset-passphrase /root/gnupg-compile/usr/libexec/
cp -fvd /usr/libexec/scdaemon /root/gnupg-compile/usr/libexec/
cp -fvd /usr/libexec/gpg-wks-client /root/gnupg-compile/usr/libexec/
cp -fvd /usr/libexec/gpg-check-pattern /root/gnupg-compile/usr/libexec/

VERSION=`/usr/bin/gpg --version|grep -E '^gpg'|cut -s -d' ' -f3`
FILEPATH="/root/gpg-compiled-$VERSION.tar.gz"
rm -f FILEPATH || true
cd /root/gnupg-compile
echo "Compressing gpg v$VERSION"
tar czf $FILEPATH *
echo $FILEPATH



