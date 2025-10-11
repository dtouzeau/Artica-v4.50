#!/bin/sh
KERNEL=`uname -r`
KOFILES=""
WORKDIR="/root/ntopng-builder"
MAINDIR="$WORKDIR"
rm -rf $WORKDIR
echo "Building destination directory... $KERNEL"
TESTKERNS=""
TESTKERNS="${TESTKERNS} 4.19.0-5-amd64"
TESTKERNS="${TESTKERNS} 4.19.0-6-amd64"
TESTKERNS="${TESTKERNS} 4.19.0-8-amd64"
TESTKERNS="${TESTKERNS} 4.19.0-9-amd64"
TESTKERNS="${TESTKERNS} 4.19.0-10-amd64"
TESTKERNS="${TESTKERNS} 4.19.0-12-amd64"
TESTKERNS="${TESTKERNS} 4.19.0-13-amd64"
TESTKERNS="${TESTKERNS} 4.19.0-16-amd64"
TESTKERNS="${TESTKERNS} 4.19.0-17-amd64"

DELF=""
DELF="${DELF} usr/local/lib/libndpi.so.3.1.0"
DELF="${DELF} usr/local/lib/libpfring.so.7.5.0"
DELF="${DELF} usr/local/lib/libpfring.so.7.7.0"


  

for TKERN in ${TESTKERNS}; do
    echo "Testing $TKERN"
    SRFILE="/usr/lib/modules/$TKERN/kernel/net/pf_ring/pf_ring.ko"
    if [ -f $SRFILE ]; then
      echo "Installing module for ... $TKERN"
      mkdir -p "$MAINDIR/usr/lib/modules/$TKERN/kernel/net/pf_ring"
      cp -fd $SRFILE "$MAINDIR/usr/lib/modules/$TKERN/kernel/net/pf_ring/"
    fi
done

strip -s /usr/local/bin/ntopng
strip -s /usr/bin/ntopng
mkdir -p $WORKDIR/usr/local/man/man8
mkdir -p $WORKDIR/usr/local/lib
mkdir -p $WORKDIR/usr/local/bin
mkdir -p $WORKDIR/usr/local/share/ntopng
mkdir -p $WORKDIR/usr/lib
mkdir -p $WORKDIR/usr/bin
mkdir -p $WORKDIR/usr/lib/x86_64-linux-gnu/xtables

cp -fd /usr/man/man8/ntopng.8 $WORKDIR/usr/local/man/man8/
cp -fd /usr/bin/ntopng /usr/local/bin/ntopng
cp -fd /usr/local/bin/ntopng $WORKDIR/usr/local/bin/
cp -rfd /usr/share/ntopng/* $WORKDIR/usr/local/share/ntopng/
cp -fd /usr/lib/x86_64-linux-gnu/xtables/libxt_ndpi.so $WORKDIR/usr/lib/x86_64-linux-gnu/xtables/


if [ -f /lib/modules/$KERNEL/extra/xt_ndpi.ko ]; then
  mkdir -p $WORKDIR/lib/modules/$KERNEL/extra
  cp -fd /lib/modules/$KERNEL/extra/xt_ndpi.ko $WORKDIR/lib/modules/$KERNEL/extra/
fi

cp -fd /usr/local/lib/libpfring* $WORKDIR/usr/local/lib/
cp -fd /usr/local/lib/libndpi* $WORKDIR/usr/local/lib/
cp -fd /root/PF_RING/userland/libpcap/libpcap* $WORKDIR/usr/local/lib/
cp -fd /usr/lib/libmaxminddb.* $WORKDIR/usr/lib/
echo "Copy in destination directory..."

#VERSION=`/usr/local/bin/ntopng -V|grep Community 2>&1|awk '{match($1,"version.*?([0-9.]+)",m)}END{print m[1]}'`
VERSION="4.3.210711"
echo "Creating package $VERSION"

for DELFILE in ${DELF}; do
  echo "Removing $WORKDIR/$DELFILE"
  rm -f "$WORKDIR/$DELFILE"
done

cd $WORKDIR 
tar -cvf $WORKDIR.$VERSION.tar.gz *
cd /root
echo "$WORKDIR.$VERSION.tar.gz done.."
