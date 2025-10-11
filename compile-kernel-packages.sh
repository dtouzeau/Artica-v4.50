#!/bin/sh

KERNEL=`uname -r`
MAINDIR="/root/kernel-modules-builder"
NDPIIPT="/usr/src/nDPI"

mkdir -p "$MAINDIR/usr/lib/x86_64-linux-gnu/xtables"

if [ ! -f "/usr/lib/modules/$KERNEL/kernel/net/pf_ring/pf_ring.ko" ]; then
  cd /root
  rm -rf /root/PF_RING
  git clone https://github.com/ntop/PF_RING.git
  cd /root/PF_RING/kernel
  make && make install
  insmod ./pf_ring.ko
  cd ../userland
  make && make install
  cd /root
fi

if [ ! -f "/usr/lib/modules/$KERNEL/extra/xt_ndpi.ko" ]; then
  rm -rf $NDPIIPT
  cd /usr/src
  git clone https://github.com/vel21ripn/nDPI.git
  cd nDPI
  ./autogen.sh
  ./configure --prefix=/usr/local/ndpi --sysconfdir=/etc/ndpi
  make && make install
  cd $NDPIIPT/ndpi-netfilter
  make && make modules_install
  if [ ! -f "$NDPIIPT/ndpi-netfilter/ipt/libxt_ndpi.so" ]; then
    echo "$NDPIIPT/ndpi-netfilter/ipt/libxt_ndpi.so no such file"
    exit 0
  fi
fi

 if [ ! -f "$NDPIIPT/ndpi-netfilter/ipt/libxt_ndpi.so" ]; then
    echo "$NDPIIPT/ndpi-netfilter/ipt/libxt_ndpi.so no such file"
    exit 0
 fi


echo "Building destination directory... $KERNEL"
TESTKERNS=""
TESTKERNS="${TESTKERNS} 4.19.0-16-amd64"
TESTKERNS="${TESTKERNS} 4.19.0-17-amd64"
TESTKERNS="${TESTKERNS} $KERNEL"


mkdir -p "$MAINDIR/usr/lib/x86_64-linux-gnu/xtables"
cp $NDPIIPT/ndpi-netfilter/ipt/libxt_ndpi.so $MAINDIR/usr/lib/x86_64-linux-gnu/xtables/
cp $NDPIIPT/ndpi-netfilter/ipt/libxt_ndpi.so /usr/lib/x86_64-linux-gnu/xtables/libxt_ndpi.so

for TKERN in ${TESTKERNS}; do
    echo "Testing $TKERN"
    SRFILE="/usr/lib/modules/$TKERN/kernel/net/pf_ring/pf_ring.ko"
    if [ -f $SRFILE ]; then
      echo "Installing module pf_ring for ... $TKERN"
      mkdir -p "$MAINDIR/usr/lib/modules/$TKERN/kernel/net/pf_ring"
      cp -fd $SRFILE "$MAINDIR/usr/lib/modules/$TKERN/kernel/net/pf_ring/"
    fi

    SRFILE="/usr/lib/modules/$TKERN/extra/xt_ndpi.ko"
    if [ -f $SRFILE ]; then
      echo "Installing module xt_ndpi for ... $TKERN"
      mkdir -p "$MAINDIR/usr/lib/modules/$TKERN/extra"
      cp -fd $SRFILE "$MAINDIR/usr/lib/modules/$TKERN/extra/"
    fi

done





