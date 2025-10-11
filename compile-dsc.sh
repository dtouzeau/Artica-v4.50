#!/bin/sh
IPATH="/root/dc-builder"
CPATH="/root/dsc"
DPATH="/root/dsp"

cd /root

if [ ! -d $IPATH ]; then
  rm -rf $IPATH
fi

if [ -d $CPATH ]; then
  rm -rf $CPATH
fi
if [ -d $DPATH ]; then
  rm -rf $DPATH
fi

git clone https://github.com/DNS-OARC/dsc.git /root/dsc

if [ ! -f $CPATH/autogen.sh ];then
  echo "$CPATH/autogen.sh no such file or directory"
  exit 0
fi

cd $CPATH
git submodule update --init
./autogen.sh
if [ ! -f $CPATH/configure ];then
  echo "$CPATH/configure no such file or directory"
  exit 0
fi


./configure --prefix=/usr --bindir=/usr/local/bin --sbindir=/usr/local/sbin --with-data-dir=/home/artica/dsc --sysconfdir=/etc --mandir=/usr/share/man --infodir=/usr/share/info --libdir=/usr/lib --libexecdir=/usr/lib --with-pid-file=/var/run/dsc.pid

make
if [ ! -f $CPATH/src/dsc ];then
  echo "$CPATH/src/dsc no such file or directory"
  exit 0
fi

make install

strip -s /usr/local/bin/dsc
mkdir -p $IPATH/usr/local/bin
cp -fd /usr/local/bin/dsc $IPATH/usr/local/bin/
VERSION=`/usr/local/bin/dsc -v 2>&1|grep -E 'version\s+'|cut -s -d' ' -f3`
TARB="dsc-$VERSION.tar.gz"

cd $IPATH
tar -czvf /root/$TARB *
cd /root
echo "/root/$TARB done"




