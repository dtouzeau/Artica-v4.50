#!/bin/bash
#apt-get install apt-get install build-essential git-core cmake pkg-config libtool
# apt-get install libxxhash-dev libzstd-dev libssl-dev liblz4-dev
. /etc/os-release
VERSION="3.3.0pre1"

cd /root




if [ ! -d /root/rsync-$VERSION ]; then
  if [ ! -f /root/rsync-$VERSION.tar.gz ]; then
     wget https://download.samba.org/pub/rsync/src-previews/rsync-$VERSION.tar.gz -O /root/rsync-$VERSION.tar.gz
 fi
  tar -xf /root/rsync-$VERSION.tar.gz -C /root/
fi

if [ ! -f /root/rsync-$VERSION.tar.gz ]; then
  rm -f /root/rsync-$VERSION.tar.gz
fi

cd /root/rsync-$VERSION
./configure --disable-zstd --disable-md2man --disable-xxhash && make && make install

if [ ! -f /root/rsync-$VERSION/Makefile ]; then
  echo "Configure failed"
  exit 1
fi
make && make install
TARBALL="/root/rsync-Debian$VERSION_ID-$VERSION.tar.gz"




mkdir -p "/root/rsync-package/usr/local/bin"

cp -f /usr/local/bin/rsync /root/rsync-package/usr/local/bin/rsync
cp -f /usr/local/bin/rsync-ssl /root/rsync-package/usr/local/bin/rsync-ssl
chmod 0755 /root/rsync-package/usr/local/bin/rsync
chmod 0755 /root/rsync-package/usr/local/bin/rsync-ssl
cd /root/rsync-package
tar -czvf $TARBALL *
cd /
echo "$TARBALL OK"