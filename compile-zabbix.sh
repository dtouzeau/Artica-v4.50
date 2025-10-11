#!/bin/sh
DIST="stretch"
MAIN_BRANCH="6.4"
VER="6.4.2-1"
DIR="/home/dtouzeau/zabbix-$VER"
URL_OLD=""http://repo.zabbix.com/zabbix/$MAIN_BRANCH/debian/pool/main/z/zabbix/zabbix-agent_$VER%2Bstretch_amd64.deb" -O $DIR/$VER.deb"
URL="https://repo.zabbix.com/zabbix/$MAIN_BRANCH/debian/pool/main/z/zabbix/zabbix-agent_$VER%2Bdebian10_amd64.deb"


if [ -d $DIR ]; then
  rm -rf $DIR
fi
mkdir -p $DIR
echo "Downloading $VER for $DIST in $DIR"

wget $URL -O $DIR/$VER.deb

if [ ! -f "$DIR/$VER.deb" ]; then
  echo "$DIR/$VER no such file"
fi

if [ ! -d  $DIR ]; then
  echo "$DIR no such directory"
  exit 1
  fi
cd $DIR
echo "Extracting $DIR/$VER"
ar -x $VER.deb
rm control.tar.xz control.tar.gz debian-binary $VER.deb || true

echo "Extracting $DIR/data.tar.xz"
tar -xf data.tar.xz && rm data.tar.xz

if [ ! -d  $DIR/usr/share/doc ]; then
  echo "usr/share/doc no such dir"
  exit 1
fi

if [ ! -d  $DIR/usr/share/man ]; then
  echo "usr/share/man no such dir"
  exit 1
fi

rm -f etc/init.d/zabbix-agent
rm -rf usr/share/doc
rm -rf usr/share/man
rm -rf lib/systemd
rm -rf etc/logrotate.d
echo "Compressing $DIR/$VER.tar.gz"
tar -czvf $VER.tar.gz *
echo "Compiled into $DIR/$VER.tar.gz"
