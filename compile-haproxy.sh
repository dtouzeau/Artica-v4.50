#!/bin/sh

BRANCH="2.4"
VERSION="2.4.0"
PACKAGE="haproxy-$VERSION.tar.gz"
TRABL="/root/$PACKAGE"
URI="http://www.haproxy.org/download/$BRANCH/src/$PACKAGE"
BUILDP="/root/haproxy-$VERSION"
TDIR="/root/compile-haproxy-$VERSION"
HAMODSEC="$BUILDP/contrib/modsecurity"




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
make TARGET=linux-glibc PREFIX=/usr IGNOREGIT=true MANDIR=/usr/share/man ARCH=x86_64 DOCDIR=/usr/share/doc/haproxy USE_STATIC_PCRE=1 TARGET=linux-glibc CPU=generic USE_LINUX_SPLICE=1 USE_LINUX_TPROXY=1 USE_OPENSSL=1 USE_ZLIB=1 USE_REGPARM=1

make install


strip -s /usr/local/sbin/haproxy
mkdir -p $TDIR/usr/local/sbin
cp -fd /usr/local/sbin/haproxy $TDIR/usr/local/sbin/
CVERSION=`/usr/local/sbin/haproxy -v |cut -s -d' ' -f3`
echo "Now Haproxy is $CVERSION"
TAR="/root/haproxy-compiled-$VERSION.tar.gz"
echo "Compressing $TAR..."
cd $TDIR
tar -czf $TAR *
echo "DONE..."

