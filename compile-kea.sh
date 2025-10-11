#!/bin/bash

USER="dtouzeau"
DPATH="/home/$USER/kea-deb"
DPAK="/home/$USER/kea-package"
URL="ftp.us.debian.org/debian/pool/main/i/isc-kea"

# get https://packages.debian.org/fr/bookworm/amd64/kea-admin/download
# https://packages.debian.org/fr/bookworm/amd64/kea-common/download
# https://packages.debian.org/fr/bookworm/amd64/kea-ctrl-agent/download
VERSION="2.2.0-6"
rm -rf $DPATH
mkdir -p $DPATH
mkdir -p $DPAK
wget http://$URL/kea-admin_${VERSION}_amd64.deb -O $DPATH/keadmin.deb
wget http://$URL/kea-common_${VERSION}_amd64.deb -O $DPATH/keacommon.deb
wget http://$URL/kea-ctrl-agent_${VERSION}_amd64.deb -O $DPATH/keactrlagent.deb
wget http://$URL/kea-dhcp-ddns-server_${VERSION}_amd64.deb -O $DPATH/keadhcpddns.deb
wget http://$URL/kea-dhcp6-server_${VERSION}_amd64.deb -O $DPATH/keadhcp6.deb
wget http://$URL/kea-dhcp4-server_${VERSION}_amd64.deb -O $DPATH/keadhcp4.deb

FNAMES=("keadmin" "keacommon" "keactrlagent" "keadhcpddns" "keadhcp6" "keadhcp4")

cd "$DPATH"

for fn in "${FNAMES[@]}"; do
  echo "Uncompresss $fn.deb"
  ar -x $fn.deb
  if [ -f "$DPATH/control.tar.xz" ]; then
    rm -f "$DPATH/control.tar.xz"
  fi
  if [ -f "$DPATH/debian-binary" ]; then
      rm -f "$DPATH/debian-binary"
  fi
  if [ -f "$DPATH/data.tar.xz" ]; then
    echo "Uncompress $DPATH/data.tar.xz"
    tar -xf "$DPATH/data.tar.xz" -C $DPATH/
    rm -f "$DPATH/data.tar.xz"
    rm -f "$DPATH/$fn.deb"
  else
    echo "Uncompress $fn $DPATH/data.tar.xz not found"
    exit
  fi
done

echo "Cleaning..."
rm -rf "$DPATH/usr/share/man"
rm -rf "$DPATH/usr/share/doc"
echo "Compressing to $DPAK/kea-debian12-$VERSION.tar.gz"
tar -h -czf $DPAK/kea-debian12-$VERSION.tar.gz *
