#!/usr/bin/env bash

# download nodejs https://nodejs.org/en/download/prebuilt-binaries

VERSION="20.17.0"
URI="https://nodejs.org/dist/v$VERSION/node-v$VERSION-linux-x64.tar.xz"
WORKDIR="/root/compile-nodejs"
NODESRCDIR="/root/node-v$VERSION-linux-x64"

rm -rf $WORKDIR
mkdir -p $WORKDIR/usr/local/nodejs/bin
mkdir -p $WORKDIR/usr/local/nodejs/include
mkdir -p $WORKDIR/usr/local/nodejs/share
mkdir -p $WORKDIR/usr/local/nodejs/opt
mkdir -p $WORKDIR/usr/local/nodejs/lib
mkdir -p $WORKDIR/usr/lib/tls/haswell/x86_64
wget $URI -O /root/nodejs-$VERSION.xz
tar -xf /root/nodejs-$VERSION.xz -C /root/

echo "Copy source from $NODESRCDIR to $WORKDIR"
cp -rf $NODESRCDIR/bin/* $WORKDIR/usr/local/nodejs/bin/
cp -rf $NODESRCDIR/include/* $WORKDIR/usr/local/nodejs/include/
cp -rf $NODESRCDIR/share/* $WORKDIR/usr/local/nodejs/share/
cp -rf $NODESRCDIR/lib/* $WORKDIR/usr/local/nodejs/lib/

ln -sf /root/compile-nodejs/usr/local/nodejs/bin/node /usr/local/bin/node
ln -sf /root/compile-nodejs/usr/local/nodejs/bin/npm /usr/local/bin/npm

/usr/local/bin/npm install -g sitespeed.io --prefix $WORKDIR/usr/local/nodejs

echo "Downloading Chrome"
rm -rf /root/chrome
mkdir -p /root/chrome
wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb -O /root/chrome/chrome.deb
cd /root/chrome || exit
echo "Extracting Chrome"
ar -x chrome.deb
rm chrome.deb  control.tar.xz  debian-binary
tar -xf data.tar.xz
rm data.tar.xz
cp -rf /root/chrome/opt/* $WORKDIR/usr/local/nodejs/opt/

echo "Downloading libatk1.0-0 for Debian 10"
rm -rf /root/libatk1
mkdir -p /root/libatk1
wget http://ftp.us.debian.org/debian/pool/main/a/atk1.0/libatk1.0-0_2.30.0-2_amd64.deb -O /root/libatk1/libatk1.deb
cd /root/libatk1 || exit 1
ar -x libatk1.deb
rm libatk1.deb  control.tar.xz  debian-binary
tar -xf data.tar.xz
rm data.tar.xz
echo "Downloading libatk-bridge2 for Debian 10"
wget http://ftp.us.debian.org/debian/pool/main/a/at-spi2-atk/libatk-bridge2.0-0_2.30.0-5_amd64.deb -O /root/libatk1/libatk1bridge2.deb
ar -x libatk1bridge2.deb
rm libatk1bridge2.deb  control.tar.xz  debian-binary
tar -xf data.tar.xz
rm data.tar.xz

echo "Downloading libcups2"

wget http://security.debian.org/debian-security/pool/updates/main/c/cups/libcups2_2.2.10-6+deb10u10_amd64.deb -O /root/libatk1/libcups2.deb
ar -x libcups2.deb
rm libcups2.deb  control.tar.xz  debian-binary
tar -xf data.tar.xz
rm data.tar.xz

echo "Downloading libxkbcommon"

wget http://ftp.us.debian.org/debian/pool/main/libx/libxkbcommon/libxkbcommon0_0.8.2-1_amd64.deb -O /root/libatk1/libxkbcommon.deb
ar -x libxkbcommon.deb
rm libxkbcommon.deb  control.tar.xz  debian-binary
tar -xf data.tar.xz
rm data.tar.xz

echo "Downloading libatspi2.0-0"
wget http://ftp.us.debian.org/debian/pool/main/a/at-spi2-core/libatspi2.0-0_2.30.0-7_amd64.deb -O /root/libatk1/libatspi2.deb
ar -x libatspi2.deb
rm libatspi2.deb  control.tar.xz  debian-binary
tar -xf data.tar.xz
rm data.tar.xz

echo "Downloading libxcomposite1"
wget http://ftp.us.debian.org/debian/pool/main/libx/libxcomposite/libxcomposite1_0.4.4-2_amd64.deb  -O /root/libatk1/libxcomposite1.deb
ar -x libxcomposite1.deb
rm libxcomposite1.deb  control.tar.xz  debian-binary
tar -xf data.tar.xz
rm data.tar.xz

echo "Downloading libxrandr2"
wget http://ftp.us.debian.org/debian/pool/main/libx/libxrandr/libxrandr2_1.5.1-1_amd64.deb  -O /root/libatk1/libxrandr2.deb
ar -x libxrandr2.deb
rm libxrandr2.deb
rm control.tar.xz || true
rm debian-binary
rm control.tar.gz || true
tar -xf data.tar.xz
rm data.tar.xz

echo "Downloading libgbm1"
wget http://ftp.us.debian.org/debian/pool/main/m/mesa/libgbm1_18.3.6-2+deb10u1_amd64.deb  -O /root/libatk1/libgbm1.deb
ar -x libgbm1.deb
rm libgbm1.deb
rm control.tar.xz || true
rm debian-binary
rm control.tar.gz || true
tar -xf data.tar.xz
rm data.tar.xz

echo "Downloading libasound2"
wget http://ftp.us.debian.org/debian/pool/main/a/alsa-lib/libasound2_1.1.8-1_amd64.deb -O /root/libatk1/libasound2.deb
ar -x libasound2.deb
rm libasound2.deb
rm control.tar.xz || true
rm debian-binary
rm control.tar.gz || true
tar -xf data.tar.xz
rm data.tar.xz

echo "Downloading libavahi-common3"
wget http://security.debian.org/debian-security/pool/updates/main/a/avahi/libavahi-common3_0.7-4+deb10u3_amd64.deb -O /root/libatk1/libavahi-common3.deb
ar -x libavahi-common3.deb
rm libavahi-common3.deb
rm control.tar.xz || true
rm debian-binary
rm control.tar.gz || true
tar -xf data.tar.xz
rm data.tar.xz

echo "libavahi-client3"
wget http://security.debian.org/debian-security/pool/updates/main/a/avahi/libavahi-client3_0.7-4+deb10u3_amd64.deb -O /root/libatk1/libavahi-client3.deb
ar -x libavahi-client3.deb
rm libavahi-client3.deb
rm control.tar.xz || true
rm debian-binary
rm control.tar.gz || true
tar -xf data.tar.xz
rm data.tar.xz


echo "libwayland-server0"
wget http://ftp.us.debian.org/debian/pool/main/w/wayland/libwayland-server0_1.16.0-1_amd64.deb -O /root/libatk1/libwayland-server0.deb
ar -x libwayland-server0.deb
rm llibwayland-server0.deb
rm control.tar.xz || true
rm debian-binary
rm control.tar.gz || true
tar -xf data.tar.xz
rm data.tar.xz



SRCLIB="/root/libatk1/usr/lib/x86_64-linux-gnu"
haswell="$WORKDIR/usr/lib/tls/haswell/x86_64"

cp $SRCLIB/libatk-1.0.so.0.23009.1 $haswell/libatk-1.0.so.0
cp $SRCLIB/libatk-bridge-2.0.so.0.0.0 $haswell/libatk-bridge-2.0.so.0
cp $SRCLIB/libcups.so.2 $haswell/libcups.so.2
cp $SRCLIB/libxkbcommon.so.0.0.0 $haswell/libxkbcommon.so.0
cp $SRCLIB/libatspi.so.0.0.1 $haswell/libatspi.so.0
cp $SRCLIB/libXcomposite.so.1.0.0  $haswell/libXcomposite.so.1
cp $SRCLIB/libXrandr.so.2.2.0 $haswell/libXrandr.so.2
cp $SRCLIB/libgbm.so.1.0.0 $haswell/libgbm.so.1
cp $SRCLIB/libasound.so.2.0.0 $haswell/libasound.so.2
cp $SRCLIB/libavahi-common.so.3.5.3 $haswell/libavahi-common.so.3
cp $SRCLIB/libavahi-client.so.3.2.9 $haswell/libavahi-client.so.3
cp $SRCLIB/libwayland-server.so.0.1.0 $haswell/libwayland-server.so.0