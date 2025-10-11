#!/bin/sh

BRANCH="2.8"
VERSION="2.8.0"
PACKAGE="haproxy-$VERSION.tar.gz"
TRABL="/root/$PACKAGE"
URI="http://www.haproxy.org/download/$BRANCH/src/$PACKAGE"
BUILDP="/root/haproxy-$VERSION"
TDIR="/root/compile-haproxy-$VERSION"
HAMODSEC="$BUILDP/contrib/modsecurity"

#git clone https://github.com/wolfSSL/wolfssl.git
 #cd wolfssl/
 #autoreconf -fi
 #./configure --enable-haproxy --enable-quic --prefix=/opt/wolfssl-5.6.0/
 #make -j $(nproc)
 #make install


cd /root
if [ ! -d $BUILDP ]; then
  if [ ! -f $TRABL ]; then
    echo "Downloading $PACKAGE"
    wget $URI -O $TRABL
  fi
  if [ ! -f $TRABL ]; then
    echo "Downloading $PACKAGE [FAILED]"
    exit 0
  fi

  cd /root
  echo "Extracting $TRABL"
  tar xf $TRABL

  if [ ! -d $BUILDP ]; then
    echo "Extracting $PACKAGE [FAILED]"
    exit 0
  fi
fi

echo "Entering into $BUILDP"
cd $BUILDP
make -j $(nproc) TARGET=linux-glibc PREFIX=/usr IGNOREGIT=true MANDIR=/usr/share/man ARCH=x86_64 DOCDIR=/usr/share/doc/haproxy USE_STATIC_PCRE=1 TARGET=linux-glibc CPU=generic USE_LINUX_SPLICE=1 USE_LINUX_TPROXY=1 USE_ZLIB=1 USE_REGPARM=1 USE_OPENSSL_WOLFSSL=1 USE_QUIC=1 SSL_INC=/opt/wolfssl-5.6.0/include SSL_LIB=/opt/wolfssl-5.6.0/lib USE_LUA=1

make install


strip -s /usr/local/sbin/haproxy
mkdir -p $TDIR/usr/local/sbin
mkdir -p $TDIR/usr/lib/x86_64-linux-gnu

cp -fd /usr/local/sbin/haproxy $TDIR/usr/local/sbin/
cp -av /opt/wolfssl-5.6.0/lib/* $TDIR/usr/lib/x86_64-linux-gnu/
rm -rf $TDIR/usr/lib/x86_64-linux-gnu/pkgconfig

CVERSION=`/usr/local/sbin/haproxy -v |cut -s -d' ' -f3`
echo "Now Haproxy is $CVERSION"
TAR="/root/haproxy-$VERSION.tar.gz"
echo "Compressing $TAR..."
cd $TDIR
tar -czvf $TAR *
echo "DONE..."

